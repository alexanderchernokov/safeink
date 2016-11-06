<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

$DB->query("ALTER TABLE ".PRGM_TABLE_PREFIX."plugins CHANGE COLUMN `authorname` `authorname` VARCHAR(254) collate utf8_unicode_ci NOT NULL DEFAULT ''");
$DB->query("ALTER TABLE ".PRGM_TABLE_PREFIX."plugins CHANGE COLUMN `displayname` `displayname` VARCHAR(254) collate utf8_unicode_ci NOT NULL DEFAULT ''");

// Fix some plugin's settings
$DB->query("UPDATE {plugins} SET authorlink = 'http://www.subdreamer.com', authorname = 'subdreamer_web'
           WHERE NOT (pluginid IN (1,2,3,6,10,11,12,17)) AND ((authorname = 'Origin 2501') OR
           (authorlink = 'http://www.origin2501.com/') OR (authorlink = '1'))");
$DB->query("UPDATE {plugins} SET authorname = 'subdreamer_web' WHERE pluginid in (4,7,16,31)");
#$DB->query("UPDATE {pagesort} SET pluginid = '1' WHERE pluginid in ('4','7','16','31')");

// Admin - Settings - BBCode
InsertAdminPhrase(0, 'settings_bbcode', 'BBCode Settings', 7);
InsertMainSetting('allow_bbcode', 'settings_bbcode', 'Allow BBCode', 'Allow BBCode in general within e.g. plugins (like Forum) or comments (default: Yes)?', 'yesno', '1', 10, true);
InsertMainSetting('allow_bbcode_embed', 'settings_bbcode', 'Allow Embedded Media', 'Allow automatic embedding of remote media links for BBCode tag [embed] (default: No)?', 'yesno', '0', 20, true);
InsertMainSetting('enable_gravatars', 'settings_general_settings', 'Enable Gravatars', 'If enabled, gravatar.com is automatically used for displaying the user avatars (e.g. for Comments; default: Yes)? If set to No, the DEFAULT avatar image will be displayed.', 'yesno', '1', 10, true);
$DB->query("UPDATE {mainsettings} SET groupname = 'comment_options' WHERE groupname = 'Comment Options'");
$DB->query("UPDATE {mainsettings} SET input = 'timezone' WHERE varname = 'timezoneoffset'");
$DB->query("DELETE FROM {mainsettings} WHERE varname = 'jquery_filename'");

// Admin - Settings - Cache
InsertAdminPhrase(0, 'settings_cache', 'Cache Settings', 7);
InsertAdminPhrase(0, 'message_no_cache_folder', 'Cache folder does not exist, please create it within a FTP client and set it\'s permissions to 0777.', 7);
InsertAdminPhrase(0, 'message_cache_not_writable', 'Cache folder is not writable, please set it\'s permissions to 0777.', 7);
InsertAdminPhrase(0, 'purge_cache', 'Purge Cache', 7);
InsertAdminPhrase(0, 'purge_cache_hint', 'Purging the cache may be usefull whenever
    cached data needs to be completely refreshed. This operation can be done
    at any time. Afterwards the first visits to the site may take a blink longer
    to rebuild cache files, but that is normal behavior.', 7);
InsertMainSetting('enable_caching', 'settings_cache', 'Enable Caching', 'Enable caching for faster pageloads?', 'yesno', '1', 10, true);

// Give designs a displayname
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'designs', 'design_name', 'VARCHAR(64)', "NOT NULL DEFAULT ''");

// Re-introduce usergroup permissions for comments
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'plugincommentids', 'TEXT', 'NOT NULL');
// Introduce a "Banned" flag for user of this group not being able to login
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'banned', 'TINYINT(1)', 'NOT NULL DEFAULT 0');
$DB->query("UPDATE {usergroups} SET plugincommentids = '2,13,17' WHERE name <> 'Banned'");
$DB->query("UPDATE {usergroups} SET plugincommentids = '' WHERE name = 'Banned'");

// Reset Article plugin settings for admin "Usergroups" display
$DB->query("UPDATE {plugins} SET settings = 59 where pluginid = 2");

