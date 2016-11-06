<?php
define('IN_PRGM', true);
define('ROOT_PATH', '../../');
if(!empty($_GET['filter']) && !empty($_GET['adm']))
{
  define('IN_ADMIN', true);
}
include(ROOT_PATH . 'includes/init.php');

# turn off any error reporting
#$sd_ignore_watchdog = true;
#@error_reporting(0);
#@ini_set('display_errors', 0);
#@ini_set('log_errors', 0);
#if(function_exists('ErrorHandler')) set_error_handler(null);

// ############################################################################
// GET REQUIRED VALUES
// ############################################################################
if($plugin_folder = sd_GetCurrentFolder(__FILE__))
{
  $pluginid = GetPluginIDbyFolder($plugin_folder);
}
$imageid = Is_Valid_Number(GetVar('imageid', 0, 'whole_number'),0,2);
if(empty($plugin_folder) || empty($pluginid) || empty($imageid))
{
  header("HTTP/1.0 403 Forbidden");
  StopLoadingPage($sdlanguage['common_attachment_denied'],$sdlanguage['common_attachment_denied'],403,'',false);
  $DB->close();
  exit();
}

// INCLUDE MEDIA GALLERY CLASSES
$path = ROOT_PATH.'plugins/'.$plugin_folder.'/';
require_once($path.'gallery_lib.php');

$base = new GalleryBaseClass($plugin_folder);
if(!$base->InitFrontpage()) exit();
$base->seo_enabled = false; // Important!
$base->seo_redirect = false; // Important!
if(!$base->SetImageAndSection($imageid) ||
   (empty($base->IsSiteAdmin) && empty($base->IsAdmin) &&
    empty($base->section_arr['can_view'])))
{
  header("HTTP/1.0 403 Forbidden");
  StopLoadingPage($sdlanguage['common_attachment_denied'],$sdlanguage['common_attachment_denied'],403);
  $DB->close();
  exit();
}

$image_name = $base->image_arr['filename'];
$image_path = ROOT_PATH.$base->IMAGEDIR.$base->image_arr['folder'].$image_name;
if(!file_exists($image_path))
{
  StopLoadingPage($sdlanguage['common_attachment_unavailable'],$sdlanguage['common_attachment_unavailable'],404);
  $DB->close();
  exit();
}

require_once(SD_INCLUDE_PATH.'class_sd_media.php');
$img = new SD_Image($image_path);

// Send the image
#127.0.0.1:8080/sdcom/plugins/media_gallery/img.php?imageid=193

header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
header("Cache-Control: private");
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
if(Is_Ajax_Request())
{
  header('Content-type:text/html; charset=' . SD_CHARSET);
}
else
{
  header('Content-Type: '.$img->getMimeType());
  header('Content-Transfer-Encoding: binary');
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
}

// Is filtering requested (must have passed valid form token!)
if(GetVar('filter', false, 'bool') /*&& CheckFormToken()*/)
{
  if(!Is_Ajax_Request())
  {
    header('Content-Disposition: inline; filename="'.addslashes($image_name).'"');
    header('Content-type:'.$img->getMimeType());
  }
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

  // Cropping before filter run:
  if(GetVar('crop', false, 'bool'))
  {
    $cropx1 = Is_Valid_Number(GetVar('x1', 0, 'natural_number'),0,0,SD_MEDIA_MAX_DIM);
    $cropy1 = Is_Valid_Number(GetVar('y1', 0, 'natural_number'),0,0,SD_MEDIA_MAX_DIM);
    $cropx2 = Is_Valid_Number(GetVar('x2', 0, 'natural_number'),0,0,SD_MEDIA_MAX_DIM);
    $cropy2 = Is_Valid_Number(GetVar('y2', 0, 'natural_number'),0,0,SD_MEDIA_MAX_DIM);
    // sanity check on values:
    if(($cropx1 < $cropx2) && ($cropy1 < $cropy2) && ($cropx2 > 0) && ($cropy2 > 0))
    {
      $conf_arr = array('crop'=>true,'x1'=>$cropx1,'y1'=>$cropy1,'x2'=>$cropx2,'y2'=>$cropy2);
      if(!$img->CreateThumbnail($img->image_file, $img->getImageWidth(), $img->getImageHeight(),
                                false, true, $conf_arr))
      {
        die('ERROR: '.$img->getErrorMessage());
      }
    }
  }

  $saveimage = false;
  if(GetVar('save', false, 'bool') && CheckFormToken('formtoken'))
  {
    $saveimage = $img->image_file;
  }
  $img->FilterImage($options, (!Is_Ajax_Request() || $saveimage), $saveimage);
}
else
{
  if(!Is_Ajax_Request())
  {
    header('Content-Length: '.$img->getSize());
    header('Content-Disposition: attachment; filename="'.addslashes($image_name).'"');
    header('Content-type:'.$type);
    readfile($image_path);
  }
}

unset($img,$base);
$DB->close();
unset($DB);
