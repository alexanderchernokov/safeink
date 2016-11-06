<?php
if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

// ####################### DETERMINE CURRENT DIRECTORY ########################
$plugin_folder  = sd_GetCurrentFolder(__FILE__);

// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,4))
{
  if(strlen($installtype))
  {
    echo 'Media Gallery ('.$plugin_folder.'): you need to upgrade to '.PRGM_NAME.' v3.4+ to use this plugin.<br />';
  }
  return false;
}

// ############################################################################
// PLUGIN INFORMATION
// ############################################################################
// Do NOT set $pluginid or $pluginid here!

$base_plugin = $pluginname = 'Media Gallery';
$version        = '3.7.0';
$pluginpath     = $plugin_folder.'/media_gallery.php';
$settingspath   = $plugin_folder.'/gallery_settings.php';
$authorname     = 'subdreamer_web';
$authorlink     = 'http://www.subdreamer.com';
$pluginsettings = '27';

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
  if($pluginid==17) $pluginname = 'Image Gallery';
}
else
// 2. Check if a plugin with the same name is already installed
if($inst_check_id = GetPluginID($pluginname))
{
  $pluginname .= ' ('.$plugin_folder.')';
}
$authorname .= "<br />Plugin folder: '<strong>plugins/$plugin_folder</strong>'".(empty($pluginid)?'':" ($pluginid)");

/*
SD344: check, if this upgrade is a fresh upgrade of the Image Gallery plugin (ID 17),
i.e. the Media Gallery files have been copied into p17_image_gallery and there
are most likely already images existing.
In that case switch the plugin version so that all upgrade steps are performed
(by default existing tables are not recreated).
*/
if(!empty($pluginid) && ($pluginid==17) && (empty($installtype) || ($installtype == 'upgrade')) &&
   ($row = $DB->query_first('SELECT * FROM {plugins} WHERE pluginid = 17')))
{
  // Does plugin not yet point to Media Gallery settings file?
  if(substr($row['settingspath'],-20)!='gallery_settings.php')
  {
    // Now we saw, that we're upgrading the Image Gallery to the Media Gallery
    // because the pluginid is 17. Also, the settingspath is NOT yet set to
    // the Media Gallery's file, so it must be the first time this upgrade runs.
    if(empty($installtype))
    {
      $authorname .= '<br /><span style="color:red;font-weight:bold">PLUGIN UPGRADE REQUIRED!</span>';
    }
    $currentversion = '3.0.0'; // Trigger update to be shown
  }
}

if(empty($installtype))
{
  // Nothing else to do, so return
  return true;
}

