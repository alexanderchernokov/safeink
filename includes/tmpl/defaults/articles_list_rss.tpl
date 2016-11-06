{* Smarty Template for SD Article 2011-01-08 *}
<div class="article_container">
{if empty({$article_counter}) && !empty({$enable_rss})}
  <div class="article_rss_head"><h1>{$page_title} <a target="_blank" href="{$sdurl}rss.php?page_id={$categoryid}"><img width="32" height="32" title="RSS" src="{$sdurl}includes/images/rss.png" alt="RSS" /></a></h1></div>
{/if}
{if !empty($article_display_title)}
  <div class="article_title">
  {if !empty($article_title_popup)}
    <h2><a class="article_title_link popup" rel="iframe" href="{$article_link}">{$article_title}</a></h2>
  {else}
    {if !empty($article_title_link)}
      <h2><a class="article_title_link" href="{$article_link}">{$article_title}</a></h2>
    {else}
      <h2 class="article_title_link">{$article_title}</h2>
    {/if}
  {/if}
  </div>
  <div class="article_subtitle">
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
{if !empty($article_display_description)}
  <div class="article_description">
  {$article_description}
  </div>
{/if}
{if !empty($article_display_article)}
  <div class="article_article">
  {$article_article}
  </div>
  {if !empty($article_display_pagination)}
  {$article_pagination}
  {/if}
{/if}
{if !empty($article_read_more) || !empty($article_display_comments)}<div style="display: block; clear: both;">{/if}
{if !empty($article_read_more)}
  {if !empty($article_title_popup)}
  <div class="article_read_more popup"><a class="btn btn-primary" rel="iframe" href="{$article_link}" rel="nofollow">{$article_lang.read_more}</a></div>
  {else}
  <div class="article_read_more"><a class="btn btn-primary" href="{$article_link}">{$article_lang.read_more}</a></div>
  {/if}
{/if}
{if !empty($article_read_more) || !empty($article_display_comments)}</div>{/if}

{if false}
{if !empty($article_display_social) || !empty($article_display_email) || !empty($article_display_print)}
<div class="article_footer">
  {if !empty($article_rating_form)}<div class="article_rating">{$article_rating_form}</div>{/if}
  {if !empty($article_display_social)}
{* <iframe src="//www.facebook.com/plugins/like.php?href={$article_share_url}&amp;layout=standard&amp;show_faces=false&amp;action=like&amp;font=arial&amp;colorscheme=light" scrolling="no" frameborder="0" class="facebook_iframe"></iframe> *}
   <iframe src="http://www.facebook.com/plugins/like.php?href={$article_share_url}&amp;send=false&amp;layout=standard&amp;width=235&amp;show_faces=false&amp;action=like&amp;colorscheme=light&amp;font=segoe+ui" scrolling="no" frameborder="0" style="border:none; overflow:hidden;" class="facebook_iframe"></iframe>
  {/if}
  <div class="article_footer_right">
    <ul class="article_tools">
    {if !empty($article_display_social)}
      <li class="social gp-one"><div class="g-plusone" data-href="{$article_link}"></div></li>
      <li class="social twitter"><a href="http://twitter.com/home?status={$article_share_title}+{$article_share_url}" target="_blank" title="{$article_twitter_text}" rel="nofollow"></a></li>
      <li class="social delicious"><a href="http://delicious.com/save?v=5&amp;url={$article_share_url}&amp;title={$article_share_title}" target="_blank" title="{$article_delicious_text}" rel="nofollow"></a></li>
      <li class="social facebook"><a href="http://www.facebook.com/share.php?u={$article_share_url}&amp;t={$article_share_title}" target="_blank" title="{$article_facebook_text}" rel="nofollow"></a></li>
      <li class="social digg"><a href="http://digg.com/submit?url={$article_share_url}&amp;title={$article_share_title}" target="_blank" title="{$article_digg_text}" rel="nofollow"></a></li>
    {/if}
    {if !empty($article_display_email)}
      <li class="social email"><a href="mailto:?subject={$article_email_subject}&amp;body={$article_share_url}" title="{$article_email_text}" rel="nofollow"></a></li>
    {/if}
    {if !empty($article_display_print)}
      {if !empty($article_display_pdf)}
      <li class="social article_pdf_link"><a href="{$sdurl}plugins/{$plugin_folder}/printarticle.php?p{$plugin_id}_articleid={$article_id}&amp;pdf" title="{$article_pdf_text}" target="_blank" rel="nofollow"></a></li>
      {/if}
      <li class="social article_print_link"><a href="{$sdurl}plugins/{$plugin_folder}/printarticle.php?p{$plugin_id}_articleid={$article_id}" title="{$article_print_text}" target="_blank" rel="nofollow"></a></li>
    {/if}
    </ul>
  </div>
</div>
{/if}
{/if}
</div>
