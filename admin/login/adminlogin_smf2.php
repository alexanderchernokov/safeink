<?php

if(!defined('IN_PRGM')) return;

// ############################# LOG USER ON ###############################

function md5_hmac($data, $key)
{
  $key = str_pad(strlen($key) <= 64 ? $key : pack('H*', md5($key)), 64, chr(0x00));
  return md5(($key ^ str_repeat(chr(0x5c), 64)) . pack('H*', md5(($key ^ str_repeat(chr(0x36), 64)). $data)));
}

function LoginUser($loginusername, $loginpassword)
{
  global $DB, $sdlanguage, $usersystem;

  $GLOBALS['login_errors_arr'] = '';
  $memberfound = false;
  $user = array();
  if(strlen(trim($loginusername)))
  {
    // check which version of smf is the user running
    $smfversion = $DB->query_first('SELECT value FROM ' . $usersystem['tblprefix'] . "settings WHERE variable = 'smfVersion'");

    if($getuser = $DB->query_first('SELECT passwd, id_member, id_group, is_activated,
      email_address, additional_groups, member_name, password_salt, time_offset
      FROM ' . $usersystem['tblprefix'] . "members WHERE member_name = '%s'", $loginusername))
    {
      $memberfound = true;
    }
    else
    if($getuser = $DB->query_first('SELECT passwd, id_member, id_group, is_activated,
      email_address, additional_groups, member_name, password_salt, time_offset
      FROM ' . $usersystem['tblprefix'] . "members WHERE email_address = '%s'", $loginusername))
    {
      $memberfound = true;
    }

    if($memberfound)
    {
      $sha1loginpassword = sha1(strtolower($getuser['member_name']) . $loginpassword);

      if(strlen($sha1loginpassword) != 40 || $sha1loginpassword != $getuser['passwd'])
      {
        $login_errors_arr = $sdlanguage['wrong_password'];
      }

      if(empty($login_errors_arr))
      {
        $user = array('userid'         => $getuser['id_member'],
                      'usergroupid'    => $getuser['id_group'],
                      'usergroupids'   => array($getuser['id_group']),
                      'username'       => $getuser['member_name'],
                      'loggedin'       => 1,
                      'email'          => $getuser['email_address'],
                      'timezoneoffset' => $getuser['time_offset'],
                      'dstonoff'       => 0,    // NA?
                      'dstauto'        => 1,
                      'sessionurl'     => '');  // NA?
      }
    }
    else
    {
      $GLOBALS['login_errors_arr'] = strip_tags($sdlanguage['wrong_username']);
    }
  }
  else
  {
    $GLOBALS['login_errors_arr'] = strip_tags($sdlanguage['please_enter_username']);
  }

  return !empty($GLOBALS['login_errors_arr']) ? false : $user;
}

// ############################# GET USER DETAILS ###############################

function GetUser($userid)
{
  global $DB, $usersystem;

  $user = null;

  $getuser = $DB->query_first('SELECT * FROM ' . $usersystem['tblprefix'] . 'members WHERE id_member = %d', $userid);

  if(isset($getuser))
  {
    $user = array('userid'         => $getuser['id_member'],
                  'usergroupid'    => $getuser['id_group'],
                  'usergroupids'   => array($getuser['id_group']),
                  'username'       => $getuser['member_name'],
                  'loggedin'       => 1,
                  'email'          => $getuser['email_address'],
                  'timezoneoffset' => $getuser['time_offset'],
                  'dstonoff'       => 0,    // NA?
                  'dstauto'        => 1,
                  'sessionurl'     => '');  // NA?
  }

  return $user;
}

?>