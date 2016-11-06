<?php
if(!defined('IN_PRGM')) exit();

// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,6))
{
  if(strlen($installtype))
  {
    echo 'Latest Images: you need to upgrade to '.PRGM_NAME.' v3.6+ to use this plugin.<br />';
  }
  return false;
}

// ####################### DETERMINE CURRENT DIRECTORY ########################
$plugin_folder = sd_GetCurrentFolder(__FILE__);

// ########################### PLUGIN INFORMATION #############################
$pluginname   = 'Latest Images';
$version      = '3.7.0';
$pluginpath   = $plugin_folder . '/latest_images.php';
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

// ############################## INSTALL PLUGIN ###############################

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

  $CSS = new CSS();
  $CSS->InsertCSS($pluginname.' ('.$pluginid.')', 'plugins/'.$plugin_folder.'/css/styles.css', true, array('pXXX_' => 'p'.$pluginid.'_'), $pluginid);
  unset($CSS);

  InsertPhrase($pluginid, 'options', 'Options');
  InsertPhrase($pluginid, 'source_plugin', 'Source Plugin');

  // plugin settings
  InsertPluginSetting($pluginid, 'source_plugin', 'Source Plugin', 'From which plugin should the latest images be displayed (id of Image Gallery or a Media Gallery plugin)?', 'text', '17', 0);
  InsertPluginSetting($pluginid, 'options', 'Number of Images to Display', 'Please enter the the maximum number of images to be displayed:', 'text',  '5', 10);
  InsertPluginSetting($pluginid, 'options', 'Images per Row', 'Enter the number of images to be displayed per row:', 'text', '1', 15);
  InsertPluginSetting($pluginid, 'options', 'Exclude Sections', 'You can exclude sections of the Image Gallery by entering their ids here:', 'text',  '',  20);
  InsertPluginSetting($pluginid, 'options', 'Display Image Name', 'Would you like the image\'s name to be displayed?', 'yesno', '1', 30);
  InsertPluginSetting($pluginid, 'options', 'Display Image Date', 'Display the date of the image when the image was uploaded (default: No)?', 'yesno', '0', 35);
  InsertPluginSetting($pluginid, 'options', 'Image Date Format', 'Format of the date display (leave empty to use default)?', 'text', 'F j, Y', 38);
  InsertPluginSetting($pluginid, 'options', 'Display Thumbnail', 'Would you like the image\'s thumbnail to be displayed?', 'yesno', '1', 40);
  InsertPluginSetting($pluginid, 'options', 'Display Author Name', 'Would you like the author\'s name of the image to be displayed?', 'yesno', '1', 50);
  InsertPluginSetting($pluginid, 'options', 'Display Author Name on New Line', 'Display the author\'s name on a new line?', 'yesno', '0', 55);

  // v2.0.0:
  InsertPhrase($pluginid, 'by', 'by');
  InsertPhrase($pluginid, 'error_gallery_offline', 'We are sorry, the <strong>\"gallery\"</strong> is currently offline.');
  InsertPhrase($pluginid, 'untitled', 'Untitled');
}

// ############################## UPGRADE PLUGIN ##############################

if(!empty($currentversion) && ($currentversion != $version) && ($installtype == 'upgrade'))
{
  if(version_compare($currentversion, '3.7.0', '<'))
  {
    UpdatePluginVersion($pluginid, '3.7.0');
    $currentversion = '3.7.0';
  }
}

// ############################## UNINSTALL PLUGIN ############################

if($installtype == 'uninstall')
{
  // no action required here
}
