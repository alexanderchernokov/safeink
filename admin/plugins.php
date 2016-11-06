<?php
// DEFINE CONSTANTS
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');
define('PLUGINS_INSTALL_FILE', 'install.php');

// INIT PRGM
require(ROOT_PATH . 'includes/init.php');
include_once(ROOT_PATH . 'includes/enablegzip.php');

// LOAD ADMIN LANGUAGE
$admin_phrases = LoadAdminPhrases(2);

// GET ACTION
$action = GetVar('action', 'display_plugins', 'string');
$pageid = Is_Valid_Number(GetVar('page', 1, 'whole_number'),1,1,4); //SD370

// ALL forms within this file generate a security token for $_POST
// so check for it right now!
if((substr($action,0,7) != 'display'))
{
  if(!CheckFormToken(null, true)) // in functions_security.php
  {
    return false;
  }
}

//SD370: deletion of custom plugins by ajax
if(($action=='delete_custom_plugins'))
{
  DeleteCustomPlugins();
  exit();
}

//SD322: "cbox" is a flag to notify of the page being called by CeeBox
//       "$NoMenu" is currently only used for displaying plugin permissions
$cbox = GetVar('cbox', false, 'bool');
defined('IS_CBOX') || define('IS_CBOX', (bool)$cbox);
$NoMenu = $cbox || ($action=='display_plugin_permissions');
$admin_sub_menu_arr = array();

