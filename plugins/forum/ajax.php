<?php
if(empty($_SERVER['HTTP_USER_AGENT'])) exit();

define('IN_PRGM', true);
define('ROOT_PATH', '../../');
require(ROOT_PATH . 'includes/init.php');

if(!$pluginid = GetPluginID('Forum')) exit();

$categoryid = GetPluginCategory($pluginid,0);
$action = GetVar('forum_action', '', 'string', true, false);
$topic_id = Is_Valid_Number(GetVar('topic_id', '', 'string', true, false),0,1,99999999);
$topic_view = GetVar('topic_view', '', 'string', true, false);
$isAdmin = !empty($userinfo['adminaccess']) || in_array($pluginid, $userinfo['pluginadminids']);
#$userid = Is_Valid_Number(GetVar('userid', 0, 'whole_number', true, false),0,1,99999999);

if(!Is_Ajax_Request() || empty($categoryid) ||
   empty($userinfo['loggedin']) || !empty($userinfo['banned']) ||
   (!$isAdmin &&
    !@in_array($pluginid, $userinfo['pluginviewids']) &&
    !@in_array($categoryid, $userinfo['categoryviewids']))
  )
{
  echo -1;
  exit();
}

$success = false;

if(CheckFormToken())
{
  #$lang = GetLanguage($pluginid);
  #$settings = GetPluginSettings($pluginid);

  require(ROOT_PATH.'plugins/forum/forum_config.php');
  $forum_config = new SDForumConfig();
  if($forum_config->InitFrontpage(false))
  {
    $title = trim(GetVar('topic_title', '', 'html', true, false));
    if(($forum_config->IsAdmin || $forum_config->IsSiteAdmin) &&
       ($action == 'rename-topic') && !empty($topic_id) &&
       empty($topic_view) || (strlen($title) > 2))
    {
      $DB->query("UPDATE {p_forum_topics} SET title = '%s'".
                 ' WHERE topic_id = %d',
                 $DB->escape_string($title), $topic_id);
      $success = 1;
      if($topic_view=='forum_list')
      {
        echo $forum_config->GetTopicTitleCell($title);
      }
      else
      {
        echo $title;
      }
    } else echo "Error: Check failed! ".$topic_id;
  } else echo "Error: Init failed! ".$topic_id;
}

if(!$success) echo -1;
$DB->close();
exit();