<?php
// DEFINE CONSTANTS
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');

// INIT PRGM
include(ROOT_PATH . 'includes/init.php');
include(ROOT_PATH . 'includes/enablegzip.php');
include(ROOT_PATH . 'includes/functions_frontend.php');

//SD370: deny access if NOT ajax request!
if(!Is_Ajax_Request())
{
  DisplayMessage(AdminPhrase('common_page_access_denied'), true);
  exit;
}

// LOAD ADMIN LANGUAGE
$admin_phrases = LoadAdminPhrases(6);

CheckAdminAccess('skins');

// GET POST VARS
$action        = GetVar('action', 'display_default', 'string');
$skinid        = Is_Valid_Number(GetVar('skinid', 0, 'whole_number'),0,0);
$structure     = GetVar('structure', 'header', 'string');
$designid      = Is_Valid_Number(GetVar('designid', 0, 'whole_number'),0,0);
$css_var_name  = GetVar('css_var_name', 'css', 'string');
$layout_number = Is_Valid_Number(GetVar('layout_number', 1, 'whole_number'),1,1);
$design_name   = GetVar('design_name', '', 'string');
$newheight     = GetVar('height', '350', 'whole_number');
$fallback      = GetVar('fallback', false, 'bool');

define('COOKIE_HL_THEME','_skins_hl_theme');

// GET SKIN
if($skinid)
  $where = 'skinid = '.(int)$skinid;
else
  $where = 'activated = 1';
$DB->result_type = MYSQL_ASSOC;
$skin_arr = $DB->query_first('SELECT * FROM {skins} WHERE '.$where);

if(empty($skin_arr))
{
  $action = '';
  DisplayMessage('Skin not found in database!',true);
  exit();
}
$skinid = $skin_arr['skinid'];
$legacy = empty($skin_arr['skin_engine']) || ($skin_arr['skin_engine'] != 2);


if(!headers_sent()) //SD370: send header
{
  header("Cache-Control: private");
  header('Content-type:text/html; charset=' . SD_CHARSET);
}


// ############################################################################
// UPDATE SKIN
// ############################################################################

