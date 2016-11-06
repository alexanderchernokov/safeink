<?php
// DEFINE CONSTANTS
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');

// INIT PRGM
include(ROOT_PATH . 'includes/init.php');
include(ROOT_PATH . 'includes/enablegzip.php');
include(ROOT_PATH . 'includes/functions_frontend.php');

// LOAD ADMIN LANGUAGE
$admin_phrases = LoadAdminPhrases(1);

// CHECK PAGE ACCESS
CheckAdminAccess('pages');

// GET POST VARS
$error_message = false;
$action     = GetVar('action', 'display_plugin_selection', 'string');
$categoryid = Is_Valid_Number(GetVar('categoryid', 0, 'whole_number'), 0);
$designid   = Is_Valid_Number(GetVar('designid',   0, 'whole_number'), 0);
$mobile     = (!empty($_GET['mobile']) && SD_MOBILE_FEATURES?1:0); //SD370

// SD313: check for page
if(!$categoryid)
{
  $error_message = '<div style="background: #ffeaef; border: 3px solid #ff829f; margin-bottom: 15px; padding: 15px;">' . AdminPhrase('pages_error_invalid_design') . '</div>';
}

// SAVE OR RETURN PLUGIN POSITIONS
$plugin_selection_arr = GetVar('plugin_selection_arr', array(), 'array', true, false);

