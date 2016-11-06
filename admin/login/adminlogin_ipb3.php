<?php

if(!defined('IN_PRGM')) return;

// ############################# LOG USER ON ###############################

function LoginUser($loginusername, $loginpassword)
{
  global $DB, $sdlanguage, $usersystem;

  $loginusername = ipb_clean($loginusername);
  $loginpassword = ipb_clean($loginpassword);

	if(strlen($loginusername))
	{
		// get userid for given username
		if($getuser = $DB->query_first('SELECT * FROM ' . $usersystem['tblprefix'] . "members WHERE members_l_username = LOWER('%s')", $loginusername))
		{
			$md5password       = md5($loginpassword);
			$salt              = $getuser['members_pass_salt'];
			$encryptedpassword = md5(md5($salt) . $md5password);

			if($getuser['members_pass_hash'] != $encryptedpassword)
				{
					$login_errors_arr = $sdlanguage['wrong_password'];
				}
				else
				{
          $user = array(
            'userid'         => $getuser['member_id'],
            'usergroupid'    => $getuser['member_group_id'],
            'usergroupids'   => array($getuser['member_group_id']),
            'username'       => $getuser['name'],
            'displayname'    => $getuser['members_display_name'],
            'loggedin'       => 1,
            'email'          => $getuser['email'],
            'timezoneoffset' => (isset($getuser['time_offset'])?$getuser['time_offset']:0),
            'dstonoff'       => $getuser['dst_in_use'],
            'dstauto'        => (isset($getuser['members_dst_auto'])?$getuser['members_dst_auto']:0),
            'sessionurl'     => '');

          // Take into account secondary usergroups:
          if(!empty($getuser['mgroup_others']))
          {
            $user['usergroupids'] = array_unique(array_merge($user['usergroupids'],@preg_split('#,#', $getuser['mgroup_others'], -1, PREG_SPLIT_NO_EMPTY)));
          }

          // If user is member of "Banned Users" or "Unregistered", than do not
          // use any other usergroup:
          @include(ROOT_PATH.$usersystem['folderpath'].'conf_global.php');
          $banned_group = !empty($INFO['banned_group']) ? (int)$INFO['banned_group'] : 5;
          $guests_group = !empty($INFO['guest_group'])  ? (int)$INFO['guest_group']  : 2;
          if(false !== array_search($banned_group,$user['usergroupids'])) // Banned
          {
            $user['usergroupid']  = $banned_group;
            $user['usergroupids'] = array($banned_group);
            $user['loggedin']     = 0;
            $login_errors_arr[]   = $sdlanguage['you_are_banned'];
          }
          else if(false !== array_search($guests_group,$user['usergroupids'])) // Guest
          {
            $user['usergroupids'] = array($guests_group);
            $user['loggedin']     = 0;
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

	return !empty($login_errors_arr) ? $login_errors_arr : $user;
}

// ############################# GET USER DETAILS ###############################

function GetUser($userid)
{
	global $DB, $usersystem;

  $user = NULL;
	$getuser = $DB->query_first('SELECT * FROM ' . $usersystem['tblprefix'] . 'members WHERE member_id = %d', $userid);
	if(!empty($getuser['member_id']))
	{
    $user = array(
      'userid'         => $getuser['member_id'],
      'usergroupid'    => $getuser['member_group_id'],
      'usergroupids'   => array($getuser['member_group_id']),
      'username'       => $getuser['name'],
      'displayname'    => $getuser['members_display_name'],
      'loggedin'       => 1,
      'email'          => $getuser['email'],
      'timezoneoffset' => (isset($getuser['time_offset'])?$getuser['time_offset']:0),
      'dstonoff'       => $getuser['dst_in_use'],
      'dstauto'        => (isset($getuser['members_dst_auto'])?$getuser['members_dst_auto']:0),
      'sessionurl'     => '');

    // Take into account secondary usergroups:
    if(!empty($getuser['mgroup_others']))
    {
      $user['usergroupids'] = array_unique(array_merge($user['usergroupids'],@preg_split('#,#', $getuser['mgroup_others'], -1, PREG_SPLIT_NO_EMPTY)));
    }

    // If user is member of "Banned Users" or "Unregistered", than do not
    // use any other usergroup:
    @include(ROOT_PATH.$usersystem['folderpath'].'conf_global.php');
    $banned_group = !empty($INFO['banned_group']) ? (int)$INFO['banned_group'] : 5;
    $guests_group = !empty($INFO['guest_group'])  ? (int)$INFO['guest_group']  : 2;
    if(false !== array_search($banned_group,$user['usergroupids'])) // Banned
    {
      $user['usergroupid']  = $banned_group;
      $user['usergroupids'] = array($banned_group);
      $user['loggedin']     = 0;
    }
    else
    if(false !== array_search($guests_group,$user['usergroupids'])) // Guest
    {
      $user['usergroupid']  = $guests_group;
      $user['usergroupids'] = array($guests_group);
      $user['loggedin']     = 0;
    }
	}

	return $user;

} //GetUser


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
} //ipb_clean

?>