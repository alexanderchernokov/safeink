<?php

if(!defined('IN_PRGM')) return false;

// ################################ Users Online ##############################

if($plugin_folder = sd_GetCurrentFolder(__FILE__))
{
  if(!class_exists('LatestFilesClass'))
  {
    @include_once(ROOT_PATH . 'plugins/' .  $plugin_folder . '/class_latest_files.php');
  }

  if(class_exists('LatestFilesClass'))
  {
    $LatestFiles = new LatestFilesClass($plugin_folder);
    $LatestFiles->DisplayLatestFiles();
    unset($LatestFiles);
  }

  unset($plugin_folder);
}
