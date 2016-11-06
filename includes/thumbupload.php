<?php
// DEFINE CONSTANTS
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');

defined('MEDIA_MAX_COLUMNS') || define('MEDIA_MAX_COLUMNS', 4);
defined('SD_DEFAULT_THUMB_WIDTH') || define('SD_DEFAULT_THUMB_WIDTH', 170);
defined('SD_DEFAULT_THUMB_HEIGHT') || define('SD_DEFAULT_THUMB_HEIGHT', 100);
defined('COOKIE_THUMB_FILTER') || define('COOKIE_THUMB_FILTER', '_thumb_filter');

// INIT PRGM
include(ROOT_PATH . 'includes/init.php');
if(empty($userinfo['adminaccess']) && empty($userinfo['pluginadminids']))
{
  die("No permissions!");
}
require_once(ROOT_PATH.'includes/enablegzip.php');
require_once(SD_INCLUDE_PATH.'class_sd_media.php');

$ajax = Is_Ajax_Request();
$media_phrases = LoadAdminPhrases(3);
$selection_note = $media_phrases['media_image_selection_note'];

defined('BLANK_IMG') || define('BLANK_IMG', SD_INCLUDE_PATH.'css/images/blank.gif');
defined('BLANK_IMG_TAG') || define('BLANK_IMG_TAG', '<img alt="" src="'.BLANK_IMG.'" width="16" height="16" />');

