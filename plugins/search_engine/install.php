<?php
if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,4))
{
  if(strlen($installtype))
  {
    echo 'Search Engine: you need to upgrade to '.PRGM_NAME.' v3.4+ to use this plugin.<br />';
  }
  return false;
}

// ####################### DETERMINE CURRENT DIRECTORY ########################
$plugin_folder  = sd_GetCurrentFolder(__FILE__);

// ############################################################################
// PLUGIN INFORMATION
// ############################################################################
// Do NOT set $uniqueid or $pluginid here!

$pluginname     = 'Search Engine';
$version        = '1.3.8';
$pluginpath     = $plugin_folder.'/search_engine.php';
$settingspath   = $plugin_folder.'/settings.php';
$authorname     = 'subdreamer_web';
$authorlink     = 'http://www.subdreamer.com/';
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


// SearchEngineUpgrade137 is both install and major settings upgrade!
if(!function_exists('SearchEngineUpgrade137'))
{
  function SearchEngineUpgrade137()
  {
    global $DB, $installtype, $pluginid, $plugin_folder;

    ConvertPluginSettings($pluginid);

    // CREATE FRONT-END PHRASES
    InsertPhrase($pluginid, 'search_but',       'Search');
    InsertPhrase($pluginid, 'no_searchterm',    'You must provide at least one word to search for!');
    InsertPhrase($pluginid, 'no_results',       'No Results Found');
    InsertPhrase($pluginid, 'results_for',      'Results For');
    InsertPhrase($pluginid, 'article_results',  'Article Results');
    InsertPhrase($pluginid, 'forum_results',    'Forum Results');
    InsertPhrase($pluginid, 'no_search_params', 'Error: You must enable either article or forum searching in the Search Engine Plugin settings!');

    // CREATE NEW ADMIN PHRASES
    InsertAdminPhrase($pluginid, 'search_engine_settings',  'Search Engine Settings');
    InsertAdminPhrase($pluginid, 'highlight_title',         'Highlight Title Matches?');
    InsertAdminPhrase($pluginid, 'highlight_body',          'Highlight Body Matches?');
    InsertAdminPhrase($pluginid, 'show_full_url',           'Show the full URL link?');
    InsertAdminPhrase($pluginid, 'use_autocomplete',        'Enable Autocomplete?');

    //Insert Plugin Settings
    #InsertPluginSetting($pluginid, 'search_engine_settings', 'display_article_results', 'You must have at least this or forum results enabled for the plugin to work.', 'yesno', '1', 10);
    #InsertPluginSetting($pluginid, 'search_engine_settings', 'display_forum_results',   'Disable this if you are not using the Subdreamer Forum Plugin.', 'yesno', '1', 20);
    InsertPluginSetting($pluginid, 'search_engine_settings', 'Display Form With Results', 'Display the search entry form together with results (default: Yes)', 'yesno', '1', 25);
    InsertPluginSetting($pluginid, 'search_engine_settings', 'Results Per Page',        'How many results would you like displayed per page?', 'text', '10', 30);
    InsertPluginSetting($pluginid, 'search_engine_settings', 'highlight_title',         'Would you like the matches in the article and (if enabled below) forum topic titles highlighted?', 'yesno', '1', 40);
    InsertPluginSetting($pluginid, 'search_engine_settings', 'highlight_body',          'Would you like the matches in the actual article and (if enabled below) forum post highlighted?', 'yesno', '1', 50);
    InsertPluginSetting($pluginid, 'search_engine_settings', 'show_full_url',           'Would you like to show the full URL under each result entry (default: Yes)?', 'yesno', '1', 60);
    InsertPluginSetting($pluginid, 'search_engine_settings', 'use_autocomplete',        'Would you like to enable Autocomplete for the searchbox?<br />This will provide instant result links to the articles.', 'yesno', '1', 70);

    /* v1.3.7, 2012-03-19:
    - Replace individual search articles/forum settings with search plugins selection and remove "old" settings
    - Convert settings to new format/notation (_descr instead of _desc)
    - Create CSS entry for Admin|Skins page and remove old color-related settings
    */
    $preset = array();
    if($installtype == 'upgrade')
    {
      $DB->query('UPDATE '.PRGM_TABLE_PREFIX."pluginsettings SET groupname = 'search_engine_settings' WHERE pluginid = %d",$pluginid);
      $DB->query('UPDATE '.PRGM_TABLE_PREFIX."adminphrases SET varname = CONCAT(varname,'r') WHERE pluginid = %d AND varname LIKE '%_desc'",$pluginid);

      // REORDER SETTINGS
      #$DB->query("UPDATE {pluginsettings} SET displayorder = '10', description = 'display_article_results_descr' WHERE pluginid = %d AND title = 'display_article_results'",$pluginid);
      #$DB->query("UPDATE {pluginsettings} SET displayorder = '20', description = 'display_forum_results_descr' WHERE pluginid = %d AND title = 'display_forum_results'",$pluginid);
      $DB->query("UPDATE {pluginsettings} SET displayorder = '30', description = 'results_per_page_descr' WHERE pluginid = %d AND title = 'results_per_page'",$pluginid);
      $DB->query("UPDATE {pluginsettings} SET displayorder = '40', description = 'highlight_title_descr' WHERE pluginid = %d AND title = 'highlight_title'",$pluginid);
      $DB->query("UPDATE {pluginsettings} SET displayorder = '50', description = 'highlight_body_descr' WHERE pluginid = %d AND title = 'highlight_body'",$pluginid);
      $DB->query("UPDATE {pluginsettings} SET displayorder = '60', description = 'show_full_url_descr' WHERE pluginid = %d AND title = 'show_full_url'",$pluginid);
      $DB->query("UPDATE {pluginsettings} SET displayorder = '70', description = 'use_autocomplete_descr' WHERE pluginid = %d AND title = 'use_autocomplete'",$pluginid);

      // Determine current settings
      $forumid = GetPluginID('Forum');
      if($getopts = $DB->query('SELECT title, value'.
                               ' FROM '.PRGM_TABLE_PREFIX.'pluginsettings'.
                               ' WHERE pluginid = %d'.
                               " AND title IN ('display_article_results','display_forum_results')",
                               $pluginid))
      {
        while($opt = $DB->fetch_array($getopts,null,MYSQL_ASSOC))
        {
          if(!empty($opt['value']))
          {
            if($opt['title'] == 'display_article_results')
            {
              $preset[] = 2;
            }
            else
            {
              $preset[] = $forumid;
            }
          }
        }
      }
    }
    else
    {
      $preset[] = 2; // Default to article plugin upon install
    }
    InsertPluginSetting($pluginid, 'search_engine_settings', 'Search Plugins',     'Which plugins should be searched (if any)?<br />By default the Article plugin and the Forum plugin are supported (to be extended with future releases).', 'plugins:multiple', implode(',',$preset), 10);
    InsertPluginSetting($pluginid, 'search_engine_settings', 'Search Page Titles', 'Search Page titles (default: No)?', 'yesno', '0', 20);

    DeleteAdminPhrase($pluginid, 'display_article_results');
    DeleteAdminPhrase($pluginid, 'display_article_results_descr');
    DeleteAdminPhrase($pluginid, 'display_forum_results');
    DeleteAdminPhrase($pluginid, 'display_forum_results_descr');
    DeletePluginSetting($pluginid, 'search_engine_settings', 'display_article_results');
    DeletePluginSetting($pluginid, 'search_engine_settings', 'display_forum_results');

    //Create CSS entry with colors replaced by old settings (or defaults):
    $se_colors = array('acbox_bgcolor' => 'FFFFFF',
                       'acbox_bordercolor' => 'DDDDDD',
                       'acresult_hovercolor' => 'DDDDDD');
    if($getcolors = $DB->query('SELECT DISTINCT title, value FROM {pluginsettings} WHERE pluginid = %d'.
                            " AND title IN ('acbox_bgcolor','acbox_bordercolor','acresult_hovercolor')".
                            ' ORDER BY title', $pluginid))
    {
      while($entry = $DB->fetch_array($getcolors,null,MYSQL_ASSOC))
      {
        $se_colors[$entry['title']] = str_replace('#','',$entry['value']);
      }
    }
    $CSS = new CSS();
    $CSS->InsertCSS('Search Engine', 'plugins/'.$plugin_folder.'/css/default.css', true, $se_colors, $pluginid);
    unset($CSS, $se_colors);

    DeletePluginSetting($pluginid, 'search_engine_settings', 'acbox_bgcolor');
    DeletePluginSetting($pluginid, 'search_engine_settings', 'acbox_bordercolor');
    DeletePluginSetting($pluginid, 'search_engine_settings', 'acresult_hovercolor');
    DeleteAdminPhrase($pluginid, 'acbox_bgcolor');
    DeleteAdminPhrase($pluginid, 'acbox_bgcolor_desc');
    DeleteAdminPhrase($pluginid, 'acbox_bordercolor');
    DeleteAdminPhrase($pluginid, 'acbox_bordercolor_desc');
    DeleteAdminPhrase($pluginid, 'acresult_hovercolor');
    DeleteAdminPhrase($pluginid, 'acresult_hovercolor_desc');
  } //SearchEngineUpgrade137
} //DO NOT REMOVE!


