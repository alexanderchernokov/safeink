<?php
if(!defined('IN_PRGM')) exit();

// ############################################################################
// INITIALIZE USERINFO
// ############################################################################
$userinfo = array(); // assure to have it clean
$userinfo = array('userid'=> 0,'banned'=>0); // start as guest. do not remove!

// ############################################################################
// COOKIE_PREFIX, COOKIE_TIMEOUT, COOKIE_ADMIN_TIMEOUT
// ############################################################################

//SD313: check for admincookietimeout main setting
if(isset($mainsettings_admincookietimeout))
{
  $timeout = (int)$mainsettings_admincookietimeout;
  // Allow 0 (30 days timout), otherwise lowest value is 5 minutes = 300 seconds:
  $timeout = ($timeout==0 ? 30*24*3600 : ($timeout < 300 ? 300 : $timeout));
  define('COOKIE_ADMIN_TIMEOUT', $timeout);
  unset($timeout);
}
defined('COOKIE_ADMIN_TIMEOUT') || define('COOKIE_ADMIN_TIMEOUT', 86400); // 1 day admin timeout

// ############################################################################
// GET USERIP AND USERAGENT
// ############################################################################

unset($userid);
$userip = '';
if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND !empty($_SERVER['HTTP_X_FORWARDED_FOR']))
{
  foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $values)
  {
    $values = preg_replace( '/[^0-9a-fA-F:., ]/', '', $values);
    $userip .= long2ip(ip2long($values)) . ' ; ';
  }
  $userip = rtrim($userip,'; ');
}
else
{
  //SD343: replace for REMOTE_ADDR added; preliminary IPv6 support
  $userip = (!empty($HTTP_SERVER_VARS['REMOTE_ADDR']) ? preg_replace("/^::ffff:/", "", $HTTP_SERVER_VARS['REMOTE_ADDR']) :
            (!empty($HTTP_ENV_VARS['REMOTE_ADDR']) ? $HTTP_ENV_VARS['REMOTE_ADDR'] :
            (isset($_SERVER['REMOTE_ADDR']) ? preg_replace("/^::ffff:/", "", $_SERVER['REMOTE_ADDR']):'unknown')));
  $userip = preg_replace('/[^0-9a-fA-F:., ]/', '', $userip);
  $userip = ($userip == 'unknown') ? $userip : long2ip(ip2long($userip));
}

$userip = addslashes(substr($userip, 0, 40)); //SD343: 40, not 15 for IPv6 support
if(defined('IN_ADMIN'))
{
  // SD313: login issues on IE8 with user agent > 255 characters; fix: added substr()
  define('USER_AGENT', isset($_SERVER['HTTP_USER_AGENT']) ? substr(htmlentities(strip_tags($_SERVER['HTTP_USER_AGENT'])),0,255) : '');
}
unset($rememberme);
define('USERIP', $userip);

// ############################################################################
// SD322: LEAVE NOW IF WE ARE NOT IN ADMIN PANEL!
// ############################################################################
if(!defined('IN_ADMIN') || SD_IS_BOT)
{
  return true;
}


// ############################################################################
// GET SESSION ID FROM COOKIE SAFELY (SD 313)
// ############################################################################

function sd_GetSafeMD5Cookie($name)
{
  $sessionid = isset($_COOKIE[COOKIE_PREFIX . $name]) ? $_COOKIE[COOKIE_PREFIX . $name] : 0;
  if($sessionid)
  {
    // Make sure that sessionid is exactly 32 characters and ONLY contains
    // latin characters and numerals
    if((strlen($sessionid)!=32) || preg_match('/[^a-zA-Z0-9]/', $sessionid))
    {
      return 0;
    }
  }
  return $sessionid;

} //sd_GetSafeMD5Cookie


// ############################################################################
// CREATE COOKIE
// ############################################################################

