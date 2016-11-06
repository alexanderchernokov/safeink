<?php
if(!defined('IN_PRGM')) return;

defined('SD_331') || define('SD_331', version_compare(PRGM_VERSION, '3.3.1', '>='));

if(!isset($categoryid)) global $categoryid; // needed as this file may be included inside a function!

if(!defined('IN_ADMIN'))
  $location = isset($categoryid)?(int)($categoryid):0;
else
  $location = 0;

defined('COOKIE_TIMEOUT') || define('COOKIE_TIMEOUT', 86400 * 7); // 7 days frontpage cookie timeout

// set defaults
unset($session, $sessionid);
$sessioncreated = false;

if(isset($dbname)) $DB->select_db($dbname);
$login_errors_arr = array();

if(defined('SD_SESSION'))
{
  return true;
}
else
{
  define('SD_SESSION', true);

  define('USER_AGENT', isset($_SERVER['HTTP_USER_AGENT']) ? substr(htmlentities(strip_tags($_SERVER['HTTP_USER_AGENT'])),0,255) : '');
  define('COOKIE_PREFIX', 'sd');
  // global array to cache avatar html for ForumAvatar
  $sd_global_avatars = array();

  // ##########################################################################
  // CREATE COOKIE
  // ##########################################################################

  function CreateCookie($name, $value = '', $rememberme=true)
  {
    $value  = !isset($value) ? '' : $value;
    $expire = TIME_NOW;
    //SD370: with "remember me" do 7 days, otherwise only 1 day
    if(strlen($value)) $expire += (!empty($rememberme)?COOKIE_TIMEOUT:86400);
    //SD370: fixed setcookie call depending on PHP version
    $cookiepath = '/';
    if(version_compare(PHP_VERSION, '5.2.0', 'ge'))
      @setcookie(COOKIE_PREFIX . $name, $value, $expire, $cookiepath, null, false, true);
    else
      @setcookie(COOKIE_PREFIX . $name, $value, $expire, $cookiepath, null, false);
  }

  // ############################ CREATE SESSION ID ###########################

  function CreateSessionID($userid=0)
  {
    return md5(uniqid(USERIP.'-'.(int)$userid).'-'.USER_AGENT); //SD343: allow multiple users per IP
  }

  // ############################# CREATE SESSION #############################

  function CreateSession($userid = 0)
  {
    global $DB, $sessioncreated, $location;

    $loggedin = empty($userid) || ($userid < 1) ? 0 : 1;

    $session = array('sessionid'    => CreateSessionID($userid),
                     'userid'       => intval($userid),
                     'ipaddress'    => USERIP,
                     'useragent'    => USER_AGENT,
                     'lastactivity' => TIME_NOW,
                     'location'     => $location,
                     'loggedin'     => $loggedin);

    // login creates its own session
    if(isset($_POST['login'])) return null;

    //SD322: do not create session for bots
    if(SD_IS_BOT) return $session;

    //SD332: update "lastactivity" immediately
    if(!empty($session['userid']))
    {
      $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'users SET lastactivity = %d WHERE activated = 1 AND userid = %d', TIME_NOW, $session['userid']);
      $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX."sessions WHERE (sessionid = '%s') OR (userid = %d AND ipaddress = '%s' AND useragent = '%s' AND admin = 0)",
                 $session['sessionid'], $session['userid'], $session['ipaddress'], $DB->escape_string(USER_AGENT));
    }
    if(defined('SD_331'))
    {
      $DB->query('REPLACE INTO '.PRGM_TABLE_PREFIX."sessions (sessionid, userid, ipaddress, useragent, lastactivity, location, loggedin)
                  VALUES ('%s', %d, '%s', '%s', %d, '%s', %d) ",
                  $session['sessionid'], $session['userid'], $session['ipaddress'],
                  $DB->escape_string(USER_AGENT), $session['lastactivity'],
                  $session['location'], $session['loggedin']);
    }
    else
    {
      $DB->query('REPLACE INTO '.PRGM_TABLE_PREFIX."sessions (sessionid, userid, ipaddress, useragent, lastactivity)
                  VALUES ('%s', %d, '%s', '%s', %d) ",
                  $session['sessionid'], $session['userid'], $session['ipaddress'],
                  $DB->escape_string(USER_AGENT), $session['lastactivity']);
    }

    if(!isset($_POST['logout']) && !isset($_GET['logout']))
    {
      // save the sessionid
      CreateCookie('sessionid', $session['sessionid']);
    }

    $GLOBALS['sessioncreated'] = true;

    return $session;

  } //CreateSession


  // ############################ USER FUNCTIONS ##############################

  function IsIPBanned($clientip, $iplist=null)
  {
    if(empty($clientip)) return false;
    return sd_IsIPBanned($clientip, $iplist); //SD343: moved to functions_global
  } //IsIPBanned


  // Returns the relevent forum link url
  // linkType
  // 1 - Register
  // 2 - UserCP
  // 3 - Recover Password
  // 4 - Private "Profile" page or public "Member" page (requires $userid)
  // 5 - SendPM (requires $userid)
  function ForumLink($linkType, $userid = -1)
  {
    global $DB, $dbname, $sdurl, $mainsettings, $mainsettings_modrewrite,
           $mainsettings_user_profile_page_id, $userinfo;

    if(empty($linkType) || ($linkType<1)) return '#'; //SD342

    $url = '';
    $prevDB = $DB->database;
    if($DB->database != $dbname) $DB->select_db($dbname);

    switch($linkType)
    {
      case 1:
      case 3:
        $DB->result_type = MYSQL_ASSOC;
        if($getregpath = $DB->query_first("SELECT p.categoryid FROM {pagesort} p,
          {categories} c
          WHERE p.categoryid = c.categoryid AND p.pluginid = '12'
          AND (LENGTH(c.link)=0)
          ORDER BY p.categoryid LIMIT 1"))
        {
          if(!empty($getregpath[0]))
          {
            $url = RewriteLink('index.php?categoryid=' . (int)$getregpath[0]);
          }
        }
        $DB->result_type = MYSQL_BOTH;
        break;
      // TODO: for 2, 4 and 5 eventually use "slugs" table (instead of CP_PATH)
      // which should then contain pages for profile and members (SD343+)
      // to allow for http://site/members/1234 instead of http://site/members.html?member=1234
      case 2:
      case 4:
      case 5:
        if(!defined('UCP_BASIC') || !UCP_BASIC)
        {
          if(!defined('CP_PATH') || (CP_PATH==''))
          {
            $url = '#';
            break;
          }
          $sep = ($mainsettings_modrewrite ? '?' : '&amp;');
          if($linkType == 4)
          {
            if(!empty($userinfo['userid']) && ($userinfo['userid']==$userid))
            {
              $sep .= 'profile=';
            }
            else
            {
              $sep .= 'member=';
            }
          }
          else
          {
            $sep .= ($linkType==2 ? 'profile=' : 'do=createnewmessage&amp;profile=');
          }
          $url = CP_PATH . $sep . ($userid?(int)$userid:'');
        }
        break;
    }
    if($DB->database != $prevDB) $DB->select_db($prevDB);

    return $url;
  }

  function ForumAvatar($userid, $username, $useremail='', $override_user_arr=false)
  {
    global $mainsettings, $sd_global_avatars;

    if(!empty($userid) && is_numeric($userid) && ($userid>0) && (!defined('UCP_BASIC') || !UCP_BASIC))
    {
      //SD343: only use globally cached avatar if no override-user is specified
      // because the avatar sizes can have changed between plugins
      if(empty($override_user_arr) && !empty($sd_global_avatars[$userid]))
      {
        return $sd_global_avatars[$userid];
      }

      //SD343: avoid another DB access if override user was specified
      if(empty($override_user_arr))
        $user = sd_GetUserrow($userid, array('avatar_disabled','user_avatar_type','user_avatar',
                                             'user_avatar_width','user_avatar_height','user_avatar_link'));
      else
        $user = $override_user_arr;

      if(!empty($user['avatar_disabled']))
      {
        return '';
      }

      if(!empty($user) && !empty($user['user_avatar_type']))
      {
        if(($user['user_avatar_type']==1) && !empty($user['usergroupid']))
        {
          $usergroup_options = SDProfileConfig::$usergroups_config[$user['usergroupid']];
          $avatar_path = isset($usergroup_options['avatar_path'])?(string)$usergroup_options['avatar_path']:'';
          $avatar_path = (!empty($avatar_path) && is_dir(ROOT_PATH.$avatar_path) && is_writable(ROOT_PATH.$avatar_path)) ? $avatar_path : false;
          if($avatar_path !== false)
          {
            $avatar_path .= (substr($avatar_path,-1)=='/'?'':'/').floor($userid / 1000).'/';
            $avatar_path = is_dir(ROOT_PATH.$avatar_path) ? SITE_URL.$avatar_path : false;
            if($avatar_path !== false)
            {
              $avatar_w = (int)$user['user_avatar_width'];
              $avatar_h = (int)$user['user_avatar_height'];
              $avatar = '<img alt="" class="avatar" width="'.$avatar_w.'" height="'.$avatar_h.'" src="' . $avatar_path . $user['user_avatar'] . '" />';
              $GLOBALS['sd_global_avatars'][$userid] = $avatar;
              return $avatar;
            }
          }
        }
        elseif($user['user_avatar_type']==2)
        {
          $avatar_w = (int)$user['user_avatar_width'];
          $avatar_h = (int)$user['user_avatar_height'];
          $avatar = '<img alt="" class="avatar" width="'.$avatar_w.'" height="'.$avatar_h.'" src="' . $user['user_avatar_link'] . '" />';
          $GLOBALS['sd_global_avatars'][$userid] = $avatar;
          return $avatar;
        }
      }
    }
    $avatar_w = (int)$mainsettings['default_avatar_width'];
    $avatar_h = (int)$mainsettings['default_avatar_height'];
    $avatar = '<img alt="" class="avatar" width="'.$avatar_w.'" height="'.$avatar_h.'" src="' . GetAvatarPath($useremail, $userid) . '" />';
    $GLOBALS['sd_global_avatars'][$userid] = $avatar;
    return $avatar;
  }

} // SD_SESSION


// ############################# FIND SESSIONID ###############################
if(!empty($_POST['s']))
{
  $sessionid = htmlspecialchars(strip_tags(sd_unhtmlspecialchars($_POST['s'])), ENT_QUOTES);
}
else if(!empty($_GET['s']))
{
  $sessionid = htmlspecialchars(strip_tags(sd_unhtmlspecialchars($_GET['s'])), ENT_QUOTES);
}
else
{
  $sessionid = sd_GetSafeMD5Cookie('sessionid'); // SD 313 - safe cookie retrieval
}

$session = '';
$user = array();// init user as Guest


// ############################################################################
// BOT HANDLING
// ############################################################################
if(SD_IS_BOT) //SD343
{
  $session = CreateSession();
  CreateCookie('userid', '');
  CreateCookie('password', '');
  $usersettings = array('userid'         => 0,
                        'usergroupid'    => 0,
                        'usergroupids'   => array(),
                        'username'       => 'BOT',
                        'loggedin'       => 0,
                        'email'          => '',
                        'timezoneoffset' => '',
                        'dstonoff'       => 0,
                        'dstauto'        => 0,
                        'ipaddress'      => USERIP,
                        'sessionurl'     => '',
                        'sessionid'      => '',
                        'salt'           => '');
  return true;
}

// ############################################################################
// SESSION LOGIN
// ############################################################################

if(!empty($sessionid))
{
  $sessionid = preg_replace('/[^a-zA-Z0-9]/', '', $sessionid); // Tobias, 2012-02-01
  $DB->result_type = MYSQL_ASSOC;
  $session = $DB->query_first('SELECT * FROM '.PRGM_TABLE_PREFIX."sessions WHERE sessionid  = '%s'
                               AND lastactivity > %d AND useragent = '%s'
                               AND ipaddress = '%s' AND admin = 0",
                               $sessionid, intval(TIME_NOW - COOKIE_TIMEOUT), $DB->escape_string(USER_AGENT), USERIP);
  $DB->result_type = MYSQL_BOTH;

  // will return an empty session if last activity is expired, meaning the user
  // will have to login via cookies 'remember me option'
}


// if a session doesn't exist that means two things
// 1) This is a user who's session deleted (empty($session)) because it expired (Subdreamer always deletes old sessions)
//    If this is the case, then we'll try logging in via a cookie
// 2) This is a guest (userid == 0)
if(empty($session) || ((int)$session['userid'] < 1))
{
  $cp = sd_GetSafeMD5Cookie('password');
  if(!empty($_COOKIE[COOKIE_PREFIX . 'userid']) && !empty($cp) && is_numeric($_COOKIE[COOKIE_PREFIX . 'userid']))
  {
    // check if cookie login is correct
    $session = '';
    //SD342 now $usertmp
    $DB->result_type = MYSQL_ASSOC;
    if($usertmp = $DB->query_first('SELECT * FROM '.PRGM_TABLE_PREFIX.'users WHERE activated = 1 AND userid = %d',
                                   $_COOKIE[COOKIE_PREFIX . 'userid']))
    {
      if(($cp == $usertmp['password']) || (!empty($usertmp['salt']) && (md5($usertmp['salt'].$cp) == $usertmp['password'])))
      {
        // delete old sessions
        if(!empty($sessionid))
        {
          $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX."sessions WHERE sessionid = '%s'", $sessionid);
        }

        // create a new session for this user
        $session = CreateSession($_COOKIE[COOKIE_PREFIX . 'userid']);
        $user = $usertmp;
      }
      unset($usertmp); //SD342
    }
    $DB->result_type = MYSQL_BOTH;

    if(empty($session) || empty($_POST['login']))
    {
      // cookie's bad and since we're not doing anything login related, kill the bad cookie
      CreateCookie('userid', '');
      CreateCookie('password', '');
      $user = array(); //SD342
    }
  }
}

