{* Smarty Template for Form Wizard plugin form.
Last updated: 2013-08-17
Core form fields are inside $form_fields array.
A random, invisible honeypot input is being added for bot/spam prevention. *}
<div class="form_wizard_form"> <!-- Form Wizard -->
{if !empty($settings.display_form_name) && !empty($form_data.name)}<h2>{$form_data.name}</h2>{/if}
{if !empty($form_data.intro_text)}<div class="form_intro_text">{$form_data.intro_text}</div>{/if}
<noscript><div class="error_message"><strong>{$phrases.message_js_required}</strong></div></noscript>
<form class="uniForm" id="p{$pluginid}_{$form_data.form_id}" action="{$form_action}" method="post" enctype="multipart/form-data">
<input type="hidden" name="p{$pluginid}_formid" value="{$form_data.form_id}" />
{$SecurityFormToken}
{if !empty($form_errors)}<div id="error_message">{$form_errors}</div>{/if}
<div class="error_message" style="display: none;"></div>
<fieldset class="inlineLabels">
{if !empty($display_recipients_list)}
  <div class="ctrlHolder">
  <label for="p{$pluginid}_recipientid">{$phrases.recipient}</label>
  {$display_recipients_list}
  </div>
{/if}
{foreach item=ffield from=$form_fields}
{if !empty($ffield.do_honepot)}
  <div class="{$honeypot.outer_class}">
  <label for="{$honeypot.input_id}">{$honeypot.label_text}</label>
  <input class="{$honeypot.input_class}" {if !empty($honeypot.input_id)}id="{$honeypot.input_id}"{/if} name="{$honeypot.input_name}" type="{$honeypot.input_type}" {$honeypot.input_attr} value="{$honeypot.value}" />
  </div>
{/if}
<div class="{$ffield.outer_class}">
{if ($ffield.input_type != "bbcode") AND ($ffield.input_type != "textarea")}
  <label{if !empty($ffield.input_id)} for="{$ffield.input_id}"{/if}>{$ffield.label_text}{if !empty($ffield.input_required)} *{/if}</label>
{/if}
{if isset($ffield.pre_html) && strlen($ffield.pre_html)}{$ffield.pre_html}{/if}
{if !empty($ffield.input_name)}
  {if empty($ffield.input_type) OR ($ffield.input_type == "text")}
    <input {if !empty($ffield.input_class)}class="{$ffield.input_class}" {/if}id="{$ffield.input_id}" name="{$ffield.input_name}" type="{$ffield.input_type}" {if !empty($ffield.input_attr)}{$ffield.input_attr} {/if}value="{$ffield.input_value}" />
    {$ffield.extra_html}
  {/if}
  {if ($ffield.input_type == "file")}
    <input {if !empty($ffield.input_class)}class="{$ffield.input_class}" {/if}id="{$ffield.input_id}" name="{$ffield.input_name}" type="{$ffield.input_type}" {if !empty($ffield.input_attr)}{$ffield.input_attr} {/if}/>
    <div style="clear:both">{$ffield.extra_html}</div>
  {/if}
  {if $ffield.input_type == "checkbox"}
    <input {if !empty($ffield.input_class)}class="{$ffield.input_class}" {/if}{if !empty($ffield.input_id)}id="{$ffield.input_id}" {/if}name="{$ffield.input_name}" type="checkbox" {if !empty($ffield.input_attr)}{$ffield.input_attr} {/if}value="1" {if !empty($ffield.input_value)}checked="checked" {/if}/>
  {/if}
  {if $ffield.input_type == "checkboxes" && !empty($ffield.options)}
    <div id="{$ffield.input_id}" style="display:inline-block">
    {foreach item=fopt from=$ffield.options}
    <input type="checkbox" {if !empty($ffield.input_class)}class="{$ffield.input_class}" {/if}name="{$ffield.input_name}[]" value="{$fopt.optionvalue}" {if $fopt.checked==1}checked="checked" {/if}/> {$fopt.name}<br />
    {/foreach}
    </div>
  {/if}
  {if $ffield.input_type == "radio"}
    <ul class="uni-ul" id="{$ffield.input_id}" style="display:inline-block">
    {foreach item=fopt from=$ffield.options}
    <li><input type="radio" {if !empty($ffield.input_class)}class="{$ffield.input_class}" {/if}name="{$ffield.input_name}" value="{$fopt.optionvalue}" {if $fopt.checked==1}checked="checked" {/if}/> {$fopt.name}</li>
    {/foreach}
    </ul>
  {/if}
  {if ($ffield.input_type == "bbcode") OR ($ffield.input_type == "textarea")}
    <div style="height:20px"><label for="{$ffield.input_id}" style="margin:0;padding:0">{$ffield.label_text}</label></div>
    <textarea name="{$ffield.input_name}" id="{$ffield.input_id}" style="width:99%" {if !empty($ffield.input_class)}class="{$ffield.input_class}" {/if}{if !empty($ffield.input_attr)}{$ffield.input_attr} {else}rows="5" cols="80"{/if}>{$ffield.input_value}</textarea>
    <div style="clear:both;height:1px;"></div>
  {/if}
  {if !empty($ffield.extra_html) AND (($ffield.input_type == "select") OR ($ffield.input_type == "timezone")) }
    {* This content gets filled by plugin! *}
    {$ffield.extra_html}
  {/if}
{else}
  {$ffield.extra_html}
{/if}
</div>
{/foreach}
{if $honeypot_idx >= $form_fields_count}
  <div class="{$honeypot.outer_class}">
    <label for="{$honeypot.input_id}">{$honeypot.label_text}</label>
    <input class="{$honeypot.input_class}" id="{$honeypot.input_id}" name="{$honeypot.input_id}" type="{$honeypot.input_type}" {$honeypot.input_attr} value="{$honeypot.value}" />
  </div>
{/if}
{if !empty($captcha)}<div class="ctrlHolder">{$captcha}</div>{/if}
</fieldset>
<table class="p-submit" width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
  <td class="p-prompt" width="175">&nbsp;</td>
  <td class="p-submit">
    <input type="submit" value="{$form_data.submit_text}" />
  </td>
</tr>
</table>
</form>
</div>
{* DO NOT CHANGE/REMOVE "$p_js"! CONTAINS REQUIRED CORE JAVASCRIPT! *}
{if !empty($p_js)}{$p_js}{/if}<!-- Form Wizard End -->
