<form id="ucpForm" class="uniForm" enctype="multipart/form-data" method="post" action="{$current_url}">
<input type="hidden" name="profile" value="{$profile}" />
<input type="hidden" name="ucp_action" id="ucp_action" value="submit" />
<input type="hidden" name="ucp_page" value="page_avatar" />
<input type="hidden" name="submit" value="1" />
{$token_element}
<div class="ucp-groupheader ucp_color_light">{$page_title}</div>
<fieldset class="inlineLabels">
<div class="padding_small ucp_color_lighter">{$phrases.avatar_hint}</div>
<table class="messagetbl" border="0" cellpadding="0" cellspacing="5" width="100%">
<thead><tr>
  <th align="center" class="firstcol lborder">{$phrases.active}</th>
  <th align="left" class="lborder">{$phrases.avatar_source}</th>
  <th align="center" class="lborder rborder">{$phrases.image}</th>
</tr></thead>
<tr>
  <td align="center" class="firstcol lborder" style="text-align:center;">
    {if !empty($enable_gravatars)}
    <input type="radio" name="user_avatar_type" value="0" {if $user_avatar_type==0}checked="checked" {/if}/>
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
{if $avatar_upload_allowed && !empty($group_options.avatar_extensions)}
<tr>
  <td align="center" class="firstcol lborder" style="text-align:center;">
    {if !empty($avatar_path)}
    <input type="radio" name="user_avatar_type" value="1" {if $user_avatar_type==1}checked="checked" {/if}/>
    {/if}
  </td>
  <td align="left" class="lborder">{$phrases.avatar_new_upload}<br /><br />
  {if !empty($avatar_path)}<input type="file" name="avatar_image" size="40" />{else}{$phrases.avatar_upload_disabled}<br />{/if}<br />
  {if !empty($group_options.avatar_max_size)}<input type="hidden" name="max_file_size" value="{$group_options.avatar_max_size}" />{/if}
  <input type="checkbox" name="del_avatar_image" value="1" /> {$phrases.avatar_delete_image}<br />
  <span style="font-size: 11px; font-weight: bold">{$phrases.restrictions}</span><br />
  <span style="font-size: 11px">
  The image must be less than <strong>{$group_options.avatar_max_size}</strong> bytes in file size.<br />
  Allowed image file extensions: <strong>{$group_options.avatar_extensions}</strong><br />
  Image will be scaled down to <strong>{$group_options.avatar_max_width}px</strong> by <strong>{$group_options.avatar_max_height}px</strong> pixels.<br />
  </span>
  </td>
  <td align="center" class="lborder rborder" style="text-align:center;">
    {if empty($avatar_uploaded)}{$phrases.no_image}{else}<img class="ucp_image" src="{$avatar_uploaded}" alt="{$phrases.no_image}" width="{$avatar_width}" height="{$avatar_height}" /><br />{/if}
  </td>
</tr>{/if}
{if $avatar_link_allowed}
<tr>
  <td align="center" class="firstcol lborder" style="text-align:center;">
    <input type="radio" name="user_avatar_type" value="2" {if $user_avatar_type==2}checked="checked" {/if}/>
  </td>
  <td align="left" class="lborder">{$phrases.avatar_external_link}<br />
  <input type="text" name="avatar_link" size="50" style="width: 95%" /><br />
  <span style="font-size: 11px; font-weight: bold">{$phrases.avatar_current_link}</span><br />
  <span style="font-size: 11px">{$user_avatar_link}</span>
  </td>
  <td align="center" class="lborder rborder">
  {if empty($user_avatar_link)}{$phrases.no_image}{else}<img class="ucp_image" src="{$user_avatar_link}" width="{$avatar_width}" height="{$avatar_height}" />{/if}
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
