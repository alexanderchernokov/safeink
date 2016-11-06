<?php

if(!defined('IN_PRGM')) return;

$sessioncreated = false;

// ############################# PHPBB2 VARIABLES ###############################

$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr(htmlentities(strip_tags($_SERVER['HTTP_USER_AGENT'])),0,255) : '';
define('USER_AGENT', $user_agent);

$tableprefix  = $usersystem['tblprefix'];

$cookiesecure = false;
if($getcookiesecure = $DB->query_first('SELECT config_value FROM ' . $tableprefix . "config WHERE config_name = 'cookie_secure'"))
{
  $cookiesecure = !empty($getcookiesecure[0]);
}
$cookietime   = $usersystem['cookietimeout'];
$cookieprefix = $usersystem['cookieprefix'];
$cookiedomain = isset($usersystem['cookiedomain']) ? $usersystem['cookiedomain'] : '';
$cookiepath   = isset($usersystem['cookiepath'])   ? $usersystem['cookiepath']   : "/";

// ################################ ENCODE IP ##################################
// written by the authors of phpBB2
if(!function_exists('encode_ip'))
{
  function encode_ip($dotquad_ip)
  {
    $ip_sep = explode('.', $dotquad_ip);
    $ip_sep[1] = isset($ip_sep[1])?$ip_sep[1]:'0';
    $ip_sep[2] = isset($ip_sep[2])?$ip_sep[2]:'0';
    $ip_sep[3] = isset($ip_sep[3])?$ip_sep[3]:'0';
    return @sprintf('%02x%02x%02x%02x', $ip_sep[0], $ip_sep[1], $ip_sep[2], $ip_sep[3]);
  }
}

// ############################### SD SET COOKIE ###############################

if(!function_exists('sdsetcookie'))
{
  function sdsetcookie($name, $value = '', $permanent = 1)
  {
    global $cookieprefix, $cookiesecure, $cookiepath, $cookiedomain;

    $expire = TIMENOW;
    if(!empty($permanent))
    {
      $expire += empty($phpbb_config['max_autologin_time']) ? 31536000 : (86400 * (int)$phpbb_config['max_autologin_time']);
    }
    $name = $cookieprefix . $name;

    setcookie($name, $value, $expire, $cookiepath, $cookiedomain, $cookiesecure);
  } //sdsetcookie
}

// ################################# USER IP ###################################
$clientip = !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ( !empty($_ENV['REMOTE_ADDR']) ? $_ENV['REMOTE_ADDR'] : $REMOTE_ADDR );
$userip   = encode_ip($clientip);

// ################################## DEFINES ##################################

// Defining timenow in the event this is also called by a developer.  This is
// initally set in core.php
defined('TIMENOW') || define('TIMENOW', time());
define('SESSION_HOST', $userip);
defined('ANONYMOUS') || define('ANONYMOUS', -1);


// ############################ CREATE SESSION HASH ############################

function CreateSessionHash()
{
  return md5(uniqid(SESSION_HOST));
}


function CreateAutoLoginID($userid, $session_id, $autologinid = '')
{
  global $DB, $tableprefix;

  list($sec, $usec) = explode(' ', microtime());
  mt_srand(hexdec(substr($session_id, 0, 8)) + (float) $sec + ((float) $usec * 1000000));
  $auto_login_key = uniqid(mt_rand(), true);

  if (isset($autologinid) && (string) $autologinid != '')
  {
    $DB->query("UPDATE ". $tableprefix . "sessions_keys
      SET last_ip = '%d', key_id = '%s', last_login = %d
      WHERE key_id = '%s'",
      SESSION_HOST, md5($auto_login_key), TIMENOW, md5($autologinid));
  }
  else
  {
    $DB->query('INSERT INTO '. $tableprefix . "sessions_keys (key_id, user_id, last_ip, last_login)
      VALUES ('%s', %d, '%s', %d)",
      md5($auto_login_key), $userid, SESSION_HOST, TIMENOW);
  }

  return $auto_login_key;
} //CreateAutoLoginID


