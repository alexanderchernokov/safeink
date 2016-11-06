<?php
if(!defined('IN_PRGM')) return false;
if(defined('IN_ADMIN')) return true;

// #############################################################################
// GET ACTION AND DISPLAY CONTENT
// #############################################################################
$action = GetVar('action', 'search_form', 'string');

if($plugin_folder = sd_GetCurrentFolder(__FILE__))
{
  @include_once(ROOT_PATH . 'plugins/class_search_engine.php');
  if(class_exists('SearchEngineClass'))
  {
    $SearchEngine = new SearchEngineClass($plugin_folder);
    if($SearchEngine->settings !== false)
    {
      switch ($action)
      {
         case 'search_form':
         $SearchEngine->SearchForm();
         break;

         case 'search':
         $SearchEngine->DisplayResults();
         break;

         case 'autocomplete':
         $SearchEngine->AutoComplete();
         break;
      }
    }
    unset($SearchEngine);
  }
  unset($plugin_folder);
}

