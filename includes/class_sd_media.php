<?php
defined('SD_MEDIA_ERR_UNSUPPORTED')   or define('SD_MEDIA_ERR_UNSUPPORTED',   -1);
defined('SD_MEDIA_ERR_WRONG_EXT')     or define('SD_MEDIA_ERR_WRONG_EXT',     -2);
defined('SD_MEDIA_ERR_FILENOTFOUND')  or define('SD_MEDIA_ERR_FILENOTFOUND',  -3);
defined('SD_MEDIA_ERR_THUMB_FAILED')  or define('SD_MEDIA_ERR_THUMB_FAILED',  -4);
defined('SD_MEDIA_ERR_FILESIZE')      or define('SD_MEDIA_ERR_FILESIZE',      -5);
defined('SD_MEDIA_ERR_WIDTH')         or define('SD_MEDIA_ERR_WIDTH',         -6);
defined('SD_MEDIA_ERR_HEIGHT')        or define('SD_MEDIA_ERR_HEIGHT',        -7);
defined('SD_MEDIA_ERR_UPLOAD')        or define('SD_MEDIA_ERR_UPLOAD',        -8);
defined('SD_MEDIA_ERR_INVALID')       or define('SD_MEDIA_ERR_INVALID',       -9);
defined('SD_MEDIA_ERR_NOFOLDER')      or define('SD_MEDIA_ERR_NOFOLDER',     -10);
defined('SD_MEDIA_ERR_WRITEFOLDER')   or define('SD_MEDIA_ERR_WRITEFOLDER',  -11);
defined('SD_MEDIA_ERR_GD_MISSING')    or define('SD_MEDIA_ERR_GD_MISSING',   -12);
defined('SD_MEDIA_ERR_FILTER_FAILED') or define('SD_MEDIA_ERR_FILTER_FAILED',-13);
defined('SD_MEDIA_MAX_DIM')           or define('SD_MEDIA_MAX_DIM', 8192);

// Note: required defines: ROOT_PATH, SD_INCLUDE_PATH etc.

// ############################################################################
// SD_Media_Base CLASS
// ############################################################################
if(!class_exists('SD_Media_Base'))
{
class SD_Media_Base
{
  //SD360: now public
  public static $known_image_types = array(
    'bmp'   => 'image/bmp',
    'gif'   => 'image/gif',
    'jpe'   => 'image/jpeg',
    'jpg'   => 'image/pjpeg', //IE8
    'jpg'   => 'image/jpeg', // MUST BE AFTER pjpeg!
    'jpeg'  => 'image/jpeg',
    'pjpeg' => 'image/jpeg',
    'png'   => 'image/png');

  //SD360: additional static helpers: $known_type_ext, $valid_image_extensions...
  public static $known_type_ext = array(
    'image/bmp'   => 'bmp',
    'image/gif'   => 'gif',
    'image/jpe'   => 'jpg',
    'image/jpg'   => 'jpg',
    'image/jpeg'  => 'jpg',
    'image/pjpeg' => 'jpg', //IE8
    'image/png'   => 'png',
    'image/wbmp'  => 'bmp',
    'image/x-png' => 'png');

  public static $image_type_extension = array (
    ## see: http://php.net/manual/en/function.image-type-to-extension.php
    ## These integer values correspond to the the respective IMAGETYPE constants
    IMAGETYPE_GIF     => 'gif',   ##  1 = GIF
    IMAGETYPE_JPEG    => 'jpg',   ##  2 = JPG
    IMAGETYPE_PNG     => 'png',   ##  3 = PNG
    IMAGETYPE_SWF     => 'swf',   ##  4 = SWF
    IMAGETYPE_PSD     => 'psd',   ##  5 = PSD
    IMAGETYPE_BMP     => 'bmp',   ##  6 = BMP
    IMAGETYPE_TIFF_II => 'tiff',  ##  7 = TIFF  (intel byte order)
    IMAGETYPE_TIFF_MM => 'tiff',  ##  8 = TIFF  (motorola byte order)
    IMAGETYPE_JPC     => 'jpc',   ##  9 = JPC
    IMAGETYPE_JP2     => 'jp2',   ## 10 = JP2
    IMAGETYPE_JPX     => 'jpf',   ## 11 = JPX   Yes! jpf extension is correct for JPX image type
    IMAGETYPE_JB2     => 'jb2',   ## 12 = JB2
    IMAGETYPE_SWC     => 'swc',   ## 13 = SWC
    IMAGETYPE_IFF     => 'aiff',  ## 14 = IFF
    IMAGETYPE_WBMP    => 'wbmp',  ## 15 = WBMP
    IMAGETYPE_XBM     => 'xbm'    ## 16 = XBM
  );

  public static $valid_image_extensions = array('bmp','gif','jpe','jpeg','jpg','png','svg','tif');
  public static $valid_media_extensions = array('avi','f4v','flv','m4a','mp3','mp4','m4a','m4v','mov','mpg','mpeg','ogg','oga','ogv','swf','webm','wmv','wma','3gp','3g2','aac','rm','midi','au','aif','gsm');

  private static $ImageFilter = null;
  public static function ImageFilterExists()
  {
    if(!isset(self::$ImageFilter))
    {
      self::$ImageFilter = function_exists('imagefilter');
    }
    return self::$ImageFilter;
  }

  //SD343: Media URL support (see: media_embed.php)
  // Note: indexes/order must not be changed!
  public static $known_media_sites = array(
    'youtube'     => 1,
    'vimeo'       => 2,
    'google'      => 3,
    'facebook'    => 4,  #untested
    'dailymotion' => 5,
    'metacafe'    => 6,
    #'revver'      => 7, #error
    #'fivemin'     => 8, #now "aol"
    'blip'        => 9,
    'viddler'     => 10,
    'funnyordie'  => 11,
    'twitpic'     => 12,
    'yfrog'       => 13,
    'break'       => 14,
    'qik'         => 15,
    'hulu'        => 16, #untested
    'revision'    => 17,
    //SD362: newly allowed
    'liveleak'    => 18,
    'flickr'      => 19,
    'aol'         => 20,
    'clikthrough' => 21,
    'myspace'     => 22,
    'slideshare'  => 23,
    'pixelbark'   => 24,
    'soundcloud'  => 25,
    'screenr'     => 26,
    'sevenload'   => 27,
    'videojug'    => 28,
    'dotsub'      => 29,
  );

  public static $m_embed = false; //SD362 media_embed object
  public static $defaultMediaImg = 'includes/images/video.png'; //SD362

  public static function getImageExtentions(){ return self::$valid_image_extensions; }
  public static function getImageTypes(){ return self::$known_image_types; }

  private static $_isWin = null;
  private static $_phpThumb_Installed = null;

  // ############################# getExtention ###############################

  public static function getExtention($file_name, $allowed_extentions=null)
  {
    $extension = false;
    if(strlen($file_name) < 5)
    {
      return false;
    }

    $dotpos = strrpos($file_name, '.');
    if($dotpos !== false)
    {
      $extension = strtolower(substr($file_name, ($dotpos+1)));
    }
    $allowed_extentions = empty($allowed_extentions)?array_unique(array_keys(self::$known_image_types)):$allowed_extentions;
    if(empty($extension) || !@in_array(strtolower($extension), $allowed_extentions))
    {
      return false;
    }

    return $extension;

  } //getExtention

  // ##########################################################################

  public static function getErrorPhrase($errorcode) //SD362
  {
    global $sdlanguage;

    if(empty($errorcode) || !is_numeric($errorcode)) return '';
    $errorcode = Is_Valid_Number($errorcode,
                                 SD_MEDIA_ERR_UNSUPPORTED,
                                 SD_MEDIA_ERR_FILTER_FAILED,
                                 SD_MEDIA_ERR_UNSUPPORTED);
    if(isset($sdlanguage['err_sd_media'.$errorcode]))
    {
      return $sdlanguage['err_sd_media'.$errorcode];
    }
    return 'ERROR';
  }

  // ##########################################################################

  public static function is_valid_path($path)
  {
    return (preg_match('#\^|\\\|\/|\?|\*|"|\'|\<|\>|\:|\|#', $path) ? false : true);
  } //is_valid_path

  // ##########################################################################

  public static function IsWin() //SD360
  {
    if(self::$_isWin===null)
    {
      self::$_isWin = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN');
    }
    return self::$_isWin;
  }

  // ##########################################################################

  public static function IsPhpThumb_Installed() //SD360
  {
    if(self::$_phpThumb_Installed===null)
    {
      self::$_phpThumb_Installed = file_exists(SD_INCLUDE_PATH.'phpthumb/phpthumb.class.php');
    }

    return self::$_phpThumb_Installed;
  }

  // #############################################################################

  public static function isMediaUrlValid($media_url='', $outputState=false) //SD362
  {
    // check if given url is a supported media url and return the vendor name,
    // otherwise return false
    $result = false;
    self::InitEmbedMedia();
    if(self::$m_embed && is_object(self::$m_embed))
    {
      $org = sd_unhtmlspecialchars($media_url);
      if(DetectSQLInjection($org) || DetectXSSinjection($org) ||
         (strip_tags($org) != $media_url) ||
         @preg_match("#<script|<html|<head|<\?php|<title|<body|<pre|<table|<a(\s*)href|<img|<plaintext|<cross\-domain\-policy#si", $org))
      {
        if($outputState) echo 'media_error';
      }
      else
      {
        if($result = self::$m_embed->set_site($org))
        {
          $result = self::$m_embed->get_vendor();
        }
      }
    }
    if($outputState) echo ($result!==false ? 'media_ok' : 'media_error');
    return $result;

  } //isMediaUrlValid


  // ##########################################################################


  public static function ImageSecurityCheck($docheck, $folder='', $imagename='')
  {
    if(empty($docheck) || !isset($folder) || !isset($imagename) || !strlen($imagename)) return true;

    $olddog = $GLOBALS['sd_ignore_watchdog'];
    $GLOBALS['sd_ignore_watchdog'] = true;
    if(substr($imagename,0,1) == '.' || substr($imagename,0,1) == '/')
    {
      $error = 1;
    }
    else
    if($file_hdl = @fopen($folder . $imagename, 'rb'))
    {
      $file_data = @fread($file_hdl, 2048);
      @fclose($file_hdl);
      if(!$file_data)
      {
        $error = 1;
      }
    }
    else $error = 1;
    if(empty($error))
    {
      # file content scan
      if(preg_match("#eval\s?\(|gzinflate|base64_decode|str_rot13|<\?php|<script|<html|<head|<title|<body|<pre|<table|<a\s+href|<img|<plaintext|<cross\-domain\-policy#si", $file_data))
      {
        $error = 1;
      }
      unset($file_data);
    }
    $image_ext = explode('.', $imagename);
    $image_ext = (is_array($image_ext) && count($image_ext)) ? strtolower($image_ext[count($image_ext)-1]) : null;
    if(is_file($folder . $imagename))
    {
      //SD370: security check on jpg exif data (if "exif" is installed/enabled)
      if( empty($error) && function_exists('exif_read_data') &&
          preg_match('/jpe?g/i', $image_ext) &&
          (false !== ($exif = @exif_read_data($folder . $imagename))) )
      {
        foreach($exif as $key => $value)
        {
          $tmp = ucwords(strtolower($value));
          if (strpos($tmp,"/e")==true)
          {
            $error = 1;
            break;
          }
        }
      }
      $flags = @getimagesize($folder . $imagename);
    }
    $error = !empty($error) ||
             ( !is_array($flags) || !count($flags) || !isset($flags[2]) ||
               (isset($flags[2]) && ($flags[2]==1) &&
                preg_match('/jpe?g/i', $image_ext)) );
    $GLOBALS['sd_ignore_watchdog'] = $olddog;

    if($error)
    {
      // If security checks failed, delete previously inserted row and return
      @unlink($folder . $imagename);
      return false;
    }

    return true;

  } //ImageSecurityCheck

  public static function requestMem($mem='128M') #SD362
  {
    // Try to raise memory, but only once:
    if(defined('SD_MEM_REQUESTED') || !function_exists('ini_set')) return;
    define('SD_MEM_REQUESTED', 1);
    if(!sd_safe_mode())
    {
      $mem_limit = (int)ini_get('memory_limit');
      if(is_numeric($mem_limit) && (intval($mem_limit) < intval($mem)))
      {
        @ini_set('memory_limit', $mem);
      }
    }
  } //requestMem

  // ##########################################################################
  //                  SPECIAL EMBEDDABLE MEDIA FUNCTIONS
  // ##########################################################################

  public static function InitEmbedMedia() //SD362
  {
    //SD362: create internal object once
    if(!class_exists('media_embed'))
    {
      @include_once(SD_CLASS_PATH.'media_embed.php');
    }
    if((self::$m_embed !== false) && is_object(self::$m_embed ) &&
       (self::$m_embed instanceof media_embed))
    {
      return true;
    }
    self::$m_embed = false;
    if(!class_exists('media_embed')) return false;
    self::$m_embed = new media_embed();
    return true;
  } //InitEmbedMedia

  public static function GetMediaType($media_url='')
  {
    self::InitEmbedMedia();
    if(empty($media_url) || (strlen($media_url) < 5) || !is_object(self::$m_embed)) return 0;

    if(self::$m_embed->set_site($media_url))
    {
      $site = strtolower(self::$m_embed->get_vendor());
      if(!empty($site) && array_key_exists($site, self::$known_media_sites))
      {
        return self::$known_media_sites[$site];
      }
    }
    return 0;
  } //GetMediaType


  public static function getMediaImgLink($m_type, $media_url) //SD343
  {
    self::InitEmbedMedia();
    $res = SITE_URL.self::$defaultMediaImg;

    $mt = empty($m_type)?0:(int)$m_type;
    if(empty($media_url) || (strlen($media_url) < 5) || !is_object(self::$m_embed)) return $res;

    if($mt && self::$m_embed->set_site($media_url))
    {
      $site = strtolower(self::$m_embed->get_vendor());
      if(($site != '') && isset(self::$known_media_sites[$site]))
      {
        if($embed = self::$m_embed->get_thumb('medium'))
        {
          return $embed;
        }
      }
    }

    return $res;

  } //getMediaImgLink


  public static function getMediaEmbedLink($m_type, $media_url, $width, $height) //SD343
  {
    self::InitEmbedMedia();
    $res = SITE_URL.self::$defaultMediaImg;

    $mt = empty($m_type)?0:(int)$m_type;
    if(empty($media_url) || (strlen($media_url) < 5) || !is_object(self::$m_embed)) return $res;

    if($mt && is_object(self::$m_embed) && self::$m_embed->set_site($media_url))
    {
      $site = self::$m_embed->get_vendor();
      if(($site != '') && isset(self::$known_media_sites[$site]))
      {
        if($embed = self::$m_embed->get_embed($width, $height))
        {
          $res = $embed;
        }
        else
        if($embed = self::$m_embed->get_iframe())
        {
          $res = $embed;
        }
      }
    }

    return $res;

  } //getMediaEmbedLink


  public static function getMediaPreviewImgTag($m_type, $m_url, $width=400, $height=226, $doDefault=true) //SD343
  {
    self::InitEmbedMedia();
    //SD362: default to dummy video image; set defaults for width/height params
    // sizes in 16:9 (w:h) like 600/338, 400/226
    if($attr = (empty($width)?'':'max-width: '.$width.'px;').
                (empty($height)?'':'max-height: '.$height.'px;'))
    {
      $attr = ' style="'.$attr.'" ';
    }
    $attr = ' alt=" "'.$attr;

    $res = '';
    if(!empty($doDefault))
    {
      $res = '<img src="'.SITE_URL.self::$defaultMediaImg.'"'.$attr.'/>';
    }
    if(empty($m_type) || empty($m_url) || (strlen($m_url)<5) || !is_object(self::$m_embed) || !self::$m_embed->set_site($m_url))
    {
      return $res;
    }

    $site = self::$m_embed->get_vendor();
    if(($site != '') && array_key_exists($site, self::$known_media_sites))
    {
      if($src = self::$m_embed->get_thumb('medium'))
      {
        return '<img src="'.$src.'"'.$attr.'/>';
      }
      if($src = self::$m_embed->get_thumb('small'))
      {
        return '<img src="'.$src.'"'.$attr.'/>';
      }
    }

    return $res;

  } //getMediaPreviewImgTag


  public static function getMediaPreview($m_type, $m_url, $m_title, $width=425, $height=335, $Embed=true, $errText=true) //SD343
  {
    self::InitEmbedMedia();
    //SD362: default to dummy video image; set defaults for width/height params
    // sizes in 16:9 (w:h) like 600/338, 400/226
    $attr = ' alt=" "';
    if($attr = (empty($width)?'':'max-width: '.$width.'px;').
                (empty($height)?'':'max-height: '.$height.'px;'))
    {
      $attr .= 'style="'.$attr.'" ';
    }

    $res = '<img src="'.SITE_URL.self::$defaultMediaImg.'"'.$attr.'/>';
    if(empty($m_type) || empty($m_url) || !is_object(self::$m_embed) || !self::$m_embed->set_site($m_url))
    {
      return $res;
    }

    self::$m_embed->set_sizes($width,$height);
    $site = self::$m_embed->get_vendor();
    if(($site != '') && isset(self::$known_media_sites[$site]))
    {
      if(!empty($Embed))
      {
        if($res = self::$m_embed->get_embed($width,$height)) return $res;
        if($res = self::$m_embed->get_iframe($width,$height)) return $res;
      }

      if($embed = self::$m_embed->get_thumb('medium'))
      {
        $res = '<a target="_blank" title="'.addslashes($m_title).'" href="'.self::$m_embed->get_url().'"><img src="'.$embed.'"'.$attr.'/></a>';
      }
      /*
      # commented out for better performance
      else
      if($embed = self::$m_embed->get_thumb('small'))
      {
        $res = '<a target="_blank" title="'.addslashes($m_title).'" href="'.self::$m_embed->get_url().'"><img src="'.$embed.'"'.$attr.'/></a>';
      }
      */
      else
      {
        #if(!empty($errText)) $res = $this->language['no_media_preview_available'];
        $res = '<a target="_blank" title="'.addslashes($m_title).'" href="'.self::$m_embed->get_url().'">'.
               '<img src="'.SITE_URL.self::$defaultMediaImg.'"'.$attr.'/></a>';
      }
    }

    return $res;

  } //getMediaPreview


} // END OF SD_Media_Base
} // DO NOT REMOVE


