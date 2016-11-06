<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

// User reporting support (e.g. Forum, Article-Comments)
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'users_data', 'user_titleid', 'INT(11)', 'UNSIGNED NOT NULL DEFAULT 0');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'users_reports', 'categoryid', 'INT(11)', 'UNSIGNED NOT NULL DEFAULT 0');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'users_reports', 'reported_userid', 'INT(11)', 'UNSIGNED NOT NULL DEFAULT 0');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'users_reports', 'reported_username', 'VARCHAR(250) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT '' AFTER `reported_userid`");
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'users_reports', 'usersystemid', 'INT(11)', 'UNSIGNED NOT NULL DEFAULT 1');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'plugins', 'reporting', 'INT(1)', 'UNSIGNED NOT NULL DEFAULT 0');
$DB->query('UPDATE '.PRGM_TABLE_PREFIX.'plugins SET reporting = 0 WHERE reporting IS NULL');

// Usergroup: option to hide avatar from Guests?
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups_config', 'hide_avatars_guests', 'TINYINT(1) UNSIGNED NOT NULL DEFAULT 0');
InsertAdminPhrase(0,'hide_avatars_guests', 'Hide avatar images from Guests (default: No)?', 5);

// Fix error with inserting new rows in some cases (NULL)
$DB->query('ALTER TABLE `'.PRGM_TABLE_PREFIX.'users_data` CHANGE COLUMN `user_text` `user_text` mediumtext collate utf8_unicode_ci NULL');

DeletePhrase(1, 'plugin_not_available');
DeleteAdminPhrase(0, 'plugin_not_available', 0);
InsertPhrase(1, 'err_invalid_operation', 'Invalid operation!');
InsertPhrase(1, 'err_invalid_report', 'Sorry, the report could not be submitted (invalid or no reason specified)!');
InsertPhrase(1, 'err_report_unconfirmed', 'Sorry, but you missed to confirm the report!');
InsertPhrase(1, 'err_member_no_download', 'You have no permissions to download attachments.');
InsertPhrase(1, 'err_plugin_not_available', 'Error: plugin not available');
InsertPhrase(1, 'confirm_report_comment', 'Yes, I confirm to report the comment');
InsertPhrase(1, 'comments_report', 'Report Comment');
InsertPhrase(1, 'comments_report_user_msg', 'Please leave us a message for your report:');
InsertPhrase(1, 'comments_comment_reported', 'Thank you, the comment was reported and will be reviewed by staff members.');
InsertPhrase(1, 'comments_comment_not_reported', 'The comment was not reported.');
InsertPhrase(1, 'comment_already_reported', 'Thank you, but the comment was already reported.');
InsertPhrase(1, 'comment_report_descr', 'Please confirm and specify a reason (if available) for reporting the selected comment.');
InsertPhrase(1, 'comment_send_report', 'Send Report');
InsertPhrase(1, 'common_attachment_deleted', 'Attachment deleted.');
InsertPhrase(1, 'common_attachment_delete_failed', 'Attachment not deleted!');
InsertPhrase(1, 'common_attachment_delete_prompt', 'Really delete attachment?');
InsertPhrase(1, 'common_attachment_upload_invalid', 'Invalid attachment upload!');
InsertPhrase(1, 'common_attachment_filesize_error', 'Attachment has invalid filesize!');
InsertPhrase(1, 'common_comment', 'Comment');
InsertPhrase(1, 'common_comments', 'Comments');
InsertPhrase(1, 'common_btn_cancel', 'Cancel');
InsertPhrase(1, 'common_btn_ok', 'OK');
InsertPhrase(1, 'common_btn_save', 'Save');
InsertPhrase(1, 'common_click_tip', 'Double-click to rename topic...');
InsertPhrase(1, 'common_directory_not_found', 'Directory not found');
InsertPhrase(1, 'common_directory_not_writable', 'Directory not writable');
InsertPhrase(1, 'common_invalid_file_type', 'Invalid file type.');
InsertPhrase(1, 'common_status', 'Status');
InsertPhrase(1, 'common_thumbnail_failed', 'Thumbnail creation failed');
InsertPhrase(1, 'common_view', 'View');
InsertPhrase(1, 'common_views', 'Views');
InsertPhrase(1, 'link_open_profile_hint', 'Open your profile page');
InsertPhrase(1, 'link_open_member_page', 'View member page');
InsertPhrase(1, 'social_link_delicious', 'Share [item] on Delicious');
InsertPhrase(1, 'social_link_digg',      'Digg [item]');
InsertPhrase(1, 'social_link_facebook',  'Share [item] on Facebook');
InsertPhrase(1, 'social_link_twitter',   'Share [item] on Twitter');
InsertPhrase(1, 'upload_err_invalid_extension', 'Unallowed file extention for attachment!');

