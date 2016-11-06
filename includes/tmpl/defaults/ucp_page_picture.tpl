<form id="ucpForm" class="uniForm" enctype="multipart/form-data" method="post" action="{$current_url}">
<input type="hidden" name="profile" value="{$profile}" />
<input type="hidden" name="ucp_action" id="ucp_action" value="submit" />
<input type="hidden" name="ucp_page" value="page_picture" />
<input type="hidden" name="submit" value="1" />
{$token_element}
<div class="ucp-groupheader ucp_color_light">{$page_title}</div>
<fieldset class="inlineLabels">
<div class="padding_small ucp_color_lighter">{$phrases.picture_hint}</div>
<table class="messagetbl" border="0" cellpadding="0" cellspacing="5" width="100%">
<thead><tr>
  <th align="center" class="firstcol lborder">{$phrases.active}</th>
  <th align="left" class="lborder">{$phrases.picture_source}</th>
  <th align="center" class="lborder rborder">{$phrases.image}</th>
</tr></thead>
{if $picture_allow_gravatar}
<tr>
  <td align="center" class="firstcol lborder" style="text-align:center;">
    {if !empty($enable_gravatars)}
    <input type="radio" name="user_picture_type" value="0" {if $user_picture_type==0}checked="checked" {/if}/>
    {/if}
  </td>
  <td align="left" class="lborder">Gravatar ({if $enable_gravatars}{$sdlanguage.common_enabled}{else}{$sdlanguage.common_disabled}{/if})</td>
  <td align="center" class="lborder rborder">
    {if !empty($gravatar)}
    <img class="ucp_image" src="{$gravatar}" alt="Gravatar" width="60" height="60" />
    {else}
    <img class="ucp_image" src="includes/images/default_avatar.png" alt="" width="60" height="60" />
    {/if}
  </td>
</tr>
{/if}
{if $picture_upload_allowed && !empty($group_options.pub_img_extensions)}
<tr>
  <td align="center" class="firstcol lborder" style="text-align:center;">
    <input type="radio" name="user_picture_type" value="1" {if $user_picture_type==1}checked="checked" {/if}/>
  </td>
  <td align="left" class="lborder">{$phrases.picture_new_upload}<br /><br />
  {if !empty($picture_path)}<input type="file" name="picture_image" size="40" />{else}{$phrases.picture_upload_disabled}<br />{/if}<br />
  {if !empty($group_options.pub_img_max_size)}<input type="hidden" name="max_file_size" value="{$group_options.pub_img_max_size}" />{/if}
  <input type="checkbox" name="del_profile_image" value="1" /> {$phrases.delete_picture}<br />
  <span style="font-size: 11px; font-weight: bold">{$phrases.restrictions}</span><br />
  <span style="font-size: 11px">
  The image must be less than <strong>{$group_options.pub_img_max_size}</strong> bytes in file size.<br />
  Allowed image file extensions: <strong>{$group_options.pub_img_extensions}</strong><br />
  Image will be scaled down to <strong>{$group_options.pub_img_max_width}px</strong> by <strong>{$group_options.pub_img_max_height}px</strong> pixels.<br />
  </span>
  </td>
  <td align="center" class="lborder rborder" style="text-align:center;">
    {if !empty($picture_uploaded)}<img class="ucp_image" src="{$picture_uploaded}" alt="{$phrases.no_image}" width="{$picture_width}" height="{$picture_height}" /><br />{/if}
  </td>
</tr>{/if}
{if $picture_link_allowed}
<tr>
  <td align="center" class="firstcol lborder" style="text-align:center;">
    <input type="radio" name="user_picture_type" value="2" {if $user_picture_type==2}checked="checked" {/if}/>
  </td>
  <td align="left" class="lborder">{$phrases.picture_external_link}<br />
  <input type="text" name="picture_link" size="50" style="width: 95%" /><br />
  {if !empty($user_picture_link)}
  <input type="checkbox" name="del_profile_link" value="1" /> {$phrases.delete_link}<br />
  {/if}
  {if !empty($user_picture_link)}
  <span style="font-size: 11px; font-weight: bold">{$phrases.picture_current_link}</span><br />
  <span style="font-size: 11px">{$user_picture_link}</span><br />
  {/if}
  {$phrases.picture_link_hint}
  </td>
  <td align="center" class="lborder rborder">
  {if empty($user_picture_link)}{$phrases.no_image}{else}<img class="ucp_image" src="{$user_picture_link}" width="{$picture_width}" height="{$picture_height}" />{/if}
  </td>
</tr>{/if}
<tr>
  <td align="center" colspan="3" class="lborder rborder" style="text-align:center">
    <button type="submit" class="primaryAction">{$phrases.update_profile}</button>
  </td>
</tr>
</table>

{if !empty($errors) || !empty($errortitle)}
<div class="ucp_errorMsg round_corners">
  {if !empty($errortitle)}<h3>{$errortitle}</h3>{/if}
  {if !empty($errors)}
  <ol>
  {foreach item=error from=$errors}
  <li>{$error}</li>
  {/foreach}
  </ol>
  {/if}
</div>
{/if}
</fieldset>
</form>
