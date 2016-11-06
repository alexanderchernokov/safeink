<?php
// #############################################################
// v2.1.1: Ajax pre-run
// - added admin instant comments, status change of events
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
    exit(AdminPhrase('common_page_access_denied'));
  }
}


if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

if(!$plugin_folder = sd_GetCurrentFolder(__FILE__))
{
  return false;
}

require(ROOT_PATH . 'plugins/' .  $plugin_folder . '/class_eventmanager.php');
$EventMgr = new EventManagerClass($plugin_folder);
$load_wysiwyg = 0;

if(empty($EventMgr->table)) return false;


function EM_ajaxCheckEventUpdate($action='')
{
  if(!CheckFormToken()) return false;

  global $DB, $sdlanguage, $plugin_names, $EventMgr;

  if(!headers_sent()) header('Content-Type: text/html; charset='.SD_CHARSET);

  $pluginid = Is_Valid_Number(GetVar('pluginid', null, 'whole_number'),-1,18);
  $eventid  = Is_Valid_Number(GetVar('eventid',  null, 'whole_number'),-1,1);
  $newvalue = Is_Valid_Number(GetVar('newvalue', null, 'natural_number'),-1,0,1);

  //Sanity checks:
  if( ($eventid < 1) || ($newvalue < 0) || ($pluginid < 18) ||
      ($pluginid != $EventMgr->pluginid) || !isset($plugin_names[$pluginid]) ||
      !in_array($action, array('ea','ec')) )
  {
    $DB->close();
    exit('ERROR: '.AdminPhrase('common_page_access_denied'));
  }

  $column = ($action=='ea') ? 'activated' : 'allowcomments';

  $DB->result_type = MYSQL_ASSOC;
  if($row = $DB->query_first('SELECT eventid, '.$column.
                             ' FROM '.$EventMgr->table.
                             ' WHERE eventid = %d', $eventid))
  {
    if($row[$column] != $newvalue)
    $DB->query('UPDATE '.$EventMgr->table.
               " SET $column = $newvalue WHERE eventid = $eventid");
    echo $EventMgr->language['message_event_updated'];
  }
  else
  {
    if(!headers_sent()) header("HTTP/1.0 404 Not Found");
    echo 'ERROR: Invalid reference!';
  }

  $DB->close();
  exit;

} //EM_ajaxCheckEventUpdate


// ############################################################################
// GET ACTION
// ############################################################################
$action = GetVar('action', 'displayevents', 'string');
$eventid = GetVar($EventMgr->pref.'eventid', 0, 'natural_number');


// *** v2.1.1: process known Ajax request(s) ***
if(Is_Ajax_Request())
{
  switch($action)
  {
    case 'ea':
    case 'ec': EM_ajaxCheckEventUpdate($action); break; //v2.1.1
    default:
      //anything else is an error!
      if(!headers_sent()) header("HTTP/1.0 404 Not Found");
      echo '0';
  }
  $DB->close();
  exit();
}

require_once(ROOT_PATH.'includes/class_sd_media.php');

// ############################## INSERT EVENT ################################

