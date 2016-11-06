<form id="ucpForm" class="uniForm" enctype="multipart/form-data" method="post" action="{$current_url}">
<input type="hidden" name="profile" value="{$profile}" />
<input type="hidden" name="ucp_action" id="ucp_action" value="do" />
<input type="hidden" name="ucp_page" value="page_viewmessage" />
<input type="hidden" name="submit" value="1" />
<input type="hidden" name="msg_recipient_limit" value="{$msg_recipient_limit}" />
{$token_element}
{if !empty($data.msg_id)}{if empty($quote_private)}
<input type="hidden" name="reply_to_id" value="{$data.msg_id}" />
{else}
<input type="hidden" name="msg_private" value="1" />
<input type="hidden" name="p_reply_to_id" value="{$data.msg_id}" />
<input type="hidden" name="p_recipient_id" value="{$data.userid}" />
{/if}{/if}
{if !empty($master_id)}
<input type="hidden" name="d" value="{$master_id}" />
<div class="discussion_reply_header">
  {if !empty($quote_private)}
  <div class="header_title">{$phrases.lbl_quote_as_private}</div>
  {$phrases.lbl_private_quote}
  {else}
  <span style="font-size: small">{$phrases.lbl_discussion}</span><br />
  <div class="header_title">{$discussion_title}</div>
  {/if}
