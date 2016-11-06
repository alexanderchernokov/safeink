{* Media Gallery - Displaymode 0 - images list template - 2013-02-24 *}
{* Put any comment in curly brackets with * next to it like the first line.
   Comments are not displayed on the website and can assist in documentation. *}
{strip}
<table border="0" cellpadding="0" cellspacing="0" class="gal-section-{$sectionid} gal-mode-{$display_mode}" id="gallery{$pluginid}_containment" summary="layout" width="100%">
{* Loop over all available images (may not be all due to pagination).
   Check for each image, if a new TR must be started (pre-calculated). *}
{foreach from=$images_list item=image}
{if !empty($image.SD_open_tr)}<tr>{/if}
  <td valign="top" width="{$image_cell_width}%" style="width:{$image_cell_width}%;">
  <div class="image_title_single">{$image.title}</div>
  <div class="thumb_img"><a href="{$image.a_href}">{$image.a_text}</a>
  {if !empty($image.mod_links) ||
      (!empty($section.display_view_counts) && !empty($image.viewcount)) ||
      (!empty($section.display_comments) && !empty($image.comments_count))}
    <br />
    <div style="clear:both;text-align:center;margin-top:4px">
    {if !empty($image.mod_links)}
      {$image.mod_links}<br />
    {/if}
    {if !empty($section.display_comments) && !empty($image.comments_count)}
      {$image.comments_count} {if $image.comments_count==1}{$language.comment}{else}{$language.comments}{/if}<br />
    {/if}
    {if !empty($section.display_view_counts) && !empty($image.viewcount)}
      {$image.viewcount} {if $image.viewcount==1}{$language.view}{else}{$language.views}{/if}<br />
    {/if}
    </div>
  {/if}
  </div>
  </td>
{if !empty($image.SD_close_tr)}</tr>{/if}
{/foreach}
{if !empty($fillup_cells)}
  <td colspan="{$fillup_cells}">&nbsp;</td>
</tr>
{/if}
</table>
{/strip}