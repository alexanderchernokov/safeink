<?php
// +---------------------------------------------+
// | Download Manager plugin for Subdreamer      |
// +---------------------------------------------+
// | v2.2.0, September 2013                      |
// | Maintainer: 2007-2013 tobias                |
// | This version requires Subdreamer 3.6+!      |
// +---------------------------------------------+

if(!defined('IN_PRGM')) exit();

// ####################### DETERMINE CURRENT DIRECTORY ########################

$dlm_currentdir = sd_GetCurrentFolder(__FILE__);
if(!$dlm_pluginid = GetPluginIDbyFolder($dlm_currentdir))
{
  return;
}

// ############################################################################
// Include required class files
// ############################################################################

// Check if required classes are available
if(!class_exists('DownloadManagerTools'))
{
  // class DownloadManager (main frontpage class, object instance required)
  if(!@include(ROOT_PATH . 'plugins/'.$dlm_currentdir.'/class_dlm_tools.php'))
  {
    return false;
  }
}

if(!class_exists('DownloadManagerTools'))
{
  return false;
}

if(!DownloadManagerTools::DMT_Init())
{
  DownloadManagerTools::DownloadManagerToolsInit($dlm_pluginid);
}
if(DownloadManagerTools::DMT_Init())
{
  DownloadManagerTools::dlm_DisplayTagCloud(30,6);
}