function DeleteSession($sessionid, $autologinid = null)
{
  global $DB, $tableprefix, $v2018;

  if($v2018)
  {
    if(isset($autologinid))
    {
      $DB->query('DELETE FROM '. $tableprefix . "sessions_keys WHERE key_id = '%s'", md5($autologinid));
    }
    else
    {
      $userid = $DB->query_first('SELECT session_user_id FROM ' . $tableprefix . "sessions WHERE session_id = '%s'", $sessionid);

      if(isset($userid))
      {
          $DB->query('DELETE FROM '. $tableprefix . 'sessions_keys WHERE user_id = %d', $userid[0]);
      }
    }
  }
  $DB->query('DELETE FROM ' . $tableprefix . "sessions WHERE session_id = '%s'", $sessionid);

} //DeleteSession


// ############################## CREATE SESSION ###############################

function CreateSession($userid = -1, $user_session_time = -1, $autologin = 0, $autologinid = '')
{
  global $DB, $sessioncreated, $tableprefix, $v2018;

  $userid = intval($userid);

  $loggedin = $userid > 1 ? 1 : 0;  // phpbb2 users start from id 1

  // setup the session
  $session = array(
    'session_id'        => CreateSessionHash(),
    'session_user_id'   => $userid,
    'session_start'     => TIMENOW,
    'session_time'      => TIMENOW,
    'session_ip'        => SESSION_HOST,
    'session_page'      => 0,
    'session_logged_in' => $loggedin,
    'session_autologinid' => '');

  // insert the session into the database
  $DB->query('REPLACE INTO ' . $tableprefix . "sessions (session_id, session_user_id, session_start, session_time, session_ip, session_page, session_logged_in)
    VALUES ('%s', %d, %d, %d, '%s', %d, %d)",
    $session['session_id'], $session['session_user_id'], $session['session_start'], $session['session_time'],
    $session['session_ip'], $session['session_page'], $session['session_logged_in']);

  // update last activity
  if($userid > 0)
  {
    $last_visit = ( $user_session_time > 0 ) ? $user_session_time : TIMENOW;

    $DB->query('UPDATE ' . $tableprefix . 'users SET user_session_time = %d, user_lastvisit = %d WHERE user_id = %d',
      TIMENOW, $last_visit, $userid);

    if($autologin && $v2018)
    {
      $session['session_autologinid'] = CreateAutoLoginID($userid, $session['session_id'], $autologinid);
    }
  }

  // return if we are logging in our logging out (since logging in and out already creates sessions)
  if(!isset($_POST['login']) AND !isset($_GET['logout']))
  {
    // save the session id
    sdsetcookie('_sid', $session['session_id'], 1);
  }

  // set sessioncreated to true so that we don't update this session later
  // on in the script (because it was just created)
  $sessioncreated = true;

  return $session;

} //CreateSession


// ###################### IS THIS VERSION 2.1.8 or later? #####################

$v2018 = false;
$version = $DB->query_first('SELECT config_value FROM ' . $tableprefix . "config WHERE config_name = 'version'");
if(isset($version[0]))
{
  $ver = explode('.', $version[0]);
  if(count($ver) == 3 && ((intval($ver[1]) > 0) || (intval($ver[2]) > 18)))
  {
    $v2018 = true;
  }
}
unset($version, $ver);

// ############################# FIND SESSION HASH #############################

if(!empty($_POST['s']))
{
  $sessionhash = $_POST['s'];
}
else if (!empty($_GET['s']))
{
  $sessionhash = $_GET['s'];
}
else
{
  $sessionhash = isset($_COOKIE[$cookieprefix . '_sid']) ? $_COOKIE[$cookieprefix . '_sid'] : '';
}

// ############################# CONTINUE SESSION #############################

if(!empty($sessionhash))
{
  $session = $DB->query_first('SELECT * FROM ' . $tableprefix . "sessions
    WHERE session_id = '%s' AND session_time > %d AND session_ip = '%s'",
    trim($sessionhash), (TIMENOW - $cookietime), SESSION_HOST);
}

// ############################### COOKIE LOGIN ###############################

