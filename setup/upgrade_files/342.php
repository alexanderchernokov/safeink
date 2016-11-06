<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

$DB->query("UPDATE {adminphrases} SET adminpageid = 2, pluginid = 11 WHERE (adminpageid = 5)
            AND (pluginid <> 11) AND ((varname like '%profile%') OR (varname IN ('users_add_field_to_group')))");

// Skin CSS table: add admin_only attribute
$DB->ignore_error = true;
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'skin_css', 'admin_only', 'TINYINT(1)',  "NOT NULL DEFAULT 0");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'skin_css', 'disabled', 'TINYINT(1)', "NOT NULL DEFAULT 0");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'skin_css', 'description', 'VARCHAR(250) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."skin_css SET admin_only = 0");
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."skin_css SET admin_only = 1 WHERE var_name = 'WYSIWYG'");
$DB->ignore_error = false;
InsertAdminPhrase(0,'hint_enable_disable','Enable/Disable',6);

$DB->query('CREATE TABLE IF NOT EXISTS '.PRGM_TABLE_PREFIX."slugs (
`term_id`       int(11) unsigned NOT NULL AUTO_INCREMENT,
`name`          varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
`description`   text COLLATE utf8_unicode_ci NOT NULL,
`pluginid`      int(11) unsigned NOT NULL DEFAULT 0,
`parent`        int(11) unsigned NOT NULL DEFAULT 0,
`count`         int(11) unsigned NOT NULL DEFAULT 0,
`slug`          varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
`slug_type`     int(4) unsigned NOT NULL DEFAULT 0,
PRIMARY KEY (`term_id`),
KEY `pluginid` (`pluginid`),
KEY `name` (`name`),
KEY `slug` (`slug`),
KEY `slug_type` (`slug_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."msg_master (
`master_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`usersystemid` int(11) unsigned NOT NULL DEFAULT 0,
`starter_id` int(11) unsigned NOT NULL DEFAULT 0,
`starter_username` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
`recipient_id` int(11) unsigned NOT NULL DEFAULT 0,
`master_title` varchar(250) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
`master_date` int(10) unsigned NOT NULL DEFAULT 0,
`msg_count` int(10) unsigned NOT NULL DEFAULT 0,
`msg_user_count` int(11) unsigned NOT NULL DEFAULT 0,
`first_msg_id` int(10) unsigned NOT NULL DEFAULT 0,
`first_text_id` int(10) unsigned NOT NULL DEFAULT 0,
`last_msg_id` int(10) unsigned NOT NULL DEFAULT 0,
`last_user_id` int(11) unsigned NOT NULL DEFAULT 0,
`last_text_id` int(10) unsigned NOT NULL DEFAULT 0,
`last_msg_date` int(10) unsigned NOT NULL DEFAULT 0,
`last_msg_username` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
`views` int(10) unsigned NOT NULL DEFAULT '0',
`is_private` tinyint(1) unsigned NOT NULL DEFAULT 1,
`is_closed` tinyint(1) unsigned NOT NULL DEFAULT 0,
`allow_invites` tinyint(1) NOT NULL DEFAULT 0,
`approved` tinyint(1) NOT NULL DEFAULT 1,
`access_view` TEXT COLLATE utf8_unicode_ci NOT NULL,
`access_post` TEXT COLLATE utf8_unicode_ci NOT NULL,
`access_moderate` TEXT COLLATE utf8_unicode_ci NOT NULL,
PRIMARY KEY (`master_id`),
KEY `starter_id` (`starter_id`),
KEY `recipient_id` (`recipient_id`),
KEY `master_date` (`master_date`),
KEY `last_msg_date` (`last_msg_date`),
KEY `last_user_id` (`last_user_id`),
KEY `approved` (`approved`),
FULLTEXT KEY `master_title` (`master_title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."msg_messages (
`msg_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`usersystemid` int(10) unsigned NOT NULL DEFAULT 1,
`userid` int(11) unsigned NOT NULL DEFAULT 0,
`username` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
`master_id` int(11) unsigned NOT NULL DEFAULT 0,
`msg_title` varchar(250) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
`msg_text_id` int(10) unsigned NOT NULL DEFAULT 0,
`recipient_id` int(11) unsigned NOT NULL DEFAULT 0,
`recipient_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
`msg_date` int(10) unsigned NOT NULL DEFAULT 0,
`msg_read` int(10) unsigned NOT NULL DEFAULT 0,
`private` tinyint(1) unsigned NOT NULL DEFAULT 0,
`outbox_copy` tinyint(1) unsigned NOT NULL DEFAULT 0,
`ip_address` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
`approved` tinyint(1) unsigned NOT NULL DEFAULT 1,
PRIMARY KEY (`msg_id`),
KEY `userid` (`userid`),
KEY `usersystemid` (`usersystemid`),
KEY `master_id` (`master_id`),
KEY `recipient_id` (`recipient_id`),
KEY `username` (`username`),
KEY `msg_date` (`msg_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."msg_text (
`msg_text_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`msg_text` MEDIUMTEXT COLLATE utf8_unicode_ci NOT NULL,
PRIMARY KEY (`msg_text_id`),
FULLTEXT KEY `msg_text` (`msg_text`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."msg_user (
`master_id` int(11) unsigned NOT NULL DEFAULT 0,
`userid` int(11) unsigned NOT NULL DEFAULT 0,
`username` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
`last_read` int(10) unsigned NOT NULL DEFAULT 0,
`is_moderator` tinyint(1) unsigned NOT NULL DEFAULT 0,
`allow_invites` tinyint(1) unsigned NOT NULL DEFAULT 0,
`status` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
PRIMARY KEY (`master_id`, `userid`),
KEY `userid` (`userid`),
KEY `username` (`username`),
KEY `last_read` (`last_read`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."users_subscribe (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`userid` int(11) unsigned NOT NULL DEFAULT 0,
`pluginid` int(11) unsigned NOT NULL DEFAULT 0,
`objectid` int(11) unsigned NOT NULL DEFAULT 0,
`type` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
`categoryid` int(11) unsigned NOT NULL DEFAULT 0,
`last_read` int(10) unsigned NOT NULL DEFAULT 0,
`last_email` int(10) unsigned NOT NULL DEFAULT 0,
`expiration_date` int(10) unsigned NOT NULL DEFAULT 0,
`email_notify` int(1) unsigned NOT NULL DEFAULT 0,
`email_template_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
PRIMARY KEY (`id`),
KEY `userid` (`userid`),
KEY `pluginid` (`pluginid`),
KEY `objectid` (`objectid`),
KEY `last_read` (`last_read`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
$DB->ignore_error = true;
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'users_subscribe', 'categoryid','int(11) unsigned','NOT NULL DEFAULT 0 AFTER `type`');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'users_subscribe', 'last_email','int(11) unsigned','NOT NULL DEFAULT 0 AFTER `last_read`');

$tbl = PRGM_TABLE_PREFIX.'msg_master';
$DB->add_tablecolumn($tbl, 'first_text_id','int(10) unsigned','NOT NULL DEFAULT 0 AFTER `first_msg_id`');
$DB->add_tablecolumn($tbl, 'last_text_id','int(10) unsigned','NOT NULL DEFAULT 0 AFTER `last_msg_id`');
$DB->add_tablecolumn($tbl, 'last_user_id','int(11) unsigned','NOT NULL DEFAULT 0 AFTER `last_msg_id`');
$DB->add_tablecolumn($tbl, 'is_closed','tinyint(1) unsigned','NOT NULL DEFAULT 0 AFTER `is_private`');
$DB->add_tableindex($tbl, 'usersystemid', 'usersystemid');
$DB->add_tableindex($tbl, 'last_user_id', 'last_user_id');
$DB->add_tableindex($tbl, 'master_date', 'master_date');

$tbl = PRGM_TABLE_PREFIX.'msg_messages';
$DB->add_tablecolumn($tbl, 'usersystemid','int(10) unsigned','NOT NULL DEFAULT 1 AFTER `msg_id`');
$DB->add_tablecolumn($tbl, 'msg_read','int(10) unsigned','NOT NULL DEFAULT 0 AFTER `msg_text`');
$DB->add_tablecolumn($tbl, 'is_reply_to','int(10) unsigned','NOT NULL DEFAULT 0 AFTER `ip_address`');
$DB->add_tablecolumn($tbl, 'msg_title','varchar(250) COLLATE utf8_unicode_ci',"NOT NULL DEFAULT '' AFTER `master_id`");
$DB->add_tablecolumn($tbl, 'recipient_name','varchar(100) COLLATE utf8_unicode_ci',"NOT NULL DEFAULT '' AFTER `recipient_id`");
$DB->add_tablecolumn($tbl, 'msg_text_id','int(10) unsigned','NOT NULL DEFAULT 0 AFTER `msg_title`');
$DB->add_tablecolumn($tbl, 'approved','tinyint(1) unsigned','NOT NULL DEFAULT 1');
$DB->query("ALTER TABLE $tbl DROP INDEX msg_text");
$DB->remove_tablecolumn($tbl, 'msg_text');
$DB->add_tablecolumn($tbl, 'msg_read_notify','tinyint(1) unsigned','NOT NULL DEFAULT 0');
$DB->add_tableindex($tbl, 'usersystemid', 'usersystemid');
$DB->add_tableindex($tbl, 'msg_date', 'msg_date');

$tbl = PRGM_TABLE_PREFIX.'ratings';
$DB->add_tableindex($tbl, 'pluginid', 'pluginid');

$tbl = PRGM_TABLE_PREFIX.'users_field_groups';
$DB->add_tablecolumn($tbl, 'access_view', 'varchar(128)', "collate utf8_unicode_ci NOT NULL DEFAULT ''");
$DB->add_tablecolumn($tbl, 'friends_only', 'tinyint(1)', "NOT NULL DEFAULT 0");

$tbl = PRGM_TABLE_PREFIX.'users_data';
$DB->add_tablecolumn($tbl, 'msg_in_count', 'int(10) unsigned', 'NOT NULL DEFAULT 0 AFTER `userid`');
$DB->add_tablecolumn($tbl, 'msg_out_count', 'int(10) unsigned', 'NOT NULL DEFAULT 0 AFTER `msg_in_count`');
$DB->add_tablecolumn($tbl, 'public_fields', 'text', 'null AFTER `msg_out_count`');
$DB->add_tablecolumn($tbl, 'user_avatar_link', 'varchar(200)', 'null AFTER `user_avatar`');
$DB->add_tablecolumn($tbl, 'user_timezone', 'varchar(4)', "collate utf8_unicode_ci NOT NULL DEFAULT '0' AFTER `user_gender`");
$DB->add_tablecolumn($tbl, 'user_birthday_mode', 'int(4)', "NOT NULL DEFAULT 0 AFTER `user_birthday`");
$DB->add_tablecolumn($tbl, 'user_facebook', 'varchar(150)', "collate utf8_unicode_ci NOT NULL DEFAULT '' AFTER `user_department`");
$DB->add_tablecolumn($tbl, 'user_twitter', 'varchar(150)', "collate utf8_unicode_ci NOT NULL DEFAULT '' AFTER `user_facebook`");
$DB->add_tablecolumn($tbl, 'avatar_disabled', 'tinyint(1)', "NOT NULL DEFAULT 0");
$DB->query("ALTER TABLE $tbl CHANGE user_twitter user_twitter VARCHAR(150) collate utf8_unicode_ci NOT NULL DEFAULT ''");
$DB->query("ALTER TABLE $tbl CHANGE user_dateformat user_dateformat VARCHAR(128) collate utf8_unicode_ci NOT NULL DEFAULT 'F j, Y g:ia'");
$DB->query("UPDATE $tbl SET user_dateformat = 'F j, Y g:ia' WHERE user_dateformat = 'Y-m-d H:i'");
$DB->query("OPTIMIZE TABLE `$tbl`");

$tbl = PRGM_TABLE_PREFIX.'users_fields';
$DB->add_tablecolumn($tbl, 'ucp_only', 'tinyint(1) unsigned', 'not null default 0 after `is_custom`');
$DB->add_tablecolumn($tbl, 'public_req', 'tinyint(1) unsigned', 'not null default 0 after `public_status`');
$DB->ignore_error = false;

InsertAdminPhrase(11,'profiles_field_public_req','Field is required on public member page and cannot be hidden by user?',2);
InsertAdminPhrase(11,'profiles_field_reg_form','Field will be prompted for input on the registration form?',2);
InsertAdminPhrase(11,'profiles_field_reg_form_req','Field is required to be provided on the registration form by users?',2);
$DB->query("UPDATE $tbl SET ucp_only = 1, public_status = 0 WHERE fieldname IN ('user_allow_newsletter','user_allow_pm','user_allow_viewemail','user_allow_viewonline','user_dateformat','user_dst','user_notify_pm','user_pm_email','user_screen_name','user_sig','user_view_avatars','user_view_sigs')");
if(!$DB->query_first("SELECT 1 FROM $tbl WHERE fieldname = 'user_allow_pm'"))
{
  $DB->query("INSERT INTO $tbl (`fieldnum`, `groupname_id`, `fieldname`, `fieldlabel`, `is_custom`, `ucp_only`, `show_on_reg`, `fieldshow`, `fieldorder`, `crypted`, `fieldlength`, `fieldtype`, `vartype`, `fieldexpr`, `fieldmask`, `errdescr`, `req`, `upd`, `ins`, `seltable`, `readonly`, `public_status`) VALUES
  (NULL, 7, 'user_allow_pm', 'Private Messaging', 0, 1, 0, 1, 32, 0, 1, 'yesno', 'bool', '', '', '*', 0, 1, 1, NULL, 0, '0')");
}
if(!$DB->query_first("SELECT 1 FROM $tbl WHERE fieldname = 'user_timezone'"))
{
  $DB->query("INSERT INTO $tbl (`fieldnum`, `groupname_id`, `fieldname`, `fieldlabel`, `is_custom`, `ucp_only`, `show_on_reg`, `fieldshow`, `fieldorder`,
  `crypted`, `fieldlength`, `fieldtype`, `vartype`, `fieldexpr`, `fieldmask`, `errdescr`, `req`, `upd`, `ins`, `seltable`, `readonly`, `public_status`) VALUES
  (NULL, 7, 'user_timezone', 'Timezone', 0, 1, 0, 1, 100, 0, 5, 'timezone', 'number', '', '', '*', 0, 1, 1, NULL, 0, '0')");
}
if(!$DB->query_first("SELECT 1 FROM $tbl WHERE fieldname = 'user_birthday_mode'"))
{
  $DB->query("INSERT INTO $tbl (`fieldnum`, `groupname_id`, `fieldname`, `fieldlabel`, `is_custom`, `ucp_only`, `show_on_reg`, `fieldshow`, `fieldorder`,
  `crypted`, `fieldlength`, `fieldtype`, `vartype`, `fieldexpr`, `fieldmask`, `errdescr`, `req`, `upd`, `ins`, `seltable`, `readonly`, `public_status`) VALUES
  (NULL, 7, 'user_birthday_mode', 'Birthday Display Mode', 0, 1, 0, 1, 100, 0, 5, 'select', 'string', '0|Age\r\n1|Day and month\r\n2|Full birthday', '', '*', 0, 1, 1, NULL, 0, '0')");
}
if(!$DB->query_first("SELECT 1 FROM $tbl WHERE fieldname = 'user_facebook'"))
{
  $DB->query("INSERT INTO $tbl (`fieldnum`, `groupname_id`, `fieldname`, `fieldlabel`, `is_custom`, `ucp_only`, `show_on_reg`, `fieldshow`, `fieldorder`,
  `crypted`, `fieldlength`, `fieldtype`, `vartype`, `fieldexpr`, `fieldmask`, `errdescr`, `req`, `upd`, `ins`, `seltable`, `readonly`, `public_status`) VALUES
  (NULL, 6, 'user_facebook', 'Facebook Account', 0, 0, 0, 1, 300, 0, 150, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '1')");
}
if(!$DB->query_first("SELECT 1 FROM $tbl WHERE fieldname = 'user_twitter'"))
{
  $DB->query("INSERT INTO $tbl (`fieldnum`, `groupname_id`, `fieldname`, `fieldlabel`, `is_custom`, `ucp_only`, `show_on_reg`, `fieldshow`, `fieldorder`,
  `crypted`, `fieldlength`, `fieldtype`, `vartype`, `fieldexpr`, `fieldmask`, `errdescr`, `req`, `upd`, `ins`, `seltable`, `readonly`, `public_status`) VALUES
  (NULL, 6, 'user_twitter', 'Twitter Account', 0, 0, 0, 1, 310, 0, 150, 'text', 'string', '', '', '*', 0, 1, 1, NULL, 0, '1')");
}
$DB->query("UPDATE $tbl SET fieldlength = 150 WHERE fieldname = 'user_twitter'");
$DB->query_first("UPDATE $tbl SET fieldtype = 'bbcode' WHERE fieldname = 'user_text'");
$DB->query_first("UPDATE $tbl SET fieldtype = 'dateformat' WHERE fieldname = 'user_dateformat'");
$DB->add_tablecolumn($tbl, 'reg_form', 'tinyint(1) unsigned', 'not null default 0');
$DB->add_tablecolumn($tbl, 'reg_form_req', 'tinyint(1) unsigned', 'not null default 0');
$DB->query("OPTIMIZE TABLE `$tbl`");

$tbl = PRGM_TABLE_PREFIX.'usergroups';
$DB->remove_tablecolumn($tbl, 'allow_avatar');
$DB->remove_tablecolumn($tbl, 'avatar_width');
$DB->remove_tablecolumn($tbl, 'avatar_height');
$DB->remove_tablecolumn($tbl, 'avatar_type');
$DB->remove_tablecolumn($tbl, 'avatar_name');
$DB->remove_tablecolumn($tbl, 'allow_pm_send');
$DB->remove_tablecolumn($tbl, 'allow_pm_receive');
$DB->remove_tablecolumn($tbl, 'allow_signature');
$DB->remove_tablecolumn($tbl, 'max_sig_chars');
$excerpt_mode_existed = $DB->column_exists($tbl, 'excerpt_mode');
$DB->add_tablecolumn($tbl, 'excerpt_mode', "TINYINT(1) NOT NULL DEFAULT 0");
$DB->add_tablecolumn($tbl, 'excerpt_message', "mediumtext COLLATE utf8_unicode_ci NULL");
$DB->add_tablecolumn($tbl, 'excerpt_length', "INT(10) NOT NULL DEFAULT 80");
$DB->query("OPTIMIZE TABLE `$tbl`");
InsertAdminPhrase(0, 'enable_subscriptions', 'Enable Subscriptions', 5);

InsertAdminPhrase(0, 'plugin_excerpt_mode', 'Plugins Excerpt Mode', 2);
InsertAdminPhrase(0, 'plugin_excerpt_mode_descr', 'Set this option to "Yes" to output
  only excerpts of Custom Plugins and Article bodies to members of this group (default: No).
  Below the excerpt then the custom message below will be displayed, which could contain
  a note to e.g. register first and login to view the full contents.<br />
  <strong>Note:</strong> this option only reacts to users that are not logged in
  (<strong>Guests</strong> usergroup) and may not be used by all plugins.', 2);
InsertAdminPhrase(0, 'plugin_excerpt_message', 'Plugins Excerpt Message', 2);
InsertAdminPhrase(0, 'plugin_excerpt_message_descr', 'Please enter here the message to be displayed
  below each excerpted content (Custom Plugin, Article) in HTML, if the above <strong>Plugins Excerpt Mode</strong> is set to Yes.<br />
  It may contain the shortcuts <strong>[REGISTER_PATH]</strong> and <strong>[LOGIN_PATH]</strong> as links for the
  registration and login pages.', 2);
InsertAdminPhrase(0, 'plugin_excerpt_length', 'Plugins Excerpt Length', 2);
InsertAdminPhrase(0, 'plugin_excerpt_length_descr', 'Please enter here the maximum amount
  of characters for an excerpt (default: 80).<br />Note: the excerpt is displayed
  as text-only without any HTML tags.', 2);
if(!$excerpt_mode_existed)
{
  $DB->query("UPDATE ".PRGM_TABLE_PREFIX."usergroups SET excerpt_message =
  'To view this content in full please <a href=\"[REGISTER_PATH]\">register</a> with us and <a href=\"[LOGIN_PATH]\">login</a> to our site first.'
  WHERE usergroupid = 4 AND IFNULL(excerpt_message,'')=''");
}

InsertAdminPhrase(0, 'plugins_ignore_excerpt_mode', 'Ignore Excerpt Mode', 2);
InsertAdminPhrase(0, 'plugins_ignore_excerpt_mode_descr', 'If the usergroup of the current
  user has the <strong>Excerpt Mode</strong> active, set this option to
  <strong>Yes</strong> to override the excerpt mode for this custom plugin and
  display it fully (default: No):', 2);

$tbl = PRGM_TABLE_PREFIX.'customplugins';
$DB->add_tablecolumn($tbl, 'ignore_excerpt_mode', 'tinyint(1)', 'NOT NULL DEFAULT 0');
$DB->query("ALTER TABLE $tbl CHANGE displayname displayname varchar(250) collate utf8_unicode_ci NOT NULL DEFAULT ''");
$DB->query("OPTIMIZE TABLE `$tbl`");

$tbl = PRGM_TABLE_PREFIX.'usergroups_config';
$DB->query("CREATE TABLE IF NOT EXISTS $tbl (
`usergroupid` int(10) unsigned NOT NULL DEFAULT 0,
`account_expires_days` int(10) unsigned NOT NULL DEFAULT 0,
`account_expired_group` int(11) unsigned NOT NULL DEFAULT 3,
`pwd_expires_days` int(11) unsigned NOT NULL DEFAULT 0,
`avatar_enabled` int(10) unsigned NOT NULL DEFAULT 0,
`avatar_type` int(4) unsigned NOT NULL DEFAULT 0,
`avatar_max_width` int(10) unsigned NOT NULL DEFAULT 80,
`avatar_max_height` int(10) unsigned NOT NULL DEFAULT 80,
`avatar_max_size` int(10) unsigned NOT NULL DEFAULT 20000,
`avatar_path` VARCHAR(200) collate utf8_unicode_ci NOT NULL DEFAULT '',
`avatar_extensions` VARCHAR(200) collate utf8_unicode_ci NOT NULL DEFAULT 'gif,png,jpg',
`pub_img_enabled` int(10) unsigned NOT NULL DEFAULT 0,
`pub_img_type` int(4) unsigned NOT NULL DEFAULT 0,
`pub_img_max_width` int(10) unsigned NOT NULL DEFAULT 200,
`pub_img_max_height` int(10) unsigned NOT NULL DEFAULT 200,
`pub_img_max_size` int(10) unsigned NOT NULL DEFAULT 40000,
`pub_img_path` VARCHAR(200) collate utf8_unicode_ci NOT NULL DEFAULT '',
`pub_img_extensions` VARCHAR(200) collate utf8_unicode_ci NOT NULL DEFAULT 'gif,png,jpg',
`sig_enabled` int(10) unsigned NOT NULL DEFAULT 0,
`sig_max_chars` int(10) unsigned NOT NULL DEFAULT 400,
`sig_max_chars_raw` int(10) unsigned NOT NULL DEFAULT 1600,
`visitor_page_enabled` int(1) unsigned NOT NULL DEFAULT 0,
`visitor_page_perm_own` int(10) unsigned NOT NULL DEFAULT 0,
`visitor_page_perm_other` int(10) unsigned NOT NULL DEFAULT 0,
`visitor_msg_moderated` int(1) unsigned NOT NULL DEFAULT 0,
`visitor_msg_purge_days` int(10) unsigned NOT NULL DEFAULT 0,
`visitor_msg_expire_days` int(10) unsigned NOT NULL DEFAULT 0,
`visitor_msg_requirevvc` int(1) unsigned NOT NULL DEFAULT 1,
`msg_enabled` int(1) unsigned NOT NULL DEFAULT 0,
`msg_permissions` int(10) unsigned NOT NULL DEFAULT 0,
`msg_inbox_limit` int(10) unsigned NOT NULL DEFAULT 50,
`msg_keep_limit` int(10) unsigned NOT NULL DEFAULT 50,
`msg_keep_days` int(10) unsigned NOT NULL DEFAULT 30,
`msg_purge_days` int(10) unsigned NOT NULL DEFAULT 0,
`msg_recipients_limit` int(10) unsigned NOT NULL DEFAULT 5,
`msg_notification` int(10) unsigned NOT NULL DEFAULT 0,
`msg_requirevvc` int(1) unsigned NOT NULL DEFAULT 1,
`email_template_id` int(10) NOT NULL DEFAULT 0,
PRIMARY KEY (`usergroupid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query('INSERT INTO '.$tbl.' (usergroupid) SELECT usergroupid FROM '.
           PRGM_TABLE_PREFIX.'usergroups ug WHERE NOT EXISTS(SELECT 1 FROM '.
           $tbl.' uc WHERE uc.usergroupid = ug.usergroupid)');
$DB->add_tablecolumn($tbl, 'enable_attachments', 'tinyint(1) unsigned', 'not null default 0');
$DB->add_tablecolumn($tbl, 'attachments_max_size', 'int(11) unsigned', 'not null default 2048');
$DB->add_tablecolumn($tbl, 'attachments_extensions', 'varchar(200) collate utf8_unicode_ci ', "not null default '*'");
$DB->add_tablecolumn($tbl, 'enable_subscriptions', 'tinyint(1) unsigned', 'not null default 0');
$DB->query("OPTIMIZE TABLE `$tbl`");

$tbl = PRGM_TABLE_PREFIX.'attachments';
$DB->query("CREATE TABLE IF NOT EXISTS ".$tbl."(
`attachment_id`       INT(10) UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
`pluginid`            INT(10) UNSIGNED  NOT NULL DEFAULT 0,
`area`                VARCHAR(20)       collate utf8_unicode_ci NOT NULL DEFAULT '',
`objectid`            INT(10) UNSIGNED  NOT NULL DEFAULT 0,
`usersystemid`        INT(10) UNSIGNED  NOT NULL DEFAULT 0,
`userid`              INT(10) UNSIGNED  NOT NULL DEFAULT 0,
`attachment_name`     VARCHAR(255)      collate utf8_unicode_ci NOT NULL DEFAULT '',
`filename`            VARCHAR(255)      collate utf8_unicode_ci NOT NULL DEFAULT '',
`filesize`            INT(10) UNSIGNED  NOT NULL DEFAULT 0,
`filetype`            VARCHAR(64)       collate utf8_unicode_ci NOT NULL DEFAULT '',
`username`            VARCHAR(128)      collate utf8_unicode_ci NOT NULL DEFAULT '',
`download_count`      INT(11) UNSIGNED  NOT NULL DEFAULT 0,
`uploaded_date`       INT(11) UNSIGNED  NOT NULL DEFAULT 0,
`access_view`         VARCHAR(128)      collate utf8_unicode_ci NOT NULL DEFAULT '',
KEY (`pluginid`),
KEY (`area`,`objectid`),
KEY (`usersystemid`,`userid`),
KEY (`filename`),
KEY (`username`))
ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

// Make User Control Panel also "allow download"-configurable for attachments
// inside the messaging system
$DB->query('UPDATE {plugins} SET settings = 23 WHERE pluginid = 11');

InsertAdminPhrase(3, 'page_targeting_desc', 'Display only the latest articles of the page which the latest articles plugin resides in.<br />Selecting "Yes" will disable the "Page Targeting" setting.',2);

// UserCP (p11)
InsertAdminPhrase(11,'pm_email_subject_descr','Email subject for email notifications sent to users?<br />
  This may include the following macros: [sitename], [siteurl], [firstname], [lastname], [username], [date], [sendername]',2);
InsertAdminPhrase(11,'pm_email_body_descr','Email message for email notifications sent to users?<br />
  This may include the following macros: [sitename], [siteurl], [firstname], [lastname], [username], [date], [sendername], [message], [messagetitle]',2);
InsertAdminPhrase(11,'settings', 'Settings', 2);
InsertAdminPhrase(11,'view_settings', 'View Settings', 2);
InsertAdminPhrase(11,'view_settings_descr', 'View and edit settings for this plugin.', 2);
DeleteAdminPhrase(11,'profiles_col_on_public_profile',2);
InsertAdminPhrase(11,'profiles_col_on_public_profile','Allowed on<br />Member Page?',2);
DeleteAdminPhrase(11,'profiles_col_visible_on_frontpage',2);
InsertAdminPhrase(11,'profiles_col_visible_on_frontpage','Allowed on<br />Member Page?',2);
InsertAdminPhrase(11,'profiles_col_group','Order &amp; Group',2);
InsertAdminPhrase(11,'profiles_col_reg_form','Registration<br />Form?',2);
InsertAdminPhrase(11,'profiles_col_reg_form_req','Reg.Form<br />required?',2);
InsertAdminPhrase(11,'profiles_col_permissions','View<br />Permissions',2);
InsertAdminPhrase(11,'profiles_col_friendsonly','Only<br />Friends',2);
InsertAdminPhrase(11,'profiles_col_public_req', 'Required on<br />Member Page?', 2);
InsertAdminPhrase(11,'lbl_hide', 'Hide', 2);
InsertAdminPhrase(11,'lbl_show', 'Show', 2);

InsertPluginSetting(11, 'Options', 'PM Email Address', 'With which email address should email notifications be sent to users?<br />Leave empty to use the technical email address configured in Main Settings.', 'text', '', 10);
InsertPluginSetting(11, 'Options', 'PM Email Subject', 'Email subject for email notifications sent to users?<br />
  This may include the following macros: [sitename], [siteurl], [firstname], [lastname], [username], [date], [sendername]', 'text', 'Private Message Notification', 20);
InsertPluginSetting(11, 'Options', 'PM Email Includes Message', 'Include the private message text within the email body (default: No)?', 'yesno', '0', 25);
InsertPluginSetting(11, 'Options', 'PM Email Body', 'Email message for email notifications sent to users?<br />
  This may include the following macros: [sitename], [siteurl], [firstname], [lastname], [username], [date], [sendername], [message], [messagetitle]', 'wysiwyg',
  'DO NOT REPLY TO THIS EMAIL!
***************************

Dear [firstname],

You have received a new private message on [date] from [sendername],
titled "[messagetitle]".

To read the original version, respond to, or delete this message, please login
to your profile page here:
http://www.yourdomain.com/profilepage.html

This is the message that was sent:
***************
[message]
***************

Again, please do not reply to this email. You must go to the following page
to reply to this private message:
http://www.yourdomain.com/profilepage.html

All the best,
', 30);

InsertAdminPhrase(11,'subscription_email_subject_descr','Email subject for subscription email notifications sent to users?<br />
  This may include the following macros: [sitename], [siteurl], [username], [date], [type], [pluginname], [pagename]',2);
InsertAdminPhrase(11,'subscription_email_body_descr','Email message for subscription email notifications sent to users?<br />
  This may include the following macros: [sitename], [siteurl], [username], [date], [title], [type], [pluginname], [pagename], [pagelink]',2);
InsertPluginSetting(11, 'Options', 'Subscription Email Subject', 'Email subject for subscription email notifications sent to users?<br />
  This may include the following macros: [sitename], [siteurl], [username], [date], [type], [pluginname], [pagename]',
  'text', 'Subscription Update Notification', 80);
InsertPluginSetting(11, 'Options', 'Subscription Email Body', 'Email message for subscription email notifications sent to users?<br />
  This may include the following macros: [sitename], [siteurl], [username], [date], [title], [type], [pluginname], [pagename], [pagelink]', 'wysiwyg',
  'DO NOT REPLY TO THIS EMAIL!<br>
***************************<br>
<br>
Dear [username],<br>
<br>
the [type] "<strong>[title]</strong>", to which you subscribed, has been updated [date].<br>
<br>
You can visit it here: <strong>[pagelink]</strong>.<br>
<br>
Again, please do not reply to this email. To disable this type of notification for any of your subscribed items, please login and visit your <strong>[profilepage]</strong>.<br>
<br>
There may also be other replies, but you will not receive any more notifications until you visit the website again.<br>
<br>
All the best,<br>
[sitename]<br>', 85);
InsertPluginSetting(11, 'Options', 'Disable Guest Member Page Access', 'Disable access to any member page for guests (default: No)?', 'yesno', '0', 200);

InsertPhrase(11,'msg_fill_out_field','Please fill out:');
InsertPhrase(11,'msg_invalid_user','<strong>Invalid user!</strong><br />');
InsertPhrase(11, 'select_profile_user_timezone', 'Timezone');
DeletePhrase(11, 'page_createmessage_title');
InsertPhrase(11, 'page_newmessage_title',       'Send New Message');
InsertPhrase(11, 'page_viewdiscussions_title',  'View Discussions');
InsertPhrase(11, 'page_viewdiscussion_title',   'View Discussion');
InsertPhrase(11, 'page_viewmessages_title',     'View Messages');
InsertPhrase(11, 'page_viewsentmessages_title', 'View Sent Messages');
InsertPhrase(11, 'page_viewmessage_title',      'View Message');
InsertPhrase(11, 'page_picture_title',          'Profile Picture');
InsertPhrase(11, 'page_avatar_title',           'Avatar');
InsertPhrase(11, 'page_mycontent_title',        'My Content');
InsertPhrase(11, 'page_recent_title',           'Recent Messages');
InsertPhrase(11, 'page_subscriptions_title',    'View Subscriptions',true);
InsertPhrase(11, 'page_member_contact_title',   'Contact Member');
InsertPhrase(11, 'no_info_available',           'There is currently no information available here.');
InsertPhrase(11, 'content_not_available',       'We are sorry, but the requested content is not available!');
InsertPhrase(11, 'my_control_panel',            'My Control Panel');
InsertPhrase(11, 'title_member_page',           'Member Page');
InsertPhrase(11, 'my_member_page',              'My Member Page');
InsertPhrase(11, 'edit',                        'Edit');
InsertPhrase(11, 'view',                        'View');
InsertPhrase(11, 'unsubscribe',                 'Unsubscribe');
InsertPhrase(11, 'message_error1',              'Page was called with invalid parameters!');
InsertPhrase(11, 'message_error2',              'Message is missing a recipient or recipient rejected the message!');
InsertPhrase(11, 'message_error4',              'The message title was missing!');
InsertPhrase(11, 'message_error5',              'The security code was empty or invalid!');
InsertPhrase(11, 'message_not_logged_in',       '<h2>You must be logged in to view the Control Panel.</h2>');
InsertPhrase(11, 'message_not_allowed',         '<p>Your account does not have permission for this feature.</p>');
InsertPhrase(11, 'messaging_disabled',          'Messaging is not available for your account.');
InsertPhrase(11, 'image_error-1',               'Unsupported image type');
InsertPhrase(11, 'image_error-2',               'Wrong image extension');
InsertPhrase(11, 'image_error-3',               'No image found');
InsertPhrase(11, 'image_error-4',               'Thumbnail error');
InsertPhrase(11, 'image_error-5',               'Image filesize exceeds limit');
InsertPhrase(11, 'image_error-6',               'Image width exceeds limit');
InsertPhrase(11, 'image_error-7',               'Image height exceeds limit');
InsertPhrase(11, 'image_error-8',               'Upload error');
InsertPhrase(11, 'image_error-9',               'Invalid image file or type');
InsertPhrase(11, 'image_error-10',              'Failed to save image (no folder)');
InsertPhrase(11, 'image_error-11',              'Failed to save image (folder not writable)');
InsertPhrase(11, 'attachments',               'Attachments:');
InsertPhrase(11, 'delete_attachment_prompt',  'Delete attachment?');
InsertPhrase(11, 'err_msg_fail_incomplete',   'Message could not be sent (missing data)!');
InsertPhrase(11, 'err_recipients_empty',      'Please add at least one name as recipient!');
InsertPhrase(11, 'err_recipients_limit',      'Recipient limit reached, cannot add further names!');
InsertPhrase(11, 'err_invalid_recipient',     'Message could not be send! Following user does not receive messages:');
InsertPhrase(11, 'err_body_missing',          'Message body is missing!');
InsertPhrase(11, 'err_title_missing',         'Message title is missing!');
InsertPhrase(11, 'remove_recipient_prompt',   'Really remove recipient from list?');
InsertPhrase(11, 'err_not_send',              'Message not send, please check your input!');
InsertPhrase(11, 'section_messages',          'Messages &amp; Discussions');
InsertPhrase(11, 'section_account',           'Account Details &amp; Settings');
InsertPhrase(11, 'section_pictures',          'Profile Picture &amp; Avatar');
InsertPhrase(11, 'section_member_info',       'Member Information');
InsertPhrase(11, 'section_options',           'Options');
InsertPhrase(11, 'active',                    'Active');
InsertPhrase(11, 'restrictions',              'Restrictions:');
InsertPhrase(11, 'image',                     'Image');
InsertPhrase(11, 'avatar_current_link',       'Current link:');
InsertPhrase(11, 'avatar_delete_image',       'Delete existing image?');
InsertPhrase(11, 'avatar_external_link',      'Link to an external image:');
InsertPhrase(11, 'avatar_new_upload',         'Upload a new avatar image:');
InsertPhrase(11, 'avatar_source',             'Avatar Source');
InsertPhrase(11, 'avatar_upload_disabled',    '[---Upload Disabled---]');
InsertPhrase(11, 'avatar_hint',               'An avatar image, if available, is usually displayed in combination with
a user\'s name, such as in a forum topic or comments, and is usually a small sized-image.<br />
This is not the same as the profile picture, which is especially for the public member page.');
InsertPhrase(11, 'btn_delete_message',        'Delete Message');
InsertPhrase(11, 'btn_send_message',          'Send Message');
InsertPhrase(11, 'discussion_col_date',       'Last Activity');
InsertPhrase(11, 'status_no_messages',        'No messages.');
InsertPhrase(11, 'status_no_subscriptions',   'There are no subscriptions in your account.');
InsertPhrase(11, 'status_no_unread_messages', 'No unread messages found.');
InsertPhrase(11, 'status_has_unread_messages','Unread Messages');
InsertPhrase(11, 'displayname_label',         'Your displayed username:');
InsertPhrase(11, 'participants',              'Participants:');
InsertPhrase(11, 'and_x_more',                'and #x# more');
InsertPhrase(11, 'additional_participants',   'Additional Participants:');
InsertPhrase(11, 'invite_others_discussion',  'Invite members to discussion:');
InsertPhrase(11, 'additional_recipients',     'Additional Recipients:');
InsertPhrase(11, 'your_recent_messages',      'Your recent messages or discussions:');
InsertPhrase(11, 'untitled',                  'untitled');
InsertPhrase(11, 'msg_col_date',              'Date');
InsertPhrase(11, 'msg_col_title',             'Title');
DeletePhrase(11, 'msg_col_discussion');
InsertPhrase(11, 'msg_col_started_by',        'Started by');
InsertPhrase(11, 'msg_col_sender',            'Sender');
InsertPhrase(11, 'msg_col_recipient',         'Recipient');
InsertPhrase(11, 'msg_col_replies',           'Replies');
InsertPhrase(11, 'msg_avatar_not_available',  'Avatar customisation is not available for your account.');
InsertPhrase(11, 'msg_only_gravatar_available','<p>Currently we have <a href="http://www.gravatar.com">Gravatars</a> enabled. Visit their site to
  link an avatar image to your email address, which will also be supported by many other sites.</p>');
InsertPhrase(11, 'msg_must_use_forum_profile','Please use your forum profile to edit your avatar image.');
InsertPhrase(11, 'lbl_attachment_extensions', 'allowed extensions:');
InsertPhrase(11, 'lbl_attachment_max_size',   'allowed max. filesize (in KB):');
InsertPhrase(11, 'lbl_discussions_title',     'Discussions');
InsertPhrase(11, 'lbl_discussion',            'Discussion');
InsertPhrase(11, 'lbl_discussion_closed',     'Closed');
InsertPhrase(11, 'lbl_inbox_title',           'Inbox');
InsertPhrase(11, 'lbl_outbox_title',          'Sent Messages');
InsertPhrase(11, 'lbl_recipient',             'Recipient:');
InsertPhrase(11, 'lbl_selected_recipients',   'Selected Recipient(s):');
InsertPhrase(11, 'lbl_message_from',          'From:');
InsertPhrase(11, 'lbl_message_title',         'Title:');
InsertPhrase(11, 'lbl_message_text',          'Message Text:');
InsertPhrase(11, 'lbl_message_options',       '<strong>Message Options</strong>');
InsertPhrase(11, 'lbl_message_options_switch','Show/Hide additional options');
InsertPhrase(11, 'lbl_message_allow_invites', 'Allow Invitations?');
InsertPhrase(11, 'lbl_message_invites_hint',  'If not private, allow participants to invite other users to take join?');
InsertPhrase(11, 'lbl_message_type',          'Message Type:');
InsertPhrase(11, 'lbl_private_message',       'Private Message');
InsertPhrase(11, 'lbl_private_message_hint',  'If private, each recipient will receive the message individually. Otherwise a new discussion is started to which all recipients can reply to and read all each other\'s messages.');
InsertPhrase(11, 'lbl_public_option',         'Public?');
InsertPhrase(11, 'lbl_search_users',          'Search Users:');
InsertPhrase(11, 'lbl_status_subtitle',       'Your status (displayed on your profile and messages):');
InsertPhrase(11, 'lbl_subscription_options',  'Unsubscribe from selected items:');
InsertPhrase(11, 'lbl_latest_topic',          'Latest Topic:');
InsertPhrase(11, 'lbl_notification',          'Notification');
InsertPhrase(11, 'msg_subscriptions_updated', 'Your subscriptions have been updated.');
InsertPhrase(11, 'option_no_email',           'No Email');
InsertPhrase(11, 'option_instant_email',      'Instant');
InsertPhrase(11, 'lbl_topic',                 'Topic:');
InsertPhrase(11, 'lbl_user_profile_page',     'user profile page');
InsertPhrase(11, 'lbl_unread',                'unread');
InsertPhrase(11, 'lbl_replies',               'Replies:');
InsertPhrase(11, 'lbl_views',                 'Views:');
InsertPhrase(11, 'lbl_view_all_messages',     'View All Messages');
InsertPhrase(11, 'lbl_message_sent',          'Sent:');
InsertPhrase(11, 'lbl_sent_by',               'Sent by:');
InsertPhrase(11, 'lbl_add_message',           'Reply to Discussion',true);
InsertPhrase(11, 'lbl_add_message_hint',      'Post a new message to the current discussion');
InsertPhrase(11, 'lbl_quote',                 'Quote');
InsertPhrase(11, 'lbl_quoted_message',        'Quoted Message:');
InsertPhrase(11, 'lbl_quote_hint',            'Quote this message inside the discussion');
InsertPhrase(11, 'lbl_your_reply',            'Your Reply:');
InsertPhrase(11, 'lbl_reply_to_message',      'Reply to this message');
InsertPhrase(11, 'lbl_private_quote',         'Quote this message as a private message');
InsertPhrase(11, 'lbl_private_subtitle',      'Send a message to one or more recipients.<br />Note: Every recipient must be selected from the popup list in order to be added to the recipient list.');
InsertPhrase(11, 'lbl_quote_as_private',      'Quote privately');
InsertPhrase(11, 'lbl_read_discussion',       'Read discussion');
InsertPhrase(11, 'lbl_delete_message',        'Delete message?');
InsertPhrase(11, 'lbl_delete_message_hint',   'If checked and the send button is clicked, your reply (if entered) will be sent and this message deleted.');
InsertPhrase(11, 'lbl_delete_message_hint2',  'Check above option and click the button to delete this message.');
InsertPhrase(11, 'lbl_latest_comment_by',     'Latest comment by');
DeletePhrase(11, 'lbl_save_message_copy');
InsertPhrase(11, 'lbl_save_copy',             'Save Copy?');
InsertPhrase(11, 'lbl_save_copy_hint',        'If checked, a copy of this message will be saved in your outbox folder.');
InsertPhrase(11, 'lbl_selected_messages',     'Selected Messages:');
InsertPhrase(11, 'lbl_request_msg_read',      'Message-read Notification?');
InsertPhrase(11, 'lbl_request_msg_read_hint', 'If checked, you will receive a notification message when a recipient has read this message.');
InsertPhrase(11, 'lbl_re',                    'Re:');
InsertPhrase(11, 'lbl_bcc',                   'BCC (send blind copies to others?)');
InsertPhrase(11, 'lbl_bcc_hint',              'You may send your message to up to <strong>#d#</strong> people at a time.');
InsertPhrase(11, 'lbl_discussion_options',    'Discussion Options:');
InsertPhrase(11, 'lbl_options_go',            'Go');
InsertPhrase(11, 'msg_conf_your_message',     'Your message:');
InsertPhrase(11, 'msg_conf_to',               'to:');
InsertPhrase(11, 'msg_conf_was_read',         'was read:');
InsertPhrase(11, 'msg_conf_footer',           '*** Do not reply to this automated message! ***');
InsertPhrase(11, 'msg_conf_title',            'Message read:');
InsertPhrase(11, 'msg_leave_error',           'Not all selected discussions were left, at least one was created by yourself!');
InsertPhrase(11, 'options_select',            '---Select Operation---');
InsertPhrase(11, 'options_mark_all_read',     'Mark all read');
InsertPhrase(11, 'options_mark_discussion_read','Mark discussion read');
InsertPhrase(11, 'options_mark_selected_read','Mark all selected as read');
InsertPhrase(11, 'options_move',              'Move to Folder');
InsertPhrase(11, 'options_invite_user',       'Invite User');
InsertPhrase(11, 'options_uninvite_user',     'Uninvite User');
InsertPhrase(11, 'options_approve',           'Approve');
InsertPhrase(11, 'options_unapprove',         'Unapprove');
InsertPhrase(11, 'options_close',             'Close Discussion');
InsertPhrase(11, 'options_open',              'Open Discussion');
InsertPhrase(11, 'options_leave',             'Leave Discussion');
InsertPhrase(11, 'options_ignore',            'Ignore User(s)');
InsertPhrase(11, 'options_unignore',          'Unignore User(s)');
InsertPhrase(11, 'options_buddy',             'Add user to Buddy list');
InsertPhrase(11, 'options_unbuddy',           'Remove user from Buddy list');
InsertPhrase(11, 'options_friend',            'Add user to Friends list');
InsertPhrase(11, 'options_unfriend',          'Remove user from Friends list');
InsertPhrase(11, 'options_delete',            'Delete');
InsertPhrase(11, 'forum_messages',            'Forum Messages');
InsertPhrase(11, 'lbl_forum',                 'Forum');
InsertPhrase(11, 'lbl_pagesize',              'Pagesize');
InsertPhrase(11, 'lbl_latest_post',           'Latest Post: by');
InsertPhrase(11, 'lbl_created_by',            'Created by');

InsertPhrase(1, 'enter_verify_math',          'Please enter the result of the following math question:');
InsertPhrase(1, 'upload_err_0',               'File upload successfull');
InsertPhrase(1, 'upload_err_1',               'File size exceeds server limit');
InsertPhrase(1, 'upload_err_2',               'File size exceeds server limit');
InsertPhrase(1, 'upload_err_3',               'File upload is broken');
InsertPhrase(1, 'upload_err_6',               'Server unable to store file');
InsertPhrase(1, 'upload_err_invalid_ext',     'File extension not allowed');
InsertPhrase(1, 'upload_err_size_limit',      'File size exceeds allowed size limit');
InsertPhrase(1, 'upload_err_access_error',    'File not accessible on server');
InsertPhrase(1, 'upload_err_general',         'Sorry, upload failed');

InsertPhrase(1, 'unsubscribe',                'Unsubscribe');
InsertPhrase(1, 'unsubscribed',               'You were successfully unsubscribed!');
InsertPhrase(1, 'unsubscribe_from_comments',  'Unsubscribe from these comments.');
InsertPhrase(1, 'subscribe',                  'Subscribe');
InsertPhrase(1, 'subscribed',                 'You were successfully subscribed!');
InsertPhrase(1, 'subscribe_to_comments',      'Subscribe to these comments.');
InsertPhrase(1, 'send_private_message',       'Send user a private message');

// Clean up potentially duplicate entries detected since 341
$dupe_count = 0;
if($getdupes=$DB->query('SELECT adminpageid, pluginid, varname, count(*) phrasecount, min(adminphraseid) phraseminid
FROM '.PRGM_TABLE_PREFIX.'adminphrases a1
GROUP BY adminpageid, pluginid, varname
HAVING COUNT(*) > 1'))
{
  while($row = $DB->fetch_array($getdupes,null,MYSQL_ASSOC))
  {
    if($row['phrasecount'] > 1)
    {
      $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'adminphrases'.
                 " WHERE adminpageid = %d AND pluginid = %d AND varname = '%s'".
                 ' AND adminphraseid > %d',
                 $row['adminpageid'], $row['pluginid'], $DB->escape_string($row['varname']), $row['phraseminid']);
      if($count = $DB->affected_rows())
      {
        $dupe_count += $count;
        echo $count.' duplicate phrase(s) removed for "'.$row['varname'].'" (pluginid: '.$row['pluginid'].')<br />';
      }
    }
  }
}
if($dupe_count)
{
  echo '<strong>'.$dupe_count.' duplicate phrase(s) removed in total.</strong><br /><br />';
}
sleep(2);

DeleteAdminPhrase(11,'users_profile_permissions',2);
InsertAdminPhrase(0,'users_profile_permissions', 'Users Profile Permissions', 5);
InsertAdminPhrase(0,'account_expires_days', 'Account expiration in days (after first login):', 5);
InsertAdminPhrase(0,'account_expired_group', 'Expired accounts move to usergroup:', 5);
InsertAdminPhrase(0,'pwd_expires_days', 'Password expires after (days):', 5);
InsertAdminPhrase(0,'pub_img_enabled', 'Enable public profile images:', 5);
InsertAdminPhrase(0,'pub_img_type', 'Allowed profile images types (upload, url):', 5);
InsertAdminPhrase(0,'pub_img_max_width', 'Profile image max. width (in pixels):', 5);
InsertAdminPhrase(0,'pub_img_max_height', 'Profile image max. height (in pixels):', 5);
InsertAdminPhrase(0,'pub_img_max_size', 'Profile image max. upload filesize:', 5);
InsertAdminPhrase(0,'pub_img_path', 'Storage path for profile images (relative to '.PRGM_NAME.' base folder):', 5);
InsertAdminPhrase(0,'pub_img_extensions', 'Allowed profile image extensions (e.g. gif,png,jpg):', 5);
InsertAdminPhrase(0,'sig_enabled', 'Enable user signatures:', 5);
InsertAdminPhrase(0,'sig_max_chars', 'Signature max. length (excl. BBCode):', 5);
InsertAdminPhrase(0,'sig_max_chars_raw', 'Signature max. raw characters:', 5);
InsertAdminPhrase(0,'visitor_page_enabled', 'Enable public visitor page:', 5);
InsertAdminPhrase(0,'visitor_page_perm_own', 'Visitor page permissions for user (e.g. delete):', 5);
InsertAdminPhrase(0,'visitor_page_perm_other', 'Visitor page permissions for visitors (e.g. leave comment):', 5);
InsertAdminPhrase(0,'visitor_msg_moderated', 'Moderate any visitor comments by default:', 5);
InsertAdminPhrase(0,'visitor_msg_purge_days', 'Delete ALL visitor messages EVERY x days (0 to disable):', 5);
InsertAdminPhrase(0,'visitor_msg_expire_days', 'Delete unapproved visitor messages when older than x days (0 to disable):', 5);
InsertAdminPhrase(0,'visitor_msg_requirevvc', 'Visitors must enter security code confirmation:', 5);
InsertAdminPhrase(0,'msg_enabled', 'Enable private messaging/discussions:', 5);
InsertAdminPhrase(0,'msg_permissions', 'Messaging Permissions:', 5);
InsertAdminPhrase(0,'msg_inbox_limit', 'Limit for inbox folder (if full, user cannot receive any further messages; default: 50):', 5);
InsertAdminPhrase(0,'msg_keep_limit', 'Limit for outbox folder (if full, user cannot keep any message copies; default: 50):', 5);
InsertAdminPhrase(0,'msg_keep_days', 'Delete outbox messages older than x days (0 to disable):', 5);
InsertAdminPhrase(0,'msg_purge_days', 'Delete ALL messages EVERY x days (0 to disable):', 5);
InsertAdminPhrase(0,'msg_recipients_limit', 'Number of concurrent recipients per message (default: 5)', 5);
InsertAdminPhrase(0,'msg_notification', 'Enable email notification for new messages:', 5);
InsertAdminPhrase(0,'msg_requirevvc', 'Users must enter security code confirmation with each message (to prevent server flodding; default: yes):', 5);
InsertAdminPhrase(0,'email_template_id', 'Default email template for notifications:', 5);
InsertAdminPhrase(0,'enable_attachments', 'Enable attachments for messaging (default: No):', 5);
InsertAdminPhrase(0,'attachments_max_size', 'Max. filesize (in KB) for attachments (default: 2048):', 5);
InsertAdminPhrase(0,'attachments_extensions', 'Allowed file extensions for attachments separated by comma (* = all; empty = disabled):', 5);
DeleteAdminPhrase(0,'avatar_support', 5);
DeleteAdminPhrase(0,'avatar_type', 5);
InsertAdminPhrase(0,'avatar_enabled', '<strong>Avatars support</strong><br />Check the allowed avatar options which are available for all users within this group:', 5);
InsertAdminPhrase(0,'avatar_extensions', 'Allowed image file extensions for uploads (separate entries by single comma):<br />Default: gif,png,jpg', 5);
InsertAdminPhrase(0,'users_check_all', 'Check all/none',5);
InsertAdminPhrase(0,'avatar_max_width', 'Avatar resizing to width in pixels (default: 80):', 5);
InsertAdminPhrase(0,'avatar_max_height', 'Avatar resizing to height in pixels (default: 80):', 5);
InsertAdminPhrase(0,'avatar_max_size', 'Avatar max. uploaded filesize in bytes:<br />The minimum value is <strong>2048</strong>, the default is <strong>20480</strong> (= 20KB).', 5);
InsertAdminPhrase(0,'avatar_path', 'Storage path for avatar images, relative to '.PRGM_NAME.' base folder (default: images/avatars/):<br />
  <strong>Note:</strong> this folder must be made writable on the server (e.g. by FTP program)<br />
  If left empty, avatar image upload will not work!', 5);
InsertAdminPhrase(0,'users_check_all', 'Check all/none',5);
InsertAdminPhrase(0,'users_remove_avatar', 'Remove user avatar?',5);
InsertAdminPhrase(0,'users_disable_avatar', 'Disable any avatar for user?',5);

$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'users_data', 'user_googletalk', 'varchar(255) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");
$DB->query('UPDATE `'.PRGM_TABLE_PREFIX.'users_data` SET msg_in_count = (SELECT COUNT(msg_id) FROM '.PRGM_TABLE_PREFIX.'msg_messages mm WHERE mm.master_id = 0 AND mm.recipient_id = '.PRGM_TABLE_PREFIX.'users_data.userid AND mm.outbox_copy = 0 AND mm.usersystemid = '.PRGM_TABLE_PREFIX.'users_data.usersystemid)');
$DB->query('UPDATE `'.PRGM_TABLE_PREFIX.'users_data` SET msg_out_count = (SELECT COUNT(msg_id) FROM '.PRGM_TABLE_PREFIX.'msg_messages mm WHERE mm.master_id = 0 AND mm.userid = '.PRGM_TABLE_PREFIX.'users_data.userid AND mm.outbox_copy = 1 AND mm.usersystemid = '.PRGM_TABLE_PREFIX.'users_data.usersystemid)');
$DB->query('UPDATE `'.PRGM_TABLE_PREFIX."users_fields` SET fieldtype = 'ext_aim' WHERE fieldname = 'user_aim'");
$DB->query('UPDATE `'.PRGM_TABLE_PREFIX."users_fields` SET fieldtype = 'ext_fb' WHERE fieldname = 'user_facebook'");
$DB->query('UPDATE `'.PRGM_TABLE_PREFIX."users_fields` SET fieldtype = 'ext_icq' WHERE fieldname = 'user_icq'");
$DB->query('UPDATE `'.PRGM_TABLE_PREFIX."users_fields` SET fieldtype = 'ext_msnm' WHERE fieldname = 'user_msnm'");
$DB->query('UPDATE `'.PRGM_TABLE_PREFIX."users_fields` SET fieldtype = 'ext_skype' WHERE fieldname = 'user_skype'");
$DB->query('UPDATE `'.PRGM_TABLE_PREFIX."users_fields` SET fieldtype = 'ext_twitter' WHERE fieldname = 'user_twitter'");
$DB->query('UPDATE `'.PRGM_TABLE_PREFIX."users_fields` SET fieldtype = 'ext_yim' WHERE fieldname = 'user_yim'");

if($res=$DB->query_first("SELECT 1 FROM ".PRGM_TABLE_PREFIX."users_fields WHERE fieldname = 'user_googletalk'"))
{
  if(empty($res[0]))
  $DB->query("INSERT INTO ".PRGM_TABLE_PREFIX."users_fields (`fieldnum`, `groupname_id`, `fieldname`, `fieldlabel`, `is_custom`, `ucp_only`, `show_on_reg`, `fieldshow`, `fieldorder`, `crypted`, `fieldlength`, `fieldtype`, `vartype`, `fieldexpr`, `fieldmask`, `errdescr`, `req`, `upd`, `ins`, `seltable`, `readonly`, `public_status`, `public_req`, `noforum`, `reg_form`, `reg_form_req`) VALUES
  (NULL, 6, 'user_googletalk', 'Google Talk', 0, 0, 0, 1, 4, 0, 255, 'ext_googletalk', 'string', '', '', '*', 0, 1, 1, NULL, 0, '1', 0, 0, 0, 0)");
}

InsertPhrase(1,'short_username_available','Username available');
InsertPhrase(1,'short_username_not_available','Username not available');
InsertPhrase(1,'no_view_access_title','Page not available');
InsertPhrase(1,'no_view_access_title_guests','Page not available');
InsertPhrase(1,'no_view_access_guests','We are sorry, but this page is only available to registered members.');

InsertPhrase(12, 'new_user_registration', 'New user registration:');
InsertPhrase(12, 'ip_address_not_allowed', '<p>We are sorry, but we do not accept new registrations from your IP address.</p>');
InsertPhrase(12, 'lbl_i_agree', 'I agree');
InsertPhrase(12, 'lbl_terms_conditions', 'Terms and Conditions');
InsertPhrase(12, 'error_must_accept_terms', 'You must accept our terms and conditions in order to signup with us.');
InsertPhrase(12, 'lbl_terms_conditions_hint', 'Please confirm to have read and accepted our terms and conditions to sign-up now:');
InsertPhrase(12, 'registration_awaits_approval', 'Thank you for registering with us.<br />Your account is not yet active as it requires approval by our staff.<br />');

InsertAdminPhrase(12, 'prevention_options', 'Prevention', 2);
InsertAdminPhrase(12, 'welcome_options', 'Welcome Message', 2);
$tmp = 'UPDATE '.PRGM_TABLE_PREFIX.'pluginsettings SET ';
$DB->query($tmp."groupname = 'prevention_options' WHERE pluginid = 12 AND title IN ('banned_emails','banned_ip_addresses','invalid_usernames','prohibit_multiple_users_per_ip')");
$DB->query($tmp."groupname = 'welcome_options' WHERE pluginid = 12 AND title IN ('send_welcome_email_message','welcome_message_subject','welcome_message_text','welcome_message_email_sender')");
$DB->query($tmp."displayorder = 8 WHERE pluginid = 12 AND title = 'default_signup_usergroup'");
unset($tmp);

InsertPluginSetting(12, 'user_registration_settings', 'New Registration Admin Email',
  'Specify here an email address which will receive an email notification for each new user registration (empty = disabled)?<br />
  This is not used if a forum is integrated.', 'text', '', 7);
InsertPluginSetting(12, 'user_registration_settings', 'Require Admin Activation',
  'If the option for <strong>email activation</strong> is disabled (set to No), should all new registrations
  require the manual activation by an administrator (Yes) or be activated instantly (No)?<br />
  If enabled, this may provide more control over who should be allowed into the site.<br />
  This is not used if a forum is integrated.', 'yesno', '0', 7);
InsertPluginSetting(12, 'user_registration_settings', 'Disable Forgot Password',
  'Disable the use of all forgot password links to prevent misuse and increase security (default: No):<br />Note: also check User Login Panel plugin settings for option to display the link.', 'yesno', '0', 8);
InsertPluginSetting(12, 'user_registration_settings', 'Terms and Conditions Display',
  'Please enter here any Terms and Conditions for your website which the user must read and confirm in order to be allowed to sign-up:<br />
  If this is left empty, no text and checkbox for the user to check is displayed.<br />
  In some countries it may be legally required to provide/display terms and conditions.', 'wysiwyg', '', 30);
InsertPluginSetting(12, 'user_registration_settings', 'Default Signup Usergroup',
  'Please select the default usergroup a newly registered user will be assigned to (default: Registered):', 'usergroup', '3', 8);
InsertPluginSetting(12, 'welcome_options', 'Send Welcome Email Message',
  'Should a welcome email message be sent to the user when the registration email is confirmed (default: No):', 'yesno', '0', 50);
InsertPluginSetting(12, 'welcome_options', 'Welcome Message Subject',
  'Please enter here the email subject for the welcome email message:', 'text', 'Welcome to our site [sitename]', 55);
InsertPluginSetting(12, 'welcome_options', 'Welcome Message Text',
  'Please enter here the actual message text (as HTML) for the welcome <strong>email body</strong>, which may contain the following placeholders:<br />
<strong>[date]</strong> = date of email, <strong>[username]</strong> = user name,
<strong>[siteurl]</strong> = website url, <strong>[sitename]</strong> = website title,
<strong>[email]</strong> = user email', 'wysiwyg',
  'Welcome [username],<br />
<p>thank you for activating your account with us on [date] with your email address "[email]".</p>
<p>Please visit us at <a href="[siteurl]"><strong>[sitename]</strong></a> frequently for updated news and great content.</p>
<p>Enjoy your visit,<br />
your [sitename] team</p>', 60);
InsertPluginSetting(12, 'welcome_options', 'Welcome Message Email Sender',
  'Please enter the <strong>email address</strong> used to send out the welcome email message. Leave this empty to use the technical email address (see Settings).', 'text', '', 65);
InsertPluginSetting(12, 'welcome_options', 'Welcome Message Email From',
  'Please enter the name for the <strong>from</strong> email field together with the email sender (e.g. your website name).<br />
  Only enter characters that are valid for the email server system or otherwise the email could fail.', 'text', '', 68);
InsertPluginSetting(12, 'prevention_options', 'Prohibit Multiple Users Per IP',
  'If this option is active, the plugin will check if a new registration came from an IP address,
   that was used by any previous registrations. If so, the registration will be declined.', 'yesno', '0', 70);

$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'users', 'register_ip', 'VARCHAR(32) collate utf8_unicode_ci', "NOT NULL DEFAULT ''");
$tbl = PRGM_TABLE_PREFIX.'pluginsettings';
$DB->query('UPDATE '.$tbl." SET description = 'banned_emails_descr' WHERE pluginid = 12 AND description = 'banned_emails_desc'");
$DB->query('UPDATE '.$tbl." SET description = 'max_username_length_descr' WHERE pluginid = 12 AND description = 'max_username_length_desc'");
$DB->query('UPDATE '.$tbl." SET description = 'require_email_activation_descr' WHERE pluginid = 12 AND description = 'require_email_activation_desc'");
// Latest Articles (p3): fix selection display (no eval needed)
$DB->query('UPDATE '.$tbl." SET input = '%s'
  WHERE pluginid = 3 AND groupname = 'latest_articles_settings' AND title = 'sorting'",
  "select:\r\nnewest|Newest First\r\noldest|Oldest First\r\nalphaAZ|Alphabetically A-Z\r\nalphaZA|Alphabetically Z-A\r\nauthornameAZ|Author Name A-Z\r\nauthornameZA|Author Name Z-A");

$tbl = PRGM_TABLE_PREFIX.'adminphrases';
$DB->query('UPDATE '.$tbl." SET varname = 'banned_emails_descr' WHERE pluginid = 12 AND varname = 'banned_emails_desc'");
$DB->query('UPDATE '.$tbl." SET varname = 'max_username_length_descr' WHERE pluginid = 12 AND varname = 'max_username_length_desc'");
$DB->query('UPDATE '.$tbl." SET varname = 'require_email_activation_descr' WHERE pluginid = 12 AND varname = 'require_email_activation_desc'");
$DB->query('UPDATE '.$tbl." SET defaultphrase = 'Enter the character set of your database, which must correspond to the above <strong>Character Set</strong>.<br /> For the default website character set <strong>UTF-8</strong> the database character set must be <strong>utf8</strong>.' WHERE adminpageid = 7 AND varname = 'settings_db_charset_desc'");
$DB->query('UPDATE '.PRGM_TABLE_PREFIX."usergroups_config SET avatar_path = 'images/avatars/' WHERE IFNULL(avatar_path,'')=''");

// Fix lang region display (introduced in SD 3.3.0):
$tbl = PRGM_TABLE_PREFIX.'mainsettings';
$lang_region = $DB->query_first("SELECT value FROM $tbl WHERE varname = 'lang_region'");
$DB->query("DELETE FROM $tbl WHERE varname = 'lang_region'");
InsertMainSetting('lang_region', 'settings_general_settings', 'Regional Language', 'Select the language for your region,
which will be used for e.g. the date picker popup in the Articles page (especially translation of day/month names as
well as the date format).',
'select:
af|Afrikaans
az|Azərbaycan dili (Azerbaijani)
id|Bahasa Indonesia (Indonesian)
ms|Bahasa Melayu (Malaysian)
nl-BE|Belgisch-Nederlands (Dutch/Belgian)
bs|Bosanski (Bosnian)
bg|български език (Bulgarian)
ca|Català (Catalan)
cs|Čeština (Czech)
da|Dansk (Danish)
de|Deutsch (German)
el|Ελληνικά (Greek)
en-AU|English/Australia
en-NZ|English/New Zealand
en-GB|English/UK
en-US|English/US
es|Español (Spanish)
es-AR|Español/Argentina (Spanish/Argentina)
eo|Esperanto
et|eesti keel (Estonian)
eu|Euskara (Basque)
fo|Føroyskt (Faroese)
fr|Français (French)
fr-CH|Français de Suisse (French/Swiss)
gl|Galego (Galician)
sq|Gjuha shqipe (Albanian)
gu|ગુજરાતી (Gujarati)
ko|한국어 (Korean)
hr|Hrvatski jezik (Croatian)
hy|Հայերեն (Armenian)
is|Íslenska (Icelandic)
it|Italiano (Italian)
lv|Latviešu Valoda (Latvian)
lt|lietuvių kalba (Lithuanian)
mk|македонски јазик (Macedonian)
no|Norwegian (Norsk)
hu|Magyar (Hungarian)
nl|Nederlands (Dutch)
ja|日本語 (Japanese)
no|Norsk (Norwegian)
th|ภาษาไทย (Thai)
pl|Polski (Polish)
pt-BR|Português (Portuguese/Brazil)
ro|Română (Romanian)
ru|Русский (Russian)
de-CH|Schweizerdeutsch (Swiss-German)
sk|Slovenčina (Slovak)
sl|Slovenski Jezik (Slovenian)
sr|српски језик (Serbian)
sr-SR|srpski jezik (Serbian)
fi|suomi (Finnish)
sv|Svenska (Swedish)
ta|தமிழ் (Tamil)
vi|Tiếng Việt (Vietnamese)
tr|Türkçe (Turkish)
uk|Українська (Ukranian)
zh-HK|简体中文 (Chinese Hong Kong)
zh-CN|简体中文 (Chinese Simplified)
zh-TW|繁體中文 (Chinese Traditional)",
','en-US', 200);
if(!empty($lang_region['value']))
{
  $DB->query("UPDATE $tbl SET value = '%s' WHERE varname = 'lang_region'", $DB->escape_string($lang_region['value']));
}

InsertAdminPhrase(0, 'menu_media_view_smilies', 'View Smilies', 0);
InsertMainSetting('site_inactive_redirect', 'settings_site_activation', 'Redirect Link when Off',
  'Enter a valid URL to which the site should redirect to (code 302) when being offline (default: none)<br />Note: leave empty to display default error page', 'text', '', 30);

InsertMainSetting('log_new_user_registrations', 'settings_system_log', 'Log New User Registrations',
  'Create a system log entry for each new user registration (default: Yes)?<br />
  This will note the registered username and IP address.<br />
  This is only used if no forum is integrated.', 'yesno', '1', 10);
InsertMainSetting('count_banned_ips', 'settings_system_log', 'Count Banned IP Attempts',
  'Keep a list of all banned IP addresses with which a login attempt was made (requires enabled caching)?<br />
  This also counts the attempts per banned IP and may help to identify frequently used bad addresses.<br />
  This is only used if no forum is integrated.', 'yesno', '0', 110);

// Censoring feature (global word list, comments censoring option)
InsertMainSetting('censored_words', 'settings_general_settings', 'Censor Words',
  'Enter a list of bad or invalid words (one entry per line) that should be censored<br />This list may be used by the forum plugin or comments (if activated).', 'textarea', '', 250);
InsertMainSetting('censor_comments', 'comment_options', 'Censor Comments',
  'Automatically censor all comments using the <strong>Censor Words</strong> list (default: No)?<br />The censored words list can be maintained in the <strong>Main Settings</strong> page.', 'yesno', '0', 15);

InsertMainSetting('enable_custom_plugin_paging', 'settings_general_settings', 'Enable Custom Plugin Paging',
  'Enable processing of Custom Plugins for the {pagebreak} macro and - if found - display pagination (default: No)?', 'yesno', '0', 110);
InsertMainSetting('enable_custom_plugin_ajax', 'settings_general_settings', 'Enable Custom Plugin Ajax',
  'If pagination for Custom Plugins is enabled, load contained pages in the background by Ajax (default: No)?<br />
  This should only be enabled if the current <strong>Character Set</strong> is set to <strong>utf-8</strong> or otherwise special characters and umlauts will be displayed incorrectly.', 'yesno', '0', 120);

// Convert "title_order" with new "select:" input type:
$DB->query("UPDATE $tbl SET input = '%s' WHERE varname = 'title_order' AND groupname = 'settings_seo_settings'",
           "select:\r\n0|Site - Category (default)\r\n1|Category - Site\r\n2|Article Title only\r\n3|Article Title - Site\r\n4|Article Title - Category\r\n5|Article Title - Category - Site\r\n6|Article Title - Site - Category");
$DB->query("UPDATE $tbl SET input = '%s' WHERE groupname = 'settings_captcha' AND varname = 'captcha_method'",
           "select:\r\n0|Disable Captcha\r\n1|reCaptcha\r\n2|VVC Image\r\n3|Simple Math");

$DB->add_tableindex($tbl,'groupname');
$DB->add_tableindex($tbl,'varname');
unset($tbl);

//TESTING - REMOVE "DROP" BEFORE RELEASE:
$DB->query('CREATE TABLE IF NOT EXISTS '.PRGM_TABLE_PREFIX."smilies (
smilieid INT(10)      UNSIGNED NOT NULL             AUTO_INCREMENT,
title    VARCHAR(100)          NOT NULL DEFAULT '',
text     VARCHAR(32)           NOT NULL DEFAULT '',
image    VARCHAR(100)          NOT NULL DEFAULT '',
is_core  TINYINT(1)   UNSIGNED NOT NULL DEFAULT 0,
PRIMARY KEY (smilieid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'smilies','is_core', 'TINYINT(1) UNSIGNED','NOT NULL DEFAULT 0');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'smilies','title');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'smilies','text');

if($res=$DB->query_first('SELECT COUNT(*) FROM '.PRGM_TABLE_PREFIX.'smilies'))
{
  if(empty($res[0]))
  {
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Smile',        ':)',        'smile.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Happy',        ':D',        'bigsmile.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Laugh',        'XD',        'laugh.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Disappointed', ':|',        'neutral.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Sad',          ':(',        'frown.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Angry',        'D:',        'angry.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Blush',        ':blush:',   'blush.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Tongue',       ':P',        'tongue.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Evil',         '>:)',       'evil.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Sneaky',       '>;)',       'sneaky.gif', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Saint',        'O:)',       'saint.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Surprise',     ':O',        'surprise.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Confused',     ':?',        'confuse.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Worry',        ':s',        'worry.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Irritated',    ':/',        'irritated.gif', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Big Eyes',     '8)',        'bigeyes.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Cool',         'B)',        'cool.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Wink',         ';)',        'wink.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Bigwink',      ';D',        'bigwink.gif', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Anime',        '^_^',       'anime.gif', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Sweatdrop',    '^^;',       'sweatdrop.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Teeth',        '<g>',       'teeth.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Boggle',       'o.O',       'boggle.gif', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Blue',         ':blue:',    'blue.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Sleepy',       ':zzz:',     'sleepy.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Heart',        ':heart:',   'heart.gif', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Cake',         ':cake:',    'cake.png', 1)");
    $DB->query('INSERT INTO ' . PRGM_TABLE_PREFIX . "smilies VALUES (NULL, 'Star',         ':star:',    'star.gif', 1)");
  }
}

if($forumid = GetPluginID('Forum'))
{
  InsertPhrase($forumid,'no_topics_found','There are no topics in this forum yet.');
  InsertPhrase($forumid,'subscribe','Subscribe to Topic');
  InsertPhrase($forumid,'subscribe_forum','Subscribe to Forum');
  InsertPhrase($forumid,'subscribe_to_topic','Subscribe to this topic to be notified upon new posts.');
  InsertPhrase($forumid,'subscribe_to_forum','Subscribe to this forum to be notified upon new topics.');
  InsertPhrase($forumid,'unsubscribe','Unsubscribe from Topic');
  InsertPhrase($forumid,'unsubscribe_forum','Unsubscribe from Forum');
  InsertPhrase($forumid,'unsubscribe_from_topic','Unsubscribe from this topic to not receive further notifications.');
  InsertPhrase($forumid,'unsubscribe_from_forum','Unsubscribe from this forum to not receive further notifications.');
  InsertPhrase($forumid,'you_are_subscribed','You are now subscribed.');
  InsertPhrase($forumid,'you_are_unsubscribed','You are now unsubscribed.');
  InsertPhrase($forumid,'msg_moderate_user_stats','The user "<strong>[username]</strong>" has created in total '.
               '<strong>[topics_count] topics</strong> and submitted <strong>[posts_count] posts</strong> within <strong>[forums_count] forums</strong>.');
  InsertPhrase($forumid,'msg_moderate_user_posts_hint1','Please select below an action on how to proceed with posts and topics for this user.<br />'.
               '<strong>What should happen with all of the user\'s POSTS?</strong>');
  InsertPhrase($forumid,'msg_mod_option_none','No changes.');
  InsertPhrase($forumid,'msg_mod_option_posts_not_change','Do not change visibility of posts.');
  InsertPhrase($forumid,'msg_mod_option_posts_mod_all','Moderate <strong>ALL</strong> of the user\'s posts (posts become invisible)?');
  InsertPhrase($forumid,'msg_mod_option_posts_del_all','Delete <strong>ALL</strong> of the user\'s posts (cannot be undone)?');
  InsertPhrase($forumid,'msg_mod_option_posts_unmod_all','Unmoderate ALL of the user\'s posts (posts become visible).');
  InsertPhrase($forumid,'msg_mod_option_topics_hint1','<strong>What should happen with all TOPICS the user created?</strong>');
  InsertPhrase($forumid,'msg_mod_option_topics_not_change','Do not change visibility of topics the user created.');
  InsertPhrase($forumid,'msg_mod_option_topics_mod_all','Moderate <strong>ALL</strong> of the user\'s posts (posts become invisible)?');
  InsertPhrase($forumid,'msg_mod_option_topics_del_all','Delete <strong>ALL</strong> topics started by the user, including ALL posts (cannot be undone)?');
  InsertPhrase($forumid,'msg_mod_option_topics_del_hint','<strong>Note:</strong> deletion of topics includes <strong>ALL</strong> their posts, regardless of the user!');
  InsertPhrase($forumid,'msg_mod_option_topics_unmod_all','Unmoderate all topics the user started (topics may become visible, too)?');
  InsertPhrase($forumid,'msg_mod_option_user_mod_hint1','For a working moderation, the user should get a different usergroup assigned '.
               'which prohibits the <strong>Submit</strong> permission for the forum.');
  InsertPhrase($forumid,'msg_mod_option_user_mod_hint2','Please select below a different (non-admin) usergroup for the user to be moderated if possible '.
               '(the current usergroup is pre-selected).');
  InsertPhrase($forumid,'msg_mod_option_user_mod_backlink','Otherwise click [topiclink] to go back to the topic.');
  InsertPhrase($forumid,'msg_mod_option_ban_ip_address','Add the IP address ([IP_ADDRESS]) to banned IP addresses?');
  InsertPhrase($forumid,'view_member_page','View member\'s page');
  InsertPhrase($forumid,'open_your_profile_page','Open your profile page');
  InsertPhrase($forumid,'invalid_file_type','Invalid file type.');

  InsertAdminPhrase($forumid,'err_invalid_forum_id','Invalid Forum ID!',2);
  InsertAdminPhrase($forumid,'forum_permissions_title','Forum Permissions:',2);
  InsertAdminPhrase($forumid,'forum_options_title','Forum Options',2);
  InsertAdminPhrase($forumid,'auto_moderate_descr','Auto-moderate all new topics/posts?<br />'.
    'If this option is active, all new topics and posts created by regular users '.
    'will be invisible to the public until these are manually approved (unmoderated) '.
    'by an administrator. Only then these will appear publicly on the frontpage.',2);
  InsertAdminPhrase($forumid,'auto_moderate_option','Auto-Moderate?',2);
  InsertAdminPhrase($forumid,'view_permissions_descr','If a usergroup has permission to view the forum plugin, either '.
    'all (if no selection is made) or just the below selected usergroups may see this forum and topics within it.<br /><br />'.
    'Please use CTRL+click to select/deselect any usergroup(s), that shall be allowed to VIEW the current forum '.
    'and below topics and posts.<br />',2);
  InsertAdminPhrase($forumid,'view_permissions_title','View Permissions',2);
  InsertAdminPhrase($forumid,'posting_permissions_descr','If a usergroup has permission to submit to the forum plugin, '.
    'either all (if no selection is made) or just the below selected usergroups '.
    'may post in this forum and topics within it (incl. Quote/Reply).<br /><br />'.
    'Please use CTRL+click to select/deselect any usergroup(s), that shall '.
    'be allowed to POST in the current forum and below topics and posts.',2);
  InsertAdminPhrase($forumid,'posting_permissions_title','Posting Permissions',2);
  InsertAdminPhrase($forumid,'posting_permissions_note',
    '<p>Note: above is not used for site administrators (usergroup with "Full Access").</p>',2);
  InsertAdminPhrase($forumid,'update_permissions','Update Permissions',2);
  InsertAdminPhrase($forumid,'forum_permissions_updated','Forum Permissions Updated',2);
  InsertAdminPhrase($forumid,'forum_delete_prompt',"Warning! Are you sure you want to delete this forum?\\n\\nThis would DELETE also ALL topics and posts belonging to the forum!",2);
  InsertAdminPhrase($forumid,'new_forum_created','New Forum Created',2);
  InsertAdminPhrase($forumid,'forum_deleted','Forum Deleted',2);
  InsertAdminPhrase($forumid,'forums_updated','Forums Updated',2);
  InsertAdminPhrase($forumid,'lbl_delete','Delete',2);
  InsertAdminPhrase($forumid,'lbl_description','Description',2);
  InsertAdminPhrase($forumid,'lbl_display_order','Display Order',2);
  InsertAdminPhrase($forumid,'lbl_forum_title','Forum Title',2);
  InsertAdminPhrase($forumid,'lbl_topics_mod','Topics / Mod.',2);
  InsertAdminPhrase($forumid,'lbl_posts_mod','Posts / Mod.',2);
  InsertAdminPhrase($forumid,'lbl_status','Status',2);
  InsertAdminPhrase($forumid,'lbl_title','Title',2);
  InsertAdminPhrase($forumid,'lbl_unique_users','Unique Users',2);
  InsertAdminPhrase($forumid,'statistics_updated','Statistics updated!',2);
  InsertAdminPhrase($forumid,'update_forums','Update Forums',2);
  InsertAdminPhrase($forumid,'update_all_statistics','Update all Statistics',2);
  InsertAdminPhrase($forumid,'update_all_statistics_hint','This will update all Forum\'s details for last topic/post display and -counters.',2);

  InsertAdminPhrase($forumid,'forum_details','Forum Details',2);
  InsertAdminPhrase($forumid,'forum_parent_forum','Parent Forum',2);
  InsertAdminPhrase($forumid,'forum_seo_title','SEO Title',2);
  InsertAdminPhrase($forumid,'forum_seo_title_hint','If this is left empty, the SEO title will be automatically generated when saving the forum.',2);
  InsertAdminPhrase($forumid,'forum_link_to','Links to (full URL)',2);
  InsertAdminPhrase($forumid,'forum_link_to_hint','If a valid URL is specified, the forum will link to that instead of showing it\'s own topics.',2);
  InsertAdminPhrase($forumid,'forum_external_link_target','Link Target',2);
  InsertAdminPhrase($forumid,'forum_meta_description','Meta Description',2);
  InsertAdminPhrase($forumid,'forum_meta_keywords','Meta Keywords',2);
  InsertAdminPhrase($forumid,'forum_insert_forum','Insert Forum',2);
  InsertAdminPhrase($forumid,'forum_update_forum','Update Forum',2);
  InsertAdminPhrase($forumid,'forum_updated','Forum successfully updated.',2);
  InsertAdminPhrase($forumid,'update_category','Update Category',2);
  InsertAdminPhrase($forumid,'forum_categories','Forum Categories',2);
  InsertAdminPhrase($forumid,'forum_category','Forum Category',2);
  InsertAdminPhrase($forumid,'forums_listed_in_category','Forums listed in Category',2);
  InsertAdminPhrase($forumid,'lbl_category_title','Category Title',2);
  InsertAdminPhrase($forumid,'lbl_edit_category','Edit Category',2);
  InsertAdminPhrase($forumid,'lbl_edit_forum','Edit Forum',2);

  InsertPluginSetting($forumid, 'forum_display_settings', 'Censor Posts',
    'Automatically censor all posts and topic titles based on the <strong>Censor Words</strong> list in the <strong>Main Settings</strong> page (default: No)?<br />
    This option is ignored for site administrators.', 'yesno', '0', 8);
  InsertPluginSetting($forumid, 'forum_display_settings', 'Enable SEO Forums',
    'If SEO is activated for the site (see Admin|Settings|SEO Settings), should all <strong>forum and topic titles</strong>
     be converted to their SEO names (default: No)?<br />
     If possible, the forum will try to redirect (301) old URLs to the new URL version.', 'yesno', '0', 80);
  InsertPluginSetting($forumid, 'forum_display_settings', 'Display Forum Image',
    'Display an extra column to the forums list display for forum images (default: No)?<br />
     An image can be uploaded when editing a forum.', 'yesno', '0', 16);
  InsertPluginSetting($forumid, 'forum_display_settings', 'Disable Guest Search',
    'Disable forum search feature for Guests (default: No)?', 'yesno', '0', 65);
  InsertPluginSetting($forumid, 'forum_display_settings', 'Display User IP',
    'Display user IP addresses within topics for administrators (default: No)?', 'yesno', '0', 70);

  InsertAdminPhrase($forumid,'forum_topic_settings','Forum Topic Settings',2);
  $messaging_fields = array('user_aim','user_facebook','user_googletalk','user_icq',
                            'user_msnm','user_skype','user_twitter','user_yim');
  $idx = 10;
  foreach($messaging_fields as $field)
  {
    $name = strtolower(str_replace('user_','',$field));
    $name = strlen($name)<5 ? strtoupper($name) : (strtoupper(substr($name,0,1)). substr($name,1));
    InsertPluginSetting($forumid, 'forum_topic_settings', 'Display User '.$name.' Icon In Topic',
      'Display the <strong>'.$name.'</strong> icon for users, if it is publicly available (default: No)?', 'yesno', '0', $idx);
    $idx += 10;
  }
  DeletePluginSetting($forumid,'forum_display_usergroup','display_usergroup');

  $tbl = PRGM_TABLE_PREFIX.'p_forum_forums';
  $DB->query("UPDATE $tbl SET online = 1 WHERE (parent_forum_id = 0 OR is_category = 1) AND online = 0");
  $DB->add_tablecolumn($tbl, 'seo_title', 'VARCHAR(200) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");
  $DB->add_tablecolumn($tbl, 'metakeywords', 'VARCHAR(200) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");
  $DB->add_tablecolumn($tbl, 'metadescription', 'VARCHAR(200) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");
  $DB->add_tablecolumn($tbl, 'pwd', 'VARCHAR(64) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");
  $DB->add_tablecolumn($tbl, 'pwd_salt', 'VARCHAR(64) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");
  $DB->add_tablecolumn($tbl, 'link_to', 'VARCHAR(200) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");
  $DB->add_tablecolumn($tbl, 'target', 'VARCHAR(10) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");
  $DB->add_tablecolumn($tbl, 'image', 'VARCHAR(64) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");
  $DB->add_tablecolumn($tbl, 'image_w', 'INT(8) UNSIGNED', 'NOT NULL DEFAULT 0');
  $DB->add_tablecolumn($tbl, 'image_h', 'INT(8) UNSIGNED', 'NOT NULL DEFAULT 0');
  $DB->add_tablecolumn($tbl, 'allow_rss', 'INT(1) UNSIGNED', 'NOT NULL DEFAULT 1');
  $DB->add_tablecolumn($tbl, 'publish_start', 'INT(11) UNSIGNED', 'NOT NULL DEFAULT 0');
  $DB->add_tablecolumn($tbl, 'publish_end', 'INT(11) UNSIGNED', 'NOT NULL DEFAULT 0');
} //Forum

$DB->ignore_error = true;
$DB->add_tableindex(PRGM_TABLE_PREFIX.'adminphrases','pluginid,varname','plugin_varname');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'categories','designid');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'categories','urlname');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'designs','skinid');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'phrases','pluginid,varname','plugin_varname');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'plugins','name');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'ratings','user_id');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'tags','datecreated');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'tags','objectid');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'sessions','userid');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'sessions','loggedin','loggedin');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'slugs','parent');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'skins','activated','activated',false);
$DB->add_tableindex(PRGM_TABLE_PREFIX.'skins','name');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'skins','skin_engine');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'skin_css','var_name');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'sessions','ipaddress');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'sessions','lastactivity');
if($DB->table_exists(PRGM_TABLE_PREFIX.'syslog'))
{
  $DB->add_tableindex(PRGM_TABLE_PREFIX.'syslog','severity');
  $DB->add_tableindex(PRGM_TABLE_PREFIX.'syslog','timestamp');
  $DB->add_tableindex(PRGM_TABLE_PREFIX.'syslog','username');
}
$DB->add_tableindex(PRGM_TABLE_PREFIX.'users','lastactivity');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'users','usergroupid');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'users','email','email',true,50);
$DB->add_tableindex(PRGM_TABLE_PREFIX.'users','username');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'users','activated,userid','active_users');
$DB->errdesc = '';$DB->errno = 0;
if($DB->columnindex_exists(PRGM_TABLE_PREFIX.'users_data','first_name'))
$DB->query('ALTER TABLE '.PRGM_TABLE_PREFIX."users_data DROP INDEX `first_name`");
if($DB->columnindex_exists(PRGM_TABLE_PREFIX.'users_data','last_name'))
$DB->query('ALTER TABLE '.PRGM_TABLE_PREFIX."users_data DROP INDEX `last_name`");
$DB->add_tableindex(PRGM_TABLE_PREFIX.'users_data','first_name,usersystemid','first_name_system');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'users_data','last_name,usersystemid','last_name_system');
if(!$DB->index_exists(PRGM_TABLE_PREFIX.'users_data','screen_name_system'))
$DB->add_tableindex(PRGM_TABLE_PREFIX.'users_data','user_screen_name,usersystemid','screen_name_system');

$DB->query('OPTIMIZE TABLE `'.PRGM_TABLE_PREFIX.'adminphrases`');
$DB->query('OPTIMIZE TABLE `'.PRGM_TABLE_PREFIX.'mainsettings`');
$DB->query('OPTIMIZE TABLE `'.PRGM_TABLE_PREFIX.'msg_master`');
$DB->query('OPTIMIZE TABLE `'.PRGM_TABLE_PREFIX.'msg_messages`');
$DB->query('OPTIMIZE TABLE `'.PRGM_TABLE_PREFIX.'msg_text`');
$DB->query('OPTIMIZE TABLE `'.PRGM_TABLE_PREFIX.'phrases`');
$DB->query('OPTIMIZE TABLE `'.PRGM_TABLE_PREFIX.'ratings`');
$DB->query('OPTIMIZE TABLE `'.PRGM_TABLE_PREFIX.'pluginsettings`');
$DB->ignore_error = false;
