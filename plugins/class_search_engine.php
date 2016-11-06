<?php
if(!defined('IN_PRGM') || !function_exists('sd_GetCurrentFolder')) return false;

if(!class_exists('SearchEngineClass'))
{

// ############################################################################
// SEARCH ENGINE CLASS
// ############################################################################

class SearchEngineClass
{
  public  $pluginid     = 0;
  public  $settings     = false;
  public  $phrases      = false;
  private $_smarty      = false;
  //SD360: props for general use across functions
  private $_highlight   = '';
  private $_searchterm  = '';
  private $_searchwords  = '';
  private $_items_per_page = 10;
  private $_page           = 1;
  private $_limit          = '';
  private $_results        = '';


  public function __construct($plugin_folder) // v1.3.6
  {
    if($this->pluginid = GetPluginIDbyFolder($plugin_folder))
    {
      $this->settings = GetPluginSettings($this->pluginid);
      $this->phrases = GetLanguage($this->pluginid);
    }
  }

  // ##########################################################################

  private function _InitTemplate() // v1.3.6
  {
    global $categoryid, $sdlanguage, $userinfo;

    // Instantiate smarty object
    $this->_smarty = SD_Smarty::getNew();
    $this->_smarty->assign('categoryid',    $categoryid);
    $this->_smarty->assign('sdlanguage',    $sdlanguage);
    $this->_smarty->assign('sdurl',         SITE_URL);
    $this->_smarty->assign('securitytoken', $userinfo['securitytoken']);

    $this->_smarty->assign('pluginid',      $this->pluginid);
    $this->_smarty->assign('phrases',       $this->phrases);
    $this->_smarty->assign('settings',      $this->settings);
  } //_InitTemplate

  // ##########################################################################

  function SearchForm($error_text='')
  {
    global $DB, $pluginid, $categoryid, $mainsettings_search_results_page, $userinfo;

    //SD343: use special search results page
    $target = !empty($mainsettings_search_results_page) ? (int)$mainsettings_search_results_page : (int)$categoryid;

    if(empty($this->settings['search_plugins']))
    {
      if(!empty($userinfo['adminaccess'])) echo $this->phrases['no_search_params'];
      return;
    }

    // Get the search term
    $searchterm = GetVar('q', '', 'string');

    // v1.3.3: code moved to header.php!
    $this->_InitTemplate();
    $this->_smarty->assign('searchString', $searchterm);
    $this->_smarty->assign('target_page',  $target);
    $this->_smarty->assign('target_url',   RewriteLink('index.php?categoryid='.$target));

    // v1.3.6: display Search Form by template
    if(defined('SD_SMARTY_VERSION') && (SD_SMARTY_VERSION > 2)) //SD344
    {
      if(!SD_Smarty::display($this->pluginid, 'search_engine_form.tpl'))
      {
        if(!empty($userinfo['adminaccess']))
        {
          echo '<br /><strong>Seach Engine Form ('.$this->pluginid.') template NOT FOUND!</strong><br />';
        }
      }
    }
    else
    {
      $tmpl_done = false;
      if(is_file(SD_INCLUDE_PATH.'tmpl/search_engine_form.tpl'))
      {
        $this->_smarty->display('search_engine_form.tpl');
        $tmpl_done = true;
      }
      else
      if(is_file(SD_INCLUDE_PATH.'tmpl/defaults/search_engine_form.tpl'))
      {
        $this->_smarty->setTemplateDir(SD_INCLUDE_PATH.'tmpl/defaults/');
        $this->_smarty->display('search_engine_form.tpl');
        $tmpl_done = true;
      }
      if(!$tmpl_done)
      {
        if(!empty($userinfo['adminaccess']))
        {
          echo '<br /><strong>Seach Engine Form ('.$this->pluginid.') template file NOT FOUND!</strong><br />';
        }
      }
    }
    unset($this->_smarty);
  } //SearchForm

  // ##########################################################################

  function DisplayArticleResults($pid)
  {
    //SD360: encapsulated code for cloned plugins support
    global $DB, $categoryid, $userinfo;

    // Build RELEVANCE clause for result scores
    $titlecount = array();
    $articlecount = array();
    for($i = 0; $i < count($this->searchwords); $i++)
    {
      if(!empty($this->settings['search_page_titles']))
      $titlecount[] = "(CASE WHEN title LIKE '%" . $DB->escape_string($this->searchwords[$i]) . "%' THEN 1 ELSE 0 END)";
      $articlecount[] = "(CASE WHEN article LIKE '%" . $DB->escape_string($this->searchwords[$i]) . "%' THEN 1 ELSE 0 END)";
    }
    if(!empty($this->settings['search_page_titles']))
    $titlecount = implode(' + ', $titlecount);
    $articlecount = implode(' + ', $articlecount);

    // Build WHERE clause for Article Search
    $titleparts = array();
    $articleparts = array();
    for($i = 0; $i < count($this->searchwords); $i++)
    {
      $titleparts[] = "title LIKE '%" . $DB->escape_string($this->searchwords[$i]) . "%'";
      $articleparts[] = "article LIKE '%" . $DB->escape_string($this->searchwords[$i]) . "%'";
    }
    if(empty($articleparts))
      $articleparts = '1 = 0';
    else
      $articleparts = implode(' OR ', $articleparts);
    if(empty($titleparts))
      $titleparts = '1 = 0';
    else
      $titleparts = implode(' OR ', $titleparts);

    $where = ' AND (settings & 2)'
             ." AND ((IFNULL(access_view,'')='') OR (access_view like '%|".(int)$userinfo['usergroupid']."|%'))"
             .' AND ((datestart = 0) OR (datestart < ' . TIME_NOW . '))'
             .' AND ((dateend   = 0) OR (dateend   > ' . TIME_NOW . '))';

    $articles_table = PRGM_TABLE_PREFIX.'p'.$pid.'_news';

    $DB->result_type = MYSQL_ASSOC;
    $articlerows = $DB->query('SELECT count(*) rows FROM '. $articles_table.
                              ' WHERE (('.$titleparts.') OR ('.$articleparts.'))'.
                              $where);
    $arows = $DB->fetch_array($articlerows);

    $asearchraw =
      'SELECT *, ('.(empty($titlecount)?'':$titlecount.' + ').$articlecount.') AS relevance'.
      ' FROM '.$articles_table.
      ' WHERE (('.$titleparts.') OR ('.$articleparts.')) '.
      $where .
      ' ORDER BY relevance, datecreated DESC ' .
      $this->limit;
    $articlesearch = $DB->query($asearchraw);

    // Display Article Results
    if(!$DB->get_num_rows($articlesearch))
    {
     // If there weren't any results, say so
     echo $this->phrases['no_results'];
    }
    else
    {
      // If there were results, start displaying each one.
      while($articles = $DB->fetch_array($articlesearch,null,MYSQL_ASSOC))
      {
        //SD360: use "GetArticleLink()" to get link
        $articlelink = GetArticleLink($articles['categoryid'],$pid,$articles,array(),true,'');

        for($i = 0; $i < count($this->searchwords); $i++)
        {
          if (strlen($this->searchwords[$i]) > 3)
          {
            $articles['title'] = str_replace(strtoupper($this->searchwords[$i]),'<b' . $this->_highlight . '">' . strtoupper($this->searchwords[$i]) . '</b>', $articles['title']);
            $articles['title'] = str_replace(strtolower($this->searchwords[$i]),'<b' . $this->_highlight . '">' . strtolower($this->searchwords[$i]) . '</b>', $articles['title']);
            $articles['title'] = str_replace(ucfirst(strtolower($this->searchwords[$i])),'<b' . $this->_highlight . '">' . ucfirst(strtolower($this->searchwords[$i])) . '</b>', $articles['title']);
          }
        }

        echo '<a href="' . $articlelink . '"><u>' . $articles['title'] . '</u></a><br / >';

        $articles['article'] = trim(sd_substr(strip_alltags($articles['article']), 0, 150)).'...';

        // Highlight matched words in article, if the settings allow
        if(!empty($this->settings['highlight_body']))
        {
          for($i = 0; $i < count($this->searchwords); $i++)
          {
            if (strlen($this->searchwords[$i]) > 3)
            {
              $articles['article'] = str_replace(strtoupper($this->searchwords[$i]),'<b' . $this->_highlight . '">' . strtoupper($this->searchwords[$i]) . '</b>', $articles['article']);
              $articles['article'] = str_replace(strtolower($this->searchwords[$i]),'<b' . $this->_highlight . '">' . strtolower($this->searchwords[$i]) . '</b>', $articles['article']);
              $articles['article'] = str_replace(ucfirst(strtolower($this->searchwords[$i])),'<b' . $this->_highlight . '">' . ucfirst(strtolower($this->searchwords[$i])) . '</b>', $articles['article']);
            }
          }
        }

        echo DisplayDate($articles['datecreated']) . ' <strong>...</strong> ' . $articles['article'] . '<br />';

        if ($this->settings['show_full_url'] == '1')
        {
          echo '<a href="' . $articlelink . '">' . $articlelink . '</a><br / >';
        }

        echo '<br />';
      }

      $totalrows = $arows['rows'];
      $multiplepages = ($totalrows > $this->_items_per_page);

      if($multiplepages)
      {
        // pagination
        $p = new pagination;
        $p->parameterName('page');
        $p->items($totalrows);
        $p->limit($this->_items_per_page);
        $p->currentPage($this->_page);
        $p->adjacents(3);
        $p->target(RewriteLink('index.php?categoryid=' . $categoryid .
          '&action=search&results=' . $this->_results.
          '&q=' .urlencode($this->_searchterm)));
        $p->show();
      }
    }
  }

  // ##########################################################################

  function DisplayResults()
  {
    global $DB, $pluginid, $categoryid, $plugin_names, $plugin_name_to_id_arr,
           $mainsettings_search_results_page, $userinfo;

    //SD343: use special search results page
    $target = !empty($mainsettings_search_results_page) ? (int)$mainsettings_search_results_page : (int)$categoryid;

    //SD351: switched options in recent upgrade, need to reset "old" settings
    $articles_pluginidis = array();
    $search_plugins = sd_ConvertStrToArray($this->settings['search_plugins'], ',');
    $forum_id = $plugin_name_to_id_arr['Forum'];
    $this->settings['display_article_results'] = false;
    $this->settings['display_forum_results'] = false;
    if(!empty($search_plugins))
    {
      //SD350: allow cloned articles plugins
      foreach($search_plugins as $pid)
      {
        if($forum_id == $pid)
          $this->settings['display_forum_results'] = true;
        else
        if(isset($plugin_names['base-'.$pid]) &&
           ($plugin_names['base-'.$pid]=='Articles'))
        {
          $this->settings['display_article_results'] = true;
          $articles_pluginidis[] = $pid;
        }
      }
    }

    // Get the search term
    $this->_searchterm = urldecode(GetVar('q', '', 'string'));
    $this->_searchterm = trim(sd_substr($this->_searchterm,0,100));
    if(empty($this->_searchterm) || (strlen($this->_searchterm) < 3))
    {
      $this->SearchForm($this->phrases['no_searchterm']);
      return false;
    }

    if(!empty($this->settings['display_form_with_results'])) //v1.3.6
    {
      $this->SearchForm();
      echo '<br />';
    }

    // PAGINATION
    $this->_page = GetVar('page', 1, 'whole_number');
    $this->_page = Is_Valid_Number($this->_page, 1, 1, 999999);
    $this->_items_per_page = Is_Valid_Number($this->settings['results_per_page'],10,1,999);
    $this->limit = " LIMIT ".(($this->_page-1)*$this->_items_per_page)."," . $this->_items_per_page;

    // Break up the term into separate words
    $this->searchwords = preg_split("/\s\s+/", $this->_searchterm, -1, PREG_SPLIT_NO_EMPTY);

    $this->_highlight = !empty($this->settings['highlight_body']) ? ' class="highlight"' : '';

    // Prepare/display a basic search header
    $articleslink = ($this->settings['display_article_results'] ? '<a href="' . RewriteLink('index.php?categoryid=' . $categoryid . '&action=search&results=articles&q='.urlencode($this->_searchterm)).'">' . $this->phrases['article_results'] . '</a>' : '');
    $forumslink   = ($this->settings['display_forum_results']   ? '<a href="' . RewriteLink('index.php?categoryid=' . $categoryid . '&action=search&results=forums&q='.urlencode($this->_searchterm)).'">' . $this->phrases['forum_results'] . '</a>' : '');
    $separator    = ($this->settings['display_article_results'] && $this->settings['display_forum_results'] ?
                     '<div style="margin-left: 15px; margin-right: 15px; display: inline;">|</div>': '');

    if(strlen($articleslink) || strlen($forumslink))
    {
      echo '
      <div class="searchresults">';
      if(strlen($articleslink)) echo $articleslink;
      if(strlen($separator)) echo $separator;
      if(strlen($forumslink)) echo $forumslink;
      echo '
      </div><br />';
    }

    // Remind the user what they searched for
    echo $this->phrases['results_for'] . ' "' . $this->_searchterm . '"<br /><br />';

    // Check if a link was clicked to display results for either articles or forum
    $this->_results = GetVar('results', ($this->settings['display_article_results'] ? 'articles' : 'forums'), 'string');
    if(!in_array($this->_results, array('articles','forums')))
    {
      $this->_results = ($this->settings['display_article_results'] ? 'articles' : 'forums');
    }

    switch ($this->_results)
    {
      case 'articles':
        if(empty($articles_pluginidis)) break;
        foreach($articles_pluginidis as $pid)
        {
          $this->DisplayArticleResults($pid);
        }
        break;

      case 'forums':
        // Display forum results, if the settings included Forum
        if(!empty($this->settings['display_forum_results']))
        {
          if(!$forumpid = GetPluginID('Forum')) break;

          $IsSiteAdmin = !empty($userinfo['adminaccess']) && !empty($userinfo['loggedin']) && !empty($userinfo['userid']);
          $IsAdmin     = (!empty($userinfo['pluginadminids']) && @in_array($forumpid, $userinfo['pluginadminids']));
          // Does user have view permissions for plugin?
          if(!$IsSiteAdmin && $IsAdmin &&
             (empty($userinfo['pluginviewids']) || !in_array($forumpid, $userinfo['pluginviewids'])))
          {
            echo $this->phrases['no_results'];
            break;
          }

          // Does Forum plugin exist on any page?
          $DB->result_type = MYSQL_ASSOC;
          $fid = $DB->query_first("SELECT categoryid FROM {pagesort} WHERE pluginid = '%d'", $forumpid);
          if(empty($fid['categoryid']))
          {
            echo $this->phrases['no_results'];
            break;
          }
          $fid = (int)$fid['categoryid'];
          // Does user have view permission for category?
          if(!$IsSiteAdmin && (empty($userinfo['categoryviewids']) || !in_array($fid, $userinfo['categoryviewids'])))
          {
            echo $this->phrases['no_results'];
            break;
          }

          $GroupCheck = '';
          if(!$IsSiteAdmin)
          {
            // the usergroup id MUST be enclosed in pipes!
            $GroupCheck = " AND ((IFNULL(ff.access_view,'')='') OR (ff.access_view like '%|".$userinfo['usergroupid']."|%'))";
          }

          // Build RELEVANCE clause for result scores
          $postcount = array();
          for($i = 0; $i < count($this->searchwords); $i++)
          {
            $postcount[] = "(CASE WHEN fp.post LIKE '%" . $DB->escape_string($this->searchwords[$i]) . "%' THEN 1 ELSE 0 END)";
          }
          $postcount = implode(' + ', $postcount);

          // Build WHERE clause for post Search
          $postparts = array();
          foreach($this->searchwords as $i => $val)
          {
            $postparts[] = "fp.post LIKE '%" . $DB->escape_string(sd_substr($val,0,100)) . "%'";
          }
          if(empty($postparts))
          {
            echo $this->phrases['no_results'];
            break;
          }
          $postparts = ' AND ('.implode(' OR ', $postparts).')';

          // Ensure that user has actually view permissions for forum and topic
          $sql = ' FROM '.PRGM_TABLE_PREFIX.'p_forum_topics ft'.
                 ' INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_forums ff ON ff.forum_id = ft.forum_id'.
                 ' INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_posts fp  ON (fp.topic_id = ft.topic_id AND fp.moderated = 0)';
          $where = ' WHERE ff.forum_id > 0 '.
                    ($IsSiteAdmin ? '' : ' AND ff.online = 1 ').
                    $GroupCheck.
                    ($IsSiteAdmin || $IsAdmin ? '' : ' AND IFNULL(ft.moderated,0)=0 ');
          $totalrows = 0;
          if($prows = $DB->query_first('SELECT COUNT(DISTINCT ft.topic_id) rows '.$sql . $where . $postparts))
          {
            $totalrows = (int)$prows['rows'];
          }
          if(empty($totalrows))
          {
            echo $this->phrases['no_results'];
          }
          else
          {
            $f_config = false;
            //SD343:
            if(file_exists(ROOT_PATH.'plugins/forum/forum_config.php'))
            {
              @include_once(ROOT_PATH.'plugins/forum/forum_config.php');
              if(defined('SD_FORUM_CONFIG')) //set by forum header file in SD343
              {
                global $sd_forum_config;
                $f_config = $sd_forum_config;
              }
              else
              {
                $f_config = new SDForumConfig();
                $f_config->InitFrontpage();
              }
            }

            $postsearch = $DB->query(
              'SELECT DISTINCT fp.*, ('.$postcount.') AS relevance, ft.title topic_title '.
              $sql .
              $where .
              $postparts .
              ' ORDER BY relevance, date DESC ' .
              $this->limit);

            while($posts = $DB->fetch_array($postsearch,null,MYSQL_ASSOC))
            {
              if($f_config)
                $post_text = sd_unhtmlspecialchars(SDForumConfig::GetBBCodeExtract($posts['post']));
              else
                $post_text = trim(sd_substr(strip_alltags($posts['post']), 0, 150)).'...';
              if(!empty($this->settings['highlight_body']))
              {
                for($i = 0; $i < count($this->searchwords); $i++)
                {
                  if(strlen($this->searchwords[$i]) > 3)
                  {
                    $post_text = str_replace(strtoupper($this->searchwords[$i]),'<b' . $this->_highlight . '">' . strtoupper($this->searchwords[$i]) . '</b>', $post_text);
                    $post_text = str_replace(strtolower($this->searchwords[$i]),'<b' . $this->_highlight . '">' . strtolower($this->searchwords[$i]) . '</b>', $post_text);
                    $post_text = str_replace(ucfirst(strtolower($this->searchwords[$i])),'<b' . $this->_highlight . '">' . ucfirst(strtolower($this->searchwords[$i])) . '</b>', $post_text);
                  }
                }
              }
              $topic_title = trim(strip_alltags($posts['topic_title']));
              for($i = 0; $i < count($this->searchwords); $i++)
              {
                if (strlen($this->searchwords[$i]) > 3)
                {
                  $topic_title = str_replace(strtoupper($this->searchwords[$i]),'<b' . $this->_highlight . '>' . strtoupper($this->searchwords[$i]) . '</b>', $topic_title);
                  $topic_title = str_replace(strtolower($this->searchwords[$i]),'<b' . $this->_highlight . '>' . strtolower($this->searchwords[$i]) . '</b>', $topic_title);
                  $topic_title = str_replace(ucfirst(strtolower($this->searchwords[$i])),'<b' . $this->_highlight . '>' . ucfirst(strtolower($this->searchwords[$i])) . '</b>', $topic_title);
                }
              }

              if($f_config)
              {
                $res = $f_config->RewriteTopicLink($posts['topic_id'], $posts['topic_title'],1,true);
                $link = $res['link'].(strpos($res['link'],'?')?'&':'?').'post_id=' . $posts['post_id']. '#' . $posts['post_id'];
              }
              else
                $link = RewriteLink('index.php?categoryid=' . $fid . '&topic_id=' . $posts['topic_id'] . '&post_id=' . $posts['post_id'] . '#' . $posts['post_id']);
              echo '
              <a href="' . $link . '"><u>' . $topic_title . '</u></a><br />
              '.DisplayDate($posts['date']).' <strong>...</strong> '.$post_text.'<br />';

              if(!empty($this->settings['show_full_url']))
              {
                echo '<a href="' . $link . '">' . $link . '</a><br />';
              }

              echo '<br />';
            } //while

            if($totalrows > $this->_items_per_page)
            {
              echo '<br />';
              // pagination
              $p = new pagination;
              $p->parameterName('page');
              $p->items($totalrows);
              $p->limit($this->_items_per_page);
              $p->currentPage($this->_page);
              $p->adjacents(3);
              $p->target(RewriteLink('index.php?categoryid='.$categoryid.
                '&action=search&results='.$this->_results.
                '&q='.urlencode($this->_searchterm)));
              $p->show();
            }
          }
        }
        break;
    } //switch

  }

} //end of class

} // DO NOT REMOVE!
