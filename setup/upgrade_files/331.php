<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

// Core table changes
InsertAdminPhrase(0, 'siteactivation', 'Turn Site On/Off');
InsertAdminPhrase(0, 'siteactivation_descr', 'You may want to turn off your site while performing updates or other types of maintenance.');
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."mainsettings SET groupname = 'settings_site_activation' WHERE varname = 'siteactivation'");
$DB->query("ALTER TABLE ".PRGM_TABLE_PREFIX."sessions CHANGE COLUMN `ipaddress` `ipaddress` VARCHAR(32) collate utf8_unicode_ci NOT NULL DEFAULT ''");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'comments', 'categoryid', "INT(11) NOT NULL DEFAULT 0");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'vvc', 'useragent', "VARCHAR(255) collate utf8_unicode_ci NOT NULL DEFAULT ''");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'vvc', 'ipaddress', "VARCHAR(32) collate utf8_unicode_ci NOT NULL DEFAULT ''");

// Correct some settings
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."customplugins SET settings = 17 WHERE settings IS NULL OR settings = 0");
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."users SET user_post_count = 0 WHERE user_post_count IS NULL");
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."users SET user_thread_count = 0 WHERE user_thread_count IS NULL");

// Plugins
InsertPhrase(1, 'mysql_error_message', 'We are sorry, a database error has occurred and pageload stopped, an admin has been notified.');
InsertPhrase(1, 'comments_closed', '<strong>Comments are closed.</strong>');
InsertPhrase(6, 'invalid_information', 'We are sorry, but one or more fields contained invalid information.');
InsertPhrase(6, 'message_first_line', 'Contact Form Email sent by: ');
DeleteAdminPhrase(6, 'require_captcha', 2);
DeleteAdminPhrase(6, 'require_captcha_descr', 2);
$DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."adminphrases WHERE adminpageid = 0 AND varname in ('background_lines','background_lines_descr',
  'background_image','background_image_descr','background_dots','background_dots_descr')");
$DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."pluginsettings WHERE pluginid = 12 AND title IN ('Reset Password Title','Registration Form Title')");
InsertPluginSetting(12, 'user_registration_settings', 'Banned IP Addresses', 'Enter a list of banned ip addresses (one entry per line)<br />You can ban entire subnets (0-255) by using wildcard characters (192.168.0.*, 192.168.*.*) or enter a full ip address:', 'textarea', '', 5, true);
DeleteAdminPhrase(12, 'banned_email_addresses');
DeleteAdminPhrase(12, 'banned_email_addresses_descr');
DeleteAdminPhrase(12, 'registration_options_descr');
InsertAdminPhrase(3, 'display_author', 'Display Author');
InsertAdminPhrase(3, 'display_author_descr', 'Display author name under each news article?');

// Articles (admin page and plugin)
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p2_news', 'max_comments', "INT(10) NOT NULL DEFAULT 0");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p2_news', 'paperformat', "VARCHAR(40) collate utf8_unicode_ci NOT NULL DEFAULT ''");
InsertAdminPhrase(2, 'seo_title_hint1', 'Note: leave empty to auto-generate', 2);
InsertAdminPhrase(2, 'articles_max_comments', 'Close Comments if more than (0=never):');
InsertAdminPhrase(2, 'display_pdf_link', 'Display PDF Link', 2);
InsertPluginSetting(2, 'article_display_settings', 'Display PDF Link', 'Display a link to convert the article to a PDF document?<br />
  Note: requires HTML2PDF library to exist in "includes/html2pdf" and is only displayed for logged in users in any case.', 'yesno', '1', 5);
InsertPluginSetting(2, 'article_display_settings', 'PDF Page Size', 'Default page size for PDF document?', 'pagesize', 'letter', 50);
//InsertPluginSetting(2, 'article_display_settings', 'Display Tags', 'Display tags for article?', 'yesno', '0', 9);

// Media
$DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."adminphrases WHERE adminpageid = 3 AND varname = 'media_browse_folder' LIMIT 1"); //remove all first
InsertAdminPhrase(0, 'media_browse_folder',     'Browse Folder', 3);
InsertAdminPhrase(0, 'media_filesize',          'File Size:', 3);
InsertAdminPhrase(0, 'media_image_details',     'Image/Media File Details', 3);
InsertAdminPhrase(0, 'media_delete_image',      'Delete Image/Media File', 3);
InsertAdminPhrase(0, 'media_delete_this_image', 'Do you wish to delete this file now?', 3);

