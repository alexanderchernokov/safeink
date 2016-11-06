<?php

if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,4))
{
  if(strlen($installtype))
  {
    echo 'Forum Stats: you need to upgrade to '.PRGM_NAME.' v3.4+ to use this plugin.<br />';
  }
  return false;
}

// ############################################################################
// PLUGIN INFORMATION
// ############################################################################

$pluginname   = 'Forum Stats';
$version      = '3.4.2';
$pluginpath   = 'forum_stats/forum_stats.php';
$settingspath = 'forum_stats/settings.php';
$authorname   = 'subdreamer_web';
$authorlink   = 'http://www.subdreamer.com';
$pluginsettings = '17'; /*view/admin*/

if(($installtype == 'install'))
{
  $pluginid = CreatePluginID($pluginname);
}
else
if($installtype == 'upgrade')
{
  $pluginid = GetPluginID($pluginname);
}
if(empty($pluginid)) return true;

function pForumStats_Upgrade102()
{
  global $DB, $pluginid;

  InsertPhrase($pluginid, 'members',        'Members:');
  InsertPhrase($pluginid, 'threads',        'Threads:');
  InsertPhrase($pluginid, 'posts',          'Posts:');
  InsertPhrase($pluginid, 'top_poster',     'Top Poster:');
  InsertPhrase($pluginid, 'welcome_newest', 'Welcome our newest member,');
  InsertPhrase($pluginid, 'members_last_7_days', 'last 7 days:');
}

// ############################################################################
// INSTALL PLUGIN
// ############################################################################

if($installtype == 'install')
{
  // NOTE: NEVER change the display order values here!!!
  InsertPluginSetting($pluginid, 'Options', 'Member Count',    'Display member count?',                           'yesno', '1', 10);
  InsertPluginSetting($pluginid, 'Options', 'Thread Count',    'Display thread count?',                           'yesno', '1', 20);
  InsertPluginSetting($pluginid, 'Options', 'Post Count',      'Display post count?',                             'yesno', '1', 30);
  InsertPluginSetting($pluginid, 'Options', 'Top Poster',      'Display the top poster?',                         'yesno', '1', 40);
  InsertPluginSetting($pluginid, 'Options', 'Last 7 Days Count', 'Display the number of new members within the last 7 days?<br />
    This will only count activated and not-banned members.', 'yesno', '1', 45);
  InsertPluginSetting($pluginid, 'Options', 'Welcome Message', 'Display a welcome message to the newest member?', 'yesno', '1', 60);

  pForumStats_Upgrade102();
}

// ############################################################################
// UPGRADE PLUGIN
// ############################################################################

if(!empty($currentversion) && ($currentversion != $version) && ($installtype == 'upgrade'))
{
  if(version_compare($currentversion,'3.4.0','<'))
  {
    $DB->query('UPDATE {plugins} SET settings = %d WHERE pluginid = %d',$pluginsettings,$pluginid);

    // Convert all plugin settings to new naming method to allow translation
    ConvertPluginSettings($pluginid);

    InsertPhrase($pluginid, 'members_last_7_days', 'last 7 days:');
    UpdatePluginVersion($pluginid, '3.4.0');
    $currentversion = '3.4.0';
  }
  if($currentversion == '3.4.0')
  {
    $DB->query('UPDATE {pluginsettings} SET displayorder=(displayorder*10) WHERE pluginid = %d',$pluginid);
    InsertPluginSetting($pluginid, 'Options', 'Last 7 Days Count', 'Display the number of new members within the last 7 days?<br />
      This will only count activated and not-banned members.', 'yesno', '1', 45);
    UpdatePluginVersion($pluginid, '3.4.2');
    $currentversion = '3.4.2';
  }
}
