<?php
// DEFINE CONSTANTS
$MediaIncluded = defined('IN_PRGM') && defined('IN_ADMIN');
if(!$MediaIncluded)
{
  define('IN_PRGM', true);
  define('IN_ADMIN', true);
  define('ROOT_PATH', '../');
  // INIT PRGM
  require(ROOT_PATH . 'includes/init.php');
  require_once(ROOT_PATH.'includes/enablegzip.php');
}
define('SELF_MEDIA_PHP', 'media.php');
defined('MEDIA_PREVIEW_WIDTH') || define('MEDIA_PREVIEW_WIDTH', 250);
defined('MEDIA_PREVIEW_HEIGHT') || define('MEDIA_PREVIEW_HEIGHT', 250);
defined('COOKIE_MEDIA_FILTER') || define('COOKIE_MEDIA_FILTER', '_media_filter');
defined('SD_DEFAULT_THUMB_WIDTH') || define('SD_DEFAULT_THUMB_WIDTH', 100);
defined('SD_DEFAULT_THUMB_HEIGHT') || define('SD_DEFAULT_THUMB_HEIGHT', 100);
defined('MEDIA_UPL_ID') || define('MEDIA_UPL_ID',     'uploader_file_media_log');
defined('MEDIA_UPL_TARGET') || define('MEDIA_UPL_TARGET', 'uploader_file_media_messages');
defined('MEDIA_MAX_COLUMNS') || define('MEDIA_MAX_COLUMNS', 4);
defined('MEDIA_DEFAULT_DIR') || define('MEDIA_DEFAULT_DIR', ROOT_PATH.'images/');
defined('BLANK_IMG') || define('BLANK_IMG', SD_INCLUDE_PATH.'css/images/blank.gif');
defined('BLANK_IMG_TAG') || define('BLANK_IMG_TAG', '<img alt="" src="'.BLANK_IMG.'" width="16" height="16" />');

require_once(SD_INCLUDE_PATH.'class_sd_media.php');
SD_Image_Helper::$ImagePath = MEDIA_DEFAULT_DIR;

// ****************************************************************************
// LOAD ADMIN LANGUAGE
// ****************************************************************************
$admin_phrases  = LoadAdminPhrases(3);
$selection_note = $admin_phrases['media_image_selection_note'];
$readonly_note  = $admin_phrases['media_folder_readonly_note'];
$all_pagesizes  = array(0, 12,24,36,48,96,120,240,360,480);

// GET parameters like action and path
$action         = strtolower(GetVar('action', 'displayimages', 'string'));
$imageonly      = GetVar('imageonly', 0, 'bool');
if($action!=='displaymediadetails')
{
  $imageonly = false;
}

$media_images = array();
if(($action=='displayimages') || ($action=='getimages'))
{
  $media_images['video'] = SITE_URL.'includes/images/video.png';
  $media_images['image'] = SITE_URL.'includes/images/image.png';
}

// SD341: Restore previous toolbar flags from cookie
$cookie_read = false;
if(!$imageonly && ($cookie = isset($_COOKIE[COOKIE_PREFIX . COOKIE_MEDIA_FILTER]) ? $_COOKIE[COOKIE_PREFIX . COOKIE_MEDIA_FILTER] : false))
{
  if($cookie = base64_decode($cookie))
  {
    include_once(SD_INCLUDE_PATH.'JSON.php');
    $json = new Services_JSON();
    $cookie = $json->decode($cookie);
    if(!empty($cookie) && is_object($cookie))
    {
      $folderpath   = empty($cookie->folderpath)?MEDIA_DEFAULT_DIR:(string)$cookie->folderpath;
      $folderpath   = rtrim($folderpath,'/').'/';
      $displaymode  = empty($cookie->displaymode)?0:(int)$cookie->displaymode;
      $filter       = isset($cookie->filter)?(string)$cookie->filter:'';
      $filter       = trim(strip_tags(sd_substr($filter,0,50)));
      $page         = empty($cookie->page)?0:Is_Valid_Number($cookie->page,0,0,999);
      $pagesize     = !isset($cookie->pagesize)?36:($cookie->pagesize=='all'?0:Is_Valid_Number($cookie->pagesize, 24, 0, 480)); // multiple of 12
      $sortcolumn   = empty($cookie->sortcolumn)?0:(int)$cookie->sortcolumn;
      $sortorder    = empty($cookie->sortorder)?'desc':(string)$cookie->sortorder;
      $cookie_read  = true;
    }
    unset($json);
  }
}

$function_name  = str_replace('_', '', $action);
if(!$cookie_read)
{
  $folderpath   = GetVar('folderpath', MEDIA_DEFAULT_DIR, 'string');
  $folderpath  .= (substr($folderpath,-1)=='/'?'':'/');
  $displaymode  = GetVar('displaymode', 0, 'natural_number');
  $filter       = GetVar('filter', '', 'string');
  $filter       = trim(strip_tags(substr($filter,0,50)));
  $page         = Is_Valid_Number(GetVar('page', 0, 'natural_number'),0,0,999);
  $pagesize     = GetVar('pagesize', 36, 'string'); // multiple of 12
  $pagesize     = ($pagesize=='all'?0:Is_Valid_Number($pagesize, 24, 0, 480));
  $sortcolumn   = GetVar('sortcolumn', null, 'natural_number');
  $sortorder    = GetVar('sortorder', null, 'string');
}
if(!in_array($pagesize, $all_pagesizes))
{
  $pagesize = 24;
}
if(!isset($sortcolumn))
{
  $sortcolumn = 0;
  $sortorder  = 'desc';
}
else
{
  $sortcolumn = ($sortcolumn > 1) ? 0 : (int)$sortcolumn;
  $sortorder  = ($sortorder=='asc'||$sortorder=='desc')?$sortorder:'asc';
}

// SD343: sanitize path
$folderpath = SanitizeInputForSQLSearch($folderpath,false,false,true);
$folderpath = trim($folderpath);
$folderpath = str_replace('\\','/',$folderpath);
$folderpath = str_replace('//','/',$folderpath);

$baseDir = realpath('.');
$path = realpath($baseDir . '/'.$folderpath);
if( ($folderpath=='/') || !is_readable($folderpath) ||
    (strpos($path, realpath(ROOT_PATH))!==0) )
{
  $folderpath = MEDIA_DEFAULT_DIR;
}
unset($baseDir,$path);

$folders  = GetImageFolders(MEDIA_DEFAULT_DIR, true, $folderpath);
$path = ROOT_PATH.'plugins/p17_image_gallery/';
$folders .= ' <option value="-">-</option>';
if(is_dir($path.'/images/'))
$folders .= ' <option value="'.$path.'images/">'.$plugin_names[17].' - images</option>';
if(is_dir($path.'/upload/'))
$folders .= ' <option value="'.$path.'upload/">'.$plugin_names[17].' - upload</option>';

// Fetch folders from Image Gallery, Media Gallery plugin and clones
if($get_plugins = $DB->query('SELECT pluginid, name, base_plugin, settingspath'.
                             ' FROM {plugins} WHERE pluginid > 17'.
                             " AND (name LIKE 'Media Gallery%' OR name LIKE 'Image Gallery%'".
                             " OR base_plugin = 'Media Gallery' OR base_plugin = 'Image Gallery')".
                             ' ORDER BY pluginid'))
{
  while($row = $DB->fetch_array($get_plugins,null,MYSQL_ASSOC))
  {
    if(!empty($row['settingspath']) && strlen($row['settingspath']))
    {
      $path = ROOT_PATH.'plugins/'.dirname($row['settingspath']);
      $pname = isset($plugin_names[$row['pluginid']])?$plugin_names[$row['pluginid']]:$row['name'];
      if(is_dir($path.'/images/'))
      $folders .= ' <option value="'.$path.'/images/">'.
                  strip_alltags(htmlspecialchars($pname)).' - images</option>';
      if(class_exists('SD_Image_Helper'))
      {
        SD_Image_Helper::$ImagePath = $path.'/images/';
        $folders .= SD_Image_Helper::GetSubFoldersForSelect($path.'/images/', '', '&nbsp;&nbsp;&gt;', true);
      }
      if(is_dir($path.'/upload/'))
      $folders .= ' <option value="'.$path.'/upload/">'.strip_alltags(htmlspecialchars($pname)).' - upload</option>';
    }
  }
}
$folders .= ' <option value="' . ROOT_PATH.'includes/images/smileys/">Smileys</option>';

