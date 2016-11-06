<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

//Fix "tags" table to actually have a primary key (since SD340)
$old_ignore = $DB->ignore_error;
$DB->ignore_error = true;
if(!$DB->index_exists(PRGM_TABLE_PREFIX.'tags', 'PRIMARY'))
{
  $DB->query("ALTER TABLE `".PRGM_TABLE_PREFIX."tags` ADD PRIMARY KEY(tagid)");
}
if($DB->columnindex_exists(PRGM_TABLE_PREFIX.'tags', 'tagid'))
{
  $DB->drop_index(PRGM_TABLE_PREFIX.'tags', 'tagid');
}
$DB->ignore_error = $old_ignore;
unset($old_ignore);

// Fix plugins' SD author settings:
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."plugins SET authorlink = 'http://www.subdreamer.com',".
           " authorname = trim(authorname)".
           " WHERE authorlink = '1' AND authorname LIKE '%subdreamer_web%'");

// Fix old install errors about DB charset:
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."mainsettings SET value = 'utf8' WHERE varname = 'db_charset' AND lower(value) IN ('utf-8','iso-8859-1')");
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."adminphrases
  SET defaultphrase = 'Enter the character set of your database:<br />The default for the CMS is <b>utf8</b>!'
  WHERE varname = 'settings_db_charset_descr'");

// Add columns for new page-level HTML settings:
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'categories', 'html_class', 'VARCHAR(100) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'categories', 'html_id', 'VARCHAR(100) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");

// Table to store data about plugins, from where to receive item data for
// e.g. the Latest Comments plugin to receive titles for commented objects:
$DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."plugins_titles (
`cpid`           INT(11) UNSIGNED NOT NULL auto_increment,
`pluginid`       INT(11) UNSIGNED NOT NULL DEFAULT 0,
`tablename`      VARCHAR(254) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
`id_column`      VARCHAR(254) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
`title_column`   VARCHAR(254) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
`type`           VARCHAR(254) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
`activated`      INT(1) UNSIGNED NOT NULL DEFAULT 1,
PRIMARY KEY (`cpid`),
KEY (`pluginid`,`id_column`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

// Remove interim wrong table name for forum topics (beta testing)
$DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'plugins_titles'.
           " WHERE tablename = 'p_forum_topcis'");
// Add default data for known plugins
if(function_exists('FillPluginsTitlesTable'))
{
  FillPluginsTitlesTable(); # in functions_plugins.php
}

InsertPluginSetting(11, 'Options', 'Datetime Autoupdate',
  'Enable the automatic update for the date and time display in the profile (default: Yes)?<br />
  Note: this will fetch every minute the current date/time from the server (via ajax),
  but may increase server load with many concurrent users viewing their profile page.',
  'yesno', '1', 220);

InsertPluginSetting(12, 'user_registration_settings', 'New Registration Admin Usage',
  'Specify when admin notification is sent (default: Required).<br />'.
  'Note: Required refers to the admin activation option.',
  "select:\r\n0|Required\r\n1|Not required\r\n2|Always\r\n3|Disabled", 0, 58);

InsertAdminPhrase(0, 'menu_plugins_options', 'Plugin Options', 0);
InsertAdminPhrase(0, 'page_html_class', 'HTML CLASS for Page',1);
InsertAdminPhrase(0, 'page_html_class_descr', 'This value (max. 100 characters) will be accessible within the Skins page as <b>[PAGE_HTML_CLASS]</b> variable.',1);
InsertAdminPhrase(0, 'page_html_id', 'HTML ID for Page',1);
InsertAdminPhrase(0, 'page_html_id_descr', 'This value (max. 100 characters) will be accessible within the Skins page as <b>[PAGE_HTML_ID]</b> variable.',1);
InsertAdminPhrase(0, 'page_menuwidth', 'Menu Item Width',1);
InsertAdminPhrase(0, 'page_menuwidth_descr', 'Width in Pixels for this page entry in the menu system (default: 0)?<br /><b>ONLY used by old SD 2.x skins!</b>',1);
InsertAdminPhrase(0, 'no_selection', '[No Selection]', 2);
InsertAdminPhrase(0, 'plugins_confirm_custom_delete', 'You have checked at least one Custom Plugin to be deleted! Do you want to continue?', 2);
InsertAdminPhrase(0, 'plugins_submit_hint', 'Updates all tabs!', 2);
InsertAdminPhrase(0, 'plugins_used_hint','How many pages include this plugin?',2);
InsertAdminPhrase(0, 'media_thumb_mode_resize', 'Resize (keeping ratio)',3,true);
InsertAdminPhrase(0, 'media_thumb_mode_scaling', 'Scale (ignoring ratio/squared off; fixed width/height)',3,true);
InsertAdminPhrase(0, 'media_fonts_preview', 'Fonts Preview',3);
InsertAdminPhrase(0, 'media_watermark_custom_lbl', 'Custom:',3);
InsertAdminPhrase(0, 'media_preview_lbl', 'Preview',3);
InsertAdminPhrase(0, 'media_err_upload_js', 'Sorry, but there was an error:',3);
InsertAdminPhrase(0, 'media_image_background_color', 'Image Background Color',3);
InsertAdminPhrase(0, 'media_image_background_color_descr',
  'The background color may be used for "squared-off" images to fill borders above/below or left/right of the resized image.<br />Default: <b>#FFFFFF</b> (white)',3);

