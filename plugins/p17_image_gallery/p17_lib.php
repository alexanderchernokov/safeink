<?php

if(!defined('IN_PRGM')) return;

define('P17_PLUGINDIR',  ROOT_PATH . 'plugins/p17_image_gallery/');
define('P17_IMAGEDIR',   P17_PLUGINDIR . 'images/');
define('P17_UPLOADDIR',  P17_PLUGINDIR . 'upload/');
define('P17_IMAGEPATH',  P17_IMAGEDIR);
define('P17_UPLOADPATH', P17_UPLOADDIR);
define('P17_IMAGEURL',   SITE_URL . P17_IMAGEDIR);

// Pre-check if "GD" module is installed and provides support:
$testGD = get_extension_funcs('gd');
$gd2 = ($testGD !== false) && in_array('imagecreatetruecolor', $testGD) && function_exists('imagecreatetruecolor');
define('SD_GD_SUPPORT', $gd2);
unset($testGD, $gd2);

// #############################################################################

function p17_UpdateImageCounts($sectionid = 0)
{
  return p17_UpdateSectionImageCounts($sectionid, 0);
} //p17_UpdateImageCounts

// #############################################################################

function p17_UpdateSectionImageCounts($sectionid, $totalcount)
{
  global $DB;

  $sectioncount = $DB->query_first('SELECT COUNT(*) FROM {p17_images} where sectionid = %d AND activated = 1', $sectionid);
  $localcount = $sectioncount[0];

  // Find all sections under this one
  $getsections = $DB->query('SELECT sectionid FROM {p17_sections} where parentid = %d', $sectionid);
  while($section = $DB->fetch_array($getsections))
  {
    $localcount += p17_UpdateSectionImageCounts($section['sectionid'], $localcount);
  }

  $DB->query('UPDATE {p17_sections} SET imagecount = %d WHERE sectionid = %d', $localcount, $sectionid);

  return $localcount;

} //p17_UpdateSectionImageCounts

// #############################################################################

function p17_GetFileErrorDescr($error_id)
{
  $error = '';
  if(!empty($error_id))
  {
    switch ($error_id)
    {
      case 1: //UPLOAD_ERR_INI_SIZE:
        //$error = "The uploaded file exceeds the upload_max_filesize directive (".ini_get("upload_max_filesize").") in php.ini.";
        $error = 'The uploaded file exceeds the allowed filesize limit.';
        break;
      case 2: //UPLOAD_ERR_FORM_SIZE:
        //$error = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.";
        $error = 'The uploaded file exceeds the allowed filesize limit.';
        break;
      case 3: //UPLOAD_ERR_PARTIAL:
        $error = 'The uploaded file was only partially uploaded.';
        break;
      case 4: //UPLOAD_ERR_NO_FILE:
        $error = 'No file was uploaded.';
        break;
      case 6: //UPLOAD_ERR_NO_TMP_DIR:
        $error = 'Missing a temporary folder.';
        break;
      case 7: //UPLOAD_ERR_CANT_WRITE:
        $error = 'Failed to write file to disk.';
        break;
      case 8: //UPLOAD_ERR_EXTENSION:
        $error = 'File upload stopped by extension.';
        break;
      default:
        $error = 'Unknown File Error';
        break;
    }
  }
  return $error;
} //p17_GetFileErrorDescr

?>