// ############################################################################
// SD_Image_Helper CLASS //SD360
// Defines *static* functions for easy use of e.g. thumbnailing
// ############################################################################

if(!class_exists('SD_Image_Helper'))
{
class SD_Image_Helper
{
  public static $ImagePath = '';

  public static function FilterImagename(&$imagename)
  {
    $imagename = str_replace(array("'",'"','&#039;','&#39;'), '_', $imagename);
    $org = $imagename;
    if(strlen($imagename))
    {
      $imagename = strip_tags($imagename);
      //SD343: added checks
      if(preg_match("#\.$|\.pdf|\.inc|\.php([2-6]?)|\.bat|\.txt|\.(p?)html?|\.pl|\.sh|\.js|\.cgi|\.cmd|\.com|\.exe|\.vb|<script|<html|<head|<\?php|<title|<body|<pre|<table|<a(\s*)href|<img|<plaintext|<cross\-domain\-policy#si", $imagename))
        $imagename = '';
      else
        $imagename = preg_replace("/%0A|\\r|%0D|\\n|%00|\\0|%09|\\t|%01|%02|%03|%04|%05|%06|%07|%08|%09|%0B|%0C|%0E|%0F|%10|%11|%12|%13/im", '', $imagename);//SD343
    }
    return ($org == $imagename);
  } //FilterImagename

  public static function GetExtByType($imagetype=0,$withDot=true)
  {
    if(empty($imagetype)) return '';
    if(isset(SD_Media_Base::$image_type_extension[$imagetype]))
    {
      return (empty($withDot)?'':'.').
             SD_Media_Base::$image_type_extension[$imagetype];
    }
    return '';
  }

  /**
  * Uploads (processing $_FILES buffer) an image identified by "$image_element", incl. security checks.
  * <b>Target path MUST NOT have ROOT_PATH</b> at the beginnging because it is used for different functions.
  * If successful, from that image then a thumbnail is created, scaled down
  * proportionally, optionally squared or with kept aspect ratio.
  * The $keepOriginal is false, the originally uploaded image is removed.
  * Uploaded image is accepted with up to 8192x8192 pixels (width/height).
  *
  * Globals: $sdlanguage
  * Includes: class_sd_media.php (class SD_Image)
  *
  * @param string $image_element  Name of the form element (of type file) of the uploaded image
  * @param string $keepOriginal   If true, the original image file will be kept, else deleted.
  * @param string $target_path    Path (no filename, NO ROOT_PATH!) for storing image/thumb, ex: "images/"
  * @param string $imagename      Filename without(!) extention for uploaded image file, ex: "test". Extention is taken from uploaded file.
  * @param string $thumbnailname  Filename for created thumbnail file, ex: "test_thumb.jpg"
  * @param int $maxwidth          Max. thumb width in pixels (default: 100)
  * @param int $maxheight         Max. thumb height in pixels (default: 100)
  * @param int $maxuploadsize     Max. uploaded image size in bytes (default: 8MB; min. 1KB; max. 24MB)
  * @param bool $squareoff        Create squared thumbnail (width equals height, default: false)
  * @param bool $keepratio        Keep aspect ratio to avoid deformation/stretching (default: true)
  * @param bool $isUploaded       Default true; false if checking for local file "$imagename" (ignoring "$image_element")
  * @param SD_Image $img_obj      If no success, then null (if not passed in); else (new or passed) SD_Image pointing to thumbnail image
  * @return bool                  returns true when success; otherwise error text
  */
  public static function UploadImageAndCreateThumbnail(
         $image_element, $keepOriginal, $target_path, $imagename, $thumbnailfile,
         $maxwidth=100, $maxheight=100, $maxuploadsize=8388608,
         $squareoff=false, $keepratio=true, $isUploaded=true, & $img_obj)
  {
    $img_obj_created = false;
    if(empty($img_obj) || !($img_obj instanceof SD_Image))
    {
      $img_obj = new SD_Image();
      $img_obj_created = true;
    }

    $errormsg = '';
    $image_uploaded = false;

    // Sanity checks on params
    $maxwidth  = !isset($maxwidth)  ? 100 : Is_Valid_Number($maxwidth,100,20,SD_MEDIA_MAX_DIM);
    $maxheight = !isset($maxheight) ? 100 : Is_Valid_Number($maxheight,100,20,SD_MEDIA_MAX_DIM);
    $maxuploadsize = Is_Valid_Number($maxuploadsize, 8388608, 1024, 25165824);
    $isUploaded = !empty($isUploaded);

    if(!$isUploaded)
    {
      if($res = $img_obj->setImageFile(ROOT_PATH.$target_path.$imagename))
      {
        if(($img_obj->getErrorCode() == SD_MEDIA_ERR_WRONG_EXT))
        {
          $res = SD_MEDIA_ERR_WRONG_EXT;
        }
        elseif($img_obj->getImageWidth() > SD_MEDIA_MAX_DIM)
        {
          $res = SD_MEDIA_ERR_WIDTH;
        }
        elseif($img_obj->getImageHeight() > SD_MEDIA_MAX_DIM)
        {
          $res = SD_MEDIA_ERR_HEIGHT;
        }
        elseif(($img_obj->getSize() == 0) || ($img_obj->getSize() > $maxuploadsize))
        {
          $res = SD_MEDIA_ERR_FILESIZE;
        }
        else
        if(!$img_obj->getGDImageType() ||
           !isset(SD_Media_Base::$known_type_ext[$img_obj->getMimeType()]))
        {
          $res = SD_MEDIA_ERR_UNSUPPORTED;
        }
        elseif(!SD_Image_Helper::FilterImagename($imagename) || !is_executable(ROOT_PATH.$target_path.$imagename))
        {
          $res = SD_MEDIA_ERR_UNSUPPORTED;
        }
      }
      else
      {
        $res = SD_MEDIA_ERR_UNSUPPORTED;
      }
      // If error is set, translate it
      if(($res !== true) && is_numeric($res))
      {
        $res = SD_Media_Base::getErrorPhrase($res);
      }
    }
    else
    {
      $res = $img_obj->UploadImage($image_element, $target_path, $imagename,
                                   SD_Media_Base::getImageExtentions(),
                                   $maxuploadsize, SD_MEDIA_MAX_DIM, SD_MEDIA_MAX_DIM);
    }

    if($res !== true)
    {
      if($img_obj_created) unset($img_obj);
      return $res;
    }

    if($img_obj->getImageValid())
    {
      global $sdlanguage;
      $ext = '.' . $img_obj->getImageExt();
      $imagename .= $ext;
      $thumbnailfile .= $ext;
      if(!SD_Media_Base::ImageSecurityCheck(true, ROOT_PATH.$target_path, $imagename))
      {
        $errormsg = $sdlanguage['upload_err_general'];
      }
      else
      {
        if($img_obj->CreateThumbnail(ROOT_PATH.$target_path.$thumbnailfile,
                                     $maxwidth, $maxheight,
                                     !empty($squareoff), !empty($keepratio)))
        {
          if(empty($keepOriginal)) @unlink(ROOT_PATH.$target_path.$imagename);
          $image_uploaded = $img_obj->setImageFile(ROOT_PATH.$target_path.$thumbnailfile);
        }
        else
        {
          $errormsg = $sdlanguage['common_thumbnail_failed'];
        }
      }
    }
    if(!$image_uploaded)
    {
      if($img_obj_created) unset($img_obj);
    }
    return empty($errormsg) ? $image_uploaded : $errormsg;

  } //UploadImageAndCreateThumbnail


  // ##########################################################################
  // GetThumbnailDataUri
  // ##########################################################################

  /**
  * Funtion to prepare Data URI, supports JPG, PNG and GIF.
  * Transparency *should* be retained.
  *
  * @param string $filename  Full path/filename of source image, ex: ./images/test_thumbnail.jpg
  * @param int $width        Max. width in pixels of resulting image
  * @param int $height       Max. height in pixels of resulting image
  * @return string           The data-string
  */
  public static function GetThumbnailDataUri($filename,$width=0,$height=0)
  {
    if(empty($filename) || !is_file($filename)) return false;

    $oldignore = $GLOBALS['sd_ignore_watchdog'];
    $GLOBALS['sd_ignore_watchdog'] = true;

    // Get Image Header Type
    if(false === ($image_info = @getimagesize($filename))) return false;
    $image_type = $image_info[2];



    // Check the Size is Specified
    if(($width > 0) && ($height > 0))
    {
      if( $image_type == IMAGETYPE_JPEG ) {
         $image = imagecreatefromjpeg($filename);
      } elseif( $image_type == IMAGETYPE_GIF ) {
         $image = imagecreatefromgif($filename);
      } elseif( $image_type == IMAGETYPE_PNG ) {
         $image = imagecreatefrompng($filename);
         imagealphablending($image, true);
      }

      if(false !== ($new_image = @imagecreatetruecolor($width, $height)))
      {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $width, $height,
                           imagesx($image),imagesy($image));
        $image = $new_image;
      }

      if(empty($image))
      {
        $GLOBALS['sd_ignore_watchdog'] = $oldignore;
        return false;
      }

      ob_start();
      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($image);
      } elseif( $image_type == IMAGETYPE_GIF ) {
         imagegif($image);
      } elseif( $image_type == IMAGETYPE_PNG ) {
         imagesavealpha($image, true); //SD362
         imagepng($image);
      }
      $content = ob_get_clean();
      imagedestroy($image); //SD362
    }
    else
    if(function_exists('file_get_contents'))
    {
      $content = @file_get_contents($filename,true);
    }
    else
    {
      $GLOBALS['sd_ignore_watchdog'] = $oldignore;
      return false;
    }

    $base64 = base64_encode($content);
    $GLOBALS['sd_ignore_watchdog'] = $oldignore;
	
    return "data:$image_type;base64,$base64";

  } //GetThumbnailDataUri


  // ############################################################################
  // GetSubFoldersArray
  // ############################################################################

  public static function GetSubFoldersArray($dirname, &$result)
  {
    if(empty($dirname)) return false;
    $dirname = str_replace('\\','/',$dirname);
    $dirname = rtrim($dirname,'/').'/';
    if((substr($dirname,0,1)=='/') || (strpos('//',$dirname)!==false))
    {
      return false;
    }

    if(!is_dir($dirname) ||
       !sd_check_foldername(basename($dirname)) ||
       (false === ($d = @dir($dirname))) )
    {
      return false;
    }
    while(false !== ($entry = $d->read()))
    {
      if((substr($entry,0,1) != '.') && is_dir($dirname.$entry))
      {
        self::GetSubFoldersArray($dirname.$entry.'/', $result);

        $entry = str_replace(self::$ImagePath, '', $dirname) . $entry;
        $result[] = $entry;
      }
    }
    $d->close();

    return true;

  } //GetSubFoldersArray


  // ############################################################################
  // GetSubFoldersForSelect
  // ############################################################################

  public static function GetSubFoldersForSelect($dirname, $selected='',
                                                $prefix='', $shortEntries=false)
  {
    $list = array();
    if(!self::GetSubFoldersArray($dirname, $list) ||
       empty($list) || !is_array($list))
    {
      return '';
    }
    $prefix = isset($prefix)?(string)$prefix:'';
    $selected = isset($selected)?(string)$selected:'';
    if(strlen($selected))
    {
      $selected = rtrim($selected,'/');
      $selected = utf8_decode($selected);
    }
    natcasesort($list);

    $result = '';
    foreach($list as $entry)
    {
      $isSelected = ($selected == $entry);
      $entry = utf8_encode($entry);
      $entryName = (empty($shortEntries)?$entry:str_replace($dirname, '', $entry));
      $result .= '
      <option value="' .htmlspecialchars($dirname.$entry) . '/"' .
      ($isSelected ? ' selected="selected"' : '') . ' >'.
      $prefix.$entry . '</option>';
    }
    return $result;

  } //GetSubFoldersForSelect


  // ##########################################################################
  // ScaleThumbnailSizes
  // ##########################################################################

  /**
  * Calculate new image dimensions with correct ratio to fit max width/height.
  * If $image is null but an image is loaded, that will be used, otherwise false.
  *
  * @param string $image          Full path/filename of source image; NULL to use internal image
  * @param int $maxwidth          Max. width in pixels to fit the image
  * @param int $maxheight         Max. height in pixels to fit the image
  * @param int $behaviour         default: 1
  * @return mixed FALSE if error; otherwise array with new width/height
  */
  public static function ScaleThumbnailSizes($image, $maxwidth = -1, $maxheight = -1,
                                             $behaviour = 1, $upscale = false)
  {
    if(empty($image) ||
       (is_string($image) && !is_file($image)) ||
       !is_resource($image) )
    return false;

    if(is_string($image))
    {
      list($width, $height) = @getimagesize($image);
      $maxwidth  = intval($maxwidth);
      $maxheight = intval($maxheight);
    }
    else
    {
      $maxwidth  = $width  = imagesx($image);
      $maxheight = $height = imagesy($image);
    }
    $maxwidth  = Is_Valid_Number($maxwidth,0,1,SD_MEDIA_MAX_DIM);
    $maxheight = Is_Valid_Number($maxheight,0,1,SD_MEDIA_MAX_DIM);

    if(empty($maxwidth) || empty($maxheight))
    {
      return false;
    }

    //Calculate the width:height ratio
    $owidth  = $width;
    $oheight = $height;
    $ratio = $height/$width;
    if($maxheight < 0 || $maxwidth < 0)
    {
      if($maxheight < 0 && $maxwidth < 0)
      {
        //Do nothing
      }
      elseif ($maxheight < 0)
      {
        $width = $maxwidth;
        $height = round($width*$ratio);
      }
      elseif ($maxwidth < 0)
      {
        $height = $maxheight;
        $width = round($height/$ratio);
      }
    }
    elseif ($ratio == 1)
    {
      //Same Height/Width
      if ($maxheight === $maxwidth)
      {
        $width = $maxwidth;
        $height = $maxheight;
      }
      else
      {
        $height = min($maxheight,$maxwidth);
        $width = min($maxheight,$maxwidth);
      }
    }
    else
    {
      $case1_width = $maxwidth;
      $case1_height = round($case1_width*$ratio);
      $case1_area = round($case1_width*$case1_height);

      $case2_height = $maxheight;
      $case2_width = round($case2_height/$ratio);
      $case2_area = round($case2_width*$case2_height);

      //Check if it is an ambiguous case
      if(($case1_width <= $maxwidth) && ($case1_height <= $maxheight) &&
         ($case2_width <= $maxwidth) && ($case2_height <= $maxheight))
      {
        //$behaviour Sometimes, 2 values are obtained. Set this to 1 to return the
        //one with the bigger pixel area or 2 to return the one with smaller pixel area.
        if($behaviour == 1)
        {
          if($case1_area >= $case2_area)
          {
            $height = $case1_height;
            $width = $case1_width;
          }
          else
          {
            $height = $case2_height;
            $width = $case2_width;
          }
        }
        else
        {
          if($case1_area <= $case2_area)
          {
            $height = $case1_height;
            $width = $case1_width;
          }
          else
          {
            $height = $case2_height;
            $width = $case2_width;
          }
        }
      }
      else
      {
        if ($case1_width <= $maxwidth && $case1_height <= $maxheight)
        {
          $height = $case1_height;
          $width = $case1_width;
        }
        else
        {
          $height = $case2_height;
          $width = $case2_width;
        }
      }
    }

    if(($height > $oheight) || ($width > $owidth))
    {
      $width  = $owidth;
      $height = $oheight;
    }
    $array = array((int)$width,(int)$height);
    return $array;

  } //ScaleThumbnailSizes


  // ##########################################################################
  // GetThumbnailTag
  // ##########################################################################

  public static function GetThumbnailTag($imagepath, $classname='', $title='', $alt='',
                                         $ScaleUp=false, $dataUri=false,
                                         $maxwidth=100, $maxheight=100, $html_id='')
  {
    global $sdurl, $mainsettings;
    $invalid_img = '<img src="'.$sdurl.'includes/images/delete.png" alt="" width="20" height="20" />';

    if(empty($imagepath) || !file_exists($imagepath))
    {
      return false;
    }

    $maxwidth  = empty($maxwidth)?100:Is_Valid_Number($maxwidth,100,10,2000);
    $maxheight = empty($maxheight)?100:Is_Valid_Number($maxheight,100,10,2000);
    $newwidth  = $maxwidth;
    $newheight = $maxheight;

    $sdi = new SD_Image($imagepath);
    $sdi->setImageTitle(isset($title)?(string)$title:'');
    $sdi->setImageAlt(isset($alt)?(string)$alt:'');
    $sdi->setImageID('');
    $sdi->setImageUrl('');
    $sizes = $sdi->ScaleDisplayWidth($maxwidth, $maxheight, false, $ScaleUp);
    if(($sizes !== false))
    {
      $newwidth  = $sizes[0];
      $newheight = $sizes[1];
    }
    if($sdi->getImageValid())
    {
      // Use "Data URI" (image encoded) if specified:
      $img = false;
      $noSizing = false;
      if(!empty($dataUri))
      {
        $img = self::GetThumbnailDataUri($imagepath,$newwidth,$newheight);
		
      }
      if($img !== false)
        $noSizing = true;
      else
        $img = str_replace(' ','%20',$imagepath) . '?'.TIME_NOW;

      return('<img alt="'.$sdi->getImageAlt().'"'.
           ($sdi->getImageTitle() ? ' title="'.$sdi->getImageTitle().'"' : '').
           (empty($html_id)?'':' id="'.trim($html_id).'"').
           (empty($classname)?'':' class="'.trim($classname).'"').
           ' src="' . ($mainsettings['use_data_uri'] ? $img : $imagepath) . '"'.
           ' width="'.$newwidth.'"'.# height="'.$newheight.'"'.
           (empty($classname)?' style="max-width:'.$newwidth.'px;max-height='.$newheight.'px"':'').
           ' />');
    }

    return '';

  } //GetThumbnailTag


  // ##########################################################################
  // GetImageMimeType
  // ##########################################################################

  public static function GetMimeTypeFromFilename($filename)
  {
    if(empty($filename)) return false;

    $filetype = 'application/octet-stream';

    require_once(SD_INCLUDE_PATH . 'mimetypes.php');
    if(function_exists('sd_getfilemimetype'))
    {
      $filetype = sd_getfilemimetype($filename);
    }
    else
    {
      if(function_exists('finfo_open'))
      {
        $finfo = @finfo_open(FILEINFO_MIME); // return mime type ala mimetype extension
      }
      if(isset($finfo))
      {
        $filetype = @finfo_file($finfo, $filename);
        if(function_exists('finfo_close'))
        {
          @finfo_close($finfo);
        }
      }
      else
      {
        if(function_exists('mime_content_type'))
        {
          $filetype = @mime_content_type($filename);
        }
      }
    }

    return $filetype;

  } //GetMimeTypeFromFilename

} // END OF SD_Image_Helper
} // DO NOT REMOVE!


