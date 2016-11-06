{* Media Gallery - Displaymode 5 (Slide Rotator) - images list template - 2013-02-18 *}
{* Put any comment in curly brackets with * next to it like the first line.
   Comments are not displayed on the website and can assist in documentation. *}
{strip}
<table border="0" cellpadding="0" cellspacing="0" class="gal-section-{$sectionid} gal-mode-{$display_mode}" id="gallery{$pluginid}_containment" summary="layout" width="100%">
<tr><td>
  <div id="slideShowContainer">
  <div id="slideShow">
  <ul style="list-style-type: none;">
{* Loop over all available images (may not be all due to pagination). *}
{foreach from=$images_list item=image}
  <li>{$image.a_href}</li>
{/foreach}
  </ul>
  </div>
  <a id="previousLink" href="#">&raquo;</a>
  <a id="nextLink" href="#">&laquo;</a>
  </div>
</td></tr>
</table>
{/strip}