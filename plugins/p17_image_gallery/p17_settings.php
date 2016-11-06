<?php
if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

// ###############################################################################
// GET ACTION
// ###############################################################################
$action = GetVar('action', 'display_images', 'string');

sd_header_add(array(
  'other' => array('
<script type="text/javascript">
if (typeof(jQuery) !== "undefined") {
jQuery(document).ready(function() {
  (function($){
    $(".file-input").ace_file_input();
  })(jQuery);
});
}
</script>
')));

// ###############################################################################
// DISPLAY MENU
// ###############################################################################
echo '
  <ul class="nav nav-pills no-padding-left" id="contentnavigation">
    <li><a href="' . $refreshpage . '&action=display_images">' . AdminPhrase('view_images') . '</a></li>
    <li><a href="' . $refreshpage . '&action=display_image_form">' . AdminPhrase('add_image') . '</a></li>
    <li><a href="' . $refreshpage . '&action=display_batch_import_form">' . AdminPhrase('import_images') . '</a></li>
    <li><a href="' . $refreshpage . '&action=organize_images">' . AdminPhrase('organize_images') . '</a></li>
    <li><a href="' . $refreshpage . '&action=display_settings">' . AdminPhrase('view_settings') . '</a></li>
  </ul>
  <div class="space-10"></div>';

// ###############################################################################
// INCLUDE GALLERY FUNCTIONS
// ###############################################################################

require_once(ROOT_PATH . 'plugins/p17_image_gallery/p17_lib.php');

// ###############################################################################
// GET PLUGIN SETTINGS
// ###############################################################################
$p17_settings = GetPluginSettings(17);

// ###############################################################################
// MAKE SURE PHP DOESN'T TIMEOUT WHEN UPLOADING IMAGES
// ###############################################################################
$safeMode = (@ini_get("safe_mode") == 'On') || (@ini_get("safe_mode") == 'on') || (@ini_get("safe_mode") == 1);
if(!$safeMode)
{
  @set_time_limit(0);
}

// ###############################################################################
// CHECK FOLDER PERMISSIONS
// ###############################################################################
$errors_arr = array();

if(!is_dir(P17_UPLOADPATH))
{
  $errors_arr[] = P17_UPLOADPATH . ' ' . AdminPhrase('folder_not_found');
}

if(!is_dir(P17_IMAGEPATH))
{
  $errors_arr[] = P17_IMAGEPATH . ' ' . AdminPhrase('folder_not_found');
}

if(!is_writable(P17_UPLOADPATH))
{
  $errors_arr[] = P17_UPLOADPATH . ' ' . AdminPhrase('folder_not_writable');
}

if(!is_writable(P17_IMAGEPATH))
{
  $errors_arr[] = P17_IMAGEPATH . ' ' . AdminPhrase('folder_not_writable');
}

if(count($errors_arr))
{
  DisplayMessage($errors_arr, true);
}


// ###############################################################################
// DELETE SECTION
// ###############################################################################

function DeleteSection($sectionid)
{
  global $DB, $refreshpage;

  if($sectionid > 1)
  {
    // delete the section
    $DB->query("DELETE FROM {p17_sections} WHERE sectionid = %d", $sectionid);

    // turn all images offline that were in that section with a default root directory
    $DB->query("UPDATE {p17_images} SET sectionid = 1, activated = 0 WHERE sectionid = %d", $sectionid);

    p17_UpdateImageCounts();
  }

  RedirectPage($refreshpage, AdminPhrase('image_section_deleted'));
}



// ###############################################################################
// DELETE SECTION IMAGES
// ###############################################################################

function DeleteSectionImages($sectionid = -1)
{
  global $DB, $refreshpage;

  // delete all of the images
  $images = array();
  $getimages = $DB->query('SELECT imageid, filename FROM {p17_images} WHERE sectionid = %d', $sectionid);
  while($image = $DB->fetch_array($getimages))
  {
    $images[$image['imageid']] = $image['filename'];
  }
  $DB->query('DELETE FROM {p17_images} WHERE sectionid = %d', $sectionid);
  foreach($images as $imageid => $filename)
  {
    if(file_exists(P17_IMAGEPATH.$filename))
    {
      @unlink(P17_IMAGEPATH.$filename);
    }
    if(file_exists(P17_IMAGEPATH.'tb_'.$filename))
    {
      @unlink(P17_IMAGEPATH.'tb_'.$filename);
    }
    if(file_exists(P17_IMAGEPATH.'md_'.$filename))
    {
      @unlink(P17_IMAGEPATH.'md_'.$filename);
    }
  }
  p17_UpdateImageCounts($sectionid);
}



// ###############################################################################
// INSERT SECTION
// ###############################################################################

function InsertSection()
{
  global $DB, $refreshpage;

  $parentid    = GetVar('parentid', 1, 'whole_number'); // default is root
  $activated   = GetVar('activated', 0, 'bool');
  $name        = GetVar('name', 'Untitled Section', 'string');
  $description = GetVar('description', '', 'string');
  $sorting     = empty($_POST['sorting'])? 0/*'Newest First'*/:(int)$_POST['sorting'];

  $DB->query("INSERT INTO {p17_sections} (parentid, activated, name, description, sorting, datecreated) VALUES
             (%d, %d, '%s', '%s', '%s', %d)", $parentid, $activated, $name, $description, $sorting, TIME_NOW);

  RedirectPage($refreshpage, AdminPhrase('section_created'));
}


// ############################# UPDATE SECTION ###############################

function UpdateSection()
{
  global $DB, $refreshpage;

  $errors_arr = array();

  $sectionid      = GetVar('sectionid', 0, 'whole_number');
  $parentid       = isset($_POST['parentid'])   ? (int)($_POST['parentid'])  : 0;
  $activated      = empty($_POST['activated'])  ? 0 : 1;
  $name           = empty($_POST['name'])       ? '' : (string)$_POST['name'];
  $description    = empty($_POST['description'])? '' : (string)$_POST['description'];
  $sorting        = empty($_POST['sorting'])    ? 0 /*'Newest First'*/ : (int)$_POST['sorting'];
  $imageid        = empty($_POST['imageid'])    ? 0 : intval($_POST['imageid']);
  $movetoid       = empty($_POST['movetoid'])   ? 0 : intval($_POST['movetoid']);

  // delete section
  if(!empty($_POST['deletesection']) && ($sectionid > 1))
  {
    return DeleteSection($sectionid);
  }

  // delete section images
  if(!empty($_POST['deletesectionimages']))
  {
    DeleteSectionImages($sectionid);
  }

  if(!isset($errors) && !empty($sectionid))
  {
    $DB->query("UPDATE {p17_sections} SET parentid = %d,
      activated   = %d,
      name        = '%s',
      description = '%s',
      sorting     = '%s',
      imageid     = %d
      WHERE sectionid = %d",
      $parentid, $activated, $name, $description, $sorting, $imageid, $sectionid);

    if(!empty($movetoid) && ($movetoid != $sectionid))
    {
      $DB->query('UPDATE {p17_images} SET sectionid = %d WHERE sectionid = %d',$movetoid,$sectionid);
      p17_UpdateImageCounts($movetoid);
    }

    p17_UpdateImageCounts($sectionid);
    PrintRedirect($refreshpage, 1);
  }
  else
  {
    PrintErrors($errors);
    DisplaySectionForm(null);
  }
}


// ###############################################################################
// DISPLAY SECTION FORM
// ###############################################################################

function DisplaySectionForm()
{
  global $DB, $refreshpage;

  $sectionid = GetVar('sectionid', 0, 'whole_number');

  if($sectionid)
  {
    $section = $DB->query_first("SELECT * FROM {p17_sections} WHERE sectionid = %d", $sectionid);
  }
  else
  {
    $section = array('parentid'    => GetVar('parentid', 1, 'whole_number'),
                     'activated'   => GetVar('activated', 1, 'bool'),
                     'name'        => GetVar('name', '', 'string'),
                     'description' => GetVar('description', '', 'string'),
                     'sorting'     => GetVar('sorting', 'Newest First', 'string'),
                     'imageid'     => GetVar('imageid', 0, 'whole_number'));
  }

  echo '<form method="post" action="'.$refreshpage.'&action=' . iif($sectionid, 'updatesection&sectionid=' . $sectionid, 'insertsection') . '" class="form-horizontal">';

  StartSection(iif($sectionid, AdminPhrase('update_section'), AdminPhrase('create_section')),'',true,true);
  echo '<div class="form-group">
  			<label class="control-label col-sm-3">' . AdminPhrase('parent_section') . '</label>
          	<div class="col-sm-6">';

  if($sectionid != 1)
  {
    PrintSectionSelection('parentid', $section['parentid'], $sectionid);
  }
  else
  {
    echo AdminPhrase('no_parent_for_root');
  }

  echo '  </div>
        </div>
        <div class="form-group">
  			<label class="control-label col-sm-3">' . AdminPhrase('sort_images_by') . '</label>
          	<div class="col-sm-6">
            <select name="sorting" class="form-control">
              <option value="0" '.(in_array($section['sorting'],array('0','Newest First')) ? 'selected="selected" ':'') .'/>' . AdminPhrase('newest_first') . '</option>
              <option value="1" '.(in_array($section['sorting'],array('1','Oldest First')) ? 'selected="selected" ':'') .'/>' . AdminPhrase('oldest_first') . '</option>
              <option value="2" '.(in_array($section['sorting'],array('2','Alphabetically A-Z')) ? 'selected="selected" ':'') .'/>' . AdminPhrase('alpha_az') . '</option>
              <option value="3" '.(in_array($section['sorting'],array('3','Alphabetically Z-A')) ? 'selected="selected" ':'') .'/>' . AdminPhrase('alpha_za') . '</option>
              <option value="4" '.(in_array($section['sorting'],array('4','Author Name A-Z')) ? 'selected="selected" ':'') .'/>' . AdminPhrase('author_name_az') . '</option>
              <option value="5" '.(in_array($section['sorting'],array('5','Author Name Z-A')) ? 'selected="selected" ':'') .'/>' . AdminPhrase('author_name_za') . '</option>
            </select>
          </div>
        </div>
        <div class="form-group">
  			<label class="control-label col-sm-3">' . AdminPhrase('section') . '</label>
          	<div class="col-sm-6">
            <input type="text" class="form-control" name="name" value="'.$section['name'].'" />
          </div>
        </div>
        <div class="form-group">
  			<label class="control-label col-sm-3">' . AdminPhrase('description') . '</label>
          	<div class="col-sm-6">
            <textarea name="description" class="form-control" rows="5">'.$section['description'].'</textarea>
          </div>
        </div>';

  // Only show section image and move operation if there are actually
  // images within this section already
  if(!$sectionid)
  {
    echo '<input type="hidden" name="imageid"  value="0" />';
    echo '<input type="hidden" name="movetoid" value="0" />';
  }
  $imgcount = $DB->query_first('SELECT COUNT(*) FROM {p17_images} WHERE sectionid = %d', $sectionid);
  $imgcount = $imgcount[0];
  if($imgcount > 0 && $sectionid > 0)
  {
    if($sectionid > 1)
    {
      // Display the images in the section so that the user can choose one to represent the section
      echo '
      <div class="form-group">
  			<label class="control-label col-sm-3">' . AdminPhrase('section_image') . '</label>
          	<div class="col-sm-6">
        <input type="radio" class="ace" name="imageid" value="0" '.(empty($section['imageid'])?'CHECKED':'') . ' /><span class="lbl"></span>
        <img src="'.P17_IMAGEPATH.'defaultfolder.png" align="middle" alt=" " />';

      $getimages = $DB->query("SELECT imageid, filename FROM {p17_images} WHERE sectionid = %d ORDER BY imageid", $sectionid);
      while($image = $DB->fetch_array($getimages))
      {
        echo '
        <input type="radio" class="ace" name="imageid" value="'.$image['imageid'].'" '.($image['imageid']==$section['imageid']?'checked="CHECKED"':'').' /><span class="lbl"></span>
        <img src="'.P17_IMAGEPATH.'tb_' . $image['filename'] .'" align="middle" alt="" />';
      }

      echo '
        </div>
      </div>';
    }

    echo '
    <div class="form-group">
  			<label class="control-label col-sm-3">' . AdminPhrase('move_images') . '</label>
          	<div class="col-sm-6">
      <select name="movetoid" class="form-control" >
        <option value="0" selected />' . AdminPhrase('do_not_move') . '</option>
        ';
    PrintSectionChildren(0, -1, $sectionid);
    echo '
      </select>
      </div>
    </div>';
  }


  echo '<div class="form-group">
  			<label class="control-label col-sm-3">' . AdminPhrase('options') . '</label>
          	<div class="col-sm-6">
            <input type="checkbox" class="ace" name="activated" value="1" ' . (!empty($section['activated']) ? 'CHECKED' : '').' /><span class="lbl"></span> ' . AdminPhrase('display_section_online') . '<br />';

  if($sectionid > 1)
  {
    echo '  <br /><input type="checkbox" class="ace" name="deletesection" value="1" /><span class="lbl red"> ' . AdminPhrase('delete_section') . '</span>';
  }

  if($sectionid)
  {
    echo '  <br /><input type="checkbox" class="ace" name="deletesectionimages" value="1" /><span class="lbl red"> ' . AdminPhrase('delete_images_in_section') . '</span>';
  }

  echo '  </div>
        </div>';

  echo '<div class="center"><button class="btn btn-info" type="submit" value=""><i class="ace-icon fa fa-check"></i> ' . iif($sectionid, AdminPhrase('update_section'), AdminPhrase('create_section')) . '</button>
 		</div>
        </form>';
}


// ############################## DELETE IMAGE ################################

function DeleteImage($imageid, $silent = false)
{
  global $DB, $refreshpage;

  if(!empty($imageid))
  {
    if($getfilename = $DB->query_first('SELECT filename FROM {p17_images} WHERE imageid = ' . (int)$imageid))
    {
      $filename  = $getfilename['filename'];

      $image     = P17_IMAGEPATH . $filename;
      $thumbnail = P17_IMAGEPATH . 'tb_' . $filename;
      $midsize   = P17_IMAGEPATH . 'md_' . $filename;

      if(!is_writable(P17_IMAGEPATH))
      {
        if(!$silent)
        {
          DisplayMessage(AdminPhrase('folder_not_writable'), true);
        }
        return false;
      }
      else
      {
        // delete image
        $DB->query('DELETE FROM {p17_images} WHERE imageid = ' . (int)$imageid);

        // delete image's comments
        DeletePluginComments(17, $imageid);

        if(file_exists($image))     @unlink($image);
        if(file_exists($thumbnail)) @unlink($thumbnail);
        if(file_exists($midsize))   @unlink($midsize);

        p17_UpdateImageCounts();
      }
    }
  }
  if(!$silent)
  {
    PrintRedirect($refreshpage, 1);
  }
  return true;

} //DeleteImage


// ############################## UPDATE IMAGE ################################

function UpdateImage()
{
  global $DB, $refreshpage;

  $deleteimage    = empty($_POST['deleteimage']) ? 0 : 1;
  $imageid        = GetVar('imageid', 0, 'whole_number');
  $sectionid      = GetVar('sectionid', 1, 'whole_number');
  $activated      = empty($_POST['activated']) ? 0 : 1;
  $author         = isset($_POST['author'])?$_POST['author']:'';
  $title          = isset($_POST['title'])?$_POST['title']:'';
  $description    = isset($_POST['description'])?$_POST['description']:'';
  $regenthumbnail = empty($_POST['regenthumbnail']) ? 0 : 1;

  // delete image?
  if($deleteimage && $imageid)
  {
    if(!DeleteImage($imageid, true))
    {
      $DB->query('UPDATE {p17_images} SET activated = 0 WHERE imageid = %d', $imageid);
    }
    p17_UpdateImageCounts();
    PrintRedirect($refreshpage, 1);
    return;
  }

  if(!isset($errors))
  {
    $DB->query("UPDATE {p17_images}
      SET sectionid = %d, activated = %d, author = '%s', title = '%s', description = '%s'
      WHERE imageid = %d",
    $sectionid, $activated, $author, $title, $description, $imageid);

    if($regenthumbnail == 1)
    {
      ReGenerateThumbnail($imageid);
    }

    p17_UpdateImageCounts();

    PrintRedirect($refreshpage, 1);
  }
  else
  {
    PrintErrors($errors);

    DisplayImageForm($imageid);
  }

} //UpdateImage


function ReGenerateThumbnail($imageid)
{
  global $DB, $refreshpage, $p17_settings;

  if(!empty($imageid) && ($filename = $DB->query_first('SELECT filename FROM {p17_images} WHERE imageid = %d', $imageid)))
  {
    $filename = $filename[0];
    @ini_set('memory_limit', '80M');

    $msg = CreateThumbnail(P17_IMAGEPATH.$filename, P17_IMAGEPATH.'tb_'.$filename,
      $p17_settings['max_thumb_width'], $p17_settings['max_thumb_height'],
      $p17_settings['square_off_thumbs']);
    if(isset($msg))
    {
      $errors[] = 'Thumbnail Error: '.$msg;
    }
    if($p17_settings['create_midsize_images'])
    {
      $msg = CreateThumbnail(P17_IMAGEPATH.$filename, P17_IMAGEPATH.'md_'.$filename,
        $p17_settings['max_midsize_width'], $p17_settings['max_midsize_height'],
        $p17_settings['square_off_midsize']);
      if(isset($msg))
      {
        $errors[] = 'MidSize Images Error: '.$msg;
      }
    }
    if(isset($errors))
    {
      DisplayMessage($errors, true);
    }
  }
}


// ############################## INSERT IMAGE ################################

function InsertImage()
{
  global $DB, $refreshpage, $p17_settings;

  $errors_arr = array();

  $image         = isset($_FILES['image']) ? $_FILES['image'] : null;
  $thumbnail     = isset($_FILES['thumbnail']) ? $_FILES['thumbnail'] : null;
  $sectionid     = GetVar('sectionid', 1, 'whole_number');
  $activated     = GetVar('activated', 1, 'bool');
  $author        = GetVar('author', '', 'string');
  $title         = GetVar('title', '', 'string');
  $description   = GetVar('description', '', 'string');

  $valid_image_types = array(
  'image/pjpeg',
  'image/jpeg',
  'image/gif',
  'image/bmp',
  'image/x-png',
  'image/png');

  // check if image was uploaded
  if($image['error'] > 0)
  {
    $errors_arr[] = 'PHP Upload Error: ' . p17_GetFileErrorDescr($image['error']);
  }
  else if(empty($image['size']))
  {
    $errors_arr[] = 'Please select an image to upload.';
  }
  else
  {
    if(!in_array($image['type'], $valid_image_types))
    {
      $errors_arr[] = 'Invalid image type.';
    }

    if(!empty($thumbnail['tmp_name']) && (empty($thumbnail['type']) || !in_array($thumbnail['type'], $valid_image_types)))
    {
      $errors_arr[] = 'Invalid thumbnail type.';
    }

    if($p17_settings['auto_create_thumbs'] && ($image['type'] == 'image/gif') && !function_exists('imageCreateFromGIF'))
    {
      $errors_arr[] =  'Images with the .gif extension are not supported when using GD1 or GD2 to create thumbnails.<br />
                        If you wish to use .gif images, then set "Image Resizing" to "Submit Thumbnails" in the settings page.';
    }
  }

  if(!count($errors_arr))
  {
    // Store the orignal file
    if(!empty($image['tmp_name']) && !is_uploaded_file($image['tmp_name']))
    {
      $errors_arr[] = 'Invalid Image Upload!';
    }
    else
    {
      $DB->query("INSERT INTO {p17_images} (sectionid, activated, filename, author, title, description, datecreated)
                  VALUES($sectionid, $activated, '', '$author', '$title', '$description', " . TIMENOW . ")");

      // List of our known photo types
      $known_photo_types = array(
      'image/pjpeg' => 'jpg',
      'image/jpeg'  => 'jpg',
      'image/gif'   => 'gif',
      'image/bmp'   => 'bmp',
      'image/x-png' => 'png',
      'image/png'   => 'png'
      );

      $imageid   = $DB->insert_id();
      $filetype  = $image['type'];
      $extention = $known_photo_types[$filetype];
      $filename  = $imageid . '.' . $extention;

      $DB->query("UPDATE {p17_images} SET filename = '%s' WHERE imageid = %d", $filename, $imageid);

      if(!@move_uploaded_file($image['tmp_name'], P17_IMAGEPATH.$filename))
      {
        $errors_arr[] = 'Image could not be copied to plugin images folder! Please check permissions!';
      }
      else
      {
        // Collect the size of the image and update the database
        @chmod(P17_IMAGEPATH.$filename, 0644);
        if(!isset($thumbnail))
        {
          @ini_set('memory_limit', '80M');
          $msg = CreateThumbnail(P17_IMAGEPATH.$filename, P17_IMAGEPATH.'tb_'.$filename, $p17_settings['max_thumb_width'], $p17_settings['max_thumb_height'], $p17_settings['square_off_thumbs']);
          if(isset($msg))
          {
            $errors_arr[] = 'Thumbnail Error: '.$msg;
          }
          else
          {
            @chmod(P17_IMAGEPATH.'tb_'.$filename, 0644);
          }

          if($p17_settings['create_midsize_images'])
          {
            $msg = CreateThumbnail(P17_IMAGEPATH.$filename, P17_IMAGEPATH.'md_'.$filename, $p17_settings['max_midsize_width'], $p17_settings['max_midsize_height'], $p17_settings['square_off_midsize']);
            if(isset($msg))
            {
              $errors_arr[] = 'MidSize Image Error: '.$msg;
            }
            else
            {
              @chmod(P17_IMAGEPATH.'md_'.$filename, 0644);
            }
          }

          if(!$p17_settings['save_fullsize_images'] && file_exists(P17_IMAGEPATH.$filename))
          {
            @unlink(P17_IMAGEPATH.$filename);
            // Here we would usually clear the 'filename' column in the DB but unfortunately
            // the thumbnail/midsize code needs it so we'll use file_exists instead
          }
        }
        else
        if(isset($thumbnail) && !empty($thumbnail['tmp_name']))
        {
          if(is_uploaded_file($thumbnail['tmp_name']))
          {
            // user submitted thumbnail
            $thumbtype = $thumbnail['type'];
            $extention = $known_photo_types[$thumbtype];
            $thumbname = 'tb_' . $imageid . '.' . $extention;
            if(!move_uploaded_file($thumbnail['tmp_name'], P17_IMAGEPATH . $thumbname))
            {
              $errors_arr[] = 'Thumbnail could not be copied to plugin images folder! Please check permissions!';
            }
            else
            {
              @chmod(P17_IMAGEPATH . $thumbname, 0644);
            }
          }
          else
          {
            $errors_arr[] = 'Invalid Thumbnail Upload!';
          }
        }
      }
    }

    if(!count($errors_arr))
    {
      RedirectPage($refreshpage, 'Image ' . iif($imageid, 'Updated', 'Added'));
      return;
    }
    $redirectonerror = true;
  }

  DisplayMessage($errors_arr, true);

  if($redirectonerror)
    PrintRedirect($refreshpage, 4);
  else
    DisplayImageForm(NULL);

} //InsertImage


// ############################ DISPLAY SETTINGS ###############################

function DisplaySettings()
{
  global $DB, $pluginid, $refreshpage;

  $refreshpage .= '&action=display_settings';

  PrintPluginSettings($pluginid, array('image_gallery_settings', 'upload_settings', 'thumbnail_settings', 'midsize_settings'), $refreshpage);
}


// ############################# DELETE IMAGES #################################

function UpdateSelectedImages()
{
  global $DB, $refreshpage;

  // Post values
  $imageids = isset($_POST['imageids']) && is_array($_POST['imageids']) ? $_POST['imageids'] : array();
  $moveids  = isset($_POST['imagemoveids']) && is_array($_POST['imagemoveids']) ? $_POST['imagemoveids'] : array();
  $movetoid = isset($_POST['movetoid']) ? (int)$_POST['movetoid'] : null;

  // Process images checked for Deletion:
  $deleteFailed = false;
  for($i = 0; !$deleteFailed && ($i < count($imageids)); $i++)
  {
    if((int)$imageids[$i] == $imageids[$i])
    {
      if(!DeleteImage($imageids[$i], true))
      {
        $DB->query('UPDATE {p17_images} SET activated = 0 WHERE imageid = %d', $imageids[$i]);
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
  // Process images checked for Move to different Section:
  if(!empty($movetoid) && is_numeric($movetoid))
  {
    for($i = 0; $i < count($moveids); $i++)
    {
      if(is_numeric($moveids[$i]))
      {
        $DB->query('UPDATE {p17_images} SET sectionid = %d WHERE imageid = %d', $movetoid, $moveids[$i]);
      }
    } //for
  }

  p17_UpdateImageCounts();

  PrintRedirect($refreshpage, 1);

} //UpdateSelectedImages


function GetSectionSort($sortorder)
{
  $order = '';
  switch($sortorder)
  {
    case '1':
    case 'Oldest First':
      $order = 'datecreated ASC';
      break;

    case '2':
    case 'Alphabetically A-Z':
      $order = 'name ASC';
    break;

    case '3':
    case 'Alphabetically Z-A':
      $order = 'name DESC';
      break;

    case '0':
    case 'Newest First':
    default :
      $order = 'datecreated DESC';
      break;
  }

  return $order;
}


// ###############################################################################
// PRINT SECTION CHILDREN
// ###############################################################################

function PrintSectionChildren($parentid, $selected, $exclude, $indent='', $displaycounts = 0, $sort = null)
{
  global $DB;

  if(!isset($sort))
  {
    $sort = $DB->query_first("SELECT value FROM {pluginsettings} WHERE pluginid = 17 and title = 'Section Sort Order'");
    $sort = GetSectionSort(isset($sort['value']) ? $sort['value'] : '');
  }
  $sort = empty($sort) ? 'name' : $sort;
  $getsections = $DB->query('SELECT sectionid, name FROM {p17_sections} WHERE parentid = %d ORDER BY ' . $sort, $parentid);

  while($sections = $DB->fetch_array($getsections))
  {
    if($exclude != $sections['sectionid'])
    {
      $name = $indent . ' ' . $sections['name'] .($sections['sectionid'] == 1 ? ' (root)' : '');

      echo '<option value="'.$sections['sectionid'].'" '.($selected == $sections['sectionid'] ? 'selected="selected"' : '').">$name</option>";
    }

    PrintSectionChildren($sections['sectionid'], $selected, $exclude, $indent . '- - ', $displaycounts, $sort);
  }
}


// ###############################################################################
// PRINT SECTION SELECTION
// ###############################################################################

function PrintSectionSelection($name, $selected = null, $exclude = null)
{
  echo '<select class="form-control" name="' . $name . '">';

  PrintSectionChildren(0, $selected, $exclude, '', 1);

  echo '</select>';
}


// ###############################################################################
// BATCH IMPORT
// ###############################################################################

function BatchImport()
{
  global $DB, $pluginid, $refreshpage, $p17_settings;

  // get post variables
  $sectionid     = (int)$_POST['sectionid'];
  $activated     = empty($_POST['activated'])    ? 0 : 1;
  $keepfullsize  = empty($_POST['keepfullsize']) ? 0 : 1;
  $moveimages    = empty($_POST['moveimages']) ? 0 : 1;
  $author        = empty($_POST['author']) ? ''  : (string)$_POST['author'];
  $uploadlimit   = empty($_POST['uploadlimit'])  ? 10 : intval($_POST['uploadlimit']);
  $importfolder  = !isset($_POST['importfolder']{0}) ? P17_UPLOADPATH : (string)$_POST['importfolder'];

  if(is_dir(ROOT_PATH . $importfolder))
  {
    $uploaddir = ROOT_PATH . $importfolder;
  }
  else
  {
    $uploaddir = P17_UPLOADPATH;

    DisplayMessage(AdminPhrase('import_location_not_found'), true);
    DisplayBatchImportForm();
    return;
  }

  // error checking
  if(!is_numeric($uploadlimit) || empty($uploadlimit))
  {
    $uploadlimit = 10; // default
  }

  // init vars
  $imagesmoved = 0;
  $errors      = '';
  $datecreated = TIMENOW;

  // List of our known photo types
  $known_photo_types = array(
  'image/pjpeg' => 'jpg',
  'image/jpeg'  => 'jpg',
  'image/gif'   => 'gif',
  'image/bmp'   => 'bmp', // Thumbnail creation not working for all .bmp!
  'image/x-png' => 'png',
  'image/png'   => 'png'
  );

  @ini_set('memory_limit', '80M');

  $images_moved = '';

  $d = dir($uploaddir);
  for($i = 0; (false !== ($entry = $d->read())) && ($i < $uploadlimit); $i++)
  {
    if((substr($entry,0,1)=='.') || (strpos($entry,'.htm')!==false) || !is_file($uploaddir.$entry))
    {
      // No need to actually say anything since this is probably just the "." & ".." being returned
      $uploadlimit++;
      continue;
    }
    $filespecs = explode('.',$entry);
    $extention = strtolower($filespecs[count($filespecs)-1]);
    if((count($filespecs)<2) || !array_search($extention,$known_photo_types))
    {
      $uploadlimit++;
      continue;
    }
    // get image title
    $title = $filespecs[0];

    // else time for all images is the same which screws up everything
    $datecreated++;

    // is it an image? GIF not supported!
    if((@$size = @getimagesize($uploaddir . $entry)))
    {
      $filetype = image_type_to_mime_type($size[2]);

      // addslashes needs to be added to title, title was not cleaned because it came from a $_FILE (not get or post)
      // if its not cleaned, then a single apostrophe will break the sql
      // this only needs to be done for bulk uploading, for single images the
      // title is entered by the user and thus runs through the $_POST cleanup
      $DB->query("INSERT INTO {p17_images} (sectionid, activated, author, title, datecreated) VALUES
                 ($sectionid, $activated, '$author', '" . addslashes($title) . "', $datecreated)");

      $imageid   = $DB->insert_id();
      $extention = $known_photo_types[$filetype];
      $filename  = $imageid . '.' . $extention;

      $DB->query("UPDATE {p17_images} SET filename = '%s' WHERE imageid = %d", $filename, $imageid);
      $imagesmoved++;

      // First copy image for processing
      $res = @copy($uploaddir . $entry, P17_IMAGEPATH.$filename);
      if($res)
      {
        if(SD_GD_SUPPORT && !empty($p17_settings['auto_create_thumbs']))
        {
          $err = CreateThumbnail(P17_IMAGEPATH.$filename, P17_IMAGEPATH.'tb_'.$filename, $p17_settings['max_thumb_width'], $p17_settings['max_thumb_height'], $p17_settings['square_off_thumbs']);
          if(empty($err))
          {
            if($p17_settings['create_midsize_images'])
            {
              CreateThumbnail(P17_IMAGEPATH.$filename, P17_IMAGEPATH.'md_'.$filename, $p17_settings['max_midsize_width'], $p17_settings['max_midsize_height'], $p17_settings['square_off_thumbs']);
            }
            if(empty($keepfullsize))
            {
              @unlink(P17_IMAGEPATH.$filename);
              // Here we would usually clear the 'filename' column in the DB but unfortunately
              // the thumbnail/midsize code needs it so we'll use file_exists instead
            }
            $images_moved .= $entry . ' - ' . AdminPhrase('image_imported') . '<br />';
          }
          else
          {
            @rename(P17_IMAGEPATH.$filename, $uploaddir.$entry);
            $DB->query('DELETE FROM {p17_images} WHERE imageid = %d', $imageid);
            $errors .= $entry . ' ' . AdminPhrase('thumb_error_image_not_imported') . '<br />';
          }
        }
        else
        {
          $images_moved .= $entry . ' - ' . AdminPhrase('image_imported') . '<br />';
        }
        // If the "Move Images" option was set, remove original image
        if(!empty($moveimages))
        {
          @unlink($uploaddir . $entry);
        }
      }
      else
      {
        // move file did NOT work
        // delete db record and report error
        $DB->query("DELETE FROM {p17_images} WHERE imageid = %d", $imageid);
        $errors .= $entry . ' ' . AdminPhrase('image_not_imported') . '<br />';
      }
    }
    else
    {
      $uploadlimit++;  // could have been a gif or non image, so lets try the next image!
    }  // end if is image

  } // end the for loop

  if($imagesmoved > 0)
  {
    p17_UpdateImageCounts($sectionid);
  }

  StartSection(AdminPhrase('import_results'));
  echo '<table class="table table-bordered">
        <tr>
          <td class="td2">
            ' . AdminPhrase('imported_count') . ' ' . $imagesmoved;

  if($errors)
  {
    echo '  <br /><br /><b>' . AdminPhrase('errors_found') . '<br />' . $errors;
  }

  echo '  <br /><br /><a class="btn btn-info" href="' . $refreshpage . '">' . AdminPhrase('return_to_image_gallery') . '</a>
          </td>
        </tr>
        </table>';
  EndSection();
}


// ###############################################################################
// DISPLAY BATCH IMPORT FORM
// ###############################################################################

function DisplayBatchImportForm()
{
  global $DB, $refreshpage, $pluginid, $userinfo, $p17_settings;

  if(!SD_GD_SUPPORT)
  {
    DisplayMessage(AdminPhrase('gd_disabled'), true);
    return;
  }

  if(empty($p17_settings['auto_create_thumbs']))
  {
    DisplayMessage(AdminPhrase('enable_image_resizing_required'), true);
    return;
  }

  echo '<form action="'.$refreshpage.'&action=batchimport" method="post" class="form-horizontal">';

  StartSection(AdminPhrase('import_images'),'',true,true);
  echo '<div class="form-group">
  			<label class="control-label col-sm-3">' . AdminPhrase('import_images_from') . '</label>
			<div class="col-sm-6">
          		<input type="text" class="form-control" name="importfolder" value="plugins/p17_image_gallery/upload/" />
			</div>
        </div>
        <div class="form-group">
  			<label class="control-label col-sm-3">'.AdminPhrase('import_image_count') . '</label>
			<div class="col-sm-6">
				<input type="text" name="uploadlimit" class="form-control" value="10" />
		 </div>
        </div>
        <div class="form-group">
  			<label class="control-label col-sm-3">' . AdminPhrase('import_image_location') . '</label>
			<div class="col-sm-6">';

  PrintSectionSelection('sectionid');

  echo '  </div>
        </div>
        <div class="form-group">
  			<label class="control-label col-sm-3">' . AdminPhrase('import_image_author') . '</label>
			<div class="col-sm-6">
				<input type="text" class="form-control" name="author" value="'.$userinfo['username'].'" />
		  </div>
        </div>
        <div class="form-group">
  			<label class="control-label col-sm-3">' . AdminPhrase('options') . '</label>
			<div class="col-sm-6">
            <input type="checkbox" name="activated" class="ace"     value="1" checked="CHECKED" /><span class="lbl"> ' . AdminPhrase('publish_desc') . '</span><br />
            <input type="checkbox" name="keepfullsize" class="ace"  value="1" '.($p17_settings['save_fullsize_images']?'checked="CHECKED"':'').' /><span class="lbl"> ' . AdminPhrase('keep_images_desc') . '</span><br />
            <input type="checkbox" name="moveimages" class="ace"    value="1" checked="CHECKED" /><span class="lbl"> ' . AdminPhrase('move_imported_images_desc') . '</span>
          </div>
        </div>';

  echo '<div class="center">
  		<button class="btn btn-info" type="submit" value=""><i class="ace-icon fa fa-check"></i> ' . AdminPhrase('import_images') . '</button>
	</div>
        </form>';
}


// ###############################################################################
// ORGANIZE IMAGES
// ###############################################################################

function OrganizeImages()
{
  global $DB, $refreshpage, $pluginid;

  StartSection(AdminPhrase('organize_images'));
  echo '<table class="table table-bordered">
        <tr>
          <td class="td2" width="50%">
            <b>' . AdminPhrase('add_new_section') . '</b><br /><br />
            ' . AdminPhrase('add_new_section_desc') . '
          </td>
          <td class="td3">
            <form method="post" action="' . $refreshpage . '&action=displaysectionform">
            <button class="btn btn-info" type="submit" value="" /><i class="ace-icon fa fa-plus"></i> ' . AdminPhrase('add_new_section') . '</button>
            </form>
          </td>
        </tr>
        <tr>
          <td class="td2">
            <b>' . AdminPhrase('edit_section') . '</b><br /><br />
            ' . AdminPhrase('edit_section_desc') . '
          </td>
          <td class="td3">
            <form method="post" action="' . $refreshpage . '&action=displaysectionform">';

  PrintSectionSelection('sectionid');

  echo '    <button class="btn btn-info btn-sm" type="submit" value="" /><i class="ace-icon fa fa-edit"></i> ' . AdminPhrase('edit_section') . '</button>
            </form>
          </td>
        </tr>
        </table>';
  EndSection();
}


// ###############################################################################
// DISPLAY IMAGE FORM
// ###############################################################################

function DisplayImageForm()
{
  global $DB, $refreshpage, $userinfo, $p17_settings;

  $imageid = GetVar('imageid', 0, 'whole_number');

  if($imageid)
  {
    $image = $DB->query_first("SELECT * FROM {p17_images} WHERE imageid = $imageid");
  }
  else if(isset($_POST['SubmitImage']))
  {
    $image = array("sectionid"     => $_POST['sectionid'],
                   "author"        => $userinfo['username'],
                   "title"         => (isset($_POST['title'])?$_POST['title']:''),
                   "description"   => (isset($_POST['description'])?$_POST['description']:''),
                   "activated"     => (empty($_POST['activated'])?0:1));
  }
  else
  {
    // create empty array
    $image = array("sectionid"     => GetVar('sectionid', 1, 'whole_number'),
                   "author"        => GetVar('username', $userinfo['username'], 'string'),
                   "title"         => '',
                   "description"   => '',
                   "activated"     => 1);
  }

  echo '<form enctype="multipart/form-data" action="' . $refreshpage . '&action=' . iif($imageid, 'updateimage&imageid=' . $imageid, 'insertimage') . '" method="post" name="upload_form" class="form-horizontal">';

  StartSection(iif($imageid, AdminPhrase('edit_image'), AdminPhrase('add_image')),'',true,true);
 
  if($imageid)
  {
    // delete image option
    echo '<div class="form-group">
			<label class="control-label col-sm-3">' . AdminPhrase('delete_image') . '</label>
            <div class="col-sm-6">
              <input type="checkbox" class="ace" name="deleteimage" value="1" /><span class="lbl red"> ' . AdminPhrase('confirm_delete_image') . '</span>
            </div>
          </div>';

    if(strlen($image['filename']) && file_exists(P17_IMAGEPATH . $image['filename']))
    {
      echo '<div class="form-group">
			<label class="control-label col-sm-3">' . AdminPhrase('regenerate_thumb') . '</label>
            <div class="col-sm-6">
                <input type="checkbox" class="ace" name="regenthumbnail" value="1" /><span class="lbl"></span> ' . AdminPhrase('confirm_regenerate_thumb') . '
              </div>
		 </div>';
    }
  }

  echo '<div class="form-group">
			<label class="control-label col-sm-3">' . AdminPhrase('image') . '</label>
            <div class="col-sm-6">';

  if($imageid AND strlen($image['filename']))
  {
    if(file_exists(P17_IMAGEPATH . 'tb_' . $image['filename']))
    {
      echo '<p class="form-control-static"><a href="'.P17_IMAGEPATH . $image['filename'] . '" target="_blank"><img src="' . P17_IMAGEPATH . 'tb_' . $image['filename'] . '" /></a></p>';
    }
    else
    {
      echo AdminPhrase('no_thumb_found') . '<br />';

      if(file_exists(P17_IMAGEPATH . $image['filename']))
      {
        echo '<p class="form-control-static">' . AdminPhrase('use_regenerate_option') . '</p>';
      }
      else
      {
        echo '<p class="form-control-static">'.AdminPhrase('upload_image_again').'</p>';
      }
    }
  }
  else
  {
    echo '<input name="image" type="file" size="50" class="file-input" /><span class="helper-text">' . AdminPhrase('php_max_upload_size') . ' ' .
          min(ini_get('post_max_size'), ini_get('upload_max_filesize')) . '</span>';
  }

  echo '  </div>
		 </div>';

  if(!$imageid)
  {
    echo '<div class="form-group">
			<label class="control-label col-sm-3">' . AdminPhrase('thumbnail') . '</label>
            <div class="col-sm-6">';

    if(!empty($p17_settings['auto_create_thumbs']))
    {
      echo  '<p class="form-control-static">'.AdminPhrase('thumb_created_automatically').'</span>';
    }
    else
    {
      echo '<input name="thumbnail" type="file" size="50" class="file-input" />';
    }

    echo '  </div>
		 </div>';
  }

  echo '<div class="form-group">
			<label class="control-label col-sm-3">' . AdminPhrase('section') . '</label>
            <div class="col-sm-6">';

  PrintSectionSelection('sectionid', $image['sectionid']);

  echo '  </div>
		 </div>
        <div class="form-group">
			<label class="control-label col-sm-3">' . AdminPhrase('author') . '</label>
            <div class="col-sm-6">
				<input type="text" class="form-control" name="author" size="70" value="'.$image['author'].'" />
				</div>
		 </div>
        <div class="form-group">
			<label class="control-label col-sm-3">' . AdminPhrase('title') . '</label>
            <div class="col-sm-6">
				<input type="text" class="form-control" name="title" size="70" value="'.$image['title'].'" />
				</div>
		 </div>
        <div class="form-group">
			<label class="control-label col-sm-3">' . AdminPhrase('description') . '</label>
            <div class="col-sm-6">
            <textarea name="description" class="form-control"  rows="10">'.$image['description'].'</textarea>
          </div>
		 </div>
        <div class="form-group">
			<label class="control-label col-sm-3">' . AdminPhrase('options') . '</label>
            <div class="col-sm-6">
            <input type="checkbox" class="ace" name="activated" value="1" '.(empty($image['activated'])?'':'checked="CHECKED"').' /><span class="lbl"></span> ' . AdminPhrase('publish_desc') . '
          </div>
		 </div>';
  EndSection();

  echo '<div class="center">
  		<button class="btn btn-info" type="submit" value="" /><i class="ace-icon fa fa-check"></i> ' . iif($imageid, AdminPhrase('update_image'), AdminPhrase('add_image')) . '</button>
		</div>
        </form>';
}


// ###############################################################################
// DISPLAY IMAGES
// ###############################################################################

function DisplayImages()
{
  global $DB, $refreshpage;

  $sectionid = GetVar('sectionid', 0, 'whole_number');
  $page = GetVar('page', 1, 'whole_number');

  $items_per_page = 5;
  $limit = " LIMIT ".(($page-1)*$items_per_page).",$items_per_page";
  $total_rows_arr = $DB->query_first("SELECT count(*) as value FROM {p17_images}");
  $total_rows = $total_rows_arr['value'];

  if($sectionid)
  {
    $getimages = $DB->query('SELECT * FROM {p17_images} WHERE sectionid = %d ORDER BY imageid DESC',$sectionid);
  }
  else
  {
    $getimages = $DB->query('SELECT * FROM {p17_images} ORDER BY imageid DESC ' . $limit);
  }

  if(!$DB->get_num_rows($getimages))
  {
    DisplayMessage(AdminPhrase('no_images_found'));
    return;
  }

  echo '<form action="'.$refreshpage.'" method="post" name="deleteimageform">
        <input type="hidden" name="action" value="updateselectedimages" />';

  StartSection(AdminPhrase('images'));
  echo '<table class="table table-bordered table-striped">
  		<thead>
        <tr>
          <th class="td1" width="100">' . AdminPhrase('preview') . '</th>
          <th class="td1">' . AdminPhrase('view_image') . '</th>
          <th class="td1">' . AdminPhrase('section') . '</th>
          <th class="td1">' . AdminPhrase('author') . '</th>
          <th class="td1" width="80">' . AdminPhrase('status') . '</th>
          <th class="td1" width="75">
            <input type="checkbox" checkall="movegroup" class="ace" onclick="javascript: return select_deselectAll (\'deleteimageform\', this, \'movegroup\');" /><span class="lbl"></span> ' . AdminPhrase('move') . '
          </th>
          <th class="td1" width="75">
            <input type="checkbox" checkall="group" class="ace" onclick="javascript: return select_deselectAll (\'deleteimageform\', this, \'group\');" /><span class="lbl"></span> ' . AdminPhrase('delete') . '
          </th>
        </tr>
		</thead>
		<tbody>';

  while($image = $DB->fetch_array($getimages))
  {
    $section = $DB->query_first("SELECT name FROM {p17_sections} WHERE sectionid = %d", $image['sectionid']);

    echo '<tr>
            <td class="td2"><a href="'.$refreshpage.'&action=displayimageform&imageid='.$image['imageid'].'"><img title="'.$image['title'].'" src="'.P17_IMAGEPATH.'tb_' . $image['filename'] .'" alt="No Thumbnail" /></a></td>
            <td class="td2"><a href="'.$refreshpage.'&action=displayimageform&imageid='.$image['imageid'].'"><i class="ace-icon fa fa-file-image-o"></i> ' . $image['title'] . '</a></td>
            <td class="td2"><a href="' . $refreshpage . '&action=display_images&sectionid=' . $image['sectionid'] . '">'.$section['name'].'</a></td>
            <td class="td2">'.$image['author'].'</td>
            <td class="td2">'.(!empty($image['activated'])?"<div class='green'>" . AdminPhrase('online') . "</div>":"<div class='red'>" . AdminPhrase('offline') . "</div>").'</td>
            <td class="td3"><input type="checkbox" class="ace" name="imagemoveids[]" value="'.$image['imageid'].'" checkme="movegroup" /><span class="lbl"></span></td>
            <td class="td3"><input type="checkbox" class="ace" name="imageids[]" value="'.$image['imageid'].'" checkme="group" /><span class="lbl"></span></td>
          </tr>';
  }

  echo '</tbody></table></div>';

  echo '<div class="col-sm-9 align-left">';
  // pagination
  $p = new pagination;
  $p->items($total_rows);
  $p->limit($items_per_page);
  $p->currentPage($page);
  $p->adjacents(3);
  $p->target($refreshpage);
  $p->show();
  
  echo '</div>
		<div class="col-sm-3 align-right no-padding-right">
            ' . AdminPhrase('move_images') . '
            <select name="movetoid" class="input-sm">
            <option value="0" selected />' . AdminPhrase('do_not_move') . '</option>';

  PrintSectionChildren(0, -1, 0);

  echo '    </select>
  		</div>
		</div>
		<div class="center">
  			<button class="btn btn-info" type="submit" value=""><i class="ace-icon fa fa-check"></i> ' . AdminPhrase('update_images') . '</button>
		</div>
        </form>';
		
		
	
}


// ###############################################################################
// SELECT FUNCTION
// ###############################################################################

$function_name = str_replace('_', '', $action);

if(is_callable($function_name))
{
  call_user_func($function_name);
}
else
{
  DisplayMessage("Incorrect Function Call: $function_name()", true);
}

?>