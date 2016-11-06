<?php
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');

// INIT PRGM
include(ROOT_PATH . 'includes/init.php');

define('TEMPLATES_DATE_SORT_DESC', 'dateupdated DESC');
define('TEMPLATES_DATE_SORT_ASC', 'dateupdated ASC');
define('TEMPLATES_TYPE_SORT_ASC', 'tpl_type ASC');
define('TEMPLATES_TYPE_SORT_DESC', 'tpl_type DESC');
define('TEMPLATES_UNAME_SORT_ASC', 'userid ASC');
define('TEMPLATES_UNAME_SORT_DESC', 'userid DESC');
define('SD_TEMPLATES_MAX_ID', 99999999);
define('SD_TEMPLATES_MAX_PID', 99999);
define('SD_TEMPLATES_SELF', 'templates.php');
define('COOKIE_HL_THEME','_tmpl_hl_theme');

$action = str_replace('_', '', GetVar('action', 'displaytemplates', 'string'));
$templateid = Is_Valid_Number(GetVar('template_id', 0, 'whole_number'),1,0,SD_TEMPLATES_MAX_ID);
$pluginid = Is_Valid_Number(GetVar('pluginid', 0, 'natural_number'),0,0,SD_TEMPLATES_MAX_PID);


// ############################################################################
// PRE-PROCESS SPECIAL ACTIONS
// ############################################################################
if(Is_Ajax_Request())
{
  // UPDATE TEMPLATE (ajax call)
  if($action=='updatetemplate') //SD360
  {
    header("Content-Type: text/html; charset=".$sd_charset);
	$admin_phrases = LoadAdminPhrases(6); //SD 4.0.0
    CheckAdminAccess('templates');
    UpdateTemplate();
    $DB->close();
    exit();
  }
}
else
{
  // EXPORT TEMPLATE
  if($action == 'exporttemplate')
  {
    CheckAdminAccess('templates');
    ExportTemplate($pluginid, $templateid);
    exit();
  }
}


// ############################################################################
// CHECK PAGE ACCESS
// ############################################################################
CheckAdminAccess('templates');


// ############################################################################
// PREPARE JS, SUB MENU AND ADD PAGE HEAD CONTENT
// ############################################################################
$admin_phrases = LoadAdminPhrases(6); //Skins!
$fallback = empty($mainsettings['skins_enable_highlighting']); // global!

