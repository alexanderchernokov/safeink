<?php
// Check for Ajax request
if(!defined('IN_PRGM'))
{
  if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
     ($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest'))
  {
    exit();
  }
  define('IN_PRGM', true);
  define('ROOT_PATH', '../../');
  require(ROOT_PATH . 'includes/init.php');
  if(empty($userinfo['loggedin'])) exit();
  require(ROOT_PATH . 'includes/enablegzip.php');
} //end of Ajax actions


$p11_action = $p11_pageid = false;

$p11_profile = GetVar('profile', null, 'whole_number');
if($p11_member = GetVar('member', 0, 'whole_number'))
{
  $p11_action = 'memberpage';
  $p11_pageid = GetVar('do', '', 'string');
}
else
if($p11_profile && ($p11_profile == $userinfo['userid']))
{
  if( (!$p11_action = GetVar('ucp_action', '', 'string')) ||
      (!$p11_pageid = GetVar('ucp_page', '', 'string')) )
  {
    $p11_action = 'do';
    $p11_pageid = GetVar('do', '', 'string');
  }
}

if(Is_Ajax_Request())
{
  $p11_action = GetVar('action', '', 'string'); //SD370
  if( !$p11_action || (($p11_action!='currentdate') && !$p11_profile) ||
      !in_array($p11_action, array('currentdate','delete','loadpage','loadval','submit')))
  {
    if(isset($DB)) $DB->close();
    exit();
  }
  if(!headers_sent())
  {
    header('Content-type: text/html');
  }
}

// ############################################################################

function p11_DisplayContent()
{
  global $DB, $mainsettings_user_profile_page_id, $userinfo, $usersystem,
         $UserProfile, $p11_action, $p11_member, $p11_pageid;

  $p11_settings = GetPluginSettings(11);
  if(!empty($p11_settings['disable_guest_member_page_access']) &&
     (empty($userinfo['loggedin']) || empty($userinfo['userid']) || ($userinfo['userid'] < 1)))
  {
    global $sdlanguage;
    echo $sdlanguage['no_view_access'];
    return;
  }
/*
  // Allow forum integration???
  if($usersystem['name'] != 'Subdreamer')
  {
    if(!Is_Ajax_Request())
    {
      $p11_language = GetLanguage(11);
      echo '<a href="' . ForumLink(2, $userinfo['userid']) . '">' . $p11_language['visit_cp'] . '</a>';
    }
    return;
  }
*/
  require_once(SD_INCLUDE_PATH.'class_userprofile.php');
  SDProfileConfig::init();

  $UserProfile_local = false;
  if(!isset($UserProfile) || !($UserProfile instanceof SDUserProfile))
  {
    $UserProfile_local = true;
    $UserProfile = new SDUserProfile($userinfo['userid']);
    $userinfo['profile'] = SDProfileConfig::GetUserdata(); //SD370: important!
  }
  else
  {
    if($UserProfile->currentuser() != $userinfo['userid'])
    {
      $UserProfile->LoadUser($userinfo['userid']);
    }
  }

  $skip_panel = false;
  if(Is_Ajax_Request() && !empty($p11_action))
  {
    switch($p11_action)
    {
      case 'currentdate':
        echo DisplayDate(TIME_NOW, 'Y-m-d G:i', true);
        $DB->close();
        exit();
      case 'delete':
      case 'loadpage':
      case 'submit':
        if(empty($mainsettings_user_profile_page_id)) exit();
        define('PAGEID', (int)$mainsettings_user_profile_page_id);
        $GLOBALS['categoryid'] = PAGEID;
        if(!empty($p11_pageid) && ($p11_pageid!=='dashboard'))
        {
          if(in_array($p11_action,array('delete','submit')))
          {
            $UserProfile->DisplayControlPanel($p11_action, $p11_pageid);
          }
          else
          {
            if(!$UserProfile->DisplayProfilePage($p11_action, $p11_pageid))
            {
              echo 'Error';
              exit();
            }
          }
          $skip_panel = true;
        }
        elseif(!empty($userinfo['userid']))
        {
          $UserProfile->DisplayControlPanel($p11_action, '');
        }
        break;
      case 'loadval':
        $val_id = GetVar('ucp_val', '', 'string');
        if($val_id || ($val_id!='unread_text'))
        {
          $UserProfile->PrintProfileValue($val_id);
          $skip_panel = true;
        }
        break;
    }
  }

  if(!$skip_panel)
  {
    if($p11_member)
    {
      $UserProfile->DisplayMemberPage($p11_action, $p11_pageid, $p11_member);
    }
    elseif(!empty($userinfo['userid']))
    {
      $UserProfile->DisplayControlPanel($p11_action, $p11_pageid);
    }
  }

  if($UserProfile_local)
  {
    unset($UserProfile);
  }

} //p11_DisplayContent

p11_DisplayContent();

unset($p11_action, $p11_language, $p11_member, $p11_pageid, $p11_profile);