// ########################## CREATE GUEST SESSION ############################

if(empty($session))
{
  $session = CreateSession();
}

// ########################### SETUP USER VARIABLE ############################

if(!empty($session['userid']) && ($session['userid'] > 0))
{
  $DB->result_type = MYSQL_ASSOC;
  if($user = $DB->query_first('SELECT * FROM '.PRGM_TABLE_PREFIX.'users WHERE activated = 1 AND userid = %d', $session['userid']))
  {
    // everything else is filled from the database query
    $user['usergroupid']  = (int)$user['usergroupid'];
    $user['usergroupids'] = array((int)$user['usergroupid']);
    //SD322: for Subdreamer usersystem only: if "usergroup_others" is set, add it's
    // usergroup id's (which had to be serialized) into the "usergroupids" array:
    if(!empty($user['usergroup_others']) && (substr($user['usergroup_others'],0,2)=='a:'))
    {
      $sd_ignore_watchdog = true;
      if(!empty($user['usergroup_others']) &&
         (($usergroup_others = @unserialize($user['usergroup_others'])) !== false))
      {
        if(is_array($usergroup_others))
        {
          $user['usergroupids'] = array_unique(array_merge($user['usergroupids'], $usergroup_others));
        }
        unset($usergroup_others);
      }
      $sd_ignore_watchdog = false;
    }
    $user['loggedin']       = 1;
    $user['timezoneoffset'] = 0;
    $user['dstonoff']       = 0;
    $user['dstauto']        = 1;
    $user['ipaddress']      = USERIP;
    $user['sessionid']      = $session['sessionid'];

    $sessioncreated = false;
  }
  $DB->result_type = MYSQL_BOTH;
}


