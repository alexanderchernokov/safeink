<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usersystems', 'cookiedomain','varchar(200)',"COLLATE utf8_unicode_ci NOT NULL DEFAULT '' AFTER `cookietimeout`");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usersystems', 'cookiepath','varchar(200)',"COLLATE utf8_unicode_ci NOT NULL DEFAULT '' AFTER `cookiedomain`");

// Add XenForo usersystem once:
$xf = $DB->query_first("SELECT usersystemid FROM ".PRGM_TABLE_PREFIX."usersystems WHERE name = 'XenForo 1'");
if(empty($xf[0]))
{
  $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX."usersystems (usersystemid, name, activated, dbname, queryfile, tblprefix, folderpath, cookietimeout, cookiedomain, cookiepath, cookieprefix, extra)
  VALUES (null, 'XenForo 1', '0', 'xenforo', 'xenforo1.php', 'xf_', '../xenforo/', 900, '', '', 'xf_', '')");
}

$DB->query('DELETE FROM '.PRGM_TABLE_PREFIX."mainsettings WHERE varname = 'url_extension' AND groupname <> 'settings_seo_settings'");

$table = PRGM_TABLE_PREFIX.'plugins';
$DB->query("ALTER TABLE ".$table." CHANGE COLUMN `name` `name` VARCHAR(254) NOT NULL collate utf8_unicode_ci DEFAULT ''");
$DB->query("ALTER TABLE ".$table." CHANGE COLUMN `pluginpath` `pluginpath` VARCHAR(254) NOT NULL collate utf8_unicode_ci DEFAULT ''");
$DB->query("ALTER TABLE ".$table." CHANGE COLUMN `settingspath` `settingspath` VARCHAR(254) NOT NULL collate utf8_unicode_ci DEFAULT ''");
$DB->query("ALTER TABLE ".$table." CHANGE COLUMN `authorlink` `authorlink` VARCHAR(254) NOT NULL collate utf8_unicode_ci DEFAULT ''");
$DB->add_tablecolumn($table, 'base_plugin', 'VARCHAR(254)', "NULL collate utf8_unicode_ci DEFAULT ''");
$DB->query("UPDATE ".$table." SET base_plugin = 'Articles' where pluginid = 2");
$DB->query("UPDATE ".$table." SET base_plugin = 'Image Gallery' where pluginid = 17");
$DB->query("UPDATE ".$table." SET base_plugin = 'Download Manager 2' where name like 'Download Manager 2%'");

