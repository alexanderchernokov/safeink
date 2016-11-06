<?php
if(!defined('IN_ADMIN')) return false;

class ArticlesArchiveSettings
{
  private $pluginid = 0;
  private $pluginfolder = '';
  private $settings = array();

  function ArticlesArchiveSettings($plugin_folder)
  {
    $this->pluginfolder = $plugin_folder;
    $this->pluginid     = GetPluginIDbyFolder($this->pluginfolder);
    $this->settings     = GetPluginSettings($this->pluginid);
  } //ArticlesArchiveSettings

  // ############################# INIT FUNCTION #############################

  function Init()
  {
    global $plugin, $refreshpage;

    $action  = GetVar('action', 'settings', 'string');

    //echo '&nbsp;<a style="font-size: 14px;" href="'.$refreshpage.'" target="_self">&laquo; '.$plugin['name'].'</a>';
    PrintPluginSettings($this->pluginid, 'articles_archive_settings', $refreshpage);

  } //Init

} //end of class