</div>
<div style="clear: both; height: 6px;"> </div>
{else}
  {if !empty($quote_private)}
  <div class="discussion_reply_header round_corners">
    <div class="header_title">{$phrases.lbl_quote_as_private}</div>
    {$phrases.lbl_private_quote}
  </div>
  {else}
  <div class="ucp-groupheader ucp_color_light">{$page_title}</div>
  {/if}
  <div style="clear: both; height: 6px;"> </div>
{/if}
{if !empty($data.msg_id)}
  {if !empty($quote_private)}{$phrases.lbl_quoted_message}{/if}
  <div class="{if $isreply}reply_header{else}message_header{/if}">
    <div class="header_title">{$data.msg_title|default:$phrases.untitled}</div>
    <div style="display: inline-block; margin-top: 4px;">
      {$phrases.lbl_sent_by} <a class="msg_sender" href="{$data.msg_sender_link}">{$data.username}</a><br />
      {* For BCC messages, display original recipient's name *}
      {if empty($isbcc)}
        {if !empty($isreply)}{$phrases.lbl_recipient}
        {if !empty($data.msg_recipients_list)}{$data.msg_recipients_list}{else}
        <a class="msg_sender" href="{$data.msg_recipient_link}">{$data.recipient_name}</a><br />{/if}
        {/if}
      {else}
        {$phrases.lbl_recipient} <a class="msg_sender" href="{$org_recipient_link}">{$org_recipient_name}</a> (BCC)<br />
      {/if}
    </div>
    <div style="display: inline-block; float:right; margin-top: 4px; text-align: right;">
      {$phrases.lbl_message_sent} <strong>{$data.msg_date_text_nobr}</strong>
    </div>
    <br />
    <div class="bigger" style="margin-bottom: 4px; margin-top: 8px;">{$phrases.lbl_message_text}</div>
    <div class="msg_text">{$data.msg_text}</div>
    {if !empty($attachments)}
    <div class="bigger" style="margin-bottom: 4px; margin-top: 8px;">{$phrases.attachments}</div>
    <div class="msg_attachments">{$attachments}</div>{/if}
  </div>
{/if}
<fieldset class="inlineLabels">
{if !empty($group_options.msg_enabled)}
{if !empty($master_id)}
  <div class="bigger">&nbsp;&nbsp;{if empty($data.msg_id)}{$phrases.lbl_add_message_hint}{else}{$phrases.lbl_reply_to_message}{/if}</div>
  {if !empty($allow_invites)}
    <div class="ctrlHolder">
      <label for="msg_recipient">&nbsp;{$phrases.invite_others_discussion}</label>
      <input type="text" class="textInput auto" value="" maxlength="50" size="30" id="msg_recipient" name="msg_recipient" />
    </div>
    <div class="ctrlHolder">
      <label for="recipients_list">&nbsp;{$phrases.lbl_selected_recipients}</label><br />
      <div class="recipients_list">{$recipients_list}
        <div style="clear: both; height: 1px"> </div>
      </div>
    </div>
  {/if}
{else}
  {* Private Quote a discussion message or viewing a sent message *}
  {if !empty($data.msg_id) && (!empty($isreply) || !empty($quote_private))}
    <div class="ctrlHolder ucp-groupheader">
      <div class="bigger">{$phrases.lbl_your_reply}</div>
    </div>
    <div class="ctrlHolder">
      <label for="msg_recipient">&nbsp;{$phrases.additional_recipients}</label>
      <input type="text" class="textInput auto" value="" maxlength="50" size="30" id="msg_recipient" name="msg_recipient" />
    </div>
    <div class="ctrlHolder">
      <label for="recipients_list">&nbsp;{$phrases.lbl_selected_recipients}</label><br />
      <div class="recipients_list">{$recipients_list}
        <div style="clear: both; height: 1px"> </div>
      </div>
    </div>
  {/if}
  {if !$quote_message && empty($quote_private)} {* "BCC" NOT for quote/reply to discussion messages *}
    <div class="ctrlHolder{if empty($isreply)} ucp-groupheader{/if}">
      {if empty($isreply)}<div class="bigger">{$phrases.lbl_your_reply}</div>{/if}
      {if !empty($bcc_enabled)}<a href="#" id="bcc_toggle" onclick="$('#bcc_container').toggle();return false;">BCC</a>{/if}
    </div>
    {if !empty($bcc_enabled)}
    <div id="bcc_container" class="ctrlHolder ucp-color-light">
      <label for="msg_recipient">{if empty($data.msg_id)}{$phrases.lbl_recipient}{else}{$phrases.lbl_bcc}{/if}</label><br />
      <input type="text" class="textInput auto" value="" maxlength="100" size="30" id="msg_bbc_recipient" name="msg_recipient" />
      <br />
      <label for="bcc_list" style="clear:both;margin-bottom:6px">{$phrases.lbl_selected_recipients}</label>
      <div class="recipients_list" style="clear:both">
        {$bcc_list}
        <div style="clear: both; height: 1px"> </div>
      </div>
      {$send_limit_hint}
    </div>
    {/if}
  {/if}
{/if}
<div class="ctrlHolder">
  <label for="ucp_message_title">&nbsp;{$phrases.lbl_message_title}</label>
  <input type="text" class="textInput auto" value="{if isset($form_data.title)}{$form_data.title}{/if}" maxlength="200" size="50" id="ucp_message_title" name="msg_title" style="margin: 4px;" />
</div>
<div class="ctrlHolder">
  <label for="ucp_message_text">&nbsp;{$phrases.lbl_message_text}</label><br />
</div>
<div class="ctrlHolder">
<textarea class="ucp_bbcode auto" name="msg_text" id="ucp_message_text" rows="10" cols="80" style="margin-top: 4px;">{strip}
{/strip}{if isset($form_data.message)}{$form_data.message}{else}{strip}
{/strip}{if !empty($quote_message) || !empty($quote_private)}{$data.msg_text_raw}{/if}{/if}</textarea>
</div>
{if !empty($group_options.enable_attachments) && !empty($group_options.attachments_max_size)}{strip}
<div class="ctrlHolder">
  <label>&nbsp;{$phrases.attachments}</label>
  <input type="file" class="textInput auto" size="30" name="attachment" style="margin: 4px;" />
  <p style="clear:both;font-size: 11px;padding-left:8px;">
    {if ($group_options.attachments_extensions) != '*'}{$phrases.lbl_attachment_extensions} {$group_options.attachments_extensions} || {/if}
    {$phrases.lbl_attachment_max_size} {$group_options.attachments_max_size}
  </p>
</div>
{/strip}{/if}
{/if}
{if !empty($captcha_html)}{$captcha_html}{/if}
{if !empty($errors) || !empty($errortitle)}<div style="margin: 8px">
<div class="ucp_errorMsg round_corners">
  {if !empty($errortitle)}<h3>{$errortitle}</h3>{/if}
  {if !empty($errors)}<ol>{foreach item=error from=$errors}<li>{$error}</li>{/foreach}</ol>{/if}
</div></div>
{/if}
{if empty($master_id) && !empty($group_options.msg_enabled)}<br />
  <div id="ucp_message_options_title">
    <a id="msg_options_switch" title="{$phrases.lbl_message_options_switch}" href="#">{$phrases.lbl_message_options}</a>
  </div>
  <div id="ucp_msg_options">
  {if empty($quote_private)}
    {if !empty($data.msg_id)}
    <div class="ctrlHolder">
      <label for="ucp_message_private"><strong>{$phrases.lbl_private_message}</strong></label>
      <input type="checkbox" class="checkbox auto" id="ucp_message_private" name="msg_private" value="1" {if empty($master_id)}checked="checked"{/if} />
      <p>{$phrases.lbl_private_message_hint}</p>
    </div>
    {/if}
    {if empty($quote_private)}
    <div class="ctrlHolder">
      <label for="ucp_message_invites"><strong>{$phrases.lbl_message_allow_invites}</strong></label>
      <input type="checkbox" class="checkbox auto" id="ucp_message_invites" name="msg_invites" value="1" />
      <p>{$phrases.lbl_message_invites_hint}</p>
    </div>
    {/if}
  {/if}
    {if !empty($allow_delete)}
    <div class="ctrlHolder">
      <label for="ucp_save_msg_copy"><strong>{$phrases.lbl_save_copy}</strong></label>
      <input type="checkbox" class="checkbox auto" id="ucp_save_msg_copy" name="msg_save_copy" value="1" checked="checked" />
      <p>{$phrases.lbl_save_copy_hint}</p>
    </div>
    {/if}
    <div class="ctrlHolder">
      <label for="ucp_msg_read_notification"><strong>{$phrases.lbl_request_msg_read}</strong></label>
      <input type="checkbox" class="checkbox auto" id="ucp_msg_read_notification" name="msg_read_notify" {if !empty($read_notify)}checked="checked" {/if}value="1" />
      <p>{$phrases.lbl_request_msg_read_hint}</p>
    </div>
  </div>
{/if}
<div class="buttonHolder"><button type="submit" class="primaryAction">{$phrases.btn_send_message}</button></div>
<div style="clear:both;height:1px;margin-bottom:1px"></div>
</fieldset>
</form>

{if !empty($data.msg_id) && !empty($allow_delete)}
<form id="ucpDelForm" class="uniForm" enctype="multipart/form-data" method="post" action="{$current_url}">
<input type="hidden" name="profile" value="{$profile}" />
<input type="hidden" name="ucp_action" value="delete" />
<input type="hidden" name="ucp_page" value="page_viewmessage" />
<input type="hidden" name="submit" value="1" />
{$token_element}
<fieldset class="inlineLabels">
<div class="ucp_color_dark" style="margin-top:5px">
<div class="ctrlHolder">
  <label for="ucp_message_delete"><strong>{$phrases.lbl_delete_message}</strong></label>
  <input type="checkbox" class="checkbox auto" id="ucp_message_delete" name="msg_delete" value="1" />
  <p>{$phrases.lbl_delete_message_hint2}</p>
</div>
<div class="buttonHolder" style="text-align:center"><button type="submit" class="primaryAction">{$phrases.btn_delete_message}</button></div>
<div style="clear:both;height:1px;margin-bottom:1px"></div>
</div>
</fieldset>
</form>
{/if}
