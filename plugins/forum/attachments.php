<?php
if(!isset($_SERVER['HTTP_USER_AGENT'])) exit();

define('IN_PRGM', true);
define('ROOT_PATH', '../../');
include(ROOT_PATH . 'includes/init.php');
if(!$pluginid = GetPluginID('Forum'))
{
  exit();
}

// ###############################################################################
// GET ATTACHMENT ID
// ###############################################################################

$attachment_id = Is_Valid_Number(GetVar('attachment_id', 0, 'whole_number'),0,1,99999999);
$post_id = Is_Valid_Number(GetVar('post_id', 0, 'whole_number'),0,1,99999999);

if(!$attachment_id || !$post_id)
{
  exit();
}

$HasAccess = !empty($userinfo['adminaccess']) ||
             (!empty($userinfo['pluginadminids']) && @in_array($pluginid, $userinfo['pluginadminids'])) ||
             (!empty($userinfo['pluginmoderateids']) && @in_array($pluginid, $userinfo['pluginmoderateids'])) ||
             (!empty($userinfo['plugindownloadids']) || @in_array($pluginid, $userinfo['plugindownloadids']));
if(!$HasAccess)
{
  exit();
}

$attachment_arr = $DB->query_first('SELECT attachment_id, user_id, attachment_name, filename, filesize, filetype'.
                                   ' FROM {p_forum_attachments}'.
                                   ' WHERE post_id = %d AND attachment_id = %d',
                                   $post_id, $attachment_id);
if(empty($attachment_arr['attachment_id']))
{
  exit();
}

// Send file
$disposition = 'attachment';
if(!$size = (int)$attachment_arr['filesize']) exit();
$name = (string)$attachment_arr['filename'];
$type = (string)$attachment_arr['filetype'];

$file_path = 'attachments/' .
             implode('/', preg_split('//', $attachment_arr['user_id'],  -1, PREG_SPLIT_NO_EMPTY)).
             '/'.$attachment_arr['filename'];

if(@file_exists($file_path) && is_file($file_path))
{
  header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
  header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
  header("Content-Type: $type");
  header("Content-Length: $size");
  header('Content-Disposition: ' . $disposition . '; filename="' . $attachment_arr['attachment_name'] . '"');
  header("Content-type: $type");

  @readfile($file_path);

  $DB->query_first('UPDATE {p_forum_attachments}'.
                   ' SET download_count = IFNULL(download_count,0)+1'.
                   ' WHERE attachment_id = %d', $attachment_id);
}
$DB->close();
exit();
