<?php

// DEFINE CONSTANTS
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');
define('COOKIE_HL_THEME','_skins_hl_theme');
define('SELF_SKINS_PHP','skins.php');

// INIT PRGM
require(ROOT_PATH.'includes/init.php');
require(ROOT_PATH.'includes/enablegzip.php');

// LOAD ADMIN LANGUAGE
$admin_phrases = LoadAdminPhrases(6);
$gzip_support  = function_exists('gzopen');
$zip_support   = ( extension_loaded('zip') &&
                   class_exists('ZipArchive') &&
                   function_exists('file_get_contents') );

// GET PARAMS
$action        = GetVar('action', 'display_skin', 'string');
$cssid         = GetVar('cssid', null, 'natural_number'); //SD342
$designid      = GetVar('designid', null, 'natural_number');
$layout_number = Is_Valid_Number(GetVar('layout_number', 0, 'whole_number'),0,1);
$skinid        = Is_Valid_Number(GetVar('skinid', 0, 'whole_number'),0,0);
$token         = GetVar(SD_TOKEN_NAME, '', 'string');
$structure     = GetVar('structure', 'header', 'string');
$css_var_name  = GetVar('css_var_name', '', 'string');
$isAjax        = Is_Ajax_Request();
$fallback      = empty($mainsettings['skins_enable_highlighting']);

// CHECK FOR SUPPORTED AJAX REQUEST:
if($isAjax)
{
  $actions_arr = array('css_switch','delete_layout','getlayouts','insert_layout'); //SD342: css_switch
  // Final check, including admin permissions:
  if(!in_array($action, $actions_arr) || empty($skinid) || ($skinid < 1) ||
     !CheckFormToken('', false) ||
     empty($userinfo['adminaccess']) &&
     (empty($userinfo['admin_pages']) ||
     !in_array('skins',$userinfo['admin_pages'])))
  {
    die('Permission denied!');
  }

  switch($action){
    case 'css_switch': //SD342: set CSS disabled flag
      if(!empty($cssid))
      {
        $disabled = GetVar('disabled', null, 'natural_number');
        $DB->query('UPDATE {skin_css} SET disabled = %d WHERE skin_css_id = %d',(1-$disabled),$cssid);
        if(isset($SDCache) && ($SDCache instanceof SDCache))
        {
          $SDCache->delete_cacheid('all_skin_css');
        }
      }
      else die('Permission denied!');
      break;
    case 'delete_layout' : DeleteLayout(); break;
    case 'insert_layout' : InsertLayout(); break;
    case 'getlayouts' :
      $where = $skinid ? 'skinid = '.(int)$skinid : 'activated = 1';
      $skin_arr = $DB->query_first('SELECT * FROM {skins} WHERE '.$where);
      GetLayouts($skin_arr, $designid);
      break;
  }
  exit();
}

