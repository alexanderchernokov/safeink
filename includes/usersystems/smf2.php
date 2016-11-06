<?php
if(!defined('IN_PRGM')) return;

// ########################## SETUP VARIABLES #################################

$login_errors_arr = array();

$tableprefix  = $usersystem['tblprefix'];
$cookietime   = $usersystem['cookietimeout'];
$cookiename   = $usersystem['cookieprefix']; #is overwritten in Settings.php!
$cookiedomain = isset($usersystem['cookiedomain']) ? $usersystem['cookiedomain'] : '';
$cookiepath   = isset($usersystem['cookiepath'])   ? $usersystem['cookiepath'] : "/";

$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr(htmlentities(strip_tags($_SERVER['HTTP_USER_AGENT'])),0,255) : '';
define('USER_AGENT', $user_agent);

//SD343: "$modSettings" is global var used by SMF, fill it now to lower SQL count
$modSettings = array();
if($getSMFvalues = $DB->query('SELECT variable, value FROM ' . $tableprefix . 'settings ORDER BY variable ASC'))
{
  while($smfRow = $DB->fetch_array($getSMFvalues,'',MYSQL_ASSOC))
  {
    $modSettings[$smfRow['variable']] = $smfRow['value'];
  }
}
unset($getSMFvalues,$smfRow);

//SD343: load SMF settings and special functions file:
define('SMF',1);
$sc = ''; // used by SMF

@require_once(realpath(ROOT_PATH.$usersystem['folderpath']).DIRECTORY_SEPARATOR.'Settings.php');
if(isset($sourcedir)) @include_once($sourcedir.'/Subs-Auth.php');

// ######################## CHECK FOR SESSION #################################

if(session_id() == '')
{
  // This is here to stop people from using bad junky PHPSESSIDs.
  if(isset($_REQUEST[session_name()]) &&
     (preg_match('~^[A-Za-z0-9]{32}$~', $_REQUEST[session_name()]) == 0) &&
     !isset($_COOKIE[session_name()]))
  {
    $_COOKIE[session_name()] = md5(md5('smf_sess_' . time()) . mt_rand());
  }

  // Only use database session
  if(empty($modSettings['databaseSession_enable']))
  {
    echo 'Forum Integration only supports SMF with using Database Sessions!<br />';
    exit;
  }

  // Change it so the cache settings are a little looser than default.
  if(!empty($modSettings['databaseSession_loose']))
  {
    header('Cache-Control: private');
  }
}

// There's a strange bug in PHP 4.1.2 which makes $_SESSION not work unless you do this...
if (@version_compare(PHP_VERSION, '4.2.0') == -1)
{
  $HTTP_SESSION_VARS['php_412_bugfix'] = true;
}


// ######################## CHECK COOKIE AND SESSIONS ##########################

if(isset($_COOKIE[$cookiename]))
{
  $_COOKIE[$cookiename] = sd_unhtmlspecialchars($_COOKIE[$cookiename]);  // Subdreamer specific fix for PreClean
  $_COOKIE[$cookiename] = stripslashes($_COOKIE[$cookiename]);

  // Fix a security hole in PHP 4.3.9 and below...
  if (preg_match('~^a:[34]:\{i:0;(i:\d{1,6}|s:[1-8]:"\d{1,8}");i:1;s:(0|40):"([a-fA-F0-9]{40})?";i:2;[id]:\d{1,14};(i:3;i:\d;)?\}$~', $_COOKIE[$cookiename]) == 1)
  {
    list ($id_member, $password) = @unserialize($_COOKIE[$cookiename]);
    $id_member = !empty($id_member) && strlen($password) > 0 ? (int) $id_member : 0;
  }
  else
  {
    $id_member = 0;
  }
}
else if(isset($_SESSION['login_' . $cookiename]) AND ($_SESSION['USER_AGENT'] == $_SERVER['HTTP_USER_AGENT']))
{
  // check by session
  list ($id_member, $password, $login_span) = @unserialize(stripslashes($_SESSION['login_' . $cookiename]));
  $id_member = !empty($id_member) && strlen($password) == 40 && $login_span > time() ? (int) $id_member : 0;
}
else
{
  $id_member = 0;
}


// ############################ LOAD USER SETTINGS #############################

