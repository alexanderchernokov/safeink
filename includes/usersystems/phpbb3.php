<?php

if(!defined('IN_PRGM')) exit();

defined('TIMENOW') || define('TIMENOW', time());
$sessioncreated = false;

// ################## PHPBB3 VARIABLES & UTF-8 TOOLS ###########################
// used for special text conversion to/from phpBB3!
$phpbb_root_path = $usersystem['folderpath'];
$phpBB_UTF_file1 = $phpbb_root_path.'includes/utf/utf_normalizer.php';
$phpBB_UTF_file2 = $phpbb_root_path.'includes/utf/utf_tools.php';
$phpEx = substr(strrchr(__FILE__, '.'), 1);

if(is_file($phpBB_UTF_file1) && is_file($phpBB_UTF_file2))
{
  DEFINE('IN_PHPBB', true);
  include_once($phpBB_UTF_file1);
  include_once($phpBB_UTF_file2);
}
else
{
  //WatchDog('phpBB3','Missing phpBB3 files: '.$phpBB_UTF_file1);
}
unset($phpBB_UTF_file1, $phpBB_UTF_file2);

$tableprefix = $usersystem['tblprefix'];

if($DB->database != $usersystem['dbname']) $DB->select_db($usersystem['dbname']);

// Get phpBB3 config values;
$phpbb_config = array();
if($getconfig = $DB->query("SELECT config_name, config_value FROM " . $tableprefix . "config
  WHERE config_name IN ('allow_autologin','board_dst','browser_check','cookie_secure',
  'cookie_name','cookie_path','cookie_domain','forwarded_for_check','ip_check',
  'max_autologin_time','rand_seed','rand_seed_last_update','session_length')"))
{
  while($row = $DB->fetch_array($getconfig))
  {
    $phpbb_config[$row['config_name']] = $row['config_value'];
  }
  $DB->free_result($getconfig);
  unset($getconfig);
}

// ########## Get the group id's for admins and guests usergroups: #############
$get_admin_id = $DB->query_first('SELECT group_id FROM ' . $usersystem['tblprefix'] . "groups WHERE group_name = 'ADMINISTRATORS'");
$BB_ADMIN_GID = isset($get_admin_id[0]) ? (int)$get_admin_id[0] : null;

$get_guests_id = $DB->query_first('SELECT group_id FROM ' . $tableprefix . "groups WHERE group_name = 'GUESTS'");
$GUEST_ID = (int)$get_guests_id[0];

unset($get_admin_id, $get_guests_id);


$cookiesecure = empty($phpbb_config['cookie_secure']) ? 0 : 1;
$cookietime   = $usersystem['cookietimeout'];
$cookieprefix = $usersystem['cookieprefix'];
$cookiedomain = isset($usersystem['cookiedomain']) ? $usersystem['cookiedomain'] : '';
$cookiepath   = isset($usersystem['cookiepath'])   ? $usersystem['cookiepath'] : "/";

$forumdbname  = $usersystem['dbname'];

// ################################# USER IP ###################################

$userip = ( !empty($_SERVER['REMOTE_ADDR']) ) ? $_SERVER['REMOTE_ADDR'] : ( ( !empty($_ENV['REMOTE_ADDR']) ) ? $_ENV['REMOTE_ADDR'] : getenv('REMOTE_ADDR') );
$userip = addslashes(htmlspecialchars($userip));

// ################################## DEFINES ##################################

define('SESSION_HOST', $userip);

// ########################### SPECIAL INCLUDE CHECK ###########################
if(!defined('SD_PHPBB3_INCLUDED'))
{
  define('SD_PHPBB3_INCLUDED', true);

  if(!defined('ANONYMOUS'))
  {
    define('ANONYMOUS', 1);
  }
  define('USER_AGENT', substr((!empty($_SERVER['HTTP_USER_AGENT'])) ? htmlspecialchars((string) $_SERVER['HTTP_USER_AGENT']) : '', 0, 150));
  $phpbb_forwarded_for = (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) ? (string) $_SERVER['HTTP_X_FORWARDED_FOR'] : '';

  define('USER_NORMAL',   0);
  define('USER_INACTIVE', 1);
  define('USER_IGNORE',   2);
  define('USER_FOUNDER',  3);

  // ############################## USER FUNCTIONS ##############################

  function IsIPBanned($clientip)
  {
    global $DB, $usersystem;

    $prevDB = $DB->database;
    if($DB->database != $usersystem['dbname']) $DB->select_db($usersystem['dbname']);

    $user_ip_parts = null;

    preg_match('/(..)(..)(..)(..)/', $clientip, $user_ip_parts);

    $query = 'SELECT ban_ip FROM ' . $usersystem['tblprefix'] . 'banlist
      WHERE ((ban_end >= ' . TIME_NOW . ") OR (ban_end = 0))
      AND ban_ip IN ('" . $user_ip_parts[1] . $user_ip_parts[2] . $user_ip_parts[3] . $user_ip_parts[4] . "', '" .
      $user_ip_parts[1] . $user_ip_parts[2] . $user_ip_parts[3] . "ff', '" .
      $user_ip_parts[1] . $user_ip_parts[2] . "ffff', '" .
      $user_ip_parts[1] . "ffffff')";

    $getbanip = $DB->query($query);

    if($DB->database != $prevDB) $DB->select_db($prevDB);

    if($banip = $DB->fetch_array($getbanip))
    {
      return !empty($banip['ban_ip']);
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
  function ForumLink($linkType, $userid = ANONYMOUS)
  {
    global $DB, $sdurl, $usersystem;

    $url = '';
    switch($linkType)
    {
      case 1:
        $url = 'ucp.php?mode=register';
        break;
      case 2:
        $url = 'ucp.php';
        break;
      case 3:
        $url = 'ucp.php?mode=sendpassword';
        break;
      case 4:
        $url = 'memberlist.php?mode=viewprofile&amp;u=' . $userid;
        break;
      case 5:
        $url = 'ucp.php?i=pm&amp;mode=compose&amp;u=' . $userid;
        break;
    }

    return strlen($url) ? $sdurl . $usersystem['folderpath'] . $url : '';

  } //ForumLink


  function ForumAvatar($userid, $username)
  {
    global $DB, $dbname, $usersystem, $sdurl;

    if(empty($userid) && (!isset($username) || !strlen($username)))
    {
      return '';
    }
    $avatar = '';
    $prevDB = $DB->database;
    // forum information
    $forumdbname = $usersystem['dbname'];
    $forumpath   = $usersystem['folderpath'];
    $tableprefix = $usersystem['tblprefix'];

    // switch to forum database
    if($DB->database != $forumdbname) $DB->select_db($forumdbname);

    // get avatar settings
    $config = array();
    $avatarpath        = '';
    $avatargallerypath = '';
    if($getconf = $DB->query('SELECT config_name, config_value FROM ' . $tableprefix .
                             'config WHERE config_name IN (\'avatar_path\',\'avatar_gallery_path\')'))
    {
      while($row = $DB->fetch_array($getconf))
      {
        $config[$row['config_name']] = $row['config_value'];
      }
      $avatarpath        = @$config['avatar_path'];
      $avatargallerypath = @$config['avatar_gallery_path'];
    }

    $useravatar_sql = 'SELECT user_avatar, user_avatar_type, user_avatar_width, user_avatar_height
                       FROM '.$tableprefix.'users WHERE ';
    if($userid > ANONYMOUS)
    {
      $useravatar = $DB->query_first($useravatar_sql.'user_id = %d', $userid);
    }
    else
    {
      $useravatar = $DB->query_first($useravatar_sql."username = '%s'", $username);
    }
    if($DB->database != $prevDB) $DB->select_db($prevDB);

    $avatar = '';
    if(strlen($useravatar['user_avatar']))
    {
      $avatar = '<img border="0" alt="avatar" width="' . $useravatar['user_avatar_width'] . '" height="' . $useravatar['user_avatar_height'].'" ';
      switch($useravatar['user_avatar_type'])
      {
        case 1:  // uploaded avatar
        $avatar .= 'src="' . $sdurl . $forumpath . 'download/file.php?avatar=' . $useravatar['user_avatar'] . '" />';
        break;

        case 2:  // an external link to an avatar (offsite)
        $avatar .= 'src="' . $useravatar['user_avatar'] . '" />';
        break;

        case 3:  // uploaded avatar in the avatars -> gallery folder
        $avatar .= 'src="' . $sdurl . $forumpath . $avatargallerypath . '/' . $useravatar['user_avatar'] . '" />';
        break;

        default:
        $avatar = '';
      }
    }

    return $avatar;

  } //ForumAvatar


  if(!function_exists('sd_InitForumPermissions')) // may be defined in admin
  {
  function sd_InitForumPermissions($userdata)
  {
    global $DB, $dbname, $sdurl, $usersystem;
    static $acl_options, $acl;

    if(isset($acl) && isset($acl_options))
    {
      return array($acl_options,$acl);
    }

    $forumdbname = $usersystem['dbname'];
    $tableprefix = $usersystem['tblprefix'];

    $prevDB = $DB->database;
    if($DB->database != $forumdbname) $DB->select_db($forumdbname);

    $sql = 'SELECT auth_option, is_global, is_local FROM ' . $tableprefix . 'acl_options'.
           ' ORDER BY auth_option_id';
    if($result = $DB->query($sql))
    {
      $global = $local = 0;
      $acl_options = $acl = array();
      while ($row = $DB->fetch_array($result))
      {
        if ($row['is_global'])
        {
          $acl_options['global'][$row['auth_option']] = $global++;
        }

        if ($row['is_local'])
        {
          $acl_options['local'][$row['auth_option']] = $local++;
        }
      }
      if(!empty($acl_options))
      {
        $DB->free_result($result);
      }
    }
    if($DB->database != $prevDB) $DB->select_db($prevDB); //SD342: use $prevDB

    if(isset($userdata['user_permissions']))
    {
      $user_permissions = explode("\n", $userdata['user_permissions']);
      foreach ($user_permissions as $f => $seq)
      {
        if ($seq)
        {
          $i = 0;

          if (!isset($acl[$f]))
          {
            $acl[$f] = '';
          }

          while ($subseq = substr($seq, $i, 6))
          {
            // We put the original bitstring into the acl array
            $acl[$f] .= str_pad(base_convert($subseq, 36, 2), 31, 0, STR_PAD_LEFT);
            $i += 6;
          }
        }
      }
    }

    return array($acl_options, $acl);

  } //sd_InitForumPermissions
  }

  if(!function_exists('sd_GetForumPermissions')) // may be defined in admin
  {
  function sd_GetForumPermissions($f,$opt)
  {
    global $DB, $userinfo;

    list($acl_options,$acl) = sd_InitForumPermissions($userinfo);
    $cache[$f][$opt] = false;

    // Is this option a global permission setting?
    if (isset($acl_options['global'][$opt]))
    {
      if (isset($acl[0]))
      {
        $cache[$f][$opt] = $acl[0][$acl_options['global'][$opt]];
      }
    }

    // Is this option a local permission setting?
    // But if we check for a global option only, we won't combine the options...
    if(($f > 0) && isset($acl_options['local'][$opt]))
    {
      if (isset($acl[$f]) && isset($acl[$f][$acl_options['local'][$opt]]))
      {
        $cache[$f][$opt] |= $acl[$f][$acl_options['local'][$opt]];
/*                  
# FOR DEBUGGING ONLY!
if(IPADDRESS == 'xxx')
{
  echo '<pre>$opt: '.$opt.'</pre>';
  echo '<pre>$acl-$opt: '.$acl[$f][$acl_options['local'][$opt]].'</pre>';
  echo '<pre>$cache[$f][$opt]: '.$cache[$f][$opt].'</pre>';
}
*/
      }
    }

    return $cache[$f][$opt];

  } //sd_GetForumPermissions
  }

  // ############################### PORTABLE HASH ###############################

  function phpbb3_hash($password)
  {
    $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    $random_state = unique_id();
    $random = '';
    $count = 6;

    if(($fh = @fopen('/dev/urandom', 'rb')))
    {
      $random = fread($fh, $count);
      fclose($fh);
    }

    if (strlen($random) < $count)
    {
      $random = '';
      for ($i = 0; $i < $count; $i += 16)
      {
        $random_state = md5(unique_id() . $random_state);
        $random .= pack('H*', md5($random_state));
      }
      $random = substr($random, 0, $count);
    }

    $hash = _hash3_crypt_private($password, _hash_gensalt_private($random, $itoa64), $itoa64);

    if (strlen($hash) == 34)
    {
      return $hash;
    }

    return md5($password);
  }

  /**
  * Check for correct password
  */
  function phpbb3_check_hash($password, $hash)
  {
      $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
      if (strlen($hash) == 34)
      {
          return (_hash3_crypt_private($password, $hash, $itoa64) === $hash) ? true : false;
      }

      return (md5($password) === $hash) ? true : false;
  }

  /**
  * Generate salt for hash generation
  */
  function _hash3_gensalt_private($input, &$itoa64, $iteration_count_log2 = 6)
  {
      if ($iteration_count_log2 < 4 || $iteration_count_log2 > 31)
      {
          $iteration_count_log2 = 8;
      }

      $output = '$H$';
      $output .= $itoa64[min($iteration_count_log2 + ((PHP_VERSION >= 5) ? 5 : 3), 30)];
      $output .= _hash3_encode64($input, 6, $itoa64);

      return $output;
  }

  /**
  * Encode hash
  */
  function _hash3_encode64($input, $count, &$itoa64)
  {
      $output = '';
      $i = 0;

      do
      {
          $value = ord($input[$i++]);
          $output .= $itoa64[$value & 0x3f];

          if ($i < $count)
          {
              $value |= ord($input[$i]) << 8;
          }

          $output .= $itoa64[($value >> 6) & 0x3f];

          if ($i++ >= $count)
          {
              break;
          }

          if ($i < $count)
          {
              $value |= ord($input[$i]) << 16;
          }

          $output .= $itoa64[($value >> 12) & 0x3f];

          if ($i++ >= $count)
          {
              break;
          }

          $output .= $itoa64[($value >> 18) & 0x3f];
      }
      while ($i < $count);

      return $output;
  }

  /**
  * The crypt function/replacement
  */
  function _hash3_crypt_private($password, $setting, &$itoa64)
  {
      $output = '*';

      // Check for correct hash
      if (substr($setting, 0, 3) != '$H$')
      {
          return $output;
      }

      $count_log2 = strpos($itoa64, $setting[3]);

      if ($count_log2 < 7 || $count_log2 > 30)
      {
          return $output;
      }

      $count = 1 << $count_log2;
      $salt = substr($setting, 4, 8);

      if (strlen($salt) != 8)
      {
          return $output;
      }

      /**
      * We're kind of forced to use MD5 here since it's the only
      * cryptographic primitive available in all versions of PHP
      * currently in use.  To implement our own low-level crypto
      * in PHP would result in much worse performance and
      * consequently in lower iteration counts and hashes that are
      * quicker to crack (by non-PHP code).
      */
      if (PHP_VERSION >= 5)
      {
          $hash = md5($salt . $password, true);
          do
          {
              $hash = md5($hash . $password, true);
          }
          while (--$count);
      }
      else
      {
          $hash = pack('H*', md5($salt . $password));
          do
          {
              $hash = pack('H*', md5($hash . $password));
          }
          while (--$count);
      }

      $output = substr($setting, 0, 12);
      $output .= _hash3_encode64($hash, 16, $itoa64);

      return $output;
  }

  // ############################### UNIQUE ID ###################################

  function phpbb3_unique_id($extra = 'c') // phpBB3!
  {
    //static $dss_seeded = false;
    global $phpbb_config;

    $val = $phpbb_config['rand_seed'] . microtime();
    $val = md5($val);
    $phpbb_config['rand_seed'] = md5($phpbb_config['rand_seed'] . $val . $extra);

    /* ???
    if ($dss_seeded !== true && ($phpbb_config['rand_seed_last_update'] < time() - rand(1,10)))
    {
      set_config('rand_seed', $phpbb_config['rand_seed'], true);
      set_config('rand_seed_last_update', time(), true);
      $dss_seeded = true;
    }
    */

    return substr($val, 4, 16);
  } //phpbb3_unique_id

  function short3_ipv6($ip, $length) // phpBB3!
  {
    if ($length < 1)
    {
      return '';
    }

    // extend IPv6 addresses
    $blocks = substr_count($ip, ':') + 1;
    if ($blocks < 9)
    {
      $ip = str_replace('::', ':' . str_repeat('0000:', 9 - $blocks), $ip);
    }
    if ($ip[0] == ':')
    {
      $ip = '0000' . $ip;
    }
    if ($length < 4)
    {
      $ip = implode(':', array_slice(explode(':', $ip), 0, 1 + $length));
    }

    return $ip;
  }


  // ############################ CreateAutoLoginID ##############################

  function CreateAutoLoginID($userid, $session_id, $autologinid = '')
  {
    global $DB, $tableprefix, $phpbb_config;

    $auto_login_key = phpbb3_unique_id(hexdec(substr($session_id, 0, 8)));

    if(!empty($autologinid))
    {
      $DB->query('UPDATE '. $tableprefix . "sessions_keys
                  SET key_id = '%s', last_ip = '%s', last_login = %d
                  WHERE key_id = '%s'",
                  md5($auto_login_key), SESSION_HOST, TIME_NOW, md5($autologinid));
    }
    else
    {
      $expire = TIME_NOW - (empty($phpbb_config['max_autologin_time']) ? 31536000 : (86400 * (int)$phpbb_config['max_autologin_time']));
      $DB->query('DELETE FROM '. $tableprefix . 'sessions_keys WHERE (user_id = %d)
        AND (last_ip = \'%s\') AND (last_login < %d)',
        $userid, SESSION_HOST, $expire);
      $DB->query('INSERT INTO '. $tableprefix . "sessions_keys (key_id, user_id, last_ip, last_login)
        VALUES ('%s', %d, '%s', %d)",
        md5($auto_login_key), $userid, SESSION_HOST, TIME_NOW);
    }
    sdsetcookie('_k', $auto_login_key);

    return $auto_login_key;

  } //CreateAutoLoginID


  // ############################## DeleteSession ################################

  function DeleteSession($sessionid, $autologinid = null)
  {
    global $DB, $tableprefix;

    if(!empty($autologinid))
    {
      $DB->query('DELETE FROM '. $tableprefix . 'sessions_keys WHERE key_id = \'%s\'', md5($autologinid));
    }
    else
    {
      $userid = $DB->query_first('SELECT session_user_id FROM ' . $tableprefix . 'sessions WHERE session_id = \'%s\'', $sessionid);
      if(!empty($userid))
      {
        $DB->query('DELETE FROM '. $tableprefix . 'sessions_keys WHERE user_id = %d AND last_ip = \'%s\'',
          $userid[0], SESSION_HOST);
      }
    }
    $DB->query('DELETE FROM ' . $tableprefix . 'sessions WHERE session_id = \'%s\'', $sessionid);
    $DB->query('DELETE FROM ' . $tableprefix . "sessions WHERE session_user_id = 1
      AND session_browser = '%s' AND session_ip = '%s'",
      $DB->escape_string(USER_AGENT), SESSION_HOST);

  } //DeleteSession


  // ############################### SD SET COOKIE ###############################

  function sdsetcookie($name, $value = '', $permanent = 1)
  {
    global $cookieprefix, $cookiesecure, $cookiepath, $cookiedomain, $phpbb_config;

    $expire = TIME_NOW;
    if(!empty($permanent))
    {
      $expire += empty($phpbb_config['max_autologin_time']) ? 31536000 : (86400 * (int)$phpbb_config['max_autologin_time']);
    }
    $name = $cookieprefix . $name;

    if(!strlen($cookiedomain) && ((substr_count($_SERVER['HTTP_HOST'], '.') > 1) &&
       (substr($_SERVER['HTTP_HOST'],0,3) != 'www') && ($_SERVER['HTTP_HOST'] != '127.0.0.1')))
    {
      $cookiedomain = '.'.$_SERVER['HTTP_HOST'];
    }
    setcookie($name, $value, $expire, $cookiepath, $cookiedomain, $cookiesecure);
  }

  // ############################ CREATE SESSION HASH ############################

  function CreateSessionHash()
  {
    return md5(uniqid(SESSION_HOST, true));
  }

  // ############################## CREATE SESSION ###############################

  if(!function_exists('CreateSession')) // may be defined in admin
  {
  function CreateSession($userid = ANONYMOUS, $user_session_time = -1, $autologin = 0, $autologinid = '')
  {
    global $DB, $sessioncreated, $cookieprefix, $tableprefix;

    $userid = (int) $userid;

    $loggedin = $userid > ANONYMOUS ? 1 : 0;  // phpbb3 users start from id 2 (guest=1!)
    $autologinid = ($loggedin && !empty($autologin)?$autologinid:'');
    // setup the session
    $session = array(
      'session_id'          => CreateSessionHash(),
      'session_user_id'     => $userid,
      'session_browser'     => USER_AGENT,
      'session_start'       => TIME_NOW,
      'session_time'        => TIME_NOW,
      'session_ip'          => SESSION_HOST,
      'session_page'        => 0,
      'session_viewonline'  => $loggedin,
      'session_autologinid' => '',
      'session_forward'     => '');

    // insert the session into the database
    $DB->query('REPLACE INTO ' . $tableprefix . "sessions
      (session_id, session_user_id, session_start, session_time, session_last_visit,
       session_ip, session_browser, session_page, session_viewonline,
       session_autologin, session_admin)
      VALUES ('%s', %d, %d, %d, %d, '%s', '%s', %d, %d, %d, %d)",
      $session['session_id'], $session['session_user_id'], $session['session_start'], $session['session_time'], $session['session_start'],
      $session['session_ip'],
      $DB->escape_string(USER_AGENT),
      $session['session_page'], $session['session_viewonline'],
      $autologin, 0);

    // update last activity
    if($userid > ANONYMOUS)
    {
      $last_visit = (!empty($user_session_time) && ($user_session_time > 0)) ? (int)$user_session_time : TIME_NOW;
      $DB->query("UPDATE ".$tableprefix."users SET user_lastvisit = %d WHERE user_id = %d", $last_visit, $userid);

      if($autologin)
      {
        $session['session_autologinid'] = CreateAutoLoginID($userid, $session['session_id'], $autologinid);
      }
    }

    // return if we are logging in our logging out (since logging in and out already creates sessions)
    if(!isset($_POST['login']) && !isset($_GET['logout']))
    {
      // save the session id
      sdsetcookie('_sid', $session['session_id']);
      sdsetcookie('_u', $userid);
      sdsetcookie('_k', '');
    }

    // set sessioncreated to true so that we don't update this session later on in the script
    // (because it was just created)
    $GLOBALS['sessioncreated'] = true;

    return $session;

  } //CreateSession
  }
} // special include check end


// ############################# FIND SESSION HASH #############################

$cn = $cookieprefix . '_u';
$cookie_data_u = isset($_COOKIE[$cn])?$_COOKIE[$cn]:'';
$cn = $cookieprefix . '_k';
$cookie_data_k = isset($_COOKIE[$cn])?$_COOKIE[$cn]:'';

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
  $sessionhash = '';
  if (isset($_COOKIE[$cookieprefix . '_sid']) || isset($_COOKIE[$cookieprefix . '_u']))
  {
    $cn = $cookieprefix . '_sid';
    $sessionhash = isset($_COOKIE[$cn])?$_COOKIE[$cn]:'';
  }
}
unset($cn);


// ############################# CONTINUE SESSION ##############################

// if the forwarded for header shall be checked we have to validate its contents
if(!empty($phpbb_config['forwarded_for_check']) && isset($session['forwarded_for']))
{
  $session['forwarded_for'] = preg_replace('#, +#', ', ', $session['forwarded_for']);

  // Whoa these look impressive!
  // The code to generate the following two regular expressions which match valid IPv4/IPv6 addresses
  // can be found in phpBB3's develop directory
  $ipv4 = '#^(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$#';
  $ipv6 = '#^(?:(?:(?:[\dA-F]{1,4}:){6}(?:[\dA-F]{1,4}:[\dA-F]{1,4}|(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])))|(?:::(?:[\dA-F]{1,4}:){5}(?:[\dA-F]{1,4}:[\dA-F]{1,4}|(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])))|(?:(?:[\dA-F]{1,4}:):(?:[\dA-F]{1,4}:){4}(?:[\dA-F]{1,4}:[\dA-F]{1,4}|(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])))|(?:(?:[\dA-F]{1,4}:){1,2}:(?:[\dA-F]{1,4}:){3}(?:[\dA-F]{1,4}:[\dA-F]{1,4}|(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])))|(?:(?:[\dA-F]{1,4}:){1,3}:(?:[\dA-F]{1,4}:){2}(?:[\dA-F]{1,4}:[\dA-F]{1,4}|(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])))|(?:(?:[\dA-F]{1,4}:){1,4}:(?:[\dA-F]{1,4}:)(?:[\dA-F]{1,4}:[\dA-F]{1,4}|(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])))|(?:(?:[\dA-F]{1,4}:){1,5}:(?:[\dA-F]{1,4}:[\dA-F]{1,4}|(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])))|(?:(?:[\dA-F]{1,4}:){1,6}:[\dA-F]{1,4})|(?:(?:[\dA-F]{1,4}:){1,7}:))$#i';

  // split the list of IPs
  $ips = explode(', ', $session['forwarded_for']);
  foreach ($ips as $ip)
  {
    // check IPv4 first, the IPv6 is hopefully only going to be used very seldomly
    if (!empty($ip) && !preg_match($ipv4, $ip) && !preg_match($ipv6, $ip))
    {
      // contains invalid data, don't use the forwarded for header
      $session['forwarded_for'] = '';
      break;
    }
  }
}

// switch to forum database
if($dbname != $forumdbname) $DB->select_db($forumdbname);

if(empty($sessionhash))
{
  $cookie_data_u = '0';
  $cookie_data_k = '';
  sdsetcookie('_u', 0, 0);
  sdsetcookie('_k', '', 0);
}
else
{
  $sql = 'SELECT u.*, s.* FROM ' . $tableprefix. 'sessions s, ' . $tableprefix. "users u".
         " WHERE s.session_id = '" . $DB->escape_string($sessionhash) .
         "' AND u.user_id = s.session_user_id";
  $session = $DB->query_first($sql);
  // Did the session exist in the DB?
  if(isset($session['user_id']))
  {
    if (strpos($userip, ':') !== false && strpos($session['session_ip'], ':') !== false)
    {
      $s_ip = short3_ipv6($session['session_ip'], $phpbb_config['ip_check']);
      $u_ip = short3_ipv6($userip, $phpbb_config['ip_check']);
    }
    else
    {
      $s_ip = implode('.', array_slice(explode('.', $session['session_ip']), 0, $phpbb_config['ip_check']));
      $u_ip = implode('.', array_slice(explode('.', $userip), 0, $phpbb_config['ip_check']));
    }
    $s_browser = ($phpbb_config['browser_check']) ? strtolower(substr($session['session_browser'], 0, 150)) : '';
    $u_browser = ($phpbb_config['browser_check']) ? strtolower(USER_AGENT) : '';

    $session['forwarded_for'] = ((!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) ? (string) $_SERVER['HTTP_X_FORWARDED_FOR'] : '');
    $s_forwarded_for = ($phpbb_config['forwarded_for_check']) ? substr($session['session_forwarded_for'], 0, 255) : '';
    $u_forwarded_for = ($phpbb_config['forwarded_for_check']) ? substr($session['forwarded_for'], 0, 255) : '';

    if ($u_ip === $s_ip && $s_browser === $u_browser && $s_forwarded_for === $u_forwarded_for)
    {
      $session_expired = false;
      // Check the session length timeframe if autologin is not enabled.
      // Else check the autologin length... and also removing those having
      // autologin enabled but no longer allowed board-wide.
      if(empty($cookie_data_k))
      {
        if ($session['session_time'] < (TIME_NOW - ($phpbb_config['session_length'] + 60)))
        {
          $session_expired = true;
        }
      }
      else if (!$phpbb_config['allow_autologin'] || ($phpbb_config['max_autologin_time'] && ($session['session_time'] < (TIME_NOW - (86400 * (int) $phpbb_config['max_autologin_time']) + 60))))
      {
        $session_expired = true;
      }

      if (!$session_expired)
      {
        // Only update session DB a minute or so after last update or if page changes
        if ((TIME_NOW - $session['session_time']) > 60)
        {
          $sql = 'UPDATE '.$tableprefix.'sessions SET session_time = ' . TIME_NOW .
            " WHERE session_id = '" . $DB->escape_string($sessionhash) . "'";
          $DB->query($sql);
        }

        $session['is_registered'] = ($session['user_id'] != ANONYMOUS && ($session['user_type'] == USER_NORMAL || $session['user_type'] == USER_FOUNDER)) ? true : false;
        $session['is_bot'] = (!$session['is_registered'] && $session['user_id'] != ANONYMOUS) ? true : false;

        unset($session_expired);
      }
    }

  } //user_id set
  else
  {
    unset($session);
  }
}

// ############################### COOKIE LOGIN ################################

if(empty($session) || empty($session['session_user_id']))
{
  // session is not set or the user is a guest, lets check the cookies:

  if(isset($_COOKIE[$cookieprefix . '_data']))
  {
    $sessiondata = sd_unhtmlspecialchars($_COOKIE[$cookieprefix . '_data']);
    $sessiondata = unserialize(stripslashes($sessiondata));
    if (!$phpbb_config['allow_autologin'])
    {
      $cookie_data_k = '';
    }

    // ??? add BOT check here (session.php, 360ff)

    if(!empty($sessiondata['userid']) && is_numeric($sessiondata['userid']) &&
       !empty($cookie_data_k))
    {
      $sql = 'SELECT u.*
        FROM '.$tableprefix.'users u, '.$tableprefix.'sessions_keys k
        WHERE u.user_id = ' . (int) $sessiondata['userid'] . '
          AND u.user_type IN (' . USER_NORMAL . ', ' . USER_FOUNDER . ")
          AND k.user_id = u.user_id
          AND k.key_id = '" . $DB->escape_string(md5($cookie_data_k)) . "'";

      if($user = $DB->query_first($sql))
      {
        // combination is valid
        if(!empty($session['session_id']))
        {
          // old session still exists; kill it
          DeleteSession($sessiondata['session_id'], (strlen($cookie_data_k) ? $cookie_data_k : null));
        }

        $session = CreateSession($user['user_id'], -1, $phpbb_config['allow_autologin'], $cookie_data_k);
        $cn = $cookieprefix . '_u';
        $cookie_data_u = isset($_COOKIE[$cn])?$_COOKIE[$cn]:'';
        $cn = $cookieprefix . '_k';
        $cookie_data_k = isset($_COOKIE[$cn])?$_COOKIE[$cn]:'';

      }
      else if(isset($_POST['login'])) // cookie is bad!
      {
        // cookie's bad and since we're not doing anything login related, kill the bad cookie
        sdsetcookie('_data', '', 1);
        sdsetcookie('_sid', '', 0);
        sdsetcookie('_u', '0', 0);
        sdsetcookie('_k', '', 0);
      }
      unset($user);
    }
  }
}


// ########################### CREATE GUEST SESSION ############################

if(empty($session))
{
  // still no session, create a guest session
  $session = CreateSession(ANONYMOUS);
  $cn = $cookieprefix . '_u';
  $cookie_data_u = isset($_COOKIE[$cn])?$_COOKIE[$cn]:'';
  $cn = $cookieprefix . '_k';
  $cookie_data_k = isset($_COOKIE[$cn])?$_COOKIE[$cn]:'';
}


// ############################ SETUP USER VARIABLE ############################

if((int)$session['session_user_id'] <= 1)
{
  $user = array('userid'         => ANONYMOUS, // phpBB3 guests are "1" userid
                'usergroupid'    => $GUEST_ID,  // phpBB3 - "GUESTS"
                'usergroupids'   => array($GUEST_ID),
                'username'       => '',
                'displayname'    => '',
                'loggedin'       => 0,
                'email'          => '',
                'timezoneoffset' => 0,
                'dateformat'     => '',
                'dstonoff'       => 0,
                'dstauto'        => 1);
  if($getuser = $DB->query_first('SELECT u.user_permissions FROM ' . $tableprefix . 'users u WHERE u.user_id = 1'))
  {
    $user['user_permissions'] = $getuser['user_permissions'];
  }

}
else
{
  $getuser = $DB->query_first('SELECT u.user_id, u.group_id, u.username, u.user_email,
    u.user_timezone, u.user_dateformat, u.user_dst, u.user_permissions, g.group_name
    FROM ' . $tableprefix . 'users u, ' . $tableprefix . 'groups g
    WHERE (u.user_id = %d) AND (u.group_id = g.group_id)',
    (int)$session['session_user_id']);

  // If user was not found and/or his primary group no longer
  // exists, treat the user as GUEST:
  if(empty($getuser) || empty($getuser['user_id']) || ($getuser['group_name'] == 'BOTS') || ($getuser['group_name'] == 'GUESTS'))
  {
    $user = array('userid'         => ANONYMOUS, // phpBB3 guests are "1" userid
                  'usergroupid'    => $GUEST_ID,  // phpBB3 - "GUESTS"
                  'usergroupids'   => array($GUEST_ID),
                  'username'       => '',
                  'displayname'    => '',
                  'loggedin'       => 0,
                  'email'          => '',
                  'timezoneoffset' => 0,
                  'dateformat'     => '',
                  'dstonoff'       => 0,
                  'dstauto'        => 1,
                  'sessionurl'     => '',
                  'user_permissions' => '');
    if($getuser = $DB->query_first('SELECT u.user_permissions
      FROM ' . $tableprefix . 'users u WHERE u.user_id = 1'))
    {
      $user['user_permissions'] = $getuser['user_permissions'];
    }
  }
  else
  {
    $user = array('userid'         => $getuser['user_id'],
                  'usergroupid'    => $getuser['group_id'],
                  'usergroupids'   => array($getuser['group_id']),
                  'username'       => $getuser['username'],
                  'displayname'    => '',
                  'loggedin'       => 1,
                  'email'          => $getuser['user_email'],
                  'timezoneoffset' => $getuser['user_timezone'],
                  'dateformat'     => $getuser['user_dateformat'],
                  'dstonoff'       => $getuser['user_dst'],
                  'dstauto'        => 0,
                  'sessionurl'     => '',
                  'user_permissions' => $getuser['user_permissions']);

    unset($getuser);

    // find the usergroupids
    $usergroupids = array();

    // Check for secondary, non-guests usergroups of user
    if($getusergroups = $DB->query('SELECT g.group_id, g.group_name
      FROM ' . $tableprefix . 'groups g, ' . $tableprefix . "user_group u
      WHERE u.user_id = %d AND u.group_id = g.group_id
      AND (g.group_name <> 'BOTS')",
      $user['userid']))
    {
      if($DB->get_num_rows($getusergroups) > 0)
      {
        while($usergroup = $DB->fetch_array($getusergroups))
        {
          $usergroupids[] = $usergroup['group_id'];
        }
        $usergroupids = array_unique($usergroupids);
        $DB->free_result($getusergroups);
      }
    }
    unset($getusergroups,$usergroup);
  }

  // Check for "Full Admin" (4) permission role in phpBB3:
  $getrole = $DB->query_first('SELECT user_id FROM ' . $usersystem['tblprefix'] . 'acl_users
                               WHERE (user_id = %d) AND (forum_id = 0) AND (auth_role_id = 4)',
                               $user['userid']);
  if(isset($getrole['user_id']) && ($getrole['user_id'] == $user['userid']))
  {
    if(empty($user['usergroupids']) || (isset($BB_ADMIN_GID) && (false == array_search($BB_ADMIN_GID,$user['usergroupids']))))
    {
      $user['usergroupids'][] = $BB_ADMIN_GID; // add phpBB3's admin group
    }
  }

  if(empty($usergroupids))
  {
    // If none, default to SD "GUESTS" in phpBB3
    $user['usergroupids'] = array($GUEST_ID);
  }
  else
  {
    // If user is member of "Guests", than do not use any other usergroup:
    if(false !== array_search($GUEST_ID,$user['usergroupids'])) // Guest
    {
      $user['usergroupids'] = array($GUEST_ID);
    }
    else
    {
      natsort($usergroupids);
      $user['usergroupids'] = $usergroupids;
    }

    // If user is an admin (phpBB3 ADMIN=12), then update the session now:
    if(!empty($user['usergroupids']) && isset($BB_ADMIN_GID) && @in_array($BB_ADMIN_GID,$user['usergroupids']))
    {
      $DB->query('UPDATE ' . $tableprefix . 'sessions
        SET session_admin = 1, session_last_visit = %d
        WHERE session_id = \'%s\' AND session_user_id = %d',
        TIME_NOW, $session['session_id'], $session['session_user_id']);
    }
  }
  unset($usergroupids);
}


// ############################## UPDATE SESSION ###############################

if(!$sessioncreated)  // unless the session was just created....
{
  $DB->query('UPDATE ' . $tableprefix . 'sessions
    SET session_time = %d WHERE session_id = \'%s\'',
    TIME_NOW, $session['session_id']);
}


// ################################### LOGIN ###################################

if(isset($_POST['login']) && $_POST['login'] == 'login')
{
  // clean up login name as phpBB3 does it:
  $phpEx = 'php';
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
  $sd_ignore_watchdog = true; // important!
  $loginusername = utf8_clean_string($loginusername); // in utf_tools.php!
  $sd_ignore_watchdog = false;

  $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

  if(strlen($loginusername) != 0)
  {
    // get userid for given username
    $DB->result_type = MYSQL_ASSOC;
    if($getuser = $DB->query_first('SELECT u.*, b.ban_id
      FROM ' . $tableprefix . 'users u
      LEFT OUTER JOIN ' . $tableprefix . 'banlist b ON b.ban_userid = u.user_id
      WHERE u.username_clean = \'%s\'
      AND u.user_type IN (' . USER_NORMAL . ', ' . USER_FOUNDER . ')',
      $loginusername))
    {
      if(($getuser['ban_id'] > 0) || ($getuser['user_type'] == USER_INACTIVE) || ($getuser['user_type'] == USER_IGNORE))
      {
        $login_errors_arr[] = $sdlanguage['you_are_banned'];
      }
      else if(_hash3_crypt_private($loginpassword, $getuser['user_password'], $itoa64) === $getuser['user_password'] && empty($getuser['user_inactive_reason']) && ($getuser['user_id'] != ANONYMOUS))
      {
        // logged in
        $user = array('userid'         => $getuser['user_id'],
                      'usergroupid'    => $getuser['group_id'], // default usergroup
                      'usergroupids'   => array($getuser['group_id']),
                      'username'       => $getuser['username'],
                      'displayname'    => '',
                      'loggedin'       => 1,
                      'email'          => $getuser['user_email'],
                      'timezoneoffset' => $getuser['user_timezone'],
                      'dateformat'     => $getuser['user_dateformat'],
                      'dstonoff'       => $getuser['user_dst'],    // phpBB3 now has a dst option
                      'dstauto'        => 0,
                      'user_permissions' => $getuser['user_permissions']);

        $user_password = $getuser['user_password'];
        unset($getuser);

        // Check for secondary, non-guests usergroups of user
        $usergroupids = $user['usergroupids'];
        if($getusergroups = $DB->query('SELECT g.group_id, g.group_name
          FROM ' . $tableprefix . 'groups g, ' . $tableprefix . "user_group u
          WHERE (u.user_id = %d) AND (u.group_id = g.group_id)
          AND (g.group_name <> 'BOTS') AND (g.group_name <> 'GUESTS')",
          $user['userid']))
        {
          if($DB->get_num_rows($getusergroups) > 0)
          {
            while($usergroup = $DB->fetch_array($getusergroups))
            {
              // now find the usergroup name
              if(!@in_array($usergroup['group_id'],$usergroupids))
              {
                $usergroupids[] = $usergroup['group_id'];
              }
            }
            $DB->free_result($getusergroups);
          }
        }

        // Check for "Full Admin" (4) permission role in phpBB3:
        $getrole = $DB->query_first('SELECT user_id FROM ' . $usersystem['tblprefix'] . 'acl_users
          WHERE (user_id = %d) AND (forum_id = 0) AND (auth_role_id = 4)', $user['userid']);
        if(isset($getrole['user_id']) && ($getrole['user_id'] == $user['userid']))
        {
          if(empty($user['usergroupids']) || (isset($BB_ADMIN_GID) && (false == array_search($BB_ADMIN_GID,$user['usergroupids']))))
          {
            $user['usergroupids'][] = $BB_ADMIN_GID; // add phpBB3's admin group
          }
        }

        if(empty($usergroupids))
        {
          // If none, default to phpBB3 "Guests"
          $user['usergroupids'] = array($GUEST_ID);
        }
        else
        {
          // If user is member of "Guests", than do not use any other usergroup:
          if(false !== array_search($GUEST_ID,$user['usergroupids'])) // Guest
          {
            $user['usergroupids'] = array($GUEST_ID);
          }
          else
          {
            natsort($usergroupids);
            $user['usergroupids'] = $usergroupids;
          }
        }
        unset($usergroupids);

        // erase old session
        DeleteSession($session['session_id']);

        // save the password if the user has selected the 'remember me' option
        $autologin = (!empty($_POST['rememberme']) && $phpbb_config['allow_autologin'] ? 1 : 0);
        $session = CreateSession($user['userid'], -1, $autologin, '');
        $session['forwarded_for'] = isset($session['forwarded_for'])?$session['forwarded_for']:'';

        $sessiondata = array();
        $sessiondata['userid'] = $session['session_user_id'];
        $sessiondata['session_id'] = $session['session_id'];
        sdsetcookie('_data', serialize($sessiondata));
        sdsetcookie('_u', $user['userid']);
        sdsetcookie('_sid', $session['session_id']);

        // If user is an admin (phpBB3 ADMIN=12), then update the session now:
        if(!empty($user['usergroupids']) && isset($BB_ADMIN_GID) && @in_array($BB_ADMIN_GID,$user['usergroupids']))
        {
          $DB->query('UPDATE ' . $tableprefix . 'sessions
            SET session_admin = 1, session_last_visit = %d
            WHERE session_id = \'%s\' AND session_user_id = %d',
            TIME_NOW, $session['session_id'], $user['userid']);
        }

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

} //"Login"


// ################################## LOGOUT ###################################

if(isset($_GET['logout']))
{
  // clear cookies
  sdsetcookie('_u', '0', 1);
  sdsetcookie('_k', '', 1);
  sdsetcookie('_data', '', 1);
  sdsetcookie('_sid', '', 0);

  $user['userid'] = (int)$user['userid'];
  if($user['userid'] > ANONYMOUS)
  {
    $DB->query('UPDATE ' . $tableprefix . 'users SET user_lastvisit = %d WHERE user_id = %d', TIME_NOW, $user['userid']);

    // make sure any other of this user's sessions are deleted (in case they ended up with more than one)
    if(!empty($cookie_data_k))
    {
      $DB->query('DELETE FROM ' . $tableprefix . 'sessions_keys WHERE user_id = %d AND key_id = \'%s\'',
        $user['userid'], md5($cookie_data_k));
    }
    $DB->query('DELETE FROM ' . $tableprefix . 'sessions WHERE session_user_id = %d', $user['userid']);
  }

  DeleteSession($session['session_id'], $cookie_data_k);

  $session['session_id']    = CreateSessionHash();
  $session['forwarded_for'] = isset($session['forwarded_for'])?$session['forwarded_for']:'';

  // insert/replace the session in the database
  $DB->query('REPLACE INTO ' . $tableprefix . 'sessions
    (session_id, session_user_id, session_start, session_time, session_ip, session_browser,
    session_forwarded_for, session_page, session_viewonline, session_autologin, session_admin)
    VALUES (\'%s\',' . ANONYMOUS . ", %d, %d, '%s', '%s', '%s', 0, 0, 0, 0)",
    $session['session_id'], TIME_NOW, TIME_NOW, SESSION_HOST, $DB->escape_string(USER_AGENT), $session['forwarded_for']);

  sdsetcookie('_sid', $session['session_id'], 0);

  $user = array('userid'         => ANONYMOUS, // phpBB3 guests are "1" userid
                'usergroupid'    => $GUEST_ID, // phpBB3 - "GUESTS" usergroup
                'usergroupids'   => array($GUEST_ID),
                'username'       => '',
                'displayname'    => '',
                'loggedin'       => 0,
                'email'          => '',
                'timezoneoffset' => 0,
                'dateformat'     => '',
                'dstonoff'       => 0,
                'dstauto'        => 1,
                'user_permissions' => '');

} //"Logout"


// ############################ ADD SESSION TO URL? ############################

$stmp = isset($_SERVER['HTTP_USER_AGENT'])?(string)$_SERVER['HTTP_USER_AGENT']:'';
// MOD 3. Added bot user-agent strings - mongeron
if((!empty($_COOKIE)) OR empty($stmp) OR preg_match("#(Mozilla/4\.0 (compatible;)|NimbleCrawler|www.fi crawler|ICF_Site|ZyBorg|Virgo|OmniExplorer_Bot|Python-urllib|IRLbot|MJ12bot|WebStripper|Gigabot|WNMbot|Snapbot|LinkChecker|TurnitinBot|Exabot|Accoona-AI|nutch|kroolari|Twisted PageGetter|google|msnbot|yahoo! slurp)#si", $stmp))
// MOD 3. END
{
  $user['sessionurl'] = '';
}
else if (strlen($session['session_id']) > 0)
{
  $user['sessionurl'] = 's=' . $session['session_id'];
}
unset($stmp);

// ############################ DELETE OLD SESSIONS ############################

if(!empty($phpbb_config['session_length']))
{
  $DB->query('DELETE FROM ' . $tableprefix . 'sessions WHERE session_time < %d',
    (int)(TIME_NOW - $phpbb_config['session_length']));
}

// switch back to Subdreamer database
if($DB->database != $database['name']) $DB->select_db($database['name']);

// ###################### SUBDREAMER USER SETTINGS SETUP #######################

$usersettings = array('userid'         => ($user['userid'] == ANONYMOUS ? 0 : $user['userid']),
                      'usergroupid'    => $user['usergroupid'],
                      'usergroupids'   => $user['usergroupids'],
                      'username'       => $user['username'],
                      'displayname'    => $user['displayname'],
                      'loggedin'       => $user['loggedin'],
                      'email'          => $user['email'],
                      'timezoneoffset' => $user['timezoneoffset'],
                      'dateformat'     => $user['dateformat'],
                      'dstonoff'       => $user['dstonoff'],
                      'dstauto'        => $user['dstauto'],
                      'sessionurl'     => $user['sessionurl'],
                      'user_permissions' => $user['user_permissions']);

// cleanup unused variables
unset($user, $sessiondata, $session, $sessionhash, $phpbb_config, $cn,
      $cookie_data_u,$cookie_data_k, $loginusername, $loginusername,
      $phpbb_root_path, $userip, $userip, $sql, $u_ip,
      $s_browser, $u_browser, $s_forwarded_for, $u_forwarded_for, $itoa64);
