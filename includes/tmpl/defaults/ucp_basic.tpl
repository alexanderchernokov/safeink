<table id="ucp_table" border="0" cellspacing="0" cellpadding="0" summary="controlpanel" width="100%">
<tbody>
<td valign="top" class="content_cell">
  <div id="ucp_content">{if !empty($page_content)}{$page_content}{/if}</div>
</td>
</tr>
</tbody>
</table>

<div style="clear:both;display:block;margin-top: 8px; padding: 2px 10px 2px 15px;">
  <div style="display:inline;float:left;font-weight:bold;font-style:italic">Login: {$data.username} ({$data.displayname})</div>
  <div class="datetime" style="display:inline;float:right">{$data.currentdate}</div>
</div>
