<?php

// DEFINE CONSTANTS
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');

// INIT PRGM
include(ROOT_PATH . 'includes/init.php');

// CHECK PAGE ACCESS
CheckAdminAccess('languages');

define('LANG_FILE_PATH', ROOT_PATH . ADMIN_PATH . '/languages/');

// GET POST VARS
$charset  = 'utf-8';
$language = GetVar('language', '', 'string',true,true);
$language = PreClean(strip_tags(unhtmlspecialchars($language)));
$version  = GetVar('version', '', 'string',true,true);
$version  = PreClean(strip_tags(unhtmlspecialchars($version)));
$author   = GetVar('author', '', 'string',true,true);
$author   = PreClean(strip_tags(unhtmlspecialchars($author)));
$defaults = GetVar('defaults', false, 'bool',true,true); //SD343: export default phrases only

// CHECK FOR ERRORS
if(!CheckFormToken() || ($defaults && (($author != PRGM_NAME) || ($language != 'en_US'))))
{
  echo '<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />';
  die();
}
if(!strlen($language) || !strlen($version))
{
  RedirectPage('languages.php?action=display_export_language', AdminPhrase('error_no_lang_specified'), 2, true);
  exit();
}

$plugins_list = array();
// GET PLUGINS
// First get a distinct list of plugins with translations to provide a list of these
// within the export file:
if($get_plugins = $DB->query('SELECT DISTINCT plugins.pluginid, plugins.name'.
                             ' FROM '.PRGM_TABLE_PREFIX.'phrases phrases'.
                             ' INNER JOIN '.PRGM_TABLE_PREFIX.'plugins plugins ON plugins.pluginid = phrases.pluginid'.
                             ($defaults?'':" WHERE IFNULL(phrases.customphrase,'') != ''").
                             ' ORDER BY plugins.pluginid ASC'))
{
  while($plugin_arr = $DB->fetch_array($get_plugins,null,MYSQL_ASSOC))
  {
    $plugins_list[$plugin_arr['pluginid']] = $plugin_arr['name'];
  }
}

// START WRITING FILE
$comments = '// ' . PRGM_NAME . ' ' . $mainsettings_sdversion . ($defaults?' Default':'')." Language Phrases - $language\r\n";

if(!$defaults && strlen($author))
{
  $comments .= "// Translation by $author\r\n";
}
$comments .= "// Export date: ".DisplayDate(TIME_NOW, 'Y-m-d H:i')."\r\n\r\n";
$comments .= "\$charset = '$charset';\r\n\$version = '".($defaults?$mainsettings_sdversion:$version)."';\r\n\r\n";

// Write an array list of translated plugins for reference
// This is needed as e.g. the "Forum" plugin may have different
// plugin id on different installations. Since all the phrases
// are stored with plugin id, like "$sdphrases[32] = array",
// the language import can determine the actual plugin id
// by looking up the plugin name for id "32" for above example.
$comments .= "// TRANSLATED FRONTEND PLUGINS:\r\n".
             '$translated_plugins_arr = array('. "\r\n";
foreach($plugins_list as $pluginid => $pluginname)
{
  if($pluginid > 1)
  $comments .= "  '" . $pluginid . "' => '".addslashes(strip_tags($pluginname))."',\r\n";
}
$comments .= ");\r\n";

// write the start of the file
$languagefile = '<'."?php\r\n$comments\r\n";

// ###############################################################################
// PHRASES: FRONT-END PLUGINS
// ###############################################################################

foreach($plugins_list as $pluginid => $pluginname)
{
  $get_phrases = $DB->query('SELECT pluginid, varname, '.($defaults?'defaultphrase':'customphrase').' phrase'.
                            ' FROM '.PRGM_TABLE_PREFIX.'phrases'.
                            ' WHERE pluginid = %d '.($defaults?'':" AND IFNULL(customphrase,'') != ''").
                            ' ORDER BY varname ASC', $pluginid);

  if($DB->get_num_rows($get_phrases))
  {
    // pluginid 1 is used for system phrases
    $pluginname = 'FRONTEND '.($pluginid == 1 ? 'PRGM' : 'PLUGIN: '.strip_tags($pluginname));

    // open array
    $languagefile .= "\r\n\r\n// " . $pluginname . " (" . $pluginid . ") \r\n\$sdphrases[" . $pluginid . "] = array \r\n( \r\n";

    while($phrase_arr = $DB->fetch_array($get_phrases,null,MYSQL_ASSOC))
    {
      $languagefile .= "  '" . $phrase_arr['varname'] . "' => '" .
                       addslashes($phrase_arr['phrase']) . "',\r\n";
                       #str_replace("'", "\'", $phrase_arr['phrase']) . "',\r\n";
    }

    // get rid of the last comma before closing the array
    $languagefile = substr($languagefile, 0, -3) . "\r\n";

    // close array
    $languagefile .= ");\r\n";
  }
}


// ###############################################################################
// PHRASES: ADMIN PAGES
// ###############################################################################

$admin_pages_arr = array(0 => 'Common Phrases',
                         1 => 'Pages',
                         2 => 'Plugins',
                         3 => 'Media',
                         4 => 'Comments',
                         5 => 'Users',
                         6 => 'Skins',
                         7 => 'Settings');

