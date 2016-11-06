<?php

// DEFINE CONSTANTS
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');

// INIT PRGM
require(ROOT_PATH . 'includes/init.php');
require(ROOT_PATH.'includes/enablegzip.php');
require(SD_INCLUDE_PATH.'class_sd_media.php');
define('SMILIES_DEFAULT_DIR', SD_INCLUDE_PATH.'images/smileys/');

// ****************************************************************************
// LOAD ADMIN LANGUAGE
// ****************************************************************************
$admin_phrases  = LoadAdminPhrases(3);

// GET parameters like action and path
$action         = strtolower(GetVar('action', 'displaysmilies', 'string'));
$function_name  = str_replace('_', '', $action);

// Invalidate below-root access:
$folderpath = SMILIES_DEFAULT_DIR;

$js = array(
	SITE_URL . ADMIN_STYLES_FOLDER . '/assets/js/bootbox.min.js', // SD400
	);
$sd_head->AddJS($js);

$script = '<script type="text/javascript">
if (typeof(jQuery) !== "undefined") {
jQuery(document).ready(function() {
  (function($){
	 
	$(".file-input").ace_file_input(); 
	
	$("form#updatesmilies").delegate("a#checkall","click",function(e){
      e.preventDefault();
      var ischecked = 1 - parseInt($(this).attr("rel"),10);
      if(ischecked==1) {
        $("form#updatesmilies input.deletesmilie").attr("checked","checked");
        $("form#updatesmilies tr:not(thead tr, tr.core)").addClass("danger");
      } else {
        $("form#updatesmilies input.deletesmilie").removeAttr("checked");
        $("form#updatesmilies tr").removeClass("danger");
      }
      $(this).attr("rel",ischecked);
      return false;
    });
    $("input[type=checkbox].deletesmilie").on("change",function(){
      var tr = $(this).parents("tr");
      $(tr).toggleClass("danger");
    });
	
	$("#doupdatesmilies").click(function(e) {
		e.preventDefault();
    	var $this = $(this);
    	var checked = $("form#updatesmilies input.deletesmilie:checked");
    	if(!checked.length){
			$("#updatesmilies").submit();
      		return false;
    	}
	 
	 bootbox.confirm("'.addslashes(AdminPhrase('smilies_confirm_custom_delete')).'", function(result) {
				if(result) {
				  $("#updatesmilies").submit();
				}
     });
   
  	});
    
  })(jQuery);
});
}
</script>';

$sd_head->AddScript($script);

// CHECK PAGE ACCESS
CheckAdminAccess('media');

// DISPLAY ADMIN HEADER
$load_wysiwyg = 0;
DisplayAdminHeader(array('Media','menu_media_view_smilies'));

// ############################# IM_CheckExtention #############################

function IM_CheckExtention($imagename, $allowed_extentions)
{
  if((strlen($imagename) < 5) || (substr($imagename,0, 1) == '.'))
  {
    return false;
  }
  $extension = '';
  $dotpos = strrpos($imagename, '.');
  if($dotpos !== false)
  {
    $extension = strtolower(substr($imagename, ($dotpos+1)));
  }

  if((substr($imagename,0, 1) == '.') || !@in_array($extension, $allowed_extentions))
  {
    return false;
  }
  return true;

} //IM_CheckExtention


// ############################## UPDATE SMILIES ###############################

