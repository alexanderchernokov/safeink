<?php
if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return;

// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,4))
{
  if(strlen($installtype))
  {
    echo 'Active Posters: you need to upgrade to '.PRGM_NAME.' v3.4+ to use this plugin.<br />';
  }
  return false;
}

// ####################### DETERMINE CURRENT DIRECTORY ########################
$plugin_folder  = sd_GetCurrentFolder(__FILE__);

// ############################################################################
// PLUGIN INFORMATION
// ############################################################################
// Do NOT set $pluginid or $pluginid here!

$pluginname     = 'Active Posters';
$version        = '1.0.1';
$pluginpath     = $plugin_folder.'/active_posters.php';
$settingspath   = $plugin_folder.'/settings.php';
$authorname     = 'subdreamer_web';
$authorlink     = 'http://www.subdreamer.com';
$pluginsettings = '17';

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

  $CSS = new CSS();
  $CSS->InsertCSS('Active Posters ('.$pluginid.')', 'plugins/'.$plugin_folder.'/css/default.css', true, array('pXXX' => 'p'.$pluginid), $pluginid);
  unset($CSS);

  InsertPhrase($pluginid, 'title', 'Most active users in the past [xxx] days (and new posts):');
  InsertPhrase($pluginid, 'msg_no_users', 'No active users found.');
  InsertAdminPhrase($pluginid, 'options', 'Options', 2);
  InsertPluginSetting($pluginid, 'options', 'Number of Active Posters to Display',
    'Please enter the maximum number of active posters to be displayed (default: 5):<br />
    Being active means that they were active within the given amount of days before today.', 'text', '5', 10);
  InsertPluginSetting($pluginid, 'options', 'Days to count Posts', 'Please enter how many past days from today the posts should be counted (default: 7):<br />
    If set to 0 then days are ignored and the user just must have a last activity attribute set (dependent on user system).', 'text', '7', 20);
  InsertPluginSetting($pluginid, 'options', 'Minimum Posts',       'Please enter the minimum amount of posts a user must have submitted in the checked days to be included in the list at all (default: 1):', 'text', '1', 30);
  InsertPluginSetting($pluginid, 'options', 'Display Usergroups',  'Display only users having as primary usergroup one of the selected usergroups (multiple choice)<br />
    At least one usergroup must be selected.', 'usergroups', '1,2,3', 40);
  InsertPluginSetting($pluginid, 'options', 'Display Avatar',      'Display user avatars (if available)?', 'yesno', '0', 100);
  InsertPluginSetting($pluginid, 'options', 'Avatar Image Width',  'Avatar image <strong>width</strong> in Pixels for sizing the image horizontally (default: 80)?<br />Example: <strong>40</strong><br />If empty, the default avatar width is used (see Settings).', 'text', '40', 110);
  InsertPluginSetting($pluginid, 'options', 'Avatar Image Height', 'Avatar image <strong>height</strong> in Pixels for sizing the image vertically (default: 80)?<br />Example: <strong>40</strong><br />If empty, default avatar height is used (see Settings).', 'text', '40', 120);
}


// ############################################################################
// UPGRADES
// ############################################################################

if(!empty($currentversion) && ($currentversion != $version) && ($installtype == 'upgrade'))
{
  if($currentversion=='1.0.0')
  {
    InsertAdminPhrase($pluginid, 'days_to_count_posts_descr', 'Please enter how many past days from today the posts should be counted (default: 7):<br />
    If set to 0 then days are ignored and the user just must have a last activity attribute set (dependent on user system).',2,true);
    InsertPhrase($pluginid, 'msg_no_users', 'No active users found.');
    $DB->query("UPDATE ".PRGM_TABLE_PREFIX."pluginsettings SET title = 'minimum_posts', description = 'minimum_posts_descr'
               WHERE pluginid = %d AND title = 'mininum_posts'", $pluginid);
    $DB->query("UPDATE ".PRGM_TABLE_PREFIX."adminphrases SET varname = 'minimum_posts' WHERE pluginid = %d AND varname = 'mininum_posts'", $pluginid);
    $DB->query("UPDATE ".PRGM_TABLE_PREFIX."adminphrases SET varname = 'minimum_posts_descr' WHERE pluginid = %d AND varname = 'mininum_posts_descr'", $pluginid);
    InsertPluginSetting($pluginid, 'options', 'Display Usergroups',  'Display only users having as primary usergroup one of the selected usergroups (multiple choice):<br />
      At least one usergroup must be selected.', 'usergroups', '', 40);

    $currentversion = '1.0.1';
    UpdatePluginVersion($pluginid, $currentversion);
  }
}
