{* Smarty Template for SD Latest Articles plugin.
   Last updated: 2013-09-30 *}
{if empty($is_ajax_request)}<div id="p{$pluginid}"{$container_class}>{/if}
{foreach item=article from=$entries name=articleslist}
<div{$entry_class}>
{if !empty($article.article_thumbnail)}
<div class="article_head_left">
  <div class="article-image"><img alt="" src="{$sdurl}images/articlethumbs/{$article.article_thumbnail}" /></div>
</div>
{/if}
<div class="article_title">
{if $show_title_link}<a href="{$article.article_url}">{/if}
  {$article.bold_start}{$article.article_title}{$article.bold_end}
{if $show_title_link}</a>{/if}
</div>
<div class="article_subtitle">
{if $show_category_name}
  {if $show_category_link}(<a href="{$article.category_link}">{$article.category_name}</a>){else}({$article.category_name}){/if}
{/if}
{if $show_avatar && !empty($article.avatar)}<br />{$article.avatar}{/if}
{if $show_author}<br />{$language.by} {$article.author_link}{/if}
{if $show_date}
  {if $show_author} - {else}<br />{/if}
  {$language.published} {$article.date_published_stamp|date_format:$dateformat}
{/if}
{if $article.show_updatedate}
  {if $show_author && !$show_date} - {else}<br />{/if}
  {$language.updated} {$article.date_updated_stamp|date_format:$dateformat}
{/if}
</div>

{if $article.show_description}
<div class="article_read_more">{if !empty($article.article_description)}{$article.article_description}{if $show_read_more}<br />{/if}{/if}
  {if $show_read_more}<a href="{$article.article_url}">{$language.read_more}</a><br />{/if}
</div>
{/if}
</div>
{/foreach}
{$pagination}
{if !$is_ajax_request}<div style="clear:both;display:block;height:1px;"> </div></div>{/if}
