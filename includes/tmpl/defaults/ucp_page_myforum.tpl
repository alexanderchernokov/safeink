<form id="ucpForm" class="uniForm" enctype="multipart/form-data" method="post" action="{$current_url}">
<input type="hidden" name="profile" value="{$profile}" />
<input type="hidden" name="ucp_action" id="ucp_action" value="do" />
<input type="hidden" name="ucp_page" value="page_mycontent" />
<input type="hidden" name="submit" value="1" />
<input type="hidden" name="page" value="{$page}" />
<input type="hidden" name="prev_page" value="{$prev_page}" />
<input type="hidden" name="prev_page_size" value="{$page_size}" />
{$token_element}
<div class="ucp-groupheader ucp_color_light"><a href="{$content_url}">{$phrases.page_mycontent_title}</a> - {$page_title}</div>
<fieldset class="inlineLabels">

<div class="ucp_mailbox_header ucp_color_light bigger">{$phrases.forum_messages} ({$forum.post_count})</div>
<table class="forumtbl" border="0" cellpadding="0" cellspacing="5" width="100%">
  <thead>
    <tr>
      <th align="left" class="lborder">{$phrases.msg_col_title} / {$phrases.lbl_forum}</th>
      <th align="right" class="datecol">{$phrases.msg_col_date}</th>
    </tr>
  </thead>
{if !$forum.count}
  <tbody>
    <tr>
      <td align="center" colspan="2"><strong>{$phrases.status_no_messages}</strong></td>
    </tr>
  </tbody>
{else}
  {if !empty($forum.pagination)}
  <tbody id="folder_pagination">
    <tr><td colspan="2">{$forum.pagination}</td></tr>
  </tbody>
  {/if}

  {foreach item=msg from=$forum.messages}
  <tbody>
    <tr>
      <td align="left">
        <a  href="{$msg.post_link}" class="msg_title" rel="{$msg.post_id}"><strong>{if !empty($msg.title)}{$msg.title}{else}{$phrases.untitled}{/if}</strong></a>
        <br />
        &raquo; <a rel="{$msg.forum_name}" href="{$msg.forum_link}">{$msg.forum_name}</a>
      </td>
      <td align="right" class="datecol">{$msg.date_text}</td>
    </tr>
    <tr>
      <td align="left" colspan="2"  style="padding-bottom: 8px">
        <div class="forum_post">{$msg.post}</div>
      </td>
    </tr>
  </tbody>
  {/foreach}

  {if !empty($forum.pagination)}
  <tbody id="folder_pagination">
    <tr><td colspan="2">{$forum.pagination}</td>
  </tr>
  <tr>
  <td colspan="2" class="lborder rborder" align="right" style="padding:0">
  <div class="msg_operations ucp_color_bglighter">
    <div>{$phrases.lbl_pagesize}</div>
    <select id="page_size" name="page_size" style="float:none;width:50px !important;margin-top:4px">
    <option value="5" {if empty($page_size)||($page_size==5)}selected="selected"{/if}>5</option>
    <option value="10" {if $page_size==10}selected="selected"{/if}>10</option>
    <option value="25" {if $page_size==25}selected="selected"{/if}>25</option>
    <option value="50" {if $page_size==50}selected="selected"{/if}>50</option>
    </select>
    <input type="submit" id="msg_op_pagesize" value="{$phrases.lbl_options_go}" class="button" style="float: right;" />
  </div>
  </td>
  </tr>
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