if($action == 'update_skin')
{
  if(!CheckFormToken(null, true))
  {
    return false;
  }

  //SD343: removed extra stripslashes() since JS code within content would
  // get destroyed, such as myvar.match(/\?page=(\d+)$/g,'')
  // then becomes invalid like puri.match(/?page=(d+)$/g,'')
  //SD370: added mobile variants
  $content = GetVar('skincontent', '', 'html');
  $clear_cache = false;
  if(!$legacy)
  {
    if( ($structure == 'header') || ($structure == 'footer') ||
        ($structure == 'mobile_header') || ($structure == 'mobile_footer') )
    {
      // does this header/footer include plugins?
      if(strstr($content, '[PLUGIN]'))
      {
        // how many plugin positions in this header/footer?
        $content_plugin_count = substr_count($content, '[PLUGIN]');

        // lets update all layouts for this skin that include header
        // (if header) or footer (if footer)
        $layout_arr = array();
        if($get_layouts = $DB->query('SELECT designid, layout FROM {designs}'.
                                     ' WHERE skinid = %d',$skinid))
        {
          while($layout_arr = $DB->fetch_array($get_layouts,null,MYSQL_ASSOC))
          {
            if( (($structure == 'header') && (false !== strstr($layout_arr['layout'], '[HEADER]'))) ||
                (($structure == 'footer') && (false !== strstr($layout_arr['layout'], '[FOOTER]'))) ||
                (($structure == 'mobile_header') && (false !== strstr($layout_arr['layout'], '[MOBILE_HEADER]'))) ||
                (($structure == 'mobile_footer') && (false !== strstr($layout_arr['layout'], '[MOBILE_FOOTER]'))) )
            {
              $total_plugin_count = ($content_plugin_count + substr_count($layout_arr['layout'], '[PLUGIN]'));

              // does this layout include footer (if header) or header (if footer)
              // then add the plugin slots of respective counterpart
              if(($structure == 'header') && strstr($layout_arr['layout'], '[FOOTER]'))
              {
                $total_plugin_count += substr_count($skin_arr['footer'], '[PLUGIN]');
              }
              else
              if(($structure == 'footer') && strstr($layout_arr['layout'], '[HEADER]'))
              {
                $total_plugin_count += substr_count($skin_arr['header'], '[PLUGIN]');
              }
              else
              if(($structure == 'mobile_footer') && strstr($layout_arr['layout'], '[HEADER]'))
              {
                $total_plugin_count += substr_count($skin_arr['mobile_header'], '[PLUGIN]');
              }
              else
              if(($structure == 'mobile_header') && strstr($layout_arr['layout'], '[FOOTER]'))
              {
                $total_plugin_count += substr_count($skin_arr['mobile_footer'], '[PLUGIN]');
              }

              $DB->query('UPDATE {designs} SET maxplugins = %d WHERE designid = %d AND maxplugins <> %d',
                         $total_plugin_count,$layout_arr['designid'],$total_plugin_count);
            }
          } //while
        }
      }
      $DB->query("UPDATE {skins} SET %s = '%s' WHERE skinid = %d LIMIT 1",
                 $structure, $DB->escape_string($content), $skinid);
      $clear_cache = true;
    }
    else
    if($structure == 'layout')
    {
      // does this layout include header?
      if(strstr($content, "[HEADER]"))
        // how many plugin positions in header?
        $header_plugin_count = substr_count($skin_arr['header'], '[PLUGIN]');
      else
      // how many plugin positions in mobile header?
      if(strstr($content, "[MOBILE_HEADER]"))
        $header_plugin_count = substr_count($skin_arr['mobile_header'], '[PLUGIN]');
      else
        $header_plugin_count = 0;

      // does this layout include footer?
      if(strstr($content, "[FOOTER]"))
        // how many plugin positions in footer?
        $footer_plugin_count = substr_count($skin_arr['footer'], '[PLUGIN]');
      else
      // how many plugin positions in mobile footer?
      if(strstr($content, "[MOBILE_FOOTER]"))
        $footer_plugin_count = substr_count($skin_arr['mobile_footer'], '[PLUGIN]');
      else
        $footer_plugin_count = 0;

      //SD313: clean up design's name
      $design_name = CleanVar(strip_alltags($design_name));

      // how many plugins in this layout?
      $layout_plugin_count = substr_count($content, '[PLUGIN]');

      $total_plugin_count = ($header_plugin_count + $footer_plugin_count + $layout_plugin_count);

      $DB->query('UPDATE {designs}'.
                 " SET maxplugins = %d, layout = '%s', design_name = '%s'".
                 ' WHERE designid = %d LIMIT 1',
                  $total_plugin_count, $DB->escape_string($content), $design_name, $designid);
      $clear_cache = true;
    }
    else
    if($structure == 'menu')
    {
      $sql_set = '';
      foreach(array('menu_level0_opening', 'menu_level0_closing',
                    'menu_submenu_opening', 'menu_submenu_closing',
                    'menu_item_opening', 'menu_item_closing',
                    'menu_item_link') as $column)
      {
        $value = GetVar($column, '', 'html');
        $sql_set .= ($sql_set == '' ? '' : ', ') . $column . " = '" .
                    $DB->escape_string($value) . "'";
      }
      $DB->query('UPDATE {skins} SET '.$sql_set.' WHERE skinid = %d',$skinid);
      $clear_cache = true;
    }
    else
    if($structure == 'mobile_menu') //SD370
    {
      $sql_set = '';
      foreach(array('mobile_menu_level0_opening', 'mobile_menu_level0_closing',
                    'mobile_menu_submenu_opening', 'mobile_menu_submenu_closing',
                    'mobile_menu_item_opening', 'mobile_menu_item_closing',
                    'mobile_menu_item_link') as $column)
      {
        $value = GetVar($column, '', 'html');
        $sql_set .= ($sql_set == '' ? '' : ', ') . $column . " = '" .
                    $DB->escape_string($value) . "'";
      }
      $DB->query('UPDATE {skins} SET '.$sql_set.' WHERE skinid = %d',$skinid);
      $clear_cache = true;
    }
  }

  //SD370: only error page and CSS entries are accessible for SD2 skins
  if($structure == 'error_page')
  {
    $DB->query("UPDATE {skins} SET error_page = '%s' WHERE skinid = %d",
                $DB->escape_string($content), $skinid);
    $clear_cache = true;
  }
  else
  if($structure == 'css')
  {
    if($css_arr = $DB->query_first("SELECT skin_css_id FROM {skin_css}".
                                   " WHERE skin_id = %d AND var_name = '%s' LIMIT 1",
                                   $skinid, $DB->escape_string($css_var_name)))
    {
      $DB->query("UPDATE {skin_css} SET css = '" . $DB->escape_string($content) . "'
                  WHERE skin_id = %d AND skin_css_id = %d",
                  $skinid, $css_arr['skin_css_id']);
    }
    else
    {
      // create a new CSS row for current skin
      if($css_arr = $DB->query_first('SELECT plugin_id, var_name FROM {skin_css}'.
                                     " WHERE skin_id = 0 AND var_name = '%s' LIMIT 1",
                                     $DB->escape_string($css_var_name)))
      {
        $DB->query("INSERT INTO {skin_css} (skin_id, plugin_id, var_name, css) VALUES
                    (%d, %d, '%s', '" .
                    $DB->escape_string($content) . "')",
                    $skinid, $css_arr['plugin_id'], $DB->escape_string($css_var_name));
      }
    }
    if(isset($SDCache) && $SDCache->IsActive())
    {
      $SDCache->delete_cacheid(CACHE_ALL_SKIN_CSS);
    }
  }

  // SD313: remove related cache files for skin
  if($clear_cache && isset($SDCache) && $SDCache->IsActive())
  {
    $SDCache->purge_cache(true);
  }


  if(Is_Ajax_Request())
  {
    die('1');
  }

  // this should not happen!
  echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html;charset='.SD_CHARSET.'" />
<script type="text/javascript" src="'.(defined('JQUERY_GA_CDN') && strlen(JQUERY_GA_CDN)?JQUERY_GA_CDN:SD_JS_PATH.JQUERY_FILENAME).'"></script>
<script type="text/javascript" src="'.SITE_URL.'includes/javascript/jquery-migrate-1.2.1.min.js"></script>
<style type="text/css">
body,html{
  background: #f0f2f2;
  color: #222222;
  font-family: Helvetica, Arial, sans-serif;
  font-size: 13px;
  line-height: 18px;
  margin: 0px;
  padding: 0px;
  text-align: left;}
a:link, a:visited{
  color: #71767e;
  text-decoration: none;}
a:active { color: #0094c8; }
a:hover { color: #222222; }
div#error_message{
  background: #ffeaef;
  border: 3px solid #ff829f;
  left: 55px;
  margin-bottom: 15px;
  padding: 15px; }
div#success_message{
  background: #eaf4ff;
  border: 3px solid #82c0ff;
  left: 55px;
  margin-bottom: 15px;
  padding: 15px;}
textarea#skincontent {
  background: #ffffff;
  border: none;
  color: #222222;
  font-size: 13px;
  font-family: "Monaco", "Menlo", "Ubuntu Mono", "Consolas", "source-code-pro", "Lucida Console", "Courier New", "Courier";
  margin: 0;
  padding: 0;
  vertical-align: middle;
  width: 99%;
  height: 100%;}
</style>
</head>
<body>';

  // SD313: reload full page so that layout with changed names
  // also appear correctly in layouts list on left side:
  if(in_array($structure, array('css','header','footer','error_page','menu','mobile_menu','mobile_header','mobile_footer')))
  {
    RedirectPage('skin_structure_selection.php?skinid=' . $skinid . '&structure=' . $structure . '&designid=' . $designid . '&css_var_name=' . $css_var_name .
                 '&layout_number=' . $layout_number, AdminPhrase('skins_skin_updated'), 2);
  }
  else
  {
    echo '
  <div id="success_message">'.AdminPhrase('skins_skin_updated').'</div>
  <script type="text/javascript">
  //<![CDATA[
    jQuery(document).ready(function() {
      jQuery("#success_message").delay(1000).delay(0, function(){
        window.parent.location="skins.php?skinid=' . $skinid . '&structure=' . $structure .
        (!empty($designid)?'&designid=' . $designid:'') .
        (!empty($layout_number)?'&layout_number=' . $layout_number:''). '";
      });
    });
  //]]>
  </script>';
    }
  echo '
</body>
</html>';

  return true;

} //update_skin

