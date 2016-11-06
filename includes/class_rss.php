<?php

if(!defined('IN_PRGM')) return false;

// ############################################################################
// RSS CLASS
// ############################################################################

class RSS
{
  private $feed = '';

  var $content_type      = '';
  var $page_id           = 0;
  var $forum_id          = 0;
  var $forum_ids         = array(); //SD343
  var $articles_limit    = 20;
  var $articles_pluginid = 2;
  var $forum_pluginid    = 0;
  var $forums_limit      = 20;

  var $rss_title       = '';
  var $rss_description = '';
  var $rss_link        = '';
  var $rss_self_link   = '';

  private $title_arr       = array();
  private $link_arr        = array();
  private $description_arr = array();
  private $pubdate_arr     = array();
  private $creator_arr     = array();

  function RSS()
  {
    global $mainsettings;

    $this->rss_title = $mainsettings['websitetitle'];
    $this->rss_description = $mainsettings['metadescription'];
    $this->rss_link = SITE_URL;
    $this->rss_self_link = SITE_URL . 'rss.php';
    $this->articles_pluginid = 2;
  }

  // ##########################################################################

  // convert all relative urls to absolute urls
  function relToAbs($text)
  {
    return preg_replace('#(href|src)="([^:"]*)(?:")#','$1="' . SITE_URL . '$2"', $text);
  }

  // ##########################################################################

