<?php

if(!defined('IN_PRGM')) exit();
include_once ('includes/custom_languages/'.$user_language.'/login.php');

// ############################################################################
// LOGIN PANEL
// ############################################################################

if(!function_exists('p10_LoginPanel'))
{
function p10_LoginPanel()
{
  global $DB, $mainsettings, $sdurl, $userinfo, $usersystem, $login_errors_arr,$user_language,$values;
  //var_dump($values);
  // get settings and language
  $settings = GetPluginSettings(10);
  $language = GetLanguage(10);
  $displayAvatar = !empty($settings['display_avatar']);

  // SD313: check setting for max. username length (min. 12)
  $p12_settings = GetPluginSettings(12);
  $max_usr_length = $p12_settings['max_username_length'];
  $max_usr_length = (empty($max_usr_length) || $max_usr_length < 12) ? 12 : (int)$max_usr_length;
  $max_usr_length = ($max_usr_length > 64) ? 64 : $max_usr_length;

  //SD343: user caching
  include_once(SD_INCLUDE_PATH.'class_sd_usercache.php'); //SD343
  SDUserCache::$avatar_height = (int)$mainsettings['default_avatar_height'];
  SDUserCache::$avatar_width  = (int)$mainsettings['default_avatar_width'];
  $user = SDUserCache::CacheUser($userinfo['userid'], $userinfo['username'], false, $displayAvatar);

  if($displayAvatar && empty($user['avatar'])) //SD370
  {
    $avatar_conf = array(
      'output_ok' => $displayAvatar,
      'userid'    => $userinfo['userid'],
      'username'  => $userinfo['username'],
      'Avatar Column'       => false,
      'Avatar Image Height' => SDUserCache::$avatar_height,
      'Avatar Image Width'  => SDUserCache::$avatar_width
    );
    $user['avatar'] = sd_PrintAvatar($avatar_conf, true);
  }
  //SD344: create login panel smarty template and assign values
  $tmpl = SD_Smarty::getNew();
  $tmpl->assign('ucp_basic', SDUserCache::IsBasicUCP());
  //language values
  $tmpl->assign('username', $values['username']);
  $tmpl->assign('password', $values['password']);
  $tmpl->assign('remember_me', $values['remember_me']);
  $tmpl->assign('login', $values['login']);
  $tmpl->assign('not_registered', $values['not_registered']);
  $tmpl->assign('register_now', $values['register_now']);
  $tmpl->assign('forgot_your_password', $values['forgot_your_password']);

  $tmpl->assign('userdata', $user);
  $tmpl->assign('loggedin', $userinfo['loggedin'] && empty($login_errors_arr));
  $tmpl->assign('login_errors', empty($login_errors_arr)?false:implode('<br />',$login_errors_arr));
  $tmpl->assign('login_button_text', htmlspecialchars($language['login'], ENT_QUOTES));
  $tmpl->assign('display_avatar', $displayAvatar);
  $tmpl->assign('max_username_length', $max_usr_length);
  $tmpl->assign('phrases', $language);
  $tmpl->assign('settings', $settings);
  $tmpl->assign('form_post_link', '/'.$user_language.'/login');
  $tmpl->assign('private_messages_code', '');
  $tmpl->assign('register_link', false);
  $tmpl->assign('lostpwd_link', false);
  if(!empty($settings['display_register_link']) || !empty($settings['display_forgot_password_link']) )
  {
    if(defined('REGISTER_PATH') && strlen(REGISTER_PATH) && !empty($settings['display_register_link']))
    {
      $tmpl->assign('register_link', REGISTER_PATH);
    }
    if(defined('LOSTPWD_PATH') && strlen(LOSTPWD_PATH) &&
        empty($p12_settings['disable_forgot_password']) &&
       !empty($settings['display_forgot_password_link']))
    {
      $tmpl->assign('lostpwd_link', LOSTPWD_PATH);
    }
  }

  // **********************************************************************
  // *** Buffer output of Private Messages addon
  // **********************************************************************
  if($userinfo['loggedin'] && empty($login_errors_arr) &&
       !empty($settings['show_private_messages']) &&
       (($usersystem['name'] != 'Subdreamer') || !SDUserCache::IsBasicUCP()))
  {
    @ob_start();
    @require_once(ROOT_PATH.'plugins/p10_mi_loginpanel/pminfo.php');
    p10_DisplayPMs($usersystem, $language, $settings); //SD342 pass params
    if($pm_code = @ob_get_clean())
    {
      $tmpl->assign('private_messages_code', $pm_code);
    }
  }

  SD_Smarty::display(10, 'loginpanel.tpl', $tmpl, true);
} //p10_LoginPanel
} //do not remove!

// ############################################################################
// SELECT FUNCTION
// ############################################################################

if(!SD_IS_BOT) //SD343
p10_LoginPanel();