if(!$error_message && !empty($_POST[SD_TOKEN_NAME]) && CheckFormToken())
{
  // Update plugins for selected design
  // First check to see if a plugin was selected twice
  $checkKeysUniqueComparison = create_function('$value','if ($value > 1) return true;');
  $result = array_keys(array_filter(array_count_values($plugin_selection_arr), $checkKeysUniqueComparison));

  for($i = 0, $rc = count($result); $i < $rc; $i++)
  {
    if($result[$i] != 1)
    {
      $pluginerror = 1;
      break;
    }
  }

  if(isset($pluginerror))
  {
    $error_message = '<div style="background: #ffeaef; border: 3px solid #ff829f; margin-bottom: 15px; padding: 15px;">' . AdminPhrase('pages_error_common_plugins') . '</div>';
  }
  else
  {
    // save info if we are 'editing' a category/page
    if($categoryid)
    {
      //SD370: added mobile support
      if($designid)
      {
        $DB->query('UPDATE {categories} SET '.($mobile?'mobile_':'').'designid = %d'.
                   ' WHERE categoryid = %d', $designid, $categoryid);
      }

      // first delete pagesort plugins for this category
      $pagesort = PRGM_TABLE_PREFIX.'pagesort'.($mobile?'_mobile':'');
      $DB->query('DELETE FROM '.$pagesort.' WHERE categoryid = %d', $categoryid);

      // now insert the new plugins into pagesort
      for($i = 0, $pc = count($plugin_selection_arr); $i < $pc; $i++)
      {
        // convert Menus (ex: + Custom Plugins) to the empty plugin
        if(empty($plugin_selection_arr[$i]))
        {
          $plugin_selection_arr[$i] = '1';
        }

        $DB->query('INSERT INTO '.$pagesort." (categoryid, pluginid, displayorder)
                    VALUES (%d, '%s', %d)",
                    $categoryid, (string)$plugin_selection_arr[$i], ($i+1));
      }

      // find and update the page IDs for user profile, user registration and user login panel
      // SD313: fixed: this call was within above loop
      UpdateUserPluginsPageIDs();

      // delete cache files related to designs
      if(isset($SDCache) && $SDCache->IsActive())
      {
        //SD370: added mobile
        $SDCache->delete_cacheid(CACHE_ALL_MAINSETTINGS);
        $cache_id = ($mobile?MOBILE_CACHE:'').CACHE_PAGE_PREFIX;
        $SDCache->delete_cacheid($cache_id.(int)$categoryid);
        $cacheid = (int)floor($categoryid/10);
        $SDCache->delete_cacheid(CACHE_PAGE_DESIGN.($mobile?MOBILE_CACHE:'').$cacheid);
      }

      // Check the "Set for all?" option of each plugin -- not valid for mobile!
      if(!$mobile && isset($_POST['plugin_set_for_all_pages']) &&
         is_array($_POST['plugin_set_for_all_pages']) &&
         count($_POST['plugin_set_for_all_pages']))
      {
        $catids = array();
        // get all categories which use the same design
        $DB->result_type = MYSQL_ASSOC;
        if($designid = $DB->query_first('SELECT designid FROM {categories} WHERE categoryid = %d', $categoryid))
        {
          $designid = (int)$designid['designid'];
          if($getcategory = $DB->query('SELECT DISTINCT categoryid FROM {categories} WHERE designid = %d', $designid))
          {
            while($category = $DB->fetch_array($getcategory,null,MYSQL_ASSOC))
            {
              $catids[] = $category['categoryid'];
            }
          }
        }
        else
        {
          $designid = 0;
        }

        // Process all "set all" plugins for all categories with the same design
        if($pall = (isset($_POST['plugin_set_for_all_pages'])?(array)$_POST['plugin_set_for_all_pages']:false))
        foreach($pall as $key => $value)
        {
          if(($key >= 0) && !empty($value))
          {
            foreach($catids as $cat)
            {
              $key2 = $key+1;
              // Replace the same plugin - if it is NOT the empty plugin AND
              // already exists in a different position within the category -
              // with the "empty" plugin to avoid any duplicates:
              if($plugin_selection_arr[$key] != '1')
              {
                $DB->query("UPDATE {pagesort} SET pluginid = '1'
                  WHERE (categoryid = %d) AND (displayorder <> %d) AND (pluginid = '%s')",
                  $cat, $key2, $DB->escape_string((string)$plugin_selection_arr[$key]));
              }
              // Delete an existing plugin from the given position:
              $DB->query('DELETE FROM {pagesort} WHERE (categoryid = %d) AND (displayorder = %d)', $cat, $key2);
              // Finally insert the plugin at the selected position:
              $DB->query("INSERT INTO {pagesort} (categoryid, pluginid, displayorder) VALUES (%d, '%s', %d)",
                         $cat, $DB->escape_string((string)$plugin_selection_arr[$key]), $key2);

              // clear related cache files
              if(isset($SDCache) && $SDCache->IsActive())
              {
                //SD370: added mobile
                $SDCache->delete_cacheid(($mobile?MOBILE_CACHE:'').CACHE_PAGE_PREFIX.(int)$cat);
                $cacheid = (int)floor($cat/10);
                $SDCache->delete_cacheid(CACHE_PAGE_DESIGN.($mobile?MOBILE_CACHE:'').$cacheid);
              }
            }
          }
        }
      }
    } // if $categoryid
    else
    {
      $hidden_input_fields = '';
      if(is_array($plugin_selection_arr) && count($plugin_selection_arr))
      foreach($plugin_selection_arr AS $key => $pluginid)
      {
        $hidden_input_fields .= '<input type="hidden" name="plugins[]" value="' . $pluginid . '" />';

        if(isset($_POST['plugin_set_for_all_pages'][$key]))
        {
          $hidden_input_fields .= '<input type="hidden" name="pluginall[' . $key . ']" value="' . $pluginid . '" />';
        }
      }

      $hidden_input_fields .= '<input type="hidden" name="designselection" value="' . $designid . '" />';
    }

    echo '<html>
<head>
<script type="text/javascript" language="javascript">
//<![CDATA[
var sdurl = "'.SITE_URL.'";
//]]>
</script>
<script type="text/javascript" src="'.(defined('JQUERY_GA_CDN') && strlen(JQUERY_GA_CDN)?JQUERY_GA_CDN:SITE_URL.'includes/javascript/'.JQUERY_FILENAME).'"></script>
<script type="text/javascript" src="'.SITE_URL.'includes/javascript/jquery-migrate-1.2.1.min.js"></script>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function()
{';

    if(isset($hidden_input_fields))
    {
      echo '  jQuery("#hidden_input_fields", parent.parent.document.body).html("' . addslashes($hidden_input_fields) . '");';
    }

    //SD322: replaced shadowbox with ceebox due to licensing issues
    echo '    setTimeout(function(){ parent.parent.jQuery.fn.ceebox.closebox("fast"); },500);
});
//]]>
</script>
</head>
<body>
</body>
</html>';

    // remove cache files
    if(isset($SDCache) && $SDCache->IsActive())
    {
      //SD370: added mobile
      $SDCache->delete_cacheid(CACHE_ALL_MAINSETTINGS);
      $SDCache->delete_cacheid(($mobile?MOBILE_CACHE:'').(int)$categoryid);
      $cacheid = (int)floor($categoryid/10);
      $SDCache->delete_cacheid(CACHE_PAGE_DESIGN.($mobile?MOBILE_CACHE:'').$cacheid);
    }

    exit();
  }
} // plugin position update

