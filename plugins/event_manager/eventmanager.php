<?php
if(!defined('IN_PRGM')) return false;

// ############################### Event Manager ##############################

if($plugin_folder = sd_GetCurrentFolder(__FILE__))
{
  if(!class_exists('EventManagerClass'))
  {
    @include_once(ROOT_PATH . 'plugins/' .  $plugin_folder . '/class_eventmanager.php');
  }

  if(class_exists('EventManagerClass'))
  {
    $EventManager = new EventManagerClass($plugin_folder);
    $EventManager->DisplayDefault();
    unset($EventManager);
  }

  unset($plugin_folder);
}