if($id_member != 0 AND is_numeric($id_member))
{
  $DB->result_type = MYSQL_ASSOC;
  if($getuser = $DB->query_first('SELECT mem.*, IFNULL(a.id_attach, 0) AS id_attach
    FROM ' . $tableprefix . 'members AS mem
    LEFT JOIN ' . $tableprefix . 'attachments AS a ON (a.id_member = mem.id_member)
    LEFT JOIN ' . $tableprefix . 'ban_items AS bi ON (bi.id_member = mem.id_member)
    LEFT JOIN ' . $tableprefix . 'ban_groups AS bg ON (bi.id_ban_group = bg.id_ban_group)
    WHERE mem.id_member = %d AND (bi.id_ban IS NULL OR (bg.expire_time IS NOT NULL AND bg.expire_time < UNIX_TIMESTAMP(NOW())) OR (bg.id_ban_group IS NULL OR (bg.cannot_login = 0 AND bg.cannot_access = 0)))',
    $id_member))
  {
    $id_member = (strlen($password) != 40) || sha1($getuser['passwd'] . $getuser['password_salt']) != $password || empty($getuser['is_activated']) ? 0 : $getuser['id_member'];
  }
  else
  {
    $id_member = 0;
  }
}


// ############################## LOAD USERGROUPS ##############################

if($id_member != 0)
{
  $groups = array((int)$getuser['id_group'], (int)$getuser['id_post_group']);

  if(!empty($getuser['additional_groups']))
  {
    $addGroups = explode(',', $getuser['additional_groups']);
    $groups = array_unique(array_merge($groups, $addGroups));
  }

  $user = array('userid'         => $getuser['id_member'],
                'usergroupid'    => $getuser['id_group'],
                'usergroupids'   => $groups,
                'username'       => $getuser['member_name'],
                'displayname'    => (isset($getuser['real_name'])?$getuser['real_name']:$getuser['member_name']),
                'loggedin'       => 1,
                'email'          => $getuser['email_address'],
                'timezoneoffset' => $getuser['time_offset'],
                'dstonoff'       => 0,   // NA?
                'dstauto'        => 1);  // NA?
}
else
{
  $user = array('userid'         => 0,
                'usergroupid'    => 0,
                'usergroupids'   => array(0),
                'username'       => '',
                'displayname'    => '',
                'loggedin'       => 0,
                'email'          => '',
                'timezoneoffset' => 0,
                'dstonoff'       => 0,
                'dstauto'        => 1);

  // erase cookie
  if(isset($_COOKIE[$cookiename]))
  {
    $_COOKIE[$cookiename] = '';
  }
}


// ############################# SET LOGIN COOKIE ##############################

if(!function_exists('setLoginCookie'))
{
function setLoginCookie($cookie_length, $id, $password = '')
{
  global $cookiename, $cookiedomain, $cookiepath;

  $data = serialize(empty($id) ? array(0, '', 0) : array($id, $password, time() + $cookie_length, 0));
  /*
  if(!strlen($cookiedomain) && ((substr_count($_SERVER['HTTP_HOST'], '.') > 1) && (substr($_SERVER['HTTP_HOST'],0,3) != 'www')))
  {
    $cookiedomain = '.'.$_SERVER['HTTP_HOST'];
  }
  */
  setcookie($cookiename, $data, time() + $cookie_length, $cookiepath, $cookiedomain, 0);

  $_COOKIE[$cookiename] = $data;
}
}

function sdSMF_sessionOpen($save_path, $session_name)
{
  return true;
}

function sdSMF_sessionClose()
{
  return true;
}

function sdSMF_sessionRead($session_id)
{
  global $DB, $dbname, $tableprefix, $usersystem;
  if(empty($DB) || (preg_match('~^[A-Za-z0-9]{16,32}$~', $session_id) == 0)) return false;
  if($dbname != $usersystem['dbname']) $DB->select_db($usersystem['dbname']);
  $result = $DB->query_first('SELECT data FROM '.$tableprefix.
                             "sessions WHERE session_id = '%s' LIMIT 1", $session_id);
  if($dbname != $usersystem['dbname']) $DB->select_db($dbname);
  return $result;
}

