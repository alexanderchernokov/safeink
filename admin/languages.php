<?php
// DEFINE CONSTANTS
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');
define('SELF_LANGUAGES', 'languages.php');

// INIT PRGM
include(ROOT_PATH . 'includes/init.php');

// LOAD ADMIN LANGUAGE
$admin_phrases = LoadAdminPhrases(7);

sd_header_add(array(
  'other' => array('
<script type="text/javascript">
// <![CDATA[
if(typeof(jQuery) !== "undefined"){
  jQuery(document).ready(function(){
    (function($){
	
$("#exportphrases").on("click", function(e) {
		e.preventDefault();
   bootbox.dialog({
      message: "' . AdminPhrase('settings_export_english') . '",
	  title: "' . AdminPhrase('settings_export_defaults') . '",
      buttons:
      {
         success:
         {
            label : "<i class=\"ace-icon fa fa-download\"></i> ' . AdminPhrase('download') .'!",
            className : "btn-sm btn-success",
            callback: function() {
                 window.location.href = "./exportlanguage.php?defaults=1&language=en_US&author='.PRGM_NAME.'&version='.$mainsettings['sdversion'] .SD_URL_TOKEN.'"
            }
         }
      }
   });
});


	$("#phrasetype").change(function() {
			var type = $("#phrasetype").val();
			
			if(type == 1)
			{
				$("#admingroup").show("slow");
			}
			else
			{
				$("#frontendgroup").show("slow");
				$("#admingroup").hide();
			}
	});
	
    }(jQuery));
  });
}
// ]]>
</script>
')));


// DISPLAY ADMIN HEADER
DisplayAdminHeader('Settings'/*, $admin_sub_menu_arr*/);

// CHECK PAGE ACCESS
CheckAdminAccess('languages');

$admin_pages_arr = array(-2 => 'Articles',
                         0 => AdminPhrase('settings_common_phrases'),
                         1 => 'Pages',
                         2 => 'Plugins',
                         3 => 'Media',
                         4 => 'Comments',
                         5 => 'Users',
                         6 => 'Skins',
                         7 => 'Settings');

// GET ACTION
$action = GetVar('action', 'display_website_language', 'string');
$function_name = str_replace('_', '', $action);

// DISPLAY SUB MENU
//SD360: sub-menu display similar to main menu
/*
echo '<ul id="submenu">
        <li><a href="'.SELF_LANGUAGES.'" class="menu_item">' . AdminPhrase('settings_website_language') . '</a></li>
        <li><a href="'.SELF_LANGUAGES.'?action=display_admin_language" class="menu_item">' . AdminPhrase('settings_admin_language') . '</a></li>
        <li><a href="'.SELF_LANGUAGES.'?action=display_switch_language" class="menu_item">' . AdminPhrase('settings_switch_language') . '</a></li>
        <li><a href="'.SELF_LANGUAGES.'?action=display_export_language" class="menu_item">' . AdminPhrase('settings_export_language') . '</a></li>
      </ul>
      <div class="clear"></div>';
*/
$sel_item = ' class="current_page"';
$sub1 = '';
if(in_array($action,array('display_website_language',
                          'editwebsitelanguage')))
{
  $sub1 = $sel_item;
}
$sub2 = '';
if(in_array($action,array('display_admin_language',
                          'editadminlanguage')))
{
  $sub2 = $sel_item;
}

echo '
		<div class="btn-group">
		<button data-toggle="dropdown" class="btn btn-primary dropdown-toggle">' . AdminPhrase('common_menu').'
			<span class="ace-icon fa fa-caret-down icon-on-right"></span>
		</button>';
		
		echo '
<ul class="dropdown-menu dropdown-info">
  <li><a href="'.SELF_LANGUAGES.'?action=new_phrase_form"><i class="ace-icon fa fa-plus"></i> ' . AdminPhrase('add_new_phrase') . '</a></li>
  <li><a href="'.SELF_LANGUAGES.'">' . AdminPhrase('settings_website_language') . '</a></li>
  <li><a href="'.SELF_LANGUAGES.'?action=display_admin_language">' . AdminPhrase('settings_admin_language') . '</a></li>
  <li><a href="'.SELF_LANGUAGES.'?action=display_switch_language">' . AdminPhrase('settings_switch_language') . '</a></li>
  <li><a href="'.SELF_LANGUAGES.'?action=display_export_language">' . AdminPhrase('settings_export_language') . '</a></li>
  <li><a href="#" id="exportphrases">'. AdminPhrase('settings_export_defaults') . '</a></li>
</ul>
</div>
<div class="space-10"></div>
';




// ############################################################################
// DISPLAY WARNING IF NOT USING UTF-8
// ############################################################################

if((strtolower($mainsettings['charset']) != 'utf-8') ||
   (strtolower(substr($mainsettings['db_charset'],0,3)) != 'utf') )
{
  DisplayMessage(AdminPhrase('message_not_utf8_charset'), true);
}
// Remove empty phrases
$DB->query("DELETE FROM {adminphrases} WHERE pluginid = 0 AND (trim(varname) = '' OR trim(defaultphrase) = '')");
$DB->query("DELETE FROM {phrases} WHERE pluginid = 0 AND (trim(varname) = '' OR trim(defaultphrase) = '')");
$DB->query("UPDATE {phrases} SET customphrase = '' WHERE customphrase IS NULL");
$DB->query("UPDATE {adminphrases} SET customphrase = '' WHERE customphrase IS NULL");


/**
* New Phrase Form
*
*/
function newphraseform()
{
	global $admin_pages_arr, $DB, $mainsettings, $sdlanguage, $userinfo;
	
	// Get Plugin Select Form
	$plugins_arr	=	GetPluginsSelect('',$userinfo['usergroupid']);
	
	//print_r($plugins_arr);
	
	echo '<h2 class="header blue lighter">' . AdminPhrase('insert_phrase') . '</h2>
	<form id="insertphrase" action="'.SELF_LANGUAGES.'" method="post" class="form-horizontal">
	<input type="hidden" name="action" value="add_phrase" />
  '.PrintSecureToken() . 
  '<div class="form-group">
  	<label class="control-label col-sm-3">' . AdminPhrase('phrase_type') . '</label>
	<div class="col-sm-6">
		<select name="phrasetype" id="phrasetype" class="form-control">
			<option value="1">' . AdminPhrase('admin_phrase') . '</option>
			<option value="2">' . AdminPhrase('frontend_phrase') . '</option>
		</select>
	</div>
</div>
<div class="form-group" id="frontendgroup">
  	<label class="control-label col-sm-3">' . AdminPhrase('plugin') . '</label>
	<div class="col-sm-6">
		<select name="pluginid" class="form-control">
			' . $plugins_arr . '
		</select>
		<span class="helper-text">' . AdminPhrase('plugin_hint') . '</span>
	</div>
</div>
  <div class="form-group" id="admingroup">
  	<label class="control-label col-sm-3">' . AdminPhrase('admin_phrase_section') . '</label>
	<div class="col-sm-6">
		<select name="phrasesection" class="form-control">';
		foreach($admin_pages_arr as $key => $value)
		{
			echo '<option value="'.$key.'">'. $value .'</option>';
		}
		echo '</select>
	</div>
</div>
<div class="form-group">
  	<label class="control-label col-sm-3">' . AdminPhrase('phrase_name') . '</label>
	<div class="col-sm-6">
		<input type="text" class="form-control" name="phrasename" value="" />
	</div>
</div>
<div class="form-group">
  	<label class="control-label col-sm-3">' . AdminPhrase('phrase_text') . '</label>
	<div class="col-sm-6">
		<textarea name="phrasetext" class="form-control"></textarea>
		<span class="helper-text">' . AdminPhrase('phrase_text_hint') . '</span>
	</div>
</div>
<div class="center">
	<button type="submit" class="btn btn-info"><i class="ace-icon fa fa-plus"></i>  ' . AdminPhrase('add_phrase') . '</button>
</div>
</form>';
}
	
	
/**
* 
* Insert Phrase
*
*/
function addphrase()
{
	global $DB;
	
	$phrasetype 	=	GetVar('phrasetype','','int');
	$phrasesection	=	GetVar('phrasesection','','int');
	$phrasename		=	GetVar('phrasename','','string');
	$phrasetext		=	GetVar('phrasetext','','html');
	$pluginid		=	GetVar('pluginid','0','int');
	
	if(!CheckFormToken())
  	{
    	DisplayMessage('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
    	return false;
  	}
	
	if(!strlen($phrasename) || !strlen($phrasetext))
	{
		DisplayMessage(AdminPhrase('err_all_fields_required'),true);
		newphraseform();
		return false;
	}
	
	if($phraseexists = $DB->query_first("SELECT * FROM " . ($phrasetype == 1 ? "{adminphrases}" : "{phrases}") . " WHERE varname='$phrasename' AND " . ($phrasetype == 1 ? "adminpageid=$phrasesection" : "pluginid=$pluginid")))
	{
		DisplayMessage(AdminPhrase('err_phrase_name_exists'),true);
		newphraseform();
		return false;
	}
	
	// Insert Phrase
	if($phrasetype == 1)
	{
		if(InsertAdminPhrase($pluginid, $phrasename, $phrasetext,$phrasesection, false))
		{
			 RedirectPage(SELF_LANGUAGES.'?action=editadminlanguage&adminpageid='.$phrasesection, AdminPhrase('phrase_inserted'));
			 return;
		}
			
	}
	else
	{
		if(InsertPhrase($pluginid, $phrasename, $phrasetext, false))
		{
			 RedirectPage(SELF_LANGUAGES.'?action=editwebsitelanguage&pluginid='.$pluginid, AdminPhrase('phrase_inserted'));
			 return;
		}
	}
	
	DisplayMessage(AdminPhrase('insert_phrase_error_occurred'),true);
	newphraseform();
	return false;
}
		
	

// ############################################################################
// SWITCH LANGUAGE
// ############################################################################

function SwitchLanguage()
{
  global $DB, $SDCache, $mainsettings, $sdlanguage;

  if(!CheckFormToken())
  {
    DisplayMessage('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
    return false;
  }

  $languagefile = GetVar('languagefile', '', 'string');

  if(strtolower($languagefile) == 'english.php')
  {
    $DB->query("UPDATE {phrases} SET customphrase = ''");
    $DB->query("UPDATE {adminphrases} SET customphrase = ''");

    $version = $mainsettings['sdversion'];
    $charset = 'utf-8';
  }
  else
  if(is_file('./languages/' . $languagefile))
  {
    //SD341: clear custom phrases here, too
    $DB->query("UPDATE {phrases} SET customphrase = ''");
    $DB->query("UPDATE {adminphrases} SET customphrase = ''");

    include('./languages/' . $languagefile);

    $version = isset($version) ? $version : '';
    $charset = isset($charset) ? strtolower($charset) : 'utf-8';

    if(isset($sdphrases))
    {
      foreach($sdphrases as $pluginid => $this_array)
      {
        if($pluginid = Is_Valid_Number($pluginid,0,1,999999))
        {
          // Source-system of translations may have plugin with a different ID
          // as the current SD install. So explicitely re-fetch the $pluginid
          // by it's name from the translation list - if present in file.
          if(($pluginid > 1) && isset($translated_plugins_arr) && is_array($translated_plugins_arr))
          {
            $pluginid = GetPluginID($translated_plugins_arr[$pluginid]);
          }
          if($pluginid)
          {
            foreach($this_array as $varname => $customphrase)
            {
              $DB->query("UPDATE {phrases} SET customphrase = '" . $DB->escape_string($customphrase) .
                         "' WHERE varname = '".$DB->escape_string($varname).
                         "' AND pluginid = ".$pluginid);
            }
          }
        }
      }
    }

    if(isset($phrases_admin_pages))
    {
      foreach($phrases_admin_pages as $adminpageid => $this_array)
      {
        if(($adminpageid = Is_Valid_Number($adminpageid,-1,0,999)) != -1)
        foreach($this_array as $varname => $customphrase)
        {
          $DB->query("UPDATE {adminphrases} SET customphrase = '" . $DB->escape_string($customphrase) .
                     "' WHERE varname = '".$DB->escape_string($varname).
                     "' AND adminpageid = ".$adminpageid);
        }
      }
    }

    if(isset($phrases_admin_plugins))
    {
      foreach($phrases_admin_plugins as $pluginid => $this_array)
      {
        if(($pluginid = Is_Valid_Number($pluginid,-1,0,999999)) != -1)
        foreach($this_array as $varname => $customphrase)
        {
          $DB->query("UPDATE {adminphrases} SET customphrase = '" . $DB->escape_string($customphrase) .
                     "' WHERE varname = '".$DB->escape_string($varname).
                     "' AND adminpageid = 2 AND pluginid = ".$pluginid);
        }
      }
    }
  }

  $languagearray = array($languagefile, $version, $charset);
  $languageinfo  = implode('|', $languagearray);

  $DB->query("UPDATE {mainsettings} SET value = '%s' WHERE varname = 'language'", $languageinfo);
  $DB->query("UPDATE {mainsettings} SET value = '%s' WHERE varname = 'charset'", $charset);

  // SD313: purge all cache files
  if(isset($SDCache))
  {
    $SDCache->purge_cache(true);
  }

  RedirectPage(SELF_LANGUAGES, AdminPhrase('settings_language_switched'));

} //SwitchLanguage


// ############################################################################
// DISPLAY ADMIN LANGUAGE
// ############################################################################

function DisplayAdminLanguage()
{
  global $DB, $mainsettings, $admin_pages_arr, $plugin_names, $sdlanguage,
         $core_pluginids_arr;

  $doSearch = GetVar('do_search', false, 'bool', true, false);
  $search_phrase = GetVar('search_phrase', '', 'string', true, false);
  $search_original = GetVar('search_original', false, 'bool', true, false);
  $search_translated = GetVar('search_translated', false, 'bool', true, false);
  $doSearch = $doSearch && !empty($search_phrase) && (strlen($search_phrase)>1) &&
              ($search_original || $search_translated);
  if($doSearch)
  {
    if(!CheckFormToken())
    {
      DisplayMessage('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
      return false;
    }
  }
  SearchLanguageForm('display_admin_language');

  //SD360: search feature
  $searchResult = false;
  $extra_sql_plugins = '';
  $extra_sql_pages = '';
  if($doSearch)
  {
    StartTable( AdminPhrase('lang_search_results').' "'.strip_alltags($search_phrase).'"', array('table', 'table-bordered', 'table-striped'));
  }
  else
  {
    // Display main menu items for translation first
    StartTable(PRGM_NAME.' Admin Menu', array('table', 'table-bordered', 'table-striped'));
  }

  echo '
  <form id="updadminmenu" action="'.SELF_LANGUAGES.'" method="post">
  '.PrintSecureToken();

  if(!$doSearch)
  {
    
	echo '
  <thead>
  <tr>
    <th class="td1" width="250">Menu Page</th>
    <th class="td1" width="100">Menu Name (HTML)</th>
    <th class="td1">&nbsp;</th>
  </tr>
  </thead>
  <tbody>';
    foreach($admin_pages_arr AS $admin_page_id => $admin_page_name)
    {
      if($admin_page_id != 0)
      {
        $menu_item_name = AdminPhrase('menu_'.strtolower($admin_page_name), true);
        if(empty($menu_item_name))
        {
          $menu_item_name = $admin_page_name;
        }
        echo '
    <tr>
      <td class="td2">'.$admin_page_name.'</td>
      <td class="td2"><input type="text" name="menu_names['.$admin_page_id.']" size="30" value="'.$menu_item_name.'" /></td>
      <td class="td2">&nbsp;</td>
    </tr>';
      }
    }
    echo '</tbody>
  </table>';
  
  PrintSubmit('update_admin_menu', AdminPhrase('settings_save_translations'), 'updadminmenu', 'fa-check');
  echo '</div>
  </form>
  <div class="space-20"></div>
  ';


    StartTable(AdminPhrase('settings_admin_language'), array('table', 'table-bordered', 'table-striped'));
  }

  $header = '
  <thead>
  <tr>
    <th class="td1" width="250" style="font-size:13px;padding:8px 4px 8px 4px">' . AdminPhrase('settings_admin_pages') . '</th>
    <th class="td1" width="100" style="font-size:13px;padding:8px 4px 8px 4px">' . AdminPhrase('settings_phrases') . '</th>
    <th class="td1" colspan="2" style="font-size:13px;padding:8px 4px 8px 4px">' . AdminPhrase('settings_phrases_edited') . '</th>
  </tr>
  </thead>
  <tbody>';

  if($doSearch)
  {
    $search = array();
    if($search_original)   $search[] = "(p.defaultphrase LIKE '".$search_phrase."')";
    if($search_translated) $search[] = "(p.customphrase LIKE '".$search_phrase."')";
  }

  foreach($admin_pages_arr AS $admin_page_id => $admin_page_name)
  {
    if($admin_page_id >= 0)
    {
      $menu_item_name = AdminPhrase('menu_'.strtolower($admin_page_name), true);
      if(empty($menu_item_name))
      {
        $menu_item_name = $admin_page_name;
      }

      $DB->result_type = MYSQL_ASSOC;
      $admin_phrase_rows = $DB->query_first('SELECT COUNT(*) total FROM '.PRGM_TABLE_PREFIX.'adminphrases p'.
                                            ' WHERE p.adminpageid = '.(int)$admin_page_id.
                                            ' AND p.pluginid < 1'.
                                            (empty($search)?'':" AND (".implode(' OR ',$search).")"));
      $admin_phrase_count = empty($admin_phrase_rows['total'])?0:(int)$admin_phrase_rows['total'];
      $DB->result_type = MYSQL_ASSOC;
      $admin_custom_rows = $DB->query_first('SELECT COUNT(*) total FROM '.PRGM_TABLE_PREFIX.'adminphrases p'.
                                            " WHERE IFNULL(p.customphrase,'') <> ''".
                                            ' AND p.adminpageid = '.(int)$admin_page_id.
                                            ' AND p.pluginid < 1'.
                                            (empty($search)?'':" AND (".implode(' OR ',$search).")"));
      $admin_custom_count = empty($admin_custom_rows['total'])?0:(int)$admin_custom_rows['total'];

      $color = $admin_custom_count >= $admin_phrase_count ? 'green' : 'red';
      if(!$doSearch || ($admin_custom_count || $admin_phrase_count))
      {
        if(strlen($header))
        {
          echo $header;
          $header = '';
        }
        echo '
    <tr>
      <td class="td2">
        <a href="'.SELF_LANGUAGES.'?action=editadminlanguage&amp;adminpageid='.$admin_page_id.
          '"><span class="sprite sprite-edit"></span>&nbsp;' . $menu_item_name . '</a></td>
      <td class="td2">' . $admin_phrase_count . '</td>
      <td class="td2" colspan="2"><span style="color: '.$color.'">
        ' . $admin_custom_count . '</span></td>
    </tr>';
      }
    }
  }

  // Get Main Plugins
  $core_pluginids = implode(',',$core_pluginids_arr);
  if($forum_id = GetPluginID('Forum'))
  {
    $core_pluginids .= ','.$forum_id;
  }

  $plugin_types_arr = array(AdminPhrase('common_main_plugins') => "(p.pluginid IN (".$core_pluginids."))",
                            AdminPhrase('common_downloaded_plugins') => "(p.authorname NOT LIKE 'subdreamer_cloner%') AND NOT (p.pluginid IN (".$core_pluginids."))",
                            AdminPhrase('common_cloned_plugins') => "(p.pluginid > 1000) AND (p.authorname LIKE 'subdreamer_cloner%')");

  if($doSearch)
  {
    $search = array();
    if($search_original)   $search[] = "(ph.defaultphrase LIKE '".$search_phrase."')";
    if($search_translated) $search[] = "(ph.customphrase LIKE '".$search_phrase."')";
    $extra_sql_plugins = ' AND EXISTS(SELECT 1 FROM '.PRGM_TABLE_PREFIX.'adminphrases ph'.
                                    ' WHERE ph.pluginid = p.pluginid'.
                                    " AND (".implode(' OR ',$search)."))";
  }

  foreach($plugin_types_arr AS $plugins_type => $extra_sql_query)
  {
    $title_row_printed = false;
    $get_plugins = $DB->query('SELECT p.pluginid, p.name'.
                              ' FROM {plugins} p'.
                              ' WHERE (p.pluginid > 1)'.
                              ' AND ' . $extra_sql_query .
                              (empty($search)?'':$extra_sql_plugins).
                              ' ORDER BY pluginid');
    if($DB->get_num_rows($get_plugins))
    {
      $plugins = array();
      while($plugin_arr = $DB->fetch_array($get_plugins,null,MYSQL_ASSOC))
      {
        $p = array();
        $p['name_org'] = $plugin_arr['name'];
        $p['name'] = isset($plugin_names[$plugin_arr['name']]) ? $plugin_names[$plugin_arr['name']] : $plugin_arr['name'];
        $p['pluginid'] = $plugin_arr['pluginid'];
        $p['phraserows'] = 0;
        if($tmp = $DB->query_first('SELECT COUNT(*) FROM {adminphrases} ph'.
                                   ' WHERE ph.pluginid = %d'.
                                   (empty($search)?'':" AND (".implode(' OR ',$search).")"),
                                   $plugin_arr['pluginid']))
        {
          $p['phraserows'] = $tmp[0];
        }
        $p['$customrows'] = 0;
        if($tmp = $DB->query_first('SELECT COUNT(*) FROM {adminphrases} ph'.
                                   ' WHERE (ph.pluginid = %d)'.
                                   " AND (IFNULL(ph.customphrase,'') <> '')".
                                   (empty($search)?'':" AND (".implode(' OR ',$search).")"),
                                   $plugin_arr['pluginid']))
        {
          $p['$customrows'] = $tmp[0];
        }
        $plugins[$p['name'].'-'.$p['pluginid']] = $p;
      } //while

      ksort($plugins);

      foreach($plugins as $p)
      {
        if(!$title_row_printed)
        {
          $title_row_printed = true;
          echo '
		  <thead>
        <tr>
          <th class="td1" width="250" style="font-size:13px;padding:8px 4px 8px 4px">' . $plugins_type . '</th>
          <th class="td1" width="100" style="font-size:13px;padding:8px 4px 8px 4px">' . AdminPhrase('settings_phrases') . '</th>
          <th class="td1" width="200" style="font-size:13px;padding:8px 4px 8px 4px">' . AdminPhrase('settings_phrases_edited') . '</th>
          <th class="td1" style="font-size:13px;padding:8px 4px 8px 4px">ID</td>
        </tr>
		</thead>';
        }
        $color = ($p['$customrows'] >= $p['phraserows']) ? 'green' : 'red';
        echo '
        <tr>
          <td class="td2">';
        if($p['phraserows'] > 0)
        {
          echo '<a href="'.SELF_LANGUAGES.'?action=editadminlanguage&amp;pluginid=' .
            $p['pluginid'].'"><span class="sprite sprite-edit"></span>&nbsp;' . $p['name'] . '</a>';
        }
        else
        {
          echo '<span class="sprite sprite-edit"></span>&nbsp;' . $p['name'];
        }
        echo '</td>
          <td class="td2">' . $p['phraserows'] . '</td>
          <td class="td2"><span style="color: '.$color.'">' . $p['$customrows'] . '</span></td>
          <td class="td2">'.$p['pluginid'].($p['name']!=$p['name_org']?' - '.$p['name_org']:'').'</td>
        </tr>';
      } //foreach
    }
  }
  echo '
      </table>
	  </div>';


} //DisplayAdminLanguage


// ############################################################################
// EDIT ADMIN LANGUAGE
// ############################################################################

function EditAdminLanguage()
{
  global $DB, $admin_pages_arr;

  $adminpageid = Is_Valid_Number(GetVar('adminpageid', 0, 'whole_number'),0,1,10);
  $pluginid    = Is_Valid_Number(GetVar('pluginid', 0, 'whole_number'),0,1,999999);

  if($pluginid)
  {
    $plugin_arr  = $DB->query_first('SELECT name FROM {plugins} WHERE pluginid = '.$pluginid);
    $section_name = $plugin_arr['name'];
    $getlanguage = $DB->query('SELECT * FROM {adminphrases} WHERE pluginid = %d ORDER BY varname',$pluginid);
  }
  else
  {
    $section_name = $admin_pages_arr[$adminpageid];
    $getlanguage = $DB->query('SELECT * FROM {adminphrases} WHERE adminpageid = %d'.
                              ' AND pluginid = 0 ORDER BY varname',$adminpageid);
  }

  if(!$DB->get_num_rows($getlanguage))
  {
    DisplayMessage('No phrases found.', true);
    return;
  }

  echo '
  <form action="'.SELF_LANGUAGES.'?'.
    ($pluginid ? 'pluginid='.$pluginid : 'adminpageid='.$adminpageid).
    '" id="wslang" method="post">';

  StartTable($section_name, array('table','table-bordered', 'table-striped'));
  echo '
  '.PrintSecureToken().'
 <thead>
  <tr>
    <th class="td1" width="45%">' . AdminPhrase('settings_default_phrase') . '</th>
    <th class="td1">' . AdminPhrase('settings_custom_phrase') . '</th>
  </tr>
  </thead>
  <tbody>';

  while($language = $DB->fetch_array($getlanguage,null,MYSQL_ASSOC))
  {
    echo '
  <tr>
    <td class="td2" valign="top">
      <input type="hidden" name="admin_phrase_id_arr[]" value="' . $language['adminphraseid'] . '" />
      <strong>'.htmlspecialchars($language['defaultphrase']).'</strong><br />
      <i>'.htmlspecialchars($language['varname']).'</i>
    </td>
    <td class="td3" valign="top">
      <input type="text" name="custom_phrase_arr[]" value="' . htmlspecialchars($language['customphrase']) . '" size="55" style="width: 95%;" />&nbsp;
    </td>
  </tr>';
  }

  echo '
  </tbody>
  </table>
  </div>';
 
  PrintSubmit('update_admin_language', AdminPhrase('settings_update_phrases'), 'wslang', 'fa-check');

  echo '
  </form>';

} //EditAdminLanguage


// ############################################################################
// UPDATE ADMIN LANGUAGE
// ############################################################################

function TranslateMainPlugins()
{
  global $DB, $SDCache, $sdlanguage;

  if(!CheckFormToken())
  {
    DisplayMessage('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
    return false;
  }

  $post_names = GetVar('plugin_name', array(), 'array');

  $DB->query("DELETE FROM {phrases} WHERE varname LIKE 'plugin_name_%'");
  foreach($post_names as $key => $value)
  {
    $value = unhtmlspecialchars($value);
    if(!empty($value))
    {
      InsertPhrase(1, 'plugin_name_'.$key, $value);
    }
  }

  if(isset($SDCache))
  {
    $SDCache->delete_cacheid('planguage_1');
  }

  RedirectPage(SELF_LANGUAGES, AdminPhrase('settings_translations_saved'));

} //TranslateMainPlugins


// ############################################################################
// UPDATE ADMIN MENU ITEMS
// ############################################################################

function UpdateAdminMenu()
{
  global $DB, $admin_pages_arr, $sdlanguage;

  if(!CheckFormToken())
  {
    DisplayMessage('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
    return false;
  }

  if(!empty($_POST['menu_names']) && is_array($_POST['menu_names']))
  {
    if($names = GetVar('menu_names', false, 'array', true, false))
    foreach($admin_pages_arr AS $admin_page_id => $admin_page_name)
    {
      if($admin_page_id != 0)
      {
        $value = !empty($names[$admin_page_id]) ? $names[$admin_page_id] : '';
        $value = unhtmlspecialchars($value);
        $menu_item_name = 'menu_'.strtolower($admin_page_name);
        if($menu_item_name !== 'menu_')
        {
          $DB->query("UPDATE {adminphrases} SET customphrase = '%s' WHERE varname = '%s'",
          $DB->escape_string($value), $menu_item_name);
        }
      }
    }
  }
  RedirectPage(SELF_LANGUAGES.'?action=display_admin_language', AdminPhrase('settings_translations_saved'));

} //UpdateAdminMenu


// ############################################################################
// UPDATE ADMIN LANGUAGE
// ############################################################################

function UpdateAdminLanguage()
{
  global $DB, $SDCache, $sdlanguage;

  if(!CheckFormToken())
  {
    DisplayMessage('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
    return false;
  }

  $admin_phrase_id_arr = GetVar('admin_phrase_id_arr', array(), 'array');
  $custom_phrase_arr = GetVar('custom_phrase_arr', array(), 'array');

  for($i = 0; $i < count($admin_phrase_id_arr); $i++)
  {
    $custom_phrase_arr[$i] = unhtmlspecialchars($custom_phrase_arr[$i]);
    $DB->query("UPDATE {adminphrases} SET customphrase = '" . $DB->escape_string($custom_phrase_arr[$i]) . "'
                WHERE adminphraseid = " . $admin_phrase_id_arr[$i]);
  }

  if(isset($SDCache))
  {
    $SDCache->purge_cache(true);
  }

  RedirectPage(SELF_LANGUAGES.'?action=display_admin_language', AdminPhrase('settings_language_updated'));

} //UpdateAdminLanguage


// ############################################################################
// SEARCH FORM
// ############################################################################

function SearchLanguageForm($target='display_website_language')
{
  global $DB, $core_pluginids_arr, $mainsettings, $plugin_names, $sdlanguage;

  $doSearch = GetVar('do_search', false, 'bool', true, false);
  $search_phrase = GetVar('search_phrase', '', 'string', true, false);
  $search_original = GetVar('search_original', false, 'bool', true, false);
  $search_translated = GetVar('search_translated', false, 'bool', true, false);
  $doSearch = $doSearch && !empty($search_phrase) && (strlen($search_phrase)>1) &&
              ($search_original || $search_translated);

  // Display form to offer search for original or translated items
  StartTable(AdminPhrase('search_title'), array('table', 'table-bordered'));
  echo '
  <form id="lang_search" action="'.SELF_LANGUAGES.'?action='.$target.'" method="post" class="form-horizontal">
  <input type="hidden" name="do_search" value="1" />
  '.PrintSecureToken().'
  <tr>
    <td class="td2">
		<div class="form-group">
			<label class="control-label col-sm-3">'.AdminPhrase('search_label').'</label>
			<div class="col-sm-6">
				<input type="text" id="search_phrase" class="form-control" name="search_phrase" value="'. ($doSearch?$search_phrase:'').'" /></label><br />
     			<input id="search_o" type="checkbox" class="ace" name="search_original" value="1"'.(!$doSearch || $search_original?' checked="checked"':'').' />
        		<span class="lbl"> '.AdminPhrase('search_original').'</span></label><br />
      			<input id="search_t" type="checkbox" class="ace" name="search_translated" value="1"'.(!$doSearch || $search_translated?' checked="checked"':'').' />
        		<span class="lbl"> '.AdminPhrase('search_translated').'</span>
    		</div>
		</div>
	</td>
  </tr>
  </table>
  </div>';
  
   PrintSubmit($target, AdminPhrase('search_button'), 'lang_search', 'fa-search');
  echo '
  </form>
  <div class="space-20"></div>';


?>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function(){
  jQuery("input#search_phrase").focus();
  jQuery("form#lang_search").submit(function(e){
    var error = false;
    var sx = $("input#search_phrase").val();
    var so = $("input#search_o").attr("checked");
    var st = $("input#search_t").attr("checked");
    if(sx==="") { error = "<?php echo addslashes(AdminPhrase('err_enter_search_phrase')); ?>"; }
    if(so!=="checked" && st!=="checked") { error = "<?php echo addslashes(AdminPhrase('err_check_search_option')); ?>"; }
    if(error !== false){
      e.preventDefault();
      alert(error);
      return false;
    }
    return true;
  });
});
//]]>
</script>
<?php

} //SearchLanguageForm


// ############################################################################
// DISPLAY WEBSITE LANGUAGE
// ############################################################################

function DisplayWebsiteLanguage()
{
  global $DB, $core_pluginids_arr, $mainsettings, $plugin_names, $sdlanguage;

  // Get Main Plugins
  $core_pluginids = implode(',',$core_pluginids_arr);
  if($forum_id = GetPluginID('Forum'))
  {
    $core_pluginids .= ','.$forum_id;
  }
  $prgm_phrase_rows = $DB->query_first("SELECT COUNT(*) AS total FROM {phrases} WHERE pluginid = 1 AND (varname NOT LIKE 'plugin_name_%')");
  $prgm_custom_rows = $DB->query_first("SELECT COUNT(*) AS total FROM {phrases} WHERE (customphrase <> '') AND pluginid = 1");

  $doSearch = GetVar('do_search', false, 'bool', true, false);
  $search_phrase = GetVar('search_phrase', '', 'string', true, false);
  $search_original = GetVar('search_original', false, 'bool', true, false);
  $search_translated = GetVar('search_translated', false, 'bool', true, false);
  $doSearch = $doSearch && !empty($search_phrase) && (strlen($search_phrase)>1) &&
              ($search_original || $search_translated);
  if($doSearch)
  {
    if(!CheckFormToken())
    {
      DisplayMessage('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
      return false;
    }
  }
  else
  {
    SearchLanguageForm();
  }

  //SD360: search feature
  $searchResult = false;
  if($doSearch)
  {
    $search = array();
    if($search_original)   $search[] = "(ph.defaultphrase LIKE '".$search_phrase."')";
    if($search_translated) $search[] = "(ph.customphrase LIKE '".$search_phrase."')";
    $rows = $DB->query_first('SELECT COUNT(*) pcount'.
                             ' FROM '.PRGM_TABLE_PREFIX.'phrases ph'.
                             ' WHERE ph.pluginid = 1'.
                             (!count($search)?'':" AND (".implode(' OR ',$search).')'));
    $searchResult = !empty($rows) && ($rows['pcount']>0);
    StartTable(AdminPhrase('lang_search_results').' "'.strip_alltags($search_phrase).'"', array('table', 'table-bordered','table-striped'));
  }
  else
  {
    StartTable(AdminPhrase('settings_website_language'), array('table','table-bordered','table-striped'));
  }
  $color = $prgm_custom_rows['total'] >= $prgm_phrase_rows['total'] ? 'green' : 'red';
  echo '
  <form action="'.SELF_LANGUAGES.'" id="languages" method="post" class="form-horizontal">
  '.PrintSecureToken();
  if(!$doSearch)
  {
    echo '
	<thead>
  <tr>
    <th class="td1" width="250">' . PRGM_NAME . '</th>
    <th class="td1" width="300"> </th>
    <th class="td1" width="100"> </th>
    <th class="td1"> </th>
  </tr>
  </thead>
  </tbody>
  <tr>
    <td class="td2"><a href="'.SELF_LANGUAGES.'?action=editwebsitelanguage&amp;pluginid=1">'.
      '<span class="sprite sprite-edit"></span>&nbsp;' . AdminPhrase('settings_main_phrases') . '</a></td>
    <td class="td2"> </td>
    <td class="td2">' . $prgm_phrase_rows['total'] . '</td>
    <td class="td2"><span style="color: '.$color.'">' . $prgm_custom_rows['total'] . '</span></td>
  </tr>
  ';
  }
  else
  {
    echo '
  <tr>
    <td class="td1" colspan="4">' . PRGM_NAME . '</td>
  </tr>';
  }

  $plugin_types_arr = array(
    array('id' => 0, 'phrase' => AdminPhrase('common_main_plugins'),       'sql' => "(p.pluginid IN ($core_pluginids))"),
    array('id' => 1, 'phrase' => AdminPhrase('common_downloaded_plugins'), 'sql' => "(p.authorname NOT LIKE 'subdreamer_cloner%') AND NOT (p.pluginid IN ($core_pluginids))"),
    array('id' => 2, 'phrase' => AdminPhrase('common_cloned_plugins'),     'sql' => "(p.authorname LIKE 'subdreamer_cloner%')") );

  foreach($plugin_types_arr AS $plugins_type)
  {
    $extra_sql_query = $plugins_type['sql'];

    //SD360: search feature
    if($doSearch)
    {
      $search = array();
      if($search_original)   $search[] = "(ph.defaultphrase LIKE '".$search_phrase."')";
      if($search_translated) $search[] = "(ph.customphrase LIKE '".$search_phrase."')";
      $extra_sql_query .= ' AND EXISTS(SELECT 1 FROM '.PRGM_TABLE_PREFIX.'phrases ph'.
                                     ' WHERE ph.pluginid = p.pluginid'.
                                     " AND (".implode(' OR ',$search)."))";
    }

    $get_plugins = $DB->query('SELECT pluginid, name FROM {plugins} p'.
                              ' WHERE '.
                              (empty($search)?'(p.pluginid > 1)':'1=1').
                              (empty($extra_sql_query)?'':' AND ' . $extra_sql_query).
                              ' ORDER BY p.name');
    if($plugins_count = $DB->get_num_rows($get_plugins))
    {
      $searchResult = true;
      echo '
     <thead>
      <tr>
        <th class="td1" width="250" style="font-size:13px;padding:8px 4px 8px 4px">
          '.$plugins_type['phrase']. '
        </th>
        ';
      if($doSearch)
      {
        echo '
        <th class="td1" colspan="3" style="font-size:13px;padding:8px 4px 8px 4px">'.
          AdminPhrase('settings_default_phrase').' / '.
          AdminPhrase('settings_phrases').' (HTML)</th>';
      }
      else
      {
        echo '
        <th class="td1" width="200" style="font-size:13px;padding:8px 4px 8px 4px">'.
          ($plugins_type['id'] == 0 ? AdminPhrase('settings_edit_plugin_names') : '').'(HTML)</th>
        <th class="td1" width="100">' . AdminPhrase('settings_phrases') . '</th>
        <th class="td1">' . AdminPhrase('settings_phrases_edited') . '</th>';
      }
      echo '
      </tr>
	  </thead>';

      while($plugin_arr = $DB->fetch_array($get_plugins,null,MYSQL_ASSOC))
      {
        $pid = $plugin_arr['pluginid'];
        $DB->result_type = MYSQL_ASSOC;
        if($doSearch)
        {
          $phraserows = $DB->query('SELECT * FROM {phrases} ph WHERE pluginid = '.(int)$pid.
                                   (empty($search)?'':" AND (".implode(' OR ',$search).")"));
          if($phraserows_count = $DB->get_num_rows($phraserows))
          {
            echo '
            <tr>
              <td class="td3" valign="middle" style="height:20px">
                <a href="'.SELF_LANGUAGES.'?action=editwebsitelanguage&amp;pluginid='.$pid.'">'.
                  '<span class="sprite sprite-edit"></span>' .
                  ($plugin_arr['pluginid']==1?AdminPhrase('settings_main_phrases'):$plugin_arr['name']).
                  '</a>
              </td>
              <td class="td3" colspan="3" valign="middle">Phrases: '.$phraserows_count .'</td>
            </tr>';

            while($row = $DB->fetch_array($phraserows,null,MYSQL_ASSOC))
            {
              echo '
              <tr>
                <td class="td2">'.$row['varname'].'</td>
                <td class="td2" colspan="3">
                  &nbsp;<b>'.htmlspecialchars($row['defaultphrase']).'</b><br />
                  <input type="text" name="phrases['.$row['phraseid'].']" size="30" style="width:97%" value="'.
                  htmlspecialchars($row['customphrase'],ENT_COMPAT).'" />
                </td>
              </tr>';
            } //while
          }
        }
        else
        {
          $phraserows = $DB->query_first("SELECT COUNT(*) pcount FROM {phrases}
                                         WHERE pluginid = %d", $plugin_arr['pluginid']);
          $phraserows_count = empty($phraserows['pcount'])?0:$phraserows['pcount'];
          $customrows = $DB->query_first("SELECT COUNT(*) pcount FROM {phrases}
                                         WHERE (IFNULL(customphrase,'') <> '') AND (pluginid = %d)", $plugin_arr['pluginid']);
          $customrows_count = empty($customrows['pcount'])?0:$customrows['pcount'];
          echo '
          <tr>
            <td class="td2">';
          if(!empty($phraserows_count))
          {
            echo '<a href="'.SELF_LANGUAGES.'?action=editwebsitelanguage&amp;pluginid='.
                 $plugin_arr['pluginid'].'">'.
                 '<span class="sprite sprite-edit"></span>&nbsp;' . $plugin_arr['name'] . '</a>';
          }
          else
          {
            echo '<span class="sprite sprite-edit"></span>&nbsp;' . $plugin_arr['name'];
          }
          echo '</td>
            <td class="td3">';

          //SD322: translatable plugin names
          echo '<input type="text" name="plugin_name['.$plugin_arr['pluginid'].']" size="30" style="width:97%" value="'.
               $plugin_names[$plugin_arr['pluginid']].'" />';
          if(empty($phraserows_count))
          {
            echo '</td><td class="td2" colspan="2">-</td></tr>';
          }
          else
          {
            $color = $customrows_count >= $phraserows_count ? 'green' : 'red';
            echo ' </td>
            <td class="td2">'.$phraserows_count.'</td>
            <td class="td2"><span style="color: '.$color.'">'.$customrows_count.'</span></td>
          </tr>';
          }
        }
      } //while
    }
  } //foreach

  if($doSearch && !$searchResult)
  {
    echo '
    <tr>
      <td class="td2" colspan="4">
      <center><strong>'.AdminPhrase('lang_no_search_results').'</strong></center>
      </td>
    </tr>';
  }
  

  echo '
  </tbody>
  </table>';
  
   PrintSubmit(($doSearch?'updatesearchresults':'translatemainplugins'),
                AdminPhrase('settings_save_translations'), 'languages', 'ok-sign');
				
echo'
  </div>
  </form>
  ';


} //DisplayWebsiteLanguage


// ############################################################################
// EDIT WEBSITE LANGUAGE
// ############################################################################

function EditWebsiteLanguage()
{
  global $DB, $plugin_names;

  $pluginid    = GetVar('pluginid', 0, 'whole_number');
  $plugin      = $DB->query_first('SELECT name FROM {plugins} WHERE pluginid = %d',$pluginid);
  $extra = '';
  if($pluginid == 1)
  {
    $extra = " AND NOT(varname LIKE 'plugin_name_%')";
  }
  $getlanguage = $DB->query('SELECT * FROM {phrases} WHERE pluginid = %d '.$extra.' ORDER BY varname',$pluginid);

  if(!$DB->get_num_rows($getlanguage))
  {
    DisplayMessage('No phrases found.', true);
    return;
  }

  echo '
  <form id="wslang" action="'.SELF_LANGUAGES.'?pluginid='.$pluginid.'" method="post">
  '.PrintSecureToken();

  if(($pluginid != 1) && isset($plugin_names[$plugin['name']]))
  {
    $plugin['name'] = $plugin_names[$plugin['name']];
  }
  StartTable($pluginid == 1 ? PRGM_NAME : $plugin['name'], array('table', 'table-bordered', 'table-striped'));
  echo '
	<thead>
    <tr>
      <th class="td1" width="45%">' . AdminPhrase('settings_default_phrase') . '</th>
      <th class="td1">' . AdminPhrase('settings_custom_phrase') . '</th>
    </tr>
	</thead>
	<tbody>';

  while($language = $DB->fetch_array($getlanguage,null,MYSQL_ASSOC))
  {
    echo '
    <tr>
      <td class="td2" valign="top">
        <input type="hidden" name="phraseid[]" value="' . $language['phraseid'] . '" />
        <b>' . htmlspecialchars($language['defaultphrase']) . '</b><br />
        <i>'.htmlspecialchars($language['varname']).'</i>
      </td>
      <td class="td3" valign="top">
        <input type="text" name="customphrase[]" value="' .
        htmlspecialchars($language['customphrase']) . '" size="55" style="width:98%" />&nbsp;
      </td>
    </tr>';
  }
 

  echo '
  </td>
  </tr>
  </table>';
	PrintSubmit('updatewebsitelanguage', AdminPhrase('settings_update_phrases'), 'wslang', 'fa-check');
  echo '
  </div>
  </form>
  <div class="space-20"></div>';

} //EditWebsiteLanguage


// ############################################################################
// UPDATE WEBSITE LANGUAGE
// ############################################################################

function UpdateWebsiteLanguage()
{
  global $DB, $SDCache;

  if(!CheckFormToken())
  {
    DisplayMessage('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
    return false;
  }

  $phrase_id_arr     = GetVar('phraseid', array(), 'array');
  $custom_phrase_arr = GetVar('customphrase', array(), 'array');

  for($i = 0; $i < count($phrase_id_arr); $i++)
  {
    $custom_phrase_arr[$i] = unhtmlspecialchars($custom_phrase_arr[$i]);
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'phrases'.
               " SET customphrase = '%s'".
               ' WHERE phraseid = %d',
                $DB->escape_string($custom_phrase_arr[$i]),
                $phrase_id_arr[$i]);
  }

  // SD313: purge all cache files
  if(isset($SDCache))
  {
    $SDCache->purge_cache(true);
  }

  RedirectPage(SELF_LANGUAGES.'?action=display_website_language',
               AdminPhrase('website_language_updated'));

} //UpdateWebsiteLanguage


// ############################################################################
// UPDATE SEARCH RESULTS
// ############################################################################

function UpdateSearchResults()
{
  global $DB, $SDCache, $sdlanguage;

  if(!CheckFormToken())
  {
    DisplayMessage('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
    return false;
  }
  $phrase_id_arr = GetVar('phrases', array(), 'array');

  foreach($phrase_id_arr as $id => $custom_phrase)
  {
    $id = Is_Valid_Number($id,0,1,9999999);
    $err = empty($id);
    $DB->result_type = MYSQL_ASSOC;
    $err |= !($p = $DB->query_first('SELECT 1 FROM '.PRGM_TABLE_PREFIX.'phrases'.
                                    ' WHERE phraseid = '.$id));
    if($err)
    {
      DisplayMessage('<strong>'.$sdlanguage['err_invalid_operation'].'</strong><br />',true);
      return false;
    }
    $custom_phrase = unhtmlspecialchars($custom_phrase);
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'phrases'.
               " SET customphrase = '%s'".
               ' WHERE phraseid = %d',
                $DB->escape_string($custom_phrase), $id);
  }

  // SD313: purge all cache files
  if(isset($SDCache))
  {
    $SDCache->purge_cache(true);
  }

  RedirectPage(SELF_LANGUAGES.'?action=display_website_language',
               AdminPhrase('website_language_updated'));

} //UpdateWebsiteLanguage


// ############################################################################
// DISPLAY SWITCH LANGUAGE
// ############################################################################

function DisplaySwitchLanguage()
{
  global $DB;

  echo '
  <form action="'.SELF_LANGUAGES.'?action=switch_language" method="post" class="form-horizontal">
  '.PrintSecureToken();

  echo '<h3 class="header blue lighter">' . AdminPhrase('settings_switch_language'). '</h3>';
  echo '
  <div class="form-group">
  	<label class="control-label col-sm-2" for="languagefile">' . AdminPhrase('settings_select_language') . '</label>
	<div class="col-sm-6">
      <select name="languagefile" class="form-control">
        <option value="English.php">English</option>';

  // find language files
  if(false !== ($d = @dir('./languages/')))
  {
    while($entry = $d->read())
    {
      if($entry != "." && $entry != ".." && (substr($entry, -4) == '.php'))
      {
        echo '
        <option value="' . $entry . '">' .
        str_replace('_', ' ', substr($entry, 0, -4)) . '</option>';
      }
    } //while
  }

  echo '
      </select>
	  <span class="helper-text">' . AdminPhrase('settings_select_language_descr') . '</span>
	</div>
</div>';

  echo '
  <div class="center">
  	<button class="btn btn-info" type="submit"/><i class="ace-icon fa fa-check"></i>'. AdminPhrase('settings_switch_language').'</button>
  </form>';

} //DisplaySwitchLanguage


// ############################################################################
// DISPLAY EXPORT LANGUAGE
// ############################################################################

function DisplayExportLanguage()
{
  global $DB, $userinfo, $mainsettings;

  $languageinfo = explode('|', $mainsettings['language']);

  if( (strtolower($mainsettings['charset']) == 'utf-8') || (strtolower($mainsettings['db_charset']) == 'utf8') /* SD342 utf8*/ ||
      (strtolower($mainsettings['db_charset']) == 'utf-8') )
  {
    //SD343: added form token
    echo '
    <form action="./exportlanguage.php" method="post" class="form-horizontal">
    '.PrintSecureToken();

    echo '<h3 class="header blue lighter">' . AdminPhrase('settings_export_language') . '</h3>';
    echo '
    <div class="form-group">
		<label class="control-label col-sm-2" for="language">' . AdminPhrase('settings_language_name') . '</label>
		<div class="col-sm-6">
        	<input type="text" name="language" class="form-control" value="' . substr($languageinfo[0], 0, -4) . '" />
			<span class="helper-text">' . AdminPhrase('settings_language_name_descr') . '</span>
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-sm-2" for="version">' . AdminPhrase('settings_language_version') . '</label>
		<div class="col-sm-6">
        	<input type="text" name="version" class="form-control" value="' .(strlen($languageinfo[1]) ? $languageinfo[1] : $mainsettings['sdversion']) . '" />
			<span class="helper-text">' . AdminPhrase('settings_language_version_descr') . '</span>
		</div>
	</div>
  	<div class="form-group">
		<label class="control-label col-sm-2" for="author">' . AdminPhrase('settings_language_author') . '</label>
		<div class="col-sm-6">
        	<input type="text" name="author" class="form-control" value="' . $userinfo['username'] . '" />
			<span class="helper-text">' . AdminPhrase('settings_language_author_descr') . '</span>
		</div>
	</div>
	<div class="center">
    <button class="btn btn-info" type="submit" /><i class="ace-icon fa fa-check"></i> ' . AdminPhrase('settings_export_language') . '</button>
    </div>
	</form>
    ';
  }

} //DisplayExportLanguage


// ############################################################################
// SELECT FUNCTION
// ############################################################################

if(is_callable($function_name))
{
  call_user_func($function_name);
}
else
{
  DisplayMessage("Incorrect Function Call: $function_name()", true);
}


// ############################################################################
// DISPLAY ADMIN FOOTER
// ############################################################################

DisplayAdminFooter();