function SaveEvent($action, $eventid)
{
  global $DB, $refreshpage, $sdlanguage, $EventMgr;

  // Security check
  if(!CheckFormToken(SD_TOKEN_NAME, false))
  {
    DisplayMessage($sdlanguage['error_invalid_token'], true);
    return false;
  }

  $EventMgr->FetchEventBuffer();

  $date = EventManagerClass::FormatDate($EventMgr->event['day'],  $EventMgr->event['month'], $EventMgr->event['year'],
                                        $EventMgr->event['hour'], $EventMgr->event['minute']);

  if($action=='insertevent')
  {
    $DB->query('INSERT INTO '.$EventMgr->table.
               ' (activated,allowcomments,title,description,date,venue,street,city,state,country)'.
               " VALUES (%d, %d, '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s')",
                $EventMgr->event['activated'], $EventMgr->event['allowcomments'],
                $EventMgr->event['title'], $EventMgr->event['description'], $date,
                $EventMgr->event['venue'], $EventMgr->event['street'], $EventMgr->event['city'],
                $EventMgr->event['state'], $EventMgr->event['country']);
    $eventid = $DB->insert_id();
  }
  else
  if(!empty($eventid) && ($eventid > 0))
  {
    $deleteevent = GetVar('deleteevent', false, 'bool', true, false);
    if($deleteevent && ($action=='updateevent'))
    {
      $result = DeleteEvent($eventid);
      sd_GrowlMessage(AdminPhrase($result?'msg_event_deleted':'err_deletion_failed'));
      DisplayDefault();
      return;
    }

    $DB->query('UPDATE '.$EventMgr->table.
        " SET activated = %d, allowcomments = %d,
          title = '%s', description = '%s', date = %d, venue = '%s',
          street = '%s', city = '%s', state = '%s', country = '%s'
      WHERE eventid = %d",
      $EventMgr->event['activated'], $EventMgr->event['allowcomments'],
      $EventMgr->event['title'], $EventMgr->event['description'], $date,
      $EventMgr->event['venue'], $EventMgr->event['street'], $EventMgr->event['city'],
      $EventMgr->event['state'], $EventMgr->event['country'], $eventid);
  }

  if(empty($eventid))
  {
    DisplayMessage('ERROR!',true);
    return false;
  }

  // ###################################
  // v2.1.0: Image/Thumbnail processing
  // ###################################
  $image        = isset($_FILES['image'])?$_FILES['image']:null;
  $thumbnail    = isset($_FILES['thumbnail'])?$_FILES['thumbnail']:null;
  $deleteimage  = GetVar('deleteimage', false, 'bool', true, false);
  $pl_image     = GetVar($EventMgr->pref.'image', array(), 'array', true, false);
  $pl_thumbfile = GetVar($EventMgr->pref.'thumbnail', array(), 'array', true, false);

  $thumbname = '';
  $imagename = '';
  $filename = '';
  $cachepath = ROOT_PATH.'cache/';

  $img = new SD_Image();

  // Check if a thumbnail was uploaded by either plupload OR regular file input
  $pluploader = false;
  foreach($pl_thumbfile AS $temp_fn => $logical_fn)
  {
    $tmp = $cachepath.$temp_fn;
    if(strlen($logical_fn) && strlen($temp_fn) && file_exists($tmp))
    {
      if($filesize = @filesize($tmp))
      {
        $pluploader = true;
        $thumbname  = $temp_fn;
        $filename   = $logical_fn;
      }
    }
  }
  $thumb_uploaded = false;
  if($pluploader || (isset($thumbnail) && is_array($thumbnail) && !empty($thumbnail['name'])))
  {
    if($pluploader)
    {
      $filepath  = $cachepath.$thumbname;
      $filetype  = SD_Image_Helper::GetMimeTypeFromFilename($filename);
      $extention = SD_Media_Base::getExtention($filename, SD_Media_Base::$known_type_ext);
      $thumbname = $EventMgr->TB_PREFIX.$eventid.'.'.$extention;
      if($thumb_uploaded = @copy($filepath, ROOT_PATH.$EventMgr->image_folder.$thumbname))
      {
        $thumb_uploaded = SD_Media_Base::ImageSecurityCheck(true, ROOT_PATH.$EventMgr->image_folder,$thumbname);
      }
      @unlink($filepath);
    }
    else
    if(!empty($thumbnail['tmp_name']) && is_uploaded_file($thumbnail['tmp_name']))
    {
      $extention = SD_Media_Base::getExtention($thumbnail['name'], SD_Media_Base::$known_type_ext);
      $thumbname = $EventMgr->TB_PREFIX.$eventid.'.'.$extention;
      if($thumb_uploaded = @move_uploaded_file($thumbnail['tmp_name'],
                                               ROOT_PATH.$EventMgr->image_folder.$thumbname))
      {
        $thumb_uploaded = SD_Media_Base::ImageSecurityCheck(true, ROOT_PATH.$EventMgr->image_folder,$thumbname);
      }
    }

    if($thumb_uploaded)
    {
      $DB->query('UPDATE '.$EventMgr->table." SET thumbnail = '%s' WHERE eventid = %d",
                 $thumbname, $eventid);
      @chmod(ROOT_PATH.$EventMgr->image_folder.$thumbname, intval('0644', 8));
    }
    else
    {
      $errors[] = 'Thumbnail file could not be copied on server!';
    }
  }

  // Check if an image was uploaded by either plupload OR regular file input
  $pluploader = false;
  foreach($pl_image AS $temp_fn => $logical_fn)
  {
    $tmp = $cachepath.$temp_fn;
    if(strlen($logical_fn) && strlen($temp_fn) && file_exists($tmp))
    {
      if($filesize = @filesize($tmp))
      {
        $pluploader = true;
        $thumbname  = $temp_fn;
        $filename   = $logical_fn;
      }
    }
  }
  $image_uploaded = false;
  if($pluploader || (isset($image) && is_array($image) && !empty($image['name'])))
  {
    if($pluploader)
    {
      $filepath  = $cachepath.$thumbname;
      $filetype  = SD_Image_Helper::GetMimeTypeFromFilename($filename);
      $extention = SD_Media_Base::getExtention($filename, SD_Media_Base::$known_type_ext);
      $thumbname = $eventid.'.'.$extention;
      if($image_uploaded = @copy($filepath, ROOT_PATH.$EventMgr->image_folder.$thumbname))
      {
        $image_uploaded = SD_Media_Base::ImageSecurityCheck(true,ROOT_PATH.$EventMgr->image_folder,$thumbname);
      }
      @unlink($filepath);
    }
    else
    if(!empty($image['tmp_name']) && is_uploaded_file($image['tmp_name']))
    {
      $extention = SD_Media_Base::getExtention($image['name'], SD_Media_Base::$known_type_ext);
      $thumbname = $eventid.'.'.$extention;
      if($image_uploaded = @move_uploaded_file($image['tmp_name'], ROOT_PATH.$EventMgr->image_folder.$thumbname))
      {
        $image_uploaded = SD_Media_Base::ImageSecurityCheck(true,ROOT_PATH.$EventMgr->image_folder,$thumbname);
      }
    }

    if($image_uploaded)
    {
      $DB->query('UPDATE '.$EventMgr->table." SET image = '%s' WHERE eventid = %d",
                 $thumbname, $eventid);
      @chmod(ROOT_PATH.$EventMgr->image_folder.$thumbname, intval('0644', 8));
    }
    else
    {
      $errors[] = 'Image file could not be copied on server!';
    }
  }
  unset($img);

  if(!$image_uploaded && $deleteimage)
  {
    $row = $DB->query_first('SELECT image FROM '.$EventMgr->table.
                            ' WHERE eventid = %d', $eventid);
    if(!empty($row['image']))
    {
      $tmp = ROOT_PATH . $EventMgr->image_folder . $row['image'];
      if(is_file($tmp))
      {
        @unlink($tmp);
      }
      $DB->query('UPDATE '.$EventMgr->table." SET image = '' WHERE eventid = %d", $eventid);
    }
  }

  // Create thumbnail from image OR delete thumbnail?
  if(!$thumb_uploaded)
  {
    if(!empty($_POST['createthumbnail']))
    {
      if(!$image_uploaded)
      {
        $row = $DB->query_first('SELECT image FROM '.$EventMgr->table.
                                ' WHERE eventid = %d',$eventid);
        $imagename = $row['image'];
      }
      else
      {
        $imagename = $thumbname;
      }
      if(!empty($imagename) &&
         file_exists(ROOT_PATH.$EventMgr->image_folder.$imagename))
      {
        $thumbname = $EventMgr->TB_PREFIX.$imagename;
        $maxwidth = Is_Valid_Number($EventMgr->settings['max_thumbnail_width'],100,10,1000);
        $maxheight = Is_Valid_Number($EventMgr->settings['max_thumbnail_width'],100,10,1000);
        $err = CreateThumbnail(ROOT_PATH.$EventMgr->image_folder.$imagename,
                               ROOT_PATH.$EventMgr->image_folder.$thumbname,
                               $maxwidth, $maxheight);
        if(isset($err))
        {
          DisplayMessage($errormsg, true);
        }
        else
        {
          $DB->query('UPDATE '.$EventMgr->table." SET thumbnail = '%s' WHERE eventid = %d",
                     $thumbname, $eventid);
        }
      }
    }
    else
    if(!empty($_POST['deletethumbnail']))
    {
      $row = $DB->query_first('SELECT thumbnail FROM '.$EventMgr->table.
                              ' WHERE eventid = %d',$eventid);
      if(!empty($row['thumbnail']))
      {
        $thumbname = ROOT_PATH . $EventMgr->image_folder . $row['thumbnail'];
        if(is_file($thumbname))
        {
          @unlink($thumbname);
        }
        $DB->query('UPDATE '.$EventMgr->table." SET thumbnail = '' WHERE eventid = %d",$eventid);
      }
    }
  }


  PrintRedirect($refreshpage.'&amp;action=displayevent&amp;'.
                $EventMgr->pref.'eventid='.$eventid,
                1, AdminPhrase('msg_event_updated'));

} //SaveEvent


