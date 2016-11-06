<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit();

sd_DisplaySetupMessage('<h4>Upgrade 3.3.0 starting...</h4>');

// ################################ Forum Plugin ##############################

if($forum_pluginid = $DB->query_first("SELECT pluginid FROM ".PRGM_TABLE_PREFIX."plugins WHERE name = 'Forum' LIMIT 1"))
{
  $forum_pluginid = $forum_pluginid[0];
}

if($forum_pluginid)
{
  sd_DisplaySetupMessage('<h4>Updating Forum plugin...</h4>');

  $DB->query("UPDATE ".PRGM_TABLE_PREFIX."plugins SET version = '3.3.0' where pluginid = %d",$forum_pluginid);

  // Add frontpage phrases
  InsertPhrase($forum_pluginid, 'joined',                 '<strong>Joined:</strong>');
  InsertPhrase($forum_pluginid, 'img_click_to_enlarge',   'Click image to enlarge');
  InsertPhrase($forum_pluginid, 'title_attachments',      'Attachments:');
  InsertPhrase($forum_pluginid, 'upload_attachments',     'Upload attachment:');
  InsertPhrase($forum_pluginid, 'upload_error',           'Can not upload attachment, folder not writable or other error.');
  InsertPhrase($forum_pluginid, 'err_member_no_download', 'You have no permissions to download attachments.');
  InsertPhrase($forum_pluginid, 'img_click_to_enlarge',   'Click to enlarge image.');
  InsertPhrase($forum_pluginid, 'user_online',            'User is online');
  InsertPhrase($forum_pluginid, 'user_offline',           'User is offline');
  InsertPhrase($forum_pluginid, 'pagination_posts',       ' Posts');
  InsertPhrase($forum_pluginid, 'search_forums',          'Search Forums');
  InsertPhrase($forum_pluginid, 'search_options',         'Search Options');
  InsertPhrase($forum_pluginid, 'search_topics',          'Search Topics');
  InsertPhrase($forum_pluginid, 'search_posts',           'Search Posts');
  InsertPhrase($forum_pluginid, 'search_usernames',       'Search Users');
  InsertPhrase($forum_pluginid, 'todays_posts',           'Today\'s Posts');
  InsertPhrase($forum_pluginid, 'post_too_short',         'Post is too short!');

  // Add plugin settings
  InsertPluginSetting($forum_pluginid, 'forum_display_settings', 'Max Image Width', 'Automatically resize images that are wider than x Pixels (default: 500; 0 = disable):', 'text', '500', 30);
  InsertPluginSetting($forum_pluginid, 'forum_display_settings', 'Valid Attachment Types', 'Allowed file extensions for post attachments (e.g. `zip,txt`; empty = disabled):', 'text', 'zip', 40);
  InsertPluginSetting($forum_pluginid, 'forum_display_settings', 'Attachments Upload Path', 'Folder in which attachments will be uploaded to (CHMOD folder to 777; default: `attachments`; empty = disabled):', 'text', 'attachments', 50);
  InsertPluginSetting($forum_pluginid, 'forum_display_settings', 'Display Guest Header', 'Display a message to guests to login or signup (translatable in Languages):', 'yesno', '1', 60);
  InsertPluginSetting($forum_pluginid, 'forum_display_usergroup', 'Display Usergroup', 'Display user\'s usergroup name in topic view (default: No):', 'yesno', '0', 70);

  $DB->query("UPDATE {phrases} SET defaultphrase = 'Most Recent Posts' WHERE varname = 'new_posts' AND pluginid = %d",$forum_pluginid);

  // DB changes
  // Forum: attachments table
  //$DB->query("DROP TABLE IF EXISTS {p_forum_attachments}");
  $DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."p_forum_attachments (
  `attachment_id`       INT(10) UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `post_id`             INT(10) UNSIGNED  NOT NULL DEFAULT 0,
  `attachment_name`     VARCHAR(255)      collate utf8_unicode_ci NOT NULL DEFAULT '',
  `filename`            VARCHAR(255)      collate utf8_unicode_ci NOT NULL DEFAULT '',
  `filesize`            INT(10) UNSIGNED  NOT NULL DEFAULT 0,
  `filetype`            VARCHAR(64)       collate utf8_unicode_ci NOT NULL DEFAULT '',
  `user_id`             INT(10) UNSIGNED  NOT NULL DEFAULT 0,
  `username`            VARCHAR(128)      collate utf8_unicode_ci NOT NULL DEFAULT '',
  `download_count`      INT(11) UNSIGNED  NOT NULL DEFAULT 0,
  `uploaded_date`       INT(11) UNSIGNED  NOT NULL DEFAULT 0,
  `access_view`         VARCHAR(128)      collate utf8_unicode_ci NOT NULL DEFAULT '',
  KEY (`post_id`),
  KEY (`user_id`),
  KEY (`username`))
  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

  $DB->add_tableindex(PRGM_TABLE_PREFIX.'p_forum_attachments', 'user_id', 'user_id');
  $DB->add_tableindex(PRGM_TABLE_PREFIX.'p_forum_attachments', 'username', 'username');

  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p_forum_posts', 'attachment_count', 'INT(10)', 'NOT NULL DEFAULT 0');
}

// Enhancing the Plugin Settings table for easier development and data input validation
sd_DisplaySetupMessage('<strong>Upgrading users, please wait...</strong>');

$DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."users_data (
  `usersystemid` int(10) unsigned NOT NULL,
  `userid` int(10) unsigned NOT NULL,
  `authorname` tinytext collate utf8_unicode_ci,
  `first_name` varchar(64) collate utf8_unicode_ci NOT NULL default '',
  `last_name` varchar(64) collate utf8_unicode_ci NOT NULL default '',
  `user_birthday` int(10) default NULL,
  `contact_phone` varchar(32) collate utf8_unicode_ci NOT NULL default '',
  `address_street1` varchar(200) collate utf8_unicode_ci NOT NULL default '',
  `address_street2` varchar(200) collate utf8_unicode_ci NOT NULL default '',
  `address_zip` varchar(20) collate utf8_unicode_ci NOT NULL default '',
  `address_city` varchar(100) collate utf8_unicode_ci NOT NULL default '',
  `address_state` varchar(64) collate utf8_unicode_ci NOT NULL default '',
  `address_country` varchar(64) collate utf8_unicode_ci NOT NULL default '',
  `address_country_code` varchar(2) collate utf8_unicode_ci NOT NULL default '',
  `user_website` varchar(100) collate utf8_unicode_ci NOT NULL default '',
  `user_occupation` text collate utf8_unicode_ci,
  `user_gender` varchar(1) collate utf8_unicode_ci default '',
  `user_interests` text collate utf8_unicode_ci,
  `user_profile_img` varchar(100) collate utf8_unicode_ci NOT NULL default '',
  `user_screen_name` varchar(64) collate utf8_unicode_ci NOT NULL default '',
  `user_lastpost_time` int(11) NOT NULL default '0',
  `user_login_attempts` tinyint(4) NOT NULL default '0',
  `user_dst` tinyint(1) NOT NULL default '0',
  `user_dateformat` varchar(30) collate utf8_unicode_ci NOT NULL default 'Y-m-d H:i',
  `user_rank` mediumint(8) NOT NULL default '0',
  `user_new_privmsg` int(4) NOT NULL default '0',
  `user_unread_privmsg` int(4) NOT NULL default '0',
  `user_last_privmsg` int(11) NOT NULL default '0',
  `user_topic_show_days` varchar(1) collate utf8_unicode_ci NOT NULL default 't',
  `user_topic_sortby_dir` varchar(1) collate utf8_unicode_ci NOT NULL default 'd',
  `user_post_show_days` int(4) NOT NULL default '0',
  `user_post_sortby_dir` varchar(1) collate utf8_unicode_ci NOT NULL default 'd',
  `user_notify_pm` tinyint(1) NOT NULL default '1',
  `user_allow_pm` tinyint(1) NOT NULL default '1',
  `user_pm_email` tinyint(1) NOT NULL default '0',
  `user_view_avatars` tinyint(1) NOT NULL default '1',
  `user_view_sigs` tinyint(1) NOT NULL default '1',
  `user_allow_viewonline` tinyint(1) NOT NULL default '1',
  `user_allow_viewemail` tinyint(1) NOT NULL default '0',
  `user_allow_newsletter` tinyint(1) NOT NULL default '1',
  `user_avatar` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `user_avatar_type` tinyint(2) NOT NULL default '0',
  `user_avatar_width` smallint(4) NOT NULL default '0',
  `user_avatar_height` smallint(4) NOT NULL default '0',
  `user_sig` mediumtext collate utf8_unicode_ci,
  `user_from` varchar(100) collate utf8_unicode_ci NOT NULL default '',
  `user_aim` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `user_icq` varchar(15) collate utf8_unicode_ci NOT NULL default '',
  `user_jabber` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `user_msnm` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `user_skype` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `user_yim` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `user_has_blog` tinyint(1) NOT NULL default '0',
  `user_has_gallery` tinyint(1) NOT NULL default '0',
  `user_form_salt` varchar(32) collate utf8_unicode_ci NOT NULL default '',
  `user_profile_views` int(10) NOT NULL default '0',
  `user_company` varchar(100) collate utf8_unicode_ci NOT NULL default '',
  `user_department` varchar(200) collate utf8_unicode_ci NOT NULL default '',
  `contact_fax` varchar(32) collate utf8_unicode_ci NOT NULL default '',
  `contact_mobile_phone` varchar(32) collate utf8_unicode_ci NOT NULL default '',
  `contact_office_phone` varchar(32) collate utf8_unicode_ci NOT NULL default '',
  `user_text` mediumtext collate utf8_unicode_ci NULL,
  `contact_mobile_office` varchar(30) collate utf8_unicode_ci NOT NULL default '',
  PRIMARY KEY (`usersystemid`,`userid`),
  KEY  (`first_name`),
  KEY  (`last_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users_data', 'user_post_count');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users_data', 'user_thread_count');

$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'users', 'validation_time',  'INT(10)');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'users', 'usergroup_others', 'VARCHAR(255)', "collate utf8_unicode_ci NOT NULL DEFAULT '' AFTER `usergroupid`");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'users', 'salt', 'VARCHAR(20)', "collate utf8_unicode_ci NOT NULL DEFAULT '' AFTER `password`");

// Remove previously added columns from users table as these
// weren't used before and are now in users_data
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'first_name');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'last_name');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_birthday');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'contact_phone');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'address_street1');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'address_street2');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'address_zip');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'address_city');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'address_state');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'address_country');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'address_country_code');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_website');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_occupation');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_gender');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_interests');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_profile_img');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_screen_name');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_lastpost_time');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_login_attempts');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_dst');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_dateformat');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_rank');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_new_privmsg');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_unread_privmsg');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_last_privmsg');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_topic_show_days');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_topic_sortby_dir');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_post_show_days');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_post_show_days');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_post_sortby_dir');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_notify_pm');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_allow_pm');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_pm_email');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_view_avatars');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_view_sigs');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_allow_viewonline');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_allow_viewemail');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_allow_newsletter');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_avatar');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_avatar_type');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_avatar_width');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_avatar_height');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_sig');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_from');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_aim');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_icq');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_jabber');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_msnm');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_skype');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_yim');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_has_blog');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_has_gallery');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_form_salt');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_profile_views');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_company');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_department');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'contact_fax');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'contact_mobile_phone');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'contact_office_phone');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_text');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'contact_mobile_office');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users', 'authorname');

sd_DisplaySetupMessage('<strong>Updating User Post/Topic Counts...</strong>');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_post_count', 'MEDIUMINT');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'users', 'user_thread_count', 'MEDIUMINT');
$DB->query('UPDATE '.PRGM_TABLE_PREFIX.'users users SET user_post_count = (SELECT COUNT(*) FROM {p_forum_posts} fp WHERE fp.user_id = users.userid)');
$DB->query('UPDATE '.PRGM_TABLE_PREFIX.'users users SET user_thread_count = (SELECT COUNT(*) FROM {p_forum_topics} ft WHERE ft.post_user_id = users.userid)');
sd_DisplaySetupMessage('<strong>Done.</strong><br />');

// Re-add phrases columns for SD2 backwards compatibility
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'phrases', 'font', 'VARCHAR(32)',  "collate utf8_unicode_ci NOT NULL DEFAULT ''");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'phrases', 'color', 'VARCHAR(32)', "collate utf8_unicode_ci NOT NULL DEFAULT ''");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'phrases', 'size', 'VARCHAR(32)',  "collate utf8_unicode_ci NOT NULL DEFAULT ''");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'phrases', 'bold', 'TINYINT(1)',   "collate utf8_unicode_ci NOT NULL DEFAULT 0");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'phrases', 'italic', 'TINYINT(1)',  "collate utf8_unicode_ci NOT NULL DEFAULT 0");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'phrases', 'underline', 'TINYINT(1)', "collate utf8_unicode_ci NOT NULL DEFAULT 0");

// Enhance Usergroups table
sd_DisplaySetupMessage('Upgrading usergroups...');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'pluginmoderateids', 'MEDIUMTEXT',  "collate utf8_unicode_ci AFTER `pluginadminids`");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'admin_access_pages','MEDIUMTEXT',  "collate utf8_unicode_ci AFTER `adminaccess`");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'displayname',      'VARCHAR(100)', "collate utf8_unicode_ci NOT NULL DEFAULT ''");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'description',      'TEXT',         "collate utf8_unicode_ci");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'color_online',     'VARCHAR(20)',  "collate utf8_unicode_ci NOT NULL DEFAULT ''");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'display_online',   'TINYINT(1)',   'NOT NULL DEFAULT 0');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'allow_avatar',     'TINYINT(1)',   'NOT NULL DEFAULT 1');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'avatar_width',     'MEDIUMINT(4)', 'NOT NULL DEFAULT 80');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'avatar_height',    'MEDIUMINT(4)', 'NOT NULL DEFAULT 80');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'avatar_type',      'MEDIUMINT(4)', 'NOT NULL DEFAULT 0');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'avatar_name',      'VARCHAR(200)', "collate utf8_unicode_ci NOT NULL DEFAULT ''");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'allow_pm_send',    'TINYINT(1)',   'NOT NULL DEFAULT 1');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'allow_pm_receive', 'TINYINT(1)',   'NOT NULL DEFAULT 1');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'allow_signature',  'TINYINT(1)',   'NOT NULL DEFAULT 1');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'max_sig_chars',    'INT(10)',      'NOT NULL DEFAULT 0');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'articles_author_mode','TINYINT(1)','NOT NULL DEFAULT 0');

$DB->query("ALTER TABLE ".PRGM_TABLE_PREFIX."usergroups CHANGE COLUMN `description` `description` TEXT");
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."usergroups SET admin_access_pages = '' WHERE admin_access_pages IS NULL");
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."usergroups SET description = '' WHERE description IS NULL");
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."usergroups SET pluginmoderateids = '' WHERE pluginmoderateids IS NULL");

sd_DisplaySetupMessage('Usergroups done.<br />');

sd_DisplaySetupMessage('Updating Main Settings...');

InsertMainSetting('enable_rss_forum', 'settings_general_settings', 'Enable Forum RSS', 'Enable RSS Syndication for Forum plugin?', 'yesno', '1', 6);

$DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."mainsettings WHERE varname IN('dateentry_format','timeentry_format')");
$DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."adminphrases WHERE varname IN('date_entry_format','time_entry_format','date_entry_format_descr','time_entry_format_descr')");

InsertMainSetting('default_avatar_height', 'settings_general_settings', 'Default Avatar Height', 'Default height in pixels of the default avatar images.', 'text', '60', 11);
InsertMainSetting('default_avatar_width',  'settings_general_settings', 'Default Avatar Width', 'Default width in pixels of the default avatar images.', 'text', '60', 12);

// Re-adding SSL features:
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'categories', 'sslurl', 'TINYINT(1)', 'NOT NULL DEFAULT 0 AFTER `urlname`');
InsertMainSetting('forcessl', 'settings_general_settings', 'Force SSL', 'If marked yes, an error page will be displayed if an URL not using HTTPS is used.', 'yesno', '0', 101);
InsertMainSetting('sslurl', 'settings_general_settings', 'Secure Website URL', 'Please enter the full URL to your secure website:<br />
                  Example: http<strong>s</strong>://www.yoursite.com/', 'text', '', 100);
InsertAdminPhrase(0, 'pages_ssl_url',   'Secure website URL', 1);
InsertAdminPhrase(0, 'pages_ssl_url_hint', 'Secure this page using SSL?
    NOTE: You will need to have a secured host and configure the appropriate
    "Secure Website URL" under "Main Settings". If you are not sure,
    leave this setting to the default "<strong>No</strong>".', 1);

// Convert "siteactivation" to new "select:" input type:
InsertAdminPhrase(0, 'select_siteactivation_on', 'On');
InsertAdminPhrase(0, 'select_siteactivation_off', 'Off');
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."mainsettings SET input = '%s' WHERE varname = 'siteactivation'",
           "select:\r\non|On\r\noff|Off");

// Captcha Settings:
// - Fix displayorder
// - Insert new option to display for guests only
// - Insert "captcha_method" with new "select:" input type
InsertMainSetting('captcha_guests_only', 'settings_captcha', 'Guests-only Captcha',
  'Display Captcha/VVC only for Guests (default: yes)?<br />If set to No, individual plugins may have a setting to require it for every user.', 'yesno', '1', 1);
$DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."adminphrases where varname = 'captcha_method_descr'");
$curr_value = $DB->query_first("SELECT value FROM ".PRGM_TABLE_PREFIX."mainsettings where varname = 'captcha_method'");
$curr_value = !empty($curr_value[0]) ? $curr_value[0] : '2';
InsertMainSetting('captcha_method', 'settings_captcha', 'Captcha Method',
  'A "captcha" or visual verification code system is used primarily to verify a human is submitting a form and prevents spam with form submissions. Subdreamer VVC or reCaptcha are the two available options.',
  "select:\r\n0|Disable Captcha\r\n1|reCaptcha\r\n2|VVC Image", $curr_value, 2, true);
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."mainsettings SET displayorder = 5 WHERE groupname = 'settings_captcha' and varname = 'captcha_publickey'");
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."mainsettings SET displayorder = 6 WHERE groupname = 'settings_captcha' and varname = 'captcha_privatekey'");

// Fix reCaptcha link as it now is G**gle code
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."adminphrases SET defaultphrase = 'Enter your public reCaptcha key:<br /><a href=\"http://www.google.com/recaptcha/whyrecaptcha\" target=\"_blank\">Click here to get your public and private reCaptcha keys.</a>' WHERE varname = 'settings_captcha_publickey_desc'");

// Insert setting to enabled/disable the System Log: System Log Settings
InsertAdminPhrase(0, 'settings_system_log', 'System Log Settings', 7);
InsertMainSetting('syslog_enabled', 'settings_system_log', 'Enable System Log', 'If set to Yes,
  extra messages are displayed in the System Log page (like code warnings or login attempts).',
  "yesno", '1', 10);

// Convert "enablewysiwyg" with new "select:" input type:
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."mainsettings SET input = '%s' WHERE varname = 'enablewysiwyg'",
           "select:\r\n0|Disable WYSIWYG\r\n1|TinyMCE (default)\r\n2|CKeditor");

// Convert "title_order" with new "select:" input type:
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."mainsettings SET input = '%s' WHERE varname = 'title_order' AND groupname = 'settings_seo_settings'",
           "select:\r\n0|Site - Category (default)\r\n1|Category - Site");

// Fix potentially wrong database character set
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."adminphrases SET defaultphrase = 'Enter the character set of your database, which must correspond to the above <strong>Character Set</strong>.<br /> For the default website character set UTF-8 the database character set must be <strong>utf8</strong>.' WHERE varname = 'settings_db_charset_desc'");
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."mainsettings SET value = 'utf8' WHERE varname = 'db_charset' AND value IN ('utf-8', 'UTF-8')");

// Re-introduce gzip Compression main setting
InsertMainSetting('gzipcompress', 'settings_general_settings', 'GZip Compression', 'If your server is running off of apache, then you can turn this setting on to compress your pages potentially making your site much quicker.', "yesno", '0', 20);

// Add VVC table
$DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."vvc (
  `vvcid`        INT(10)     UNSIGNED NOT NULL AUTO_INCREMENT,
  `verifycode`   VARCHAR(100)         NOT NULL DEFAULT '',
  `datecreated`  INT(10)     UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`vvcid`))
  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

// Add new main setting
$DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."mainsettings WHERE groupname = 'character_encoding'");
InsertMainSetting('forum_character_set', 'settings_character_encoding', 'Forum Character Set', 'Character Set (e.g. utf-8 or iso-8859-1 etc.) of a 3rd party forum?<br />'.
                  'If the forum character set is not utf-8, it is highly recommended to have it converted to utf-8'.
                  ' to avoid wrong display in forum-related plugins.', 'text', 'utf-8', 100);

InsertMainSetting('vvc_bg_image',   'settings_captcha', 'VVC Background Image', 'If set to Yes, one of the delivered background images will be used. Requires GD php module to be enabled (default: Yes):<br />Distortion level: normal.<br />Note: this options only applies to the VVC captcha method.', 'yesno', '1', 10);
InsertMainSetting('vvc_bg_lines',   'settings_captcha', 'VVC Background Lines', 'If set to Yes, multiple extra lines are drawn to distort the VVC image (default: No):<br />Distortion level: medium.<br />Note: this options only applies to the VVC captcha method.', 'yesno', '0', 20);
InsertMainSetting('vvc_bg_dots',    'settings_captcha', 'VVC Background Dots',  'If set to Yes, many extra dots are drawn to distort the VVC image (default: No):<br />Distortion level: high.<br />Note: this options only applies to the VVC captcha method.', 'yesno', '0', 30);

$ext = $DB->query_first("SELECT value FROM ".PRGM_TABLE_PREFIX."mainsettings WHERE varname = 'url_extension'");
$DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."mainsettings WHERE varname = 'url_extension' AND groupname = 'System Settings'");
InsertMainSetting('url_extension', 'settings_seo_settings', 'URL Extension',
  'If the setting <strong>SEO URLs</strong> is enabled, then you have the option of setting an extension that will be added to the end of your URLs.<br />
  Note: on Apache web server the mod_rewrite module must be enabled, on IIS a 3rd party module installed!<br />Example: <strong>.html</strong>', 'text',
  (!empty($ext[0]) ? $ext[0] : '.html'), 1, true);

sd_DisplaySetupMessage('Main Settings updated.');

sd_DisplaySetupMessage('Adding phrases...');

$DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."adminphrases WHERE pluginid = 16 AND varname LIKE 'require%vvc%'");
$DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."pluginsettings WHERE pluginid = 16 AND title LIKE 'require%vvc%'");

InsertPhrase(1, 'please_enter_username',   'Please enter username and password.');
InsertPhrase(1, 'wrong_password',          'Wrong username or password.');
InsertPhrase(1, 'wrong_username',          'Wrong username or password.');
InsertPhrase(1, 'error_token_expired',     'Security Token expired!');
InsertPhrase(1, 'site_requires_javascript','This website requires Javascript to be enabled.');
InsertPhrase(1, 'rss_not_available',       'RSS feed not available.');

InsertPhrase(1, 'refresh',                 'Refresh');
InsertPhrase(1, 'incorrect_vvc_code',      'Incorrect confirmation code');
InsertPhrase(1, 'enter_verify_code',       'Enter the code you see in the image above (case sensitive).<br />Click on the image to refresh it.');
InsertPhrase(1, 'email_recipient_invalid', 'Email recipient is invalid or empty.');
InsertPhrase(1, 'email_sender_invalid',    'Email sender is invalid.');
InsertPhrase(1, 'email_invalid_header',    'Email has invalid header information.');
InsertPhrase(1, 'common_signup',           'Sign-Up');
InsertPhrase(1, 'common_login',            'Login');
InsertPhrase(1, 'common_or',               'or');
InsertPhrase(1, 'comments_confirm_delete', 'Really DELETE this Comment?');
InsertPhrase(1, 'comments_delete',         'Delete Comment');
InsertPhrase(1, 'comments_deleted',        'Comment deleted!');
InsertPhrase(1, 'comments_edit',           'Edit Comment');
InsertPhrase(1, 'comments_saved',          'Comment Saved!');
InsertPhrase(1, 'ajax_operation_failed',   'Operation failed!');
InsertPhrase(1, 'message_loading',         'Loading...');
InsertPhrase(1, 'common_close',            'Close');

InsertPhrase(2, 'pdf',                     'PDF');
InsertPhrase(2, 'social_link_delicious',   'Share on Delicious');
InsertPhrase(2, 'social_link_digg',        'Digg This');
InsertPhrase(2, 'social_link_facebook',    'Share on Facebook');
InsertPhrase(2, 'social_link_twitter',     'Share on Twitter');
InsertPhrase(2, 'email_to_friend',         'Email to a Friend');
InsertPhrase(2, 'tags',                    'Tagged with:');
InsertPhrase(2, 'views',                   'Views:');

InsertPhrase(6, 'error_no_name',           'Please enter your name');
InsertPhrase(6, 'error_no_email',          'Please enter your email address');
InsertPhrase(6, 'error_no_subject',        'Please enter a subject');
InsertPhrase(6, 'error_no_message',        'Please enter a message');

InsertPhrase(10, 'private_messages',       'Private Messages:');
InsertPhrase(10, 'unread_total',           ' Unread, Total ');
InsertPhrase(10, 'new_priv_msg',           'You have a new private message.');
InsertPhrase(10, 'title',                  'Title:');
InsertPhrase(10, 'sender',                 'Sender:');
InsertPhrase(10, 'click_ok',               'Click OK to view it, or cancel to hide this prompt.');
InsertPhrase(10, 'open_msg',               'Open Message in New Window?');
InsertPhrase(10, 'new_priv_msg_title',     'New Private Message');
InsertPhrase(10, 'forgot_password',        'Forgot your password?');
InsertPhrase(10, 'admin_panel',            'Admin Panel');
InsertPhrase(10, 'popup_alert',            'Failed to open a new window. Are you using a Popup Blocker?');

InsertPhrase(11, 'receive_emails',         'Receive Emails?');

// Add Admin Phrases
InsertAdminPhrase(0, 'unnamed',             'unnamed', 0);
InsertAdminPhrase(0, 'create_copy',         'Create Copy?');
InsertAdminPhrase(0, 'wysiwyg_toggle_editor', 'Toggle Editor', 0);
InsertAdminPhrase(0, 'file_not_writable',   'File is not writable');
InsertAdminPhrase(0, 'folder_not_writable', 'Folder is not writable');
InsertAdminPhrase(0, 'undefined_phrase',    'Undefined Phrase:');

InsertAdminPhrase(0, 'pages_create_copy',   'Create a copy of this Page?', 1);
InsertAdminPhrase(0, 'pages_edit_plugins',  'Edit Plugin Positions', 1);
InsertAdminPhrase(0, 'pages_external_link', 'External Link', 1);
InsertAdminPhrase(0, 'pages_cannot_delete', 'Not deletable', 1);
InsertAdminPhrase(0, 'no_pages_found',      'No pages found.', 1);
InsertAdminPhrase(0, 'no_pages_exist',      'No pages exist.', 1);
InsertAdminPhrase(0, 'filter_pages',        'Filter Pages', 1);
InsertAdminPhrase(0, 'pages_page_options',  'Page Options', 1);
InsertAdminPhrase(0, 'pages_skin_layout',   'Skin Layout', 1);
InsertAdminPhrase(0, 'pages_plugins',       'Uses Plugin', 1);
InsertAdminPhrase(0, 'pages_filter',        'Filter', 1, true);
InsertAdminPhrase(0, 'pages_limit',         'Limit', 1);
InsertAdminPhrase(0, 'pages_secure',        'SSL?', 1);
InsertAdminPhrase(0, 'pages_untitled',      '(untitled)', 1);
InsertAdminPhrase(0, 'pages_apply_filter',  'Apply Filter', 1);
InsertAdminPhrase(0, 'pages_remove_filter', 'Remove Filter', 1);
InsertAdminPhrase(0, 'pages_page_no_plugins', '<strong>Page without Plugins</strong>', 1);
InsertAdminPhrase(0, 'pages_page_has_plugins', '<strong>Page uses Plugins:</strong><br />', 1);
InsertAdminPhrase(0, 'pages_info_ssl_empty', 'Secure Website URL is currently empty (see Main Settings)!', 1);
InsertAdminPhrase(0, 'pages_info_ssl_set_to', 'Secure URL currently set to:', 1);
InsertAdminPhrase(0, 'pages_external_no_plugins', 'Plugin positioning not available for externally linked pages.', 1);
InsertAdminPhrase(0, 'pages_copy_suffix', '(copy)', 1);
InsertAdminPhrase(0, 'pages_copy_page_section', 'Create Page Copy', 1);
InsertAdminPhrase(0, 'pages_copy_page_descr', 'If you\'d like to copy over the settings from an existing page -
  including it\'s selected plugins and skin design - select it here.<br />
  To create a blank, new page, leave this empty.<br /><br />
  Please note, that the above <strong>Page Settings</strong> have to be specified in any case.', 1);
InsertAdminPhrase(0, 'pages_copy_current_page_descr', 'If you\'d like to create a copy
  of the currently displayed page - including it\'s selected plugins and skin design -
  check this option here.<br />', 1);
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."adminphrases SET defaultphrase = 'Order' WHERE varname = 'pages_display_order' AND adminpageid = 1");
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."adminphrases SET defaultphrase = 'Create / Copy Page' WHERE varname = 'menu_pages_create_page' AND adminpageid = 0");

InsertAdminPhrase(2, 'articles_filter_title', 'Filter Articles', 2);
InsertAdminPhrase(2, 'articles_filter', 'Filter', 2);
InsertAdminPhrase(2, 'articles_clear_filter', 'Clear Filter', 2);
InsertAdminPhrase(2, 'articles_apply_filter', 'Apply Filter', 2);
InsertAdminPhrase(2, 'limit', 'Limit', 2);
InsertAdminPhrase(2, 'display_email_link', 'Display Email Link', 2);
InsertAdminPhrase(2, 'display_social_links', 'Display Social Media Links', 2);
InsertAdminPhrase(2, 'display_tags', 'Display Tags', 2);
InsertAdminPhrase(2, 'articles_date_asc', 'Oldest First', 2);
InsertAdminPhrase(2, 'articles_date_desc', 'Newest First', 2);
InsertAdminPhrase(2, 'articles_onlineoffline', 'Online/Offline', 2);
InsertAdminPhrase(2, 'articles_online', 'Online', 2);
InsertAdminPhrase(2, 'articles_offline', 'Offline', 2);
InsertAdminPhrase(2, 'display_order_asc', 'Display Order Asc', 2);
InsertAdminPhrase(2, 'display_order_desc', 'Display Order Desc', 2);
InsertAdminPhrase(2, 'order_title_asc', 'Article Title A-Z', 2);
InsertAdminPhrase(2, 'order_title_desc', 'Article Title Z-A', 2);
InsertAdminPhrase(2, 'order_author_asc', 'Author Name A-Z', 2);
InsertAdminPhrase(2, 'order_author_desc', 'Author Name Z-A', 2);
InsertAdminPhrase(2, 'original_author', 'Original Author', 2);
InsertAdminPhrase(2, 'last_modified', 'Last Modified', 2);
InsertAdminPhrase(2, 'status_pending_review', 'Offline till approved by Administrator', 2);
InsertAdminPhrase(2, 'confirm_remove_tag', 'Remove selected tag for current article?', 2);
InsertAdminPhrase(2, 'edit_tags_hint', 'Separate tags by comma and remove by clicking the button next to each.', 2);
InsertAdminPhrase(17, 'delete_failed', 'Image deletion failed, please check write permissions of images folder!', 2);

InsertAdminPhrase(0, 'comments_clear_filter', 'Clear Filter', 4);
InsertAdminPhrase(0, 'comments_apply_filter', 'Apply Filter', 4);
InsertAdminPhrase(0, 'comments_limit', 'Limit', 4);
InsertAdminPhrase(0, 'comments_filter_title', 'Filter Comments', 4);
InsertAdminPhrase(0, 'comments_filter', 'Filter', 4);
InsertAdminPhrase(0, 'comments_plugin', 'Plugin', 4);
InsertAdminPhrase(0, 'comments_sort_by', 'Sort by', 4);
InsertAdminPhrase(0, 'comments_status', 'Status', 4);
InsertAdminPhrase(0, 'comments_approved', 'Approved', 4);
InsertAdminPhrase(0, 'comments_unapproved', 'Unapproved', 4);
InsertAdminPhrase(0, 'comments_date_asc', 'Oldest First', 4);
InsertAdminPhrase(0, 'comments_date_desc', 'Newest First', 4);

InsertAdminPhrase(0, 'message_plugin_requires_name', 'The name of the plugin is required.', 2);
InsertAdminPhrase(0, 'hint_no_blanks', 'Please do not use blanks!', 2);

InsertAdminPhrase(0, 'users_profile_config', 'User Profile Configuration', 5);
InsertAdminPhrase(0, 'users_profile_fields', 'User Profile Fields', 5);

InsertAdminPhrase(0, 'users_other_usergroups', '<strong>Other Usergroups</strong>:<br /><br />
  The user may be a member of any additional usergroup, whose permissions will be
  cumulatively added to the ones of the primary usergroup. However, if any of the
  selected usergroups is banned, only banned usergroups are stored.<br />
  Use <strong>CTRL+click</strong> to select/unselect indiviual groups.', 5);
InsertAdminPhrase(0, 'users_usergroup_displaycolor', 'Font Color', 5);
InsertAdminPhrase(0, 'users_pages_admin_access', 'Admin Pages Access', 5);
InsertAdminPhrase(0, 'users_pages_admin_access_desc', 'If "<strong>Full Admin Access</strong>" is not granted (set to "No"),
  users of this group may still have access to indiviual admin pages (use CTRL+click to select/deselect entries).<br />
  This may be useful when setting up users to be able to only add and maintain content (like Pages, Plugins),
  but not change any system settings.', 5);
InsertAdminPhrase(0, 'usergroup_description', 'Description', 5);
InsertAdminPhrase(0, 'users_usergroup_description_desc', 'Publicly visible <strong>description</strong> for the usergroup, which may be used by plugins:', 5);
InsertAdminPhrase(0, 'users_usergroup_displayname', 'User Group Displayname', 5);
InsertAdminPhrase(0, 'users_usergroup_displayname_desc', 'Publicly visible <strong>displayname</strong> for the usergroup, which may be used by plugins:', 5);
InsertAdminPhrase(0, 'users_allow_moderate', 'Allow Moderate', 5);
InsertAdminPhrase(0, 'usergroup_displayname', 'Displayname', 5);
InsertAdminPhrase(0, 'usergroup_unnamed', 'Unnamed group', 5);

InsertAdminPhrase(0, 'forum_integration_enabled', 'Forum Integration Enabled', 5);
$DB->query("DELETE FROM {adminphrases} WHERE varname like 'forum_integration_msg_users%'");
InsertAdminPhrase(0, 'forum_integration_msg_users1', 'NOTE: The Subdreamer usersystem is currently OFF. Forum integration is ON.<br />
This means that the CMS will exclusively use the forum\'s member database to authenticate users and
permissions will be based off your forums usergroups. You can manage users within the CMS user pages/tab,
but those changes will only apply to the website when forum integration is turned off again.<br />
If you need to view, modify, update, change details, or email a user that exists in your forum, please
do so from your forum administration pages.<br />', 5, true);

InsertAdminPhrase(0, 'forum_integration_msg_usergroups', 'Note: Subdreamer is currently integrated with a forum.<br />', 5);

InsertAdminPhrase(0, 'users_authorname_exists', 'The specified author name already exists, please change it.', 5);
InsertAdminPhrase(0, 'users_authorname', 'Articles Author Name', 5);
InsertAdminPhrase(0, 'users_authorname_desc', 'Only for articles this is the author name, separate of the above username.
  Once this is set either here or when the user adds/submits an article in the Articles page or from the frontpage,
  this can only be changed here by an administrator.', 5);
InsertAdminPhrase(0, 'users_author_mode', 'Articles Author Mode', 5);
InsertAdminPhrase(0, 'users_receive_emails', 'Receive Emails', 5);
InsertAdminPhrase(0, 'users_author_mode_desc', 'Restrict access for users to view/edit only Articles they created (default: No)?<br />
  This option is used if this usergroup has access to the Articles page and the Articles plugin
  so that users only have access to their own articles.', 5);

InsertAdminPhrase(0, 'skins_insert_variable', 'Insert Variable:', 6);
InsertAdminPhrase(0, 'toggle_highlighting', 'Toggle Highlighting', 6);
InsertAdminPhrase(0, 'skins_layout_hint',   'Layouts:', 6);

InsertAdminPhrase(0, 'syslog_clear_log', 'Clear System Log', 7);
InsertAdminPhrase(0, 'syslog_clear_log_prompt', 'Really clear out all entries from the System Log?\r\nThis operation cannot be undone.', 7);
InsertAdminPhrase(0, 'syslog_current_time', 'Current time:', 7);
InsertAdminPhrase(0, 'syslog_distinct_messages', 'Show Distinct Messages', 7);
InsertAdminPhrase(0, 'syslog_refresh', 'Refresh', 7);
InsertAdminPhrase(0, 'syslog_search', 'Search', 7);
InsertAdminPhrase(0, 'settings_edit_plugin_names', 'Translate Plugin Names', 7);
InsertAdminPhrase(0, 'settings_save_translations', 'Save Translations', 7);
InsertAdminPhrase(0, 'settings_translations_saved', 'Translations Saved', 7);
InsertAdminPhrase(0, 'syslog_messages_deleted', 'Messages deleted:', 7);
InsertAdminPhrase(0, 'syslog_messages', 'Messages', 7);
InsertAdminPhrase(0, 'syslog_messages_of', 'of', 7);

InsertPhrase(12, 'invalid_username', 'Username is not allowed, please enter a different one.');

sd_DisplaySetupMessage('Adding phrases done.');

// Add Plugin Settings
sd_DisplaySetupMessage('Adding plugin settings');
InsertPluginSetting(2, 'article_display_settings', 'Display Email Link', 'Display an Email link?', 'yesno', '1', 6);
InsertPluginSetting(2, 'article_display_settings', 'Display Social Media Links', 'Display social media links for each article?', 'yesno', '0', 8);
InsertPluginSetting(2, 'article_display_settings', 'Display Views Count', 'Display the number of views for each article (default: no)?', 'yesno', '0', 4);
DeletePluginSetting(2, 'article_display_settings', 'display_tags');

DeletePluginSetting(6, 'contact_form_settings', 'require_captcha');

InsertPluginSetting(10, 'user_login_panel_settings', 'Display Avatar', 'Display avatar image for user?', 'yesno', '1', 10);
InsertPluginSetting(10, 'user_login_panel_settings', 'Show Private Messages', 'Enable private message notifications?', 'yesno', '1', 15);
InsertPluginSetting(10, 'user_login_panel_settings', 'Show Popups', 'Show PM notifications as popup?', 'yesno', '1', 20);

$DB->query("UPDATE ".PRGM_TABLE_PREFIX."pluginsettings SET title = 'banned_ip_addresses' WHERE pluginid = 12 AND title = 'Banned IP Addresses'");
InsertPluginSetting(12, 'user_registration_settings', 'Banned IP Addresses', 'Enter a list of banned ip addresses (one entry per line)<br />You can ban entire subnets (0-255) by using wildcard characters (192.168.0.*, 192.168.*.*) or enter a full ip address:', 'textarea', '', 5, true);
InsertPluginSetting(12, 'user_registration_settings', 'Invalid Usernames', 'Enter a list of invalid usernames (one entry per line)<br />These usernames will not be allowed for Login or Registration.', 'textarea', '', 5, true);
InsertPluginSetting(12, 'user_registration_settings', 'Reset Password Title', 'Title for the password reset form:', 'wysiwyg', '<strong>Please enter your registered email to where the new password should be sent to:</strong><br /><br />', 10);
InsertPluginSetting(12, 'user_registration_settings', 'Registration Form Title', 'Title for the registration form:', 'wysiwyg', '<strong>Please enter your name, password and email for registration:</strong><br /><br />', 20);

sd_DisplaySetupMessage('Plugin settings added.');

sd_DisplaySetupMessage('Converting tables to utf8_unicode_ci...');
$DB->query('ALTER DATABASE `'.$DB->database.'` CHARACTER SET utf8 COLLATE utf8_unicode_ci');
if(!empty($DB->table_names_arr[$DB->database]))
{
  $oldignore = $DB->ignore_error;
  $DB->ignore_error = true;
  foreach($DB->table_names_arr[$DB->database] as $tablename)
  {
    if($DB->table_exists(PRGM_TABLE_PREFIX.$tablename))
    {
      $DB->query('ALTER TABLE '.PRGM_TABLE_PREFIX.$tablename.' CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
    }
  }
  $DB->ignore_error = $oldignore;
}
sd_DisplaySetupMessage('Done.');

// Enhance Articles plugin
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'p2_news', 'org_author_name');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'p2_news', 'last_modifier_name');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p2_news', 'tags', 'MEDIUMTEXT', 'NOT NULL collate utf8_unicode_ci AFTER `seo_title`');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p2_news', 'org_author_id', 'INT(10)', 'NULL DEFAULT 0');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p2_news', 'org_author_name', 'TEXT', 'collate utf8_unicode_ci');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p2_news', 'org_system_id', 'INT(10)', 'NOT NULL DEFAULT 0');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p2_news', 'org_created_date', 'INT(10)', 'NOT NULL DEFAULT 0 AFTER `org_system_id`');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p2_news', 'last_modifier_system_id', 'INT(10)', 'NOT NULL DEFAULT 0');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p2_news', 'last_modifier_id', 'INT(10)', 'NOT NULL DEFAULT 0');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p2_news', 'last_modifier_name', 'TEXT', 'collate utf8_unicode_ci');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p2_news', 'last_modified_date', 'INT(10)', 'NOT NULL DEFAULT 0');

$DB->query("ALTER TABLE ".PRGM_TABLE_PREFIX."p2_news CHANGE COLUMN `title` `title` TEXT NOT NULL");
$DB->query("ALTER TABLE ".PRGM_TABLE_PREFIX."p2_news CHANGE COLUMN `author` `author` TEXT NOT NULL");
$DB->query("ALTER TABLE ".PRGM_TABLE_PREFIX."p2_news CHANGE COLUMN `metadescription` `metadescription` MEDIUMTEXT NOT NULL");
$DB->query("ALTER TABLE ".PRGM_TABLE_PREFIX."p2_news CHANGE COLUMN `metakeywords` `metakeywords` MEDIUMTEXT NOT NULL");
$DB->query("ALTER TABLE ".PRGM_TABLE_PREFIX."p2_news CHANGE COLUMN `description` `description` MEDIUMTEXT NOT NULL");

$DB->ignore_error = True;
$DB->query('UPDATE '.PRGM_TABLE_PREFIX.'p2_news SET org_author_id =
IFNULL((SELECT userid FROM '.PRGM_TABLE_PREFIX.'users users
 WHERE users.username COLLATE utf8_unicode_ci = '.PRGM_TABLE_PREFIX.'p2_news.author COLLATE utf8_unicode_ci),0)
WHERE org_author_id = 0');
$DB->ignore_error = False;

// Add Main Settings
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."adminphrases SET defaultphrase =
   'Enable a WYSIWYG editor in the admin panel?<br /><br />
   Subdreamer supports 2 powerful editors for editing contents or options in
   a what-you-see-is-what-you-get (WYSIWYG) manner. This can be either disabled
   or one of TinyMCE (default) or CKeditor. WYSIWYG is used in several areas
   including Articles and the website logo setting.<br />
   The editor will allow you to view changes you make on your content such as
   altering a font\'s size and color. While the editor is great for simple HTML
   changes, it has to be disabled for Javascript and sometimes also for more
   complex code originating from 3rd party tools.'
   WHERE adminpageid = 7 AND varname = 'settings_enable_wysiwyg_desc'");

$DB->query("UPDATE ".PRGM_TABLE_PREFIX."adminphrases SET defaultphrase =
   'Number of seconds of idle time before you are automatically logged out of
   the admin control panel (default 7200 = 2 hours)<br />Entering a value of
   0 here will mean that your session will not timeout:'
   WHERE adminpageid = 7 AND varname = 'settings_admin_cookie_timeout_desc'");

// Add new email settings (re-added from SD 2.6)
$curr_value = $DB->query_first("SELECT value FROM ".PRGM_TABLE_PREFIX."mainsettings where varname = 'default_email_format'");
$curr_value = !empty($curr_value[0]) ? $curr_value[0] : 'Text';
InsertMainSetting('default_email_format', 'settings_email_settings', 'Default Email Format',
  'Default format for sending Emails.',
  "select:\r\nText|Text\r\nHTML|HTML", $curr_value, 0, true);
InsertMainSetting('email_encoding_charset', 'settings_email_settings', 'Email Encoding Charset',
  'Specify an encoding character set for emails. This should be the same as the \'Character Set\' setting,
   but may be set to a different value depending on your preferred language.
   The default is <strong>iso-8859-1</strong>.', 'text', 'iso-8859-1', 5);
$curr_value = $DB->query_first("SELECT value FROM ".PRGM_TABLE_PREFIX."mainsettings where varname = 'email_mailtype'");
$curr_value = !empty($curr_value[0]) ? $curr_value[0] : '0';
InsertMainSetting('email_mailtype', 'settings_email_settings', 'Default Email Program',
  'Default system program for sending emails. In case the server\'s PHP "mail()" function is disabled, try SMTP or Sendmail.',
  "select:\r\n0|Mail (Default)\r\n1|Sendmail\r\n2|SMTP", $curr_value, 10, true);
InsertMainSetting('email_smtp_server', 'settings_email_settings',
  'SMTP Server Name', 'Name of the SMTP server:<br />Specify multiple servers separated
  by a semi-colon (<strong>;</strong>). You can also specify a different port for
  each host by using this format: [hostname:port] (e.g. smtp1.example.com:25;smtp2.example.com)', 'text', '', 15);
InsertMainSetting('email_smtp_auth', 'settings_email_settings', 'SMTP Authentication', 'Use SMTP authentication to connect to the SMTP server (requiring username/password):', 'yesno', '1', 20);
InsertMainSetting('email_smtp_user', 'settings_email_settings', 'SMTP Username', 'Username for SMTP authentication:', 'text', '', 30);
InsertMainSetting('email_smtp_pwd',  'settings_email_settings', 'SMTP Password', 'Password for SMTP authentication:', 'text', '', 40);
$curr_value = $DB->query_first("SELECT value FROM ".PRGM_TABLE_PREFIX."mainsettings where varname = 'email_smtp_secure'");
$curr_value = !empty($curr_value[0]) ? $curr_value[0] : '0';
InsertMainSetting('email_smtp_secure', 'settings_email_settings', 'SMTP Secure Protocol',
  'Use a secure protocol with SMTP (default: Off):',
  "select:\r\n0|Off\r\n1|SSL\r\n2|TLS", $curr_value, 50, true);
InsertMainSetting('email_sendmail_path', 'settings_email_settings', 'Sendmail Path',
  'Path to sendmail application. When empty it assumes Un*x/Linux platform and defaults to usr/sbin/sendmail.<br />
   For Wind*ws platform enter full path and filename to sendmail.exe here.', 'text', '', 60);


// ************** FORUM USER SYSTEM INTEGRATION **************
// ************** FORUM USER SYSTEM INTEGRATION **************
// ************** FORUM USER SYSTEM INTEGRATION **************

InsertAdminPhrase(0, 'close_window', 'Close Window', 0);
InsertAdminPhrase(0, 'hint_close', 'Close', 0);

// Any menu item must be with admin-pageid of 0!
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."adminphrases SET adminpageid = 0 WHERE varname = 'menu_users_forum_integration' AND adminpageid = 5");
InsertAdminPhrase(0, 'menu_users_forum_integration', 'Forum Integration',0);
InsertAdminPhrase(0, 'menu_users_profiles', 'Profiles Configuration',0);
InsertAdminPhrase(0, 'select_forum', 'Select the Forum system',5);

$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'forumusergroupid', 'INT(10)', 'NOT NULL DEFAULT 0');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'sessions',   'location', 'VARCHAR(255)', "NOT NULL DEFAULT ''");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'sessions',   'loggedin', 'TINYINT(1)', 'NOT NULL DEFAULT 0');

$DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."usersystems (
  `usersystemid`      int(10)         unsigned NOT NULL auto_increment PRIMARY KEY,
  `name`              varchar(64)     collate utf8_unicode_ci NOT NULL default '',
  `activated`         enum('0','1')   NOT NULL default '0',
  `dbname`            varchar(128)    collate utf8_unicode_ci NOT NULL default '',
  `queryfile`         varchar(64)     collate utf8_unicode_ci NOT NULL default '',
  `tblprefix`         varchar(32)     collate utf8_unicode_ci NOT NULL default '',
  `folderpath`        varchar(128)    collate utf8_unicode_ci NOT NULL default '',
  `cookietimeout`     int(11)         unsigned NOT NULL default 0,
  `cookiedomain`      varchar(200)    collate utf8_unicode_ci NOT NULL default '',
  `cookiepath`        varchar(200)    collate utf8_unicode_ci NOT NULL default '',
  `cookieprefix`      varchar(32)     collate utf8_unicode_ci NOT NULL default '',
  `extra`             varchar(64)     collate utf8_unicode_ci NOT NULL default '',
  KEY (`name`))
  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

if(!$DB->query_first("SELECT 1 FROM ".PRGM_TABLE_PREFIX."usersystems WHERE `name` = 'Subdreamer'"))
{
  $DB->query("INSERT INTO ".PRGM_TABLE_PREFIX."usersystems (`usersystemid`, `name`, `activated`, `dbname`, `queryfile`, `tblprefix`, `folderpath`, `cookietimeout`, `cookieprefix`, `extra`) VALUES
  (NULL, 'Subdreamer', '1', '', 'subdreamer.php', '', '', 0, '', '')");
}
if(!$DB->query_first("SELECT 1 FROM ".PRGM_TABLE_PREFIX."usersystems WHERE `name` = 'vBulletin 3'"))
{
  $DB->query("INSERT INTO ".PRGM_TABLE_PREFIX."usersystems (`usersystemid`, `name`, `activated`, `dbname`, `queryfile`, `tblprefix`, `folderpath`, `cookietimeout`, `cookieprefix`, `extra`) VALUES
  (NULL, 'vBulletin 3', '0', '', 'vbulletin3.php', '', '', 900, 'bb', '')");
}
if(!$DB->query_first("SELECT 1 FROM ".PRGM_TABLE_PREFIX."usersystems WHERE `name` = 'vBulletin 4'"))
{
  $DB->query("INSERT INTO ".PRGM_TABLE_PREFIX."usersystems (`usersystemid`, `name`, `activated`, `dbname`, `queryfile`, `tblprefix`, `folderpath`, `cookietimeout`, `cookieprefix`, `extra`) VALUES
  (NULL, 'vBulletin 4', '0', '', 'vbulletin4.php', '', '', 900, 'bb', '')");
}
if(!$DB->query_first("SELECT 1 FROM ".PRGM_TABLE_PREFIX."usersystems WHERE `name` = 'phpBB2'"))
{
  $DB->query("INSERT INTO ".PRGM_TABLE_PREFIX."usersystems (`usersystemid`, `name`, `activated`, `dbname`, `queryfile`, `tblprefix`, `folderpath`, `cookietimeout`, `cookieprefix`, `extra`) VALUES
  (NULL, 'phpBB2', '0', '', 'phpbb2.php', '', '', 0, 'phpbb2mysql', '')");
}
if(!$DB->query_first("SELECT 1 FROM ".PRGM_TABLE_PREFIX."usersystems WHERE `name` = 'phpBB3'"))
{
  $DB->query("INSERT INTO ".PRGM_TABLE_PREFIX."usersystems (`usersystemid`, `name`, `activated`, `dbname`, `queryfile`, `tblprefix`, `folderpath`, `cookietimeout`, `cookieprefix`, `extra`) VALUES
  (NULL, 'phpBB3', '0', '', 'phpbb3.php', '', '', 0, 'phpbb3mysql', '')");
}
if(!$DB->query_first("SELECT 1 FROM ".PRGM_TABLE_PREFIX."usersystems WHERE `name` = 'Invision Power Board 2'"))
{
  $DB->query("INSERT INTO ".PRGM_TABLE_PREFIX."usersystems (`usersystemid`, `name`, `activated`, `dbname`, `queryfile`, `tblprefix`, `folderpath`, `cookietimeout`, `cookieprefix`, `extra`) VALUES
  (NULL, 'Invision Power Board 2', '0', '', 'ipb2.php', '', '', 3600, '', '')");
}
if(!$DB->query_first("SELECT 1 FROM ".PRGM_TABLE_PREFIX."usersystems WHERE `name` = 'Invision Power Board 3'"))
{
  $DB->query("INSERT INTO ".PRGM_TABLE_PREFIX."usersystems (`usersystemid`, `name`, `activated`, `dbname`, `queryfile`, `tblprefix`, `folderpath`, `cookietimeout`, `cookieprefix`, `extra`) VALUES
  (NULL, 'Invision Power Board 3', '0', '', 'ipb3.php', '', '', 3600, '', '')");
}
if(!$DB->query_first("SELECT 1 FROM ".PRGM_TABLE_PREFIX."usersystems WHERE `name` = 'Simple Machines Forum 1'"))
{
  $DB->query("INSERT INTO ".PRGM_TABLE_PREFIX."usersystems (`usersystemid`, `name`, `activated`, `dbname`, `queryfile`, `tblprefix`, `folderpath`, `cookietimeout`, `cookieprefix`, `extra`) VALUES
  (NULL, 'Simple Machines Forum 1', '0', '', 'smf1.php', '', '', 0, '', '')");
}
if(!$DB->query_first("SELECT 1 FROM ".PRGM_TABLE_PREFIX."usersystems WHERE `name` = 'Simple Machines Forum 2'"))
{
  $DB->query("INSERT INTO ".PRGM_TABLE_PREFIX."usersystems (`usersystemid`, `name`, `activated`, `dbname`, `queryfile`, `tblprefix`, `folderpath`, `cookietimeout`, `cookieprefix`, `extra`) VALUES
  (NULL, 'Simple Machines Forum 2', '0', '', 'smf2.php', '', '', 3600, '', '')");
}

$sd_usersystem_id = $DB->query_first('SELECT usersystemid FROM '.PRGM_TABLE_PREFIX."usersystems WHERE activated = '1'");
$DB->query('UPDATE '.PRGM_TABLE_PREFIX.'p2_news SET org_author_name = author, org_system_id = %d WHERE org_author_name IS NULL',$sd_usersystem_id[0]);

// User Profile phrases
InsertPhrase(11, 'view_profile', 'View Profile');
InsertPhrase(11, 'edit_profile', 'Edit My Profile');
InsertPhrase(11, 'update_profile_errors', 'Errors with profile update:');

// Add User Profile configuration groups
$DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."users_field_groups (
  `groupname_id` int(10) NOT NULL AUTO_INCREMENT,
  `displayname` varchar(80) collate utf8_unicode_ci NOT NULL default '',
  `displayorder` int(10) NOT NULL default 0,
  `is_visible` tinyint(2) NOT NULL default 1,
  `is_public` tinyint(2) NOT NULL default 0,
  PRIMARY KEY  (`groupname_id`),
  KEY `displayname` (`displayname`),
  KEY `displayorder` (`displayorder`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

// Add default groups if none exist
$count = $DB->query_first("SELECT COUNT(*) FROM ".PRGM_TABLE_PREFIX.'users_field_groups');
if(empty($count[0]))
{
  $DB->query("INSERT INTO ".PRGM_TABLE_PREFIX."users_field_groups
  (`groupname_id`, `displayname`, `displayorder`, `is_visible`, `is_public`)
  VALUES
  (1, 'User Credentials', 10, 1, 0),
  (2, 'Personal Details', 20, 1, 0),
  (3, 'Address', 30, 1, 0),
  (4, 'Phone', 40, 1, 0),
  (5, 'Other Information', 50, 1, 0),
  (6, 'Messenging', 60, 1, 0),
  (7, 'Site Preferences', 70, 1, 0)");
}
// Add plugin phrases for all groupnames
$getitems = $DB->query('SELECT displayname FROM '.PRGM_TABLE_PREFIX.'users_field_groups ORDER BY groupname_id');
while($item = $DB->fetch_array($getitems))
{
  $phrase_id = 'groupname_'.strtolower(preg_replace('/[^0-9a-zA-Z_]/', '_', $item['displayname']));
  InsertPhrase(11, $phrase_id, $item['displayname']);
}

// Add User Profile configuration fields
$DB->query('DROP TABLE IF EXISTS '.PRGM_TABLE_PREFIX.'users_fields'); //TODO: remove before final release!
$DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."users_fields (
  `fieldnum` smallint(4) NOT NULL auto_increment,
  `groupname_id` int(10) NOT NULL default 0,
  `fieldname` varchar(30) collate utf8_unicode_ci NOT NULL default '',
  `fieldlabel` varchar(50) collate utf8_unicode_ci NOT NULL default '',
  `fieldshow` tinyint(4) NOT NULL default '1',
  `fieldorder` int(4) NOT NULL default '0',
  `crypted` tinyint(4) NOT NULL default '0',
  `fieldlength` smallint(4) NOT NULL default '200',
  `fieldtype` varchar(30) collate utf8_unicode_ci NOT NULL default '',
  `vartype` varchar(20) collate utf8_unicode_ci NOT NULL default 'string',
  `fieldexpr` tinytext collate utf8_unicode_ci NOT NULL,
  `fieldmask` varchar(100) collate utf8_unicode_ci NOT NULL default '',
  `errdescr` varchar(30) collate utf8_unicode_ci NOT NULL default '*',
  `req` smallint(4) NOT NULL default '1',
  `upd` tinyint(4) NOT NULL default '1',
  `ins` tinyint(4) NOT NULL default '1',
  `seltable` varchar(100) collate utf8_unicode_ci default NULL,
  `readonly` tinyint(4) NOT NULL default '0',
  `public_status` varchar(255) collate utf8_unicode_ci NOT NULL default '0',
  `noforum` tinyint(2) NOT NULL default '0',
  PRIMARY KEY  (`fieldnum`),
  KEY `fieldorder` (`fieldorder`),
  KEY `fieldname` (`fieldname`),
  KEY `groupname_id` (`groupname_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$count = $DB->query_first('SELECT COUNT(*) FROM '.PRGM_TABLE_PREFIX.'users_fields');
if(empty($count[0]))
{
  $DB->query("INSERT INTO ".PRGM_TABLE_PREFIX."users_fields (`fieldnum`, `groupname_id`, `fieldname`, `fieldlabel`, `fieldshow`, `fieldorder`, `crypted`, `fieldlength`, `fieldtype`, `vartype`, `fieldexpr`, `fieldmask`, `errdescr`, `req`, `upd`, `ins`, `seltable`, `readonly`, `public_status`, `noforum`) VALUES
  (1, 1, 'user_text', 'User Message', 1, 7, 0, 100, 'text', 'string', '', '', '*', 0, 1, 1, '', 0, '0', 0),
  (2, 1, 'user_screen_name', 'Screen Name', 1, 8, 0, 64, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (3, 1, 'authorname', 'Author Name', 1, 9, 0, 64, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (4, 2, 'first_name', 'First Name', 1, 1, 0, 64, 'text', 'string', '', '', '*', 1, 1, 1, NULL, 0, '0', 0),
  (5, 2, 'last_name', 'Last Name', 1, 2, 0, 64, 'text', 'string', '', '', '*', 1, 1, 1, NULL, 0, '0', 0),
  (6, 2, 'user_birthday', 'Birthday', 1, 3, 0, 10, 'date', 'integer', '', '', '*', 1, 1, 1, NULL, 0, '0', 0),
  (7, 2, 'user_gender', 'Gender', 1, 4, 0, 1, 'select', 'string', '0|Not specified\r\n1|Male\r\n2|Female', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (8, 3, 'address_street1', 'Address 1', 1, 5, 0, 200, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (9, 3, 'address_street2', 'Address 2', 1, 6, 0, 200, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (10, 3, 'address_zip', 'ZIP', 1, 7, 0, 20, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (11, 3, 'address_city', 'City', 1, 8, 0, 100, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (12, 3, 'address_state', 'State', 1, 9, 0, 64, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (13, 3, 'address_country', 'Country', 1, 10, 0, 2, 'select', 'string', '', '', '*', 0, 1, 1, 'countries|code|name', 0, '0', 0),
  (14, 4, 'contact_phone', 'Phone', 1, 1, 0, 32, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (15, 4, 'contact_mobile_office', 'Phone (office)', 1, 2, 0, 32, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (16, 4, 'contact_fax', 'Fax (private)', 1, 3, 0, 32, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (17, 4, 'contact_mobile_phone', 'Phone (mobile)', 1, 4, 0, 32, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (18, 5, 'user_website', 'Website', 1, 1, 0, 100, 'url', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (19, 5, 'user_occupation', 'Occupation', 1, 2, 0, 255, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (20, 5, 'user_interests', 'Interests', 1, 3, 0, 2048, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (21, 5, 'user_company', 'Company', 1, 4, 0, 50, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (22, 5, 'user_department', 'Department', 1, 5, 0, 50, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (23, 6, 'user_aim', 'AIM username', 1, 1, 0, 100, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (24, 6, 'user_icq', 'ICQ username', 1, 2, 0, 100, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (25, 6, 'user_jabber', 'Jabber username', 1, 3, 0, 100, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (26, 6, 'user_msnm', 'MSNM username', 1, 4, 0, 100, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (27, 6, 'user_skype', 'Skype username', 1, 5, 0, 100, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (28, 6, 'user_yim', 'YIM username', 1, 6, 0, 100, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (29, 7, 'user_dst', 'Daylight Saving', 1, 1, 0, 1, 'yesno', 'bool', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (30, 7, 'user_dateformat', 'Dateformat', 1, 2, 0, 30, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (31, 7, 'user_notify_pm', 'Private Message Notifications', 1, 3, 0, 1, 'yesno', 'bool', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (32, 7, 'user_pm_email', 'Private Message as Email', 1, 4, 0, 1, 'yesno', 'bool', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (33, 7, 'user_view_avatars', 'View Avatars', 1, 5, 0, 1, 'yesno', 'bool', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (34, 7, 'user_view_sigs', 'View Signatures', 1, 6, 0, 1, 'yesno', 'bool', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (35, 7, 'user_allow_viewonline', 'Display Online Status', 1, 7, 0, 1, 'yesno', 'bool', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (36, 7, 'user_allow_viewemail', 'Display Email Online', 1, 8, 0, 1, 'yesno', 'bool', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (37, 7, 'user_allow_newsletter', 'Receive Newsletters and Site News by Email', 1, 9, 0, 1, 'yesno', 'bool', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (38, 7, 'user_sig', 'Signature', 1, 10, 0, 1024, 'bbcode', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0),
  (39, 7, 'user_from', 'You Are From Text', 1, 11, 0, 100, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '0', 0)");
}

//TODO: this is currently only for testing!
// Remove OLD entries of plugin setting "profile_page" first:
$DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."phrases WHERE pluginid = 11 AND varname LIKE 'select_profile_page_%'");
$DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."pluginsettings WHERE pluginid = 11 AND title = 'user_editable_fields'");
/*
// Add plugin phrases for all field names
// In addition build multi-select-entries for plugin setting named "profile_page"!
$fields_select = '';
$getitems = $DB->query('SELECT fieldname, fieldlabel FROM {users_fields} ORDER BY fieldnum');
while($item = $DB->fetch_array($getitems))
{
  // using special prefix "select_profile_page_" which will also be used for admin side!
  $phrase_name = strtolower(preg_replace('/[^0-9a-zA-Z_]/', '_', $item['fieldname']));
  $phrase_id = 'select_profile_page_'.$phrase_name;
  InsertPhrase(11, $phrase_id, $item['fieldlabel']);
  $fields_select .= $phrase_name.'|'.$item['fieldlabel']."\r\n";
}
InsertPluginSetting(11, 'Profile Page', 'User Editable Fields', 'Please select all fields which
  are available to the user on the User Control Panel plugin page for editing:<br />
  Use <strong>CTRL+Click</strong> to select/deselect individual fields.',
  "select-multi:\r\n".$fields_select, '', 50, true);
*/
// Add "countries" table for DB lookups
$DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."countries (
  `code` char(2) NOT NULL default '',
  `name` char(32) NOT NULL default '',
  PRIMARY KEY (`code`),
  KEY (`name`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$count = $DB->query_first('SELECT COUNT(*) FROM '.PRGM_TABLE_PREFIX.'countries');
if(empty($count[0]))
{
  $DB->query("INSERT INTO ".PRGM_TABLE_PREFIX."countries (`code`, `name`)
  VALUES
  ('ac','Ascencion Island'),
  ('ad','Andorra'),
  ('ae','United Arab Emirates'),
  ('af','Afghanistan'),
  ('ag','Antigua and Barbuda'),
  ('ai','Anguilla'),
  ('al','Albania'),
  ('am','Armenia'),
  ('an','Netherlands Antilles'),
  ('ao','Angola'),
  ('aq','Antarctica'),
  ('ar','Argentina'),
  ('as','American Samoa'),
  ('at','Austria'),
  ('au','Australia'),
  ('aw','Aruba'),
  ('ax','Aland Islands'),
  ('az','Azerbaijan'),
  ('ba','Bosnia and Herzegovina'),
  ('bb','Barbados'),
  ('bd','Bangladesh'),
  ('be','Belgium'),
  ('bf','Burkina Faso'),
  ('bg','Bulgaria'),
  ('bh','Bahrain'),
  ('bi','Burundi'),
  ('bj','Benin'),
  ('bm','Bermuda'),
  ('bn','Brunei Darussalam'),
  ('bo','Bolivia'),
  ('br','Brazil'),
  ('bs','Bahamas'),
  ('bt','Bhutan'),
  ('bv','Bouvet Island'),
  ('bw','Botswana'),
  ('by','Belarus'),
  ('bz','Belize'),
  ('ca','Canada'),
  ('cc','Cocos Islands'),
  ('cd','Congo'),
  ('cf','Central African Republic'),
  ('cg','Congo'),
  ('ch','Switzerland'),
  ('ci','Ivory Coast'),
  ('ck','Cook Islands'),
  ('cl','Chile'),
  ('cm','Cameroon'),
  ('cn','China'),
  ('co','Colombia'),
  ('cr','Costa Rica'),
  ('cs','Czechoslovakia'),
  ('cu','Cuba'),
  ('cv','Cape Verde'),
  ('cx','Christmas Island'),
  ('cy','Cyprus'),
  ('cz','Czech Republic'),
  ('de','Germany'),
  ('dj','Djibouti'),
  ('dk','Denmark'),
  ('dm','Dominica'),
  ('do','Dominican Republic'),
  ('dz','Algeria'),
  ('ec','Ecuador'),
  ('ee','Estonia'),
  ('eg','Egypt'),
  ('eh','Western Sahara'),
  ('er','Eritrea'),
  ('es','Spain'),
  ('et','Ethiopia'),
  ('fi','Finland'),
  ('fj','Fiji'),
  ('fk','Falkland Islands'),
  ('fm','Micronesia'),
  ('fo','Faroe Islands'),
  ('fr','France'),
  ('fx','France, Metropolitan'),
  ('ga','Gabon'),
  ('gb','Great Britain'),
  ('gd','Grenada'),
  ('ge','Georgia'),
  ('gf','French Guiana'),
  ('gg','French Guiana'),
  ('gh','Ghana'),
  ('gi','Gibraltar'),
  ('gl','Greenland'),
  ('gm','Gambia'),
  ('gn','Guinea'),
  ('gp','Guadeloupe'),
  ('gq','Equatorial Guinea'),
  ('gr','Greece'),
  ('gs','S. Georgia and S. Sandwich Islands'),
  ('gt','Guatemala'),
  ('gu','Guam'),
  ('gw','Guinea-Bissau'),
  ('gy','Guyana'),
  ('hk','Hong Kong'),
  ('hm','Heard and McDonald Islands'),
  ('hn','Honduras'),
  ('hr','Croatia'),
  ('ht','Haiti'),
  ('hu','Hungary'),
  ('id','Indonesia'),
  ('ie','Ireland'),
  ('il','Israel'),
  ('im','Isle of Man'),
  ('in','India'),
  ('io','British Indian Ocean Territory'),
  ('iq','Iraq'),
  ('ir','Iran'),
  ('is','Iceland'),
  ('it','Italy'),
  ('jm','Jamaica'),
  ('jo','Jordan'),
  ('jp','Japan'),
  ('ke','Kenya'),
  ('kg','Kyrgyzstan'),
  ('kh','Cambodia'),
  ('ki','Kiribati'),
  ('km','Comoros'),
  ('kn','Saint Kitts and Nevis'),
  ('ko','Kosovo'),
  ('kp','North Korea'),
  ('kr','South Korea'),
  ('kw','Kuwait'),
  ('ky','Cayman Islands'),
  ('kz','Kazakhstan'),
  ('la','Laos'),
  ('lb','Lebanon'),
  ('lc','Saint Lucia'),
  ('li','Liechtenstein'),
  ('lk','Sri Lanka'),
  ('lr','Liberia'),
  ('ls','Lesotho'),
  ('lt','Lithuania'),
  ('lu','Luxembourg'),
  ('lv','Latvia'),
  ('ly','Libya'),
  ('ma','Morocco'),
  ('mc','Monaco'),
  ('md','Moldova'),
  ('me','Montenegro'),
  ('mg','Madagascar'),
  ('mh','Marshall Islands'),
  ('mk','Macedonia'),
  ('ml','Mali'),
  ('mm','Myanmar'),
  ('mn','Mongolia'),
  ('mo','Macau'),
  ('mp','Northern Mariana Islands'),
  ('mq','Martinique'),
  ('mr','Mauritania'),
  ('ms','Montserrat'),
  ('mt','Malta'),
  ('mu','Mauritius'),
  ('mv','Maldives'),
  ('mw','Malawi'),
  ('mx','Mexico'),
  ('my','Malaysia'),
  ('mz','Mozambique'),
  ('na','Namibia'),
  ('nc','New Caledonia'),
  ('ne','Niger'),
  ('nf','Norfolk Island'),
  ('ng','Nigeria'),
  ('ni','Nicaragua'),
  ('nl','The Netherlands'),
  ('no','Norway'),
  ('np','Nepal'),
  ('nr','Nauru'),
  ('nt','Neutral Zone'),
  ('nu','Niue'),
  ('nz','New Zealand'),
  ('om','Oman'),
  ('pa','Panama'),
  ('pe','Peru'),
  ('pf','French Polynesia'),
  ('pg','Papua New Guinea'),
  ('ph','Philippines'),
  ('pk','Pakistan'),
  ('pl','Poland'),
  ('pm','St. Pierre and Miquelon'),
  ('pn','Pitcairn'),
  ('pr','Puerto Rico'),
  ('ps','Palestine'),
  ('pt','Portugal'),
  ('pw','Palau'),
  ('py','Paraguay'),
  ('qa','Qatar'),
  ('re','Reunion'),
  ('ro','Romania'),
  ('rs','Serbia'),
  ('ru','Russian Federation'),
  ('rw','Rwanda'),
  ('sa','Saudi Arabia'),
  ('sb','Solomon Islands'),
  ('sc','Seychelles'),
  ('sd','Sudan'),
  ('se','Sweden'),
  ('sg','Singapore'),
  ('sh','St. Helena'),
  ('si','Slovenia'),
  ('sj','Svalbard and Jan Mayen Islands'),
  ('sk','Slovak Republic'),
  ('sl','Sierra Leone'),
  ('sm','San Marino'),
  ('sn','Senegal'),
  ('so','Somalia'),
  ('sr','Suriname'),
  ('st','Sao Tome and Principe'),
  ('su','USSR (former)'),
  ('sv','El Salvador'),
  ('sy','Syria'),
  ('sz','Swaziland'),
  ('tc','Turks and Caicos Islands'),
  ('td','Chad'),
  ('tf','French Southern Territories'),
  ('tg','Togo'),
  ('th','Thailand'),
  ('tj','Tajikistan'),
  ('tk','Tokelau'),
  ('tm','Turkmenistan'),
  ('tn','Tunisia'),
  ('to','Tonga'),
  ('tp','East Timor'),
  ('tr','Turkey'),
  ('tt','Trinidad and Tobago'),
  ('tv','Tuvalu'),
  ('tw','Taiwan'),
  ('tz','Tanzania'),
  ('ua','Ukraine'),
  ('ug','Uganda'),
  ('uk','United Kingdom'),
  ('um','US Minor Outlying Islands'),
  ('us','United States'),
  ('uy','Uruguay'),
  ('uz','Uzbekistan'),
  ('va','Vatican'),
  ('vc','Saint Vincent and the Grenadines'),
  ('ve','Venezuela'),
  ('vg','British Virgin Islands'),
  ('vi','U.S. Virgin Islands'),
  ('vn','Viet Nam'),
  ('vu','Vanuatu'),
  ('wf','Wallis and Futuna Islands'),
  ('ws','Samoa'),
  ('ye','Yemen'),
  ('yt','Mayotte'),
  ('yu','Serbia and Montenegro'),
  ('za','South Africa'),
  ('zm','Zambia'),
  ('zr','Zaire'),
  ('zw','Zimbabwe')
  ");
}
$DB->query('ALTER TABLE '.PRGM_TABLE_PREFIX.'countries CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');

sd_DisplaySetupMessage('<h4>Upgrade 3.3.0 finished.</h4>');
