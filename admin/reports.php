<?php

// ############################################################################
// DEFINE CONSTANTS
// ############################################################################

define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');

// ############################################################################
// INIT PRGM
// ############################################################################

include(ROOT_PATH . 'includes/init.php');

define('SELF_REPORTS_PHP',         'reports.php');
define('SELF_REPORTS_PHP_DEFAULT', SELF_REPORTS_PHP.'?action=display_reports');
define('REPORTS_DATE_SORT_DESC',   'datereported DESC');
define('REPORTS_DATE_SORT_ASC',    'datereported ASC');
define('REPORTS_IP_SORT_ASC',      'ipaddress ASC');
define('REPORTS_IP_SORT_DESC',     'ipaddress DESC');
define('REPORTS_UNAME_SORT_ASC',   'username ASC');
define('REPORTS_UNAME_SORT_DESC',  'username DESC');

// Load required classes
require_once(SD_INCLUDE_PATH.'class_sd_reports.php');
require_once(ROOT_PATH.'plugins/forum/forum_config.php');


// ############################################################################
// LOAD ADMIN LANGUAGE
// ############################################################################
$admin_phrases = LoadAdminPhrases(4);


// ############################################################################
// GET/POST VARS
// ############################################################################
$search        = array();
$action        = GetVar('action', 'display_reports', 'string');
$function_name = str_replace('_', '', $action);
$reportid      = GetVar('reportid', 0, 'whole_number');
$pluginid      = GetVar('pluginid', 0, 'whole_number');
$customsearch  = GetVar('customsearch', 0, 'bool');
$clearsearch   = GetVar('clearsearch',  0, 'bool');

// Include autocomplete JS
if( (substr($function_name,0,6) != 'insert') &&
    (substr($function_name,0,6) != 'update'))
{
	$js_arr = array();
  #SD370: if minify is enabled, it'll be loaded by admin loader
  if(!defined('ENABLE_MINIFY') || !ENABLE_MINIFY)
  {
    $js_arr = array(ROOT_PATH.ADMIN_STYLES_FOLDER .'/assets/js/jquery-ui.min.js');
  }
  sd_header_add(array(
    #SD370: moved to core admin loader
    #'css' => array(SD_CSS_PATH.'jquery.autocomplete.css'),
    'js'  => $js_arr
  ));
}