if(empty($session) || empty($session['session_user_id']) || ($session['session_user_id'] < 1))
{
    // session is not set or the user is a guest, lets check the cookies:

  if(isset($_COOKIE[$cookieprefix . '_data']))
  {
    $sessiondata = array();
    if(isset($_COOKIE[$cookieprefix . '_data']))
    {
      $sessiondata = sd_unhtmlspecialchars($_COOKIE[$cookieprefix . '_data']);
      $sessiondata = unserialize(stripslashes($sessiondata));
    }

    if(!empty($sessiondata['userid']) &&
       !empty($sessiondata['autologinid']) &&
       is_numeric($sessiondata['userid']))
    {
        if($v2018)
        {
          $user = $DB->query_first("SELECT u.* FROM " . $tableprefix .
            "users u INNER JOIN ". $tableprefix . "sessions_keys k ON k.user_id = u.user_id
            LEFT OUTER JOIN " . $tableprefix . "banlist b ON b.ban_userid = u.user_id
            WHERE b.ban_id IS NULL
            AND u.user_id = %d
            AND u.user_active = 1
            AND k.key_id = '%s'",
            intval($sessiondata['userid']), md5($sessiondata['autologinid']));
        }
        else
        {
          $user = $user = $DB->query_first("SELECT u.* FROM " . $tableprefix . "users u
            LEFT OUTER JOIN " . $tableprefix . "banlist b ON b.ban_userid = u.user_id
            WHERE b.ban_id IS NULL
            AND u.user_id = %d
            AND u.user_password = '%s'",
            $sessiondata['userid'], $sessiondata['autologinid']);
        }

        if($user)
        {
          // combination is valid
          if(!empty($session['session_id']))
          {
            // old session still exists; kill it
            DeleteSession($sessiondata['session_id'], (isset($sessiondata['autologinid'])?$sessiondata['autologinid']:null));
          }

          $session = CreateSession($user['user_id'], $user['user_session_time']);
        }
        else if(isset($_POST['login'])) // cookie is bad!
        {
          // cookie's bad and since we're not doing anything login related, kill the bad cookie
          sdsetcookie('_data', '', 1);
          sdsetcookie('_sid', '', 0);
        }
    }
  }
}


// ########################### CREATE GUEST SESSION ############################

if(empty($session))
{
  // still no session, create a guest session
  $session = CreateSession(-1);
}


// ############################ SETUP USER VARIABLE ############################

