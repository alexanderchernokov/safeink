<?php

if(!defined('IN_PRGM')) return false;

if($plugin_folder = sd_GetCurrentFolder(__FILE__))
{
  if(!class_exists('LatestImagesClass'))
  {
    @include_once(ROOT_PATH . 'plugins/' .  $plugin_folder . '/class_latest_images.php');
  }

  if(class_exists('LatestImagesClass'))
  {
    $LatestImages = new LatestImagesClass($plugin_folder);
    $LatestImages->DisplayLatestImages();
    unset($LatestImages);
  }
  unset($plugin_folder);
}
