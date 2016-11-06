<?php
if(!defined('IN_PRGM')) exit();

// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,7))
{
  if(strlen($installtype))
  {
    echo 'Twitter: you need to upgrade to '.PRGM_NAME.' v3.7+ to use this plugin.<br />';
  }
  return false;
}

// ####################### DETERMINE CURRENT DIRECTORY ########################
$plugin_folder = sd_GetCurrentFolder(__FILE__);

// ########################### PLUGIN INFORMATION #############################
$pluginname   = 'Twitter';
$version      = '3.7.0';
$pluginpath   = $plugin_folder . '/twitter.php';
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

  $CSS = new CSS();
  $CSS->InsertCSS($pluginname.' ('.$pluginid.')', 'plugins/'.$plugin_folder.'/css/default.css', true, array('pXXX_' => 'p'.$pluginid.'_'), $pluginid);
  unset($CSS);

  InsertAdminPhrase($pluginid, 'options', 'Options', 2);

  // plugin settings
  InsertPluginSetting($pluginid, 'options', 'Feed Username', 'Enter the Twitter username, whose tweets you would like to display (leave empty if a query is wanted):', 'text', 'subdreamer', 10);
  InsertPluginSetting($pluginid, 'options', 'Feed Query', 'Enter the search term for which to display tweets (leave empty if username is used):', 'text', '', 20);
  InsertPluginSetting($pluginid, 'options', 'User Favorites', 'Display favorites of the specified user instead of tweets (default: No)?', 'yesno',  0,  30);
  InsertPluginSetting($pluginid, 'options', 'Tweet Count', 'How many tweets do you want to display (default: 5)?', 'text', '5', 40);
  InsertPluginSetting($pluginid, 'options', 'Avatar Size', 'Enter the max. pixels of avatar images:<br/>Set to 0 to disable avatars. The default value is <b>48</b>.', 'text', '48', 50);
  InsertPluginSetting($pluginid, 'options', 'Include Retweets', 'Would you like the tweets list to include retweets (default: Yes)?', 'yesno', '1', 60);
  InsertPluginSetting($pluginid, 'options', 'Refresh Interval', 'If the list should be refreshed automatically to update date intervals, enter the number of seconds here (default: 0):<br/>Note: Twitter tweets are cached, new tweets are only fetched every 15 minutes! Only use this for feeds with tweets every hour.', 'text', '0', 70);
  InsertPluginSetting($pluginid, 'options', 'Display Format', 'Display the tweets in the following format:<br/>Default: <b>{avatar}{time}{join}{text}</b>', 'text', '{avatar}{time}{join}{text}', 80);
  InsertPluginSetting($pluginid, 'options', 'Intro Text', 'Display a short text BEFORE each tweet (default: empty):', 'text', '', 90);
  InsertPluginSetting($pluginid, 'options', 'Outro Text', 'Display a short text AFTER each tweet (default: empty):', 'text', '', 100);
  InsertPluginSetting($pluginid, 'options', 'Join Text', 'Optional short text in between date and tweet (default: empty; try <b>auto</b> for below English tweaks):', 'text', '', 110);
  InsertPluginSetting($pluginid, 'options', 'Auto Join Text Default', 'Auto text for non-verb:<br/>Default: <b>I said,</b> (something).', 'text', 'I said,', 120);
  InsertPluginSetting($pluginid, 'options', 'Auto Join Text ed', 'Auto join text for past-tense:<br/>Default: <b>I</b> (brows<b>ed</b>)', 'text', 'I', 130);
  InsertPluginSetting($pluginid, 'options', 'Auto Join Text ing', 'Auto join text for present-tense:<br/>Default: <b>I am</b> (brows<b>ing</b>)', 'text', 'I am', 140);
  InsertPluginSetting($pluginid, 'options', 'Auto Join Text Reply', 'Auto join text for replies:<br/>Default: <b>I replied to</b>', 'text', 'I replied to', 150);
  InsertPluginSetting($pluginid, 'options', 'Auto Join Text URL', 'Auto join text for URLs:<br/>Default: <b>I was looking at</b>', 'text', 'I was looking at', 160);
  InsertPluginSetting($pluginid, 'options', 'Loading Text', 'Default text before tweets appear (default: empty):', 'text', '', 170);
  InsertPluginSetting($pluginid, 'options', 'Date Text Just Now', 'Date text for <b>just now</b>:', 'text', 'just now', 200);
  InsertPluginSetting($pluginid, 'options', 'Date Text Seconds', 'Date text for <b>seconds ago</b> (keep xxx for number):', 'text', 'xxx seconds ago', 205);
  InsertPluginSetting($pluginid, 'options', 'Date Text A Minute', 'Date text for <b>about a minute ago</b>:', 'text', 'about a minute ago', 210);
  InsertPluginSetting($pluginid, 'options', 'Date Text Minutes', 'Date text for minutes (keep xxx for number):', 'text', 'about xxx minutes ago', 215);
  InsertPluginSetting($pluginid, 'options', 'Date Text An Hour', 'Date text for <b>about an hour ago</b>:', 'text', 'about an hour ago', 220);
  InsertPluginSetting($pluginid, 'options', 'Date Text Hours', 'Date text for hours (keep xxx for number):', 'text', 'about xxx hours ago', 225);
  InsertPluginSetting($pluginid, 'options', 'Date Text A Day', 'Date text for <b>about a day ago</b>:', 'text', 'about a day ago', 230);
  InsertPluginSetting($pluginid, 'options', 'Date Text Days', 'Date text for days (keep xxx for number):', 'text', 'about xxx days ago', 235);
  InsertPluginSetting($pluginid, 'options', 'Date Text A Year', 'Date text for <b>a year ago</b>:', 'text', 'a year ago', 240);
  InsertPluginSetting($pluginid, 'options', 'Date Text Years', 'Date text for years (keep xxx for number):', 'text', 'xxx years ago', 245);
  InsertPluginSetting($pluginid, 'options', 'List', 'Optional slug of list belonging to username (default: empty):', 'text', '', 300);
  InsertPluginSetting($pluginid, 'options', 'List ID', 'ID (number) of list to fetch when using list functionality (default: empty):', 'text', '', 310);
}

// ############################## UPGRADE PLUGIN ##############################

if(!empty($currentversion) && ($currentversion != $version) && ($installtype == 'upgrade'))
{
  //nothing here yet
}

// ############################## UNINSTALL PLUGIN ############################

if($installtype == 'uninstall')
{
  // no action required here
}