function sdSMF_sessionWrite($session_id, $data)
{
  global $DB, $dbname, $tableprefix, $usersystem;
  if(empty($DB) || (preg_match('~^[A-Za-z0-9]{16,32}$~', $session_id) == 0)) return false;
  // First try to update an existing row...
  if($dbname != $usersystem['dbname']) $DB->select_db($usersystem['dbname']);
  $result = $DB->query('UPDATE '.$tableprefix."sessions SET data = '%s', last_update = '%d'".
                       " WHERE session_id = '%s'",
    $data, time(),  $session_id);
  // If that didn't work, try inserting a new one.
  if ($DB->affected_rows() == 0)
    $result = $DB->query('INSERT INTO '.$tableprefix."sessions(session_id,last_update,data) VALUES('%s',%d,'%s')",
      $session_id, time(), $data);
  if($dbname != $usersystem['dbname']) $DB->select_db($dbname);
  return $result;
}

function sdSMF_sessionDestroy($session_id)
{
  global $DB, $dbname, $tableprefix, $usersystem;
  if(empty($DB) || (preg_match('~^[A-Za-z0-9]{16,32}$~', $session_id) == 0)) return false;
  // Just delete the row...
  if($dbname != $usersystem['dbname']) $DB->select_db($usersystem['dbname']);
  $DB->query('DELETE FROM '.$tableprefix."sessions WHERE session_id = '%s'", $session_id);
  if($dbname != $usersystem['dbname']) $DB->select_db($dbname);
  return true;
}

function sdSMF_sessionGC($max_lifetime)
{
  global $DB, $dbname, $tableprefix, $usersystem;
  if(empty($DB)) return false;
  // Just set to the default or lower?  Ignore it for a higher value. (hopefully)
  if(!empty($modSettings['databaseSession_lifetime']) &&
     ($max_lifetime <= 1440 || $modSettings['databaseSession_lifetime'] > $max_lifetime))
    $max_lifetime = max($modSettings['databaseSession_lifetime'], 60);
  if($dbname != $usersystem['dbname']) $DB->select_db($usersystem['dbname']);
  $DB->query('DELETE FROM '.$tableprefix."sessions WHERE last_update < %d", (time() - $max_lifetime));
  if($dbname != $usersystem['dbname']) $DB->select_db($dbname);
  return true;
}

// Attempt to start the session, unless it already has been.
function loadSession()
{
  global $HTTP_SESSION_VARS, $modSettings, $boardurl, $sc;

  // Attempt to change a few PHP settings.
  @ini_set('session.use_cookies', true);
  @ini_set('session.use_only_cookies', false);
  @ini_set('url_rewriter.tags', '');
  @ini_set('session.use_trans_sid', false);
  @ini_set('arg_separator.output', '&amp;');

  if (!empty($modSettings['globalCookies']))
  {
    $parsed_url = parse_url($boardurl);

    if (preg_match('~^\d{1,3}(\.\d{1,3}){3}$~', $parsed_url['host']) == 0 && preg_match('~(?:[^\.]+\.)?([^\.]{2,}\..+)\z~i', $parsed_url['host'], $parts) == 1)
      @ini_set('session.cookie_domain', '.' . $parts[1]);
  }
  // !!! Set the session cookie path?

  // If it's already been started... probably best to skip this.
  if ((@ini_get('session.auto_start') == 1 && !empty($modSettings['databaseSession_enable'])) || session_id() == '')
  {
    // Attempt to end the already-started session.
    if (@ini_get('session.auto_start') == 1)
      @session_write_close();

    // This is here to stop people from using bad junky PHPSESSIDs.
    if (isset($_REQUEST[session_name()]) && preg_match('~^[A-Za-z0-9]{16,32}$~', $_REQUEST[session_name()]) == 0 && !isset($_COOKIE[session_name()]))
    {
      $session_id = md5(md5('smf_sess_' . time()) . mt_rand());
      $_REQUEST[session_name()] = $session_id;
      $_GET[session_name()] = $session_id;
      $_POST[session_name()] = $session_id;
    }

    // Use database sessions? (they don't work in 4.1.x!)
    if (!empty($modSettings['databaseSession_enable']) && @version_compare(PHP_VERSION, '4.2.0') != -1)
    {
      session_set_save_handler('sdSMF_sessionOpen', 'sdSMF_sessionClose', 'sdSMF_sessionRead',
                               'sdSMF_sessionWrite', 'sdSMF_sessionDestroy', 'sdSMF_sessionGC');
      @ini_set('session.gc_probability', '1');
    }
    elseif (@ini_get('session.gc_maxlifetime') <= 1440 && !empty($modSettings['databaseSession_lifetime']))
      @ini_set('session.gc_maxlifetime', max($modSettings['databaseSession_lifetime'], 60));

    // Use cache setting sessions?
    /*
    if (empty($modSettings['databaseSession_enable']) && !empty($modSettings['cache_enable']) && php_sapi_name() != 'cli')
    {
      if (function_exists('mmcache_set_session_handlers'))
        mmcache_set_session_handlers();
      elseif (function_exists('eaccelerator_set_session_handlers'))
        eaccelerator_set_session_handlers();
    }
    */

    session_start();

    // Change it so the cache settings are a little looser than default.
    if (!empty($modSettings['databaseSession_loose']))
      header('Cache-Control: private');
  }

  // While PHP 4.1.x should use $_SESSION, it seems to need this to do it right.
  if (@version_compare(PHP_VERSION, '4.2.0') == -1)
    $HTTP_SESSION_VARS['php_412_bugfix'] = true;

  // Set the randomly generated code.
  if (!isset($_SESSION['session_var']))
  {
    $_SESSION['session_value'] = md5(session_id() . mt_rand());
    $_SESSION['session_var'] = substr(preg_replace('~^\d+~', '', sha1(mt_rand() . session_id() . mt_rand())), 0, rand(7, 12));
  }
  $sc = $_SESSION['session_value'];
}

