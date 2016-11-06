<div class="ucp-groupheader ucp_color_light">{$page_title}</div>
{if !empty($data.myarticles_count)}
<div class="ucp_mailbox_header ucp_color_light bigger">
<a href="{$data.myarticles_url}">{$phrases.page_myarticles_title} ({$data.myarticles_count})</a>
</div>
{/if}

{if !empty($data.myforum_post_count)}
<div class="ucp_mailbox_header ucp_color_light bigger">
<a href="{$data.myforum_url}">{$phrases.page_myforum_title} ({$data.myforum_post_count} posts, {$data.myforum_thread_count} topics)</a>
</div>
{/if}

{if !empty($data.myfiles_count)}
<div class="ucp_mailbox_header ucp_color_light bigger">
<a href="{$data.myfiles_url}">{$phrases.page_myfiles_title} ({$data.myfiles_count})</a>
</div>
{/if}

{if !empty($data.mymedia_images_count) || !empty($data.mymedia_sections_count)}
<div class="ucp_mailbox_header ucp_color_light bigger">
<a href="{$data.mymedia_url}">{$phrases.page_mymedia_title} (published {$data.mymedia_images_count} image(s){if !empty($data.mymedia_sections_count)}, maintaining {$data.mymedia_sections_count} album(s){/if})</a>
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