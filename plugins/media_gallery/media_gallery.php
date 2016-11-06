<?php
// ############################################################################
// SD360: support ajax'ed operations (e.g. subsections pagination)
// ############################################################################
$standalone = false;
if(!defined('ROOT_PATH'))
{
  $standalone = true;
  // Assume regular admin menu request, normal bootstrap required:
  define('IN_PRGM', true);
  define('ROOT_PATH', '../../');
  require(ROOT_PATH . 'includes/init.php');
  if(!Is_Ajax_Request())
  {
    exit('No access');
  }
}

if(!defined('IN_PRGM')) return false;

// ########################## Media Gallery SD3 ###############################

if($plugin_folder = sd_GetCurrentFolder(__FILE__))
{
  // INCLUDE CORE SD MEDIA CLASS
  require_once(SD_INCLUDE_PATH.'class_sd_media.php');

  // INCLUDE MEDIA GALLERY CLASSES
  $path = ROOT_PATH.'plugins/'.$plugin_folder.'/';
  require_once($path.'gallery_lib.php');
  require_once($path.'class_gallery.php');

  if(Is_Ajax_Request())
  {
    if(!headers_sent()) header('Content-Type: text/html; charset='.SD_CHARSET);
    $action = GetVar('action', '', 'string');
    if(($action=='getsections') || ($action=='check_media_url'))
    {
      $MG = new MediaGalleryClass($plugin_folder);
      switch($action)
      {
        case 'getsections':
          $MG->DisplaySubsections();
          break;
        case 'check_media_url': //SD362
          $base = $MG->GetBase();
          if(CheckFormToken('', false) && $base->AllowSubmit &&
             ($base->pluginid == GetVar('pluginid', 0, 'whole_number', true, false)) &&
             ($url = GetVar('media_url', '', 'html', true, false)))
          {
            $result = $base->CheckAllowedMediaType($url);
            echo ($result ? 'media_ok' : 'media_error');
          }
          else
          {
            echo 'media_error';
          }
          break;
      }
      unset($MG);
    }
    $DB->close();
    exit();
  }

  $MG = new MediaGalleryClass($plugin_folder);
  $MG->DisplayContent();

  unset($MG,$path,$plugin_folder);
}
