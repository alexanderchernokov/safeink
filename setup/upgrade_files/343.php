<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

$DB->query('UPDATE '.PRGM_TABLE_PREFIX."usersystems SET dbname = '' WHERE name = 'Subdreamer'");

// Module "Bad Behavior" table:
$DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."bad_behavior (
`id`              INT(11) UNSIGNED NOT NULL auto_increment,
`ip`              TEXT     collate utf8_unicode_ci NOT NULL,
`date`            DATETIME NOT NULL default '0000-00-00 00:00:00',
`request_method`  TEXT collate utf8_unicode_ci NOT NULL,
`request_uri`     TEXT collate utf8_unicode_ci NOT NULL,
`server_protocol` TEXT collate utf8_unicode_ci NOT NULL,
`http_headers`    TEXT collate utf8_unicode_ci NOT NULL,
`user_agent`      TEXT collate utf8_unicode_ci NOT NULL,
`request_entity`  TEXT collate utf8_unicode_ci NOT NULL,
`key`             TEXT collate utf8_unicode_ci NOT NULL,
INDEX (`ip`(15)),
INDEX (`user_agent`(10)),
PRIMARY KEY (`id`))
ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

// Modules and -settings tables:
$DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."modules (
`moduleid`              INT(10)     UNSIGNED NOT NULL DEFAULT '0',
`name`                  VARCHAR(64) collate utf8_unicode_ci NOT NULL DEFAULT  '',
`displayname`           VARCHAR(64) collate utf8_unicode_ci NOT NULL DEFAULT  '',
`enabled`               TINYINT(1)  UNSIGNED NOT NULL DEFAULT 1,
`load_admin`            TINYINT(1)  UNSIGNED NOT NULL DEFAULT 1,
`function_code`         VARCHAR(50) collate utf8_unicode_ci NOT NULL DEFAULT  '',
`version`               VARCHAR(10) collate utf8_unicode_ci NOT NULL DEFAULT  '',
`modulepath`            VARCHAR(64) collate utf8_unicode_ci NOT NULL DEFAULT  '',
`settingspath`          VARCHAR(64) collate utf8_unicode_ci NOT NULL DEFAULT  '',
`settings`              TEXT        collate utf8_unicode_ci NOT NULL,
PRIMARY KEY (moduleid))
ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."modulesettings (
`moduleid`     INT(10)      UNSIGNED NOT NULL DEFAULT 0,
`settingid`    VARCHAR(30)           collate utf8_unicode_ci NOT NULL DEFAULT '',
`editable`     TINYINT      UNSIGNED NOT NULL DEFAULT '1',
`enabled`      TINYINT      UNSIGNED NOT NULL DEFAULT '1',
`description`  MEDIUMTEXT            collate utf8_unicode_ci NOT NULL,
`input`        VARCHAR(50)           collate utf8_unicode_ci NOT NULL,
`value`        MEDIUMTEXT            collate utf8_unicode_ci NOT NULL,
`displayorder` INT(10)      UNSIGNED NOT NULL DEFAULT 0,
`eventproc`    VARCHAR(80)           collate utf8_unicode_ci NOT NULL DEFAULT '',
PRIMARY KEY (moduleid, settingid),
KEY (displayorder))
ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

// Modules data:
if(!$DB->query_first('SELECT moduleid FROM '.PRGM_TABLE_PREFIX.'modules WHERE moduleid = 200'))
{
  $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX."modules
  (`moduleid`, `name`, `displayname`, `enabled`, `load_admin`, `function_code`, `version`, `modulepath`, `settingspath`, `settings`) VALUES
  (200, 'bad-behavior', 'Bad-Behavior', 1, 0, 'antispam', '2.1.15', 'bad-behavior.php', 'bad-behavior.admin.php', '')");
}
else
{
  $DB->query('UPDATE '.PRGM_TABLE_PREFIX."modules SET version = '2.1.15' WHERE moduleid = 200");
}

