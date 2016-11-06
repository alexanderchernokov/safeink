{* Smarty Template for User Registration plugin form.
Last updated: 2013-09-16
Core form fields are inside $form_fields array.
A random, invisible honeypot input is being added for bot/spam prevention. *}
<div id="p12_registration"> <!-- User Registration -->
{$settings.registration_form_title}
<noscript><div class="error_message"><strong>{$phrases.message_js_required}</strong></div></noscript>
<form class="uniForm" id="userregistration" action="#" method="post" style="display:none">
{$SecurityFormToken}
{$errors}
<fieldset class="inlineLabels">
{foreach item=ffield from=$form_fields}
{if !empty($ffield.do_honepot)}
  <div class="{$honeypot.outer_class}">
  <label for="{$honeypot.input_id}">{$honeypot.label_text}</label>
  <input class="{$honeypot.input_class}" {if !empty($honeypot.input_id)}id="{$honeypot.input_id}"{/if} name="{$honeypot.input_name}" type="{$honeypot.input_type}" {$honeypot.input_attr} value="{$honeypot.value}" />
  </div>
{/if}
<div class="{$ffield.outer_class}">
{if ($ffield.input_type != "yesno") AND ($ffield.input_type != "radio") AND ($ffield.input_type != "bbcode") AND ($ffield.input_type != "textarea")}
  <label{if !empty($ffield.input_id)} for="{$ffield.input_id}"{/if}>{$ffield.label_text}{if !empty($ffield.input_required)} *{/if}</label>
{/if}
{if isset($ffield.pre_html) && strlen($ffield.pre_html)}
{$ffield.pre_html}
{/if}
{if !empty($ffield.input_name)}
  {if ($ffield.input_type == "text") OR ($ffield.input_type == "password")}
    <input {if !empty($ffield.input_class)}class="{$ffield.input_class}" {/if}id="{$ffield.input_id}" name="{$ffield.input_name}" type="{$ffield.input_type}" {if !empty($ffield.input_attr)}{$ffield.input_attr} {/if}value="{$ffield.input_value}" />
    {$ffield.extra_html}
  {/if}
  {if $ffield.input_type == "yesno"}
    <input {if !empty($ffield.input_class)}class="{$ffield.input_class}" {/if}{if !empty($ffield.input_id)}id="{$ffield.input_id}" {/if}name="{$ffield.input_name}" type="radio" {if !empty($ffield.input_attr)}{$ffield.input_attr} {/if}value="1" {if !empty($ffield.input_value)}checked="checked" {/if}/> {$sdlanguage.yes}
    <input {if !empty($ffield.input_class)}class="{$ffield.input_class}" {/if}name="{$ffield.input_name}" type="radio" {if !empty($ffield.input_attr)}{$ffield.input_attr} {/if}value="0" {if empty($ffield.input_value)}checked="checked" {/if}/> {$sdlanguage.no}
    {$ffield.extra_html}
  {/if}
  {if $ffield.input_type == "radio"}
    <ul class="uni-ul">
    <li><input {if !empty($ffield.input_class)}class="{$ffield.input_class}" {/if}id="{$ffield.input_id}" name="{$ffield.input_name}" type="radio" {if !empty($ffield.input_attr)}{$ffield.input_attr} {/if}value="0" {if empty($ffield.input_value)}checked="checked" {/if}/> {$sdlanguage.no}&nbsp;</li>
    <li><input {if !empty($ffield.input_class)}class="{$ffield.input_class}" {/if}name="{$ffield.input_name}" type="radio" {if !empty($ffield.input_attr)}{$ffield.input_attr} {/if}value="{$ffield.input_value}" {if !empty($ffield.input_value)}checked="checked" {/if}/> {$ffield.label_text}</li>
    </ul>
    {$ffield.extra_html}
  {/if}
  {if ($ffield.input_type == "bbcode") OR ($ffield.input_type == "textarea")}
    <div style="height:20px"><label for="{$ffield.input_id}" style="margin:0px;padding:0px;">{$ffield.label_text}</label></div>
    <textarea name="{$ffield.input_name}" id="{$ffield.input_id}" style="width:99%" {if !empty($ffield.input_class)}class="{$ffield.input_class}" {/if}{if !empty($ffield.input_attr)}{$ffield.input_attr} {/if}rows="6" cols="80">{$ffield.input_value}</textarea>
    <div style="clear:both;height:1px;"></div>
  {/if}
  {if !empty($ffield.extra_html) && (($ffield.input_type == "timezone") OR ($ffield.input_type == "dateformat") OR ($ffield.input_type == "select")) }
    {* This content gets filled by SD core! *}
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
{if !empty($p12_captcha)}<div class="ctrlHolder">{$p12_captcha}</div>{/if}
</fieldset>
<table class="registration-submit" width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
  <td class="registration-prompt" width="175">&nbsp;</td>
  <td class="registration-submit">
    <input type="submit" name="p12_register" value="{$phrases.register}" />
  </td>
</tr>
</table>
</form>
</div> <!-- User Registration End -->
{* DO NOT CHANGE/REMOVE "$p12_js"! REQUIRED CORE JAVASCRIPT! *}
{if !empty($p12_js)}{$p12_js}{/if}
