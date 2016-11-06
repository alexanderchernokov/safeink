<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

// Fix some table's internal settings
$ignore_old = $DB->ignore_error;
$DB->ignore_error = true;
foreach(array('p2_news','p4_guestbook','p7_chatterbox','ratings','slugs','smilies','syslog','tags') as $tbl)
{
  $DB->query('ALTER TABLE '.PRGM_TABLE_PREFIX.$tbl.' ENGINE = MyISAM');
  $DB->query('ALTER TABLE '.PRGM_TABLE_PREFIX.$tbl.' COLLATE utf8_unicode_ci');
  $DB->query('ALTER TABLE '.PRGM_TABLE_PREFIX.$tbl.' CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci');
}
$DB->ignore_error = $ignore_old;
unset($DB->ignore_error);

if(!$DB->query_first('SELECT settingid FROM '.PRGM_TABLE_PREFIX."modulesettings WHERE moduleid = 200 AND settingid = 'eu_cookie'"))
{
  $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX."modulesettings
  (`moduleid`, `settingid`, `editable`, `enabled`, `description`, `input`, `value`, `displayorder`, `eventproc`) VALUES
  (200, 'eu_cookie', 1, 1, 'EU Cookie (default: No): Enable this option to alter Bad Behavior’s cookie handling to conform to 2012 EU cookie regulations. Note that at this time, we believe Bad Behavior is exempt from these regulations.', 'yesno', '0', 232, '')");
}
if(!$DB->query_first('SELECT settingid FROM '.PRGM_TABLE_PREFIX."modulesettings WHERE moduleid = 200 AND settingid = 'httpbl_key'"))
{
  $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX."modulesettings
  (`moduleid`, `settingid`, `editable`, `enabled`, `description`, `input`, `value`, `displayorder`, `eventproc`) VALUES
  (200, 'httpbl_key', 1, 1, 'Project HoneyPot API Key (default: empty; visit <a href=\"http://www.projecthoneypot.org/httpbl_api.php\" target=\"_blank\">http://www.projecthoneypot.org/httpbl_api.php</a>)', 'text', '', 235, '')");
}
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'bad_behavior', 'httpbl_code', 'INT(6)', 'NOT NULL DEFAULT 0');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'bad_behavior', 'httpbl_level', 'INT(6)', 'NOT NULL DEFAULT 0');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'usergroups', 'name');
$DB->add_tableindex(PRGM_TABLE_PREFIX.'usergroups', 'forumusergroupid');