function ThumbBrowser()
{
  global $ajax, $action, $allowed_image_ext, $media_phrases, $dothumb, $upload_dir;

  define('SELF_MEDIA_PHP', $_SERVER['PHP_SELF']);
  $all_pagesizes = array(0,12,24,36,48,96,120,240,360,480);
  $list_done = false;
  $files_arr = array();
  $filecount = 0;
  $table_started = false;

  $folderpath   = ROOT_PATH.$upload_dir;
  #$folderpath   = GetVar('folderpath', MEDIA_DEFAULT_DIR, 'string');
  $folderpath  .= (substr($folderpath,-1)=='/'?'':'/');
  $displaymode  = Is_Valid_Number(GetVar('displaymode', 0, 'natural_number'),0,0,1);
  $filter       = substr(GetVar('filter', '', 'string'),0,50);
  $filter       = trim(strip_tags($filter));
  $filterexpr   = '/'.preg_quote($filter, '/').'/';
  $filterexpr   = $filterexpr!='/' ? $filterexpr : false;
  $sortcolumn   = Is_Valid_Number(GetVar('sortcolumn', null, 'natural_number'),0,0,1);
  # $sortcolumn: 1 = Filename ASC, 0 = Filedate DESC
  $sortorder    = GetVar('sortorder', null, 'string');
  $page         = Is_Valid_Number(GetVar('page', 0, 'natural_number'),0,0,999);
  $pagesize     = GetVar('pagesize', 12, 'natural_number'); // multiple of 12
  $pagesize     = ($pagesize=='all'?0:Is_Valid_Number($pagesize, 12, 0, 480));
  if(!in_array($pagesize, $all_pagesizes))
  {
    $pagesize = 12;
  }
  #$pagesize = MEDIA_MAX_COLUMNS * 3;
  $skip = 0;
  if($pagesize && ($page > 0))
  {
    $skip = $page*$pagesize;
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

  try
  {
    // Fetch all supported files and store them in $files_arr
    // Depending on $sortcolumn, the item's key is set (name/date)
    $it = new directoryIterator($folderpath);
    while( $it->valid() )
    {
      if($it->isFile() && !$it->isDot() && !$it->isDir() && !$it->isExecutable())
      {
        $filename = $it->getFilename();
        if(false !== ($ext = SD_Media_Base::getExtention($filename, $allowed_image_ext)))
        {
          if($match = (!$filterexpr || preg_match($filterexpr, $filename)))
          {
            $filecount++;
            $fname_lower = strtolower($filename);
            $fdate = $it->getMTime();
            $key = ($sortcolumn==1) ? $fname_lower: (str_pad($fdate,12,'0',STR_PAD_LEFT).'|'.$fname_lower);
            $files_arr[$key] = array(
              'filename'  => $filename,
              'fsize'     => $it->getSize(),
              'fdate'     => $fdate,
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
  // ###########################################
  if(!$list_done || empty($files_arr)) return;
  // ###########################################

  if($sortorder=='asc')
    @ksort($files_arr);
  else
    @krsort($files_arr);
  $GLOBALS['sd_ignore_watchdog'] = true;
  $tdcount = $pagecount = $size = 0;
  $tdstyle = 'td3';

  if(!$ajax)
  {
    echo '<div class="content">';
    StartSection('Images: '.htmlspecialchars($folderpath));

    echo '
    <form id="browse_files" method="post" action="'.SELF_MEDIA_PHP.'" style="clear: both;">
    <table class="table table-bordered table-striped">
    <tr>
      <td class="td2" valign="top">
      <div id="toolbar">
        <input type="hidden" name="action" value="displayimages" />
        <input type="hidden" id="sortorder" name="sortorder" value="'.$sortorder.'" />
        <input type="hidden" id="sel_folderpath" name="folderpath" value="'.$folderpath.'" />
        '.$media_phrases['media_filter'].' <input id="filter" name="filter" type="text" value="'.$filter.'" size="10" />
        <a id="del_filter" class="button-small" href="#" title="'.$media_phrases['media_clear_filter'].'">'.BLANK_IMG_TAG.'</a>
        <select id="sortcolumn" name="sortcolumn" style="margin: 2px;">
          <option value="0" '.( empty($sortcolumn)?'selected="selected"':'').'>'.$media_phrases['media_sort_date'].'</option>
          <option value="1" '.(!empty($sortcolumn)?'selected="selected"':'').'>'.$media_phrases['media_sort_name'].'</option>
        </select>
        <a id="sort_asc" class="button-small" href="#" title="'.$media_phrases['media_sort_asc'].'">'.BLANK_IMG_TAG.'</a>
        <a id="sort_desc" class="button-small" href="#" title="'.$media_phrases['media_sort_descending'].'">'.BLANK_IMG_TAG.'</a>
        &nbsp;'.$media_phrases['media_displaymode'].
        '<select id="displaymode" name="displaymode" style="margin: 2px;">
          <option value="0" '.( empty($displaymode)?'selected="selected"':'').'>'.$media_phrases['media_thumbnails'].'</option>
          <option value="1" '.(!empty($displaymode)?'selected="selected"':'').'>'.$media_phrases['media_details'].'</option>
        </select>&nbsp;'.
        '<select id="pagesize" name="pagesize" style="margin: 2px;">
          <option value="all" '.( empty($pagesize)?'selected="selected"':'').'>'.$media_phrases['media_pagesize_all'].'</option>';
        foreach($all_pagesizes as $ps)
        {
          if($ps)
          echo '
          <option value="'.$ps.'" '.($pagesize==$ps?'selected="selected"':'').'>'.$ps.'</option>';
        }
        echo '
        </select>
        <img id="files_loader" style="display: none; margin-top: 8px;" alt="" src="'.SD_INCLUDE_PATH.'css/images/indicator.gif" width="16" height="16" />
      </div>
      </td>
    </tr>
    </table>
    </form>
    ';
    EndSection();

    echo '
    <div id="files_list">
    ';
  } // !Ajax

  echo '
  <table class="table table-bordered table-striped" summary="Files">';
  $folderpath .= '/';
  $idx = 0;
  foreach($files_arr as $entry)
  {
    if((!$pagesize || ($pagecount < $pagesize)) && (empty($skip) || ($idx >= $skip)))
    {
      DisplayFileRow($pagesize, $tdcount, $tdstyle, $folderpath, $entry['filename'], $entry['fsize'],
                     $entry['fdate'], $entry['ext']);
      $size += (int)$entry['fsize'];
      $pagecount++;
    }
    $idx++;
  }
  $GLOBALS['sd_ignore_watchdog'] = false;
  if(($tdcount > 0) && ($tdcount <= MEDIA_MAX_COLUMNS))
  {
    while($tdcount < MEDIA_MAX_COLUMNS)
    {
      $tdcount++;
      echo '<td class="'.$tdstyle.'"><div class="filecell">&nbsp;</div></td>';
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
      <td class="td1" colspan="2" align="right"><strong>Files: '.number_format($filecount). '</strong></td>
      <td class="td1" colspan="'.(MEDIA_MAX_COLUMNS-2).
      '" align="left"><strong>Listing '.$pagecount.' Files with '.number_format($size).' Bytes ('.
      DisplayReadableFilesize($size) .')</strong></td>
    </tr>
  </table>
  <br />
  <input type="hidden" name="page" id="currentpage" value="'.($page-1).'" />';

  // pagination
  if(!empty($filecount) && $pagesize)
  {
    $p = new pagination;
    $p->items($filecount);
    $p->limit($pagesize);
    $p->currentPage($page);
    $p->adjacents(6);
    $p->target($_SERVER['PHP_SELF'].'?action='.$action);
    $p->show();
  }

  if(!$ajax)
  {
    echo '<br /><br />
    </div>
    </div>
    ';
  }

} //ThumbBrowser


// ############################################################################
// DISPLAY A ROW OF THUMBS
// ############################################################################

function DisplayFileRow($pagesize, & $tdcount, & $tdstyle, $folderpath, $fname, $fsize, $fdate, $ext)
{
  global $allowed_image_ext;
  static $rowcount = 0;

  if($tdcount == 0)
  {
    echo '<tr>';
    $tdstyle = ($tdstyle == 'td2') ? 'td3' : 'td2';
  }
  $viewable = true;
  $link = '';
  $img_tag_tip = false;
  if(in_array($ext, $allowed_image_ext))
  {
    $img_tag = SD_Image_Helper::GetThumbnailTag($folderpath.$fname, '', '', '',
                                                false, ($pagesize<=32));
    $img_tag_tip = 'Click to select as thumbnail';
  }
  else
  {
    $img_tag = '<img alt="image" width="64" height="64" src="'.SITE_URL.'includes/images/image.png" />';
    $viewable = false;
  }
  if($viewable)
  {
    $link = '<a '.($img_tag_tip?'class="ceetip" title="'.$img_tag_tip.'" ':'').
            'href="#" onclick="javascript:returnThumbToParent(\''.urlencode($fname).'\');">'.
            $img_tag. '</a>';
  }
  else
  {
    $link = $fname;
  }
  echo '
  <td class="' . $tdstyle . '" height="100">
    <div class="filecell">
    '.$link.'<br />' . $fname . '<br />
    '.(isset($fsize)?DisplayReadableFilesize($fsize).' - ':'').
    (isset($fdate)?DisplayDate($fdate,'Y-m-d H:i'):'').'
    </div>
  </td>
  ';

  $tdcount++;
  if($tdcount >= MEDIA_MAX_COLUMNS)
  {
    echo '</tr>';
    $tdcount = 0;
  }

  return $tdcount;

} //DisplayFileRow

##########################################################################################################
# IMAGE FUNCTIONS
# You do not need to alter these functions
##########################################################################################################

//You do not need to alter these functions
function resizeThumbnailImage($thumb_image_name, $image,
           $width, $height, $start_width, $start_height, $scale)
{
  list($imagewidth, $imageheight, $imageType) = @getimagesize($image);
  if(empty($imagewidth) || empty($imageheight)) return false;
  $imageType = @image_type_to_mime_type($imageType);

  $newImageWidth  = ceil($width * $scale);
  $newImageHeight = ceil($height * $scale);
  if(false === ($newImage = @imagecreatetruecolor($newImageWidth,$newImageHeight)))
  {
    return false;
  }
  switch($imageType) {
    case "image/gif":
      $source = @imagecreatefromgif($image);
      break;
    case "image/pjpeg":
    case "image/jpeg":
    case "image/jpg":
      $source = @imagecreatefromjpeg($image);
      break;
    case "image/png":
    case "image/x-png":
      $source = @imagecreatefrompng($image);
      break;
  }
  @imagecopyresampled($newImage,$source,0,0,
                      $start_width,$start_height,
                      $newImageWidth,$newImageHeight,
                      $width,$height);
  switch($imageType) {
    case "image/gif":
      imagegif($newImage,$thumb_image_name);
    break;
    case "image/pjpeg":
    case "image/jpeg":
    case "image/jpg":
      imagejpeg($newImage,$thumb_image_name,90);
      break;
    case "image/png":
    case "image/x-png":
      imagepng($newImage,$thumb_image_name);
      break;
  }
  if(file_exists($thumb_image_name))
  {
    @chmod($thumb_image_name, 0644);
    return $thumb_image_name;
  }
  return false;
}

// ############################################################################
// ############################################################################

/* Takes any uploaded image and allows the user to crop and save as a thumbnail for their article */
$thumb_cookie = 'sdthumb-'.$userinfo['userid'];
$action = GetVar('action', '', 'string'); // Preliminary action for this script, influences upload folder

//Clear the time stamp session and user file extension
if(!empty($_GET['new']) && (empty($action) || ($action !== 'featured')))
{
  sd_CreateCookie($thumb_cookie, '');
  unset($_COOKIE[$thumb_cookie]);
  header('Location: '.$_SERVER['PHP_SELF'].'?action='.$action);
}

$thumb_session = isset($_COOKIE[COOKIE_PREFIX.$thumb_cookie]) ? $_COOKIE[COOKIE_PREFIX.$thumb_cookie] : 0;
if(!empty($thumb_session) && is_string($thumb_session))
{
  if($thumb_session = @base64_decode($thumb_session))
  {
    if(!empty($thumb_session) && substr($thumb_session,0,2)=='a:')
    {
      $thumb_session = @unserialize($thumb_session);
    }
    else
    {
      $thumb_session = false;
    }
  }
  else
  {
    $thumb_session = false;
  }
}
if(empty($thumb_session) || empty($thumb_session['random_key']) ||
   !preg_match('#^(\d{1,6})-(\d{1,11})$#',$thumb_session['random_key'],$matches) ||
   ($matches[1]!=$userinfo['userid']) ||($matches[2]<(TIME_NOW-86400))
   )
{
  $thumb_session = array('random_key' => $userinfo['userid'].'-'.strtotime(date('Y-m-d H:i:s')),
                         'user_file_ext' => '');
  sd_CreateCookie($thumb_cookie, base64_encode(serialize($thumb_session)));
}

if($action=='featured')
{
  $dothumb = false;
  $upload_dir  = 'images/featuredpics';  // Directory for featured images
  $large_image_prefix = 'featured_';     // The prefix name to large image
  $thumb_image_prefix = '';              // The prefix name to the thumb image
}
else
{
  $action  = 'thumb';
  $dothumb = true;
  $upload_dir  = 'images/articlethumbs'; // Directory for thumbnails
  $large_image_prefix = 'resize_';       // The prefix name to large image
  $thumb_image_prefix = 'thumb_';        // The prefix name to the thumb image
}
$thumb_width  = GetVar('thumb_width', SD_DEFAULT_THUMB_WIDTH, 'whole_number'); // Width of thumbnail image
$thumb_width  = Is_Valid_Number($thumb_width, SD_DEFAULT_THUMB_WIDTH, 20, 1000);
$thumb_height = GetVar('thumb_height', SD_DEFAULT_THUMB_HEIGHT, 'whole_number'); // Height of thumbnail image
$thumb_height = Is_Valid_Number($thumb_height, SD_DEFAULT_THUMB_HEIGHT, 20, 1000);
$upload_path = $upload_dir.'/'; // The path to where the image will be saved
$large_image_name = $large_image_prefix.$thumb_session['random_key']; // New name of the large image (append the timestamp to the filename)
$thumb_image_name = $thumb_image_prefix.$thumb_session['random_key']; // New name of the thumbnail image (append the timestamp to the filename)
$max_file         = 3;   // Maximum file size in MB
$max_width        = 500; // Max width allowed for the large image

// Only one of these image types should be allowed for upload
$allowed_image_types = array('image/pjpeg'=>"jpg",'image/jpeg'=>"jpg",'image/jpg'=>"jpg",'image/png'=>"png",'image/x-png'=>"png",'image/gif'=>"gif");
$allowed_image_ext   = array_unique($allowed_image_types); // do not change this
$image_ext           = '';  // initialise variable, do not change this.
foreach ($allowed_image_ext as $mime_type => $ext)
{
  $image_ext.= strtoupper($ext).' ';
}

//Image Locations
$large_image_location = ROOT_PATH.$upload_path.$large_image_name.
                        $thumb_session['user_file_ext'];
$thumb_image_location = ROOT_PATH.$upload_path.$thumb_image_name.
                        $thumb_session['user_file_ext'];

//Create the upload directory with the right permissions if it doesn't exist
if(!is_dir(ROOT_PATH.$upload_dir))
{
  @mkdir(ROOT_PATH.$upload_dir, 0777);
  @chmod(ROOT_PATH.$upload_dir, 0777);
}

//Check to see if any images with the same name already exist
if(file_exists($large_image_location))
{
  if(file_exists($thumb_image_location))
  {
    $thumb_photo_exists = '<img src="'.$sdurl.$upload_path.
      $thumb_image_name.$thumb_session['user_file_ext'].
      '" alt="Thumbnail Image" />';
  }
  else
  {
    $thumb_photo_exists = '';
  }
  $large_photo_exists = '<img src="'.$sdurl.$upload_path.$large_image_name.$thumb_session['user_file_ext'].'" alt="Large Image" />';
}
else
{
  $large_photo_exists = '';
  $thumb_photo_exists = '';
}


if($ajax)
{
  ThumbBrowser();
  exit;
}


if(!empty($_POST["upload"]))
{
  $img_obj = false;
  $res = SD_Image_Helper::UploadImageAndCreateThumbnail(
                          'image', true, $upload_path,
                          $large_image_name, ''/*$thumb_image_name*/,
                          $thumb_width, $thumb_height,
                          ($max_file * 1048576),
                          false, true, true, $img_obj);
  if($res === true)
  {
    //Refresh the page to show the newly uploaded image
    $thumb_session['user_file_ext'] = '.'.$img_obj->getImageExt();
    sd_CreateCookie($thumb_cookie, base64_encode(serialize($thumb_session)));
    $newloc = $_SERVER["PHP_SELF"].'?action='.$action;
    $newloc .= '&amp;upload_thumbnail=1&amp;thumb_width='.(empty($thumb_width)?SD_DEFAULT_THUMB_WIDTH:(int)$thumb_width);
    $newloc .= '&amp;thumb_height='.(empty($thumb_height)?SD_DEFAULT_THUMB_HEIGHT:(int)$thumb_height);
    header("location:".$newloc);
    exit();
  }
  else $error = $res;
  unset($img_obj);
}

if(isset($_POST["upload_thumbnail"]) && strlen($large_photo_exists))
{
  //Get the new coordinates to crop the image.
  $x1 = GetVar('x1', 0, 'natural_number', true, false);
  $y1 = GetVar('y1', 0, 'natural_number', true, false);
  $x2 = GetVar('x2', 0, 'natural_number', true, false);
  $y2 = GetVar('y2', 0, 'natural_number', true, false);
  $w  = Is_Valid_Number(GetVar('w',  1, 'natural_number', true, false),1,1,9999);
  $h  = Is_Valid_Number(GetVar('h',  1, 'natural_number', true, false),1,1,9999);
  //Scale the image to the thumb_width set above
  $scale = $thumb_width / $w;
  $cropped = resizeThumbnailImage($thumb_image_location, $large_image_location,
                                  $w, $h, $x1, $y1, $scale);
  //Reload the page again to view the thumbnail
  header("location:".$_SERVER["PHP_SELF"].'?action='.$action);
  exit();
}

$admin_url = $sdurl.ADMIN_PATH.'/';

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<base href="<?php echo $admin_url; ?>" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Image Uploader</title>
<script type="text/javascript">
//<![CDATA[
var sdurl = "<?php echo $admin_url; ?>";
//]]>
</script>
<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="<?=SITE_URL ?>includes/css/font-awesome.min.css" />

		<!-- page specific plugin styles -->

		<!-- text fonts -->
		<link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/ace-fonts.css" />

		<!-- ace styles -->
		<link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/ace.min.css" id="main-ace-style" />

		<!--[if lte IE 9]>
			<link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/ace-part2.min.css" />
		<![endif]-->
		<link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/ace-skins.min.css" />
		<link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/ace-rtl.min.css" />

		<!--[if lte IE 9]>
		  <link rel="stylesheet" href="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/css/ace-ie.min.css" />
         <![endif]-->
         
         <link rel="stylesheet" type="text/css" href="<?php echo $sdurl.ADMIN_STYLES_FOLDER; ?>assets/css/admin.css.php" />
<link rel="stylesheet" type="text/css" href="<?php echo $sdurl.ADMIN_STYLES_FOLDER; ?>assets/css/media.css.php" />
<link rel="stylesheet" type="text/css" href="<?php echo $sdurl.ADMIN_STYLES_FOLDER; ?>assets/css/pagination.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $sdurl; ?>includes/css/imgareaselect-animated.css" />
         
        <!-- ace settings handler -->
		<script src="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/js/ace-extra.min.js"></script>

		<!-- HTML5shiv and Respond.js for IE8 to support HTML5 elements and media queries -->

		<!--[if lte IE 8]>
		<script src="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/js/html5shiv.min.js"></script>
		<script src="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/js/respond.min.js"></script>
		<![endif]-->
        
        <!-- basic scripts -->

		<!--[if !IE]> -->
		<script type="text/javascript">
			window.jQuery || document.write("<script src='<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/js/jquery.min.js'>"+"<"+"/script>");
		</script>

		<!-- <![endif]-->

		<!--[if IE]>
        <script type="text/javascript">
         window.jQuery || document.write("<script src='<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/js/jquery1x.min.js'>"+"<"+"/script>");
        </script>
        <![endif]-->
		<script type="text/javascript">
			if("ontouchstart" in document.documentElement) document.write("<script src='<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
		</script>
		<script src="<?=SITE_URL . ADMIN_STYLES_FOLDER?>assets/js/bootstrap.min.js"></script>

<script type="text/javascript" src="<?php echo $sdurl; ?>includes/javascript/jquery-migrate-1.2.1.min.js"></script>
<script type="text/javascript" src="<?php echo $sdurl.MINIFY_PREFIX_G; ?>admin,admin_media"></script>
<script type="text/javascript">
// <![CDATA[
if(typeof(jQuery) !== "undefined"){
  jQuery(document).ready(function(){
    (function($){	
	
	//$("#file-upload").ace_file_input();

    }(jQuery));
  });
}
var img_width = 0, img_height = 0, pre_width = 0, pre_height = 0, pos = 0;
var img_form = false, ias = false, scale_x = 1, scale_y = 1, previewbox;
var media_options = {
  action: "displayimages",
  admin_path: "<?php echo ROOT_PATH.ADMIN_PATH; ?>",
  load_page: "<?php echo $sdurl.'includes/thumbupload.php?action='.$action; ?>",
  toolbar_cookie_name: "<?php echo COOKIE_PREFIX.COOKIE_THUMB_FILTER; ?>",
  style_folder_name: "<?php echo ADMIN_STYLE_FOLDER_NAME; ?>",
  sd_include_path: "<?php echo SD_INCLUDE_PATH; ?>",
  default_thumb_width: 0<?php echo SD_DEFAULT_THUMB_WIDTH; ?>,
  default_thumb_height: 0<?php echo SD_DEFAULT_THUMB_HEIGHT; ?>,
  selection_note: "<?php echo htmlspecialchars($selection_note,ENT_COMPAT); ?>",
  media_max_colums: 0<?php echo MEDIA_MAX_COLUMNS; ?>,
  media_token: "<?php echo SD_FORM_TOKEN; ?>",
  media_confirm_deletion: "<?php echo htmlspecialchars(AdminPhrase('media_confirm_deletion'),ENT_QUOTES); ?>",
  media_image_err_sizes: "<?php echo htmlspecialchars(AdminPhrase('media_image_err_sizes'),ENT_QUOTES); ?>",
  media_image_err_folder: "<?php echo htmlspecialchars(AdminPhrase('media_image_err_folder'),ENT_QUOTES); ?>"
};
function returnToParent()
{
  if(typeof(parent.jQuery.fn.ceebox) !== "undefined") {
    var is_thumb = 0<?php echo $dothumb ? 1 : 0; ?>;
    var elem_name = is_thumb==1 ? 'thumbnail' : 'featured';
    var elem_value = document.getElementById(elem_name+'path').value;
    try { parent.UpdateImage(is_thumb, elem_name, elem_value); } catch(e){}
    setTimeout(function(){ parent.jQuery.fn.ceebox.closebox('fast'); }, 100);
  }
}
function returnThumbToParent(thumbname)
{
  if(typeof(parent.jQuery.fn.ceebox) !== "undefined") {
    var is_thumb = 0<?php echo $dothumb ? 1 : 0; ?>;
    var elem_name = is_thumb==1 ? 'thumbnail' : 'featured';
    try { parent.UpdateImage(is_thumb, elem_name, thumbname); } catch(e){}
    setTimeout(function(){ parent.jQuery.fn.ceebox.closebox('fast'); }, 100);
  }
}
function ApplyCeebox(){
  if(typeof(jQuery.fn.ceebox) !== "undefined") {
    jQuery("a.cbox").attr("rel", "iframe modal:false");
    '.GetCeeboxDefaultJS(false).'
  }
}
// ]]>
</script>
<script type="text/javascript" src="<?php echo $sdurl.ADMIN_PATH.'/javascript/page_media.js?'.TIME_NOW; ?>"></script>
<style type="text/css">
body { background-color: #fff; padding: 10px; overflow: auto; }
div#wrapper { width: 95% !important; background-color: #fff; padding: 4px; margin: 0; height: auto; }
div#content { width: 95% !important; overflow: hidden; }
div#content ol,
div#content ul { list-style-position: inside; }
div#content ol li { padding-left: 20px; }
</style>
</head>
<body>
<div id="wrapper"><div id="content">
<?php
if($dothumb)
{
  echo '<h1 class="header blue">Thumbnailer </h1>
  <div class="well">
<h2 class="header blue lighter">Instructions:</h2>
<ol>
  <li>Select and upload your image.</li>
  <li>Drag your mouse over the uploaded picture to make a thumbnail on the right side.</li>
  <li>When you are satisfied with how your thumbnail looks, click the "Save Thumbnail" button.</li>
  <li>Copy the image name below the new thumbnail and paste it into the "<strong>Thumbnail Image Path</strong>" entry box on the article page.</li>
</ol></div>';
}
else
{
  echo '<h1 class="header blue"> Featured Image Upload </h1>
  <div class="well">
<h2 class="header blue lighter">>Instructions:</h2>
<ol>
  <li>Browse locally for an image and let it upload.</li>
  <li>Click any one existing image for it to be selected as the "Featured Image" in the article.</li>
</ol></div>';
}

//Only display the javacript if an image has been uploaded
if($dothumb && strlen($large_photo_exists))
{
  list($current_large_image_width,$current_large_image_height) = @getimagesize($large_image_location);
  $t = $current_large_image_width*$current_large_image_height;
?>
<script type="text/javascript">
function preview(img, selection) {
  var scaleX = 0<?php echo (int)$thumb_width;?> / selection.width;
  var scaleY = 0<?php echo (int)$thumb_height;?> / selection.height;
  $('#thumbnail + div > img').css({
    width: Math.round(scaleX * 0<?php echo (int)$current_large_image_width; ?>) + 'px',
    height: Math.round(scaleY * 0<?php echo (int)$current_large_image_height; ?>) + 'px',
    marginLeft: '-' + Math.round(scaleX * selection.x1) + 'px',
    marginTop: '-' + Math.round(scaleY * selection.y1) + 'px'
  });
  $('#x1').val(selection.x1);
  $('#y1').val(selection.y1);
  $('#x2').val(selection.x2);
  $('#y2').val(selection.y2);
  $('#w').val(selection.width);
  $('#h').val(selection.height);
  $('#th').val("<?php echo (int)$thumb_height;?>");
  $('#tw').val("<?php echo (int)$thumb_width;?>");
}
$(document).ready(function () {
  $('#save_thumb').click(function() {
    var x1 = $('#x1').val();
    var y1 = $('#y1').val();
    var x2 = $('#x2').val();
    var y2 = $('#y2').val();
    var w = $('#w').val();
    var h = $('#h').val();
    if(x1=="" || y1=="" || x2=="" || y2=="" || w=="" || h==""){
      alert("You must make a selection first");
      return false;
    }else{
      return true;
    }
  });
});
$(window).load(function () {
  $('#thumbnail').imgAreaSelect({ aspectRatio: '1:<?php echo $thumb_height/$thumb_width;?>', onSelectChange: preview });
});
</script>
<?php
}

//Display error message if there are any
if(!empty($error))
{
  echo '<ul><li><strong>Error!</strong></li><li>'.$error.'</li></ul>';
}

if(!$dothumb && isset($large_photo_exists) && strlen($large_photo_exists))
{
  // Featured Image Upload
  echo $thumb_photo_exists.'
  <br /><br />
  2.) Copy the below image name and paste it in your article\'s "Featured Image" entry box <strong>or</strong> click below button!
  <br /><br />
  <input type="text" size="50" id="featuredpath" value="'.$thumb_image_name.$thumb_session['user_file_ext'] . '"><br />
  <input type="button" onclick="returnToParent();" value="Send to Article Form" />
  <hr />
  <p><a href="'.$_SERVER["PHP_SELF"].'?new=1">Click here to upload another Featured Image!</a></p>
  ';
}
else
if($dothumb && isset($large_photo_exists) && strlen($large_photo_exists) && isset($thumb_photo_exists) && strlen($thumb_photo_exists))
{
  // Thumbnail Image Upload
  echo $thumb_photo_exists.'
  <br /><br />
  4.) Click below button to assign the thumbnail to your article.<br />
  <br />
  <input type="text" size="50" id="thumbnailpath" value="'.$thumb_image_name.$thumb_session['user_file_ext'] . '" disabled><br /><br />
  <button class="btn btn-info" type="button" onclick="returnToParent();" value="" /><i class="ace-icon fa fa-arrow-right"></i>Send to Article Form</button>
  <div class="hr"></div>
  <p><a href="'.$_SERVER["PHP_SELF"].'?new=1">Click here to create a new thumbnail!</a></p>
  ';
}
else
{
  if($dothumb && isset($large_photo_exists) && strlen($large_photo_exists))
  {?>
    <h2>2.) Create Thumbnail</h2>
    <div class="center">
      <img src="<?php echo $sdurl.$upload_path.$large_image_name.$thumb_session['user_file_ext'];?>" style="float: left; margin-right: 10px;" id="thumbnail" alt="Create Thumbnail" />
      <div style="border:1px #e5e5e5 solid; float:left; position:relative; overflow:hidden; width:<?php echo $thumb_width;?>px; height:<?php echo $thumb_height;?>px;">
        <img src="<?php echo $sdurl.$upload_path.$large_image_name.$thumb_session['user_file_ext'];?>" style="position: relative;" alt="Thumbnail Preview" />
      </div>
      <br style="clear:both;" />
      <form name="thumbnail" action="<?php echo $_SERVER["PHP_SELF"];?>" method="post" class="form-horizontal">
        <input type="hidden" name="x1" value="" id="x1" />
        <input type="hidden" name="y1" value="" id="y1" />
        <input type="hidden" name="x2" value="" id="x2" />
        <input type="hidden" name="y2" value="" id="y2" />
        <input type="hidden" name="w" value="" id="w" />
        <input type="hidden" name="h" value="" id="h" />
        <input type="hidden" name="thumb_height" value="" id="th" />
        <input type="hidden" name="thumb_width" value="" id="tw" />
        <br />
        <button class="btn btn-info" type="submit" name="upload_thumbnail" value="" id="save_thumb" /><i class="ace-icon fa fa-save"></i> 3.) Save Thumbnail</button>
      </form>
    </div>
  <hr />
  <?php
  }
  ?>
  <h2 class="header blue lighter">1.) Upload Image (png, jpg, gif)</h2>
  <form name="photo" enctype="multipart/form-data" action="<?php echo $_SERVER["PHP_SELF"];?>" method="post" class="form-horizontal">
  <?php
  if($dothumb)
  {
    echo '
		<div class="form-group">
			<label class="control-label col-sm-2">Width (in pixels)</label>
			<div class="col-sm-6">
			 <input type="text" class="input-small" name="thumb_width" maxlength="4" value="'.(empty($thumb_width)?SD_DEFAULT_THUMB_WIDTH:(int)$thumb_width).'" />
			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-sm-2">Height (in pixels)</label>
			<div class="col-sm-6">
				<input type="text" name="thumb_height" class="input-small" maxlength="4" value="'.(empty($thumb_height)?SD_DEFAULT_THUMB_HEIGHT:(int)$thumb_height).'" />
			</div>
		</div>';
  }
  ?>
  <div class="form-group">
  	<label class="control-label col-sm-2">Image</label>
    <div class="col-sm-6">
    	<input type="hidden" name="action" value="<?php echo $action; ?>" />
  		<input type="file" name="image" size="30" id="file-upload" />
    </div>
  </div>
  <div class="align-left">
  		<button class="btn btn-info" type="submit" name="upload" value="Upload"><i class="ace-icon fa fa-plus"></i> Upload</button>
   </div>
  <br />
  Max. allowed filesize: <?php echo $max_file; ?>MB (site may allow less!)<br />
  </form>
<?php
  ThumbBrowser(); //SD351
}
?>
</div></div>
</body>
</html>