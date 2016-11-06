<?php
if(!defined('IN_PRGM')) exit();

if($plugin_folder = sd_GetCurrentFolder(__FILE__))
{
  if($pluginid = GetPluginIDbyFolder($plugin_folder))
  {
    require_once(SD_INCLUDE_PATH.'class_sd_tags.php');
    $tc_settings = GetPluginSettings($pluginid);
    $tc_phrases  = GetLanguage($pluginid);
    SD_Tags::$plugins    = sd_ConvertStrToArray($tc_settings['source_plugins']);
    SD_Tags::$maxentries = Is_Valid_Number($tc_settings['display_limit'],30,5,999);
    SD_Tags::$html_container_start = $tc_settings['html_container_start'];
    SD_Tags::$html_container_end   = $tc_settings['html_container_end'];
    SD_Tags::$html_tag_template    = $tc_settings['html_tag_template'];
    SD_Tags::$html_tag_separator   = $tc_settings['html_tag_separator'];
    SD_Tags::$tags_title           = $tc_phrases['tags_title'];

    SD_Tags::DisplayCloud();
  }
}
