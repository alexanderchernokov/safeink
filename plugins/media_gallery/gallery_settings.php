<?php
// #############################################################
// SD360: Ajax pre-run
// SD360: added admin instant status change of media/image files
// SD362: added admin media url check
// #############################################################
$standalone = false;
if(!defined('ROOT_PATH'))
{
  $standalone = true;
  // Assume regular admin menu request, normal bootstrap required:
  define('IN_PRGM', true);
  define('IN_ADMIN', true);
  define('ROOT_PATH', '../../');
  require(ROOT_PATH . 'includes/init.php');
  if(!Is_Ajax_Request())
  {
    exit('ERROR: '.AdminPhrase('common_page_access_denied'));
  }
}

if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

if(!$plugin_folder = sd_GetCurrentFolder(__FILE__))
{
  return false;
}

function MG_ajaxGalleryChangeImageStatus() //declared BEFORE ajax operation!
{
  if(!CheckFormToken()) return false;

  global $DB, $plugin_names, $GalleryBase;

  if(!headers_sent()) header('Content-Type: text/html; charset='.SD_CHARSET);

  $pluginid    = Is_Valid_Number(GetVar('pluginid', null, 'whole_number'),0,200,99999);
  $imageid     = Is_Valid_Number(GetVar('imageid', null, 'whole_number'),0,1,999999);
  $imagestatus = Is_Valid_Number(GetVar('imagestatus', null, 'natural_number'),0,0,1);

  //Sanity checks:
  if(empty($imageid) || !isset($imagestatus) || !isset($pluginid) ||
     ($pluginid != $GalleryBase->pluginid) || !isset($plugin_names[$pluginid]))
  {
    $DB->close();
    exit('ERROR: '.AdminPhrase('common_page_access_denied'));
  }

  $DB->result_type = MYSQL_ASSOC;
  if($row = $DB->query_first('SELECT imageid, activated'.
                             ' FROM '.$GalleryBase->images_tbl.
                             ' WHERE imageid = %d',$imageid))
  {
    // Reset 2nd bit and OR with 2 or 0 to make sure the new status is set:
    if($row['activated'] != $imagestatus)
    $DB->query('UPDATE '.$GalleryBase->images_tbl.
               ' SET activated = %d WHERE imageid = %d',
               $imagestatus, $imageid);
    $admin_phrases = LoadAdminPhrases(2, $pluginid);
    echo $admin_phrases['message_image_updated'];
  }
  else
  {
    if(!headers_sent()) header("HTTP/1.0 404 Not Found");
    echo 'ERROR: Invalid reference!';
  }
  $DB->close();
  exit();

} //MG_ajaxGalleryChangeImageStatus


// ############################################################################
// GET ACTION
// ############################################################################
$action = GetVar('action', 'displayimages', 'string');

//SD362: require files only when needed
if(!Is_Ajax_Request() || ($action=='setimagestatus'))
{
  // INCLUDE CORE SD MEDIA CLASS
  require_once(SD_INCLUDE_PATH.'class_sd_media.php');
  require_once(SD_INCLUDE_PATH.'class_sd_tags.php');
  // INCLUDE GALLERY BASE CLASS
  require_once(ROOT_PATH.'plugins/'.$plugin_folder.'/gallery_lib.php');

  $GalleryBase = new GalleryBaseClass($plugin_folder);
  SD_Image_Helper::$ImagePath = $GalleryBase->IMAGEPATH;
}

// *** SD360: process known Ajax request(s) ***
if(Is_Ajax_Request())
{
  switch($action)
  {
    case 'setimagestatus': MG_ajaxGalleryChangeImageStatus(); break; //SD360
    default:
      if(!headers_sent()) header("HTTP/1.0 404 Not Found");
      echo '0';
  }
  $DB->close();
  exit();
}

// ############################################################################

$customsearch = GetVar('customsearch', 0, 'bool');
$clearsearch  = GetVar('clearsearch',  0, 'bool');

// Restore previous search array from cookie
$search_cookie_name = '_'.$pluginid.'_images_search';
$search_cookie = isset($_COOKIE[COOKIE_PREFIX.$search_cookie_name]) ? $_COOKIE[COOKIE_PREFIX.$search_cookie_name] : false;

//SD360: keep track of images listing page
$page_cookie_name = '_'.$pluginid.'_page';
$gallery_page = !isset($_GET['page'])?false:Is_Valid_Number(GetVar('page', 1, 'whole_number',false,true),1,1,99999999);
$page_cookie  = isset($_COOKIE[COOKIE_PREFIX.$page_cookie_name]) ? $_COOKIE[COOKIE_PREFIX.$page_cookie_name] : false;
if(($gallery_page===false) && ($page_cookie !== false))
{
  $gallery_page = Is_Valid_Number($page_cookie,1,1,99999999);
}
if($gallery_page===false)
{
  $gallery_page = 1;
}

if($clearsearch)
{
  $search_cookie = false;
  $customsearch  = false;
}

if($customsearch)
{
  $sectionid = GetVar('sectionid', 0, 'whole_number');
  $search = array(
    'image'     => GetVar('searchimage',   '',    'string',  true, false),
    'status'    => GetVar('searchstatus',  'all', 'string',  true, false),
    'width'     => GetVar('searchwidth',   'all', 'integer', true, false),
    'sectionid' => GetVar('searchsection', $sectionid, 'whole_number', true, false),
    'author'    => GetVar('searchauthor',  '',    'string',  true, false),
    'limit'     => GetVar('searchlimit',   10,    'whole_number', true, false),
    'sorting'   => GetVar('searchsorting', 0,     'integer', true, false),
    'tag'       => GetVar('searchtag',     '',    'string',  true, false) #SD362
  );
  if(empty($search['tag'])) $search['tag'] = GetVar('tag', '', 'string', false, true);
  // Note: cookie is stored in header.php!
}
else
{
  if($search_cookie !== false)
  {
    $search = unserialize(base64_decode(($search_cookie)));
  }
}
unset($customsearch,$clearsearch);

if(empty($search) || !is_array($search))
{
  $search = array('image'     => '',
                  'tag'       => '',
                  'status'    => 'all',
                  'width'     => 'all',
                  'sectionid' => 0,
                  'author'    => '',
                  'limit'     => 10,
                  'sorting'   => '',
                  'tag'       => '',
                  );

  // Remove search params cookie
  //sd_CreateCookie('_images_search', '');
}

$search['image']    = isset($search['image'])   ? (string)$search['image'] : '';
$search['tag']      = isset($search['tag'])     ? (string)$search['tag'] : '';
$search['status']   = isset($search['status'])  ? (string)$search['status'] : 'all';
$search['width']    = isset($search['width'])   ? (string)$search['width'] : 'all';
$search['author']   = isset($search['author'])  ? (string)$search['author'] : '';
$search['limit']    = Is_Valid_Number($search['limit'], 10, 5, 999);
$search['sorting']  = isset($search['sorting']) ? Is_Valid_Number($search['sorting'],0,0,5) : 0;
$search['tag']      = isset($search['tag'])     ? (string)$search['tag'] : '';

echo '
<img src="' .ADMIN_IMAGES_FOLDER. 'ceetip_arrow.png" alt="" style="display:none;" />
';

//SD362: remove orphaned tags for images
$DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'tags
            WHERE pluginid = '.(int)$pluginid.'
            AND tagtype = 0
            AND NOT EXISTS(SELECT 1 FROM '.PRGM_TABLE_PREFIX.'p'.(int)$pluginid.'_images
                           WHERE imageid = '.PRGM_TABLE_PREFIX.'tags.objectid)');

// ###########################################################################
// ########### Display sections for either SELECT tag or UL/LI list ##########
// This works for both the frontpage and the admin panel by using different links.

function GalleryPrintChildSections($config_arr)
/*
  Do NOT change any default values of parameters!
  parentid               required
  selectedSectionId      null
  displayCounts          false
  parentText             ''
  asMenu                 false
  menuAction             ''
*/
{
  global $DB, $GalleryBase, $refreshpage;

  // If no valid array is passed, do nothing and return:
  if(empty($config_arr) || !is_array($config_arr)) return;

  static $default_array = array(
            'selectedSectionId' => '',
            'excludedSectionId' => '',
            'displayCounts'     => false,
            'parentText'        => '',
            'asMenu'            => false,
            'menuAction'        => '',
            'li_attrib'         => ''
          );

  $config_arr = array_merge($default_array, $config_arr);

  // Extract array keys as "$conf_*" variables:
  extract($config_arr, EXTR_PREFIX_ALL, 'conf');

  $excludeSql = '';
  if(!empty($conf_excludedSectionId) && !is_array($conf_excludedSectionId))
  {
    $conf_excludedSectionId = array($conf_excludedSectionId);
    $excludeSql = ' AND NOT (sectionid IN ('.implode(',',$conf_excludedSectionId).'))';
  }
  if($getsections = $DB->query('SELECT sectionid, name'.
                               ' FROM '.$GalleryBase->sections_tbl.
                               ' WHERE parentid = %d'.
                               $excludeSql.
                               ' ORDER BY datecreated DESC',$conf_parentid))
  {
    while($section = $DB->fetch_array($getsections,null,MYSQL_ASSOC))
    {
      $sid = (int)$section['sectionid'];
      $name = $section['name'];

      if(strlen($name) <= 0)
      {
        if(empty($conf_parentid))
        {
          $name = '&lt;Root&gt;';
        }
        else
        {
          $name = '&lt; ' . $GalleryBase->language['untitled'] . ' ' . $sid . '&gt;';
        }
      }

      $section_count = false;
      if(!$conf_asMenu && $conf_displayCounts)
      {
        echo '<option value="'.$section['sectionid'].'">'.
             htmlspecialchars($conf_parentText.$name).'</option>';
      }
      else
      {
        // Display as menu?
        if($conf_asMenu && !empty($conf_menuAction))
        {
          $DB->result_type = MYSQL_ASSOC;
          $section_count = $DB->query_first('SELECT COUNT(*) sec_count'.
                                            ' FROM '.$GalleryBase->sections_tbl.
                                            ' WHERE parentid = %d',
                                            $sid);
          if(defined('IN_ADMIN'))
          {
            echo '<li><a href="'.$refreshpage.$conf_menuAction.'&amp;sectionid='.$sid.'">'.$name.'</a>';
          }
          else
          {
            echo '<li><a href="'.RewriteLink('index.php'.$conf_menuAction.$GalleryBase->pre.'_sectionid='.$sid).'">'.$name.'</a>';
          }
          if(!empty($section_count['sec_count'])) echo '<ul>';
        }
        else
        {
          echo '<option value="'.$section['sectionid'].'" '.
          (is_array($conf_selectedSectionId) && @in_array($sid, $conf_selectedSectionId) ? 'selected="selected"' : '').'>'.
          htmlspecialchars($conf_parentText.$name).'</option>';
        }
      }

      // Recursive call for current section
      $config_arr['parentid'] = $sid;
      $config_arr['parentText'] = (!$conf_asMenu ? $conf_parentText.$name.'\\' : '');
      GalleryPrintChildSections($config_arr);

      if($conf_asMenu)
      {
        if(!empty($section_count['sec_count'])) echo '</ul>';
        echo '</li>';
      }
    }
    $DB->free_result($getsections);
  }
} //GalleryPrintChildSections


function GalleryPrintSectionSelection($config_arr)
/*
"$config_arr" may contain the following keys (in any order):
  Key                    Default
  --------------------------------------------
  formId                 required, no default
  selectedSectionId      ''
  displayCounts          true
  displayOffline         false
  showmultiple           true
  asMenu                 false
  menuAction             ''
  size                   5
  extraStyle             ''
  excludeSectionId       0

  Any missing key is defaulted to above mentioned values.

Example call:
    GalleryPrintSectionSelection(
      array('formId'            => DownloadManagerTools::GetVar('HTML_PREFIX').'_sectionid',
            'selectedSectionId' => $sectionid,
            'displayCounts'     => false,
            'displayOffline'    => false));
*/
{
  // If no valid array is passed, do nothing and return:
  if(empty($config_arr) || !is_array($config_arr)) return;

  global $GalleryBase;

  static $default_array = array(
                            'selectedSectionId' => '',
                            'excludedSectionId' => 0,
                            'displayCounts'     => true,
                            'displayOffline'    => false,
                            'showmultiple'      => true,
                            'asMenu'            => false,
                            'menuAction'        => '',
                            'ul_attrib'         => '',
                            'li_attrib'         => '',
                            'size'              => 5,
                            'extraStyle'        => '',
                            'parent_id'         => 0
                          );

  $config_arr = array_merge($default_array, $config_arr);

  // Extract array keys as "$conf_*" variables:
  extract($config_arr, EXTR_PREFIX_ALL, 'conf');

  // Form's ID is required if not displayed as menu:
  if(empty($conf_formId) && empty($conf_asMenu)) return;

  if(!empty($conf_asMenu))
  {
    echo '<ul class="dropdown-menu" role="menu" '.$conf_ul_attrib.'>';
  }
  else
  {
    echo "
    <select id=\"$conf_formId\" name=\"$conf_formId\" ".$conf_extraStyle. (empty($conf_showmultiple) ? '' : ' size ="'.$conf_size.'" multiple="multiple"').'>
    ';
  }

  if(!empty($conf_selectedSectionId) && !is_array($conf_selectedSectionId))
  {
    $conf_selectedSectionId = array($conf_selectedSectionId);
  }

  $config_sub_arr = array('parentid'          => $conf_parent_id,
                          'selectedSectionId' => $conf_selectedSectionId,
                          'excludedSectionId' => (isset($conf_excludedSectionId)?$conf_excludedSectionId:-1),
                          'displayCounts'     => $conf_displayCounts,
                          'parentText'        => '',
                          'li_attrib'         => $conf_li_attrib,
                          'asMenu'            => $conf_asMenu,
                          'menuAction'        => $conf_menuAction);
  GalleryPrintChildSections($config_sub_arr);

  // Show Offline files?
  if(!$conf_asMenu && $conf_displayOffline)
  {
    global $DB;
    if($offlinerows = $DB->query_first('SELECT COUNT(imageid)'.
                                       ' FROM '.$GalleryBase->images_tbl.
                                       ' WHERE IFNULL(activated,0) = 0'))
    {
      echo '
      <option value="-1"'.(is_array($conf_selectedSectionId) && @in_array(-1, $conf_selectedSectionId) ? ' selected="selected"' : '').
           '">'.AdminPhrase('offline').' (' . $offlinerows[0] . ')</option>';
    }
  }

  echo empty($conf_asMenu) ? '</select>' : '</ul>';

} //GalleryPrintSectionSelection

// ############################################################################
// DISPLAY MEDIA GALLERY IN-PLUGIN ADMIN MENU
// ############################################################################

$loadwysiwyg = $load_wysiwyg = 0;
$refreshpage = str_replace('&amp;load_wysiwyg=1','',$refreshpage).
               (!empty($mainsettings['enablewysiwyg']) ? '&amp;load_wysiwyg=0' : '');

$DB->result_type = MYSQL_ASSOC;
$root = $DB->query_first('SELECT s.sectionid, s.name,'.
                           ' (SELECT COUNT(*) FROM '.$GalleryBase->sections_tbl.' s2'.
                           '  WHERE s2.parentid = s.sectionid) sec_count'.
                         ' FROM '.$GalleryBase->sections_tbl.' s'.
                         ' WHERE s.parentid = 0 ORDER BY s.sectionid LIMIT 0,1');

$rootlink = '<a href="view_plugin.php?pluginid='.$GalleryBase->pluginid.'&amp;load_wysiwyg=0'.
            '&amp;action=displaysectionform&amp;sectionid='.$root['sectionid'].'" class="dropdown-toggle" data-toggle="dropdown">'.
            AdminPhrase('edit_section').': '.
            strip_alltags($root['name']).' <span class="caret"></span></a>';
echo '
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function() {
var galmenu = jQuery("ul#galmenu");
jQuery(".navigation").css({"visibility": "visible"});
if(jQuery(galmenu).length) {
  jQuery("ul#galmenu ul#gal_filter").css({"visibility": "visible"});
  jQuery("ul#galmenu ul#gal_sections").css({"visibility": "visible"});
  jQuery("div.row li",galmenu).each(function(){
    uls = jQuery(this).find("ul");
    if(uls.length===0){
      jQuery(this).css({"overflow-x":"hidden","overflow-y":"hidden","height":"24px"});
    }
  });
}
});
//]]>
</script>';

// ############################################################################
// DISPLAY MENU
// ############################################################################

echo '
<ul class="nav nav-pills no-padding-left no-margin-left">
<li class="dropdown">
	<a class="dropdown-toggle " data-toggle="dropdown" href="#">'.AdminPhrase('images').' <span class="caret"></span></a>
	<ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
  		<li><a href="' . $refreshpage . '">'.AdminPhrase('view_images').'</a></li>
  		<li><a href="' . $refreshpage . '&amp;action=displaysectionform">'.AdminPhrase('create_section').'</a></li>
 	</ul>
</li>
  ';

if(!empty($root['sec_count']) && ($root['sec_count']>0))
{
  echo '
  <li class="dropdown">
  	<a class="dropdown-toggle" data-toggle="dropdown" href="#" onclick="return false;">'.AdminPhrase('filter_title').' <span class="caret"></span></a>';

  GalleryPrintSectionSelection(
    array('formId'        => '',
          'displayCounts' => false,
          'showmultiple'  => false,
          'asMenu'        => true,
          'parent_id'     => 1,
          'ul_attrib'     => ' ',
          'menuAction'    => '&amp;action=displayimages&amp;customsearch=1&amp;load_wysiwyg=0'));

}
echo '</li>
<li class="dropdown">
  '.$rootlink;

GalleryPrintSectionSelection(
  array('formId'        => '',
        'displayCounts' => false,
        'showmultiple'  => false,
        'asMenu'        => true,
        'parent_id'     => 1,
        'ul_attrib'     => ' ',
        'menuAction'    => '&amp;action=displaysectionform&amp;load_wysiwyg=0'));
echo '</li>
<li class="dropdown">
  <a class="dropdown-toggle" data-toggle="dropdown" href="' . $refreshpage . '&amp;action=displayimages'.(empty($sectionid)?'':'&amp;customsearch=1&amp;searchsection='.$sectionid).'">'.AdminPhrase('media_files').' <span class="caret"></span></a>
    <ul class="dropdown-menu">
    <li><a href="' . $refreshpage . '&amp;action=displayimageform'.(empty($sectionid)?'':'&amp;sectionid='.$sectionid).'">'.AdminPhrase('add_media').'</a></li>
    <li><a href="' . $refreshpage . '&amp;action=displaybatchimportform">'.AdminPhrase('import_images').'</a></li>
    <li><a href="' . $refreshpage . '&amp;action=displaybatchuploadform">'.AdminPhrase('batch_upload').'</a></li>
    </ul>
</li>
<li class="dropdown">
  <a class="dropdown-toggle" data-toggle="dropdown" href="' . $refreshpage . '&amp;action=displaysettings">'.AdminPhrase('view_settings').' <span class="caret"></span></a>
    <ul class="dropdown-menu">
    <li><a href="' . $refreshpage . '&amp;action=displaysettings&amp;settingspage=1">'.AdminPhrase('admin_options').'</a></li>
    <li><a href="' . $refreshpage . '&amp;action=displaysettings&amp;settingspage=2">'.AdminPhrase('upload_settings').'</a></li>
    <li><a href="' . $refreshpage . '&amp;action=displaysettings&amp;settingspage=3">'.AdminPhrase('media_gallery_settings').'</a></li>
    <li><a href="' . $refreshpage . '&amp;action=displaysettings&amp;settingspage=4">'.AdminPhrase('midsize_settings').'</a></li>
    <li><a href="' . $refreshpage . '&amp;action=displaysettings&amp;settingspage=5">'.AdminPhrase('thumbnail_settings').'</a></li>
    <li><a href="' . $refreshpage . '&amp;action=displaysettings&amp;settingspage=6">'.AdminPhrase('sections_settings').'</a></li>
    </ul>
</li>
 </ul>
 <div class="clearfix"></div>
<div class="space=30"></div>
';

// ############################################################################
// CHECK FOLDER PERMISSIONS
// ############################################################################

$errors = array();

if(!is_dir($GalleryBase->UPLOADPATH))
{
  $errors[] = $GalleryBase->UPLOADPATH . ' ' . AdminPhrase('folder_not_found');
}
else
if(!is_writable($GalleryBase->UPLOADPATH))
{
  $errors[] = $GalleryBase->UPLOADPATH . ' ' . AdminPhrase('folder_not_writable');
}

if(!is_dir($GalleryBase->IMAGEPATH))
{
  $errors[] = $GalleryBase->IMAGEPATH . ' ' . AdminPhrase('folder_not_found');
}
else
if(!is_writable($GalleryBase->IMAGEPATH))
{
  $errors[] = $GalleryBase->IMAGEPATH . ' ' . AdminPhrase('folder_not_writable');
}

if(!empty($errors))
{
  DisplayMessage($errors, true);
}


