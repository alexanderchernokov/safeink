{* Media Gallery - General header template - 2013-09-06 *}
{* Put any comment in curly brackets with * next to it like the first line.
   Comments are not displayed on the website and can assist in documentation. *}
{* Common top section container *}
<table border="0" class="gallery_header" cellpadding="0" cellspacing="0" summary="layout" width="100%">
{if !empty($settings.display_jump_menu) && !empty($section_menu_items)}
<tr>
  <td{if !empty($submit_href)} colspan="2"{/if}>
  <form method="post" id="{$prefix}_FormMenu" class="gallery_menu" action="{$page_link}">
  <div style="float: right; padding: 2px;">{$language.sections}
    {* Display dropdown with all available sections *}
    <select id="{$prefix}_sectionid">
    <option value="-1">{$language.jump_to}</option>
    {$section_menu_items}
    </select>
    <noscript><p><input type="submit" value="{$language.go}" /></p></noscript>
  </div>
  </form>
  </td>
</tr>
{/if}
<tr>
  <td{if empty($submit_href)} colspan="2"{/if}>
  {* Display hierarchy for current section *}
  {$section_hierarchy}
  </td>
  {if !empty($submit_href)}
  <td align="right"><a class="gallery_upload_link" href="{$submit_href}">{$language.submit_an_image}</a></td>
  {/if}
</tr>
</table>