$plugin_selection = GetPluginsSelect('', $userinfo['usergroupid']);

// GET THEME
$DB->result_type = MYSQL_ASSOC;
$theme_arr = $DB->query_first('SELECT * FROM {skins} WHERE activated = 1');

// GET CURRENT DESIGN
if($categoryid)
{
  $DB->result_type = MYSQL_ASSOC;
  $category_arr = $DB->query_first(
    'SELECT name, '.($mobile?'mobile_':'').'designid as designid, title'.
    (defined('SD_370') ? ", IFNULL(html_class,'') html_class, IFNULL(html_id,'') html_id" : '').
    ' FROM {categories}'.
    ' WHERE categoryid = %d',$categoryid);
  if(!$designid)
  {
    $designid = (int)$category_arr['designid'];
    $category_name = '<span style="margin: 0; padding: 0; position: absolute; top: 15px; right: 15px;">'.
                     $category_arr['name'] . '</span>';
  }
  else
  {
    $category_name = '';
  }

  $DB->result_type = MYSQL_ASSOC;
  if($design_arr = $DB->query_first('SELECT d.designid, d.maxplugins, d.layout, d.design_name'.
                                    ' FROM {designs} d '.
                                    ' INNER JOIN {categories} c ON d.designid = c.'.($mobile?'mobile_':'').'designid'.
                                    ' WHERE c.categoryid = %d AND c.'.($mobile?'mobile_':'').'designid = %d',
                                    $categoryid, $designid))
  {
    $new_design = false; // user has not changed to a different design
  }
}
else
{
  $category_arr = array('name' => '', 'title' => '', 'html_class' => '', 'html_id' => '');
}

if(!isset($design_arr))
{
  $new_design = true; // user has changed to a different design, or is creating a new page

  $DB->result_type = MYSQL_ASSOC;
  if($designid)
  {
    $design_arr = $DB->query_first('SELECT designid, maxplugins, layout, design_name FROM {designs} WHERE designid = %d', $designid);
  }
  else
  {
    $design_arr = $DB->query_first('SELECT designid, maxplugins, layout, design_name FROM {designs} WHERE skinid = %d ORDER BY designid ASC LIMIT 1', $theme_arr['skinid']);
  }
}

// SD313: if design was not found, redirect to default admin page
if(!$design_arr)
{
  echo '<html>
<head>
<script type="text/javascript" language="javascript">
//<![CDATA[
var sdurl = "'.SITE_URL.'";
//]]>
</script>
<script type="text/javascript" src="'.(defined('JQUERY_GA_CDN') && strlen(JQUERY_GA_CDN)?JQUERY_GA_CDN:SITE_URL.'includes/javascript/'.JQUERY_FILENAME).'"></script>
<script type="text/javascript" src="'.SITE_URL.'includes/javascript/jquery-migrate-1.2.1.min.js"></script>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function()
{
  jQuery("#error").show().delay(2000).delay(0, function(){
    parent.parent.location.href = "'.ADMIN_DEFAULT_PAGE.'";
  });
});
//]]>
</script>
</head>
<body>
<div id="error" style="display: none; background: #ffeaef; border: 3px solid #ff829f; margin-bottom: 15px; padding: 15px;">' . AdminPhrase('pages_error_invalid_design') . '</div>
</body>
</html>';
  exit();
}

// ############################################################################
// GET PLUGINS FOR THIS PAGE
// ############################################################################

$plugin_ids_arr = $plugins_hidden = array();
$design_arr_maxplugins = $design_arr['maxplugins'];

