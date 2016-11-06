<?php

// DEFINE CONSTANTS
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../../../../');
define('IM_THUMB_WIDTH', 120);
define('IM_THUMB_HEIGHT', 120);

// INIT PRGM
require(ROOT_PATH . 'includes/init.php');
require(SD_INCLUDE_PATH.'class_sd_media.php');

$admin_phrases = LoadAdminPhrases(3);

CheckAdminAccess();

// GET ACTION AND FOLDER PATH
$action     = GetVar('action', 'displayimages', 'string');
$folderpath = urldecode(trim(GetVar('folderpath', ROOT_PATH . 'images/', 'string')));
$folderpath = str_replace('*','../', $folderpath);

// Invalidate below-root access:
if((substr($folderpath,0,1)=='/') ||
   (substr_count($folderpath, '../') > substr_count(ROOT_PATH, '../')) ||
   !is_dir($folderpath) || !is_readable($folderpath))
{
  $action = 'displayimages';
  $folderpath = ROOT_PATH . 'images/';
}
else
{
  $folderpath .= substr($folderpath,-1)=='/'?'':'/';
}

// Note: known_photo_types declared in init.php!
$valid_media_extensions = array(
  'flv', 'mp3', 'mp4', 'mov', 'f4v', '3gp', '3g2', 'aac', 'm4a', 'ogv', 'webm'
);
$valid_image_extensions = array(
  'gif', 'jpg', 'jpeg', 'bmp', 'png'
);
$all_valid_extentions = array_merge($valid_image_extensions,$valid_media_extensions);


// ############################ IM_CheckExtention #############################

function IM_CheckExtention($imagename, $allowed_extentions, &$extension)
{
  $extension = '';
  if((strlen($imagename) < 5) || (substr($imagename,0, 1) == '.'))
  {
    return false;
  }

  $dotpos = strrpos($imagename, '.');
  if($dotpos !== false)
  {
    $extension = strtolower(substr($imagename, ($dotpos+1)));
  }

  if((substr($imagename,0, 1) == '.') || !@in_array(strtolower($extension), $allowed_extentions))
  {
    return false;
  }
  return true;

} //IM_CheckExtention


// ########################## DISPLAY FILE DETAILS ############################

function DisplayFileDetails($file, $rowcolor = '')
{
  global $folderpath, $sdurl, $all_valid_extentions,
         $valid_image_extensions, $valid_media_extensions;

  $output = '';

  // first create the full path to the file
  $filepath = $folderpath . $file;

  $div = '<div class="fileentry">';
  $class = 'fileentry-container';

  // either display an image or folder
  if(IM_CheckExtention($file, $all_valid_extentions, $ext))
  {
    if(IM_CheckExtention($file, $valid_media_extensions, $ext))
    {
      $ext = strtoupper($ext);
      $class = 'fileentry-container-media';
      $filename = htmlspecialchars(basename($file),ENT_COMPAT);
      /*
      $output = '<div class="fileentry-media"><a href="javascript:void(0);" onmousedown=\'InsertMedia("' . $sdurl . substr($filepath, strlen(ROOT_PATH)) .
        '","'.$ext.'");\' title="'.$filename.'"><img title="'.htmlspecialchars(basename($filepath),ENT_COMPAT).'" border="0" src="' . $sdurl .
        'includes/images/video.gif" alt="video" width="32" height="32" />'.$filename.'</a></div>';
      */
      $output = '';
    }
    else
    // lets make sure we're dealing with a real image
    // SD343: added is_file/is_readable for PHP 5.4.x notices; disable sys.log
    $GLOBALS['sd_ignore_watchdog'] = true;
    if( is_file($filepath) && is_readable($filepath) &&
        ($imagesize = @getimagesize($filepath)) )
    {
      // get the image information
      list($width, $height, $type, $attr) = $imagesize;

      // lets scale the image so it fits nicely into our window
      $scale = min(IM_THUMB_WIDTH/$width, IM_THUMB_HEIGHT/$height);
      $newwidth  = ($scale < 1) ? floor($scale * $width)  : $width;
      $newheight = ($scale < 1) ? floor($scale * $height) : $height;
      $filename = htmlspecialchars(basename($file),ENT_COMPAT);
      // now display the image
      $output =  $div.'<a href="javascript:void(0);" onmousedown=\'InsertImage("' .
                 $sdurl . substr($filepath, strlen(ROOT_PATH)) .
                 '",'.$width.','.$height.');\' title="'.$filename.'">'.
                 '<img alt="'.$filename.'" border="0" src="' . $filepath . '" width="' . $newwidth . '" height="' . $newheight . '" /></a></div>';
    }
    $GLOBALS['sd_ignore_watchdog'] = false;
  }
  else // the file is not an image
  {
    // display 'go back (..)'?
    if($file == '..')
    {
      // display up arrow
      if(substr($folderpath, -7) != 'images/')
      {
        $tmp_folderpath = str_replace('../', '*', dirname($folderpath)).'/';
        $output =  $div.'<a href="./imagemanager.php?folderpath=' .
                   $tmp_folderpath.
                   '"><img alt="Up" width="48" height="48" src="./img/folderup.gif" /></a>'.
                   '    <br /><a style="font-size: 10px;" href="./imagemanager.php?folderpath=' .
                   $tmp_folderpath.
                   '">Go up 1 folder</a></div>';
      }
    }
    else
    if(is_dir($filepath))
    {
      $tmp_folderpath = str_replace('../', '*', $filepath);
      $output =  $div.'<a href="./imagemanager.php?folderpath='.$tmp_folderpath.
                 '/"><img alt="Change folder" border="0" width="48" height="48" src="./img/folder.gif" /></a>'.
                 '    <br /><a style="font-size: 10px;" href="./imagemanager.php?folderpath='.
                 $tmp_folderpath.'/">'.$file.'</a></div>';
    }
  }

  if(strlen($output))
  {
    $output = '
    <div class="'.$class.'">'.$output.'</div>';
  }

  return $output;
} //DisplayFileDetails


