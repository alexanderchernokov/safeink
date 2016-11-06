{* Smarty Template for Form Wizard plugin: summary response.
Last updated: 2013-09-04
Core form fields are inside $form_fields array. *}
<div class="form_wizard_summary">
{if !empty($response_top_html)}{$response_top_html}{/if}
<fieldset class="inlineLabels">
<div class="ctrlHolder">
<table width="100%">
{foreach item=ffield from=$responses}
<tr>
  <td width="30%"><label{if !empty($ffield.id)} for="{$ffield.id}"{/if}>{$ffield.name}</label></td>
  <td width="70%"><label{if !empty($ffield.id)} id="{$ffield.id}"{/if}>{$ffield.value}</label></td>
</tr>
{/foreach}
</table>
</div>
</fieldset>
</div>
{if !empty($response_bottom_html)}{$response_bottom_html}{/if}