$valid_audio_extensions = array('au','aac','aif','gsm','midi','mp3','m4a','oga','ogg','rm','wma');
$valid_image_extensions = SD_Media_Base::$valid_image_extensions;
$valid_media_extensions = SD_Media_Base::$valid_media_extensions;
$all_valid_extentions = array_unique(array_merge($valid_image_extensions,$valid_media_extensions,$valid_audio_extensions));
natsort($all_valid_extentions);

$sdm = false;
$sdm = new SD_Media();
$sdm->base_url = SITE_URL . 'images/';

// ****************************************************************************
// Check for Ajax actions, like display of images
// ****************************************************************************
if(Is_Ajax_Request() || $imageonly)
{
  // CHECK PAGE ACCESS
  CheckAdminAccess('media');

  if(($action=='getimages') && is_dir($folderpath) && CheckFormToken('', false))
  {
    DisplayImages();
  }
  else
  if(($action=='refresh') && is_dir($folderpath) && CheckFormToken('', false))
  {
    DoCreateThumbnail(false);
    DisplayMediaDetails();
  }
  if($action !== 'displaymediadetails')
  {
    exit();
  }
}


// ****************************************************************************
// BUILD PAGE HEADER
// ****************************************************************************
$media_osm_header = '';
$header_css = $header_js = array();

//SD362: moved "imgareaselect" JS/CSS to core admin loader!
//SD360: moved most non-media related JS files to "min/groupsConfig.php"!
$header_css[] = 'styles/' . ADMIN_STYLE_FOLDER_NAME . '/assets/css/media.css.php';
if($function_name == 'displaymediadetails')
{
  $header_js[]  = $sdurl.MINIFY_PREFIX_G.'admin_media'; //SD360
  if(!$imageonly)
  {
    $header_css[] = SD_CSS_PATH.'video-js.min.css';
    $header_css[] = SD_CSS_PATH.'tube.css';
    $header_css[] = SD_CSS_PATH.'jplayer.blue.monday.css';
	$header_css[] = SD_CSS_PATH.'jquery.Jcrop.min.css';
	$header_css[] = SD_CSS_PATH.'jPicker-1.1.6.min.css';
    //SD362: moved jPicker to core admin loader!

    if(file_exists(ROOT_PATH.'includes/javascript/osm/OSMPlayer.php'))
    {
      include_once(SD_INCLUDE_PATH.'javascript/osm/OSMPlayer.php');
      $osm_player = new OSMPlayer(array(
                        'disablePlaylist' => true,
                        'theme' => 'dark-hive',
                        'playerPath' => SD_JS_PATH.'osm',
                        'prefix' => '',
                        'template' => 'simpleblack'
      ));
      $media_osm_header = $osm_player->getHeader();
      unset($osm_player);
    }
  }
}
else
if($action != 'deleteimage')
{
  $header_css[] = SD_INCLUDE_PATH . 'plupload/css/plupload.queue.css';
  $header_js[]  = $sdurl.MINIFY_PREFIX_G . 'admin_media'; //SD362: extended!
  $header_js[]  = $sdurl.MINIFY_PREFIX_G . 'plupload'; //SD362
}
$header_js[] = 'javascript/page_media.js';

// *** COMMON JS HEADER - IMPORTANT! ***
if($action != 'deleteimage')
{
  $sd_js_header = '
<script type="text/javascript">
//<![CDATA[
var img_width = 0, img_height = 0, pre_width = 0, pre_height = 0;
var img_form = false, ias = false, scale_x = 1, scale_y = 1, previewbox;
var media_options = {
  action: "'.$action.'",
  admin_path: "'.ROOT_PATH.ADMIN_PATH.'",
  load_page: "'.ROOT_PATH.ADMIN_PATH.'/media.php",
  toolbar_cookie_name: "'.COOKIE_PREFIX.COOKIE_MEDIA_FILTER.'",
  style_folder_name: "'.ADMIN_STYLE_FOLDER_NAME.'",
  sd_include_path: "'.SD_INCLUDE_PATH.'",
  default_thumb_width: '.SD_DEFAULT_THUMB_WIDTH.',
  default_thumb_height: '.SD_DEFAULT_THUMB_HEIGHT.',
  selection_note: "'.addslashes($selection_note).'",
  media_max_colums: "'.MEDIA_MAX_COLUMNS.'",
  media_token: "'.SD_FORM_TOKEN.'",
  media_confirm_deletion: "'.addslashes(AdminPhrase('media_confirm_deletion')).'",
  media_image_err_sizes: "'.addslashes(AdminPhrase('media_image_err_sizes')).'",
  media_image_err_folder: "'.addslashes(AdminPhrase('media_image_err_folder')).'"
};
function ApplyCeebox(){
  if(typeof(jQuery.fn.ceebox) !== "undefined") {
    jQuery("a.cbox").attr("rel", "iframe modal:false");
    '.GetCeeboxDefaultJS(false).'
  }
}
//]]>
</script>
';
  // Add everything to header now:
  sd_header_add(array(
    'css'   => $header_css,
    'js'    => $header_js,
    'other' => array($sd_js_header . $media_osm_header)));
  unset($media_osm_header);
}


// DISPLAY ADMIN HEADER
$load_wysiwyg = 0;
if($MediaIncluded)
  sd_header_output();
else
{
  // CHECK PAGE ACCESS
  CheckAdminAccess('media');
  DisplayAdminHeader('Media', null, null, $imageonly);
}

// ############################# GET IMAGE FOLDERS #############################

function GetImageFolders($dirname, $showWritable=true, $selected)
{
  global $folderpath;

  if(!is_dir($dirname) ||
     (false === ($d = @dir($dirname))))
  {
    return '';
  }

  $result = '';
  while(false !== ($entry = $d->read()))
  {
    if( (substr($entry,0,1) != '.') &&
        ($entry!=='avatars') &&
        ($entry!=='profiles') )
    {
      if(is_dir($dirname . '/' . $entry))
      {
        $entry_display = $entry;
        if($showWritable && !is_writable($dirname . $entry ))
        {
          $entry_display .= ' *';
        }
        $result .= ' <option value="' . $dirname . $entry . '/"' .
                   (($selected == $dirname . $entry . '/') ? ' selected="selected"' : '') . '>' .
                   substr($dirname, 3) . $entry_display . '</option>';
        $result .= GetImageFolders($dirname . $entry . '/', $showWritable, $selected);
      }
    }
  }
  $d->close();
  return $result;

} //GetImageFolders


// ############################# GET IMAGE FOLDERS #############################

function GetWatermarkImages($dirname)
{
  global $folderpath;

  $result = '';
  if(empty($dirname) || !is_dir($dirname)) return '';
  if(false !== ($d = @dir($dirname)))
  {
    while(false !== ($entry = $d->read()))
    {
      if(substr($entry,0,1) == '.') continue;
      $tmp = strtolower($entry);
      if(is_file($dirname . $entry) &&
         ((substr($tmp,-4)=='.png') || (substr($tmp,-4)=='.jpg')  || (substr($tmp,-5)=='.jpeg')))
      {
        $result .= ' <option value="' . $dirname . $entry . '">' . $entry . '</option>';
      }
    }
    $d->close();
  }

  return $result;

} //GetWatermarkImages


// ############################################################################
// DO CREATE THUMBNAIL
// ############################################################################