if($categoryid AND !$new_design)
{
  $get_plugin_positions = $DB->query('SELECT pluginid FROM {pagesort'.($mobile?'_mobile':'').'}'.
                                     ' WHERE categoryid = %d ORDER BY displayorder', $categoryid);

  for($i = 0; $i < $design_arr_maxplugins; $i++)
  {
    $plugin_position_arr = $DB->fetch_array($get_plugin_positions,null,MYSQL_ASSOC);
    // invalid plugin position is defaulted to "1" (empty plugin)
    if(empty($plugin_position_arr) || ($plugin_position_arr['pluginid']=='1'))
    {
      $plugin_ids_arr[$i] = 1;
    }
    else
    {
      $pid = $plugin_position_arr['pluginid'];
      $hasAccess = true;
      if(empty($userinfo['adminaccess']))
      {
        if((substr($pid,0,1)=='c') &&
           (empty($userinfo['custompluginviewids']) || !in_array(substr($pid,1), $userinfo['custompluginviewids'])) &&
           (empty($userinfo['custompluginadminids']) || !in_array(substr($pid,1), $userinfo['custompluginadminids'])))
        {
          $hasAccess = false;
        }
        else
        if((substr($pid,0,1)!=='c') &&
           (empty($userinfo['pluginviewids']) || !in_array($pid, $userinfo['pluginviewids'])) &&
           (empty($userinfo['pluginadminids']) || !in_array($pid, $userinfo['pluginadminids'])))
        {
          $hasAccess = false;
        }
      }
      if($hasAccess)
      {
        $plugin_ids_arr[$i] = $pid;
      }
      else
      {
        $plugin_ids_arr[$i] = -1;
        $plugins_hidden[$i] = $pid;
      }
    }
  }
}
else
{
  for($i = 0; $i < $design_arr_maxplugins; $i++)
  {
    $plugin_ids_arr[$i] = 1;
  }
}

// ############################################################################
// LOAD LAYOUT
// ############################################################################
$site_url = defined('SITE_URL') ? SITE_URL : $sdurl;

//SD340: minify support
if(defined('ENABLE_MINIFY') && ENABLE_MINIFY)
{
  $menu_header = "\n".'<script type="text/javascript" src="'.$site_url.'includes/min/index.php?g=menu"></script>';
}
else
{
  $menu_header = "\n" .
    '<script type="text/javascript" src="'.$site_url.'includes/javascript/superfish/js/jquery.hoverIntent.min.js"></script>' . "\n"
  . '<script type="text/javascript" src="'.$site_url.'includes/javascript/superfish/js/supersubs.js"></script>' . "\n"
  . '<script type="text/javascript" src="'.$site_url.'includes/javascript/superfish/js/superfish.js"></script>' . "\n"
  . '<script type="text/javascript" src="'.$site_url.'includes/javascript/superfish/js/jquery.bgiframe.min.js"></script>';
}
if($mobile)
{
  #$menu_header .= '<script type="text/javascript" src="'.$site_url.'includes/javascript/jquery.mobile-1.3.2.min.js"></script>';
  $menu_header .= "\n" .'<script type="text/javascript" src="'.$site_url.'includes/javascript/jquery.hoverAccordion.min.js"></script>'.
                  "\n" . $mainsettings_mobile_menu_javascript;
}
else
{
  $menu_header .= $mainsettings_frontpage_menu_javascript;
}

