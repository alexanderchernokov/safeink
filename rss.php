<?php
// DEFINE CONSTANTS
define('IN_PRGM', true);
define('IN_RSS', true);
define('ROOT_PATH', './');

// INIT PRGM
@require(ROOT_PATH.'includes/init.php');
@require(ROOT_PATH.'includes/enablegzip.php');
@require(ROOT_PATH.'includes/class_rss.php');

header("Content-Type: application/xml; " . $mainsettings_charset);
header('Cache-control: public');
header("Cache-Control: max-age=60");

// RSS CONDITIONS
$content   = GetVar('content', '', 'string');
$page_id   = Is_Valid_Number(GetVar('page_id', 0, 'whole_number'),0,0,9999999999);  // articles of a specific page
$forum_id  = Is_Valid_Number(GetVar('forum_id', 0, 'whole_number'),0,0,9999999999); // forum threads of a forum
$plugin_id = Is_Valid_Number(GetVar('plugin_id', 2, 'whole_number'),2,2,99999); //SD342 article plugin id
//SD343: allow SEO URL as param name instead of the category ID
$page = substr(urldecode(GetVar('page', '', 'string')),0,100);
if(strlen($page) && isset($pages_seo_arr[$page]))
{
  $page_id = $pages_seo_arr[$page];
}
unset($page);

// START RSS CLASS AND SET CONTENT TYPE
$RSS = new RSS();

$isError = false;
if(!empty($content) && ($content=='all'))
{
  $RSS->content_type = 'all';
}
else if($page_id)
{
  $RSS->content_type = 'articles';
  $RSS->page_id = $page_id;
}
else if($content == 'forum' || $forum_id)
{
  $RSS->content_type = 'forum';
  if($forum_id < 1) $forum_id = 'all';
  $RSS->forum_id = $forum_id;
}
else
{
  // all articles for entire site
  $RSS->content_type = 'articles';
  //SD342 allow for plugin id specification for clones
  if(($plugin_id<>2) && !isset($plugin_names[$plugin_id]))
    $isError = true;
  else
    $RSS->articles_pluginid = $plugin_id;
}

// DISPLAY RSS
// GetFeed -> GetContent -> (GetArticles | GetForum)
if($RSS->GetFeed($isError))
{
  $RSS->DisplayFeed();
}

if($DB->conn) $DB->close();
