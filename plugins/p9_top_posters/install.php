<?php

if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,7))
{
  if(strlen($installtype))
  {
    echo 'Top Posters: you need to upgrade to '.PRGM_NAME.' v3.7+ to use this plugin.<br />';
  }
  return false;
}

// ###################### DETERMINE CURRENT DIRECTORY #########################
$plugin_folder = sd_GetCurrentFolder(__FILE__);

// ############################################################################
// PLUGIN INFORMATION
// ############################################################################

$uniqueid       = 9;
$pluginname     = 'Top Posters';
$version        = '3.7.0';
$pluginpath     = 'p9_top_posters/topposters.php';
$settingspath   = 'p9_top_posters/p9_settings.php';
$authorname     = 'subdreamer_web';
$authorlink     = 'http://www.subdreamer.com';
$pluginsettings = '17';


// ############################################################################
// INSTALL PLUGIN
// ############################################################################

if($installtype == 'install')
{
  $p9_CSS = new CSS();
  $p9_CSS->InsertCSS($pluginname, 'plugins/'.$plugin_folder.'/css/default.css', true, array(), $uniqueid); #2013-10-05
  unset($p9_CSS);

  InsertPluginSetting($uniqueid, 'Options', 'Number of Top Posters to Display', 'Please enter the the maximum number of top posters to be displayed:', 'text', '5', 1);
  InsertPluginSetting($uniqueid, 'Options', 'Display Avatar',      'Display user avatars (if available)?', 'yesno', '0', 2);
  InsertPluginSetting($uniqueid, 'Options', 'Avatar Image Width',  'Avatar image <strong>width</strong> in Pixels for sizing the image horizontally (default: 80)?<br />Example: <strong>80</strong><br />If empty, no width tag is being used and the image will appear in full width.', 'text', '20', 4);
  InsertPluginSetting($uniqueid, 'Options', 'Avatar Image Height', 'Avatar image <strong>height</strong> in Pixels for sizing the image vertically (default: 80)?<br />Example: <strong>80</strong><br />If empty, no height tag is being used and the image will appear in full height.', 'text', '20', 5);

  InsertPhrase($uniqueid, 'user',  'User:');
  InsertPhrase($uniqueid, 'posts', 'Posts:');
}


// ############################################################################
// UPGRADES
// ############################################################################

if(!empty($currentversion) && ($currentversion != $version) && ($installtype == 'upgrade'))
{
  if(substr($currentversion,0,2) != '3.')
  {
    // Convert all plugin settings to new naming method to allow translation
    ConvertPluginSettings($uniqueid);
    UpdatePluginVersion($uniqueid, '3.2.0');
    $currentversion = '3.2.0';
  }
  if(version_compare($currentversion,'3.7.0','<')) #2013-10-05
  {
    $p9_CSS = new CSS();
    $p9_CSS->InsertCSS($pluginname, 'plugins/'.$plugin_folder.'/css/default.css', true, array(), $uniqueid);
    unset($p9_CSS);
    UpdatePluginVersion($uniqueid, '3.7.0');
    $currentversion = '3.7.0';
  }
}

// ############################################################################
// UNINSTALL PLUGIN
// ############################################################################

if($installtype == 'uninstall')
{
  // no tables to uninstall
}
