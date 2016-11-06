<?php
if(!defined('IN_PRGM')) return;

// ##### init variables #####
$BB_ADMIN_GID = 1;
$BB_GUEST_ID  = 2;
$punbb_config  = array();

$forumdbname  = $usersystem['dbname'];
$tableprefix  = $usersystem['tblprefix'];

if($DB->database != $forumdbname) $DB->select_db($forumdbname);

// Get some punBB config values:
if($getrows = $DB->query('SELECT conf_name, conf_value'.
   ' FROM ' . $tableprefix . 'config'.
   " WHERE conf_name IN ('o_date_format','o_timeout_visit','p_allow_banned_email')"))
{
  while($row = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
  {
    $punbb_config[$row['conf_name']] = $row['conf_value'];
  }
  $DB->free_result($getrows);
}
unset($getrows);
$punbb_config['o_date_format'] = isset($punbb_config['o_date_format'])?
                                 $punbb_config['o_date_format']:'';
$punbb_config['date_formats']  = array($punbb_config['o_date_format'], 'Y-m-d',
                                 'Y-d-m', 'd-m-Y', 'm-d-Y', 'M j Y', 'jS M Y');

// ############################ LOG USER IN ###################################

function LoginUser($loginusername, $loginpassword)
{
  global $DB, $sdlanguage, $usersystem, $punbb_config, $BB_GUEST_ID;

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
      $isSHA = (strlen($getuser['password'])==40);
      if(!empty($loginpassword) &&
         (($isSHA  && !empty($getuser['salt']) && (sha1($getuser['salt'].sha1($loginpassword)) == $getuser['password'])) ||
          (!$isSHA &&  empty($getuser['salt']) && (md5($loginpassword) == $getuser['password']))) )
      {
        // login was ok...
      }
      else
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
  global $DB, $sdlanguage, $usersystem, $punbb_config, $BB_GUEST_ID;

  $tableprefix = $usersystem['tblprefix'];

  $user = array();
  if(empty($userid) && empty($username)) return $user;

  $sql = 'SELECT u.id, u.username, u.group_id, u.password, u.salt, u.realname,'.
         ' u.email, u.date_format, u.dst, u.timezone,'.
         ' b.username user_banned'.
         ' FROM ' . $tableprefix . 'users u'.
         ' LEFT JOIN ' . $tableprefix . 'groups g ON g.g_id = u.group_id'.
         ' LEFT JOIN ' . $tableprefix . 'bans b ON b.username = u.username'.
         ' WHERE ';

  $DB->result_type = MYSQL_ASSOC;
  if(!empty($userid))
    $getuser = $DB->query_first($sql.'u.id = %d', $userid);
  else
    $getuser = $DB->query_first($sql."u.username = '%s'", $DB->escape_string($username));

  // Check ban and activation status first:
  if(!empty($getuser))
  {
    // Is user banned?
    if(!is_null($getuser['user_banned']))
    {
      return $sdlanguage['you_are_banned'];
    }
    else
    // Is user not yet activated (usergroup id = 0)?
    if(empty($getuser['group_id']))
    {
      return $sdlanguage['not_yet_activated'];
    }
  }

  if(!empty($getuser['id']))
  {
    $user = array('userid'         => $getuser['id'],
                  'usergroupid'    => $getuser['group_id'],
                  'usergroupids'   => array($getuser['group_id']),
                  'username'       => $getuser['username'],
                  'email'          => $getuser['email'],
                  'timezoneoffset' => $getuser['timezone'],
                  'dateformat'     => $getuser['date_format'],
                  'dstonoff'       => $getuser['dst'],
                  'salt'           => $getuser['salt'],
                  'password'       => $getuser['password'],
                  'loggedin'       => 1,
                  'ipaddress'      => USERIP,
                  'displayname'    => (isset($getuser['realname'])?$getuser['realname']:''),
                  'dstauto'        => 0,
                  'session_url'    => '');

    if( ($user['dateformat']!='') && is_numeric($user['dateformat']) &&
        isset($punbb_config['date_formats'][$user['dateformat']]) )
    {
      $user['dateformat'] = $punbb_config['date_formats'][$user['dateformat']];
    }
  }
  unset($getuser);
  return $user;

} //GetUser
