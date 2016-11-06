<?php
if(!defined('IN_PRGM')) exit();

// LEGACY FUNCTIONS
function PrintHeader() {}
function PrintFooter() {}

// ############################################################################
// UpdateUserPluginsPageIDs
// ############################################################################
function UpdateUserPluginsPageIDs($mobile=false)
{
  //SD322: rewrote function to take into account missing mainsettings entries
  //SD370: added $mobile parameter to use pagesort_mobile
  global $DB, $SDCache, $mainsettings_search_results_page,
         $mainsettings_tag_results_page;
  $mobile = !empty($mobile) && SD_MOBILE_FEATURES;
  $user_registration_page_id = $user_profile_page_id = $user_login_panel_page_id = 0;
  $values = array();
  $prefix = $mobile ? 'mobile' : 'user';
  $getprevious = $DB->query("SELECT varname, IFNULL(value,0) value FROM {mainsettings}".
                            " WHERE varname IN ('".$prefix."_login_panel_page_id',".
                            " '".$prefix."_profile_page_id','".$prefix."_registration_page_id')");
  while($tmp = $DB->fetch_array($getprevious))
  {
    $values[$tmp['varname']] = intval($tmp['value']);
  }
  //SD370: exclude search/tags pages
  if($getpages = $DB->query('SELECT DISTINCT pluginid, MIN(categoryid) categoryid'.
                            ' FROM '.PRGM_TABLE_PREFIX.'pagesort'.($mobile ? '_mobile' : '').
                            " WHERE pluginid IN ('10','11','12')".
                            ' AND categoryid NOT IN (%d,%d)'.
                            ' GROUP BY pluginid'.
                            ' ORDER BY pluginid, categoryid',
                            $mainsettings_search_results_page,
                            $mainsettings_tag_results_page))
  {
    while($pages_arr = $DB->fetch_array($getpages,null,MYSQL_ASSOC))
    {
      switch($pages_arr['pluginid'])
      {
        case 10: $user_login_panel_page_id = empty($user_login_panel_page_id) ? (int)$pages_arr['categoryid'] : $user_login_panel_page_id; break;
        case 11: $user_profile_page_id = empty($user_profile_page_id) ? (int)$pages_arr['categoryid'] : $user_profile_page_id; break;
        case 12: $user_registration_page_id = empty($user_registration_page_id) ? (int)$pages_arr['categoryid'] : $user_registration_page_id; break;
      }
    }
  }
  if(empty($values[$prefix.'_login_panel_page_id']) ||
     empty($user_login_panel_page_id) ||
     ($user_login_panel_page_id != $values[$prefix.'_login_panel_page_id']))
  {
    $DB->result_type = MYSQL_ASSOC;
    if(isset($values[$prefix.'_login_panel_page_id']))
    {
      $DB->query('UPDATE {mainsettings} SET value = %d'.
                 " WHERE varname = '".$prefix."_login_panel_page_id'".
                 ' AND value <> %d LIMIT 1',
                  $user_login_panel_page_id, $user_login_panel_page_id);
    }
    else
    {
      InsertMainSetting($prefix.'_login_panel_page_id', '', '', '', 'text', $user_login_panel_page_id);
    }
    //SD342 delete cache files related to page
    if(isset($SDCache) && $SDCache->IsActive())
    {
      $SDCache->delete_cacheid(CACHE_PAGE_PREFIX.$user_login_panel_page_id);
      $SDCache->delete_cacheid(MOBILE_CACHE.CACHE_PAGE_PREFIX.$user_login_panel_page_id);
    }
  }
  if(empty($values[$prefix.'_profile_page_id']) ||
     empty($user_profile_page_id) ||
     ($user_profile_page_id != $values[$prefix.'_profile_page_id']))
  {
    $DB->result_type = MYSQL_ASSOC;
    if(isset($values[$prefix.'_profile_page_id']))
    {
      $DB->query('UPDATE {mainsettings} SET value = %d'.
                 " WHERE varname = '".$prefix."_profile_page_id'".
                 ' AND value <> %d LIMIT 1',
                  $user_profile_page_id, $user_profile_page_id);
    }
    else
    {
      InsertMainSetting($prefix.'_profile_page_id', '', '', '', 'text', $user_profile_page_id);
    }
    //SD342 delete cache files related to page
    if(isset($SDCache) && $SDCache->IsActive())
    {
      $SDCache->delete_cacheid(CACHE_PAGE_PREFIX.$user_profile_page_id);
      $SDCache->delete_cacheid(MOBILE_CACHE.CACHE_PAGE_PREFIX.$user_profile_page_id);
    }
  }
  if(empty($values[$prefix.'_registration_page_id']) ||
     empty($user_registration_page_id) ||
     ($user_registration_page_id != $values[$prefix.'_registration_page_id']))
  {
    $DB->result_type = MYSQL_ASSOC;
    if(isset($values[$prefix.'_registration_page_id']))
    {
      $DB->query('UPDATE {mainsettings} SET value = %d'.
                 " WHERE varname = '".$prefix."_registration_page_id'".
                 ' AND value <> %d LIMIT 1',
                  $user_registration_page_id, $user_registration_page_id);
    }
    else
    {
      InsertMainSetting($prefix.'_registration_page_id', '', '', '', 'text', $user_registration_page_id);
    }
    //SD342 delete cache files related to page
    if(isset($SDCache) && $SDCache->IsActive())
    {
      $SDCache->delete_cacheid(CACHE_PAGE_PREFIX.$user_registration_page_id);
      $SDCache->delete_cacheid(MOBILE_CACHE.CACHE_PAGE_PREFIX.$user_registration_page_id);
    }
  }
  return true;
} //UpdateUserPluginsPageIDs

// ############################################################################
// PRINT REDIRECT
// ############################################################################
// legacy, RedirectPage() now used
function PrintRedirect($gotopage, $timeout = 2, $message = "Settings Updated!")
{
  RedirectPage($gotopage, $message, intval($timeout));
}

// ############################################################################
// PRINT SECTION
// ############################################################################
// legacy, StartSection() now used
function PrintSection($section_name)
{
  StartSection($section_name);
  /*
  echo '<h1>' . $section_name . '</h1>
        <div class="table_wrap">
        <div class="form_wrap">';
  */
}

/**
* Returns a responsive table with desired classes
*
* @param string $section_name
* @param array class
* @param bool return
* @return string
*/
function StartTable($section_name, $class = array(), $return = FALSE)
{
	$r = '
		<!-- Start Table -->
		<div class="table-responsive">
			<div class="table-header"> ' . $section_name . '</div>
				<table class="' . @implode(' ', $class) . '">';
	if($return)
		return $r;
		
	echo $r;
}


