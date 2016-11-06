<?php
if(!defined('IN_PRGM') || !defined('IN_ADMIN')) exit();

// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,7))
{
  if(strlen($installtype))
  {
    echo 'Latest Posts: you need to upgrade to '.PRGM_NAME.' v3.7+ to use this plugin.<br />';
  }
  return false;
}

// ###################### DETERMINE CURRENT DIRECTORY #########################
$plugin_folder = sd_GetCurrentFolder(__FILE__);

// ############################################################################
// PLUGIN INFORMATION
// ############################################################################

$uniqueid       = 8;
$pluginname     = 'Latest Posts';
$version        = '3.7.0';
$pluginpath     = 'p8_latest_posts/latestposts.php';
$settingspath   = 'p8_latest_posts/p8_settings.php';
$authorname     = 'subdreamer_web';
$authorlink     = 'http://www.subdreamer.com/';
$pluginsettings = '17'; //admin+view

// ############################################################################
// INSTALL PLUGIN
// ############################################################################

if($installtype == 'install')
{
  $CSS = new CSS();
  $CSS->InsertCSS($pluginname, 'plugins/'.$plugin_folder.'/css/default.css',true,null,$uniqueid); #2013-10-05
  unset($CSS);

  InsertPluginSetting($uniqueid, 'Options', 'Number of Posts to Display', 'Please enter the the maximum number of latest posts to be displayed:', 'text', '5', 1);
  InsertPluginSetting($uniqueid, 'Options', 'Word Wrap',           'Wraps a string to the given number of characters:', 'text', '30', 2);
  InsertPluginSetting($uniqueid, 'Options', 'Exclude Forums',      'You can exclude private forums by entering their id&quot;s here (separated by a blank):', 'text', '', '3');
  InsertPluginSetting($uniqueid, 'Options', 'Shorten Titles',      'Shorten titles to given amount of characters (0 = disable):', 'text', '0', 4);
  InsertPluginSetting($uniqueid, 'Options', 'Display Avatar',      'Display users forum avatar (default: no)?', 'yesno', '0', 5);
  InsertPluginSetting($uniqueid, 'Options', 'Display Post Date',   'Display dates for latest postings?', 'yesno', '1', 6);

  InsertPluginSetting($uniqueid, 'Options', 'Avatar Image Width',  'Avatar image <strong>width</strong> in Pixels for sizing the image horizontally (default: 80)?<br />Example: <strong>80</strong><br />If empty, no width tag is being used and the image will appear in full width.', 'text', '80', 11);
  InsertPluginSetting($uniqueid, 'Options', 'Avatar Image Height', 'Avatar image <strong>height</strong> in Pixels for sizing the image vertically (default: 80)?<br />Example: <strong>80</strong><br />If empty, no height tag is being used and the image will appear in full height.', 'text', '80', 12);
  InsertPluginSetting($uniqueid, 'Options', 'Avatar Column',       'Display avatar in a single column (yes) or together with post title in one cell (no)?<br />Default: Yes', 'yesno', '1', 13);

  InsertPluginSetting($uniqueid, 'Options', 'Thread Display',      'Display which post of each thread (default: Last)?',
    '<select name=\\\\\"settings[\$setting[settingid]]\\\\\">
    <option value=\\\\\"0\\\\\" \".(\$setting[value]==\"0\" ? \"selected\" : \"\").\">First Topic</option>
    <option value=\\\\\"1\\\\\" \".(\$setting[value]==\"1\" ? \"selected\" : \"\").\">Last Topic</option>
    </select>', '1', 20);

  InsertPhrase($uniqueid, 'posted_by', 'Posted by');
}


// ############################################################################
// UPGRADE PLUGIN
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
  if(substr($currentversion,0,4) == '3.2.')
  {
    UpdatePluginVersion($uniqueid, '3.3.0');
    $currentversion = '3.3.0';
  }
  if($currentversion == '3.3.0')
  {
    $DB->query("DELETE FROM {adminphrases} WHERE pluginid = %d AND varname LIKE 'number_of_post%'",$uniqueid);
    $DB->query("UPDATE {pluginsettings} SET title = 'number_of_posts_to_display', description = 'number_of_posts_to_display_descr'
                WHERE pluginid = %d AND (title = 'number_of_post_to_display' OR title = 'number_of_posts_to_display')",$uniqueid);
    InsertPluginSetting($uniqueid, 'Options', 'Number of Posts to Display', 'Please enter the the maximum number of latest posts to be displayed:', 'text', '5', 1, true);
    $currentversion = '3.3.1';
    UpdatePluginVersion($uniqueid, '3.3.1');
  }
  if($currentversion == '3.3.1')
  {
    InsertPluginSetting($uniqueid, 'Options', 'Forum Plugin', 'Use built-in forum even if Forum Integration is active?<br />Default: No', 'yesno', '0', 4);
    $currentversion = '3.3.2';
    UpdatePluginVersion($uniqueid, '3.3.2');
  }
  if($currentversion == '3.3.2')
  {
    $currentversion = '3.4.0';
    UpdatePluginVersion($uniqueid, '3.4.0');
  }
  if($currentversion == '3.4.0')
  {
    // fix settings display (no eval needed)
    $DB->query("UPDATE {pluginsettings} SET input = '%s'
      WHERE pluginid = %d AND lower(groupname) = 'options' AND title = 'thread_display'",
      "select:\r\n0|First Topic\r\n1|Last Topic", $uniqueid);
    $currentversion = '3.4.3';
    UpdatePluginVersion($uniqueid, '3.4.3');
  }
  if($currentversion == '3.4.3')
  {
    InsertPluginSetting($uniqueid, 'Options', 'Shorten Titles', 'Shorten titles to given amount of characters (0 = disable):', 'text', '0', 4);
    $currentversion = '3.4.4';
    UpdatePluginVersion($uniqueid, '3.4.4');
  }
  if($currentversion == '3.4.4') #2013-10-05
  {
    $CSS = new CSS();
    $CSS->InsertCSS($pluginname, 'plugins/'.$plugin_folder.'/css/default.css',true,null,$uniqueid); #2013-10-05
    unset($CSS);
    $currentversion = '3.7.0';
    UpdatePluginVersion($uniqueid, '3.7.0');
  }
}