if(!$DB->query_first('SELECT moduleid FROM '.PRGM_TABLE_PREFIX."modulesettings WHERE moduleid = 200 AND settingid = 'enabled'"))
{
  $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX."modulesettings
  (`moduleid`, `settingid`, `editable`, `enabled`, `description`, `input`, `value`, `displayorder`, `eventproc`) VALUES
  (200, 'enabled',       1, 1, 'Enable this module on the frontpage (default: No):<br />Enabling this module may help to avoid any spam bots/posters and therefore raising the security of your site and users.', 'yesno', '0', 200, ''),
  (200, 'log_table',     0, 1, 'Name for the logging table:', 'text', '{bad_behavior}', 210, ''),
  (200, 'verbose',       1, 1, 'Verbose HTTP request logging:<br />If enabled, not only banned page requests will be logged, but also allowed ones.', 'yesno', '1', 215, ''),
  (200, 'display_stats', 1, 1, 'Display statistics in site footer:', 'yesno', '0', 220, ''),
  (200, 'strict',        1, 1, 'Strict checking (blocks more spam but may block some people):', 'yesno', '0', 230, ''),
  (200, 'httpbl_key',    1, 1, 'Project HoneyPot API Key (default: empty; visit <a href=\"http://www.projecthoneypot.org/httpbl_api.php\" target=\"_blank\">http://www.projecthoneypot.org/httpbl_api.php</a>)', 'text', '', 235, ''),
  (200, 'reverse_proxy',    1, 1, 'Reverse Proxy (default: No) Only Yes if site receives connections from reverse proxy.<br />Visit http://bad-behavior.ioerror.us/2011/01/25/bad-behavior-2-1-9/ for details.', 'yesno', '0', 240, ''),
  (200, 'reverse_proxy_header', 1, 1, 'Reverse Proxy Header (default: X-Forwarded-For)', 'text', 'X-Forwarded-For', 242, ''),
  (200, 'reverse_proxy_addresses', 1, 1, 'Reverse Proxy Addresses (separate multiple entries by single comma)', 'text', '', 244, ''),
  (200, 'onpageheader',  0, 1, '', 'event', '1', 250, 'bb2_insert_head'),
  (200, 'onpagefooter',  0, 1, '', 'event', '1', 255, 'bb2_insert_stats')
  ");
}
if(!$DB->query_first('SELECT settingid FROM '.PRGM_TABLE_PREFIX."modulesettings WHERE moduleid = 200 AND settingid = 'logging'"))
{
  $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX."modulesettings
  (`moduleid`, `settingid`, `editable`, `enabled`, `description`, `input`, `value`, `displayorder`, `eventproc`) VALUES
  (200, 'logging', 1, 1, 'Enable logging at all (default: Yes):<br />With Yes, site access will be logged in a separate table (default: sd_bad_behavior). If set to No, logging is completely disabled and below Verbose option will be ignored, too.', 'yesno', '0', 212, '')");
}
if(!$DB->query_first('SELECT settingid FROM '.PRGM_TABLE_PREFIX."modulesettings WHERE moduleid = 200 AND settingid = 'httpbl_whitelisted_groups'"))
{
  $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX."modulesettings
  (`moduleid`, `settingid`, `editable`, `enabled`, `description`, `input`, `value`, `displayorder`, `eventproc`) VALUES
  (200, 'httpbl_whitelisted_groups', 1, 1, 'Whitelist the selected usergroups for Project HoneyPot (default: none):<br />Select the usergroups that shall override a Project Honeypot blacklisting, e.g. Customers. Do not use this for Guests!', 'usergroups', '', 238, '')");
}
if(!$DB->query_first('SELECT settingid FROM '.PRGM_TABLE_PREFIX."modulesettings WHERE moduleid = 200 AND settingid = 'httpbl_threat_level'"))
{
  $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX."modulesettings
  (`moduleid`, `settingid`, `editable`, `enabled`, `description`, `input`, `value`, `displayorder`, `eventproc`) VALUES
  (200, 'httpbl_threat_level', 1, 1, 'Threat level threshold upon for Project HoneyPot to be triggered (default: 25):<br />
  The default setting is usually fine and should only be changed when absolutely necessary!', 'text', '25', 236, '')");
}
if(!$DB->query_first('SELECT settingid FROM '.PRGM_TABLE_PREFIX."modulesettings WHERE moduleid = 200 AND settingid = 'offsite_forms'"))
{
  $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX."modulesettings
  (`moduleid`, `settingid`, `editable`, `enabled`, `description`, `input`, `value`, `displayorder`, `eventproc`) VALUES
  (200, 'offsite_forms', 1, 1, 'Allow forms submitted with referer not pointing to your site (default: No):<br />
  The default setting is usually fine and should only be changed when users cannot login or submit anything!', 'yesno', 0, 213, '')");
}
if(!$DB->query_first('SELECT settingid FROM '.PRGM_TABLE_PREFIX."modulesettings WHERE moduleid = 200 AND settingid = 'httpbl_key'"))
{
  $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX."modulesettings
  (`moduleid`, `settingid`, `editable`, `enabled`, `description`, `input`, `value`, `displayorder`, `eventproc`) VALUES
  (200, 'httpbl_key', 1, 1, 'Project HoneyPot API Key (default: empty; visit <a href=\"http://www.projecthoneypot.org/httpbl_api.php\" target=\"_blank\">http://www.projecthoneypot.org/httpbl_api.php</a>)', 'text', '', 235, '')");
}
InsertAdminPhrase(0, 'filter_ip_hint', 'Filter this IP', 0);
InsertAdminPhrase(0, 'filter_username_hint', 'Filter this Username', 0);
InsertAdminPhrase(0, 'ip_tools_title', 'IP Tools', 0);
InsertAdminPhrase(0, 'menu_settings_modules', 'Modules', 0);
InsertAdminPhrase(0, 'hint_debug_mode', 'Debug mode is ON', 0);
InsertAdminPhrase(0, 'syslog_error', 'Error', 0);
InsertAdminPhrase(0, 'syslog_information', 'Information', 0);
InsertAdminPhrase(0, 'syslog_warning', 'Warning', 0);

InsertAdminPhrase(0, 'mod_bb2_allowed', 'Allowed', 0);
InsertAdminPhrase(0, 'mod_bb2_blocked', 'Blocked', 0);
InsertAdminPhrase(0, 'mod_bb2_clear_log', 'Clear Log', 0);
InsertAdminPhrase(0, 'mod_bb2_clear_log_prompt', 'Are you sure you wish to clear out all entries (cannot be undone)?', 0);
InsertAdminPhrase(0, 'mod_bb2_delete', 'Delete', 0);
InsertAdminPhrase(0, 'mod_bb2_delete_ip', 'Delete IP', 0);
InsertAdminPhrase(0, 'mod_bb2_delete_prompt', 'Are you sure you wish to remove the selected entries (cannot be undone)?', 0);
InsertAdminPhrase(0, 'mod_bb2_distinct', 'Distinct', 0);
InsertAdminPhrase(0, 'mod_bb2_entries', 'Entries', 0);
InsertAdminPhrase(0, 'mod_bb2_refresh', 'Refresh', 0);
InsertAdminPhrase(0, 'mod_bb2_show_distinct', 'Show Distinct IP\'s', 0);
InsertAdminPhrase(0, 'mod_bb2_search', 'Search', 0);
InsertAdminPhrase(0, 'mod_bb2_search_hint', 'Search performed on IP, URI and Request', 0);
InsertAdminPhrase(0, 'mod_bb2_visitor_message', 'Visitor Message', 0);

$DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."users_likes(
`id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`pluginid`        INT(11) UNSIGNED NOT NULL DEFAULT 0,
`objectid`        INT(11) UNSIGNED NOT NULL DEFAULT 0,
`liked_type`      VARCHAR(20) collate utf8_unicode_ci NOT NULL DEFAULT '',
`userid`          INT(11) UNSIGNED NOT NULL DEFAULT 0,
`username`        VARCHAR(250) collate utf8_unicode_ci NOT NULL DEFAULT '',
`liked_date`      INT(11) UNSIGNED NOT NULL DEFAULT 0,
`liked_userid`    INT(11) UNSIGNED NOT NULL DEFAULT 0,
`ip_address`      VARCHAR(40) collate utf8_unicode_ci NOT NULL DEFAULT '',
INDEX (`pluginid`,`objectid`),
INDEX (`userid`),
PRIMARY KEY (`id`))
ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'msg_messages', 'is_bcc', 'TINYINT(1)', 'UNSIGNED NOT NULL DEFAULT 0');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'msg_messages', 'org_recipientid', 'INT(11)', 'UNSIGNED NOT NULL DEFAULT 0');

$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'categories', 'navigation_flag', 'INT(8)', 'UNSIGNED NOT NULL DEFAULT 0');

DeleteAdminPhrase(0,'comments_no',4);
DeleteAdminPhrase(0,'comments_yes',4);
DeletePhrase(1,'lbl_latest_comment_by');
DeletePhrase(1,'rating');
DeletePhrase(1,'rating2');

InsertAdminPhrase(0,'menu_data','Data',0);
InsertAdminPhrase(0,'menu_tags','Tags',0);
InsertAdminPhrase(0,'menu_tags_view_tags','View Tagged Data',0);
InsertAdminPhrase(0,'menu_tags_view_tag_settings','Configure Tags',0);
InsertAdminPhrase(0,'menu_tags_view_tags_by_plugin','View Tags by Plugin',0);
InsertAdminPhrase(0,'menu_tags_view_global_tags','View Global Tags',0);
InsertAdminPhrase(0,'common_usergroup','Usergroup',0);

InsertAdminPhrase(0,'pages_appenkeywords_descr',
  'Should the keywords and description entered here overwrite the globally defined
  keywords/description in your Main Settings or be appended to them?', 1);

InsertAdminPhrase(0,'page_navigation_flag','Display page in navigation?',1);
InsertAdminPhrase(0,'page_navigation_flag_descr',
  'Include this page in all, top-/bottom-related or no navigation variables (default: All)?<br />'.
  'The Skins layout variables, which are named like <strong>[NAVIGATION-...]</strong>,'.
  ' will include the current page depending on this setting.<br />'.
  '<strong>Examples:</strong><br />'.
  '[NAVIGATION] will include all pages that are NOT set to None.<br />'.
  '[NAVIGATION-TOP...] variable will NOT include pages that are set to either Bottom or None.<br />'.
  '[NAVIGATION-BOTTOM...] variable will NOT include pages that are set to either Top or None.<br />'.
  'Select an entry by using CTRL+Click to CTRL+Shift+Click.',1);
InsertAdminPhrase(0,'page_navigation_flag_opt_all', 'All',1);
InsertAdminPhrase(0,'page_navigation_flag_opt_top', 'Top', 1);
InsertAdminPhrase(0,'page_navigation_flag_opt_bottom', 'Bottom', 1);
InsertAdminPhrase(0,'page_navigation_flag_opt_none', 'None', 1);
InsertAdminPhrase(0,'edit_mobile_plugin_positions', 'Edit Mobile Plugin Positions', 1);

InsertAdminPhrase(0,'tags','Tags',4);
InsertAdminPhrase(0,'tags_allowed_usergroups_hint','Limit the tag to be used for e.g. tag auto-completion/selection to specific usergroup(s).<br />
  For non-selected usergroups this will only hide a tag when a user adds tags to a plugin item, but not from viewing existing tags (plugin items, tag clouds, tag lists).<br />
  Select any usergroup by clicking CTRL+Click to select or CTRL+Shift+Click to unselect.',4,true);
InsertAdminPhrase(0,'tags_allowed_objects','Allowed Objects',4);
InsertAdminPhrase(0,'tags_allowed_objects_descr','For specific plugins the permission to use a tag or prefix
  can be further restricted to one or more objects. An object here can be e.g. a subforum of the Forum plugin.',4);
InsertAdminPhrase(0,"tags_apply_filter","Apply Filter",4);
InsertAdminPhrase(0,"tags_approve_tag","Approve/Unapprove Tag:",4);
InsertAdminPhrase(0,"tags_approve_this_tag","Approve this tag?",4);
InsertAdminPhrase(0,"tags_approved","Approved",4);
InsertAdminPhrase(0,"tags_by_plugin","Tags by Plugin",4);
InsertAdminPhrase(0,"tags_censored","Censored",4);
InsertAdminPhrase(0,"tags_censored_hint","If a tag is censored, it cannot be added/used by regular users for the configured plugin (if one is specified) and will not appear in related tags listings or selections.<br />This will take into account the configured pluginid and tag type. This means, that a censored tag, which is not bound by a plugin and configured as a global tag, will prevent this tag to be used within any plugin.",4,true);
InsertAdminPhrase(0,"tags_clear_filter","Clear Filter",4);
InsertAdminPhrase(0,"tags_create_global_tag","Create Global Tag",4);
InsertAdminPhrase(0,"tags_create_tag","Create Tag",4);
InsertAdminPhrase(0,"tags_date","Date Created",4);
InsertAdminPhrase(0,"tags_date_asc","Oldest First",4);
DeleteAdminPhrase(0,"tags_date_desc",4);
InsertAdminPhrase(0,"tags_date_descending","Newest First",4);
InsertAdminPhrase(0,"tags_delete","Delete",4);
InsertAdminPhrase(0,"tags_delete_tag","Delete Tag:",4);
InsertAdminPhrase(0,"tags_delete_tags","Delete",4,true);
InsertAdminPhrase(0,"tags_delete_this_tag","Delete this tag?",4);
InsertAdminPhrase(0,"tags_edit_hint","A tag is recommended to be shorter than 30 characters and <strong>must</strong> only contain all latin alpha-numerics (a-z, A-Z,0-9), dash/hyphen character (\"-\"), but no blanks/spaces!<br />This is a requirement as it is used in browser URLs and to be search engine friendly.",4,true);
InsertAdminPhrase(0,"tags_edit_tag","Edit Tag",4);
InsertAdminPhrase(0,"tags_filter","Filter",4);
InsertAdminPhrase(0,"tags_filter_title","Filter Tags",4);
InsertAdminPhrase(0,"tags_global_tags","Global Tags",4);
InsertAdminPhrase(0,"tags_global_tip","<strong>Global Tags</strong> are customizable, site-wide tags of different types,<br />which can be used as either user-level tags, prefixes (for topics) or categories (for articles).",4);
InsertAdminPhrase(0,"tags_groups","Allowed Usergroups?",4);
InsertAdminPhrase(0,"tags_html_prefix","HTML Prefix",4);
InsertAdminPhrase(0,"tags_html_prefix_hint",
  "HTML code to be displayed as a prefix. Include <strong>[tagname]</strong> to display the name of tag itself in the output.<br />
  Note: only this instance of the prefix stores this code! Any applied prefix on the frontpage will only link to
  this prefix instance, i.e. you only need to change the code once here for all children of this prefix.",4, true);
InsertAdminPhrase(0,"tags_ip","IP",4);
InsertAdminPhrase(0,"tags_ip_asc","IP Address Asc",4);
InsertAdminPhrase(0,"tags_ip_desc","IP Address Desc",4);
InsertAdminPhrase(0,"tags_ipaddress","IP Address",4);
InsertAdminPhrase(0,"tags_item","Item:",4);
InsertAdminPhrase(0,"tags_latest_tags","Latest Tags",4);
InsertAdminPhrase(0,"tags_limit","Limit",4);
InsertAdminPhrase(0,"tags_no_tags_found","No Tags Found",4);
InsertAdminPhrase(0,"tags_not_censored","Not Censored",4);
InsertAdminPhrase(0,"tags_number_of_tags","Number of Tags",4);
InsertAdminPhrase(0,"tags_old_tags_first","Display Older Tags First",4);
InsertAdminPhrase(0,"tags_old_tags_first_desc","Display older tags first?",4);
InsertAdminPhrase(0,"tags_plugin","Plugin",4);
InsertAdminPhrase(0,"tags_plugin_hint","Limit appearance of this tag to a specific plugin OR leave it empty to be available to all plugins.<br />Note: if this tag is assigned to a specific item and the plugin is changed, the link to the item will be lost!",4);
InsertAdminPhrase(0,"tags_plugin_item","Plugin Item",4);
InsertAdminPhrase(0,"tags_plugin_name","Plugin Name",4);
InsertAdminPhrase(0,"tags_plugin_not_found","Plugin not found or not installed!",4);
InsertAdminPhrase(0,"tags_settings_updated","Tag Settings Updated",4);
InsertAdminPhrase(0,"tags_sort_by","Sort by",4);
InsertAdminPhrase(0,"tags_status","Status",4);
InsertAdminPhrase(0,"tags_tag","Tag:",4);
InsertAdminPhrase(0,"tags_tag2","Tag",4);
InsertAdminPhrase(0,"tags_tag_created","Tag created.",4);
InsertAdminPhrase(0,"tags_tag_deleted","Tag deleted.",4);
InsertAdminPhrase(0,"tags_tag_not_found","Tag not found!",4);
InsertAdminPhrase(0,"tags_tag_type_user","User defined",4);
InsertAdminPhrase(0,"tags_tag_settings","Tags Configuration",4);
InsertAdminPhrase(0,"tags_tag_asc","Tag A-Z",4);
DeleteAdminPhrase(0,"tags_tag_desc",4);
InsertAdminPhrase(0,"tags_tag_descending","Tag Z-A",4);
InsertAdminPhrase(0,"tags_tagtype_asc","Type A-Z",4);
DeleteAdminPhrase(0,"tags_tagtype_desc",4);
InsertAdminPhrase(0,"tags_tagtype_descending","Type Z-A",4);
InsertAdminPhrase(0,"tags_plugin_asc","Plugin A-Z",4);
DeleteAdminPhrase(0,"tags_plugin_desc",4);
InsertAdminPhrase(0,"tags_plugin_descending","Plugin Z-A",4);
InsertAdminPhrase(0,"tags_tag_type","Tag Type",4);
InsertAdminPhrase(0,"tags_tag_type_hint","<strong>Available Tag Types:</strong><br />
<strong>User defined</strong> = tag was added freely for a plugin's item (e.g. article, forum topic).<br />
<strong>Global Tag</strong> = entered here by an admin for global use across one or all plugins.<br />
<strong>Prefix</strong> = a HTML-enhanced tag added by an admin, which is usable by one or all plugins, e.g. to be displayed in front of a forum topic's name.<br />
<strong>Category</strong> = a category is used by e.g. articles to group several articles into a certain group: an article can belong to one category, but may have many tags. Example: a category named 'Programming' could have items with tags like 'HTML', 'Javascript', 'PHP' and so on.<br />",4);
InsertAdminPhrase(0,"tags_tag_type_user","User-added/Plugin-level",4);
InsertAdminPhrase(0,"tags_tag_type_global","Global Tag (admin maintained)",4);
InsertAdminPhrase(0,"tags_tag_type_prefix","Prefix (with HTML; admin maintained)",4,true);
InsertAdminPhrase(0,"tags_tag_type_category","Category (e.g. for Articles)",4);
InsertAdminPhrase(0,"tags_tag_type_others","Others",4);
InsertAdminPhrase(0,"tags_tag_type_admin_only","Global/Prefix/Category only",4);
InsertAdminPhrase(0,"tags_no_filter","No Filter",4);
InsertAdminPhrase(0,"tags_no_plugin_configured","No Plugin configured",4);
InsertAdminPhrase(0,"tags_tag_updated","Tag Updated",4);
InsertAdminPhrase(0,"tags_tags_deleted","Tags Deleted",4);
InsertAdminPhrase(0,"tags_uname_asc","Username Asc",4);
InsertAdminPhrase(0,"tags_uname_desc","Username Desc",4);
InsertAdminPhrase(0,"tags_unapproved","Unapproved",4);
InsertAdminPhrase(0,"tags_update_tag","Update Tag",4);
InsertAdminPhrase(0,"tags_user_name","Username:",4);
InsertAdminPhrase(0,"tags_username","User Name:",4);
InsertAdminPhrase(0,"tags_username2","Username",4);
InsertAdminPhrase(0,'users_email_ip', 'Email / IP', 5, true);
InsertAdminPhrase(0,'usergroup_submit_first', 'Please first submit any changes made to this usergroup!', 5);

InsertPhrase(1, 'msg_do_not_enter_anything','Do NOT enter anything here:');
InsertPhrase(1, 'msg_spam_trap_triggered','Spam form submission detected!');
InsertPhrase(1, 'msg_javascript_required','You must enable Javascript to use this functionality!');
InsertPhrase(1, 'pagination_goto','Goto page');
InsertPhrase(1, 'tags_title','Tags');
InsertPhrase(1, 'common_go','Go!');
InsertPhrase(1, 'common_refresh','Refresh');
InsertPhrase(1, 'common_reload','Reload');
InsertPhrase(1, 'common_search_tags','Search Tags');
InsertPhrase(1, 'ip_listed_on_blacklist', 'IP listed on blacklist:');

// Fix some UserCP default phrases:
InsertPhrase(11, 'profile_views', 'Profile Views:',true);
InsertPhrase(11, 'select_profile_page_user_allow_viewonline', 'Display Your Online Status', true);
InsertPhrase(11, 'select_profile_page_user_birthday', 'Birthday/Age', true);
InsertPhrase(11, 'select_profile_page_user_dateformat', 'Date Format', true);
InsertPhrase(11, 'select_profile_page_user_dst', 'Daylight Savings', true);
InsertPhrase(11, 'select_profile_page_user_facebook', 'Facebook Page URL', true);
InsertPhrase(11, 'select_profile_page_user_googletalk', 'Google Talk', true);
InsertPhrase(11, 'select_profile_page_user_pm_email', 'Private Message as Email', true);
InsertPhrase(11, 'select_profile_page_user_sig', 'Signature', true);
InsertPhrase(11, 'select_profile_page_user_twitter', 'Twitter Page URL', true);
DeletePhrase(11, 'option_user_birthday_mode_age__1');
DeletePhrase(11, 'option_user_gender_not_specified__1');

InsertPhrase(12, 'message_js_required','Please enable Javascript for registration and reload this page, thank you.');
InsertPhrase(12, 'user_activation_required', 'User activation requrired:');
InsertPhrase(12, 'admin_activation_subject', 'Activation of new user requrired');
InsertPhrase(12, 'admin_activation_message', 'Activation of new user requrired');
InsertPhrase(12, 'checking_hint', 'Checking...');
InsertPhrase(12, 'password_unsecure', 'Password unsecure (min. 4 different characters)',true);
InsertPhrase(12, 'email_available', 'Email available');
InsertPhrase(12, 'email_unavailable', 'Email not available');
DeletePhrase(12, 'ip_listed_on_blacklist');

$tbl = PRGM_TABLE_PREFIX.'adminphrases';
$DB->query('UPDATE '.$tbl." SET defaultphrase = 'Enter the maximum username length (13..64):' WHERE pluginid = 12 AND varname = 'max_username_length_descr'");
$DB->query('UPDATE '.$tbl." SET defaultphrase = 'Enter the minimum username length (3..20):' WHERE pluginid = 12 AND varname = 'min_username_length_descr'");
$DB->query('UPDATE '.$tbl." SET defaultphrase = 'Enter the minimum password length (5..20):' WHERE pluginid = 12 AND varname = 'min_password_length_descr'");

$tmp = 'UPDATE '.PRGM_TABLE_PREFIX.'pluginsettings SET ';
$DB->query($tmp."displayorder = 50 WHERE pluginid = 12 AND title = 'require_email_activation'");
$DB->query($tmp."displayorder = 55 WHERE pluginid = 12 AND title = 'require_admin_activation'");
$DB->query($tmp."displayorder = 60 WHERE pluginid = 12 AND title = 'new_registration_admin_email'");

$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'tags', 'tag_ref_id', 'INT(11) UNSIGNED NOT NULL DEFAULT 0');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'tags', 'censored', 'INT(1) UNSIGNED NOT NULL DEFAULT 0');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'tags', 'allowed_groups', 'VARCHAR(200) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'tags', 'html_prefix', "TEXT COLLATE utf8_unicode_ci NULL");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'tags', 'allowed_object_ids', 'VARCHAR(200) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");

InsertPluginSetting(6, 'contact_form_settings', 'Reject Words',
  'Enter a list of words which will cause the message to be rejected.<br />
  Each word must be on a single line (may contain blanks), no wildcards. Search is case-insensitive.<br />
  This may assist in avoiding spam messages to be sent in case the plugin is accessible by Guests.', 'textarea','', 20);
InsertPluginSetting(6, 'contact_form_settings', 'Enable SFS AntiSpam',
  'Enable the checking of the senders email address and IP address against the <strong>StopForumSpam</strong> (SFS) database (default: No)?<br />
  Note: this requires to query their database remotely and may not be possible on all servers, please try it out.<br />
  Please visit <a href="http://www.stopforumspam.com" target="_blank"><strong>StopForumSpam.com</strong></a> for further information
  and terms of usage. If enabled, please consider supporting them by donating to their project.', 'yesno','0', 30);
InsertPluginSetting(6, 'contact_form_settings', 'Enable Blocklist Checks',
  'Enable checking of IP address against one or more <strong>blocklists</strong> (default: Disabled)?<br />
  Note: this requires to query the lists remotely and may not be technically possible on all servers, please try it out
  and check frequently your System Log (Settings page) for warnings or error messages.<br />
  Please visit any used list <strong>BEFORE</strong> enabling and consult their respective terms of use/usage policies.<br />
  <strong>Not obeying their rules may get your server IP blacklisted or banned!</strong>',
  '', '0', 40);

InsertPluginSetting(10, 'user_login_panel_settings', 'Enable Blocklist Checks',
  'Enable checking of IP address against one or more <strong>blocklists</strong> (default: Disabled)?<br />
  Note: this requires to query the lists remotely and may not be technically possible on all servers, please try it out
  and check frequently your System Log (Settings page) for warnings or error messages.<br />
  Please visit any used list <strong>BEFORE</strong> enabling and consult their respective terms of use/usage policies.<br />
  <strong>Not obeying their rules may get your server IP blacklisted or banned!</strong>',
  '', '0', 100);

InsertPluginSetting(12, 'user_registration_settings', 'New Registration Email Subject',
  'Please enter here the email subject for the email message to admin for user activation:', 'text',
  'New user [username] - activation required ([date])', 70);
InsertPluginSetting(12, 'user_registration_settings', 'New Registration Email Message',
  'Please enter here the actual message text (as HTML) for the <strong>email body</strong> to the admin, which may contain the following placeholders:<br />
<strong>[date]</strong> = date of email, <strong>[username]</strong> = user name,
<strong>[siteurl]</strong> = website url, <strong>[sitename]</strong> = website title,
<strong>[email]</strong> = user email', 'wysiwyg',
  '<strong>A new user registered on [date] on [sitename] requires approval:</strong><br /><br />
Username: <strong>[username]</strong><br />
Email: <strong>[email]</strong><br />
IP Address: <strong>[ipaddress]</strong><br /><br />
Registrations are configured to require an administrator to review and manually approve all new users.<br /><br />
Please visit the site admin area at <a href="[siteurl]"><strong>[sitename]</strong></a> to review the user.<br />', 80);

InsertPluginSetting(12, 'prevention_options', 'Auto-Block URIs',
  'Maintain a list of URI snippets (partially contained) that will trigger the page load to stop.<br />
  By default there are some known entries to filter spammers immediately.<br />
  Spammers/attackers/bots frequently submit fake data by using links that are known to 3rd party
  forums, but not the CMS, in order to add fake user registrations without using the
  CMS registration plugin itself or trigger user data changes.<br />
  Enter one entry per line with NO blanks in it.', 'textarea',
  "?agreed=true\r\nregister.php?do=addmember\r\nprofile.php?do=editsignature\r\nprofile.php?do=updatesignature\r\nregister.php?do=signup\r\nshowthread.php?p=\r\n/index.php/external.php?forumids=", 80);

$oldval = $DB->query_first("SELECT value FROM ".PRGM_TABLE_PREFIX."pluginsettings WHERE pluginid = 12 AND title = 'enable_sfs_antispam' ORDER BY settingid LIMIT 1");
$oldval = empty($oldval['value'])?0:1;
$DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."pluginsettings WHERE pluginid = 12 AND title = 'enable_sfs_antispam'");
InsertPluginSetting(12, 'prevention_options', 'Enable SFS AntiSpam',
  'Enable the checking of the registrants email address and IP address against the <strong>StopForumSpam</strong> (SFS) database (default: No)?<br />
  Note: this requires to query their database remotely and may not be possible on all servers, please try it out.<br />
  Please visit <a href="http://www.stopforumspam.com" target="_blank"><strong>StopForumSpam.com</strong></a> for further information
  and usage policy. If enabled, please consider supporting them by donating to their project.',
  'yesno', $oldval, 90);

$oldval = $DB->query_first("SELECT value FROM ".PRGM_TABLE_PREFIX."pluginsettings WHERE pluginid = 12 AND title = 'enable_blocklist_checks' ORDER BY settingid LIMIT 1");
$oldval = empty($oldval['value'])?'0':(string)$oldval['value'];
$DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."pluginsettings WHERE pluginid = 12 AND title = 'enable_blocklist_checks'");
InsertPluginSetting(12, 'prevention_options', 'Enable Blocklist Checks',
  'Enable checking of IP address against one or more <strong>blocklists</strong> (default: Disabled)?<br />
  Note: this requires to query the lists remotely and may not be technically possible on all servers, please try it out
  and check frequently your System Log (Settings page) for warnings or error messages.<br />
  Please visit any used list <strong>BEFORE</strong> enabling and consult their respective terms of use/usage policies.<br />
  <strong>Not obeying their rules may get your server IP blacklisted or banned!</strong>',
  '', $oldval, 95);
unset($oldval);
$DB->query('UPDATE '.PRGM_TABLE_PREFIX."pluginsettings SET groupname = 'prevention_options'".
           " WHERE pluginid = 12 AND title IN ('auto_block_uris','enable_sfs_antispam','enable_blocklist_checks')");

/*
InsertPluginSetting(12, 'user_registration_settings', 'New Registration Activation Message',
  'Please enter here the actual message text (as HTML) for the <strong>email body</strong> to the user,
  when an admin activates the user account while admin-activation is active.<br />
  This may contain the following placeholders:<br />
<strong>[date]</strong> = date of registration, <strong>[username]</strong> = user name,
<strong>[siteurl]</strong> = website url, <strong>[sitename]</strong> = website title,
<strong>[email]</strong> = user email', 'wysiwyg',
  'Hello [username],<br />on [date] you registered with our website, thank you!<br /><br />
Your registration has been reviewed and your user account activated.<br />
Please visit our <a href="[siteurl]"><strong>[sitename]</strong></a> to login and participate.<br /><br />
Best regards<br />', 80);
*/
InsertAdminPhrase(0, 'view_subpages', 'View Subpages', 1);

InsertAdminPhrase(0, 'comments_ip_asc', 'IP address Asc', 4);
InsertAdminPhrase(0, 'comments_ip_desc', 'IP address Desc', 4);
InsertAdminPhrase(0, 'comments_uname_asc', 'Username Asc', 4);
InsertAdminPhrase(0, 'comments_uname_desc', 'Username Desc', 4);

InsertAdminPhrase(0, 'users_welcome_message', 'Email Welcome Message', 5);
InsertAdminPhrase(0, 'users_send_welcome_failed', 'Failed to send the welcome message to user.', 5);

InsertAdminPhrase(0, 'menu_settings_cache', 'Cache', 0);
InsertAdminPhrase(0, 'menu_settings_search_tags', 'Search and Tags', 0);
InsertAdminPhrase(0, 'settings_search_tags', 'Search and Tags', 0);
InsertMainSetting('search_results_page', 'settings_search_tags', 'Page to display Search Results',
  'Select the page which is to be used to redirect to for displaying <strong>search results</strong>.<br />
  This is important to be configured as this basically defines the layout/design of the search
  results page and the browser URL.<br />
  <strong>Requirements:</strong><br />
  * Page (seo) title should be short like "search" as that is important for external search engines.<br />
  * Add the core "Search Form" and "Search Results" plugins to that page. The Search Results should not
  be added to any other page.',
  'pageselect', '', 10);
InsertMainSetting('tag_results_page', 'settings_search_tags', 'Page to display Tag Results',
  'Select the page which is to be used to redirect to for displaying <strong>tag results</strong>.<br />
  This is important to be configured as this basically defines the layout/design of
  the tag results page and the browser URL.<br />
  <strong>Requirements:</strong><br />
  * Page (seo) title should be short like "tag" as that is important for external search engines.',
  'pageselect', '', 20);

InsertAdminPhrase(3, 'page_targeting', 'Page Targeting', 2);
DeleteAdminPhrase(3, 'page_targeting_descr', 2);
DeleteAdminPhrase(3, 'page_targeting_desc', 2);
InsertAdminPhrase(3, 'page_targeting_desc', 'Display only the latest articles of the page which the latest articles plugin resides in.<br />Selecting "Yes" will disable the "Page Targeting" setting.',2);

InsertAdminPhrase(0, 'settings_export_defaults', 'Export Default Phrases', 7);
InsertAdminPhrase(0, 'settings_export_english', 'This will export all currently existing default phrases in English.', 7);
InsertAdminPhrase(0, 'export_default_phrases', 'Export Default Phrases', 7);
InsertAdminPhrase(0, 'error_no_lang_specified', '<strong>Error:</strong> Please enter a language name and a version!', 7);

InsertMainSetting('seo_max_title_length', 'settings_seo_settings', 'SEO Maximum Title Length',
  'The maximum length (in characters) of generated SEO titles (default: 30, valid: 10 - 100).<br />
  Depending on configured protected words below, the final title length may exceed this limit.', 'text', '30', 40);

InsertMainSetting('seo_filter_words', 'settings_seo_settings', 'SEO Filter Words',
  'Filter all of below specified words from appearing in SEO titles (default: yes)?<br />
  Within a language there are several phrases which do not add any meaning to SEO titles
  and can therefore be removed, like in English the words "a", "about", "is", "in", "me", "you" etc.', 'yesno', '1', 45);

InsertMainSetting('seo_stop_words_list', 'settings_seo_settings', 'SEO Stop Words List',
  'A list of words (all lowercase!) that will be filtered out of SEO titles if option "SEO Filter Words" is active.<br />
  By default it contains a list of enlish words, so you may adapt it to your language.<br />
  Enter one word per line and only latin alphabet characters as the filter is applied after conversion!', 'textarea',
  "a\r\nabout\r\nafter\r\nago\r\nall\r\nalmost\r\nalong\r\nalot\r\nalso\r\nam\r\nan\r\nand\r\nanswer\r\nany\r\nanybody\r\nanybodys\r\nanywhere\r\nare\r\narent\r\naround\r\nas\r\nask\r\naskd\r\nat\r\nbad\r\nbe\r\nbecause\r\nbeen\r\nbefore\r\nbeing\r\nbest\r\nbetter\r\nbetween\r\nbig\r\nbtw\r\nbut\r\nby\r\ncan\r\ncant\r\ncome\r\ncould\r\ncouldnt\r\nday\r\ndays\r\ndays\r\ndid\r\ndidnt\r\ndo\r\ndoes\r\ndoesnt\r\ndont\r\ndown\r\neach\r\netc\r\neither\r\nelse\r\neven\r\never\r\nevery\r\neverybody\r\neverybodys\r\neveryone\r\nfar\r\nfind\r\nfor\r\nfound\r\nfrom\r\nget\r\ngo\r\ngoing\r\ngone\r\ngood\r\ngot\r\ngotten\r\nhad\r\nhas\r\nhave\r\nhavent\r\nhaving\r\nher\r\nhere\r\nhers\r\nhim\r\nhis\r\nhome\r\nhow\r\nhows\r\nhref\r\nI\r\nIve\r\nif\r\nin\r\nini\r\ninto\r\nis\r\nisnt\r\nit\r\nits\r\nits\r\njust\r\nknow\r\nlarge\r\nless\r\nlike\r\nliked\r\nlittle\r\nlooking\r\nlook\r\nlooked\r\nlooking\r\nlot\r\nmaybe\r\nmany\r\nme\r\nmore\r\nmost\r\nmuch\r\nmust\r\nmustnt\r\nmy\r\nnear\r\nneed\r\nnever\r\nnew\r\nnews\r\nno\r\nnone\r\nnot\r\nnothing\r\nnow\r\nof\r\noff\r\noften\r\nold\r\non\r\nonce\r\nonly\r\noops\r\nor\r\nother\r\nour\r\nours\r\nout\r\nover\r\npage\r\nplease\r\nput\r\nquestion\r\nquestions\r\nquestioned\r\nquote\r\nrather\r\nreally\r\nrecent\r\nsaid\r\nsaw\r\nsay\r\nsays\r\nshe\r\nsee\r\nsees\r\nshould\r\nsites\r\nsmall\r\nso\r\nsome\r\nsomething\r\nsometime\r\nsomewhere\r\nsoon\r\ntake\r\nthan\r\ntrue\r\nthank\r\nthat\r\nthatd\r\nthats\r\nthe\r\ntheir\r\ntheirs\r\ntheres\r\ntheirs\r\nthem\r\nthen\r\nthere\r\nthese\r\nthey\r\ntheyll\r\ntheyd\r\ntheyre\r\nthis\r\nthose\r\nthough\r\nthrough\r\nthus\r\ntime\r\ntimes\r\nto\r\ntoo\r\nunder\r\nuntil\r\nuntrue\r\nup\r\nupon\r\nuse\r\nusers\r\nversion\r\nvery\r\nvia\r\nwant\r\nwas\r\nway\r\nwe\r\nwell\r\nwent\r\nwere\r\nwerent\r\nwhat\r\nwhen\r\nwhere\r\nwhich\r\nwho\r\nwhom\r\nwhose\r\nwhy\r\nwide\r\nwill\r\nwith\r\nwithin\r\nwithout\r\nwont\r\nworld\r\nworse\r\nworst\r\nwould\r\nwrote\r\nwww\r\nyes\r\nyet\r\nyou\r\nyoud\r\nyoull\r\nyour\r\nyoure\r\nyours\r\nAFAIK\r\nIIRC\r\nLOL\r\nROTF\r\nROTFLMAO\r\nYMMV", 50);

InsertMainSetting('seo_protect_words', 'settings_seo_settings', 'SEO Keep Protected Words',
  'Keep any of the protected words in a SEO title regardless of other options (default: yes)?', 'yesno', '1', 55);

InsertMainSetting('seo_protected_words_list', 'settings_seo_settings', 'SEO Keep Protected Words List',
  'All words in this list will be kept in the SEO title regardless of any other settings (all lowercase!).<br />
  Enter one word per line, only latin alphabet characters!', 'textarea', '', 60);

InsertMainSetting('seo_remove_short_words', 'settings_seo_settings', 'SEO Remove Short Words',
  'Remove words that are shorter than the configured minimum (default: yes).', 'yesno', '1', 65);

InsertMainSetting('seo_min_word_length', 'settings_seo_settings', 'SEO Minimum Word Length',
  'The minimum length of words to be included in SEO titles (default: 3; valid: 1 - 10).', 'text', '3', 70);

InsertMainSetting('display_messaging_symbols', 'comment_options', 'Display Messaging Symbols',
  'Display the user\'s public messaging symbols within comments (default: No)?<br />
  This requires the default comments template to be current.', 'yesno', '0', 18);
InsertMainSetting('comments_enable_likes', 'comment_options', 'Enable Like this Comment',
  'Display a <strong>Like this Comment</strong> link below each comment for logged-in users (default: No)?<br />'.
  'This may be a valuable resource within a community to encourage others to e.g. provide comments '.
  'or let others know, that the liked comment was helpful or appreciated.',
  'usergroups', 0, 13);
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."mainsettings SET input = 'usergroups' WHERE varname = 'comments_enable_likes' AND groupname = 'comment_options'");
InsertMainSetting('comments_display_likes', 'comment_options', 'Display Comment Likes',
  'Independent of the above <strong>Like this Comment</strong> option being enabled, display existing Likes (default: Yes)?<br />'.
  'If, for whatever reason, no likes should be displayed anymore, disable this option.<br />',
  'yesno', '1', 14);

InsertMainSetting('enable_mobile_detection', 'settings_general_settings', 'Enable Mobile Device Detection',
  'Enable the detection of mobile devices with every pageload (default: No)?<br />'.
  'Only if set to Yes, the setting <strong>Supported browsers</strong> for pages is working for mobile devices.',
  'yesno', '0', 130);

InsertMainSetting('comments_sfs_antispam', 'comment_options', 'Enable SFS AntiSpam',
  'Enable the checking of the registrants email address and IP address against the <strong>StopForumSpam</strong> (SFS) database (default: No)?<br />
  Note: this requires to query their database remotely and may not be possible on all servers, please try it out.<br />
  Please visit <a href="http://www.stopforumspam.com" target="_blank"><strong>StopForumSpam.com</strong></a> for further information
  and terms of usage. If enabled, please consider supporting them by donating to their project.', 'yesno','0', 16);
InsertMainSetting('comments_enable_blocklist_checks', 'comment_options', 'Enable Blocklist Checks',
  'Enable checking of IP address against one or more <strong>blocklists</strong> (default: Disabled)?<br />
  Note: this requires to query the lists remotely and may not be technically possible on all servers, please try it out
  and check frequently your System Log (Settings page) for warnings or error messages.<br />
  Please visit any used list <strong>BEFORE</strong> enabling and consult their respective terms of use/usage policies.<br />
  <strong>Not obeying their rules may get your server IP blacklisted or banned!</strong>',
  '', '0', 40);

$DB->query('UPDATE '.PRGM_TABLE_PREFIX."adminphrases SET defaultphrase = 'Enable processing of Custom Plugins for the {pagebreak} macro and - if found - display pagination (default: No)?'
            WHERE pluginid = 0 AND varname = 'enable_custom_plugin_paging_descr'");
$DB->query('UPDATE '.PRGM_TABLE_PREFIX."adminphrases SET varname = 'count_banned_ips' WHERE varname = 'count_banned_ip_attempts'");
$DB->query('UPDATE '.PRGM_TABLE_PREFIX."adminphrases SET varname = 'count_banned_ips_descr' WHERE varname = 'count_banned_ip_attempts_descr'");
$DB->query('UPDATE '.PRGM_TABLE_PREFIX."mainsettings SET title = 'count_banned_ips', description = 'count_banned_ips_descr' WHERE varname = 'count_banned_ips'");

$DB->query('DELETE FROM '.PRGM_TABLE_PREFIX."mainsettings WHERE varname IN ('log_banned_ip_login','log_invalid_login_attempts')");
InsertMainSetting('log_invalid_login_attempts', 'settings_system_log', 'Log Invalid Login Attempts',
  'Create a system log entry if a login attempt was made with unknown username (could be spam/hack)?<br />
  Websites are frequently attacked with attempts to login by chance with usernames which do not exist (fake).
  These attempts usually occour within a certain timeframe (maybe hourly), with varying usernames and mostly different IP addresses.
  Logging these attempts - most likely to create spam - may assist to identify IP addresses, that should be
  added to the <strong>Banned IP Addresses</strong> list in the <strong>User Registration</strong> plugin.<br />
  This is only used if no forum is integrated.', 'yesno', '1', 100);
InsertMainSetting('log_banned_ip_login', 'settings_system_log', 'Log Banned IP Login',
  'Create a system log entry if a login attempt was made with a banned email or IP address (default: No)?<br />
  This is only used if no forum is integrated.', 'yesno', '1', 120);

if($forumid = GetPluginID('Forum'))
{
  InsertPluginSetting($forumid, 'forum_display_settings', 'Enable SFS AntiSpam',
  'Enable the checking of the registrants email address and IP address against the <strong>StopForumSpam</strong> (SFS) database (default: No)?<br />
  Note: this requires to query their database remotely and may not be possible on all servers, please try it out.<br />
  Please visit <a href="http://www.stopforumspam.com" target="_blank"><strong>StopForumSpam.com</strong></a>
  for further information and terms of usage. If enabled, please consider supporting them by donating to their project.',
  'yesno', '0', 9);
  $DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."phrases WHERE pluginid = %d AND varname LIKE 'select_enable_blocklist_checks_%'",$forumid);
  InsertPluginSetting($forumid, 'forum_display_settings', 'Enable Blocklist Checks',
    'Enable checking of IP address against one or more <strong>blocklists</strong> (default: Disabled)?<br />
    Note: this requires to query the lists remotely and may not be technically possible on all servers, please try it out
    and check frequently your System Log (Settings page) for warnings or error messages.<br />
    Please visit any used list <strong>BEFORE</strong> enabling and consult their respective terms of use/usage policies.<br />
    <strong>Not obeying their rules may get your server IP blacklisted or banned!</strong>',
    '', '0', 9);
  InsertPluginSetting($forumid, 'forum_display_settings', 'User Edit Timeout',
    'Allow users to edit their own posts within the given amount of minutes after submitting a post (default: 60; 0 = never)?',
    'text', '60', 90);
  InsertPluginSetting($forumid, 'forum_display_settings', 'Display No Post Access Topic',
    'Display the below message for users that do not have permission to post (default: No):', 'yesno', '0', 100);
  InsertPluginSetting($forumid, 'forum_display_settings', 'Message No Post Access Topic',
    'Content of the message to display at bottom of topic view (HTML; default: empty):', 'wysiwyg', '', 110);
  InsertPluginSetting($forumid, 'forum_display_settings', 'Display Tagcloud',
    'Display a tagcloud for article tags (default: No)?', "select:\r\n0|No\r\n1|Top\r\n2|Bottom\r\n3|Both", 0, 120);
  InsertPluginSetting($forumid, 'forum_topic_settings', 'Limit Moderated Posts',
    'Limit the amount of moderated posts (incl. topics) by a user to the given number of posts (default: 3; max 100):<br />
    This is used for non-admin users within forums where auto-moderation is active for usergroups.<br />
    To disable this limit, set this to 0.',
    'text', 3, 122);
  InsertPluginSetting($forumid, 'forum_topic_settings', 'Limit Moderated Minutes',
    'Apply the above limit for moderated posts by a user within the given number of minutes (default: 5; max 1440):<br />
    This is used for non-admin users within forums where auto-moderation is active for usergroups.<br />
    To disable this limit, set this to 0.',
    'text', 5, 124);

  InsertPluginSetting($forumid, 'forum_topic_settings', 'Tag Submit Permissions',
    'Which usergroup(s) should be allowed to enter <strong>tags</strong> when creating a new topic?<br /><br />'.
    'De-/select single or multiple entries by CTRL/Shift+click.', 'usergroups', '', 100, true);
  InsertPluginSetting($forumid, 'forum_topic_settings', 'Minimum Topic Title Length', 'How many characters in total must a topic title at least have (default: 3)?', 'text', '3', 1, true);
  InsertPluginSetting($forumid, 'forum_topic_settings', 'Minimum Post Text Length', 'How many characters in total must a post text at least have (default: 3)?', 'text', '3', 2, true);

  #2012-03-04: avoid duplicate entry
  $oldval = $DB->query_first("SELECT value FROM ".PRGM_TABLE_PREFIX."pluginsettings WHERE pluginid = %d AND title = 'enable_like_this_post' ORDER BY settingid LIMIT 1",$forumid);
  $oldval = !isset($oldval['value'])?'0':$oldval['value'];
  $DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."pluginsettings WHERE pluginid = %d AND title = 'enable_like_this_post'",$forumid);
  InsertPluginSetting($forumid, 'forum_topic_settings', 'Enable Like this Post',
    'Display a <strong>Like this Post</strong> link below each post for users in selected usergroups (default: none)?<br />'.
    'This is the global, forum-wide option and - if enabled - also needs to be enabled for each forum!<br />'.
    'This may be a valuable resource within a community to encourage others to e.g. provide answers '.
    'or let others know, that the liked post was helpful or appreciated.<br /><br />'.
    'De-/select single or multiple entries by CTRL/Shift+click.',
    'usergroups', $oldval, 130, true);

  InsertPluginSetting($forumid, 'forum_topic_settings', 'Enable Dislikes for Posts',
    '<strong>Only</strong> if above option is set to Yes, allow users to also dislike posts (default: No)?<br />'.
    'Note: if a user has liked a post, a click on dislike will remove that like vote.',
    'yesno', 0, 135, true);

  #2012-03-04: avoid duplicate entry
  $oldval = $DB->query_first("SELECT value FROM ".PRGM_TABLE_PREFIX."pluginsettings WHERE pluginid = %d AND title = 'display_likes_results' ORDER BY settingid LIMIT 1",$forumid);
  $oldval = !isset($oldval['value'])?'1':(int)$oldval['value'];
  $DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."pluginsettings WHERE pluginid = %d AND title = 'display_likes_results'",$forumid);
  InsertPluginSetting($forumid, 'forum_topic_settings', 'Display Likes Results',
    'Independent of the above <strong>Like this Post</strong> option being enabled, display existing Likes (default: Yes)?<br />'.
    'If - for whatever reason - no likes should be displayed anymore, disable this option (set to No).',
    'yesno', $oldval, 140,true);

  InsertPhrase($forumid, 'topic_moderated',           'Topic moderated');
  InsertPhrase($forumid, 'attachment_deleted',        'Attachment deleted.');
  InsertPhrase($forumid, 'attachment_delete_failed',  'Attachment not deleted!');
  InsertPhrase($forumid, 'attachment_upload_invalid', 'Invalid attachment upload!');
  InsertPhrase($forumid, 'attachment_filesize_error', 'Attachment has invalid filesize!');
  InsertPhrase($forumid, 'destination_forum',         'Destination Forum');
  InsertPhrase($forumid, 'move_posts_new_topic',      'Move posts into a new topic?',true);
  InsertPhrase($forumid, 'move_posts_existing_topic', 'Move posts into an existing topic?',true);
  InsertPhrase($forumid, 'enter_new_topic_title',     'Please enter the title for the new topic:',true);
  InsertPhrase($forumid, 'enter_existing_topic_id',   'Please enter the ID of the existing topic (see browser URL):');
  InsertPhrase($forumid, 'confirm_move_posts',        'Really move post(s) to selected thread or forum?');
  InsertPhrase($forumid, 'message_post_moved',        'The post has been moved.');
  InsertPhrase($forumid, 'message_posts_moved',       'The selected posts have been moved.');
  InsertPhrase($forumid, 'message_posts_move_cancelled', 'The moving of post(s) has been cancelled.');
  InsertPhrase($forumid, 'confirm_delete_post',       'Yes, I confirm post(s) deletion');
  InsertPhrase($forumid, 'post_singular',             'Post');
  InsertPhrase($forumid, 'topic_prefix',              'Topic Prefix:');
  InsertPhrase($forumid, 'forum_tags',                'Forum Tags:');
  InsertPhrase($forumid, 'topic_tags',                'Topic Tags:');
  InsertPhrase($forumid, 'topic_tags_hint',           'You may add 10 comma-separated tags to this topic. These will be visible to all users.');
  InsertPhrase($forumid, 'topic_options_move_posts',  'Move Posts');
  InsertPhrase($forumid, 'topic_options_edit_topic',  'Edit Topic');
  InsertPhrase($forumid, 'topic_options_remove_prefix', 'Remove Prefix');
  InsertPhrase($forumid, 'change_prefix_to',          'Change Prefix:');
  InsertPhrase($forumid, 'update_topic',              'Update Topic');
  InsertPhrase($forumid, 'user_options',              'User Options');
  InsertPhrase($forumid, 'last_visit',                'Last Visit:');
  InsertPhrase($forumid, 'search_users_posts',        'Search Users Posts');
  InsertPhrase($forumid, 'message_tagged_topics',     'Topics Tagged with');
  InsertPhrase($forumid, 'message_like_removed',      'Your vote on this post was removed.');
  InsertPhrase($forumid, 'message_post_like',         'Thank you for liking the post!');
  InsertPhrase($forumid, 'message_post_dislike',      'Post has been thumbed down!');
  InsertPhrase($forumid, 'message_topic_updated',     'Topic updated.');
  DeletePhrase($forumid, 'message_topic_renamed');
  InsertPhrase($forumid, 'message_too_many_moderated', 'Sorry, your submission was rejected because your account reached the limit of moderated items.');

  InsertAdminPhrase($forumid, 'lbl_enable_likes',     'Enable <strong>Like this Post</strong> links');
  InsertAdminPhrase($forumid, 'current_image',        'Current image:');
  InsertAdminPhrase($forumid, 'delete_image',         'Delete image?');
  InsertAdminPhrase($forumid, 'no_image_uploaded',    'No image uploaded.');
  InsertAdminPhrase($forumid, 'upload_new_image',     'Upload new image:');
  InsertAdminPhrase($forumid, 'is_category',          'Is a Category?');
  InsertAdminPhrase($forumid, 'err_invalid_upload',   'Invalid image upload!');
  InsertAdminPhrase($forumid, 'err_upload_errors',    'Possible Upload Errors');
  InsertAdminPhrase($forumid, 'err_category_ignored', 'Forum could not be set to be a category, ignoring that setting!');
  InsertAdminPhrase($forumid, 'err_seo_title_existing', 'The forum\'s <strong>SEO Title</strong> must be set manually! There already exists a forum with the same SEO title!');
  InsertAdminPhrase($forumid, 'category_not_possible','For this forum either topics or subforums already exist. Therefore this forum cannot be configured as a category.');
  InsertAdminPhrase($forumid, 'category_not_possible2','This forum is marked as a category, which at this point cannot be changed due to existing topics or subforums.');
  InsertAdminPhrase($forumid, 'note_folder_not_writable', 'NOTE! folder is not writable: ');
  InsertAdminPhrase($forumid, 'forum_upload_image',
    '<strong>Upload a forum image</strong> to be displayed in the forums list on the frontpage.<br />'.
    'Note: in the forum settings page the image column can be enabled/disabled.');
  InsertAdminPhrase($forumid,'post_edit_permissions_title','Post Editing Permissions');
  InsertAdminPhrase($forumid,'post_edit_permissions_descr','Always allow users in selected usergroups to edit their own posts:<br />'.
    'Select none to allow all usergroups OR only specific usergroups to always allow editing of own posts, otherwise the <strong>User Edit Timeout</strong> setting is used '.
    '(see <strong>Forum Settings</strong>).<br />Site administrators can always edit all posts, plugin administrators only if they have access to this forum.<br /><br />'.
    'Please use CTRL/Shift+click to select/deselect any usergroup(s), that shall be allowed to VIEW the current forum '.
    'and below topics and posts.<br />',2,true);

  $oldval = $DB->query_first("SELECT value FROM ".PRGM_TABLE_PREFIX."pluginsettings WHERE pluginid = 1 AND title = 'sd343_change2' LIMIT 1");
  if(empty($oldval['value']))
  {
    $tbl = PRGM_TABLE_PREFIX.'p_forum_forums';
    $oldignore = $DB->ignore_error;
    $DB->ignore_error = true;
    $DB->query("ALTER TABLE $tbl MODIFY access_post VARCHAR(254) collate utf8_unicode_ci NULL");
    $DB->query("ALTER TABLE $tbl CHANGE access_post access_post VARCHAR(254) collate utf8_unicode_ci NOT NULL DEFAULT ''");
    $DB->query("ALTER TABLE $tbl ALTER access_post SET DEFAULT ''");
    $DB->query("ALTER TABLE $tbl MODIFY access_view VARCHAR(254) collate utf8_unicode_ci NULL");
    $DB->query("ALTER TABLE $tbl CHANGE access_view access_view VARCHAR(254) collate utf8_unicode_ci NOT NULL DEFAULT ''");
    $DB->query("ALTER TABLE $tbl ALTER access_view SET DEFAULT ''");
    $DB->ignore_error = $oldignore;
    $DB->query("ALTER TABLE $tbl CHANGE moderated moderated VARCHAR(254) collate utf8_unicode_ci NOT NULL DEFAULT ''");
    $DB->query("UPDATE $tbl SET moderated = '' WHERE moderated IN ('0','1')");
    $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX."phrases WHERE pluginid = %d AND varname LIKE 'like%'",$forumid);
    $DB->add_tablecolumn($tbl, 'enable_likes', 'INT(1) UNSIGNED', 'NOT NULL DEFAULT 0');
    $DB->add_tablecolumn($tbl, 'post_edit', 'VARCHAR(254)', "collate utf8_unicode_ci NOT NULL DEFAULT '' AFTER access_view");

    $tbl = PRGM_TABLE_PREFIX.'p_forum_posts';
    $DB->add_tablecolumn($tbl, 'user_likes', 'MEDIUMBLOB', 'NULL');
    $DB->add_tablecolumn($tbl, 'likes_count', 'INT(11)', 'UNSIGNED NOT NULL DEFAULT 0');
    $DB->add_tablecolumn($tbl, 'dislikes_count', 'INT(11)', 'UNSIGNED NOT NULL DEFAULT 0');
    $DB->query("ALTER TABLE $tbl CHANGE `ip_address` `ip_address` VARCHAR(40) collate utf8_unicode_ci NOT NULL DEFAULT ''");
    $DB->query("ALTER TABLE $tbl CHANGE `attachment_count` `attachment_count` INT(11) UNSIGNED NOT NULL DEFAULT 0");
    $DB->query("ALTER TABLE $tbl CHANGE `likes_count` `likes_count` INT(11) UNSIGNED NOT NULL DEFAULT 0");
  }
}

InsertPhrase(1, 'err_forbidden_content',  'Sorry, but your data contains forbidden content and was rejected!');
InsertPhrase(1, 'dislike_comment',        'Dislike this Comment');
InsertPhrase(1, 'dislike_post',           'Dislike this Post');
InsertPhrase(1, 'like_comment',           'Like this Comment');
InsertPhrase(1, 'like_post',              'Like this Post');
InsertPhrase(1, 'likes_and',              'and');
InsertPhrase(1, 'likes_others',           'others');
InsertPhrase(1, 'likes_one_other',        '1 other user');
InsertPhrase(1, 'likes_like_this',        'like this.');
InsertPhrase(1, 'likes_likes_this',       'likes this.');
InsertPhrase(1, 'likes_dislike_this',     'dislike this.');
InsertPhrase(1, 'likes_dislikes_this',    'dislikes this.');
InsertPhrase(1, 'remove_dislike',         'Remove Dislike');
InsertPhrase(1, 'remove_like',            'Remove Like');
InsertPhrase(1, 'message_comment_disliked', 'Your disliking of the comment was registered.');
InsertPhrase(1, 'message_comment_like_removed', 'Your comment vote was removed.');
InsertPhrase(1, 'message_comment_liked', 'Thank you for liking a comment!');

$DB->ignore_error = true;
$tbl = PRGM_TABLE_PREFIX.'comments';
$DB->add_tablecolumn($tbl, 'user_likes', 'MEDIUMBLOB', 'NULL');
$DB->add_tablecolumn($tbl, 'likes_count', 'INT(11)', 'UNSIGNED NOT NULL DEFAULT 0');
$DB->add_tablecolumn($tbl, 'dislikes_count', 'INT(11)', 'UNSIGNED NOT NULL DEFAULT 0');
$DB->query("ALTER TABLE $tbl CHANGE `ipaddress` `ipaddress` VARCHAR(40) collate utf8_unicode_ci NOT NULL DEFAULT ''");
$DB->query("ALTER TABLE $tbl CHANGE `likes_count` `likes_count` INT(11) UNSIGNED NOT NULL DEFAULT 0");

$oldval = $DB->query_first("SELECT value FROM ".PRGM_TABLE_PREFIX."pluginsettings WHERE pluginid = 1 AND title = 'sd343_change1' LIMIT 1");
if(empty($oldval['value']))
{
  # DB changes for IP address fields (sized to 40 to support IPv6) etc.
  $tbl = PRGM_TABLE_PREFIX.'users';
  $DB->query("ALTER TABLE $tbl CHANGE `register_ip` `register_ip` VARCHAR(40) collate utf8_unicode_ci NOT NULL DEFAULT ''");
  $tbl = PRGM_TABLE_PREFIX.'users_likes';
  $DB->query("ALTER TABLE $tbl CHANGE `ip_address` `ip_address` VARCHAR(40) collate utf8_unicode_ci NOT NULL DEFAULT ''");
  $tbl = PRGM_TABLE_PREFIX.'sessions';
  $DB->query("ALTER TABLE $tbl CHANGE `ipaddress` `ipaddress` VARCHAR(40) collate utf8_unicode_ci NOT NULL DEFAULT ''");
  $tbl = PRGM_TABLE_PREFIX.'tags';
  $DB->query("ALTER TABLE $tbl CHANGE `censored` `censored` INT(11) UNSIGNED NOT NULL DEFAULT 0");
  $DB->query("ALTER TABLE $tbl CHANGE `tag_ref_id` `tag_ref_id` INT(11) UNSIGNED NOT NULL DEFAULT 0");
  $tbl = PRGM_TABLE_PREFIX.'ratings';
  $DB->query("ALTER TABLE $tbl CHANGE `user_ip` `user_ip` VARCHAR(40) collate utf8_unicode_ci NOT NULL DEFAULT ''");
  $DB->query("ALTER TABLE $tbl CHANGE `pluginid` `pluginid` INT(11) UNSIGNED NOT NULL DEFAULT 0");
  $DB->query("ALTER TABLE $tbl CHANGE `user_id` `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0");
  $tbl = PRGM_TABLE_PREFIX.'msg_messages';
  $DB->query("ALTER TABLE $tbl CHANGE `ip_address` `ip_address` VARCHAR(40) collate utf8_unicode_ci NOT NULL DEFAULT ''");
  $tbl = PRGM_TABLE_PREFIX.'vvc';
  $DB->query("ALTER TABLE $tbl CHANGE `ipaddress` `ipaddress` VARCHAR(40) collate utf8_unicode_ci NOT NULL DEFAULT ''");
  $DB->query("ALTER TABLE $tbl CHANGE `date` `date` INT(11) UNSIGNED NOT NULL DEFAULT 0");

  $tbl = PRGM_TABLE_PREFIX.'slugs';
  $DB->query("ALTER TABLE $tbl CHANGE `pluginid` `pluginid` INT(11) UNSIGNED NOT NULL DEFAULT 0");
  $DB->query("ALTER TABLE $tbl CHANGE `parent` `parent` INT(11) UNSIGNED NOT NULL DEFAULT 0");
  $DB->query("ALTER TABLE $tbl CHANGE `count` `count` INT(11) UNSIGNED NOT NULL DEFAULT 0");

  $tbl = PRGM_TABLE_PREFIX.'pluginsettings';
  $DB->query("REPLACE INTO $tbl (settingid, pluginid, groupname, title,           description, input, value, displayorder)
                         VALUES (null,      1,        'UPGRADE', 'sd343_change1', '-',         '-',   '1',   1)");
}
$tbl = PRGM_TABLE_PREFIX.'slugs';
$DB->add_tablecolumn($tbl, 'slug_type', 'INT(4)', 'UNSIGNED NOT NULL DEFAULT 0');
if(!$DB->query_first("SELECT name FROM $tbl WHERE name = 'Tag Archive' AND pluginid = 2 LIMIT 1"))
{
  $DB->query_first("INSERT INTO `$tbl` (`term_id`, `name`, `description`, `pluginid`, `parent`, `count`, `slug`, `slug_type`) VALUES
  (NULL, 'Tag Archive', 'URI for plugin tags with tag value at then end.', 2, 0, 0, '[tag]', 0)");
}
if(!$DB->query_first("SELECT name FROM $tbl WHERE name = 'Tags by Year and Month' AND pluginid = 2 LIMIT 1"))
{
  $DB->query_first("INSERT INTO `$tbl` (`term_id`, `name`, `description`, `pluginid`, `parent`, `count`, `slug`, `slug_type`) VALUES
  (NULL, 'Tags by Year and Month', 'URI for tags by year and month', 2, 0, 0, '[year]/[month]', 0)");
}

DeleteAdminPhrase(0,'media_sort_desc',3);
DeleteAdminPhrase(0,'media_sort_descr',3);
InsertAdminPhrase(0,'media_sort_descending','Sort: Descending',3);
InsertAdminPhrase(0,'media_details','Details',3);
InsertAdminPhrase(0,'media_thumbnails','Thumbnails',3);
DeleteAdminPhrase(0,'users_require_vvc_desc',2);
InsertAdminPhrase(0,'users_require_vvc_descr','Members are required to enter a verification code when submitting data?<br />Note: this option may only be used by some plugins, like the Forum.',5);
DeleteAdminPhrase(0,'users_require_vvc',2);
InsertAdminPhrase(0,'users_require_vvc','Require VVC',5);
DeleteAdminPhrase(0,'users_sort_desc',5);
InsertAdminPhrase(0,'users_sort_descending','Descending',5);

$DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."phrases WHERE pluginid IN (4,6,7,10,12,16) AND varname LIKE 'select_enable_blocklist_checks_%'");
$blocklists = "select-multi:\r\n0|Disabled\r\n1|sbl.spamhaus.org\r\n2|zen.spamhaus.org\r\n4|multi.sburl.org\r\n8|bl.spamcop.net\r\n16|dnsbl.njabl.org\r\n32|dnsbl.sorbs.net";
$DB->query('UPDATE `'.PRGM_TABLE_PREFIX.'pluginsettings` SET input = \'%s\' WHERE title = "enable_blocklist_checks"', $blocklists);
$DB->query('UPDATE `'.PRGM_TABLE_PREFIX.'mainsettings` SET input = \'%s\' WHERE title = "enable_blocklist_checks"', $blocklists);

$DB->query('UPDATE `'.PRGM_TABLE_PREFIX."adminphrases` SET varname = CONCAT(varname,'r') WHERE pluginid IN (0,3,10) AND varname LIKE '%_desc'");
$DB->query('UPDATE `'.PRGM_TABLE_PREFIX."mainsettings` SET description = CONCAT(description,'r') WHERE description LIKE '%_desc'");
$DB->query('UPDATE `'.PRGM_TABLE_PREFIX."pluginsettings` SET description = CONCAT(description,'r') WHERE pluginid IN (3,10) AND description LIKE '%\_desc'");

$DB->query('UPDATE `'.PRGM_TABLE_PREFIX."adminphrases` SET varname = 'comments_date_descending' WHERE adminpageid = 4 AND pluginid = 0 AND varname = 'comments_date_descr'");
$DB->query('UPDATE `'.PRGM_TABLE_PREFIX."adminphrases` SET varname = 'comments_uname_descending' WHERE adminpageid = 4 AND pluginid = 0 AND varname = 'comments_uname_descr'");
$DB->query('UPDATE `'.PRGM_TABLE_PREFIX."adminphrases` SET varname = 'comments_ip_descending' WHERE adminpageid = 4 AND pluginid = 0 AND varname = 'comments_ip_descr'");

DeleteAdminPhrase(0,'users_profile_permissions',5);
InsertAdminPhrase(0,'comments_permissions','Comments Permissions',5);
InsertAdminPhrase(0,'usergroups_extended_permissions','Extended Permissions',5);
InsertAdminPhrase(0,'comments_allow_edit','Allow user to edit own comments (default: No)?<br />This requires Allow Comments permission for corresponding plugin!',5);
InsertAdminPhrase(0,'comments_allow_delete','Allow user to delete own comments (default: No)?<br />This requires Allow Comments permission for corresponding plugin!',5);
InsertAdminPhrase(0,'comments_approve_edits','Auto-approve user edited comments (default: No)?<br />If set to Yes, edited comments will be automatically approved.',5);
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups_config', 'edit_own_comments', 'TINYINT(1) UNSIGNED NOT NULL DEFAULT 0');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups_config', 'delete_own_comments', 'TINYINT(1) UNSIGNED NOT NULL DEFAULT 0');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups_config', 'approve_comment_edits', 'TINYINT(1) UNSIGNED NOT NULL DEFAULT 0');
$DB->ignore_error = false;