// ############################################################################
// GET CONTENT
// ############################################################################

$content_class = 'content_header';
if($structure == 'footer')
{
  $content = $skin_arr['footer'];
  $content_name = AdminPhrase('skins_footer');
}
else if($structure == 'mobile_footer')
{
  $content = $skin_arr['mobile_footer'];
  $content_name = AdminPhrase('skins_mobile_footer');
}
else if($structure == 'mobile_header')
{
  $content = $skin_arr['mobile_header'];
  $content_name = AdminPhrase('skins_mobile_header');
}
else if($structure == 'mobile_menu')
{
  $content = '';
  $content_name = AdminPhrase('skins_mobile_menu_settings');
}
else if($structure == 'menu')
{
  $content = '';
  $content_name = AdminPhrase('skins_menu_settings');
}
else if($structure == 'error_page')
{
  $content = $skin_arr['error_page'];
  $content_name = AdminPhrase('skins_error_page');
}
else if($structure == 'layout')
{
  $content_class = 'content_header_layout';
  $layout_arr = $DB->query_first('SELECT designid, layout FROM {designs} WHERE designid = %d',$designid);

  $content = $layout_arr['layout'];
  $content_name = AdminPhrase('skins_layout');
}
else if($structure == 'css')
{
  $DB->result_type = MYSQL_ASSOC;
  if(!$css_arr = $DB->query_first('SELECT * FROM {skin_css}'.
                                  " WHERE skin_id = %d AND var_name = '%s'",
                                  $skinid, $DB->escape_string($css_var_name)))
  {
    // not found, try to get default
    $DB->result_type = MYSQL_ASSOC;
    $css_arr = $DB->query_first('SELECT * FROM {skin_css}'.
                                " WHERE skin_id = 0 AND var_name = '%s'",
                                $DB->escape_string($css_var_name));
  }
  if(isset($css_arr) && is_array($css_arr))
  {
    $content = $css_arr['css'];
    $content_name = $css_arr['var_name'];
  }
  else
  {
    $content = '';
    $content_name = '';
  }
}
else // assume 'header'
{
  $content = $skin_arr['header'];
  $content_name = AdminPhrase('skins_header');
}