foreach($admin_pages_arr AS $adminpageid => $adminpagename)
{
  $get_admin_page_phrases = $DB->query('SELECT varname, '.($defaults?'defaultphrase':'customphrase').' phrase'.
                                       ' FROM '.PRGM_TABLE_PREFIX.'adminphrases'.
                                       ' WHERE adminpageid = %d AND pluginid = 0 '.
                                       ($defaults?'':" AND IFNULL(customphrase,'') != ''").
                                       ' ORDER BY varname ASC', $adminpageid);
  if($DB->get_num_rows($get_admin_page_phrases))
  {
    $languagefile .= "\r\n\r\n// ADMIN PAGE: " . $adminpagename . " \r\n\$phrases_admin_pages[" . $adminpageid . "] = array \r\n( \r\n";

    while($admin_page_phrase_arr = $DB->fetch_array($get_admin_page_phrases,null,MYSQL_ASSOC))
    {
      $languagefile .= "  '" . $admin_page_phrase_arr['varname'] . "' => '" .
                       addslashes($admin_page_phrase_arr['phrase']) . "',\r\n";
                       #str_replace("'", "\'", $admin_page_phrase_arr['phrase']) . "',\r\n";
    }
    // get rid of the last comma before closing the array
    $languagefile = substr($languagefile, 0, -3) . "\r\n";
    // close array
    $languagefile .= ');';
  }
}


// ############################################################################
// PHRASES: ADMIN PLUGIN PHRASES
// ############################################################################

if($get_plugins = $DB->query('SELECT DISTINCT plugins.pluginid, plugins.name'.
                             ' FROM '.PRGM_TABLE_PREFIX.'adminphrases phrases'.
                             ' INNER JOIN '.PRGM_TABLE_PREFIX.'plugins plugins ON plugins.pluginid = phrases.pluginid'.
                             ' WHERE phrases.adminpageid = 2'.
                             ($defaults?'':" AND IFNULL(phrases.customphrase,'') != ''").
                             ' ORDER BY plugins.pluginid ASC'))
{
  while($plugin_arr = $DB->fetch_array($get_plugins,null,MYSQL_ASSOC))
  {
    $get_admin_plugin_phrases = $DB->query('SELECT varname, '.($defaults?'defaultphrase':'customphrase').' phrase'.
                                           ' FROM '.PRGM_TABLE_PREFIX.'adminphrases'.
                                           ' WHERE adminpageid = 2 AND pluginid = %d'.
                                           ($defaults?'':" AND IFNULL(customphrase,'') != ''").
                                           ' ORDER BY varname ASC',$plugin_arr['pluginid']);

    if($DB->get_num_rows($get_admin_plugin_phrases))
    {
      $languagefile .= "\r\n\r\n// ADMIN PLUGIN: " . $plugin_arr['name'] . " \r\n\$phrases_admin_plugins[" . $plugin_arr['pluginid'] . "] = array \r\n( \r\n";

      while($admin_plugin_phrase_arr = $DB->fetch_array($get_admin_plugin_phrases,null,MYSQL_ASSOC))
      {
        $languagefile .= "  '" . $admin_plugin_phrase_arr['varname'] . "' => '" .
                         addslashes($admin_plugin_phrase_arr['phrase']) . "',\r\n";
                         #str_replace("'", "\'", $admin_plugin_phrase_arr['phrase']) . "',\r\n";
      }
      // get rid of the last comma before closing the array
      $languagefile = substr($languagefile, 0, -3) . "\r\n";
      // close array
      $languagefile .= ');';
    }
  }
} // Admin plugin phrases

// ############################################################################
// CLOSE FILE, SAVE FILE, AND READFILE
// ############################################################################

$languagefile .= "\r\n\r\n?".">";

// path to custom language file
$file = LANG_FILE_PATH . 'Custom_Language.php';

$error_unwritable_file = "<strong>ERROR: Cannot write language file: $file<br /><br />".
                         "Please use 'chmod' in FTP client to set folder permissions to 0777<br />".
                         "and file permissions to 0666 (if it already exists)!</strong>";

// First check, if folder is actually writable:
if(!is_dir(LANG_FILE_PATH) || !is_writable(LANG_FILE_PATH))
{
  DisplayAdminHeader('Settings');
  RedirectPage('languages.php?action=display_export_language',
               "<strong>ERROR: Cannot write to folder: ".LANG_FILE_PATH."<br /><br />
               Please use 'chmod' in FTP client to set it to 0777 permissions!</strong>", 4, true);
  exit();
}

// chmod file
if(file_exists($file) && !is_writable($file))
{
  @chmod($file, 0666);
}


if( (!file_exists($file) || is_writable($file)) && is_writable(LANG_FILE_PATH) )
{
  // open the file
  if(false == ($handle = @fopen($file, "w")))
  {
    RedirectPage('languages.php?action=display_export_language', $error_unwritable_file, 4, true);
    exit();
  }

  // write language file
  if(false === @fwrite($handle, $languagefile))
  {
    RedirectPage('languages.php?action=display_export_language', $error_unwritable_file, 4, true);
    exit();
  }

  // close file
  fclose($handle);

  // get file information
  $filelength = @filesize($file);
  $filename   = basename($file);

  // begin writing headers
  header("Pragma: public");
  header("Expires: 0");
  header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
  header("Cache-Control: public");
  header("Content-Description: File Transfer");
  header("Content-type: text/plain");

  // force the download
  $header="Content-Disposition: attachment; filename=".$filename.";";
  header($header);
  header("Content-Transfer-Encoding: binary");
  header("Content-Length: ".$filelength);
  @readfile($file);
  exit();
}

RedirectPage('languages.php?action=display_export_language', $error_unwritable_file, 4, true);

?>