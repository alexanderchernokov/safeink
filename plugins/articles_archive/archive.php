<?php
if(!defined('IN_PRGM')) return false;

if($plugin_folder = sd_GetCurrentFolder(__FILE__))
{
  if(!class_exists('ArticlesArchiveClass'))
  {
    @include_once(ROOT_PATH . 'plugins/class_articles_archive.php');
  }
  if(class_exists('ArticlesArchiveClass'))
  {
    $obj = new ArticlesArchiveClass($plugin_folder);
    $obj->DisplayContent();
    unset($obj);
  }
  unset($plugin_folder);
}
