{* Media Gallery - User upload form template - 2013-09-05 *}
{* Put any comment in curly brackets with * next to it like the first line.
   Comments are not displayed on the website and can assist in documentation. *}
{* Common top div *}
{if empty($submit_href)}
  <b>{$language.submit_offline}</b><br />
{else}
<form id="p{$pluginid}_upload_form" method="post" enctype="multipart/form-data" action="{$upload_link}">
{$token_element}
<input type="hidden" name="MAX_FILE_SIZE" value="{$settings.max_upload_size}" />
<input type="hidden" name="{$prefix}_sectionid" value="{$sectionid}" />
<input type="hidden" name="{$prefix}_imageid" value="0" />
<input type="hidden" name="pluginid" value="{$pluginid}" />
{* For logged-in users set author in hidden element *}
{if empty($user_is_guest)}
<input type="hidden" name="{$prefix}_author" value="{$user_name}" />
{/if}
<table border="0" cellspacing="0" cellpadding="0" summary="layout" width="100%">
<tr>
  <td valign="top" width="100">{$language.image_title}{if $settings.image_title_required} <strong>*</strong>{/if}</td>
  <td style="padding: 0 0 8px 8px;">
    <input size="{$inputsize}" type="text" name="{$prefix}_title" value="{$media_title}" />
  </td>
</tr>
{* For guests display an input for author name *}
{if !empty($user_is_guest)}
<tr>
  <td valign="top" width="100">
    {$language.your_name}{if !empty($settings.image_author_required)} <strong>*</strong>{/if}
  </td>
  <td style="padding: 0 0 8px 8px;">
    <input class="btn" size="{$inputsize}" type="text" name="{$prefix}_author" value="{$media_author}" maxlength="64" />
  </td>
</tr>
{/if}

{* Display row with actual file upload element (if allowed in section *}
{if !empty($allow_files) && !empty($max_upload_size)}
<tr>
  <td valign="top" width="100">{$language.image}</td>
  <td style="padding: 0 0 8px 8px;">
    <ul id="{$prefix}_files" style="list-style-type: none;">
      <li><input name="{$prefix}_image" type="file" size="{$inputsize}" /></li>
    </ul>
    <p>{$allowed_extensions}<br />{$max_upload_size}</p>
  </td>
</tr>
{* SD362: allow upload of thumbnail if either media urls are allowed OR thumbnails are not auto-created *}
{if empty($settings.auto_create_thumbs) || (!empty($section.allow_submit_media_link) && !empty($allowed_media_sites))}
<tr>
  <td valign="top" width="100">{$language.thumbnail}</td>
  <td style="padding: 0 0 8px 8px;">
    <input name="{$prefix}_thumbnail" type="file" size="{$inputsize}" />
  </td>
</tr>
{/if}
{/if}

{* Display row with media link input (if allowed in section *}
{if !empty($allow_links) && !empty($allowed_media_sites)}
<tr>
  <td valign="top" width="100">{$language.enter_media_site_link}</td>
  <td style="padding: 0 0 8px 8px;">
    {* SD362: instant media url verification when leaving edit or clicking icon *}
    <div class="gallery_media_url_check">
      <input type="text" id="media_url" name="{$prefix}_media_url" maxlength="255" size="{$inputsize}" value="{$media_link}" />
      <a href="#" onclick="javascript:return false;" title="{$language.click_to_verify_media_url}" class="status_link_small media_ok" style="display: none"><img width="16" height="16" alt="OK!" src="{$sdurl}includes/images/check-ok.png" /></a>
      <a href="#" onclick="javascript:return false;" title="{$language.click_to_verify_media_url}" class="status_link_small media_error" style="display: none"><img width="16" height="16" alt="Error!" src="{$sdurl}includes/images/check-fail.png" /></a>
      <a href="#" id="check_indicator" onclick="javascript:return false;" class="status_link_small" style="display: block"><img width="16" height="16" alt="..." src="{$sdurl}includes/images/refresh.png" /></a>
    </div>
    <br />
    <p>{$allowed_media_sites}</p>
  </td>
</tr>
{/if}
<tr>
  <td valign="top" width="100">
    {$language.description}{if !empty($settings.image_description_required)} <strong>*</strong>{/if}
  </td>
  <td class="{$prefix}_textarea" style="padding: 0 0 8px 8px;">
    <textarea id="{$prefix}_description" name="{$prefix}_description" rows="8" cols="80">{$media_description}</textarea>
  </td>
</tr>
<tr>
  <td valign="top" width="100">{$language.tags}</td>
  <td style="padding: 0 0 8px 8px;">
    {$language.tags_hint}<br />
    <input type="text" id="{$prefix}_tags" name="{$prefix}_tags" value="{$media_tags}" style="width: 250px;" />
  </td>
</tr>
<tr>
  <td valign="top" width="100">{$language.submit_options}</td>
  <td style="padding: 0 0 8px 8px;">
    <fieldset>
    <label for="{$prefix}_comments"><input type="checkbox" id="{$prefix}_comments" name="{$prefix}_comments" {if $media_option_comments}checked="checked"{/if} value="1" /> {$language.submit_option_comments}</label>
    <br />
    <label for="{$prefix}_ratings"><input type="checkbox" id="{$prefix}_ratings" name="{$prefix}_ratings" {if $media_option_ratings}checked="checked"{/if} value="1" /> {$language.submit_option_ratings}</label>
    <br />
    <label for="{$prefix}_private"><input type="checkbox" id="{$prefix}_private" name="{$prefix}_private" {if $media_option_private}checked="checked"{/if} value="1" /> {$language.submit_option_private}</label>
    </fieldset>
  </td>
</tr>
</table>
{$upload_captcha}
<table border="0" cellspacing="0" cellpadding="0" summary="layout" width="100%">
<tr>
  <td valign="top" width="100"> </td>
  <td style="padding: 0 0 8px 8px;">
    <input class="btn btn-primary" type="submit" name="{$prefix}_Submit" value="{$language.submit_image}" />&nbsp;&nbsp;
    <input class="btn btn-primary" type="reset" value="{$language.reset_form}" />
  </td>
</tr>
</table>
</form>
{/if}