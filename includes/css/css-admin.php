<?php
error_reporting(0);
header("Content-type: text/css");
header("Cache-Control: public");
define('IN_PRGM', true);
define('ROOT_PATH', './../../');
define('SD_INCLUDE_PATH', ROOT_PATH.'includes/');
define('SD_CSS_PATH', SD_INCLUDE_PATH.'css/');
define('SD_JS_PATH', SD_INCLUDE_PATH.'javascript/');
@require(SD_INCLUDE_PATH.'config.php');
unset($database);
defined('ADMIN_PATH') || define('ADMIN_PATH','admin');
require(ROOT_PATH.ADMIN_PATH.'/prgm_info.php');
if(is_file(ROOT_PATH.ADMIN_PATH.'/branding.php')) include(ROOT_PATH.ADMIN_PATH.'/branding.php');
$mainsettings['gzipcompress'] = true; //trick it
include(SD_INCLUDE_PATH.'enablegzip.php');

//SD362: output core admin CSS files instead of multiple file requests:
// These are moved here from functions_admin.php->DisplayAdminHeader() function!

$cache_file = ROOT_PATH.'cache/css-admin.css';
// Use cached CSS file for up to 30 days
if(file_exists($cache_file) && (filemtime($cache_file) > (time() - 2592000))) #30*24*3600
{
  @readfile($cache_file);
  exit;
}

// Load CSS files and store in cache file
$css_list = array(
  ROOT_PATH.ADMIN_STYLES_FOLDER.'/css/plugin_submenu.css',
  ROOT_PATH.ADMIN_STYLES_FOLDER.'/css/sf-menu.css',
  SD_CSS_PATH.'ceebox.css',
  SD_CSS_PATH.'menuskins/black.css',
  SD_CSS_PATH.'jquery.tag.editor.css',
  SD_CSS_PATH.'jquery.autocomplete.css',
  SD_CSS_PATH.'jquery.jdialog.css',
  SD_CSS_PATH.'jquery.jgrowl.css',
  SD_CSS_PATH.'jquery.datepick.css',
  SD_CSS_PATH.'jquery.timeentry.css',
  SD_CSS_PATH.'redmond.datepick.css',
  SD_CSS_PATH.'jPicker-1.1.6.min.css',
  SD_CSS_PATH.'imgareaselect-animated.css',
  SD_CSS_PATH.'uni-form.css',
  SD_CSS_PATH.'default.uni-form.css',
  ROOT_PATH.ADMIN_STYLES_FOLDER.'/css/jdialog.css',
  /* SD400 */
  ROOT_PATH.ADMIN_STYLES_FOLDER.'assets/css/bootstrap.min.css',
  ROOT_PATH.'includes/css/font-awesome.min.css',
  ROOT_PATH.ADMIN_STYLES_FOLDER.'assets/css/ace-fonts.css',
  ROOT_PATH.ADMIN_STYLES_FOLDER.'assets/css/ace.min.css',
  ROOT_PATH.ADMIN_STYLES_FOLDER.'assets/css/ace-skins.min.css',
  ROOT_PATH.ADMIN_STYLES_FOLDER.'assets/css/ace-rtl.min.css',
  #DO NOT LINK to "markitup" CSS FILES IN HERE! IMAGE URLS WON'T WORK!
);
$css = '';
$count = 0;
foreach($css_list as $entry)
{
  if(false !== ($handle = @fopen($entry, 'rb')))
  {
    $fc = '';
    while(!feof($handle))
    {
      $fc .= fread($handle,8192);
    }
    fclose($handle);
    if(strlen($fc))
    {
      $count++;
      $css .= "/* FILE: ".basename($entry)." */\n".trim($fc)."\n\n";
    }
  }
}

//Write contents to cache file now:
if(file_exists($cache_file)) @unlink($cache_file);
if(false !== ($handle = @fopen($cache_file, 'wb')))
{
  fwrite($handle, $css, strlen($css));
  fclose($handle);
}
chmod($cache_file,0644);

//Output to browser, finally:
echo $css;
exit;