// Search array functionality on for reports display
if($function_name == 'displayreports')
{
  // Restore previous search array from cookie
  $search_cookie = isset($_COOKIE[COOKIE_PREFIX . '_reports_search']) ? $_COOKIE[COOKIE_PREFIX . '_reports_search'] : false;

  if($clearsearch)
  {
    $search_cookie = false;
    $customsearch  = false;
  }

  if($customsearch)
  {
    $search = array('report'    => GetVar('searchreport', '', 'string', true, false),
                    'status'    => GetVar('searchstatus', 'all', 'string', true, false),
                    'username'  => GetVar('searchusername', '', 'string', true, false),
                    'pluginid'  => GetVar('searchpluginid', '', 'string', true, false),
                    'limit'     => GetVar('searchlimit', 20, 'integer', true, false),
                    'sorting'   => GetVar('searchsorting', '', 'string', true, false),
                    'ipaddress' => GetVar('ipaddress', '', 'string', true, false));

    // Store search params in cookie
    sd_CreateCookie('_reports_search',base64_encode(serialize($search)));
  }
  else
  {
    $searchpluginid = GetVar('pluginid', 0, 'whole_number');
    if(!empty($searchpluginid))
    {
      $search['pluginid'] = $searchpluginid;
    }

    if($search_cookie !== false)
    {
      $search = unserialize(base64_decode(($search_cookie)));
    }
  }

  if(empty($search) || !is_array($search))
  {
    $search = array('report'    => '',
                    'status'    => 'all',
                    'username'  => '',
                    'pluginid'  => 0,
                    'limit'     => 20,
                    'sorting'   => '',
                    'ipaddress' => '');

    // Remove search params cookie
    sd_CreateCookie('_reports_search', '');
  }

  $search['report']   = isset($search['report'])   ? (string)$search['report'] : '';
  $search['status']   = isset($search['status'])   ? (string)$search['status'] : 'all';
  $search['status']   = in_array($search['status'], array('all','closed','open'))?$search['status']:'all';
  $search['username'] = isset($search['username']) ? (string)$search['username'] : '';
  $search['pluginid'] = isset($search['pluginid']) ? (int)$search['pluginid'] : '';
  $search['limit']    = isset($search['limit'])    ? Is_Valid_Number($search['limit'],20,5,100) : 20;
  $search['sorting']  = empty($search['sorting'])  ? REPORTS_DATE_SORT_DESC : $search['sorting'];
  $sort = in_array($search['sorting'], array(REPORTS_DATE_SORT_ASC,
                                             REPORTS_DATE_SORT_DESC,
                                             REPORTS_IP_SORT_ASC,
                                             REPORTS_IP_SORT_DESC,
                                             REPORTS_UNAME_SORT_ASC,
                                             REPORTS_UNAME_SORT_DESC)) ? $search['sorting'] : REPORTS_DATE_SORT_DESC;
  $search['sorting'] = $sort;
  $search['ipaddress']= isset($search['ipaddress']) ? (string)$search['ipaddress'] : '';
  
  //SD322: enable BBCode editor (MarkItUp)
  $js_arr = array();
  $js_arr[] = ROOT_PATH . MINIFY_PREFIX_F . 'includes/javascript/markitup/markitup-full.js';
 // $js_arr[] = SD_INCLUDE_PATH.'javascript/jquery.autocomplete.min.js';

  sd_header_add(array(
    'js'    => $js_arr,
     // NO ROOT_PATH for "css" entries!
    'css'   => array(SD_JS_PATH . 'markitup/skins/markitup/style.css',
                     SD_JS_PATH . 'markitup/sets/bbcode/style.css'),
    'other' => array('
  <script type="text/javascript">
  //<![CDATA[
  if (typeof(jQuery) !== "undefined") {
    jQuery(document).ready(function() {
      (function($){
        $("form#searchreports select").change(function() {
          $("#searchreports").submit();
        });

        $("a#reports-submit-search").click(function() {
          $("#searchreports").submit();
        });

        $("a#reports-clear-search").click(function() {
          $("#searchreports input").val("");
          $("#searchreports select").prop("selectedIndex", 0);
          $("#searchreports input[name=clearsearch]").val("1");
          $("#searchreports").submit();
        });

	  
	  $("a#checkall").click(function(e){
      e.preventDefault();
      var ischecked = 1 - parseInt($(this).attr("rel"),10);
      if(ischecked==1) {
        $("input.deletereport").attr("checked","checked");
        $("form#reports tr:not(thead tr, tr:last-child)").addClass("danger");
      } else {
        $("input.deletereport").removeAttr("checked");
        $("form#reports tr").removeClass("danger");
      }
      $(this).attr("rel",ischecked);
      return false;
    });
	
	$("input[type=checkbox].deletereport").on("change",function(){
      var tr = $(this).parents("tr");
      $(tr).toggleClass("danger");
    });
	
	$("input[type=checkbox].removemod").on("change",function(){
      var tr = $(this).parents("tr");
      $(tr).toggleClass("danger");
    });
	
	$("#submit_delete").on("click", function(e) {
	  	e.preventDefault();
  		bootbox.confirm("'.AdminPhrase('reports_confirm_delete_reports') .'", function(result) {
    	if(result) {
       		$("form#reports").submit();
    	}
  		});
	});
	
      }(jQuery));
    });
  }
  //]]>
  </script>')
  ), false);

  

} // end for display reports

// SD400
if($function_name == 'displayreportsettings')
{
	 //SD322: enable BBCode editor (MarkItUp)
  $js_arr =  array(	ROOT_PATH.ADMIN_STYLES_FOLDER .'/assets/js/jquery-ui.min.js',
  				ROOT_PATH.ADMIN_PATH.'/javascript/functions.js',
				SITE_URL . ADMIN_STYLES_FOLDER . '/assets/js/bootbox.min.js',);
 
  
  $css = array(ROOT_PATH.ADMIN_STYLES_FOLDER. '/assets/css/jquery-ui.min.css');
 sd_header_add(array(
    'js'    => $js_arr,
     // NO ROOT_PATH for "css" entries!
    'css'   => $css,
    'other' => array('
  <script type="text/javascript">
//<![CDATA[
if (typeof(jQuery) !== "undefined") {
  $(document).ready(function() {
    $("#UserSearch").autocomplete({
      source: "'.SITE_URL.'includes/ajax/getuserselection.php",
	  minLength: 2,
      select: function(event,ui) {
       	if(ui.item.userid) {
          var user_id = 0, old_id = 0;
          user_id = parseInt(ui.item.userid,10);
          if(user_id > 0) {
            // Assign selected, new author id
            jQuery("input#new_moderator_id").val(user_id);
          }
        }
      }
    });
	
	$("[data-rel=popover]").popover({container:"body"});
	
	$("#submit_repmods").on("click", function(e) {
		e.preventDefault();
		if( $("#repmods input:checkbox.remove:checked").length > 0 )
		{
			  bootbox.confirm("'.AdminPhrase('report_delete_moderator').'", function(result) {
				if(result) {
				   $("#repmods").submit();
				}
  			  });
		}
		else
		{
			$("#repmods").submit();
		}
	});
	
	
  });
}
//]]>
</script>')
  ), false);

}




// ############################################################################
// DISPLAY ADMIN HEADER
// ############################################################################
DisplayAdminHeader(array('Reports', $action));


// ############################################################################
// CHECK PAGE ACCESS
// ############################################################################

CheckAdminAccess('reports');

// ############################################################################
// UPDATE REPORT
// ############################################################################

function UpdateReport()
{
  global $DB, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage(SITE_URL,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
    return;
  }

  $reportid = Is_Valid_Number(GetVar('reportid', 0, 'whole_number',true,false),0,1,9999999);
  if(empty($reportid))
  {
    RedirectPage(SELF_REPORTS_PHP, AdminPhrase('reports_report_not_found'));
    return;
  }

  $do_approve = GetVar('do_approve', 0, 'bool',true,false);

  $deletereport = GetVar('deletereport', 0, 'bool',true,false);
  if($deletereport)
  {
    SD_Reports::DeleteReport($reportid, $do_approve, true);
    RedirectPage(SELF_REPORTS_PHP, AdminPhrase('reports_report_deleted'));
    return true;
  }

  $is_closed = GetVar('is_closed', 0, 'bool',true,false);
  if($is_closed)
  {
    SD_Reports::CloseReport($reportid, $do_approve, true);
  }

  RedirectPage(SELF_REPORTS_PHP.'?action=display_report&reportid='.$reportid,
               AdminPhrase('reports_report_updated'));
  return true;

} //UpdateReport


// ############################################################################
// DELETE REPORTS
// ############################################################################

function DeleteReports()
{
  global $DB, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage(SITE_URL,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
    return;
  }

  $approve_deleted_multi = GetVar('approve_deleted_multi', 0, 'bool',true,false);
  $report_id_arr = GetVar('report_id_arr', array(), 'array',true,false);
  $pluginid_arr  = GetVar('plugin_id_arr', array(), 'array',true,false);
  $delete_report_id_arr = GetVar('delete_report_id_arr', array(), 'array',true,false);


  for($i = 0; $i < count($report_id_arr); $i++)
  {
    $report_id = $report_id_arr[$i];
    $pluginid  = $pluginid_arr[$i];

    if(@in_array($report_id, $delete_report_id_arr))
    {
      SD_Reports::DeleteReport($report_id, !empty($approve_deleted_multi), $approve_deleted_multi);
    }
  }

  RedirectPage(SELF_REPORTS_PHP, AdminPhrase('reports_reports_deleted'));

} //DeleteReports


// ############################################################################
// UPDATE REASON
// ############################################################################

function SaveReason()
{
  global $DB;

  $redir = SELF_REPORTS_PHP.'?action=display_report_reasons';
  if(!CheckFormToken())
  {
    RedirectPage($redir,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
    return;
  }

  $reasonid = GetVar('reasonid', 0, 'whole_number');
  $isNew = empty($reasonid);

  if(!$isNew && !($reasonid = Is_Valid_Number($reasonid,0,1,999999999)))
  {
    RedirectPage($redir, AdminPhrase('reports_reason_not_found'));
    return;
  }

  $title  = trim(GetVar('title', '', 'html', true, false));
  $descr  = trim(GetVar('description', '', 'html', true, false));
  $delete = GetVar('deletereason', 0, 'bool', true, false);

  if(!$isNew && $delete)
  {
    SD_Reports::DeleteReasonFully($reasonid);
    RedirectPage($redir, AdminPhrase('reports_reason_deleted'));
    return;
  }

  $errors = array();
  if(strlen($title) < 3) $errors[] = AdminPhrase('reports_title_missing');
  #if(strlen($descr) < 3) $errors[] = AdminPhrase('reports_description_missing');

  if(empty($errors))
  {
    if($isNew)
    {
      // Add new reason
      SD_Reports::AddReportReason($title, $descr);
    }
    else
    {
      // Update actual reason
      SD_Reports::UpdateReasonDetails($reasonid, $descr, $title, false);

      // Remove link between reason and plugins and re-insert the selected ones
      SD_Reports::DeleteReasonLinks($reasonid, false);
    }

    if($rep_plugins = GetVar('rep_plugins', false, 'array'))
    {
      foreach($rep_plugins as $p_id)
      {
        if($p_id = Is_Valid_Number($p_id,0,2,99999999))
        {
          SD_Reports::AddReasonLink($reasonid, $p_id, 1, false);
        }
      }
    }

    SD_Reports::ClearReasonsCache();

    $msg = AdminPhrase($isNew?'reports_reason_added':'reports_reason_updated');
    RedirectPage($redir, (empty($errors)?$msg:$errors), 2, !empty($errors));
    return;
  }

  DisplayReason($errors);

} //SaveReason


// ############################################################################
// DISPLAY INDIVIDUAL REPORT
// ############################################################################

function DisplayReport()
{
  global $DB, $sdlanguage;

  $reportid = Is_Valid_Number(GetVar('reportid', 0, 'whole_number'),0,1,9999999);
  if(!$reportid)
  {
    RedirectPage(SELF_REPORTS_PHP, AdminPhrase('reports_no_reports_found'));
    return;
  }

  $DB->result_type = MYSQL_ASSOC;
  if(!$report = $DB->query_first(
     'SELECT ur.*, p.name pluginname,'.
     ' r.title reason_title, r.description reason_descr'.
     ' FROM '.TABLE_PREFIX.'users_reports ur'.
     ' LEFT JOIN '.TABLE_PREFIX.'report_reasons r ON r.reasonid = ur.reasonid'.
     ' LEFT JOIN '.TABLE_PREFIX.'plugins p ON p.pluginid = ur.pluginid'.
     ' WHERE ur.reportid = %d',$reportid))
  {
    RedirectPage(SELF_REPORTS_PHP, AdminPhrase('reports_no_reports_found'));
    return;
  }

  $item_content = '';
  $item_title = SD_Reports::TranslateObjectID($report['pluginid'],
                  $report['objectid1'], $report['objectid2'],
                  $item_content);
  $reported_user = SDUserCache::CacheUser($report['reported_userid'],'',false,false,true);

  echo '
  <form method="post" id="editrep" action="'.SELF_REPORTS_PHP.'" class="form-horizontal">
  '.PrintSecureToken();
  echo '
  <input type="hidden" name="reportid" value="'.$reportid.'" />
  <input type="hidden" name="pluginid" value="'.$report['pluginid'].'" />
  <input type="hidden" name="objectid1" value="'.$report['objectid1'].'" />
  <input type="hidden" name="objectid2" value="'.$report['objectid2'].'" />
  ';
  
  echo '<h3 class="header blue lighter">' . AdminPhrase('reports_edit_report') . '</h3>';
  
  echo'
  <div class="form-group">
  	<label class="control-label col-sm-2">'. AdminPhrase('reports_plugin') . '</label>
	<div class="col-sm-6">
	<p class="form-control-static">
		'.$report['pluginname'].'
	</p>
	</div>
</div>
    
  
  <div class="form-group">
  	<label class="control-label col-sm-2">' . AdminPhrase('reports_reported_item') . '</label>
    <div class="col-sm-6">
	<p class="form-control-static">
		'.
		SD_Reports::DisplayReportLink($report['categoryid'], $report['pluginid'],
                                     $report['objectid1'], $report['objectid2']).'
		 '.strip_tags($item_title).'
	</p>
	</div>
</div>
    ';


  echo '
     
  <div class="form-group">
  	<label class="control-label col-sm-2">'.$sdlanguage['common_status'].'</label>
    <div class="col-sm-6">
	<p class="form-control-static">
      <span class="bigger ';
  $status = SD_Reports::GetReportedItemStatus($reportid, $msg);
  if(!$status && !empty($msg))
  {
    echo 'red">'.$msg;
  }
  else
  {
    if($status)
      echo 'green">'.$sdlanguage['approved'];
    else
      echo 'red">'.$sdlanguage['unapproved'];
  }
  echo '</span>
  </p>
    </div>
  </div>
  <div class="form-group">
  	<label class="control-label col-sm-2">' . AdminPhrase('reported_user') . '</label>
    <div class="col-sm-6">
		<p class="form-control-static">';
  if(!empty($reported_user['username']))
  {
    echo  $reported_user['username'].' ('.
         $reported_user['usergroup_details']['name'].')';
  }
  else
  {
    echo '---';
  }
  echo '</p></div>
  </div>
  <div class="form-group">
  	<label class="control-label col-sm-2">' . AdminPhrase('reported_by') . '</label>
    <div class="col-sm-6">
		<input type="text" class="form-control" disabled value="'.$report['username'].'" />
		<span class="helper-text">'.AdminPhrase('reports_date').' <strong>'.  DisplayDate($report['datereported']).'</strong> | '.AdminPhrase('reports_ipaddress').' <strong>'. $report['ipaddress'].'</strong></span>
    </div>
  </div>';

  if(!empty($report['user_msg']))
  {
  echo '
  <div class="form-group">
  	<label class="control-label col-sm-2">' . AdminPhrase('reports_user_message') . '</label>
    <div class="col-sm-6">
		<textarea class="form-control" disabled>'.$report['user_msg'].'</textarea>
	</div>
</div>';
  }

  echo '
  <div class="form-group">
  	<label class="control-label col-sm-2">' . AdminPhrase('reports_reason') . '</label>
    <div class="col-sm-6"><p class="form-control-static"><strong>'.$report['reason_title'].'</strong><br />
    <i>'.$report['reason_descr'].'</i></p>
	</div>
</div>
  <div class="form-group">
  	<label class="control-label col-sm-2">' . AdminPhrase('reported_content') . '</label>
    <div class="col-sm-6">
     '.nl2br(htmlspecialchars($item_content)).'
    </div>
  </div>

    <div class="form-group">
      <label class="control-label col-sm-2"></label>
	  <div class="col-sm-6">
	  <input type="checkbox" class="ace" id="deletereport" name="deletereport" value="1" /><span class="lbl"> '.
      AdminPhrase('reports_delete_this').'</span><br /><br />
	
	<input type="checkbox" class="ace" id="is_closed" name="is_closed" value="1" '.(empty($report['is_closed'])?'':'checked="checked"').' /><span class="lbl"> '.
      AdminPhrase('reports_close_this_report') . '</span>';
  if(!$status)
  {
  echo '<br /><input type="checkbox" id="do_approve" name="do_approve" value="1" /><span class="lbl"> '.
      AdminPhrase('report_change_status') . '</span>';
  }

  echo '
  </div></div>';

  PrintSubmit('updatereport', AdminPhrase('reports_update_report'),'editrep','fa-check');

  echo '
  </form>';

} //DisplayReport


// ############################################################################
// DISPLAY INDIVIDUAL REASON
// ############################################################################

function DisplayReason($errors=null)
{
  global $DB, $userinfo;

  $reasonid = GetVar('reasonid', 0, 'whole_number');
  $isNew = empty($reasonid);

  if(!$isNew && !($reasonid = Is_Valid_Number($reasonid,0,1,999999999)))
  {
    RedirectPage(SELF_REPORTS_PHP.'?action=display_report_reasons', AdminPhrase('reports_no_reasons_found'));
    return false;
  }

  if($isNew)
  {
    $reason = array(
        'reasonid'    => 0,
        'title'       => '',
        'description' => '',
        'datecreated' => TIME_NOW,
        'dateupdated' => TIME_NOW,
        'created_by'  => $userinfo['username']
      );
  }
  else
  {
    $DB->result_type = MYSQL_ASSOC;
    $reason = $DB->query_first('SELECT r.*'.
                               ' FROM {report_reasons} r'.
                               ' WHERE r.reasonid = %d',$reasonid);
    if(empty($reason['reasonid']))
    {
      RedirectPage(SELF_REPORTS_PHP.'?action=display_report_reasons',
                   AdminPhrase('reports_no_reasons_found'));
      return false;
    }
  }

  if(!empty($errors) && is_array($errors))
  {
    DisplayMessage($errors, true);
    // Fetch entered values
    $reason['description'] = GetVar('description','','html',true,false);
    $reason['title'] = GetVar('title','','html',true,false);
  }

  echo '
  <form method="post" id="repreason" role="form" class="form-horizontal" action="'.SELF_REPORTS_PHP.'?reasonid='.$reasonid.'">
  '.PrintSecureToken();
 // StartTable(AdminPhrase('reports_edit_reason'), array('table', 'table-bordered', 'table-striped'));
  echo '
  <div class="form-group">
  	<label class="control-label col-sm-2" for="title">' . AdminPhrase('reports_reason_title') . '</label>
	<div class="col-sm-9">
		<input class="form-control" type="text" maxlength="254" name="title" value="'.$reason['title'].'" />
    </div>
  </div>
  
  <div class="form-group">
  	<label class="control-label col-sm-2" for="title">'.AdminPhrase('reports_reason_description').'</label>
	<div class="col-sm-9">';	
	PrintWysiwygElement('description', $reason['description'], 10, 60);
	echo'
    </div>
  </div>';

  // Display reportable plugins
  $DB->ignore_error = true;
  if($getrows = $DB->query('SELECT p.pluginid, p.name pluginname,'.
                           ' (SELECT COUNT(*) FROM {report_reasons_plugins} rp WHERE rp.pluginid = p.pluginid AND rp.reasonid = %d) pr_count'.
                           ' FROM {plugins} p '.
                           ' WHERE p.reporting = 1'.
                           ' ORDER BY p.name', $reasonid))
  {
      echo '
	  <div class="form-group">
  			<label class="control-label col-sm-2" for="rep_plugins">'.AdminPhrase('reportable_plugins') . '</label>
			<div class="col-sm-9">';
			 while($p = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
      {
        echo '<input type="checkbox" class="ace" name="rep_plugins[]" value="'.$p['pluginid'].'" '.
        (empty($p['pr_count'])?'':'checked="checked" ').'/><span class="lbl"> '.$p['pluginname'];
        $tmp = '('.$p['pluginid'].')';
        if(strpos($p['pluginname'], $tmp) === false)
        {
          echo ' '.$tmp . '</span><br />';
        }
      }
	  echo'<span class="helper-text">'.AdminPhrase('reports_select_plugins').'</span>
    		</div>
  	</div>';
  }
  $DB->ignore_error = false;

  if(!$isNew)
  {
    echo '
	<div class="form-group">
  	<label class="control-label col-sm-2" for="deletereason">' . AdminPhrase('reports_delete') . '</label>
	<div class="col-sm-9">
		<input type="checkbox" class="ace" id="deletereason" name="deletereason" value="1" />
		<span class="lbl"> ' . AdminPhrase('common_yes') . '</span>
    </div>
  </div>';
  }

  if($isNew)
    PrintSubmit('save_reason', AdminPhrase('add_reason_short'), 'repreason','fa-plus');
  else
    PrintSubmit('save_reason', AdminPhrase('reports_update_reason'), 'repreason','fa-check');


  echo '
  </form>';


} //DisplayReason


// ############################################################################
// UPDATE SETTINGS
// ############################################################################

function UpdateSettings()
{
  global $DB, $SDCache, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_REPORTS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
    return;
  }

  if(!isset($_POST['settings']) || !is_array($_POST['settings']))
  {
    RedirectPage(SELF_REPORTS_PHP.'?action=displayreportsettings',
                 AdminPhrase('err_invalid_setting_format'),2,true);
    return false;
  }

  $getsettings = $DB->query('SELECT settingid, input FROM {mainsettings}'.
                            " WHERE groupname = 'user_reports_settings'".
                            ' ORDER BY settingid');
  $settings = $_POST['settings'];
  while($setting = $DB->fetch_array($getsettings))
  {
    $key = (int)$setting['settingid'];
    $value = isset($settings[$key]) ? $settings[$key] : '';
    if(is_array($value))
    {
      $value = unhtmlspecialchars(implode(',',$value));
    }
    else
    {
      $value = unhtmlspecialchars($value);
    }
    if(($setting['input']=='usergroups') && ($value=='-1'))
    {
      $value = '';
    }
    $DB->query("UPDATE {mainsettings} SET value = '%s' WHERE settingid = %d",
               $DB->escape_string($value), $key);
  }

  if(isset($SDCache))
  {
    $SDCache->delete_cacheid(CACHE_ALL_MAINSETTINGS);
  }

  RedirectPage(SELF_REPORTS_PHP.'?action=displayreportsettings',
               AdminPhrase('reports_settings_updated'));

} //UpdateSettings


// ############################################################################
// ADD MODERATOR FORM
// ############################################################################

function AddModeratorForm()
{
  global $DB, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_REPORTS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
    return;
  }

  $user = array();
  $user['userid'] = 0;
  $user['username'] = '';
  if($userid = Is_Valid_Number(GetVar('new_moderator_id', 0, 'whole_number', true, false),0,1,9999999))
  {
    $user = SDUserCache::CacheUser($userid,'',false,false,true);
  }
  if(empty($user['userid']))
  {
    RedirectPage(SELF_REPORTS_PHP.'?action=display_report_settings',
                 AdminPhrase('err_no_user'));
    return;
  }

  $DB->ignore_error = true;
  $pviews = $DB->query_first('SELECT pluginviewids FROM {usergroups}'.
                             ' WHERE usergroupid = %d',
                             $user['usergroupid']);
  if(empty($pviews['pluginviewids']))
  {
    $pviews['pluginviewids'] = '-1';
  }

  $getplugins = $DB->query('SELECT pluginid, name FROM {plugins}'.
                           ' WHERE reporting = 1'.
                           " AND pluginid IN (%s)".
                           ' ORDER BY name', $pviews['pluginviewids']);
  $pcount = $DB->get_num_rows($getplugins);
  if(empty($pcount))
  {
    RedirectPage(SELF_REPORTS_PHP.'?action=display_report_settings',
                 AdminPhrase('err_no_reporting_plugins'));
    return;
  }

  echo '<h3 class="header blue lighter">' . AdminPhrase('add_moderator') . '</h3>';

  echo '
  <form method="post" action="'.SELF_REPORTS_PHP.'" class="form-horizontal">
  '.PrintSecureToken().'
  <input type="hidden" name="action" value="insert_moderator" />
  <input type="hidden" name="userid" value="'.$user['userid'].'" />';

  echo '
  	<div class="form-group">
		<label class="control-label col-sm-2" for="username">' . AdminPhrase('common_username') . '</label>
		<div class="col-sm-6">
			<input type="text" class="form-control" name="username" value="' . $user['username'] . '" disabled />
		</div>
	</div>
	
	<div class="form-group">
		<label class="control-label col-sm-2" for="username">' . AdminPhrase('reporting_plugins') . '</label>
		<div class="col-sm-6">
			<select name="reporting_plugin" class="form-control">';
  while($p = $DB->fetch_array($getplugins,null,MYSQL_ASSOC))
  {
    echo '<option value="'.$p['pluginid'].'" /> '.$p['name'].' ('.$p['pluginid'].')</option>';
  }
  echo '</select>';

  echo '
    </div>
</div>

	<div class="form-group">
		<label class="control-label col-sm-2" for="username">' . AdminPhrase('receive_emails') . '</label>
		<div class="col-sm-6">
			<input type="checkbox" class="ace" name="receive_emails" value="1" /><span class="lbl"></span>
		</div>
	</div>
  <div class="center">
  	<button class="btn btn-info" type="submit" value="" ><i class="ace-icon fa fa-plus"></i> ' . AdminPhrase('insert_moderator') . '</button>
</div>
  </form>';


} //AddModeratorForm


// ############################################################################
// INSERT MODERAOTOR
// ############################################################################

function InsertModerator()
{
  global $DB, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_REPORTS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
    return;
  }

  $user = array();
  $user['userid'] = 0;
  $user['username'] = '';
  if($userid = Is_Valid_Number(GetVar('userid', 0, 'whole_number', true, false),0,1,9999999))
  {
    $user = SDUserCache::CacheUser($userid,'',false,false,true);
  }
  if(empty($user['userid']))
  {
    RedirectPage(SELF_REPORTS_PHP.'?action=display_report_settings',
                 AdminPhrase('err_no_user'));
    return;
  }

  $receive = GetVar('receive_emails', false, 'bool', true, false);
  $pluginid = Is_Valid_Number(GetVar('reporting_plugin', false, 'whole_number', true, false),0,2,9999999);
  if(empty($pluginid))
  {
    $DB->query('DELETE FROM {report_moderators} WHERE userid = %d',$userid);
    RedirectPage(SELF_REPORTS_PHP.'?action=display_report_settings',
                 '<strong>'.AdminPhrase('err_mod_no_plugins').'</strong>',true);
    return;
  }

  global $usersystem;
  $DB->query('INSERT INTO {report_moderators} (userid,pluginid,subitemid,usersystemid,receiveemails,email)'.
    " VALUES(%d,%d,0,%d,%d,'%s')",
    $userid,$pluginid,$usersystem['usersystemid'],($receive?1:0),
    $DB->escape_string($user['email']));

  RedirectPage(SELF_REPORTS_PHP.'?action=display_report_settings',
               AdminPhrase('reports_settings_updated'));

} //InsertModerator


// ############################################################################
// UPDATE MODERATORS
// ############################################################################

function UpdateModerators()
{
  global $DB, $sdlanguage, $usersystem;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_REPORTS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
    return;
  }

  // First remove flagged moderators
  $removed = array();
  $remove = GetVar('remove_mod', false, 'array', true, false);
  if(!empty($remove))
  {
    foreach($remove as $key => $flag)
    {
      // $key is formatted "plugin_userid", so split it:
      list($pid, $userid) = @explode('_',$key);
      if( empty($pid) || empty($userid) ||
          (!$pid = Is_Valid_Number($pid,0,2,99999)) ||
          (!$userid = Is_Valid_Number($userid,0,1,9999999)) )
      {
        break;
      }

      // Cache removed entries for the following steps
      $removed[$key] = 1;

      $DB->query('DELETE FROM {report_moderators}'.
                 ' WHERE usersystemid = %d AND userid = %d AND pluginid = %d',
                 $usersystem['usersystemid'], $userid, $pid);
    }
  }

  // First reset email flag for all moderators
  $receive = GetVar('receive', false, 'array', true, false);
  $DB->query('UPDATE {report_moderators} SET receiveemails = 0'.
             ' WHERE usersystemid = %d', $usersystem['usersystemid']);

  // Process the posted user id's and set their email flag
  if(!empty($receive))
  {
    foreach($receive as $key => $flag)
    {
      // $key is formatted "plugin_userid", so split it:
      list($pid, $userid) = @explode('_',$key);
      if( empty($pid) || empty($userid) ||
          (!$pid = Is_Valid_Number($pid,0,2,99999)) ||
          (!$userid = Is_Valid_Number($userid,0,1,9999999)) )
      {
        break;
      }

      // Only update if plugin/moderator combo was not removed earlier:
      if(!isset($removed[$key]))
      $DB->query('UPDATE {report_moderators} SET receiveemails = 1'.
                 ' WHERE usersystemid = %d AND userid = %d AND pluginid = %d',
                 $usersystem['usersystemid'], $userid, $pid);
    }
  }

  // Process the posted user emails
  $emails = GetVar('moderator_email', false, 'array', true, false);
  if(!empty($emails))
  {
    foreach($emails as $key => $email)
    {
      // $key is formatted "plugin_userid", so split it:
      list($pid, $userid) = @explode('_',$key);
      if( empty($pid) || empty($userid) ||
          (!$pid = Is_Valid_Number($pid,0,2,99999)) ||
          (!$userid = Is_Valid_Number($userid,0,1,9999999)) )
      {
        break;
      }

      // Don't process removed plugin/moderator combos
      if(isset($removed[$key])) continue;

      if(!IsValidEmail($email))
      {
        echo 'Email Error: <strong>'.$email.'</strong><br />';
      }
      else
      {
        $DB->query("UPDATE {report_moderators} SET email = '%s'".
                   ' WHERE usersystemid = %d AND userid = %d AND pluginid = %d',
                   $DB->escape_string($email),
                   $usersystem['usersystemid'], $userid, $pid);
      }
    }
    echo '<br />';
  }

  RedirectPage(SELF_REPORTS_PHP.'?action=display_report_settings',
               AdminPhrase('moderators_updated'),1);

} //UpdateModerators