function DeleteEventImage($eventid, $isThumb=true, $deleteFile=true)
{
  //v2.1.1, added 2013-09-10
  global $DB, $EventMgr, $userinfo;

  $result = AdminPhrase('common_page_access_denied');
  // If not allowed, check for author mode and if current user is author
  if(!$EventMgr->HasAdminRights || empty($userinfo['loggedin']) ||
     empty($eventid) || !is_numeric($eventid) || ($eventid < 1))
  {
    return $result;
  }

  $column = empty($isThumb)?'image':'thumbnail';

  $DB->result_type = MYSQL_ASSOC;
  $event_arr = $DB->query_first('SELECT eventid, thumbnail, image'.
                                ' FROM {p'.$EventMgr->pluginid.'_events}'.
                                ' WHERE eventid = '.(int)$eventid);
  if(empty($event_arr['eventid']))
  {
    return $result;
  }
  if(empty($event_arr[$column])) #no file name present?
  {
    return true;
  }

  if(!empty($deleteFile))
  {
    // filename sanity check
    $file = @preg_replace('#[^a-zA-Z0-9_\-\.]+#m', '', $event_arr[$column]);
    if(($file != $event_arr[$column]) || (strpos($file,'..')!==false))
    {
      return $result;
    }

    $folder = ROOT_PATH.$EventMgr->image_folder;
    $thumbpath = $folder.$file;
    if(is_file($thumbpath) && file_exists($thumbpath))
    {
      @unlink($thumbpath);
    }
  }

  // clear image/thumb name from event row:
  $DB->query('UPDATE '.$EventMgr->table." SET $column = ''".
             ' WHERE eventid = '.(int)$eventid);

  return true;

} //DeleteEventImage

// ############################## DELETE EVENT ################################

function DeleteEvent($eventid, $dorefresh=false)
{
  global $DB, $refreshpage, $EventMgr;

  $result = false;
  if(!empty($eventid) && is_numeric($eventid) && ($eventid > 0) &&
    ($DB->query_first('SELECT 1 FROM '.$EventMgr->table.' WHERE eventid = %d',$eventid)))
  {
    // delete any comments
    DeletePluginComments($EventMgr->pluginid, $eventid);

    // remove article ratings
    DeletePluginRatings($EventMgr->pluginid, 'p'.$EventMgr->pluginid.'-'.(int)$eventid);

    // remove image/thumb for event
    DeleteEventImage($eventid, false, true);
    DeleteEventImage($eventid, true, true);

    // delete event (as LAST action!)
    $DB->query('DELETE FROM '.$EventMgr->table.' WHERE eventid = %d',$eventid);

    $result = true;
  }

  if($dorefresh)
  {
    PrintRedirect($refreshpage, 3, AdminPhrase($result?'msg_event_deleted':'err_deletion_failed'));
  }
  return !empty($result);

} //DeleteEvent


// ############################## DELETE EVENTS ###############################

function DeleteEvents()
{
  global $DB, $refreshpage;

  $page = GetVar('page', 1, 'whole_number');
  $pagesize = GetVar('pagesize', 15, 'string');
  if($pagesize!='all')
  $pagesize = Is_Valid_Number($pagesize, 15, 15, 100);
  $sorttype = GetVar('sorttype', 'datez', 'string');
  $deleteeventid = GetVar('deleteeventid', array(), 'array', true, false);

  $count = $errors = 0;
  if(!empty($deleteeventid) && is_array($deleteeventid))
  {
    // delete events (if user selected to delete any)
    $cnt = count($deleteeventid);
    for($i = 0; $i < $cnt; $i++)
    {
      if(is_numeric($deleteeventid[$i]) && ((int)$deleteeventid[$i] > 0))
      {
        if(!DeleteEvent($deleteeventid[$i])) $errors++;
        $count++;
      }
      else
      {
        $errors++;
        break;
      }
    }
  }

  if($errors)
  {
    DisplayMessage(AdminPhrase('err_deletion_failed').' ('.$errors.')', true);
  }
  else
  {
    if($count)
      sd_GrowlMessage(AdminPhrase('msg_events_deleted').' ('.$count.')');
    else
      sd_GrowlMessage(AdminPhrase('msg_no_events_deleted'));
  }
  DisplayDefault();

} //DeleteEvents


// ################################ ADD EVENT #################################

