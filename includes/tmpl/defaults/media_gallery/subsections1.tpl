{* Media Gallery - Sub-Sections display 1 template - 2013-02-18 *}
{* Put any comment in curly brackets with * next to it like the first line.
   Comments are not displayed on the website and can assist in documentation. *}
{if !empty($sections_count)}
{strip}
<input type="hidden" id="{$prefix}_sectionid" value="{$sectionid}" />
<table border="0" cellspacing="0" cellpadding="0" class="{$prefix}_sections" summary="layout" width="100%">
<tr><td>
<div class="sections_header">{$language.sections}</div>
<div class="section_container">
{* Loop over all available images (may not be all due to pagination).
   Check for each image, if a new TR must be started (pre-calculated). *}
{foreach from=$subsections item=section}
{if !empty($settings.display_sections_as_images)}
  <div class="section">
    <div class="section_inner">
      <a href="{$section.link}"{if !empty($section.target)} target="{$section.target}"{/if}><img alt="" src="{$section.imagefile}" /></a>
    </div>
    {$section.name}
    {if !empty($settings.display_section_image_count)}
      {if empty($settings.display_sections_as_images)}&nbsp;{else}<br />{/if}
      {if empty($section.imagecount)}{$language.no_image_available}{else}
        ({$section.imagecount} {if $section.imagecount == 1}{$language.image2}{else}{$language.images}{/if})
      {/if}
      {* {if !empty($section.section_added_label)}<br />{$section.section_added_label}{/if} *}
    {/if}
    <br /><br />
    {$section.description}
  </div>
  {if !empty($section.new_row)}<div style="clear: both"></div>{/if}
{else}
- <a href="{$section.link}">{$section.name}</a>
  {if !empty($settings.display_section_image_count)}
    {if empty($settings.display_sections_as_images)}&nbsp;{else}<br />{/if}
    {if !empty($section.imagecount)}
      ({$section.imagecount} {if $section.imagecount == 1}{$language.image2}{else}{$language.images}{/if})
    {/if}
    {* {if !empty($section.section_added_label)}{$section.section_added_label}{/if} *}
  {/if}<br />
  {if !empty($section.description)}<br />{$section.description}<br />{/if}
  <br />
{/if}
{/foreach}
{if !empty($subsections_pagination)}{$subsections_pagination}{/if}
</div><!-- section_container -->
</td></tr>
</table>
{/strip}
{/if}