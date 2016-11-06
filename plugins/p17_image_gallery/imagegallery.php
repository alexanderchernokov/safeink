<?php

if(!defined('IN_PRGM'))
{
  die('Hacking attempt!');
}


// ############################################################################
// INCLUDE IMAGE GALLERY LIBRARY
// ############################################################################

require_once(ROOT_PATH . 'plugins/p17_image_gallery/p17_lib.php');


// ############################################################################
// DISPLAY HEADER
// ############################################################################

function p17_DisplayHeader($sectionid)
{
  global $userinfo, $p17_language, $p17_settings;

  echo $p17_language['sections'] . ' ';

  p17_PrintSectionHierarchy($sectionid);

  if((!empty($userinfo['pluginsubmitids']) && @in_array(17, $userinfo['pluginsubmitids'])) &&
     (!empty($p17_settings['allow_root_upload']) || ($sectionid>1)))
  {
    echo '<br /><a href="' . RewriteLink('index.php?categoryid=' . PAGE_ID . '&p17_sectionid=' . $sectionid . '&p17_action=submitimage') . '">' .
      str_replace(' ','&nbsp;',$p17_language['submit_an_image']) . '</a>';
  }

  echo '<br /><br />';
}


// ############################################################################
// PRINT SECTION HIERARCHY
// ############################################################################

function p17_PrintSectionHierarchy($sectionid)
{
  global $DB;

  $section = $DB->query_first("SELECT parentid, name FROM {p17_sections} WHERE sectionid = %d AND (sectionid=1 OR activated=1) ORDER BY " . p17_GetSectionSort(), $sectionid);

  if($section['parentid'] > 0)
  {
    p17_PrintSectionHierarchy($section['parentid']);
    echo '&nbsp;&raquo;&nbsp;';
  }

  echo '<a href="' . RewriteLink('index.php?categoryid=' . PAGE_ID . '&p17_sectionid=' . $sectionid) . '">' . $section['name'] . '</a>';
}


// ############################################################################
// GET SECTION SORT
// ############################################################################

function p17_GetSectionSort()
{
  global $p17_settings;

  switch($p17_settings['section_sort_order'])
  {
    case '0':
    case 'Newest First':
      $order = 'datecreated DESC';
      break;

    case '1':
    case 'Oldest First':
      $order = 'datecreated ASC';
      break;

    case '3':
    case 'Alphabetically Z-A':
      $order = 'name DESC';
      break;

    case '2':
    case 'Alphabetically A-Z':
    default:
      $order = 'name ASC';
      break;
  }

  return $order;
}



// ############################################################################
// INSERT IMAGE
// ############################################################################

