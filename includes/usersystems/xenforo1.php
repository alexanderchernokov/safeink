<?php

if(!defined('IN_PRGM')) return;

// ############################## SETUP VARIABLES #############################
$login_errors_arr = array();
$startTime = microtime(true);

// ######################## START XenForo Session #############################
/*
Inspired by:
http://xenforo.com/community/threads/using-xenforo-permission-outside-of-xenforo.7585/page-2
https://github.com/JeremyHutchings/xF_auth/blob/master/system/application/libraries/xf_auth.php
Other stuff:
http://xenforo.com/community/threads/forumconnector.8176/
http://code.google.com/p/forumconnect/
*/
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

// Not required if you are not using any of the preloaded data
//$dependencies = new XenForo_Dependencies_Public();
//$dependencies->preLoadData();

$SD_XenForo_Session = XenForo_Session::startPublicSession();

//$session = new XenForo_Session();
//$session->start();
//$session->save();

$SD_XenForo_user = XenForo_Visitor::getInstance();

if($DB->database != $usersystem['dbname']) $DB->select_db($usersystem['dbname']);
$tableprefix = $usersystem['tblprefix'];
$cookietime  = 3600;
$usersystem['cookieprefix'] = $SD_XenForo_Application->get('config')->cookie->prefix;
$usersystem['cookiedomain'] = $cookiedomain = $SD_XenForo_Application->get('config')->cookie->domain;
$usersystem['cookiepath']   = $cookiepath   = $SD_XenForo_Application->get('config')->cookie->path;
$cookiename = $usersystem['cookieprefix'].'session';

// ################################ FORM LOGIN ################################

if(isset($_POST['login']) && ($_POST['login'] == 'login'))
{
  $loginusername = GetVar('loginusername', '', 'string', true, false);
  $loginpassword = sd_unhtmlspecialchars(GetVar('loginpassword', '', 'string', true, false));
  $rememberme    = !empty($_POST['rememberme']);

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
        $login_errors_arr[] = $sdlanguage['wrong_password'];
      }
      else
      {
        $SD_XenForo_Session->changeUserId($userid);
        $SD_XenForo_user = XenForo_Visitor::setup($userid);
        $SD_XenForo_Session->save();

        // fill in member userinfo for subdreamer
        $user = array(
          'userid'         => $userid,
          'username'       => $SD_XenForo_user['username'],
          'usergroupid'    => $SD_XenForo_user['user_group_id'],
          'usergroupids'   => @array_merge(array($SD_XenForo_user['user_group_id']),@explode(',',$SD_XenForo_user['secondary_group_ids'])),
          'banned'         => $SD_XenForo_user['is_banned'],
          'displayname'    => (isset($SD_XenForo_user['custom_title'])?$SD_XenForo_user['custom_title']:''),
          'loggedin'       => 1,
          'email'          => $SD_XenForo_user['email']
          );

        // If user is banned, than do not use any usergroup
        if(!empty($user['banned']))
        {
          $user['usergroupid']  = 1;
          $user['usergroupids'] = array(1);
          $user['loggedin']     = 0;
          $user['banned']       = 1;
          $login_errors_arr[] = $sdlanguage['you_are_banned'];
        }
      }
    }
    else
    {
      $login_errors_arr[] = $sdlanguage['wrong_username'];
    }
  }
  else
  {
    $login_errors_arr[] = $sdlanguage['please_enter_username'];
  }
} //login


if(isset($_SERVER['HTTP_USER_AGENT']) && !defined('USER_AGENT'))
define('USER_AGENT', substr(htmlspecialchars($_SERVER['HTTP_USER_AGENT']), 0, 200));


// ############################################################################
// LOGOUT
// ############################################################################

if(isset($_GET['logout']) AND $SD_XenForo_user['user_id'] > 0)
{
  $SD_XenForo_Session->changeUserId(0);
  $SD_XenForo_user = XenForo_Visitor::setup(0);
  $SD_XenForo_Session->save();

  $SD_XenForo_user = array(
                     'user_id'             => 0,
                     'username'            => '',
                     'user_group_id'       => 1, // XF "Guests"
                     'secondary_group_ids' => '',
                     'custom_title'        => '',
                     'loggedin'            => 0,
                     'email'               => '',
                     'is_banned'           => 0);
}

