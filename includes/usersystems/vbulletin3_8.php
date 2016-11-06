<?php

if(!defined('IN_PRGM')) exit();

// ###################### START WITH SESSION NOT CREATED #######################

$sessioncreated = false;

// ############################## FORUM SETTINGS ###############################

$tableprefix     = $usersystem['tblprefix'];
$cookietimeout   = $usersystem['cookietimeout'];
$cookieprefix    = $usersystem['cookieprefix'];
define('COOKIE_PREFIX', $cookieprefix);
define('REQ_PROTOCOL', 'http'); // TODO: check for SSL?
$cookiedomain    = isset($usersystem['cookiedomain']) ? $usersystem['cookiedomain'] : '';
$cookiepath      = isset($usersystem['cookiepath'])   ? $usersystem['cookiepath'] : "/";

// ####### Get the group id's for banned and unregistered usergroups: ##########
$get_banned_id = $DB->query_first('SELECT usergroupid FROM ' . $tableprefix . "usergroup WHERE title = 'Banned Users'");
$VB_BANNED_GID = isset($get_banned_id[0]) ? (int)$get_banned_id[0] : null;

$get_guest_id = $DB->query_first('SELECT usergroupid FROM ' . $tableprefix . "usergroup WHERE title LIKE 'Unregistered%'");
$VB_GUEST_GID = isset($get_guest_id[0]) ? (int)$get_guest_id[0] : null;

unset($get_banned_id, $get_guest_id);

// REPLACE VALUE HERE WITH forum/includes/functions.php:
defined('COOKIE_SALT') || define('COOKIE_SALT', 'must get value from vb/includes/functions.php!');

// ####################### FIX VBULLETIN 3 COOKIE PREFIX #######################
// vbulletin 3 defaults to bb if a cookieprefix is blank, strange... but whatever!

$cookieprefix = strlen($cookieprefix) ? $cookieprefix : 'bb';
$sessionlimit = 0;
$getipoctet   = $DB->query_first("SELECT value FROM " . $tableprefix . "setting WHERE varname = 'ipcheck'");
$ipoctet      = isset($getipoctet['value']) ? $getipoctet['value'] : 0;

// ################################ FIND ALT IP ################################

$alt_ip = '';
if(isset($_SERVER['HTTP_CLIENT_IP']))
{
  $alt_ip = $_SERVER['HTTP_CLIENT_IP'];
}
else
if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches))
{
  if(isset($matches[0]))
  {
    foreach($matches[0] AS $ip)
    {
      if(!preg_match("#^(10|172\.16|192\.168)\.#", $ip))
      {
        $alt_ip = $ip;
        break;
      }
    }
  }
}
else if(isset($_SERVER['HTTP_FROM']))
{
  $alt_ip = $_SERVER['HTTP_FROM'];
}
else
{
  $alt_ip = $_SERVER['REMOTE_ADDR'];
}

//Disect the IP and put it back together based on the octet in VB.
//This option is set in "Server Settings and Optimization Options" of VB.
$alt_ip = implode('.', array_slice(explode('.', $alt_ip), 0, 4 - $ipoctet));
define('ALT_IP', $alt_ip);

// ################################## DEFINES ##################################
define('SESSION_IDHASH', md5((isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '') . ALT_IP));

$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr(htmlentities(strip_tags($_SERVER['HTTP_USER_AGENT'])),0,255) : '';
define('USER_AGENT',   $user_agent);
define('IPADDRESS',    $_SERVER['REMOTE_ADDR']);
define('SESSION_HOST', substr(IPADDRESS, 0, 15));
defined('TIMENOW') || define('TIMENOW', time());

// ############################## USER FUNCTIONS ##############################