  function GetArticles()
  {
    global $DB, $mainsettings_modrewrite, $mainsettings_url_extension,
           $sdlanguage, $userinfo, $usersystem;

    $error = false;
    if(empty($this->articles_pluginid))
    {
      $this->articles_pluginid = GetPluginID('Articles');
    }
    $this->articles_pluginid = empty($this->articles_pluginid)?false:(int)$this->articles_pluginid;
    $error = !$this->articles_pluginid;

    // If visitor is NOT an admin, apply check for view permissions of article plugin
    $IsAdmin = !$error && !empty($userinfo['loggedin']) &&
               (!empty($userinfo['adminaccess']) ||
                (!empty($userinfo['pluginadminids']) && @in_array($this->articles_pluginid, $userinfo['pluginadminids'])));
    if(!$IsAdmin)
    {
      if(empty($userinfo['pluginviewids']) || !@in_array($this->articles_pluginid, $userinfo['pluginviewids']))
      {
        $error = true;
      }
    }

    $news_table = 'p'.$this->articles_pluginid.'_news';
    if($error || !($table_exists = in_array($news_table,$DB->table_names_arr[$DB->database])))
    {
      //SD342 output formatted error
      $this->title_arr[]       = $sdlanguage['rss_not_available'];
      $this->link_arr[]        = SITE_URL;
      $this->description_arr[] = '';
      $this->pubdate_arr[]     = TIME_NOW;
      $this->creator_arr[]     = '';
      return false;
    }

    $extra_sql = '';

    if(!empty($this->page_id) && ($this->page_id > 0))
    {
      $this->page_id = (int)$this->page_id;
      //SD343: also list articles from secondary pages
      //old: $extra_sql .= 'AND categoryid = ' . (int)$this->page_id;
      $extra_sql .= ' AND ((categoryid = '.$this->page_id.
                    ') OR EXISTS(SELECT 1 FROM {p'.$this->articles_pluginid.'_articles_pages} ap'.
                    ' WHERE ap.categoryid = '.$this->page_id.
                    ' AND ap.articleid = '.PRGM_TABLE_PREFIX.$news_table.'.articleid))';

    }

    //SD342: check usergroup's permissions against article permissions
    if($DB->column_exists(PRGM_TABLE_PREFIX.$news_table,'access_view'))
    {
      $extra_sql .= " AND ((IFNULL(access_view,'')='') OR (access_view like '%|".$userinfo['usergroupid']."|%'))";
    }

    $this->articles_limit = Is_Valid_Number($this->articles_limit,20,1,100);
    $get_articles = $DB->query('SELECT * FROM '.PRGM_TABLE_PREFIX.$news_table.' WHERE (settings & 2)
                                AND ((datestart = 0) OR (datestart < ' . TIME_NOW . '))
                                AND ((dateend   = 0) OR (dateend   > ' . TIME_NOW . '))
                                ' . $extra_sql . '
                                ORDER BY IF (datestart > 0, datestart,
                                IF (dateupdated = 0, datecreated, dateupdated)) DESC
                                LIMIT 0,' . $this->articles_limit);

    //SD343: flip array so that isset() can be used instead of slow in_array()
    $allowed_pages = empty($userinfo['categoryviewids'])?array():@array_flip($userinfo['categoryviewids']);
    while($article_arr = $DB->fetch_array($get_articles,null,MYSQL_ASSOC))
    {
      $cid = empty($article_arr['categoryid'])?0:(int)$article_arr['categoryid'];
      if($IsAdmin || (!empty($allowed_pages) && @isset($allowed_pages[$cid])))
      {
        if(!empty($mainsettings_modrewrite) && strlen($article_arr['seo_title']))
        {
          $article_link = RewriteLink('index.php?categoryid=' . $cid);
          //SD342 bugfix: was hardcoded .html here instead of url_extension
          $article_link = str_replace($mainsettings_url_extension, '/' . $article_arr['seo_title'] . $mainsettings_url_extension, $article_link);
        }
        else
        {
          $article_link = RewriteLink('index.php?categoryid='.$cid.'&p'.$this->articles_pluginid.'_articleid='.$article_arr['articleid']);
        }

        if(strlen($article_arr['description']))
        {
          $description = $article_arr['description'];
        }
        else
        {
          $description = $article_arr['article'];
        }
        //SD342: remove invalid tinyMCE attr
        $description = str_replace('_mce_bogus="1"', '', $description);

        $this->title_arr[]       = strip_tags($article_arr['title']);
        $this->link_arr[]        = $article_link;
        $this->description_arr[] = $description;
        $this->pubdate_arr[]     = $article_arr['datecreated'];
        $this->creator_arr[]     = $article_arr['author'];
      }
    }

    return true;

  } //GetArticles

  // ##########################################################################

  function GetForum()
  {
    global $DB, $mainsettings, $userinfo;

    if(empty($this->forum_pluginid))
    {
      $this->forum_pluginid = GetPluginID('Forum');
    }
    if(!$this->forum_pluginid)
    {
      return false;
    }

    if(!$page_arr = $DB->query_first('SELECT categoryid FROM {pagesort} WHERE pluginid = %d LIMIT 1',$this->forum_pluginid))
    {
      return false;
    }
    $forum_page_id = $page_arr['categoryid'];
    defined('PAGE_ID') || define('PAGE_ID',$forum_page_id); //SD343

    @require('plugins/forum/forum_config.php');
    $sd_forum_config = new SDForumConfig();
    $sd_forum_config->attach_path = 'plugins/forum/attachments/'; // default
    if(!$sd_forum_config->InitFrontpage())
    {
      unset($sd_forum_config);
      return false;
    }

    // Has user category- and plugin-view access (if not site admin)?
    if(!$sd_forum_config->IsSiteAdmin)
    {
      if( empty($userinfo['pluginviewids']) || !@in_array($this->forum_pluginid, $userinfo['pluginviewids']) ||
          empty($userinfo['categoryviewids']) || !@in_array($forum_page_id, $userinfo['categoryviewids'])
         )
      {
        unset($sd_forum_config);
        return false;
      }
    }

    if(!empty($this->forum_id) && (strtolower($this->forum_id) != 'all'))
    {
      $this->forum_ids[] = (int)$this->forum_id;
    }
    if(!empty($this->forum_ids))
    {
      //SD343: check passed forum id's against existing forums and check permissions
      $allowed = array();
      $this->forum_ids = array_intersect($this->forum_ids, array_keys($sd_forum_config->forums_cache['forums']));
      foreach($this->forum_ids as $fid)
      {
        $f = $sd_forum_config->forums_cache['forums'][$fid];
        if($sd_forum_config->IsSiteAdmin || empty($f['access_view']) ||
           (strpos($f['access_view'],'|'.$userinfo['usergroupid'].'|') !== false))
        {
          $allowed[] = $fid;
        }
      }
      unset($f, $fid);
      if(empty($allowed)) return false;
    }

    $forum_arr = array();
    if(empty($allowed) || (!empty($this->forum_id) && (strtolower($this->forum_id) == 'all')))
    {
      $extraSQL = '';
    }
    else
    {
      $extraSQL = 'AND ff.forum_id IN ('.implode(',',$allowed).')';
      //SD343 SEO Forum Title linking
      if(!empty($allowed) && (count($allowed)==1))
      {
        $f = $sd_forum_config->forums_cache['forums'][$allowed[0]];
        $link = $sd_forum_config->RewriteForumLink($allowed[0]);
        $this->rss_title = strip_tags($this->rss_title . ' - ' . $f['title']);
        $this->rss_description = $f['description'];
        $this->rss_self_link = $this->rss_self_link . '?forum_id=' . $allowed[0];
        $this->rss_link = $link;
      }
    }

    $this->forums_limit = Is_Valid_Number($this->forums_limit,20,1,100);
    $sql = 'SELECT DISTINCT ft.forum_id, ft.topic_id, ft.title, ft.post_count,
              ft.last_post_id, ft.last_post_username, ft.last_post_date, ft.views,
              ft.title,
              ff.title forum_title, ff.access_view, ff.access_post,
              ft.last_post_id post_id
            FROM '.PRGM_TABLE_PREFIX."p_forum_topics ft
            INNER JOIN ".PRGM_TABLE_PREFIX."p_forum_forums ff ON ff.forum_id = ft.forum_id
            WHERE ff.is_category = 0 AND ff.online = 1 AND ft.open = 1 AND ft.moderated = 0
            AND ((ff.access_view = '') OR (ff.access_view is null) or (ff.access_view like '%|".$userinfo['usergroupid']."|%'))
            $extraSQL
            ORDER BY ft.last_post_date DESC, ft.title ASC LIMIT 0," .
            $this->forums_limit;

    $DB->ignore_error = true;
    if($get_topics = $DB->query($sql))
    {
      while($topic_arr = $DB->fetch_array($get_topics,null,MYSQL_ASSOC))
      {
        $res = $sd_forum_config->RewriteTopicLink($topic_arr['topic_id'],$topic_arr['title'],1,true);
        $topic_link = $res['link'].(strpos($res['link'],'?')?'&':'?').'post_id=' . $topic_arr['last_post_id']. '#' . $topic_arr['last_post_id'];
        $this->description_arr[] = '';
        $this->title_arr[]       = strip_tags($topic_arr['title']);
        $this->link_arr[]        = $topic_link;
        $this->pubdate_arr[]     = $topic_arr['last_post_date'];
        $this->creator_arr[]     = $topic_arr['last_post_username'];
      }
    }
    $DB->ignore_error = false;
    return true;

  } //GetForum

  // ##########################################################################

  function GetContent()
  {
    $content = false;
    if(($this->content_type=='all') || ($this->content_type == 'forum'))
    {
      $content = true;
      $this->GetForum();
    }
    if(($this->content_type=='all') || ($this->content_type == 'articles'))
    {
      $content = true;
      $this->GetArticles();
    }

    return $content;

  } //GetContent

  // ##########################################################################

  function FormatString($input='')
  {
    /*
    1. & - &amp;
    2. < - &lt;
    3. > - &gt;
    4. " - &quot;
    5. ' - &#39;
    */
    $input = str_replace(array('&amp;','&lt;','&gt;','&quot;','&#39;','&#039;'),
                         array('&','<','>','"',"'","'"),$input);
    return $input;
  }

  // ##########################################################################

  function GetFeed($isError=false)
  {
    global $mainsettings_enable_rss, $sdlanguage;

    $isError = !empty($isError) || !$this->GetContent();

    if(!empty($isError) || empty($mainsettings_enable_rss)) //SD342
    {
      //SD342 output formatted error
      $this->title_arr[]       = $sdlanguage['rss_not_available'];
      $this->link_arr[]        = SITE_URL;
      $this->description_arr[] = '';
      $this->pubdate_arr[]     = TIME_NOW;
      $this->creator_arr[]     = '';
    }

    $feed  = '<?xml version="1.0" encoding="' . SD_CHARSET . '" ?>' . "\n";
    $feed .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">' . "\n";
    $feed .= "<channel>\n";
    $feed .= "  <atom:link href=\"" . $this->rss_self_link . "\" rel=\"self\" type=\"application/rss+xml\" />\n";
    $feed .= "  <title><![CDATA[" . $this->FormatString($this->rss_title) . "]]></title>\n";
    $feed .= "  <description><![CDATA[" . $this->rss_description . "]]></description>\n";
    $feed .= "  <link>" .$this->rss_link . "</link>\n";

    for($i = 0; $i < count($this->title_arr); $i++)
    {
      $feed .= "  <item>\n";
      $feed .= "    <title><![CDATA[" . $this->FormatString($this->title_arr[$i]) . "]]></title>\n";
      $feed .= "    <link>"  . $this->link_arr[$i]  . "</link>\n";
      $feed .= "    <guid>"  . $this->link_arr[$i]  . "</guid>\n";

      if(strlen($this->description_arr[$i]))
      {
        $feed .= "    <description><![CDATA[" . ($this->relToAbs($this->description_arr[$i])) . "<hr /> ]]></description>\n";
      }

      $feed .= "    <pubDate>" . @date('r', $this->pubdate_arr[$i]) . "</pubDate>\n"; // "r" = RFC822 format!
      $feed .= "    <dc:creator>" . $this->creator_arr[$i] . "</dc:creator>\n";
      $feed .= "  </item>\n";
    }

    $feed .= "</channel>\n";
    $feed .= "</rss>";

    $this->feed = $feed;

    return true;

  } //GetFeed

  // ##########################################################################

  function DisplayFeed()
  {
    if(!empty($this->feed))
    {
      echo $this->feed;
    }
  }

} // end of class
