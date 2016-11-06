lass<?php
if(!defined('IN_PRGM')) return;

// ############################################################################
// PROFILE PAGE HANDLER CLASS
// ############################################################################
if(!class_exists('SDUserProfilePageHandler'))
{
class SDUserProfilePageHandler
{
  private static $_instance   = null;
  private static $_action     = '';
  private static $_pageid     = '';
  private static $_jscode     = '';
  private static $_pluginid   = 11;
  private static $_userid     = 0;
  private static $_msg_obj    = 0;
  private static $_data       = null;

  public  static $errortitle  = '';
  public  static $errors      = '';

  protected function __construct()
  {
    //empty!
  }

  public static final function getInstance()
  {
    if (!self::$_instance)
    {
      self::$_instance = new self();
    }

    return self::$_instance;
  }

  public static function hasInstance()
  {
    return (self::$_instance ? true : false);
  }

  private static function InitTmpl($page_url, $current_url)
  {
    $tmp_smarty = SDProfileConfig::CreateSmartyDefault(true);
    $tmp_smarty->assign('errortitle',     self::$errortitle);
    $tmp_smarty->assign('errors',         self::$errors);
    $tmp_smarty->assign('page_title',     SDProfileConfig::$phrases[self::$_pageid.'_title']);
    $tmp_smarty->assign('page_url',       $page_url);
    $tmp_smarty->assign('current_url',    $current_url);
    $tmp_smarty->assign('profile',        self::$_userid);
    $tmp_smarty->assign('securitytoken',  SD_FORM_TOKEN);
    $tmp_smarty->assign('seo',            SDProfileConfig::GetProfilePages());
    $tmp_smarty->assign('token_element',  PrintSecureToken(SDProfileConfig::$form_prefix . 'token'));
    $tmp_smarty->assign('page',           1);
    $tmp_smarty->assign('prev_page',      1);
    $tmp_smarty->assign('page_size',      999);
    return $tmp_smarty;
  }

  public static function page_mycontent()
  {
    global $DB, $bbcode, $categoryid, $mainsettings, $sdlanguage,
           $userinfo, $usersystem;

    self::$_action = 'error';

    if(!self::$_userid || empty($userinfo['loggedin']))
    {
      return false;
    }
    $page_url = RewriteLink('index.php?categoryid='.$categoryid);
    $page_url .= (strpos($page_url,'?')===false?'?':'&amp;').'profile='.self::$_userid;
    $url_params = 'do='.SDProfileConfig::GetPageFragment(self::$_pageid);
    $current_url = $page_url.'#'.$url_params;

    $data = array();

    $data['myarticles_url'] = $page_url.'#do='.SDProfileConfig::GetPageFragment(self::$_pageid,'page_myarticles');
    $data['myblog_url']     = $page_url.'#do='.SDProfileConfig::GetPageFragment(self::$_pageid,'page_myblog');
    $data['myfiles_url']    = $page_url.'#do='.SDProfileConfig::GetPageFragment(self::$_pageid,'page_myfiles');
    $data['myforum_url']    = $page_url.'#do='.SDProfileConfig::GetPageFragment(self::$_pageid,'page_myforum');
    $data['mymedia_url']    = $page_url.'#do='.SDProfileConfig::GetPageFragment(self::$_pageid,'page_mymedia');

    $data['myarticles_count']       = 0;
    $data['myfiles_count']          = 0;
    $data['myforum_post_count']     = 0;
    $data['myforum_thread_count']   = 0;
    $data['mymedia_images_count']   = 0;
    $data['mymedia_sections_count'] = 0;

    $fid = GetPluginID('Forum');

    // Get a list of plugins for Articles, Forum and Media Gallery
    // (incl. clones!), that are currently assigned to any page:
    $pp = array();
    if($tmp = $DB->query('SELECT DISTINCT p.pluginid, p.base_plugin, COUNT(*) pc'.
                         ' FROM {pagesort} ps'.
                         ' INNER JOIN {plugins} p ON p.pluginid = ps.pluginid'.
                         " WHERE (p.pluginid IN ('2', '$fid'))".
                         " OR (p.base_plugin IN ('Articles','Download Manager','Media Gallery'))".
                         ' GROUP BY p.pluginid, p.base_plugin'.
                         ' ORDER BY p.base_plugin, p.pluginid'))
    {
      while($entry = $DB->fetch_array($tmp, NULL, MYSQL_ASSOC))
      {
        if(!empty($entry['pc']))
        {
          $pp[(int)$entry['pluginid']] = $entry['base_plugin'];
        }
      }
    }

    // Count each plugin's item counts:
    foreach($pp AS $pid => $base)
    {
      // Check plugin view permissions first
      if(empty($userinfo['adminaccess']) &&
         (empty($userinfo['pluginviewids'])  || !in_array($pid,$userinfo['pluginviewids'])) &&
         (empty($userinfo['pluginadminids']) || !in_array($pid,$userinfo['pluginadminids'])) )
      {
        continue;
      }

      // *** Articles data ***
      if(($pid==2) or ($base=='Articles'))
      {
        $sql = ' FROM '.PRGM_TABLE_PREFIX."p".$pid."_news p2
          INNER JOIN ".PRGM_TABLE_PREFIX."categories c ON c.categoryid = p2.categoryid
          WHERE p2.org_author_id = %d AND (p2.settings & 2)";
        $rowcount = $DB->query_first('SELECT COUNT(*) articlecount'.$sql, self::$_userid);
        $rowcount = empty($rowcount['articlecount']) ? 0 : $rowcount['articlecount'];
        $data['myarticles_count'] += (int)$rowcount;
        continue;
      }

      // *** Forum data ***
      if($fid && ($pid==$fid))
      {
        $sql = ' FROM '.PRGM_TABLE_PREFIX.'p_forum_posts fp
          INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_topics ft ON ft.topic_id = fp.topic_id
          INNER JOIN '.PRGM_TABLE_PREFIX."p_forum_forums ff ON ff.forum_id = ft.forum_id
          WHERE fp.user_id = %d
          AND ((IFNULL(ff.access_view,'')='') OR (ff.access_view like '%|".$userinfo['usergroupid']."|%'))
          AND ft.moderated = 0 AND fp.moderated = 0 AND ff.online = 1 AND ff.is_category = 0 ";

        $rowcount = $DB->query_first('SELECT COUNT(*) pc '.$sql, self::$_userid);
        $rowcount = empty($rowcount['pc']) ? 0 : $rowcount['pc'];
        $data['myforum_post_count']   = (int)$rowcount;
        $data['myforum_thread_count'] = 0;
        if(isset($userinfo['profile']) && !empty($userinfo['profile']['user_thread_count']))
        {
          $data['myforum_thread_count'] = (int)$userinfo['profile']['user_thread_count'];
        }
        $fid = false;
        continue;
      }

      // *** Download Manager data ***
      if($base=='Download Manager')
      {
        $rowcount = $DB->query_first('SELECT COUNT(*) pc FROM {p'.$pid.'_files}'.
                                     " WHERE author = '%s'".
                                     ' AND activated = 1',
                                     $userinfo['username']);
        $rowcount = empty($rowcount['pc']) ? 0 : $rowcount['pc'];
        $data['myfiles_count'] += (int)$rowcount;
        continue;
      }

      // *** Media Gallery data ***
      if($base=='Media Gallery')
      {
        $rowcount = $DB->query_first('SELECT COUNT(*) pc FROM {p'.$pid.'_images}'.
                                     ' WHERE owner_id = ' . $userinfo['userid'].
                                     ' AND activated = 1');
        $rowcount = empty($rowcount['pc']) ? 0 : $rowcount['pc'];
        $data['mymedia_images_count'] += (int)$rowcount;

        $rowcount = $DB->query_first('SELECT COUNT(*) pc FROM {p'.$pid.'_sections}'.
                                     ' WHERE owner_id = ' . $userinfo['userid'].
                                     ' AND activated = 1');
        $rowcount = empty($rowcount['pc']) ? 0 : $rowcount['pc'];
        $data['mymedia_sections_count'] += (int)$rowcount;
        continue;
      }
    } //foreach

    // Pass data to template
    $tmp_smarty = self::InitTmpl($page_url,$current_url);
    $tmp_smarty->assign('data', $data);
    unset($data);

    $tmpl_name = 'ucp_'.self::$_pageid.'.tpl';
    if($tmpl_done = SD_Smarty::display(11, $tmpl_name, $tmp_smarty))
    {
      self::$_action = 'submit';
      self::$_pageid = '';
    }
    else
    {
      if($userinfo['adminaccess']) echo '<p><strong>Template error OR template not found ('.$tmpl_name.')!<br />
      Please check Skins|Templates or "/includes/tmpl/defaults" folder!</strong></p>';
    }

    return (self::$_action != 'error');

  } //page_mycontent


  public static function page_myarticles()
  {
    global $DB, $bbcode, $mainsettings, $sdlanguage,
           $userinfo, $usersystem, $categoryid, $plugin_name_to_id_arr;

    self::$_action = 'error';

    if(!self::$_userid || empty($userinfo['loggedin']))
    {
      return false;
    }

    $page_url = RewriteLink('index.php?categoryid='.$categoryid);
    $page_url .= (strpos($page_url,'?')===false?'?':'&amp;').'profile='.self::$_userid;
    $url_params = 'do='.SDProfileConfig::GetPageFragment('page_mycontent',self::$_pageid);
    $content_url = $page_url.'#'.'do='.SDProfileConfig::GetPageFragment('page_mycontent');
    $current_url = $page_url.'#'.$url_params;
    $page_content = '';

    if(!class_exists('LatestArticlesClass'))
    {
      @include_once(ROOT_PATH . 'plugins/class_latest_articles.php');
    }
    if(class_exists('LatestArticlesClass'))
    {
      // Get a list of all Articles plugins (incl. clones!),
      // that are currently assigned to any page and run
      // the Latest Articles class with its data for output:
      if($tmp = $DB->query('SELECT DISTINCT p.pluginid, p.base_plugin, COUNT(*) pc'.
                           ' FROM {pagesort} ps'.
                           ' INNER JOIN {plugins} p ON p.pluginid = ps.pluginid'.
                           " WHERE (p.pluginid = '2') OR (p.base_plugin = 'Articles')".
                           ' GROUP BY p.pluginid, p.base_plugin'.
                           ' ORDER BY p.base_plugin, p.pluginid'))
      {
        $LatestArticles = new LatestArticlesClass('latest_articles');

        // Create output for each plugin (if multiple):
        while($entry = $DB->fetch_array($tmp, NULL, MYSQL_ASSOC))
        {
          $pid = (int)$entry['pluginid'];

          // Check plugin view permissions first
          if(empty($entry['pc']) ||
             (empty($userinfo['adminaccess']) &&
              (empty($userinfo['pluginviewids']) || !in_array($pid,$userinfo['pluginviewids'])) &&
              (empty($userinfo['pluginadminids']) || !in_array($pid,$userinfo['pluginadminids']))))
          {
            continue;
          }

          $LatestArticles->Init($pid);
          ob_start();
          $LatestArticles->DisplayContent(true);
          $output = ob_get_clean();
          if(strlen(trim($output)))
          {
            $page_content .=
              (isset($sdlanguage['plugin_name_'.$pid])?'<h1>'.$sdlanguage['plugin_name_'.$pid].'</h1>':'').
              $output.'<br />';
          }
        } //while

        unset($LatestArticles);
      }
    }

    $tmp_smarty = self::InitTmpl($page_url,$current_url);
    $tmp_smarty->assign('content_url', $content_url);
    $tmp_smarty->assign('my_articles_list', $page_content);

    $tmpl_name = 'ucp_'.self::$_pageid.'.tpl';
    if($tmpl_done = SD_Smarty::display(11, $tmpl_name, $tmp_smarty))
    {
      self::$_action = 'submit';
      self::$_pageid = '';
    }
    else
    {
      if($userinfo['adminaccess']) echo '<p><strong>Template error OR template not found ('.$tmpl_name.')!<br />
      Please check Skins|Templates or "/includes/tmpl/defaults" folder!</strong></p>';
    }

    return (self::$_action != 'error');

  } //page_myarticles


  public static function page_myfiles()
  {
    global $DB, $bbcode, $mainsettings, $sdlanguage,
           $userinfo, $usersystem, $categoryid;

    self::$_action = 'error';

    if(!self::$_userid || empty($userinfo['loggedin']))
    {
      return false;
    }

    $page_url = RewriteLink('index.php?categoryid='.$categoryid);
    $page_url .= (strpos($page_url,'?')===false?'?':'&amp;').'profile='.self::$_userid;
    $url_params = 'do='.SDProfileConfig::GetPageFragment('page_mycontent',self::$_pageid);
    $current_url = $page_url.'#'.$url_params;
    $content_url = $page_url.'#'.'do='.SDProfileConfig::GetPageFragment('page_mycontent');
    $page_content = '';

    if(!class_exists('LatestFilesClass'))
    {
      @include_once(ROOT_PATH . 'plugins/latest_files/class_latest_files.php');
    }
    if(class_exists('LatestFilesClass'))
    {
      // Get a list of all Download Manager plugins (incl. clones!),
      // that are currently assigned to any page and run
      // the Latest Articles class with its data for output:
      if($getfiles = $DB->query('SELECT DISTINCT p.pluginid, p.base_plugin, COUNT(*) pc'.
                           ' FROM {pagesort} ps'.
                           ' INNER JOIN {plugins} p ON p.pluginid = ps.pluginid'.
                           " WHERE (p.base_plugin = 'Download Manager')".
                           ' GROUP BY p.pluginid, p.base_plugin'.
                           ' ORDER BY p.base_plugin, p.pluginid'))
      {
        $LatestFiles = new LatestFilesClass('latest_files');

        // Create output for each plugin (if multiple):
        while($entry = $DB->fetch_array($getfiles, NULL, MYSQL_ASSOC))
        {
          $pid = (int)$entry['pluginid'];

          // Check plugin view permissions first
          if(empty($entry['pc']) ||
             (empty($userinfo['adminaccess']) &&
              (empty($userinfo['pluginviewids']) || !in_array($pid,$userinfo['pluginviewids'])) &&
              (empty($userinfo['pluginadminids']) || !in_array($pid,$userinfo['pluginadminids']))))
          {
            continue;
          }

          $LatestFiles->SetSourcePluginID($pid);
          $LatestFiles->settings['exclude_sections'] = '';
          $LatestFiles->settings['number_of_files_to_display'] = 999;
          ob_start();
          $LatestFiles->DisplayLatestFiles(true);
          $output = ob_get_clean();
          if(strlen(trim($output)))
          {
            $page_content .=
              (isset($sdlanguage['plugin_name_'.$pid])?'<h1>'.$sdlanguage['plugin_name_'.$pid].'</h1>':'').
              $output.'<br />';
          }
        } //while

        unset($LatestFiles);
      }
    }

    $tmp_smarty = self::InitTmpl($page_url,$current_url);
    $tmp_smarty->assign('content_url',    $content_url);
    $tmp_smarty->assign('my_files_list',  $page_content);

    $tmpl_name = 'ucp_'.self::$_pageid.'.tpl';
    if($tmpl_done = SD_Smarty::display(11, $tmpl_name, $tmp_smarty))
    {
      self::$_action = 'submit';
      self::$_pageid = '';
    }
    else
    {
      if($userinfo['adminaccess']) echo '<p><strong>Template error OR template not found ('.$tmpl_name.')!<br />
      Please check Skins|Templates or "/includes/tmpl/defaults" folder!</strong></p>';
    }

    return (self::$_action != 'error');

  } //page_myfiles


  public static function page_mymedia()
  {
    global $DB, $bbcode, $mainsettings, $sdlanguage,
           $userinfo, $usersystem, $categoryid;

    self::$_action = 'error';

    if(!self::$_userid || empty($userinfo['loggedin']))
    {
      return false;
    }

    $page_url = RewriteLink('index.php?categoryid='.$categoryid);
    $page_url .= (strpos($page_url,'?')===false?'?':'&amp;').'profile='.self::$_userid;
    $url_params = 'do='.SDProfileConfig::GetPageFragment('page_mycontent',self::$_pageid);
    $current_url = $page_url.'#'.$url_params;
    $content_url = $page_url.'#'.'do='.SDProfileConfig::GetPageFragment('page_mycontent');
    $page_content = '';

    if(!class_exists('LatestImagesClass'))
    {
      @include_once(ROOT_PATH . 'plugins/latest_images/class_latest_images.php');
    }
    if(class_exists('LatestImagesClass'))
    {
      // Get a list of all Download Manager plugins (incl. clones!),
      // that are currently assigned to any page and run
      // the Latest Articles class with its data for output:
      $sql = 'SELECT DISTINCT p.pluginid, p.base_plugin, COUNT(*) pc'.
             ' FROM {pagesort} ps'.
             ' INNER JOIN {plugins} p ON p.pluginid = ps.pluginid'.
             " WHERE (p.base_plugin = 'Media Gallery')".
             ' GROUP BY p.pluginid, p.base_plugin'.
             ' ORDER BY p.base_plugin, p.pluginid';
      if($getplugins = $DB->query($sql))
      {
        $LatestImages = new LatestImagesClass('latest_images');

        // Create output for each plugin (if multiple):
        while($entry = $DB->fetch_array($getplugins, NULL, MYSQL_ASSOC))
        {
          $pid = (int)$entry['pluginid'];

          // Check plugin view permissions first
          if(empty($entry['pc']) ||
             (empty($userinfo['adminaccess']) &&
              (empty($userinfo['pluginviewids']) || !in_array($pid,$userinfo['pluginviewids'])) &&
              (empty($userinfo['pluginadminids']) || !in_array($pid,$userinfo['pluginadminids']))))
          {
            continue;
          }

          $LatestImages->SetSourcePluginID($pid);
          $LatestImages->settings['exclude_sections'] = '';
          $LatestImages->settings['images_per_row'] = 3;
          $LatestImages->settings['number_of_images_to_display'] = 999;
          ob_start();
          $LatestImages->DisplayLatestImages(true);
          $output = ob_get_clean();
          if(strlen(trim($output)))
          {
            $page_content .=
              (isset($sdlanguage['plugin_name_'.$pid])?'<h1>'.$sdlanguage['plugin_name_'.$pid].'</h1>':'').
              $output.'<br />';
          }
        } //while

        unset($LatestImages);
      }
    }

    $tmp_smarty = self::InitTmpl($page_url,$current_url);
    $tmp_smarty->assign('content_url',    $content_url);
    $tmp_smarty->assign('my_media_list',  $page_content);

    $tmpl_name = 'ucp_'.self::$_pageid.'.tpl';
    if($tmpl_done = SD_Smarty::display(11, $tmpl_name, $tmp_smarty))
    {
      self::$_action = 'submit';
      self::$_pageid = '';
    }
    else
    {
      if($userinfo['adminaccess']) echo '<p><strong>Template error OR template not found ('.$tmpl_name.')!<br />
      Please check Skins|Templates or "/includes/tmpl/defaults" folder!</strong></p>';
    }

    return (self::$_action != 'error');

  } //page_mymedia


  public static function page_myforum()
  {
    global $DB, $bbcode, $mainsettings, $sdlanguage,
           $userinfo, $usersystem, $categoryid;

    self::$_action = 'error';

    if($usersystem['name']!='Subdreamer')
    {
      echo '<div class="ucp_okMsg round_corners"><h3>'.SDProfileConfig::$phrases['msg_must_use_forum_profile'].'</h3></div>';
      return false;
    }
    if(!self::$_userid || empty($userinfo['loggedin']))
    {
      return false;
    }

    $page_url = RewriteLink('index.php?categoryid='.$categoryid);
    $page_url .= (strpos($page_url,'?')===false?'?':'&amp;').'profile='.self::$_userid;
    $url_params = 'do='.SDProfileConfig::GetPageFragment('page_mycontent',self::$_pageid);
    $current_url = $page_url.'#'.$url_params;
    $content_url = $page_url.'#'.'do='.SDProfileConfig::GetPageFragment('page_mycontent');

    $tmp_smarty = self::InitTmpl($page_url,$current_url);
    $tmp_smarty->assign('content_url', $content_url);

    $posts_sql_base = ' FROM '.PRGM_TABLE_PREFIX.'p_forum_posts fp
      INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_topics ft ON ft.topic_id = fp.topic_id
      INNER JOIN '.PRGM_TABLE_PREFIX."p_forum_forums ff ON ff.forum_id = ft.forum_id
      WHERE fp.user_id = %d
      AND ((IFNULL(ff.access_view,'')='') OR (ff.access_view like '%|".$userinfo['usergroupid']."|%'))
      AND ft.moderated = 0 AND fp.moderated = 0 AND ff.online = 1 AND ff.is_category = 0 ";

    $rowcount = $DB->query_first('SELECT COUNT(*) postcount'.$posts_sql_base, self::$_userid);
    $rowcount = empty($rowcount['postcount']) ? 0 : (int)$rowcount['postcount'];

    $page = Is_Valid_Number(GetVar('page', 1, 'whole_number'), 1, 1, 9999999);
    $pagesize = Is_Valid_Number(GetVar('page_size', 5, 'whole_number'), 5, 5, 100);
    $prev_page_size = Is_Valid_Number(GetVar('prev_page_size', $pagesize, 'whole_number'), $pagesize, 5, 100);
    if($pagesize != $prev_page_size) // re-calc last page position
    {
      $prev_page = GetVar('prev_page', $page, 'whole_number', 1, 9999999);
      $tmp_smarty->assign('prev_page', (int)$page);
      $page = ceil($prev_page*$prev_page_size / $pagesize);
      if($page < 1) $page = 1;
    }
    else
    {
      $prev_page = GetVar('prev_page', $page, 'whole_number', 1, 9999999);
      $tmp_smarty->assign('prev_page', (int)$prev_page);
    }

    if(($page-1)*$pagesize > $rowcount)
    {
      $page = ceil($rowcount / $pagesize);
    }
    $limit = ' ORDER BY fp.post_id DESC LIMIT ' . ($page-1)*$pagesize.','.$pagesize;
    $tmp_smarty->assign('page', (int)$page);
    $tmp_smarty->assign('page_size', (int)$pagesize);

    $posts_sql = 'SELECT ft.topic_id, ft.title, fp.post_id, fp.post, ff.forum_id, ff.title forum_name,
                  fp.post message, fp.user_id, fp.username, fp.date postdate '.
                  $posts_sql_base . $limit;

    $posts = $DB->query($posts_sql, self::$_userid);

    if(isset($bbcode) && ($bbcode instanceof BBCode) && !empty($mainsettings['allow_bbcode']))
    {
      $bbcode->RemoveRule('code');
      $bbcode->AddRule('code',
          array('mode'  => BBCODE_MODE_CALLBACK,
            'class'     => 'code',
            'method'    => 'sd_BBCode_DoCode',
            'allow_in'  => array('listitem', 'block', 'columns'),
            'content'   => BBCODE_VERBATIM
          )
      );
    }

    $pageid = $categoryid;
    if($forum_pluginid = GetPluginID('Forum'))
    {
      if($pageid = $DB->query_first("SELECT categoryid FROM {pagesort} WHERE pluginid = '%d' ORDER BY categoryid LIMIT 1",$forum_pluginid))
      {
        $pageid = $pageid['categoryid'];
      }
    }

    $forum = array();
    $forum['pagination'] = '';
    $forum['post_count'] = $rowcount;
    $forum['thread_count'] = $userinfo['profile']['user_thread_count'];

    $paginated = false;
    $p = new pagination;
    $p->adjacents(2);
    $p->className = 'forum-pagination';
    $p->currentPage($page);
    $p->items($rowcount);
    $p->limit($pagesize);
    $p->parameterName($url_params.'&amp;page_size='.$pagesize.'&amp;page');
    $p->pagesT = '';
    $p->showCounter = false;
    $p->target($page_url);

    $messages = array();
    while($post = $DB->fetch_array($posts,null,MYSQL_ASSOC))
    {
      // Topic header (with pagination output)
      if(!$paginated) // only process this once per loop
      {
        $paginated = true;
        $forum['pagination'] = $p->getOutput();
      }

      // Apply BBCode parsing
      $olddetect = $bbcode->GetDetectURLs();
      $oldpattern = $bbcode->GetURLPattern();
      $bbcode->SetDetectURLs(true);
      $bbcode->SetURLPattern('<a rel="nofollow" href="{$url/h}">{$text/h}</a>'); //SD341
      $post['post'] = $bbcode->Parse($post['post']);
      $bbcode->SetURLPattern($oldpattern);
      $bbcode->SetDetectURLs($olddetect);

      $messages[] = array(
        'date'        => $post['postdate'],
        'date_text'   => DisplayDate($post['postdate']),
        'forum_id'    => $post['forum_id'],
        'forum_link'  => RewriteLink('index.php?categoryid='.$pageid.'&amp;forum_id='.$post['forum_id']),
        'forum_name'  => strip_tags($post['forum_name']),
        'post'        => $post['post'],
        'post_link'   => RewriteLink('index.php?categoryid='.$pageid.'&amp;topic_id='.$post['topic_id'].
                         '&amp;post_id='. $post['post_id'] . '#' . $post['post_id']),
        'post_id'     => $post['post_id'],
        'title'       => $post['title'],
        'topic_id'    => $post['topic_id'],
        'topic_link'  => RewriteLink('index.php?categoryid='.$pageid.'&amp;topic_id='.$post['topic_id'])
      );
    }

    $forum['count'] = count($messages);
    $forum['messages'] = $messages;
    $tmp_smarty->assign('forum', $forum);

    unset($messages,$forum,$p);

    //SD344: new template handling
    $tmpl_name = 'ucp_'.self::$_pageid.'.tpl';
    if($tmpl_done = SD_Smarty::display(11, $tmpl_name, $tmp_smarty))
    {
      self::$_action = 'submit';
      self::$_pageid = '';
    }
    else
    {
      if($userinfo['adminaccess']) echo '<p><strong>Template error OR template not found ('.$tmpl_name.')!<br />
      Please check Skins|Templates or "/includes/tmpl/defaults" folder!</strong></p>';
    }

    return (self::$_action != 'error');

  } //page_myforum


  public static function page_avatar()
  {
    global $DB, $mainsettings, $sdlanguage, $userinfo, $usersystem, $categoryid;

    self::$_action = 'error';

    if($usersystem['name']!='Subdreamer')
    {
      echo '<div class="ucp_okMsg round_corners"><h3>'.SDProfileConfig::$phrases['msg_must_use_forum_profile'].'</h3></div>';
      return false;
    }
    if(!self::$_userid || empty($userinfo['loggedin']))
    {
      return false;
    }

    $DB->result_type = MYSQL_ASSOC;
    if(!$old_avatar = $DB->query_first("SELECT userid,user_avatar,user_avatar_link,avatar_disabled FROM {users_data}
                                       WHERE usersystemid = %d AND userid = %d",
                                       SDProfileConfig::$usersystem['usersystemid'], self::$_userid))
    {
      // Create initial profile data row for user
      $DB->query("INSERT INTO {users_data} (usersystemid,userid,authorname,user_text) VALUES (%d,%d,'','')",
                 SDProfileConfig::$usersystem['usersystemid'], self::$_userid);
      $old_avatar = $DB->query_first('SELECT userid,user_avatar,user_avatar_link,avatar_disabled FROM {users_data}
                                      WHERE usersystemid = %d AND userid = %d',
                                      SDProfileConfig::$usersystem['usersystemid'], self::$_userid);
    }
    $user_avatar_link = $old_avatar['user_avatar_link'];
    $DB->result_type = MYSQL_BOTH;

    $usergroup_options = SDProfileConfig::$usergroups_config[$userinfo['usergroupid']];
    $gravatars = !empty($mainsettings['enable_gravatars']) &&
                 (!empty($usergroup_options['avatar_enabled']) && (($usergroup_options['avatar_enabled'] & 1) == 1));
    if(!empty($old_avatar['avatar_disabled']) || ((int)$usergroup_options['avatar_enabled'] < 2))
    {
      $msg = '<h2>'.SDProfileConfig::$phrases['msg_avatar_not_available'].'</h2>';
      if(empty($old_avatar['avatar_disabled']) && $gravatars && ($usergroup_options['avatar_enabled'] & 1)) // 1 == Gravatar
      {
        $msg .= SDProfileConfig::$phrases['msg_only_gravatar_available'];
      }
      echo '<div class="ucp_okMsg round_corners">'.$msg.'</div>';
      return false;
    }

    SDProfileConfig::$last_error = false;
    require_once(SD_INCLUDE_PATH.'class_sd_media.php');

    $page_url = RewriteLink('index.php?categoryid='.$categoryid);
    $page_url .= (strpos($page_url,'?')===false?'?':'&amp;').'profile='.self::$_userid;
    $current_url = $page_url.'#do='.SDProfileConfig::$profile_pages[self::$_pageid];

    $gravatar_link = GetAvatarPath(SDProfileConfig::GetFieldValue('email',''), self::$_userid);
    $avatar_upload_allowed = (int)$usergroup_options['avatar_enabled'] & 2;
    $avatar_link_allowed   = (int)$usergroup_options['avatar_enabled'] & 4;
    $maxwidth              = (int)$usergroup_options['avatar_max_width'];
    $maxheight             = (int)$usergroup_options['avatar_max_height'];
    $maxsize               = (int)$usergroup_options['avatar_max_size'];
    $avatar_extensions     = empty($usergroup_options['avatar_extensions'])?false:(string)$usergroup_options['avatar_extensions'];
    if($avatar_extensions !== false)
    {
      $avatar_extensions = @explode(',', $avatar_extensions);
    }

    $avatar_uploaded = false;
    $avatar_path = isset($usergroup_options['avatar_path'])?(string)$usergroup_options['avatar_path']:false;
    if($avatar_path!==false)
    {
      $avatar_path = SD_Media::FixPath($avatar_path);
      SDProfileConfig::makePathWritable(ROOT_PATH.$avatar_path);
      $avatar_path .= (substr($avatar_path,-1)=='/'?'':'/').floor(self::$_userid / 1000).'/';
      SDProfileConfig::makePathWritable(ROOT_PATH.$avatar_path);
      $avatar_path = (!empty($avatar_path) && is_dir(ROOT_PATH.$avatar_path) && is_writable(ROOT_PATH.$avatar_path)) ? $avatar_path : false;
    }
    $avatar_h = $avatar_w = 0;

    // Check, if there were user-made changes and/or an image submitted
    $is_user_update = !empty($_POST['submit']) && ($_POST['submit']==1);

    $tmp_smarty = self::InitTmpl($page_url,$current_url);
    $tmp_smarty->assign('group_options',  $usergroup_options);
    $tmp_smarty->assign('enable_gravatars', $gravatars);
    $tmp_smarty->assign('gravatar', $gravatar_link);
    $tmp_smarty->assign('user_avatar_link', $user_avatar_link);

    if($is_user_update)
    {
      $user_avatar_type = GetVar('user_avatar_type', 0, 'natural_number', true, false);
      $avatar_link = GetVar('avatar_link', '', 'string', true, false);

      // Remove a previously uploaded avatar image (if exists)?
      if(!empty($old_avatar) && !empty($_POST['del_avatar_image']) && ($avatar_path !== false))
      {
        if(is_file(ROOT_PATH.$avatar_path.$old_avatar['user_avatar']))
        {
          @unlink(ROOT_PATH.$avatar_path.$old_avatar['user_avatar']);
          $DB->query("UPDATE {users_data} SET user_avatar_type=0,user_avatar='',user_avatar_link='',user_avatar_width=0,user_avatar_height=0
                      WHERE usersystemid = %d AND userid = %d",
                      SDProfileConfig::$usersystem['usersystemid'], self::$_userid);
          unset($old_avatar);
        }
      }

      // Process remote avatar image link (is it downloadable/valid)?
      if($avatar_link_allowed && ($avatar_path !== false) && ($avatar_extensions !== false) &&
         !empty($avatar_link) && sd_check_image_url($avatar_link))
      {
        $imagename = 'a'.self::$_userid.'_org';
        require_once(SD_INCLUDE_PATH.'class_sd_media.php');
        // Initiate image instance and process upload...
        $img = new SD_Image();
        if(false !== ($res = $img->Download($avatar_link, ROOT_PATH.$avatar_path, $imagename, 'gd')))
        {
          $ext = '.' . $img->getImageExt();
          // Security check
          if(!SD_Media_Base::ImageSecurityCheck(true, ROOT_PATH.$avatar_path, $imagename.$ext))
          {
            SDProfileConfig::$last_error = SD_MEDIA_ERR_INVALID;
          }
          else
          {
            //...and create a thumbnailed version
            $thumbnailfile = 'a'.self::$_userid.'-'.TIME_NOW.$ext;
            if(true === $img->CreateThumbnail(ROOT_PATH.$avatar_path.$thumbnailfile,
                                              $maxwidth, $maxheight))
            {
              // all ok, so assign thumbnail and fetch real dimensions:
              $avatar_uploaded = $thumbnailfile;
              if(true === $img->setImageFile(ROOT_PATH.$avatar_path.$thumbnailfile))
              {
                $avatar_w = $img->getImageWidth();
                $avatar_h = $img->getImageheight();
                $DB->query("UPDATE {users_data} SET user_avatar_type=2,user_avatar='%s',user_avatar_link='%s',user_avatar_width=0,user_avatar_height=0
                            WHERE usersystemid = %d AND userid = %d",
                            $avatar_uploaded,$avatar_link, SDProfileConfig::$usersystem['usersystemid'], self::$_userid);
                $user_avatar_link = $avatar_link;
                $old_img = ROOT_PATH.$avatar_path.$old_avatar['user_avatar'];
                if(is_file($old_img))
                {
                  @unlink($old_img);
                }
              }
              else
              {
                $avatar_uploaded = false;
              }
            }
            // remove temp image file
            @unlink(ROOT_PATH.$avatar_path.$imagename.$ext);
          }
          unset($img,$ext,$thumbnailfile);
        }
      }
      $tmp_smarty->assign('user_avatar_link', $user_avatar_link);

      // Process an uploaded image (only if path exists and dimensions etc. are valid)?
      if(($avatar_path !== false) && ($avatar_extensions !== false) &&
         !empty($_FILES['avatar_image']['name']) && ($maxwidth>0) && ($maxheight>0))
      {
        $imagename = 'a'.self::$_userid.'_org';
        if(class_exists('SD_Image'))
        {
          // Initiate image instance and process upload...
          $img = new SD_Image();
          if(!$img->UploadImage('avatar_image', $avatar_path, $imagename,
                                $avatar_extensions, $maxsize, 800, 600))
          {
            SDProfileConfig::$last_error = $img->getErrorCode();
          }
          else
          {
            // Security check
            $ext = '.'.$img->getImageExt();
            if(!SD_Media_Base::ImageSecurityCheck(true, ROOT_PATH . $avatar_path, $imagename.$ext))
            {
              SDProfileConfig::$last_error = SD_MEDIA_ERR_INVALID;
            }
            else
            {
              //...and create a thumbnailed version
              $thumbnailfile = 'a'.self::$_userid.'-'.TIME_NOW.$ext;
              if(true === $img->CreateThumbnail(ROOT_PATH.$avatar_path.$thumbnailfile,
                                                $maxwidth, $maxheight))
              {
                // Remove a previously uploaded avatar image (if exists)?
                if(!empty($old_avatar) && ($avatar_path !== false))
                {
                  $file = ROOT_PATH.$avatar_path.$old_avatar['user_avatar'];
                  if(is_file($file) && file_exists($file) && !is_dir($file))
                  {
                    @unlink($file);
                    unset($old_avatar);
                  }
                  unset($file);
                }
                // all ok, so assign thumbnail and fetch real dimensions:
                $avatar_uploaded = $thumbnailfile;
                if(true === $img->setImageFile(ROOT_PATH.$avatar_path.$thumbnailfile))
                {
                  $avatar_w = $img->getImageWidth();
                  $avatar_h = $img->getImageheight();
                }
                else
                {
                  $avatar_uploaded = false;
                }
              }
              // remove temp image file
              @unlink(ROOT_PATH.$avatar_path.$imagename.$ext);
            }
          }
          unset($img,$ext,$thumbnailfile);
        }
      }
    }
    else
    {
      $user_avatar_type = isset($userinfo['profile']['user_avatar_type']) ? (int)$userinfo['profile']['user_avatar_type'] : 0;
    }

    // Check configured avatar type (0=Gravatar,1=Upload,2=Link) if allowed:
    $user_avatar_type = Is_Valid_Number($user_avatar_type, 0, 0, 2);
    if( ($user_avatar_type==1 && !$avatar_upload_allowed) ||
        ($user_avatar_type==2 && !$avatar_link_allowed) )
    {
      $user_avatar_type = 0;
    }

    // Check, if there were user-made changes submitted
    if($is_user_update)
    {
      if(isset($old_avatar))
      {
        if(($avatar_uploaded!==false) && is_file(ROOT_PATH.$avatar_path.$old_avatar['user_avatar']))
        {
          @unlink(ROOT_PATH.$avatar_path.$old_avatar['user_avatar']);
        }
      }
      $DB->query("UPDATE {users_data} SET user_avatar_type = $user_avatar_type".
                 ($avatar_uploaded===false?'':",user_avatar='$avatar_uploaded', user_avatar_width = $avatar_w, user_avatar_height = $avatar_h").
                 " WHERE usersystemid = %d AND userid = %d",
                  SDProfileConfig::$usersystem['usersystemid'], self::$_userid);
    }

    // MUST reload user's avatar data here in any case!
    $DB->result_type = MYSQL_ASSOC;
    if($getavatar = $DB->query_first("SELECT userid,user_avatar_type,user_avatar,user_avatar_width,user_avatar_height
                                      FROM {users_data} WHERE usersystemid = %d AND userid = %d",
                                      SDProfileConfig::$usersystem['usersystemid'], self::$_userid))
    {
      $avatar_uploaded = $getavatar['user_avatar'];
      $avatar_w = (int)$getavatar['user_avatar_width'];
      $avatar_h = (int)$getavatar['user_avatar_height'];
    }
    if(($avatar_path!==false) && isset($getavatar) && !empty($avatar_uploaded))
    {
      $avatar_uploaded = SITE_URL . $avatar_path . $avatar_uploaded;
    }

    $tmp_err = false;
    if(isset(SDProfileConfig::$last_error) && (SDProfileConfig::$last_error!==false))
    {
      $tmp_err = SDProfileConfig::$last_error;
      if(is_numeric($tmp_err))
      {
        $tmp_err = SDProfileConfig::$phrases['image_error'.$tmp_err];
      }
      else
      if(is_array($tmp_err))
      {
        foreach($tmp_err as $key => $err)
        {
          $tmp_err = SDProfileConfig::$phrases['image_error'.$err].'<br />';
        }
      }
    }

    $tmp_smarty->assign('user_avatar_type', $user_avatar_type);
    $tmp_smarty->assign('avatar_upload_allowed', $avatar_upload_allowed);
    $tmp_smarty->assign('avatar_link_allowed', $avatar_link_allowed);
    $tmp_smarty->assign('avatar_path', $avatar_path);
    $tmp_smarty->assign('avatar_uploaded', $avatar_uploaded);
    $tmp_smarty->assign('avatar_width', $avatar_w);
    $tmp_smarty->assign('avatar_height', $avatar_h);
    $tmp_smarty->assign('errors', $tmp_err);

    //SD344: new template handling
    $tmpl_name = 'ucp_'.self::$_pageid.'.tpl';
    if($tmpl_done = SD_Smarty::display(11, $tmpl_name, $tmp_smarty))
    {
      self::$_action = 'submit';
      self::$_pageid = '';
    }
    else
    {
      if($userinfo['adminaccess']) echo '<p><strong>Template error OR template not found ('.$tmpl_name.')!<br />
      Please check Skins|Templates or "/includes/tmpl/defaults" folder!</strong></p>';
    }

    return (self::$_action != 'error');

  } //page_avatar


  public static function page_picture()
  {
    global $DB, $mainsettings, $sdlanguage, $userinfo, $usersystem, $categoryid;

    self::$_action = 'error';

    if(!self::$_userid || empty($userinfo['loggedin']))
    {
      return false;
    }

    $DB->result_type = MYSQL_ASSOC;
    $main_select = 'SELECT userid, user_profile_img, profile_img_type,'.
                   ' profile_img_width, profile_img_height, profile_img_link,'.
                   ' profile_img_disabled, profile_img_public'.
                   ' FROM {users_data}'.
                   ' WHERE usersystemid = %d AND userid = %d';
    if(!$old_img = $DB->query_first($main_select, SDProfileConfig::$usersystem['usersystemid'], self::$_userid))
    {
      // Create initial profile data row for user
      $DB->query("INSERT INTO {users_data} (usersystemid,userid,authorname,user_text) VALUES (%d,%d,'','')",
                 SDProfileConfig::$usersystem['usersystemid'], self::$_userid);
      $old_img = $DB->query_first($main_select, SDProfileConfig::$usersystem['usersystemid'], self::$_userid);
    }
    $DB->result_type = MYSQL_BOTH;

    $user_picture_link = empty($old_img['profile_img_link'])?'':$old_img['profile_img_link'];
    $usergroup_options = SDProfileConfig::$usergroups_config[$userinfo['usergroupid']];
    if(!empty($old_img['profile_img_disabled']) || ((int)$usergroup_options['pub_img_enabled'] < 2))
    {
      $msg = '<h2>'.SDProfileConfig::$phrases['msg_profile_picture_not_available'].'</h2>';
      echo '<div class="ucp_okMsg round_corners">'.$msg.'</div>';
      return false;
    }

    require_once(SD_INCLUDE_PATH.'class_sd_media.php');

    $page_url = RewriteLink('index.php?categoryid='.$categoryid);
    $page_url .= (strpos($page_url,'?')===false?'?':'&amp;').'profile='.self::$_userid;
    $current_url = $page_url.'#do='.SDProfileConfig::$profile_pages[self::$_pageid];

    $picture_upload_allowed = ((int)$usergroup_options['pub_img_enabled'] & 2) > 0;
    $picture_link_allowed   = ((int)$usergroup_options['pub_img_enabled'] & 4) > 0;
    $maxwidth               = (int)$usergroup_options['pub_img_max_width'];
    $maxheight              = (int)$usergroup_options['pub_img_max_height'];
    $maxsize                = (int)$usergroup_options['pub_img_max_size'];
    $picture_extensions     = empty($usergroup_options['pub_img_extensions'])?false:(string)$usergroup_options['pub_img_extensions'];
    if($picture_extensions !== false)
    {
      $picture_extensions = @explode(',', $picture_extensions);
    }

    $gravatar_link = GetAvatarPath(SDProfileConfig::GetFieldValue('email',''), self::$_userid);
    $gravatars = !empty($mainsettings['enable_gravatars']) &&
                 (($usergroup_options['pub_img_enabled'] & 1) == 1);

    $picture_uploaded = false;
    $pub_img_path = isset($usergroup_options['pub_img_path'])?(string)$usergroup_options['pub_img_path']:false;
    if($pub_img_path!==false)
    {
      $pub_img_path = SD_Media::FixPath($pub_img_path);
      SDProfileConfig::makePathWritable(ROOT_PATH.$pub_img_path);
      $pub_img_path .= (substr($pub_img_path,-1)=='/'?'':'/').floor(self::$_userid / 1000).'/';
      SDProfileConfig::makePathWritable(ROOT_PATH.$pub_img_path);
      $pub_img_path = (!empty($pub_img_path) && is_dir(ROOT_PATH.$pub_img_path) && is_writable(ROOT_PATH.$pub_img_path)) ? $pub_img_path : false;
    }
    $picture_h = $picture_w = 0;

    // Check, if there were user-made changes and/or an image submitted
    $is_user_update = !empty($_POST['submit']) && ($_POST['submit']==1);

    if($is_user_update)
    {
      $user_picture_type = GetVar('user_picture_type', 0, 'natural_number', true, false);
      $picture_link = GetVar('picture_link', '', 'string', true, false);

      // Remove a previously uploaded profile picture (if exists)?
      if(!empty($old_img) && !empty($_POST['del_profile_image']) && ($pub_img_path !== false))
      {
        // Remove previous picture file if exists
        $fname = ROOT_PATH.$pub_img_path.$old_img['user_profile_img'];
        if(is_file($fname) && file_exists($fname))
        {
          @unlink(ROOT_PATH.$pub_img_path.$old_img['user_profile_img']);
        }
        // Clear picture data in user's data row
        $DB->query("UPDATE {users_data} SET profile_img_type=0,user_profile_img='',profile_img_link='',profile_img_width=0,profile_img_height=0".
                   ' WHERE usersystemid = %d AND userid = %d',
                   SDProfileConfig::$usersystem['usersystemid'], self::$_userid);
        unset($old_img);
      }

      // Remove a previously added picture link?
      if(!empty($old_img['profile_img_link']) && !empty($_POST['del_profile_link']))
      {
        $DB->query("UPDATE {users_data} SET profile_img_link =''".
                   ' WHERE usersystemid = %d AND userid = %d',
                   SDProfileConfig::$usersystem['usersystemid'], self::$_userid);
      }

      // Process remote picture image link (is it downloadable/valid)?
      if($picture_link_allowed && ($pub_img_path !== false) && ($picture_extensions !== false) &&
         !empty($picture_link) && sd_check_image_url($picture_link))
      {
        $imagename = 'p'.self::$_userid.'_org';

        // Initiate image instance
        $img = new SD_Image();

        // First try to download the remote image and create local, thumbnailed version
        if(false !== ($res = $img->Download($picture_link, ROOT_PATH.$pub_img_path, $imagename, 'gd')))
        {
          $ext = '.'.$img->getImageExt();
          // Security check
          if(!SD_Media_Base::ImageSecurityCheck(true, ROOT_PATH . $pub_img_path, $imagename.$ext))
          {
            SDProfileConfig::$last_error = SD_MEDIA_ERR_INVALID;
          }
          else
          {
            //...and create a thumbnailed version
            $thumbnailfile = 'p'.self::$_userid.'-'.TIME_NOW.$ext;
            if(true === $img->CreateThumbnail(ROOT_PATH.$pub_img_path.$thumbnailfile,
                                              $maxwidth, $maxheight))
            {
              // Register thumbnail, fetch real dimensions and switch profile picture type
              // back to uploaded file as we now have a local copy (URL is not stored!)
              $user_picture_type = 1;
              $picture_uploaded  = $thumbnailfile;
              $user_picture_link = '';
              if(!$img->setImageFile(ROOT_PATH.$pub_img_path.$thumbnailfile))
              {
                $picture_uploaded = false;
              }
              else
              {
                $picture_w = $img->getImageWidth();
                $picture_h = $img->getImageheight();
                $DB->query("UPDATE {users_data} SET profile_img_type=%d, user_profile_img='%s', profile_img_link='', profile_img_width=%d, profile_img_height=%d".
                           ' WHERE usersystemid = %d AND userid = %d',
                           $user_picture_type, $picture_uploaded, $picture_w, $picture_h,
                           SDProfileConfig::$usersystem['usersystemid'], self::$_userid);
                // Remove a previous profile picture if exists
                if(!empty($old_img['user_profile_img']) && ($pub_img_path !== false))
                {
                  $file = ROOT_PATH.$pub_img_path.$old_img['user_profile_img'];
                  if(is_file($file) && file_exists($file) && !is_dir($file))
                  {
                    @unlink($file);
                  }
                }
              }
            }
            // remove temp image file
            $file = ROOT_PATH.$pub_img_path.$imagename.$ext;
            if(is_file($file) && file_exists($file))
            {
              @unlink($file);
            }
          }
        }
        else
        {
          // Download failed, so issue warning, store image link and clear image file
          $user_picture_type = 2;
          $picture_w = $maxwidth;
          $picture_h = $maxheight;
          $DB->query("UPDATE {users_data} SET profile_img_type=%d, user_profile_img='', profile_img_link='%s', profile_img_width=%d, profile_img_height=%d".
                     ' WHERE usersystemid = %d AND userid = %d',
                     $user_picture_type, $picture_link, $picture_w, $picture_h,
                     SDProfileConfig::$usersystem['usersystemid'], self::$_userid);
          // Remove a previous profile picture if exists
          if(!empty($old_img['user_profile_img']) && ($pub_img_path !== false))
          {
            $file = ROOT_PATH.$pub_img_path.$old_img['user_profile_img'];
            if(is_file($file) && file_exists($file) && !is_dir($file))
            {
              @unlink($file);
            }
          }
        }
        unset($img,$ext,$file,$thumbnailfile);
      }
      else
      // Process an uploaded image (only if path exists and dimensions etc. are valid)?
      if(($pub_img_path !== false) && ($picture_extensions !== false) &&
         !empty($_FILES['picture_image']['name']) && ($maxwidth>0) && ($maxheight>0))
      {
        $imagename = 'p'.self::$_userid.'_org';
        if(class_exists('SD_Image'))
        {
          // Initiate image instance and process upload...
          $img = new SD_Image();

          if(!$img->UploadImage('picture_image', $pub_img_path, $imagename,
                                $picture_extensions, $maxsize, 800, 600))
          {
            SDProfileConfig::$last_error = $img->getErrorCode();
          }
          else
          {
            // Security check
            $ext = '.'.$img->getImageExt();
            if(!SD_Media_Base::ImageSecurityCheck(true, ROOT_PATH . $pub_img_path, $imagename.$ext))
            {
              SDProfileConfig::$last_error = SD_MEDIA_ERR_INVALID;
            }
            else
            {
              //...and create a thumbnailed version
              $thumbnailfile = 'p'.self::$_userid.'-'.TIME_NOW.$ext;
              if(true === $img->CreateThumbnail(ROOT_PATH.$pub_img_path.$thumbnailfile,
                                                $maxwidth, $maxheight))
              {
                // Remove a previously uploaded avatar image (if exists)?
                if(!empty($old_img['user_profile_img']) && ($pub_img_path !== false))
                {
                  $file = ROOT_PATH.$pub_img_path.$old_img['user_profile_img'];
                  if(is_file($file) && file_exists($file) && !is_dir($file))
                  {
                    @unlink($file);
                    unset($old_img); //do not remove!
                  }
                  unset($file);
                }
                // all ok, so assign thumbnail and fetch real dimensions:
                $picture_uploaded = $thumbnailfile;
                if(!$img->setImageFile(ROOT_PATH.$pub_img_path.$thumbnailfile))
                {
                  $picture_uploaded = false;
                }
                else
                {
                  $picture_w = $img->getImageWidth();
                  $picture_h = $img->getImageheight();
                }
              }
              // remove temp image file
              @unlink(ROOT_PATH.$pub_img_path.$imagename.$ext);
            }
          }
          unset($img,$ext,$thumbnailfile);
        }
      }
      unset($old_img); // Cleanup
    }
    else
    {
      $user_picture_type = isset($userinfo['profile']['profile_img_type']) ? (int)$userinfo['profile']['profile_img_type'] : 0;
    }

    // Check configured avatar type (0=Gravatar,1=Upload,2=Link) if allowed:
    $user_picture_type = Is_Valid_Number($user_picture_type, 0, 0, 2);
    if( ($user_picture_type==1 && !$picture_upload_allowed) ||
        ($user_picture_type==2 && !$picture_link_allowed) )
    {
      $user_picture_type = 0;
    }

    // Check, if there were user-made changes submitted
    if($is_user_update)
    {
      /*
      if(!empty($old_img['user_profile_img']) && ($pub_img_path!==false))
      {
        $file = ROOT_PATH.$pub_img_path.$old_img['user_profile_img'];
        if(is_file($file) && file_exists($file) && !is_dir($file))
        {
          @unlink($file);
        }
        unset($file);
      }
      */
      $DB->query('UPDATE {users_data} SET profile_img_type = %d'.
                 ($picture_uploaded===false?'':",user_profile_img='$picture_uploaded',profile_img_width=$picture_w,profile_img_height=$picture_h").
                 ' WHERE usersystemid = %d AND userid = %d',
                 $user_picture_type, SDProfileConfig::$usersystem['usersystemid'], self::$_userid);
    }
    unset($old_img);

    // MUST reload user's picture data here always!
    $DB->result_type = MYSQL_ASSOC;
    if($getpicture = $DB->query_first($main_select, SDProfileConfig::$usersystem['usersystemid'], self::$_userid))
    {
      $picture_uploaded = $getpicture['user_profile_img'];
      $picture_w = (int)$getpicture['profile_img_width'];
      $picture_h = (int)$getpicture['profile_img_height'];
    }
    if(($pub_img_path!==false) && isset($getpicture) && !empty($picture_uploaded))
    {
      $picture_uploaded = SITE_URL . $pub_img_path . $picture_uploaded;
    }

    $tmp_err = false;
    if(isset(SDProfileConfig::$last_error) && (SDProfileConfig::$last_error!==false))
    {
      $tmp_err = SDProfileConfig::$last_error;
      if(is_numeric($tmp_err))
      {
        $tmp_err = SDProfileConfig::$phrases['image_error'.$tmp_err];
      }
      else
      if(is_array($tmp_err))
      {
        foreach($tmp_err as $key => $err)
        {
          $tmp_err = SDProfileConfig::$phrases['image_error'.$err].'<br />';
        }
      }
    }

    $tmp_smarty = self::InitTmpl($page_url,$current_url);
    $tmp_smarty->assign('group_options',  $usergroup_options);
    $tmp_smarty->assign('user_picture_link', $user_picture_link);

    //SD371: "enable_gravatars" was not set for template
    $gravatars_enabled = !empty($mainsettings['enable_gravatars']) &&
                         (!empty($usergroup_options['avatar_enabled']) && (($usergroup_options['avatar_enabled'] & 1) == 1));
    $tmp_smarty->assign('enable_gravatars', $gravatars_enabled);
    $tmp_smarty->assign('picture_allow_gravatar', $gravatars);
    $tmp_smarty->assign('gravatar', $gravatar_link);
    $tmp_smarty->assign('user_picture_type', $user_picture_type);
    $tmp_smarty->assign('user_picture_link',$user_picture_link);
    $tmp_smarty->assign('picture_upload_allowed', $picture_upload_allowed);
    $tmp_smarty->assign('picture_link_allowed', $picture_link_allowed);
    $tmp_smarty->assign('picture_path', $pub_img_path);
    $tmp_smarty->assign('picture_uploaded', $picture_uploaded);
    $tmp_smarty->assign('picture_width', $picture_w);
    $tmp_smarty->assign('picture_height', $picture_h);
    $tmp_smarty->assign('errors', $tmp_err);

    //SD344: new template handling
    $tmpl_name = 'ucp_'.self::$_pageid.'.tpl';
    if($tmpl_done = SD_Smarty::display(11, $tmpl_name, $tmp_smarty))
    {
      self::$_action = 'submit';
      self::$_pageid = '';
    }
    else
    {
      if($userinfo['adminaccess']) echo '<p><strong>Template error OR template not found ('.$tmpl_name.')!<br />
      Please check Skins|Templates or "/includes/tmpl/defaults" folder!</strong></p>';
    }

    return (self::$_action != 'error');

  } //page_picture


  public static function page_subscriptions()
  {
    global $DB, $categoryid, $mainsettings, $plugin_names,
           $refreshpage, $sdlanguage, $userinfo, $usersystem;

    self::$_action = 'error';

    $usergroup_options = SDProfileConfig::$usergroups_config[$userinfo['usergroupid']];

    $page_url = RewriteLink('index.php?categoryid='.$categoryid);
    $page_url .= (strpos($page_url,'?')===false?'?':'&amp;').'profile='.self::$_userid;
    $current_url = $page_url.'#do='.SDProfileConfig::$profile_pages[self::$_pageid];

    // Update subscriptions when user submitted page
    if(!empty($_POST['submit']) && ($_POST['submit']==1))
    {
      // Check if subscriptions were checked for removal (unsubscribe)
      if(!CheckFormToken(SDProfileConfig::$form_prefix . 'token'))
      {
        if(defined('IN_ADMIN'))
        {
          echo 'here';
		  RedirectPage($refreshpage,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
        }
        else
        {
          RedirectFrontPage(RewriteLink('index.php?categoryid='.$categoryid),'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
        }
        return false;
      }
      // Update selected enries
      if($ids = GetVar('email_options',null,'array',true,false))
      {
        foreach($ids as $key => $value)
        {
          if($key = Is_Valid_Number($key,'whole_number',1,9999999))
          {
            if(isset($value) && (($value==0) || ($value==1)))
            $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'users_subscribe SET email_notify = %d WHERE userid = %d AND id = %d',
                       $value, self::$_userid, $key);
          }
        }
      }
      // Remove selected enries
      if($ids = GetVar('selected_items',null,'array',true,false))
      {
        foreach($ids as $key)
        {
          if($key = Is_Valid_Number($key,'whole_number',1,9999999))
          {
            $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'users_subscribe WHERE userid = %d AND id = %d', self::$_userid, $key);
          }
        }
      }
      RedirectFrontPage(RewriteLink('index.php?categoryid='.$categoryid.'&profile='.
                        self::$_userid.'&do='.SDProfileConfig::$profile_pages['page_subscriptions']),
                        SDProfileConfig::$phrases['msg_subscriptions_updated']);
      return true;
    }

    // Fetch existing user's subscriptions and pass data to smarty
    $subscriptions = array();
    if($getsubscriptions = $DB->query('SELECT * FROM '.PRGM_TABLE_PREFIX.'users_subscribe
                             WHERE userid = %d
                             ORDER BY id DESC',
                             self::$_userid))
    {
      $sub = new SDSubscription(self::$_userid);
      while($entry = $DB->fetch_array($getsubscriptions,null,MYSQL_ASSOC))
      {
        $entry['data_pluginname'] = '';
        $entry['data_link'] = '';
        $entry['data_sublink'] = '';
        $entry['data_title'] = '[untitled]';
        $entry['data_subtitle'] = '';
        $entry['data_userid'] = 0;
        $entry['data_username'] = '';
        $entry['data_useravatar'] = '';
        $entry['data_pagename'] = '';
        if(!empty($entry['pluginid']))
        {
          $pid             = (int)$entry['pluginid'];
          $sub->pluginid   = $pid;
          $sub->objectid   = (int)$entry['objectid'];
          $sub->type       = (string)$entry['type'];
          $sub->categoryid = (int)$entry['categoryid'];
          if($sub->GetContent())
          {
            $entry['data_date'] = empty($sub->data_date)?'':DisplayDate($sub->data_date,'',true);
            $entry['data_title'] = $sub->data_title;
            $entry['data_subtitle'] = $sub->data_subtitle;
            $entry['data_link'] = $sub->data_link;
            $entry['data_sublink'] = $sub->data_sublink;
            $entry['data_userid'] = $sub->data_userid;
            $entry['data_username'] = $sub->data_username;
            $entry['data_useravatar'] = empty($sub->data_userid)?'':ForumAvatar($sub->data_userid,'');
            $entry['data_userlink'] = ForumLink(4,$sub->data_userid);
            $entry['data_pluginname'] = $plugin_names[$pid];
            $entry['data_pagename'] = $sub->data_pagename;
            $subscriptions[] = $entry;
          }
        }

      } //while
    }

    $tmp_smarty = self::InitTmpl($page_url,$current_url);
    $tmp_smarty->assign('group_options',  $usergroup_options);
    $tmp_smarty->assign('subscriptions', $subscriptions);
    $tmp_smarty->assign('options', array());

    //SD344: new template handling
    $tmpl_name = 'ucp_'.self::$_pageid.'.tpl';
    if($tmpl_done = SD_Smarty::display(11, $tmpl_name, $tmp_smarty))
    {
      self::$_action = 'submit';
      self::$_pageid = '';
    }
    else
    {
      if($userinfo['adminaccess']) echo '<p><strong>Template error OR template not found ('.$tmpl_name.')!<br />
      Please check Skins|Templates or "/includes/tmpl/defaults" folder!</strong></p>';
    }

    return (self::$_action != 'error');

  } //page_subscriptions


  public static function page_createmessage()
  {
    global $categoryid, $mainsettings_allow_bbcode, $refreshpage, $sdlanguage, $userinfo;

    self::$_action = 'error';

    if(!CheckFormToken(SDProfileConfig::$form_prefix . 'token'))
    {
      if(defined('IN_ADMIN'))
      {
        RedirectPage($refreshpage,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      }
      else
      {
        RedirectFrontPage(RewriteLink('index.php?categoryid='.$categoryid),'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      }
      return;
    }

    $delete   = GetVar('msg_delete',  0, 'bool',true,false);
    $reply_to = GetVar('reply_to_id', 0, 'whole_number',true,false);

    // If messaging is not enabled for user, it is still allowed to delete
    // a (pre-)existing message, which could also be a system notification:
    $usergroups_config = SDProfileConfig::$usergroups_config[$userinfo['usergroupid']];
    if(!$usergroups_config['msg_enabled'])
    {
      if(!empty($delete) && !empty($reply_to))
      {
        SDMsg::DeletePrivateMessages($userinfo['userid'], $reply_to);
      }

      self::$errors  = false;
      self::$_action = 'do';
      self::$_pageid = 'page_viewsentmessages';
      return true;
    }

    $recipient      = GetVar('msg_recipient',   0,       'whole_number',true,false);
    $recipients     = GetVar('msg_recipients',  array(), 'array',true,false);
    $p_recipient_id = GetVar('p_recipient_id',  0,       'whole_number',true,false);
    $p_reply_to_id  = GetVar('p_reply_to_id',   0,       'whole_number',true,false);
    $title          = GetVar('msg_title',       '',      'string',true,false);
    $message        = GetVar('msg_text',        '',      'string',true,false);
    $private        = GetVar('msg_private',     1,       'bool',true,false) ? 1 : 0;
    $invites        = GetVar('msg_invites',     0,       'bool',true,false) ? 1 : 0;
    $save_copy      = GetVar('msg_save_copy',   0,       'bool',true,false) ? 1 : 0;
    $read_notify    = GetVar('msg_read_notify', 0,       'bool',true,false) ? 1 : 0;
    $master_id      = GetVar('d',               0,       'whole_number',true,false);
    $bcc            = GetVar('msg_bcc',         array(), 'array',true,false);
    $attachment     = isset($_FILES['attachment']) ? $_FILES['attachment'] : false;

    if(!$delete && !$reply_to && !$master_id && !$p_recipient_id &&
       ((!$recipient && empty($recipients)) || empty($title) || empty($message) ))
    {
      self::$errors = SDProfileConfig::$phrases['err_msg_fail_incomplete'];
      self::$_action = 'do';
      self::$_pageid = 'page_newmessage';
    }

    $msg = SDProfileConfig::GetMsgObj();
    $msg->setMessageText($message);
    $msg->addRecipients($recipients);
    if(!empty($bcc)) $msg->addRecipients($bcc,true);
    if(!empty($recipient)) $msg->setRecipient($recipient);
    if($p_recipient_id) $msg->setRecipient($p_recipient_id);
    $msg->setSender($userinfo['userid']);
    $msg->setTitle($title);
    $msg->setDiscussionID($master_id);
    if($attachment) $msg->addAttachment($attachment);

    SDMsg::setOption('is_private', $private);
    SDMsg::setOption('allow_invites', $invites);
    SDMsg::setOption('save_copy', $save_copy);
    SDMsg::setOption('reply_id', $reply_to);
    SDMsg::setOption('delete', $delete);
    SDMsg::setOption('read_notify', $read_notify);
    SDMsg::setOption('attachments_extensions', $usergroups_config['attachments_extensions']);
    SDMsg::setOption('attachments_max_size', (int)$usergroups_config['attachments_max_size']);
    SDMsg::$email_address = SDProfileConfig::$settings['pm_email_address'];
    SDMsg::$email_subject = SDProfileConfig::$settings['pm_email_subject'];
    SDMsg::$email_body    = SDProfileConfig::$settings['pm_email_body'];
    SDMsg::$email_include_msg = SDProfileConfig::$settings['pm_email_includes_message'];
    SDMsg::$email_msg_bbcode = $mainsettings_allow_bbcode;

    $hasAccess = true;
    if(empty($recipients) && empty($recipient) && ($reply_msg = SDMsg::loadMessage($reply_to)))
    {
      $hasAccess = $msg->setRecipient($reply_msg['userid']);
      unset($reply_msg);
    }
    if($master_id && $hasAccess)
    {
      $hasAccess = SDMsg::checkUserCanReplyDiscussion($userinfo['userid'], $master_id, $reply_to);
    }

    if(!$hasAccess)
    {
      self::$errors = $sdlanguage['no_post_access'];
      return false;
    }

    if(!empty($usergroups_config['msg_requirevvc']) || !empty($userinfo['require_vvc']))
    {
      $vvcid = GetVar('msg_vvcid', '', 'string',true,false);
      $code  = GetVar('msg_verifycode', '', 'string', true, false);
      if(empty($vvcid) || empty($code) || !ValidVisualVerifyCode($vvcid, $code))
      {
        self::$errors  = 5;// captcha invalid
        self::$_action = 'submit';
        self::$_pageid = 'page_newmessage';
        return false;
      }
    }

    if($msg->save())
    {
      self::$errors  = false;
      self::$_action = 'do';
      self::$_pageid = $private ? 'page_viewsentmessages' : (!empty($master_id) ? 'page_viewdiscussion' : 'page_viewdiscussions');
      return true;
    }

    if(!empty($msg->errorcode))
    {
      if($msg->errorcode == SD_MSG_ERR_INVALID_RECIPIENT)
      {
        self::$errors = SDProfileConfig::$phrases['err_invalid_recipient'].' '.$msg->errortext;
      }
      elseif($msg->errorcode == SD_MSG_ERR_TITLE_MISSING)
      {
        self::$errors = SDProfileConfig::$phrases['err_title_missing'];
      }
      elseif($msg->errorcode == SD_MSG_ERR_BODY_EMPTY)
      {
        self::$errors = SDProfileConfig::$phrases['err_body_missing'];
      }
    }
    if(empty(self::$errors))
    {
      self::$errors = isset($msg->errortext) ? $msg->errortext : SDProfileConfig::$phrases['err_not_send'];
    }
    return false;

  } //page_createmessage


  public static function page_viewmessage()
  {
    self::$_data = array();
    self::$_action = '';

    $msg_id = GetVar('id', 0, 'whole_number');
    if($msg_id > 0)
    {
      global $DB, $userinfo;

      $msg = SDProfileConfig::GetMsgObj();
      self::$_data = $msg->loadMessage($msg_id); // First fetch message...

      // Send "message read" notification (as message)?
      if(!empty($userinfo['userid']) && !empty(self::$_data['msg_id']) && !empty(self::$_data['msg_read_notify']))
      {
        $msg->setMessageText(SDProfileConfig::$phrases['msg_conf_your_message'].' `'.self::$_data['msg_title']."`\r\n".
                             SDProfileConfig::$phrases['msg_conf_to'].' `'.self::$_data['recipient_name']."`\r\n".
                             SDProfileConfig::$phrases['msg_conf_was_read'].' '.DisplayDate(TIME_NOW)."\r\n\r\n".
                             SDProfileConfig::$phrases['msg_conf_footer']);
        $msg->setRecipient(self::$_data['userid']);
        $msg->setSender($userinfo['userid']);
        $msg->setTitle(SDProfileConfig::$phrases['msg_conf_title'].' '.self::$_data['msg_title']);
        SDMsg::setOption('is_private', 1);
        SDMsg::setOption('save_copy', 0);
        $msg->save();
      }
      //SD370: only mark as read if no timestamp yet set
      if(empty(self::$_data['msg_read']))
      {
        SDMsg::setMessagesRead($userinfo['userid'], $msg_id);
      }
      //SD370: update inbox/outbox counts
      if(self::$_data['recipient_id']==self::$_userid)
        SDMsg::updateUserInboxCount(self::$_userid);
      else
        SDMsg::updateUserOutboxCount(self::$_userid);
    }

    return self::page_newmessage($msg_id);

  } //page_viewmessage


  public static function page_newmessage($msg_id=0)
  {
    global $categoryid, $sdlanguage, $userinfo;

    $isreply = !empty($msg_id);
    $isbcc   = !empty(self::$_data['is_bcc']); //SD343

    self::$_action = 'error';

    $page_url = RewriteLink('index.php?categoryid='.$categoryid);
    $page_url .= (strpos($page_url,'?')===false?'?':'&amp;').'profile='.self::$_userid;
    $current_url = $page_url.'#do='.SDProfileConfig::$profile_pages[self::$_pageid].
                   ($msg_id ? '&amp;id='.$msg_id : '');

    $tmp_smarty = self::InitTmpl($page_url,$current_url);
    $tmp_smarty->assign('discussion_title', SDProfileConfig::$phrases['untitled']);
    $tmpl_name = 'ucp_'.self::$_pageid.'.tpl';

    $msg = SDProfileConfig::GetMsgObj();

    // Is this a new discussion message?
    $js_check_empty_recipients = true;
    if($master_id = GetVar('d', 0, 'whole_number'))
    {
      $js_check_empty_recipients = false;
      $master = SDMsg::getDiscussions($userinfo['userid'], $master_id);
      $master_title = $master[$master_id]['master_title'];
      $tmp_smarty->assign('discussion_title', $master_title);
      $tmp_smarty->assign('allow_invites', (($master[$master_id]['starter_id']==$userinfo['userid'])));
      if(self::$_pageid=='page_newmessage') $tmpl_name = 'ucp_page_viewmessage.tpl';
    }

    $is_quote = GetVar('q', 0, 'bool') ? 1 : 0;
    $is_private = GetVar('msg_private', 1, 'bool') ? 1 : 0;
    $tmp_smarty->assign('msg_private',    $is_private);
    $tmp_smarty->assign('quote_message',  $is_quote);
    $tmp_smarty->assign('quote_private',  GetVar('p', 0, 'bool'));
    $tmp_smarty->assign('allow_delete',   empty($master_id));
    $tmp_smarty->assign('master_id',      $master_id);
    $tmp_smarty->assign('isbcc',          $isbcc); //SD343
    $tmp_smarty->assign('isreply',        $isreply);
    $tmp_smarty->assign('read_notify',    (GetVar('msg_read_notify', 0, 'bool')?1:0));

    $doQuote =  $isreply || $is_quote || !empty($_POST['p']) || !empty($_GET['p']);
    $msg_text = GetVar('msg_text', '', 'string',true,false);
    if($doQuote)
    {
      if(isset(self::$_data['msg_text_raw']) && strlen(self::$_data['msg_text_raw']))
      {
        self::$_data['msg_text_raw'] = '[quote]'.self::$_data['msg_text_raw'].'[/quote]';
        $msg_text = self::$_data['msg_text_raw'];
      }
    }
    $tmp_smarty->assign('data', self::$_data);

    if(!empty($msg_id) && !empty(self::$_data['msg_text_id']))
    {
      if($attachments = $msg->getAttachments(self::$_data['msg_text_id']))
      {
        $tmp_smarty->assign('attachments', $attachments);
      }
    }

    $usergroup_options = SDProfileConfig::$usergroups_config[$userinfo['usergroupid']];
    $tmp_smarty->assign('group_options',  $usergroup_options);

    if(!empty($usergroup_options['msg_requirevvc']) || !empty($userinfo['require_vvc']))
    {
      $vvcid = CreateVisualVerifyCode();
      $vvc = '
        <div class="sd_vvc">
        <input type="hidden" name="msg_vvcid" value="' . $vvcid . '" />' .
        VVCimage($vvcid) . '<br />
        ' . $sdlanguage['enter_verify_code'] . '<br />
        <input type="text" id="msg_verifycode" name="msg_verifycode" maxlength="'.SD_VVCLEN.'" style="width: '.(SD_VVCLEN*10).'px;" />
        </div>';
      $tmp_smarty->assign('captcha_html', $vvc);
    }

    $tmp_smarty->assign('errortitle', self::$errortitle);
    if(isset(SDProfileConfig::$last_error) && (SDProfileConfig::$last_error!==false))
    {
      $tmp_err = SDProfileConfig::$last_error;
      if(is_numeric($tmp_err))
      {
        $tmp_err = SDProfileConfig::$phrases['message_error'.$tmp_err];
      }
      else
      if(is_array($tmp_err))
      {
        foreach($tmp_err as $key => $err)
        {
          if(isset(SDProfileConfig::$phrases['message_error'.$err]))
          {
            $tmp_err = SDProfileConfig::$phrases['message_error'.$err];
          }
          else
          {
            $tmp_err = $err;
          }
        }
      }
      $tmp_smarty->assign('errors', $tmp_err);
    }

    // Max. recipients limit
    $msg_recipients_limit = $usergroup_options['msg_recipients_limit'];
    $tmp_smarty->assign('msg_recipient_limit', $msg_recipients_limit);

    $send_limit_hint = str_replace('#d#', $msg_recipients_limit, SDProfileConfig::$phrases['lbl_bcc_hint']);
    $tmp_smarty->assign('send_limit_hint', $send_limit_hint);

    if($isreply || ($master_id > 0))
    {
      $tmp_smarty->assign('reply_data', array(
        'recipient'  => GetVar('msg_recipient', (isset(self::$_data['recipient_id'])?self::$_data['recipient_id']:0), 'whole_number',true,false),
        'recipients' => GetVar('msg_recipients', array(), 'array',true,false),
        'title'      => GetVar('msg_title',      '',      'string',true,false),
        'message'    => $msg_text,
        'private'    => (GetVar('msg_private',    0,      'bool',true,false) ? 1 : 0),
        'invites'    => (GetVar('msg_invites',    0,      'bool',true,false) ? 1 : 0)
      ));
    }
    if(!empty(self::$errors))
    {
      $tmp_smarty->assign('form_data', array(
        'recipients' => GetVar('msg_recipients', array(), 'array',true,false),
        'title'      => GetVar('msg_title',      '',      'string',true,false),
        'message'    => $msg_text,
      ));
      self::$errors = false;
      SDUserProfilePageHandler::$errors = false;
    }
    else
    {
      if(isset(self::$_data['msg_title']) && strlen(self::$_data['msg_title']))
      {
        $tmp_smarty->assign('form_data', array(
          'title'      => SDProfileConfig::$phrases['lbl_re'].' '.self::$_data['msg_title'],
          'message'    => self::$_data['msg_text_raw']
        ));
      }
    }

    if(!$recipientid = GetVar('msg_recipients', null, 'array',true,false))
    {
      $recipientid = GetVar('recipientid', null, 'whole_number',false,true);
    }
    if(empty($recipientid))
    {
      if($isreply) $recipientid = ($isbcc ? self::$_data['recipient_id'] : self::$_data['userid']); //SD343
    }
    $tmp = sd_GetUserSelection('recipients_list', 'msg_recipients', $recipientid);
    $tmp_smarty->assign('recipients_list', $tmp);
    $tmp_smarty->assign('bcc_enabled', empty($master_id));
    $tmp_smarty->assign('bcc_list', sd_GetUserSelection('bcc_list', 'bcc', null));

    //SD343
    if($isbcc && !empty(self::$_data['org_recipientid']))
    {
      if($tmp = sd_GetUserrow(self::$_data['org_recipientid']))
      {
        $tmp_smarty->assign('org_recipient_id', self::$_data['org_recipientid']);
        $tmp_smarty->assign('org_recipient_name', $tmp['username']);
        $tmp_smarty->assign('org_recipient_link', ForumLink(4, self::$_data['org_recipientid']));
      }
    }

    //SD344: new template handling
    if($tmpl_done = SD_Smarty::display(11, $tmpl_name, $tmp_smarty))
    {
      self::$_action = 'submit';
      self::$_pageid = '';
    }
    else
    {
      if($userinfo['adminaccess']) echo '<p><strong>Template error OR template not found ('.$tmpl_name.')!<br />
      Please check Skins|Templates or "/includes/tmpl/defaults" folder!</strong></p>';
    }

    self::$_jscode .= '
  $("div#bcc_container,div#ucp_msg_options").hide();
  $("a#msg_options_switch").click(function(e){
    e.preventDefault();
    $("div#ucp_msg_options").toggle();
  });'.
  (!$js_check_empty_recipients?'':'
  $("#ucpForm").submit(function(e){
    if($("#recipients_list").length){
      var entry_count = $("#recipients_list li").length;
      if(entry_count == 0)
      {
        alert("'.htmlspecialchars(SDProfileConfig::$phrases['err_recipients_empty'],ENT_COMPAT).'");
        return false;
      }
    }
    return true;
  });').'
  $("#msg_recipient").autocomplete({
    maxItemsToShow: 10,
    matchInside: true,
    minChars: 2,
    loadingClass: "ucpLoading",
    url: "'.SITE_URL.'includes/ajax/getuserselection.php",
    useCache: false,
    onItemSelect: function(item) {
      var userid = 0;
      if(item.data.length) {
        userid = parseInt(item.data,10);
      }
      if(userid !== null && userid > 0) {
        if(!checkRecipientLimit()) return false;
        var id_exists = $("#msg_recipient_"+userid).val();
        if (typeof id_exists == "undefined") {
          jQuery("<li id=\"msg_recipient_"+userid+"\">").html(\'<input type="hidden" name="msg_recipients[]" value="\'+userid+\'" /><div>\'+item.value+\'</div><img alt="[-]" class="list_deluser" rel="\'+userid+\'" src="'.SITE_URL.'includes/images/delete.png" height="16" width="16" />\').appendTo("#recipients_list");
        }
      }
    }
  });
  $("#msg_bbc_recipient").autocomplete({
    maxItemsToShow: 10,
    matchInside: true,
    minChars: 2,
    loadingClass: "ucpLoading",
    url: "'.SITE_URL.'includes/ajax/getuserselection.php",
    useCache: false,
    onItemSelect: function(item) {
      var userid = 0;
      if(item.data.length) {
        userid = parseInt(item.data,10);
      }
      if(userid !== null && userid > 0) {
        if(!checkRecipientLimit()) return false;
        var id_exists = $("#msg_recipient_"+userid).val();
        if (typeof id_exists == "undefined") {
          jQuery("<li id=\"msg_recipient_"+userid+"\">").html(\'<input alt="[-]" type="hidden" name="msg_bcc[]" value="\'+userid+\'" /><div>\'+item.value+\'</div><img alt="[-]" class="list_deluser" rel="\'+userid+\'" src="'.SITE_URL.'includes/images/delete.png" height="16" width="16" />\').appendTo("#bcc_list");
        }
      }
    }
  });
';

    return (self::$_action != 'error');

  } //page_newmessage


  private static function _process_operation($discussions=false, $master_id=0)
  {
    global $DB;

    $action      = GetVar('ucp_action','','string',true,false);
    $selected    = GetVar('selected_items',false,'array',true,false);
    $option      = GetVar('msg_operation',false,'string',true,false);
    $master_id   = empty($master_id) ? 0 : (int)$master_id;
    $discussions = !empty($discussions);

    if(($action!='submit') || !$selected || !$option || ($master_id < 0)) return;

    switch($option)
    {
      case 'op_markselread':
      case 'op_markselunread': //SD370
        if(empty($discussions))
        {
          SDMsg::setMessagesRead(self::$_userid, $selected, ($option=='op_markselunread'));
          SDMsg::updateUserInboxCount(self::$_userid); //SD370
        }
        else
        if(!$master_id)
        {
          SDMsg::setDiscussionsRead(self::$_userid, $selected, ($option=='op_markselunread'));
        }
        break;
      case 'op_close':
        if($discussions)
        {
          SDMsg::setDiscussionsClosed(self::$_userid, 1, $selected);
        }
        break;
      case 'op_open':
        if($discussions)
        {
          SDMsg::setDiscussionsClosed(self::$_userid, 0, $selected);
        }
        break;
      case 'op_delete':
        if(!$discussions)
        {
          SDMsg::DeletePrivateMessages(self::$_userid, $selected);
        }
        break;
      case 'op_approve':
        if($discussions)
        {
          SDMsg::setDiscussionMessagesApproved($master_id, 1, $selected);
        }
        break;
      case 'op_unapprove':
        if($discussions)
        {
          SDMsg::setDiscussionMessagesApproved($master_id, 0, $selected);
        }
        break;
      case 'op_leave':
        $result = SDMsg::setDiscussionUserStatus(self::$_userid, SDMsg::DISCUSSIONS_USER_IGNORE, $master_id, $selected);
        if(!empty($result))
        {
          DisplayMessage(SDProfileConfig::$phrases['msg_leave_error'],true);
        }
        break;
    }

  } //_process_operation


  public static function page_viewdiscussion()
  {
    self::$_data = array();
    self::$_action = '';

    $master_id = Is_Valid_Number(GetVar('d', 0, 'whole_number'),0,1);

    return self::page_viewmessages(true, $master_id);

  } //page_viewmessage


  public static function page_viewdiscussions()
  {
    return self::page_viewmessages(true);
  }


  public static function page_viewsentmessages()
  {
    return self::page_viewmessages(false,0,true);
  }


  public static function page_viewmessages($discussions=false,$master_id=0,$viewsent=false)
  {
    // Note: this covers both in/outbox as well as discussions!
    global $DB, $SDCache, $categoryid, $userinfo;

    self::$_action = 'error';

    $discussions = !empty($discussions);
    $viewsent = !empty($viewsent);
    $master_id = !empty($master_id) && is_numeric($master_id) ? (int)$master_id : 0;

    $currentpage = Is_Valid_Number(GetVar('page', 1, 'whole_number'),1,1,999);
    $pagesize = Is_Valid_Number(GetVar('page_size', 5, 'whole_number'),5,1,100);
    if(!in_array($pagesize,array(5,10,15,30,60,100)))
    {
      $pagesize = 5;
    }

    $page_url = RewriteLink('index.php?categoryid='.$categoryid);
    $page_url .= (strpos($page_url,'?')===false?'?':'&amp;').'profile='.self::$_userid;
    $current_url = $page_url.'#do='.SDProfileConfig::$profile_pages[self::$_pageid].
                   ($master_id ? '&amp;d='.$master_id : '');


    // Configure available options depending on user status, i.e. if user is the
    // discussion starter, a site admin or just regular participant
    $options_flag = 0;
    $usergroup_options = SDProfileConfig::$usergroups_config[$userinfo['usergroupid']];
    $msg = SDProfileConfig::GetMsgObj(); // must have!

    $tmp_smarty = self::InitTmpl($page_url,$current_url);
    $tmp_smarty->assign('group_options', $usergroup_options);

    // Process operation for selected items
    self::_process_operation($discussions, $master_id);

    if($discussions)
    {
      // ######################################################################
      // ##### Discussions
      // ######################################################################

      //TODO: page params
      $sortby = $master_id ? 'last_msg_date' : 'msg_date';
      $order  = $master_id ? 'asc' : 'desc';

      SDMsg::setOption('order',  $order);
      SDMsg::setOption('sortby', $sortby);
      SDMsg::setOption('currentpage', $currentpage);
      SDMsg::setOption('pagesize', $pagesize);


      $messages_dis = SDMsg::getDiscussions(self::$_userid, $master_id, true);

      if($master_id && ($master_id > 0) && isset($messages_dis[$master_id]))
      {
        // ##### SINGLE DISCUSSION VIEW #####
        $tmp_smarty->assign('page_url', $page_url);
        $tmp_smarty->assign('master_id', $master_id);
        $participant_count = $messages_dis[$master_id]['users_count'];
        if(($participant_count) > 3)
        {
          $tmp_smarty->assign('msg_more_participants', str_replace('#x#',$participant_count-3,SDProfileConfig::$phrases['and_x_more']));
        }

        if(!empty($userinfo['adminaccess']) || (isset($messages_dis[$master_id]) && ($messages_dis[$master_id]['starter_id']==self::$_userid)))
        {
          $options_flag |= SDProfileConfig::OPTION_APPROVE
                           | SDProfileConfig::OPTION_UNAPPROVE
                           //| SDProfileConfig::OPTION_INVITE
                           //| SDProfileConfig::OPTION_UNINVITE
                           ;
        }
        else
        {
          $options_flag |= SDProfileConfig::OPTION_LEAVE;
        }

        // Check the user's last "read date" against the last message displayed on current page
        //SD343: avoid strict notice by making separate calls to array funcs
        $tmp = array_keys($messages_dis[$master_id]['messages']);
        $tmp = array_reverse($tmp);
        $last_msg_id = array_shift($tmp);
        unset($tmp);
        $last_page_date = (int)$messages_dis[$master_id]['messages'][$last_msg_id]['msg_date'];
        if($last_msg_id >= $messages_dis[$master_id]['last_msg_id'])
        {
          $last_page_date = TIME_NOW;
        }
        if($last_page_date > (int)$messages_dis[$master_id]['user_last_read'])
        {
          $messages_dis[$master_id]['user_last_read'] = $last_page_date;
        }

        SDMsg::setUserDiscussionReadDate(self::$_userid, $master_id, $last_page_date);
        $messages_count = $messages_dis[$master_id]['total_rowcount'];

        self::$_jscode .= '
  jQuery("#ucpForm a.imgdelete").click(function(e){
    e.preventDefault();
    var parent = jQuery(this).parent("li");
    var oldBorder = jQuery(parent).css("borderColor");
    jQuery(parent).css("borderColor", "red");
    if(ConfirmDeleteAttachment()) {
      var href = jQuery(this).attr("href");
      jQuery.ajax({
        async: true,
        cache: false,
        url: href,
        success: function(data){
          if((typeof (data) !== "undefined") && (data == "1")) {
            jQuery(parent).remove();
            alert("Ok!");
          } else {
            if(data.length > 0){ alert(data); }
          }
          return false;
        }
      });
    }
    jQuery(parent).css("borderColor", oldBorder);
    return false;
  });';

      }
      else
      {
        // ##### DISCUSSION LIST VIEW #####
        $options_flag |= SDProfileConfig::OPTION_MARKSELECTEDREAD | SDProfileConfig::OPTION_LEAVE;
        if(!empty($userinfo['adminaccess']))
        {
          $options_flag |= SDProfileConfig::OPTION_CLOSE
                           | SDProfileConfig::OPTION_OPEN
                           | SDProfileConfig::OPTION_APPROVE
                           | SDProfileConfig::OPTION_UNAPPROVE;
        }
        else
        {
          if(SDMsg::userIsDiscussionMaintainer(self::$_userid))
          {
            $options_flag |= SDProfileConfig::OPTION_CLOSE | SDProfileConfig::OPTION_OPEN;
          }
        }
        $messages_count = $msg->getMessageCounts(self::$_userid, SDMsg::MSG_DISCUSSIONS);
      }
      $options = SDProfileConfig::GetOptions($options_flag);

      $pagination_discussion = '';
      $pages = SDProfileConfig::GetProfilePages();
      if(($messages_count > $pagesize))
      {
        $p = new pagination;
        if(!empty($master_id) && ($master_id > 0))
        {
          $p->parameterName('do='.$pages['page_viewdiscussion'].'&amp;d='.$master_id.
                            '&amp;pagesize='.$pagesize.'&amp;sortby='.$sortby.'&amp;page');
        }
        else
        {
          $p->parameterName('do='.$pages['page_viewdiscussions'].'&amp;page');
        }
        $p->items($messages_count);
        $p->limit($pagesize);
        $p->currentPage($currentpage);
        $p->adjacents(3);
        $p->target($page_url);
        $pagination_discussion = $p->getOutput();
        unset($p);
      }

      foreach($messages_dis as $mid => $m)
      {
        $participant_count = $m['users_count'];
        if($participant_count > 3)
        {
          $messages_dis[$mid]['msg_more_participants'] = str_replace('#x#',$participant_count-3,SDProfileConfig::$phrases['and_x_more']);
        }
      }

      $tmp_smarty->assign('discussions',array(
        'options'       => $options,
        'messages'      => $messages_dis,
        'profile_link'  => ForumLink(4, 0),
        'count_total'   => (int)$messages_count,
        'pagination'    => $pagination_discussion,
        'current_page'  => (int)$currentpage,
        'page_size'     => (int)$pagesize,
        ));
        unset($pagination_discussion, $messages_dis);
    }
    else
    {
      // ######################################################################
      // ##### Private Messages
      // ######################################################################
      SDMsg::setOption('order',  'desc');
      SDMsg::setOption('sortby', 'msg_date');
      SDMsg::setOption('currentpage', $currentpage);
      SDMsg::setOption('pagesize', $pagesize);

      if($viewsent)
      {
        $messages = $msg->getOutboxMessages(self::$_userid, SDMsg::MSG_OUTBOX);
      }
      else
      {
        $messages = $msg->getInboxMessages(self::$_userid, SDMsg::MSG_INBOX);
      }
      $userdata = SDProfileConfig::GetUserdata();
      //SD370: use profile data if possible to save on SQLs
      if( (self::$_userid == $userinfo['userid']) &&
          !empty($userinfo['profile']['msg_in_count']) )
      {
        $messages_in_count  = $userinfo['profile']['msg_in_count'];
        $messages_out_count = $userinfo['profile']['msg_out_count'];
      }
      else
      {
        $messages_in_count  = SDMsg::getMessageCounts(self::$_userid, SDMsg::MSG_INBOX);
        $messages_out_count = SDMsg::getMessageCounts(self::$_userid, SDMsg::MSG_OUTBOX);
      }
      $messages_count = $viewsent ? $messages_out_count : $messages_in_count;

      if(($currentpage-1)*$pagesize > $messages_count)
      {
        $currentpage = ceil($messages_count / $pagesize);
      }
      $tmp_smarty->assign('total_messagecount', ($messages_in_count+$messages_out_count));

      $options_flag = SDProfileConfig::OPTION_DELETE /*
                      | SDProfileConfig::OPTION_FRIEND
                      | SDProfileConfig::OPTION_UNFRIEND
                      | SDProfileConfig::OPTION_BUDDY
                      | SDProfileConfig::OPTION_UNBUDDY */
                      | SDProfileConfig::OPTION_MARKSELECTEDUNREAD;
      if(!$viewsent)
      {
        $options_flag |= SDProfileConfig::OPTION_MARKSELECTEDREAD;
      }
      $options = SDProfileConfig::GetOptions($options_flag);

      $pagination = '';
      if(($messages_count > $pagesize))
      {
        $pages = SDProfileConfig::GetProfilePages();
        $p = new pagination;
        $p->parameterName('page');
        $p->items($messages_count);
        $p->limit($pagesize);
        $p->currentPage($currentpage);
        $p->adjacents(3);
        $p->target($page_url.'&amp;do='.($viewsent ? $pages['page_viewsentmessages'] : $pages['page_viewmessages']).
                   '&amp;page_size='.$pagesize);
        $pagination = $p->getOutput();
        unset($p);
      }

      $tmp_smarty->assign('folder',array(
        'options'       => $options,
        'messages'      => $messages,
        'count'         => $messages_count,
        'pagination'    => $pagination,
        'current_page'  => $currentpage,
        'page_size'     => $pagesize,
        'title'         => ($viewsent ? SDProfileConfig::$phrases['lbl_outbox_title'] : SDProfileConfig::$phrases['lbl_inbox_title']),
        'quota'         => ($viewsent ? $usergroup_options['msg_keep_limit'] : $usergroup_options['msg_inbox_limit']),
        ));
    }

    $tmpl_name = 'ucp_'.self::$_pageid.'.tpl';
    self::$_pageid = ''; // Content was displayed already, so reset pageid now

    if(SD_Smarty::Is_Smarty3())
    {
      //SD360: use SD_Smarty to display (otherwise templates aren't taken from DB!)
      SD_Smarty::display(11, $tmpl_name,$tmp_smarty);
      self::$_action = 'do';
    }
    else
    {
      if(SD_Smarty::template_exists($tmpl_name))
      {
        $tmp_smarty->display($tmpl_name);
        self::$_action = 'do';
      }
      else
      if(SD_Smarty::template_exists('defaults/'.$tmpl_name))
      {
        $tmp_smarty->display('defaults/'.$tmpl_name);
        self::$_action = 'do';
      }
    }

    unset($tmp_smarty,$messages, $pagination);

    return (self::$_action != 'error');

  } //page_viewmessages


  public static function setUserId($userid)
  {
    self::$_userid = $userid;
  }

  public static function ProcessPage(&$action, &$pageid, &$jscode)
  {
    global $userinfo;

    self::$_pageid = $pageid;
    //self::$errortitle = '';
    //self::$errors = false;

    if(empty($userinfo['loggedin']) || empty($userinfo['userid']))
    {
      self::$errors  = 'No Guests allowed!';
      self::$_action = 'error';
      self::$_jscode = '';
      return false;
    }

    self::$_action = $action;
    self::$_jscode = $jscode;

    if(method_exists(__CLASS__, $pageid))
    {
      $result = call_user_func(array(__CLASS__, $pageid));
      $action = self::$_action;
      $pageid = (self::$_pageid == 'page_createmessage' ? 'page_viewmessage' : self::$_pageid);
      $jscode = self::$_jscode;
    }
    else
    {
      self::$errors = 'Sorry, invalid page!'; //SD370: should not happen!
    }

    return (self::$errors === false);
  }

} //class SDUserProfilePageHandler
} //do not remove
