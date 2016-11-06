<?php
if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

// ####################### DETERMINE CURRENT DIRECTORY ########################
$plugin_folder = sd_GetCurrentFolder(__FILE__);

// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,6))
{
  if(strlen($installtype))
  {
    echo 'Articles: upgrade to '.PRGM_NAME.' v3.6 or higher required for this plugin ('.$plugin_folder.').<br />';
  }
  return false;
}

// ############################################################################
// PLUGIN INFORMATION
// ############################################################################
// Do NOT set $uniqueid or $pluginid here!

$base_plugin = $pluginname = 'Articles'; //SD341: set $base_plugin too
$version        = '3.7.1';
$pluginpath     = $plugin_folder.'/news.php';
$settingspath   = $plugin_folder.'/settings.php';
$authorname     = 'subdreamer_web';
$authorlink     = 'http://www.subdreamer.com';
$pluginsettings = '63';

// 1. Check if this plugin is already installed from within the current folder:
// Only *IF* it is installed, set the plugin id so that it is treated
// as installed and does not show up for installation again
if($plugin_folder=='p2_news') //SD370
  $pluginid = 2;
else
if($inst_check_id = GetPluginIDbyFolder($plugin_folder))
{
  $pluginid = $inst_check_id;
}
else
// 2. Check if a plugin with the same name is already installed
if($inst_check_id = GetPluginID($pluginname))
{
  $pluginname .= ' ('.$plugin_folder.')';
}
$authorname .= "<br />Plugin folder: '<b>plugins/$plugin_folder</b>'";

if(empty($installtype))
{
  // Nothing else to do, so return
  return true;
}


