<?php

if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,4))
{
  if(strlen($installtype))
  {
    echo 'Tagcloud: you need to upgrade to '.PRGM_NAME.' v3.4+ to use this plugin.<br />';
  }
  return false;
}

// ####################### DETERMINE CURRENT DIRECTORY ########################
$plugin_folder  = sd_GetCurrentFolder(__FILE__);

// ############################################################################
// PLUGIN INFORMATION
// ############################################################################
// Do NOT set $uniqueid or $pluginid here!

$pluginname     = 'Tagcloud';
$version        = '3.4.4';
$pluginpath     = $plugin_folder.'/tagcloud.php';
$settingspath   = $plugin_folder.'/tagcloud_settings.php';
$authorname     = 'subdreamer_web';
$authorlink     = 'http://www.subdreamer.com';
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


// ############################################################################
// INSTALL PLUGIN
// ############################################################################

if(($installtype == 'install') && empty($pluginid))
{
  // At this point SD3 has to provide a new plugin id
  $pluginid = CreatePluginID($pluginname);

  // If for whatever reason the plugin id is invalid, return false NOW!
  if(empty($pluginid))
  {
    return false;
  }

  // Admin language (must be first here!)
  InsertAdminPhrase($pluginid, 'tagcloud_settings', 'Tagcloud Settings', 2);
  InsertAdminPhrase($pluginid, 'manual_output_settings', 'Manual Output Settings', 2);

  // plugin settings
  InsertPluginSetting($pluginid, 'tagcloud_settings', 'Display Limit',
    'The Tagcloud plugin will display a list of the most appearing tags for all or a selection of plugins.
    Enter the max. number of tags to be shown:', 'text', '10', 10);
  InsertPluginSetting($pluginid, 'tagcloud_settings', 'Sorting',
    'How are the tags to be sorted for display (default: alphabetically)?',
    "select:\r\n0|Alphabetically A-Z\r\n1|Alphabetically Z-A", '0', 20);
    # maybe later:
    #\r\n2|Heaviest First\r\n3|Lightest First\r\n4|Random
  InsertPluginSetting($pluginid, 'tagcloud_settings', 'Source Plugins',
    'Select the plugins, whose tags are to be displayed (default: Articles)?',
    'plugins:multiple', '2', 50);

  InsertPluginSetting($pluginid, 'manual_output_settings', 'HTML Container Start',
    'HTML code BEFORE the tagcloud output (e.g. an opening DIV or P element):<br />'.
    ' Default: <strong>'.htmlspecialchars('<div class="tagcloud">').'</strong><br />'.
    ' This can contain <strong>[tags_title]</strong> to be replaced by same phrase.',
    'textarea', '<div class="tagcloud">', 200);
  InsertPluginSetting($pluginid, 'manual_output_settings', 'HTML Container End',
    'HTML code AFTER the tagcloud output (e.g. closing DIV or P element):<br />'.
    ' Default: <strong>'.htmlspecialchars('</div>').'</strong>',
    'textarea', '</div>', 210);
  InsertPluginSetting($pluginid, 'manual_output_settings', 'HTML Tag Template',
    'Full HTML code template for each tag item displayed, containing <strong>[taglink]</strong> and <strong>[tagname]</strong> macros:<br />'.
    ' Note: for UL/OL lists this must include the corresponding <strong>&lt;li&gt;</strong> HTML tags surrounding the link!<br />'.
    ' Default: <strong>'.htmlspecialchars('<a class="[tagclass]" href="[taglink]">[tagname]</a>').'</strong>',
    'textarea', '<a class="[tagclass]" href="[taglink]">[tagname]</a>', 220);
  InsertPluginSetting($pluginid, 'manual_output_settings', 'HTML Tag Separator',
    'Text/HTML code to separate individual tags from another (optional; default: comma):<br />'.
    ' For example this can be left empty or use comma or a valid BR tag to separate tags.',
    'textarea', ', ', 230);

  InsertPhrase($pluginid, 'tags_title', 'Tags');

} //install


// ############################################################################
// UPGRADE PLUGIN
// ############################################################################

if(!empty($currentversion) && ($installtype == 'upgrade'))
{
  if(empty($pluginid)) return false;

  if(version_compare($currentversion, '3.4.4', '<'))
  {
    // Update plugin with new settings
    $DB->query('UPDATE {plugins} SET settings = %d WHERE pluginid = %d',$pluginsettings,$pluginid);
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX."pluginsettings SET input = 'plugins:multiple' WHERE pluginid = %d AND title = 'source_plugins'", $pluginid);

    UpdatePluginVersion($pluginid, '3.4.4');
    $currentversion = '3.4.4';
  }

} //upgrade
