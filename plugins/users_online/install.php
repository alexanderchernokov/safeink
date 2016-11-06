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
    echo 'Users Online ('.$plugin_folder.'): you need to upgrade to '.PRGM_NAME.' v3.4+ to use this plugin.<br />';
  }
  return false;
}

// PLUGIN INFORMATION
// Do NOT set $uniqueid or $pluginid here!
$pluginname     = 'Users Online';
$version        = '3.4.2';
$pluginpath     = $plugin_folder.'/users_online.php';
$settingspath   = $plugin_folder.'/users_online_settings.php';
$authorname     = 'subdreamer_web';
$authorlink     = 'http://www.subdreamer.com';
$pluginsettings = '17'; /*view/admin*/


######################### PRE-CHECK FOR CLONING ###############################
if($inst_check_id = GetPluginIDbyFolder($plugin_folder))
{
  $pluginid = $inst_check_id;
}
else
if($inst_check_id = GetPluginID($pluginname))
{
  $pluginname .= ' ('.$plugin_folder.')';
}
$authorname .= "<br />Plugin folder: '<b>plugins/$plugin_folder</b>'";

if(empty($installtype)) return true;


// ############################################################################
// INSTALL PLUGIN
// ############################################################################

if(empty($pluginid) && ($installtype == 'install'))
{
  // At this point SD3 has to provide a new plugin id
  if(!$pluginid = CreatePluginID($pluginname)) return false;

  // plugin settings
  InsertPluginSetting($pluginid, 'Options', 'Display Total Online', 'Display total online number? ex: 5 Online', 'yesno', '1', 10);
  InsertPluginSetting($pluginid, 'Options', 'Display Users and Guests Online', 'Display online users and guest numbers? ex: 5 Members | 3 Guests', 'yesno', '1', 20);
  InsertPluginSetting($pluginid, 'Options', 'Display Usernames', 'Display online usernames?', 'yesno', '1', 30);
  InsertPluginSetting($pluginid, 'Options', 'Display Most Ever Online', 'Display most users ever online?', 'yesno', '1', 40);
  InsertPluginSetting($pluginid, 'Options', 'Display Max Usernames', 'Enter the max number of usernames displayed (0 = all).<br />
    If a limit is set, a short info text will be appended like "<strong> and 5 other users</strong>".<br />
    See Settings|Languages for phrase, which contains macro <strong>[USERCOUNT]</strong> to be replaced by actual number in text.', 'text', '0', 50);

  // plugin language
  InsertPhrase($pluginid, 'online_now',  'Online Now:');
  InsertPhrase($pluginid, 'member',      'Member');
  InsertPhrase($pluginid, 'members',     'Members');
  InsertPhrase($pluginid, 'guest',       'Guest');
  InsertPhrase($pluginid, 'guests',      'Guests');
  InsertPhrase($pluginid, 'most_online', 'Most users ever online was');
  InsertPhrase($pluginid, 'on',          'on');
  InsertPhrase($pluginid, 'at',          'at');
  InsertPhrase($pluginid, 'x_other_users', 'and [USERCOUNT] other users.');
}

// ############################################################################
// UPGRADE PLUGIN
// ############################################################################

if(!empty($currentversion) && ($currentversion != $version) && ($installtype == 'upgrade'))
{
  if(empty($pluginid)) return false;

  if(version_compare($currentversion, '3.3.0', '<'))
  {
    $DB->query('UPDATE {plugins} SET settings = %d WHERE pluginid = %d',$pluginsettings,$pluginid);
    ConvertPluginSettings($pluginid);
    UpdatePluginVersion($pluginid, '3.3.0');
    $currentversion = '3.3.0';
  }
  if($currentversion == '3.3.0')
  {
    $currentversion = '3.4.0';
    UpdatePluginVersion($pluginid, '3.4.0');
  }
  if($currentversion == '3.4.0')
  {
    $DB->query('UPDATE {pluginsettings} SET displayorder = displayorder*10 WHERE pluginid = %d',$pluginid);
    InsertPluginSetting($pluginid, 'Options', 'Display Max Usernames', 'Enter the max number of usernames displayed (0 = all).<br />
    If a limit is set, a short info text will be appended like "<strong> and 5 other users</strong>".<br />
    See Settings|Languages for phrase, which contains macro <strong>[USERCOUNT]</strong> to be replaced by actual number in text.', 'text', '0', 50);
    InsertPhrase($pluginid, 'x_other_users', 'and [USERCOUNT] other users.');
    $currentversion = '3.4.2';
    UpdatePluginVersion($pluginid, '3.4.2');
  }
}
