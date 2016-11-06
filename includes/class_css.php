<?php

// ############################################################################
// CSS CLASS
// ############################################################################

class CSS
{
  function GetPluginIDforCSSName($var_name='')
  {
    global $DB;

    if(!strlen($var_name))
    {
      return false;
    }

    $plugin_id = 0;
    if($plugin_arr = $DB->query_first('SELECT pluginid FROM {plugins}'.
                                      " WHERE name = '".$DB->escape_string($var_name).
                                      "' LIMIT 1"))
    {
      $plugin_id = $plugin_arr['pluginid'];
    }

    return $plugin_id;

  } //GetPluginIDforCSSName


  // ##########################################################################
  // DELETE PLUGIN CSS
  // ##########################################################################
  function DeletePluginCSS($pluginid)
  {
    global $DB, $core_pluginids_arr;

    $pluginid = Is_Valid_Number($pluginid,0,13); // 1st 12 plugins are reserved
    if(empty($pluginid) || in_array($pluginid, $core_pluginids_arr))
    {
      return false;
    }
    $DB->query("DELETE FROM {skin_css} WHERE plugin_id = ".(int)$pluginid);
  } //DeletePluginCSS


  // ##########################################################################
  // INSERT CSS
  // ##########################################################################
  // If plugin then var_name is a plugin name, otherwise a global CSS name.
  // SD322: new parameter "$replaceBase" to allow replacing of the default entry
  // (with skin_id = 0), in case the css already exists.
  // SD370: added $mobile
  function InsertCSS($var_name='', $css_file_path='', $replaceBase = true, $replace_arr=array(),
                     $override_pluginid = 0, $mobile=false)
  {
    global $DB;

    if(!strlen($var_name) || !strlen(trim($css_file_path,' \\/')))
    {
      return false;
    }

    $fileok = false;
    $css = '';
    if(file_exists(ROOT_PATH . $css_file_path))
    {
      if(false === ($css = @file_get_contents(ROOT_PATH . $css_file_path)))
        $css = '';
      else
        $fileok = true;
    }

    if($fileok && !empty($replace_arr) && is_array($replace_arr))
    {
      foreach($replace_arr as $search => $replace)
      {
        $css = str_replace($search, $replace, $css);
      }
    }

    if(!empty($override_pluginid)) //SD341
    {
      $plugin_id = $override_pluginid;
    }
    else
    {
      $plugin_id = $this->GetPluginIDforCSSName($var_name);
    }

    if($DB->query_first('SELECT skin_css_id FROM {skin_css}'.
                        ' WHERE skin_id = 0 AND plugin_id = %d'.
                        " AND var_name = '" . $DB->escape_string($var_name).
                        "' LIMIT 1", $plugin_id))
    {
      //SD322: allow to replace at least the default entry (skin_id = 0)
      if(!empty($replaceBase))
      {
        if($fileok)
        $DB->query("UPDATE {skin_css} SET css = '".$DB->escape_string($css).
                   "' WHERE var_name = '".$DB->escape_string($var_name).
                   "' AND plugin_id = ".(int)$plugin_id.
                   ' AND skin_id = 0'.
                   (empty($mobile)?'':' AND mobile = 1'));
        return true;
      }
      else
      {
        return false;
      }
    }

    $tbl = PRGM_TABLE_PREFIX.'skin_css';
    $DB->query("INSERT INTO $tbl (skin_id, plugin_id, var_name, css, mobile)
               VALUES (0, %d, '".$DB->escape_string($var_name)."', '".
               $DB->escape_string($css)."', %d)",
               $plugin_id, (empty($mobile)?0:1));
    return true;

  } //InsertCSS


  // ##########################################################################
  // REPLACE CSS
  // ##########################################################################

  function ReplaceCSS($var_name='', $css_file_path='', $replace_all = true)
  {
    global $DB;

    if(!strlen($var_name) || !strlen($css_file_path) ||
       !file_exists(ROOT_PATH . $css_file_path))
    {
      return false;
    }

    if(($css = @file_get_contents(ROOT_PATH . $css_file_path)) === false)
    {
      return false;
    }

    $plugin_id = $this->GetPluginIDforCSSName($var_name);

    $where = empty($replace_all) ? 'AND skin_id = 0' : '';

    $DB->query("UPDATE {skin_css} SET css = '".$DB->escape_string($css).
               "' WHERE var_name = '".$DB->escape_string($var_name).
               "' AND plugin_id = %d %s",
               $plugin_id, $where);

    return true;

  } //ReplaceCSS


  // ##########################################################################
  // APPEND CSS
  // ##########################################################################
  // @var_name = name of the plugin or global CSS
  // @css_file_path = the path to the css file
  // @append_all = append this css to all skins, default is yes

  function AppendCSS($var_name='', $css_file_path='', $append_all = true)
  {
    global $DB;

    if(!strlen($var_name) OR !strlen($css_file_path))
    {
      return false;
    }

    if(is_file(ROOT_PATH . $css_file_path))
    {
      $css = @file_get_contents(ROOT_PATH . $css_file_path);

      if(is_bool($css) && ($css === false))
      {
        return false;
      }
    }
    else
    {
      return false;
    }

    $plugin_id = $this->GetPluginIDforCSSName($var_name);

    $where = $append_all ? '' : 'AND skin_id = 0';
    $DB->query("UPDATE {skin_css} SET css = CONCAT(css, '\r\n\r\n" .
                $DB->escape_string($css) . "' )
                WHERE var_name = '" . $DB->escape_string($var_name) . "'
                AND plugin_id = %d %s LIMIT 1",
                $plugin_id, $where);

    return true;

  } //AppendCSS


  // ##########################################################################
  // GET CSS
  // ##########################################################################
  // @var_name = name of the plugin or global CSS
  // @skin_id  = if 0: return default CSS; if > 0: current CSS for skin with id

  function GetCSS($var_name='', $skin_id=0)
  {
    global $DB;

    if(!strlen($var_name))
    {
      return false;
    }

    $plugin_id = $this->GetPluginIDforCSSName($var_name);

    // The "default" css is stored with "skin_id = 0"
    if($css_arr = $DB->query("SELECT css FROM {skin_css}".
                  " WHERE var_name = '".$DB->escape_string($var_name).
                  "' AND plugin_id = %d AND skin_id = %d LIMIT 1",
                  $plugin_id, $skin_id))
    {
    }

    return false;

  } //GetCSS

} // end of CSS class
