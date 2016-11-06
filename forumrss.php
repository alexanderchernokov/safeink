<?php
// DEFINE CONSTANTS
define('IN_PRGM', true);
define('ROOT_PATH', './');

// INIT PRGM
@require(ROOT_PATH.'includes/init.php');
if(empty($mainsettings['enable_rss_forum']))
{
  echo $sdlanguage['rss_not_available'];
  exit();
}
@require(ROOT_PATH.'includes/enablegzip.php');
@require(ROOT_PATH.'includes/class_rss.php');

// ############################################################################
// RSS CONDITIONS
// ############################################################################

$content  = GetVar('content', '', 'string');
$forum_id = Is_Valid_Number(GetVar('forum_id', 0, 'whole_number'),0,1,99999999);
if($forum_ids = GetVar('forumids', '', 'string'))
{
  $forum_ids = sd_ConvertStrToArray($forum_ids);
}

// ############################################################################
// START RSS CLASS AND SET CONTENT TYPE
// ############################################################################

$RSS = new RSS();

$RSS->rss_self_link = SITE_URL . 'forumrss.php';
$RSS->content_type = 'forum';
$RSS->forum_id = 'all';
if(!empty($forum_ids) && count($forum_ids))
{
  $RSS->forum_id = '';
  $RSS->forum_ids = $forum_ids;
}
else
if($forum_id) $RSS->forum_id = $forum_id;

// DISPLAY RSS
header("Content-Type: application/xml; " . $mainsettings_charset);
header('Cache-control: public');
header("Cache-Control: max-age=60");
if($RSS->GetFeed()) $RSS->DisplayFeed();

// CLOSE DB CONNECTION
if($DB->conn) $DB->close();