function DisplayEventForm($eventid)
{
  global $DB, $refreshpage, $plugin, $pluginid, $sdlanguage, $EventMgr;

  echo '<div style="margin-bottom:6px">&nbsp;<a style="font-size:14px" href="'.
       $refreshpage.'" target="_self">&laquo; '.$plugin['name'].'</a></div>';

  $eventid = (!empty($eventid) && is_numeric($eventid)) ? (int)$eventid : 0;
  $tzsettings = EventManagerClass::GetTZSettings();
  if($eventid)
  {
    echo '<h2 class="header blue lighter">' .  AdminPhrase('edit_event') . '</h2>';
    $DB->result_type = MYSQL_ASSOC;
    $event = $DB->query_first('SELECT * FROM ' . $EventMgr->table .
                              ' WHERE eventid = %d',$eventid);

    /*
    $event['date']   = $event['date'] + (3600 * ($tzsettings['timezoneoffset']
                        +$tzsettings['daylightsavings'] ));
    */
    $event['day']    = @gmdate("d", $event['date']);
    $event['month']  = @gmdate("n", $event['date']);
    $event['year']   = @gmdate("Y", $event['date']);
    $event['hour']   = @gmdate("H", $event['date']);
    $event['minute'] = @gmdate("i", $event['date']);
    //v2.1.0: image/thumbnail existing?
    $event['image']  = empty($event['image'])?'':$event['image'];
    $event['thumbnail'] = empty($event['thumbnail'])?'':$event['thumbnail'];
  }
  else
  {
    echo '<h2 class="header blue lighter">' . AdminPhrase('new_event') . '</h2>';
    $eventid = 0;
    $eventdate = time() + (3600 * ($tzsettings['timezoneoffset']/*+$tzsettings['daylightsavings']*/));
    $event = array('title'         => '',
                   'description'   => '',
                   'date'          => $eventdate,
                   'day'           => @gmdate('d', $eventdate),
                   'month'         => @gmdate('n', $eventdate),
                   'year'          => @gmdate('Y', $eventdate),
                   'hour'          => @gmdate('H', $eventdate),
                   'minute'        => '00',
                   'venue'         => '',
                   'street'        => '',
                   'city'          => '',
                   'state'         => '',
                   'country'       => '',
                   'activated'     => 1,
                   'allowcomments' => 0,
                   'image'         => '',
                   'thumbnail'     => '');
  }

  echo '
  <form method="post" action="'.$refreshpage.'" class="form-horizontal">
  <input type="hidden" name="'.$EventMgr->pref.'eventid" value="'.$eventid.'" />
  '.PrintSecureToken().'';

  if($eventid)
  {
    // Delete event
    echo '
  <div class="form-group">
  	<label class="control-label col-sm-3">'.AdminPhrase('delete_event').'</label>
	<div class="col-sm-6">
      <input type="checkbox" class="ace" name="deleteevent" value="1" /><span class="lbl"> '.AdminPhrase('delete_event_descr').'</span>
    </div>
  </div>';
  }

  $p18_minute = empty($event['minute']) ? 00 : $event['minute'];
  $p18_hour   = empty($event['hour'])   ? @gmdate('H', time()) : intval($event['hour']);
  $p18_day    = empty($event['day'])    ? @gmdate('d', time()) : intval($event['day']);
  $p18_month  = empty($event['month'])  ? @gmdate('n', time()) : intval($event['month']);
  $p18_year   = empty($event['year'])   ? @gmdate('Y', time()) : intval($event['year']);

  echo '
  <div class="form-group">
  	<label class="control-label col-sm-3">'.$EventMgr->language['event_name2'].'</label>
	<div class="col-sm-6">
      <input type="text" class="form-control" name="'.$EventMgr->pref.'title" size="32" maxlength="128" value="'.CleanFormValue($event['title']).'" />
    </div>
  </div>
  <div class="form-group">
  	<label class="control-label col-sm-3">'.$EventMgr->language['date2'].'</label>
	<div class="col-sm-6">
    <select class="form-control" name="'.$EventMgr->pref.'month">
    ';

  $months = range(1,12);
  $month_arr = array($sdlanguage['January'], $sdlanguage['February'], $sdlanguage['March'],
                     $sdlanguage['April'],   $sdlanguage['May'],      $sdlanguage['June'],
                     $sdlanguage['July'],    $sdlanguage['August'],   $sdlanguage['September'],
                     $sdlanguage['October'], $sdlanguage['November'], $sdlanguage['December']);
  foreach($months as $value)
  {
    echo '
      <option value="'.$value.'"' . ($p18_month == $value ? ' selected="selected"' : '') . '>'.$month_arr[$value-1].'</option>';
  }
  echo '
      </select>
      <select class="form-control" name="'.$EventMgr->pref.'day">
      ';

  $days = range(1,31);
  foreach($days as $value)
  {
    $value = sprintf("%02d", $value);
    echo '
      <option value="'.$value.'"' . ($p18_day == $value ? ' selected="selected"' : '') . '>'.$value.'</option>';
  }
      echo '
    </select>
    <input type="text" class="form-control" name="'.$EventMgr->pref.'year" size="4" maxlength="4" value="'.CleanFormValue($event['year']).'" /> (ex: '.gmdate('Y').')
   </div>
  </div>
  <div class="form-group">
  	<label class="control-label col-sm-3">'.$EventMgr->language['time'].'</label>
	<div class="col-sm-6">
      <select name="'.$EventMgr->pref.'hour">';

  if(empty($EventMgr->settings['24_hours_display']))
  {
    echo '
        <option value="00"' . ($p18_hour == '00' ? ' selected="selected"' : '') . '>12 am</option>
        <option value="01"' . ($p18_hour == '01' ? ' selected="selected"' : '') . '>1 am</option>
        <option value="02"' . ($p18_hour == '02' ? ' selected="selected"' : '') . '>2 am</option>
        <option value="03"' . ($p18_hour == '03' ? ' selected="selected"' : '') . '>3 am</option>
        <option value="04"' . ($p18_hour == '04' ? ' selected="selected"' : '') . '>4 am</option>
        <option value="05"' . ($p18_hour == '05' ? ' selected="selected"' : '') . '>5 am</option>
        <option value="06"' . ($p18_hour == '06' ? ' selected="selected"' : '') . '>6 am</option>
        <option value="07"' . ($p18_hour == '07' ? ' selected="selected"' : '') . '>7 am</option>
        <option value="08"' . ($p18_hour == '08' ? ' selected="selected"' : '') . '>8 am</option>
        <option value="09"' . ($p18_hour == '09' ? ' selected="selected"' : '') . '>9 am</option>
        <option value="10"' . ($p18_hour == '10' ? ' selected="selected"' : '') . '>10 am</option>
        <option value="11"' . ($p18_hour == '11' ? ' selected="selected"' : '') . '>11 am</option>
        <option value="12"' . ($p18_hour == '12' ? ' selected="selected"' : '') . '>12 pm</option>
        <option value="13"' . ($p18_hour == '13' ? ' selected="selected"' : '') . '>1 pm</option>
        <option value="14"' . ($p18_hour == '14' ? ' selected="selected"' : '') . '>2 pm</option>
        <option value="15"' . ($p18_hour == '15' ? ' selected="selected"' : '') . '>3 pm</option>
        <option value="16"' . ($p18_hour == '16' ? ' selected="selected"' : '') . '>4 pm</option>
        <option value="17"' . ($p18_hour == '17' ? ' selected="selected"' : '') . '>5 pm</option>
        <option value="18"' . ($p18_hour == '18' ? ' selected="selected"' : '') . '>6 pm</option>
        <option value="19"' . ($p18_hour == '19' ? ' selected="selected"' : '') . '>7 pm</option>
        <option value="20"' . ($p18_hour == '20' ? ' selected="selected"' : '') . '>8 pm</option>
        <option value="21"' . ($p18_hour == '21' ? ' selected="selected"' : '') . '>9 pm</option>
        <option value="22"' . ($p18_hour == '22' ? ' selected="selected"' : '') . '>10 pm</option>
        <option value="23"' . ($p18_hour == '23' ? ' selected="selected"' : '') . '>11 pm</option>
        ';
  }
  else
  {
    $hours = range(0,23);
    foreach($hours as $value)
    {
      $value = sprintf("%02d", $value);
      echo '
        <option value="'.$value.'"' . ($p18_hour == $value ? ' selected="selected"' : '') . '>'.$value.'</option>';
    }
  }

  echo '
      </select> :
      <select name="'.$EventMgr->pref.'minute">
        <option value="00"' . ($p18_minute == '00' ? ' selected="selected"' : '') . '>00</option>
        <option value="05"' . ($p18_minute == '05' ? ' selected="selected"' : '') . '>05</option>
        <option value="10"' . ($p18_minute == '10' ? ' selected="selected"' : '') . '>10</option>
        <option value="15"' . ($p18_minute == '15' ? ' selected="selected"' : '') . '>15</option>
        <option value="20"' . ($p18_minute == '20' ? ' selected="selected"' : '') . '>20</option>
        <option value="25"' . ($p18_minute == '25' ? ' selected="selected"' : '') . '>25</option>
        <option value="30"' . ($p18_minute == '30' ? ' selected="selected"' : '') . '>30</option>
        <option value="35"' . ($p18_minute == '35' ? ' selected="selected"' : '') . '>35</option>
        <option value="40"' . ($p18_minute == '40' ? ' selected="selected"' : '') . '>40</option>
        <option value="45"' . ($p18_minute == '45' ? ' selected="selected"' : '') . '>45</option>
        <option value="50"' . ($p18_minute == '50' ? ' selected="selected"' : '') . '>50</option>
        <option value="55"' . ($p18_minute == '55' ? ' selected="selected"' : '') . '>55</option>
      </select>
    </div>
  </div>
  <div class="form-group">
  	<label class="control-label col-sm-3">'.$EventMgr->language['venue2'].'</label>
	<div class="col-sm-6">
      <input type="text" class="form-control" name="'.$EventMgr->pref.'venue" size="32" maxlength="128" value="'.CleanFormValue($event['venue']).'" />
    </div>
  </div>
  <div class="form-group">
  	<label class="control-label col-sm-3">'.$EventMgr->language['street'].'</label>
	<div class="col-sm-6">
      <input type="text" class="form-control" name="'.$EventMgr->pref.'street" size="32" maxlength="128" value="'.CleanFormValue($event['street']).'" />
    </div>
  </div>
  <div class="form-group">
  	<label class="control-label col-sm-3">'.$EventMgr->language['city'].'</label>
	<div class="col-sm-6">
      <input type="text" class="form-control" name="'.$EventMgr->pref.'city" size="32" maxlength="128" value="'.CleanFormValue($event['city']).'" />
    </div>
  </div>';

  if(empty($EventMgr->settings['display_us_states']))
  {
    echo '
  <div class="form-group">
  	<label class="control-label col-sm-3">'.$EventMgr->language ['state'].'</label>
	<div class="col-sm-6">
		<input type="text" class="form-control" name="'.$EventMgr->pref.'state" value="' . $event['state'] . '" maxlength="128" size="32" /></div>
  </div>';
  }
  else
  {
    //2013-05-11: store state codes in uppercase now
    $p_state = strtoupper($event['state']);
    echo '
  <div class="form-group">
  	<label class="control-label col-sm-3">'.$EventMgr->language['state'].'</label>
	<div class="col-sm-6">
    <select class="form-control" name="'.$EventMgr->pref.'state">
      <option value=""'.(empty($p_state) ? 'selected="selected"' : '').'>'.$EventMgr->language['none'].'</option>
        <option value="AL"' . ($p_state == 'AL' ? ' selected="selected"' : '') . '>Alabama</option>
        <option value="AK"' . ($p_state == 'AK' ? ' selected="selected"' : '') . '>Alaska</option>
        <option value="AZ"' . ($p_state == 'AZ' ? ' selected="selected"' : '') . '>Arizona</option>
        <option value="AR"' . ($p_state == 'AR' ? ' selected="selected"' : '') . '>Arkansas</option>
        <option value="CA"' . ($p_state == 'CA' ? ' selected="selected"' : '') . '>California</option>
        <option value="CO"' . ($p_state == 'CO' ? ' selected="selected"' : '') . '>Colorado</option>
        <option value="CT"' . ($p_state == 'CT' ? ' selected="selected"' : '') . '>Connecticut</option>
        <option value="DE"' . ($p_state == 'DE' ? ' selected="selected"' : '') . '>Delaware</option>
        <option value="DC"' . ($p_state == 'DC' ? ' selected="selected"' : '') . '>District of Columbia</option>
        <option value="FL"' . ($p_state == 'FL' ? ' selected="selected"' : '') . '>Florida</option>
        <option value="GA"' . ($p_state == 'GA' ? ' selected="selected"' : '') . '>Georgia</option>
        <option value="HI"' . ($p_state == 'HI' ? ' selected="selected"' : '') . '>Hawaii</option>
        <option value="ID"' . ($p_state == 'ID' ? ' selected="selected"' : '') . '>Idaho</option>
        <option value="IL"' . ($p_state == 'IL' ? ' selected="selected"' : '') . '>Illinois</option>
        <option value="IN"' . ($p_state == 'IN' ? ' selected="selected"' : '') . '>Indiana</option>
        <option value="IA"' . ($p_state == 'IA' ? ' selected="selected"' : '') . '>Iowa</option>
        <option value="KS"' . ($p_state == 'KS' ? ' selected="selected"' : '') . '>Kansas</option>
        <option value="KY"' . ($p_state == 'KY' ? ' selected="selected"' : '') . '>Kentucky</option>
        <option value="LA"' . ($p_state == 'LA' ? ' selected="selected"' : '') . '>Louisiana</option>
        <option value="ME"' . ($p_state == 'ME' ? ' selected="selected"' : '') . '>Maine</option>
        <option value="MD"' . ($p_state == 'MD' ? ' selected="selected"' : '') . '>Maryland</option>
        <option value="MA"' . ($p_state == 'MA' ? ' selected="selected"' : '') . '>Massachusetts</option>
        <option value="MI"' . ($p_state == 'MI' ? ' selected="selected"' : '') . '>Michigan</option>
        <option value="MN"' . ($p_state == 'MN' ? ' selected="selected"' : '') . '>Minnesota</option>
        <option value="MS"' . ($p_state == 'MS' ? ' selected="selected"' : '') . '>Mississippi</option>
        <option value="MO"' . ($p_state == 'MO' ? ' selected="selected"' : '') . '>Missouri</option>
        <option value="MT"' . ($p_state == 'MT' ? ' selected="selected"' : '') . '>Montana</option>
        <option value="NE"' . ($p_state == 'NE' ? ' selected="selected"' : '') . '>Nebraska</option>
        <option value="NV"' . ($p_state == 'NV' ? ' selected="selected"' : '') . '>Nevada</option>
        <option value="NH"' . ($p_state == 'NH' ? ' selected="selected"' : '') . '>New Hampshire</option>
        <option value="NJ"' . ($p_state == 'NJ' ? ' selected="selected"' : '') . '>New Jersey</option>
        <option value="NM"' . ($p_state == 'NM' ? ' selected="selected"' : '') . '>New Mexico</option>
        <option value="NY"' . ($p_state == 'NY' ? ' selected="selected"' : '') . '>New York</option>
        <option value="NC"' . ($p_state == 'NC' ? ' selected="selected"' : '') . '>North Carolina</option>
        <option value="ND"' . ($p_state == 'ND' ? ' selected="selected"' : '') . '>North Dakota</option>
        <option value="OH"' . ($p_state == 'OH' ? ' selected="selected"' : '') . '>Ohio</option>
        <option value="OK"' . ($p_state == 'OK' ? ' selected="selected"' : '') . '>Oklahoma</option>
        <option value="OR"' . ($p_state == 'OR' ? ' selected="selected"' : '') . '>Oregon</option>
        <option value="PA"' . ($p_state == 'PA' ? ' selected="selected"' : '') . '>Pennsylvania</option>
        <option value="RI"' . ($p_state == 'RI' ? ' selected="selected"' : '') . '>Rhode Island</option>
        <option value="SC"' . ($p_state == 'SC' ? ' selected="selected"' : '') . '>South Carolina</option>
        <option value="SD"' . ($p_state == 'SD' ? ' selected="selected"' : '') . '>South Dakota</option>
        <option value="TN"' . ($p_state == 'TN' ? ' selected="selected"' : '') . '>Tennessee</option>
        <option value="TX"' . ($p_state == 'TX' ? ' selected="selected"' : '') . '>Texas</option>
        <option value="UT"' . ($p_state == 'UT' ? ' selected="selected"' : '') . '>Utah</option>
        <option value="VT"' . ($p_state == 'VT' ? ' selected="selected"' : '') . '>Vermont</option>
        <option value="VA"' . ($p_state == 'VA' ? ' selected="selected"' : '') . '>Virginia</option>
        <option value="WA"' . ($p_state == 'WA' ? ' selected="selected"' : '') . '>Washington</option>
        <option value="WV"' . ($p_state == 'WV' ? ' selected="selected"' : '') . '>West Virginia</option>
        <option value="WI"' . ($p_state == 'WI' ? ' selected="selected"' : '') . '>Wisconsin</option>
        <option value="WY"' . ($p_state == 'WY' ? ' selected="selected"' : '') . '>Wyoming</option>
    </select>
    </div>
  </div>';
  }

  echo '<div class="form-group">
  	<label class="control-label col-sm-3">'.$EventMgr->language['country'].'</label>
	<div class="col-sm-6">
      <input type="text" class="form-control" name="'.$EventMgr->pref.'country" size="32" maxlength="64" value="'.CleanFormValue($event['country']).'" />
    </div>
  </div>
  <div class="form-group">
  	<label class="control-label col-sm-3">'.$EventMgr->language['description'].'</label>
	<div class="col-sm-6">';
  DisplayBBEditor($EventMgr->allow_bbcode, $EventMgr->pref.'description', $event['description'], 'eventmgr_bbcode', 54, 5);
  echo '
    </div>
  </div>
  <div class="form-group">
  	<label class="control-label col-sm-3">'.AdminPhrase('options').'</label>
	<div class="col-sm-6">
      <input type="checkbox" class="ace" name="'.$EventMgr->pref.'activated" value="1"'.
        (!empty($event['activated'])?' checked="checked"':'').' />'.
        ' <span class="lbl"><strong>'.$EventMgr->language['option_display'].'</strong> '.$EventMgr->language['option_display_hint'].'</span><br />
      <input type="checkbox" class="ace" name="'.$EventMgr->pref.'allowcomments" value="1"'.
        (!empty($event['allowcomments'])?' checked="checked"':'').' />'.
        ' <span class="lbl"><strong>'.$EventMgr->language['option_comments'].'</strong> '.$EventMgr->language['option_comments_hint'].'</span><br />
    </div>
  </div>';

  //v2.1.0: file uploader for image/thumbnail
  // Create an uploaded object to integrate "plupload" by Moxiecode:
  require_once(ROOT_PATH.'includes/class_sd_uploader.php');
  $uploader_config = array(
    'html_id'            => $EventMgr->pref.'image',  // id of new uploader div container
    'html_upload_remove' => 'image', // hide "old" file input
    'filters'            => ('{title : "'.addslashes($EventMgr->language['images_title']).
                             '", extensions : "jpg,jpeg,gif,png,tif"}'),
    'maxQueueCount'      => 1,
    'removeAfterSuccess' => 'false',
    'afterUpload'        => 'processFile',
    'singleUpload'       => 'true',
    'title'              => '<strong>'.$EventMgr->language['select_image_hint'].'</strong>',
	'border'			=>	false,
	'layout'				=> 'col-sm-12',
  );
  $uploader = new SD_Uploader($pluginid, $eventid, $uploader_config);
  $image_uploader_JS   = $uploader->getJS();
  $image_uploader_HTML = $uploader->getHTML();

  echo '
  <div class="form-group">
  	<label class="control-label col-sm-3">'.$EventMgr->language['optional_image'].'
  	'.$EventMgr->language['optional_image_descr'].'</label>
	<div class="col-sm-6">
      '.$image_uploader_HTML.'
      <input type="file" id="image" name="'.$EventMgr->pref.'image" size="30" />
    ';

  // Offer preview/delete only when editing, not inserting an event
  if(!empty($eventid) && strlen($event['image']))
  {
    // Preview of current image
    $src = ROOT_PATH.$EventMgr->image_folder.$event['image'];
    echo '<a class="cbox" href="'.$src.'" rel="image">'.
         SD_Image_Helper::GetThumbnailTag($src,'','','',false,true).'</a><br />
      <input type="checkbox" value="1" name="deleteimage" /> '.AdminPhrase('delete_image');
  }

  echo '
    </div>
  </div>';

  $uploader->setValue('html_id', $EventMgr->pref.'thumbnail'); // id of new uploader div container
  $uploader->setValue('html_upload_remove', 'thumbnail'); // hide "old" file input
  $thumb_uploader_JS   = $uploader->getJS();
  $thumb_uploader_HTML = $uploader->getHTML();

  echo '
  <div class="form-group">
  	<label class="control-label col-sm-3">'.$EventMgr->language['optional_thumbnail'].'
  	'.$EventMgr->language['optional_thumbnail_descr'].'<</label>
	<div class="col-sm-6">
      '.$thumb_uploader_HTML.'
      <input type="file" id="thumbnail" name="'.$EventMgr->pref.'thumbnail" size="30" />';

  // Offer preview/delete only when editing, not inserting an event
  if(!empty($eventid) && strlen($event['thumbnail']))
  {
    // Preview thumbnail
    $src = ROOT_PATH.$EventMgr->image_folder.$event['thumbnail'];
    echo '<a class="cbox" href="'.$src.'" rel="image">'.
         SD_Image_Helper::GetThumbnailTag($src,'','','',false,true).'</a><br />
      <label for="deletethumbnail"><input type="checkbox" value="1" id="deletethumbnail" name="deletethumbnail" />
      '.AdminPhrase('delete_thumbnail').'</label>';
  }
  if(!empty($event['image']))
  {
    echo '<br />
      <label for="createthumbnail">
        <input type="checkbox" value="1" id="createthumbnail" name="createthumbnail" />
        '.AdminPhrase('create_thumbnail_from_image').'
      </label>';
  }
  echo '
    </div>
  </div>';

  echo '
  <div class="center">
      <input type="hidden" name="action" value="'.(empty($eventid)?'insert':'update').'event" />
      <button class="btn btn-info" type="submit" value="'.addslashes($EventMgr->language[(empty($eventid)?'submit':'update').'_event']).'" /><i class="ace-icon fa fa-check"></i> '.addslashes($EventMgr->language[(empty($eventid)?'submit':'update').'_event']).'</button>
  </div>
 
  </form>
  ';


  echo '
<script type="text/javascript">
//<![CDATA[
function processFile(file_id, file_name, file_title) {
  jQuery("#progress_"+file_id).hide();
  jQuery("#filelist_"+file_id).html("");
  jQuery("#uploader_"+file_id+"_messages").html(\''.addslashes(AdminPhrase('msg_image_uploaded_js')).'\').show();
}

jQuery(document).ready(function() {
'.$thumb_uploader_JS.'
'.$image_uploader_JS.'
});
//]]>
</script>
';

} //DisplayEventForm


// ############################# DISPLAY EVENTS ###############################

function DisplayEvents()
{
  global $DB, $categoryid, $mainsettings, $refreshpage, $sdlanguage, $EventMgr;

  $sorttype = GetVar('sorttype', 'datez', 'string');
  switch($sorttype)
  {
    case 'titlea':
      $order = 'title ASC';
    break;

    case 'titlez':
      $order = 'title DESC';
    break;

    case 'datea':
      $order = 'date ASC';
    break;

    case 'locationa':
      $order = 'city ASC';
    break;

    case 'locationz':
      $order = 'city DESC';
    break;

    case 'venuea':
      $order = 'venue ASC';
    break;

    case 'venuez':
      $order = 'venue DESC';
    break;

    default:
      $sorttype = 'datez';
      $order = 'date DESC';
  }

  $page = GetVar('page', 1, 'whole_number');
  $pagesize = GetVar('pagesize', 15, 'string');
  if(!in_array($pagesize, array(15,30,50,80,100,200,500,'all')))
  {
    $pagesize = 15;
  }
  else
  if($pagesize!='all')
  {
    $pagesize = Is_Valid_Number($pagesize, 15, 15, 500);
  }

  $asc = (substr($order, -3) == 'ASC');
  $arrow = $asc ? '&uarr;' : '&darr;';
  $pagination_html = false;

  $total_rows = $DB->query_first('SELECT COUNT(*) ecount FROM '.$EventMgr->table.
                                 ' WHERE IFNULL(activated,0) = 1');
  $total_rows = (int)$total_rows['ecount'];
  if(!$total_rows)
  {
    PrintSection($EventMgr->language['events']);
    DisplayMessage(AdminPhrase('no_events'));
    EndSection();
    return;
  }

  if($pagesize=='all')
  {
    $limit = '';
  }
  else
  {
    //SD 2012-11-16: fix paging (ceil instead of floor)
    if($page * $pagesize > $total_rows)
    {
      $page = ceil($total_rows / $pagesize);
    }
    $page = ($page < 1) ? 1 : $page; //2013-02-08
    $limit = ' LIMIT '.(($page-1)*$pagesize).', ' . $pagesize;

    // Cache Pagination:
    ob_start();
    $p = new pagination;
    $p->items($total_rows);
    $p->limit($pagesize);
    $p->currentPage($page);
    $p->adjacents(3);
    $p->target($refreshpage.'&amp;sorttype='.$sorttype);
    $p->show();
    $pagination_html = ob_get_clean();
    unset($p);
  }

  $getevents = $DB->query('SELECT * FROM ' . $EventMgr->table . ' ORDER BY ' . $order . $limit);
  $rows = $DB->get_num_rows($getevents);

  echo '
<script type="text/javascript">
//<![CDATA[
if(typeof jQuery !== "undefined") {
jQuery(document).ready(function() {
  (function($){
    $("select#select-pagesize").change(function(){
      $("form#events-list").submit();
    });
  })(jQuery);
});
}
//]]>
</script>
    ';

  $pagesize_select = '<select id="select-pagesize" name="pagesize">
    <option value="15"'.($pagesize==15?' selected="selected"':'').'>15</option>
    <option value="30"'.($pagesize==30?' selected="selected"':'').'>30</option>
    <option value="50"'.($pagesize==50?' selected="selected"':'').'>50</option>
    <option value="80"'.($pagesize==80?' selected="selected"':'').'>80</option>
    <option value="100"'.($pagesize==100?' selected="selected"':'').'>100</option>
    <option value="200"'.($pagesize==200?' selected="selected"':'').'>200</option>
    <option value="500"'.($pagesize==500?' selected="selected"':'').'>500</option>
    <option value="all"'.($pagesize=='all'?' selected="selected"':'').'>'.AdminPhrase('all_events').'</option>
    </select>';

  $paging_row = '<tr><td class="td2" colspan="5" style="padding: 8px !important">'.$pagination_html.'</td>
      <td class="td2" colspan="2" style="padding: 8px !important">[pagesize]</td>
      </tr>';
	  
	    //PrintSection(AdminPhrase('add_event_or_settings'))
	  
   echo '<div class="pull-right">'  ;
   echo str_replace('[pagesize]',AdminPhrase('pagesize').' '.$pagesize_select,$paging_row);
   echo '</div><div class="clearfix"></div><div class="space-4"></div>';

  PrintSection($EventMgr->language['events']);
  echo '
  <form id="events-list" method="post" action="' . $refreshpage . '">
  <input type="hidden" name="action" value="deleteevents" />
  <input type="hidden" name="page" value="'.$page.'" />
  <input type="hidden" name="pagesize" value="'.$pagesize.'" />
  <input type="hidden" name="sorttype" value="'.$sorttype.'" />
  '.PrintSecureToken().'
  <table width="100%" class="table table-bordered table-striped">';

  echo '
  <thead>
    <tr>
      <th class="td1"><a href="'.$refreshpage.'&amp;action=displayevents&amp;sorttype='.($asc?'titlez':'titlea').'">'.$EventMgr->language['event_name'].'</a></th>
      <th class="td1"><a href="'.$refreshpage.'&amp;action=displayevents&amp;sorttype='.($asc?'datez':'datea').'">'.$EventMgr->language['date'].'</a></th>
      <th class="td1"><a href="'.$refreshpage.'&amp;action=displayevents&amp;sorttype='.($asc?'locationz':'locationa').'">'.$EventMgr->language['location'].'</a></th>
      <th class="td1"><a href="'.$refreshpage.'&amp;action=displayevents&amp;sorttype='.($asc?'venuez':'venuea').'">'.$EventMgr->language['venue'].'</a></th>
      <th class="td1" align="center" width="70">'.AdminPhrase('common_allow_comment').'</th>
      <th class="td1" align="center" width="70">'.AdminPhrase('status').'</th>
      <th class="td1" align="center" width="110">'.AdminPhrase('delete_event').'</th>
    </tr>
	</thead>
	<tbody>';

  // Remove timestamps
  $dispformat = str_replace('H:i:s','',$mainsettings['dateformat']);
  $dispformat = str_replace('H:i','',$dispformat);
  $dispformat = str_replace('h:i:s','',$dispformat);
  $dispformat = str_replace('h:i','',$dispformat);
  $dispformat = str_replace('G:i:s','',$dispformat);
  $dispformat = str_replace('G:i','',$dispformat);
  $dispformat = str_replace('a','',$dispformat);

  for($i = 0; $i < $rows; $i++)
  {
    $event = $DB->fetch_array($getevents,null,MYSQL_ASSOC);
    $event['date'] = $event['date'];
    $eventid = (int)$event['eventid'];
    $ac = (empty($event['allowcomments']) ? 0 : 1);
    $online = (empty($event['activated']) ? 0 : 1);

    echo '
    <tr>
      <td class="td2">
        <input type="hidden" name="eventids[]" value="'.$eventid.'" />
        <a href="'.$refreshpage.'&amp;action=displayevent&amp;'.
          $EventMgr->pref.'eventid='.$event['eventid'].'">'.$event['title'].'</a></td>
      <td class="td3">&nbsp;'.
        #SD371: use gmdate instead of DisplayDate
        @gmdate($dispformat, $event['date']).' '.
        @gmdate((empty($EventMgr->settings['24_hours_display'])?'g:ia':'G:i'), $event['date']).
        '</td>
      <td class="td2">&nbsp;'.
         $event['city'].
         (!empty($event['state']) || !empty($event['country']) ? ', ' : '') .
         $event['state'] .
         (!empty($event['country']) ? ', ' : '') . $event['country'] . '</td>
      <td class="td3">&nbsp;'.$event['venue'].'</td>
      <td class="td3" align="center" width="70" style="margin:0;padding:2px">
        <div class="status_switch" style="margin:0 auto; display:inline-block;">
          <input type="hidden" name="ec_'.$eventid.'" value="'.$ac.'" />
          <a onclick="javascript:return false;" class="status_link on"  style="display: '.( $ac ? 'block': 'none').'"> '.AdminPhrase('common_yes').' </a>
          <a onclick="javascript:return false;" class="status_link off" style="display: '.(!$ac ? 'block': 'none').'"> '.AdminPhrase('common_no').' </a>
        </div>
      </td>
      <td class="td2" align="center" width="70" >
        <div class="status_switch" style="margin:0 auto; display:inline-block;">
          <input type="hidden" name="ea_'.$eventid.'" value="'.$online.'" />
          <a  onclick="javascript:return false;" class="btn btn-success status_link on"  style="display: '.( $online ? 'block': 'none').'"> '.AdminPhrase('online').' </a>
          <a  onclick="javascript:return false;" class="btn btn-danger status_link off" style="display: '.(!$online ? 'block': 'none').'"> '.AdminPhrase('offline').' </a>
        </div>
        </td>
      <td class="td3" align="center">
        <center><input type="checkbox" name="deleteeventid[]" value="'.$event['eventid'].'" /></center>
      </td>
    </tr>';
  }

  if($rows)
  {
    //echo str_replace('[pagesize]',$EventMgr->language['events'].': '.$total_rows,$paging_row);
    echo '<tr>
      <td class="td1" colspan="7" align="right" >
        <button class="btn btn-danger btn-sm" type="submit" value=""><i class="ace-icon fa fa-trash-o"></i> '.AdminPhrase('delete_events').'</button>
      </td>
    </tr>';
  }
  else
  {
    echo '<tr><td class="td1" colspan="7">'.AdminPhrase('no_events').'</td></tr>';
  }

  echo '</tbody></table>
  </form>';

  EndSection();

} //DisplayEvents


// ############################# DISPLAY DEFAULT ##############################

function DisplayDefault()
{
  global $refreshpage;
  
   echo '
  		<div class="no-margin-left no-margin-bottom pull-left">
        <a class="btn btn-success" href="'.
        $refreshpage.'&amp;action=displayevent">'.
        '<i class="ace-icon fa fa-plus"></i> '.AdminPhrase('add_event').'</a>
        
        &nbsp;<a class="btn btn-primary" href="'.
        $refreshpage.'&amp;action=settings">'.
        '<i class="ace-icon fa fa-cog"></i> '.AdminPhrase('view_settings').'</a>
        </div>
		';
  /*
 
  echo '
  <table width="100%" border="0" cellpadding="5" cellspacing="0">
    <tr>
      <td class="td2"><b>'.AdminPhrase('add_event').'</b><br />
        '.AdminPhrase('add_event_descr').'</td>
      <td class="td3" style="padding:8px">
        <center>
        <a style="display:block;width:180px" class="btn btn-primary" href="'.
        $refreshpage.'&amp;action=displayevent">'.
        '<span class="sprite sprite-plus"></span> <b>'.AdminPhrase('add_event').'</b></a>
        </center>
      </td>

      <td class="td2"><b>'.AdminPhrase('settings').'</b><br />
        '.AdminPhrase('settings_descr').'</td>
      <td class="td3" style="padding:8px;">
        <center>
        <a style="display:block;width:180px" class="btn btn-primary" href="'.
        $refreshpage.'&amp;action=settings">'.
        '<span class="sprite sprite-settings"></span> <b>'.AdminPhrase('view_settings').'</b></a>
        </center>
      </td>
    </tr>
  </table>';
  */

  //EndSection();

  DisplayEvents();

} //DisplayDefault


// ############################# SELECT FUNCTION ##############################

switch($action)
{
  case 'displayevent':
    DisplayEventForm($eventid);
    break;

  case 'insertevent':
  case 'updateevent':
    SaveEvent($action, $eventid);
    break;

  case 'deleteevent':
    DeleteEvent($eventid,true);
    break;

  case 'deleteevents':
    DeleteEvents();
    break;

  case 'settings':
    echo '<div style="clear:both;padding:2px;margin:0 0 8px 0;font-weight:bold;font-size:14px">
    <a href="'.$refreshpage.'" target="_self">&laquo; '.$plugin['name'].'</a></div>';
    PrintPluginSettings($pluginid, array('options'), $refreshpage);
    break;

  default:
    DisplayDefault();
}
