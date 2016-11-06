{* User Report Form template - 2013-01-19 *}
{* Put any comment in curly brackets with * next to it like the first line.
   Template comments are not displayed on the website and can assist in documentation. *}
<div class="report-form">
{if !empty($form.html_top)}{$form.html_top}{/if}
{if !empty($item_title)}
  {if !empty($item_link)}
  <h2 style="width:100%"><a rel="nofollow" href="{$item_link}">{$item_title}</a></h2>
  {else}
  <h2 style="width:100%">{$item_title}</h2>
  {/if}
{/if}
<form id="report-form" action="{$form_action}" method="post" style="margin-top:10px">
{foreach from=$hidden item=entry}
<input type="hidden" name="{$entry.name}" value="{$entry.value}" />
{/foreach}
{$form_token}
{if !empty($form.title)}<h2>{$form.title}</h2><br /><br />{/if}
{if !empty($form.subtitle)}{$form.subtitle}<br />{/if}
{if empty($reasons)}
<input type="hidden" name="report_reason" value="99" />
{else}
{foreach from=$reasons item=entry}
<label for="r{$entry.reasonid}">
  <input type="radio" name="report_reason" id="r{$entry.reasonid}" value="{$entry.reasonid}" />
  <strong>{$entry.title}</strong><br />
  {$entry.description}<br />
</label>
<br />
{/foreach}
{/if}
{if !empty($report_user_message)}
{$sdlanguage.comments_report_user_msg}<br />
<textarea class="bbeditor" id="comment" name="user_msg" rows="5" cols="80"></textarea>
<br /><br />
{/if}
<label for="report_confirm">
  <input type="checkbox" id="report_confirm" name="report_confirm" value="1" /> <strong>{$form.confirm}</strong>
</label>
<br /><br />
{if !empty($form.do_captcha) && !empty($form.captcha_html)}{$form.captcha_html}<br />{/if}
<input class="btn btn-primary" type="submit" name="" value="{$form.submit}" />
{if !empty($form.show_close)}
<input class="btn btn-primary submit" type="submit" onclick="parent.jQuery.fn.ceebox.closebox('fast'); return false;" value="{$sdlanguage.common_close}" />
{/if}
</form>
</div>
<br />
{if !empty($form.html_bottom)}{$form.html_bottom}{/if}
