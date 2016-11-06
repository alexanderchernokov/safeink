<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

//SD341: new: this file is a collection of install/upgrade calls for core SD
//       plugins in order to install each once or upgrade it to current version.
//       This assumes to be run in global scope, i.e. not within function!

defined('IN_PRGM') || define('IN_PRGM', true);
defined('IN_ADMIN') || define('IN_ADMIN', true);
include_once(ROOT_PATH.'includes/functions_admin.php');
include_once(ROOT_PATH.'includes/functions_plugins.php');
if(!function_exists('UpdatePluginVersion'))
{
  function UpdatePluginVersion($pid, $version) {
    global $DB;
    if(!empty($pid) && !empty($version))
    $DB->query("UPDATE {plugins} SET version = '%s' WHERE pluginid = %d", $DB->escape_string($version), $pid);
  }
}

if(defined('INSTALLING_PRGM'))
{
  // Check if other plugins (Media Gallery, Latest Comments...) need installation:
  //SD370: added calendar2, event_manager, form_wizard, random_media to distribution!
  $inst_arr = array('p2_news','p4_guestbook','p6_contact_form','p7_chatterbox',
                    'p8_latest_posts','p9_top_posters','p16_link_directory',
                    'download_manager','media_gallery','event_manager','form_wizard',
                    'active_posters','articles_archive','calendar2','forum_stats',
                    'latest_articles','latest_comments','latest_files','latest_images',
                    'random_media','search_engine','subcategory_menu','tagcloud',
                    'users_online');
  foreach($inst_arr as $p)
  {
    if(!GetPluginIDbyFolder($p))
    {
      $base_plugin = $authorname = $authorlink = '';
      $pluginsettings = 17;
      unset($pluginid, $uniqueid); // DO NOT REMOVE!
      $installtype = 'install';
      $install_file = ROOT_PATH.'plugins/'.$p.'/install.php';
      if(file_exists($install_file))
      {
        echo $lang_installing_plugin.' "<strong>'.$p.'</strong>"...<br />';
        if(require($install_file))
        {
          if(!empty($uniqueid) && ($uniqueid > 1) && ($uniqueid < 17))
          {
            $DB->query("REPLACE INTO {plugins} (pluginid, name, displayname, version, pluginpath, settingspath, authorname, authorlink, settings)
                        VALUES (%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d)",
                        $uniqueid,
                        $DB->escape_string($pluginname),
                        $DB->escape_string($pluginname),
                        $DB->escape_string($version),
                        $DB->escape_string($pluginpath),
                        $DB->escape_string($settingspath),
                        $DB->escape_string($authorname),
                        $DB->escape_string($authorlink),
                        $pluginsettings);
          }
          else
          if(!empty($pluginid))
          {
            $pluginsettings = empty($pluginsettings) ? 17 : (int)$pluginsettings;
            /*
            $DB->query("UPDATE {plugins}
              SET name = '%s', displayname = '%s', version = '%s', pluginpath = '%s',
                  settingspath = '%s', authorname = '%s', authorlink = '%s',
                  settings = %d
                  WHERE pluginid = %d LIMIT 1",
            */
            $DB->query("REPLACE INTO {plugins} (pluginid, name, displayname, version, pluginpath, settingspath, authorname, authorlink, settings)
                        VALUES (%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d)",
              $pluginid,
              $DB->escape_string($pluginname),
              $DB->escape_string($pluginname),
              $DB->escape_string($version),
              $DB->escape_string($pluginpath),
              $DB->escape_string($settingspath),
              $DB->escape_string($authorname),
              $DB->escape_string($authorlink),
              $pluginsettings);

            DefaultPluginInUsergroups($pluginid,$pluginsettings);
            echo '"<strong>'.$p.'</strong>" installed!<br />';
          }
        }
      }
    }
  } //foreach
}

// Check all SD-developed plugins for upgrade and perform appropriate action:
$getplugins = $DB->query('SELECT * FROM '.PRGM_TABLE_PREFIX.'plugins'.
                         ' WHERE pluginid > 1'.
                         " AND authorname LIKE '%subdreamer%'".
                         ' ORDER BY pluginid');
