<?php
if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

// ####################### DETERMINE CURRENT DIRECTORY ########################
$plugin_folder = sd_GetCurrentFolder(__FILE__);

// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,4))
{
  if(strlen($installtype))
  {
    echo 'Articles Archive ('.$plugin_folder .'): you need to upgrade to '.PRGM_NAME.' v3.4.3+ to use this plugin.<br />';
  }
  return false;
}

// ############################################################################
// PLUGIN INFORMATION
// ############################################################################
// Do NOT set $uniqueid or $pluginid here!

$pluginname     = 'Articles Archive';
$version        = '3.5.0';
$pluginpath     = $plugin_folder.'/archive.php';
$settingspath   = $plugin_folder.'/settings.php';
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

if(!function_exists('ArticlesArchiveUpgrade341'))
{
  function ArticlesArchiveUpgrade341()
  {
    global $DB, $pluginid, $plugin_folder;

    InsertAdminPhrase($pluginid, 'articles_archive_settings', 'Articles Archive Settings', 2);

    InsertPluginSetting($pluginid, 'articles_archive_settings', 'Article Plugin Selection', 'Please select the source Article plugin, whose articles are to be displayed:<br />In case there are compatible Article clones available, these will be included in this list.', 'plugin:Articles', '2', 10);
    InsertPluginSetting($pluginid, 'articles_archive_settings', 'Calendar Years Age',       'Specify how many calendar years back to display articles from?<br />Example: to display all articles from only the <strong>current</strong> calendar year (January-December), select <strong>1 Year</strong>. Set this to <strong>All Years</strong> to display all years.',
      'select:\r\n0|All Years\r\n1|1 Year\r\n2|2 Years\r\n3|3 Years\r\n4|4 Years\r\n5|5 Years\r\n6|6 Years\r\n7|7 Years\r\n8|8 Years\r\n9|9 Years\r\n10|10 Years', '0', 20,true);
    InsertPluginSetting($pluginid, 'articles_archive_settings', 'Display Mode',
      'Please select the desired display mode for the archive display here:<br />
      The first two modes list articles grouped by year/month, the third mode only displays available article categories.',
      'select:\r\n0|year/month/day\r\n1|years/months\r\n2|Categories-only listing', '0', 30,true);
    InsertPluginSetting($pluginid, 'articles_archive_settings', 'Article List Page',
      'Please specify here the target page, which must contain the source Article plugin, to display
      articles for a given month or category:', 'page', '0', 40,true);
  }
} //DO NOT REMOVE!!!

if(!function_exists('ArticlesArchiveUpgrade343'))
{
  function ArticlesArchiveUpgrade343()
  {
    global $DB, $pluginid;

    $val = $DB->query_first("SELECT value FROM {pluginsettings} WHERE pluginid = %d AND title = 'display_mode'",$pluginid);
    DeletePluginSetting($pluginid,'articles_archive_settings','display_mode');
    $DB->query_first("DELETE FROM {phrases} WHERE pluginid = %d AND varname LIKE 'select_display_mode_%'",$pluginid);
    InsertPluginSetting($pluginid, 'articles_archive_settings', 'Display Mode',
      'Please select the desired display mode for the archive display here:<br />
      The first two modes list articles grouped by year/month, the third mode only displays available article categories.',
      'select:\r\n0|Year/Months+Articles List\r\n1|Years/Months List\r\n2|Years/Months Dropdown\r\n3|Categories-only listing',
      (empty($val['value'])?'0':(int)$val['value']), 30,true);
  }
} //DO NOT REMOVE!!!


if(!function_exists('ArticlesArchiveUpgrade350'))
{
  function ArticlesArchiveUpgrade350()
  {
    global $DB, $pluginid;

    InsertPluginSetting($pluginid, 'articles_archive_settings', 'Ignore Current Page',
      'Ignore any page specifications for articles of the source plugin (default: No)?<br />'.
      'Normally only articles for the current page (where archive plugin is located) are displayed.<br />'.
      'Set this option to <strong>Yes</strong> to ignore the page and display all eligible articles.',
      'yesno', 0, 100);
  }
} //DO NOT REMOVE!!!


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
  $CSS->InsertCSS('Articles Archive ('.$pluginid.')', 'plugins/'.$plugin_folder.'/css/default.css', true, array('pXXX' => 'p'.$pluginid), $pluginid);
  unset($CSS);

  ArticlesArchiveUpgrade341();
  ArticlesArchiveUpgrade343();
  ArticlesArchiveUpgrade350();
}

// ############################################################################
// UPGRADE PLUGIN
// ############################################################################

if(!empty($currentversion) && ($currentversion != $version) && ($installtype == 'upgrade'))
{
  if(empty($pluginid)) return false;

  if($currentversion == '3.4.0')
  {
    ArticlesArchiveUpgrade341();

    UpdatePluginVersion($pluginid, '3.4.1');
    $currentversion = '3.4.1';
  }

  if(version_compare($currentversion, '3.5.0', '<'))
  {
    ArticlesArchiveUpgrade343();
    ArticlesArchiveUpgrade350();

    UpdatePluginVersion($pluginid, '3.5.0');
    $currentversion = '3.5.0';
  }
} //upgrade
