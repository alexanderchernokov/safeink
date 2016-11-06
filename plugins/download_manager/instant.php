<?php
// +---------------------------------------------+
// | Download Manager plugin for Subdreamer      |
// +---------------------------------------------+
// | v2.2.0, September 2013                      |
// | Maintainer: 2007-2013 tobias                |
// | This version requires Subdreamer 3.6+!      |
// +---------------------------------------------+

// ############################################################################
// DEFINE CONSTANTS
// ############################################################################

define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../../');

// ############################################################################
// INIT PRGM
// ############################################################################
@require(ROOT_PATH . 'includes/init.php');
@require(ROOT_PATH . 'includes/enablegzip.php');
if(!Is_Ajax_Request())
{
  return;
}

$dlm_currentdir = sd_GetCurrentFolder(__FILE__);
if(!$pluginid = GetPluginIDbyFolder($dlm_currentdir))
{
  return;
}

// ############################################################################
// Include required class files
// ############################################################################

$initOK = !empty($userinfo['adminaccess']) || (!empty($userinfo['pluginadminids']) && @in_array($pluginid,$userinfo['pluginadminids']));
// Check if required classes are available
if($initOK && !@require(ROOT_PATH . 'plugins/'.$dlm_currentdir.'/dlm_settings.php'))
{
  $initOK = false;
}

if($initOK && class_exists('DownloadManagerSettings') && is_object($DownloadManagerSettings))
{
  DownloadManagerTools::DownloadManagerToolsInit();
}
else
{
  $initOK = false;
}

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtm">
<head>
<base href="'.$sdurl.'" />
<meta http-equiv="Content-Type" content="text/html;charset='.SD_CHARSET.'" />
</head>
<body>
<div id="dlm_instant_response">
';

$success = false;

$token      = GetVar(SD_TOKEN_NAME,  '', 'string');
$action     = GetVar('action',       '', 'string');
$file_id    = GetVar('instant_id',   '', 'string');
if(!$sectionids = GetVar('sectionids', false, 'array'))
{
  if($sectionids = GetVar('sectionids', 0, 'whole_number'))
  {
    $sectionids = array($sectionids);
  }
}
$file       = GetVar('file',         '', 'string');
$file       = trim(CleanVar($file));
$file_title = GetVar('title',        '', 'string');
$file_title = trim(CleanVar($file_title));
$initOK     = $initOK && CheckFormToken();

if($initOK && (!empty($action) || !empty($file)) && DownloadManagerTools::GetVar('allow_admin'))
{
  // MIMIC ADMIN PLUGINS PAGE:
  $admin_phrases = LoadAdminPhrases(2, $pluginid);
  $refreshpage = 'view_plugin.php?pluginid=' . $pluginid;
  define('REFRESH_PAGE', $refreshpage);

  // Was a file uploaded from admin panel and is to be added to the DLM now?
  if(strlen($file) && strlen($file_title))
  {
    $ulen = strlen((string)$userinfo['userid']);
    $plen = strlen((string)$pluginid);
    // Sanity and security checks on filename:
    $file_ok = (strlen($file_title) && isset($sectionids) && is_numeric($sectionids[0]) &&
               (substr($file, 0, $ulen) == $userinfo['userid']) &&
               (substr($file, $ulen+1, $plen) == $pluginid)) &&
               (substr($file, $ulen+$plen+2, 40) == sha1($token));
    $file_ok = $file_ok && (substr($file,0,1) != '.') && (substr($file,0,1) != '\\');
    $file_ok = $file_ok && file_exists(ROOT_PATH.'cache/'.$file);

    // Call the settings' UpdateFile function to process the file:
    if($file_ok && ($success = $DownloadManagerSettings->UpdateFile(0, true)))
    {
      echo '<span style="color: #0000FF">File successfully uploaded!</span>';
    }
  }
  else
  if($action=='latestfiles')
  {
    $success = true;
    $sectionid = GetVar('sectionid', 0, 'int');
    if($sectionid == -1)
    {
      $DownloadManagerSettings->DisplayFiles(-1);
    }
    else
    {
      $DownloadManagerSettings->DisplayFiles($sectionid > 0 ? $sectionid : 'Latest Files');
    }
  }

}

if(!$success)
{
  echo '<span style="color: #FF0000">Failed!</span>';
}

echo '</div>
</body></html>';

$DB->close();
exit();