InsertPhrase(11, 'msg_must_use_forum_profile','This page is not available, please use the forum profile page.');

InsertAdminPhrase(0, 'no_usergroup_option', '[No Usergroup]', 0);
InsertAdminPhrase(0, 'err_no_avatar_uploaded', 'No avatar uploaded.', 0);
InsertAdminPhrase(0, 'err_no_profile_image_uploaded', 'No profile image uploaded.', 0);
InsertAdminPhrase(0, 'common_page', 'Page', 0);
InsertAdminPhrase(0, 'common_username', 'Username', 0);
InsertAdminPhrase(0, 'common_allow_moderate', 'Allow Moderation', 0);
InsertAdminPhrase(0, 'users_allow_moderate', 'Allow Moderation', 5,true);

// User Reports
DeleteAdminPhrase(0, 'reports_reasons_title_hint', 4);
InsertAdminPhrase(0,'reports_close_report','Report is closed?',4);
InsertAdminPhrase(0,'reports_close_this_report','Is report closed?',4);
DeleteAdminPhrase(0,'reports_item',4);
InsertAdminPhrase(0,'reports_reported_item','Reported Item',4);
InsertAdminPhrase(0, 'add_moderator', 'Add Moderator', 4);
InsertAdminPhrase(0, 'add_moderator_hint', '<strong>Add existing user as a report moderator:</strong><br />'.
  'First type the first 2 letters of the user\'s name for quick-search to display and then select user from the popup list before clicking the button.<br />'.
  'The list of moderators is only used to maintain email recipients. Plugin administrators and moderators are eligible to report items, such as forum posts or article comments.', 4, true);
InsertAdminPhrase(0, 'add_report_moderator', 'Add Report Moderator', 4);
InsertAdminPhrase(0, 'add_reason_short', 'Add Reason', 4);
InsertAdminPhrase(0, 'add_report_reason', 'Add new Report Reason', 4);
InsertAdminPhrase(0, 'add_report_reason_hint', 'New report reasons can be added by specifying a title and description.', 4);
InsertAdminPhrase(0, 'err_mod_no_plugins', 'Moderator not created, no plugins were selected!', 4);
InsertAdminPhrase(0, 'err_no_reporting_plugins', 'There are no plugins available to the user for reporting!', 4);
InsertAdminPhrase(0, 'err_no_user', 'Sorry, no user was specified!', 4);
InsertAdminPhrase(0, 'insert_moderator', 'Insert Moderator', 4);
InsertAdminPhrase(0, 'moderator_email', 'Moderator Email', 4);
InsertAdminPhrase(0, 'moderator_receives_emails', 'Receives Email?', 4);
InsertAdminPhrase(0, 'moderators_updated', 'Moderators updated.', 4);
InsertAdminPhrase(0, 'receive_emails', 'Receive Emails?', 4);
InsertAdminPhrase(0, 'report_moderators', 'Report Moderators', 4);
InsertAdminPhrase(0, 'reports_reason_added', 'Reason has been added.', 4);
InsertAdminPhrase(0, 'report_moderator_remove', 'Remove?', 4);
InsertAdminPhrase(0, 'report_change_status', 'Approve/unmoderate item if report is <strong>deleted or closed</strong> here?', 4);
InsertAdminPhrase(0, 'reportable_plugin', 'Reportable Plugin', 4);
InsertAdminPhrase(0, 'reportable_plugins', 'Plugins supporting reporting', 4);
InsertAdminPhrase(0, 'reported_by', 'Reported by', 4);
InsertAdminPhrase(0, 'reported_content', 'Reported Content', 4);
InsertAdminPhrase(0, 'reported_user', 'Reported User', 4);
InsertAdminPhrase(0, 'reporting_plugins', 'Reporting Plugins', 4);
InsertAdminPhrase(0, 'reports_date','Reported on ', 4);
InsertAdminPhrase(0, 'reports_edit_report','Edit Report', 4);
InsertAdminPhrase(0, 'reports_delete_this','Delete this report?', 4);
InsertAdminPhrase(0, 'reports_title_missing','Please enter at least 3 characters for the title.',4,true);
InsertAdminPhrase(0, 'report_reason_delete','Delete this report reason?', 4);
InsertAdminPhrase(0, 'reports_delete_multi_approve', 'Deleting multiple reports: approve/unmoderate any reported items?<br />'.
  'Note: Deleting reports does not delete the reported item(s).', 4);
