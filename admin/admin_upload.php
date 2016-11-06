<?php

// ############################################################################
// DEFINE CONSTANTS
// ############################################################################

define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');

// ############################################################################
// INIT PRGM
// ############################################################################
@require(ROOT_PATH . 'includes/init.php');
if(!Is_Ajax_Request())
{
  return;
}

// ############################################################################
// Common page header
// ############################################################################

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtm">
<head>
<base href="'.$sdurl.'" />
<meta http-equiv="Content-Type" content="text/html;charset='.SD_CHARSET.'" />
</head>
<body>
<div id="uploader_response">
';

// LOAD ADMIN PHRASES:
$admin_phrases = LoadAdminPhrases();

// ############################################################################
// Check parameters and access permissions
// ############################################################################

// ALL parameters are REQUIRED!
$success    = false;
$token      = GetVar(SD_TOKEN_NAME,  '', 'string');
$admin      = GetVar('admin',        '', 'string'); // admin page, e.g. "media"
$action     = GetVar('action',       '', 'string'); // move, rename
$action     = strtolower($action);
$pluginid   = GetVar('pluginid',     1,  'whole_number');
$folder     = GetVar('folder',       '', 'string');
$file       = GetVar('file',         '', 'string');
$file       = trim(CleanVar($file));
$file_title = GetVar('title',        '', 'string');
$file_title = trim(CleanVar($file_title));

// Check access permissions and parameters
$access = !empty($userinfo['adminaccess']) && (strlen($admin) > 3);
if(!$access)
{
  $access = ( !empty($userinfo['pluginadminids']) &&
              (($admin == 'articles') && in_array(2, $userinfo['pluginadminids'])) ||
              (($admin == 'plugins')  && in_array($pluginid, $userinfo['pluginadminids'])) ) ||
            (strlen($admin) && !empty($userinfo['admin_pages']) && @in_array(strtolower($admin), $userinfo['admin_pages']));

  $access = $access && CheckFormToken('', false) &&
            !empty($action) && !empty($file) && file_exists(ROOT_PATH.'cache/'.$file) &&
            !empty($file_title) &&
            !empty($folder) && is_dir($folder) && is_writable($folder);
}

if($access)
{
  // Sanity and security checks on filename:
  $ulen = strlen((string)$userinfo['userid']);
  $file_ok = (strlen($file_title) &&
             (substr($file, 0, $ulen) == $userinfo['userid']) &&
             (substr($file, $ulen+1, 1) == 1)) &&
             (substr($file, $ulen+3, 40) == sha1($token)) &&
             (substr($file,0,1) != '.') && (substr($file,0,1) != '\\');

  // File has been checked, so now depending on "$admin" process the file:
  if($file_ok)
  {
    // Supported actions:
    // "move"   : moves the file to target folder, keeping the file named "as-is"
    // "rename" : moves the file to target folder with "$file_title" as filename
    // Above actions require the target folder set in "folder"!
    switch($admin)
    {
      case 3: $action = 'rename'; // For "Media" page: rename file
      default:
        if(in_array($action, array('move','rename')))
        {
          $target = $folder . '/' . ($action == 'move' ? $file : $file_title);
          if($success = @copy(ROOT_PATH.'cache/'.$file, $target))
          {
            @chmod($target, 0644);
            $msg = isset($admin_phrases['uploader_msg_success'])?$admin_phrases['uploader_msg_success']:'File successfully uploaded!';
            echo '<span style="color: #0000FF">'.$msg.'</span>';
          }
          @unlink(ROOT_PATH.'cache/'.$file);
        }
    }
  }
}

if(!$success)
{
  $msg = isset($admin_phrases['uploader_msg_fail']) ? $admin_phrases['uploader_msg_fail'] : 'Upload Failed!';
  echo '<span style="color: #FF0000">'.$msg.'</span>';
}

echo '</div>
</body></html>';

$DB->close();
exit();

?>