$DB->query('UPDATE '.PRGM_TABLE_PREFIX."adminphrases SET defaultphrase =
           'Edit here the default <strong>email body</strong> text for notification, which may contain the following placeholders:<br /><br />
           <strong>[date]</strong> = date of comment, <strong>[username]</strong> = user name, <strong>[pagename]</strong> = name of the page where comment was posted<br />
           <strong>[pagelink]</strong> = clickable link to the page, <strong>[pluginname]</strong> = name of the plugin for which the comment was posted<br />
           <strong>[commentstatus]</strong> = status of the comment (approved or unapproved).'
           WHERE pluginid = 0 AND adminpageid = 0 AND varname = 'email_body_descr'");

$DB->query('UPDATE '.PRGM_TABLE_PREFIX."adminphrases SET defaultphrase =
           'This email address will receive an email message whenever a new link was submitted:<br />Separate multiple addresses with a single comma.'
           WHERE pluginid = 16 AND adminpageid = 2 AND varname = 'link_notification_descr'");
InsertAdminPhrase(0,'common_downloaded_plugins','Add-On Plugins',0,true);
InsertPhrase(1,'common_edit_article','Edit Article');
InsertPhrase(1,'comments_no_username','Guest');
InsertAdminPhrase(0,'add_profile_field','Add Profile Field',5);
InsertAdminPhrase(0,'update_profile_field','Update Profile Field',5);
InsertAdminPhrase(0,'add_profile_group','Add Profile Group',5);
InsertAdminPhrase(0,'update_profile_config','Update Configuration',5);
InsertAdminPhrase(0,'menu_users_profile_groups','Profile Groups',5);
InsertAdminPhrase(0,'profile_fields','Profile Fields',5);
InsertAdminPhrase(0,'profiles_confirm_delete_field','<strong>Delete Profile Field?</strong><br />Please confirm to delete the below profile field.<br /><strong>Warning:</strong> removing a field will destroy all information any user may have entered in this field! This field and it\'s data will not be recoverable!<br />',5,true);
InsertAdminPhrase(0,'profiles_col_groupname','Groupname',5);
InsertAdminPhrase(0,'profiles_col_displayorder','Display<br />Order',5);
InsertAdminPhrase(0,'profiles_col_displayname','Display Name',5);
InsertAdminPhrase(0,'profiles_col_visible_to_user','Visible<br />to User?',5);
InsertAdminPhrase(0,'profiles_col_on_public_profile','On Public<br />Profile Page?',5);
InsertAdminPhrase(0,'profiles_col_visible_on_frontpage','On Public<br />Profile Page?',5);
InsertAdminPhrase(0,'profiles_col_view_only','View Only?',5);
InsertAdminPhrase(0,'profiles_col_required','Required?',5);
InsertAdminPhrase(0,'profiles_col_delete_group','Delete?',5);
InsertAdminPhrase(0,'profiles_new_field','New Field',5);
InsertAdminPhrase(0,'profiles_new_group','New Group',5);
InsertAdminPhrase(0,'profiles_err_displayname','The display name must not be empty!',5);
InsertAdminPhrase(0,'profiles_err_fieldtype','The provided field type is not supported or invalid!',5);
InsertAdminPhrase(0,'profiles_err_groupname','The group name must not be empty!',5);
InsertAdminPhrase(0,'profiles_confirm_group_delete','Really DELETE the profile group including ALL fields?\r\nThe fields and all their data will be lost!',5);
InsertAdminPhrase(0,'profiles_field_label','Field Label:',5);
InsertAdminPhrase(0,'profiles_field_length','Field Length:<br />Max. amount of characters for Text/BBCode fields.',5,true);
InsertAdminPhrase(0,'profiles_field_order','Field Order<br />Display order within group, enter a whole number.',5,true);
InsertAdminPhrase(0,'profiles_field_public','Field is displayed on the <strong>public</strong> profile page?',5,true);
InsertAdminPhrase(0,'profiles_field_readonly','Field is <strong>view-only</strong>?<br />If set to Yes, this is only editable from within the admin panel and read-only for the user.',5,true);
InsertAdminPhrase(0,'profiles_field_required','Field is <strong>required</strong> (for Text/BBCode fields)?',5,true);
InsertAdminPhrase(0,'profiles_field_show','<strong>Visible</strong> to user?<br />If set to No, this field is only visible within the admin panel.',5,true);
InsertAdminPhrase(0,'profiles_field_type','Field Type:',5);
InsertAdminPhrase(0,'profiles_fieldtype_bbcode','BBCode',5);
InsertAdminPhrase(0,'profiles_fieldtype_text','Plain Text (single line)',5,true);
InsertAdminPhrase(0,'profiles_fieldtype_textarea','Textarea (no formatting)',5);
InsertAdminPhrase(0,'profiles_fieldtype_date','Date',5);
InsertAdminPhrase(0,'profiles_fieldtype_yesno','Yes / No',5);
InsertAdminPhrase(0,'users_add_profile_group','Add New Profile Group',5);
InsertAdminPhrase(0,'users_add_profile_field','Add Profile Field',5);
InsertAdminPhrase(0,'users_add_field_to_group','Add a new field to selected group:',5);

$DB->query("UPDATE {adminphrases} SET defaultphrase = 'Maximum number of article links to display per page (max. 1000):'
           WHERE adminpageid = 2 AND pluginid = 3 AND varname = 'display_limit_desc'");

$tbl = PRGM_TABLE_PREFIX.'users_fields';
if(!$DB->column_exists($tbl,'is_custom'))
{
  $DB->add_tablecolumn($tbl, 'is_custom', 'TINYINT(1)', "NOT NULL DEFAULT 1 AFTER `fieldlabel`");
  $DB->query('UPDATE '.$tbl." SET is_custom = 0 WHERE groupname_id <= 7");
}
$DB->query('DELETE FROM '.PRGM_TABLE_PREFIX."phrases WHERE pluginid = 11 AND varname LIKE 'groupname_%' AND varname <> 'groupname_user_credentials'");

$tbl = PRGM_TABLE_PREFIX.'users_field_groups';
if(!$DB->column_exists($tbl,'groupname'))
{
  $DB->add_tablecolumn($tbl, 'groupname', 'VARCHAR(255)', "COLLATE utf8_unicode_ci NOT NULL DEFAULT '' AFTER `groupname_id`");
  $DB->query('UPDATE '.$tbl." SET groupname = 'Credentials' WHERE groupname_id = 1");
  $DB->query('UPDATE '.$tbl." SET groupname = 'About Me' WHERE groupname_id = 2");
  $DB->query('UPDATE '.$tbl." SET groupname = 'Address' WHERE groupname_id = 3");
  $DB->query('UPDATE '.$tbl." SET groupname = 'Phone' WHERE groupname_id = 4");
  $DB->query('UPDATE '.$tbl." SET groupname = 'Other Info' WHERE groupname_id = 5");
  $DB->query('UPDATE '.$tbl." SET groupname = 'Messaging' WHERE groupname_id = 6");
  $DB->query('UPDATE '.$tbl." SET groupname = 'Preferences' WHERE groupname_id = 7");
}
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'users_fields', 'show_on_reg', 'TINYINT(1)', "NOT NULL DEFAULT 0 AFTER `is_custom`");

$DB->query('DELETE FROM '.PRGM_TABLE_PREFIX."pluginsettings WHERE pluginid = 12 AND title = 'Banned IP Addresses'");

// ################################ Forum Plugin ##############################

if($forum_pluginid = $DB->query_first('SELECT pluginid FROM '.PRGM_TABLE_PREFIX."plugins WHERE name = 'Forum' LIMIT 1"))
{
  $forum_pluginid = $forum_pluginid[0];
}
if($forum_pluginid > 17)
{
  $DB->query("UPDATE ".$table." SET version = '3.4.1' WHERE pluginid = %d AND name = 'Forum'",$forum_pluginid);
  // Add plugin settings
  InsertPluginSetting($forum_pluginid, 'forum_display_settings', 'Auto Detect Links',
    'Try to automatically detect url\'s within bbcode text and convert them to links (default: Yes):', 'yesno', '1', 5);
  // Just for fresh install: add forum plugin to Forum page
  if(defined('INSTALLING_PRGM'))
  {
    $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX."p_forum_forums (`forum_id`, `online`, `is_category`, `parent_forum_id`,
     `title`, `description`, `last_topic_id`, `last_topic_title`, `last_post_id`, `last_post_username`, `last_post_date`,
     `topic_count`, `post_count`, `display_order`, `access_post`, `access_view`, `moderated`)
     VALUES
     (NULL, 1, 0, 1, 'Example Forum', 'This is an example forum section and can be maintained in the admin area.', 0, '', 0, '', 0,
     0, 0, 10, '', '', 0)");
  }
}