if($action=='displaytemplate')
{
	$css = array(ROOT_PATH . ADMIN_STYLES_FOLDER . 'assets/css/templates.css.php',
	SD_INCLUDE_PATH.'css/jquery.jdialog.css',);
  

  if(!$fallback)
  {
     $js = array(
					SD_JS_PATH . 'jquery-migrate-1.2.1.min.js',
					SD_JS_PATH . 'jquery.cookie.js',
					SD_JS_PATH .'ace/ace.js',
					SD_JS_PATH .'ace/mode-html.js',
					SD_JS_PATH .'ace/mode-html_completions.js',
					SD_JS_PATH .'ace/mode-css.js',
					SD_JS_PATH .'ace/mode-javascript.js',
					SD_JS_PATH .'ace/mode-php.js',
					SD_JS_PATH .'ace/theme-textmate.js',
					SD_JS_PATH .'ace/ext-keybinding_menu.js',
					SD_JS_PATH .'ace/ext-whitespace.js',
					SD_JS_PATH .'ace/ext-statusbar.js',
					SD_JS_PATH .'ace/ext-language_tools.js',
					SD_JS_PATH . 'jquery.blockui.min.js', //SD343
    				SD_JS_PATH . 'jquery.easing-1.3.pack.js',
    				SD_JS_PATH . 'jquery.jgrowl.min.js',
					SITE_URL . ADMIN_STYLES_FOLDER . 'assets/js/jquery.noty.packaged.min.js',
					SD_JS_PATH . 'jquery.form.min.js',
					SITE_URL . ADMIN_STYLES_FOLDER . '/assets/js/bootbox.min.js',
					SITE_URL . ADMIN_PATH . '/javascript/page_templates.js',
					SD_JS_PATH . 'jquery.jdialog.js');
  }

$script = '
<script type="text/javascript">
//<![CDATA[
 tmpl_declarations : {
    var tmpl_options = {
      cookie_name: "'.COOKIE_PREFIX.COOKIE_HL_THEME.'",
      fallback: '.($fallback?'true':'(jQuery.browser.msie && (parseInt(jQuery.browser.version, 10) < 9))').',
      includes_path: "'.SD_INCLUDE_PATH.'",
      skins_token_name: "'.addslashes(SD_TOKEN_NAME).'",
      skins_token: "'.addslashes(SD_FORM_TOKEN).'",
      lang_editor_hotkeys: "' . addslashes(AdminPhrase('skins_editor_hotkeys')) . '",
      lang_template_updated: "' . addslashes(AdminPhrase('tmpl_template_updated')) . '",
      lang_abandon_changes: "' . addslashes(AdminPhrase('tmpl_abandon_changes')) . '",
      lang_restore_prompt: "' . addslashes(AdminPhrase('tmpl_restore_prompt')) . '"
    };
    var editor_top, ed_id = "#skineditor";
    if(tmpl_options.fallback) { ed_id = "#skincontent"; }
  }
  //]]>
</script>';

} # END 'displaytemplate'
else
if($action=='displaytemplates')
{
  $search = array();
  $customsearch = GetVar('customsearch', 0, 'bool');
  $clearsearch  = GetVar('clearsearch',  0, 'bool');

  // Restore previous search array from cookie
  $search_cookie = isset($_COOKIE[COOKIE_PREFIX . '_tmpl_search']) ? $_COOKIE[COOKIE_PREFIX . '_tmpl_search'] : false;

  if($clearsearch)
  {
    $search_cookie = false;
    $customsearch  = false;
  }

  if($customsearch)
  {
    $search = array('content'   => GetVar('searchcontent', '', 'string', true, false),
                    'active'    => GetVar('searchactive', 'all', 'string', true, false),
                    'username'  => GetVar('searchusername', '', 'string', true, false),
                    'pluginid'  => GetVar('searchpluginid', -1, 'int', true, true), #SD370: both true!
                    'limit'     => GetVar('searchlimit', 20, 'integer', true, false),
                    'sorting'   => GetVar('searchsorting', '', 'string', true, false),
                    'tpl_type'  => GetVar('searchtpl_type', '', 'string', true, false));

    // Store search params in cookie
    sd_CreateCookie('_tmpl_search',base64_encode(serialize($search)));
  }
  else
  {
    $searchpluginid = GetVar('pluginid', -1, 'int');
    if(!empty($searchpluginid))
    {
      $search['pluginid'] = (int)$searchpluginid;
    }

    if($search_cookie !== false)
    {
      $search = unserialize(base64_decode(($search_cookie)));
    }
  }

  if(empty($search) || !is_array($search))
  {
    $search = array('content'  => '',
                    'active'   => 'all',
                    'username' => '',
                    'pluginid' => -1,
                    'limit'    => 20,
                    'sorting'  => '',
                    'tpl_type' => '');

    // Remove search params cookie
    sd_CreateCookie('_tmpl_search', '');
  }

  $search['content'] = isset($search['content']) ? (string)$search['content'] : '';
  $search['active']   = isset($search['active'])   ? (string)$search['active'] : 'all';
  $search['username'] = isset($search['username']) ? (string)$search['username'] : '';
  $search['pluginid'] = isset($search['pluginid']) ? (int)$search['pluginid'] : '';
  $search['limit']    = isset($search['limit'])    ? Is_Valid_Number($search['limit'],20,5,100) : 20;
  $search['sorting']  = empty($search['sorting'])  ? TEMPLATES_DATE_SORT_DESC : $search['sorting'];
  //SD343: added sorting by IP
  $sort = in_array($search['sorting'], array(TEMPLATES_DATE_SORT_ASC,
                                             TEMPLATES_DATE_SORT_DESC,
                                             TEMPLATES_TYPE_SORT_ASC,
                                             TEMPLATES_TYPE_SORT_DESC,
                                             TEMPLATES_UNAME_SORT_ASC,
                                             TEMPLATES_UNAME_SORT_DESC)) ? $search['sorting'] : TEMPLATES_DATE_SORT_DESC;
  $search['sorting'] = $sort;
  $search['tpl_type']= isset($search['tpl_type']) ? (string)$search['tpl_type'] : '';

 $script = 
   //SD370: added delete confirmation prompt and "checkall" managing
  '<script type="text/javascript">
//<![CDATA[
if (typeof(jQuery) !== "undefined") {
jQuery(document).ready(function() {
  (function($){
    $("a#submit_templates").unbind("click");
    $("form#templates").unbind("submit").bind("submit",function(e) {
      var checked = $("form#templates input.deltemplate:checked").length;
      if(checked==0 || !ConfirmDelete("'.addslashes(AdminPhrase('tmpl_confirm_delete')).'")){
        e.preventDefault();
        return false;
      }
    });

    $("form#templates a#checkall").click(function(e){
      e.preventDefault();
      var ischecked = 1 - parseInt($(this).attr("rel"),10);
      if(ischecked==1) {
        $("form#templates input.deltemplate").attr("checked","checked");
        $("form#templates tr:NOT(thead tr)").addClass("danger");
      } else {
        $("form#templates input.deltemplate").removeAttr("checked");
        $("form#templates tr").removeClass("danger");
      }
      $(this).attr("rel",ischecked);
      return false;
    });

    $("input.deltemplate").on("change",function(){
      var tr = $(this).parents("tr");
      $(tr).toggleClass("danger");
    });

    $("form#searchtemplates select").change(function() {
      $("#searchtemplates").submit();
    });

    $("a#templates-submit-search").click(function() {
      $("#searchtemplates").submit();
    });

    $("a#templates-clear-search").click(function() {
      $("#searchtemplates input").val("");
      $("#searchtemplates select").prop("selectedIndex", 0);
      $("#searchtemplates input[name=clearsearch]").val("1");
      $("#searchtemplates").submit();
    });
  }(jQuery));
})
}
//]]>
</script>';

} # END 'displaytemplates'

 $sd_head->AddCSS($css);
 $sd_head->AddJS($js);
 $sd_head->AddScript($script);



// ############################################################################
// DISPLAY ADMIN HEADER
// ############################################################################

