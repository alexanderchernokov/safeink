<?php

if(!defined('IN_PRGM')) return;

$tableprefix  = $usersystem['tblprefix'];
$cookietime   = $usersystem['cookietimeout'];
$cookieprefix = $usersystem['cookieprefix'];
$cookiedomain = isset($usersystem['cookiedomain']) ? $usersystem['cookiedomain'] : '';
$cookiepath   = isset($usersystem['cookiepath'])   ? $usersystem['cookiepath'] : "/";

// set defaults
$sessioncreated = false;

// ################################## DEFINES ##################################

defined('TIME_NOW') || define('TIMENOW', time());

define('USER_AGENT', substr(ipb_clean($_SERVER['HTTP_USER_AGENT']), 0, 200));
define('IPADDRESS',  $DB->escape_string(substr($_SERVER['REMOTE_ADDR'], 0, 50)));

// ########################### SPECIAL INCLUDE CHECK ###########################
if(!defined('SD_IPB_INCLUDED'))
{
  define('SD_IPB_INCLUDED', true);

  // ############################## USER FUNCTIONS #############################

  function IsIPBanned($clientip)
  {
    global $DB, $usersystem, $dbname;

    $query = 'SELECT ban_content FROM ' . $usersystem['tblprefix'] . 'banfilters WHERE ban_type = \'ip\'';

    if($DB->database != $usersystem['dbname']) $DB->select_db($usersystem['dbname']);
    $getbanip = $DB->query($query);

    $result = false;
    while($banip = $DB->fetch_array($getbanip))
    {
      $banip[0] = str_replace( '\*', '.*', preg_quote($banip[0], "/") );

      if ( preg_match( "/^" . $banip[0] . "$/", $clientip ) )
      {
        $result = false;
        break;
      }
    }
    if($DB->database != $dbname) $DB->select_db($dbname);

    return $result;
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

    $url = '';
    if(!empty($userid) && ($userid>0))
    switch($linkType)
    {
      case 1:
        $url = 'index.php?act=Reg&amp;CODE=00';
        break;
      case 2:
        $url = 'index.php?act=UserCP&amp;CODE=00';
        break;
      case 3:
        $url = 'index.php?act=Reg&amp;CODE=10';
        break;
      case 4:
        $url = 'index.php?showuser=' . (int)$userid;
        break;
      case 5:
        $url = 'index.php?act=Msg&amp;CODE=4&amp;MID=' . (int)$userid;
        break;
      default:
        return '';
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

    if($userid > 0)
    {
      $useravatar = $DB->query_first('SELECT avatar_location, avatar_size, avatar_type
        FROM ' . $tableprefix . 'member_extra WHERE id = %d', $userid);
    }
    else
    {
      $useravatar = $DB->query_first('SELECT x.avatar_location, x.avatar_size, x.avatar_type
        FROM ' . $tableprefix . 'member_extra x
          INNER JOIN ' . $tableprefix . 'members m ON m.id = x.id WHERE m.name = \'%s\'',
        $DB->escape_string($username));
    }

    if($DB->database != $dbname) $DB->select_db($dbname);

    if($useravatar['avatar_type'] == 'local' AND strlen($useravatar['avatar_location']))
    {
      $avatar = '<img alt="" border="0" src="' . $sdurl . $forumpath . 'style_avatars/' . $useravatar['avatar_location'] . '" />';
    }
    else if($useravatar['avatar_type'] == 'url')
    {
      $avatar = '<img alt="" border="0" src="' . $useravatar['avatar_location'] . '" />';
    }
    else if($useravatar['avatar_type'] == 'upload')
    {
      $avatar = '<img alt="" border="0" src="' . $sdurl . $forumpath . 'uploads/' . $useravatar['avatar_location'] . '" />';
    }

    return $avatar;

  } //ForumAvatar

  // ############################## SET COOKIE #################################

  function sdsetcookie($name, $value = '', $permanent = 1)
  {
    global $DB, $tableprefix, $usersystem, $_SERVER, $cookieprefix, $cookiepath, $cookiedomain;

    // cookie path should always be /
    $expire = $permanent ? (TIMENOW + 31536000) : 0;
    $secure = (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] == '443')) ? 1 : 0; // secure(1) = using SSL

    static $sensitive_cookies = array( 'ipb_stronghold', 'session_id', 'ipb_admin_session_id', 'member_id', 'pass_hash' );
    if(in_array($name, $sensitive_cookies))
    {
      $name = $cookieprefix . $name;
      if(PHP_VERSION < 5.2)
      {
        if($cookiedomain)
        {
          @setcookie($name, $value, $expire, $cookiepath, $cookiedomain . '; HttpOnly');
        }
        else
        {
          @setcookie($name, $value, $expire, $cookiepath);
        }
      }
      else
      {
        @setcookie($name, $value, $expire, $cookiepath, $cookiedomain, NULL, TRUE);
      }
    }
    else
    {
      $name = $cookieprefix . $name;
      @setcookie($name, $value, $expire, $cookiepath, $cookiedomain);
    }
    //setcookie($name, $value, $expire, $cookiepath, $cookiedomain, $secure);

  } //sdsetcookie


  // ########################## CREATE SESSION HASH ############################

  function CreateSessionHash()
  {
    return md5(uniqid(microtime()));
  }


  // ############################ CREATE SESSION ###############################

  if(!function_exists('CreateSession')) // may be defined in admin
  {
  function CreateSession($userid = 0, $usergroupid = 2, $username = '', $logintype = 0)  // usergroupid 2 = IPB2 Guest
  {
    global $DB, $sessioncreated, $tableprefix;

    // setup the session
    $session = array('id'           => CreateSessionHash(),
                     'member_id'    => intval($userid),
                     'ip_address'   => IPADDRESS,
                     'browser'      => USER_AGENT,
                     'running_time' => TIMENOW);

    // return if we are logging in our logging out (since logging in and out already creates sessions)
    if(isset($_POST['login']) || isset($_GET['logout']))
    {
      return '';
    }

    $DB->query('INSERT INTO ' . $tableprefix . "sessions (id, member_name, member_id, ip_address, browser, running_time, login_type, member_group)
      VALUES ('%s', '%s', %d, '%s', '%s', '%s', '%s', %d)",
      $session['id'], $DB->escape_string($username), $DB->escape_string($session['member_id']),
      $session['ip_address'], $DB->escape_string($session['browser']),
      $session['running_time'], $logintype, $usergroupid);

    // save the sessionhash
    sdsetcookie('session_id', $session['id'], 0);

    // set sessioncreated to true so that we don't update this session later on in the script
    // (because it was just created)
    $sessioncreated = true;

    return $session;
  }
  }

  function ipb_clean($val)
  {
      $val = str_replace( "&#032;"       , " "             , $val );
      $val = str_replace( chr(0xCA)      , ""              , $val );  //Remove sneaky spaces
      $val = str_replace( "<!--"         , "&#60;&#33;--"  , $val );
      $val = str_replace( "-->"          , "--&#62;"       , $val );
      $val = preg_replace( "/<script/i"  , "&#60;script"   , $val );
      $val = str_replace( ">"            , "&gt;"          , $val );
      $val = str_replace( "<"            , "&lt;"          , $val );
      $val = str_replace( "\""           , "&quot;"        , $val );
      $val = preg_replace( "/\n/"        , "<br />"        , $val ); // Convert literal newlines
      $val = preg_replace( "/\\\$/"      , "&#036;"        , $val );
      $val = preg_replace( "/\r/"        , ""              , $val ); // Remove literal carriage returns
      $val = str_replace( "!"            , "&#33;"         , $val );

      return $val;
  }

} // special include check end