// Update description for Comments in Admin
$DB->query("UPDATE {adminphrases} SET defaultphrase = 'Always allow users of this usergroup to post comments?<br />Only if set to \"No\", the \"Allow Comments\" settings below are used.'".
           " WHERE adminpageid = 5 AND pluginid = 0 AND varname = 'users_comment_access_desc'");

// System Log core table (ported from 2.6):
$oldignore = $DB->ignore_error;
$DB->ignore_error = true;
$DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."syslog (
  `wid`       INT(5)        NOT NULL AUTO_INCREMENT,
  `username`  VARCHAR(128)  collate utf8_unicode_ci NOT NULL DEFAULT '',
  `type`      VARCHAR(32)   collate utf8_unicode_ci NOT NULL DEFAULT '',
  `message`   LONGTEXT      collate utf8_unicode_ci NOT NULL,
  `severity`  TINYINT(3)    unsigned NOT NULL DEFAULT 0,
  `location`  VARCHAR(255)  collate utf8_unicode_ci NOT NULL DEFAULT '',
  `referer`   VARCHAR(255)  collate utf8_unicode_ci NOT NULL DEFAULT '',
  `hostname`  VARCHAR(255)  collate utf8_unicode_ci NOT NULL DEFAULT '',
  `timestamp` INT(11)       NOT NULL DEFAULT 0,
  PRIMARY KEY (`wid`))
  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
$DB->ignore_error = $oldignore;

// Ratings core table:
$DB->query("CREATE TABLE IF NOT EXISTS {ratings} (
  `rid`       INT(10)       NOT NULL AUTO_INCREMENT,
  `rating_id` VARCHAR(64)   NOT NULL DEFAULT '',
  `user_id`   int(11)       NOT NULL DEFAULT 0,
  `user_ip`   VARCHAR(32)   NOT NULL DEFAULT '',
  `rating`    FLOAT(5,2)    NOT NULL DEFAULT 0,
  PRIMARY KEY (`rid`))
  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
if(!$DB->columnindex_exists('{ratings}', 'rating_id', 'rating_id'))
$DB->query("CREATE INDEX `rating_id` ON {ratings} (rating_id)");
if(!$DB->columnindex_exists('{ratings}', 'rating_id', 'rating_user_id'))
$DB->query("CREATE INDEX `rating_user_id` ON {ratings} (rating_id, user_id)");
// Add pluginid column
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'ratings', 'pluginid', 'int', 'NOT NULL DEFAULT 0');
// Add timestamp column
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'ratings', 'rating_time', 'int', 'NOT NULL DEFAULT 0');

// Phrases for new "Settings" sub-menu items:
InsertAdminPhrase(0, 'menu_settings_database',    'Database', 0);
InsertAdminPhrase(0, 'menu_settings_syslog',      'System Log', 0);
InsertAdminPhrase(0, 'menu_settings_info_mysql',  'MySQL Info', 0);
InsertAdminPhrase(0, 'menu_settings_info_php',    'PHP Info', 0);
InsertAdminPhrase(0, 'confirm_plugin_delete',     'Really DELETE this plugin?', 0);
InsertAdminPhrase(0, 'pages_error_invalid_data',  'Update cancelled! There was invalid data entered!', 0);
InsertAdminPhrase(0, 'common_allow_comment',      'Allow Comments', 0);

// Skin related phrases
InsertAdminPhrase(0, 'skins_skin_import_error',   'Skin not imported, please check that skin file is readable.', 6);
InsertAdminPhrase(0, 'skins_skin_install_error',  'Skin not installed, please check that skin file is readable.', 6);
InsertAdminPhrase(0, 'skins_skin_layout_name',    ' - Name:', 6);
InsertAdminPhrase(0, 'skins_layout_not_deleted',  'Skin layout could not be deleted!', 6);
InsertAdminPhrase(0, 'skins_layout_not_inserted', 'Skin layout could not be inserted!', 6);
InsertAdminPhrase(0, 'pages_error_invalid_design','Error: an invalid design or skin was called', 1);