InsertAdminPhrase(0, 'users_error_invalid_usergroup', 'Invalid or no usergroup specified!',5);
InsertAdminPhrase(0, 'users_back_btn', '&laquo; Back',5);

// Fix some template phrases' admin page (re-added in SD 3.7.2 upgrade!)
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."adminphrases
  SET adminpageid = 6 WHERE adminpageid = 0 AND varname LIKE 'tmpl\\_%'");

InsertAdminPhrase(0, 'skins_xml_errors', 'XML Parsing Errors<br />The following errors were reported when loading XML files:',6);
InsertAdminPhrase(0, 'skins_abandon_changes', 'Editor content was changed! Abandon changes???',6);
InsertAdminPhrase(0, 'skins_theme', 'Theme',6);
InsertAdminPhrase(0, 'skins_hotkeys', 'Hotkeys',6);
InsertAdminPhrase(0, 'skins_menu_item_link_descr', 'Single menu item entry link with placeholders, default: <b>&lt;a href="[LINK]" [TARGET]&gt;[NAME]&lt;/a&gt;</b><br />
 May also contain [HTML_CLASS] or [HTML_ID] (edit a page to configure values).', 6, true);
InsertAdminPhrase(0, 'skins_uninstall_cancelled', 'Skin was not uninstalled.', 6);
InsertAdminPhrase(0, 'skins_uninstall_removefolder', 'Remove skin folder and its content (if permissions allow it)?', 6);

$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups_config', 'allow_uname_change', 'TINYINT(1) UNSIGNED NOT NULL DEFAULT 0');
InsertAdminPhrase(0,'allow_uname_change', 'Allow users of this group to change their username (default: No)?', 5);
DeletePhrase(1,'username_changed', 'Username changed:');

InsertPhrase(1, 'upload_err_path_incorrect', 'Attachment path incorrect or not writable!');
InsertPhrase(1, 'common_attachment_denied', 'You do not have permission to access the attachment, sorry.');
InsertPhrase(1, 'common_attachment_unavailable', 'Sorry, but the attachment is currently not available.');
InsertPhrase(1, 'common_remove_tag', 'Remove selected tag from list?');
InsertPhrase(1, 'common_media_brightness', 'Brightness (-255/255)');
InsertPhrase(1, 'common_media_contrast', 'Contrast (-100/100)');
InsertPhrase(1, 'common_media_gamma', 'Gamma (default: 1.0)');
InsertPhrase(1, 'common_media_blur', 'Blur');
InsertPhrase(1, 'common_media_emboss', 'Emboss');
InsertPhrase(1, 'common_media_grayscale', 'Grayscale');
InsertPhrase(1, 'common_media_mean_removal', 'Mean Removal');
InsertPhrase(1, 'common_media_flip', 'Flip');
InsertPhrase(1, 'common_media_mirror', 'Mirror');
InsertPhrase(1, 'common_media_negate', 'Negate');
InsertPhrase(1, 'common_media_rotate', 'Rotate (0-360)');
InsertPhrase(1, 'common_media_smoothness', 'Smoothness (0/100)');
InsertPhrase(1, 'common_media_colorize', 'Colorize (hex, e.g. F3)');
InsertPhrase(1, 'common_media_watermarktext', 'Watermark Text');
InsertPhrase(1, 'common_media_textcolor', 'Text Color');
InsertPhrase(1, 'common_media_bgcolor', 'Background Color');
InsertPhrase(1, 'common_media_pixelate', 'Pixelate block size (1-99 pixels)');
InsertPhrase(1, 'common_media_preview', 'Preview');
InsertPhrase(1, 'common_media_preview', 'Preview');
InsertPhrase(1, 'common_media_revert', 'Revert');
InsertPhrase(1, 'common_media_applynow', 'Apply now!');
InsertPhrase(1, 'common_media_start_cropping', 'Start Cropping');
InsertPhrase(1, 'common_media_stop_cropping', 'Stop Cropping');
InsertPhrase(1, 'common_media_image_filters', 'Image Filters');
InsertPhrase(1, 'common_media_image_filters_hint',
  'Note: these filter are being applied as the first step, separate from resizing, cropping and watermarking.<br /><br />
  These filters depend on the installed GD library (PHP) and not all may work (could result in "white page" error).',true);
InsertPhrase(1, 'common_media_quality', 'Quality');
InsertPhrase(1, 'common_media_compression', 'Compression');
InsertPhrase(1, 'common_msg_crop_confirm_js', 'Cropping an image cannot be undone! CONTINUE?');
InsertPhrase(1, 'common_msg_filters_confirm_js', 'Applying filters cannot be undone (use preview to test)! CONTINUE?');

InsertPhrase(1, 'err_imagetype_unsupported', 'Image type "<b>%s</b>" not supported!<br />');
InsertPhrase(1, 'err_sd_media-1',  'Error: unsupported media',true);
InsertPhrase(1, 'err_sd_media-2',  'Error: wrong file extention');
InsertPhrase(1, 'err_sd_media-3',  'Error: file not found');
InsertPhrase(1, 'err_sd_media-4',  'Error: failed to create thumbnail');
InsertPhrase(1, 'err_sd_media-5',  'Error: invalid filesize');
InsertPhrase(1, 'err_sd_media-6',  'Error: invalid image width');
InsertPhrase(1, 'err_sd_media-7',  'Error: invalid image height');
InsertPhrase(1, 'err_sd_media-8',  'Error: invalid file upload');
InsertPhrase(1, 'err_sd_media-9',  'Error: invalid data');
InsertPhrase(1, 'err_sd_media-10', 'Error: folder not found');
InsertPhrase(1, 'err_sd_media-11', 'Error: folder not writable');
InsertPhrase(1, 'err_sd_media-12', 'Error: GD libary not installed');
InsertPhrase(1, 'err_sd_media-13', 'Error: image filter operation failed');

InsertPhrase(1, 'likes_guest_name', 'Guest');
InsertPhrase(1, 'likes_guests_name', 'Guests');

InsertPhrase(6, 'error_no_name', 'Please enter your name',true); //Fix for SD 3.3.0

InsertPhrase(11, 'image_error-12',        'Error: GD libary not installed');
InsertPhrase(11, 'image_error-13',        'Error: image filter operation failed');
InsertPhrase(11, 'status_no_articles',    'No articles found.');
InsertPhrase(11, 'status_no_files',       'No files found.');
InsertPhrase(11, 'status_no_media',       'No media found.');
InsertPhrase(11, 'page_myarticles_title', 'User Articles',1);
InsertPhrase(11, 'page_myblog_title',     'User Blog',1);
InsertPhrase(11, 'page_myforum_title',    'User Forum',1);
InsertPhrase(11, 'page_myfiles_title',    'User Files',1);
InsertPhrase(11, 'page_mymedia_title',    'User Media',1);
InsertPhrase(11, 'options_mark_selected_unread','Mark selected as unread');
InsertPhrase(11, 'loading_msg',           'Loading...');
InsertAdminPhrase(11, 'profiles_col_public_input', 'Public<br />Input?', 2);

InsertMainSetting('comments_form_footer', 'comment_options', 'Comments Form Footer',
  'HTML for the bottom of the frontpage Comments form:<br />'.
  'This <b>must</b> include at least the HTML submit button but can be extended by '.
  'extra markup for e.g. site-specific links or further, static information.<br /><br />'.
  'Default: <b>&lt;input type="submit" value="{post_comment}" /&gt;<b>',
  'wysiwyg', '<input type="submit" value="{post_comment}" />', 12, true);

$DB->query("DROP TABLE IF EXISTS {oauth_session}");
$DB->query("CREATE TABLE ".PRGM_TABLE_PREFIX."oauth_session (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `state` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `access_token` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `access_token_secret` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `expiry` datetime DEFAULT NULL,
  `type` char(12) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `server` char(12) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `creation` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `authorized` char(1) COLLATE utf8_unicode_ci DEFAULT '0',
  `userid` int(10) unsigned NOT NULL DEFAULT 0,
  `refresh_token` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `social_oauth_session_index` (`session`,`server`),
  KEY (`userid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

InsertAdminPhrase(0, 'settings_social_media_twitter', 'Twitter', 0);
InsertMainSetting('twitter_consumer_key', 'settings_social_media_twitter', 'Twitter Consumer Key',
  'The Twitter <b>consumer key</b> (and below secret) are required for authentication prior to using the Twitter plugin.<br />
  In order to obtain OAuth credentials, see <a href="https://dev.twitter.com/apps" target="_blank">https://dev.twitter.com/apps</a>
  to create a developer account and an application entry for your CMS installation.<br />
  <b>Note:</b> This data is encrypted for storage and <b>must be reentered</b> whenever the
  DB-name/username/password for the CMS database change!', 'enc', '', 10);
InsertMainSetting('twitter_consumer_secret', 'settings_social_media_twitter', 'Twitter Consumer Secret',
  'The Twitter <b>consumer secret</b> for authentication.', 'enc', '', 20);

// Preliminary support for MyBB forum integration:
if(!$DB->query_first("SELECT 1 FROM ".PRGM_TABLE_PREFIX."usersystems WHERE `name` = 'MyBB'"))
{
  $DB->query("INSERT INTO ".PRGM_TABLE_PREFIX."usersystems (`usersystemid`, `name`, `activated`, `dbname`, `queryfile`, `tblprefix`, `folderpath`, `cookietimeout`, `cookieprefix`, `extra`) VALUES
  (NULL, 'MyBB', '0', '', 'mybb.php', 'mybb_', '', 3600, '', '')");
}

// Preliminary support for punBB forum integration:
if(!$DB->query_first("SELECT 1 FROM ".PRGM_TABLE_PREFIX."usersystems WHERE `name` = 'punBB'"))
{
  $DB->query("INSERT INTO ".PRGM_TABLE_PREFIX."usersystems (`usersystemid`, `name`, `activated`, `dbname`, `queryfile`, `tblprefix`, `folderpath`, `cookietimeout`, `cookieprefix`, `extra`) VALUES
  (NULL, 'punBB', '0', '', 'punbb.php', 'punbb_', '', 1209600, '', '')");
}
if($forumid = GetPluginID('Forum'))
{
  //fix for SD 3.4.2 typo:
  DeletePhrase($forumid,'msg_mod_option_topcis_mod_all');
  InsertPhrase($forumid,'msg_mod_option_topics_mod_all','Moderate <strong>ALL</strong> of the user\'s posts (posts become invisible)?');
  $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'plugins'.
             " SET version = '3.7.0' WHERE pluginid = %d AND version = '3.4.1'",$forumid);
}

$DB->query('UPDATE '.PRGM_TABLE_PREFIX.'plugins'.
           " SET version = '3.7.0' WHERE pluginid IN (3,10,11,12) AND version = '3.2.0'");


// ############################################################################
// MOBILE SUPPORT CHANGES
// ############################################################################

// CATEGORIES
$tbl = PRGM_TABLE_PREFIX.'categories';
if(!$DB->column_exists($tbl, 'mobile_designid'))
{
  $DB->add_tablecolumn($tbl, 'mobile_designid', 'INT(11)', "NOT NULL DEFAULT 0 AFTER `designid`");
  $DB->query("UPDATE $tbl SET mobile_designid = designid");
  $DB->query("UPDATE {mainsettings} SET value = '0' WHERE varname = 'enable_mobile_detection'");
}

// SKINS
$tbl = PRGM_TABLE_PREFIX.'skins';
$doUpdate = !$DB->column_exists($tbl, 'mobile_header');
$DB->add_tablecolumn($tbl, 'mobile_header', 'TEXT', "COLLATE utf8_unicode_ci NULL AFTER `header`");
$DB->add_tablecolumn($tbl, 'mobile_footer', 'TEXT', "COLLATE utf8_unicode_ci NULL AFTER `footer`");
$DB->add_tablecolumn($tbl, 'mobile_menu_level0_opening', 'TEXT', "COLLATE utf8_unicode_ci NULL");
$DB->add_tablecolumn($tbl, 'mobile_menu_level0_closing', 'TEXT', "COLLATE utf8_unicode_ci NULL");
$DB->add_tablecolumn($tbl, 'mobile_menu_submenu_opening', 'TEXT', "COLLATE utf8_unicode_ci NULL");
$DB->add_tablecolumn($tbl, 'mobile_menu_submenu_closing', 'TEXT', "COLLATE utf8_unicode_ci NULL");
$DB->add_tablecolumn($tbl, 'mobile_menu_item_opening', 'TEXT', "COLLATE utf8_unicode_ci NULL");
$DB->add_tablecolumn($tbl, 'mobile_menu_item_closing', 'TEXT', "COLLATE utf8_unicode_ci NULL");
$DB->add_tablecolumn($tbl, 'mobile_menu_item_link', 'TEXT', "COLLATE utf8_unicode_ci NULL");
if($doUpdate)
{
  $DB->query('UPDATE '.$tbl." SET mobile_header = IFNULL(header,''), mobile_footer = IFNULL(footer,''),
    mobile_menu_level0_opening = '<div id=\"mobilenavigation\" class=\"mobile-menu clearfix\"><ul>',
    mobile_menu_level0_closing = '</ul></div>',
    mobile_menu_submenu_opening = IFNULL(menu_submenu_opening,''),
    mobile_menu_submenu_closing = IFNULL(menu_submenu_closing,''),
    mobile_menu_item_opening = IFNULL(menu_item_opening,''),
    mobile_menu_item_closing = IFNULL(menu_item_closing,''),
    mobile_menu_item_link = IFNULL(menu_item_link,'')");
}

//SKIN_CSS
$tbl = PRGM_TABLE_PREFIX.'skin_css';
if(!$DB->column_exists($tbl, 'mobile'))
{
  $DB->add_tablecolumn($tbl, 'mobile', 'TINYINT', "NOT NULL DEFAULT 0 AFTER `skin_id`");
  $DB->query("UPDATE $tbl SET `mobile` = 0 WHERE `mobile` IS NULL");
  if(!$DB->query_first('SELECT 1 FROM '.$tbl." WHERE skin_id = 0 AND mobile = 1 AND var_name = 'mobile-skin-css' LIMIT 1"))
  {
    // Try to copy over existing skin-css entry from current skin (if exists)
    $skincss = '';
    $skinid = $DB->query_first('SELECT skinid FROM '.PRGM_TABLE_PREFIX."skins WHERE activated = 1");
    if(!empty($skinid['skinid']))
    {
      if($getcss = $DB->query_first('SELECT css FROM '.$tbl."
                                     WHERE skin_id = %d AND mobile = 0
                                     AND var_name = 'skin-css' LIMIT 1",
                                     $skinid['skinid']))
      {
        $skincss = $getcss['css'];
      }
    }
    $DB->query("INSERT INTO $tbl (`skin_css_id`,`skin_id`,`mobile`,`plugin_id`,
    `var_name`,`css`,`admin_only`,`disabled`,`description`)
    VALUES (NULL,0,1,0,'mobile-skin-css','".$DB->escape_string($skincss."

/* ################### Responsive Mobile Menu Styles  ####################### */
/* JS/CSS (c) EGrappler.com */
/* http://www.egrappler.com/free-responsive-html5-portfolio-business-website-template-brownie/ */
/* ################### Responsive Mobile Menu Styles  ####################### */
/* Add [MOBILENAVIGATION] to the [MOBILE_HEADER] to use this mobile menu! */

/* === Clearfix === */
.clear {
  clear:both;
  display:block;
  height:0;
  overflow:hidden;
  visibility:hidden;
  width:0
}
.clearfix:after {
  clear:both;
  content:' ';
  display:block;
  font-size:0;
  height:0;
  line-height:0;
  visibility:hidden;
  width:0
}
* html .clearfix, :first-child+html .clearfix {
  zoom:1
}

/* ID and class can be used for separate purposes */
div#mobilenavigation {
  background: #E4E4E4 url(includes/images/nav.jpg) repeat-x;
  display: block;
  margin: 0 30px 0 30px;
  width: auto;
}
.mobile-menu {
  background-color: #e9e9e9;
  clear: both;
  display: block;
  margin: 0;
  padding: 0 10px 0 0;
}
.mobile-menu a {
  font-family: \"Century Gothic\", \"Verdana\", \"Tahoma\", Arial, Helvetica, sans-serif;
}
.mobile-menu ul {
  margin:0;
}
.mobile-menu li ul {
  background:url(includes/images/shadow.png) no-repeat bottom right;
  padding-top:0px;
  left:-2px;
  padding: 0 8px 9px 0;
  -moz-border-radius-bottomleft: 17px;
  -moz-border-radius-topright: 17px;
  -webkit-border-top-right-radius: 17px;
  -webkit-border-bottom-left-radius: 17px;
}
.mobile-menu li ul li {
  box-shadow:2px 2px 2px 0px rgba(0,0,0,0.1);
}
.mobile-menu li {
  background: #E4E4E4 url(includes/images/nav.jpg) repeat-x;
  white-space:nowrap;
  display:block;
  position:relative;
  margin:0;
  padding:0;
  z-index:100;
}
.mobile-menu a {
  color: #000;
  display:block;
  font-size:13px;
  font-family: \"Century Gothic\", \"Verdana\", \"Tahoma\", Arial, Helvetica, sans-serif;
  position:relative;
  padding: 6px;
}
.mobile-menu a:hover {
  background-color:#f5f5f5;
  color: #4c4c4c;
  text-decoration:none;
}
.mobile-menu li.submenu > a {
  padding-right:20px;
  background:url(includes/images/arrow_320.png) no-repeat right;
  cursor:default;
}
.mobile-menu > ul > li {
  float:left;
  /* margin-right: 28px; */
}
.mobile-menu > ul > li:last-child {
  margin-right:0;
}
.mobile-menu li ul {
  display:none;
  position:absolute;
  top:100%;
  z-index:100;
}
.mobile-menu li:hover > ul {
  display:block;
}
.mobile-menu li ul li.submenu > a {
  padding-right:10px;
  background:#e9e9e9 url(includes/images/submenu_left_arrow.png) no-repeat right;
}
.mobile-menu li ul li.submenu > a:hover {
  padding-right:10px;
  background:#f5f5f5 url(includes/images/submenu_left_arrow.png) no-repeat right;
}
.mobile-menu li ul li.current > a {
  background: #dbdbdb;
}
.mobile-menu li ul li {
  border-bottom:1px solid #d8d8d8;
  background:#e9e9e9;
}
.mobile-menu li ul li a.current:hover,
.mobile-menu li ul li a:hover {
  background:#f5f5f5;
  color:#000;
}
.mobile-menu li ul li:last-child {
  border-bottom:1px solid #d8d8d8;
}
.mobile-menu li ul li a {
  padding:0 20px 0 12px;
  line-height:33px;
  background: #e9e9e9; /* #403830; */
}
.mobile-menu li ul li ul {
  top:-1px !important;
  left:100% !important;
  padding:0 !important;
}


/* ================= Tablet (Portrait) 768px - 959px ================= */
@media only screen and (min-width: 768px) and (max-width: 959px) {
/* Set here site containers to a smaller width, e.g.: */
/*
  div#header { width: 758px; }
  div#wrap { width: 758px; }
  div#content-wrap { width: 758px; margin: 0;}
  div#footer-wrap { width: 758px; }
*/
div#mobilenavigation { width: 758px; margin: 0; padding: 0;}
}


/* ================= Mobile (Portrait) < 767px ================= */
@media only screen and (max-width: 767px) {
/* Set here any site containers to auto width and/or start to hide elements, e.g.: */
/*
  div#header { display: none !important; }
  div#wrap,
  div#content-wrap,
  div#footer,
  div#footer-wrap { width: auto; }
  div#footer-wrap,
  div#content-wrap { margin-left: 10px; margin-right: 10px; }
*/
/* SD articles plugin: hide certain elements or make them wrap */
.article_head { height: auto !important; }
.article-image,
.article_head_left { display: none !important; }
.article_article,
.article_head_right div { display: block !important; clear: both !important; }
.article_footer_right {
  clear:both !important;
  display:block !important;
  float: none;
  height:auto;
  margin-top:20px !important;
}

/* Now, from here on the mobile menu will completely wrap all top-level entries: */
div#mobilenavigation {
  margin: 0 10px 0 10px;
  padding: 0;
  width: auto;
}
.mobile-menu ul {
  position:static !important;
  padding:0 !important;
}
.mobile-menu li {
  box-shadow:none !important;
  border:0 !important;
  border-top:1px solid #d9d9d9 !important;
  display:block !important;
  float:none !important;
  margin:0 !important;
}
.mobile-menu li.submenu > a {
  cursor:pointer;
}
.mobile-menu li a {
  padding:0 16px;
  line-height:33px;
}
.mobile-menu li ul li a {
  padding-left:32px !important;
}
.mobile-menu li ul li ul li a {
  padding-left:48px !important;
}
.mobile-menu li.submenu > a {
  padding-right:20px;
  background:url(includes/images/arrow_320.png) no-repeat right;
}
.mobile-menu li ul li.submenu > a {
  padding-right:10px;
  background:url(includes/images/arrow_320.png) no-repeat right;
}
.mobile-menu li ul li.submenu > a:hover {
  padding-right:10px;
  background:#f5f5f5 url(includes/images/arrow_320.png) no-repeat right;
}
.mobile-menu li.submenu > a {
  padding-right:20px;
  background:url(includes/images/arrow_320.png) no-repeat right;
}
.mobile-menu li ul {
  display:none !important;
}
.mobile-menu li:hover > ul {
  display:block !important;
}
}


/* ================= Mobile (Landscape) 480px - 767px ================= */
@media only screen and (min-width: 480px) and (max-width: 767px) {
.article_footer_right { display: none !important; }
div#mobilenavigation {
  background: transparent;
  padding-right: 0;
}
}


/* ================= Mobile (Landscape) 480px - 767px ================= */
@media only screen and (min-width: 320px) and (max-width: 480px) {
.article_footer_right { display: none !important; }
div#mobilenavigation { padding-right: 0;}
}


/* ================= Mobile (Portrait) < 320px ================= */
@media only screen and (max-width: 317px) {
/* SD articles plugin: hide pretty much everything, except title */
.article_subtitle { border-bottom: 0 !important; }
.article_title a, .article_title h1  { font-size: 15px !important; }
.article_tags { display: none !important; width:0;height:0;overflow:hidden; }
.article_footer { display: none !important; }
.article_footer_right,
.article_tags,
.article_description,
.article_read_more,
.article_article { display: none !important; }
div#mobilenavigation {
  width: 260px; margin-right: 10px; padding-right: 0;
}
}

")."',0,0,'')");
  }
}

//USERGROUPS
$tbl = PRGM_TABLE_PREFIX.'usergroups';
if(!$DB->column_exists($tbl, 'categorymobilemenuids'))
{
  $DB->add_tablecolumn($tbl, 'categorymobilemenuids', 'TEXT', "NULL AFTER `categorymenuids`");
  $DB->query("UPDATE $tbl SET categorymobilemenuids = categoryviewids");
}

// NEW PAGESORT_MOBILE
$tbl = PRGM_TABLE_PREFIX.'pagesort_mobile';
if(!$DB->table_exists($tbl))
{
  $DB->query('CREATE TABLE '.$tbl.' SELECT * FROM '.PRGM_TABLE_PREFIX.'pagesort');
}

// NEW PAGESORT_MOBILE BACKUP TABLES
$tbl = PRGM_TABLE_PREFIX.'skin_bak_pgs_mobile';
if(!$DB->table_exists($tbl))
{
  $DB->query("CREATE TABLE $tbl (
    `skinid` int(10) unsigned NOT NULL DEFAULT 0,
    `categoryid` int(10) unsigned NOT NULL DEFAULT 0,
    `displayorder` int(10) unsigned NOT NULL DEFAULT 0,
    `pluginid` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
    KEY `sbpgs` (`skinid`,`categoryid`,`displayorder`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
}

$tbl = PRGM_TABLE_PREFIX.'skin_bak_cat';
if(!$DB->column_exists($tbl, 'mobile_designid'))
{
  $DB->add_tablecolumn($tbl, 'mobile_designid', 'INT(10) UNSIGNED', "NOT NULL DEFAULT 0 AFTER `designid`");
}

// INSERT NEW ADMIN PHRASES AND MAIN SETTING FOR MOBILE SWITCHING
InsertAdminPhrase(0, 'common_display_in_mobile_menu', 'Display page link in mobile version of website menu?', 0);
InsertAdminPhrase(0, 'pages_mobile_content', 'Mobile Content', 1);
InsertAdminPhrase(0, 'pages_edit_mobile_plugin_positions', 'Edit Mobile Plugin Positions', 1);
InsertAdminPhrase(0, 'pages_usergroup_mobile_link_instructions', 'Select which usergroups will see this page link in the mobile version of the website menu.', 1);
InsertAdminPhrase(0, 'skins_mobile_header', 'Mobile Header', 6);
InsertAdminPhrase(0, 'skins_mobile_footer', 'Mobile Footer', 6);
InsertAdminPhrase(0, 'skins_mobile_menu_settings', 'Mobile Menu Settings', 6);
InsertAdminPhrase(0, 'skins_mobile_menu_settings_hint', 'Note: these settings only apply for '.PRGM_NAME."-compatible skins \nfor the frontpage mobile menu system.<br />\nIf the default menu system is to be used, &lt;ul&gt; / &lt;/ul&gt; HTML tags MUST be used.<br />\nImportant: opening and closing tags must be the same element type!", 6);
InsertAdminPhrase(0, 'skins_mobile_menu_level0_opening', 'Top-Level Menu Opening HTML Tag', 6);
InsertAdminPhrase(0, 'skins_mobile_menu_level0_closing', 'Top-Level Menu Closing HTML Tag', 6);
InsertAdminPhrase(0, 'skins_mobile_menu_submenu_opening', 'Sub-Menu Opening HTML Tag', 6);
InsertAdminPhrase(0, 'skins_mobile_menu_submenu_closing', 'Sub-Menu Closing HTML Tag', 6);
InsertAdminPhrase(0, 'skins_mobile_menu_item_opening', 'Menu Item Opening HTML Tag', 6);
InsertAdminPhrase(0, 'skins_mobile_menu_item_closing', 'Menu Item Closing HTML Tag', 6);
InsertAdminPhrase(0, 'skins_mobile_menu_item_link', 'Menu Item Link', 6);
InsertAdminPhrase(0, 'skins_mobile_menu_level0_opening_descr', 'HTML opening tag for top-level menu container (default: <strong>&lt;div id=&quot;mobilenavigation&quot; class=&quot;mobile-menu clearfix&quot;&gt;&lt;ul&gt;</strong> )', 6);
InsertAdminPhrase(0, 'skins_mobile_menu_level0_closing_descr', 'HTML closing tag for top-level menu container (default: <strong>&lt;/ul&gt;&lt;/div&gt;</strong> )', 6);
InsertAdminPhrase(0, 'skins_mobile_menu_submenu_opening_descr', 'HTML opening tag for sub-menu lists (default: <strong>&lt;ul&gt;</strong> )', 6);
InsertAdminPhrase(0, 'skins_mobile_menu_submenu_closing_descr', 'HTML closing tag for sub-menu lists (default: <strong>&lt;/ul&gt;</strong> )', 6);
InsertAdminPhrase(0, 'skins_mobile_menu_item_opening_descr', 'HTML opening tag for a menu item (default: <strong>&lt;li&gt;</strong> )', 6);
InsertAdminPhrase(0, 'skins_mobile_menu_item_closing_descr', 'HTML closing tag for a menu item (default: <strong>&lt;/li&gt;</strong> )', 6);
InsertAdminPhrase(0, 'skins_mobile_menu_item_link_descr', 'Single menu item entry link with placeholders (default: <strong>&lt;a href=&quot;[LINK]\&quot; [TARGET]&gt;[NAME]&lt;/a&gt;</strong> )', 6);

InsertMainSetting('mobile_menu_javascript', 'settings_general_settings', 'Mobile Frontpage Menu Javascript',
'Default Javascript code for the mobile frontpage menu to be created.<br />This needs to be adapted whenever a skin is used
with non-standard menu HTML tags or the minimum width of menu items is to be changed etc.', 'textarea',
'<script type="text/javascript">
//<![CDATA[
<script type="text/javascript">
//<![CDATA[
var menu_shown = true;
if(typeof(jQuery) !== "undefined") {
jQuery(document).ready(function() {
  /* Regular Menu (still included) */
  var sd_menu = jQuery("ul.sf-menu");
  if(jQuery(sd_menu).length && typeof(jQuery.fn.supersubs)!=="undefined"){
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

  /* ################### Responsive Mobile Menu #######################
     based on "Brownie" template; JS/CSS (c) EGrappler.com
     http://www.egrappler.com/free-responsive-html5-portfolio-business-website-template-brownie/
     ################### Responsive Mobile Menu ####################### */
  jQuery(".mobile-menu li:has(ul)").addClass("submenu");
  jQuery(".mobile-menu").on("mouseenter", "li", function () {
    jQuery(this).children("ul").hide().stop(true, true).fadeIn("normal");
  }).on("mouseleave", "li", function () {
    jQuery(this).children("ul").stop(true, true).fadeOut("normal");
  });
  /* Fixing responsive menu */
  jQuery(window).resize(function () {
    jQuery(".mobile-menu").children("ul").children("li").children("ul").hide();
    jQuery(".mobile-menu").children("ul").children("li").children("ul").children("li").children("ul").hide();
  });

/* Mobile Accordion Menu (fb-menu and fb-menu2) */
/*
  // This is code for an alternate display by accordion hover menu!
  // Can be removed, but advised to make a backup of this code beforehand.
  // Requires jquery.hoveraccordion.min.js in header!
  var fb_menu = jQuery("ul.fb-menu");
  var fb_menu2 = jQuery("ul.fb-menu2");
  var fb_menucount = jQuery(fb_menu).length + jQuery(fb_menu2).length;
  if(fb_menucount>0 && typeof(jQuery.fn.hoverAccordion)!=="undefined"){
    if(jQuery(fb_menu).length > 0){
      jQuery("ul.fb-menu").hoverAccordion({
        speed: "fast",
        keepHeight: false,
        onClickOnly: false
      });
    }
    if(jQuery(fb_menu2).length > 0){
      jQuery("ul.fb-menu2").hoverAccordion({
        speed: "fast",
        keepHeight: false,
        onClickOnly: false
      });
      jQuery("ul.fb-menu2").children("li:first").addClass("firstitem");
      jQuery("ul.fb-menu2").children("li:last").addClass("lastitem");
    }
    jQuery("#fb-menu").hide();
    jQuery(window).resize(function() {
      if(menu_shown) {
        jQuery("#MainContent").animate(
          { width: $("#wrapper").width() - 200 },
          0, function(){});
      }
    });
    jQuery("#NavBTN").toggle(
      function() {
        jQuery("#fb-menu").show();
        jQuery("#MainContent").animate(
          { width: jQuery("#wrapper").width() - 200 },
          0, function(){});
        menu_shown = true;
      },
      function() {
        jQuery("#fb-menu").hide();
        jQuery("#MainContent").animate(
          { width: jQuery("#wrapper").width() },
          0, function(){});
        menu_shown = false;
      }
    );
  }
*/
})
}
//]]>
</script>', 320);

# WE ALREADY HAVE "Enable Mobile Device Detection" setting since SD 3.4.3!!!
# So just update description for that setting:
DeleteAdminPhrase(0, 'enable_mobile_device_detection_descr');
InsertAdminPhrase(0, 'enable_mobile_device_detection_descr',
  'Lets you select if your site will handle mobile browsers differently than normal brwosers (default: No)<br />'.
  'If enabled, separate sets of header/footer, mobile skin-css and mobile page settings are being used.<br />'.
  'Each page can have assigned a regular and a mobile layout with separate plugins.', 0);