// ############################# FIND SESSION HASH #############################

if(!empty($_POST['s']))
{
  $sessionid = $_POST['s'];
}
else if (!empty($_GET['s']))
{
  $sessionid = $_GET['s'];
}
else
{
  $sessionid = isset($_COOKIE[$cookieprefix . 'session_id']) ? $_COOKIE[$cookieprefix . 'session_id'] : (isset($_COOKIE['session_id'])?$_COOKIE['session_id']:'');
}


// ############################# CONTINUE SESSION ##############################

if(!empty($sessionid))
{
  $session = $DB->query_first('SELECT * FROM ' . $tableprefix . "sessions
    WHERE id = '%s'
    AND running_time > %d
    AND browser = '%s'",
    trim($sessionid), (TIMENOW - $cookietime), $DB->escape_string(USER_AGENT));
}


// ############################### COOKIE LOGIN ################################

if(empty($session) || ($session['member_id'] == 0))
{
  if(!empty($_COOKIE[$cookieprefix . 'member_id']) AND
     !empty($_COOKIE[$cookieprefix . 'pass_hash']) AND
     is_numeric($_COOKIE[$cookieprefix . 'member_id']))
  {
    $mid = intval($_COOKIE[$cookieprefix . 'member_id']);
    $pid = substr($_COOKIE[$cookieprefix . 'pass_hash'],0,32);
    if($user = $DB->query_first('SELECT * FROM ' . $tableprefix .
      "members WHERE id = %d AND member_login_key = '%s'",
      $mid, $pid))
    {
      // combination is valid
      if(!empty($session['id']))
      {
        $session['id'] = preg_replace("/([^a-zA-Z0-9])/", "", $session['id']);
        // old session still exists; kill it
        $DB->query('DELETE FROM ' . $tableprefix . 'sessions WHERE id = \'%s\'', $session['id']);
      }

      $session = CreateSession($user['id'], $user['mgroup'], (strlen($user['members_display_name'])?$user['members_display_name']:$user['name']));
    }
    else if(isset($_POST['login'])) // cookie is bad!
    {
      // cookie's bad and since we're not doing anything login related, kill the bad cookie
      sdsetcookie('session_id', '-1', 1);
      sdsetcookie('member_id',  '-1', 1);
      sdsetcookie('pass_hash',  '-1', 1);
      sdsetcookie('topicsread', '-1', 1);
      sdsetcookie('anonlogin',  '-1', 1);
      sdsetcookie('forum_read', '-1', 1);
    }
  }
}