// ############################################################################
// PRINT PLUGIN SETTINGS
// ############################################################################
function PrintAdminSetting($setting, $pluginphrases=array(), $no_tr=false, $no_td=false, $pluginid=0)
{ //SD350: added param $pluginid; primarily called by "PrintPluginSettings"
  global $DB, $mainsettings, $admin_phrases, $plugin_names;
  $input = isset($setting['input']) ? strtolower(trim($setting['input'])) : 'text';
  $no_td = !empty($no_td);
  $no_tr = !empty($no_tr);
  
 
  if(!$no_tr)
 // echo '
 // <tr>
 //   ';
  // Display BBCode and WYSIWYG editors in 2 columns
  if(($input=='bbcode') || ($input=='wysiwyg'))
    $columns = 2;
  else
    $columns = 1;
  if(!$no_td)
  if($columns > 1)
  {
	  echo '<div class="form-group">';
   // echo '<td class="td2" colspan="'.$columns.'" valign="top" width="100%">';
  }
  else
  {
      echo '<div class="form-group">';
	//echo '<td class="td2" valign="top" width="55%">';
  }
  if(isset($setting['title']))
    $setting['title'] = isset($admin_phrases[$setting['title']]) ? $admin_phrases[$setting['title']] : $setting['title'];
  else
    $setting['title'] = '';
  $setting['description'] = isset($admin_phrases[$setting['description']]) ? $admin_phrases[$setting['description']] : $setting['description'];
  
  if(strlen($setting['title']))
  {
  	echo '<label class="control-label col-sm-3" for="settings[' . $setting['settingid'] . ']">' . $setting['title'] . '
  		<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="'. strip_tags(($setting['description']))  . '" title="Help">?</span></label>
  			<div class="col-sm-6">';
  }
  elseif(strlen($setting['description']))
  {
	  echo '<label class="control-label col-sm-3" for="settings[' . $setting['settingid'] . ']">' . $setting['description'] . '
  		</label>
  			<div class="col-sm-6">';
  }
  
  if(!$no_td)
 // echo '
  //  </td>';
  if(!$no_td && ($columns==1))
  {
  //  echo '
  //  <td>';
  }
  # ---------------------------------------------------------------------------
  if(!isset($setting['input']) || (strlen($setting['input']) < 3))
  {
    echo '<strong>'.AdminPhrase('err_invalid_setting_format').'</strong>';
  }
  else
  # ---------------------------------------------------------------------------
  if(($input=='usergroup') || ($input=='usergroups')) //SD322
  {
    $groups = sd_ConvertStrToArray($setting['value'], (isset($setting['separator'])?(string)$setting['separator']:','));
    $NoneSelected = (empty($groups) || (isset($groups[0]) && ($groups[0]=='-1')));
    echo '<select class="form-control" name="settings[' . $setting['settingid'] . ']';
    if($input=='usergroups')
    {
      echo '[]" multiple="multiple" ';
    }
    echo '"'.(empty($setting['style'])?'':' style="'.$setting['style'].'"').'>';
    if($input=='usergroups')
    {
      echo '
        <option value="-1" '.
        ($NoneSelected ?' selected="selected"':'').'>'.
        AdminPhrase('no_usergroup_option').'</option>';
    }
    if($getrows = $DB->query('SELECT usergroupid, name FROM {usergroups}'.
                             ' ORDER BY usergroupid'))
    {
      while($ug = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
      {
        echo '
        <option value="'.$ug['usergroupid'].'" '.
        (in_array($ug['usergroupid'],$groups)?' selected="selected"':'').'>'.
        $ug['name'].'</option>';
      }
    }
    echo '
      </select><br />';
  }
  else
  # ---------------------------------------------------------------------------
  if(($input=="page") || ($input=="pages")) //SD341
  {
    $pages = sd_ConvertStrToArray($setting['value']);
    echo '<select class="form-control" name="settings[' . $setting['settingid'] . ']';
    if($input=="pages")
    {
      echo '[]" multiple="multiple" size="8';
    }
    echo '">
      <option value="0" '.(empty($pages) || ($pages[0]=='0')?' selected="selected"':'').'>-</option>';
    $allpages = array();
    $getrows = $DB->query('SELECT categoryid, name FROM {categories} ORDER BY parentid, displayorder, categoryid');
    while($page = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
    {
      echo '<option value="'.$page['categoryid'].'" '.
           (in_array($page['categoryid'],$pages)?' selected="selected"':'').'>'.$page['name'].'</option>';
    }
    echo '</select>';
  }
  else
  # ---------------------------------------------------------------------------
  if($input=='template') //SD350
  {
    $tmpl = $setting['value'];
    echo '<select class="form-control" name="settings[' . $setting['settingid'] . ']">';
    if(empty($mainsettings['templates_from_db'])) //SD350
    {
      clearstatcache();
      $folderpath = SD_INCLUDE_PATH.'tmpl/';
      $files1 = @scandir($folderpath);
      $files2 = @scandir($folderpath.'defaults/');
      $files = @array_merge((array)$files1, (array)$files2);
      echo '<option value="0" '.(empty($files)||empty($tmpl) ?' selected="selected"':'').'>Default</option>';
      if(!empty($files))
      {
        $files = array_unique($files);
        natcasesort($files);
        foreach($files as $filename)
        {
          if((substr($filename,0,8) == 'articles') && (strrpos($filename,'.tpl') !== false))
          {
            echo '<option value="'.htmlspecialchars($filename).'"'.
                 ($filename==$tmpl ?' selected="selected"':'').'>'.$filename.'</option>';
          }
        }
      }
    }
    else
    {
      //SD350: display templates for current plugin ($pluginid) from DB
      if($getrows = $DB->query('SELECT tpl_name, displayname'.
                               ' FROM '.PRGM_TABLE_PREFIX.'templates'.
                               ' WHERE pluginid = %d ORDER BY displayname',$pluginid))
      {
        while($row = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
        {
          echo '<option value="'.$row['tpl_name'].'" '.
               ($row['tpl_name']==$tmpl?' selected="selected"':'').'>'.$row['displayname'].'</option>';
        }
      }
    }
    echo '</select>';
  }
  else
  # ---------------------------------------------------------------------------
  if((substr($input,0,7)=='plugin:') || (substr($input,0,8)=='plugins:')) //SD341
  {
    $pname = explode(':',$setting['input']);
    $pname = count($pname) > 1 ? $pname[1] : '';
    echo '<select class="form-control" name="settings[' . $setting['settingid'] . ']';
    if(substr($input,0,8)=="plugins:")
    {
      echo '[]" multiple="multiple" size="8';
    }
    echo '">';
    $allplugins = array();
    $pluginvalues = sd_ConvertStrToArray($setting['value']);
    if(empty($pname) || ($pname=='multiple')) //SD360: "multiple" to show all
      $getrows = $DB->query('SELECT pluginid, name FROM {plugins} ORDER BY name');
    else
      $getrows = $DB->query("SELECT pluginid, name FROM {plugins} WHERE name = '%s' OR base_plugin = '%s' ORDER BY name",
                            $DB->escape_string($pname), $DB->escape_string($pname));
    while($p = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
    {
      $pid = (int)$p['pluginid'];
      if(isset($plugin_names[$pid])) $p['name'] = $plugin_names[$pid];
      $allplugins[$p['name']] = $pid;
    }
    //SD370: output new "No selection" option
    $NoneSelected = empty($pluginvalues) || ((count($pluginvalues)==1) && ($pluginvalues[0]=='-1'));
    echo '
      <option value="-1" '.($NoneSelected ?' selected="selected"':'').'>'.
        AdminPhrase('no_selection').'</option>';
    @ksort($allplugins); //SD343: ksort instead of natsort
    foreach($allplugins as $pname => $pid)
    {
      echo '<option value="'.$pid.'" '.(in_array($pid,$pluginvalues)?' selected="selected"':'').'>'.$pname.'</option>';
    }
    echo '</select><br />';
  }
  else
  # ---------------------------------------------------------------------------
  if($input == 'color')
  {
    $color_inputs[] = 'setting_'.$setting['settingid'];
    $color = htmlspecialchars($setting['value']);
    if(substr($color,0,1)=='#')
    {
      $color = substr($color,1,6);
    }
    echo '<input class="colorpicker form-control" type="text" size="8" id="setting_'.
         $setting['settingid'].'" name="settings['.$setting['settingid'].
         ']" maxlength="7" style="width: 55px;" value="'.$color.'" />';
  }
  else
  # ---------------------------------------------------------------------------
  if($input == 'text')
  {
    echo '<input type="text" class="form-control"  name="settings['.$setting['settingid'].
         ']" value="'.htmlspecialchars($setting['value']).'" />';
  }
  else
  # ---------------------------------------------------------------------------
  if($input == 'password')
  {
    echo '<input type="password" class="form-control" name="settings['.$setting['settingid'].
         ']" value="'.htmlspecialchars($setting['value']).'" />';
  }
  else
  # ---------------------------------------------------------------------------
  if($input == 'yesno')
  {
    echo ' 
				<input type="radio" class="ace" name="settings['.$setting['settingid'].']"'. (!empty($setting['value']) ? ' checked="checked"' : '').' value="1" />
		 		<span class="lbl"> ' . AdminPhrase('common_yes') . '</span>
				&nbsp;
			
		 		<input type="radio" class="ace" name="settings['.$setting['settingid'].']"'.(empty($setting['value'])  ? ' checked="checked"' : '').' value="0" />
		 		<span class="lbl"> '. AdminPhrase('common_no').'</span>
			<br />';
  }
  //SD322: new input type "select" and "select-multi" with simple "value|phrase" lines
  // See further explanations in functions_global.php => sd_ParseToSelect
  //SD350: fix to allow "select\r\n"
  else
  # ---------------------------------------------------------------------------
  if((substr($input,0,7) == "select\n") || (substr($input,0,8) == "select\r\n") ||
     (substr($input,0,7) == 'select:') || (substr($input,0,13) == 'select-multi:'))
  {
    echo sd_ParseToSelect($setting['input'], $setting['value'], $setting['title'],
                          'settings['.$setting['settingid'].']', $pluginphrases);
  }
  // SD313: split textarea and wysiwyg in separate branches
  else
  # ---------------------------------------------------------------------------
  if($input == 'textarea')
  {
    echo '<textarea class="form-control" name="settings['.$setting['settingid'].']">'.
         htmlspecialchars($setting['value']).'</textarea>';
  }
  // SD314: bbcode
  else
  # ---------------------------------------------------------------------------
  if($input == 'bbcode')
  {
    // Set special define to have JS included by "DisplayAdminFooter"
    defined('ADMIN_BBCODE') || define('ADMIN_BBCODE', true);
   // echo ' </td></tr><tr><td colspan="2">';
    echo '<textarea class="bbcode form-control" name="settings['.$setting['settingid'].']">'.htmlspecialchars($setting['value']).'</textarea>';
  }
  else
  # ---------------------------------------------------------------------------
  if($input == 'timezone')
  {
    // SD313: print HTML select for a timezone-type setting (in functions_global.php!)
    echo GetTimezoneSelect('settings['.$setting['settingid'].']', $setting['value'], 'settings'.$setting['settingid']);
  }
  else
  # ---------------------------------------------------------------------------
  if($input == 'wysiwyg')
  {
    // SD313: wysiwyg in separate row, spanning 2-columns
   // echo '</tr><tr><td colspan="2">';
    PrintWysiwygElement('settings['.$setting['settingid'].']', htmlspecialchars($setting['value']), 10, 80);
  }
  else
  # ---------------------------------------------------------------------------
  if($input == 'pagesize')
  {
    // SD331: display selection of pagesizes (supported by HTML2PDF/TCPDF as well):
    sd_PrintPagesizeSelect('settings['.$setting['settingid'].']', $setting['value'], 21);
  }
  else
  # ---------------------------------------------------------------------------
  if(substr($input,0,8) == 'datetime') //SD370: optionally line "readonly"
  {
    //TODO: datepicker support!
    // normalize line breaks in $input
    $input = preg_replace("/(\r\n|\n\r|\r|\n)+/", "\n", trim($input));
    // split up individual lines into an array:
    $arr = @preg_split('/\n/', substr($input, 8), -1, PREG_SPLIT_NO_EMPTY);
    if(is_array($arr) && in_array('readonly',$arr))
    {
      if(trim($setting['value'])=='')
        echo '---';
      else
        echo DisplayDate($setting['value'], '', false, true);
      echo '<input type="hidden" name="settings['.$setting['settingid'].']" value="'.intval($setting['value']).'" />';
    }
    else
    {
      $datecreated = DisplayDate($setting['value'], '', false, true);
      echo '
      <input type="hidden" class="date-hidden" id="setting'.$setting['settingid'].'" name="settings['.$setting['settingid'].']" value="' . DisplayDate($setting['value'], 'yyyy-mm-dd') . '" />
      <input type="text" class="datepicker" id="settingd'.$setting['settingid'].'" name="settings[d'.$setting['settingid'].']" rel="'.($datecreated?$datecreated:'0').'" value="" size="10" />';
    }
  }
  else
  //SD370: otherwise only allow legacy "<select ..." input for security
  if(isset($setting['input']) && (strpos($setting['input'], '<select')!==false))
  {
    @eval("echo \"$setting[input]\";");
	echo '<br />';
  }
 // if(!$no_td) echo "\n</td>";
 // if(!$no_tr) echo "</tr>\n";
 if(strlen($setting['description']))
  {
    //echo '<span class="helper-text"> ' .$setting['description'] . '</span>';
  }
 echo '</div></div>';
} //PrintAdminSetting

function PrintPluginSettings($pluginid, $group_names_arr = array('Options'), $ref_page = null)
{
  global $DB, $refreshpage, $mainsettings, $admin_phrases, $load_wysiwyg, $plugin;
  if(!empty($ref_page))
  {
    $refreshpage = $ref_page;
  }
  if(!is_array($group_names_arr))
  {
    $group_names_arr = array($group_names_arr);
  }
  $form_started = false;
  $color_inputs = array();
  // SD313 - 2010-08-28 - add load_wysiwyg to $refreshpage if active
  if(!empty($load_wysiwyg) && strpos($refreshpage,'load_wysiwyg')===false)
  {
    $refreshpage .= (strpos($refreshpage,'?')===false?'?':'&amp;').'load_wysiwyg=1';
  }
  $pluginphrases = GetLanguage($pluginid); //SD322
  for($i = 0; $i < count($group_names_arr); $i++)
  {
    $pluginsettings = $DB->query(
      'SELECT * FROM {pluginsettings}'.
      " WHERE pluginid = %d AND groupname = '%s'".
      ' ORDER BY displayorder, title',
      (int)$pluginid, $DB->escape_string($group_names_arr[$i]));
    if($DB->get_num_rows($pluginsettings) > 0)
    {
      if(!$form_started)
      {
        $form_started = true;
        echo '
        <form method="post" id="pluginsettings" action="'.$refreshpage.'" class="form-horizontal">
        <input type="hidden" name="refreshpage" value="'.$refreshpage.'" />
        <input type="hidden" name="updatesettings" value="Save Settings" />
        '.PrintSecureToken().'
        ';
      }
      if(isset($admin_phrases[$group_names_arr[$i]]))
      {
        echo '<h3 class="header blue lighter">' . AdminPhrase($group_names_arr[$i]) .'</h3>';
      }
      else
      {
        echo '<h3 class="header blue lighter">' . $group_names_arr[$i] . '</h3>';
      }
      
	  
      while($setting = $DB->fetch_array($pluginsettings,null,MYSQL_ASSOC))
      {
        PrintAdminSetting($setting, $pluginphrases, false, false, $pluginid);
      }
      

      $DB->free_result($pluginsettings);
    }
    unset($pluginsettings);
  }
  if($form_started)
  {
    echo '
    <div class="center"><button type="submit" class="btn btn-info" value=""><i class="ace-icon fa fa-check"></i> ' . AdminPhrase('common_update_settings') . '</button>
	</div>
    </form>
   
    ';
    defined('ADMIN_COLORPICKER') || define('ADMIN_COLORPICKER', true);
  }
  else
  {
    $tmp = (!empty($plugin['name']) ? $plugin['name'].': ' : '');
    DisplayMessage($tmp . AdminPhrase('plugins_no_settings_found'));
  }
} //PrintPluginSettings


// ############################################################################
// UPDATE PLUGIN SETTINGS
// ############################################################################
function UpdatePluginSettings($post_settings, $refreshpage)
{
  global $DB, $SDCache, $pluginid, $sdlanguage;
  //SD313: The PrintPlugins form generates a security token for $_POST!
  //SD370: pluginid must be set at this point
  //SD370: processing re-arranged, so that the real settings of the plugin
  // are the lead values and are checked against posted settings; this allows
  // for better sanity (no settings outside of plugin are changed) and to
  // actually clear values for e.g. multi-selects (browsers do not post
  // "empty" selections for select elements)
  
  if(empty($pluginid) || ($pluginid < 2) ||
     !CheckFormToken()) // in functions_security.php
  {
    RedirectPage($refreshpage, '<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />', 2, true);
    return false;
  }
  if(isset($post_settings) && is_array($post_settings))
  {
    //SD370: fetch existing plugin settings for specified groups
    $pset = array();
    if($groups = GetVar('groupnames', false, 'array', true, false))
    {

      foreach($groups as $name)
      {
        $tmp = GetPluginSettings($pluginid,$name,'',true,true);
        foreach($tmp as $settingid => $value)
        {
          $pset[$settingid] = $value;
        }
      }
    }
	
	
    //SD370: sanity check: count of posted values cannot be more than existing,
    // real plugin settings
    // COMMENTED OUT FOR TIME BEING SINCE IT DOES NOT ACCOUNT FOR
    // "HIDDEN" FORM FIELDS!
    /*
    if( (count($post_settings) > count($pset)) )
    {
      RedirectPage($refreshpage, '<strong>Settings error (count off)!</strong><br />', 2, true);
      return false;
    }
    */
    //SD370: iterate through existing settings and check for posted value
	
    foreach($post_settings as $settingid => $row)
    {
      //SD370: if real setting $settingid has no posted value, then the setting
      // must be cleared (e.g. no entry selected in a multi-select!)
      if(!isset($post_settings[$settingid]))
      {
        $value = '';
      }
      else
      {
        $value = $post_settings[$settingid];
        //SD322: support for array-typed options
        if(is_array($value))
        {
          //SD370: remove "-1" from "plugins:" list, treat as empty selection:
          if( empty($value) ||
              ((substr($row['input'],0,8)=='plugins:') && (count($value)==1) && ($value[0]=='-1')))
          {
            $value = '';
          }
          else
          {
            $value = sd_unhtmlspecialchars(implode(',',$value));
            //SD370: convert CR/LF to just LF
            $value = preg_replace("/(\r\n|\n\r|\r|\n)+/", "\n", trim($value));
            $value = preg_replace("/\x01-\x08+/", '', trim($value));
            $value = trim($value,','); //SD370
          }
        }
        else
        {
          //SD370: new "datetime" type
          if(substr($row['input'],0,8) == 'datetime')
          {
            // normalize line breaks in $input
            $row['input'] = preg_replace("/(\r\n|\n\r|\r|\n)+/", "\n", trim($row['input']));
            // split up individual lines into an array:
            $arr = preg_split('/\n/', substr($row['input'], 8), -1, PREG_SPLIT_NO_EMPTY);
            // if setting is readonly, then skip to next setting
            if(is_array($arr) && in_array('readonly',$arr)) continue;
            //TODO: datepicker support!
            if(false === ($value = @strtotime(trim($value))))
            {
              $value = 0;
            }
          }
          else
          {
            //SD370: convert CR/LF to just LF
            $value = preg_replace("/(\r\n|\n\r|\r|\n)+/", "\n", trim($value));
            $value = preg_replace("/\x01-\x08+/u", '', trim($value));
            $value = sd_unhtmlspecialchars($value);
          }
        }
      }
	 
      $DB->query("UPDATE {pluginsettings} SET value = '".$DB->escape_string($value).
                 "' WHERE settingid = %d", (int)$settingid);
    }
    // SD313x: Check to remove existing cache file for plugin
    if(!empty($pluginid))
    {
      $SDCache->delete_cacheid('psettings_'.(int)$pluginid);
    }
  }
  RedirectPage($refreshpage, AdminPhrase('common_plugin_settings_updated'),1);
} //UpdatePluginSettings
// ############################################################################
// UPDATE PLUGIN SETTINGS
// ############################################################################
// legacy function
function PrintSubmit($action, $value, $formid='', $icon='fa-check', $align='center', $hint='', $class='btn-primary')
{
  //SD370: added formid, icon, align, hint parameters
  echo '
  <input type="hidden" name="action" value="'.$action.'" />
  <div class="'.$align.'">
  ';
  if(!empty($formid))
  {
    echo '<button class="btn ' . $class . '" type="submit" value="'.$value.'" '.(empty($hint)?'':' title="'.addslashes($hint).'" ').'id="submit_'.$formid.'" ><i class="ace-icon fa ' . $icon . ' bigger-110"></i>' . $value . '</button>';
  }
  else
  {
    echo '
    <button class="btn ' . $class .'" type="submit" value="'.$value.'" />
		<i class="ace-icon fa ' . $icon .' bigger-110"></i>
		'.$value.'
		</button>';
  }
  echo '
   
  </div>';
} //PrintSubmit
// ############################################################################
// PRINT ERRORS
// ############################################################################
// legacy code
function PrintErrors($errors, $errortitle = '')
{
  DisplayMessage($errors, true, !empty($errortitle)?$errortitle:'');
}
// #############################################################################
// DISPLAY CATEGORY SELECTION WITH ARTICLE PLUGIN PRESENT - SD350
// #############################################################################
function DisplayArticleCategories($pluginid, $categoryid = 0, $showzerovalue = 0,
                                  $parentid = 0, $sublevelmarker = '',
                                  $selectname = 'parentid', $style='',
                                  $checkViewPermission=false,
                                  $mustHavePlugin=false)
//SD322: added "$style" for custom style attribute content for select tag
//SD322: added "$restricted" to only display pages for which the user - if not
//       a full admin - has "submit" permissions
//SD360: added "$mustHavePlugin": if true, the category must have plugin
{
  global $DB, $userinfo, $pages_md_arr,
         $mainsettings_tag_results_page,
         $mainsettings_search_results_page;
  // start selection box
  if(empty($parentid)) $parentid = 0;
  if($parentid == 0)
  {
    echo '
    <select class="form-control" id="' . $selectname . '" name="' . $selectname . '" '.(empty($style)?'':'style="'.$style.'"').'>';
    if($showzerovalue)
    {
      echo '
      <option value="0">-</option>';
    }
  }
  else
  {
    $sublevelmarker .= '- - ';
  }
  $mustHavePlugin = !empty($mustHavePlugin);
  if(!$mustHavePlugin) //SD360
  {
    // Select ALL categories, regardless of plugin
    $sql = 'SELECT c.displayorder, c.title, c.categoryid, c.parentid, c.name,'.
           " (SELECT 1 FROM {pagesort} p WHERE p.categoryid = c.categoryid AND p.pluginid = '%s') tmp".
           ' FROM {categories} c'.
           ' WHERE IFNULL(c.parentid,0) = %d '.
           /*
             'UNION '.
             'SELECT c.displayorder, c.title, c.categoryid, c.parentid, c.name, 1 tmp'.
             ' FROM '.PRGM_TABLE_PREFIX.'categories c'.
             ' WHERE c.categoryid = '.(int)$categoryid.
             ' ORDER BY 1, 2'
             */
           ' ORDER BY c.displayorder, c.title'
           ;
  }
  else
  {
    // Only select categories, that have plugin in it
    /*
    $sql = 'SELECT c.categoryid, c.parentid, c.name'.
           ' FROM '.PRGM_TABLE_PREFIX.'categories c'.
           ' INNER JOIN '.PRGM_TABLE_PREFIX.'pagesort p ON p.categoryid = c.categoryid'.
           " WHERE (p.pluginid = '%s')".
           ' AND IFNULL(c.parentid,0) = %d'.
           ' ORDER BY c.displayorder, c.title';
    */
    $sql = 'SELECT c.displayorder, c.title, c.categoryid, c.parentid, c.name'.
           ' FROM '.PRGM_TABLE_PREFIX.'categories c'.
           ' INNER JOIN '.PRGM_TABLE_PREFIX.'pagesort p ON p.categoryid = c.categoryid'.
           " WHERE (p.pluginid = '%s')".
           #' AND IFNULL(c.parentid,0) = %d'.
           'UNION '.
           'SELECT c.displayorder, c.title, c.categoryid, c.parentid, c.name'.
           ' FROM '.PRGM_TABLE_PREFIX.'categories c'.
           ' WHERE c.categoryid = '.(int)$categoryid.
           ' ORDER BY 4, 1, 2'
           #' ORDER BY c.displayorder, c.title'
           ;
  }
/*
  $categories = array();
  $cat_id = $categoryid;
  while(!empty($cat_id))
  {
    if(isset($pages_md_arr[$cat_id]))
    {
      $cat = $pages_md_arr[$cat_id];
      array_unshift($categories, $cat['name']);
      $cat_id = $cat['parentid'];
    }
    else
    {
      break;
    }
  }
  $cat_str = implode(' -- ', $categories);
*/
  //SD350: show all pages, but color-code pages which have plugin in it
  //Previously pages were missing if plugin was in deeper level pages.
  if($getcategories = $DB->query($sql, $pluginid, $parentid))
  {
    while($category = $DB->fetch_array($getcategories,null,MYSQL_ASSOC))
    {
      // Exclude Tags page
      if(!empty($mainsettings_tag_results_page) &&
         ($mainsettings_tag_results_page==$category['categoryid']))
      {
        continue;
      }
      // Exclude Search Results page
      if(!empty($mainsettings_search_results_page) &&
         ($mainsettings_search_results_page==$category['categoryid']))
      {
        continue;
      }
      if($mustHavePlugin)
      {
        $categories = array();
        $cat_id = $category['categoryid'];
        while(!empty($cat_id))
        {
          if(isset($pages_md_arr[$cat_id]))
          {
            $cat = $pages_md_arr[$cat_id];
            array_unshift($categories, $cat['name']);
            $cat_id = $cat['parentid'];
          }
          else
          {
            break;
          }
        }
        $sublevelmarker = implode(' -- ', $categories);
      }
      //SD322: only display category (and it's sub-categories) if the current
      // user is either admin or has view permission for it:
      if( !empty($userinfo['adminaccess']) || empty($checkViewPermission) ||
          (!empty($userinfo['categoryviewids']) && @in_array($category['categoryid'], $userinfo['categoryviewids'])) )
      {
        $colored = ($mustHavePlugin || !empty($category['tmp']));
        echo '
        <option value="' . $category['categoryid'] . '" '.
          ($colored?'style="background-color:Green;color: #FFFFFF;" ':'').
          ($categoryid == $category['categoryid'] ? 'selected="selected"' : '').'>'.
          $sublevelmarker . ($mustHavePlugin?'':$category['name']) . '</option>';
      }
      else
      {
        $sublevelmarker = '';
      }
      if(!$mustHavePlugin && ($categoryid!=$category['categoryid']))
      DisplayArticleCategories($pluginid, $categoryid /*$category['categoryid']*/, $showzerovalue, $category['categoryid'],
                               $sublevelmarker, '', $style,
                               $checkViewPermission, $mustHavePlugin);
    }
    $DB->free_result($getcategories);
  }
  if($parentid == 0)
  {
    echo '
    </select>';
  }
} //DisplayArticleCategories
// #############################################################################
// DISPLAY CATEGORY SELECTION
// #############################################################################
function DisplayCategorySelection($categoryid = 0, $showzerovalue = 0, $parentid = 0,
                                  $sublevelmarker = '', $selectname = 'parentid',
                                  $style='', $checkViewPermission=false)
//SD322: added "$style" for custom style attribute content for select tag
//SD322: added "$restricted" to only display pages for which the user - if not
//       a full admin - has "submit" permissions
{
  global $DB, $userinfo;
  // start selection box
  if($parentid == 0)
  {
    echo '
    <select class="form-control" id="' . $selectname . '" name="' . $selectname . '" '.(empty($style)?'':'style="'.$style.'"').'>';
    if($showzerovalue)
    {
      echo '
      <option value="0">-</option>';
    }
  }
  else
  {
    $sublevelmarker .= '- - ';
  }
  if($getcategories = $DB->query('SELECT categoryid, parentid, name FROM {categories} WHERE parentid = %d
                                  ORDER BY displayorder, title', $parentid))
  {
    while($category = $DB->fetch_array($getcategories,null,MYSQL_ASSOC))
    {
      //SD322: only display category (and it's sub-categories) if the current
      // user is either admin or has view permission for it:
      if( !empty($userinfo['adminaccess']) || empty($checkViewPermission) ||
          (!empty($userinfo['categoryviewids']) && @in_array($category['categoryid'], $userinfo['categoryviewids'])) )
      {
        $selected = ($categoryid == $category['categoryid']);
        echo '
        <option value="' . $category['categoryid'] . '" ' .
        ($selected ? 'selected="selected"' : '') . '>' .
        $sublevelmarker .
        (strlen(trim($category['name']))?$category['name']:'(ID: '.$category['categoryid'].')') . '</option>';
      }
      else
      {
        $sublevelmarker = '';
      }
      DisplayCategorySelection($categoryid, $showzerovalue, $category['categoryid'], $sublevelmarker, $style, $checkViewPermission);
    }
    $DB->free_result($getcategories);
  }
  if($parentid == 0)
  {
    echo '
    </select>';
  }
} //DisplayCategorySelection
// ############################################################################
// CONFIRM DELETE
// ############################################################################
//SD370: added $icon_xxx parameters
function ConfirmDelete($description, $hiddenvalues = '', $formredirect = '',
                       $icon_yes='check', $icon_no='times')
{
  if(empty($formredirect)) return false;
  echo '
  <form id="formconf" method="post" action="'.$formredirect.'">
  '.PrintSecureToken().'
  '.$hiddenvalues;
  StartSection(AdminPhrase('common_confirm_deletion'));
  echo '<table class="table table-bordered">
  <tr>
    <td class="td2">' . $description . '</td>
  </tr>
  </table>';
  EndSection();
  echo '<div class="center">
  <input type="hidden" id="confirmdelete" name="confirmdelete" value="' . AdminPhrase('common_no') . '" />
  <a href="#" class="btn btn-danger" onclick="javascript:jQuery(\'#confirmdelete\').val(\''.addslashes(AdminPhrase('common_yes')).'\');jQuery(\'#formconf\').submit(); return false;">'.
    (empty($icon_yes) ? '' : '<i class="ace-icon fa fa-'.$icon_yes.'"></i>&nbsp;').
    AdminPhrase('common_yes').'</a> &nbsp;&nbsp;&nbsp;
  <input type="submit" value="'.AdminPhrase('common_yes').'" style="position:absolute;top:-9999px;left:-9999px;display:none;margin-left:-9999px" />
  <a href="#" class="btn btn-default" onclick="javascript:jQuery(\'#formconf\').submit(); return false;">'.
    (empty($icon_no) ? '' : '<i class="ace-icon fa fa-'.$icon_no.'"></i>&nbsp;').
    AdminPhrase('common_no').'&nbsp;</a>
  <input type="submit" value="'.AdminPhrase('common_no').'" style="position:absolute;top:-9999px;left:-9999px;display:none;margin-left:-9999px" />';
  /*
  echo '
    <input type="submit" name="confirmdelete" value="' . AdminPhrase('common_yes') . '" />
    <input type="submit" name="confirmdelete" value="' . AdminPhrase('common_no') . '" />';
  */
  echo '
  </div>
  </form>';
  return true;
} //ConfirmDelete
// ############################# CLEAN FORM VALUE #############################
// legacy function, all data now goes through preclean
// kept for old downloaded plugins
function CleanFormValue($value)
{
  if(!isset($value) || ($value==''))
  {
    return '';
  }
  else
  {
    return htmlspecialchars(sd_unhtmlspecialchars($value), ENT_QUOTES, SD_CHARSET);
  }
} //CleanFormValue
// ################## CHECK THAT THIS IS THE CORRECT SD VERSION ################
function RequiredVersion($version,$minorVersion=null)
{
  global $DB, $mainsettings;
  if(!isset($mainsettings) || !isset($mainsettings['sdversion']))
  {
    // SD313: fix: here undefined $sdversion was used
    $DB->result_type = MYSQL_ASSOC;
    $curVersion = $DB->query_first('SELECT value FROM {mainsettings}'.
                                   " WHERE varname = 'sdversion'");
    $curVersion = isset($curVersion['value']) ? $curVersion['value'] : '';
	
  }
  else
  {
    $curVersion = $mainsettings['sdversion'];
  }
  
  	// SD400: Use php version_compare
	$reqVersion = $version . '.' . $minorVersion . '.0';
	
	if(version_compare($curVersion, $reqVersion, '>='))
	{
		return true;
	}
	
	
  
  if(strpos($curVersion, ' ') > 0)
  {
    $curVersion = substr($curVersion , 0, strpos($curVersion, ' '));
  }
  if(strpos($curVersion, '-') > 0)
  {
    $curVersion = substr($curVersion , 0, strpos($curVersion, '-'));
  }
  $currentValue = intval(str_pad(trim(str_replace('.', '', $curVersion)), 4, '0'));
  
  //SD2 compatiblity for parameters like (2,0)
  if(isset($minorVersion))
  {
    $version .= '.'.$minorVersion;
  }
  $rqdValue = intval(str_pad(trim(str_replace('.', '', $version)), 4, '0'));
  return ($currentValue >= $rqdValue);
} //RequiredVersion


// ####################### SORT A STRING LIST's VALUES #########################
function sd_SortIdList($id_list, $separator = ',', $sorttype = SORT_NUMERIC)
{
  if(isset($id_list) && strlen($id_list))
  {
    if($separator != ' ')
    {
      $temp = str_replace(' ', '', $id_list);
    }
    else
    {
      $temp = str_replace($separator.$separator, $separator, $id_list);
    }
    $temp = explode($separator, $temp);
    if(!empty($temp) && is_array($temp))
    {
      if(@sort($temp, $sorttype))
      {
        $id_list = @implode($separator, $temp);
      }
    }
    unset($temp);
    return $id_list;
  }
  else
  {
    return '';
  }
} //sd_SortIdList
// ############################################################################
// ADMIN SEARCH BAR INITIALISATION
// ############################################################################
function SearchBarInit($cookie_suffix, array $setup_arr)
{
  global $DB;
  $search_arr   = array();
  $customsearch = GetVar('customsearch', 0, 'bool');
  $clearsearch  = GetVar('clearsearch',  0, 'bool');
  $usedb        = isset($setup_arr['use_db']) ? trim($setup_arr['use_db']) : false;
  $use_storage  = false;
  if($clearsearch)
  {
    $customsearch = false;
    if($usedb !== false)
    {
      $DB->query("DELETE FROM {mainsettings} WHERE IFNULL(groupname,'')='' AND varname = 'filterbar_%s'", $DB->escape_string($usedb));
    }
  }
  else
  if(!$customsearch)
  {
    if($usedb !== false)
    {
      $DB->result_type = MYSQL_ASSOC;
      if($val = $DB->query_first('SELECT value FROM {mainsettings}'.
                                 " WHERE IFNULL(groupname,'') = ''".
                                 " AND varname = 'filterbar_%s'",
                                 $DB->escape_string($usedb)))
      {
        if($search_arr = @unserialize(@base64_decode($val['value'])))
        {
          $use_storage = true;
        }
      }
    }
    else
    {
      $use_storage = isset($_COOKIE[COOKIE_PREFIX . $cookie_suffix]) ? $_COOKIE[COOKIE_PREFIX . $cookie_suffix] : false;
    }
  }
  if(!$usedb && ($use_storage !== false))
  {
    // Fetch values from cookie (if allowed and not cleared)
    // Note: using base64*, otherwise cookie would require unhtmlspecialchars() in SD!
    $search_arr = @unserialize(@base64_decode($use_storage));
    // MUST CHECK NOW TO INIT ARRAY:
    if(!$search_arr || !is_array($search_arr))
    {
      $search_arr = array();
    }
  }
  // If a search was specified or "use_buffer", then fetch
  // values from global buffer and not the cookie:
  $use_buffer = !isset($setup_arr['use_buffer']) || $setup_arr['use_buffer'];
  //if($customsearch || $use_buffer)
  if(!$clearsearch && ($customsearch || $use_buffer))
  {
    foreach($setup_arr['fields'] as $fieldname => $conf)
    {
      $type = isset($conf['type']) ? strtolower($conf['type']) : 'string';
      if(($use_storage !== false) && (isset($search_arr[$fieldname])) /* && !empty($conf['hidden'])*/)
      {
        // If a pre-set value comes from the cookie, and the field is hidden,
        // set this as the new default value so it will be used if there's no
        // buffer value present:
        $default = $search_arr[$fieldname];
      }
      else
      {
        $default = isset($conf['default']) ? $conf['default'] : '';
      }
      $search_arr[$fieldname] = GetVar($fieldname, $default, $type,
                                       !empty($setup_arr['allow_post']),
                                       !empty($setup_arr['allow_get']));
    }
  }
  unset($use_storage);
  if(empty($search_arr) || !is_array($search_arr))
  {
    $search_arr = array(); // MUST HAVE!
    foreach($setup_arr['fields'] as $fieldname => $conf)
    {
      $default = isset($conf['default']) ? $conf['default'] : '';
      $search_arr[$fieldname] = $default;
    }
    // Remove search params cookie
    if($usedb === false)
    {
      sd_CreateCookie($cookie_suffix, '');
    }
  }
  // Go through all fields and check for types and default values
  foreach($setup_arr['fields'] as $fieldname => $conf)
  {
    $type = isset($conf['type']) ? strtolower($conf['type']) : 'string';
    $default = isset($conf['default']) ? $conf['default'] : '';
    // Make sure field has a value
    $tmp = isset($search_arr[$fieldname]) ? $search_arr[$fieldname] : $default;
    // The "clear" flag allows to reset the value to the default no matter what
    if(isset($setup_arr['clear_val']) && ($tmp == $setup_arr['clear_val']))
    {
      $search_arr[$fieldname] = $default;
    }
    else
    {
      // If different from default value, check if allowed values
      // were specified and the current value is included:
      // (could be a collection of numbers or literals)
      if(($tmp != $default) && isset($conf['allowed']) && is_array($conf['allowed']))
      {
        $tmp = @in_array($tmp, $conf['allowed']) ? $tmp : $default;
      }
      // Check numeric field types
      if(in_array($type, array('int','natural_number','whole_number','int')))
      {
        $max = isset($conf['max']) ? (int)$conf['max'] : 99999999;
        $min = isset($conf['min']) ? (int)$conf['min'] : 0;
        $search_arr[$fieldname] = Is_Valid_Number($tmp, $default, $min, $max);
      }
      else
      {
        settype($tmp, $type);
        // Otherwise assign value right now
        if(($type=='string') && isset($conf['maxlen']) && is_numeric($conf['maxlen']))
        {
          if(false !== ($substr = substr($tmp,0,intval($conf['maxlen']))))
          {
            $tmp = $substr;
          }
        }
        // Otherwise assign value right now
        $search_arr[$fieldname] = $tmp;
      }
    }
  }
  if($usedb !== false)
  {
    $DB->query("DELETE FROM {mainsettings} WHERE IFNULL(groupname,'')='' AND varname = 'filterbar_%s'", $DB->escape_string($usedb));
    $DB->query("INSERT INTO {mainsettings} (varname, groupname, input, title, description, value)
      VALUES ('filterbar_%s', '', '', '', '', '%s')",
      $DB->escape_string($usedb),@base64_encode(@serialize($search_arr)));
  }
  else
  {
    // Store search params in cookie
    sd_CreateCookie($cookie_suffix,@base64_encode(@serialize($search_arr)));
  }
  return $search_arr;
} //SearchBarInit
// ############################################################################
// ADMIN SEARCH BAR OUTPUT
// ############################################################################
/*
  Example array parameter:
  $searchbar_config_arr['form'] = array(
      'action'  => 'users.php?action=display_users&search=1',
      'id'      => 'searchusers',
      'method'  => 'post',
      'title'   => AdminPhrase('comments_filter_title'),
      'hiddenfields' => array(
        'customsearch'  => '1',
        'clearsearch'   => '0'
      ),
      'columns' => array(
          'username' => array(
              'title' => '',
              'sortable' => false,
              'field'
              ),
      ),
    );
*/
function SearchBarOutput(array $setup_arr)
{
  global $DB, $mainsettings, $sdlanguage;
  if(!is_array($setup_arr) || !isset($setup_arr['form']) || !is_array($setup_arr['form'])) return '';
  if(!extract($setup_arr['form'], EXTR_REFS | EXTR_PREFIX_ALL, 'form'))
  {
    return '';
  }
  // Check some required variables to be present (e.g. action, columns)
  if(empty($form_action) || !isset($form_columns) || !is_array($form_columns) || !count($form_columns))
  {
    return '';
  }
  $form_method = isset($form_method) ? strtolower($form_method) : 'post';
  $form_form_class  = isset($form_form_class)  ? ' class="'.$form_form_class.'" ' : '';
  $r = ''; // Total output result
  // Main form with ID, method and action
  $r .= '
  <form class="form-horizontal" role="form" action="'.$form_action.'"';
  if(isset($form_id) && strlen($form_id))
  {
    $r .= ' id="'.$form_id.'" name="'.$form_id.'"';
  }
  $r .= $form_form_class.' method="'.$form_method.'">'.PrintSecureToken() .  '<div class="table-repsonsive">';
  // Section title - if present
  if(!empty($form_title))
  {
    $r .= '<div class="table-header">' . $form_title .'</div>';
  }
  $r .= '
  <input type="submit" value="Submit" style="display:none" />';
  // Hidden fields - if present
  if(isset($form_hiddenfields) && is_array($form_hiddenfields))
  {
    foreach($form_hiddenfields as $name => $value)
    {
      $r .=  '
    <input type="hidden" name="'.$name.'" value="'.$value.'" />';
    }
  }
  // Main table
  $r .=  '
  <table class="table table-bordered">
  <thead>
  <tr>
  ';
  // 1. Header column (titles)
  $form_cell_class = isset($form_cell_class) ? $form_cell_class : 'tdrow1';
  foreach($form_columns as $name => $opt)
  {
    $style_cell = isset($opt['style_cell']) && strlen(isset($opt['style_cell'])) ? ' style="'.$opt['style_cell'].'"': '';
    $r .=  '<th' . $style_cell .'>' . $opt['title'].'</th>';
  }
  // 2. Header column (user input)
  $r .= '
  </tr>
  </thead>
  <tbody>
  <tr>';
  $DB->ignore_error = true;
  foreach($form_columns as $name => $opt)
  {
    $style = isset($opt['style']) && strlen(isset($opt['style'])) ? 'style="'.$opt['style'].'"': '';
    $style_cell = isset($opt['style_cell']) && strlen(isset($opt['style_cell'])) ? ' style="'.$opt['style_cell'].'"': '';
    $r .= '<td class="align-middle" '.$style_cell.'>';
    if($opt['type']=='html')
    {
      $r .= isset($opt['html']) && strlen(isset($opt['html'])) ? $opt['html'] : '';
    }
    else
    if(($opt['type']=='text') || ($opt['type']=='colorpicker'))
    {
      $size  = isset($opt['size']) && strlen(isset($opt['size'])) ? 'size="'.$opt['size'].'"': '';
      $value = isset($opt['value']) && strlen(isset($opt['value'])) ? $opt['value'] : '';
      $r .= '
      <input class="form-control" '.($opt['type']=='colorpicker'?' class="colorpicker"':'').'type="text" name="'.$name.'" '.$style.' '.$size.' value="' . $value . '" />
      ';
      unset($value);
    }
    else
    if(($opt['type']=='lookup') && isset($opt['lookup']['table']) && isset($opt['lookup']['keyfield']) && !empty($opt['lookup']['displayfield']))
    {
      $table   = $opt['lookup']['table'];
      $key     = $opt['lookup']['keyfield'];
      $display = $opt['lookup']['displayfield'];
      $value   = isset($opt['value']) && strlen(isset($opt['value'])) ? $opt['value'] : '';
      if(in_array($table, $DB->table_names_arr[$DB->database]))
      {
        if($getrows = $DB->query('SELECT '.$key.', '.$display.' FROM '.PRGM_TABLE_PREFIX.$table.
                                 ' ORDER BY '.$display))
        {
          $r .= '
          <select class="form-control" id="'.$name.'" name="'.$name.'" '.$style.'>';
          while($row = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
          {
            $r .= '
            <option value="'.$row[$key].'"'.($value==$row[$key]?' selected="selected"':'').'>'.$row[$display].'</option>';
          }
          $r .= '
          </select>';
        }
      }
      unset($table,$key,$display,$value);
    }
    else
    if(($opt['type']=='select') && isset($opt['options']) && is_array($opt['options']))
    {
      $r .= '
      <select class="form-controL" id="'.$name.'" name="'.$name.'" '.$style.'>';
      $value = isset($opt['value']) && strlen(isset($opt['value'])) ? $opt['value'] : '';
      foreach($opt['options'] as $key => $display)
      {
        $r .= '
        <option value="'.$key.'"'.($value==$key?' selected="selected"':'').'>'.$display.'</option>';
      }
      $r .= '
      </select>';
    }
    $r .= '</td>';
  } //foreach
  $DB->ignore_error = false;
  $r .= '
  </tr>
  </tbody>
  </table>
  </div>
  ';
  if(!empty($form_title))
  {
   // $r .= EndSection(false);
  }
  if(!isset($form_endtag) || ($form_endtag==true))
  $r .=  '
  </form>
  ';
  return $r;
} //SearchBarOutput
// ############################################################################
// LOAD ADMIN PHRASES
// ############################################################################
function LoadAdminPhrases($admin_page_id=0, $pluginid=0, $plugin_only=false)
{
  //SD343, 2012-03-25: added 3rd paramt "$plugin_only" to ONLY select the
  // phrases for the plugin with specified id
  global $DB;
  $admin_phrases_arr = array();
  $GLOBALS['sd_ignore_watchdog'] = true;
  $DB->ignore_error = true; //SD342: ignore errors if not upgraded yet
  if(!empty($admin_page_id))
  {
    if(empty($plugin_only)) //SD343
      $get_admin_phrases = $DB->query('SELECT DISTINCT varname, defaultphrase, customphrase'.
                                      ' FROM '.PRGM_TABLE_PREFIX.'adminphrases'.
                                      ' WHERE (adminpageid in(0,%d) AND pluginid = 0)'.
                                      ' OR (adminpageid = 2 AND pluginid = %d)'.
                                      ' ORDER BY varname',
                                       $admin_page_id, $pluginid);
    else
      $get_admin_phrases = $DB->query('SELECT varname, defaultphrase, customphrase'.
                                      ' FROM '.PRGM_TABLE_PREFIX.'adminphrases'.
                                      ' WHERE (adminpageid = %d) AND (pluginid = %d)'.
                                      ' ORDER BY varname',
                                       $admin_page_id, $pluginid);
  }
  else
  {
    // ex: LoadAdminPhrases() (no arguments), load only common phrases
    // for example this is used when displaying the admin login
    if(empty($plugin_only)) //SD343
      $get_admin_phrases = $DB->query('SELECT varname, defaultphrase, customphrase'.
                                      ' FROM '.PRGM_TABLE_PREFIX.'adminphrases'.
                                      ' WHERE adminpageid = 0'.
                                      ' ORDER BY VARNAME'); // SD313: added order by
    else
      $get_admin_phrases = $DB->query('SELECT varname, defaultphrase, customphrase'.
                                      ' FROM '.PRGM_TABLE_PREFIX.'adminphrases'.
                                      ' WHERE pluginid = %d'.
                                      ' ORDER BY VARNAME',
                                      $pluginid); // SD313: added order by
  }
  if(!$DB->errno)
  while($admin_phrase_arr = $DB->fetch_array($get_admin_phrases,null,MYSQL_ASSOC))
  {
    if(isset($admin_phrase_arr['customphrase']) && strlen($admin_phrase_arr['customphrase']))
    {
      $admin_phrases_arr[$admin_phrase_arr['varname']] = $admin_phrase_arr['customphrase'];
    }
    else
    {
      $admin_phrases_arr[$admin_phrase_arr['varname']] = $admin_phrase_arr['defaultphrase'];
    }
  }
  $DB->ignore_error = false;
  $GLOBALS['sd_ignore_watchdog'] = false;
  return $admin_phrases_arr;
} //LoadAdminPhrases
// ############################################################################
// ADMIN PHRASE
// ############################################################################
function AdminPhrase($varname = '', $falseIfNotExists=false)
{
  global $admin_phrases;
  if(!isset($admin_phrases[$varname]))
  {
    $admin_phrases[$varname] = $admin_phrases['undefined_phrase'].' '.$varname;
    return $falseIfNotExists ? false : $admin_phrases[$varname];
  }
  return $admin_phrases[$varname];
}
// ############################################################################
// RETURN HTML SELECT ELEMENT WITH ALL PAGES
// ############################################################################
function GetPageSelection($elem_name='categoryparentids[]',$selected=0,$parentselection='',$parentid=0,$sublevelmarker='')
{
  global $DB;
  // start selection box
  if($parentid != 0)
  {
    $sublevelmarker .= '- - ';
  }
  $getcategories = $DB->query('SELECT categoryid, parentid, name FROM {categories}
                               WHERE parentid = %d ORDER BY displayorder',$parentid);
  while($category = $DB->fetch_array($getcategories,null,MYSQL_ASSOC))
  {
    $parentselection .= '<option '.($selected==$category['categoryid']?'selected="selected" ':'').
                        'value="'.$category['categoryid'].'">'.
                        $sublevelmarker . $category['name'] . '</option>';
    $parentselection = GetPageSelection($elem_name,$selected,$parentselection,$category['categoryid'],$sublevelmarker);
  }
  // end the selection box
  if($parentid == 0)
  {
    return '<select id="'.str_replace(array('[',']'),'',$elem_name).'" name="'.$elem_name.'" style="min-with: 80px; width: 95%; font-size: 12px;"><option value="0">-</option>' .
           $parentselection . '</select>';
  }
  else
  {
    return $parentselection;
  }
} //GetPageSelection
// ############################################################################
// RETURN HTML SELECT ELEMENT WITH ALL PLUGINS (FOR EDITING PAGE)
// ############################################################################
function GetPluginsSelect($selectedPluginId='1', $usergroupid=0)
{
  // SD342: If $usergroupid is 0/false/null, then ALL entries are included,
  // otherwise only those available for that specific usergroup!
  global $DB, $core_pluginids_arr, $plugin_names, $sdlanguage, $userinfo;
  $option0_style = '';#'font-weight: bold; background-color:#B0B0B0';
  // CREATE MAIN PLUGINS SELECTION
  $core_pluginids = implode(',',$core_pluginids_arr);
  if($forum_id = GetPluginID('Forum'))
  {
    $core_pluginids .= ','.$forum_id;
  }
  $p_ids = $core_pluginids_arr; // in init.php!
  if($forum_id)
  {
    array_push($p_ids, (int)$forum_id);
  }
  $p_arr = array();
  foreach($p_ids as $pid)
  {
    if ($pid > 1) $p_arr[$plugin_names[$pid]] = $pid;
  }
  ksort($p_arr);
  //SD370: correct insertion of "optgroup" (with labels) instead of dummy entries
  $plugin_selection  = '<option value="1"'.($selectedPluginId=='1'?' selected="selected"':'').'>&nbsp;</option>';
  $plugin_selection .= '<optgroup label="'.AdminPhrase('common_main_plugins').'">';
  foreach($p_arr as $pname => $pid)
  {
    if(!empty($userinfo['adminaccess']) || empty($usergroupid) || ((substr($pid,0,1)!=='c') && !empty($userinfo['pluginviewids']) && in_array($pid, $userinfo['pluginviewids'])))
    {
      $plugin_selection .= '<option value="'.$pid.'"'.($selectedPluginId==$pid?' selected="selected"':'').'>'.$pname.'</option>';
    }
  }
  // ADD CUSTOM/DOWNLOADED/CLONED PLUGINS SELECTIONS
  $p_config =
  array(
    AdminPhrase('common_custom_plugins') => array('id' => '-2',
      'sql' => 'SELECT custompluginid pid, name FROM {customplugins} ORDER BY name',
      'prefix' => 'c'
    ),
    AdminPhrase('common_downloaded_plugins') => array('id' => '-3',
      'sql' => "SELECT pluginid pid, name FROM {plugins} WHERE (authorname != 'subdreamer_cloner') AND NOT (pluginid IN ($core_pluginids)) ORDER BY name",
      'prefix' => ''
    ),
    AdminPhrase('common_cloned_plugins') => array('id' => '-4',
      'sql' => "SELECT pluginid pid, name FROM {plugins} WHERE authorname = 'subdreamer_cloner' ORDER BY name",
      'prefix' => ''
    ),
  );
  foreach($p_config as $title => $entry)
  {
    if($get_plugins = $DB->query($entry['sql']))
    {
      if($DB->get_num_rows($get_plugins))
      {
        $p_arr = array();
        while($plugin_arr = $DB->fetch_array($get_plugins,null,MYSQL_ASSOC))
        {
          if(isset($plugin_names[$plugin_arr['name']]))
          {
            $plugin_arr['name'] = $plugin_names[$plugin_arr['name']];
          }
          $p_arr[$entry['prefix'].$plugin_arr['pid']] = $plugin_arr['name'];
        }
        if(count($p_arr))
        {
          natcasesort($p_arr);
          $section_title = '</optgroup><optgroup label="'.$title.'">';
          foreach($p_arr as $pid => $pname)
          {
            if(!empty($userinfo['adminaccess']) || empty($usergroupid) ||
               ((substr($pid,0,1)=='c') && !empty($userinfo['custompluginviewids']) && in_array(substr($pid,1), $userinfo['custompluginviewids'])) ||
               ((substr($pid,0,1)!='c') && !empty($userinfo['pluginviewids']) && in_array($pid, $userinfo['pluginviewids']))
               )
            {
              if(strlen($section_title))
              {
                $plugin_selection .= $section_title;
                $section_title = '';
              }
              $plugin_selection .= '<option value="'.$pid.'"'.($selectedPluginId==$pid?' selected="selected"':'').'>'.$pname.'</option>';
            }
          }
        }
      }
    }
  }
  $plugin_selection .= '</optgroup></select>';
  return $plugin_selection;
} //GetPluginsSelect
// ############################################################################
// DISPLAY PLUGINS AS MENU (<ul> LIST)
// ############################################################################
// SD313 2010-09-01 - new function to display plugins for menu based on
//                    core function "DisplayPlugins" from "plugins.php"
//                    with exception of cloned plugins
function DisplayPluginsMenu($showpluginssubmenu = false, array $sub_menu_arr = null)
{
  global $DB, $core_pluginids_arr, $load_wysiwyg, $mainsettings_enablewysiwyg,
         $plugin_names, $sdlanguage, $userinfo, $current_version;
  /*
  For the plugins menu to display submenus at all, the user must be either...
  - Full Admin OR usergroup has access to Plugins page (i.e. "page admin" permission)
  AND
  - has "pluginadminids" or "pluginmoderateids" set for either the
    Main/Downloaded/Cloned Plugins submenu
    OR
  - has "custompluginadminids" set for the Custom Plugins submenu
  */
  $IsAdmin = !empty($userinfo['adminaccess']);
  $wysiwyg_suffix = ($mainsettings_enablewysiwyg ? '&amp;load_wysiwyg=1' : '');
  // Get Main Plugins
  $core_pluginids = implode(',',$core_pluginids_arr);
  if($forum_id = GetPluginID('Forum'))
  {
    $core_pluginids .= ','.$forum_id;
  }
  if(!empty($sub_menu_arr))
  {
    echo '
            <li class="plugins-options">
              <div class="plugins-list-header">'.AdminPhrase('menu_plugins_options').'</div>
              <ul>';
    foreach($sub_menu_arr as $title => $select)
    {
      echo '
                <li><a href="' . $select . '">' . $title . '</a></li>';
    }
    echo '
              </ul>
            </li>
            ';
  }
  $hasAccess = // Has access to "Plugins" page at all?
               ($IsAdmin || (!empty($userinfo['admin_pages']) && @in_array('plugins', $userinfo['admin_pages']))) &&
               // Admin for any custom plugin or downloaded plugin?
               ( !empty($userinfo['custompluginadminids']) ||
                 !empty($userinfo['pluginadminids']) ||
                 !empty($userinfo['pluginmoderateids']) );
  if(!$hasAccess)
  {
    return;
  }
  //SD360: check for "base_plugin" existing
  $p_extra = '';
  if(defined('SD_342') || $DB->column_exists(PRGM_TABLE_PREFIX.'plugins','base_plugin'))
  {
    $p_extra = ', base_plugin';
  }
  // ADD CUSTOM/DOWNLOADED/CLONED PLUGINS SELECTIONS
  $p_config = array(
    AdminPhrase('common_main_plugins') => "SELECT pluginid pid, name".$p_extra." FROM {plugins} WHERE (pluginid > 1) AND (pluginid IN ($core_pluginids)) ORDER BY name",
    AdminPhrase('common_custom_plugins') => 'SELECT custompluginid pid, name FROM {customplugins} ORDER BY name',
    AdminPhrase('common_downloaded_plugins') => "SELECT pluginid pid, name".$p_extra." FROM {plugins} WHERE (pluginid > 1) AND (authorname != 'subdreamer_cloner') AND NOT (pluginid IN ($core_pluginids)) ORDER BY name",
    AdminPhrase('common_cloned_plugins') => "SELECT pluginid pid, name".$p_extra." FROM {plugins} WHERE authorname = 'subdreamer_cloner' ORDER BY name"
  );
  $p_class = array('','','','');
  $idx = 0;
  foreach($p_config as $title => $select)
  {
    $get_plugins = $DB->query($select);
    if($count = $DB->get_num_rows($get_plugins))
    {
      $content = '';
      $p_arr = array();
      $p_bases = array();
      // Fetch plugin entries and check permissions
      while($plugin_arr = $DB->fetch_array($get_plugins,null,MYSQL_ASSOC))
      {
        $p_id = (int)$plugin_arr['pid'];
        if(!empty($plugin_arr['base_plugin']))
        {
          $p_bases[$p_id] = $plugin_arr['base_plugin'];
        }
        if($idx == 1) // Custom Plugins?
        {
          if($IsAdmin || !empty($userinfo['maintain_customplugins']) ||
             (!empty($userinfo['custompluginadminids']) && @in_array($p_id,$userinfo['custompluginadminids'])) )
          {
            $p_arr[$p_id] = strip_alltags($plugin_arr['name']);
          }
        }
        else
        {
          if($IsAdmin || (!empty($userinfo['pluginadminids']) && @in_array($p_id,$userinfo['pluginadminids'])) )
          {
            $p_name = isset($plugin_names[$p_id]) ?
                        $plugin_names[$p_id] :
                        $plugin_arr['name'];
            $p_arr[$p_id] = strip_alltags($p_name);
          }
        }
      } //while
      if($menuitemscount = count($p_arr))
      {
        natcasesort($p_arr);
        $p_count = $total = 0;
        $action = GetVar('action','','string');
        foreach($p_arr as $p_id => $p_name)
        {
          $ws = $wysiwyg_suffix;
          if($idx == 1)
          {
            $p_link = 'plugins.php?action=display_custom_plugin_form&amp;custompluginid='.$p_id.$ws;
          }
          else
          {
            if(($p_id == 2) || (isset($p_bases[$p_id]) && ($p_bases[$p_id]=='Articles')))
            {
              //SD370: omit pluginid param from URL for core Articles plugin (p2)
              $p_link = 'articles.php'.($p_id > 2 ? '?pluginid=' . $p_id : '');
              if($ws)
              {
                if(empty($action) || ($action=='displayarticles'))
                {
                  $ws = '';
                }
                if($p_id == 2) $ws = str_replace('&amp;','?',$ws);
              }
            }
            else
            {
              $p_link = 'view_plugin.php?pluginid='.$p_id;
            }
          }
          $content .= '
            <li><a href="'.$p_link.($idx == 0? $ws : '').'">'.
            $p_name.'</a></li>';
          $p_count++;
          $total++;
          if($p_count > 13)
          {
            $p_class[$idx] = ' class="scroll"';
          }
        } //foreach
        echo '<li class="plugins-list">
          <div class="plugins-list-header">'.$title.' ('.count($p_arr).')</div>';
        echo ($p_count > 0 ? "\r            <ul".$p_class[$idx].'>' : '');
        echo $content . ($p_count > 0 ? "\r            </ul>" : ''). '</li>';
      }
    }
    $idx++;
  } //foreach
} //DisplayPluginsMenu
if(!function_exists('ForumLink'))
{
function ForumLink($linkType, $userid = -1)
{
// Returns the relevent forum link url
// linkType
// 1 - Register
// 2 - UserCP
// 3 - Recover Password
// 4 - Private "Profile" page or public "Member" page (requires $userid)
// 5 - SendPM (requires $userid)
  global $DB, $dbname, $sdurl, $mainsettings, $mainsettings_modrewrite,
         $mainsettings_user_profile_page_id, $userinfo;
  if(empty($linkType) || ($linkType<1)) return '#'; //SD342
  $url = '';
  $prevDB = $DB->database;
  if($DB->database != $dbname) $DB->select_db($dbname);
  switch($linkType)
  {
    case 1:
    case 3:
      $DB->result_type = MYSQL_ASSOC;
      if($getregpath = $DB->query_first("SELECT p.categoryid FROM {pagesort} p,
        {categories} c
        WHERE p.categoryid = c.categoryid AND p.pluginid = '12'
        AND (LENGTH(c.link)=0)
        ORDER BY p.categoryid LIMIT 1"))
      {
        if(!empty($getregpath[0]))
        {
          $url = RewriteLink('index.php?categoryid=' . (int)$getregpath[0]);
        }
      }
      $DB->result_type = MYSQL_BOTH;
      break;
    // TODO: for 2, 4 and 5 eventually use "slugs" table (instead of CP_PATH)
    // which should then contain pages for profile and members (SD343+)
    // to allow for http://site/members/1234 instead of http://site/members.html?member=1234
    case 2:
    case 4:
    case 5:
      if(!defined('UCP_BASIC') || !UCP_BASIC)
      {
        if(!defined('CP_PATH') || (CP_PATH==''))
        {
          $url = '#';
          break;
        }
        $sep = ($mainsettings_modrewrite ? '?' : '&amp;');
        if($linkType == 4)
        {
          if(!empty($userinfo['userid']) && ($userinfo['userid']==$userid))
          {
            $sep .= 'profile=';
          }
          else
          {
            $sep .= 'member=';
          }
        }
        else
        {
          $sep .= ($linkType==2 ? 'profile=' : 'do=createnewmessage&amp;profile=');
        }
        $url = CP_PATH . $sep . ($userid?(int)$userid:'');
      }
      break;
  }
  if($DB->database != $prevDB) $DB->select_db($prevDB);
  return $url;
}
} //DO NOT REMOVE


/**
* Displays the admin header bar
*
* SD322: added $title and $simplePage
* $title - if specified - will be displayed in the sub-menu row on the right
* $simplePage = true means to leave out the whole menu area
* 
* @param string $active_page
* @param array $admin_sub_menu_arr
* @param string $title
* @param bool $simplePage
*/
function DisplayAdminHeader($active_page = '', $admin_sub_menu_arr = array(), $title = '', $simplePage = false, $settings_box = '')
{
  global $DB, $action, $mainsettings, $userinfo, $usersystem, $admin_menu_arr,
         $load_wysiwyg, $plugin_names, $refreshpage, $sdlanguage, $sdurl, $sd_head;
		 
  if(empty($userinfo['loggedin']))
  {
    return;
  }
  
  if(defined('ADMIN_HEADER_DONE') && ADMIN_HEADER_DONE)
  {
    return;
  }
 
  define('ADMIN_HEADER_DONE', true);
  $admin_menu = '';
  include_once(ROOT_PATH.'includes/enablegzip.php');
  
   // Include the file specific for sub-menu definitions:
  require_once(ROOT_PATH.ADMIN_PATH.'/prgm_menu.php');
  $submenus = GetSubMenuEntries();
    
  if($userinfo['loggedin'])
  {
	  
	 // SD 400 - Display sub title
	 if(is_array($active_page))
	 {
		 $active_action = $active_page[1];
		 $active_page = $active_page[0];
	 }
	 
	 // Format sub title
	 $phr = '';
	 if(strstr($active_action, ' ') !== false)
	 {
		foreach(explode(' ', $active_action) as $key => $value)
		{
			$phr .=  AdminPhrase($value) . ' ';
		}
		$active_action = $phr;
	 }
	 else
	 {
		 $active_action = strlen($active_action) ? AdminPhrase($active_action) : '';
	 }
	 
	$authormode = empty($userinfo['adminaccess']) && !empty($userinfo['authormode']); //SD360
	
	//SD350: $flip_admin_pages for faster search
    $flip_admin_pages = array();
    
	if(!empty($userinfo['admin_pages']) && is_array($userinfo['admin_pages']))
    {
      $flip_admin_pages = array_flip($userinfo['admin_pages']);
    } 
	  // SD400: setup menu
	  foreach($admin_menu_arr as $page_name => $page_link_menu)
	  { 
		  $page_name_lower = strtolower($page_name);
		  
		  //SD360: try to not translate non-existant entries by 2nd param being true
		  // This allows for manually added menu entries via branding file.
		  // (avoiding the "undefined phrase")
		  if(!$menu_item_name = AdminPhrase('menu_'.$page_name_lower,true))
		  {
			$menu_item_name = $page_name;
		  }
		  
		  if(empty($menu_item_name))
		  {
			$menu_item_name = $page_name;
		  }
		  
		  if($userinfo['adminaccess'] ||
			 ( // ****** non-Articles page ******
			   ($page_name_lower != 'articles') &&
			   array_key_exists($page_name_lower, $flip_admin_pages) )
			 ||
			 ( // ****** "Data" page ******
			   ($page_name_lower == 'data') &&
			   ( array_key_exists('comments', $flip_admin_pages) ||
				 array_key_exists('reports', $flip_admin_pages) ||
				 array_key_exists('tags', $flip_admin_pages)) )
			 ||
			 ( // ****** "Articles" page ******
			   ($page_name_lower == 'articles') &&
			   ( (!empty($userinfo['pluginadminids']) && @in_array(2, $userinfo['pluginadminids'])) ||
				 (!empty($userinfo['pluginmoderateids']) && @in_array(2, $userinfo['pluginmoderateids'])) ||
				 ($authormode && array_key_exists('articles', $flip_admin_pages))) )
			 ||
			 ( // ****** "Plugins" page ******
			   ($page_name_lower == 'plugins') && array_key_exists('plugins', $flip_admin_pages) &&
			   (!empty($userinfo['pluginadminids']) || !empty($userinfo['custompluginadminids'])) )
			)
		  {
			  // Determine active menu
			  $active = str_replace('.php', '', $page_link_menu[0]) == strtolower($active_page) ? "class='active'" : "class=''";
			  
			  if(sizeof($submenus[$page_name]) > 0)
			  {
				$toggle = "class='dropdown-toggle'";
			  }
			  else
			  {
				  $toggle = '';
			  }
			  
			  
			  $admin_menu .= "<li $active>
									<a href='$page_link_menu[0]' $toggle>
										<i class='menu-icon fa $page_link_menu[1]'></i>
										<span class='menu-text'> $menu_item_name </span>
									";
								
			if(sizeof($submenus[$page_name]) == 0)
			{
				$admin_menu .= "</a><b class='arrow'></b></li>";
			}
			else
			{
				$admin_menu .= "<b class='arrow fa fa-angle-down'></b>
								</a>
				
								<ul class='submenu'>";
				
				 // Append all submenus
				foreach($submenus[$page_name] as $key => $link)
				{
					if(!is_array($link) || ($page_name == 'Articles'))	
            		{
						if(strlen($key))
              			{
							//if $sub_link is array it's probably the Articles Plugins
							if($page_name == 'Articles' && is_array($link))
							{
								if(count($submenus[$page_name]) > 1)
								{
						
								$admin_menu .= '<li $active>
											<a href="#" class="dropdown-toggle">
												<i class="menu-icon fa fa-caret-right"></i>
												' . $link['name'] .'
												<b class="arrow fa fa-angle-down"></b>
											</a>
											<b class="arrow"></b>
											<ul class="submenu">';
								}
								foreach($link['menu'] as $key2	=>	$link2)
								{
									if(!($authormode && (strpos($link2, 'settings') !== false)))
									{
										$admin_menu .="<li $active>
										<a href='$link2'>
											<i class='menu-icon fa fa-caret-right'></i>
											<span class='menu-text'>
												$key2
											</span>
											<b class='arrow'></b>
										</a>
										
										<b class='arrow'></b>
									</li>";
									}
								}
								
								if(count($submenus[$page_name]) > 1 )
								{
								$admin_menu .= '</ul></li>';
								}
							}
							else
							// do not display menu links with "settings" in it:
							if(!(($active_page == 'articles') && $authormode && (strpos($link, 'settings') !== false)))
							{
							  $admin_menu .="<li $active>
										<a href='$link'>
											<i class='menu-icon fa fa-caret-right'></i>
											<span class='menu-text'>
												$key
											</span>
											<b class='arrow'></b>
										</a>
										
										<b class='arrow'></b>
									</li>";
							}
						}
					}
					else
					{
					
						$admin_menu .="<li $active>
								<a href='$sub_link'>
									<i class='menu-icon fa fa-caret-right'></i>
									<span class='menu-text'>
										$sub_name 
									</span>
									<b class='arrow'></b>
								</a>
								
								<b class='arrow'></b>
							</li>";
					}
				}
				
				$admin_menu .= "</ul></li>";
			}
		}
	  }
  } // if($userinfo[loggedin])
  
  
  $active_page_org = $active_page;
  $active_page = strtolower($active_page);
  $title_dummy = $title;
 
  if(!$title && (!$title_dummy = AdminPhrase('menu_'.$active_page,true)))
  {
    $title_dummy = $title;
  }
  
  $title = (empty($title_dummy)?'':$title_dummy.' - ').strip_tags(AdminPhrase('common_admin_panel').' - '.PRGM_NAME);
  
  // Check for unread private messages
  if($userinfo['loggedin'])
  {
      if(!empty($userinfo['adminaccess']) || !empty($userinfo['custompluginadminids']) || !empty($userinfo['pluginadminids']) || !empty($userinfo['admin_pages']) )
      {
        //SD370: display new private messages (also changes in pminfo file)
        if( (($usersystem['name'] != 'Subdreamer') || !SDUserCache::IsBasicUCP()))
        {
          @require_once(ROOT_PATH.'plugins/p10_mi_loginpanel/pminfo.php');
       	  $pm = p10_DisplayPMs($usersystem);          
        }
      }
   }
   
     // ###################################
  // Initialize "ready"-JS code
  // ###################################
  // FOR LATER? Bootstrap submit buttons with btn classes
  //jQuery("form input[type=submit]").not("[class^=]").addClass("btn btn-primary");
  $lang = empty($mainsettings['lang_region']) ? 'en-GB' : $mainsettings['lang_region'];
  $js_start = '
<script type="text/javascript">
// <![CDATA[
$(document).ready(function() {
  var SyntaxRoot = "'.SD_JS_PATH.'syntax/";
  if($(".syntax").length) {
    $.getScript(SyntaxRoot + "jquery.syntax.min.js", function () {
      $.syntax({ root: SyntaxRoot, theme: "grey", replace: true, context: $(".bbcode_code") });
    });
  }';
  $js_end = '});
// ]]>
</script>';


  $js = '';
  $js_arr = array(); //SD370: for single sd_header_add() call
  // SD313 - check $load_wysiwyg against mainsettings
  if($load_wysiwyg)
  {
    switch($mainsettings['enablewysiwyg'])
    {
      case 1:
        defined('TINYMCE_NOCACHE') || define('TINYMCE_NOCACHE', false);
        $tinyCache = (is_writable(realpath(ROOT_PATH.'cache')) && !TINYMCE_NOCACHE);
        //SD322: TinyMCE uses it's own compressor - if not disabled - so do not use with minify!
        $js_arr = array(
          ROOT_PATH. 'includes/tiny_mce/tinymce.min.js',
          'javascript/tiny_init_default.js');
		  
		$js_other ='';
        break;
      case 2:
        $js_arr = array(
          'ckeditor/ckeditor.js',
          'ckeditor/adapters/jquery.js',
          'javascript/ckeditor_init.js');
        break;
    }
  }
  
  /*
  * SD322:
  * - "minify" support for JS files added (do not use it for CSS!)
  * - all css/js header output combined by "sd_header_add", not directly
  *   output with "echo"
  * - see: /includes/min/groupsConfig.php for list of pre-loaded JS!
  */
  @include_once(SD_INCLUDE_PATH.'min/groupsConfig.php');
  // *** DO NOT RE-/MOVE BELOW! ***
  // !!! this should never happen: !!!
  if(!isset($groups)) $groups = array('admin_all'=>array(),'css'=>array(),'css-min'=>array());
  if(!isset($groups['css'])) $groups['css'] = array();
 
  // Include both date- and time picker language files, if either exists
  if(file_exists(SD_JS_PATH.'datetime/jquery.datepick-'.$lang.'.js'))
  {
    $js_arr[] = SD_JS_PATH.'datetime/jquery.datepick-'.$lang.'.js';
  }
 
  if(file_exists(SD_JS_PATH.'datetime/jquery.timeentry-'.$lang.'.js'))
  {
    $js_arr[] = SD_JS_PATH.'datetime/jquery.timeentry-'.$lang.'.js';
  }
  
  if(file_exists(SD_JS_PATH . 'jquery.jdialog.js'))
  {
	 // $js_arr[] = SD_JS_PATH . 'jquery.jdialog.js';
  }
  
//  $js_arr[] = SITE_URL . ADMIN_STYLES_FOLDER . 'assets/js/chosen.jquery.min.js';
  
  
  // SD313 - display "Plugins" item with sub-menus for which a menu system
  // is required to be loaded:
  if(empty($simplePage))
  {
    $columns = 4;
    $DB->result_type = MYSQL_ASSOC;
    if($clones_exist = $DB->query_first('SELECT pluginid FROM {plugins}'.
                                        " WHERE authorname LIKE '%%subdreamer_cloner%%' LIMIT 1"))
    {
      $columns = 5;
    } 
  }
  
  /*
  //SD370: *** moved MOST CSS loading into "includes/css/css-admin.php"! ***
  $groups['css'][] = ADMIN_STYLES_FOLDER.'css/ceebox.admin.css';
  $groups['css'][] = ADMIN_STYLES_FOLDER.'css/plugin_submenu.css';
  $groups['css'][] = SD_CSS_PATH.'menuskins/black.css';
  $groups['css'][] = SD_CSS_PATH.'jquery.jdialog.css';
  $groups['css'][] = SD_CSS_PATH.'jquery.tag.editor.css';
  $groups['css'][] = SD_CSS_PATH.'jquery.autocomplete.css';
  $groups['css'][] = SD_CSS_PATH.'jquery.jgrowl.css';
  */
  // Required to link explicitly due to heavy use of images:
  $groups['css'][] = SITE_URL . ADMIN_STYLES_FOLDER.'assets/css/chosen.css'; 
  $groups['css'][] = SITE_URL . ADMIN_STYLES_FOLDER.'assets/css/ceebox.admin.css';
  $groups['css'][] = SD_JS_PATH.'markitup/skins/markitup/style.css';
  $groups['css'][] = SD_JS_PATH.'markitup/sets/bbcode/style.css';
  //SD370: include files for sidebar
  if(empty($_GET['cbox']) && SD_ADMIN_SIDEBAR_LOADED)
  {
    #$js_arr[] = SD_INCLUDE_PATH.'javascript/jquery.metadata.js';
    #$js_arr[] = SD_INCLUDE_PATH.'javascript/jquery.hoverIntent.min.js';
    $js_arr[] = SD_INCLUDE_PATH.'javascript/jquery.mb.flipText.js';
    $js_arr[] = SD_INCLUDE_PATH.'javascript/mbExtruder.js';

  }
  
  if(defined('ENABLE_MINIFY') && ENABLE_MINIFY )
  {
    // NEVER USE CSS FILES WITH MINIFY IF CSS USES IMAGES!!!
    sd_header_add(array(
      'css'   => $groups['css'],
      'js'    => @array_merge(array(SD_INCLUDE_PATH.'min/index.php?g=admin_all'), $js_arr),
      'other' => array($js_start . $js . $js_end . $js_other)
    ), true);
  }
  else
  {

    sd_header_add(array(
      'css'   => $groups['css'],
      'js'    => @array_merge(array($groups['admin_all']), $js_arr),
      'other' => array($js_start . $js . $js_end)
    ));
  }
	
 // Add settings to $sd_head for us
 $sd_head->AddSetting('title', $title);
 $sd_head->AddSetting('wysiwyg_disabled', $mainsettings["wysiwyg_starts_off"]);
  
// Start Output
 ?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset='<?=SD_CHARSET?>'" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<base href="<?=SITE_URL.ADMIN_PATH?>/" />
		<meta http-equiv="Content-Type" content="text/html;charset=<?=SD_CHARSET?>" />
        <title>
            <?=$title?>
        </title>

		<meta name="description" content="overview &amp; stats" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
        
   		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="<?=SITE_URL ?>includes/css/font-awesome.min.css" />

		<!-- page specific plugin styles -->

		<!-- text fonts -->
		<link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/ace-fonts.css" />

		<!-- ace styles -->
		<link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/ace.min.css" id="main-ace-style" />

		<!--[if lte IE 9]>
			<link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/ace-part2.min.css" />
		<![endif]-->
		<link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/ace-skins.min.css" />
		<link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/ace-rtl.min.css" />

		<!--[if lte IE 9]>
		  <link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/ace-ie.min.css" />
		<![endif]-->

		<!-- inline styles related to this page -->
        <?=$sd_head->PrintCSS();?>
        
		<?=$sd_head->PrintJavaScript();?>
		       
        <!-- Page Specific Inline JavaScript -->
		<?=$sd_head->PrintScript();?>
        
        
	</head>
   <body class="<?=ADMIN_SKIN?>">
   	<?php if(!$simplePage): ?>
		<!-- #section:basics/navbar.layout -->
		<div id="navbar" class="navbar navbar-default <?=(defined('NAVBAR_FIXED') && NAVBAR_FIXED ? 'navbar-fixed-top' : '')?>">
			<script type="text/javascript">
				try{ace.settings.check('navbar' , 'fixed')}catch(e){}
			</script>

			<div class="navbar-container" id="navbar-container">
				<!-- #section:basics/sidebar.mobile.toggle -->
				<button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler">
					<span class="sr-only">Toggle sidebar</span>

					<span class="icon-bar"></span>

					<span class="icon-bar"></span>

					<span class="icon-bar"></span>
				</button>

				<!-- /section:basics/sidebar.mobile.toggle -->
				<div class="navbar-header pull-left">
					<!-- #section:basics/navbar.layout.brand -->
					<a href="#" class="navbar-brand">
						<small>
							<?=PRGM_NAME?>
						</small>
					</a>

					<!-- /section:basics/navbar.layout.brand -->

					<!-- #section:basics/navbar.toggle -->

					<!-- /section:basics/navbar.toggle -->
				</div>

				<!-- #section:basics/navbar.dropdown -->
				<div class="navbar-buttons navbar-header pull-right" role="navigation">
					<ul class="nav ace-nav">
						

						<li class="green">
							<a href="<?=$pm['pmurl']?>">
								<i class="ace-icon fa fa-envelope <?=($pm['pmunread'] > 0 ? 'icon-animated-vertical' : '')?>"></i>
								<span class="badge badge-success"><?=($pm['pmunread'] > 0 ? $pm['pmunread'] : 0)?></span>
							</a>

							
						</li>

						<!-- #section:basics/navbar.user_menu -->
						<li class="light-blue">
							<a data-toggle="dropdown" href="#" class="dropdown-toggle">
								<img class="nav-user-photo" src="<?=GetAvatarPath($userinfo['email'], $userinfo['userid']);?>" alt="" />
								<span class="user-info">
                                <small><?=AdminPhrase('common_welcome');?></small>
									<?=$userinfo['username']?>
								</span>

								<i class="ace-icon fa fa-caret-down"></i>
							</a>

							<ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
								<li>
									<a href="<?=SITE_URL?>" Target="_blank">
										<i class="ace-icon fa fa-home"></i>
										<?=AdminPhrase('common_view_site');?>
									</a>
								</li>

								<li>
									<a href="?logout=1">
										<i class="ace-icon fa fa-power-off"></i>
										<?=AdminPhrase('common_logout');?>
									</a>
								</li>
							</ul>
						</li>

						<!-- /section:basics/navbar.user_menu -->
					</ul>
				</div>

				<!-- /section:basics/navbar.dropdown -->
			</div><!-- /.navbar-container -->
		</div>
        <!-- /section:basics/navbar.layout -->

		<div class="main-container" id="main-container">
			<script type="text/javascript">
				try{ace.settings.check('main-container' , 'fixed')}catch(e){}
			</script>

			<!-- #section:basics/sidebar -->
			<div id="sidebar" class="sidebar responsive <?=(defined('SIDEBAR_FIXED') && SIDEBAR_FIXED ? 'sidebar-fixed' : '')?>">
				<script type="text/javascript">
					try{ace.settings.check('sidebar' , 'fixed')}catch(e){}
				</script>
                
               
                <div class="sidebar-shortcuts" id="sidebar-shortcuts">
                	 <?php if($userinfo['adminaccess']):?>
					<div class="sidebar-shortcuts-large" id="sidebar-shortcuts-large">
						<a class="btn btn-success" href="pages.php?action=create_page" Title="<?=AdminPhrase('menu_pages_create_page');?>">
							<i class="ace-icon fa fa-file"></i>
						</a>

						<a class="btn btn-info" href="users.php?action=display_user_form<?=SD_URL_TOKEN?>" Title="<?=AdminPhrase('menu_users_add_user');?>">
							<i class="ace-icon fa fa-user"></i>
						</a>

						<!-- #section:basics/sidebar.layout.shortcuts -->
						<a class="btn btn-warning" href="usergroups.php?action=display_usergroup_form<?=SD_URL_TOKEN?>" Title="<?=AdminPhrase('menu_users_add_user_group');?>">
							<i class="ace-icon fa fa-users"></i>
						</a>

						<a class="btn btn-danger" href="settings.php" Title="Settings">
							<i class="ace-icon fa fa-cogs"></i>
						</a>

						<!-- /section:basics/sidebar.layout.shortcuts -->
					</div>
                    <?php endif; ?>

					<div class="sidebar-shortcuts-mini" id="sidebar-shortcuts-mini">
						<span class="btn btn-success"></span>

						<span class="btn btn-info"></span>

						<span class="btn btn-warning"></span>

						<span class="btn btn-danger"></span>
					</div>
				</div><!-- /.sidebar-shortcuts -->


                <ul class="nav nav-list">
					<?=$admin_menu?>
				</ul><!-- /.nav-list -->

				<!-- #section:basics/sidebar.layout.minimize -->
				<div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
					<i class="ace-icon fa fa-angle-double-left" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
				</div>

				<!-- /section:basics/sidebar.layout.minimize -->
				<script type="text/javascript">
					try{ace.settings.check('sidebar' , 'collapsed')}catch(e){}
				</script>
			</div>
            <!-- /section:basics/sidebar -->
	<?php endif; ?>
			
			<div class="main-content">
                <?php if(!$simplePage): ?>
                <!-- #section:basics/content.breadcrumbs -->
				<div class="breadcrumbs" id="breadcrumbs">
					<script type="text/javascript">
						try{ace.settings.check('breadcrumbs' , 'fixed')}catch(e){}
					</script>

					<ul class="breadcrumb">
						<li>
							<i class="ace-icon fa fa-home home-icon"></i>
							<a href="#"><?=AdminPhrase('home');?></a>
						</li>
						<li class="active"><?=ucfirst($active_page);?></li>
					</ul><!-- /.breadcrumb -->
                    
                    <!-- #section:basics/content.searchbox -->
					<div class="nav-search" id="nav-search">
						<!-- <form class="form-search">
							<span class="input-icon">
								<input type="text" placeholder="Search ..." class="nav-search-input" id="nav-search-input" autocomplete="off" />
								<i class="ace-icon fa fa-search nav-search-icon"></i>
							</span>
						</form>-->
					</div><!-- /.nav-search -->

					<!-- /section:basics/content.searchbox -->


          <!-- /section:basics/content.searchbox -->
				</div><!-- /section:basics/content.breadcrumbs -->
                <?php endif; ?>
				
        <div class="page-content">
        	<?=$settings_box?>
        	

			<div class="page-content-area">
						
           <?php if(!$simplePage): ?>
            <div class="page-header">
				<h1>
					<?=ucfirst($active_page);?>		
                    <?php if(strlen($active_action)): ?>
                    <small>
						<i class="ace-icon fa fa-angle-double-right"></i>
							<?=(strlen($active_action) ? $active_action : '');?>
					</small>
					<?php endif; ?>
				</h1>
			</div><!-- /.page-header -->
           <?php endif; ?>
            
            <div class="row">
              <div class="col-xs-12">			
<?php

  if(!empty($simplePage)) //SD322
  {
   // echo '<div class="right">
   // <div class="left">
   // ';
  }
  else
  {
    
  if($userinfo['loggedin'])
  {
    $pid = Is_Valid_Number(GetVar('pluginid', 0, 'whole_number'),0,2,99999999);
    if(empty($pid) && ($active_page == 'articles'))
    {
      $pid = 2;
    }
    //SD360: take into account plugin "base" (e.g. "Articles") to support cloned plugins
    $articles_plugin = (isset($plugin_names['base-'.$pid]) && ($plugin_names['base-'.$pid]=='Articles'));
    //SD322: check permission for current page and show sub-menu only if allowed
    if($userinfo['adminaccess'] ||
       ( ($active_page != 'articles') && !in_array($active_page, $userinfo['admin_pages']) )
       ||
       ( ($active_page == 'articles') &&
         ( $authormode ||
           (!empty($userinfo['pluginadminids']) && @in_array($pid, $userinfo['pluginadminids'])) ||
           (!empty($userinfo['pluginmoderateids']) && @in_array($pid, $userinfo['pluginmoderateids']))))
       ||
       ( ($active_page == 'plugins') &&
         @in_array('plugins', $userinfo['admin_pages']) &&
         (!empty($userinfo['pluginadminids']) ||
          !empty($userinfo['custompluginadminids'])) )
      )
    {
      if(isset($submenus[$active_page_org]) || !empty($admin_sub_menu_arr))
      {
        // For Articles plugins: add "golden key" icon for plugin permissions popup
        if(!$authormode && $articles_plugin &&
           ($active_page == 'articles') && isset($submenus[$active_page_org]))
        {
          //SD370: use "font-awesome" icon
          $perm = '<a href="plugins.php?action=display_plugin_permissions&amp;cbox=1&amp;pluginid='.$pid.
                  '" class="cbox pluginpermissions" rel="iframe modal:false height:420" '.
                  'title="'.htmlspecialchars(strip_tags(AdminPhrase('plugins_usergroup_permissions_for').
                  ' '.$plugin_names[$pid]),ENT_COMPAT).'"><i class="icon-key"></i></a>&nbsp;';
                  #<span class="sprite sprite-key"></span>
          //SD360: display link to CSS entry on Skins page:
          //SD370: fixed SQL: was empty if only default existed (skinid = 0)
          $DB->result_type = MYSQL_ASSOC;
          if($css_row = $DB->query_first('SELECT sc.skin_id, sc.skin_css_id, sc.var_name'.
                                       ' FROM '.PRGM_TABLE_PREFIX.'skin_css sc'.
                                       ' WHERE sc.plugin_id = '.(int)$pid.
                                       ' AND sc.skin_id IN (0, IFNULL((SELECT MAX(skinid) FROM {skins} WHERE activated = 1),0))'.
                                       ' ORDER BY sc.skin_id DESC, sc.skin_css_id'.
                                       ' LIMIT 0,1'))
          {
            $tmp = isset($plugin_names[$pid])?$plugin_names[$pid]:$plugin_names[2];
            $perm .= ' &nbsp;<a href="skins.php?skinid='.$css_row['skin_id'].
                     '&amp;structure=css&amp;css_var_name='.urlencode($tmp).
                     '&amp;pluginid='.$pid.
                     '" target="_blank" title="CSS"><i class="icon-external-link icon-1-2x"></i></a>';
          }
          //SD370: display link to templates page on Skins page:
          $DB->result_type = MYSQL_ASSOC;
          if($entry = $DB->query_first('SELECT 1 FROM '.PRGM_TABLE_PREFIX.'templates t'.
                                       ' WHERE t.pluginid = '.(int)$pid.
                                       ' LIMIT 1') )
          {
            $perm .= ' &nbsp;&nbsp;<a href="templates.php?action=display_templates'.
                     '&amp;customsearch=1&amp;searchpluginid='.$pid.
                     '" target="_blank" title="'.AdminPhrase('menu_templates').
                     '"><i class="icon-tasks icon-1-2x"></i></a>';
          }
          $submenus[$active_page_org][$pid]['menu'][] = $perm;
        }
        if(!empty($admin_sub_menu_arr) && is_array($admin_sub_menu_arr))
        {
          if(isset($submenus[$active_page_org]))
            $submenus[$active_page_org] = @array_merge((array)$submenus[$active_page_org],$admin_sub_menu_arr);
          else
            $submenus[$active_page_org] = (array)$admin_sub_menu_arr;
        }
        foreach($submenus[$active_page_org] as $sub_page_name => $sub_page_link)
        {
          if($sub_page_link=='#') continue; //SD350
          //SD341: IF the menu item is an article plugin sub-menu (items pluginid, name, menu):
          if(($active_page_org == 'Articles') && is_array($sub_page_link))
          {
            if(isset($sub_page_link['pluginid']) && ($sub_page_link['pluginid'] == $pid))
            {
          
              foreach($sub_page_link['menu'] as $key2 => $link2)
              {
                $hasLink = (substr($link2,0,7) == '<a href');
                if($hasLink)
                {
               
                }
                else
                {
            
                }
              }
            }
            continue;
          }
          $hasLink = (substr($sub_page_name,0,3) == '<a ');
          if(!$hasLink && (($sub_page_link=='#') || empty($sub_page_link)) ) //SD322: empty entry points to current page
          {
            $sub_page_link = $refreshpage;
          }
          if(($active_page == 'articles') && $authormode &&
             (strpos($sub_page_link, 'settings') !== false))
          {
            // This avoids settings links in author mode
          }
          else
          {
       
            if(!empty($sub_page_link))
            {
        
            }
      
            if(!empty($sub_page_link))
            {
       
            }
       
          }
        } //foreach
      }
    }

  } // if loggedin
  } // END OF "!empty(simplePage)"

  if(empty($simplePage)) //SD322
  {
    $messages = array();
    if(file_exists('../setup'))
    {
      $messages[] = 'For safety measures please <strong>delete</strong> or <strong>password protect</strong> the ' . PRGM_NAME . ' \'<strong>setup</strong>\' directory via FTP.';
    }
    if(file_exists('../install'))
    {
      $messages[] = '<center>For safety measures please <strong>delete</strong> or <strong>password</strong> protect the ' . PRGM_NAME . ' \'<strong>install</strong>\' directory via FTP.</span>';
    }
    if(!is_dir(ROOT_PATH.'cache') && !is_writable(ROOT_PATH.'cache'))
    {
      $messages[] = 'The "cache" folder does not exist or is not writable, please set permissions to 777 via FTP.';
    }
    if(count($messages))
    {
      DisplayMessage($messages, true);
    }
  }
} //DisplayAdminHeader


// ############################################################################
// DISPLAY ADMIN FOOTER
// ############################################################################
function DisplayAdminFooter($noCopyright=false)
{
  global $mainsettings, $userinfo, $sd_head;
 
  if($userinfo['loggedin'])
  {
echo '
<noscript>
<div class="alert alert-danger">
 	Sorry, the '.PRGM_NAME.' Admin interface only fully works on a JavaScript enabled browser.  Please either <a target="_blank" href="http://www.google.com/search?hl=en&amp;q=how+to+enable+javascript">enable JavaScript</a> on your browser or
  <a href="http://www.google.com/search?hl=en&amp;safe=off&amp;q=download+browser" target="_blank">download</a> a browser that allows you to run JavaScript.
 </div>
</noscript>
';
  }
  
 ?>

 				</div> <!-- /. col-xs-12 -->
 			</div> <!-- /.row -->
	 </div><!-- /.page-content-area -->
   </div><!-- / . page-content -->
</div><!-- /.main-content -->
 <div class="footer">
    <div class="footer-inner">
        <!-- #section:basics/footer -->
        <div class="footer-content">
            <span class="bigger-105">
 <?php
 if(empty($noCopyright))
  {
    @include(SD_INCLUDE_PATH.'build.php');
	
	echo str_replace(' ','&nbsp;',AdminPhrase('common_powered_by') . ' <strong>' . PRGM_NAME . ' (v' . SD_BUILD . ')</strong>');
			
	if(PRGM_NAME == 'Subdreamer CMS')
	{
		echo "<br><a href='http://antiref.com/?http://subdreamer.com/' target='_blank' title='Subdreamer'> Copyright &copy; 2003 - ". date('Y', time()) ." - Subdreamer</a>";
	}
	
	
  }
  ?>
        </span>
    </div>
    <!-- /section:basics/footer -->
	</div>
 </div>
 <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
				<i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
			</a>

</div><!-- /.main-container -->
</body>
</html>
<?php
 
		
  

} //DisplayAdminFooter

/**
* Outputs included CSS files line by line
*/
function PrintIncludedCSS($page, $incl)
{
	if(!is_array($incl))
	{
		return;
	}
	
	foreach($incl[$page] as $value)
	{
		echo "\t" . '<link rel="stylesheet" type="text/css" href="' . SITE_URL . ADMIN_STYLES_FOLDER . $value . '" />' . "\n";
	}
}

/**
* Outputs included CSS files line by line
*/
function PrintIncludedJS($page, $incl)
{
	if(!is_array($incl))
	{
		return;
	}
	
	foreach($incl[$page] as $value)
	{
		echo '<script src="' . SITE_URL . ADMIN_STYLES_FOLDER . $value . '"></script>' . "\n";
	}
	
	// output all extra javascript
	if(is_array($incl['other']))
	{
		foreach ($incl['other'] as $inlinejs)
		{
			echo $inlinejs . "\n";
		}
	}
}


// ############################################################################
function GetPluginListForPage($category_id = 0, $ResultSetOnly=true, $applyCaption=true, $mobile=false)
{
  global $DB, $plugin_names, $sdlanguage;
  //SD370: added $mobile param; use mobile pagesort table when indicated
  $pagesort = PRGM_TABLE_PREFIX.'pagesort';
  if(!empty($mobile) && SD_MOBILE_FEATURES)
  {
    $pagesort .= '_mobile';
  }
  //SD342: sort custom plugins by name
  $sql = 'SELECT DISTINCT ps.pluginid,
    IF(c.custompluginid is not null, 0, 1) isplugin,
    IF(c.custompluginid is not null, c.custompluginid, ps.pluginid) realpluginid,
    IF(c.custompluginid is not null, c.name, p.name) displayname
    FROM '.$pagesort.' ps
    LEFT JOIN '.PRGM_TABLE_PREFIX.'plugins p ON p.pluginid = ps.pluginid
    LEFT JOIN '.PRGM_TABLE_PREFIX.'customplugins c ON c.custompluginid = substr(ps.pluginid,2,6) AND substr(ps.pluginid,1,1) = \'c\'
    WHERE ps.pluginid <> \'1\' '.
    (empty($category_id)?'':' AND ps.categoryid = %d ').'
    ORDER BY isplugin DESC, IF(c.custompluginid is not null, c.name, p.name)';
  $result_set = $DB->query($sql, $category_id);
  if(!empty($ResultSetOnly))
  {
    return $result_set;
  }
  // Prepare HTML output of list
  $output = '';
  while($plugin_arr = $DB->fetch_array($result_set,null,MYSQL_ASSOC))
  {
    $output .=
    ($plugin_arr['isplugin'] && isset($plugin_names[$plugin_arr['pluginid']]) ?
       htmlspecialchars($plugin_names[$plugin_arr['pluginid']],ENT_COMPAT,SD_CHARSET) :
       htmlspecialchars($plugin_arr['displayname'],ENT_COMPAT,SD_CHARSET)).
    ' ('.(substr($plugin_arr['pluginid'],0,1)!='c'?'P':'').$plugin_arr['pluginid'].')<br />';
  }
  if($applyCaption)
  {
    if(strlen($output))
    {
      $output = AdminPhrase('pages_page_has_plugins') . $output;
    }
    else
    {
      $output = AdminPhrase('pages_page_no_plugins');
    }
  }
  return $output;
} //GetPluginListForPage
// ############################################################################
// ############################ DISPLAY ADMIN FORM ############################
// ############################################################################
function DisplayAdminForm($config)
{
/*
  // EXAMPLE CODE:
  $base_action = REFRESH_PAGE.'&amp;action=';
  $sections = array(
    AdminPhrase('images') => array(
        AdminPhrase('add_image')      => array('hint'   => AdminPhrase('add_image_hint'),
                                               'action' => $base_action.'displayimageform'),
        AdminPhrase('import_images')  => array('hint'   => AdminPhrase('import_images_hint'),
                                               'action' => $base_action.'displaybatchimportform')
    ),
    AdminPhrase('sections') => array(
        AdminPhrase('create_section') => array('hint'   => AdminPhrase('create_section_descr'),
                                               'action' => $base_action.'displaysectionform'),
        AdminPhrase('edit_section')   => array('hint'   => AdminPhrase('edit_section_desc'),
                                               'action' => $base_action.'displaysectionform',
                                               'call'   => array('func'  => 'PrintSectionSelection',
                                                                 'param' => 'sectionid'))
    )
  );
  DisplayAdminForm($sections);
*/
  if(!isset($config) || !is_array($config) || empty($config))
  {
    return false;
  }
  $output = false;
  foreach($config as $section => $section_details)
  {
    StartSection($section);
    foreach($section_details as $entry => $sub_config)
    {
      $output = true;
      echo '<table width="100%" border="0" cellpadding="5" cellspacing="0">';
      echo '
      <tr>
        <td class="tdrow1" colspan="2" valign="top">'.$entry.'</td>
      </tr>
      <tr>
        <td class="td2" width="70%" valign="top">'.$sub_config['hint'].'</td>
        <td style="padding-left: 20px;" valign="top">
          <form method="post" action="'.$sub_config['action'].'">
          ';
      if(isset($sub_config['call']) && isset($sub_config['call']['func']))
      {
        $func = $sub_config['call']['func'];
        if(is_callable($func))
        {
          $func(isset($sub_config['call']['param']) ? $sub_config['call']['param'] : null);
        }
      }
      echo '
          <input type="submit" value="'.strip_tags($entry).'" />
          </form>
        </td>
      </tr>
      <tr>';
      echo '</table>';
    }
    EndSection();
  }
  if($output)
  {
    echo '
<script type="text/javascript">
// <![CDATA[
jQuery(document).ready(function()
{
  jQuery("form").submit(function() {
    var link = jQuery(this).attr("action");
    var select = jQuery(this).find("select");
    if(select.length !== 0) {
      link = link + "&amp;" + select.attr("name") + "=" + select.val();
    }
    window.location = link;
    return false;
  });
})
// ]]>
</script>
';
  }
} //DisplayAdminForm
// ############################################################################
// REDIRECT PAGE
// ############################################################################
// used for the admin panel to display a message then redirect to a new page
// usually when settings are updated etc
// SD313: new parameter "$IsError"
function RedirectPage($new_page, $message = 'Settings Updated', $delay_in_seconds = 2, $IsError=false)
{
  $message .= '<br /><br /><a class="btn btn-sm btn-info" href="' . $new_page . '" onclick="javascript:if(typeof sd_timerID !== \'undefined\') clearTimeout(sd_timerID);">' .
              AdminPhrase('common_click_to_redirect') . '</a>';
  DisplayMessage($message, $IsError);
  AddTimeoutJS($delay_in_seconds, $new_page);
} //RedirectPage
// ############################################################################
// DISPLAY ADMIN LOGIN
// ############################################################################
function DisplayAdminLogin()
{
  global $login_errors_arr, $mainsettings;
  $admin_phrases = LoadAdminPhrases();
  
  
  if(!empty($login_errors_arr))
  {
    $login_msg = "<br /><div class='alert alert-danger'>";

    if(is_string($login_errors_arr))
    {
      $login_msg .= $login_errors_arr . '<br />';
    }
    elseif(is_array($login_errors_arr))
	{
    	foreach($login_errors_arr AS $key => $value)
    	{
      		$login_msg .= $value . '<br />';
    	}
	}
    $login_msg .= '</div>';
  }
  
  if(substr($_SERVER['QUERY_STRING'], 0, 25) == 'action=displayarticleform')
  {
    $action_page = 'articles.php?articleaction=displayarticleform';
    $pageid = GetVar('pageid', 0, 'whole_number');
    if($mainsettings['enablewysiwyg'])
    {
      $action_page .= '&amp;load_wysiwyg=1';
    }
    if($pageid)
    {
      $action_page .= '&amp;pageid=' . $pageid;
    }
  }
  else
  {
    // SD313: use branding' default page if set
    $action_page = defined('ADMIN_DEFAULT_PAGE') ? ADMIN_DEFAULT_PAGE : 'pages.php';
  }
  
  ?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title>
			<?=PRGM_NAME?>  -  <?=$admin_phrases['common_admin_panel']?>
		</title>

		<meta name="description" content="User login page" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="<?=SITE_URL ?>includes/css/font-awesome.min.css" />

		<!-- text fonts -->
		<link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/ace-fonts.css" />

		<!-- ace styles -->
		<link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/ace.min.css" />

		<!--[if lte IE 9]>
			<link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/ace-part2.min.css" />
		<![endif]-->
		<link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/ace-rtl.min.css" />

		<!--[if lte IE 9]>
		  <link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/ace-ie.min.css" />
		<![endif]-->
		<link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/ace.onpage-help.css" />

		<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->

		<!--[if lt IE 9]>
		<script src="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/js/html5shiv.js"></script>
		<script src="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/js/respond.min.js"></script>
		<![endif]-->
	</head>

	<body class="login-layout light-login">
		<div class="main-container">
			<div class="main-content">
				<div class="row">
					<div class="col-sm-20 col-sm-offset-1">
						<div class="login-container">
							<div class="center">
                             <?=$login_msg?>
								<h1 class="lighter">
									<img src="styles/<?=ADMIN_STYLE_FOLDER_NAME?>/assets/images/<?=ADMIN_LOGIN_LOGO_IMG?>" />
								</h1>
								
							</div>

							<div class="space-6"></div>

							<div class="position-relative">
								<div id="login-box" class="login-box visible widget-box no-border">
									<div class="widget-body">
										<div class="widget-main">
											<h4 class="header blue lighter bigger">
												<i class="ace-icon fa fa-key green "></i>
												<?=AdminPhrase('login_enter_your_information');?>
											</h4>

											<div class="space-6"></div>

											<form action="<?=$action_page?>" method="post" id="adminlogin" >
                                            <?=PrintSecureToken();?>
                                                <input type="hidden" name="login" value="login" />
												<fieldset>
													<label class="block clearfix">
														<span class="block input-icon input-icon-right">
															<input id="username" type="text" class="form-control" placeholder="<?=AdminPhrase('common_username');?>" name="loginusername" />
															<i class="ace-icon fa fa-user"></i>
														</span>
													</label>

													<label class="block clearfix">
														<span class="block input-icon input-icon-right">
															<input id="password" type="password" class="form-control" placeholder="<?=AdminPhrase('common_password');?>"  name="loginpassword"/>
															<i class="ace-icon fa fa-lock"></i>
														</span>
													</label>

													<div class="space"></div>

													<div class="clearfix">
														

														<button type="submit" class="width-35 pull-right btn btn-sm btn-primary">
															<i class="ace-icon fa fa-key bigger-110"></i>
															<span class="bigger-110"><?=AdminPhrase('common_login');?></span>
														</button>
													</div>

													<div class="space-4"></div>
												</fieldset>
											</form>

											
										</div><!-- /.widget-main -->

										<div class="toolbar clearfix">
											<div>
												
											</div>

											<div>
												
											</div>
										</div>
									</div><!-- /.widget-body -->
								</div><!-- /.login-box -->
                                <h5 class="blue center" id="id-company-text"><?=$admin_phrases['common_powered_by']?>  <?=PRGM_NAME?></h5>

								
							</div><!-- /.position-relative -->

						</div>
					</div><!-- /.col -->
				</div><!-- /.row -->
			</div><!-- /.main-content -->
		</div><!-- /.main-container -->

		<!-- basic scripts -->

		<!--[if !IE]> -->
		<script type="text/javascript">
			window.jQuery || document.write("<script src='<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/js/jquery.min.js'>"+"<"+"/script>");
		</script>

		<!-- <![endif]-->

		<!--[if IE]>
<script type="text/javascript">
 window.jQuery || document.write("<script src='<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/js/jquery1x.min.js'>"+"<"+"/script>");
</script>
<![endif]-->
		<script type="text/javascript">
			if('ontouchstart' in document.documentElement) document.write("<script src='<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
		</script>

		<!-- inline scripts related to this page -->
		<script type="text/javascript">
			jQuery(function($) {
			 $(document).on('click', '.toolbar a[data-target]', function(e) {
				e.preventDefault();
				var target = $(this).data('target');
				$('.widget-box.visible').removeClass('visible');//hide others
				$(target).addClass('visible');//show target
			 });
			});
		</script>
	</body>
</html>

<?php
  exit();
} //DisplayAdminLogin


// ############################################################################
// DISPLAY TAG FORM (SD343)
// ############################################################################
function DisplayTagForm($new=false, $global=false, $formUrl='tags.php',
                        $fixedPluginId=null, $fixedPluginType=null,
                        $allowed_obj_ids='')
{
  global $DB, $plugin_names;
  $phrases = LoadAdminPhrases(4); //Comments/Tags admin page
  // first call may haven $fixedPluginId present, any subsequent call
  // may have a value in POST (fixedplugin):
  if(empty($fixedPluginId) || !is_numeric($fixedPluginId))
  {
    $fixedPluginId = Is_Valid_Number(GetVar('fixedplugin', null, 'whole_number', true, false),null,2,999999);
  }
  if(empty($fixedPluginType) || !is_numeric($fixedPluginType))
  {
    if($fixedPluginType = GetVar('fixedtype', null, 'natural_number', true, false))
      $fixedPluginType = Is_Valid_Number($fixedPluginType,null,0,999);
  }
  if(empty($new))
  {
    $tagid = GetVar('tagid', 0, 'whole_number');
    $DB->result_type = MYSQL_ASSOC;
    if($tag_arr = $DB->query_first('SELECT t.*, p.name pluginname'.
                                   ' FROM {tags} t'.
                                   ' LEFT JOIN {plugins} p ON t.pluginid = p.pluginid '.
                                   ' WHERE t.tagid = %d LIMIT 1',
                                   $tagid))
    {
      if(!empty($tag_arr['allowed_groups']))
        $tag_arr['allowed_groups'] = sd_ConvertStrToArray($tag_arr['allowed_groups'],'|');
      else
        $tag_arr['allowed_groups'] = array();
      if(!empty($tag_arr['allowed_object_ids']))
        $tag_arr['allowed_object_ids'] = sd_ConvertStrToArray($tag_arr['allowed_object_ids'],'|');
      else
        $tag_arr['allowed_object_ids'] = array();
    }
    else
    {
      DisplayMessage($phrases['tags_tag_not_found'],true);
      return false;
    }
    if(!empty($tag_arr['pluginid']) && empty($tag_arr['pluginname']))
    {
      $tag_arr['pluginname'] = '<strong>'.$phrases['tags_plugin_not_found'].'</strong>';
    }
    $action = 'updatetag';
    $title = 'tags_edit_tag';
    if(!empty($global) || !empty($tag_arr['tagtype']) || empty($tag_arr['pluginid'])) $global = 1;
  }
  else
  {
    $action = 'inserttag';
    $title = empty($global) ? 'tags_create_tag' : 'tags_create_global_tag';
    $tagid = 0;
    $tag_arr = array(
      'pluginid'       => 0,
      'pluginname'     => '',
      'objectid'       => 0,
      'censored'       => 0,
      'tagtype'        => 0,
      'tag'            => '',
      'allowed_groups' => array(),
      'html_prefix'    => '',
      'allowed_object_ids' => array(),
    );
  }

  echo '
  <form method="post" class="form-horizontal" id="tag_edit_form" action="'.$formUrl.'?tagid=' . $tagid . '">
  '.PrintSecureToken();
  if(!empty($global))
  {
    echo '<input type="hidden" name="global" value="1" />';
  }
  
  echo '<h3 class="header blue lighter">' . $phrases[$title] . '</h3>
  		<div class="hr hr-2 space-20"></div>
		
  		<div class="form-group">
			<label class="col-sm-2 control-label" for="tag">' . $phrases['tags_tag'] . '</label>
			<div class="col-sm-6">
				<input type="text" name="tag" class="form-control" id="tag" value="'.htmlspecialchars($tag_arr['tag']).'">
			</div>
		</div>';

  $where = '> 1';
  
  if(!empty($fixedPluginId) && is_numeric($fixedPluginId)) $where = '= 1'.(int)$fixedPluginId;
  
  $plugins = $DB->query('SELECT pluginid, name FROM {plugins} WHERE pluginid '.$where);
  
  echo '<div class="form-group">
			<label class="col-sm-2 control-label" for="tag_plugin">' . $phrases['tags_plugin'] . '
			<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="'.$phrases['tags_plugin_hint'].'" title="Help">?</span>
			</label>
			<div class="col-sm-6">
				<select id="tag_pluginid" name="pluginid" class="form-control">
      				<option value="0" '.(empty($tag_arr['pluginid'])?'selected="selected"':'').'>---</option>';
				  $plugins_arr = array();
				  while($entry = $DB->fetch_array($plugins,null,MYSQL_ASSOC))
				  {
					$name = isset($plugin_names[$entry['name']]) ? $plugin_names[$entry['name']] : $entry['name'];
					$plugins_arr[$name] = $entry['pluginid'];
				  }
				  ksort($plugins_arr);
				  foreach($plugins_arr AS $name => $id)
				  {
					echo '<option value="'.$id.'"'.($id==$tag_arr['pluginid']?' selected="selected"':'').'>'.$name.'</option>';
				  }
				  echo '
				</select>
				
			</div>
		</div>';
 
  if(empty($new) && !empty($tag_arr['objectid']) && function_exists('TranslateObjectID'))
  {
    echo '
	<div class="form-group">
			<label class="col-sm-2 control-label" for="tag">' . $phrases['tags_item'] . '</label>
			<div class="col-sm-6">
			<p class="form-control-static">
				'.TranslateObjectID($tag_arr['pluginid'], $tag_arr['objectid']).'
			</p>
			</div>
		</div>';
  }
  echo '<div class="form-group">
			<label class="col-sm-2 control-label" for="tag_type">' . $phrases['tags_tag_type'] . '
			<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="'.$phrases['tags_tag_type_hint'].'" title="Help">?</span>
			</label>
			<div class="col-sm-6">';
			if(isset($fixedPluginType))
			  {
				echo '<input type="hidden" name="fixedtype" value="'.$tag_arr['tagtype'].'">';
				switch($fixedPluginType)
				{
				  case 0: echo $phrases['tags_tag_type_user'];
				  case 1: echo $phrases['tags_tag_type_global'];
				  case 2: echo $phrases['tags_tag_type_prefix'];
				  case 3: echo $phrases['tags_tag_type_category'];
				  default: echo $phrases['tags_tag_type_other'];
				}
			  }
		  else
		  {
			echo '
			  <select id="tag_type" name="tag_type" class="form-control">
			  '.(empty($new)?'<option value="0"'.(empty($tag_arr['tagtype'])?' selected="selected"':'').'>'.$phrases['tags_tag_type_user'].'</option>':'').'
			  <option value="1"'.(!empty($tag_arr['tagtype'])&&($tag_arr['tagtype']==1)?' selected="selected"':'').'>'.$phrases['tags_tag_type_global'].'</option>
			  <option value="2"'.(!empty($tag_arr['tagtype'])&&($tag_arr['tagtype']==2)?' selected="selected"':'').'>'.$phrases['tags_tag_type_prefix'].'</option>
			  <option value="3"'.(!empty($tag_arr['tagtype'])&&($tag_arr['tagtype']==3)?' selected="selected"':'').'>'.$phrases['tags_tag_type_category'].'</option>';
			if(!empty($tag_arr['tagtype']) && ($tag_arr['tagtype'] > 3))
			{
			  echo '
				<option value="'.$tag_arr['tagtype'].'" selected="selected">'.$phrases['tags_tag_type_other'].' ('.$tag_arr['tagtype'].')</option>';
			}
			echo '
			  </select>';
		  }
		  echo'
			</div>
		</div>';
		
  echo '<div class="form-group">
			<label class="col-sm-2 control-label" for="censored">' . $phrases['tags_censored'] . '
			<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="'.$phrases['tags_censored_hint'].'" title="Help">?</span>
			</label>
			<div class="col-sm-6">
				<input type="checkbox" class="ace" name="censored" value="1" '.(empty($tag_arr['censored'])?'':'checked="checked" ').'/><span class="lbl"</span>
				
			</div>
		</div>';
		
  $forum_id = GetPluginID('Forum');
  if(!empty($forum_id) && empty($new) && ($tag_arr['tagtype']==2))
  {
    echo '<div class="form-group" id="tags_allowed_obj_ids" style="display:none">
			<label class="col-sm-2 control-label" for="allowed_object_ids">' . $phrases['tags_allowed_objects'] . '</label>
			<div class="col-sm-6">
				<select name="allowed_object_ids[]" id="allowed_object_ids" class="form-control" multiple="multiple">
    ';
    $parent_id = 0;
    $forums_tbl = PRGM_TABLE_PREFIX.'p_forum_forums';
    $posts_tbl  = PRGM_TABLE_PREFIX.'p_forum_posts';
    $topics_tbl = PRGM_TABLE_PREFIX.'p_forum_topics';
    $getdata = $DB->query(
    "SELECT concat(
        lpad(IF(ff.parent_forum_id <> 0, fp.display_order, ff.display_order),4,'0'),
        lpad(IF(ff.parent_forum_id <> 0, fp.forum_id, ff.forum_id),6,'0')) sortorder,
      (SELECT COUNT(*) FROM $forums_tbl ff2 WHERE ff2.parent_forum_id = ff.forum_id) subforums,
      ff.forum_id, ff.title, ff.is_category, fp.forum_id parent_id
      FROM $forums_tbl ff
      LEFT JOIN $forums_tbl fp on fp.forum_id = ff.parent_forum_id
      ORDER BY sortorder, subforums DESC, ff.display_order");
    while($entry = $DB->fetch_array($getdata,null,MYSQL_ASSOC))
    {
      if(empty($entry['is_category']))
      echo '<option value="'.$entry['forum_id'].'"'.
         (in_array($entry['forum_id'],$tag_arr['allowed_object_ids'])?' selected="selected"':'').
         '>'.$entry['title'].'</option>';
    }
    echo '</select>
		</div>
	</div>';
  }
  if(empty($new) || !empty($tag_arr['tagtype']))
  {
    echo '<div class="form-group" id="tags_html_prefix" style="display:none">
			<label class="col-sm-2 control-label" for="html_prefix">' . $phrases['tags_html_prefix'] . '</label>
			<div class="col-sm-6">';
			PrintWysiwygElement('html_prefix', (isset($tag_arr['html_prefix'])?htmlentities($tag_arr['html_prefix']):''), 2, 50);
	echo'
			</div>
		</div>
		
		<div class="form-group">
			<label class="col-sm-2 control-label" for="allowed_groups">' . $phrases['tags_groups'] . '
			<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="'.$phrases['tags_allowed_usergroups_hint'].'" title="Help">?</span>
			</label>
			<div class="col-sm-6">
			  <select name="allowed_groups[]" id="allowed_groups" multiple="multiple" class="form-control">';
			// Fetch all usergroups (do not exclude Administrators here!)
			$getrows = $DB->query('SELECT usergroupid, name FROM {usergroups} ORDER BY usergroupid');
			$options_cv = '';
			while($ug = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
			{
			  $ugid = $ug['usergroupid'];
			  $ugname = $ug['name'];
			  echo '<option value="'.$ugid.'" '.
				   (in_array($ugid,$tag_arr['allowed_groups']) ? 'selected="selected"' : '').">".$ugname.'</option>';
			} //while
			echo '</select>
					
				</div>
			</div>';
  }
  if(empty($new))
  {
    echo '<div class="form-group">
			<label class="col-sm-2 control-label" for="deletetag">' . $phrases['tags_delete_tag'] . '</label>
			<div class="col-sm-6">
				<input type="checkbox" class="ace" name="deletetag" value="1" />
				<span class="lbl">&nbsp;'.$phrases['tags_delete_this_tag'].'</span>
			</div>
		</div>';
  }
  
  
  PrintSubmit($action, ($phrases[empty($new)?'tags_update_tag':'tags_create_tag']), 'tag_edit_form', 'fa-check');
  
  
 echo'
  </form>
  
<script type="text/javascript"> //<![CDATA[
var tag_type_select, tag_plugin_select;
function DisplayTagHTML() {
  var pluginid = parseInt($(tag_pluginid_select).val(),10);
  var tag_type = parseInt($(tag_type_select).val(),10);
  var cssval = (tag_type==2) ? "" : "none";
  cssval = (pluginid==='.$forum_id.') ? "" : "none";
  
  if(tag_type == 2)
  {
	  $("#tags_html_prefix").show("fast");
  }
  else
  {
	  $("#tags_html_prefix").hide();
  }
  
  if(pluginid =='.$forum_id.')
  {
	  $("#tags_allowed_obj_ids").show();
  }
  else
  {
	  $("#tags_allowed_obj_ids").hide();
  }
	  
}
jQuery(document).ready(function() {
  tag_type_select = $("form#tag_edit_form select#tag_type");
  tag_pluginid_select = $("form#tag_edit_form select#tag_pluginid");
  $(tag_pluginid_select).change(function(){ DisplayTagHTML(); });
  $(tag_type_select).change(function(){ DisplayTagHTML(); });
  DisplayTagHTML();
  
  $("#allowed_groups").chosen();
  $("#allowed_object_ids").chosen();
  $("[data-rel=popover]").popover({container:"body"});

});
//]]>
</script>
';
} //DisplayTagForm


/**
* Displays a form Row
*
* @param 
*/
function PrintFormRow($label, $inputname, $inputtype, $description = '', $inputclass = "input-medium", $javascript = '', $return = FALSE)
{
	// Input class
	if(is_array($inputclass))
	{
		$inputclass = @implode(' ', $inputclass);
	}
	
	$r = '<div class="form-group">
			<label class="col-sm-3 control-label" for="' . $inputname .'">' . $label . '</label>
				<div class="col-sm-6">';
			
	switch ($inputtype)
	{
		case 'text':
			$r .= '<input type="text" name="'.$inputname.'" id="'.$inputname.'" class="'.$inputclass.'" ' . $javascript . '>';
		break;
	}
	
	if($return)
		return $r;
		
	echo $r;
}

/*
*
* Builds a Bootstrap Popover
*
* @param string phrase
* @param string $title
* @param string position
* @param string class
*
* @raturn string
*/
function HelpPopover($phrase, $title = 'Help', $position = 'right', $class = '')
{
	echo '<span class="help-button ' . $class .'" data-rel="popover" data-trigger="hover" data-placement="'.$position.'" data-content="'.$phrase.'" title="'.$title.'">?</span>';
}
	

// ############################################################################
// CHECK ADMIN ACCESS
// ############################################################################
function CheckAdminAccess($page_name = '')
{
  global $login_errors_arr, $pluginid, $sdlanguage, $userinfo;
  $page_name = strtolower($page_name);
  // Guests and Banned users are NEVER allowed any admin access!
  if(empty($userinfo) || empty($userinfo['loggedin']) || !empty($userinfo['banned']))
  {
    if(!empty($_POST['login']))
    {
      $GLOBALS['login_errors_arr'] = $sdlanguage['wrong_password'];
    }
    DisplayAdminLogin();
    exit;
  }
  // If user has full admin access, return
  if(!empty($userinfo['adminaccess']))
  {
    return true;
  }
  // User is not an admin and does not have access to plugins page
  if(empty($userinfo['adminaccess']) && strlen($page_name) &&
     ($page_name != 'articles') && ($page_name != 'view_plugin') &&
     (empty($userinfo['admin_pages']) || !@in_array(strtolower($page_name), $userinfo['admin_pages'])))
  {
    if(isset($_POST['login']) || ($page_name.'.php' != strtolower(ADMIN_DEFAULT_PAGE)))
    {
      $GLOBALS['login_errors_arr'] = AdminPhrase('common_page_access_denied');
    }
    // Try to redirect to first admin-allowed page
    if(!empty($userinfo['admin_pages']))
    {
      if(isset($userinfo['admin_pages'][0]))
        header('Location: '.ROOT_PATH.ADMIN_PATH.'/'.$userinfo['admin_pages'][0].'.php');
      else
        header('Location: '.ROOT_PATH.ADMIN_PATH.'/index.php');
    }
    else
    {
      DisplayAdminLogin();
      exit;
    }
    exit;
  }
  $authormode = empty($userinfo['adminaccess']) && !empty($userinfo['authormode']); //SD360
  // User has access to given page
  // Lets make sure they are viewing the plugins page and have access to said plugin
  if(strlen($page_name))
  {
    $kick_moderator = true;
    if(@in_array($page_name, $userinfo['admin_pages']))
    {
      $kick_moderator = false;
    }
    else
    if( ($page_name == 'articles') &&
        ((!empty($userinfo['pluginadminids']) && @in_array($pluginid, $userinfo['pluginadminids'])) ||
         (!empty($userinfo['pluginmoderateids']) && @in_array($pluginid, $userinfo['pluginmoderateids'])) ||
         ($authormode || @in_array(strtolower('articles'), $userinfo['admin_pages'])) ))
    {
      $kick_moderator = false;
    }
    else
    if($page_name == 'plugins')
    {
      $action = GetVar('action', '', 'string');
      // Allow all - if admin page right - or at least view of all plugins
      if(@in_array($page_name, $userinfo['admin_pages']) ||
         ( (!empty($userinfo['pluginadminids']) || !empty($userinfo['custompluginadminids'])) &&
           (!strlen($action) || ($action == 'display_plugins'))))
      {
        $kick_moderator = false;
      }
      else
      // check permission to edit/update custom plugins
      if(($action == 'display_custom_plugin_form') || ($action == 'update_custom_plugin'))
      {
        $custompluginid = GetVar('custompluginid', 0, 'whole_number');
        if(($custompluginid > 0) && @in_array($custompluginid, $userinfo['custompluginadminids']))
        {
          $kick_moderator = false;
        }
      }
    }
    else if($page_name == 'view_plugin')
    {
      $pluginid = GetVar('pluginid', 0, 'whole_number');
      if(($pluginid > 0) && (@in_array('plugins', $userinfo['admin_pages']) || @in_array($pluginid, $userinfo['pluginadminids'])))
      {
        $kick_moderator = false;
      }
    }
    // kick moderator back to plugins page
    if($kick_moderator)
    {
      DisplayAdminHeader();
      DisplayMessage(AdminPhrase('common_page_access_denied'), true);
      DisplayAdminFooter();
      $DB->close();
      exit;
    }
  }
} //CheckAdminAccess
// ######################## PRINT WYSIWYG HTML ELEMENT #########################
function PrintWysiwygElement($name, $value, $rows = 15, $columns = 50, $overrideWysiwyg = null)
{
  global $mainsettings_enablewysiwyg, $mainsettings_wysiwyg_starts_off, $sdurl;
  if(!isset($overrideWysiwyg) || ($overrideWysiwyg===false))
  {
    $overrideWysiwyg = $mainsettings_enablewysiwyg;
  }
  $overrideWysiwyg = (int)$overrideWysiwyg;
  $id = str_replace(array('[',']'),array(),$name); //SD342
  switch($overrideWysiwyg)
  {
    case 1 : //TinyMCE
      echo '
      <textarea class="mce" name="'.$name.'" rows="'.$rows.'" cols="'.$columns.'">' . $value . '</textarea>';
      break;
    case 2 : //FCKeditor
      //SD322: upgraded to CKeditor 3.x
      //SD343: if wysiwyg is to be disabled at start, just output textarea
      if(!empty($mainsettings_wysiwyg_starts_off))
      {
        echo '
        <textarea class="ckeditor_enabled" id="'.$id.'" name="'.$name.'" rows="'.$rows.'" cols="'.$columns.'" style="width: 98%;">' . $value . '</textarea>
        <div class="align-left"><a class="btn btn-default btn-sm" href="javascript:;" onmousedown="javascript:toggleEditor(\''.$id.'\'); return false;">'.AdminPhrase('wysiwyg_toggle_editor').'</a></div>';
        return;
      }
      include_once(ROOT_PATH . ADMIN_PATH . '/ckeditor/ckeditor.php');
      if(class_exists('CKeditor'))
      {
        $oFCKeditor = new CKeditor($name);
        $oFCKeditor->basePath = ROOT_PATH . ADMIN_PATH . '/ckeditor/';
        $oFCKeditor->textareaAttributes['cols'] = $columns;
        $oFCKeditor->textareaAttributes['rows'] = $rows;
        $oFCKeditor->config['BaseHref'] = $sdurl;
        //$oFCKeditor->config['removePlugins'] = 'elementspath,about,pagebreak,preview,newpage,save';
        //$oFCKeditor->config['extraPlugins'] = 'MediaEmbed';
        $oFCKeditor->config['toolbarCanCollapse'] = false;
        $oFCKeditor->config['skin'] = 'office2003';
        $oFCKeditor->config['toolbar'] = 'Full';
        $oFCKeditor->config['toolbar_Full'] = array(
          array('Source','-','Templates'),
          array('Maximize', 'ShowBlocks'),
          array('Cut','Copy','Paste','PasteText','PasteFromWord','-','Print'),
          array('Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'),
          '/',
          array('Image','Flash',/*'MediaEmbed',*/'Table','HorizontalRule','Smiley','SpecialChar','Iframe'),
          array('Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'),
          array('Link','Unlink','Anchor'),
          array('BidiLtr', 'BidiRtl'),
          '/',
          array('Bold','Italic','Underline','Strike','-','Subscript','Superscript'),
          array('NumberedList','BulletedList','-','Outdent','Indent','Blockquote','CreateDiv'),
          array('JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'),
          '/',
          array('TextColor','BGColor'),
          array('Styles','Format','Font','FontSize'),
          );
        $oFCKeditor->editor($name, $value);
        break;
      }
    default :
      echo '<textarea id="' . $name . '" name="' . $name . '" rows="'.$rows.'" cols="'.$columns.'" style="width: 95%;">' . $value . '</textarea>';
  }
} //PrintWysiwygElement
function sd_PrintBreadcrumb($title, $link='', $addlink=null)
{
  global $refreshpage;
  echo '<strong>&nbsp;<a href="'. (empty($link)&&!empty($refreshpage) ? $refreshpage : $link).'">'.$title.'</a>';
  if(!empty($addlink))
  {
    echo '&nbsp;&raquo;&nbsp;'.$addlink;
  }
  echo '</strong>';
} //sd_PrintBreadcrumb
function sd_purge_plugin_cache($pluginid = 0)
{
  global $SDCache;
  if(isset($SDCache) && !empty($pluginid) && Is_Valid_Number($pluginid,0,2,999999))
  {
    $SDCache->delete_cacheid('planguage_'.$pluginid);
    $SDCache->delete_cacheid('psettings_'.$pluginid);
  }
} //sd_purge_plugin_cache
function DisplayIPTools($link_selector='a.hostname', $parent_element='td', $extraJS='')
{
  global $userinfo;
  if(empty($userinfo['userid']) || empty($userinfo['adminaccess']) || !defined('IN_ADMIN')) return;
  echo '
  <div id="iptools_selector" style="display: none">
  <div class="table-header">' . addslashes(AdminPhrase('ip_tools_title')).'</div>
    <a class="honeypot ceebox blue" rel="iframe modal:false" title="Project Honeypot" href="#"><i class="ace-icon fa fa-external-link blue"></i> Project Honeypot</a>
    <a class="sfs ceebox blue" rel="iframe modal:false" title="StopForumSpam" href="#"><i class="ace-icon fa fa-external-link blue"></i> StopForumSpam</a>
    <a class="erols ceebox blue" rel="iframe modal:false" title="Reverse Lookup" href="#"><i class="ace-icon fa fa-external-link blue"></i> Reverse Lookup (erols.com)</a>
    <a class="whois1 ceebox blue" rel="iframe modal:false" title="WhoIs" href="#"><i class="ace-icon fa fa-external-link blue"></i> WhoIs (Heise.de)</a>
    <a class="whois2 ceebox blue" rel="iframe modal:false" title="ARIN" href="#"><i class="ace-icon fa fa-external-link blue"></i> ARIN</a>
    <a class="whois3 ceebox blue" rel="iframe modal:false" title="RIPE" href="#"><i class="ace-icon fa fa-external-link blue"></i> RIPE</a>
    <a class="spamhaus ceebox blue" rel="iframe modal:false" title="Spamhaus" href="#"><i class="ace-icon fa fa-external-link blue"></i> Spamhaus</a>
    <a class="robtex ceebox blue" rel="iframe modal:false" title="Robtex IP Lookup" href="#"><i class="ace-icon fa fa-external-link blue"></i> Robtex IP Lookup</a>
    <a class="host ceebox blue" rel="iframe modal:false" title="Hostname Lookup" href="#"><i class="ace-icon fa fa-external-link blue"></i> Hostname Lookup</a><br />
    <a href="view_plugin.php?pluginid=12&amp;loadwysiwyg=1&amp;page=2" class=" btn btn-danger btn-xs ">Registration &raquo; Prevention</a>
  </div>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function() {
(function($){
  $("<link>").appendTo("head").attr({rel: "stylesheet", type: "text/css", href: sdurl+"'.ADMIN_STYLES_FOLDER.'assets/css/jdialog.css" });
  $("#iptools_selector a.ceebox").attr("rel", "iframe modal:false");
  /* IP Tools popup */
  $(document).delegate("'.$link_selector.'","click",function(e){
    e.preventDefault();
    jDialog.close();
    hostname = $(this).text();
    $(this).jDialog({
      align : "left",
      content : $("div#iptools_selector").html(),
      close_on_body_click : true,
      idName : "iptools_popup",
      lbl_close: "'.addslashes(AdminPhrase('hint_close')).'",
      title : "",
      title_visible : true,
      top_offset : -40,
      width : 200
    });
    $("#iptools_popup a.honeypot").attr("href","http://www.projecthoneypot.org/ip_"+encodeURI(hostname));
    $("#iptools_popup a.sfs").attr("href","http://www.stopforumspam.com/api?ip="+encodeURI(hostname));
    $("#iptools_popup a.erols").attr("href","http://cgibin.erols.com/ziring/cgi-bin/nsgate/gate.pl?submit=Search+by+IP+Address&q="+encodeURI(hostname)+"&mode=2&qtype=PTR");
    $("#iptools_popup a.whois1").attr("href","http://www.heise.de/netze/tools/whois/?rm=whois_query&amp;target_object="+encodeURI(hostname));
    $("#iptools_popup a.whois2").attr("href","http://whois.arin.net/rest/nets;q="+encodeURI(hostname)+"?showDetails=true&amp;showARIN=false");
    $("#iptools_popup a.whois3").attr("href","http://www.db.ripe.net/whois?form_type=simple&amp;full_query_string=&amp;searchtext="+encodeURI(hostname)+"&amp;do_search=Search#ripe-form");
    $("#iptools_popup a.spamhaus").attr("href","http://www.spamhaus.org/query/bl?ip="+encodeURI(hostname));
    $("#iptools_popup a.robtex").attr("href","http://www.robtex.com/ip/"+encodeURI(hostname)+".html#ip");
    $("#iptools_popup a.host").attr("href","http://www.topwebhosts.org/tools/lookup.php?query="+encodeURI(hostname)+"&amp;submit=Search");
    $("#iptools_popup a.ceebox").ceebox({animSpeed:"fast", htmlGallery:false, overlayOpacity: 0.8});
  });
  '.$extraJS.'
}(jQuery));
});
//]]>
</script>
';
} //DisplayIPTools
function sd_delete_recursively($path)
{
  if( empty($path) || ($path=='.') || ($path=='..') ||
      (stripos($path,'ftp:')!==false) || (stripos($path,'http:')!==false))
  {
    return true;
  }
  $tmp = realpath(dirname($path)).DIRECTORY_SEPARATOR;
  if(stripos($tmp, SD_DOCROOT) === false) return false;
  $olddog = $GLOBALS['sd_ignore_watchdog'];
  $GLOBALS['sd_ignore_watchdog'] = true;
  $res = sd_delete_recursively_ex($path);
  $GLOBALS['sd_ignore_watchdog'] = $olddog;
  return $res;
}
function sd_delete_recursively_ex($path) // do not use this directly!
{
  if(empty($path) || ($path=='.') || ($path=='..')) return true;
  if(is_file($path))
  {
    return @unlink($path);
  }
  if(is_dir($path))
  {
    $res = 1;
    if(false !== ($scan = @glob(rtrim($path,'\\/').'/*')))
    {
      foreach($scan as $idx => $fsentry)
      {
        $res &= sd_delete_recursively_ex($fsentry);
      }
    }
    $res &= @rmdir($path);
    return $res;
  }
}