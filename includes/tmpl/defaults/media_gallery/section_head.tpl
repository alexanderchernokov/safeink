{* Media Gallery - Section head template - 2013-10-02 *}
{* Put any comment in curly brackets with * next to it like the first line.
   Comments are not displayed on the website and can assist in documentation. *}
{* Common top div *}
<div class="section_header">
{if empty($tag) && !empty($section.description)}
<br />
<div class="section_descr">{$section.description}</div>
{/if}
{if !empty($tag)}
  {if !empty($language.search_results)}
  <div class="images_header">{$language.search_results} <strong>{$tag}</strong></div>
  {/if}
  {if !empty($search_message)}{$search_message}{/if}
{else}
  <div class="images_header">
  {if !empty($section.section_added_label)}{$section.section_added_label}<br />{/if}

  {* SD370: display of image contributors for section *}
  {if !empty($contributors)}
  <div class="contributors">
  {$language.images_contributed_by}
  <ul class="contributors">
    {foreach from=$contributors item=contrib}
    <li>{$contrib.link} ({$contrib.count})</li>
    {/foreach}
  </ul>
  </div>
  {/if}
{*
  {if empty($section.imagecount)}{$language.no_entries_in_section}{else}{$language.section_images_count} {$section.imagecount}<br />{/if}
*}
  {if !empty($section.display_social_media)}
  <div class="media_social_tools_section">
  <ul class="social_tools">
  <li class="social twitter"><a href="http://twitter.com/home?status={$section.social_title}+{$section.social_url}" target="_blank" title="{$section.social_twitter_title}" rel="nofollow"></a></li>
  <li class="social delicious"><a href="http://delicious.com/save?v=5&amp;url={$section.social_url}&amp;title={$section.social_title}" target="_blank" title="{$section.social_delicious_title}" rel="nofollow"></a></li>
  <li class="social facebook"><a href="http://www.facebook.com/share.php?u={$section.social_url}&amp;t={$section.social_title}" target="_blank" title="{$section.social_facebook_title}" rel="nofollow"></a></li>
  <li class="social digg"><a href="http://digg.com/submit?url={$section.social_url}&amp;title={$section.social_title}" target="_blank" title="{$section.social_digg_title}" rel="nofollow"></a></li>
  </ul>
  </div>
  {/if}

{* SD370: display section edit form (if allowed) *}
{if !empty($allow_section_edit) && !empty($editor_html)}
<div class="media-editform"> {* DO NOT CHANGE CLASS NAME! *}
  <a class="imgedit btn btn-primary" href="{$editlink}"><span class="">{$language.edit_section}</span></a>
  <br />
  <form id="p{$pluginid}-form" name="form-p{$pluginid}" action="{$editlink}" method="post" style="display:none">
  <input type="hidden" name="{$prefix}_action" value="update_section" />
  <input type="hidden" name="{$prefix}_id" value="{$section.sectionid}" />
  {$token_element}
  <fieldset>
  <label for="{$prefix}_name">
  <h3><b>{$language.image_title}</b></h3>
  <input type="text" id="{$prefix}_name" name="{$prefix}_name" value="{$section.name}" maxlength="128" /></label><br />
  <label for="{$prefix}_description">
  <h3><b>{$language.description}</b></h3>
  {$editor_html}</label>
  <h3><b>{$language.submit_options}:</b></h3>
  {if $section.sectionid > 1}
  <label for="{$prefix}_activated">
  <input type="checkbox" id="{$prefix}_activated" name="{$prefix}_activated" value="1" {if $section.activated}checked="checked" {/if}/> {$language.display_section_online}</label><br />
  {/if}
  <label for="{$prefix}_displaycomments">
  <input type="checkbox" id="{$prefix}_displaycomments" name="{$prefix}_displaycomments" value="1" {if $section.display_comments}checked="checked" {/if}/> {$language.section_display_comments}</label><br />
  <label for="{$prefix}_displayratings">
  <input type="checkbox" id="{$prefix}_displayratings" name="{$prefix}_displayratings" value="1" {if $section.display_ratings}checked="checked" {/if}/> {$language.section_display_ratings}</label><br />
  <label for="{$prefix}_displaysocial">
  <input type="checkbox" id="{$prefix}_displaysocial" name="{$prefix}_displaysocial" value="1" {if $section.display_social_media}checked="checked" {/if}/> {$language.section_display_socialmedia}</label><br />
  <label for="{$prefix}_displayviews">
  <input type="checkbox" id="{$prefix}_displayviews" name="{$prefix}_displayviews" value="1" {if $section.display_view_counts}checked="checked" {/if}/> {$language.section_display_view_counts}</label><br />

  <input class="btn btn-primary" type="submit" value="{$language.update_section}" />
  </fieldset>
  </form>
</div>
{/if}

  </div><!-- images_header -->
{/if}

{* Header pagination or prev/next image links (common) *}
{if $pagination_needed}
  {if ($pagination_links <= 1)}
    {if ($pagination_links < 2) && ($pagination_html !== false)}
    <div class="{$prefix}_pager_top">{$pagination_html}</div>
    {/if}
  {else}
    {if !empty($image_nav_links) && (($image_navigation_links == 0) || ($image_navigation_links == 2))}
    {$image_nav_links}
    {/if}
  {/if}
{/if}
</div>