// ########################### CREATE GUEST SESSION ############################

if(empty($session))
{
  // create a guest session
  $session = CreateSession();
}

// Fetch usergroup ids for Banned and Guests from IPB config (set default if
// neccessary):
$INFO = array();
@include($rootpath . $usersystem['folderpath'] . 'conf_global.php');
if(empty($INFO['banned_group']))
{
  $INFO['banned_group'] = 5;
}
if(empty($INFO['guest_group']))
{
  $INFO['guest_group'] = 2;
}

// ############################ SETUP USER VARIABLE ############################

if(empty($session['member_id']))
{
  $user = array('userid'         => 0,
                'usergroupid'    => $INFO['guest_group'],  // IPB2 - Guest
                'usergroupids'   => array($INFO['guest_group']),  // IPB2 - Guest
                'username'       => '',
                'displayname'    => '',
                'loggedin'       => 0,
                'email'          => '',
                'timezoneoffset' => 0,
                'dateformat'     => '',
                'dstonoff'       => 0,
                'dstauto'        => 1);
}
else
{
  $getuser = $DB->query_first('SELECT * FROM ' . $tableprefix . 'members WHERE id = %d', $session['member_id']);

  // fill in member userinfo for subdreamer
  $user = array('userid'         => $getuser['id'],
                'usergroupids'   => array($getuser['mgroup']),
                'username'       => $getuser['name'],
                'displayname'    => $getuser['members_display_name'],
                'loggedin'       => 1,
                'email'          => $getuser['email'],
                'timezoneoffset' => (isset($getuser['time_offset'])?$getuser['time_offset']:0),
                'dstonoff'       => $getuser['dst_in_use'],
                'dstauto'        => (isset($getuser['members_dst_auto'])?$getuser['members_dst_auto']:0));

  // 2.4.4: take into account secondary usergroups:
  if(!empty($getuser['mgroup_others']))
  {
    $user['usergroupids'] = array_unique(array_merge($user['usergroupids'],explode(',',$getuser['mgroup_others'])));
  }

  // If user is member of "Banned Users" or "Unregistered", than do not
  // use any other usergroup:
  if(false !== array_search($INFO['banned_group'],$user['usergroupids'])) // Banned
  {
    $user['usergroupids'] = array($INFO['banned_group']);
    $user['loggedin']     = 0;
    $login_errors_arr     = $sdlanguage['you_are_banned'];
  }
  else if(false !== array_search($INFO['guest_group'],$user['usergroupids'])) // Guests
  {
    $user['usergroupids'] = array($INFO['guest_group']);
  }

  // update last activity
  if((TIMENOW - $getuser['last_activity']) > $cookietime)
  {
    // see if session has 'expired'
    $DB->query('UPDATE ' . $tableprefix . 'members
      SET last_visit = last_activity, last_activity = %d WHERE id = %d',
      TIMENOW, $user['userid']);
  }
  else
  {
    $DB->query('UPDATE ' . $tableprefix . 'members SET last_activity = %d WHERE id = %d',
      TIMENOW, $user['userid']);
  }
}