loadSession();

// ################################ FORM LOGIN ################################

if(isset($_POST['login']) && $_POST['login'] == 'login')
{
  //SMF:
  if (empty($_SESSION['login_url']) && isset($_SESSION['old_url']) && strpos($_SESSION['old_url'], 'dlattach') === false && preg_match('~(board|topic)[=,]~', $_SESSION['old_url']) != 0)
    $_SESSION['login_url'] = $_SESSION['old_url'];
  if (isset($_SESSION['failed_login']) && $_SESSION['failed_login'] >= $modSettings['failed_login_threshold'] * 3)
    fatal_lang_error('login_threshold_fail', 'critical');
  $modSettings['cookieTime'] = 10080; //1 week

  $loginusername = isset($_POST['loginusername']) ? $_POST['loginusername'] : '';
  $loginpassword = isset($_POST['loginpassword']) ? $_POST['loginpassword'] : '';
  $rememberme    = !empty($_POST['rememberme']);
  $memberfound   = false;

  //SD343: check username for non-allowed characters as SMF does:
  $loginusername = preg_replace('~&amp;#(\\d{1,7}|x[0-9a-fA-F]{1,6});~', '&#\\1;', $loginusername);
  if (preg_match('~[<>&"\'=\\\]~', preg_replace('~(&#(\\d{1,7}|x[0-9a-fA-F]{1,6});)~', '', $loginusername)) != 0)
  {
    $login_errors_arr[] = $sdlanguage['wrong_username'];
  }
  else
  if(trim($loginpassword) == '')
  {
    $login_errors_arr[] = $sdlanguage['please_enter_username'];
  }
  else
  if(strlen($loginusername))
  {
    if($dbname != $usersystem['dbname']) $DB->select_db($usersystem['dbname']);

    if($getuser = $DB->query_first('SELECT passwd, m.id_member, id_group, id_post_group,
      is_activated, m.email_address, additional_groups, m.member_name, password_salt,
      time_offset, bi.id_ban, bg.id_ban_group, m.real_name
      FROM ' . $tableprefix . 'members m
      LEFT JOIN ' . $tableprefix . 'ban_items AS bi ON (bi.id_member = m.id_member)
      LEFT JOIN ' . $tableprefix . "ban_groups AS bg ON (bi.id_ban_group = bg.id_ban_group
        AND (bg.expire_time is null or bg.expire_time >= UNIX_TIMESTAMP(NOW()))
        AND (bg.cannot_access = 1 OR bg.cannot_login = 1))
      WHERE m.member_name = '%s' LIMIT 1", $loginusername))
    {
      $memberfound = true;
    }
    else
    if($getuser = $DB->query_first('SELECT passwd, m.id_member, id_group, id_post_group,
      is_activated, m.email_address, additional_groups, m.member_name, password_salt,
      time_offset, bi.id_ban, bg.id_ban_group, m.real_name
      FROM ' . $tableprefix . 'members m
      LEFT JOIN ' . $tableprefix . 'ban_items  AS bi ON (bi.id_member = m.id_member)
      LEFT JOIN ' . $tableprefix . "ban_groups AS bg ON (bi.id_ban_group = bg.id_ban_group
        AND (bg.expire_time IS NULL OR bg.expire_time >= UNIX_TIMESTAMP(NOW()))
        AND (bg.cannot_access = 1 OR bg.cannot_login = 1))
      WHERE m.email_address = '%s' LIMIT 1", $loginusername))
    {
      $memberfound = true;
    }

    if($memberfound)
    {
      //SD343: sd_unhtmlspecialchars() on pwd:
      $sha1loginpassword = sha1(strtolower($getuser['member_name']) . sd_unhtmlspecialchars($loginpassword));

      if(empty($getuser['password_salt']))
      {
        $login_errors_arr[] = 'You must login to the forum the first time!';
      }
      else
      if(strlen($sha1loginpassword) != 40 || ($sha1loginpassword != $getuser['passwd']))
      {
        $login_errors_arr[] = $sdlanguage['wrong_password'];
      }
      else
      if(!empty($getuser['id_ban']) && ($getuser['id_ban_group'] > 0))
      {
        $login_errors_arr[] = $sdlanguage['you_are_banned'];
      }
      else
      {
        $groups = array();
        if($getuser['id_group'] > 0) $groups[] = (int)$getuser['id_group'];
        if($getuser['id_post_group'] > 0) $groups[] = (int)$getuser['id_post_group'];

        if(!empty($getuser['additional_groups']))
        {
          $addGroups = explode(",", $getuser['additional_groups']);
          $groups = array_unique(array_merge($groups, $addGroups));
        }

        $user = array('userid'         => (int)$getuser['id_member'],
                      'usergroupid'    => (int)$getuser['id_group'],
                      'usergroupids'   => $groups,
                      'username'       => $getuser['member_name'],
                      'displayname'    => (isset($getuser['real_name'])?$getuser['real_name']:$getuser['member_name']),
                      'loggedin'       => 1,
                      'email'          => $getuser['email_address'],
                      'timezoneoffset' => $getuser['time_offset'],
                      'dstonoff'       => 0,   // NA?
                      'dstauto'        => 1);  // NA?

        // If remember me is checked, expire the cookie a long time into the future
        if(!$rememberme)
        {
          $modSettings['cookieTime'] = 60;
        }
        $user['is_admin'] = ($user['usergroupid'] == 1) || in_array(1, $groups);

        setLoginCookie(60 * $modSettings['cookieTime'], $user['userid'], sha1($getuser['passwd'] . $getuser['password_salt']));

        if($dbname != $usersystem['dbname']) $DB->select_db($usersystem['dbname']); //SD370

        // Additional checks
        if(isset($_SESSION['failed_login'])) unset($_SESSION['failed_login']);
        $request = $DB->query_first('SELECT id_member, last_login FROM '.$tableprefix.
                                    'members WHERE id_member = %d AND last_login = 0',$user['userid']);
        if(!empty($request['id_member']))
          $_SESSION['first_login'] = true;
        else
          unset($_SESSION['first_login']);

        $DB->query('UPDATE '.$tableprefix."members SET last_login = %d, member_ip = '%s', member_ip2 = '%s'",
                   $user['userid'], time(), USERIP, isset($_SERVER['BAN_CHECK_IP'])?(string)$_SERVER['BAN_CHECK_IP']:USERIP);
        $DB->query('DELETE FROM '.$tableprefix."log_online WHERE session = '%s'", 'ip'.USERIP);
        $_SESSION['log_time'] = 0;

        unset($request);
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

if(empty($login_errors_arr) && !empty($_GET['logout']))
{
  if($dbname != $usersystem['dbname']) $DB->select_db($usersystem['dbname']);

  if(isset($_SESSION['pack_ftp'])) $_SESSION['pack_ftp'] = null;
  if(isset($_SESSION['openid'])) unset($_SESSION['openid']);
  unset($_SESSION['first_login'],$_SESSION['logout_url']);
  $_SESSION['log_time'] = 0;

  // If you log out, you aren't online anymore :P.
  if($user['userid'] > 0)
  $DB->query('DELETE FROM ' . $tableprefix . 'log_online WHERE id_member = %d LIMIT 1', $user['userid']);

  // Empty the cookie! (set it in the past, and for id_member = 0)
  setLoginCookie(-3600, 0);

  $user = array('userid'         => 0,
                'usergroupid'    => 0,
                'usergroupids'   => array(0),
                'username'       => '',
                'displayname'    => '',
                'loggedin'       => 0,
                'email'          => '',
                'timezoneoffset' => 0,
                'dstonoff'       => 0,
                'dstauto'        => 1);
}

// ############################ ADD SESSION TO URL? ############################
if(sizeof($_COOKIE) > 0 OR preg_match("#(google|msnbot|yahoo! slurp)#si", $_SERVER['HTTP_USER_AGENT']))
{
  $user['sessionurl'] = '';
}

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

// ############################# UNSET VARIABLES ##############################

//SD370: unset also variables from SMF's config file
unset($user, $session, $sessionid,
      $maintenance, $mtitle, $mmessage, $mbname, $language, $boardurl,
      $webmaster_email, $cookiename, $db_type, $db_server, $db_name, $db_user,
      $db_passwd, $ssi_db_user, $ssi_db_passwd, $db_prefix, $db_persist,
      $db_error_send, $boarddir, $sourcedir, $cachedir, $db_last_error,
      $db_character_set);


// ############################## USER FUNCTIONS ##############################

function IsIPBanned($clientip)
{
  global $DB, $usersystem, $dbname;

  $ban_query = array();

  if (preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $clientip, $ip_parts) == 1)
  {
	  $ban_query[] = "(($ip_parts[1] BETWEEN bi.ip_low1 AND bi.ip_high1)
			AND ($ip_parts[2] BETWEEN bi.ip_low2 AND bi.ip_high2)
			AND ($ip_parts[3] BETWEEN bi.ip_low3 AND bi.ip_high3)
			AND ($ip_parts[4] BETWEEN bi.ip_low4 AND bi.ip_high4))";

  }
  else
  {
  	// Invalid IP address so we just let them through
  	return false;
  }

  $query = 'SELECT bi.id_ban, bg.cannot_access, bg.cannot_register, bg.cannot_post, bg.cannot_login, bg.reason
				FROM ' . $usersystem['tblprefix'] . 'ban_groups AS bg, ' . $usersystem['tblprefix'] . 'ban_items AS bi
				WHERE bg.id_ban_group = bi.id_ban_group
				AND (bg.expire_time IS NULL OR bg.expire_time > ' . TIME_NOW . ')
				AND (' . implode(' OR ', $ban_query) . ')';

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

  return ($DB->get_num_rows() > 0);

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

  $url = '';
  switch($linkType)
  {
    case 1:
      $url = 'index.php?action=register';
      break;
    case 2:
      $url = 'index.php?action=profile';
      break;
    case 3:
      $url = 'index.php?action=reminder';
      break;
    case 4:
      $url = 'index.php?action=profile;u=' . $userid;
      break;
    case 5:
      $url = 'index.php?action=pm;sa=send;u=' . $userid;
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
  if($dbname != $forumdbname) $DB->select_db($forumdbname);

  if(empty($username))
  {
    $useravatar = $DB->query_first('SELECT id_member, avatar FROM ' . $tableprefix . 'members WHERE id_member = %d', $userid);
  }
  else
  {
    $useravatar = $DB->query_first('SELECT id_member, avatar FROM ' . $tableprefix . "members WHERE member_name = '%s'", $username);
  }

  if(substr($useravatar['avatar'], 0, 4) == 'http')
  {
    // user entered url
    $avatar = '<img alt="avatar" src="' . $useravatar['avatar'] . '" />';
  }
  else if(strlen($useravatar['avatar']))
  {
    // selected avatar from forum avatars folder
    $avatar = '<img alt="avatar" src="' . $sdurl . $forumpath . 'avatars/' . $useravatar['avatar'] . '" />';
  }
  else if($attachment = $DB->query_first("SELECT id_attach FROM " . $tableprefix . "attachments WHERE id_member = %d", $useravatar['id_member']))
  {
    // user uploaded avatar
    // 2.4.4: fixed wrong column name
    $avatar = '<img alt="avatar" src="' . $sdurl . $forumpath . 'index.php?action=dlattach;attach=' . $attachment['id_attach'] . ';type=avatar" />';
  }

  // switch back to subdreamer database
  if($dbname != $forumdbname) $DB->select_db($dbname);

  return $avatar;

} //ForumAvatar