function sd_CreateCookie($name, $value = '')
{
  // SD 313: if no value is set, cookie must expire immediately.
  // Otherwise it has to expire depending on the cookie timeout value
  // for either admin panel or frontpage.
  $expire = TIME_NOW;
  if(!empty($value))
  {
    $expire += COOKIE_ADMIN_TIMEOUT;
  }
  if(version_compare(PHP_VERSION, '5.2.0', 'ge'))
  {
    @setcookie(COOKIE_PREFIX . $name, $value, $expire, '/', '', false, true);
  }
  else
  {
    @setcookie(COOKIE_PREFIX . $name, $value, $expire, '/', '', false);
  }

} //sd_CreateCookie


// ############################################################################
// DELETE COOKIES
// ############################################################################

function DeleteCookies()
{
  sd_CreateCookie('userid', '');
  sd_CreateCookie('password', '');
  sd_CreateCookie('sessionid', '');
  unset($_COOKIE[COOKIE_PREFIX.'userid']);
  unset($_COOKIE[COOKIE_PREFIX.'password']);
  unset($_COOKIE[COOKIE_PREFIX.'sessionid']);
}


// ############################################################################
// CREATE SESSION ID
// ############################################################################

function sdCreateSessionID($userid=0)
{
  //SD343: allow multiple users per IP/useragent
  return md5(uniqid(USERIP.'-'.microtime(true).'-'.(int)$userid).'-'.USER_AGENT);
}


// ############################################################################
// CREATE SESSION
// ############################################################################
// sessions are only created when a user logs in with remember me unchecked
//SD370: added 2nd parameter to specify predefined session id

function sdCreateSession($userid = 0, $preSessionid='')
{
  global $DB, $mainsettings, $usersystem, $dbname;

  $loggedin = empty($userid) ? 0 : 1;

  $session = array(
    'sessionid'    => (!empty($preSessionid)?$preSessionid:sdCreateSessionID($userid)),
    'userid'       => intval($userid),
    'ipaddress'    => USERIP,
    'useragent'    => USER_AGENT,
    'lastactivity' => TIME_NOW,
    'location'     => (defined('IN_ADMIN') ? 'Admin Panel' : ''),
    'loggedin'     => $loggedin);

  $DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."sessions WHERE (sessionid = '%s') OR (ipaddress = '%s' AND useragent = '%s'
              AND admin = 1 AND lastactivity < %d)",
             $session['sessionid'], $session['ipaddress'], $DB->escape_string(USER_AGENT),
             intval(TIME_NOW - COOKIE_ADMIN_TIMEOUT));
  if(defined('SD_331'))
  {
    $DB->query("REPLACE INTO ".PRGM_TABLE_PREFIX."sessions (sessionid, userid, ipaddress, useragent, lastactivity, admin, location, loggedin)
                VALUES ('%s', %d, '%s', '%s', %d, 1, '%s', %d) ",
                $session['sessionid'], $session['userid'], $session['ipaddress'],
                $DB->escape_string(USER_AGENT), $session['lastactivity'], $session['location'], $loggedin);
  }
  else
  {
    $DB->query("REPLACE INTO ".PRGM_TABLE_PREFIX."sessions (sessionid, userid, ipaddress, useragent, lastactivity, admin)
                VALUES ('%s', %d, '%s', '%s', %d, 1) ",
                $session['sessionid'], $session['userid'], $session['ipaddress'],
                $DB->escape_string(USER_AGENT), $session['lastactivity']);
  }
  sd_CreateCookie('sessionid', '');
  sd_CreateCookie('sessionid', $session['sessionid']);

  return $session;

} //sdCreateSession

//SD322: moved declaration of "unhtmlspecialchars" from functions_global
// here because the same function may already be defined by vB system!
if(!function_exists('unhtmlspecialchars'))
{
  function unhtmlspecialchars($string)
  {
    return sd_unhtmlspecialchars($string);
  }
}


// ############################################################################
// START PROCESSING COOKIES AND SESSION...
// ############################################################################


//SD360: use DB name as cookie suffix to allow multiple admin logins for
// same domain if on different databases (convenient for separate production
// and development/testing installations):
define('COOKIE_PREFIX', 'sd3a'.(empty($dbname)?'':substr(md5($dbname),0,5)));
defined('IPADDRESS') || define('IPADDRESS', USERIP);

// usersystem is fetched in init.php
if(empty($usersystem) || ($usersystem['name'] == 'Subdreamer'))
{
  // make sure that the dbname is in sync for Subdreamer system
  $usersystem['dbname'] = $dbname;
  $usersystem['queryfile'] = 'subdreamer.php';
}

