{* Media Gallery - Displaymode 6 (mb.Gallery) - images list template - 2013-02-18 *}
{* Put any comment in curly brackets with * next to it like the first line.
   Comments are not displayed on the website and can assist in documentation. *}
{strip}
<table border="0" cellpadding="0" cellspacing="0" class="gal-section-{$sectionid} gal-mode-{$display_mode}" id="gallery{$pluginid}_containment" summary="layout" width="100%">
<tr><td>
  <div id="mb{$pluginid}_containment"></div>
  <div id="mb{$pluginid}_gallery">
{* Loop over all available images (may not be all due to pagination). *}
{foreach from=$images_list item=image}
  <a class="imgThumb" href="{$image.a_href}"></a>
  <a class="imgFull" href="{$image.a_href_full}"></a>
  <div class="imgTitle">{$image.title}</div>
  {if !empty($image.description)}<div class="imgDesc">{$image.description}</div>{/if}
{/foreach}
  </div>
</td></tr>
</table>
{/strip}