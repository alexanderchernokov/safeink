<?php
//SD342 - TODO: "LoadUser": with Forum Integration there is no usergroup defined in $tmp!

if(!defined('IN_PRGM')) return;
if(!defined('SD_PROFILE_INCL'))
{
define('SD_PROFILE_INCL', true);
define('SD_P11_USERCP_LINK', 'plugins/p11_mi_usercp/usercp.php');

// ##########################################################################
// Subscriptions
// ##########################################################################

class SDSubscription
{
  protected $id             = 0;
  protected $userid         = 0;
  public  $pluginid         = 0;
  public  $objectid         = 0;
  public  $type             = '';
  public  $categoryid       = 0;
  public  $last_read        = 0;
  public  $last_email       = 0;
  public  $expiration_date  = 0;
  public  $email_notify     = 0;
  public  $email_template_name = ''; //SD342: not used yet
  public  $data_title       = '';
  public  $data_subtitle    = '';
  public  $data_link        = '';
  public  $data_sublink     = '';
  public  $data_date        = 0;
  public  $data_pagename    = '';
  public  $data_userid      = 0;
  public  $data_username    = '';

  public function __construct($userid,$pluginid=null,$objectid=null,$type=null,$categoryid=0)
  {
    $this->userid   = empty($userid)   ? 0 : (int)$userid;
    $this->pluginid = empty($pluginid) ? 0 : (int)$pluginid;
    $this->objectid = empty($objectid) ? 0 : (int)$objectid;
    $this->type     = empty($type)     ? '': (string)$type;
    $this->categoryid = empty($categoryid) ? 0 : (int)$categoryid;
    $this->GetSubscription();
  }

  public function GetSubscription()
  {
    $this->id = 0;
    $this->last_read       = 0;
    $this->last_email      = 0;
    $this->expiration_date = 0;
    $this->email_notify    = 0;
    $this->data_title      = '';
    $this->data_subtitle   = '';
    $this->data_link       = '';
    $this->data_sublink    = '';
    $this->data_date       = 0;
    $this->data_pagename  = '';
    $this->data_userid     = 0;
    $this->data_username   = '';

    if(empty($this->userid) || empty($this->pluginid)) return false;

    global $DB;
    if($result = $DB->query_first('SELECT * FROM '.PRGM_TABLE_PREFIX.'users_subscribe
                                   WHERE userid = %d AND pluginid = %d'.
                                  (empty($this->objectid)?'':' AND objectid = '.(int)$this->objectid).
                                  (empty($this->type)?'':" AND IFNULL(type,'') = '".$DB->escape_string($this->type)."'"),
                                  $this->userid, $this->pluginid))
    {
      $this->id = (int)$result['id'];
      $this->categoryid = (int)$result['categoryid'];
      $this->last_read = (int)$result['last_read'];
      $this->last_email = (int)$result['last_email'];
      $this->expiration_date = (int)$result['expiration_date'];
      $this->email_notify = (int)$result['email_notify'];
    }
    return !empty($this->id);

  } //GetSubscription

  public function GetUserSubscriptions()
  {
    if(empty($this->userid) || empty($this->pluginid)) return false;

    global $DB;

    $result = $DB->query('SELECT * FROM '.PRGM_TABLE_PREFIX.'users_subscribe
                          WHERE userid = %d AND pluginid = %d'.
                          (empty($this->objectid)?'':' AND objectid = '.(int)$this->objectid).
                          (empty($this->type)?'':" AND IFNULL(type,'') = '".$DB->escape_string($this->type)."'"),
                          $this->userid, $this->pluginid);

    return empty($result)?false:$result;

  } //GetSubscription

  public function GetSubscribedStatus($pluginid, $objectid=null, $type='')
  {
    if(empty($this->id)) return false;

    global $DB;
    $result = $DB->query_first('SELECT 1 FROM '.PRGM_TABLE_PREFIX.'users_subscribe
                                WHERE userid = %d AND pluginid = %d'.
                                (empty($objectid)?'':' AND objectid = '.(int)$objectid).
                                (empty($type)?'':" AND IFNULL(type,'') = '".$DB->escape_string($type)."'"),
                                $this->userid, $pluginid);

    return !empty($result[0]);

  } //IsSubscribed

  public function IsSubscribed()
  {
    return !empty($this->id);
  } //IsSubscribed

  public function UpdateRead()
  {
    if(!$this->IsSubscribed()) return false;

    global $DB;
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'users_subscribe SET last_read = %d
                WHERE userid = %d AND pluginid = %d'.
                (empty($this->objectid)?'':' AND objectid = '.(int)$this->objectid).
                (empty($this->type)?'':" AND IFNULL(type,'') = '".$DB->escape_string($this->type)."'"),
                TIME_NOW, $this->userid, $this->pluginid);
  } //UpdateRead

  public function GetContent()
  {
    $this->data_title      = '';
    $this->data_subtitle   = '';
    $this->data_link       = '';
    $this->data_sublink    = '';
    $this->data_date       = 0;
    $this->data_userid     = 0;
    $this->data_username   = '';
    if(empty($this->pluginid) || empty($this->objectid) || empty($this->categoryid)) return false;

    global $DB, $bbcode, $mainsettings_allow_bbcode, $mainsettings_modrewrite, $mainsettings_url_extension,
           $pages_md_arr, $userinfo;
    $allow_bbcode = isset($bbcode) && ($bbcode instanceof BBCode) && !empty($mainsettings_allow_bbcode);

    $result = false;
    if($this->pluginid==GetPluginID('Forum'))
    {
      if(!empty($this->categoryid) && ($c = GetPluginCategory($this->pluginid,$this->categoryid)))
      {
        $params = $subparams = ''; $forumid = 0;
        if($this->type=='forum')
        {
          if($topic = $DB->query_first('SELECT ff.forum_id, ff.last_topic_id, ff.title forum_title,
                      ff.last_topic_title topic_title, ff.last_post_date,
                      ft.post_user_id, ft.post_username, ft.last_post_id, fp.user_id
                      FROM {p_forum_forums} ff
                      INNER JOIN {p_forum_topics} ft ON ft.forum_id = ff.forum_id AND ft.topic_id = ff.last_topic_id
                      INNER JOIN {p_forum_posts} fp ON fp.post_id = ft.last_post_id
                      WHERE ff.forum_id = %d LIMIT 1',$this->objectid))
          {
            $params = '&forum_id='.$this->objectid;
            $subparams = '&topic_id='.$topic['last_topic_id'].'&post_id='.$topic['last_post_id'].'#'.$topic['last_post_id'];
            $this->data_date     = (int)$topic['last_post_date'];
            $this->data_title    = unhtmlspecialchars($topic['forum_title']);
            $this->data_subtitle = $topic['topic_title'];
            $this->data_userid   = empty($topic['user_id'])?0:(int)$topic['user_id'];
            $this->data_username = empty($topic['post_username'])?'':(string)$topic['post_username'];
            $result = true;
          }
        }
        else
        if($this->type=='topic')
        {
          $params = '&topic_id='.$this->objectid;
          if($topic = $DB->query_first('SELECT ft.forum_id, ft.last_post_id, ff.title forum_title,
                      ft.title topic_title, ft.last_post_date, fp.user_id
                      FROM {p_forum_topics} ft
                      INNER JOIN {p_forum_forums} ff ON ff.forum_id = ft.forum_id
                      INNER JOIN {p_forum_posts} fp ON fp.post_id = ft.last_post_id
                      WHERE ft.topic_id = %d LIMIT 1',$this->objectid))
          {
            $params= '&topic_id='.$this->objectid.'&post_id='.$topic['last_post_id'].'#'.$topic['last_post_id'];
            $subparams = '&forum_id='.$topic['forum_id'];
            $this->data_date     = (int)$topic['last_post_date'];
            $this->data_title    = $topic['topic_title'];
            $this->data_subtitle = unhtmlspecialchars($topic['forum_title']);
            $this->data_userid   = empty($topic['user_id'])?0:(int)$topic['user_id'];
            $this->data_username = '';
            $result = true;
          }
        }
        if($result)
        {
          $this->data_link = RewriteLink('index.php?categoryid='.$c.$params);
          $this->data_sublink = RewriteLink('index.php?categoryid='.$c.$subparams);
        }
      }
    } //Forum/Topics
    else
    if(($this->type=='comments') && (($this->pluginid==2) || ($this->pluginid > 500)))
    {
      // Check if plugin is an article plugin clone
      if($this->pluginid >= 500)
      {
        $p = $DB->query_first('SELECT * FROM {plugins} WHERE pluginid = %d',$this->pluginid);
      }
      if(($this->pluginid==2) || (!empty($p['base_plugin']) && ($p['base_plugin']=='Articles')))
      {
        $article = $DB->query_first(
          'SELECT categoryid, title, seo_title, settings, datecreated, author, org_author_id, categoryid FROM {p'.$this->pluginid.'_news}'.
          ' WHERE (articleid = %d) AND (settings & 2) '.
          ' AND ((IFNULL(datestart,0) = 0) OR (datestart < ' . TIME_NOW . '))'.
          ' AND ((IFNULL(dateend,0)= 0) OR (dateend > ' . TIME_NOW . '))',
          $this->objectid);
        if(empty($article)) return false;
        if(empty($userinfo['categoryviewids']) || !in_array($article['categoryid'],$userinfo['categoryviewids'])) return false;

        $comments = $DB->query_first(
          'SELECT commentid, username, userid, date, comment FROM {comments}
          WHERE pluginid = %d AND objectid = %d
          '.(empty($userinfo['adminaccess']) && empty($userinfo['commentaccess'])?'AND approved = 1':'').'
          ORDER BY commentid DESC LIMIT 1',
          $this->pluginid, $this->objectid);

        //$this->data_date  = (int)$article['datecreated'];
        $this->data_title = $article['title'];
        if(!empty($comments[0]))
        {
          $this->data_date     = $comments['date'];
          $this->data_subtitle = $comments['comment'];
          if($allow_bbcode)
          {
            $this->data_subtitle = $bbcode->Parse($this->data_subtitle);
          }
          $this->data_userid   = $comments['userid'];
          $this->data_username = $comments['username'];
        }
        $link = 'index.php?categoryid='.$article['categoryid'];
        if($mainsettings_modrewrite && strlen($article['seo_title']))
        {
          $this->data_link = RewriteLink($link);
          $this->data_link = preg_replace('#'.SD_QUOTED_URL_EXT.'$#', '/' . $article['seo_title'] .
                                          $mainsettings_url_extension, $this->data_link);
        }
        else
        {
          $this->data_link = RewriteLink($link.'&p'.$this->pluginid.'_articleid=' . $this->objectid);
        }
        $this->data_sublink = $this->data_link.'#comments';
        $this->data_pagename = !empty($pages_md_arr[$article['categoryid']]['title']) ? $pages_md_arr[$article['categoryid']]['title'] : $pages_md_arr[$article['categoryid']]['name'];
        $this->data_pagename = '<a href="'.RewriteLink($link).'">'.$this->data_pagename.'</a>';
        $result = true;
      }
    } //Article-Comments
    if(!empty($this->data_userid) && ($tmp = sd_GetUserrow($this->data_userid,array('user_screen_name'))))
    {
      if(!empty($tmp['user_screen_name']))
      {
        $this->data_username = $tmp['user_screen_name'];
      }
      elseif(empty($this->data_username))
      {
        $this->data_username = $tmp['username'];
      }
    }

    return $result;
  } //GetContent

  public function SendNotifications()
  {
    global $DB, $categoryid, $mainsettings, $pages_md_arr, $plugin_names, $sdlanguage,
           $userinfo, $usersystem;

    $senderemail = SDProfileConfig::$settings['pm_email_address'];
    $senderemail = !empty($senderemail)?$senderemail:$mainsettings['technicalemail'];
    if(!strlen(trim($senderemail)) || empty($this->pluginid) || empty($this->type))
    {
      return false;
    }

    if($getsubs = $DB->query('SELECT * FROM '.PRGM_TABLE_PREFIX.'users_subscribe
                              WHERE (pluginid = %d) AND (email_notify > 0) AND (userid <> %d)
                              AND ((last_email = 0) OR (last_email < last_read))'.
                              (empty($this->objectid)?'':' AND objectid = '.(int)$this->objectid).
                              (empty($this->type)?'':" AND IFNULL(type,'') = '".$DB->escape_string($this->type)."'"),
                              $this->pluginid, $this->userid))
    {
      global $mainsettings_websitetitle_original;

      $this->categoryid = $categoryid;
      $date       = DisplayDate(TIME_NOW);
      $pagename   = !empty($pages_md_arr[$categoryid]['title']) ? $pages_md_arr[$categoryid]['title'] : $pages_md_arr[$categoryid]['name'];
      $pagename   = '<a href="'.RewriteLink('index.php?categoryid='.$this->categoryid).'">'.$pagename.'</a>';
      $pluginname = $plugin_names[$this->pluginid];
      $sendername = '';

      $subject2 = SDProfileConfig::$settings['subscription_email_subject'];
      $subject2 = str_replace(array('[sitename]',
                                    '[siteurl]',
                                    '[date]',
                                    '[type]',
                                    '[pluginname]',
                                    '[pagename]'),
                              array($mainsettings_websitetitle_original,
                                    SITE_URL,
                                    $date,
                                    $this->type,
                                    $pluginname,
                                    $pagename), $subject2);

      $this->GetContent();
      $link = '<a href="'.$this->data_link.'">'.$this->data_title.'</a>';

      $message2 = SDProfileConfig::$settings['subscription_email_body'];
      $message2 = str_replace(array('[sitename]',
                                    '[siteurl]',
                                    '[date]',
                                    '[type]',
                                    '[pluginname]',
                                    '[pagename]',
                                    '[pagelink]',
                                    '[title]'
                                    ),
                              array($mainsettings_websitetitle_original,
                                    SITE_URL,
                                    $date,
                                    $this->type,
                                    $pluginname,
                                    $pagename,
                                    $link,
                                    $this->data_title), $message2);

      while($entry = $DB->fetch_array($getsubs,null,MYSQL_ASSOC))
      {
        if(!empty($entry['email_notify']) && ($entry['email_notify']==1)) // 1 == instant notification
        {
          $user = sd_GetUserrow($entry['userid']);
          // do some checks if user is to receive notifications
          if((($usersystem['name']=='Subdreamer') && empty($user['activated'])) ||
             empty(SDProfileConfig::$usergroups_config[$user['usergroupid']]['enable_subscriptions']) ||
             !empty($user['banned']))
          {
            continue;
          }
          $profilepagelink = '<a href="'.ForumLink(2, $entry['userid']).'">'.SDProfileConfig::$phrases['lbl_user_profile_page'].'</a>';

          $subject = str_replace('[username]', $user['username'], $subject2);
          $message = str_replace(array('[username]', '[profilepage]'), array($user['username'],$profilepagelink), $message2);

          if(SendEmail($user['email'], $subject, $message, $sendername, $senderemail, null, null, true))
          {
            $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'users_subscribe SET last_email = %d
                        WHERE userid = %d AND pluginid = %d AND email_notify > 0'.
                        (empty($this->objectid)?'':' AND objectid = '.(int)$this->objectid).
                        (empty($this->type)?'':" AND IFNULL(type,'') = '".$DB->escape_string($this->type)."'"),
                        TIME_NOW, $entry['userid'], $this->pluginid);
          }
        }
      } //while
    }
  } //SendNotifications

  public function Subscribe($pluginid, $objectid=null, $type='', $category_source=null)
  {
    if(empty($this->userid)) return false;

    global $DB;
    $category_source = isset($category_source)?Is_Valid_Number($category_source,$this->categoryid,1):$this->categoryid;
    return $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX."users_subscribe (userid,pluginid,objectid,type,categoryid,email_notify) VALUES (%d,%d,%d,'%s',%d,1)",
                      $this->userid, $pluginid, (empty($objectid)?0:(int)$objectid),
                      (empty($type)?'':$DB->escape_string($type)), $category_source);

  } //Subscribe

  public function Unsubscribe($pluginid, $objectid=null, $type='')
  {
    if(empty($this->userid)) return false;

    global $DB;
    return $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX."users_subscribe
                       WHERE userid = %d AND pluginid = %d AND objectid = %d AND type= '%s'",
                       $this->userid, $pluginid, (empty($objectid)?0:(int)$objectid),
                       (empty($type)?'':$DB->escape_string($type)));

  } //Unsubscribe
} //SDSubscription

// ############################################################################
// PROFILE BASE CLASS
// ############################################################################

class SDProfileConfig
{
  const NOTIFY_BY_EMAIL         = 1;
  const NOTIFY_BY_PM            = 2;
  const NOTIFY_NEW_PM           = 4;
  const NOTIFY_NEW_DISCUSSION   = 8;
  const NOTIFY_NEW_DISC_MSG     = 16;
  const NOTIFY_NEW_VISITOR_MSG  = 32;
  const NOTIFY_NEW_COMMENT      = 64;
  const NOTIFY_NEW_POST         = 128;
  const NOTIFY_DISC_REQUEST     = 1024;
  const NOTIFY_INVITE_USER      = 2048;
  const NOTIFY_INVITE_ACCEPTED  = 4096;
  const NOTIFY_INVITE_DECLINED  = 8192;

  const OPTION_MARKALLREAD      = 1;
  const OPTION_MARKDISCREAD     = 2;
  const OPTION_MARKSELECTEDREAD = 4;
  const OPTION_APPROVE          = 8;
  const OPTION_UNAPPROVE        = 16;
  const OPTION_MOVE             = 32;
  const OPTION_INVITE           = 64;
  const OPTION_UNINVITE         = 128;
  const OPTION_LEAVE            = 256;
  const OPTION_CLOSE            = 512;
  const OPTION_OPEN             = 1024;
  const OPTION_IGNORE           = 8192;
  const OPTION_UNIGNORE         = 16384;
  const OPTION_DELETE           = 32768;
  const OPTION_FRIEND           = 65536;
  const OPTION_UNFRIEND         = 131072;
  const OPTION_BUDDY            = 262144;
  const OPTION_UNBUDDY          = 524288;
  const OPTION_MARKSELECTEDUNREAD = 1048576; //SD370

  private static $_fieldtypes         = array('bbcode','date','password','text','textarea','select','url','yesno',
                                              'ext_aim','ext_fb','ext_icq','ext_googletalk','ext_msnm','ext_skype','ext_twitter','ext_yim');
  private static $_initdone           = false;
  private static $_msg_obj            = false;
  private static $_all_options        = array();
  private static $_userdata           = array();
  private static $_page_url           = '';

  public static $usersystem           = false;
  public static $pluginid             = 0;
  public static $phrases              = array();
  public static $settings             = array();
  public static $form_prefix          = 'ucp_';
  public static $p10_phrases          = array();
  public static $p12_phrases          = array();
  public static $p12_settings         = array();
  public static $profile_fields       = array();
  public static $public_fieldnames    = array();
  public static $public_fieldnameids  = array();
  public static $profile_groups       = array();
  public static $profile_group_fields = array();
  public static $group_start_html     = '';
  public static $group_end_html       = '';
  public static $profile_pages        = array();
  public static $usergroups_config    = array();
  public static $fields_loaded        = false;
  public static $last_error           = false;
  public static $basic_profile        = false;
  public static $_ajax                = false;

  public static function init($pluginid=11)
  {
    global $DB, $SDCache, $database, $usersystem;

    if((self::$_initdone) || defined('UPGRADING_PRGM')) return true;

    self::$basic_profile = defined('UCP_BASIC') && UCP_BASIC; //SD342

    if(empty($pluginid) || !is_numeric($pluginid)) $pluginid = 11;
    self::$pluginid = (int)$pluginid;
    self::$usersystem = &$usersystem;

    // Load plugin language phrases and settings
    self::$phrases  = GetLanguage(self::$pluginid);
    self::$settings = GetPluginSettings(self::$pluginid);

    self::$group_start_html = '
<form id="ucpForm" class="uniForm" enctype="multipart/form-data" method="post" action="$current_url">
<input type="hidden" name="profile" value="$profile" />
<input type="hidden" name="ucp_action" id="ucp_action" value="submit" />
<input type="hidden" name="ucp_page" value="$page" />
<input type="hidden" name="submit" value="1" />
$token_element
<div class="ucp-groupheader">%s</div>
<fieldset class="inlineLabels">';
    if(!self::$basic_profile)
    {
      self::$group_start_html .= "\r\n".'<div style="float:right;padding:0;margin-right:8px">'.self::$phrases['lbl_public_option'].'</div>';
    }
    self::$group_end_html   = '
</fieldset>
';

    // Get extra settings/phrases from Registration plugin
    // Note: this assumes the default plugin with id 12!
    self::$p10_phrases  = GetLanguage(10);
    self::$p12_phrases  = GetLanguage(12);
    self::$p12_settings = GetPluginSettings(12);

    // This is the MOST IMPORTANT profile page configuration array,
    // containing all the major page elements available!
    self::$profile_pages = array(
      'page_newmessage'        => ConvertNewsTitleToUrl(SDProfileConfig::$phrases['page_newmessage_title'], null, null, true),
      'page_createmessage'     => 'page_createmessage',
      'page_viewmessages'      => ConvertNewsTitleToUrl(SDProfileConfig::$phrases['page_viewmessages_title'], null, null, true),
      'page_viewdiscussions'   => ConvertNewsTitleToUrl(SDProfileConfig::$phrases['page_viewdiscussions_title'], null, null, true),
      'page_viewdiscussion'    => ConvertNewsTitleToUrl(SDProfileConfig::$phrases['page_viewdiscussion_title'], null, null, true),
      'page_subscriptions'     => ConvertNewsTitleToUrl(SDProfileConfig::$phrases['page_subscriptions_title'], null, null, true),
      'page_viewsentmessages'  => ConvertNewsTitleToUrl(SDProfileConfig::$phrases['page_viewsentmessages_title'], null, null, true),
      'page_mycontent'         =>
        #ConvertNewsTitleToUrl(SDProfileConfig::$phrases['page_mycontent_title'], null, null, true),
        array('title' => ConvertNewsTitleToUrl(SDProfileConfig::$phrases['page_mycontent_title'], null, null, true),
              'pages' => array(
                'page_myarticles' => ConvertNewsTitleToUrl(SDProfileConfig::$phrases['page_myarticles_title'], null, null, true),
                'page_myblog'     => ConvertNewsTitleToUrl(SDProfileConfig::$phrases['page_myblog_title'], null, null, true),
                'page_myforum'    => ConvertNewsTitleToUrl(SDProfileConfig::$phrases['page_myforum_title'], null, null, true),
                'page_myfiles'    => ConvertNewsTitleToUrl(SDProfileConfig::$phrases['page_myfiles_title'], null, null, true),
                'page_mymedia'    => ConvertNewsTitleToUrl(SDProfileConfig::$phrases['page_mymedia_title'], null, null, true),
                )
              ),
      'page_viewmessage'       => ConvertNewsTitleToUrl(SDProfileConfig::$phrases['page_viewmessage_title'], null, null, true),
      'page_avatar'            => ConvertNewsTitleToUrl(SDProfileConfig::$phrases['page_avatar_title'], null, null, true),
      'page_picture'           => ConvertNewsTitleToUrl(SDProfileConfig::$phrases['page_picture_title'], null, null, true)
    );

    // Default options array with ALL available items
    // Use "GetOptions($flag)"
    self::$_all_options = array(
      self::OPTION_MARKALLREAD    => array('val' => 'op_markallread',   'phrase' => self::$phrases['options_mark_all_read']),
      self::OPTION_MARKDISCREAD   => array('val' => 'op_markdiscread',  'phrase' => self::$phrases['options_mark_discussion_read']),
      self::OPTION_MARKSELECTEDREAD   => array('val' => 'op_markselread',   'phrase' => self::$phrases['options_mark_selected_read']),
      self::OPTION_MARKSELECTEDUNREAD => array('val' => 'op_markselunread', 'phrase' => self::$phrases['options_mark_selected_unread']), //SD370
      self::OPTION_APPROVE        => array('val' => 'op_approve',       'phrase' => self::$phrases['options_approve']),
      self::OPTION_UNAPPROVE      => array('val' => 'op_unapprove',     'phrase' => self::$phrases['options_unapprove']),
      self::OPTION_MOVE           => array('val' => 'op_move',          'phrase' => self::$phrases['options_move']),
      self::OPTION_INVITE         => array('val' => 'op_invite',        'phrase' => self::$phrases['options_invite_user']),
      self::OPTION_UNINVITE       => array('val' => 'op_uninvite',      'phrase' => self::$phrases['options_uninvite_user']),
      self::OPTION_LEAVE          => array('val' => 'op_leave',         'phrase' => self::$phrases['options_leave']),
      self::OPTION_CLOSE          => array('val' => 'op_close',         'phrase' => self::$phrases['options_close']),
      self::OPTION_OPEN           => array('val' => 'op_open',          'phrase' => self::$phrases['options_open']),
      self::OPTION_IGNORE         => array('val' => 'op_ignore',        'phrase' => self::$phrases['options_ignore']),
      self::OPTION_UNIGNORE       => array('val' => 'op_unignore',      'phrase' => self::$phrases['options_unignore']),
      self::OPTION_DELETE         => array('val' => 'op_delete',        'phrase' => self::$phrases['options_delete']),
      self::OPTION_FRIEND         => array('val' => 'op_friend',        'phrase' => self::$phrases['options_friend']),
      self::OPTION_UNFRIEND       => array('val' => 'op_unfriend',      'phrase' => self::$phrases['options_unfriend']),
      self::OPTION_BUDDY          => array('val' => 'op_buddy',         'phrase' => self::$phrases['options_buddy']),
      self::OPTION_UNBUDDY        => array('val' => 'op_unbuddy',       'phrase' => self::$phrases['options_unbuddy']),
    );

    // Load extra usergroup configuration settings (usergroups_config)
    self::$usergroups_config = array();
    if(($getgroupsconfig = $SDCache->read_var('UCP_GROUPS_CONFIG', 'groups_config')) === false)
    {
      $prevDB = $DB->database;
      if($DB->database != $database['name']) $DB->select_db($database['name']);
      if(in_array('usergroups_config', $DB->table_names_arr[$DB->database]))
      {
        if($getgroupsconfig = $DB->query('SELECT ugc.*, ug.color_online, ug.display_online, ug.displayname, ug.banned
                                          FROM {usergroups_config} ugc
                                          INNER JOIN {usergroups} ug ON ug.usergroupid = ugc.usergroupid'))
        {
          while($group = $DB->fetch_array($getgroupsconfig,null,MYSQL_ASSOC))
          {
            self::$usergroups_config[$group['usergroupid']] = $group;
          }
        }
      }
      if($DB->database != $prevDB) $DB->select_db($prevDB);

      $SDCache->write_var('UCP_GROUPS_CONFIG', 'groups_config', self::$usergroups_config, false);
    }
    else
    {
      self::$usergroups_config = $getgroupsconfig;
    }
    self::$_ajax = Is_Ajax_Request(); //SD370

    self::$_initdone = true;

    // Load profile structure and field definitions (last line!)
    self::LoadProfileConfig(false);

  } //init


  // ##########################################################################
  // GET ALL AVAILABLE "OPTIONS" (FOR SELECT ELEMENT)
  // ##########################################################################

  public static function GetOptions($options_flag)
  {
    self::init();
    $result = array();
    if(empty($options_flag) || !is_numeric($options_flag)) return $result;
    foreach(self::$_all_options as $key => $entry)
    {
      if($options_flag & $key)
      {
        $result[] = $entry;
      }
    }
    return $result;
  } //GetOptions


  // ##########################################################################
  // GET A SINGLE PROFILE FIELD'S VALUE
  // ##########################################################################

  public static function GetFieldValue($field, $default='')
  {
    if(isset(self::$_userdata[$field]))
    {
      return self::$_userdata[$field];
    }
    return $default;
  } //GetFieldValue


  // ##########################################################################
  // UPDATE A SINGLE PROFILE FIELD WITH GIVEN VALUE
  // ##########################################################################

  public static function SetField($field, $value='')
  {
    if(isset($field))
    {
      self::$_userdata[$field] = $value;
      return true;
    }

    return false;
  } //SetField


  // ##########################################################################
  // GET A DEFAULT/TRANSLATED PHRASE
  // ##########################################################################

  public static function GetPhrase($phrase,$default)
  {
    if(isset($phrase) && strlen($phrase) && isset(self::$phrases[$phrase]))
    {
      return self::$phrases[$phrase];
    }
    else
    {
      return $default;
    }
  }

  // ##########################################################################
  // GET THE ASSIGNED USERDATA ARRAY
  // ##########################################################################

  public static function GetOnlineImage($userid=0)
  {
    global $DB, $usersystem;

    if(empty($userid) || ($usersystem['name'] !== 'Subdreamer')) return '';

    // Fetch user's online status (SD only)
    $online_img = '';
    if($online = $DB->query_first('SELECT 1 user_online FROM {sessions} sessions
                  WHERE sessions.userid = %d AND sessions.lastactivity > %d',
                  $userid, (TIME_NOW - 900)))
    {
      $online = !empty($online);
      $online_img = '<img src="'.SITE_URL.'plugins/forum/images/'.
                    ($online?'online':'offline').'.png'.
                    '" alt="" width="16" height="16" />';
    }

    return $online_img;

  } //GetUserdata


  // ##########################################################################
  // GET THE ASSIGNED USERDATA ARRAY
  // ##########################################################################

  public static function GetUserdata()
  {
    if(isset(self::$_userdata))
    {
      return self::$_userdata;
    }

    return array();

  } //GetUserdata


  // ##########################################################################
  // ASSIGN A SPECIFIC USERDATA ARRAY
  // ##########################################################################

  public static function SetUserdata($data_arr=array())
  {
    if(isset($data_arr) && is_array($data_arr))
    {
      self::$_userdata = $data_arr;
      return true;
    }

    return false;

  } //SetUserdata


  // ##########################################################################
  // MAKE A GIVEN PATH WRITABLE (CREATE FOLDER IF NEEDED)
  // ##########################################################################

  public static function makePathWritable($path)
  {
    if(empty($path) || ($path=='./') || ($path=='../')) return false;
    if(!is_dir($path))
    {
      //SD343: ignore notices and fix folder permissions
      $old_ignore = $GLOBALS['sd_ignore_watchdog'];
      $GLOBALS['sd_ignore_watchdog'] = true;
      $old_umask = @umask(0);
      $res = @mkdir($path, 0777, true);
      if(is_dir($path)) @chmod($path, 0777);
      @umask($old_umask);
      $GLOBALS['sd_ignore_watchdog'] = $old_ignore;
      return $res;
    }
    return true;
  }


  // ##########################################################################
  // LOAD PROFILE FIELD DEFINITIONS
  // ##########################################################################

  public static function ClearUserData($loadUserData=false)
  {
    self::$_userdata = array();
    foreach(self::$profile_fields as $key => $field)
    {
      $fieldname = $field['name'];

      $default = '';
      if(isset($field['type']))
      switch($field['type'])
      {
        case 'bool':
        case 'integer':
          $default = 0;
          break;
        case 'whole_number':
          $default = 1;
          break;
        default:
      }
      if(!empty($loadUserData))
      {
        $default = self::GetFieldValue($fieldname, $default);
      }
      self::$_userdata[$fieldname] = $default;
    }

  } //ClearUserData


  public static function LoadProfileConfig($loadUserData=false, $onlyExtraData=false, $onlyCredentials=false)
  {
    global $DB, $database, $categoryid, $sdlanguage;

    SDProfileConfig::init();

    if(self::$fields_loaded && empty($loadUserData)) return;

    $field_cnt = 0;
    self::$profile_fields = array();
    self::$profile_groups = array();
    self::$profile_group_fields = array();
    self::$public_fieldnames = array();
    self::$public_fieldnameids = array();

    // Password/Email is NOT stored in users_data, so manually create it now:
    $groupname = self::$phrases['groupname_user_credentials'];
    self::$profile_groups[1] = array(); // do not remove!
    self::$profile_groups[1] = array(
      'groupname_id'  => 1,
      'groupname'     => 'Login',
      'displayname'   => $groupname,
      'displayorder'  => 0,
      'is_visible'    => 1,
      'friends_only'  => 0,
      'access_view'   => ''
    );

    if(empty($onlyExtraData) || !empty($onlyCredentials))
    {
      self::$profile_fields[$field_cnt] = array(
                  'groupname_id'  => 1,
                  'groupname'     => $groupname,
                  'id'            => self::$form_prefix . 'user_name',
                  'type'          => 'text',
                  'name'          => 'username',
                  'label'         => self::$phrases['user_name'],
                  'is_custom'     => 0,
                  'public'        => 0,
                  'readonly'      => 1,
                  'required'      => 0,
                  'value'         => '',//self::GetFieldValue('username'),
                  'ucp_only'      => 1,
                  'visible'       => true
                );
      self::$profile_group_fields[1][] = $field_cnt;
      $field_cnt++;

      if(self::$usersystem['name'] == 'Subdreamer')
      {
        self::$profile_fields[$field_cnt] = array(
                  'groupname_id'  => 1,
                  'groupname'     => $groupname,
                  'id'            => self::$form_prefix . 'new_password',
                  'type'          => 'password',
                  'name'          => 'p_new_password',
                  'is_custom'     => 0,
                  'label'         => self::$phrases['new_password'],
                  'public'        => 0,
                  'readonly'      => 0,
                  'required'      => 0,
                  'ucp_only'      => 1,
                  'visible'       => true
                );
        self::$profile_group_fields[1][] = $field_cnt;
        $field_cnt++;
        self::$profile_fields[$field_cnt] = array(
                  'groupname_id'  => 1,
                  'groupname'     => $groupname,
                  'id'            => self::$form_prefix . 'confirm_new_password',
                  'type'          => 'password',
                  'name'          => 'p_confirm_new_password',
                  'is_custom'     => 0,
                  'label'         => self::$phrases['confirm_password'],
                  'public'        => 0,
                  'readonly'      => 0,
                  'required'      => 0,
                  'ucp_only'      => 1,
                  'visible'       => true
                );
        self::$profile_group_fields[1][] = $field_cnt;
        $field_cnt++;
        self::$profile_fields[$field_cnt] = array(
                  'groupname_id'  => 1,
                  'groupname'     => $groupname,
                  'id'            => self::$form_prefix . 'new_email',
                  'type'          => 'email',
                  'name'          => 'p_new_email',
                  'is_custom'     => 0,
                  'label'         => self::$phrases['new_email'],
                  'public'        => 0,
                  'readonly'      => 0,
                  'required'      => 0,
                  'ucp_only'      => 1,
                  'visible'       => true
                );
        self::$profile_group_fields[1][] = $field_cnt;
        $field_cnt++;
        self::$profile_fields[$field_cnt] = array(
                  'groupname_id'  => 1,
                  'groupname'     => $groupname,
                  'id'            => self::$form_prefix . 'confirm_new_email',
                  'type'          => 'email',
                  'name'          => 'p_confirm_new_email',
                  'label'         => self::$phrases['confirm_email'],
                  'is_custom'     => 0,
                  'public'        => 0,
                  'readonly'      => 0,
                  'required'      => 0,
                  'ucp_only'      => 1,
                  'visible'       => true
                );
        self::$profile_group_fields[1][] = $field_cnt;
        $field_cnt++;
        self::$profile_fields[$field_cnt] = array(
                  'groupname_id'  => 1,
                  'groupname'     => $groupname,
                  'id'            => self::$form_prefix . 'receive_emails',
                  'type'          => 'checkbox',
                  'name'          => 'p_receive_emails',
                  'checked'       => (/*$loadUserData &&*/ self::GetFieldValue('receive_emails')),
                  'is_custom'     => 0,
                  'label'         => self::$phrases['receive_emails'],
                  'public'        => 0,
                  'readonly'      => 0,
                  'required'      => 0,
                  'value'         => 1,
                  'ucp_only'      => 1,
                  'visible'       => true
                );
        self::$profile_group_fields[1][] = $field_cnt;
        $field_cnt++;
      }
    }

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);

    // ALL fielda are loaded; a field's visibility status is determined
    // by both the group's and field's visibility.
    // SD342: do not try to continue if not already upgraded to prevent DB error
    if(defined('SD_342') && empty($onlyCredentials) && !SDProfileConfig::$basic_profile)
    {
      // Get Profile Groups
      if($getgroups = $DB->query('SELECT * FROM '.PRGM_TABLE_PREFIX.'users_field_groups'.
                                 ' ORDER BY groupname_id'))
      {
        while($group_arr = $DB->fetch_array($getgroups,null,MYSQL_ASSOC))
        {
          self::$profile_groups[$group_arr['groupname_id']] = $group_arr;
          if($group_arr['groupname_id'] > 1) // do not clear first group!
          {
            self::$profile_group_fields[$group_arr['groupname_id']] = array();
          }
        }
      }

      // Get Profile Fields
      if($getfields = $DB->query('SELECT ug.displayname `groupname`, ug.is_visible, ug.groupname_id, uf.*'.
                                 ' FROM '.PRGM_TABLE_PREFIX.'users_fields uf'.
                                 ' LEFT JOIN '.PRGM_TABLE_PREFIX.'users_field_groups ug ON ug.groupname_id = uf.groupname_id'.
                                 ' ORDER BY ug.displayorder, uf.fieldorder, uf.fieldnum'))
      {
        while($field_arr = $DB->fetch_array($getfields,null,MYSQL_ASSOC))
        {
          // Set default values for field array:
          $current_field = array(
            'groupname_id'  => (int)$field_arr['groupname_id'],
            'groupname'     => $field_arr['groupname'],
            'fieldnum'      => (int)$field_arr['fieldnum'],
            'name'          => $field_arr['fieldname'],
            'type'          => $field_arr['fieldtype'],
            'id'            => self::$form_prefix . $field_arr['fieldname'],
            'is_custom'     => !empty($field_arr['is_custom']),
            'fieldorder'    => (int)$field_arr['fieldorder'],
            'label'         => $field_arr['fieldlabel'],
            'public'        => !empty($field_arr['public_status']),
            'public_req'    => !empty($field_arr['public_req']),
            'readonly'      => !empty($field_arr['readonly']),
            'required'      => !empty($field_arr['req']),
            'reg_form'      => !empty($field_arr['reg_form']),
            'reg_form_req'  => !empty($field_arr['reg_form_req']),
            'vartype'       => (!empty($field_arr['vartype']) ? $field_arr['vartype'] : 'string'),
            'ucp_only'      => !empty($field_arr['ucp_only']),
            'visible'       => (!empty($field_arr['fieldshow']) && !empty($field_arr['is_visible'])),
            'expression'    => (empty($field_arr['fieldexpr']) ? '' : (string)$field_arr['fieldexpr'])
          );

          // Check if a plugin phrase exists for the field's label and add it once if not found
          $phrase_id = 'select_profile_page_'.strtolower(preg_replace('/[^0-9a-zA-Z_]/', '_', $current_field['name']));
          if(isset(self::$phrases[$phrase_id]))
          {
            $label = self::$phrases[$phrase_id];
          }
          else
          {
            $label = $current_field['label'];
            if(empty($onlyExtraData))
            {
              require_once(SD_INCLUDE_PATH.'functions_plugins.php');
              InsertPhrase(self::$pluginid, $phrase_id, $label);
            }
            self::$phrases[$phrase_id] = $label;
          }
          $current_field['label'] = $label;

          // Translate special types to mimic supported types:
          if($current_field['type'] == 'yesno')
          {
            unset($current_field['maxlength']);
            $current_field['ValidateOnlyOnServerSide'] = '1';
          }
          else
          if($current_field['type'] == 'select')
          {
            // actual options reside in "fieldexpr":
            if(!empty($field_arr['fieldexpr']) || !empty($field_arr['seltable']))
            {
              $default = '';
              $current_field['options'] = array();
              // Is SELECT using a prompt table to retrieve key/value pairs from?
              // A prompt table has priority over any field expression (see below).
              // Table's name MUST have SD's table prefix and only consist of latin letters and digits
              // Each lookup value may have a translation phrase ($sdlanguage)
              $lookup_ok = false; // IF lookup wrongly configured, below "fieldexpr" may be taking effect
              if(!empty($field_arr['seltable']))
              {
                list($sel_table, $sel_key, $sel_value) = explode('|', $field_arr['seltable']);
                if(@in_array($sel_table, $DB->table_names_arr[$DB->database]) &&
                   $DB->column_exists(PRGM_TABLE_PREFIX . $sel_table,$sel_key) &&
                   $DB->column_exists(PRGM_TABLE_PREFIX . $sel_table,$sel_value))
                {
                  if($getrows = $DB->query("SELECT `$sel_key`, `$sel_value` FROM %s ORDER BY $sel_value",
                     PRGM_TABLE_PREFIX.$sel_table))
                  {
                    $lookup_ok = true;
                    while($option = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
                    {
                      // Try to find a translated phrase for the current value
                      $table_ident = strtolower(preg_replace('/[^0-9a-zA-Z_]/', '_', $sel_table));
                      $phrase_ident = strtolower(preg_replace('/[^0-9a-zA-Z_]/', '_', $sel_value));
                      if(isset($sdlanguage['select_'.$table_ident.'_'.$phrase_ident]))
                      {
                        $option[$sel_value] = $sdlanguage[$sel_table.'_'.$phrase_ident];
                      }
                      $current_field['options'][$option[$sel_key]] = $option[$sel_value];
                    }
                  }
                }
              }

              // Is SELECT using a stored list of key/value pairs?
              if(!$lookup_ok && !empty($field_arr['fieldexpr']))
              {
                $current_field['options'] = array();
                $select_arr = preg_split('/\R/', $field_arr['fieldexpr'], -1, PREG_SPLIT_NO_EMPTY);
                $select_ident = strtolower(preg_replace('/[^0-9a-zA-Z_]/', '_', $current_field['name']));
                foreach($select_arr AS $sel_entry)
                {
                  list($sel_value, $sel_phrase) = explode('|', $sel_entry);
                  // Try to find a phrase; if not present, add it to the plugin phrases once:
                  $phrase_ident = strtolower(preg_replace('/[^0-9a-zA-Z_]/', '_', $sel_phrase));
                  $phrase_ident = 'option_'.strtolower($select_ident.'_'.$phrase_ident);
                  if(isset(self::$phrases[$phrase_ident]))
                  {
                    $sel_phrase = self::$phrases[$phrase_ident];
                  }
                  else
                  if(empty($onlyExtraData))
                  {
                    require_once(SD_INCLUDE_PATH.'functions_plugins.php');
                    InsertPhrase(self::$pluginid, $phrase_ident, $sel_phrase);
                    self::$phrases[$phrase_ident] = $sel_phrase;
                  }
                  // Finally add the option to the field
                  $current_field['options'][$sel_value] = $sel_phrase;
                }
                if(empty($current_field['options']))
                {
                  $current_field['options'][0] = '---';
                  $current_field["value"] = '';
                }
              }
            }
            $current_field['ValidateOnlyOnServerSide'] = '1';
          }
          else
          if($current_field['type'] == 'radio')
          {
            $current_field['ValidateOnlyOnServerSide'] = '1';
          }
          else
          if($current_field['type'] == 'date')
          {
            if(empty($current_field['value'])) // avoid 0 as date value
            {
              $current_field['value'] = '';
            }
          }
          else
          {
            $current_field['maxlength'] = $field_arr['fieldlength'];
            $current_field['ValidateOnlyOnServerSide'] = '1';
          }

          if($current_field['public'])
          {
            self::$public_fieldnames[] = $field_arr['fieldname'];
            self::$public_fieldnameids[$field_arr['fieldname']] = array('fieldnum'=>(int)$field_arr['fieldnum'],'id'=>(int)$field_cnt);
          }
          self::$profile_fields[$field_cnt] = $current_field;
          self::$profile_group_fields[$field_arr['groupname_id']][] = (int)$field_cnt;
          $field_cnt++;

        } //while
      }
    }

    self::$fields_loaded = true;

    if($DB->database != $prevDB) $DB->select_db($prevDB);

  } //LoadProfileConfig


  public static function GetMsgObj()
  {
    global $SDCache;

    if(!self::$_msg_obj)
    {
      require_once(SD_INCLUDE_PATH.'class_messaging.php');
      self::$_msg_obj = new SDMsg(self::$usersystem['usersystemid'], $SDCache);
    }

    return self::$_msg_obj;
  }

  public static function CreateSmartyDefault($keepExisting=false)
  {
    global $sdlanguage, $sdurl, $userinfo;

    self::init();

    //SD342: use new SD's Smarty class
    //SD344: new template handling; pass param to keep existing object
    $smarty = SD_Smarty::getNew($keepExisting);
    $smarty->assign('AdminAccess', !empty($userinfo['adminaccess']));
    $smarty->assign('sdlanguage',  $sdlanguage);
    $smarty->assign('sdurl',       $sdurl);
    $smarty->assign('phrases',     @array_merge(self::$phrases,self::$p10_phrases));
    $smarty->assign('pluginid',    self::$pluginid);

    return $smarty;

  } //CreateSmartyDefault

  public static function GetProfileFieldByIdx($idx)
  {
    self::init();
    if(!empty(self::$profile_fields) && count(self::$profile_fields) && isset(self::$profile_fields[$idx]))
    {
      return self::$profile_fields[$idx];
    }
    return false;
  }

  public static function GetProfileField($fieldname)
  {
    self::init();
    if(!empty(self::$profile_fields) && count(self::$profile_fields) && isset(self::$profile_fields[$fieldname]))
    {
      return self::$profile_fields[$fieldname];
    }
    return false;
  }

  public static function GetFieldTypes()
  {
    return self::$_fieldtypes;
  }

  public static function GetProfileFields()
  {
    self::init();
    return self::$profile_fields;
  }

  public static function GetProfileGroups()
  {
    self::init();
    return self::$profile_groups;
  }

  public static function GetProfilePages()
  {
    self::init();
    return self::$profile_pages;
  }

  public static function GetPageFragment($pageid,$subPage=false)
  {
    self::init();
    if(!isset(self::$profile_pages[$pageid])) return '';
    $item = self::$profile_pages[$pageid];
    if(is_string($item)) return $item;
    if(empty($subPage)) return isset($item['title'])?$item['title']:'';
    return (isset($item['pages'][$subPage])?$item['pages'][$subPage]:'');
  }

  public static function PageValid($pageid,$subPage=false)
  {
    self::init();
    if(empty($pageid)) return false;
    if(!isset(self::$profile_pages[$pageid])) return false;
    if(!empty($subPage) && isset(self::$profile_pages[$pageid]['pages']))
    {
      return (false !== array_search($subPage,self::$profile_pages[$pageid]['pages']));
    }
    #if(array_key_exists($pageid, self::$profile_pages)) return true;
    #($pageid==self::$profile_pages['page_mycontent']['title']);
    return false;
  }

} //class SDProfileConfig


// ############################################################################
// PROFILE PAGE HANDLER CLASS
// ############################################################################
// SD342 moved to file "class_profilehandler.php"


// ############################################################################
// USER PROFILE CLASS
// ############################################################################

class SDUserProfile
{
  private $_userid        = 0;
  private $_smarty        = false;
  private $_tables_check  = false;

  public  $conf           = null;
  public  $fields_loaded  = false;
  public  $okmessage      = '';
  public  $errortitle     = '';
  public  $errors         = array();
  public  $js_output      = '';
  public  $form_token     = '';

  public function __construct($userid=0)
  {
    global $DB;

    SDProfileConfig::init(11);

    $this->form_token = SDProfileConfig::$form_prefix . 'token';

    $this->_tables_check = isset($DB->table_names_arr[SD_DBNAME]) &&
                           in_array('users_data', $DB->table_names_arr[SD_DBNAME]);

    // Load user profile data for either provided user id
    // or otherwise the currently logged in user
    $this->_userid = false;
    if(!empty($userid) && ((int)$userid > 0))
    {
      $this->_userid = (int)$userid;
    }
    if($this->_userid)
    {
      $this->LoadUser($this->_userid);
    }

    return true;

  } //constructor


  public function tablescheck()
  {
    return $this->_tables_check;
  }

  public function currentuser()
  {
    return $this->_userid;
  }

  // ##########################################################################
  // LOAD USER'S PROFILE DATA
  // ##########################################################################

  public function LoadUser($userid, $asCurrentUser=true)
  {
    global $DB, $database, $usersystem;

    $result = array();
    $asCurrentUser = !empty($asCurrentUser);
    $prevDB = $DB->database;
    $DB->result_type = MYSQL_ASSOC;
    if(!empty($userid) && $this->tablescheck())
    {
      if($tmp = sd_GetForumUserInfo(1,$userid,true,array('user_sig','user_post_count','user_thread_count')))
      {
        //SD342 - TODO: with Forum Integration there is no usergroup defined in $tmp!
        //Temporarily match it to Guests group
        if(empty($tmp['usergroupid']))
        {
          $tmp['usergroupid'] = 4; //SD342
          $user['loggedin'] = 0;
        }
        if(isset($tmp['usergroup_details']))
        {
          $tmp['usergroup_details']['adminaccess'] = !empty($tmp['usergroup_details']['adminaccess']);
          $tmp['usergroup_details']['color_online'] = empty($tmp['usergroup_details']['color_online']) ? '#000000' : '#'.(string)$tmp['usergroup_details']['color_online'];
          $tmp['usergroup_details']['displayname'] = empty($tmp['usergroup_details']['displayname']) ? '' : (string)$tmp['usergroup_details']['displayname'];
          $tmp['usergroup_details']['display_online'] = !isset($tmp['usergroup_details']['display_online']) || !empty($tmp['usergroup_details']['display_online']);
          $tmp['usergroup_details']['name'] = !empty($tmp['usergroup_details']['name']) ? (string)$tmp['usergroup_details']['name'] : '';
          $tmp['usergroup_details']['sig_enabled'] = isset($tmp['usergroup_details']['sig_enabled']) && !empty($tmp['usergroup_details']['sig_enabled']);
        }
        unset($tmp['password']);
        if($asCurrentUser)
        {
          $this->_userid = $userid;
          SDProfileConfig::SetUserdata($tmp);
        }
        //SD343: sort details
        if(isset($tmp['usergroup_details']) && is_array($tmp['usergroup_details']))
        {
          @ksort($tmp['usergroup_details']);
        }
        @ksort($tmp);
        $result = $tmp;
      }
    }
    $DB->result_type = MYSQL_BOTH;

    if($DB->database != $prevDB) $DB->select_db($prevDB);

    if(!$result && $asCurrentUser)
    {
      $this->_userid = 0;
      SDProfileConfig::ClearUserData();
    }
    return $result;

  } //LoadUser


  // ##########################################################################
  // INIT TEMPLATE
  // ##########################################################################

  private function InitTemplate($keepExisting=false) //SD344: new param
  {
    global $categoryid, $userinfo;

    $smarty = SDProfileConfig::CreateSmartyDefault($keepExisting);
    $smarty->assign('ucp_url',      SD_P11_USERCP_LINK);
    $smarty->assign('ucp_groups',   array());
    $smarty->assign('seo',          SDProfileConfig::GetProfilePages());
    $smarty->assign('userid',       $this->_userid);
    $smarty->assign('logout',       RewriteLink('index.php?categoryid=1&logout=1'));
    $smarty->assign('AdminAccess',  ($userinfo['adminaccess'] || (!empty($userinfo['pluginadminids']) &&
                                    in_array(SDProfileConfig::$pluginid, $userinfo['pluginadminids']))));

    return $smarty;

  } //InitTemplate


  // ##########################################################################
  // DISPLAY CONTROL PANEL
  // ##########################################################################

  public function DisplayControlPanel($action=null,$pageid=null)
  {
    global $DB, $SDCache, $categoryid, $sdlanguage, $sdurl, $userinfo, $usersystem,
           $mainsettings, $mainsettings_modrewrite, $mainsettings_url_extension;

    if(empty($userinfo['userid']) || empty($userinfo['loggedin']))
    {
      DisplayMessage($sdlanguage['no_view_access'],true);
      return false;
    }

    SDProfileConfig::init();

    // Pre-process profile groups
    if(($getsorted = $SDCache->read_var('UCP_PROFILE_GROUPS', 'groups')) === false)
    {
      $profile_groups = SDProfileConfig::GetProfileGroups();
      $group_tabs = array();
      foreach($profile_groups as $group)
      {
        $seo = ConvertNewsTitleToUrl($group['displayname'], null, null, true);
        $profile_groups[$group['groupname_id']]['seo_title'] = $seo;
        if($group['is_visible'])
        {
          $group_tabs[$group['displayorder']] = array(
            'group_id'  => $group['groupname_id'],
            'name'      => $group['displayname'],
            'seo_title' => $seo
          );
        }
      }
      ksort($group_tabs);

      $SDCache->write_var('UCP_PROFILE_GROUPS', 'groups', array(
                            'profile_groups' => $profile_groups,
                            'profile_tabs'   => $group_tabs,
                            ), false);
    }
    else
    {
      $group_tabs = $getsorted['profile_tabs'];
      $profile_groups = $getsorted['profile_groups'];
    }
    unset($getgroups);

    $group_options = SDProfileConfig::$usergroups_config[$userinfo['usergroupid']];

    $page_url = $profile_url = RewriteLink('index.php?categoryid='.$categoryid).
                ($mainsettings_modrewrite ? '?' : '&amp;');
    $page_url.= 'profile='.$this->currentuser();
    $profile_url .= 'member='.$this->currentuser();

    // Prepare extra profile links:
    $links = array();
    $links['member_page']   = $profile_url;
    $links['mycontent']     = $page_url.'#do='.SDProfileConfig::GetPageFragment('page_mycontent');
    $links['subscriptions'] = $page_url.'#do='.SDProfileConfig::$profile_pages['page_subscriptions'];

    // Init SMARTY template
    $this->_smarty = $this->InitTemplate(false);
    $this->_smarty->assign('basic_profile', SDProfileConfig::$basic_profile); //SD342
    $this->_smarty->assign('links', $links);

    //date_default_timezone_set('UTC');
    require_once(SD_INCLUDE_PATH.'class_messaging.php');
    $msg = SDProfileConfig::GetMsgObj(); // must have! initializes objects if need be!

    $userdata = $this->LoadUser($this->currentuser(), false);
    SDProfileConfig::SetUserdata($userdata);
    $online_img = SDProfileConfig::GetOnlineImage($this->currentuser());
    $avatar = ForumAvatar($this->currentuser(),'');
    //SD370: "user_unread_privmsg" was not used before; it's updated whenever
    // as user receives a new PM, so as long as this is 0, do manual recount
    // (i.e. it saves a SQL once a user received first message):
    if( ($this->currentuser() == $userinfo['userid']) &&
        !empty($userinfo['profile']['user_unread_privmsg']))
      $unread_count = $userinfo['profile']['user_unread_privmsg'];
    else
      $unread_count = SDMsg::getMessageCounts($this->currentuser(), SDMsg::MSG_INBOX | SDMsg::MSG_STATUS_UNREAD);
    $discs_unread = SDMsg::getMessageCounts($this->currentuser(), SDMsg::MSG_DISCUSSIONS | SDMsg::MSG_STATUS_UNREAD);
    //SD343: fetch users' "likes"
    require_once(SD_INCLUDE_PATH.'class_sd_likes.php');
    $likes_comments = SD_Likes::GetUserLikesCount($this->currentuser(), SD_LIKED_TYPE_COMMENT);
    $likes_posts = SD_Likes::GetUserLikesCount($this->currentuser(), SD_LIKED_TYPE_POST);
    $liked_posts_count = 0;
    $liked_posts_likes = 0;
    if($forumid = GetPluginID('Forum'))
    {
      if($tmp = SD_Likes::GetUserLikedCount($this->currentuser(),SD_LIKED_TYPE_POST,$forumid))
      {
        $liked_posts_count = $tmp['likesitems'];
        $liked_posts_likes = $tmp['likescount'];
      }
    }

    $displayname  = (SDProfileConfig::GetFieldValue('user_screen_name')?SDProfileConfig::GetFieldValue('user_screen_name'):SDProfileConfig::GetFieldValue('username'));
    $comments_count = $DB->query('SELECT COUNT(*) FROM {comments} WHERE userid = %d',$this->currentuser());
    $userdata['usergroup_details']['displayname'] =
      !empty($userdata['usergroup_details']['displayname'])?$userdata['usergroup_details']['displayname']:$userdata['usergroup_details']['name'];
    $data_conf = array(
      'avatar'        => $avatar,
      'currentdate'   => DisplayDate(TIME_NOW, '', true),
      'discussions_unread_count' => $discs_unread,
      'displayname'   => $displayname,
      'forum_integration' => ($usersystem['name']!='Subdreamer'),
      'inbox_count'   => (isset($userdata['msg_in_count'])?(int)$userdata['msg_in_count']:0),
      'joindate'      => DisplayDate($userdata['joindate'],'Y-m-d'),
      'likes_comments'=> $likes_comments,
      'likes_posts'   => $likes_posts,
      'liked_posts_count' => $liked_posts_count,
      'liked_posts_likes' => $liked_posts_likes,
      'messaging_on'  => (!isset($userdata['user_allow_pm']) || !empty($userdata['user_allow_pm'])), // do not change! users_data row may no exist yet!
      'online'        => $online_img,
      'outbox_count'  => (isset($userdata['msg_out_count'])?(int)$userdata['msg_out_count']:0),
      'post_count'    => (empty($userdata['user_post_count'])?0:$userdata['user_post_count']),
      'thread_count'  => (empty($userdata['user_thread_count'])?0:$userdata['user_thread_count']),
      'unread_count'  => $unread_count,
      'user_text'     => $this->GetUserDisplayValue($userdata, array('name'=>'user_text','type'=>'bbcode')),
      'username'      => SDProfileConfig::GetFieldValue('username'),
      'usergroup'     => $userdata['usergroup_details']['displayname'],
      'usergroup_color' => $userdata['usergroup_details']['color_online'],
    );
    $this->_smarty->assign('data', $data_conf);
    $this->_smarty->assign('group_options', $group_options);

    // Remove non-permitted tabs before passing to template:
    foreach($group_tabs as $gid => $g)
    {
      $perm = $profile_groups[$g['group_id']]['access_view'];
      if((SDProfileConfig::$basic_profile && ($g['group_id'] != 1)) ||
         (strlen($perm) && (strpos($perm,'|'.$userinfo['usergroupid'].'|')===false)))
      {
        unset($group_tabs[$gid]);
      }
    }
    $this->_smarty->assign('groups_sorted', $group_tabs);
    $this->_smarty->assign('current_url', $page_url);
    $this->_smarty->assign('page_url', $page_url);

    $redir_profile = false;
    $page_content = '';
    $smarty_messages = array();
    $isgroup = false;

    //SD370: when ajaxed call, the pageid is e.g. "group_2": "_" -> "."
    if(SDProfileConfig::$_ajax && !SDProfileConfig::$basic_profile &&
       (strlen($pageid) < 9) &&
       (substr($pageid,0,6)=='group_') && is_numeric(substr($pageid,6)))
    {
      $pageid = str_replace('_','.',$pageid);
      if($action=='submit') $action = GetVar('ucp_action', '', 'string');
    }

    if(SDProfileConfig::$basic_profile) //SD342
    {
      $groupid = 1;
      $isgroup = true;
      $pageid = 'group_1';
      if(($action!='do') && ($action!='ucp_update_user'))
      {
        $action = 'do';
      }
    }
    else
    if(false !== ($groupid = strrchr($pageid,'.')))
    {
      $groupid = substr($groupid,1);
      if($isgroup = is_numeric($groupid) && isset($profile_groups[$groupid]))
      {
        $groupid = intval($groupid);
        // Check permission first
        $perm = $profile_groups[$groupid]['access_view'];
        if(strlen($perm) && (strpos($perm,'|'.$userinfo['usergroupid'].'|')===false))
        {
          $pageid = '';
          $isgroup = false;
          $redir_profile = true;
        }
        else
        {
          $pageid = 'group_'.$groupid;
        }
      }
      else
      {
        $pageid = '';
        $isgroup = false;
        $redir_profile = true;
      }
    }
    else
    //SD342 any other page MUST have a re-set here
    if($pageid=='page_mycontent')
    {
      $action = 'do';
      $pageid = SDProfileConfig::GetPageFragment($pageid);
    }

    // If messaging is disabled for usergroup, do not allow sending message page
    if(!$isgroup && (($pageid==SDProfileConfig::$profile_pages['page_newmessage']) && !$group_options['msg_enabled']))
    {
      $pageid = '';
    }

    /*
    Info on URLs and form's "action"
    --------------------------------
    * ALL non-profile-groups MUST be included in the "$profile_pages" configuration
      array in the SDProfileConfig class with their SEO name;
      for each page's SEO name a specific phrase MUST exist (see upgrader)!

    * A page URL links either to a "profile group" page -or- a page of the above

    * Profile group-related pages take their title and SEO title from p11's
      profile configuration page which allows the admin to sort and "name" groups

    a) Links always start with "profile=xxx" (xxx = userid) and must include a
       identifier "do=[page to load]". The "page to load" can be either a profile
       group name or a non-profile group name as described above.
       Optional params are like "id=xxx" for message id when viewing a single message

    b) Updating a profile page has unique action "ucp_update_user" to trigger an
       update of profile fields for the current user.
       The link must be rewritten to link back to "do=[seo-group-title].xx" with
       ".xx" being the profile group's ID.
    */
    if(!$redir_profile && !empty($pageid) &&
       (( ($action=='ucp_update_user') && (substr($pageid,0,6)=='group_') ) ||
        ( ($action=='submit') && ($isgroup || isset(SDProfileConfig::$profile_pages[$pageid]))) ||
        ( ($action=='do') && ($pageid == SDProfileConfig::GetPageFragment('page_mycontent')) ) ||
        ( ($action=='do') && ($pageid == SDProfileConfig::PageValid('page_mycontent',$pageid)) ) ||
        ( ($action=='do') && (false !== ($key = array_search($pageid, SDProfileConfig::$profile_pages))) ) ||
        ( ($action=='do') && $isgroup) ||
        ( ($action=='delete') && !$isgroup && ($pageid=='page_viewmessage') )) )
    {
      // Delete a single private message (with checkbox being checked)
      if( ($action=='delete') && !$isgroup && ($pageid=='page_viewmessage') )
      {
        if(CheckFormToken($this->form_token))
        {
          $msg_id = GetVar('id', 0, 'whole_number');
          if(($msg_id > 0) && !empty($_POST['msg_delete']) && $_POST['msg_delete']==='1')
          {
            $msg->DeletePrivateMessages($userinfo['userid'],$msg_id);
          }
        }
        $action = 'do';
        $pageid = 'page_viewmessages';
        $page_url .= (SDProfileConfig::$_ajax?'#':'&amp;').'do='.SDProfileConfig::$profile_pages[$pageid];
        $this->_smarty->assign('current_url', $page_url);
      }
      else
      // Non-profilegroup page with "submit" action:
      if(!$isgroup && ($action=='submit') && ($key = SDProfileConfig::$profile_pages[$pageid]))
      {
        $page_url .= '&amp;do='.$key;
        if(($pageid=='page_newmessage') || ($pageid=='page_viewmessage'))
        {
          $pageid = 'page_createmessage';
        }
        $this->_smarty->assign('current_url', $page_url);
      }
      else
      // Any non-profilegroup related page, like "View Messages" etc.:
      if(!$isgroup &&
         (($pageid == SDProfileConfig::GetPageFragment('page_mycontent')) ||
          (false !== ($key = array_search($pageid, SDProfileConfig::$profile_pages['page_mycontent']['pages']))) ||
          (false !== ($key = array_search($pageid, SDProfileConfig::$profile_pages))) ))
      {
        $message_id = GetVar('id', 0, 'whole_number');
        $master_id  = GetVar('d', 0, 'whole_number');
        if($pageid == SDProfileConfig::GetPageFragment('page_mycontent'))
          $pageid = 'page_mycontent';
        else
          $pageid = $key;
        $current_url = $page_url.'#do='.SDProfileConfig::GetPageFragment($pageid).
                      ($message_id ? '&amp;id='.$message_id : ($master_id ? '&amp;d='.$master_id: ''));
        $this->_smarty->assign('current_url', $current_url);
      }
      else
      // Any profilegroup-related page:
      if(($action=='do') && $isgroup) // Fix up URL
      {
        $current_url = $page_url.'&amp;do='.$profile_groups[$groupid]['seo_title'].'.'.$groupid;
        $this->_smarty->assign('current_url', $current_url);
      }

      // **********************************************************************
      // ***** Buffer sub-page output and pass that on to main (ucp) template
      // **********************************************************************
      ob_start();

      if($action=='ucp_update_user')
      {
        // "submit" handler *only* for profilegroups
        $this->UpdateProfile(false, SDProfileConfig::$basic_profile);
        $this->fields_loaded = false;
      }
      else
      if($action=='submit')
      {
        $this->errors = false;
        // "submit" handler for all non-profilegroup pages:
        if(!$this->HandlePageAction($action, $pageid, $this->js_output))
        {
          SDProfileConfig::$last_error = is_array($this->errors) ? $this->errors : array($this->errors);
          $action = 'do'; // cannot be "submit" any longer
        }

        // Immediately update template variables (must reload values here):
        $unread_count = SDMsg::getMessageCounts($this->currentuser(), SDMsg::MSG_INBOX | SDMsg::MSG_STATUS_UNREAD);
        $data_conf['inbox_count']  = SDMsg::getMessageCounts($this->currentuser(), SDMsg::MSG_INBOX);
        $data_conf['outbox_count'] = SDMsg::getMessageCounts($this->currentuser(), SDMsg::MSG_OUTBOX);
        $data_conf['unread_count'] = $unread_count;
        $data_conf['discussions_unread_count'] = SDMsg::getMessageCounts($this->currentuser(), SDMsg::MSG_DISCUSSIONS | SDMsg::MSG_STATUS_UNREAD);
        $this->_smarty->assign('data', $data_conf);
      }

      if(SDProfileConfig::$basic_profile) //SD342
      {
        $action = 'do';
        $pageid = 'group_1';
      }

      if($pageid) // "$pageid" could have been reset!
      {
        $this->DisplayProfilePage($action, $pageid, $page_url);
      }

      // Fetch output from buffer and pass it to template
      $page_content = ob_get_clean();

      //SD370: if ajax-submit, then output and exit!
      if(SDProfileConfig::$_ajax)
      {
        echo $page_content;
        $DB->close();
        exit();
      }
      $this->_smarty->assign('page_content', $page_content);
    }
    elseif(!$redir_profile && empty($pageid))
    {
      // ############# DASHBOARD PAGE #############
      $this->_smarty->assign('ucp_content', (empty($mainsettings['templates_from_db']) ? '' : 'mysql:') . 'ucp_dashboard');
      $this->_smarty->assign('token_element', PrintSecureToken(SDProfileConfig::$form_prefix . 'token'));
    }
    elseif($redir_profile)
    {
      RedirectFrontPage($page_url, $sdlanguage['no_view_access'],3,true);
      return false;
    }

    //SD342: BIND AND DISPLAY TEMPLATE NOW
    $loadDashboard = SDProfileConfig::$_ajax && (empty($pageid) || ($pageid=='dashboard')) ; //SD370
    if($loadDashboard)
      $default_tpl = 'ucp_dashboard.tpl';
    else
      $default_tpl = SDProfileConfig::$basic_profile ? 'ucp_basic.tpl' : 'ucp.tpl';

    //SD344: new template handling
    $res = SD_Smarty::display(11, $default_tpl);
    if(!$res || ($err = SD_Smarty::getLastError()))
    {
      if(!empty($userinfo['adminaccess']))
        echo '<p><strong>User Control Panel: template error OR template not found (ucp.tpl)!<br />'.
             (SD_Smarty::getLastError()!==false?'Error: '.SD_Smarty::getLastError().'<br />':'').
             'Please check &quot;/includes/tmpl/defaults&quot; folder!</strong></p>';
      else
        echo '<p><strong>Sorry, but the user panel is currently not available!</strong></p>';
    }
    if($loadDashboard) exit();

  } //DisplayControlPanel


  // ##########################################################################
  // DISPLAY SINGLE PROFILE GROUP
  // ##########################################################################

  public function DisplayProfileGroup(array $group, $submitButton=true)
  {
    global $userinfo;

    $header = false;
    if(SDProfileConfig::$basic_profile)
    {
      $group['groupname_id'] = 1;
      $group_id = 1;
    }
    $group_id = $group['groupname_id'];
    $title    = $group['displayname'];
    $fields   = SDProfileConfig::$profile_group_fields[$group_id];

    $usergroup_options = SDProfileConfig::$usergroups_config[$userinfo['usergroupid']];
    $userdata = SDProfileConfig::GetUserdata();
    $public_fields = (SDProfileConfig::$basic_profile || empty($userdata['public_fields'])) ? array() : @explode(',',$userdata['public_fields']);
    $user_sig_detected = false;
    $fieldcount = 0;
    $editable_count = 0;

    if(!empty($userdata['activated'])) //SD370
    foreach($fields AS $field_idx)
    {
      $field = SDProfileConfig::GetProfileFieldByIdx($field_idx);

      // Generate articial value for "DisplayProfileInput", if field was marked
      // as publicly visible for member page:
      $field['user_config_public'] = !empty($field['fieldnum']) && empty($field['public_req']) &&
                                     @in_array($field['fieldnum'], $public_fields);

      if(!empty($field['visible']))
      {
        if(!$header)
        {
          $header = true;
          $out = sprintf(SDProfileConfig::$group_start_html, $title);
          echo $out;
        }
        if($field['name']=='user_sig' && empty($usergroup_options['sig_enabled']))
        {
          $user_sig_detected = true;
        }
        else
        {
          $fieldcount++;
          //SD370: based on group permission, reset readonly flag for username
          if(!empty($userdata['activated']) &&
             !empty(SDProfileConfig::$usergroups_config[$userdata['usergroupid']]['allow_uname_change']))
          {
            $field['readonly'] = false;
          }
          if(empty($field['readonly'])) $editable_count++;
          //SD360: respect new option to disable email editing
          if( (($field['id']!='ucp_new_email') && ($field['id']!='ucp_confirm_new_email')) ||
              empty(SDProfileConfig::$settings['disable_email_editing']))
          {
            $this->DisplayProfileInput($field);
          }
          if($field['id']=='ucp_confirm_new_email')
          {
            echo '<div class="ctrlHolder">'.$userdata['email'].'</div>';
          }
        }
      }
    } //foreach

    // Hide button if signature is disabled by admin and sig is only field on page
    if(!$fieldcount && $user_sig_detected) $submitButton = false;

    if(!empty($this->errors))
    {
      echo '
      <div class="ucp_errorMsg round_corners">';
      if($this->errortitle) echo '<h3>'.$this->errortitle.'</h3>';
      if(is_string($this->errors) && strlen($this->errors))
      {
        echo '<strong>'.$this->errortitle.'</strong>';
      }
      else
      if(is_array($this->errors) && count($this->errors))
      {
        echo '<ol>';
        foreach($this->errors as $error)
        {
          echo '<li>'.$error.'</li>';
        }
        echo '</ol>';
      }
      echo '
      </div>
      ';
    }

    if(!$fieldcount) $this->okmessage = SDProfileConfig::$phrases['no_info_available'];
    if(!empty($this->okmessage))
    {
      echo '
      <div class="ucp_okMsg round_corners"><h3>'.$this->okmessage.'</h3></div>
      ';
    }
    if($header)
    {
      echo SDProfileConfig::$group_end_html;
      if($submitButton && $editable_count)
      {
        echo '
        <div id="submit" style="text-align: left; margin-top: 8px;"><button type="submit" class="primaryAction">'.
        strip_tags(SDProfileConfig::$phrases['update_profile']) .'</button>
        </div>
        </form>';
      }
    }

  } //DisplayProfileGroup


  public function PrintProfileValue($val_id) // intended for ajax calls!
  {
    if(empty($val_id)) return;

    $msg = SDProfileConfig::GetMsgObj();

    switch($val_id)
    {
      case 'unread_count':
        echo $msg->getMessageCounts($this->_userid, SDMsg::MSG_INBOX);
        break;
      case 'unread_text':
        if($count = $msg->getMessageCounts($this->_userid, SDMsg::MSG_INBOX | SDMsg::MSG_STATUS_UNREAD))
        {
          echo '<a href="#" name="page_viewmessages" class="profilelink ucp_unread">('.$count.' '.SDProfileConfig::$phrases['lbl_unread'].')</a>';
        }
        break;
    }
  }


  // ##########################################################################
  // DISPLAY PROFILE PAGE ( = current user seeing his own data )
  // ##########################################################################

  public function DisplayProfilePage(&$action, $pageid, $page_url='')
  {
    $doError = false;
    $this->js_output = '';

    //SD370: needs to point to profile page or exit
    if(SDProfileConfig::$_ajax)
    {
      if(!$page_url = (defined('CP_PATH')?CP_PATH:''))
      {
        exit();
      }
      $page_url .= (strpos($page_url,'?')===false?'?':'&amp;').'profile='.$this->_userid;

      //when ajaxed call, the pageid could be a group like "about.me.2": -> "group_2"
      if( (false !== ($groupid = strrchr($pageid,'.'))) &&
          is_numeric(substr($groupid,1)) )
      {
        $pageid = 'group_'.intval(substr($groupid,1));
      }
    }

    // ########################################################################
    // ################### Output specific profile group? #####################
    // ########################################################################
    if(substr($pageid,0,6)=='group_')
    {
      $action = 'ucp_update_user'; $id = intval(substr($pageid,6));
      $group_valid = false;
      SDProfileConfig::init();
      if($this->_userid && !$this->fields_loaded)
      {
        $userdata = $this->LoadUser($this->currentuser(), false);
        SDProfileConfig::SetUserdata($userdata);
        SDProfileConfig::LoadProfileConfig(true,false,false);
      }

      foreach(SDProfileConfig::$profile_groups AS $group)
      {
        if(($pageid == 'group_'.$group['groupname_id']) && ($group['is_visible']))
        {
          $seo = ConvertNewsTitleToUrl($group['displayname'],null,null,true);
          $current_url = $page_url.'&amp;do='.$seo.'.'.(int)$id;
          SDProfileConfig::$group_start_html = str_replace(
            array('$current_url','$page_url','$profile','$page','$token_element'),
            array($current_url, $page_url, $this->currentuser(), $pageid, PrintSecureToken($this->form_token)),
            SDProfileConfig::$group_start_html);
          $group_valid = true;
          $this->DisplayProfileGroup($group, true);
          break;
        }
      }
      $doError = !$group_valid;
      if(!$doError)
      {
        echo PrintSecureToken(SDProfileConfig::$form_prefix . 'token');
      }
      else
      {
        DisplayMessage(array(SDProfileConfig::$phrases['content_not_available']),true);
      }
    }
    ELSE
    // ########################################################################
    // #### Output specific profile page (contents generated by template!)? ###
    // ########################################################################
    if(in_array($action, array('do','loadpage','submit')) &&
       ((SDProfileConfig::$_ajax &&
         ((($tmp = SDProfileConfig::GetPageFragment('page_mycontent'))!=='') ||
          in_array($pageid, SDProfileConfig::$profile_pages))) ||
        (!SDProfileConfig::$_ajax &&
         isset(SDProfileConfig::$profile_pages[$pageid]) ||
         #isset(SDProfileConfig::$profile_pages['page_mycontent']['pages'][$pageid]) ||
         (false !== array_search($pageid,SDProfileConfig::$profile_pages['page_mycontent']['pages'])))
       ))
    {
      //SD370: for ajax the pageid must be switched to the page's key
      //SD370: take into account the "mycontent" pages
      if(SDProfileConfig::$_ajax && ($action=='loadpage'))
      {
        if(false !== ($tmp2 = array_search($pageid,SDProfileConfig::$profile_pages['page_mycontent']['pages'])))
        {
          $pageid = $tmp2;
        }
        else
        if($tmp == $pageid)
          $pageid = 'page_mycontent';
        else
          $pageid = array_search($pageid,SDProfileConfig::$profile_pages);
      }
      if(($pageid === false) ||
         !$this->HandlePageAction($action, $pageid, $this->js_output))
      {
        $doError = true;
      }
    }
    ELSE
    {
      $doError = true;
    }

    // ######################## Prepare extra JS output #######################
    if(!empty($this->errors) || !empty($this->okmessage))
    {
      $this->js_output .= 'jQuery(".ucp_okMsg,.ucp_errorMsg").delay(5000).slideUp();';
    }

    // Common Javascript
    echo '
<script type="text/javascript">
//<![CDATA[
if (typeof jQuery !== "undefined") {
jQuery(document).ready(function() {
    jQuery("#ucp_action").val("'.$action.'");';

    if(!$doError)
    {
      echo '
    jQuery.validator.setDefaults({
      highlight:   function(input) { jQuery(input).addClass("ui-state-highlight"); },
      unhighlight: function(input) { jQuery(input).removeClass("ui-state-highlight"); }
    });
    jQuery("#ucpForm .password").val("");
    jQuery("#ucpForm").validate();
    ';
    }

    echo $this->js_output.'
});
}
//]]>
</script>
';
    return !$doError;

  } //DisplayProfilePage


  // ##########################################################################
  // DISPLAY PUBLIC MEMBER PAGE
  // ##########################################################################

  public function DisplayMemberPage($action, $pageid, $memberid)
  {
    global $DB, $SDCache, $bbcode, $categoryid, $sdlanguage, $sdurl,
           $userinfo, $usersystem, $mainsettings,
           $mainsettings_modrewrite, $mainsettings_url_extension;

    if(empty($memberid) || ($memberid<1) || ($action !== 'memberpage'))
    {
      DisplayMessage(SDProfileConfig::$phrases['msg_invalid_user'],true);
      return false;
    }

    SDProfileConfig::init();

    $userdata = $this->LoadUser($memberid, false);
    if(empty($memberid) || empty($userdata) || empty($userdata['userid']) ||
       empty($userdata['username']) ||
       !empty($userdata['banned']) || !empty($userdata['usergroup_details']['banned']))
       //SD351: do not allow profile display for banned users
    {
      DisplayMessage(SDProfileConfig::$phrases['msg_invalid_user'],true);
      return false;
    }

    //SD351: add to member's profile page views counter
    if(!SD_IS_BOT && !empty($userinfo['userid']) &&
       ($memberid != $userinfo['userid']))
    {
      $userdata['user_profile_views'] = empty($userdata['user_profile_views'])?1:($userdata['user_profile_views']+1);
      $DB->query('UPDATE {users_data} SET user_profile_views = %d'.
                 ' WHERE usersystemid = %d AND userid = %d',
                 $userdata['user_profile_views'], $usersystem['usersystemid'], $memberid);
    }

    SDProfileConfig::SetUserdata($userdata);

    // Init SMARTY template
    $this->_smarty = $this->InitTemplate(true);

    // Pre-process profile groups for member page (public!)
    if(($getsorted = $SDCache->read_var('UCP_MEMBER_GROUPS', 'groups')) === false)
    {
      $profile_groups = SDProfileConfig::GetProfileGroups();
      $group_tabs = array();
      foreach($profile_groups as $group)
      {
        $seo = ConvertNewsTitleToUrl($group['displayname'], null, null, true);
        $profile_groups[$group['groupname_id']]['seo_title'] = $seo;
        if($group['is_visible'] && $group['is_public'] && ($group['groupname_id']>1))
        {
          $group_tabs[$group['displayorder']] = array(
            'group_id'  => $group['groupname_id'],
            'name'      => $group['displayname'],
            'seo_title' => $seo
          );
        }
      }
      if(count($group_tabs)) @ksort($group_tabs);

      $SDCache->write_var('UCP_MEMBER_GROUPS', 'groups', array(
                            'profile_groups' => $profile_groups,
                            'profile_tabs'   => $group_tabs,
                            ), false);
    }
    else
    {
      $group_tabs = $getsorted['profile_tabs'];
      $profile_groups = $getsorted['profile_groups'];
    }
    unset($getgroups);

    $page_url = $profile_url = RewriteLink('index.php?categoryid='.$categoryid).
                ($mainsettings_modrewrite ? '?' : '&amp;');
    $page_url .= 'member='.$memberid;
    $profile_url .= 'profile='.$this->_userid;

    require_once(SD_INCLUDE_PATH.'class_messaging.php');
    $msg = SDProfileConfig::GetMsgObj(); // must have! initializes objects if need be!

    // Prepare extra profile links:
    $this->_smarty->assign('links', array(
      //'member_statistics' => $page_url.'&amp;do=statistics',
      'profile_url' => (!empty($userinfo['userid']) ? $profile_url : ''),
    ));

    $this->_smarty->assign('group_msg_enabled', !empty($userdata['usergroup_details']['msg_enabled']));

    // Additional data for template:
    $data_conf = array(
      'currentdate' => DisplayDate(TIME_NOW, 'Y-m-d G:i', true)
    );
    $this->_smarty->assign('data', $data_conf);

    $usergroup_options = SDProfileConfig::$usergroups_config[$userinfo['usergroupid']];

    //SD344: check for existing profile picture
    $userdata['profile_picture'] = false;
    // If usergroup allows any profile picture option and
    // current user's profile picture is not disabled...
    if(!empty($userdata['usergroup_details']['pub_img_enabled']) &&
       empty($userdata['profile_img_disabled']))
    {
      $picture_upload_allowed = ((int)$usergroup_options['pub_img_enabled'] & 2) > 0;
      $picture_link_allowed   = ((int)$usergroup_options['pub_img_enabled'] & 4) > 0;
      $pub_img_path = isset($userdata['usergroup_details']['pub_img_path'])?
                      (string)$userdata['usergroup_details']['pub_img_path']:false;
      // If picture option is set for local file, path exists and image name is set...
      if(!empty($userdata['profile_img_type']))
      {
        if(($userdata['profile_img_type']==1) &&
           ($pub_img_path !== false))
        {
          // Check for local picture file
          require_once(SD_INCLUDE_PATH.'class_sd_media.php');
          if($pub_img_path = SD_Media::FixPath($pub_img_path))
          {
            $pub_img_path = rtrim($pub_img_path,'/').'/'.floor($memberid / 1000).'/';
            if($pub_img_path = is_dir(ROOT_PATH.$pub_img_path) ? $pub_img_path : false)
            if(is_file(ROOT_PATH.$pub_img_path.$userdata['user_profile_img']))
            {
              $userdata['profile_picture'] = $pub_img_path.$userdata['user_profile_img'];
            }
          }
        }
        else
        if(($userdata['profile_img_type']==2) &&
           !empty($userdata['profile_img_link']) &&
           sd_check_image_url($userdata['profile_img_link']))
        {
          $userdata['profile_picture'] = $userdata['profile_img_link'];
        }
      }
    }

    $userdata['avatar']      = ($memberid ? ForumAvatar($memberid,'') : '');
    $userdata['displayname'] = !empty($userdata['user_screen_name']) ? $userdata['user_screen_name'] : (!empty($userdata['username'])?$userdata['username']:'');
    $userdata['user_text']   = $this->GetUserDisplayValue($userdata, array('name'=>'user_text','type'=>'bbcode'));
    $userdata['joindate']    = DisplayDate($userdata['joindate'],'Y-m-d');
    $userdata['usergroup_details']['displayname'] =
      !empty($userdata['usergroup_details']['displayname'])?$userdata['usergroup_details']['displayname']:$userdata['usergroup_details']['name'];

    if(!empty($userdata['user_allow_viewonline']))
    {
      $userdata['online'] = SDProfileConfig::GetOnlineImage($memberid);
    }
    //SD343: fetch users' "likes"
    require_once(SD_INCLUDE_PATH.'class_sd_likes.php');
    $userdata['likes_comments'] = SD_Likes::GetUserLikesCount($memberid,SD_LIKED_TYPE_COMMENT);
    $userdata['likes_posts'] = SD_Likes::GetUserLikesCount($memberid,SD_LIKED_TYPE_POST);
    $liked_posts_count = 0;
    $liked_posts_likes = 0;
    if($forumid = GetPluginID('Forum'))
    {
      if($tmp = SD_Likes::GetUserLikedCount($memberid,SD_LIKED_TYPE_POST,$forumid))
      {
        $userdata['liked_posts_count'] = $tmp['likesitems'];
        $userdata['liked_posts_likes'] = $tmp['likescount'];
      }
    }

    if(false !== ($groupid = strrchr($pageid,'.')))
    {
      $groupid = substr($groupid,1);
      if($isgroup = is_numeric($groupid))
      {
        $pageid = intval($groupid);
      }
    }
    else
    {
      $pageid = false;
    }
    if(!$pageid) $pageid = $groupid = 1; // default to first group if false

    // Remove non-permitted tabs before passing to template:
    $valid_tabs = array();
    foreach($group_tabs as $sortid => $g)
    {
      $grp = $profile_groups[$g['group_id']];
      $perm = $grp['access_view'];
      if(!empty($grp['is_visible']) && !empty($grp['is_public']) &&
         (empty($perm) || (strpos($perm,'|'.$userinfo['usergroupid'].'|')!==false)) )
      {
        $valid_tabs[$sortid] = $g;
      }
    }

    $visible_count = 0;
    if(!empty($pageid) && array_key_exists($pageid,$profile_groups) && $profile_groups[$pageid]['is_public'])
    {
      //$this->_smarty->assign('token_element', PrintSecureToken(SDProfileConfig::$form_prefix . 'token'));
      $header = false;
      $group  = $profile_groups[$pageid];
      $fields = SDProfileConfig::$profile_group_fields;
      $public_fields = array();
      $user_public_fields = empty($userdata['public_fields']) ? array() : @explode(',',$userdata['public_fields']);

      // Iterate through all fields to determine, which of the groups are
      // actually contain publicly viewable fields (allowed by admin AND user)
      foreach($fields AS $group_id => $fields)
      foreach($fields AS $field_idx)
      {
        if(!$field = SDProfileConfig::GetProfileFieldByIdx($field_idx)) continue;
        $fieldnum = isset($field['fieldnum']) ? (int)$field['fieldnum'] : 0;
        $perm = $profile_groups[$field['groupname_id']]['access_view'];
        $field_show = !empty($fieldnum) && empty($field['ucp_only']) &&
                      (!strlen($perm) || (strpos($perm,'|'.$userinfo['usergroupid'].'|')!==false)) &&
                      (!empty($field['public_req']) || !empty($field['visible'])) &&
                      !empty($field['public']) &&
                      (!empty($field['public_req']) || count($user_public_fields) && in_array($fieldnum, $user_public_fields));
        if($field_show)
        {
          // Mark group as displayable, i.e. it contains at least one field
          $valid_tabs[$profile_groups[$field['groupname_id']]['displayorder']]['show'] = 1;
          $visible_count++;
        }
        if(($field['groupname_id']==1) || ($field['groupname_id'] != $groupid)) continue;

        if($field_show)
        {
          if(!$header)
          {
            $header = true;
            // TODO: place any header-like output here
            $this->_smarty->assign('fields_title', $profile_groups[$field['groupname_id']]['displayname']);
          }
          if($field['name']=='user_birthday')
          {
            $ud = SDProfileConfig::GetUserdata();
            if(isset($ud['user_birthday_mode']))
            {
              switch($ud['user_birthday_mode'])
              {
                case 1:
                  $val = DisplayDate($userdata['user_birthday'],'F j');
                  break;
                case 2:
                  $val = DisplayDate($userdata['user_birthday'],'Y-m-d');
                  break;
                default: // display age
                  $val = floor((TIME_NOW - $userdata['user_birthday'])/86400/365);
                  break;
              }
            }
          }
          else
          {
            $val = $this->GetUserDisplayValue($userdata, $field);
          }

          $public_fields[$field['name']] = array(
            'id'     => $fieldnum,
            'form_id'=> $field['id'],
            'order'  => $field['fieldorder'],
            'name'   => $field['name'],
            'label'  => $field['label'],
            'type'   => $field['type'],
            'value'  => $val
            );
        }
      }
      if($header)
      {
        $this->_smarty->assign('fields', $public_fields);
      }
    }
    $this->_smarty->assign('memberid', $memberid);
    $this->_smarty->assign('member_data', $userdata);

    // Other required data:
    $this->_smarty->assign('groups_visible_count', $visible_count);
    $this->_smarty->assign('groups_sorted', $valid_tabs);
    $this->_smarty->assign('current_url',   $page_url);
    $this->_smarty->assign('page_url',      $page_url);

    // BIND AND DISPLAY TEMPLATE NOW
    // Check if custom version exists, otherwise use default template:
    //SD344: new template handling
    $tmpl_done = false;
    $tpls = array();
    if(!empty(SDProfileConfig::$settings['member_template']))
    {
      $tpls[] = SDProfileConfig::$settings['member_template'];
    }
    $tpls[] = 'member.tpl';
    $tmpl_done = SD_Smarty::display(11, $tpls, $this->_smarty); //SD351: added 3rd param
    if(!$tmpl_done)
    {
      if($userinfo['adminaccess']) echo '<p><strong>Member Page: template error
      OR template not found (member.tpl)!<br />Please check Skins|Templates or
      "/includes/tmpl/defaults" folder!</strong></p>';
    }

  } //DisplayMemberPage


  // ##########################################################################
  // DisplayProfileInput
  // ##########################################################################

  public function DisplayProfileInput(array $input, $public_option=true)
  {
    global $bbcode, $mainsettings, $sdlanguage;

    if(!is_array($input) || (!defined('IN_ADMIN') && empty($input['visible']))) return;

    if($input['name']=='p_receive_emails')
    {
      $input_value = SDProfileConfig::GetFieldValue('receive_emails');
    }
    else
    {
      $input_value = SDProfileConfig::GetFieldValue($input['name']);
    }
	
	// required?
	$infoClass = '';
	$iconClass = FALSE;
	if(!empty($public_option) && !empty($input['required']))
	{
		$infoClass = " has-error bolder";
		$iconClass = TRUE;
		
	}
	
    $extraClass = 'ctrlHolder';
    $output = '<div class="form-group '. $infoClass.'">
				<label class="control-label col-sm-2 for="'.$input['id'].'">'.$input['label'].'</label>
				<div class="col-sm-8">';
    switch($input['type'])
    {
      case 'checkbox' :
        $extraClass .= ' noLabel';
        $checked = empty($input_value/*$input['checked']*/) ? '' : ' checked="checked"';
        $output .= '<input name="'.$input['name'].'" id="'.$input['id'].'" value="'.$input['value'].'" type="checkbox" class="ace" '.$checked.' /> '.$input['label'].'<span class="lbl"></span>';
        break;
      case 'yesno' :
        $extraClass .= ' noLabel';
        $output .= '<select class="form-control" name="'.$input['name'].'" id="'.$input['id'].'">
          <option value="1" id="'.$input['id'].'"'.(!empty($input_value)?' selected ':'').'/>'.$sdlanguage['yes'].'</option>
          <option value="0" '.( empty($input_value)?'selected" ':'').'/>'.$sdlanguage['no'].'</option>
		  </select>
        ';
        break;
      case 'radio' :
        $extraClass .= ' noLabel';
        $output .= '
		  <input name="'.$input['name'].'" class="ace" id="'.$input['id'].'" type="radio" value="0" /><span class="lbl"> '.$sdlanguage['no'].'</span><br />
         <label for="'.$input['id'].'"><input name="'.$input['name'].'" class="ace" id="'.$input['id'].'" type="radio" value="'.$input_value.'" /><span class="lbl"> '.$input['label'].'</span>
 		';
        break;

      case 'timezone':
        $output .= 
        GetTimezoneSelect($input['name'], $input_value, $input['id'], 'selectInput');
        $output .= '<span class="helper-text">(Site Date: '.date('Y-m-d, G:i',TIME_NOW).' - DST: ' . (date('I') ? 'on' : 'off').')</span>'; // 1 or 0
        break;
      case 'dateformat':
      case 'select':
        $output .= '
        <select name="'.$input['name'].'" id="'.$input['id'].'" class="form-control selectInput">';
        $options = '';
        $option_selected = false;
        if($input['type']=='dateformat')
        {
          $input['options'] = array(
            'l, F j, Y g:ia' => DisplayDate(TIME_NOW, 'l, F j, Y g:ia', false, false, true),
            'l, F j, Y G:i'  => DisplayDate(TIME_NOW, 'l, F j, Y G:i',  false, false, true),
            'F j, Y g:ia'    => DisplayDate(TIME_NOW, 'F j, Y g:ia',    false, false, true),
            'F j, Y G:i'     => DisplayDate(TIME_NOW, 'F j, Y G:i',     false, false, true),
            'Y-m-d, g:ia'    => DisplayDate(TIME_NOW, 'Y-m-d, g:ia',    false, false, true),
            'Y-m-d, G:i'     => DisplayDate(TIME_NOW, 'Y-m-d, G:i',     false, false, true),
            'l, Y-m-d, G:i'  => DisplayDate(TIME_NOW, 'l, Y-m-d, G:i',  false, false, true),
            'd.m.Y, G:i'     => DisplayDate(TIME_NOW, 'd.m.Y, G:i',     false, false, true),
            'l, d.m.Y, G:i'  => DisplayDate(TIME_NOW, 'l, d.m.Y, G:i',  false, false, true),
            'c'              => DisplayDate(TIME_NOW, 'c',  false),
          );
        }
        if(isset($input['options']))
        {
          foreach($input['options'] as $value => $option)
          {
            if(isset($input_value) && ($value==$input_value) && !$option_selected)
            {
              $option_selected = true;
              $options .= '
            <option value="'.$value.'" selected="selected">'.$option.'</option>';
            }
            else
            {
              $options .= '
            <option value="'.$value.'">'.$option.'</option>';
            }
          }
        }
        if(!$option_selected)
        {
          $options = '
            <option value="" selected="selected">-</option>' . "\n" . $options;
        }
        $output .= $options . '
        </select>
        ';
        break;
      case 'bbcode':
        global $mainsettings_allow_bbcode;
        $allow_bbcode = !empty($mainsettings_allow_bbcode) && isset($bbcode) && ($bbcode instanceof BBCode);
        $output .= '
        <textarea class="'.SDProfileConfig::$form_prefix.(!$allow_bbcode?'no':'').'bbcode form-control" name="'.$input['name'].'" id="'.$input['id'].'" style="width:100%;" rows="6" >'.$input_value.'</textarea>
        ';
        if($allow_bbcode)
        {
          $preview = $bbcode->Parse($input_value);
          $output .= '<br />
          <div class="ucp_color_light bbcode_preview" style="clear:both;padding:8px">'.$preview.'</div>
          ';
        }
        break;
      case 'textarea':
        $output .= '
        <textarea class="form-conrol" name="'.$input['name'].'" id="'.$input['id'].'">'.$input_value.'</textarea>';
        break;
      case 'decimal':
      case 'date':
      case 'ext_aim':
      case 'ext_fb':
      case 'ext_googletalk':
      case 'ext_icq':
      case 'ext_msnm':
      case 'ext_skype':
      case 'ext_twitter':
      case 'ext_yim':
      case 'hidden':
      case 'password':
      case 'text':
      default:
        $type = 'textInput';
        $inputClass = '';
        if($input['type']=='email') $inputClass .= ' email';
        if($input['type']=='url') $inputClass .= ' url';
        if($input['type']=='date')
        {
          $input['maxlength'] = 10;
          if(empty($input_value)) $input_value = '';
          if(!strlen($this->js_output))
          {
          }
          if(empty($input['readonly']) || defined('IN_ADMIN'))
          {
            $this->js_output .= "\n      ".
            'var dvalue = jQuery("#'.$input['id'].'").attr("rel") * 1000;
            '.(!empty($input_value) ? 'var dc = new Date(dvalue);' : '') . '
            $("#'.$input['id'].'_date").datepicker();';
          }
          // alternate field to store date in ISO format
          $output .= '<input type="hidden" id="'.$input['id'].'" name="'.$input['name'].'" rel="'.$input_value.'" value="' .
          (!empty($input_value) ? DisplayDate($input_value, '', true) : '') . '" />
          ';
          $input['id'] = $input['id'].'_date';
          $input['name'] = $input['name'].'_date';
          if(!empty($input['readonly']) && !defined('IN_ADMIN'))
          {
            if($input['type']=='date')
            {
              $input_value = DisplayDate($input_value);
            }
            else
            {
              $input_value = '';
            }
          }
        }
        if($input['type'] == 'password')
        {
          $inputClass .= ' password';
          $input_value = '';
        }
        else
        {
          $input['type'] = 'text';
        }
        if(!empty($input['required']))
        {
          $inputClass .= ' required';
        }

        if($input['type']!='hidden')
        {
          // If profile display then check "required", else assume registration
          // form and check "reg_form_req"!
          if( (!empty($public_option) && !empty($input['required'])) ||
              ( empty($public_option) && !empty($input['reg_form_req'])) )
          {
            $output .= !defined('IN_ADMIN') ? ' *' : '';
          }
         
        }

        if(empty($input['readonly']) || defined('IN_ADMIN'))
        {
          $input['maxlength'] = !isset($input['maxlength']) ? 45 : (int)$input['maxlength'];
          $output .= '
        <input size="'.($input['maxlength']>45?45:(int)$input['maxlength']).'" name="'.$input['name'].'" '.
          (empty($input['id']) ? '' : 'id="'.$input['id'].'"').
          (empty($input['maxlength'])?'':' maxlength="'.$input['maxlength'].'"').
          ' value="'.$input_value.'"'.
          ' type="'.$input['type'].'"';
          if($input['type'] != 'hidden')
          {
            $output .= ' class="form-control textInput auto'.$inputClass.'"';
          }
          $output .= ' />';
        }
        else
        {
          $output .= '<span id="'.$input['id'].'" class="'.SDProfileConfig::$form_prefix.'value_only">'.$input_value.'</span>';
        }
        break;
    }
    if($input['type']!='hidden')
    {
     
      // Show checkbox for "public" option?
      if(!empty($public_option) && ($input['groupname_id']!=1))
      {
        // Field config value for member page only allowed if field is not defined as ucp-only!
        if(empty($input['fieldnum']) || !empty($input['ucp_only']) || empty($input['public']) || !empty($input['public_req']))
        {
          $output .= '</div><div class="col-sm-1">
          <i class="fa fa-minus-circle red bigger-120"></i></div>';
        }
        else
        {
          $output .= '</div>
		  <div class="col-sm-1">
            <input type="checkbox" class="ace tooltip" data-rel="tooltip" title="'.AdminPhrase('profiles_col_public_input').'" '.(empty($input['ucp_only']) ? '' : 'disabled="disabled"').
              ' name="public['.$input['fieldnum'].']" value="'.$input['fieldnum'].'"'.
              (!empty($input['user_config_public']) ? 'checked="checked"' : '').' /><span class="lbl"></span></div>';
          // above "user_config_public" is an artificial value generated during pageload!
		}
		
		
      }
	  else
	  {
		  $output .= '</div>';
	  }
	  
      echo '
      '.$output.'
	 </div>

	';
    }

  } //DisplayProfileInput


  public function GetProfileInputForTemplate(array $input, $public_option=true) //SD344
  {
    global $bbcode, $mainsettings, $sdlanguage;

    if(!is_array($input) || (!defined('IN_ADMIN') && empty($input['visible']))) return;

    if($input['name']=='p_receive_emails')
    {
      $input_value = SDProfileConfig::GetFieldValue('receive_emails');
    }
    else
    {
      $input_value = SDProfileConfig::GetFieldValue($input['name']);
    }

    $field = array(
      'outer_class' => 'ctrlHolder',
      'input_id'    => $input['id'],
      'input_name'  => $input['name'],
      'input_class' => '',
      'input_type'  => $input['type'],
      'input_value' => (isset($input['value'])?(string)$input['value']:''),
      'input_attr'  => (empty($input_value)?'':' checked="checked" '),
      'label_text'  => $input['label'],
      'extra_html'  => '',
      'do_honepot'  => false
    );

    $extraClass = 'ctrlHolder';
    $output = '';
    switch($input['type'])
    {
      case 'checkbox' :
        $field['input_attr'] = (empty($input['value']) ? '' : ' checked="checked" ');
        break;
      case 'yesno' :
        break;
      case 'radio' :
        $field['outer_class'] .= ' noLabel';
        break;
      case 'timezone':
        $field['extra_html'] = GetTimezoneSelect($input['name'], $input_value, $input['id'], 'selectInput');
        break;
      case 'dateformat':
      case 'select':
        $field['extra_html'] = '
        <select name="'.$input['name'].'" id="'.$input['id'].'" class="selectInput">';
        $options = '';
        $option_selected = false;
        if($input['type']=='dateformat')
        {
          $input['options'] = array(
            'l, F j, Y g:ia' => DisplayDate(TIME_NOW, 'l, F j, Y g:ia', false, false, true),
            'l, F j, Y G:i'  => DisplayDate(TIME_NOW, 'l, F j, Y G:i',  false, false, true),
            'F j, Y g:ia'    => DisplayDate(TIME_NOW, 'F j, Y g:ia',    false, false, true),
            'F j, Y G:i'     => DisplayDate(TIME_NOW, 'F j, Y G:i',     false, false, true),
            'Y-m-d, g:ia'    => DisplayDate(TIME_NOW, 'Y-m-d, g:ia',    false, false, true),
            'Y-m-d, G:i'     => DisplayDate(TIME_NOW, 'Y-m-d, G:i',     false, false, true),
            'l, Y-m-d, G:i'  => DisplayDate(TIME_NOW, 'l, Y-m-d, G:i',  false, false, true),
            'd.m.Y, G:i'     => DisplayDate(TIME_NOW, 'd.m.Y, G:i',     false, false, true),
            'l, d.m.Y, G:i'  => DisplayDate(TIME_NOW, 'l, d.m.Y, G:i',  false, false, true),
            'c'              => DisplayDate(TIME_NOW, 'c',  false),
          );
        }
        if(isset($input['options']))
        {
          foreach($input['options'] as $value => $option)
          {
            if(isset($input_value) && ($value==$input_value) && !$option_selected)
            {
              $option_selected = true;
              $options .= '
            <option value="'.$value.'" selected="selected">'.$option.'</option>';
            }
            else
            {
              $options .= '
            <option value="'.$value.'">'.$option.'</option>';
            }
          }
        }
        if(!$option_selected)
        {
          $options = '
            <option value="" selected="selected">-</option>' . "\n" . $options;
        }
        $field['extra_html'] .= $options . '
        </select>
        ';
        break;
      case 'bbcode':
        global $mainsettings_allow_bbcode;
        $allow_bbcode = !empty($mainsettings_allow_bbcode) && isset($bbcode) && ($bbcode instanceof BBCode);
        /*$field['extra_html'] = '
        <textarea class="'.SDProfileConfig::$form_prefix.(!$allow_bbcode?'no':'').'bbcode" name="'.$input['name'].'" id="'.$input['id'].'" rows="6" cols="80">'.$input_value.'</textarea>
        ';*/
        $field['input_class'] = SDProfileConfig::$form_prefix.(!$allow_bbcode?'no':'').'bbcode';
        break;
      case 'textarea':
        break;
      case 'decimal':
      case 'date':
      case 'ext_aim':
      case 'ext_fb':
      case 'ext_googletalk':
      case 'ext_icq':
      case 'ext_msnm':
      case 'ext_skype':
      case 'ext_twitter':
      case 'ext_yim':
      case 'hidden':
      case 'password':
      case 'text':
      default:
        $type = 'textInput';
        if($input['type']=='email') $field['input_class'] .= ' email';
        if($input['type']=='url') $field['input_class'] .= ' url';
        if($input['type']=='date')
        {
          $input['maxlength'] = 10;
          if(empty($input_value)) $input_value = '';
          if(!strlen($this->js_output))
          {
            $this->js_output .= "\n      ".
            'var ucp_datePickerOptions = { altFormat: "yyyy-mm-dd", yearRange: "1900:2020", showTrigger: "<"+"img alt=\'...\' src=\''.SD_INCLUDE_PATH.'css/images/calendar.png\' width=\'16\' height=\'16\' />"};';
          }
          if(empty($input['readonly']))
          {
            $this->js_output .= "\n      ".
            'var dvalue = jQuery("#'.$input['id'].'").attr("rel") * 1000;
            var dc = new Date(dvalue);
            jQuery("#'.$input['id'].'_date").datepick(
              jQuery.extend(ucp_datePickerOptions, {
                '.(!empty($input_value) ? 'defaultDate: dc,' : '') . ' altField: "#'.$input['id'].'"
              })
            );
            if((dvalue != 0) && (typeof(dc) !== "undefined")) { jQuery("#'.$input['id'].'_date").datepick("setDate", dc); }';
          }
          // alternate field to store date in ISO format
          $field['extra_html'] = '<input type="hidden" id="'.$input['id'].'" name="'.$input['name'].'" rel="'.$input_value.'" value="' .
                     (!empty($input_value) ? DisplayDate($input_value, '', true) : '') . '" />';
          $field['input_id'] = $input['id'].'_date';
          $field['input_name'] = $input['name'].'_date';
          if(!empty($input['readonly']) && !defined('IN_ADMIN'))
          {
            $field['input_value'] = DisplayDate($input_value);
          }
        }
        if($input['type'] == 'password')
        {
          $field['input_class'] .= ' password';
          $field['input_value'] = '';
        }
        else
        {
          $field['input_type'] = 'text';
        }
        if(!empty($input['required']) && !defined('IN_ADMIN'))
        {
          $field['input_class'] .= ' required';
        }

        if($input['type'] != 'hidden')
        {
          // If profile display then check "required", else assume registration
          // form and check "reg_form_req"!
          if( (!empty($public_option) && !empty($input['required'])) ||
              ( empty($public_option) && !empty($input['reg_form_req'])) )
          {
            $field['input_required'] = true;
          }
        }

        if(empty($input['readonly']) || defined('IN_ADMIN'))
        {
          $maxsize = ((!isset($input['maxlength']) || ((int)$input['maxlength']>45)) ? 45 : (int)$input['maxlength']);
          $field['input_attr'] = ' size="'.$maxsize.'" ';
          if(!empty($input['maxlength']) && ((int)$input['maxlength'] > 0))
          {
            $field['input_attr'] .= ' maxlength="'.(int)$input['maxlength'].'" ';
          }
          if($input['type'] != 'hidden')
          {
            $field['input_class'] = 'textInput auto'.$field['input_class'];
          }
          #$output .= ' />';
        }
        else
        {
          #$output .= '<span id="'.$input['id'].'" class="'.SDProfileConfig::$form_prefix.'value_only">'.$input_value.'</span>';
        }
        break;
    }

    return $field;

  } //GetProfileInputForTemplate


  // ##########################################################################
  // GetUserDisplayValue
  // ##########################################################################

  public function GetUserDisplayValue($userdata, array $input)
  {
    global $bbcode, $sdlanguage, $sdurl;

    if(!is_array($input) || empty($input)) return '';

    $input_value = isset($userdata[$input['name']]) ? $userdata[$input['name']] : '';
    $output = '';
    switch($input['type'])
    {
      case 'checkbox' :
        $checked = empty($input_value) ? '' : ' checked="checked"';
        $output = '<input type="checkbox" disabled="disabled" value="'.$input['value'].'" '.$checked.' />';
        break;
      case 'yesno' :
        $output = !empty($input_value)?$sdlanguage['yes']:$sdlanguage['no'];
        break;
      case 'radio' :
        $output = empty($input_value)?$sdlanguage['no']:$input['label'];
        break;
      case 'select' :
        $output = isset($input['options'][$input_value])?$input['options'][$input_value]:'';
        break;
      case 'bbcode' :
        global $mainsettings_allow_bbcode;
        if(!empty($mainsettings_allow_bbcode) && isset($bbcode) && ($bbcode instanceof BBCode))
        {
          $output = $bbcode->Parse($input_value);
          $output = preg_replace('#<a href="http([s]?)://(.*?)"#', '<a href="http$1://$2" rel="nofollow"', $output);
        }
        else
        {
          $output = $input_value;
        }
        break;
      case 'textarea' :
        $output = $input_value;
        break;
      case 'currency':
      case 'decimal':
      case 'whole_number':
        $output = floatval($input_value);
        break;
      case 'date':
        $output = DisplayDate($input_value, 'Y-m-d');
        break;
      case 'hidden':
      case 'password':
        break;
      case 'ext_aim':
      case 'ext_fb':
      case 'ext_googletalk':
      case 'ext_icq':
      case 'ext_msnm':
      case 'ext_skype':
      case 'ext_twitter':
      case 'ext_yim':
        $output = self::GetMessengingLink($input['type'], $input_value);
        break;
      case 'url':
        $output = '<a rel="nofollow" href="'.$input_value.'" target="_blank">'.$input_value.'</a>';
        break;
      case 'text':
      default:
        $output = $input_value;
        break;
    }
    return $output;

  } //GetUserDisplayValue

  public static function GetMessengingLink($type, $value, $labeled=true)
  {
    if(empty($type) ||empty($value)) return '';
    $disp = '';
    if($labeled = !empty($labeled))
    {
      $disp = ' '.$value;
    }

    $output = '';
    switch($type)
    {
      case 'ext_aim':
        if(strlen($value))
        $output = '<a rel="nofollow" href="aim:goim?screenname='.$value.'" target="_blank"><img src="'.SITE_URL.'includes/images/aim.png" style="border: none;" width="16" height="16" alt="AIM" />'.$disp.'</a>';
        break;
      case 'ext_facebook':
      case 'ext_fb':
        if(strlen($value))
        $output = '<a rel="nofollow" href="'.((strpos($value,'http')===false?'http://www.facebook.com/':'').$value).
                  '" target="_blank"><img src="'.SITE_URL.'includes/images/fb.jpg" style="border: none;" width="16" height="16" alt="Facebook" />'.$disp.'</a>';
        break;
      case 'ext_googletalk':
        if(strlen($value))
        $output = '<a rel="nofollow" href="gtalk:chat?jid='.$value.'" target="_blank"><img src="'.
                  SITE_URL.'includes/images/google-talk.png" style="border: none;" width="16" height="16" alt="Google Talk" />'.$disp.'</a>';
        break;
      case 'ext_icq':
        if(strlen($value))
        $output = '<a rel="nofollow" href="http://www.icq.com/people/'.$value.'" target="_blank"><img src="'.
                  SITE_URL.'includes/images/icq.png" style="border: none;" width="16" height="16" alt="ICQ" />'.$disp.'</a>';
        break;
      case 'ext_msnm':
        if(strlen($value))
        $output = '<a rel="nofollow" href="msnim:chat?contact='.$value.'" target="_blank"><img src="'.
                  SITE_URL.'includes/images/msn.png" style="border: none;" width="16" height="16" alt="MSN" />'.$disp.'</a>';
        break;
      case 'ext_skype':
        if(strlen($value))
        $output = '<!-- Skype \'Skype Me™!\' button http://www.skype.com/go/skypebuttons -->
<script type="text/javascript" src="http://download.skype.com/share/skypebuttons/js/skypeCheck.js"></script>
<a rel="nofollow" target="_blank" href="skype:'.$value.'?call"><img src="'.
          SITE_URL.'includes/images/skype_34.png" style="border: none;" width="16" height="16" alt="Skype" /></a>'.$disp;
        break;
      case 'ext_twitter':
        if(strlen($value))
        $output = '<a rel="nofollow" href="'.((strpos($value,'http')===false?'http://www.twitter.com/':'').$value).
                  '" target="_blank"><img src="'.SITE_URL.'includes/images/twitter_small.png" style="border: none;" width="16" height="16" alt="Twitter" />'.$disp.'</a>';
        break;
      case 'ext_yim':
        if(strlen($value))
        $output = '<a rel="nofollow" href="ymsgr:sendim?'.$value.'" target="_blank"><img src="'.
                  SITE_URL.'includes/images/yim.png" style="border: none;" width="16" height="16" alt="Yahoo" />'.$disp.'</a>';
        break;
    }
    return $output;
  }

  // ##########################################################################
  // UPDATE PROFILE
  // ##########################################################################

  public function UpdateProfile($onlyExtraData=false, $onlyCredentials=false, $overrideUserID=0)
  {
    global $DB, $categoryid, $refreshpage, $sdlanguage, $phrases, $userinfo;
	

    if(!CheckFormToken($this->form_token) && !defined('IN_ADMIN'))
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

    if(defined('IN_ADMIN') && !empty($overrideUserID) && is_numeric($overrideUserID) && ($overrideUserID > 0))
    {
      $this->_userid = (int)$overrideUserID;
    }
    if(empty($this->_userid)) return false;

    $this->errors = array();

    if(empty($onlyExtraData) && (SDProfileConfig::$usersystem['name'] == 'Subdreamer') && isset($_POST['p_new_password']))
    {
      $new_password = sd_unhtmlspecialchars(GetVar('p_new_password', '', 'string', true, false));
      $confirm_new_password = sd_unhtmlspecialchars(GetVar('p_confirm_new_password', '', 'string', true, false));

      //SD360: respect new option to disable email editing
      $new_email = $confirm_new_email = '';
      if(empty(SDProfileConfig::$settings['disable_email_editing']))
      {
        $new_email = GetVar('p_new_email', '', 'string', true, false);
        $confirm_new_email = GetVar('p_confirm_new_email', '', 'string', true, false);
      }

      $min_pwd_length = SDProfileConfig::$p12_settings['min_password_length'];
      $min_pwd_length = (empty($min_pwd_length) || $min_pwd_length < 5) ? 5 : (int)$min_pwd_length;

      $receive_emails = GetVar('p_receive_emails', 0, 'bool', true, false);

      if(!strlen($new_password) && !strlen($new_email))
      {
        //do nothing
      }
      else
      {
        if(strlen($new_password))
        {
          // SD313: check min length of password corresponding to Registration settings
          if(strlen($new_password) < $min_pwd_length)
          {
            $this->errors[] = str_replace('#d#', $min_pwd_length, SDProfileConfig::$p12_phrases['password_too_short']);
          }
          else
          // SD313: check for invalid characters in password
          if($new_password != preg_replace('#[ %\/_\'"\s]+#im','',$new_password))
          {
            $this->errors[] = SDProfileConfig::$p12_phrases['enter_alnum_password'];
          }
          else
          if(strcmp($new_password, $confirm_new_password) != 0) // 0 means they are a match
          {
            $this->errors[] = SDProfileConfig::$phrases['password_unmatched'];
          }
        }

        // SD313: min. length of email is 6 characters
        if( empty(SDProfileConfig::$settings['disable_email_editing']) &&
            ($email_len = strlen($new_email)) )
        {
          // Check to make sure they entered a valid email address
          if(($email_len < 6) || !IsValidEmail($new_email))
          {
            $this->errors[] = SDProfileConfig::$phrases['enter_valid_email'];
          }
          else if(strcmp($new_email, $confirm_new_email) != 0) // 0 means they are a match
          {
            $this->errors[] = SDProfileConfig::$phrases['email_unmatched'];
          }
          else
          if($DB->query_first("SELECT email FROM {users} WHERE email = '$new_email' ".
                              "AND userid != %d LIMIT 1", $this->_userid))
          {
            $this->errors[] = $new_email . ' ' . SDProfileConfig::$phrases['email_already_exists'];
          }
        }
      }
    }

    // Lists with updateable fields and their values
    if(empty($onlyCredentials))
    {
      $upd_fields = array();

      // #####################################################
      // Load all form fields (table users_fields)
      // #####################################################
      //$this->LoadFormFields();
      SDProfileConfig::init();

      $userdata = SDProfileConfig::GetUserdata();
      $public_fields = empty($userdata['public_fields']) ? array() : @explode(',',$userdata['public_fields']);
      $upd_fields_users = array();
      $uname_changed = false;
      $p12_username = '';

      // #####################################################
      // Process any visible/editable fields from user_fields
      // #####################################################
      $logged = false;
      foreach(SDProfileConfig::$profile_fields AS $field)
      {
        if($logged || (!$target_group = SDProfileConfig::$profile_groups[$field['groupname_id']]))
        {
          continue;
        }
        /* If data is submitted by frontpage user, then check if the profile group,
           to which the current field is assigned to, actually has access to.
           If not, it is most likely a spammer, such as POST'ing a "website" value.
           Also, if user is a guest (not logged in), we should assume, that the
           data was intended for the User Registration plugin.
        */
        if(!defined('IN_ADMIN') && isset($_POST[$field['name']]))
        {
          $do_reject = false;
          $msg = $subject = '';
          // ...otherwise check if a guest had just now submitted a registration form
          // for which the currently processed field was not marked for (reg_form)
          if( !$logged && !empty($overrideUserID) && (substr($this->form_token,0,3)=='p12'))
          {
            if(empty($field['reg_form']))
            {
              $do_reject = true;
              $subject = 'Registration';
              $msg = 'registering user submitted data for protected field: <strong>'.$field['name'].'</strong>, <strong>IP:</strong> '.USERIP;
            }
          }
          else
          // Check if the user has simply no permission for the field...
          if(!$logged && strlen($target_group['access_view']) && ($perm = explode('|',$target_group['access_view'])))
          {
            if($do_reject = !in_array($userinfo['usergroupid'],$perm))
            {
              $subject = 'Profile';
              if(empty($userinfo['loggedin']))
              {
                $msg = 'Guest posted data for protected field: <strong>'.$field['name'].'</strong>'.
                       ', <strong>IP:</strong> '.USERIP;
              }
              else
              {
                $msg = 'User <strong>'.$userinfo['username'].'<strong> posted data for protected field: <strong>'.
                       $field['name'].'</strong>, <strong>IP:</strong> '.USERIP;
              }
            }
          }
          if($do_reject && !$logged)
          {
            $logged = true;
            $this->errors[] = 'Invalid data!';
            Watchdog($subject, 'Spam: '.$msg, WATCHDOG_WARNING);
            continue;
          }
        }

        //SD370: allow username changing (usergroup permission)
        //Taking into account most p12 settings to obey site rules
        if( ($disUsername = ($field['name']=='username')) &&
            isset($userdata['usergroupid']) && isset($_POST['p_new_password']) &&
            (!empty(SDProfileConfig::$usergroups_config[$userdata['usergroupid']]['allow_uname_change'])))
        {
          $p12_username_org = isset($_POST[$field['name']])?unhtmlspecialchars($_POST[$field['name']]):'';
          $p12_username_org = ($p12_username_org == SanitizeInputForSQLSearch($p12_username_org,false,false) ? $p12_username_org : '');

          if(!empty($p12_username_org) && !sd_IsUsernameInvalid($p12_username_org))
          {
            $p12_username = $DB->escape_string(trim($p12_username_org));
            if(empty($p12_username))
            {
              $this->errors[] = SDProfileConfig::$p12_phrases['invalid_username'];
            }
            else
            if(strlen($p12_username) && ($p12_username != $userinfo['username']))
            {
              $max_usr_length = empty(SDProfileConfig::$p12_settings['min_password_length'])?0:(int)SDProfileConfig::$p12_settings['max_username_length'];
              $max_usr_length = Is_Valid_Number($max_usr_length,13,13,64);

              $min_usr_length = empty(SDProfileConfig::$p12_settings['min_username_length'])?0:(int)SDProfileConfig::$p12_settings['min_username_length'];
              $min_usr_length = Is_Valid_Number($min_usr_length,3,3,20);

              if(strlen($p12_username) < $min_usr_length)
              {
                $this->errors[] = str_replace('#d#', $min_usr_length, SDProfileConfig::$p12_phrases['username_too_short']);
              }
              else
              if(strlen($p12_username) > $max_usr_length)
              {
                $this->errors[] = str_replace('#d#', $max_usr_length, SDProfileConfig::$p12_phrases['username_too_long']);
              }
              else
              if($DB->query_first("SELECT username FROM {users} WHERE userid <> %d AND trim(username) = '%s'",
                                  $userinfo['userid'], $p12_username))
              {
                $this->errors[] = SDProfileConfig::$p12_phrases['username_exists'];
              }
              else
              {
                $upd_fields_users[] = "`username` = '$p12_username'";
                $uname_changed = true;
                continue;
              }
            }
          }
        }

        // Skip "invisible"/"hidden" inputs or special fields
        if( !isset($_POST[$field['name']]) ||
            ((substr($field['name'],0,2)=='p_') || $disUsername ||
             (!defined('IN_ADMIN') && (!empty($field['readonly']) || empty($field['visible']) ||
                                       ($field['type']=='hidden') ))))
        {
          continue;
        }

        $name = $field['name'];
        $fieldnum = isset($field['fieldnum'])?(int)$field['fieldnum']:0;

        // If field is not for ucp-only use, it is allowed for public display...
        if(empty($field['ucp_only']))
        {
          $show_public = isset($_POST['public'][$fieldnum]) && ($_POST['public'][$fieldnum]==$fieldnum);
          $key = array_search($fieldnum, $public_fields);
          if($show_public && ($key === false))
          {
            $public_fields[] = $fieldnum;
          }
          else
          if(!$show_public && ($key !== false))
          {
            unset($public_fields[$key]);
          }
        }

        if(!isset($field['fieldnum']) && (substr($field['name'],0,2)!='p_') && ($field['name']!='username'))
        {
          continue;
        }

        if($field['type']=='date')
        {
          $value = GetVar($field['name'],'','string',true,false);
          if($value = sd_CreateUnixTimestamp($value, ''))
          {
            $value += 3600*12;
          }
        }
        else
        {
          $value = GetVar($name,null,(isset($field['vartype'])?$field['vartype']:'string'),true,false);
          if(!isset($value) && ($field['vartype']=='string'))
          {
            $value = '';
          }
        }

        if( !defined('IN_ADMIN') && !empty($input['required']) &&
            (!isset($value) ||
             ( (($field['type']=='text') || ($field['type']=='bbcode')) && (!isset($value) || !strlen($value))) ||
             ( ($field['type']=='date') && (empty($value) || ($value < 1)) ))
          )
        {
          // Only mark an error on frontpage
          $this->errors[] = 'Field without value: '.$field['label'];
        }
        else
        if(isset($value) && !count($this->errors))
        {
          if(($field['vartype']=='integer') && (empty($value) || !is_numeric($value))) $value = 0;
          if(($field['vartype']=='bool'))
          {
            if(!empty($value) && (($value=='1') || ($value=='true')))
            {
              $value = 1;
            }
            else
            {
              $value = 0;
            }
          }
          $upd_fields[] = "`$name` = '$value'";
        }
      } //foreach

      if(!$logged)
      {
        if(!empty($public_fields) && is_array($public_fields))
        {
          $public_fields = array_unique($public_fields);
          sort($public_fields);
          $public_fields = implode(',',$public_fields);
        }
        else
        {
          $public_fields = '';
        }
        $DB->query("UPDATE {users_data} SET public_fields = '%s' WHERE userid = %d",
                   $public_fields, $this->_userid);
      }
    }

    if(count($this->errors))
    {
      $this->errortitle = SDProfileConfig::$phrases['update_profile_errors'];
    }
    else
    {
      if(empty($onlyExtraData) && (SDProfileConfig::$usersystem['name'] == 'Subdreamer') && isset($_POST['p_new_password']))
      {
        //SD360: respect new option to disable email editing
        if(empty(SDProfileConfig::$settings['disable_email_editing']) &&
           strlen($new_email))
        {
          $upd_fields_users[] = "`email` = '$new_email'";
        }

        // change password?
        if(strlen($new_password))
        {
          if(NotEmpty(SDProfileConfig::GetFieldValue('salt')))
          {
            $upd_fields_users[] = "`use_salt` = 1, `password` = '".md5(SDProfileConfig::GetFieldValue('salt').md5($new_password))."'";
          }
          else
          {
            $upd_fields_users[] = "`salt` = '', `use_salt` = 0, `password` = '".md5($new_password)."'";
          }
        }
        $upd_fields_users[] = "`receive_emails` = ".$receive_emails;
      }
      // Change other fields
      if(!empty($upd_fields_users))
      {
        $DB->query("UPDATE {users} SET ".implode(', ',$upd_fields_users).
                   ' WHERE userid = %d', $this->_userid);
        //SD370: log a username change
        if($uname_changed)
        {
          Watchdog('Profile', $sdlanguage['username_changed'].' <strong>'.$userinfo['username'].
                   '</strong> => <strong>'.$p12_username.'</strong>', WATCHDOG_NOTICE);
        }
      }

      // change user data fields
      if(in_array('users_data', $DB->table_names_arr[$DB->database]))
      {
        if(!$DB->query_first('SELECT 1 FROM '.PRGM_TABLE_PREFIX.'users_data WHERE usersystemid = %d AND userid = %d',
                             SDProfileConfig::$usersystem['usersystemid'], $this->_userid))
        {
          $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX."users_data (usersystemid,userid,user_text) VALUES (%d, %d, '')",
                     SDProfileConfig::$usersystem['usersystemid'], $this->_userid);
        }
        if(empty($onlyCredentials) && !empty($upd_fields))
        {
		
          $DB->query("UPDATE {users_data} SET ".implode(', ',$upd_fields).
                     " WHERE usersystemid = %d AND userid = %d",
                     SDProfileConfig::$usersystem['usersystemid'], $this->_userid);
        }
      }

      $this->okmessage = SDProfileConfig::$phrases['profile_updated'];

      // Reload user from DB
      $this->LoadUser($this->_userid);
    }

    return !count($this->errors);

  } //UpdateProfile


  public function HandlePageAction(&$action, &$pageid, &$jscode)
  {
    if(empty($action) || empty($pageid)) return false;

    try
    {
      require_once(SD_INCLUDE_PATH.'class_profilehandler.php');
      SDUserProfilePageHandler::setUserId($this->_userid);
      if(!SDUserProfilePageHandler::ProcessPage($action,$pageid,$jscode))
      {
        $this->errors = SDUserProfilePageHandler::$errors;
      }
    }
    catch (Exception $e)
    {
      $this->errors = $e->getMessage();
    }
    return empty($this->errors);

  } //HandlePageAction

} // class SDUserProfile

} //do not remove
