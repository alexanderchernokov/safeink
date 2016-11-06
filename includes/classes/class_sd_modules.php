<?php
if(!defined('IN_PRGM')) return false;

if(!class_exists('SD_Modules'))
{
// SD globals used: $DB

define('SD_GROUP_ANTISPAM', 'antispam');
define('SD_GROUP_FILTER',   'filter');
define('SD_EVENT_GETVAR',   'ongetvar');
define('SD_EVENT_HEADER',   'onpageheader');
define('SD_EVENT_FOOTER',   'onpagefooter');

class SD_Modules
{
  public $ClassPath = '';
  public $Modules;
  public $Module_Events;
  public $Module_Groups;

  function SD_Modules ($ClassPath)
  {
    $this->ClassPath = $ClassPath;
    $this->Modules = array();
    $this->Module_Groups = array();
    $this->Module_Events = array();
  } //SD_Modules

  // ##########################################################################

  public function GetSettings ($Modulename, $editableonly = false)
  {
    global $DB;

    $settings = array();
    if(isset($this->Modules[$Modulename]))
    {
      $moduleid = $this->Modules[$Modulename]['moduleid'];
      $getsettings = $DB->query('SELECT settingid, editable, enabled, value, eventproc, input FROM {modulesettings}
        WHERE moduleid = %d '.(empty($editableonly) ? '' : 'AND editable = 1').
        ' ORDER BY displayorder', $moduleid);
      while($setting = $DB->fetch_array($getsettings))
      {
        // Store filter events:
        if(!empty($this->Modules[$Modulename]['enabled']) && ($setting['input'] == 'event'))
        {
          if(!isset($this->Module_Events[$setting['settingid']][$setting['eventproc']]))
          {
            $this->Module_Events[$setting['settingid']][$setting['eventproc']] =
              ($setting['enabled']+$setting['value'] == 2 ? 1 : 0);
          }
        }
        else if(!isset($settings[$setting['settingid']]))
        {
          // Regular module setting:
          $settings[$setting['settingid']] = $setting['value'];
        }
      }
    }
    return $settings;

  } //GetSettings

  // ##########################################################################

  public function GetSetting ($Modulename, $settingid)
  {
    global $DB;

    if(!isset($this->Modules[$Modulename]))
    {
      return false;
    }

    $moduleid = $this->Modules[$Modulename]['moduleid'];
    if($setting = $DB->query_first('SELECT value FROM {modulesettings}
      WHERE moduleid = %d AND settingid = \'%s\'', $moduleid, $settingid))
    {
      $setting = $setting['value'];
    }
    else
    {
      $setting = false;
    }

    return $setting;

  } //GetSetting

  // ##########################################################################

  public function SetSettings ($Modulename, $settings)
  {
    if(!isset($this->Modules[$Modulename]) || !isset($settings) || !is_array($settings))
    {
      return false;
    }

    global $DB;

    $moduleid = $this->Modules[$Modulename]['moduleid'];
    foreach($settings as $key => $value)
    {
      //Support for array-typed options, e.g. usergroups selection
      if(is_array($value))
      {
        $value = unhtmlspecialchars(implode(',',$value));
      }
      else
      {
        $value = unhtmlspecialchars($value);
      }
      $DB->query("UPDATE {modulesettings} SET value = '%s' WHERE moduleid = %d AND settingid = '%s'",
                 $DB->escape_string((string)$value), $moduleid, $DB->escape_string($key));
    }
    return true;

  } //SetSettings


  // ##########################################################################

  public function LoadModules ()
  {
    global $DB;

    $this->Modules = array();
    $DB->ignore_error = true;
    if($getmodules = $DB->query('SELECT * FROM {modules} ORDER BY function_code, moduleid'))
    {
      while($module = $DB->fetch_array($getmodules,null, MYSQL_ASSOC))
      {
        if(file_exists($this->ClassPath . $module['modulepath']))
        {
          $this->Modules[$module['name']] = $module;
          $this->Module_Groups[$module['function_code']][$module['name']] =
            array('moduleid'   => $module['moduleid'],
                  'modulename' => $module['name']);
          // Get the admin-"enabled" setting for this module:
          $mod_enabled = $DB->query_first("SELECT 1 FROM {modulesettings}
            WHERE moduleid = %d AND settingid = 'enabled' AND value = '1'",
            $module['moduleid']);
          $this->Modules[$module['name']]['enabled']  = !empty($mod_enabled[0]);
          $this->Modules[$module['name']]['settings'] = $this->GetSettings($module['name']);
          // *** Do not actually "include()" the module here or any global ***
          // *** variables won't work as we are inside this class!         ***
        }
      } //while
      if(count($this->Modules))
      {
        $DB->free_result($getmodules);
      }
    }
    $DB->ignore_error = false;

    unset($module,$getmodules);

  } //LoadModules


  // ##########################################################################

  public function GetModule ($Modulename)
  {
    return isset($this->Modules[$Modulename]) ? $this->Modules[$Modulename] : false;
  } //GetModule


  // ##########################################################################

  public function GetModuleByIndex ($index)
  {
    $keys = array_keys($this->Modules);
    if(array_key_exists($index, $keys))
    {
      return $this->Modules[$keys[$index]];
    }
    return false;
  } //GetModuleByIndex


  // ##########################################################################

  public function SetModule ($Module)
  {
    if(isset($Module) && is_array($Module))
    {
      if($Modulename = (isset($Module['name']{1}) ? $Module['name'] : false))
      {
        unset($this->Modules[$Modulename]);
        $this->Modules[$Modulename] = $Module;
      }
    }
  } //SetModule


  // ##########################################################################

  public function ModuleCount ()
  {
    return empty($this->Modules) ? 0 : count($this->Modules);
  }


  // ##########################################################################

  public function DisplaySettingsForm($modulename)
  {
    global $DB;

    if(!defined('IN_ADMIN') || !isset($this->Modules[$modulename]))
    {
      return;
    }
    $module = $this->Modules[$modulename];

   echo '<h3 class="header blue lighter">' . $module['displayname'] . ' Settings' . '</h3>';

    echo '
    <form enctype="multipart/form-data" method="post" class="form-horizontal" action="settings.php?display_type=modules'./*$module['settingspath'].*/'">
    <input type="hidden" name="modulename" value="'.$module['name'].'" />
    ';
    $getsettings = $DB->query('SELECT * FROM {modulesettings} WHERE moduleid = %d'.
                              ' AND editable = 1 ORDER BY displayorder',
                              $module['moduleid']);
    while($setting = $DB->fetch_array($getsettings,null,MYSQL_ASSOC))
    {
      PrintAdminSetting($setting);
    } //while


    PrintSubmit('updatemodulesettings', 'Save Settings');
    echo '</form><br />';


  } //DisplaySettingsForm

} //class ends here

} //DO NOT REMOVE!
