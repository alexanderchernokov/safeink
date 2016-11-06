{* Smarty Template for SD Events Manager plugin, single event view 1.
   Last updated: 2013-10-10 *}
<table border="0" class="event_details" cellpadding="0" cellspacing="0" width="100%">
<tr>
  <td colspan="2" class="rowcol1"><h2>{if empty($event.title)}{$phrases.untitled}{else}{$event.title}{/if}</h2></td>
</tr>
{if !empty($settings.display_images) && !empty($event.image)}
<tr>
  <td class="rowcol1" colspan="2">
    <img class="event_image" src="{$event.image}" alt="" />
  </td>
</tr>
{/if}
{if !empty($event.date)}
<tr>
  <td width="100" valign="top" class="rowcol{$event.date_color}">{$phrases.date2}</td>
  <td valign="top" class="rowcol2">{$event.date_display}</td>
</tr>
{/if}
{if !empty($event.time)}
<tr>
  <td width="100" valign="top" class="rowcol{$event.time_color}">{$phrases.time}</td>
  <td valign="top" class="rowcol2">{$event.time}</td>
</tr>
{/if}
{if !empty($event.venue)}
<tr>
  <td width="100" valign="top" class="rowcol{$event.venue_color}">{$phrases.venue2}</td>
  <td valign="top" class="rowcol2">{$event.venue}</td>
</tr>
{/if}
{if !empty($event.street)}
<tr>
  <td width="100" valign="top" class="rowcol{$event.street_color}">{$phrases.street}</td>
  <td valign="top" class="rowcol2">{$event.street}</td>
</tr>
{/if}
{if !empty($event.city)}
<tr>
  <td width="100" valign="top" class="rowcol{$event.city_color}">{$phrases.city}</td>
  <td valign="top" class="rowcol2">{$event.city}</td>
</tr>
{/if}
{if !empty($event.state)}
<tr>
  <td width="100" valign="top" class="rowcol{$event.state_color}">{$phrases.state}</td>
  <td valign="top" class="rowcol2">{$event.state}</td>
</tr>
{/if}
{if !empty($event.country)}
<tr>
  <td width="100" valign="top" class="rowcol{$event.country_color}">{$phrases.country}</td>
  <td valign="top" class="rowcol2">{$event.country}</td>
</tr>
{/if}
{if !empty($event.description)}
<tr>
  <td width="100" valign="top" class="rowcol{$event.description_color}">{$phrases.description}</td>
  <td valign="top" class="rowcol2">{$event.description} </td>
</tr>
{/if}
{if !empty($return_link)}
<tr><td colspan="2"><a id="event_return_link" href="{$return_link}">{$phrases.return}</a></td></tr>
{/if}
</table>
{if !empty($comments_html)}{$comments_html}{/if}
{if !empty($js_code)}{$js_code}{/if}