//SD342 switch to SD database first
if(!empty($dbname) && ($DB->database != $dbname)) $DB->select_db($dbname);

unset($session, $sessionid);
if(SD_IS_BOT) return true; //SD343 bail out if bot detected


// ############################################################################
// DELETE OLD SESSIONS
// ############################################################################
// SD 313: since there's a different timeout for admin and frontpage,
// the DELETE has to work on 2 different timeouts, too!
if(COOKIE_ADMIN_TIMEOUT > 60)
{
  $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'sessions'.
             ' WHERE (userid > 0) AND (admin = 1)'.
             ' AND (lastactivity < %d)',
             (TIME_NOW - COOKIE_ADMIN_TIMEOUT));
}

/*
if(!empty($sessionid))
{
  $DB->result_type = MYSQL_ASSOC;
  $session = $DB->query_first('SELECT * FROM '.PRGM_TABLE_PREFIX.'sessions'.
                              " WHERE sessionid  = '%s'".
                              " AND lastactivity > %d AND useragent = '%s'".
                              " AND ipaddress = '%s' AND admin = 0",
                              $DB->escape_string($sessionid),
                              intval(TIME_NOW - COOKIE_ADMIN_TIMEOUT),
                              $DB->escape_string(USER_AGENT), USERIP);

  // will return a NULL session if last activity is expired (needs re-login)
}
*/

// ############################################################################
// POST LOGIN
// ############################################################################

