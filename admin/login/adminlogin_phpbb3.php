<?php

if(!defined('IN_PRGM')) return;

if(!defined('ANONYMOUS'))
{
  define('ANONYMOUS', 1); // phpBB2 = -1 ; phpBB3 = 1
}

// Get the actual GUESTS usergroup id:
if($DB->database != $usersystem['dbname']) $DB->select_db($usersystem['dbname']);

// ########## Get the group id's for admins and guests usergroups: #############
$get_admin_id = $DB->query_first('SELECT group_id FROM ' . $usersystem['tblprefix'] . "groups WHERE group_name = 'ADMINISTRATORS'");
$BB_ADMIN_GID = isset($get_admin_id[0]) ? (int)$get_admin_id[0] : null;

$get_guests_id = $DB->query_first("SELECT group_id FROM " . $usersystem['tblprefix'] . "groups WHERE group_name = 'GUESTS'");
$BB_GUEST_GID  = isset($get_guests_id[0]) ? (int)$get_guests_id[0] : null;

unset($get_admin_id, $get_guests_id);

// ############################### PORTABLE HASH ###############################

function phpbb_hash($password)
{
    $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    $random_state = unique_id();
    $random = '';
    $count = 6;

    if (($fh = @fopen('/dev/urandom', 'rb')))
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

    $hash = _hash_crypt_private($password, _hash_gensalt_private($random, $itoa64), $itoa64);

    if (strlen($hash) == 34)
    {
        return $hash;
    }

    return md5($password);
}

/**
* Check for correct password
*/
function phpbb_check_hash($password, $hash)
{
    $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    if (strlen($hash) == 34)
    {
        return (_hash_crypt_private($password, $hash, $itoa64) === $hash) ? true : false;
    }

    return (md5($password) === $hash) ? true : false;
}

/**
* Generate salt for hash generation
*/
function _hash_gensalt_private($input, &$itoa64, $iteration_count_log2 = 6)
{
    if ($iteration_count_log2 < 4 || $iteration_count_log2 > 31)
    {
        $iteration_count_log2 = 8;
    }

    $output = '$H$';
    $output .= $itoa64[min($iteration_count_log2 + ((PHP_VERSION >= 5) ? 5 : 3), 30)];
    $output .= _hash_encode64($input, 6, $itoa64);

    return $output;
}

/**
* Encode hash
*/
function _hash_encode64($input, $count, &$itoa64)
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
function _hash_crypt_private($password, $setting, &$itoa64)
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
    $output .= _hash_encode64($hash, 16, $itoa64);

    return $output;
}

// ############################# LOG USER ON ###################################

