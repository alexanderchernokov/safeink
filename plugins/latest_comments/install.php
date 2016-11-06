<?php
if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,4))
{
  if(strlen($installtype))
  {
    echo 'Latest Comments: you need to upgrade to '.PRGM_NAME.' v3.6+ to use this plugin.<br />';
  }
  return false;
}

// ####################### DETERMINE CURRENT DIRECTORY ########################
$plugin_folder  = sd_GetCurrentFolder(__FILE__);

// ############################################################################
// PLUGIN INFORMATION
// ############################################################################
// Do NOT set $uniqueid or $pluginid here!

$pluginname     = 'Latest Comments';
$version        = '3.7.0';
$pluginpath     = $plugin_folder.'/latest_comments.php';
$settingspath   = $plugin_folder.'/settings.php';
$authorname     = 'subdreamer_web';
$authorlink     = 'http://www.subdreamer.com';
$pluginsettings = '17'; #view+admin

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
  if(empty($pluginid)) return false;

  InsertPluginSetting($pluginid, 'options', 'Comment Source Plugins', 'To display comments from one or multiple plugins, enter each plugin id, separated by comma.<br />Default plugin: 2 = Articles', 'text', '2', 10);
  InsertPluginSetting($pluginid, 'options', 'Comment Limit',          'How many comments would you like to be shown?', 'text', '10', 20);
  InsertPluginSetting($pluginid, 'options', 'Limit Comment Length',   'Enter the maximum number of characters before a comment is cut off.<br />0 = Unlimited', 'text', '0', 30);
  InsertPluginSetting($pluginid, 'options', 'Display Comment',        'Display the comment?', 'yesno', '1', 40);
  InsertPluginSetting($pluginid, 'options', 'Display Username',       'Display the username of the comment?', 'yesno', '1', 50);
  InsertPluginSetting($pluginid, 'options', 'Display Date',           'Display the date when the comment was posted?', 'yesno', '1', 60);
  InsertPluginSetting($pluginid, 'options', 'Display Source Title',   'Display the title of the comment\'s source (default: Yes)?<br />This could be an article or image title.', 'yesno', '1', 70);
  InsertPluginSetting($pluginid, 'options', 'Source Link',            'Display the source as a link to the comment (default: Yes)?<br />This will link to the article or image etc. where the user posted the comment.', 'yesno', '1', 80);
  InsertPluginSetting($pluginid, 'options', 'Page Link',              'Display link to the source plugin\'s page (default: No)?', 'yesno', '0', 90);
  InsertPluginSetting($pluginid, 'options', 'Display Avatar',         'Display the avatar for every user (default: Yes)?', 'yesno', '1', 100);
  InsertPluginSetting($pluginid, 'options', 'Avatar Width',           'Avatar image width in pixels (keep empty or 0 to use default)?', 'text', '0', 102);
  InsertPluginSetting($pluginid, 'options', 'Avatar Height',          'Avatar image height in pixels (keep empty or 0 to use default)?', 'text', '0', 104);
  InsertPluginSetting($pluginid, 'options', 'Avatar Column',          'Display avatar in a separate column (Yes) or should the comments flow around the avatar (No)?<br />Default: Yes', 'yesno', '1', 106);
  InsertPluginSetting($pluginid, 'options', 'Display Pagination',     'Display a list of pages to browse through all comments (default: Yes)?', 'yesno', '1', 110);

  $CSS = new CSS();
  $CSS->InsertCSS('Latest Comments ('.$pluginid.')', 'plugins/'.$plugin_folder.'/css/default.css', true, null, $pluginid);
  unset($CSS);
}

// ############################################################################
// UPGRADE PLUGIN
// ############################################################################