if(!empty($_POST['login']))
{
  // SD313: use new function CleanVar to remove suspicious characters
  // so these do not go into any SQL or build invalid usernames
  $loginusername = GetVar('loginusername', '', 'string');
  $loginpassword = GetVar('loginpassword', '', 'string');
  $rememberme    = GetVar('rememberme', 0, 'bool');
  $login_errors_arr = array();

  // delete old sessions
  $sessionid = sd_GetSafeMD5Cookie('sessionid');
  if(!empty($sessionid))
  {
    $DB->query("DELETE FROM {sessions} WHERE admin = 1 AND sessionid = '%s'", $sessionid);
  }

  // NOTE: ALL POST VARS HAVE GONE THROUGH StripSlashesArray AND PreClean!
  if(strlen($loginusername) && strlen($loginpassword))
  {
    require(ROOT_PATH . ADMIN_PATH . '/login/adminlogin_' . $usersystem['queryfile']);

    if(substr($usersystem['name'],0,20) == 'Invision Power Board')
    {
      // IPB stores usernames and passwords as their entities in the database
      // Fix IPB entity &#039; to &#39;
      $loginusername = str_replace('&#039;', '&#39;', $loginusername);
      $loginpassword = str_replace('&#039;', '&#39;', $loginpassword);
    }
    else
    {
      // vBulletin, SMF, & PHPBB DO NOT store usernames and passwords with entities
      if($usersystem['name'] !== 'Subdreamer')
      {
        $loginusername = sd_unhtmlspecialchars($loginusername);
      }
      $loginpassword = sd_unhtmlspecialchars($loginpassword);

      // HOWEVER, now that they are back to normal (example: Zi'ad), they could break
      // the database and need to be escaped for use with the database
      $loginusername = $DB->escape_string($loginusername);

      // What about the passwords?
      // The passwords are not used in queries, however some forums use the
      // addslashes function on their passwords before encrypting them:
      if($usersystem['name'] == 'phpBB2')
      {
        $loginpassword = $DB->escape_string($loginpassword);
      }
    }

    // Transliterate credentials from forum to SD character set (if needed):
    if($usersystem['name'] != 'Subdreamer')
    {
      $to_charset = !empty($mainsettings_forum_character_set) ? $mainsettings_forum_character_set : SD_CHARSET;
      if(SD_CHARSET && function_exists('sd_GetConverter') &&
         !empty($to_charset) && ($to_charset !== SD_CHARSET))
      {
        // Create new object for forum character set
        $convobj = sd_GetConverter($sd_charset, $to_charset, 1);
        if(defined('SD_CVC') && isset($convobj) && isset($convobj->CharsetTable))
        {
          $loginusername = $convobj->Convert($loginusername);
          $loginpassword = $convobj->Convert($loginpassword);
        }
      }
    }

    if($DB->database != $usersystem['dbname']) $DB->select_db($usersystem['dbname']);

    $usersettings = LoginUser($loginusername, $loginpassword);
    //echo '<p>'.var_dump($usersettings).'</p>';
    $dbname = $database['name'];
    if($DB->database != $dbname) $DB->select_db($dbname);

    if(empty($usersettings) || !is_array($usersettings) ||
       empty($usersettings['userid']) || empty($usersettings['loggedin']))
    {
      if(is_string($usersettings))
      {
        $login_errors_arr[] = $usersettings;
      }
      $error = is_string($usersettings)?$usersettings:$sdlanguage['wrong_username'];
      Watchdog('Login', "Login Error - User: '" . htmlentities($loginusername)."' IP: ".USERIP.' '.
               strip_tags($error), WATCHDOG_WARNING);
      unset($error,$usersettings);
    }
    else
    {
      Watchdog('Login', "<strong>'" . $loginusername . "'</strong> (".
               $usersettings['userid'].'), IP: '.USERIP);
      $session = sdCreateSession($usersettings['userid'],$sessionid);
    }
  }
  else
  {
    $login_errors_arr[] = $sdlanguage['incorrect_login'];
  }
}
// ############################################################################
// SESSION LOGOUT
// ############################################################################
ELSE
if(isset($_GET['logout']) || isset($_POST['logout']))
{
  $sessionid = sd_GetSafeMD5Cookie('sessionid');
  if(!empty($sessionid))
  {
    $DB->query("DELETE FROM {sessions} WHERE admin = 1 AND sessionid = '%s'", $sessionid);
  }

  DeleteCookies();
}
ELSE
{
  // ##########################################################################
  // SESSION LOGIN
  // ##########################################################################

  unset($session, $sessionid);
  /*
  if(!empty($_POST['s']))
  {
    $sessionid = htmlspecialchars(strip_tags(unhtmlspecialchars($_POST['s'])), ENT_QUOTES);
  }
  else if(!empty($_GET['s']))
  {
    $sessionid = htmlspecialchars(strip_tags(unhtmlspecialchars($_GET['s'])), ENT_QUOTES);
  }
  else
  */
  if(empty($sessionid))
  {
    $sessionid = sd_GetSafeMD5Cookie('sessionid');
  }

  // ############################# CHECK IF SESSION ###########################

  if(!empty($sessionid))
  {
    $sessionid = htmlentities(strip_tags(unhtmlspecialchars($sessionid)));
    $sql = 'SELECT * FROM '.PRGM_TABLE_PREFIX."sessions WHERE sessionid = '%s' ".
           // Timeout = 0 means never expire
           (!empty($mainsettings_admincookietimeout) ?
            ('AND lastactivity >= '.(TIME_NOW - COOKIE_ADMIN_TIMEOUT)) : '').
           " AND useragent = '".$DB->escape_string(USER_AGENT)."'".
           " AND ipaddress = '".USERIP."'".
           ' AND admin = 1 ORDER BY lastactivity DESC LIMIT 1';

    $DB->result_type = MYSQL_ASSOC;
    if($session = $DB->query_first($sql, $DB->escape_string($sessionid)))
    {
      $session['sessionid'] = htmlentities(strip_tags(unhtmlspecialchars($session['sessionid'])));
    }
    unset($sql);
  }

  if(isset($session) && !empty($session['sessionid']))
  {
    //SD370: only update once a minute
    if(empty($session['lastactivity']) ||
       ((TIME_NOW - (int)$session['lastactivity']) > 60))
    {
      $DB->query("UPDATE ".PRGM_TABLE_PREFIX."sessions".
                 " SET useragent = '" . $DB->escape_string(USER_AGENT).
                 "', lastactivity = " . TIME_NOW.
                 (defined('SD_331') ? ", location = 'Admin Panel'":'').
                 " WHERE sessionid = '".$DB->escape_string($session['sessionid'])."'");
    }

    //SD322: Reset session-id cookie to prevent timeout
    sd_CreateCookie('sessionid', '');
    sd_CreateCookie('sessionid', $session['sessionid']);

    // switch database?
    if($usersystem['dbname'] != $dbname)
    {
      // Subdreamer is being integrated with a Forum in a different database
      $DB->select_db($usersystem['dbname']);
    }
    require_once(ROOT_PATH . ADMIN_PATH . '/login/adminlogin_' . $usersystem['queryfile']);
    // This will read additional columns from SD's users table and combine these
    // together with the session information into the usersettings variable:
    $usersettings = GetUser(!empty($session['userid'])?$session['userid']:0);

    if(empty($usersettings) && !isset($_GET['dologin']))
    {
      header('Location: '.ROOT_PATH.ADMIN_PATH.'/index.php?dologin');
      exit();
    }
  }
}

