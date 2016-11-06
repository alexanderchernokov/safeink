<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

// ###############################################################################
// DROP LEGACY TABLES
// ###############################################################################
$DB->query("DROP TABLE IF EXISTS {smilies}");

// ###############################################################################
// ALTER TABLES
// ###############################################################################

// commentaccess was previously used as a setting to allow a usergroup to edit/delete comments in the frontend
// however version 300 uses this variable to allow a usergroup to post comments to a website
$DB->query('UPDATE {usergroups} SET commentaccess = 0');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'mainsettings', 'displayorder', 'INT(10) UNSIGNED', "NOT NULL DEFAULT 0 AFTER `value`");

// ###############################################################################
// INSERT NEW MAINSETTINGS
// ###############################################################################

// get db charset
// if mysql_set_names is enabled, then the db charset is set to $charset
$db_charset = 'utf8';#'iso-8859-1';
if($mysql_set_names_arr = $DB->query_first("SELECT value FROM {mainsettings} WHERE varname = 'mysql_set_names'"))
{
  $db_charset = $mysql_set_names_arr['value'];
}

// get charset
$charset = 'UTF-8';
if($lang = $DB->query_first("SELECT value FROM {mainsettings} WHERE varname = 'language'"))
{
  $lang = explode('|', $lang['value']); // ex: Greek utf-8.php|1.0|UTF-8
  $charset = isset($lang[2]) ? $lang[2] : 'UTF-8';
  unset($lang);
}

