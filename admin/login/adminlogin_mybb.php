<?php
if(!defined('IN_PRGM')) return;

// ##### init variables #####
$BB_ADMIN_GID = 0;
$BB_GUEST_ID  = -1;
$mybb_config  = array();

$forumdbname  = $usersystem['dbname'];
$tableprefix  = $usersystem['tblprefix'];

if($DB->database != $forumdbname) $DB->select_db($forumdbname);

// Get MyBB config values:
if($getrows = $DB->query('SELECT name, value'.
   ' FROM ' . $tableprefix . 'settings'.
   " WHERE name IN ('cookiename','cookiepath','cookiedomain','maxloginattempts',
                    'avatardir','avataruploadpath',
                    'postmaxavatarsize','username_method')"))
{
  while($row = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
  {
    $mybb_config[$row['name']] = $row['value'];
  }
  $DB->free_result($getrows);
}

// Get MyBB cached values:
if($getrows = $DB->query('SELECT title, cache'.
   ' FROM ' . $tableprefix . 'datacache'.
   " WHERE title IN ('banned','bannedips','spiders')"))
{
  while($row = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
  {
    if(!empty($row['value']) && (substr($row['value'],0,3) != 'a:0'))
    {
      $mybb_config[$row['title']] = @unserialize($row['value']);
    }
  }
  $DB->free_result($getrows);
}

// Get id's for default usergroups Administrators and Guests:
if($getrows = $DB->query('SELECT title, gid FROM '.$usersystem['tblprefix'].
                           'usergroups WHERE type = 1'.
                           " AND title IN ('Administrators', 'Guests')"))
{
  while($row = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
  {
    if($row['title'] == 'Administrators')
      $BB_ADMIN_GID = (int)$row['gid'];
    else
      $BB_GUEST_ID = (int)$row['gid'];
  }
}
unset($getrows,$row);

// ############################ LOG USER IN ###################################

function LoginUser($loginusername, $loginpassword)
{
  global $DB, $sdlanguage, $usersystem, $mybb_config, $BB_GUEST_ID;

  $tableprefix = $usersystem['tblprefix'];
  $user = array();

  if(isset($loginusername) && strlen($loginusername))
  {
    $getuser = GetUser(0, $loginusername);

    // If a string, then it is an error message...
    if(is_string($getuser))
    {
      return $getuser;
    }

    if(is_array($getuser) && !empty($getuser['userid']))
    {
      if(empty($loginpassword) ||
         (md5(md5($getuser['salt']).md5($loginpassword)) != $getuser['password']))
      {
        $login_error = $sdlanguage['wrong_password'];
      }

      if(empty($login_error))
      {
        $user = $getuser;
        unset($user['password']);
      }
    }
    else
    {
      $login_error = $sdlanguage['wrong_username'];
    }
  }
  else
  {
    $login_error = $sdlanguage['please_enter_username'];
  }

  return isset($login_error) ? $login_error : $user;

} //LoginUser


// ############################ GET USER DETAILS ##############################

function GetUser($userid, $username='')
{
  global $DB, $sdlanguage, $usersystem, $mybb_config, $BB_GUEST_ID;

  $tableprefix = $usersystem['tblprefix'];

  $user = array();
  if(empty($userid) && empty($username)) return $user;

  $sql = 'SELECT u.*, g.isbannedgroup, a.aid, a.type,'.
         ' b.uid ban_uid, b.lifted ban_lifted'.
         ' FROM ' . $tableprefix . 'users u'.
         ' LEFT OUTER JOIN ' . $tableprefix . 'usergroups g ON g.gid = u.usergroup'.
         ' LEFT OUTER JOIN ' . $tableprefix . 'banned b ON b.uid = u.uid'.
         ' LEFT OUTER JOIN ' . $tableprefix . 'awaitingactivation a ON a.uid = u.uid'.
         ' WHERE ';

  $DB->result_type = MYSQL_ASSOC;
  if(!empty($userid))
    $getuser = $DB->query_first($sql.'u.uid = %d', $userid);
  else
    $getuser = $DB->query_first($sql."u.username = '%s'", $username);

  // Check ban and activation status first:
  if(!empty($getuser))
  {
    // Is user banned?
    if( !empty($getuser['isbannedgroup']) ||
        (!empty($getuser['ban_uid']) && ($getuser['ban_lifted']) > TIME_NOW) )
    {
      return $sdlanguage['you_are_banned'];
    }
    else
    // Is user not yet activated?
    if(!empty($getuser['aid'])) // not activated
    {
      return $sdlanguage['not_yet_activated'];
    }
  }

  if(!empty($getuser['uid']))
  {
    $user = array('userid'         => $getuser['uid'],
                  'usergroupid'    => $getuser['usergroup'],
                  'usergroupids'   => array($getuser['usergroup']),
                  'loginkey'       => $getuser['loginkey'],
                  'logoutkey'      => md5($getuser['loginkey']),
                  'username'       => $getuser['username'],
                  'email'          => $getuser['email'],
                  'timezoneoffset' => $getuser['timezone'],
                  'dateformat'     => $getuser['dateformat'],
                  'dstonoff'       => $getuser['dst'],
                  'salt'           => $getuser['salt'],
                  'password'       => $getuser['password'],
                  'ipaddress'      => USERIP,
                  'loggedin'       => 1,
                  'displayname'    => '',
                  'dstauto'        => 0,
                  'session_url'    => '',
                  'admin_notes'    => '',
                  'user_notes'     => '',
                  'activated'      => empty($getuser['aid']));

    // Check for secondary, non-guests usergroups of user
    $usergroupids = $user['usergroupids'];
    if(!empty($getuser['additionalgroups']))
    {
      if(false !== ($tmp = explode(',',$getuser['additionalgroups']))) //TBD???
      {
        $usergroupids = array_merge($usergroupids, $tmp);
      }
    }

    if(empty($usergroupids))
    {
      // If none, default to phpBB3 "Guests"
      $user['usergroupids'] = array($BB_GUEST_ID);
    }
    else
    {
      // If user is member of "Guests", than do not use any other usergroup:
      if(false !== array_search($BB_GUEST_ID,$user['usergroupids'])) // Guests
      {
        $user['usergroupids'] = array($BB_GUEST_ID);
      }
      else
      {
        natsort($usergroupids);
        $user['usergroupids'] = $usergroupids;
      }
    }
    unset($usergroupids);
  }
  unset($getuser);
  return $user;

} //GetUser