if(!Is_Ajax_Request())
{
  echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>'.PRGM_NAME.' - Skin Editor</title>
<meta http-equiv="Content-Type" content="text/html; charset='.$mainsettings_charset.'" />
</head>
<body>';
}

echo '
<div id="output">
<style type="text/css">
#skineditor { height: '.$newheight.'px !important; }
</style>
<form method="post" action="skin_structure_selection.php?action=update_skin" id="skinform" name="skinform">
'.PrintSecureToken().'
<input type="hidden" name="skinid" value="' . $skinid . '" />
<input type="hidden" name="structure" id="structure" value="' . $structure . '" />
<input type="hidden" name="css_var_name" value="' . $css_var_name . '" />
<input type="hidden" name="designid" value="' . $designid . '" />
<input type="hidden" name="layout_number" value="' . $layout_number . '" />
<div id="editor_top">
';

// SD313: display layout's design_name
$content_element_name = '';
if($structure == 'layout')
{
  $DB->result_type = MYSQL_ASSOC;
  $delete_layout_link = false;
  if(($structure == 'layout') &&
     $DB->query_first('SELECT designid FROM {designs} WHERE skinid = %d AND designid != %d LIMIT 1',$skinid, $designid))
  {
    $delete_layout_link = '
    <div class="delete_layout_bottom">
      <a href="#" id="delete_layout" class="btn btn-danger "><i class="ace-icon fa fa-trash"></i> '.AdminPhrase('skins_delete_layout').'</a>
    </div>';
  }
  $DB->result_type = MYSQL_ASSOC;
  if($current_design = $DB->query_first('SELECT designid, design_name FROM {designs} WHERE skinid = %d AND designid = %d LIMIT 1',
                                        $skinid, $designid))
  {
    $content_element_name = $current_design['design_name'];
    echo '
  <div class="content_header_layout">'.$content_name.' '.AdminPhrase('skins_skin_layout_name').'
    <input id="design_name" name="design_name" type="text" maxlength="64" style="min-width: 120px" value="'.$current_design['design_name'].'">
  </div>';
  }
}
else
{
  $content_element_name = $content_name;
  echo '
  <div class="'.$content_class.'">' . $content_name .'</div>';
}