function DoCreateThumbnail()
{
  global $action, $displaymode, $folderpath, $sdlanguage;

  if(!CheckFormToken() || empty($action))
  {
    RedirectPage(SELF_MEDIA_PHP,'<b>'.$sdlanguage['error_invalid_token'].'</b>',2,true);
    return;
  }
  $imagepath  = GetVar('imagepath', '', 'string', true, false);
  $imagename  = GetVar('imagename', '', 'string', true, false);
  $file_mode  = GetVar('file_mode', 0, 'natural_number', true, false);
  $thumb_mode = GetVar('thumb_mode', 0, 'natural_number', true, false);
  $thumbfile  = GetVar('thumbfilename', '', 'string', true, false);

  $maxwidth   = GetVar('maxwidth', SD_DEFAULT_THUMB_WIDTH, 'whole_number', true, false);
  $maxheight  = GetVar('maxheight', SD_DEFAULT_THUMB_HEIGHT, 'whole_number', true, false);

  $x1         = GetVar('x1', 0, 'whole_number', true, false);
  $x2         = GetVar('x2', 0, 'whole_number', true, false);
  $y1         = GetVar('y1', 0, 'whole_number', true, false);
  $y2         = GetVar('y2', 0, 'whole_number', true, false);

  $conf_arr   = array();
  $squaredoff = GetVar('squaredoff',0,'natural_number',true,false);

  //SD332: create new image OR replace existing image
  $targetfile = ($file_mode == 0) ? $thumbfile : $imagename;

  //SD332: Resize OR Crop to selection?
  if($thumb_mode == 1)
  {
    $conf_arr['x1'] = $x1;
    $conf_arr['x2'] = $x2;
    $conf_arr['y1'] = $y1;
    $conf_arr['y2'] = $y2;
    $conf_arr['crop'] = (($x2-$x1) > 0) && (($y2-$y1) > 0);
  }

  //SD332: Apply text watermark?
  $conf_arr['wtext']  = GetVar('watermark_text','','string',true,false);
  $conf_arr['wimage'] = GetVar('watermark_image','','string',true,false);
  $angle_custom       = GetVar('watermark_angle_custom',null,'int',true,false);
  if(!empty($angle_custom) && is_numeric($angle_custom))
  {
    $conf_arr['angle'] = $angle_custom;
  }
  else
  {
    $conf_arr['angle'] = GetVar('watermark_angle',0,'int',true,false);
  }
  $conf_arr['angle'] = Is_Valid_Number($conf_arr['angle'],0,-360,360);
  $conf_arr['alignment'] = GetVar('watermark_alignment','TL','string',true,false);
  $conf_arr['pos_x'] = GetVar('watermark_x',10,'natural_number',true,false);
  $conf_arr['pos_y'] = GetVar('watermark_y',10,'natural_number',true,false);
  $conf_arr['color'] = GetVar('watermark_color','#000000','string',true,false);
  $conf_arr['font']  = GetVar('watermark_font','1','string',true,false);
  $conf_arr['size']  = GetVar('watermark_size',20,'whole_number',true,false);
  $conf_arr['bgcolor']=GetVar('bgcolor','#FFFFFF','string',true,false);
  if($thumb_mode==2) //SD362
  {
    $conf_arr['fixedsize'] = true;
  }

  //SD370: options for image filters
  $options = array();
  if(GetVar('blur', false, 'bool')) $options['blur'] = true;
  if(GetVar('emboss', false, 'bool')) $options['emboss'] = true;
  if(GetVar('grayscale', false, 'bool')) $options['grayscale'] = true;
  if(GetVar('meanremoval', false, 'bool')) $options['meanremoval'] = true;
  if(GetVar('mirror', false, 'bool')) $options['mirror'] = true;
  if(GetVar('negate', false, 'bool')) $options['negate'] = true;
  if(GetVar('flip', false, 'bool')) $options['flip'] = true;
  if(GetVar('mirror', false, 'bool')) $options['mirror'] = true;

  if($tmp = GetVar('brightness', 0, 'int'))
    $options['brightness'] = Is_Valid_Number($tmp,0,-255,255);
  if($tmp = GetVar('contrast', 0, 'int'))
    $options['contrast'] = Is_Valid_Number($tmp,0,-100,100);
  if($tmp = GetVar('gamma', 0, 'float'))
  {
    if(($tmp < 2) && ($tmp >= 0))
    $options['gamma'] = (float)$tmp;
  }
  if($tmp = GetVar('pixelate', 0, 'whole_number'))
    $options['pixelate'] = Is_Valid_Number($tmp,0,1,100);
  if($tmp = GetVar('rotate', 0, 'whole_number'))
    $options['rotate'] = Is_Valid_Number($tmp,0,0,360);
  if(false !== ($tmp = GetVar('rotatebg', false, 'string')))
    $options['rotatebg'] = $tmp;

  $cols = array('r'=>0,'g'=>0,'b'=>0);
  $cols_count = 0;
  foreach($cols as $key => $value)
  {
    $cols[$key] = GetVar('colorize'.$key, '0', 'string');
    if(is_numeric($cols[$key]))
    {
      $cols[$key] = Is_Valid_Number($cols[$key],0,0,255);
    }
    else
    if(ctype_xdigit($cols[$key]))
    {
      $cols[$key] = hexdec($cols[$key]);
    }
    if($cols[$key] != 0) $cols_count++;
  }
  if($cols_count) $options['colorize'] = $cols;

  if($tmp = GetVar('smooth', 0, 'float'))
  {
    if(($tmp > -10) && ($tmp <= 999))
    $options['smooth'] = (int)$tmp;
  }
  if(false !== ($tmp = GetVar('watermarktext', false, 'string')))
    $options['watermarktext'] = $tmp;
  if(false !== ($tmp = GetVar('wtextcolor', false, 'string')))
    $options['wtextcolor'] = $tmp;
  if(false !== ($tmp = GetVar('wbackcolor', false, 'string')))
    $options['wbackcolor'] = $tmp;

  if($tmp = GetVar('quality', 90, 'whole_number')) #JPG only
    $options['quality'] = Is_Valid_Number($tmp,90,0,100);
  if($tmp = GetVar('compression', 1, 'whole_number')) #PNG only
    $options['compression'] = Is_Valid_Number($tmp,1,0,9);


  require_once(SD_INCLUDE_PATH.'class_sd_media.php');
  $sdi = new SD_Image($imagepath);
  if(!$sdi->getImageValid())
  {
    $_POST['error'] = 'Invalid image';
    unset($sdi);
    return false;
  }
  $errormsg = '';

  //SD370: apply image filters first:
  if(!$sdi->FilterImage($options,true,$folderpath.$targetfile))
  {
    $errormsg = $sdi->getErrorMessage();
  }
  else
  if(!$sdi->CreateThumbnail($folderpath.$targetfile, $maxwidth, $maxheight,
                            $squaredoff, ($thumb_mode!==2), $conf_arr))
  {
    $errormsg = $sdi->getErrorMessage();
  }
  unset($sdi);

  if(Is_Ajax_Request())
  {
    $_POST['folderpath'] = $folderpath;
    $_POST['imagename']  = $targetfile;
    $_POST['imagepath']  = $folderpath . $targetfile;
    $_POST['error']      = $errormsg;
    return empty($errormsg);
  }

  if(!empty($errormsg))
  {
    DisplayMessage($errormsg, true);
    DisplayThumbnailForm();
  }
  else
  {
    RedirectPage(SELF_MEDIA_PHP.'?action=display_media_details&amp;folderpath=' .
      $targetfolder . '&amp;displaymode='.$displaymode.'&amp;imagename='.$targetfile,
      AdminPhrase('media_thumbnail_created'));
  }

} //DoCreateThumbnail


// ############################################################################
// DISPLAY IMAGE DETAILS
// ############################################################################