if(!$isAjax)
{
  if($action != 'delete_layout')
  {
    if($action == 'display_skin')
    {
		sd_header_add(array(
        'other' => array(
        '<link rel="stylesheet" type="text/css" href="'.$sdurl.ADMIN_STYLES_FOLDER.'assets/css/skins.css.php" />'))
      );
		
    }

   sd_header_add(array(
      'other' => array('
<script type="text/javascript">
//<![CDATA[
  var skins_options = {
    cookie_name: "'.COOKIE_PREFIX.COOKIE_HL_THEME.'",
    fallback: '.($fallback?'true':'(jQuery.browser.msie && (parseInt(jQuery.browser.version, 10) < 9))').',
    includes_path: "'.SD_INCLUDE_PATH.'",
    skins_token_name: "'.htmlspecialchars(SD_TOKEN_NAME).'",
    skins_token: "'.htmlspecialchars(SD_FORM_TOKEN).'",
    lang_skins_delete_layout: "' . htmlspecialchars(AdminPhrase('skins_delete_layout'),ENT_COMPAT) . '",
    lang_skins_skin_updated: "'.htmlspecialchars(AdminPhrase('skins_skin_updated')).'",
    lang_skins_confirm_new_layout: "' . htmlspecialchars(AdminPhrase('skins_confirm_new_layout')) . '",
    lang_editor_hotkeys: "' . htmlspecialchars(AdminPhrase('skins_editor_hotkeys')) . '",
    lang_abandon_changes: "' . addslashes(AdminPhrase('skins_abandon_changes')) . '"
  };
  var structure = "layout", indicator = false, skinTable, itemsDiv;
  var editor_top, editor_bottom, ed_id = "#skineditor";
  var content_changed = false;
  if(skins_options.fallback) { ed_id = "#skincontent"; }
  var editor = false; /* for ACE editor! */
//]]>
</script>')));
  
    // Load ACE editor
    if($action == 'display_skin')
    {
      $prefix = $sdurl . (defined('ENABLE_MINIFY') && ENABLE_MINIFY ? MINIFY_PREFIX_F : '');
      if(!$fallback)
      {
		 
        sd_header_add(array(
          'other' => array('
			<script type="text/javascript" src="'.$prefix.'includes/javascript/ace/ace.js" charset="utf-8"></script>
			<script type="text/javascript" src="'.$prefix.'includes/javascript/ace/mode-html.js" charset="utf-8"></script>
			<script type="text/javascript" src="'.$prefix.'includes/javascript/ace/mode-html_completions.js" charset="utf-8"></script>
			<script type="text/javascript" src="'.$prefix.'includes/javascript/ace/mode-css.js" charset="utf-8"></script>
			<script type="text/javascript" src="'.$prefix.'includes/javascript/ace/mode-javascript.js" charset="utf-8"></script>
			<script type="text/javascript" src="'.$prefix.'includes/javascript/ace/mode-php.js" charset="utf-8"></script>
			<script type="text/javascript" src="'.$prefix.'includes/javascript/ace/theme-textmate.js" charset="utf-8"></script>
			<script type="text/javascript" src="'.$prefix.'includes/javascript/ace/ext-keybinding_menu.js" charset="utf-8"></script>
			<script type="text/javascript" src="'.$prefix.'includes/javascript/ace/ext-whitespace.js" charset="utf-8"></script>
			<script type="text/javascript" src="'.$prefix.'includes/javascript/ace/ext-statusbar.js" charset="utf-8"></script>
			<script type="text/javascript" src="'.$prefix.'includes/javascript/ace/ext-language_tools.js" charset="utf-8"></script>')));
	   
      }
    }
	

    if($action != 'delete_layout')
    {
	   sd_header_add(array(
        'other' => array('<script type="text/javascript" src="javascript/page_skins.js"></script>')));
    }
  }
} //!Ajax

// CHECK PAGE ACCESS
CheckAdminAccess('skins');

// DISPLAY ADMIN HEADER
$load_wysiwyg = 0;
DisplayAdminHeader(array('Skins', $action));


// ############################################################################
// INSTALL SKIN
// ############################################################################
// install old skin
function InstallSkin()
{
  global $DB, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_SKINS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  $installpath = GetVar('installpath', '', 'string');

  if(preg_match('#^(http|ftp)#',$installpath) || #SD370
     !file_exists($installpath) || !is_file($installpath) || !@include($installpath))
  {
    RedirectPage(SELF_SKINS_PHP.'?action=display_skins', AdminPhrase('skins_skin_install_error'),2,true);
    return false;
  }

  if(is_numeric($authorlink))
  {
    $authorlink = '';
  }

  // install the skin
  //SD370: fill folder_name from first folder in $previewimage
  $folder_name = @explode('/',str_replace('\\','/',$previewimage));
  if(is_array($folder_name) && count($folder_name))
    $folder_name = $folder_name[0];
  else
    $folder_name = '';
  $DB->query("INSERT INTO {skins} (skinid, name, skin_engine, activated, numdesigns,
              folder_name, previewimage, authorname, authorlink, header, footer, error_page,
              menu_level0_opening, menu_level0_closing, menu_submenu_opening, menu_submenu_closing,
              menu_item_opening, menu_item_closing, menu_item_link,
              mobile_menu_level0_opening, mobile_menu_level0_closing,
              mobile_menu_submenu_opening, mobile_menu_submenu_closing,
              mobile_menu_item_opening, mobile_menu_item_closing, mobile_menu_item_link)
              VALUES (NULL, '%s', 1, 0, %d, '%s', '%s', '%s', '%s', '', '', '',
              '<ul class=\"sf-menu\">', '</ul>', '<ul>', '</ul>',
              '<li>', '</li>', '<a href=\"[LINK]\" [TARGET]>[NAME]</a>',
              '', '', '', '', '', '', '')",
              $DB->escape_string(strip_tags($skinname)),(int)$numdesigns,
              $DB->escape_string(strip_tags($folder_name)),
              $DB->escape_string(strip_tags($previewimage)),
              $DB->escape_string(strip_tags($authorname)), $authorlink);

  // get the skin's id that was given to the template
  if($skinid = $DB->insert_id())
  {
    // install the skins designs
    if(!empty($numdesigns))
    {
      for($i = 0; $i < $numdesigns; $i++)
      {
        $DB->query("INSERT INTO {designs} (designid, skinid, maxplugins, designpath, imagepath, layout, design_name)
                    VALUES (NULL,%d,%d,'%s','%s','','Layout ".($i+1)."')",
                    $skinid, (int)$maxplugins[$i],
                    $DB->escape_string(strip_tags($designpath[$i])),
                    $DB->escape_string(strip_tags($imagepath[$i])));
      }
    }
  }
  RedirectPage(SELF_SKINS_PHP.'?action=display_skins', AdminPhrase('skins_skin_installed'));

} //InstallSkin


// ############################################################################
// UNINSTALL SKIN
// ############################################################################

function UninstallSkin()
{
  //SD370: added confirmation page with remove folder option (and sub-folders)
  global $DB, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_SKINS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  $confirmdelete = GetVar('confirmdelete', 0, 'string');
  $skinid = Is_Valid_Number(GetVar('skinid', 0, 'whole_number'),0,1,999999);

  if(($confirmdelete === AdminPhrase('common_no')) || empty($skinid))
  {
    $msg = AdminPhrase('skins_uninstall_cancelled');
    RedirectPage(SELF_SKINS_PHP.'?action=display_skins',$msg,2);
  }
  else
  if(empty($confirmdelete))
  {
    // get the skin's data
    $DB->result_type = MYSQL_ASSOC;
    if($skin = $DB->query_first('SELECT name, authorname, authorlink, folder_name, numdesigns'.
                                ' FROM {skins} WHERE skinid = %d', $skinid))
    {
      $description = '
       <span style="font-size:16px;font-weight:bold">'.AdminPhrase('skins_details').': '.$skin['name'].'</span>
       <br /><br />'.
       (!empty($skin['authorname']) ? AdminPhrase('skins_author_name').' <b>'.$skin['authorname'].'</b> / ' : '').
       (!empty($skin['authorlink']) ? ' <b><a href="'.$skin['authorlink'].'" target="_blank">'.$skin['authorlink'].'</a></b>' : ' ').
       '<br /><br /><span class="sprite sprite-folder"></span><b>skins/'.$skin['folder_name'].'</b> / '.
       AdminPhrase('skins_layout_hint').' <b>'.$skin['numdesigns'].'</b>
       <br /><br /><label for="removeskinfolder"><input id="removeskinfolder" name="removeskinfolder" type="checkbox" value="1" /> '.AdminPhrase('skins_uninstall_removefolder').'</label>
       <br /><br /><center><b>'.AdminPhrase('skins_confirm_uninstall').'</b></center>';

      $hiddenvalues = '<input type="hidden" name="skinid" value="'.$skinid.'" />
                       <input type="hidden" name="action" value="uninstall_skin" />
                       ';

      // arguments: description, hidden input values, form redirect page
      ConfirmDelete($description, $hiddenvalues, SELF_SKINS_PHP, 'bolt', 'remove');
      return;
    }

    $msg = AdminPhrase('skins_uninstall_cancelled');
    RedirectPage(SELF_SKINS_PHP.'?action=display_skins',$msg,2,true);
  }
  else if($confirmdelete == AdminPhrase('common_yes'))
  {
    // make sure not to delete currently active skin
    $DB->result_type = MYSQL_ASSOC;
    if($skinid = Is_Valid_Number(GetVar('skinid', 0, 'whole_number'),0,1,999999))
    {
      $DB->result_type = MYSQL_ASSOC;
      $skin = $DB->query_first('SELECT skinid, activated, folder_name FROM {skins}'.
                               ' WHERE skinid = %d', $skinid);
    }

    if(!empty($skin['skinid']) && empty($skin['activated']))
    {
      //SD370: option to remove skin folder (incl. sub-folders):
      if(GetVar('removeskinfolder', false, 'bool'))
      {
        sd_delete_recursively(SD_DOCROOT.'skins'.DIRECTORY_SEPARATOR.$skin['folder_name'].DIRECTORY_SEPARATOR);
      }

      $DB->query('DELETE FROM {skins} WHERE skinid = %d', $skinid);
      $DB->query('DELETE FROM {designs} WHERE skinid = %d', $skinid);
      $DB->query('DELETE FROM {skin_css} WHERE skin_id = %d', $skinid);

      RedirectPage(SELF_SKINS_PHP.'?action=display_skins',
                   AdminPhrase('skins_skin_uninstalled'));
      return true;
    }

    if(!empty($skin['activated']))
      $msg = AdminPhrase('skins_err_delete_active_skin');
    else
      $msg = AdminPhrase('skins_err_skin_not_found');

    RedirectPage(SELF_SKINS_PHP.'?action=display_skins',$msg,2,true);
  }

} //UninstallSkin


// ############################################################################
// DISPLAY CREATE SKIN
// ############################################################################

function DisplayCreateSkin($errors=null)
{
  global $DB, $userinfo;

  if(!empty($errors) && is_array($errors))
  {
    DisplayMessage($errors, true);
  }

  $skin_name   = trim(GetVar('skin_name', 'Untitled', 'string',true,false));
  $authorname  = trim(GetVar('authorname', $userinfo['username'], 'string',true,false));
  $authorlink  = trim(GetVar('authorlink', '', 'string',true,false));
  $folder_name = trim(GetVar('folder_name', '', 'string',true,false));

  $create_folder = GetVar('create_folder', 1, 'string',false,true)?1:0;
  $copy_files    = GetVar('copy_files', 1, 'string',false,true)?1:0;
  $copy_layouts  = GetVar('copy_layouts', 1, 'string',false,true)?1:0;
  $copy_css      = GetVar('copy_css', 1, 'string',false,true)?1:0;

  echo '
  <form method="post" action="skins.php?action=insert_skin" class="form-horizontal">
  '.PrintSecureToken();

 echo '<h3 class="header blue lighter">' . AdminPhrase('skins_create_skin') . '</h3>';
  echo '
  <div class="form-group">
  	<label class="control-label col-sm-2">' . AdminPhrase('skins_skin_name') . '</label>
	<div class="col-sm-6">
    	<input type="text" name="skin_name" class="form-control" value="'.$skin_name.'" size="45" /></div>
</div>
   <div class="form-group">
  	<label class="control-label col-sm-2">' . AdminPhrase('skins_author_name') . '</label>
	<div class="col-sm-6"><input type="text" class="form-control" name="authorname" value="'.$authorname.'" size="45" /></div>
</div>
   <div class="form-group">
  	<label class="control-label col-sm-2">' . AdminPhrase('skins_author_link') . '</label>
	<div class="col-sm-6"><input type="text" class="form-control" name="authorlink" value="'.$authorlink.'" size="45" /></div>
</div>
   <div class="form-group">
  	<label class="control-label col-sm-2">' . AdminPhrase('skins_skin_folder') . '</label>
	<div class="col-sm-6"><input type="text" class="form-control" name="folder_name" value="'.$folder_name.'" size="37" />
	<span class="helper-text"><strong>skins/<i>&lt;skin-name&gt;</i>/</strong></span>
	</div>
</div>';

  //SD360: enable copying skin contents
  if($get_skins = $DB->query('SELECT skinid, name FROM {skins}'.
                             ' WHERE skin_engine = 2 ORDER BY name ASC'))
  {
    /*
    if(function_exists('posix_getpwuid') &&
       function_exists('posix_geteuid'))
    {
      $euid = posix_geteuid();
      $pwuid = posix_getpwuid($euid);
      #echo "<code><br />\$euid: $euid<br />\$pwuid: ".print_r($pwuid).'</code><br />';
      if($pwuid['uid']!==0) echo '<br />NOTE: copied files may by inaccessible by FTP due to system permissions!<br />';
    }
    */
    $started = false;
    $head = '
     <div class="form-group">
  	<label class="control-label col-sm-2">' . AdminPhrase('skins_clone_skin') . '</label>
	<div class="col-sm-6">
        <select name="clone_skin_id" class="form-control">
        <option value="0">' . AdminPhrase('skins_none') . '</option>';
    $bottom = '
        </select>
	</div>
	</div>
	 <div class="form-group">
  		<label class="control-label col-sm-2"></label>
		<div class="col-sm-6">
        <fieldset>
       <input class="checkbox ace"  id="opt_folder" type="checkbox" name="create_folder" value="1"'.($create_folder?' checked="checked"':'').' /><span class="lbl">
          '.AdminPhrase('skins_new_opt_create_folder').'</span><br />
        <input class="checkbox ace"  id="opt_folder" type="checkbox" name="copy_files" value="1"'.($copy_files?' checked="checked"':'').' /><span class="lbl">
          '.AdminPhrase('skins_new_opt_copy_files').'</span><br />
       <input class="checkbox ace"  id="opt_layout" type="checkbox" name="copy_layouts" value="1"'.($copy_layouts?' checked="checked"':'').' /><span class="lbl">
          '.AdminPhrase('skins_new_opt_copy_layouts').'</span><br />
        <input class="checkbox ace"  id="opt_css" type="checkbox" name="copy_css" value="1"'.($copy_css?' checked="checked"':'').' /><span class="lbl">
          '.AdminPhrase('skins_new_opt_copy_css').'</span>
      </div>
</div>';

    while($skin_arr = $DB->fetch_array($get_skins,null,MYSQL_ASSOC))
    {
      if(!$started)
      {
        echo $head;
        $started = true;
      }
      $name = htmlspecialchars(trim($skin_arr['name']));
      if(!strlen($name)) $name = AdminPhrase('skins_unnamed_skin');
      echo '
      <option value="'.$skin_arr['skinid'].'">'.$name.'</option>';
    }
    if($started) echo $bottom;
  }

  echo '<div class="center">
  		<button class="btn btn-info" type="submit" value=""><i class="ace-icon fa fa-check"></i> ' .
    addslashes(AdminPhrase('skins_create_skin')) . '</button>
	</div>
  </form>';
  
   echo '
   <div class="space-10"></div>
 	<div class="well">
    <h3 class="header blue lighter">Important</h3>
	Please note that the new skin is <b>NOT</b> being
    automatically activated! Until the new skin is activated, any editing
    on the Skins page would be for the current skin!
  </div>';

} //DisplayCreateSkin


// ############################################################################
// DISPLAY SWITCH SKIN SETTINGS
// ############################################################################

function DisplaySwitchSkinSettings()
{
  global $DB;

  if($skinid = Is_Valid_Number(GetVar('skinid', 0, 'whole_number'),0,1,999999))
  {
    $DB->result_type = MYSQL_ASSOC;
    $skin = $DB->query_first('SELECT skinid, name, folder_name, previewimage'.
                             ' FROM {skins}'.
                             ' WHERE skinid = %d', $skinid);
  }
  
  if(empty($skin['skinid']))
  {
    RedirectPage(SELF_SKINS_PHP.'?action=display_skins',
                 AdminPhrase('skins_err_skin_not_found'),2,true);
    return;
  }

  $DB->result_type = MYSQL_ASSOC;
  $skin_backup_arr = $DB->query_first('SELECT skinid FROM {skin_bak_cat}'.
                                      ' WHERE skinid = %d', $skinid);
  $skin_backup_exists = !empty($skin_backup_arr['skinid']);

  $previewimage = explode('/', trim($skin['previewimage']));
  $previewimage = $previewimage[count($previewimage) -1];

  if(strlen($previewimage))
  {
    $skin_preview_image = ROOT_PATH.'skins/'.$skin['folder_name'].'/'.$previewimage;
  }
  elseif(is_file(ROOT_PATH .'skins/' . $skin['folder_name'] . '/preview.jpg'))
  {
	  $skin_preview_image = ROOT_PATH .'skins/' . $skin['folder_name'] . '/preview.jpg';
  }
  else
  {
	  $skin_preview_image = ADMIN_IMAGES_FOLDER.'theme_preview.png';
  }
  

  echo '
  <form method="post" action="skins.php?action=switch_skin&skinid=' . $skin['skinid'] . '">
  '.PrintSecureToken();

  StartSection(AdminPhrase('skins_switch_skin'));
  echo '
  <table class="table table-bordered">
  <thead>
  <tr>
    <th class="tdrow1" colspan="2">
      '.AdminPhrase('skins_switching_to').' '.$skin['name'].'
    </td>
  </tr>
  </thead>
  <tbody>
  <tr>
    <td class="td3" width="225" align="center">
      <img alt="'.AdminPhrase('skins_click_to_switch').'" src="'.
      $skin_preview_image . '" />
    </td>
    <td class="td2" valign="top">
      <strong>' . AdminPhrase('skins_plugin_positions') . '</strong>
      <br /><br />
      ' . AdminPhrase('skins_switch_descr') . '
      <br /><br />
      <table class="table">
      <tr>
        <td width="40" class="center">
          <input ' . ($skin_backup_exists ? '' : 'disabled="disabled"').
          ' type="radio" class="ace" name="plugin_positions_setting" value="restore_plugin_positions" '.
          ($skin_backup_exists ? 'checked="checked"' : '') . ' /><span class="lbl"></span>
        </td>
        <td>
          <u>' . AdminPhrase('skins_plugins_restore').'</u><br />
          '.AdminPhrase('skins_plugins_restore_descr').'<br />
          <div class="' .
            ($skin_backup_exists ? 'green' : 'red') . ' ;">' . ($skin_backup_exists ? '<i class="ace-icon fa fa-check green bigger-120"></i> ' : '<i class="ace-icon fa fa-times red bigger-120"></i> ') .
            AdminPhrase($skin_backup_exists?'skins_backup_found':'skins_no_backup_found').'
          </div>
        </td>
      </tr>
      <tr>
        <td width="40" class="center">
          <input type="radio" class="ace" name="plugin_positions_setting" value="keep_plugin_positions" ' .
          ($skin_backup_exists ? '' : 'checked=checked"').' /><span class="lbl"></span>
        </td>
        <td >
          <u>'.AdminPhrase('skins_plugins_keep').'</u><br />
          '.AdminPhrase('skins_plugins_keep_descr').'
        </td>
      </tr>
      <tr>
        <td width="40" class="center">
          <input type="radio" class="ace" name="plugin_positions_setting" value="delete_plugin_positions" /><span class="lbl"></span>
        </td>
        <td>
          <u>'.AdminPhrase('skins_plugins_delete').'</u><br />
          '.AdminPhrase('skins_plugins_delete_descr').'
        </td>
      </tr>
      </table>
    </td>
  </tr>
  </tbody>
  </table>
  </div>';

  echo '<div class="center">
  			<button class="btn btn-info" type="submit" value="" /><i class="ace-icon fa fa-check"></i> ' .  addslashes(AdminPhrase('skins_switch_skin')) . '</button>
	</div>
  </form>';

} //DisplaySwitchSkinSettings


// ############################################################################
// SWITCH SKIN
// ############################################################################

function SwitchSkin()
{
  global $DB, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_SKINS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  if($skinid = Is_Valid_Number(GetVar('skinid', 0, 'whole_number'),0,1,999999))
  {
    $DB->result_type = MYSQL_ASSOC;
    $skin = $DB->query_first('SELECT skinid, name, folder_name, previewimage'.
                             ' FROM {skins}'.
                             ' WHERE skinid = %d', $skinid);
  }
  if(empty($skin['skinid']))
  {
    RedirectPage(SELF_SKINS_PHP.'?action=display_skins',
                 AdminPhrase('skins_err_skin_not_found'),2,true);
    return;
  }

  // values: keep_plugin_positions, restore_plugin_positions, delete_plugin_positions
  $plugin_positions_setting = GetVar('plugin_positions_setting', 'keep_plugin_positions', 'string');

  //
  // backup current skin before switching to new skin
  //
  $DB->result_type = MYSQL_ASSOC;
  $current_skin_arr = $DB->query_first('SELECT skinid FROM {skins} WHERE activated = 1 LIMIT 1');
  $current_skin_id  = $current_skin_arr['skinid'];

  // delete old backup of current skin
  $DB->query('DELETE FROM {skin_bak_cat} WHERE skinid = %d', $current_skin_id);
  $DB->query('DELETE FROM {skin_bak_pgs} WHERE skinid = %d', $current_skin_id);
  $DB->query('DELETE FROM {skin_bak_pgs_mobile} WHERE skinid = %d', $current_skin_id); //SD370

  // create the new backup of current skin
  //SD370: added mobile_designid
  if($getcats = $DB->query('SELECT categoryid, designid, mobile_designid'.
                           ' FROM {categories} ORDER BY categoryid'))
  {
    while($catset = $DB->fetch_array($getcats,null,MYSQL_ASSOC))
    {
      $DB->query('INSERT INTO {skin_bak_cat} (skinid, categoryid, designid, mobile_designid)'.
                 ' VALUES (%d, %d, %d, %d)',
                  $current_skin_id, $catset['categoryid'],
                  $catset['designid'], $catset['mobile_designid']);
    }
    $DB->free_result($getcats);
  }
  unset($getcats);

  if($getpluginpositions = $DB->query('SELECT * FROM {pagesort} ORDER BY categoryid, displayorder'))
  {
    while($pluginposition = $DB->fetch_array($getpluginpositions,null,MYSQL_ASSOC))
    {
      if($pluginposition['pluginid'] != '1')
      {
        $DB->query('INSERT INTO {skin_bak_pgs} (skinid, categoryid, pluginid, displayorder)'.
                   " VALUES (%d, %d, '%s', %d)",
                   $current_skin_id, $pluginposition['categoryid'],
                   $pluginposition['pluginid'], $pluginposition['displayorder']);
      }
    }
    $DB->free_result($getpluginpositions);
  }
  unset($getpluginpositions);

  //SD370: mobile pagesort backup
  if($getpluginpositions = $DB->query('SELECT * FROM {pagesort_mobile} ORDER BY categoryid, displayorder'))
  {
    while($pluginposition = $DB->fetch_array($getpluginpositions,null,MYSQL_ASSOC))
    {
      if($pluginposition['pluginid'] != '1')
      {
        $DB->query('INSERT INTO {skin_bak_pgs_mobile} (skinid, categoryid, pluginid, displayorder)'.
                   " VALUES (%d, %d, '%s', %d)",
                   $current_skin_id, $pluginposition['categoryid'],
                   $pluginposition['pluginid'], $pluginposition['displayorder']);
      }
    }
    $DB->free_result($getpluginpositions);
  }
  unset($getpluginpositions);

  //
  // Get skin backup information if we are restoring plugin positions
  //
  if($plugin_positions_setting == 'restore_plugin_positions')
  {
    $DB->result_type = MYSQL_ASSOC;
    $maxdesign = $DB->query_first('SELECT MAX(designid) maxdesign'.
                                  ' FROM {designs} WHERE skinid = %d',
                                  $skinid);
    $maxdesign = (empty($maxdesign['maxdesign']) ? 0 : (int)$maxdesign['maxdesign']);

    // get plugin positions backup
    $bakpositions = array();
    if($getpluginpositions = $DB->query('SELECT categoryid, displayorder, pluginid'.
                                        ' FROM {skin_bak_pgs}'.
                                        ' WHERE skinid = %d'.
                                        ' ORDER BY categoryid, displayorder',
                                        $skinid))
    {
      while($pluginposition = $DB->fetch_array($getpluginpositions,null,MYSQL_ASSOC))
      {
        if($pluginposition['pluginid'] != '1')
        {
          $bakpositions[$pluginposition['categoryid']][$pluginposition['displayorder']] = (string)$pluginposition['pluginid'];
        }
      }
      $DB->free_result($getpluginpositions);
    }
    unset($getpluginpositions);

    //SD370: get mobile plugin positions backup
    $mbakpositions = array();
    if($getpluginpositions = $DB->query('SELECT categoryid, displayorder, pluginid'.
                                        ' FROM {skin_bak_pgs_mobile}'.
                                        ' WHERE skinid = %d ORDER BY categoryid, displayorder',
                                        $skinid))
    {
      while($pluginposition = $DB->fetch_array($getpluginpositions,null,MYSQL_ASSOC))
      {
        if($pluginposition['pluginid'] != '1')
        {
          $mbakpositions[$pluginposition['categoryid']][$pluginposition['displayorder']] = (string)$pluginposition['pluginid'];
        }
      }
      $DB->free_result($getpluginpositions);
    }
    unset($getpluginpositions);

    // get category info backup
    $catdesigns = $catmdesigns = array();
    if($getcats = $DB->query('SELECT * FROM {skin_bak_cat}'.
                             ' WHERE skinid = %d'.
                             ' ORDER BY skinid, categoryid',
                             $skinid))
    {
      while($catdes = $DB->fetch_array($getcats,null,MYSQL_ASSOC))
      {
        $catdesigns[$catdes['categoryid']] = (int)$catdes['designid'];
        $catmdesigns[$catdes['categoryid']] = (int)$catdes['mobile_designid']; //SD370
      }
      $DB->free_result($getcats);
    }
    unset($getcats);

    // create an array of all plugins that exist, this is to verify an old
    // backup doesn't display a plugin that no longer exists
    $sd_plugin = array();
    if($getplugins = $DB->query('SELECT pluginid FROM {plugins} ORDER BY pluginid'))
    {
      while($sd_plugin = $DB->fetch_array($getplugins,null,MYSQL_ASSOC))
      {
        $sd_plugins[] = (string)$sd_plugin['pluginid'];
      }
      $DB->free_result($getplugins);
      unset($getplugins);
    }

    // create an array of all custom plugins that exist, this is to verify an
    // old backup doesn't display a custom plugin that no longer exists
    $sd_cplugins = array();
    if($getcplugins = $DB->query('SELECT custompluginid FROM {customplugins} ORDER BY custompluginid'))
    {
      while($sd_cplugin = $DB->fetch_array($getcplugins,null,MYSQL_ASSOC))
      {
        $sd_cplugins[] = (string)$sd_cplugin['custompluginid'];
      }
      $DB->free_result($getcplugins);
      unset($getcplugins);
    }
  } //restore plugin positions

  //
  // update all categories to use new skins design; this must be done even
  // if we are restoring, because new categories could have been created
  // after the restore point
  //
  $DB->result_type = MYSQL_ASSOC;
  if($design = $DB->query_first('SELECT designid, maxplugins FROM {designs} WHERE skinid = %d ORDER BY designid ASC', $skinid))
  {
    $DB->query('UPDATE {categories} SET designid = %d, mobile_designid = %d',
               $design['designid'], $design['designid']);
  }

  //
  // save the list of old plugin positions in case the user has enabled 'save plugin positions'
  //
  if($plugin_positions_setting == 'keep_plugin_positions')
  {
    $pluginpositions = $mpluginpositions = array();
    if($getpluginpositions = $DB->query('SELECT * FROM {pagesort} ORDER BY categoryid, displayorder'))
    {
      while($pluginposition = $DB->fetch_array($getpluginpositions,null,MYSQL_ASSOC))
      {
        $pluginpositions[$pluginposition['categoryid']][$pluginposition['displayorder']] = (string)$pluginposition['pluginid'];
      }
      $DB->free_result($getpluginpositions);
    }

    //SD370: added new mobile pagesort
    if($getpluginpositions = $DB->query('SELECT * FROM {pagesort_mobile} ORDER BY categoryid, displayorder'))
    {
      while($pluginposition = $DB->fetch_array($getpluginpositions,null,MYSQL_ASSOC))
      {
        $mpluginpositions[$pluginposition['categoryid']][$pluginposition['displayorder']] = (string)$pluginposition['pluginid'];
      }
      $DB->free_result($getpluginpositions);
    }
    unset($getpluginpositions);
  }

  //
  // Delete pagesort tables
  //
  $DB->query('DELETE FROM {pagesort}');
  $DB->query('DELETE FROM {pagesort_mobile}'); //SD370

  //
  // Insert the new plugin positions and update categories with designs
  //
  $getcategories = $DB->query('SELECT categoryid FROM {categories} ORDER BY categoryid');
  while($category = $DB->fetch_array($getcategories,null,MYSQL_ASSOC))
  {
    //For non-mobile layouts
    for($i = 0; $i < $design['maxplugins']; $i++)
    {
      $displayorder = $i + 1;

        // assign plugins and designs from backup:
        if($plugin_positions_setting == 'restore_plugin_positions')
        {
          // Copy over plugin slot from backup or 1
          $pluginid = !empty($bakpositions[$category['categoryid']][$displayorder]) ? $bakpositions[$category['categoryid']][$displayorder] : 1;

          // Check if specified plugin still exists:
          if(substr($pluginid,0,1)=='c')
          {
            $pluginid = !empty($sd_cplugins[substr($pluginid,1,6)])?$pluginid:1;
          }
          else
          {
            $pluginid = (!empty($sd_plugins) && @in_array($pluginid,$sd_plugins))?$pluginid:1;
          }

          // Update current category with the backup designid
          $did = isset($catdesigns[$category['categoryid']]) ? (int)$catdesigns[$category['categoryid']] : 0;
          if(!empty($did) && ((int)$did <= $maxdesign))
          {
            $DB->query('UPDATE {categories} SET designid = %d WHERE categoryid = %d',
                       $did, $category['categoryid']);
          }
        }
        else
        {
          if($plugin_positions_setting == 'keep_plugin_positions')
          {
            $pluginid = empty($pluginpositions[$category['categoryid']][$displayorder]) ? 1 : $pluginpositions[$category['categoryid']][$displayorder];
          }
          else
          {
            $pluginid = 1;
          }
        }
        $DB->query('INSERT INTO {pagesort} (categoryid, pluginid, displayorder)'.
                   " VALUES (%d, '%s', %d)",
                   $category['categoryid'], $pluginid, $displayorder);
    } //for

    //SD370: For mobile-only layouts
    for($i = 0; $i < $design['maxplugins']; $i++)
    {
      $displayorder = $i + 1;

      // assign plugins and designs from backup:
      if($plugin_positions_setting == 'restore_plugin_positions')
      {
        // Copy over plugin slot from backup or 1
        $pluginid = !empty($mbakpositions[$category['categoryid']][$displayorder]) ? $mbakpositions[$category['categoryid']][$displayorder] : 1;

        // Check if specified plugin still exists:
        if(substr($pluginid,0,1)=='c')
        {
          $pluginid = !empty($sd_cplugins[substr($pluginid,1,6)])?$pluginid:1;
        }
        else
        {
          $pluginid = (is_array($sd_plugins) && @in_array($pluginid,$sd_plugins))?$pluginid:1;
        }
        // Update current category with the backup designid
        $did = isset($catdesigns[$category['categoryid']]) ? (int)$catdesigns[$category['categoryid']] : 0;
        if(!empty($did) && ((int)$did <= $maxdesign))
        {
          $DB->query('UPDATE {categories} SET mobile_designid = %d WHERE categoryid = %d',
                     $did, $category['categoryid']);
        }

      }
      else
      {
        if($plugin_positions_setting == 'keep_plugin_positions')
        {
          $pluginid = empty($mpluginpositions[$category['categoryid']][$displayorder]) ? 1 : $mpluginpositions[$category['categoryid']][$displayorder];
        }
        else
        {
          $pluginid = 1;
        }
      }
      $DB->query("INSERT INTO {pagesort_mobile} (categoryid, pluginid, displayorder)
                  VALUES (%d, '%s', %d)",
                  $category['categoryid'], $pluginid, $displayorder);
    } //for mobile

  } //while

  // Finally, activate new skin and deactivate previous one
  $DB->query('UPDATE {skins} SET activated = 0 WHERE activated = 1');
  $DB->query('UPDATE {skins} SET activated = 1 WHERE skinid = %d', $skinid);

  RedirectPage(SELF_SKINS_PHP, AdminPhrase('skins_skin_switched'));

} //SwitchSkin


// ############################################################################
// DISPLAY IMPORT SKINS
// ############################################################################

function error_handler_skins($errno, $errstr)
{
  throw new Exception($errstr);
}

function DisplayImportSkins()
{
  global $DB;

  // LOAD XML PARSER ONCE
  require_once(SD_INCLUDE_PATH . 'xml_parser_php5.php');

  /*
  StartSection(AdminPhrase('skins_install_skin'));
  echo '
    <table width="100%" border="0" cellpadding="5" cellspacing="0">
    <tr>
      <td class="td1" width="100">' . AdminPhrase('skins_preview') . '</td>
      <td class="td1" width="150">' . AdminPhrase('skins_name') . '</td>
      <td class="td1">' . AdminPhrase('skins_author_information') . '</td>
      <td class="td1" width="150" align="center">' . AdminPhrase('skins_install_skin') . '</td>
    </tr>';
	*/

  $skins_dir = ROOT_PATH . 'skins/';
  $errors = array();

  if(false !== ($skins_dir_handle = @dir($skins_dir)))
  while(false !== ($entry = $skins_dir_handle->read()))
  {
    if((substr($entry,0,1)=='.') || !is_dir($skins_dir.$entry))
    {
      continue;
    }
    if(($entry != 'skin.xml') && (substr($entry,0,4) == 'skin') &&
       (substr($entry,-4) == '.xml'))
    {
      continue;
    }
    $skin_install_path  = '';
    $skin_engine        = 0;
    $skin_name          = '';
    $skin_author_name   = '';
    $skin_preview_image = '';
    $skin_error         = false;

    // Detect SD3 "xml" or SD2 "install" file in current folder:
    if(false !== ($skin_dir_handle = dir($skins_dir . $entry . '/')))
    {
      while(false !== ($filename = $skin_dir_handle->read()))
      {
        // Skip any "dotted" entries
        if((substr($filename,0,1)=='.') || is_dir($skins_dir.$entry.$filename))
        {
          continue;
        }

        $skin_install_path = $skins_dir . $entry . '/';
        if(strtolower(substr($filename, -4)) == '.xml')
        {
          $skin_install_path .= $filename;
          $skin_engine = 2;
          break;
        }
        else
        if($filename == 'install.php')
        {
          $skin_install_path .= 'install.php';
          $skin_engine = 1;
          break;
        }
      } //while
    }

    // If no entry file found, continue loop
    if(empty($skin_engine)) continue;

    if($skin_engine == 2) //new skin engine
    {
      $skin_name = '';
      // Load the xml document into a variable
      if(false !== ($xml = @file_get_contents($skin_install_path)))
      {
        // Set up the parser object
        $parser = new XMLParser($xml);

        // Parse the xml
        //SD360: avoid system log messages for xml errors by
        // temporarily assigning a dummy exception handler:
        $error = false;
        set_error_handler('error_handler_skins');
        try
        {
          $parser->Parse();
          $skin_name = property_exists($parser->document, 'name') ?
                         sd_substr(trim($parser->document->name[0]->tagData),0,64) :
                         '';
          $skin_author_name = property_exists($parser->document, 'author_name') ?
                         sd_substr(trim($parser->document->author_name[0]->tagData),0,64) :
                         '';
        }
        catch (Exception $e)
        {
          $error = true;
          $skin_name = dirname($skin_install_path);
          $skin_author_name = '<span class="red">'.$e->getMessage().'</span>';
          $errors[] = 'Parse error: <b>"'.$skin_install_path.'</b>": '.$e->getMessage();
        }
        restore_error_handler();
        unset($parser);

        if(is_file($skins_dir . $entry . '/preview.jpg'))
        {
          $skin_preview_image = $skins_dir . $entry . '/preview.jpg';
        }
        else
        {
          $skin_preview_image = ADMIN_IMAGES_FOLDER.'theme_preview.png';
        }
      }
    }
    else
    if($skin_engine == 1) // old skin engine
    {
      include($skin_install_path);

      $skin_name = '';
      $skin_author_name = '';
      $skin_preview_image = '';
      if(!empty($skinname))     $skin_name  = sd_substr(trim($skinname),0,64);
      if(!empty($authorname))   $skin_author_name = sd_substr(trim($authorname),0,64);
      if(!empty($previewimage)) $skin_preview_image = ROOT_PATH . 'skins/' . sd_substr(trim($previewimage),0,64);
    }

    if(strlen($skin_name) &&
      (!$skin_arr = $DB->query_first('SELECT skinid FROM {skins}'.
                                     " WHERE name = '%s'",
                                     $DB->escape_string($skin_name))))
    {
      $td_css = !isset($td_css) ? 'td2' : ( ($td_css == 'td2') ? 'td3' : 'td2' );
	  
	  echo '<div class="col-sm-3">
					<div class="panel panel-default">
					<div class="panel-heading bolder">' . $skin_name . '</div>
					<div class="panel-body">
					<div class="center">
						<h6 class="header blue lighter no-margin-top no-padding-top">' . AdminPhrase('skins_preview') . '</h6>
						<img src="' . $skin_preview_image . '" width="100" height="100" class="img-thumbnail"/>
					</div>
					<div class="space-4"></div>
					
					<ul class="list-unstyled spaced">
					
						
                    	<li>
							<i class="ace-icon fa fa-arrow-right  blue"></i>' . AdminPhrase('skins_author_information') . ' : <strong>' . $skin_author_name .'</strong>
						</li>
						<li>
							<i class="ace-icon fa fa-arrow-right  blue"></i>'.($error?'':'Folder: <strong>/skins/'.$entry.'/</strong>').'
						</li>
					</ul>
					<div class="space-10"></div>
					<div class="center">';
					
					if($skin_engine == 2)
					  {
						if($skin_error) echo $skin_error.'<br />';
						echo '
						  <form method="post" action="skins.php?action=import_skin">
						  '.PrintSecureToken().'
						  <input type="hidden" name="xml_file_path" value="' . $skin_install_path . '" />
						  <button class="btn btn-sm btn-block no-border btn-info" type="submit" name="installskin" value="' .
							addslashes(AdminPhrase('skins_install_skin')) . '" /><i class="ace-icon fa fa-download"></i>' .
							addslashes(AdminPhrase('skins_install_skin')) . '</button><br /><br />
						  </form>
						  <form method="post" action="skins.php?action=import_skin&verify=1">
						  '.PrintSecureToken().'
						  <input type="hidden" name="xml_file_path" value="' . $skin_install_path . '" />
						  <button type="submit" class="btn btn-sm btn-block no border btn-warning" name="installskin" title="Only verifies loading and presents error messages (if any), NO installation is done." value="Verify only" /><i class="ace-icon fa fa-check"></i> Verify Only</button>
						  </form>
						  ';
					  }
					  else
					  {
						echo '
						  <form method="post" action="skins.php?action=installskin">
						  '.PrintSecureToken().'
						  <input type="hidden" name="installpath" value="' . $skin_install_path . '" />
						  <button class="btn btn-sm btn-block no-border btn-info" type="submit" name="installskin" value="' .
							addslashes(AdminPhrase('skins_install_skin')) . '" /><i class="ace-icon fa fa-download"></i> ' .
							addslashes(AdminPhrase('skins_install_skin')) . '</button>
						  </form>';
					  }
					

            echo '  </form></div><div class="space-6"></div>					
					</div>
				</div></div>';
				

    }
  } //while

  if(!isset($td_css))
  {
    echo '
      <div class="alert alert-info">' . AdminPhrase('skins_all_installed') . '</div>';
  }

  echo '
  </table>';

  //SD370: display parse errors
  //TODO: translate this!
  if(!empty($errors))
  {
    DisplayMessage($errors,true,AdminPhrase('skins_xml_errors'));
  }



} //DisplayImportSkins


// ############################################################################
// IMPORT SKIN
// ############################################################################

function ImportSkin($reimport_mode=0)
{
  //SD400: added param "$reimport_mode":
  // 0 = none (default, no special action)
  // 1 = checking (load and lists layouts/css entries for selection)
  // 2 = reimport (only import selected entries, updating existing ones in DB)
  global $DB, $sdlanguage;

  //SD400: check $reimport_mode
  $reimport_mode = empty($reimport_mode)?0:(int)$reimport_mode;

  if($reimport_mode)
    $refreshpage = SELF_SKINS_PHP.'?action=display_reimport_skin_form'; //SD372
  else
    $refreshpage = SELF_SKINS_PHP.'?action=display_import_skins'; //SD370

  if(!CheckFormToken())
  {
    RedirectPage($refreshpage,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return false;
  }
  $xml_file_path = GetVar('xml_file_path', '', 'string',true,false);

  // Sanity checks for file/folder
  if(empty($xml_file_path) ||
     !sd_check_foldername(basename($xml_file_path)) ||
     (substr_count($xml_file_path,'../')>1) ||
     (substr($xml_file_path,0,1)=='/') ||
     strpos($xml_file_path,'//') ||
     strpos($xml_file_path,'\\\\') ||
     is_dir($xml_file_path) ||
     (strtolower(substr($xml_file_path,-4)) != '.xml') )
  {
    DisplayMessage(AdminPhrase('skins_err_invalid_filefolder'), true);
    if(!$reimport_mode) DisplayImportSkins();
    return false;
  }

  if($reimport_mode) $xml_file_path = ROOT_PATH.$xml_file_path;

  if(!file_exists($xml_file_path) || !is_file($xml_file_path))
  {
    if(!$reimport_mode)
    {
      DisplayMessage(AdminPhrase('skins_xml_file_not_found'), true);
      DisplayImportSkins();
    }
    return false;
  }

  // load the xml document into a string variable
  $xml = @file_get_contents($xml_file_path);

  // SD313: exit if file could not be read
  if(empty($xml) || ($xml === false))
  {
    RedirectPage($refreshpage, AdminPhrase('skins_skin_import_error'),2,true);
    return false;
  }

  // ################
  // LOAD XML PARSER
  // ################
  require_once(SD_INCLUDE_PATH . 'xml_parser_php5.php');

  // set up the parser object
  $parser = new XMLParser($xml);

  //SD360: avoid system log messages for xml errors by
  // temporarily assigning a dummy exception handler
  //SD370: added "verify" mode
  $verifyOnly = GetVar('verify', false, 'bool');
  $error = false;
  $errors = array();
  @set_error_handler('error_handler_skins');
  try
  {
    $parser->Parse();
  }
  catch (Exception $e)
  {
    $error = $e->getMessage();
  }
  @restore_error_handler();

  if($error !== false)
  {
    //SD370 only bail out if NOT in verify mode
    if(!$verifyOnly)
    {
      unset($parser);
      RedirectPage($refreshpage,
        '<p style="color:red;font-size:16px;font-weight:bold">'.AdminPhrase('skins_skin_errors').'</p><br />
        <b>'.AdminPhrase('skins_skin_file_error_descr').' "'.$xml_file_path.'":</b><br /><br />'.
        $error,5,true);
      return false;
    }
    $errors[] = $error;
  }

  $name = $author_name = $author_link = $folder_name = $header = $footer = $error_page = '';
  $error_page = $menu_outer_open = $menu_outer_close = $menu_item_open = $menu_item_close = '';
  $mobile_header = $mobile_footer = ''; //SD370

  if(isset($parser->document->name[0]))          $name          = $DB->escape_string($parser->document->name[0]->tagData);
  if(isset($parser->document->author_name[0]))   $author_name   = $DB->escape_string($parser->document->author_name[0]->tagData);
  if(isset($parser->document->author_link[0]))   $author_link   = $DB->escape_string($parser->document->author_link[0]->tagData);
  if(isset($parser->document->folder_name[0]))   $folder_name   = $DB->escape_string($parser->document->folder_name[0]->tagData);
  if(isset($parser->document->header[0]))        $header        = $DB->escape_string($parser->document->header[0]->tagData);
  if(isset($parser->document->footer[0]))        $footer        = $DB->escape_string($parser->document->footer[0]->tagData);
  if(isset($parser->document->error_page))       $error_page    = $DB->escape_string($parser->document->error_page[0]->tagData);
  if(isset($parser->document->mobile_header[0])) $mobile_header = $DB->escape_string($parser->document->mobile_header[0]->tagData); //SD370
  if(isset($parser->document->mobile_footer[0])) $mobile_footer = $DB->escape_string($parser->document->mobile_footer[0]->tagData); //SD370

  if(isset($parser->document->menu_level0_opening))
    $menu_level0_opening = $DB->escape_string($parser->document->menu_level0_opening[0]->tagData);
  else
    $menu_level0_opening = '<ul class=\"sf-menu\">';

  if(isset($parser->document->menu_level0_closing))
    $menu_level0_closing = $DB->escape_string($parser->document->menu_level0_closing[0]->tagData);
  else
    $menu_level0_closing = '</ul>';

  if(isset($parser->document->menu_submenu_opening))
    $menu_submenu_opening = $DB->escape_string($parser->document->menu_submenu_opening[0]->tagData);
  else
    $menu_submenu_opening = '<ul>';

  if(isset($parser->document->menu_submenu_closing))
    $menu_submenu_closing = $DB->escape_string($parser->document->menu_submenu_closing[0]->tagData);
  else
    $menu_submenu_closing = '</ul>';

  if(isset($parser->document->menu_item_opening))
    $menu_item_opening = $DB->escape_string($parser->document->menu_item_opening[0]->tagData);
  else
    $menu_item_opening = '<li>';

  if(isset($parser->document->menu_item_closing))
    $menu_item_closing = $DB->escape_string($parser->document->menu_item_closing[0]->tagData);
  else
    $menu_item_closing = '</li>';

  if(isset($parser->document->menu_item_link))
    $menu_item_link = $DB->escape_string($parser->document->menu_item_link[0]->tagData);
  else
    $menu_item_link = '<a href=\"[LINK]\" [TARGET]>[NAME]</a>';

  //SD370: import mobile menu settings
  if(isset($parser->document->mobile_menu_level0_opening))
    $mobile_menu_level0_opening = $DB->escape_string($parser->document->mobile_menu_level0_opening[0]->tagData);
  else
    $mobile_menu_level0_opening = '<ul class=\"fb-menu\">';

  if(isset($parser->document->mobile_menu_level0_closing))
    $mobile_menu_level0_closing = $DB->escape_string($parser->document->mobile_menu_level0_closing[0]->tagData);
  else
    $mobile_menu_level0_closing = '</ul>';

  if(isset($parser->document->mobile_menu_submenu_opening))
    $mobile_menu_submenu_opening = $DB->escape_string($parser->document->mobile_menu_submenu_opening[0]->tagData);
  else
    $mobile_menu_submenu_opening = '<ul>';

  if(isset($parser->document->mobile_menu_submenu_closing))
    $mobile_menu_submenu_closing = $DB->escape_string($parser->document->mobile_menu_submenu_closing[0]->tagData);
  else
    $mobile_menu_submenu_closing = '</ul>';

  if(isset($parser->document->mobile_menu_item_opening))
    $mobile_menu_item_opening = $DB->escape_string($parser->document->mobile_menu_item_opening[0]->tagData);
  else
    $mobile_menu_item_opening = '<li>';

  if(isset($parser->document->mobile_menu_item_closing))
    $mobile_menu_item_closing = $DB->escape_string($parser->document->mobile_menu_item_closing[0]->tagData);
  else
    $mobile_menu_item_closing = '</li>';

  if(isset($parser->document->mobile_menu_item_link))
    $mobile_menu_item_link = $DB->escape_string($parser->document->mobile_menu_item_link[0]->tagData);
  else
    $mobile_menu_item_link = '<a href=\"[LINK]\" [TARGET]>[NAME]</a>';

  // legacy 2.0 engine (< SD 3.0.3!)
  if(isset($parser->document->doctype))
  {
    $doctype = $starting_html_tag = $starting_body_tag = $head_include = $css_from_legacy = '';
    if(isset($parser->document->doctype))
    {
      $doctype = @$DB->escape_string(@$parser->document->doctype[0]->tagData) . "\n";
    }
    if(isset($parser->document->starting_html_tag))
    {
      $starting_html_tag = @$DB->escape_string(@$parser->document->starting_html_tag[0]->tagData) . "\n";
    }
    if(isset($parser->document->head_include))
    {
      $head_include = @$DB->escape_string(@$parser->document->head_include[0]->tagData) . "\n";
    }
    if(isset($parser->document->starting_body_tag))
    {
      $starting_body_tag = @$DB->escape_string(@$parser->document->starting_body_tag[0]->tagData) . "\n";
    }
    if(isset($parser->document->css))
    {
      $css_from_legacy = @$DB->escape_string(@$parser->document->css[0]->tagData);
    }

    $header = $doctype
              . $starting_html_tag
              . "<head>\n"
              . "[CMS_HEAD_INCLUDE]\n"
              . $head_include
              . "</head>\n"
              . $starting_body_tag
              . $header;

    $footer .= "\n"
               . "</body>\n"
               . "</html>";

    $legacy_engine_2 = true;
  }

  $layout_count = isset($parser->document->layout) ? count($parser->document->layout) : 0;
  $css_count    = isset($parser->document->css) ? count($parser->document->css) : 0;
  if(empty($name)) $name = $DB->escape_string(AdminPhrase('skins_untitled'));
  
  //SD370: no INSERT in verify mode
  //SD400: no INSERT in reimport mode
  if(!$verifyOnly && !$reimport_mode)
  {
    $DB->query("INSERT INTO {skins}
               (skinid, skin_engine, name, numdesigns, authorname, authorlink,
                folder_name, header, footer, error_page,
                menu_level0_opening, menu_level0_closing,
                menu_submenu_opening, menu_submenu_closing,
                menu_item_opening, menu_item_closing, menu_item_link,
                mobile_menu_level0_opening, mobile_menu_level0_closing,
                mobile_menu_submenu_opening, mobile_menu_submenu_closing,
                mobile_menu_item_opening, mobile_menu_item_closing, mobile_menu_item_link)
               VALUES (NULL, 2, '$name', $layout_count, '$author_name', '$author_link',
               '$folder_name', '$header', '$footer', '$error_page',
               '$menu_level0_opening', '$menu_level0_closing',
               '$menu_submenu_opening', '$menu_submenu_closing',
               '$menu_item_opening', '$menu_item_closing', '$menu_item_link',
               '$mobile_menu_level0_opening', '$mobile_menu_level0_closing',
               '$mobile_menu_submenu_opening', '$mobile_menu_submenu_closing',
               '$mobile_menu_item_opening', '$mobile_menu_item_closing',
               '$mobile_menu_item_link')");
    $skinid = $DB->insert_id();
    if(empty($skinid)) //SD370
    {
      unset($parser);
      RedirectPage($refreshpage, AdminPhrase('skins_import_parse_error').'<br />'.$error,2,true);
      return false;
    }
  }
  else
  if($reimport_mode>0) //SD400
  {
    $skinid = Is_Valid_Number(GetVar('skinid', 0, 'whole_number'),0,1,9999999);
    if(!$skin_arr = $DB->query_first('SELECT name, folder_name'.
                                    ' FROM {skins}'.
                                    ' WHERE skinid = %d AND activated = 1 AND skin_engine = 2',$skinid))
    {
      RedirectPage($refreshpage,'<strong>'.AdminPhrase('skins_err_skin_not_found').'</strong><br />',2,true);
      return;
    }
  }

  //SD360: at least create one layout with one plugin slot
  if($layout_count < 1)
  {
    if(!$verifyOnly && !$reimport_mode) //SD370: no SQL in verify mode
      $DB->query("INSERT INTO {designs} (designid, skinid, maxplugins, layout, design_name)
                  VALUES (NULL, %d, 1, '%s', '%s')",
                  $skinid,
                  "[HEADER]\r\n<plugin>\r\n<plugin_name>[PLUGIN_NAME]</plugin_name>\r\n[PLUGIN]\r\n</plugin>\r\n[FOOTER]",
                  $DB->escape_string(AdminPhrase('skins_untitled')));
    else
      DisplayMessage(AdminPhrase('skins_no_layouts_err'),true);
  }
  else
  {
    if($reimport_mode>0)
    {
     	if($reimport_mode == 1)
		{
	  		
		}
		else
		{
			
		}
		
      if($reimport_mode==1)
	  {
		 echo '<div class="form-group">
      			<label class="control-label col-sm-4">';
        echo 'Reimport any of the following '.$layout_count.' layouts</label>
		<div class="col-sm-6">';
	  }
      else
	  {
	  	echo '<tr><td>Reimporting layouts</td><td>';
	  }
    }
    for($i = 0; $i < $layout_count; $i++)
    {
      // SD313: also import design names
      $total_plugin_count = 0;
      $layout_tag  = $parser->document->layout[$i];
      $design_name = @htmlspecialchars($layout_tag->tagAttrs['design_name'], ENT_QUOTES); //MUST use htmlspecialchars()!
      if(empty($design_name) || strlen($design_name) < 1) $design_name = 'Layout ' . ($i+1);
      $layout = @$DB->escape_string($layout_tag->tagData);
	  
      if(!empty($legacy_engine_2))
      {
        $layout = "[HEADER]\n" . $layout . "\n[FOOTER]";
      }

      // does this layout include header?
      if(strstr($layout, "[HEADER]"))
      {
        // how many plugin positions in header?
        $total_plugin_count += substr_count($header, '[PLUGIN]');
      }

      // does this layout include a footer?
      if(strstr($layout, "[FOOTER]"))
      {
        // how many plugin positions are in footer?
        $total_plugin_count += substr_count($footer, '[PLUGIN]');
      }

      // does this layout include a mobile header?
      if(strstr($layout, "[MOBILE_HEADER]")) //SD370
      {
        // how many plugin positions in mobile header?
        $total_plugin_count += substr_count($mobile_header, '[PLUGIN]');
      }

      // does this layout include a mobile footer?
      if(strstr($layout, "[MOBILE_FOOTER]")) //SD370
      {
        // how many plugin positions are in mobile footer?
        $total_plugin_count += substr_count($mobile_footer, '[PLUGIN]');
      }

      // how many plugins are in this layout?
      $total_plugin_count += substr_count($layout, '[PLUGIN]');

      //SD400: reimport_mode to either check or update existing layout(s)
      if($reimport_mode==1)
      {
        echo '&nbsp;&nbsp;<input type="checkbox" class="ace" value="'.$design_name.'" name="layouts[]"><span class="lbl"> '.$design_name.'</span><br /><br />';
      }
      else
      if(($reimport_mode==2) && isset($_POST['layouts']) && in_array($design_name,$_POST['layouts']))
      {
        // Now the important stuff: update existing layout in designs table
		echo $design_namme;
		
        if(!$design_arr = $DB->query_first('SELECT designid'.
                                           ' FROM {designs}'.
                                           ' WHERE skinid = %d'.
                                           " AND design_name = '%s'",
                                           $skinid, $DB->escape_string($design_name)))
        {
          echo '<span class="red"><i class="ace-icon fa fa-minus-circle red"></i> '. $design_name . ' ' .AdminPhrase('skins_not_found').'</span>';
        }
        else
        {
          $DB->query('UPDATE {designs}'.
                     ' SET maxplugins = %d,'.
                     " layout = '%s'".
                     ' WHERE skinid = %d AND designid = %d',
                      $total_plugin_count, $layout,
                      $skinid, $design_arr['designid']);
          echo '&nbsp;&nbsp; <span class="green"><i class="ace-icon fa fa-check green"></i> <strong>'. $design_name . '</strong> ' . AdminPhrase('skins_entry_reimported');

        }
        echo '<br />';
      }
      else
      if(!$verifyOnly && !$reimport_mode) //SD372: no INSERT in verify/reimport mode
      {

        $DB->query("INSERT INTO {designs} (designid, skinid, maxplugins, layout, design_name)
                    VALUES (NULL, %d, %d, '%s', '%s')",
                    $skinid, $total_plugin_count, $layout,
                    $DB->escape_string($design_name));
      }
    } //for

    if($reimport_mode==1) echo '</div></div>';
	if($reimport_mode==2) echo '</td></tr>';
  }

  if(!empty($css_from_legacy))
  {
    if(!$verifyOnly && !$reimport_mode) //SD370: no SQL in verify mode
      $DB->query("INSERT INTO {skin_css} (skin_css_id, skin_id, plugin_id, var_name, css) VALUES
                  (NULL, %d, 0, 'skin-css', '%s')",
                  $skinid, $css_from_legacy);
  }
  else
  {
    global $plugin_name_to_id_arr; //SD343

    if($reimport_mode) //SD400
    {
      
    
      if($reimport_mode==1)
	  {
	 	 echo '<div class="form-group">
	 	<label class="control-label col-sm-4">';
        echo 'Reimport any of the following '.count($parser->document->css).' CSS entries</label>';
		echo '
        </label>
		<div class="col-sm-6">';
	  }
      else
        echo '<tr><td>Reimporting CSS entries</td><td>';
      
    }

    foreach($parser->document->css as $css_tag)
    {
      $var_name  = isset($css_tag->tagAttrs['var_name'])?trim($css_tag->tagAttrs['var_name']):'';
      $plugin_id = isset($css_tag->tagAttrs['plugin_id'])?trim($css_tag->tagAttrs['plugin_id']):-1;

      // error checking
      if($plugin_id !== -1) $plugin_id = Is_Valid_Number($plugin_id,-2,0,9999999);
      if(empty($var_name) || ($plugin_id < 0))
      {
        echo '<br />'.AdminPhrase('skins_invalid_css_entry_err').' "'.htmlspecialchars($var_name).'"';
        if($plugin_id == -2)
          echo ' '.AdminPhrase('skins_invalid_plugin_id_err');
        else
          echo ($plugin_id > 0 ? '' : ' Plugin ID: '.$plugin_id);
        continue;
      }

      //SD370: "mobile" support; differentiate between regular and mobile CSS
      $mobile = isset($css_tag->tagAttrs['mobile'])?trim($css_tag->tagAttrs['mobile']):0;
      if(intval($mobile) != 1) $mobile = 0;

      $css = isset($css_tag->tagData) ? $css_tag->tagData : '';

      //SD322: fix forum plugin id which could be different from source system
      if(($plugin_id > 0) && ($var_name == 'Forum'))
      {
        if(!$plugin_id = GetPluginID('Forum'))
        {
          $plugin_id = -1;
        }
      }

      //TODO: WHAT DO WE DO WITH NON-MATCHING PLUGIN IDs HERE???
      $doSkip = false;
      if(($plugin_id >= 5000) && !in_array($plugin_id, $plugin_name_to_id_arr))
      {
        echo '<br />Plugin '.$var_name.' is not installed (or different ID), skipped!';
        $doSkip = true;
      }

      if($reimport_mode==1) //SD372
      {
        echo '&nbsp;&nbsp;<input type="checkbox" class="ace" value="'.$var_name.'" name="css[]"><span class="lbl"> '.$var_name.'</span><br /><br />';
      }

      if(!$doSkip && empty($verifyOnly) && ($reimport_mode!=1)) //SD370: no SQL in verify mode
      {
        //SD370: mobile related changes; update then insert logic
        // only the "mobile-skin-css" entry should have mobile=1
        $DB->result_type = MYSQL_ASSOC;
        $tmp = $DB->query_first('SELECT skin_css_id FROM {skin_css}'.
                                " WHERE skin_id = %d AND plugin_id = %d AND var_name = '%s' LIMIT 1",
                                (int)$skinid, (int)$plugin_id, $DB->escape_string($var_name));
        if(empty($tmp['skin_css_id']))
        {
          if(($reimport_mode==2) && in_array($var_name,$_POST['css']))
          {
            echo '<b>CSS "'.$var_name.'" <span style="color:red"><i class="ace-icon fa fa-minus-circle red"></i> '.AdminPhrase('skins_not_found').'</span></b><br />';
          }
          else
          if(!$verifyOnly && !$reimport_mode)
          {
            $DB->query('INSERT INTO {skin_css} (skin_css_id, skin_id, plugin_id, var_name, mobile, css)'.
                       " VALUES (NULL, %d, %d, '%s', %d, '%s')",
                       (int)$skinid, (int)$plugin_id, $DB->escape_string($var_name),
                       (int)$mobile, $DB->escape_string($css));
          }
        }
        else
        if((!$verifyOnly && !$reimport_mode) ||
           (($reimport_mode==2) && in_array($var_name,$_POST['css'])))
        {
          $DB->query("UPDATE {skin_css} SET css = '%s', mobile = %d".
                     " WHERE skin_css_id = %d",
                     $DB->escape_string($css), (int)$mobile, $tmp['skin_css_id']);
          if($reimport_mode==2)
          {
            echo '<span class="green"><i class="ace-icon fa fa-check green"></i> CSS <strong>"'.$var_name.'"</strong> '.AdminPhrase('skins_entry_reimported').'</span><br />';
          }
        }
      }
    }//foreach

    if($reimport_mode == 1) //SD400
    {
      echo '</div></div>';
    }
	else
	{
		echo '</td></tr>';
	}
  }

  if($reimport_mode>0) return (bool)empty($errors); //SD400

  //SD370: display parsed errors
  if(!empty($errors))
  {
    RedirectPage($refreshpage,
      '<p style="color:red;font-size:16px;font-weight:bold">'.AdminPhrase('skins_skin_errors').
      '</p><br /><b>'.AdminPhrase('skins_skin_file_error_descr').' "'.
      $xml_file_path.'":</b><br /><br />'.implode('<br />',$errors),5,true);
    return false;
  }
  else
  {
    RedirectPage($refreshpage,
      (empty($verifyOnly)?AdminPhrase('skins_skin_imported'):'Skin file verified without errors.'),5);
    return true;
  }

} //ImportSkin


// ############################################################################
// DISPLAY ALL INSTALLED SKINS
// ############################################################################

function DisplaySkins()
{
  global $DB, $sdlanguage;

  echo '
<script type="text/javascript">
  function ConfirmUninstallSkin(){
    if(confirm("' . htmlspecialchars(AdminPhrase('skins_confirm_uninstall')) . '"))
      return true;
    else
      return false;
  }
</script>';

  StartTable(AdminPhrase('common_skins'), array('table','table-bordered','table-striped'));
  echo '
  <thead>
  <tr>
    <th class="td1" width="100">' . AdminPhrase('skins_preview') . '</th>
    <th class="td1" width="150">' . AdminPhrase('skins_name') . '</th>
    <th class="td1">' . AdminPhrase('skins_author_information') . '</th>
    <th class="td1">' . AdminPhrase('skins_switch_skin') . '</th>
    <th class="td1">' . AdminPhrase('skins_export_skin') . '</th>
    <th class="td1" width="75" align="center">' . AdminPhrase('common_delete') . '</th>
  </tr>
  </thead>
  <tbody>';

  if($getskins = $DB->query('SELECT * FROM {skins}'.
                            ' ORDER BY activated DESC, name ASC, skinid'))
  while($skin_arr = $DB->fetch_array($getskins,null,MYSQL_ASSOC))
  {
    if(!is_numeric($skin_arr['authorlink']) && strlen($skin_arr['authorlink']) > 0)
    {
      if(substr($skin_arr['authorlink'], 0, 7) != 'http://')
      {
        $skin_arr['authorlink'] = 'http://' . $skin_arr['authorlink'];
      }

      $author_link = '<br /><a href="' . $skin_arr['authorlink'] . '" target="_blank">'.
                     AdminPhrase('skins_visit_author_site') . '</a>';
    
    }
    else
    {
      $author_link = '';
    }

    if($skin_arr['activated'])
    {
      $switch_skin_link = AdminPhrase('skins_current');
      $delete_skin_link = '<i class="ace-icon fa fa-trash-o red bigger-110"></i>';
    }
    else
    {
      $switch_skin_link = '<a href="skins.php?action=display_switch_skin_settings&amp;skinid='.
        $skin_arr['skinid'].SD_URL_TOKEN.'">'.
        '<i class="ace-icon fa fa-check green bigger-110"></i>&nbsp;'.
        AdminPhrase('skins_switch_to_skin').'</a>';
      $delete_skin_link = '<a href="skins.php?action=uninstallskin&amp;skinid='.
        $skin_arr['skinid'].SD_URL_TOKEN.'">'.
        '<i class="ace-icon fa fa-trash-o red bigger-110"></i></a>';
    }

    if(strlen($skin_arr['previewimage']))
    {
      $skin_preview_image = '../skins/' . $skin_arr['previewimage'];
    }
    else if(is_file('../skins/' . $skin_arr['folder_name'] . '/preview.jpg'))
    {
      $skin_preview_image = '../skins/' . $skin_arr['folder_name'] . '/preview.jpg';
    }
    else
    {
      $skin_preview_image = ADMIN_IMAGES_FOLDER.'theme_preview.png';
    }

    if($skin_arr['skin_engine'] == 2)
    {
      $export_skin_link = '<a href="skins.php?action=display_export_skin_form&amp;skinid='.
        $skin_arr['skinid'].SD_URL_TOKEN.'"><i class="ace-icon fa fa-download bigger-110"></i> ' .
        AdminPhrase('skins_export_skin') . '</a>';
		if($skin_arr['activated'])
      {
        //SD372: offer reimport for active SD3 skin
        $export_skin_link .=
          '<br /><br /><a href="skins.php?action=display_reimport_skin_form&amp;skinid='.
          $skin_arr['skinid'].SD_URL_TOKEN.'"><i class="ace-icon fa fa-refresh bigger-110"></i>&nbsp;'.AdminPhrase('skins_reimport_file').'</a>';
      }
    }
    else
    {
      $export_skin_link = '';
    }

    $td_css = !isset($td_css) ? 'td2' : ($td_css == 'td2' ? 'td3' : 'td2');
	$td_css .= $skin_arr['activated']?'':''; //SD400
   

    echo '
    <tr ' . ($skin_arr['activated'] ? 'class="warning"' : '') .'>
      <td class="' . $td_css . '"><img src="' . $skin_preview_image . '" width="100" height="100" alt=" " /></td>
      <td class="' . $td_css . '" align="center"><a href="'.SELF_SKINS_PHP.'?action=display_skin&amp;skinid='.
        $skin_arr['skinid'].SD_URL_TOKEN.'"><i class="ace-icon fa fa-edit bigger-110"></i> '.
        $skin_arr['name'].'</a></td>
      <td class="'.$td_css.'" align="center">'.$skin_arr['authorname'].$author_link.'</td>
      <td class="'.$td_css.'" align="center">'.$switch_skin_link.'</td>
      <td class="'.$td_css.'" align="center">'.$export_skin_link.'</td>
      <td class="'.$td_css.'" align="center">'.$delete_skin_link.'</td>
    </tr>';
  }

  echo '</tbody></table></div>';

} //DisplaySkins


// ############################################################################
// DISPLAY EXPORT SKIN FORM
// ############################################################################

function DisplayExportSkinForm($errors=false)
{
  global $DB, $sdlanguage, $gzip_support, $zip_support;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_SKINS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  $skinid = Is_Valid_Number(GetVar('skinid', 0, 'whole_number'),0,1,9999999);
  if(!$skin_arr = $DB->query_first('SELECT name, folder_name'.
                                  ' FROM {skins}'.
                                  ' WHERE skinid = %d',$skinid))
  {
    RedirectPage(SELF_SKINS_PHP,'<strong>'.AdminPhrase('skins_err_skin_not_found').'</strong><br />',2,true);
    return;
  }

  if(!empty($errors))
  {
    DisplayMessage($errors, true);
    $xml_file_path = GetVar('xml_file_path', '', 'string',true,false);
  }
  else
  {
    $skinid = Is_Valid_Number(GetVar('skinid', 0, 'whole_number',false,true),0,1,9999999);
    $xml_file_path = 'skins';
    if(isset($skin_arr['folder_name']) && strlen($skin_arr['folder_name']))
    {
      $xml_file_path .= '/'.$skin_arr['folder_name'];
    }
    $xml_file_path .= '/skin.xml';
  }

  $exp_all_css  = GetVar('exp_all_css', 1, 'bool', true, false);
  $exp_cust_css = GetVar('exp_cust_css', 0, 'bool', true, false);
  $exp_gzip     = GetVar('exp_gzip', 0, 'bool', true, false);
  $exp_zip      = GetVar('exp_zip', 0, 'bool', true, false);

  echo '
  <form action="'.SELF_SKINS_PHP.'?action=export_skin" method="post" class="form-horizontal">
  <input type="hidden" name="skinid" value="'.$skinid.'" />
  '.PrintSecureToken();

  echo '<h2 class="header blue lighter">' . AdminPhrase('skins_export_skin') . '</h2>';
  echo '
  <div class="form-group">
  	<label class="col-sm-3 control-label" for="skinname">'.AdminPhrase('skins_name').'</label>
	<div class="col-sm-6">
		<p class="form-control-static">'.$skin_arr['name'].'</strong></p>
	</div>
</div>
  <div class="form-group">
  	<label class="col-sm-3 control-label" for="xml_file_path">'.AdminPhrase('skins_select_xml_file').'</label>
	<div class="col-sm-6">
      <input type="text" class="form-control" name="xml_file_path" value="'.$xml_file_path.'" />
	<span class="helper-text"> '.AdminPhrase('skins_select_xml_file_descr').'</span>
    </div>
  </div>
  <div class="form-group">
  	<label class="col-sm-3 control-label" for="skinname">' . AdminPhrase('options') . '</label>
	<div class="col-sm-6">
    	<input class="ace" id="exp_css" type="checkbox" name="exp_all_css" value="1"'.($exp_all_css?' checked="checked"':'').' />
        <span class="lbl"> '.AdminPhrase('skins_exp_opt_css').'</span><br /><br />';
  if($gzip_support)
  {
    echo '
      <input class="ace" id="exp_gzip" type="checkbox" name="exp_gzip" value="1"'.($exp_gzip?' checked="checked"':'').' />
       <span class="lbl"> '.AdminPhrase('skins_exp_opt_gzip').'</span><br /><br />';
  }
  if($zip_support)
  {
    echo '
      <input class="ace" id="exp_zip" type="checkbox" name="exp_zip" value="1"'.($exp_zip?' checked="checked"':'').' />
        <span class="lbl"> '.AdminPhrase('skins_exp_opt_zip').'</span>';
  }
  echo '
    </div>
  </div>';

  echo '
  <div class="center">
  	<button type="submit" class="btn btn-info" value=""><i class="ace-icon fa fa-save"></i> '.addslashes(AdminPhrase('skins_export_skin')).'</button>
</div>
  </form>';

} //DisplayExportSkinForm

// ############################################################################
// DISPLAY REIMPORT SKIN FORM
// ############################################################################

function DisplayReimportSkinForm($errors=false) //SD372
{
  global $DB, $sdlanguage, $gzip_support, $zip_support;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_SKINS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  $skinid = Is_Valid_Number(GetVar('skinid', 0, 'whole_number'),0,1,9999999);
  if(!$skin_arr = $DB->query_first('SELECT name, folder_name'.
                                  ' FROM {skins}'.
                                  ' WHERE skinid = %d AND activated = 1 AND skin_engine = 2',$skinid))
  {
    RedirectPage(SELF_SKINS_PHP,'<strong>'.AdminPhrase('skins_err_skin_not_found').'</strong><br />',2,true);
    return;
  }

  if(!empty($errors))
  {
    DisplayMessage($errors, true);
    $xml_file_path = GetVar('xml_file_path', '', 'string',true,false);
  }
  else
  {
    $xml_file_path = 'skins';
    if(isset($skin_arr['folder_name']) && strlen($skin_arr['folder_name']))
    {
      $xml_file_path .= '/'.$skin_arr['folder_name'];
    }
    $xml_file_path .= '/skin.xml';
  }

  echo '
  <form action="'.SELF_SKINS_PHP.'?action=display_reimport_options" method="post" class="form-horizontal">
  <input type="hidden" name="skinid" value="'.$skinid.'" />
  <input type="hidden" name="reimport_mode" value="0" />
  '.PrintSecureToken();

  echo '<h3 class="header blue lighter">' . AdminPhrase('skins_reimport_file').' (1/2)</h3>';
  echo '
 	<div class="form-group">
		<label class="control-label col-sm-2">'.AdminPhrase('skins_select_xml_import_file').'</label>
		<div class="col-sm-6">
     		<input type="text" class="form-control" name="xml_file_path" value="'.$xml_file_path.'" />
			<span class="helper-text"> '.AdminPhrase('skins_select_xml_import_file_descr').'</span>
		</div>
	</div>
  	<div class="center">
		<button class="btn btn-info" type="submit" value=""><i class="ace-icon fa fa-check"></i> ' .addslashes(AdminPhrase('skins_analyze_xml')).'
	</div>
  </form>';

} //DisplayReimportSkinForm


// ############################################################################
// DISPLAY REIMPORT OPTIONS FORM
// ############################################################################

function DisplayReimportOptions($errors=false) //SD372
{
  global $DB, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_SKINS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  // Must have reimport_mode posted, otherwise go back
  if(!isset($_POST['reimport_mode']))
  {
    RedirectPage(SELF_SKINS_PHP.'?action=display_reimport_skin_form','Error!',1);
    return;
  }

  // check for skin existing
  $skinid = Is_Valid_Number(GetVar('skinid', 0, 'whole_number'),0,1,9999999);
  if(!$skin_arr = $DB->query_first('SELECT name, folder_name'.
                                  ' FROM {skins}'.
                                  ' WHERE skinid = %d AND activated = 1 AND skin_engine = 2',$skinid))
  {
    RedirectPage(SELF_SKINS_PHP,'<strong>'.AdminPhrase('skins_err_skin_not_found').'</strong><br />',2,true);
    return;
  }
  unset($_GET['xml_file_path']); // avoid false input

  $reimport_mode = Is_Valid_Number(GetVar('reimport_mode', 0, 'whole_number'),0,0,2);
  if(!$reimport_mode) $reimport_mode = 1;
  if(($reimport_mode==2) && !isset($_POST['layouts']) && !isset($_POST['css']))
  {
    RedirectPage(SELF_SKINS_PHP.'?action=display_reimport_skin_form&skinid='.$skinid.PrintSecureUrlToken(),
                 '<strong>'.AdminPhrase('skins_reimport_cancelled').'</strong>',2,true);
    return;
  }

  ob_start();
  $result = ImportSkin($reimport_mode);
  $output = ob_get_clean();
  if(!$result && ($reimport_mode<2))
  {
    DisplayReimportSkinForm('skins_skin_file_err');
    return false;
  }

  if($reimport_mode==2)
  {
    StartSection(AdminPhrase('skins_reimport_file'));
    echo '
    <table class="table table-bordered">
	<thead>
    <tr>
      <th class="td2"><strong>'.AdminPhrase('skins_name').'</th>
      <th class="td3"><strong>'.$skin_arr['name'].'</strong></th>
    </tr>
	</thead>
	<tbody>
    '.$output.'
    </table><br />';
    RedirectPage(SELF_SKINS_PHP.'?action=display_skins&skinid='.$skinid.PrintSecureUrlToken(),
                 ''.AdminPhrase('skins_reimport_finished').'',5,false);
    return;
  }
  else
  {
    $xml_file_path = GetVar('xml_file_path', '', 'string',true,false);
    echo '
    <form action="'.SELF_SKINS_PHP.'?action=display_reimport_options" method="post" class="form-horizontal">
    <input type="hidden" name="skinid" value="'.$skinid.'" />
    <input type="hidden" name="reimport_mode" value="2" />
    <input type="hidden" name="xml_file_path" value="'.$xml_file_path.'" />
    '.PrintSecureToken();

    echo '<h3 class="header blue lighter">' . AdminPhrase('skins_reimport_file').' (2/2)</h3>';
    echo '
    <div class="form-group">
		<label class="control-label col-sm-4">'.AdminPhrase('skins_name').'</label>
		<div class="col-sm-6"><p class="form-control-static bigger bolder">'.$skin_arr['name'].'</p></div>
	</div>
   
    '.$output.'
 
    <div class="center">
		<button type="submit" class="btn btn-info" value=""><i class="ace-icon fa fa-check"></i> '.
      addslashes(AdminPhrase('skins_reimport_xml')).'</button>
	</div>
    </form>';
  }

} //DisplayReimportOptions


// ############################################################################
// EXPORT SKIN
// ############################################################################

function ExportSkin()
{
  global $DB, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_SKINS_PHP.'?action=display_skins','<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  $skinid = Is_Valid_Number(GetVar('skinid', 0, 'whole_number',true,false),0,1,9999999);
  $xml_file_path = trim(GetVar('xml_file_path', '', 'string'));

  $errors = array();
  $folder = ROOT_PATH.dirname($xml_file_path);

  // Was a folder specified?
  if(empty($xml_file_path))
  {
    $errors[] = AdminPhrase('skins_enter_full_xml_filepath');
  }

  // Sanity checks for file/folder
  if((false===realpath($folder)) ||
     substr_count($xml_file_path,'./') ||
     (substr($xml_file_path,0,1)=='/') ||
     (substr($xml_file_path,0,1)=='.') ||
     strpos($xml_file_path,'//') ||
     is_dir($xml_file_path) ||
     !sd_check_foldername(basename($folder)))
  {
    $errors[] = AdminPhrase('skins_err_invalid_filefolder');
  }
  else
  if(!is_dir($folder))
  {
    $errors[] = AdminPhrase('skins_err_dest_folder_missing');
  }
  else
  if(strtolower(substr(basename($xml_file_path),-4)) != '.xml')
  {
    $errors[] = AdminPhrase('skins_err_ext_not_xml');
  }
  else
  // Does skin exist?
  if(!$skin_arr = $DB->query_first('SELECT * FROM {skins} WHERE skinid = %d',$skinid))
  {
    $errors[] = AdminPhrase('skins_err_skin_not_found');
  }

  if(!empty($errors))
  {
    DisplayExportSkinForm($errors);
    return false;
  }

  $GLOBALS['sd_ignore_watchdog'] = true;
  echo '<h2>Skin XML export to folder: '.$folder.'</h2><br />';

  $xml_file_path = ROOT_PATH.$xml_file_path;

  // Let's make sure the file exists and is writable first.
  if(!file_exists($xml_file_path) || !is_file($xml_file_path))
  {
    echo AdminPhrase('skins_xml_file_not_found') . ': ' . $xml_file_path.'<br />';
  }

  // Make folder writable if it exists
  if(is_dir($folder) /*&& !is_writable($folder)*/)
  {
    @chmod($folder, intval("0777",8));
  }
  // Try to make file writable if exists
  if(is_file($xml_file_path))
  {
    if(!is_writable($xml_file_path))
    {
      @chmod($xml_file_path, intval("0666",8));
      if(!is_writable($xml_file_path))
      {
        $errors[] = AdminPhrase('skins_xml_file_not_writable');
      }
    }
  }
  if(empty($errors) && ($handle = @fopen($xml_file_path, 'wb')) === false)
  {
    $errors[] = AdminPhrase('skins_xml_file_not_writable');
  }
  $GLOBALS['sd_ignore_watchdog'] = false;

  if(count($errors))
  {
    DisplayExportSkinForm($errors);
    return false;
  }

  // create the contents of the xml file
  $xml_content  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
  $xml_content .= "<theme>\n";
  $xml_content .= "<name>" . htmlspecialchars($skin_arr['name']) . "</name>\n";
  $xml_content .= "<author_name>" . htmlspecialchars($skin_arr['authorname']) . "</author_name>\n";
  $xml_content .= "<author_link>" . htmlspecialchars($skin_arr['authorlink']) . "</author_link>\n";
  $xml_content .= "<folder_name>" . htmlspecialchars($skin_arr['folder_name']). "</folder_name>\n";

  //SD370: added mobile data
  $write_arr = array('header','footer','mobile_header','mobile_footer','error_page',
                     'menu_level0_opening','menu_level0_closing',
                     'menu_submenu_opening','menu_submenu_closing',
                     'menu_item_opening','menu_item_closing','menu_item_link',
                     'mobile_menu_level0_opening','mobile_menu_level0_closing',
                     'mobile_menu_submenu_opening','mobile_menu_submenu_closing',
                     'mobile_menu_item_opening','mobile_menu_item_closing',
                     'mobile_menu_item_link');
  foreach($write_arr as $write_item)
  {
    //SD370: escape CDATA sections within content (e.g. Javascript)
    $output = '';
    if(isset($skin_arr[$write_item]) && strlen($skin_arr[$write_item]))
    {
      $output = str_replace(']]>', ']]]]><![CDATA[>', $skin_arr[$write_item]);
    }
    $xml_content .= "<$write_item><![CDATA[".$output."]]></$write_item>\n";
  }

  // Export layouts
  if($getrows = $DB->query('SELECT layout, design_name'.
                           ' FROM {designs}'.
                           ' WHERE skinid = %d'.
                           ' ORDER BY designid ASC', $skinid))
  {
    $counter = 1;
    while($row = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
    {
      $row['design_name'] = trim($row['design_name']);
      $row['layout'] = trim($row['layout']);

      // SD313: only export layouts with name or contents
      if(strlen($row['layout']) || strlen($row['design_name']))
      {
        //SD331: enumerate empty layout names
        if(!strlen($row['design_name']))
        {
          $row['design_name'] = 'Layout '.$counter;
          $counter++;
        }
        //SD370: escape CDATA sections within content (e.g. Javascript)
        $output = '';
        if(isset($row['layout']) && strlen($row['layout']))
        {
          $output = str_replace(']]>', ']]]]><![CDATA[>', $row['layout']);
        }
        $xml_content .= '<layout design_name="'.$row['design_name'].
                        "\"><![CDATA[\n".$output."\n]]></layout>\n";
      }
    } //while
  }

  // Export CSS entries
  if(GetVar('exp_all_css', 0, 'string', true,false))
  {
    if($getrows = $DB->query('SELECT DISTINCT plugin_id, var_name, mobile, max(skin_css_id) skin_css_id
                              FROM {skin_css}
                              WHERE skin_id IN (0,%d)
                              GROUP BY plugin_id, var_name
                              ORDER BY skin_id DESC, plugin_id ASC, var_name ASC',
                              $skinid))
    {
      $count = $DB->get_num_rows($getrows);
      while($css_item = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
      {
        if($css_arr = $DB->query_first('SELECT * FROM '.PRGM_TABLE_PREFIX.'skin_css'.
                                       ' WHERE skin_css_id = '.(int)$css_item['skin_css_id']))
        {
          $xml_content .= '<css var_name="'.trim($css_arr['var_name']).
                          '" plugin_id="'.(int)$css_arr['plugin_id'].
                          "\" mobile=\"".(int)$css_arr['mobile']."\"><![CDATA[\n".
                          $css_arr['css']."\n]]></css>\n";
        }
      }
    }
  }

  $xml_content .= "\n</theme>";

  $GLOBALS['sd_ignore_watchdog'] = true;
  if(!empty($handle))
  {
    @fwrite($handle, $xml_content);
    @fclose($handle);
    @chmod($xml_file_path, intval(0666,8));

    echo '<strong>'.AdminPhrase('skins_skin_exported').'</strong><br />';

    //SD360: create gzip/zip archive of skin folder?
    global $gzip_support, $zip_support;
    $destination = realpath('./backup');

    if(($gzip_support) && GetVar('exp_gzip', 0, 'bool', true, false))
    {
      if(!sd_safe_mode())
      {
        echo '<br /><h2>GZip backup of skin folder</h2>';
        $src_folder = dirname(realpath('./')).'/skins/'.basename($folder);
        echo '<br />Source Folder: '.$src_folder.'<br />';
        if(!empty($src_folder))
        {
          $fname = basename($folder).'_'.time().'.tar.gz';
          $command = "tar -C ".escapeshellarg($src_folder).' -zc --verbose --file='.escapeshellarg($destination.'/'.$fname).' .';
          #echo '<br />TAR command line:<br />'.$command;
          echo '<br />Command output:<br /><pre>';
          $output = system(escapeshellcmd($command), $result);
          echo '</pre>';
          if(empty($result)) //0 = success
            #echo '<strong>Success! Please check admin/backup folder for backup file.</strong><br /><br />';
            echo '<br /><strong>GZip file successfully created: '.$destination.'/'.$fname.'</strong><br />';
          else
            echo '<strong>GZip backup failed, sorry! TAR may not be available on your system.</strong><br /><br />';
        }
      }
    }

    if(($zip_support) && GetVar('exp_zip', 0, 'bool', true, false))
    {
      echo '<br /><h2>Zip backup of skin folder</h2><br />';
      $fname = basename($folder).'_'.time().'.zip';
      if(!CreateZipFile($folder, $destination, $fname))
      {
        return false;
      }
    }

    echo '<br /><br />';
    $GLOBALS['sd_ignore_watchdog'] = false;
    RedirectPage(SELF_SKINS_PHP.'?action=display_skins',AdminPhrase('skins_skin_exported'),5);
    return true;
  }

  DisplayExportSkinForm(AdminPhrase('skins_err_exp_failed'));

} //ExportSkin


function ZipStatusString( $status )
{
  switch( (int) $status )
  {
    case ZipArchive::ER_OK           : return 'N No error';
    case ZipArchive::ER_MULTIDISK    : return 'N Multi-disk zip archives not supported';
    case ZipArchive::ER_RENAME       : return 'S Renaming temporary file failed';
    case ZipArchive::ER_CLOSE        : return 'S Closing zip archive failed';
    case ZipArchive::ER_SEEK         : return 'S Seek error';
    case ZipArchive::ER_READ         : return 'S Read error';
    case ZipArchive::ER_WRITE        : return 'S Write error';
    case ZipArchive::ER_CRC          : return 'N CRC error';
    case ZipArchive::ER_ZIPCLOSED    : return 'N Containing zip archive was closed';
    case ZipArchive::ER_NOENT        : return 'N No such file';
    case ZipArchive::ER_EXISTS       : return 'N File already exists';
    case ZipArchive::ER_OPEN         : return 'S Can\'t open file';
    case ZipArchive::ER_TMPOPEN      : return 'S Failure to create temporary file';
    case ZipArchive::ER_ZLIB         : return 'Z Zlib error';
    case ZipArchive::ER_MEMORY       : return 'N Malloc failure';
    case ZipArchive::ER_CHANGED      : return 'N Entry has been changed';
    case ZipArchive::ER_COMPNOTSUPP  : return 'N Compression method not supported';
    case ZipArchive::ER_EOF          : return 'N Premature EOF';
    case ZipArchive::ER_INVAL        : return 'N Invalid argument';
    case ZipArchive::ER_NOZIP        : return 'N Not a zip archive';
    case ZipArchive::ER_INTERNAL     : return 'N Internal error';
    case ZipArchive::ER_INCONS       : return 'N Zip archive inconsistent';
    case ZipArchive::ER_REMOVE       : return 'S Can\'t remove file';
    case ZipArchive::ER_DELETED      : return 'N Entry has been deleted';

    default: return sprintf('Unknown status %s', $status );
  }
}

function CreateZipFile($source, $dest_path, $dest_file, $silent=0) //SD360
{
  global $zip_support;
  if(empty($zip_support) ||
     !class_exists('RecursiveIteratorIterator'))
  {
    return false;
  }

  $archiv = new ZipArchive();
  $res = $archiv->open($dest_path.'/'.$dest_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
  if(!$res)
  {
    var_dump($res);
    return false;
  }

  $source = str_replace("\\", '/', realpath($source));
  if(is_dir($source) === true)
  {
    $dirIter = new RecursiveDirectoryIterator($source);
    $iter = new RecursiveIteratorIterator($dirIter);

    foreach($iter as $element)
    {
      $fname = $element->getFilename();
      if(substr($fname,0,1)=='.') continue;

      $res  = true;
      $path = str_replace("\\", '/', $element->getPath());
      $dir  = str_replace($source, '', $path) . '/';
      $file = $path . '/' . $fname;
      if ($element->isDir())
      {
        $archiv->addEmptyDir(substr($dir,1));
        echo 'added <strong>'.$dir.'</strong>: '.$archiv->getStatusString().'<br />';
      }
      else
      if($element->isFile() && file_exists($file))
      {
        $fileInArchiv = substr($dir . $fname,1);
        // "substr()" removes leading "/" which causes "Close" to fail on Win
        $archiv->addFile($file, $fileInArchiv);
        echo 'added <strong>'.$fileInArchiv.'</strong>: '.$archiv->getStatusString().'<br />';
        /*
        $contents = file_get_contents($file);
        if($contents !== false)
        {
          $archiv->addFromString($fileInArchiv, $contents);
          unset($contents);
          echo 'added <strong>'.$fileInArchiv.'</strong>: '.$archiv->getStatusString().'<br />';
        }
        */
      }
      if($archiv->status <> 0) break;
    }
    unset($iter,$dirIter);
  }
  else
  if(is_file($source) === true)
  {
    $archiv->addFromString(basename($source), file_get_contents($source));
  }

  // Save a comment
  $archiv->setArchiveComment('Backup ' . basename($source));
  // save and close
  $res = $archiv->close();
  if(empty($silent))
  {
    if($archiv->status == 0)
    {
      echo '<br /><strong>ZIP file successfully created: '.$dest_path.'/'.$dest_file.'</strong><br />';
    }
    else
    {
      echo '<br /><strong>ERROR: '.ZipStatusString($archiv->status).'</strong><br />';
    }
  }
  return $res;

} //CreateZipFile


// ############################################################################
// INSERT LAYOUT
// ############################################################################

function InsertLayout()
{
  global $DB, $sdlanguage, $userinfo, $isAjax, $layout_number, $skinid, $token;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_SKINS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  if($skinid > 0)
  {
    $DB->query("INSERT INTO {designs} (designid, skinid, layout, design_name) VALUES (NULL, %d, '[HEADER]\n[FOOTER]', 'New Layout')", $skinid);
    if($new_design_id = $DB->insert_id())
    {
      $DB->query('UPDATE {skins} SET numdesigns = (SELECT COUNT(*) FROM {designs} WHERE skinid = %d) WHERE skinid = %d',$skinid,$skinid);

      if($isAjax)
      {
        die("$new_design_id");
      }
      RedirectPage(SELF_SKINS_PHP.'?skinid='.$skinid.'&structure=layout&layout_number='.
                   $layout_number.'&amp;designid='.$new_design_id,
                   AdminPhrase('skins_layout_inserted'));
      return true;
    }
  }

  if($isAjax)
  {
    die('Permission denied!');
  }
  RedirectPage(SELF_SKINS_PHP.'?skinid='.$skinid,
               AdminPhrase('skins_layout_not_inserted'), 2, true);
  return false;

} //InsertLayout


// ############################################################################
// DELETE LAYOUT
// ############################################################################

function DeleteLayout()
{
  global $DB, $sdlanguage, $isAjax, $skinid, $designid;

  $error = false;
  if(!CheckFormToken())
  {
    $error = $sdlanguage['error_invalid_token'];
  }

  if(($skinid < 1) || ($designid < 1))
  {
    $error = AdminPhrase('skins_layout_not_deleted');
  }

  // make sure there are no pages using this design
  if($page_arr = $DB->query_first('SELECT categoryid FROM {categories}'.
                                  ' WHERE designid = %d LIMIT 1',
                                  $designid))
  {
    $error = AdminPhrase('skins_cant_delete_pages_exist');
  }

  if((false !== $error))
  {
    if($isAjax)
    {
      die($error);
    }
    RedirectPage(SELF_SKINS_PHP, $error, 2, true);
    return false;
  }

  $DB->query('DELETE FROM {designs} WHERE designid = %d AND skinid = %d',
             $designid, $skinid);
  if($isAjax)
  {
    die(AdminPhrase('skins_layout_deleted'));
  }

  RedirectPage(SELF_SKINS_PHP, AdminPhrase('skins_layout_deleted'));
  return true;

} //DeleteLayout


// ############################################################################
// INSERT SKIN
// ############################################################################

function InsertSkin()
{
  global $DB, $sdlanguage, $userinfo;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_SKINS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  $skin_name   = trim(GetVar('skin_name', AdminPhrase('skins_untitled'), 'string',true,false));
  $authorname  = trim(GetVar('authorname', $userinfo['username'], 'string',true,false));
  $authorlink  = trim(GetVar('authorlink', '', 'string',true,false));
  $folder_name = trim(GetVar('folder_name', '', 'string',true,false));

  // SD360: Check folder name and bail if invalid
  if( (substr($folder_name,0,1)=='.') || substr_count($folder_name, '..') ||
      !sd_check_foldername($folder_name) )
  {
    RedirectPage(SELF_SKINS_PHP,'<strong>'.AdminPhrase('skins_err_invalid_filefolder').'</strong><br />',2,true);
    return;
  }


  // SD360: Check folder name and bail if invalid
  $errors = array();
  if(!strlen($skin_name))   $errors[] = 'Skin name missing';
  if(!strlen($authorname))  $errors[] = 'Author name missing';
  if(!strlen($folder_name)) $errors[] = 'Folder name missing';
  if(!empty($errors))
  {
    DisplayCreateSkin($errors);
    return;
  }

  //SD360: enable copying over skin row values (optional)
  $clone_skin = false;
  $skin_arr = array();
  $clone_skin_id = Is_Valid_Number(GetVar('clone_skin_id', 0, 'whole_number',true,false),0,1,999999);
  if($clone_skin_id)
  {
    $DB->result_type = MYSQL_ASSOC;
    if($skin_arr = $DB->query_first('SELECT * FROM {skins}'.
                                    ' WHERE skinid = %d LIMIT 1',
                                    $clone_skin_id))
    {
      $clone_skin = !empty($skin_arr);
    }
  }

  // copy values if present, otherwise empty
  $header               = isset($skin_arr['header'])?$skin_arr['header']:'';
  $footer               = isset($skin_arr['footer'])?$skin_arr['footer']:'';
  $error_page           = isset($skin_arr['error_page'])?$skin_arr['error_page']:'';
  $menu_level0_opening  = isset($skin_arr['menu_level0_opening'])?$skin_arr['menu_level0_opening']:'';
  $menu_level0_closing  = isset($skin_arr['menu_level0_closing'])?$skin_arr['menu_level0_closing']:'';
  $menu_submenu_opening = isset($skin_arr['menu_submenu_opening'])?$skin_arr['menu_submenu_opening']:'';
  $menu_submenu_closing = isset($skin_arr['menu_submenu_closing'])?$skin_arr['menu_submenu_closing']:'';
  $menu_item_opening    = isset($skin_arr['menu_item_opening'])?$skin_arr['menu_item_opening']:'';
  $menu_item_closing    = isset($skin_arr['menu_item_closing'])?$skin_arr['menu_item_closing']:'';
  $menu_item_link       = isset($skin_arr['menu_item_link'])?$skin_arr['menu_item_link']:'';

  $DB->query('INSERT INTO {skins}
              (skin_engine, numdesigns, name, authorname, authorlink, folder_name,
               header, footer, error_page,
               menu_level0_opening, menu_level0_closing,
               menu_submenu_opening, menu_submenu_closing,
               menu_item_opening, menu_item_closing,
               menu_item_link) VALUES'.
              "(2, 1, '%s', '%s', '%s', '%s', '%s', '%s', '%s',
               '%s', '%s', '%s', '%s',
               '%s', '%s', '%s')",
              $DB->escape_string($skin_name),
              $DB->escape_string($authorname),
              $DB->escape_string($authorlink),
              $DB->escape_string($folder_name),
              $DB->escape_string($header),
              $DB->escape_string($footer),
              $DB->escape_string($error_page),
              $DB->escape_string($menu_level0_opening),
              $DB->escape_string($menu_level0_closing),
              $DB->escape_string($menu_submenu_opening),
              $DB->escape_string($menu_submenu_closing),
              $DB->escape_string($menu_item_opening),
              $DB->escape_string($menu_item_closing),
              $DB->escape_string($menu_item_link));

  if($skinid = $DB->insert_id())
  {
    // #########################################################
    //SD360: process extra options for new skin
    // #########################################################
    $GLOBALS['sd_ignore_watchdog'] = true;
    $dest_folder = ROOT_PATH.'skins/'.$folder_name;

    // ###########################
    // Create destination folder?
    // ###########################
    if(GetVar('create_folder', false, 'bool',true,false))
    {
      // If folder exists, contents will be overwritten
      // and permissions set:
      $old = umask(0);
      if(!is_dir($dest_folder))
      {
        @mkdir($dest_folder, intval("0777", 8), true);
      }
      // MUST set 0777 or FTP user may not be able to unlink
      // (primarily issue on un*x servers: different uid's)
      @chmod($dest_folder, intval("0777", 8));
      clearstatcache();
    }

    // ###########################
    // Copy skin folder contents?
    // ###########################
    if($clone_skin)
    {
      if( strlen($skin_arr['folder_name']) &&
          GetVar('copy_files', false, 'bool',true,false))
      {
        // Check if source/destination folders exist
        // and are accessible
        $src_folder  = ROOT_PATH.'skins/'.$skin_arr['folder_name'];
        $src_exists  = is_dir($src_folder) &&
                       is_readable($src_folder);
        $dest_exists = is_dir($dest_folder);
        if($dest_exists && !is_writable($dest_folder))
        {
          @chmod($dest_folder, intval("0777", 8));
          $dest_exists = is_writable($dest_folder);
        }

        // Copy files/folders from source to destination:
        if($src_exists && $dest_exists)
        try {
          sd_xcopy($src_folder, $dest_folder);
        }
        catch (Exception $e){}
        @umask($old);
      }

      // #############################
      // Copy over all skin layouts?
      // #############################
      if(GetVar('copy_layouts', false, 'bool',true,false))
      {
        $count = 0;
        if($getrows = $DB->query('SELECT * FROM {designs}'.
                                 ' WHERE skinid = %d'.
                                 ' ORDER BY designid ASC',
                                 $clone_skin_id))
        {
          $count = $DB->get_num_rows($getrows);
          while($row = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
          {
            $DB->query('INSERT INTO {designs} (designid, skinid, maxplugins,'.
                       ' layout, design_name, designpath, imagepath)'.
                       " VALUES (NULL, %d, %d, '%s', '%s', '', '')",
                       $skinid, $row['maxplugins'],
                       $DB->escape_string($row['layout']),
                       $DB->escape_string($row['design_name']));
          }
        }
        $DB->query('UPDATE {skins} SET numdesigns = %d'.
                   ' WHERE skinid = %d',
                   $count, $skinid);
      }


      // ################################
      // Copy over all skin CSS entries?
      // ################################
      if(GetVar('copy_css', false, 'bool',true,false))
      {
        if($getrows = $DB->query('SELECT * FROM {skin_css} sc'.
                                 ' WHERE sc.skin_id IN(%d,0)'.
                                 ' AND sc.var_name IS NOT NULL'.
                                 " ORDER BY sc.skin_id DESC, sc.plugin_id ASC, sc.var_name",
                                 $clone_skin_id))
        {
          $css_vars = array();
          while($row = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
          {
            $key = $row['plugin_id'].'|'.$row['var_name'];
            if(!array_key_exists($key,$css_vars))
            {
              $css_vars[$key] = $row;
            }
          }
          if(count($css_vars)) @ksort($css_vars);

          foreach($css_vars as $row)
          {
            $DB->query('INSERT INTO {skin_css} (skin_css_id, skin_id, plugin_id, var_name,'.
                       ' css, admin_only, disabled, description)'.
                       " VALUES (NULL, %d, %d, '%s',
                         '%s', %d, %d, '%s')",
                       $skinid, $row['plugin_id'],
                       $DB->escape_string($row['var_name']),
                       $DB->escape_string($row['css']),
                       $row['admin_only'],
                       $row['disabled'],
                       $DB->escape_string($row['description']) );
          }
        }
      }
    }
    else
    {
      $DB->query('INSERT INTO {designs} (designid, skinid, maxplugins,
                  layout, design_name, designpath, imagepath) VALUES'.
                 "(NULL, %d, 1, '', 'New', '', '')",
                 $skinid);
    }
    $GLOBALS['sd_ignore_watchdog'] = false;
  }

  RedirectPage(SELF_SKINS_PHP.'?action=display_skins',
               AdminPhrase('skins_skin_created'));

} //InsertSkin


// ############################################################################
// DISPLAY SKIN
// ############################################################################

function GetLayouts($skin_arr, $designid)
{
  global $DB;

  $layout_count = 1;
  if($get_layouts = $DB->query('SELECT designid, design_name FROM {designs} WHERE skinid = %d'.
                               " ORDER BY IF(IFNULL(design_name,'') = '', 1, 0) ASC, design_name ASC, designid ASC",
                               $skin_arr['skinid']))
  {
    for($layout_count = 1; $row = $DB->fetch_array($get_layouts,null,MYSQL_ASSOC); $layout_count++)
    {
      $title = (strlen($row['design_name']) ? $row['design_name'] : AdminPhrase('skins_layout') . ' ' . $row['designid']);
      $class = $row['designid'] == $designid ? ' current-item' : '';
      echo '
      <div id="layout_' . $row['designid'] . '" class="layout-link-container'.$class.'">
        <a title="'.htmlspecialchars($title,ENT_COMPAT).'" class="layout-link "'.
        ' target="iframe-content" href="skinid='.$skin_arr['skinid'] .
        '&amp;structure=layout&amp;layout_number=' . $layout_count .
        '&amp;designid=' . $row['designid'] . '"><i class="ace-icon fa fa-edit blue bigger-110"></i> '.$title.'</a>
        <a class="delete-layout-link" onclick="javascript:;" title="'.
        AdminPhrase('skins_delete_layout').'" href="&amp;skinid='.$skin_arr['skinid'].
        '&amp;designid='.$row['designid'].
        '"><i class="ace-icon fa fa-trash-o red bigger-110"></i></a>
      </div>';
    }
  }

} //GetLayouts


// ############################################################################
// DISPLAY SKIN
// ############################################################################

function DisplaySkin()
{
  global $DB, $mainsettings, $css_var_name, $designid, $layout_number,
         $plugin_names, $skinid, $structure;

  $layout_count  = 0;

  // get skin
  $where = !empty($skinid) ? 'skinid = '.(int)$skinid : 'activated = 1';
  $DB->result_type = MYSQL_ASSOC; //SD343
  if(!$skin_arr = $DB->query_first('SELECT * FROM {skins} WHERE '.$where.' LIMIT 1'))
  {
    DisplayMessage('Skin installation error!',true);
    return false;
  }
  $skinid = (int)$skin_arr['skinid'];

  // remove invalid CSS entries
  if($skinid)
  {
    $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'skin_css WHERE skin_id IN (0, %d) AND plugin_id > 0 '.
               'AND NOT EXISTS (SELECT 1 FROM '.PRGM_TABLE_PREFIX.'plugins pl '.
               ' WHERE pl.pluginid = '.PRGM_TABLE_PREFIX.'skin_css.plugin_id)',
               $skin_arr['skinid']);
  }

  //SD340: moved most JS code to "/admin/javascript/page_skins.js"!

  $legacy_skin = ($skin_arr['skin_engine'] != 2);

  //Display Skin functions, Layouts and CSS parts
  //SD340: Editor hotkeys help popup
  echo '
  <div id="hotkeys" style="display: none">
    <table border="0" cellpadding="0" cellspacing="4" summary="Hotkeys" width="100%">
    <tr><td>Ctrl-S</td><td>SAVE</td></tr>
    <tr><td>Ctrl-A</td><td>Select All</td></tr>
    <tr><td>Ctrl-D</td><td>Duplicate Line</td></tr>
    <tr><td>Ctrl-Y</td><td>Delete Line</td></tr>
    <tr><td>Ctrl-F</td><td>Find Text popup</td></tr>
    <tr><td>Ctrl-K</td><td>Repeat Find</td></tr>
    <tr><td>Ctrl-Shift-K</td><td>Repeat Find Backwards</td></tr>
    <tr><td>Ctrl-H</td><td>Replace Text</td></tr>
    <tr><td>Ctrl-Alt-L</td><td>Goto Line Number</td></tr>
    <tr><td>Ctrl-T</td><td>Transpose Letters</td></tr>
    <tr><td>Ctrl-Shift-Z</td><td>Redo</td></tr>
    <tr><td>Ctrl-Z</td><td>Undo</td></tr>
    <tr><td>Ctrl-Up/Down</td><td>Scroll text line up/down</td></tr>
    <tr><td>Ctrl-Pos1/End</td><td>Goto start/end of Text</td></tr>
    <tr><td>Alt-Left/Right</td><td>Goto start/end of Line</td></tr>
    <tr><td>Alt-Up/Down</td><td>Move Current Line Up/Down</td></tr>
    <tr><td>Ctrl-Alt+Cursor keys</td><td>Start multi-select mode (press ESC to end)</td></tr>
    <tr><td>Alt-0</td><td>Fold all sections (e.g. in CSS)</td></tr>
    <tr><td>Alt-Shift+0</td><td>Unfold all folded sections</td></tr>
    <tr><td>Alt-L</td><td>Fold section at current position (see Gutter symbols)</td></tr>
    <tr><td>Alt-Shift-L</td><td>Unfold code item at current position</td></tr>
    <tr><td>Ctrl-P</td><td>Find matching parenthesis</td></tr>
    <tr><td>Ctrl-Shift-P</td><td>Select to matching parenthesis</td></tr>
    </table>
  </div>
  
  <div class="table-header"><i class="ace-icon fa fa-paint-brush"></i>  '. $skin_arr['name'] .' ';
  if(strlen($skin_arr['folder_name']))
  {
    echo '<div class="pull-right"><i class="ace-icon fa fa-folder-open orange"></i> ./skins/'.$skin_arr['folder_name'].'/ &nbsp;</div>';
  }

  echo '</div>
  <table class="table" id="skin-layout-tbl" summary="" width="100%">
  	<tr>
    	<td id="items_cell" class="td2" valign="top">
    		<div id="items_container">
     	 		<div id="indicator"><img alt="*" src="'.SITE_URL.'includes/css/images/indicator.gif" height="18" width="18" /></div>';

  if(!$legacy_skin)
  {
    // Layouts: max. 10 items; scrollable vertically
    //SD370: added links for mobile elements
    echo '
      <a id="header-link" class="iframe-link '.($structure=='header'?' current-item':'').'" target="iframe-content" href="skinid='.$skinid.
      '&amp;structure=header"><i class="ace-icon fa fa-edit bigger-110"></i> ' . AdminPhrase('skins_header') . '</a>
      <a id="footer-link" class="iframe-link '.($structure=='footer'?' current-item':'').'" target="iframe-content" href="skinid='.$skinid.
      '&amp;structure=footer"><i class="ace-icon fa fa-edit bigger-110"></i> ' . AdminPhrase('skins_footer') . '</a>
      <a id="menu-link" class="iframe-link '.($structure=='menu'?' current-item':'').'" target="iframe-content" href="skinid='.$skinid.
      '&amp;structure=menu"><i class="ace-icon fa fa-edit bigger-110"></i> ' . AdminPhrase('skins_menu_settings') . '</a>
      <a id="header-link" class="iframe-link '.($structure=='mobile_header'?' current-item':'').'" target="iframe-content" href="skinid='.$skinid.
      '&amp;structure=mobile_header"><i class="ace-icon fa fa-edit bigger-110"></i> ' . AdminPhrase('skins_mobile_header') . '</a>
      <a id="footer-link" class="iframe-link '.($structure=='mobile_footer'?' current-item':'').'" target="iframe-content" href="skinid='.$skinid.
      '&amp;structure=mobile_footer"><i class="ace-icon fa fa-edit bigger-110"></i> ' . AdminPhrase('skins_mobile_footer') . '</a>
      <a id="menu-link" class="iframe-link '.($structure=='mobile_menu'?' current-item':'').'" target="iframe-content" href="skinid='.$skinid.
      '&amp;structure=mobile_menu"><i class="ace-icon fa fa-edit bigger-110"></i> ' . AdminPhrase('skins_mobile_menu_settings') . '</a>
      <span id="layouts_hint">'.AdminPhrase('skins_layout_hint').'</span>

      <div class="skin-items" id="skin-layouts">
        <input type="hidden" id="skinid" value="'.$skin_arr['skinid'].'" />
        ';

    // Output skin layouts
    GetLayouts($skin_arr, $designid);

    echo '
      </div>
      <a class="iframe-link " target="iframe-content" href="skinid='.
      $skin_arr['skinid'].'&amp;structure=error_page"><i class="ace-icon fa fa-edit bigger-110"></i> '.
      AdminPhrase('skins_error_page').'</a>
      <a class="iframe-link-no-highlight" id="insertLayout" href="skins.php?action=insert_layout&amp;skinid='.
        $skin_arr['skinid'].SD_URL_TOKEN.'"><i class="ace-icon fa fa-plus green bigger-110"></i> '.
        AdminPhrase('skins_create_new_layout').'</a>';
  }
  else
  {
    //SD370: make error page editable for SD2 skins
    echo '
      <a id="error-page" class="iframe-link  current-item" target="iframe-content" href="skinid='.
      $skin_arr['skinid'].'&amp;structure=error_page">'.
      AdminPhrase('skins_error_page').'</a>';
  }

  //SD313: CSS items; max. 10 items; vertically scrollable
  echo '
      <span id="skins_css_hint">' . AdminPhrase('skins_css') . ':</span>
      <div class="skin-items" id="skin-css">
        ';

  // Output CSS skin items
  //SD343: join on plugin_id was missing in sub-select
  if($get_css = $DB->query(
     'SELECT sc.skin_css_id, sc.var_name, sc.plugin_id, sc.disabled
      FROM '.PRGM_TABLE_PREFIX.'skin_css sc
      WHERE sc.skin_id IN ('.$skin_arr['skinid'].', 0)
      AND sc.skin_css_id = (SELECT MAX(skin_css_id) FROM '.PRGM_TABLE_PREFIX.'skin_css sc2
        WHERE sc2.plugin_id = sc.plugin_id AND sc2.var_name = sc.var_name AND sc2.skin_id IN ('.$skin_arr['skinid'].',0))
      ORDER BY sc.plugin_id, sc.var_name ASC, sc.skin_id DESC'))
  {
    $pluginid = Is_Valid_Number(GetVar('pluginid', 0, 'whole_number'),0,1,99999);
    while($css_arr = $DB->fetch_array($get_css,null,MYSQL_ASSOC))
    {
      $row_var_name = $css_arr['var_name'];
      $pid = empty($css_arr['plugin_id']) ? 0 : (int)$css_arr['plugin_id'];
      if(!empty($pid) && isset($plugin_names[$pid]))
      {
        $row_var_name = $plugin_names[$pid]." ($pid)";
      }
      $class = '';
      if( ($structure=='css') && !empty($pluginid) && isset($plugin_names[$pluginid]) &&
          ($pid == $pluginid) )
      {
        $class = ' current-item';
      }

      //SD342: added toggle (image) for enabling/disabling CSS entries:
      if(empty($css_arr['disabled']))
      {
        $img = '<i class="ace-icon fa fa-eye green bigger-110"></i>';
        $tag = 0;
      }
      else
      {
        $img = '<i class="ace-icon fa fa-eye-slash red bigger-110"></i>';
        $tag = 1;
      }
      //SD362: added title and css-link class
      echo '
      <div id="css_'.$css_arr['skin_css_id'].'" class="layout-link-container'.$class.'">
        <a class="iframe-link css-link '.$class.
        '" target="iframe-content"'.
        ' title="'.$css_arr['var_name'].'"'.
        ' href="skinid='.$skin_arr['skinid'].'&amp;structure=css&amp;css_var_name='.
        urlencode($css_arr['var_name']).
        (!empty($pid)?'&amp;pluginid='.$pid:'').
        '"><i class="ace-icon fa fa-edit bigger-110"></i> '.$row_var_name.'</a>
        <a class="css-status-link" title="'.AdminPhrase('hint_enable_disable').'" rel="'.
        $tag.'" onclick="javascript:;" href="'.$css_arr['skin_css_id'].'">'.$img.'</a>
      </div>';
    }
  }

  echo '
      </div> <!-- skin_items container -->
      </div> <!-- items_container -->
    </td>
    <td class="content_cell" valign="top">
      <div id="iframe-content"></div>
    </td>
  </tr>
  </table>
  <input type="hidden" id="layout_count" value="'.$layout_count.'" />
  <input type="hidden" id="legacy_skin" value="'.($legacy_skin?1:0).'" />
  <div style="clear:both;display:block;width:100%;height:2px;"> </div>';

} //DisplaySkin


// ############################################################################
// SELECT FUNCTION
// ############################################################################

$function_name = str_replace('_', '', $action);

if(is_callable($function_name))
{
  call_user_func($function_name);

  // SD313: clear cache if there's any change-involved action:
  if(in_array(strtolower($function_name),
     array('deletelayout','insertlayout','insertskin','installskin',
           'switchskin','uninstallskin')))
  {
    if(isset($SDCache) && ($SDCache instanceof SDCache))
    {
      $SDCache->purge_cache(true);
    }
  }
}
else
{
  DisplayMessage("Incorrect Function Call: $function_name()", true);
}

// ############################################################################
// DISPLAY ADMIN FOOTER
// ############################################################################

DisplayAdminFooter();
