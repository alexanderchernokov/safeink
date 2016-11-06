<form id="ucpForm" class="uniForm" enctype="multipart/form-data" method="post" action="{$current_url}">
<input type="hidden" name="profile" value="{$profile}" />
<input type="hidden" name="ucp_action" id="ucp_action" value="do" />
<input type="hidden" name="ucp_page" value="page_myarticles" />
<input type="hidden" name="submit" value="1" />
<input type="hidden" name="page" value="{$page}" />
{$token_element}
<div class="ucp-groupheader ucp_color_light"><a href="{$content_url}">{$phrases.page_mycontent_title}</a> - {$page_title}</div>

{if !empty($my_articles_list)}
{$my_articles_list}
{else}
<div class="ucp_errorMsg round_corners">
<strong>{$phrases.status_no_articles}</strong>
</div>
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
</form>