if(!defined('SD_VB_INCLUDED2'))
{
  define('SD_VB_INCLUDED2', true);

  function IsIPBanned($clientip)
  {
    global $DB, $usersystem, $dbname;

    if($usersystem['dbname'] != $dbname)
    {
      // Subdreamer is being integrated with a Forum in a different database
      $DB->select_db($usersystem['dbname']);
      $getbanip = $DB->query_first('SELECT value FROM ' . $usersystem['tblprefix'] . 'setting WHERE varname = \'banip\'');
      $DB->select_db($dbname);
    }
    else
    {
      $getbanip = $DB->query_first('SELECT value FROM ' . $usersystem['tblprefix'] . 'setting WHERE varname = \'banip\'');
    }

    $banip = trim($getbanip[0]);

    /* This isn't the same code as VB because their code has a bug in it :) */
    $addresses = explode(' ', preg_replace("/[[:space:]]+/", " ", $banip) );
    $clientaddresses = explode('.', $clientip);
    if(isset($addresses))
    foreach ($addresses AS $val)
    {
      if (strpos(' ' . $clientip, ' ' . trim($val)) !== false)
      {
        // Do we have a full match on last octet of ban IP
        $ban_ip_a = explode(".", trim($val));
        if ($ban_ip_a[count($ban_ip_a) - 1] == $clientaddresses[count($ban_ip_a) - 1])
        {
          return true;
        }
      }
    }
    return false;

  } //IsIPBanned

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
      $url = 'register.php';
      break;
    case 2:
      $url = 'usercp.php';
      break;
    case 3:
      $url = 'login.php?do=lostpw';
      break;
    case 4:
      $url = 'member.php?u=' . $userid;
      break;
    case 5:
      $url = 'private.php?do=newpm&amp;u=' . $userid;
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

    if($DB->database != $forumdbname) $DB->select_db($forumdbname);

    $extrasql = ($userid > 0) ? 'WHERE user.userid = ' . (int)$userid :
                                'WHERE user.username = "' . $DB->escape_string((string)$username) . '"';

    $query = 'SELECT user.avatarid, user.avatarrevision, avatarpath, NOT ISNULL(filedata) hascustom,
      customavatar.dateline, customavatar.filename, user.userid
      FROM ' . $tableprefix . 'user user
      LEFT JOIN ' . $tableprefix . 'avatar avatar ON avatar.avatarid = user.avatarid
      LEFT JOIN ' . $tableprefix . 'customavatar customavatar ON customavatar.userid = user.userid ' . $extrasql;
    $DB->result_type = MYSQL_ASSOC;
    if($avatarinfo = $DB->query_first($query))
    {
      if(!empty($avatarinfo['avatarpath']))
      {
        $avatar = '<img alt="" class="avatar" src="' . $sdurl . $forumpath . $avatarinfo['avatarpath'] . '" />';
      }
      else if(!empty($avatarinfo['hascustom']) || !empty($avatarinfo['filename']))
      {
        $usefileavatar = $DB->query_first('SELECT value FROM ' . $tableprefix . 'setting WHERE varname=\'usefileavatar\'');
        if(isset($usefileavatar[0]) && $usefileavatar[0])
        {
          $avatarurl = $DB->query_first('SELECT value FROM ' . $tableprefix . 'setting WHERE varname=\'avatarurl\'');

          if(substr($avatarurl[0], 0, 1) == '/')
          {
            $avurl = $avatarurl[0];
          }
          else
          {
            $avurl = $sdurl . $forumpath . $avatarurl[0];
          }

          $avatar = '<img alt="" class="avatar" src="' . $avurl . '/avatar' . $avatarinfo['userid'] . '_' . $avatarinfo['avatarrevision'] . '.gif" />';
        }
        else
        {
          $avatar = '<img alt="" class="avatar" src="' . $sdurl . $forumpath . 'image.php?u=' . $avatarinfo['userid'] . '&amp;dateline=' . $avatarinfo['dateline'] . '" />';
        }
      }
    }
    if(empty($avatar))
    {
      $avatar = GetDefaultAvatarImage();
    }

    if($DB->database != $dbname) $DB->select_db($dbname);

    return $avatar;
  }

} //ForumAvatar

