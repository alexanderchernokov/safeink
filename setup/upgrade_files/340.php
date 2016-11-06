<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'vvc', 'DATE', 'INT(10) NOT NULL DEFAULT 0');
$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'usergroups', 'require_vvc', "TINYINT(1) NOT NULL DEFAULT 0");

InsertPluginSetting(12, 'user_registration_settings', 'Activation Link Expiration', 'After how many days will activation links expire and no longer be accepted (default: 14 days)?', 'text', 14, 1, true);

InsertPhrase(1, 'approved', 'Approved');
InsertPhrase(1, 'unapproved', 'Unapproved');
if($FoumPluginID = GetPluginID('Forum'))
{
  InsertPhrase($FoumPluginID, 'topic_goto_top', 'Top');
}
InsertPhrase(2, 'pdf_print_failed', 'We apologise, but the requested article could not be converted to PDF at this time.');

InsertAdminPhrase(0, 'status_offline', 'Offline', 2);
InsertAdminPhrase(0, 'status_online', 'Online', 2);
InsertAdminPhrase(0, 'message_plugin_upgraded', 'Plugin upgrade successful!', 2);
InsertAdminPhrase(0, 'message_plugin_upgrade_error', '<strong>An error occured when trying to upgrade the plugin!</strong><br /><br />Failed to open:', 2);

InsertAdminPhrase(0, 'uploader_msg_success', 'File successfully uploaded!');
InsertAdminPhrase(0, 'uploader_msg_fail', 'Upload Failed!');

