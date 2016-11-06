<?php

if(!defined('IN_PRGM')) return false;

// ############################## Articles ###################################

if($plugin_folder = sd_GetCurrentFolder(__FILE__))
{
  if($pluginid = GetPluginIDbyFolder($plugin_folder))
  {
    if(!isset($sd_instances)) $sd_instances = array();
    if(empty($sd_instances[$pluginid]) || !is_object($sd_instances[$pluginid]))
    {
      if(!class_exists('ArticlesClass'))
      {
        @require_once(SD_INCLUDE_PATH . 'class_articles.php');
      }
      $sd_instances[$pluginid] = new ArticlesClass($plugin_folder);
    }
    $sd_instances[$pluginid]->Display(!empty($article_arr)?$article_arr:null);
    unset($plugin_folder);
  }
}
