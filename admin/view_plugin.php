<?php

// DEFINE CONSTANTS
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');

// INIT PRGM
include(ROOT_PATH . 'includes/init.php');

// GET/POST VARS
$pluginid = Is_Valid_Number(GetVar('pluginid', 1, 'whole_number'),1,1,999999);

// SD313: moved code for "load_wysiwyg" to "functions_admin.php"!
$refreshpage = GetVar('refreshpage', '', 'string', true, false);

// LOAD ADMIN LANGUAGE
$admin_phrases = LoadAdminPhrases(2, $pluginid);

$script = '
<script type="text/javascript">
if (typeof(jQuery) !== "undefined") {
jQuery(document).ready(function() {
  (function($){
	$("[data-rel=popover]").popover({container:"body"});
	$(".mytooltip").tooltip();
  })(jQuery);
});
}
</script>
';
//$sd_head->AddScript($script);

// UPDATE PLUGIN SETTINGS OR LOAD PLUGIN
if(!empty($_POST['updatesettings']))
{
  DisplayAdminHeader('Plugins');

  CheckAdminAccess('view_plugin');

  $pluginsettings = GetVar('settings', array(), 'array', true, false);
  
  UpdatePluginSettings($pluginsettings, $refreshpage);
}
else
{
  $refreshpage = 'view_plugin.php?pluginid=' . $pluginid;
  // SD313: was WYSIWYG requested?
  if(!empty($load_wysiwyg))
  {
    $refreshpage .= '&amp;load_wysiwyg=1';
  }
  define('REFRESH_PAGE', $refreshpage); //SD313

  $plugin_arr = $DB->query_first('SELECT pluginid, name, displayname, version, settingspath'.
                                 ' FROM '.PRGM_TABLE_PREFIX.'plugins'.
                                 ' WHERE pluginid = '.(int)$pluginid);

  $plugin = & $plugin_arr; // SD313: for compatibility with SD2.x plugins

  // ############# INCLUDE PLUGIN HEADER FILE #############
  // SD313: process plugin header file, which could use sd_header_add
  // to add HTML code to the page's <HEAD>; "DisplayAdminHeader" would
  // then pick it up and output everything.
  $headerfile = ROOT_PATH . 'plugins/' . dirname($plugin_arr['settingspath']) . '/header.php';
  if(file_exists($headerfile) && is_file($headerfile))
  {
    @include($headerfile);
  }

  // ################## DISPLAY ADMIN HEADER #################
  //SD322: pass current plugin name for browser title
  $pname = '';
  if(isset($plugin_arr['name']))
  {
    $pluginid = (int)$plugin_arr['pluginid'];
    $pname = $plugin_arr['name'];
    if(isset($plugin_names[$plugin_arr['name']]))
    {
      $pname = $plugin_names[$plugin_arr['name']];
      $plugin['name'] = $pname;
    }
    $admin_sub_menu_arr['&raquo; '.$pname] = '';
    //SD322: show permissions icon for plugin
    if($userinfo['adminaccess'])
    {
      //SD370: use "font-awesome" icons
      $perm = '<a href="'.$pluginid.'" class="permissionslink"><i class="ace-icon fa fa-key orange bigger-120"></i></a>';
      #<i class="sprite sprite-key"></i>

      //SD360: display link to CSS entry on Skins page:
      //SD370: fixed SQL: was empty if only default existed (skinid = 0)
      $DB->result_type = MYSQL_ASSOC;
      if($entry = $DB->query_first('SELECT sc.skin_id, sc.skin_css_id, sc.var_name'.
                                   ' FROM '.PRGM_TABLE_PREFIX.'skin_css sc'.
                                   ' WHERE sc.plugin_id = '.(int)$pluginid.
                                   ' AND sc.skin_id IN (0, IFNULL((SELECT MAX(skinid) FROM {skins} WHERE activated = 1),0))'.
                                   ' ORDER BY sc.skin_id DESC, sc.skin_css_id'.
                                   ' LIMIT 0,1'))
      {
        $perm .= ' &nbsp;<a target="_blank" title="CSS" href="skins.php?skinid='.$entry['skin_id'].
                 '&amp;structure=css&amp;css_var_name='.urlencode($plugin_arr['name']).
                 '&amp;pluginid='.$pluginid.'"><i class="ace-icon fa fa-external-link"></i></a>';
      }

      //SD370: display link to templates page on Skins page:
      $DB->result_type = MYSQL_ASSOC;
      if($DB->query_first('SELECT 1 FROM '.PRGM_TABLE_PREFIX.'templates t'.
                          ' WHERE t.pluginid = '.(int)$pluginid.' LIMIT 1'))
      {
        $perm .= ' &nbsp;&nbsp;<a href="templates.php?action=display_templates'.
                 '&amp;customsearch=1&amp;searchpluginid='.$pluginid.
                 '" target="_blank" title="'.AdminPhrase('menu_templates').'">'.
                 '<i class="ace-icon fa fa-tasks"></i></a>';
      }

      $admin_sub_menu_arr[$perm] = '';
    }
  }
  
 /* $js = array(
  	SD_JS_PATH . 'jquery-migrate-1.2.1.min.js',
	ROOT_PATH . MINIFY_PREFIX_F . 'includes/javascript/markitup/markitup-full.js',
    ROOT_PATH.ADMIN_PATH.'/javascript/functions.js',
	SITE_URL . ADMIN_STYLES_FOLDER . '/assets/js/bootbox.min.js',
	ROOT_PATH.ADMIN_PATH.'/javascript/jquery.tinymce.js',
    ROOT_PATH.ADMIN_PATH.'/javascript/tiny_init_jq.js',);*/
//$sd_head->AddJS($js);

$css = array(SD_JS_PATH . 'markitup/skins/markitup/style.css',
                   SD_JS_PATH . 'markitup/sets/bbcode/style.css',
				  );
$sd_head->AddCSS($css);
  
  $script = '
<script type="text/javascript">
if (typeof(jQuery) !== "undefined") {
  jQuery(document).ready(function() {
	 if(typeof(jQuery.fn.ceebox) !== "undefined") {
      jQuery("a.permissionslink").each(function(event) {
        jQuery(this).find("img").attr("alt", "'.htmlspecialchars(AdminPhrase('plugins_permissions'),ENT_COMPAT).'");
        jQuery(this).toggleClass("cbox");
        jQuery(this).attr("rel", "iframe modal:false height:420");
        var link = "plugins.php?action=display_plugin_permissions&amp;cbox=1&amp;pluginid=" + jQuery(this).attr("href");
        jQuery(this).attr("href", link).attr("title","'.
          htmlspecialchars(strip_tags(AdminPhrase('plugins_usergroup_permissions_for').' '.$pname),ENT_COMPAT).'");
      });
      '.
      GetCeeboxDefaultJS(false). //SD322 - use CeeBox
	  '
    }
  });
}
</script>';
$sd_head->AddScript($script);

CheckAdminAccess('view_plugin');

  /* SD400
  We want to let plugins insert javascript and CSS into the header and footer instead of
  adding it inline.  Turn on the output buffer to we can include the plugin settings file
  */
  ob_start();
  
  // ############# INCLUDE PLUGIN SETTINGS FILES #############
  $settings_file = ROOT_PATH . 'plugins/' . $plugin_arr['settingspath'];
  if(empty($plugin_arr['settingspath']) || !file_exists($settings_file) || !@include($settings_file))
  {
    if(isset($plugin_arr['settingspath']) && strlen($plugin_arr['settingspath']))
    {
      echo '<div class="alert alert-error">Error: Plugin settings file not found: '. $plugin_arr['settingspath'].'</div>';
    }
    DisplayMessage($pname.' '.AdminPhrase('plugins_no_settings_found'));
  }
  
 // $sd_head->AddCSS($css);
 // $sd_head->AddScript($script);
 // $sd_head->AddJS($js);
  
  $obstore = ob_get_contents();
  
  ob_end_clean();

  DisplayAdminHeader('Plugins', (isset($admin_sub_menu_arr)?$admin_sub_menu_arr:null), $pname);
  
  echo $obstore;


  
}

// ###############################################################################
// DISPLAY ADMIN FOOTER
// ###############################################################################

DisplayAdminFooter();