InsertAdminPhrase(0, 'reports_select_plugins', 'Please check any of the listed plugins for which the current reason should be available to:', 4);
InsertAdminPhrase(0, 'update_moderators', 'Update Moderators', 4);

// Admin Users Page
InsertAdminPhrase(0, 'users_disable_pub_image', 'Disable any profile image for user?', 5);
InsertAdminPhrase(0, 'users_remove_pub_image', 'Remove profile image?', 5);
InsertAdminPhrase(0, 'menu_users_titles', 'View User Titles', 0);
InsertAdminPhrase(0, 'users_titles', 'User Titles', 5);
InsertAdminPhrase(0, 'users_user_title', 'Title', 5);
InsertAdminPhrase(0, 'users_delete_user_title', 'Delete Title', 5);
InsertAdminPhrase(0, 'users_title_min_count', 'Min. Count', 5);
InsertAdminPhrase(0, 'users_update_titles', 'Update Titles', 5);
InsertAdminPhrase(0, 'users_no_titles', 'No Titles', 5);
InsertAdminPhrase(0, 'users_confirm_delete_titles', 'Delete this title(s):', 5);
InsertAdminPhrase(0, 'users_titles_updated', 'Title(s) updated.', 5);
InsertAdminPhrase(0, 'users_title_added', 'Title added.', 5);
InsertAdminPhrase(0, 'err_users_title_empty', 'Failed to add user title (empty or invalid)!', 5);
InsertAdminPhrase(0, 'users_add_title', 'Add Title', 5);
InsertAdminPhrase(0, 'users_add_title_hint',
  'Add a new user title together with the post count limit, upon which '.
  'the user will earn this title.', 5);
InsertAdminPhrase(0, 'usergroups_filter_hint', 'Search or filter usergroups by below entry boxes:', 5);
InsertAdminPhrase(0, 'users_email_unsubscribe_link', 'Include unsubscribe from email link at email bottom?', 5);
InsertAdminPhrase(0, 'email_prompt_back', 'Back', 5);

