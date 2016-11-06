{* Media Gallery - Sub-Sections display 2 template - 2013-02-18 *}
{* Put any comment in curly brackets with * next to it like the first line.
   Comments are not displayed on the website and can assist in documentation. *}
{if !empty($sections_count)}
<div class="sections_header">{$language.sections}</div>
<div class="section_container">
{* Loop over all available sections (may not be all due to pagination). *}
<ul>
{foreach from=$subsections item=section}
<li> <a href="{$section.link}">{$section.name}</a>
{if !empty($settings.display_section_image_count)} ({$section.imagecount} {if $section.imagecount == 1}{$language.image2}{else}{$language.images}{/if}){/if}
</li>
{/foreach}
</ul>
</div>
{/if}