function p17_InsertImage()
{
  global $DB, $sdlanguage, $userinfo, $p17_settings, $p17_language;

  if(!CheckFormToken())
  {
    RedirectPage(RewriteLink('index.php?categoryid='.PAGE_ID),'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  $sectionid     = GetVar('p17_sectionid', 0, 'whole_number');
  $image         = empty($_FILES['p17_image'])     ? null : $_FILES['p17_image'];
  $thumbnail     = empty($_FILES['p17_thumbnail']) ? null : $_FILES['p17_thumbnail'];
  $activated     = empty($p17_settings['auto_approve_images'])?0:1;
  $author        = GetVar('p17_author', '', 'string');
  $title         = GetVar('p17_title', '', 'string');
  $description   = GetVar('p17_description', '', 'string');

  if(!$sectionid)
  {
    return;
  }

  $errors_arr = array();

  // initialization
  $imagedir  = dirname(__FILE__) . '/images/';

  // List of our known photo types
  $known_photo_types = array(
  'image/pjpeg' => 'jpg',
  'image/jpeg'  => 'jpg',
  'image/gif'   => 'gif',
  'image/wbmp'  => 'wbmp',
  'image/bmp'   => 'bmp',
  'image/x-png' => 'png',
  'image/png'   => 'png'
  );

  $valid_image_types = array(
  'image/pjpeg',
  'image/jpeg',
  'image/gif',
  'image/bmp',
  'image/x-png',
  'image/png');

  $valid_image_extensions = array(
  'gif',
  'jpg',
  'peg', // jpeg
  'bmp',
  'png');

  if(isset($image) && !empty($image['tmp_name']))
  {
    //SD343: don't allow dangerous titles, file names or descriptions
    if( preg_match("#\.php([2-6]?)|\.bat|\.pl|\.(p?)html?|\.sh|\.js|\.cgi|\.cmd|\.com|\.exe|\.vb|<script|<html|<head|<\?php|<title|<body|<pre|<table|<a\s+href|<img|<plaintext|<cross\-domain\-policy#si", unhtmlspecialchars($title)) ||
        preg_match("#\.php([2-6]?)|\.bat|\.pl|\.(p?)html?|\.sh|\.js|\.cgi|\.cmd|\.com|\.exe|\.vb|<script|<html|<head|<\?php|<title|<body|<pre|<table|<a\s+href|<img|<plaintext|<cross\-domain\-policy#si", unhtmlspecialchars($image['name'])) ||
        preg_match("#<script|<html|<head|<\?php|<title|<body|<pre|<table|<a\s+href|<img|<plaintext|<cross\-domain\-policy#si", unhtmlspecialchars($description)) ||
        preg_match("#<script|<html|<head|<\?php|<title|<body|<pre|<table|<a\s+href|<img|<plaintext|<cross\-domain\-policy#si", unhtmlspecialchars($author))
        )
    {
      echo '<strong>'.$sdlanguage['invalid_image_upload'].'!<strong><br />';
      p17_SubmitImage($sectionid);
      return false;
    }

    // Check if image was really uploaded
    if(!@is_uploaded_file($image['tmp_name']))
    {
      echo '<strong>'.$sdlanguage['invalid_image_upload'].'!<strong><br />';
      p17_SubmitImage($sectionid);
      return false;
    }

    if($image['error'] > 0)
    {
      $errors_arr[] = p17_GetFileErrorDescr($image['error']);
    }
    else
    {
      // Check file type
      if(isset($image) && !in_array($image['type'], $valid_image_types))
      {
        $errors_arr[] = $p17_language['image'].' '.$p17_language['invalid_image_type'];
      }
      else
      // lets make sure the extension is correct
      if(isset($image) && !in_array(substr(strtolower($image['name']), -3), $valid_image_extensions))
      {
        $errors_arr[] = $p17_language['image'].' '.$p17_language['invalid_image_type'];
      }
    }
  }
  else
  {
    $errors_arr[] = $p17_language['image'].' '.p17_GetFileErrorDescr($image['error']);//$p17_language['invalid_image_type'];
  }

  // Thumbnail error processing (no error if no thumbnail was uploaded (4))
  if(isset($thumbnail) && $thumbnail['error'] > 0 && $thumbnail['error'] <> 4)
  {
    $errors_arr[] = p17_GetFileErrorDescr($thumbnail['error']);
    unset($thumbnail);
  }
  else if(isset($thumbnail) && ($thumbnail['error'] = 0) && !empty($thumbnail['tmp_name']))
  {
    // Check if image was really uploaded
    if(!is_uploaded_file($thumbnail['tmp_name']))
    {
      echo '<strong>'.$sdlanguage['invalid_image_upload'].'!<strong><br />';
      p17_SubmitImage($sectionid);
      return false;
    }
    else
    {
      //SD343: don't allow dangerous filenames
      if(preg_match("#\.php([2-6]?)|\.bat|\.pl|\.(p?)html?|\.sh|\.js|\.cgi|\.cmd|\.com|\.exe|\.vb|<script|<html|<head|<\?php|<title|<body|<pre|<table|<a(\s*)href|<img|<plaintext|<cross\-domain\-policy#si", unhtmlspecialchars($thumbnail['name'])))
      {
        echo '<strong>'.$sdlanguage['invalid_image_upload'].'!<strong><br />';
        p17_SubmitImage($sectionid);
        return false;
      }
      if(empty($p17_settings['auto_create_thumbs']) && isset($thumbnail) &&
         !in_array($thumbnail['type'], $valid_image_types))
      {
        $errors_arr[] = $p17_language['thumbnail'].' '.$p17_language['invalid_image_type'];
        unset($thumbnail);
      }
      if(empty($p17_settings['auto_create_thumbs']) && isset($thumbnail) &&
        !in_array(substr(strtolower($_FILES['p17_thumbnail']['name']), -3), $valid_image_extensions))
      {
        $errors_arr[] = $p17_language['thumbnail'].' '.$p17_language['invalid_image_type'];
        unset($thumbnail);
      }
    }
  }

  if(!strlen($title) && !empty($p17_settings['image_title_required']))
  {
    $errors_arr[] = $p17_language['enter_title'];
  }

  if(!strlen($author) && !empty($p17_settings['image_author_required']))
  {
    $errors_arr[] = $p17_language['enter_author'];
  }

  if($image['size'] == 0)
  {
    $errors_arr[] = $p17_language['select_image'];
  }

  if(empty($p17_settings['auto_create_thumbs']) && isset($thumbnail) && empty($thumbnail['size']))
  {
    $errors_arr[] = $p17_language['select_thumbnail'];
  }

  if(!empty($p17_settings['auto_create_thumbs']) && isset ($image) &&
     ($image['type'] == 'image/gif') && !function_exists('imageCreateFromGIF'))
  {
    $errors_arr[] = $p17_language['no_gif_support'];
  }

  if(!strlen($description) && !empty($p17_settings['image_description_required']))
  {
    $errors_arr[] = $p17_language['enter_description'];
  }

  if(!CaptchaIsValid())
  {
    $errors_arr[] = $sdlanguage['captcha_not_valid'];
  }

  // IF errors exist, display them and the submit form again
  if(count($errors_arr))
  {
    DisplayMessage($errors_arr, true);
    p17_SubmitImage($sectionid);
    return;
  }

  // Process uploaded image and check validity
  $title2 = preg_replace(array('/\.php/', '/\.php([2-6])?/', '/\.pl/', '/\.cgi/'), '', $title);
  if($title2 !== $title)
  {
    echo '<strong>Invalid image upload, operation cancelled (error 1)!<strong><br />';
    return false;
  }

  $DB->query("INSERT INTO {p17_images} (sectionid, activated, filename, author, title, description, datecreated)
    VALUES (%d, %d, '', '%s', '%s', '%s', %d)",
    $sectionid, $activated, $author, $title, $description, TIME_NOW);

  $imageid   = $DB->insert_id();
  $filetype  = isset($image['type']) ? $image['type'] : '';
  $extention = isset($known_photo_types[$filetype]) ? $known_photo_types[$filetype] : '';
  $filename  = $imageid . '.' . $extention;

  if(!@move_uploaded_file($image['tmp_name'], $imagedir.$filename))
  {
    $DB->query("DELETE FROM {p17_images} WHERE imageid = %d", $imageid);
    echo '<strong>Image copy operation failed, please contact site administration!<strong><br />';
    return false;
  }

  // SD313: security check for uploaded image:
  if(!ImageSecurityCheck($imagedir.$filename))
  {
    $DB->query("DELETE FROM {p17_images} WHERE imageid = %d", $imageid);
    echo '<strong>Invalid image upload, operation cancelled (error 2)!<strong><br />';
    return false;
  }

  $DB->query("UPDATE {p17_images} SET filename = '%s' WHERE imageid = %d", $filename, $imageid);

  // Make sure it's readable
  @chmod("$imagedir$filename", 0644);

  if(!empty($p17_settings['auto_create_thumbs']))
  {
    @ini_set('memory_limit', '48M');
    $res = CreateThumbnail($imagedir.$filename, $imagedir."tb_".$filename, $p17_settings['max_thumb_width'], $p17_settings['max_thumb_height'], $p17_settings['square_off_thumbs']);
    if(!empty($res))
    {
      echo $res;
    }
    else if(is_file($imagedir.'tb_'.$filename))
    {
      @chmod($imagedir.'tb_'.$filename, 0644);
    }

    if($p17_settings['create_midsize_images'])
    {
      $res = CreateThumbnail($imagedir.$filename, $imagedir."md_".$filename, $p17_settings['max_midsize_width'], $p17_settings['max_midsize_height'], $p17_settings['square_off_midsize']);
      if(!empty($res))
      {
        echo $res;
      }
      else
      {
        if(is_file($imagedir.'md_'.$filename))
        {
          @chmod($imagedir.'md_'.$filename, 0644);
        }
        if(empty($p17_settings['save_fullsize_images']))
        {
          @unlink("$imagedir$filename");
          // Here we would usually clear the 'filename' column in the DB but unfortunately
          // the thumbnail/midsize code needs it so we'll use file_exists instead
        }
      }
    }
  }
  else
  if(isset($thumbnail) && empty($thumbnail['error']))
  {
    // user submitted thumbnail
    $thumbtype = isset($thumbnail['type']) ? $thumbnail['type'] : '';
    $extention = isset($known_photo_types[$thumbtype]) ? $known_photo_types[$thumbtype] : '';
    $thumbname = $imageid . '.' . $extention;

    // validity already checked, move thumbnail to images folder
    if(!@move_uploaded_file($thumbnail['tmp_name'], $imagedir . 'tb_' . $thumbname))
    {
      echo '<strong>Thumbnail copy operation failed, please contact site administration!<strong><br />';
      return;
    }
    @chmod($imagedir . 'tb_' . $thumbname, 0644);

    // SD313: security check for uploaded image:
    if(!ImageSecurityCheck($imagedir . 'tb_' . $thumbname))
    {
      @unlink($imagedir . 'tb_' . $thumbname);
      echo '<strong>Invalid thumbnail upload!<strong><br />';
    }

  }

  $emails = $p17_settings['image_notification'];

  if(strlen($emails))
  {
    $emails_arr = explode(',', $emails);

    $fullname = $p17_language['notify_email_from'];
    $subject  = $p17_language['notify_email_subject'];
    $message  = $p17_language['notify_email_message'] . EMAIL_CRLF;
    $message .= $p17_language['notify_email_author'] . ' - ' . $author . EMAIL_CRLF;
    $message .= $p17_language['notify_email_title'] . ' - ' . $title . EMAIL_CRLF;
    $message .= $p17_language['notify_email_description'] . ' - ' . $description . EMAIL_CRLF;

    for($i = 0; $i < count($emails_arr); $i++)
    {
      SendEmail(trim($emails_arr[$i]), $subject, $message, $fullname);
    }
  }

  if($activated)
  {
    p17_UpdateImageCounts();
  }
  echo $p17_language['image_submitted'] . '<br /><br />';

  p17_DisplayImages($sectionid, 0);
}



// ############################################################################
// SUBMIT IMAGE
// ############################################################################

function p17_SubmitImage($sectionid)
{
  global $DB, $userinfo, $inputsize, $p17_settings, $p17_language, $sdlanguage;

  echo '
  <form method="post" enctype="multipart/form-data" action="' . RewriteLink('index.php?categoryid=' . PAGE_ID . '&p17_sectionid=' . $sectionid . '&p17_action=insertimage') . '">
  '.PrintSecureToken().'
  <input type="hidden" name="MAX_FILE_SIZE" value="4000000" />'.
  ($userinfo['loggedin'] ? '<input type="hidden" name="p17_author" value="' . $userinfo['username'] . '" />' : '').'
  <table border="0" cellspacing="0" cellpadding="0" summary="layout" width="100%">
  <tr>
    <td width="100">' . $p17_language['image_title'] . '</td>
    <td><input type="text" name="p17_title" value="' . (empty($_POST['p17_title'])?'':$_POST['p17_title']).'" /></td>
  </tr>';

  if(empty($userinfo['loggedin']))
  {
    echo '
    <tr>
      <td>' . $p17_language['your_name'] . '</td>
      <td><input type="text" name="p17_author" value="' .
      (empty($_POST['p17_author'])?'':$_POST['p17_author']).'" maxlength="64" /></td>
    </tr>';
  }

  // max. allowed filesize (PHP): ' . ini_get('upload_max_filesize').'<br />
  // max. allowed post size (PHP): ' . ini_get('post_max_size').'

  echo '
    <tr>
      <td>' . $p17_language['image'] . '</td>
      <td><input name="p17_image" type="file" /></td>
    </tr>';

  // does user need to upload thumbnail?
  if(empty($p17_settings['auto_create_thumbs']))
  {
    echo '
    <tr>
      <td>' . $p17_language['thumbnail'] . '</td>
      <td><input name="p17_thumbnail" type="file" /></td>
    </tr>';
  }

  echo '
    <tr>
      <td>' . $p17_language['description'] . '</td>
      <td><textarea name="p17_description" rows="5" cols="35">' . (empty($_POST['p17_description'])?'':$_POST['p17_description']). '</textarea></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td>';

  DisplayCaptcha();

  echo '
      </td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td><input type="submit" name="p17_Submit" value="'.strip_tags($p17_language['submit_image']).'" />&nbsp;&nbsp;</td>
    </tr>
    </table>
    </form>
    ';

} //p17_SubmitImage


// ############################################################################
// GET IMAGE ORDER
// ############################################################################

function p17_GetImageOrder($friendly_order_name)
{
  switch($friendly_order_name)
  {
    case '2':
    case 'Alphabetically A-Z':
      $order = 'title ASC, imageid ASC';
    break;

    case '3':
    case 'Alphabetically Z-A':
      $order = 'title DESC, imageid DESC';
    break;

    case '4':
    case 'Author Name A-Z':
      $order = 'author ASC, imageid ASC';
    break;

    case '5':
    case 'Author Name Z-A':
      $order = 'author DESC, imageid DESC';
    break;

    case '1':
    case 'Oldest First':
      $order = 'datecreated ASC, imageid ASC';
    break;

    case '0':
    case 'Newest First':
    default:
      $order = 'datecreated DESC, imageid DESC';
    break;
  }

  return $order;
}


// ############################################################################
// DISPLAY IMAGES
// ############################################################################

function p17_DisplayImage($image_id)
{
  global $DB, $p17_language, $p17_settings;

  // get image
  if(!$image_arr = $DB->query_first('SELECT i.*,
    s.activated AS section_activated, s.sorting AS section_sorting
    FROM {p17_images} i
    INNER JOIN {p17_sections} s ON s.sectionid = i.sectionid
    WHERE i.imageid = %d',$image_id))
  {
    return;
  }

  // SD313 2010-08-27 - update image view count
  $DB->query('UPDATE {p17_images} SET viewcount = (viewcount + 1) WHERE imageid = %d', $image_id);

  if(!$image_arr['activated'])
  {
    return;
  }

  if(!$image_arr['section_activated'])
  {
    return;
  }

  $section_images_arr = array();

  // get all images under this section
  $get_section_images = $DB->query('SELECT imageid FROM {p17_images}'.
                                   ' WHERE sectionid = %d AND activated = 1 ORDER BY %s',
                                   $image_arr['sectionid'], p17_GetImageOrder($image_arr['section_sorting']));

  while($section_image_arr = $DB->fetch_array($get_section_images))
  {
    $section_images_arr[] = $section_image_arr['imageid'];
  }

  // Find where the current image is in the list
  $current_image_index_location = array_search($image_arr['imageid'], $section_images_arr);

  // initialize navigation links
  $previous_image_link = $next_image_link = '';

  if($current_image_index_location > 0)
  {
    $previous_image_link = '<a href="' . RewriteLink('index.php?categoryid=' . PAGE_ID . '&p17_sectionid=' . $image_arr['sectionid'] . '&p17_imageid=' . $section_images_arr[$current_image_index_location - 1]) . '">' . $p17_language['previous_image'] . '</a>';
  }

  if($current_image_index_location < count($section_images_arr)-1)
  {
    $next_image_link = '<a href="' . RewriteLink('index.php?categoryid=' . PAGE_ID . '&p17_sectionid=' . $image_arr['sectionid'] . '&p17_imageid=' . $section_images_arr[$current_image_index_location + 1]) . '">' . $p17_language['next_image'] . '</a>';
  }


  echo '
  <div id="image_gallery_image">
  ';

  if($p17_settings['center_images'])
  {
    echo '<center>';
  }

  echo $image_arr['title'] . '<br /><br />';

  $imagetitle = htmlspecialchars($image_arr['title'], ENT_COMPAT);

  $img_md_exists = file_exists(P17_IMAGEPATH.'md_'.$image_arr['filename']);
  $img_full_url  = P17_IMAGEURL . $image_arr['filename'];
  $image_path    = P17_IMAGEPATH . ($img_md_exists ? 'md_' : '') . $image_arr['filename'];
  list($width, $height, $type, $attr) = @getimagesize($image_path);

  if($img_md_exists) echo '<a class="p17_image" href="' . $img_full_url . '" target="_blank">';
  echo '<img title="' . $imagetitle . '" alt="' . $imagetitle . '" src="' .
        P17_IMAGEURL . ($img_md_exists ? 'md_' : '') . $image_arr['filename'] .
        '" width="' . $width . '" height="' . $height . '" />';
  if($img_md_exists) echo '</a>';

  if($p17_settings['center_images'])
  {
    echo '</center>';
  }

  echo '</div>';


  if($p17_settings['center_images'])
  {
    echo '<center>';
  }

  echo '
  <table cellpadding="0" cellspacing="0" summary="layout" width="' . $width . '">
  <tr>
    <td align="left" style="width: 50%; padding-bottom: 5px; padding-top: 7px; padding-right: 20px;">'  .
      (!empty($previous_image_link) ? $previous_image_link : '&nbsp;') . '
    </td>
    <td align="right" style="width: 50%; padding-bottom: 5px; padding-top: 7px;">
        ' .(!empty($next_image_link) ? $next_image_link : '&nbsp;') . '
    </td>
  </tr>
  </table>
  <br />';

  if($p17_settings['center_images'])
  {
    echo '</center>';
  }

  // SD313 2010-08-27 - display image's view count?
  if(!empty($p17_settings['display_view_count']))
  {
    echo $image_arr['viewcount'] . ' ' . $p17_language['views'] . '<br /><br />';
  }

  if(strlen($image_arr['description']))
  {
    echo nl2br($image_arr['description']);
  }

  if($p17_settings['display_author_name'])
  {
    echo iif(strlen($image_arr['description']), '<br /><br />') . $p17_language['submitted_by'] . ' ' . $image_arr['author'];
  }

  if($p17_settings['enable_comments'])
  {
    Comments(17, $image_arr['imageid'], 'index.php?categoryid=' . PAGE_ID . '&p17_sectionid=' . $image_arr['sectionid'] . '&p17_imageid=' . $image_arr['imageid']);
  }
}


// ############################################################################
// DISPLAY IMAGES
// ############################################################################

function p17_DisplayImages()
{
  global $DB, $sdlanguage, $userinfo, $p17_settings, $p17_language;

  $sectionid = GetVar('p17_sectionid', 1, 'whole_number');
  $page = GetVar('p17_start', 1, 'whole_number');

  $section_arr = $DB->query_first('SELECT * FROM {p17_sections} WHERE sectionid = %d',$sectionid);
  if(empty($section_arr['activated']))
  {
    return;
  }

  // display subsections
  $getsubsections = $DB->query("SELECT * FROM {p17_sections} WHERE parentid = $sectionid AND activated = 1 ORDER BY " . p17_GetSectionSort());
  $sectionrows = $DB->get_num_rows($getsubsections);

  if($sectionrows)
  {
    $tr_opened = false;
    $td_count = 0;
    $td_width = round(100 / $p17_settings['section_images_per_row']);

    echo '
    <div id="image_gallery_sections">
    <table border="0" cellpadding="0" cellspacing="0" summary="layout" width="100%">
    ';

    // display subsections
    while($subsection = $DB->fetch_array($getsubsections))
    {
      $td_count++;

      if(!$tr_opened)
      {
        echo '
        <tr>';
        $tr_opened = true;
      }

      echo '
      <td valign="top" width="' . $td_width . '%" style="padding: 0px 15px 15px 0px;">
        <a href="' . RewriteLink('index.php?categoryid=' . PAGE_ID . '&p17_sectionid=' . $subsection['sectionid']) . '">';

      if($subsection['imageid'] > 0)
      {
        $sectionimage = $DB->query_first("SELECT imageid, filename FROM {p17_images} WHERE imageid = %d", $subsection['imageid']);
        echo '<img src="'.P17_IMAGEURL.'tb_'.$sectionimage['filename'].'" alt="'.htmlspecialchars($subsection['name'],ENT_COMPAT).'" />';
      }
      else
      {
        echo '<img alt="'.htmlspecialchars($subsection['name'],ENT_COMPAT).'" src="'.P17_IMAGEURL.
          'defaultfolder.png" width="' . $p17_settings['max_thumb_width'] . '" height="' . ($p17_settings['max_thumb_height']) . '" />';
      }

      echo '</a><br />
      ' . $subsection['name'];

      if($p17_settings['display_section_image_count'])
      {
        echo ' (' . $subsection['imagecount'] . ')';
      }

      if(strlen($subsection['description']))
      {
        echo '<br /><br />
        ' . $subsection['description'];
      }

      echo '
        </td>';

      if($td_count == $p17_settings['section_images_per_row'])
      {
        echo '
        </tr>';
        $tr_opened = false;
        $td_count = 0;
      }
    }

    if($tr_opened)
    {
      echo '  <td colspan="' . ($p17_settings['section_images_per_row'] - $td_count) . '">&nbsp;</td>
            </tr>';
      $tr_opened = false;
    }

    echo '
    </table>
    </div>';

  }  // if section rows

  $items_per_page = (int)$p17_settings['images_per_page'];
  $items_per_page = ($items_per_page < 1) ? 10 : $items_per_page;
  $limit = " LIMIT ".(($page-1)*$items_per_page).','.$items_per_page;
  $total_rows_arr = $DB->query_first("SELECT count(*) as value FROM {p17_images}
                                      WHERE sectionid = $sectionid AND activated = 1");
  $total_rows = $total_rows_arr['value'];

  $get_images = $DB->query('SELECT * FROM {p17_images}'.
                           ' WHERE sectionid = %d AND activated = 1'.
                           ' ORDER BY %s ' . $limit,
                           $sectionid, p17_GetImageOrder($section_arr['sorting']));

  if($total_rows)
  {
    if($sectionrows)
    {
      echo '<br />';
    }

    $tr_opened = false;
    $td_count = 0;
    $td_width = round(100 / $p17_settings['images_per_row']);

    echo '
    <div id="image_gallery_thumbnails">
    <table border="0" cellpadding="0" cellspacing="0" summary="layout" width="100%">
    ';

    while($image = $DB->fetch_array($get_images))
    {
      $td_count++;

      if(!$tr_opened)
      {
        echo '
      <tr>';
        $tr_opened = true;
      }

      echo '
        <td valign="top" width="' . $td_width . '%" style="padding: 0px 15px 15px 0px;">';

      $imagetitle = htmlspecialchars($image['title'],ENT_COMPAT);

      if($p17_settings['image_display_mode'] == 1) // popup
      {
        if($p17_settings['save_fullsize_images'] || !file_exists(P17_IMAGEPATH.'md_'.$image['filename']))
        {
          $filename = $image['filename'];
        }
        else
        {
          $filename = 'md_' . $image['filename'];
        }

        if(is_file(P17_IMAGEPATH.$filename))
        {
          list($sizew, $sizeh) = @getimagesize(P17_IMAGEPATH.$filename);
          if($sizeh > 500) $sizeh = 500;
        }
        else
        {
          $sizew = 300;
          $sizeh = 100;
        }

        $image_link = "href='#' onclick=\"window.open('" . SITE_URL . P17_PLUGINDIR.
                'popup.php?categoryid=' . PAGE_ID .
                '&p17_sectionid=' . $image['sectionid'] . '&p17_imageid=' . $image['imageid'] .
                "', '', 'width=" . ($sizew+100) . ',height=' . ($sizeh+300) .
                ",directories=no,location=no,menubar=no,scrollbars=yes,status=no,toolbar=no,resizable=yes');return false;\" target=\"_blank\"";
      }
      else
      {
        $image_link = 'href="' . RewriteLink('index.php?categoryid=' . PAGE_ID . '&p17_sectionid=' .
                      $image['sectionid'] . '&p17_imageid=' . $image['imageid']) . '"';
      }

      echo '
        <a ' . $image_link . '><img title="' . $imagetitle . '" alt="' . $imagetitle . '" src="' . P17_IMAGEURL . 'tb_'.$image['filename'].'" /></a>';

      echo '
        </td>';

      if($td_count == $p17_settings['images_per_row'])
      {
        echo '
      </tr>';
        $tr_opened = false;
        $td_count = 0;
      }
    }

    if($tr_opened)
    {
      echo '
        <td colspan="' . ($p17_settings['images_per_row'] - $td_count) . '">&nbsp;</td>
      </tr>';
      $tr_opened = false;
    }

    echo '
    </table>
    </div>
    <br />
    ';

    if($total_rows > $items_per_page)
    {
      // pagination
      $p = new pagination;
      $p->parameterName('p17_start');
      $p->items($total_rows);
      $p->limit($items_per_page);
      $p->currentPage($page);
      $p->adjacents(3);
      $p->target(RewriteLink('index.php?categoryid=' . PAGE_ID . '&p17_sectionid=' . $sectionid));
      $p->show();
    }

  } // if image rows

} //p17_DisplayImages


// ############################################################################
// SELECT FUNCTIONS
// ############################################################################

$safeMode = (@ini_get("safe_mode") == 'On') || (@ini_get("safe_mode") == 'on') || (@ini_get("safe_mode") == 1);
if(!$safeMode)
{
  $old_ignore = $GLOBALS['sd_ignore_watchdog'];
  $GLOBALS['sd_ignore_watchdog'] = true;
  @set_time_limit(480);
  $GLOBALS['sd_ignore_watchdog'] = $old_ignore;
}

$p17_language = GetLanguage(17);
$p17_settings = GetPluginSettings(17);

$p17_action = GetVar('p17_action', 'display_images', 'string');
$p17_imageid = GetVar('p17_imageid', 0, 'whole_number');
$p17_sectionid = GetVar('p17_sectionid', 1, 'whole_number');

p17_DisplayHeader($p17_sectionid);

$p17_submit = (!empty($userinfo['pluginsubmitids']) && @in_array(17,$userinfo['pluginsubmitids']));

if(($p17_action == 'insertimage') && $p17_submit && (!empty($p17_settings['allow_root_upload']) || ($p17_sectionid > 1)))
{
  p17_InsertImage();
}
else if(($p17_action == 'submitimage') && $p17_submit && (!empty($p17_settings['allow_root_upload']) || ($p17_sectionid > 1)))
{
  p17_SubmitImage($p17_sectionid);
}
else if($p17_imageid)
{
  p17_DisplayImage($p17_imageid);
}
else
{
  p17_DisplayImages();
}

unset($p17_settings, $p17_language, $p17_action, $p17_submit);
?>