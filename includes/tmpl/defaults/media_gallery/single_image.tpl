{* Media Gallery - Single image template - 2013-09-14 *}
{* Put any comment in curly brackets with * next to it like the first line.
   Comments are not displayed on the website and can assist in documentation. *}
{strip}
<table border="0" id="{$prefix}_images_container" cellpadding="0" cellspacing="0" summary="layout" width="100%">
{if ($settings.image_navigation_links <= 1)}
<tr>
  <td class="previous_img">{if !empty($image.prev_img_url)}<a href="{$image.prev_img_url}" class="previmg">{$language.previous_image}</a>{else}&nbsp;{/if}</td>
  <td class="next_img">{if !empty($image.next_img_url)}<a href="{$image.next_img_url}" class="nextimg">{$language.next_image}</a>{else}&nbsp;{/if}</td>
</tr>
{/if}
<tr>
  <td class="image_cell" colspan="2">
  <div class="image_title_single">{$image.title}</div>
  {if !empty($image.rating_form)}{$image.rating_form}{/if}
  {if !empty($image.media_html)}
  {$image.media_html}
  {else}
  {if !empty($image.popup_needed)}<a rel="fancybox-p{$pluginid}" href="{$image.folder_url}{$image.image_file_full}" target="_blank">{/if}
  <img title="{if !empty($image.popup_needed)}{$language.click_to_enlarge}{else}{$image.imagetitle}{/if}" alt="{$image.imagetitle}" src="{$image.folder_url}{$image.image_file_thumb}" width="{$image.image_width}" height="{$image.image_height}" style="max-width: {$image.max_width}px; max-height: {$image.max_height}px" />
  {if !empty($image.popup_needed)}</a>{/if}
  <br />
  {if empty($image.mt) && !empty($image.filesize_readable)}<br />
  <p style="text-align:center">Filesize: {$image.filesize_readable},&nbsp;
  Resolution: {$image.px_width}px / {$image.px_height}px</p>{/if}

  <a title="[Open in new window]" target="_blank" rel="nofollow" href="{$image.folder_url}{$image.image_file_full}">Media in new window</a><br />

  {if empty($image.media_type)}
  <a title="[Download Foto]" target="_blank" rel="nofollow" href="{$sdurl}plugins/{$plugin_folder}/img.php?imageid={$image.imageid}">Download Foto</a>
  {/if}

  {/if}
  {if !empty($image.mod_links)}
    <div style="clear:both;text-align:center">{$image.mod_links}</div>
  {/if}
  </td>
</tr>
{if ($settings.image_navigation_links == 0) || ($settings.image_navigation_links == 2)}
<tr>
  <td class="previous_img">{if !empty($image.prev_img_url)}<a href="{$image.prev_img_url}" class="previmg">{$language.previous_image}</a>{else}&nbsp;{/if}</td>
  <td class="next_img">{if !empty($image.next_img_url)}<a href="{$image.next_img_url}" class="nextimg">{$language.next_image}</a>{else}&nbsp;{/if}</td>
</tr>
{/if}
<tr>
<td colspan="2">
{if !empty($image.description)}<div class="image_descr">{$image.description}</div>{/if}
<div class="image_details">
{if !empty($display_author)}
  {if !empty($image.image_added_label)}
    {$image.image_added_label}<br />
  {else}
    {if !empty($image.author)}{$language.submitted_by} {$image.author}<br />{/if}
  {/if}
{/if}
{if !empty($section.display_view_counts) && !empty($image.viewcount)}
  {$image.viewcount} {if $image.viewcount==1}{$language.view}{else}{$language.views}{/if}<br />
{/if}
{if !empty($image.display_comments) && !empty($image.comments_count)}
  {$image.comments_count} {if $image.comments_count==1}{$sdlanguage.common_comment}{else}{$sdlanguage.common_comments}{/if}<br />
{/if}
</div>