// ############################### UPLOAD IMAGE ################################

function UploadImage()
{
  global $folderpath, $valid_image_extensions, $valid_media_extensions;

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
    if(IM_CheckExtention($image['name'], $valid_image_extensions, $ext))
    {
      $known_photo_types = array(
      'image/pjpeg' => 'jpg',
      'image/jpeg'  => 'jpg',
      'image/gif'   => 'gif',
      'image/bmp'   => 'bmp',
      'image/x-png' => 'png',
      'image/png'   => 'png');

      $is_image = true;
      // lets make sure the extension is correct
      if(!array_key_exists($image['type'],$known_photo_types))
      {
        $errors[] = AdminPhrase('media_upload_error_invalid_image');
      }
    }
    else
    if(!IM_CheckExtention($image['name'], $valid_media_extensions, $ext))
    {
      $errors[] = AdminPhrase('media_upload_error_invalid_image');
    }
  }

  if(empty($errors))
  {
    // Before accessing it, we need to ...
    // a) secure the filename to avoid apache bug in executing the image!
    // b) move the uploaded temp file to our own directory to allow it
    //    to run on secured servers (safe mode, open_...)
    $imagename = $image['name'];
    if( SD_Image_Helper::FilterImagename($imagename) &&
        SD_Image_Helper::FilterImagename($image['tmp_name']) &&
        !is_executable($image['tmp_name']) &&
        is_uploaded_file($image['tmp_name']) )
    {
      $imagename = basename($image['name']);
      $imagesdir .= $imagename;
      if(@move_uploaded_file($image['tmp_name'], $imagesdir))
      {
        @chmod($imagesdir, 0644);
        // final attempt to make sure it's a real image:
        // SD343: changed to is_file and use getimagesize(); disable sys.log
        $GLOBALS['sd_ignore_watchdog'] = true;
        if(!$is_image || !is_file($imagesdir) || !@getimagesize($imagesdir))
        {
          $errors[] = AdminPhrase('media_upload_error_invalid_image');
          if(is_file($imagesdir)) @unlink($imagesdir);
        }
        $GLOBALS['sd_ignore_watchdog'] = false;
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
    DisplayImages($imagename);
  }

} //UploadImage


// ############################### DISPLAY IMAGES ##############################

function DisplayImages($newimage = '')
{
  global $folderpath, $sdurl, $all_valid_extentions;

  StartSection(AdminPhrase('media_upload_image'));
  if(strlen($newimage))
  {
    echo '
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
      <td class="td1"></td>
    </tr>
    <tr>
      <td class="td2">
        File successfully uploaded! <a style="text-decoration: underline;" href="./imagemanager.php?action=displayimages">
        Upload new Image/Media?</a>
        <br /><br />';

    DisplayFileDetails($newimage);

    echo '
      </td>
    </tr>
    </table>';
  }
  else
  {
    $tmp_folderpath = str_replace('../', '*', ROOT_PATH.'images/');
    echo '
    <table border="0" cellpadding="0" cellspacing="0" summary="layout" width="100%">
    <tr>
      <td class="td2"><strong>'.AdminPhrase('media_upload_image_to_folder').'</strong></td>
      <td align="left" class="td3">
        <form enctype="multipart/form-data" method="post" action="./imagemanager.php" id="upload_form">
        <input type="hidden" name="action" value="uploadimage" />
        <input type="hidden" name="folderpath" value="'.
        str_replace('../', '*', $folderpath).'" />
        <input name="image" type="file" size="70" /><br />
        <input type="submit" value="'.AdminPhrase('media_upload_image').'" />
        </form>
        <a href="#" onclick=\'javascript:window.location="./imagemanager.php?folderpath='.urlencode($tmp_folderpath).'&amp;action=displayimages"\'>[Site Images]</a> &nbsp;
        <a href="#" onclick=\'javascript:window.location="./imagemanager.php?folderpath='.urlencode($tmp_folderpath.'articlethumbs/').'&amp;action=displayimages"\'>[Articles Thumbs]</a> &nbsp;
        <a href="#" onclick=\'javascript:window.location="./imagemanager.php?folderpath='.urlencode($tmp_folderpath.'featuredpics/').'&amp;action=displayimages"\'>[Articles Pictures]</a> &nbsp;
      </td>
    </tr>
    </table>';
        #<a href="#" onclick=\'javascript:window.location="./imagemanager.php?folderpath='.urlencode(ROOT_PATH.'plugins/p17_image_gallery/images/').'&amp;action=displayimages"\'>[Image Gallery]</a> &nbsp;
        #<a href="#" onclick=\'javascript:window.location="./imagemanager.php?folderpath='.urlencode(ROOT_PATH.'plugins/media_gallery/images/').'&amp;action=displayimages"\'>[Media Gallery]</a>
  }
  EndSection();

  clearstatcache();
  $files = $images = $folders = array();
  $GLOBALS['sd_ignore_watchdog'] = true;
  if(false !== ($handle = @opendir($folderpath)))
  {
    // This is the correct way to loop over the directory
    while(false !== ($file = readdir($handle)))
    {
      if(($file != '.') && ($file != 'avatars') && ($file != 'profiles'))
      {
        if(($file == '..') || is_dir($folderpath . $file))
        {
          $folders[] = $file;
        }
        else
        if(IM_CheckExtention($file, $all_valid_extentions, $ext))
        {
          $images[] = $file;
        }
      }
    }
  }
  // now sort both images and folders
  @natcasesort($images);
  @natcasesort($folders);
  $GLOBALS['sd_ignore_watchdog'] = false;

  // combine the two
  $files = @array_merge($folders, $images);

  $columncount = 0;

  StartSection('Images');
  echo '
  <table border="0" cellpadding="0" cellspacing="0" summary="images" width="100%">
  <tr>
    <td class="td1">Folder Path: ' . $sdurl . substr($folderpath, strlen(ROOT_PATH)) . '</td>
  </tr>
  <tr>
    <td class="td2" align="left" style="text-align: left">';

  if(!count($files))
  {
    echo DisplayFileDetails('..');
  }
  else
  for($i = 0; $i < count($files); $i++)
  {
    echo DisplayFileDetails($files[$i]);
  }
  echo '
    </td>
  </tr>
  </table>';
  EndSection();

  if($handle)
  {
    closedir($handle);
  }
}

// ############################## DISPLAY HEADER ###############################
#<link rel="stylesheet" type="text/css" href="' . ROOT_PATH .ADMIN_PATH. '/styles/' . ADMIN_STYLE_FOLDER_NAME . '/css/admin.css.php" />
#<link rel="stylesheet" type="text/css" href="' . ROOT_PATH .ADMIN_PATH. '/tiny_mce/themes/advanced/skins/default/ui.css" />
#<link rel="stylesheet" type="text/css" href="' . ROOT_PATH .ADMIN_PATH. '/tiny_mce/themes/advanced/skins/default/window.css" />
#

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<title>' . PRGM_NAME . ' - Admin Panel</title>
<link rel="stylesheet" type="text/css" href="' . ROOT_PATH .ADMIN_PATH. '/styles/' . ADMIN_STYLE_FOLDER_NAME . '/css/admin.css.php" />
<style type="text/css">
#content { padding: 0; margin: 0; max-width: 850px !important; min-width: 200px !important; }
.fileentry-container,
.fileentry-container-media {
  background-color: #FFF;
  border: 1px solid #c0c0c0;
  display: inline;
  float: left;
  margin: 10px;
  height: '.(IM_THUMB_HEIGHT+10).'px;
  text-align: center;
  width: '.(IM_THUMB_WIDTH+10).'px;
  overflow: hidden;
}
.fileentry, .fileentry-media {
  border: none;
  display: block;
  border: none;
  padding: 4px;
  min-height: '.IM_THUMB_HEIGHT.'px;
  text-align: center;
}
.fileentry-container:hover {
  border: 1px solid #0000FF;
}
.fileentry-container-media:hover {
  border: 1px solid #00FF00;
}
</style>
<script type="text/javascript" src="../../tiny_mce_popup.js"></script>
<script type="text/javascript">
  sdurl = "'.SITE_URL.'";
  function InsertImage(imagepath,img_width,img_height) {
    tinyMCE.execCommand("mceInsertContent", false, \'<img src="\'+imagepath+\'" width="\'+img_width+\'" height="\'+img_height+\'" style="border: none" />\');
    tinyMCEPopup.close();
  }';
echo '
</script>
</head>
<body>
<div id="content">';
#style="width:100%; background:#F0F0EE; padding: 14px;"

/*
echo '
  function InsertMedia(imagepath, ext) {
    if(window.opener && (typeof(ext) !== "undefined")) {
      var media_content =
      "\r\n    <a class=\"media {width:480, height:300}\" href=\""+escape(imagepath)+"\">[MEDIA \""+ext+"\"]</a>";
      tinyMCE.execCommand("mceInsertContent", false, media_content);
    }
    tinyMCEPopup.close();
  }';
echo '
  function InsertMedia(imagepath) {
    if(window.opener) {
      var media_content = "\r\n"+
      "  <div id=\"media-container\"><"+"/div> \r\n"+
      "  <"+"script type=\"text/javascript\">// <"+"![CDATA[ \r\n"+
      "  if(typeof(jwplayer) !== \"undefined\") { \r\n"+
      "  jwplayer(\"media-container\").setup({ \r\n"+
      "    file: \""+imagepath+"\", \r\n"+
      "    height: 270, \r\n"+
      "    width: 480, \r\n"+
      "    volume: 80, \r\n"+
      "    wmode: \"transparent\", \r\n"+
      "    events: { onReady: function() { this.play(); } }, \r\n"+
      "    players: [ \r\n"+
      "      { type: \"html5\" }, \r\n"+
      "      { type: \"flash\", src: \""+sdurl+"includes/javascript/jwplayer/player.swf\" } \r\n"+
      "    ] \r\n"+
      "  }); \r\n"+
      "  } \r\n"+
      "  // ]]> \r\n"+
      "  <"+"/script>";

      tinyMCE.execCommand("mceInsertContent", false, media_content);
    }
    tinyMCEPopup.close();
  }';
*/
/*
// Code template from here: http://sandbox.thewikies.com/vfe-generator/
//      "    <source src=\""+imagepath+"\".webm" type="video/webm" /> \r\n"+
//      "    <source src=\""+imagepath+"\".ogv" type="video/ogg" /> \r\n"+
echo '
  function InsertMedia(imagepath) {
    if(window.opener) {
      var media_content = "\n"+
      "  <div class=\"video-js-box\">\n"+
      "  <video class=\"video-js\" controls preload  poster=\"'.SITE_URL.'includes/images/video.gif\" width=\"640\" height=\"360\">\n"+
      "    <source src=\""+imagepath+"\" type=\"video/mp4\" />\n"+
      "    <object class=\"vjs-flash-fallback\" type=\"application/x-shockwave-flash\" data=\"http://flashfox.googlecode.com/svn/trunk/flashfox.swf\" width=\"640\" height=\"360\">\n"+
      "      <param name=\"movie\" value=\"http://flashfox.googlecode.com/svn/trunk/flashfox.swf\" />\n"+
      "      <param name=\"allowFullScreen\" value=\"true\" />\n"+
      "      <param name=\"wmode\" value=\"transparent\" />\n"+
      "      <param name=\"flashVars\" value=\"controls=true&amp;&amp;src=\"+encodeuricomponent(imagepath)+\" />\n"+
      "      <img alt=\"Video\" src=\"'.SITE_URL.'includes/images/video.gif\" width=\"640\" height=\"360\" title=\"No video playback capabilities, please download the video below.\" />\n"+
      "    </object>\n"+
      "  </video>\n"+
      "  <p class=\"vjs-no-video\"><strong>Download video:</strong> <a href=\"+imagepath+\">MP4 format</a></p>\n"+
      "  </div>\n";
      tinyMCE.execCommand("mceInsertContent", false, media_content);
    }
    tinyMCEPopup.close();
  }';
*/

// ############################## SELECT FUNCTION ##############################

switch($action)
{
  case 'uploadimage':
    UploadImage();
  break;

  case 'displayimages':
    DisplayImages();
  break;

  default:
    echo 'Invalid page call!';
}

// ############################## DISPLAY FOOTER ###############################

echo '</div>
</body>
</html>';