//SD362: store current element name to be accessible for JS
echo '
  <div id="content_element_name" style="display:none;position:absolute;left-margin:-1000px">'.$content_element_name.'</div>';

if(!in_array($structure, array('css', 'menu')))
{
  echo '
  <select id="variable_selection" style="float: right; max-width: 140px;">
    <option value="0">'.AdminPhrase('skins_insert_variable').'</option>
    <option value="[PLUGIN]">[PLUGIN]</option>
    <option value="[HEADER]">[HEADER]</option>
    <option value="[FOOTER]">[FOOTER]</option>
    <option value="[MOBILE_HEADER]">[MOBILE_HEADER]</option>
    <option value="[MOBILE_FOOTER]">[MOBILE_FOOTER]</option>
    <option value="[ARTICLE_TITLE]">[ARTICLE_TITLE]</option>
    <option value="[PAGE_TITLE]">[PAGE_TITLE]</option>
    <option value="[PAGE_NAME]">[PAGE_NAME]</option>
    <option value="[PAGE_HTML_CLASS]">[PAGE_HTML_CLASS]</option>
    <option value="[PAGE_HTML_ID]">[PAGE_HTML_ID]</option>
    <option value="[CMS_HEAD_INCLUDE]">[CMS_HEAD_INCLUDE]</option>
    <option value="[CMS_HEAD_NOMENU]">[CMS_HEAD_NOMENU]</option>
    <option value="[LOGO]">[LOGO]</option>
    <option value="[BREADCRUMB]">[BREADCRUMB]</option>
    <option value="[MOBILENAVIGATION]">[MOBILENAVIGATION]</option>
    <option value="[MOBILE_RETURN]">[MOBILE_RETURN]</option>
    <option value="[NAVIGATION]">[NAVIGATION]</option>
    <option value="[NAVIGATION-TOPLEVEL]">[NAVIGATION-TOPLEVEL]</option>
    <option value="[NAVIGATION-TOPLEVEL-NOMENU]">[NAVIGATION-TOPLEVEL-NOMENU]</option>
    <option value="[NAVIGATION-TOP-ONLY]">[NAVIGATION-TOP-ONLY]</option>
    <option value="[NAVIGATION-TOP-ONLY-NOMENU]">[NAVIGATION-TOP-ONLY-NOMENU]</option>
    <option value="[NAVIGATION-BOTTOM-ONLY]">[NAVIGATION-BOTTOM-ONLY]</option>
    <option value="[NAVIGATION-BOTTOM-ONLY-NOMENU]">[NAVIGATION-BOTTOM-ONLY-NOMENU]</option>
    <option value="[SUBNAVIGATION]">[SUBNAVIGATION]</option>
    <option value="[SIBLINGPAGES]">[SIBLINGPAGES]</option>
    <option value="[COPYRIGHT]">[COPYRIGHT]</option>
    <option value="[ERROR_PAGE_MESSAGE_TITLE]">[ERROR_PAGE_MESSAGE_TITLE]</option>
    <option value="[ERROR_PAGE_MESSAGE]">[ERROR_PAGE_MESSAGE]</option>
    <option value="[REGISTER_PATH]">[REGISTER_PATH]</option>
    <option value="[USERCP_PATH]">[USERCP_PATH]</option>
    <option value="[LOSTPWD_PATH]">[LOSTPWD_PATH]</option>
    <option value="[LOGIN_PATH]">[LOGIN_PATH]</option>
  </select>
  ';
}