// ############################## UPDATE SESSION ###############################

if(!$sessioncreated && isset($session['id']))
{
  $DB->query('UPDATE ' . $tableprefix . "sessions SET browser = '%s', running_time = %d WHERE id = '%s'",
             $DB->escape_string(USER_AGENT), TIMENOW, $session['id']);
}


// ################################### LOGIN ###################################

if(empty($login_errors_arr) && isset($_POST['login']) && ($_POST['login'] == 'login'))
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
      $_POST['loginusername'] = $convobj->Convert($loginusername);
      $_POST['loginpassword'] = $convobj->Convert($loginpassword);
    }
  }

  // we run through ipb_clean because there are a few more characters that need
  // to be converted to their entities that are not processed by htmlspecialchars()
  $loginusername = ipb_clean($loginusername);
  $loginpassword = ipb_clean($loginpassword);

  if(isset($loginusername{0}))
  {
    // get userid for given username
    if($getuser = $DB->query_first('SELECT * FROM ' . $tableprefix . 'members WHERE name = \'%s\'', $loginusername))
    {
      if($converge = $DB->query_first('SELECT converge_pass_hash, converge_pass_salt
        FROM ' . $tableprefix . 'members_converge
        WHERE converge_id = %d AND converge_email = \'%s\'',
        $getuser['id'], $DB->escape_string($getuser['email'])))
      {
        $md5password       = md5($loginpassword);
        $salt              = $converge['converge_pass_salt'];
        $encryptedpassword = md5(md5($salt) . $md5password);

        if($converge['converge_pass_hash'] != $encryptedpassword)
        {
          $login_errors_arr = $sdlanguage['wrong_password'];
        }
        else
        {
          // logged in
          $user = array('userid'         => $getuser['id'],
                        'usergroupid'    => $getuser['mgroup'],
                        'usergroupids'   => array($getuser['mgroup']),
                        'username'       => $getuser['name'],
                        'displayname'    => $getuser['members_display_name'],
                        'loggedin'       => 1,
                        'email'          => $getuser['email'],
                        'timezoneoffset' => (isset($getuser['time_offset'])?$getuser['time_offset']:0),
                        'dstonoff'       => $getuser['dst_in_use'],
                        'dstauto'        => (isset($getuser['members_dst_auto'])?$getuser['members_dst_auto']:0));

          // Take into account secondary usergroups:
          if(!empty($getuser['mgroup_others']))
          {
            $user['usergroupids'] = array_unique(array_merge($user['usergroupids'],@preg_split('#,#', $getuser['mgroup_others'], -1, PREG_SPLIT_NO_EMPTY)));
          }
          // If user is member of "Banned Users" or "Unregistered", than do not
          // allow any other usergroup:
          if(false !== array_search($INFO['banned_group'],$user['usergroupids'])) // Banned
          {
            $user['usergroupids'] = array($INFO['banned_group']);
            $user['loggedin']     = 0;
            $login_errors_arr     = $sdlanguage['you_are_banned'];
          }
          else if(false !== array_search($INFO['guest_group'],$user['usergroupids'])) // Guests
          {
            $user['usergroupids'] = array($INFO['guest_group']);
          }

          // erase old session
          $DB->query('DELETE FROM ' . $tableprefix . 'sessions WHERE id = \'%s\'',
                     $DB->escape_string($session['id']));

          if(empty($login_errors_arr))
          {
            // insert new session
            $session['id'] = CreateSessionHash();

            // insert the session into the database
            $DB->query('INSERT INTO ' . $tableprefix . "sessions (id, member_name, member_id, ip_address, browser, running_time, login_type, member_group)
              VALUES ('%s', '%s', %d, '%s', '%s', %d, 0, %d)",
              $DB->escape_string($session['id']), $DB->escape_string($user['username']),
              $user['userid'], IPADDRESS,
              $DB->escape_string(USER_AGENT),
              TIMENOW, $getuser['mgroup']);

            // save the sessionhash in the cookie
            sdsetcookie('session_id', $session['id'], 0);

            // save the userid and password if the user has selected the 'remember me' option
            if(!empty($_POST['rememberme']))
            {
              sdsetcookie('member_id', $user['userid'], 1);
              sdsetcookie('pass_hash', $getuser['member_login_key'], 1);
            }
          }
        }
      }
      else
      {
        $login_errors_arr = $sdlanguage['wrong_username'];
      }
    }
    else
    {
      $login_errors_arr = $sdlanguage['wrong_username'];
    }
  }
  else
  {
    $login_errors_arr = $sdlanguage['please_enter_username'];
  }

  unset($getuser, $md5password, $salt, $converge);

} // login