// ############################# UPDATE SESSION ###############################

if(!$sessioncreated && !empty($session['sessionid']))
{
  if(defined('SD_331'))
  {
    if(!defined('IN_RSS')) //SD360
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX."sessions SET useragent = '%s', lastactivity = %d, location = '%s'
                WHERE sessionid = '%s' AND admin = 0",
                $DB->escape_string(USER_AGENT), TIME_NOW, $location, $session['sessionid']);
  }
  else
  {
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX."sessions SET useragent = '%s', lastactivity = %d
                WHERE sessionid = '%s' AND admin = 0",
                $DB->escape_string(USER_AGENT), TIME_NOW, $session['sessionid']);
  }
}


// ################################ FORM LOGIN ################################

if(isset($_POST['login']) && ($_POST['login'] == 'login') && !SD_IS_BOT)
{
  //SD342: ip ban check
  if($ip_banned = IsIPBanned(USERIP, (isset($p12_settings['banned_ip_addresses'])?$p12_settings['banned_ip_addresses']:null)))
  {
    $login_errors_arr[] = $sdlanguage['ip_banned'];
    sleep(1);
  }
  /*
  else
  if(!CheckFormToken(SD_TOKEN_NAME,false))
  {
    //StopLoadingPage('Sorry, are you spamming?','ERROR',403,'');
    $login_errors_arr[] = $sdlanguage['wrong_password'];
  }
  */

  $loginusername = GetVar('loginusername', '', 'string', true, false);
  $loginpassword = '';
  $rememberme = false;
  $input = preg_replace("/%0A|\\r|%0D|\\n|%00|\\0|%09|\\t|%01|%02|%03|%04|%05|%06|%07|%08|%09|%0B|%0C|%0E|%0F|%10|%11|%12|%13/im", '', $loginusername);
  if(empty($login_errors_arr))
  {
    //SD343: error on invalid characters
    if($input != $loginusername)
    {
      $loginusername = '';
      $login_errors_arr[] = $sdlanguage['wrong_username'];
      unset($_POST['loginusername'],$_POST['loginpassword']);
    }
    else
    {
      $loginpassword = sd_unhtmlspecialchars(GetVar('loginpassword', '', 'string', true, false));
      $rememberme = !empty($_POST['rememberme']) && ($_POST['rememberme']=='1');
    }
  }

  //SD343: support for several blocklist providers
  if(empty($login_errors_arr) && function_exists('sd_reputation_check'))
  {
    $blacklisted = sd_reputation_check(USERIP, 10);
    if($blacklisted !== false)
    {
      $login_errors_arr[] = trim($sdlanguage['ip_listed_on_blacklist'].' '.USERIP);
      if(!empty($mainsettings['log_invalid_login_attempts']))
      {
        WatchDog('Login','<b>'.$blacklisted.'</b>: IP: <b><span class="ipaddress">'.USERIP.'</span></b>, Username: <b>'.$input.'</b>',
                 WATCHDOG_ERROR);
      }
    }
    unset($blacklisted);
  }

  if(!$ip_banned && empty($login_errors_arr))
  if(strlen($loginusername) && strlen($loginpassword))
  {
    $DB->result_type = MYSQL_ASSOC;
    if($user = $DB->query_first('SELECT * FROM '.PRGM_TABLE_PREFIX."users WHERE trim(username) = '%s'", trim($loginusername)))
    {
      $newpwd = $ok1 = $ok2 = false;
      if(!empty($user['banned']))
      {
        sleep(1);
        $login_errors_arr[] = $sdlanguage['you_are_banned'];
        if(!empty($mainsettings['log_invalid_login_attempts'])) //SD343
        {
          Watchdog('Login Error','Banned user login attempt: <strong>'.$loginusername.' (IP:&nbsp;'.USERIP.')</strong>',WATCHDOG_WARNING);
        }
      }
      else
      if(empty($user['activated']))
      {
        sleep(1);
        $login_errors_arr[] = $sdlanguage['not_yet_activated'];
        if(!empty($mainsettings['log_invalid_login_attempts'])) //SD343
        {
          Watchdog('Login Error','Inactive user login attempt: <strong>'.$loginusername.' (IP:&nbsp;'.USERIP.')</strong>',WATCHDOG_WARNING);
        }
      }
      else
      {
        // SD331: determine, if regular or new salted password is valid:
        $ok1 = empty($user['use_salt']) && ($user['password'] == md5($loginpassword));
        $ok2 = !empty($user['use_salt']) && !empty($user['salt']) && ($user['password'] == md5($user['salt'].md5($loginpassword)));
        if(!$ok1 && !$ok2)
        {
          $login_errors_arr[] = $sdlanguage['wrong_username'/*wrong_password*/];
          if(!empty($mainsettings['log_invalid_login_attempts'])) //SD343
          {
            Watchdog('Login Error','Wrong password for user: <strong>'.$loginusername.' (IP:&nbsp;'.USERIP.')</strong>',WATCHDOG_WARNING);
          }
        }
      }

      if(empty($login_errors_arr))
      {
        // Update user is salt does not exist yet
        if($ok1 && empty($user['salt']))
        {
          $user['salt'] = sd_CreateUserSalt($user['userid']);
        }
        // IF salt value exists, update password with salted version if possible:
        if($ok1 && !empty($user['salt']) && !$ok2)
        {
          $newpwd = $user['password'] = md5($user['salt'].md5($loginpassword));
          $DB->query('UPDATE '.PRGM_TABLE_PREFIX."users SET password = '%s', use_salt = 1, lastactivity = %d".
                     " WHERE userid = %d AND password != '%s'",
                     $newpwd, TIME_NOW, $user['userid'], $newpwd);
        }
        else
        {
          $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'users SET lastactivity = %d WHERE userid = %d',
                     TIME_NOW, $user['userid']);
        }
        // everything else is filled from the database query
        $user['usergroupid']    = $user['usergroupid'];
        $user['usergroupids']   = array($user['usergroupid']);
        $user['loggedin']       = 1;
        $user['timezoneoffset'] = 0;
        $user['dstonoff']       = 0;
        $user['dstauto']        = 1;
        $user['ipaddress']      = USERIP;
        //SD322: for Subdreamer usersystem only: if "usergroup_others" is set, add it's
        // usergroup id's (which had to be serialized) into the "usergroupids" array:
        if(!empty($user['usergroup_others']) && (substr($user['usergroup_others'],0,2)=='a:'))
        {
          $sd_ignore_watchdog = true;
          if((($usergroup_others = @unserialize($user['usergroup_others'])) !== false) && is_array($usergroup_others))
          {
            $user['usergroupids'] = @array_unique(@array_merge($user['usergroupids'], $usergroup_others));
          }
          unset($usergroup_others);
          $sd_ignore_watchdog = false;
        }

        // delete old session or the newly created session for this user
        // (a session was created before this login script was even executed)
        $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX."sessions WHERE (sessionid = '%s') OR
                    (ipaddress = '%s' AND useragent = '%s' AND lastactivity < %d AND admin = 0)",
                   $sessionid, USERIP, $DB->escape_string(USER_AGENT), intval(TIME_NOW - COOKIE_TIMEOUT));

        // create new session
        if(defined('SD_331'))
        {
          $DB->query('REPLACE INTO '.PRGM_TABLE_PREFIX."sessions (sessionid, userid, ipaddress, useragent, lastactivity, location, loggedin)
                      VALUES ('%s', %d, '%s', '%s', %d, '%s', 1)",
                      $sessionid, $user['userid'], USERIP, $DB->escape_string(USER_AGENT), TIME_NOW, $location);
        }
        else
        {
          $DB->query('REPLACE INTO '.PRGM_TABLE_PREFIX."sessions (sessionid, userid, ipaddress, useragent, lastactivity)
                      VALUES ('%s', %d, '%s', '%s', %d)",
                      $sessionid, $user['userid'], USERIP, $DB->escape_string(USER_AGENT), TIME_NOW);
        }
        // save sessionid into cookie
        CreateCookie('sessionid', $sessionid);

        // if remember me then save username and password
        DeleteCookies();
        CreateCookie('userid', $user['userid'], $rememberme);
        CreateCookie('password', $user['password'], $rememberme);
      }
      unset($newpwd,$ok1,$ok2);
    }
    else
    {
      $login_errors_arr[] = $sdlanguage['wrong_username'];
      if(!empty($mainsettings['log_invalid_login_attempts'])) //SD342
      {
        Watchdog('Login Error','Unknown user: <strong>'.$loginusername.' (IP:&nbsp;'.USERIP.')</strong>',WATCHDOG_WARNING);
      }
    }
    $DB->result_type = MYSQL_BOTH;
  }
  else
  {
    $login_errors_arr[] = $sdlanguage['please_enter_username'];
  }
  if(!empty($login_errors_arr))
  {
    $user=array();
    sleep(2);
  }
  unset($loginusername,$loginpassword);
} //login

