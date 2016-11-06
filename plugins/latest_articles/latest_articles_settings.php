<?php
$dosaveoptions = false;
if(!defined('IN_PRGM') || !defined('IN_ADMIN'))
{
  define('IN_PRGM', true);
  define('IN_ADMIN', true);
  define('ROOT_PATH', '../../');

  // INIT PRGM
  @require(ROOT_PATH . 'includes/init.php');
  include_once(ROOT_PATH.'includes/enablegzip.php');

  $action = GetVar('action', '', 'string');
  if(!Is_Ajax_Request() || !CheckFormToken('',false) || empty($action)) return false;
  @header('Content-type:text/html; charset=' . SD_CHARSET);
  switch($action)
  {
    case 'savepageoptions':
      $dosaveoptions = true;
      break;
    default: die('Error!');
  }
}

if($plugin_folder = sd_GetCurrentFolder(__FILE__))
{
  if(!class_exists('LatestArticlesSettings'))
  {
    @include_once(ROOT_PATH . 'plugins/class_latest_articles_settings.php');
  }

  if(class_exists('LatestArticlesSettings'))
  {
    $LAS = new LatestArticlesSettings($plugin_folder);
    if($dosaveoptions)
    {
      $LAS->SaveMatches(false);
    }
    else
    {
      $LAS->Init();
    }
    unset($LAS);
  }
}