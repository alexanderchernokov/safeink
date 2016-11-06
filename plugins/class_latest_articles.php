<?php
if(!defined('IN_PRGM')) return;

if(!class_exists('LatestArticlesClass'))
{

class LatestArticlesClass
{
  private $aid      = 2; // default
  private $pluginid = 0;
  private $language = array();
  private $settings = array();
  private $p2_settings = array();
  private $p2_language = array();
  private $_smarty     = null; // smarty object

  public function __construct($plugin_folder)
  {
    if($this->pluginid = GetPluginIDbyFolder($plugin_folder))
    {
      $this->language = GetLanguage($this->pluginid);
      $this->settings = GetPluginSettings($this->pluginid);
      $this->Init($this->settings['article_plugin_selection']);
    }
  }

  public function Init($new_aid=2) //SD370
  {
    $this->aid = Is_Valid_Number($new_aid,2,2,99999);
    $this->p2_language = GetLanguage($this->aid);
    $this->p2_settings = GetPluginSettings($this->aid);
    $this->settings['article_plugin_selection'] = $this->aid;
  }

  // Article Bitfield Settings (static, so use "self::$articlebitfield"!)
  public static $articlebitfield = array( // max. 32 options!
    'useglobalsettings'       => 1,
    'displayonline'           => 2,
    'displaytitle'            => 4,
    'displayauthor'           => 8,
    'displaycreateddate'      => 16,
    'displayupdateddate'      => 32,
    'displayprintarticlelink' => 64,
    'displayemailarticlelink' => 128,
    'displaydescription'      => 256,
    'displaysmilies'          => 512,
    'displaycomments'         => 1024,
    'displayviews'            => 2048,
    'displaymainpage'         => 4096,
    'displaysticky'           => 8192,
    'displayratings'          => 16384,
    'displaysocialmedia'      => 32768,
    'displaytags'             => 65536,
    'displaypdfarticlelink'   => 131072,
    'displayaspopup'          => 262144,
    'ignoreexcerptmode'       => 524288 //SD342
    );

  private function InitTemplate()
  {
    global $categoryid, $mainsettings_modrewrite, $sdlanguage, $userinfo;

    // Instantiate smarty object
    $this->_smarty = SD_Smarty::getNew(true); //SD370: 1st param must be "true"
    $this->_smarty->assign('AdminAccess',   ($userinfo['adminaccess'] || (!empty($userinfo['pluginadminids']) &&
                                            @array_key_exists($this->pluginid,array_flip($userinfo['pluginadminids'])))));
    $this->_smarty->assign('categoryid',    $categoryid);
    $this->_smarty->assign('pluginid',      $this->pluginid);
    $this->_smarty->assign('language',      $this->language);
    $this->_smarty->assign('seo_enabled',   !empty($mainsettings_modrewrite));
    $this->_smarty->assign('article_pluginid', $this->aid);
    $this->_smarty->assign('article_language', $this->p2_language);
    $this->_smarty->assign('article_settings', $this->p2_settings);
  } //InitTemplate

  function DisplayContent($current_user=false, $useUserID=0)
  {
    //SD370: added parameters to limit articles for either current user
    // or the specified user id; functionality used by User Profile
    global $DB, $categoryid, $mainsettings, $mainsettings_modrewrite,
           $mainsettings_url_extension, $pages_md_arr, $plugin_names,
           $userinfo;

    if(empty($this->pluginid)) return false;

    $current_user = !empty($current_user);
    $useUserID = empty($useUserID)?0:Is_Valid_Number($useUserID,0,1,999999999);

    $showcategoryname   = !empty($this->settings['display_page_name']);     // display categoryname to the right of each article (default no)?
    $showdescription    = !$current_user && !$useUserID && !empty($this->settings['display_description']);   // display description (default no)?
    $showauthor         = !$current_user && !$useUserID && !empty($this->settings['display_author']);        // display authorname (default no)?
    $showdate           = !empty($this->settings['display_creation_date']); // display creation date (default no)?
    $showupdatedate     = !empty($this->settings['display_updated_date']);  // display update date (default no)?
    $showreadmore       = !empty($this->settings['display_read_more']);     // display read more link (default no)
    $displayavatar      = !empty($this->settings['display_avatar']);        // display user avatar image?
    $categorytolink     = !empty($this->settings['page_link']);             // convert category name to link (default yes)?
    $titletolink        = !empty($this->settings['title_link']);            // convert articles title to link (default yes)?
    $boldtitle          = !empty($this->settings['title_in_bold']);         // convert articles title to link (default yes)?
    $boldtitlestart     = $boldtitle ? '<strong>'  : '';
    $boldtitleend       = $boldtitle ? '</strong>' : '';
    $container_class    = !empty($this->settings['html_container_class']) ? ' class="'.$this->settings['html_container_class'].'"' : '';
    $entry_class        = !empty($this->settings['html_entry_class'])     ? ' class="'.$this->settings['html_entry_class'].'"' : '';

    if($current_user || $useUserID)
      $items_per_page   = 999;
    else
      $items_per_page   = (int)$this->settings['display_limit'];   // how many articles to display (default 10)
    $sorting            = $this->settings['sorting'];              // sort by author, title, newest first, oldest first etc (see the articles plugin)
    $pagination         = !$current_user && !$useUserID && !empty($this->settings['display_pagination']); // display pagination for multiple pages?
    $a_width            = empty($this->settings['avatar_width'])  ? $mainsettings['default_avatar_width']  : intval($this->settings['avatar_width']);
    $a_height           = empty($this->settings['avatar_height']) ? $mainsettings['default_avatar_height'] : intval($this->settings['avatar_height']);

    // *** SMARTY TEMPLATE ***
    $this->InitTemplate(); //SD343
    $this->_smarty->assign('container_class', $container_class);
    $this->_smarty->assign('dateformat', $mainsettings['dateformat']); //SD360
    $this->_smarty->assign('entry_class', $entry_class);
    $this->_smarty->assign('is_ajax_request', Is_Ajax_Request());
    $this->_smarty->assign('pagination', '');
    $this->_smarty->assign('plugin_name', $plugin_names[$this->pluginid]); //SD360
    $this->_smarty->assign('show_author', $showauthor);
    $this->_smarty->assign('show_avatar', $displayavatar);
    $this->_smarty->assign('show_category_name', $showcategoryname);
    $this->_smarty->assign('show_category_link', $categorytolink);
    $this->_smarty->assign('show_date', $showdate);
    $this->_smarty->assign('show_description', $showdescription);
    $this->_smarty->assign('show_read_more', $showreadmore);
    $this->_smarty->assign('show_title_bold', $boldtitle);
    $this->_smarty->assign('show_title_link', $titletolink);

    if(empty($userinfo['categoryviewids'])) $userinfo['categoryviewids'] = array(-1);
    $extraquery = '';
    $matches = false;
    $p_page = array();
    $valid_ids = array();

    if(!empty($userinfo['categoryviewids'])) // only if user has any page view permissions
    {
      if($current_user || $useUserID)
      {
        $matches = 0;
      }
      else
      if(isset($this->settings[$categoryid]))
      {
        $matches = $this->settings[$categoryid];
      }
      else
      if(isset($this->settings['default_page_match']))
      {
        $matches = $this->settings['default_page_match'];
      }

      // Process matches
      if($matches && ($matches !== false))
      {
        if(strpos($matches,'-2') !== false) // selected pages
        {
          $valid_ids = explode(',',$matches);
        }
        else
        if(strpos($matches,'-1') !== false) // current page
        {
          $valid_ids = array((int)$categoryid);
        }

        if(is_array($valid_ids) && count($valid_ids))
        {
          // Check all matches for being a valid value
          $tmp = array();
          foreach($valid_ids as $cat_id)
          {
            if($cat_id = Is_Valid_Number($cat_id, 0, 1, 9999999))
            {
              if(in_array($cat_id,$userinfo['categoryviewids']))
                $tmp[] = $cat_id;
            }
          }
          if(count($tmp) > 1)
            $extraquery = '(news.categoryid IN ('.implode(',',$tmp).'))';
          elseif(count($tmp) == 1)
            $extraquery = '(news.categoryid = '.(int)$tmp[0].')';
          if($extraquery!=='') $valid_ids = implode(',',$tmp); // Reassign
          unset($tmp);
        }
      }

      if(strlen($extraquery))
      {
        if(($matches !== false) && @in_array('p'.$this->aid.'_articles_pages', $DB->table_names_arr[$DB->database]))
        {
          // Check for secondary article pages as well
          $extraquery = ' AND ('.$extraquery.
                          ' OR (EXISTS(SELECT 1 FROM {p'.$this->aid.'_articles_pages} ap '.
                          ' WHERE ap.articleid = news.articleid AND ap.categoryid IN ('.$valid_ids.'))))';
        }
        else
        {
          $extraquery = ' AND '.$extraquery;
        }
      }

      // Limit to pages that actually include the plugin
      if($getplugins = $DB->query('SELECT DISTINCT categoryid FROM '.PRGM_TABLE_PREFIX.
                                  "pagesort WHERE pluginid = '%d'".
                                  ' ORDER BY categoryid', $this->aid))
      {
        while($p = $DB->fetch_array($getplugins,null,MYSQL_ASSOC))
        {
          #if(in_array($p['categoryid'],$userinfo['categoryviewids'])) //???
          $p_page[] = (int)$p['categoryid'];
        }
        $DB->result_type = MYSQL_BOTH;
      }

      if(count($p_page))
        $extraquery .= ' AND (news.categoryid IN ('.implode(',',$p_page).'))';
      else
        $extraquery = ' AND (news.categoryid = -1)'; // no page access at all

      // if we have elected to hide articles by default, search for those which are online
      if(!$current_user && !$useUserID)
      {
        if(empty($this->p2_settings['display_on_main_page']))
        {
          $extraquery .= ' AND (news.settings & 4096)';
        }
        else // else exclude all global articles
        {
          $extraquery .= ' AND ((news.settings & 1) OR (news.settings & 4096))';
        }
      }
    }
    else
    if(!$current_user && !$useUserID)
    {
      $extraquery = ' AND (news.categoryid = -1)'; // no page access at all
    }

    //SD370: allow for current user or specified user
    if($current_user)
    {
      $extraquery .= ' AND (news.org_author_id = '.$userinfo['userid'].')';
    }
    else
    if($useUserID)
    {
      $extraquery .= ' AND ((news.org_author_id = '.$useUserID.') OR (news.last_modifier_id = '.$useUserID.'))';
    }

    //342: check for usergroup permission's
    if($DB->column_exists(PRGM_TABLE_PREFIX.'p'.$this->aid.'_news','access_view'))
    {
      $extraquery .= " AND ((IFNULL(news.access_view,'')='') OR (news.access_view like '%|".(int)$userinfo['usergroupid']."|%'))";
    }

    // sorting articles
    $sorting = strtolower($sorting);
    switch($sorting)
    {
      case 'alphaaz':
        $sort = 'news.title ASC';
      break;

      case 'alphaza':
        $sort = 'news.title DESC';
      break;

      case 'authornameaz':
        $sort = 'news.author ASC';
      break;

      case 'authornameza':
        $sort = 'news.author DESC';
      break;

      case 'oldest':
        $sort = 'IF(IFNULL(news.datecreated,IFNULL(news.datestart,0))=0,news.dateupdated,IFNULL(news.datecreated,IFNULL(news.datestart,0))) ASC';
      break;

      default:
        $sorting = 'newest';
        $sort = 'IF(IFNULL(news.datecreated,IFNULL(news.datestart,0))=0,news.dateupdated,IFNULL(news.datecreated,IFNULL(news.datestart,0))) DESC';
    }

    $entries = array();

    // items_per_page created above
    $page = Is_Valid_Number(GetVar('latestarticles'.$this->pluginid, 1, 'whole_number'), 1, 1);
    $items_per_page = Is_Valid_Number($items_per_page, 5, 1, 1000);
    $limit = ' LIMIT ' . (($page-1)*$items_per_page) . ',' . $items_per_page;

    $Where_Cond = ' WHERE (news.settings & 2)';
    if(!$current_user && !$useUserID)
      $Where_Cond .= ' AND ((news.datestart = 0) OR (news.datestart < ' . TIME_NOW . '))'.
                     ' AND ((news.dateend   = 0) OR (news.dateend  >= ' . TIME_NOW . ')) ';
    $Where_Cond .= $extraquery;

    if($total_rows = $DB->query_first('SELECT COUNT(*) FROM {p'.$this->aid.'_news} news'.$Where_Cond))
      $total_rows = (int)$total_rows[0];
    else
      return;

    $sql = 'SELECT DISTINCT * FROM {p'.$this->aid."_news} news $Where_Cond ORDER BY $sort $limit";
    if(!$getarticles = $DB->query($sql)) return;

    // Config array as parameter for sd_PrintAvatar (in globalfunctions.php)
    if($displayavatar)
    {
      $avatar_conf = array(
        'output_ok' => $displayavatar,
        'userid'    => -1,
        'username'  => '',
        'Avatar Column'       => false,
        'Avatar Image Height' => $a_height,
        'Avatar Image Width'  => $a_width
        );
    }
    if(count($p_page)) $p_page = array_flip($p_page); //SD370

    // Main loop to pass article(s) to template
    while($a = $DB->fetch_array($getarticles,null,MYSQL_ASSOC))
    {
      // does category exist?
      if(isset($pages_md_arr[$a['categoryid']]) && ($category = $pages_md_arr[$a['categoryid']]))
      {
        if(isset($p_page[(int)$a['categoryid']])) //SD370: "isset"
        {
          //SD343: use user cache for avatar and user (link/name)
          $author = SDUserCache::CacheUser($a['org_author_id'], $a['org_author_name'], false, true, true);

          if($mainsettings_modrewrite && strlen($a['seo_title']))
          {
            $articlelink = RewriteLink('index.php?categoryid=' . $a['categoryid']);
            $articlelink = str_replace($mainsettings_url_extension, '/' . $a['seo_title'] . $mainsettings_url_extension, $articlelink);
            $articlelink .= ($this->aid > 2 ? '?pid='.$this->aid : '');
          }
          else
          {
            $articlelink = RewriteLink('index.php?categoryid=' . $a['categoryid'].'&pid='.$this->aid.'&p'.$this->aid.'_articleid='.$a['articleid']);
          }

          $published_date = (!empty($a['datestart']) ? $a['datestart'] :
                             (!empty($a['datecreated']) ? $a['datecreated'] :
                              $a['dateupdated']));
          $entries[] = array(
            'article_id'           => (int)$a['articleid'],
            'article_description'  => $a['description'],
            'article_featured'     => !empty($a['featured']),
            'article_featuredpath' => $a['featuredpath'],
            'article_title'        => $a['title'],
            'article_thumbnail'    => $a['thumbnail'],
            'article_url'          => $articlelink,
            'author_id'            => $a['org_author_id'],
            'author_link'          => $author['profile_link'],
            'author_name'          => $a['org_author_name'],
            'avatar'               => ($displayavatar && !empty($author['avatar']) ? $author['avatar'] : ''),
            'bold_end'             => $boldtitleend,
            'bold_start'           => $boldtitlestart,
            'category_link'        => RewriteLink('index.php?categoryid='.$a['categoryid']),
            'category_name'        => $category['name'],
            'categoryid'           => $a['categoryid'],
            'date_created'         => DisplayDate($a['datecreated'],'',true),
            'date_created_stamp'   => (int)$a['datecreated'], //SD343
            'date_start'           => DisplayDate($a['datestart'],'',true),
            'date_start_stamp'     => (int)$a['datestart'], //SD343
            'date_updated'         => DisplayDate($a['dateupdated'],'',true),
            'date_updated_stamp'   => (int)$a['dateupdated'], //SD343
            'date_only_published'  => DisplayDate($published_date,'',false), //SD351
            'date_dmy_published'   => DisplayDate($published_date,'d.m.Y',false), //SD351
            'date_iso_published'   => DisplayDate($published_date,'Y-m-d',false), //SD351
            'date_published'       => DisplayDate($published_date,'',true),
            'date_published_stamp' => $published_date,
            'show_description'     => $showdescription,
            'show_updatedate'      => ($showupdatedate && !empty($a['dateupdated']))
          );
        }
        else
        {
          $total_rows--;
        }
      }
      else
      {
        $total_rows--;
      }
    } //while
    $DB->result_type = MYSQL_BOTH;

    // Finally assign the articles array to the template
    $this->_smarty->assign('entries', $entries);

    if($pagination && ($total_rows > $items_per_page))
    {
      //SD370: new option "pagination_links"
      $adjacents = empty($this->settings['pagination_links'])?0:intval($this->settings['pagination_links']);
      $adjacents = Is_Valid_Number($adjacents, 1, 1, 99);
      // Generate and assign pagination HTML
      $p = new pagination;
      $p->adjacents($adjacents);
      $p->currentPage($page);
      $p->nextLabel('');
      $p->prevLabel('');
      $p->items($total_rows);
      $p->limit($items_per_page);
      $p->parameterName('latestarticles'.$this->pluginid);
      $p->target(RewriteLink('index.php?categoryid=' . $categoryid));
      $this->_smarty->assign('pagination', $p->getOutput());
    }

    // BIND AND DISPLAY TEMPLATE NOW
    // Check if custom version exists, otherwise use default template:
    $err_msg = '<br /><b>'.$plugin_names[$this->pluginid].' ('.$this->pluginid.') template file NOT FOUND!</b><br />';
    if(defined('SD_SMARTY_VERSION') && (SD_SMARTY_VERSION > 2)) //SD344
    {
      $error = !SD_Smarty::display($this->pluginid, 'latest_articles.tpl', $this->_smarty);
      if($error && !empty($userinfo['adminaccess']))
      {
        $err = SD_Smarty::getLastError();
        echo $err.$err_msg;
      }
    }
    else
    {
      if(is_file(SD_INCLUDE_PATH.'tmpl/latest_articles.tpl')) //SD343
      {
        $this->_smarty->display('latest_articles.tpl');
      }
      else
      if(is_file(SD_INCLUDE_PATH.'tmpl/defaults/latest_articles.tpl'))
      {
        $this->_smarty->setTemplateDir(SD_INCLUDE_PATH.'tmpl/defaults/');
        $this->_smarty->display('latest_articles.tpl');
      }
      else
      {
        if($userinfo['adminaccess']) echo $err_msg;
      }
    }
  }

} // end of class

} // DO NOT REMOVE!