// ############################################################################
// SD_Image CLASS
// ############################################################################
if(!class_exists('SD_Image'))
{
class SD_Image extends SD_Media_Base
{
  private static $_classInitDone      = false;
  private static $_GD_Installed       = false;
  private static $_GD_ImageTypes      = 0;

  protected $_crop_options = array();
  protected $error_code, $error_msg, $image_info, $isJPG, $isPNG, $image_ext,
            $image_exists, $image_type, $image_valid, $width, $height,
            $image_title, $image_alt, $image_url,
            $display_width, $display_height,
            $image_id, $filesize,
            $source_handle=false,
            $source_transp_index=false,
            $source_transp_color=false;

  public $target_file = '';
  public $image_file  = '';

  public function __construct($image_file='')
  {
    $this->setImageFile($image_file);
  }

  public function __destruct()
  {
    $this->destroy_handle();
  }

  private function destroy_handle()
  {
    $this->source_transp_index = false;
    $this->source_transp_color = false;
    if(isset($this->source_handle) && ($this->source_handle !== false) &&
       is_resource($this->source_handle))
    {
      @ImageDestroy($this->source_handle);
    }
    unset($this->source_handle);
    $this->source_handle = false;
  }

  private static function _Init() //SD360
  {
    if(self::$_classInitDone) return;
    if(self::$_GD_Installed = function_exists('imagetypes'))
    {
      self::$_GD_ImageTypes = imagetypes();
    }
    self::$_classInitDone = true;
    parent::ImageFilterExists();
    if(function_exists('ini_set')) @ini_set("gd.jpeg_ignore_warning", 1);
  }

  private function setError($errorCode)
  {
    $this->error_code = (int)$errorCode;
    $this->error_msg = parent::getErrorPhrase($errorCode);
  }

  public function setCrop($enabled, $x1=0, $x2=0, $y1=0, $y2=0)
  {
    if(empty($enabled))
    {
      $this->_crop_options = array();
      $this->_crop_options['enabled'] = false;
      return;
    }
    $this->_crop_options['enabled'] = true;
    $this->_crop_options['x1'] = intval($x1);
    $this->_crop_options['x2'] = intval($x2);
    $this->_crop_options['y1'] = intval($y1);
    $this->_crop_options['y2'] = intval($y2);
  }

  /**
  * Entry point to use this object by providing a valid image file name.
  * If successful, TRUE will be returned, otherwise negative error codes.
  *
  * @param string $image_file     Full path- and filename of source image file, ex: "./images/test_thumb.jpg"
  * @return multi                 true if successful, but negative error codes if fatal error!
  */
  public function setImageFile($image_file=null)
  {
    self::_Init();

    $this->destroy_handle();
    $this->_crop_options = array();
    $this->error_code = 0;
    $this->error_msg = '';
    $this->image_info = false;
    $this->filesize = 0; #SD362
    $this->height = 0;
    $this->width = 0;
    $this->display_width = 0;
    $this->display_height = 0;
    $this->image_id = '';
    $this->image_ext = '';
    $this->image_type = 0;
    $this->image_valid = false;
    $this->image_file = '';
    $this->image_title = '';
    $this->image_alt = '';
    $this->image_url = '';
    $this->isJPG = false;
    $this->isPNG = false;

    if(!isset($image_file))
    {
      $this->setError(SD_MEDIA_ERR_INVALID);
      return false;
    }

    if(empty($image_file) || !file_exists($image_file) ||
       !SD_Image_Helper::FilterImagename($image_file) || is_executable($image_file))
    {
      $this->setError(SD_MEDIA_ERR_INVALID);
      return false;
    }
    if(function_exists('exif_imagetype') &&
       !($this->image_type = exif_imagetype($image_file))) #SD362
    {
      $this->setError(SD_MEDIA_ERR_INVALID);
      return false;
    }

    if(!$this->image_info = @getimagesize($image_file))
    {
      $this->setError(SD_MEDIA_ERR_INVALID);
      return false;
    }

    $this->image_file = $image_file;

    if(!$this->filesize = @filesize($this->image_file)) #SD362
    {
      $this->filesize = 0;
    }
    if(false === ($this->image_ext = parent::getExtention($this->image_file, SD_Media_Base::getImageExtentions())))
    {
      $this->setError(SD_MEDIA_ERR_WRONG_EXT);
      //DO NOT return HERE, it's not a fatal error!
    }

    $this->display_width  = $this->width  = (int)$this->image_info[0];
    $this->display_height = $this->height = (int)$this->image_info[1];
    $this->image_type     = (empty($this->image_info[2])?0:$this->image_info[2]);
                            // e.g. IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG

    $this->image_ext = SD_Image_Helper::GetExtByType($this->image_type,false);
    $this->isJPG     = in_array($this->image_type, array(IMAGETYPE_JPEG,IMAGETYPE_JPEG2000));
    $this->isPNG     = ($this->image_type == IMAGETYPE_PNG);

    $this->image_exists = true;
    $this->image_valid = true;

    return true;

  } //setImageFile

  public static function getGD_Installed()
  {
    self::_Init();
    return self::$_GD_Installed;
  }

  public function getHtml()
  {
    if(!$this->image_valid) return '';
    $url = (strlen($this->image_url) ? $this->image_url . basename($this->image_file) : $this->image_file);
    $title = (strlen($this->image_title) ? 'title="'.$this->image_title.'" ' : '');
    return '<img '.
      (strlen($this->image_id)  ? 'id="'.$this->image_id.'" ':'').
      (strlen($this->image_alt) ? 'alt="'.$this->image_alt.'" ':' alt=""').
      $title.'src="'.$url.'" width="'.$this->display_width.'" height="'.$this->display_height.'" />';
  }

  public function __toString()
  {
    return $this->getHtml();
  }

  public function getErrorCode()    { return (int)$this->error_code; }
  public function getErrorMessage()
  {
    // In case only the error code was set, but without message,
    // then re-apply the error code:
    if(($this->error_code !== 0) && empty($this->error_msg))
    {
      $this->setError($this->error_code);
    }
    return $this->error_msg;
  }

  public function getImageAlt()     { return $this->image_alt; }
  public function getImageExists()  { return $this->image_exists; }
  public function getImageExt()     { return $this->image_ext; }
  public function getImageTitle()   { return $this->image_title; }
  public function getImageValid()   { return $this->image_valid; }
  public function getImageHeight()  { return (int)$this->height; }
  public function getImageWidth()   { return (int)$this->width; }
  public function getSize()         { return (int)$this->filesize; } #SD362
  public function getImageType()    { return $this->image_type; }
  public function getMimeType()
  {
    if(!$this->image_valid || empty($this->image_type)) return '';
    return @image_type_to_mime_type($this->image_type);
  }

  public function CopyTo($newPathAndFile) #SD362
  {
    if(!$this->image_valid || empty($newPathAndFile) || !file_exists($this->image_file))
    {
      return false;
    }
    $olddog = $GLOBALS['sd_ignore_watchdog'];
    $GLOBALS['sd_ignore_watchdog'] = true;
    $res = @copy($this->image_file, $newPathAndFile);
    $GLOBALS['sd_ignore_watchdog'] = $olddog;
    return $res;
  }

  public function setImageAlt($newAlt)     { $this->image_alt   = (string)$newAlt; }
  public function setImageID($newID)       { $this->image_id    = (string)$newID; }
  public function setImageTitle($newTitle) { $this->image_title = (string)$newTitle; }
  public function setImageUrl($newUrl)     { $this->image_url   = (string)$newUrl; }

  public function ScaleDisplayWidth($maxWidth, $maxHeight, $squared=false, $scaleUp=true)
  {
    if(!$this->image_valid || empty($maxWidth) || empty($maxHeight)) return false;

    if(!empty($squared))
    {
      $squareoff_max = min($maxWidth, $maxHeight);
      if($sizes = SD_Image_Helper::ScaleThumbnailSizes($this->image_file, $squareoff_max, $squareoff_max))
      {
        $this->display_width = $sizes[0];
        $this->display_height = $sizes[1];
      }
      // Figure out which is the shorter side and resize the thumbs to that size
      if($this->display_width > $this->display_height)
      {
        $this->display_height = $this->display_width;
      }
      else
      {
        $this->display_width = $this->display_height;
      }
    }
    else
    {
      // Calculate scaling factor
      $scale = min($maxWidth/$this->display_width, $maxHeight/$this->display_height);

      // If the image is larger than the max, shrink it
      if( ($scale < 1) || !empty($scaleUp))
      {
        $this->display_width  = floor($scale * $this->display_width);
        $this->display_height = floor($scale * $this->display_height);
      }

      if(($this->display_height == 0) && ($maxHeight > 0))
      {
        $this->display_height = $maxHeight;
      }

      if(($this->display_width == 0) && ($maxWidth > 0))
      {
        $this->display_width = $maxWidth;
      }
    }
    return array($this->display_width, $this->display_height);

  } //ScaleDisplayWidth


  public function createImageHandle()
  {
    $this->setError(0);

    $this->destroy_handle();
    $this->source_handle = false;
    if(empty($this->image_file) || !$this->image_valid)
    {
      return false;
    }

    $function_to_write = '';
    $error_format = '';

    switch($this->image_type)
    {
      case IMAGETYPE_GIF:
        if(self::$_GD_ImageTypes & IMG_GIF)
        {
          $function_to_write = 'imagegif';
          $this->source_handle = imagecreatefromgif($this->image_file);
          if(($this->source_transp_index = imagecolortransparent($this->source_handle)) >= 0)
          {
            $this->source_transp_color = @imagecolorsforindex($this->source_handle, $this->source_transp_index);
          }
        }
        else
        {
          $error_format = 'GIF';
        }
      break;

      case IMAGETYPE_JP2:
      case IMAGETYPE_JPC:
      case IMAGETYPE_JPX:
      case IMAGETYPE_JPEG2000:
      case IMAGETYPE_JPEG:
        if(self::$_GD_ImageTypes & IMG_JPEG)
        {
          $function_to_write = 'imagejpeg';
          $this->source_handle = imagecreatefromjpeg($this->image_file);
          $this->isJPG = true;
        }
        else
        {
          $error_format = 'JPEG';
        }
      break;

      case IMAGETYPE_PNG:
        if(self::$_GD_ImageTypes & IMG_PNG)
        {
          $function_to_write = 'imagepng';
          $this->source_handle = imagecreatefrompng($this->image_file);
          imagealphablending($this->source_handle, false);
          imagesavealpha($this->source_handle,true); //SD362
          $this->isPNG = true;
        }
        else
        {
          $error_format = 'PNG';
        }
      break;

      case IMAGETYPE_BMP:
      case IMAGETYPE_WBMP:
        if(self::$_GD_ImageTypes & IMG_WBMP)
        {
          $function_to_write = 'imagewbmp';
          $this->source_handle = imagecreatefromwbmp($this->image_file);
        }
        else
        {
          $error_format = 'WBMP';
        }

      case IMAGETYPE_XBM:
        if(function_exists('imagecreatefromxbm'))
        {
          $function_to_write = 'imagexbm';
          $this->source_handle = imagecreatefromxbm($this->image_file);
        }
        else
        {
          $error_format = 'XBM';
        }
      break;

      default:
        $error_format = $this->getImageType();
      break;
    }

    if(!empty($error_format))
    {
      //SD362: added error message as phrase
      $this->setError(SD_MEDIA_ERR_THUMB_FAILED);
      global $sdlanguage;
      $this->error_msg = str_replace('%s',$this->error_msg,$sdlanguage['err_imagetype_unsupported']);
      return false;
    }

    return array('handle'     => $this->source_handle,
                 'write_func' => $function_to_write);

  } //createImageHandle


  /**
  * Creates a thumbnail of the current image file (if valid!). Resulting image is scaled
  * down to given max. width/height, keeping the aspect ratio intact ($keepratio);
  * optionally the target image will be squared if $squaroff is true.
  * $conf_arr may contain index "convert_to" with value "png","jpg" etc. to convert
  * output thumb to that format with the corresponding extention.
  *
  * Globals: $sdlanguage, $userinfo, SD defines
  *
  * @param string $thumbnailfile  Full path/filename where to save the thumbnail, ex: ./images/test_thumbnail.jpg
  * @param int $maxwidth          Max. width in pixels of resulting thumbnail
  * @param int $maxheight         Max. height in pixels of resulting thumbnail
  * @param bool $squareoff        Create square thumbnail (width equals height)
  * @param bool $keepratio        Keep aspect ratio to avoid deformation/stretching
  * @param array $conf_arr        Array with additional parameters (""convert_to") or for phpThumb
  * @return TRUE if no errors, otherwise error text
  */
  public function CreateThumbnail($thumbnailfile, $maxwidth, $maxheight,
                                  $squareoff=false, $keepratio=true,
                                  $conf_arr=array())
  {
    self::_Init();
    $this->setError(0);
    if(!self::getGD_Installed() || !function_exists('imagecreatetruecolor') || !$this->image_valid)
    {
      $this->setError(SD_MEDIA_ERR_GD_MISSING);
      return false;
    }

    $conf_arr = !isset($conf_arr) || !is_array($conf_arr) ? array(): $conf_arr;

    // phpThumb currently disabled:
    if(($this->isPNG || $this->isJPG) && parent::IsPhpThumb_Installed())
    {
      $doWatermark = !empty($conf_arr) && is_array($conf_arr) &&
                     (isset($conf_arr['wtext']) || isset($conf_arr['wimage'])) &&
                     strlen(trim($conf_arr['wtext'])) &&
                     isset($conf_arr['color']) && isset($conf_arr['font']) &&
                     isset($conf_arr['pos_x']) && isset($conf_arr['pos_y']);

      @include_once(SD_INCLUDE_PATH.'phpthumb/phpthumb.class.php');
      $phpThumb = new phpThumb();
      $phpThumb->setParameter('config_ttf_directory', ROOT_PATH.'includes/fonts');
      $phpThumb->setParameter('config_document_root', ROOT_PATH);
      $phpThumb->setParameter('config_cache_directory', ROOT_PATH.'cache/');
      $phpThumb->setParameter('config_output_format', 'png');
      $phpThumb->sourceFilename = $this->image_file;

      if(empty($squareoff))
      {
        $phpThumb->setParameter('w', $maxwidth);
        $phpThumb->setParameter('h', $maxheight);
        if(empty($keepratio))
        {
          $phpThumb->setParameter('zc', 'C');
        }
      }
      else
      {
        $phpThumb->setParameter('ws', $maxwidth);
        $phpThumb->setParameter('hs', $maxheight);
        if(!empty($keepratio))
        {
          $phpThumb->setParameter('far', 'C');
        }
        else
        {
          $phpThumb->setParameter('iar', '1');
        }
      }

      //Filters:
      //'ric': // RoundedImageCorners
      //'crop': // Crop
      //'bord': // Border
      //'over': // Overlay
      //'drop': // DropShadow
      //'mask': // Mask cropping
      //'wmi': // WaterMarkImage
      // "Watermark Text" parameters, separated by pipe "|" character:
      // $text, $size, $alignment, $hex_color, $ttffont, $opacity, $margin, $angle, $bg_color, $bg_opacity, $fillextend
      $filters = array();
      if($doWatermark)
      {
        $conf_arr['color'] = str_replace('#','',$conf_arr['color']);
        $filters[] = implode('|',
        array('wmt',
          $conf_arr['wtext'],
          ($conf_arr['size']?(int)$conf_arr['size']:6), // size
          ($conf_arr['alignment']?$conf_arr['alignment']:'TL'), // alignment, e.g. TL = top-left, C = centered, BR = bottom-right etc.
          $conf_arr['color'], // text color
          (strlen($conf_arr['font'])?$conf_arr['font'].'.ttf':''), // font name
          (isset($conf_arr['opacity'])?(int)$conf_arr['opacity']:100), // opacity
          (isset($conf_arr['margin'])?(int)$conf_arr['margin']:0), // margin
          (isset($conf_arr['angle'])?(int)$conf_arr['angle']:0), // angle in degrees, 0 = left-to-right
          (isset($conf_arr['bgcolor'])?$conf_arr['bgcolor']:false), // bg_color
          (isset($conf_arr['bgopacity'])?$conf_arr['bgopacity']:0), // bg_opacity
          ''  // fillextend
        ));
      }
      if(count($filters))
      {
        $phpThumb->setParameter('fltr', $filters);
      }

      if($phpThumb->GenerateThumbnail()) // this line is VERY important, do not remove it!
      {
        $phpThumb->RenderToFile($thumbnailfile);
        @chmod($thumbnailfile, 0644); //SD370
        return true;
      }
      else
      {
        $this->setError(SD_MEDIA_ERR_THUMB_FAILED);
        return false;
      }

    } //END phpthumb

    if(!$handle_arr = $this->createImageHandle())
    {
      $this->setError(SD_MEDIA_ERR_THUMB_FAILED);
      return false;
    }
    $this->source_handle = $handle_arr['handle'];
    $function_to_write = $handle_arr['write_func']; //default writer

    //SD362: new "convert_to" flag (e.g. "png", "jpg") to convert current
    // image to a different image type (png -> jpg etc.)
    if(!empty($conf_arr['convert_to']) &&
       isset(parent::$known_image_types[$conf_arr['convert_to']]))
    {
      if(function_exists('image'.$conf_arr['convert_to']))
      {
        $function_to_write = 'image'.$conf_arr['convert_to'];
      }
    }

    if(!isset($maxwidth)  || !is_numeric($maxwidth))  $maxwidth = 100;
    if(!isset($maxheight) || !is_numeric($maxheight)) $maxheight = 100;

    //SD332: Check config for "crop" setup:
    $this->setCrop(false);
    $x1 = $x2 = $y1 = $y2 = $dx = $dy = 0;
    if(!empty($conf_arr['crop']))
    {
      $this->setCrop(true, $conf_arr['x1'], $conf_arr['x2'], $conf_arr['y1'], $conf_arr['y2']);
      $x1 = $conf_arr['x1'];
      $x2 = $conf_arr['x2'];
      $y1 = $conf_arr['y1'];
      $y2 = $conf_arr['y2'];
      // Override image dimensions with cropped area dimensions:
      $this->width  = $x2 - $x1;
      $this->height = $y2 - $y1;
    }

    // ************ RESIZE NOW ************
    // If the size suggested is larger than the current image
    // make the new size the same as the current one
    if($squareoff)
    {
      if(!empty($conf_arr['fixedsize']))
      {
        $squareoff_width  = $maxwidth;
        $squareoff_height = $maxheight;
        $thumbnail_width  = $this->width;
        $thumbnail_height = $this->height;
        if($thumbnail_width >= $thumbnail_height)
        {
          $thumbnail_width = max($thumbnail_width, $squareoff_width);
        }
        else
        {
          $thumbnail_height = max($thumbnail_height, $squareoff_height);
        }
        if($thumbnail_width < $squareoff_width)
        {
          $dx = round(($maxwidth - $thumbnail_width) / 2);
        }
        if($thumbnail_height < $squareoff_height)
        {
          $dy = round(($maxheight - $thumbnail_height) / 2);
        }
      }
      else
      {
        $squareoff_width = $squareoff_height = min($maxwidth, $maxheight);
        if(false !== ($sizes = SD_Image_Helper::ScaleThumbnailSizes(
                                 $this->source_handle,
                                 $squareoff_width, $squareoff_height,
                                 1, !empty($conf_arr['fixedsize']))))
        {
          $thumbnail_width  = $sizes[0];
          $thumbnail_height = $sizes[1];
          if($thumbnail_width < $squareoff_width)
          {
            $dx = round(($maxwidth - $thumbnail_width) / 2);
          }
          else
          if($thumbnail_height < $squareoff_height)
          {
            $dy = round(($maxheight - $thumbnail_height) / 2);
          }
        }
        else
        {
          // Figure out which is the shorter side and resize the thumbs to that size
          if($this->width > $this->height)
          {
            $thumbnail_width = $thumbnail_height = $maxwidth;
          }
          else
          {
            $thumbnail_width = $thumbnail_height = $maxheight;
          }
        }
      }
    }
    else
    {
      //SD370 if fixedsize is specified, then make image the exact size
      $thumbnail_width  = $this->width;
      $thumbnail_height = $this->height;
      if(!empty($conf_arr['fixedsize']))
      {
        $thumbnail_width  = $maxwidth;
        $thumbnail_height = $maxheight;
      }
      else
      {
        if($maxwidth  > $this->width)  $maxwidth  = $this->width;
        if($maxheight > $this->height) $maxheight = $this->height;

        if($this->width > $this->height)
        {
          $thumbnail_width = $maxwidth;
          $thumbnail_height = (int)($maxwidth * $this->height / $this->width);
        }
        #else
        if($this->height > $maxheight)
        {
          $thumbnail_height = $maxheight;
          $thumbnail_width = (int)($maxheight * $this->width / $this->height);
        }
      }
    }

    if(empty($thumbnail_width) || empty($thumbnail_height))
    {
      $this->setError(SD_MEDIA_ERR_WIDTH);
      return false;
    }

    parent::requestMem();

    // create a blank image for the thumbnail
    if(false === ($destination_handle = @imagecreatetruecolor(($squareoff?$squareoff_width:$thumbnail_width),
                                                              ($squareoff?$squareoff_height:$thumbnail_height))))
    {
      return false;
    }

    $back_color = false;
    if(isset($conf_arr['bgcolor']) && strlen($conf_arr['bgcolor']))
    {
      #SD362: - lowercase; support 3 letter notation; default to #FFF bg if error
      $conf_arr['bgcolor'] = strtolower(str_replace('#','',trim($conf_arr['bgcolor'])));
      if(strlen($conf_arr['bgcolor'])==3)
        sscanf($conf_arr['bgcolor'], "%x%x%x", $red, $green, $blue);
      else
        sscanf($conf_arr['bgcolor'], "%2x%2x%2x", $red, $green, $blue);
      $back_color = @imagecolorallocate($destination_handle, $red, $green, $blue);
      if($back_color !== false)
      {
        $back_color = sscanf('FFF', "%x%x%x", $red, $green, $blue);
        $back_color = @imagecolorallocate($destination_handle, $red, $green, $blue);
      }
      imagefill($destination_handle, 0, 0, $back_color);
    }
    //SD362: else check for transparency in GIF,PNG images
    else
    if($this->image_type == IMAGETYPE_PNG)
    {
      imagealphablending($destination_handle, false);
      $color = imagecolorallocatealpha($destination_handle, 0, 0, 0, 127);
      imagefill($destination_handle, 0, 0, $color);
      imagesavealpha($destination_handle, true);
    }
    else
    if(($this->image_type == IMAGETYPE_GIF) && ($this->source_transp_index >= 0))
    {
      $trnprt_color = imagecolorallocate($destination_handle,
                        $this->source_transp_color['red'],
                        $this->source_transp_color['green'],
                        $this->source_transp_color['blue']);
      imagefill($destination_handle, 0, 0, $trnprt_color);
      imagecolortransparent($destination_handle, $trnprt_color);
    }

    $txt_color = false;
    if(isset($conf_arr['color']) && strlen($conf_arr['color']))
    {
      $conf_arr['color'] = str_replace('#','',$conf_arr['color']);
      if(strlen($conf_arr['color'])==6)
      {
        sscanf($conf_arr['color'], "%2x%2x%2x", $red, $green, $blue);
      }
      else
      {
        sscanf($conf_arr['color'], "%1x%1x%1x", $red, $green, $blue);
        $red *= 16; $green *= 16; $blue *= 16;
      }
      $txt_color = @imagecolorallocate($destination_handle, $red, $green, $blue);
    }

    // Rescale image now into destination image
    if(!$res = @imagecopyresampled($destination_handle, $this->source_handle,
                                   $dx, $dy, $x1, $y1,
                                   $thumbnail_width, $thumbnail_height,
                                   $this->width, $this->height))
    {
      unset($destination_handle);
      $this->destroy_handle(); //???
      $this->setError(SD_MEDIA_ERR_THUMB_FAILED);
      return false;
    }

    if(($back_color!==false) && $squareoff)
    {
      if($thumbnail_width < $squareoff_width)
      {
        imagefill($destination_handle, 0, 0, $back_color);
        imagefill($destination_handle, $squareoff_width, 0, $back_color);
      }
      else
      if($thumbnail_height < $squareoff_height)
      {
        imagefill($destination_handle, 0, 0, $back_color);
        imagefill($destination_handle, 0, $squareoff_height, $back_color);
      }
    }
    $thumbnail_width  = $squareoff ? $squareoff_width  : $thumbnail_width;
    $thumbnail_height = $squareoff ? $squareoff_height : $thumbnail_height;
    $doWatermarkImage = (isset($conf_arr['wimage']) &&
                         ($conf_arr['wimage']!=='---') &&
                         file_exists($conf_arr['wimage']));

    //imagefilledrectangle($destination_handle, 330, 330, 470, 470, imagecolorallocate($destination_handle, 255, 0, 0 /* red */));
    if(($txt_color!==false) && isset($conf_arr['wtext']) && strlen($conf_arr['wtext']))
    {
      $font = (empty($conf_arr['font']) || !is_numeric($conf_arr['font']))? '0' : SD_INCLUDE_PATH.'fonts/'.(int)$conf_arr['font'].'.ttf';
      if(!@is_readable($font) || !is_file($font))
      {
        $font = false;
      }
      if(!font || !@imagettftext($destination_handle, (int)$conf_arr['size'], (int)$conf_arr['angle'],
                    (int)$conf_arr['pos_x'] , (int)$conf_arr['pos_y'],
                    $txt_color, $font, $conf_arr['wtext']))
      {
        @imagestring($destination_handle, $font, (int)$conf_arr['pos_x'], (int)$conf_arr['pos_y'],
                     $conf_arr['wtext'],$txt_color);
      }
    }

    // Create watermarked, target image file:
    $wmOK = false;
    if(!empty($doWatermarkImage) && ($this->isPNG || $this->isJPG))
    {
      //http://koivi.com/php-gd-image-watermark/
      $watermark_image = $conf_arr['wimage'];
      //$watermark_image = SD_INCLUDE_PATH.'images/watermarks/grad-check-trans.png';
      if($srcImage = imagecreatefrompng($watermark_image))
      {
        global $userinfo;
        $quality    = 100;
        $target     = ROOT_PATH.'cache/wmark_'.
                      (empty($userinfo['userid'])?'':$userinfo['userid'].'_').
                      date('YmdHis').($this->isPNG?'.png':'.jpg');
        $srcWidth   = imagesx($srcImage);
        $srcHeight  = imagesy($srcImage);
        $percentage = (double)$thumbnail_width/$srcWidth;
        $destHeight = round($srcHeight*$percentage)+1;
        $destWidth  = round($srcWidth*$percentage)+1;
        if($destHeight > $thumbnail_height){
          // if the width produces a height bigger than we want, calculate based on height
          $percentage = (double)$thumbnail_height/$srcHeight;
          $destHeight = round($srcHeight*$percentage)+1;
          $destWidth  = round($srcWidth*$percentage)+1;
        }
        if(false !== ($destImage = imagecreatetruecolor($destWidth-1,$destHeight-1)))
        {
          if(imagealphablending($destImage,FALSE))
          {
            if(imagesavealpha($destImage,TRUE))
            {
              if(imagecopyresampled($destImage,$srcImage,0,0,0,0,$destWidth,$destHeight,$srcWidth,$srcHeight))
              {
                if(imagepng($destImage, $target))
                {
                  imagedestroy($destImage);
                  imagedestroy($srcImage);
                  // ^^^ BASICALLY End-of-function

                  $wmInfo = @getimagesize($target);
                  $waterMarkDestWidth=$wmInfo[0];
                  $waterMarkDestHeight=$wmInfo[1];

                  // Generate own thumb now which is target for created watermark:
                  if(@$function_to_write($destination_handle, $thumbnailfile))
                  {
                    @ImageDestroy($destination_handle);
                    //unset($destination_handle);
                    clearstatcache();
                    if($this->isPNG)
                      $resultImage = imagecreatefrompng($thumbnailfile);
                    else
                      $resultImage = imagecreatefromjpeg($thumbnailfile);
                    if($resultImage){
                      imagealphablending($resultImage,true);
                      if($finalWaterMarkImage = imagecreatefrompng($target)){
                        $finalWaterMarkWidth = imagesx($finalWaterMarkImage);
                        $finalWaterMarkHeight = imagesy($finalWaterMarkImage);

                        @imagecopy($resultImage,
                                  $finalWaterMarkImage,
                                  $conf_arr['pos_x'],
                                  $conf_arr['pos_y'],
                                  0,
                                  0,
                                  $finalWaterMarkWidth,
                                  $finalWaterMarkHeight
                        );
                        if($this->isPNG)
                        {
                          imagealphablending($resultImage,false);
                          imagesavealpha($resultImage,true);
                          $wmOK = @imagepng($resultImage,$thumbnailfile,$quality);
                        }
                        else
                        {
                          $wmOK = @imagejpeg($resultImage,$thumbnailfile,$quality);
                        }
                        if(!$wmOK == @getimagesize($thumbnailfile))
                        {
                          $this->error_msg = 'Watermarking failed';
                        }
                        imagedestroy($resultImage);
                        imagedestroy($finalWaterMarkImage);
                      }
                    }
                  }
                  @unlink($target);
                }
              }
            }
          }
        }
      }
    }

    // Pre-change thumbnails' permissions (if one exists already)
    if(isset($thumbnailfile) && strlen($thumbnailfile))
    {
      if(file_exists($thumbnailfile))
      {
        @chmod($thumbnailfile, 0644);
      }

      // Save the thumbnail
      if( (file_exists($thumbnailfile) && !is_writable($thumbnailfile)) ||
          !@$function_to_write($destination_handle, $thumbnailfile) )
      {
        $this->setError(SD_MEDIA_ERR_THUMB_FAILED);
      }
      else
      // Post-change thumbnails' permissions
      if(file_exists($thumbnailfile))
      {
        @chmod($thumbnailfile, 0644);
      }

      @imagedestroy($destination_handle);
      $this->destroy_handle();
    }
    else
    {
      // SD362: if no output file specified, replace original image
      // with newly generated image in memory:
      $this->destroy_handle();
      $this->source_handle = $destination_handle;
      $this->width  = imagesx($this->source_handle);
      $this->height = imagesy($this->source_handle);
    }

    return ($this->error_code == 0);

  } //CreateThumbnail


  // ##########################################################################
  //                         FILTER FUNCTIONS
  // ##########################################################################


  public function FilterBlur()
  {
    if(($this->source_handle===false) || !is_resource($this->source_handle))
      return true;

    if(parent::ImageFilterExists())
    {
      return @imagefilter($this->source_handle, IMG_FILTER_GAUSSIAN_BLUR, 100);
    }

    if(!function_exists('imageconvolution')) return false;

    // Backwards compatibility (slow!)
    $gaussian = array(array(1.0, 2.0, 1.0), array(2.0, 4.0, 2.0), array(1.0, 2.0, 1.0));
    $gaussian = array(array(1.0, 2.0, 1.0), array(2.0, 4.0, 2.0), array(1.0, 2.0, 1.0));
    imageconvolution($this->source_handle, $gaussian, 16, 0);

    return true;

  } //FilterBlur


  public function FilterNegate()
  {
    if(($this->source_handle===false) || !is_resource($this->source_handle))
      return true;

    if(parent::ImageFilterExists())
    {
      return @imagefilter($this->source_handle, IMG_FILTER_NEGATE);
    }

    // Backwards compatibility (slow!)
    $maxx = @imagesx($this->source_handle);
    $maxy = @imagesy($this->source_handle);
    if(empty($maxx) || empty($maxy)) return false;
    for($x = 0; $x < $maxx; ++$x)
    {
      for($y = 0; $y < $maxy; ++$y)
      {
        $index = imagecolorat($this->source_handle, $x, $y);
        $rgb   = imagecolorsforindex($this->source_handle, $index);
        $color = imagecolorallocate($this->source_handle,
                   255 - $rgb['red'],
                   255 - $rgb['green'],
                   255 - $rgb['blue']);

        if(!imagesetpixel($this->source_handle, $x, $y, $color)) return false;
      }
    }
    return true;

  } //FilterNegate


  function FilterColorize($color)
  {
    if(($this->source_handle===false) || !is_resource($this->source_handle))
      return true;

    if(!isset($color) || !is_array($color) || (count($color)!=3)) return false;

    if(parent::ImageFilterExists() && defined('IMG_FILTER_COLORIZE'))
    {
      return @imagefilter($this->source_handle, IMG_FILTER_COLORIZE,intval($color[0]), intval($color[1]), intval($color[2]));
    }
    return false;

  } //FilterColorize

  public function mirror()
  {
    if(($this->source_handle===false) || !is_resource($this->source_handle))
      return true;

    $wid = imagesx($this->source_handle);
    $hei = imagesy($this->source_handle);
    if(false === ($im2 = imagecreatetruecolor($wid,$hei))) return false;

    for($x = 0; $x < $wid; $x++)
    {
      for($y = 0; $y < $hei; $y++)
      {
        $ref = imagecolorat($this->source_handle, $x, $y);
        imagesetpixel($im2, $wid - $x, $y, $ref);
     }
    }
    $this->destroy_handle();
    $this->source_handle = $im2;
    return true;
  }

  public function flip_slow()
  {
    if(($this->source_handle===false) || !is_resource($this->source_handle))
      return true;

    $wid = imagesx($this->source_handle);
    $hei = imagesy($this->source_handle);
    if(false === ($im2 = imagecreatetruecolor($wid,$hei))) return false;

    for($x = 0;$x < $wid; $x++)
    {
      for($y = 0;$y < $hei; $y++)
      {
        $ref = imagecolorat($this->source_handle, $x, $y);
        imagesetpixel($im2, $x, $hei - $y, $ref);
      }
    }
    $this->destroy_handle();
    $this->source_handle = $im2;
    return true;
  } //flip_slow

  public function flip($h=1, $v=0)
  {
    if( ($this->source_handle===false) || !is_resource($this->source_handle) ||
        (empty($h) && empty($v))) return true;

    $width  = imagesx($this->source_handle);
    $height = imagesy($this->source_handle);

    if(false === ($temp = imagecreatetruecolor($width,$height))) return false;

    imagecopy($temp,$this->source_handle,0,0,0,0,$width,$height);

    if(!empty($h))
    {
      for($x = 0; $x < $width; $x++)
      {
        imagecopy($this->source_handle, $temp, $width-$x-1, 0, $x, 0, 1, $height);
      }
      imagecopy($temp,$this->source_handle,0,0,0,0,$width,$height);
    }

    if(!empty($v))
    {
      for($x = 0; $x < $height; $x++)
      {
        imagecopy($this->source_handle, $temp, 0, $height-$x-1, 0, $x, $width, 1);
      }
    }

    return true;

  } //flip


  public function printtext($text='',$color='#000000',$backcolor='#FFFFFF')
  {
    if(($this->source_handle===false) || !is_resource($this->source_handle))
      return true;

    if(trim($text) === '') return false;
    // Replace path by your own font path
    $font = '4.ttf';

    $gd_info = @gd_info();
    $use_ttf = !empty($gd_info['FreeType Support']) && function_exists('imagettftext') &&
               is_callable('imagettftext') && is_file(ROOT_PATH.'includes/fonts/'.$font);
    if(!$use_ttf) return false;

    $fpath = realpath(ROOT_PATH.'includes/fonts').'/';
    if(!sd_safe_mode())
    {
      $old_ignore = $GLOBALS['sd_ignore_watchdog'];
      $GLOBALS['sd_ignore_watchdog'] = true;
      @putenv('GDFONTPATH=' . $fpath);
      $GLOBALS['sd_ignore_watchdog'] = $old_ignore;
    }

    imagealphablending($this->source_handle, true);

    // Default colors (black on white)
    $fontcolor   = imagecolorallocate($this->source_handle, 0, 0, 0);
    $grey  = imagecolorallocate($this->source_handle, 128, 128, 128);

    $color = isset($color) ? trim($color, ' #') : '';
    if(ctype_xdigit($color) && ((strlen($color) == 3) || (strlen($color) == 6)))
    {
      if(strlen($color)==3)
        $fontcolor = imagecolorallocate($this->source_handle,
                       hexdec(substr($color,0,1).'0'),
                       hexdec(substr($color,1,1).'0'),
                       hexdec(substr($color,2,1).'0'));
      else
        $fontcolor = imagecolorallocate($this->source_handle,
                       hexdec(substr($color,0,2)),
                       hexdec(substr($color,2,2)),
                       hexdec(substr($color,4,2)));
    }

    $backcolor = isset($backcolor) ? trim($backcolor, ' #') : '';
    if(ctype_xdigit($backcolor) && ((strlen($backcolor) == 3) || (strlen($backcolor) == 6)))
    {
      if(strlen($backcolor)==3)
        $fontbgcolor = imagecolorallocate($this->source_handle,
                       hexdec(substr($backcolor,0,1)),
                       hexdec(substr($backcolor,1,1)),
                       hexdec(substr($backcolor,2,1)));
      else
        $fontbgcolor = imagecolorallocate($this->source_handle,
                       hexdec(substr($backcolor,0,2)),
                       hexdec(substr($backcolor,2,2)),
                       hexdec(substr($backcolor,4,2)));
    }

    $border = 3;
    $fontSize = max(8,ceil($this->width / strlen($text))); # 20;
    $fontSize = Is_Valid_Number($fontSize,14,8,24);
    $fontAngle = 0;
    $bbox = imagettfbbox($fontSize, $fontAngle, $fpath.$font, $text);
    $bwidth  = ($bbox[4] - $bbox[6]) + (2*$border);
    $bheight = ($bbox[7] - $bbox[1]) + (2*$border);

    // bottom-right display:
    $x = ($this->width  - $bwidth - $border);
    $y = ($this->height - 3*$border);

    # centered display:
    #$x = $bbox[0] + (imagesx($this->source_handle) / 2) - ($bbox[4] / 2) - 25;
    #$y = $bbox[1] + (imagesy($this->source_handle) / 2) - ($bbox[5] / 2) - 5;

    if(isset($fontbgcolor) && ($fontAngle===0))
      imagefilledrectangle($this->source_handle,
        $x-$border, $y+(2*$border), $x + $bwidth + $border, $y + $bheight - (2*$border), $fontbgcolor);

    #vertical character:
    #imagecharup($im, 5, 13, 13, $string, $black);

    // Add some shadow to the text
    imagettftext($this->source_handle, $fontSize, $fontAngle, $x+2, $y+2, $grey, $fpath.$font, $text);

    // Add the text
    imagettftext($this->source_handle, $fontSize, $fontAngle, $x, $y, $fontcolor, $fpath.$font, $text);

    return true;
  }

  public function FilterImage($filter_options=array(), $doOutput=false, $outputFilename=null)
  {
    if( !$this->image_valid || !isset($filter_options) ||
        !is_array($filter_options) ||
        (false === ($handle_arr = $this->createImageHandle())) )
    {
      $this->setError(SD_MEDIA_ERR_FILTER_FAILED);
      return false;
    }

    $this->source_handle = $handle_arr['handle'];
    $function_to_write = $handle_arr['write_func'];
    $t = false;

    if(!empty($filter_options['blur']))
    {
      $t = $this->FilterBlur();
    }

    // First execute "imagefilter"-dependent functions (if available)
    if(parent::ImageFilterExists())
    {
      if(defined('IMG_FILTER_EMBOSS') && !empty($filter_options['emboss']))
      {
        $t = @imagefilter($this->source_handle, IMG_FILTER_EMBOSS);
      }

      if(defined('IMG_FILTER_GRAYSCALE') && isset($filter_options['grayscale']))
      {
        $t = @imagefilter($this->source_handle, IMG_FILTER_GRAYSCALE);
      }

      if(defined('IMG_FILTER_PIXELATE') && !empty($filter_options['pixelate']))
      {
        $t = @imagefilter($this->source_handle, IMG_FILTER_PIXELATE, intval($filter_options['pixelate']));
      }

      if(defined('IMG_FILTER_BRIGHTNESS') && !empty($filter_options['brightness']))
      {
        # -255 = min brightness, 0 = no change, +255 = max brightness
        $t = @imagefilter($this->source_handle, IMG_FILTER_BRIGHTNESS, intval($filter_options['brightness']));
      }

      if(defined('IMG_FILTER_CONTRAST') && !empty($filter_options['contrast']))
      {
        # -100 = max contrast, 0 = no change, +100 = min contrast (note the direction!)
        $t = @imagefilter($this->source_handle, IMG_FILTER_CONTRAST, intval($filter_options['contrast']));
      }

      if(defined('IMG_FILTER_SMOOTH') && !empty($filter_options['smooth']))
      {
        # any float; -10 ... 2048
        # -6 = "unsharp"; -7 or -8: like emboss/edging
        $filter_options['smooth'] = Is_Valid_Number($filter_options['smooth'],25,-10,100);
        $t = @imagefilter($this->source_handle, IMG_FILTER_SMOOTH, (float)($filter_options['smooth']));
      }

      if(defined('IMG_FILTER_MEAN_REMOVAL') && !empty($filter_options['meanremoval']))
      {
        $t = @imagefilter($this->source_handle, IMG_FILTER_MEAN_REMOVAL);
      }
    }

    // functions independent of "imagefilter" or alternative (hopefully :-? )
    if( !empty($filter_options['colorize']) &&
        is_array($filter_options['colorize']) )
    {
      $filter_options['colorize']['r'] = isset($filter_options['colorize']['r'])?intval($filter_options['colorize']['r']):0;
      $filter_options['colorize']['g'] = isset($filter_options['colorize']['g'])?intval($filter_options['colorize']['g']):0;
      $filter_options['colorize']['b'] = isset($filter_options['colorize']['b'])?intval($filter_options['colorize']['b']):0;
      $t = $this->FilterColorize(array($filter_options['colorize']['r'],
                                       $filter_options['colorize']['g'],
                                       $filter_options['colorize']['b']));
    }

    if(!empty($filter_options['gamma']))
    {
      # any float from 0 to 2
      $t = @imagegammacorrect($this->source_handle, 1.0, (float)($filter_options['gamma']));
    }

    if(!empty($filter_options['negate']))
    {
      $t = $this->FilterNegate();
    }

    if(!empty($filter_options['flip']))
    {
      $t = $this->flip(0, 1);
    }

    if(!empty($filter_options['mirror']))
    {
      $t = $this->flip(1, 0);
    }

    if(isset($filter_options['watermarktext']) && is_string($filter_options['watermarktext']))
    {
      $col = isset($filter_options['wtextcolor']) ? $filter_options['wtextcolor'] : '';
      $bg  = isset($filter_options['wbackcolor']) ? $filter_options['wbackcolor'] : '';
      $this->printtext($filter_options['watermarktext'], $col, $bg);
    }

    if(!empty($filter_options['rotate']) && function_exists('imagerotate'))
    {
      // keep transparency for PNG and GIF
      if(($this->image_type==IMAGETYPE_PNG) || ($this->image_type==IMAGETYPE_GIF))
      {
        $bg = imageColorAllocateAlpha($this->source_handle, 0, 0, 0, 127);
      }
      else
      {
        # otherwise set white as default background color
        $bg = 16777215; # = #FFFFFF
        if(isset($filter_options['rotatebg']))
        {
          $bg = hexdec(ltrim($filter_options['rotatebg'],'#'));
        }
      }
      // GD is counter-clockwise, make values behave clockwise ("360 - x")
      $rotang = 360 - Is_Valid_Number($filter_options['rotate'],0,-360,360);
      if(false !== ($rotated = @imagerotate($this->source_handle, $rotang, $bg)))
      {
        $t = true;
        if(($this->image_type==IMAGETYPE_PNG) || ($this->image_type==IMAGETYPE_GIF))
        {
          imagecolortransparent($rotated, $bg);
          imagealphablending($rotated, false);
          imagesavealpha($rotated, true);
        }
        $this->destroy_handle();
        $this->source_handle = $rotated;
        $this->width  = imagesx($this->source_handle);
        $this->height = imagesy($this->source_handle);
      }
    }

    // Options for 3rd param?
    $third = false;
    if($this->image_type == IMAGETYPE_JPEG)
    {
      $third = 90;
      if(isset($filter_options['quality']) &&
         is_numeric($filter_options['quality']))
      {
        $third = Is_Valid_Number($filter_options['quality'],100,10,100);
      }
    }
    else
    if( ($this->image_type == IMAGETYPE_PNG) &&
        isset($filter_options['compression']) &&
        is_numeric($filter_options['compression']) )
    {
      $third = Is_Valid_Number($filter_options['compression'],1,0,9);
    }

    // output the new image to the browser?
    if(!empty($doOutput) && !empty($handle_arr['write_func']))
    {
      $t = $function_to_write($this->source_handle,
                              (empty($outputFilename)?null:$outputFilename),
                              ($third!==false ? $third : null));
    }

    return !empty($t);

  } //FilterImage


  // ##########################################################################
  //                    UPLOAD/DOWNLOAD IMAGE FUNCTIONS
  // ##########################################################################


  /**
  * Process an uploaded image, whose $_FILE index is "$file_element_name".
  * Checks image restrictions on max. filesize, width and height.
  *
  * Globals: $sdlanguage
  *
  * @param string $file_element_name  Index of $_FILES array e.g. "image"
  * @param string $target_folder      Path-only for target image location (<b>no ROOT_PATH!</b>)
  * @param string $imagename          Filename-only for target image
  * @param string $valid_extensions   Allowed image extensions (if null, defaults to ::$known_image_types)
  * @param int $maxsize               Max. filesize of image (if no PHP limit reached)
  * @param int $maxwidth              Max. width in pixels of image
  * @param int $maxheight             Max. height in pixels of image
  * @return bool                      TRUE if successful, FALSE if errors: check "ErrorCode()"!
  */
  function UploadImage($file_element_name, $target_folder, $imagename, $valid_extensions,
                       $maxsize, $maxwidth=0, $maxheight=0)
  {
    $this->setError(0);

    if( !isset($file_element_name) || !strlen($file_element_name) ||
        !isset($imagename) || !strlen($imagename) ||
        !isset($_FILES[$file_element_name]) || !is_array($_FILES[$file_element_name]) ||
        empty($_FILES[$file_element_name]['name']) ||
        !is_uploaded_file($_FILES[$file_element_name]['tmp_name']) )
    {
      $this->setError(SD_MEDIA_ERR_UPLOAD);
      return false;
    }

    global $sdlanguage;

    $image = (array)$_FILES[$file_element_name];

    $imagesdir = ROOT_PATH . $target_folder;
    $is_image  = false;

    clearstatcache();
    if(!is_dir($imagesdir))
    {
      $this->setError(SD_MEDIA_ERR_NOFOLDER);
    }
    else if(!is_writable($imagesdir))
    {
      $this->setError(SD_MEDIA_ERR_WRITEFOLDER);
    }

    // check if file was uploaded
    if(!isset($image['tmp_name']) || (!empty($image['error']) && ($image['error'] != 4)))
    {
      $this->setError(SD_MEDIA_ERR_UPLOAD);
    }
    else
    if(empty($image['size']) || ($image['error'] == 4))
    {
      $this->setError(SD_MEDIA_ERR_UPLOAD);
    }

    if(!$this->error_code)
    {
      // lets make sure the file type is correct
      $hasExt = ($img_ext = parent::getExtention($imagename, $valid_extensions));
      if(false !== ($ext = parent::getExtention($image['name'], $valid_extensions)))
      {
        $is_image = true;
        // lets make sure the extension is correct
        if(!isset(SD_Media_Base::$known_image_types[$ext]))
        {
          $this->setError(SD_MEDIA_ERR_UNSUPPORTED);
        }
      }
      else
      {
        $this->setError(SD_MEDIA_ERR_WRONG_EXT);
      }
    }

    if(!$this->error_code)
    {
      // Before accessing it, we need to ...
      // a) secure the filename to avoid apache bug in executing the image!
      // b) move the uploaded temp file to our own directory to allow it
      //    to run on secured servers (safe mode, open_dir...)
      if(!$hasExt) $imagename .= '.'.$ext; //SD360
      if( SD_Image_Helper::FilterImagename($imagename) &&
          !is_executable($image['tmp_name']) &&
          is_uploaded_file($image['tmp_name']) )
      {
        if(@move_uploaded_file($image['tmp_name'], $imagesdir . $imagename))
        {
          @chmod($imagesdir . $imagename, 0644);
          // final attempt to make sure it's a real image:
          if(!$res = @getimagesize($imagesdir . $imagename))
          {
            $this->setError(SD_MEDIA_ERR_INVALID);
            @unlink($imagesdir . $imagename);
          }
          else
          {
            if(!empty($maxwidth) && is_numeric($maxwidth) && ($maxwidth<$res[0]))
            {
              $this->setError(SD_MEDIA_ERR_WIDTH);
            }
            if(!empty($maxheight) && is_numeric($maxheight) && ($maxwidth<$res[1]))
            {
              $this->setError(SD_MEDIA_ERR_HEIGHT);
            }
            if(!empty($maxsize) && is_numeric($maxsize))
            {
              $size = @filesize($imagesdir . $imagename);
              if(!$size || ($maxsize < $size))
              {
                $this->setError(SD_MEDIA_ERR_FILESIZE);
              }
            }
          }
        }
        else
        {
          $this->setError(SD_MEDIA_ERR_UPLOAD);
        }
      }
      else
      {
        $this->setError(SD_MEDIA_ERR_INVALID);
      }
    }

    if($this->error_code !== 0)
    {
      $this->setImageFile();
      return false;
    }

    $this->setImageFile($imagesdir . $imagename);
    return true;

  } //UploadImage

  // ##########################################################################

  //From: http://www.bitrepository.com/download-image.html
  function Download($source, $target_folder, $target_name, $method = 'curl') // default method: cURL
  {
    $this->setError(SD_MEDIA_ERR_INVALID);
    if(empty($method) || (($method !== 'curl') && ($method !== 'gd'))) return false;
    if(empty($source) || !strlen($source) || (substr($source,0,4)!='http')) return false;
    if(empty($target_folder) || !is_dir($target_folder) || !is_writable($target_folder)) return false;
    if(stripos($target_name,'..') !== false) return false; //SD343
    $this->setError(0);

    //SD343: suppress SD syslog message
    $oldflag = !empty($GLOBALS['sd_ignore_watchdog']);
    $GLOBALS['sd_ignore_watchdog'] = true;

    if(!$info = @getimagesize($source))
    {
      $GLOBALS['sd_ignore_watchdog'] = $oldflag;
      $this->setError(SD_MEDIA_ERR_UNSUPPORTED);
      return false;
    }
    $mime = $info['mime'];

    // What sort of image?
    $type = substr(strrchr($mime, '/'), 1);
    $new_image_ext = $type;

    parent::requestMem();

    switch ($type)
    {
      case 'jp2':
      case 'jpc':
      case 'jpg':
      case 'jpeg':
      case 'jpeg2000':
        $image_create_func = 'ImageCreateFromJPEG';
        $image_save_func = 'ImageJPEG';
        $new_image_ext = 'jpg';
        $quality = 100; // = best quality: 100
      break;

      case 'png':
        $image_create_func = 'ImageCreateFromPNG';
        $image_save_func = 'ImagePNG';
        $quality = 0; // = compression level: from 0 (no compression) to 9
      break;

      case 'bmp':
        $image_create_func = 'ImageCreateFromBMP';
        $image_save_func = 'ImageBMP';
      break;

      case 'gif':
        $image_create_func = 'ImageCreateFromGIF';
        $image_save_func = 'ImageGIF';
      break;

      case 'vnd.wap.wbmp':
        $image_create_func = 'ImageCreateFromWBMP';
        $image_save_func = 'ImageWBMP';
        $new_image_ext = 'bmp';
      break;

      case 'xbm':
        $image_create_func = 'ImageCreateFromXBM';
        $image_save_func = 'ImageXBM';
      break;

      default:
        $image_create_func = 'ImageCreateFromJPEG';
        $image_save_func = 'ImageJPEG';
        $new_image_ext = 'jpg';
    }

    $target_name = str_replace(' ','_', $target_name);
    $save_to = $target_folder.$target_name.'.'.$new_image_ext;
    $save_image = false;

    //SD362: info being false means that GD failed, so try curl as fallback
    if(($info === false) || ($method == 'curl'))
    {
      $save_image = $this->LoadImageCURL($source, $save_to);
    }
    else
    if(($info !== false) && ($method == 'gd'))
    {
      if(false !== ($img = $image_create_func($source)))
      {
        //SD343: exception handling, just in case
        try
        {
          if(isset($quality))
          {
            $save_image = $image_save_func($img, $save_to, $quality);
          }
          else
          {
            $save_image = $image_save_func($img, $save_to);
          }
        }
        catch(Exception $e) { $save_image = false; }
        if(($save_image !== false) && file_exists($save_to))
        {
          @chmod($save_to, 0644); //SD362
        }
      }
    }

    //SD343: restore syslog flag
    $GLOBALS['sd_ignore_watchdog'] = $oldflag;

    if(false !== $save_image)
    {
      return $this->setImageFile($save_to);
    }

    return false;

  } //Download


  function LoadImageCURL($source, $save_to)
  {
    //SD362: bugix: $source as parameter was missing
    if(!function_exists('curl_init')) return false;

    if(empty($source) || (false === ($ch = curl_init($source))))
    {
      return false;
    }
    if(false === ($fp = fopen($save_to, "wb")))
    {
      curl_close($ch);
      return false;
    }

    // set URL and other appropriate options
    $options = array(
      CURLOPT_FILE           => $fp,
      CURLOPT_HEADER         => 0,
      CURLOPT_FOLLOWLOCATION => 1,
      CURLOPT_TIMEOUT        => 60, // 1 minute timeout (should be plenty)
      CURLOPT_RETURNTRANSFER => 1);

    $result = false;
    if(curl_setopt_array($ch, $options))
    {
      $result = curl_exec($ch);
    }
    curl_close($ch);
    fclose($fp);

    return ($result!==false);

  } //LoadImageCURL

} // end of SD_Image class
} // DO NOT REMOVE!