// ***** User Profile additions *****
$tbl = PRGM_TABLE_PREFIX.'users_data';
$DB->add_tablecolumn($tbl, 'last_email_time',      'int(11)',     'NOT NULL DEFAULT 0 AFTER `msg_out_count`');
$DB->add_tablecolumn($tbl, 'profile_img_type',     'smallint(4)', 'NOT NULL DEFAULT 0 AFTER `user_profile_img`');
$DB->add_tablecolumn($tbl, 'profile_img_width',    'smallint(4)', 'NOT NULL DEFAULT 0 AFTER `profile_img_type`');
$DB->add_tablecolumn($tbl, 'profile_img_height',   'smallint(4)', 'NOT NULL DEFAULT 0 AFTER `profile_img_type`');
$DB->add_tablecolumn($tbl, 'profile_img_link',     'varchar(250) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT '' AFTER `profile_img_type`");
$DB->add_tablecolumn($tbl, 'profile_img_disabled', 'smallint(1)', 'NOT NULL DEFAULT 0 AFTER `profile_img_type`');
$DB->add_tablecolumn($tbl, 'profile_img_public',   'smallint(1)', 'NOT NULL DEFAULT 1 AFTER `profile_img_type`');

InsertPhrase(1,'common_enabled','enabled');
InsertPhrase(1,'common_disabled','disabled');
InsertPhrase(11,'picture_hint','A profile picture is normally only displayed within the user profile'.
                               ' with a convenient display size and could be used for e.g. portraits'.
                               ' or resemblances of the user.');
InsertPhrase(11,'picture_source','Picture Source');
InsertPhrase(11,'picture_upload_disabled','[---Upload Disabled---]');
InsertPhrase(11,'picture_new_upload','Upload a new profile picture:');
InsertPhrase(11,'picture_external_link','Link to an external image:');
InsertPhrase(11,'picture_current_link','Current link:');
InsertPhrase(11,'picture_link_hint','If possible, a local copy of the remote image will be created (thumbnailed). Only if that is not technically possible, the actual link will be used afterwards.');
InsertPhrase(11,'delete_link','Delete Link?');
InsertPhrase(11,'delete_picture','Delete Picture?');
InsertPhrase(11,'no_image','No image');
InsertPhrase(11,'msg_profile_picture_not_available','Profile picture customisation is not available for your account.');
InsertPhrase(12,'random_password','Random Password');
InsertPhrase(12,'generate_password','[Generate Password]');
InsertPhrase(12,'use_random_password','[Use Password]');

InsertAdminPhrase(0,'profile_picture_enabled', '<strong>Profile picture support</strong><br />Check the allowed profile picture options which are available for all users within this group:', 5);
InsertAdminPhrase(0,'picture_path', 'Storage path for profile pictures, relative to '.PRGM_NAME.' base folder (default: images/profiles/):<br />
  <strong>Note:</strong> this folder must be made writable on the server (e.g. by FTP program)<br />
  If left empty, profile picture upload will not work!', 5);
InsertAdminPhrase(0,'picture_max_width', 'Profile picture resizing to width in pixels (default: 120):', 5);
InsertAdminPhrase(0,'picture_max_height', 'Profile picture resizing to height in pixels (default: 120):', 5);
InsertAdminPhrase(0,'picture_max_size', 'Profile picture max. uploaded filesize in bytes:<br />The minimum value is <strong>2048</strong>, the default is <strong>102400</strong> (= 100KB).', 5);
// One-time update of max picture setting
if(isset($GLOBALS['mainsettings_sdversion']) &&
   version_compare($GLOBALS['mainsettings_sdversion'],'3.5.0','<'))
{
  $DB->query("UPDATE {usergroups_config} SET pub_img_max_size = 262144, pub_img_max_width = 350, pub_img_max_height = 350, pub_img_path = 'images/profiles/'");
}
InsertPluginSetting(12,'user_registration_settings', 'Show Generate Password',
  'Display a link to generate and apply a random password for the user.', 'yesno', '0');

// ***** User reports and reasons *****
InsertAdminPhrase(0,'menu_reports','User Reports',0);
InsertAdminPhrase(0,'menu_reports_view_reports','View User Reports',0);
InsertAdminPhrase(0,'menu_reports_view_reports_by_plugin','View Reports by Plugin',0);
InsertAdminPhrase(0,'menu_reports_view_report_reasons','View Report Reasons',0);
InsertAdminPhrase(0,'menu_reports_view_settings','View Reporting Settings',0);
InsertAdminPhrase(0,'reports','User Reports',4);
InsertAdminPhrase(0,'reports_by_plugin','Reports by Plugin',4);
InsertAdminPhrase(0,'reports_clear_filter','Clear Filter',4);
InsertAdminPhrase(0,'reports_closed','Closed',4);
InsertAdminPhrase(0,'reports_date_asc','Oldest First',4);
InsertAdminPhrase(0,'reports_date_descending','Newest First',4);
InsertAdminPhrase(0,'reports_delete','Delete',4);
InsertAdminPhrase(0,'reports_delete_tag','Delete Report:',4);
InsertAdminPhrase(0,'reports_delete','Delete',4);
InsertAdminPhrase(0,'reports_description_missing','Please enter a description.',4);
InsertAdminPhrase(0,'reports_edit_hint','A Report is recommended to be shorter than 250 characters.',4);
InsertAdminPhrase(0,'reports_edit_reason','Edit Reason',4);
InsertAdminPhrase(0,'reports_edit_report','Edit Report',4);
InsertAdminPhrase(0,'reports_filter','Filter',4);
InsertAdminPhrase(0,'reports_filter_title','Filter Reports',4);
InsertAdminPhrase(0,'reports_groups','Allowed Usergroups?',4);
InsertAdminPhrase(0,'reports_ip','IP',4);
InsertAdminPhrase(0,'reports_ip_asc','IP Address Asc',4);
InsertAdminPhrase(0,'reports_ip_descending','IP Address Desc',4);
InsertAdminPhrase(0,'reports_ipaddress','IP Address',4);
InsertAdminPhrase(0,'reports_latest_reports','Latest reports',4);
InsertAdminPhrase(0,'reports_limit','Limit',4);
InsertAdminPhrase(0,'reports_no_reasons_found','No Reasons Found',4);
InsertAdminPhrase(0,'reports_no_reports_found','No Reports Found',4);
InsertAdminPhrase(0,'reports_no_filter','No Filter',4);
InsertAdminPhrase(0,'reports_no_plugin_configured','No Plugin(s) configured',4);
InsertAdminPhrase(0,'reports_none','(None)',4);
InsertAdminPhrase(0,'reports_number_of_reports','Number of Reports',4);
InsertAdminPhrase(0,'reports_open','Open',4);
InsertAdminPhrase(0,'reports_plugin','Plugin',4);
InsertAdminPhrase(0,'reports_plugin_asc','Plugin A-Z',4);
InsertAdminPhrase(0,'reports_plugin_descending','Plugin Z-A',4);
InsertAdminPhrase(0,'reports_plugin_item','Plugin Item',4);
InsertAdminPhrase(0,'reports_plugin_name','Plugin Name',4);
InsertAdminPhrase(0,'reports_plugin_not_found','Plugin not found or not installed!',4);
InsertAdminPhrase(0,'reports_reason_plugin_hint','Limit appearance of this reason to a specific list of plugins OR leave it empty to be available to all plugins.',4);
InsertAdminPhrase(0,'reports_reasons_updated','Report Reasons Updated',4);
InsertAdminPhrase(0,'reports_reason','Reason',4);
InsertAdminPhrase(0,'reports_reason_title','Title',4);
InsertAdminPhrase(0,'reports_reason_description','Description',4);
InsertAdminPhrase(0,'reports_reason_created','Reason created.',4);
InsertAdminPhrase(0,'reports_reason_deleted','Reason deleted.',4);
InsertAdminPhrase(0,'reports_reason_not_deletable','This reason cannot be deleted!',4);
InsertAdminPhrase(0,'reports_reason_not_found','Reason not found!',4);
InsertAdminPhrase(0,'reports_reason_deleted','Reason Deleted',4);
InsertAdminPhrase(0,'reports_reason_updated','Reason Updated',4);
InsertAdminPhrase(0,'reports_reasons','Reports Reasons',4);
InsertAdminPhrase(0,'reports_reasons_asc','Reasons A-Z',4);
InsertAdminPhrase(0,'reports_reasons_descending','Reasons Z-A',4);
InsertAdminPhrase(0,'reports_reasons_hint','Change description of reasons and specify which plugins may use which reasons.',4);
InsertAdminPhrase(0,'reports_reasons_settings','Reasons Settings',4);
InsertAdminPhrase(0,'reports_reasons_title_hint','Note: to change the frontpage title for this reason, <a href="languages.php?action=edit_website_language&pluginid=1">click here</a> and search for "reason_" entries.',4);
InsertAdminPhrase(0,'reports_report','Report',4);
InsertAdminPhrase(0,'reports_report_deleted','Report deleted.',4);
InsertAdminPhrase(0,'reports_report_not_found','Report not found!',4);
InsertAdminPhrase(0,'reports_report_settings','Reports Settings',4);
InsertAdminPhrase(0,'reports_report_updated','Report updated.',4);
InsertAdminPhrase(0,'reports_reports_deleted','Reports Deleted',4);
InsertAdminPhrase(0,'reports_settings_updated','Report Settings Updated.',4);
InsertAdminPhrase(0,'reports_sort_by','Sort by',4);
InsertAdminPhrase(0,'reports_status','Status',4);
InsertAdminPhrase(0,'reports_uname_asc','Username Asc',4);
InsertAdminPhrase(0,'reports_uname_descending','Username Desc',4);
InsertAdminPhrase(0,'reports_update_reason','Update Reason',4);
InsertAdminPhrase(0,'reports_update_report','Update Report',4);
InsertAdminPhrase(0,'reports_username','Username',4);
InsertAdminPhrase(0,'reports_user_message','User Message:',4);

InsertAdminPhrase(0,'comments_none','(None)',4);
InsertAdminPhrase(0,'tags_none','(None)',4);
InsertAdminPhrase(0,'email_empty_password', 'Only email users that have no password set (migrated users)?', 5);

$DB->query('CREATE TABLE IF NOT EXISTS '.PRGM_TABLE_PREFIX."report_reasons (
  reasonid      SMALLINT(4)  UNSIGNED NOT NULL AUTO_INCREMENT,
  title         VARCHAR(254) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  description   MEDIUMTEXT   COLLATE utf8_unicode_ci NULL,
  datecreated   INT(11)      UNSIGNED NOT NULL DEFAULT 0,
  dateupdated   INT(11)      UNSIGNED NOT NULL DEFAULT 0,
  created_by    VARCHAR(254) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (reasonid)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

// Add core reasons
$reasons = array(1  => array('t' => 'Spam',      'd' => 'The entry is suspicious of serving as unrelated/undesired marketing, links to (spam-)advertised sites or is filled with keywords.'),
                 2  => array('t' => 'Warez',     'd' => 'The entry contains links to websites promoting illegal products or services.'),
                 3  => array('t' => 'Malware',   'd' => 'The entry contains links to websites reported as infected/malicious/virused.'),
                 4  => array('t' => 'Rules',     'd' => 'The entry violates rules for proper conduct on this website.'),
                 5  => array('t' => 'Bad Behavior', 'd' => 'The entry represents abusive/ill-intent behavior.'),
                 6  => array('t' => 'Off Topic', 'd' => 'The entry has no relation at all to the main topic.'),
                 99 => array('t' => 'Other',     'd' => 'The entry does not fit any other available reason.'));
foreach($reasons as $reasonid => $entry)
{
  InsertPhrase(1, 'reason_'.strtolower($entry['t']), str_replace(' ','_',$entry['t']));
  if(!$DB->query_first('SELECT reasonid FROM '.PRGM_TABLE_PREFIX.'report_reasons WHERE reasonid = %d',$reasonid))
  {
    $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX."report_reasons (`reasonid`, `title`, `description`, `datecreated`) VALUES".
               "(%d, '%s', '%s', ".TIME_NOW.")",
               $reasonid, $DB->escape_string($entry['t']), $DB->escape_string($entry['d']));
  }
}

$DB->query('CREATE TABLE IF NOT EXISTS '.PRGM_TABLE_PREFIX."report_reasons_plugins (
  reasonid      SMALLINT(4) UNSIGNED NOT NULL AUTO_INCREMENT,
  pluginid      INT(11)     UNSIGNED NOT NULL DEFAULT 0,
  is_active     TINYINT(1)  UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (reasonid, pluginid)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query('CREATE TABLE IF NOT EXISTS '.PRGM_TABLE_PREFIX."users_reports (
  reportid      INT(11)      UNSIGNED NOT NULL AUTO_INCREMENT,
  userid        INT(11)      UNSIGNED NOT NULL DEFAULT 0,
  username      VARCHAR(250) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  pluginid      INT(11)      UNSIGNED NOT NULL DEFAULT 0,
  objectid1     INT(11)      UNSIGNED NOT NULL DEFAULT 0,
  objectid2     INT(11)      UNSIGNED NOT NULL DEFAULT 0,
  reasonid      SMALLINT(4)  UNSIGNED NOT NULL DEFAULT 0,
  user_msg      MEDIUMTEXT   COLLATE utf8_unicode_ci NULL,
  ipaddress     VARCHAR(40)  COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  datereported  INT(11)      UNSIGNED NOT NULL DEFAULT 0,
  is_closed     TINYINT(1)   UNSIGNED NOT NULL DEFAULT 0,
  reportcount   INT(11)      UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (reportid),
  INDEX (pluginid,objectid1,objectid2),
  INDEX (userid)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

InsertAdminPhrase(0, 'err_php_uploads_disabled', 'Error: PHP uploads are currently <strong>disabled</strong>.');
InsertAdminPhrase(0, 'max_php_filesize_is', 'The maximum allowed size per uploaded file is <strong>[max_size]</strong>.');
InsertAdminPhrase(0, 'max_uploadsize_is', 'The total allowed data size is <strong>[max_size]</strong>.');
InsertAdminPhrase(0, 'server_restrictions', 'Server-side Restrictions:');

InsertAdminPhrase(0, 'err_invalid_setting_format','Invalid setting format!',2);
InsertAdminPhrase(0, 'plugins_used','Used',2);

InsertAdminPhrase(0, 'comments_date_descending', 'Date Desc', 4);
DeleteAdminPhrase(0, 'comments_uname_desc', 4);
InsertAdminPhrase(0, 'comments_uname_descending', 'Username Desc', 4);

if($forumid = GetPluginID('Forum'))
{
  InsertPluginSetting($forumid, 'forum_topic_settings', 'Post Reporting Permissions',
    'Which usergroup(s) should be allowed to report posts?<br />'.
    'Note: reporting must be explicitely enabled in the <a href="./reports.php?action=display_report_settings">Data|View Report Settings</a> page.<br /><br />'.
    'De-/select single or multiple entries by CTRL/Shift+click.', 'usergroups', '1,2', 110, true);
}
// **************** ADMIN TEMPLATES ******************
InsertAdminPhrase(0, 'settings_templates', 'Templates', 0);
InsertMainSetting('templates_from_db', 'settings_templates', 'Templates From Database',
  'Load all templates from database (SD350+) when set to Yes, otherwise only use filesystem (tmpl folder).',
  'yesno', 1, 10);
InsertAdminPhrase(0, 'menu_templates', 'Templates', 0);
InsertAdminPhrase(0, 'menu_skins_view_templates', 'View Templates', 0);
InsertAdminPhrase(0, 'menu_skins_view_templates_by_plugin', 'View Templates by Plugin', 0);
InsertAdminPhrase(0, 'menu_skins_view_template_settings', 'View Template Settings', 0);

// Recreate "revisions" and "templates" tables if version <= 3.4.3 (or empty)
$tbl = PRGM_TABLE_PREFIX.'templates';
$tbl_rev = PRGM_TABLE_PREFIX.'revisions';
if(isset($GLOBALS['mainsettings_sdversion']) &&
   version_compare($GLOBALS['mainsettings_sdversion'],'3.5.0','<'))
{
  $DB->query('DROP TABLE IF EXISTS '.$tbl);
  $DB->query('DROP TABLE IF EXISTS '.$tbl_rev);
}
else
{
  if($tmp = $DB->table_exists($tbl))
  {
    if($tmp=$DB->query_first('SELECT COUNT(*) TPL_COUNT FROM '.$tbl))
    {
      if(empty($tmp['TPL_COUNT']))
      {
        $DB->query('DROP TABLE '.$tbl);
        $DB->query('DROP TABLE '.$tbl_rev);
      }
    }
  }
  unset($tmp);
}

$DB->query('CREATE TABLE IF NOT EXISTS '.$tbl_rev." (
`revision_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`pluginid` int(11) unsigned NOT NULL DEFAULT 0,
`objectid` int(11) unsigned NOT NULL DEFAULT 0,
`userid` int(11) unsigned NOT NULL DEFAULT 0,
`datecreated` int(11) unsigned NOT NULL DEFAULT 0,
`content` TEXT COLLATE utf8_unicode_ci NOT NULL,
`description` TEXT COLLATE utf8_unicode_ci NULL,
PRIMARY KEY (`revision_id`),
KEY `userid` (`userid`),
KEY `pluginid` (`pluginid`),
KEY `objectid` (`objectid`),
KEY `datecreated` (`datecreated`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
$DB->add_tablecolumn($tbl_rev, 'pluginid', 'INT(11)', 'UNSIGNED NOT NULL DEFAULT 0 AFTER revision_id');
$DB->add_tablecolumn($tbl_rev, 'objectid', 'INT(11)', 'UNSIGNED NOT NULL DEFAULT 0 AFTER pluginid');
$DB->add_tableindex($tbl_rev,'pluginid');
$DB->add_tableindex($tbl_rev,'objectid');

$DB->query('CREATE TABLE IF NOT EXISTS '.$tbl." (
`template_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`tpl_name` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
`displayname` varchar(250) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
`description` TEXT COLLATE utf8_unicode_ci NULL,
`is_active` int(1) unsigned NOT NULL DEFAULT 1,
`pluginid` int(11) unsigned NOT NULL DEFAULT 0,
`revision_id` int(11) unsigned NOT NULL DEFAULT 0,
`userid` int(11) unsigned NOT NULL DEFAULT 0,
`datecreated` int(11) unsigned NOT NULL DEFAULT 0,
`dateupdated` int(11) unsigned NOT NULL DEFAULT 0,
`system_only` int(1) unsigned NOT NULL DEFAULT 0,
`tpl_type` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'general',
PRIMARY KEY (`template_id`),
KEY `tpl_name` (`tpl_name`),
KEY `displayname` (`displayname`),
KEY `pluginid` (`pluginid`),
KEY `revision_id` (`revision_id`),
KEY `datecreated` (`datecreated`),
KEY `dateupdated` (`dateupdated`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
$DB->add_tablecolumn($tbl, 'displayname', 'VARCHAR(250) collate utf8_unicode_ci', "NOT NULL DEFAULT '' AFTER `tpl_name`");

//Cleanup vars
unset($tbl,$tbl_rev);

$DB->ignore_error = false;
