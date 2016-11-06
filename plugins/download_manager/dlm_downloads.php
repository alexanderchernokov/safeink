<?php
// +---------------------------------------------+
// | Download Manager plugin for Subdreamer      |
// +---------------------------------------------+
// | v2.2.0, September 2013                      |
// | Maintainer: 2007-2013 tobias                |
// | This version requires Subdreamer 3.6+!      |
// +---------------------------------------------+

if(!defined('IN_PRGM') || !function_exists('sd_GetCurrentFolder')) exit();

// ####################### DETERMINE CURRENT DIRECTORY ########################

$dlm_currentdir = sd_GetCurrentFolder(__FILE__);
if(!$dlm_pluginid = GetPluginIDbyFolder($dlm_currentdir))
{
  unset($dlm_pluginid, $dlm_currentdir);
  return;
}

// ############################################################################
// Include required class files
// ############################################################################

// Check if required classes are available
if(!class_exists('DownloadManager'))
{
  // class DownloadManager (main frontpage class, object instance required)
  @include_once(ROOT_PATH . 'plugins/'.$dlm_currentdir.'/class_dlm.php');
}

if(!class_exists('DownloadManagerTools') || !class_exists('DownloadManager'))
{
  return false;
}

// ############################################################################
// INITIALIZE CLASS OBJECT
// ############################################################################
// This is NOT encapsulated within a function so that this file *could*
// be included multiple times without PHP errors.
/*
$DownloadManager = new DownloadManager();
if($DownloadManager->Init($dlm_pluginid))
{
  $DownloadManager->Show();
}
unset($DownloadManager);
*/

// Use cached instance if already created in header.php
if(!isset($sd_instances) || !is_array($sd_instances)) $sd_instances = array();
if(empty($sd_instances[$dlm_pluginid]) || !is_object($sd_instances[$dlm_pluginid]))
{
  $sd_instances[$dlm_pluginid] = new DownloadManager($dlm_currentdir);
}
if($sd_instances[$dlm_pluginid]->Init($dlm_pluginid))
{
  $sd_instances[$dlm_pluginid]->Show();
}
unset($dlm_currentdir);
