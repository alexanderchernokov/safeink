<?php
if(!defined('IN_PRGM')) exit();

define('FORUM_TOKEN', 'forum_token');
define('FORUM_URL_TOKEN', PrintSecureUrlToken(FORUM_TOKEN));
define('FORUM_REPLY_CLASS', 'quick-reply');
define('FORUM_CACHE_FORUMS', 'sd-forum-forums');
define('FORUM_CACHE_SEO', 'sd-forum-seo');
define('FORUM_INVALID_COLOR', '#FFC0C0');

include_once(SD_INCLUDE_PATH.'class_cache.php');

// ############################################################################
// SUBDREAMER FORUM CONFIGURATION CLASS
// ############################################################################

if(!class_exists('SDForumConfig'))
{
class SDForumConfig
{
  public $categoryid          = 0; //SD351
  public $plugin_id           = 0;
  public $plugin_phrases_arr  = array();
  public $plugin_settings_arr = array();

  public $error_arr    = array();
  public $IsSiteAdmin  = false;
  public $IsAdmin      = false;
  public $IsModerator  = false;
  public $AllowSubmit  = false;
  public $search_tag   = false; //SD343
  public $search_text  = false;
  public $InitStatus   = false;

  public $action       = '';
  public $seo_cache    = '';
  public $forums_cache = '';
  public $seo_enabled  = false;

  public $forum_id     = 0;
  public $topic_id     = 0;
  public $post_id      = 0;

  public $forum_arr    = array();
  public $topic_arr    = array();
  public $post_arr     = array();

  public $GroupCheck   = '';

  public $attach_path           = '';
  public $attach_path_exists    = false;
  public $attach_path_ok        = false;
  public $img_prefix            = 'forum_image_'; // for admin settings
  public $is_sd_users           = true;
  public $paging_adjacents      = 3;
  public $current_page          = '';
  public $seo_detected          = false;
  public $user_post_edit        = false;
  public $user_forum_post       = false;
  public $user_likes_perm       = false;
  public $user_dislikes_perm    = false;
  public $user_report_perm      = false; //SD351
  public $user_report_perm_msg  = false; //SD351
  public $censor_posts          = false;
  public $mod_post_limit        = 0;
  public $mod_time_limit        = 0;

  public function SDForumConfig()
  {
    $this->InitStatus = false;
    $this->plugin_id = GetPluginID('Forum');
    if(!empty($this->plugin_id) && ($this->plugin_id > 17) && ($this->plugin_id <= 999999))
    {
      global $mainsettings_modrewrite, $usersystem;
      // Load the plugin settings and phrases
      $this->plugin_phrases_arr = GetLanguage($this->plugin_id);
      $this->plugin_settings_arr = GetPluginSettings($this->plugin_id);

      $this->plugin_settings_arr['user_edit_timeout'] = Is_Valid_Number($this->plugin_settings_arr['user_edit_timeout'],0,0,99999999);

      if(!isset($this->plugin_settings_arr['date_display']) ||
         ($this->plugin_settings_arr['date_display'] < 1))
      {
        $this->plugin_settings_arr['date_display'] = 0;
      }
      $this->is_sd_users = ($usersystem['name'] == 'Subdreamer');
      $this->seo_enabled = !empty($this->plugin_settings_arr['enable_seo_forums']) &&
                           !empty($mainsettings_modrewrite);
    }
  } //SDForumConfig

  // ##########################################################################

  public function InitFrontpage($checkCache=true)
  {
    // Init() must be called separately after extra properties are set
    if(!$this->plugin_id) return false;

    global $DB, $categoryid, $mainsettings_modrewrite, $SDCache,
           $userinfo, $usersystem, $pages_md_arr, $pluginids,
           $mainsettings_tag_results_page,
           $mainsettings_search_results_page;

    // Try to find the first category which has Forum in it:
    $this->categoryid = 1;
    if(!empty($categoryid) && isset($pluginids) && in_array($this->plugin_id, $pluginids))
    {
      // Good, Forum is in current list of page plugins on frontpage
      $this->categoryid = (int)$categoryid;
    }
    else
    if($forum_page = $DB->query_first('SELECT categoryid FROM {pagesort}'.
                                      " WHERE pluginid = '%d'".
                                      ' AND categoryid NOT IN (%d,%d) LIMIT 1',
                                      $this->plugin_id,
                                      $mainsettings_tag_results_page,
                                      $mainsettings_search_results_page))
    {
      // At least it was found somewhere...
      $this->categoryid = (int)$forum_page['categoryid'];
      unset($forum_page);
    }
    else
    if(!empty($categoryid))
    {
      // We'll have to take take the current page then
      $this->categoryid = $categoryid;
    }
    $this->current_page = RewriteLink('index.php?categoryid='.$this->categoryid);

    //SD342: check attachments path
    $this->attach_path = '';
    $this->attach_path_ok = $this->attach_path_exists = false;
    if(isset($this->plugin_settings_arr['attachments_upload_path']) &&
       strlen(trim($this->plugin_settings_arr['attachments_upload_path'])))
    {
      $this->attach_path = 'plugins/forum/'.trim($this->plugin_settings_arr['attachments_upload_path']);
      $this->attach_path_exists = @is_dir(ROOT_PATH.$this->attach_path);
      $this->attach_path_ok = $this->attach_path_exists && @is_writable(ROOT_PATH.$this->attach_path);
    }

    $this->IsSiteAdmin = !empty($userinfo['adminaccess']) && !empty($userinfo['loggedin']) && !empty($userinfo['userid']);
    $this->IsAdmin     = (!empty($userinfo['pluginadminids']) && @in_array($this->plugin_id, $userinfo['pluginadminids']));
    $this->IsModerator = (!empty($userinfo['pluginmoderateids']) && @in_array($this->plugin_id, $userinfo['pluginmoderateids'])); //SD360
    $this->AllowSubmit = (!empty($userinfo['pluginsubmitids']) && @in_array($this->plugin_id, $userinfo['pluginsubmitids']));
    $this->user_post_edit  = false;
    $this->user_forum_post = false;
    $this->user_likes_perm = false;
    $this->user_dislikes_perm = false;
    $this->mod_post_limit = empty($this->plugin_settings_arr['limit_moderated_posts'])?0:
                            Is_Valid_Number($this->plugin_settings_arr['limit_moderated_posts'],0,1,100);
    $this->mod_time_limit = empty($this->plugin_settings_arr['limit_moderated_minutes'])?0:
                            Is_Valid_Number($this->plugin_settings_arr['limit_moderated_posts'],0,1,1440);

    $this->censor_posts = !$this->IsSiteAdmin && !empty($this->plugin_settings_arr['censor_posts']); //SD343

    // SD313: if NOT a site admin, build an extra WHERE condition for checking view permission
    $this->GroupCheck = '';
    if(!$this->IsSiteAdmin)
    {
      // the usergroup id MUST be enclosed in pipes!
      $this->GroupCheck = " AND ((IFNULL(access_view,'')='') OR (access_view like '%|".$userinfo['usergroupid']."|%'))";
    }

    // Load or fill cached forums and SEO titles for forums
    $this->seo_cache = $this->forums_cache = false;
    $cache_on = !empty($SDCache) && ($SDCache instanceof SDCache) && $SDCache->IsActive();
    if($cache_on)
    {
      if(($getcache = $SDCache->read_var(FORUM_CACHE_FORUMS, 'forumcache')) !== false)
      {
        $this->forums_cache = array('forums'     => array(),
                                    'categories' => array(),
                                    'parents'    => array());
        if(isset($getcache['forums'])) $this->forums_cache['forums'] = (array)$getcache['forums'];
        if(isset($getcache['categories'])) $this->forums_cache['categories'] = (array)$getcache['categories'];
        if(isset($getcache['parents'])) $this->forums_cache['parents'] = (array)$getcache['parents'];
      }
      if(($getcache = $SDCache->read_var(FORUM_CACHE_SEO, 'seo')) !== false)
      {
        $this->seo_cache = array('by_id'    => array(),
                                 'by_title' => array());
        if(isset($getcache['by_id'])) $this->seo_cache['by_id'] = (array)$getcache['by_id'];
        if(isset($getcache['by_title'])) $this->seo_cache['by_title'] = (array)$getcache['by_title'];
      }
    }

    if(!empty($checkCache)) //SD351
    if(empty($this->seo_cache['by_id']) || empty($this->seo_cache['by_title']) || !$this->forums_cache)
    {
      $parent_id = 0;
      $forums_tbl = PRGM_TABLE_PREFIX.'p_forum_forums';
      $posts_tbl  = PRGM_TABLE_PREFIX.'p_forum_posts';
      $topics_tbl = PRGM_TABLE_PREFIX.'p_forum_topics';
      $this->forums_cache = array('forums'     => array(),
                                  'categories' => array(),
                                  'parents'    => array());
      $this->seo_cache = array('by_id'    => array(),
                               'by_title' => array());
      if($getdata = $DB->query(
        "SELECT concat(
            lpad(IF(ff.parent_forum_id <> $parent_id, fp.display_order, ff.display_order),4,'0'),
            lpad(IF(ff.parent_forum_id <> $parent_id, fp.forum_id, ff.forum_id),6,'0')) sortorder,
          (SELECT COUNT(*) FROM $forums_tbl ff2 WHERE ff2.parent_forum_id = ff.forum_id) subforums,
          ff.*, fp.forum_id parent_id, posts_tbl.user_id poster_id, posts_tbl.post post_text,
          ft.title topic_title
          FROM $forums_tbl ff
          LEFT JOIN $forums_tbl fp on fp.forum_id = ff.parent_forum_id
          LEFT JOIN $topics_tbl ft on ft.topic_id = ff.last_topic_id AND ft.moderated = 0
          LEFT JOIN $posts_tbl posts_tbl on posts_tbl.post_id = ff.last_post_id AND posts_tbl.moderated = 0
          ORDER BY sortorder, subforums DESC, ff.display_order"))
      {
        while($entry = $DB->fetch_array($getdata,null,MYSQL_ASSOC))
        {
          $fid = (int)$entry['forum_id'];
          if(isset($entry['seo_title']) && strlen($entry['seo_title']))
          {
            $this->seo_cache['by_id'][$fid] = $entry['seo_title'];
            $this->seo_cache['by_title'][$entry['seo_title']] = $fid;
          }
          else
          if(strlen($entry['title']))
          {
            $title = ConvertNewsTitleToUrl($entry['title'],$fid,1,true,false);
            $this->seo_cache['by_id'][$fid] = $title;
            $this->seo_cache['by_title'][$title] = $fid;
            $entry['seo_title'] = $title;
          }

          $entry['post_text'] = sd_substr($entry['post_text'],0,200);
          $entry['last_topic_seo_title'] = false;
          $entry['last_topic_seo_url'] = false;
          if(!empty($entry['last_topic_title']))
          {
            $post_id = empty($entry['last_post_id']) ? '' :
                       ('post_id='.(int)$entry['last_post_id'].'#'.(int)$entry['last_post_id']);
            $res = $this->RewriteTopicLink($entry['last_topic_id'],$entry['last_topic_title'],1,true);
            if($this->seo_enabled)
            {
              $entry['last_topic_seo_title'] = $res['title'];
              $entry['last_topic_seo_url'] = $res['link'].'?'.$post_id;
            }
            else
            {
              $entry['last_topic_seo_title'] = '';
              $entry['last_topic_seo_url'] = $res['link'].'&amp;'.$post_id;
            }
          }

          ksort($entry);
          $this->forums_cache['forums'][$fid] = $entry;

          $pid = empty($entry['parent_forum_id']) ? 0 : (int)$entry['parent_forum_id'];
          if(!empty($entry['is_category']) || !$pid)
          {
            $this->forums_cache['categories'][] = $fid;
          }
          if($pid > 0)
          {
            $this->forums_cache['parents'][$pid][] = $fid;
          }
        }

        if(count($this->seo_cache['by_id'])) ksort($this->seo_cache['by_id']);
        if(count($this->seo_cache['by_title'])) ksort($this->seo_cache['by_title']);
        if($cache_on)
        {
          $SDCache->write_var(FORUM_CACHE_FORUMS, 'forumcache', $this->forums_cache);
          $SDCache->write_var(FORUM_CACHE_SEO, 'seo', $this->seo_cache);
        }
        unset($getdata,$entry,$title);
      }
    }

    // Fetch forum- and/or topic id's now
    $this->forum_id = Is_Valid_Number(GetVar('forum_id', 0, 'whole_number'),0,1);
    $this->topic_id = Is_Valid_Number(GetVar('topic_id', 0, 'whole_number'),0,1);

    //SD343 SEO support
    $this->seo_detected = false;
    if($this->seo_enabled && !$this->forum_id && !$this->topic_id)
    {
      global $sd_variable_arr, $uri;

      $count = count($sd_variable_arr);
      if(($count>1) && preg_match('#(.*)[\-](f|t)([0-9]*)\.?$#i',$sd_variable_arr[$count-1],$matches))
      {
        if((count($matches)==4) && ctype_digit($matches[3]))
        {
          if($matches[2]=='t')
          {
            $this->topic_id = (int)$matches[3];
            $this->seo_detected = true;
          }
          else
          if($matches[2]=='f')
          {
            $this->forum_id = (int)$matches[3];
            $this->seo_detected = true;
          }
        }
      }
      else
      // Legacy support for old search engine entries (from vB times):
      if(preg_match('#showthread\.php\?t=([0-9]*)$#i',$uri,$matches))
      {
        if((count($matches)==2) && ctype_digit($matches[1]))
        {
          $this->topic_id = (int)$matches[1];
          $this->seo_detected = true;
        }
      }
    }

    $result = true;
    if($this->topic_id && ($this->topic_id > 0))
    {
      $result = $this->SetTopicAndForum($this->topic_id, ($this->seo_detected && isset($matches[1])?(string)$matches[1]:false));
    }
    else
    if($this->forum_id && ($this->forum_id > 0))
    {
      $result = $this->SetForum($this->forum_id, ($this->seo_detected && isset($matches[1])?(string)$matches[1]:false));
    }

    $this->InitStatus = $result;
    return $result;

  } //InitFrontpage

  function ClearForumsCache()
  {
    global $SDCache;
    if(!empty($SDCache) && ($SDCache instanceof SDCache) && $SDCache->IsActive())
    {
      $SDCache->delete_cacheid(FORUM_CACHE_FORUMS);
    }
  }

  function SaveForumsCache()
  {
    global $SDCache;
    if(!empty($SDCache) && ($SDCache instanceof SDCache) && $SDCache->IsActive())
    {
      $SDCache->write_var(FORUM_CACHE_FORUMS, 'forumcache', $this->forums_cache);
    }
  }

  // ##########################################################################

  function SetTopicAndForum($topic_id, $uri_seo_title='')
  {
    global $DB;

    $p_fld = '';
    $p_sql = '';
    if($post_id = Is_Valid_Number(GetVar('post_id', 0, 'whole_number', false, true),0,1,99999999))
    {
      $p_fld = ', fp.post';
      $p_sql = ' INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_posts fp ON fp.topic_id = ft.topic_id AND fp.post_id = '.$post_id;
    }

    $DB->result_type = MYSQL_ASSOC;
    if($topic_arr = $DB->query_first(
       'SELECT ft.*, ff.title forum_title, ff.seo_title forum_seo_title,'.
       " ft.post_user_id first_poster_id, IFNULL(fp2.username,'') first_post_username".
       $p_fld.
       ' FROM '.PRGM_TABLE_PREFIX.'p_forum_topics ft '.$p_sql.
       ' INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_forums ff ON ft.forum_id = ff.forum_id'.
       ' INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_posts fp2 ON (fp2.post_id = ft.first_post_id)'.
       ' WHERE ff.is_category = 0 AND ft.topic_id = %d'.
       ($this->IsSiteAdmin ? '' : ' AND ff.online = 1 ').
       ($this->IsSiteAdmin || $this->IsAdmin ? '' : ' AND (IFNULL(ft.moderated,0) = 0)').
       ' LIMIT 1', $topic_id))
    {
      if($post_id)
      {
        $this->post_id = (int)$post_id;
        $this->post_arr = array('post_id' => $this->post_id,
                                'post'    => $topic_arr['post']);
      }
      $post_action = GetVar('post_action',  '', 'string');
      $att_id = GetVar('attachment_id', 0, 'whole_number');
      if($this->seo_enabled && ($post_action!='attdel') && ($att_id < 1))
      {
        //SD343: check for mis-named SEO param in URI
        $res = ConvertNewsTitleToUrl(sd_unhtmlspecialchars($topic_arr['title']),$topic_arr['topic_id'],0,true,null);
        if($res)
        {
          if(!empty($uri_seo_title) && ($res != $uri_seo_title))
          {
            $res = $this->RewriteTopicLink($topic_arr['topic_id'],sd_unhtmlspecialchars($topic_arr['title']),1,true);
            StopLoadingPage('','',301,$res['link']);
          }
          else
          if(!isset($_GET['forum_action']) && !isset($_POST['forum_action']) &&
             (isset($_GET['topic_id']) || isset($_GET['forum_id'])))
          {
            // Redirect non-seo URL to seo-enabled link
            $res = $this->RewriteTopicLink($topic_arr['topic_id'],sd_unhtmlspecialchars($topic_arr['title']),1,true);
            if($post_id)
            {
              $res['link'] .= '?post_id='.$post_id.'#'.$post_id;
            }
            StopLoadingPage('','',301,$res['link']);
          }
        }
      }

      // topic exists, does forum?
      if($this->SetForum($topic_arr['forum_id']))
      {
        // forum exists, all is good!
        $this->topic_id  = (int)$topic_arr['topic_id'];
        $this->topic_arr = $topic_arr;
        if(isset($_GET['forum_action']) && ($_GET['forum_action']=='edit_post') && SD_IS_BOT) //SD343
        {
          if(!headers_sent()) header("HTTP/1.0 404 Not Found");
          $this->error_arr[] = '<strong>'.$this->plugin_phrases_arr['err_no_edit_post_access'].'</strong>';
          return false;
        }
        return true;
      }
      else
      {
        return false;
      }
    }

    // topic not found
    $this->topic_id  = 0;
    $this->topic_arr = array();
    $this->forum_id  = 0;
    $this->forum_arr = array();

    if(!headers_sent()) header("HTTP/1.0 404 Not Found");
    $this->error_arr[] = '<strong>'.$this->plugin_phrases_arr['err_topic_not_found'] . '</strong>';
    return false;

  } //SetTopicAndForum

  // ##########################################################################

  function SetForum($forum_id, $uri_seo_title='')
  {
    global $DB, $mainsettings, $userinfo;

    // SD313: added GroupCheck for usergroup view permission
    $DB->result_type = MYSQL_ASSOC;
    if($forum_arr = $DB->query_first('SELECT * FROM '.PRGM_TABLE_PREFIX.'p_forum_forums ff'.
                                      ' WHERE ff.forum_id = %d AND ff.is_category = 0'.
                                      ($this->IsSiteAdmin ? '' : ' AND ff.online = 1 ').
                                      $this->GroupCheck . ' LIMIT 1', $forum_id))
    {
      $this->forum_id  = (int)$forum_arr['forum_id'];

      //SD370: redirect non-SEO forum link to SEO forum link
      if($this->seo_enabled)
      {
        $res = ConvertNewsTitleToUrl(sd_unhtmlspecialchars($forum_arr['title']),$this->forum_id,0,true,null);
        if($res)
        {
          if( (!empty($uri_seo_title) && ($res != $uri_seo_title)) ||
              (!isset($_GET['forum_action']) && !isset($_POST['forum_action']) && isset($_GET['forum_id'])) )
          {
            $res = $this->RewriteForumLink($this->forum_id);
            StopLoadingPage('','',301,$res);
          }
        }
      }

      $this->forum_arr = $forum_arr;
      $this->forum_arr['moderated']   = !empty($this->forum_arr['moderated'])   ? sd_ConvertStrToArray($this->forum_arr['moderated'],'|'):array(); //SD343
      $this->forum_arr['access_post'] = !empty($this->forum_arr['access_post']) ? sd_ConvertStrToArray($this->forum_arr['access_post'],'|'):array(); //SD343
      $this->forum_arr['access_view'] = !empty($this->forum_arr['access_view']) ? sd_ConvertStrToArray($this->forum_arr['access_view'],'|'):array(); //SD343
      $this->forum_arr['post_edit']   = !empty($this->forum_arr['post_edit'])   ? sd_ConvertStrToArray($this->forum_arr['post_edit'],'|'):array(); //SD343

      //SD343: set user-level permission flags - incl. secondary usergroups
      $canAdmin  = !empty($userinfo['pluginadminids']) && in_array($this->plugin_id, $userinfo['pluginadminids']);
      $canView   = empty($this->forum_arr['access_view']) ||
                   (!empty($userinfo['usergroupids']) && @array_intersect($userinfo['usergroupids'], $this->forum_arr['access_view']));
      $canSubmit = (!empty($userinfo['pluginsubmitids']) && in_array($this->plugin_id, $userinfo['pluginsubmitids'])) &&
                   (empty($this->forum_arr['access_post']) ||
                    (!empty($userinfo['usergroupids']) &&
                     @array_intersect($userinfo['usergroupids'], $this->forum_arr['access_post'])));

      $this->IsAdmin        = $canAdmin && $canView;
      $this->AllowSubmit    = $this->IsSiteAdmin ||
                              ($canView && ($canAdmin || $canSubmit));
      $this->user_post_edit = $this->IsSiteAdmin ||
                              ($canView && ($canAdmin || $canSubmit) &&
                               (empty($this->forum_arr['post_edit']) ||
                                array_intersect($userinfo['usergroupids'], $this->forum_arr['post_edit'])));

      $like_groups = (!empty($this->plugin_settings_arr['enable_like_this_post']) ?
                      sd_ConvertStrToArray($this->plugin_settings_arr['enable_like_this_post'],',') :
                      array());
      $this->user_likes_perm  = $this->AllowSubmit && !empty($like_groups) &&
                                !empty($userinfo['usergroupids']) &&
                                @array_intersect($userinfo['usergroupids'], $like_groups);
      $this->user_dislikes_perm = $this->user_likes_perm && !empty($this->plugin_settings_arr['enable_dislikes_for_posts']);

      //SD351: reporting of posts by user
      $this->user_report_perm = false;
      $this->user_report_perm_msg = false;
      if(!empty($mainsettings['reporting_enabled']))
      {
        $this->user_report_perm_msg = empty($userinfo['report_message'])?'':$userinfo['report_message'];
        $report_groups =
          (!empty($this->plugin_settings_arr['post_reporting_permissions']) ?
           sd_ConvertStrToArray($this->plugin_settings_arr['post_reporting_permissions'],',') :
           array());
        $this->user_report_perm =
          !empty($userinfo['loggedin']) &&
          ( $this->IsSiteAdmin || $this->IsModerator ||
            ($this->AllowSubmit &&
             !empty($report_groups) && !empty($userinfo['usergroupids']) &&
             @array_intersect($userinfo['usergroupids'], $report_groups)) );
      }

      return true;
    }

    // forum not found
    if(!headers_sent()) header("HTTP/1.0 404 Not Found");
    $this->error_arr[] = $this->plugin_phrases_arr['err_forum_not_found'];
    $this->forum_id    = 0;
    $this->topic_id    = 0;
    $this->forum_arr   = array();
    return false;

  } //SetForum

  // ##########################################################################

  public static function UsergroupsModerated($ug_arr, $mod_arr)
  {
    if(!isset($ug_arr) || !isset($mod_arr)) return '';
    if(!is_array($mod_arr) || is_string($mod_arr))
      $mod_arr = sd_ConvertStrToArray($mod_arr,'|');
    if(!is_array($ug_arr))
    {
      if(is_numeric($ug_arr))
        $ug_arr = array((int)$ug_arr);
      else
      if(is_string($ug_arr))
        $ug_arr = sd_ConvertStrToArray($ug_arr,',');
    }

    return (count(array_intersect($ug_arr,$mod_arr)) > 0);
  } //UsergroupsModerated

  // ##########################################################################

  public static function GetBBCodeExtract($str,$maxlen=150,$trail='...')
  {
    global $bbcode;
    if(!isset($bbcode) || !is_object($bbcode) || !isset($str) || !strlen($str)) return '';
    $str = trim(unhtmlspecialchars(str_replace(array("\r\n",'<br>','<br />'),array(' ',' ',' '), $str)));
    $bbcode->SetPlainMode(true);
    $bbcode->SetLimit((int)$maxlen);
    $bbcode->SetLimitTail($trail);
    $str = $bbcode->Parse($str);
    $bbcode->SetPlainMode(false);
    $bbcode->SetLimit(0);
    $str = str_replace(array('"','&quot;'),array('',''), $str); //SD351 remove quotes
    $str = ' title="'.htmlentities(strip_alltags($str)).'" ';
    return $str;
  } //GetBBCodeExtract


  // ##########################################################################

  public function GetForumDate($timestamp)
  {
    return Ago($timestamp,
               $this->plugin_settings_arr['date_display'],
               $this->plugin_settings_arr['date_format'],
               $this->plugin_phrases_arr['posted_ago_before'],
               $this->plugin_phrases_arr['posted_ago_after']);
  } //GetForumDate


  // ##########################################################################

  public function IsUserBlacklisted() //SD343: combined blacklist check
  {
    $blacklisted = false;
    // SFS and blocklist checking for user's email and IP
    if(!$this->IsSiteAdmin)
    {
      global $userinfo;
      if(!empty($this->plugin_settings_arr['enable_sfs_antispam']) &&
         function_exists('sd_sfs_is_spam'))

      {
        $blacklisted = sd_sfs_is_spam((empty($userinfo['email'])?null:$userinfo['email']),USERIP);
      }

      if(!$blacklisted && !empty($this->plugin_settings_arr['enable_blocklist_checks']) &&
         function_exists('sd_reputation_check'))
      {
        $blacklisted = sd_reputation_check(USERIP, $this->plugin_id);
      }
    }
    return ($blacklisted!==false);

  } //IsUserBlacklisted

  // ##########################################################################

  public function GetTags($topic_id = 0, $display_mode = 0, $limit = 10) //SD343
  {
    require_once(SD_INCLUDE_PATH.'class_sd_tags.php');
    $result = false;
    $tconf = array(
      'chk_ugroups' => 0,
      'pluginid'    => $this->plugin_id,
      'objectid'    => (empty($topic_id)?0:(int)$topic_id),
      'tagtype'     => 0,
      'ref_id'      => -1,
      'allowed_id'  => -1,
    );
    if(($tags = SD_Tags::GetPluginTagsAsArray($tconf)) !== false)
    {
      global $categoryid;
      $limit = Is_Valid_Number($limit,10,1,50);
      $tags = array_unique(array_values($tags));
      $tcount = count($tags);
      $idx = 0;
      $result = '';
      foreach($tags as $tag)
      {
        $result .= '<a class="forum-tag" href="'.RewriteLink('index.php?categoryid='.$categoryid.'&tag='.$tag).'">'.$tag.'</a>';
        $idx++;
        if($idx > $limit) break; # obey limit!
        if(empty($display_mode) && ($idx < $tcount))
          $result .= ', ';
        else
          $result .= ' ';
      }
    }
    return $result;

  } //GetTags

  public function GetPostByPostedId()
  {
    global $DB;
    if($post_id = Is_Valid_Number(GetVar('post_id', 0, 'whole_number'),0,1,99999999))
    {
      $DB->result_type = MYSQL_ASSOC;
      if($post_arr = $DB->query_first(
        'SELECT fp.post_id, fp.topic_id, fp.user_id, fp.username, fp.user_likes,'.
        ' ft.title topic_title, ft.forum_id'.
        ' FROM {p_forum_posts} fp'.
        ' INNER JOIN {p_forum_topics} ft ON ft.topic_id = fp.topic_id'.
        ' INNER JOIN {p_forum_forums} ff ON ff.forum_id = ft.forum_id'.
        ' WHERE fp.post_id = %d AND ff.is_category = 0 AND ft.topic_id = %d'.
        ($this->IsSiteAdmin || $this->IsAdmin ? '' :
          ' AND IFNULL(ft.moderated,0) = 0 AND (IFNULL(fp.moderated,0) = 0)').
        ($this->IsSiteAdmin ? '' : ' AND ff.online = 1 ').
        $this->GroupCheck,
        $post_id, $this->topic_id))
      {
        return $post_arr;
      }
    }

    return false;

  } //GetPostByPostedId


  // ###########################################################################
  // DIS/LIKE A POST
  // SD343: single-vote "thumbs up/down" for a single post; logged-in users only
  // ###########################################################################

  public function DoLikePost($doLike=true) // false == dislike
  {
    global $DB, $sdlanguage, $userinfo;

    // SD313: security check against spam/bot submissions
    if(!CheckFormToken(FORUM_TOKEN, false) || !$this->user_likes_perm || empty($userinfo['loggedin']) ||
       (!Is_Ajax_Request() && (empty($this->forum_id) || empty($this->topic_id))) )
    {
      if(Is_Ajax_Request()) { return -1; };
      $this->RedirectPageDefault('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
      return -1;
    }
    if(!$this->user_dislikes_perm && ($doLike===SD_LIKED_TYPE_POST_NO))
    {
      return -3;
    }

    $post_arr = $this->GetPostByPostedId();
    if(($post_arr === false) || empty($post_arr['post_id']))
    {
      $DB->result_type = MYSQL_BOTH;
      echo $this->plugin_phrases_arr['err_message_not_found'];
      return -4;
    }

    require_once(SD_INCLUDE_PATH.'class_sd_likes.php');

    $post_id = (int)$post_arr['post_id'];
    $entries = SD_Likes::DoLikeElement($this->plugin_id, $post_id,
                                       ($doLike===SD_LIKED_TYPE_POST_REMOVE?SD_LIKED_TYPE_POST_REMOVE:($doLike?SD_LIKED_TYPE_POST:SD_LIKED_TYPE_POST_NO)),
                                       $userinfo['userid'], $userinfo['username'],
                                       (int)$post_arr['user_id']);
    if(isset($entries) && is_array($entries))
    {
      $DB->query("UPDATE {p_forum_posts} SET user_likes = '%s', likes_count = %d, dislikes_count = %d WHERE post_id = %d",
                 $DB->escape_string(serialize($entries)), $entries['total'], $entries['total_no'], $post_id);
    }

    if(Is_Ajax_Request())
    {
      echo $this->plugin_phrases_arr[$doLike===SD_LIKED_TYPE_POST_REMOVE ? 'message_like_removed':
                                     ($doLike ? 'message_post_like' : 'message_post_dislike')];
      return true;
    }
    $link = $this->RewriteTopicLink($post_arr['topic_id'],$post_arr['topic_title']).
            ($this->seo_enabled?'?':'&amp;').'post_id='.$post_id.'#p'.$post_id;
    RedirectFrontPage($link, $this->plugin_phrases_arr[
                      $doLike===SD_LIKED_TYPE_POST_REMOVE ? 'message_like_removed':
                      ($doLike ? 'message_post_like' : 'message_post_dislike')]);

  } //DoLikePost


  // ###########################################################################
  // REPORT A POST CONFIRMED
  // SD351: user confirmed to report a specific post
  // ###########################################################################

  public function DoReportPost()
  {
    global $DB, $sdlanguage, $userinfo, $usersystem;

    // SD313: security check against spam/bot submissions
    if(!CheckFormToken(FORUM_TOKEN, false) || empty($userinfo['loggedin']))
    {
      $this->RedirectPageDefault('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
      return -1;
    }
    // User has basic reporting permissions?
    if(empty($userinfo['userid']) || !$this->user_report_perm)
    {
      $this->RedirectPageDefault($this->plugin_phrases_arr['err_no_options_access'], true);
      return false;
    }

    // Valid topic selected?
    if(!$this->topic_arr['topic_id'])
    {
      $this->RedirectPageDefault($this->plugin_phrases_arr['err_invalid_topic_id'], true);
      return false;
    }

    if(!($this->IsSiteAdmin || $this->IsAdmin) ||
       !empty($userinfo['require_vvc']))
    {
      if(!CaptchaIsValid('amihuman'))
      {
        $this->RedirectPageDefault($sdlanguage['captcha_not_valid'], true);
        return false;
      }
    }

    // Does post exist and viewable by reporting user?
    // E.g. moderated posts/topics or offline forums are not valid.
    $post_arr = $this->GetPostByPostedId();
    if(($post_arr === false) || empty($post_arr['post_id']))
    {
      $DB->result_type = MYSQL_BOTH;
      echo $this->plugin_phrases_arr['err_message_not_found'];
      return -4;
    }
    $post_id = (int)$post_arr['post_id'];

    require_once(SD_INCLUDE_PATH.'class_sd_reports.php');
    SD_Reports::Init();

    $confirmed = GetVar('report_confirm', false, 'string',true,false)?1:0;
    if($reasonid = Is_Valid_Number(GetVar('report_reason', 0, 'whole_number',true,false),0,1,999999))
    {
      $reason = SD_Reports::GetCachedReason($this->plugin_id, $reasonid, true);
    }
    if(empty($reason))
    {
      $this->RedirectPageDefault($sdlanguage['err_invalid_report'], true);
      return false;
    }
    if(empty($confirmed))
    {
      $this->RedirectPageDefault($sdlanguage['err_report_unconfirmed'], true);
      return false;
    }

    global $mainsettings;

    // Post already reported?
    $reportcount = 1;
    if(false !== ($report = SD_Reports::GetReportedItem($this->plugin_id, $this->topic_arr['topic_id'], $post_id)))
    {
      $reportid = (int)$report['reportid'];
      $reportcount = empty($report['reportcount'])?0:(1+$report['reportcount']);
      SD_Reports::AddToReport($reportid, $reasonid);
      $msg = $this->plugin_phrases_arr['post_already_reported'];
    }
    else
    {
      // Add report for post
      global $categoryid;

      $user_msg  = '';
      if($this->user_report_perm_msg)
      {
        $user_msg = GetVar('user_msg', '', 'string', true, false);
      }

      $reportid = SD_Reports::CreateReport($this->plugin_id, $post_arr['topic_id'],
                              $post_id, $reasonid, $categoryid,
                              $post_arr['user_id'], $post_arr['username'],
                              $user_msg);
      if($reportid === false)
      {
        $this->RedirectPageDefault($sdlanguage['err_invalid_report'], true);
        return false;
      }
      $msg = $this->plugin_phrases_arr['reported_post'];
    }

    // Moderate post if amount of reports reached
    if(!empty($mainsettings['reports_moderate_items']) &&
       ($reportcount >= $mainsettings['reports_moderate_items']))
    {
      $DB->query('UPDATE {p_forum_posts} SET moderated = 1 WHERE post_id = %d',$post_id);
    }

    SD_Reports::SendReport($reportid, $this->plugin_id,
                           'Forum post reported by [username], [date]');

    RedirectFrontPage($this->RewriteTopicLink($post_arr['topic_id'],$post_arr['topic_title']), $msg);

  } //DoReportPost


  // ##########################################################################
  // GET LIKES FOR A POST
  // ##########################################################################

  public function GetPostLikes($doLikes=true)
  {
    global $DB, $sdlanguage, $userinfo;

    // SD313: security check against spam/bot submissions
    if(!CheckFormToken(FORUM_TOKEN, false) ||
       (!Is_Ajax_Request() && (empty($this->forum_id) || empty($this->topic_id))) )
    {
      if(Is_Ajax_Request()) return;
      $this->RedirectPageDefault('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
      return;
    }
    if(empty($this->plugin_settings_arr['display_likes_results'])) return;

    $post_arr = $this->GetPostByPostedId();
    if(($post_arr === false) || empty($post_arr['post_id']))
    {
      $DB->result_type = MYSQL_BOTH;
      return $this->plugin_phrases_arr['err_message_not_found'];
    }

    if(!empty($post_arr['user_likes']) && ($likes = @unserialize($post_arr['user_likes'])))
    {
      require_once(SD_INCLUDE_PATH.'class_sd_likes.php');
      return SD_Likes::GetLikesList($likes,$doLikes);
    }
    return '';

  } //GetPostLikes


  // ##########################################################################
  // GET LIKES LINKS FOR A SPECIFIC POST AND CURRENTLY LOGGED-IN USER
  // ##########################################################################

  public function GetPostLikesLinks($post_arr=null)
  {
    global $DB, $categoryid, $sdlanguage, $userinfo;

    // SD313: security check against spam/bot submissions
    if(!CheckFormToken(FORUM_TOKEN, false) ||
       (!Is_Ajax_Request() && (empty($this->forum_id) || empty($this->topic_id))) )
    {
      if(Is_Ajax_Request()) return;
      $this->RedirectPageDefault('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
      return;
    }
    if(empty($this->plugin_settings_arr['display_likes_results'])) return;

    if(!Is_Ajax_Request())
    {
      if(empty($post_arr) || !is_array($post_arr)) return '';
    }
    else
    {
      $post_arr = $this->GetPostByPostedId();
    }
    if(!isset($post_arr) || empty($post_arr['post_id']))
    {
      $DB->result_type = MYSQL_BOTH;
      return $this->plugin_phrases_arr['err_message_not_found'];
    }

    //SD343: display "like this post" results
    $likes_txt = '';
    if(!empty($post_arr['user_likes']) && ($likes = @unserialize($post_arr['user_likes'])))
    {
      require_once(SD_INCLUDE_PATH.'class_sd_likes.php');
      $likes_txt  = SD_Likes::GetLikesList($likes,true);
      $likes_txt .= ' '.SD_Likes::GetLikesList($likes,false);
      $likes_txt = trim($likes_txt);
    }

    if(!empty($this->plugin_settings_arr['enable_like_this_post']) &&
       !empty($this->forum_arr['enable_likes']) &&
       ($this->IsSiteAdmin || $this->IsAdmin || (!empty($userinfo['loggedin']) && $this->AllowSubmit)))
    {
      $out1 = $out2 = '';
      $showRemove = false;
      $liked = $DB->query_first('SELECT 1 postliked FROM {users_likes}'.
                                " WHERE pluginid = %d AND objectid = %d ".
                                " AND userid = %d AND liked_type = '%s' LIMIT 1",
                               $this->plugin_id, $post_arr['post_id'],
                               $userinfo['userid'], SD_LIKED_TYPE_POST);
      if(empty($liked['postliked']))
      {
        $out1 = ' <a class="like-link" rel="nofollow" href="' .
        RewriteLink('index.php?categoryid='.$categoryid.'&forum_action=like_post&topic_id='.
          $post_arr['topic_id'].'&post_id='.$post_arr['post_id'].FORUM_URL_TOKEN).'">'.
        $sdlanguage['like_post'].'</a>';
      }
      else $showRemove = 1;

      $disliked = $DB->query_first('SELECT 1 postdisliked FROM {users_likes}'.
                                " WHERE pluginid = %d AND objectid = %d ".
                                " AND userid = %d AND liked_type = '%s' LIMIT 1",
                                $this->plugin_id, $post_arr['post_id'],
                                $userinfo['userid'], SD_LIKED_TYPE_POST_NO);
      if(empty($disliked['postdisliked']))
      {
        $out2 = ' <a class="dislike-link" rel="nofollow" href="' .
        RewriteLink('index.php?categoryid='.$categoryid.'&forum_action=dislike_post&topic_id=' . $post_arr['topic_id'] .
        '&post_id=' . $post_arr['post_id'].FORUM_URL_TOKEN).'">'.
        $sdlanguage['dislike_post'].'</a>';
      }
      else $showRemove = 2;

      if(($showRemove==1) || ($showRemove==2))
      {
        $out = ' <a class="remove-like-link" rel="nofollow" href="' .
        RewriteLink('index.php?categoryid='.$categoryid.'&forum_action=remove_like&topic_id=' . $topic_arr['topic_id'] .
        '&post_id=' . $post_arr['post_id'].FORUM_URL_TOKEN).'">'.
        $sdlanguage[$showRemove==1?'remove_like':'remove_dislike'].'</a>';
      }
      if($out1 || $out2 || strlen($likes_txt))
        if(!Is_Ajax_Request())
          echo '<div class="likes-box">'.$likes_txt.(!$out1||!$out2?$out:'').$out1.$out2.'</div>';
        else
          echo $likes_txt.(!$out1||!$out2?$out:'').$out1.$out2;
    }
    else
    if(strlen($likes_txt))
    {
      if(!Is_Ajax_Request())
        echo '<div class="likes-box">'.$likes_txt.'</div>';
      else
        echo $likes_txt;
    }

  } //GetPostLikesLinks


  // ##########################################################################
  // GET THE CELL CONTENT FOR CURRENT TOPIC (FORUM VIEW)
  // ##########################################################################

  public function GetTopicTitleCell($newTitle='')
  {
    global $DB, $userinfo;

    if(!Is_Ajax_Request() || empty($this->topic_id)) return '';

    $result = '';
    $GLOBALS['sd_ignore_watchdog'] = true;
    $DB->ignore_error = true;

    SDUserCache::$ForumAvatarAvailable  = function_exists('ForumAvatar');
    SDUserCache::$img_path              = 'plugins/forum/images/';
    SDUserCache::$bbcode_detect_links   = !empty($this->plugin_settings_arr['auto_detect_links']);
    SDUserCache::$lbl_offline           = $this->plugin_phrases_arr['user_offline'];
    SDUserCache::$lbl_online            = $this->plugin_phrases_arr['user_online'];
    SDUserCache::$lbl_open_profile_page = $this->plugin_phrases_arr['open_your_profile_page'];
    SDUserCache::$lbl_view_member_page  = $this->plugin_phrases_arr['view_member_page'];
    SDUserCache::$show_avatars          = !empty($this->plugin_settings_arr['display_avatar']) &&
                                          (!isset($userinfo['profile']['user_view_avatars']) ||
                                           !empty($userinfo['profile']['user_view_avatars']));

    $first_poster = SDUserCache::CacheUser($this->topic_arr['first_poster_id'],
                                           $this->topic_arr['first_post_username'],
                                           true, true);
    $link = $this->RewriteTopicLink($this->topic_id, $this->topic_arr['title']);
    $moderated_class = empty($this->topic_arr['moderated']) ? '' : ' topic-moderated';

    if(SDUserCache::$show_avatars && $first_poster['avatar'])
      $result .= $first_poster['topic_avatar'];
    else
      $result .= '<div class="topic-avatar">'.
                 GetDefaultAvatarImage(SDUserCache::$avatar_width,
                                       SDUserCache::$avatar_height).
                 '</div> ';

    echo (empty($this->topic_arr['open'])? '<img alt="locked" src="'.SDUserCache::$img_path.'lock.png" width="16" height="16" title="'.htmlspecialchars($this->plugin_phrases_arr['topic_locked']).'" /> ' : '');
    echo (!empty($this->topic_arr['sticky'])? '<img alt="sticky" src="'.SDUserCache::$img_path.'sticky.png" width="16" height="16" title="'.htmlspecialchars($this->plugin_phrases_arr['sticky']).'" /> ' : '');
    echo (!empty($this->topic_arr['moderated'])? '<img alt="!" src="'.SDUserCache::$img_path.'attention.png" width="16" height="16" /> ' : '');

    //SD343: fetch prefix (if present and uncensored) and display it
    $prefix = $DB->query_first('SELECT t2.* FROM '.PRGM_TABLE_PREFIX.'tags t1'.
                               ' INNER JOIN '.PRGM_TABLE_PREFIX.'tags t2 ON t2.tagid = t1.tag_ref_id'.
                               ' WHERE t1.pluginid IN (0, %d) AND t1.objectid = %d AND t1.censored = 0'.
                               ' AND t2.pluginid IN (0, %d) AND t2.tagtype = 2 AND t2.censored = 0 LIMIT 1',
                               $this->plugin_id, $this->topic_arr['topic_id'], $this->plugin_id);
    if(!empty($prefix['tagid']))
    {
      echo str_replace(array('[tagname]'), array($prefix['tag']), $prefix['html_prefix']);
    }
    unset($prefix);

    $result .= '
      <a class="topic-link'.$moderated_class.'" href="'.$link.'" rel="'.$this->topic_id.'">' .
      trim(strlen($newTitle)?$newTitle:$this->topic_arr['title']).'</a>';
    if($this->IsAdmin || $this->IsSiteAdmin)
    {
      $result .= '<span class="editable" style="display:none;float:right;"></span>';
    }
    $result .= '<br />';

    if(!empty($first_poster['forum']))
    {
      $result .= $first_poster['forum'];
    }
    else
    {
      $result .= $this->topic_arr['first_post_username'];
    }
    $GLOBALS['sd_ignore_watchdog'] = false;

    return $result;

  } //GetTopicTitleCell


  // ##########################################################################
  // REMOVE A USERS DIS-/LIKE FOR A GIVEN POST
  // ##########################################################################

  public function RemovePostLikes($doLikes=true)
  {
    global $DB, $sdlanguage, $userinfo;

    // SD313: security check against spam/bot submissions
    if(!CheckFormToken(FORUM_TOKEN, false) ||
       (!Is_Ajax_Request() && (empty($this->forum_id) || empty($this->topic_id))) )
    {
      if(Is_Ajax_Request())
      {
        echo '<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />';
        return;
      }
      $this->RedirectPageDefault('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
      return;
    }

    $post_arr = $this->GetPostByPostedId();
    if(($post_arr === false) || empty($post_arr['post_id']))
    {
      $DB->result_type = MYSQL_BOTH;
      return $this->plugin_phrases_arr['err_message_not_found'];
    }

    if(!empty($post_arr['user_likes']) && ($likes = @unserialize($post_arr['user_likes'])))
    {
      require_once(SD_INCLUDE_PATH.'class_sd_likes.php');
      return SD_Likes::RemoveUserLikesForObject($this->plugin_id,$post_id,$userinfo['userid']);
    }
    return '';

  } //RemovePostLikes


  // ##########################################################################
  // RedirectPageDefault
  // ##########################################################################

  public function RedirectPageDefault($message='', $IsError=false)
  {
    if($this->topic_id)
    {
      RedirectFrontPage($this->RewriteTopicLink($this->topic_id,(isset($this->topic_arr['title'])?$this->topic_arr['title']:0)), $message, 3, $IsError);
    }
    else
    if($this->forum_id)
    {
      RedirectFrontPage($this->RewriteForumLink($this->forum_id), $message, 3, $IsError);
    }
    else
    {
      RedirectFrontPage($this->current_page, $message, 3, $IsError);
    }
  } //RedirectPageDefault


  // ###########################################################################
  // REWRITE FORUM LINK
  // ###########################################################################

  public function RewriteForumLink($forum_id, $url_title_only=false, $page=1)
  {
    global $mainsettings_modrewrite, $mainsettings_url_extension;

    if($this->seo_enabled && !empty($forum_id))
    {
      $title = isset($this->seo_cache['by_id']) ? $this->seo_cache['by_id'][$forum_id] : '';
      if(!empty($url_title_only))
      {
        return $title;
      }
      $link = $this->current_page;
      $title .= '-f'.(int)$forum_id;
      if(!empty($page) && ((int)$page > 1)) $title .= '?page='.(int)$page;
      $link = str_replace($mainsettings_url_extension,
                          '/'.$title.$mainsettings_url_extension,
                          $link);

    }
    else
    {
      global $categoryid;
      $link = RewriteLink('index.php?categoryid='.$categoryid.'&forum_id='.$forum_id);
    }
    return $link;

  } //RewriteForumLink


  // ###########################################################################
  // REWRITE TOPIC LINK
  // ###########################################################################

  public function RewriteTopicLink($topic_id, $topic_title=null, $page=1, $as_array=false)
  {
    global $mainsettings_url_extension;

    if($this->seo_enabled)
    {
      $link = $this->current_page;
      if(empty($as_array))
      {
        if(empty($topic_title) && ($topic_id == $this->topic_id) && isset($this->topic_arr['title']))
        {
          $topic_title = $this->topic_arr['title'];
        }
        $title = ConvertNewsTitleToUrl($topic_title,$topic_id,(int)$page,false,'-t');
      }
      else
      {
        $title = ConvertNewsTitleToUrl($topic_title,$topic_id,0,false,'-t');
      }
      $link = str_replace($mainsettings_url_extension, '/'.$title, $link);
      if(!empty($as_array))
      {
        return array('link' => $link, 'title' => $title);
      }
    }
    else
    {
      $link = $this->current_page.(strpos($this->current_page,'?')===false?'?':'&amp;').'topic_id='.$topic_id;
      if(!empty($as_array))
      {
        return array('link' => $link, 'title' => $topic_title);
      }
    }
    return $link;

  } //RewriteTopicLink


  // ###########################################################################
  // REWRITE POST LINK
  // ###########################################################################

  public function RewritePostLink($topic_id, $topic_title='', $post_id=null)
  {
    $link = $this->RewriteTopicLink($topic_id, $topic_title);
    if(!empty($post_id))
    {
      $link .= ($this->seo_enabled?'?':'&amp;').
               'post_id='.(int)$post_id.'#p'.(int)$post_id;
    }
    return $link;

  } //RewritePostLink

} // class SDForumConfig

} // DO NOT REMOVE!
