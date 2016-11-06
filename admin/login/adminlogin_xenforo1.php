<?php

if(!defined('IN_ADMIN')) exit();

if($DB->database != $usersystem['dbname']) $DB->select_db($usersystem['dbname']);

// ############################## SETUP VARIABLES #############################

$login_errors_arr = array();

$tableprefix  = $usersystem['tblprefix'];
$cookietime   = $usersystem['cookietimeout'];
$cookiename   = $usersystem['cookieprefix'];
$cookiedomain = isset($usersystem['cookiedomain']) ? $usersystem['cookiedomain'] : '';
$cookiepath   = isset($usersystem['cookiepath'])   ? $usersystem['cookiepath'] : "/";

// ##################### START XenForo Session ################################
/* Inspired by:
http://xenforo.com/community/threads/using-xenforo-permission-outside-of-xenforo.7585/page-2
https://github.com/JeremyHutchings/xF_auth/blob/master/system/application/libraries/xf_auth.php
Other stuff:
http://xenforo.com/community/threads/forumconnector.8176/
http://code.google.com/p/forumconnect/
*/

$startTime = microtime(true);
$SD_XenForo_fileDir = ROOT_PATH.$usersystem['folderpath'];

require_once($SD_XenForo_fileDir . '/library/XenForo/Autoloader.php');
XenForo_Autoloader::getInstance()->setupAutoloader($SD_XenForo_fileDir . '/library');
XenForo_Application::disablePhpErrorHandler();
XenForo_Application::setDebugMode(false);
error_reporting(0);
XenForo_Application::initialize($SD_XenForo_fileDir . '/library', $SD_XenForo_fileDir);

$SD_XenForo_Application = XenForo_Application::getInstance();
$SD_XenForo_Application->set('page_start_time', $startTime);
$SD_XenForo_Options = $SD_XenForo_Application->get('options');
error_reporting(ERROR_LOG_LEVEL);

// ############################### LOG IN USER #################################

function LoginUser($loginusername, $loginpassword)
{
  global $DB, $sdlanguage, $usersystem;

  $login_error = false;
  $user = array(
    'userid'         => 0,
    'username'       => '',
    'usergroupid'    => 1,
    'usergroupids'   => array(1),
    'banned'         => false,
    'displayname'    => '',
    'loggedin'       => 0,
    'email'          => '',
    'timezoneoffset' => 0,
    'dstonoff'       => 0,
    'dstauto'        => 0,
    'sessionurl'     => ''
    );

  if(strlen($loginusername))
  {
    // get userid for given username
    if($getuser = $DB->query_first('SELECT * FROM ' . $usersystem['tblprefix'] . 'user u
       INNER JOIN ' . $usersystem['tblprefix'] . "user_authenticate ua ON ua.user_id = u.user_id
       WHERE u.username = '%s'", $loginusername))
    {
      $authok = false;
      $userid = $getuser['user_id'];

      $auth = XenForo_Authentication_Abstract::create($getuser['scheme_class']);
      $auth->setData($getuser['data']);
      $authok = $auth->authenticate($userid, $loginpassword);
      if(!$authok)
      {
        $login_error = $sdlanguage['wrong_password'];
      }
      else
      {
        //$SD_XenForo_Session = XenForo_Session::startAdminSession();
        //$SD_XenForo_Session->deleteExpiredSessions();
        //$SD_XenForo_Session->changeUserId($userid);
        //$SD_XenForo_Session->sessionExists()
        //$visitor->hasAdminPermission()
        //$visitor = XenForo_Visitor::setup($userid);
        //if(!$SD_XenForo_Session->saved()) $SD_XenForo_Session->save();

        // fill in member userinfo for subdreamer
        $user = array(
          'userid'         => $userid,
          'username'       => $getuser['username'],
          'usergroupid'    => $getuser['user_group_id'],
          'usergroupids'   => @array_merge(array($getuser['user_group_id']),@explode(',',$getuser['secondary_group_ids'])),
          'banned'         => $getuser['is_banned'],
          'displayname'    => (isset($getuser['custom_title'])?$getuser['custom_title']:''),
          'loggedin'       => 1,
          'email'          => $getuser['email'],
          'timezoneoffset' => 0,
          'dstonoff'       => 0,
          'dstauto'        => 0,
          'sessionurl'     => ''
          );

        // If user is banned, than do not use any usergroup
        if(!empty($user['banned']))
        {
          $user['usergroupid']  = 1;
          $user['usergroupids'] = array(1);
          $user['loggedin']     = 0;
          $user['banned']       = 1;
          $login_error = $sdlanguage['you_are_banned'];
        }
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
  return empty($login_error) ? $user : $login_error;

} //LoginUser


// ############################ GET USER DETAILS ###############################

function GetUser($userid)
{
  global $DB, $database, $usersystem, $sdlanguage;

  if($DB->database != $usersystem['dbname']) $DB->select_db($usersystem['dbname']);

  $user = false;
  if($getuser = $DB->query_first('SELECT * FROM ' . $usersystem['tblprefix'] . 'user WHERE user_id = %d LIMIT 1', $userid))
  {
    // fill in member userinfo for subdreamer
    $user = array(
      'userid'         => $getuser['user_id'],
      'usergroupid'    => $getuser['user_group_id'],
      'usergroupids'   => array_unique(@array_merge(array($getuser['user_group_id']),@explode(',',$getuser['secondary_group_ids']))),
      'username'       => $getuser['username'],
      'banned'         => $getuser['is_banned'],
      'displayname'    => $getuser['custom_title'],
      'loggedin'       => 1,
      'email'          => $getuser['email'],
      'timezoneoffset' => 0,
      'sessionurl'     => '',
      'dstauto'        => 0,
      'dstonoff'       => 0
      );

    // If user is member of "Banned Users" or "Unregistered", than do not
    // use any other usergroup:
    if(!empty($user['banned']))
    {
      $user['usergroupid']  = 0;
      $user['usergroupids'] = array();
      $user['loggedin']     = 0;
      $user['banned']       = 1;
    }
  }

  if($DB->database != $database['name']) $DB->select_db($database['name']);

  return $user;

} //GetUser
