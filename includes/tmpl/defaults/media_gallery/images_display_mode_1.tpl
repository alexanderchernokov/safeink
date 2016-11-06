{* Media Gallery - Displaymode 1 (Popup) - images list template - 2013-02-24 *}
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
    <div class="thumb_img">
      <a href="#" onclick="window.open('{$sdurl}{$plugindir}mediapopup.php?categoryid={$categoryid}&{$prefix}_sectionid={$sectionid}&{$prefix}_imageid={$image.imageid}', '', 'width={$image.width},height={$image.width},directories=no,location=no,menubar=no,scrollbars=yes,status=no,toolbar=no,resizable=yes');return false;" target="_blank">{$image.a_text}</a>
  {if !empty($image.mod_links) ||
      (!empty($section.display_view_counts) && !empty($image.viewcount)) ||
      (!empty($section.display_comments) && !empty($image.comments_count))}
    <br />
    <div style="clear:both;text-align:center;margin-top:4px">
    {if !empty($image.mod_links)}
      {$image.mod_links}<br />
    {/if}
    {if !empty($section.display_comments) && !empty($image.comments_count)}
      {$image.comments_count} {if $image.comments_count==1}{$sdlanguage.comment}{else}{$sdlanguage.comments}{/if}<br />
    {/if}
    {if !empty($section.display_view_counts) && !empty($image.viewcount)}
      {$image.viewcount} {if $image.viewcount==1}{$language.view}{else}{$language.views}{/if}<br />
    {/if}
    </div>
  {/if}
    </div>
  </li>
{/foreach}
  </ul>
</td></tr>
</table>
{/strip}