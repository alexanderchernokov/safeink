<?php

if(!defined('IN_PRGM')) return;

// ############################# LOG USER ON ###############################

function LoginUser($loginusername, $loginpassword)
{
  global $DB, $sdlanguage;

  if(isset($loginusername) && strlen($loginusername))
  {
    $DB->result_type = MYSQL_ASSOC;
    if($user = $DB->query_first("SELECT * FROM {users} WHERE username = '%s'", $loginusername))
    {
      if(!empty($user['banned']))
      {
        $login_error = $sdlanguage['you_are_banned'];
      }
      else if(empty($user['activated']))
      {
        $login_error = $sdlanguage['not_yet_activated'];
      }
      else
      {
        // SD331: determine, if regular or new salted password is valid:
        $ok1 = empty($user['use_salt']) && ($user['password'] == md5($loginpassword));
        $ok2 = !empty($user['use_salt']) && !empty($user['salt']) && ($user['password'] == md5($user['salt'].md5($loginpassword)));
        if(!$ok1 && !$ok2)
        {
          $login_error = $sdlanguage['wrong_password'];
        }
      }

      if(empty($login_error))
      {
        // user successfully logged in
        // everything else is filled from the database query
        $user['usergroupid']    = $user['usergroupid'];
        $user['usergroupids']   = array($user['usergroupid']);
        $user['loggedin']       = 1;
        $user['dstonoff']       = 0;
        $user['dstauto']        = 1;
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


// ############################# GET USER DETAILS ##############################

function GetUser($userid)
{
  global $DB;

  $user = array();
  if($user = $DB->query_first('SELECT * FROM {users} WHERE userid = %d', $userid))
  {
    // everything else is filled from the database query
    $user['usergroupid']    = $user['usergroupid'];
    $user['usergroupids']   = array($user['usergroupid']);
    $user['loggedin']       = 1;
    //SD322: these fields are not available in the users table yet:
    $user['dstonoff']       = 0;
    $user['dstauto']        = 1;
    $user['sessionurl']     = '';
    $user['ipaddress']      = '';
    $user['admin_notes']    = '';
    $user['user_notes']     = '';
  }
  unset($user['password']);
  return $user;

} //GetUser
