{* Smarty Template for SD Article 2011-01-08 *}
<div class="article_container_tagged">
{if !empty($article_display_title)}
  <div class="article_title">
  {if !empty($article_title_link)}
    <h2><a class="article_title_link" href="{$article_link}">{$article_title}</a></h2>
  {else}
    <h2 class="article_title_link">{$article_title}</h2>
  {/if}
  </div>
  <div class="article_subtitle_tagged">
  {if !empty($article_display_published)} {$article_lang.published} {$article_published_date}{/if}
  {if !empty($article_display_author)} {$article_lang.by} <strong>{$article_author}</strong>{/if}
  {if !empty($article_display_updated)}
    {if !empty($article_display_published) || !empty($article_display_author)} | {/if}
    {$article_lang.updated} {$article_updated_date}
  {/if}
  {if !empty($article_display_views)}
    {if !empty($article_display_published) || !empty($article_display_author) || !empty($article_display_updated)} | {/if}
    {$article_lang.views} <strong>{$article_views}</strong>
  {/if}
  {if !empty($article_display_comments)} | <a href="{$article_link}#comments">{$article_lang.comments} ({$article_comment_count})</a>{/if}
  {if !empty($article_display_tags)}
  <div class="article_tags"> {$article_lang.tags}
  {foreach item=tag_url from=$article_tags key=tag name=taglist}
  <span style="white-space:nowrap"><a href="{$tag_url}">{$tag}</a>{if $smarty.foreach.taglist.iteration < $article_tagcount},{/if}</span>
  {/foreach}
  </div>
  {/if}
  </div>
{/if}
</div>
