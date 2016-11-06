<?php
// +---------------------------------------------+
// | Download Manager plugin for Subdreamer      |
// +---------------------------------------------+
// | v2.2.0, August 2013                         |
// | Maintainer: 2007-2013 tobias                |
// | This version requires Subdreamer 3.5+!      |
// +---------------------------------------------+

if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return;

// ####################### DETERMINE CURRENT DIRECTORY ########################
$plugin_folder = sd_GetCurrentFolder(__FILE__);

// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,6))
{
  if(strlen($installtype))
  {
    echo 'Download Manager requires '.PRGM_NAME.' v3.6+ ('.$plugin_folder.').<br />';
  }
  return false;
}


// ############################################################################
// PLUGIN INFORMATION
// ############################################################################
// Do NOT set $uniqueid or $pluginid here!
$pluginname     = 'Download Manager';
$version        = '2.4.0';
$pluginpath     = $plugin_folder . '/dlm_downloads.php';
$settingspath   = $plugin_folder . '/dlm_settings.php';
$authorname     = 'subdreamer_web';
$authorlink     = 'http://www.subdreamer.com';
$pluginsettings = '31';


/* ######################### PRE-CHECK FOR CLONING ############################
   If no install type is set, then SD is scanning for new plugins.
   This is the right time to pre-check, if a plugin with the same name within
   a different folder has already been installed.
*/

// 1. Check if this plugin is already installed from within the current folder:
// Only *IF* it is installed, set the plugin id so that it is treated
// as installed and does not show up for installation again
if($inst_check_id = GetPluginIDbyFolder($plugin_folder))
{
  $pluginid = $inst_check_id;
}
else
// 2. Check if a plugin with the same name is already installed
if($inst_check_id = GetPluginID($pluginname))
{
  $pluginname .= ' ('.$plugin_folder.')';
}
$authorname .= "<br />Plugin folder: '<b>plugins/$plugin_folder</b>'";

if(empty($installtype))
{
  // Nothing else to do, so return
  return true;
}


// ############################# LOAD PLUGIN LIBRARY ##########################
$pluglib = ROOT_PATH.'plugins/'.$plugin_folder.'/pluglib.php';
if(!file_exists($pluglib)) echo 'Pluglib not found! '.$pluglib.'<br />';
require_once($pluglib);