function DisplayMediaDetails()
{
  global $sdlanguage,
         $action, $displaymode, $filter, $folderpath, $folders, $page, $pagesize,
         $sortcolumn, $sortorder,
         $valid_audio_extensions,
         $selection_note, $sdm, $imageonly;

  $isajax    = Is_Ajax_Request() && ($action == 'refresh');
  // DO NOT RE-/MOVE THE FOLLOWING LINES WHICH MAY BE FILLED BY DoCreateThumbnail!!!
  $imagename = GetVar('imagename', '', 'string');
  $imagepath = GetVar('imagepath', $folderpath . $imagename, 'string');
  $error     = GetVar('error', '', 'string');
  if(strstr($imagepath, 'http://') || strstr($imagepath, 'ftp://'))
  {
    return false;
  }

  $is_image = false;
  $is_media = false;
  if(false !== ($imageextension = SD_Media_Base::getExtention($imagename, SD_Media_Base::$valid_image_extensions)))
  {
    $is_image    = true;
    $ext_length  = -1 * (strlen($imageextension)+1);
    $thumbname   = substr($imagename, 0, $ext_length) . '_thumbnail.' . $imageextension;
    $thumbname   = str_replace(' ', '_', $thumbname);
    $thumbwidth  = SD_DEFAULT_THUMB_WIDTH;
    $thumbheight = SD_DEFAULT_THUMB_HEIGHT;
  }
  else
  if(false !== SD_Media_Base::getExtention($imagename, SD_Media_Base::$valid_media_extensions))
  {
    $is_media = true;
  }
  if( ($is_image && !file_exists($folderpath.$imagename)) ||
      (!$is_image && !$is_media) )
  {
    RedirectPage(SELF_MEDIA_PHP,'<b>'.$sdlanguage['err_sd_media-3'].'</b>',2,true);
    return;
  }

  if($isajax)
  {
    if(!$is_image) return; // Not supported!
    echo '<div id="image_content">';
  }
  else
  {
    StartSection(AdminPhrase('media_image_details'));
    echo '
    <div id="image_details">';
  }

  //'.MEDIA_PREVIEW_WIDTH.'
  echo '
    <table class="table table-bordered">
    <tr>
      <td class="align-top" width="35%">
        <b>' . AdminPhrase('media_path') . ': ' . substr(dirname($imagepath), 3) . '</b><br />
        <table class="table">
        ';

  $ThisFileInfo = $width = $height = false;
  if($ThisFileInfo = $sdm->GetID3Info($imagepath, false, true))
  {
    $width = $ThisFileInfo['width'];
    $height = $ThisFileInfo['height'];
  }
  if($is_image && (!$ThisFileInfo || !$width || !$height) && file_exists($imagepath))
  {
    if(!list($width, $height) = @getimagesize($imagepath))
    {
      $error .= '<br /><span class="red">'.AdminPhrase('media_image_err_file').'</span>';
      $width = $height = 80;
    }
  }
  unset($ThisFileInfo);

  echo '
        </table>
        <div class="well center align-top">
		
          <div id="thumb_dummy" style="display:none;margin:0px;overflow:hidden;width:100%;height:100%">
            <h3 class="blue lighter">'.AdminPhrase('media_thumb_preview').'</h3>
            <div id="thumb_container" style="border:1px solid #ff0000;position:relative;margin:2px;overflow:hidden;width:'.
            MEDIA_PREVIEW_WIDTH.'px;height:100%"><img src="'.$imagepath.
            '" style="position:relative" id="thumbnail_preview" alt="'.AdminPhrase('media_thumb_preview').'" /></div>
          </div>
        </div>
      </td>
      <td id="previewcell" class="'.($is_media?'left" valign="top':'center').'">
        <input type="hidden" id="img_width" value="'.$width.'" />
        <input type="hidden" id="img_height" value="'.$height.'" />
        <input type="hidden" id="imagepath" value="'.$imagepath.'" />
        <h3 class="header blue lighter">'.$imagename.''.$error.'</h3>
        <div id="previewloader"'.($is_media?'" style="margin-left: 9999px"':'').'><img src="'.SD_INCLUDE_PATH.'css/images/indicator.gif" width="16" height="16" alt="" /></div>
        <div id="preview_container">
        <div class="previewbox">
        ';

  if($is_media)
  {
    $jscode = "jQuery('#previewloader').remove();";
    $sdm->base_url = SITE_URL;
    $tmp = preg_replace('#'.preg_quote(ROOT_PATH,'#').'#', '', $folderpath);
    $sdm->embed_width = 650;
    $sdm->embed_height = 366;
    $sdm->embed_autoplay = true;
    $sdm->extralink = '';
    $embed = $sdm->GetEmbedCode($tmp, $imagename, $jscode);
    echo $embed.'<br />';
  }
  else
  {
    echo '
          <div class="previewlink">
          <img id="previewimg" alt="" src="'.BLANK_IMG.'" /><br /><br />
          <a id="previewlink" class="btn btn-info cbox" title="'.$imagename.'" href="'.
            $imagepath.'" target="_blank"><i class="ace-icon fa fa-search"></i> '.AdminPhrase('media_preview_lbl').'</a>
          <div>
          ';
  }
  echo '
        </div> <!-- .previewbox !-->
        </div> <!-- preview_container !-->
      </td>
    </tr>';

  $delete_btn = '
        <div class="center">
          <form id="form_delete" method="post" action="'.SELF_MEDIA_PHP.
          '?action=deleteimage&amp;folderpath='.$folderpath.'&amp;imagename='.urlencode($imagename).'">
          <a class="btn btn-danger" id="del_image" href="#" onclick="jQuery(\'#form_delete\').submit(); return false;">'.
          '<i class="ace-icon fa fa-trash-o"></i> '.AdminPhrase('media_delete').'</a>
          </form>
        </div>';

  if($is_image)
  {
    echo '
    <tr>
      <td class="center no-border-right"><b>&nbsp;</b></td>
      <td class="center no-border-left"><div id="preview_details">'.$selection_note.'</div></td>
    </tr>
    <tr>
      <td class="td3">
        <b>' . AdminPhrase('media_delete_image') . '</b>
      </td>
      <td class="td3">'.$delete_btn .'</td>
    </tr>';
  }

  echo '
    </table>
    </div> <!-- #image_details -->
    '; // end of top table container!

  if($isajax)
  {
    return;
  }


  if($is_image)
  {
    echo '<h3 class="header blue lighter">' . AdminPhrase('media_create_thumbnail') . '</h3>
    <form id="image_form" class="form-horizontal" method="post" action="'.SELF_MEDIA_PHP.'?action=DoCreateThumbnail">
    '.PrintSecureToken().'
    <input type="hidden" name="imagepath" value="' . $imagepath . '" />
    <input type="hidden" name="imagename" value="' . $imagename . '" />
    <input type="hidden" name="displaymode" value="' . $displaymode . '" />
    <input type="hidden" name="page" value="'.$page.'" />
    <input type="hidden" name="pagesize" value="'.$pagesize.'" />
    <input type="hidden" name="sortcolumn" value="'.$sortcolumn.'" />
    <input type="hidden" name="sortorder" value="'.$sortorder.'" />
    <input type="hidden" name="filter" value="' . $filter . '" />
    <input type="hidden" name="x1" value="0" />
    <input type="hidden" name="x2" value="0" />
    <input type="hidden" name="y1" value="0" />
    <input type="hidden" name="y2" value="0" />
    <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('media_select_destination') . '</label>
		<div class="col-sm-6">
        <select name="folderpath" class="form-control">
        <option value="../images/">images</option>';

    echo $folders;

    echo '
        </select>
        <span class="helper-text">' . AdminPhrase('media_folder_readonly_note') . '</span>
      </div>
    </div>
	
    <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('media_processing_mode') . '</label>
		<div class="col-sm-6">
				<select id="file_mode" name="file_mode" class="form-control">
				<option value="0" selected="selected">' . AdminPhrase('media_processing_mode1') . '</option>
				<option value="1">' . AdminPhrase('media_processing_mode2') . '</option>
				</select>
				<span class="helper-text">' . AdminPhrase('media_processing_mode_descr') . '</span>
      	</div>
    </div>
	
    <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('media_thumb_filename') . '</label>
		<div class="col-sm-6">
        	<input type="text" id="thumbfilename" name="thumbfilename" value="' . $thumbname . '" class="form-control" />
			<span class="helper-text"> ' . AdminPhrase('media_enter_filename_for_thumb') . '</span>
      	</div>
	</div>
	
    <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('media_thumb_mode') . '</label>
		<div class="col-sm-6">
				<select id="thumb_mode" name="thumb_mode" class="form-control">
				<option value="0" selected="selected">' . AdminPhrase('media_thumb_mode_resize') . '</option>
				<option value="1">' . AdminPhrase('media_thumb_mode_crop') . '</option>
				<option value="2">' . AdminPhrase('media_thumb_mode_scaling') . '</option>
				</select>
				<span class="helper-text">' . AdminPhrase('media_thumb_mode_descr') . '</span>
      </div>
</div>
   <div class="form-group">
   <label class="control-label col-sm-2">'.AdminPhrase('media_image_sizes').'</label>
       
      <div class="col-sm-6">
        <input type="button" class="btn btn-sm btn-info media_size_btn" name="size_original" value="Original" />
        <input type="button" class="btn btn-sm btn-info media_size_btn" name="size_100x100" value="100x100" />
        <input type="button" class="btn btn-sm btn-info media_size_btn" name="size_200x200" value="200x200" />
        <input type="button" class="btn btn-sm btn-info media_size_btn" name="size_320x180" value="320x180" />
        <input type="button" class="btn btn-sm btn-info media_size_btn" name="size_480x320" value="480x320" />
        <br />
        <input type="button" class="btn btn-sm btn-info media_size_btn" name="size_640x480" value="640x480" />
        <input type="button" class="btn btn-sm btn-info media_size_btn" name="size_800x600" value="800x600" />
        <input type="button" class="btn btn-sm btn-info media_size_btn" name="size_1024x768" value="1024x768" />
        <input type="button" class="btn btn-sm btn-info media_size_btn" name="size_1280x1024" value="1280x1024" />
        <input type="button" class="btn btn-sm btn-info media_size_btn" name="size_1680x1050" value="1680x1050" />
        <br />
        <input type="button" class="btn btn-sm btn-info media_size_btn" name="size_1920x720" value="1920x720" />
        <input type="button" class="btn btn-sm btn-info media_size_btn" name="size_1920x1080" value="1920x1080" />
        <input type="button" class="btn btn-sm btn-info media_size_btn" name="size_2048x1040" value="2048x1040" />
        <input type="button" class="btn btn-sm btn-info media_size_btn" name="size_3840x2160" value="3840x2160" />
        <input type="button" class="btn btn-sm btn-info media_size_btn" name="size_4096x4096" value="4096x4096" />
		<br />
	  <span class="helper-text">'.AdminPhrase('media_image_sizes_descr').'</span>
	</div>
</div>

    <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('media_max_thumb_width') . '</label>
		<div class="col-sm-6">
				<input type="text" id="maxwidth" name="maxwidth" value="' . $thumbwidth . '" class="form-control" />
				<span class="helper-text">	' . AdminPhrase('media_enter_max_thumb_width') . '</span>
      	</div>
	</div>
	
    <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('media_max_thumb_height') . '</label>
		<div class="col-sm-6">
				<input type="text" id="maxheight" name="maxheight" value="' . $thumbheight . '" class="form-control" />
				<span class="helper-text"> ' . AdminPhrase('media_enter_max_thumb_height') . '</span>
      	</div>
	</div>
    <div class="form-group">
		<label class="control-label col-sm-2">'.AdminPhrase('media_square_off_image').'</label>
		<div class="col-sm-6">
      	<input type="checkbox" class="ace" name="squaredoff" value="1" />
		<span class="lbl"> 
        '.AdminPhrase('media_square_off_image_hint').'
		</span>
      </div>
</div>
    <div class="form-group">
		<label class="control-label col-sm-2">'.AdminPhrase('media_image_background_color').'</label>
		<div class="col-sm-6">
		<input type="text" class="colorpicker" name="bgcolor" value="#FFFFFF"/><br />
		<span class="helper-text">'.AdminPhrase('media_image_background_color_descr').'</span>
		</div>
</div>';

    if(function_exists('imagefilter'))
    {
      echo '<h3 class="header blue lighter">' . $sdlanguage['common_media_image_filters'].'</h3>
    <div class="form-group">
		<label class="control-label col-sm-2">'.$sdlanguage['common_media_image_filters'].'</label>
		<div class="col-sm-6">
        <label><input class="checkbox ace" type="checkbox" value="1" id="mg_blur" name="blur" /><span class="lbl"> '.$sdlanguage['common_media_blur'].'</span></label><br />
        <label><input class="checkbox ace" type="checkbox" value="1" id="mg_emboss" name="emboss" /><span class="lbl">  '.$sdlanguage['common_media_emboss'].'</span></label><br />
        <label><input class="checkbox ace" type="checkbox" value="1" id="mg_grayscale" name="grayscale" /><span class="lbl">  '.$sdlanguage['common_media_grayscale'].'</span></label><br />
        <label><input class="checkbox ace" type="checkbox" value="1" id="mg_mean_removal" name="meanremoval" /><span class="lbl">  '.$sdlanguage['common_media_mean_removal'].'</span></label><br />
        <label><input class="checkbox ace" type="checkbox" value="1" id="mg_flip" name="flip" /><span class="lbl">  '.$sdlanguage['common_media_flip'].'</span></label><br />
        <label><input class="checkbox ace" type="checkbox" value="1" id="mg_mirror" name="mirror" /><span class="lbl">  '.$sdlanguage['common_media_mirror'].'</span></label><br />
        <label><input class="checkbox ace" type="checkbox" value="1" id="mg_negate" name="negate" /><span class="lbl">  '.$sdlanguage['common_media_negate'].'</span></label>
        <table class="table">';

      if(defined('IMG_FILTER_COLORIZE')) //PHP 5.2.5+
      {
        echo '
        <tr><td class="td3" width="50%">'.$sdlanguage['common_media_colorize'].'</td>
          <td class="align-middle">
            R: #<input class="mg_colorize" type="text" value="" name="colorizer" size="2" maxlength="2" class="form-control" />
            G: #<input class="mg_colorize" type="text" value="" name="colorizeg" size="2" maxlength="2" />
            B: #<input class="mg_colorize" type="text" value="" name="colorizeb" size="2" maxlength="2" />
          </td>
        </tr>';
      }
      echo '
        <tr><td class="td3" width="50%">'.$sdlanguage['common_media_brightness'].'</td>
          <td class="td3"><input class="mg_brightness" type="text" value="0" id="mg_brightness" name="brightness" size="5" maxlength="4" /></td>
        </tr>
        <tr><td class="td3" width="50%">'.$sdlanguage['common_media_contrast'].'</td>
          <td class="td3"><input class="mg_contrast" type="text" value="0" id="mg_contrast" name="contrast" size="5" maxlength="4" /></td>
        </tr>
        <tr><td class="td3" width="50%">'.$sdlanguage['common_media_gamma'].'</td>
          <td class="td3"><input class="mg_gamma" type="text" value="1.0" id="mg_gamma" name="gamma" size="5" maxlength="5" /></td>
        </tr>
        <tr><td class="td3" width="50%">'.$sdlanguage['common_media_rotate'].'</td>
          <td class="td3"><input class="mg_rotate" type="text" value="0" id="mg_rotate" name="rotate" size="4" maxlength="3" /></td>
        </tr>
        <tr><td class="td3" width="50%">'.$sdlanguage['common_media_smoothness'].'</td>
          <td class="td3"><input class="mg_smooth" type="text" value="0" id="mg_smooth" name="smooth" size="4" maxlength="3" /></td>
        </tr>';
        if(defined('IMG_FILTER_PIXELATE')) //PHP 5.3+
        {
          echo '
        <tr><td class="td3" width="50%">'.$sdlanguage['common_media_pixelate'].'</td>
          <td class="td3"><input class="mg_pixelate" type="text" value="0" id="mg_pixelate" name="pixelate" size="3" maxlength="2" /></td>
        </tr>';
        }
      echo '
        </table>
      </div></div>
      ';
    }

    echo '
	<h3 class="header blue lighter">'.AdminPhrase('media_watermark').'</h3>
    <div class="form-group">
		<label class="control-label col-sm-2">'.AdminPhrase('media_watermark_image').'</label>
		<div class="col-sm-6">
        <select name="watermark_image" class="form-control">
          <option value="-" selected="selected">---</option>
          '.GetWatermarkImages(SD_INCLUDE_PATH.'images/watermarks/').'
        </select>
		<span class="helper-text"> '.AdminPhrase('media_watermark_image_hint').'</span>
      </div>
</div>
    <div class="form-group">
		<label class="control-label col-sm-2">'.AdminPhrase('media_watermark_text').'</label>
		<div class="col-sm-6">
        <input type="text" name="watermark_text" value="" class="form-control" />
		<span class="helper-text"> '.AdminPhrase('media_watermark_text_hint').'</span>
      </div>
</div>
    './*
    <tr>
      <td class="td2">Watermark Alignment:</td>
      <td class="td3">
        <select name="watermark_alignment" style="width: 200px">
        <option value="TL" selected="selected">Top Left</option>
        <option value="T">Top Center</option>
        <option value="TR">Top Right</option>
        <option value="C">Center Center</option>
        <option value="L">Center Left</option>
        <option value="R">Center Right</option>
        <option value="BL">Bottom Left</option>
        <option value="B">Bottom Center</option>
        <option value="BR">Bottom Right</option>
        </select>
      </td>
    </tr>*/'
    <div class="form-group">
		<label class="control-label col-sm-2">'.AdminPhrase('media_watermark_font').':</label>
		<div class="col-sm-6">
				<select name="watermark_font" class="form-control">
				<option value="0" selected="selected">'.AdminPhrase('media_watermark_internal_font').'</option>
				<option value="1">1 Halter</option>
				<option value="2">2 Abscissa Bold</option>
				<option value="3">3 Acidic</option>
				<option value="4">4 Helvetica-Black Semi-Bold</option>
				<option value="5">5 Activa</option>
				<option value="6">6 Alberta Regular</option>
				<option value="7">7 Alien League</option>
				<option value="8">8 AllHookedUp</option>
				<option value="9">9 Alpha Romanie G98</option>
				<option value="10">10 Opossum</option>
				</select>
				<span class="helper-text">'.AdminPhrase('media_watermark_font_hint').' <a class="cbox" rel="width:500 height:400 html:true" href="'.
				SD_INCLUDE_PATH.'fonts/font_preview.html#previews">'.
				AdminPhrase('media_fonts_preview').'</a></span>
      </div>
</div>
    <div class="form-group">
<label class="control-label col-sm-2">'.AdminPhrase('media_watermark_angle').'</label>
      <div class="col-sm-6">
        <input type="radio" class="ace" name="watermark_angle" value="360" /><span class="lbl"> 360</span>
        <input type="radio" class="ace" name="watermark_angle" value="270" /><span class="lbl"> 270</span>
        <input type="radio" class="ace" name="watermark_angle" value="180" /><span class="lbl"> 180</span>
        <input type="radio" class="ace" name="watermark_angle" value="90" /><span class="lbl"> 90</span>
        <input type="radio" class="ace" name="watermark_angle" value="0" checked="checked" /><span class="lbl"> 0</span><br />
        <input type="text" class="form-control" name="watermark_angle_custom" placeholder="'.AdminPhrase('media_watermark_custom_lbl').'" value="" />
		<span class="helper-text">'.AdminPhrase('media_watermark_angle_hint').'<br /></span>
      </div>
</div>
    <div class="form-group">
		<label class="control-label col-sm-2">'.AdminPhrase('media_watermark_text_size').'</label>
		<div class="col-sm-6">
			<input type="text" name="watermark_size" value="6" class="form-control"/>
			<span class="helper-text">'.AdminPhrase('media_watermark_text_size_hint').'</span>
      </div>
</div>
    <div class="form-group">
		<label class="control-label col-sm-2">'.AdminPhrase('media_watermark_margin_left').'</label>
		<div class="col-sm-6">
			<input type="text" name="watermark_x" value="10" class="form-control"/>
		</div>
	</div>
	
    <div class="form-group">
		<label class="control-label col-sm-2">'.AdminPhrase('media_watermark_margin_top').'</label>
		<div class="col-sm-6">
			<input type="text" name="watermark_y" value="10" class="form-control" />
		</div>
	</div>
    <div class="form-group">
		<label class="control-label col-sm-2">'.AdminPhrase('media_watermark_text_color').'</label>
		<div class="col-sm-6">
			<input type="text" class="colorpicker" name="watermark_color" value="#000000" class="form-control" />
		</div>
	</div>
	<div class="center">
          <a class="btn btn-info" href="#" onclick="jQuery(\'#image_form\').submit(); return false;">
         <i class="ace-icon fa fa-crop"></i> '.
          AdminPhrase('media_create_thumb').'</a>
          <input type="submit" value="Submit" style="display:none" />
    ';
  }
  else
  {
    echo '
    <table class="table">
    <tr>
      <td width="50%">'.$delete_btn;
  }
  echo '
          &nbsp;<a href="#" onclick="window.location=\''.SELF_MEDIA_PHP.'?action=displayimages&amp;displaymode='.$displaymode.
          '&amp;filter='.urlencode($filter).'&amp;page='.urlencode($page).'&amp;pagesize='.$pagesize.
          '&amp;sortcolumn='.urlencode($sortcolumn).'&amp;sortorder='.urlencode($sortorder).
          '&amp;folderpath='.urlencode($folderpath).
          '\'; return false;" class="btn btn-danger">
          <i class="ace-icon fa fa-times"></i> '.
          AdminPhrase('media_cancel').'</a>
        </div>
    </form>
	</div>';

  if($is_image)
  {
    echo '
<script type="text/javascript">
// <![CDATA[
if (typeof(jQuery) !== "undefined") {
  jQuery(document).ready(function() {
  (function($){
    $("img#previewimg").unbind().bind("onreadystatechange load", function(){
    if(this.complete) {
      if('.$height.' < 300){
        var imgheight = $("#previewimg").height();
        var height = $(".previewbox:first").height();
        $("#previewimg").css({ marginTop: Math.round((height-imgheight)/2) });
      }
      sdmedia.PositionLoader();
      window.setTimeout(function() {
        sdmedia.PositionLoader();
        sdmedia.ApplyImageSelector();
        $("#previewloader").hide();
        $("#previewimg").css({ border: "1px solid #d0d0d0" });
      }, 500);
    } else {
      $("#previewloader").show();
    };
    });
    $("#previewimg").attr("src", $("#imagepath").attr("value")+"?"+(new Date()).getTime());
  }(jQuery));
  }); // DOCUMENT READY
}
// ]]>
</script>
';
  }
  else
  {
	  echo '</td></tr></table></div>';
  }

} //DisplayMediaDetails