// ############################################################################
// MAIN SETTINGS CLASS TO ENCLOSE FUNCTIONS
// ############################################################################
class MediaGallerySettings
{

// ############################################################################
// DELETE SECTION CACHE FILE
// ############################################################################

function DeleteSectionCache($sectionid)
{
  global $SDCache, $GalleryBase;

  if(!empty($SDCache) && ($sectionid > 0))
  {
    $SDCache->delete_cacheid('mg_cache_'.$GalleryBase->pluginid.'_'.$sectionid);
    $SDCache->delete_cacheid('mg_cache_seo_'.$GalleryBase->pluginid.'_'.$sectionid);
  }
} //DeleteSectionCache


// ############################################################################
// DELETE SECTION
// ############################################################################

function DeleteSection($sectionid)
{
  global $DB, $SDCache, $GalleryBase;

  if(empty($GalleryBase->sections_tbl) || ($sectionid < 2))
  {
    return;
  }
  if(!$section = $DB->query_first('SELECT sectionid, parentid'.
                                  ' FROM '.$GalleryBase->sections_tbl.
                                  ' WHERE sectionid = %d',
                                  $sectionid))
  {
    return false;
  }

  //SD360: remove cache files for sub-sections
  if(!empty($SDCache))
  {
    $this->DeleteSectionCache($section['parentid']);
    if($getrows = $DB->query('SELECT sectionid'.
                             ' FROM '.$GalleryBase->sections_tbl.
                             ' WHERE parentid = %d',
                             $sectionid))
    {
      while($row = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
      {
        $this->DeleteSectionCache($row['sectionid']);
      }
    }
  }
  // delete the section
  $DB->query('DELETE FROM '.$GalleryBase->sections_tbl.
             ' WHERE sectionid = %d', $sectionid);

  // turn all images offline that were in that section
  $DB->query('UPDATE '.$GalleryBase->images_tbl.
             ' SET sectionid = 1, activated = 0'.
             ' WHERE sectionid = %d', $sectionid);

  //SD360: move sub-sections to current section's parent
  $DB->query('UPDATE '.$GalleryBase->sections_tbl.
             ' SET parentid = %d, activated = 0'.
             ' WHERE parentid = %d',
             $section['parentid'], $sectionid);

  $GalleryBase->UpdateImageCounts();

} //DeleteSection


// ############################################################################
// DELETE SECTION IMAGES
// ############################################################################

function DeleteSectionImages($sectionid = -1)
{
  global $DB, $GalleryBase;

  $sectionid = Is_Valid_Number($sectionid,-1,1);
  if(empty($GalleryBase->sections_tbl) || empty($GalleryBase->images_tbl) || ($sectionid < 1))
  {
    return;
  }
  if(!$section = $DB->query_first('SELECT sectionid, parentid'.
                                  ' FROM '.$GalleryBase->sections_tbl.
                                  ' WHERE sectionid = %d',
                                  $sectionid))
  {
    return;
  }

  // delete all of the images
  $images = array();
  $getimages = $DB->query("SELECT imageid, filename, IFNULL(folder,'') folder".
                          ' FROM '.$GalleryBase->images_tbl.
                          ' WHERE sectionid = %d', $sectionid);
  while($image = $DB->fetch_array($getimages,null,MYSQL_ASSOC))
  {
    $images[$image['imageid']] = $image;
  }
  foreach($images as $imageid => $entry)
  {
    $folder = strlen($entry['folder'])?rtrim($entry['folder'],'/').'/':'';
    $folder = $GalleryBase->IMAGEPATH.$folder;
    $filename = $entry['filename'];
    $img = $folder.$filename;
    $tb  = $folder.$GalleryBase->TB_PREFIX.$filename;
    $md  = $folder.$GalleryBase->MD_PREFIX.$filename;

    $old = $GLOBALS['sd_ignore_watchdog'];
    $GLOBALS['sd_ignore_watchdog'] = true;
    foreach(array($img,$tb,$md) as $file)
    {
      if(file_exists($file))
      {
        @unlink($file);
      }
    }
    $GLOBALS['sd_ignore_watchdog'] = $old;

    $DB->query('DELETE FROM '.$GalleryBase->images_tbl.
               ' WHERE imageid = %d', $imageid);
    $DB->query('DELETE FROM {comments_count} WHERE plugin_id = %d AND object_id = %d',
               $GalleryBase->pluginid, $imageid);
    $DB->query('DELETE FROM {comments} WHERE pluginid = %d AND objectid = %d',
               $GalleryBase->pluginid, $imageid);
    $DB->query('DELETE FROM {ratings} WHERE pluginid = %d'.
               " AND rating_id = '%s'",
               $GalleryBase->pluginid,
               $DB->escape_string($GalleryBase->pre.'-'.$imageid));
    $DB->query('DELETE FROM {tags} WHERE pluginid = %d AND objectid = %d',
               $GalleryBase->pluginid, $imageid);
  } //foreach

  $GalleryBase->UpdateImageCounts($sectionid);

  $this->DeleteSectionCache($sectionid);
  $this->DeleteSectionCache($section['parentid']);

} //DeleteSectionImages


// ############################################################################
// INSERT SECTION
// ############################################################################

function InsertSection()
{
  global $DB, $mainsettings, $refreshpage, $sdlanguage, $GalleryBase;

  if(!CheckFormToken('', false))
  {
    RedirectPage($refreshpage,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  $parentid             = GetVar('parentid', 1, 'whole_number'); // default is root
  $activated            = (GetVar('activated', 0, 'bool')?1:0);
  $name                 = GetVar('sectionname', AdminPhrase('untitled_section'), 'string');
  $description          = GetVar('description', '', 'string');
  $folder               = GetVar('folder', '', 'string');
  $sorting              = GetVar('sorting', 'newest_first', 'string');
  $display_mode         = Is_Valid_Number(GetVar('display_mode',0,'natural_number'),0,0,GALLERY_MAX_DISP_MODE);
  $display_author       = (GetVar('display_author', 0, 'bool')?1:0);
  $display_comments     = (GetVar('display_comments', 0, 'bool')?1:0);
  $display_ratings      = (GetVar('display_ratings', 0, 'bool')?1:0);
  $display_view_counts  = (GetVar('display_view_counts', 0, 'bool')?1:0);
  $images_per_row       = GetVar('images_per_row', 0, 'natural_number');
  $images_per_page      = GetVar('images_per_page', 0, 'natural_number');
  $access_view          = GetVar('access_view', array(), 'a_int');

  //SD343: set owner-id and restriction option
  $owner_id = 0;
  $owner_name           = GetVar('owner_name', '', 'string');
  $owner_access_only    = (GetVar('owner_access_only', 0, 'bool')?1:0);
  if(!empty($owner_name) && ($owner = sd_GetForumUserInfo(0, $owner_name)))
  {
    $owner_id = $owner['userid'];
  }
  $owner_access_only = empty($owner_id) ? 0 : $owner_access_only;

  if($owner_access_only || empty($access_view))
  {
    $access_view = '';
  }
  else
  {
    $access_view = count($access_view) > 1 ? implode('|', $access_view) : $access_view[0];
    $access_view = empty($access_view) ? '' : '|'.$access_view.'|';
  }

  //SD360:
  $display_social_media = (GetVar('display_social_media', 0, 'bool')?1:0);
  $section_sorting      = GetVar('section_sorting', '', 'string');
  $max_subsections      = GetVar('max_subsections', 0, 'natural_number');
  $subsection_template_id = GetVar('subsection_template_id', 0, 'whole_number');
  $allow_submit_file_upload = (GetVar('allow_submit_file_upload', 0, 'bool')?1:0);
  $allow_submit_media_link  = (GetVar('allow_submit_media_link', 0, 'bool')?1:0);
  $seo_title            = GetVar('seo_title', '', 'string');
  $metakeywords         = GetVar('metakeywords', '', 'string');
  $metadescription      = GetVar('metadescription', '', 'string');
  $link_to              = GetVar('link_to', '', 'string');
  $target               = GetVar('target', '', 'string');
  $publish_start        = Is_Valid_Number(GetVar('publish_start', 0, 'natural_number'),0,0,2199999999);
  $publish_end          = Is_Valid_Number(GetVar('publish_end', 0, 'natural_number'),0,0,2199999999);
  //unused yet:
  $pwd                  = GetVar('pwd', '', 'string');
  $pwd_salt             = GetVar('pwd_salt', '', 'string');

  if(empty($name))
  {
    $errors[] = AdminPhrase('error_no_section_title');
  }

  //SD360: convert seo title for URL and check if seotitle is taken
  $title = preg_replace('/&[^amp;|#]/i', '&amp;', $name); //SD343
  if(!strlen(trim($seo_title)))
  {
    $seo_title = ConvertNewsTitleToUrl($title, 0, 0, true);
  }
  else
  {
    #$seo_title = ConvertNewsTitleToUrl($seo_title, 0, 0, true);
    $sep = $mainsettings['settings_seo_default_separator'];
    $ex = array(';', ',', '"', "'",  '&#039;', '&#39;', '\\', '%', '0x', '?', ' ', '(', ')', '[', ']');
    $replace = array($sep, $sep, '', $sep, $sep, $sep, '', '', '', '',  $sep, $sep, $sep, $sep, $sep);
    $seotitle = str_replace($ex, $replace, strip_alltags($seotitle));
  }
  if(strlen($seo_title))
  {
    if($DB->query_first('SELECT seo_title FROM '.$GalleryBase->sections_tbl.
                        " WHERE seo_title = '%s' AND parentid = %d",
                        $seo_title, $parentid))
    {
      $seo_title = '';
      echo AdminPhrase('err_seo_title_existing').'<br />';
    }
  }

  if(!isset($errors))
  {
    $DB->query('INSERT INTO '.$GalleryBase->sections_tbl."
      (parentid, activated, name, description, sorting, datecreated, folder, display_mode,
       display_author, display_comments, display_ratings, display_view_counts,
       images_per_row, images_per_page, access_view, owner_id, owner_access_only,
       display_social_media, section_sorting, max_subsections,
       subsection_template_id, allow_submit_file_upload, allow_submit_media_link,
       link_to, target, seo_title, metakeywords, metadescription,
       publish_start, publish_end)
      VALUES (%d, %d, '%s', '%s', '%s', %d, '%s', %d,
       %d, %d, %d, %d,
       %d, %d, '%s', %d, %d,
       %d, '%s',
       %d, %d, %d, %d,
       '%s', '%s', '%s', '%s', '%s',
       %d, %d)",
      $parentid, $activated, $name, $description, $sorting, TIME_NOW, $folder, $display_mode,
      $display_author, $display_comments, $display_ratings, $display_view_counts,
      $images_per_row, $images_per_page, $access_view, $owner_id, $owner_access_only,
      $display_social_media, $section_sorting,
      $max_subsections, $subsection_template_id, $allow_submit_file_upload, $allow_submit_media_link,
      $link_to, $target, $seo_title, $metakeywords, $metadescription,
      $publish_start, $publish_end);

    $this->DeleteSectionCache($parentid);

    RedirectPage($refreshpage, AdminPhrase('message_section_added'), 2);
  }
  else
  {
    PrintErrors($errors);

    $this->DisplaySectionForm();
  }

} //InsertSection


// ############################################################################
// UPDATE SECTION
// ############################################################################

function UpdateSection()
{
  global $DB, $refreshpage, $sdlanguage, $GalleryBase;

  if(!CheckFormToken('', false))
  {
    RedirectPage($refreshpage,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  $sectionid = GetVar('sectionid', 0, 'whole_number');
  if($sectionid < 1)
  {
    RedirectPage($refreshpage, AdminPhrase('import_invalid_section'), 3, true);
    return;
  }
  // parentid: default is 1 = root, but 0 is allowed for root section itself
  $parentid = GetVar('parentid', 1, 'natural_number');
  if($sectionid == 1)
    $parentid = 0;
  else
  if(($sectionid > 1) && ($sectionid == $parentid))
    $parentid = 1;

  $activated            = GetVar('activated', 0, 'bool')?1:0;
  $name                 = GetVar('sectionname', '', 'string');
  $description          = GetVar('description', '', 'string');
  $folder               = GetVar('folder', '', 'string');
  $folder               = str_replace($GalleryBase->IMAGEPATH, '', $folder);
  $sorting              = empty($_POST['sorting'])  ? 'newest_first': (string)$_POST['sorting'];
  $display_mode         = GetVar('display_mode', 0, 'natural_number');
  $display_author       = GetVar('display_author', 0, 'bool')?1:0;
  $display_comments     = GetVar('display_comments', 0, 'bool')?1:0;
  $display_ratings      = GetVar('display_ratings', 0, 'bool')?1:0;
  $display_view_counts  = GetVar('display_view_counts', 0, 'bool')?1:0;
  $images_per_row       = GetVar('images_per_row', 0, 'natural_number');
  $images_per_page      = GetVar('images_per_page', 0, 'natural_number');
  $imageid              = GetVar('imageid', 0, 'natural_number');
  $movetoid             = GetVar('movetoid', 0, 'natural_number');

  $imageid  = Is_Valid_Number($imageid,0,0,99999999);
  $movetoid = Is_Valid_Number($movetoid,0,0,99999999);

  $add_tags             = GetVar('add_tags', 0, 'bool')?1:0; //TODO
  $set_allowcomments    = GetVar('set_allowcomments', '', 'string');
  $set_displayauthor    = GetVar('set_displayauthor', '', 'string');
  $set_ratings          = GetVar('set_ratings', '', 'string');
  $remove_comments      = GetVar('remove_comments', 0, 'bool')?1:0;
  $remove_ratings       = GetVar('remove_ratings', 0, 'bool')?1:0;
  $remove_tags          = GetVar('remove_tags', 0, 'bool')?1:0;
  $deletesection        = GetVar('deletesection', 0, 'bool')?1:0;
  $deletesectionimages  = GetVar('deletesectionimages', 0, 'bool')?1:0;

  //SD343: set owner-id and restriction option
  $owner_name           = GetVar('owner_name', '', 'string');
  $owner_access_only    = GetVar('owner_access_only', 0, 'bool')?1:0;
  $owner_id = 0;
  if(!empty($owner_name) && ($owner = sd_GetForumUserInfo(0, $owner_name)) && !empty($owner['userid']))
  {
    $owner_id = $owner['userid'];
  }
  $owner_access_only = empty($owner_id) ? 0 : $owner_access_only;

  $access_view = GetVar('access_view', array(), 'a_int', true, false);
  $access_view = is_array($access_view) && count($access_view) ? implode('|', $access_view) : $access_view;
  $access_view = empty($access_view) ? '' : '|'.$access_view.'|';

  //SD360:
  $display_social_media = GetVar('display_social_media', 0, 'bool')?1:0;
  $section_sorting      = GetVar('section_sorting', '', 'string'); //SD360
  $max_subsections      = GetVar('max_subsections', 0, 'natural_number'); //SD360
  $subsection_template_id = GetVar('subsection_template_id', 0, 'whole_number'); //SD360
  $allow_submit_file_upload = GetVar('allow_submit_file_upload', 0, 'bool')?1:0; //SD360
  $allow_submit_media_link  = GetVar('allow_submit_media_link', 0, 'bool')?1:0; //SD360
  $seo_title            = GetVar('seo_title', '', 'string');
  $metakeywords         = GetVar('metakeywords', '', 'string');
  $metadescription      = GetVar('metadescription', '', 'string');
  $link_to              = GetVar('link_to', '', 'string');
  $target               = GetVar('target', '', 'string');
  //SD362:
  $datestart            = GetVar('datestart',   0,    'string', true, false);
  $datestart2           = GetVar('datestart2',  null, 'string', true, false);
  $timestart            = GetVar('timestart',   0,    'string', true, false);
  $dateend              = GetVar('dateend',     0,    'string', true, false);
  $dateend2             = GetVar('dateend2',    null, 'string', true, false);
  $timeend              = GetVar('timeend',     0,    'string', true, false);
  $publish_start = !empty($datestart2)   && $datestart   ? sd_CreateUnixTimestamp($datestart, $timestart) : 0;
  $publish_end   = !empty($dateend2)     && $dateend     ? sd_CreateUnixTimestamp($dateend, $timeend) : 0;
  //unused yet:
  $pwd                  = GetVar('pwd', '', 'string');
  $pwd_salt             = GetVar('pwd_salt', '', 'string');

  //SD360: convert seo title for URL and check if seotitle is taken
  $title = preg_replace('/&[^amp;|#]/i', '&amp;', $name); //SD343
  if(!strlen(trim($seo_title)))
  {
    $seo_title = ConvertNewsTitleToUrl($title, 0, 0, true);
  }
  else
  {
    $seo_title = ConvertNewsTitleToUrl($seo_title, 0, 0, true);
  }
  if(strlen($seo_title))
  {
    if($DB->query_first('SELECT seo_title FROM '.$GalleryBase->sections_tbl.
                        " WHERE seo_title = '%s'".
                        ' AND sectionid <> %d AND parentid = %d',
                        $seo_title, $sectionid, $parentid))
    {
      $seo_title = '';
      echo AdminPhrase('err_seo_title_existing').'<br />';
    }
  }

  // Fix sections: sectionid must be different from parentid!
  $DB->query('UPDATE '.$GalleryBase->sections_tbl.
             ' SET parentid = 1'.
             ' WHERE sectionid > 1 AND sectionid = parentid');

  if(!$deletesection && !strlen(trim($name)))
  {
    $errors[] = AdminPhrase('error_no_section_title');
    //SD360: update all section data except it's name
    if(!empty($sectionid))
    $DB->query('UPDATE '.$GalleryBase->sections_tbl."
      SET parentid = %d, activated = %d, description = '%s',
      sorting = '%s', imageid = %d, folder = '%s', display_mode = %d,
      display_author = %d, display_comments = %d, display_ratings = %d,
      display_view_counts = %d, images_per_row = %d, images_per_page = %d,
      access_view = '%s', owner_id = %d, owner_access_only = %d,
      display_social_media = %d, section_sorting = '%s', max_subsections = %d,
      subsection_template_id = %d, allow_submit_file_upload = %d,
      allow_submit_media_link = %d, link_to = '%s', target = '%s',
      seo_title = '%s', metakeywords = '%s', metadescription = '%s',
      publish_start = %d, publish_end = %d
      WHERE sectionid = %d",
      $parentid, $activated, $description, $sorting, $imageid, $folder, $display_mode,
      $display_author, $display_comments, $display_ratings, $display_view_counts,
      $images_per_row, $images_per_page, $access_view, $owner_id, $owner_access_only,
      $display_social_media, $section_sorting, $max_subsections, $subsection_template_id,
      $allow_submit_file_upload, $allow_submit_media_link, $link_to, $target,
      $seo_title, $metakeywords, $metadescription,
      $publish_start, $publish_end,
      $sectionid);
  }

  if(!isset($errors) && !empty($sectionid))
  {
    $DB->query('UPDATE '.$GalleryBase->sections_tbl."
      SET parentid = %d, activated = %d, name = '%s', description = '%s',
      sorting = '%s', imageid = %d, folder = '%s', display_mode = %d,
      display_author = %d, display_comments = %d, display_ratings = %d,
      display_view_counts = %d, images_per_row = %d, images_per_page = %d,
      access_view = '%s', owner_id = %d, owner_access_only = %d,
      display_social_media = %d, section_sorting = '%s', max_subsections = %d,
      subsection_template_id = %d,
      allow_submit_file_upload = %d, allow_submit_media_link = %d, link_to = '%s',
      target = '%s', seo_title = '%s', metakeywords = '%s', metadescription = '%s',
      publish_start = %d, publish_end = %d
      WHERE sectionid = %d",
      $parentid, $activated, $name, $description, $sorting, $imageid, $folder, $display_mode,
      $display_author, $display_comments, $display_ratings, $display_view_counts,
      $images_per_row, $images_per_page, $access_view, $owner_id, $owner_access_only,
      $display_social_media, $section_sorting, $max_subsections, $subsection_template_id,
      $allow_submit_file_upload, $allow_submit_media_link, $link_to,
      $target, $seo_title, $metakeywords, $metadescription,
      $publish_start, $publish_end,
      $sectionid);

    //SD360: clear cache files for section and it's parent
    $this->DeleteSectionCache($sectionid);
    $this->DeleteSectionCache($parentid);

    // Delete section's images?
    if($deletesectionimages)
    {
      $this->DeleteSectionImages($sectionid);
    }
    else
    {
      if(!empty($movetoid) && ($movetoid != $sectionid))
      {
        $DB->query('UPDATE '.$GalleryBase->images_tbl.
                   ' SET sectionid = %d WHERE sectionid = %d',
                   $movetoid, $sectionid);
        $GalleryBase->UpdateImageCounts($movetoid);
        $this->DeleteSectionCache($movetoid);
      }

      $GalleryBase->UpdateImageCounts($sectionid);

      if(($set_allowcomments == 'Y') || ($set_allowcomments == 'N'))
      {
        $DB->query('UPDATE '.$GalleryBase->images_tbl.
                   ' SET allowcomments = %d'.
                   ' WHERE sectionid = %d',
                   ($set_allowcomments == 'Y' ? 1 : 0), $sectionid);
      }

      if(($set_displayauthor == 'Y') || ($set_displayauthor == 'N'))
      {
        $DB->query('UPDATE '.$GalleryBase->images_tbl.
                   ' SET showauthor = %d'.
                   ' WHERE sectionid = %d',
                   ($set_displayauthor == 'Y' ? 1 : 0), $sectionid);
      }

      if(($set_ratings == 'Y') || ($set_ratings == 'N'))
      {
        $DB->query('UPDATE '.$GalleryBase->images_tbl.
                   ' SET allow_ratings = %d'.
                   ' WHERE sectionid = %d',
                   ($set_ratings == 'Y' ? 1 : 0), $sectionid);
      }

      if($remove_comments)
      {
        $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'comments_count'.
                   ' WHERE plugin_id = %d AND object_id IN'.
                   ' (SELECT imageid FROM '.$GalleryBase->images_tbl.
                    ' WHERE sectionid = %d)',
                   $GalleryBase->pluginid, $sectionid);
        $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'comments'.
                   ' WHERE pluginid = %d AND objectid IN'.
                   ' (SELECT imageid FROM '.$GalleryBase->images_tbl.
                    ' WHERE sectionid = %d)',
                   $GalleryBase->pluginid, $sectionid);
      }

      if($remove_ratings)
      {
        $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'ratings'.
                   ' WHERE pluginid = %d AND rating_id IN '.
                   " (SELECT CONCAT('".$GalleryBase->pre."-', imageid)".
                   ' FROM '.$GalleryBase->images_tbl.
                   ' WHERE sectionid = %d)',
                   $GalleryBase->pluginid, $sectionid);
      }

      if($remove_tags)
      {
        $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'tags'.
                   ' WHERE pluginid = %d AND objectid IN '.
                   ' (SELECT imageid FROM '.$GalleryBase->images_tbl.
                    ' WHERE sectionid = %d)',
                   $GalleryBase->pluginid, $sectionid);
      }
    }

    // Delete section?
    if($deletesection && ($sectionid > 1))
    {
      $this->DeleteSection($sectionid);
    }

    RedirectPage($refreshpage.'&action=displaysectionform&sectionid='.$sectionid,
                 AdminPhrase('message_section_updated'), 2);
  }
  else
  {
    PrintErrors($errors);
    $this->DisplaySectionForm();
  }

} //UpdateSection


// ######################### DISPLAY SECTION FORM ##############################

function DisplaySectionForm($param_sectionid = null)
{
  global $DB, $refreshpage, $sdlanguage, $GalleryBase;

  if(isset($param_sectionid))
  {
    $sectionid = (int)$param_sectionid;
  }
  else
  {
    $sectionid = Is_Valid_Number(GetVar('sectionid', 0, 'whole_number'),0,0,99999999);
  }
  if($sectionid)
  {
    $DB->result_type = MYSQL_ASSOC;
    $section = $DB->query_first('SELECT * FROM '.$GalleryBase->sections_tbl.
                                ' WHERE sectionid = %d', $sectionid);
  }
  else
  {
    $section = array(
     'parentid'       => GetVar('parentid', 1, 'whole_number'),
     'activated'      => (GetVar('activated', 1, 'bool')?1:0),
     'name'           => GetVar('name', '', 'string'),
     'description'    => GetVar('description', '', 'string'),
     'folder'         => GetVar('folder', '', 'string'),
     'tags'           => GetVar('tags', '', 'string'),
     'sorting'        => GetVar('sorting', 'Newest First', 'string'),
     'imageid'        => GetVar('imageid', 0, 'whole_number'),
     'images_per_page'=> GetVar('images_per_page', 0, 'natural_number'),
     'images_per_row' => GetVar('images_per_row', 0, 'natural_number'),
     'display_mode'   => GetVar('display_mode', 0, 'whole_number'),
     'display_author' => (GetVar('display_author', 0, 'bool')?1:0),
     'display_comments'     => (GetVar('display_comments', 0, 'bool')?1:0),
     'display_ratings'      => (GetVar('display_ratings', 0, 'bool')?1:0),
     'display_view_counts'  => (GetVar('display_view_counts', 0, 'bool')?1:0),
     'display_social_media' => (GetVar('display_social_media', 0, 'bool')?1:0),
     'access_view'          => GetVar('access_view', '', 'a_int'),
     'owner_access_only'    => (GetVar('owner_access_only', 0, 'bool')?1:0),
     #SD360:
     'section_sorting'  => GetVar('section_sorting', '', 'string'),
     'max_subsections'  => GetVar('max_subsections', 0, 'natural_number'),
     'subsection_template_id' => GetVar('subsection_template_id', 0, 'whole_number'),
     'allow_submit_file_upload' => (GetVar('allow_submit_file_upload', 0, 'bool')?1:0),
     'allow_submit_media_link'  => (GetVar('allow_submit_media_link', 0, 'bool')?1:0),
     'seo_title'        => GetVar('seo_title', '', 'string'),
     'metakeywords'     => GetVar('metakeywords', '', 'string'),
     'metadescription'  => GetVar('metadescription', '', 'string'),
     'link_to'          => GetVar('link_to', '', 'string'),
     'target'           => GetVar('target', '', 'string'),
     'publish_start'    => Is_Valid_Number(GetVar('publish_start', 0, 'natural_number'),0,0,2199999999),
     'publish_end'      => Is_Valid_Number(GetVar('publish_end', 0, 'natural_number'),0,0,2199999999),
     //unused yet:
     'pwd'              => '',#GetVar('pwd', '', 'string'),
     'pwd_salt'         => '',#GetVar('pwd_salt', '', 'string'),
     );
  }

  //Check empty vars to avoid system log messages
  $section['description'] = isset($section['description'])?$section['description']:'';
  $section['folder'] = isset($section['folder'])?(string)$section['folder']:'';
  if(!isset($section['access_view'])) $section['access_view'] = array();
  $section['section_sorting'] = empty($section['section_sorting'])?'newest_first':$section['section_sorting'];
  $section['sorting'] = empty($section['sorting'])?'newest_first':$section['sorting'];
  $section['subsection_template_id'] = empty($section['subsection_template_id'])?0:$section['subsection_template_id'];
  $section['allow_submit_file_upload'] = (empty($sectionid) || !empty($section['allow_submit_file_upload']))?1:0;
  $section['allow_submit_media_link'] = empty($section['allow_submit_media_link'])?0:1;
  $section['metadescription'] = empty($section['metadescription'])?'':(string)$section['metadescription'];
  $section['metakeywords'] = empty($section['metakeywords'])?'':(string)$section['metakeywords'];
  $section['link_to'] = empty($section['link_to'])?'':(string)$section['link_to'];
  $section['target'] = empty($section['target'])?'':(string)$section['target'];
  $section['owner_id'] = empty($section['owner_id'])?0:(int)$section['owner_id'];
  $section['publish_start'] = empty($section['publish_start'])?0:(int)$section['publish_start'];
  $section['publish_end'] = empty($section['publish_end'])?0:(int)$section['publish_end'];

  echo '
  <form method="post" action="'.$refreshpage.'" class="form-horizontal">
  <input type="hidden" name="action" value="'. ($sectionid ? 'updatesection' : 'insertsection').'" />
  <input type="hidden" name="sectionid" value="'. $sectionid .'" />
  '.PrintSecureToken();

  StartSection($sectionid ? AdminPhrase('update_section') : AdminPhrase('create_section'),'',true,true);
  echo '
  <div class="form-group">
    <label class="control-label col-sm-3">' . AdminPhrase('section') . '</label>
		<div class="col-sm-6">
      <input type="text" class="form-control" name="sectionname" maxlength="128" size="80" value="'.$section['name'].'" style="width: 95%; font-weight: bold;" />
    </div>
  </div>
  <div class="form-group">
    <label class="control-label col-sm-3">'.AdminPhrase('section_seo_title').'</label>
<div class="col-sm-6">
      <input type="text" class="form-control" name="seo_title" style="min-with: 100px; width: 95%;" value="'.$section['seo_title'].'" />
      <br />'.AdminPhrase('section_seo_title_hint').'
    </div>
</div>
  <div class="form-group">
    <label class="control-label col-sm-3">' . AdminPhrase('parent_section') . '</label>
    <div class="col-sm-6">';

  if($sectionid != 1)
  {
    $this->PrintSectionSelection('parentid', $section['parentid'], $sectionid, 'width: 98%; padding: 4px;');
  }
  else
  {
    echo '<p class="form-control-static">' . AdminPhrase('no_parent_for_root') . '</p>';
  }

  echo '</div>
</div>';

  //SD343: section-owner, owner-only access
  echo '
  <div class="form-group">
  	<label class="control-label col-sm-3">' . AdminPhrase('description') . '</label>
   <div class="col-sm-6">';

  DisplayBBEditor($GalleryBase->allow_bbcode, 'description', $section['description'], null, 80, 10);

  // Apply BBCode parsing
  if($sectionid && $GalleryBase->allow_bbcode)
  {
    global $bbcode;
    $bbcode->SetDetectURLs(false);
    echo '<div class="bbcode_preview">'.$bbcode->Parse($section['description']).'</div>';
  }

  echo '
    </div>
</div>
  <div class="form-group">
    <label class="control-label col-sm-3">'.AdminPhrase('section_owner').'</label>
<div class="col-sm-6">';
  $owner_name = '';
  if(!empty($section['owner_id']) &&
     ($owner = sd_GetForumUserInfo(1, $section['owner_id'])))
  {
    $owner_name = $owner['username'];
  }
  $owner_valid = !empty($section['owner_id']) && strlen($owner_name);
  echo '
      <input type="text" id="UserSearch" class="form-control" name="owner_name" value="'.$owner_name.'" maxlength="30" />
      <input type="hidden" id="newOwnerID" name="newAuthorID" value="'.(int)$section['owner_id'].'" />
      <input type="hidden" id="owner_id" value="'.(int)$section['owner_id'].'" />
      ';
  if(!empty($section['owner_id']))
  {
    echo $owner_valid ? '' : '<span style="color:red">';
    echo ' (ID '.$section['owner_id'].($owner_valid?'':': INVALID!').')';
    echo $owner_valid ? '' : '</span>';
  }
  echo '<span class="helper-text">'.AdminPhrase('section_owner_descr').'</span>
    </div>
</div>
  <div class="form-group">
    <label class="control-label col-sm-3">'.AdminPhrase('owner_access_only').'</label>
<div class="col-sm-6">
    <fieldset>
      <label for="owner_access_only">
      <input type="checkbox" class="ace" id="owner_access_only" name="owner_access_only" value="1"'.
       (!empty($section['owner_access_only'])?' checked="checked"':'').' /><span class="lbl"></span> '.
       AdminPhrase('owner_access_only_descr').'</label>
    </fieldset>
    </div>
</div>';

  echo '
  <div class="form-group">
    <label class="control-label col-sm-3">' . AdminPhrase('viewing_permissions') . '</label>
<div class="col-sm-6">
      <select name="access_view[]" multiple="multiple" class="form-control" size="6">';

  if(is_array($section['access_view']))
    $groups = $section['access_view'];
  else
    $groups = sd_ConvertStrToArray($section['access_view'], '|');
  $getusergroups = $DB->query('SELECT usergroupid, name FROM {usergroups} ORDER BY usergroupid');
  while($ug = $DB->fetch_array($getusergroups,null,MYSQL_ASSOC))
  {
    echo '
      <option value="'.$ug['usergroupid'].'" '.
      (in_array($ug['usergroupid'],$groups)?' selected="selected"':'').'>'.
      htmlspecialchars($ug['name']).'</option>';
  }

  echo '</select>
  		<span class="helper-text"> ' . AdminPhrase('viewing_permissions_hint') . '</span>
    </div>
</div>
  <div class="form-group">
    <label class="control-label col-sm-3">' . AdminPhrase('sort_images_by') . '</label>
<div class="col-sm-6">
      <select name="sorting" class="form-control"
        <option value="newest_first" '.($section['sorting'] == "newest_first" ? 'selected="selected"':'') .'>' . AdminPhrase('newest_first') . '</option>
        <option value="oldest_first" '.($section['sorting'] == "oldest_first" ? 'selected="selected"':'') .'>' . AdminPhrase('oldest_first') . '</option>
        <option value="alpha_az" '.($section['sorting'] == "alpha_az" ? 'selected="selected"':'') .'>' . AdminPhrase('alpha_az') . '</option>
        <option value="alpha_za" '.($section['sorting'] == "alpha_za" ? 'selected="selected"':'') .'>' . AdminPhrase('alpha_za') . '</option>
        <option value="author_name_az" '.($section['sorting'] == "author_name_az" ? 'selected="selected"':'') .'>' . AdminPhrase('author_name_az') . '</option>
        <option value="author_name_za" '.($section['sorting'] == "author_name_za" ? 'selected="selected"':'') .'>' . AdminPhrase('author_name_za') . '</option>
      </select>
    </div>
</div>
  <div class="form-group">
    <label class="control-label col-sm-3">' . AdminPhrase('image_display_mode') . '</label>
<div class="col-sm-6">';

  $setting = $DB->query_first("SELECT input FROM {pluginsettings} WHERE pluginid = %d AND title = 'image_display_mode'",$GalleryBase->pluginid);
  $section['display_mode'] = empty($section['display_mode'])?0:(int)$section['display_mode'];
  echo sd_ParseToSelect($setting['input'], $section['display_mode'], AdminPhrase('image_display_mode'),
                        'display_mode', $GalleryBase->language, 'width: 260px; padding: 2px;');
  unset($setting);

  echo '
    </div>
</div>';

  echo '
  <div class="form-group">
    <label class="control-label col-sm-3">' . AdminPhrase('subsections_list_template') . '</label>
<div class="col-sm-6">
      <select name="subsection_template_id" class="form-control">';

  // Display all templates for plugin starting with "subsections"
  if($t = SD_Smarty::GetTemplateNamesForPlugin($GalleryBase->pluginid, false))
  foreach($t as $idx => $row)
  {
    if(sd_substr($row['tpl_name'],0,11)=='subsections')
    echo '<option value="'.$row['template_id'].'" '.
         ($row['template_id']==$section['subsection_template_id']?' selected="selected"':'').'>'.$row['displayname'].'</option>';
  }
  echo '
      </select>
      <span class="helper-text">' . AdminPhrase('subsections_list_template_descr') . '</span>
    </div>
</div>
  <div class="form-group">
    <label class="control-label col-sm-3">' . AdminPhrase('sort_subsections_by') . '</label>
<div class="col-sm-6">
      <select name="section_sorting" class="form-control">
        <option value="newest_first" '.($section['section_sorting'] == "newest_first" ? 'selected="selected"':'') .'>' . AdminPhrase('newest_first') . '</option>
        <option value="oldest_first" '.($section['section_sorting'] == "oldest_first" ? 'selected="selected"':'') .'>' . AdminPhrase('oldest_first') . '</option>
        <option value="alpha_az" '.($section['section_sorting'] == "alpha_az" ? 'selected="selected"':'') .'>' . AdminPhrase('alpha_az') . '</option>
        <option value="alpha_za" '.($section['section_sorting'] == "alpha_za" ? 'selected="selected"':'') .'>' . AdminPhrase('alpha_za') . '</option>
      </select>
    </div>
</div>
  <div class="form-group">
    <label class="control-label col-sm-3">' . AdminPhrase('display_max_subsections') . '</label>
   <div class="col-sm-6">
      <input type="text" class="form-control" name="max_subsections" maxlength="3" value="'.
        (empty($section['max_subsections'])?0:(int)$section['max_subsections']).
        '" />
    </div>
</div>';

  echo '
  <div class="form-group">
    <label class="control-label col-sm-3">' . AdminPhrase('allow_submit_file_upload') . '</label>
<div class="col-sm-6">
      <input type="checkbox" class="ace" name="allow_submit_file_upload" value="1" '.
       (empty($section['allow_submit_file_upload'])?'':' checked="checked"').' /><span class="lbl"></span>
       ' . AdminPhrase('allow_submit_file_upload_descr') . '
    </div>
</div>
  <div class="form-group">
    <label class="control-label col-sm-3">' . AdminPhrase('allow_submit_media_link') . '</label>
<div class="col-sm-6">
      <input type="checkbox" class="ace" name="allow_submit_media_link" value="1" '.
       (empty($section['allow_submit_media_link'])?'':' checked="checked"').' /><span class="lbl"></span>
       ' . AdminPhrase('allow_submit_media_link_descr') . '
    </div>
</div>
  <div class="form-group">
    <label class="control-label col-sm-3">' . AdminPhrase('number_of_images_per_row') . '</label>
<div class="col-sm-6">
      <input type="text" class="form-control" name="images_per_row" maxlength="3" value="'.
      (empty($section['images_per_row'])?0:(int)$section['images_per_row']).
      '" />
	  <span class="helper-text">' . AdminPhrase('number_of_images_per_row_hint') . '</span>
    </div>
</div>
  <div class="form-group">
    <label class="control-label col-sm-3">' . AdminPhrase('section_images_per_page') . '</label>
<div class="col-sm-6">
      <input type="text" class="form-control" name="images_per_page" maxlength="3" value="'.
      (empty($section['images_per_page'])?0:(int)$section['images_per_page']).
      '" />
	  <span class="helper-text">
      ' . AdminPhrase('section_images_per_page_hint') . '</span>
    </div>
</div>';

  if($sectionid != 1)
  {
    echo '
  <div class="form-group">
    <label class="control-label col-sm-3">'.AdminPhrase('section_link_to').'<br />
      '.AdminPhrase('section_link_to_hint').'
    </label>
<div class="col-sm-6">
      <input type="text" class="form-control" name="link_to" value="'.
        (isset($section['link_to'])?htmlentities($section['link_to'], ENT_QUOTES):'').
        '" size="30" />
    </div>
</div>
  <div class="form-group">
  	<label class="control-label col-sm-3">'.AdminPhrase('section_external_link_target').'</label>
<div class="col-sm-6">
      <select name="target" class="form-control">
        <option value="" ' . (empty($section['target']) ? 'selected="selected"' : '') . '>-</option>
        <option value="_blank" ' . ($section['target'] == '_blank'  ? 'selected="selected"' : '') . '>_blank</option>
        <option value="_self" ' .  ($section['target'] == '_self'   ? 'selected="selected"' : '') . '>_self</option>
        <option value="_parent" ' .($section['target'] == '_parent' ? 'selected="selected"' : '') . '>_parent</option>
        <option value="_top" ' .   ($section['target'] == '_top'    ? 'selected="selected"' : '') . '>_top</option>
      </select>
    </div>
</div>';
  }

  echo '
 <div class="form-group">
  	<label class="control-label col-sm-3">' . AdminPhrase('section_meta_description') . '</label>
<div class="col-sm-6"><textarea name="metadescription" class="form-control" rows="3" cols="40">'.
      $section['metadescription'] . '</textarea></div>
</div>
  <div class="form-group">
  	<label class="control-label col-sm-3">' . AdminPhrase('section_meta_keywords') . '</label>
<div class="col-sm-6"><textarea name="metakeywords" class="form-control" rows="3" cols="40">'.
      $section['metakeywords'] . '</textarea></div>
</div>
  <div class="form-group">
  	<label class="control-label col-sm-3">' . AdminPhrase('options') . '</label>
   <div class="col-sm-6">
      <fieldset>';

  $options = array( array('activated', $GalleryBase->language['display_section_online']),
                    array('display_author', $GalleryBase->language['section_display_author']),
                    array('display_comments', $GalleryBase->language['section_display_comments']),
                    array('display_ratings', $GalleryBase->language['section_display_ratings']),
                    array('display_view_counts', $GalleryBase->language['section_display_view_counts']),
                    array('display_social_media', AdminPhrase('show_social_media')) );
  foreach($options as $key)
  {
    echo '<label for="'.$key[0].'"><input type="checkbox" class="ace" id="'.$key[0].'" name="'.$key[0].'" value="1" ' .
         (!empty($section[$key[0]]) ? 'checked="checked"' : '').' /><span class="lbl"></span> ' . $key[1] . "</label>\n";
  }

  echo '
    </fieldset></div>
</div>';

  $datestart = DisplayDate($section['publish_start'], '', false, true);
  $dateend   = DisplayDate($section['publish_end'], '', false, true);
  echo '
  <div class="form-group">
  	<label class="control-label col-sm-3">' . $GalleryBase->language['start_publishing'] . '</label>
<div class="col-sm-6">
      <input type="hidden" id="datestart" name="datestart" value="' . DisplayDate($section['publish_start'], 'yyyy-mm-dd') . '" />
      <input type="text" id="datestart2" name="datestart2" rel="'.($datestart?$datestart:'0').'" value="" size="10" />
      <input type="text" id="timestart" name="timestart" value="'.DisplayDate($datestart,'H:i',false).'" size="6" />
    </div>
</div>
  <div class="form-group">
  	<label class="control-label col-sm-3">' . $GalleryBase->language['end_publishing'] . '</label>
<div class="col-sm-6">
      <input type="hidden" id="dateend" name="dateend" value="' . DisplayDate($section['publish_end'], 'yyyy-mm-dd') . '" />
      <input type="text" id="dateend2" name="dateend2" rel="'.($dateend?$dateend:'0').'" value="" size="10" />
      <input type="text" id="timeend" name="timeend" value="'.DisplayDate($dateend,'H:i',false).'" size="6" />
    </div>
</div>
  <div class="form-group">
  	<label class="control-label col-sm-3">' . AdminPhrase('section_upload_folder') . '</label>
   <div class="col-sm-6">
      <select name="folder" class="form-control">
      <option value=""'.(empty($section['folder']) ? ' selected="selected"' : '').'>'.AdminPhrase('default_folder').'</option>
      ';

  echo SD_Image_Helper::GetSubFoldersForSelect(ROOT_PATH.$GalleryBase->IMAGEDIR, $section['folder']);

  echo '
      </select>
	  <span class="helper-text">
      ' . AdminPhrase('section_upload_folder_hint') . '</span>
    </div>
</div>
  ';

  // Only show section image and move operation if there are actually
  // images within this section already
  if(!$sectionid)
  {
    echo '
    <input type="hidden" name="imageid" value="0" />
    <input type="hidden" name="movetoid" value="0" />';
  }
  if($imgcount = $DB->query_first('SELECT COUNT(*) FROM '.$GalleryBase->images_tbl.
                                  ' WHERE sectionid = %d', $sectionid))
  {
    $imgcount = $imgcount[0];
  }
  if(!empty($imgcount) && ($imgcount > 0) && ($sectionid > 0))
  {
    if($sectionid > 1)
    {
      $img_div = '<div style="white-space: nowrap; display: inline; float: left; margin: 6px; padding: 2px; height: 90px; min-width: 90px;">';
      // Display the images in the section so that the user can choose one to represent the section
      echo '
     <h3 class="header blue lighter">' . AdminPhrase('section_image') . '</h3>
        <br />' . AdminPhrase('section_thumbnail_hint') . '</td>
        <div style="min-height: 120px; max-height: 300px; overflow: hidden; overflow-y: scroll;">'.$img_div.'<input type="radio" name="imageid" value="0" '.
        (empty($section['imageid'])?'checked="checked"':'') . ' />
        <img src="'.$GalleryBase->IMAGEPATH.$GalleryBase->defaultimg.'" align="middle" alt=" " style="max-width: 100px; max-height: 90px; padding: 2px;" /></div>';

      $getimages = $DB->query("SELECT imageid, IFNULL(filename,'') filename, title, IFNULL(folder,'') folder, px_width, px_height".
                              ' FROM '.$GalleryBase->images_tbl.
                              ' WHERE sectionid = %d'.
                              " AND (IFNULL(filename,'') > '')". //SD362: media thumbs allowed!
                              ' ORDER BY imageid', $sectionid);
      while($image = $DB->fetch_array($getimages,null,MYSQL_ASSOC))
      {
        $folder = $GalleryBase->IMAGEPATH . $image['folder'];
        $folder .= (strlen($folder) && (sd_substr($folder,strlen($folder)-1,1)!='/') ? '/' : '');
        echo $img_div.'
        <input type="radio"  name="imageid" value="'.$image['imageid'].'" '.
             ($image['imageid']==$section['imageid'] ? 'checked="checked"' : '').' />'.
             '<a class="ceebox tip" href="'.$folder.$image['filename'] .'" rel="image" '.
             'title="'.$GalleryBase->language['image_title'].' <strong>'.' '.
             (empty($image['title'])?$GalleryBase->language['untitled']:$image['title']).'</strong><br />'.
               AdminPhrase('image_size').' '.$image['px_width'].' * '.$image['px_height'] . ' px">'.
             '<img src="'.$folder.$GalleryBase->TB_PREFIX. $image['filename'].
             '" align="middle" alt="" style="'.($image['imageid']==$section['imageid'] ? 'border: 1px solid #ff0000; ' : '').
             'max-width: 90px; max-height: 80px; padding: 2px;" /></a></div>';
      }

      echo '
        </div>';
    }

    echo '
    <h3 class="header blue lighter">' . AdminPhrase('section_batch_operations') . ' <small>' . AdminPhrase('reassign_images_hint') . '</small></h3>
      <div class="form-group">
  	<label class="control-label col-sm-3">' . AdminPhrase('reassign_images') . '</label>
	<div class="col-sm-6">
      <select name="movetoid" class="form-control" size="8">
        <option value="0" selected="selected">' . AdminPhrase('no_action') . '</option>
        ';
    $this->PrintSectionChildren(0, -1, $sectionid);
    echo '
      </select>
	  <span class="helper-text"> </span>
      </div>
    </div>
    <div class="form-group">
  	<label class="control-label col-sm-3">'.AdminPhrase('section_batch_set_displayauthor').'</label>
	<div class="col-sm-6">
      <select name="set_displayauthor" class="form-control">
        <option value="0" selected="selected">' . AdminPhrase('no_action') . '</option>
        <option value="Y">' . $sdlanguage['yes'] . '</option>
        <option value="N">' . $sdlanguage['no'] . '</option>
      </select>
	 </div>
	</div>
	<div class="form-group">
  	<label class="control-label col-sm-3"> '.AdminPhrase('section_batch_set_allowcomments').'</label>
	<div class="col-sm-6">
      <select name="set_allowcomments" class="form-control">
        <option value="0" selected="selected">' . AdminPhrase('no_action') . '</option>
        <option value="Y">' . $sdlanguage['yes'] . '</option>
        <option value="N">' . $sdlanguage['no'] . '</option>
      </select>
	</div>
</div>
<div class="form-group">
  	<label class="control-label col-sm-3">'.AdminPhrase('section_batch_set_ratings').'</label>
	<div class="col-sm-6">
      <select name="set_ratings" class="form-control">
        <option value="0" selected="selected">' . AdminPhrase('no_action') . '</option>
        <option value="Y">' . $sdlanguage['yes'] . '</option>
        <option value="N">' . $sdlanguage['no'] . '</option>
      </select>
	</div>
</div>
<div class="form-group">
  	<label class="control-label col-sm-3">'.AdminPhrase('options').'</label>
	<div class="col-sm-6">
      <fieldset>
	  
      ';

    $options = array( //array('add_tags', AdminPhrase('section_batch_add_tags').' ... [input here]'),
                      array('remove_comments', AdminPhrase('section_batch_remove_comments')),
                      array('remove_ratings', AdminPhrase('section_batch_remove_ratings')),
                      array('remove_tags', AdminPhrase('section_batch_remove_tags')),
                      );
    foreach($options as $key)
    {
      echo '<label for="'.$key[0].'"><input type="checkbox" class="ace" id="'.$key[0].'" name="'.$key[0].'" value="1" '.
           (!empty($section[$key[0]]) ? 'checked="checked"' : '').' /><span class="lbl"></span> ' . $key[1].'<br /></label>';
    }

    echo '</fieldset>
      </div>
    </div>
    ';
  }

  if(($sectionid > 1) || $imgcount)
  {
    echo '
  <div class="form-group">
  	<label class="control-label col-sm-3 red">'.AdminPhrase('delete').'</label>
	<div class="col-sm-6">
      <fieldset>';
    if($sectionid > 1)
    {
      echo '
      <label for="deletesection"><input type="checkbox" class="ace" id="deletesection" name="deletesection" value="1" /><span class="lbl red bolder">
      <strong>'.AdminPhrase('delete_section').'</strong></span></label>';
    }
    if($sectionid && $imgcount)
    {
      echo '
      <label for="deletesectionimages"><input type="checkbox" class="ace" id="deletesectionimages" name="deletesectionimages" value="1" /><span class="lbl red bolder">
      <strong>' . AdminPhrase('delete_images_in_section').'</strong></span></label>
      '.AdminPhrase('delete_images_in_section_hint');
    }
    echo '
      </fieldset>
    </div>
</div>
';
  }
  echo '
  <div class="center">
  	<button class="btn btn-info" type="submit" value=""/><i class="ace-icon fa fa-check"></i>'.addslashes(($sectionid ? AdminPhrase('update_section') : AdminPhrase('create_section'))).'</button>
	</div>';

  echo '
  </form>';

} //DisplaySectionForm


// ############################## DELETE IMAGE ################################

function DeleteImage($imageid, $silent=false)
{
  global $refreshpage, $GalleryBase;

  $result = false;
  if(!empty($imageid))
  {
    $result = $GalleryBase->DeleteMediaEntry($imageid,$silent);
  }
  if(empty($silent))
  {
    PrintRedirect($refreshpage, 1);
  }
  return $result;

} //DeleteImage


// ############################## UPDATE IMAGE ################################

function UpdateImage($action='updateimage')
{
  global $DB, $refreshpage, $sdlanguage, $GalleryBase;

  if(!CheckFormToken())
  {
    RedirectPage($refreshpage,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return false;
  }

  $isUpdate = ($action=='updateimage');

  $imageid         = (!$isUpdate ? 0 : GetVar('imageid', 0, 'whole_number',true,false));
  $sectionid       = GetVar('sectionid', 1, 'whole_number',true,false);
  $activated       = empty($_POST['activated'])     ? 0 : 1;
  $allowcomments   = empty($_POST['allowcomments']) ? 0 : 1;
  $allow_ratings   = empty($_POST['allow_ratings']) ? 0 : 1;
  $showauthor      = empty($_POST['showauthor'])    ? 0 : 1;
  $author          = sd_substr(trim(GetVar('author','','string',true,false)),0,64);
  $title           = sd_substr(trim(GetVar('title','','string',true,false)),0,128);
  $description     = trim(GetVar('description','','string',true,false));
  $tags            = trim(GetVar('tags','','string',true,false));
  $image           = isset($_FILES['image']) ? $_FILES['image'] : array('error'=>'4');
  $filetype        = isset($image['type']) ? (string)$image['type'] : '';
  $thumbnail       = isset($_FILES['thumbnail']) ? $_FILES['thumbnail'] : false;
  $regen_midsize   = $isUpdate && !empty($_POST['regen_midsize']);
  $regen_thumbnail = $isUpdate && !empty($_POST['regen_thumbnail']);
  //SD344: Media URL support
  $media_type      = GetVar('media_type',0,'whole_number',true,false);
  $media_url       = GetVar('media_url','','string',true,false);
  $px_width        = GetVar('px_width',0,'whole_number', true, false);
  $px_height       = GetVar('px_height',0,'whole_number', true, false);
  //SD362:
  $update_date     = !empty($_POST['update_date']);
  $fetch_thumb_url = trim(GetVar('fetch_thumb_url','','string',true,false));
  $fetch_thumb     = !empty($_POST['fetch_thumb']) && ($fetch_thumb_url!='');
  $private         = GetVar('private',false,'bool',true,false);
  $duration        = Is_Valid_Number(GetVar('duration',0,'whole_number', true, false),0,0,99999);
  $owner           = GetVar('owner','','string',true,false);
  $lyrics          = GetVar('lyrics','','string',true,false);
  $pl_image        = GetVar($GalleryBase->pre.'image', array(), 'array', true, false);
  $pl_thumbfile    = GetVar($GalleryBase->pre.'thumbnail', array(), 'array', true, false);

  $sql = '';
  $old_img = false;
  if($isUpdate)
  {
    // In case the image could not be found, display error
    $DB->result_type = MYSQL_ASSOC;
    if(!$imageid ||
       !($old_img = $DB->query_first("SELECT IFNULL(filename,'') filename,".
                                     " IFNULL(folder,'') folder,".
                                     ' IFNULL(media_type,0) media_type'.
                                     ' FROM '.$GalleryBase->images_tbl.
                                     ' WHERE imageid = %d', $imageid)))
    {
      PrintRedirect($refreshpage, 1, $GalleryBase->language['err_image_not_found']);
      return;
    }

    // *** Delete image? ***
    if(!empty($_POST['deleteimage']))
    {
      $this->DeleteImage($imageid, true);
      PrintRedirect($refreshpage, 1, $GalleryBase->language['watchdog_deleted']);
      return;
    }

    if($update_date) //SD362: reset date?
    {
      $sql = ', datecreated = '.TIME_NOW;
    }
  }

  // Section not found? Smells fishy!
  if(!$DB->query_first('SELECT 1 FROM '.$GalleryBase->sections_tbl.
                       ' WHERE sectionid = %d', $sectionid))
  {
    if(defined('IN_ADMIN'))
      PrintRedirect($refreshpage, 1, $GalleryBase->language['err_section_not_found']);

    return;
  }

  //SD343: don't allow dangerous titles, file names or descriptions
  if( preg_match("#<script|<html|<head|<\?php|<title|<body|<pre|<table|<a(\s*)href|<img|<plaintext|<cross\-domain\-policy#si", sd_unhtmlspecialchars($title)) ||
      preg_match("#<script|<html|<head|<\?php|<title|<body|<pre|<table|<plaintext|<cross\-domain\-policy#si", sd_unhtmlspecialchars($description)) ||
      preg_match("#<script|<html|<head|<\?php|<title|<body|<pre|<table|<a(\s*)href|<img|<plaintext|<cross\-domain\-policy#si", sd_unhtmlspecialchars($tags)) ||
      preg_match("#<script|<html|<head|<\?php|<title|<body|<pre|<table|<a(\s*)href|<img|<plaintext|<cross\-domain\-policy#si", sd_unhtmlspecialchars($author)) ||
      preg_match("#<script|<html|<head|<\?php|<title|<body|<pre|<table|<a(\s*)href|<img|<plaintext|<cross\-domain\-policy#si", sd_unhtmlspecialchars($media_url)))
  {
    DisplayMessage($GalleryBase->language['err_invalid_data_entered'].'<br />', true);
    $this->DisplayImageForm();
    return false;
  }

  // Init some vars
  $owner_id = 0;
  $mt = 0;
  $errors = array();
  $filename = '';
  $folder = (!$isUpdate || !isset($old_img['folder']) ? '' : $old_img['folder']);
  $folder .= (strlen($folder) && (sd_substr($folder,strlen($folder)-1,1)!='/') ? '/' : '');
  $target_folder = $GalleryBase->IMAGEPATH.$folder;

  //SD362: invalid owner name empties owner_id (= maintainer in SD)
  // Previously this was attached to "author", which now is plain text
  if(!empty($owner))
  {
    $tmp = sd_GetForumUserInfo(0, $owner, false);
    if(!empty($tmp['userid']))
    {
      $owner_id = (int)$tmp['userid'];
    }
    unset($tmp);
  }

  // -------------------------
  // Process remote media URL
  // -------------------------
  if(empty($media_url) || (strlen(trim($media_url)) < 5))
  {
    $mt = 0;
    $media_url = '';
  }
  else
  {
    // Is the URL's format recognized and supported?
    if(!$mt = SD_Media_Base::GetMediaType(sd_unhtmlspecialchars($media_url)))
    {
      $mt = 0;
      $media_url = '';
      DisplayMessage($GalleryBase->language['media_url_unsupported'], true);
    }
    else
    {
      //SD362: fill title/description/tags/duration from media, if not already specified
      SD_Media_Base::InitEmbedMedia();
      $media_title = trim(SD_Media_Base::$m_embed->get_title()); // call this first!
      $media_data = SD_Media_Base::$m_embed->get_current_data();
      if(!strlen($title) && strlen($media_title))
      {
        $title = trim($media_title);
        PreCleanValue($title);
        $title = str_replace('&amp;quot;','&quot;',$title);
        $title = str_replace('&amp;','&',$title);
        $title = $DB->escape_string($title);
      }
      if(empty($tags) && isset($media_data['tags']) && strlen(trim($media_data['tags'])))
      {
        $tags = trim($media_data['tags']);
        PreCleanValue($tags);
        $tags = str_replace('&amp;','&',trim($tags));
        $tags = $DB->escape_string($tags);
      }
      if(empty($description) && isset($media_data['description']) && strlen(trim($media_data['description'])))
      {
        $description = trim($media_data['description']);
        PreCleanValue($description);
        $description = str_replace('&amp;','&',trim($description));
        $description = $DB->escape_string($description);
      }
      if(!empty($media_data['w']) && !empty($media_data['h']) &&
         empty($px_width) && empty($px_height))
      {
        $px_width  = Is_Valid_Number($media_data['w'],0,0,9999);
        $px_height = Is_Valid_Number($media_data['h'],0,0,9999);
      }
      if(!empty($media_data['duration']) && empty($duration))
      {
        $duration = Is_Valid_Number($media_data['duration'],0,0,99999);
      }

      //SD362: remove "old" image files when switching to media url
      if($isUpdate && ($old_img !== false) && empty($old_img['media_type']))
      {
        $GalleryBase->DeleteImageFiles($old_img['filename'], $old_img['folder']);
      }
    }
  }

  // Create a new media row NOW to get a valid imageid
  if(!$isUpdate)
  {
    $DB->query('INSERT INTO '.$GalleryBase->images_tbl."
       (sectionid, activated, filename, allowcomments, allow_ratings, showauthor,
        author, title, description, datecreated, px_width, px_height, folder,
        media_type, media_url, owner_id, lyrics, access_view)
       VALUES(%d, %d, '', %d, %d, %d,
        '%s', '%s', '%s', %d, %d, %d, '%s',
        %d, '%s', %d, '', '')",
       $sectionid, $activated, $allowcomments, $allow_ratings, $showauthor,
       $author, $title, $description, TIME_NOW, $px_width, $px_height, $folder,
       $mt, $DB->escape_string(sd_unhtmlspecialchars($media_url)), $owner_id);

    if(!$imageid = $DB->insert_id())
    {
      return false;
    }
  }

  if(!$mt && !is_writable($target_folder))
  {
    $errors[] = AdminPhrase('err_folder_not_writable');
  }

  // init some vars
  $cachepath = ROOT_PATH.'cache/';
  $extention = '';
  $image_uploaded = false;
  $pluploader = false;

  //SD362: check if IMAGE was uploaded by either plupload OR regular file input
  foreach($pl_image AS $temp_fn => $logical_fn)
  {
    $tmp = $cachepath.$temp_fn;
    if(strlen($temp_fn)    && SD_Image_Helper::FilterImagename($temp_fn) &&
       strlen($logical_fn) && SD_Image_Helper::FilterImagename($logical_fn) &&
       file_exists($tmp) && !is_executable($tmp) && !is_executable($cachepath.$temp_fn))
    {
      if($filesize = @filesize($tmp))
      {
        $pluploader = true;
        $plname     = $temp_fn;
      }
    }
    break; #only 1 image upload allowed
  }

  // ****************************************
  // Check uploaded image (plupload or file)
  // ****************************************
  $img_obj = new SD_Image();
  if($pluploader || (isset($image['error']) && empty($image['error']) ))
  {
    if($pluploader)
    {
      if(!$image_uploaded = $img_obj->setImageFile($cachepath.$plname))
      {
        $pluploader = $regen_midsize = $regen_thumbnail = false;
        @unlink($cachepath.$plname);
      }
    }
    else
    {
      $msg = $img_obj->UploadImage('image', $GalleryBase->IMAGEPATH.$folder,
                       $imageid, SD_Media_Base::getImageExtentions(),
                       SD_MG_MAX_FILESIZE, SD_MEDIA_MAX_DIM, SD_MEDIA_MAX_DIM);
      if($msg === true)
      {
        if($img_obj->getErrorCode())
        {
          unset($image); //FAIL!
          $regen_midsize = $regen_thumbnail = false;
        }
        else
        {
          $image_uploaded = true;
        }
      }
      else
      {
        $errors[] = $msg;
      }
    }
  }

  // ***************************************************
  // If uploaded, check extention (GIF support needed?)
  // ***************************************************
  if($image_uploaded)
  {
    if( $GalleryBase->settings['auto_create_thumbs'] &&
        ($img_obj->getImageType() == IMAGETYPE_GIF) &&
        !function_exists('imageCreateFromGIF') )
    {
      $errors[] = $GalleryBase->language['no_gd_no_gif_support'];
    }
    $extention = $img_obj->getImageExt();
  }

  // ***************************************************
  // Process image file, finally
  // ***************************************************
  if($image_uploaded && strlen($extention))
  {
    //SD362: remove "old" image files when switching either media types
    // or the image extention has changed
    if($old_img !== false)
    {
      $old_ext = SD_Media::getExtention($old_img['filename']);
      if( !empty($old_img['media_type']) ||
          (($old_ext!==false) && ($old_ext != $extention)))
      {
        $GalleryBase->DeleteImageFiles($old_img['filename'], $target_folder);
      }
      unset($old_ext);
    }

    // *************************************
    // Set final filename based on image ID
    // *************************************
    $filename = $imageid . '.' . $extention;
    $target_path = $target_folder.$filename;

    // ***********************************************
    // Copy/move the new image to destination folder:
    // ***********************************************
    $updateFilename = false;
    if($pluploader)
    {
      if(!$img_obj->CopyTo($target_path))
      {
        $errors[] = AdminPhrase('error_image_copy_failed');
      }
      else
      {
        $updateFilename = true;
        @unlink($cachepath.$plname); #remove file from "cache" folder
      }
    }
    else
    if(@move_uploaded_file($image['tmp_name'], $target_path))
    {
      $updateFilename = true;
    }
    else
    {
      $errors[] = AdminPhrase('error_image_copy_failed');
    }

    // *********************************************************************
    // If copy/move worked, update image row to have new filename, which is
    // required before any ReGenerateThumbnail() call!
    // *********************************************************************
    if($updateFilename)
    {
      $DB->query("UPDATE {".$GalleryBase->pre."_images}
                 SET filename = '%s'
                 WHERE imageid = %d",
                 $filename, $imageid);
    }

    // *********************
    // Image Security Check
    // *********************
    if(!count($errors) && !SD_Media_Base::ImageSecurityCheck(true, $target_path))
    {
      @unlink($target_path);
      if(!$isUpdate && !empty($imageid))
      {
        $DB->query("DELETE FROM {".$GalleryBase->pre."_images} WHERE imageid = %d", (int)$imageid);
      }
      Watchdog('Media Gallery '.$this->pluginid, 'Malicious image upload attempt by '.
        (empty($userinfo['loggedin']) ? 'guest' : ('user: '.$userinfo['username'])), WATCHDOG_ERROR);
      echo $this->base->language['invalid_image_upload'].'<br />';
      return false;
    }

    // *********************************************************************
    // If no errors, take care of regen options etc.
    // *********************************************************************
    if(!count($errors))
    {
      $mt = 0;
      $media_url = '';

      @chmod($target_path, 0644);

      // Create Mid-Size image from original image:
      if($GalleryBase->settings['create_midsize_images'])
      {
        $regen_midsize = true;
      }

      //SD362: recreate thumbnail if thumb does not exist yet:
      if( (empty($thumbnail) || !empty($thumbnail['error'])) &&
          !file_exists($target_folder.$GalleryBase->TB_PREFIX.$filename) )
      {
        $regen_thumbnail = false; // Prevent 2nd run further down
        $this->ReGenerateThumbnail($imageid, false, 'thumb');

        // In the old days we'd clear the 'filename' column in the DB but
        // unfortunately the thumbnail/midsize code needs it so we'll
        // use "file_exists" instead
        if( empty($GalleryBase->settings['save_fullsize_images']) &&
            is_file($target_path) && file_exists($target_path) )
        {
          @unlink($target_path);
        }
      }
    }

    // Store image dimensions for update:
    $px_width  = (int)$img_obj->getImageWidth();
    $px_height = (int)$img_obj->getImageHeight();

  } // END OF image uploaded/processed
  unset($img_obj);


  // *********************************************************************
  // Process uploaded thumbnail file
  // *********************************************************************
  //SD362: check if IMAGE was uploaded by either plupload OR regular file input
  $thumb_obj = new SD_Image();

  $pluploader = $thumb_uploaded = false;
  foreach($pl_thumbfile AS $temp_fn => $logical_fn)
  {
    $tmp = $cachepath.$temp_fn;
    if(strlen($temp_fn)    && SD_Image_Helper::FilterImagename($temp_fn) &&
       strlen($logical_fn) && SD_Image_Helper::FilterImagename($logical_fn) &&
       file_exists($tmp) && !is_executable($tmp) && !is_executable($cachepath.$temp_fn))
    {
      if($filesize = @filesize($tmp))
      {
        $pluploader = true;
        $plname     = $temp_fn;
      }
    }
    break; #only 1 image upload allowed
  }

  if($pluploader || (isset($thumbnail['error']) && empty($thumbnail['error'])))
  {
    if($pluploader)
    {
      if(!$thumb_uploaded = $thumb_obj->setImageFile($cachepath.$plname))
      {
        $regen_midsize = $regen_thumbnail = false;
      }
      else
      {
        $conf_arr = array();
        $thumb_file = (!empty($mt)?'':$GalleryBase->TB_PREFIX).$imageid.'.';
        // Check to use the main image's extention, if one exists:
        if(($old_img !== false) && !empty($old_img['filename']) &&
           ($thumb_obj->getImageExt() != SD_Media_Base::getExtention($old_img['filename'])))
        {
          $conf_arr['convert_to'] = SD_Media_Base::getExtention($old_img['filename']);
          $thumb_file .= $conf_arr['convert_to'];
        }
        else
        {
          $thumb_file .= $thumb_obj->getImageExt();
        }
        if(true === $thumb_obj->CreateThumbnail($target_folder.$thumb_file,
                                                $GalleryBase->settings['max_thumb_width'],
                                                $GalleryBase->settings['max_thumb_height']))
        {
          $thumb_uploaded = $thumb_obj->setImageFile($target_folder.$thumb_file);
        }
        else
        {
          $thumb_uploaded = false;
        }
      }
      @unlink($cachepath.$plname);
    }
    else
    {
      $msg = SD_Image_Helper::UploadImageAndCreateThumbnail(
                              'thumbnail', !empty($mt),
                              $GalleryBase->IMAGEDIR.$folder, #NOT IMAGEPATH!
                              $imageid.(empty($mt)?'-tmp':''),
                              (!empty($mt)?'':$GalleryBase->TB_PREFIX).$imageid,
                              $GalleryBase->settings['max_thumb_width'],
                              $GalleryBase->settings['max_thumb_height'],
                              SD_MG_MAX_FILESIZE, false, true, TRUE, $thumb_obj);
      $thumb_uploaded = ($msg === true);
      $thumb_file = $imageid.'.'.$thumb_obj->getImageExt();
    }

    if($thumb_uploaded)
    {
      if(!empty($mt))
      {
        $sql .= ", filename = '".$DB->escape_string($thumb_file)."' ".
                ", folder = '".$DB->escape_string($folder)."' "; #gets into DB!
      }
      //Remove old image/thumbnail file
      if(($old_img !== false) && !empty($old_img['filename']) &&
         ($thumb_obj->getImageExt() != SD_Media_Base::getExtention($old_img['filename'])))
      {
        /*
        if(file_exists($target_folder.$GalleryBase->TB_PREFIX.$old_img['filename']))
        {
          @unlink($target_folder.$GalleryBase->TB_PREFIX.$old_img['filename']);
        }
        */
        if(!empty($mt) && file_exists($target_folder.$old_img['filename']))
        {
          @unlink($target_folder.$old_img['filename']);
        }
      }
    }
    else
    {
      $errors[] = $GalleryBase->language['invalid_thumb_upload'];
    }
  }

  // **********************************************************
  // SD362: download image from URL (if no thumb was uploaded)
  // **********************************************************
  if(empty($thumb_uploaded) && $fetch_thumb && !empty($fetch_thumb_url))
  {
    $tmpfile = $imageid;
    if(false !== ($res = $thumb_obj->Download($fetch_thumb_url, $target_folder, $tmpfile, 'gd')))
    {
      $extention = '.' . $thumb_obj->getImageExt();
      $tmpfile .= $extention;
      // Security check
      if(!SD_Media_Base::ImageSecurityCheck(true, $target_folder, $tmpfile))
      {
        $errors[] = $GalleryBase->language['invalid_thumb_upload'];
      }
      else
      {
        // Create thumbnail from image:
        $filename = $imageid.$extention;
        $err = CreateThumbnail($target_folder.$tmpfile, $target_folder.$GalleryBase->TB_PREFIX.$filename,
                               $GalleryBase->settings['max_thumb_width'],
                               $GalleryBase->settings['max_thumb_height'],
                               $GalleryBase->settings['square_off_thumbs']);
        if(isset($err))
        {
          $errors[] = $err.'<br />'.AdminPhrase('thumb_error_image_not_imported');
        }
        else
        {
          @chmod($target_folder.$filename, 0644);
          if(!empty($mt))
          {
            $sql .= ", filename = '".$DB->escape_string($filename)."' ".
                    ", folder = '".$DB->escape_string($folder)."' "; #gets into DB!
          }
        }
      }
    }
    else
    {
      $errors[] = $GalleryBase->language['remote_download_failed'];
    }
  }
  unset($thumb_obj);

  if($regen_midsize || $regen_thumbnail)
  {
    echo '<br />';
  }
  if($regen_midsize)
  {
    $this->ReGenerateThumbnail($imageid, false, 'midsize');
  }
  if($regen_thumbnail)
  {
    $this->ReGenerateThumbnail($imageid, false, 'thumb');
  }
  if($regen_midsize || $regen_thumbnail)
  {
    echo '<br />';
  }

  // Update media entry with possibly changed data (even if new!)
  $DB->query('UPDATE '.$GalleryBase->images_tbl."
  SET sectionid     = %d,
      activated     = %d,
      allowcomments = %d,
      allow_ratings = %d,
      showauthor    = %d,
      author        = '%s',
      title         = '%s',
      description   = '%s' ".$sql.",
      media_type    = %d,
      media_url     = '%s',
      px_height     = %d,
      px_width      = %d,
      owner_id      = %d,
      duration      = %d,
      private       = %d,
      lyrics        = '%s',
      access_view   = '%s'
  WHERE imageid     = %d",
  $sectionid, $activated, $allowcomments, $allow_ratings, $showauthor, $author,
  $title, $description, $mt, $DB->escape_string(sd_unhtmlspecialchars($media_url)),
  $px_height, $px_width, $owner_id, $duration, $private, $lyrics, '',
  $imageid);

  $GalleryBase->UpdateImageCounts();

  // Process Tags
  $GalleryBase->StoreImageTags($imageid, $tags);

  $url = $refreshpage.'&amp;action=displayimageform&amp;imageid='.$imageid;

  if(empty($errors))
    RedirectPage($url,AdminPhrase($isUpdate?'message_image_updated':'message_image_added'),2,false);
  else
    RedirectPage($url,implode('<br />', $errors),3,true);

} //UpdateImage


// ############################################################################


function ReGenerateThumbnail($imageid, $doRedirect=true, $doMidSizeOnly='both')
{
  global $DB, $refreshpage, $GalleryBase;

  if(!empty($imageid) &&
     ($image = $DB->query_first("SELECT IFNULL(filename,'') filename, IFNULL(folder,'') folder, IFNULL(title,'') title".
                                ' FROM '.$GalleryBase->images_tbl.
                                ' WHERE imageid = %d', $imageid)))
  {
    $filename = $image['filename'];
    $folder   = (strlen($image['folder'])?rtrim($image['folder'],'/').'/':'');
    $folder   = $GalleryBase->IMAGEPATH.$folder;
    $title    = $image['title'];

    SD_Media_Base::requestMem();

    if(($doMidSizeOnly == 'both') || ($doMidSizeOnly == 'thumb'))
    {
      $msg = CreateThumbnail($folder . $filename, $folder . $GalleryBase->TB_PREFIX . $filename,
                             $GalleryBase->settings['max_thumb_width'],
                             $GalleryBase->settings['max_thumb_height'],
                             $GalleryBase->settings['square_off_thumbs']);
    }
    if(!empty($msg))
    {
      $errors[] = $GalleryBase->language['thumbnail_error'].' '.$msg;
    }
    else
    {
      if(($doMidSizeOnly == 'both') || ($doMidSizeOnly == 'thumb'))
      {
        echo $GalleryBase->language['thumbnail_created'].' "'.$title.'"<br />';
      }

      if( (($doMidSizeOnly == 'both') && $GalleryBase->settings['create_midsize_images']) || ($doMidSizeOnly == 'midsize') )
      {
        $msg = CreateThumbnail($folder . $filename, $folder . $GalleryBase->MD_PREFIX.$filename,
                               $GalleryBase->settings['max_midsize_width'],
                               $GalleryBase->settings['max_midsize_height'],
                               $GalleryBase->settings['square_off_midsize']);
        if(!empty($msg))
        {
          $errors[] = $GalleryBase->language['midsize_image_error'].' '.$msg;
        }
        else
        {
          echo $GalleryBase->language['midsize_image_created'].' '.$title.'<br />';
        }
      }
    }
    if(isset($errors))
    {
      DisplayMessage($errors, true);
    }
  }

  if($doRedirect)
  {
    PrintRedirect($refreshpage, empty($msg)?1:6);
  }
  return empty($msg);

} //ReGenerateThumbnail


// ########################### DISPLAY IMAGE FORM #############################

function DisplayImageForm()
{
  global $DB, $mainsettings, $refreshpage, $sdlanguage, $sdurl,
         $userinfo, $GalleryBase;

  $imageid = GetVar('imageid', 0, 'whole_number');
  $crumb   = AdminPhrase('add_media');
  if(!empty($imageid) && ($imageid > 0))
  {
    // gather image information
    $DB->result_type = MYSQL_ASSOC;
    $image = $DB->query_first('SELECT * FROM '.$GalleryBase->images_tbl.
                              ' WHERE imageid = %d', $imageid);
    if(empty($image['imageid'])) return false;
    $tags = $GalleryBase->GetImageTags($imageid);
    if(!empty($tags)) $image['tags'] = implode(',', $tags);
    unset($tags);
    $crumb = AdminPhrase('edit_image');
  }
  else
  {
    $imageid = 0;
    if(isset($_POST['SubmitImage']))
    {
      $image = array(
        'author'         => $userinfo['username'],
        'title'          => GetVar('title','','string', true, false),
        'description'    => GetVar('description','','string', true, false),
        'activated'      => (empty($_POST['activated'])?0:1),
        'showauthor'     => (empty($_POST['showauthor'])?0:1),
        'allowcomments'  => (empty($_POST['allowcomments'])?0:1),
        'filename'       => '',
        'folder'         => '',
        'allow_ratings'  => (empty($_POST['allow_ratings'])?0:1),
        'tags'           => GetVar('tags','','string', true, false),
        'media_type'     => GetVar('media_type',0,'whole_number', true, false),
        'media_url'      => GetVar('media_url','','string', true, false),
        'px_width'       => GetVar('px_width',0,'whole_number', true, false),
        'px_height'      => GetVar('px_height',0,'whole_number', true, false),
        'access_view'    => GetVar('media_url','','string', true, false), //SD362
        'private'        => (empty($_POST['private'])?0:1), //SD362
        'duration'       => GetVar('duration',0,'whole_number', true, false), //SD362
        'lyrics'         => GetVar('lyrics','','string', true, false), //SD362
        'owner_id'       => GetVar('owner_id',0,'whole_number', true, false), //SD362
      );
    }
    else
    {
      // create empty array
      $image = array(
        'author'         => $userinfo['username'],
        'title'          => '',
        'description'    => '',
        'activated'      => 1,
        'showauthor'     => 1,
        'allowcomments'  => 1,
        'filename'       => '',
        'folder'         => '',
        'allow_ratings'  => 1,
        'tags'           => '',
        'media_type'     => 0,
        'media_url'      => '',
        'px_width'       => 0,
        'px_height'      => 0,
        'access_view'    => '',
        'private'        => 0,
        'duration'       => 0,
        'lyrics'         => '',
        'owner_id'       => 0
      );
    }
    $image['sectionid'] = GetVar('sectionid', 1, 'whole_number');
  }
  // Check target folder, does section have one?
  $folder = isset($image['folder'])?$image['folder']:'';
  $folder .= (strlen($folder) && (sd_substr($folder,strlen($folder)-1,1)!='/') ? '/' : '');
  $target_folder = $GalleryBase->IMAGEPATH.$folder;
  $target_url    = $GalleryBase->IMAGEURL.$folder;

  $image['owner_id']   = isset($image['owner_id']) ? (int)$image['owner_id'] : 0;
  $image['tags']       = isset($image['tags']) ? $image['tags'] : '';
  $image['media_url']  = (isset($image['media_url']) ? (string)$image['media_url'] : '');
  $image['media_type'] = !empty($image['media_url']) &&
                         (!empty($image['media_type']) ? (int)$image['media_type'] : 0);
  $mt = $image['media_type'];
  $tb_exists = $md_exists = false;

  //SD362: separatation between author and owner data:
  // "Author" is name of creator/copyright owner of the media.
  // "Owner" is a CMS user who has added this media and may edit it on frontpage.
  // The ["owner"] entry is a dummy, the real data is in "owner_id"!
  $image['owner'] = '';
  if(!empty($image['owner_id']))
  {
    $tmp = sd_GetForumUserInfo(1, $image['owner_id'], false);
    if(!empty($tmp['userid']) && !empty($tmp['username']))
    {
      $image['owner'] = $tmp['username'];
    }
    else
    {
      // User wasn't found, so remove owner id!
      $image['owner_id'] = 0;
    }
    unset($tmp);
  }

  $formID = 'p'.$GalleryBase->pluginid.'_upload_form';
  echo '
  <a style="display:block;margin:6px;padding:0px;font-size: 14px; font-weight:bold" href="view_plugin.php?pluginid='.$GalleryBase->pluginid.'&amp;load_wysiwyg=0&amp;action=displayimages&amp;customsearch=1&amp;sectionid='.
  $image['sectionid'].'">&laquo; '.AdminPhrase('view_section_media').'</a>
 
  <form enctype="multipart/form-data" action="'.$refreshpage.'" method="post" id="'.$formID.'" name="'.$formID.'" class="form-horizontal">
  <input type="hidden" name="action" value="'. ($imageid ? 'updateimage' : 'insertimage') .'" />
  <input type="hidden" name="pluginid" value="'.$GalleryBase->pluginid.'" />
  <input type="hidden" name="imageid" value="'.$imageid.'" />
  <input type="hidden" name="SubmitImage" value="1" />
  '.PrintSecureToken();

  StartSection($imageid ? AdminPhrase('edit_image') : AdminPhrase('add_media'), '', true, true);

  echo '
  <div class="form-group">
  	<label class="control-label col-sm-3">' . AdminPhrase('title') . '</label>
   	<div class="col-sm-6">
      <input type="text" class="form-control" name="title" maxlength="128" size="80" value="'.CleanFormValue($image['title']).'" style="width: 95%" />
    </div>
  </div>
  
  <div class="form-group">
    <label class="control-label col-sm-3">' . AdminPhrase('section') . ':</label>
   	<div class="col-sm-6">';
		$this->PrintSectionSelection('sectionid', $image['sectionid'], 'width: 98%; padding: 4px;');
echo '
     </div>
  </div>';

  //SD362: always display original image, otherwise it won't be obvious if
  // a different thumbnail is being uploaded
  $GLOBALS['sd_ignore_watchdog'] = true;
  $imageOK = (!empty($image['filename']) && file_exists($target_folder.$image['filename']) &&
              ($size = @getimagesize($target_folder.$image['filename'])));
  $GLOBALS['sd_ignore_watchdog'] = false;
  if($imageOK)
  {
    //SD362: also display image dimensions
    echo '
	 <div class="well col-sm-9 col-sm-offset-1">
    <h3 class="header blue">Preview</h3>
	<div class="col-sm-9">
        <div id="image_preview" class="align-left">
          <!-- <a href="'.$target_url.$image['filename'].'" rel="image" class="ceebox2" title="'.$image['title'].'"> -->
          <img src="'.$target_folder.$image['filename'].'?'.TIME_NOW.
          '" class="imgpreview" id="mg_image" alt="'.
          $GalleryBase->language['no_media_preview_available'].'" />
          <!-- </a> -->
        </div>
        
		<br />
        <b>'.AdminPhrase('image_size').'</b> &raquo;
        <i><span id="mg_image_dimensions">'.(int)$size[0].'px x '.(int)$size[1].'px</span></i> ('.
        SD_Media_Base::getExtention($image['filename']).')
	</div>';

    //SD362: image filter options
    if(function_exists('imagefilter'))
    {
      echo '
     	<div class="col-sm-3">
        	<ul id="mg_options">
        		<li class="btn btn-primary btn-sm btn-block" id="mg_filter_options_btn" >
					<i class="ace-icon fa fa-adjust"></i> '.$sdlanguage['common_media_image_filters'].'
				</li><br />
        		<li class="btn btn-primary btn-sm btn-block" id="mg_filter_crop_btn">
					<i class="ace-icon fa fa-crop"></i> <span>'.$sdlanguage['common_media_start_cropping'].'</span>
				</li>
        	</ul>
			<div id="cropdata" style="display:none;">
			  <div>
				  <b>X1</b>: <span id="crop_x1"></span>,
				  <b>Y1</b>: <span id="crop_y1"></span><br />
				  <b>X2</b>: <span id="crop_x2"></span>,
				  <b>Y2</b>: <span id="crop_y2"></span><br />
				  <b>&nbsp;W</b>: <span id="crop_w"></span>,
				  <b>&nbsp;H</b>: <span id="crop_h"></span><br />
				  </div>
				  <br />
				  <span class="btn btn-primary" id="mg_crop_apply" style="width:90%">
					<i class="icon-crop"></i> <span>'.$sdlanguage['common_media_applynow'].'</span>
				  </span>
			  </div>
			</div>';
    }
	
	echo '</div>';
  }
  
   echo '
   <div class="clearfix"></div>';

  //SD362: file uploader for image/thumbnail
  // Create an uploaded object to integrate "plupload" by Moxiecode:
  require_once(ROOT_PATH.'includes/class_sd_uploader.php');
  $uploader_config = array(
    'html_id'            => $GalleryBase->pre.'image',  // id of new uploader div container
    'html_upload_remove' => 'image', // hide "old" file input
    'filters'            => ('{title : "Images", extensions : "jpg,jpeg,gif,png,tif,bmp"}'),
    'maxQueueCount'      => 1,
    'removeAfterSuccess' => 'false',
    'afterUpload'        => 'processFile',
    'singleUpload'       => 'true',
    'title'              => ''
  );
  $uploader = new SD_Uploader($GalleryBase->pluginid, $imageid, $uploader_config);
  $image_uploader_JS   = $uploader->getJS();
  $image_uploader_HTML = $uploader->getHTML();

  // Existing entry: show embedded media OR image thumbnail
  if(!empty($imageid) && ($mt || strlen($image['filename'])))
  {
    if(!empty($image['filename']))
    {
      $tb_exists = file_exists($target_folder . $GalleryBase->TB_PREFIX.$image['filename']);
      $md_exists = file_exists($target_folder . $GalleryBase->MD_PREFIX.$image['filename']);
    }

    if($mt) # media url mode
    {
		echo '<div class="well col-sm-6 col-sm-offset-3">
		 <h3 class="header blue">Preview</h3>';
      $settings = GetPluginSettings($GalleryBase->pluginid, 'admin_options');
      $embed_enabled = !empty($settings['admin_media_embedding']);
      SD_Media_Base::InitEmbedMedia();
      if(SD_Media_Base::$m_embed->set_site(sd_unhtmlspecialchars($image['media_url'])))
      {
        $prevDone = false;
        if(!$embed_enabled && !empty($image['filename']) && file_exists($target_folder.$image['filename']))
        {
          $prevDone = true;
          /*
          echo '<a href="'.$image['media_url'].'" title="&raquo; '.SD_Media_Base::$m_embed->get_vendor().
               '" target="_blank"><img src="'.$target_url.$image['filename'].'?'.TIME_NOW.
               '" alt="'.$GalleryBase->language['no_image_available'].'"'.
               ' style="border:none;max-height:300px;max-width:300px" /></a><br />';
          */
        }
        if($embed_enabled || !$prevDone)
        echo SD_Media_Base::getMediaPreview($mt, sd_unhtmlspecialchars($image['media_url']),
                             (empty($image['title']) ? AdminPhrase('untitled') : $image['title']),
                             480, 320, $embed_enabled).'<br />';
        //required for tags+description, before "get_current_data()"!
        $title = trim(SD_Media_Base::$m_embed->get_title()); //call 1st!
        $media_data = SD_Media_Base::$m_embed->get_current_data(); //call 2nd!
        if(!empty($title))
        {
          $title = trim(htmlspecialchars($title));
          $title = str_replace('&amp;quot;','&quot;',$title);
          echo '<br /><b>'.AdminPhrase('title').'</b> &raquo; <i>'.$title.'</i><br />';
        }
        if(!empty($media_data['description']))
        {
          $media_data['description'] = str_replace('&amp;','&',$media_data['description']);
          $media_data['description'] = str_replace('&quot;',"'",trim($media_data['description']));
          echo '<br /><b>'.AdminPhrase('description').'</b> &raquo; <i>'.
            nl2br(/*htmlspecialchars*/($media_data['description'])).'</i><br />';
        }
        if(!empty($media_data['tags']))
        {
          $media_data['tags'] = trim(htmlspecialchars($media_data['tags']));
          $media_data['tags'] = str_replace('&amp;','&',$media_data['tags']);
          $media_data['tags'] = str_replace('&quot;',"'",trim($media_data['tags']));
          echo '<br /><b>'.$GalleryBase->language['tags'].'</b> &raquo; <i>'.$media_data['tags'].'</i><br />';
        }
        if(!empty($media_data['duration']))
        {
          $duration = Is_Valid_Number($media_data['duration'],0,0,99999);
          echo '<br /><b>'.$GalleryBase->language['duration'].'</b> &raquo; <i>'.$duration.'</i><br />';
          if(empty($image['duration']))
          {
            $image['duration'] = Is_Valid_Number($media_data['duration'],0,0,99999);
          }
        }
        if(!empty($media_data['w']) && !empty($media_data['h']))
        {
          $media_data['w'] = Is_Valid_Number($media_data['w'],0,0,9999);
          $media_data['h'] = Is_Valid_Number($media_data['h'],0,0,9999);
          if(empty($image['px_width']) && empty($image['px_height']))
          {
            $image['px_width']  = $media_data['w'];
            $image['px_height'] = $media_data['h'];
          }
          echo '<br /><b>'.AdminPhrase('image_size').'</b> &raquo; <i>'.intval($media_data['w']).'px / '.intval($media_data['h']).'px</i><br />';
        }
      }
      else
      {
        echo '<b>'.$GalleryBase->language['media_url_unsupported'].'</b><br />';
      }
      echo '
      <br />'.AdminPhrase($embed_enabled ? 'admin_embedding_hint' : 'admin_embedding_disabled').'
	  </div>
      ';
    }
    else # image mode
    {
      if(!$imageOK)
      {
        echo AdminPhrase('upload_image_again');
        echo '<br />
      <div class="hr"></div>
      ';
      }
    }
  }

  // Show options to upload an image or specify a media URL
  #      '<br />'.AdminPhrase('php_max_upload_size') . ' ' .
  #      min(@ini_get('post_max_size'),@ini_get('upload_max_filesize')).'
  
  echo '
  	<div class="hr"></div>
    <div class="col-sm-12">
		<label for="media_type_check1">
        <input class="ace" id="media_type_check1" name="media_type" type="radio" value="0"'. (empty($mt)?' checked="checked" ':'').'/>
        <span class="lbl bigger bolder"> '.AdminPhrase('upload_media_file').'</span>
      	</label>
      <div id="mg_type_image">
        '.$image_uploader_HTML .'
        <input id="image" name="image" type="file" size="50"/>
      </div>
    </div>
	<div class="clearfix"></div>
	<div class="hr"></div>
	
 <div class="col-sm-12">
      <label for="media_type_check2">
        <input class="ace" id="media_type_check2" name="media_type" type="radio" value="1"'.(empty($mt)?'':' checked="checked" ').'/>
        <span class="lbl bigger bolder"> '.AdminPhrase('embed_media_url').'</span>
      </label>
      <div class="col-sm-6 col-sm-offset-3" id="mg_type_url"'.(empty($image['media_type'])?' style="display: none;"':'').'>
        <div class="gallery_media_url_check input-group">
		
          <input type="text" id="media_url" name="media_url" maxlength="255" class="form-control" value="'.$image['media_url'].'" />
		  <span class="input-group-addon">
          <a href="#" onclick="javascript:return false;" title="'.$GalleryBase->language['click_to_verify_media_url'].'" class="status_link_small media_ok"  style="display: '.
          ($mt ? 'block': 'none').'"><i class="ace-icon fa fa-check green bigger-130"></i></a>
          <a href="#" onclick="javascript:return false;" title="'.$GalleryBase->language['click_to_verify_media_url'].'" class="status_link_small media_error" style="display: '.
          (!$mt ? 'block': 'none').'"><i class="ace-icon fa fa-times red bigger-130"></i></a></a>
          <a href="#" id="check_indicator" onclick="javascript:return false;" class="status_link_small" style="display: none">
          <i class="ace-icon fa fa-refresh green bigger-130"></i></a>
		  </span>
        </div>
		<span class="helper-text">'.AdminPhrase('media_url_hint').'</span>
      </div>
    </div>
	<div class="clearfix"></div>
	<div class="hr"></div>';
	
  

  //SD343: folder only relevant for images, not media urls
  //SD362: folder now always displayed since type can be changed now
  if(isset($image['folder']) && strlen($image['folder']))
  {
    echo '<div class="form-group">
			<label class="control-label col-sm-3">'.AdminPhrase('folder').'</label>
			<div class="col-sm-6"><p class="form-control-static"> '.$image['folder'] . '</p>
		</div>
	</div>';
  }

  echo '
 <div class="form-group">
 	<label class="control-label col-sm-3">' . AdminPhrase('description') . '</label>
   <div class="col-sm-6">
    ';

  DisplayBBEditor($GalleryBase->allow_bbcode, 'description', $image['description'], 'form-control', 80, 10);

  echo '
        </div>
</div>';

  //SD344: allow to edit embedding dimensions
  if($mt)
  {
    echo '
  <div class="form-group">
    <label class="control-label col-sm-3">' . AdminPhrase('media_embed_width') . '</label>
   <div class="col-sm-6">
      <input type="text" class="form-control"  name="px_width" value="'.(int)$image['px_width'].'" />
      <span class="helper-text">'.AdminPhrase('media_embed_dim_hint').'</span>
	</div>
</div>
  <div class="form-group">
    <label class="control-label col-sm-3">' . AdminPhrase('media_embed_height') . '</label>
   <div class="col-sm-6">
      <input type="text" class="form-control" size="4" maxlength="4" name="px_height" value="'.(int)$image['px_height'].'" />
      <span class="helper-text">'.AdminPhrase('media_embed_dim_hint').'</span>
    </div>
</div>';
  }

  // Thumbnail
  echo '
  <div class="form-group">
    <label class="control-label col-sm-3">' . AdminPhrase('thumbnail') . '</label>
   <div class="col-sm-6">';

  // Check if an image was uploaded, which is also set if thumb for media url was uploaded!
  if(!empty($imageid) && ($mt || !empty($image['filename'])))
  {
    if(!$tb_exists && !$md_exists)
    {
      echo '
      <p class="red form-control-static">'.AdminPhrase('no_thumb_found').'<br />';
      if(file_exists($target_folder.$image['filename']))
      {
        echo AdminPhrase('use_regenerate_option');
      }
      else
      {
        echo AdminPhrase('upload_image_again');
      }
      echo '</p>';
    }
    else
    {
      $prefix = ($tb_exists ? $GalleryBase->TB_PREFIX : ($md_exists ? $GalleryBase->MD_PREFIX:''));
      echo '
	  <p class="form-control-static">
        <a href="'.$target_url.$prefix.$image['filename'].'" rel="image" class="ceebox" title="'.$image['title'].'">'.
        SD_Image_Helper::GetThumbnailTag($target_folder.$prefix.$image['filename'],'','',AdminPhrase('no_thumbnail'),false,true,300,300).
        '</a><br />';

      //SD362: display image dimensions
      $GLOBALS['sd_ignore_watchdog'] = true;
      if(!empty($image['filename']) && file_exists($target_folder.$prefix.$image['filename']) &&
         ($size = @getimagesize($target_folder.$prefix.$image['filename'])))
      {
        echo '<b>'.AdminPhrase('image_size').'</b> &raquo; <i>'.
              (int)$size[0].'px / '.(int)$size[1].'px</i><br />';
      }
      $GLOBALS['sd_ignore_watchdog'] = false;
	  echo '</p>';
    }
  }
  
  echo '</div>
  </div>';

  //SD362: added option to download thumb from remote URL
  //SD362: changed: thumb upload is now always allowed
  $tmp = !empty($media_data['medium']) ? $media_data['medium'] :
         (!empty($media_data['small']) ? $media_data['small'] : '');
  $checked = ($tmp > '') &&
             (empty($image['filename']) || !file_exists($target_folder.$GalleryBase->TB_PREFIX.$image['filename']));

  $uploader->setValue('html_id', $GalleryBase->pre.'thumbnail'); // id of new uploader div container
  $uploader->setValue('html_upload_remove', 'thumbnail'); // hide "old" file input
  $thumb_uploader_JS   = $uploader->getJS();
  $thumb_uploader_HTML = $uploader->getHTML();

  echo '
    <div class="col-sm-12">
    '.$thumb_uploader_HTML.'<br />
    <input id="thumbnail" name="thumbnail" type="file" size="50" />
    </div>

    <div class="hr"></div>
	<div class="clearfix"></div>

    <div class="form-group">
		<label class="control-label col-sm-3">'.$GalleryBase->language['remote_thumbnail_download'].'</label>
		<div class="col-sm-6">
    		<input type="checkbox" class="ace" name="fetch_thumb" value="1"'.($checked?' checked="checked" ':'').'/><span class="lbl"> '.AdminPhrase('download_remote_thumb').'</span><br />
    		<input type="text" class="form-control" name="fetch_thumb_url" value="'.$tmp.'" size="60" />';

  //SD362: preview of remote thumb via iframe (may not be just a simple image!)
  if(!empty($tmp))
  {
    echo '
    <br />
    <iframe width="100%" src="'.$tmp.'" scrolling="no" frameborder="0" style="border:none;max-height:300px"></iframe>';
  }
  echo '
        </div>
</div>';

  // For existing image offer options to regenerate thumb/midsize images
  if(!empty($imageid) && (!$mt || strlen($image['filename'])) &&
     file_exists($target_folder.$image['filename']))
  {
    echo '
     <div class="form-group">
		<label class="control-label col-sm-3">' . AdminPhrase('regenerate_thumb') . '</label>
		<div class="col-sm-6">
      <label for="regen_thumbnail">
        <input type="checkbox" class="ace" id="regen_thumbnail" name="regen_thumbnail" value="1" /><span class="lbl"> ' . AdminPhrase('confirm_regenerate_thumb') . '</span><br />
      </label>
      <label for="regen_midsize">
        <input type="checkbox" class="ace" id="regen_midsize" name="regen_midsize" value="1" /><span class="lbl"> ' . AdminPhrase('confirm_regenerate_midsize') . '</span>
      </label>
      </div>
    </div>
    ';
  }

  echo '
  <div class="form-group">
    <label class="control-label col-sm-3">' . $GalleryBase->language['tags'] . '</label>
   <div class="col-sm-6">
      <input type="text" class="tags" name="tags" size="20"  value="' . CleanFormValue($image['tags']) . '" />
	  <span class="helper-text">'.$GalleryBase->language['tags_hint'].'</span>
        </div>
</div>
  ';

  //SD362: "duration" (in seconds) and "lyrics" for media urls
  if(!empty($mt))
  {
    echo '
  <div class="form-group">
    <label class="control-label col-sm-3">' . $GalleryBase->language['duration'] . '</label>
   <div class="col-sm-6">
      <input type="text" class="form-control" name="duration" size="5" maxlength="5" value="' . (int)($image['duration']) . '" />
        </div>
</div>
  <div class="form-group">
    <label class="control-label col-sm-3">' . $GalleryBase->language['lyrics'] . '</label>
   <div class="col-sm-6">
    ';
    DisplayBBEditor($GalleryBase->allow_bbcode, 'lyrics', $image['lyrics'], null, 80, 10);
    echo '
        </div>
</div>
  ';
  }

  echo '
  <div class="form-group">
    <label class="control-label col-sm-3">' . AdminPhrase('author') . ':</label>
   <div class="col-sm-9">
      <input type="text" name="author" class="form-control"  maxlength="64" value="'.CleanFormValue($image['author']).'" />
	  <span class="helper-text">'. AdminPhrase('author_hint').'</span>
	</div>
</div>
  <div class="form-group">
    <label class="control-label col-sm-3">' . AdminPhrase('maintainer') . ':</label>
   <div class="col-sm-6">
      <input type="text" id="UserSearch" name="owner" class="form-control" size="64" value="'.CleanFormValue($image['owner']).'" />
      <input type="hidden" id="newOwnerID" name="newAuthorID" value="'.(int)$image['owner_id'].'" />
      <input type="hidden" id="owner_id" value="'.(int)$image['owner_id'].'" />
      <span class="helper-text">'.
      AdminPhrase('maintainer_hint').'</span></br />
      <input type="checkbox" class="ace" name="private" value="1"'.(!empty($image['private'])?' checked="checked"':'').' /><span class="lbl"></span>
      '.AdminPhrase('image_option_private').'
        </div>
</div>';

  //SD362: added "private" and "update_date" options
  echo '
 <div class="form-group">
    <label class="control-label col-sm-3">' . AdminPhrase('options') . '</label>
   <div class="col-sm-6">
      <label for="activated"><input type="checkbox" class="ace" id="activated" name="activated" value="1"'.(empty($image['activated'])?'':' checked="checked"').' /><span class="lbl"></span> '.AdminPhrase('image_option_publish').'</label>
      <label for="showauthor"><input type="checkbox" class="ace" id="showauthor" name="showauthor" value="1"'.(empty($image['showauthor'])?'':' checked="checked"').' /><span class="lbl"></span> '.AdminPhrase('image_option_display_author').'</label>
      <label for="allowcomments"><input type="checkbox" class="ace" id="allowcomments" name="allowcomments" value="1"'.(empty($image['allowcomments'])?'':' checked="checked"').' /><span class="lbl"></span> '.AdminPhrase('image_option_comments').'</label>
      <label for="allow_ratings"><input type="checkbox" class="ace" id="allow_ratings" name="allow_ratings" value="1"'.(empty($image['allow_ratings'])?'':' checked="checked"').' /><span class="lbl"></span> '.AdminPhrase('image_option_ratings').'</label>
      <label for="update_date"><input type="checkbox" class="ace" id="update_date" name="update_date" value="1" /><span class="lbl"></span> <strong>'.AdminPhrase('update_timestamp').'</strong></label>
        </div>
</div>';

  // Option to delete image only when editing existing entry
  if(!empty($imageid))
  {
    echo '
     <div class="form-group">
    <label class="control-label col-sm-3">' . AdminPhrase('delete_image') . '</label>
   <div class="col-sm-6">
        <label for="deleteimage"><input type="checkbox" class="ace" id="deleteimage" name="deleteimage" value="1" /><span class="lbl"></span>
          <strong><span class="red"> ' . AdminPhrase('confirm_delete_image') . '</span></strong></label>
      </div>
    </div>
    ';
  }

  echo '
  	<div class="center">
      <button class="btn btn-info" type="submit" value=""><i class="ace-icon fa fa-check"></i> ' . AdminPhrase($imageid ? 'update_image' : 'add_media') . '</button>
	 </div>
    
  ';

  echo '</form>';

  if($imageOK && function_exists('imagefilter'))
  {
    echo '
  <div id="mg_options_container" style="display:none">
  <form class="mg_form_options" action="'.$sdurl.'plugins/'.$GalleryBase->plugin_folder.'/img.php?imageid='.$imageid.'" method="post">
    <input type="hidden" id="mg_imageid" name="imageid" value="'.$imageid.'" />
    '.PrintSecureToken().'
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td>'.$sdlanguage['common_media_blur'].'</td>
      <td>'.$sdlanguage['common_media_emboss'].'</td>
      <td>'.$sdlanguage['common_media_grayscale'].'</td>
      <td>'.$sdlanguage['common_media_mean_removal'].'</td>
    </tr>
    <tr>
      <td width="70"><input class="checkbox" type="checkbox" class="ace" value="1" id="mg_blur" name="blur" /></td>
      <td width="70"><input class="checkbox" type="checkbox" class="ace" value="1" id="mg_emboss" name="emboss" /></td>
      <td width="70"><input class="checkbox" type="checkbox" class="ace" value="1" id="mg_grayscale" name="grayscale" /></td>
      <td width="70"><input class="checkbox" type="checkbox" class="ace" value="1" id="mg_mean_removal" name="meanremoval" /></td>
    </tr>
    <tr>
      <td>'.$sdlanguage['common_media_flip'].'</td>
      <td>'.$sdlanguage['common_media_mirror'].'</td>
      <td>'.$sdlanguage['common_media_negate'].'</td>
      <td> </td>
    </tr>
    <tr>
      <td><input class="checkbox" type="checkbox" class="ace" value="1" id="mg_flip" name="flip" /></td>
      <td><input class="checkbox" type="checkbox" class="ace" value="1" id="mg_mirror" name="mirror" /></td>
      <td><input class="checkbox" type="checkbox" class="ace" value="1" id="mg_negate" name="negate" /></td>
      <td> </td>
    </tr>';
    if(defined('IMG_FILTER_COLORIZE')) //PHP 5.2.5+
    {
      echo '
    <tr>
      <td>'.$sdlanguage['common_media_colorize'].'</td>
      <td>R: #<input class="mg_colorize" type="text" value="" name="colorizer" size="2" maxlength="2" /></td>
      <td>G: #<input class="mg_colorize" type="text" value="" name="colorizeg" size="2" maxlength="2" /></td>
      <td>B: #<input class="mg_colorize" type="text" value="" name="colorizeb" size="2" maxlength="2" /></td>
    </tr>';
    }
    echo '
    <tr>
      <td>'.$sdlanguage['common_media_watermarktext'].'</td>
      <td colspan="3">
        <input class="mg_watertext" type="text" value="" id="mg_watermark_text" name="watermarktext" size="40" maxlength="50" />
      </td>
    </tr>
    <tr>
      <td>'.$sdlanguage['common_media_textcolor'].'</td>
      <td>#<input class="color" type="text" value="" id="mg_text_color" name="wtextcolor" size="6" maxlength="7" style="width: 55px;" /></td>
      <td>'.$sdlanguage['common_media_bgcolor'].'</td>
      <td>#<input class="color" type="text" value="" id="mg_bg_color" name="wbackcolor" size="6" maxlength="7" style="width: 55px;" /></td>
    </tr>
    </table>

    <br />
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td>'.$sdlanguage['common_media_brightness'].'</td>
      <td>'.$sdlanguage['common_media_contrast'].'</td>
      <td>'.$sdlanguage['common_media_gamma'].'</td>
    </tr>
    <tr>
      <td><input class="mg_brightness" type="text" value="" id="mg_brightness" name="brightness" size="5" maxlength="4" /></td>
      <td><input class="mg_contrast" type="text" value="" id="mg_contrast" name="contrast" size="5" maxlength="4" /></td>
      <td><input class="mg_gamma" type="text" value="1.0" id="mg_gamma" name="gamma" size="5" maxlength="5" /></td>
    </tr>

    <tr>
      <td>'.$sdlanguage['common_media_rotate'].'</td>
      <td>'.$sdlanguage['common_media_smoothness'].'</td>';
    if(defined('IMG_FILTER_PIXELATE')) //PHP 5.3+
    {
      echo '
      <td>'.$sdlanguage['common_media_pixelate'].'</td>';
    }
    else
    {
      echo '
      <td> </td>';
    }
    echo '
    </tr>
    <tr>
      <td><input class="mg_rotate" type="text" value="" id="rotate" name="rotate" size="4" maxlength="3" /></td>
      <td><input class="mg_smooth" type="text" value="" id="mg_smooth" name="smooth" size="4" maxlength="3" /></td>';
    if(defined('IMG_FILTER_PIXELATE')) //PHP 5.3+
    {
      echo '
      <td><input class="mg_pixelate" type="text" value="" id="mg_pixelate" name="pixelate" size="3" maxlength="2" /></td>';
    }
    else
    {
      echo '
      <td> </td>';
    }
    echo '
    </tr>
    </table>
    <br />
    <a class="btn btn-primary btn-sm mg_filter_preview" href="#"><i class="icon-camera"></i> '.$sdlanguage['common_media_preview'].'</a> &nbsp;&nbsp;
    <a class="btn btn-danger btn-sm mg_filter_reset" href="#"><i class="icon-mail-reply"></i> '.$sdlanguage['common_media_revert'].'</a> &nbsp;&nbsp;
    <a style="float:right" class="btn btn-success btn-sm mg_filter_apply" href="#"><i class="icon-ok"></i> '.$sdlanguage['common_media_applynow'].'</a>
  </form>
  </div>';
  }

  // Add required JS code, e.g. switch image<->media type display
  echo '
<script type="text/javascript">
//<![CDATA[
if(typeof jQuery !== "undefined") {
function processFile(file_id, file_name, file_title) {
  jQuery("#progress_"+file_id).hide();
  jQuery("#filelist_"+file_id).html("");
  jQuery("#uploader_"+file_id+"_messages").html(\''.addslashes(AdminPhrase('msg_image_uploaded_js')).'\').show();
}
jQuery(document).ready(function() {
  (function($){
    $("input#media_type_check1").click(function(e){
      $("input#media_type_check2").attr("checked",null);
      $("div#mg_type_image").show();
      $("div#mg_type_url").hide();
      return true;
    });
    $("input#media_type_check2").click(function(e){
      $("input#media_type_check1").attr("checked",null);
      $("div#mg_type_image").hide();
      $("div#mg_type_url").show();
      return true;
    });
'.$thumb_uploader_JS.'
'.$image_uploader_JS.'
  })(jQuery);
});
}
//]]>
</script>
';

} //DisplayImageForm


// ############################ DISPLAY SETTINGS ###############################

function DisplaySettings()
{
  global $refreshpage, $GalleryBase;

  $settings_arr = array();
  $settingspage = GetVar('settingspage', '0', 'natural_number');
  $Gallery_Admin = ($GalleryBase->IsAdmin || $GalleryBase->IsSiteAdmin);

  if($Gallery_Admin && (empty($settingspage) or ($settingspage == 1))) $settings_arr[] = 'admin_options';
  if(empty($settingspage) or ($settingspage == 2)) $settings_arr[] = 'upload_settings';
  if(empty($settingspage) or ($settingspage == 3)) $settings_arr[] = 'media_gallery_settings';
  if(empty($settingspage) or ($settingspage == 4)) $settings_arr[] = 'midsize_settings';
  if(empty($settingspage) or ($settingspage == 5)) $settings_arr[] = 'thumbnail_settings';
  if(empty($settingspage) or ($settingspage == 6)) $settings_arr[] = 'sections_settings';

  PrintPluginSettings($GalleryBase->pluginid, $settings_arr, $refreshpage);

  //v35x: display settings for media sites allowed for frontpage upload
  /*
  if(!empty($settingspage) && ($settingspage == 2))
  {
    foreach(SD_Media_Base::$known_media_sites as $key => $vendor)
    {
      echo $key.'<br />';
    }
  }
  */

} //DisplaySettings


// ############################# DELETE IMAGES #################################

function UpdateSelectedImages()
{
  global $DB, $refreshpage, $GalleryBase;

  // Post values
  $thumbids     = isset($_POST['thumbids'])     && is_array($_POST['thumbids'])     ? $_POST['thumbids'] : array();
  $midsizeids   = isset($_POST['midsizeids'])   && is_array($_POST['midsizeids'])   ? $_POST['midsizeids'] : array();
  $moveids      = isset($_POST['imagemoveids']) && is_array($_POST['imagemoveids']) ? $_POST['imagemoveids'] : array();
  $imageids     = isset($_POST['imageids'])     && is_array($_POST['imageids'])     ? $_POST['imageids'] : array();
  $status       = isset($_POST['status'])       && is_array($_POST['status'])       ? $_POST['status'] : array();
  $reassigntoid = isset($_POST['reassigntoid']) ? (int)$_POST['reassigntoid']       : null;
  $movetofolder = isset($_POST['movetofolder']) ? (string)$_POST['movetofolder']    : null;

  // Process images checked for deletion:
  $deleteFailed = false;
  for($i = 0; !$deleteFailed && ($i < count($imageids)); $i++)
  {
    if((int)$imageids[$i] == $imageids[$i])
    {
      if(!$this->DeleteImage($imageids[$i], true))
      {
        $DB->query('UPDATE '.$GalleryBase->images_tbl.
                   ' SET activated = 0 WHERE imageid = %d',
                   $imageids[$i]);
        $deleteFailed = true;
        break;
      }
    }
  }
  if($deleteFailed)
  {
    RedirectPage($refreshpage, AdminPhrase('delete_failed'), 2, true);
    return false;
  }

  if(empty($movetofolder) || !is_string($movetofolder) ||
     !is_dir($GalleryBase->IMAGEPATH.$movetofolder) || !is_writable($GalleryBase->IMAGEPATH.$movetofolder))
  {
    $movetofolder = false;
  }
  $reassigntoid = (!empty($reassigntoid) && is_numeric($reassigntoid) && ($reassigntoid > 0)) ? (int)$reassigntoid : false;

  // Loop over all selected images:
  foreach($status as $id => $value)
  {
    if(isset($id) && is_numeric($id) && ($id > 0) &&
       (($value == '0') || ($value == '1')))
    {
      $DB->query('UPDATE '.$GalleryBase->images_tbl.
                 ' SET activated = %d'.
                 ' WHERE imageid = %d',
                 $value, $id);
    }
  }

  // Loop over all selected images:
  for($i = 0; $i < count($moveids); $i++)
  {
    if(is_numeric($moveids[$i]) && ($moveids[$i] > 0))
    {
      // Process images checked for reassignment them to different section:
      if($reassigntoid)
      {
        $DB->query('UPDATE '.$GalleryBase->images_tbl.
                   ' SET sectionid = %d'.
                   ' WHERE imageid = %d',
                   $reassigntoid, $moveids[$i]);
      }
      // Process images checked to (physically) moving them to different folder:
      if($movetofolder)
      {
        if($image = $DB->query_first("SELECT imageid, filename, IFNULL(folder,'') folder".
                                     ' FROM '.$GalleryBase->images_tbl.
                                     ' WHERE imageid = %d',
                                     $moveids[$i]))
        {
          $GLOBALS['sd_ignore_watchdog'] = true;
          $source_path = $GalleryBase->IMAGEPATH.$image['folder'];
          $source = $source_path.$image['filename'];
          if(is_file($source) && file_exists($source) &&
             @copy($source, $GalleryBase->IMAGEPATH.$movetofolder.$image['filename']))
          {
            @unlink($source);

            $source = $source_path.$GalleryBase->MD_PREFIX.$image['filename'];
            if(is_file($source) && file_exists($source) &&
               @copy($source, $GalleryBase->IMAGEPATH.$movetofolder.$GalleryBase->MD_PREFIX.$image['filename']))
            {
              @unlink($source);
            }

            $source = $source_path.$GalleryBase->TB_PREFIX.$image['filename'];
            if(is_file($source) && file_exists($source) &&
               @copy($source, $GalleryBase->IMAGEPATH.$movetofolder.$GalleryBase->TB_PREFIX.$image['filename']))
            {
              @unlink($source);
            }
          }
          $GLOBALS['sd_ignore_watchdog'] = false;
          $DB->query_first('UPDATE '.$GalleryBase->images_tbl.
                           " SET folder = '%s'".
                           ' WHERE imageid = %d',
                           $movetofolder, $moveids[$i]);
        }
      }
    }
  } //for

  // Fix section's display image that no longer exist within the same section:
  if($reassigntoid)
  {
    $DB->query('UPDATE '.$GalleryBase->sections_tbl.' s'.
               ' SET imageid = 0'.
               ' WHERE s.imageid > 0'.
               ' AND NOT EXISTS'.
               ' (SELECT f.imageid FROM '.$GalleryBase->images_tbl.' f'.
                ' WHERE f.imageid = s.imageid AND f.sectionid = s.sectionid)');
  }

  $GalleryBase->UpdateImageCounts();

  // Process images checked for Thumbnail Regeneration:
  if(!empty($thumbids))
  {
    $regen_ok = true;
    for($i = 0; $regen_ok && ($i < count($thumbids)); $i++)
    {
      if(!empty($thumbids[$i]) && ((int)$thumbids[$i] == $thumbids[$i]) && ($thumbids[$i] > 0))
      {
        $regen_ok = $this->ReGenerateThumbnail($thumbids[$i], false);
      }
    } //for
    if(!$regen_ok)
    {
      RedirectPage($refreshpage, $GalleryBase->language['thumbnail_regen_error'], 2, true);
      return;
    }
  }

  // Process images checked for MidSize Regeneration:
  if(!empty($midsizeids))
  {
    $regen_ok = true;
    for($i = 0; $regen_ok && ($i < count($midsizeids)); $i++)
    {
      if(!empty($midsizeids[$i]) && ((int)$midsizeids[$i] == $midsizeids[$i]) && ($midsizeids[$i] > 0))
      {
        $regen_ok = $this->ReGenerateThumbnail($midsizeids[$i], false, 'midsize');
      }
    } //for
    if(!$regen_ok)
    {
      RedirectPage($refreshpage, $GalleryBase->language['midsize_regen_error'], 2, true);
      return;
    }
  }

  PrintRedirect($refreshpage, 1);

} //UpdateSelectedImages


// ############################################################################
// DISPLAY IMAGES
// ############################################################################

function DisplayImages($user_message=null)
{
  global $DB, $refreshpage, $GalleryBase, $gallery_page, $search;

  $where = 'WHERE';

  // Search for section?
  if(!empty($search['sectionid']))
  {
    if($search['sectionid'] < 0)
    {
      $where .= ($where != 'WHERE' ? ' AND' : '').' IFNULL(i.activated,0) = 0 ';
    }
    else
    if($search['sectionid'] > 0)
    {
      $where .= ($where != 'WHERE' ? ' AND' : '').' i.sectionid = '.(int)$search['sectionid'];
    }
  }

  // Search for images?
  if(strlen($search['image']))
  {
    $where .= ($where != 'WHERE' ? ' AND' : '') .
               " ((i.title LIKE '%" . $DB->escape_string($search['image'])."%')".
               " OR (i.description LIKE '%".$DB->escape_string($search['image'])."%'))";
  }

  // Search for image tag? #SD362
  if(strlen($search['tag']))
  {
    if($DB->table_exists(PRGM_TABLE_PREFIX.'tags'))
    {
      $where .= ($where != 'WHERE' ? ' AND' : '') .
                 " EXISTS(SELECT 1 FROM {tags} t WHERE t.pluginid = ".
                 $GalleryBase->pluginid." AND t.objectid = i.imageid AND t.tag = '" .
                 $DB->escape_string($search['tag']) . "')";
    }
  }

  // Search for status?
  if(strlen($search['status']) && ($search['status'] != 'all'))
  {
    $where .= ($where != 'WHERE' ? ' AND' : '') .
               ' (i.activated = ' . ($search['status']=='online'?1:0) . ')';
  }

  // Search for author?
  if(strlen($search['author']))
  {
    $where .= ($where != 'WHERE' ? ' AND' : '') .
               " (i.author LIKE '%".$DB->escape_string($search['author'])."%')";
  }

  // Search for image width?
  if($search['width'] = Is_Valid_Number($search['width'],0,0,GALLERY_MAX_DIM))
  {
    $where .= ($where != 'WHERE' ? ' AND' : '').' (i.px_width >= '.(int)$search['width'] . ')';
  }
  $where = $where=='WHERE' ? '' : $where;

  $total_rows = $DB->query_first('SELECT count(*) imgcount'.
                                 ' FROM '.$GalleryBase->images_tbl.' i '.
                                 $where);
  $total_rows = $total_rows['imgcount'];

  $page = $gallery_page;
  $items_per_page = Is_Valid_Number($search['limit'],10,5,9999);

  if($total_rows && (($page-1)*$items_per_page > $total_rows))
  {
    //SD 2012-11-16: fix paging (ceil instead of floor)
    $page = ceil($total_rows / $items_per_page);
    if($page < 1) $page = 1;
  }
  $limit = ' LIMIT '.(($page-1)*$items_per_page).','.$items_per_page;

  switch($search['sorting'])
  {
    case '0': // Newest First
      $order = 'datecreated DESC';
      break;

    case '1': // Oldest First
      $order = 'datecreated ASC';
      break;

    case '2': // Alphabetically A-Z
      $order = 'title ASC';
      break;

    case '3': // Alphabetically Z-A
      $order = 'title DESC';
      break;

    case '4': // Author A-Z
      $order = 'author ASC';
      break;

    case '5': // Author Z-A
      $order = 'author DESC';
      break;

    default:
      $order = 'datecreated DESC';
  }

  $getimages = $DB->query("SELECT i.*, s.name section_name, IFNULL(s.folder,'') section_folder".
                          ' FROM '.$GalleryBase->images_tbl.' i'.
                          ' INNER JOIN '.$GalleryBase->sections_tbl.' s ON s.sectionid = i.sectionid '.
                          $where .
                          ' ORDER BY i.'.$order.' '.$limit);

  // ######################################################################
  // DISPLAY COMMENTS SEARCH BAR
  // ######################################################################

  echo '
  <form action="'.$refreshpage.'&amp;action=display_images" id="searchimages" name="searchimages" method="post">
  '.PrintSecureToken();

  StartSection(AdminPhrase('filter_title'));

  #SD362: display tags
  require_once(SD_INCLUDE_PATH.'class_sd_tags.php');
  $tconf = array(
    'elem_name'   => 'searchtag',
    'elem_size'   => 1,
    'elem_zero'   => 1,
    'chk_ugroups' => false,
    'pluginid'    => $GalleryBase->pluginid,
    'objectid'    => 0,
    'tagtype'     => 0,
    'ref_id'      => 0,
    'allowed_id'  => false,
    'selected_id' => $search['tag'],
    'names_only'  => true #SD362
  );
  $tags_select = SD_Tags::GetPluginTagsAsSelect($tconf);

  echo '
  <input type="hidden" name="customsearch" value="1" />
  <input type="hidden" name="clearsearch" value="0" />
  <table class="table table-bordered" summary="Filterbar">
  <thead>
  <tr>
    <th>' . $GalleryBase->language['search']. '</th>
    <th>' . AdminPhrase('tags_title'). '</th>
    <th>' . AdminPhrase('author') . '</th>
    <th>' . AdminPhrase('status') . '</th>
    <th>' . AdminPhrase('filter_width') . '</th>
    <th>' . AdminPhrase('section') . '</th>
    <th width="120">' . AdminPhrase('section_sort_order_title') . '</th>
    <th>' . AdminPhrase('limit') . '</th>
    <th>' . AdminPhrase('filter') . '</th>
  </tr>
  </thead>
  <tbody>
  <tr>
    <td><input type="text" name="searchimage"  style="width: 90%;" value="' . $search['image'] . '" /></td>
    <td>'.$tags_select.'</td>
    <td><input type="text" name="searchauthor" style="width: 90%;" value="' . $search['author'] . '" /></td>
    <td>
      <select name="searchstatus" style="width: 95%;">
        <option value="all" ' .     ($search['status'] == 'all'     ? 'selected="selected"':'') .'>---</option>
        <option value="online" ' .  ($search['status'] == 'online'  ? 'selected="selected"':'') .'>'.AdminPhrase('online').'</option>
        <option value="offline" ' . ($search['status'] == 'offline' ? 'selected="selected"':'') .'>'.AdminPhrase('offline').'</option>
      </select>
    </td>
    <td>
      <select name="searchwidth" style="width: 95%;">
        <option value="all" ' .  ($search['width'] == 'all'  ? 'selected="selected"':'') .'>---</option>
        <option value="400" ' .  ($search['width'] == '400'  ? 'selected="selected"':'') .'>&gt; 400</option>
        <option value="800" ' .  ($search['width'] == '800'  ? 'selected="selected"':'') .'>&gt; 800</option>
        <option value="1000" ' . ($search['width'] == '1000' ? 'selected="selected"':'') .'>&gt; 1000</option>
        <option value="1200" ' . ($search['width'] == '1200' ? 'selected="selected"':'') .'>&gt; 1200</option>
        <option value="1600" ' . ($search['width'] == '1600' ? 'selected="selected"':'') .'>&gt; 1600</option>
        <option value="2000" ' . ($search['width'] == '2000' ? 'selected="selected"':'') .'>&gt; 2000</option>
      </select>
    </td>
    <td>';

  $this->PrintSectionSelectionEx('searchsection', $search['sectionid'], 0);

  echo '
    </td>
    <td>
      <select name="searchsorting" style="width: 95%;">
        <option value="0" '.($search['sorting'] == 0?'selected="selected"':'') .'>'.AdminPhrase('newest_first').'</option>
        <option value="1" '.($search['sorting'] == 1?'selected="selected"':'') .'>'.AdminPhrase('oldest_first').'</option>
        <option value="2" '.($search['sorting'] == 2?'selected="selected"':'') .'>'.AdminPhrase('alpha_az').'</option>
        <option value="3" '.($search['sorting'] == 3?'selected="selected"':'') .'>'.AdminPhrase('alpha_za').'</option>
        <option value="4" '.($search['sorting'] == 4?'selected="selected"':'') .'>'.AdminPhrase('author_name_az').'</option>
        <option value="5" '.($search['sorting'] == 5?'selected="selected"':'') .'>'.AdminPhrase('author_name_za').'</option>
      </select>
    </td>
    <td style="width: 40px;"><input type="text" name="searchlimit" maxlength="5" style="width: 35px;" value="'.$search['limit'].'" size="2" /></td>
    <td class="center align-middle" style="width: 50px;">
      <input type="submit" value="'.AdminPhrase('search').'" style="display:none" />
      <a class="tip" id="images-submit-search" href="#" onclick="return false;" title="'.
        AdminPhrase('apply_filter').'"><i class="ace-icon fa fa-search blue bigger-110"></i></a>&nbsp;
		 <a class="tip" id="images-clear-search" href="#" onclick="return false;" title="'.
        AdminPhrase('clear_filter').'"><i class="ace-icon fa fa-trash-o red bigger-110"></i></a>
    </td>
  </tr>
  </table>
  ';
  EndSection();
  echo '
  </form>
  ';

  if(empty($total_rows) || empty($total_rows[0]))
  {
    DisplayMessage(AdminPhrase('no_images_found'));
    return;
  }
  echo '
  <form action="'.$refreshpage.'" method="post" id="media_list" name="media_list" class="form-horizontal">
  <input type="hidden" name="action" value="updateselectedimages" />
  <input type="hidden" name="pluginid" value="'.$GalleryBase->pluginid.'" />
  <input type="hidden" name="page" value="'.$page.'" />
  '.PrintSecureToken();

  $showTagsColumn = !empty($GalleryBase->settings['display_tags_column']);
  $totalColumns   = $showTagsColumn ? 12 : 11;

  StartSection(AdminPhrase('images'));
  echo '
  <table class="table table-bordered table-striped">
  <thead>
  <tr>
    <th  >' . AdminPhrase('preview') . '</th>
    <th width="15%">' . AdminPhrase('edit_image') . '</th>';
  if($showTagsColumn)
  {
    echo '
    <th width="15%"><span class="sprite sprite-tags"></span>' . AdminPhrase('tags_title') . '</th>';
  }
  echo '
    <th width="10%">' . AdminPhrase('section') . '</th>
    <th  width="10%">' . AdminPhrase('author') . '</th>
    <th width="10%">' . AdminPhrase('image_size') . '</th>
    <th  width="10%">' . AdminPhrase('uploaded') . '</th>
    <th class="center"> ' . AdminPhrase('status') . '</th>
    <th class="center" >
      <input type="checkbox" class="ace" checkall="regen_thumb" name="regen_thumb" value="1" onclick="javascript: return select_deselectAll (\'media_list\', this, \'regen_thumb\');" /><span class="lbl"></span> ' . AdminPhrase('thumb_regenerate') . '
    </th>
    <th class="center">
      <input type="checkbox" class="ace" checkall="regen_midsize" name="regen_midsize" value="1" onclick="javascript: return select_deselectAll (\'media_list\', this, \'regen_midsize\');" /><span class="lbl"></span> ' . AdminPhrase('midsize_regenerate') . '
    </th>
    <th class="center">
      <input type="checkbox" class="ace" checkall="movegroup" name="movegroup" value="1" onclick="javascript: return select_deselectAll (\'media_list\', this, \'movegroup\');" /><span class="lbl"></span> ' . AdminPhrase('move') . '
    </th>
    <th class="center" >
      <input type="checkbox" class="ace" checkall="group" name="group" value="1" onclick="javascript: return select_deselectAll (\'media_list\', this, \'group\');" /><span class="lbl"></span> ' . AdminPhrase('delete') . '
    </th>
  </tr>';

  require_once(SD_CLASS_PATH.'media_embed.php'); //v3.5

  // ######################################################################
  // MAIN DISPLAY LOOP
  // ######################################################################

  while($image = $DB->fetch_array($getimages,null,MYSQL_ASSOC))
  {
    $imageid = (int)$image['imageid'];
    echo '
    <tr>
      <td>
      <input type="hidden" name="mediaids[]" value="'.$imageid.'" />';

    // Check target folder, does section have one?
    $target_folder = $GalleryBase->IMAGEPATH;
    if(!empty($image['folder'])) $target_folder .= $image['folder'];

    //SD343: initial support for Media URL's, e.g. YouTube, Vimeo...
    $site = '';
    $mt = empty($image['media_type'])?0:(int)$image['media_type'];
    if($mt && !file_exists($target_folder.$GalleryBase->TB_PREFIX.$image['filename']))
    {
      echo SD_Media_Base::getMediaPreview($mt, sd_unhtmlspecialchars($image['media_url']),
                           (empty($image['title']) ? AdminPhrase('untitled') : $image['title']),
                           100, 100, false, false);
    }
    else
    {
      $link = $target_folder.$image['filename'];
      if(!file_exists($link)) $link = $target_folder.$GalleryBase->MD_PREFIX.$image['filename'];
      if(!file_exists($link)) $link = $target_folder.$GalleryBase->TB_PREFIX.$image['filename'];
      echo '<a class="mytooltip ceebox tip" href="'.$link.'" rel="image" title="'.
        htmlspecialchars('<b>'.(empty($image['title'])?AdminPhrase('untitled'):$image['title']).
        '</b>', ENT_COMPAT);
      if(!empty($image['folder']))
      {
        echo htmlspecialchars('<br />'.AdminPhrase('folder').' '.$image['folder'], ENT_COMPAT);
      }
      echo '">';

      $src = $target_folder.$GalleryBase->TB_PREFIX.$image['filename'];
      /*
      echo '<img src="'.$src.'" alt="'.htmlspecialchars(AdminPhrase('no_thumbnail')).
           '" style="max-width: '.(int)$GalleryBase->adm_maxThumbW.
           'px; max-height: '.(int)$GalleryBase->adm_maxThumbH.'px;" />';
      */
      echo SD_Image_Helper::GetThumbnailTag($src,'','','',false,($items_per_page<=32));
      //phpThumb watermark example:
      //<img src="../phpThumb.php?src=images/disk.jpg&w=200&fltr[]=wmi|images/watermark.png|BL" alt="">
      //<img src="../phpThumb.php?src=images/small.jpg&fltr[]=wmt|Watermark|20|C|FF0000|arial.ttf|100" border="0" id="imageimg" hspace="0" vspace="0" style="padding: 0px; margin: 0px;">
      //echo '<img src="'.SD_INCLUDE_PATH.'phpthumb/phpThumb.php?src='.ROOT_PATH.$target_folder.$GalleryBase->TB_PREFIX.$image['filename'].
      //     '&fltr[]=wmt|Subdreamer|20|C|FF0000|tahoma.ttf|100" border="0" id="imageimg" hspace="0" vspace="0" style="padding: 0px; margin: 0px;">';
      echo '</a>';
    }

    echo '</td>
      <td>
        <a class="mytooltip" title="'.htmlspecialchars(AdminPhrase('edit_image')).
        '" href="'.$refreshpage.'&amp;action=displayimageform&amp;page='.$page.
        '&amp;imageid='.$imageid.'">' .
        (empty($image['title']) ? AdminPhrase('untitled') : $image['title']).'</a>
      </td>';

    if($showTagsColumn) #SD362
    {
      echo '
      <td>';
      $tags = $GalleryBase->GetImageTags($imageid);
      if(!empty($tags) && count($tags))
      {
        foreach($tags as $idx => $tag)
        {
          $tag = trim($tag);
          if(strlen($tag))
          {
            echo '
            <a href="'.$refreshpage.'&amp;customsearch=1&amp;tag='.urlencode($tag).'">'.$tag.'</a> ';
          }
        } //foreach
      }
      echo '</td>';
    }

    echo '
      <td><a class="mytooltip" title="'.AdminPhrase('edit_section').'" href="' .
        $refreshpage.'&amp;action=displaysectionform&amp;page='.$page.
        '&amp;sectionid='.$image['sectionid'].'">'.
        $image['section_name'].'</a>
      </td>
      <td>'.$image['author'].'</td>
      <td width="100">';
    if(!empty($image['px_width']) && !empty($image['px_width']))
    {
      echo $image['px_width'].' * '.$image['px_height'].' px';
    }
    echo '</td>
      <td width="100">'.DisplayDate($image['datecreated']).'</td>
      <td class="align-middle center" width="5%" >
        <div class="status_switch" style="margin:0 auto; display:inline-block;">
          <input type="hidden" name="status['.$imageid.']" value="'.(empty($image['activated'])?'0':'1').'" />
          <a href="#" onclick="javascript:return false;" class="status_link on btn btn-sm btn-success"  style="display: '.(!empty($image['activated'])? 'block': 'none').'">'.AdminPhrase('online') .'</a>
          <a href="#" onclick="javascript:return false;" class="status_link off btn btn-sm btn-danger" style="display: '.( empty($image['activated'])? 'block': 'none').'">'.AdminPhrase('offline').'</a>
        </div>
      </td>
      <td class="center"><input type="checkbox" class="ace" name="thumbids[]" value="'.$imageid.'" checkme="regen_thumb" /><span class="lbl"></span></td>
      <td class="center"><input type="checkbox" class="ace" name="midsizeids[]" value="'.$imageid.'" checkme="regen_midsize" /><span class="lbl"></span></td>
      <td class="center"><input type="checkbox" class="ace" name="imagemoveids[]" value="'.$imageid.'" checkme="movegroup" /><span class="lbl"></span></td>
      <td class="center"><input type="checkbox" class="ace" name="imageids[]" value="'.$imageid.'" checkme="group" /><span class="lbl"></span></td>
    </tr>';

  } //while

  echo '</table></div>';

  // #################################################
  // FOOTER ROWS WITH BATCH OPERATIONS
  // #################################################

  // Image List Pagination
  if($total_rows > $items_per_page)
  {
    $p = new pagination;
    $p->items($total_rows);
    $p->limit($items_per_page);
    $p->currentPage($page);
    $p->adjacents(3);
    $p->target($refreshpage);
    $p->show();
  }

  echo '<div class="form-group">
  			<label class="control-label col-sm-2 col-sm-offset-6">' . AdminPhrase('move_images') . '</label>
			<div class="col-sm-4">
  			<select class="form-control" name="movetofolder">
					  <option value="0" selected="selected">' . AdminPhrase('no_action') . '</option>';
						echo SD_Image_Helper::GetSubFoldersForSelect(ROOT_PATH.$GalleryBase->IMAGEDIR, '');
 echo '
      </select>
    </div>
	</div>
	<div class="form-group">
  			<label class="control-label col-sm-2 col-sm-offset-6">' . AdminPhrase('reassign_images') . '</label>
			<div class="col-sm-4">
			<select class="form-control" name="reassigntoid">
      <option value="0" selected="selected">' . AdminPhrase('no_action') . '</option>';

  		$this->PrintSectionChildren(0, -1, 0);

  echo '</select>
  <span class="helper-text">' . AdminPhrase('reassign_images_hint') . '</span>
    </div>
	</div>
 	<div class="center">
		<button class="btn btn-info" type="submit" value="" /><i class="ace-icon fa fa-check"></i>' . AdminPhrase('update_images') . '</button>
	</div>
  ';

  echo '
  </form>';

if(!empty($user_message))
{
  echo '
<script type="text/javascript">
//<![CDATA[
if(typeof jQuery !== "undefined") {
jQuery(document).ready(function() {
  (function($){
    $.jGrowl("'.htmlspecialchars($user_message, ENT_COMPAT).'", {
      easing: "swing", life: 2000,
      animateOpen:  { height: "show", width: "show" },
      animateClose: { height: "hide", width: "show" }
    });
  })(jQuery);
});
}
//]]>
</script>
';
}

} //DisplayImages


function GetSectionSort($sortorder)
{
  switch($sortorder)
  {
    case '0': //'Newest First':
      $order = 'datecreated DESC';
      break;

    case '1': //'Oldest First':
      $order = 'datecreated ASC';
      break;

    case '2': //'Alphabetically A-Z'
      $order = 'name ASC';
    break;

    case '3': //'Alphabetically Z-A':
      $order = 'name DESC';
      break;

    default:
      $order = 'name ASC';
      break;
  }

  return $order;

} //GetSectionSort


// ############################################################################
// PRINT SECTION CHILDREN
// ############################################################################

function PrintSectionChildren($parentid, $selected, $exclude, $indent='', $displaycounts = 0, $sort = null)
{
  global $DB, $GalleryBase;

  if(!isset($sort))
  {
    /*
    $sort = $DB->query_first('SELECT value FROM {pluginsettings}'.
                             ' WHERE pluginid = '.$GalleryBase->pluginid.
                             " AND title = 'section_sort_order'");
    $sort = $this->GetSectionSort(isset($sort['value']) ? $sort['value'] : '');
    */
    $sort = $this->GetSectionSort($GalleryBase->settings['section_sort_order']);
  }
  $sort = empty($sort) ? 'name' : $sort;

  $getsections = $DB->query('SELECT sectionid, name'.
                            ' FROM '.$GalleryBase->sections_tbl.
                            ' WHERE parentid = %d'.
                            ' ORDER BY ' . $sort, $parentid);
  while($sections = $DB->fetch_array($getsections,null,MYSQL_ASSOC))
  {
    if($exclude != $sections['sectionid'])
    {
      $name = $indent . ' ' . $sections['name'] .($sections['sectionid'] == 1 ? ' (root)' : '');

      if($displaycounts)
      {
        $imgcount = $DB->query_first('SELECT COUNT(imageid) imgcount'.
                                     ' FROM '.$GalleryBase->images_tbl.
                                     ' WHERE sectionid = %d',
                                     $sections['sectionid']);
        $name .= ' ('.$imgcount['imgcount'].')';
      }

      echo '
      <option value="'.$sections['sectionid'].'" '.
        ($selected == $sections['sectionid'] ? 'selected="selected"' : '').
        ">$name</option>";
    }

    $this->PrintSectionChildren($sections['sectionid'], $selected, $exclude, $indent . '- - ', $displaycounts, $sort);
  }
} //PrintSectionChildren


// ############################################################################
// PRINT SECTION SELECTION
// ############################################################################

// display sections in a selection box
function PrintSectionSelection($name, $selected=null, $exclude=null, $style='')
{
  echo '<select class="form-control" name="'.$name.'"'.
       (!empty($style)?' style="'.$style.'"':'').'>';
       //e.g. width: 260px; padding: 2px;

  $this->PrintSectionChildren(0, $selected, $exclude, '', 1);

  echo '</select>';
}


// ######################## PRINT SECTION SELECTION EX #########################

function PrintSectionSelectionEx($name='sectionid', $selected=null, $exclude=null)
{
  global $DB, $GalleryBase;

  echo '<select name="' .$name. '" style="width: 95%;">';

  if(isset($exclude) && ($exclude=='0'))
  {
    echo '<option value="0">---</option>';
  }
  $this->PrintSectionChildren(0, $selected, '', '', 1);

  if($getofflineimages = $DB->query_first('SELECT count(*) imgcount'.
                                          ' FROM '.$GalleryBase->images_tbl.
                                          ' WHERE IFNULL(activated,0) = 0'))
  {
    echo '<option value="-1">'.AdminPhrase('offline_images').' (' .
         $getofflineimages['imgcount'] . ')</option>';
  }
  echo '</select>';

} //PrintSectionSelectionEx


// ############################## BATCH IMPORT ################################

function BatchImport()
{
  global $DB, $refreshpage, $sdlanguage, $userinfo, $GalleryBase;

  if(!CheckFormToken('', false))
  {
    RedirectPage($refreshpage,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  // get post variables
  $sectionid     = GetVar('sectionid', 0, 'whole_number', true, false);
  $author        = GetVar('author', '', 'string', true, false);
  $uploadlimit   = GetVar('uploadlimit', 10, 'whole_number', true, false);
  $uploadlimit   = Is_Valid_Number($uploadlimit, 10,1,999);
  $activated     = empty($_POST['activated'])    ? 0  : 1;
  $allowcomments = empty($_POST['allowcomments'])? 0  : 1;
  $allow_ratings = empty($_POST['allow_ratings'])? 0  : 1;
  $showauthor    = empty($_POST['showauthor'])   ? 0  : 1;
  $keepfullsize  = empty($_POST['keepfullsize']) ? 0  : 1;
  $moveimages    = empty($_POST['moveimages'])   ? 0  : 1;
  $importfolder  = empty($_POST['importfolder']) ? $GalleryBase->UPLOADDIR : (string)$_POST['importfolder'];
  $notitle       = empty($_POST['notitle'])      ? 0 :  1;

  $auto_create_thumbs    = empty($_POST['auto_create_thumbs']) ? 0 : 1;
  $create_midsize_images = empty($_POST['create_midsize_images']) ? 0 : 1;
  $max_thumb_width    = GetVar('max_thumb_width', 0, 'whole_number', true, false);
  $max_thumb_height   = GetVar('max_thumb_height', 0, 'whole_number', true, false);
  $max_midsize_width  = GetVar('max_midsize_width', 0, 'whole_number', true, false);
  $max_midsize_height = GetVar('max_midsize_height', 0, 'whole_number', true, false);

  $errors = array();
  $DB->result_type = MYSQL_ASSOC;
  if(empty($sectionid) ||
     !($section = $DB->query_first("SELECT IFNULL(folder,'') folder".
                                   ' FROM '.$GalleryBase->sections_tbl.
                                   ' WHERE sectionid = %d',
                                   $sectionid)))
  {
    $errors[] = AdminPhrase('import_invalid_section');
  }
  if( $auto_create_thumbs && ((empty($max_thumb_width)  || ($max_thumb_width > GALLERY_MAX_DIM)) ||
                              (empty($max_thumb_height) || ($max_thumb_height > GALLERY_MAX_DIM))) )
  {
    $errors[] = AdminPhrase('import_thumbsize_error');
  }
  if( $create_midsize_images && ((empty($max_midsize_width)  || ($max_midsize_width > GALLERY_MAX_DIM)) ||
                                 (empty($max_midsize_height) || ($max_midsize_height > GALLERY_MAX_DIM))) )
  {
    $errors[] = AdminPhrase('import_midsize_error');
  }

  // Check source import folder
  $importfolder .= ((sd_substr($importfolder,strlen($importfolder)-1,1)!='/')?'/':'');
  if(is_dir(ROOT_PATH.$importfolder))
  {
    $uploaddir = ROOT_PATH . $importfolder;
  }
  else
  {
    $errors[] = AdminPhrase('import_location_not_found');
  }

  // Check target folder, does section have one?
  $target_folder = $GalleryBase->IMAGEPATH;
  $section_folder = '';
  if(!empty($section['folder']))
  {
    $section_folder = $section['folder'];
    $target_folder .= $section['folder'];
    if(!is_dir($target_folder) || !is_writable($target_folder))
    {
      $errors[] = AdminPhrase('import_section_error');
    }
  }

  // Display Errors if present
  if(!empty($errors))
  {
    DisplayMessage($errors, true);
    $this->DisplayBatchImportForm();
    return;
  }

  // init vars
  $imagesmoved = 0;
  $errors      = '';
  $datecreated = TIME_NOW;
  SD_Media_Base::requestMem();

  //SD360: invalid author name empties author/owner_id!
  $owner_id = 0;
  if(empty($author))
  {
    $author = '';
  }
  else
  {
    $tmp = sd_GetForumUserInfo(0, $author, false);
    if(empty($tmp['userid']))
    {
      $author = '';
    }
    else
    {
      $owner_id = (int)$tmp['userid'];
    }
  }
  $images_moved = '';

  if(false !== ($d = dir($uploaddir)))
  for($i = 0; (($imagesmoved < $uploadlimit) && (false !== ($entry = $d->read()))); $i++)
  {
    if(preg_match("#^\.|\.$|\.php([2-6]?)|\.bat|\.txt|\.inc|\.pdf|\.(p?)html?|\.pl|\.sh|\.js|\.cgi|\.cmd|\.com|\.exe|\.vb|<script|<html|<head|<\?php|<title|<body|<pre|<table|<a(\s*)href|<img|<plaintext|<cross\-domain\-policy#si", $entry) ||
       !is_file($uploaddir.$entry))
    {
      // Skip non-image entry
      continue;
    }
    $filespecs = explode('.',$entry);
    $extention = strtolower($filespecs[count($filespecs)-1]);
    if((count($filespecs) < 2) ||
       ($extention!='jpeg' && !isset(SD_Media_Base::$known_image_types[$extention])))
    {
      continue;
    }
    // get image title
    $title = $notitle ? '' : $filespecs[0]; //SD342

    // else time for all images is the same which screws up everything
    $datecreated++;

    // is it an image?
    $size_err = true;
    $GLOBALS['sd_ignore_watchdog'] = true;
    if($size = @getimagesize($uploaddir . $entry))
    {
      $px_width  = $size[0];
      $px_height = $size[1];
      $filetype = @image_type_to_mime_type($size[2]);
      $size_err = (empty($px_width)  || (intval($px_width)  > GALLERY_MAX_DIM) ||
                   empty($px_height) || (intval($px_height) > GALLERY_MAX_DIM) ||
                   !array_key_exists($filetype, SD_Media_Base::$known_type_ext));
    }
    $GLOBALS['sd_ignore_watchdog'] = false;

    if($size_err)
    {
      echo '<br />'.$entry.' - '.AdminPhrase('import_imagedim_error');
    }
    else
    {
      // addslashes needs to be added to title, title was not cleaned because it came from a $_FILE (not get or post)
      // if its not cleaned, then a single apostrophe will break the sql
      // this only needs to be done for bulk uploading, for single images the
      // title is entered by the user and thus runs through the $_POST cleanup
      $DB->query('INSERT INTO '.$GalleryBase->images_tbl."
        (sectionid, activated, filename, allowcomments, allow_ratings, showauthor,
         author, title, datecreated, description, px_width, px_height, folder, owner_id)
        VALUES (%d, %d, '', %d, %d, %d, '%s', '%s', %d, '', %d, %d, '%s', %d)",
        $sectionid, $activated, $allowcomments, $allow_ratings, $showauthor,
        $author, $DB->escape_string($title), $datecreated,
        $px_width, $px_height, $DB->escape_string($section_folder), $owner_id);

      $imageid   = $DB->insert_id();
      $extention = SD_Media_Base::$known_type_ext[$filetype];
      $filename  = $imageid . '.' . $extention;

      $DB->query('UPDATE '.$GalleryBase->images_tbl.
                 " SET filename = '%s'".
                 ' WHERE imageid = %d',
                 $filename, $imageid);
      $imagesmoved++;

      // First copy image for processing
      $GLOBALS['sd_ignore_watchdog'] = true;
      $res = @copy($uploaddir . $entry, $target_folder.$filename);
      if($res)
      {
        $err = false;
        if(SD_GD_SUPPORT)
        {
          if($auto_create_thumbs)
          {
            $err = CreateThumbnail($target_folder.$filename, $target_folder.$GalleryBase->TB_PREFIX.$filename,
                                   $max_thumb_width, $max_thumb_height,
                                   $GalleryBase->settings['square_off_thumbs']);
          }
          if(empty($err))
          {
            if($create_midsize_images)
            {
              $err = CreateThumbnail($target_folder.$filename, $target_folder.$GalleryBase->MD_PREFIX.$filename,
                                     $max_midsize_width, $max_midsize_height,
                                     $GalleryBase->settings['square_off_midsize']);
            }
            if(empty($err) && empty($keepfullsize))
            {
              // Here we would usually clear the 'filename' column in the DB but unfortunately
              // the thumbnail/midsize code needs it so we'll use file_exists instead
              if(file_exists($target_folder.$filename))
              {
                @unlink($target_folder.$filename);
              }
            }
            $images_moved .= $entry . ' - ' . AdminPhrase('image_imported') . '<br />';
          }
          else
          {
            if(file_exists($target_folder.$filename))
            @rename($target_folder.$filename, $uploaddir.$entry);
            $DB->query('DELETE FROM '.$GalleryBase->images_tbl.
                       ' WHERE imageid = %d',
                       $imageid);
            $errors .= $entry . ' ' . AdminPhrase('thumb_error_image_not_imported') . '<br />';
          }
        }
        else
        {
          $images_moved .= $entry . ' - ' . AdminPhrase('image_imported') . '<br />';
        }

        // If the "Move Images" option was set and no error occured, remove original image
        if(empty($err) && !empty($moveimages))
        {
          @unlink($uploaddir . $entry);
        }
      }
      else
      {
        // move file did NOT work
        // delete db record and report error
        $DB->query('DELETE FROM '.$GalleryBase->images_tbl.
                   ' WHERE imageid = %d', $imageid);
        $errors .= $entry . ' ' . AdminPhrase('image_not_imported') . '<br />';
      }
      $GLOBALS['sd_ignore_watchdog'] = false;
    }

  } // end the for loop

  if($imagesmoved > 0)
  {
    $GalleryBase->UpdateImageCounts($sectionid);
  }

  StartSection(AdminPhrase('import_results'));
  echo '
  <table class="table table-bordered">
  <tr>
    <td>' . AdminPhrase('imported_count') . ' ' .
    $imagesmoved.'<br />'.$images_moved;

  if($errors)
  {
    echo '<br /><br /><strong>'.AdminPhrase('errors_found').'<strong><br />'.$errors;
  }

  echo '<br /><br />
      <a href="'.$refreshpage.'&customsearch=1&sectionid='.$sectionid.'">'.AdminPhrase('return_to_image_gallery').'</a>
    </td>
  </tr>
  </table>';

  EndSection();

} //BatchImport


// ############################### BATCH UPLOAD ###############################

function BatchUpload()
{
  global $DB, $refreshpage, $sdlanguage, $userinfo, $GalleryBase;

  if(!CheckFormToken('', false))
  {
    RedirectPage($refreshpage,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  // get post variables
  $sectionid     = Is_Valid_Number(GetVar('sectionid', 0, 'whole_number', true, false),0,1);
  $activated     = empty($_POST['activated'])    ? 0  : 1;
  $allowcomments = empty($_POST['allowcomments'])? 0  : 1;
  $allow_ratings = empty($_POST['allow_ratings'])? 0  : 1;
  $notitle       = empty($_POST['notitle'])      ? 0 :  1;
  $showauthor    = empty($_POST['showauthor'])   ? 0  : 1;
  $author        = GetVar('author', '', 'string', true, false);
  $tags          = GetVar('tags', '', 'string', true, false);
  $uploadlimit   = GetVar('uploadlimit', 10, 'whole_number', true, false);
  $folder        = GetVar('folder', '', 'string', true, false);
  $files         = isset($_FILES) ? (array)$_FILES : array();

  $auto_create_thumbs    = empty($_POST['auto_create_thumbs']) ? 0 : 1;
  $create_midsize_images = empty($_POST['create_midsize_images']) ? 0 : 1;
  $max_thumb_width    = GetVar('max_thumb_width', 0, 'whole_number', true, false);
  $max_thumb_height   = GetVar('max_thumb_height', 0, 'whole_number', true, false);
  $max_midsize_width  = GetVar('max_midsize_width', 0, 'whole_number', true, false);
  $max_midsize_height = GetVar('max_midsize_height', 0, 'whole_number', true, false);

  $maxFiles = $this->MG_inisize_to_num(@ini_get('max_file_uploads')); // PHP 5.2.12+ (else 0)
  $maxFiles = empty($maxFiles) ? 100 : $maxFiles; // up to 10 concurrent files here

  $DB->result_type = MYSQL_ASSOC;
  $errors = array();
  if(empty($sectionid) ||
     !($section = $DB->query_first('SELECT * FROM '.$GalleryBase->sections_tbl.
                                   ' WHERE sectionid = %d',
                                   $sectionid)))
  {
    $errors[] = AdminPhrase('import_invalid_section');
  }
  if(preg_match("#<script|<html|<head|<\?php|<title|<body|<pre|<table|<a\s+href|<img|<plaintext|<cross\-domain\-policy#si", sd_unhtmlspecialchars($author)))
  {
    $errors[] = $GalleryBase->language['invalid_image_upload'].'<br />';
  }
  if(empty($files))
  {
    $errors[] = AdminPhrase('err_batch_no_files');
  }
  else
  if(count($files) > $maxFiles)
  {
    $errors[] = AdminPhrase('err_batch_toomany_files');
  }
  if($auto_create_thumbs && ((empty($max_thumb_width)  || ($max_thumb_width > GALLERY_MAX_DIM)) ||
                             (empty($max_thumb_height) || ($max_thumb_height > GALLERY_MAX_DIM))) )
  {
    $errors[] = AdminPhrase('import_thumbsize_error');
  }
  if($create_midsize_images && ((empty($max_midsize_width)  || ($max_midsize_width > GALLERY_MAX_DIM)) ||
                                (empty($max_midsize_height) || ($max_midsize_height > GALLERY_MAX_DIM))) )
  {
    $errors[] = AdminPhrase('import_midsize_error');
  }

  // Check target folder, does section have one?
  $target_folder = trim($folder);
  if(empty($target_folder)) $target_folder = $section['folder'];
  if(!empty($target_folder))
  {
    if(stripos($target_folder, ROOT_PATH.$GalleryBase->IMAGEDIR)===0)
    {
      $target_folder = substr($target_folder, strlen(ROOT_PATH.$GalleryBase->IMAGEDIR));
    }
    if(!is_dir($GalleryBase->IMAGEPATH.$target_folder))
    {
      $errors[] = AdminPhrase('import_section_error');
    }
    else
    if(!is_writable($GalleryBase->IMAGEPATH.$target_folder))
    {
      $errors[] = AdminPhrase('err_folder_not_writable');
    }
  }

  // Display Errors if present
  if(!empty($errors))
  {
    DisplayMessage($errors, true);
    $this->DisplayBatchUploadForm();
    return;
  }

  StartSection(AdminPhrase('import_results'));
  echo '
  <table class="table table-bordered">
  <tr>
    <td>';

  // init vars
  $imagesmoved  = 0;
  $errors       = array();
  $images_moved = '';

  foreach($files as $tagname => $image)
  {
    // Skip if no image uploaded
    if(empty($image['size']) || empty($image['name']) || ($image['error'] == 4)) continue;

    $idx = intval(sd_substr($tagname,4,255));
    $title = GetVar('title'.$idx, '', 'string', true, false);
    $filetype = isset($image['type']) ? (string)$image['type'] : '';

    // Perform some sanity checks on image name
    if(preg_match("#\.php([2-6]?)|\.bat|\.p?html?|\.pl|\.sh|\.js|\.cgi|\.cmd|\.com|\.exe|\.vb|\<script|\<html|\<head|\<\?php|\<title|\<body|\<pre|\<table|\<a(\s*)href|\<img|\<plaintext|\<cross-domain-policy#si",
                  sd_unhtmlspecialchars($title)))
    {
      $errors[] = $GalleryBase->language['invalid_image_title'].'<br />';
    }

    // check if image was uploaded
    if($image['error'] > 0)
    {
      $errors[] = AdminPhrase('err_php_upload_error').' '.
                  $GalleryBase->GetFileErrorDescr($image['error']);
    }
    else
    if(empty($image['size']))
    {
      $errors[] = $GalleryBase->language['invalid_image_upload'];
    }
    else
    {
      // Check if image has a valid extention at all
      if(false === ($extention = SD_Media_Base::getExtention($image['name'])))
      {
        $errors[] = $GalleryBase->language['invalid_image_upload'].' ('.$idx.')<br />';
      }

      if(empty($filetype) || !isset(SD_Media_Base::$known_type_ext[$filetype]))
      {
        $errors[] = $GalleryBase->language['invalid_image_type'];
      }

      if($auto_create_thumbs && ($image['type'] == 'image/gif') &&
         !function_exists('imagecreatefromgif'))
      {
        $errors[] = $GalleryBase->language['no_gd_no_gif_support'];
      }
    }

    if(empty($errors) || !count($errors))
    {
      //SD360: invalid author name empties author/owner_id!
      $owner_id = 0;
      if(empty($author))
      {
        $author = '';
      }
      else
      {
        $tmp = sd_GetForumUserInfo(0, $author, false);
        if(empty($tmp['userid']))
        {
          $author = '';
        }
        else
        {
          $owner_id = (int)$tmp['userid'];
        }
      }

      if(!empty($image['tmp_name']) && !is_uploaded_file($image['tmp_name']))
      {
        $errors[] = $GalleryBase->language['invalid_image_upload'];
      }
      else
      {
        // Store the orignal file
        $DB->query('INSERT INTO '.$GalleryBase->images_tbl."
           (sectionid, activated, filename, allowcomments, allow_ratings, showauthor,
            author, title, description, datecreated, px_width, px_height, folder, owner_id)
           VALUES(%d, %d, '', %d, %d, %d, '%s', '%s', '%s', %d, 0, 0, '%s', %d)",
           $sectionid, $activated, $allowcomments, $allow_ratings, $showauthor,
           $author, $title, '' /* Description*/,
           TIME_NOW, $target_folder, $owner_id);

        $imageid = $DB->insert_id();

        $full_folder = $GalleryBase->IMAGEPATH . $target_folder;

        // Process Tags
        $GalleryBase->StoreImageTags($imageid, $tags);

        // Determine final filename based on ID:
        $extention = SD_Media_Base::$known_type_ext[$filetype];
        $filename  = $imageid . '.' . $extention;
        if(!@move_uploaded_file($image['tmp_name'], $full_folder.$filename))
        {
          $errors[] = AdminPhrase('error_image_copy_failed');
        }
        else
        {
          $imagesmoved++;

          SD_Media_Base::requestMem();
          @chmod($full_folder.$filename, 0644);

          // Determine dimensions of image:
          $sql = '';
          $GLOBALS['sd_ignore_watchdog'] = true;
          if(file_exists($full_folder.$filename) && ($size = @getimagesize($full_folder.$filename)))
          {
            $sql = ', px_width = '.(int)$size[0].', px_height = '.(int)$size[1];
          }
          $GLOBALS['sd_ignore_watchdog'] = false;

          // Update image row with final filename and dimensions (if applicable):
          $DB->query('UPDATE '.$GalleryBase->images_tbl.
                     " SET filename = '%s' ".$sql.
                     ' WHERE imageid = %d',
                     $filename, $imageid);

          // Create Mid-Size image from original image:
          if($create_midsize_images)
          {
            $msg = CreateThumbnail($full_folder.$filename, $full_folder.$GalleryBase->MD_PREFIX.$filename,
                                   $GalleryBase->settings['max_midsize_width'],
                                   $GalleryBase->settings['max_midsize_height'],
                                   $GalleryBase->settings['square_off_midsize']);
            if(isset($msg))
            {
              $errors[] = $GalleryBase->language['midsize_image_error'].' '.$msg;
            }
            else
            {
              @chmod($full_folder . $GalleryBase->MD_PREFIX . $filename, 0644);
            }
          }

          if($auto_create_thumbs)
          {
            // Create Thumbnail image from original image:
            $msg = CreateThumbnail($full_folder.$filename, $full_folder.$GalleryBase->TB_PREFIX.$filename,
                                   $GalleryBase->settings['max_thumb_width'],
                                   $GalleryBase->settings['max_thumb_height'],
                                   $GalleryBase->settings['square_off_thumbs']);
            if(isset($msg))
            {
              $errors[] = AdminPhrase('thumb_error_image_not_imported').' '.$msg;
            }
            else
            {
              @chmod($full_folder . $GalleryBase->TB_PREFIX . $filename, 0644);
            }
          }

          if(empty($errors))
          {
            echo AdminPhrase('image_imported').': '.$title.' ('.$image['name'].')<br />';
          }
          else
          {
            echo AdminPhrase('image').' '.$title.' ('.$image['name'].') failed!<br />';
          }
        }
      }

    }

  } // end the for loop

  $GalleryBase->UpdateImageCounts($sectionid);

  echo AdminPhrase('uploaded_count') . ' ' . $imagesmoved.'<br />';

  if($errors)
  {
    DisplayMessage($errors, true);
  }

  echo '<br />
      <a class="btn btn-primary" href="'.$refreshpage.'&action=displayimages&customsearch=1&load_wysiwyg=0&sectionid='.$sectionid.'">'.AdminPhrase('return_to_image_gallery').'</a>
      <br /><br />
    </td>
  </tr>
  </table>';
  EndSection();

} //BatchUpload


// ############################################################################
// DISPLAY BATCH IMPORT FORM
// ############################################################################

function DisplayBatchImportForm()
{
  global $DB, $refreshpage, $sdurl, $userinfo, $GalleryBase, $plugin_folder;

  if(!SD_GD_SUPPORT)
  {
    DisplayMessage(AdminPhrase('gd_disabled'), true);
    return;
  }

  if(empty($GalleryBase->settings['auto_create_thumbs']))
  {
    DisplayMessage(AdminPhrase('enable_image_resizing_required'), true);
  }

  $author                 = empty($_POST['author']) ? '' : (string)$_POST['author'];
  $uploadlimit            = GetVar('uploadlimit', 10, 'whole_number', true, false);
  $sectionid              = GetVar('sectionid', 1, 'whole_number', true, false);
  $importfolder           = empty($_POST['importfolder']) ? $GalleryBase->UPLOADDIR : (string)$_POST['importfolder'];
  $auto_create_thumbs     = isset($_POST['auto_create_thumbs']) ? !empty($_POST['auto_create_thumbs']) : $GalleryBase->settings['auto_create_thumbs'];
  $create_midsize_images  = isset($_POST['create_midsize_images']) ? !empty($_POST['create_midsize_images']) : $GalleryBase->settings['create_midsize_images'];
  $max_thumb_width        = Is_Valid_Number(GetVar('max_thumb_width', 0, 'whole_number', true, false),$GalleryBase->settings['max_thumb_width'],4,1024);
  $max_thumb_height       = Is_Valid_Number(GetVar('max_thumb_height', 0, 'whole_number', true, false),$GalleryBase->settings['max_thumb_height'],4,1024);
  $max_midsize_width      = Is_Valid_Number(GetVar('max_midsize_width', 0, 'whole_number', true, false),$GalleryBase->settings['max_midsize_width'],10,4096);
  $max_midsize_height     = Is_Valid_Number(GetVar('max_midsize_height', 0, 'whole_number', true, false),$GalleryBase->settings['max_midsize_height'],10,4096);

  // Count files
  $count = 0;
  $imagecount = 0;
  $files = array();
  if(false !== ($dir = @opendir(ROOT_PATH . $importfolder)))
  {
    while(false !== ($f = @readdir($dir)))
    {
      if(!preg_match("#^\.|\.$|\.pdf|\.inc|\.php([2-6]?)|\.bat|\.txt|\.(p?)html?|\.pl|\.sh|\.js|\.cgi|\.cmd|\.com|\.exe|\.vb|<script|<html|<head|<\?php|<title|<body|<pre|<table|<a(\s*)href|<img|<plaintext|<cross\-domain\-policy#si", $f))
      {
        $count++;
        $files[] = $f;
        if(strlen($GalleryBase->HasFileImageExt($f)))
        {
          $imagecount++;
        }
      }
    }
    @closedir($dir);
  }

  echo '
  <form id="batchimportform" action="'.$refreshpage.'" method="post" class="form-horizontal">
  <input type="hidden" name="action" value="batchimport" />
  '.PrintSecureToken();

  StartSection(AdminPhrase('import_images'),'',true, true);
  echo '
  <div class="well">'.AdminPhrase('batch_import_hint').'</div>
 	<div class="form-group">
		<label class="control-label col-sm-3">' . AdminPhrase('import_images_from') . '</label>
   		<div class="col-sm-6">
     		<input type="text" class="form-control" name="importfolder" value="'.$importfolder.'" size="60" />
      		<span class="helper-text">'.AdminPhrase('import_images_from_hint').'</span>
		</div>
	</div>
	<div class="col-sm-6 col-sm-offset-3">
      ' . ($imagecount?' '.AdminPhrase('images') . ' - '.$imagecount:'').'
      <strong><a onclick="jQuery(\'input[name=action]\').val(\'displaybatchimportform\').parent().submit(); return false;" href="#"><i class="ace-icon fa fa-refresh green bigger-110"></i> '.
      AdminPhrase('import_recheck_folder').'</a></strong>
     </div><div class="clearfix"></div>';
   // Display list of files
  if(!empty($files))
  {
    @natsort($files);
    echo '<div class="well col-sm-6 col-sm-offset-3">';
    foreach($files as $name)
    {
      echo '<a class="ceebox" target="_blank" href="'.ROOT_PATH.$importfolder.
           str_replace(' ','%20',$name).'" style="margin: 4px;">'.$name.'</a> ';
    }
    echo '</div><div class="clearfix"></div>';
  }
  echo '
  <div class="form-group">
		<label class="control-label col-sm-3">' . AdminPhrase('import_image_count') . '</label>
    	<div class="col-sm-6">
			<input type="text" class="form-control" name="uploadlimit" size="3" maxlength="3" value="'.$uploadlimit.'" /> (1 - 100)
  		</div>
	</div>
  <div class="form-group">
		<label class="control-label col-sm-3">' . AdminPhrase('import_image_location') . '</label>
    	<div class="col-sm-6">';

  $this->PrintSectionSelection('sectionid', $sectionid, null, 'width: 98%; padding: 4px;');

  echo '
    </div>
  </div>
  <div class="form-group">
		<label class="control-label col-sm-3">' . AdminPhrase('import_image_author') . '</label>
    	<div class="col-sm-6">
			<input type="text" class="form-control" name="author" value="'.$userinfo['username'].'" />
		</div>
  </div>
  <h3 class="header blue lighter">' . AdminPhrase('midsize_settings') . '</h3>
  <div class="form-group">
		<label class="control-label col-sm-3">' . AdminPhrase('create_midsize_images') . '</label>
    	<div class="col-sm-6">
			  <input type="checkbox" class="ace" id="create_midsize_images" name="create_midsize_images" value="1" '.($create_midsize_images?'checked="checked"':'').' />
			  <span class="lbl"></span>
				  
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-sm-3">' . AdminPhrase('max_midsize_width') . '</label>
    	<div class="col-sm-6">
				  <input type="text" class="form-control" name="max_midsize_width" maxlength="4" size="4" value="'.$max_midsize_width.'" />
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-sm-3">'. AdminPhrase('max_midsize_height') . '</label>
    	<div class="col-sm-6">
				  <input type="text" class="form-control" name="max_midsize_height" maxlength="4" size="4" value="'.$max_midsize_height.'" />
		</div>
	</div>
	<h3 class="header blue lighter">' . AdminPhrase('thumbnail_settings') . '</h3>
    <div class="form-group">
		<label class="control-label col-sm-3">' . AdminPhrase('auto_create_thumbs') . '</label>
    	<div class="col-sm-6">
          <input type="checkbox" class="ace" id="auto_create_thumbs" name="auto_create_thumbs" value="1" '.($auto_create_thumbs?'checked="checked"':'').' /><span class="lbl"></span>
         </div>
	</div>
      <div class="form-group">
		<label class="control-label col-sm-3">' . AdminPhrase('max_thumb_width') . '</label>
    	<div class="col-sm-6">
          <input type="text" class="form-control" name="max_thumb_width" maxlength="4" size="4" value="'.$max_thumb_width.'" />
		</div>
	</div>
       <div class="form-group">
		<label class="control-label col-sm-3">'. AdminPhrase('max_thumb_height') . '</label>
    	<div class="col-sm-6">
          <input type="text" class="form-control" name="max_thumb_height" maxlength="4" size="4" value="'.$max_thumb_height.'" />
        </div>
      </div>
	  <h3 class="header blue lighter">' . AdminPhrase('options') . '</h3>
 <div class="form-group">
		<label class="control-label col-sm-3"></label>
    	<div class="col-sm-6">
      <label for="activated"><input type="checkbox" class="ace" id="activated" name="activated" value="1" checked="checked" /><span class="lbl"></span> ' . AdminPhrase('image_option_publish') . '</label>
      <label for="notitle"><input type="checkbox" class="ace" id="notitle" name="notitle" value="1" /><span class="lbl"></span> ' . AdminPhrase('import_no_titles') . '</label>
      <label for="keepfullsize"><input type="checkbox" class="ace" id="keepfullsize" name="keepfullsize" value="1" '.($GalleryBase->settings['save_fullsize_images']?'checked="checked"':'').' /><span class="lbl"></span> ' . AdminPhrase('import_option_keep_fullsize') . '</label>
      <label for="showauthor"><input type="checkbox" class="ace" id="showauthor" name="showauthor" value="1" checked="checked" /><span class="lbl"></span> '.AdminPhrase('import_option_author').'</label>
      <label for="allowcomments"><input type="checkbox" class="ace" id="allowcomments" name="allowcomments" value="1" checked="checked" /><span class="lbl"></span> '.AdminPhrase('import_option_comments').'</label>
      <label for="allow_ratings"><input type="checkbox" class="ace" id="allow_ratings" name="allow_ratings" value="1" checked="checked" /><span class="lbl"></span> '.AdminPhrase('import_option_ratings').'</label>
      <br />
      <label for="moveimages"><input type="checkbox" class="ace" id="moveimages" name="moveimages" value="1" checked="checked" /><span class="lbl"></span> '.AdminPhrase('move_imported_images_descr') . '</label>
    </div>
  </div>
  <div class="center">
      <button class="btn btn-info" id="submit_btn" type="submit" value="" /><i class="ace-icon fa fa-check"></i>' . AdminPhrase('import_images') . '
</div></form>';

} //DisplayBatchImportForm


function MG_inisize_to_num($v)
{
  //Based on comments on http://php.net/manual/de/ini.core.php
  //This function transforms the php.ini notation for numbers (like '2M')
  //to an integer (2*1024*1024 in this case)
  if(empty($v)) return 0;
  $v = trim(str_replace(' ','',$v));
  $l = strtoupper(substr($v, -1));
  // If no valid unit is specified, return as integer-bytes
  if(!in_array($l, array('B','G','K','M','P','T')))
  {
    return intval($v);
  }
  // Calc value based on unit; catch exception with high numbers:
  $ret = intval(substr($v, 0, -1));
  try {
    switch($l) {
      case 'B':
        break;
      case 'G':
        $ret *= 1073741824;
        break;
      case 'K':
        $ret *= 1024;
        break;
      case 'M':
        $ret *= 1048576;
        break;
      case 'P':
          $ret *= 1024*1024*1024*1024*1024;
        break;
      case 'T':
        $ret *= 1099511627776;
        break;
    }
  }
  catch (Exception $e) { $ret = PHP_INT_MAX; }
  return $ret;
}

// ######################## DISPLAY BATCH UPLOAD FORM ##########################

function DisplayBatchUploadForm()
{
  global $DB, $refreshpage, $sdurl, $userinfo, $GalleryBase, $plugin_folder;

  if(!SD_GD_SUPPORT)
  {
    DisplayMessage(AdminPhrase('gd_disabled'), true);
    return;
  }

  if(empty($GalleryBase->settings['auto_create_thumbs']))
  {
    DisplayMessage(AdminPhrase('enable_image_resizing_required'), true);
  }

  $tmp = @ini_get('file_uploads');
  if(empty($tmp))
  {
    DisplayMessage(AdminPhrase('err_php_uploads_disabled'),true);
    return false;
  }

  $author                = empty($_POST['author']) ? $userinfo['username'] : (string)$_POST['author'];
  $tags                  = GetVar('tags', '', 'string', true, false);
  $auto_create_thumbs    = isset($_POST['auto_create_thumbs']) ? !empty($_POST['auto_create_thumbs']) : $GalleryBase->settings['auto_create_thumbs'];
  $create_midsize_images = isset($_POST['create_midsize_images']) ? !empty($_POST['create_midsize_images']) : $GalleryBase->settings['create_midsize_images'];
  $max_thumb_width       = Is_Valid_Number(GetVar('max_thumb_width', 0, 'whole_number', true, false),$GalleryBase->settings['max_thumb_width'],4,1024);
  $max_thumb_height      = Is_Valid_Number(GetVar('max_thumb_height', 0, 'whole_number', true, false),$GalleryBase->settings['max_thumb_height'],4,1024);
  $max_midsize_width     = Is_Valid_Number(GetVar('max_midsize_width', 0, 'whole_number', true, false),$GalleryBase->settings['max_midsize_width'],10,4096);
  $max_midsize_height    = Is_Valid_Number(GetVar('max_midsize_height', 0, 'whole_number', true, false),$GalleryBase->settings['max_midsize_height'],10,4096);

  $maxFiles = $this->MG_inisize_to_num(@ini_get('max_file_uploads')); // PHP 5.2.12+ (else 0)
  $maxFiles = empty($maxFiles) ? 10 : min($maxFiles, 10); // up to 10 concurrent files here
  if($maxFiles > 50) $maxFiles = 50;
  $max_file_size = $this->MG_inisize_to_num(@ini_get('upload_max_filesize'));
  $max_post_size = $this->MG_inisize_to_num(@ini_get('post_max_size'));

  StartSection(AdminPhrase('batch_upload'),'',true,true);

  echo '
    <form method="post" enctype="multipart/form-data" id="batchuploadform" action="'.$refreshpage.'" class="form-horizontal">
    '.PrintSecureToken().'
    <div class="well">
		'.AdminPhrase('batch_upload_hint').
      '<br /><strong>'.AdminPhrase('server_restrictions').'</strong> ';
  echo str_replace('[max_size]', DisplayReadableFilesize($max_file_size), AdminPhrase('max_php_filesize_is')).' '.
       str_replace('[max_size]', DisplayReadableFilesize($max_post_size), AdminPhrase('max_uploadsize_is'));
  echo '
    </div>
    <div class="form-group">
		<label class="control-label col-sm-3">' . AdminPhrase('import_image_location') . '</label>
    	<div class="col-sm-6">';

    $this->PrintSectionSelection('sectionid', 1, null, 'width: 98%; padding: 4px;');

    echo '
      </div>
    </div>
    <div class="form-group">
		<label class="control-label col-sm-3">' . AdminPhrase('section_upload_folder') . '</label>
    	<div class="col-sm-6">
        <select name="folder" class="form-control" >
        <option value=""'.(empty($section['folder']) ? ' selected="selected"' : '').'>'.AdminPhrase('default_folder').'</option>
        ';

    echo SD_Image_Helper::GetSubFoldersForSelect(ROOT_PATH.$GalleryBase->IMAGEDIR, '');

    echo '
        </select>
		<span class="helper-text">
        ' . AdminPhrase('default_upload_folder_hint') . '
		</span>
      </div>
    </div>
     <div class="form-group">
		<label class="control-label col-sm-3">' . AdminPhrase('import_image_author') . '</label>
    	<div class="col-sm-6">
        <input type="text" class="form-control" name="author" size="20" value="'.$author.'" />
      </div>
    </div>
     <div class="form-group">
		<label class="control-label col-sm-3">' . $GalleryBase->language['tags'] . '</label>
    	<div class="col-sm-6">
        <input type="text" class="tags" name="tags"  value="' .
        (isset($image)?CleanFormValue($image['tags']):'') . '" />
		<span class="helper text">'.$GalleryBase->language['tags_hint'].'</span>
      </div>
    </div>
    <h3 class="header blue lighter">' . AdminPhrase('midsize_settings') . '</h3>
     <div class="form-group">
		<label class="control-label col-sm-3">
            ' . AdminPhrase('create_midsize_images') . '</label>
    	<div class="col-sm-6">
            <input type="checkbox" class="ace" id="create_midsize_images" name="create_midsize_images" value="1" '.($create_midsize_images?'checked="checked"':'').' />
			<span class="lbl"></span>
          </div>
        </div>
        <div class="form-group">
		<label class="control-label col-sm-3">
            ' . AdminPhrase('max_midsize_width') . ' (8 - 4096)</label>
    	<div class="col-sm-6">
            <input type="text" class="form-control" name="max_midsize_width" maxlength="4" size="4" value="'.$max_midsize_width.'" />
		</div>
	</div>
     <div class="form-group">
		<label class="control-label col-sm-3">
            '. AdminPhrase('max_midsize_height') . ' (8 - 4096)</label>
    	<div class="col-sm-6">
            <input type="text" class="form-control" name="max_midsize_height" maxlength="4" size="4" value="'.$max_midsize_height.'" />
		</div>
        </div>
       <h3 class="header blue lighter">' . AdminPhrase('thumbnail_settings') . '</h3>
      <div class="form-group">
		<label class="control-label col-sm-3">
            ' . AdminPhrase('auto_create_thumbs') . '</label>
    	<div class="col-sm-6">
            <input type="checkbox" class="ace" id="auto_create_thumbs" name="auto_create_thumbs" value="1" '.($auto_create_thumbs?'checked="checked"':'').' />
			<span class="lbl"></span>
         </div>
	</div>
        <div class="form-group">
		<label class="control-label col-sm-3">
            ' . AdminPhrase('max_thumb_width') . ' (4 - 1024)</label>
    	<div class="col-sm-6">
            <input type="text" class="form-control" name="max_thumb_width" maxlength="4" size="4" value="'.$max_thumb_width.'" />
         </div>
		</div>
        <div class="form-group">
		<label class="control-label col-sm-3">
            '. AdminPhrase('max_thumb_height') . ' (4 - 1024)</label>
    	<div class="col-sm-6">
            <input type="text" class="form-control" name="max_thumb_height" maxlength="4" size="4" value="'.$max_thumb_height.'" />
          </div>
        </div>
        <h3 class="header blue lighter">' . AdminPhrase('options') . '</h3>
		 <div class="form-group">
		<label class="control-label col-sm-3"></label>
    	<div class="col-sm-6">
     
        <label for="activated"><input type="checkbox" class="ace" id="activated" name="activated" value="1" checked="checked" /><span class="lbl"></span> ' . AdminPhrase('image_option_publish') . '</label>
        <label for="notitle"><input type="checkbox" class="ace" id="notitle" name="notitle" value="1" /><span class="lbl"></span> ' . AdminPhrase('import_no_titles') . '</label>
        <label for="showauthor"><input type="checkbox" class="ace" id="showauthor" name="showauthor" value="1" checked="checked" /><span class="lbl"></span> '.AdminPhrase('import_option_author').'</label>
        <label for="allowcomments"><input type="checkbox" class="ace" id="allowcomments" name="allowcomments" value="1" checked="checked" /><span class="lbl"></span> '.AdminPhrase('import_option_comments').'</label>
        <label for="allow_ratings"><input type="checkbox" class="ace" id="allow_ratings" name="allow_ratings" value="1" checked="checked" /><span class="lbl"></span> '.AdminPhrase('import_option_ratings').'</label>
      </div>
    </div>
  <br />
  <table class="table table-bordered table-striped">
  <thead>
    <tr>
      <th>' . AdminPhrase('image') .'</th>
      <th>' . AdminPhrase('title') . '</th>
      <th>' . AdminPhrase('browse_image') . '</th>
    </tr>
	</thea>
	<tbody>
    ';

  for($i = 0; $i < $maxFiles; $i++)
  {
    echo '
    <tr>
      <td width="200">Browse for image ' . ($i+1) . ':</td>
      <td class="td3" valign="top"><input type="text" class="input-medium" name="title' . $i . '"  value="'.
      (isset($_POST['title'.$i]) ? $_POST['title'.$i] : ('Image ' . ($i+1))) . '" style="width: 95%" /></td>
      <td class="td3" valign="top"><input type="file" class="file-upload" name="file' . $i . '" size="30" style="width: 95%" /></td>
    </tr>';
  }

  echo "\n    </tbody></table>\n";


  PrintSubmit('batchupload', AdminPhrase('btn_upload_images'), 'batchuploadform', 'fa-check');

  echo "\n</form>\n";
  
  echo'
  <script type="text/javascript">
	// <![CDATA[
  if(typeof(jQuery) !== "undefined"){
  jQuery(document).ready(function(){
    (function($){
  $(".file-upload").ace_file_input();    
  }(jQuery));
  });
}
// ]]>
</script>
';

} //DisplayBatchUploadForm

} //END OF CLASS

// ############################# SELECT FUNCTION ##############################

// make sure php doesnt time out
if(!sd_safe_mode())
{
  $old = $GLOBALS['sd_ignore_watchdog'];
  $GLOBALS['sd_ignore_watchdog'] = false;
  @set_time_limit(3600);
  $GLOBALS['sd_ignore_watchdog'] = $old;
}

$MGS = new MediaGallerySettings();

$function_name = str_replace('_', '', $action);
if(($function_name=='insertimage') || ($function_name=='updateimage')) //SD362
{
  $MGS->UpdateImage($function_name);
}
else
{
  if(method_exists($MGS, $function_name))
  {
    $MGS->$function_name();
  }
  else
    DisplayMessage('Incorrect Function Call: '.htmlspecialchars($function_name).'()', true);
}
