<?php
if(!defined('IN_PRGM')) return false;

// +---------------------------------------------------+
// |    Code take from Giorgos (many thanks)           |
// |    http://freshmeat.net/projects/activecalendar/  |
// |    Revision 1: Done by Sublu                      |
// |    Revision 2: Done by abcohen                    |
// |    Please give credit to where its due.           |
// |    Big Credits to Subduck                         |
// |    http://www.subdreamer.com                      |
// +---------------------------------------------------+
// Last updated: March 3, 2014 to v3.7.1
// Maintained by Tobias, Subdreamer Team

// ###################### DETERMINE CURRENT DIRECTORY #########################
$plugin_folder = sd_GetCurrentFolder(__FILE__);

// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,7))
{
  if(strlen($installtype))
  {
    echo 'Calendar2: you need to upgrade to '.PRGM_NAME.' v3.7+ to use this plugin.<br />';
  }
  return false;
}

// ############################################################################
$base_plugin = $pluginname = 'Calendar2';
$version        = '3.7.1';
$pluginpath     = $plugin_folder.'/calendar2.php';
$settingspath   = $plugin_folder.'/settings.php';
$authorname     = 'subdreamer_web';
$authorlink     = 'http://www.subdreamer.com';
$pluginsettings = 17; //view/admin

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

if(!function_exists('Calendar2_UpdatePhrases'))
{
  function Calendar2_UpdatePhrases()
  {
    global $pluginid;

    InsertAdminPhrase($pluginid, 'options', 'Options');
    InsertPhrase($pluginid, 'no_news_found', 'No Articles Found');
    InsertPhrase($pluginid, 'no_events_found', 'No Events Found');
    InsertPhrase($pluginid, 'news', 'NEWS');
    InsertPhrase($pluginid, 'events', 'EVENTS');
  }
}


if(!function_exists('Calendar2_Update370'))
{
  function Calendar2_Update370()
  {
    global $DB, $pluginid, $authorname, $authorlink, $pluginpath,
           $plugin_folder, $pluginname, $settingspath;

    $CSS = new CSS();
    $CSS->InsertCSS($pluginname, 'plugins/'.$plugin_folder.'/css/default.css',true,null,$pluginid); #2013-10-05
    unset($CSS);

    // Assure that default slugs exist
    $tbl = PRGM_TABLE_PREFIX.'slugs';
    if(!$DB->query_first("SELECT name FROM $tbl WHERE name = 'Year and Month' AND pluginid = %d LIMIT 1", $pluginid))
    {
      $DB->query_first("INSERT INTO `$tbl` (`term_id`, `name`, `description`, `pluginid`, `parent`, `count`, `slug`, `slug_type`) VALUES
      (NULL, 'Year and Month', 'URI for news by year and month', %d, 0, 0, '[year]/[month]', 0)", $pluginid);
    }
    if(!$DB->query_first("SELECT name FROM $tbl WHERE name = 'Year, Month and Day' AND pluginid = %d LIMIT 1", $pluginid))
    {
      $DB->query_first("INSERT INTO `$tbl` (`term_id`, `name`, `description`, `pluginid`, `parent`, `count`, `slug`, `slug_type`) VALUES
      (NULL, 'Year, Month and Day', 'URI for news by year, month and day', %d, 0, 0, '[year]/[month]/[day]', 0)", $pluginid);
    }
    InsertAdminPhrase($pluginid, 'article_sources', 'Article Plugins Sources');
    InsertAdminPhrase($pluginid, 'event_sources', 'Event Manager Plugins Sources');
    InsertPluginSetting($pluginid, 'options', 'article_sources', 'Articles plugins to display news from?',
                        'plugins:Articles', '2', 50);
    $ev = GetPluginID('Event Manager');
    InsertPluginSetting($pluginid, 'options', 'event_sources', 'Event Manager plugins to display events from?',
                        'plugins:Event Manager', (empty($ev)?'':$ev), 100);
    InsertAdminPhrase($pluginid, 'event_sources_descr', 'Event Manager plugins to display events from?',2,true);
    $DB->query("UPDATE {plugins} set name = '%s', base_plugin = '%s',
               pluginpath = '%s', settingspath = '%s',
               authorname = '%s', authorlink = '%s' WHERE pluginid = %d",
               $pluginname, $pluginname, $pluginpath, $settingspath,
               $DB->escape_string($authorname), $authorlink, $pluginid);
  }
}


if(!function_exists('Calendar2_Update371'))
{
  function Calendar2_Update371()
  {
	  global $DB, $pluginid;

    InsertAdminPhrase($pluginid, 'cal_prev_months', 'Months Previous Display');
    InsertAdminPhrase($pluginid, 'cal_next_months', 'Months Next Display');
    InsertAdminPhrase($pluginid, 'cal_week_numbers', 'Display Week Numbers');

    InsertPluginSetting($pluginid, 'options', 'cal_prev_months',
      'Display x number of previous months before current month (default: 0; max. 12):', 'text', '0', 10);
    InsertPluginSetting($pluginid, 'options', 'cal_next_months',
      'Display x number of following months after current month (default: 0; max. 12):', 'text', '0', 20);
    InsertPluginSetting($pluginid, 'options', 'cal_week_numbers',
      'Display week numbers for each month (default: No):', 'yesno', '0', 30);
  }
}


// ############################## Install #####################################

if($installtype == 'install')
{
  // At this point SD3 has to provide a new plugin id
  if(empty($pluginid))
  {
    $pluginid = CreatePluginID($pluginname);
  }

  // If for whatever reason the plugin id is invalid, return false NOW!
  if(empty($pluginid)) return false;

  InsertPluginSetting($pluginid, 'options', 'Style',
    'Visual style the calendar should be displayed with (default: default)?<br />
     This is located in the css subfolder and the folder name must be exactly
     the same as the CSS file name.<br />
     Available styles: advanced, default, gravity, grey',
     'text', 'default', 0, true);
  InsertPluginSetting($pluginid, 'options', 'MaxChar',
    'How many characters do you want to show before cut-off titles?<br />
     Default: output 25 characters (recommended)',
     'text', '25', 1, true);

  Calendar2_UpdatePhrases();
  Calendar2_Update370(); #2013-09-27
  Calendar2_Update371(); #2014-03-03

  $DB->query("UPDATE {plugins} set base_plugin = '%s' WHERE pluginid = %d",
           $pluginname, $pluginid);
}

// ############################## Upgrade #####################################

if(!empty($currentversion) && ($currentversion != $version) && ($installtype == 'upgrade'))
{
  if(version_compare($currentversion,'1.3.1','<'))
  {
    ConvertPluginSettings($pluginid);
    $currentversion = '1.3.1';
  }

  if(version_compare($currentversion,'3.7.0','<'))
  {
    Calendar2_Update370();
    UpdatePluginVersion($pluginid, '3.7.0');
    $currentversion = '3.7.0';
  }

  if(version_compare($currentversion,'3.7.1','<'))
  {
    Calendar2_UpdatePhrases();
    Calendar2_Update371();
    UpdatePluginVersion($pluginid, '3.7.1');
    $currentversion = '3.7.1';
  }
}