// Switch back to SD database
if($DB->database != $database['name']) $DB->select_db($database['name']);

$DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'sessions WHERE admin = 1 AND (lastactivity < %d)', (int)(TIME_NOW - COOKIE_ADMIN_TIMEOUT));

unset($userinfo);
if(!empty($usersettings))
{
  $userinfo = GetUserInfo($usersettings);
}

if(isset($userinfo))
{
  // Update "lastactivity" immediately (SD only)
  //SD370: only update once a minute
  if(!empty($userinfo['userid']) && ($usersystem['dbname'] == $dbname) &&
     (empty($session['lastactivity']) || ((TIME_NOW - (int)$session['lastactivity']) > 60)) )
  {
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'users SET lastactivity = %d WHERE userid = %d', TIME_NOW, $userinfo['userid']);
  }
  $userinfo['lastactivity'] = TIME_NOW;

  if(!empty($userinfo['error']))
  {
    $login_errors_arr[] = '<span style="color: red">'.$sdlanguage['no_view_access'].'</span>';
  }

  // SD313: is user banned?
  if(!empty($userinfo['banned']))
  {
    DeleteCookies();
    // SD313: admin login requires cookies!
    sd_CreateCookie('userid', $userinfo['userid']);
  }
  $userinfo['sessionid'] = $session['sessionid'];
}


// ############################################################################
// COOKIE LOGIN (if user is not logged in yet by session table)
// ############################################################################

if(empty($login_errors_arr) && !GetVar('logout',0,'bool') && empty($userinfo['loggedin']))
{
  // SD 313: safe cookie values handling
  $userid = isset($_COOKIE[COOKIE_PREFIX . 'userid']) ? $_COOKIE[COOKIE_PREFIX . 'userid'] : false;
  $pwd    = sd_GetSafeMD5Cookie('password');

  if($userid && $pwd && preg_match('/[0-9]/', $userid))
  {
    if(($userid > 0) and ($userid < 999999) &&
       ($user_arr = $DB->query_first('SELECT * FROM '.PRGM_TABLE_PREFIX.'users WHERE userid = %d LIMIT 1',$userid)))
    {
      $ok1 = empty($user_arr['use_salt']) && ($user_arr['password'] == $pwd);
      $ok2 = !empty($user_arr['use_salt']) && !empty($user_arr['salt']) &&
             ($user_arr['password'] == $pwd);

      if($ok1 || $ok2)
      {
        $userinfo = GetUserInfo($user_arr);
        // SD313: for admin: reset user cookie with new timeout
        if(defined('IN_ADMIN'))
        {
          sd_CreateCookie('userid', '');
          sd_CreateCookie('userid', $userinfo['userid']);
        }
      }
      else
      {
        // cookie has false information *or maybe the user was deleted*, delete the cookies:
        DeleteCookies();
      }
    }
    else
    {
      // cookie has false information *or maybe the user was deleted*, delete the cookies:
      DeleteCookies();
    }
  }
  unset($user_arr, $userid, $pwd);
}


// ############################################################################
// SET IP ADDRESS
// ############################################################################

$userinfo['ipaddress'] = USERIP;
unset($sessionid, $usersettings, $loginpassword, $loginusername,
      $_POST['loginpassword'], $_POST['loginusername'],
      $_REQUEST['loginusername'], $_REQUEST['loginpassword']);