//SD370: try to prefetch template name and pass to page meta header:
$tpl_title = AdminPhrase('menu_templates');
if(($tplid = GetVar('template_id', 0, 'whole_number', false, true)) &&
   (GetVar('action', '', 'string', false, true)=='display_template'))
{
  $DB->result_type = MYSQL_ASSOC;
  if($row = $DB->query_first("SELECT t.displayname, t.pluginid".
                             ' FROM '.PRGM_TABLE_PREFIX.'templates t'.
                             ' WHERE template_id = %d', $tplid))
  {
    $tpl_title = $row['displayname'].' - '.
                 (isset($plugin_names[$row['pluginid']])?$plugin_names[$row['pluginid']].' - ':'').
                 $tpl_title;
  }
}


DisplayAdminHeader(array('Skins', $action), null, $tpl_title); //Templates has same head as Skins


// ############################################################################
// EXPORT TEMPLATE ("save file as" in browser)
// ############################################################################
function ExportTemplate($pluginid, $template_id)
{
  global $DB;

  if(!isset($pluginid) ||($pluginid < 0) || ($pluginid > SD_TEMPLATES_MAX_PID) ||
     empty($template_id) ||($template_id < 1) || ($template_id > SD_TEMPLATES_MAX_ID))
    return;

  if(!$row = $DB->query_first('SELECT t.template_id, t.tpl_name, r.revision_id, r.content'.
                              ' FROM '.PRGM_TABLE_PREFIX.'templates t'.
                              ' INNER JOIN '.PRGM_TABLE_PREFIX.'revisions r ON r.revision_id = t.revision_id'.
                              ' WHERE t.pluginid = %d AND t.template_id = %d',
                              $pluginid, $template_id))
  {
    return false;
  }

  Header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
  Header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
  Header("Cache-Control: no-store, no-cache, must-revalidate");
  Header("Cache-Control: post-check=0, pre-check=0", false);
  Header("Pragma: no-cache");
  Header("Content-Type: text/plain");
  Header("Content-Length: " . strlen($row['content']));
  Header('Content-Disposition: attachment; filename="' .$row['tpl_name'].'"');
  echo $row['content'];

} //ExportTemplate