if($structure !== 'menu' && $structure !== 'mobile_menu')
{
  echo '
  <div class="content_header_layout">';

  if(!$fallback)
  {
    echo '
    <div style="float: left; padding: 6px;">
      <a id="hotkeys_help" href="#" title="Hotkeys" onclick="javascript:;">?</a>
      &nbsp;
      <span for="ace_theme">'.AdminPhrase('skins_theme').'</span>
    </div>
    <select id="ace_theme" style="float: left;" size="1">
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
    <select id="mode_selector" style="float: left;" size="1">
      <option value="html" selected="selected">HTML</option>
      <option value="css">CSS</option>
      <option value="php">PHP</option>
      <option value="js">Javascript</option>
    </select>';
  }
  echo '
    <input id="cpicker" name="cpicker" class="colorpicker" style="width: 60px;" type="text" size="7" maxlength="7" value="#000000" />
  </div> <!-- content_header_layout -->
<div style="clear:both;display:block;height:1px"> </div>
</div> <!-- editor_top -->';

  if($fallback)
  {
    echo '
    <div id="skineditor" style="display:none"></div>
    <textarea id="skincontent" name="skincontent" style="display: block;" rows="30" cols="80">' . htmlspecialchars($content) . '</textarea>';
  }
  else
  {
    echo '
    <div id="skineditor">' . htmlspecialchars($content) . '</div>
    <textarea id="skincontent" name="skincontent" style="display: none;" rows="10" cols="80">' . htmlspecialchars($content) . '</textarea>
    <div id="editor_statusBar">&nbsp; '.$content_name.' </div>
  ';
  }
}
else
{
  echo '
  <div id="menu-content">
  <input type="hidden" name="menu_settings" value="1" />
  <table class="table" summary="layout" width="99%">
  <tr class="info">
    <td colspan="2">'.
    AdminPhrase($structure == 'menu'?'skins_menu_settings_hint':'skins_mobile_menu_settings_hint').
    '</td>
  </tr>
  ';
  if($structure == 'menu')
    $array = array('menu_level0_opening', 'menu_level0_closing',
                   'menu_submenu_opening', 'menu_submenu_closing',
                   'menu_item_opening', 'menu_item_closing', 'menu_item_link');
  else
    $array = array('mobile_menu_level0_opening', 'mobile_menu_level0_closing',
                   'mobile_menu_submenu_opening', 'mobile_menu_submenu_closing',
                   'mobile_menu_item_opening', 'mobile_menu_item_closing', 'mobile_menu_item_link');

  foreach($array as $column)
  {
    echo '
  <tr>
    <td  width="220">'.AdminPhrase('skins_'.$column).'</td>
    <td  valign="top">
      <input type="text" class="form-control" name="'.$column.'" style="width: 95%" value="' .
      htmlspecialchars($skin_arr[$column], ENT_QUOTES). '" /><span class="helper-text">
      '.AdminPhrase('skins_'.$column.'_descr').'</span>
    </td>
  </tr>';
  }
  echo '
  </table>
  </div>
  ';
}

echo '
  <div id="editor_bottom">
  <span id="ed_changed" style="float:left;color:red;padding:4px;visibility:hidden;">'.
  AdminPhrase('editor_content_changed').'</span>
  <a href="#" class="btn btn-primary" onclick="javascript:jQuery(\'#skinform\').submit(); return false;"><i class="ace-icon fa fa-check"></i> '.
  AdminPhrase('skins_update_skin').'</a>';

if(($structure == 'layout') && $delete_layout_link)
{
  echo $delete_layout_link;
}
echo '<div class="clearfix"></div>
</div><!-- items_container --><br />
<input type="submit" value="' . AdminPhrase('skins_update_skin') . '" style="position:absolute;top:-9999px;left:-9999px;display:none;margin-left:-9999px" />
</form>
</div>';

if(!Is_Ajax_Request())
echo '
</body>
</html>';