function UpdateSmilies()
{
  global $DB, $sdlanguage;

  if(!CheckFormToken())
  {
    PrintRedirect('smilies.php',1,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />');
    return;
  }

  $smilieids       = isset($_POST['smilieids']) ? $_POST['smilieids'] : null;
  $smilietitles    = isset($_POST['smilietitles']) ? $_POST['smilietitles'] : null;
  $smilietexts     = isset($_POST['smilietexts']) ? $_POST['smilietexts'] : null;
  $smilieimages    = isset($_POST['smilieimages']) ? $_POST['smilieimages'] : null;
  $deletesmilieids = isset($_POST['deletesmilieids']) ? $_POST['deletesmilieids'] : null;

  // first update smilies
  if(isset($smilietitles) && is_array($smilieids))
  for($i = 0, $sc = count($smilieids); $i < $sc; $i++)
  {
    $DB->query("UPDATE {smilies} SET title = '%s',".
               " text = '%s', image = '%s'".
               ' WHERE smilieid = %d',
      $DB->escape_string($smilietitles[$i]),
      $DB->escape_string($smilietexts[$i]),
      $DB->escape_string($smilieimages[$i]), $smilieids[$i]);
  } //for

  // now delete smilies (if user selected to delete any)
  if(!empty($deletesmilieids) && is_array($deletesmilieids))
  {
    $deletesmilieids = implode(',', $deletesmilieids);
    $DB->query('DELETE FROM {smilies} WHERE smilieid IN (%s) AND is_core = 0', $deletesmilieids);
  }

  PrintRedirect('smilies.php', 1);

} //UpdateSmilies


// ############################### UPLOAD SMILIE ###############################

function UploadSmilie()
{
  global $DB, $sdlanguage;

  if(!CheckFormToken())
  {
    PrintRedirect('smilies.php',1,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />');
    return;
  }

  $smilietitle = isset($_POST['smilietitle']) ? $_POST['smilietitle'] : '';
  $smiliecode  = isset($_POST['smiliecode'])  ? $_POST['smiliecode']  : '';
  $smilieimage = isset($_FILES['smilieimage'])? $_FILES['smilieimage'] : null;

  $errors = array();
  if(!isset($smilieimage))
  {
    $errors[] = AdminPhrase('media_upload_error_invalid_image');
    PrintErrors($errors, 'Upload Error');
    DisplaySmilies();
    return false;
  }

  //$smiliedir = dirname(__FILE__) . SMILIES_DEFAULT_DIR;
  $smiliedir = SMILIES_DEFAULT_DIR;
  $imagename = $smilieimage['name'];

  // check if image was uploaded
  if(($smilieimage['error'] > 0) && ($smilieimage['error'] != 4))
  {
    switch ($smilieimage['error'])
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
    $errors[] = 'PHP ERROR: ' . $error;
  }
  else
  if(empty($smilieimage['size']) || ($smilieimage['error'] == 4))
  {
    $errors[] = AdminPhrase('media_upload_error_no_image');
  }
  else
  {
    if(!in_array($smilieimage['type'], SD_Media_Base::$known_image_types))
    {
      $errors[] = AdminPhrase('media_upload_error_invalid_image');
    }

    // lets make sure the extension is correct
    if(!IM_CheckExtention($smilieimage['name'], SD_Media_Base::$valid_image_extensions))
    {
      $errors[] = AdminPhrase('media_upload_error_invalid_image');
    }
  }

  if(empty($errors))
  {
    // check if there is already the same smilie
    if($smilierepeat = $DB->query_first("SELECT title, image FROM {smilies} WHERE image = '%s'",
                                        $DB->escape_string($smilieimage['name'])))
    {
      $errors[] = 'The smilie filename "' . $smilieimage['name'] . '" already exists in the "images/smilies" folder.<br />
                   Solution: Rename the smilie you are trying to upload.';
    }
    else
    {
      // Store the orignal file
      if( SD_Image_Helper::FilterImagename($imagename) &&
          SD_Image_Helper::FilterImagename($smilieimage['tmp_name']) &&
          !is_executable($smilieimage['tmp_name']) &&
          is_uploaded_file($smilieimage['tmp_name']) )
      {
        $imagename = basename($smilieimage['name']);
        if(@move_uploaded_file($smilieimage['tmp_name'], $smiliedir . $imagename))
        {
          @chmod($smiliedir . $imagename, 0644);
          // final attempt to make sure it's a real image:
          if(!@getimagesize($smiliedir . $imagename))
          {
            $errors[] = AdminPhrase('media_upload_error_invalid_image');
            @unlink($smiliedir . $imagename);
          }
          else
          {
            // Security check
            // SD_Image in class_sd_media.php
            if(!SD_Media_Base::ImageSecurityCheck(true, $smiliedir, $imagename))
            {
              $errors[] = AdminPhrase('media_upload_error_invalid_image');
              @unlink($smiliedir . $imagename);
            }
            else
            {
              // image has been uploaded, now save smilie in database
              $DB->query('INSERT INTO {smilies} (title, text, image)'.
                         " VALUES ('%s', '%s', '%s')",
                         $DB->escape_string($smilietitle),
                         $DB->escape_string($smiliecode),
                         $DB->escape_string($imagename));
              clearstatcache();
            }
          }
        }
        else
        {
          $errors[] = AdminPhrase('media_upload_error_not_writable') . ' includes/images/smilies';
        }
      }
      else
      {
        $errors[] = AdminPhrase('media_upload_error_invalid_image');
      }
    }
  }

  if(!empty($errors))
  {
    PrintErrors($errors, 'Uploading Errors');
    DisplaySmilies();
  }
  else
  {
    PrintRedirect('smilies.php', 1);
  }

} //UploadSmilie


// ############################## DISPLAY SMILIES ##############################

function DisplaySmilies() //SD342
{
  global $DB, $mainsettings, $usersystem;

  echo '<h3 class="header blue lighter">'. AdminPhrase('add_smilie'). '</h3>
  <form enctype="multipart/form-data" method="post" action="smilies.php" class="form-horizontal">
  '.PrintSecureToken().'
  <input type="hidden" name="MAX_FILE_SIZE" value="51200" />
  <input type="hidden" name="action" value="uploadsmilie" />
  <div class="form-group">
  	<label class="control-label col-sm-2">Smilie Title</label>
	<div class="col-sm-6">
   	<input type="text" name="smilietitle" class="form-control" />
	</div>
</div>
    <div class="form-group">
  	<label class="control-label col-sm-2">Smilie Code</label>
	<div class="col-sm-6"><input type="text" name="smiliecode" class="form-control" />
	</div>
	</div>
  <div class="form-group">
  	<label class="control-label col-sm-2">Smilie Image</label>
	<div class="col-sm-6">
	<input name="smilieimage" type="file"  class="file-input"/>
	</div>
</div>
  <div class="center">
  	<button type="submit" class="btn btn-info" value="" /><i class="ace-icon fa fa-plus"></i> Upload Smilie</button>
</div>
 
  </form>
  <div class="space-20"></div>';


  // get all smilie filenames
  if(false !== ($handle = @opendir(SMILIES_DEFAULT_DIR)))
  {
    while(false !== ($file = @readdir($handle)))
    {
      if( (substr($file,0,1) !== '.') &&
          IM_CheckExtention($file, SD_Media_Base::$valid_image_extensions) &&
          SD_Image_Helper::FilterImagename($file) )
      {
        $smiliefiles[] = $file;
      }
    }//while
    @closedir($handle);
  }
  if(!empty($smiliefiles)) sort($smiliefiles);

  PrintSection('Smilies');
  echo '
  <form method="post" action="smilies.php" name="deletesmilies" id="updatesmilies" class="form-inline">
  '.PrintSecureToken().'
  <input type="hidden" name="action" value="updatesmilies" />
  <table class="table table-bordered table-striped">
  <thead>
  <tr>
    <th class="tdrow1">Smilie</th>
    <th class="tdrow1">Title</th>
    <th class="tdrow1">Code</th>
    <th class="tdrow1">Image File</th>
    <th class="center" width="75"><a id="checkall" rel="0" title="'.AdminPhrase('common_delete').'" href="#" onclick="javascript:return false;"><i class="ace-icon fa fa-trash-o red bigger-130"></i></a></th>
  </tr>
  </thead>
  </tbody>';

  // get all smilies in the database
  if($smilies = $DB->query('SELECT * FROM {smilies} ORDER BY title ASC'))
  {
    $scount = $DB->get_num_rows($smilies);
    if($scount > 0)
    {
      for($x = 0; $smilie = $DB->fetch_array($smilies,null,MYSQL_ASSOC); $x++)
      {
        echo '
        <tr '.(empty($smilie['is_core'])?'':'class="core"').'>
          <td class="center">
            <input type="hidden" name="smilieids[]" value="' . $smilie['smilieid'] . '" />
            <img src="'.SD_INCLUDE_PATH.'images/smileys/' . $smilie['image'] . '" alt="?" width="16" height="16" />
          </td>
          <td class="tdrow3"><input class="col-sm-10" type="text" name="smilietitles[]" value="' . CleanFormValue($smilie['title']) . '" /></td>
          <td class="tdrow2"><input  class="col-sm-10" type="text" name="smilietexts[]"  value="' . CleanFormValue($smilie['text'])  . '" /></td>
          <td class="tdrow3">
            <select class="col-sm-10" name="smilieimages[]" onchange="update_smiley(this.options[selectedIndex].value, \'' . $x  . '\');">
            ';

        for($i = 0, $sc = count($smiliefiles); $i < $sc; $i++)
        {
          echo '<option value="' . CleanFormValue($smiliefiles[$i]) . '" ' . ($smilie['image'] == $smiliefiles[$i]? 'selected="selected"': '') . '>' . $smiliefiles[$i] . '</option>
            ';
        }

        echo '
            </select>
          </td>
          <td class="center">
            '.(empty($smilie['is_core'])?'<input type="checkbox" class="ace deletesmilie" name="deletesmilieids[]" value="' .
            $smilie['smilieid'] . '" checkme="group" /><span class="lbl"></span>':'<i class="ace-icon fa fa-minus-circle red bigger-120"></i>').'
          </td>
        </tr>';
      }
      $DB->free_result($smilies);
      echo '</table>
	  <div class="center">
        <button type="submit" id="doupdatesmilies" class="btn btn-info" value="" /><i class="ace-icon fa fa-check"></i> Update Smilies</button>
       </div>';
    }
    else
    {
      echo '
        <tr>
          <td class="tdrow1" bgcolor="#FCFCFC" colspan="5" align="center"><strong>No smilies available!</strong></td>
        </tr>';
    }
  }
  echo '
      
      </form></div>';

} //DisplaySmilies


// ############################################################################
// SELECT FUNCTION
// ############################################################################

if(is_callable($function_name))
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

DisplayAdminFooter();
