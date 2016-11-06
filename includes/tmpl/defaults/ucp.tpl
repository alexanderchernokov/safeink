<table id="ucp_table" border="0" cellspacing="0" cellpadding="0" summary="controlpanel" width="100%">
<tbody>
<tr><td colspan="2" style="padding-left:8px;margin-left:6px;padding-right:0">
<div class="ucp_color_light" style="padding:4px">
  <span class="ucp_home_image"><a href="{$page_url}" style="font-size:15px"><strong>{$phrases.my_control_panel}</strong></a></span>
</div>
</td></tr>
<tr>
<td valign="top" class="ucp_left_col">
  <div id="profile_indicator" style="display: none;"><img width="16" height="16" src="{$sdurl}includes/css/images/indicator.gif" alt="*" /></div>
  <div class="sectionbar ucp_color_light" id="ucp_group1"><a href="#" onclick="javascript:return false;">{$phrases.section_messages}</a></div>
  <ul class="ucp_groupitems">
    {if !empty($group_options.msg_enabled) && !empty($data.messaging_on)}<li><a class="profilelink" name="page_newmessage" href="{$page_url}&amp;do={$seo.page_newmessage}">{$phrases.page_newmessage_title}</a></li>{/if}
    <li><a class="profilelink" href="{$page_url}&amp;do={$seo.page_viewmessages}">{$phrases.view} {$phrases.lbl_inbox_title}</a>{if !empty($data.unread_count)} <a href="{$page_url}&amp;do={$seo.page_viewmessages}" name="page_viewmessages" class="profilelink ucp_unread">({$data.unread_count} {$phrases.lbl_unread})</a>{/if}</li>
    <li><a class="profilelink" href="{$page_url}&amp;do={$seo.page_viewdiscussions}">{$phrases.page_viewdiscussions_title}</a>
    {if !empty($data.discussions_unread_count)}<a href="{$page_url}&amp;do={$seo.page_viewdiscussions}" name="page_viewdiscussions" class="profilelink ucp_unread">({$data.discussions_unread_count} {$phrases.lbl_unread})</a>{/if}</li>
    <li><a class="profilelink" href="{$links.subscriptions}">{$phrases.page_subscriptions_title}</a></li>
    <li><a class="profilelink" href="{$page_url}&amp;do={$seo.page_viewsentmessages}">{$phrases.view} {$phrases.lbl_outbox_title}</a></li>
    {if empty($data.forum_integration)}<li><a class="profilelink" href="{$links.mycontent}">{$phrases.page_mycontent_title}</a></li>{/if}
  </ul>
  {if !empty($groups_sorted)}
  <div class="sectionbar ucp_color_light" id="ucp_group2"><a href="#" onclick="javascript:return false;">{$phrases.section_account}</a></div>
  <ul class="ucp_groupitems">
{foreach item=group from=$groups_sorted}
    <li><a class="grouplink" name="group_{$group.group_id}" href="{$page_url}&amp;do={$group.seo_title}.{$group.group_id}">{$phrases.edit} {$group.name}</a></li>
{/foreach}
  </ul>{/if}
{if empty($data.forum_integration)}
  <div class="sectionbar ucp_color_light" id="ucp_group3"><a href="#" onclick="javascript:return false;">{$phrases.section_pictures}</a></div>
  <ul class="ucp_groupitems">
    <li><a class="profilelink" name="page_avatar" href="{$page_url}&amp;do={$seo.page_avatar}">{$phrases.edit} {$phrases.page_avatar_title}</a></li>
    <li><a class="profilelink" name="page_picture" href="{$page_url}&amp;do={$seo.page_picture}">{$phrases.edit} {$phrases.page_picture_title}</a></li>
  </ul>
{/if}
  <div class="sectionbar ucp_color_light" id="ucp_group4"><a href="#" onclick="javascript:return false;">{$phrases.section_options}</a></div>
  <ul class="ucp_groupitems">
    <li><a class="profilelink" href="{$links.member_page}">{$phrases.my_member_page}</a></li>
    <li><a class="profilelink" href="{$logout}">{$phrases.logout}</a></li>
  </ul>

</td>
<td valign="top" class="content_cell">
<div id="ucp_content">
  {if !empty($ucp_content)}
  {include file="$ucp_content.tpl"}
  {else}
  {if !empty($page_content)}{$page_content}{/if}
  {/if}
</div>
</td>
</tr>
</tbody>
</table>

<div style="clear:both;display:block;margin-top:8px;margin-bottom:8px;padding:2px 10px 2px 15px;min-height:20px">
  <div style="display:inline;float:left;font-weight:bold;font-style:italic">Login: {$data.username} ({$data.displayname})</div>
  <div class="datetime" style="display:inline;float:right">{$data.currentdate}</div>
</div>
