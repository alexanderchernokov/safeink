<?php

if(!defined('IN_PRGM')) return;

// ############################## SETUP VARIABLES ##############################

$login_errors_arr = array();

$tableprefix  = $usersystem['tblprefix'];
$cookietime   = $usersystem['cookietimeout'];
$cookiename   = $usersystem['cookieprefix'];
$cookiedomain = isset($usersystem['cookiedomain']) ? $usersystem['cookiedomain'] : '';
$cookiepath   = isset($usersystem['cookiepath'])   ? $usersystem['cookiepath'] : "/";

// ################################# MD5 HMAC ##################################

function md5_hmac($data, $key)
{
  $key = str_pad(strlen($key) <= 64 ? $key : pack('H*', md5($key)), 64, chr(0x00));
  return md5(($key ^ str_repeat(chr(0x5c), 64)) . pack('H*', md5(($key ^ str_repeat(chr(0x36), 64)). $data)));
}


// ############################# CHECK IF SESSION ##############################

if(session_id() == '')
{
  // This is here to stop people from using bad junky PHPSESSIDs.
  if(isset($_REQUEST[session_name()]) && preg_match('~^[A-Za-z0-9]{32}$~', $_REQUEST[session_name()]) == 0 && !isset($_COOKIE[session_name()]))
  {
    $_COOKIE[session_name()] = md5(md5('smf_sess_' . time()) . rand());
  }

  // Only use database session
  if(!$databasesessions = $DB->query_first('SELECT * FROM ' . $tableprefix . 'settings WHERE variable = \'databaseSession_enable\' AND value = 1'))
  {
    echo 'Subdreamer can only integrate with SMF\'s using Database Sessions';
    exit;
  }

  // Change it so the cache settings are a little looser than default.
  if($databasesessionloose = $DB->query_first('SELECT * FROM ' . $tableprefix . 'settings WHERE variable = \'databaseSession_loose\' AND value = 1'))
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
  $_COOKIE[$cookiename] = sd_unhtmlspecialchars($_COOKIE[$cookiename]);
  $_COOKIE[$cookiename] = stripslashes($_COOKIE[$cookiename]);

  // fix a security hole in PHP 4.3.9 and below...
  if(preg_match('~^a:3:\{i:0;(i:\d{1,6}|s:[1-6]:"\d{1,6}");i:1;s:(0|32):"([a-fA-F0-9]{32})?";i:2;i:\d{1,12};\}$~', $_COOKIE[$cookiename]) == 1)
  {
    list($ID_MEMBER, $password) = @unserialize($_COOKIE[$cookiename]);
    $ID_MEMBER = !empty($ID_MEMBER) ? (int) $ID_MEMBER : 0;
  }
  else
  {
    $ID_MEMBER = 0;
  }
}
else if(isset($_SESSION['login_' . $cookiename]) AND ($_SESSION['USER_AGENT'] == $_SERVER['HTTP_USER_AGENT'] OR !empty($modSettings['disableCheckUA'])))
{
  // check by session
  list ($ID_MEMBER, $password, $login_span) = @unserialize(stripslashes($_SESSION['login_' . $cookiename]));
  $ID_MEMBER = !empty($ID_MEMBER) && $login_span > time() ? (int) $ID_MEMBER : 0;
}
else
{
  $ID_MEMBER = 0;
}


// ############################ LOAD USER SETTINGS #############################

if($ID_MEMBER != 0 AND is_numeric($ID_MEMBER))
{
  if($getuser = $DB->query_first('SELECT mem.*, IFNULL(a.ID_ATTACH, 0) AS ID_ATTACH
    FROM ' . $tableprefix . 'members AS mem
    LEFT JOIN ' . $tableprefix . 'attachments AS a ON (a.ID_MEMBER = mem.ID_MEMBER)
    LEFT JOIN ' . $tableprefix . 'banned AS b ON (b.ID_MEMBER = mem.ID_MEMBER)
    WHERE mem.ID_MEMBER = %d AND (b.ID_BAN IS NULL OR (b.expire_time IS NOT NULL AND b.expire_time < UNIX_TIMESTAMP(NOW())))',
    $ID_MEMBER))
  {
    // check user password
    $ID_MEMBER = md5_hmac($getuser['passwd'], 'ys') != $password || empty($getuser['is_activated']) ? 0 : $getuser['ID_MEMBER'];
  }
  else
  {
    $ID_MEMBER = 0;
  }
}