// ########################### SPECIAL INCLUDE CHECK ###########################
if(!defined('SD_VB_INCLUDED'))
{
  define('SD_VB_INCLUDED', true);

  // ############################### CREATE COOKIE ###############################
  //vbsetcookie($name, $value = '', $permanent = true, $allowsecure = true, $httponly = false)
  function CreateCookie($name, $value = '', $permanent = 1, $allowsecure = true, $httponly = false)
  {
    global $_SERVER, $cookieprefix, $cookiepath, $cookiedomain;

    $name   = $cookieprefix . $name;
    $expire = $permanent ? (TIMENOW + 31536000) : 0;
    $secure = (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] == '443')) ? 1 : 0; // secure(1) = using SSL
    if(version_compare(PHP_VERSION, '5.2.0', 'ge'))
    {
      @setcookie($name, $value, $expire, $cookiepath, $cookiedomain, $secure, $httponly);
    }
    else
    {
      @setcookie($name, $value, $expire, $cookiepath, $cookiedomain, $secure);
    }
  }


  // ############################ CREATE SESSION HASH ############################

  function CreateSessionHash()
  {
    return md5(TIMENOW . SESSION_IDHASH . SESSION_HOST . mt_rand(1, 1000000));
  }


  // ############################## CREATE SESSION ###############################

  if(!function_exists('CreateSession')) // may be defined in admin
  {
  function CreateSession($userid = 0)
  {
    global $DB, $sessionlimit, $sessioncreated, $tableprefix;

    // setup the session
    $session = array('sessionhash'  => CreateSessionHash(),
                     'userid'       => (int)$userid,
                     'host'         => SESSION_HOST,
                     'useragent'    => USER_AGENT,
                     'idhash'       => SESSION_IDHASH,
                     'lastactivity' => TIMENOW);

    // return the session if the sessionlimit has exceeded
    if($sessionlimit > 0)
    {
      $sessions = $DB->query_first('SELECT COUNT(*) AS sessioncount FROM ' . $tableprefix . 'session');

      if($sessions['sessioncount'] > $sessionlimit)
      {
        return $session;
      }
    }

    // return if we are logging in our logging out (since logging in and out already creates sessions)
    if(isset($_POST['login']) || isset($_GET['logout']))
    {
      return null;
    }

    // insert the session into the database
    $DB->query('INSERT INTO ' . $tableprefix . "session (sessionhash, userid, host, useragent, idhash, lastactivity)
                VALUES ('%s', %d, '%s', '%s', '%s', %d)",
                $session['sessionhash'], $session['userid'],
                $DB->escape_string($session['host']),
                $DB->escape_string(USER_AGENT), $session['idhash'], $session['lastactivity']);

    // save the sessionhash
    CreateCookie('sessionhash', $session['sessionhash'], 0);

    // set sessioncreated to true so that we don't update this session later on in the script
    // (because it was just created)
    $sessioncreated = true;

    return $session;
  }
  }

  // ######################### UPDATE LAST VISIT/ACTIVITY ########################

  function UpdateLastActivity($userid)
  {
	  global $DB, $cookietimeout, $tableprefix;

	  $lastactivity = $DB->query_first('SELECT lastactivity FROM ' . $tableprefix . 'user WHERE userid = %d', $userid);

	  if (TIMENOW - $lastactivity[0] > $cookietimeout)
	  {
		  $DB->query('UPDATE ' . $tableprefix . 'user
					  SET lastvisit = lastactivity, lastactivity = %d
            WHERE userid = %d', TIMENOW, $userid);
	  }
	  else
	  {
		  $DB->query('UPDATE ' . $tableprefix . 'user
					  SET lastactivity = %d WHERE userid = %d', TIMENOW, $userid);
	  }
  }
}

// ############################# FIND SESSION HASH #############################

if(!empty($_POST['s']))
{
  $sessionhash = $_POST['s'];
}
else if(!empty($_GET['s']))
{
  $sessionhash = $_GET['s'];
}
else
{
  $sessionhash = isset($_COOKIE[$cookieprefix . 'sessionhash']) ? $_COOKIE[$cookieprefix . 'sessionhash'] : (isset($_COOKIE['sessionhash']) ? $_COOKIE['sessionhash'] : '');
}


// ############################# CONTINUE SESSION ##############################

if(!empty($sessionhash))
{
  $session = $DB->query_first('SELECT * FROM ' . $tableprefix . 'session
    WHERE sessionhash = \'%s\' AND lastactivity > %d
    AND host = \'%s\' AND idhash = \'%s\'',
    $DB->escape_string(trim($sessionhash)), (int)(TIMENOW - $cookietimeout),
    $DB->escape_string(SESSION_HOST), SESSION_IDHASH);
}


// ############################### COOKIE LOGIN ################################
// session has expired or does not exist, but the user might still have a userid and password cookies set:

if(empty($session) || ($session['userid'] == 0))
{
  if(!empty($_COOKIE[$cookieprefix . 'userid']) AND
     isset($_COOKIE[$cookieprefix . 'password']) AND
     is_numeric((string)$_COOKIE[$cookieprefix . 'userid']))
  {
    $eraseusercookie = false;

    if($user = $DB->query_first('SELECT * FROM ' . $tableprefix . 'user WHERE userid = %d LIMIT 1', $_COOKIE[$cookieprefix . 'userid']))
    {
      //SD322: use "salt" here
      if(md5(md5($user['password']) . $user['salt']) == $_COOKIE[$cookieprefix . 'password'])
      {
        // combination is valid,
        // delete the old session hash and create a new one
        if(isset($session['sessionhash']) && strlen($session['sessionhash']))
        {
          // old session still exists; kill it
          $DB->query('DELETE FROM ' . $tableprefix . 'session WHERE sessionhash = \'%s\'',
            $DB->escape_string($session['sessionhash']));
        }

        $session = CreateSession($user['userid']);
      }
      else
      {
        $eraseusercookie = true;
      }
    }
    else
    {
      $eraseusercookie = true;
    }

    if($eraseusercookie)
    {
      // cookie has false information *or maybe the user was deleted*, delete the cookies:
      CreateCookie('userid', '', 1);
      CreateCookie('password', '', 1);
    }
  }
}


// ########################### CREATE GUEST SESSION ############################

if(empty($session))
{
  // still no session. the user is a guest, so try to find this guest's session
  $session = $DB->query_first('SELECT * FROM ' . $tableprefix . 'session
    WHERE userid = 0 AND host = \'%s\' AND idhash = \'%s\' LIMIT 1',
    $DB->escape_string(SESSION_HOST), SESSION_IDHASH);

  // still no session found, create a new one for the guest:
  if(empty($session))
  {
    $session = CreateSession(0);
  }
}


// ############################ SETUP USER VARIABLE ############################

if(empty($session['userid']))
{
  // fill in guest userinfo for subdreamer
  $user = array('userid'         => 0,
                'usergroupid'    => $VB_GUEST_GID, // vBulletin 3 - Unregistered / Not Logged In
                'usergroupids'   => array($VB_GUEST_GID),
                'username'       => '',
                'displayname'    => '',
                'loggedin'       => 0,
                'email'          => '',
                'timezoneoffset' => 0,
                'dstonoff'       => 0,
                'dstauto'        => 1);
}
else if($session['userid'] > 0)
{
  $getuser = $DB->query_first('SELECT * FROM ' . $tableprefix . 'user WHERE userid = %d', $session['userid']);

  // fill in member userinfo for subdreamer
  $user = array('userid'         => $getuser['userid'],
                'usergroupid'    => $getuser['usergroupid'],
                'usergroupids'   => array($getuser['usergroupid']),
                'username'       => $getuser['username'],
                'displayname'    => $getuser['username'],
                'loggedin'       => 1,
                'email'          => $getuser['email'],
                'timezoneoffset' => $getuser['timezoneoffset']);

  // bit values: 'dstauto' => 64, 'dstonoff' => 128,
  $user['dstonoff'] = (128 & $getuser['options']) ? 1 : 0;
  $user['dstauto']  = (64  & $getuser['options']) ? 1 : 0;

  // 2.4.4: take into account secondary usergroups:
  if(!empty($getuser['membergroupids']))
  {
    $user['usergroupids'] = array_unique(array_merge($user['usergroupids'],explode(',',$getuser['membergroupids'])));
  }

  // If user is member of "Banned Users" or "Unregistered", than do not
  // use any other usergroup:
  if(isset($VB_BANNED_GID) && (false !== array_search($VB_BANNED_GID,$user['usergroupids']))) // Banned
  {
    $user['usergroupid']  = $VB_BANNED_GID;
    $user['usergroupids'] = array($VB_BANNED_GID);
    $user['loggedin']     = 0;
    $user['banned']       = 1;
    $login_errors_arr[]  = $sdlanguage['you_are_banned'];
  }
  else if(isset($VB_GUEST_GID) && (false !== array_search($VB_GUEST_GID,$user['usergroupids']))) // Guests
  {
    $user['usergroupid']  = $VB_GUEST_GID;
    $user['usergroupids'] = array($VB_GUEST_GID);
    $user['loggedin']     = 0;
  }

  UpdateLastActivity($user['userid']);

  unset($getuser);
}


// ############################## UPDATE SESSION ###############################

if(!$sessioncreated && isset($session['sessionhash']))
{
  $DB->query('UPDATE ' . $tableprefix . "session
    SET useragent = '%s', lastactivity = %d WHERE sessionhash = '%s'",
    $DB->escape_string(USER_AGENT), TIMENOW, $DB->escape_string($session['sessionhash']));
}


// ################################### LOGIN ###################################

if(empty($login_errors_arr) && isset($_POST['login']) && ($_POST['login'] == 'login'))
{
  // post data already cleaned
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

  if(isset($loginusername{0}))
  {
    // get userid for given username
    if($getuser = $DB->query_first('SELECT * FROM ' . $tableprefix . "user WHERE username = '%s'", $loginusername))
    {
      if($getuser['password'] != md5(md5($loginpassword) . $getuser['salt']) )
      {
        $login_errors_arr[] = $sdlanguage['wrong_password'];
      }
      else
      {
        // fill in member userinfo for subdreamer
        $user = array('userid'         => $getuser['userid'],
                      'usergroupid'    => $getuser['usergroupid'],
                      'usergroupids'   => array($getuser['usergroupid']),
                      'username'       => $getuser['username'],
                      'displayname'    => $getuser['username'],
                      'loggedin'       => 1,
                      'email'          => $getuser['email'],
                      'timezoneoffset' => $getuser['timezoneoffset']);

        // bit values: 'dstauto' => 64, 'dstonoff' => 128
        $user['dstonoff'] = (128 & $getuser['options']) ? 1 : 0;
        $user['dstauto']  = (64  & $getuser['options']) ? 1 : 0;

        // 2.4.4: take into account secondary usergroups:
        if(!empty($getuser['membergroupids']))
        {
          $user['usergroupids'] = array_unique(array_merge($user['usergroupids'],explode(',',$getuser['membergroupids'])));
        }
        // If user is member of "Banned Users" or "Unregistered", than do not
        // use any other usergroup:
        if(isset($VB_BANNED_GID) && (false !== array_search($VB_BANNED_GID,$user['usergroupids']))) // Banned
        {
          $user['usergroupid']  = $VB_BANNED_GID;
          $user['usergroupids'] = array($VB_BANNED_GID);
          $user['loggedin']     = 0;
          $user['banned']       = 1;
          $login_errors_arr[]   = $sdlanguage['you_are_banned'];
        }
        else if(isset($VB_GUEST_GID) && (false !== array_search($VB_GUEST_GID,$user['usergroupids']))) // Guests
        {
          $user['usergroupids'] = array($VB_GUEST_GID);
        }

        if(empty($login_errors_arr))
        {
          // a sessionhash was created before user logged in, so delete this sessionhash and create a new one
          $DB->query('DELETE FROM ' . $tableprefix . 'session WHERE sessionhash = \'%s\'', $session['sessionhash']);

          // insert new session
          $session['sessionhash'] = CreateSessionHash();

          $DB->query("INSERT INTO " . $tableprefix . "session (sessionhash, userid, host, idhash, lastactivity, loggedin, useragent)
                      VALUES ('%s', %d, '%s', '%s', %d, 1, '%s') ",
                      $session['sessionhash'], (int)$getuser['userid'],
                      $DB->escape_string(SESSION_HOST), SESSION_IDHASH, TIMENOW, $DB->escape_string(USER_AGENT));

          // save the sessionhash in the cookie
          CreateCookie('sessionhash', $session['sessionhash'], 1);

          // save the userid and password if the user has selected the 'remember me' option
          if(!empty($_POST['rememberme']))
          {
            CreateCookie('userid', $getuser['userid'], 1);
            CreateCookie('password', md5(md5($getuser['password']) . $getuser['salt']), 1);
          }
        }
      }
    }
    else
    {
      $login_errors_arr[] = $sdlanguage['wrong_username'];
    }
  }
  else
  {
    $login_errors_arr[] = $sdlanguage['please_enter_username'];
  }
}


// ################################## LOGOUT ###################################

if(isset($_GET['logout']))
{
  // clear all cookies beginning with COOKIE_PREFIX
  $prefix_length = strlen($cookieprefix);
  if(isset($_COOKIE))
  foreach($_COOKIE AS $key => $val)
  {
    $index = @strpos($key, $cookieprefix);

    if($index == 0 AND $index !== false)
    {
      $key = substr($key, $prefix_length);

      if(trim($key) == '')
      {
        continue;
      }

      CreateCookie($key, '', 1);
    }
  }

  if($user['userid'] > 0)
  {
    // delete all sessions that match the userid
    $DB->query('DELETE FROM ' . $tableprefix . 'session WHERE userid = %d', $user['userid']);
  }

  // delete all sessions that match the sessionhash
  $DB->query('DELETE FROM ' . $tableprefix . 'session WHERE sessionhash = \'%s\'', $session['sessionhash']);

  $session['sessionhash'] = CreateSessionHash();

  $DB->query('INSERT INTO ' . $tableprefix . "session (sessionhash, userid, host, idhash, lastactivity, styleid, useragent)
              VALUES ('%s', 0, '%s', '%s', %d, 0, '%s')",
              (isset($session['sessionhash']) ? $session['sessionhash'] : ''),
              (isset($session['host']) ? $session['host'] : ''),
              (isset($session['idhash']) ? $session['idhash'] : ''),
              TIMENOW, $DB->escape_string(USER_AGENT));

  CreateCookie('sessionhash', $session['sessionhash'], 0);

  $user = array('userid'         => 0,
                'usergroupid'    => $VB_GUEST_GID, // vBulletin 3 - Unregistered / Not Logged In
                'usergroupids'   => array($VB_GUEST_GID),
                'username'       => '',
                'displayname'    => '',
                'loggedin'       => 0,
                'email'          => '',
                'timezoneoffset' => 0,
                'dstonoff'       => 0,
                'dstauto'        => 1);
} //"Logout"


// ############################ ADD SESSION TO URL? ############################
// write the session id/hash if a LOGGED IN USER does not have cookies in the url,

if(isset($_COOKIE) OR (isset($_SERVER['HTTP_USER_AGENT']) && preg_match("#(google|msnbot|yahoo! slurp)#si", $_SERVER['HTTP_USER_AGENT'])) OR (isset($user['userid']) && $user['userid'] == 0))
{
  $user['sessionurl'] = '';
}
else if (strlen($session['sessionhash']) > 0)
{
  $user['sessionurl'] = 's=' . $session['sessionhash'];
}


// ############################ DELETE OLD SESSIONS ############################

$DB->query('DELETE FROM ' . $tableprefix . 'session WHERE lastactivity < %d', (int)(TIMENOW - $cookietimeout));


// ###################### SUBDREAMER USER SETTINGS SETUP #######################

$usersettings = array('userid'         => $user['userid'],
                      'usergroupid'    => $user['usergroupid'],
                      'usergroupids'   => $user['usergroupids'],
                      'username'       => $user['username'],
                      'displayname'    => $user['displayname'],
                      'loggedin'       => $user['loggedin'],
                      'email'          => $user['email'],
                      'timezoneoffset' => $user['timezoneoffset'],
                      'dstonoff'       => $user['dstonoff'],
                      'dstauto'        => $user['dstauto'],
                      'sessionurl'     => $user['sessionurl']);


// ############################## FASHERMAN CODE ###############################
// this code is submitted by fred (fasherman) and will help with the development of vbulletin 3 plugins

if(!empty($usersettings['userid']) && ($usersettings['userid'] > 0))
{
  $vb3userinfo = $DB->query_first('SELECT * FROM ' . $tableprefix . 'user WHERE userid = %d', $usersettings['userid']);

  if(isset($_COOKIE['bbstyleid']))
  {
    $vb3userinfo['styleid'] = $_COOKIE['bbstyleid'];
  }

  //vb38: see function fetch_userinfo in "vb/includes/functions.php"
  //SD343: fill $usersettings with values, not $user
  $usersettings['securitytoken_raw'] = sha1($vb3userinfo['userid'] . sha1($vb3userinfo['salt']) . sha1(COOKIE_SALT));
  $usersettings['securitytoken'] = TIMENOW . '-' . sha1(TIMENOW . $usersettings['securitytoken_raw']);
  $usersettings['salt'] = $vb3userinfo['salt'];

  $vb3userinfo['logouthash'] = $usersettings['securitytoken'];// changed in 3.8
}

// ############################## UNSET VARIABLES ##############################

unset($user, $session, $sessionhash);
