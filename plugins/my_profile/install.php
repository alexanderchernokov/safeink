<?php
/**
 * Created by PhpStorm.
 * User: Alex.Chernokov
 * Date: 24/10/2016
 * Time: 10:51
 */
if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,4))
{
    if(strlen($installtype))
    {
        echo 'Subcategory Menu: you need to upgrade to '.PRGM_NAME.' v3.4+ to use this plugin.<br />';
    }
    return false;
}

// ####################### DETERMINE CURRENT DIRECTORY ########################
$plugin_folder  = sd_GetCurrentFolder(__FILE__);

// ############################################################################
// PLUGIN INFORMATION
// ############################################################################

$pluginname     = 'My Profile';
$version        = '1.0';
$pluginpath     = $plugin_folder.'/my_profile.php';
$settingspath   = $plugin_folder.'/settings.php';
$authorname     = 'Alexander Chernokov';
$authorlink     = 0;
$pluginsettings = '';

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

    /*InsertAdminPhrase($pluginid,'subcategory_options','Subcategory Options');

    // install plugin settings
    InsertPluginSetting($pluginid, 'subcategory_options', 'Subcategory Separator', 'How would you like your subcategories to be separated?', 'text', '<br />', 1);
    InsertPluginSetting($pluginid, 'subcategory_options', 'Custom Link Styles',    'If set to yes, then the subcategories will use the CSS styles defined in this page instead of the styles from your current Subdreamer skin:' , 'yesno', '0', 2);
    InsertPluginSetting($pluginid, 'subcategory_options', 'Default Link Style',    'CSS style of the default subcategory link:\r\n<br />Example: <i>font: 11px verdana; color: #6E8195;</i>', 'text', 'font: 11px verdana; color: #6E8195;', 3);
    InsertPluginSetting($pluginid, 'subcategory_options', 'Active Link Style',     'CSS style of the active subcategory link:', 'text', 'font: 11px verdana; color: #6E8195;', 4);
    InsertPluginSetting($pluginid, 'subcategory_options', 'Visited Link Style',    'CSS style of the visited subcategory link:', 'text', 'font: 11px verdana; color: #6E8195;', 5);
    InsertPluginSetting($pluginid, 'subcategory_options', 'Hover Link Style',      'CSS style of the hover subcategory link:', 'text', 'font: 11px verdana; color: #6E8195;', 6);
    InsertPluginSetting($pluginid, 'subcategory_options', 'Display Usergroup Permission', 'Include a subcategory for display if a usergroup has permission for:',
      "select\r\n0|Display in Menu\r\n1|Allow View\r\n2|Both", 0, '70');
    */
}

// ############################################################################
// UPGRADE PLUGIN
// ############################################################################
/*
if(!empty($currentversion) && ($currentversion != $version) && ($installtype == 'upgrade'))
{
  if(empty($pluginid)) return false;

  if(version_compare($currentversion, '3.2.2', '<'))
  {
    // Convert all plugin settings to new naming method to allow translation
    ConvertPluginSettings($pluginid);

    UpdatePluginVersion($pluginid, '3.2.2');
    $currentversion = '3.2.2';
  }
  if(version_compare($currentversion,'3.6.0','<'))
  {
    // Update plugin with new settings
    $DB->query('UPDATE {plugins} SET settings = %d WHERE pluginid = %d',$pluginsettings,$pluginid);

    InsertAdminPhrase($pluginid,'subcategory_options','Subcategory Options');

    // Remove duplicate "old" settings row (since v3.4.1)
    $rows = $DB->query_first('SELECT COUNT(*) rowcnt'.
                             ' FROM {pluginsettings} WHERE pluginid = '.$pluginid.
                             " AND ((title = 'Display Usergroup Permission') OR ".
                             "      (title = 'display_usergroup_permission')) ");
    if($rows['rowcnt'] > 1)
    {
      // Get current value:
      $old_subcat = $DB->query_first('SELECT value FROM {pluginsettings}'.
                                    ' WHERE pluginid = '.$pluginid.
                                    " AND ((title = 'Display Usergroup Permission') OR ".
                                    "      (title = 'display_usergroup_permission')) ".
                                    ' ORDER BY settingid LIMIT 0,1');
      // Remove settings:
      $DB->query('DELETE FROM {pluginsettings} WHERE pluginid = '.$pluginid.
                 " AND ((title = 'Display Usergroup Permission') OR ".
                 "      (title = 'display_usergroup_permission')) ");

      // Reinsert setting:
      InsertPluginSetting($pluginid,'subcategory_options','Display Usergroup Permission',
        'Include a subcategory for display if a usergroup has permission for:',
        "select:\r\n0|Display in Menu\r\n1|Allow View\r\n2|Both",
        (isset($old_subcat['value'])?(int)$old_subcat['value']:0), '70');

    }
    $DB->query("UPDATE ".PRGM_TABLE_PREFIX."pluginsettings SET groupname = 'subcategory_options'".
               " WHERE pluginid = $pluginid AND groupname = 'Subcategory Options'");

    UpdatePluginVersion($pluginid, '3.6.0');
    $currentversion = '3.6.0';
  }

}
*/