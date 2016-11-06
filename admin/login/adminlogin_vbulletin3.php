<?php

if(!defined('IN_PRGM')) exit();

// ####### Get the group id's for banned and unregistered usergroups: ##########

if($DB->database != $usersystem['dbname']) $DB->select_db($usersystem['dbname']);

$get_banned_id = $DB->query_first('SELECT usergroupid FROM ' . $usersystem['tblprefix'] . "usergroup WHERE title = 'Banned Users'");
$VB_BANNED_GID = isset($get_banned_id[0]) ? (int)$get_banned_id[0] : null;

$get_guest_id = $DB->query_first('SELECT usergroupid FROM ' . $usersystem['tblprefix'] . "usergroup WHERE title LIKE 'Unregistered%'");
$VB_GUEST_GID = isset($get_guest_id[0]) ? (int)$get_guest_id[0] : null;

unset($get_admin_id, $get_banned_id, $get_guest_id);

// ############################### LOG USER ON #################################

function LoginUser($loginusername, $loginpassword)
{
	global $DB, $sdlanguage, $usersystem, $VB_BANNED_GID, $VB_GUEST_GID;

	if(strlen($loginusername))
	{
		// get userid for given username
		if($getuser = $DB->query_first("SELECT * FROM " . $usersystem['tblprefix'] . "user WHERE username = '%s'", $loginusername))
		{
			if($getuser['password'] != md5(md5($loginpassword) . $getuser['salt']) )
			{
				$login_errors_arr = $sdlanguage['wrong_password'];
			}
			else
			{
				// fill in member userinfo for subdreamer
				$user = array(
          'userid'         => $getuser['userid'],
          'usergroupid'    => $getuser['usergroupid'],
          'usergroupids'   => array($getuser['usergroupid']),
					'username'       => $getuser['username'],
          'displayname'    => $getuser['usertitle'],
					'loggedin'       => 1,
					'email'          => $getuser['email'],
					'timezoneoffset' => $getuser['timezoneoffset'],
					'sessionurl'     => '');

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
        if(!empty($user['usergroupids']))
        {
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
            $user['usergroupid']  = $VB_GUEST_GID;
            $user['usergroupids'] = array($VB_GUEST_GID);
            $user['loggedin']     = 0;
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
		$login_errors_arr = $sdlanguage['please_enter_username'];
	}

	return isset($login_errors_arr) ? $login_errors_arr : $user;

} //LoginUser


// ############################ GET USER DETAILS ###############################

function GetUser($userid)
{
	global $DB, $usersystem, $sdlanguage, $VB_BANNED_GID, $VB_GUEST_GID;

	$user = null;
	$getuser = $DB->query_first('SELECT * FROM ' . $usersystem['tblprefix'] . 'user WHERE userid = %d', $userid);

	if(isset($getuser))
	{
		// fill in member userinfo for subdreamer
    $user = array(
      'userid'         => $getuser['userid'],
      'usergroupid'    => $getuser['usergroupid'],
      'usergroupids'   => array($getuser['usergroupid']),
      'username'       => $getuser['username'],
      'displayname'    => $getuser['usertitle'],
      'loggedin'       => 1,
      'email'          => $getuser['email'],
      'timezoneoffset' => $getuser['timezoneoffset'],
      'sessionurl'     => '');

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
    if(!empty($user['usergroupids']))
    {
      if(isset($VB_BANNED_GID) && (false !== array_search($VB_BANNED_GID,$user['usergroupids']))) // Banned
      {
        $user['usergroupid']  = $VB_BANNED_GID;
        $user['usergroupids'] = array($VB_BANNED_GID);
        $user['loggedin']     = 0;
        $user['banned']       = 1;
        $login_errors_arr[] = $sdlanguage['you_are_banned'];
      }
      else if(isset($VB_GUEST_GID) && (false !== array_search($VB_GUEST_GID,$user['usergroupids']))) // Guests
      {
        $user['usergroupid']  = $VB_GUEST_GID;
        $user['usergroupids'] = array($VB_GUEST_GID);
        $user['loggedin']     = 0;
      }
    }
  }

	return $user;

} //GetUser

?>