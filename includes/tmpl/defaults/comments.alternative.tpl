{* Comment template - 2011-08-21 *}
{if empty($CommentsOrder)}
  <div id="com_editsave" style="display:none"></div>
  <img alt="*" src="includes/css/images/indicator.gif" height="16" width="16" style="display: none; position: absolute; left: -9999999px;" />
{/if}
{if !empty($subscription_html)}{$subscription_html}{/if}
{if !empty($comments_list)}
  {if $ListTag}
  <ol class="comments-list">
  {/if}
  {foreach from=$comments_list item=comment}
  {strip}
    <li id="comment-{$comment.commentid}">
      <a class="comment-anchor" name="c{$comment.commentid}"></a>
      <div class="comment">
        <div class="avatar-column">{$comment.avatar}</div>
        <div class="message-column">
        <ul class="meta">
          <li class="author">{if !empty($comment.user_link)}<a href="{$comment.user_link}" rel="nofollow">{$comment.username}</a>&nbsp;{else}<span class="username">{$comment.username}</span>{/if}
          {if !empty($comment.userid) && !empty($cp_path)}<a title="{$sdlanguage.send_private_message}" href="{$cp_path}?profile={$userid}&amp;do=send-new-message&amp;recipientid={$comment.userid}"><img src="includes/images/mail.png" alt="PM" width="16" height="16" style="vertical-align:middle" /></a>{/if}</li>
          <li class="date"><span class="date">{$comment.date}</span></li>
        </ul>
        {if $AdminAccess}<div id="cadmin-{$comment.commentid}" class="comment-admin">
        <a class="comment-delete" title="{$sdlanguage.comments_delete}" href="includes/ajax/sd_ajax_comments.php?do=deletecomment&amp;amp;cid={$comment.commentid}&amp;pid={$pluginid}&amp;securitytoken={$securitytoken}"></a>
        <a target="c{$comment.commentid}" class="comment-edit" title="{$sdlanguage.comments_edit}" href="includes/ajax/sd_ajax_comments.php?do=editcomment&amp;cid={$comment.commentid}&amp;pid={$pluginid}&amp;securitytoken={$securitytoken}"></a>
        </div>{/if}
        <p id="comment-p-{$comment.commentid}" class="comment-text">{$comment.comment}</p>
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
  <div id="com_editsave" style="display: none"></div>
  <img alt="*" src="includes/css/images/indicator.gif" height="16" width="16" style="display: none; position: absolute; left: -9999999px;" />
{/if}
{if !empty($display_pagination)}
  {$comments_pagination}
{/if}