function LoginUser($loginusername, $loginpassword)
{
	global $DB, $sdlanguage, $usersystem, $BB_ADMIN_GID, $BB_GUEST_GID;

  $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
  if(strlen($loginusername))
	{
		// get userid for given username
		if($getuser = $DB->query_first('SELECT user_id, group_id, user_password,
      user_pass_convert, user_inactive_reason, username, user_email,
      user_timezone, user_dst, user_dateformat, user_permissions
      FROM ' . $usersystem['tblprefix'] . "users WHERE (username = '%s') AND (user_pass_convert = 0)",
      $loginusername))
		{
      if(_hash_crypt_private($loginpassword, $getuser['user_password'], $itoa64) === $getuser['user_password'] && empty($getuser['user_inactive_reason']) && ($getuser['user_id'] != ANONYMOUS))
			{
				// logged in
				$user = array(
          'userid'         => $getuser['user_id'],
          'loggedin'       => 1,
          'usergroupid'    => $getuser['group_id'],
					'usergroupids'   => array($getuser['group_id']),
          'username'       => $getuser['username'],
					'displayname'    => '',
					'email'          => $getuser['user_email'],
					'timezoneoffset' => $getuser['user_timezone'],
          'dstonoff'       => $getuser['user_dst'],
					'dstauto'        => 0,
          'dateformat'     => $getuser['user_dateformat'],
					'sessionurl'     => '',
          'user_permissions' => $getuser['user_permissions']);

        // Check for secondary, non-guests usergroups of user
        if($getusergroups = $DB->query('SELECT g.group_id
          FROM ' . $usersystem['tblprefix'] . 'groups g, ' . $usersystem['tblprefix'] . "user_group u
          WHERE u.user_id = %d AND u.group_id = g.group_id
          AND (g.group_type <> 1) AND (g.group_name <> 'BOTS')",
          $user['userid']))
        {
          if($DB->get_num_rows($getusergroups)>0)
          {
            while($usergroup = $DB->fetch_array($getusergroups))
            {
              // now find the usergroup name
              if(empty($user['usergroupids']) || !@in_array($usergroup['group_id'], $user['usergroupids']))
              {
                $user['usergroupids'][] = $usergroup['group_id'];
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

        if(empty($user['usergroupids']))
        {
          // If none, default to SD "GUESTS" in phpBB3
          $user['usergroupids'] = isset($BB_GUEST_GID) ? array($BB_GUEST_GID) : array();
        }
        else
        {
          // If user is member of "Guests", than do not use any other usergroup:
          if(isset($BB_GUEST_GID) && (false !== array_search($BB_GUEST_GID,$user['usergroupids']))) // Guests
          {
            $user['usergroupids'] = array($BB_GUEST_GID);
          }
          else
          {
            natsort($user['usergroupids']);
          }
        }
			}
			else
			{
				$login_errors_arr = $sdlanguage['wrong_password'];
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

	return isset($login_errors_arr) ? $login_errors_arr : $user;

} //LoginUser


// ############################ GET USER DETAILS ###############################


function GetUser($userid)
{
	global $DB, $usersystem, $BB_ADMIN_GID, $BB_GUEST_GID;

	$user = null;

	if($getuser = $DB->query_first("SELECT user_id, group_id, username, user_email,
    user_timezone, user_dst, user_dateformat, user_permissions
    FROM " . $usersystem['tblprefix'] . "users WHERE user_id = %d",
    $userid))
	{
		$user = array('userid'   => $getuser['user_id'],
            'loggedin'       => 1,
            'usergroupid'    => $getuser['group_id'],
            'usergroupids'   => array($getuser['group_id']),
					  'username'       => $getuser['username'],
					  'email'          => $getuser['user_email'],
					  'timezoneoffset' => $getuser['user_timezone'],
					  'dstonoff'       => $getuser['user_dst'],
					  'dstauto'        => 0,
            'dateformat'     => $getuser['user_dateformat'],
					  'sessionurl'     => '',
            'user_permissions' => $getuser['user_permissions']);

    // Check for secondary, non-guests usergroups of user
    if($getusergroups = $DB->query('SELECT g.group_id
      FROM ' . $usersystem['tblprefix'] . 'groups g, ' . $usersystem['tblprefix'] . "user_group u
      WHERE u.user_id = %d AND u.group_id = g.group_id
      AND (g.group_type <> 1) AND (g.group_name <> 'BOTS')",
      $user['userid']))
    {
      if($DB->get_num_rows($getusergroups)>0)
      {
        while($usergroup = $DB->fetch_array($getusergroups))
        {
          // now find the usergroup name
          if(empty($user['usergroupids']) || !@in_array($usergroup['group_id'], $user['usergroupids']))
          {
            $user['usergroupids'][] = $usergroup['group_id'];
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

    if(empty($user['usergroupids']))
    {
      // If none, default to SD "GUESTS" in phpBB3
      $user['usergroupids'] = isset($BB_GUEST_GID) ? array($BB_GUEST_GID) : array();
    }
    else
    {
      // If user is member of "Guests", than do not use any other usergroup:
      if(isset($BB_GUEST_GID) && (false !== array_search($BB_GUEST_GID,$user['usergroupids']))) // Guests
      {
        $user['usergroupids'] = array($BB_GUEST_GID);
      }
      else
      {
        natsort($user['usergroupids']);
      }
    }

  }

	return $user;

} //GetUser

if(!function_exists('sd_InitForumPermissions'))
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

  if($DB->database != $forumdbname)
  {
    $prevdb = $DB->database;
    $DB->select_db($forumdbname);
  }
  $sql = 'SELECT auth_option, is_global, is_local
    FROM ' . $tableprefix . 'acl_options
    ORDER BY auth_option_id';
  $result = $DB->query($sql);

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
  $DB->free_result($result);

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

  if(!empty($prevdb))
  {
    $DB->select_db($prevdb);
  }

  return array($acl_options, $acl);

} //sd_InitForumPermissions
}

if(!function_exists('sd_GetForumPermissions'))
{
function sd_GetForumPermissions($f,$opt)
{
  global $DB, $dbname, $sdurl, $usersystem, $userinfo;

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
  if ($f != 0 && isset($acl_options['local'][$opt]))
  {
    if (isset($acl[$f]) && isset($acl[$f][$acl_options['local'][$opt]]))
    {
      $cache[$f][$opt] |= $acl[$f][$acl_options['local'][$opt]];
    }
  }

  return $cache[$f][$opt];

} //sd_GetForumPermissions
}

?>