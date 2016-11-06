{* Media Gallery - Section footer template - 2013-02-18 *}
{* Put any comment in curly brackets with * next to it like the first line.
   Comments are not displayed on the website and can assist in documentation. *}
<div class="section_footer">
{* Footer pagination or prev/next image links (common) *}
{if $pagination_needed}
  {if ($pagination_links == 0) || ($pagination_links == 2)}
    <div class="{$prefix}_pager_bottom">{$pagination_html}</div>
  {else}
  {if !empty($image_nav_links) && (($display_navigation_links == 0) || ($display_navigation_links == 2))}
  {$image_nav_links}
  {/if}
  {/if}
{/if}
</div>