if(!function_exists('MediaGalleryUpgrade330'))
{
function MediaGalleryUpgrade330()
{
  global $DB, $pluginid, $plugin_folder;

  if(empty($pluginid)) return;

  // Fix older phrases
  $DB->query("UPDATE {adminphrases} SET varname = varname || 'r' WHERE pluginid = %d AND varname LIKE '%\_desc'", $pluginid);
  $DB->query("DELETE FROM {adminphrases} WHERE pluginid = %d AND varname = '0'", $pluginid);
  $DB->query("DELETE FROM {adminphrases} WHERE pluginid = %d AND varname LIKE 'image_display_mode%'", $pluginid);

  InsertAdminPhrase($pluginid, 'admin_options',          'Admin Options');
  InsertAdminPhrase($pluginid, 'media_gallery_settings', 'Media Gallery Settings');
  InsertAdminPhrase($pluginid, 'midsize_settings',       'Midsize Settings');
  InsertAdminPhrase($pluginid, 'sections_settings',      'Sections Settings');
  InsertAdminPhrase($pluginid, 'thumbnail_settings',     'Thumbnail Settings');
  InsertAdminPhrase($pluginid, 'upload_settings',        'Upload Settings');

  InsertPhrase($pluginid, 'approve', 'Approve');
  InsertPhrase($pluginid, 'click_to_enlarge', '[Click image to enlarge]');
  InsertPhrase($pluginid, 'comments', 'Comments');
  InsertPhrase($pluginid, 'delete', 'Delete');
  InsertPhrase($pluginid, 'description', 'Description:');
  InsertPhrase($pluginid, 'enter_author', 'Please enter the author\'s name.');
  InsertPhrase($pluginid, 'enter_description', 'Please enter a description.');
  InsertPhrase($pluginid, 'enter_title', 'Please enter a title.');
  InsertPhrase($pluginid, 'go', 'Go');
  InsertPhrase($pluginid, 'image_submit_hint1', 'Uploaded images may have one of these file extensions: #extensions#');
  InsertPhrase($pluginid, 'image_submit_hint2', 'The maximum attached file size is: #size#.');
  InsertPhrase($pluginid, 'image_title', 'Title:');
  InsertPhrase($pluginid, 'imagesize_error', 'No image uploaded or image filesize is too large.');
  InsertPhrase($pluginid, 'invalid_image_ext_error', 'The uploaded file\'s extension is not allowed!');
  InsertPhrase($pluginid, 'invalid_image_type', 'The uploaded file is not a supported image type!');
  InsertPhrase($pluginid, 'invalid_image_upload', 'Invalid image upload!');
  InsertPhrase($pluginid, 'invalid_thumb_type', 'Invalid thumbnail type!');
  InsertPhrase($pluginid, 'invalid_thumb_upload', 'Invalid thumbnail upload!');
  InsertPhrase($pluginid, 'jump_to', 'Jump To...');
  InsertPhrase($pluginid, 'lb_close', 'Close X');
  InsertPhrase($pluginid, 'lb_closeInfo', 'You can also click anywhere outside the image to close.');
  InsertPhrase($pluginid, 'lb_help_close', 'Click to close.');
  InsertPhrase($pluginid, 'lb_image', 'Image');
  InsertPhrase($pluginid, 'lb_of', 'of');
  InsertPhrase($pluginid, 'midsize_image_created', 'MidSize Image created.');
  InsertPhrase($pluginid, 'midsize_image_error', 'MidSize Image Error:',true);
  InsertPhrase($pluginid, 'no_gif_support', 'Images with the .gif extension are not supported.');
  InsertPhrase($pluginid, 'no_gd_no_gif_support', '<strong>Images with the .gif extension are not supported when using GD1 or GD2 to create thumbnails.<br />
    If you wish to use .gif images, then set "Image Resizing" to "Submit Thumbnails" in the plugin settings.</strong>');
  InsertPhrase($pluginid, 'notify_email_author', 'Author');
  InsertPhrase($pluginid, 'notify_email_description', 'Description');
  InsertPhrase($pluginid, 'notify_email_from', 'Media Gallery Plugin');
  InsertPhrase($pluginid, 'notify_email_title', 'Title');
  InsertPhrase($pluginid, 'reset_form', 'Reset');
  InsertPhrase($pluginid, 'search', 'Search');
  InsertPhrase($pluginid, 'search_results', 'Search Results');
  InsertPhrase($pluginid, 'sections', 'Sections:');
  InsertPhrase($pluginid, 'select_image', 'Please select an image.');
  InsertPhrase($pluginid, 'select_thumbnail', 'Please select a thumbnail.');
  InsertPhrase($pluginid, 'submitted_by', 'Submitted by:');
  InsertPhrase($pluginid, 'tags', 'Tags:');
  InsertPhrase($pluginid, 'thumbnail', 'Thumbnail:');
  InsertPhrase($pluginid, 'thumbnail_created', 'Thumbnail created.');
  InsertPhrase($pluginid, 'thumbnail_error', 'Thumbnail Error:');
  InsertPhrase($pluginid, 'thumbnail_regen_error', 'Thumbnail creation failed due to an error!');
  InsertPhrase($pluginid, 'thumbsize_error', 'No thumbnail uploaded or thumbnail filesize is too large.');
  InsertPhrase($pluginid, 'unapprove', 'Unapprove');
  InsertPhrase($pluginid, 'views', 'Views');
  InsertPhrase($pluginid, 'watchdog_by_user', 'by User');
  InsertPhrase($pluginid, 'watchdog_upload_by', 'Possible malicious image upload by ');
  InsertPhrase($pluginid, 'your_name', 'Your Name:');

  InsertAdminPhrase($pluginid, 'alpha_az', 'Alphabetically A-Z');
  InsertAdminPhrase($pluginid, 'alpha_za', 'Alphabetically Z-A');
  InsertAdminPhrase($pluginid, 'approved', 'Approved');
  InsertAdminPhrase($pluginid, 'apply_filter', 'Apply Filter');
  InsertAdminPhrase($pluginid, 'author', 'Author');
  InsertAdminPhrase($pluginid, 'author_name_az', 'Author Name A-Z');
  InsertAdminPhrase($pluginid, 'author_name_za', 'Author Name Z-A');
  InsertAdminPhrase($pluginid, 'clear_filter', 'Clear Filter');
  InsertAdminPhrase($pluginid, 'create_section', 'Create Section');
  InsertAdminPhrase($pluginid, 'default_folder', 'Default');
  InsertAdminPhrase($pluginid, 'delete', 'Delete');
  InsertAdminPhrase($pluginid, 'delete_images_in_section_hint', 'Note: this will also <strong>delete ALL</strong> related files for images assigned to this section from the server!');
  InsertAdminPhrase($pluginid, 'delete_section', 'Delete this Section?');
  InsertAdminPhrase($pluginid, 'description', 'Description:');
  InsertAdminPhrase($pluginid, 'do_not_move', 'Don\'t Move');
  InsertAdminPhrase($pluginid, 'edit_section', 'Edit Section');
  InsertAdminPhrase($pluginid, 'enable_image_resizing_required', 'Please enable option <strong>Auto Create Thumbs</strong> in the <strong>Thumbnail Settings</strong> for full operation.');
  InsertAdminPhrase($pluginid, 'errors_found', 'Errors found:');
  InsertAdminPhrase($pluginid, 'error_no_section_title', 'Please enter a section title!');
  InsertAdminPhrase($pluginid, 'error_image_copy_failed', 'Image could not be copied to designated image folder, please check folder permissions!');
  InsertAdminPhrase($pluginid, 'error_thumb_copy_failed', 'Thumbnail could not be copied to designated image folder, please check folder permissions!');
  InsertAdminPhrase($pluginid, 'filter_width', 'Image Width');
  InsertAdminPhrase($pluginid, 'limit', 'Limit');
  InsertAdminPhrase($pluginid, 'filter', 'Filter');
  InsertAdminPhrase($pluginid, 'folder', 'Folder:');
  InsertAdminPhrase($pluginid, 'folder_not_found', 'folder not found, please create it.');
  InsertAdminPhrase($pluginid, 'folder_not_writable', 'folder not writable, please CHMOD to 0777.');
  InsertAdminPhrase($pluginid, 'gd_disabled', 'GD image library is required for importing multiple images, contact your server admin.');
  InsertAdminPhrase($pluginid, 'image_imported', 'Successfully re-sized and imported.');
  InsertAdminPhrase($pluginid, 'image_not_imported', 'image was not imported.');
  InsertAdminPhrase($pluginid, 'image_section_deleted', 'Image Section Deleted');
  InsertAdminPhrase($pluginid, 'import_option_author', '<strong>Author Name:</strong> Enable the display of the author\'s name for all imported images?');
  InsertAdminPhrase($pluginid, 'import_option_comments', '<strong>Comments:</strong> Enable comments for all imported images?');
  InsertAdminPhrase($pluginid, 'import_option_keep_fullsize', '<strong>Keep original</strong> image files on server if MidSize images are used?');
  InsertAdminPhrase($pluginid, 'import_option_ratings', '<strong>Ratings:</strong> Enable ratings for all imported images?');
  InsertAdminPhrase($pluginid, 'import_image_author', 'Author of images:');
  InsertAdminPhrase($pluginid, 'import_image_count', 'Number of Images to Import from folder:');
  InsertAdminPhrase($pluginid, 'import_image_location', 'Section to import images into:');
  InsertAdminPhrase($pluginid, 'import_images', 'Import Images', 2, true);
  InsertAdminPhrase($pluginid, 'import_images_from', 'Import Images From:');
  InsertAdminPhrase($pluginid, 'import_images_from_hint', 'Note: the upload folder path must be relative to the program base folder.');
  InsertAdminPhrase($pluginid, 'import_images_hint', 'Import multiple images on the server into the gallery at once.');
  InsertAdminPhrase($pluginid, 'import_invalid_section', 'The selected section is invalid!');
  InsertAdminPhrase($pluginid, 'import_location_not_found', 'Specified import folder not found.');
  InsertAdminPhrase($pluginid, 'import_midsize_error', 'Invalid width or height for midsize images specified!');
  InsertAdminPhrase($pluginid, 'import_results', 'Batch Image Import Results');
  InsertAdminPhrase($pluginid, 'import_section_error', 'The selected section folder does not exist or is not writable, please check folder permissions!');
  InsertAdminPhrase($pluginid, 'import_thumbsize_error', 'Invalid width or height for thumbnail images specified!');
  InsertAdminPhrase($pluginid, 'import_imagesize_error', 'Image not imported because width or height exceeds 3072 pixels.');
  InsertAdminPhrase($pluginid, 'import_recheck_folder', '[Recheck folder again]');
  InsertAdminPhrase($pluginid, 'imported_count', 'Number of images imported:');
  InsertAdminPhrase($pluginid, 'midsize_settings', 'Mid-Size Settings');
  InsertAdminPhrase($pluginid, 'no_action', 'No action');
  InsertAdminPhrase($pluginid, 'reassign', 'Reassign');
  InsertAdminPhrase($pluginid, 'reassign_images', 'Reassign images to:');
  InsertAdminPhrase($pluginid, 'reassign_images_hint', 'Selecting a different section here will reassign <strong>all</strong> images of this section to the selected section without physically moving image files on the server.');
  InsertAdminPhrase($pluginid, 'message_section_added', 'Section was added successfully!');
  InsertAdminPhrase($pluginid, 'message_section_updated', 'Section was updated successfully!');
  InsertAdminPhrase($pluginid, 'move', 'Move');
  InsertAdminPhrase($pluginid, 'move_options_title', 'MOVE Options');
  InsertAdminPhrase($pluginid, 'move_images', 'Move images to folder:');
  InsertAdminPhrase($pluginid, 'move_images_hint', 'Moving images only means to assign the images to a different section. Images will NOT be physically moved on the server to a different folder!');
  InsertAdminPhrase($pluginid, 'move_imported_images_descr', 'Move Imported Images: Move all valid Images? Otherwise Images are just copied and original Images stay.');
  InsertAdminPhrase($pluginid, 'newest_first', 'Newest First');
  InsertAdminPhrase($pluginid, 'no_thumbnail', 'No Thumbnail');
  InsertAdminPhrase($pluginid, 'no_images_found', 'No images found.');
  InsertAdminPhrase($pluginid, 'no_parent_for_root', 'Root section can not have a parent.');
  InsertAdminPhrase($pluginid, 'offline', 'Offline');
  InsertAdminPhrase($pluginid, 'offline_images', 'Offline Images');
  InsertAdminPhrase($pluginid, 'oldest_first', 'Oldest First');
  InsertAdminPhrase($pluginid, 'online', 'Online');
  InsertAdminPhrase($pluginid, 'options', 'Options:');
  InsertAdminPhrase($pluginid, 'organize_images', 'Organise Images');
  InsertAdminPhrase($pluginid, 'parent_section', 'Parent Section:');
  InsertAdminPhrase($pluginid, 'php_max_upload_size', 'Allowed file size in PHP to submit is:');
  InsertAdminPhrase($pluginid, 'preview', 'Preview');
  InsertAdminPhrase($pluginid, 'publish_descr', 'Publish: Are you ready to publish this image on your site?');
  InsertAdminPhrase($pluginid, 'regenerate_thumb', 'Regenerate Thumbnail:');
  InsertAdminPhrase($pluginid, 'return_to_image_gallery', 'Return to the Media Gallery');
  InsertAdminPhrase($pluginid, 'section', 'Section');
  InsertAdminPhrase($pluginid, 'sections', 'Sections');
  InsertAdminPhrase($pluginid, 'section_sort_order_title', 'Sort by');
  InsertAdminPhrase($pluginid, 'section_created', 'Section Created');
  InsertAdminPhrase($pluginid, 'section_image', 'Section Image:');
  InsertAdminPhrase($pluginid, 'section_images_per_page', 'Number of images displayed per page (0-999)?');
  InsertAdminPhrase($pluginid, 'section_images_per_page_hint', '<strong>Default: 0</strong> to use the default value from the settings.');
  InsertAdminPhrase($pluginid, 'section_images_per_row', 'Number of section images displayed per row (0-99)?');
  InsertAdminPhrase($pluginid, 'section_images_per_row_hint', '<strong>Default: 0</strong> to use the default value from the settings.<br /><strong>Notes:</strong> After this number of images a new line will be started, regardless of space allowing for more.<br />
  However, depending on the CSS styles the amount of images fitting a row may differ from this value.');
  InsertAdminPhrase($pluginid, 'section_upload_folder', 'Section Upload Folder:');
  InsertAdminPhrase($pluginid, 'section_upload_folder_hint', 'The selected folder is used when importing new images or adding a new image.<br />
  By default images are stored into the "images" folder of the plugin.<br />
  Any folder - once selected - <strong>must</strong> be writable (set permissions to 0777 in FTP) or uploads to it will fail.');
  InsertAdminPhrase($pluginid, 'sort_images_by', 'Sort Images by:');
  InsertAdminPhrase($pluginid, 'section_batch_operations', '<strong>Batch Operations</strong>');
  InsertAdminPhrase($pluginid, 'section_batch_operations_hint', 'All options displayed here will <strong>update all</strong> images within this section:');
  InsertAdminPhrase($pluginid, 'section_batch_add_tags', 'Add tags?');
  InsertAdminPhrase($pluginid, 'section_batch_set_allowcomments', 'Set option to allow comments to');
  InsertAdminPhrase($pluginid, 'section_batch_set_displayauthor', 'Set option to display the author to');
  InsertAdminPhrase($pluginid, 'section_batch_set_ratings', 'Set option to allow ratings to');
  InsertAdminPhrase($pluginid, 'status', 'Status');
  InsertAdminPhrase($pluginid, 'tags_title', 'Tags');
  InsertAdminPhrase($pluginid, 'thumb_created_automatically', 'Thumbnail will be automatically created.');
  InsertAdminPhrase($pluginid, 'thumb_error_image_not_imported', 'Thumbnail creation failed, image not imported.');
  InsertAdminPhrase($pluginid, 'thumbnail', 'Thumbnail:');
  InsertAdminPhrase($pluginid, 'thumb_regenerate', 'Thumb regenerate');
  InsertAdminPhrase($pluginid, 'thumbnail_settings', 'Thumbnail Settings');
  InsertAdminPhrase($pluginid, 'title', 'Title:');
  InsertAdminPhrase($pluginid, 'unapproved', 'Unapproved');
  InsertAdminPhrase($pluginid, 'update_section', 'Update Section');
  InsertAdminPhrase($pluginid, 'uploaded', 'Uploaded');
  InsertAdminPhrase($pluginid, 'uploadeded_by', 'Uploaded by');
  InsertAdminPhrase($pluginid, 'view_settings', 'View Settings');
  InsertAdminPhrase($pluginid, 'delete_failed', 'Image deletion failed, please check write permissions of the images folder!');
  InsertAdminPhrase($pluginid, 'untitled_section', 'Untitled Section');
  DeleteAdminPhrase($pluginid, 'enable_comments_desc', 2);
  DeleteAdminPhrase($pluginid, '0', 2);

  InsertPluginSetting($pluginid, 'admin_options', 'Display Approve Link',   'Display <strong>approve</strong> link on frontpage for administrators and moderators (default: no)?<br /><strong>Note</strong>: when set to <strong>yes</strong>, this will have the plugin to also display offline images!', 'yesno', '0', 10);
  InsertPluginSetting($pluginid, 'admin_options', 'Display Unapprove Link', 'Display <strong>unapprove</strong> link on frontpage for administrators and moderators (default: yes)?', 'yesno', '1', 20);
  InsertPluginSetting($pluginid, 'admin_options', 'Display Delete Link',    'Display <strong>delete</strong> link on frontpage for administrators and moderators (default: yes)?', 'yesno', '1', 30);
  InsertPluginSetting($pluginid, 'admin_options', 'Log Moderation Actions', 'Log all approve/unapprove/delete actions to the System Log (default: yes)?', 'yesno', '1', 40);

  $DB->query("UPDATE ".PRGM_TABLE_PREFIX."adminphrases SET varname = 'default_'+varname WHERE pluginid = ".$pluginid.
             " AND ((varname LIKE 'images_per_row%') OR (varname LIKE 'images_per_page%'))");
  $DB->query("UPDATE ".PRGM_TABLE_PREFIX."pluginsettings SET groupname = 'sections_settings', title = 'default_images_per_page' WHERE pluginid = ".$pluginid.
             " AND groupname = 'image_gallery_settings' and title = 'images_per_page'");
  $DB->query("UPDATE ".PRGM_TABLE_PREFIX."pluginsettings SET groupname = 'sections_settings', title = 'default_images_per_row' WHERE pluginid = ".$pluginid.
             " AND groupname = 'image_gallery_settings' and title = 'images_per_row'");
  InsertPluginSetting($pluginid, 'image_gallery_settings', 'Image Display Mode',      'Mode for displaying a single image?',
    "select:\r\n0|Integrated (Default)\r\n1|Popup (new window with comments)\r\n2|Ceebox (no comments)\r\n3|Fancybox (no comments)\r\n4|Galleria (no comments)\r\n5|Slide Rotator (no comments)\r\n6|mb.Gallery (no comments)\r\n7|jqGalViewII (no comments)", '3', 10, true);
  InsertPluginSetting($pluginid, 'media_gallery_settings', 'Save FullSize Images',    'When an image is uploaded should we keep the fullsize image?<br />If this is set to <strong>No</strong> and \'Create MidSize Images\' is set to <strong>Yes</strong>, this gives you ability to restrict the maximum size of the image. In this case the original image will be deleted leaving you with the fixed size MidSize image. Only active when \'Create MidSize Images\' is enabled (default: Yes):', 'yesno', '1', 20);
  InsertPluginSetting($pluginid, 'media_gallery_settings', 'Display Jump Menu',       'Display the Jump Menu navigation?', 'yesno', '1', 60);
  InsertPluginSetting($pluginid, 'media_gallery_settings', 'Display Tags',            'Display a Tags listing?', 'yesno', '1', 75);
  InsertPluginSetting($pluginid, 'media_gallery_settings', 'Enable Comments',         'Enable display of comments for images plugin-wide (default: Yes)?<br />If disabled, no comments will be displayed at all.', 'yesno', '1', 90);
  InsertPluginSetting($pluginid, 'media_gallery_settings', 'Section Sort Order',      'Default sorting of section names (also used for search results):',
    "select:\r\n0|Newest First\r\n1|Oldest First\r\n2|Alphabetically A-Z\r\n3|Alphabetically Z-A", '2', 15, true);
  InsertPluginSetting($pluginid, 'media_gallery_settings', 'Show Comments Expanded',  'Display the images comments expanded (yes) or collapsed (no)?', 'yesno', '1', 130);
  InsertPluginSetting($pluginid, 'media_gallery_settings', 'Integrated View Max Width', 'Max. display width of image when single image is viewed in the Integrated display mode?<br />This allows to size down large images to fit the layout design.', 'text', '400px', 102, true);
  InsertPluginSetting($pluginid, 'media_gallery_settings', 'Integrated View Max Height', 'Max. display height of image when single image is viewed in the Integrated display mode?<br />This allows to size down large images to fit the layout design.', 'text', '300px', 104, true);
  InsertPluginSetting($pluginid, 'media_gallery_settings', 'Image Navigation Links',  'Position of the previous and next image links (default: Top)?<br />Note: this setting will always be used in the
    integrated, single-image display mode. In the section view mode this option is only used if the option <strong>Pagination Links</strong> is set to <strong>None</strong>.',
    "select:\r\n0|Both\r\n1|Top\r\n2|Bottom\r\n3|None", '1', 140, true);
  InsertPluginSetting($pluginid, 'media_gallery_settings', 'Pagination Links',    'Display list of page links when viewing a section (default: Bottom)?<br />To display only simple links for previous/next page, set this option to <strong>None</strong>, so that the <strong>Image Navigation Links</strong> option will be used.',
    "select:\r\n0|Both\r\n1|Top\r\n2|Bottom\r\n3|None", '2', 150, true);

  InsertPluginSetting($pluginid, 'upload_settings', 'Allow Root Section Upload',  'Allow users to upload images to the root section?', 'yesno', '0', 10);
  InsertPluginSetting($pluginid, 'upload_settings', 'Allowed Image Types',        'List of allowed image types for upload (comma separated)?<br />E.g.: <strong>gif, jpg, jpeg, png<strong>', 'text', 'gif, jpg, jpeg, png', 20);
  InsertPluginSetting($pluginid, 'upload_settings', 'Auto Approve Images',        'Automatically approve user submitted images (default: No)?', 'yesno', '0', 30);
  InsertPluginSetting($pluginid, 'upload_settings', 'Max Upload Size',            'Maximum image filesize for user uploads in Bytes?<br />Default: <strong>1048576</strong> (= 1MB)', 'text', '1048576', 40);
  InsertPluginSetting($pluginid, 'upload_settings', 'Image Notification',         'This email address will receive an email message when a new image is submitted:<br />Separate multiple addresses with commas.', 'text', '', 50, true);
  InsertPluginSetting($pluginid, 'upload_settings', 'Image Author Required',      'Is the image <strong>Author</strong> required for image uploads (default: Yes)?', 'yesno', '1', 60);
  InsertPluginSetting($pluginid, 'upload_settings', 'Image Description Required', 'Is the image <strong>Description</strong> required for image uploads (default: Yes)?', 'yesno', '1', 70);
  InsertPluginSetting($pluginid, 'upload_settings', 'Image Title Required',       'Is the image <strong>Title</strong> required for image uploads (default: Yes)?', 'yesno', '1', 80);
  InsertPluginSetting($pluginid, 'upload_settings', 'Image Security Check',       'Perform extra security checks on uploaded images/thumbnails (default: Yes)<br />This is highly recommended to avoid malicious attacks!', 'yesno', '1', 90);
  InsertPluginSetting($pluginid, 'upload_settings', 'Require VVC Code',           'Require users to enter a Visual Verification Code to submit an image (default: Yes)?', 'yesno', '1', 100);

  InsertPluginSetting($pluginid, 'midsize_settings', 'Create MidSize Images',     'Automatically create midsize images (default: No)?<br />This allows you to show a larger (than thumbnail) image before the full size image is shown:', 'yesno', '0', 10);
  InsertPluginSetting($pluginid, 'midsize_settings', 'Max MidSize Width',         'Enter the max width that the midsize image should be resized to (in pixels):', 'text', '400', 30);
  InsertPluginSetting($pluginid, 'midsize_settings', 'Max MidSize Height',        'Enter the max height that the midsize image should be resized to (in pixels):', 'text', '400', 40);
  InsertPluginSetting($pluginid, 'midsize_settings', 'Square Off MidSize',        'When automatically creating midsize images, should the image be scaled so that it is square (default: No)?', 'yesno', '0', 50);

  InsertPluginSetting($pluginid, 'thumbnail_settings', 'Auto Create Thumbs',      'Automatically create thumbnail from uploaded image (default: Yes)?', 'yesno', '1', 10);
  InsertPluginSetting($pluginid, 'thumbnail_settings', 'Max Thumb Width',         'Enter the max width that a thumbnail should be resized to (in pixels):', 'text', '100', 20);
  InsertPluginSetting($pluginid, 'thumbnail_settings', 'Max Thumb Height',        'Enter the max height that a thumbnail should be resized to (in pixels):', 'text', '100', 30);
  InsertPluginSetting($pluginid, 'thumbnail_settings', 'Square Off Thumbs',       'When automatically creating thumbnails, should the image be scaled so that it is square (default: No)?', 'yesno', '0', 40);

  InsertPluginSetting($pluginid, 'sections_settings', 'Default Images Per Row',      'Default number of images per <strong>row</strong> used by new sections or sections with 0 entered (depends also on Display Mode):', 'text', '3', 5, true);
  InsertPluginSetting($pluginid, 'sections_settings', 'Default Images Per Page',     'Default number of images per <strong>page</strong> used by new sections or sections with 0 entered (depends also on Display Mode):', 'text', '9', 8, true);
  InsertPluginSetting($pluginid, 'sections_settings', 'Display Section Image Count', 'Display the image count in each section (default: Yes)?<br />Disable this option if you have many images and your media gallery is loading slowly.', 'yesno', '1', 10);
  InsertPluginSetting($pluginid, 'sections_settings', 'Display Sections as Images',  'Display sections as images instead of text?', 'yesno', '1', 20);
  InsertPluginSetting($pluginid, 'sections_settings', 'Section Images per Row',      'Enter the number of section images to be displayed per row (default: 5):<br />Note: not every display mode may use this!', 'text', '5', 30);

  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_images',   'px_width',      'BIGINT(10)', 'UNSIGNED NOT NULL DEFAULT 0');
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_images',   'px_height',     'BIGINT(10)', 'UNSIGNED NOT NULL DEFAULT 0');
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_images',   'folder',        'VARCHAR(254) collate utf8_unicode_ci', 'NULL');
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_images',   'allow_ratings', 'TINYINT(1)', 'UNSIGNED NOT NULL DEFAULT 1');
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections', 'folder',        'VARCHAR(254) COLLATE utf8_unicode_ci', 'NULL');
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections', 'display_mode',  'INT(4)',     'UNSIGNED NOT NULL DEFAULT 0');
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections', 'owner_id',      'BIGINT(11)', 'UNSIGNED NOT NULL DEFAULT 0');
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections', 'display_author',     'TINYINT(1)', 'UNSIGNED NOT NULL DEFAULT 0');
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections', 'display_comments',   'TINYINT(1)', 'UNSIGNED NOT NULL DEFAULT 0');
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections', 'display_ratings',    'TINYINT(1)', 'UNSIGNED NOT NULL DEFAULT 0');
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections', 'display_view_counts','TINYINT(1)', 'UNSIGNED NOT NULL DEFAULT 0');
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections', 'images_per_page',    'SMALLINT(4)', 'UNSIGNED NOT NULL DEFAULT 0');
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections', 'images_per_row',     'SMALLINT(4)', 'UNSIGNED NOT NULL DEFAULT 0');

  $DB->query('DROP TABLE IF EXISTS '.PRGM_TABLE_PREFIX.'image_viewers');
  $DB->query('CREATE TABLE IF NOT EXISTS '.PRGM_TABLE_PREFIX."image_viewers (
  id            INT(6)        NOT NULL AUTO_INCREMENT,
  viewer_id     INT(6)        NOT NULL DEFAULT '0',
  is_active     tinyint(4)    NOT NULL DEFAULT '0',
  viewer_title  varchar(50)   COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  js_files      text          COLLATE utf8_unicode_ci NOT NULL,
  css_files     text          COLLATE utf8_unicode_ci NOT NULL,
  page_js       text          COLLATE utf8_unicode_ci,
  tmpl_open     text          COLLATE utf8_unicode_ci,
  tmpl_entry    text          COLLATE utf8_unicode_ci,
  tmpl_close    text          COLLATE utf8_unicode_ci,
  width_embed   INT(4)        NOT NULL DEFAULT 0,
  height_embed  INT(4)        NOT NULL DEFAULT 0,
  width_thick   INT(4)        NOT NULL DEFAULT 0,
  height_thick  INT(4)        NOT NULL DEFAULT 0,
  width_win     INT(4)        NOT NULL DEFAULT 0,
  height_win    INT(4)        NOT NULL DEFAULT 0,
  viewer_image  varchar(50)   COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  viewer_class  varchar(30)   COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  supports_comments TINYINT(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY viewer_id (viewer_id)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

  // Display Mode: 0 - Integrated
  if(!$DB->query_first('SELECT 1 FROM {image_viewers} WHERE viewer_id = 0'))
  $DB->query("INSERT INTO {image_viewers} (`id`, `viewer_id`, `is_active`, `viewer_title`, `js_files`, `css_files`, `page_js`, `tmpl_open`, `tmpl_entry`, `tmpl_close`, `width_embed`, `height_embed`, `width_thick`, `height_thick`, `width_win`, `height_win`, `viewer_image`, `viewer_class`, `supports_comments`) VALUES
  (NULL, '0', '1', 'Simple (with comments)', 'fancybox/jquery.easing-1.3.pack.js
fancybox/jquery.fancybox-1.3.4.pack.js', 'fancybox/jquery.fancybox-1.3.4.css',
'<script type=\"text/javascript\">
//<![CDATA[
jQuery(document).ready(function(){
  jQuery(\"a[rel*=fancybox-p[[\$pluginid]]]\").fancybox({
    ''titlePosition''   : ''inside'',
    ''transitionIn''    : ''elastic'',
    ''transitionOut''   : ''elastic'',
    ''easingIn''        : ''swing'',
    ''easingOut''       : ''swing'',
    ''overlayOpacity''  : 0.8,
    ''overlayColor''    : ''#000''
  });
});
//]]>
</script>
', '<ul class=\"galleryimages\">',
'<li><div class=\"image_title\">[[\$title]]</div><div class=\"thumb_img\"><a href=\"[[\$imagepage]]\"><img alt=\"\" src=\"[[\$thumburl]]\" title=\"[[\$title]]\" /></a>
<p class=\"p[[\$pluginid]]_p\">[[\$info]]</p></li>', '</ul>', '500', '400', '500', '400', '800', '600', '', '', 1)");

  // Display Mode: 1 - Popup
  if(!$DB->query_first("SELECT 1 FROM {image_viewers} WHERE viewer_id = 1"))
  $DB->query("INSERT INTO {image_viewers} (`id`, `viewer_id`, `is_active`, `viewer_title`, `js_files`, `css_files`, `page_js`, `tmpl_open`, `tmpl_entry`, `tmpl_close`, `width_embed`, `height_embed`, `width_thick`, `height_thick`, `width_win`, `height_win`, `viewer_image`, `viewer_class`, `supports_comments`) VALUES
  (NULL, '1', '1', 'Popup (with comments)', '', '', '', '<ul class=\"galleryimages\">',
'<li><div class=\"image_title\">[[\$title]]</div><div class=\"thumb_img\"><a href=\"#\" onclick=\"window.open(''[[\$popupurl]]'', '''', ''width=[[\$popupwidth]],height=[[\$popupheight]],directories=no,location=no,menubar=no,scrollbars=yes,status=no,toolbar=no,resizable=yes''); return false;\" target=\"_blank\"><img alt=\"\" src=\"[[\$thumburl]]\" title=\"[[\$title]]\" />
<p class=\"p[[\$pluginid]]_p\">[[\$info]]</p></li>', '</ul>', '0', '0', '0', '0', '1280', '1024', '', '', 1)");

  // Display Mode: 2 - Ceebox
  if(!$DB->query_first("SELECT 1 FROM {image_viewers} WHERE viewer_id = 2"))
  $DB->query("INSERT INTO {image_viewers} (`id`, `viewer_id`, `is_active`, `viewer_title`, `js_files`, `css_files`, `page_js`, `tmpl_open`, `tmpl_entry`, `tmpl_close`, `width_embed`, `height_embed`, `width_thick`, `height_thick`, `width_win`, `height_win`, `viewer_image`, `viewer_class`, `supports_comments`) VALUES
  (NULL, '2', '1', 'Ceebox', 'jquery.ceebox-min.js', 'css/ceebox.css',
'<script type=\"text/javascript\">
//<![CDATA[
jQuery(document).ready(function(){
  jQuery(\"a.gallerybox-p[[\$pluginid]]\").ceebox({
    animSpeed: ''fast'',
    borderWidth: ''2px'',
    overlayOpacity: 0.7,
    html: true,
    htmlGallery: true,
    imageGallery: true,
    margin: ''100'',
    padding: ''14'',
    titles: true,
    itemCaption: '''',
    ofCaption: '' / ''
  });
});
//]]>
</script>
', '<ul class=\"galleryimages\">',
'<li><div class=\"image_title\">[[\$title]]</div><div class=\"thumb_img\"><a class=\"gallerybox-p[[\$pluginid]]\" href=\"[[\$imageurl]]\" title=\"[[\$title]]\"><img alt=\"\" src=\"[[\$thumburl]]\" /></a>
<p class=\"p[[\$pluginid]]_p\">[[\$info]]</p></li>', '</ul>', '500', '400', '1025', '768', '1024', '768', '', '', 0)");

  // Display Mode: 3 - Fancybox
  if(!$DB->query_first("SELECT 1 FROM {image_viewers} WHERE viewer_id = 3"))
  $DB->query("INSERT INTO {image_viewers} (`id`, `viewer_id`, `is_active`, `viewer_title`, `js_files`, `css_files`, `page_js`, `tmpl_open`, `tmpl_entry`, `tmpl_close`, `width_embed`, `height_embed`, `width_thick`, `height_thick`, `width_win`, `height_win`, `viewer_image`, `viewer_class`, `supports_comments`) VALUES
  (NULL, '3', '1', 'Fancybox', 'fancybox/jquery.easing-1.3.pack.js
fancybox/jquery.fancybox-1.3.4.pack.js', 'fancybox/jquery.fancybox-1.3.4.css',
'<script type=\"text/javascript\">
//<![CDATA[
jQuery(document).ready(function(){
  jQuery(\"a[rel*=fancybox-p[[\$pluginid]]]\").fancybox({
    ''titlePosition''   : ''inside'',
    ''transitionIn''    : ''elastic'',
    ''transitionOut''   : ''elastic'',
    ''easingIn''        : ''swing'',
    ''easingOut''       : ''swing'',
    ''overlayOpacity''  : 0.8,
    ''overlayColor''    : ''#000''
  });
});
//]]>
</script>
', '<ul class=\"galleryimages\">',
'<li><div class=\"image_title\">[[\$title]]</div><div class=\"thumb_img\"><a rel=\"fancybox-p[[\$pluginid]]\" href=\"[[\$imageurl]]\" title=\"[[\$title]]\"><img alt=\"\" src=\"[[\$thumburl]]\" /></a>
<p class=\"p[[\$pluginid]]_p\">[[\$info]]</p></li>', '</ul>', '500', '400', '500', '400', '800', '600', '', '', 0)");

  // Display Mode: 4 - Galleria
  if(!$DB->query_first("SELECT 1 FROM {image_viewers} WHERE viewer_id = 4"))
  $DB->query("INSERT INTO {image_viewers} (`id`, `viewer_id`, `is_active`, `viewer_title`, `js_files`, `css_files`, `page_js`, `tmpl_open`, `tmpl_entry`, `tmpl_close`, `width_embed`, `height_embed`, `width_thick`, `height_thick`, `width_win`, `height_win`, `viewer_image`, `viewer_class`, `supports_comments`) VALUES
  (NULL, '4', '1', 'Galleria', 'galleria-1.2.2.min.js', '[[pluginfolder]]/css/galleria.css',
'<script type=\"text/javascript\">Galleria.loadTheme(\"".$plugin_folder."/js/galleria.classic.js\");</script>
<script type=\"text/javascript\">
//<![CDATA[
jQuery(document).ready(function(){
  jQuery(\'ul.p[[pluginid]]_gallery_unstyled\').addClass(\'p[[pluginid]]_gallery\');
  jQuery(\'ul.p[[pluginid]]_gallery\').galleria({
    autoplay  : 5000,
    image_crop: false,
    thumb_crop: false,
    transition: \'fade\',
    height    : 600,
    width     : \'100%\',
    history   : false,
    clicknext : false,
    insert    : \'#p[[pluginid]]_main_image\'
  });
});
//]]>
</script>',
'<div class=\"p[[pluginid]]_gallery_container\"><div id=\"p[[pluginid]]_main_image\"></div><ul class=\"p[[pluginid]]_gallery_unstyled\">',
'<li><a href=\"[[\$imageurl]]\" alt=\"[[\$title]]\" title=\"[[\$title]]\"><img alt=\"\" src=\"[[\$thumburl]]\" /></a></li>',
'</ul>', '500', '400', '500', '400', '800', '600', '', '', 0)");

  // Remove obsolete columns if present:
  $DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_images',   'allowsmilies');
  $DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_images',   'tags');
  $DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections', 'tags');

  // Update plugin with new settings
  $DB->query('UPDATE {plugins} SET settings = 27 WHERE pluginid = %d',$pluginid);
  $DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."pluginsettings WHERE pluginid = ".$pluginid." AND ((groupname IN ('image_border_options','Image Border Options'))
  OR (title like 'display%image_border') OR (title IN ('show_comment_counts','Center Images','center_images')) )");
  $DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."pluginsettings WHERE pluginid = ".$pluginid."
  AND (title IN ('display_author_name', 'display_view_count', 'show_submitted_by'))");
  $DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."adminphrases WHERE pluginid = ".$pluginid." AND ((varname IN ('image_border_options','Image Border Options'))
  OR (varname LIKE 'display%image_border%') OR (adminpageid = 1) OR (varname = 'add_new_section_desc') OR (varname LIKE 'border%') OR (varname LIKE 'center_images%') )");
  $DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."adminphrases WHERE pluginid = ".$pluginid." AND
  ( (varname = 'Show Comment Counts') OR (varname LIKE 'show_comment_counts%') OR (varname LIKE 'image_option_smilies%') )");
  $DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."adminphrases WHERE pluginid = ".$pluginid." AND
  ( (varname LIKE 'display_author_name%') OR (varname LIKE 'display_view_count%') OR (varname LIKE 'show_submitted_by%') )");

} // MediaGalleryUpgrade330
} // DO NOT REMOVE!

if(!function_exists('MediaGalleryUpgrade340'))
{
function MediaGalleryUpgrade340()
{
  global $DB, $pluginid;

  if(empty($pluginid)) return;
  DeleteAdminPhrase($pluginid, 'number_of_section_images_per_row', 2);
  DeleteAdminPhrase($pluginid, 'number_of_section_images_per_row_descr', 2);
  InsertAdminPhrase($pluginid, 'section_images_per_row', 'Number of section images displayed per row (0-99)?',2,true);
  InsertAdminPhrase($pluginid, 'section_thumbnail_hint', 'Pick any image to be displayed as thumbnail for this section.<br />To hide the selected image from the regular display of images on the frontpage, set it\'s status to Offline.');

} // MediaGalleryUpgrade340
} // DO NOT REMOVE!

if(!function_exists('MediaGalleryUpgrade344'))
{
function MediaGalleryUpgrade344()
{
  global $DB, $pluginid;

  if(empty($pluginid)) return;

  InsertAdminPhrase($pluginid, 'media_gallery_settings',  'Media Gallery Settings');
  InsertAdminPhrase($pluginid, 'import_imagedim_error', 'Error: unable to determine image size (image name invalid or image width/height too large)!',2);
  InsertAdminPhrase($pluginid, 'folder', 'Folder:',2);
  InsertAdminPhrase($pluginid, 'viewing_permissions', 'Viewing Permissions',2);
  InsertAdminPhrase($pluginid, 'viewing_permissions_hint', 'Restrict view access to this section to all (no selection) or any combination of usergroups?<br /><br />
  You may select none, one or multiple usergroups by using [CTRL/Shift+Click] to de-/select entries.', 2);
  InsertAdminPhrase($pluginid, 'import_no_titles',  '<strong>No image title(s)?</strong>');
  InsertAdminPhrase($pluginid, 'midsize_regenerate', 'MidSize regenerate');
  InsertPhrase($pluginid, 'midsize_regen_error', 'MidSize image creation failed due to an error!');

  $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections','datecreated');
  $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections','owner_id');

} // MediaGalleryUpgrade344
} // DO NOT REMOVE!


if(!function_exists('MediaGalleryUpgrade346'))
{
function MediaGalleryUpgrade346()
{
  global $DB, $pluginid;

  if(empty($pluginid)) return;

  InsertAdminPhrase($pluginid, 'section_owner', 'Section Owner');
  InsertAdminPhrase($pluginid, 'section_owner_descr', 'Leave this empty or type in the exact username that owns this section.');
  InsertAdminPhrase($pluginid, 'owner_access_only', 'Restrict access to owner?');
  InsertAdminPhrase($pluginid, 'owner_access_only_descr', 'Enable this option to only allow the configured owner exclusive access to this section.<br />The usergroup-level viewing permissions are then not used.');
  InsertAdminPhrase($pluginid, 'untitled', '(untitled)');

  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections','owner_access_only','TINYINT(1)','UNSIGNED NOT NULL DEFAULT 0');

} // MediaGalleryUpgrade346
} // DO NOT REMOVE!


if(!function_exists('MediaGalleryUpgrade350')) #2012-08-27
{
function MediaGalleryUpgrade350()
{
  global $DB, $pluginid;

  if(empty($pluginid)) return;

  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_images','media_type','TINYINT(8)','UNSIGNED NOT NULL DEFAULT 0');
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_images','media_url','VARCHAR(255) collate utf8_unicode_ci',"NOT NULL DEFAULT ''");

  //Make sure some new core phrases exist (introduced in SD 3.4.4):
  InsertAdminPhrase(0, 'err_php_uploads_disabled', 'Error: PHP uploads are currently <strong>disabled</strong>.');
  InsertAdminPhrase(0, 'max_php_filesize_is', 'The maximum allowed size per uploaded file is <strong>[max_size]</strong>.');
  InsertAdminPhrase(0, 'max_uploadsize_is', 'The total allowed data size is <strong>[max_size]</strong>.');
  InsertAdminPhrase(0, 'server_restrictions', 'Server-side Restrictions:');

  DeleteAdminPhrase($pluginid, 'import_imagesize_error');
  InsertAdminPhrase($pluginid, 'batch_upload', 'Batch Upload');
  InsertAdminPhrase($pluginid, 'uploaded_count', 'Number of images uploaded:');
  InsertAdminPhrase($pluginid, 'err_folder_not_writable', 'Target folder is not writable, check permissions!');
  InsertAdminPhrase($pluginid, 'err_batch_no_files', 'No files were uploaded!');
  InsertAdminPhrase($pluginid, 'err_batch_toomany_files', 'Too many files (max. 100)!');
  InsertAdminPhrase($pluginid, 'err_php_upload_error', 'PHP Upload Error:');
  InsertAdminPhrase($pluginid, 'default_upload_folder_hint', '<strong>Default</strong> is the regular images folder of the plugin.');
  InsertAdminPhrase($pluginid, 'browse_image', 'Browse for Image');
  InsertAdminPhrase($pluginid, 'btn_upload_images', 'Upload Images');
  InsertAdminPhrase($pluginid, 'batch_import_hint',
    'On this page you can import multiple images that already exist on your server and'.
    ' have them copied or moved to an existing gallery section of your choice.<br />'.
    ' Please specify below the target section and the number of images to be imported'.
    ' (default: 10 images; max. 100 images).<br />');
  InsertAdminPhrase($pluginid, 'batch_upload_hint',
    'On this page you can browse for multiple images on your computer and'.
    ' have them uploaded to an existing gallery section of your choice.<br />'.
    ' Please select below the target section and - if available - a storage'.
    ' location (folder) where the images are uploaded to (should relate to the section).<br />');

  InsertAdminPhrase($pluginid, 'media_embed_width', 'Embedding Width (in Pixels; 0 = Auto):');
  InsertAdminPhrase($pluginid, 'media_embed_height', 'Embedding Height (in Pixels; 0 = Auto):');

  InsertPhrase($pluginid, 'no_media_preview_available', 'Sorry, there is currently no preview available for this media.');
  InsertPhrase($pluginid, 'visit_media_site', 'Visit media site (external link)...');

} // MediaGalleryUpgrade350
} // DO NOT REMOVE!


if(!function_exists('MediaGalleryUpgrade351')) #2013-01-04
{
function MediaGalleryUpgrade351()
{
  global $DB, $pluginid;

  if(empty($pluginid)) return;

  // Fix old CSS entries
  if($getcss = $DB->query('SELECT skin_css_id, css FROM {skin_css}'.
                          " WHERE var_name LIKE 'Media Gallery%' AND plugin_id = %d", $pluginid))
  {
    while($row = $DB->fetch_array($getcss,null,MYSQL_ASSOC))
    {
      $css = str_replace(
        array('gallery_containment', 'gallery17_containment', 'mb_containment', 'mb17_containment'),
        array('gallery'.$pluginid.'_containment', 'gallery'.$pluginid.'_containment', 'mb'.$pluginid.'_containment', 'mb'.$pluginid.'_containment'),
        $row['css']);
      $DB->query("UPDATE {skin_css} SET css = '%s' WHERE skin_css_id = %d",
                 $DB->escape_string($css), $row['skin_css_id']);
    }
    unset($getcss,$css,$row);
  }

} // MediaGalleryUpgrade351
} // DO NOT REMOVE!


if(!function_exists('MediaGalleryUpgrade352')) #2013-01-14
{
function MediaGalleryUpgrade352()
{
  global $DB, $pluginid;

  if(empty($pluginid)) return;

  InsertAdminPhrase($pluginid, 'add_media', 'Add Media');
  InsertAdminPhrase($pluginid, 'media_files', 'Media Files');

  DeleteAdminPhrase($pluginid, 'add_image');
  DeleteAdminPhrase($pluginid, 'add_image_hint');

  // Enable reporting support (comments):
  if($DB->column_exists(PRGM_TABLE_PREFIX.'plugins','reporting'))
  {
    $DB->query('UPDATE {plugins} SET reporting = 1 WHERE pluginid = %d',$pluginid);
  }

} // MediaGalleryUpgrade352
} // DO NOT REMOVE!


if(!function_exists('MediaGalleryUpgrade361')) #2013-02-02
{
function MediaGalleryUpgrade361()
{
  global $DB, $pluginid;

  if(empty($pluginid)) return;

  // * Fix primary key for pXXXX_images table, which is just
  // a regular index on older installations (2013-02-18):
  $DB->ignore_error = true;
  $tbl = PRGM_TABLE_PREFIX.'p'.$pluginid.'_images';
  $DB->drop_index($tbl, 'imageid');
  if(!$DB->index_exists($tbl, 'PRIMARY'))
  {
    $DB->query('ALTER TABLE '.$tbl.' ADD PRIMARY KEY (`imageid`)');
  }
  // Add extra indexes and column "owner_id" to images table (2013-02-18)
  $DB->add_tableindex($tbl, 'activated');
  $DB->add_tableindex($tbl, 'media_type');
  $DB->add_tablecolumn($tbl, 'owner_id', 'INT(11)', 'UNSIGNED NOT NULL DEFAULT 0');
  $DB->add_tablecolumn($tbl, 'media_category_id', 'INT(11)', 'UNSIGNED NOT NULL DEFAULT 0');

  $tbl = PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections';
  $DB->add_tablecolumn($tbl, 'display_social_media', 'TINYINT(1)', 'NOT NULL DEFAULT 0');
  $DB->add_tablecolumn($tbl, 'section_sorting', 'VARCHAR(32) collate utf8_unicode_ci', "NOT NULL DEFAULT ''");
  $DB->add_tablecolumn($tbl, 'max_subsections', 'INT(5)', 'NOT NULL DEFAULT 0');
  $DB->add_tablecolumn($tbl, 'subsection_template_id', 'INT(10)', "NOT NULL DEFAULT 0");
  $DB->add_tablecolumn($tbl, 'allow_submit_file_upload', 'INT(1)', "NOT NULL DEFAULT 1");
  $DB->add_tablecolumn($tbl, 'allow_submit_media_link', 'INT(1)', "NOT NULL DEFAULT 0");
  $DB->add_tablecolumn($tbl, 'seo_title', 'VARCHAR(200) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");
  $DB->add_tablecolumn($tbl, 'metakeywords', 'VARCHAR(200) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");
  $DB->add_tablecolumn($tbl, 'metadescription', 'VARCHAR(200) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");
  $DB->add_tablecolumn($tbl, 'pwd', 'VARCHAR(64) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");
  $DB->add_tablecolumn($tbl, 'pwd_salt', 'VARCHAR(64) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");
  $DB->add_tablecolumn($tbl, 'link_to', 'VARCHAR(200) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");
  $DB->add_tablecolumn($tbl, 'target', 'VARCHAR(10) COLLATE utf8_unicode_ci', "NOT NULL DEFAULT ''");
  $DB->add_tablecolumn($tbl, 'publish_start', 'INT(11) UNSIGNED', 'NOT NULL DEFAULT 0');
  $DB->add_tablecolumn($tbl, 'publish_end', 'INT(11) UNSIGNED', 'NOT NULL DEFAULT 0');
  $DB->ignore_error = false;

  InsertAdminPhrase($pluginid, 'allow_submit_file_upload', 'Allow File Uploads');
  InsertAdminPhrase($pluginid, 'allow_submit_file_upload_descr',
    'Allow users to upload files (images,video) to this section (default: Yes)?');
  InsertAdminPhrase($pluginid, 'allow_submit_media_link', 'Allow Media Links');
  InsertAdminPhrase($pluginid, 'allow_submit_media_link_descr',
    'Allow users to submit links of media sites items (default: No)?<br />'.
    'Note: the allowed list of media types is configured in the plugin settings.');
  InsertAdminPhrase($pluginid, 'section_seo_title','SEO Title');
  InsertAdminPhrase($pluginid, 'section_seo_title_hint','If this is left empty, the SEO title will be automatically generated when saving the section.');
  InsertAdminPhrase($pluginid, 'section_link_to','Links to (full URL)');
  InsertAdminPhrase($pluginid, 'section_link_to_hint','If a valid URL is specified, the section will link to that instead of showing it\'s own media.');
  InsertAdminPhrase($pluginid, 'section_external_link_target','Link Target');
  InsertAdminPhrase($pluginid, 'section_meta_description','Meta Description');
  InsertAdminPhrase($pluginid, 'section_meta_keywords','Meta Keywords');
  InsertAdminPhrase($pluginid, 'err_seo_title_existing', 'The section\'s <strong>SEO Title</strong> must be set manually! There already exists a section with the same SEO title!');

  $DB->query("UPDATE {adminphrases} SET defaultphrase = 'Automatically create thumbnails from uploaded images (default: Yes)?'".
             " WHERE pluginid = %d AND varname = 'auto_create_thumbs_descr'",$pluginid);
  $DB->query("UPDATE {adminphrases} SET defaultphrase = 'Enable display of comments for images plugin-wide (default: Yes)?<br />If disabled, no comments will be displayed at all.'".
             " WHERE pluginid = %d AND varname = 'enable_comments_descr'",$pluginid);
  $DB->query("UPDATE {adminphrases} SET defaultphrase = 'List of allowed image types for upload (comma separated)?<br />E.g.: <strong>gif, jpg, jpeg, png<strong>'".
             " WHERE pluginid = %d AND varname = 'allowed_image_types_descr'",$pluginid);
  $DB->query("UPDATE {adminphrases} SET defaultphrase = 'Automatically approve user submitted images (default: no)?'".
             " WHERE pluginid = %d AND varname = 'auto_approve_images_descr'",$pluginid);
  $DB->query("UPDATE {adminphrases} SET defaultphrase = 'Maximum image filesize for user uploads in Bytes?<br />Default: <strong>1048576</strong> (= 1MB)'".
             " WHERE pluginid = %d AND varname = 'max_upload_size_descr'",$pluginid);
  $DB->query("UPDATE {adminphrases} SET defaultphrase = 'Is the image <strong>Author</strong> required for image uploads (default: Yes)?'".
             " WHERE pluginid = %d AND varname = 'image_author_required_descr'",$pluginid);
  $DB->query("UPDATE {adminphrases} SET defaultphrase = 'Is the image <strong>Description</strong> required for image uploads (default: Yes)?'".
             " WHERE pluginid = %d AND varname = 'image_description_required_descr'",$pluginid);
  $DB->query("UPDATE {adminphrases} SET defaultphrase = 'Is the image <strong>Title</strong> required for image uploads (default: Yes)?'".
             " WHERE pluginid = %d AND varname = 'image_title_required_descr'",$pluginid);
  $DB->query("UPDATE {adminphrases} SET defaultphrase = 'Perform extra security checks on uploaded images/thumbnails (default: Yes)<br />This is highly recommended to avoid malicious attacks!'".
             " WHERE pluginid = %d AND varname = 'image_security_check_descr'",$pluginid);

  $DB->query("UPDATE {adminphrases} SET defaultphrase = 'When an image is uploaded should we keep the fullsize image?<br />If this is set to <strong>No</strong> and \'Create MidSize Images\' is set to <strong>Yes</strong>, this gives you ability to restrict the maximum size of the image. In this case the original image will be deleted leaving you with the fixed size MidSize image. Only active when \'Create MidSize Images\' is enabled (default: Yes):'".
             " WHERE pluginid = %d AND varname = 'save_fullsize_images_descr'",$pluginid);
  $DB->query("UPDATE {adminphrases} SET defaultphrase = 'Automatically create midsize images (default: No)?<br />This allows you to show a larger (than thumbnail) image before the full size image is shown:'".
             " WHERE pluginid = %d AND varname = 'create_midsize_images_descr'",$pluginid);
  $DB->query("UPDATE {adminphrases} SET defaultphrase = 'Enter the max width that the midsize image should be resized to (in pixels):'".
             " WHERE pluginid = %d AND varname = 'max_midsize_width_descr'",$pluginid);
  $DB->query("UPDATE {adminphrases} SET defaultphrase = 'Enter the max height that the midsize image should be resized to (in pixels):'".
             " WHERE pluginid = %d AND varname = 'max_midsize_height_descr'",$pluginid);
  $DB->query("UPDATE {adminphrases} SET defaultphrase = 'When automatically creating midsize images, should the image be scaled so that it is square (default: No)?'".
             " WHERE pluginid = %d AND varname = 'square_off_midsize_descr'",$pluginid);

  $DB->query("UPDATE {adminphrases} SET defaultphrase = 'Automatically create thumbnail from uploaded image (default: Yes)?'".
             " WHERE pluginid = %d AND varname = 'auto_create_thumbs_descr'",$pluginid);
  $DB->query("UPDATE {adminphrases} SET defaultphrase = 'Enter the max width that the thumbnail image should be resized to (in pixels):'".
             " WHERE pluginid = %d AND varname = 'max_thumb_width_descr'",$pluginid);
  $DB->query("UPDATE {adminphrases} SET defaultphrase = 'Enter the max height that the thumbnail image should be resized to (in pixels):'".
             " WHERE pluginid = %d AND varname = 'max_thumb_height_descr'",$pluginid);
  $DB->query("UPDATE {adminphrases} SET defaultphrase = 'When automatically creating thumbnail images, should the image be scaled so that it is square (default: No)?'".
             " WHERE pluginid = %d AND varname = 'square_off_thumbs_descr'",$pluginid);

  $DB->query("UPDATE {adminphrases} SET defaultphrase = 'Display the image count in each section (default: Yes)?<br />Disable this option if you have many images and your media gallery is loading slowly.'".
             " WHERE pluginid = %d AND varname = 'display_section_image_count_descr'",$pluginid);
  $DB->query("UPDATE {adminphrases} SET defaultphrase = 'Enter the number of section images to be displayed per row (default: 5):<br />Note: not every display mode may use this!'".
             " WHERE pluginid = %d AND varname = 'section_images_per_row_descr'",$pluginid);

  DeletePhrase($pluginid, 'comment');
  DeletePhrase($pluginid, 'comments');

  InsertPhrase($pluginid, 'section_not_found', 'Section not found.');
  InsertPhrase($pluginid, 'action_successful', 'Thank you, the task was successful.');
  InsertPhrase($pluginid, 'image_added_label', 'Image added by [owner], uploaded on [datecreated].');
  InsertPhrase($pluginid, 'section_added_label', 'Section added by [owner] on [datecreated].');
  InsertPhrase($pluginid, 'section_images_count', 'Number of images:');
  InsertPhrase($pluginid, 'no_entries_in_section', 'Sorry, there are no entries in this section yet.');
  InsertPhrase($pluginid, 'enter_media_site_link', 'Enter link to a video on supported sites:',true);
  InsertPhrase($pluginid, 'view', 'View');
  InsertPhrase($pluginid, 'delete_image_title', 'Confirm image deletion');
  InsertPhrase($pluginid, 'confirm_delete_image', 'Yes, I confirm the image deletion');
  InsertPhrase($pluginid, 'delete_image_unconfirmed', 'You chose to decline the image deletion, nothing happened.');
  InsertPhrase($pluginid, 'section', 'Section');

  InsertAdminPhrase($pluginid, 'enable_seo', 'Enable SEO');
  InsertPluginSetting($pluginid, 'admin_options', 'enable_seo',
    'Enable <strong>search engine optimized</strong> links on frontpage for sections and images (default: No)?<br />'.
    'Note: this should only be activated if the Main Settings SEO option is activated as well, '.
    'but it can be separately deactivated here in case of any issues.', 'yesno', '0', 5);

  InsertAdminPhrase($pluginid, 'allow_media_owner_delete', 'Allow image owners to delete own media?');
  InsertAdminPhrase($pluginid, 'allow_section_owner_delete', 'Allow section owners to delete media?');
  InsertPluginSetting($pluginid, 'admin_options', 'allow_section_owner_delete',
    'Allow section owners (non-admin users) to delete any item in all sections they own (default: No)?<br />'.
    'Note: this would allow them to delete any media item within their sections, '.
    'even if submitted by somebody else!', 'yesno', '0', 100);
  InsertPluginSetting($pluginid, 'admin_options', 'allow_media_owner_delete',
    'Allow item owners (non-admin users) to delete media they originally submitted (default: No)?<br />'.
    'Note: this would allow them to delete their own submitted items from a section '.
    'even if they are not owning the section itself.', 'yesno', '0', 110);

  InsertAdminPhrase($pluginid, 'subsections_list_template', 'Sub-Sections List Template');
  InsertAdminPhrase($pluginid, 'subsections_list_template_descr',
    'Specify the template name to be used for displaying the list of <strong>sub-sections</strong> on the frontpage.<br />'.
    'The first variant display sections with their own image, the 2nd variant displays only section names.',2);

  InsertAdminPhrase($pluginid, 'sort_subsections_by', 'Sort Subsections by:');
  InsertAdminPhrase($pluginid, 'display_max_subsections', 'Display max. number of subsections:<br />0 = unlimited',2);
  DeleteAdminPhrase($pluginid, 'show_social_media');
  InsertAdminPhrase($pluginid, 'show_social_media', 'Display Social Media Links<br />
    Enable display of social media links for a media/image file (default: No)<br />
    <strong>Note:</strong> this setting is just an on/off switch for use within the template;
    the actual choice of social media links must be maintained within the single-image template itself.');

  // Remove duplicate "old" settings row (probably p17)
  $rows = $DB->query_first('SELECT COUNT(*) rowcnt'.
                           ' FROM {pluginsettings} WHERE pluginid = '.$pluginid.
                           " AND title = 'section_sort_order'");
  if($rows['rowcnt'] > 1)
  {
    $DB->query('DELETE FROM {pluginsettings} WHERE pluginid = '.$pluginid.
               " AND title = 'section_sort_order'".
               ' AND input LIKE \'%settings%$setting%\'');
  }

  $DB->query("UPDATE {pluginsettings} SET displayorder = 15,".
             " input = 'select:\r\n0|Newest First\r\n1|Oldest First\r\n2|Alphabetically A-Z\r\n3|Alphabetically Z-A'".
             ' WHERE pluginid = '.$pluginid.
             " AND title = 'section_sort_order'");

  // Update SEO title of all sections, that do not yet have one!
  $table = PRGM_TABLE_PREFIX.'p'.$pluginid.'_sections';
  if($getrows = $DB->query('SELECT sectionid, parentid, name FROM '.$table.
                           " WHERE IFNULL(seo_title,'') = '' ORDER BY sectionid DESC"))
  {
    $GLOBALS['mainsettings_seo_filter_words'] = 0;
    while($row = $DB->fetch_array($getrows,NULL,MYSQL_ASSOC))
    {
      $sectionid = $row['sectionid'];
      $seotitle = ConvertNewsTitleToUrl($row['name'], 0, 0, true);
      if(!$DB->query_first('SELECT seo_title FROM '.$table.
                           " WHERE sectionid != %d AND parentid = %d AND seo_title = '%s'",
                           $sectionid, $row['parentid'],
                           $DB->escape_string($seotitle)))
      {
        $DB->query('UPDATE '.$table." SET seo_title = '%s' WHERE sectionid = %d",
                   $seotitle, $sectionid);
      }
    }
  }

} // MediaGalleryUpgrade361
} // DO NOT REMOVE!


if(!function_exists('MediaGalleryUpgrade370')) #2013-08-19
{
function MediaGalleryUpgrade370()
{
  global $DB, $pluginid;

  if(empty($pluginid)) return;

  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_images','access_view','TEXT','COLLATE utf8_unicode_ci NULL');
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_images','private','TINYINT(1) UNSIGNED','NOT NULL DEFAULT 0');
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_images','duration','INT UNSIGNED','NOT NULL DEFAULT 0');
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p'.$pluginid.'_images','lyrics','TEXT','COLLATE utf8_unicode_ci NULL');

  // Assure that default slugs exist
  $tbl = PRGM_TABLE_PREFIX.'slugs';
  if(!$DB->query_first("SELECT name FROM $tbl WHERE name = 'Tag Archive' AND pluginid = %d LIMIT 1", $pluginid))
  {
    $DB->query_first("INSERT INTO `$tbl` (`term_id`, `name`, `description`, `pluginid`, `parent`, `count`, `slug`, `slug_type`) VALUES
    (NULL, 'Tag Archive', 'URI for plugin tags with tag value at then end.', %d, 0, 0, '[tag]', 0)", $pluginid);
  }
  if(!$DB->query_first("SELECT name FROM $tbl WHERE name = 'Tags by Year and Month' AND pluginid = %d LIMIT 1", $pluginid))
  {
    $DB->query_first("INSERT INTO `$tbl` (`term_id`, `name`, `description`, `pluginid`, `parent`, `count`, `slug`, `slug_type`) VALUES
    (NULL, 'Tags by Year and Month', 'URI for tags by year and month', %d, 0, 0, '[year]/[month]', 0)", $pluginid);
  }
  InsertPhrase($pluginid, 'msg_no_results','<p><strong>No media found.</strong></p>');
  InsertPhrase($pluginid, 'msg_no_tag_results','<h2>Sorry, no results!</h2><p>There is no media tagged by: [tag].</p>');
  InsertPhrase($pluginid, 'media_year_month_head','<p>Media for period: [year]-[month].</p>');
  InsertPhrase($pluginid, 'media_tags_head','<p>Media tagged with: [tag].</p>');
  InsertPhrase($pluginid, 'msg_no_tag_results','<strong>Sorry, could not find any media tagged with [tag].</strong>',2,true);
  InsertPhrase($pluginid, 'msg_no_results','<strong>There are no media entries available here.</strong>');
  InsertPhrase($pluginid, 'msg_search_results','<strong>Results for media:</strong>');
  InsertPhrase($pluginid, 'meta_page_phrase','Page [page]');

  DeletePhrase($pluginid, 'confirm_remove_tag');

  // Replace common use of "image/s" with "media":
  InsertPhrase($pluginid, 'image_submitted', 'Thank you! Your media has been submitted. ',2,true);
  InsertPhrase($pluginid, 'image', 'Image:',2,true);
  InsertPhrase($pluginid, 'image2', 'Image',2,true);
  InsertPhrase($pluginid, 'images', 'Images',2,true);
  InsertPhrase($pluginid, 'images2', 'Images:',2,true);
  InsertPhrase($pluginid, 'notify_email_message', 'New media has been submitted to your gallery.',2,true);
  InsertPhrase($pluginid, 'notify_email_subject', 'New media submitted to your website!',2,true);
  InsertPhrase($pluginid, 'previous_image', '&laquo; Previous Media',2,true);
  InsertPhrase($pluginid, 'previous_images', '&laquo; Previous Media',2,true);
  InsertPhrase($pluginid, 'section_offline_message', 'The selected section is currently not available.',2,true);
  InsertPhrase($pluginid, 'submit_an_image', 'Submit media to this section',2,true);
  InsertPhrase($pluginid, 'submit_image', 'Submit Media',2,true);
  InsertPhrase($pluginid, 'submitting_image', 'Submitting Media',2,true);
  InsertPhrase($pluginid, 'watchdog_deleted', 'Media deleted',2,true);
  InsertPhrase($pluginid, 'watchdog_approved', 'Media approved',2,true);
  InsertPhrase($pluginid, 'watchdog_unapproved', 'Media unapproved',2,true);
  InsertPhrase($pluginid, 'image_not_found', 'Sorry, the requested media could not be found or is private.',2,true);
  InsertPhrase($pluginid, 'next_image', 'Next Media &raquo;',true);
  InsertPhrase($pluginid, 'submit_offline', 'Media upload currently deactivated.',true);
  InsertPhrase($pluginid, 'more_images', 'More Media &raquo;',true);
  InsertAdminPhrase($pluginid, 'edit_image', 'Edit Media',2,true);
  InsertAdminPhrase($pluginid, 'image_option_publish', '<strong>Publish:</strong> Are you ready to publish this media on your site?',2,true);
  InsertAdminPhrase($pluginid, 'image_option_display_author', '<strong>Display Author Name:</strong> Would you like the author\'s name shown under the title of the media?',2,true);
  InsertAdminPhrase($pluginid, 'image_option_comments', '<strong>Comments:</strong> Enable comments to be posted for this media?',2,true);
  InsertAdminPhrase($pluginid, 'image_option_ratings', '<strong>Ratings:</strong> Allow visitors to rate this media?',2,true);
  InsertAdminPhrase($pluginid, 'delete_image', 'Delete Media:',2,true);
  InsertAdminPhrase($pluginid, 'delete_images_in_section', 'Delete all media assigned to this section?',2,true);
  InsertAdminPhrase($pluginid, 'confirm_delete_image', 'Delete this media?',2,true);
  InsertAdminPhrase($pluginid, 'update_image', 'Update Media',2,true);
  InsertAdminPhrase($pluginid, 'update_images', 'Update Media',2,true);
  InsertAdminPhrase($pluginid, 'view_image', 'View Media',2,true);
  InsertAdminPhrase($pluginid, 'view_images', 'View Media',2,true);
  InsertAdminPhrase($pluginid, 'message_image_added', 'Media was added successfully!',2,true);
  InsertAdminPhrase($pluginid, 'message_image_updated', 'Media was updated successfully!',2,true);
  InsertAdminPhrase($pluginid, 'create_section_descr', 'You can group media entries into sections,
    which may also have a specific sub-folder for e.g. images on the server
    (requires manual creation on server within FTP client).<br />
    The top, default section is the root section and cannot be deleted.',2,true);
  InsertAdminPhrase($pluginid, 'filter_title', 'Filter Media',2,true);
  InsertAdminPhrase($pluginid, 'image', 'Media:',2,true);
  InsertAdminPhrase($pluginid, 'images', 'Media',2,true);
  InsertAdminPhrase($pluginid, 'confirm_regenerate_midsize', 'Regenerate the mid-size image (uses current Midsize Settings)?',2,true);
  InsertAdminPhrase($pluginid, 'confirm_regenerate_thumb', 'Regenerate the thumbnail for this image (uses current Thumbnail Settings)?',2,true);
  InsertAdminPhrase($pluginid, 'section_batch_remove_comments', 'Remove <strong>ALL</strong> media comments?',2,true);
  InsertAdminPhrase($pluginid, 'section_batch_remove_ratings', 'Remove <strong>ALL</strong> media ratings?',2,true);
  InsertAdminPhrase($pluginid, 'section_batch_remove_tags', 'Remove <strong>ALL</strong> media tags?',2,true);
  InsertAdminPhrase($pluginid, 'image_size', 'Media Size',2,true);
  InsertAdminPhrase($pluginid, 'upload_image_again', 'Note: the original image <i>does not exist</i>, please either delete or re-upload this image.',2,true);

  // New phrases:
  InsertPhrase($pluginid, 'no_image_available', 'No media.');
  InsertPhrase($pluginid, 'images_contributed_by', 'Media contributed by:');
  InsertPhrase($pluginid, 'edit_media', 'Edit Media');
  InsertPhrase($pluginid, 'edit_section', 'Edit Section');
  InsertPhrase($pluginid, 'update_image', 'Update Media');
  InsertPhrase($pluginid, 'update_section', 'Update Section');
  InsertPhrase($pluginid, 'set_as_section_thumb', 'Set image as the thumbnail for this section?');
  InsertPhrase($pluginid, 'err_image_not_found', 'The specified image does not exist!');
  InsertPhrase($pluginid, 'err_section_not_found', 'The specified section does not exist!');
  InsertPhrase($pluginid, 'err_invalid_data_entered', 'There was invalid data entered in one of the text fields!');
  InsertPhrase($pluginid, 'section_name', 'Section Name:');
  InsertPhrase($pluginid, 'section_description', 'Section Description:');
  InsertPhrase($pluginid, 'remote_image_download', 'Remote image download:');
  InsertPhrase($pluginid, 'remote_thumbnail_download', 'Remote thumbnail download:');
  InsertPhrase($pluginid, 'remote_download_failed', 'Remote image download failed (server may not allow it).');
  InsertPhrase($pluginid, 'lbl_is_private', 'Private?');
  InsertPhrase($pluginid, 'duration', 'Duration (in seconds):');
  InsertPhrase($pluginid, 'lyrics', 'Lyrics:');
  InsertPhrase($pluginid, 'go_to_section', 'Go to section');
  InsertPhrase($pluginid, 'click_to_verify_media_url', 'Click to verify Media URL');
  InsertPhrase($pluginid, 'media_url_confirmed', 'Media site recognized:');
  InsertPhrase($pluginid, 'media_url_unsupported', 'Sorry, but the media URL was not recognized or is not support.');
  InsertPhrase($pluginid, 'tags_hint', 'Separate individual tags by pressing ENTER or comma (,) key.');
  InsertPhrase($pluginid, 'submit_options', 'Options');
  InsertPhrase($pluginid, 'submit_option_author', 'Show author?');
  InsertPhrase($pluginid, 'submit_option_comments', 'Allow user comments?');
  InsertPhrase($pluginid, 'submit_option_ratings', 'Allow user ratings?');
  InsertPhrase($pluginid, 'submit_option_private', 'Private?');
  InsertPhrase($pluginid, 'display_section_online', 'Active: display this section online?',true);
  InsertPhrase($pluginid, 'section_display_author', 'Display author name for images (depends on Display Mode)?');
  InsertPhrase($pluginid, 'section_display_comments', 'Display comment counts for media (depends on Display Mode)?');
  InsertPhrase($pluginid, 'section_display_ratings', 'Display image ratings?');
  InsertPhrase($pluginid, 'section_display_socialmedia', 'Display social media links?');
  InsertPhrase($pluginid, 'section_display_view_counts', 'Display number of views per image?<br />Note: views counting is not available for all Display Modes.');
  InsertPhrase($pluginid, 'start_publishing', 'Start publishing date/time (default: none):');
  InsertPhrase($pluginid, 'end_publishing', 'End publishing date/time (default: none):');
  InsertPhrase($pluginid, 'invalid_image_title', 'Invalid image title!');

  //Moved to phrases:
  DeleteAdminPhrase($pluginid, 'section_display_author',2);
  DeleteAdminPhrase($pluginid, 'section_display_comments');
  DeleteAdminPhrase($pluginid, 'section_display_ratings',2);
  DeleteAdminPhrase($pluginid, 'section_display_view_counts',2);
  DeleteAdminPhrase($pluginid, 'display_section_online',2);
  DeleteAdminPhrase($pluginid, 'err_invalid_embed_url', 2);
  DeleteAdminPhrase($pluginid, 'confirm_remove_tag', 2);
  DeleteAdminPhrase($pluginid, 'tags_hint', 2);

  // New admin phrases:
  InsertAdminPhrase($pluginid, 'media_embed_dim_hint', 'If the dimensions are left at 0, the remote media will determine it.',2,true);
  InsertAdminPhrase($pluginid, 'media_url_hint', 'Enter here an URL to a video/foto hosted on one of the following sites:<br />'.
    'YouTube, Vimeo, LiveLeak, DailyMotion, MetaCafe, FunnyOrDie, Revision3, Break, AOL, Clikthrough, MySpace, Viddler, Blip, Dotsub, Slideshare, Qik, Pixel Bark, Yfrog, Sevenload, Screenr, Videojug, TwitPic, Flickr',2,true);

  InsertAdminPhrase($pluginid, 'view_section_media', 'View Section Media');
  InsertAdminPhrase($pluginid, 'maintainer', 'Maintainer');
  InsertAdminPhrase($pluginid, 'maintainer_hint', 'The maintainer is a CMS user who added this to his/her profile or to a gallery.
    If a maintainer is assigned, this user may edit this media on the frontpage (profile or plugin page) depending on settings in the Admin Options.<br />
    Leave this empty if not otherwise needed.');
  InsertAdminPhrase($pluginid, 'author_hint', 'This should be the original author or copyright holder of this media.');
  InsertAdminPhrase($pluginid, 'image_option_private', 'Is this media <b>private</b> to the maintainer (default: No)?',2,true);
  InsertAdminPhrase($pluginid, 'update_timestamp', 'Update timestamp to now?');
  InsertAdminPhrase($pluginid, 'upload_media_file', 'Upload a Media File');
  InsertAdminPhrase($pluginid, 'embed_media_url', 'Embed from media URL');
  InsertAdminPhrase($pluginid, 'download_remote_thumb', 'Download remote image as local thumb:');
  InsertAdminPhrase($pluginid, 'use_regenerate_option', 'Note: the original image <i>does exist</i>, please use the "regenerate" option to re-create a valid thumbnail image.',2,true);
  InsertAdminPhrase($pluginid, 'no_thumb_found', 'No valid thumbnail image was found, which might be due to a different extention of image and thumb, a failed upload or thumbnail creation error.',2,true);
  InsertAdminPhrase($pluginid, 'admin_embedding_disabled', '<b>Note:</b> embedding is currently disabled (see View Settings|Admin Options).',2,true);
  InsertAdminPhrase($pluginid, 'admin_embedding_hint', '<b>Note:</b> for both faster pageload and security concerns remote content should not be embedded in the admin area (see View Settings|Admin Options).<br />'.
    'Media URLs require server to support <b>cURL</b> module and may not work at all due to server restrictions!');
  InsertAdminPhrase($pluginid, 'msg_image_uploaded_js', 'Image uploaded!');
  InsertAdminPhrase($pluginid, 'number_of_images_per_row', 'Number of images displayed per row (1-99)?',2,true);
  InsertAdminPhrase($pluginid, 'number_of_images_per_row_hint', 'Note: only used for <b>Integrated Display Mode</b>.');

  // Support for more media sites:
  InsertPluginSetting($pluginid, 'upload_settings', 'Allow media site links',
    'List of allowed media sites a user may submit a link from (default: Disabled)?<br />'.
    'Select none, one or multiple entries by CTRL/Shift+Click.',
    "select-multi:\r\n0|Disabled\r\n1|YouTube\r\n2|Vimeo\r\n3|Google\r\n4|Facebook\r\n5|DailyMotion\r\n".
    "6|MetaCafe\r\n9|Blip\r\n10|Viddler\r\n11|FunnyOrDie\r\n".
    "12|TwitPic\r\n13|Yfrog\r\n14|Break\r\n15|Qik\r\n16|Hulu\r\n17|Revision\r\n".
    "18|Liveleak\r\n19|Flickr\r\n20|AOL\r\n21|clikthrough\r\n22|MySpace\r\n23|slideshare\r\n".
    "24|Pixel Bark\r\n25|Soundcloud\r\n26|Screenr\r\n27|Sevenload\r\n28|Videojug\r\n29|Dotsub\r\n",
    '0', 25, true);

  // New options:
  InsertAdminPhrase($pluginid, 'admin_media_embedding', 'Embed remote media within admin?');
  InsertAdminPhrase($pluginid, 'allow_media_owner_edit', 'Allow image owners to edit own media?');
  InsertAdminPhrase($pluginid, 'allow_section_owner_edit', 'Allow section owners to edit media?');
  InsertAdminPhrase($pluginid, 'display_tags_column', 'Display Tags column in media list?');
  InsertPluginSetting($pluginid, 'admin_options', 'admin_media_embedding',
    'When editing a Media URL, should the actual media be embedded to be playable (default: No)?<br />'.
    'For both faster pageload and security concerns remote content should not be embedded in the admin area. '.
    'If not embedded, a thumbnail will be displayed which links to the original URL.<br />'.
    'Note: Media URLs require server to support <b>cURL</b> module and may not work at all due to server restrictions!',
    'yesno', '0', 102);
  InsertPluginSetting($pluginid, 'admin_options', 'allow_section_owner_edit',
    'Allow section owners (non-admin users) to edit (title, description) any item in all sections they own (default: No)?<br />'.
    'Note: this would allow them to edit any media item within their sections, '.
    'even if submitted by somebody else!', 'yesno', '0', 105);
  InsertPluginSetting($pluginid, 'admin_options', 'allow_media_owner_edit',
    'Allow item owners (non-admin users) to edit media (title, description) they originally submitted (default: No)?<br />'.
    'Note: this would allow them to edit their own submitted items from a section '.
    'even if they are not owning the section itself.', 'yesno', '0', 115);
  InsertPluginSetting($pluginid, 'admin_options', 'display_tags_column',
    'Display within the media list the Tags column (default: Yes)?<br />'.
    'If you do not need tags or wish to make room for other columns, set this option to No,'.
    ' the search bar will still allow to filter for single tags.', 'yesno', '1', 120);

} // MediaGalleryUpgrade370
} // DO NOT REMOVE!


if(!function_exists('UpgradeMediaGalleryTemplates')) //SD360
{
function UpgradeMediaGalleryTemplates()
{
  global $DB, $pluginid, $pluginpath;
  if(empty($pluginid)) return false;

  require_once(SD_INCLUDE_PATH.'class_sd_smarty.php');
  $ppath = dirname($pluginpath);
  echo '<b>Adding templates to Media Gallery plugin ('.$ppath.')...</b>';

  $new_tmpl = array(
    'gallery_header.tpl' => array('Media Gallery Header', 'General header that is always displayed'),
    'images_display_mode_0.tpl' => array('Images - Integrated Display Mode (with comments)', 'Integrated display moder'),
    'images_display_mode_1.tpl' => array('Images - Popup (with comments)', 'Popup display mode'),
    'images_display_mode_2.tpl' => array('Images - Ceebox (no comments)', 'Ceebox display mode'),
    'images_display_mode_3.tpl' => array('Images - Fancybox (with comments)', 'Fancybox display mode'),
    'images_display_mode_4.tpl' => array('Images - Galleria (no comments)', 'Galleria display mode'),
    'images_display_mode_5.tpl' => array('Images - Slide Rotator (no comments)', 'Slide Rotator display mode'),
    'images_display_mode_6.tpl' => array('Images - mb.Gallery (no comments)', 'mb.Gallery display mode'),
    'images_display_mode_7.tpl' => array('Images - jqGalViewII (no comments)', 'jqGalViewII display mode'),
    'section_head.tpl' => array('Section - Head (images list)', 'Top of section area when displaying media items'),
    'section_footer.tpl' => array('Section - Footer (images list)', 'Bottom of section area when displaying media items'),
    'section_offline.tpl' => array('Section - Offline Message', 'Message for user if given section is offline/not accessible'),
    'single_image.tpl' => array('Single Image View', 'Display of a single image'),
    'subsections1.tpl' => array('Sub-sections variant 1', 'Sub-scections display variant 1 (images)'),
    'subsections2.tpl' => array('Sub-sections variant 2', 'Sub-scections display variant 2 (text)'),
    'upload_form.tpl' => array('User upload form', 'Displays form to upload a media file for user'),
  );
  $tpl_path = SD_INCLUDE_PATH.'tmpl/defaults/media_gallery/';

  // Loop to add DEFAULT templates first:
  foreach($new_tmpl as $tpl_name => $tpl_data)
  {
    // Do not create duplicate
    if(false !== SD_Smarty::TemplateExistsInDB($pluginid, $tpl_name)) continue;

    if(false !== SD_Smarty::CreateTemplateFromFile($pluginid, $tpl_path, $tpl_name,
                                                   $tpl_data[0], $tpl_data[1]))
    {
      echo '<br />Default template for <strong>"'.$tpl_name.'"</strong> added.';
    }
  }
  echo '<br /><b>Done.</b><br />';

} //UpgradeMediaGalleryTemplates
} //DO NOT REMOVE!


// ############################################################################
// INSTALL PLUGIN
// ############################################################################

if(($installtype == 'install') && (empty($pluginid) || ($pluginid==17)))
{
  // At this point SD3 has to provide a new plugin id
  $pluginid = CreatePluginID($pluginname);

  // If for whatever reason the plugin id is invalid, return false NOW!
  if(empty($pluginid))
  {
    return false;
  }

  // Fix old CSS entries wrongly inserted
  $DB->query("DELETE FROM {skin_css} WHERE var_name LIKE 'Media Gallery %' AND plugin_id = 0");
  $DB->query("DELETE FROM {skin_css} WHERE var_name LIKE 'Image Gallery %' AND plugin_id = 0");

  $CSS = new CSS();
  if($pluginid == 17)
  {
    $CSS->ReplaceCSS('Media Gallery', 'plugins/'.$plugin_folder.'/css/styles.css', true);
  }
  else
  {
    $CSS->InsertCSS('Media Gallery', 'plugins/'.$plugin_folder.'/css/styles.css', true,
                    array(
                    'p17_' => 'p'.$pluginid.'_',
                    'p17-' => 'p'.$pluginid.'-',
                    'gallery_containment' => 'gallery'.$pluginid.'_containment',
                    'mb_containment' => 'mb'.$pluginid.'_containment',
                    ), $pluginid);
  }
  unset($CSS);

  $DB->query('CREATE TABLE IF NOT EXISTS {p'.$pluginid."_images} (
  imageid       BIGINT(11)   UNSIGNED NOT NULL AUTO_INCREMENT,
  sectionid     BIGINT(11)   UNSIGNED NOT NULL DEFAULT 0,
  activated     TINYINT(1)   UNSIGNED NOT NULL DEFAULT 0,
  filename      VARCHAR(32)           NOT NULL DEFAULT '',
  allowcomments TINYINT(1)   UNSIGNED NOT NULL DEFAULT 0,
  showauthor    TINYINT(1)   UNSIGNED NOT NULL DEFAULT 1,
  author        VARCHAR(64)           NOT NULL DEFAULT '',
  title         VARCHAR(128)          NOT NULL DEFAULT '',
  description   TEXT,
  viewcount     INT(10)      UNSIGNED NOT NULL DEFAULT 0,
  datecreated   INT(10)      UNSIGNED NOT NULL DEFAULT 0,
  px_width      INT(10)      UNSIGNED NOT NULL DEFAULT 0,
  px_height     INT(10)      UNSIGNED NOT NULL DEFAULT 0,
  folder        VARCHAR(254) COLLATE utf8_unicode_ci NULL,
  allow_ratings TINYINT(1)   UNSIGNED NOT NULL DEFAULT 1,
  media_type    TINYINT(8)   UNSIGNED NOT NULL DEFAULT 0,
  media_url     VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  owner_id      INT(11)      UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY imageid (imageid),
  INDEX (sectionid),
  INDEX (activated),
  INDEX (media_type)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

  $DB->query('CREATE TABLE IF NOT EXISTS {p'.$pluginid."_sections} (
  sectionid     BIGINT(11)   UNSIGNED NOT NULL AUTO_INCREMENT,
  parentid      BIGINT(11)   UNSIGNED NOT NULL DEFAULT 0,
  activated     TINYINT(1)   UNSIGNED NOT NULL DEFAULT 0,
  name          VARCHAR(128)          NOT NULL DEFAULT '',
  description   TEXT                  NOT NULL,
  sorting       VARCHAR(32)           NOT NULL DEFAULT '',
  imageid       INT(10)      UNSIGNED NULL,
  datecreated   INT(10)      UNSIGNED NOT NULL DEFAULT 0,
  imagecount    INT(10)      UNSIGNED NOT NULL DEFAULT 0,
  folder        VARCHAR(254) COLLATE utf8_unicode_ci NULL,
  display_mode  INT(4)       UNSIGNED NOT NULL DEFAULT 0,
  owner_id      BIGINT(11)   NOT NULL DEFAULT 0,
  display_author      TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  display_comments    TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  display_ratings     TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  display_view_counts TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  images_per_page     SMALLINT(4) UNSIGNED NOT NULL DEFAULT 0,
  images_per_row      SMALLINT(4) UNSIGNED NOT NULL DEFAULT 0,
  access_view         TEXT COLLATE utf8_unicode_ci NULL,
  PRIMARY KEY sectionid (sectionid),
  INDEX (parentid),
  INDEX (name),
  INDEX (datecreated),
  INDEX (owner_id)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

  // Insert default section if none exists
  if(!$DB->query_first("SELECT sectionid FROM {p" . $pluginid . "_sections} LIMIT 1"))
  {
    $DB->query("INSERT INTO {p" . $pluginid . "_sections} (sectionid, parentid, activated, name, description, sorting, datecreated, display_mode, imagecount)
               VALUES (NULL, 0, 1, 'Media', '', 'newest_first', " . TIME_NOW . ", 3, 1)");
  }

  // Important! Call all available upgrade steps now:
  MediaGalleryUpgrade330();
  MediaGalleryUpgrade340();
  MediaGalleryUpgrade344();
  MediaGalleryUpgrade346();
  MediaGalleryUpgrade350(); #2012-08-27
  MediaGalleryUpgrade351(); #2013-01-04
  MediaGalleryUpgrade352(); #2013-01-14
  MediaGalleryUpgrade361(); #2013-02-02
  MediaGalleryUpgrade370(); #2013-08-19

  UpgradeMediaGalleryTemplates(); #2013-02-18

} //install


// ############################### UPGRADE PLUGIN #############################

if(!empty($currentversion) && ($currentversion != $version) && ($installtype == 'upgrade'))
{
  if(empty($pluginid)) return false;

  if(version_compare($currentversion, '3.3.1', '<'))
  {
    // Remove old, wrongly inserted CSS entries
    $DB->query("DELETE FROM {skin_css} WHERE (plugin_id = 0) AND ((var_name LIKE 'Media Gallery %') OR (var_name LIKE 'Image Gallery %'))");

    // From SD 3.0.3 upgrade: fix up image gallery settings
    // These steps are needed as we could upgrade an old SD 2.x cloned plugin!
    $DB->query("UPDATE {pluginsettings} SET groupname = 'image_gallery_settings' WHERE groupname = 'Options' AND pluginid = $pluginid");
    $DB->query("UPDATE {pluginsettings} SET title = 'image_display_mode', description = 'image_display_mode_descr' WHERE pluginid = $pluginid AND title = 'Image Display Mode'");
    $DB->query("UPDATE {pluginsettings} SET title = 'images_per_row', description = 'images_per_row_descr' WHERE pluginid = $pluginid AND title = 'Number of images per Row'");
    $DB->query("UPDATE {pluginsettings} SET title = 'images_per_page', description = 'images_per_page_descr' WHERE pluginid = $pluginid AND title = 'Images Per Page'");
    $DB->query("UPDATE {pluginsettings} SET title = 'display_view_count', description = 'display_view_count_descr' WHERE pluginid = $pluginid AND title = 'Show View Counts'");
    $DB->query("UPDATE {pluginsettings} SET title = 'display_author_name', description = 'display_author_name_descr' WHERE pluginid = $pluginid AND title = 'Show Submitted By'");
    $DB->query("UPDATE {pluginsettings} SET title = 'center_images', description = 'center_images_descr' WHERE pluginid = $pluginid AND title = 'Center Images'");
    $DB->query("UPDATE {pluginsettings} SET title = 'section_sort_order', description = 'section_sort_order_descr' WHERE pluginid = $pluginid AND title = 'Section Sort Order'");
    $DB->query("UPDATE {pluginsettings} SET title = 'display_section_image_count', description = 'display_section_image_count_descr' WHERE pluginid = $pluginid AND title = 'Display Section Image Count'");
    $DB->query("UPDATE {pluginsettings} SET title = 'section_images_per_row', description = 'section_images_per_row_descr' WHERE pluginid = $pluginid AND title = 'Number of Section Images per Row'");
    $DB->query("UPDATE {pluginsettings} SET title = 'enable_comments', description = 'enable_comments_descr' WHERE pluginid = $pluginid AND title = 'Enable Comments'");

    $DB->query("UPDATE {pluginsettings} SET groupname = 'midsize_settings' WHERE groupname = 'MidSize Options' AND pluginid = $pluginid");
    $DB->query("UPDATE {pluginsettings} SET title = 'create_midsize_images', description = 'create_midsize_images_descr' WHERE pluginid = $pluginid AND title = 'Create MidSize Images'");
    $DB->query("UPDATE {pluginsettings} SET title = 'save_fullsize_images', description = 'save_fullsize_images_descr' WHERE pluginid = $pluginid AND title = 'Keep FullSize Images'");
    $DB->query("UPDATE {pluginsettings} SET title = 'max_midsize_width', description = 'max_midsize_width_descr' WHERE pluginid = $pluginid AND title = 'Max MidSize Width'");
    $DB->query("UPDATE {pluginsettings} SET title = 'max_midsize_height', description = 'max_midsize_height_descr' WHERE pluginid = $pluginid AND title = 'Max MidSize Height'");
    $DB->query("UPDATE {pluginsettings} SET title = 'square_off_midsize', description = 'square_off_midsize_descr' WHERE pluginid = $pluginid AND title = 'Square Off MidSize'");

    $DB->query("UPDATE {pluginsettings} SET groupname = 'thumbnail_settings' WHERE groupname = 'Thumbnail Options' AND pluginid = $pluginid");
    $DB->query("UPDATE {pluginsettings} SET title = 'auto_create_thumbs', description = 'auto_create_thumbs_descr' WHERE pluginid = $pluginid AND title = 'Image Resizing'");
    $DB->query("UPDATE {pluginsettings} SET title = 'max_thumb_width', description = 'max_thumb_width_descr' WHERE pluginid = $pluginid AND title = 'Max Thumbnail Width'");
    $DB->query("UPDATE {pluginsettings} SET title = 'max_thumb_height', description = 'max_thumb_height_descr' WHERE pluginid = $pluginid AND title = 'Max Thumbnail Height'");
    $DB->query("UPDATE {pluginsettings} SET title = 'square_off_thumbs', description = 'square_off_thumbs_descr' WHERE pluginid = $pluginid AND title = 'Square off Thumbnails'");

    $DB->query("UPDATE {pluginsettings} SET groupname = 'upload_settings' WHERE groupname = 'Upload Options' AND pluginid = $pluginid");
    $DB->query("UPDATE {pluginsettings} SET title = 'allowed_image_types', description = 'allowed_image_types_descr' WHERE pluginid = $pluginid AND title = 'Allowed Image Types'");
    $DB->query("UPDATE {pluginsettings} SET title = 'max_upload_size', description = 'max_upload_size_descr' WHERE pluginid = $pluginid AND title = 'Max Image Upload Size'");
    $DB->query("UPDATE {pluginsettings} SET title = 'image_notification', description = 'image_notification_descr' WHERE pluginid = $pluginid AND title = 'Image Notification'");
    $DB->query("UPDATE {pluginsettings} SET title = 'auto_approve_images', description = 'auto_approve_images_descr' WHERE pluginid = $pluginid AND title = 'Auto Approve Images'");
    $DB->query("UPDATE {pluginsettings} SET title = 'image_title_required', description = 'image_title_required_descr' WHERE pluginid = $pluginid AND title = 'Upload Requires Title'");
    $DB->query("UPDATE {pluginsettings} SET title = 'image_description_required', description = 'image_description_required_descr' WHERE pluginid = $pluginid AND title = 'Upload Requires Description'");
    $DB->query("UPDATE {pluginsettings} SET title = 'image_author_required', description = 'image_author_required_descr' WHERE pluginid = $pluginid AND title = 'Upload Requires Author'");
    $DB->query("UPDATE {pluginsettings} SET title = 'allow_root_upload', description = 'allow_root_upload_descr' WHERE pluginid = $pluginid AND title = 'Allow Root Section Upload'");
    //SD 3.0.3 - end

    $DB->query("UPDATE {pluginsettings} SET groupname = 'upload_settings' WHERE pluginid = $pluginid AND groupname = 'Upload Options'");
    $DB->query("UPDATE {pluginsettings} SET groupname = 'thumbnail_settings' WHERE pluginid = $pluginid AND groupname = 'Thumbnail Options'");
    $DB->query("UPDATE {pluginsettings} SET groupname = 'midsize_options' WHERE pluginid = $pluginid AND groupname = 'MidSize Options'");
    $DB->query("UPDATE {pluginsettings} SET groupname = 'sections_settings' WHERE pluginid = $pluginid AND groupname = 'Section Options'");
    $DB->query("UPDATE {pluginsettings} SET groupname = 'sections_settings' WHERE pluginid = $pluginid AND title IN ('section_images_per_row', 'display_section_image_count')");
    $DB->query("UPDATE {pluginsettings} SET groupname = 'admin_settings' WHERE pluginid = $pluginid AND groupname = 'Admin Options'");
    $DB->query("DELETE FROM {pluginsettings} WHERE pluginid = $pluginid AND title in ('Allow Root Upload', 'allow_root_upload')");
    $DB->query("DELETE FROM {pluginsettings} WHERE pluginid = $pluginid AND groupname = 'sections_settings' AND title IN ('Allow Root Section Upload', 'allow_root_section_upload')");
    $DB->query("UPDATE {p".$pluginid."_sections} SET sorting = 'alpha_az' WHERE sorting = 'Alphabetically A-Z'");
    $DB->query("UPDATE {p".$pluginid."_sections} SET sorting = 'alpha_za' WHERE sorting = 'Alphabetically Z-A'");
    $DB->query("UPDATE {p".$pluginid."_sections} SET sorting = 'newest_first' WHERE sorting = 'Newest First'");
    $DB->query("UPDATE {p".$pluginid."_sections} SET sorting = 'oldest_first' WHERE sorting = 'Oldest First'");
    $DB->query("UPDATE {p".$pluginid."_sections} SET sorting = 'author_name_az' WHERE sorting = 'Author Name A-Z'");
    $DB->query("UPDATE {p".$pluginid."_sections} SET sorting = 'author_name_za' WHERE sorting = 'Author Name Z-A'");

    // Convert all plugin settings to new naming method to allow translation
    ConvertPluginSettings($pluginid);

    // Update plugin's core settings:
    if(version_compare($mainsettings['sdversion'], '3.4.1', 'ge') && $DB->column_exists(PRGM_TABLE_PREFIX.'plugins','base_plugin'))
    {
      $DB->query("UPDATE {plugins} SET base_plugin = 'Media Gallery' WHERE pluginid = $pluginid");
    }
    MediaGalleryUpgrade330();

    UpdatePluginVersion($pluginid, '3.3.1');
    $currentversion = '3.3.1';
  }

  if(version_compare($currentversion,'3.4.1','<='))
  {
    MediaGalleryUpgrade340();

    $CSS = new CSS();
    if($pluginid == 17)
    {
      $CSS->ReplaceCSS('Image Gallery', 'plugins/'.$plugin_folder.'/css/styles.css', false);
    }
    else
    {
      $CSS->InsertCSS('Media Gallery ('.$pluginid.')', 'plugins/'.$plugin_folder.'/css/styles.css', true,
                      array(
                      'p17_' => 'p'.$pluginid.'_',
                      'p17-' => 'p'.$pluginid.'-',
                      'gallery_containment' => 'gallery'.$pluginid.'_containment',
                      'mb_containment' => 'mb'.$pluginid.'_containment',
                      ), $pluginid);
    }
    unset($CSS);

    $DB->query("UPDATE {plugins} SET name = '%s', settings = %d, pluginpath = '%s', settingspath = '%s', authorname = '%s'
               WHERE pluginid = %d",
               $pluginname.($pluginid!=17?' ('.$pluginid.')':''), $pluginsettings,
               $pluginpath, $settingspath, $DB->escape_string($authorname),$pluginid);

    UpdatePluginVersion($pluginid, '3.4.2');
    $currentversion = '3.4.2';
  }

  if($currentversion == '3.4.2')
  {
    MediaGalleryUpgrade344();

    UpdatePluginVersion($pluginid, '3.4.4');
    $currentversion = '3.4.4';
  }

  if($currentversion == '3.4.4')
  {
    MediaGalleryUpgrade346();

    UpdatePluginVersion($pluginid, '3.4.6');
    $currentversion = '3.4.6';
  }

  if($currentversion == '3.4.6')
  {
    MediaGalleryUpgrade350();

    // Update plugin's tables:
    $DB->query("ALTER TABLE {p".$pluginid."_images} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci");
    $DB->query("ALTER TABLE {p".$pluginid."_sections} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci");

    $DB->query("DELETE FROM {skin_css} WHERE var_name LIKE 'Media Gallery %' AND plugin_id = 0");
    $DB->query("DELETE FROM {skin_css} WHERE var_name LIKE 'Image Gallery %' AND plugin_id = 0");
    $DB->query("UPDATE {pluginsettings} SET groupname = 'media_gallery_settings'
                WHERE pluginid = %d AND groupname IN ('image_gallery_settings', 'Options')", $pluginid);

    UpdatePluginVersion($pluginid, '3.5.0');
    $currentversion = '3.5.0';
  }

  if($currentversion == '3.5.0')
  {
    MediaGalleryUpgrade351();

    UpdatePluginVersion($pluginid, '3.5.1');
    $currentversion = '3.5.1';
  }

  if($currentversion == '3.5.1')
  {
    MediaGalleryUpgrade352();

    UpdatePluginVersion($pluginid, '3.5.2');
    $currentversion = '3.5.2';
  }

  if(version_compare($currentversion,'3.6.1','le'))
  {
    MediaGalleryUpgrade361();
    UpgradeMediaGalleryTemplates();

    UpdatePluginVersion($pluginid, '3.6.1');
    $currentversion = '3.6.1';
  }

  if($currentversion == '3.6.1')
  {
    MediaGalleryUpgrade370();

    UpdatePluginVersion($pluginid, '3.7.0');
    $currentversion = '3.7.0';
  }

  if($pluginid==17)
  {
    $DB->query("UPDATE {plugins} SET name = 'Image Gallery' WHERE pluginid = 17");
  }

} //upgrade


// ############################## UNINSTALL PLUGIN ############################

if(($installtype == 'uninstall') && ($pluginid >= 17))
{
  $DB->query('DROP TABLE IF EXISTS {p'.$pluginid.'_images}');
  $DB->query('DROP TABLE IF EXISTS {p'.$pluginid.'_sections}');
}