// ############################### UPLOAD IMAGE ################################

function UploadImage()
{
  global $folderpath;

  $image     = isset($_FILES['image']) ? $_FILES['image'] : null;
  $imagesdir = dirname(__FILE__) . '/' .  $folderpath;
  $is_image  = false;
  $imagename = '';

  clearstatcache();
  if(!is_dir($imagesdir))
  {
    $errors[] = 'Images directory "'.$imagesdir.'" not found!';
  }
  else if(!is_writable($imagesdir))
  {
    $errors[] = 'Images directory "'.$imagesdir.'" is not writable!';
  }

  // check if file was uploaded
  if(!empty($image['error']) && ($image['error'] != 4))
  {
    switch ($image['error'])
    {
      case 1: //UPLOAD_ERR_INI_SIZE:
        $error = AdminPhrase('media_upload_error_1');
      break;

      case 2: //UPLOAD_ERR_FORM_SIZE:
        $error = AdminPhrase('media_upload_error_2');
      break;

      case 3: //UPLOAD_ERR_PARTIAL:
        $error = AdminPhrase('media_upload_error_3');
      break;

      case 6: //UPLOAD_ERR_NO_TMP_DIR:
        $error = AdminPhrase('media_upload_error_4');
      break;

      case 7: //UPLOAD_ERR_CANT_WRITE:
        $error = AdminPhrase('media_upload_error_5');
      break;

      case 8: //UPLOAD_ERR_EXTENSION:
        $error = AdminPhrase('media_upload_error_6');
      break;

      default:
        $error = AdminPhrase('media_upload_error_0');
    }
    $errors[] = $error;
  }
  else if(empty($image['size']) || ($image['error'] == 4))
  {
    $errors[] = AdminPhrase('media_upload_error_no_image');
  }
  else
  {
    // lets make sure the file type is correct
    if(false !== SD_Media_Base::getExtention($image['name'], SD_Media_Base::$valid_image_extensions))
    {
      $is_image = true;
      // lets make sure the extension is correct
      if(!in_array($image['type'], SD_Media_Base::$known_image_types))
      {
        $errors[] = AdminPhrase('media_upload_error_invalid_image');
      }
    }
    else
    if(false !== SD_Media_Base::getExtention($image['name'], SD_Media_Base::$valid_media_extensions))
    {
      $errors[] = AdminPhrase('media_upload_error_invalid_image');
    }
  }

  if(empty($errors))
  {
    // Before accessing it, we need to ...
    // a) secure the filename to avoid apache bug in executing the image!
    // b) move the uploaded temp file to our own directory to allow it
    //    to run on secured servers (safe mode, open_dir...)
    $imagename = $image['name'];
    if( SD_Image_Helper::FilterImagename($imagename) &&
        SD_Image_Helper::FilterImagename($image['tmp_name']) &&
        !is_executable($image['tmp_name']) &&
        is_uploaded_file($image['tmp_name']))
    {
      $imagename = basename($image['name']);
      if(@move_uploaded_file($image['tmp_name'], $imagesdir . $imagename))
      {
        @chmod($imagesdir . $imagename, 0644);
        // final attempt to make sure it's a real image:
        if($is_image && !@getimagesize($imagesdir . $imagename))
        {
          $errors[] = AdminPhrase('media_upload_error_invalid_image');
          @unlink($imagesdir . $imagename);
        }
      }
      else
      {
        $errors[] = AdminPhrase('media_upload_error_not_writable') . ' ' . substr($folderpath, 1);
      }
    }
    else
    {
      $errors[] = AdminPhrase('media_upload_error_invalid_image');
    }
  }

  if(!empty($errors))
  {
    DisplayMessage($errors, true);
    DisplayImages();
  }
  else
  {
    RedirectPage(SELF_MEDIA_PHP.'?action=displayimages&amp;folderpath=' . $folderpath . '', AdminPhrase('media_image_uploaded'));
  }

} //UploadImage