// ############################################################################
// DISPLAY REPORTS SETTINGS
// ############################################################################

function DisplayReportSettings()
{
  global $DB, $admin_phrases, $plugin_names, $usersystem;

  $getsettings = $DB->query("SELECT * FROM {mainsettings} WHERE groupname = 'user_reports_settings' ORDER BY displayorder ASC");

  
  echo'
  <form method="post" action="'.SELF_REPORTS_PHP.'" role="form">
  <input type="hidden" id="new_moderator_id" name="new_moderator_id" value="" />
  <input type="hidden" name="action" value="add_moderator_form" />
  '.PrintSecureToken() . '
  <div class="col-sm-3 align-left no-padding-left">
  <div class="input-group">
		<input type="text" id="UserSearch" name="UserSearch" class="form-control search-query" value="" placeholder="'. AdminPhrase('common_username') . '" />
		<span class="input-group-btn">
			<button type="submit" class="btn btn-purple btn-sm" id="submit">
				' . AdminPhrase('add_moderator') . '
				<i class="ace-icon fa fa-search icon-on-right bigger-110"></i>
			</button>
		</span>
	</div>
	</div>
	<div class="space-20"></div><br /></form>';
  
  
   echo '
  <form method="post" action="'.SELF_REPORTS_PHP.'" id="repmods">
  '.PrintSecureToken();
  
  StartTable(AdminPhrase('report_moderators'), array('table', 'table-bordered', 'table-striped'));
  
  echo '
  <thead>
  <tr>
    <th>'.AdminPhrase('reportable_plugin').'</th>'.
    #<td">Plugin Subitem</td>
    '<th>'.AdminPhrase('report_moderators').'</th>
    <th class="center">'.AdminPhrase('moderator_receives_emails').'</th>
    <th>'.AdminPhrase('moderator_email').'</th>
    <th class="center">'.AdminPhrase('report_moderator_remove').'</th>
  </tr>
  </thead>
  <tbody>
  ';

  $IsSD = ($usersystem['name'] == 'Subdreamer');
  if($IsSD)
  {
    $getmods = $DB->query("SELECT r.*, IFNULL(p.name,'-') plugin_name, u.username".
      ' FROM {report_moderators} r'.
      ' INNER JOIN {plugins} p ON p.pluginid = r.pluginid'.
      ' INNER JOIN {users} u ON u.userid = r.userid'.
      ' WHERE usersystemid = %d',$usersystem['usersystemid']);
  }
  else
  {
    $getmods = $DB->query("SELECT r.*, IFNULL(p.name,'-') plugin_name".
      ' FROM {report_moderators} r'.
      ' INNER JOIN {plugins} p ON p.pluginid = r.pluginid'.
      ' WHERE usersystemid = %d',$usersystem['usersystemid']);
  }
  if($count = $DB->get_num_rows($getmods))
  {
    while($mod = $DB->fetch_array($getmods,null,MYSQL_ASSOC))
    {
      if(!$IsSD)
      {
        $mod['username'] = '-';
        if($user = SDUserCache::CacheUser($mod['userid'],'',false,false,true))
        {
          $mod['username'] = $user['username'];
        }
      }
      $modkey = $mod['pluginid'].'_'.$mod['userid'];
      echo '
    <tr>
      <td class="align-middle">'.
        (isset($plugin_names[$mod['pluginid']]) ? $plugin_names[$mod['pluginid']] : $mod['plugin_name']).
        ' ('.$mod['pluginid'].')</td>
      <td class="align-middle">'.$mod['username'].'</b></td>
      <td class="center align-middle">
        <input type="checkbox" class="ace" name="receive['.$modkey.']" value="1" '.(empty($mod['receiveemails'])?'':'checked="checked" ').' />
		<span class="lbl"></span></td>
      <td><input type="text" class="form-control" name="moderator_email['.$modkey.']" value="'.$mod['email'].'" /></td>
      <td class="center align-middle"><input type="checkbox" class="ace remove" name="remove_mod['.$modkey.']" value="1" /><span class="lbl"></span></td>
    </tr>';
    }
    echo '</tbody></table>';
    echo '
    <div class="center">';
    PrintSubmit('update_moderators',AdminPhrase('update_moderators'),'repmods','fa-check');
    echo '</div>';
  }
  else
  {
    echo '<tr><td class="center" colspan="5" >
      No moderators configured yet.</td>
      </tr>
   </tbody> </table>';
  }
  echo '
  </form></br>';

  echo '
  <form method="post" action="'.SELF_REPORTS_PHP.'" id="repsettings" class="form-horizontal">';
  echo '<h3 class="header blue lighter">' . AdminPhrase('reports_report_settings') . '</h3>';
  echo '
  <input type="hidden" name="action" value="updatesettings" />
  '.PrintSecureToken();

  while($setting = $DB->fetch_array($getsettings,null,MYSQL_ASSOC))
  {
    PrintAdminSetting($setting);
  }


  echo '
  <div class="center">';
  PrintSubmit('updatesettings',AdminPhrase('common_update_settings'),'repsettings','fa-check');
  echo '</div>
  </form></br>';
} //DisplayReportSettings


