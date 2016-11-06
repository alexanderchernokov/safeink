<?php
if(!defined('IN_PRGM')) return false;

if($plugin_folder = sd_GetCurrentFolder(__FILE__))
{
  @include_once(ROOT_PATH . 'plugins/' .  $plugin_folder . '/class_link_directory.php');
  if(class_exists('LinkDirectoryClass'))
  {
    $LinkDirectory = new LinkDirectoryClass($plugin_folder);
    $LinkDirectory->DisplayLinkDirectory();
    unset($LinkDirectory);
  }
  unset($plugin_folder);
}
