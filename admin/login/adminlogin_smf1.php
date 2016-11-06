<?php

if(!defined('IN_PRGM')) return;

// ############################# LOG USER ON ###################################

function md5_hmac($data, $key)
{
  $key = str_pad(strlen($key) <= 64 ? $key : pack('H*', md5($key)), 64, chr(0x00));
  return md5(($key ^ str_repeat(chr(0x5c), 64)) . pack('H*', md5(($key ^ str_repeat(chr(0x36), 64)). $data)));
}

function LoginUser($loginusername, $loginpassword)
{
  global $DB, $sdlanguage, $usersystem;

  $login_errors_arr = '';
  $memberfound = false;

  if(strlen(trim($loginusername)))
  {
    // check which version of smf is the user running
    $smfversion = $DB->query_first('SELECT value FROM ' . $usersystem['tblprefix'] . "settings WHERE variable = 'smfVersion'");

    if($getuser = $DB->query_first('SELECT passwd, ID_MEMBER, ID_GROUP, is_activated,
      emailAddress, additionalGroups, memberName, passwordSalt, timeOffset
      FROM ' . $usersystem['tblprefix'] . "members WHERE memberName = '%s'", $loginusername))
    {
      $memberfound = true;
    }
    else
    if($getuser = $DB->query_first('SELECT passwd, ID_MEMBER, ID_GROUP, is_activated,
      emailAddress, additionalGroups, memberName, passwordSalt, timeOffset
      FROM ' . $usersystem['tblprefix'] . "members WHERE emailAddress = '%s'", $loginusername))
    {
      $memberfound = true;
    }

    if($memberfound)
    {
      if(substr($smfversion[0], 0, 3) == '1.1')
      {
        $sha1loginpassword = sha1(strtolower($getuser['memberName']) . $loginpassword);

        if(strlen($sha1loginpassword) != 40 || $sha1loginpassword != $getuser['passwd'])
        {
          $login_errors_arr = $sdlanguage['wrong_password'];
        }
      }
      else
      {
        $md5loginpassword = md5_hmac($loginpassword, strtolower($getuser['memberName']));

        if($getuser['passwd'] != $md5loginpassword)
        {
          $login_errors_arr = $sdlanguage['wrong_password'];
        }
      }

      if(strlen($login_errors_arr) <= 0)
      {
        $user = array(
          'userid'         => $getuser['ID_MEMBER'],
          'usergroupid'    => $getuser['ID_GROUP'],
          'usergroupids'   => array($getuser['ID_GROUP']),
          'username'       => $getuser['memberName'],
          'loggedin'       => 1,
          'email'          => $getuser['emailAddress'],
          'timezoneoffset' => $getuser['timeOffset'],
          'dstonoff'       => 0,   // NA?
          'dstauto'        => 1,
          'sessionurl'     => '');  // NA?
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

  return isset($login_errors_arr{0}) ? $login_errors_arr : $user;
}

// ############################ GET USER DETAILS ###############################

function GetUser($userid)
{
  global $DB, $usersystem;

  $user = null;

  $getuser = $DB->query_first('SELECT * FROM ' . $usersystem['tblprefix'] . 'members WHERE ID_MEMBER = %d', $userid);

  if(isset($getuser))
  {
    $user = array(
      'userid'         => $getuser['ID_MEMBER'],
      'usergroupid'    => $getuser['ID_GROUP'],
      'usergroupids'   => array($getuser['ID_GROUP']),
      'username'       => $getuser['memberName'],
      'loggedin'       => 1,
      'email'          => $getuser['emailAddress'],
      'timezoneoffset' => $getuser['timeOffset'],
      'dstonoff'       => 0,   // NA?
      'dstauto'        => 1,
      'sessionurl'     => '');  // NA?
  }

  return $user;
}

?>