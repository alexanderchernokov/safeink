<form id="ucpForm" class="uniForm" enctype="multipart/form-data" method="post" action="{$current_url}">
<input type="hidden" name="profile" value="{$profile}" />
<input type="hidden" name="ucp_action" id="ucp_action" value="do" />
<input type="hidden" name="ucp_page" value="page_viewmessages" />
<input type="hidden" name="submit" value="1" />
<input type="hidden" name="page" value="{$folder.current_page}" />
<input type="hidden" name="page_size" value="{$folder.page_size}" />
{$token_element}
<div class="ucp-groupheader ucp_color_light">{$page_title}</div>
<fieldset class="inlineLabels">
<div class="ucp_mailboxes_top ucp_color_lighter">
<strong>{$folder.title}</strong> contains <strong>{$folder.count}</strong> messages (limit is <strong>{$folder.quota}</strong>).<br />
You have <strong>{$total_messagecount}</strong> messages stored in total.<br />
</div>

<div class="ucp_mailbox_header ucp_color_light bigger">{$folder.title} ({$folder.count})</div>
<table class="messagetbl" border="0" cellpadding="0" cellspacing="5" width="100%">
  <thead>
    <tr>
      <th align="center" class="firstcol">&nbsp;</th>
      <th align="left" class="lborder">{$phrases.msg_col_title} / {$phrases.msg_col_sender}</th>
      <th align="right" class="datecol lborder">{$phrases.msg_col_date}</th>
      <th align="center" class="lborder rborder lastcol"><input id="checkall" type="checkbox" value="1" /></th>
    </tr>
  </thead>

{if !$folder.count}
  <tbody>
    <tr>
      <td align="center" colspan="4"><strong>{$phrases.status_no_messages}</strong></td>
    </tr>
  </tbody>
{else}
  {foreach item=msg from=$folder.messages}
  <tbody>
    <tr>
      <td align="center" class="firstcol lborder">
        <img alt="" src="{$sdurl}includes/images/{if empty($msg.is_reply_to)}mail.png{else}up_blue_14.png{/if}" width="16" height="16" />
      </td>
      <td align="left" class="lborder">
        <a  href="{$page_url}&amp;do={$seo.page_viewmessage}&amp;id={$msg.msg_id}" class="viewmessagelink msg_title{if empty($msg.msg_read)} msg_unread{/if}" rel="{$msg.msg_id}">
        <strong>{if !empty($msg.msg_title)}{$msg.msg_title}{else}{$phrases.untitled}{/if}</strong></a><br />
        <a class="msg_sender" href="{$msg.msg_sender_link}">{$msg.username}</a>
      </td>
      <td align="right" class="datecol lborder">{$msg.msg_date_text}</td>
      <td align="center" class="lborder rborder"><input class="msg_check" name="selected_items[{$msg.msg_id}]" type="checkbox" value="{$msg.msg_id}" /></td>
    </tr>
  </tbody>
  {/foreach}

  <tbody>
  <tr>
  <td colspan="4" class="lborder rborder" align="right" style="padding:0">
  <div class="msg_operations ucp_color_bglighter">
    <select id="pagesize" name="pagesize">
    <option value="5"{if $folder.page_size < 10} selected="selected"{/if}>5</option>
    <option value="10"{if $folder.page_size == 10} selected="selected"{/if}>10</option>
    <option value="15"{if $folder.page_size == 15} selected="selected"{/if}>15</option>
    <option value="30"{if $folder.page_size == 30} selected="selected"{/if}>30</option>
    <option value="60"{if $folder.page_size == 60} selected="selected"{/if}>60</option>
    <option value="100"{if $folder.page_size === 100} selected="selected"{/if}>100</option>
    </select>
    <div>{$phrases.lbl_selected_messages}</div>
    <input type="submit" id="msg_op_submit" value="{$phrases.lbl_options_go} (0)" class="button" disabled="disabled" style="float: right;" />
    <select id="msg_operation" name="msg_operation">
    <option value="0" selected="selected">{$phrases.options_select}</option>
    {foreach item=entry from=$folder.options}
    <option value="{$entry.val}">{$entry.phrase}</option>
    {/foreach}
    </select>
  </div>
  </td>
  </tr>
  </tbody>

  {if !empty($folder.pagination)}
  <tbody id="folder_pagination">
    <tr><td colspan="4">{$folder.pagination}</td></tr>
  </tbody>
  {/if}
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
