<?php

// DEFINE CONSTANTS
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');

// INIT PRGM
include(ROOT_PATH . 'includes/init.php');

// If allowed by mainsetting, activate WYSIWYG editor
$load_wysiwyg = $mainsettings['enablewysiwyg']?1:0;

// LOAD ADMIN LANGUAGE
$admin_phrases = LoadAdminPhrases(7);


sd_header_add(array(
  'other' => array('
<script type="text/javascript">
if (typeof(jQuery) !== "undefined") {
jQuery(document).ready(function() {
  (function($){
	  
	   $("a#checkall").click(function(e){
      e.preventDefault();
      var ischecked = 1 - parseInt($(this).attr("rel"),10);
      if(ischecked==1) {
        $("input.deletebb2").attr("checked","checked");
        $("form#bb2 tr:not(thead tr)").addClass("danger");
      } else {
        $("input.deletebb2").removeAttr("checked");
        $("form#bb2 tbody tr").removeClass("danger");
      }
      $(this).attr("rel",ischecked);
      return false;
    });
	
	$("input[type=checkbox].deletebb2").on("change",function(){
      var tr = $(this).parents("tr");
      $(tr).toggleClass("danger");
    });
	
	$("a.deletelink").on("click", function(e) {
		var link = $(this).attr("href");
	  e.preventDefault();
  bootbox.confirm("'.AdminPhrase('file_delete_prompt').'", function(result) {
    if(result) {
       window.location.href = link;
    }
  });
});
	
	 /* SD400 */
  $(document).delegate("input[type=checkbox].del_entry","change",function(){
    var tr = $(this).parents("tr");
    $(tr).toggleClass("danger");
  });
  
  $("#deletebutton").on("click", function(e) {
	  e.preventDefault();
  bootbox.confirm("Are you sure?", function(result) {
    if(result) {
       $("form#syslogform").submit();
    }
  })
})

$("a.clearsystemlog").on("click", function(e) {
	 var link = $(this).attr("href");
	 e.preventDefault();
  bootbox.confirm("'.AdminPhrase('syslog_clear_log_prompt').'", function(result) {
    if(result) {
      window.location.href = link;
    }
  })
})

$("#bb2_delete_submit").on("click", function(e) {
	  e.preventDefault();
  bootbox.confirm("'.AdminPhrase('mod_bb2_delete_prompt').'", function(result) {
    if(result) {
      $("#bb2").submit();
    }
  });
});
  })(jQuery);
});
}
</script>
')));


// CHECK PAGE ACCESS
CheckAdminAccess('settings');


// DISPLAY ADMIN HEADER
DisplayAdminHeader('Settings', null/*$admin_sub_menu_arr*/, '', false);


// ############################################################################
// DISPLAY PHP INFO (SD 3.1.3)
// ############################################################################
function DisplayPHPinfo()
{
  echo '
<style type="text/css">
#phpinfo {}
#phpinfo pre {}
#phpinfo a:link { color: #000099; text-decoration: none; background-color: #ffffff; }
#phpinfo a:hover {}
#phpinfo table { border-collapse: collapse; }
#phpinfo .center { text-align: center; }
#phpinfo .center table { margin-left: auto; margin-right: auto; text-align: left; }
#phpinfo .center th { text-align: center !important; }
#phpinfo td, th { border: 1px solid #000000; font-size: 75%; vertical-align: baseline; }
#phpinfo h1 { font-size: 150%; }
#phpinfo h2 { font-size: 125%; }
#phpinfo .p { text-align: left; }
#phpinfo .e { background-color: #ccccff; font-weight: bold; color: #000000; }
#phpinfo .h { background-color: #9999cc; font-weight: bold; color: #000000; }
#phpinfo .v { background-color: #cccccc; color: #000000; }
#phpinfo .vr { background-color: #cccccc; text-align: right; color: #000000; }
#phpinfo img { float: right; border: 0px; }
#phpinfo hr { width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000000; }
</style>
';
  ob_start();
  @phpinfo();
  $pinfo = ob_get_contents();
  ob_end_clean();
  $pinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms','$1',$pinfo);
  echo '<div id="phpinfo">'.$pinfo.'</div>';

} //DisplayPHPinfo


// ############################################################################
// DISPLAY MySQL INFO (SD 3.1.3)
// ############################################################################
function DisplayMySQLInfo()
{
  global $DB;

  StartTable('MySQL Information', array('table', 'table-bordered','table-striped'));
  
  echo '<thead>
  			<tr>
				<th>Setting</th>
				<th>Value</th>
			</tr>
		</thead>
		<tbody>';
  $mysqlver = $DB->query_first("SELECT VERSION();");
  echo '
    <tr>
      <td>MySQL Version:</td>
	  <td>'.$mysqlver[0].'</td>
    </tr>
    ';

  if(function_exists('mysql_stat'))
  {
    echo '
    <tr>
      <td>Statistics:</td>
    </tr>
    ';
    $mysql_status = explode('  ', mysql_stat($DB->conn));
    foreach($mysql_status as $value)
    {
      echo '
      <tr>
	  	<td>&nbsp;</td>
        <td class="tdrow1">'.$value.'</td>
      </tr>
      ';
    }
  }

  if($mysql_info = $DB->query('SHOW VARIABLES'))
  {
    echo '
	<thead>
    <tr>
      <th colspan="2"><span class="bigger-120">MySQL Variables</span></th>
    </tr>
	</thead>
    ';
    while ($row = $DB->fetch_array($mysql_info))
    {
      echo '
      <tr>
        <td class="tdrow2">' . $row['Variable_name'] . '</td>
        <td class="tdrow3">' . $row['Value'] . '</td>
      </tr>
      ';
    }
    $DB->free_result($mysql_info);
  }
  echo '</tbody></table></div>';

} //DisplayMySQLInfo


// ############################################################################
// DISPLAY CACHE SETTINGS
// ############################################################################

function DisplayCacheInfo()
{
  global $DB, $admin_phrases;

  if(!is_dir(ROOT_PATH.'cache'))
  {
    echo '<h2>'.$admin_phrases['message_no_cache_folder'].'</h2><br />';
  }
  else if(!is_writable(ROOT_PATH.'cache'))
  {
    echo '<h2>'.$admin_phrases['message_cache_not_writable'].'</h2><br />';
  }
  
  $getsitesettings = $DB->query('SELECT * FROM {mainsettings}'.
                                " WHERE groupname = 'settings_cache'".
                                ' ORDER BY displayorder ASC');

  echo '<h3 class="header blue lighter">' . AdminPhrase('settings_cache') . '</h3>';

  echo '
  <form action="settings.php?action=updatesettings" id="cachesettings" method="post" class="form-horizontal">
  <input type="hidden" name="display_type" value="info_cache" />';
 

  while($setting = $DB->fetch_array($getsitesettings,null,MYSQL_ASSOC))
  {
    $setting['title'] = isset($admin_phrases[$setting['title']]) ? $admin_phrases[$setting['title']] : $setting['title'];
    $setting['description'] = isset($admin_phrases[$setting['description']]) ? $admin_phrases[$setting['description']] : $setting['description'];

    echo '
	 <div class="form-group">
  		<label class="control-label col-sm-2">'. $setting['title'] .'
			<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="' . $setting['description']. '" title="Help">?</span>
		</label>
		<div class="col-sm-6">
    		<label>
				<input type="radio" class="ace" name="settings['.$setting['settingid'].']"' . ($setting['value'] == 1 ? ' checked="checked"' : '') .  ' value="1" /> 
				<span class="lbl">'. AdminPhrase('common_yes') . '</span> 
			</label>&nbsp;
			<label>
	 			<input type="radio" class="ace" name="settings['.$setting['settingid'].']"'. ($setting['value'] == 0 ? ' checked="checked"' : '').' value="0" />
				<span class="lbl"> '. AdminPhrase('common_no') . '</span>
			</label>
	</div>
</div>';
  }
 
 	echo '<div class="center">';
      
  PrintSubmit('updatesettings', AdminPhrase('common_update_settings'), 'cachesettings', 'fa-check');
  
  echo '
  </form></div>';



  echo '<h3 class="header blue lighter">' . $admin_phrases['purge_cache'] . '</h3>';
  echo '
  <form action="settings.php" id="purge_cache" method="post" class="form-horizontal">
  <div class="form-group">
  		<label class="control-label col-sm-2">'. $admin_phrases['purge_cache'] .'
			<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="' . AdminPhrase('purge_cache_hint'). '" title="Help">?</span>
		</label>
		<div class="col-sm-6">
    		<label>
				<input type="radio" class="ace" name="purge_cache" value="1" /> 
				<span class="lbl">'. AdminPhrase('common_yes') . '</span> 
			</label>&nbsp;
			<label>
	 			<input type="radio" class="ace" name="purge_cachee" value="0" checked="checked" />
				<span class="lbl"> '. AdminPhrase('common_no') . '</span>
			</label>
	</div>
</div>';


  
  echo '<div class="center">';
  
  	PrintSubmit('purge_cache', $admin_phrases['purge_cache'], 'purge_cache', 'fa-trash-o','','','btn-danger');
   
   echo '</div> 
  </form>';


} //DisplayCacheInfo


// ############################################################################
// UPDATE SETTINGS
// ############################################################################

function PurgeCache()
{
  global $SDCache;
  
  $doPurge = GetVar('purge_cache',0,'bool');
  

  // SD313: delete Main Settings cache file
  if(isset($SDCache) && $doPurge)
  {
    $SDCache->purge_cache();
	
	RedirectPage('settings.php?display_type=info_cache', AdminPhrase('settings_updated'));
  }
  else
  {  
 	 RedirectPage('settings.php?display_type=info_cache', AdminPhrase('nothing_selected'));
  }


  

} //PurgeCache


// ############################################################################
// UPDATE SETTINGS
// ############################################################################

function UpdateSettings()
{
  global $DB, $load_wysiwyg, $SDCache;

  $new_settings_arr = GetVar('settings', array(), 'array');
  $currentlogo  = GetVar('currentlogo', '', 'html');
  $display_type = GetVar('display_type', '', 'string'); // both POST/GET!

  if($display_type == 'logo')
  {
    // update the logo
    $DB->query("UPDATE {mainsettings} SET value =  '" . $DB->escape_string($currentlogo) . "' WHERE varname = 'currentlogo'");
    $redirect_page = 'settings.php?display_type=logo';
    if($load_wysiwyg)
    {
      $redirect_page .= '&amp;load_wysiwyg=1';
    }
  }
  else if(in_array($display_type, array('search_tags','seo')))
  {
    while(list($key, $value) = each($new_settings_arr))
    {
      $value = unhtmlspecialchars($value);
      $DB->query("UPDATE {mainsettings} SET value = '" . $DB->escape_string($value) . "' WHERE settingid = %d", (int)$key);
    }
  }
  else if($display_type == 'database') //SD313
  {
    require('database.php');
  }
  else
  {
    while(list($key, $value) = each($new_settings_arr))
    {
      //SD370: make sure the setting actually exists
      if($getsetting = $DB->query_first('SELECT settingid, input, title'.
                                        ' FROM {mainsettings}'.
                                        ' WHERE settingid = %d LIMIT 1', $key))
      {
        //SD370: new type "enc": encrypt value with system hash (256bits)
        if($getsetting['input'] == 'enc')
        {
          if(strlen($value)) $value = sd_encodesetting($value);
        }
        // SD370: new input type "datetime" with optional line "readonly"
        elseif(substr($getsetting['input'],0,8) == 'datetime')
        {
          //TODO: datepicker support!
          // normalize line breaks in $input
          $getsetting['input'] = preg_replace("/(\r\n|\n\r|\r|\n)+/", "\r", trim($getsetting['input']));
          // split up individual lines into an array:
          $arr = preg_split('/\r/', substr($getsetting['input'], 8), -1, PREG_SPLIT_NO_EMPTY);
          // if readonly, skip to next setting
          if(is_array($arr) && in_array('readonly',$arr)) continue;
          $value = intval($value);
        }
        else
        {
          $value = sd_unhtmlspecialchars($value);
        }

        $DB->query("UPDATE {mainsettings} SET value = '".
                   $DB->escape_string($value).
                   "' WHERE settingid = ".(int)$key);
      }
    }
    $redirect_page = 'settings.php';
    if($load_wysiwyg)
    {
      $redirect_page .= '?load_wysiwyg=1';
    }
  }

  // SD313: delete Main Settings cache file
  if(isset($SDCache))
  {
    $SDCache->delete_cacheid(CACHE_ALL_MAINSETTINGS);
  }

  if(in_array($display_type, array('database','info_cache','search_tags','seo')))
  {
    $redirect_page = 'settings.php?display_type='.urlencode($display_type);
  }
  RedirectPage($redirect_page, AdminPhrase('settings_updated'));

} //UpdateSettings


// ############################################################################
// DISPLAY SETTINGS
// ############################################################################

function DisplaySettings()
{
  global $DB, $admin_phrases, $sdurl, $userinfo;

  $display_type = GetVar('display_type', '', 'string');

  $group = array();

  if(!strlen($display_type))
  {
    $group[] = 'settings_site_activation';
    if(!empty($userinfo['adminaccess']))
    {
      $group[] = 'settings_general_settings';
	  $group[] = 'settings_seo_settings';
      $group[] = 'settings_date_time_settings';
      $group[] = 'settings_email_settings';
      $group[] = 'settings_captcha';
      $group[] = 'settings_character_encoding';
      $group[] = 'settings_bbcode'; //SD313
      $group[] = 'settings_system_log'; //SD322
      $group[] = 'skins_options'; //SD340
      $group[] = 'settings_social_media_twitter'; //SD370
	  $group[] = 'settings_image_display';
	  $group[] = 'settings_external_libraries'; //SD420
	  $group[] = 'settings_search_tags';
    }
  }
  elseif($display_type == 'seo')
  {
    $group[] = 'settings_seo_settings';
  }
  elseif($display_type == 'search_tags')
  {
    $group[] = 'settings_search_tags';
  }
  elseif($display_type == 'logo')
  {
	  $group[] = 'settings_website_logo';
  }
  
  echo '
  <form action="settings.php" id="sdsettings" method="post" class="form-horizontal">
  <input type="hidden" name="display_type" value="' . $display_type . '" />
  <div class="tabbable tabs-left">
  <ul class="nav nav-tabs" role="tablist">';
  
   foreach($group as $key => $item)
   {
	   echo '<li class="'.($key == 0 ? "active" : "") . '">
				<a href="#'.$item.'" data-toggle="tab">
				 '.AdminPhrase($item).'</a>
			</li>';
   }
   
   echo '</ul>
   		 <div class="tab-content">';

  for($i = 0; $i < count($group); $i++)
  {
    $getsitesettings = $DB->query("SELECT * FROM {mainsettings} WHERE groupname = '" .
                                  $group[$i] . "' AND display = 1 ORDER BY displayorder ASC, title ASC");
								  
	echo '<div class="tab-pane in ' . ($i == 0 ? "active" : "").'" id="'.$group[$i].'">
	<h3 class="header blue lighter">' . AdminPhrase($group[$i]) . '</h3>';

    echo '
    <input type="hidden" name="groupnames[]" value="'.$group[$i].'" />';

    while($setting = $DB->fetch_array($getsitesettings,null,MYSQL_ASSOC))
    {
      $org_setting_title = $setting['title'];
      $setting['title'] = isset($admin_phrases[$setting['title']]) ? $admin_phrases[$setting['title']] : $setting['title'];
      $setting['description'] = isset($admin_phrases[$setting['description']]) ? $admin_phrases[$setting['description']] : $setting['description'];
      $input = isset($setting['input'])?$setting['input']:'';

      echo '
      <div class="form-group">
	  	<label class="control-label col-sm-3" for="'.$setting['settingid'].'">' . $setting['title'] .'
		<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="' . strip_tags($setting['description']) . '" title="'.AdminPhrase('common_help').'">?</span>
		</label>
		<div class="col-sm-6">';

      if($input == 'text')
      {
        echo '<input type="text" class="form-control" size="40" name="settings[' . $setting['settingid'] . ']" value="' .
             sd_unhtmlspecialchars($setting['value']) . '" />';
      }
      else
      if($input == 'password') //SD340
      {
        echo '<input type="password" class="form-control" size="40" name="settings[' . $setting['settingid'] . ']" value="' .
             sd_unhtmlspecialchars($setting['value']) . '" />';
      }
      else
      if($input == 'yesno')
      {
        echo  '<label><input type="radio" class="ace" name="settings['. $setting['settingid'] .']" ' . ($setting['value'] == 1 ? 'checked="checked"' : '') . ' value="1" />
			 <span class="lbl"> ' . AdminPhrase('common_yes') . '</span> </label>  &nbsp;&nbsp;<label>
			 <input type="radio" class="ace" name="settings['.$setting['settingid'].']" ' . ($setting['value'] == 0 ? 'checked="checked"' : '') . ' value="0" />
			 <span class="lbl"> ' . AdminPhrase('common_no') . '</span></label>';
      }
      else
      if($input == 'textarea')
      {
        echo '<textarea class="form-control" rows="6" name="settings[' . $setting['settingid'] . ']" >' .
             htmlspecialchars($setting['value']) . '</textarea>';
      }
      else
      if($input == 'pageselect') //SD343
      {
        echo GetPageSelection('settings['.$setting['settingid'].']',(int)$setting['value']);
      }
      //SD322: new input type "select" with simple "value|phrase" lines
      // It will try to automatically match the phrase to a admin phrase which has
      // as phrases ["select_" + settingname + underscore + value] (all lowercased!)
      // as the key.
      // E.g.: setting "siteactivation" will look like this (3 lines):
      // select:
      // on|On
      // off|Off
      // As phrases it will look for "select_siteactivation_on" and
      // "select_siteactivation_off" as it has values "on" and "off".
      else
      if(substr($input,0,7) == 'select:')
      {
        $select_arr = preg_split('#\r|\n#', substr($setting['input'],7), -1, PREG_SPLIT_NO_EMPTY);
        if(!empty($select_arr))
        {
          echo '<select class="form-control" name="settings[' . $setting['settingid'] . ']">';
          foreach($select_arr as $sel_entry)
          {
            list($sel_value, $sel_phrase) = explode('|', $sel_entry);
            $phrase_id = 'select_'.strtolower($setting['varname'].'_'.$sel_value);
            $sel_phrase = isset($admin_phrases[$phrase_id]) ? $admin_phrases[$phrase_id] : $sel_phrase;
            echo '<option value="'.$sel_value.'" '.($setting['value']==$sel_value?'selected="selected"':'').'>'.$sel_phrase.'</option>';
          }
          echo '</select>';
        }
      }
      // SD313: new input type "timezone" added
      else
      if($input == 'timezone')
      {
        echo GetTimezoneSelect('settings[' . $setting['settingid'] . ']', $setting['value']);
      }
      // SD313: separate display code for textarea and wysiwyg inputs
      else
      if($input == 'wysiwyg')
      {
        // SD313: wysiwyg in separate row, spanning 2-columns
        PrintWysiwygElement(htmlspecialchars('settings['.$setting['settingid'].']'), $setting['value'], 10, 80);
      }
      // SD370: new input type "enc": encrypts (fallback: compresses) value
      else
      if($input == 'enc')
      {
        // if installed, uses mcrypt library, otherwise base64_en/decode
        $setting['value'] = sd_decodesetting($setting['value']);
        echo '<input type="password" class="form-control" size="40" name="settings['.$setting['settingid'].
             ']" value="'.sd_unhtmlspecialchars($setting['value']).'" />';
      }
      // SD370: new input type "datetime" with optional line "readonly"
      else
      if(substr($input,0,8) == 'datetime')
      {
        //TODO: datepicker support!
        // normalize line breaks in $input
        $setting['input'] = preg_replace("/(\r\n|\n\r|\r|\n)+/", "\r", trim($setting['input']));
        // split up individual lines into an array:
        $arr = preg_split('/\r/', substr($setting['input'], 8), -1, PREG_SPLIT_NO_EMPTY);
        if(is_array($arr) && in_array('readonly',$arr))
        {
          if(trim($setting['value'])=='')
            echo '---';
          else
            echo DisplayDate($setting['value']);
          echo '<input class="form-control" type="hidden" name="settings['.$setting['settingid'].
                 ']" value="'.intval($setting['value']).'" />';
        }
        else
        {
          echo '<input type="text" class="form-0 size="20" name="settings['.$setting['settingid'].
               ']" value="'.DisplayDate($setting['value']).'" />';
        }
      }
      else
      if(isset($setting['input']))
      {
        if(substr($input,0,6) == 'select')
        {
          @eval('echo "'.$input.'";');
        }
        else
        {
          echo $input;
        }
      }

      //SD370: especially for Twitter offer link for admin to check access token
      if(($setting['varname']=='twitter_consumer_key') && !empty($setting['value']))
      {
        echo '<br /><a class="btn" href="'.SD_CLASS_PATH.'sd_twitter.php?p=1&amp;check=1'.
          PrintSecureUrlToken().'" target="_blank">Check Access Token</a>';
      }

      echo '</div></div>';
    }
	
	echo '</div>';
	
  }  // end for loop
 
  if($display_type == 'logo')
  {
    // logo settings
    $currentlogo = $DB->query_first("SELECT value FROM {mainsettings} WHERE varname = 'currentlogo'");
	
	echo '<div class="tab-pane in active" id="logo">';
    PrintWysiwygElement('currentlogo', htmlspecialchars($currentlogo['value']), 10, 90);
	echo '</div>';
  }

  echo '
  	</div>
  	</div><br />';
	
	 PrintSubmit('updatesettings', AdminPhrase('common_update_settings'), 'sdsettings', 'fa-check');
	 echo'
  </form>';
  
  

} //DisplaySettings


function PrintMenuRow($url, $name, $extra = '', $div_id = '')
{
  global $sdurl;

  echo '<li class="menulink-normal" '.(isset($url{1})?'onclick="nav_goto(\'' . $url . '\');">':'').'
  <div '.(isset($div_id{1})?'id="'.$div_id.'" ':'').'class="menuitem-outer">
  ';
  if(isset($extra{0}))
  {
    if(isset($url{1}))
    {
      echo '<div class="menuitem-inner"><a href="'.$url.'" target="mainFrame" onclick="javascript:return false;">'.$name.'</a></div>';
    }
    echo '<div class="right">'.$extra.'</div></div>';
  }
  else
  {
    if(isset($url{1}))
    {
      echo '<a href="'.$url.'" target="mainFrame" onclick="javascript:return false;">'.$name.'</a>';
    }
    echo '</div>';
  }
  echo "\n</li>\n";
}

function PrintModules()
{
  global $DB, $sd_modules;

  if(!isset($sd_modules) || !($mc = $sd_modules->ModuleCount()))
  {
    echo '
    <div class="menutitle"><strong>None available</strong></div>
    ';
    return;
  }

  for($i = 0; $i < $mc; $i++)
  {
    if($entry = $sd_modules->GetModuleByIndex($i))
    {
      //OLD SD2.6 display:
      //PrintMenuRow(SD_MODULES_PATH . $entry['settingspath'].'?action=displaysettings', $entry['displayname']);
      $action = 'displaysettings';
      include(SD_MODULES_PATH . $entry['settingspath']);
    }
  }

} //PrintModules


// ############################################################################
// GET ACTION
// ############################################################################

// Check for "action" and "display_type" first
$action = GetVar('action', 'DisplaySettings', 'string');
$display_type = GetVar('display_type', '', 'string'); // both POST/GET!
//SD343: check for valid settings call
$error = false;
if($display_type && !in_array($display_type,
     array('database','info_cache','info_mysql','info_php','logo',
           'modules','search_tags','seo','syslog')))
{
  $error = true;
  DisplayMessage('Invalid settings call!',true);
}

if($display_type == 'database') //313
{
  // Database has to be used outside of any function!
  if(!@include(ROOT_PATH . ADMIN_PATH . '/database.php'))
  {
    $error = true;
    DisplayMessage('Database file not found in '.ADMIN_PATH.' folder!', true);
  }
}
else
if($display_type == 'info_php') //313
{
  DisplayPHPinfo();
}
else
if($display_type == 'info_mysql') //313
{
  DisplayMySQLInfo();
}
else
if($display_type == 'info_cache') //313
{
  if($action=='updatesettings')
  {
    UpdateSettings();
  }
  else
  {
    DisplayCacheInfo();
  }
}
else
if($display_type == 'modules') //343
{
  PrintModules();
}
else
if($display_type == 'syslog') //313
{
  // SysLog has to be used outside of any function!
  if(!@include(ROOT_PATH . ADMIN_PATH . '/syslog.php'))
  {
    DisplayMessage('Syslog file not found in '.ADMIN_PATH.' folder!', true);
  }
}
else
if(!$error)
{
  // Regular settings processing
  $function_name = str_replace('_', '', $action);

  if(is_callable($function_name))
  {
    call_user_func($function_name);
  }
  else
  {
    DisplayMessage("Incorrect Function Call: $function_name()", true);
  }
}

// ############################################################################
// DISPLAY ADMIN FOOTER
// ############################################################################

DisplayAdminFooter();