// ############################## LOAD USERGROUPS ##############################

if($ID_MEMBER != 0)
{
  $groups = array($getuser['ID_GROUP'], $getuser['ID_POST_GROUP']);

  if(!empty($getuser['additionalGroups']))
  {
    $addGroups = explode("'", $getuser['additionalGroups']);
    $groups = array_merge($groups, $addGroups);
  }

  $user = array('userid'         => $getuser['ID_MEMBER'],
                'usergroupid'    => $getuser['ID_GROUP'],
                'usergroupids'   => $groups,
                'username'       => $getuser['memberName'],
                'displayname'    => $getuser['realName'],
                'loggedin'       => 1,
                'email'          => $getuser['emailAddress'],
                'timezoneoffset' => $getuser['timeOffset'],
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

function setLoginCookie($cookie_length, $id, $password = '')
{
  global $cookiename, $cookiedomain, $cookiepath;

  $data = serialize(empty($id) ? array(0, '', 0) : array($id, md5_hmac($password, 'ys'), time() + $cookie_length));
  /*
  if(!strlen($cookiedomain) && ((substr_count($_SERVER['HTTP_HOST'], '.') > 1) && (substr($_SERVER['HTTP_HOST'],0,3) != 'www')))
  {
    $cookiedomain = '.'.$_SERVER['HTTP_HOST'];
  }
  */
  setcookie($cookiename, $data, time() + $cookie_length, $cookiepath, $cookiedomain, 0);

  $_COOKIE[$cookiename] = $data;
}


// ################################ FORM LOGIN #################################

if(isset($_POST['login']) && $_POST['login'] == 'login')
{
  $loginusername = isset($_POST['loginusername']) ? $_POST['loginusername'] : '';
  $loginpassword = isset($_POST['loginpassword']) ? $_POST['loginpassword'] : '';
  $rememberme    = !empty($_POST['rememberme']);

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

  $memberfound = false;

  if(strlen($loginusername))
  {
    if($getuser = $DB->query_first('SELECT passwd, m.ID_MEMBER, ID_GROUP, ID_POST_GROUP, is_activated, emailAddress, additionalGroups, memberName, passwordSalt, timeOffset, b.ID_BAN
      FROM ' . $tableprefix . 'members m
      LEFT JOIN ' . $tableprefix . 'banned AS b ON (b.ID_MEMBER = m.ID_MEMBER AND (b.expire_time IS NULL OR b.expire_time >= UNIX_TIMESTAMP(NOW())))
      WHERE memberName = \'%s\'', $loginusername))
    {
      $memberfound = true;
    }
    else if($getuser = $DB->query_first('SELECT passwd, m.ID_MEMBER, ID_GROUP, ID_POST_GROUP, is_activated, emailAddress, additionalGroups, memberName, passwordSalt, timeOffset, b.ID_BAN
      FROM ' . $tableprefix . 'members m
      LEFT JOIN ' . $tableprefix . 'banned AS b ON (b.ID_MEMBER = m.ID_MEMBER AND (b.expire_time IS NULL OR b.expire_time >= UNIX_TIMESTAMP(NOW())))
      WHERE emailAddress = \'%s\'', $loginusername))
    {
      $memberfound = true;
    }

    if($memberfound)
    {
      $md5loginpassword = md5_hmac($loginpassword, strtolower($getuser['memberName']));

      if($getuser['passwd'] != $md5loginpassword)
      {
        $login_errors_arr[] = $sdlanguage['wrong_password'];
      }
      else if($getuser['ID_BAN'] > 0)
      {
        $login_errors_arr[] = $sdlanguage['you_are_banned'];
      }
      else
      {
        $groups = array($getuser['ID_GROUP'], $getuser['ID_POST_GROUP']);

        if(!empty($getuser['additionalGroups']))
        {
          $addGroups = explode(",", $getuser['additionalGroups']);
          $groups = array_merge($groups, $addGroups);
        }

        $user = array('userid'         => $getuser['ID_MEMBER'],
                      'usergroupid'    => $getuser['ID_GROUP'],
                      'usergroupids'   => $groups,
                      'username'       => $getuser['memberName'],
                      'displayname'    => $getuser['realName'],
                      'loggedin'       => 1,
                      'email'          => $getuser['emailAddress'],
                      'timezoneoffset' => $getuser['timeOffset'],
                      'dstonoff'       => 0,   // NA?
                      'dstauto'        => 1);  // NA?

        // If remember me is checked, expire the cookie a long time into the future
        if($rememberme)
        {
          $cookietime = 31536000; // one year
        }
        else
        {
          $cookietime = 60 * $cookietime;
        }

        setLoginCookie($cookietime, $user['userid'], $md5loginpassword);
        unset($cookietime);
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

if(isset($_GET['logout']) AND $user['userid'] > 0)
{
  // If you log out, you aren't online anymore :P.
  $DB->query('DELETE FROM ' . $tableprefix . 'log_online WHERE ID_MEMBER = %d LIMIT 1', $user['userid']);

  // Empty the cookie! (set it in the past, and for ID_MEMBER = 0)
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



// ############################## UNSET VARIABLES ##############################

unset($user, $session, $sessionid);

// ############################## USER FUNCTIONS ##############################

function IsIPBanned($clientip)
{
  global $DB, $usersystem, $dbname;

  $ban_query = array();

  if (preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $clientip, $ip_parts) == 1)
  {
	$ban_query[] = "(ban_type = 'ip_ban'
					AND ($ip_parts[1] BETWEEN ip_low1 AND ip_high1)
					AND ($ip_parts[2] BETWEEN ip_low2 AND ip_high2)
					AND ($ip_parts[3] BETWEEN ip_low3 AND ip_high3)
					AND ($ip_parts[4] BETWEEN ip_low4 AND ip_high4))";

  }
  else
  {
  	// Invalid IP address so we just let them through
  	return false;
  }

  $query = 'SELECT ban_type, restriction_type, reason
				FROM ' . $usersystem['tblprefix'] . 'banned
				WHERE (expire_time IS NULL OR expire_time > ' . time() . ')
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

  return $DB->get_num_rows() > 0;
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
}

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

  if(empty($username))
  {
    $useravatar = $DB->query_first('SELECT ID_MEMBER, avatar FROM ' . $tableprefix . 'members WHERE ID_MEMBER = %d', $userid);
  }
  else
  {
    $useravatar = $DB->query_first('SELECT ID_MEMBER, avatar FROM ' . $tableprefix . 'members WHERE memberName = \'%s\'', $username);
  }

  if(substr($useravatar['avatar'], 0, 4) == 'http')
  {
    // user entered url
    $avatar = '<img alt="avatar" src="' . $useravatar['avatar'] . '" />';
  }
  else if(strlen($useravatar['avatar']))
  {
    // selected avatar from forum avatars folder
    $avatar = '<img alt="avatar" src="' . $sdurl . $forumpath . 'avatars/' . $useravatar['avatar'] . '" / >';
  }
  else if($attachment = $DB->query_first("SELECT ID_ATTACH FROM " . $tableprefix . "attachments WHERE ID_MEMBER = %d", $useravatar['ID_MEMBER']))
  {
    // user uploaded avatar
    // 2.4.4: fixed wrong column name
    $avatar = '<img alt="avatar" src="' . $sdurl . $forumpath . 'index.php?action=dlattach;attach=' . $attachment['ID_ATTACH'] . ';type=avatar" />';
  }

  // switch back to subdreamer database
  if($dbname != $forumdbname)
  {
    $DB->select_db($dbname);
  }

  return $avatar;
}

?>