// ############################################################################
// DISPLAY REPORTS
// ############################################################################

function DisplayReports($pluginid = 0)
{
  global $DB, $mainsettings_modrewrite, $plugin_names, $usersystem,
         $search, $sdlanguage;

  $items_per_page = $search['limit'];
  $page = GetVar('page', 1, 'int');
  $limit = ' LIMIT '.(($page-1)*$items_per_page).','.$items_per_page;
  $pagination_target = SELF_REPORTS_PHP_DEFAULT;

  $where = 'WHERE';

  if($pluginid)
  {
    $title = $DB->query_first('SELECT name FROM {plugins} WHERE pluginid = %d', $pluginid);
    $title = $title['name'] . ' ' . AdminPhrase('reports');

    $where .= ' p.pluginid = '.(int)$pluginid;
  }
  else
  {
    // Latest Reports
    $title = AdminPhrase('reports_latest_reports');
  }

  // Search for report?
  if(strlen($search['report']))
  {
    $where .= ($where != 'WHERE' ? ' AND' : '') .
               " (c.user_msg LIKE '%" . $DB->escape_string($search['report']) . "%')";
  }

  // search for status?
  if(strlen($search['status']) && ($search['status'] != 'all'))
  {
    $where .= ($where != 'WHERE' ? ' AND' : '') .
               ' (c.is_closed = ' .
               ($search['status']=='closed'?1:0) . ')';
  }

  // search for username?
  if(strlen($search['username']))
  {
    $where .= ($where != 'WHERE' ? ' AND' : '') .
               " ((c.username LIKE '%%" . $DB->escape_string($search['username']) . "%%') OR
                  (c.reported_username LIKE '%%" . $DB->escape_string($search['username']) . "%%')) ";
  }

  // search for ipaddress?
  if(strlen($search['ipaddress']))
  {
    $where .= ($where != 'WHERE' ? ' AND' : '') .
               " (c.ipaddress LIKE '%%" . $DB->escape_string($search['ipaddress']) . "%%')";
  }

  // search for pluginid?
  if(strlen($search['pluginid']) && (int)$search['pluginid'] > 1)
  {
    $where .= ($where != 'WHERE' ? ' AND' : '') .
               ' (c.pluginid = ' . (int)$search['pluginid'] . ')';
  }

  $where = $where=='WHERE' ? '' : $where;
  $select = 'SELECT COUNT(*) ccount FROM {users_reports} c'.
            ' LEFT JOIN {plugins} p ON p.pluginid = c.pluginid '.$where;

  // Get the total count of comments with conditions
  $reports_count = 0;
  $DB->result_type = MYSQL_ASSOC;
  if($getcount = $DB->query_first($select))
  {
    $reports_count = (int)$getcount['ccount'];
  }

  $sort = $search['sorting'];
  if($sort==REPORTS_IP_SORT_ASC)
    $search['sorting'] = 'INET_ATON(ipaddress) ASC';
  elseif($sort==REPORTS_IP_SORT_DESC)
    $search['sorting'] = 'INET_ATON(ipaddress) DESC';

  // Select all comment rows
  $select = 'SELECT c.*, p.name pluginname, rr.title reason_title'.
            ' FROM '.PRGM_TABLE_PREFIX.'users_reports c'.
            ' LEFT JOIN '.PRGM_TABLE_PREFIX.'report_reasons rr ON rr.reasonid = c.reasonid'.
            ' LEFT JOIN '.PRGM_TABLE_PREFIX.'plugins p ON p.pluginid = c.pluginid '.
            $where.
            ' ORDER BY '.$search['sorting'].$limit;
  $getreports = $DB->query($select);

  // Fetch distinct list of all referenced plugins from reports
  $plugins_arr = array();
  if($getplugins = $DB->query('SELECT DISTINCT p.pluginid, p.name'.
                              ' FROM {users_reports} c'.
                              ' INNER JOIN {plugins} p ON p.pluginid = c.pluginid'))
  {
    while($plugin = $DB->fetch_array($getplugins,null,MYSQL_ASSOC))
    {
      $plugins_arr[$plugin['pluginid']] = (isset($plugin_names[$plugin['pluginid']]) ? $plugin_names[$plugin['pluginid']] : $plugin['name']);
    }
    @ksort($plugins_arr);
  }

  // ######################################################################
  // DISPLAY COMMENTS SEARCH BAR
  // ######################################################################

  echo '
  <form action="'.SELF_REPORTS_PHP.'?action=displayreports" id="searchreports" name="searchreports" method="post">
  '.PrintSecureToken().'
  <input type="hidden" name="customsearch" value="1" />
  <input type="hidden" name="clearsearch" value="0" />';
  
  StartTable(AdminPhrase('reports_filter_title'), array('table','table-bordered'));
  
  echo'
  	<thead>
  <tr>
    <th >' . AdminPhrase('menu_reports').'</th>
    <th>' . AdminPhrase('reports_status') . '</th>
    <th>' . AdminPhrase('reports_username') . '</th>
    <th>' . AdminPhrase('reports_plugin') . '</th>
    <th>' . AdminPhrase('reports_ip') . '</th>
    <th>' . AdminPhrase('reports_sort_by') . '</th>
    <th>' . AdminPhrase('reports_limit') . '</th>
    <th width="5%">' . AdminPhrase('reports_filter') . '</th>
  </tr>
  </thead>
  <tbody>
  <tr>
    <td><input type="text" name="searchreport" style="width: 90%;" value="' . $search['report'] . '" /></td>
    <td>
      <select name="searchstatus" style="width: 95%;">
        <option value="all"' .   ($search['status'] == 'all'   ?' selected="selected"':'') .'>---</option>
        <option value="open"' .  ($search['status'] == 'open'  ?' selected="selected"':'') .'>'.AdminPhrase('reports_open').'</option>
        <option value="closed"'. ($search['status'] == 'closed'?' selected="selected"':'') .'>'.AdminPhrase('reports_closed').'</option>
      </select>
    </td>
    <td><input type="text" id="username" name="searchusername" style="width: 90%;" value="' . $search['username'] . '" /></td>
    <td>
      <select id="searchpluginid" name="searchpluginid" style="width: 95%">
        <option value="0"'.(empty($search['pluginid'])?' selected="selected"':'').'>---</option>';

  foreach($plugins_arr as $p_id => $p_name)
  {
    if($p_id > 1) // do not list "empty" plugin
    {
      echo '
        <option value="'.$p_id. '" ' . ($search['pluginid']==$p_id?'selected="selected"':'') .'>'. $p_name . '</option>';
    }
  }

  echo '
      </select>
    </td>
    <td><input type="text" id="ipaddress" name="ipaddress" value="' . $search['ipaddress']. '" size="8" style="width:90%" /></td>
    <td>
      <select name="searchsorting" style="width:95%">
        <option value="'.REPORTS_DATE_SORT_DESC.'" ' .
        ($sort == REPORTS_DATE_SORT_DESC?'selected="selected"':'') .'>'.AdminPhrase('reports_date_descending').'</option>
        <option value="'.REPORTS_DATE_SORT_ASC.'" ' .
        ($sort == REPORTS_DATE_SORT_ASC?'selected="selected"':'') .'>'.AdminPhrase('reports_date_asc').'</option>
        <option value="'.REPORTS_UNAME_SORT_ASC.'" ' .
        ($sort == REPORTS_UNAME_SORT_ASC?'selected="selected"':'') .'>'.AdminPhrase('reports_uname_asc').'</option>
        <option value="'.REPORTS_UNAME_SORT_DESC.'" ' .
        ($sort == REPORTS_UNAME_SORT_DESC?'selected="selected"':'') .'>'.AdminPhrase('reports_uname_descending').'</option>
        <option value="'.REPORTS_IP_SORT_ASC.'" ' .
        ($sort == REPORTS_IP_SORT_ASC?'selected="selected"':'') .'>'.AdminPhrase('reports_ip_asc').'</option>
        <option value="'.REPORTS_IP_SORT_DESC.'" ' .
        ($sort == REPORTS_IP_SORT_DESC?'selected="selected"':'') .'>'.AdminPhrase('reports_ip_descending').'</option>
      </select>
    </td>
    <td style="width: 40px;"><input type="text" name="searchlimit" style="width: 35px;" value="' . $items_per_page . '" size="2" /></td>
    <td class="align-center align-middle">
      <input type="submit" value="'.AdminPhrase('search').'" style="display:none" />
	   <a id="reports-submit-search" href="#" onclick="return false;" title="'.
        AdminPhrase('reports_apply_filter').'"><i class="ace-icon fa fa-search bigger-130"></i></a> &nbsp;
      <a id="reports-clear-search" href="#" onclick="return false;" title="'.
        AdminPhrase('reports_clear_filter').'"><i class="ace-icon fa fa-trash-o red bigger-130"></i></a>
    </td>
  </tr>
  </tbody>
  </table>
  </div>
  </form>
  ';


  if(!$reports_count)
  {
    DisplayMessage(AdminPhrase('reports_no_reports_found'));
    return;
  }

  echo '
  <form action="'.SELF_REPORTS_PHP.'" method="post" id="reports" name="reports">
  <input type="hidden" name="action" value="delete_reports" />
  '.PrintSecureToken();
  
  StartTable($title, array('table','table-bordered','table-striped'));
  
  echo'
  <thead>
  <tr>
    <th width="15%">' . AdminPhrase('reports_edit_report') . '</th>
    <th width="5%" align="center">' . AdminPhrase('reports_closed') . '</th>
    <th class="center">' . AdminPhrase('common_page') . '</th>
    <th>' . AdminPhrase('reports_plugin_name') . '</th>
    <th>' . AdminPhrase('reported_content') . '</th>
    <th>' . AdminPhrase('reported_by') . '</th>
    <th>' . AdminPhrase('reports_ipaddress') . '</th>
    <th>' . AdminPhrase('reports_date') . '</th>
    <th width="70" class="center">
      <a id="checkall" rel="0" title="'.htmlspecialchars(AdminPhrase('common_delete'),ENT_COMPAT).
		  '" href="#" onclick="javascript:return false;"><i class="ace-icon fa fa-trash-o red bigger-130"></i></a>
	  <span class="lbl"></span>
    </th>
  </tr>
  </thead>
  <tbody>';

  $count = 0;
  while($report = $DB->fetch_array($getreports,null,MYSQL_ASSOC))
  {
    // Make sure the user does have admin/mod permissions for plugin
    if(!empty($userinfo['adminaccess']) ||
      (!empty($userinfo['pluginadminids']) && @in_array($report['pluginid'],$userinfo['pluginadminids'])) ||
      (!empty($userinfo['pluginmoderateids']) && @in_array($report['pluginid'],$userinfo['pluginmoderateids'])) )
    {
      continue;
    }
    $count++;

    if(sd_strlen($report['user_msg']) > 30)
    {
      $report['user_msg'] = sd_substr(trim($report['user_msg']), 0, 30) . '...';
    }

    $item_content = '';
    $item_title = SD_Reports::TranslateObjectID($report['pluginid'], $report['objectid1'], $report['objectid2'], $item_content);
    $reported_user = SDUserCache::CacheUser($report['reported_userid'],'',false,false,true);

    echo '
    <tr>
      <td>
        <a href="'.SELF_REPORTS_PHP.'?action=display_report&amp;reportid=' .
          $report['reportid'] . '"><i class="ace-icon fa fa-edit blue bigger-120"></i>&nbsp; ' .
        $report['reason_title'] .
        '</a></td>
      <td class="center">
	  <i class="ace-icon fa ' . ($report['is_closed'] ? 'fa-check green' : 'fa-times red') .' bigger-120"></i>
      </td>
      <td class="center">';

    echo SD_Reports::DisplayReportLink($report['categoryid'], $report['pluginid'],
                                       $report['objectid1'], $report['objectid2']);

    $obj1_link = SD_Reports::DisplayReportLink($report['categoryid'], $report['pluginid'],
                                               $report['objectid1'], 0, true);

    echo '</td>
      <td>'.
       (isset($plugin_names[$report['pluginid']]) ? $plugin_names[$report['pluginid']] :$report['pluginname']).'
       ('.$report['pluginid'].')
      </td>
      <td>';

    // Display link for major object (e.g. forum topic)
    if(!empty($obj1_link) && ($obj1_link !== '-'))
    {
      echo '<a href="'.$obj1_link.'" target="_blank">';
    }
    if(sd_strlen($item_title) > 40) $item_title = sd_substr($item_title,0,40).'...';
    echo '<i class="ace-icon fa fa-search blue bigger-130"></i>&nbsp;<span class="bigger-115 lighter">'.
         strip_tags($item_title).'</span>';
    if(!empty($obj1_link) && ($obj1_link !== '-'))
    {
      echo '</a>';
    }

    echo '
        <hr class="space-0">
          '.nl2br(htmlspecialchars($item_content)).'</p>';
    if($usersystem['name']=='Subdreamer')
    {
      echo '<i class="ace-icon fa fa-user bigger-120 red" title="'.AdminPhrase('reported_user') . '"></i>&nbsp; <a href="users.php?action=display_user_form&userid='.$report['reported_userid'].'">';
    }
    echo '<span class="red">'.
         $reported_user['username'].'</span>';
    if($usersystem['name']=='Subdreamer')
    {
      echo '</a>';
    }

    echo '</td>
      <td><a class="mytooltip username" title="'.htmlspecialchars(AdminPhrase('filter_username_hint')).'" href="#" onclick="return false;"><i class="ace-icon fa fa-user"></i> '.$report['username'].'</a></td>
      <td>';
      // Note: first link in this <td> cell MUST be the link with the IP as text!!!
      $ip = !empty($report['ipaddress']) ? (string)$report['ipaddress'] : '';
      if($ip !== '')
      {
        $style = '';
        if(sd_IsIPBanned($ip))
        {
          $style = ' class="red" ';
        }
        echo '
		<a href="#" class="mytooltip hostname" title="IP Tools"><i class="ace-icon fa fa-wrench"></i> <span class="text-hide">'.$ip.'</span></a>
		<a class="mytooltip ipaddress" title="'.
          htmlspecialchars(AdminPhrase('filter_ip_hint')).'" href="#" '.$style.'onclick="return false;">'.$ip.'</a>';
      }

      echo '</td>
      <td>' . DisplayDate($report['datereported']) . '</td>
      <td class="center">
        <input type="hidden" name="report_id_arr[]" value="' . $report['reportid'] . '" />
        <input type="hidden" name="plugin_id_arr[]" value="' . $report['pluginid'] . '" />
        <input type="hidden" name="object_id_arr[]" value="' . $report['objectid1'] . '" />
        
		<input type="checkbox" class="ace deletereport" name="delete_report_id_arr[]" checkme="deletereport"  value="' . $report['reportid'] . '" />
		<span class="lbl"></span>
		
      </td>
    </tr>';
  } //while

  if($count)
  {
    /*
    echo '
    <tr>
      <td colspan="9" align="left"> '.
      $reports_count.' '.AdminPhrase('menu_reports').'</td>
    </tr>';
    */
    echo '
    <tr>
      <td colspan="8" class="align-right">
        <label for="approve_deleted_multi">' .
        AdminPhrase('reports_delete_multi_approve') . '</label>
	</td>
	<td class="center align-middle">
        <input type="checkbox" class="ace" value="1" id="approve_deleted_multi" name="approve_deleted_multi" />
		<span class="lbl"></span>
      </td>
    </tr>
     </tbody>
  </table>
  </div>
      <div class="align-right">
        <button class="btn btn-xs btn-danger" id="submit_delete" type="submit" value="' . AdminPhrase('reports_delete') . '" /><i class="ace-icon fa fa-trash-o"></i>&nbsp; ' . AdminPhrase('reports_delete') .'</button>
      </div>';
  }
  echo '
  </form>
  ';

  if(!$count)
  {
    DisplayMessage(AdminPhrase('reports_no_reports_found'));
    return;
  }

  // pagination
  if(!empty($reports_count))
  {
    $p = new pagination;
    $p->items($reports_count);
    $p->limit($items_per_page);
    $p->currentPage($page);
    $p->adjacents(7);
    $p->target($pagination_target);
    $p->show();
  }

  // SD343: make IP address clickable
  $moreJS = '
  $("a.ipaddress").click(function(e) {
    e.preventDefault();
    var new_val = $(this).text();
    $("#searchreports #ipaddress").attr("value", new_val);
    $("#searchreports").submit();
  });
  $("a.username").click(function(e) {
    e.preventDefault();
    var new_val = $(this).text();
    $("#searchreports #username").attr("value", new_val);
    $("#searchreports").submit();
  });
  ';
  DisplayIPTools('a.hostname', 'td', $moreJS); //SD343

} //DisplayReports


