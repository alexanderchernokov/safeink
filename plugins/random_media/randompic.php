<?php

if(!defined('IN_PRGM')) return false;

if($plugin_folder = sd_GetCurrentFolder(__FILE__))
{
  if(!class_exists('RandomImageClass'))
  {
    @include_once(ROOT_PATH . 'plugins/' .  $plugin_folder . '/class_random_image.php');
  }

  if(class_exists('RandomImageClass'))
  {
    $RandomImage = new RandomImageClass($plugin_folder);
    $RandomImage->DisplayRandomImage();
    unset($RandomImage);
  }
  unset($plugin_folder);
}