// IMPORTANT: individual functions have to be "hidden" if already existing or
// otherwise PHP will abort the script.
if(!function_exists('dlm_DropTables')) // do not remove!
{
  function dlm_DropTables($pluginid=0)
  {
    global $DB;

    // Remove existing tables
    if(!empty($pluginid))
    {
      $DB->query('DROP TABLE IF EXISTS '.PRGM_TABLE_PREFIX.'p'.$pluginid.'_dlm_owners');
      $DB->query('DROP TABLE IF EXISTS '.PRGM_TABLE_PREFIX.'p'.$pluginid.'_files');
      $DB->query('DROP TABLE IF EXISTS '.PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections');
      $DB->query('DROP TABLE IF EXISTS '.PRGM_TABLE_PREFIX.'p'.$pluginid.'_file_sections');
      $DB->query('DROP TABLE IF EXISTS '.PRGM_TABLE_PREFIX.'p'.$pluginid.'_file_downloads');
      $DB->query('DROP TABLE IF EXISTS '.PRGM_TABLE_PREFIX.'p'.$pluginid.'_file_versions');

      $DB->query('DELETE FROM {adminphrases} WHERE pluginid = %d',$pluginid);
      $DB->query('DELETE FROM {phrases} WHERE pluginid = %d',$pluginid);
      $DB->query('DELETE FROM {pluginsettings} WHERE pluginid = %d',$pluginid);
      $DB->query('DELETE FROM {comments} WHERE pluginid = %d',$pluginid);
      $DB->query('DELETE FROM {ratings} WHERE pluginid = %d',$pluginid);
    }
  }
}

// ############################ UPGRADE 2.0.0 #################################

// ****************************************************************************
// v2.0.0 - SD3-only release and merger with previous DLM MOD
// ****************************************************************************
if(!function_exists('dlm_Upgrade200')) //do not remove!
{
function dlm_Upgrade200()
{
  global $DB, $pluginid;

  // --------------------------------------------------------------------------
  // Database updates
  // --------------------------------------------------------------------------
  $DB->query('DROP TABLE IF EXISTS '.PRGM_TABLE_PREFIX.'p'.$pluginid.'_access');
  $DB->query('DROP TABLE IF EXISTS '.PRGM_TABLE_PREFIX.'p'.$pluginid.'_ratings');

  ConvertPluginSettings($pluginid);

  // ----------------------- DB CHANGES FOR FILES TABLE -----------------------
  $tbl = PRGM_TABLE_PREFIX.'p'.$pluginid.'_files';
  $pre = 'ALTER TABLE '.$tbl.' ADD ';
  sd_addtablecolumn($tbl, 'standalone',          $pre."`standalone` TINYINT NOT NULL DEFAULT 0");
  // *** NEW IN 2010 ***
  sd_addtablecolumn($tbl, 'access_groupids',     $pre."`access_groupids` TEXT NOT NULL");
  sd_addtablecolumn($tbl, 'access_userids',      $pre."`access_userids` TEXT NOT NULL");
  // List of blank-separated tags for tag cloud display
  sd_addtablecolumn($tbl, 'tags',                $pre."`tags` TEXT NOT NULL");
  // if "is_embeddable_media" == 1: embed media file in files list (when viewing a section listing)?
  sd_addtablecolumn($tbl, 'embedded_in_list',    $pre."`embedded_in_list` TINYINT UNSIGNED NOT NULL DEFAULT 0");
  // if "is_embeddable_media" == 1: embed media file on "More Info" page or just display link to external page?
  sd_addtablecolumn($tbl, 'embedded_in_details', $pre."`embedded_in_details` TINYINT UNSIGNED NOT NULL DEFAULT 1");
  // if file is an image, this stores the image height in pixels after upload
  sd_addtablecolumn($tbl, 'img_height',          $pre."`img_height` INT(10) UNSIGNED NOT NULL DEFAULT 0");
  // if file is an image, this stores the image width in pixels after upload
  sd_addtablecolumn($tbl, 'img_width',           $pre."`img_width` INT(10) UNSIGNED NOT NULL DEFAULT 0");
  // add some indexes for better search performance
  if(!sd_tablecolumnindexexists($tbl,'title'))       $DB->query($pre.'INDEX (`title`)');
  if(!sd_tablecolumnindexexists($tbl,'author'))      $DB->query($pre.'INDEX (`author`)');
  if(!sd_tablecolumnindexexists($tbl,'dateadded'))   $DB->query($pre.'INDEX (`dateadded`)');
  if(!sd_tablecolumnindexexists($tbl,'dateupdated')) $DB->query($pre.'INDEX (`dateupdated`)');
  if(!sd_columnindexexists($tbl,'tags','tags'))      $DB->query('CREATE INDEX `tags` ON '.$tbl.' (tags(50))');

  // --------------------- DB CHANGES FOR FILE_VERSIONS TABLE --------------------
  $tbl = PRGM_TABLE_PREFIX.'p'.$pluginid.'_file_versions';
  $pre = 'ALTER TABLE '.$tbl.' ADD ';
  // is filename referencing a remote site media file (for use with e.g. embevi.class!)
  sd_addtablecolumn($tbl, 'is_embeddable_media',
      $pre."`is_embeddable_media` TINYINT UNSIGNED NOT NULL DEFAULT 0");
  sd_addtablecolumn($tbl, 'duration',
      $pre."`duration` VARCHAR(20) collate utf8_unicode_ci NOT NULL default ''");
  sd_addtablecolumn($tbl, 'lyrics',
      $pre."`lyrics`   TEXT        collate utf8_unicode_ci NULL");

  // ----------------------- DB CHANGES FOR SECTIONS TABLE -----------------------
  $tbl = PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections';
  $pre = 'ALTER TABLE '.$tbl.' ADD ';
  sd_addtablecolumn($tbl, 'image',     $pre."image VARCHAR(100) collate utf8_unicode_ci NOT NULL DEFAULT ''");
  sd_addtablecolumn($tbl, 'thumbnail', $pre."thumbnail VARCHAR(100) collate utf8_unicode_ci NOT NULL DEFAULT ''");
  sd_addtablecolumn($tbl, 'fsprefix',  $pre."fsprefix VARCHAR(255) collate utf8_unicode_ci NOT NULL DEFAULT ''");

  // Columns to actually contain pipe-separated list of
  // usergroup-/user-ids for explicit access permission
  sd_addtablecolumn($tbl, 'access_groupids',    $pre."`access_groupids` TEXT NOT NULL");
  sd_addtablecolumn($tbl, 'access_userids',     $pre."`access_userids` TEXT NOT NULL");
  // allow user uploads on section-level in general; if "0", then uploads are prohibited
  // even if user's usergroup has submit privileges for plugin
  sd_addtablecolumn($tbl, 'allow_user_uploads', $pre."`allow_user_uploads` TINYINT UNSIGNED NOT NULL DEFAULT 1");
  // if "0" then ratings for files in the section is prohibited regardless of DLM plugin settings
  sd_addtablecolumn($tbl, 'enable_ratings',     $pre."`enable_ratings` TINYINT UNSIGNED NOT NULL DEFAULT 1");
  // if "0" then commenting for files in the section is prohibited regardless of DLM plugin settings/usergroup permissions
  sd_addtablecolumn($tbl, 'enable_comments',    $pre."`enable_comments` TINYINT UNSIGNED NOT NULL DEFAULT 1");
  // width (in pixels) of the left column in displaying files list for section
  sd_addtablecolumn($tbl, 'left_col_width',     $pre."`left_col_width` INT UNSIGNED NOT NULL DEFAULT 150");

/*
  $DB->query("CREATE TABLE {p".$pluginid."_dlm_owners} (
    `fileownerid`  INT(11) NOT NULL auto_increment,
    `author`       VARCHAR(255) collate utf8_unicode_ci NULL,
    `displayname`  VARCHAR(255) collate utf8_unicode_ci NULL,
    `userid`       INT(11) NOT NULL default 0,
    `usergroupid`  INT(11) NOT NULL default 0,
    `fileid`       INT(11) NOT NULL DEFAULT 0,
    `active`       TINYINT(4) NOT NULL DEFAULT 0,
    `datecreated`  INT(11) NULL,
    PRIMARY KEY (`fileownerid`),
    KEY `author` (`author`),
    KEY `fileid` (`fileid`))
    DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
*/
} //dlm_Upgrade200
} //do not remove!


if(!function_exists('dlm_Upgrade208'))
{
  function dlm_Upgrade208()
  {
    global $DB, $pluginid, $plugin_folder;
    //v2.0.7
    // improve IPv6 support
    $tbl = PRGM_TABLE_PREFIX.'p'.$pluginid.'_files';
    $DB->add_tablecolumn($tbl,'ipaddress', 'VARCHAR(40)', "collate utf8_unicode_ci NOT NULL DEFAULT ''");

    $tbl = PRGM_TABLE_PREFIX.'p'.$pluginid.'_file_downloads';
    $DB->query("ALTER TABLE $tbl CHANGE ipaddress ipaddress VARCHAR(40) collate utf8_unicode_ci NOT NULL DEFAULT ''");

    # track IP and userid for new file versions
    $tbl = PRGM_TABLE_PREFIX.'p'.$pluginid.'_file_versions';
    $DB->add_tablecolumn($tbl,'ipaddress', 'VARCHAR(40)', "collate utf8_unicode_ci NOT NULL DEFAULT ''");
    $DB->add_tablecolumn($tbl,'userid', 'INT(11)', 'UNSIGNED NOT NULL DEFAULT 0');
  }
} // do not remove!


if(!function_exists('dlm_Upgrade230'))
{
  function dlm_Upgrade230()
  {
    global $DB, $pluginid;

    // v2.3.0: store tags in core table
    require_once(SD_INCLUDE_PATH.'class_sd_tags.php');

    // Convert all file tags to core "tags" table entries
    $rows = $DB->query('SELECT fileid, tags FROM {p'.$pluginid.'_files}'.
                       " WHERE tags IS NOT NULL AND tags <> ''");
    while($f = $DB->fetch_array($rows,null,MYSQL_ASSOC))
    {
      SD_Tags::StorePluginTags($pluginid, $f['fileid'], $f['tags']);
    }
    $DB->query('UPDATE {p'.$pluginid.'_files}'.
               " SET tags = ''");
  }
} // do not remove!


if(!function_exists('dlm_Upgrade240')) #2014-01-19
{
  function dlm_Upgrade240()
  {
    global $pluginid;

    // v2.4.0: Sections-option to count files in upper section
    $tbl = PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections';
    $pre = 'ALTER TABLE '.$tbl.' ADD ';
    sd_addtablecolumn($tbl, 'count_files', $pre."count_files TINYINT(1) NOT NULL DEFAULT 1");
  }
} // do not remove!


if(!function_exists('dlm_CheckPhrasesAndSettings')) // do not remove!
{
  function dlm_CheckPhrasesAndSettings()
  {
    global $DB, $pluginid, $plugin_folder;

    // Plugin phrases
    sd_checkphrase($pluginid,'sections',                    'Sections');
    sd_checkphrase($pluginid,'click_here_to_submit_file',   'Click here to submit a File');
    sd_checkphrase($pluginid,'section_offline',             'This section is currently offline or does not exist.');
    sd_checkphrase($pluginid,'file_offline',                'This file is currently offline, does not exist or you do not have access permission.');
    sd_checkphrase($pluginid,'search_results',              'Search Results');
    sd_checkphrase($pluginid,'update',                      'Update');
    sd_checkphrase($pluginid,'search',                      'Search');
    sd_checkphrase($pluginid,'view_image',                  'View Image');
    sd_checkphrase($pluginid,'file_size',                   'File Size:');
    sd_checkphrase($pluginid,'author',                      'Author:');
    sd_checkphrase($pluginid,'date_added',                  'Date Added:');
    sd_checkphrase($pluginid,'date_updated',                'Date Updated:');
    sd_checkphrase($pluginid,'download_count',              'Download Count:');
    sd_checkphrase($pluginid,'rating',                      'Rating:');
    sd_checkphrase($pluginid,'rating2',                     'Rating');
    sd_checkphrase($pluginid,'download_now',                'Download Now');
    sd_checkphrase($pluginid,'no_file_to_download',         'No file to download');
    sd_checkphrase($pluginid,'more_info',                   'More Info');
    sd_checkphrase($pluginid,'more',                        '[more]');
    sd_checkphrase($pluginid,'date',                        'Date');
    sd_checkphrase($pluginid,'title',                       'Title');
    sd_checkphrase($pluginid,'num_of_downloads',            '# of Downloads');
    sd_checkphrase($pluginid,'5',                           '5');
    sd_checkphrase($pluginid,'10',                          '10');
    sd_checkphrase($pluginid,'20',                          '20');
    sd_checkphrase($pluginid,'description',                 'Description');
    sd_checkphrase($pluginid,'author_name',                 'Author Name');
    sd_checkphrase($pluginid,'file_submission_not_allowed', 'File submission is not allowed.');
    sd_checkphrase($pluginid,'select_file',                 'Browse for file:');
    sd_checkphrase($pluginid,'file_title',                  'File Title:');
    sd_checkphrase($pluginid,'file_description',            'File Description:');
    sd_checkphrase($pluginid,'optional_thumbnail',          'Optional Thumbnail:');
    sd_checkphrase($pluginid,'optional_image',              'Optional Image:');
    sd_checkphrase($pluginid,'submit_file',                 'Submit File');
    sd_checkphrase($pluginid,'file_submitted',              'Thanks! Your file has been submitted.');
    sd_checkphrase($pluginid,'needs_approval',              'It will be displayed in the directory once approved.');
    sd_checkphrase($pluginid,'unknown',                     'Unknown');
    sd_checkphrase($pluginid,'previous',                    'Previous');
    sd_checkphrase($pluginid,'next',                        'Next');
    sd_checkphrase($pluginid,'page',                        'Page');
    sd_checkphrase($pluginid,'of',                          'of');
    sd_checkphrase($pluginid,'members_only',                'You have to be a member to download this file.');
    sd_checkphrase($pluginid,'description2',                'Description:');
    sd_checkphrase($pluginid,'sort_by',                     'Sort by:');
    sd_checkphrase($pluginid,'show',                        'Show:');
    sd_checkphrase($pluginid,'search_by',                   'Search by:');
    sd_checkphrase($pluginid,'search_text',                 'Search Text:');
    sd_checkphrase($pluginid,'votes',                       'votes');
    sd_checkphrase($pluginid,'agree',                       'I Agree');
    sd_checkphrase($pluginid,'disagree',                    'Back');
    sd_checkphrase($pluginid,'must_agree',                  'You must agree to the following license agreement to download this file');
    sd_checkphrase($pluginid,'upload_error',                'Upload Error');
    sd_checkphrase($pluginid,'file_too_big',                'The file you are trying to upload is too big.');
    sd_checkphrase($pluginid,'file_upload_error',           'There was an error trying to upload your file. Please try again.');
    sd_checkphrase($pluginid,'image_too_big',               'The image you are trying to upload is too big.');
    sd_checkphrase($pluginid,'image_upload_error',          'There was an error trying to upload your image. Please try again.');
    sd_checkphrase($pluginid,'thumbnail_too_big',           'The thumbnail you are trying to upload is too big.');
    sd_checkphrase($pluginid,'thumbnail_upload_error',      'There was an error trying to upload your thumbnail. Please try again.');
    sd_checkphrase($pluginid,'upload_error_no_title',       'The uploaded file must have a title.');
    sd_checkphrase($pluginid,'upload_error_no_filename',    'The filename is invalid or empty.');
    sd_checkphrase($pluginid,'upload_error_no_file',        'No filename was specified or file uploaded.');
    sd_checkphrase($pluginid,'upload_error_no_section',     'At least one section has to be selected.');
    sd_checkphrase($pluginid,'upload_error_no_version',     'The version of the file has to be specified.');
    sd_checkphrase($pluginid,'error_invalid_file_ext',      'Cannot upload the selected file because the file extension is not allowed.');
    sd_checkphrase($pluginid,'unknown_image_type',          'Unknown Image Format');
    sd_checkphrase($pluginid,'version',                     'Version:');
    sd_checkphrase($pluginid,'previous_versions',           'Previous versions');
    sd_checkphrase($pluginid,'cannot_link_directly',        'You cannot directly link to this download');

    // v2.0.0:
    sd_checkphrase($pluginid,'file_edit_approval',         'Note: File changes are pending approval by website administrator.');
    sd_checkphrase($pluginid,'file_submit_approval',       'Note: File uploads are pending approval by website administrator.');
    sd_checkphrase($pluginid,'no_section_access',          'You do not have access to this section.');
    sd_checkphrase($pluginid,'file_dates',                 'File Dates');
    sd_checkphrase($pluginid,'click_to_view',              'Click to view image...');
    sd_checkphrase($pluginid,'date_updated_hint',          '<br />Empty = unchanged. "now" = set to current date; "0" = reset date.');
    sd_checkphrase($pluginid,'file_thumbnail_error',       'Failed to create thumbnail');
    sd_checkphrase($pluginid,'section',                    'Section');
    sd_checkphrase($pluginid,'edit_file',                  'Edit File');
    sd_checkphrase($pluginid,'add_file_version',           'Add new File version');
    sd_checkphrase($pluginid,'update_file_settings',       'Update File Settings');
    sd_checkphrase($pluginid,'delete_thumbnail',           'Delete Thumbnail');
    sd_checkphrase($pluginid,'delete_image',               'Delete Image');
    sd_checkphrase($pluginid,'version',                    'Version:');
    sd_checkphrase($pluginid,'author',                     'Author:');
    sd_checkphrase($pluginid,'optional_thumbnail_and_image', 'Optional Thumbnail and Image');
    sd_checkphrase($pluginid,'submit_create_thumbnail',    'Create thumbnail if uploaded file is an image?');
    sd_checkphrase($pluginid,'submit_create_thumb_hint',   '(if checked, existing thumbnail will be replaced)');
    sd_checkphrase($pluginid,'submit_create_thumb_hint2',  '(when checked a specified thumbnail will be ignored)');
    sd_checkphrase($pluginid,'related_files',              'Related Files');
    sd_checkphrase($pluginid,'current_file_version',       'Current File Version');
    sd_checkphrase($pluginid,'download_locations',         'Download Locations for');
    sd_checkphrase($pluginid,'download_mirror',            'Mirror');
    sd_checkphrase($pluginid,'file_standalone_option',     'Select "File Version" type of this file');
    sd_checkphrase($pluginid,'back',                       'Back');
    sd_checkphrase($pluginid,'file_online',                'File is online?');
    sd_checkphrase($pluginid,'yes',                        'Yes');
    sd_checkphrase($pluginid,'no',                         'No');
    sd_checkphrase($pluginid,'edit_author_edit',           'Note: editing of the author name is case-sensitive.');
    sd_checkphrase($pluginid,'edit_file_storage',          'File storage location:');
    sd_checkphrase($pluginid,'delete_fileversion',         'Delete File Version');
    sd_checkphrase($pluginid,'delete_fileversion_error',   'File Version cannot be deleted!');
    sd_checkphrase($pluginid,'delete_fileversion_success', 'File Version deleted.');
    sd_checkphrase($pluginid,'errors_header',              'Please note the following errors:');
    sd_checkphrase($pluginid,'upload_to_section',          'Upload into Section:', '');
    sd_checkphrase($pluginid,'user_submit_change_subject', ' submitted changes to existing file version to your website.');
    sd_checkphrase($pluginid,'user_submit_change_message', 'Changes to below file have been submitted to the download manager plugin: ');
    sd_checkphrase($pluginid,'user_submit_new_subject',    ' submitted changes to a file to your website');
    sd_checkphrase($pluginid,'user_submit_new_message',    'A new file has been submitted to the download manager plugin: ');
    sd_checkphrase($pluginid,'invalid_email_address',      'Invalid email address detected for notification');
    sd_checkphrase($pluginid,'email_notification_failed',  'Failed to send email notification to ');
    sd_checkphrase($pluginid,'user_submit_url',            'Specify URL for remote file:');
    sd_checkphrase($pluginid,'user_url_invalid',           'Specified URL was not accepted.');
    sd_checkphrase($pluginid,'dlm_unique_id',              'Unique Indentifier');
    sd_checkphrase($pluginid,'dlm_unique_id_hint',         'Unique identifier (optional) could be an internal reference number, product id etc.');
    sd_checkphrase($pluginid,'dlm_tags',                   'Tags:');
    sd_checkphrase($pluginid,'dlm_tags_hint',              'Tags work like keywords and can be used for e.g. related files or tags cloud. Please separate each tag by a comma (",").' /*,true*/);
    sd_checkphrase($pluginid,'dlm_view_count',             'Views:');
    sd_checkphrase($pluginid,'dlm_open_link',              'Open Link');
    sd_checkphrase($pluginid,'dlm_error_no_upload_dir',    '<p><strong>Admin: ERROR! Upload Directory does not exist.</strong></p>');
    sd_checkphrase($pluginid,'dlm_offline_files',          'Offline Files');
    sd_checkphrase($pluginid,'title_errors',               'Errors');
    sd_checkphrase($pluginid,'title_delete_error',         'Delete Error');
    sd_checkphrase($pluginid,'title_notification_error',   'Notification Error');
    sd_checkphrase($pluginid,'title_upload_error',         'Upload Error');
    sd_checkphrase($pluginid,'file_version_default',       'Default (Version)');
    sd_checkphrase($pluginid,'file_version_standalone',    'Standalone');
    sd_checkphrase($pluginid,'file_version_mirror',        'Mirror');
    sd_checkphrase($pluginid,'no_tags_available',          'No Tags available.');
    sd_checkphrase($pluginid,'untitled',                   'Untitled');

    // Admin phrases
    sd_checkadminphrase($pluginid,'dlm_configuration_error',  'Configuration Error');
    sd_checkadminphrase($pluginid,'dlm_files_upload_directory', 'Files Upload Directory');
    sd_checkadminphrase($pluginid,'dlm_files_import_directory', 'Files Import Directory');
    sd_checkadminphrase($pluginid,'dlm_images_upload_directory', 'Images Upload Directory');
    sd_checkadminphrase($pluginid,'dlm_directory_not_writable', 'is not writable - please chmod to 0777');
    sd_checkadminphrase($pluginid,'dlm_directory_not_exists', 'does not exist');
    sd_checkadminphrase($pluginid,'dlm_menu_sections',        'Sections');
    sd_checkadminphrase($pluginid,'dlm_menu_add_section',     'Add Section');
    sd_checkadminphrase($pluginid,'dlm_menu_edit_section',    'Edit Section');
    sd_checkadminphrase($pluginid,'dlm_menu_list_files_for',  'List Files for');
    sd_checkadminphrase($pluginid,'dlm_menu_files',           'Files');
    sd_checkadminphrase($pluginid,'dlm_menu_add_file_by_upload', 'Add File by Upload');
    sd_checkadminphrase($pluginid,'dlm_menu_add_file_by_url', 'Add File by URL');
    sd_checkadminphrase($pluginid,'dlm_menu_batch_import',    'Batch Import');
    sd_checkadminphrase($pluginid,'dlm_menu_batch_upload',    'Batch Upload');
    sd_checkadminphrase($pluginid,'dlm_menu_settings',        'Settings');
    sd_checkadminphrase($pluginid,'dlm_offline_files',        'Offline Files');
    sd_checkadminphrase($pluginid,'dlm_files_in_section',     'Files in Section');
    sd_checkadminphrase($pluginid,'dlm_head_file',            'File');
    sd_checkadminphrase($pluginid,'dlm_head_version',         'Version');
    sd_checkadminphrase($pluginid,'dlm_head_thumbnail',       'Thumbnail');
    sd_checkadminphrase($pluginid,'dlm_head_thumbnail_short', 'TN');
    sd_checkadminphrase($pluginid,'dlm_head_sections',        'Section(s)');
    sd_checkadminphrase($pluginid,'dlm_head_author',          'Author');
    sd_checkadminphrase($pluginid,'dlm_head_updated',         'Updated');
    sd_checkadminphrase($pluginid,'dlm_head_added',           'Added');
    sd_checkadminphrase($pluginid,'dlm_head_downloaded_short','D/L');
    sd_checkadminphrase($pluginid,'dlm_head_status',          'Status');
    sd_checkadminphrase($pluginid,'dlm_head_delete',          'Delete?');
    sd_checkadminphrase($pluginid,'dlm_prompt_upd_files',     'Really update all selected files?');
    sd_checkadminphrase($pluginid,'dlm_prompt_del_files',     'Really delete all selected files?');
    sd_checkadminphrase($pluginid,'dlm_return_to_dlm',        'Return to Download Manager');
    sd_checkadminphrase($pluginid,'dlm_online',               'Online');
    sd_checkadminphrase($pluginid,'dlm_offline',              'Offline');
    sd_checkadminphrase($pluginid,'dlm_downloads_for',        'Downloads for');
    sd_checkadminphrase($pluginid,'dlm_user_name',            'User Name');
    sd_checkadminphrase($pluginid,'dlm_ip_address',           'IP Address');
    sd_checkadminphrase($pluginid,'dlm_download_date',        'Download Date');
    sd_checkadminphrase($pluginid,'dlm_view_downloads',       'Views Downloads');
    sd_checkadminphrase($pluginid,'dlm_edit_file',            'Edit File');
    sd_checkadminphrase($pluginid,'dlm_upload_new_file',      'Upload New File');
    sd_checkadminphrase($pluginid,'dlm_never',                'Never');
    sd_checkadminphrase($pluginid,'dlm_section',              'Section');
    sd_checkadminphrase($pluginid,'dlm_section_hint1',        'Select the section(s) in which the file is to be displayed:<br />(Use CTRL/Shift+Click to de-/select sections)');
    sd_checkadminphrase($pluginid,'dlm_file_title_hint1',     'File title as it will be displayed in the downloads list:');
    sd_checkadminphrase($pluginid,'dlm_no_section_set',       'No Section');
    sd_checkadminphrase($pluginid,'dlm_active',               'Active');
    sd_checkadminphrase($pluginid,'dlm_display_file_online',  'Display file online?');
    sd_checkadminphrase($pluginid,'dlm_dateentry_hint',       'Entry format example (English!)');
    sd_checkadminphrase($pluginid,'dlm_dateadded_hint',       'Empty = unchanged. "now" = set to current date; "0" = reset date.');
    sd_checkadminphrase($pluginid,'dlm_current_value',        'Current value');
    sd_checkadminphrase($pluginid,'dlm_embedded_in_details',  'Embed media file in details page');
    sd_checkadminphrase($pluginid,'dlm_embedded_in_details_hint', 'If file links to a supported media file, then embed it directly in the details page (default: yes)? Otherwise the download link opens the link in a new window.');
    sd_checkadminphrase($pluginid,'dlm_embedded_in_list',      'Embed media file in files list');
    sd_checkadminphrase($pluginid,'dlm_embedded_in_list_hint', 'If file links to a supported media file, then embed it directly in the files list (default: no)?');
    sd_checkadminphrase($pluginid,'dlm_downloads_by_ip_address', 'Downloads by IP-Address');
    sd_checkadminphrase($pluginid,'dlm_username',              'Username');
    sd_checkadminphrase($pluginid,'dlm_filename',              'Filename');
    sd_checkadminphrase($pluginid,'dlm_no_downloads_for_ip',   'No Downloads for IP-Address');
    sd_checkadminphrase($pluginid,'dlm_no_downloads_for_user', 'No Downloads for User');
    sd_checkadminphrase($pluginid,'dlm_downloads_by_user',     'Downloads by User');
    sd_checkadminphrase($pluginid,'dlm_ip_address',            'IP Address');
    sd_checkadminphrase($pluginid,'dlm_file_unknown',          'Unknown');
    sd_checkadminphrase($pluginid,'dlm_no_recent_downloads',   'No recent downloads');
    sd_checkadminphrase($pluginid,'dlm_reset_rating',          'Reset rating?');
    sd_checkadminphrase($pluginid,'confirm_version_delete',    'Really DELETE file version?');

    sd_checkadminphrase($pluginid,'dlm_settings_admin_options',     'Admin');
    sd_checkadminphrase($pluginid,'dlm_settings_download_options',  'Download');
    sd_checkadminphrase($pluginid,'dlm_settings_upload_options',    'Upload');
    sd_checkadminphrase($pluginid,'dlm_settings_frontpage_options', 'Frontpage');
    sd_checkadminphrase($pluginid,'dlm_settings_rating_options',    'Ratings');
    sd_checkadminphrase($pluginid,'dlm_file_embed_width',           'Width (in pixels) of embedded media files:');
    sd_checkadminphrase($pluginid,'dlm_file_embed_height',          'Height (in pixels) of embedded media files:');

    // ------------------------
    $section = 'Admin Options';
    // ------------------------
    sd_checksetting3($pluginid, $section, 'Display Latest Downloads',  'Display # entries in "Latest Downloads" section (0 = disable)?', 'text', '10', '10');
    sd_checksetting3($pluginid, $section, 'Latest Files Thumbnails',   'Display thumbnail images in "Latest Files" list (Admin page)?<br />This will also enable display of actual filename for pictures.', 'yesno', '1', '20');

    // --------------------------
    $section = 'Upload Options';
    // --------------------------
    sd_checksetting3($pluginid, $section, 'Allow Remote Upload Links', 'Allow users to specify remote files (URL) in submit form (in addition to file upload; default: No)?','yesno', '0', '5');
    sd_checksetting3($pluginid, $section, 'Allowed File Extensions',   'You can restrict the files which can be uploaded by your users by entering a list of file extensions here seperated by commas eg (zip, jpg, gif). If left blank all files are allowed.', 'text', '', '10');
    sd_checksetting3($pluginid, $section, 'Auto-Approve Uploads',      'Automatically approve user uploads?', 'yesno', '0', '15');
    sd_checksetting3($pluginid, $section, 'Check User Remote Link',    'Apply a conformity check on the user submitted link (URL) upon submittal (default: Yes)?', 'yesno', '1', '20');
    sd_checksetting3($pluginid, $section, 'Default Chmod',             'Default chmod for uploaded files (leave blank for none)', 'text', '0644', '25');
    sd_checksetting3($pluginid, $section, 'Default Owner',             'Default owner for uploaded files (default: empty)', 'text', '', '30');
    sd_checksetting3($pluginid, $section, 'Default Upload Location',   'Default location for user uploads (default: Filesystem)?',
      'select:\r\n0|Database\r\n1|Filesystem', '1', '35');
    sd_checksetting3($pluginid, $section, 'Edit File Storage Change',  'Allow user to specify upload location (Database/Filesystem) when editing a file?', 'yesno', '0', '40');
    sd_checksetting3($pluginid, $section, 'File Submission',           'Allow members to submit files (not for Guests)?', 'yesno', '0', '45');
    sd_checksetting3($pluginid, $section, 'File Notification Email',   'This email address will receive an email message when a new file is submitted:<br />Separate multiple addresses with commas.', 'text', '', '50');
    sd_checksetting3($pluginid, $section, 'File Storage Location',     'Enter the full path to your stored files e.g. \'/home/myuser/myfiles\'. If you leave this setting blank it will default to \'/plugins/'.$plugin_folder.'/ftpfiles/\' but it is strongly suggested that this location is set outside your web root for added security.<br /><strong>Note:</strong> If this setting is changed after you have already uploaded files into the filesystem you will need to manually move those files to the new location', 'text', '', '55');
    sd_checksetting3($pluginid, $section, 'File Storage Location URL', 'Above location - if not empty - is a local webserver directory name (filesystem). In order to correctly display IMAGE files (not equal to thumbnails/images) in the front page, this needs to be mapped to a corresponding URL:', 'text', '', '60');
    sd_checksetting3($pluginid, $section, 'Max Upload Size',           'You can specify here a maximum size for your file uploads. The size should be in KB so 1MB would be 1024<br />Note: The actual maximum size is limited by your PHP settings.','text', '', '65');
    sd_checksetting3($pluginid, $section, 'User Submit File Count',    'How many files should a user be allowed to submit at once?<br />'.
      'The first uploaded file is the main one, all others are "Related Files". Default is "<strong>1</strong>", maximum is "<strong>10</strong>"
      (regardless of what is entered here).', 'text', '1', '70');

    // ----------------------------
    $section = 'Frontpage Options';
    // ----------------------------
    sd_checksetting3($pluginid, $section, 'Allow User Comments',            'Allow visitors to post comments for files in general (default: Yes)?<br />If set to No, commenting will be disabled for all.', 'yesno', '1', 10);
    sd_checksetting3($pluginid, $section, 'Description Below Title',        'Display the description below title instead below all details?<br />Default is "Yes" (below title)', 'yesno', '1', 20);
    sd_checksetting3($pluginid, $section, 'Display Sections',               'Display files with section hierarchy?', 'yesno', '1', 30);
    sd_checksetting3($pluginid, $section, 'Downloads Header Guests',        'Header message for Guests on top of Downloads display:', 'wysiwyg',
      '<p>The Download Area shows available files grouped by below sections. Access to non-public files is only allowed when being logged in. '.
      'For signing-up please click the <strong>"Register Now"</strong> link in the <strong>"Login Panel"</strong>.</p>', 40);
    sd_checksetting3($pluginid, $section, 'Enable Author File Delete',      'Allow authors to delete File Version (default: no)?', 'yesno', '0', 50);
    sd_checksetting3($pluginid, $section, 'Page Size',                      'The default number of files to display per page', 'text',  '5', 60);
    sd_checksetting3($pluginid, $section, 'Details Only On More Info Page', 'Display file details only on the "More Info" page?<br />Default is <strong>No</strong>.', 'yesno', '0', 70);
    sd_checksetting3($pluginid, $section, 'Display Edit Link For Files',    'Display an "Edit File" link on the Downloads page for each file if permitted for user?', 'yesno', '0', 80);
    sd_checksetting3($pluginid, $section, 'Display Author',                 'Display the "Author" for each file?', 'yesno', '1', 90);
    sd_checksetting3($pluginid, $section, 'Display Version',                'Display the "Version" for each file?<br />Version is never displayed for embedded media.', 'yesno', '1', 100);
    sd_checksetting3($pluginid, $section, 'Display Date Added',             'Display the "Date added" for each file?', 'yesno', '1', 110);
    sd_checksetting3($pluginid, $section, 'Display Date Updated',           'Display the "Date updated" for each file?', 'yesno', '1', 120);
    sd_checksetting3($pluginid, $section, 'Display Download Count',         'Display the "Download Count" for each file?', 'yesno', '1', 130);
    sd_checksetting3($pluginid, $section, 'Display Filesize',               'Display the file size for each file?<br />File size is never displayed for embedded media.', 'yesno', '1', 140);
    sd_checksetting3($pluginid, $section, 'Display Tags',                   'Display the tags entered for each file?', 'yesno', '1', 150);
    sd_checksetting3($pluginid, $section, 'Display Previous Versions',      'Show a list of previous versions of files which users can download?', 'yesno', '1', 160);
    sd_checksetting3($pluginid, $section, 'Display Sort Options',           'Display the "Sort by" options above files list on the frontpage (default: yes)?', 'yesno', '1', 170);
    sd_checksetting3($pluginid, $section, 'Display Tag Cloud',              'Display a tag cloud for files page (default: Yes)?', 'yesno', '1', 180, true);
    sd_checksetting3($pluginid, $section, 'Enable Details Page',            'Display link to and enable "More Info" page at all?<br />Default is <strong>Yes</strong>.', 'yesno', '1', 190);
    sd_checksetting3($pluginid, $section, 'File Separator Image',           'Image filename (gif/jpg/png) or HTML code to separate each file
      (images must be located in images/misc folder)?<br />Set <strong>empty</strong> to show regular &lt;hr&gt; tag.', 'textarea', '', 200);
    sd_checksetting3($pluginid, $section, 'Imagewidth On More Info Page',   'Display width of image files on the "More Info" page?<br />Default is "<strong>450</strong>".', 'text', '450', 210);
    sd_checksetting3($pluginid, $section, 'File Description In Downloads',  'Display file description (if available) also in files listing page and not only on the file details page?<br />', 'yesno', '0', 220);
    sd_checksetting3($pluginid, $section, 'File Details Rows',              'Display file details one per row like DLM 1.4 ("Yes" means 1 detail per row, else 2 per row)?', 'yesno', '1', 230);
    sd_checksetting3($pluginid, $section, 'File Details Ignore Date Updated', 'File details ignore if "Date Updated" is set or not ("Yes" means to always show details, "No" means DLM 1.4 behavior)?', 'yesno', '0', 240);
    sd_checksetting3($pluginid, $section, 'Message No Thumbnail Available', 'Default text displayed if no thumbnail is available<br />Set it to empty for none.', 'text', 'no image<br />available', 250);
    sd_checksetting3($pluginid, $section, 'Show More Info For Images',      'Display the "More Info" link for images?<br />Default is "Yes". Set to "No" if it should not be displayed for image files (jpg/gif/png).', 'yesno', '1', 260);
    sd_checksetting3($pluginid, $section, 'Show Footer',                    'Show the Footer', 'yesno', '1', 270);
    sd_checksetting3($pluginid, $section, 'Show Search Header',             'Show the Search Options Header', 'yesno', '1', 280);
    sd_checksetting3($pluginid, $section, 'Show Section Thumbnail',         'Show thumbnail images in front of section name (section menu):<br />This only applies if also "Display Sections" is enabled.', 'yesno', '1', 290);
    sd_checksetting3($pluginid, $section, 'Show Section Image',             'Show image of current section in front of section menulist:', 'yesno', '1', 300);
    sd_checksetting3($pluginid, $section, 'Show Upload File Version',       'Display file version selection on upload form (default: yes)?', 'yesno', '1', 310);
    sd_checksetting3($pluginid, $section, 'Show Upload Create Thumbnail Option', 'Display option to create thumbnail for image files on upload form (default: yes)?', 'yesno', '1', 320);
    sd_checksetting3($pluginid, $section, 'Show Upload Optional Thumbnail', 'Display optional thumbnail upload entries on upload form (default: yes)?', 'yesno', '1', 330);
    sd_checksetting3($pluginid, $section, 'Show Upload Section Selection',  'Display section selection on upload form (default: yes)?<br />"No" means to upload file into current section.','yesno', '1', 340);
    sd_checksetting3($pluginid, $section, 'Thumbnails New Window',          'Clicking thumbnails will open a new window for full image viewing?<br />Yes = open in new window, No = display in current window.','yesno', '1', 350 /*,true*/);
    sd_checksetting3($pluginid, $section, 'Thumbnail Display Width',        'Display width of thumbnails in "Downloads"?<br />Default is "100". Note: "0" will display thumbnails <strong>unscaled</strong>.', 'text', '100', 360);

    // ------------------ DLM 2.0.2 - DB CHANGES FOR FILES TABLE ----------------
    $tbl = PRGM_TABLE_PREFIX.'p'.$pluginid.'_files';
    $pre = 'ALTER TABLE '.$tbl.' ADD ';
    sd_addtablecolumn($tbl, 'audio_class',    $pre."`audio_class` VARCHAR(100) collate utf8_unicode_ci NOT NULL DEFAULT 'jp-jplayer'");
    sd_addtablecolumn($tbl, 'video_class',    $pre."`video_class` VARCHAR(100) collate utf8_unicode_ci NOT NULL DEFAULT 'jp-video-270p'");
    sd_addtablecolumn($tbl, 'media_autoplay', $pre."`media_autoplay` INT(1) NOT NULL DEFAULT 0");
    sd_addtablecolumn($tbl, 'media_loop',     $pre."`media_loop` INT(1) NOT NULL DEFAULT 0");
    // if file is an embeddable video, this stores the width in pixels
    sd_addtablecolumn($tbl, 'embed_width',    $pre."`embed_width` INT(10) UNSIGNED NOT NULL DEFAULT 560");
    // if file is an embeddable video, this stores the height in pixels
    sd_addtablecolumn($tbl, 'embed_height',   $pre."`embed_height` INT(10) UNSIGNED NOT NULL DEFAULT 350");
    // if file is an embeddable audio/video file, allow download / display download link?
    sd_addtablecolumn($tbl, 'media_download', $pre."`media_download` INT(1) UNSIGNED NOT NULL DEFAULT 1");

    sd_checkadminphrase($pluginid,'dlm_file_media_options_desc',  'Other embedded media options (for uploaded files):');
    sd_checkadminphrase($pluginid,'dlm_file_media_autoplay_desc', 'Automatically start playback of media file (for uploaded files)?',2/*,true*/);
    sd_checkadminphrase($pluginid,'dlm_file_media_loop_desc',     'Loop playback at end of media file (for uploaded files)?',2/*,true*/);
    sd_checkadminphrase($pluginid,'dlm_file_media_download_desc', 'Display <strong>Open Link</strong> for this file if it is an uploaded media file?');
    sd_checkadminphrase($pluginid,'dlm_file_audio_class_desc',    'HTML classname for audio files:<br />
      This setting will be used for the following media types: mp3, m4v, m4a, ogg',2/*,true*/);
    sd_checkadminphrase($pluginid,'dlm_file_video_class_desc',    'HTML classname for video files:<br />
      Used for m4v, ogg. Available: <strong>jp-video-270p</strong> or <strong>jp-video-360p</strong>.',2/*,true*/);

    sd_removesetting($pluginid, $section, 'jplayer_html');
    sd_checksetting3($pluginid, $section, 'jPlayer Audio HTML', 'HTML code for the jPlayer audio player (only for details page).<br />
  Used for mp3, m4a and ogg. Do <strong>NOT<strong> remove any <strong>%...%</strong> symbols!', 'textarea', '
  <div id="%player_id%" class="%audio_class%"></div>
  <div class="jp-audio">
    <div class="jp-type-single">
      <div id="jp_interface_1" class="jp-interface">
        <ul class="jp-controls">
          <li><a href="#" class="jp-play" tabindex="1">play</a></li>
          <li><a href="#" class="jp-pause" tabindex="1">pause</a></li>
          <li><a href="#" class="jp-stop" tabindex="1">stop</a></li>
          <li><a href="#" class="jp-mute" tabindex="1">mute</a></li>
          <li><a href="#" class="jp-unmute" tabindex="1">unmute</a></li>
        </ul>
        <div class="jp-progress">
          <div class="jp-seek-bar">
            <div class="jp-play-bar"></div>
          </div>
        </div>
        <div class="jp-volume-bar">
          <div class="jp-volume-bar-value"></div>
        </div>
        <div class="jp-current-time"></div>
        <div class="jp-duration"></div>
        <div class="jp-full-screen"></div>
        <div class="jp-restore-screen"></div>
      </div>
    </div>
  </div>
', '66');

  sd_checksetting3($pluginid, $section, 'jPlayer Video HTML', 'HTML code for the jPlayer video player (only for details page).<br />
  Used for m4v. Do NOT remove any <strong>%...%</strong> symbols!', 'textarea', '
  <div class="jp-video %video_class%">
    <div class="jp-type-single">
      <div id="%player_id%" class="jp-jplayer"></div>
      <div id="jp_interface_1" class="jp-interface">
        <div class="jp-video-play"></div>
        <ul class="jp-controls">
          <li><a href="#" class="jp-play" tabindex="1">play</a></li>
          <li><a href="#" class="jp-pause" tabindex="1">pause</a></li>
          <li><a href="#" class="jp-stop" tabindex="1">stop</a></li>
          <li><a href="#" class="jp-mute" tabindex="1">mute</a></li>
          <li><a href="#" class="jp-unmute" tabindex="1">unmute</a></li>
        </ul>
        <div class="jp-progress">
          <div class="jp-seek-bar">
            <div class="jp-play-bar"></div>
          </div>
        </div>
        <div class="jp-volume-bar">
          <div class="jp-volume-bar-value"></div>
        </div>
        <div class="jp-current-time"></div>
        <div class="jp-duration"></div>
        <div class="jp-full-screen"></div>
        <div class="jp-restore-screen"></div>
      </div>
    </div>
  </div>
', '67');

    // ---------------------------
    $section = 'Download Options';
    // ---------------------------
    sd_checksetting3($pluginid, $section, 'Allow Download Accelerators', 'Allow download accelerators to e.g. resume downloads and download files in parts at a time?','yesno', '1', '60');
    sd_checksetting3($pluginid, $section, 'Allow Per User Limits',       'This option allows you to choose to limit the number of times a file can be downloaded per user. If this is not enabled, any download limits will be applied globally for that file.<br /><strong>Important!</strong> This will only work if you have MySql 4.1 or above. If you enable this option and do not have MySql 4.1 you will experience SQL errors!', 'yesno', '0', '7');
    sd_checksetting3($pluginid, $section, 'Block Remote Downloads',      'Block direct links to downloads? The ensures that people cannot hotlink to your downloads', 'yesno', '0', '13');
    sd_checksetting3($pluginid, $section, 'Count Admin Downloads',       'Should admin downloads increment the download counter (view counter for embedded media files)?', 'yesno', '0', '5');
    sd_checksetting3($pluginid, $section, 'Force Download',              'When clicking on a download link, this option will allow the user to select whether to open/save rather than opening automatically with the associated system application', 'yesno', '0', '14');
    sd_checksetting3($pluginid, $section, 'Default License Agreement',
      'Default "<strong>License Agreement</strong>" setting when adding files (default: Yes)?', 'yesno', '1', '20'/*,true*/);
    sd_checksetting3($pluginid, $section, 'License Agreement',           'License Agreement text for downloads', 'textarea', '', '21'/*,true*/);
    sd_checksetting3($pluginid, $section, 'Log Downloads',               'Log each file download?<br />You might want to disable this if having space or performance issues.', 'yesno', '1', '6');

    // ------------------------------
    $section = 'File Rating Options';
    // ------------------------------
    sd_checksetting3($pluginid, $section, 'Display Ratings', 'Display file ratings?<br />Note: Rating a file is only allowed for logged-in members which have also permission to submit comments.', 'yesno', '1', '7'/*,true*/);

    //v2.0.5
    $DB->ignore_error = true;
    $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_file_versions','fileid');
    $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_files','datestart');
    $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_files','dateend');
    $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_files','author');
    $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_files','currentversionid');
    $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections','parentid');
    $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections','name');
    $DB->ignore_error = false;

    //v2.0.6
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'pluginsettings SET input = \'select:\r\n0|Database\r\n1|Filesystem\' WHERE pluginid = '.$pluginid." AND title = 'default_upload_location'");
    InsertPhrase($pluginid, 'select_default_upload_location_database', 'Database');
    InsertPhrase($pluginid, 'select_default_upload_location_filesystem', 'Filesystem');

    //v2.2.0 - 2013-08-14
    $section = 'Frontpage Options';
    sd_checksetting3($pluginid, $section, 'Enable SEO',
      'If SEO is activated for the site (see Admin|Settings|SEO Settings), should all <strong>section and file titles</strong> be converted to their SEO names (default: No)?<br />'.
      'If possible, the plugin will try to redirect (301) old URLs to the new URL version.', 'yesno', '0', 5);
    sd_checksetting3($pluginid, $section, 'Display Comment Counts', 'Display the number of comments for each file (default: yes)', 'yesno', '1', 12);
    sd_checksetting3($pluginid, $section, 'Tagcloud Location', 'Location of the tag cloud (default: Bottom)?',
      'select:\r\n0|Top\r\n1|Bottom\r\n2|Both', '1', 185);

    //v2.4.0 - 2014-01-19
    sd_checkadminphrase($pluginid,'dlm_sections_count_files', 'Count Files');
    sd_checkadminphrase($pluginid,'dlm_sections_count_files_descr', 'Include sum of all files within this '.
    'and all descendent sections to the parent section (default: Yes)?');

  } //dlm_CheckPhrasesAndSettings

} // do not remove!


// ############################################################################
// INSTALL PLUGIN
// ############################################################################

if(($installtype == 'install') && empty($pluginid))
{
  /* v2.0.0, tobias, November 2010:

  The plugin and it's installation have been enhanced to allow "self-cloning"
  of the plugin within Subdreamer 3.3+

  This means, by just copying the plugin folder contents (files and sub-folders)
  into a different folder under "plugins", it will determine it's own instance
  based on the folder name (not plugin name alone) and automatically use it's
  own tables and settings etc.
  Thus, it is now possible to have the same plugin code in 2+ different folders
  E.g. plugins/download_manager/
  and  plugins/my_special_dlm

  Important: the (small) thing for the administrator to think of is to
  ALWAYS upgrade ALL plugin instance folders at the same time (upload/upgrade)
  to avoid any further issues.
  */

  // At this point SD3 has to provide a new plugin id
  $pluginid = CreatePluginID($pluginname);

  // If for whatever reason the plugin id is invalid, return false NOW!
  if(empty($pluginid))
  {
    return false;
  }

  // Create the new plugin tables:
  $DB->query("CREATE TABLE IF NOT EXISTS {p".$pluginid."_files} (
  `fileid`           INT(10)          NOT NULL auto_increment,
  `activated`        TINYINT          NOT NULL default 0,
  `uniqueid`         VARCHAR(100)     collate utf8_unicode_ci NOT NULL default '',
  `currentversionid` INT              NOT NULL default 0,
  `title`            VARCHAR(255)     collate utf8_unicode_ci NOT NULL default '',
  `author`           VARCHAR(255)     collate utf8_unicode_ci NOT NULL default '',
  `description`      mediumtext       collate utf8_unicode_ci NOT NULL,
  `image`            VARCHAR(100)     collate utf8_unicode_ci NOT NULL default '',
  `thumbnail`        VARCHAR(100)     collate utf8_unicode_ci NOT NULL default '',
  `dateadded`        INT(10)          NOT NULL default 0,
  `dateupdated`      INT(10)          NOT NULL default 0,
  `downloadcount`    INT(10)          NOT NULL default 0,
  `datestart`        INT(10)          NOT NULL default 0,
  `dateend`          INT(10)          NOT NULL default 0,
  `maxdownloads`     INT(11)          NOT NULL default 0,
  `maxtype`          TINYINT          NOT NULL default 0,
  `licensed`         TINYINT          NOT NULL default 0,
  PRIMARY KEY (fileid))
  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

  $DB->query("CREATE TABLE IF NOT EXISTS {p".$pluginid."_file_versions} (
  versionid           INT(10)         NOT NULL auto_increment,
  fileid              INT(11)         NOT NULL default 0,
  version             VARCHAR(20)     collate utf8_unicode_ci NOT NULL default '',
  file                longblob NOT    NULL,
  filename            VARCHAR(250)    collate utf8_unicode_ci NOT NULL,
  storedfilename      VARCHAR(255)    collate utf8_unicode_ci NOT NULL default '',
  filesize            VARCHAR(20)     collate utf8_unicode_ci NOT NULL default '',
  filetype            VARCHAR(50)     collate utf8_unicode_ci NOT NULL default '',
  datecreated         int(11)         NOT NULL,
  is_embeddable_media TINYINT         unsigned NOT NULL default 0,
  duration            VARCHAR(20)     collate utf8_unicode_ci NOT NULL default '',
  lyrics              TEXT            collate utf8_unicode_ci NULL,
  PRIMARY KEY (`versionid`))
  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

  $DB->query("CREATE TABLE IF NOT EXISTS {p".$pluginid."_sections} (
  sectionid          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  parentid           INT(10) UNSIGNED NOT NULL DEFAULT 1,
  activated          TINYINT          NOT NULL default 0,
  name               VARCHAR(255)     collate utf8_unicode_ci NOT NULL default '',
  description        TEXT             collate utf8_unicode_ci NOT NULL,
  sorting            VARCHAR(64)      collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY (sectionid))
  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

  $DB->ignore_error = true;
  $DB->query("INSERT INTO {p".$pluginid."_sections} (sectionid, parentid, activated, name, description, sorting)
              VALUES (1, 0, 1, 'Downloads', '', 'Newest First')");
  $DB->ignore_error = false;

  $DB->query("CREATE TABLE IF NOT EXISTS {p".$pluginid."_file_downloads} (
  fileid             INT              NOT NULL ,
  username           VARCHAR(255)     collate utf8_unicode_ci NOT NULL,
  ipaddress          VARCHAR(40)      NOT NULL,
  downloaddate       INT              NOT NULL,
  KEY `dnl_by_username` (`username`),
  KEY `dnl_by_fileid`   (`fileid`))
  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

  $DB->query("CREATE TABLE IF NOT EXISTS {p".$pluginid."_file_sections} (
  fileid             INT              NOT NULL,
  sectionid          INT              NOT NULL,
  PRIMARY KEY (`fileid`, `sectionid`))
  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

  // --------------------------------------------------------------------------
  // --------------------------------------------------------------------------

  // ALWAYS call this at it must include all settings and (admin-)phrases!!!
  dlm_CheckPhrasesAndSettings();

  // Call available upgrades to keep up-to-date:
  dlm_Upgrade200();

  //----------------------------
  // Install embedded demo file
  //----------------------------
  $DB->query("INSERT INTO {p".$pluginid."_files}
   (`fileid`, `activated`, `uniqueid`, `currentversionid`, `title`, `author`,
    `description`, `image`, `thumbnail`, `dateadded`, `dateupdated`, `downloadcount`,
    `datestart`, `dateend`, `maxdownloads`, `maxtype`, `licensed`, `standalone`,
    `access_groupids`, `access_userids`, `tags`, `embedded_in_list`, `embedded_in_details`)
    VALUES (null, 1, '', 1, 'Kuroshio Sea (Vimeo)', 'John Rowlinson',
    'Beautifully filmed Kuroshio Sea Aquarium (Japan) by John Rowlinson.', '', '',
    1283724000, 0, 0, 1283755121, 0, 0, 0, 0, 0,
    '', '', '', 0, 1)");
  $fid = $DB->insert_id();

  //SD370: store tags in core table
  require_once(SD_INCLUDE_PATH.'class_sd_tags.php');
  SD_Tags::StorePluginTags($pluginid, $fid, 'Kuroshio,Rawlinson,Video,Vimeo');

  $DB->query("INSERT INTO {p".$pluginid."_file_versions} (`versionid`, `fileid`, `version`, `file`, `filename`,
    `storedfilename`, `filesize`, `filetype`, `datecreated`, `is_embeddable_media`, `lyrics`)
    VALUES (NULL, ".$fid.", '1.0.0', '', 'http://www.vimeo.com/5606758', '', '0', 'FTP', 1283755143, 1, '')");
  $vid = $DB->insert_id();

  $DB->query('UPDATE {p'.$pluginid.'_files} SET currentversionid = %d where fileid = %d',$vid,$fid);

  $DB->query('INSERT INTO {p'.$pluginid.'_file_sections} (`fileid`, `sectionid`) VALUES (%d, 1)',$fid);

  dlm_Upgrade208();

  $DB->ignore_error = true;
  $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_file_versions','fileid');
  $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_files','datestart');
  $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_files','dateend');
  $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_files','author');
  $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_files','currentversionid');
  $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections','parentid');
  $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections','name');
  $DB->ignore_error = false;

  dlm_Upgrade230(); #2013-10-05
  dlm_Upgrade240(); #2014-01-19

} // *** install ***


// ############################## UPGRADE PLUGIN ###############################

if(!empty($currentversion) && ($installtype == 'upgrade'))
{
  // --------------------------------------------------------------
  // *** IMPORTANT: ***
  // place ALL new settings, adminphrases and phrases in function
  // dlm_CheckPhrasesAndSettings!!!
  // --------------------------------------------------------------
  if(empty($pluginid)) return false;

  // upgrade to 2.0.0
  if(version_compare($currentversion,'2.0.1', '<'))
  {
    dlm_Upgrade200();
    UpdatePluginVersion($pluginid, '2.0.1');
    $currentversion = '2.0.1';
  }

  // ALWAYS call this here to include all settings and (admin-)phrases!!!
  dlm_CheckPhrasesAndSettings();

  if(($currentversion == '2.0.1') || ($currentversion == '2.0.2') || ($currentversion == '2.0.3'))
  {
    // Update plugin's core settings:
    if(version_compare($mainsettings['sdversion'], '3.4.1', 'ge') && $DB->column_exists(PRGM_TABLE_PREFIX.'plugins','base_plugin'))
    {
      $DB->query("UPDATE {plugins} SET base_plugin = 'Download Manager' WHERE pluginid = %d", $pluginid);
    }
    // Update plugin's tables:
    $DB->ignore_error = true;
    $DB->query("ALTER TABLE {p".$pluginid."_files} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci");
    $DB->query("ALTER TABLE {p".$pluginid."_file_downloads} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci");
    $DB->query("ALTER TABLE {p".$pluginid."_file_sections} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci");
    $DB->query("ALTER TABLE {p".$pluginid."_file_versions} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci");
    $DB->query("ALTER TABLE {p".$pluginid."_ratings} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci");
    $DB->query("DROP TABLE IF EXISTS {p".$pluginid."_ratings}");
    $DB->ignore_error = false;

    $DB->query("UPDATE {plugins} SET name = '%s', settings = %d, pluginpath = '%s', settingspath = '%s', authorname = '%s'
               WHERE pluginid = $pluginid",
               $pluginname.' ('.$pluginid.')', $pluginsettings, $pluginpath, $settingspath, $DB->escape_string($authorname));

    UpdatePluginVersion($pluginid, '2.0.4');
    $currentversion = '2.0.4';
  }

  if(version_compare($currentversion, '2.0.8', '<'))
  {
    dlm_Upgrade208();

    UpdatePluginVersion($pluginid, '2.0.8');
    $currentversion = '2.0.8';
  }

  if($currentversion == '2.0.8')
  {
    // Enable reporting support (comments):
    if($DB->column_exists(PRGM_TABLE_PREFIX.'plugins','reporting'))
    {
      $DB->query('UPDATE {plugins} SET reporting = 1 WHERE pluginid = %d',$pluginid);
    }

    UpdatePluginVersion($pluginid, '2.1.0');
    $currentversion = '2.1.0';
  }

  if(version_compare($currentversion, '2.2.0', '<'))
  {
    $DB->query("UPDATE {adminphrases} SET defaultphrase = 'Display a tag cloud at bottom of page (default: Yes)?'".
               " WHERE pluginid = %d AND varname = 'display_tag_cloud_descr'", $pluginid);
    UpdatePluginVersion($pluginid, '2.2.0');
    $currentversion = '2.2.0';
  }

  if(version_compare($currentversion, '2.3.0', '<')) #2013-10-05
  {
    // Convert old plugin tags to use core tags table
    dlm_Upgrade230();

    UpdatePluginVersion($pluginid, '2.3.0');
    $currentversion = '2.3.0';
  }

  if(version_compare($currentversion, '2.4.0', '<')) #2014-01-19
  {
    // Sections-option to count files into upper section
    dlm_Upgrade240();

    UpdatePluginVersion($pluginid, '2.4.0');
    $currentversion = '2.4.0';
  }
}

// ########################### UNINSTALL PLUGIN ###############################

if($installtype == 'uninstall')
{
  dlm_DropTables(empty($pluginid)?(int)$uniqueid:(int)$pluginid);
}
