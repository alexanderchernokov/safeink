<?php
if(!defined('IN_PRGM')) exit();

defined('TIMENOW') || define('TIMENOW', time());

// ##### init variables #####
$BB_ADMIN_GID = 1;
$BB_GUEST_ID  = 2;
$punbb_config = array();
$forumdbname  = $usersystem['dbname'];
$tableprefix  = $usersystem['tblprefix'];

if($DB->database != $forumdbname) $DB->select_db($forumdbname);

// Get punBB config values:
if($getrows = $DB->query('SELECT conf_name, conf_value'.
   ' FROM ' . $tableprefix . 'config'.
   " WHERE conf_name IN ('p_allow_banned_email','o_timeout_visit','o_timeout_online',
     'o_date_format','o_avatars','o_avatars_height','o_avatars_width','o_avatars_dir')"))
{
  while($row = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
  {
    $punbb_config[$row['conf_name']] = $row['conf_value'];
  }
  $DB->free_result($getrows);
}
unset($getrows);
$punbb_config['o_date_format'] = isset($punbb_config['o_date_format'])?
                                 $punbb_config['o_date_format']:'';
$punbb_config['date_formats']  = array($punbb_config['o_date_format'], 'Y-m-d',
                                 'Y-d-m', 'd-m-Y', 'm-d-Y', 'M j Y', 'jS M Y');

// Original cookiename came from config file and is in usersystem's cookieprefix.
// The cookie name is not stored in MyBB's settings table or datacache.
// So we set "our" cookieprefix as actual cookiename and set a MyBB-defined
// cookieprefix as the new usersystem cookieprefix:
$cookiename = !empty($usersystem['cookieprefix'])  ? $usersystem['cookieprefix'] : 'forum_cookie';
$usersystem['cookieprefix'] = '';
if(isset($punbb_config['cookieprefix']) && strlen($punbb_config['cookieprefix']))
{
  $usersystem['cookieprefix'] = $punbb_config['cookieprefix'];
}
$cookieprefix = $usersystem['cookieprefix'];
$cookiedomain  = isset($usersystem['cookiedomain'])   ? $usersystem['cookiedomain'] : '';
$cookiepath    = isset($usersystem['cookiepath'])     ? $usersystem['cookiepath']   : '/';
$cookiesecure  = false;
$cookietimeout = !empty($usersystem['cookietimeout']) ? Is_Valid_Number($usersystem['cookietimeout'],1209600,3600) : 1209600;
define('COOKIE_TIMEOUT', $cookietimeout);


// ########################## SPECIAL INCLUDE CHECK ###########################
if(!defined('SD_PUNBB_INCLUDED'))
{
  define('SD_PUNBB_INCLUDED', true);
  defined('ANONYMOUS') || define('ANONYMOUS', 1); // pre-defined "Guest"
  define('USER_AGENT', substr((!empty($_SERVER['HTTP_USER_AGENT'])) ?
                         htmlspecialchars((string) $_SERVER['HTTP_USER_AGENT']):
                         '', 0, 100));

  // ############################ USER FUNCTIONS ##############################

  function IsIPBanned($clientip)
  {
    global $DB, $usersystem;

    $prevDB = $DB->database;
    $forumdbname = $usersystem['dbname'];
    $tableprefix = $usersystem['tblprefix'];
    #if($prevDB != $forumdbname) $DB->select_db($forumdbname);
    # ???
    #if($prevDB != $forumdbname) $DB->select_db($prevDB);

    return false; //TBD???
  }

  // Returns the relevent forum link url
  // linkType
  // 1 - Register
  // 2 - UserCP
  // 3 - Recover Password
  // 4 - UserCP (requires $userid)
  // 5 - SendPM (requires $userid)
  function ForumLink($linkType, $userid = 0)
  {
    global $DB, $sdurl, $usersystem;

    $url = '';
    switch($linkType)
    {
      case 1:
        $url = 'register.php';
        break;
      case 2:
        $url = 'profile.php';
        break;
      case 3:
        $url = ''; //TBD???
        break;
      case 4:
        if($userid > 0)
        $url = 'profile.php?id='.$userid;
        break;
      case 5:
        if($userid > 0)
        $url = 'misc.php?email='.$userid;
        break;
    }

    return strlen($url) ? $sdurl.$usersystem['folderpath'].$url : '';

  } //ForumLink


  function ForumAvatar($userid, $username)
  {
    if(empty($userid) && (!isset($username) || !strlen($username)))
    {
      return '';
    }

    global $DB, $usersystem, $sdurl, $punbb_config;

    $prevDB = $DB->database;
    $forumdbname = $usersystem['dbname'];
    $tableprefix = $usersystem['tblprefix'];
    if($prevDB != $forumdbname) $DB->select_db($forumdbname);

    // get avatar settings
    $useravatar_sql = 'SELECT avatar, avatar_width, avatar_height'.
                      ' FROM '.$tableprefix.'users'.
                      ' WHERE ';

    $DB->result_type = MYSQL_ASSOC;
    if($userid > ANONYMOUS)
    {
      $useravatar = $DB->query_first($useravatar_sql.'id = %d', $userid);
    }
    else
    {
      $useravatar = $DB->query_first($useravatar_sql."username = '%s' LIMIT 1",
                                     $DB->escape_string($username));
    }
    if($prevDB != $forumdbname) $DB->select_db($prevDB);

    if(empty($useravatar['avatar']) || !is_numeric($useravatar['avatar'])) return '';

    $avatar_width  = (int)$useravatar['avatar_width'];
    $avatar_height = (int)$useravatar['avatar_height'];

    $avatar = '<img border="0" alt="avatar" width="'.(int)$avatar_width.
              '" height="'.(int)$avatar_height.'" '.
              'src="'.$sdurl.$usersystem['folderpath'].
              $punbb_config['o_avatars_dir'].'/'.$userid;

    switch($useravatar['avatar'])
    {
      case 1:
        $avatar .= '.gif';
        break;
      case 2:
        $avatar .= '.jpg';
        break;
      case 3:
        $avatar .= '.png';
        break;
    }
    $avatar .= '" />';

    return $avatar;

  } //ForumAvatar


  // ########################### SD SET COOKIE ################################

  function sdsetcookie($name, $value = null, $expire=null)
  {
    global $cookiedomain, $cookiepath, $cookieprefix, $cookiesecure;

    if(is_null($value) || ($value==''))
    {
      $expire = 0; $value = '';
    }
    else
    if(is_null($expire) || ($expire==0))
    {
      $expire = TIME_NOW + COOKIE_TIMEOUT;
    }
    else
    if($expire < 0)
    {
      $expire = null;
    }

    if(version_compare(PHP_VERSION, '5.2.0', 'ge'))
      @setcookie($cookieprefix . $name, $value, $expire, $cookiepath, (empty($cookiedomain)?null:$cookiedomain), false, true);
    else
      @setcookie($cookieprefix . $name, $value, $expire, $cookiepath, (empty($cookiedomain)?null:$cookiedomain), false);
  }


  // ####################### PUNBB SPECIFIC FUNCTIONS #########################

  function punbb_random_hashkey($len=8)
  {
    return substr(sha1(uniqid(rand(), true)), 0, intval($len));
  }

  function punbb_hash($salt, $value='')
  {
    return sha1($salt.sha1($value));
  }

  function punbb_guest_hash()
  {
    $expire = TIME_NOW + COOKIE_TIMEOUT;
    return base64_encode('1|'.punbb_random_hashkey(8).'|'.
                         $expire.'|'.punbb_random_hashkey(8));
  }

  function punbb_set_online($userid = 1, $username = '', $token = '')
  {
    global $DB, $sdurl, $uri;

    if(empty($userid) || empty($username) || ($userid <= ANONYMOUS))
    {
      $userid = ANONYMOUS;
      $username = 'Guest';
    }

    if(empty($token) || (strlen($token)!==40))
    {
      $token = punbb_random_hashkey(40);
    }
    $DB->query('REPLACE INTO '.$tableprefix.'online'.
               '(user_id, ident, logged, csrf_token, prev_url) VALUES '.
               "(%d, '%s', %d, '%s', '%s')",
               $userid, $DB->escape_string($username),
               TIME_NOW,
               $DB->escape_string($token),
               $DB->escape_string($sdurl.ltrim($uri,'/')));
  }

} // special include check end


// ################## SETUP USER AS GUEST AS DEFAULT ##########################

// switch to forum database
if($DB->database != $forumdbname) $DB->select_db($forumdbname);

unset($user);
$user = $guestuser = array(
  'userid'         => ANONYMOUS, // guests are "1" userid
  'usergroupid'    => $BB_GUEST_ID, // "Guests"
  'usergroupids'   => array($BB_GUEST_ID),
  'username'       => '',
  'displayname'    => '',
  'loggedin'       => 0,
  'email'          => '',
  'timezoneoffset' => 0,
  'dateformat'     => 0, // translated to format at bottom of file!
  'dstonoff'       => 0,
  'dstauto'        => 1,
  'banned'         => 0);

// Default SQL we use to get user data during frontpage loading:
$sql = 'SELECT u.id, u.username, u.group_id, u.password, u.salt,'.
       ' u.email, u.date_format, u.dst, u.timezone,'.
       ' o.user_id o_id, o.logged, o.idle, o.csrf_token, o.prev_url,'.
       ' b.id user_banned, b2.id ip_banned'.
       ' FROM ' . $tableprefix . 'users u'.
       ' LEFT JOIN ' . $tableprefix . 'groups g ON g.g_id = u.group_id'.
       ' LEFT JOIN ' . $tableprefix . 'bans b ON b.username = u.username'.
       ' LEFT JOIN ' . $tableprefix . "bans b2 ON b2.ip = '".USERIP."'".
       ' LEFT JOIN ' . $tableprefix . 'online o ON o.user_id = u.id'.
       ' WHERE ';


// ############################### LOGIN ######################################

if(isset($_POST['login']) && ($_POST['login'] == 'login') &&
   !(isset($_GET['logout']) || isset($_POST['logout'])))
{
  $loginusername = isset($_POST['loginusername']) ? $_POST['loginusername'] : '';
  $loginpassword = isset($_POST['loginpassword']) ? $_POST['loginpassword'] : '';

  if(strlen($loginusername) && strlen($loginpassword))
  {
    $DB->result_type = MYSQL_ASSOC;
    if($getuser = $DB->query_first($sql." u.username = '%s' LIMIT 1",
                                   $DB->escape_string($loginusername)))
    {
      $isSHA = (strlen($getuser['password'])==40);
      // Is user banned?
      if(!is_null($getuser['user_banned']) ||
         !is_null($getuser['ip_banned']))
      {
        $login_errors_arr[] = $sdlanguage['you_are_banned'];
      }
      else
      // Is user not yet activated (usergroup id = 0)?
      if(empty($getuser['group_id']))
      {
        $login_errors_arr[] = $sdlanguage['not_yet_activated'];
      }
      else
      // Does password verify ok?
      if(!empty($loginpassword) &&
         (($isSHA  && !empty($getuser['salt']) && (punbb_hash($getuser['salt'],$loginpassword) == $getuser['password'])) ||
          (!$isSHA &&  empty($getuser['salt']) && (md5($loginpassword) == $getuser['password']))) )
      {
        // logged in
        $user = array('userid'         => $getuser['id'],
                      'usergroupid'    => $getuser['group_id'],
                      'usergroupids'   => array($getuser['group_id']),
                      'username'       => $getuser['username'],
                      'email'          => $getuser['email'],
                      'timezoneoffset' => $getuser['timezone'],
                      'dateformat'     => $getuser['date_format'],
                      'dstonoff'       => $getuser['dst'],
                      'salt'           => (empty($getuser['salt'])?'':$getuser['salt']),
                      'loggedin'       => 1,
                      'ipaddress'      => USERIP,
                      'displayname'    => '',
                      'dstauto'        => 0);

        // set new cookie
        $rememberme = (GetVar('rememberme',false,'string',true,false)=='1');
        $expire = TIME_NOW + COOKIE_TIMEOUT;
        $newhash = base64_encode($getuser['id'].'|'.$getuser['password'].'|'.$expire.'|'.
                      sha1($getuser['salt'].$getuser['password'].
                           punbb_hash($getuser['salt'],$expire)));
        sdsetcookie($cookiename, $newhash, ($rememberme ? $expire : -1));

        // delete guest entry for IP from "online" list
        $DB->query('DELETE FROM '.$tableprefix.'online'.
                   " WHERE user_id = 1 AND ident = '%s'",USERIP);
        // create new "online" row
        $csrf_token = !empty($getuser['csrf_token']) && (strlen($getuser['csrf_token'])==40) ?
                        $getuser['csrf_token'] : punbb_random_hashkey(40);
        punbb_set_online($user['userid'], $user['username'], $csrf_token);

        unset($getuser,$expire,$newhash);
      }
      else
      {
        $login_errors_arr[] = $sdlanguage['wrong_password'];
      }
    }
    else
    {
      // wront username OR: username = ANONYMOUS, probably a hacker
      // lets just give them wrong username to throw them off
      $login_errors_arr[] = $sdlanguage['wrong_username'];
    }
  }
  else
  {
    $login_errors_arr[] = $sdlanguage['please_enter_username'];
  }

  if(!empty($login_errors_arr))
  {
    // set cookie, online row and user as guest
    sdsetcookie($cookiename, punbb_guest_hash());
    punbb_set_online();
    $user = $guestuser;
  }

} // end of "Login"
else
{
  // ####################### CHECK FORUM COOKIE ###############################
  // punBB does not use DB sessions, just a single cookie

  $doSetGuestCookie = false;
  $sessiondata = array();
  if(isset($_COOKIE[$cookieprefix.$cookiename]))
  {
    if(false !== ($tmp = @base64_decode($_COOKIE[$cookieprefix.$cookiename])))
    {
      $tmp = explode('|', $tmp);
      if( !empty($tmp) && is_array($tmp) && (count($tmp)==4) )
      {
        @list($sessiondata['uid'], $sessiondata['password_hash'],
              $sessiondata['expiration_time'], $sessiondata['expire_hash']) = $tmp;
      }
    }

    // If somethings wrong, set guest cookie
    if( empty($tmp) || !is_array($tmp) && (count($tmp)!=4) ||
        empty($sessiondata['expiration_time']) ||
        (intval($sessiondata['expiration_time']) <= TIME_NOW) )
    {
      $doSetGuestCookie = true;
    }
    unset($tmp);
  }

  // ####################### CHECK USER COOKIE ################################

  if( !$doSetGuestCookie &&
      !empty($sessiondata['uid']) && (intval($sessiondata['uid']) > 1) &&
      !empty($sessiondata['expiration_time']) &&
      (intval($sessiondata['expiration_time']) > TIME_NOW) &&
      !empty($sessiondata['password_hash']))
  {
    if($getuser = $DB->query_first($sql.' u.id = %d', $sessiondata['uid']))
    {
      if( ($sessiondata['password_hash'] == $getuser['password']) &&
          ($sessiondata['expire_hash'] ==
            sha1($getuser['salt'].$getuser['password'].
                 punbb_hash($getuser['salt'],intval($sessiondata['expiration_time'])))) )
      {
        $doSetGuestCookie = false;
        $user = array('userid'         => $getuser['id'],
                      'usergroupid'    => $getuser['group_id'],
                      'usergroupids'   => array($getuser['group_id']),
                      'username'       => $getuser['username'],
                      'displayname'    => '',
                      'loggedin'       => 1,
                      'email'          => $getuser['email'],
                      'timezoneoffset' => $getuser['timezone'],
                      'dateformat'     => $getuser['date_format'],
                      'dstonoff'       => $getuser['dst'],
                      'dstauto'        => 0,
                      'salt'           => (empty($getuser['salt'])?'':$getuser['salt']),
                      'banned'         => 0);

        // Renew cookie with new expiration time
        $expire = (intval($sessiondata['expiration_time']) > (TIME_NOW + $punbb_config['o_timeout_visit'])) ?
                    TIME_NOW + COOKIE_TIMEOUT : TIME_NOW + (int)$punbb_config['o_timeout_visit'];
        $newhash = base64_encode($getuser['id'].'|'.$getuser['password'].'|'.$expire.'|'.
                      sha1($getuser['salt'].$getuser['password'].
                           punbb_hash($getuser['salt'],$expire)));
        sdsetcookie($cookiename, $newhash, $expire);
        unset($expire,$newhash);
      }
    }
    else $doSetGuestCookie = true;
  }

  // User cookie was invalid, set default guest cookie
  if($doSetGuestCookie)
  {
    unset($sessiondata);
    sdsetcookie($cookiename, punbb_guest_hash());
  }
  unset($getuser,$doSetGuestCookie);


  // ############################ LOGOUT ######################################

  if(!empty($user['userid']) && ($user['userid'] > 0))
  {
    if( isset($_GET['logout']) || isset($_POST['logout']) )
    {
      // set cookie, online row and user as guest
      sdsetcookie($cookiename, punbb_guest_hash());
      punbb_set_online();
      $user = $guestuser;
    }
    else
    if($user['userid'] > ANONYMOUS)
    {
      $DB->query('UPDATE '.$tableprefix.'online SET logged = %d WHERE user_id = %d',
                 TIME_NOW, $user['userid']);
    }
  }
} // end of "Login"/else

// ################### SUBDREAMER USER SETTINGS SETUP #########################

// switch back to Subdreamer database
if($DB->database != $database['name']) $DB->select_db($database['name']);

$usersettings = array('userid'         => ($user['userid'] < ANONYMOUS ? 0 : $user['userid']),
                      'usergroupid'    => $user['usergroupid'],
                      'usergroupids'   => $user['usergroupids'],
                      'username'       => $user['username'],
                      'displayname'    => $user['displayname'],
                      'loggedin'       => $user['loggedin'],
                      'email'          => $user['email'],
                      'timezoneoffset' => $user['timezoneoffset'],
                      'dateformat'     => $user['dateformat'],
                      'dstonoff'       => $user['dstonoff'],
                      'salt'           => (empty($user['salt'])?'':$user['salt']),
                      'dstauto'        => $user['dstauto']);

if( strlen($usersettings['dateformat']) && is_numeric($usersettings['dateformat']) &&
    isset($punbb_config['date_formats'][$usersettings['dateformat']]) )
{
  $usersettings['dateformat'] = $punbb_config['date_formats'][$usersettings['dateformat']];
}

// cleanup unused variables (except $punbb_config)
unset($user, $guestuser, $loginusername, $loginpassword, $sessiondata, $sql);