// Comments
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'comments', 'ipaddress', "VARCHAR(32) collate utf8_unicode_ci NOT NULL DEFAULT ''");
DeleteAdminPhrase(0, 'comments_clear_search', 4);
InsertAdminPhrase(0, 'comments_clear_filter', 'Clear Filter', 4);
InsertAdminPhrase(0, 'comments_ipaddress', 'IP Address', 4);
InsertAdminPhrase(0, 'comments_ip', 'IP', 4);

// Skins
foreach(array('menu_level0_opening', 'menu_level0_closing',
              'menu_submenu_opening', 'menu_submenu_closing',
              'menu_item_opening', 'menu_item_closing', 'menu_item_link') as $newcolumn)
{
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'skins', $newcolumn, "TEXT collate utf8_unicode_ci NOT NULL");
}
// Fill new skin columns with default values:
$DB->query("UPDATE {skins} SET menu_level0_opening = '<ul class=\"sf-menu\">' WHERE IFNULL(menu_level0_opening,'') = ''");
$DB->query("UPDATE {skins} SET menu_level0_closing = '</ul>' WHERE IFNULL(menu_level0_closing,'') = ''");
$DB->query("UPDATE {skins} SET menu_submenu_opening = '<ul>' WHERE IFNULL(menu_submenu_opening,'') = ''");
$DB->query("UPDATE {skins} SET menu_submenu_closing = '</ul>' WHERE IFNULL(menu_submenu_closing,'') = ''");
$DB->query("UPDATE {skins} SET menu_item_opening = '<li>' WHERE IFNULL(menu_item_opening,'') = ''");
$DB->query("UPDATE {skins} SET menu_item_closing = '</li>' WHERE IFNULL(menu_item_closing,'') = ''");
$DB->query("UPDATE {skins} SET menu_item_link = '<a href=\"[LINK]\" [TARGET]>[NAME]</a>' WHERE IFNULL(menu_item_link,'') = ''");
InsertMainSetting('frontpage_menu_javascript', 'settings_general_settings', 'Default Frontpage Menu Javascript',
'Default Javascript code for the frontpage menu to be created.<br />This needs to be adapted whenever a skin is used
with non-standard menu HTML tags or the minimum width of menu items is to be changed etc.', 'textarea',
'<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function() {
  var sd_menu = jQuery("ul.sf-menu");
  if( jQuery(sd_menu).length ) {
    jQuery("ul.sf-menu").supersubs({
      autoArrows:  true,
      dropShadows: true,
      delay:       200,
      minWidth:    10,
      maxWidth:    27,
      extraWidth:  1,
      speed:       "fast"
    }).superfish({delay: 200});
    jQuery(".sf-menu ul").bgiframe();
  }
});
//]]>
</script>', 300);