while($p = $DB->fetch_array($getplugins))
{
  $pluginid = $p['pluginid'];
  $installtype = 'upgrade';
  $currentversion = $p['version'];
  $install_file = ROOT_PATH.'plugins/'.dirname($p['pluginpath']).'/install.php';
  if(file_exists($install_file))
  {
    echo $p['name']. ' ('.$pluginid.')<br />';
    @include_once($install_file);
  }
} //while

unset($inst_arr,$install_file,$p,$pluginid,$uniqueid);

$DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'skin_css'.
           ' WHERE plugin_id = 16 AND skin_id = 0');
$DB->query('UPDATE '.PRGM_TABLE_PREFIX.'skin_css'.
           " SET var_name = 'Link Directory'".
           ' WHERE plugin_id = 16'.
           " AND var_name = 'Latest Directory'");
$CSS = new CSS();
$CSS->InsertCSS('WYSIWYG', SETUPDIR.'/css/wysiwyg.css', true);
$CSS->InsertCSS('bbeditor', SETUPDIR.'/css/bbeditor.css', true);
$CSS->InsertCSS('comments', SETUPDIR.'/css/comments.css', true);
$CSS->InsertCSS('markitup', SETUPDIR.'/css/markitup.css', true);
$CSS->InsertCSS('messages', SETUPDIR.'/css/messages.css', true);
$CSS->InsertCSS('pagination', SETUPDIR.'/css/pagination.css', true);
$CSS->InsertCSS('rating', SETUPDIR.'/css/rating.css', true);
$CSS->InsertCSS('sf-menu', SETUPDIR.'/css/sf-menu.css', true);
$CSS->InsertCSS('Articles', 'plugins/p2_news/css/default.css', true, null, 2);
$CSS->InsertCSS('Contact Form', SETUPDIR.'/css/contactform.css', true, null, 6);
$CSS->InsertCSS('Guestbook', 'plugins/p4_guestbook/css/default.css', true, null, 4);
$CSS->InsertCSS('Image Gallery', 'plugins/p17_image_gallery/style.css', true, null, 17);
$CSS->InsertCSS('Latest Articles', 'plugins/latest_articles/css/default.css', true, null, 3);
$CSS->InsertCSS('Link Directory', 'plugins/p16_link_directory/css/default.css', true, null, 16);
$CSS->InsertCSS('Top Posters', 'plugins/p9_top_posters/styles.css', true, null, 9);
$CSS->InsertCSS('User Login Panel', SETUPDIR.'/css/loginpanel.css', true, null, 10);
$CSS->InsertCSS('User Profile', SETUPDIR.'/css/userprofile.css', true, null, 11);
$CSS->InsertCSS('User Registration', SETUPDIR.'/css/registration.css', true, null, 12);
$CSS->InsertCSS('Forum', SETUPDIR.'/css/forum.css', true);
if(defined('INSTALLING_PRGM'))
{
  $CSS->ReplaceCSS('bbeditor', SETUPDIR.'/css/bbeditor.css', true);
  $CSS->ReplaceCSS('comments', SETUPDIR.'/css/comments.css', true);
  $CSS->ReplaceCSS('markitup', SETUPDIR.'/css/markitup.css', true);
  $CSS->ReplaceCSS('messages', SETUPDIR.'/css/messages.css', true);
  $CSS->ReplaceCSS('Articles', 'plugins/p2_news/css/default.css', true);
  $CSS->ReplaceCSS('Forum', SETUPDIR.'/css/forum.css', true);
  $CSS->ReplaceCSS('Top Posters', 'plugins/p9_top_posters/styles.css', true);
  $CSS->ReplaceCSS('User Login Panel', SETUPDIR.'/css/loginpanel.css', true);
  $CSS->ReplaceCSS('User Profile', SETUPDIR.'/css/userprofile.css', true);
  $CSS->ReplaceCSS('User Registration', SETUPDIR.'/css/registration.css', true);
}
unset($CSS);
$DB->query("DELETE FROM {skin_css} WHERE var_name LIKE 'Articles %' AND plugin_id = 0");
$DB->query("UPDATE {plugins} SET authorlink = 'http://www.subdreamer.com/' WHERE authorlink = '1'");
sd_DisplaySetupMessage('<strong>CSS components added.</strong><br />');