if(!function_exists('UpgradeArticles340'))
{
function UpgradeArticles340()
{
  global $DB, $pluginid;

  if(empty($pluginid)) return false;

  // Add plugin settings
  $DB->query("UPDATE {adminphrases} SET varname = CONCAT(varname,'r') WHERE pluginid = %d AND varname LIKE '%\_desc'", $pluginid);
  $DB->query("UPDATE {pluginsettings} SET description = CONCAT(description,'r') WHERE pluginid = %d AND description LIKE '%\_desc'", $pluginid);
  InsertAdminPhrase($pluginid,   'article_display_settings', 'Article Display Settings');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Admin Edit Link', 'Display an admin link to open the admin page to edit the current article (default: No)?', 'yesno', '0', '5');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Display Author', 'Display the author\'s name?', 'yesno', '1', '10');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Display Creation Date', 'Display the article\'s creation date?', 'yesno', '1', '20');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Display Email Link', 'Display an Email link?', 'yesno', '1', '30');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Display Description In Article', 'Would you like the article\'s description to be displayed when viewing the full article?', 'yesno', '0', '40');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Display on Main Page', 'If you select "No", then articles will be hidden from your website.', 'yesno', '1', '50');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Display Print Link', 'Display the "Print Article" link?', 'yesno', '1', '60');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Display PDF Link', 'Display a link to convert the article to a PDF document?', 'yesno', '1', '70');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Display Social Media Links', 'Display social media links for each article?', 'yesno', '1', '80');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Display Title', 'Display the title of the article?', 'yesno', '1', '90');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Display Updated Date', 'Display the date of when the article was last updated?', 'yesno', '1', '100');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Display Comments', 'Display user comments after each article?', 'yesno', '1', '110');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Display User Ratings', 'Enable/display rating of articles for users?', 'yesno', '1', '120');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Display Views Count', 'Display the number of views for each article (default: no)?', 'yesno', '1', '130');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Article Separator', 'How would you like the articles to be separated?<br />You can enter HTML tags here.', 'text', '', '140');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Sticky Article', 'Sticky articles remain stuck on your website, you should disable this option.', 'yesno', '0', '150');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Title Link', 'Would you like the article\'s title to link to the full article?', 'yesno', '1', '160');
  InsertPluginSetting($pluginid, 'article_display_settings', 'PDF Page Size', 'Default page size for PDF document?', 'pagesize', 'Letter', '170');

  // plugin language
  InsertAdminPhrase($pluginid, 'admin_edit_link', 'Admin Edit Link', 2);
  InsertAdminPhrase($pluginid, 'admin_edit_link_descr', 'Display an admin link to open the admin page to edit the current article (default: No)?', 2);
  InsertAdminPhrase($pluginid, 'article', 'Article', 2);
  InsertAdminPhrase($pluginid, 'article_created', 'Article Created', 2);
  InsertAdminPhrase($pluginid, 'article_description', 'Article Description', 2);
  InsertAdminPhrase($pluginid, 'article_details', 'Article Details', 2);
  InsertAdminPhrase($pluginid, 'article_separator', 'Article Separator', 2);
  InsertAdminPhrase($pluginid, 'article_separator_descr', 'How would you like the articles to be separated?<br />You can enter HTML tags here.', 2);
  InsertAdminPhrase($pluginid, 'article_sorting_method', 'Article Sorting Method', 2);
  InsertAdminPhrase($pluginid, 'article_title_az', 'Article Title A-Z', 2);
  InsertAdminPhrase($pluginid, 'article_title_za', 'Article Title Z-A', 2);
  InsertAdminPhrase($pluginid, 'article_updated', 'Article Updated', 2);
  InsertAdminPhrase($pluginid, 'articles', 'Articles', 2);
  InsertAdminPhrase($pluginid, 'articles_apply_filter', 'Apply Filter', 2);
  InsertAdminPhrase($pluginid, 'articles_clear_filter', 'Clear Filter', 2);
  InsertAdminPhrase($pluginid, 'articles_date_asc', 'Oldest First', 2);
  InsertAdminPhrase($pluginid, 'articles_date_desc', 'Newest First', 2);
  InsertAdminPhrase($pluginid, 'articles_deleted', 'Articles Deleted', 2);
  InsertAdminPhrase($pluginid, 'articles_filter', 'Filter', 2);
  InsertAdminPhrase($pluginid, 'articles_filter_title', 'Filter Articles', 2);
  InsertAdminPhrase($pluginid, 'articles_max_comments', 'Close Comments if more than:', 2);
  InsertAdminPhrase($pluginid, 'articles_offline', 'Offline', 2);
  InsertAdminPhrase($pluginid, 'articles_online', 'Online', 2);
  InsertAdminPhrase($pluginid, 'articles_onlineoffline', 'Online/Offline', 2);
  InsertAdminPhrase($pluginid, 'articles_updated', 'Articles Updated', 2);
  InsertAdminPhrase($pluginid, 'author', 'Author', 2);
  InsertAdminPhrase($pluginid, 'author_name_az', 'Author Name A-Z', 2);
  InsertAdminPhrase($pluginid, 'author_name_za', 'Author Name Z-A', 2);
  InsertAdminPhrase($pluginid, 'confirm_remove_tag', 'Remove selected tag for current article?', 2);
  InsertAdminPhrase($pluginid, 'date_created', 'Date Created', 2);
  InsertAdminPhrase($pluginid, 'date_updated', 'Date Updated', 2);
  InsertAdminPhrase($pluginid, 'delete', 'Delete', 2);
  InsertAdminPhrase($pluginid, 'delete_the_following_articles', 'Delete the following articles?', 2);
  InsertAdminPhrase($pluginid, 'display_author', 'Display Author', 2);
  InsertAdminPhrase($pluginid, 'display_author_descr', 'Display the author\'s name?', 2);
  InsertAdminPhrase($pluginid, 'display_comments', 'Display User Comments', 2);
  InsertAdminPhrase($pluginid, 'display_comments_descr', 'Display user comments after each article?', 2);
  InsertAdminPhrase($pluginid, 'display_creation_date', 'Display Creation Date', 2);
  InsertAdminPhrase($pluginid, 'display_creation_date_descr', 'Display the article\'s creation date?', 2);
  InsertAdminPhrase($pluginid, 'display_description_in_article', 'Display Description Inside Article', 2);
  InsertAdminPhrase($pluginid, 'display_description_in_article_descr', 'Would you like the article\'s description to be displayed when viewing the full article?', 2);
  InsertAdminPhrase($pluginid, 'display_email_link', 'Display Email Link', 2);
  InsertAdminPhrase($pluginid, 'display_email_link_descr', 'Display an Email link?', 2);
  InsertAdminPhrase($pluginid, 'display_multiple_pages_of_articles', 'Display Multiple Pages of Articles', 2);
  InsertAdminPhrase($pluginid, 'display_on_common_page', 'Display Article on main page', 2);
  InsertAdminPhrase($pluginid, 'display_on_main_page', 'Display Article on main page', 2);
  InsertAdminPhrase($pluginid, 'display_on_main_page_descr', 'If you select "No", then articles will be hidden from your website.', 2);
  InsertAdminPhrase($pluginid, 'display_order', 'Display Order', 2);
  InsertAdminPhrase($pluginid, 'display_order_asc', 'Display Order Asc', 2);
  InsertAdminPhrase($pluginid, 'display_order_desc', 'Display Order descr', 2);
  InsertAdminPhrase($pluginid, 'display_pdf_link', 'Display PDF Link', 2);
  InsertAdminPhrase($pluginid, 'display_pdf_link_descr', 'Display a link to convert the article to a PDF document?', 2);
  InsertAdminPhrase($pluginid, 'display_print_link', 'Display Print Article Link', 2);
  InsertAdminPhrase($pluginid, 'display_print_link_descr', 'Display the "Print Article" link?', 2);
  InsertAdminPhrase($pluginid, 'display_social_links', 'Display Social Links', 2);
  InsertAdminPhrase($pluginid, 'display_social_media_links', 'Display Social Media Links', 2);
  InsertAdminPhrase($pluginid, 'display_social_media_links_descr', 'Display social media links for each article?', 2);
  InsertAdminPhrase($pluginid, 'display_tags', 'Display Tags', 2);
  InsertAdminPhrase($pluginid, 'display_tags_descr', 'Display tags for article?', 2);
  InsertAdminPhrase($pluginid, 'display_title', 'Display Title', 2);
  InsertAdminPhrase($pluginid, 'display_title_descr', 'Display the title of the article?', 2);
  InsertAdminPhrase($pluginid, 'display_updated_date', 'Display Updated Date', 2);
  InsertAdminPhrase($pluginid, 'display_updated_date_descr', 'Display the date of when the article was last updated?', 2);
  InsertAdminPhrase($pluginid, 'display_ratings', 'Display Ratings', 0);
  InsertAdminPhrase($pluginid, 'display_views_count', 'Display Views Count', 2);
  InsertAdminPhrase($pluginid, 'display_views_count_descr', 'Display the number of views for each article (default: no)?', 2);
  InsertAdminPhrase($pluginid, 'edit_description', 'Edit Description', 2);
  InsertAdminPhrase($pluginid, 'edit_tags_hint', 'Separate tags by comma and remove by clicking the button next to each.', 2);
  InsertAdminPhrase($pluginid, 'end_publishing', 'End Publishing', 2);
  InsertAdminPhrase($pluginid, 'enter_search_terms', 'Enter in your search terms:', 2);
  InsertAdminPhrase($pluginid, 'insert_article', 'Submit Article', 2);
  InsertAdminPhrase($pluginid, 'last_modified', 'Last Modified', 2);
  InsertAdminPhrase($pluginid, 'limit', 'Limit', 2);
  InsertAdminPhrase($pluginid, 'max_articles_per_page', 'Max Articles Per Page', 2);
  InsertAdminPhrase($pluginid, 'meta_description', 'Meta Description', 2);
  InsertAdminPhrase($pluginid, 'meta_keywords', 'Meta Keywords', 2);
  InsertAdminPhrase($pluginid, 'newest_first', 'Newest First', 2);
  InsertAdminPhrase($pluginid, 'no', 'No', 2);
  InsertAdminPhrase($pluginid, 'no_articles_exist', 'No articles exist, click on "Write New Article" to get started.', 2);
  InsertAdminPhrase($pluginid, 'no_articles_found', 'No articles found.', 2);
  InsertAdminPhrase($pluginid, 'offline', 'Offline', 2);
  InsertAdminPhrase($pluginid, 'oldest_first', 'Oldest First', 2);
  InsertAdminPhrase($pluginid, 'online', 'Online', 2);
  InsertAdminPhrase($pluginid, 'order_author_asc', 'Author Name A-Z', 2);
  InsertAdminPhrase($pluginid, 'order_author_desc', 'Author Name Z-A', 2);
  InsertAdminPhrase($pluginid, 'order_title_asc', 'Article Title A-Z', 2);
  InsertAdminPhrase($pluginid, 'order_title_desc', 'Article Title Z-A', 2);
  InsertAdminPhrase($pluginid, 'original_author', 'Original Author', 2);
  InsertAdminPhrase($pluginid, 'page', 'Page', 2);
  InsertAdminPhrase($pluginid, 'page_article_settings', 'Page Article Settings', 2);
  InsertAdminPhrase($pluginid, 'page_name', 'Page Name', 2);
  InsertAdminPhrase($pluginid, 'pagebreak_description', 'Separate multiple pages with {pagebreak}', 2);
  InsertAdminPhrase($pluginid, 'pdf_page_size', 'PDF Page Size', 2);
  InsertAdminPhrase($pluginid, 'pdf_page_size_descr', 'Default page size for PDF document?', 2);
  InsertAdminPhrase($pluginid, 'publish_date', 'Publish Date', 2);
  InsertAdminPhrase($pluginid, 'search', 'Search', 2);
  InsertAdminPhrase($pluginid, 'search_articles', 'Search Articles', 2);
  InsertAdminPhrase($pluginid, 'seo_title', 'SEO Title', 2);
  InsertAdminPhrase($pluginid, 'seo_title_hint1', 'Note: leave empty to auto-generate', 2);
  InsertAdminPhrase($pluginid, 'settings', 'Settings', 2);
  InsertAdminPhrase($pluginid, 'settings_updated', 'Settings Updated', 2);
  InsertAdminPhrase($pluginid, 'sort_by', 'Sort by', 2);
  InsertAdminPhrase($pluginid, 'start_publishing', 'Start Publishing', 2);
  InsertAdminPhrase($pluginid, 'status', 'Status', 2);
  InsertAdminPhrase($pluginid, 'status_pending_review', 'Offline till approved by Administrator', 2);
  InsertAdminPhrase($pluginid, 'sticky_article', 'Sticky Article', 2);
  InsertAdminPhrase($pluginid, 'sticky_article_descr', 'Sticky articles remain stuck on your website, you should disable this option.', 2);
  InsertAdminPhrase($pluginid, 'tags', 'Tags<br />Separate individual tags by a comma (,).', 2);
  InsertAdminPhrase($pluginid, 'title', 'Title', 2);
  InsertAdminPhrase($pluginid, 'title_link', 'Title Link', 2);
  InsertAdminPhrase($pluginid, 'title_link_descr', 'Would you like the article\'s title to link to the full article?', 2);
  InsertAdminPhrase($pluginid, 'update_article', 'Update Article', 2);
  InsertAdminPhrase($pluginid, 'update_articles', 'Update Articles', 2);
  InsertAdminPhrase($pluginid, 'use_global_settings', 'Use Global Settings', 2);
  InsertAdminPhrase($pluginid, 'views', 'Views', 2);
  InsertAdminPhrase($pluginid, 'yes', 'Yes', 2);

  InsertPhrase($pluginid, 'by', 'by');
  InsertPhrase($pluginid, 'comments', 'Comments');
  InsertPhrase($pluginid, 'display_ratings', 'Display Ratings');
  InsertPhrase($pluginid, 'email_to_friend', 'Email to a Friend');
  InsertPhrase($pluginid, 'insert_article_into_page', 'Insert new article into this page.');
  InsertPhrase($pluginid, 'pdf', 'PDF');
  InsertPhrase($pluginid, 'pdf_print_failed', 'We apologise, but the requested article could not be converted to PDF at this time.');
  InsertPhrase($pluginid, 'print', 'Print');
  InsertPhrase($pluginid, 'published', 'Published on ');
  InsertPhrase($pluginid, 'read_more', 'Read More...');
  InsertPhrase($pluginid, 'social_link_delicious', 'Share on Delicious');
  InsertPhrase($pluginid, 'social_link_digg', 'Digg this');
  InsertPhrase($pluginid, 'social_link_facebook', 'Share on Facebook');
  InsertPhrase($pluginid, 'social_link_twitter', 'Share on Twitter');
  InsertPhrase($pluginid, 'tags', 'Tagged with:');
  InsertPhrase($pluginid, 'updated', 'Updated on ');
  InsertPhrase($pluginid, 'views', 'Views:');

  $table = PRGM_TABLE_PREFIX.'p'.$pluginid.'_news';
  $DB->add_tablecolumn($table, 'tags', 'MEDIUMTEXT', 'NOT NULL COLLATE utf8_unicode_ci');
  $DB->add_tablecolumn($table, 'org_author_id', 'INT(10)', 'NULL DEFAULT 0');
  $DB->add_tablecolumn($table, 'org_author_name', 'TEXT', 'COLLATE utf8_unicode_ci');
  $DB->add_tablecolumn($table, 'org_system_id', 'INT(10)', 'NOT NULL DEFAULT 0');
  $DB->add_tablecolumn($table, 'org_created_date', 'INT(10)', 'NOT NULL DEFAULT 0 AFTER `org_system_id`');
  $DB->add_tablecolumn($table, 'last_modifier_system_id', 'INT(10)', 'NOT NULL DEFAULT 0');
  $DB->add_tablecolumn($table, 'last_modifier_id', 'INT(10)', 'NOT NULL DEFAULT 0');
  $DB->add_tablecolumn($table, 'last_modifier_name', 'TEXT', 'COLLATE utf8_unicode_ci');
  $DB->add_tablecolumn($table, 'last_modified_date', 'INT(10)', 'NOT NULL DEFAULT 0');
  $DB->add_tablecolumn($table, 'max_comments', 'INT(10) NOT NULL DEFAULT 0');
  $DB->add_tablecolumn($table, 'paperformat', "VARCHAR(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''");

  $DB->query("ALTER TABLE ".$table." CHANGE COLUMN `title` `title` TEXT NOT NULL");
  $DB->query("ALTER TABLE ".$table." CHANGE COLUMN `author` `author` TEXT NOT NULL");
  $DB->query("ALTER TABLE ".$table." CHANGE COLUMN `metadescription` `metadescription` MEDIUMTEXT NOT NULL");
  $DB->query("ALTER TABLE ".$table." CHANGE COLUMN `metakeywords` `metakeywords` MEDIUMTEXT NOT NULL");
  $DB->query("ALTER TABLE ".$table." CHANGE COLUMN `description` `description` MEDIUMTEXT NOT NULL");

} //UpgradeArticles340
} //DO NOT REMOVE!