$DB->query("UPDATE ".PRGM_TABLE_PREFIX."adminphrases SET defaultphrase = 'XML file is not writable, please temporarily change it\'s permissions to 0666 (use CHMOD) in FTP client!' WHERE varname = 'skins_xml_file_not_writable'");
InsertAdminPhrase(0, 'skins_menu_settings', 'Menu Settings', 6);
InsertAdminPhrase(0, 'skins_menu_settings_hint', 'Note: These settings only apply for '.htmlspecialchars(PRGM_NAME, ENT_QUOTES).'-compatible
  skins for the frontpage drop-down menu system.<br />
  If the default menu system is to be used, &lt;ul&gt; / &lt;/ul&gt; HTML tags MUST be used.<br />
  Important: opening and closing tags must be the same element type!', 6);
InsertAdminPhrase(0, 'skins_menu_level0_opening', 'Top-Level Menu Opening HTML Tag', 6);
InsertAdminPhrase(0, 'skins_menu_level0_opening_descr', 'HTML opening tag for top-level menu container (default: <strong>&lt;ul class="sf-menu"&gt;</strong> )', 6);
InsertAdminPhrase(0, 'skins_menu_level0_closing', 'Top-Level Menu Closing HTML Tag', 6);
InsertAdminPhrase(0, 'skins_menu_level0_closing_descr', 'HTML closing tag for top-level menu container (default: <strong>&lt;/ul&gt;</strong> )', 6);
InsertAdminPhrase(0, 'skins_menu_submenu_opening', 'Sub-Menu Opening HTML Tag', 6);
InsertAdminPhrase(0, 'skins_menu_submenu_opening_descr', 'HTML opening tag for sub-menu lists (default: <strong>&lt;ul&gt;</strong> )', 6);
InsertAdminPhrase(0, 'skins_menu_submenu_closing', 'Sub-Menu Closing HTML Tag', 6);
InsertAdminPhrase(0, 'skins_menu_submenu_closing_descr', 'HTML closing tag for sub-menu lists (default: <strong>&lt;/ul&gt;</strong> )', 6);
InsertAdminPhrase(0, 'skins_menu_item_opening', 'Menu Item Opening HTML Tag', 6);
InsertAdminPhrase(0, 'skins_menu_item_opening_descr', 'HTML opening tag for a menu item (default: <strong>&lt;li&gt;</strong> )', 6);
InsertAdminPhrase(0, 'skins_menu_item_closing', 'Menu Item Closing HTML Tag', 6);
InsertAdminPhrase(0, 'skins_menu_item_closing_descr', 'HTML closing tag for a menu item (default: <strong>&lt;/li&gt;</strong> )', 6);
InsertAdminPhrase(0, 'skins_menu_item_link', 'Menu Item Link', 6);
InsertAdminPhrase(0, 'skins_menu_item_link_descr', 'Single menu item entry link with placeholders (default: <strong>&lt;a href="[LINK]" [TARGET]&gt;[NAME]&lt;/a&gt;</strong> )', 6);

// Main Settings
$DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."mainsettings WHERE varname = 'settings_seo_ascii_only'");
DeleteAdminPhrase(0, 'titles_ascii_only');
DeleteAdminPhrase(0, 'titles_ascii_only_descr');
InsertMainSetting('comments_guest_auto_approve', 'comment_options', 'Auto-Approve Guest Comments', 'Automatically approve every comment made by a guest?<br />To avoid spam comments this option is off by default in case the Guests usergroup has submit permission for comments.', 'yesno', '0', 10);
InsertMainSetting('settings_seo_default_separator', 'settings_seo_settings', 'Titles Word Separator', 'Separator character between words within title, replacing blanks or other URL-invalid characters.<br />Default: "-" (a single hyphen/dash)', 'text', '-', 20);
InsertMainSetting('settings_seo_lowercase', 'settings_seo_settings', 'Titles All Lowercase', 'Convert all SEO titles to lowercase-only characters (default: Yes)?', 'yesno', '1', 30);
InsertMainSetting('settings_time_format', 'settings_date_time_settings', 'Time Format', 'Display format for time only, which may be used by plugins (default: "h:iA").<br />
 <strong>Examples:</strong> "<strong>h:iA</strong>" for 12-hour format and AM/PM suffix; "<strong>H:i</strong>" for 24-hour format without suffix', 'text', 'h:iA', 30);

// Users / Usergroups
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'maintain_customplugins', "TINYINT(1) NOT NULL DEFAULT 0");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'users', 'use_salt', 'INT(1) NOT NULL DEFAULT 0 AFTER `salt`');
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."users SET salt = '' WHERE use_salt = 0 AND salt != '' AND password != ''");
InsertAdminPhrase(0, 'users_maintain_custom_plugins', 'Maintain Custom Plugins', 5);
InsertAdminPhrase(0, 'users_maintain_custom_plugins_descr', 'Allow users to maintain Custom Plugins (add,edit,delete) if access
  to the Plugins page is granted (default: No)?<br /><br /><strong>Notes:</strong><br />
  * "Yes" does not include access to Pages for placing Custom Plugins onto pages.<br />
  * This option is ignored for users that already have full admin access.', 5);
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."adminphrases SET defaultphrase = 'Please enter an email message text.' WHERE varname = 'users_email_message_missing'");