{* SD362: display media tags if enabled *}
{if !empty($settings.display_tags) && !empty($image.tags)}
  <div class="{$prefix}_tags"><span>{$language.tags}</span><br />
  <ul>
  {foreach from=$image.tags item=tag}
  <li class="{$prefix}_background">{$tag}</li>
  {/foreach}
  </ul>
  </div>
{/if}
{if !empty($section.display_social_media)}
<div class="media_social_tools">
<ul class="social_tools">
<li class="social twitter"><a href="http://twitter.com/home?status={$image.social_title}+{$image.social_url}" target="_blank" title="{$image.social_twitter_title}" rel="nofollow"></a></li>
<li class="social delicious"><a href="http://delicious.com/save?v=5&amp;url={$image.social_url}&amp;title={$image.social_title}" target="_blank" title="{$image.social_delicious_title}" rel="nofollow"></a></li>
<li class="social facebook"><a href="http://www.facebook.com/share.php?u={$image.social_url}&amp;t={$image.social_title}" target="_blank" title="{$image.social_facebook_title}" rel="nofollow"></a></li>
<li class="social digg"><a href="http://digg.com/submit?url={$image.social_url}&amp;title={$image.social_title}" target="_blank" title="{$image.social_digg_title}" rel="nofollow"></a></li>
</ul>
</div>
{/if}

{* SD362: display media edit form (if allowed) *}
{if !empty($allow_media_edit) && !empty($editor_html)}
<div class="media-editform"> {* DO NOT CHANGE CLASS NAME! *}
  <a class="imgedit {$prefix}_background" href="{$editlink}"><span>{$language.edit_media}</span></a>
  <form id="p{$pluginid}-form" name="form-p{$pluginid}" action="{$editlink}" method="post" style="display:none">
  <input type="hidden" name="{$prefix}_action" value="update_media" />
  <input type="hidden" name="{$prefix}_id" value="{$image.imageid}" />
  {$token_element}
  {$language.image_title}
  <input type="text" name="{$prefix}_title" value="{$image.title}" maxlength="128" /><br />
  {$language.description}<br />
  {$editor_html}
  <fieldset>
  {if !empty($allow_section_edit) && empty($image.media_type)}
  <label for="{$prefix}_sectionthumb">
  <input type="checkbox" id="{$prefix}_sectionthumb" name="{$prefix}_sectionthumb" value="1" {if $is_section_thumb}checked="checked" {/if}/> {$language.set_as_section_thumb}</label><br />
  {/if}
  <label for="{$prefix}_private">
  <input type="checkbox" id="{$prefix}_private" name="{$prefix}_private" value="1" {if $image.private}checked="checked" {/if}/> {$language.submit_option_private}</label><br />
  <label for="{$prefix}_allowcomments">
  <input type="checkbox" id="{$prefix}_allowcomments" name="{$prefix}_allowcomments" value="1" {if $image.allowcomments}checked="checked" {/if}/> {$language.submit_option_comments}</label><br />
  <label for="{$prefix}_allowratings">
  <input type="checkbox" id="{$prefix}_allowratings" name="{$prefix}_allowratings" value="1" {if $image.allow_ratings}checked="checked" {/if}/> {$language.submit_option_ratings}</label><br />
  <label for="{$prefix}_showauthor">
  <input type="checkbox" id="{$prefix}_showauthor" name="{$prefix}_showauthor" value="1" {if $image.showauthor}checked="checked" {/if}/> {$language.submit_option_author}</label><br />
  {if !empty($allow_section_delete) || !empty($allow_media_delete)}
  <label for="{$prefix}_unapprove">
  <input type="checkbox" id="{$prefix}_unapprove" name="{$prefix}_unapprove" value="1"  {if $image.activated}{else}checked="checked" {/if}/> {$language.unapprove}?</label><br />
  <label for="{$prefix}__{$deletekey}">
  <input type="checkbox" id="{$prefix}_{$deletekey}" name="{$prefix}_{$deletekey}" value="1" /> {$language.delete}?</label><br />
  {/if}
  </fieldset>
  <input class="{$prefix}_background" type="submit" value="{$language.update_image}" />
  </form>
</div>
{/if}

</td>
</tr>
</table>
{/strip}