// SD313: core output (HTML and CSS) now validates XHTML-valid
$cms_head_include = '
<title>' . $mainsettings_websitetitle . '</title>
<base href="' . SITE_URL . '" /><!--[if IE]></base><![endif]-->
<meta http-equiv="Content-Type" content="text/html;charset=' . CHARSET. '" />
<link rel="stylesheet" type="text/css" href="'.SITE_URL.'css.php'.($mobile ? '?mobile=1' : '').'" />
<style type="text/css">
  select.plugin_selection { max-width: 100%; min-width: 130px; }
  select.plugin_selection,
  select.plugin_selection option {
    background: #fff; color: #000; font-size: 12px !important; font-family: "Helvetica", Arial, sans-serif;
  }
  select.plugin_selection optgroup:before {
    content: attr(label);
    display: block;
    background: #000; color: #FFF;
    font-size: 13px; font-style: normal; font-weight: bold;
    padding: 4px;
  }
  input[type="checkbox"] { border: none; }
  span.set_for_all { background: #FFF; color: #272727; font-size: 10px; font-family: "Helvetica", Arial, sans-serif; }
</style>
<script type="text/javascript" language="javascript">
//<![CDATA[
var sdurl = "'.SITE_URL.'";
//]]>
</script>
<script type="text/javascript" src="'.(defined('JQUERY_GA_CDN') && strlen(JQUERY_GA_CDN)?JQUERY_GA_CDN:SITE_URL.'includes/javascript/'.JQUERY_FILENAME).'"></script>
<script type="text/javascript" src="'.SITE_URL.'includes/javascript/jquery-migrate-1.2.1.min.js"></script>
'.$menu_header.'
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function(){
  $(document).delegate("a","click", function(e){ e.preventDefault; return false; });
  var plugin_ids_arr = new Array("' . implode('","', $plugin_ids_arr) . '");
  /* Fill selected items */
  $(".plugin_selection").each(function() {
    if(plugin_ids_arr.length > 0)
      $(this).val(plugin_ids_arr.shift());
    else
      $(this).val(1);
  });
});
//]]>
</script>
';

/* SD362: Testcode (commented out): jQuery "chosen" plugin

  <link rel="stylesheet" type="text/css" href="'.SITE_URL.'includes/css/chosen.css" />
  <script type="text/javascript" src="'.SITE_URL.'includes/javascript/chosen.jquery.min.js"></script>

  // SD362: apply "Chosen" to selects
  if(typeof $.fn.chosen !== "undefined") {
    $(".plugin_selection").chosen({
      disable_search: true, inherit_select_classes: false,
      max_selected_options: 1, search_contains: false,
      group_search: false, width: "170px"
    });
  }
*/

$extra = (!empty($error_message) ? $error_message : '') . '
<form id="plugin_selection_form" action="'.ADMIN_PATH.'/page_plugin_selection_content.php?mobile='.($mobile?'1':'0').
  '&amp;categoryid='.$categoryid.'&amp;designid='.$design_arr['designid'].'" method="post">
' . PrintSecureToken();

// Below variable is used in functions_frontend for menu generation:
$root_parent_categoryid = GetRootCategoryid($categoryid); // SD321: TODO: caching
$skinvars = array();
$skinvars['MOBILENAVIGATION'] = '';
$skinvars['MOBILE_HEADER'] = '';
$skinvars['MOBILE_FOOTER'] = '';
$skinvars['MOBILE_RETURN'] = 'xxx';
$skinvars['BREADCRUMB'] = '';
$skinvars['NAVIGATION'] = '';
$skinvars['NAVIGATION_BOTTOM_ONLY'] = '';
$skinvars['NAVIGATION_BOTTOM_ONLY_NOMENU'] = '';
$skinvars['NAVIGATION_TOPLEVEL'] = '';
$skinvars['NAVIGATION_TOPLEVEL_NOMENU'] = '';
$skinvars['NAVIGATION_TOP_ONLY'] = '';
$skinvars['NAVIGATION_TOP_ONLY_NOMENU'] = '';
$skinvars['SUBNAVIGATION'] = '';

