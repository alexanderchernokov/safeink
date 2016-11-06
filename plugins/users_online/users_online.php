<?php
if(!defined('IN_PRGM')) return false;

if($plugin_folder = sd_GetCurrentFolder(__FILE__))
{
  @include_once(ROOT_PATH . 'plugins/' .  $plugin_folder . '/class_users_online.php');
  if(class_exists('UsersOnlineClass'))
  {
    @include_once(SD_INCLUDE_PATH.'class_sd_usercache.php');
    $UsersOnline = new UsersOnlineClass($plugin_folder);
    $UsersOnline->DisplayUsersOnline($usersystem);
    unset($UsersOnline);
  }
  unset($plugin_folder);
}