if($DB->query_first("SELECT 1 FROM {mainsettings} WHERE varname = 'charset'"))
{
  $DB->query("UPDATE {mainsettings} SET value = '%s' WHERE varname = 'charset'",
             $DB->escape_string($charset));
}
else
{
  $DB->query("INSERT INTO {mainsettings} (varname, groupname, input, title, description, value, displayorder) VALUES
             ('charset', 'Character Encoding', 'text', 'Character Set', 'Enter the character set of your website:<br />Default is UTF-8',
             '%s', 1)", $DB->escape_string($charset));
}

if($DB->query_first("SELECT 1 FROM {mainsettings} WHERE varname = 'db_charset'"))
{
  $DB->query("UPDATE {mainsettings} SET value = '%s' WHERE varname = 'db_charset'",
             $DB->escape_string($db_charset));
}
else
{
  $DB->query("INSERT INTO {mainsettings} (varname, groupname, input, title, description, value, displayorder) VALUES
             ('db_charset', 'Character Encoding', 'text', 'Database Character Set', 'Enter the character set of your database:<br />
             This should correspond to your character set; the default is `utf8`.','%s', 2)",
             $DB->escape_string($db_charset));
}

$DB->query("UPDATE {mainsettings} SET groupname = 'Character Encoding',
            description = 'Enter your forum\'s charset if you have integration enabled:',
            displayorder = 3
            WHERE varname = 'forum_character_set'");

// ###############################################################################
// DELETE FROM MAINSETTINGS
// ###############################################################################

// SD3 - TT - 2010-11-02: the below will be re-introduced with SD3.2.2+ upgrade
$DB->query("DELETE FROM {mainsettings} WHERE groupname = 'Cache Settings'");
$DB->query("DELETE FROM {mainsettings} WHERE groupname = 'Captcha Settings'");
$DB->query("DELETE FROM {mainsettings} WHERE groupname = 'Email System Settings'");

$DB->query("DELETE FROM {mainsettings} WHERE varname IN
('forum_character_set', 'forcessl', 'wysiwyg_css', 'checkforupdates', 'globalscomp', 'use_skin',
 'commentavatar', 'com_avatar_link', 'com_avatar_width', 'com_avatar_height',
 'com_user_edit', 'com_approval_required', 'commentvvc',
 'use_displayname', 'display_sql_errors', 'techfromemail',
 'emailformat', 'email_encoding_charset', 'email_db_errors', 'smiliesystem', 'mysql_set_names')
");
//'gzipcompress', 'sslurl',

// ###############################################################################
// UPDATE MAINSETTINGS
// ###############################################################################

$DB->query("UPDATE {mainsettings} SET description = 'Please enter the full URL to your website:<br />This setting is required in order for your site to function correctly.',
                                      displayorder = 1 WHERE varname = 'sdurl'");

$DB->query("UPDATE {mainsettings} SET `input` = 'yesno', `title` = 'Enable WYSIWYG Editor',
            `description` = 'Enable the WYSIWYG editor in the admin panel?', `value` = 1
            WHERE varname = 'enablewysiwyg'");

$DB->query("UPDATE {mainsettings} SET input = 'textarea' WHERE varname = 'offmessage'");

$DB->query("UPDATE {mainsettings} SET groupname = 'SEO Settings', displayorder = 1 WHERE varname = 'modrewrite'");
$DB->query("UPDATE {mainsettings} SET groupname = 'SEO Settings', description = 'If set to Yes, URL\'s will contain all subcategories leading to the target category.', displayorder = 2 WHERE varname = 'url_subcategories'");
$DB->query("UPDATE {mainsettings} SET groupname = 'SEO Settings', displayorder = 3 WHERE varname = 'categorytitle'");
$DB->query("UPDATE {mainsettings} SET groupname = 'SEO Settings', displayorder = 4 WHERE varname = 'title_order'");
$DB->query("UPDATE {mainsettings} SET groupname = 'SEO Settings', displayorder = 5 WHERE varname = 'title_separator'");
$DB->query("UPDATE {mainsettings} SET groupname = 'SEO Settings', displayorder = 6 WHERE varname = 'metadescription'");
$DB->query("UPDATE {mainsettings} SET groupname = 'SEO Settings', displayorder = 7 WHERE varname = 'metakeywords'");

$DB->query("UPDATE {mainsettings} SET groupname = 'General Settings' WHERE groupname = 'Site Settings'");
$DB->query("UPDATE {mainsettings} SET groupname = 'General Settings' WHERE groupname = 'Subdreamer Settings'");
$DB->query("UPDATE {mainsettings} SET groupname = 'General Settings', displayorder = 1 WHERE varname = 'sdurl'");
$DB->query("UPDATE {mainsettings} SET groupname = 'General Settings', displayorder = 2 WHERE varname = 'websitetitle'");
$DB->query("UPDATE {mainsettings} SET groupname = 'General Settings', displayorder = 3, input = 'text', description = 'Enter your copyright information here:' WHERE varname = 'copyrighttext'");
$DB->query("UPDATE {mainsettings} SET groupname = 'General Settings', displayorder = 4 WHERE varname = 'admincookietimeout'");
$DB->query("UPDATE {mainsettings} SET groupname = 'General Settings', displayorder = 5 WHERE varname = 'enablewysiwyg'");


// ###############################################################################
// DELETE FROM PLUGIN SETTINGS
// ###############################################################################

// articles
$DB->query("DELETE FROM {pluginsettings} WHERE pluginid = 2 AND groupname = 'Admin Options'");
$DB->query("DELETE FROM {pluginsettings} WHERE pluginid = 2 AND title IN
           ('Article Notification', 'Auto Approve News', 'Enable Email Articles', 'Require VVC Code', 'Article Back Link',
            'Redirect Invalid Article', 'Use SEO links', 'Bold Title', 'Allow Guest View', 'Guest No-View Message')");
$DB->query("DELETE FROM {pluginsettings} WHERE pluginid = 2 AND description IN
           ('Display Smilie Images', 'Display Views Count', 'Display Email Article Link')");

// latest articles
$DB->query("DELETE FROM {pluginsettings} WHERE pluginid = 3 AND title IN
            ('Source Plugin-ID', 'Matching Categories', 'Display Print Article Option', 'Display Email Article Option',
             'Grouping', 'Group Separator', 'Group/Article Separator', 'News Article Separator', 'Display Sort Selection')");

// guestbook
$DB->query("DELETE FROM {pluginsettings} WHERE pluginid = 4 AND title IN
           ('Enable Smilies', 'Show Post Date', 'Require VVC Code', 'Section')");

// contact form
$DB->query("DELETE FROM {pluginsettings} WHERE pluginid = 6 AND title IN
           ('Require VVC Code', 'Include User Email')");

// chatterbox
$DB->query("DELETE FROM {pluginsettings} WHERE pluginid = 7 AND title IN
            ('Enable Smilies', 'Chatterbox History', 'Require VVC Code', 'Display Avatar',
             'Avatar Image Width', 'Avatar Image Height', 'Avatar Column', 'Maximum History Length')");

// login panel
$DB->query("DELETE FROM {pluginsettings} WHERE pluginid = 10 AND title IN
            ('Display Avatar', 'Show PMs', 'Show Popups')");

// profile
$DB->query("DELETE FROM {pluginsettings} WHERE pluginid = 11 AND title = 'Require VVC Code'");

// registration
$DB->query("DELETE FROM {pluginsettings} WHERE pluginid = 12 AND title IN
            ('Maximum Password Length', 'Enable Javascript Checking', 'Require VVC Code')");

// link directory
$DB->query("DELETE FROM {pluginsettings} WHERE pluginid = 16 AND title IN
            ('Allow User Thumbnail', 'Simple Display Layout', 'Link Row Height', 'Background Hover Colour',
             'Enable Hover Effect', 'Require VVC Code', 'Number of links per Row')");

// image gallery
$DB->query("DELETE FROM {pluginsettings} WHERE pluginid = 17 AND groupname = 'Image Border Options'");

// subcategory plugin
$DB->query("DELETE FROM {pluginsettings} WHERE pluginid = 31 AND title != 'Subcategory Separator'");



// ###############################################################################
// ALTER AND CREATE TABLES
// ###############################################################################

$DB->query("ALTER TABLE {skins} CHANGE `authorlink` `authorlink` VARCHAR(32) NOT NULL");

// author link was filled with userid that would have links to subdreamer forum profile
$DB->query("UPDATE {skins} SET authorlink = ''");

$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'skins', 'skin_engine', 'SMALLINT(1)', 'UNSIGNED NOT NULL DEFAULT 1 AFTER `skinid`');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'skins', 'folder_name', 'VARCHAR(32)', "NOT NULL DEFAULT ''");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'skins', 'doctype', 'VARCHAR(255)', "NOT NULL DEFAULT ''");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'skins', 'starting_html_tag', 'VARCHAR(255)', "NOT NULL DEFAULT ''");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'skins', 'starting_body_tag', 'VARCHAR(255)', "NOT NULL DEFAULT ''");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'skins', 'head_include', 'MEDIUMTEXT', "NULL");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'skins', 'css', 'MEDIUMTEXT', "NULL");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'skins', 'prgm_css', 'MEDIUMTEXT', "NULL");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'skins', 'header', 'MEDIUMTEXT', "NOT NULL");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'skins', 'footer', 'MEDIUMTEXT', "NOT NULL");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'skins', 'error_page', 'MEDIUMTEXT', "NOT NULL");

$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'designs', 'layout', 'MEDIUMTEXT', "NOT NULL");

$DB->query("CREATE TABLE IF NOT EXISTS {skin_bak_cat} (
skinid       INT(10)    UNSIGNED NOT NULL DEFAULT 0,
categoryid   INT(10)    UNSIGNED NOT NULL DEFAULT 0,
designid     INT(10)    UNSIGNED NOT NULL DEFAULT 0,
KEY sbcat (skinid, categoryid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE IF NOT EXISTS {skin_bak_pgs} (
skinid       INT(10)    UNSIGNED NOT NULL DEFAULT 0,
categoryid   INT(10)    UNSIGNED NOT NULL DEFAULT 0,
displayorder INT(10)    UNSIGNED NOT NULL DEFAULT 0,
pluginid     VARCHAR(5)          NOT NULL DEFAULT '1',
KEY sbpgs (skinid, categoryid, displayorder)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("UPDATE {plugins} SET name = 'User Login Panel' WHERE pluginid = 10");
$DB->query("UPDATE {plugins} SET name = 'User Profile' WHERE pluginid = 11");
$DB->query("UPDATE {plugins} SET name = 'User Registration' WHERE pluginid = 12");


// ###############################################################################
// ALTER PHRASES
// ###############################################################################

// system
$DB->query("DELETE FROM {phrases} WHERE pluginid = 1 AND varname IN ('enable_wysiwyg', 'disable_wysiwyg', 'delete',
            'unapprove', 'approve', 'reset', 'email_recipient_invalid', 'email_sender_invalid', 'email_invalid_header',
            'file_extension_invalid', 'invalid_image_type', 'invalid_image_upload', 'invalid_file_upload', 'file_copy_error',
            'image_read_error', 'file_read_error', 'image_upload_error', 'file_upload_error', 'refresh', 'enter_verify_code',
            'incorrect_vvc_code', 'session_error', 'hide_comments', 'view_comments', 'url_not_found' )");

InsertPhrase(1, 'website_offline', 'Website Offline');

// articles
$DB->query("DELETE FROM {phrases} WHERE pluginid = 2 AND varname NOT IN ('read_more', 'published', 'by', 'updated', 'print')");

// latest articles
$DB->query("DELETE FROM {phrases} WHERE pluginid = 3 AND varname IN ('print', 'email', 'sort_by', 'next_page', 'previous_page')");

// guestbook
$DB->query("DELETE FROM {phrases} WHERE pluginid = 4 AND varname IN ('date', 'reset', 'previous', 'next', 'website')");

// contact form
$DB->query("DELETE FROM {phrases} WHERE pluginid = 6 AND varname NOT IN ('empty_fields', 'invalid_email', 'email_sent',
            'email_not_sent', 'full_name', 'your_email', 'subject', 'message', 'send_message')");

// chatterbox
$DB->query("DELETE FROM {phrases} WHERE pluginid = 7 AND varname IN ('Delete Comment', 'View Message History')");
InsertPhrase(2, 'comments', 'Comments');

// login panel
$DB->query("DELETE FROM {phrases} WHERE pluginid = 10 AND varname NOT IN ('welcome_back', 'my_account', 'logout', 'username',
            'password', 'remember_me','login', 'not_registered', 'register_now', 'forgot_password', 'admin_panel')");

// registration
$DB->query("DELETE FROM {phrases} WHERE pluginid = 12 AND varname IN ('reset_form', 'enter_alnum_password')");

// link directory
$DB->query("DELETE FROM {phrases} WHERE pluginid = 16 AND varname IN ('previous_links', 'more_links', 'goto_link', 'goto_section')");

// image gallery
$DB->query("DELETE FROM {phrases} WHERE pluginid = 17 AND varname IN ('previous_images', 'more_images', 'jump_to',
            'lb_image', 'lb_of', 'lb_close', 'lb_closeInfo', 'lb_help_close', 'reset_form', 'go')");


// ###############################################################################
// ADD THEME CSS CODE ADD TO ALL OLD THEMES
// ###############################################################################

$prgm_css = '
/* Basic System Styling */
div #error_message {
	background: #ffeaef;
	border: 3px solid #ff829f;
	left: 55px;
	margin-bottom: 15px;
	padding: 15px; }

div #success_message {
	background: #eaf4ff;
	border: 3px solid #82c0ff;
	left: 55px;
	margin-bottom: 15px;
	padding: 15px; }

/* Pagination */
div.pagination {
	font-family: Helvetica, Times, serif;
	font-style: italic;
	padding: 0px;
	margin: 0px;
	line-height: 40px;
	text-align: left;
	height: 40px; }

	div.pagination a {
		padding: 2px 5px 2px 5px;
		margin: 2px;
		text-decoration: none;
		color: #44B0EB; }

		div.pagination a:hover {
			color: #44e5eb; }

	div.pagination span.current {
		padding: 2px 5px 2px 5px;
		margin: 2px;
		font-weight: bold;
		background-color: #44B0EB;
		color: #ffffff; }

	div.pagination span.disabled {
		padding: 2px 5px 2px 5px;
		margin: 2px;
		color: #dddddd; }

/* Hover Menu */
.sf-menu ul {
	position: absolute;
	top: -999em;
	width: 10em; /* left offset of submenus need to match (see below) */ }

	.sf-menu ul li { width: 100%; }

	.sf-menu li:hover { visibility: inherit; /* fixes IE7 sticky bug */ }

	.sf-menu li { position: relative; }

	.sf-menu a {
		display: block;
		padding-left: 20px;
		padding-right: 20px; }

	.sf-menu li:hover ul, .sf-menu li.sfHover ul {
		left: 0;
		background: #ffffff;
		top: 24px; /* match top ul list item height */
		z-index: 99; }

	.sf-menu li:hover li ul, .sf-menu li.sfHover li ul, .sf-menu li li:hover li ul, .sf-menu li li.sfHover li ul { top: -999em; }

	.sf-menu li li:hover ul, .sf-menu li li.sfHover ul, .sf-menu li li li:hover ul, .sf-menu li li li.sfHover ul {
		left: 10em; /* match ul width */
		top: 0; }

/* Articles Plugin */
div.article_title {
	background: transparent;
	color: #272727;
	font-family: Helvetica, Times, serif;
	font-size: 38px;
	line-height: 1;
	padding-bottom: 15px; }

a.article_title_link {
	background: transparent;
	color: #272727;
	font-family: Helvetica, Times, serif;
	font-size: 38px;
	line-height: 1;
	font-style: normal;
	text-decoration: none; }

	a.article_title_link:hover {
		background: transparent;
		color: #44b0eb; }

div.article_footer {
	position: relative;
	width: 100%;
	height: 50px; }

div.article_footer_left {
	position: absolute;
	width: auto;
	height: 50px;
	font-family: Helvetica, Times, serif;
	font-size: 12px;
	font-style: italic; }

div.article_footer_right {
	position: absolute;
	width: auto;
	height: 50px;
	right: 0px;
	text-align: right;
	float: right;
	font-family: Helvetica, Times, serif;
	font-size: 12px;
	font-style: italic; }

	div.article_footer_right a { margin: 0 5px; }

/* Image Gallery Plugin */

#image_gallery_image img
{
  border: 1px solid #eee;
  padding: 2px;
}

#image_gallery_sections img
{
  border: 1px solid #eee;
  padding: 2px;
}

#image_gallery_thumbnails img
{
  border: 1px solid #eee;
  padding: 2px;
}';

$error_page = "[ERROR_PAGE_MESSAGE_TITLE]\n\n[ERROR_PAGE_MESSAGE]";

$DB->query("UPDATE {skins} SET prgm_css = '" . mysql_real_escape_string($prgm_css) . "', error_page = '$error_page'");