// ############################################################################
// DISPLAY ORGANIZED REPORTS
// ############################################################################

function DisplayOrganizedReports()
{
  global $DB;

  $getrows = $DB->query('SELECT p.pluginid, p.name AS pluginname, COUNT(*) AS count'.
                        ' FROM {plugins} p, {users_reports} c'.
                        ' WHERE p.pluginid = c.pluginid'.
                        ' GROUP BY p.pluginid'.
                        ' ORDER BY count DESC');

  StartTable(AdminPhrase('reports_by_plugin'), array('table', 'table-bordered', 'table-striped'));
  echo '
  <thead>
  <tr>
    <th>' . AdminPhrase('reports_plugin') . '</th>
    <th>' . AdminPhrase('reports_number_of_reports') . '</th>
  </tr>
  </thead>
  <tbody>';

  $reports_count = 0;
  while($report = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
  {
    $reports_count++;
    echo '
    <tr>
      <td><a href="'.SELF_REPORTS_PHP.'?pluginid='.
      $report['pluginid']. '">' . $report['pluginname'] . '</a></td>
      <td>' . $report['count'] . '</td>
    </tr>';
  }
  echo '</tbody></table></div>';
  if(!$reports_count)
  {
    DisplayMessage(AdminPhrase('reports_no_reports_found'));
  }

} //DisplayOrganizedReports


// ############################################################################
// DISPLAY REPORT REASONS
// ############################################################################

function DisplayReportReasons()
{
  global $DB;

  $editlink = SELF_REPORTS_PHP.'?action=display_reason&amp;reasonid=';

 echo '<div class="align-right">
 			<form method="post" action="'.SELF_REPORTS_PHP.'?reasonid=new" id="addreason">
      			'.PrintSecureToken();
  						PrintSubmit('display_reason', AdminPhrase('add_report_reason'),'addreason','fa-plus','','','btn-success');
  echo '
      </form>
	  </div>
	  <br />';

 
  StartTable(AdminPhrase('reports_reasons'), array('table', 'table-bordered', 'table-striped'));

  echo '
	<thead> 
  <tr>
    <th>' . AdminPhrase('reports_reason_title') . '</th>
    <th>' . AdminPhrase('reports_reason_description') . '</th>
    <th>' . AdminPhrase('reports_plugin_name') . '</th>
  </tr>
  </thead>
  <tbody>';

  $reasonid = 0;
  $rowcount = 0;

  $getrows = $DB->query('SELECT p.pluginid, p.name pluginname, r.*'.
                        ' FROM {report_reasons} r'.
                        ' LEFT JOIN {report_reasons_plugins} rp ON rp.reasonid = r.reasonid'.
                        ' LEFT JOIN {plugins} p ON p.pluginid = rp.pluginid'.
                        ' ORDER BY r.title, p.name');

  while($row = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
  {
    $rowcount++;
    echo '<tr>';
    if($reasonid != $row['reasonid'])
    {
      $reasonid = $row['reasonid'];
      echo '
      <td>
        <a href="'.$editlink.$reasonid.'"><i class="ace-icon fa fa-edit blue bigger-120"></i>&nbsp;'.
        $row['title'].'</a></td>
      <td width="50%">'.strip_alltags($row['description']).'</td>';
    }
    else
    {
      echo '
      <td colspan="2">&nbsp;</td>';
    }
    echo '<td>'.$row['pluginname'].'</td>
    </tr>';
  }
  echo '</table></div>';
  if(!$rowcount)
  {
    DisplayMessage(AdminPhrase('reports_no_reports_found'));
  }


} //DisplayReportReasons


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
