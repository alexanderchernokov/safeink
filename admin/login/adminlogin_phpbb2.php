<?php

if(!defined('IN_PRGM')) return;

define('ANONYMOUS', -1);

// ############################# LOG USER ON ###############################

function LoginUser($loginusername, $loginpassword)
{
  global $DB, $sdlanguage, $usersystem;

  if(strlen($loginusername))
  {
    // get userid for given username
    if($getuser = $DB->query_first("SELECT * FROM " . $usersystem['tblprefix'] . "users WHERE username = '%s'", $loginusername))
    {
      if(md5($loginpassword) === $getuser['user_password'] AND $getuser['user_active'] AND $getuser['user_id'] != ANONYMOUS)
      {
        // logged in
        $user = array('userid'   => $getuser['user_id'],
                'usergroupid'    => -1,
                'usergroupids'   => array(-1),
                'username'       => $getuser['username'],
                'loggedin'       => 1,
                'email'          => $getuser['user_email'],
                'timezoneoffset' => $getuser['user_timezone'],
                'dstonoff'       => 0,    // phpBB2 doesn't have a dst option
                'dstauto'        => 0,
                'sessionurl'     => '');

        $usergroupids = array();

        // find the usergroupids
        // phpbb2 creates a new usergroup for each user, but I don't want to show hundreds of usergroups in the admin panel
        // so lets only select the usergroups that don't have a strlen of 0
        //$usergroupids[] = 5; // default of 5

        $getusergroups = $DB->query('SELECT g.group_id FROM ' . $usersystem['tblprefix'] . "groups g, " . $usersystem['tblprefix'] . "user_group u
          WHERE u.user_id = %d AND u.group_id = g.group_id AND g.group_name != ''", $user['userid']);

        while($usergroup = $DB->fetch_array($getusergroups))
        {
          // now find the usergroup name
          if(!in_array($usergroup['group_id'], $usergroupids))
          {
            $usergroupids[] = $usergroup['group_id'];
          }
        }

        // add the 'fake' registered group?
        if(count($usergroupids) <= 0 && $isregistered = $DB->query_first("SELECT g.group_id FROM " . $usersystem['tblprefix'] . "groups g, " . $usersystem['tblprefix'] . "user_group u
                                                              WHERE u.user_id = %d AND u.group_id = g.group_id AND g.group_name = ''", $user['userid']))
        {
          $usergroupids[] = -3; // usergroup id -3 is an emulated registered usergroup for phpBB2 created by Subdreamer
        }

        $user['usergroupids'] = $usergroupids;
        if(count($usergroupids))
        {
          $user['usergroupid'] = $usergroupids[0];
        }
      }
      else
      {
        $login_errors_arr = $sdlanguage['wrong_password'];
      }
    }
    else
    {
      // wrong username OR: username = ANONYMOUS, probably a hacker
      // lets just give them wrong username to throw them off
      $login_errors_arr = $sdlanguage['wrong_username'];
    }
  }
  else
  {
    $login_errors_arr = $sdlanguage['please_enter_username'];
  }
  return isset($login_errors_arr) ? $login_errors_arr : $user;
}

// ############################# GET USER DETAILS ###############################

function GetUser($userid)
{
  global $DB, $usersystem;

  $user = null;

  $getuser = $DB->query_first('SELECT * FROM ' . $usersystem['tblprefix'] . 'users WHERE user_id = %d', $userid);

  if(isset($getuser))
  {
    $user = array('userid'   => $getuser['user_id'],
            'username'       => $getuser['username'],
            'loggedin'       => 1,
            'email'          => $getuser['user_email'],
            'timezoneoffset' => $getuser['user_timezone'],
            'dstonoff'       => 0,   // phpBB2 doesn't have a dst option
            'dstauto'        => 0,
            'sessionurl'     => '');

    $usergroupids = array();

    // find the usergroupids
    // phpbb2 creates a new usergroup for each user, but I don't want to show hundreds of usergroups in the admin panel
    // so lets only select the usergroups that don't have a strlen of 0

    $getusergroups = $DB->query("SELECT g.group_id FROM " . $usersystem['tblprefix'] . "groups g, " . $usersystem['tblprefix'] . "user_group u
                                 WHERE u.user_id = %d AND u.group_id = g.group_id AND g.group_name != ''", $user['userid']);
    while($usergroup = $DB->fetch_array($getusergroups))
    {
      // now find the usergroup name
      if(!in_array($usergroup['group_id'], $usergroupids))
      {
        $usergroupids[] = $usergroup['group_id'];
      }
    }
    if(count($usergroupids))
    {
      $user['usergroupid'] = $usergroupids[0];
    }

    // add the 'fake' registered group?
    if(!count($usergroupids) && ($isregistered = $DB->query_first("SELECT g.group_id FROM " . $usersystem['tblprefix'] . "groups g, " . $usersystem['tblprefix'] . "user_group u
                                 WHERE u.user_id = %d AND u.group_id = g.group_id AND g.group_name = ''", $user['userid'])))
    {
      $usergroupids[] = -3; // usergroup id -3 is an emulated registered usergroup for phpBB2 created by Subdreamer
    }

    $user['usergroupids'] = $usergroupids;
  }

  return $user;
}