// For Admin page "Users"
// Users "email" length is too short with 64 characters, RFC allows 320!
$DB->query("ALTER TABLE {users} CHANGE `email` `email` TEXT COLLATE utf8_unicode_ci");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'users',   'timezoneoffset', 'varchar(4)', "NOT NULL DEFAULT '0'");
InsertAdminPhrase(0, 'users_timezone',            'Timezone', 5);
InsertAdminPhrase(0, 'users_timezone_desc',       'The timezone the user resides in, which is most likely different from the timezone in the Main Settings.', 5);
InsertAdminPhrase(0, 'users_banned',              'Banned', 5);
InsertAdminPhrase(0, 'users_banned_desc',         'User is banned and is no longer allowed to login anymore?', 5);
InsertAdminPhrase(0, 'users_allow_comments',      'Allow Comments', 5);
InsertAdminPhrase(0, 'users_group_banned_desc',   'Users of this group are banned and cannot login anymore?', 5);
InsertAdminPhrase(0, 'users_search_results',      'Search Results', 5);
InsertAdminPhrase(0, 'users_link_send_email_user','Send Email to User', 5);
InsertAdminPhrase(0, 'users_link_edit_usergroup', 'Edit Usergroup', 5);
InsertAdminPhrase(0, 'users_letter',              'Letter', 5);
InsertAdminPhrase(0, 'users_filter_activated',    'Activated', 5);
InsertAdminPhrase(0, 'users_filter_allusers',     'All Users', 5);
InsertAdminPhrase(0, 'users_filter_banned',       'Banned', 5);
InsertAdminPhrase(0, 'users_filter_filterby',     'Filter by:', 5);
InsertAdminPhrase(0, 'users_filter_others',       'Others', 5);
InsertAdminPhrase(0, 'users_filter_users',        'Users:', 5);
InsertAdminPhrase(0, 'users_filter_validating',   'Validating', 5);
DeleteAdminPhrase(0, 'users_timezone_deesc'); // fix typo

// For Admin page "Languages"
InsertAdminPhrase(0, 'message_not_utf8_charset',    'Warning: You will not be able to export your language, switch Chararacter Set to utf-8 to enable it (under Main Settings).', 7);

InsertAdminPhrase(0, 'message_no_access_install',   'You do not have permissions to install plugins!', 2);
InsertAdminPhrase(0, 'message_no_access_uninstall', 'You do not have permissions to uninstall this plugin!', 2);
InsertAdminPhrase(0, 'message_no_access_upgrade',   'You do not have permissions to upgrade plugins!', 2);
InsertAdminPhrase(0, 'message_plugin_not_found',    'The requested plugin was not found!', 2);

InsertAdminPhrase(0, 'common_messages',           'Messages', 0);
InsertAdminPhrase(0, 'common_website_name',       'Website Name', 0);
InsertAdminPhrase(0, 'common_website_url',        'Website URL', 0);
InsertAdminPhrase(0, 'common_category',           'Category', 0);
InsertAdminPhrase(0, 'common_date_posted',        'Date Posted', 0);

// ################################ Forum Plugin ##############################
if($forum_pluginid = $DB->query_first("SELECT pluginid FROM {plugins} WHERE name = 'Forum' LIMIT 1"))
{
  $forum_pluginid = $forum_pluginid[0];
}

