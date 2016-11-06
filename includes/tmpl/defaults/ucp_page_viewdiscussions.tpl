<form id="ucpForm" class="uniForm" enctype="multipart/form-data" method="post" action="{$current_url}">
<input type="hidden" name="profile" value="{$profile}" />
<input type="hidden" name="ucp_action" id="ucp_action" value="do" />
<input type="hidden" name="ucp_page" value="page_viewdiscussions" />
<input type="hidden" name="submit" value="1" />
{$token_element}

<div class="ucp-groupheader ucp_color_light">{$page_title}</div>
<fieldset class="inlineLabels">
<div class="bigger ucp_mailbox_header ucp_color_light">{$phrases.lbl_discussions_title} ({$discussions.count_total})</div>
<table class="discussiontbl" border="0" cellpadding="0" cellspacing="0" width="100%">
<thead class="ucp_color_lighter">
  <tr>
    <th align="left">{$phrases.msg_col_title} / {$phrases.msg_col_started_by}</th>
    <th align="center" width="80">{$phrases.msg_col_replies}</th>
    <th align="right" class="datecol" width="110">{$phrases.discussion_col_date}</th>
    <th align="center" class="lborder rborder lastcol" width="20"><input id="checkall" type="checkbox" value="1" /></th>
  </tr>
</thead>

{if !empty($discussions.pagination)}
<tbody class="discussions_pagination">
  <tr><td colspan="4">{$discussions.pagination}</td></tr>
</tbody>
{/if}

{foreach item=master from=$discussions.messages name=discussionslist}
<tbody class="discussion_master">
  <tr>
    <td align="left" class="lborder ucp_color_light{if empty($master.approved)} is_unapproved{/if}">
      <a class="msg_title{if $master.user_last_read < $master.last_msg_raw} msg_unread{/if}" href="{$page_url}&amp;do={$seo.page_viewdiscussion}&amp;d={$master.master_id}" title="{$phrases.lbl_read_discussion|escape}"><strong>{$master.title|default:$phrases.untitled}</strong></a>
      {if $master.user_last_read < $master.last_msg_raw} <img alt="{$phrases.lbl_unread}" title="{$phrases.status_has_unread_messages}" src="{$sdurl}includes/images/mail.png" width="16" height="16" />{/if}
      <br style="clear: both; margin-bottom: 4px" />
      {$phrases.lbl_sent_by} <a class="msg_sender" href="{$discussions.profile_link}{$master.starter_id}">{$master.started_by}</a>
      <br style="clear: both; margin-bottom: 4px" />
      {$phrases.participants}
      {foreach item=msg_user from=$master.users name=master_users}
        {if $smarty.foreach.master_users.iteration <= 3}
        <a class="msg_sender" href="{$discussions.profile_link}{$msg_user.id}">{$msg_user.name|default:$phrases.untitled}</a>{/if}{strip}
        {/strip}{if $smarty.foreach.master_users.iteration <= 2}{if !$smarty.foreach.master_users.last},{/if}{/if}
      {/foreach}
      {if !empty($master.msg_more_participants)}{$master.msg_more_participants}{/if}
    </td>
    <td align="center" class="lborder ucp_color_light" width="60">
      <span class="master_replies">{$phrases.lbl_replies} {$master.message_count-1}</span><br />
      <span class="master_views">{$phrases.lbl_views} {$master.views}</span>
    </td>
    <td align="right" class="datecol lborder ucp_color_light" width="90">
      {$master.last_msg_text}
      {if empty($master.is_closed)}
      <div style="clear: both; padding:0; padding-top: 8px; margin: 0; text-align: right; vertical-align: bottom;">
        <a class="viewmessagelink" href="{$page_url}&amp;do={$seo.page_viewmessage}&amp;d={$master.master_id}" title="{$phrases.lbl_add_message_hint}">{$phrases.lbl_add_message}</a>
      </div>
      {else}
      <br /><strong>{$phrases.lbl_discussion_closed}</strong>
      {/if}
    </td>
    <td align="center" class="lborder rborder ucp_color_light" width="20"><input class="msg_check" name="selected_items[{$master.master_id}]" type="checkbox" value="{$master.master_id}" /></td>
  </tr>
</tbody>

{if !empty($master.master_id) && ($master.master_id > 0)}
<tbody>
  <tr><td colspan="4" style="padding: 4px; height: 4px; line-height: 4px;"> </td></tr>
</tbody>
{else}
<tbody>
  <tr>
  <td colspan="5" class="discussioncol">
    <table class="discussiontbl2" border="0" cellpadding="0" cellspacing="0" width="100%">
    {foreach item=msg from=$master.messages}
    <tbody>
    <tr>
      <td align="center" class="firstcol">
        <img alt="[R]" src="{$sdurl}includes/images/{if empty($msg.is_reply_to)}mail.png{else}up_blue_14.png{/if}" width="16" height="16" />
      </td>
      <td align="left" class="lborder">
        <a title="{$phrases.lbl_reply_to_message|escape}" href="{$page_url}&amp;do={$seo.page_viewmessage}&amp;d={$msg.master_id}&amp;id={$msg.msg_id}" class="viewmessagelink {if empty($msg.msg_read)} msg_unread{/if}" rel="{$msg.msg_id}">
        <strong>{$msg.msg_title|default:$phrases.untitled}</strong></a>
        <br />
        <a class="msg_sender" href="{$msg.msg_sender_link}">{$msg.username}</a>
      </td>
      <td align="right" class="datecol">{$msg.msg_date_text}</td>
    </tr>
    </tbody>
    <tbody class="message_body_{$msg.msg_id}">
    <tr>
      <td align="left" colspan="3" style="padding: 4px;"><div class="msg_text">{$msg.msg_text}</div></td>
    </tr>
    </tbody>
    {/foreach}
    </table>
  </td>
  </tr>
</tbody>
{/if}
{if $smarty.foreach.discussionslist.last && !empty($discussions.options)}
<tbody>
  <tr>
    <td colspan="4" align="right" class="ucp_color_lighter" style="padding:0">
    <div class="msg_operations">
      <div>{$phrases.lbl_discussion_options}</div>
      <input type="submit" id="msg_op_submit" value="{$phrases.lbl_options_go} (0)" class="button" disabled="disabled" style="float: right;" />
      <select id="msg_operation" name="msg_operation">
      <option value="0" selected="selected">{$phrases.options_select}</option>
      {foreach item=entry from=$discussions.options}
      <option value="{$entry.val}">{$entry.phrase}</option>
      {/foreach}
      </select>
    </div>
    </td>
  </tr>
</tbody>
{/if}
{foreachelse}
<tbody class="discussion_master">
  <tr>
    <td align="center" colspan="4"><strong>{$phrases.status_no_messages}</strong></td>
  </tr>
</tbody>
{/foreach}
{if !empty($discussions.pagination)}
<tbody class="discussions_pagination">
  <tr><td colspan="4">{$discussions.pagination}</td></tr>
</tbody>
{/if}
</table>

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