// ############################################################################
// LOGOUT
// ############################################################################

if(isset($_POST['logout']) || isset($_GET['logout']))
{
  // erase all cookies
  CreateCookie('sessionid', '');
  CreateCookie('userid', '');
  CreateCookie('password', '');

  if(!empty($user['userid']) && ($user['userid'] != -1))
  {
    // update user lastactivity and user lastvisit
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'users SET lastactivity = %d WHERE activated = 1 AND userid = %d',
               intval(TIME_NOW - COOKIE_TIMEOUT), $user['userid']);

    // delete sessions with same userid
    $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'sessions WHERE userid = %d AND admin = 0', $user['userid']);
  }

  // delete sessions with same sessionid
  $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX."sessions WHERE (sessionid = '%s') OR (admin = 0
              AND ipaddress = '%s' AND useragent = '%s AND lastactivity < %d')",
              $sessionid, USERIP, $DB->escape_string(USER_AGENT), intval(TIME_NOW - COOKIE_TIMEOUT));

  // create a new sessionid for this guest
  $sessionid = CreateSessionID();

  // save sessionid into cookie
  CreateCookie('sessionid', $sessionid);

  // save this new sessionid in the sessions table
  //SD322: do not create session for bots in DB
  if(!SD_IS_BOT)
  {
    if(defined('SD_331'))
    {
      $DB->query('REPLACE INTO '.PRGM_TABLE_PREFIX."sessions (sessionid, userid, ipaddress, useragent, lastactivity, location, loggedin)
                  VALUES ('%s', 0, '%s', '%s', %d, '%s', 0)",
                  $sessionid, USERIP, $DB->escape_string(USER_AGENT), TIME_NOW, $location);
    }
    else
    {
      $DB->query('REPLACE INTO '.PRGM_TABLE_PREFIX."sessions (sessionid, userid, ipaddress, useragent, lastactivity)
                  VALUES ('%s', 0, '%s', '%s', %d)",
                  $sessionid, USERIP, $DB->escape_string(USER_AGENT), TIME_NOW);
    }
  }

} //logout