if($forum_pluginid)
{
  // Reset Forum plugin settings for admin "Usergroups" display
  // (comments permission not usable for Forum)
  $DB->query("UPDATE {plugins} SET settings = 55, version = '3.1.3' where pluginid = %d",$forum_pluginid);

  InsertAdminPhrase($forum_pluginid, 'menu_forums',           'Forums', 2);
  InsertAdminPhrase($forum_pluginid, 'menu_create_new_forum', 'Create New Forum', 2);
  InsertAdminPhrase($forum_pluginid, 'menu_forum_settings',   'Forum Settings', 2);
  InsertAdminPhrase($forum_pluginid, 'menu_forum_statistics', 'Statistics', 2);
  InsertAdminPhrase($forum_pluginid, 'message_no_forums',     'Click "Create New Forum" to get started.', 2);
  InsertAdminPhrase($forum_pluginid, 'forum_statistics',      'Forum Statistics', 2);

  // Add missing frontpage phrases
  InsertPhrase($forum_pluginid, 'bbcode_allowed', 'BBCode allowed');
  InsertPhrase($forum_pluginid, 'breadcrumb_forum', 'Forum');
  InsertPhrase($forum_pluginid, 'column_forum', 'Forum');
  InsertPhrase($forum_pluginid, 'column_topic', 'Topic');
  InsertPhrase($forum_pluginid, 'column_topics', 'Topics');
  InsertPhrase($forum_pluginid, 'column_view_count', 'Views');
  InsertPhrase($forum_pluginid, 'column_last_updated', 'Last updated');
  InsertPhrase($forum_pluginid, 'column_posts', 'Posts');
  InsertPhrase($forum_pluginid, 'confirm_delete_topic', 'Yes, I confirm topic(s) deletion ');
  InsertPhrase($forum_pluginid, 'create_new_topic', 'Create New Topic');
  InsertPhrase($forum_pluginid, 'create_topic', 'Create Topic');
  InsertPhrase($forum_pluginid, 'edit', 'Edit');
  InsertPhrase($forum_pluginid, 'edit_post', 'Edit Post');
  InsertPhrase($forum_pluginid, 'delete', 'Delete');
  InsertPhrase($forum_pluginid, 'delete_posts', 'Delete Posts');
  InsertPhrase($forum_pluginid, 'do_with_selected_posts', 'Do with selected posts:');
  InsertPhrase($forum_pluginid, 'err_empty_post', 'Please enter a message text.');
  InsertPhrase($forum_pluginid, 'err_forum_not_found', 'Forum not found.');
  InsertPhrase($forum_pluginid, 'err_message_not_found', 'Message not found.');
  InsertPhrase($forum_pluginid, 'err_invalid_forum_id', 'Invalid Forum ID.');
  InsertPhrase($forum_pluginid, 'err_invalid_topic_id', 'Invalid Topic ID.');
  InsertPhrase($forum_pluginid, 'err_no_admin_permissions', 'You do not have permissions to administer topics.');
  InsertPhrase($forum_pluginid, 'err_no_edit_topic_access', 'You do not have permissions to edit this topic.');
  InsertPhrase($forum_pluginid, 'err_no_edit_post_access', 'You do not have permissions to edit this post.');
  InsertPhrase($forum_pluginid, 'err_no_update_post_access', 'You do not have permissions to update this post.');
  InsertPhrase($forum_pluginid, 'err_no_post_access', 'You do not have permissions to post in this forum.');
  InsertPhrase($forum_pluginid, 'err_no_messages_selected', 'There were no messages selected.');
  InsertPhrase($forum_pluginid, 'err_no_topics_selected', 'There were no topics selected.');
  InsertPhrase($forum_pluginid, 'err_no_options_access', 'You do not have permissions for options in this forum.');
  InsertPhrase($forum_pluginid, 'err_member_access_only', 'Only members can post in this forum.');
  InsertPhrase($forum_pluginid, 'err_topic_not_logged_in', 'You must be logged in to post a topic.');
  InsertPhrase($forum_pluginid, 'err_topic_not_found', 'Topic not found.');
  InsertPhrase($forum_pluginid, 'err_topic_no_title', 'Please enter a title for your topic.');
  InsertPhrase($forum_pluginid, 'err_topic_no_post', 'Please enter a post.');
  InsertPhrase($forum_pluginid, 'err_topic_no_repeat', 'Repeat topic not posted.');
  InsertPhrase($forum_pluginid, 'forum_offline', 'Forum Offline');
  InsertPhrase($forum_pluginid, 'message_invalid_operation', 'Invalid Forum operation requested.');
  InsertPhrase($forum_pluginid, 'message_no_operation', 'No Forum action was performed.');
  InsertPhrase($forum_pluginid, 'message_no_data_no_changes', 'The action returned no data, no changes were applied.');
  InsertPhrase($forum_pluginid, 'message_forum_deleted', 'Forum deleted.');
  InsertPhrase($forum_pluginid, 'message_forums_deleted', 'Forums deleted.');
  InsertPhrase($forum_pluginid, 'message_post_posted', 'Message posted.');
  InsertPhrase($forum_pluginid, 'message_post_updated', 'Post updated.');
  InsertPhrase($forum_pluginid, 'message_post_deleted', 'Post deleted.');
  InsertPhrase($forum_pluginid, 'message_posts_deleted', 'Posts deleted.');
  InsertPhrase($forum_pluginid, 'message_posts_moderated', 'Posts are now moderated.');
  InsertPhrase($forum_pluginid, 'message_posts_unmoderated', 'Posts are now unmoderated.');
  InsertPhrase($forum_pluginid, 'message_topic_deletion_cancelled', 'Topic deletion cancelled.');
  InsertPhrase($forum_pluginid, 'message_topic_created', 'Topic created.');
  InsertPhrase($forum_pluginid, 'message_topic_locked', 'Topic Locked.');
  InsertPhrase($forum_pluginid, 'message_topics_locked', 'Topics Locked.');
  InsertPhrase($forum_pluginid, 'message_topic_unlocked', 'Topic Unlocked.');
  InsertPhrase($forum_pluginid, 'message_topics_unlocked', 'Topics Unlocked.');
  InsertPhrase($forum_pluginid, 'message_topic_deleted', 'Topic Deleted.');
  InsertPhrase($forum_pluginid, 'message_topics_deleted', 'Topics Deleted.');
  InsertPhrase($forum_pluginid, 'message_topic_renamed', 'Topic Renamed.');
  InsertPhrase($forum_pluginid, 'message_topic_moderated', 'Topic is now moderated.');
  InsertPhrase($forum_pluginid, 'message_topic_unmoderated', 'Topic is now unmoderated.');
  InsertPhrase($forum_pluginid, 'message_topics_moderated', 'Topics are now moderated.');
  InsertPhrase($forum_pluginid, 'message_topics_unmoderated', 'Topic are now unmoderated.');
  InsertPhrase($forum_pluginid, 'message_signup_login', '<a href="[REGISTER_PATH]" style="text-decoration: underline;">'.
                                                  'Sign up</a> for a new account or <a href="[LOGIN_PATH]" style="text-decoration: underline;">login here</a>.');
  InsertPhrase($forum_pluginid, 'new_post', 'New Post');
  InsertPhrase($forum_pluginid, 'new_topic', 'New Topic');
  InsertPhrase($forum_pluginid, 'posts', 'Posts');
  InsertPhrase($forum_pluginid, 'posted_by', 'by');
  InsertPhrase($forum_pluginid, 'posted_ago_before', 'Posted');
  InsertPhrase($forum_pluginid, 'posted_ago_after', 'ago');
  InsertPhrase($forum_pluginid, 'post_reply', 'Post Reply');
  InsertPhrase($forum_pluginid, 'proceed', 'Proceed');
  InsertPhrase($forum_pluginid, 'quote', 'Quote');
  InsertPhrase($forum_pluginid, 'quick_reply', 'Quick Reply');
  InsertPhrase($forum_pluginid, 'rename', 'Rename');
  InsertPhrase($forum_pluginid, 'rename_topic', 'Rename Topic');
  InsertPhrase($forum_pluginid, 'reply', 'Reply');
  InsertPhrase($forum_pluginid, 'search', 'Search');
  InsertPhrase($forum_pluginid, 'search_no_results', 'Search returned no results.');
  InsertPhrase($forum_pluginid, 'search_for', 'Search for:');
  InsertPhrase($forum_pluginid, 'search_results_for', 'Search Results for:');
  InsertPhrase($forum_pluginid, 'select', 'Select');
  InsertPhrase($forum_pluginid, 'select_all', 'all');
  InsertPhrase($forum_pluginid, 'select_none', 'none');
  InsertPhrase($forum_pluginid, 'select_all_topics', 'Select all Topics');
  InsertPhrase($forum_pluginid, 'select_all_posts', 'Select all Posts');
  InsertPhrase($forum_pluginid, 'settings_updated', 'Settings Updated');
  InsertPhrase($forum_pluginid, 'topic_locked', 'Topic Locked');
  InsertPhrase($forum_pluginid, 'topic_options', 'Topic Options:');
  InsertPhrase($forum_pluginid, 'topic_options_lock_topic', 'Lock Topic');
  InsertPhrase($forum_pluginid, 'topic_options_lock_topics', 'Lock Topics');
  InsertPhrase($forum_pluginid, 'topic_options_unlock_topic', 'Unlock Topic');
  InsertPhrase($forum_pluginid, 'topic_options_unlock_topics', 'Unlock Topics');
  InsertPhrase($forum_pluginid, 'topic_options_delete_topic', 'Delete Topic');
  InsertPhrase($forum_pluginid, 'topic_options_delete_topics', 'Delete Topics');
  InsertPhrase($forum_pluginid, 'topic_options_rename_topic', 'Rename Topic');
  InsertPhrase($forum_pluginid, 'topic_options_rename_topics', 'Rename Topics');
  InsertPhrase($forum_pluginid, 'topic_options_moderate_posts', 'Moderate Posts');
  InsertPhrase($forum_pluginid, 'topic_options_unmoderate_posts', 'Unmoderate Posts');
  InsertPhrase($forum_pluginid, 'topic_options_moderate_topic', 'Moderate Topic');
  InsertPhrase($forum_pluginid, 'topic_options_moderate_topics', 'Moderate Topics');
  InsertPhrase($forum_pluginid, 'topic_options_unmoderate_topic', 'Unmoderate Topic');
  InsertPhrase($forum_pluginid, 'topic_options_unmoderate_topics', 'Unmoderate Topics');
  InsertPhrase($forum_pluginid, 'topic_options_moderate_user', 'Moderate User');
  InsertPhrase($forum_pluginid, 'topic_options_unmoderate_user', 'Unmoderate User');
  InsertPhrase($forum_pluginid, 'topic_title', 'Topic Title:');
  InsertPhrase($forum_pluginid, 'topics', 'Topics');
  InsertPhrase($forum_pluginid, 'topic_singular', 'Topic');
  InsertPhrase($forum_pluginid, 'update_post', 'Update Post');
  InsertPhrase($forum_pluginid, 'new_posts', 'Most Recent Posts');

  // Add new plugin settings
  InsertAdminPhrase($forum_pluginid,   'forum_display_settings', 'Display Settings');
  InsertPluginSetting($forum_pluginid, 'forum_display_settings', 'Date Display', 'Display the full date after x days instead of "x days ago":', 'text', '30', 10);
  InsertPluginSetting($forum_pluginid, 'forum_display_settings', 'Date Format', 'Date display format (empty: use Main Settings "Date Format" value):', 'text', '', 15);
  InsertPluginSetting($forum_pluginid, 'forum_display_settings', 'Display Avatar', 'Display user avatars (default: Yes)?', 'yesno', '1', 18);
  InsertPluginSetting($forum_pluginid, 'forum_display_settings', 'Display View Count', 'Display the view count for each topic in forum list?', 'yesno', '1', 20);
  InsertPluginSetting($forum_pluginid, 'forum_display_settings', 'Enable BBCode', 'Enable conversion of BBCode tags to HTML in user posts (default: Yes)?', 'yesno', '1', 22);
  InsertPluginSetting($forum_pluginid, 'forum_display_settings', 'Enable Embedding', 'Enable embedding of remote media links (default: No)?', 'yesno', '0', 25);
  InsertPluginSetting($forum_pluginid, 'forum_display_settings', 'Embedding Max Height', 'Maximum height (in pixels) of embedded links (default: 500)?', 'text', '500', 26);
  InsertPluginSetting($forum_pluginid, 'forum_display_settings', 'Embedding Max Width', 'Maximum width (in pixels) of embedded links (default: 600)?', 'text', '600', 27);
  InsertPluginSetting($forum_pluginid, 'forum_display_settings', 'Enable Search', 'Display search bar above forums (default: Yes)?', 'yesno', '1', 30);
  InsertPluginSetting($forum_pluginid, 'forum_display_settings', 'Forum Page Size', 'Number of topics displayed per page when viewing a forum (1-100; default: 15):', 'text', '15', 35);
  InsertPluginSetting($forum_pluginid, 'forum_display_settings', 'Topic Page Size', 'Number of posts displayed per page when viewing a topic (1-100; default: 10):', 'text', '10', 40);

  // Forum: all elements get new column "moderated" to hide/show indiviual entries (for admin)
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p_forum_forums', 'access_post', 'text', 'collate utf8_unicode_ci');
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p_forum_forums', 'access_view', 'text', 'collate utf8_unicode_ci');
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p_forum_forums', 'moderated',   'TINYINT(1)', 'NOT NULL DEFAULT 0');
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p_forum_posts',  'moderated',   'TINYINT(1)', 'NOT NULL DEFAULT 0');
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p_forum_topics', 'moderated',   'TINYINT(1)', 'NOT NULL DEFAULT 0');
}

