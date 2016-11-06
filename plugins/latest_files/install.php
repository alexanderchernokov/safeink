<?php
if(!defined('IN_PRGM')) exit();

// ####################### DETERMINE CURRENT DIRECTORY ########################
$plugin_folder = sd_GetCurrentFolder(__FILE__);

// ############################################################################
// PLUGIN INFORMATION
// ############################################################################
// Do NOT set $pluginid or $uniqueid here!
$pluginname     = 'Latest Files'; // formerly known: p27
$version        = '3.4.0';
$pluginpath     = $plugin_folder . '/latest_files.php';
$settingspath   = $plugin_folder . '/settings.php';
$authorname     = 'subdreamer_web';
$authorlink     = 'http://www.subdreamer.com';
$pluginsettings = '17'; /*view/admin*/


// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,6))
{
  if(strlen($installtype))
  {
    echo $pluginname.': you need to upgrade to '.PRGM_NAME.' v3.6+ to use this plugin.<br />';
  }
  return false;
}

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

// ########################## UPGRADE TO 1.2.0 ################################

if(!function_exists('latestfiles_Upgrade120'))
{
  function latestfiles_Upgrade120()
  {
    global $DB, $pluginid;
    // Latest Files 1.2.0 Changes
    // + New setting to display the updated/uploaded date
    // * Fix for strict MySQL mode upon install/clone
    // * Using the new SQL table referencing
    // * Added "Untitled" as translatable phrase

    InsertPluginSetting($pluginid, 'Options', 'Display File Date', 'Display the day the file was uploaded/updated?', 'yesno', '0', 5);
    InsertPluginSetting($pluginid, 'Options', 'File Date Format', 'Format of the date display (leave empty to use Subdreamer default)?', 'text', 'F j, Y', 6);
    InsertPhrase($pluginid, 'untitled', 'Untitled');

  } //latestfiles_Upgrade120
}


// ########################## UPGRADE TO 3.4.0 ################################

if(!function_exists('latestfiles_Upgrade340'))
{
  function latestfiles_Upgrade340()
  {
    global $DB, $pluginid;
    // Latest Files 3.4.0 Changes
    // + Settings: added selection for DLM plugins to display entries from
    // Setting is declared as "text" to store a comma-separated list of IDs

    InsertAdminPhrase($pluginid, 'error_no_dlm', 'Could not find any Download Manager plugin!');
    InsertAdminPhrase($pluginid, 'dlm_selection', 'Download Manager Selection');
    InsertPluginSetting($pluginid, 'dlm_selection', 'Source Plugin', 'From which Download Manager plugin should the latest files be displayed?', 'text', '', 0);

  } //latestfiles_Upgrade340
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

  // plugin settings
  InsertPluginSetting($pluginid, 'Options', 'Number of Files to Display',      'Please enter the the maximum number of files to be displayed:',                'text',  '5', 1);
  InsertPluginSetting($pluginid, 'Options', 'Exclude Sections',                'You can exclude sections of the Download Manager by entering their ids here:', 'text',  '',  2);
  InsertPluginSetting($pluginid, 'Options', 'Display Author Name',             'Would you like the file\'s author\'s name to be displayed?',                   'yesno', '1', 3);
  InsertPluginSetting($pluginid, 'Options', 'Display Author Name on New Line', 'Display the Author\'s name on a new line?',                                    'yesno', '0', 4);

  // plugin language
  InsertPhrase($pluginid, 'by', 'by');

  // Process ALL plugin upgrades now:
  latestfiles_Upgrade120();
  latestfiles_Upgrade340();
}


// ############################## UPGRADE PLUGIN ###############################

if(!empty($currentversion) && ($currentversion != $version) && ($installtype == 'upgrade'))
{
  // --------------------------------------------------------------
  // *** IMPORTANT: ***
  // --------------------------------------------------------------
  if(empty($pluginid)) return false;

  // upgrade to 1.1
  if($currentversion == '1.0')
  {
    UpdatePluginVersion($pluginid, '1.1');
    $currentversion = '1.1';
  }
  if($currentversion == '1.1')
  {
    // + If a file has no title, 'Untitled' will be shown.
    // + Files are also ordered by 'date updated'
    $DB->query('UPDATE {plugins} SET settings = %d WHERE pluginid = %d',$pluginsettings,$pluginid);
    UpdatePluginVersion($pluginid, '1.1.1');
    $currentversion = '1.1.1';
  }
  if($currentversion == '1.1.1')
  {
    // No DB changes
    UpdatePluginVersion($pluginid, '1.1.2');
    $currentversion = '1.1.2';
  }
  if($currentversion == '1.1.2')
  {
    latestfiles_Upgrade120();
    UpdatePluginVersion($pluginid, '1.2.0');
    $currentversion = '1.2.0';
  }
  if($currentversion == '1.2.0')
  {
    // No DB changes
    UpdatePluginVersion($pluginid, '3.2.1');
    $currentversion = '3.2.1';
  }
  if($currentversion == '3.2.1')
  {
    // + Settings enhanced to select DLM plugins from which to show entries
    // No DB changes
    latestfiles_Upgrade340();
    UpdatePluginVersion($pluginid, '3.4.0');
    $currentversion = '3.4.0';
  }
}

// ############################## UNINSTALL PLUGIN #############################

if($installtype == 'uninstall')
{
  // no tables need to be uninstalled for this plugin
}
