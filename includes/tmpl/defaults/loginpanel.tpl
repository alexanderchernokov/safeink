{* Smarty Template for SD Login Panel 2012-09-02 *}
<!-- Login Panel -->
<div class="login-panel" id="login-panel">
{if $loggedin && empty($login_errors)}
  {if $display_avatar}<div class="login-avatar">{$userdata.avatar}</div>{/if}
  <div class="login-welcome">{$phrases.welcome_back} {$userdata.username}
  {if !empty($userdata.usergroup_details.displayname)}<br />
  <span{$userdata.color}>{$userdata.usergroup_details.displayname}</span>
  {/if}
  </div>
  {if !$ucp_basic && $settings.display_profile_link}
  <a class="login-link" href="{$userdata.link}">{$phrases.my_account}</a><br />
  {/if}
  {if $settings.display_admin_link && $can_admin}
  <a class="login-link" href="{$admin_link}">{$phrases.admin_panel}</a><br />
  {/if}
  <a class="login-link" href="{$logout_link}">{$phrases.logout}</a><br />
  {if !empty($can_show_pm) && !empty($private_messages_code)}{$private_messages_code}{/if}
  {if !empty($private_messages_code)}{$private_messages_code}{/if}
{else}
  {if $login_errors}<div id="error_message">{$login_errors}</div><br />{/if}
  <form class="login-form" action="{$form_post_link}" method="post">
  {$SecurityFormToken}
  <input type="hidden" name="login" value="login" />
  <div class="login-div-user1">{$phrases.username}</div>
  <div class="login-div-user2"><input name="loginusername" type="text" maxlength="{$max_username_length}" /></div>
  <div class="login-div-pwd1">{$phrases.password}</div>
  <div class="login-div-pwd2"><input name="loginpassword" type="password" maxlength="30" /></div>
  <div class="login-div-remember">{$phrases.remember_me} <input type="checkbox" name="rememberme" checked="checked" value="1" /></div>
  <div class="login-div-submit"><input type="submit" name="loginsubmit" value="{$login_button_text}" /></div>
  </form>
  {if $register_link && !empty($settings.display_register_link)}
  <div id="login-div-register">{$phrases.not_registered} <a href="{$register_link}">{$phrases.register_now}</a></div>
  {/if}
  {if $lostpwd_link && !empty($settings.display_forgot_password_link)}
  <div id="login-div-lostpwd"><a href="{$lostpwd_link}">{$phrases.forgot_password}</a></div>
  {/if}
{/if}
</div>