// Media new phrases:
InsertAdminPhrase(0, 'media_folder_readonly_note', 'Note: Folders with an asterisk <span style="color: #ff0000; font-size: 16px;">*</span> are <strong>NOT</strong> writable!',3,true);
InsertAdminPhrase(0, 'media_image_selection_note', 'Note: To crop this image, click-and-drag on the image to select a region to mark new image dimensions.<br />Then you MUST select <strong>Crop to image</strong> under the <strong>Thumbnailing Mode</strong> option listed below.',3,true);
InsertAdminPhrase(0, 'media_processing_mode', 'Image Processing Mode',3);
InsertAdminPhrase(0, 'media_processing_mode_descr', 'Would you like to create a new image (default) or create a new image with the below entered filename?',3);
InsertAdminPhrase(0, 'media_processing_mode1', 'Create new image',3);
InsertAdminPhrase(0, 'media_processing_mode2', 'Replace original image',3);
InsertAdminPhrase(0, 'media_image_err_folder', 'You selected a non-writable folder in which the image cannot be changed/created!\r\nPlease select a different folder or change permissions on the server by FTP.',3);
InsertAdminPhrase(0, 'media_image_err_file', 'ERROR: Image file is invalid, not readable or does not exist!',3,true);
InsertAdminPhrase(0, 'media_image_err_sizes', 'Invalid width or height values specified!',3);
InsertAdminPhrase(0, 'media_image_sizes', 'Image Sizes',3);
InsertAdminPhrase(0, 'media_image_sizes_descr', 'Choose a predefined dimension for resizing. Square-Off image option required for squared dimensions, otherwise aspect ratio is maintained. Note: Image cannot be resized larger then original image dimensions.',3,true);
InsertAdminPhrase(0, 'media_instant_upload_hint', 'For easy, instant uploads, please select the target folder,<br />
          <strong>select</strong> one or multiple files and just click the <strong>upload</strong> button:<br />
          Available media folders:<br />',3,true);
InsertAdminPhrase(0, 'media_upload_image', 'Upload File',3,true);
InsertAdminPhrase(0, 'media_uploder_select_hint', 'Select one or more media files for upload:',3);
InsertAdminPhrase(0, 'media_thumb_mode', 'Thumbnailing Mode (Resize or Crop)',3,true);
InsertAdminPhrase(0, 'media_thumb_mode_descr', 'How should the image or selected region be processed?',3);
InsertAdminPhrase(0, 'media_thumb_mode_resize', 'Resize image',3,true);
InsertAdminPhrase(0, 'media_thumb_mode_crop', 'Crop image to selection',3);
InsertAdminPhrase(0, 'media_thumb_mode_watermark_txt', 'Watermark Text',3);
InsertAdminPhrase(0, 'media_thumb_mode_watermark_img', 'Watermark Image',3);
InsertAdminPhrase(0, 'media_watermark', 'Watermark',3,true);
InsertAdminPhrase(0, 'media_watermark_descr', 'Apply watermark to the resulting image?',3,true);
InsertAdminPhrase(0, 'media_watermark_angle', 'Text Angle (in degrees, counter-clockwise)',3,true);
InsertAdminPhrase(0, 'media_watermark_angle_hint', 'The rotation-point is the bottom-left corner! Value 0 = normal, 90 = vertical etc.',3,true);
InsertAdminPhrase(0, 'media_watermark_image', 'Watermark Image',3,true);
InsertAdminPhrase(0, 'media_watermark_image_hint', 'Select as watermark a listed image to be overlayed onto image as a watermark:<br />Only supported if original image is a <strong>png</strong> or <strong>jpg</strong> type! ',3,true);
InsertAdminPhrase(0, 'media_watermark_text', 'Watermark Text',3,true);
InsertAdminPhrase(0, 'media_watermark_text_hint', 'Enter the text to be printed on top of the image (otherwise leave empty):',3,true);
InsertAdminPhrase(0, 'media_watermark_font', 'Font',3,true);
InsertAdminPhrase(0, 'media_watermark_font_hint', 'Note: Font files (TTF) reside in <strong>/includes/fonts</strong>, which are also used for VVC Images.',3,true);
InsertAdminPhrase(0, 'media_watermark_internal_font', 'Internal Font',3,true);
InsertAdminPhrase(0, 'media_watermark_text_size', 'Font Size',3,true);
InsertAdminPhrase(0, 'media_watermark_text_size_hint', 'Text Size (built-in font: 1-5, &gt;5 is only for TTF fonts!):',3,true);
InsertAdminPhrase(0, 'media_watermark_margin_left', 'Margin Left (in pixels):',3,true);
InsertAdminPhrase(0, 'media_watermark_margin_top', 'Margin Top (in pixels):',3,true);
InsertAdminPhrase(0, 'media_watermark_text_color', 'Text Color (6 digits, e.g. "000000" for black):',3,true);
InsertAdminPhrase(0, 'media_square_off_image', 'Square-Off image?',3,true);
InsertAdminPhrase(0, 'media_square_off_image_hint', 'Resize the image to have the same height and width within the above specified
        max. limits, resulting in left/right or top/bottom borders depending on whether
        the width is greater than the height or vice versa.',3,true);
InsertAdminPhrase(0, 'media_cancel', 'Cancel',3);
InsertAdminPhrase(0, 'media_clear_filter', 'Clear Filter',3);
InsertAdminPhrase(0, 'media_displaymode', 'Displaymode',3);
InsertAdminPhrase(0, 'media_filter', 'Filter',3,true);
InsertAdminPhrase(0, 'media_folder', 'Folder',3,true);
InsertAdminPhrase(0, 'media_pagesize_all', 'All',3);
InsertAdminPhrase(0, 'media_refresh', 'Refresh',3);
InsertAdminPhrase(0, 'media_sort_name', 'File Name',3);
InsertAdminPhrase(0, 'media_sort_date', 'File Date',3);
InsertAdminPhrase(0, 'media_sort_asc', 'Sort: Ascending',3);
InsertAdminPhrase(0, 'media_sort_descending', 'Sort: Descending',3);
InsertAdminPhrase(0, 'media_confirm_deletion', 'Do you really want to DELETE this image now?',3);
InsertAdminPhrase(0, 'media_click_image_tip', 'Click to edit image',3);
InsertAdminPhrase(0, 'media_click_media_tip', 'Click to edit media file',3);
InsertAdminPhrase(0, 'media_thumb_preview', 'Preview:',3);
DeleteAdminPhrase(0, 'media_click_thumb_for_full_image', 3);
DeleteAdminPhrase(0, 'media_click_tip', 3);

// Users new phrases:
InsertAdminPhrase(0, 'users_activation_expiration', 'Activation links are configured to expire after (days):',5);
InsertAdminPhrase(0, 'users_activation_expiration_hint', 'Note: the expiration time can be configured in the <a href="view_plugin.php?pluginid=12"><strong>User Registration</strong></a> settings.',5);
InsertAdminPhrase(0, 'users_apply_filter', 'Apply Filter',5);
InsertAdminPhrase(0, 'users_change_userstatus_title', 'Change User Status', 5);
InsertAdminPhrase(0, 'users_change_userstatus_descr', 'Change status to:', 5);
InsertAdminPhrase(0, 'users_change_usergroup_title', 'Change Usergroup', 5);
InsertAdminPhrase(0, 'users_change_usergroup_descr', 'Change usergroup to:', 5);
InsertAdminPhrase(0, 'users_clear_filter', 'Clear Filter',5);
InsertAdminPhrase(0, 'users_confirm_activationlink', 'Would you like to send the user an email with an activation link now?\n\nThis will also deactivate the user\'s account if currently active!',5,true);
InsertAdminPhrase(0, 'users_copy_usergroup', 'Create Copy', 5);
InsertAdminPhrase(0, 'users_delete_move', 'Move / Delete', 5, true);
InsertAdminPhrase(0, 'users_delete', 'Delete', 5, true);
InsertAdminPhrase(0, 'users_email_bcc_address', 'BCC - Send blind copy of email to:', 5, true);
InsertAdminPhrase(0, 'users_email_emails_not_sent', 'email(s) not sent, probably invalid.', 5, true);
InsertAdminPhrase(0, 'users_email_emails_pause', 'Pause Emailing', 5, true);
InsertAdminPhrase(0, 'users_email_emails_resume', 'Resume Emailing', 5, true);
InsertAdminPhrase(0, 'users_filter', 'Filter',5);
InsertAdminPhrase(0, 'users_filter_title', 'Filter Users',5);
InsertAdminPhrase(0, 'users_group_banned', 'Group Banned',5);
InsertAdminPhrase(0, 'users_ignore_duplicate_email', 'Ignore duplicate email?',5);
InsertAdminPhrase(0, 'users_last_activity', 'Last Activity',5);
InsertAdminPhrase(0, 'users_limit', 'Limit', 5,true);
InsertAdminPhrase(0, 'users_loading', 'Loading...', 5);
InsertAdminPhrase(0, 'users_move_users', 'Move Users', 5);
InsertAdminPhrase(0, 'users_open_email', 'Open Email Editor',5);
InsertAdminPhrase(0, 'users_open_external_email', 'Open External Email',5);
InsertAdminPhrase(0, 'users_options', 'Options',5);
InsertAdminPhrase(0, 'users_order', 'Order',5);
InsertAdminPhrase(0, 'users_send_password_reset_email', 'Send Password Reset Email',5);
InsertAdminPhrase(0, 'users_send_password_reset_email_hint', 'Note: password will be changed after email was sent',5);
InsertAdminPhrase(0, 'users_sort_by', 'Sort by',5);
InsertAdminPhrase(0, 'users_sort_asc', 'Ascending',5);
InsertAdminPhrase(0, 'users_users_moved', 'Users moved!', 5);

InsertAdminPhrase(0, 'skins_editor_hotkeys', 'Editor Hotkeys', 6);

// Comments new options:
InsertMainSetting('comments_require_approval_groups', 'comment_options', 'Usergroups requiring approval', 'Which usergroups should always require admin approval? This works in combination with the other comment settings.<br />Select none or multiple entries by Ctrl/Shift+Click.', 'usergroups', '', 20);
InsertMainSetting('comments_email_trigger', 'comment_options', 'Email Trigger', 'Select the desired option when an email for a new comment should be sent:',
                  "select:\r\noff|Off\r\napproval|Approval required\r\nall|All", 'off', 30);
InsertMainSetting('comments_email_notification', 'comment_options', 'Email Notification', 'Send email notifications for comments to this address (leave empty to use technical email address):', 'text', '', 32);
InsertMainSetting('comments_email_subject', 'comment_options', 'Email Subject', 'Default email subject for notification:', 'text',
                  'New Comment Notification', 34, true);
InsertMainSetting('comments_email_no_admin', 'comment_options', 'No Admin Comment Email',
                  'Do not send emails for comments posted by administrators (default: Yes)?', 'yesno', '1', 35);
InsertMainSetting('comments_email_user_as_sender', 'comment_options', 'User as Email Sender',
                  'Use the user\'s name and email address as email sender (default: No)?<br />
                  Leave empty to use technical email address or if email server rejects non-domain emails.', 'yesno', '0', 36);
InsertMainSetting('comments_email_body', 'comment_options', 'Email Body',
                  'Edit here the default <strong>email body</strong> text for notification, which may contain the following placeholders:<br /><br />
                  <strong>[date]</strong> = date of comment, <strong>[username]</strong> = user name, <strong>[pagename]</strong> = name of the page where comment was posted<br />
                  <strong>[pagelink]</strong> = clickable link to the page, <strong>[pluginname]</strong> = name of the plugin for which the comment was posted<br />
                  <strong>[commentstatus]</strong> = status of the comment (approved or unapproved)', 'wysiwyg',
                  '<h1>New Comment Notification</h1><br />'.
                  'On [date] a new comment was posted on your website and needs your attention.<br />'.
                  'The user <strong>[username]</strong> posted on page <strong>[pagename]</strong><br />(Link: [pagelink]),<br />'.
                  'for an entry in the plugin <strong>[pluginname]</strong>, the below comment.<br />'.
                  'The comment currently has the status: [commentstatus].<br /><br />'.
                  'This is the full comment text:<br />'.
                  '[comment]', 40, true);

InsertAdminPhrase(0, 'skins_options', 'Skins Options');
InsertMainSetting('skins_enable_highlighting', 'skins_options', 'Enable highlighting', 'Enable the editor for syntax highlighting (default: No)?<br />The highlighting editor is suited for modern browsers only.', 'yesno', '0', 10);
InsertMainSetting('wysiwyg_starts_off', 'settings_general_settings', 'WYSIWYG Starts Disabled',
  'If WYSIWYG is enabled, this option allows to initially start without the full HTML editor being active (set this to Yes).
  The usually available <strong>toggle</strong> button can then be used to activate it.', 'yesno', '0', 6);

DeleteAdminPhrase(0, 'skins_head_include', 6);

$DB->query("ALTER TABLE ".PRGM_TABLE_PREFIX."plugins CHANGE COLUMN `authorname` `authorname` VARCHAR(254) collate utf8_unicode_ci NOT NULL DEFAULT ''");
$DB->query("ALTER TABLE ".PRGM_TABLE_PREFIX."plugins CHANGE COLUMN `displayname` `displayname` VARCHAR(254) collate utf8_unicode_ci NOT NULL DEFAULT ''");
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."plugins SET authorname = 'subdreamer_web' WHERE pluginid < 20");
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."plugins SET authorlink = 'http://www.subdreamer.com' WHERE authorlink = '0' OR authorlink = '1' AND pluginid < 20");
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."mainsettings SET displayorder = 8 WHERE groupname = 'settings_general_settings' and varname = 'enable_rss'");
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."mainsettings SET displayorder = 9 WHERE groupname = 'settings_general_settings' and varname = 'enable_rss_forum'");
$DB->query("UPDATE ".PRGM_TABLE_PREFIX."mainsettings SET input = 'password' WHERE groupname = 'settings_email_settings' and varname = 'email_smtp_pwd'");

$DB->query('CREATE TABLE IF NOT EXISTS '.PRGM_TABLE_PREFIX."tags (
tagid         INT(11)   UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
pluginid      INT(11)   UNSIGNED NOT NULL DEFAULT 0,
objectid      INT(11)   UNSIGNED NOT NULL DEFAULT 0,
tagtype       INT(4)       UNSIGNED NOT NULL DEFAULT 0,
tag           VARCHAR(254) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
datecreated   INT(10)      UNSIGNED NOT NULL DEFAULT 0,
INDEX (pluginid),
INDEX (tag)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
