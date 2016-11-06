{* Media Gallery - Displaymode 3 (Fancybox) - images list template - 2013-02-18 *}
{* Put any comment in curly brackets with * next to it like the first line.
   Comments are not displayed on the website and can assist in documentation. *}
{strip}
<table border="0" cellpadding="0" cellspacing="0" class="gal-section-{$sectionid} gal-mode-{$display_mode}" id="gallery{$pluginid}_containment" summary="layout" width="100%">
<tr><td>
<ul class="galleryimages">
{* Loop over all available images (may not be all due to pagination). *}
{foreach from=$images_list item=image}
<li>
  <div class="image_title">{$image.title}</div>
  <div class="thumb_img">{$image.a_href}{$image.comments_markup}
  {if !empty($image.mod_links)}<br />
    <div style="clear:both;text-align:center">{$image.mod_links}</div>
  {/if}
  </div>
</li>
{/foreach}
</ul>
</td></tr>
</table>
{/strip}