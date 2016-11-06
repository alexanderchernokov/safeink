<?php
if(empty($_SERVER['HTTP_USER_AGENT'])) exit();

define('IN_PRGM', true);
define('ROOT_PATH', '../../');
require(ROOT_PATH . 'includes/init.php');

if(!$pluginid = GetPluginID('Forum')) exit();
$categoryid = GetPluginCategory($pluginid,0);
$isAdmin = !empty($userinfo['adminaccess']) || in_array($pluginid, $userinfo['pluginadminids']);

if(!Is_Ajax_Request() || empty($categoryid) || empty($userinfo['loggedin']) || !empty($userinfo['banned']) ||
   (!$isAdmin &&
    !@in_array($pluginid, $userinfo['pluginviewids']) &&
    !@in_array($categoryid, $userinfo['categoryviewids']))
  )
{
  echo 'Restricted!';
  exit();
}

$action = GetVar('forum_action', '', 'string', false, true);
require(ROOT_PATH.'plugins/forum/forum_config.php');

// Handle all forum posts "Likes" actions first:
if(in_array($action, array('like_post','dislike_post','get_likes','get_dislikes','remove_like')))
{
  require(SD_INCLUDE_PATH.'class_sd_likes.php');

  if(!CheckFormToken(FORUM_TOKEN)) die($sdlanguage['error_invalid_token']);
  $fc = new SDForumConfig();
  $fc->InitFrontpage();

  if(($action=='like_post') || ($action=='dislike_post'))
  {
    if(!empty($fc->user_likes_perm))
    {
      $res = $fc->DoLikePost($action=='like_post');
      if($res !== true)
      {
        die('Error '.(string)$res);
      }
    }
    else
      die('Not allowed!');
  }
  else
  if(($action=='get_likes') || ($action=='get_dislikes'))
  {
    if(!empty($fc->plugin_settings_arr['display_likes_results']))
    {
      echo $fc->GetPostLikes(true).' '.$fc->GetPostLikes(false);
    }
  }
  else
  if($action=='getpostlikeslinks')
  {
    if(!empty($fc->plugin_settings_arr['enable_like_this_post']))
    {
      $fc->GetPostLikesLinks();
    }
  }
  else
  if($action=='remove_like')
  {
    if(!empty($fc->user_likes_perm))
    {
      $fc->DoLikePost(SD_LIKED_TYPE_POST_REMOVE);
    }
  }
  unset($fc);
  exit();
}

$userid = Is_Valid_Number(GetVar('userid', 0, 'whole_number', false, true),0,1,99999999);
if(empty($userid) || (!$isAdmin && !@in_array($categoryid, $userinfo['categoryviewids'])))
{
  echo 'Restricted!';
  exit();
}

$lang = GetLanguage($pluginid);
$settings = GetPluginSettings($pluginid);
SDUserCache::$img_path    = $sdurl.'plugins/forum/images/';
SDUserCache::$lbl_offline = $lang['user_offline'];
SDUserCache::$lbl_online  = $lang['user_online'];
SDUserCache::$lbl_open_profile_page = $lang['open_your_profile_page'];
SDUserCache::$lbl_view_member_page  = $lang['view_member_page'];
SDUserCache::$show_avatars          = !empty($settings['display_avatar']) &&
                                      (!isset($userinfo['profile']['user_view_avatars']) ||
                                      !empty($userinfo['profile']['user_view_avatars']));

if(!$self = SDUserCache::CacheUser($userinfo['userid'], '', true, false)) exit();
if(!$user = SDUserCache::CacheUser($userid, '', true, false)) exit();
$userdata = sd_GetForumUserInfo(1,$userid,true,array('user_post_count','user_thread_count','user_allow_pm'));
if(empty($self['valid']) || !$self['valid'] || empty($user['valid']) || !$user['valid']) exit();

echo '<ul class="useroptions">
<li class="author-name"><strong>'.$user['profile_link'].'</strong></li>';
if(!empty($user['user_title']))
{
  echo '<div class="author-title" style="vertical-align:top"><strong>'.$user['user_title'].'</strong></div>';
}

if(!empty($userinfo['loggedin']) && !empty($userinfo['userid']) && !empty($user['username']))
{
  if($isAdmin || (!empty($userdata['user_allow_pm']) && isset($userdata['usergroup_details']) &&
     !empty($userdata['usergroup_details']['msg_enabled']) && !empty($self['usergroup_details']['msg_enabled'])))
  {
    include_once(SD_INCLUDE_PATH.'class_userprofile.php'); //SD343
    SDProfileConfig::init(11); //SD343
    $newmsg = ConvertNewsTitleToUrl(SDProfileConfig::$phrases['page_newmessage_title'], null, null, true);
    //SD370: url param as hashtag for ajax
    echo '<li class="send-message"><a class="profilelink" href="'.$self['link'].
         '&amp;recipientid='.$userid.(SD_370?'#':'&amp;').'do='.$newmsg.'">'.
         SDProfileConfig::$phrases['page_newmessage_title'].'</a></li>';
  }
  if(!empty($user['username']))
  {
    $searchlink = RewriteLink('index.php?categoryid='.$categoryid.'&searchusers=1&forum_search='.urlencode($user['username']).FORUM_URL_TOKEN);
    echo '<li class="search-user"><a rel="nofollow" href="'.$searchlink.'">'.$lang['search_users_posts'].'</a></li>';
  }
}

if(!empty($user['user_allow_viewonline'])) echo '<li class="online-status">'.$user['online_img'].'</li>';

if(!empty($user['post_count']))
{
  echo '<li class="author-stats">'.$lang['posts'] . ': ' .number_format($user['post_count']).'</li>';
}
if(!empty($user['thread_count']))
{
  echo '<li class="author-stats">'.$lang['topics'] . ': ' .number_format($user['thread_count']).'</li>';
}

if(!empty($user['lastactivity'])) echo '<li class="lastactivity">'.$lang['last_visit'].' '.DisplayDate($user['lastactivity'], '', true).'</li>';

if(!empty($user['joindate'])) echo '<li class="author-joined">'.$lang['joined'] . ' '. DisplayDate($user['joindate'], 'Y-m-d', true).'</li>';

if(!empty($user['usergroup_details']) && is_array($user['usergroup_details']))
{
  $groupname = !empty($user['usergroup_details']['displayname'])?$user['usergroup_details']['displayname']:'<div class="usergroup">'.$user['usergroup_details']['name'];
  $groupname = empty($user['usergroup_details']['color_online'])?$groupname:'<span style="color:#'.$user['usergroup_details']['color_online'].'">'.$groupname.'</span>';
  echo '<li class="usergroup">'.$groupname.'</li>';
}

if($isAdmin)
{
  if(!empty($settings['display_user_ip']) && !empty($user['ipaddress']))
  {
    echo '<li class="author-ip">IP: <a rel="nofollow" target="_blank" href="http://www.projecthoneypot.org/ip_'.urlencode($user['ipaddress']).'">'.$user['ipaddress'].'</a></li>';
  }
  $action = '&forum_action=topic_options&topic_action=moderation_user_confirm&moderation_user_confirm=0&moderation=1&topic_id=xxx';
  echo '<li class="moderate-user"><a rel="nofollow" href="'.RewriteLink('index.php?categoryid='.$categoryid.$action.'&user_id='.$userid.FORUM_URL_TOKEN).'">'.
       $lang['topic_options_moderate_user'].'</a></li>';
}
echo '</ul>';

$DB->close();
exit();