<?php
if(!defined('IN_PRGM')) exit();

if(!class_exists('SDUserCache'))
{
class SDUserCache
{
  public static $messaging_fields = array('user_aim','user_facebook','user_googletalk','user_icq',
                                          'user_msnm','user_skype','user_twitter','user_yim');
  public static $allow_bbcode = true;
  public static $bbcode_detect_links = false;
  public static $img_path = '';
  public static $lbl_offline = 'Offline';
  public static $lbl_online = 'Online';
  public static $lbl_open_profile_page = 'Open your profile page';
  public static $lbl_view_member_page = 'View member page';
  public static $online_period = 900;
  public static $show_avatars = true;
  public static $avatar_width = 0;
  public static $avatar_height = 0;
  public static $ForumAvatarAvailable = true;
  public static $default_avatar = '';

  private static $usercache = false;
  private static $all_fields = array();
  private static $is_basic_ucp = false;

  protected function __construct()
  {
  }

  // ###########################################################################
  // INIT CACHE
  // ###########################################################################

  public static function Init()
  {
    global $bbcode, $mainsettings, $sdlanguage;

    self::$is_basic_ucp = defined('UCP_BASIC') && UCP_BASIC;

    if(self::$usercache===false) self::$usercache = array();
    self::$allow_bbcode  = isset($bbcode) && ($bbcode instanceof BBCode) && !empty($mainsettings['allow_bbcode']);
    self::$avatar_width  = empty($mainsettings['default_avatar_width'])  ? 60 : (int)$mainsettings['default_avatar_width'];
    self::$avatar_height = empty($mainsettings['default_avatar_height']) ? 60 : (int)$mainsettings['default_avatar_height'];
    self::$ForumAvatarAvailable = function_exists('ForumAvatar');
    self::$all_fields = array_merge(self::$messaging_fields,
                                    array('user_sig', 'user_screen_name','user_allow_viewonline',
                                          'avatar_disabled','user_avatar_type','user_avatar',
                                          'user_avatar_width','user_avatar_height','user_avatar_link',
                                          'user_title'));
    self::$default_avatar = GetDefaultAvatarImage(self::$avatar_width, self::$avatar_height);
    //SD360:
    if(isset($sdlanguage['link_open_profile_hint']))
      SDUserCache::$lbl_open_profile_page = $sdlanguage['link_open_profile_hint'];
    if(isset($sdlanguage['link_open_member_page_hint']))
      SDUserCache::$lbl_view_member_page = $sdlanguage['link_open_member_page_hint'];
  }

  public static function IsBasicUCP()
  {
    return self::$is_basic_ucp;
  }

  // ###########################################################################
  // ADD PROPERTY TO CACHED USER
  // ###########################################################################

  public static function AddCachedUserProperty($uid, $name, $value)
  {
    if(empty($uid) || ($uid < 1) || ($uid > 99999999) || empty($name)) return false;
    if(isset(self::$usercache[$uid]))
    {
      self::$usercache[$uid][$name] = $value;
    }
    return true;
  }

  // ###########################################################################
  // GET CACHED USER
  // ###########################################################################

  public static function GetCachedUser($uid=0)
  {
    if(!isset($uid) || ($uid < 0)|| ($uid > 99999999)) return false;
    if(isset(self::$usercache[$uid]))
    {
      return self::$usercache[$uid];
    }
    return array();
  }

  // ###########################################################################
  // REMOVE CACHED USER
  // ###########################################################################

  public static function RemoveCachedUser($uid=0)
  {
    if(empty($uid) || ($uid < 1) || ($uid > 99999999)) return false;
    unset(self::$usercache[$uid]);
    return true;
  }

  // ###########################################################################
  // CACHE USER
  // ###########################################################################

  public static function CacheUser($uid, $default_name='', $poll_online=true,
                                   $poll_avatar=true, $ignore_bot=false)
  {
    if(false === ($user = self::GetCachedUser($uid)))
    {
      return false;
    }

    global $DB, $mainsettings, $userinfo, $usersystem;

    if(!isset($user['valid']))
    {
      // Init user array
      $user = array(
        'activated'             => false,
        'author_avatar'         => '',
        'avatar'                => '',
        'color'                 => false,
        'email'                 => '',
        'joindate'              => 0,
        'lastactivity'          => 0,
        'link_small'            => '',
        'online'                => -1, //special flag
        'online_img'            => '',
        'post_count'            => 0,
        'public_fields'         => false,
        'signature'             => '',
        'thread_count'          => 0,
        'topic'                 => '',
        'topic_avatar'          => '',
        'user_allow_viewonline' => true,
        'user_avatar_height'    => (int)self::$avatar_height,
        'user_avatar_width'     => (int)self::$avatar_width,
        'user_id'               => (int)$uid,
        'usergroup_details'     => array(),
        'usergroup_others'      => array(),
        'usergroupid'           => 0,
        'userid'                => (int)$uid,
        'username'              => (string)$default_name,
        'valid'                 => false,
        'user_title'            => '' //SD360
      );

      if(empty($uid) || (!$ud = sd_GetForumUserInfo(1, $uid, true, self::$all_fields, 1 /* == public view status */)))
      {
        if($ud = sd_GetForumUserInfo(0, $default_name, true, self::$all_fields, 1 /* == public view status */))
        {
          $uid = $ud['userid'];
        }
      }
      if(!empty($ud))
      {
        foreach(self::$messaging_fields as $field)
        {
          $user[$field] = isset($ud[$field]) ? (string)$ud[$field] : false;
        }
        foreach(array('usergroupid','usergroup_others','avatar_disabled','user_avatar_type','user_avatar',
                      'user_avatar_width','user_avatar_height','user_avatar_link',
                      'user_title') as $field)
        {
          $user[$field] = isset($ud[$field]) ? $ud[$field] : false;
        }

        $user['valid']         = true;
        $user['activated']     = !empty($ud['activated']);
        $user['email']         = isset($ud['email']) ? $ud['email'] : '';
        $user['joindate']      = isset($ud['joindate']) ? (int)$ud['joindate'] : 0;
        $user['lastactivity']  = isset($ud['lastactivity']) ? (int)$ud['lastactivity'] : 0;
        $user['public_fields'] = (isset($ud['public_fields']) && is_array($ud['public_fields'])) ? $ud['public_fields'] : false;
        $user['username']      = !empty($ud['user_screen_name']) ? $ud['user_screen_name'] :
                                 (isset($ud['username']) ? $ud['username'] : $default_name);
        $user['usergroup_details'] = !empty($ud['usergroup_details']) ? $ud['usergroup_details'] : array();
        if(!empty($ud['usergroup_details']['color_online']))
        {
          $user['color'] = ' style="color:#'.$ud['usergroup_details']['color_online'].'"';
        }
        $user['post_count'] = empty($ud['user_post_count'])?0:(int)$ud['user_post_count'];
        $user['thread_count'] = empty($ud['user_thread_count'])?0:(int)$ud['user_thread_count'];
        $user['user_title'] = isset($ud['user_title']) ? $ud['user_title'] : ''; //SD360

        if($usersystem['name'] == 'Subdreamer')
        {
          $user['user_allow_viewonline'] = !isset($ud['user_allow_viewonline']) || !empty($ud['user_allow_viewonline']);
          if(!empty($ud['usergroup_details']['sig_enabled']) && !empty($ud['user_sig']))
          {
            if($signature = trim($ud['user_sig']))
            // Apply BBCode parsing to signature
            if(self::$allow_bbcode)
            {
              global $bbcode;
              $olddetect = $bbcode->GetDetectURLs();
              $bbcode->SetDetectURLs(true);
              $oldpattern = $bbcode->GetURLPattern();
              if(empty(self::$bbcode_detect_links)) //SD341
                $bbcode->SetURLPattern('{$text/h}'); //SD342
              else
                $bbcode->SetURLPattern('<a rel="nofollow" href="{$url/h}">{$text/h}</a>'); //SD341
              $signature = $bbcode->Parse($signature);
              $bbcode->SetURLPattern($oldpattern);
              $bbcode->SetDetectURLs($olddetect);
            }
            $user['signature'] = $signature;
          }
        }
        else
        {
          $user['activated'] = true; //SD370: assume this
        }
        unset($ud,$signature);
        if($user['valid']) @ksort($user);
      }
    }

    if(($usersystem['name'] == 'Subdreamer') && !empty($poll_online) &&
       (!empty($user['user_allow_viewonline']) || !empty($userinfo['adminaccess'])))
    {
      $user['online'] = -1; //SD343: fix: init var or otherwise always online
      $DB->result_type = MYSQL_ASSOC;
      if($lastactivity = $DB->query_first('SELECT s.lastactivity last_active, ipaddress FROM {sessions} s'.
                                          ' WHERE userid = %d AND admin = 0 AND loggedin = 1'.
                                          ' ORDER BY lastactivity DESC LIMIT 1',$uid))
      {
        $user['lastactivity'] = empty($lastactivity['last_active'])?0:(int)$lastactivity['last_active'];
        $user['ipaddress'] = empty($lastactivity['ipaddress'])?'':$lastactivity['ipaddress'];
        if($user['online'] == -1)
        {
          $user['online'] = !empty($user['lastactivity']) &&
                            ($lastactivity['last_active'] > (TIME_NOW - (int)self::$online_period));
        }
        unset($lastactivity);
      }
      $online_phrase = ($user['online']===1) || ($user['online']===true) ? self::$lbl_online : self::$lbl_offline;
      $user['online_img'] =
        '<div class="author-online"><img src="'.self::$img_path.
        (($user['online']===1) || ($user['online']===true)?'online':'offline').'.png'.
        '" alt="" width="16" height="16" title="'.htmlspecialchars($online_phrase).'" />'.
        ' <span style="vertical-align: top;">'.$online_phrase.'</span></div>';
    }
    else
    {
      $user['online_img'] = '';
    }
    //SD362: 'activated' added to some conditions
    if(!isset($user['link']))
    {
      $user['link'] = (!empty($userinfo['userid']) && !empty($user['activated']) && !empty($user['valid']) &&
                      !self::$is_basic_ucp && !SD_IS_BOT) ? (function_exists('ForumLink')?ForumLink(4, $uid):'') : '';
    }

    $username = $user['username'];
    $user['link_title'] = '';
    if(empty($uid) || empty($user['activated']) || self::$is_basic_ucp || (empty($ignore_bot) && SD_IS_BOT))
    {
      $user['link'] = $user['profile_link'] = $user['topic'] = $user['forum'] = $username;
      $user['link_title'] = '';
    }
    else
    {
      $link_title = empty($userinfo['userid']) ? '' : ($uid==$userinfo['userid'] ? self::$lbl_open_profile_page : self::$lbl_view_member_page);
      if(strlen($link_title))
      {
        $user['link_title'] = htmlspecialchars(strip_tags($link_title));
        $link_title = ' title="'. /*addslashes*/(strip_tags($link_title)).'"';
      }

      if(empty($user['forum'])) // user link in right-side column
      {
        $user['forum'] = ($user['valid']&&$user['link']) ? '<a rel="nofollow" class="forum-memberlink"'.$user['color'].$link_title.
                         ' href="'.$user['link'].'">'.$username.'</a>' :
                         ($user['color']==false ? $username : '<span '.$user['color'].'>'.$username.'</span>');
      }
      if(empty($user['topic'])) // user link in topics list
      {
         $user['topic'] = ($user['valid']&&$user['link']) ? '<a rel="nofollow" class="topics-memberlink"'.$user['color'].$link_title.
                          ' href="'.$user['link'].'">'.$username.'</a>' :
                          ($user['color']==false ? $username : '<span '.$user['color'].'>'.$username.'</span>');
      }
      if(empty($user['link_small'])) // small user link in forums list
      {
        $user['link_small'] = ($user['valid']&&$user['link']) ? '<a rel="nofollow" class="forum-memberlink-small"'.$user['color'].$link_title.
                              ' href="'.$user['link'].'">'.$username.'</a>' :
                              ($user['color']==false ? $username : '<span '.$user['color'].'>'.$username.'</span>');
      }
      if(empty($userinfo['loggedin']) || empty($user['profile_link'])) // link to user profile / member page
      {
        if(self::$is_basic_ucp || empty($user['link']) || !$user['valid'])
        {
          $user['link'] = '';
          $user['profile_link'] = ($user['color']==false ? $username : '<span '.$user['color'].'>'.$username.'</span>');
        }
        else
        {
          $user['profile_link'] = '<a rel="nofollow" class="'.
            (!empty($userinfo['userid'])&&($uid == $userinfo['userid'])?'profile-link':'member-link').
            '"'.$user['color'].$link_title.
            ' href="'.$user['link'].'">'.$username.'</a>';
        }
      }
    }

    if(!empty($uid) && !empty($poll_avatar) && !empty($user['activated']))
    {
      $user['avatar'] = false;
      //SD360: added option to hide avatars of specific groups
      if( self::$show_avatars &&
          ( (($usersystem['name'] == 'Subdreamer') && !empty($userinfo['loggedin'])) ||
            empty($user['usergroup_details']['hide_avatars_guests'])) &&
          !empty($user['usergroup_details']['avatar_enabled']) &&
          (!isset($userinfo['profile']['user_view_avatars']) ||
           !empty($userinfo['profile']['user_view_avatars'])) )
      {
        //SD343: temporarily store avatar dimensions of mainsettings and
        // user-level, so that current settings can override those:
        $am_w = (int)$mainsettings['default_avatar_width'];
        $am_h = (int)$mainsettings['default_avatar_height'];
        $au_w = $user['user_avatar_width'];
        $au_h = $user['user_avatar_height'];
        $mainsettings['default_avatar_width']  = self::$avatar_width;
        $mainsettings['default_avatar_height'] = self::$avatar_height;
        $user['user_avatar_width']  = self::$avatar_width;
        $user['user_avatar_height'] = self::$avatar_height;

        $user['author_avatar'] = $user['topic_avatar'] = '<div class="no-avatar"></div> ';
        if($user['valid'])
        {
          if($usersystem['name'] == 'Subdreamer')
          {
            if(!self::$ForumAvatarAvailable || self::$is_basic_ucp)
            {
              if(!empty($user['email']))
              {
                $user['avatar'] = GetAvatarPath($user['email'], $uid);
                $user['avatar'] = '<img alt="" class="avatar" width="'.self::$avatar_width.
                                  '" height="'.self::$avatar_height.'" src="'.$user['avatar'].'" />';
              }
            }
            else $user['avatar'] = ForumAvatar($uid,$default_name,$user['email'],$user);
          }
          else
          if(self::$ForumAvatarAvailable) $user['avatar'] = ForumAvatar($uid,$default_name);

          if(empty($user['avatar']) || ($user['avatar']!==false))
          {
            if(empty($user['avatar']))
            {
              $user['avatar'] = self::$default_avatar;
              $user['avatar'] = preg_replace('#height="[\w+][^"]"#i','height="'.self::$avatar_height.'"',$user['avatar']);
              $user['avatar'] = preg_replace('#width="[\w+][^"]"#i','width="'.self::$avatar_width.'"',$user['avatar']);
            }
            $user['author_avatar'] = '<div class="author-avatar">'.$user['avatar'].'</div> ';
            $user['topic_avatar']  = '<div class="topic-avatar">'.$user['avatar'].'</div> ';
          }
        }

        //SD343: restore original values
        $mainsettings['default_avatar_width'] = $am_w;
        $mainsettings['default_avatar_height'] = $am_h;
        $user['user_avatar_width'] = $au_w ;
        $user['user_avatar_height'] = $au_h;
      }
    }

    self::$usercache[$uid] = $user;
    return $user;
  } //CacheUser

  // ############################################################################

  public static function UpdateAllUsersTitles() //SD360
  {
    global $DB, $usersystem;

    if(empty($usersystem['usersystemid']) || ($usersystem['name'] != 'Subdreamer')) return;

    //SD360: removed "IFNULL(user_post_count,0) > 0" condition to update all
    $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX."users_data (usersystemid,userid,user_text)
      SELECT ".$usersystem['usersystemid'].", userid, ''
      FROM ".PRGM_TABLE_PREFIX."users
      WHERE NOT EXISTS(SELECT 1 FROM ".PRGM_TABLE_PREFIX."users_data ud
             WHERE ud.userid = ".PRGM_TABLE_PREFIX."users.userid
             AND ud.usersystemid = ".$usersystem['usersystemid'].")");

    // Update all user titles (if a row in user_data exists)
    $DB->query_first('UPDATE '.PRGM_TABLE_PREFIX.'users_data'.
      ' SET user_titleid = IFNULL('.
        '(SELECT titleid FROM '.PRGM_TABLE_PREFIX.'users_titles'.
        ' WHERE post_count < (SELECT sdu.user_post_count'.
                            ' FROM '.PRGM_TABLE_PREFIX.'users sdu'.
                            ' WHERE sdu.userid = '.PRGM_TABLE_PREFIX.'users_data.userid)'.
        ' ORDER BY post_count DESC LIMIT 1),0)'.
      ' WHERE usersystemid = %d',
      $usersystem['usersystemid']);
  }

  // ##########################################################################

  public static function UpdateUserTitle($userid) //SD360
  {
    global $DB, $usersystem;

    if(empty($userid) || empty($usersystem['usersystemid'])) return;

    $count = $DB->query_first('SELECT user_post_count FROM {users}'.
                              ' WHERE userid = %d', $userid);
    $count = empty($count['user_post_count']) ? 0 : (int)$count['user_post_count'];

    $tmp = $DB->query_first('SELECT titleid'.
                            ' FROM '.PRGM_TABLE_PREFIX.'users_titles'.
                            ' WHERE post_count < %d'.
                            ' ORDER BY post_count DESC LIMIT 1',
                            $count);
    $tmp = empty($tmp['titleid'])?0:$tmp['titleid'];
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'users_data'.
      ' SET user_titleid = %d'.
      ' WHERE userid = %d',
      $tmp, $userid);

  } //UpdateUsersTitle

} // class SDUserCache
} //do not remove
