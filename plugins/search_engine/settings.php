<?php
if(!defined('IN_PRGM') || !defined('IN_ADMIN')) exit();

// ############################################################################
// SETTINGS
// ############################################################################
if(!class_exists('SearchEngineSettings'))
{
  class SearchEngineSettings
  {
    public function SearchEngineSettings()
    {
    }

    static function DisplaySettings()
    {
      global $pluginid, $refreshpage;
      PrintPluginSettings($pluginid, 'search_engine_settings', $refreshpage);
    }
  }
}

// ############################################################################
// DISPLAY
// ############################################################################

SearchEngineSettings::DisplaySettings();