if($action=='display_plugins' || $action == 'display_custom_plugin_form')
{
	// SD313 - Bind click event for deletion links to prompt user for confirmation
  // SD322 - If JS is enabled, pass a parameter with every plugin permissions link,
  //         process all "permissionlink" anchors to use CeeBox (if available)
  sd_header_add(array(
  #'css'   => (IS_CBOX?array():array('bootstrap-min.css')),
  'js'    => #(IS_CBOX?array():array('bootstrap-tab.js')),
             array(
               'jquery.aciPlugin.min.js', //SD370
               'jquery.aciFragment.min.js' //SD370
             ),
  'other' => array('
<script type="text/javascript">
var api;
if (typeof(jQuery) !== "undefined") {
function showResponse(msg, blocking) {
  if(!msg || msg == "" || msg == "1") 
  { 
  	msg = "Done."; 
  }
  var n = noty({
					text: \'msg\',
					layout: \'top\',
					type: \'success\',	
					timeout: 5000,					
					});
  
}

jQuery(document).ready(function() {
  $("ul.nav-tabs li").on("click", function(e) {
	  var id = $(this).attr("id");
	  id = id.replace("tab-","");
	  $("#tab").val(id);
  });
  $("a.deletelink").on("click", function(e) {
		var link = $(this).attr("href");
	  	e.preventDefault();
  		bootbox.confirm("'.AdminPhrase('confirm_plugin_delete') .'", function(result) {
    	if(result) {
       		window.location.href = link;
    	}
  		});
	});

  $("#delete_customplugins").click(function(e) {
    var $this = $(this);
    var checked = $("form#allplugins input.delcustom:checked");
    if(!checked.length){
      e.preventDefault();
      return false;
    }
	 
	 bootbox.confirm("'.addslashes(AdminPhrase('plugins_confirm_custom_delete')).'", function(result) {
				if(result) {
				  $this.hide();
    var formdata = {
      "action": "delete_custom_plugins",
      "form_token": $("form#allplugins input[name=form_token]").val(),
      "ids": $(checked).serialize()
    };
    $.post("plugins.php", formdata,
      function(data, status) {
        $this.show();
        if(status != "success" || data.trim() != "success") {
	
			var n = noty({
					text: \''.AdminPhrase('message_no_access_uninstall').'\',
					layout: \'top\',
					type: \'error\',	
					timeout: 5000,					
					});
         
        }
      }, "text")
      .done(function(){
		  
		  var n = noty({
					text: \''.AdminPhrase('plugins_custom_plugin_deleted').'\',
					layout: \'top\',
					type: \'success\',	
					timeout: 5000,					
					});
			 $("tr.danger").remove();
       
      });
    return true;
				}
  			  });

  });
  
  

  $("table#customplugins a#checkall").click(function(e){
    e.preventDefault();
    var ischecked = 1 - parseInt($(this).attr("rel"),10);
    if(ischecked==1) {
      $("table#customplugins input.delcustom").attr("checked","checked");
      $("table#customplugins tr").not(":last-child").addClass("danger");
    } else {
      $("table#customplugins input.delcustom").removeAttr("checked");
      $("table#customplugins tr").not(":last-child").removeClass("danger");
    }
    $(this).attr("rel",ischecked);
    return false;
  });

  $("input.delcustom").on("change",function(){
    var tr = $(this).parents("tr");
    $(tr).toggleClass("danger");
  });
  
  function showErrorAlert (reason, detail) {
		var msg=\'\';
		if (reason===\'unsupported-file-type\') { msg = "Unsupported format " +detail; }
		else {
			//console.log("error uploading file", reason, detail);
		}
		$(\'<div class="alert"> <button type="button" class="close" data-dismiss="alert">&times;</button>\'+ 
		 \'<strong>File upload error</strong> \'+msg+\' </div>\').prependTo(\'#alerts\');
	}
	
$("[data-rel=popover]").popover({container:"body"});
  
  $("#custom-html").ace_wysiwyg({
  	toolbar:
		[
			\'font\',
			null,
			\'fontSize\',
			null,
			{name:\'bold\', className:\'btn-info\'},
			{name:\'italic\', className:\'btn-info\'},
			{name:\'strikethrough\', className:\'btn-info\'},
			{name:\'underline\', className:\'btn-info\'},
			null,
			{name:\'insertunorderedlist\', className:\'btn-success\'},
			{name:\'insertorderedlist\', className:\'btn-success\'},
			{name:\'outdent\', className:\'btn-purple\'},
			{name:\'indent\', className:\'btn-purple\'},
			null,
			{name:\'justifyleft\', className:\'btn-primary\'},
			{name:\'justifycenter\', className:\'btn-primary\'},
			{name:\'justifyright\', className:\'btn-primary\'},
			{name:\'justifyfull\', className:\'btn-inverse\'},
			null,
			{name:\'createLink\', className:\'btn-pink\'},
			{name:\'unlink\', className:\'btn-pink\'},
			null,
			{name:\'insertImage\', className:\'btn-success\'},
			null,
			\'foreColor\',
			null,
			{name:\'undo\', className:\'btn-grey\'},
			{name:\'redo\', className:\'btn-grey\'},
			null,
			{name:\'viewSource\', classname:\'btn-grey\'}
		],
		\'wysiwyg\': {
			fileUploadError: showErrorAlert
		}
	}).prev().addClass(\'wysiwyg-style2\');
	
	$("#custompluginedit").on("submit", function() {
  		var hidden_input = $("<input type=\"hidden\" name=\"plugin\" />").appendTo("#custompluginedit");

  var html_content = $("#custom-html").html();
  hidden_input.val(html_content);
  //put the editor\'s HTML into hidden_input and it will be sent to server
});
	
	


  if(typeof(jQuery.fn.ceebox) !== "undefined") {
    jQuery("a.permissionslink").each(function(event) {
      jQuery(this).attr("class", "permissionslink cbox").attr("rel", "iframe modal:false height:520");
      var link = jQuery(this).attr("href") + "&amp;cbox=1";
      jQuery(this).attr("href", link);
    });
    '.
    GetCeeboxDefaultJS(false).
    '
  }

  if(typeof($.fn.aciFragment) !== "undefined") {
    $(document).bind("acifragment", function(event, api, anchorChanged) {
      var page = 1;
      var anchor = api.get("page", false);
      if(!anchor) return false;
      var page = parseInt(anchor,10);
      if(page >= 1 && page <= 4) {
        page--;
        $("a.mt-b-"+page).trigger("click");
      } else {
        api.set("page", 1);
      }
    });
    $.fn.aciFragment.defaults.scroll = null;
    api = $(document).aciFragment("api");
    $(document).aciFragment();
  }
});
}
</script>
')));
				   
  // SD313 - Bind click event for deletion links to prompt user for confirmation
  // SD322 - If JS is enabled, pass a parameter with every plugin permissions link,
  //         process all "permissionlink" anchors to use CeeBox (if available)

}

// CHECK PAGE ACCESS
CheckAdminAccess('plugins');


if(!in_array($action, array('display_custom_plugin_form','insert_custom_plugin')))
{
  // DISPLAY ADMIN HEADER
  DisplayAdminHeader(array('Plugins', $action), null, '', $NoMenu);
}

//SD322: Special security check: only full admins have access to:
if(empty($userinfo['adminaccess']) && ($action == 'display_install_upgrade_plugins'))
{
  DisplayMessage(AdminPhrase('common_page_access_denied'), true);
  DisplayAdminFooter();
  exit;
}

// REMOVE INVALID PLUGIN ENTRIES
if(!empty($userinfo['adminaccess']) && !$NoMenu && ($action != 'display_plugins') && ($action != 'display_install_upgrade_plugins'))
{
  $DB->query("UPDATE {adminphrases} SET customphrase = '' WHERE customphrase IS NULL");
  $DB->query("DELETE FROM {adminphrases} WHERE adminpageid = 2 AND pluginid = 0 AND varname = ''");
  $DB->query("DELETE FROM {phrases} WHERE pluginid IS NULL OR pluginid = 0 OR varname = ''");
  $DB->query("DELETE FROM {pluginsettings} WHERE pluginid IS NULL OR pluginid = 0");
  $DB->query("DELETE FROM {plugins} WHERE pluginid IS NULL OR pluginid = 0 OR name = ''");
  $DB->query("DELETE FROM {comments} WHERE pluginid = 0");
  $DB->query("UPDATE {customplugins} SET settings = 17 WHERE IFNULL(settings, 0) = 0");
}

// Clear PHP file system cache
clearstatcache();

// ############################################################################
// DISPLAY PLUGINS
// ############################################################################

function DisplayPlugins()
{
  global $DB, $core_pluginids_arr, $mainsettings_enablewysiwyg, $pageid,
         $plugin_names, $sdlanguage, $userinfo;

  UninstallBrokenPlugins();
  
  $tab = GetVar('tab','main','string');
  $IsAdmin = !empty($userinfo['adminaccess']);
  $custom_plugins_maintainer = $IsAdmin || !empty($userinfo['maintain_customplugins']);
  $wysiwyg_suffix = ($mainsettings_enablewysiwyg?'&amp;load_wysiwyg=1':'');
  //$table_tag = "\r\n  ".'<table border="0" cellpadding="2" cellspacing="0" summary="layout" width="100%">';
  
  // Get Main Plugins
  $core_pluginids = implode(',',$core_pluginids_arr);
  if($forum_id = GetPluginID('Forum'))
  {
    $core_pluginids .= ','.$forum_id;
  }

  echo '
  <form id="allplugins" role="form" action="plugins.php" method="post">
  <input type="hidden" id="page" name="page" value="1" />
  <input type="hidden" id="tab" name="tab" value="'.$tab.'"/>
  '.PrintSecureToken();
  $alltabs = $headers = '';

  // ######################### DISPLAY MAIN PLUGINS ###########################
  $visible = true;
  if($IsAdmin || !empty($userinfo['pluginadminids']))
  {
    $base_exists = $DB->column_exists(PRGM_TABLE_PREFIX.'plugins', 'base_plugin');
    $get_plugins = $DB->query('SELECT pluginid, name, displayname, version'.($base_exists?', base_plugin,':'').
                              ' (SELECT COUNT(*) FROM '.PRGM_TABLE_PREFIX.'pagesort ps WHERE ps.pluginid = sdp.pluginid) pcount'.
                              ' FROM '.PRGM_TABLE_PREFIX.'plugins sdp'.
                              " WHERE (pluginid > 1) AND (pluginid IN ($core_pluginids))".
                              ' ORDER BY name ASC');
    $plugin_rows = $DB->get_num_rows($get_plugins);

    $content = '';
	
    if($plugin_rows > 0)
    {
      while($plugin_arr = $DB->fetch_array($get_plugins,null,MYSQL_ASSOC))
      {
        $pid = (int)$plugin_arr['pluginid'];
		$wysiwyg = true;
        if($IsAdmin || (!empty($userinfo['pluginadminids']) && @in_array($pid,$userinfo['pluginadminids'])) )
        {
          if(($pid == "2") || ($base_exists && ($plugin_arr['base_plugin'] == 'Articles')))
          {
            $plugin_settings_link = 'articles.php'.($pid>2?'?pluginid='.$pid:'');
			$wysiwyg = false;
          }
          else
          {
            $plugin_settings_link = 'view_plugin.php?pluginid=' . $pid;
          }

		  
		  $content .= '
          <tr>
            <td>
				<a href="' . $plugin_settings_link . ($wysiwyg ? $wysiwyg_suffix : '') . '"'. (empty($plugin_arr['pcount'])?' class="red"':'').'>
					<i class="ace-icon fa fa-edit bigger-120"></i>&nbsp;'.$plugin_names[$pid].'
				</a>
			</td>
            <td  class="align-center">' . (empty($plugin_arr['pcount'])?'-':$plugin_arr['pcount']) . '</td>
            <td  class="align-center">' . $plugin_arr['version'] . '</td>
            <td>
              <input type="hidden" name="plugin_id_arr[]" value="' . $pid . '" />
              <input type="text" class="form-control " name="plugin_display_name_arr[]" value="'.
              htmlspecialchars($plugin_arr['displayname']) . '" />
			</td>';
			
          if($IsAdmin)
          {
            $content .= '
            <td class="align-center">'.
              '<a class="permissionslink" href="plugins.php?action=display_plugin_permissions&amp;pluginid='.$pid. ($pageid>1?'&amp;page='.$pageid:'').'">
			  	<i class="ace-icon fa fa-key bigger-120 orange"></i>
			  </a>
            </td>';
          }
		  
          $content .=  '
          </tr>';
		  
        }
      } //while
      unset($plugin_arr,$get_plugins);

      if(strlen($content))
      {
        	
		$alltabs .= '
		<div class="tab-pane '.iif($tab == 'main', "in active").'" id="main">
			<div class="table-responsive">
			<table class="table table-striped">
				<thead>
					<tr>
						<th width="15%">'.AdminPhrase('plugins_plugin_name').'</th>
						<th width="5%" class="align-center">
							<a title="'.addslashes(AdminPhrase('plugins_used_hint')).'" href="#" onClick="javascript:return false;">'.AdminPhrase('plugins_used').'</a>
						</th>	
						<th  class="align-center">'.AdminPhrase('plugins_version').'</th>
						<th>'.AdminPhrase('plugins_website_display_name').'</th>';
			if($IsAdmin)
			{
			  $alltabs .= '
			  	<th class="align-center">' . AdminPhrase('plugins_permissions') . '</th>';
			}
			$alltabs .= '
					</tr>
			 	</thead>
			 	<tbody>
			 		'.$content.'
			 	</tbody>
			</table>
			</div>
		</div>';
		
		
		unset($content);
        $visible = false;
      }
    }
  } //MAIN PLUGINS


  // ######################## DISPLAY CUSTOM PLUGINS ##########################

  if($custom_plugins_maintainer || !empty($userinfo['custompluginadminids']))
  {
    $get_custom_plugins = $DB->query('SELECT custompluginid, displayname, name,'.
                                     ' (SELECT COUNT(*) FROM '.PRGM_TABLE_PREFIX."pagesort ps WHERE ps.pluginid = CONCAT('c',sdp.custompluginid)) pcount".
                                     ' FROM '.PRGM_TABLE_PREFIX.'customplugins sdp ORDER BY name');
    $custom_plugin_rows = $DB->get_num_rows($get_custom_plugins);

    if($custom_plugin_rows > 0)
    {
      $content = '';
      while($custom_plugin_arr = $DB->fetch_array($get_custom_plugins))
      {
        if( $custom_plugins_maintainer ||
            (!empty($userinfo['custompluginadminids']) &&
             @in_array($custom_plugin_arr['custompluginid'],
                       $userinfo['custompluginadminids'])) )
        {
          $cid = (int)$custom_plugin_arr['custompluginid'];
          $content .= '
		  <tr>
            <td>
              <a href="plugins.php?action=display_custom_plugin_form&amp;custompluginid=' .
              $cid . '&amp;load_wysiwyg=1'.($pageid>1?'&amp;page='.$pageid:'').'"'.
              (empty($custom_plugin_arr['pcount'])?' class="red"':'').
              '><i class="ace-icon fa fa-edit bigger-120"></i>&nbsp;'.$custom_plugin_arr['name'].'</a>
            </td>
            <td class="center">'.(empty($custom_plugin_arr['pcount'])?'-':$custom_plugin_arr['pcount']).'</td>
            <td>
              <input type="hidden" name="plugin_id_arr[]" value="c'.$cid.'" />
              <input type="text" class="form-control" name="plugin_display_name_arr[]" value="'.
              htmlspecialchars($custom_plugin_arr['displayname']) . '" />
            </td>';
          
		  if($custom_plugins_maintainer)
          {
            //SD370: deletion of multiple custom plugins, done by ajax
            $content .= '
            <td  class="align-center" width="75">
              <input class="delcustom ace" type="checkbox" name="delcustom_'. $cid.'" value="'.md5($cid.PRGM_HASH.md5($cid)).'" />
			  <span class="lbl"></span>
			 </td>
            <td  class="align-center" width="75">
              <a class="permissionslink"'. ' href="plugins.php?action=display_plugin_permissions&amp;pluginid=c'. $cid.PrintSecureUrlToken().($pageid>1?'&amp;page='.$pageid:''). '">
			  	<i class="ace-icon fa fa-key bigger-120 orange"></i>
			  </a>
			</td>';
          }
          $content .=  '
          </tr>';
        }
      } //while
      unset($custom_plugin_arr,$get_custom_plugins,$cid);

      if(strlen($content))
      {
        $alltabs .= '
		<div class="tab-pane  '.iif($tab == 'custom', "in active").' " id="custom">
			<table class="table table-striped" id="customplugins">
				<thead>
					<tr>
						<th width="20%">'.AdminPhrase('plugins_plugin_name').'</th>
						<th width="5%" class="align-center">
							<a title="'.addslashes(AdminPhrase('plugins_used_hint')).'" href="#" onClick="javascript:return false;">'.AdminPhrase('plugins_used').'</a>
						</th>	
						<th width="40%">'.AdminPhrase('plugins_website_display_name').'</th>';
						
        if($custom_plugins_maintainer)
        {
          $alltabs .= '
          <th  class="align-center" width="5%">
          	<a id="checkall" rel="0" title="'.htmlspecialchars(AdminPhrase('plugins_delete'),ENT_COMPAT). '" href="#" onclick="javascript:return false;">
				<i class="ace-icon fa fa-trash-o red bigger-130"></i>
			</a>
          </th>';
        }
		if($IsAdmin)
		{
			 $alltabs .= '
			  <th class="align-center" width="5%">' . AdminPhrase('plugins_permissions') . '</th>';
		}
        $alltabs .= '
					</tr>
			 	</thead>
			 	<tbody>
			 		'.$content.'';
		
        if($custom_plugins_maintainer)
        {
          $alltabs .= '
        <tr>
          <td colspan="3"> </td>
          <td colspan="2" class="align-left">
            <a id="delete_customplugins" href="#" class="btn btn-danger btn-xs" onclick="javascript:return false;"><i class="ace-icon fa fa-trash-o"></i>&nbsp; '.AdminPhrase('plugins_delete').'</a>
          </td>
        </tr>';
        }
        $alltabs .= '
        </tbody></table></div>';
        unset($content);
        $visible = false;
		
      }
    }
  } //CUSTOM PLUGINS


  if($IsAdmin || !empty($userinfo['pluginadminids']))
  {
    // ###################### DISPLAY DOWNLOADED PLUGINS ######################
    $get_downloaded_plugins = $DB->query('SELECT sdp.*,'.
                                         ' (SELECT COUNT(*) FROM '.PRGM_TABLE_PREFIX.'pagesort ps WHERE ps.pluginid = sdp.pluginid) pcount'.
                                         ' FROM '.PRGM_TABLE_PREFIX.'plugins sdp'.
                                         " WHERE (sdp.authorname NOT LIKE 'subdreamer_cloner%')".
                                         " AND (sdp.base_plugin = '')".
										 " AND NOT (sdp.pluginid IN ($core_pluginids))".
                                         ' ORDER BY sdp.name');
    $downloaded_plugin_rows = $DB->get_num_rows($get_downloaded_plugins);

    if($downloaded_plugin_rows > 0)
    {
      $content = '';
      while($downloaded_plugin_arr = $DB->fetch_array($get_downloaded_plugins))
      {
        $pid = (int)$downloaded_plugin_arr['pluginid'];
        if($IsAdmin || (!empty($userinfo['pluginadminids']) && @in_array($pid,$userinfo['pluginadminids'])) )
        {
          //SD341: special linking for Articles-based plugin(s)
          if(isset($downloaded_plugin_arr['base_plugin']) && ($downloaded_plugin_arr['base_plugin'] == 'Articles'))
          {
            $plugin_settings_link = 'articles.php'.($pid>2?'?pluginid='.$pid:'');
          }
          else
          {
            $plugin_settings_link = 'view_plugin.php?pluginid=' . $pid;
          }
          $path = $downloaded_plugin_arr['pluginpath'];
          $path = substr($path,0,strpos($path,'/'));
          $content .= '
          <tr>
            <td>
              <a href="'.$plugin_settings_link.'"'. (empty($downloaded_plugin_arr['pcount'])?' class="red"':'').'>
			  	<i class="ace-icon fa fa-edit"></i>&nbsp;'.$plugin_names[$pid].'
				</a>
            </td>
            <td  class="center">'.
              (empty($downloaded_plugin_arr['pcount'])?'-':$downloaded_plugin_arr['pcount']).'
            </td>
            <td  class="align-center">' . $downloaded_plugin_arr['version'] . '</td>
            <td>
              <input type="hidden" name="plugin_id_arr[]" value="'.$pid.'" />
              <input type="text" class="form-control" name="plugin_display_name_arr[]" value="' .
              htmlspecialchars($downloaded_plugin_arr['displayname']) .
              '" /><span class="helper-text">
              ' . AdminPhrase('folder') . ': '.htmlspecialchars($path).'</span>
            </td>
            ';
          if($IsAdmin)
          {
            $content .=  '
            <td  class="align-center" width="75"><a class="deletelink"  href="plugins.php?action=delete_plugin&amp;plugin_id='.
              $pid.PrintSecureUrlToken().
              '"><i class="ace-icon fa fa-trash-o bigger-120 red"></i></a></td>
            <td  class="align-center" width="75"><a class="permissionslink"'.
              ' href="plugins.php?action=display_plugin_permissions&amp;pluginid='.
              $pid.PrintSecureUrlToken().
              '"><i class="ace-icon fa fa-key bigger-120 orange"></i></a></td>';
          }
          $content .=  '
          </tr>';
        }
      } //while
      unset($downloaded_plugin_arr,$get_downloaded_plugins);

      if(strlen($content))
      {
        $alltabs .= '
        <div class="tab-pane  '.iif($tab == 'addon', "in active").'" id="download">
			<table class="table table-striped">
				<thead>
					<tr>
						<th width="20%">'.AdminPhrase('plugins_plugin_name').'</th>
						<th width="5%" class="align-center">
							<a title="'.addslashes(AdminPhrase('plugins_used_hint')).'" href="#" onClick="javascript:return false;">'.AdminPhrase('plugins_used').'</a>
						</th>	
						<th  width="5%" class="align-center">'.AdminPhrase('plugins_version').'</th>
						<th width="40%">'.AdminPhrase('plugins_website_display_name').'</th>';
						
        if($IsAdmin)
        {
          $alltabs .= '
          <th  width="5%" class="align-center">' . AdminPhrase('plugins_delete') . '</th>
          <th  width="5%" class="align-center">' . AdminPhrase('plugins_permissions') . '</th>';
        }
        $alltabs .= '
        </tr></thead><tbody>' . $content.'
        </tbody></table></div>';
        unset($content);
        $visible = false;
      }
    } //DOWNLOADED PLUGINS


    // ###################### DISPLAY CLONED PLUGINS ##########################
    // Get Cloned Plugins (for compatibility reasons)
    $get_cloned_plugins = $DB->query('SELECT pluginid, name, displayname, version, pluginpath,'.
                                     ' (SELECT COUNT(*) FROM '.PRGM_TABLE_PREFIX.'pagesort ps WHERE ps.pluginid = sdp.pluginid) pcount'.
                                     ' FROM '.PRGM_TABLE_PREFIX.'plugins sdp'.
                                     " WHERE (base_plugin != '')".
                                     " AND NOT (pluginid IN ($core_pluginids))".
                                     ' ORDER BY name');
    $cloned_plugin_rows = $DB->get_num_rows($get_cloned_plugins);

    if($cloned_plugin_rows > 0)
    {
      $content = '';
      while($cloned_plugin_arr = $DB->fetch_array($get_cloned_plugins,null,MYSQL_ASSOC))
      {
		$pid = (int)$cloned_plugin_arr['pluginid'];
        if($IsAdmin || (!empty($userinfo['pluginadminids']) && @in_array($cloned_plugin_arr['pluginid'],$userinfo['pluginadminids'])) )
        {
			//SD341: special linking for Articles-based plugin(s)
          if(isset($cloned_plugin_arr['base_plugin']) && ($cloned_plugin_arr['base_plugin'] == 'Articles'))
          {
            $plugin_settings_link = 'articles.php'.($pid>2?'?pluginid='.$pid:'');
          }
          else
          {
            $plugin_settings_link = 'view_plugin.php?pluginid=' . $pid;
          }
          $path = $cloned_plugin_arr['pluginpath'];
          $path = substr($path,0,strpos($path,'/'));
          $content .= '
          <tr>
            <td>
              <a href="'.$plugin_settings_link.'"'. (empty($cloned_plugin_arr['pcount'])?' class="red"':'').'>
			  	<i class="ace-icon fa fa-edit"></i>&nbsp;'.$cloned_plugin_arr['name'].'
				</a>
            </td>
            <td  class="center">'.
              (empty($cloned_plugin_arr['pcount'])?'-':$cloned_plugin_arr['pcount']).'
            </td>
            <td  class="align-center">' . $cloned_plugin_arr['version'] . '</td>
            <td>
              <input type="hidden" name="plugin_id_arr[]" value="'.$pid.'" />
              <input type="text" class="form-control" name="plugin_display_name_arr[]" value="' .
              htmlspecialchars($cloned_plugin_arr['displayname']) .
              '" /><span class="helper-text">
              ' . AdminPhrase('folder') . ': '.htmlspecialchars($path).'</span>
            </td>
            ';
          if($IsAdmin)
          {
            $content .=  '
            <td  class="align-center" width="75"><a class="deletelink"  href="plugins.php?action=delete_plugin&amp;plugin_id='.
              $pid.PrintSecureUrlToken().
              '"><i class="ace-icon fa fa-trash-o bigger-120 red"></i></a></td>
            <td  class="align-center" width="75"><a class="permissionslink"'.
              ' href="plugins.php?action=display_plugin_permissions&amp;pluginid='.
              $pid.PrintSecureUrlToken().
              '"><i class="ace-icon fa fa-key bigger-120 orange"></i></a></td>';
          }
          $content .=  '
          </tr>';
        }
      } //while

      if(strlen($content))
      {
        $alltabs .= '
        <div class="tab-pane '.iif($tab == 'cloned', "in active").' " id="clone">
			<table class="table table-striped">
				<thead>
					<tr>
						<th>'.AdminPhrase('plugins_plugin_name').'</th>
						<th class="center">
							<a title="'.addslashes(AdminPhrase('plugins_used_hint')).'" href="#" onClick="javascript:return false;">'.AdminPhrase('plugins_used').'</a>
						</th>	
						<th  class="center">'.AdminPhrase('plugins_version').'</th>
						<th>'.AdminPhrase('plugins_website_display_name').'</th>';
						
        if($IsAdmin)
        {
          $alltabs .= '
          <th  width="75" class="center">' . AdminPhrase('plugins_delete') . '</th>
          <th  width="75" class="center">' . AdminPhrase('plugins_permissions') . '</th>';
        }
        $alltabs .= '
        </tr></thead><tbody>' . $content.'
        </tbody></table></div>
        ';
		
      }
    }//CLONED PLUGINS
	else
	{
		$alltabs .= '<div class="tab-pane '.iif($tab == 'cloned', "in active").' " id="clone">
		
					<div class="alert alert-success">No Plugins to Display</div>
				</div>';
	}

  } //DOWNLOADED/CLONED PLUGINS

  echo '
  	
		<ul class="nav nav-tabs" role="tablist">
			<li id="tab-main" '.iif($tab == 'main', "class='active'").' >
				<a href="#main" data-toggle="tab">'.AdminPhrase('common_main_plugins').'</a>
			</li>
			<li id="tab-custom" '.iif($tab == 'custom', "class='active'").' >
				<a href="#custom" data-toggle="tab">'.AdminPhrase('common_custom_plugins').'</a>
			</li>
			<li  id="tab-addon" '.iif($tab == 'addon', "class='active'").' >
				<a href="#download" data-toggle="tab">'.AdminPhrase('common_downloaded_plugins').'</a>
			</li>
			<li  id="tab-cloned" '.iif($tab == 'cloned', "class='active'").' >
				<a href="#clone" data-toggle="tab">'.AdminPhrase('common_cloned_plugins').'</a>
			</li>
		</ul>
	<div class="tab-content">
		'.$alltabs . '
	</div>
';


 	echo '<div class="space-30"></div>';
  PrintSubmit('update_plugin_display_names',AdminPhrase('plugins_update_display_names'),
              'allplugins', 'fa-check', 'center', AdminPhrase('plugins_submit_hint'));

  echo '
  </form>';

} //DisplayPlugins


// ############################################################################
// UPDATE PLUGIN DISPLAY NAMES
// ############################################################################

function UpdatePluginDisplayNames()
{
  global $DB, $SDCache, $pageid;

  $plugin_id_arr = GetVar('plugin_id_arr', array(), 'array');
  $plugin_display_name_arr = GetVar('plugin_display_name_arr', array(), 'array');
  $tab = GetVar('tab', 'main', 'string');

  for($i = 0; $i < count($plugin_id_arr); $i++)
  {
    $plugin_display_name_arr[$i] = unhtmlspecialchars($plugin_display_name_arr[$i]);

    if(substr($plugin_id_arr[$i], 0, 1) == 'c')
    {
      $custompluginid = (int)substr($plugin_id_arr[$i], 1);
      $DB->query("UPDATE {customplugins} SET displayname = '" . $DB->escape_string($plugin_display_name_arr[$i]) .
                 "' WHERE custompluginid = %d", $custompluginid);
    }
    else
    {
      $DB->query("UPDATE {plugins} SET displayname = '" . $DB->escape_string($plugin_display_name_arr[$i]) .
                 "' WHERE pluginid = %d", (int)$plugin_id_arr[$i]);
    }

    // SD313x: Clear page cache files of all categories this plugin is placed in
    // "sd_deletecachefile" will itself check if cache folder exists
    if(isset($SDCache))
    {
      //SD370: also check mobile pagesort
      foreach(array('','_mobile') as $suffix)
      {
        if($suffix && !SD_MOBILE_FEATURES) break;
        $tbl = PRGM_TABLE_PREFIX.'pagesort'.$suffix;
        if($getpagesort = $DB->query("SELECT DISTINCT categoryid FROM $tbl WHERE pluginid = '%s'",(string)$plugin_id_arr[$i]))
        {
          while($catid = $DB->fetch_array($getpagesort,null,MYSQL_ASSOC))
          {
            if(!empty($catid['categoryid']))
            {
              $SDCache->delete_cacheid(($suffix?MOBILE_CACHE:'').CACHE_PAGE_PREFIX.$catid['categoryid']);
            }
          }
        }
      }
    }
  }

  RedirectPage('plugins.php?tab='.$tab.($pageid>1?'&page='.$pageid:''),
               AdminPhrase('plugins_plugin_display_names_updated'));

} //UpdatePluginDisplayNames


// ############################################################################
// DISPLAY CUSTOM PLUGIN FORM
// ############################################################################

function DisplayCustomPluginForm($errors=null)
{
  global $DB, $userinfo;

  $admin_sub_menu_arr = array();
  $custompluginid = GetVar('custompluginid', 0, 'int');

  if($custompluginid)
  {
    if($custom_plugin_arr = $DB->query_first('SELECT * FROM {customplugins} WHERE custompluginid = %d',$custompluginid))
    {
      $pname = $custom_plugin_arr['name'];
      $admin_sub_menu_arr['&raquo; '.$pname] = 'plugins.php?action=display_custom_plugin_form&amp;custompluginid='.$custompluginid.'&amp;load_wysiwyg=true';
      if($userinfo['adminaccess'])
      {
        $perm = '<a class="cpermissionslink" href="plugins.php?action=display_plugin_permissions&amp;cbox=1&amp;pluginid=c'.
                $custompluginid.'"><span class="sprite sprite-key"></span></a>';
        $admin_sub_menu_arr[$perm] = '';

        sd_header_add(array(
		'js'	=> array('assets/js/jquery.hotkeys.min.js', 
						'assets/js/bootstrap-wysiwyg.min.js'
						),
        'other' => array('
        <script type="text/javascript">
        if (typeof(jQuery) !== "undefined") {
          jQuery(document).ready(function() {
            if(typeof(jQuery.fn.ceebox) !== "undefined") {
              jQuery("a.cpermissionslink").each(function(event) {
                jQuery(this).find("img").attr("alt", "'.htmlspecialchars(AdminPhrase('plugins_permissions'),ENT_COMPAT).'");
                jQuery(this).attr("class", "cbox").attr("rel", "iframe modal:false height:420");
                jQuery(this).attr("title","'.htmlspecialchars(strip_tags(AdminPhrase('plugins_usergroup_permissions_for').' '.$pname),ENT_COMPAT).'");
              });
              '.
              GetCeeboxDefaultJS(false). //SD322 - use CeeBox, not ShadowBox
              '
            }
          });
        }
        </script>
        ')));
      }
    }
  }
  else
  {
	
  }

  DisplayAdminHeader(array('Plugins',($custompluginid ? 'plugins_edit' : 'plugins_create') . ' ' . 'plugins_custom_plugin'));

  //SD342: improved permissions check
  if(empty($userinfo['adminaccess']) &&
     !empty($custompluginid) && empty($userinfo['maintain_customplugins']) &&
     (empty($userinfo['custompluginadminids']) || !@in_array($custompluginid,$userinfo['custompluginadminids'])) )
  {
    $errors = 'No access';
  }

  if(!empty($errors))
  {
    DisplayMessage($errors, true);
    //return false;
  }

  global $pageid;
  echo '
  <form action="plugins.php" method="post" id="custompluginedit" class="form-horizontal" role="form">
  <input type="hidden" name="page" value="'.(int)$pageid.'" />
  '.PrintSecureToken();

  if($custompluginid)
  {
    echo '
    <input type="hidden" name="action" value="update_custom_plugin" />
    <input type="hidden" name="custompluginid" value="' . $custom_plugin_arr['custompluginid'] . '" />';
  }
  else
  {
    echo '
    <input type="hidden" name="action" value="insert_custom_plugin" />';

    $custom_plugin_arr = array(
      'name'        => GetVar('name', '', 'string', true, false),
      'displayname' => GetVar('displayname', '', 'string', true, false),
      'plugin'      => GetVar('plugin', '', 'html', true, false),
      'includefile' => GetVar('includefile', '', 'string', true, false),
      'ignore_excerpt_mode' => GetVar('ignore_excerpt_mode', 0, 'bool', true, false) //SD342
    );
  }

 
  //StartTable( ($custompluginid ? AdminPhrase('plugins_edit') : AdminPhrase('plugins_create')) . ' ' . AdminPhrase('plugins_custom_plugin'), array('table', 'table-bordered', 'table-striped') );
  
  echo '
  <div class="form-group">
  	<label class="control-label col-sm-2" for="pluginname">' . AdminPhrase('plugins_custom_plugin_name') . '</label>
	<div class="col-sm-9">
		<input class="form-control" type="text" name="name" id="pluginname" value="' . $custom_plugin_arr['name'] . '" />
		<span class="helper-text">' . AdminPhrase('plugins_enter_custom_plugin_name') . '</span>
	</div>
</div>

  <div class="form-group">
  	<label class="control-label col-sm-2" for="displayname">' . AdminPhrase('plugins_custom_plugin_display_name') . '</label>
	<div class="col-sm-9">
    	<input class="form-control" type="text" name="displayname" value="'. htmlspecialchars($custom_plugin_arr['displayname']) . '" />
		<span class="helper-text">' . AdminPhrase('plugins_enter_custom_plugin_display_name') . '</span>
	</div>
</div>

  <div class="form-group">
      <label class="control-label col-sm-2" for="includefile">' . AdminPhrase('plugins_custom_plugin_include_file') . '</label>
    <div class="col-sm-9">
    	<input class="form-control" type="text" name="includefile" value="' . $custom_plugin_arr['includefile'] . '" />
		<span class="helper-text">' . AdminPhrase('plugins_custom_plugin_include_file_description') . '</span>
    </div>
</div>

  <div class="form-group">
    <label class="control-label col-sm-2" for="ignore_exerpt_mode">' . AdminPhrase('plugins_ignore_excerpt_mode') /* SD342 */ . ' <span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="' . AdminPhrase('plugins_ignore_excerpt_mode_descr') . '" title="Help">?</span></label>
	
    <div class="col-sm-3">
    	<input type="radio" class="ace" name="ignore_excerpt_mode" value="1"' .(empty($custom_plugin_arr['ignore_excerpt_mode'])?'':' checked="checked"').' />
		<span class="lbl"> ' . AdminPhrase('common_yes') . '</span>&nbsp; 
		<input type="radio" class="ace" name="ignore_excerpt_mode" value="0"' .(empty($custom_plugin_arr['ignore_excerpt_mode'])?' checked="checked"':'').' />
		<span class="lbl"> ' . AdminPhrase('common_no') . '</span>
		
	</div>
</div>';
  
  //StartTable(AdminPhrase('plugins_custom_plugin_html'), array('table', 'table-bordered'));
  
  echo '
	<h3 class="header blue lighter">' . AdminPhrase('plugins_custom_plugin_html') . '</h3>
  	<label class="control-label col-sm-12 align-left" for="custom-html"></label>
	<div class="col-sm-12">';

	PrintWysiwygElement('plugincode', htmlspecialchars($custom_plugin_arr['plugin']), 18, 90);
	
	echo'
  	<!-- <div class="wysiwyg-editor" id="custom-html">' . htmlspecialchars($custom_plugin_arr['plugin']) . '</div> -->
	</div>

    ';
  //PrintWysiwygElement('plugin', htmlspecialchars($custom_plugin_arr['plugin']), 18, 90);
 

  echo '<div class="space-30"></div>
  <br /><div class="center"><button id="submit" class="btn btn-primary" type="submit"><i class="ace-icon fa fa-check"></i>&nbsp;';
  if(empty($custompluginid))
  {
    echo AdminPhrase('plugins_create') . ' ' . AdminPhrase('plugins_custom_plugin') . '</button></br>';
  }
  else
  {
    echo AdminPhrase('plugins_update') . ' ' . AdminPhrase('plugins_custom_plugin') . '</button></br>
    <input type="checkbox" class="ace" name="CopyPlugin" value="1" /><span class="lbl"> '.AdminPhrase('create_copy') . '</span>';
  }
  echo '
  </div>
  </form>';

  //SD343: prevent submit on empty plugin name
  
 

} //DisplayCustomPluginForm


// ############################################################################
// DISPLAY PLUGIN PERMISSIONS
// ############################################################################

function DisplayPluginPermissions()
{
  global $DB, $pluginbitfield, $plugin_names;

  $pluginid = GetVar('pluginid', 0, 'string');

  // Check for valid plugin ID and fetch plugin row:
  $plugin = false;
  $full_id = $pluginid;
  $is_custom = (substr($pluginid,0,1)=='c');
  if($is_custom)
  {
    $pluginid = substr($pluginid,1);
    if(($pluginid > 0) && ($pluginid < 999999))
    {
      $DB->result_type = MYSQL_ASSOC;
      $plugin = $DB->query_first('SELECT * FROM {customplugins} WHERE custompluginid = %d',$pluginid);
    }
  }
  else
  {
    if(($pluginid > 1) && ($pluginid < 999999))
    {
      $DB->result_type = MYSQL_ASSOC;
      if($plugin = $DB->query_first('SELECT * FROM {plugins} WHERE pluginid = %d',$pluginid))
      {
        if(isset($plugin_names[$plugin['name']]))
        {
          $plugin['name'] = $plugin_names[$plugin['name']];
        }
      }
    }
  }

  // Fetch all usergroups' plugin-related permissions:
  $groups = array();
  $perm = array();
  $getusergroups = $DB->query('SELECT * FROM {usergroups} ORDER BY usergroupid');
  while($ug = $DB->fetch_array($getusergroups))
  {
    $id = $ug['usergroupid'];
    $groups[] = $id;
    $perm[$id]['id'] = $id;
    $perm[$id]['name'] = $ug['name'];
    if($is_custom)
    {
      if(!empty($ug['custompluginviewids']) && strlen($ug['custompluginviewids']))
      {
        $tmp = @explode(',', $ug['custompluginviewids']);
        $perm[$id]['cv'] = in_array($pluginid,$tmp);
      }
      if(!empty($ug['custompluginadminids']) && strlen($ug['custompluginadminids']))
      {
        $tmp = @explode(',', $ug['custompluginadminids']);
        $perm[$id]['ca'] = in_array($pluginid,$tmp);
      }
    }
    else
    {
      if(!empty($ug['pluginviewids']) && strlen($ug['pluginviewids']))
      {
        $tmp = @explode(',', $ug['pluginviewids']);
        $perm[$id]['v'] = in_array($pluginid,$tmp);
      }
      if(!empty($ug['pluginsubmitids']) && strlen($ug['pluginsubmitids']))
      {
        $tmp = @explode(',', $ug['pluginsubmitids']);
        $perm[$id]['s'] = in_array($pluginid,$tmp);
      }
      if(!empty($ug['plugindownloadids']) && strlen($ug['plugindownloadids']))
      {
        $tmp = @explode(',', $ug['plugindownloadids']);
        $perm[$id]['d'] = in_array($pluginid,$tmp);
      }
      // SD313: reintroducing comments permissions
      if(!empty($ug['plugincommentids']) && strlen($ug['plugincommentids']))
      {
        $tmp = @explode(',', $ug['plugincommentids']);
        $perm[$id]['c'] = in_array($pluginid,$tmp);
      }
      if(!empty($ug['pluginadminids']) && strlen($ug['pluginadminids']))
      {
        $tmp = @explode(',', $ug['pluginadminids']);
        $perm[$id]['a'] = in_array($pluginid,$tmp);
      }
      //SD360: "Allow Moderation"
      if(!empty($ug['pluginmoderateids']) && strlen($ug['pluginmoderateids']))
      {
        $tmp = @explode(',', $ug['pluginmoderateids']);
        $perm[$id]['m'] = in_array($pluginid,$tmp);
      }
    }

  } //while

  // IF it is a standalone form, assume the action
  $formgroup = 'p_'.$full_id;
  global $pageid;
  echo '
  <form method="post" action="plugins.php" name="'.$formgroup.'">
  '.PrintSecureToken().'
  <input type="hidden" name="action" value="update_plugin_permissions" />
  <input type="hidden" name="pluginid" value="'.$full_id.'" />
  <input type="hidden" name="cbox" value="'.(IS_CBOX ?'1':'0').'" />
  <input type="hidden" name="page" value="'.(isset($pageid)?$pageid:1).'" />
  ';

  echo '<div class="table-header">'. AdminPhrase('plugins_usergroup_permissions_for') . ' "' . $plugin['name'].'"' . '</div>';

  echo '
  <table class="table table-striped table-bordered" summary="layout">
  	<thead>
    <tr>
      <th  width="15%">' . AdminPhrase('common_usergroup') . '</td>
      <th  width="15%"><input type="checkbox" class="ace" name="cv" value="0" checkall="view_' .   $formgroup . '" onclick="javascript: return select_deselectAll (\'' . $formgroup . '\', this, \'view_' . $formgroup . '\');" /><span class="lbl"> ' . AdminPhrase('common_allow_view') . '</span></td>';
  if(!$is_custom && ($plugin['settings'] & $pluginbitfield['cansubmit'])) echo '
      <th  width="15%"><input type="checkbox" class="ace" name="cs" value="0" checkall="submit_' . $formgroup . '" onclick="javascript: return select_deselectAll (\'' . $formgroup . '\', this, \'submit_' . $formgroup . '\');" /> <span class="lbl"> ' . AdminPhrase('common_allow_submit') . '</span></td>';
  if(!$is_custom && ($plugin['settings'] & $pluginbitfield['candownload'])) echo '
      <th  width="15%"><input type="checkbox" class="ace" name="cd" value="0" checkall="download_'.$formgroup . '" onclick="javascript: return select_deselectAll (\'' . $formgroup . '\', this, \'download_' . $formgroup . '\');" /> <span class="lbl"> ' . AdminPhrase('common_allow_download') . '</span></td>';
  if(!$is_custom && ($plugin['settings'] & $pluginbitfield['cancomment'])) echo '
      <th  width="15%"><input type="checkbox" class="ace" name="cc" value="0" checkall="comment_'. $formgroup . '" onclick="javascript: return select_deselectAll (\'' . $formgroup . '\', this, \'comment_' . $formgroup . '\');" /><span class="lbl"> ' . AdminPhrase('common_allow_comment') . '</span></td>';
  if(!$is_custom && ($plugin['settings'] & $pluginbitfield['canmoderate'])) echo '
      <th  width="15%"><input type="checkbox" class="ace" name="cm" value="0" checkall="moderate_'. $formgroup. '" onclick="javascript: return select_deselectAll (\'' . $formgroup . '\', this, \'moderate_' . $formgroup . '\');" /><span class="lbl"> ' . AdminPhrase('common_allow_moderate') . '</span></td>';
  if(!$is_custom && ($plugin['settings'] & $pluginbitfield['canadmin'])) echo '
      <th  width="15%"><input type="checkbox" class="ace" name="ca" value="0" checkall="admin_' .  $formgroup . '" onclick="javascript: return select_deselectAll (\''.$formgroup.'\', this, \'admin_' . $formgroup . '\');" /><span class="lbl"> ' . AdminPhrase('common_allow_administer'). '</span></td>';
  echo '
    </tr></thead><tbody>';

  // Different output for custom and regular plugins (main/cloned/downloaded):
  if($is_custom)
  {
    for($i = 0; $i < count($groups); $i++)
    {
      $id = $groups[$i];
      echo '
      <tr>
        <td>' . $perm[$id]['name'] . '</td>
        <td>' .
        (($plugin['settings'] & $pluginbitfield['canview'])?
          '<input type="checkbox" class="ace" name="'.$formgroup.'['.$id.'][cv]" value="1" '.(!empty($perm[$id]['cv']) ? 'checked="checked"' : '').' checkme="view_'.$formgroup.'" />':
          '<input type="checkbox" class="ace" disabled="disabled" />') . '<span class="lbl"></span></td>
        ';
      if(!$is_custom && ($plugin['settings'] & $pluginbitfield['canadmin'])) echo '
        <td>
          <input type="checkbox" class="ace" name="'.$formgroup.'['.$id.'][ca]" value="1" '.(!empty($perm[$id]['ca']) ? 'checked="checked"' : '').' checkme="admin_'.$formgroup.'" /><span class="lbl"></span></td>';
      echo '
      </tr>';
    }
  }
  else
  {
    for($i = 0; $i < count($groups); $i++)
    {
      $id = $groups[$i];
      echo '
      <tr>
        <td>' . $perm[$id]['name'] . '</td>';
      if($plugin['settings'] & $pluginbitfield['canview']) echo '
        <td><input type="checkbox" class="ace" name="'.$formgroup.'['.$id.'][v]" value="1" ' . (!empty($perm[$id]['v']) ? 'checked="checked"' : '') . ' checkme="view_' . $formgroup . '"  /><span class="lbl"></span></td>';
      if($plugin['settings'] & $pluginbitfield['cansubmit']) echo '
        <td><input type="checkbox" class="ace" name="'.$formgroup.'['.$id.'][s]" value="1" ' . (!empty($perm[$id]['s']) ? 'checked="checked"' : '') . ' checkme="submit_' . $formgroup . '" /><span class="lbl"></span></td>';
      if($plugin['settings'] & $pluginbitfield['candownload']) echo '
        <td><input type="checkbox" class="ace" name="'.$formgroup.'['.$id.'][d]" value="1" ' . (!empty($perm[$id]['d']) ? 'checked="checked"' : '') . ' checkme="download_' . $formgroup . '" /><span class="lbl"></span></td>';
      if($plugin['settings'] & $pluginbitfield['cancomment']) echo '
        <td><input type="checkbox" class="ace" name="'.$formgroup.'['.$id.'][c]" value="1" ' . (!empty($perm[$id]['c']) ? 'checked="checked"' : '') . ' checkme="comment_' . $formgroup . '" /><span class="lbl"></span></td>';
      if($plugin['settings'] & $pluginbitfield['canmoderate']) echo '
        <td><input type="checkbox" class="ace" name="'.$formgroup.'['.$id.'][m]" value="1" ' . (!empty($perm[$id]['m']) ? 'checked="checked"' : '') . ' checkme="moderate_' . $formgroup . '" /><span class="lbl"></span></td>';
      if($plugin['settings'] & $pluginbitfield['canadmin']) echo '
        <td><input type="checkbox" class="ace" name="'.$formgroup.'['.$id.'][a]" value="1" ' . (!empty($perm[$id]['a']) ? 'checked="checked"' : '') . ' checkme="admin_' . $formgroup . '" /><span class="lbl"></span></td>';
      echo '
      </tr>';
    }
  }

  echo '</tbody></table>';

  echo '<div class="align-center"><button type="submit" class="btn btn-primary" value="" /><i class="ace-icon fa fa-check bigger-120"></i>' . AdminPhrase('plugins_update_permissions') . '</button></div>
        </form>';

} //DisplayPluginPermissions


// ############################################################################
// UPDATE PLUGIN PERMISSIONS
// ############################################################################

    function __UPP_GetPerm($pluginid,$ug,$key,$postkey,$prefix='')
    {
      // Don't use array-conversion here as that would require more
      // operations than with a string trick:
      if(!isset($ug[$key]) || !strlen($ug[$key]))
      {
        $tmp = '';
      }
      else
      {
        $tmp = ','.$ug[$key].',';
        $tmp = str_replace(','.$pluginid.',',',',$tmp);
      }
      $posted = !empty($_POST['p_'.$prefix.$pluginid][$ug['usergroupid']][$postkey]);
      if($posted)
      {
        $tmp .= $pluginid;
      }
      $tmp = trim($tmp,',');
      // Only use array to sort the list now:
      if(strlen($tmp))
      {
        $tmp = @explode(',', $tmp);
        @natcasesort($tmp);
        $tmp = @implode(',', $tmp);
      }
      return $tmp;
    }

function UpdatePluginPermissions()
{
  global $DB;

  $pluginid = GetVar('pluginid', 0, 'string'); // custom plugin = c2 (string)

  $plugin = false;
  $full_id = $pluginid;
  $is_custom = (substr($pluginid,0,1)=='c');
  if($is_custom)
  {
    $pluginid = substr($pluginid,1);
    $plugin = $DB->query_first('SELECT * FROM {customplugins} WHERE custompluginid = %d',$pluginid);
  }
  else
  {
    $plugin = $DB->query_first('SELECT * FROM {plugins} WHERE pluginid = %d',$pluginid);
  }

  if(!$plugin)
  {
    DisplayMessage(AdminPhrase('plugins_plugin_not_found'), true);
    return false;
  }

  // Pre-fetch all usergroups:
  $groupids = $ug = array();
  if($getusergroups = $DB->query('SELECT * FROM {usergroups} ORDER BY usergroupid'))
  {
    while($ug = $DB->fetch_array($getusergroups))
    {
      $id = $ug['usergroupid'];
      $groupids[]  = $id;
      $groups[$id] = $ug;
    }
  }
  else
  {
    return false;
  }

  // Process each group and update it's plugin permissions accordingly:
  for($i = 0, $gc = count($groupids); $i < $gc; $i++)
  {
    $id = $groupids[$i];
    $ug = $groups[$id];
    if($is_custom)
    {
      $ug_v = __UPP_GetPerm($pluginid,$ug,'custompluginviewids','cv','c');
      $ug_a = __UPP_GetPerm($pluginid,$ug,'custompluginadminids','ca','c');
      $DB->query("UPDATE {usergroups} SET custompluginviewids = '%s', custompluginadminids = '%s'
        WHERE usergroupid = %d", $ug_v, $ug_a, $id);
    }
    else
    {
      $ug_v = __UPP_GetPerm($pluginid,$ug,'pluginviewids','v');
      $ug_s = __UPP_GetPerm($pluginid,$ug,'pluginsubmitids','s');
      $ug_d = __UPP_GetPerm($pluginid,$ug,'plugindownloadids','d');
      $ug_c = __UPP_GetPerm($pluginid,$ug,'plugincommentids','c');
      $ug_m = __UPP_GetPerm($pluginid,$ug,'pluginmoderateids','m');
      $ug_a = __UPP_GetPerm($pluginid,$ug,'pluginadminids','a');
      $DB->query("UPDATE {usergroups}
        SET pluginviewids = '%s',
        pluginsubmitids = '%s',
        plugindownloadids = '%s',
        plugincommentids = '%s',
        pluginmoderateids = '%s',
        pluginadminids = '%s'
        WHERE usergroupid = %d", $ug_v, $ug_s, $ug_d, $ug_c, $ug_m, $ug_a, $id);
    }
  } //for

  //SD322: either close CeeBox (if JS is enabled) or redirect page
  if(IS_CBOX)
  {
    echo '<div class="alert alert-success">'.
      sd_CloseCeebox(2, AdminPhrase('plugins_permissions_updated').'
      <br /><br /><input class="submit btn btn-info" type="button" onclick="parent.jQuery.fn.ceebox.closebox(\'fast\');" value="'.AdminPhrase('close_window').'" />');
      echo '
      </div>
      ';
  }
  else
  {
    global $pageid;
    RedirectPage('plugins.php'.($pageid>1?'?page='.$pageid:''),
                 AdminPhrase('plugins_permissions_updated'));
  }

} //UpdatePluginPermissions


// ############################################################################
// DISPLAY NEW PLUGINS
// ############################################################################
// a recursive function that searches the plugins folder for new plugins

function DisplayNewPlugins($dirname)
{
  global $DB;

  static $availableplugins;

  // initialize some variables
  $installtype = '';
  $currentversion = '';
  


  if($d = @dir($dirname))
  {
    while($entry = $d->read())
    {
      if((substr($entry,0,1) != '.') && !preg_match('#\.htm(l)*$#', $entry))
      {
        if(is_dir($dirname.'/'.$entry))
        {
          DisplayNewPlugins($dirname.'/'.$entry);
        }
        else if(is_file($dirname.'/'.$entry) && ($entry == PLUGINS_INSTALL_FILE) AND ($dirname != ROOT_PATH.'plugins') AND (substr_count($dirname, '/') == 2)) // its a file, and it's in the correct folder
        {
          $installpath = $dirname . '/'. PLUGINS_INSTALL_FILE;

          // SD313: skip if install file does not exist or returns false
          if(!is_file($installpath) || !include($installpath))
          {
            continue;
          }

          // is this a beta version?
          $betaversion = !empty($betaversion);

          // check to see if plugin has already been installed

          // do not install or upgrade cloned plugins
          if(isset($uniqueid) && ($uniqueid >= 2000) && ($uniqueid < 3000))
          {
            $installtype = '';
          }
          else if(!isset($pluginname) || !strlen($pluginname))
          {
            $installtype = '';
          }
          // SD 313: if pluginid is set, use it for select before searching by name!
          else if(!empty($pluginid) && ($plugin = $DB->query_first('SELECT name, version FROM {plugins} WHERE pluginid = %d',$pluginid)))
          {
            // plugin is an update
            if($plugin['version'] < $version)
            {
              $installtype    = 'upgrade';
              $currentversion = $plugin['version'];
            }
          }
          else if($plugin = $DB->query_first("SELECT name, version FROM {plugins} WHERE name = '%s'",
                                             $DB->escape_string($pluginname)))
          {
            // plugin is an update
            if($plugin['version'] < $version)
            {
              $installtype    = 'upgrade';
              $currentversion = $plugin['version'];
            }
          }
          else
          {
            $installtype = 'install';
          }

          if($installtype == 'install' || $installtype == 'upgrade')
          {
            $availableplugins++;
            global $pageid;
			$authorname = explode('Plugin folder:',$authorname);
			
			
           	echo '<div class="col-sm-3">
					<div class="panel ' .($installtype == 'install' ? "panel-default" : "panel-warning").'">
					<div class="panel-heading bolder">' . $pluginname . ' <span class="badge pull-right '.($installtype == 'install' ? "badge-info" : "badge-warning").'">v'.$version.'</span></div>
					<div class="panel-body">
					<form  method="post" action="plugins.php">
                    '.PrintSecureToken().'
                    <input type="hidden" name="action" value="' . $installtype . 'plugin" />
                    <input type="hidden" name="installtype" value="'.$installtype.'" />
                    <input type="hidden" name="installpath" value="'.$installpath.'" />
                    <input type="hidden" name="page" value="'.(isset($pageid)?$pageid:1).'" />
					<div class="space-4"></div>
					
					<ul class="list-unstyled spaced">
						
                    	<li>
							<i class="ace-icon fa fa-arrow-right  blue"></i> '.AdminPhrase('plugins_author_name').': <strong>'.$authorname[0].'</strong>
						</li>
					</ul>
					<div class="space-10"></div>
					<div class="center">';
					
					if($installtype == 'install')
					{
					  echo '<button type="submit" class="btn btn-info btn-sm btn-block no-border" value=""><i class="ace-icon fa fa-cloud-download bigger-120"></i>
					  			' . AdminPhrase('plugins_install') . '</button>';
					}
					else
					{
					  echo '<input type="hidden" name="currentversion" value="'.$currentversion.'" />
							<button type="submit" class="btn btn-warning btn-sm btn-block no-border"  value=""><i class="ace-icon fa fa-cloud-download bigger-120"></i>
							' . AdminPhrase('plugins_upgrade') . '</button>';
					}

            echo '  </form></div><div class="space-6"></div>					
					</div>
				</div></div>';
				
			
		   
		   /* echo '
                <tr>
                  <td width="80%">
				  	<h2 class="blue lighter">' . $pluginname . '</h2>
               		<h6><span class="label label-lg label-info arrowed-in-right arrowed-in">'.AdminPhrase('plugins_version') . ': ' . $version.(empty($currentversion)?'':' (installed: '.$currentversion.')').'</span></h6>
                    <h6>'.AdminPhrase('plugins_author_name').': '.$authorname.'</h6>
                  </td>
				  <td class="center">
				  	<form  method="post" action="plugins.php">
                    '.PrintSecureToken().'
                    <input type="hidden" name="action" value="' . $installtype . 'plugin" />
                    <input type="hidden" name="installtype" value="'.$installtype.'" />
                    <input type="hidden" name="installpath" value="'.$installpath.'" />
                    <input type="hidden" name="page" value="'.(isset($pageid)?$pageid:1).'" />';
					
					if($installtype == 'install')
					{
					  echo '<button type="submit" class="btn btn-primary btn-app radius-6" value="" /><i class="ace-icon fa fa-cloud-download bigger-230"></i>
					  			' . AdminPhrase('plugins_install') . '</button>';
					}
					else
					{
					  echo '<input type="hidden" name="currentversion" value="'.$currentversion.'" />
							<input type="submit" value="' . AdminPhrase('plugins_upgrade') . ' ' . $pluginname . '" />';
					}

            echo '  </form>
                    </td>
                  </tr>
				  ';*/

            // reset vars
            unset($installtype);
	     }
        }
        else
        if(($entry == PLUGINS_INSTALL_FILE) && ($dirname != '../plugins') && (substr_count($dirname, '/') > 2))
        {
          // the install folder was uploaded to the wrong spot,
          // do not display
        }
      }
    } //while
  }

  return $availableplugins;

} //DisplayNewPlugins


// ############################################################################
// DISPLAY INSTALL UPGRADE PLUGINS
// ############################################################################

function DisplayInstallUpgradePlugins()
{
	
 	echo '<h3 class="header blue lighter">' . AdminPhrase('plugins_install_upgrade_plugins') . '</h3>';
 
  //echo '<div class="table-header">' . AdminPhrase('plugins_install_upgrade_plugins') . '</div>';
  //echo '<table class="table table-striped">';

  if(!DisplayNewPlugins(ROOT_PATH.'plugins'))
  {
	  
	  echo '<div class="alert alert-info">
	  			<strong> ' . AdminPhrase('plugins_all_installed') . '</strong><br />
				 ' . AdminPhrase('plugins_all_up_to_date') . '
			</div>';
				
   /* echo '<tr>
            <td>' . AdminPhrase('plugins_all_installed') . '</td>
          </tr>
          <tr>
            <td  align="top">
              ' . AdminPhrase('plugins_all_up_to_date') . '
          </td>
        </tr>';
		*/
  }

 // echo '</table>';
 

} //DisplayInstallUpgradePlugins


// ############################################################################
// DELETE CUSTOM PLUGINS
// ############################################################################

function DeleteCustomPlugins()
{
  //SD370: delete one or more custom plugins; ONLY called by ajax!
  // uses new, specific security check on entries
  global $DB, $userinfo, $SDCache;

  $err_msg = AdminPhrase('message_no_access_uninstall');
  $ids = GetVar('ids', false, 'string', true, false);
  if(!Is_Ajax_Request() || !$ids || !is_string($ids) || !strlen($ids))
  {
	die($err_msg);
  }

  $ids = @explode('&amp;', $ids);
  if(!is_array($ids) || !count($ids)) die($err_msg);

  foreach($ids as $entry)
  {
    // do several sanity checks on passed data
    // example $entry: delcustom_102=8b9b7d552982b2f4bbfc8ec5e0c10c10
    list($key, $check) = @explode('=', $entry);
    $error = empty($key) || empty($check) ||
             (substr($key,0,10)!='delcustom_') || !is_numeric(substr($key,10));
    if(!$error)
    {
      $key = substr($key,10);
      $error = (!$custompluginid = Is_Valid_Number($key,0,1,99999999));
    }
    if($error ||
       (md5($custompluginid.PRGM_HASH.md5($custompluginid)) != $check) ||
       !$DB->query_first('SELECT 1 FROM {customplugins} WHERE custompluginid = %d', $custompluginid))
    {
      die($err_msg);
    }

    // Check that the user has permission to do this
    if(empty($userinfo['adminaccess']))
      if(empty($userinfo['maintain_customplugins']) ||
         empty($userinfo['custompluginadminids']) ||
         !@in_array($custompluginid, $userinfo['custompluginadminids']))
      {
        die($err_msg);
      }

    // clear cache files of all categories that contain this custom plugin
    $pagesortid = 'c' . $custompluginid;
    if(isset($SDCache))
    {
      //SD370: also check mobile pagesort
      foreach(array('','_mobile') as $suffix)
      {
        if($suffix && !SD_MOBILE_FEATURES) break;
        $tbl = PRGM_TABLE_PREFIX.'pagesort'.$suffix;
        if($getpagesort = $DB->query("SELECT categoryid FROM $tbl WHERE pluginid = '%s'", $pagesortid))
        {
          while($catid = $DB->fetch_array($getpagesort,null,MYSQL_ASSOC))
          {
            if(!empty($catid['categoryid']))
            {
              $SDCache->delete_cacheid(($suffix?MOBILE_CACHE:'').CACHE_PAGE_PREFIX.$catid['categoryid']);
            }
          }
        }
      }
    }

    $DB->query('DELETE FROM {customplugins} WHERE custompluginid = %d', $custompluginid);

    $DB->query("UPDATE {pagesort} SET pluginid = '1' WHERE pluginid = '%s'", $pagesortid);
    if(SD_MOBILE_FEATURES) //SD370
    $DB->query("UPDATE {pagesort_mobile} SET pluginid = '1' WHERE pluginid = '%s'", $pagesortid);

    // remove this custompluginid from the usergroup rows
    $usergroups = $DB->query('SELECT usergroupid, custompluginviewids, custompluginadminids FROM {usergroups}');

    while($usergroup = $DB->fetch_array($usergroups))
    {
      // get the custom plugin view and admin ids
      $custompluginviewids  = !empty($usergroup['custompluginviewids'])  ? @explode(',', $usergroup['custompluginviewids'])  : array();
      $custompluginadminids = !empty($usergroup['custompluginadminids']) ? @explode(',', $usergroup['custompluginadminids']) : array();

      // search and if found then remove the deleted custom pluginid from the allow view column
      if(@in_array($custompluginid, $custompluginviewids))
      {
        unset($custompluginviewids[array_search($custompluginid, $custompluginviewids)]);
      }

      // search and if found then remove the deleted custom pluginid from the allow admin column
      if(@in_array($custompluginid, $custompluginadminids))
      {
        unset($custompluginadminids[array_search($custompluginid, $custompluginadminids)]);
      }

      // implode both arrays back to a string
      $newcustompluginviewids  = @implode(',', $custompluginviewids);
      $newcustompluginadminids = @implode(',', $custompluginadminids);

      // now update the usergroup with the updated customplugin values
      $DB->query("UPDATE {usergroups}
        SET custompluginviewids  = '$newcustompluginviewids',
            custompluginadminids = '$newcustompluginadminids'
        WHERE usergroupid        = %d", $usergroup['usergroupid']);
    } //while
  } //foreach

  echo 'success';

} //DeleteCustomPlugins


// ########################## UPDATE PLUGIN VERSION ###########################
// this function is used when a plugin is upgrading from it's install.php file

function UpdatePluginVersion($pluginid, $version)
{
  global $DB;

  if(!empty($pluginid) && !empty($version))
  {
    $DB->query("UPDATE {plugins} SET version = '%s' WHERE pluginid = %d",
               $DB->escape_string($version), $pluginid);
  }

}  //UpdatePluginVersion


// ############################################################################
// INSERT CUSTOM PLUGIN
// ############################################################################

function InsertCustomPlugin()
{
  global $DB, $NoMenu, $pageid;

  $name         = GetVar('name', '', 'string', true, false);
  $displayname  = GetVar('displayname', '', 'html', true, false);
  $plugin       = GetVar('plugincode', '', 'html', true, false);
  $includefile  = GetVar('includefile', '', 'html', true, false);
  $ignore_excerpt_mode = (GetVar('ignore_excerpt_mode', 0, 'bool', true, false)?1:0);
  

  //SD322: Custom Plugins require a name!
  if(!strlen($name))
  {
    DisplayCustomPluginForm(AdminPhrase('message_plugin_requires_name'));
    return;
  }

  //SD361: sanity check for included file
  $includefile_org = $includefile;
  if(strlen($includefile))
  {
    if(!sd_check_pathchars($includefile))
    {
      $includefile = '';
      DisplayMessage('Invalid characters for include file!', true);
    }
    else
    {
      $includefile = strip_alltags($includefile);
      $includefile = SanitizeInputForSQLSearch($includefile,false,false,true);
      $includefile = trim($includefile);
      $includefile = str_replace('\\','/',$includefile);
      $includefile = str_replace('//','/',$includefile);
      if(empty($includefile) || ($includefile_org != $includefile))
      {
        $includefile = '';
        DisplayMessage('Invalid path to include file!', true);
      }
      else
      {
        $rootpath = realpath(ROOT_PATH);
        $pluginpath = realpath($rootpath.'/'.dirname($includefile));
        if(strlen($pluginpath) < strlen($rootpath))
        {
          $includefile = '';
          DisplayMessage('Invalid path to include file!', true);
        }
      }
    }
  }

  DisplayAdminHeader('Plugins', null, '', $NoMenu);
  $DB->skip_curly = true;
  $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX.'customplugins'.
             ' (name, displayname, plugin, includefile, settings, ignore_excerpt_mode)'.
             " VALUES ('$name', '". $DB->escape_string($displayname)."', '" .
             $DB->escape_string($plugin) . "', '".
             $DB->escape_string($includefile)."', 17, $ignore_excerpt_mode)");
  $DB->skip_curly = false;

  if($custompluginid = $DB->insert_id())
  {
    // install default usergroup settings
    $usergroups = $DB->query('SELECT usergroupid, adminaccess, banned, custompluginviewids, custompluginadminids FROM {usergroups} ORDER BY usergroupid');

    while($usergroup = $DB->fetch_array($usergroups))
    {
      $usergroupid = $usergroup['usergroupid'];
      $custompluginadminids = strlen($usergroup['custompluginadminids']) ? $usergroup['custompluginadminids'] : '';
      if(empty($usergroup['banned']))
      {
        $custompluginviewids  = strlen($usergroup['custompluginviewids'])  ? $usergroup['custompluginviewids']  . ',' . $custompluginid : $custompluginid;
      }

      // Administrators get admin access to new custom plugin
      if(!empty($usergroup['adminaccess']))
      {
        $custompluginadminids .= ',' . $custompluginid;
      }

      $DB->query("UPDATE {usergroups} SET custompluginviewids  = '%s', custompluginadminids = '%s' ".
                 'WHERE usergroupid = '.$usergroupid, $custompluginviewids, $custompluginadminids);
    }

    RedirectPage('plugins.php?action=display_custom_plugin_form&amp;custompluginid='.
                 $custompluginid.'&amp;load_wysiwyg=1'.($pageid>1?'&amp;page='.$pageid:''),
                 AdminPhrase('plugins_custom_plugin_created'));
  }

} //InsertCustomPlugin


// ############################################################################
// UPDATE CUSTOM PLUGIN
// ############################################################################

function UpdateCustomPlugin()
{
  global $DB, $SDCache, $load_wysiwyg;
  
  $custompluginid = Is_Valid_Number(GetVar('custompluginid',0,'whole_number'),0,1,9999999);
  //SD313: check if $custompluginid is really valid
  if(empty($custompluginid))
  {
    DisplayMessage('Custom Plugin not found.', true);
    DisplayPlugins();
    return false;
  }

  // Clear cache files of all categories this custom plugin is placed in
  // "sd_deletecachefile" will itself check if cache folder exists
  //SD370: also create mobile pagesort entries
  foreach(array('','_mobile') as $suffix)
  {
    if($suffix && !SD_MOBILE_FEATURES) break;
    $tbl = PRGM_TABLE_PREFIX.'pagesort'.$suffix;
    if($getpagesort = $DB->query('SELECT DISTINCT categoryid'.
                                 ' FROM '.$tbl.
                                 " WHERE pluginid = 'c".$custompluginid."'"))
    {
      while($catid = $DB->fetch_array($getpagesort,null,MYSQL_ASSOC))
      {
        if(!empty($catid['categoryid']))
        {
          $SDCache->delete_cacheid(($suffix?MOBILE_CACHE:'').CACHE_PAGE_PREFIX.$catid['categoryid']);
        }
      }
    }
  }

  $name        = GetVar('name', '', 'string');
  $displayname = GetVar('displayname', '', 'html');
  $plugin      = GetVar('plugincode', '', 'html');
  $includefile = trim(GetVar('includefile', '', 'html'));
  //SD361: sanity check for included file
  $includefile_org = $includefile;
  if(strlen($includefile))
  {
    if(!sd_check_pathchars($includefile))
    {
      $includefile = '';
      DisplayMessage('Invalid characters for include file!', true);
    }
    else
    {
      $includefile = strip_alltags($includefile);
      $includefile = SanitizeInputForSQLSearch($includefile,false,false,true);
      $includefile = trim($includefile);
      $includefile = str_replace('\\','/',$includefile);
      $includefile = str_replace('//','/',$includefile);
      if(empty($includefile) || ($includefile_org != $includefile))
      {
        $includefile = '';
        DisplayMessage('Invalid path to include file!', true);
      }
      else
      {
        $rootpath = realpath(ROOT_PATH);
        $pluginpath = realpath($rootpath.'/'.dirname($includefile));
        if(strlen($pluginpath) < strlen($rootpath))
        {
          $includefile = '';
          DisplayMessage('Invalid path to include file!', true);
        }
      }
    }
  }

  $CopyPlugin  = GetVar('CopyPlugin', false, 'bool');
  $ignore_excerpt_mode = GetVar('ignore_excerpt_mode', 0, 'bool')?1:0; //SD342

  // SD 313: use $DB->escape_string()
  $DB->skip_curly = true;
  $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'customplugins'.
             " SET name = '$name',".
             " displayname = '".$DB->escape_string($displayname)."',".
             " plugin = '" . $DB->escape_string($plugin) . "',".
             " includefile = '".$DB->escape_string($includefile)."',".
             " ignore_excerpt_mode = ".$ignore_excerpt_mode.
             ' WHERE custompluginid = '.$custompluginid);
  $DB->skip_curly = false;

  if($CopyPlugin)
  {
    $DB->skip_curly = true;
    $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX.'customplugins'.
               ' (name, displayname, plugin, includefile, settings, ignore_excerpt_mode)'.
               " SELECT CONCAT(name,' *'), displayname, plugin, includefile, 17, ignore_excerpt_mode".
               ' FROM '.PRGM_TABLE_PREFIX.'customplugins'.
               ' WHERE custompluginid = '.(int)$custompluginid);
    $DB->skip_curly = false;
    if($custompluginid = $DB->insert_id())
    {
      // install default usergroup settings
      $usergroups = $DB->query('SELECT usergroupid, adminaccess, banned, custompluginviewids, custompluginadminids FROM {usergroups} ORDER BY usergroupid');

      while($usergroup = $DB->fetch_array($usergroups))
      {
        $usergroupid = $usergroup['usergroupid'];
        $custompluginadminids = strlen($usergroup['custompluginadminids']) ? $usergroup['custompluginadminids'] : '';
        if(empty($usergroup['banned']))
        {
          $custompluginviewids  = strlen($usergroup['custompluginviewids']) ? $usergroup['custompluginviewids']  . ',' . $custompluginid : $custompluginid;
        }

        // Administrators get admin access to new custom plugin
        if(!empty($usergroup['adminaccess']))
        {
          $custompluginadminids .= ',' . $custompluginid;
        }

        $DB->query("UPDATE {usergroups} SET custompluginviewids  = '%s', custompluginadminids = '%s' ".
                   'WHERE usergroupid = '.$usergroupid, $custompluginviewids, $custompluginadminids);
      } //while
    }
  }

  global $pageid;
  RedirectPage('plugins.php?action=display_custom_plugin_form&amp;custompluginid=' .
               $custompluginid.($load_wysiwyg?'&amp;load_wysiwyg=1':'').
               ($pageid>1?'&amp;page='.$pageid:''),
               AdminPhrase('plugins_custom_plugin_updated'));

} //UpdateCustomPlugin


// ############################################################################
// UNINSTALL BROKEN PLUGINS
// ############################################################################

function UninstallBrokenPlugins()
{
  global $DB;

  $get_broken_plugins = $DB->query("SELECT DISTINCT pluginid FROM {plugins}
    WHERE displayname = '' AND version = '' AND pluginpath = '' AND settingspath = ''");

  while($broken_plugin_arr = $DB->fetch_array($get_broken_plugins))
  {
    UninstallPlugin($broken_plugin_arr['pluginid']);
  }

  return true;

} //UninstallBrokenPlugins


// ############################################################################
// UNINSTALL PLUGIN (NOT FOR CUSTOM PLUGINS!)
// ############################################################################

function UninstallPlugin($pluginid = null)
{
  global $DB, $core_pluginids_arr, $mainsettings, $SDCache, $userinfo;

  // SD313: need to re-check access!
  if(empty($userinfo['adminaccess']))
  {
    DisplayMessage(AdminPhrase('message_no_access_uninstall'), true);
    DisplayPlugins();
    DisplayAdminFooter();
    exit();
  }

  // Decide if functions was called externally
  $IsCalled = isset($pluginid);
  if(!$IsCalled)
  {
    $pluginid = GetVar('plugin_id', 0, 'whole_number');
  }
  $pluginid = Is_Valid_Number($pluginid, 0, 1,99999999);

  if((!$IsCalled && !$pluginid) || in_array($pluginid, $core_pluginids_arr))
  {
    DisplayMessage(AdminPhrase('message_no_access_uninstall'), true);
    DisplayPlugins();
    return false;
  }

  if($pluginid >= 0)
  {
    $DB->result_type = MYSQL_ASSOC;
    $plugin_arr = $DB->query_first('SELECT * FROM {plugins} WHERE pluginid = %d', $pluginid);
  }
  if(!$IsCalled && (empty($plugin_arr) || empty($plugin_arr['pluginpath'])))
  {
    DisplayMessage(AdminPhrase('message_plugin_not_found'), true);
    DisplayPlugins();
    return false;
  }

  if(!empty($plugin_arr))
  {
    $getpluginfolder = explode('/', $plugin_arr['pluginpath']);
    $uninstallfile = '../plugins/' . $getpluginfolder[0] . '/' . PLUGINS_INSTALL_FILE;
    if(is_file($uninstallfile) && file_exists($uninstallfile))
    {
      $installtype = 'uninstall';
      $uniqueid = $pluginid;
      include($uninstallfile);
    }
  }

  if(!empty($pluginid))
  {
    $DB->query('DELETE FROM {adminphrases} WHERE adminpageid = 2 AND pluginid = %d',$pluginid);
    // SD313: remove ratings for plugin (if any)
    $DB->query('DELETE FROM {ratings}      WHERE (pluginid = '.$pluginid.") OR (rating_id LIKE '%s')", 'p'.$pluginid.'-%');

    //SD351: delete templates for plugin
    SD_Smarty::DeleteTemplatebyPlugin($pluginid);

    //SD360: clear cache for report reasons
    require_once(SD_INCLUDE_PATH.'class_sd_reports.php');
    SD_Reports::DeletePluginReasons($pluginid);
  }
  $DB->query('DELETE FROM {phrases}        WHERE pluginid = %d', $pluginid);
  $DB->query('DELETE FROM {pluginsettings} WHERE pluginid = %d', $pluginid);
  $DB->query('DELETE FROM {plugins}        WHERE pluginid = %d', $pluginid);
  $DB->query('DELETE FROM {comments}       WHERE pluginid = %d', $pluginid);

  // SD313x: delete cache files this plugin relates to
  if(isset($SDCache) && is_object($SDCache))
  {
    //SD370: also create mobile pagesort entries
    foreach(array('','_mobile') as $suffix)
    {
      if($suffix && !SD_MOBILE_FEATURES) break;
      $tbl = PRGM_TABLE_PREFIX.'pagesort'.$suffix;
      $get_categories = $DB->query("SELECT DISTINCT categoryid FROM $tbl WHERE pluginid = '%s'", $pluginid);
      while($category_arr = $DB->fetch_array($get_categories,null,MYSQL_ASSOC))
      {
        $SDCache->delete_cacheid(($suffix?MOBILE_CACHE:'').CACHE_PAGE_PREFIX.$category_arr['categoryid']);
      }
    }
    $SDCache->delete_cacheid('psettings_'.$pluginid);
    $SDCache->delete_cacheid('planguage_'.$pluginid);
    $SDCache->delete_cacheid(CACHE_ALL_PLUGINS);
  }

  $DB->query("UPDATE {pagesort} SET pluginid = '1' WHERE pluginid = '%s'", $pluginid);

  // delete 'default plugin css' (only delete the default, don't mess with other skins)
  $DB->query('DELETE FROM {skin_css} WHERE skin_id = 0 AND plugin_id = %d LIMIT 1',$pluginid);

  // Remove pluginid from all usergroups
  $usergroups = $DB->query('SELECT usergroupid, pluginviewids, pluginsubmitids, plugindownloadids, pluginadminids FROM {usergroups}');
  while($usergroup = $DB->fetch_array($usergroups))
  {
    // First get the arrays
    $pluginviewids     = empty($usergroup['pluginviewids'])     ? array() : @explode(',', $usergroup['pluginviewids']);
    $pluginsubmitids   = empty($usergroup['pluginsubmitids'])   ? array() : @explode(',', $usergroup['pluginsubmitids']);
    $plugindownloadids = empty($usergroup['plugindownloadids']) ? array() : @explode(',', $usergroup['plugindownloadids']);
    $plugincommentids  = empty($usergroup['plugincommentids'])  ? array() : @explode(',', $usergroup['plugincommentids']);
    $pluginadminids    = empty($usergroup['pluginadminids'])    ? array() : @explode(',', $usergroup['pluginadminids']);

    // erase the $pluginid from the arrays
    if(in_array($pluginid, $pluginviewids))
    {
      unset($pluginviewids[array_search($pluginid, $pluginviewids)]);
    }

    if(in_array($pluginid, $pluginsubmitids))
    {
      unset($pluginsubmitids[array_search($pluginid, $pluginsubmitids)]);
    }

    if(in_array($pluginid, $plugindownloadids))
    {
      unset($plugindownloadids[array_search($pluginid, $plugindownloadids)]);
    }

    if(in_array($pluginid, $plugincommentids))
    {
      unset($plugincommentids[array_search($pluginid, $plugincommentids)]);
    }

    if(in_array($pluginid, $pluginadminids))
    {
      unset($pluginadminids[array_search($pluginid, $pluginadminids)]);
    }

    // put them back into strings
    $newpluginviewids     = @implode(',', $pluginviewids);
    $newpluginsubmitids   = @implode(',', $pluginsubmitids);
    $newplugindownloadids = @implode(',', $plugindownloadids);
    $newplugincommentids  = @implode(',', $plugincommentids);
    $newpluginadminids    = @implode(',', $pluginadminids);

    // now update the usergroup with the updated category values
    $DB->query("UPDATE {usergroups}
      SET pluginviewids     = '$newpluginviewids',
          pluginsubmitids   = '$newpluginsubmitids',
          plugindownloadids = '$newplugindownloadids',
          plugincommentids  = '$newplugincommentids',
          pluginadminids    = '$newpluginadminids'
      WHERE usergroupid     = %d", $usergroup['usergroupid']);
  } //while

  if(!$IsCalled)
  {
    global $pageid;
    RedirectPage('plugins.php?action=display_plugins'.($pageid>1?'&amp;page='.$pageid:''),
                 AdminPhrase('plugins_plugin_uninstalled'));
  }

} //UninstallPlugin


// ############################## INSTALL PLUGIN ###############################

if($action == 'installplugin')
{
  // SD313: need to re-check access!
  if(empty($userinfo['adminaccess']))
  {
    DisplayMessage(AdminPhrase('message_no_access_install'), true);
    DisplayPlugins();
    DisplayAdminFooter();
    exit();
  }

  //SD341: "base_plugin" is for self-cloning enabled plugins to allow for exact
  //       identification by it's base plugin name, like "Articles"
  $base_plugin = '';
  $installtype = 'install';
  $installpath = GetVar('installpath', '', 'string', true, false);
  $install_errors = empty($installpath) || !is_file($installpath);

  // SD313: skip if install file does not exist or returns false
  if(!$install_errors && include($installpath))
  {
    if(empty($pluginsettings))
    {
      // viewing, and admin bitfield value
      $pluginsettings = 17;
    }
    $pluginsettings = (int)$pluginsettings;

    // SD313: if no required variable is set, it's an error
    if(empty($uniqueid) && empty($pluginid))
    {
      $install_errors = true;
    }

    if(!$install_errors)
    {
      // See if it's already created
      if(isset($uniqueid) || (!empty($uniqueid) && ($uniqueid == GetPluginID($pluginname))))
      {
        $uniqueid = (int)$uniqueid;
        $install_errors = ($uniqueid < 1);
        if(!$install_errors)
        {
          // installing old plugin
          // SD313: escape all strings
          $DB->query("REPLACE INTO {plugins} (pluginid, name, displayname, version, pluginpath, settingspath, authorname, authorlink, settings)
                      VALUES (%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d)",
                      $uniqueid,
                      $DB->escape_string($pluginname),
                      $DB->escape_string($pluginname),
                      $DB->escape_string($version),
                      $DB->escape_string($pluginpath),
                      $DB->escape_string($settingspath),
                      $DB->escape_string($authorname),
                      $DB->escape_string($authorlink),
                      $pluginsettings);
          if($DB->column_exists(PRGM_TABLE_PREFIX.'plugins','base_plugin'))
          {
            $DB->query("UPDATE {plugins} SET base_plugin = '%s' WHERE pluginid = %d",
                       $DB->escape_string($base_plugin), $uniqueid);
          }
        }
      }
      else
      {
        // installing new plugin
        if(!empty($pluginid))
        {
          $uniqueid = (int)$pluginid;
        }
        $install_errors = ($uniqueid < 1) || in_array($uniqueid, $core_pluginids_arr);
        if(!$install_errors)
        {
          $extra = '';
          if($DB->column_exists(PRGM_TABLE_PREFIX.'plugins','base_plugin'))
          {
            $extra = ", base_plugin = '".$DB->escape_string($base_plugin)."'";
          }
          // SD313: escape all strings
          $DB->query("UPDATE {plugins}
            SET displayname = '%s', version = '%s', pluginpath = '%s',
                settingspath = '%s', authorname = '%s', authorlink = '%s',
                settings = %d $extra
                WHERE pluginid = %d LIMIT 1",
            $DB->escape_string($pluginname),
            $DB->escape_string($version),
            $DB->escape_string($pluginpath),
            $DB->escape_string($settingspath),
            $DB->escape_string($authorname),
            $DB->escape_string($authorlink),
            $pluginsettings,
            $uniqueid);
        }
      }
    }

    if(!$install_errors)
    {
      DefaultPluginInUsergroups($uniqueid, $pluginsettings);
    }

    //SD321: remove cache for plugin names
    if(isset($SDCache))
    {
      $SDCache->delete_cacheid(CACHE_ALL_PLUGINS);
    }
    global $pageid;
    RedirectPage('view_plugin.php?pluginid='.$uniqueid.($pageid>1?'&amp;page='.$pageid:''),
                 AdminPhrase('plugins_plugin_installed'));
  }
  else
  {
    $install_errors = true;
  }

  //SD321: remove cache for plugin names
  if(isset($SDCache))
  {
    $SDCache->delete_cacheid(CACHE_ALL_PLUGINS);
  }

  //SD362: fill stuctural data...
  if(function_exists('FillPluginsTitlesTable'))
  {
    FillPluginsTitlesTable(); # in functions_plugins.php
  }

  if($install_errors)
  {
    global $pageid;
    RedirectPage('plugins.php'.($pageid>1?'?page='.$pageid:''),
                 AdminPhrase('plugins_failed_install'));
  }
  $action = '';

} //installplugin


// ############################## UPGRADE PLUGIN ###############################

if($action == 'upgradeplugin')
{
  // SD313: need to re-check access!
  if(empty($userinfo['adminaccess']))
  {
    DisplayMessage(AdminPhrase('message_no_access_upgrade'), true);
    DisplayPlugins();
    DisplayAdminFooter();
    exit();
  }

  //SD341: "base_plugin" is for self-cloning enabled plugins to allow for exact
  //       identification by it's base plugin name, like "Articles"
  $base_plugin    = '';
  $upgrade_error  = false;
  $installtype    = 'upgrade';
  $installpath    = GetVar('installpath', '', 'string', true, false);
  $currentversion = GetVar('currentversion', false, 'string', true, false);
  if( (substr($installpath,0, strlen(ROOT_PATH.'plugins')) == ROOT_PATH.'plugins') &&
      $currentversion && is_file($installpath) )
  {
    if(@include($installpath))
    {
      //SD341: update "base_plugin" if specified in installer:
      if(!empty($base_plugin))
      {
        $DB->query("UPDATE {plugins} SET base_plugin = '%s' WHERE pluginid = %d",
                   $DB->escape_string($base_plugin), (empty($uniqueid)?(int)$pluginid:(int)$uniqueid));
      }
      //SD370: remove cache for plugin names
      if(isset($SDCache))
      {
        $SDCache->delete_cacheid(CACHE_ALL_PLUGINS);
      }
      // the upgrade takes place in the install.php file
      // SD313: check if either $uniqueid and $pluginid is set
      global $pageid;
      RedirectPage('view_plugin.php?pluginid=' .
                   (empty($uniqueid)?(int)$pluginid:(int)$uniqueid).
                   ($pageid>1?'&amp;page='.$pageid:''),
                   AdminPhrase('message_plugin_upgraded'));
      DisplayAdminFooter();
      return;
    }
  }
  else
  {
    $upgrade_error = true;
  }

  if($upgrade_error)
  {
    global $pageid;
    $errors = AdminPhrase('message_plugin_upgrade_error') . ' ' . $installpath;
    RedirectPage('plugins.php'.($pageid>1?'?page='.$pageid:''), $errors, 2, true);
    DisplayAdminFooter();
  }
  $action = '';

} //upgradeplugin


// ############################################################################
// SELECT FUNCTION
// ############################################################################

switch($action)
{
  case 'display_plugins':
    DisplayPlugins();
  break;

  case 'update_plugin_display_names':
    UpdatePluginDisplayNames();
  break;

  case 'display_custom_plugin_form':
    DisplayCustomPluginForm();
  break;

  case 'insert_custom_plugin':
    if($userinfo['adminaccess'] || !empty($userinfo['maintain_customplugins']))
    InsertCustomPlugin();
  break;

  case 'update_custom_plugin':
    if($userinfo['adminaccess'] || !empty($userinfo['maintain_customplugins']))
    UpdateCustomPlugin();
  break;

  case 'display_plugin_permissions':
    DisplayPluginPermissions();
  break;

  case 'update_plugin_permissions':
    UpdatePluginPermissions();
  break;

  case 'display_install_upgrade_plugins':
    if($userinfo['adminaccess'])
    {
      UninstallBrokenPlugins();
      DisplayInstallUpgradePlugins();
    }
  break;

  case 'delete_plugin':
    UninstallPlugin();
  break;
}

// ############################################################################
// DISPLAY ADMIN FOOTER
// ############################################################################

DisplayAdminFooter($NoMenu);