// Articles plugin: new option to display/enable user ratings
InsertPluginSetting(2, 'article_display_settings', '', 'Display User Ratings', 'yesno', '0', 10);
InsertAdminPhrase(2, 'display_user_ratings_descr', 'Enable/display rating of articles for users?', '');

// Registration plugin: new security options for username/password length
// Note: settings titles will be converted within "InsertPluginSetting",
// e.g. 'Min Username Length' results in 'min_username_length'!
// DO NOT CHANGE ANYTHING BELOW!!!
InsertPluginSetting(12, 'user_registration_settings', 'Min Username Length', 'Enter the minimum username length:', 'text', '5', 2);
InsertPluginSetting(12, 'user_registration_settings', 'Min Password Length', 'Enter the minimum password length:', 'text', '6', 3);

// Phrases for general use in plugins
InsertPhrase(1,  'ago_before',      'Posted');
InsertPhrase(1,  'ago_after',       'ago');
InsertPhrase(1,  'ago_one_second',  'second');
InsertPhrase(1,  'ago_one_minute',  'minute');
InsertPhrase(1,  'ago_one_hour',    'hour');
InsertPhrase(1,  'ago_one_day',     'day');
InsertPhrase(1,  'ago_one_week',    'week');
InsertPhrase(1,  'ago_one_month',   'month');
InsertPhrase(1,  'ago_one_year',    'year');
InsertPhrase(1,  'ago_one_decade',  'decade');