// ############################################################################
// DELETE IMAGE
// ############################################################################

function DeleteImage()
{
  global $folderpath, $all_valid_extentions;

  $imagepath = strip_tags(urldecode(GetVar('folderpath', '', 'string')));
  $imagename = strip_tags(urldecode(GetVar('imagename', '', 'string')));
  $imagepath = $imagepath.$imagename;

  $errors_arr = array();

  // Check extension for allowed image types and path/file not below web root:
  $allow = (false !== SD_Media_Base::getExtention(basename($imagepath), $all_valid_extentions));

  if(!$allow)
  {
    $errors_arr[] = AdminPhrase('media_invalid_image_extension');
  }

  $allow = $allow && (strpos($imagepath, '*') === false) &&
    (substr_count($imagepath, '../') <= substr_count(ROOT_PATH, '../')) &&
    (substr($imagepath, 0, 1) != '/') && (strpos($imagepath, '\\\\') === false);

  $exists = file_exists($imagepath);
  if(!$allow && $exists)
  {
    $errors_arr[] = AdminPhrase('media_invalid_image_name');
  }

  if(!is_writable(dirname($imagepath)))
  {
    $errors_arr[] = AdminPhrase('folder_not_writable').': "'.dirname($imagepath).'"';
  }

  // delete image
  if(!count($errors_arr) && (!$exists || @unlink($imagepath)))
  {
    clearstatcache();
    RedirectPage(SELF_MEDIA_PHP.'?action=displayimages&amp;folderpath=' . $folderpath, AdminPhrase('media_image_deleted'));
    return true;
  }
  else
  {
    $errors_arr[] = AdminPhrase('media_image_not_deleted');
    DisplayMessage($errors_arr, true);
    DisplayImages();
    return false;
  }

} //DeleteImage