// Clear $user array in case of logout or login error
if(empty($user['userid']) || !empty($login_errors_arr) || isset($_GET['logout']) || isset($_POST['logout']))
{
  $user = array('userid'         => 0,
                'usergroupid'    => GUESTS_UGID,
                'usergroupids'   => array(GUESTS_UGID),
                'username'       => '',
                'loggedin'       => 0,
                'email'          => '',
                'timezoneoffset' => 0,
                'dstonoff'       => 0,
                'dstauto'        => 1,
                'salt'           => '',
                'ipaddress'      => USERIP);
}


// ##################### SUBDREAMER USER SETTINGS SETUP #######################

$usersettings = array('userid'         => $user['userid'],
                      'usergroupid'    => $user['usergroupid'],
                      'usergroupids'   => (isset($user['usergroupids'])?$user['usergroupids']:array($user['usergroupid'])),
                      'username'       => $user['username'],
                      'loggedin'       => (empty($user['loggedin']) ? 0 : 1),
                      'email'          => (isset($user['email']) ? $user['email'] : ''),
                      'timezoneoffset' => (isset($user['timezoneoffset']) ? $user['timezoneoffset'] : ''),
                      'dstonoff'       => (empty($user['dstonoff']) ? 0 : 1),
                      'dstauto'        => (empty($user['dstauto']) ? 0 : 1),
                      'ipaddress'      => (empty($user['ipaddress'])?'':$user['ipaddress']),
                      'sessionurl'     => (empty($user['sessionurl'])?'':$user['sessionurl']),
                      'sessionid'      => (empty($user['sessionid'])?'':$user['sessionid']),
                      'salt'           => (empty($user['salt'])?'':$user['salt']));

// ############################# UNSET VARIABLES ##############################

unset($user, $session, $sessionid);

// ########################### DELETE OLD SESSIONS ############################

// To minimize the DB load, only purge old sessions every 40 minutes,
// if a user is logged in
if(!empty($usersettings['loggedin']) && (TIME_NOW % 60 > 40))
{
  if(defined('SD_331'))
  {
    $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'sessions WHERE (admin = 0) AND'.
               " ((useragent NOT LIKE '%/%') OR (lastactivity < %d AND loggedin = 0) OR (lastactivity < %d AND loggedin = 1))",
                intval(TIME_NOW - 3600), intval(TIME_NOW - COOKIE_TIMEOUT));
  }
  else
  {
    $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX."sessions WHERE (admin = 0) AND ((useragent NOT LIKE '%/%') OR (lastactivity < %d))",
               intval(TIME_NOW - COOKIE_TIMEOUT));
  }
}
