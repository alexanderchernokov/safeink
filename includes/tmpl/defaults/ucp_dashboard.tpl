{* Smarty Template for User Control Panel - Dashboard 2011-12-10 *}
<div id="ucp_username">{$data.username} {if !empty($data.online)}{$data.online}{/if}</div>
{if !empty($data.displayname)}
<div id="ucp_displayname">{$phrases.displayname_label} <span>{$data.displayname}</span></div>
{/if}
{if !empty($data.usergroup)}<div id="group_name" style="font-weight:bold;color:{$data.usergroup_color};padding:4px;">{$data.usergroup} </div>{/if}
<div id="ucp_dashboard">
  {$token_element}
  {if empty($group_options.msg_enabled) || empty($data.messaging_on)}
  <div class="ucp_errorMsg ucp_color_light round_corners">
  <strong>{$phrases.messaging_disabled}</strong>
  </div>
  {/if}
  {* <div class="ucp_status_title ucp_color_lighter round_corners">{$phrases.lbl_status_subtitle}</div> *}
  <div class="ucp_status_container">
    <div class="ucp_avatar round_corners">
      {if !empty($data.avatar)}{$data.avatar}{else}<img src="includes/images/default_avatar.png" alt="" style="max-width:60" />{/if}
    </div>
    {if !empty($data.user_text)}<div class="ucp_statustext ucp_color_light round_corners">
     {* <textarea class="ucp_color_light" id="ucp_user_text" name="user_text" cols="50" rows="2" readonly="readonly">{$data.user_text}</textarea> *}
     {$data.user_text}
    </div>{/if}
    <div style="clear: both; height: 0px"></div>
  </div>
  {if !empty($links)}
  <div class="ucp_status_links ucp_color_lighter round_corners">
    <ol class="links_list">
    {if isset($links.mycontent)}<li><a href="{$links.mycontent}">{$phrases.page_mycontent_title}</a></li>{/if}
    {if isset($links.subscriptions)}<li><a href="{$links.subscriptions}">{$phrases.page_subscriptions_title}</a></li>{/if}
    {if isset($links.member_page)}<li><a href="{$links.member_page}">{$phrases.my_member_page}</a></li>{/if}
    </ol>
    {* <div class="ucp_substatus">{$phrases.your_recent_messages}</div> *}
    <div style="clear: both; height: 0px"> </div>
  </div>
  <div class="ucp_color_lighter round_corners" style="padding:8px;margin:4px;">
  <strong>Statistics:</strong><br />
  Join Date: <strong>{$data.joindate}</strong><br />
  {if !empty($data.thread_count)}You started <strong>{$data.thread_count}</strong> forum topics.<br />{/if}
  {if !empty($data.post_count)}You submitted <strong>{$data.post_count}</strong> forum posts.<br />{/if}
  {if !empty($data.likes_comments)}You liked <strong>{$data.likes_comments}</strong> comments<br />{/if}
  {if !empty($data.likes_posts)}You liked <strong>{$data.likes_posts}</strong> forum posts.<br />{/if}
  {if !empty($data.liked_posts_likes)}<strong>{$data.liked_posts_count}</strong> of your forum posts were liked <strong>{$data.liked_posts_likes}</strong> times in total by other users.<br />{/if}
  </div>
  {/if}

  {if empty($messages)}{* <div class="ucp_status_container ucp_color_light round_corners">{$phrases.status_no_messages}</div> *}{else}
  <div class="ucp_messages round_corners">
  {foreach item=msg from=$messages}
    <div class="ucp_message_container ucp_color_lighter round_corners">
      <div class="ucp_message_right">
        <div class="ucp_message_header">
          {$phrases.lbl_message_from} <a href="{$msg.from_link}">{$msg.from_username}</a><br />
          {if !empty($msg.participants)}
          {if !empty($msg.is_private)} {$phrases.additional_participants} {else} {$phrases.additional_recipients} {/if}
          {foreach item=participant from=$msg.participants}
          <a href="{$participant.link}">{$participant.username}</a>
          {/foreach}
          {/if}
        </div>
        <div class="ucp_messagetext ucp_color_light">
          {$msg.text}
        </div>
        <div class="ucp_message_footer">
          {$phrases.lbl_message_sent} {$msg.activity}
        </div>
      </div>
      <div class="ucp_avatar">
        {if !empty($msg.avatar)}
        <img src="{$msg.avatar}" alt="" width="60" height="60" />
        {/if}
      </div>
      <div style="clear: both; height: 0px"> </div>
    </div>
  {/foreach}
  </div>
  <div style="clear: both; padding: 4px; margin-top: 4px; margin-bottom: 6px; text-align: center; width: auto;"><a href="#"><strong>{$phrases.lbl_view_all_messages}</strong></a>
  </div>{/if}
</div>