<?php

if(!defined('IN_PRGM')) return;

$loginpanel_settings = GetPluginSettings(10);
$loginpanel_language = GetLanguage(10);
$p11_language = GetLanguage(11);

if(!empty($userinfo['loggedin']))
{
  include_once(SD_INCLUDE_PATH.'class_messaging.php');
  $msg = SDProfileConfig::GetMsgObj(); // must have! initializes objects if need be!
  $unread_count = SDMsg::getMessageCounts($userinfo['userid'], SDMsg::MSG_INBOX | SDMsg::MSG_STATUS_UNREAD);
  echo '
  <div id="member-bar" style="clear:both;padding:4px;width:auto;">
  <div class="datetime" style="display:inline;float:right;right:4px;">'. DisplayDate(TIME_NOW, null, true).'</div>
  ';
  if($usersystem['name'] == 'Subdreamer')
  {
    if(defined('CP_PATH') && CP_PATH)
    {
      echo '
      <a class="login-link" href="'.CP_PATH.'">'.$loginpanel_language['my_account'].': '.$userinfo['username'].'</a> ';
      if(!empty($unread_count))
      echo '
      <a class="login-link" href="'.CP_PATH.'?profile='.$userinfo['userid'].'#do=view-messages"><span style="color:red">'.$unread_count.' unread message(s)</span></a> ';
    }
  }
  else
  if(function_exists('ForumLink'))
  {
    echo '<a href="' . ForumLink(2, $userinfo['userid']) . '" title="'.$p11_language['visit_cp'].'">' . $loginpanel_language['my_account'] . '</a> ';
  }

  if($loginpanel_settings['display_admin_link'] &&
     (!empty($userinfo['adminaccess']) || !empty($userinfo['pluginadminids']) || !empty($userinfo['custompluginadminids'])))
  {
    echo ' | <a class="login-link" href="'.ROOT_PATH.ADMIN_PATH.'/pages.php" target="_blank">' . $loginpanel_language['admin_panel'] . '</a>';
  }
  echo ' | <strong><a class="login-link" href="'.RewriteLink('&logout=1').'">'.$loginpanel_language['logout'].'</a></strong>
  </div>';
}
else
{
  $msg = 'Only members can post or search in this forum. ';
  if($forum_id = GetPluginID('Forum'))
  {
    $forum_language = GetLanguage($forum_id);
    if(isset($forum_language['err_member_access_only']))
      $msg = $forum_language['err_member_access_only'].' ';
  }
  echo '
  <!-- Member Bar Login Panel -->
  <div id="member-bar" style="padding: 4px;">';
  if(defined('REGISTER_PATH') && REGISTER_PATH)
  {
    echo '
    <div style="display: block; margin: 4px 8px 2px 0;">
      '.$msg.'
      <a href="'.REGISTER_PATH.'" style="text-decoration: underline;">Sign up</a> for a new account or login now.
    </div>';
  }
  echo '
    <form action="'.RewriteLink().'" method="post"> <input name="login" value="login" type="hidden" />
    '.PrintSecureToken().'
  '.$loginpanel_language['username'].' <input name="loginusername" maxlength="20" type="text" size="10" /> &nbsp;
  '.$loginpanel_language['password'].' <input name="loginpassword" maxlength="30" type="password" size="10" /> &nbsp;
    <input class="checkbox" name="rememberme" checked="checked" value="1" type="checkbox" /> '.$loginpanel_language['remember_me'].' &nbsp;
    <input name="Submit now" value="'.htmlspecialchars($loginpanel_language['login'], ENT_QUOTES).'" type="submit" />
    </form>';
  if(!empty($login_errors_arr))
  {
    echo '<br />';
    DisplayMessage($login_errors_arr, true);
  }
  echo '
    <div style="clear:both;display:block;height:1px;"> </div>
  </div>';
}
unset($loginpanel_settings, $loginpanel_language, $msg);
