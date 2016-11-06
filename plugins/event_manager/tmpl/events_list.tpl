{* Smarty Template for SD Events Manager plugin.
   Last updated: 2013-10-21 *}
{if empty($is_ajax_request)}<div id="p{$pluginid}"{$container_class}>{/if}
<table id="{$prefix}events" border="0" cellpadding="0" cellspacing="0" width="100%">
<thead>
<tr>
  {if !empty($settings.display_images)}<th class="rowcol1"></th>{/if}
  <th class="rowcol1"><a href="{$col_header_title_link}">{$phrases.event_name}</a>{if $sortcol==1} {$arrow}{/if}</th>
  <th class="rowcol1"><a href="{$col_header_date_link}">{$phrases.date}</a>{if $sortcol==2} {$arrow}{/if}</th>
  <th class="rowcol1"><a href="{$col_header_location_link}">{$phrases.location}</a>{if $sortcol==3} {$arrow}{/if}</th>
  <th class="rowcol1"><a href="{$col_header_venue_link}">{$phrases.venue}</a>{if $sortcol==4} {$arrow}{/if}</th>
</tr>
</thead>
<tbody>
{foreach item=event from=$events name=eventslist}
<tr>
{if !empty($settings.display_images)}
<td class="rowcol{$event.color}">
  {if !empty($event.thumbnail_html)}{$event.thumbnail_html}{/if}
</td>
{/if}
<td class="rowcol{$event.color}"><a href="{$event.details_url}">{$event.title}</a></td>
<td class="rowcol{$event.color}">{$event.date_display}</td>
<td class="rowcol{$event.color}">
  {if !empty($event.city)}{$event.city} {/if}
  {if !empty($event.city) && !empty($event.state)}, {/if}
  {if !empty($event.state)}{$event.state} {/if}
</td>
<td class="rowcol{$event.color}">
  {if !empty($event.venue)}{$event.venue} {/if}
</td>
</tr>
{/foreach}
{if !empty($show_pagination)}<tr><td colspan="{if !empty($settings.display_images)}5{else}4{/if}">{$pagination}</td></tr>{/if}
{if !empty($allow_submit)}<tr><td colspan="{if !empty($settings.display_images)}5{else}4{/if}">
  <a href="{$event_submit_link}">{$phrases.submit_event}</a>
</td></tr>
{/if}
</tbody>
</table>
{if !$is_ajax_request}<div style="clear:both;display:block;height:1px;"> </div></div>{/if}
