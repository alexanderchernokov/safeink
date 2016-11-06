{if empty($master_id)}<strong>Sorry, discussion unavailable!</strong><br />{else}
<form id="ucpForm" class="uniForm" enctype="multipart/form-data" method="post" action="{$current_url}">
<input type="hidden" name="profile" value="{$profile}" />
<input type="hidden" name="ucp_action" id="ucp_action" value="do" />
<input type="hidden" name="ucp_page" value="page_viewdiscussion" />
<input type="hidden" name="d" value="$master_id}" />
<input type="hidden" name="submit" value="1" />
{$token_element}

<div class="ucp-groupheader ucp_color_lighter">{$page_title}</div>
<fieldset class="inlineLabels">
<div id="ucp_editsave" style="display: none"></div>
<table class="discussiontbl" border="0" cellpadding="0" cellspacing="0" width="100%">
{foreach item=master from=$discussions.messages name=discussionslist}
<tbody class="discussion_master{if empty($master.approved)} is_unapproved{/if}">
  <tr>
    <td align="left" class="mastercol lborder">
      <a class="msg_title{if empty($master.user_last_read) || ($master.user_last_read < $master.last_msg_raw)} msg_unread{/if}" href="{$page_url}&amp;do={$seo.page_viewdiscussion}&amp;d={$master_id}" title="{$phrases.lbl_read_discussion|escape}"><strong>{$master.title|default:$phrases.untitled}</strong></a>
      <br style="clear: both; margin-bottom: 4px" />
      {$phrases.lbl_sent_by} <a class="msg_sender" href="{$master.starter_link}">{$master.started_by}</a>
      <br style="clear: both; margin-bottom: 4px" />
      {$phrases.participants}
      {foreach item=msg_user from=$master.users name=master_users}
        {if $smarty.foreach.master_users.iteration <= 3}
        <a class="msg_sender" href="{$msg_user.link}">{$msg_user.name}</a>{strip}
        {/strip}{if $smarty.foreach.master_users.iteration <= 2}{if !$smarty.foreach.master_users.last},{/if}{/if}
        {/if}
      {/foreach}
      {if !empty($msg_more_participants)}{$msg_more_participants}{/if}
    </td>
    <td align="center" class="lborder" width="70">
      <span class="master_replies">{$phrases.lbl_replies} {$master.message_count-1}</span><br />
      <span class="master_views">{$phrases.lbl_views} {$master.views}</span>
    </td>
    <td align="right" class="datecol lborder" width="150">
      {$master.started_date}
      {if empty($master.is_closed)}
      <div style="clear:both; display:block;position:absolute;right:0;padding:0; margin: 8px 0 8px 0; text-align:right; vertical-align:bottom;">
        <a class="button" href="{$page_url}&amp;do={$seo.page_newmessage}&amp;d={$master_id}" title="{$phrases.lbl_add_message_hint}">{$phrases.lbl_add_message}</a>
      </div>
      <div style="clear:both;height:30px"> </div>
      {else}
      <br /><strong>{$phrases.lbl_discussion_closed}</strong>
      {/if}
    </td>
    <td align="center" class="lborder rborder" width="20"><input id="checkall" name="checkall" type="checkbox" value="1" /></td>
  </tr>
</tbody>
</table>

{if !empty($discussions.pagination)}
<div id="discussions_pagination">{$discussions.pagination}</div>
{/if}

<div id="discussion" style="width:100%">
  <ol class="discussion-list">
  {foreach item=msg from=$master.messages}
  {strip}
  <li id="discussion-{$msg.msg_id}">
    <a class="discussion-anchor" name="m{$msg.msg_id}"></a>
    <div class="discussion{if empty($msg.approved)} is_unapproved{/if}">
      <div class="avatar-column">{$msg.msg_avatar}</div>
      <div class="message-column">
        <div id="madmin-{$msg.msg_id}" class="discussion-admin">
          {if !empty($AdminAccessxx)}{strip}
          <a class="msg-delete" title="{$phrases.message_delete}" href="includes/ajax/sd_ajax_messages.php?do=deletemessage&amp;id={$msg.msg_id}&amp;securitytoken={$securitytoken}"></a>
          <a class="msg-edit" title="{$phrases.message_edit}" target="{$msg.msg_id}" href="includes/ajax/sd_ajax_messages.php?do=editmessage&amp;id={$msg.msg_id}&amp;securitytoken={$securitytoken}">&nbsp;</a>&nbsp;
          {/strip}{/if}
          <a href="{$page_url}&amp;do={$seo.page_viewmessage}&amp;d={$master_id}&amp;id={$msg.msg_id}&amp;p=1" class="imglink messageprivate imgsmall" rel="{$msg.msg_id}" title="{$phrases.lbl_private_quote}"></a>
          {if empty($master.is_closed)}<a href="{$page_url}&amp;do={$seo.page_viewmessage}&amp;d={$master_id}&amp;id={$msg.msg_id}&amp;q=1" class="imglink messagequote imgsmall" rel="{$msg.msg_id}" title="{$phrases.lbl_quote_hint}"></a>&nbsp;&nbsp;{/if}
          <input class="msg_check" name="selected_items[{$msg.msg_id}]" type="checkbox" value="{$msg.msg_id}" />
        </div>
        <a class="msg_sender" href="{$msg.msg_sender_link}">{$msg.username}</a> | <span class="date">{$msg.msg_date_text_nobr}</span><br />
        {$phrases.lbl_message_title} <a title="{$phrases.lbl_reply_to_message|escape}" href="{$page_url}&amp;do={$seo.page_viewmessage}&amp;d={$master_id}&amp;id={$msg.msg_id}" class="viewmessagelink{if empty($master.user_last_read) || ($master.user_last_read < $msg.msg_date_raw)} msg_unread{/if}" rel="{$msg.msg_id}">{$msg.msg_title|truncate:25:"...":true}</a>
        <div id="discussion-p-{$msg.msg_id}" class="msg_text">{$msg.msg_text}</div>
        {if !empty($msg.attachments)}{strip}
        <div class="attachments-container" style="margin-bottom: 4px; margin-top: 8px;">{$phrases.attachments}<br />{$msg.attachments}</div>
        {/strip}{/if}
      </div>
    </div>
  </li>
  {/strip}
  {/foreach}
  </ol>
</div>

{if !empty($discussions.pagination)}
<div id="discussions_pagination">{$discussions.pagination}</div>
{/if}

{if $smarty.foreach.discussionslist.last && !empty($discussions.options)}
  <div class="msg_operations ucp_color_bglighter">
    {if empty($master.is_closed)}
    <div style="float:left; padding: 6px 0 0 0; margin-left: 8px; text-align: left; vertical-align: bottom;">
      <a class="button" href="{$page_url}&amp;do={$seo.page_newmessage}&amp;d={$master_id}" title="{$phrases.lbl_add_message_hint}">{$phrases.lbl_add_message}</a>
    </div>
    {/if}
    <div class="msg_operations_title">{$phrases.lbl_discussion_options}</div>
    <input type="submit" id="msg_op_submit" value="{$phrases.lbl_options_go} (0)" class="button" disabled="disabled" style="float: right;" />
    <select id="msg_operation" name="msg_operation">
    <option value="0" selected="selected">{$phrases.options_select}</option>
    {foreach item=entry from=$discussions.options}
    <option value="{$entry.val}">{$entry.phrase}</option>
    {/foreach}
    </select>
  </div>
{/if}
{foreachelse}
<p class="discussion_master"><strong></strong>{$phrases.status_no_messages}</strong></p>
{/foreach}

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
{/if}