$full_layout = var_export(array($design_arr['layout'],$theme_arr['header'],$theme_arr['footer']),true);
if(stripos($full_layout, '[MOBILENAVIGATION]') !== false)
{
  $skinvars['MOBILENAVIGATION'] = CreateMobileMenu(0);
}
if(stripos($full_layout, '[NAVIGATION]') !== false)
{
  $skinvars['NAVIGATION'] = CreateMenu(0, false, false, true, false);
}
if(stripos($full_layout, '[SUBNAVIGATION]') !== false)
{
  $skinvars['SUBNAVIGATION'] = CreateMenu($categoryid);
}
//SD344: new skin var "SIBLINGPAGES": display all sibiling pages
// of the current page (if current page has a parent)
$skinvars['SIBLINGPAGES'] = '';
if((stripos($full_layout, '[SIBLINGPAGES]') !== false) &&
   !empty($pages_md_arr[$categoryid]))
{
  $skinvars['SIBLINGPAGES'] = CreateMenu($pages_md_arr[$categoryid]['parentid']);
}
if(stripos($full_layout, '[BREADCRUMB]') !== false)
{
  $skinvars['BREADCRUMB'] = CreateBreadcrumb($categoryid);
}
if(stripos($full_layout, '[NAVIGATION-TOPLEVEL]') !== false)
{
  $skinvars['NAVIGATION_TOPLEVEL'] = CreateMenu(0, true, false, true, false);
}
if(stripos($full_layout, '[NAVIGATION-TOPLEVEL-NOMENU]') !== false)
{
  $skinvars['NAVIGATION_TOPLEVEL_NOMENU'] = CreateMenu(0, true, true, true, false);
}
if(stripos($full_layout, '[NAVIGATION-TOP-ONLY]') !== false)
{
  $skinvars['NAVIGATION_TOP_ONLY'] = CreateMenu(0, true, false, true, false);
}
if(stripos($full_layout, '[NAVIGATION-TOP-ONLY-NOMENU]') !== false)
{
  $skinvars['NAVIGATION_TOP_ONLY_NOMENU'] = CreateMenu(0, true, true, true, false);
}
if(stripos($full_layout, '[NAVIGATION-BOTTOM-ONLY]') !== false)
{
  $skinvars['NAVIGATION_BOTTOM_ONLY'] = CreateMenu(0, true, false, false, true);
}
if(stripos($full_layout, '[NAVIGATION-BOTTOM-ONLY-NOMENU]') !== false)
{
  $skinvars['NAVIGATION_BOTTOM_ONLY_NOMENU'] = CreateMenu(0, true, true, false, true);
}
unset($full_layout);

//SD344: respect branding free switch
$copyright = $mainsettings_copyrighttext;
if(!defined('BRANDING_FREE') && empty($mainsettings_bfo))
{
  $copyright .= ' <a class="copyright" href="' . PRGM_WEBSITE_LINK . '" title="' .
                $sdlanguage['website_powered_by'] . ' ' . PRGM_NAME .
                '">' . $sdlanguage['website_powered_by'] . ' ' . PRGM_NAME . '</a>';
}

// Remove special tags, like for header/footer, plugin and plugin name:
$theme_arr['header'] = str_replace(
                         array('[CMS_HEAD_INCLUDE]',
                               '[CMS_HEAD_NOMENU]',
                               '[BREADCRUMB]',
                               '[NAVIGATION]',
                               '[MOBILENAVIGATION]',
                               '[MOBILE_RETURN]',
                               '[NAVIGATION-TOPLEVEL]',
                               '[NAVIGATION-TOP-ONLY]',
                               '[NAVIGATION-TOPLEVEL-NOMENU]',
                               '[NAVIGATION-TOP-ONLY-NOMENU]',
                               '[NAVIGATION-BOTTOM-ONLY]',
                               '[NAVIGATION-BOTTOM-ONLY-NOMENU]',
                               '[SUBNAVIGATION]',
                               '[SIBLINGPAGES]',    //SD344
                               '<plugin_name>',
                               '</plugin_name>',
                               '<plugin>',
                               '</plugin>',
                               '[LOGO]',
                               '[COPYRIGHT]',
                               '[ARTICLE_TITLE]',   //SD362
                               '[PAGE_TITLE]',
                               '[PAGE_NAME]',
                               '[PAGE_HTML_CLASS]', //SD362
                               '[PAGE_HTML_ID]'),   //SD362
                         array($cms_head_include,
                               $cms_head_include,
                               $skinvars['BREADCRUMB'],
                               $skinvars['NAVIGATION'],
                               $skinvars['MOBILENAVIGATION'],
                               $skinvars['MOBILE_RETURN'],
                               $skinvars['NAVIGATION_TOPLEVEL'],
                               $skinvars['NAVIGATION_TOP_ONLY'],
                               $skinvars['NAVIGATION_TOPLEVEL_NOMENU'],
                               $skinvars['NAVIGATION_TOP_ONLY_NOMENU'],
                               $skinvars['NAVIGATION_BOTTOM_ONLY'],
                               $skinvars['NAVIGATION_BOTTOM_ONLY_NOMENU'],
                               $skinvars['SUBNAVIGATION'],
                               $skinvars['SIBLINGPAGES'],
                               '',
                               '',
                               '',
                               '',
                               $mainsettings_currentlogo,
                               $copyright,
                               '',
                               $category_arr['title'],
                               $category_arr['name'],
                               $category_arr['html_class'],
                               $category_arr['html_id']),
                          $theme_arr['header']);