// ############################################################################
// RESTORE TEMPLATE (from includes/tmpl/defaults folder)
// ############################################################################
function RestoreTemplate()
{
  global $DB, $sdlanguage, $pluginid, $templateid;

  if(!CheckFormToken())
  {
    RedirectPage(SITE_URL,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  if(!isset($pluginid) ||($pluginid < 0) || ($pluginid > SD_TEMPLATES_MAX_PID) ||
     empty($templateid) || ($templateid < 1) || ($templateid > SD_TEMPLATES_MAX_ID))
  {
    RedirectPage(SD_TEMPLATES_SELF.'?action=display_templates',
                 AdminPhrase('tmpl_template_not_found'),2,true);
    return false;
  }

  $DB->result_type = MYSQL_ASSOC;
  if(!$row = $DB->query_first('SELECT template_id, tpl_name'.
                              ' FROM '.PRGM_TABLE_PREFIX.'templates'.
                              ' WHERE pluginid = %d AND template_id = %d LIMIT 1',
                              $pluginid, $templateid))
  {
    RedirectPage(SD_TEMPLATES_SELF.'?action=display_templates',
                 AdminPhrase('tmpl_template_not_found'),2,true);
    return false;
  }

  $tpl_path = SD_INCLUDE_PATH.'tmpl/defaults/';
  include_once(SD_INCLUDE_PATH.'class_sd_smarty.php');
  SD_Smarty::AddTemplateRevisionFromFile($pluginid,$tpl_path, $row['tpl_name']);

  RedirectPage(SD_TEMPLATES_SELF.'?action=display_template&amp;template_id='.$templateid,
               AdminPhrase('tmpl_restore_msg'));
  return true;

} //RestoreTemplate


// ############################################################################
// UPDATE TEMPLATE
// ############################################################################

function UpdateTemplate()
{
  global $DB, $sdlanguage, $templateid, $pluginid;

  if(!CheckFormToken())
  {
    RedirectPage(SITE_URL,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  if(empty($templateid) || ($templateid < 1) || ($templateid > SD_TEMPLATES_MAX_ID) ||
     !isset($pluginid) || ($pluginid < 0) || ($pluginid > SD_TEMPLATES_MAX_PID)) return false;

  $content   = GetVar('content', '', 'html');
  $is_active = GetVar('is_active', 0, 'bool');
  $tpl_type  = 'Frontpage';//GetVar('tpl_type', '', 'string');
  $deletetemplate = GetVar('deletetemplate', 0, 'bool');

  if($deletetemplate)
  {
    SD_Smarty::DeleteTemplatebyId($pluginid, $templateid);
  }
  else
  {
    SD_Smarty::UpdateTmplRevisionByTmplIdFromVar($pluginid, $templateid, $content, $is_active, $tpl_type);
  }

  if(Is_Ajax_Request()) //SD360
  {
    echo AdminPhrase('tmpl_template_updated');
    return true;
  }

  RedirectPage(SD_TEMPLATES_SELF.'?action=display_template&amp;template_id='.$templateid, AdminPhrase('tmpl_template_updated'));

} //UpdateTemplate


// ############################################################################
// DELETE COMMENTS
// ############################################################################

function DeleteTemplates()
{
  global $DB, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage(SD_TEMPLATES_SELF,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  $delete_tmpl_id_arr = GetVar('delete', array(), 'array',true,false);
  $deleted = 0;
  $error = false;
  foreach($delete_tmpl_id_arr as $id => $check)
  {
    if(empty($check)) continue;
    if(!is_numeric($id) || (!$id = Is_Valid_Number($id,0,1,SD_TEMPLATES_MAX_ID)))
    {
      $error = true;
    }
    else
    {
      $DB->result_type = MYSQL_ASSOC;
      if(!$row = $DB->query_first('SELECT template_id, pluginid FROM {templates} WHERE template_id = %d',$id))
      {
        $error = true;
      }
    }
    if(md5($row['template_id'].PRGM_HASH.md5($row['pluginid'])) != $check)
    {
      $error = true;
    }
    if($error) break;
    SD_Smarty::DeleteTemplatebyId($row['pluginid'], $id);
    $deleted++;
  }
  if($error || !$deleted)
  {
    RedirectPage(SD_TEMPLATES_SELF,'<strong>'.AdminPhrase('tmpl_none_deleted').'</strong><br />',2,true);
    return false;
  }
  RedirectPage(SD_TEMPLATES_SELF, AdminPhrase('tmpl_templates_deleted'));
  return true;

} //DeleteTemplates


// ############################################################################
// DISPLAY TEMPLATE
// ############################################################################

function DisplayTemplate()
{
  global $DB, $sdurl, $fallback, $plugin_names, $templateid;

  $DB->result_type = MYSQL_ASSOC;
  $row = $DB->query_first("SELECT t.*, r.content, p.pluginid, ifnull(p.name,'') pluginname".
                          ' FROM '.PRGM_TABLE_PREFIX.'templates t'.
                          ' LEFT JOIN '.PRGM_TABLE_PREFIX.'revisions r ON r.revision_id = t.revision_id'.
                          ' LEFT JOIN '.PRGM_TABLE_PREFIX.'plugins p ON p.pluginid = t.pluginid'.
                          ' WHERE template_id = %d', $templateid);
  $row['pluginid'] = empty($row['pluginid'])?0:(int)$row['pluginid'];
  if(empty($row['template_id']))
  {
    DisplayMessage(AdminPhrase('tmpl_invalid_template_specified'),true);
    return false;
  }
  
  echo '<div class="col-sm-12 no-padding-left no-padding-right">
  			' . AdminPhrase('template_quick_chooser') . '&nbsp; <select id="jump_template">';

 // Display all templates for plugin starting with "subsections"
  if($t = SD_Smarty::GetTemplateNamesForPlugin($row['pluginid'], false))
  foreach($t as $idx => $tpl)
  {
    echo '<option value="'.$tpl['template_id'].'" '.
         ($tpl['template_id']==$row['template_id']?' selected="selected"':'').'>'.
         $tpl['displayname'].'</option>';
  }

  echo '
        </select> 
		<span class="pull-right">
		<a class="btn btn-info btn-sm" href="'.SD_TEMPLATES_SELF.'?action=exporttemplate&amp;pluginid='.$row['pluginid'].
      '&amp;template_id='.$templateid.'" target="_blank"><i class="ace-icon fa fa-download"></i> '.
      AdminPhrase('tmpl_export_to_file').'</a>';
  $tpl_path = SD_INCLUDE_PATH.'tmpl/defaults/'.$row['tpl_name'];
  if(file_exists($tpl_path) && is_readable($tpl_path))
  {
    echo ' &nbsp;&nbsp;
      <a id="restoretpl" class="btn btn-sm btn-warning" href="'.SD_TEMPLATES_SELF.'?action=restoretemplate&amp;pluginid='.$row['pluginid'].
      '&amp;template_id='.$templateid.PrintSecureUrlToken().'"><i class="ace-icon fa fa-refresh"></i> '.
      AdminPhrase('tmpl_restore_original').'</a>';
  }
  
  echo'</span></div>
		<div class="clearfix"></div>
		<div class="space-2"></div>';

  StartSection(($row['pluginname']?$plugin_names[$row['pluginid']]:AdminPhrase('tmpl_core_templates')).' - '.$row['displayname'] . '<div class="pull-right"><i class="ace-icon fa fa-calendar"></i>&nbsp; Updated: '.DisplayDate($row['dateupdated']).'&nbsp;</div>');
  require_once(SD_INCLUDE_PATH.'class_sd_smarty.php');

  echo '
  <table class="table table-bordered">
  <tr>
  	<td>
  <form id="tmplform" action="'.SD_TEMPLATES_SELF.'?action=update_template" method="post">
  '.PrintSecureToken().'
  <input type="hidden" id="template_id" name="template_id" value="' . $row['template_id'] . '" />
  <input type="hidden" name="pluginid" value="' . $row['pluginid'] . '" />
  <input type="hidden" name="original_status" value="' . $row['is_active'] . '" />
  <input type="hidden" id="content_changed" value="0" />';
  if(!empty($row['is_active']) && (empty($row['pluginname']) || !empty($row['system_only'])))
  {
    echo '<input type="hidden" name="is_active" value="1" />';
  }
  echo '
 		<ul class="list-unstyled">
			<li><i class="ace-icon fa fa-arrow-right blue"></i>'
			 . AdminPhrase('tmpl_displayname') . ': '.$row['displayname'].'</li>
			 <li><i class="ace-icon fa fa-arrow-right blue"></i>'.$row['description'].'</li>			
			<li><i class="ace-icon fa fa-arrow-right blue"></i>' . AdminPhrase('tmpl_filename') . ': '.$row['tpl_name'].'</li>
			<li><i class="ace-icon fa fa-arrow-right blue"></i>'.AdminPhrase('tmpl_tpl_type').': '.$row['tpl_type'].')</li>
		</ul>
		
		';
  $tpl_path = SD_INCLUDE_PATH.'tmpl/defaults/'.$row['tpl_name'];
  		 
 // echo '
   // </div>
	//</div>
  //';

  // Inactive template must always be activatable
  if(empty($row['is_active']) || (!empty($row['pluginname']) && empty($row['system_only'])))
  {
    echo '
  		<div class="col-sm-12 pull-right align-left no-padding-left">
      		<input type="checkbox" class="ace" name="is_active" value="1" '.(empty($row['is_active'])?'':'checked="checked"').' /><span class="lbl"> ' . AdminPhrase('tmpl_activate_template') . '</span>
    </div>';
  }
  echo '</td></tr></table>
  
    <div id="editor_top" class="col-sm-12 no-padding-top no-margin-top">
		<h3 class="header blue lighter no-padding-top no-margin-top">' . AdminPhrase('tmpl_content') . '<div id="indicator" class="pull-left"><i class="ace-icon fa fa-spinner orange bigger-150 fa-spin"></i>&nbsp;</div></h3>';
  if(!$fallback)
  {
    echo '
      '.AdminPhrase('skins_theme').'
      <select id="ace_theme" size="1">
        <option value="ace/theme/clouds">Clouds</option>
        <option value="ace/theme/cobalt">Cobalt</option>
        <option value="ace/theme/dawn" selected="selected">Dawn</option>
        <option value="ace/theme/idle_fingers">idleFingers</option>
        <option value="ace/theme/merbivore">Merbivore</option>
        <option value="ace/theme/merbivore_soft">Merbivore Soft</option>
        <option value="ace/theme/monokai">Monokai</option>
        <option value="ace/theme/pastel_on_dark">Pastel on dark</option>
        <option value="ace/theme/textmate">Textmate</option>
        <option value="ace/theme/tomorrow">Tomorrow</option>
        <option value="ace/theme/tomorrow_night">Tomorrow Night</option>
        <option value="ace/theme/tomorrow_night_blue">Tomorrow Blue</option>
        <option value="ace/theme/tomorrow_night_bright">Tomorrow Bright</option>
        <option value="ace/theme/tomorrow_night_eighties">Tomorrow Eighties</option>
        <option value="ace/theme/twilight">Twilight</option>
        <option value="ace/theme/vibrant_ink">Vibrant Ink</option>
      </select>
      <select id="mode_selector"size="1">
        <option value="html" selected="selected">HTML</option>
        <option value="css">CSS</option>
        <option value="php">PHP</option>
        <option value="js">Javascript</option>
      </select>
	   <a id="hotkeys_help" class="btn btn-info btn-minier" href="#" title="'.AdminPhrase('skins_hotkeys').'" onclick="javascript:;">?</a>
      ';
  }
  echo '
      <select id="variable_selection" class="pull-right">
        <option value="0">'.AdminPhrase('skins_insert_variable').'</option>
        <option value="[PAGE_TITLE]">[PAGE_TITLE]</option>
        <option value="[PAGE_NAME]">[PAGE_NAME]</option>
        <option value="[PAGE_HTML_CLASS]">[PAGE_HTML_CLASS]</option>
        <option value="[PAGE_HTML_ID]">[PAGE_HTML_ID]</option>
        <option value="[REGISTER_PATH]">[REGISTER_PATH]</option>
        <option value="[USERCP_PATH]">[USERCP_PATH]</option>
        <option value="[LOSTPWD_PATH]">[LOSTPWD_PATH]</option>
        <option value="[LOGIN_PATH]">[LOGIN_PATH]</option>
      </select>
      </div> <!-- editor_top -->
	  <div class="clearfix"></div>
	  <div class="space-4"></div>
	 
    <div id="iframe-content" class="col-sm-12">';

  if($fallback)
  {
    echo '
      <div id="skineditor"></div>
      <textarea id="skincontent" name="content" style="display: block;" rows="30" cols="80">'.
      htmlspecialchars($row['content']) . '</textarea>';
  }
  else
  {
    echo '
      <div id="skineditor">' . htmlspecialchars($row['content']) . '</div>
      <textarea id="skincontent" name="content" style="display: none;" rows="10" cols="80">'.
      htmlspecialchars($row['content']) . '</textarea>';
  }

  echo '
    </div>
      <span id="ed_changed" class="pul-left red bigger" style="visibility:hidden;">'.
      AdminPhrase('editor_content_changed').'</span>
	  <div class="center">
      <a href="#" class="btn btn-info" onclick="javascript:jQuery(\'#tmplform\').submit(); return false;"><i class="ace-icon fa fa-check"></i> '.
      AdminPhrase('tmpl_update_template').'</a></div>
   
  <input type="submit" value="'.AdminPhrase('tmpl_update_template').'" style="position:absolute;top:-9999px;left:-9999px;display:none;margin-left:-9999px" />
  </form>
  <div id="hotkeys" style="display: none; z-index:1000">
    <table border="0" cellpadding="0" cellspacing="4" summary="" width="100%">
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
  ';

  EndSection();

} //DisplayTemplate


// ############################################################################
// UPDATE SETTINGS
// ############################################################################

function UpdateTemplateSettings()
{
  global $DB, $SDCache;
  if(isset($_POST['settings']) && is_array($_POST['settings']))
  {
    $settings = $_POST['settings'];
    while(list($key, $value) = each($settings))
    {
      if(is_array($value))
      {
        $value = unhtmlspecialchars(implode(',',$value));
      }
      else
      {
        $value = unhtmlspecialchars($value);
      }
      $DB->query("UPDATE {mainsettings} SET value = '%s' WHERE groupname = 'settings_templates' AND settingid = %d",
                 $DB->escape_string($value), $key);
    }

    if(isset($SDCache) && ($SDCache instanceof SDCache))
    {
      $SDCache->delete_cacheid(CACHE_ALL_MAINSETTINGS);
    }
  }

  RedirectPage(SD_TEMPLATES_SELF, AdminPhrase('tmpl_settings_updated'));

} //UpdateTemplateSettings


// ############################################################################
// DISPLAY TEMPLATE SETTINGS
// ############################################################################

function DisplayTemplateSettings()
{
  global $DB;

  $getsettings = $DB->query("SELECT * FROM {mainsettings} WHERE groupname = 'settings_templates' ORDER BY displayorder ASC");

  echo '<h3 class="header blue lighter">' . AdminPhrase('tmpl_template_settings') . '</h3>';

  echo '
  <form method="post" action="'.SD_TEMPLATES_SELF.'" class="form-horizontal">
  <input type="hidden" name="action" value="updatetemplatesettings" />
  '.PrintSecureToken();

  while($setting = $DB->fetch_array($getsettings,null,MYSQL_ASSOC))
  {
    PrintAdminSetting($setting);
  }

  echo '
  <div class="center">
  	<button type="submit" class="btn btn-info" value="" /><i class="ace-icon fa fa-check"></i> ' . AdminPhrase('common_update_settings') . '</button>
</div>
  </form>';

  EndSection();

} //DisplayTemplateSettings


// ############################################################################
// DISPLAY TEMPLATES
// ############################################################################

function DisplayTemplates($pluginid = 0)
{
  global $DB, $plugin_names, $search, $sdlanguage;

  $items_per_page = $search['limit'];
  $page = GetVar('page', 1, 'int');
  $limit = ' LIMIT '.(($page-1)*$items_per_page).','.$items_per_page;
  $pagination_target = SD_TEMPLATES_SELF.'?action=display_templates';

  $where = 'WHERE';

  if(!empty($pluginid))
  {
    $title = $DB->query_first('SELECT name FROM {plugins} WHERE pluginid = %d AND pluginid <> 1', $pluginid);
    $title = $title['name'] . ' '.AdminPhrase('tmpl_templates');

    $where .= ' p.pluginid = '.(int)$pluginid;
  }
  else
  if($pluginid==0)
  {
    $title = AdminPhrase('tmpl_core_templates');
  }
  else
  {
    $title = AdminPhrase('tmpl_templates');
  }

  // search for template content?
  if(strlen($search['content']))
  {
    $where .= ($where != 'WHERE' ? ' AND' : '') .
               " (r.content LIKE '%%" . $search['content'] . "%%')";
  }

  // search for active?
  if(strlen($search['active']) && ($search['active'] != 'all'))
  {
    $where .= ($where != 'WHERE' ? ' AND' : '') .
               ' (t.is_active = ' . ($search['active']=='active'?1:0) . ')';
  }

  // search for username?
  if(strlen($search['username']))
  {
    $where .= ($where != 'WHERE' ? ' AND' : '') .
               " (u.username LIKE '%%" . $DB->escape_string($search['username']) . "%%')";
  }

  // search for tpl_type?
  if(strlen($search['tpl_type']))
  {
    $where .= ($where != 'WHERE' ? ' AND' : '') .
               " (t.tpl_type LIKE '%%" . $DB->escape_string($search['tpl_type']) . "%%')";
  }

  // search for pluginid?
  if(strlen($search['pluginid']) && ((int)$search['pluginid'] <> -1))
  {
    $where .= ($where != 'WHERE' ? ' AND' : '') .
               ' (t.pluginid = ' . (int)$search['pluginid'] . ')';
  }

  $where = ($where=='WHERE' ? '' : $where);
  $select = 'SELECT COUNT(*) tplcount FROM '.PRGM_TABLE_PREFIX.'templates t'.
            ' LEFT JOIN '.PRGM_TABLE_PREFIX.'plugins p ON p.pluginid = t.pluginid '.
            ' LEFT JOIN '.PRGM_TABLE_PREFIX.'revisions r ON r.revision_id = t.revision_id'.
            ' LEFT JOIN '.PRGM_TABLE_PREFIX.'users u ON u.userid = t.userid '.
            $where;

  // Get the total count of comments with conditions
  $templates_count = 0;
  $DB->result_type = MYSQL_ASSOC;
  if($getcount = $DB->query_first($select))
  {
    $templates_count = (int)$getcount['tplcount'];
  }

  $sort = $search['sorting'];

  // Select all template rows
  $select = 'SELECT t.*, p.name pluginname, u.username'.
            ' FROM '.PRGM_TABLE_PREFIX.'templates t'.
            ' LEFT JOIN '.PRGM_TABLE_PREFIX.'plugins p ON p.pluginid = t.pluginid '.
            ' LEFT JOIN '.PRGM_TABLE_PREFIX.'revisions r ON r.revision_id = t.revision_id'.
            ' LEFT JOIN '.PRGM_TABLE_PREFIX.'users u ON u.userid = t.userid '.
            $where.
            ' ORDER BY p.name, './/$search['sorting'].
            ' t.displayname '.
            $limit;
  $getcomments = $DB->query($select);

  // Fetch distinct list of all referenced plugins from comments
  $plugins_arr = array();
  if($getplugins = $DB->query('SELECT DISTINCT p.pluginid, p.name FROM '.PRGM_TABLE_PREFIX.'templates t'.
                              ' INNER JOIN '.PRGM_TABLE_PREFIX.'plugins p ON p.pluginid = t.pluginid'))
  {
    while($plugin = $DB->fetch_array($getplugins,null,MYSQL_ASSOC))
    {
      $plugins_arr[$plugin['pluginid']] = (isset($plugin_names[$plugin['pluginid']]) ? $plugin_names[$plugin['pluginid']] : $plugin['name']);
    }
    ksort($plugins_arr);
  }

  // ######################################################################
  // DISPLAY COMMENTS SEARCH BAR
  // ######################################################################

  StartSection(AdminPhrase('tmpl_filter_title'));

  echo '
  <form action="'.SD_TEMPLATES_SELF.'?action=displaytemplates" id="searchtemplates" name="searchtemplates" method="post">
  '.PrintSecureToken().'
  <input type="hidden" name="customsearch" value="1" />
  <input type="hidden" name="clearsearch" value="0" />
  <table class="table table-bordered">
  <thead>
  <tr>
    <th class="tdrow1">' . AdminPhrase('tmpl_template').'</th>
    <th class="tdrow1">' . AdminPhrase('tmpl_status') . '</th>'.
    /*
    <td class="tdrow1">' . AdminPhrase('tmpl_username') . '</td>
    <td class="tdrow1">' . AdminPhrase('tmpl_tpl_type') . '</td>
    <td class="tdrow1">' . AdminPhrase('tmpl_sort_by') . '</td>
    */
    '
    <th class="tdrow1">' . AdminPhrase('tmpl_plugin') . '</th>
    <th class="tdrow1">' . AdminPhrase('tmpl_limit') . '</th>
    <th class="tdrow1">' . AdminPhrase('tmpl_filter') . '</th>
  </tr>
  </thead>
  <tbody>
  <tr>
    <td class="tdrow2"><input type="text" name="searchcontent" style="width: 90%;" value="' . $search['content'] . '" /></td>
    <td class="tdrow2">
      <select name="searchactive" style="width: 95%;">
        <option value="all"' .     ($search['active'] == 'all'     ?' selected="selected"':'') .'>---</option>
        <option value="active"' .  ($search['active'] == 'active'  ?' selected="selected"':'') .'>'.AdminPhrase('tmpl_active').'</option>
        <option value="inactive"'. ($search['active'] == 'inactive'?' selected="selected"':'') .'>'.AdminPhrase('tmpl_inactive').'</option>
      </select>
    </td>
    <td class="tdrow2">
      <select id="searchpluginid" name="searchpluginid" style="width: 95%">
        <option value="-1"'.(empty($search['pluginid'])?' selected="selected"':'').'>---</option>
        <option value="0"'.(empty($search['pluginid'])?' selected="selected"':'').'>'.AdminPhrase('tmpl_core_templates').'</option>
        ';
  foreach($plugins_arr as $p_id => $p_name)
  {
    if($p_id > 1) // do not list "empty" plugin
    {
      echo '
        <option value="'.$p_id. '" ' . ($search['pluginid']==$p_id?'selected="selected"':'').
        '>'. $p_name .' ('.$p_id.')</option>';
    }
  }
  echo '
      </select>
    </td>
    <td class="tdrow2" style="width: 40px;"><input type="text" name="searchlimit" style="width: 35px;" value="' . $items_per_page . '" size="2" /></td>
    <td class="align-middle center" width="5%">
      <input type="submit" value="'.AdminPhrase('search').'" style="display:none" />
      <a id="templates-submit-search" href="#" onclick="return false;" title="'.AdminPhrase('tmpl_apply_filter').
      '"><i class="ace-icon fa fa-search blue bigger-120"></i></a> &nbsp;
	    <a id="templates-clear-search" href="#" onclick="return false;" title="'.AdminPhrase('tmpl_clear_filter').
      '"><i class="ace-icon fa fa-trash-o red bigger-120"></i></a>
    </td>
  </tr>
  </tbody>
  </table>
  </form>
  ';

  EndSection();

  if(!$templates_count)
  {
    DisplayMessage(AdminPhrase('tmpl_no_templates_found'));
    return;
  }

  StartSection($title . ' ('.$templates_count.')');

  echo '
  <form action="'.SD_TEMPLATES_SELF.'" method="post" id="templates" name="templates">
  '.PrintSecureToken().'

  <table class="table table-bordered table-striped">
  <thead>
  <tr>
    <th class="td1">' . AdminPhrase('tmpl_plugin_name') . '</th>
    <th class="td1" align="center" width="45">#</th>
    <th class="td1">' . AdminPhrase('tmpl_templates') . '</th>
    <th class="td1" width="60" align="center">' . AdminPhrase('tmpl_active') . '</th>
    '.
    /*
    <td class="td1">' . AdminPhrase('tmpl_username') . '</td>
    <td class="td1">' . AdminPhrase('tmpl_tpl_type') . '</td>
    */
    '
    <th class="td1" width="165">' . AdminPhrase('tmpl_date') . '</th>
    <th class="center" width="75">
      <a id="checkall" rel="0" title="'.htmlspecialchars(AdminPhrase('common_delete'),ENT_COMPAT).
        '" href="#" onclick="javascript:return false;"><i class="ace-icon fa fa-trash-o red bigger-110"></i></a>
    </th>
  </tr>
  </thead>
  <tbody>';

  while($row = $DB->fetch_array($getcomments,null,MYSQL_ASSOC))
  {
    echo '
    <tr>
      <td class="td2">';

    if(isset($plugin_names[$row['pluginid']]))
    {
      echo $plugin_names[$row['pluginid']];
    }
    else
    if($row['pluginid'] < 18)
    {
      echo AdminPhrase('tmpl_core_templates');
    }
    else
    {
      echo '!!!';
    }

    echo '</td>
      <td class="center">'.($row['pluginid']?$row['pluginid']:'-').'</td>
      <td class="td2"><a href="'.SD_TEMPLATES_SELF.'?action=display_template&amp;template_id='.
        $row['template_id'] . '"><span class="sprite sprite-edit"></span>'.
        ' &nbsp;' . $row['displayname'] . '</a></td>
      <td class="center">'.($row['is_active']? AdminPhrase('common_yes') : AdminPhrase('common_no')) . '</td>
      '.
      /*
      <td class="td2"><a class="username" title="'.htmlspecialchars(AdminPhrase('filter_username_hint')).
        '" href="#" onclick="return false;">'.$row['username'].'</a></td>
      <td class="td2">'.(!empty($row['tpl_type']) ? (string)$row['tpl_type'] : '-').'</td>
      */
      '<td class="td2">' . DisplayDate($row['dateupdated'],'Y-m-d H:i',false) . '</td>
      <td class="td3" align="center">';
      if(empty($row['system_only']))
      {
        echo '
        <input class="deltemplate ace" type="checkbox" name="delete['.(int)$row['template_id'].']" checkme="deletetemplate" value="'.
          md5($row['template_id'].PRGM_HASH.md5($row['pluginid'])).'" /><span class="lbl"></span>';
      }
      else
      {
        echo '---';
      }
      echo '
      </td>
    </tr>
	';
  }
  echo '</tbody>
	</table>
  <div class="align-right">';
  			PrintSubmit('delete_templates',AdminPhrase('tmpl_delete_templates'),'templates','fa-trash-o','','','btn-danger');
  echo '
  </div>
  </form>
  </div>
  ';



  // pagination
  if(!empty($templates_count))
  {
    $p = new pagination;
    $p->items($templates_count);
    $p->limit($items_per_page);
    $p->currentPage($page);
    $p->adjacents(7);
    $p->target($pagination_target);
    $p->show();
  }

} //DisplayTemplates


// ############################################################################
// DISPLAY ORGANIZED TEMPLATES
// ############################################################################

function DisplayOrganizedTemplates()
{
  global $DB, $plugin_names;

  $getrows = $DB->query("SELECT t.pluginid, ifnull(p.name,'Core') pluginname, COUNT(*) tplcount".
                        ' FROM '.PRGM_TABLE_PREFIX.'plugins p'.
                        ' RIGHT JOIN '.PRGM_TABLE_PREFIX.'templates t ON t.pluginid = p.pluginid'.
                        ' GROUP BY p.pluginid'.
                        ' ORDER BY 2');

  StartSection(AdminPhrase('tmpl_by_plugin'));
  echo '
  <table class="table table-bordered table-striped">
  <thead>
  <tr>
    <th class="td1">' . AdminPhrase('tmpl_plugin') . '</th>
    <th class="td1">' . AdminPhrase('tmpl_number_of_templates') . '</th>
    <th class="td1">' . AdminPhrase('tpl_plugin_id') . '</th>
  </tr>
  </thead>
  <tbody>';

  while($row = $DB->fetch_array($getrows,'',MYSQL_ASSOC))
  {
    //SD370: added "&amp;clearsearch=1" to set filter
    if(isset($row['pluginid']))
    echo '
  <tr>
    <td class="td2">
      <a href="'.SD_TEMPLATES_SELF.'?action=display_templates&amp;pluginid='.$row['pluginid'].'&amp;clearsearch=1">'.
      (empty($row['pluginid'])?AdminPhrase('tmpl_core_templates'):$plugin_names[$row['pluginid']]).'</a></td>
    <td class="td3" width="80">' . $row['tplcount'] . '</td>
    <td class="td3" width="80">'.(empty($row['pluginid'])?'-':$row['pluginid']).'</td>
  </tr>';
  }
  echo '</tbody></table>';

  EndSection();

} //DisplayOrganizedTemplates


// ############################################################################
// SELECT FUNCTION
// ############################################################################
if(is_callable($action) && in_array($action, array(
    'deletetemplates','displayorganizedtemplates','displaytemplate',
    'displaytemplates','displaytemplatesettings','restoretemplate',
    'updatetemplatesettings','updatetemplate')))
{
  call_user_func($action);
}
else
{
  DisplayMessage('Invalid function called!', true);
}

// ############################################################################
// DISPLAY ADMIN FOOTER
// ############################################################################
DisplayAdminFooter();