if(!empty($currentversion) && ($currentversion != $version) && ($installtype == 'upgrade'))
{
  if(empty($pluginid)) return false;

  // upgrade to 3.3.0
  if(substr($currentversion,0,4) < '3.3')
  {
    $DB->query("UPDATE {plugins} SET settings = %d, authorlink = '%s' WHERE pluginid = %d",
               $pluginsettings, $DB->escape_string($authorlink), $pluginid);

    // Convert all plugin settings to new naming method to allow translation
    ConvertPluginSettings($pluginid);

    UpdatePluginVersion($pluginid, '3.3.0');
    $currentversion = '3.3.0';
  }
  if($currentversion == '3.3.0')
  {
    InsertPluginSetting($pluginid, 'options', 'Comment Source Plugins', 'To display comments from one or multiple plugins, enter each plugin id, separated by comma.<br />Default plugin: 2 = Articles', 'text', '2', 10);
    UpdatePluginVersion($pluginid, '3.3.1');
    $currentversion = '3.3.1';
  }
  if($currentversion == '3.3.1')
  {
    UpdatePluginVersion($pluginid, '3.4.0');
    $currentversion = '3.4.0';
  }
  if(($currentversion == '3.4.0') || ($currentversion == '3.4.1'))
  {
    InsertPluginSetting($pluginid, 'options', 'Page Link', 'Display link to the source plugin\'s page (default: No)?', 'yesno', '0', 90);
    InsertPluginSetting($pluginid, 'options', 'Display Avatar', 'Display the avatar for every user (default: Yes)?', 'yesno', '1', 100);
    InsertPluginSetting($pluginid, 'options', 'Display Pagination', 'Display a list of pages to browse through all comments (default: Yes)?', 'yesno', '1', 110);
    $sql = 'UPDATE '.PRGM_TABLE_PREFIX.'pluginsettings SET displayorder = %d WHERE pluginid = '.$pluginid." AND title = '%s'";
    $DB->query($sql,10,'comment_source_plugins');
    $DB->query($sql,20,'comment_limit');
    $DB->query($sql,30,'limit_comment_length');
    $DB->query($sql,40,'display_comment');
    $DB->query($sql,50,'display_username');
    $DB->query($sql,60,'display_date');
    $DB->query($sql,70,'display_source_title');
    $DB->query($sql,80,'source_link');
    $DB->query($sql,90,'page_link');
    $DB->query($sql,100,'display_avatar');
    $DB->query($sql,110,'display_pagination');
    UpdatePluginVersion($pluginid, '3.4.2');
    $currentversion = '3.4.2';
  }
  if($currentversion == '3.4.2')
  {
    InsertPluginSetting($pluginid, 'options', 'Avatar Width',  'Avatar image width in pixels (keep empty or 0 to use default)?', 'text', '0', 102);
    InsertPluginSetting($pluginid, 'options', 'Avatar Height', 'Avatar image height in pixels (keep empty or 0 to use default)?', 'text', '0', 104);
    UpdatePluginVersion($pluginid, '3.4.3');
    $currentversion = '3.4.3';
  }
  if($currentversion == '3.4.3')
  {
    InsertPluginSetting($pluginid, 'options', 'Avatar Column', 'Display avatar in a separate column (Yes) or should the comments flow around the avatar (No)?<br />Default: Yes', 'yesno', '1', 106);
    UpdatePluginVersion($pluginid, '3.4.4');
    $currentversion = '3.4.4';
  }
  if($currentversion == '3.4.4')
  {
    $CSS = new CSS();
    $CSS->InsertCSS('Latest Comments ('.$pluginid.')', 'plugins/'.$plugin_folder.'/css/default.css', true, null, $pluginid);
    unset($CSS);
    UpdatePluginVersion($pluginid, '3.4.5');
    $currentversion = '3.4.5';
  }
  //SD370:
  if(version_compare($currentversion,'3.7.0','<='))
  {
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX."plugins SET base_plugin = 'Latest Comments'".
               " WHERE pluginid = $pluginid");
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX."pluginsettings SET groupname = 'options'".
               " WHERE pluginid = $pluginid");
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX."pluginsettings SET input = 'plugins:'".
               " WHERE pluginid = $pluginid AND title = 'comment_source_plugins'");
    UpdatePluginVersion($pluginid, '3.7.0');
    $currentversion = '3.7.0';
  }
}