// SearchEngineUpgrade138 is both install and major settings upgrade!
if(!function_exists('SearchEngineUpgrade138'))
{
  function SearchEngineUpgrade138()
  {
    global $DB, $installtype, $pluginid, $plugin_folder;

    $tpl_path = ROOT_PATH.'includes/tmpl/';
    $tpl_path_def = ROOT_PATH.'includes/tmpl/defaults/';

    $new_tmpl = array(
      'search_engine_form.tpl' => array('Search Engine Form', $pluginid, 'Search Engine Form'),
      'search_results.tpl' => array('Search Engine Results', $pluginid, 'Search Engine Results')
    );

    // Loop to add DEFAULT templates first:
    $tpl_path_def = ROOT_PATH.'includes/tmpl/defaults/';
    echo '<br /><b>Adding search engine templates...</b>';
    foreach($new_tmpl as $tpl_name => $tpl_data)
    {
      $rev_id = 0;
      $content = SD_Smarty::GetTemplateContentFor($tpl_data[1], $tpl_name, $rev_id);
      if($content !== false)
      {
        // Replace existing template if it is empty
        if(trim($content)=='')
        {
          if($res = SD_Smarty::CreateTemplateFromFile($tpl_data[1], $tpl_path_def, $tpl_name,
                                                      $tpl_data[0], $tpl_data[2], false,
                                                      'Frontpage', true))
            echo '<br /><b>Updated existing, but empty template "'.$tpl_name.'"</b>';
        }
      }
      else
      {
        // Create new template entry from file
        $res = SD_Smarty::CreateTemplateFromFile($tpl_data[1], $tpl_path_def, $tpl_name,
                                                 $tpl_data[0], $tpl_data[2], false);
        if($res === FALSE)
        {
          echo '<br />Template "'.$tpl_name.'" not added, probably already existing.';
        }
        else
        {
          echo '<br />Template "'.$tpl_name.'" added.';
          // Add existing, custom templates as newest revisions
          // that would preceed defaults:
          if(is_file($tpl_path.$tpl_name) && is_readable($tpl_path.$tpl_name))
          {
            SD_Smarty::AddTemplateRevisionFromFile($tpl_data[1], $tpl_path, $tpl_name, $tpl_data[2]);
          }
        }
      }
    } //foreach

  } //SearchEngineUpgrade138
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

  //v1.3.7: installs (and upgrades all old) settings to common format; creates CSS entry with colors replaced by old settings
  SearchEngineUpgrade137();

  //v1.3.8: fix potential empty templates
  SearchEngineUpgrade138();

} //install


// ############################################################################
// UPGRADE PLUGIN
// ############################################################################

if(!empty($currentversion) && ($installtype == 'upgrade'))
{
  if(empty($pluginid)) return false;

  // Upgrade any lower version to v1.3.7:
  if(version_compare($currentversion, '1.3.7', '<'))
  {
    SearchEngineUpgrade137();

    $currentversion = '1.3.7';
    UpdatePluginVersion($pluginid, $currentversion);
  }

  if($currentversion == '1.3.7')
  {
    SearchEngineUpgrade138();

    $currentversion = '1.3.8';
    UpdatePluginVersion($pluginid, $currentversion);
  }

} //upgrade


// ############################################################################
// UNINSTALL PLUGIN
// ############################################################################

if($installtype == 'uninstall')
{
  // No Custom Tables to Uninstall
}
