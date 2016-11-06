<?php
if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,3))
{
  if(strlen($installtype))
  {
    echo 'Latest Articles: you need to upgrade to '.PRGM_NAME.' v3.3+ to use this plugin.<br />';
  }
  return false;
}

// ####################### DETERMINE CURRENT DIRECTORY ########################
$plugin_folder  = sd_GetCurrentFolder(__FILE__);

// ############################################################################
// PLUGIN INFORMATION
// ############################################################################
// Do NOT set $uniqueid or $pluginid here!

$pluginname     = 'Latest Articles';
$version        = '3.7.0';
$pluginpath     = $plugin_folder.'/latest_articles.php';
$settingspath   = $plugin_folder.'/latest_articles_settings.php';
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


if(!function_exists('LatestArticlesUpgrade341'))
{
  function LatestArticlesUpgrade341()
  {
    global $DB, $pluginid;

    $DB->query("UPDATE {pluginsettings} SET description = CONCAT(description,'r') WHERE pluginid = %d AND description LIKE '%_desc'",$pluginid);
    $DB->query("UPDATE {adminphrases} SET varname = CONCAT(varname,'r') WHERE pluginid = %d AND varname LIKE '%\_desc'",$pluginid);

    InsertAdminPhrase($pluginid, 'add_page', 'Add Page');
    InsertAdminPhrase($pluginid, 'change_options', 'Change Options');
    InsertAdminPhrase($pluginid, 'delete_confirm', 'Really delete configured page match now?');
    InsertAdminPhrase($pluginid, 'settings', 'Settings');
    InsertAdminPhrase($pluginid, 'page_matches', 'Page Matches');
    InsertAdminPhrase($pluginid, 'view_settings', 'View Settings');
    InsertAdminPhrase($pluginid, 'view_settings_descr', 'View and edit settings for this plugin:');
    InsertAdminPhrase($pluginid, 'match_add', 'Add Matches for page with this plugin:',2,true);
    InsertAdminPhrase($pluginid, 'match_add_descr', 'Add a new Page Match to specify the articles displayed based on which page the plugin is positioned. <strong>Note:</strong> only pages, which already have this plugin positioned on them, will be selectable here.', 2, true);
    InsertAdminPhrase($pluginid, 'match_edit', 'Edit Page Match');
    InsertAdminPhrase($pluginid, 'match_delete', 'Delete');
    InsertAdminPhrase($pluginid, 'save_default_match', 'Save Default');
    InsertAdminPhrase($pluginid, 'save_matches', 'Save Matches');
    InsertAdminPhrase($pluginid, 'default_match_hint', 'List articles from...',2,true);
    InsertAdminPhrase($pluginid, 'default_match_all', 'All Pages');
    InsertAdminPhrase($pluginid, 'default_match_current', 'Current Page');
    InsertAdminPhrase($pluginid, 'default_custom_pages', 'Selected Pages:',2,true);
    InsertAdminPhrase($pluginid, 'no_matches_found', 'No page matches were found.');
    InsertAdminPhrase($pluginid, 'column1_title', 'When contained in Page:');
    InsertAdminPhrase($pluginid, 'column2_title', 'List articles from Page(s):');
    InsertAdminPhrase($pluginid, 'popup_change_options_title', '<strong>Change Matching Options</strong>');

    InsertPluginSetting($pluginid, 'latest_articles_settings', 'Display Avatar', 'Display the user avatar for each user?', 'yesno', '0', 45);
    InsertPluginSetting($pluginid, 'latest_articles_settings', 'Avatar Width', 'Avatar image width in pixels (keep empty or 0 to use default)?', 'text', '0', 46);
    InsertPluginSetting($pluginid, 'latest_articles_settings', 'Avatar Height', 'Avatar image height in pixels (keep empty or 0 to use default)?', 'text', '0', 47);
    InsertPluginSetting($pluginid, 'latest_articles_settings', 'Article Plugin Selection', 'Please select the source Article plugin, whose articles are to be displayed:<br />In case there are compatible Article clones available, these will be included in this list.', 'plugin:Articles', '2', 1);

    InsertPluginSetting($pluginid, 'latest_articles_matches', 'Default Page Match',
    '<strong>Default Page Match</strong><br />
    Page Matching is a very flexible way to configure the listing of articles depending on the page.<br /><br />
    If there is no specific page match configured for a page on which this plugin is positioned, the
    Default Page Match configured here will be applied.<br />
    <br />
    When set to <strong>All Pages</strong>, which is the default mode, the plugin will list the configured
    number of articles regardless of the page(s), where these articles are normally displayed on.<br />
    <br />
    When set to <strong>Current Page</strong>, only those articles that are actually configured to
    appear on the currently viewed page, will be listed. This will even work if the source article
    plugin is not present on the same page.<br />
    <br />
    The far more flexible option here is to otherwise select any combination of pages (use Ctrl/Shift+Click)
    whose articles shall be icnluded in the articles listing:<br />
    ', 'text', '0', 10, true);

    DeletePluginSetting($pluginid,'latest_articles_settings','page_targeting');
    DeletePluginSetting($pluginid,'latest_articles_settings','pages_to_include');
    $DB->query("DELETE FROM {adminphrases} WHERE pluginid = %d AND varname LIKE 'pages_to_include%' OR varname LIKE 'page_targeting%'",$pluginid);

  }
} //DO NOT REMOVE!!!


if(!function_exists('LatestArticlesUpgrade343'))
{
  function LatestArticlesUpgrade343()
  {
    global $DB, $pluginid, $plugin_folder;

    $CSS = new CSS();
    $CSS->InsertCSS('Latest Articles ('.$pluginid.')', 'plugins/'.$plugin_folder.'/css/default.css', false, array(), $pluginid);
    unset($CSS);

  }
} //DO NOT REMOVE!!!


if(!function_exists('LatestArticlesUpgrade344'))
{
  function LatestArticlesUpgrade344()
  {
    global $DB, $pluginid, $plugin_folder;

    require_once(SD_INCLUDE_PATH.'class_sd_smarty.php');

    echo '<b>Adding template to Latest Articles plugin ('.$plugin_folder.')...</b>';

    $new_tmpl = array('latest_articles.tpl' => array('Latest Articles', 'Latest Articles template'));
    $tpl_path = SD_INCLUDE_PATH.'tmpl/';
    $tpl_path_def = SD_INCLUDE_PATH.'tmpl/defaults/';

    // Loop to add template(s):
    foreach($new_tmpl as $tpl_name => $tpl_data)
    {
      // Do not create duplicate
      if(false !== SD_Smarty::TemplateExistsInDB($pluginid, $tpl_name))
      {
        continue;
      }
      if(false !== SD_Smarty::CreateTemplateFromFile($pluginid, $tpl_path_def, $tpl_name,
                                                     $tpl_data[0], $tpl_data[1]))
      {
        // Add existing, custom template as newest revision, which would
        // would override the default one:
        if(is_file($tpl_path.$tpl_name) && is_readable($tpl_path.$tpl_name))
        {
          SD_Smarty::AddTemplateRevisionFromFile($pluginid, $tpl_path, $tpl_name, $tpl_data[1], true);
        }
      }
    }
    echo '<br /><b>Done.</b><br />';
  }
} //DO NOT REMOVE!!!


if(!function_exists('LatestArticlesUpgrade370'))
{
  function LatestArticlesUpgrade370()
  {
    global $DB, $pluginid, $plugin_folder;
    InsertPluginSetting($pluginid, 'latest_articles_settings', 'Pagination Links',
      'How many neighbouring pages should be displayed left/right from the current page (default: 2; min. 1)?<br />
       Only when displayed in large columns, a higher number may fit!', 'text', '2', 48);

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

  // Admin language (must be first here!)
  InsertAdminPhrase($pluginid, 'latest_articles_settings', 'Latest Articles Settings', 2);

  // plugin settings
  InsertPluginSetting($pluginid, 'latest_articles_settings', 'Display Limit',      'The Latest Articles plugin will display links to the most recent articles on your site. Enter the number of links to be shown:', 'text', '10', 10);
  //InsertPluginSetting($pluginid, 'latest_articles_settings', 'Page Targeting',     'Only display the latest articles of the category which the latest articles plugin resides in. Include Categories or Matching Categories will not work if targeting is on.', 'yesno', '0', 20);
  //InsertPluginSetting($pluginid, 'latest_articles_settings', 'Pages To Include',   'Enter the ID\'s of the categories you want to select latest articles from, separate values with comma. Leave empty to select articles from all categories. It can also be used together with the Matching Categories option.', 'text', '', 30);
  InsertPluginSetting($pluginid, 'latest_articles_settings', 'Sorting',            'How would you like your articles to be sorted (the latest articles plugin always selects the latest articles, but this option makes it possible to sort within those articles and within groups if you are using the Grouping option below)?',
  "select:\r\nnewest|Newest First\r\noldest|Oldest First\r\nalphaAZ|Alphabetically A-Z\r\nalphaZA|Alphabetically Z-A\r\nauthornameAZ|Author Name A-Z\r\nauthornameZA|Author Name Z-A", 'newest', 40);
  InsertPluginSetting($pluginid, 'latest_articles_settings', 'Display Page Name',      'Display category name to the right of each articles title?', 'yesno', '0', 50);
  InsertPluginSetting($pluginid, 'latest_articles_settings', 'Display Description',    'Display description under each articles title?', 'yesno', '0', 60);
  InsertPluginSetting($pluginid, 'latest_articles_settings', 'Display Read More',      'Display \'Read more...\' link for each article (it will use the language settings for the articles plugin)?', 'yesno', '0', 70);
  InsertPluginSetting($pluginid, 'latest_articles_settings', 'Display Author',         'Display author name under each article?', 'yesno', '0', 80);
  InsertPluginSetting($pluginid, 'latest_articles_settings', 'Display Creation Date',  'Display creation date under each article?', 'yesno', '0', 90);
  InsertPluginSetting($pluginid, 'latest_articles_settings', 'Display Updated Date',   'Display updated date under each article?', 'yesno', '0', 100);
  InsertPluginSetting($pluginid, 'latest_articles_settings', 'Display Pagination',     'Display pagination for multiple pages.<br />Default value: Yes?', 'yesno', '1', 110);
  InsertPluginSetting($pluginid, 'latest_articles_settings', 'Title In Bold',          'Do you want the title of each article to be bold?', 'yesno', '0', 120);
  InsertPluginSetting($pluginid, 'latest_articles_settings', 'Title Link',             'Do you want to convert the title of each article into a link?', 'yesno', '1', 130);
  InsertPluginSetting($pluginid, 'latest_articles_settings', 'Page Link',              'Do you want to use the category name as a link to the category? This will only work if you group articles by category name or if you choose do display category names to the right of each article.', 'yesno', '0', 140);
  InsertPluginSetting($pluginid, 'latest_articles_settings', 'HTML Container Class',   'Name of the HTML class for the container holding the complete plugin contents?<br />
  This only needs to be changed if this plugin is installed multiple times and should use different CSS styles.<br /> Default: <strong>latest_articles_container</strong>', 'text', 'latest_articles_container', 200);
  InsertPluginSetting($pluginid, 'latest_articles_settings', 'HTML Entry Class',       'Name of the HTML class for the container holding a single entry?<br />
  This only needs to be changed if this plugin is installed multiple times and should use different CSS styles.<br /> Default: <strong>latest_article</strong>', 'text', 'latest_article', 210);

  // plugin language
  InsertPhrase($pluginid, 'by',         'By');
  InsertPhrase($pluginid, 'read_more',  'Read More...');
  InsertPhrase($pluginid, 'published',  'Published: ');
  InsertPhrase($pluginid, 'updated',    'Updated: ');

  LatestArticlesUpgrade341();
  LatestArticlesUpgrade343();
  LatestArticlesUpgrade344();
  LatestArticlesUpgrade370(); #2013-09-25

} //install


// ############################################################################
// UPGRADE PLUGIN
// ############################################################################

if(!empty($currentversion) && ($installtype == 'upgrade'))
{
  if(empty($pluginid)) return false;

  if(version_compare($currentversion, '3.3.0', '<'))
  {
    // Update plugin with new settings
    $DB->query('UPDATE {plugins} SET settings = %d WHERE pluginid = %d',$pluginsettings,$pluginid);

    UpdatePluginVersion($pluginid, '3.3.0');
    $currentversion = '3.3.0';
  }

  if($currentversion == '3.3.0')
  {
    UpdatePluginVersion($pluginid, '3.3.1');
    $currentversion = '3.3.1';
  }

  if($currentversion == '3.3.1')
  {
    UpdatePluginVersion($pluginid, '3.4.0');
    $currentversion = '3.4.0';
  }

  if($currentversion == '3.4.0')
  {
    LatestArticlesUpgrade341();
    UpdatePluginVersion($pluginid, '3.4.1');
    $currentversion = '3.4.1';
  }

  if(version_compare($currentversion, '3.4.4', '<'))
  {
    LatestArticlesUpgrade344();
    UpdatePluginVersion($pluginid, '3.4.4');
    $currentversion = '3.4.4';
  }

  if(version_compare($currentversion, '3.7.0', '<')) #2013-09-25
  {
    LatestArticlesUpgrade370();
    UpdatePluginVersion($pluginid, '3.7.0');
    $currentversion = '3.7.0';
  }

} //upgrade