// ############################################################################
// DISPLAY IMAGES
// ############################################################################

function DisplayImages()
{
  global $displaymode, $folderpath, $folders, $filter, $page, $pagesize,
         $readonly_note, $sortcolumn, $sortorder, $all_pagesizes,
         $all_valid_extentions, $MediaIncluded;

  $bottom_js = '';
  $ajax = Is_Ajax_Request();

  if(!$ajax)
  {
    if(!$MediaIncluded)
    {
      // Create an uploaded object to integrate "plupload"
      // licensed for SD from Moxiecode:
      @include_once(ROOT_PATH.'includes/class_sd_uploader.php');

      if(class_exists('SD_Uploader'))
      {
        $uploader_config = array(
          'html_id'       => 'file_media',
          'singleUpload'  => 'true',
          'maxQueueCount' => 0,
          'afterUpload'   => 'processInstantFile',
          'title'         => '<b>'.AdminPhrase('media_uploder_select_hint').'</b>',
        );
        $uploader = new SD_Uploader(1, 0, $uploader_config);
        $uploader->setValue('filters',
          '{title : "Images", extensions : "'.implode(',',SD_Media_Base::$valid_image_extensions).'"},
           {title : "Media", extensions : "'.implode(',',$all_valid_extentions).'"}');
        $uploader->message_success = 'Upload done.';
        $uploader->error_failed = AdminPhrase('media_upload_error_invalid_image');
        $uploader->button_select_text = AdminPhrase('media_browse_folder');
        $uploader->button_upload_text = AdminPhrase('media_upload_image');

        echo '<h3 class="header blue lighter">' . AdminPhrase('instant_media_upload') . '</h3>
        <form action="" method="post" class="form-horizontal">'.PrintSecureToken().'
            <div class="form-group">
			<label class="control-label col-sm-6">'.AdminPhrase('media_instant_upload_hint').'</label>
			<div class="col-sm-6">
              <select id="instant_folder" class="form-control" name="instant_folder">
                <option value="../images/">images</option>
                ' . $folders . '
              </select>
              <span class="helper-text">'.$readonly_note.'</span>
            </div>
		</div>
		<div class="center">
          
            '. $uploader->getHTML().'
        </form>';
      
        $bottom_js = $uploader->getJS();
      }

      // ********** Files "Toolbar" **********
      echo '<br />';
      StartSection(AdminPhrase('media_images'));
    }

    echo '
    <form id="browse_files" method="post" action="'.SELF_MEDIA_PHP.'" class="form-inline">
      <div id="toolbar" class="alert alert-info">
        <input type="hidden" name="action" value="displayimages" />
        <input type="hidden" id="sortorder" name="sortorder" value="' . $sortorder. '" />
       <label class="inline"> ' . AdminPhrase('media_folder') . '</label>
        <select id="sel_folderpath" name="folderpath" class="input-medium">
          <option value="../images/">images</option>
          '.$folders.'
        </select>
        <a id="btn_reload" class="btn btn-sm btn-white" href="#" onclick="sdmedia.ReloadImages(); return false;" title="'.AdminPhrase('media_refresh').'"><i class="ace-icon fa fa-refresh green bigger-110"></i></a>
		
       &nbsp;&nbsp;<label class="inline"> '.AdminPhrase('media_filter').'</label> <input id="filter" name="filter" type="text" value="'.$filter.'" class="input-medium" />
        <a id="del_filter" class="btn btn-sm- btn-white" href="#" title="'.AdminPhrase('media_clear_filter').'"><i class="ace-icon fa fa-times red bigger-110"></i></a>
        <select id="sortcolumn" name="sortcolumn" class="input-medium">
          <option value="0" '.( empty($sortcolumn)?'selected="selected"':'').'>'.AdminPhrase('media_sort_date').'</option>
          <option value="1" '.(!empty($sortcolumn)?'selected="selected"':'').'>'.AdminPhrase('media_sort_name').'</option>
        </select>
		
        <a id="sort_asc" class="btn btn-sm btn-white" href="#" title="'.AdminPhrase('media_sort_asc').'"><i class="ace-icon fa fa-sort-alpha-asc blue bigger-110"></i></a>
        <a id="sort_desc" class="btn btn-sm btn-white" href="#" title="'.AdminPhrase('media_sort_descending').'"><i class="ace-icon fa fa-sort-alpha-desc blue bigger-110"></i></a>
		
        &nbsp;<label class="inline">'.AdminPhrase('media_displaymode').
        '</label><select id="displaymode" name="displaymode" class="input-medium">
          <option value="0" '.( empty($displaymode)?'selected="selected"':'').'>'.AdminPhrase('media_thumbnails').'</option>
          <option value="1" '.(!empty($displaymode)?'selected="selected"':'').'>'.AdminPhrase('media_details').'</option>
        </select>&nbsp;'.
        '<select id="pagesize" name="pagesize" class="input-medium">
          <option value="all" '.( empty($pagesize)?'selected="selected"':'').'>'.AdminPhrase('media_pagesize_all').'</option>';
        foreach($all_pagesizes as $ps)
        {
          if($ps)
          echo '
          <option value="'.$ps.'" '.($pagesize==$ps?'selected="selected"':'').'>'.$ps.'</option>';
        }
        echo '
        </select>
		<i class="ace-icon fa fa-spin fa-spinner bigger-150 orange" style="display: none;" id="files_loader"></i>
        
      </div>
    </form>
    ';


    echo '
    <div id="files_list">
    ';
  } // !Ajax

  // Init variables for output
  $skip = $pagecount = $filecount = $size = 0;
  $files_arr = array();
  $filterexpr = '/'.preg_quote($filter, '/').'/';
  $filterexpr = $filterexpr!='/' ? $filterexpr : false;
  $list_done = false;
  $table_started = false;
  if($pagesize && ($page >= 0))
  {
    $skip = $page*$pagesize;
  }
  $tdcount = 0;
  $tdstyle = 'td3';

  // ***** Try to use Iterator (SPL object) *****
  if(class_exists('DirectoryIterator'))
  {
    try
    {
      // Fetch all supported files and store them in $files_arr
      // Depending on $sortcolumn, the item's key is set (name/date)
      $it = new directoryIterator($folderpath);
      while( $it->valid() )
      {
        if($it->isFile() && !$it->isDot() && !$it->isDir() && !$it->isExecutable())
        {
          $filename = $it->getFilename(); // getPathname(); getBasename('.php');
		  
          if(false !== ($ext = SD_Media_Base::getExtention($filename, $all_valid_extentions)))
          {
            if($match = (!$filterexpr || preg_match($filterexpr, $filename)))
            {
              $filecount++;
              $fname_lower = strtolower($filename);
              $fdate  = $it->getMTime();
              $key    = $sortcolumn==1 ? $fname_lower: (str_pad($fdate,12,'0',STR_PAD_LEFT).'|'.$fname_lower);
              $files_arr[$key] = array(
                'filename'  => $filename,
                'fsize'     => $it->getSize(),
                'fdate'     => $fdate, //getATime();
                'ext'       => $ext
              );
            }
          }
        }
        $it->next();
      }
      $list_done = true;
      unset($it);
    }
    /*** if an exception is thrown, catch it here ***/
    catch(Exception $e)
    {
      $list_done = false;
    }
  }

  // Fallback: if iterator did not work, use "legacy" code:
  if(!$list_done)
  {
    if(!($handle = @opendir($folderpath)))
    {
      DisplayMessage(AdminPhrase('media_could_not_open_folder') . ' ' . $folderpath, true);
    }
    else
    {
      while(false !== ($filename = @readdir($handle)))
      {
        if(is_file($folderpath.$filename) && (false !== ($ext = SD_Media_Base::getExtention($filename, $all_valid_extentions))))
        {
          if(!$filterexpr || preg_match($filterexpr, $filename))
          {
            $stats = @stat($folderpath.$filename);
            $filecount++;
            $fname_lower = strtolower($filename);
            $fdate  = $stats['mtime'];
            $key    = $sortcolumn == 1 ? $fname_lower: (str_pad($fdate,12,'0',STR_PAD_LEFT).'|'.$fname_lower);
            $files_arr[$key] = array(
              'filename'  => $filename,
              'fsize'     => $stats['size'],
              'fdate'     => $fdate,
              'ext'       => $ext
            );
          }
        }
      }
      $list_done = true;
    }
  }

  // ***** Output the files array using sort options *****
  if($list_done && $filecount)
  {
    $table_started = true;
    echo '<table class="table table-bordered table-striped" summary="Files">';

    if($sortorder == 'desc')
    {
      @krsort($files_arr);
    }
    else
    {
      @ksort($files_arr);
    }
    $sd_ignore_watchdog = true;
    $idx = 0;
    foreach($files_arr as $entry)
    {
      if((!$pagesize || ($pagecount < $pagesize)) && (empty($skip) || ($idx >= $skip)))
      {
        DisplayFileRow($tdcount, $tdstyle, $folderpath, $entry['filename'], $entry['fsize'], $entry['fdate'], $entry['ext']);
        $size += (int)$entry['fsize'];
        $pagecount++;
      }
      $idx++;
    }
  }

  // Close off remaining (missing) table cells for valid table HTML
  if($table_started)
  {
    if(($tdcount > 0) && ($tdcount <= MEDIA_MAX_COLUMNS))
    {
      while($tdcount < MEDIA_MAX_COLUMNS)
      {
        $tdcount++;
        echo '<td class=""><div class="filecell">&nbsp;</div></td>';
      }
      echo '</tr>';
    }

    // Re-check page number
    if($pagesize)
    {
      $page = ceil(($skip + $pagecount) / $pagesize);
      $maxpages = ceil($filecount / $pagesize);
      $page = $page > $maxpages ? $maxpages : $page;
    }

    echo '
        <tr>
          <td class="td1" colspan="2" align="right"><b>Files: '.number_format($filecount). '</b></td>
          <td class="td1" colspan="'.($displaymode+MEDIA_MAX_COLUMNS-2).
          '" align="left"><b>'.number_format($size).' Bytes ('.
          DisplayReadableFilesize($size) .')</b></td>
        </tr>
      </table>
      <br />';
  }
  echo '
      <input type="hidden" name="page" id="currentpage" value="'.($page-1).'" />
      ';

  // pagination
  if(!empty($filecount) && $pagesize)
  {
    $p = new pagination;
    $p->items($filecount);
    $p->limit($pagesize);
    $p->currentPage($page);
    $p->adjacents(6);
    $p->target(SELF_MEDIA_PHP);
    $p->show();
  }

  if(!$ajax)
  {
    echo '
    </div>
    ';

    // Output JS code for instant uploader at last
    if($bottom_js)
    {
      echo '
    <script type="text/javascript">
    // <![CDATA[
    function processInstantFile(file_id, file_name, file_title) {
      jQuery("#'.MEDIA_UPL_TARGET.'").load(media_options.admin_path+"/admin_upload.php #uploader_response", {
        "file":   file_name,
        "title":  file_title,
        "action": "rename",
        "admin":  "Media",
        "folder": $("#instant_folder").val(),
        "form_token": media_options.media_token
      },
      function(response, status, xhr) {
        if(status == "error") {
          var msg = "<b>'.AdminPhrase('media_err_upload_js').'<\/b> ";
          $("#'.MEDIA_UPL_ID.'").html(msg + xhr.status + " " + xhr.statusText);
        }
        else {
          var filecount = jQuery("#filelist_file_media div");
          if(filecount.length === 0)
          {
            jQuery("#progress_file_media").hide();
            jQuery("#sel_folderpath").val(jQuery("#instant_folder").val());
            sdmedia.ReloadImages();
          }
        }}
      );
    }

    jQuery(document).ready(function() {
      '. $bottom_js.'
    });
    // ]]>
    </script>
    ';
    }
  }

} //DisplayImages


function DisplayFileRow(& $tdcount, & $tdstyle, $folderpath,
                        $fname, $fsize, $fdate, $ext)
{
	
  global $displaymode, $filter, $page, $pagesize,
         $sortcolumn, $sortorder,
         $media_images;
  static $rowcount = 0;
  
  

  if(empty($displaymode))
  {
    if($tdcount == 0)
    {
      echo '<tr>';
      $tdstyle = ($tdstyle == 'td2') ? 'td3' : 'td2';
    }
    $viewable = true;
    $link = '';
    $img_tag_tip = false;
    if(in_array($ext, SD_Media_Base::$valid_image_extensions))
    {
	  $img_tag = SD_Image_Helper::GetThumbnailTag($folderpath.$fname, 'img-thumbnail', '', '',
                                                  false, ($pagesize<64));
      $img_tag_tip = AdminPhrase('media_click_image_tip');
	  
    }
    else
    if(in_array($ext, SD_Media_Base::$valid_media_extensions))
    {
		
      $img_tag = '<i class="ace-icon fa fa-file-video-o blue bigger-300"></i>';
      $img_tag_tip = AdminPhrase('media_click_media_tip');
    }
    else
    {

      $img_tag = '<i class="ace-icon fa fa-file-photo-o blue bigger-300"></i>';
      $viewable = false;
    }
    if($viewable)
    {

      $link = '<a '.($img_tag_tip?'class="ceetip" title="'.$img_tag_tip.'" ':'').
              'href="'.SELF_MEDIA_PHP.'?action=display_media_details&amp;folderpath=' . $folderpath .
              '&amp;displaymode='.$displaymode.'&amp;filter='.$filter.'&amp;page='.$page.
              '&amp;sortcolumn='.$sortcolumn.'&amp;sortorder='.$sortorder.
              '&amp;imagename=' . urlencode($fname) . '">' . $img_tag. '</a>';
			  
			
    }
    else
    {
      $link = $fname;
    }

	
    echo '
    <td class="" height="100">
      <div class="filecell">
      '.$link.'<br />' . $fname . '<br />
      '.(isset($fsize)?DisplayReadableFilesize($fsize).' - ':'').(isset($fdate)?DisplayDate($fdate,'Y-m-d H:i'):'').'
      </div>
    </td>
    ';

    $tdcount++;
    if($tdcount >= MEDIA_MAX_COLUMNS)
    {
      echo '</tr>';
      $tdcount = 0;
    }
  }
  else
  {
    if(empty($rowcount))
    {
      echo '<thead><tr>
      <th class="td1" style="min-width: 150px" >Folder</th>
      <th class="td1" style="min-width: 150px" >Filename</th>
      <th class="td1" style="width: 40px" >Type</th>
      <th class="td1" style="width: 100px" width="100">Size</th>
      <th class="td1" style="width: 140px" width="140">Date</th>
      </tr></thead>';
    }
    echo '<tr>
    <td class="td2">'.$folderpath . '</td>
    <td class="td2">';
    $img_tag = '-';
    $link = '';
    if($viewable = in_array($ext, SD_Media_Base::$valid_image_extensions))
    {
      $img_tag = '<i class="ace-icon fa fa-file-photo-o blue bigger-120"></i>';
    }
    else
    if($viewable = in_array($ext, SD_Media_Base::$valid_media_extensions))
    {
      $img_tag = '<i class="ace-icon fa fa-file-video-o green bigger-120"></i>';
    }
    if($viewable)
    {
      $link = '<a title="Open File" href="'.SELF_MEDIA_PHP.'?action=display_media_details&amp;folderpath=' . $folderpath .
              '&displaymode='.$displaymode.'&imagename=' . urlencode($fname) . '">' . $fname. '</a>';
    }
    echo $link.'</td>
    <td class="td2" align="center">'.$img_tag.'</td>
    <td class="td2" align="right">'.(isset($fsize)?number_format($fsize):'-').' </td>
    <td class="td2">'.(isset($fdate)?DisplayDate($fdate,'Y-m-d H:i'):'').' </td>
    </tr>';
    $rowcount++;
  }

  return $tdcount;

} //DisplayFileRow


// ############################################################################
// SELECT FUNCTION
// ############################################################################

if(in_array($function_name, array('deleteimage','displayfilerow','displayimages',
            'displaymediadetails','docreatethumbnail','getimagefolders',
            'getwatermarkimages','uploadimage')) &&
   is_callable($function_name))
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

if(!$MediaIncluded)
DisplayAdminFooter();
