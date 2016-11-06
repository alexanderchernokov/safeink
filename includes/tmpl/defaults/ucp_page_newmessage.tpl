<form id="ucpForm" class="uniForm" enctype="multipart/form-data" method="post" action="{$current_url}">
<input type="hidden" name="profile" value="{$profile}" />
<input type="hidden" name="ucp_action" id="ucp_action" value="submit" />
<input type="hidden" name="ucp_page" value="page_newmessage" />
<input type="hidden" name="submit" value="1" />
<input type="hidden" id="ucp_recipient_limit" name="msg_recipient_limit" value="{$msg_recipient_limit}" />
{$token_element}
<div class="ucp-groupheader ucp_color_light">{$page_title}</div>
<div class="ctrlHolder">{$phrases.lbl_private_subtitle}</div>
<fieldset class="inlineLabels">
<div class="ctrlHolder" style="border: 1px solid #ccc">
  {$phrases.lbl_message_type}
  <input type="radio" class="checkbox auto" id="ucp_message_private" accesskey="p" name="msg_private" value="1" {if !isset($msg_private) || !empty($msg_private)}checked="checked" {/if}/> {$phrases.lbl_private_message}
  <input type="radio" class="checkbox auto" id="ucp_message_discussion" accesskey="d" name="msg_private" value="0" {if isset($msg_private) && empty($msg_private)}checked="checked" {/if}/> {$phrases.lbl_discussion}
  <br /><span style="font-size:13px">{$phrases.lbl_private_message_hint}</span>
</div>
<div class="ctrlHolder">
  <label for="msg_recipient">&nbsp;{$phrases.lbl_search_users}</label>
  <input type="text" class="textInput auto" accesskey="s" value="" maxlength="100" size="30" id="msg_recipient" name="msg_recipient" />
</div>
<div class="ctrlHolder">
  <label for="recipients_list">&nbsp;{$phrases.lbl_selected_recipients}</label><br />
  <div class="recipients_list">
    {$recipients_list}
    <div class="ucp_clear"></div>
  </div>
  <span style="font-size:11px;padding-left:8px;">{$send_limit_hint}</span>
  {if !empty($bcc_enabled)}<a href="#" id="bcc_toggle" onclick="$('#bcc_container').toggle();return false;">BCC</a>{/if}
</div>
{if !empty($bcc_enabled)}
<div id="bcc_container" style="display: block;">
  <div class="ctrlHolder">
    <label for="msg_bbc_recipient">{$phrases.lbl_bcc}</label>
    <input type="text" class="textInput auto" value="" maxlength="100" size="30" id="msg_bbc_recipient" name="msg_bbc_recipient" />
  </div>
  <div class="ctrlHolder">
    <label for="bcc_list">{$phrases.lbl_selected_recipients}</label><br />
    <div class="recipients_list">
      {$bcc_list}
      <div class="ucp_clear"></div>
    </div>
  </div>
</div>
{/if}
<div class="ctrlHolder">
  <label for="ucp_message_title">&nbsp;{$phrases.lbl_message_title}</label>
  <input type="text" class="textInput auto" value="{if isset($form_data.title)}{$form_data.title}{/if}" maxlength="200" size="30" id="ucp_message_title" name="msg_title" style="margin: 4px;" />
</div>
<div class="ctrlHolder" style="clear:both;">
  <div style="height:20px"><label for="ucp_message_text" style="margin:0px;padding:0px;">&nbsp;{$phrases.lbl_message_text}</label></div>
  <textarea class="ucp_bbcode auto" accesskey="t" name="msg_text" id="ucp_message_text" rows="10" cols="80">{if isset($form_data.message)}{$form_data.message}{/if}</textarea>
</div>
{if !empty($group_options.enable_attachments) && !empty($group_options.attachments_max_size)}{strip}
<div class="ctrlHolder">
  <label>&nbsp;{$phrases.attachments}</label>
  <input type="file" class="textInput auto" size="30" name="attachment" style="margin: 4px;" />
  <p style="clear:both;font-size:11px">
    {if ($group_options.attachments_extensions) != '*'}{$phrases.lbl_attachment_extensions} {$group_options.attachments_extensions} || {/if}
    {$phrases.lbl_attachment_max_size} {$group_options.attachments_max_size}
  </p>
</div>
{/strip}{/if}
{if !empty($captcha_html)}<div class="ctrlHolder">{$captcha_html}</div>{/if}
<div class="ctrlHolder">
  <div id="ucp_message_options_title">
    <a id="msg_options_switch" title="{$phrases.lbl_message_options_switch}" href="#">{$phrases.lbl_message_options}</a>
  </div>
  <div id="ucp_msg_options">
    <div class="ctrlHolder">
      <label for="ucp_save_msg_copy"><strong>{$phrases.lbl_save_copy}</strong></label>
      <input type="checkbox" class="checkbox auto" id="ucp_save_msg_copy" name="msg_save_copy" value="1" checked="checked" />
      <p>{$phrases.lbl_save_copy_hint}</p>
    </div> {*
    <div class="ctrlHolder">
      <label for="ucp_message_invites"><strong>{$phrases.lbl_message_allow_invites}</strong></label>
      <input type="checkbox" class="checkbox auto" id="ucp_message_invites" name="msg_invites" value="1" />
      <p>{$phrases.lbl_message_invites_hint}</p>
    </div> *}
    <div class="ctrlHolder">
      <label for="ucp_msg_read_notify"><strong>{$phrases.lbl_request_msg_read}</strong></label>
      <input type="checkbox" class="checkbox auto" id="ucp_msg_read_notify" name="msg_read_notify" value="1" />
      <p>{$phrases.lbl_request_msg_read_hint}</p>
    </div>
  </div>
</div>
{if !empty($errors) || !empty($errortitle)}
<div class="ucp_errorMsg round_corners">
  {if !empty($errortitle)}<h3>{$errortitle}</h3>{/if}
  {if !empty($errors)}
  <ol>
  {foreach item=error from=$errors}
  <li>{$error}</li>
  {/foreach}
  </ol>
  {/if}
</div>
{/if}
<div class="buttonHolder"><button type="submit" class="primaryAction">{$phrases.btn_send_message}</button></div>
<div style="clear:both;height:4px;margin-bottom:10px"></div>
</fieldset>
</form>