$theme_arr['footer'] = str_replace(
                         array('[BREADCRUMB]',
                               '[NAVIGATION]',
                               '[MOBILENAVIGATION]',
                               '[MOBILENAVIGATION]',
                               '[NAVIGATION-TOPLEVEL]',
                               '[NAVIGATION-TOP-ONLY]',
                               '[NAVIGATION-TOPLEVEL-NOMENU]',
                               '[NAVIGATION-TOP-ONLY-NOMENU]',
                               '[NAVIGATION-BOTTOM-ONLY]',
                               '[NAVIGATION-BOTTOM-ONLY-NOMENU]',
                               '[SUBNAVIGATION]',
                               '<plugin_name>',
                               '</plugin_name>',
                               '<plugin>',
                               '</plugin>',
                               '[LOGO]',
                               '[COPYRIGHT]',
                               '[ARTICLE_TITLE]',   //SD362
                               '[PAGE_TITLE]',
                               '[PAGE_NAME]',
                               '[PAGE_HTML_CLASS]', //SD362
                               '[PAGE_HTML_ID]'),   //SD362
                         array($skinvars['BREADCRUMB'],
                               $skinvars['NAVIGATION'],
                               $skinvars['MOBILENAVIGATION'],
                               $skinvars['MOBILE_RETURN'],
                               $skinvars['NAVIGATION_TOPLEVEL'],
                               $skinvars['NAVIGATION_TOP_ONLY'],
                               $skinvars['NAVIGATION_TOPLEVEL_NOMENU'],
                               $skinvars['NAVIGATION_TOP_ONLY_NOMENU'],
                               $skinvars['NAVIGATION_BOTTOM_ONLY'],
                               $skinvars['NAVIGATION_BOTTOM_ONLY_NOMENU'],
                               $skinvars['SUBNAVIGATION'],
                               '',
                               '',
                               '',
                               '',
                               $mainsettings_currentlogo,
                               $copyright,
                               '',
                               $category_arr['title'],
                               $category_arr['name'],
                               $category_arr['html_class'],
                               $category_arr['html_id']),
                          $theme_arr['footer']);

$design_arr['layout'] = str_replace(
                         array('[HEADER]', '[FOOTER]',
                               '[MOBILE_HEADER]', '[MOBILE_FOOTER]',
                               '[CMS_HEAD_INCLUDE]', '[CMS_HEAD_NOMENU]',
                               '[BREADCRUMB]',
                               '[NAVIGATION]',
                               '[MOBILENAVIGATION]',
                               '[MOBILEN_RETURN]',
                               '[NAVIGATION-TOPLEVEL]',
                               '[NAVIGATION-TOP-ONLY]',
                               '[NAVIGATION-TOPLEVEL-NOMENU]',
                               '[NAVIGATION-TOP-ONLY-NOMENU]',
                               '[NAVIGATION-BOTTOM-ONLY]',
                               '[NAVIGATION-BOTTOM-ONLY-NOMENU]',
                               '[SUBNAVIGATION]',
                               '<plugin_name>',
                               '</plugin_name>',
                               '<plugin>',
                               '</plugin>',
                               '[LOGO]',
                               '[COPYRIGHT]',
                               '[ARTICLE_TITLE]',   //SD362
                               '[PAGE_TITLE]',
                               '[PAGE_NAME]',
                               '[PAGE_HTML_CLASS]', //SD362
                               '[PAGE_HTML_ID]'),   //SD362
                         array($theme_arr['header'], $theme_arr['footer'],
                               $theme_arr['mobile_header'], $theme_arr['mobile_footer'],
                               $cms_head_include, $cms_head_include,
                               $skinvars['BREADCRUMB'],
                               $skinvars['NAVIGATION'],
                               $skinvars['MOBILENAVIGATION'],
                               $skinvars['MOBILE_RETURN'],
                               $skinvars['NAVIGATION_TOPLEVEL'],
                               $skinvars['NAVIGATION_TOP_ONLY'],
                               $skinvars['NAVIGATION_TOPLEVEL_NOMENU'],
                               $skinvars['NAVIGATION_TOP_ONLY_NOMENU'],
                               $skinvars['NAVIGATION_BOTTOM_ONLY'],
                               $skinvars['NAVIGATION_BOTTOM_ONLY_NOMENU'],
                               $skinvars['SUBNAVIGATION'],
                               '',
                               '',
                               '',
                               '',
                               $mainsettings_currentlogo,
                               $copyright,
                               '',
                               $category_arr['title'],
                               $category_arr['name'],
                               $category_arr['html_class'],
                               $category_arr['html_id']),
                          $design_arr['layout']);

