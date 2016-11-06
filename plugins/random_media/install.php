<?php
if(!defined('IN_PRGM')) return;

// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,6))
{
  if(strlen($installtype))
  {
    echo 'Random Media: you need to upgrade to '.PRGM_NAME.' v3.6+ to use this plugin.<br />';
  }
  return false;
}

// ####################### DETERMINE CURRENT DIRECTORY ########################
$plugin_folder = sd_GetCurrentFolder(__FILE__);

// ############################ PLUGIN INFORMATION ############################
$pluginname   = 'Random Media';
$version      = '3.7.0';
$pluginpath   = $plugin_folder . '/randompic.php';
$settingspath = $plugin_folder . '/settings.php';
$authorname   = 'subdreamer_web';
$authorlink   = 1;
$pluginsettings = '17'; /*view/admin*/

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

// ############################# INSTALL PLUGIN ###############################

if($installtype == 'install')
{
  // At this point SD3 has to provide a new plugin id
  if(empty($pluginid))
  {
    $pluginid = CreatePluginID($pluginname);
  }

  // If for whatever reason the plugin id is invalid, return false NOW!
  if(empty($pluginid))
  {
    return false;
  }

  InsertPluginSetting($pluginid, 'Options', 'Allowed File Types', 'Enter a comma separated list of allowed file types (eg JPEG,JPG,PNG):', 'text', 'gif,jpg,jpeg,png', 10);
  InsertPluginSetting($pluginid, 'Options', 'Image Source', 'Use an images folder path (yes) or a Image/Media Gallery plugin (No) as the source?'.
    '<br />For galleries only: if available, the thumbnail of the image will be displayed.', 'yesno', '1', 20);
  InsertPluginSetting($pluginid, 'Options', 'Images Folder Path',
    'Enter the path from where the plugin should pick a random image file (default: images/):<br />This must be a relative path to the CMS root folder!', 'text', 'images/', 30);
  InsertPluginSetting($pluginid, 'Options', 'Gallery Plugin', 'What is the plugin ID of the Image/Media Gallery (default: 17)?', 'text', '17', 40);
  InsertPluginSetting($pluginid, 'Options', 'Border', 'Would you like a border around the random image (default: No)?', 'yesno', '0', 50);
  InsertPluginSetting($pluginid, 'Options', 'Border Color', 'If you selected yes for the border option, then please select a color (default: #000000):', 'text', '#000000', 60);
  InsertPluginSetting($pluginid, 'Options', 'Specify Image Width', 'Set the max. width of the displayed image (for CSS)?<br />".
    "Leave <strong>empty</strong> or enter e.g. <strong>100px</strong> or <strong>10%</strong>.', 'text', '', 70);
  InsertPluginSetting($pluginid, 'Options', 'Show Image Author', 'Display author of the image if source is a gallery plugin (default: No)?', 'yesno', '0', 80);
  InsertPluginSetting($pluginid, 'Options', 'Show Image Title', 'Display the title of the image for gallery plugin images (default: No)?', 'yesno', '0', 90);

  InsertPhrase($pluginid, 'by', 'by');
  InsertPhrase($pluginid, 'no_images_available', 'No image available<br />');
  InsertPhrase($pluginid, 'gallery_not_found', 'Gallery not available.<br />');

} //install


// ############################# UPGRADE PLUGIN ###############################

if($installtype == 'upgrade')
{
  if(version_compare($currentversion, '3.7.0', '<'))
  {
    UpdatePluginVersion($pluginid, '3.7.0');
    $currentversion = '3.7.0';
  }
}

// ############################# UNINSTALL PLUGIN #############################

if($installtype == 'uninstall')
{
  // no tables to delete
}
