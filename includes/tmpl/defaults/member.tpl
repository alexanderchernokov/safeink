<table id="ucp_table" border="0" cellspacing="0" cellpadding="0" summary="memberpage" width="100%">
<tbody>
<tr><td colspan="2" style="padding-left:8px;margin-left:6px;padding-right:8px;">
<div class="ucp_color_light" style="padding:4px;width:auto;">
  <span class="ucp_home_image"><a href="{$page_url}" style="font-size:15px;"><strong>{$phrases.title_member_page}</strong></a></span>
</div>
</td></tr>
<tr>
<td valign="top" class="ucp_left_col">
  <div id="profile_indicator" style="display: none;"><img width="16" height="16" src="{$sdurl}includes/css/images/indicator.gif" alt="*" /></div>
  {if !empty($group_msg_enabled) && !empty($userid)}
  <div class="sectionbar" id="ucp_group1"><a href="{$page_url}">{$phrases.page_member_contact_title}</a></div>
  <ul class="ucp_groupitems">
    <li><a class="profilelink" name="page_newmessage" href="{$links.profile_url}&amp;recipientid={$memberid}#do={$seo.page_newmessage}">{$phrases.page_newmessage_title}</a></li>
  </ul>
  {/if}
  {if !empty($groups_sorted) && !empty($groups_visible_count)}
  <div class="sectionbar" id="ucp_group2"><a href="#" onclick="javascript:return false;">{$phrases.section_member_info}</a></div>
  <ul class="ucp_groupitems">
  {foreach item=group from=$groups_sorted}{if !empty($group.show)}
    {if !empty($group.group_id)}<li><a class="grouplink" name="group_{$group.group_id}" href="{$page_url}&amp;do={$group.seo_title}.{$group.group_id}">{$group.name}</a></li>{/if}
  {/if}{/foreach}
  </ul>{/if}
  {if !empty($links.profile_url)}<div class="sectionbar" id="ucp_group4"><a href="#" onclick="javascript:return false;">{$phrases.section_options}</a></div>
  <ul class="ucp_groupitems">
    <li><a class="profilelink" href="{$links.profile_url}">{$phrases.my_control_panel}</a></li>
    <li><a class="profilelink" href="{$page_url}&amp;logout=1">{$phrases.logout}</a></li>
  </ul>{/if}
</td>
<td valign="top" class="content_cell">
<div id="member">
<ul>
  <li id="member-{$memberid}">
    <div class="member round_corners">
      <div class="avatar-column">{$member_data.avatar}</div>
      <div class="member-column">
        <div id="username">{$member_data.displayname} {if !empty($member_data.online)}{$member_data.online}{/if}</div>
        {if !empty($member_data.usergroup_details.displayname)}
        <div id="group_name" style="font-weight:bold;color:{$member_data.usergroup_details.color_online}">{$member_data.usergroup_details.displayname}</div>
        {/if}
      </div>
    </div>
  </li>
  {if !empty($member_data.user_text)}<li>
    <div id="status_text">{$member_data.user_text}</div>
  </li>{/if}
  {if isset($member_data.profile_picture) && ($member_data.profile_picture !== false)}
  <li id="member-{$memberid}-image">
    <img alt="" src="{$member_data.profile_picture}" width="{$member_data.profile_img_width}" height="{$member_data.profile_img_height}" /><br />
  </li>{/if}
  <li id="member-{$memberid}-stats">
    <strong>Statistics:</strong><br />
    Join Date: <strong>{$member_data.joindate}</strong><br />
    {if !empty($member_data.user_thread_count)}Started <strong>{$member_data.user_thread_count}</strong> forum topics.<br />{/if}
    {if !empty($member_data.user_post_count)}Submitted <strong>{$member_data.user_post_count}</strong> forum posts.<br />{/if}
    {if !empty($member_data.likes_comments)}Likes <strong>{$member_data.likes_comments}</strong> comments.<br />{/if}
    {if !empty($member_data.likes_posts)}Likes <strong>{$member_data.likes_posts}</strong> forum posts.<br />{/if}
    {if !empty($member_data.liked_posts_likes)}<strong>{$member_data.liked_posts_count}</strong> submitted posts were liked <strong>{$member_data.liked_posts_likes}</strong> times in total.<br />{/if}
    {if !empty($member_data.user_profile_views)}<strong>{$member_data.user_profile_views}</strong> profile page views.<br />{/if}
  </li>
</ul>
{if !empty($fields)}
  <form id="ucpForm" class="uniForm" enctype="multipart/form-data" method="post" action="{$current_url}">
  <input type="hidden" name="profile" value="{$userid}" />
  <input type="hidden" name="ucp_action" id="ucp_action" value="do" />
  <div class="sectionbar"><strong>{$fields_title}</strong></div>
  <fieldset class="inlineLabels">
  {foreach item=field from=$fields}
  <div class="ctrlHolder">
    <label for="field_{$field.form_id}">{$field.label}</label>
    <span class="textInput2 auto" id="field_{$field.form_id}">{$field.value}</span>
  </div>
  {/foreach}
  </fieldset>
  </form>
{/if}
</div>

</td>
</tr>
</tbody>
</table>

<div style="clear:both;display:block;margin-top: 8px;"> </div>
<div style="display:inline;float:right">{$data.currentdate}</div>
<div style="clear: both; display: block; width: auto; height: 2px;">&nbsp;</div>