if(!function_exists('UpgradeArticles341'))
{
function UpgradeArticles341()
{
  global $DB, $pluginid;

  if(empty($pluginid)) return false;

  InsertAdminPhrase($pluginid, 'multiple_pages_hint1', 'Add Article to other Pages: ', 2);
  InsertAdminPhrase($pluginid, 'multiple_pages_hint2', 'Secondary pages:', 2);

  // Article plugin "Article-to-Pages" relation table
  $DB->query("CREATE TABLE IF NOT EXISTS {p".$pluginid."_articles_pages} (
    `articleid` int(11) NOT NULL,
    `categoryid` int(11) NOT NULL,
    PRIMARY KEY (`articleid`,`categoryid`),
    KEY `articleid`  (`articleid`),
    KEY `categoryid` (`categoryid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

  DeleteAdminPhrase(0, 'articles_clear_search', 0);
  DeleteAdminPhrase(0, 'display_user_ratings', 0);
  $DB->query('DELETE FROM {adminphrases} WHERE (adminpageid = 0 OR pluginid = 0)'.
             " AND varname IN ('display_user_ratings','display_ratings')");
  InsertAdminPhrase($pluginid, 'display_user_ratings', 'Display User Ratings', 2);
  InsertAdminPhrase($pluginid, 'display_user_ratings_descr', 'Enable/display rating of articles for users?', 2);

  $table = PRGM_TABLE_PREFIX.'p'.$pluginid.'_news';
  $DB->add_tablecolumn($table, 'thumbnail', 'VARCHAR(255)', "COLLATE utf8_unicode_ci NOT NULL DEFAULT ''");
  $DB->add_tablecolumn($table, 'featured', 'TINYINT', "NOT NULL DEFAULT 0");
  $DB->add_tablecolumn($table, 'featuredpath', 'VARCHAR(255)', "COLLATE utf8_unicode_ci NOT NULL DEFAULT ''");
  $DB->add_tablecolumn($table, 'rating', 'VARCHAR(10)', "COLLATE utf8_unicode_ci NOT NULL DEFAULT ''");

  $DB->ignore_error = true;
  $DB->query('INSERT INTO {p'.$pluginid.'_articles_pages} (articleid, categoryid)
    SELECT articleid, categoryid FROM {p'.$pluginid.'_news} news
    WHERE NOT EXISTS(SELECT 1 FROM {p'.$pluginid.'_articles_pages} WHERE articleid = news.articleid AND categoryid = news.categoryid)');
  $DB->ignore_error = false;

} //UpgradeArticles341
} //DO NOT REMOVE!


if(!function_exists('UpgradeArticles342'))
{
function UpgradeArticles342()
{
  global $DB, $pluginid;

  if(empty($pluginid)) return false;

  InsertPluginSetting($pluginid, 'article_display_settings', 'Display Tags', 'Display article tags (default: Yes)?', 'yesno', '1', '165');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Display as Popup', 'Display as popup (default: No)?', 'yesno', '0', '125');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Excerpt Mode Length', 'Excerpt length of article if usergroup has <strong>excerpt mode</strong> enabled (default: 100; minimum: 10)?', 'text', '100', '200');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Ignore Excerpt Mode', 'Ignore the <strong>excerpt mode</strong> for article display if usergroup has <strong>excerpt mode</strong> enabled (default: No)?', 'yesno', '0', '210');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Comments Display Limit',
    'Limit of number of comments per comments page (default: 10)<br />Set this to 0 to display all comments at once.', 'text', '10', '220');

  InsertAdminPhrase($pluginid, 'article_notification_settings', 'Article Notification Settings', 2);
  InsertPluginSetting($pluginid, 'article_notification_settings', 'Notification Recipient Email Address',
    'Specify the email address to which below notification should be sent when an article was submitted and now requires approval (default: empty = no email)<br />
     This relates only to articles submitted by users, whose usergroup has the <strong>Author Mode</strong> active.', 'text', '', '230');
  InsertPluginSetting($pluginid, 'article_notification_settings', 'Notification Email Subject', 'Email subject for email notifications sent to the approver?<br />
    This may include the following macros: [articletitle], [username], [date], [pluginname]', 'text', 'Article needs approval', 240);
  InsertPluginSetting($pluginid, 'article_notification_settings', 'Notification Trigger', 'When should the email be sent?',
    "select:\r\n0|Never (Default)\r\n1|Only new articles\r\n2|Only updated articles\r\n3|New and updated articles", '0', 245);
  InsertPluginSetting($pluginid, 'article_notification_settings', 'Notification Email Body', 'Email message body for notification sent to approver?<br />
  This may include the following macros: [articletitle], [username], [date], [pluginname]', 'wysiwyg',
  'DO NOT REPLY TO THIS EMAIL!<br />
***************************<br />
The following article has been submitted on your website within <strong>[pluginname]</strong>
and may require further action (review/approval):<br />
Title: <strong>[articletitle]</strong><br />
Author: [username]<br />
Date: [date]<br />
', 250);

  InsertAdminPhrase($pluginid, 'article_permissions', 'Article Permissions', 2);
  InsertAdminPhrase($pluginid, 'ignore_excerpt_mode', 'Ignore Excerpt Mode', 2);
  InsertAdminPhrase($pluginid, 'permissions_hint', 'Restrict view access to this article to all (no selection) or any combination of usergroups?<br /><br />
  You may select none, one or multiple usergroups by using [CTRL/Shift+Click] to de-/select entries.', 2);
  InsertAdminPhrase($pluginid, 'permissions_updated', 'Article permissions updated!', 2);
  InsertAdminPhrase($pluginid, 'error_no_thumbnail', 'No valid thumbnail!', 2);
  InsertAdminPhrase($pluginid, 'hide_extras', '[HIDE EXTRAS]', 2);
  InsertAdminPhrase($pluginid, 'show_extras', '[SHOW EXTRAS]', 2);
  InsertAdminPhrase($pluginid, 'review_rating', 'Review Rating', 2);
  InsertAdminPhrase($pluginid, 'review_rating_hint', '(optional; example: "6.5"):', 2);
  InsertAdminPhrase($pluginid, 'featured_title', 'Feature this Article?', 2);
  InsertAdminPhrase($pluginid, 'featured_image', 'Featured Image (optional):', 2);
  InsertAdminPhrase($pluginid, 'featured_image_hint', 'Click here to create a Featured Image!', 2);
  InsertAdminPhrase($pluginid, 'featured_hint', 'The featured image may be used by either a site add-on or within the
  article template to be displayed in a special fashion, like an image rotator or showcase display etc.', 2);
  InsertAdminPhrase($pluginid, 'thumbnail', 'Thumbnail', 2);
  InsertAdminPhrase($pluginid, 'thumbnail_title', 'Thumbnail Image', 2);
  InsertAdminPhrase($pluginid, 'thumbnail_hint', 'Click here to create a Thumbnail!', 2);
  InsertAdminPhrase($pluginid, 'thumbnail_hint2', 'Note: the default template does not use this image!', 2);
  InsertAdminPhrase($pluginid, 'template_name', 'Template:', 2);
  InsertAdminPhrase($pluginid, 'template_hint', 'Hints: with file extention ".tpl"!<br />Leave empty for default template.', 2);
  InsertAdminPhrase($pluginid, 'tags', 'Tags<br />Separate individual tags by pressing ENTER or comma (,) key.', 2);
  InsertAdminPhrase($pluginid, 'height', 'Height:', 2);
  InsertAdminPhrase($pluginid, 'width', 'Width:', 2);
  InsertAdminPhrase($pluginid, 'usergroup', 'Usergroup', 2, true);
  InsertAdminPhrase($pluginid, 'delete_thumbnail', 'Delete Thumbnail?', 2);
  InsertAdminPhrase($pluginid, 'edit_tab_title1', 'Details', 2);
  InsertAdminPhrase($pluginid, 'edit_tab_title2', 'Meta', 2);
  InsertAdminPhrase($pluginid, 'edit_tab_title3', 'Dates', 2);
  InsertAdminPhrase($pluginid, 'edit_tab_title4', 'Other', 2);
  InsertAdminPhrase($pluginid, 'hide', 'Hide', 2);
  InsertAdminPhrase($pluginid, 'show', 'Show', 2);

  $table = PRGM_TABLE_PREFIX.'p'.$pluginid.'_news';
  $DB->add_tablecolumn($table, 'template', 'VARCHAR(255)', "COLLATE utf8_unicode_ci NOT NULL DEFAULT ''");
  $DB->add_tablecolumn($table, 'access_view', 'TEXT', "COLLATE utf8_unicode_ci NULL");
  $DB->add_tablecolumn($table, 'seo_title', 'VARCHAR(250)', "COLLATE utf8_unicode_ci NOT NULL DEFAULT ''");

  $table = PRGM_TABLE_PREFIX.'p'.$pluginid.'_settings';
  $DB->query("UPDATE $table SET sorting = 'newest_first' WHERE sorting = 'IF(IFNULL(datecreated,0)=0, dateupdated, datecreated) DESC'");
  $DB->query("UPDATE $table SET sorting = 'oldest_first' WHERE sorting = 'IF(IFNULL(datecreated,0)=0, dateupdated, datecreated) ASC'");

} //UpgradeArticles342
} //DO NOT REMOVE!


if(!function_exists('UpgradeArticles343'))
{
function UpgradeArticles343()
{
  global $DB, $pluginid;

  if(empty($pluginid)) return false;

  InsertPhrase($pluginid, 'msg_no_results','<p><strong>No articles found.</strong></p>');
  InsertPhrase($pluginid, 'msg_no_tag_results','<h2>Sorry, no results!</h2><p>There are no articles which are tagged by: [tag].</p>');
  InsertPhrase($pluginid, 'article_year_month_head','<p>Articles for [year]/[month]</p>',true);
  InsertPhrase($pluginid, 'article_tags_head','<p>Tag archives for: [tag].</p>');
  InsertPhrase($pluginid, 'msg_no_tag_results','<strong>There are yet no articles available here.</strong>');
  InsertPhrase($pluginid, 'msg_no_results','<strong>There are yet no articles available here.</strong>');
  InsertPhrase($pluginid, 'meta_page_phrase','Page [page]');

  InsertAdminPhrase($pluginid,'link_to_main_page','Link to main Page only?');
  InsertAdminPhrase($pluginid,'link_to_main_page_descr','Let the Read More link always link to the main article page (Yes) or stay on the same page (No) even if it is a secondary page (default: No)?');
  InsertAdminPhrase($pluginid,'confirm_remove_tag_title', 'Remove Tag', 2);
  InsertAdminPhrase($pluginid,'existing_tags_hint', 'Select a single entry and click button OR double-click an entry to add it to the article tags.', 2);

  InsertPluginSetting($pluginid, 'article_display_settings', 'link_to_main_page', 'link_to_main_page_descr', 'yesno', 1, '175');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Display Tagcloud',
    'Display a tagcloud for article tags (default: No)?', "select:\r\n0|No\r\n1|Top\r\n2|Bottom", 0, '300');
  InsertPluginSetting($pluginid, 'article_display_settings', 'No Results Message',
    'Display message (HTML) if no article were found?', 'wysiwyg',
    '<div id="p[pluginid]_container">
      <div class="noarticles"><p><strong>No articles found.</strong></p></div>
    </div>', '400');
  $DB->query("UPDATE {pluginsettings} SET `displayorder` = '170' WHERE pluginid = %d AND title = 'ignore_excerpt_mode'", $pluginid);
  $DB->query("UPDATE {pluginsettings} SET `displayorder` = '175' WHERE pluginid = %d AND title = 'link_to_main_page'", $pluginid);
  $DB->query("UPDATE {pluginsettings} SET `displayorder` = '300' WHERE pluginid = %d AND title = 'display_tagcloud'", $pluginid);
  $DB->query("UPDATE {pluginsettings} SET `displayorder` = '400' WHERE pluginid = %d AND title = 'no_results_message'", $pluginid);
  $DB->query("UPDATE {pluginsettings} SET `displayorder` = '410' WHERE pluginid = %d AND title = 'pdf_page_size'", $pluginid);
  $oldstat = $DB->ignore_error;
  $DB->query("UPDATE {plugins} SET `base_plugin` = 'Articles' WHERE pluginid = %d", $pluginid);

  //Convert existings "tags" column to individual entries in "tags" table
  @include_once(SD_INCLUDE_PATH.'class_sd_tags.php');
  if(class_exists('SD_Tags') &&
     ($getarticles = $DB->query('SELECT articleid, tags FROM '.
                                PRGM_TABLE_PREFIX.'p'.$pluginid.
                                "_news WHERE IFNULL(tags,'') <> ''")))
  {
    $aids = array();
    while($art = $DB->fetch_array($getarticles,null,MYSQL_ASSOC))
    {
      $articleid = (int)$art['articleid'];
      $aids[] = $articleid;
      $tags_old = sd_ConvertStrToArray($art['tags'],',');
      $tags_real = SD_Tags::GetPluginTags($pluginid,$articleid);
      $tags_real = array_unique(array_merge($tags_real,$tags_old));
      $tags_real = implode(',', $tags_real);
      SD_Tags::StorePluginTags($pluginid, $art['articleid'], $tags_real);
    }
    unset($getarticles,$tags);
    foreach($aids as $id)
    {
      $DB->query("UPDATE ".PRGM_TABLE_PREFIX."p".$pluginid.
                 "_news SET tags = '' WHERE articleid = %d",$id);
    }
  }
  $DB->ignore_error = $oldstat;
} //UpgradeArticles343
} //DO NOT REMOVE!


if(!function_exists('UpgradeArticles348'))
{
function UpgradeArticles348()
{
  global $DB, $pluginid;

  if(empty($pluginid)) return false;
  $table = PRGM_TABLE_PREFIX.'p'.$pluginid.'_news';
  $DB->add_tablecolumn($table, 'seo_title', 'VARCHAR(250)', "COLLATE utf8_unicode_ci NOT NULL DEFAULT ''");
  $DB->query('UPDATE '.PRGM_TABLE_PREFIX."pluginsettings SET displayorder = 2 WHERE title = 'admin_edit_link'", $pluginid);
  InsertPluginSetting($pluginid, 'article_display_settings', 'Auto-Add Linebreaks',
    'Automatically add simple linebreaks to description/article HTML code when an article is edited (e.g. after br and p tags; default: No)?', 'yesno', 0, '310');
  InsertPluginSetting($pluginid, 'article_display_settings', 'Article List Template',
    'Specify the template name to be used for displaying the list of articles on the frontpage:<br />'.
    'This allows to have 2 distinct display modes for articles: a <strong>list view</strong> and a <strong>single article view</strong>.<br />'.
    'Advantages: for article titles the <strong>list view</strong> could use &lt;h2&gt; tags, but the single article view'.
    ' can then use &lt;h1&gt; tags, which is recommended for SEO purposes.',
    'template', '', 1);
  InsertAdminPhrase($pluginid,'add_to_tags','Add to Tags',2);

  // New translatable menu phrases per plugin:
  InsertAdminPhrase($pluginid,'menu_articles_view_articles','View Articles',2);
  InsertAdminPhrase($pluginid,'menu_articles_write_article','Write New Article',2);
  InsertAdminPhrase($pluginid,'menu_articles_settings','Article Settings',2);
  InsertAdminPhrase($pluginid,'menu_articles_page_settings','Page Article Settings',2);
  InsertAdminPhrase($pluginid,'menu_articles_search','Search for Articles',2);

  // Assure that default slugs exist
  $tbl = PRGM_TABLE_PREFIX.'slugs';
  if(!$DB->query_first("SELECT name FROM $tbl WHERE name = 'Tag Archive' AND pluginid = %d LIMIT 1", $pluginid))
  {
    $DB->query_first("INSERT INTO `$tbl` (`term_id`, `name`, `description`, `pluginid`, `parent`, `count`, `slug`, `slug_type`) VALUES
    (NULL, 'Tag Archive', 'URI for plugin tags with tag value at then end.', %d, 0, 0, '[tag]', 0)", $pluginid);
  }
  if(!$DB->query_first("SELECT name FROM $tbl WHERE name = 'Tags by Year and Month' AND pluginid = %d LIMIT 1", $pluginid))
  {
    $DB->query_first("INSERT INTO `$tbl` (`term_id`, `name`, `description`, `pluginid`, `parent`, `count`, `slug`, `slug_type`) VALUES
    (NULL, 'Tags by Year and Month', 'URI for tags by year and month', %d, 0, 0, '[year]/[month]', 0)", $pluginid);
  }
} //UpgradeArticles348
} //DO NOT REMOVE!


if(!function_exists('UpgradeArticles349'))
{
function UpgradeArticles349()
{
  global $DB, $pluginid;

  if(empty($pluginid)) return false;

  InsertAdminPhrase(0, 'template_folder_not_found', 'Template folder not found: ', 2);
  InsertAdminPhrase(0, 'template_folder_not_writable', 'Template folder not writable: ', 2);

  // Update SEO title of all articles, that do not yet have one!
  $table = PRGM_TABLE_PREFIX.'p'.$pluginid.'_news';
  if($getarticles = $DB->query('SELECT articleid, title FROM '.$table.
                               " WHERE IFNULL(seo_title,'') = '' ORDER BY articleid DESC"))
  {
    $GLOBALS['mainsettings_seo_filter_words'] = 0;
    while($article = $DB->fetch_array($getarticles,NULL,MYSQL_ASSOC))
    {
      $articleid = $article['articleid'];
      $seotitle = ConvertNewsTitleToUrl($article['title'], 0, 0, true);
      if(!$DB->query_first('SELECT seo_title FROM '.$table.
                           " WHERE articleid != %d AND seo_title = '%s'",
                           $articleid, $DB->escape_string($seotitle)))
      {
        $DB->query('UPDATE '.$table." SET seo_title = '%s' WHERE articleid = %d",
                   $seotitle, $articleid);
      }
    }
  }

} //UpgradeArticles349
} //DO NOT REMOVE!


if(!function_exists('UpgradeArticles351'))
{
function UpgradeArticles351()
{
  global $DB, $pluginid;

  if(empty($pluginid)) return false;

  InsertAdminPhrase($pluginid, 'approved_by', 'Approved by:');
  InsertAdminPhrase($pluginid, 'approved_date', 'Approved:');
  InsertAdminPhrase($pluginid, 'reassign_author', 'Reassign Author?');
  InsertAdminPhrase($pluginid, 'article_admin_settings', 'Articles Admin Settings');

  InsertPluginSetting($pluginid, 'article_admin_settings', 'Admin Default Filter Status',
    'Default status filter setting for articles displayed in admin articles list (default: Both)?',
    "select:\r\nonlineoffline|Both (Default)\r\noffline|Only offline articles\r\nonline|Only online articles", 'onlineoffline', '320');
  $DB->query("UPDATE {pluginsettings} SET `displayorder` = '310' WHERE pluginid = %d AND title = 'auto_add_linebreaks'", $pluginid);

  $table = PRGM_TABLE_PREFIX.'p'.$pluginid.'_news';
  $DB->add_tablecolumn($table, 'approved_by', 'VARCHAR(64)', 'NULL COLLATE utf8_unicode_ci');
  $DB->add_tablecolumn($table, 'approved_date', 'BIGINT(11)', 'NOT NULL DEFAULT 0');

} //UpgradeArticles351
} //DO NOT REMOVE!


if(!function_exists('UpgradeArticles354')) //2013-01-13
{
function UpgradeArticles354()
{
  global $pluginid;

  if(empty($pluginid)) return false;

  InsertPluginSetting($pluginid, 'article_display_settings', 'Ignore Pages For Date Tags',
    'Ignore pages specified for articles when listed by year/month (default: No)?<br />'.
    'Normally only articles are displayed that have the current page listed in their settings. '.
    'Setting this to <strong>Yes</strong> may make it easier to articles in combination '.
    'with the Article Archive plugin if that directs to a different page.',
    'yesno', 0, 166);

  InsertPluginSetting($pluginid, 'article_display_settings', 'Ignore Publish End Date',
    'Ignore any specified <strong>Publish End</strong> date for articles (default: No)?<br />'.
    'Normally articles are no longer visible or accessible once that date has passed. '.
    'If this is set to <strong>Yes</strong>, that date is ignored and articles stay visible. '.
    'This may be desired if the articles plugin is used as an events display.',
    'yesno', 0, 168);

} //UpgradeArticles354
} //DO NOT REMOVE!


if(!function_exists('UpgradeArticles360')) //2013-01-23
{
function UpgradeArticles360()
{
  global $DB, $pluginid;
  if(empty($pluginid)) return false;

  InsertPhrase($pluginid, 'title_attachments',      'Attachments:');
  InsertPhrase($pluginid, 'upload_attachments',     'Upload attachment:');
  InsertPhrase($pluginid, 'upload_error',           'Can not upload attachment, folder not writable or other error.');
  InsertPhrase($pluginid, 'forum_backlink_text',    'Read our original article: [url=%%articlelink%%]%%articletitle%%[/url].',2,true);
  InsertPhrase($pluginid, 'article_backlink_text',  '<br />Follow also our forum discussion for <strong><a href="[topiclink]">[topictitle]</a></strong>.<br />',2,true);
  InsertAdminPhrase($pluginid, 'confirm_delete_thumbnail',  'Really delete/detach thumbnail now?');
  InsertAdminPhrase($pluginid, 'confirm_delete_featurepic', 'Really delete/detach featured picture now?');
  InsertAdminPhrase($pluginid, 'delete_featuredpic',        'Delete Featured Picture?');
  InsertAdminPhrase($pluginid, 'detach_not_delete_image',   'Remove from article (no file deletion)?');
  InsertAdminPhrase($pluginid, 'thumbnail_removed',         'Thumbnail has been removed.');
  InsertAdminPhrase($pluginid, 'featuredpic_removed',       'Featured Picture has been removed.');
  InsertAdminPhrase($pluginid, 'user_allowed_filetypes',    'Allowed filetypes:');
  InsertAdminPhrase($pluginid, 'post_to_forum',             'Post Article to Forum');
  InsertAdminPhrase($pluginid, 'post_descr_to_post',        'Include Article Description in Post');
  InsertAdminPhrase($pluginid, 'post_article_to_post',      'Include Main Article Text in Post');
  InsertAdminPhrase($pluginid, 'post_link_back_to_article', 'Include Link from Post back to Article');
  InsertAdminPhrase($pluginid, 'post_link_to_post',         'Include Link from Article to Post');
  InsertAdminPhrase($pluginid, 'post_forum_to_post',        'Forum to post to:');
  InsertAdminPhrase($pluginid, 'post_article_hint',         'Publish dates in the future do not work with this feature.'.
    ' Once enabled, the post is published to your forum and not reverted by online/offline '.
    ' status of your article or future publishing date.');

  InsertPluginSetting($pluginid, 'article_admin_settings', 'Post to Forum Usergroups',
    'Allow selected usergroups to post articles as a new forum topic (Forum plugin only; default: none):<br />'.
    'De-/select single or multiple entries by CTRL/Shift+click.',
    'usergroups', '1', 330);

  InsertPluginSetting($pluginid, 'article_admin_settings', 'Article Attachment Usergroups',
    'Allow selected usergroups to attach a file to an article (default: none):<br />'.
    'De-/select single or multiple entries by CTRL/Shift+click.',
    'usergroups', '', 400);

  InsertPluginSetting($pluginid, 'article_admin_settings', 'Valid Attachment Types',
    'Allowed file extensions for article attachments (e.g. `zip,txt,png,gif,rar` etc.):<br />'.
    'Note: enter <strong>*</strong> or leave it empty to allow all file types.',
    'text', 'zip', 410);

  InsertPluginSetting($pluginid, 'article_admin_settings', 'Attachments Max Size',
    'Max. filesize (in KB) for attachments (default: 2048):',
    'text', '2048', 415);

  InsertPluginSetting($pluginid, 'article_admin_settings', 'Attachments Upload Path',
    'Folder in which attachments will be uploaded to (CHMOD folder to 777; default: `attachments`; empty = disabled):<br />'.
    'Note: folder/pathname is relative to the CMS root folder, not the plugin folder.',
    'text', 'attachments', 420);

  InsertAdminPhrase($pluginid, 'could_not_create_article_copy', 'Article copying failed (could not retrieve fields from table)!');
  InsertAdminPhrase($pluginid, 'confirm_delete_thumbnail', 'Really delete/detach thumbnail now?');
  InsertAdminPhrase($pluginid, 'invalid_request', 'Invalid request! You may not have access to the article!');
  InsertAdminPhrase($pluginid, 'start_featuring', 'Featured Start');
  InsertAdminPhrase($pluginid, 'end_featuring', 'Featured End');
  $table = PRGM_TABLE_PREFIX.'p'.$pluginid.'_news';
  $DB->add_tablecolumn($table, 'featured_start', 'INT(11)', 'NOT NULL DEFAULT 0');
  $DB->add_tablecolumn($table, 'featured_end',   'INT(11)', 'NOT NULL DEFAULT 0');

} //UpgradeArticles360
} //DO NOT REMOVE!


if(!function_exists('UpgradeArticles371')) //2013-09-28
{
function UpgradeArticles371()
{
  global $DB, $pluginid;
  if(empty($pluginid)) return false;

  InsertAdminPhrase($pluginid, 'featured_thumb', 'F | T');
  InsertAdminPhrase($pluginid, 'hint_featured', 'Featured?');
  InsertAdminPhrase($pluginid, 'featured_shortcut', 'F');
  InsertAdminPhrase($pluginid, 'hint_thumbnail', 'Thumbnail?');
  InsertAdminPhrase($pluginid, 'thumbnail_shortcut', 'T');

  $tbl = PRGM_TABLE_PREFIX.'slugs';
  if(!$DB->query_first("SELECT name FROM $tbl WHERE name = 'Tags by Year, Month and Day' AND pluginid = %d LIMIT 1", $pluginid))
  {
    $DB->query_first("INSERT INTO `$tbl` (`term_id`, `name`, `description`, `pluginid`, `parent`, `count`, `slug`, `slug_type`) VALUES
    (NULL, 'Tags by Year, Month and Day', 'URI for tags by year, month and day', %d, 0, 0, '[year]/[month]/[day]', 0)", $pluginid);
  }

} //UpgradeArticles371
} //DO NOT REMOVE!


if(!function_exists('UpgradeArticlesAddTemplates')) //SD344
{
function UpgradeArticlesAddTemplates()
{
  global $DB, $pluginid, $pluginpath;
  if(empty($pluginid)) return false;

  require_once(SD_INCLUDE_PATH.'class_sd_smarty.php');

  echo '<b>Adding templates to Articles plugin ('.dirname($pluginpath).')...</b>';

  $new_tmpl = array(
    'articles.tpl' => array('Articles', 'Articles template (full, single article view)'),
    'articles_list.tpl' => array('Articles List', 'Articles list template'),
    'articles_list_rss.tpl' => array('Articles List with RSS', 'Articles list template with special header and RSS icon'),
    'articles_review.tpl' => array('Articles Review', 'Article with review settings'),
    'articles_tagged.tpl' => array('Articles with Tags', 'Article with tags'));
  $tpl_path = ROOT_PATH.'includes/tmpl/';
  $tpl_path_def = ROOT_PATH.'includes/tmpl/defaults/';

  // Loop to add DEFAULT templates first:
  foreach($new_tmpl as $tpl_name => $tpl_data)
  {
    // Do not create duplicate
    if(false !== SD_Smarty::TemplateExistsInDB($pluginid, $tpl_name)) continue;

    if(false !== SD_Smarty::CreateTemplateFromFile($pluginid, $tpl_path_def, $tpl_name,
                                                   $tpl_data[0], $tpl_data[1]))
    {
      echo '<br />Default template for <b>"'.$tpl_name.'"</b> added.';
      // Add existing, custom templates as newest revisions
      // that would override default:
      if(is_file($tpl_path.$tpl_name) && is_readable($tpl_path.$tpl_name))
      {
        if($rev_id = SD_Smarty::AddTemplateRevisionFromFile($pluginid, $tpl_path, $tpl_name, $tpl_data[1], true))
        {
          echo '<br /><b>Custom template</b> for <b>"'.$tpl_name.'"</b> added.';
        }
      }
      // for backwards compatibility load "article_frontpage.tpl" if it exists
      if($tpl_name == 'articles.tpl')
      {
        $tpl_tmp = 'article_frontpage.tpl';
        if(is_file($tpl_path.$tpl_tmp) && is_readable($tpl_path.$tpl_tmp))
        {
          $tpl_content = @file_get_contents($tpl_path.$tpl_tmp);
          if(!empty($tpl_content))
          {
            if(SD_Smarty::AddTemplateRevisionFromVar($pluginid, $tpl_name, $tpl_content, 'Custom '.$tpl_data[0], true))
            {
              echo '<br /><b>Custom template</b> for "'.$tpl_name.'" added (Source: '.$tpl_tmp.').';
            }
          }
        }
      }
    }
  }

  echo '<br /><b>Done.</b><br />';

} //UpgradeArticlesAddTemplates
} //DO NOT REMOVE!


// ############################################################################
// INSTALL PLUGIN
// ############################################################################

if($installtype == 'install')
{
  // At this point SD3 has to provide a new plugin id
  if(empty($pluginid))
  {
    $pluginid = CreatePluginID($pluginname);
  }

  // If for whatever reason the plugin id is invalid, return false NOW!
  if(empty($pluginid))
  {
    return false;
  }

  $DB->query("CREATE TABLE IF NOT EXISTS {p".$pluginid."_news} (
    `articleid` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `categoryid` int(10) unsigned NOT NULL DEFAULT '0',
    `settings` int(10) unsigned NOT NULL DEFAULT '0',
    `views` int(10) unsigned NOT NULL DEFAULT '0',
    `displayorder` int(10) unsigned NOT NULL DEFAULT '0',
    `datecreated` int(10) unsigned NOT NULL DEFAULT '0',
    `dateupdated` int(10) unsigned NOT NULL DEFAULT '0',
    `datestart` int(10) unsigned NOT NULL DEFAULT '0',
    `dateend` int(10) unsigned NOT NULL DEFAULT '0',
    `author` text COLLATE utf8_unicode_ci NOT NULL,
    `title` text COLLATE utf8_unicode_ci NOT NULL,
    `metadescription` mediumtext COLLATE utf8_unicode_ci NOT NULL,
    `metakeywords` mediumtext COLLATE utf8_unicode_ci NOT NULL,
    `description` mediumtext COLLATE utf8_unicode_ci NOT NULL,
    `article` mediumtext COLLATE utf8_unicode_ci NOT NULL,
    `seo_title` varchar(250) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
    `tags` mediumtext COLLATE utf8_unicode_ci NOT NULL,
    `org_author_id` int(10) NOT NULL DEFAULT '0',
    `org_system_id` int(10) NOT NULL DEFAULT '0',
    `org_created_date` int(10) NOT NULL DEFAULT '0',
    `last_modifier_system_id` int(10) NOT NULL DEFAULT '0',
    `last_modifier_id` int(10) NOT NULL DEFAULT '0',
    `last_modified_date` int(10) NOT NULL DEFAULT '0',
    `org_author_name` text COLLATE utf8_unicode_ci,
    `last_modifier_name` text COLLATE utf8_unicode_ci,
    `max_comments` int(10) NOT NULL DEFAULT '0',
    `paperformat` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
    PRIMARY KEY (`articleid`),
    KEY `categoryid` (`categoryid`),
    KEY `datecreated` (`datecreated`),
    KEY `dateupdated` (`dateupdated`),
    KEY `datestart` (`datestart`),
    KEY `dateend` (`dateend`),
    KEY `displayorder` (`displayorder`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

  // Article plugin "Article-to-Pages" relation table
  $DB->query('CREATE TABLE IF NOT EXISTS {p'.$pluginid."_settings} (
  `categoryid` int(10) unsigned NOT NULL DEFAULT '0',
  `maxarticles` int(10) unsigned NOT NULL DEFAULT '10',
  `sorting` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Newest First',
  `multiplepages` tinyint(1) NOT NULL DEFAULT '0',
  KEY `categoryid` (`categoryid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

  $CSS = new CSS();
  if(!$CSS->InsertCSS('Articles'.($pluginid==2?'':' ('.$pluginid.')'), 'plugins/'.$plugin_folder.'/css/default.css', false, array(), $pluginid))
  {
    echo '<br/>Failed to insert CSS entry for Articles plugin (ID '.$pluginid.')!';
  }
  unset($CSS);
  $DB->query("DELETE FROM {skin_css} WHERE var_name LIKE 'Articles %' AND plugin_id = 0");

  // Call *every* available upgrade > 3.3.0 now:
  UpgradeArticles340();
  UpgradeArticles341();
  UpgradeArticles342();
  UpgradeArticles343();
  UpgradeArticles348();
  UpgradeArticles349();
  UpgradeArticles351();
  UpgradeArticles354(); //2013-01-13
  UpgradeArticles360(); //2013-01-25
  UpgradeArticles371(); //2013-09-28

  UpgradeArticlesAddTemplates();

} //install


// ############################################################################
// UPGRADE PLUGIN
// ############################################################################

if(!empty($currentversion) && ($installtype == 'upgrade'))
{
  if(empty($pluginid)) return false;

  $DB->query("DELETE FROM {skin_css} WHERE var_name LIKE 'Articles %' AND plugin_id = 0");

  if(version_compare($currentversion, '3.4.0', '<'))
  {
    UpgradeArticles340();

    $DB->query('ALTER TABLE {p'.$pluginid.'_news} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
    UpdatePluginVersion($pluginid, '3.4.0');
    $currentversion = '3.4.0';
  }

  if($currentversion == '3.4.0')
  {
    UpgradeArticles341();
    UpdatePluginVersion($pluginid, '3.4.1');
    $currentversion = '3.4.1';
  }

  if($currentversion == '3.4.1')
  {
    UpgradeArticles342();
    UpdatePluginVersion($pluginid, '3.4.2');
    $currentversion = '3.4.2';
  }

  if(($currentversion == '3.4.2') || ($currentversion == '3.4.3'))
  {
    UpgradeArticles343();
    UpdatePluginVersion($pluginid, '3.4.3');
    $currentversion = '3.4.3';
  }

  if(version_compare($currentversion, '3.4.8', '<'))
  {
    UpgradeArticles348();
    UpdatePluginVersion($pluginid, '3.4.8');
    $currentversion = '3.4.8';
  }

  if(version_compare($currentversion, '3.4.9', '<'))
  {
    UpgradeArticles349();
    UpdatePluginVersion($pluginid, '3.4.9');
    $currentversion = '3.4.9';
  }

  if($currentversion == '3.4.9') //2012-08-13
  {
    UpgradeArticles351();
    UpdatePluginVersion($pluginid, '3.5.1');
    $currentversion = '3.5.1';
  }

  if(version_compare($currentversion, '3.5.4', '<')) //2013-01-13
  {
    UpgradeArticles354();
    UpdatePluginVersion($pluginid, '3.5.4');
    $currentversion = '3.5.4';
  }

  if(version_compare($currentversion, '3.6.0', '<')) //2013-01-23
  {
    UpgradeArticles360();
    UpdatePluginVersion($pluginid, '3.6.0');
    $currentversion = '3.6.0';
  }

  if(version_compare($currentversion, '3.7.1', '<'))
  {
    UpgradeArticles371(); //2013-09-28
    UpdatePluginVersion($pluginid, '3.7.1');
    $currentversion = '3.7.1';
  }

  // General reoccuring updates independent of version:
  if(isset($mainsettings['sdversion']) &&
     version_compare($mainsettings['sdversion'],'3.4.4','ge') &&
     version_compare($currentversion, '3.5.1', '>'))
  {
    $DB->query('ALTER TABLE {p'.$pluginid.'_news} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
    UpgradeArticlesAddTemplates();
  }

  if(isset($mainsettings['sdversion']) &&
     version_compare($mainsettings['sdversion'], '3.4.1', 'ge') &&
     $DB->column_exists(PRGM_TABLE_PREFIX.'plugins','base_plugin'))
  {
    $DB->query("UPDATE {plugins} SET base_plugin = 'Articles' WHERE pluginid = %d", $pluginid);
  }

  $DB->query("UPDATE {plugins} SET name = '%s', settings = %d, pluginpath = '%s', settingspath = '%s', authorname = '%s', reporting = 1".
             ' WHERE pluginid = %d',
             $pluginname.($pluginid==2?'':' ('.$pluginid.')'),
             $pluginsettings, $pluginpath, $settingspath,
             $DB->escape_string($authorname), $pluginid);

} //upgrade


// ############################################################################
// UNINSTALL PLUGIN (SD370: only for cloned plugins!)
// ############################################################################

if(($installtype == 'uninstall') && ($pluginid >= 5000) && ($plugin_folder !== 'p2_news'))
{
  // remove all attachments for plugin
  include_once(SD_INCLUDE_PATH.'class_sd_attachment.php');
  $a = new SD_Attachment($pluginid);
  $a->ProcessPluginAttachments(true);
  unset($a);

  $DB->query("DROP TABLE IF EXISTS {p".$pluginid."_articles_pages}");
  $DB->query("DROP TABLE IF EXISTS {p".$pluginid."_news}");
  $DB->query("DROP TABLE IF EXISTS {p".$pluginid."_settings}");
}