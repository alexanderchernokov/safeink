<?php
// ############################################################################
// DEFINE CONSTANTS
// ############################################################################

define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');

if(!$denied = (empty($_REQUEST['pluginid']) || !is_numeric($_REQUEST['pluginid']) ||
               (isset($_REQUEST["chunk"]) && !is_numeric($_REQUEST['chunk'])) ||
               (isset($_REQUEST["chunks"]) && !is_numeric($_REQUEST['chunks'])) ||
               empty($_REQUEST['userid']) || !is_numeric($_REQUEST['userid']) ||
               empty($_REQUEST['securitytoken']) || !is_string($_REQUEST['securitytoken']) ||
               (strlen($_REQUEST['securitytoken']) < 40)))
{
  // ############################################################################
  // INIT PRGM
  // ############################################################################
  @require(ROOT_PATH . 'includes/init.php');

  /*
  *** IMPORTANT ***
  The regular user session at this time is NOT based on the browser session,
  but may come from gears or flash etc. and would usually result in a GUEST!
  Therefore the below lines are to make sure, that the provided user (by ID)
  is checked explicitely, re-processing some security settings from "init.php"
  like the raw security token, which is REQUIRED!
  */

  // 1st: re-fetch all details of the user identified by the provided ID
  $DB->select_db($usersystem['dbname']); //SD343
  $user = GetUser(intval($_REQUEST['userid']));
  $userinfo = GetUserInfo($user);
  // 2nd: Re-fill the user's security token which is needed in CheckFormToken!
  if(empty($userinfo['salt']))
  {
    //SD360: fix: first param must be 0, not 1! caused "IO Error" in uploader
    // on admin Media page:
    $userinfo['salt'] = substr($userinfo['username'],0,30);
  }
  $userinfo['securitytoken_raw'] = sha1($userinfo['userid'] . sha1($userinfo['salt']) . sha1(SD_COOKIE_SALT));
  // 3rd: CheckFormToken uses POST buffer, so copy the passed token over:
  $securitytoken = $_POST['form_token'] = (string)$_REQUEST['securitytoken'];

  $pluginid = (int)$_REQUEST['pluginid'];

  // Final check, including admin permissions for plugin:
  $denied = !CheckFormToken('', false) ||
            empty($userinfo['adminaccess']) &&
            (empty($userinfo['pluginadminids']) ||
             (($pluginid > 1) && !@in_array($pluginid,$userinfo['pluginadminids'])));
}

// If all checks fail, bail out:
if($denied)
{
  header("HTTP/1.0 404 not found");
  die('{"jsonrpc" : "2.0", "error" : {"code": 999, "message": "Permission denied!"}, "id" : "id"}');
}
unset($userinfo['securitytoken_raw']);

error_reporting(0);

// HTTP headers for no cache etc
header('Content-type: text/plain; charset=UTF-8');
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

/**
 * upload.php
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under GPL License.
 *
 * License: http://www.plupload.com/license
 * Contributing: http://www.plupload.com/contributing
 */

// Settings
//$targetDir = ini_get("upload_tmp_dir") . DIRECTORY_SEPARATOR . "plupload";
$cleanupTargetDir = true; // Remove old files
$maxFileAge = 3600; # 1h = 60 * 60; # temp file age in seconds

// 5 minutes execution time
if(!sd_safe_mode()) set_time_limit(300);

// Uncomment this one to fake upload time
//sleep(1);

// Get parameters
$chunk  = isset($_REQUEST['chunk'])  ? (int)$_REQUEST['chunk']  : 0;
$chunks = isset($_REQUEST['chunks']) ? (int)$_REQUEST['chunks'] : 0;

// ****************************************************************************
//SD332:
//Store file chunks in SD's cache folder
$targetDir = rtrim(ROOT_PATH,'\\/').DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;
//File name is secured by userid, pluginid and security token
$fileName = $userinfo['userid'].'_'.$pluginid.'_'.sha1($securitytoken).'.dat';
// ****************************************************************************

// Clean the fileName for security reasons
$fileName = preg_replace('/[^\w\._]+/', '', $fileName);

// Make sure the fileName is unique but only if chunking is disabled
if(($chunks < 2) && file_exists($targetDir . $fileName))
{
  $ext = strrpos($fileName, '.');
  $fileName_a = substr($fileName, 0, $ext);
  $fileName_b = substr($fileName, $ext);

  $count = 1;
  while (file_exists($targetDir . $fileName_a . '_' . $count . $fileName_b))
    $count++;

  $fileName = $fileName_a . '_' . $count . $fileName_b;
}

$filepath = $targetDir . $fileName;

// Create target dir
if(!file_exists($targetDir)) @mkdir($targetDir);

// Remove old temp files
if(is_dir($targetDir) && (false !== ($dir = @opendir($targetDir))))
{
  // cleanup only if enabled and if it's the first chunk
  if(empty($chunk) && $cleanupTargetDir)
  {
    while(false !== ($file = readdir($dir)))
    {
      if(strtolower(substr($file,-4)) == '.dat')
      {
        $tmpfilePath = $targetDir . $file;
        // Remove temp files if they are older than the max age
        if(preg_match('/'.(int)$userinfo['userid'].'(.*)\.dat$/', $file) &&
           (filemtime($tmpfilePath) < time() - $maxFileAge))
        {
          @unlink($tmpfilePath);
        }
      }
    }
  }
  closedir($dir);
}
else
{
  die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open cache directory."}, "id" : "id"}');
}

// Look for the content type header
if(isset($_SERVER['HTTP_CONTENT_TYPE'])) $contentType = $_SERVER['HTTP_CONTENT_TYPE'];

if(isset($_SERVER['CONTENT_TYPE']))      $contentType = $_SERVER['CONTENT_TYPE'];

// Handle non multipart uploads; older WebKit versions didn't support multipart in HTML5
if(strpos($contentType, "multipart") !== false)
{
  if(isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name']))
  {
    // Open temp file
    if(false === ($out = @fopen($filepath, ($chunk == 0 ? 'wb' : 'ab'))))
    {
      die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
    }
    // Read binary input stream and append it to temp file
    if(false === ($in = @fopen($_FILES['file']['tmp_name'], 'rb')))
    {
      die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
    }
    while($buff = fread($in, 4096))
    {
      fwrite($out, $buff);
    }

    fclose($in);
    fclose($out);
    unlink($_FILES['file']['tmp_name']);
    @chmod($filepath, 0666); //SD370: important!
  }
  else
    die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
}
else
{
  // Open temp file
  if(false === ($out = @fopen($filepath, ($chunk == 0 ? 'wb' : 'ab'))))
  {
    die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
  }
  // Read binary input stream and append it to temp file
  if(false === ($in = @fopen("php://input", "rb")))
  {
    die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
  }
  while($buff = fread($in, 4096))
  {
    fwrite($out, $buff);
  }
  fclose($in);
  fclose($out);
  @chmod($filepath, 0666); //SD370: important!
}

// Return JSON-RPC response
die('{"jsonrpc" : "2.0", "result" : {"filename": "'.$fileName.'"}, "id" : "id"}');