//Admin Skins Page:
InsertAdminPhrase(0, 'skins_xml_file_not_writable',
  'Folder of the skin or specified XML file (if exists) are not writable!<br />
  Please temporarily change folder permissions for selected skin to 0777 (if file exists, its permissions to 0666, too) by using an FTP client!', 6, true);
InsertAdminPhrase(0, 'skins_unnamed_skin', '(unnamed)', 6);
InsertAdminPhrase(0, 'skins_untitled', 'Untitled', 6);
InsertAdminPhrase(0, 'skins_new_opt_create_folder', 'Create skin folder if it does not exists already?', 6);
InsertAdminPhrase(0, 'skins_new_opt_copy_files', 'Copy all files/folders from source skin folder?', 6);
InsertAdminPhrase(0, 'skins_new_opt_copy_layouts', 'Copy all layouts from source skin?', 6);
InsertAdminPhrase(0, 'skins_new_opt_copy_css', 'Copy all CSS entries from source skin?', 6);
InsertAdminPhrase(0, 'skins_exp_opt_css', 'Export all CSS entries?', 6);
InsertAdminPhrase(0, 'skins_exp_opt_gzip', 'Export folder content as gzip file?', 6);
InsertAdminPhrase(0, 'skins_exp_opt_zip', 'Export folder content as zip file?<br />
  By default the archive will be created in the <strong>admin/backup</strong> folder.', 6);
InsertAdminPhrase(0, 'skins_import_parse_error', 'Import failed due to XML parsing error:', 6);
InsertAdminPhrase(0, 'skins_backup_found', 'Backup found', 6);
InsertAdminPhrase(0, 'skins_no_backup_found', 'No backup found', 6);
InsertAdminPhrase(0, 'skins_err_dest_folder_missing', 'Destination folder does not exist!', 6);
InsertAdminPhrase(0, 'skins_err_invalid_filefolder', 'Invalid file/folder reference!', 6);
InsertAdminPhrase(0, 'skins_err_ext_not_xml', 'Destination file must have <strong>.xml<strong> extention!', 6);
InsertAdminPhrase(0, 'skins_err_skin_not_found', 'ERROR: specified skin cannot be found or is invalid!', 6);
InsertAdminPhrase(0, 'skins_err_delete_active_skin', 'ERROR: the active skin cannot be deleted!', 6);
InsertAdminPhrase(0, 'skins_err_exp_failed', '<strong>FAILED to write specified skin file failed!</strong><br />'.
  'Note: please check that the folder has write permissions (0777) set within FTP.', 6);
InsertAdminPhrase(0, 'editor_content_changed', '* Changed', 6);

//Admin|Settings: Languages page
InsertAdminPhrase(0, 'website_language_updated', 'Website Language Updated', 7);
InsertAdminPhrase(0, 'search_button', 'Search', 7);
InsertAdminPhrase(0, 'search_phrases', 'Search All', 7);
InsertAdminPhrase(0, 'search_title', 'Search for phrases', 7);
InsertAdminPhrase(0, 'search_label', 'Search for phrase (at least 2 characters):<br />Note: use <strong>%</strong> and <strong>?</strong> as wildcard characters.', 7, true);
InsertAdminPhrase(0, 'search_original', 'Search in original phrases', 7);
InsertAdminPhrase(0, 'search_translated', 'Search in translated phrases', 7);
InsertAdminPhrase(0, 'lang_search_results', 'Search results for ', 7);
InsertAdminPhrase(0, 'lang_no_search_results', 'Sorry, no search results.', 7);
InsertAdminPhrase(0, 'err_check_search_option', 'Please check at least one search option.', 7);
InsertAdminPhrase(0, 'err_enter_search_phrase', 'Please enter at least 2 characters as search phrase.', 7);

$DB->query("CREATE TABLE IF NOT EXISTS {users_titles} (
`titleid`        int(11) unsigned NOT NULL auto_increment,
`title`          VARCHAR(254) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
`post_count`     int(11) unsigned NOT NULL DEFAULT 0,
PRIMARY KEY (`titleid`),
KEY (`post_count`,`title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$tmp = $DB->query_first('SELECT COUNT(*) titlecount FROM {users_titles}');
if(empty($tmp['titlecount']))
{
  if(!$DB->query_first("SELECT 1 FROM {users_titles} WHERE title = 'Junior Member'"))
  $DB->query("INSERT INTO {users_titles} (title,post_count) VALUES ('Junior Member',0)");
  if(!$DB->query_first("SELECT 1 FROM {users_titles} WHERE title = 'Member'"))
  $DB->query("INSERT INTO {users_titles} (title,post_count) VALUES ('Member',30)");
  if(!$DB->query_first("SELECT 1 FROM {users_titles} WHERE title = 'Senior Member'"))
  $DB->query("INSERT INTO {users_titles} (title,post_count) VALUES ('Senior Member',250)");
  if(!$DB->query_first("SELECT 1 FROM {users_titles} WHERE title = 'Professional'"))
  $DB->query("INSERT INTO {users_titles} (title,post_count) VALUES ('Professional',1000)");
  if(!$DB->query_first("SELECT 1 FROM {users_titles} WHERE title = 'Guru'"))
  $DB->query("INSERT INTO {users_titles} (title,post_count) VALUES ('Guru',2500)");

  if($usersystem['name']=='Subdreamer')
  {
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'users_data ud SET user_titleid = 1 WHERE '.
      '(SELECT user_post_count FROM '.PRGM_TABLE_PREFIX.'users u WHERE u.userid = ud.userid) < 30');
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'users_data ud SET user_titleid = 2 WHERE '.
      '(SELECT user_post_count FROM '.PRGM_TABLE_PREFIX.'users u WHERE u.userid = ud.userid) BETWEEN 31 AND 249');
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'users_data ud SET user_titleid = 3 WHERE '.
      '(SELECT user_post_count FROM '.PRGM_TABLE_PREFIX.'users u WHERE u.userid = ud.userid) BETWEEN 250 AND 999');
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'users_data ud SET user_titleid = 4 WHERE '.
      '(SELECT user_post_count FROM '.PRGM_TABLE_PREFIX.'users u WHERE u.userid = ud.userid) BETWEEN 1000 AND 2499');
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'users_data ud SET user_titleid = 5 WHERE '.
      '(SELECT user_post_count FROM '.PRGM_TABLE_PREFIX.'users u WHERE u.userid = ud.userid) > 2499');
  }
}

$DB->query("CREATE TABLE IF NOT EXISTS {users_bans} (
`usersystemid`   int(11) unsigned NOT NULL DEFAULT 0,
`userid`         int(11) unsigned NOT NULL DEFAULT 0,
`ban_start_date` int(11) unsigned NOT NULL DEFAULT 0,
`ban_end_date`   int(11) unsigned NOT NULL DEFAULT 0,
`ban_by_userid`  int(11) unsigned NOT NULL DEFAULT 0,
`is_auto_ban`    tinyint(1)  unsigned NOT NULL DEFAULT 1,
`ban_reason`     VARCHAR(254) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
PRIMARY KEY (`usersystemid`,`userid`),
KEY (`userid`,`ban_start_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE IF NOT EXISTS {report_moderators} (
`moderatorid`   int(11) unsigned NOT NULL auto_increment,
`userid`        int(11) unsigned NOT NULL DEFAULT 0,
`pluginid`      int(11) unsigned NOT NULL DEFAULT 0,
`subitemid`     int(11) unsigned NOT NULL DEFAULT 0,
`usersystemid`  int(11) unsigned NOT NULL DEFAULT 1,
`receiveemails` tinyint(1) unsigned NOT NULL DEFAULT 0,
`email`         VARCHAR(254) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
PRIMARY KEY (`moderatorid`),
KEY (`userid`),
KEY (`pluginid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

InsertMainSetting('comments_enable_reports', 'comment_options', 'Enable Comments Reporting',
  'Enable reporting of comments for selected user groups?<br />'.
  'This may be a valuable resource within a community to encourage others to help moderate e.g. articles comments.<br />'.
  'Note: site-, plugin-admins and plugin-moderators are always allowed '.
  'to report comments if reporting is globally enabled in the Reports Settings.',
  'usergroups', 0, 14);

//2 options from 342 that might be missing in some installs:
InsertMainSetting('enable_custom_plugin_paging', 'settings_general_settings', 'Enable Custom Plugin Paging',
  'Enable processing of Custom Plugins for the {pagebreak} macro and - if found - display pagination (default: No)?', 'yesno', '0', 110);
InsertMainSetting('enable_custom_plugin_ajax', 'settings_general_settings', 'Enable Custom Plugin Ajax',
  'If pagination for Custom Plugins is enabled, load contained pages in the background by Ajax (default: No)?<br />
  This should only be enabled if the current <strong>Character Set</strong> is set to <strong>utf-8</strong> or otherwise special characters and umlauts will be displayed incorrectly.', 'yesno', '0', 120);

$tmp = $DB->query_first('SELECT value FROM '.PRGM_TABLE_PREFIX.
                        "mainsettings WHERE varname = 'reporting_enabled'");
InsertMainSetting('reporting_enabled', 'user_reports_settings', 'Enable User Reports',
  'Enable users to report items (e.g. article comments or forum posts) to be reviewed by staff?<br />'.
  'If set to No, then reporting is disabled on the whole website.',
  'yesno', (empty($tmp['value'])?0:1), 10, true);

InsertMainSetting('reporting_allow_user_message', 'user_reports_settings', 'Allow User Message',
  'Which usergroup(s) should be allowed to enter a message with the report?<br />'.
  'The user may provide additional information or describe his personal reasons for reporting.<br /><br />'.
  'De-/select single or multiple entries by CTRL/Shift+click.',
  'usergroups', '1,2', 15);

InsertMainSetting('reports_moderate_items', 'user_reports_settings', 'Moderate Reported Items',
  'Select the number of reports to trigger an immediate moderation of any moderated item (default: 1).<br />'.
  'If set to Never, then items will not be moderated (hidden) at all.',
  "select:\r\n0|Never\r\n1|One report\r\n2|Two reports\r\n3|Three reports", 0, 20);

InsertMainSetting('reporting_email_sender', 'user_reports_settings', 'Reporting Email Sender',
  'Please enter here the email address used as the <strong>Sender</strong> for report notification emails (default: empty).<br />'.
  'If this is empty, the technical email address (see: Main Settings) will be used by default.',
  'text', '', 25);

InsertMainSetting('reporting_email_subject', 'user_reports_settings', 'Reporting Email Subject',
  'Please enter here the actual subject for the <strong>email subject</strong> to the moderator(s).<br />'.
  'For a list of placeholders within the subject please see next setting.',
  'text', '[plugin_name] content reported on [sitename]', 30);

InsertMainSetting('reporting_email_body', 'user_reports_settings', 'Reporting Email Body',
  'Please enter here the actual message text (as HTML) for the <strong>email body</strong> to the moderator(s),
which may contain the following placeholders:<br />
<strong>[moderator]</strong> = username of the email recipient<br />
<strong>[reported_date]</strong> = date of email<br />
<strong>[reported_user]</strong> = username of reported item<br />
<strong>[reported_userid]</strong> = userid or reported user<br />
<strong>[reported_link]</strong> = link to reported item<br />
<strong>[reported_title]</strong> = title of reported item (or its parent if comment)<br />
<strong>[report_content]</strong> = content of the reported item<br />
<strong>[report_reason_title]</strong> = title of the specified reporting reason<br />
<strong>[report_reason_description]</strong> = description of the specified reporting reason<br />
<strong>[reported_date]</strong> = date of report<br />
<strong>[page]</strong> = link to front page containing reported item<br />
<strong>[plugin_name]</strong> = name of the plugin containing the reported item<br />
<strong>[username]</strong> = Name of the IP address of the reporting user<br />
<strong>[ipaddress]</strong> = IP address of the reporting user<br />
<strong>[siteurl]</strong> = main link to the website<br />
<strong>[sitename]</strong> = name of the website<br />',
  'wysiwyg',
  '<h2><strong>User content report notification</strong></h2>
Dear [moderator],<br />
please be advised to review the below reported user content by <strong>[reported_user]</strong>.<br />
<br />
Reason specified: <strong>[report_reason_title]</strong><br />
[report_reason_description]<br />
<br />
Reported item: <strong>[reported_title]</strong><br />
Link to reported item: <strong><a href="[reported_link]">[reported_link]</a></strong><br />
Note: the item may no longer be visible due to automatic moderation.<br />
<br />
<u>Reported content:</u><br />
<pre>[report_content]</pre><br />
Reported by <strong>[username]</strong> on [reported_date].<br />
Report sent from IP address <strong>[ipaddress]</strong>.<br />
<br />
The reported item can be viewed in the administration area under Data|View User Reports.<br />
<br />
<strong>This is an automated email, do not reply to this email.</strong><br />', 40);

if($forumid = GetPluginID('Forum'))
{
  $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'plugins SET reporting = 1 WHERE pluginid = %d',$forumid);
  $DB->query('UPDATE '.PRGM_TABLE_PREFIX."phrases SET defaultphrase = 'Sticky'".
             " WHERE pluginid = %d AND varname = 'sticky'",$forumid);

  InsertAdminPhrase($forumid, 'subforums', 'Subforums');
  InsertAdminPhrase($forumid, 'likes_enabled', 'Likes enabled');
  InsertAdminPhrase($forumid, 'moderated_topics', 'Moderated Topics:');
  InsertAdminPhrase($forumid, 'moderated_posts', 'Moderated Posts:');
  InsertAdminPhrase($forumid, 'err_reset_invalid_parent', 'Forum had INVALID parent forum, reset!');

  InsertPhrase($forumid, 'post_already_reported', 'Thank you, but the post was already reported.');
  InsertPhrase($forumid, 'report_post', 'Report post');
  InsertPhrase($forumid, 'report_post_title', 'Report Post');
  InsertPhrase($forumid, 'report_post_descr', 'Please confirm and specify a reason (if available) for reporting the selected post.');
  InsertPhrase($forumid, 'report_send', 'Send Report');
  InsertPhrase($forumid, 'reported_post', 'Thank you, the post has now been reported.',true);
  InsertPhrase($forumid, 'confirm_report_post', 'Yes, I confirm to report the post');
  InsertPhrase($forumid, 'reply_options_moderate_post', 'Reply by moderated post');
  InsertPhrase($forumid, 'message_post_awaits_approval',  'Thank you. Your post awaits admin approval to become visible.');
  InsertPhrase($forumid, 'message_topic_awaits_approval', 'Thank you. Your topic awaits admin approval to become visible.');
  InsertPhrase($forumid, 'open_your_profile_page','Open your profile page');

  DeletePhrase($forumid, 'err_invalid_report');
  DeletePhrase($forumid, 'err_cannot_report_own_post');
}