InsertPhrase(1,  'ago_seconds',     'seconds');
InsertPhrase(1,  'ago_minutes',     'minutes');
InsertPhrase(1,  'ago_hours',       'hours');
InsertPhrase(1,  'ago_days',        'days');
InsertPhrase(1,  'ago_weeks',       'weeks');
InsertPhrase(1,  'ago_months',      'months');
InsertPhrase(1,  'ago_years',       'years');
InsertPhrase(1,  'ago_decades',     'decades');

InsertPhrase(1,  'bbcode_code_title', 'Code:');
InsertPhrase(1,  'bbcode_quote_wrote','wrote:');
InsertPhrase(1,  'button_confirm',    'Confirm');
InsertPhrase(1,  'no',                'No');
InsertPhrase(1,  'yes',               'Yes');
InsertPhrase(1,  'timezone',          'Timezone');
InsertPhrase(1,  'comment_rejected',  'Your comment was rejected.'); // for class_comments.php

InsertPhrase(1, 'settings_updated',   'Information submitted');
InsertPhrase(1, 'message_redirect',   'Click here if you are not redirected.');
InsertPhrase(1, 'error_invalid_token','Invalid operation detected, no action performed!');
InsertPhrase(1, 'website_powered_by', 'Website powered by');

// Phrases for ratings system
InsertPhrase(1, 'rating',               'Rating');
InsertPhrase(1, 'rating_login_to_vote', 'Login for rating');
InsertPhrase(1, 'rating_no_votes',      'No Ratings');
InsertPhrase(1, 'rating_vote',          'Rating');
InsertPhrase(1, 'rating_votes',         'Ratings');
InsertPhrase(1, 'rating_rate_this',     'Rate this');
InsertPhrase(1, 'rating_thanks',        '(thanks)');
InsertPhrase(1, 'rating_already_rated', '(already rated)');
InsertPhrase(1, 'rating_poor',          'Poor');
InsertPhrase(1, 'rating_fair',          'Fair');
InsertPhrase(1, 'rating_average',       'Average');
InsertPhrase(1, 'rating_good',          'Good');
InsertPhrase(1, 'rating_excellent',     'Excellent');

