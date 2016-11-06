{* Comment template - 2013-05-11 *}
{* Put any comment in curly brackets with * next to it like the first line.
   Comments are not displayed on the website and can assist in documentation. *}
<div id="com_editsave" style="display:none"></div>
{if empty($CommentsOrder)}
  <img alt="*" src="{$sdurl}includes/css/images/indicator.gif" height="16" width="16" style="display: none; position: absolute; left: -9999999px;" />
{/if}
{* Support for comment subscriptions (showing un-/subscribe links) *}
{if !empty($subscription_enabled)}<br />
<div id="sub-{$pluginid}-{$objectid}" style="display:none">
  {if !empty($subscribe_link)}<a class="subscribe-link button-link" rel="1" href="{$url}" title="{$sdlanguage.subscribe_to_comments}"><span>{$sdlanguage.subscribe}</span></a>{/if}
  <a class="unsubscribe-link button-link" rel="0" href="{$url}" title="{$sdlanguage.unsubscribe_from_comments}"><span>{$sdlanguage.unsubscribe}</span></a>
</div>
{/if}
{* Main area and loop to display all comments *}
{if !empty($comments_list)}
  {if $ListTag}
  <ol class="comments-list">
  {/if}
  {* Loop over all available comments (may not be all due to pagination) *}
  {foreach from=$comments_list item=comment}
  {strip}{* "strip" means to remove extra whitespace, thus the occasional use of &nbsp; below *}
    <li id="comment-{$comment.commentid}">
      <a class="comment-anchor" name="c{$comment.commentid}"></a>
      <div class="comment">
        <div class="avatar-column">{$comment.avatar}</div>
        <div class="message-column">&nbsp;
          {if !empty($comment.user_link) && $comment.userid > 0}{$comment.user_link}{else}<span class="username">{$comment.username}</span>{/if}
          {if !empty($comment.messaging_html)}&nbsp;| {$comment.messaging_html}{/if}
          {if !empty($comment.msg_enabled) && !empty($comment.userid) && !empty($cp_path)} &nbsp;|&nbsp; <a title="{$sdlanguage.send_private_message}" href="{$cp_path}?profile={$userid}&amp;do={$send_msg_title}&amp;recipientid={$comment.userid}" rel="nofollow"><img src="{$sdurl}includes/images/mail.png" alt="PM" width="16" height="16" style="vertical-align:middle" /></a>{/if}
          &nbsp;|&nbsp; <span class="date">{$comment.date}</span>
          {* Special options for admins and users to edit/delete either all (admin) or just their own comment(s) *}
          {if $AdminAccess ||  $report_comments || (($edit_comments || $delete_comments) && ($userid == $comment.userid))}
          <div id="cadmin-{$comment.commentid}" class="comment-admin">
          {if $AdminAccess || ($delete_comments && ($userid == $comment.userid))}
          <a class="comment-delete" title="{$sdlanguage.comments_delete}" rel="nofollow" href="includes/ajax/sd_ajax_comments.php?do=deletecomment&amp;cid={$comment.commentid}&amp;pid={$pluginid}&amp;securitytoken={$securitytoken}"></a>
          {/if}
          {if $AdminAccess || ($edit_comments && ($userid == $comment.userid))}
          <a target="c{$comment.commentid}" class="comment-edit" title="{$sdlanguage.comments_edit}" rel="nofollow" href="includes/ajax/sd_ajax_comments.php?do=editcomment&amp;cid={$comment.commentid}&amp;pid={$pluginid}&amp;securitytoken={$securitytoken}"></a>
          {/if}
          {if $AdminAccess || $report_comments}
          <a target="c{$comment.commentid}" class="comment-report" title="{$sdlanguage.comments_report}" rel="nofollow" href="includes/ajax/sd_ajax_comments.php?do=reportcomment&amp;cid={$comment.commentid}&amp;pid={$pluginid}&amp;categoryid={$categoryid}&amp;securitytoken={$securitytoken}"></a>
          {/if}
          </div>
          {/if}
        <p id="comment-c{$comment.commentid}" class="comment-text">{$comment.comment}</p>
        {$comment.likes_html}
        </div>
      </div>
    </li>
  {/strip}
  {/foreach}
  {if $ListTag}
  </ol>
  {/if}
{/if}
{if !empty($CommentsOrder)}
  <img alt="*" src="{$sdurl}includes/css/images/indicator.gif" height="16" width="16" style="display: none; position: absolute; left: -9999999px;" />
{/if}
{if !empty($display_pagination)}
  {$comments_pagination}
{/if}