//SD371: invalidate embedded forms, otherwise plugin slots in footer won't
// be covered and stored
$design_arr['layout'] = preg_replace('%</form>%', '<!-- form -->', $design_arr['layout']);
$design_arr['layout'] = preg_replace('%</body>%', '</form></body>', $design_arr['layout']);

// explode the layout for each plugin
$layout_arr = explode('[PLUGIN]', $design_arr['layout']);

// display layout
ob_implicit_flush(0);
for($i = 0; $i < count($layout_arr); $i++)
{
  ob_start();
  $layout_arr[$i] = ' ?>' . $layout_arr[$i] . '<?php ';
  eval($layout_arr[$i]);
  $out = ob_get_contents();
  ob_end_clean();
  $out = preg_replace('%<body(.*)>%', ('<body$1>' . $extra), $out, 1);
  echo $out;
  if(!empty($plugin_ids_arr[$i]))
  {
    //SD342: check user's (custom) plugin permissions: if no access, then "lock" the plugin slot
    // (=no select box)
    $pid = (string)$plugin_ids_arr[$i];
    $hasAccess = true;
    if(empty($userinfo['adminaccess']) && ($pid != '1') && ($pid != '-1'))
    {
      if((substr($pid,0,1)=='c') && empty($userinfo['adminaccess']) &&
         (empty($userinfo['custompluginviewids']) || !in_array(substr($pid,1), $userinfo['custompluginviewids'])) &&
         (empty($userinfo['custompluginadminids']) || in_array(substr($pid,1), $userinfo['custompluginadminids'])))
      {
        $hasAccess = false;
      }
      else
      if((substr($pid,0,1)!=='c') && empty($userinfo['adminaccess']) &&
         (empty($userinfo['pluginviewids']) || !in_array($pid, $userinfo['pluginviewids'])) &&
         (empty($userinfo['pluginadminids']) || !in_array($pid, $userinfo['pluginadminids'])))
      {
        $hasAccess = false;
      }
    }
    if($pid == '-1')
    {
      echo "\n    ".'<input type="hidden" name="plugin_selection_arr['.$i.']" value="'.
      (isset($plugins_hidden[$i])?$plugins_hidden[$i]:1).'" />
      <input value="'.$plugin_names[$plugins_hidden[$i]].'" disabled="disabled" style="background-color:#f0f0f0" />';
    }
    else
    if($hasAccess || !empty($userinfo['adminaccess']))
    {
      if($mobile)
      {
        // No "set for all" option for mobile!
        echo "\n    ".'<select id="plugsel'.$i.'" class="plugin_selection" name="plugin_selection_arr['.$i.']">'.$plugin_selection;
      }
      else
      {
        echo "\n    ".'<select id="plugsel'.$i.'" class="plugin_selection" name="plugin_selection_arr['.$i.']">'.$plugin_selection . '
        <input name="plugin_set_for_all_pages['.$i.']" type="checkbox" value="1" />&nbsp;<span class="set_for_all">' .
        AdminPhrase('pages_set_for_all') . '</span>';
      }
    }
    else
    {
      echo "\n    ".'<input type="hidden" name="plugin_selection_arr['.$i.']" value="'.$pid.'" /><input value="'.$plugin_names[$pid].'" disabled="disabled" />';
    }
  }
}