// ###################### SUBDREAMER USER SETTINGS SETUP #######################

$usersettings = array('userid'         => $SD_XenForo_user['user_id'],
                      'username'       => $SD_XenForo_user['username'],
                      'usergroupid'    => $SD_XenForo_user['user_group_id'],
                      'usergroupids'   => array_merge(array($SD_XenForo_user['user_group_id']),explode(',',$SD_XenForo_user['secondary_group_ids'])),
                      'displayname'    => (isset($SD_XenForo_user['custom_title'])?$SD_XenForo_user['custom_title']:''),
                      'loggedin'       => !empty($SD_XenForo_user['user_id']),
                      'email'          => $SD_XenForo_user['email'],
                      'banned'         => $SD_XenForo_user['is_banned'],
                      'timezoneoffset' => 0,
                      'dstonoff'       => 0,
                      'dstauto'        => 0,
                      'sessionurl'     => ''
                      );

// ############################## USER FUNCTIONS ##############################

function IsIPBanned($clientip)
{
  return false;
}

// Returns the relevent forum link url
// linkType
// 1 - Register
// 2 - UserCP
// 3 - Recover Password
// 4 - UserCP (requires $userid)
// 5 - SendPM (requires $userid)
function ForumLink($linkType, $userid = -1, $username = '')
{
  global $sdurl, $userinfo, $usersystem, $SD_XenForo_Options;

  switch($linkType)
  {
    case 1:
      $url = 'index.php?login/';
      return XenForo_Link::buildPublicLink('canonical:login', false, array());
      break;
    case 2:
      $url = 'index.php?account/';
      return XenForo_Link::buildPublicLink('canonical:account', false, array());
      break;
    case 3:
      $url = 'index.php?lost-password/';
      return XenForo_Link::buildPublicLink('canonical:lost-password', false, array());
      break;
    case 4:
      $url = 'index.php?account/';
      if(!empty($userinfo['userid']) && ($userinfo['userid']==$userid))
      {
        return XenForo_Link::buildPublicLink('canonical:account', false, array());
      }

      $m_user = XenForo_Model::create('XenForo_Model_User');
      $user = $m_user->getUserById($userid, array('join' => XenForo_Model_User::FETCH_USER_OPTION));
      return XenForo_Link::buildPublicLink('canonical:members', $user, array());
      break;
    case 5:
      $m_user = XenForo_Model::create('XenForo_Model_User');
      $user = $m_user->getUserById($userid, array('join' => XenForo_Model_User::FETCH_USER_OPTION));
      return XenForo_Link::buildPublicLink('canonical:conversations', $user, array());
      break;
  }
  return '';
}

function ForumAvatar($userid, $username, $a_size = 'm')
{
  global $DB, $dbname, $usersystem, $sdurl, $SD_XenForo_user;

  $avatar = '';
  if(empty($SD_XenForo_user['permissions']['avatar']['allowed']))
  {
    return '';
  }
  // forum information
  $forumdbname = $usersystem['dbname'];
  $forumpath   = $usersystem['folderpath'];
  $tableprefix = $usersystem['tblprefix'];

  // switch to forum database
  if($dbname != $forumdbname)
  {
    $DB->select_db($forumdbname);
  }

  $sql = 'SELECT user_id, avatar_date, avatar_width, avatar_height, gravatar FROM ' . $tableprefix . 'user WHERE ';
  if(empty($username))
  {
    $sql = $sql.'user_id = ' . (int)$userid;
  }
  else
  {
    $sql .= "username = '" . $username . "'";
  }
  $useravatar = $DB->query_first($sql);

  if(strlen($useravatar['gravatar']))
  {
    if($avatar_path = GetAvatarPath($useravatar['gravatar'], $userid))
    {
      $avatar = '<img class="avatar" src="' . $avatar_path . '" />';
    }
  }
  else
  if(!empty($useravatar['avatar_date'])) //SD342: custom avatar image
  {
    $avatar = '<img class="avatar" src="' . XenForo_Helper_File::getExternalDataPath().
              '/avatars/'.$a_size.'/'.floor($userid/1000).'/'.$userid.'.jpg" alt="" />';
  }

  // switch back to subdreamer database
  if($dbname != $forumdbname)
  {
    $DB->select_db($dbname);
  }

  return $avatar;

} //ForumAvatar
