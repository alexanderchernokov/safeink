<?php
if(!defined('IN_PRGM'))
{
  // Allow for Ajax pagination
  if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || ($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest'))
  {
    return false;
  }
  define('IN_PRGM', true);
  define('ROOT_PATH', '../../');
  @require(ROOT_PATH . 'includes/init.php');
  $categoryid = GetVar('categoryid', 0, 'whole_number', false, true);
  if(empty($categoryid) || ($categoryid < 1))
  {
    return;
  }
  if(!headers_sent())
  {
    header('Content-type:text/html; charset=' . SD_CHARSET);
  }
  @require_once(ROOT_PATH . 'includes/enablegzip.php');
}


if($plugin_folder = sd_GetCurrentFolder(__FILE__))
{
  if(!class_exists('LatestArticlesClass'))
  {
    @include_once(ROOT_PATH . 'plugins/class_latest_articles.php');
  }
  if(class_exists('LatestArticlesClass'))
  {
    $LatestArticles = new LatestArticlesClass($plugin_folder);
    $LatestArticles->DisplayContent();
    unset($LatestArticles);
  }
  unset($plugin_folder);
}
