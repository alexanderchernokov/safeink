{* Smarty Template for SD Events Manager plugin: Upcoming Events
   Last updated: 2013-10-10 *}
<table id="{$prefix}upcomingevents" border="0" cellpadding="0" cellspacing="0" width="100%">
{foreach item=event from=$events name=eventslist}
<tr>
{if !empty($settings.upcoming_thumbnails)}
<td class="rowcol{$event.color}">
  {if !empty($event.thumbnail_html)}{$event.thumbnail_html}{/if}
</td>
{/if}
<td class="rowcol{$event.color}" style="padding:2px">
  <a href="{$event.details_url}">{$event.title}</a><br />
  {$event.date_display}<br />
  {if !empty($event.city)}{$event.city} {/if}
  {if !empty($event.city) && !empty($event.state)}, {/if}
  {if !empty($event.state)}{$event.state} {/if}
  {if !empty($event.city) || !empty($event.state)}<br />{/if}
  {if !empty($event.venue)}{$event.venue} {/if}
</td>
</tr>
{/foreach}
<tr>
  <td align="center"{if !empty($settings.upcoming_thumbnails)} colspan="2"{/if}>
    <a href="{$events_page_link}">&laquo; {$language.upcoming_back_link}</a>
  </td>
</tr>
</table>
{if !$is_ajax_request}<div style="clear:both;display:block;height:1px;"></div>{/if}