if($session['session_user_id'] < 1)
{
  $user = array(
  'userid'         => -1,   // phpbb guests are -1 userid
  'usergroupids'   => 1,    // phpBB2 - Unregistered / Not Logged In
  'username'       => '',
  'loggedin'       => 0,
  'email'          => '',
  'timezoneoffset' => 0,
  'dstonoff'       => 0,
  'dstauto'        => 1);
}
else
{
  $getuser = $DB->query_first("SELECT * FROM " . $tableprefix . "users WHERE user_id = %d", $session['session_user_id']);

  $user = array(
  'userid'         => $getuser['user_id'],   // phpbb guests are -1 userid
  'username'       => $getuser['username'],
  'loggedin'       => 1,
  'email'          => $getuser['user_email'],
  'timezoneoffset' => $getuser['user_timezone'],
  'dstonoff'       => 0,    // phpBB2 doesn't have a dst option
  'dstauto'        => 0);   // phpBB2 doesn't have a dst option

  unset($getuser);

  // find the usergroupids
  // phpbb2 creates a new usergroup for each user, but I don't want to show
  // hundreds of usergroups in the admin panel so lets only select the
  // usergroups that don't have a strlen of 0

  $usergroupids = array();
  if($getusergroups = $DB->query("SELECT g.group_id FROM " . $tableprefix . "groups g, " . $tableprefix . "user_group u
     WHERE u.user_id = %d AND u.group_id = g.group_id AND g.group_name != ''", $user['userid']))
  {
    while($usergroup = $DB->fetch_array($getusergroups))
    {
      // now find the usergroup name
      if(empty($usergroupids) || !@in_array($usergroup['group_id'], $usergroupids))
      {
        $usergroupids[] = $usergroup['group_id'];
      }
    }
  }

  // add the 'fake' registered group?
  if(empty($usergroupids) &&
     ($isregistered = $DB->query_first("SELECT g.group_id FROM " . $tableprefix . "groups g, " . $tableprefix . "user_group u
      WHERE u.user_id = %d AND u.group_id = g.group_id AND g.group_name = ''", $user['userid'])))
  {
    $usergroupids[] = -3; // usergroup id -3 is an emulated registered usergroup for phpBB2 created by Subdreamer
  }

  $user['usergroupids'] = $usergroupids;

  unset($usergroupids);
}


// ############################## UPDATE SESSION ###############################

if(!$sessioncreated)  // unless the session was just created....
{
  $DB->query('UPDATE ' . $tableprefix . "sessions SET session_time = %d
     WHERE session_id = '%s'", TIMENOW, $DB->escape_string($session['session_id']));
}


// ################################### LOGIN ###################################

if(isset($_POST['login']) && $_POST['login'] == 'login')
{
  $loginusername = isset($_POST['loginusername']) ? $_POST['loginusername'] : '';
  $loginpassword = isset($_POST['loginpassword']) ? $_POST['loginpassword'] : '';

  // Transliterate credentials from forum to SD character set:
  $to_charset = !empty($mainsettings['forum_character_set']) ? $mainsettings['forum_character_set'] : $sd_charset;
  if(!empty($sd_charset) && function_exists('sd_GetConverter') &&
     !empty($to_charset) && ($to_charset !== $sd_charset))
  {
    // Create new object for forum character set
    $convobj = sd_GetConverter($sd_charset, $to_charset, 1);
    if(defined('SD_CVC') && isset($convobj) && isset($convobj->CharsetTable))
    {
      $loginusername = $convobj->Convert($loginusername);
      $loginpassword = $convobj->Convert($loginpassword);
    }
  }

  if(strlen($loginusername))
  {
    // get userid for given username
    if($getuser = $DB->query_first("SELECT u.*, b.ban_id FROM " . $tableprefix . "users u LEFT OUTER JOIN " .
                  $tableprefix . "banlist b ON b.ban_userid = u.user_id WHERE u.username = '%s'", $loginusername))
    {
      if(!empty($getuser['ban_id']))
      {
        $login_errors_arr = $sdlanguage['you_are_banned'];
      }
      else if(md5($loginpassword) === $getuser['user_password'] AND $getuser['user_active'] AND $getuser['user_id'] != ANONYMOUS)
      {
        // logged in
        $user = array(
          'userid'         => $getuser['user_id'],   // phpbb guests are -1 userid
          'usergroupid'    => -3,           // phpBB2 Registered, this is just the default group, more are gathered next
          'usergroupids'   => array(-3),
          'username'       => $getuser['username'],
          'loggedin'       => 1,
          'email'          => $getuser['user_email'],
          'timezoneoffset' => $getuser['user_timezone'],
          'dstonoff'       => 0,
          'dstauto'        => 0);

        $user_password = $getuser['user_password'];
        unset($getuser);

        // find the usergroupids
        // phpbb2 creates a new usergroup for each user, but I don't want to show hundreds of usergroups in the admin panel
        // so lets only select the usergroups that don't have a strlen of 0
        $usergroupids = array();
        if($getusergroups = $DB->query('SELECT g.group_id FROM ' . $tableprefix . 'groups g, ' .
          $tableprefix . "user_group u WHERE u.user_id = %d AND u.group_id = g.group_id AND g.group_name != ''",
          $user['userid']))
        {
          while($usergroup = $DB->fetch_array($getusergroups))
          {
            // now find the usergroup name
            if(empty($usergroupids) || !in_array($usergroup['group_id'], $usergroupids))
            {
              $usergroupids[] = $usergroup['group_id'];
            }
          }
        }

        // add the 'fake' registered group?
        if(empty($usergroupids) &&
          ($isregistered = $DB->query_first('SELECT g.group_id FROM ' . $tableprefix . 'groups g, ' . $tableprefix . "user_group u
            WHERE u.user_id = %d AND u.group_id = g.group_id AND g.group_name = ''", $user['userid'])))
        {
          $usergroupids[] = -3; // usergroup id -3 is an emulated registered usergroup for phpBB2 created by Subdreamer
        }

        $user['usergroupids'] = empty($usergroupids)?array():$usergroupids;

        unset($usergroupids);

        // erase old session
        DeleteSession($session['session_id']);

        // insert new session
        $session['session_id'] = CreateSessionHash();

        // insert the session into the database
        $DB->query('REPLACE INTO ' . $tableprefix . "sessions (session_id, session_user_id, session_start, session_time, session_ip, session_page, session_logged_in)
            VALUES ('%s', %d, %d, %d, '%s', 0, 1)",
            $session['session_id'], intval($user['userid']), TIMENOW, TIMENOW, SESSION_HOST);

        // save the sessionhash in the cookie
        sdsetcookie('_sid', $session['session_id'], 1);

        // save the password if the user has selected the 'remember me' option
        if($v2018)
        {
          $sessiondata['autologinid'] = !empty($_POST['rememberme']) ? CreateAutoLoginID($user['userid'], $session['session_id']) : '';
        }
        else
        {
          $sessiondata['autologinid'] = !empty($_POST['rememberme']) ? $user_password : '';
        }

        $sessiondata['userid'] = $user['userid'];

        sdsetcookie('_data', serialize($sessiondata), 1);
      }
      else
      {
        $login_errors_arr = $sdlanguage['wrong_username'];
      }
    }
    else
    {
      // wront username OR: username = ANONYMOUS, probably a hacker
      // lets just give them wrong username to throw them off
      $login_errors_arr = $sdlanguage['wrong_username'];
    }
  }
  else
  {
    $login_errors_arr = $sdlanguage['please_enter_username'];
  }
}


// ################################## LOGOUT ###################################

if(isset($_GET['logout']))
{
  // clear cookies
  sdsetcookie('_data', '', 1);
  sdsetcookie('_sid', '', 0);

  if(!empty($user['userid']) && ((int)$user['userid'] > 1))
  {
    $DB->query('UPDATE ' . $tableprefix . 'users SET user_lastvisit = %d WHERE user_id = %d', TIMENOW, $user['userid']);

    // make sure any other of this user's sessions are deleted (in case they ended up with more than one)
    if($v2018)
    {
      $DB->query('DELETE FROM ' . $tableprefix . 'sessions_keys WHERE user_id = %d', (int)$user['userid']);
    }
    $DB->query('DELETE FROM ' . $tableprefix . 'sessions WHERE session_user_id = %d', (int)$user['userid']);
  }

  DeleteSession($session['session_id'], (isset($session['autologinid'])?$session['autologinid']:null));

  $session['session_id'] = CreateSessionHash();

  // insert the session into the database
  $DB->query('REPLACE INTO ' . $tableprefix . "sessions (session_id, session_user_id,
     session_start, session_time, session_ip, session_page, session_logged_in)
     VALUES ('%s', -1, %d, %d, '%s', 0, 0)",
     $DB->escape_string($session['session_id']), TIMENOW, TIMENOW, SESSION_HOST);

  sdsetcookie('_sid', $session['session_id'], 1);

  $user = array('userid'   => -1,   // phpbb guests are -1 userid
    'usergroupid'    => -3,          // phpBB2 - Guests / Not Logged In
    'usergroupids'   => array(-3),
    'username'       => '',
    'loggedin'       => 0,
    'email'          => '',
    'timezoneoffset' => 0,
    'dstonoff'       => 0,
    'dstauto'        => 1);
}


// ############################ ADD SESSION TO URL? ############################

$b2tmp = isset($_SERVER['HTTP_USER_AGENT'])?(string)$_SERVER['HTTP_USER_AGENT']:'';
if(sizeof($_COOKIE) > 0 || (!empty($b2tmp) && preg_match(SD_BOT_AGENTS, $b2tmp)))
{
  $user['sessionurl'] = '';
}
else if (strlen($session['session_id']) > 0)
{
  $user['sessionurl'] = 's=' . $session['session_id'];
}
unset($b2tmp);

// ############################ DELETE OLD SESSIONS ############################

$DB->query('DELETE FROM ' . $tableprefix . 'sessions WHERE session_time < %d', intval(TIMENOW - $cookietime));

// switch back to Subdreamer database
$DB->select_db($database['name']);
$dbname = $database['name'];

// ###################### SUBDREAMER USER SETTINGS SETUP #######################

$usersettings = array(
  'userid'         => $user['userid'],
  'usergroupid'    => (isset($user['usergroupid'])?(int)$user['usergroupid']:-3),
  'usergroupids'   => (isset($user['usergroupids'])?$user['usergroupids']:array(-3)),
  'username'       => $user['username'],
  'loggedin'       => $user['loggedin'],
  'email'          => $user['email'],
  'timezoneoffset' => $user['timezoneoffset'],
  'dstonoff'       => $user['dstonoff'],
  'dstauto'        => $user['dstauto'],
  'sessionurl'     => $user['sessionurl']);


// ############################## UNSET VARIABLES ##############################

unset($user, $session, $sessionhash);

// ############################## USER FUNCTIONS ###############################

function IsIPBanned($clientip)
{
  global $DB, $usersystem, $dbname;

  $user_ip_parts = null;

  preg_match('/(..)(..)(..)(..)/', encode_ip($clientip), $user_ip_parts);

  $query = "SELECT ban_ip FROM " . $usersystem['tblprefix'] . "banlist
    WHERE ban_ip IN ('" . $user_ip_parts[1] . $user_ip_parts[2] . $user_ip_parts[3] .
    $user_ip_parts[4] . "', '" . $user_ip_parts[1] . $user_ip_parts[2] .
    $user_ip_parts[3] . "ff', '" . $user_ip_parts[1] .
    $user_ip_parts[2] . "ffff', '" . $user_ip_parts[1] . "ffffff')";

  if($usersystem['dbname'] != $dbname)
  {
    // Subdreamer is being integrated with a Forum in a different database
    $DB->select_db($usersystem['dbname']);
    $getbanip = $DB->query($query);
    $DB->select_db($dbname);
  }
  else
  {
    $getbanip = $DB->query($query);
  }

  if($banip = $DB->fetch_array($getbanip))
  {
    if($banip['ban_ip'])
    {
      return true;
    }
  }

  return false;
}


// Returns the relevent forum link url
// linkType
// 1 - Register
// 2 - UserCP
// 3 - Recover Password
// 4 - UserCP (requires $userid)
// 5 - SendPM (requires $userid)
function ForumLink($linkType, $userid = -1)
{
  global $sdurl, $usersystem;

  switch($linkType)
  {
    case 1:
      $url = 'profile.php?mode=register';
      break;
    case 2:
      $url = 'profile.php?mode=editprofile';
      break;
    case 3:
      $url = 'profile.php?mode=sendpassword';
      break;
    case 4:
      $url = 'profile.php?mode=viewprofile&amp;u=' . $userid;
      break;
    case 5:
      $url = 'privmsg.php?mode=post&amp;u=' . $userid;
      break;
  }

  return $sdurl . $usersystem['folderpath'] . $url;

} //ForumLink


function ForumAvatar($userid, $username)
{
  global $DB, $dbname, $usersystem, $sdurl;

  $avatar = '';

  // forum information
  $forumdbname = $usersystem['dbname'];
  $forumpath   = $usersystem['folderpath'];
  $tableprefix = $usersystem['tblprefix'];

  // switch to forum database
  if($dbname != $forumdbname)
  {
    $DB->select_db($forumdbname);
  }

  // get avatar settings
  $avatarpath        = $DB->query_first('SELECT config_value FROM ' . $tableprefix . "config WHERE config_name = 'avatar_path'");
  $avatargallerypath = $DB->query_first('SELECT config_value FROM ' . $tableprefix . "config WHERE config_name = 'avatar_gallery_path'");

  if($userid > 0)
  {
    $useravatar = $DB->query_first('SELECT user_avatar, user_avatar_type FROM ' .
                  $tableprefix . 'users WHERE user_id = %d', $userid);
  }
  else
  {
    $useravatar = $DB->query_first('SELECT user_avatar, user_avatar_type FROM ' .
                  $tableprefix . "users WHERE username = '%s'",
                  $DB->escape_string($username));
  }

  if(!empty($useravatar['user_avatar']))
  {
    $avatar = '';
    switch($useravatar['user_avatar_type'])
    {
      case 1:  // uploaded avatar in avatars folder
        $avatar = '<img alt="avatar" src="' . $sdurl . $forumpath . $avatarpath['config_value'] . '/' . $useravatar['user_avatar'] . '" />';
        break;

      case 2:  // an external link to an avatar (offsite)
        $avatar = '<img alt="avatar" src="' . $useravatar['user_avatar'] . '" />';
        break;

      case 3:  // uploaded avatar in the avatars -> gallery folder
        $avatar = '<img alt="avatar" src="' . $sdurl . $forumpath . $avatargallerypath['config_value'] . '/' . $useravatar['user_avatar'] . '" />';
        break;
    }
  }

  // switch back to subdreamer database
  if($dbname != $forumdbname)
  {
    $DB->select_db($dbname);
  }

  return $avatar;
} //ForumAvatar
