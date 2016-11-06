<?php
$dosaveoptions = false;
if(!defined('IN_ADMIN')) return false;

if($plugin_folder = sd_GetCurrentFolder(__FILE__))
{
  if(!class_exists('ArticlesArchiveSettings'))
  {
    @include_once(ROOT_PATH . 'plugins/class_articles_archive_settings.php');
  }

  if(class_exists('ArticlesArchiveSettings'))
  {
    $obj = new ArticlesArchiveSettings($plugin_folder);
    $obj->Init();
    unset($obj);
  }
}