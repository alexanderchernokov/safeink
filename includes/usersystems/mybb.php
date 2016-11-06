<?php
if(!defined('IN_PRGM')) exit();

defined('TIMENOW') || define('TIMENOW', time());
$sessioncreated = false;

// ##### init variables #####
$BB_ADMIN_GID = 0;
$BB_GUEST_ID  = -1;
$mybb_config  = array();
$forumdbname  = $usersystem['dbname'];
$tableprefix  = $usersystem['tblprefix'];

if($DB->database != $forumdbname) $DB->select_db($forumdbname,true);
if(!empty($DB->errno)) return false;

$DB->ignore_error = true;

// Get MyBB config values:
if($getrows = $DB->query("SELECT name, IFNULL(value,'') value".
   ' FROM ' . $tableprefix . 'settings'.
   " WHERE name IN ('cookiedomain','cookiepath','cookieprefix',
                    'maxloginattempts','username_method',
                    'avatardir','avataruploadpath',
                    'postmaxavatarsize','username_method')"))
{
  while($row = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
  {
    $mybb_config[$row['name']] = $row['value'];
  }
  $DB->free_result($getrows);
}

// In case of error bail out early to avoid more errors
$DB->ignore_error = false;
if(!empty($DB->errno)) return false;

// Get MyBB cached values:
if($getrows = $DB->query('SELECT title, cache'.
   ' FROM ' . $tableprefix . 'datacache'.
   " WHERE title IN ('banned','bannedips')"))
{
  while($row = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
  {
    if(!empty($row['value']) && (substr($row['value'],0,3) != 'a:0'))
    {
      $mybb_config[$row['title']] = @unserialize($row['value']);
    }
  }
  $DB->free_result($getrows);
}

// Get id's for default usergroups Administrators and Guests:
if($getrows = $DB->query('SELECT title, gid FROM '.$usersystem['tblprefix'].
                         'usergroups WHERE type = 1'.
                         " AND title IN ('Administrators', 'Guests')"))
{
  while($row = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
  {
    if($row['title'] == 'Administrators')
      $BB_ADMIN_GID = (int)$row['gid'];
    else
      $BB_GUEST_ID = (int)$row['gid'];
  }
}
unset($getrows,$row);


$cookieprefix = isset($mybb_config['cookieprefix']) ? trim($mybb_config['cookieprefix']):
                (isset($usersystem['cookieprefix']) ? $usersystem['cookieprefix'] : '');
$cookiedomain = isset($mybb_config['cookiedomain']) ? trim($mybb_config['cookiedomain']):
                (isset($usersystem['cookiedomain']) ? $usersystem['cookiedomain'] : '');
$cookiepath   = isset($mybb_config['cookiepath'])   ? trim($mybb_config['cookiepath']):
                (isset($usersystem['cookiepath'])   ? $usersystem['cookiepath'] : '/');
$cookiesecure = false;
defined('COOKIE_TIMEOUT') || define('COOKIE_TIMEOUT', 31536000); // one year


// ########################## SPECIAL INCLUDE CHECK ###########################
if(!defined('SD_MYBB_INCLUDED'))
{
  define('SD_MYBB_INCLUDED', true);
  defined('ANONYMOUS') || define('ANONYMOUS', 0);
  define('USER_AGENT', substr((!empty($_SERVER['HTTP_USER_AGENT'])) ?
                         htmlspecialchars((string) $_SERVER['HTTP_USER_AGENT']):
                         '', 0, 100));


  // ########################## USER FUNCTIONS ################################

  function IsIPBanned($clientip)
  {
    if(empty($clientip)) return false;

    global $DB, $usersystem;

    $prevDB = $DB->database;
    $forumdbname = $usersystem['dbname'];
    $tableprefix = $usersystem['tblprefix'];
    if($prevDB != $forumdbname) $DB->select_db($forumdbname);

    $getban = $DB->query_first('SELECT fid FROM '.$tableprefix.'banfilters'.
                               ' WHERE type = 1 AND dateline < '.TIME_NOW.
                               " AND filter = '%s'",
                               $DB->escape_string($clientip));
    if($prevDB != $forumdbname) $DB->select_db($prevDB);

    return !empty($getban['fid']);

  } //IsIPBanned


  function IsUserBanned($userid)
  {
    if(empty($userid)) return false;

    global $DB, $usersystem;

    $prevDB = $DB->database;
    $forumdbname = $usersystem['dbname'];
    $tableprefix = $usersystem['tblprefix'];
    if($prevDB != $forumdbname) $DB->select_db($forumdbname);

    $getban = $DB->query_first('SELECT uid FROM '.$tableprefix.'banned'.
                               ' WHERE uid = %d AND dateline < '.TIME_NOW.
                               ' AND (lifted IS NULL OR lifted > '.TIME_NOW.')',
                               intval($userid));
    if($prevDB != $forumdbname) $DB->select_db($prevDB);

    return !empty($getban['uid']);

  } //IsUserBanned


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
        $url = 'member.php?action=register';
        break;
      case 2:
        $url = 'usercp.php';
        break;
      case 3:
        $url = 'member.php?action=lostpw';
        break;
      case 4:
        if($userid > 0)
        $url = 'usercp.php?action=profile&uid='.$userid;
        break;
      case 5:
        if($userid > 0)
        $url = 'private.php?action=send&amp;uid='.$userid;
        break;
    }

    return strlen($url) ? $sdurl.$usersystem['folderpath'].$url : '';

  } //ForumLink


  function ForumAvatar($userid, $username)
  {
    global $DB, $usersystem, $sdurl, $mybb_config;

    if(empty($userid) && (!isset($username) || !strlen($username)))
    {
      return '';
    }

    $prevDB = $DB->database;
    $forumdbname = $usersystem['dbname'];
    $tableprefix = $usersystem['tblprefix'];

    // get avatar settings
    $avatar_width = $avatar_height = (empty($mybb_config['postmaxavatarsize'])?70:
                                      (int)$mybb_config['postmaxavatarsize']);

    $useravatar_sql = 'SELECT avatar, avatartype, avatardimensions'.
                      ' FROM '.$tableprefix.'users'.
                      ' WHERE ';

    if($prevDB != $forumdbname) $DB->select_db($forumdbname);
    $DB->result_type = MYSQL_ASSOC;
    if($userid > ANONYMOUS)
    {
      $useravatar = $DB->query_first($useravatar_sql.'uid = %d', $userid);
    }
    else
    {
      $useravatar = $DB->query_first($useravatar_sql."username = '%s' LIMIT 1",
                                     $DB->escape_string($username));
    }
    if($prevDB != $forumdbname) $DB->select_db($prevDB);

    if(!empty($useravatar['avatardimensions']))
    {
      list($avatar_width, $avatar_height) = explode('|', $useravatar['avatardimensions']);
    }

    if(!strlen($useravatar['avatar'])) return '';

    $avatar = '<img border="0" alt="avatar" width="'.(int)$avatar_width.
              '" height="'.(int)$avatar_height.'" ';
    switch($useravatar['avatartype'])
    {
      case 'remote':
        $avatar .= 'src="'.$useravatar['avatar'];
        break;
      case 'gallery':
      case 'upload':
        $avatar .= 'src="'.$sdurl.$usersystem['folderpath'].$useravatar['avatar'];
        break;
      default: return '';
    }
    $avatar .= '" />';

    return $avatar;

  } //ForumAvatar


  // ######################## MyBB specific function #########################

  function mybb_canview_board()
  {
    global $DB, $userinfo, $usersystem;

    $prevDB = $DB->database;
    $forumdbname = $usersystem['dbname'];
    $tableprefix = $usersystem['tblprefix'];

    $allGroups = !empty($userinfo['forumusergroupids'])?$userinfo['forumusergroupids']:array(1);

    if($prevDB != $forumdbname) $DB->select_db($forumdbname);
    $perms = $DB->query_first(
      'SELECT SUM(ug.canview) c'.
      ' FROM '.$tableprefix.'usergroups ug'.
      ' WHERE ug.gid IN ('.implode(',', $allGroups).')');
    if($prevDB != $forumdbname) $DB->select_db($prevDB);

    if(empty($perms['c']) || ($perms['c'] < 1))
    {
      return false;
    }
    return true;

  } //mybb_canview_board


  function mybb_canview_forum($fid=0)
  {
    global $DB, $userinfo, $usersystem;

    if(empty($fid) || ($fid < 1)) return false;

    $prevDB = $DB->database;
    $forumdbname = $usersystem['dbname'];
    $tableprefix = $usersystem['tblprefix'];

    if($prevDB != $forumdbname) $DB->select_db($forumdbname);

    $allGroups = !empty($userinfo['forumusergroupids'])?$userinfo['forumusergroupids']:array(1);
    $canview = true;

    // For our purposes, a user must have BOTH canview/canviewthreads permissions!
    $tmp = $DB->query_first('SELECT COUNT(*) c, SUM(IF(fp.canview+fp.canviewthreads=2,1,0)) a'.
                            ' FROM '.$tableprefix.'forumpermissions fp'.
                            ' WHERE fp.fid = %d'.
                            ' AND fp.gid IN ('.implode(',', $allGroups).')',
                            $fid);
    if(!empty($tmp['c']) && empty($tmp['a']))
    {
      $canview = false;
    }
    if($prevDB != $forumdbname) $DB->select_db($prevDB);

    return $canview;

  } //mybb_canview_forum



  // ########################### DeleteSession ################################

  function DeleteSession($sessionid)
  {
    global $DB, $tableprefix;

    if($userid = $DB->query_first('SELECT uid FROM '.$tableprefix.'sessions'.
                                  " WHERE sid = '%s'",
                                  $sessionid))
    {
      $DB->query('DELETE FROM '.$tableprefix."sessions WHERE uid = %d AND useragent = '%s' AND ip = '%s'",
                 $userid['uid'], $DB->escape_string(USER_AGENT), USERIP);
      $DB->query('DELETE FROM '.$tableprefix."sessions WHERE sid = '%s'", $sessionid);
    }

  } //DeleteSession

  // ############################ SD SET COOKIE ###############################

  function sdsetcookie($name, $value = '', $expire=null)
  {
    global $cookiedomain, $cookiepath, $cookieprefix, $cookiesecure;

    if(is_null($value) || ($value==''))
    {
      $expire = 0; $value = '';
    }
    else
    if(is_null($expire) || ($expire == 0))
    {
      $expire = TIME_NOW + COOKIE_TIMEOUT;
    }
    else
    if($expire < 0)
    {
      $expire = null;
    }

    if(version_compare(PHP_VERSION, '5.2.0', 'ge'))
      @setcookie($cookieprefix . $name, $value, $expire, $cookiepath, (empty($cookiedomain)?null:$cookiedomain), $cookiesecure, true);
    else
      @setcookie($cookieprefix . $name, $value, $expire, $cookiepath, (empty($cookiedomain)?null:$cookiedomain), $cookiesecure);
  }

  // ######################### CREATE SESSION HASH ############################

  function CreateSessionHash()
  {
    return md5(uniqid(microtime(true)));
  }

  // ########################### CREATE SESSION ###############################

  if(!function_exists('CreateSession')) // may be defined in admin
  {
  function CreateSession($userid=0, $user_session_time=-1,
                         $autologin=0, $autologinid='')
  {
    global $DB, $sessioncreated, $cookieprefix, $tableprefix;

    $userid = (int)$userid;
    $loggedin = $userid > 0 ? 1 : 0;  // guest = 0

    // setup the session
    $session = array(
      'sid'             => CreateSessionHash(),
      'uid'             => $userid,
      'session_browser' => USER_AGENT,
      'session_start'   => TIME_NOW,
      'session_time'    => TIME_NOW,
      'session_ip'      => USERIP);

    // insert the session into the database
    $DB->query('REPLACE INTO ' . $tableprefix . "sessions
      (sid, uid, time, ip, useragent, location)
      VALUES ('%s', %d, %d, '%s', '%s', '')",
      $session['sid'], $session['uid'], $session['session_start'],
      USERIP, $DB->escape_string(USER_AGENT));

    // update last activity
    if($userid > 0)
    {
      $last_visit = (!empty($user_session_time) && ($user_session_time > 0)) ? (int)$user_session_time : TIME_NOW;
      $DB->query('UPDATE '.$tableprefix.'users SET lastactive = %d WHERE uid = %d',
                 $last_visit, $userid);
    }

    // return if we are logging in our logging out (since logging in and out already creates sessions)
    if(!isset($_POST['login']) && !isset($_GET['logout']))
    {
      // save the session id
      sdsetcookie('sid', $session['sid'], ($autologin?0:-1));
    }

    // set sessioncreated to true so that we don't update this session later on in the script
    // (because it was just created)
    $GLOBALS['sessioncreated'] = true;

    return $session;

  } //CreateSession
  }
} // special include check end


// switch to forum database
if($DB->database != $forumdbname) $DB->select_db($forumdbname);


// ###################### SETUP (EMPTY) USER VARIABLE #########################

unset($session, $user);
$user = $guestuser = array(
  'userid'         => 0, // guests are "0" userid
  'usergroupid'    => $BB_GUEST_ID, // "Guests"
  'usergroupids'   => array($BB_GUEST_ID),
  'loginkey'       => '',
  'logoutkey'      => '',
  'username'       => '',
  'displayname'    => '',
  'loggedin'       => 0,
  'email'          => '',
  'timezoneoffset' => 0,
  'dateformat'     => '',
  'dstonoff'       => 0,
  'dstauto'        => 1,
  'banned'         => 0,
  'salt'           => '',
  'activated'      => false);


// ########################## CHECK SESSION COOKIE ############################

$sessionid = '';
if(isset($_COOKIE[$cookieprefix.'sid']))
{
  $sessionid = $DB->escape_string($_COOKIE[$cookieprefix.'sid']);
}

$sessiondata = array();
if(isset($_COOKIE[$cookieprefix.'mybbuser']))
{
  $tmp = explode('_', $_COOKIE[$cookieprefix . 'mybbuser'], 2);
  if(!empty($tmp) && is_array($tmp) && (count($tmp)==2) &&
     is_numeric($tmp[0]) && (strlen($tmp[1])==50) && ctype_alnum($tmp[1]))
  {
    $sessiondata['uid'] = intval($tmp[0]);
    $sessiondata['loginkey'] = $DB->escape_string($tmp[1]);
  }
  unset($tmp);
}


// ####################### CHECK USER COOKIE ##################################

if(isset($sessiondata['uid']))
{
  $sql = 'SELECT u.uid'.
         ' FROM '.$tableprefix.'users u'.
         ' WHERE u.uid = '.(int)$sessiondata['uid'].
         " AND u.loginkey = '".$sessiondata['loginkey']."'".
         ' LIMIT 1';

  // If user cookie is invalid, remove cookie-related data
  if(!$row = $DB->query_first($sql, $sessionid))
  {
    sdsetcookie('mybbuser', '');
    sdsetcookie('sid', '');
    unset($_COOKIE[$cookieprefix.'mybbuser']);
    unset($_COOKIE[$cookieprefix.'sid']);
    unset($session,$sessiondata);
    $sessionid = '';
  }
  unset($row);
}


// ########################### CONTINUE SESSION ###############################

if($sessionid == '')
{
  // No session cookie, then remove mybb cookies
  sdsetcookie('mybbuser', '');
  sdsetcookie('sid', '');
}
else
{
  // Check if session row still exists:
  $sql = 'SELECT s.sid, s.uid, s.ip, s.time, s.anonymous, s.useragent'.
         ' FROM '.$tableprefix.'sessions s'.
         " WHERE s.sid = '".$sessionid."' LIMIT 1";

  $DB->result_type = MYSQL_ASSOC;
  if(($session = $DB->query_first($sql)) && !empty($session['sid']))
  {
    // Only update session a minute or so after last update
    if((TIME_NOW - $session['time']) > 60)
    {
      $sql = 'UPDATE '.$tableprefix.'sessions SET time = '.TIME_NOW.
             " WHERE sid = '".$sessionid."'";
      $DB->query($sql);
    }
  }
  else
  {
    unset($sessionid, $session);
    if(isset($_COOKIE[$cookieprefix.'mybbuser']))
    sdsetcookie('mybbuser', '');
    if(isset($_COOKIE[$cookieprefix.'sid']))
    sdsetcookie('sid', '');
  }
}


// ###################### CREATE GUEST SESSION ##############################

if(empty($session))
{
  // still no session, create a Guest session
  $session = CreateSession(0);
  $sessionid = $session['sid'];
}
else
if(!empty($session['uid']) && ($session['uid'] > 0) &&
   !(isset($_GET['logout']) || isset($_POST['logout'])))
{
  $DB->result_type = MYSQL_ASSOC;
  $getuser = $DB->query_first(
    'SELECT u.uid, u.username, u.loginkey, u.email, u.usergroup, u.salt,
     u.timezone, u.dateformat, u.dst, u.additionalgroups, u.usertitle,
     g.title as group_title, g.isbannedgroup
     FROM ' . $tableprefix . 'users u
     INNER JOIN ' . $tableprefix . 'usergroups g ON g.gid = u.usergroup
     WHERE u.uid = %d',
     (int)$session['uid']);

  // If user was not found and/or his primary group no longer exists,
  // then treat the user as a Guest:
  if( empty($getuser) || empty($getuser['uid']) ||
      #($getuser['group_title'] == 'Awaiting Activation') ||
      !empty($getuser['isbannedgroup']) ||
      ($getuser['group_title'] == 'Guests') )
  {
    $user['banned'] = empty($getuser['isbannedgroup'])?0:1;
  }
  else
  {
    $user = array('userid'         => $getuser['uid'],
                  'usergroupid'    => $getuser['usergroup'],
                  'usergroupids'   => array($getuser['usergroup']),
                  'loginkey'       => $getuser['loginkey'],
                  'logoutkey'      => md5($getuser['loginkey']),
                  'username'       => $getuser['username'],
                  'displayname'    => '',
                  'loggedin'       => 1,
                  'email'          => $getuser['email'],
                  'timezoneoffset' => $getuser['timezone'],
                  'dateformat'     => $getuser['dateformat'],
                  'dstonoff'       => $getuser['dst'],
                  'dstauto'        => 0,
                  'banned'         => 0,
                  'salt'           => (empty($getuser['salt'])?'':$getuser['salt']),
                  'activated'      => ($getuser['group_title'] != 'Awaiting Activation'));


    // additional groups?
    $usergroupids = $user['usergroupids'];
    if(!empty($getuser['additionalgroups']))
    if(false !== ($tmp = explode(',',$getuser['additionalgroups'])))
    {
      $usergroupids = array_merge($usergroupids, $tmp);
    }
  }

  if(empty($usergroupids))
  {
    // If none, default to "Guests"
    $user['usergroupids'] = array($BB_GUEST_ID);
  }
  else
  {
    // If user is member of "Guests", than do not use any other usergroup:
    if(false !== array_search($BB_GUEST_ID,$user['usergroupids'])) // Guest
    {
      $user['usergroupids'] = array($BB_GUEST_ID);
    }
    else
    {
      natsort($usergroupids);
      $user['usergroupids'] = $usergroupids;
    }
  }
  unset($getuser,$usergroupids);
}


// ########################### UPDATE SESSION #################################

if(!$sessioncreated && isset($session['sid']))
{
  $DB->query('UPDATE '.$tableprefix.'sessions'.
             " SET time = %d WHERE sid = '%s'".
             " AND ip = '".USERIP."'",
             TIME_NOW, $session['sid']);
}


// ############################### LOGIN ######################################

if(isset($_POST['login']) && ($_POST['login'] == 'login') &&
   !(isset($_GET['logout']) || isset($_POST['logout'])))
{
  $loginusername = isset($_POST['loginusername']) ? sd_unhtmlspecialchars($_POST['loginusername']) : '';
  $loginpassword = isset($_POST['loginpassword']) ? sd_unhtmlspecialchars($_POST['loginpassword']) : '';

  if(strlen($loginusername) && strlen($loginpassword))
  {
    /* myBB has 3 different login modes:
    0=Username Only, 1=Email Only, 2=Both Username and Email
    */
    if(empty($mybb_config['username_method']))
      $tmp = "u.username = '%s'";
    elseif($mybb_config['username_method']==1)
      $tmp = "u.email = '%s'";
    elseif($mybb_config['username_method']==2)
      $tmp = "(u.username = '%s') OR (u.email = '%s')";
    else $tmp = '0=1';

    $DB->result_type = MYSQL_ASSOC;
    if($getuser = $DB->query_first('SELECT u.*, g.isbannedgroup, a.aid, a.type,'.
      ' b.uid ban_uid, b.lifted ban_lifted, g.title group_title'.
      ' FROM ' . $tableprefix . 'users u'.
      ' LEFT JOIN ' . $tableprefix . 'usergroups g ON g.gid = u.usergroup'.
      ' LEFT JOIN ' . $tableprefix . 'banned b ON b.uid = u.uid'.
      ' LEFT JOIN ' . $tableprefix . 'awaitingactivation a ON a.uid = u.uid'.
      " WHERE ".$tmp." LIMIT 1",
      $DB->escape_string($loginusername)))
    {
      // Is IP banned?
      if(IsIPBanned(USERIP))
      {
        $login_errors_arr[] = $sdlanguage['you_are_banned'];
      }
      else
      // Is user banned?
      if( !empty($getuser['isbannedgroup']) ||
          (!empty($getuser['ban_uid']) && ($getuser['ban_lifted']) > TIME_NOW) )
      {
        $login_errors_arr[] = $sdlanguage['you_are_banned'];
      }
      else
      /*
      // MyBB allows users to login even if not yet activated!
      // Is user not yet activated?
      if(!empty($getuser['aid'])) // not activated
      {
        $login_errors_arr[] = $sdlanguage['not_yet_activated'];
      }
      else
      */
      // Does password match?
      if(!empty($loginpassword) &&
         (md5(md5($getuser['salt']).md5($loginpassword)) == $getuser['password']))
      {
        // logged in
        $user = array('userid'         => $getuser['uid'],
                      'username'       => $getuser['username'],
                      'usergroupid'    => $getuser['usergroup'],
                      'usergroupids'   => array($getuser['usergroup']),
                      'loginkey'       => $getuser['loginkey'],
                      'logoutkey'      => md5($getuser['loginkey']),
                      'salt'           => $getuser['salt'],
                      'displayname'    => '',
                      'loggedin'       => 1,
                      'email'          => $getuser['email'],
                      'timezoneoffset' => $getuser['timezone'],
                      'dateformat'     => $getuser['dateformat'],
                      'dstonoff'       => $getuser['dst'],
                      'dstauto'        => 0,
                      'banned'         => 0,
                      'activated'      => ($getuser['group_title'] != 'Awaiting Activation'));
        unset($getuser);

        // Check for secondary, non-guests usergroups of user
        $usergroupids = $user['usergroupids'];
        if(!empty($getuser['additionalgroups']))
        {
          if(false !== ($tmp = explode(',',$getuser['additionalgroups']))) //TBD???
          {
            $usergroupids = array_merge($usergroupids, $tmp);
          }
        }

        if(empty($usergroupids))
        {
          // If none, default to MyBB "Guests"
          $user['usergroupids'] = array($BB_GUEST_ID);
        }
        else
        {
          // If user is member of "Guests", than do not use any other usergroup:
          if(false !== array_search($BB_GUEST_ID,$user['usergroupids'])) // Guests
          {
            $user['usergroupids'] = array($BB_GUEST_ID);
          }
          else
          {
            natsort($usergroupids);
            $user['usergroupids'] = $usergroupids;
          }
        }
        unset($usergroupids);

        // erase old session
        DeleteSession($session['sid']);

        // save the password if the user has selected the 'remember me' option
        $rememberme = (GetVar('rememberme',false,'string',true,false)=='1');
        $session = CreateSession($user['userid'], -1, $rememberme);

        sdsetcookie('mybbuser', $user['userid'].'_'.$user['loginkey']);
        sdsetcookie('sid', $session['sid'], ($rememberme?0:-1));
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
    // clear mybbuser cookie
    sdsetcookie('mybbuser', '');
    $user = $guestuser;
  }

} //"Login"
else
{
  // ############################ LOGOUT ######################################

  if(isset($_GET['logout']) || isset($_POST['logout']))
  {
    $user['userid'] = (int)$user['userid'];
    if($user['userid'] > 0)
    {
      $DB->query('UPDATE '.$tableprefix.'users SET lastvisit = %d WHERE uid = %d',
                 TIME_NOW, $user['uid']);
    }

    DeleteSession($session['sid']);

    // clear cookies
    sdsetcookie('mybbuser', '');
    sdsetcookie('sid', $session['sid']);
    $user = $guestuser;

  } //"Logout"

} // end of "Login"/else

// ########################### ADD SESSION TO URL? ############################

/*
//TBD???
$stmp = isset($_SERVER['HTTP_USER_AGENT'])?(string)$_SERVER['HTTP_USER_AGENT']:'';
// Check bot user-agent strings
if((!empty($_COOKIE)) OR empty($stmp) OR preg_match("#(Mozilla/4\.0 (compatible;)|NimbleCrawler|www.fi crawler|ICF_Site|ZyBorg|Virgo|OmniExplorer_Bot|Python-urllib|IRLbot|MJ12bot|WebStripper|Gigabot|WNMbot|Snapbot|LinkChecker|TurnitinBot|Exabot|Accoona-AI|nutch|kroolari|Twisted PageGetter|google|msnbot|yahoo! slurp)#si", $stmp))
{
  $user['sessionurl'] = '';
}
else if (strlen($session['sid']) > 0)
{
  $user['sessionurl'] = 's=' . $session['session_id'];
}
unset($stmp);
*/

// ########################### DELETE OLD SESSIONS ############################

$DB->query('DELETE FROM ' . $tableprefix . 'sessions WHERE time < %d',
           (int)(TIME_NOW - 86400*365));

// switch back to Subdreamer database
if($DB->database != $database['name']) $DB->select_db($database['name']);

// ###################### SUBDREAMER USER SETTINGS SETUP #######################

$usersettings = array('userid'         => ($user['userid'] < 1 ? 0 : $user['userid']),
                      'usergroupid'    => $user['usergroupid'],
                      'usergroupids'   => $user['usergroupids'],
                      'username'       => $user['username'],
                      'loginkey'       => $user['loginkey'],
                      'logoutkey'      => $user['logoutkey'],
                      'displayname'    => $user['displayname'],
                      'loggedin'       => $user['loggedin'],
                      'email'          => $user['email'],
                      'timezoneoffset' => $user['timezoneoffset'],
                      'dateformat'     => $user['dateformat'],
                      'dstonoff'       => $user['dstonoff'],
                      'dstauto'        => $user['dstauto'],
                      'salt'           => (empty($user['salt'])?'':$user['salt']),
                      'activated'      => !empty($user['activated']));

// cleanup unused variables (except $mybb_config!)
unset($user, $guestuser, $session, $cn, $loginusername, $loginpassword,
      $sessiondata, $sql);