InsertAdminPhrase(0, 'display_ratings', 'Display Ratings', 2);

// Phrases for User Registration plugin
InsertPhrase(12, 'username_too_long', 'Please enter a shorter username (max. #d# characters).');
InsertPhrase(12, 'username_too_short', 'Please enter a longer username (min. #d# characters).');
InsertPhrase(12, 'password_too_short', 'Please enter a longer password (min. #d# characters).');
InsertPhrase(12, 'enter_alnum_password', 'Please enter a password that consists only of letters and numbers.');
InsertPhrase(12, 'pwd_different_username', 'Please enter a password different from the username.');

InsertAdminPhrase(0, 'timezone_gmt_m12','(GMT -12:00) Eniwetok, Kwajalein');
InsertAdminPhrase(0, 'timezone_gmt_m11','(GMT -11:00) Midway Island, Samoa');
InsertAdminPhrase(0, 'timezone_gmt_m10','(GMT -10:00) Hawaii');
InsertAdminPhrase(0, 'timezone_gmt_m9', '(GMT -9:00) Alaska');
InsertAdminPhrase(0, 'timezone_gmt_m8', '(GMT -8:00) Pacific Time (US &amp; Canada)');
InsertAdminPhrase(0, 'timezone_gmt_m7', '(GMT -7:00) Mountain Time (US &amp; Canada)');
InsertAdminPhrase(0, 'timezone_gmt_m6', '(GMT -6:00) Central Time (US &amp; Canada), Mexico City');
InsertAdminPhrase(0, 'timezone_gmt_m5', '(GMT -5:00) Eastern Time (US &amp; Canada), Bogota, Lima');
InsertAdminPhrase(0, 'timezone_gmt_m4', '(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz');
InsertAdminPhrase(0, 'timezone_gmt_m35','(GMT -3:30) Newfoundland');
InsertAdminPhrase(0, 'timezone_gmt_m3', '(GMT -3:00) Brazil, Buenos Aires, Georgetown');
InsertAdminPhrase(0, 'timezone_gmt_m2', '(GMT -2:00) Mid-Atlantic');
InsertAdminPhrase(0, 'timezone_gmt_m1', '(GMT -1:00 hour) Azores, Cape Verde Islands');
InsertAdminPhrase(0, 'timezone_gmt_0',  '(GMT) Western Europe Time, London, Lisbon, Casablanca');
InsertAdminPhrase(0, 'timezone_gmt_p1', '(GMT +1:00 hour) Brussels, Copenhagen, Madrid, Paris');
InsertAdminPhrase(0, 'timezone_gmt_p2', '(GMT +2:00) Kaliningrad, South Africa');
InsertAdminPhrase(0, 'timezone_gmt_p3', '(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburg');
InsertAdminPhrase(0, 'timezone_gmt_p35','(GMT +3:30) Tehran');
InsertAdminPhrase(0, 'timezone_gmt_p4', '(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi');
InsertAdminPhrase(0, 'timezone_gmt_p45','(GMT +4:30) Kabul');
InsertAdminPhrase(0, 'timezone_gmt_p5', '(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent');
InsertAdminPhrase(0, 'timezone_gmt_p55','(GMT +5:30) Bombay, Calcutta, Madras, New Delhi');
InsertAdminPhrase(0, 'timezone_gmt_p6', '(GMT +6:00) Almaty, Dhaka, Colombo');
InsertAdminPhrase(0, 'timezone_gmt_p7', '(GMT +7:00) Bangkok, Hanoi, Jakarta');
InsertAdminPhrase(0, 'timezone_gmt_p8', '(GMT +8:00) Beijing, Perth, Singapore, Hong Kong');
InsertAdminPhrase(0, 'timezone_gmt_p9', '(GMT +9:00) Tokyo, Seoul, Osaka, Sapporo, Yakutsk');
InsertAdminPhrase(0, 'timezone_gmt_p95', '(GMT +9:30) Adelaide, Darwin');
InsertAdminPhrase(0, 'timezone_gmt_p10', '(GMT +10:00) Eastern Australia, Guam, Vladivostok');
InsertAdminPhrase(0, 'timezone_gmt_p11', '(GMT +11:00) Magadan, Solomon Islands, New Caledonia');
InsertAdminPhrase(0, 'timezone_gmt_p12', '(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka');

if(!empty($forum_pluginid)) $forum_pluginid = ','.$forum_pluginid;
$DB->query("UPDATE {plugins} SET version = '3.2.0' WHERE pluginid IN (2,3,6,10,11,12,17".$forum_pluginid.")");
$DB->query("UPDATE {adminphrases} SET customphrase = '' WHERE customphrase IS NULL");