// ############################################################################
// SD_Media CLASS
// ############################################################################

if(!class_exists('SD_Media'))
{
class SD_Media extends SD_Media_Base
{
  private $_settings     = array();
  private $_all_ext      = array();
  private $_audio_ext    = array();
  private $_image_ext    = array();
  private $_media_ext    = array();
  private $_osm_path     = '';
  private $_osm_ext      = array();
  private $_jp_ext       = array();
  private $_jm_ext       = array();
  private $_vjs_ext      = array();
  private $_video_supported_ext = array();

  private static $_initDone = false;

  public  static $errorCode = 0;

  public  $base_url         = '';
  public  $playtime         = '';
  public  $extention        = '';
  public  $embed_autoplay   = false;
  public  $embed_width      = 500;//16:9 format
  public  $embed_height     = 282;
  public  $option_bgcolor   = '#000';
  public  $is_audio         = false;
  public  $is_image         = false;
  public  $is_video         = false;
  public  $osm_url          = '';
  public  $osm_installed    = false;
  public  $player_osm       = false; // OSM Player - http://mediafront.org/
  public  $player_jp        = false; // jPlayer jQuery Plugin - http://www.jplayer.com/
  public  $player_vjs       = false; // VideoJS - http://videojs.com
  public  $player_jm        = false; // jMedia jQuery Plugin - http://malsup.com/jquery/media/
  public  $flash_url        = '';
  public  $mediafile_url    = '';
  public  $media_element_id = 'media_player'; // HTML tag to be replaced (if required)
  public  $media_poster     = ''; // image path/filename for videos
  public  $extralink        = ''; // extra link (only OSM for now)


  public function __construct( $_params = array() )
  {
    if(!self::$_initDone) self::_Init();

    $this->_osm_path  = 'includes/javascript/osm';
    // Supported filetypes by each player:
    $this->_osm_ext   = array(/*'ogv',*/ 'oga', 'flv', '3g2', /*'mp3',*/ 'm4a', 'aac', 'wav', 'aif', 'wma');
    $this->_jp_ext    = array('mp3','m4a','m4v','oga','ogv','wav'/*,'webm'*/);
    $this->_vjs_ext   = array('webm');
    $this->_jm_ext    = array('avi','mp4','mpg','mpeg','mov','ram','swf','wmv','3g2','au','aac','aif','gsm','midi','rm','wma','xaml');
    $this->_video_supported_ext = array_unique(array_merge($this->_osm_ext, $this->_jp_ext, $this->_vjs_ext, $this->_jm_ext));
    natsort($this->_video_supported_ext);

    $this->_settings  = $_params;

    $this->_audio_ext = array('au','aac','aif','gsm','midi','mp3','m4a','oga','ogg','rm','wma');
    $this->_image_ext = array('bmp','gif','jpg','jpeg','png');
    $this->_media_ext = array('avi','f4v','flv','m4a','mp3','mp4','m4a','m4v','mov','mpg','mpeg','ogg','oga','ogv','swf','webm','wmv','wma','3gp','3g2','aac','rm','midi','au','aif','gsm');
    $this->_all_ext = array_unique(array_merge($this->_audio_ext, $this->_image_ext, $this->_media_ext));
    natsort($this->_all_ext);

    // Public data
    $this->osm_installed = file_exists(ROOT_PATH . $this->_osm_path.'/OSMPlayer.php');
    $this->base_url = SITE_URL;

  } //SD_Media

  private static function _Init()
  {
    self::$_initDone = true;
  }

  public static function FixPath($fullpath, $addrootpath=false)
  {
    if(!self::$_initDone) self::_Init();

    // Workaround for WAMP environments: usage of PHP function "filesize()"
    // won't work with mixed slashes/backslashes in path (PHP 5.1.x):
    $fullpath = str_replace('\\', '/', $fullpath);
    $fullpath = str_replace('//', '/', $fullpath);

    // Ensure trailing backslash
    $fullpath .= (substr($fullpath,-1)=='/' ? '' : '/');

    /*
    IF a relative path is specified (not starting with "/"), treat the path
    as being relative to the "ROOT_PATH".
    However, for Win* platform (like XAMPP), do not use ROOT_PATH if a colon ":"
    is found, which indicates a drive letter == absolute path.
    */
    if(!empty($addrootpath) &&
       (!parent::IsWin() && (substr($fullpath,0,1) != '/') ||
       (strpos($fullpath,':') === false)))
    {
      $fullpath = ROOT_PATH . $fullpath;
    }

    return $fullpath;
  }

  // ##########################################################################
  // IsMediaSupported
  // ##########################################################################
  public function IsMediaSupported($file_path, $file_name)
  {
    if(!strlen($file_name) || empty($file_path))
    {
      self::$errorCode = SD_MEDIA_ERR_FILENOTFOUND;
      return false;
    }
    $file_path = self::FixPath($file_path);
    if(substr($file_path,0,4)!=='http')
    {
      if(!file_exists(ROOT_PATH.$file_path.$file_name))
      {
        self::$errorCode = SD_MEDIA_ERR_FILENOTFOUND; // file not found
        return false;
      }
    }

    if(!$this->extention = parent::getExtention($file_name, $this->_media_ext))
    {
      self::$errorCode = SD_MEDIA_ERR_WRONG_EXT; // file extention error
      return false;
    }

    $this->player_jp  = in_array($this->extention, $this->_jp_ext);
    $this->player_jm  = in_array($this->extention, $this->_jm_ext);
    $this->player_vjs = in_array($this->extention, $this->_vjs_ext);
    $this->player_osm = $this->osm_installed && in_array($this->extention, $this->_osm_ext);
    $this->is_audio   = in_array($this->extention, $this->_audio_ext);

    if(!$this->player_jp && ! $this->player_jm && !$this->player_vjs && !$this->player_osm && !$this->is_audio)
    {
      self::$errorCode = SD_MEDIA_ERR_UNSUPPORTED; // unknown or unsupported media type
      return false;
    }
    return true;

  } //IsMediaSupported


  // ##########################################################################
  // AddCodeToGlobalHeader
  // ##########################################################################
  public function AddCodeToGlobalHeader($file_path, $file_name)
  {
    if(!$this->IsMediaSupported($file_path, $file_name)) return false;

    $header_css = $header_js = array();
    $header_other = '';

    if($this->player_jp)
    {
      $header_css[] = SD_INCLUDE_PATH.'css/jplayer.blue.monday.css';
      $header_js[]  = ROOT_PATH . MINIFY_PREFIX_F. 'includes/javascript/jquery.jplayer.js';
    }
    if($this->player_vjs)
    {
      #$header_css[] = SD_INCLUDE_PATH.'css/video-js.css'; #OLD!
      #$header_css[] = SD_INCLUDE_PATH.'css/tube.css'; #OLD!
      #$header_js[]  = ROOT_PATH . MINIFY_PREFIX_F. 'includes/javascript/video.js'; #OLD!
      $header_css[] = SD_INCLUDE_PATH.'css/video-js.min.css';
      $header_js[]  = ROOT_PATH . MINIFY_PREFIX_F. 'includes/javascript/video-min.js';
    }
    if($this->player_jm)
    {
      $header_js[] = ROOT_PATH . MINIFY_PREFIX_F. 'includes/javascript/jquery.media.js';
    }

    if($this->player_osm && file_exists(ROOT_PATH.'includes/javascript/osm/OSMPlayer.php'))
    {
      include_once(SD_INCLUDE_PATH.'javascript/osm/OSMPlayer.php');
      $osmp = new OSMPlayer(array(
                  'disablePlaylist' => true,
                  'theme' => 'dark-hive',
                  'playerPath' => SD_JS_PATH.'osm',
                  'prefix' => 'dlm_',
                  'template' => 'simpleblack'
      ));
      $header_other = $osmp->getHeader();
      unset($osmp);
    }

    sd_header_add(array(
      'css'   => $header_css,
      'js'    => $header_js,
      'other' => array($header_other)));

    return true;

  } //AddCodeToGlobalHeader


  // ##########################################################################
  // GetEmbedCode
  // ##########################################################################
  public function GetEmbedCode($file_path, $file_name, $js_ready_code = '')
  {
    if(!$this->IsMediaSupported($file_path, $file_name)) return '';

    // Set some defaults
    $this->mediafile_url = $this->base_url . $file_path . $file_name;
    if(isset($this->media_poster) && strlen($this->media_poster))
    {
      $this->media_poster = str_replace(' ','%20',$this->media_poster);
    }

    // ####################################################
    // ##################### OSM Player ###################
    // ####################################################
    if($this->player_osm)
    {
      $this->osm_url   = $this->base_url . $this->_osm_path;
      $this->flash_url = $this->osm_url  . '/flash/mediafront.swf';

      include_once(SD_INCLUDE_PATH.'javascript/osm/OSMPlayer.php');
      if(class_exists('OSMPlayer'))
      {
        $osm_player = new OSMPlayer(array(
          'autostart' => $this->embed_autoplay,
          'width' => $this->embed_width,
          'height' => $this->embed_height,
          'baseURL' => $this->osm_url,
          'disableplaylist' => true,
          'disableembed' => true,
          'disablemenu' => true,
          'flashPlayer' => $this->flash_url,
          'fluidHeight' => false,
          'fluidWidth' => false,
          'playerPath' => $this->_osm_path,
          'playerURL' => $this->osm_url,
          'embedWidth' => ($this->embed_width-20),
          'embedHeight' => ($this->embed_height-20),
          'file' => $this->mediafile_url,
          'link' => $this->extralink,
          'id' => $this->media_element_id,
          'logo' => '',
          'image' => $this->media_poster,
          'link' => '',
          'prefix' => 'media_',
          'resizable' => true,
          'showPlaylist' => false,
          'showInfo' => false,
          'theme' => 'dark-hive',
          'template' => 'simpleblack'
        ));
        $embed = $osm_player->getPlayer();
        if(strlen($js_ready_code))
        {
          $embed .= '
  <script type="text/javascript">
  // <![CDATA[
  jQuery(document).ready(function() {
    '.$js_ready_code.'
    //jQuery("#mediafront_information").remove();
  });
  // ]]>
  </script>
      ';
        }
      }
      return $embed;
    } //OSM


    // ####################################################
    // ***** jMedia jQuery Plugin
    // ####################################################
    if($this->player_jm)
    {
      $embed = '
  <a class="mediafile" href="'.$this->mediafile_url.'"></a>
  <script type="text/javascript">
  // <![CDATA[
  jQuery(document).ready(function() {
    '.$js_ready_code.'
    jQuery.fn.media.defaults.flvPlayer = "'.$this->base_url.'includes/javascript/moxieplayer.swf"; //mediaplayer
    jQuery.fn.media.defaults.mp3Player = "'.$this->base_url.'includes/javascript/moxieplayer.swf";
    jQuery("a.mediafile").media({
      width:     '.(int)$this->embed_width.',
      height:    '.(int)$this->embed_height.',
      autoplay:  '.($this->embed_autoplay ? 'true' : 'false').',
      params:    { "allowFullScreen": "true", "allowscriptaccess": "always" },
      bgColor:   "'.$this->option_bgcolor.'"
    });
  });
  // ]]>
  </script>
  ';
      return $embed;
    }

    // ####################################################
    // ***** VideoJS
    // ####################################################
    if($this->player_vjs)
    {
      // Video.js - http://videojs.com/
      //$flash_url = 'http://releases.flowplayer.org/swf/flowplayer-3.2.7.swf';
      //$flash_url = $this->base_url.'includes/javascript/moxieplayer.swf';
      $this->flash_url = $this->base_url.'includes/javascript/flowplayer-3.2.7.swf';
      $ftype = 'video/'.$this->extention; //??? mime type???
      switch($this->extention) {
        case 'webm': $ftype = $ftype.'; codecs="vp8, vorbis"'; break;
        case 'ogv' : $ftype = $ftype.'; codecs="theora, vorbis"'; break;
        case 'mp4' : $ftype = $ftype.'; codecs="avc1.42E01E, mp4a.40.2"'; break;
      }
      //for FlowPlayer:
      $flashvars = '<param name="flashvars" value=\'config={"clip":[{"metaData":false,"urlEncoding":true}],"playlist":['.
                    ($this->media_poster?'"'.$this->media_poster.'", ':'').
                    '{"url": "'.urlencode($this->mediafile_url).'","autoPlay":false,"autoBuffering":true}]}\' />';

      return "
  <div class='video-js-box'>
  <video class=\"video-js\" ".($this->embed_autoplay?'autoplay ':'')."controls=\"controls\" $this->media_poster width=\"$this->embed_width\" height=\"$this->embed_height\">
    <source src=\"$this->mediafile_url\" type=\"$ftype\" />
    <object class=\"vjs-flash-fallback\" data=\"$this->flash_url\" width=\"$this->embed_width\" height=\"$this->embed_height\" type=\"application/x-shockwave-flash\">
      <param name=\"autoPlay\" value=\"".($this->embed_autoplay?'true':'false')."\",
      <param name=\"movie\" value=\"$this->flash_url\" />
      <param name=\"quality\" value=\"high\" />
      <param name=\"allowFullScreen\" value=\"true\" />
      <param name=\"allowscriptaccess\" value=\"always\" />
      <param name=\"wmode\" value=\"transparent\" />
      ".$flashvars."
      <img alt=\"Poster Image\" src=\"$this->media_poster\" width=\"$this->embed_width\" height=\"$this->embed_height\" title=\"No video playback capabilities, please download the video below.\" />
    </object>
  </video>
  <p class='vjs-no-video'><strong>Download video:</strong> <a href=\"$this->mediafile_url\">$this->extention format</a></p>
  </div>
  <script type=\"text/javascript\">
  // <![CDATA[
  jQuery(document).ready(function() {
    ".$js_ready_code."
    VideoJS.setupAllWhenReady();
  });
  // ]]>
  </script>";
    }

    // ####################################################
    // ############### jPlayer jQuery Plugin ##############
    // ####################################################
    /* JPlayer supported file types:
       HTML5: mp3, m4a (AAC), m4v (H.264), ogv*, oga*, wav*, webm*
       Flash: mp3, m4a (AAC), m4v (H.264)
    */
    if($this->player_jp)
    {
      $this->mediafile_url = $this->extention . ": '$this->mediafile_url', ";
      $playerid = 'media';

      $embed = $this->is_audio ? $this->GetJPlayerAudioTemplate() : $this->GetJPlayerVideoTemplate();

      $embed = str_replace(array('%player_id%',           '%audio_class%', '%video_class%'),
                           array($this->media_element_id, 'jp-player',     'jp-video-270p'), $embed);

      if($this->is_audio)
      {
        $embed .= '<style type="text/css">.jp-player { height: '.$this->embed_height.'px }</style>';
      }
      $embed .= '
  <script type="text/javascript">
  // <![CDATA[
  jQuery(document).ready(function() {
    '.$js_ready_code.'
    jQuery("#'.$this->media_element_id.'").jPlayer({
      swfPath: "'.$this->base_url.'includes/javascript",
      solution: "flash,html", supplied: "'.$this->extention.'",
      cssSelectorAncestor: "",
      errorAlerts: false, warningAlerts: false,
      ready: function(){
        jQuery(this).jPlayer("setMedia", {
          '.$this->mediafile_url.'
        });
      }
    });
  });
  // ]]>
  </script>
  ';
      return $embed;
    }

    self::$errorCode = SD_MEDIA_ERR_UNSUPPORTED; // unknown or unsupported media type
    return false;

  } //GetEmbedCode

  // ##########################################################################

  public function GetJPlayerVideoTemplate()
  {
    return '
  <div class="jp-video %video_class%">
    <div class="jp-type-single">
      <div id="%player_id%" class="jp-jplayer"></div>
      <div class="jp-gui">
        <div class="jp-video-play">
          <a href="javascript:;" class="jp-video-play-icon" tabindex="1">play</a>
        </div>
        <div class="jp-interface">
          <div class="jp-progress">
            <div class="jp-seek-bar">
              <div class="jp-play-bar"></div>
            </div>
          </div>
          <div class="jp-current-time"></div>
          <div class="jp-duration"></div>
          <div class="jp-controls-holder">
            <ul class="jp-controls">
              <li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
              <li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
              <li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
              <li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
              <li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>
              <li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>
            </ul>
            <div class="jp-volume-bar">
              <div class="jp-volume-bar-value"></div>
            </div>
            <ul class="jp-toggles">
              <li><a href="javascript:;" class="jp-full-screen" tabindex="1" title="full screen">full screen</a></li>
              <li><a href="javascript:;" class="jp-restore-screen" tabindex="1" title="restore screen">restore screen</a></li>
              <li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">repeat</a></li>
              <li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat off</a></li>
            </ul>
          </div>
        </div>
      </div>
      <div class="jp-no-solution">
        <span>Update Required</span>
        To play the media you will need to either update your browser to a recent version
        or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
      </div>
    </div>
  </div>';

  } //GetJPlayerVideoTemplate

  // ##########################################################################

  public function GetJPlayerAudioTemplate()
  {
    return '
  <div id="%player_id%" class="%audio_class%"></div>
  <div class="jp-audio">
    <div class="jp-type-single">
      <div class="jp-gui jp-interface">
        <ul class="jp-controls">
          <li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
          <li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
          <li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
          <li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
          <li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>
          <li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>
        </ul>
        <div class="jp-progress">
          <div class="jp-seek-bar">
            <div class="jp-play-bar"></div>
          </div>
        </div>
        <div class="jp-volume-bar">
          <div class="jp-volume-bar-value"></div>
        </div>
        <div class="jp-time-holder">
          <div class="jp-current-time"></div>
          <div class="jp-duration"></div>

          <ul class="jp-toggles">
            <li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">repeat</a></li>
            <li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat off</a></li>
          </ul>
        </div>
      </div>
      <div class="jp-no-solution">
        <span>Update Required</span>
        To play the media you will need to either update your browser to a recent version
        or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
      </div>
    </div>
  </div>';

  } //GetJPlayerAudioTemplate


  // ##########################################################################
  // GetID3Info
  // ##########################################################################

  public function GetID3Info($filename, $createTable=true, $doOutput=false)
  {
    $this->playtime = '';

    // include getID3() library (optional!)
    if(!file_exists(SD_INCLUDE_PATH.'getid3/getid3.php')) return false;
    @include_once(SD_INCLUDE_PATH.'getid3/getid3.php');
    if(!class_exists('getID3')) return false;

    // Initialize getID3 engine, disabling several options to speed-up:
    $getID3 = new getID3;
    $getID3->option_tag_apetag  = false;
    $getID3->option_tag_id3v1   = false;
    $getID3->option_tag_id3v2   = false;
    $getID3->option_tag_lyrics3 = false;
    $getID3->option_md5_data    = false;
    $getID3->option_sha1_data   = false;

    // Analyze file and store returned data in $ThisFileInfo
    $ThisFileInfo = $getID3->analyze($filename);

    // Output desired information in whatever format you want
    // Note: all entries in [comments] or [tags] are arrays of strings
    // See structure.txt for information on what information is available where
    // or check out the output of /demos/demo.browse.php for a particular file
    // to see the full detail of what information is returned where in the array
    if(empty($ThisFileInfo) || !empty($ThisFileInfo['error'])) return false;

    if(isset($ThisFileInfo['playtime_string']))
    {
      $this->playtime = $ThisFileInfo['playtime_string'];
    }

    if(!empty($doOutput))
    {
      if(!empty($createTable))
      {
        echo '
    <table border="0" cellpadding="2" cellspacing="0" width="100%">';
      }
      echo '
    <tr><td class="td1" colspan="2">Media Info</td></tr>';

    if(isset($ThisFileInfo['filesize']))
    {
      echo '
      <tr><td class="td2" width="150">'.AdminPhrase('media_filesize').'</td><td class="td3">'.DisplayReadableFilesize(@$ThisFileInfo['filesize']).'</td></tr>';
    }
    if(isset($ThisFileInfo['comments_html']['artist'][0]))
    {
      // artist from any/all available tag formats
      echo '
      <tr><td class="td2" width="150">Artist:</td><td class="td3">' . @$ThisFileInfo['comments_html']['artist'][0].'</td></tr>';
    }
    if(isset($ThisFileInfo['tags']['id3v2']['title'][0]))
    {
      // title from ID3v2
      echo '<tr><td class="td2" width="150">Title:</td><td class="td3">' . @$ThisFileInfo['tags']['id3v2']['title'][0].'</td></tr>';
    }
    if(isset($ThisFileInfo['audio']['bitrate']))
    {
      // audio bitrate
      echo '<tr><td class="td2" width="150">Bitrate:</td><td class="td3">' . @$ThisFileInfo['audio']['bitrate'].'</td></tr>';
    }
    if(isset($ThisFileInfo['playtime_string']))
    {
      // playtime in minutes:seconds, formatted string
      echo '<tr><td class="td2" width="150">Playtime:</td><td class="td3">' . @$ThisFileInfo['playtime_string'].'</td></tr>';
    }
    $ThisFileInfo['width'] = $ThisFileInfo['height'] = $width = $height = 0;
    if(isset($ThisFileInfo['video']))
    {
      if(isset($ThisFileInfo['video']['dataformat']))
      {
        echo '<tr><td class="td2" width="120">Format:</td><td class="td3">' . @$ThisFileInfo['video']['dataformat'].'</td></tr>';
      }
      // Try to get dimensions first from meta data:
      if(!empty($ThisFileInfo['meta']['onMetaData']['width']))
      {
        $width = @$ThisFileInfo['meta']['onMetaData']['width'];
        $height = @$ThisFileInfo['meta']['onMetaData']['height'];
      }
      else
      if(isset($ThisFileInfo['video']['resolution_x']))
      {
        $ThisFileInfo['width'] = $width = $ThisFileInfo['video']['resolution_x'];
        $ThisFileInfo['height'] = $height = $ThisFileInfo['video']['resolution_y'];
      }
      echo '<tr><td class="td2" width="120">Dimensions:</td><td class="td3">' . $width.' x '. $height.' Pixels</td></tr>';
    }
    if(isset($ThisFileInfo['mime_type']))
    {
      // playtime in minutes:seconds, formatted string
      echo '<tr><td class="td2" width="150">MIME-Type:</td><td class="td3">' . @$ThisFileInfo['mime_type'].'</td></tr>';
    }
      if(!empty($createTable))
      {
        echo '
    </table';
      }
    // if you want to see ALL the output, uncomment this line:
    //echo '<pre style="font-family: courier;">'.htmlentities(print_r($ThisFileInfo, true)).'</pre>';
    }
    return $ThisFileInfo;

  } //GetID3Info

} // end of SD_Media class
} //DO NOT REMOVE