unset($INFO); // clear previously loaded IPB config now


// ################################## LOGOUT ###################################

if(isset($_GET['logout']))
{
  if(isset($user['userid']) && ((int)$user['userid'] > 0))
  {
    $DB->query('UPDATE ' . $tableprefix . 'members
      SET last_activity = %d, last_visit = %d WHERE id = %d',
      (TIMENOW - $cookietime), TIMENOW, $user['userid']);

    // make sure any other of this user's sessions are deleted (in case they ended up with more than one)
    $DB->query('DELETE FROM ' . $tableprefix . 'sessions WHERE member_id = %d', $user['userid']);
  }

  if(isset($session['id']))
  {
    $DB->query('DELETE FROM ' . $tableprefix . 'sessions WHERE id = \'%s\'', $DB->escape_string($session['id']));
  }

  $session['id'] = CreateSessionHash();

  $DB->query('INSERT INTO ' . $tableprefix . "sessions (id, member_name, member_id, ip_address, browser, running_time, member_group)
              VALUES ('%s', '', 0, '%s', '%s', %d, 2)",
              $DB->escape_string($session['id']), IPADDRESS,
              $DB->escape_string(USER_AGENT), TIMENOW);

  sdsetcookie('pass_hash', '', 0);
  sdsetcookie('member_id', '', 0);
  sdsetcookie('session_id', $session['id'], 0);

  $user = array('userid'         => 0,
                'usergroupid'    => $INFO['guest_group'],  // IPB2 - Guest
                'usergroupids'   => array($INFO['guest_group']),  // IPB2 - Guest
                'username'       => '',
                'displayname'    => '',
                'loggedin'       => 0,
                'email'          => '',
                'timezoneoffset' => 0,
                'dstonoff'       => 0,
                'dstauto'        => 1);
} //"Logout"


// ############################ ADD SESSION TO URL? ############################

if(sizeof($_COOKIE) > 0 OR (isset($_SERVER['HTTP_USER_AGENT']{0}) && preg_match("#(google|msnbot|yahoo! slurp)#si", $_SERVER['HTTP_USER_AGENT'])))
{
  $user['sessionurl'] = '';
}
else if(isset($session['id']{15}))
{
  $user['sessionurl'] = 's=' . (string)$session['id'];
}


// ############################ DELETE OLD SESSIONS ############################

$DB->query('DELETE FROM ' . $tableprefix . 'sessions WHERE running_time < %d', (int)(TIMENOW - $cookietime));


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


// ############################## UNSET VARIABLES ##############################

unset($user, $session, $sessionid);