// Admin Phrases for Settings|Database page
InsertAdminPhrase(0, 'configuration_error', 'Configuration Error', 7);
InsertAdminPhrase(0, 'folder_not_found', 'Backup folder does not exist', 7);
InsertAdminPhrase(0, 'folder_not_writable', 'Backup folder is not writable', 7);
InsertAdminPhrase(0, 'folder_chmod', 'Use FTP client (or CHMOD) to set permissions to 0777.', 7);
InsertAdminPhrase(0, 'table_export_failed', 'Failed to export table', 7);
InsertAdminPhrase(0, 'exported_table', 'Exported table', 7);
InsertAdminPhrase(0, 'exported_rows', 'Rows:', 7);
InsertAdminPhrase(0, 'backup_saved_to', 'Database backup saved to', 7);
InsertAdminPhrase(0, 'file_write_error', 'ERROR: Failed to write to file', 7);
InsertAdminPhrase(0, 'file_open_error', 'ERROR: Failed to open backup file:', 7);
InsertAdminPhrase(0, 'processed_total', 'Processed statements in total:', 7);
InsertAdminPhrase(0, 'rows_added_total', 'Rows added in total:', 7);
InsertAdminPhrase(0, 'backup_remove_error', 'FAILED to remove backup file:', 7);
InsertAdminPhrase(0, 'backup_removed', 'Backup file removed:', 7);
InsertAdminPhrase(0, 'table_operation', 'Operation on table', 7);
InsertAdminPhrase(0, 'table_operation_error', 'ERROR: Invalid table operation!', 7);
InsertAdminPhrase(0, 'database_backups_title', 'Database Backups', 7);
InsertAdminPhrase(0, 'database_tables_title', 'Database Tables', 7);
InsertAdminPhrase(0, 'backup_folder', 'Backup Folder:', 7);
InsertAdminPhrase(0, 'column_table_name', 'Table Name', 7);
InsertAdminPhrase(0, 'column_rows', 'Rows', 7);
InsertAdminPhrase(0, 'column_data_length', 'Data Length', 7);
InsertAdminPhrase(0, 'column_index_length', 'Index Length', 7);
InsertAdminPhrase(0, 'column_overhead', 'Overhead', 7);
InsertAdminPhrase(0, 'column_operations', 'Operations', 7);
InsertAdminPhrase(0, 'operation_check', 'Check', 7);
InsertAdminPhrase(0, 'operation_optimize', 'Optimize', 7);
InsertAdminPhrase(0, 'operation_repair', 'Repair', 7);
InsertAdminPhrase(0, 'operation_backup', 'Backup', 7);
InsertAdminPhrase(0, 'check_table_results', 'Check Table Results', 7);
InsertAdminPhrase(0, 'optimize_table_results', 'Optimize Table Results', 7);
InsertAdminPhrase(0, 'repair_table_results', 'Repair Table Results', 7);
InsertAdminPhrase(0, 'backup_table_results', 'Backup Table Results', 7);
InsertAdminPhrase(0, 'file_name', 'File Name', 7);
InsertAdminPhrase(0, 'file_size', 'File Size', 7);
InsertAdminPhrase(0, 'file_date', 'File Date', 7);
InsertAdminPhrase(0, 'file_delete', 'Delete', 7);
InsertAdminPhrase(0, 'file_download', 'Download', 7);
InsertAdminPhrase(0, 'file_restore', 'Restore', 7);
InsertAdminPhrase(0, 'file_info_error', 'File not readable (permissions?)', 7);
InsertAdminPhrase(0, 'file_restore_prompt', 'Are you sure you wish to restore this backup file? All data entered after backup date will be deleted!', 7, true);
InsertAdminPhrase(0, 'file_delete_prompt', 'Are you sure you wish to delete this backup file?', 7);
InsertAdminPhrase(0, 'database_instructions_title', 'About Database Operations', 7);
InsertAdminPhrase(0, 'database_instructions',
  'You can use this tool to backup and maintain your '.PRGM_NAME.' database. The available maintenance commands are:<br />
  <strong>Check</strong> - Checks selected table(s) for errors.<br />
  <strong>Optimize</strong> - Recovers wasted space (as reported in the "Overhead" column).<br />
  <strong>Repair</strong> - Let\'s MySQL attempt to recover errors in selected table(s).<br /><br />
  You can also use this tool to backup your database tables to compressed SQL files
  which can be used to recover the database in the event of data loss.', 7);

// Fix some phrases
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."adminphrases SET defaultphrase = 'If \"Full Admin Access\" is not granted (set to \"No\"), users of this group may still have access to individual admin pages (use CTRL+click to select/deselect entries).
This may be useful when setting up users to be able to only add and maintain content (like Pages, Plugins), but not change any system settings.' WHERE adminpageid = 5 AND varname = 'users_pages_admin_access_desc'");
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."adminphrases SET defaultphrase = 'Restrict access for users to view/edit only Articles they created (default: No)?<br />
This option is used if this usergroup has access to the Articles page and the Articles plugin so that users only have access to their own articles.' WHERE adminpageid = 5 AND varname = 'users_author_mode_desc'");
