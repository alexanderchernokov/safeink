<form id="ucpForm" class="uniForm" enctype="multipart/form-data" method="post" action="{$current_url}">
<input type="hidden" name="profile" value="{$profile}" />
<input type="hidden" name="ucp_action" id="ucp_action" value="do" />
<input type="hidden" name="ucp_page" value="page_subscriptions" />
<input type="hidden" name="submit" value="1" />
{$token_element}

<div class="ucp-groupheader ucp_color_lighter">{$page_title}</div>
<fieldset class="inlineLabels">
<div id="ucp_editsave" style="display: none"></div>

<div id="subscription">
  <ol class="discussion-list">
  {foreach item=msg from=$subscriptions name=subscriptionslist}
  {strip}
  <li id="discussion-{$msg.id}">
    <a class="discussion-anchor" name="m{$msg.id}"></a>
    <div class="subscription">
      <div class="avatar-column">{$msg.data_useravatar}</div>
      <div class="message-column">
        <div class="subscription-options">
          <div style="clear:both;">
            {$phrases.lbl_notification}<br />
            <select name="email_options[{$msg.id}]" style="min-width:80px !important;">
            <option value="0"{if empty($msg.email_notify)} selected="selected"{/if}> {$phrases.option_no_email}</option>
            <option value="1"{if ($msg.email_notify==1)} selected="selected"{/if}>{$phrases.option_instant_email}</option>
            </select>
            <div style="clear:both;"></div>
          </div>
          <div style="display:block;clear: both;width:100%;">
          {$phrases.unsubscribe}<br />
          <input class="msg_check" name="selected_items[{$msg.id}]" type="checkbox" value="{$msg.id}" />
          </div><br />
        </div>
        {if $msg.type == 'comments'}
          <a href="{$msg.data_link}" class="bigger" rel="{$msg.id}"><strong>{$msg.data_title}</strong></a><br />
          {if !empty($msg.data_pagename)}{$msg.data_pagename}{else}{$msg.data_pluginname}{/if}
          {if empty($msg.data_subtitle)}<br /><br /><strong>No comments yet.</strong>
          {else}
            <br /><br />{$phrases.lbl_latest_comment_by}
            <a class="msg_sender" href="{$msg.data_userlink}">{$msg.data_username}</a>{if !empty($msg.data_date)}, <span class="date">{$msg.data_date}</span><br />{/if}
            <div class="msg_text">{$msg.data_subtitle}</div>{/if}{/if}
        {if $msg.type == 'forum'}<strong>{$msg.data_pluginname}:</strong> <a href="{$msg.data_link}" class="bigger" rel="{$msg.id}"><strong>{$msg.data_title}</strong></a><br />
          {if !empty($msg.data_subtitle)}{$phrases.lbl_latest_topic} <a class="msg_sender" href="{$msg.data_sublink}">{$msg.data_subtitle}</a><br />
            {$phrases.lbl_created_by} <a class="msg_sender" href="{$msg.data_userlink}">{$msg.data_username}</a><br />{/if}
          {if !empty($msg.data_date)} <span class="date">{$msg.data_date}</span>{/if}{/if}
        {if $msg.type == 'topic'}<strong>{$phrases.lbl_topic}</strong> <a href="{$msg.data_link}" class="bigger" rel="{$msg.id}"><strong>{$msg.data_title}</strong></a><br />
          in <a href="{$msg.data_sublink}" rel="{$msg.id}">{$msg.data_subtitle}</a><br />
          {if !empty($msg.data_username)}{$phrases.lbl_latest_post} <a class="msg_sender" href="{$msg.data_userlink}">{$msg.data_username}</a><br />{/if}
          {if !empty($msg.data_date)} <span class="date">{$msg.data_date}</span>{/if}{/if}
      </div>
    </div>
  </li>
  {/strip}
  {foreachelse}
  <p class="subscriptions_master"><strong>{$phrases.status_no_subscriptions}</strong></p>
  {/foreach}
  </ol>
</div>
{if $smarty.foreach.subscriptionslist.last && !empty($subscriptions)}
  <div class="msg_operations ucp_color_bglighter">
    <input type="submit" id="msg_op_submit2" value="{$phrases.lbl_options_go}" class="button" style="float: right;" />
  </div>
{/if}

{if !empty($pagination)}
<div id="discussions_pagination">{$pagination}</div>
{/if}

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
</fieldset>
</form>
