<?php
define('IN_PRGM', true);
define('ROOT_PATH', '../');
include(ROOT_PATH . 'includes/init.php');

// ############################################################################
// GET REQUIRED VALUES
// ############################################################################
$pluginid = Is_Valid_Number(GetVar('pid', 0, 'whole_number',false,true),0,2,99999999);
$objectid = Is_Valid_Number(GetVar('objectid', 0, 'whole_number'),0,2,99999999);
$attachment_id = Is_Valid_Number(GetVar('id', 0, 'whole_number'),0,2,99999999);

if(empty($pluginid) || empty($objectid) || empty($attachment_id))
{
  echo 'Wrong data!<br />';
  $DB->close();
  exit();
}

// Download if full admin, plugin admin or has plugin download permission
if(empty($userinfo['adminaccess']) &&
   (empty($userinfo['pluginadminids']) || !@in_array($pluginid, $userinfo['pluginadminids'])) &&
   (empty($userinfo['plugindownloadids']) || !@in_array($pluginid, $userinfo['plugindownloadids'])) )
{
  echo 'No access!<br />';
  $DB->close();
  exit();
}

require(SD_INCLUDE_PATH.'class_sd_attachment.php');
require(SD_INCLUDE_PATH.'class_sd_media.php');

$attachment = new SD_Attachment($pluginid);
$attachment_arr = $attachment->FetchAttachmentEntry($objectid, $attachment_id);
if(empty($attachment_arr))
{
  echo 'File not found!<br />';
  $DB->close();
  exit();
}

// Default path is "attachments"
$attach_path = ROOT_PATH.'attachments/';

//SD351: check if plugin has a special attachments folder setting
$attach_path_ok = true;
$settings = GetPluginSettings($pluginid);
if(isset($settings['attachments_upload_path']) &&
   strlen(trim($settings['attachments_upload_path'])))
{
  $attach_path = ROOT_PATH.trim($settings['attachments_upload_path']);
  $attach_path_exists = @is_dir($attach_path);
  $attach_path_ok = $attach_path_exists &&
                    @is_writable($attach_path);
}

if(!$attach_path_ok)
{
  $DB->close();
  exit();
}

// Send the actual file
//SD362: parameter to return as inline, e.g. images (passed extention)
$isImage = !empty($_GET['isimage']) && (isset(SD_Media_Base::$known_image_types,$_GET['isimage']));
$disposition = $isImage ? 'inline' : 'attachment';
$name = $attachment_arr['filename'];
$size = $attachment_arr['filesize'];
$type = $attachment_arr['filetype'];
//SD343: x-gzip gets corrupted on some browsers, set to zip
if($type=='application/x-gzip') $type='application/zip';

// Path is constructed by "attachments-folder/pluginid/area/filename":
$file_path = $attach_path.(substr($attach_path,-1)=='/'?'':'/');
$file_path .= (int)$attachment_arr['pluginid'].'/';
$file_path .= (strlen($attachment_arr['area']) ? $attachment_arr['area'].'/' : '');
$file_path .= $attachment_arr['filename'];

if(file_exists($file_path))
{
  header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
  header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
  header("Content-Type: $type");
  header("Content-Length: $size");
  header('Content-Disposition: '.$disposition.'; filename="'.$attachment_arr['attachment_name'].'"');
  header("Content-type: $type");

  @readfile($file_path);

  $attachment->AddDownloadCount($objectid, $attachment_id);
}
else
{
  echo 'File not found!<br />';#.$file_path;
}
$DB->close();
