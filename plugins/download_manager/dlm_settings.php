<?php
// +---------------------------------------------+
// | Download Manager plugin for Subdreamer      |
// +---------------------------------------------+
// | v2.2.0, September 2013                      |
// | Maintainer: 2007-2013 tobias                |
// | This version requires Subdreamer 3.6+!      |
// +---------------------------------------------+

if(!defined('IN_PRGM') || !defined('IN_ADMIN') || empty($pluginid)) return false;

// ####################### DETERMINE CURRENT DIRECTORY ########################

$dlm_currentdir = sd_GetCurrentFolder(__FILE__);

// ################### INCLUDE DOWNLOAD MANAGER LIBRARIES #####################
if(!@include(ROOT_PATH . 'plugins/'.$dlm_currentdir.'/class_dlm_tools.php'))
{
  return false;
}

if(!class_exists('DownloadManagerTools'))
{
  return false;
}

require_once(SD_INCLUDE_PATH.'class_sd_tags.php');

// ############################################################################
// ADD SOME JAVASCRIPT IF NOT CALLED BY AJAX
// ############################################################################
if(!Is_Ajax_Request())
{
  echo '
<script language="javascript" type="text/javascript">
//<![CDATA[
  function ConfirmAction(msg) {
    if(confirm(msg)) { return true; } else { return false; }
  }
//]]>
</script>
';
} // DO NOT REMOVE!

// ########################### FUNCTION WRAPPERS ##############################

if(!function_exists('mime_content_type') && !sd_safe_mode())
{
  function mime_content_type($f) {
    if(is_file($f) && file_exists($f))
    {
      $f = @escapeshellarg($f);
      return trim("file -bi $f");
    }
    return '';
  }
}

// ############################ SETTINGS CLASS ###############################
// ############################ SETTINGS CLASS ###############################

class DownloadManagerSettings
{
  var $imagename    = '';
  var $username     = '';
  var $ipaddress    = '';
  var $settingspage = null;
  var $currentpage  = 0;
  var $ordertype    = '';
  var $pagesize     = 0;
  var $searchby     = '';
  var $searchtext   = '';
  var $sectionid    = 0;
  var $fileid       = 0;
  var $versionid    = -1;
  var $importdir    = '';
  var $errors       = array();
  var $DLM_Init     = false;
  var $uploader     = null;
  var $allow_bbcode = false;

  private $PLUGINID = 0;

  function DownloadManagerSettings()
  {
    global $bbcode, $mainsettings, $pluginid, $dlm_currentdir;

    $this->PLUGINID = $pluginid;
    $this->DLM_Init = !empty($this->PLUGINID);

    // ######################## DIRECTORY CHECKS ##############################

    // Prevent caching of directory function results
    clearstatcache();

    if(empty(DownloadManagerTools::$filesdir))
    {
      DownloadManagerTools::$filesdir = 'plugins/'.$dlm_currentdir.'/ftpfiles/';
    }
    // Check file storage location directory on server (default: ftpfiles)
    if(strpos(DownloadManagerTools::$filesdir,':')===false)
    {
      DownloadManagerTools::$filesdir = DownloadManagerTools::dlm_FixPath(DownloadManagerTools::$filesdir);
    }
    if(is_dir(DownloadManagerTools::$filesdir))
    {
      if(!is_writable(DownloadManagerTools::$filesdir))
      {
        $this->errors[] = AdminPhrase('dlm_files_upload_directory'). ' ('.
                          DownloadManagerTools::$filesdir.') '.
                          AdminPhrase('dlm_directory_not_writable');
      }
    }
    else
    {
      $this->errors[] = AdminPhrase('dlm_files_upload_directory').' ('.
                        DownloadManagerTools::$filesdir.') '.
                        AdminPhrase('dlm_directory_not_exists');
    }
    //echo 'Folder for files: '.DownloadManagerTools::$filesdir.'<br />';

    if(empty(DownloadManagerTools::$imagesdir))
    {
      DownloadManagerTools::$imagesdir = 'plugins/'.$dlm_currentdir.'/images/';
    }
    if(strpos(DownloadManagerTools::$imagesdir,':')===false)
    {
      DownloadManagerTools::$imagesdir = DownloadManagerTools::dlm_FixPath(DownloadManagerTools::$imagesdir);
    }
    if(is_dir(DownloadManagerTools::$imagesdir))
    {
      if(!is_writable(DownloadManagerTools::$imagesdir))
      {
        $this->errors[] = AdminPhrase('dlm_images_upload_directory'). ' ('.
                          DownloadManagerTools::$imagesdir.') '.
                          AdminPhrase('dlm_directory_not_writable');
      }
    }
    else
    {
      $this->errors[] = AdminPhrase('dlm_images_upload_directory').' ('.
                        DownloadManagerTools::$imagesdir.') '.
                        AdminPhrase('dlm_directory_not_exists');
    }

    $this->allow_bbcode = isset($bbcode) && ($bbcode instanceof BBCode) && !empty($mainsettings['allow_bbcode']);

    if(!empty($this->errors))
    {
      DownloadManagerTools::PrintErrors($this->errors, AdminPhrase('dlm_configuration_error'));
    }

    return $this->DLM_Init;
  }

// ########################## DELETE SECTION IMAGES ##########################

// Remove image or thumbnail name of a given section and remove file
// "$oldimagename" should *only* be filename - without any path/foldername.
function DeleteSectionImage($sectionid, $is_thumbnail, $oldimagename)
{
  global $DB;

  // Valid section?
  if(is_numeric($sectionid))
  {
    // delete image or thumbnail name in section
    if($is_thumbnail)
    {
      $DB->query('UPDATE {p'.$this->PLUGINID."_sections} SET thumbnail = '' WHERE sectionid = %d",$sectionid);
    }
    else
    {
      $DB->query('UPDATE {p'.$this->PLUGINID."_sections} SET image = '' WHERE sectionid = %d",$sectionid);
    }

    // delete image file from images directory
    $oldimagename = DownloadManagerTools::$imagesdir . $oldimagename;
    if(is_file($oldimagename) && file_exists($oldimagename))
    {
      @unlink($oldimagename);
    }
    PrintRedirect(REFRESH_PAGE . "&amp;action=displaysectionform&amp;sectionid=$sectionid", 1);
  }
} //DeleteSectionImage


// ################# RETURN LINKED SECTION NAMES FOR A FILE ###################

// Returns the names of all sections in which a given file resides, each entry
// linked to the section edit page
function DisplayFileSectionsLinks($fileid)
{
  global $DB;

  $sectionnames = '';
  if(is_numeric($fileid) && ($fileid > 0))
  {
    if($sections = $DB->query('SELECT name, s.sectionid FROM {p'.$this->PLUGINID.'_sections} s'.
                              ' LEFT JOIN {p'.$this->PLUGINID.'_file_sections} fs ON fs.sectionid = s.sectionid'.
                              ' WHERE fs.fileid = %d ORDER BY name',$fileid))
    {
      while($section = $DB->fetch_array($sections,null,MYSQL_ASSOC))
      {
        $sectionnames .= '<a title="'.htmlspecialchars(AdminPhrase('dlm_menu_edit_section'),ENT_QUOTES).
          '" href="' . REFRESH_PAGE . '&amp;action=displaysectionform&amp;sectionid='.
          $section['sectionid'] . '">' . $section['name'] . '</a>, '; // Do not remove trailing comma or blank!
      }
      $DB->free_result($sections);
    }

    if(strlen($sectionnames) > 1)
    {
      $sectionnames = substr($sectionnames, 0, -2);
    }
    else
    {
      // This should never happen
      $sectionnames = '<div style="color:red"><strong>'.AdminPhrase('dlm_no_section_set').'</strong></div>';
    }
  }

  return $sectionnames;

} //DisplayFileSectionsLinks


// ############### UPDATE ONLINE STATUS OF ALL SELECTED FILES ################

// Update files online status as set by admin on main page
function UpdateFilesOnlineStatus()
{
  global $DB, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage(REFRESH_PAGE,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  $onlinestatus = GetVar('onlinestatus',array(),'array',true,false);
  $onlinestatus_old = GetVar('onlinestatus_old',array(),'array',true,false);

  // Go through list and update any *changed* status:
  foreach($onlinestatus AS $fileid => $status)
  {
    // Update status
    if(isset($onlinestatus_old[$fileid]) && ($status != (int)($onlinestatus_old[$fileid])))
    {
      $DB->query('UPDATE {p'.$this->PLUGINID.'_files} SET activated = %d'.
                 ' WHERE fileid = %d', $status, $fileid);
    }
  } //for

  PrintRedirect(REFRESH_PAGE . ($this->sectionid?"&amp;action=displayfiles&amp;sectionid=$this->sectionid":''), 1);

} //UpdateFilesOnlineStatus


// ########################### DISPLAY IP DOWNLOADS ###########################

// Display downloads for a given IP address
function DisplayIPDownloads($ipaddress)
{
  global $DB;

  if(!$ipaddress)
  {
    return;
  }
  $sql = 'SELECT d.*, f.title'.
         ' FROM {p'.$this->PLUGINID.'_file_downloads} d'.
         ' INNER JOIN {p'.$this->PLUGINID.'_files} f ON d.fileid = f.fileid'.
         " WHERE d.ipaddress = '".htmlspecialchars($ipaddress).
         "' ORDER BY d.downloaddate DESC";

  if($getdownloads = $DB->query($sql))
  {
    PrintSection(AdminPhrase('dlm_downloads_by_ip_address').' '.htmlspecialchars($ipaddress));

    echo '<table width="100%" border="0" cellpadding="5" cellspacing="0">
      <tr>
        <td class="tdrow1">'.AdminPhrase('dlm_download_date').'</td>
        <td class="tdrow1">'.AdminPhrase('dlm_username').'</td>
        <td class="tdrow1">'.AdminPhrase('dlm_filename').'</td>
      </tr>
      ';

    while($download = $DB->fetch_array($getdownloads,null,MYSQL_ASSOC))
    {
      echo '<tr>
        <td class="tdrow2">'.DisplayDate($download['downloaddate'],'Y-m-d h:i:s').'&nbsp;</td>
        <td class="tdrow2"><a href="'.REFRESH_PAGE.'&amp;action=displayuserdownloads&amp;username='.$download['username'].'">'.
          $download['username'].'&nbsp;</a></td>
          <td class="tdrow2"><a href="'.REFRESH_PAGE.'&amp;action=displayfiledownloads&amp;fileid='.$download['fileid'].'">'.
            $download['title'].'</a></td>
      </tr>';
    }
    $DB->free_result($getdownloads);
    echo '
      </table>
      </form>';
  }
  else
  {
    PrintSection(AdminPhrase('dlm_no_downloads_for_ip').' '.$ipaddress);
  }
  EndSection();

} //DisplayIPDownloads


// ########################### DISPLAY USER DOWNLOADS #########################

// Display of downloads for a given username
function DisplayUserDownloads($username)
{
  global $DB;

  if(!strlen($username))
  {
    return;
  }

  $sql = 'SELECT d.*, f.title'.
         ' FROM {p'.$this->PLUGINID.'_file_downloads} d'.
         ' INNER JOIN {p'.$this->PLUGINID.'_files} f ON d.fileid = f.fileid'.
         " WHERE d.username = '".htmlspecialchars($username)."'".
         ' ORDER BY d.downloaddate DESC';

  if($getdownloads = $DB->query($sql))
  {
    PrintSection(AdminPhrase('dlm_downloads_by_user').' \'' . $username . '\'');
    echo '<table width="100%" border="0" cellpadding="5" cellspacing="0">
      <tr>
        <td class="tdrow1">'.AdminPhrase('dlm_download_date').'</td>
        <td class="tdrow1">'.AdminPhrase('dlm_ip_address').'</td>
        <td class="tdrow1">'.AdminPhrase('dlm_filename').'</td>
      </tr>
      ';
    while($download=$DB->fetch_array($getdownloads,null,MYSQL_ASSOC))
    {
      echo '<tr>
        <td class="tdrow2">'.DisplayDate($download['downloaddate'],'Y-m-d h:i:s').'&nbsp;</td>
        <td class="tdrow2"><a href="'.REFRESH_PAGE.'&amp;action=displayipdownloads&amp;ipaddress='.$download['ipaddress'].'">'.
          $download['ipaddress'].'&nbsp;</a></td>
          <td class="tdrow2"><a href="'.REFRESH_PAGE.'&amp;action=displayfiledownloads&amp;fileid='.$download['fileid'].'">'.
            $download['title'].'</a></td>
            </tr>';
    }
    $DB->free_result($getdownloads);
    echo '</table>
          </form>';
  }
  else
  {
    PrintSection(AdminPhrase('dlm_no_downloads_for_user').' '.$username);
  }
  EndSection();

} //DisplayUserDownloads


// ########################### DISPLAY LATEST DOWNLOADS #######################

// Display of most recent downloads (default limit: 10 entries)
function DisplayLatestDownloads()
{
  global $DB;

  $numentries = DownloadManagerTools::GetSetting('display_latest_downloads');
  if(!empty($numentries) && ($numentries > 0))
  {
    if($getdownloads = $DB->query("SELECT d.username, d.ipaddress, d.downloaddate,
        IF(f.fileid>0,f.title,'".AdminPhrase('dlm_file_unknown')."') as title, f.fileid
        FROM {p".$this->PLUGINID."_file_downloads} d
        LEFT OUTER JOIN {p".$this->PLUGINID."_files} f ON f.fileid = d.fileid
        ORDER BY d.downloaddate DESC LIMIT " . $numentries))
    {
      PrintSection("Latest ".$numentries." Downloads");
      echo '<table class="table table-striped table-bordered">
	  <thead>
        <tr>
           <th class="tdrow1" width="150">'.AdminPhrase('dlm_download_date').'</th>
           <th class="tdrow1">'.AdminPhrase('dlm_username').'</th>
           <th class="tdrow1" width="120">'.AdminPhrase('dlm_ip_address').'</th>
           <th class="tdrow1">'.AdminPhrase('dlm_filename').'</th>
        </tr>
		</thead>';
      while($download = $DB->fetch_array($getdownloads,null,MYSQL_ASSOC))
      {
        echo '<tr>
          <td class="tdrow2">'.DisplayDate($download['downloaddate'],'Y-m-d  h:i:s').'&nbsp;</td>
                <td class="tdrow2"><a href="'.REFRESH_PAGE.'&amp;action=displayuserdownloads&amp;username='.$download['username'].'">'.
                  $download['username'].'&nbsp;</a></td>
          <td class="tdrow2"><a href="'.REFRESH_PAGE.'&amp;action=displayipdownloads&amp;ipaddress='.$download['ipaddress'].'">'.
            $download['ipaddress'].'&nbsp;</a></td>
          <td class="tdrow2"><a href="'.REFRESH_PAGE.'&amp;action=displayfiledownloads&amp;fileid='.$download['fileid'].'">'.
            $download['title'].'</a>&nbsp;</td>
              </tr>';
      }
      $DB->free_result($getdownloads);
      echo '</table>';
    }
    else
    {
      PrintSection(AdminPhrase('dlm_no_recent_downloads'));
    }
    EndSection();
  }

} //DisplayLatestDownloads


// ######################## DELETE ALL SELECTED FILES #########################

function DeleteFiles()
{
  global $DB;

  // Get list of id's of "filedelids" array
  $fileids = GetVar('filedelids',array(),'array',true,false);

  // Go through list and delete selected files:
  foreach($fileids AS $fileid)
  {
    if($fileid > 0)
    {
      if($image = $DB->query_first('SELECT thumbnail, image FROM {p'.$this->PLUGINID.'_files} WHERE fileid = %d LIMIT 1',$fileid))
      {
        // Remove image file
        if(strlen($image['image']) && is_file(DownloadManagerTools::$imagesdir . $image['image']))
        {
          $imagepath = DownloadManagerTools::$imagesdir . $image['image'];
          @unlink($imagepath);
        }

        // Remove image thumbnail file
        if(strlen($image['thumbnail']) && is_file(DownloadManagerTools::$imagesdir . $image['thumbnail']))
        {
          $thumbpath = DownloadManagerTools::$imagesdir . $image['thumbnail'];
          @unlink($thumbpath);
        }
      }

      // Delete all file versions
      if($getfiles = $DB->query('SELECT filetype, storedfilename, filename'.
                                ' FROM {p'.$this->PLUGINID.'_file_versions} WHERE fileid = %d',$fileid))
      {
        while($file = $DB->fetch_array($getfiles,null,MYSQL_ASSOC))
        {
          $isRemoteFilename = ($file['filetype']=='FTP') && DownloadManagerTools::dlm_CheckURI($file['filename']);
          $isRemoteStoredFilename = ($file['filetype']=='FTP') && DownloadManagerTools::dlm_CheckURI($file['storedfilename']);

          if(!$isRemoteStoredFilename)
          if(is_file(DownloadManagerTools::$filesdir . $file['storedfilename']))
          {
            $ftpfile = DownloadManagerTools::$filesdir . $file['storedfilename'];
            @unlink($ftpfile);
          }

          if(!$isRemoteFilename)
          if(is_file(DownloadManagerTools::$filesdir . $file['filename']))
          {
            $ftpfile = DownloadManagerTools::$filesdir . $file['filename'];
            @unlink($ftpfile);
          }
        } //while
        $DB->free_result($getfiles);
      }

      // Delete file versions
      $DB->query('DELETE FROM {p'.$this->PLUGINID.'_file_versions} WHERE fileid = %d',$fileid);

      // Delete file
      $DB->query('DELETE FROM {p'.$this->PLUGINID.'_files} WHERE fileid = %d',$fileid);

      // Delete file-to-sections
      $DB->query('DELETE FROM {p'.$this->PLUGINID.'_file_sections} WHERE fileid = %d',$fileid);
    }
  } //for

  PrintRedirect(REFRESH_PAGE . ($this->sectionid?"&action=displayfiles&amp;sectionid=$this->sectionid":''), 1);

} //DeleteFiles


function DeleteFile($fileid, $versionid)
{
  global $DB;

  // If this is the current version, try and change the currentversion
  $file = $DB->query_first('SELECT currentversionid FROM {p'.$this->PLUGINID.'_files} WHERE fileid = %d',$fileid);

  if($file['currentversionid'] == $versionid)
  {
    $lastver = $DB->query_first('SELECT versionid FROM {p'.$this->PLUGINID.'_file_versions}'.
               ' WHERE fileid = %d AND versionid <> %d'.
               ' ORDER BY datecreated DESC LIMIT 1',
               $fileid,$versionid);

    // If it's the only version, don't let them delete it
    if(empty($lastver['versionid']))
    {
      DownloadManagerTools::PrintErrors(array("You cannot delete this file version because it the only one associated with this file.<br />You must delete the entire file if you wish to remove it."), "Delete Error");
      $this->DisplayFileForm($fileid);
      return;
    }
    else
    {
      // Update the version id
      $DB->query('UPDATE {p'.$this->PLUGINID.'_files} SET currentversionid = %d WHERE fileid = %d',$lastver['versionid'],$fileid);
    }
  }

  // Get version Info
  $filever = $DB->query_first('SELECT filetype, storedfilename, filename'.
             ' FROM {p'.$this->PLUGINID.'_file_versions}'.
             ' WHERE fileid = %d AND versionid = %d',$fileid,$versionid);

  $isRemoteFilename = ($filever['filetype']=='FTP') && DownloadManagerTools::dlm_CheckURI($filever['filename']);
  $isRemoteStoredFilename = ($filever['filetype']=='FTP') && DownloadManagerTools::dlm_CheckURI($filever['storedfilename']);

  if(!$isRemoteStoredFilename)
  {
    $ftpfile = DownloadManagerTools::$filesdir . $filever['storedfilename'];
    if(is_file($ftpfile))
    {
      @unlink($ftpfile);
    }
  }

  if(!$isRemoteFilename)
  {
    $ftpfile = DownloadManagerTools::$filesdir . $filever['filename'];
    if(($filever['filetype']=='FTP') && is_file($ftpfile))
    {
      // it's an ftp file, delete it
      @unlink($ftpfile);
    }
  }

  // Delete file versions
  $DB->query('DELETE FROM {p'.$this->PLUGINID.'_file_versions} WHERE fileid = %d AND versionid = %d',$fileid,$versionid);

  PrintRedirect(REFRESH_PAGE . "&action=displayfileform&amp;load_wysiwyg=0&amp;fileid=$fileid", 1);

} //DeleteFile


// ############################## DELETE IMAGES ###############################

function DeleteFileImage($fileid, $imagename)
{
  global $DB;

  // Security check
  if(is_numeric($fileid) && (strlen($imagename)>3))
  {
    // Delete imagename in db
    if(substr($imagename, 0, 2) == 'tb') // thumbnail
    {
      $DB->query('UPDATE {p'.$this->PLUGINID."_files} SET thumbnail = '' WHERE fileid = %d",$fileid);
    }
    else
    {
      $DB->query('UPDATE {p'.$this->PLUGINID."_files} SET image = '' WHERE fileid = %d",$fileid);
    }

    // delete image in server
    if(is_file(DownloadManagerTools::$imagesdir . $imagename))
    {
      $image = DownloadManagerTools::$imagesdir . $imagename;
      @unlink($image);
    }
  }

  PrintRedirect(REFRESH_PAGE . "&action=displayfileform&amp;load_wysiwyg=0&amp;fileid=$fileid", 1);

} //DeleteFileImage


// ############################## INSERT SECTION ##############################

function InsertSection()
{
  global $DB, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage(RewriteLink(REFRESH_PAGE),'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  // Get posted values
  $parentid    = GetVar('parentid', 0, 'whole_number',true,false);
  $activated   = GetVar('activated', 0, 'whole_number',true,false);
  $name        = GetVar('name', '', 'string',true,false);
  $description = GetVar('description', '', 'string',true,false);
  $sorting     = GetVar('sorting', '', 'string',true,false);
  $section_groups = GetVar('section_usergroups', array(), 'array',true,false);
  $section_users  = GetVar('section_users', array(), 'array',true,false);
  $image        = empty($_FILES['image']['name'])?'':$_FILES['image'];
  $thumbnail    = empty($_FILES['thumbnail']['name'])?'':$_FILES['thumbnail'];
  $fsprefix     = GetVar('fsprefix', '', 'string',true,false);
  $count_files  = empty($_POST['count_files'])?0:1; //v2.4.0

  if(strlen($name) == 0)
  {
    $errors[] = 'Please enter a name for the section';
  }

  if(count($section_groups) > 0 && count($section_users) > 0)
  {
    $errors[] = 'Access can only be restricted to either usergroups OR usernames, not both';
  }

  if(!isset($errors))
  {
    $groups = empty($section_groups) ? '' : implode('|', $section_groups);
    $groups = (($groups == '') || ($groups == '||')) ? $groups : '|'.$groups.'|';
    $users  = empty($section_users) ? '' : implode('|',$section_users);
    $users  = (($users == '') || ($users == '||')) ? $users : '|'.$users.'|';
    $DB->query('INSERT INTO {p'.$this->PLUGINID."_sections}
      (sectionid, parentid, activated, name, description, sorting,
      image, thumbnail, fsprefix, access_groupids, access_userids, count_files)
      VALUES
      (NULL, '$parentid', '$activated', '$name', '$description', '$sorting',
      '', '', '$fsprefix', '$groups', '$users', $count_files)");
    $sectionid = $DB->insert_id();

    // Check whether a thumbnail was uploaded
    if($thumbnail && !empty($thumbnail['name']))
    {
      if(DownloadManagerTools::dlm_CheckImageFile($thumbnail,$errors,$extension)) // dlmanlib.php
      {
        // use "tbs_" as a prefix for section thumbnails
        $thumbname = 'tbs_' . $sectionid . '.' . $extension;
        if(@is_uploaded_file($thumbnail['tmp_name']) &&
           @move_uploaded_file($thumbnail['tmp_name'], DownloadManagerTools::$imagesdir.$thumbname))
        {
          DownloadManagerTools::dlm_ReplaceSectionImage('thumbnail', $sectionid,
            DownloadManagerTools::$imagesdir, $thumbname, true);
          $DB->query('UPDATE {p'.$this->PLUGINID."_sections}
          SET thumbnail = '$thumbname' WHERE sectionid = %d",$sectionid);
        }
        else
        {
          $errors[] = 'Thumbnail file invalid';
        }
      }
      else
      {
        $errors[] = 'Thumbnail file invalid';
      }
    }

    // Check whether an image was uploaded
    if($image && !empty($image['name']))
    {
      if(DownloadManagerTools::dlm_CheckImageFile($image,$errors,$extension))
      {
        // use "s" as a prefix for section images
        $imagename = 's' . $sectionid . '.' . $extension;
        if(@is_uploaded_file($image['tmp_name']) &&
           @move_uploaded_file($image['tmp_name'], DownloadManagerTools::$imagesdir.$imagename))
        {
          DownloadManagerTools::dlm_ReplaceSectionImage('image', $sectionid,
            DownloadManagerTools::$imagesdir, $imagename, true);
          $DB->query('UPDATE {p'.$this->PLUGINID."_sections} SET image = '$imagename' WHERE sectionid = %d",$sectionid);
        }
        else
        {
          $errors[] = 'Image file invalid';
        }
      }
      else
      {
        $errors[] = 'Image file invalid';
      }
    }

  }

  // Frequent check to remove (empty) entries "||" from permissions
  $DB->query('UPDATE {p'.$this->PLUGINID."_sections} SET access_groupids = '' where access_groupids = '||'");
  $DB->query('UPDATE {p'.$this->PLUGINID."_sections} SET access_userids = '' where access_userids = '||'");

  if(!empty($errors))
  {
    DownloadManagerTools::PrintErrors($errors);
    $this->DisplaySectionForm(0); // 0 = errors exist
  }
  else
  {
    PrintRedirect(REFRESH_PAGE, 1);
  }

} //InsertSection


// ############################# DELETE SECTION ###############################

function DeleteSection($sectionid)
{
  global $DB;

  // Security check
  if(is_numeric($sectionid))
  {
    // Delete the section's "thumbnail" and "image"
    if($oldimage = $DB->query_first('SELECT thumbnail, image FROM {p'.$this->PLUGINID.'_sections} WHERE sectionid = %d',$sectionid))
    {
      if(!empty($oldimage['thumbnail']))
      {
        $oldimagepath = DownloadManagerTools::$imagesdir . $oldimage['thumbnail'];
        @unlink($oldimagepath);
      }
      if(!empty($oldimage['image']))
      {
        $oldimagepath = DownloadManagerTools::$imagesdir . $oldimage['image'];
        @unlink($oldimagepath);
      }
    }

    // FIRST: Delete the section and file-to-section relations
    $DB->query('DELETE FROM {p'.$this->PLUGINID.'_sections} WHERE sectionid = %d',$sectionid);

    // 2'ND: Remove the file links for this section.
    // If this was the only section for this file, set it offline
    $files = $DB->query('SELECT fileid FROM {p'.$this->PLUGINID.'_file_sections} WHERE sectionid = %d',$sectionid);
    while($file = $DB->fetch_array($files,null,MYSQL_ASSOC))
    {
      $DB->result_type = MYSQL_ASSOC;
      $count = $DB->query_first('SELECT COUNT(sectionid) scount'.
                                ' FROM {p'.$this->PLUGINID.'_file_sections}'.
                                ' WHERE fileid = %d AND sectionid <> %d',
                                $file['fileid'], $sectionid);
      if(empty($count['scount']))
      {
        $DB->query('UPDATE {p'.$this->PLUGINID.'_files} SET activated = 0 WHERE fileid = %d',$file['fileid']);
      }
    }
    $DB->free_result($files);

    // Delete file-to-section relations
    $DB->query('DELETE FROM {p'.$this->PLUGINID.'_file_sections} WHERE sectionid = %d',$sectionid);

  }
  PrintRedirect(REFRESH_PAGE, 1);

} //DeleteSection


// ############################## UPDATE SECTION ##############################

function UpdateSection()
{
  global $DB, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage(REFRESH_PAGE,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  $sectionid   = empty($_POST['sectionid'])?0:intval($_POST['sectionid']);
  $parentid    = empty($_POST['parentid'])?($sectionid==1?0:1):intval($_POST['parentid']);
  $activated   = empty($_POST['activated'])?0:1;
  $name        = empty($_POST['name'])?'':$_POST['name'];
  $description = empty($_POST['description'])?'':$_POST['description'];
  $sorting     = empty($_POST['sorting'])?'Newest First':$_POST['sorting'];
  $section_groups = isset($_POST['section_usergroups'])?$_POST['section_usergroups']:null;
  $section_users  = isset($_POST['section_users'])?$_POST['section_users']:null;
  $fsprefix       = empty($_POST['fsprefix'])?'':$_POST['fsprefix'];
  $count_files = empty($_POST['count_files'])?0:1; //v2.4.0

  // Added "image" and "thumbnail" file processing
  $image        = isset($_FILES['image'])?$_FILES['image']:null;
  $thumbnail    = isset($_FILES['thumbnail'])?$_FILES['thumbnail']:null;

  if($sectionid < 1)
  {
    return;
  }
  elseif(($sectionid > 1) && !empty($_POST['deletesection'])) // Delete section?
  {
    $this->DeleteSection($sectionid);
    return;
  }
  else
  {
    if(strlen(rtrim(ltrim($name))) == 0)
    {
      $errors[] = 'You must enter a <strong>Section Name</strong> for this Section';
    }

    if(!isset($errors))
    {
      $groups = empty($section_groups) ? '' : implode('|', $section_groups);
      $groups = (($groups == '') || ($groups == '||')) ? $groups : '|'.$groups.'|';
      $users  = empty($section_users) ? '' : @implode('|',$section_users);
      $users  = (($users == '') || ($users == '||')) ? $users : '|'.$users.'|';
      $DB->query('UPDATE {p'.$this->PLUGINID."_sections}
        SET parentid = %d, activated = %d, name = '%s', description = '%s', fsprefix = '%s',
        sorting  = '%s', access_groupids = '$groups', access_userids  = '$users',
        count_files = %d
        WHERE sectionid = %d",
        $parentid, $activated, $name, $description, $fsprefix,
        $sorting, $count_files, $sectionid);

      // init image names
      $imagename = '';
      $thumbname = '';

      // Check whether a thumbnail was uploaded
      if($thumbnail && strlen($thumbnail['name']))
      {
        if(DownloadManagerTools::dlm_CheckImageFile($thumbnail,$errors,$extension)) // dlmanlib.php
        {
          $thumbname = 'tbs_' . $sectionid . '.' . $extension;
          DownloadManagerTools::dlm_ReplaceSectionImage('thumbnail', $sectionid, DownloadManagerTools::$imagesdir, $thumbname);
          copy($thumbnail['tmp_name'], DownloadManagerTools::$imagesdir . $thumbname);
        }
        else
        {
          $errors[] = 'Thumbnail file invalid';
        }
      }

      // Check whether an image was uploaded
      if($image && strlen($image['name']))
      {
        if(DownloadManagerTools::dlm_CheckImageFile($image,$errors,$extension)) // dlmanlib.php
        {
          $imagename = 's' . $sectionid . '.' . $extension;
          DownloadManagerTools::dlm_ReplaceSectionImage('image', $sectionid, DownloadManagerTools::$imagesdir, $imagename);
          copy($image['tmp_name'], DownloadManagerTools::$imagesdir . $imagename);
        }
        else
        {
          $errors[] = 'Image file invalid';
        }
      }
    } //no errors

    // Frequent check to remove (empty) entries "||" from permissions
    $DB->query('UPDATE {p'.$this->PLUGINID."_sections} SET access_groupids = '' where access_groupids = '||'");
    $DB->query('UPDATE {p'.$this->PLUGINID."_sections} SET access_userids = '' where access_userids = '||'");
  }

  if(isset($errors) && (count($errors)>0))
  {
    DownloadManagerTools::PrintErrors($errors);
    $this->DisplaySectionForm($sectionid);
  }
  else
  {
    PrintRedirect(REFRESH_PAGE . "&amp;action=displaysectionform&amp;load_wysiwyg=0&amp;sectionid=$sectionid", 1);
  }

} //UpdateSection


// ########################### DISPLAY SECTION FORM ###########################

function DisplaySectionForm($sectionid)
{
  global $DB;

  $accessgroups = array();
  $accessusers = array();

  if(isset($sectionid) && ($sectionid > 0))
  {
    // gather section information
    $section = $DB->query_first('SELECT * FROM {p'.$this->PLUGINID.'_sections} WHERE sectionid = %d',$sectionid);

    $accessgroups = explode('|',$section['access_groupids']);
    $accessusers  = explode('|',$section['access_userids']);

    echo '<h2 class="header blue lighter">Edit Section</h2>';
  }
  else
  if(isset($sectionid) && ($sectionid == 0))
  {
    // Create section array from form data
    $section = array(
    "parentid"      => (empty($_POST['parentid'])?($sectionid==1?0:1):intval($_POST['parentid'])),
    "activated"     => (empty($_POST['activated'])?0:1),
    "name"          => (empty($_POST['name'])?'':$_POST['name']),
    "description"   => (empty($_POST['description'])?'':$_POST['description']),
    "sorting"       => (empty($_POST['sorting'])?'Newest First':$_POST['sorting']),
    'image'         => '',
    'thumbnail'     => '',
    'fsprefix'      => (empty($_POST['fsprefix'])?'':$_POST['fsprefix']),
    'access_groupids' => array(),
    'access_userids'  => array(),
    'count_files'   => (empty($_POST['count_files'])?0:1));

    echo '<h2 class="header blue lighter">Create New Section</h2>';
  }
  else
  {
    // create empty section array
    $section = array(
    "parentid"      => 1,
    "activated"     => 1,
    "name"          => '',
    "description"   => '',
    "sorting"       => 'Newest First',
    'image'         => '',
    'thumbnail'     => '',
    'fsprefix'      => '',
    'access_groupids' => array(),
    'access_userids'  => array(),
    'count_files'   => 1);

    echo '<h2 class="header blue lighter">Create New Section</h2>';
  }

  echo '
  <form method="post" enctype="multipart/form-data" action="'.REFRESH_PAGE.'" class="form-horizontal">
  <input type="hidden" name="sectionid" value="'.$sectionid.'" />
  '.PrintSecureToken();
  
   if(isset($sectionid))
  {
    echo '
    <div class="form-group">
  	<label class="control-label col-sm-3 red">Delete Section:</strong></label>
	<div class="col-sm-6">';

    if($sectionid == 1)
    {
      echo '<p class="form-control-static red">The Root Section can not be deleted.</p>';
    }
    else
    {
      echo '<input type="checkbox" class="ace" name="deletesection" value="1" /><span class="lbl red"> Delete this Section ('.$sectionid.')?</span>';
    }

    echo '</div>
	</div>';
  }
  
  echo'
  <div class="form-group">
  	<label class="control-label col-sm-3">Section Name:</label>
	<div class="col-sm-6">
   		<input type="text" name="name" maxlength="128" class="form-control" value="'.htmlspecialchars($section['name']).'" />
    </div>
</div>';


  echo '
    <div class="form-group">
  	<label class="control-label col-sm-3">Section is sub-section of</label>
	<div class="col-sm-6">';

  if($sectionid == 1)
  {
    echo '<p class="form-control-static">The Root Section is the parent of all sections and can not be a subsection.</p>';
  }
  else
  {
    DownloadManagerTools::dlm_PrintSectionSelection(
        array('formId'            => 'parentid',
              'selectedSectionId' => $section['parentid'],
              'excludedSectionId' => $sectionid,
              'extraStyle'        => 'style="min-width: 300px;"'
              ));
  }
  $realftpdir = (substr(DownloadManagerTools::$filesdir,0,strlen(ROOT_PATH))==ROOT_PATH)?substr(DownloadManagerTools::$filesdir,strlen(ROOT_PATH)):DownloadManagerTools::$filesdir;
  echo '</div>
  	</div>
  <div class="form-group">
  	<label class="control-label col-sm-3">Display Order for Files</label>
	<div class="col-sm-6">
          <select name="sorting" class="form-control">
          <option '.($section['sorting'] == "Newest First"?       "selected=\"selected\"": "") .'>Newest First</option>
          <option '.($section['sorting'] == "Oldest First"?       "selected=\"selected\"": "") .'>Oldest First</option>
          <option '.($section['sorting'] == "Alphabetically A-Z"? "selected=\"selected\"": "") .'>Alphabetically A-Z</option>
          <option '.($section['sorting'] == "Alphabetically Z-A"? "selected=\"selected\"": "") .'>Alphabetically Z-A</option>
          <option '.($section['sorting'] == "Author Name A-Z"?    "selected=\"selected\"": "") .'>Author Name A-Z</option>
          <option '.($section['sorting'] == "Author Name Z-A"?    "selected=\"selected\"": "") .'>Author Name Z-A</option>
        </select>
      </div>
	</div>
    <div class="form-group">
  	<label class="control-label col-sm-3">Section Filesystem Prefix</label>
	<div class="col-sm-6">
        <input type="text" name="fsprefix" maxlength="128" class="form-control" value="'.$section['fsprefix'].'" /><br />
      <strong>Default: empty</strong> = uploaded files are stored in the global DLM upload directory "<strong>'.$realftpdir.'</strong>".<br />
      Specifiy a relative path to a subdirectory within it where files for this section
      are stored.<br />Please make sure to include a trailing backslash!<br /><br />
      <strong>Important:</strong> only for <strong>initial</strong> uploads of files in this section a prefix
      of "<strong>myfolder/addons/</strong>" would result in the upload directory name of<br />
      "<strong>'.DownloadManagerTools::dlm_FixPath($realftpdir.'myfolder/addons/').'</strong>".</td>
    </div>
	</div>
	 <div class="form-group">
  	<label class="control-label col-sm-3">Options</label>
	<div class="col-sm-6">
      <input type="checkbox" class="ace" name="activated" value="1" '.
        (empty($section['activated'])?'':'checked="checked"').
        ' /> <span class="lbl">Section is online</span><br /><br />';

  if($sectionid != 1)
  {
    //v2.4.0: new option for files counting
    echo '
   <input type="checkbox" class="ace" name="count_files" value="1" '.
        (empty($section['count_files'])?'':' checked="checked"').' /> <span class="lbl">'.AdminPhrase('dlm_sections_count_files').' '.AdminPhrase('dlm_sections_count_files_descr').'</span>
	</div>
	</div>
	
    <div class="form-group">
  		<label class="control-label col-sm-3">Description:</label>
		<div class="col-sm-6">';
    PrintWysiwygElement('description', $section['description']);
   
    echo '</div>
	</div>';
  }
  else
  {
	  echo '</div>
	  	</div>';
  }

  echo '<div class="form-group">
  	<label class="control-label col-sm-3">Access Permissiosn</label>
     <div class="col-sm-6">';

  $this->PrintUserGroupSelection('section_usergroups[]', $accessgroups, 'section_users[]');
  echo '&nbsp;';
  $this->PrintUserSelection('section_users[]', $accessusers, 'section_usergroups[]');

  echo '<span class="helper-text"> You can restrict access to this section to specific usergroups or users. You may only select one or more usergroups <strong>OR</strong> one or more users but you cannot use both.
      <br />(Use [CTRL/Shift+Click] to select multiple usergroups or users)</span></div>
  </div>
  <div class="form-group">
  	<label class="control-label col-sm-3">Thumbnail:</label>
     <div class="col-sm-6">
      <input type="file" name="thumbnail" size="60" class="ace-file" />';

  if(!empty($section['thumbnail']))
  {
    // Added preview of current thumbnail
    $img_file = DownloadManagerTools::$imageurl . $section['thumbnail'];
    $extratext = '<a href="' . $img_file . '" target="_blank">View Thumbnail</a> -
      <a href="'.REFRESH_PAGE.'&amp;action=deletesectionthumb&amp;sectionid='.
      $sectionid.'&amp;imagename='.$section['thumbnail'].'">Delete Thumbnail</a>';
    DownloadManagerTools::dlm_DisplayImageDetails($section['thumbnail'], $img_file, false, $extratext);
  }

  echo '  </div></div>
       <div class="form-group">
  	<label class="control-label col-sm-3">Image:</label>
     <div class="col-sm-6">
            <input type="file" name="image" size="60" class="ace-file" />';

  if(!empty($section['image']))
  {
    // Added preview of current image; thumbnail creation possible
    $img_file = DownloadManagerTools::$imageurl . $section['image'];
    $extratext = '<a href="'  . $img_file . '" target="_blank">View Image</a> -
      <a href="'.REFRESH_PAGE.'&amp;action=deletesectionimage&amp;sectionid='.
      $sectionid.'&amp;imagename='.urlencode($section['image']).'">Delete Image</a> -
      <a href="'.REFRESH_PAGE.'&amp;action=thumbnailform&amp;sectionid='.
      $sectionid.'&amp;imagename='.urlencode($section['image']).'">Create Thumbnail</a>';
    DownloadManagerTools::dlm_DisplayImageDetails($section['image'], $img_file, false, $extratext);
  }

  echo '</div>
 	</div>';

  // FOOTER AREA
  echo '
  <div class="center">
      <input type="hidden" name="action" value="'.($sectionid?'update':'insert').'section" />
      <button type="submit" class="btn btn-info" value="" /><i class="ace-icon fa '.($sectionid?'fa-check':'fa-plus').' bigger-120"></i> '.($sectionid?'Update':'Insert').' Section</button>
   </div>
  </form>
  <br />
  ';


  if(!empty($sectionid))
  {
    $this->DisplayFiles($sectionid,false);
  }

} //DisplaySectionForm


// ######################### UPDATE FILE SECTIONS #############################

function UpdateFileSections($fileid, $sectionids)
{
  global $DB;

  if(!empty($fileid))
  {
    // First delete any existing file-to-sections entries
    $DB->query('DELETE FROM {p'.$this->PLUGINID."_file_sections} WHERE fileid = '$fileid'");

    // Insert new row for file
    for($i = 0; $i < count($sectionids); $i++)
    {
      $DB->query('INSERT INTO {p'.$this->PLUGINID.'_file_sections} VALUES(%d, %d)',$fileid,$sectionids[$i]);
    }
  }
} //UpdateFileSections


// ################################ UPDATE FILE ################################

function UpdateFile($fileid, $useDefaults=false)
{
  global $DB, $sdlanguage, $userinfo;

  if(!CheckFormToken())
  {
    RedirectPage(REFRESH_PAGE,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  $errors = array();

  // Preset variables
  $filedata      = '';
  $filename      = '';
  $filesize      = '';
  $filetype      = '';
  $image         = '';
  $thumbnail     = '';
  $storedfilename= '';
  $embeddable    = 0;
  $isremote      = false;

  // ############################# Get posted values ##########################

  // File Related
  $sectionids     = GetVar('sectionids',null,'array',true,false);
  $activated      = empty($_POST['activated'])?0:1;
  $title          = GetVar('title','','string',true,false);
  $author         = GetVar('author','','string',true,false);
  $description    = GetVar('description','','string',true,false);
  $uniqueid       = GetVar('uniqueid','','string',true,false);
  $downloadcount  = GetVar('downloadcount',0,'whole_number',true,false);
  $dateadded      = GetVar('dateadded',null,'string',true,false);
  $dateupdated    = GetVar('dateupdated',null,'string',true,false);
  $datestart      = (isset($_POST['datestart']) && ($_POST['datestart']!=''))?DownloadManagerTools::dlm_CreateUnixTimestamp($_POST['datestart']):TIME_NOW;
  $dateend        = (isset($_POST['dateend']) && !empty($_POST['dateend']))?DownloadManagerTools::dlm_CreateUnixTimestamp($_POST['dateend']):0;
  $filestore      = empty($_POST['filestore'])?0:1;
  $fsprefix       = GetVar('fsprefix','','string',true,false);
  $maxdownloads   = GetVar('maxdownloads',0,'whole_number',true,false);
  $maxtype        = empty($_POST['maxtype'])?0:1;
  $licensed       = empty($_POST['licensed'])?0:1;
  $image          = isset($_FILES['image'])?$_FILES['image']:null;
  $thumbnail      = isset($_FILES['thumbnail'])?$_FILES['thumbnail']:null;
  $standalone     = empty($_POST['standalone'])?0:$_POST['standalone'];
  $resetrating    = empty($_POST['resetrating'])?0:1;
  $tags           = GetVar('tags','','string',true,false);
  $auto_thumbnail = GetVar('auto_thumbnail','','natural_number',true,false);
  $audio_class    = GetVar('audio_class', '', 'html', true, false);
  $video_class    = GetVar('video_class', '', 'html', true, false);
  $media_autoplay = GetVar('media_autoplay', 0, 'natural_number', true, false);
  $media_download = GetVar('media_download', 0, 'natural_number', true, false);
  $media_loop     = GetVar('media_loop', 0, 'natural_number', true, false);

  if($instant_upl = GetVar('instant_id', '', 'string', true, false))
  {
    $pl_file      = GetVar('file', '', 'string', true, false);
    $pl_filename  = array($pl_file => $title);
    $filestore    = DownloadManagerTools::GetSetting('default_upload_location');
  }
  else
  {
    $pl_filename  = GetVar('file_'.$this->PLUGINID, array(), 'array', true, false);
  }
  $pl_thumbfile   = GetVar('thumb_'.$this->PLUGINID, array(), 'array', true, false);
  $pl_image       = GetVar('image_'.$this->PLUGINID, array(), 'array', true, false);
  $embed_width    = GetVar('embed_width',  560, 'natural_number', true, false);
  $embed_height   = GetVar('embed_height', 350, 'natural_number', true, false);

  if(empty($fileid))
  {
    $dateadded = isset($dateadded) ? @strtotime($dateadded) : TIME_NOW;
    if($dateadded == -1 || $dateadded === false)
    {
      $dateadded = TIME_NOW;
    }
    if(isset($dateupdated))
    {
      $dateupdated = @strtotime($dateupdated);
      if($dateupdated == -1 || $dateupdated === false)
      {
        $dateupdated = 0;
      }
    }
    else
    {
      $dateupdated = !empty($_POST['ftpfile']) || !empty($pl_filename) ? 0 : TIME_NOW;
    }
  }

  // Version Related
  $ftpfile     = empty($_POST['ftpfile'])?0:1;
  $ftpfilename = GetVar('ftpfilename','','string',true,false);
  $file        = isset($_FILES['file'])?$_FILES['file']:null;
  $version     = GetVar('version',(!$useDefaults?DownloadManagerTools::GetVar('DEFAULT_VERSION'):''),'string',true,false);

  // Access
  $file_groups = GetVar('file_usergroups', array(), 'array', true,false);
  $file_users  = GetVar('file_users',  array(), 'array', true,false);

  $embedded_in_list    = empty($_POST['embedded_in_list'])?0:1;
  $embedded_in_details = isset($_POST['embedded_in_details'])?(empty($_POST['embedded_in_details'])?0:1):1;

  if(!$useDefaults && !count($sectionids))
  {
    $errors[] = DownloadManagerTools::GetPhrase('upload_error_no_section');
  }
  else if(!$useDefaults && !isset($version) && (($ftpfile == 1) || !empty($file['size'])))
  {
    $errors[] = DownloadManagerTools::GetPhrase('upload_error_no_version');
  }
  else if($ftpfile == 1) // check if a file was uploaded
  {
    // Check that filename is set and at least 4 characters
    if(!empty($ftpfilename) && (strlen($ftpfilename)<4))
    {
      $errors[] = DownloadManagerTools::GetPhrase('upload_error_no_filename');
    }
    else
    {
      if(!strlen($title))
      {
        $title = $ftpfilename;
      }
      $filename = $ftpfilename;
      $filetype = 'FTP';
      $filesize = 0;
      if(strlen($filename))
      {
        $tmp = substr(strtolower($filename),0,4);
        $isremote = (($tmp == 'http') || ($tmp == 'www.'));
        if(!$isremote && @is_file(DownloadManagerTools::$filesdir . $filename))
        {
          $filesize = @filesize(DownloadManagerTools::$filesdir . $filename);
        }
      }
      $storedfilename = '';
    }
  }
  if(!strlen($title))
  {
    $errors[] = DownloadManagerTools::GetPhrase('upload_error_no_title');
  }

  $file_inserted = false;

  $pl_uploaded = false;
  // Was a file uploaded by plupload (flash,gears,silverlight etc.)?
  foreach($pl_filename AS $temp_fn => $logical_fn)
  {
    $tmp = ROOT_PATH.'cache/'.$temp_fn;
    if(strlen($logical_fn) && strlen($temp_fn) && file_exists($tmp))
    {
      if($filesize = @filesize($tmp))
      {
        $pl_uploaded = true;
        $ftpfilename = $temp_fn;
        $filename = $logical_fn;
        $filetype = 'FTP';
      }
    }
  }

  // *** INSERT ACTION ***
  if(empty($fileid) && (count($errors) == 0) && ($pl_uploaded || empty($file['error'])))
  {
    // Insert file into database
    $groups = empty($file_groups) ? '' : implode('|',$file_groups);
    $groups = (($groups == '') || ($groups == '||') ? '' : '|'.$groups.'|');
    $users  = empty($file_users) ? '' : implode('|',$file_users);
    $users  = (($users == '') || ($users == '||') ? '' : '|'.$users.'|');

    $DB->query('INSERT INTO {p'.$this->PLUGINID."_files} (activated,  title,  author, description,
      uniqueid, dateadded, dateupdated, datestart, dateend, maxdownloads, tags,
      maxtype, licensed, embedded_in_details, embedded_in_list, access_groupids, access_userids,
      audio_class, video_class, media_autoplay, media_download, media_loop,
      embed_width, embed_height)
    VALUES ('$activated', '$title', '$author', '$description',
      '$uniqueid', '$dateadded', '$dateupdated', '$datestart', '$dateend', '$maxdownloads', '',
      '$maxtype', '$licensed', $embedded_in_details, $embedded_in_list, '$groups','$users',
      '$audio_class', '$video_class', $media_autoplay, $media_download, $media_loop,
      $embed_width, $embed_height)");

    $fileid = $DB->insert_id();
    $file_inserted = true;
  }
  else
  if(empty($fileid) && !empty($file['error']) && empty($ftpfilename))
  {
    $errors[] = DownloadManagerTools::GetPhrase('upload_error_no_file');
  }

  // *** UPDATE ACTION ***
  if(!$file_inserted && !empty($fileid) && (count($errors) == 0))
  {
    /*
    Commented out old file dates handling and applied changes:
      a) added "Date added" and "Date updated" to be editable
      b) always update both "dateadded" and "dateupdated"
    This allows to reflect the "Date added" to be used as a
    "release date" for a file when changed manually.
    Changes will regard, though, not to change the "dateupdated"
    column if it is still "0" to be compatible with the standard
    behavior of the DLM 1.4.x.
    */

    // Check "Date added", handle input value errors
    if(isset($dateadded))
    {
      if(strtolower($dateadded) == 'now')
      {
        $dateadded = TIME_NOW;
      }
      else
      if(!empty($dateadded) && (strlen($dateadded) > 1))
      {
        $dateadded = @strtotime($dateadded);
        if($dateadded == -1 || $dateadded === false)
        {
          $dateadded = TIME_NOW;
        }
      }
    }

    // Check "Date updated", handle input value errors
    if(isset($dateupdated))
    {
      if(strtolower($dateupdated) == 'now')
      {
        $dateupdated = TIME_NOW;
      }
      else
      if(!empty($dateupdated) && (strlen($dateupdated) > 1))
      {
        $dateupdated = @strtotime($dateupdated);
        if($dateupdated == -1 || $dateupdated === false)
        {
          $dateupdated = TIME_NOW;
        }
      }
    }

    // Only update dateadded and dateupdated if they were changed
    $da_sql = strlen($dateadded)  ?"dateadded   = '".$dateadded.  "', ":'';
    $du_sql = strlen($dateupdated)?"dateupdated = '".$dateupdated."', ":'';

    // If the rating was reset, remove all rating rows for the file
    if(!empty($resetrating))
    {
      $DB->query("DELETE FROM {ratings} WHERE rating_id = '%s'", 'p'.$this->PLUGINID.'-'.$fileid);
    }

    // Statement always updates both columns dateadded and dateupdated, too!
    $groups = empty($file_groups) ? '' : implode('|',$file_groups);
    $groups = (($groups == '') || ($groups == '||') ? '' : '|'.$groups.'|');
    $users  = empty($file_users) ? '' : implode('|',$file_users);
    $users  = (($users == '') || ($users == '||') ? '' : '|'.$users.'|');

    $DB->query('UPDATE {p'.$this->PLUGINID."_files}
      SET activated = '$activated',
        title         = '$title',
        author        = '$author',
        description   = '$description', " .
        $da_sql . $du_sql . "
        uniqueid      = '$uniqueid',
        downloadcount = '$downloadcount',
        datestart     = '$datestart',
        dateend       = '$dateend',
        maxdownloads  = '$maxdownloads',
        maxtype       = '$maxtype',
        licensed      = '$licensed',
        standalone    = '$standalone',
        access_groupids = '$groups',
        access_userids  = '$users',
        embedded_in_details = '$embedded_in_details',
        embedded_in_list = '$embedded_in_list',
        audio_class    = '$audio_class',
        video_class    = '$video_class',
        media_autoplay = $media_autoplay,
        media_download = $media_download,
        media_loop     = $media_loop,
        embed_width    = $embed_width,
        embed_height   = $embed_height
        WHERE fileid   = %d",$fileid);

    // If the download count has been set to zero, purge the download log
    if(!$file_inserted && ($downloadcount == 0))
    {
      $DB->query('DELETE FROM {p'.$this->PLUGINID.'_file_downloads} WHERE fileid = %d',$fileid);
    }
  }

  if(!empty($fileid) && (count($errors) == 0))
  {
    //v2.3.0: store tags in core table
    SD_Tags::StorePluginTags($this->PLUGINID, $fileid, $tags);

    $version_inserted = false;
    $embeddable = 0;

    if(!empty($ftpfilename) && (($standalone > 0) || ($ftpfile == 1)))
    {
      // Test, if the filename - if specified - is a remote, embeddable file
      // (supported by embevi.class!)
      if(!empty($ftpfilename) && $isremote)
      {
        $embeddable = (DownloadManagerTools::dlm_LinkAsMedia($ftpfilename)==$ftpfilename ? 0 : 1);
      }

      // Now we have a new file so create a new version
      $DB->query('INSERT INTO {p'.$this->PLUGINID.'_file_versions} '.
        '(fileid, version, file, filename, storedfilename, filesize, filetype, datecreated, is_embeddable_media, lyrics, ipaddress, userid) VALUES '.
        "(%d,     '%s',    '',   '%s' ,    '',             '%d',     '%s',     %d,          %d,                  '',     '%s',      %d)",
          $fileid, $version, $filename, $filesize, $filetype, TIME_NOW, $embeddable, USERIP, $userinfo['userid']);
      $versionid = $DB->insert_id();
      $DB->query('UPDATE {p'.$this->PLUGINID.'_files} SET currentversionid = %d WHERE fileid = %d',$versionid,$fileid);
      $version_inserted = true;
    }

    // Was a physical file uploaded by user?
    if(!$pl_uploaded && isset($file['error']) && $file['error'] != 0 && $file['error'] != 4)
    {
      if($file['error'] <= 2)
      {
        $errors[] = DownloadManagerTools::GetPhrase('file_too_big') . ' ' . $file['name'];
      }
      else
      {
        $errors[] = DownloadManagerTools::GetPhrase('file_upload_error') . ' - ' .
                    DownloadManagerTools::dlm_GetFileErrorDescr($file['error']) . ' ' . $file['name'];
      }
    }
    else if($pl_uploaded || ($file['size'] > 0)) // Check if a file was uploaded
    {
      if($pl_uploaded)
      {
        $filetype = DownloadManagerTools::dlm_GetMimeType($filename);
        $filepath = ROOT_PATH.'cache/'.$ftpfilename;
      }
      else
      {
        $filename = $file['name'];
        $filetype = $file['type'];
        $filepath = $file['tmp_name'];
        $filesize = $file['size'];
      }
      if(empty($title))
      {
        $title = $filename;
      }

      // Is file an embeddable media file with a know file extension?
      if($ext = DownloadManagerTools::dlm_GetFileExtension($filename))
      {
        $embeddable = in_array($ext, DownloadManagerTools::$KnownAudioTypes) ||
                      in_array($ext, DownloadManagerTools::$KnownVideoTypes) ? 1 : 0;
      }

      // Create a new file version which provides an ID
      if(!$version_inserted || !$versionid)
      {
        $DB->query('INSERT INTO {p'.$this->PLUGINID.'_file_versions} '.
          '(fileid, version, file, filename, storedfilename, filesize, filetype, datecreated, is_embeddable_media, ipaddress, userid, lyrics) VALUES '.
          "(%d,     '%s',    '',   '%s',     '',             '%d',     '%s',     %d,          %d,                  '%s',      %d,     '')",
            $fileid, $version, $filename, 0, $filetype, TIME_NOW, $embeddable, USERIP, $userinfo['userid']);
        $versionid = $DB->insert_id();
        $version_inserted = true;
      }
      $target_dir = DownloadManagerTools::$filesdir;
      $filedata = '';

      // If file itself is an image, store it in plugin's "images" folder!
      if($ext = DownloadManagerTools::dlm_HasFileImageExt($filename))
      {
        $target_dir = DownloadManagerTools::$imagesdir;
        $storedfilename = 'file' . $fileid . '_' . $versionid . '.' . $ext;
      }
      else
      {
        // Generate a unique, obfuscated filename in target folder
        $storedfilename = $fsprefix .
                          DownloadManagerTools::dlm_CreateGuidForFolder(
                            $target_dir . $fsprefix, DownloadManagerTools::GetVar('DEFAULT_EXT')) .
                          DownloadManagerTools::GetVar('DEFAULT_EXT');
      }

      if($pl_uploaded)
      {
        if(!@copy($filepath, $target_dir . $storedfilename))
        {
          $DB->query('DELETE FROM {p'.$this->PLUGINID."_file_versions} WHERE versionid = %d", $versionid);
          $errors[] = 'ERROR with file upload! Please check permissions for directory "'.$target_dir.$fsprefix.'"';
        }
        @unlink($filepath);
      }
      else
      {
        // In any case first move the file before trying
        // to process it (keyword: "open_basedir" directive)!
        if(!@is_uploaded_file($file['tmp_name']) ||
           !@move_uploaded_file($file['tmp_name'], $target_dir . $storedfilename))
        {
          $DB->query('DELETE FROM {p'.$this->PLUGINID."_file_versions} WHERE versionid = %d", $versionid);
          $errors[] = 'ERROR with file upload! Please check permissions for directory "'.$target_dir.$fsprefix.'"';
        }
      }

      if(empty($errors))
      {
        $filetype = DownloadManagerTools::dlm_GetMimeType($filename);
        $filesize = @filesize($target_dir . $storedfilename);
        if(!empty($filestore) || $embeddable) // Filesystem (always for embedded)
        {
          if(NotEmpty(DownloadManagerTools::GetSetting('default_chmod')))
          {
            $chm = substr(DownloadManagerTools::GetSetting('default_chmod'),0,4);
            // Only if 3 digits, convert to octal value
            #if(strlen($chm) == 3)
            {
              $chm = intval($chm, 8);
            }
            @chmod($target_dir . $storedfilename, (empty($chm) ? intval('0666', 8) : $chm));
          }
          else
          {
            @chmod($target_dir . $storedfilename, intval('0666', 8));
          }
          if(NotEmpty(DownloadManagerTools::GetSetting('default_owner')))
          {
            $olddog = $GLOBALS['sd_ignore_watchdog'];
            $GLOBALS['sd_ignore_watchdog'] = true;
            @chown($target_dir . $storedfilename, DownloadManagerTools::GetSetting('default_owner'));
            $GLOBALS['sd_ignore_watchdog'] = $olddog;
          }
          // Update version with final values
          $DB->query('UPDATE {p'.$this->PLUGINID."_file_versions}
                      SET filesize = %d, storedfilename = '%s', filetype = '%s', is_embeddable_media = $embeddable
                      WHERE versionid = %d",$filesize,$storedfilename,$filetype,$versionid);
        }
        else // DB
        {
          if(!function_exists('file_get_contents'))
          {
            if(false !== ($fhandle = @fopen($target_dir.$storedfilename, "rb")))
            {
              if(!$filedata = @fread($fhandle, $filesize))
              {
                $filedata = '';
              }
              @fclose($fhandle);
            }
          }
          else
          {
            $filedata = @file_get_contents($target_dir.$storedfilename);
          }
          $filedata = ($filedata == '' ? '' : '0x' . bin2hex($filedata));
          $storedfilename = '';
          // Update version with final values
          $DB->query('UPDATE {p'.$this->PLUGINID."_file_versions}
                      SET file = '%s', filesize = %d, storedfilename = '', filetype = '%s', is_embeddable_media = $embeddable
                      WHERE versionid = %d",$filedata,$filesize,$filetype,$versionid);
        }
      }

      if(count($errors) == 0)
      {
        $DB->query('UPDATE {p'.$this->PLUGINID.'_files} SET currentversionid = %d WHERE fileid = %d',$versionid,$fileid);
      }
    }
  } // *** END OF UPDATE ***

  // Handle thumbnail and image files if uploaded and valid fileid are available
  if(!empty($fileid) && (count($errors) == 0))
  {
    // Update the file-sections relations
    $this->UpdateFileSections($fileid, $sectionids);

    $pl_thumb_uploaded = false;
    // Was a file uploaded by plupload (flash,gears,silverlight etc.)?
    foreach($pl_thumbfile AS $temp_fn => $logical_fn)
    {
      $tmp = ROOT_PATH.'cache/'.$temp_fn;
      if(strlen($logical_fn) && strlen($temp_fn) && file_exists($tmp))
      {
        if($filesize = @filesize($tmp))
        {
          $pl_thumb_uploaded = true;
          $ftpfilename = $temp_fn;
          $filename = $logical_fn;
          $filetype = 'FTP';
        }
      }
    }
    // Check whether a thumbnail was uploaded
    $thumb_uploaded = false;
    if($pl_thumb_uploaded || (is_array($thumbnail) && !empty($thumbnail['name'])))
    {
      if($pl_thumb_uploaded)
      {
        $filetype = DownloadManagerTools::dlm_GetMimeType($filename);
        $filepath = ROOT_PATH.'cache/'.$ftpfilename;
        if(($pos = strrpos($filename,'.')) !== false)
        {
          $extension = strtolower(substr($filename, $pos+1));
        }
        $thumbname = 'tb_' . $fileid . '.' . $extension;
        $thumb_uploaded = @copy($filepath, DownloadManagerTools::$imagesdir . $thumbname);
        @unlink($filepath);
      }
      else
      if(!empty($thumbnail['tmp_name']) && is_uploaded_file($thumbnail['tmp_name']) && DownloadManagerTools::dlm_CheckImageFile($thumbnail,$errors,$extension))
      {
        $thumbname = 'tb_' . $fileid . '.' . $extension;
        $thumb_uploaded = @move_uploaded_file($thumbnail['tmp_name'], DownloadManagerTools::$imagesdir . $thumbname);
      }

      if($thumb_uploaded)
      {
        DownloadManagerTools::dlm_ReplaceFileImage('thumbnail', $fileid, DownloadManagerTools::$imagesdir, $thumbname, true);
        @chmod(DownloadManagerTools::$imagesdir . $thumbname, intval('0666', 8));
      }
      else
      {
        $errors[] = 'Thumbnail file could not be copied on server!';
      }
    }

    $pl_image_uploaded = false;
    // Was a file uploaded by plupload (flash,gears,silverlight etc.)?
    foreach($pl_image AS $temp_fn => $logical_fn)
    {
      $tmp = ROOT_PATH.'cache/'.$temp_fn;
      if(strlen($logical_fn) && strlen($temp_fn) && file_exists($tmp))
      {
        if($filesize = @filesize($tmp))
        {
          $pl_image_uploaded = true;
          $ftpfilename = $temp_fn;
          $filename = $logical_fn;
          $filetype = 'FTP';
        }
      }
    }
    // Check whether an image was uploaded
    if($pl_image_uploaded || (is_array($image) && !empty($image['name'])))
    {
      $image_uploaded = false;
      if($pl_image_uploaded)
      {
        $filetype = DownloadManagerTools::dlm_GetMimeType($filename);
        $filepath = ROOT_PATH.'cache/'.$ftpfilename;
        if(($pos = strrpos($filename,'.')) !== false)
        {
          $extension = strtolower(substr($filename, $pos+1));
        }
        $imagename = $fileid . '.' . $extension;
        $image_uploaded = @copy($filepath, DownloadManagerTools::$imagesdir . $imagename);
        @unlink($filepath);
      }
      else
      if(is_uploaded_file($image['tmp_name']) && DownloadManagerTools::dlm_CheckImageFile($image,$errors,$extension))
      {
        $imagename = $fileid . '.' . $extension;
        $image_uploaded = @move_uploaded_file($image['tmp_name'], DownloadManagerTools::$imagesdir . $imagename);
      }
      if($image_uploaded)
      {
        DownloadManagerTools::dlm_ReplaceFileImage('image', $fileid, DownloadManagerTools::$imagesdir, $imagename, true);
        @chmod(DownloadManagerTools::$imagesdir . $imagename, intval('0666', 8));
      }
      else
      {
        $errors[] = 'Image file could not be copied on server!';
      }
    }

    // If auto-thumbnailing was checked, try top map a filetype-image
    // to be a thumbnail
    if($auto_thumbnail && !$thumb_uploaded)
    {
      // Determine if current file version is embedded media
      $type_image = false;
      if(!empty($fileid))
      {
        if($version = $DB->query_first('SELECT fv.filename, fv.filetype, fv.is_embeddable_media
                      FROM {p'.$this->PLUGINID.'_files} f
                      INNER JOIN {p'.$this->PLUGINID.'_file_versions} fv ON fv.versionid = f.currentversionid
                      WHERE f.fileid = %d', $fileid))
        {
          if(($version['filetype'] != 'FTP') && !empty($version['filename']))
          {
            $type_image = DownloadManagerTools::dlm_GetAutoThumbnail($version['filename']);
          }
          else
          if(($version['filetype'] == 'FTP') && ($version['is_embeddable_media']))
          {
            $type_image = DownloadManagerTools::dlm_GetAutoThumbnail('v.mpg', true);
          }
        }
      }

      if($type_image)
      {
        $imagename = 'tb_' . $fileid . '.png';
        DownloadManagerTools::dlm_ReplaceFileImage('thumbnail', $fileid, DownloadManagerTools::$imagesdir, $imagename, true);
        if(@copy($type_image, DownloadManagerTools::$imagesdir . $imagename))
        {
          chmod(DownloadManagerTools::$imagesdir . $imagename, intval('0666', 8));
        }
      }
    }
  }

  // Clear empty permissions
  $DB->query('UPDATE {p'.$this->PLUGINID."_files} SET access_groupids = '' where access_groupids = '||'");
  $DB->query('UPDATE {p'.$this->PLUGINID."_files} SET access_userids = '' where access_userids = '||'");

  if(count($errors) > 0)
  {
    DownloadManagerTools::PrintErrors($errors, DownloadManagerTools::GetPhrase('upload_error'));
    if(!$instant_upl)
    $this->DisplayFileForm((int)$fileid);
    return false;
  }
  else
  {
    if(!$instant_upl)
    PrintRedirect(REFRESH_PAGE . (!empty($fileid)?"&action=displayfileform&amp;load_wysiwyg=0&amp;fileid=$fileid":''), 1);
    return true;
  }

} //UpdateFile


// ########################### UPDATE VERSION #################################

function UpdateFileVersion($fileid, $versionid)
{
  global $DB, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage(REFRESH_PAGE,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  $version    = GetVar('version','','string',true,false);
  $filestore  = GetVar('filestore',0,'whole_number',true,false);
  $filesize   = GetVar('filesize',0,'whole_number',true,false);
  $filename   = GetVar('filename','','string',true,false);
  $sfilename  = GetVar('storedfilename','','string',true,false);
  $datecreated = !empty($_POST['datecreated']) ? @strtotime($_POST['datecreated']) : false;
  if($datecreated == -1 || $datecreated === false)
  {
    $datecreated = TIME_NOW;
  }

  if(!isset($errors))
  {
    $storage = $DB->query_first('SELECT IFNULL(storedfilename,\'\') storedfilename, IFNULL(filename,\'\') filename'.
      ' FROM {p'.$this->PLUGINID.'_file_versions}'.
      ' WHERE fileid = %d AND versionid = %d',
      $fileid, $versionid);
    $has_storedfilename = strlen($storage['storedfilename']);
    $has_filename = (strlen($storage['filename'])>0);

    // Has the storage location changed?
    if($has_storedfilename && ((int)$has_filename != $filestore))
    {
      if($this->ChangeStorageLocation($fileid, $versionid, $filestore))
      {
        $DB->query('UPDATE {p'.$this->PLUGINID."_file_versions} SET version = '$version',".
          "filesize = '$filesize', filename = '$filename', is_embeddable_media = 0".
          ($datecreated?", datecreated = '$datecreated' ":'').
          ' WHERE fileid = %d AND versionid = %d',$fileid,$versionid);
      }
    }
    else
    {
      $embeddable = ((DownloadManagerTools::dlm_LinkAsMedia($filename)==$filename) ? 0 : 1);
      $DB->query('UPDATE {p'.$this->PLUGINID."_file_versions} SET version  = '$version', ".
           "filesize = '$filesize', storedfilename = '$sfilename', is_embeddable_media = $embeddable, ".
           "filename = '$filename' ".
           ($datecreated?", datecreated = '$datecreated'":'').
           ' WHERE fileid = %d AND versionid = %d',$fileid,$versionid);
    }

    PrintRedirect(REFRESH_PAGE . '&amp;action=displayfileform&amp;load_wysiwyg=0&amp;fileid='.$fileid, 1);
  }
  else
  {
    DownloadManagerTools::PrintErrors($errors);
    $this->DisplayFileVersionEditForm($fileid, $versionid);
  }

} //UpdateFileVersion


// This function converts the storage location for a given file version
// $filestore = 0 - DB
// $filestore = 1 - Filesystem
function ChangeStorageLocation($fileid, $versionid, $filestore)
{
  global $DB;

  // Change to Filesystem: This will create a copy of an existing file (which
  // may be originating in the configured "ftpfiles" directory OR by http/www)
  // with a cryptic filename, example:
  // "A79D66E2-8305-40F7-9C0F-3B103D2A358B.dat" instead of "myfile.zip".
  // Note: The original file will remain untouched!

  // Change to Filesystem
  if($filestore == 1)
  {
    $file = $DB->query_first('SELECT file, filename, filesize, filetype, storedfilename
        FROM {p'.$this->PLUGINID.'_file_versions}
        WHERE fileid = %d AND versionid = %d',$fileid,$versionid);

    if(!($file['file'])) // size of stored file in DB
    {
      echo 'File transfer to filesystem not possible (DB-filesize = 0) [err1]!';
      return false;
    }
    $storedfilename = CreateGuid() . DownloadManagerTools::GetVar('DEFAULT_EXT');
    if(false !== ($fp = fopen(DownloadManagerTools::$filesdir . $storedfilename, "wb")))
    {
      fwrite($fp, $file['file'], $file['filesize']);
      fclose($fp);

      $DB->query('UPDATE {p'.$this->PLUGINID."_file_versions}
        SET file = '', storedfilename = '$storedfilename'
        WHERE fileid = '$fileid' AND versionid = '$versionid'");
    }
    else
    {
      return false;
    }
  }
  // Change to DB
  else
  {
    $file = $DB->query_first('SELECT storedfilename, filesize, filetype
        FROM {p'.$this->PLUGINID.'_file_versions}
        WHERE fileid = %d AND versionid = %d',$fileid,$versionid);

    $usedfilename = (isset($file['storedfilename'])?$file['storedfilename']:$file['filename']);

    // in case of a link (http/www) ONLY use "$usedfilename"
    if(($file['filetype']=='FTP') && ((substr(strtolower($usedfilename),0,3)=='www') || (substr(strtolower($usedfilename),0,4)=='http')))
    {
      $filepath = $usedfilename;
    }
    else
    {
      $filepath = DownloadManagerTools::$filesdir . $usedfilename;
    }

    if(!function_exists('file_get_contents'))
    {
      if(false !== ($fhandle = @fopen($filepath, "rb")))
      {
        if(!$filedata = @fread($fhandle, $filesize))
        {
          $filedata = '';
        }
        @fclose($fhandle);
      }
      else
      {
        return false;
      }
    }
    else
    {
      $filedata = @file_get_contents($filepath);
    }
    $filedata = ($filedata == '' ? false : $filedata);
    if(false !== $filedata)
    {
      $DB->query('UPDATE {p'.$this->PLUGINID."_file_versions}
        SET file = '$filedata', storedfilename = '', filetype = '" .
        ($file['filetype'] != 'FTP' ? $file['filetype'] : '') . "'
        WHERE fileid = %d AND versionid = %d",$fileid,$versionid);
      @unlink($filepath);
    }
    else
    {
      return false;
    }
  }

  return true;

} //ChangeStorageLocation


// ############################ BATCH UPLOAD #################################

function BatchUpload()
{
  global $DB, $userinfo;

  // get post variables
  $maxFiles        = GetVar('maxFiles', 0, 'whole_number', true, false);
  $maxFiles        = min(count($_FILES), $maxFiles);
  $autoApprove     = empty($_POST['activated']) ? 0 : 1;
  $author          = GetVar('author', '', 'string', true, false);
  $version         = DownloadManagerTools::GetVar('DEFAULT_VERSION');
  $sectionids      = GetVar('sectionid', array(1), 'array', true, false);
  $auto_thumbnail  = !empty($_POST['auto_thumbnail']);
  $createthumbnail = !empty($_POST['createthumbnail']);
  $filenameastitle = !empty($_POST['filenameastitle']);
  $filestore       = isset($_POST['filestore']) ? $_POST['filestore']:DownloadManagerTools::GetSetting('default_upload_location');
  $fsprefix        = (!empty($filestore) && isset($_POST['fsprefix'])) ? $_POST['fsprefix'] : '';
  // Access
  $file_groups = empty($_POST['file_usergroups'])?array():$_POST['file_usergroups'];
  $file_users  = empty($_POST['file_users'])?array():$_POST['file_users'];
  $up_count    = 0;

  $errors = array();

  if(!empty($filestore) && !is_dir(DownloadManagerTools::$filesdir.$fsprefix))
  {
    $errors[] = "Target filesystem folder '$fsprefix' does not exist!";
  }
  else
  if($maxFiles > 0)
  {
    for($i = 0; $i < $maxFiles; $i++)
    {
      $file = isset($_FILES['file' . $i]) ? $_FILES['file' . $i] : null;
      if(isset($file['error']) && ($file['error'] != 0) && ($file['error'] != 4))
      {
        if($file['error'] <= 2)
        {
          $errors[] = DownloadManagerTools::GetPhrase('file_too_big') . ' ' . $file['name'];
        }
        else
        {
          $errors[] = DownloadManagerTools::GetPhrase('file_upload_error') . ' - ' .
                      DownloadManagerTools::dlm_GetFileErrorDescr($file['error'])  . ' ' . $file['name'];
        }
      }
      else if(($file['error'] != 4) && ($file['size'] > 0) && is_uploaded_file($file['tmp_name'])) // check if a file was uploaded
      {
        $fileok = true;
        $filename = $file['name'];
        $filetype = $file['type'];
        $filepath = $file['tmp_name'];
        $filesize = $file['size'];
        $title    = isset($_POST['title' . $i]) ? (string)$_POST['title' . $i] : '';
        if(!strlen($title) || !empty($_POST['filenameastitle'])) // default title to filename if empty
        {
          $title = $filename;
        }

        // First move the uploaded file to obey server restrictions
        $filedata = '';
        $storedfilename = $fsprefix . CreateGuid() . DownloadManagerTools::GetVar('DEFAULT_EXT');
        if(@move_uploaded_file($file['tmp_name'], DownloadManagerTools::$filesdir . $storedfilename))
        {
          if(!empty($filestore)) // Filesystem
          {
            if(NotEmpty(DownloadManagerTools::GetSetting('default_chmod')))
            {
              $chm = substr(DownloadManagerTools::GetSetting('default_chmod'),0,4);
              // Only if 3 digits, convert to octal value
              #if(strlen($chm) == 3)
              {
                $chm = intval($chm, 8);
              }
              @chmod(DownloadManagerTools::$filesdir . $storedfilename, (empty($chm) ? intval('0666', 8) : $chm));
            }
            else
            {
              @chmod(DownloadManagerTools::$filesdir . $storedfilename, 0666);
            }
            if(NotEmpty(DownloadManagerTools::GetSetting('default_owner')))
            {
              $olddog = $GLOBALS['sd_ignore_watchdog'];
              $GLOBALS['sd_ignore_watchdog'] = true;
              @chown(DownloadManagerTools::$filesdir . $storedfilename, DownloadManagerTools::GetSetting('default_owner'));
              $GLOBALS['sd_ignore_watchdog'] = $olddog;
            }
          }
          else // DB
          {
            if(!function_exists('file_get_contents'))
            {
              if(false !== ($fhandle = @fopen(DownloadManagerTools::$filesdir . $storedfilename, "rb")))
              {
                if(!$filedata = @fread($fhandle, $filesize))
                {
                  $filedata = '';
                }
                @fclose($fhandle);
              }
            }
            else
            {
              $filedata = @file_get_contents(DownloadManagerTools::$filesdir . $storedfilename);
            }
            $filedata = ($filedata == '' ? '' : '0x' . bin2hex($filedata));
            $storedfilename = '';
          }
        }
        else
        {
          $errors[] = 'ERROR when copying file on server: "'.$filename.'"';
          $fileok = false;
        }

        if($fileok)
        {
          $groups = empty($file_groups) ? '' : implode('|',$file_groups);
          $groups = (($groups == '') || ($groups == '||') ? '' : '|'.$groups.'|');
          $users  = empty($file_users) ? '' : implode('|',$file_users);
          $users  = (($users == '') || ($users == '||') ? '' : '|'.$users.'|');

          // Is file an embeddable media file with a know file extension?
          $embeddable = 0;
          if($ext = DownloadManagerTools::dlm_GetFileExtension($filename))
          {
            $embeddable = in_array($ext, DownloadManagerTools::$KnownAudioTypes) ||
                          in_array($ext, DownloadManagerTools::$KnownVideoTypes) ? 1 : 0;
          }

          // Insert file into database
          $DB->query('INSERT INTO {p'.$this->PLUGINID.'_files}'.
            ' (activated, title,  author,  dateadded, datestart, dateend, maxdownloads,
               maxtype, access_groupids, access_userids, embedded_in_details)'.
            " VALUES ('$autoApprove', '$title', '$author', '".TIME_NOW."', '".TIME_NOW."', '0', '0', '0',
            '".$groups."','".$users."', ".$embeddable.")");

          $fileid = $DB->insert_id();

          // Ignore provided mime type and check again
          $filetype = DownloadManagerTools::dlm_GetMimeType($filename);

          // Now we have a new file so create a new version
          $DB->query('INSERT INTO {p'.$this->PLUGINID."_file_versions}
            (fileid,   version,  file, filename, storedfilename,
             filesize, filetype, datecreated,    is_embeddable_media, ipaddress, userid, lyrics)
             VALUES (%d, '%s', '%s', '%s', '%s', %d, '%s', %d, %d, '%s', %d, '')",
             $fileid, $version,  $DB->escape_string($filedata), $filename,
             str_replace("\\","\\\\", $storedfilename), // on Win the backslash needs escaping!!!
             $filesize, $filetype, TIME_NOW, $embeddable, USERIP, $userinfo['userid']);

          $versionid = $DB->insert_id();

          $DB->query('UPDATE {p'.$this->PLUGINID.'_files} '.
                     'SET currentversionid = %d WHERE fileid = %d',$versionid,$fileid);
          $up_count++;
          // Insert the sections the file belongs to
          $this->UpdateFileSections($fileid, $sectionids);

          // Thumbnail creation - Note: "$Ext" gets return value!
          if($createthumbnail && ($Ext = DownloadManagerTools::dlm_HasFileImageExt($filename)))
          {
            $thumbfile = 'tb_' . $fileid . '.' . $Ext;
            if(DownloadManagerTools::dlm_DoCreateThumbnailByParams($fileid,0,DownloadManagerTools::$filesdir.$storedfilename,DownloadManagerTools::$imagesdir,$thumbfile,100,100))
            {
              $thumbfile = is_file(DownloadManagerTools::$imagesdir.$thumbfile) ? $thumbfile : '';
              $DB->query('UPDATE {p'.$this->PLUGINID."_files} SET thumbnail = '$thumbfile' where fileid = $fileid");
              if(strlen($thumbfile))
              {
                @chmod(DownloadManagerTools::$imagesdir.$thumbfile, intval('0666', 8));
              }
            }
            else
            {
              $errors[] = DownloadManagerTools::GetPhrase('file_thumbnail_error') . " ($filename)";
            }
          }
          else
          // Automatic file extension-to-image mapping
          if($auto_thumbnail && ($type_image = DownloadManagerTools::dlm_GetAutoThumbnail($filename)))
          {
            $imagename = 'tb_' . $fileid . '.png';
            DownloadManagerTools::dlm_ReplaceFileImage('thumbnail', $fileid, DownloadManagerTools::$imagesdir, $imagename, true);
            if(@copy($type_image, DownloadManagerTools::$imagesdir . $imagename))
            {
              chmod(DownloadManagerTools::$imagesdir . $imagename, intval('0666', 8));
            }
          }

          // If stored in DB, remove uploaded file
          if(empty($filestore))
          {
            @unlink(DownloadManagerTools::$filesdir . $storedfilename);
          }
        } //$fileok

      } /* End If $file['size']; */
    } /* End For */
  }

  // Frequent check to remove (empty) entries "||" from permissions
  $DB->query('UPDATE {p'.$this->PLUGINID."_files} SET access_groupids = '' where access_groupids = '||'");
  $DB->query('UPDATE {p'.$this->PLUGINID."_files} SET access_userids = '' where access_userids = '||'");

  if(count($errors))
  {
    DownloadManagerTools::PrintErrors($errors, DownloadManagerTools::GetPhrase('upload_error'));
    if($up_count)
    {
      echo 'However, some files have been uploaded successfully.<br /><br /> ';
    }
    $this->DisplayBatchUploadForm();
  }
  else
  {
    PrintRedirect(REFRESH_PAGE, 1);
  }

} //BatchUpload


// ############################################################################

function BatchImport()
{
  global $DB, $userinfo, $dlm_currentdir;

  // Check Import directory on server (admin only)
  $errors = array();
  $this->importdir = DownloadManagerTools::dlm_FixPath(dirname(__FILE__) . '/import/');
  if(is_dir($this->importdir))
  {
    if(!is_writable($this->importdir))
    {
      $errors[] = AdminPhrase('dlm_files_import_directory'). ' ('.
                        $this->importdir.') '.
                        AdminPhrase('dlm_directory_not_writable');
    }
  }
  else
  {
    $errors[] = AdminPhrase('dlm_files_import_directory').' ('.
                      $this->importdir.') '.
                      AdminPhrase('dlm_directory_not_exists');
  }

  if(!empty($errors))
  {
    DownloadManagerTools::PrintErrors($errors, 'Configuration Error');
    return;
  }

  $version         = DownloadManagerTools::GetVar('DEFAULT_VERSION');
  $autoApprove     = empty($_POST['activated'])?0:1;
  $delete_imported = empty($_POST['delete_imported'])?0:1;
  $author          = GetVar('author', '', 'string', true, false);
  $auto_thumbnail  = GetVar('auto_thumbnail', false, 'bool', true, false);
  $sectionids      = GetVar('sectionid', array(1), 'array', true, false);
  $createthumbnail = !empty($_POST['createthumbnail']);
  $filestore       = isset($_POST['filestore']) ? (int)$_POST['filestore'] : DownloadManagerTools::GetSetting('default_upload_location');
  $fsprefix        = (!empty($filestore) && isset($_POST['fsprefix'])) ? (string)$_POST['fsprefix'] : '';
  // Access permissions
  $file_groups     = empty($_POST['file_usergroups']) ? array() : (array)$_POST['file_usergroups'];
  $file_users      = empty($_POST['file_users'])      ? array() : (array)$_POST['file_users'];
  $up_count        = 0;

  // IF a prefix was specified, make sure it is valid and has a trailing (back-)slash
  if(strlen($fsprefix))
  {
    $fsprefix = DownloadManagerTools::dlm_FixPath($fsprefix, false);
  }
  if(!empty($filestore) && !is_dir(DownloadManagerTools::$filesdir.$fsprefix))
  {
    $errors[] = 'Target filesystem folder "'.DownloadManagerTools::$filesdir.$fsprefix.'" does not exist!';
  }
  else if($dir = @opendir($this->importdir))
  {
    echo '<br /><strong>Target Folder: '.DownloadManagerTools::$filesdir.$fsprefix.'</strong><br />';
    while(false !== ($f = readdir($dir)))
    {
      if((substr($f,0,1) != '.') && (strpos($f, '.php') === false) &&
         (strpos($f, '..') === false) && (strpos($f, '.htm') === false))
      {
        echo "<br /><strong>Filename: $f</strong><br />";
        $f = $this->importdir . $f;
        $size = @filesize($f);
        $filetype = '';

        if(($size > 0) && @is_file($f))
        {
          $filename = basename($f);
          $filetype = DownloadManagerTools::dlm_GetMimeType($filename);

          if(($filetype!==false) && strlen($filetype))
          {
            echo "Filetype: '$filetype'<br />";
          }
          else
          {
            $filetype = 'application/octet-stream';
            echo "Filetype unknown, using '$filetype'<br />";
          }
          $filepath = $f;
          $filesize = $size;
          $title    = $filename;

          echo "Source filename: $filename<br />";
          // Generate a unique, obfuscated filename in target folder
          $storedfilename = $fsprefix .
                            DownloadManagerTools::dlm_CreateGuidForFolder(
                              DownloadManagerTools::$filesdir.$fsprefix, DownloadManagerTools::GetVar('DEFAULT_EXT')).
                            DownloadManagerTools::GetVar('DEFAULT_EXT');
          $filedata = '';
          // Regardless of storage location, first try to copy the file
          // in order to make sure, that file is really readable
          if(@copy($f, DownloadManagerTools::$filesdir . $storedfilename))
          {
            if(!empty($filestore)) // Filesystem
            {
              echo "Target filename: $storedfilename<br />";
              if(NotEmpty(DownloadManagerTools::GetSetting('default_chmod')))
              {
                $chm = substr(DownloadManagerTools::GetSetting('default_chmod'),0,4);
                // Only if 3 digits, convert to octal value
                #if(strlen($chm) == 3)
                {
                  $chm = intval($chm, 8);
                }
                @chmod(DownloadManagerTools::$filesdir . $storedfilename, (empty($chm) ? intval('0666', 8) : $chm));
              }
              else
              {
                @chmod(DownloadManagerTools::$filesdir . $storedfilename, intval('0666', 8));
              }
              if(NotEmpty(DownloadManagerTools::GetSetting('default_owner')))
              {
                $olddog = $GLOBALS['sd_ignore_watchdog'];
                $GLOBALS['sd_ignore_watchdog'] = true;
                @chown(DownloadManagerTools::$filesdir . $storedfilename, DownloadManagerTools::GetSetting('default_owner'));
                $GLOBALS['sd_ignore_watchdog'] = $olddog;
              }
            }
            else // DB
            {
              echo "File is stored in database.<br />";
              if(!function_exists('file_get_contents'))
              {
                if(false !== ($fhandle = @fopen(DownloadManagerTools::$filesdir . $storedfilename, "rb")))
                {
                  if(!$filedata = @fread($fhandle, $filesize))
                  {
                    $filedata = '';
                  }
                  @fclose($fhandle);
                }
              }
              else
              {
                $filedata = @file_get_contents(DownloadManagerTools::$filesdir . $storedfilename);
              }
              $filedata = (($filedata === false) || ($filedata == '') ? '' : '0x' . bin2hex($filedata));
              // File was most likely imported, so remove copied file now
              @unlink(DownloadManagerTools::$filesdir . $storedfilename);
              $storedfilename = '';
            }

            $groups = empty($file_groups) ? '' : implode('|',$file_groups);
            $groups = (($groups == '') || ($groups == '||') ? '' : '|'.$groups.'|');
            $users  = empty($file_users) ? '' : implode('|',$file_users);
            $users  = (($users == '') || ($users == '||') ? '' : '|'.$users.'|');

            // Is file an embeddable media file with a know file extension?
            $embeddable = 0;
            if($ext = DownloadManagerTools::dlm_GetFileExtension($filename))
            {
              $embeddable = in_array($ext, DownloadManagerTools::$KnownAudioTypes) ||
                            in_array($ext, DownloadManagerTools::$KnownVideoTypes) ? 1 : 0;
            }

            // Insert file into database
            $DB->query('INSERT INTO {p'.$this->PLUGINID."_files}
              (activated, title,  author,  description, dateadded, datestart, dateend,
               maxdownloads, maxtype, access_groupids, access_userids, embedded_in_details, tags)
              VALUES
              ('$autoApprove', '$title', '$author', '', '".TIME_NOW."', '".TIME_NOW."', '0',
              '0', '0', '".$groups."', '".$users."', ".$embeddable.", '')");

            $fileid = $DB->insert_id();

            // Now we have a new file so create a new version
            $DB->query('INSERT INTO {p'.$this->PLUGINID.'_file_versions} '.
              '(fileid, version, file, filename, storedfilename, filesize, filetype, datecreated, is_embeddable_media, ipaddress, userid, lyrics) VALUES '.
              "(%d,     '%s',    '%s', '%s',     '%s',           %d,       '%s',     %d,          %d,                  '%s',      %d,     '')",
               $fileid, $version,  $DB->escape_string($filedata), $filename,
               str_replace("\\","\\\\", $storedfilename), // on Win the backslash needs escaping!!!
               $filesize, $filetype, TIME_NOW, $embeddable, USERIP, $userinfo['userid']);

            $versionid = $DB->insert_id();
            $DB->query('UPDATE {p'.$this->PLUGINID.'_files} SET currentversionid = %d WHERE fileid= %d',$versionid,$fileid);

            // Insert the sections
            $this->UpdateFileSections($fileid, $sectionids);

            // Create Thumbnail - Note: "$Ext" gets a return value!
            if($createthumbnail && ($Ext = DownloadManagerTools::dlm_HasFileImageExt($f)))
            {
              $thumbfile = 'tb_' . $fileid . '.' . $Ext;
              if(DownloadManagerTools::dlm_DoCreateThumbnailByParams($fileid,0,$f,DownloadManagerTools::$imagesdir,$thumbfile,100,100))
              {
                $thumbfile = is_file(DownloadManagerTools::$imagesdir.$thumbfile) ? $thumbfile : '';
                $DB->query('UPDATE {p'.$this->PLUGINID."_files} SET thumbnail = '$thumbfile' WHERE fileid = %d",$fileid);
              }
              else
              {
                $errors[] = DownloadManagerTools::GetPhrase('file_thumbnail_error') . " ($filename)";
              }
            }
            else
            // Automatic file extension-to-image mapping
            if($auto_thumbnail && ($type_image = DownloadManagerTools::dlm_GetAutoThumbnail($filename)))
            {
              $imagename = 'tb_' . $fileid . '.png';
              DownloadManagerTools::dlm_ReplaceFileImage('thumbnail', $fileid, DownloadManagerTools::$imagesdir, $imagename, true);
              if(@copy($type_image, DownloadManagerTools::$imagesdir . $imagename))
              {
                chmod(DownloadManagerTools::$imagesdir . $imagename, intval('0666', 8));
              }
            }

            // Delete old file
            if($delete_imported)
            {
              @unlink($f);
            }
          }
          else
          {
            $errors[] = "Failed to import file '$f'<br />";
          }
        } // End If $file['size'];
        else
        {
          echo "File ignored: $f<br />";
        }
      } // End If not . or ..
    } // End While
    closedir($dir);
  }
  else
  {
    $errors[] = 'Failed to read directory!';
  }

  // Frequent check to remove (empty) entries "||" from permissions
  $DB->query('UPDATE {p'.$this->PLUGINID."_files} SET access_groupids = '' where access_groupids = '||'");
  $DB->query('UPDATE {p'.$this->PLUGINID."_files} SET access_userids = '' where access_userids = '||'");

  if(!empty($errors))
  {
    echo '<br /><ul>';
    foreach($errors as $err)
    {
      echo '<li>'. $err . '</li>';
    }
    if($up_count)
    {
      echo '</ul> However, <strong>some</strong> files may have been successfully imported.<br />';
    }
    $this->DisplayBatchImportForm();
  }
  else
  {
    PrintRedirect(REFRESH_PAGE, 20);
  }

} //BatchImport


// ######################## DISPLAY BATCH UPLOAD FORM ##########################

function DisplayBatchUploadForm()
{
  global $DB, $userinfo;

  $maxFiles = 10;

  $max_upload_size = strlen(DownloadManagerTools::GetSetting('max_upload_size')) > 0 ?
                     (DownloadManagerTools::GetSetting('max_upload_size') * 1024) :
                     DownloadManagerTools::GetVar('DEFAULT_MAX_UPLOAD_SIZE');

  PrintSection(AdminPhrase('dlm_menu_batch_upload'));

  echo '
    <form method="post" enctype="multipart/form-data" id="batchupload" action="'.REFRESH_PAGE.'">
    '.PrintSecureToken().'
    <table width="100%" border="0" cellpadding="5" cellspacing="0">
    <tr>
    <td class="tdrow2" colspan="3" width="100%">
      This form allows you to upload multiple files into the Download Manager.
      They will all be added to your root category by default and will be stored in the default
      storage location specified in your settings: <strong>'.
      (DownloadManagerTools::GetSetting('default_upload_location')==1?'Filesystem':'DB').
      '</strong>. PHP Uploads are currently <strong>';
  echo ini_get('file_uploads') ? 'enabled' : 'disabled';
  echo '</strong>. PHP max. Filesize is <strong>' . ini_get('upload_max_filesize') .
       '</strong> and your Subdreamer max. upload size is <strong>';
  echo DownloadManagerTools::dlm_DisplayReadableFilesize($max_upload_size);
  echo '</strong>
    </td>
    </tr>
    <tr>
    <td class="tdrow2" width="40%" >Section to place uploaded files into:<br />
    Please specify at least one or more sections to place each uploaded file into.
    Use [CTRL/Shift+Click] to select/deselect individual sections.
    </td>
    <td class="tdrow3" colspan="2" valign="top">';

  DownloadManagerTools::dlm_PrintSectionSelection(
    array('formId'            => 'sectionid[]',
          'selectedSectionId' => (isset($_POST['sectionid']) ? $_POST['sectionid'] : '1'))
    );

  echo '
      </td>
    </tr>
    <tr>
      <td class="tdrow2" width="40%" >Upload into default (empty) or specific section\'s directory?<br />
        Note: Only if File Storage Location is Filesystem.</td>
      <td class="tdrow3" valign="top" colspan="2">
      Select a "Section" directory (leave empty for default):<br />
      <select name="fsprefix" style="width:300px;">
      <option value="" '.(!isset($_POST['fsprefix']) ? 'selected="selected"' : '').'></option>
      ';

  // Get Filesystem-Prefixes from sections
  if($getsections = $DB->query('SELECT fsprefix FROM {p'.$this->PLUGINID.'_sections} WHERE LENGTH(fsprefix) > 0'))
  {
    while($sec = $DB->fetch_array($getsections,null,MYSQL_ASSOC))
    {
      echo '      <option value="'.$sec['fsprefix'].'"'.
        (isset($_POST['fsprefix']) && ($sec['fsprefix']==$_POST['fsprefix']) ? ' selected="selected"' : '').
        '>'.$sec['fsprefix']."</option>\r\n";
    }
    $DB->free_result($getsections);
  }
  $filestore = isset($_POST['filestore']) ? $_POST['filestore'] : DownloadManagerTools::GetSetting('default_upload_location');
  echo '
      </select>
      </td>
    </tr>
    <tr>
      <td class="tdrow2"><strong>File Storage Location</strong> for uploaded files?</td>
      <td class="tdrow3" colspan="2" valign="top">
        <select name="filestore">
          <option value="0" ' . ( empty($filestore) ? 'selected="selected"' : '') . '>Database</option>
          <option value="1" ' . (!empty($filestore) ? 'selected="selected"' : '') . '>Filesystem</option>
        </select>
      </td>
    </tr>
    <tr>
      <td class="tdrow2" valign="top"><strong>Access Permissions</strong><br /><br />
      You can restrict access to this file to specific usergroups <strong>or</strong> users.
      You may only select one or more usergroups <strong>OR</strong> one or more users but you cannot use both.<br />
      Default: none select = available to all usergroups.<br />
      Use [CTRL/Shift+Click] to select multiple usergroups or users)
      </td>
      <td class="tdrow3" colspan="2" valign="top" style="vertical-alignment: top;">';

  $this->PrintUserGroupSelection('file_usergroups[]', (isset($_POST['file_usergroup'])?$_POST['file_usergroup']:array()), 'file_users[]');
  echo '&nbsp;&nbsp;&nbsp;';
  $this->PrintUserSelection('file_users[]', (isset($_POST['file_users'])?$_POST['file_users']:array()), 'file_usergroups[]');

  $author          = GetVar('author', $userinfo['username'], 'string', true, false);
  $activated       = GetVar('activated', 1, 'natural_number', true, false);
  $auto_thumbnail  = GetVar('auto_thumbnail', 1, 'natural_number', true, false);
  $createthumbnail = GetVar('createthumbnail', 1, 'natural_number', true, false);
  $filenameastitle = GetVar('filenameastitle', 1, 'natural_number', true, false);

  echo '</td>
    </tr>
    <tr>
      <td class="tdrow2">Author name for all files:</td>
      <td class="tdrow3" valign="top" colspan="2">
        <input type="text" name="author" size="20" value="'.$author.'" />
      </td>
    </tr>
    <tr>
      <td class="tdrow2">Display file(s) as <strong>online</strong>?</td>
      <td class="tdrow3" colspan="2" valign="top">
        <select name="activated" style="min-width: 50px;">
          <option value="0" '.( $activated ? '' : 'selected="selected"').'>'.DownloadManagerTools::GetPhrase('no').'</option>
          <option value="1" '.(!$activated ? '' : 'selected="selected"').'>'.DownloadManagerTools::GetPhrase('yes').'</option>
        </select>
      </td>
    </tr>
    <input type="hidden" name="MAX_FILE_SIZE" value="' . $max_upload_size . '" />
    <input type="hidden" name="maxFiles" value="' . $maxFiles . '" />
    <tr>
      <td class="tdrow2">Automatically apply thumbnail based on file\'s extension (if file itself is not an image)?</td>
      <td class="tdrow3" valign="top">
        <select name="auto_thumbnail" style="min-width: 50px;">
          <option value="0" '.( $auto_thumbnail ? '' : 'selected="selected"').'>'.DownloadManagerTools::GetPhrase('no').'</option>
          <option value="1" '.(!$auto_thumbnail ? '' : 'selected="selected"').'>'.DownloadManagerTools::GetPhrase('yes').'</option>
        </select>
      </td>
    </tr>
      <tr>
      <td class="tdrow2">Automatically create thumbnail from file if it is of a known image type, such as bmp, gif, png and jpg?</td>
      <td class="tdrow3" valign="top" colspan="2">
        <select name="createthumbnail" style="min-width: 50px;">
          <option value="0" '.( $createthumbnail ? '' : 'selected="selected"').'>'.DownloadManagerTools::GetPhrase('no').'</option>
          <option value="1" '.(!$createthumbnail ? '' : 'selected="selected"').'>'.DownloadManagerTools::GetPhrase('yes').'</option>
        </select>
      </td>
    </tr>
    <tr>
      <td class="tdrow2">Use filename as <strong>title</strong> instead of default file1, file2 etc.?</td>
      <td class="tdrow3" valign="top" colspan="2">
        <select name="filenameastitle" style="min-width: 50px;">
          <option value="0" '.( $filenameastitle ? '' : 'selected="selected"').'>'.DownloadManagerTools::GetPhrase('no').'</option>
          <option value="1" '.(!$filenameastitle ? '' : 'selected="selected"').'>'.DownloadManagerTools::GetPhrase('yes').'</option>
        </select>
      </td>
    </tr>
    ';

  for($i = 0; $i < $maxFiles; $i++)
  {
    echo '
    <tr><td class="tdrow1" colspan="3">File ' . ($i+1) . ':</td></tr>
    <tr>
      <td class="tdrow2">Browse your computer for the file to be uploaded:</td>
      <td class="tdrow3" valign="top"><input type="text" name="title' . $i . '" size="20" value="'.
      (isset($_POST['title'.$i]) ? $_POST['title'.$i] : ('file' . ($i+1))) . '" /></td>
      <td class="tdrow3" valign="top"><input type="file" name="file' . $i . '" size="40" /></td>
    </tr>';
  }

  echo "\n    </table>\n";

  EndSection();

  PrintSubmit('batchupload', 'Upload Files', 'batchupload', 'ok-sign');

  echo "\n</form>\n";

} //DisplayBatchUploadForm


// ######################### DISPLAY BATCH IMPORT FORM #########################

function DisplayBatchImportForm()
{
  global $DB;

  PrintSection(AdminPhrase('dlm_menu_batch_import'));

  // Prevent caching of directory function results
  clearstatcache();

  // Check Import directory on server (admin only)
  $errors = array();
  $this->importdir = DownloadManagerTools::dlm_FixPath(dirname(__FILE__) . '/import/');
  if(is_dir($this->importdir))
  {
    if(!is_writable($this->importdir))
    {
      $errors[] = AdminPhrase('dlm_files_import_directory'). ' ('.
                        $this->importdir.') '.
                        AdminPhrase('dlm_directory_not_writable');
    }
  }
  else
  {
    $errors[] = AdminPhrase('dlm_files_import_directory').' ('.
                      $this->importdir.') '.
                      AdminPhrase('dlm_directory_not_exists');
  }

  if(!empty($errors))
  {
    DownloadManagerTools::PrintErrors($errors, 'Configuration Error');
  }

  // Count files
  $count = 0;
  $imagecount = 0;
  $files = array();
  if(false !== ($dir = @opendir($this->importdir)))
  {
    while(FALSE !== ($f = readdir($dir)))
    {
      if((substr($f,0,1) != '.') && (strpos($f, '.php') === false) &&
         (strpos($f, '..') === false) && (strpos($f, '.htm') === false))
      {
        $files[] = $f;
        $count++;
        if(strlen(DownloadManagerTools::dlm_HasFileImageExt($f)))
        {
          $imagecount++;
        }
      }
    }
    closedir($dir);
  }

  if(!empty($errors) || empty($count))
  {
    echo '<form method="post" action="'.REFRESH_PAGE.'">
    '.PrintSecureToken().'
    <table width="100%" border="0" cellpadding="5" cellspacing="0">
    <tr><td class="tdrow1" align="center"><h3>No files found for import!</h3></td></tr>
    <tr><td class="tdrow3" align="center">Please place files in this folder:<strong>"'.$this->importdir.'"</strong> </td></tr>
    </table>';
    EndSection();
    PrintSubmit('submit','Return to Download Manager');
    echo '</form>';
    return;
  }

  // Display usage hints incl. directory names
  echo '
  <form method="post" enctype="multipart/form-data" id="batchimport" action="'.REFRESH_PAGE.'">
  '.PrintSecureToken().'
  <table width="100%" border="0" cellpadding="5" cellspacing="0">
    <tr>
      <td class="tdrow2" valign="top">Instructions</td>
      <td class="tdrow3" width="70%">
      This form will import <strong>all</strong> files from your below "<strong>import</strong>" directory into
      the configured Download Manager file storage area.<br />
      <br />
      Place the files to be imported into this directory on your server:<br />
      <strong>"'.$this->importdir.'"</strong><br />
      <br />
      Upon import they will be renamed and <strong>moved</strong> into the <strong>File Storage Location</strong>
      folder as specified in the "<strong>Settings</strong>" (or the default "./ftpfiles"
      directory if that is empty):<br />
      "<strong>'.DownloadManagerTools::$filesdir.'</strong>"<br /><br />
      <hr>
      <br />
      <u>Usage Hints:</u>
      <ul style="margin-left: 20px;">
      <li>Successfully imported files are being <strong>deleted</strong> <i>from the import</i> directory afterwards!</li>
      <li>All files will be initially marked as offline if not otherwise selected below.</li>
      <li>Be carefull not to import huge files that may error because of upload limit restrictions.</li>
      </ul>
      </td>
    </tr>
    <tr>
      <td class="tdrow2" valign="top">Number of files available for import:</td>
      <td class="tdrow3" width="20%" valign="top"><strong>' . $count . '</strong> in total
        &nbsp;' . ($imagecount?' with '.$imagecount.' images':'(no images)').'&nbsp;&nbsp;
        <strong><a href="'.REFRESH_PAGE.'&amp;action=displaybatchimportform">Check again</a></strong>
        <br /><br />';

  // Display list of files
  if(!empty($files))
  {
    foreach($files as $name)
      echo $name . '<br />';
  }
  echo '
      </td>
    </tr>
    <tr>
      <td class="tdrow2" width="25%" valign="top">Access<strong> Permissions</strong><br /><br />
      You can restrict access to this file to specific usergroups or users.
      You may only select one or more usergroups <strong>OR</strong> one or more users but you cannot use both.
      <br />(Use [CTRL/Shift+Click] to select multiple usergroups or users)</td>
      <td class="tdrow3" width="75%" valign="top">';

  $this->PrintUserGroupSelection('file_usergroups[]', array(), 'file_users[]');
  echo '&nbsp;';
  $this->PrintUserSelection('file_users[]', array(),'file_usergroups[]');

  echo '</td>
    </tr>';
  $filestore = isset($_POST['filestore']) ? (int)$_POST['filestore'] : DownloadManagerTools::GetSetting('default_upload_location');
  echo '
      </select>
      </td>
    </tr>
    <tr>
      <td class="tdrow2"><strong>File Storage Location</strong> for imported files?</td>
      <td class="tdrow3" colspan="2" valign="top">
        <select name="filestore">
          <option value="0" ' . ( empty($filestore) ? 'selected="selected"' : '') . '>Database</option>
          <option value="1" ' . (!empty($filestore) ? 'selected="selected"' : '') . '>Filesystem</option>
        </select>
      </td>
    </tr>
    <tr>
      <td class="tdrow2" valign="top">Section to show imported files in:</td>
      <td class="tdrow3" width="70%" valign="top">';

  DownloadManagerTools::dlm_PrintSectionSelection(
    array('formId' => 'sectionid[]') //SD370: "[]" was missing
    );

  echo '
    </td>
    </tr>
    <tr>
      <td class="tdrow2" width="40%" ><strong>Import into</strong> Default (empty) or specific section\'s <strong>folder</strong>?</td>
      <td class="tdrow3" valign="top" colspan="2">
      Select a "Section" directory (leave empty for default):<br />
      <select name="fsprefix" style="width:300px;" >
      <option value="" selected="selected" ></option>
      ';

  // Fetch file system prefixes from sections
  if($getsections = $DB->query('SELECT fsprefix FROM {p'.$this->PLUGINID.'_sections} WHERE LENGTH(fsprefix) > 0'))
  {
    while($sec = $DB->fetch_array($getsections,null,MYSQL_ASSOC))
    {
      echo '      <option value="'.$sec['fsprefix'].'">'.$sec['fsprefix']."</option>\n";
    }
    $DB->free_result($getsections);
  }

  echo '
      </select>
      </td>
    </tr>
    <tr>
      <td class="tdrow2">Author name (for all files):</td>
      <td class="tdrow3" width="70%" valign="top">
        <input type="text" name="author" size="40" /></td>
        </tr>
        <tr>
          <td class="tdrow2">Display files online by default?</td>
          <td class="tdrow3" valign="top">
            <select name="activated">
              <option value="0">'.DownloadManagerTools::GetPhrase('no').'</option>
              <option value="1" selected="selected">'.DownloadManagerTools::GetPhrase('yes').'</option>
            </select>
          </td>
        </tr>
        <tr>
          <td class="tdrow2">Automatically apply thumbnail based on file\'s extension?</td>
          <td class="tdrow3" valign="top">
            <select name="auto_thumbnail">
              <option value="0">'.DownloadManagerTools::GetPhrase('no').'</option>
              <option value="1" selected="selected">'.DownloadManagerTools::GetPhrase('yes').'</option>
            </select>
          </td>
        </tr>
        <tr>
          <td class="tdrow2">Delete imported files (default: yes)?</td>
          <td class="tdrow3" valign="top">
            <select name="delete_imported">
              <option value="0">'.DownloadManagerTools::GetPhrase('no').'</option>
              <option value="1" selected="selected">'.DownloadManagerTools::GetPhrase('yes').'</option>
            </select>
          </td>
        </tr>
        ';

    if($imagecount>0)
    {
      echo '<tr>
            <td class="tdrow2">Automatically create Thumbnails for known image types, such as bmp, gif, png and jpg?</td>
            <td class="tdrow3" valign="top">
              <select name="createthumbnail">
                <option value="1" selected="selected">'.DownloadManagerTools::GetPhrase('yes').'</option>
                <option value="0">'.DownloadManagerTools::GetPhrase('no').'</option>
              </select>
            </td>
          </tr>';
  }

  echo '</table>';

  EndSection();

  PrintSubmit('batchimport', 'Import Files', 'batchimport', 'ok-sign');

  echo '</form>';

} //DisplayBatchImportForm


// ############################# DISPLAY FILE FORM #############################

function DisplayFileForm($fileid)
{
  global $DB, $dlm_currentdir, $userinfo;

  $ftpfile = GetVar('ftpfile',0,'natural_number');

  $accessgroups = array();
  $accessusers = array();
  $sections = array();

  echo '
  <form id="dlm_user_form" method="post" enctype="multipart/form-data" action="'.REFRESH_PAGE.'" class="form-horizontal">
  '.PrintSecureToken().'
  ';

  $editmode = !empty($fileid) && is_numeric($fileid);
  if($editmode)
  {
    $editmode = true;

    $file = $DB->query_first('SELECT activated, uniqueid, currentversionid,
      title, author, description, downloadcount, image, thumbnail,
      datestart, dateend, dateadded, dateupdated, maxdownloads, maxtype,
      licensed, standalone, access_groupids, access_userids,
      embedded_in_list, embedded_in_details, audio_class, video_class,
      media_autoplay, media_download, media_loop, embed_width, embed_height
      FROM {p'.$this->PLUGINID.'_files}
      WHERE fileid = %d LIMIT 1',$fileid);

    if($getsections = $DB->query('SELECT sectionid FROM {p'.$this->PLUGINID.'_file_sections} WHERE fileid = %d',$fileid))
    {
      while($sec = $DB->fetch_array($getsections,null,MYSQL_ASSOC))
      {
        $sections[] = (int)$sec['sectionid'];
      }
      $DB->free_result($getsections);
    }
    unset($getsections);

    //v2.3.0: load tags from core "tags" table
    $file['tags'] = '';
    $tags = SD_Tags::GetPluginTags($this->PLUGINID, $fileid);
    if(!empty($tags) && is_array($tags))
    {
      $file['tags'] = implode(',', $tags);
    }

    if(!empty($file['access_groupids'])) $accessgroups = explode('|',$file['access_groupids']);
    if(!empty($file['access_userids']))  $accessusers  = explode('|',$file['access_userids']);

   // echo '<h2 class="header blue lighter"> ' . AdminPhrase('dlm_edit_file') . '</h2>';

    echo '<input type="hidden" name="fileid" value="'.$fileid.'" />
          <input type="hidden" name="action" value="updatefile" />
          <input type="hidden" name="load_wysiwyg" value="0" />';
  }
  else
  {
    $file = array(
      'activated'      => GetVar('activated', 1, 'natural_number', true, false),
      'uniqueid'       => GetVar('uniqueid','','string',true,false),
      'version'        => GetVar('version',DownloadManagerTools::GetVar('DEFAULT_VERSION'),'string',true,false),
      'currentversionid' => '0',
      'title'          => GetVar('title','','string',true,false),
      'author'         => GetVar('author',$userinfo['username'],'string',true,false),
      'description'    => GetVar('description','','html',true,false),
      'downloadcount'  => GetVar('downloadcount',0,'natural_number',true,false),
      'image'          => '',
      'thumbnail'      => '',
      'datestart'      => GetVar('datestart',TIME_NOW,'natural_number',true,false),
      'dateend'        => GetVar('dateend',0,'natural_number',true,false),
      'dateadded'      => GetVar('dateadded',TIME_NOW,'natural_number',true,false),
      'dateupdated'    => GetVar('dateend',0,'natural_number',true,false),
      'maxdownloads'   => GetVar('maxdownloads',0,'natural_number',true,false),
      'maxtype'        => GetVar('maxtype',0,'natural_number',true,false),
      'licensed'       => (!NotEmpty(DownloadManagerTools::GetSetting('default_license_agreement'))?0:1),
      'standalone'     => GetVar('standalone',0,'natural_number',true,false),
      'access_groupids'     => array(),
      'access_userids'      => array(),
      'tags'                => GetVar('tags','','string',true,false),
      'embedded_in_list'    => GetVar('embedded_in_list',0,'natural_number',true,false),
      'embedded_in_details' => GetVar('embedded_in_details',1,'natural_number',true,false),
      'audio_class'         => GetVar('audio_class', 'jp-player', 'html', true, false),
      'video_class'         => GetVar('video_class', 'jp-video-270p', 'html', true, false),
      'media_autoplay'      => GetVar('media_autoplay', 0, 'natural_number', true, false),
      'media_download'      => GetVar('media_download', 0, 'natural_number', true, false),
      'media_loop'          => GetVar('media_loop', 0, 'natural_number', true, false),
      'embed_width'         => GetVar('embed_width', 0, 'natural_number', true, false),
      'embed_height'        => GetVar('embed_height', 0, 'natural_number', true, false)
    );

    $sections = (isset($_POST['sectionids'])?GetVar('sectionids',null,'array',true,false):null);
    $sections = !empty($sections) ? $sections : (empty($sectionid) ? array(1) : array($sectionid));

    echo '<h2 class="header blue lighter">' . (!$ftpfile ? AdminPhrase('dlm_menu_add_file_by_upload') : AdminPhrase('dlm_menu_add_file_by_url')) . '</h2>';

    echo '<input type="hidden" name="action" value="updatefile" />';
  }

  if(empty($file['dateend']))
  {
    $file['dateend'] = '';
  }

  echo '
  <div class="form-group">
  	<label class="control-label col-sm-2">'.DownloadManagerTools::GetPhrase('file_title').'</label>
	<div class="col-sm-6">
		<input type="text" name="title" value="'. CleanFormValue($file['title']).'" class="form-control">
		<span class="helper-text">'.AdminPhrase('dlm_file_title_hint1').'</span>
	</div>
</div>
  <div class="form-group">
  	<label class="control-label col-sm-2">'.DownloadManagerTools::GetPhrase('author_name').'
  </label>
	<div class="col-sm-6">
    <input type="text" class="form-control" name="author" value="'.
    CleanFormValue($file['author']).'" ></div>
	</div>
 <div class="form-group">
  	<label class="control-label col-sm-2">'.AdminPhrase('dlm_display_file_online').'</label>
	<div class="col-sm-6">
		<select name="activated" class="form-control">
        <option value="1" ' . (!empty($file['activated'])?'selected="selected"':'') . '>'.DownloadManagerTools::GetPhrase('yes').'</option>
        <option value="0" ' . ( empty($file['activated'])?'selected="selected"':'') . '>'.DownloadManagerTools::GetPhrase('no').'</option>
      </select>
	</div>
	</div>
    <div class="form-group">
  	<label class="control-label col-sm-2">'.AdminPhrase('dlm_section').'</label>
	<div class="col-sm-6">';
	 
	DownloadManagerTools::dlm_PrintSectionSelection(
        array('formId'            => 'sectionids[]',
              'selectedSectionId' => $sections,
			  'extraStyle'        => '',));
			  
	echo '<span class="helper-text"> '.AdminPhrase('dlm_section_hint1').'</span>
	</div>
	</div>
    <div class="form-group">
  	<label class="control-label col-sm-2">'.DownloadManagerTools::GetPhrase('date_added').'</label>
	<div class="col-sm-6"> 
		<input type="text" class="form-control" name="dateadded" size="20" '.(!$editmode?'value="'.DisplayDate($file['dateadded']).'"':'').' />
		<span class="helper-text"> '.(empty($file['dateadded'])?  ''.AdminPhrase('dlm_dateentry_hint').': '.DisplayDate(TIME_NOW):
        ($editmode?'<strong>'.AdminPhrase('dlm_current_value').': '.DisplayDate($file['dateadded']).'</strong>':'')).
      ($editmode?''.AdminPhrase('dlm_dateadded_hint'):'').
    '</span>
	</div>
	</div>
     <div class="form-group">
  	<label class="control-label col-sm-2">'.DownloadManagerTools::GetPhrase('date_updated').'</label>
	<div class="col-sm-6"> 
    	<input type="text" class="form-control" name="dateupdated" size="20" />
		<span class="helper-text"> '. (empty($file['dateupdated'])? ''.AdminPhrase('dlm_dateentry_hint').': '.DisplayDate(TIME_NOW):
      ($editmode?'<strong>'.AdminPhrase('dlm_current_value').': '.DisplayDate($file['dateupdated']).'</strong>':'')).
    ($editmode?''.AdminPhrase('dlm_dateadded_hint'):'').'</span>
    </div>
  </div>
  </table>
  </td>
  </tr>
 <div class="form-group">
  	<label class="control-label col-sm-2">'.DownloadManagerTools::GetPhrase('file_description').'</label>
	<div class="col-sm-6"> ';
  DisplayBBEditor($this->allow_bbcode, 'description', $file['description'], 'file_descr', 80, 15);
  echo '
    </div>
  </div>';

  // File "tags" work like keywords and can be used for e.g. a tags cloud or related files
  echo '
  <div class="form-group">
  	<label class="control-label col-sm-2">'.DownloadManagerTools::GetPhrase('dlm_tags').'
	<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="'.DownloadManagerTools::GetPhrase('dlm_tags_hint').'" title="Help">?</span></label>
	<div class="col-sm-6">
      <input type="text" id="tags" name="tags" class="form-control" value="' . $file['tags'] . '" size="80" />
	  
	 </div>
	</div>';

  // Display download count and file rating - not for NEW file creation
  if(!empty($fileid))
  {
    echo '
  <div class="form-group">
  	<label class="control-label col-sm-2">'.AdminPhrase('dlm_reset_rating').'</label>
	<div class="col-sm-6">
      <select name="resetrating" class="form-control">
        <option value="0" selected="selected">'.DownloadManagerTools::GetPhrase('no').'</option>
        <option value="1">'.DownloadManagerTools::GetPhrase('yes').'</option>
      </select>
    </div>
	</div>
    <div class="form-group">
  	<label class="control-label col-sm-2">Number of times this file has been downloaded/viewed:</label>
	<div class="col-sm-6">
      <input type="text" class="form-control" name="downloadcount" value="'.$file['downloadcount'].'"/>
    </div>
  </div>';
  }
  //  <table width="100%" border="0" cellpadding="5" cellspacing="0" summary="layout">

  // Display and allow entering of unique id for new file
  echo '
   <div class="form-group">
  	<label class="control-label col-sm-2">License Agreement</label>
	<div class="col-sm-6">
		<select name="licensed" class="form-control">
        <option value="0" ' . (empty($file['licensed'])?'selected="selected"':'') .'>'.DownloadManagerTools::GetPhrase('no').'</option>
        <option value="1" ' . (!empty($file['licensed'])?'selected="selected"':'').'>'.DownloadManagerTools::GetPhrase('yes').'</option>
      </select>
	  <span class="helper-text"> Require user to first accept License Agreement before download (license text
      is configured in the Settings)?</span>
	</div>
</div>
    <div class="form-group">
  	<label class="control-label col-sm-2">'.DownloadManagerTools::GetPhrase('dlm_unique_id').'</label>
	<div class="col-sm-6">
       <input type="text" class="form-control" name="uniqueid" value="' . $file['uniqueid'] . '" size="40" />
	   <span class="helper-text">'.DownloadManagerTools::GetPhrase('dlm_unique_id_hint').'</span>
	</div>
</div>
    </td>
  <div class="form-group">
  	<label class="control-label col-sm-2">Access Permissions
	<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content=" Restrict view/download access to this file to specific usergroups OR individual users?<br />
      You may only select one or more usergroups <strong>OR</strong> one or more users but you cannot use both.
      The selected groups or users will not see this file and are not allowed to download the file,
      even if the link was known.<br /><br />
      Use [CTRL/Shift+Click] to select one or multiple usergroups (or none) OR
      search for a user by using the quick-search and clicking a name from the results." title="Help">?</span></label>
	<div class="col-sm-6">
      <div style="float:left; display: inline; margin-right: 14px; ">
      Usergroups:<br />';

  $this->PrintUserGroupSelection('file_usergroups[]', $accessgroups);
  echo '</div>
  <div style="display: inline; float: left; height: 100%;">
  Search User:<br />
  <input id="UserSearch" name="searchField" type="text" style="min-width: 150px; width: 150px; padding: 2px;" />
  <br /> Allowed users:
  ';
  $this->PrintUserSelection('file_users', $accessusers);

  echo '</div>
    </div>
  </div>';

  // Option to embed media file in "More Info" page
  echo '
  <h2 class="header blue lighter">'.AdminPhrase('dlm_embedded_in_details').'</h2>
 <div class="form-group">
  	<label class="control-label col-sm-2">'.AdminPhrase('dlm_embedded_in_details_hint').'</label>
	<div class="col-sm-6">
      <select name="embedded_in_details" class="form-control">
        <option value="1" ' . (!empty($file['embedded_in_details'])?'selected="selected"':'').'>'.DownloadManagerTools::GetPhrase('yes').'</option>
        <option value="0" ' . ( empty($file['embedded_in_details'])?'selected="selected"':'').'>'.DownloadManagerTools::GetPhrase('no').'</option>
      </select>
    </div>
  </div>
   <div class="form-group">
  	<label class="control-label col-sm-2">'.AdminPhrase('dlm_file_audio_class_desc').'</label>
	<div class="col-sm-6">
      <input type="text" class="form-control" name="audio_class" value="'.$file['audio_class'].'" />
    </div>
</div>
    <div class="form-group">
  	<label class="control-label col-sm-2">
      '.AdminPhrase('dlm_file_video_class_desc').'</label>
	<div class="col-sm-6">
      <input type="text" class="form-control" name="video_class" value="'.$file['video_class'].'" />
    </div>
  </div>
   <div class="form-group">
  	<label class="control-label col-sm-2">
      '.AdminPhrase('dlm_file_embed_width').'</label>
	<div class="col-sm-6">
      <input type="text" class="form-control" name="embed_width" value="'.$file['embed_width'].'" size="4" />
    </div>
</div>
    <div class="form-group">
  	<label class="control-label col-sm-2">
      '.AdminPhrase('dlm_file_embed_height').'</label>
	<div class="col-sm-6">
      <input type="text" class="form-control" name="embed_height" value="'.$file['embed_height'].'" size="4" />
    </div>
  </div>
  <div class="form-group">
  	<label class="control-label col-sm-2">'.AdminPhrase('dlm_file_media_options_desc').'</label>
	<div class="col-sm-6">
      <input type="checkbox" name="media_download" value="1" '.($file['media_download']?'checked="checked"':'').' />
      '.AdminPhrase('dlm_file_media_download_desc').'<br />
      <input type="checkbox" name="media_autoplay" value="1" '.($file['media_autoplay']?'checked="checked"':'').' />
      '.AdminPhrase('dlm_file_media_autoplay_desc').'<br />
      <input type="checkbox" name="media_loop" value="1" '.($file['media_loop']?'checked="checked"':'').' />
      '.AdminPhrase('dlm_file_media_loop_desc').'
    </div>
  </div>
  <div class="form-group">
  	<label class="control-label col-sm-2">'.AdminPhrase('dlm_embedded_in_list').'
	<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="'.AdminPhrase('dlm_embedded_in_list_hint').'" title="Help">?</span>
  </label>
	<div class="col-sm-6">
      <select name="embedded_in_list" class="form-control">
        <option value="1" ' . (!empty($file['embedded_in_list'])?'selected="selected"':'').'>'.DownloadManagerTools::GetPhrase('yes').'</option>
        <option value="0" ' . ( empty($file['embedded_in_list'])?'selected="selected"':'').'>'.DownloadManagerTools::GetPhrase('no').'</option>
      </select>
      <span class="helper-text">Embedding media site files in the files list is "No" by default. </span>
    </div>
  </div>';

  //v2.2.0
  $this->uploader = false;
  $file_uploader_JS  = '';
  $thumb_uploader_JS = '';
  $image_uploader_JS = '';

  // Create an uploaded object to integrate "plupload" by Moxiecode:
  @require_once(ROOT_PATH.'includes/class_sd_uploader.php');
  $uploader_config = array(
    'html_id'            => 'file_'.$this->PLUGINID,
    'html_upload_remove' => 'file_input',
    'afterUpload'        => 'processFile',
    'maxQueueCount'      => 1,
    'removeAfterSuccess' => 'false',
    'singleUpload'       => 'true',
	'border'			=> false,
	'layout'			=> 'col-sm-12',
  );
  $this->uploader = new SD_Uploader($this->PLUGINID, empty($this->fileid)?0:(int)$this->fileid, $uploader_config);

  // Uploader for main file (before calling DisplayFileVersions()!)
  if((!empty($file['standalone']) || empty($ftpfile)))
  {
    $file_uploader_JS = $this->uploader->getJS();
  }

  $this->DisplayFileVersions($fileid, $file['currentversionid'], $file['standalone']);

  echo '<h2 class="header blue lighter">Optional Thumbnail and Image</h2>';

  echo '
    <div class="form-group">
		<label class="control-label col-sm-2">'.DownloadManagerTools::GetPhrase('optional_thumbnail').'</label>
		<div class="col-sm-6">
        <input type="file" id="file_thumbnail" name="thumbnail" size="40" />
        ';

  if($this->uploader)
  {
    $this->uploader->setValue('html_id', 'thumb_'.$this->PLUGINID);
    $this->uploader->setValue('html_upload_remove', 'file_thumbnail');
    $this->uploader->setValue('removeAfterSuccess', 'false');
    $this->uploader->setValue('filters', '{title : "Images", extensions : "jpg,jpeg,gif,png,tif,svg,bmp"}');
    $this->uploader->setValue('title', '<strong>Select a single thumbnail image for upload:</strong>');
    $thumb_uploader_JS = $this->uploader->getJS();
    echo $this->uploader->getHTML();
  }

    // Offer View/Delete only when editing, not inserting a file
  if(!empty($fileid) && strlen($file['thumbnail']))
  {
    // Preview thumbnail
    /*
    $img_file = SD_INCLUDE_PATH.'phpthumb/phpThumb.php?src='.DownloadManagerTools::$imagesdir . $file['thumbnail'].'&fltr[]=wmt|Subdreamer|20|C|FF0000|tahoma.ttf|100';
    $watermarked = '<img src="'.SD_INCLUDE_PATH.'phpthumb/phpThumb.php?src='.
          DownloadManagerTools::$imagesdir . $file['thumbnail'].
          '&fltr[]=wmt|'.urlencode(PRGM_NAME).'|20|C|FF0000|tahoma.ttf|100" border="0" id="imageimg" hspace="0" vspace="0" style="padding: 4px; margin: 0px;" />';
    */
    $img_file = DownloadManagerTools::$imageurl . $file['thumbnail'];
    $extratext = ' <a href="'.REFRESH_PAGE.'&amp;action=deletefileimage&amp;fileid='.$fileid.
                 '&amp;imagename='.$file['thumbnail'].'">Delete Thumbnail</a>';
    DownloadManagerTools::dlm_DisplayImageDetails($file['thumbnail'], $img_file, false, $extratext);
  }
  else
  {
    echo '<div style="clear: both; margin-top: 4px">
      <input type="checkbox" name="auto_thumbnail" value="1" '.(empty($fileid)?'checked="checked" ':'').' />
      Automatically apply a thumbnail if current file version is of a known type?<br />
      Processes files with extension like e.g. doc, pdf, xls, zip.<br />
      For other extension place a PNG image with the same name as the
      extension in the plugin\'s "images/misc" folder, e.g. "rar.png".
      </div>';
  }

  if(!empty($fileid))
  {
    // Allow to create a thumbnail of the MAIN file -
    // IF that current version file is an image file by extension:
    $filesver = $DB->query_first('SELECT versionid, version, storedfilename, filename, filesize, filetype
          FROM {p'.$this->PLUGINID.'_file_versions}
          WHERE fileid = %d ORDER BY version DESC LIMIT 1', $fileid);
    $imgext = DownloadManagerTools::dlm_HasFileImageExt($filesver['filename']);
    if((strlen($imgext)>0) && (strlen($filesver['storedfilename'])>0))
    {
      echo (strlen($file['thumbnail'])?'<br />':'') .
      '<div style="margin: 10px;"><a class="dlm-admin-link" href="'.REFRESH_PAGE.
        '&amp;action=thumbnailform&amp;fileid=' . $fileid .
        '&amp;useftpdir=0&amp;useimgext=' . $imgext . '&amp;imagename=' . $filesver['storedfilename'] .
        '">Create Thumbnail of latest file version</a></div>';
    }
  }

  echo '
      </div>
    </div>
    <div class="form-group">
		<label class="control-label col-sm-2">'.DownloadManagerTools::GetPhrase('optional_image').'</label>
		<div class="col-sm-6">Browse your computer for the file\'s display image:
        <input type="file" id="file_image" name="image" size="40" />
        ';
  // Re-use uploader to offer upload of optional image:
  if($this->uploader)
  {
    $this->uploader->setValue('html_id', 'image_'.$this->PLUGINID);
    $this->uploader->setValue('html_upload_remove', 'file_image');
    $this->uploader->setValue('removeAfterSuccess', 'false');
    $image_uploader_JS = $this->uploader->getJS();
    echo $this->uploader->getHTML();
  }

  if(strlen($file['image']))
  {
    // Preview of current image
    $extratext = //'<a href="' . DownloadManagerTools::$imageurl . $file['image'] . '" target="_blank">View Image</a> -
      '<a href="'.REFRESH_PAGE.'&amp;action=deletefileimage&amp;fileid='.$fileid.
        '&amp;imagename='.$file['image'].'">Delete Image</a> -
      <a href="'.REFRESH_PAGE.'&amp;action=thumbnailform&amp;fileid='.$fileid.
        '&amp;imagename='.$file['image'].'">Create Thumbnail</a>';
    $maxwidth = $maxheight = 100;
    DownloadManagerTools::dlm_DisplayImageDetails($file['image'], DownloadManagerTools::$imageurl . $file['image'], false, $extratext);
  }

  echo '
      </div>
    </div>';

  echo '<h2 class="header blue lighter">Download Counter and Optional Automatic Activation</h2>';

  if(DownloadManagerTools::GetSetting('allow_per_user_limits') != 1)
  {
    echo '<input type="hidden" name="maxtype" value="0" />';
  }
  echo '
    <div class="form-group">
		<label class="control-label col-sm-2">Maximum Downloads</label>
		<div class="col-sm-6"><input type="text" class="form-control" name="maxdownloads" value="'.
        $file['maxdownloads'].'" size="10" />
		<span class="helper-text">Maximum number of times the file can be downloaded<br />
        File will be automatically deactivated when limit reached (Leave at 0 for unlimited)</span>
      </div>
    </div>';

  if(DownloadManagerTools::GetSetting('allow_per_user_limits') == 1)
  {
    echo '
     <div class="form-group">
		<label class="control-label col-sm-2">Limit Downloads per user or total downloads?</label>
		<div class="col-sm-6">
        <select name="maxtype" class="form-control">
          <option value="0" '.(empty($file['maxtype'])?'selected="selected"':'').'>Everyone</option>
          <option value="1" '.(!empty($file['maxtype'])?'selected="selected"':'').'>Per User</option>
        </select>
      </div>
    </div>';
  }
  echo '
   <div class="form-group">
		<label class="control-label col-sm-2">Start Publishing (YYYY-MM-DD HH:MM:DS)</label>
		<div class="col-sm-6">
		<input type="text" class="form-control" name="datestart" value="' .
      DisplayDate($file['datestart'], 'Y-m-d H:i:s') . '" size="20" />
	  </div>
    </div>
    <div class="form-group">
		<label class="control-label col-sm-2">Finish Publishing (YYYY-MM-DD HH:MM:SS)</label>
		<div class="col-sm-6"><input type="text" class="form-control" name="dateend" value="' .
      (empty($file['dateend'])?'':DisplayDate($file['dateend'],'Y-m-d H:i:s')).'" size="20" />
	  <span class="helper-text"> Leave this empty to keep the file active forever.</span>
    </div>
	</div>';
  // FOOTER AREA
  echo '
  <div class="center">
       <button class="btn btn-info" id="submit_button" type="submit" value=""><i class="ace-icon fa fa-check"></i> '.
      ($fileid?'Update':($ftpfile?'Upload':'Add')).' File</button>
</div>
  </form>';

  echo '
  <script type="text/javascript">
  //<![CDATA[
  function processFile(file_id, file_name, file_title) {
    jQuery("#progress_"+file_id).hide();
    jQuery("#filelist_"+file_id).html("");
    jQuery("#uploader_"+file_id+"_messages").html("File \""+file_title+"\" uploaded!").show();
  }

  jQuery(document).ready(function() {
    jQuery("a.deletelink").click(function(event){
      if(confirm("'.AdminPhrase('confirm_version_delete').'")) {
        return true;
      } else {
        event.preventDefault();
        return false;
      }
    });

    jQuery("#adduser").click(function(){
      var username = $("#UserSearch").val();
      var user_id  = $("#last_selected_id").text();
      //var mytext = $("#file_users option[value=\'" + username + "\']").text();
      var mytext = $("#file_users option[value=\'" + username + "\']").text();
      //if(mytext == "") {
        //jQuery("#file_users").append(new Option(username,user_id));
      //}
    });

    jQuery(document).delegate("#deluser","click",function(){
      var user_id = jQuery(this).attr("rel");
      if(typeof user_id !== "undefined") {
        jQuery("li#user_"+jQuery(this).attr("rel")).remove();
        jQuery("input#file_user_"+jQuery(this).attr("rel")).remove();
      }
    });
    jQuery("#UserSearch").blur(function(){
      jQuery("#UserSearch").val("");
      jQuery("#adduser").hide();
    }).autocomplete({
      source: "'.SITE_URL.'includes/ajax/getuserselection.php",
      useCache: false,
      onItemSelect: function(item) {
        var user_id = 0;
        if(item.data.length) {
          user_id = parseInt(item.data,10);
        }
        if(user_id !== null && user_id > 0) {
          var id_exists = $("#user_"+user_id).val();
          if (typeof id_exists == "undefined") {
            jQuery("<li id=\"user_"+user_id+"\">").html(\'<input type="hidden" name="file_users[]" value="\'+user_id+\'" /><div>\'+item.value+\'</div><img id="deluser" rel="\'+user_id+\'" src="'.ADMIN_IMAGES_FOLDER.'icons/eraser.png" height="16" width="16" alt="[-]" />\').appendTo("#file_users");
          }
        }
      },
      maxItemsToShow: 8,
      minLength: 2
    });
  '.$file_uploader_JS.'
  '.$thumb_uploader_JS.'
  '.$image_uploader_JS.'
  });
  //]]>
  </script>
  ';

} //DisplayFileForm


// ############################################################################

function DisplayFileVersions($fileid, $currentversionid = -1, $standalone = 0)
// $standalone has now 3 values (0 = default; 1 = standalone; 2 = mirror)
{
  global $DB, $dlm_currentdir;

  $ftpfile = GetVar('ftpfile',0,'whole_number');
  $filestore = DownloadManagerTools::GetSetting('default_upload_location');

  echo '<h2 class="header blue lighter">File Versions</h2>';

  // Added "Standalone" and "Mirror" file attribute values which
  // will cause the "File Versions" being displayed in different ways in the
  // Downloads list (just frontend).
  echo '<div class="form-group">
  			<label class="control-label col-sm-2">File Version Type
			<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="<strong>Default</strong> = only current file version is displayed for download<br />
        <strong>Standalone</strong> = all file versions are displayed as separate download links<br />
        <strong>Mirror</strong> = all file versions display as "Mirror x" as mirrors of the main file" title="Help">?</span></label>
			<div class="col-sm-6">
        <select name="standalone" class="form-control">
        <option value="0" '.($standalone==0?'selected="selected"':'').'>Default (Version)</option>
        <option value="1" '.($standalone==1?'selected="selected"':'').'>Standalone</option>
        <option value="2" '.($standalone==2?'selected="selected"':'').'>Mirror</option>
        </select>
      </div>
    </div>';

  if(($standalone > 0) || (!empty($fileid)) || !empty($ftpfile))
  {
    echo '<div class="form-group">
  			<label class="control-label col-sm-2">' .
      ($standalone>0?'Add external file by ':'') . 'FTP/URL File Path
	  </label>
    <div class="col-sm-6">
        <input type="hidden" name="ftpfile" value="1" />
        <input type="text" class="form-control" name="ftpfilename" size="60" />
      </div>
    </div>';
  }

  if(($standalone > 0) || empty($ftpfile))
  {
    $max_upload_size = strlen(DownloadManagerTools::GetSetting('max_upload_size')) > 0 ?
                       ((int)DownloadManagerTools::GetSetting('max_upload_size') * 1024) :
                       (int)DownloadManagerTools::GetVar('DEFAULT_MAX_UPLOAD_SIZE');
    echo '
   <div class="form-group">
  			<label class="control-label col-sm-2">Add New File (Upload)<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="Browse your computer for the file you would like to upload:<br />
      PHP Uploads are currently <strong>'.(ini_get('file_uploads') ? 'enabled' : 'disabled').'</strong>,
      PHP Max Filesize is <strong>' . ini_get('upload_max_filesize') . '</strong><br />
      and your Subdreamer max. Upload Size is <strong>'.
      DownloadManagerTools::dlm_DisplayReadableFilesize($max_upload_size).'</strong>" title="Help">?</span></label>
    <div class="col-sm-6">
        <input type="hidden" name="MAX_FILE_SIZE" value="'.$max_upload_size.'" />
        <input type="file" id="file_input" name="file" size="50" style="display: none" />
        ';

    if($this->uploader)
    {
      echo $this->uploader->getHTML();
    }

    echo '
      &nbsp;Available target folders related to Sections:<br />
      <select name="fsprefix" style="margin-top: 4px; height: 24px; padding: 2px; width: 95%;" >
      <option value="" selected="selected" ></option>
      ';

    // Check for filesystem-prefixes of sections
    if($getsections = $DB->query('SELECT fsprefix FROM {p'.$this->PLUGINID.'_sections} WHERE LENGTH(fsprefix) > 0'))
    {
      while($sec = $DB->fetch_array($getsections,null,MYSQL_ASSOC))
      {
        echo '<option value="'.$sec['fsprefix'].'">'.$sec['fsprefix'].'</option>';
      }
      $DB->free_result($getsections);
    }

    echo '
          </select>
         
        </div>
      </div>
    <div class="form-group">
		<label class="control-label col-sm-2">File Storage</label>
		<div class="col-sm-6">
        <select name="filestore" class="form-control">
          <option value="0" ' . ($filestore == 0 ? 'selected="selected"' : '') . '>DB</option>
          <option value="1" ' . ($filestore == 1 ? 'selected="selected"' : '') . '>Filesystem</option>
        </select>
		<span class="helper-text"> Where should the new file version be stored?<br />
        It is recommended to use the filesystem if either large or many files are stored as otherwise
        the database grows very large and slows down the site.</span>
      </div>
    </div>
    ';
  }

  // If it is a "Default" file version, then default the new
  // version number to the most recently added file (highest versionid).
  // For "Standalone" and "Mirror" the new version number is taken from
  // the main file, which is the first file added, therefore "ASC" order:
  if($standalone==0)
  {
    $fileversort = 'DESC';
  }
  else
  {
    $fileversort = 'ASC';
  }
  $filever = $DB->query_first('SELECT version FROM {p'.$this->PLUGINID.'_file_versions}'.
                              ' WHERE fileid = %d ORDER BY versionid ' .
                              $fileversort .
                              ' LIMIT 1',$fileid);
  $filever = (!empty($filever[0])?$filever[0]:DownloadManagerTools::GetVar('DEFAULT_VERSION'));
  echo '<div class="form-group">
		<label class="control-label col-sm-2">Version #</label>
		<div class="col-sm-6">
          <input type="text" class="form-control" name="version" size="30" value="'.$filever.'" />
      </div>
    </div>

    <table class="table table-bordered table-striped">
	<thead>
    <tr>
      <th class="tdrow1">ID</th>
      <th class="tdrow1">Original File Name</th>
      <th class="tdrow1">Stored File Name</th>
      <th class="tdrow1">Version</th>
      <th class="tdrow1">File Size</th>
      <th class="tdrow1">Embeddable</th>
      <th class="tdrow1">Storage</th>
      <th class="tdrow1">Options</th>
    </tr>
	</thead>';

  if($standalone==0)
  {
    $fileversort = 'version DESC';
  }
  else
  {
    $fileversort = 'versionid ASC';
  }
  if($getfiles = $DB->query('SELECT versionid, version, storedfilename, filename,'.
                      ' filesize, filetype, is_embeddable_media'.
                      ' FROM {p'.$this->PLUGINID.'_file_versions}'.
                      ' WHERE fileid = %d ORDER BY ' . $fileversort, $fileid))
  {
    $versioncount = $DB->get_num_rows($getfiles);
    while($file = $DB->fetch_array($getfiles,null,MYSQL_ASSOC))
    {
      $tmp = substr(strtolower($file['filename']),0,4);
      $isremote = (($tmp == 'http') || ($tmp == 'www.'));
      $embeddable = 0;
      if($isremote)
      {
        // Test, if the filename is a remote, embeddable file supported by embevi
        $embeddable = (DownloadManagerTools::dlm_LinkAsMedia($file['filename'])==$file['filename'] ? 0 : 1);
        $fname = '<a title="Open link" href="'.$file['filename'].'" target="_blank">'.wordwrap($file['filename'], 30, '<br />', 1).'</a>';
      }
      else
      {
        // Is file an embeddable media file with a know file extension?
        $embeddable = 0;
        if($ext = DownloadManagerTools::dlm_GetFileExtension($file['filename']))
        {
          $embeddable = in_array($ext, DownloadManagerTools::$KnownAudioTypes) ||
                        in_array($ext, DownloadManagerTools::$KnownVideoTypes) ? 1 : 0;
        }
        $fname = (!empty($file['filename']) ? $file['filename'] : '-');
      }

      echo '
      <tr>
        <td class="tdrow2" align="center">' . $file['versionid'] . '</td>
        <td class="tdrow2">' . $fname . '</td>
        <td class="tdrow2">' . (strlen($file['storedfilename']) > 0 ? wordwrap($file['storedfilename'], 30, '<br />', 1) : '-');
	  //SD370: only use "fileowner" if file exists
      if(function_exists('posix_getpwuid') && !empty($file['storedfilename']) &&
         file_exists(DownloadManagerTools::$filesdir.$file['storedfilename']))
      {
        echo '<br/>Owner: ';
        $owner = @posix_getpwuid(fileowner(DownloadManagerTools::$filesdir.$file['storedfilename']));
        if(($owner !== false) && is_array($owner) && isset($owner['name']))
        {
          echo $owner['name'];
        }
      }
      echo '</td>
        <td class="tdrow2" align="center">' . (empty($file['version'])?'-':$file['version']) . '</td>
        <td class="tdrow2" align="center">' . DownloadManagerTools::dlm_DisplayReadableFileSize($file['filesize']) . '&nbsp;</td>
        <td class="tdrow2" align="center">' . ($embeddable ? DownloadManagerTools::GetPhrase('yes') : DownloadManagerTools::GetPhrase('no')).'</td>
        <td class="tdrow2" align="center">';
      if(($file['filesize']==0) || $isremote)
        echo 'None (external link)';
      else
        echo (($file['filetype']!=='FTP') && (strlen($file['storedfilename'])==0)) ? 'DB' : 'Filesystem';

      echo '</td>
        <td class="tdrow2" width="140" align="left">';

      // Depending if file is an URL or file in local filesystem, display open/download link
      if($file['filetype'] == 'FTP')
      {
        if(($file['filesize']==0) || $isremote)
        {
          // Link to remote or local file
          if($isremote)
          {
            echo '<a href="' . $file['filename'] . '" target="_blank">Open link</a>';
          }
          else
          {
            echo '<a href="' . DownloadManagerTools::$filesdir . $file['filename'] . '" target="_blank">Download</a>';
          }
        }
        else
        {
          // Assume it is a "local" file
          echo '<a href="' . DownloadManagerTools::$filesdir . $file['filename'] . '" target="_blank">Download</a>';
        }
      }
      else
      {
        echo '<a href="'.SITE_URL.DownloadManagerTools::GetVar('PATH').'getfile.php?p'.$this->PLUGINID.'_fileid='.$fileid.
          DownloadManagerTools::GetVar('URI_TAG').'_sectionid=1'.
          DownloadManagerTools::GetVar('URI_TAG').'_versionid='.$file['versionid'].
          '&amp;in_admin=1" target="_blank">Download</a>';
      }

      echo '|<a href="'.REFRESH_PAGE.'&amp;action=editversion&amp;fileid='.$fileid.
           '&amp;versionid='.$file['versionid'].'&amp;load_wysiwyg=0">Edit</a>';

      if($versioncount > 1)
      {
        echo '|<a class="deletelink" href="'.REFRESH_PAGE.'&amp;action=deletefile&amp;fileid='.$fileid.'&amp;versionid='.
        $file['versionid'].'&amp;load_wysiwyg=0">Delete</a>';
      }
      echo '
        </td>
      </tr>';
    } //while

    $DB->free_result($getfiles);
  }
  echo '
    <tr>
      <td class="tdrow2" colspan="8"><strong>Note:</strong> Files are sorted '.
      ($standalone==0?
        'by "Version" in descending order (latest file version or main file is at top).':
        'ascendingly in order of entry (main file at top).') . '
      </td>
    </tr>
  </table>';

} //DisplayFileVersions


// ############################# DISPLAY FILE VERSION  ###############################

function DisplayFileVersionEditForm($fileid, $versionid)
{
  global $DB;

  echo '
  <br /><a href="'.REFRESH_PAGE . "&amp;action=displayfileform&amp;load_wysiwyg=0&amp;fileid=$fileid".'">&laquo; Back to File</a>';

  // Added "datecreated" in SELECT
  $file = $DB->query_first('SELECT fv.versionid, fv.version, fv.storedfilename,
    fv.filename, fv.filesize, fv.filetype, fv.datecreated, f.title
    FROM {p'.$this->PLUGINID.'_file_versions} fv
    LEFT JOIN {p'.$this->PLUGINID.'_files} f ON fv.fileid = f.fileid
    WHERE fv.fileid = %d AND fv.versionid = %d LIMIT 1',$fileid,$versionid);

  PrintSection('Edit File Version');

  $fileislink = (substr(strtolower($file['filename']),0,4)=='www.') || (substr(strtolower($file['filename']),0,5)=='http:');
  echo '
  <form method="post" action="'.REFRESH_PAGE.'">
  '.PrintSecureToken().'
  <input type="hidden" name="fileid"  value="'.$fileid.'" />
  <input type="hidden" name="versionid"  value="'.$versionid.'" />
  <input type="hidden" name="action" value="updatefileversion" />

  <table width="100%" border="0" cellpadding="5" cellspacing="0">
  <tr>
    <td class="tdrow2" width="30%"><strong>File:</strong></td>
    <td class="tdrow3" width="70%" valign="top">' . $file['title']  . '</td>
  </tr>';

  // Allow editing of file's actual "Filename" (could've been renamed by FTP)
  echo '
  <tr>
    <td class="tdrow2" width="30%"><strong>Filename:</strong><br />(for download)</td>
    <td class="tdrow3" width="70%" valign="top">
      <input type="text" name="filename" size="80" value="' . $file['filename'] . '" /></td>
  </tr>';

  if(!empty($file['storedfilename']))
  {
    echo '
    <tr>
      <td class="tdrow2" width="30%"><strong>Stored Filename:</strong><br />
        (physical filename in filesystem)</td>
      <td class="tdrow3" width="70%" valign="top">
        <input type="text" name="storedfilename" size="80" value="' . $file['storedfilename'] . '" />
      </td>
    </tr>';
  }
  echo '
  <tr>
      <td class="tdrow2" width="30%"><strong>File Size:</strong></td>
      <td class="tdrow3" width="70%" valign="top">
      <input type="text" name="filesize" value="'.
      (!empty($file['filesize']) ? $file['filesize'] : 0).'" />';
  if(!$fileislink)
  {
    echo DownloadManagerTools::dlm_DisplayReadableFileSize($file['filesize']) . '&nbsp;';
  }
  echo '</td></tr>
  <tr>
    <td class="tdrow2" width="30%"><strong>Storage Location:</strong></td>
    <td class="tdrow3" width="70%" valign="top">' .
    ((empty($file['filesize']) || $fileislink) ? 'None (external link)' : '
      <select name="filestore" >
        <option value="0" ' . (($file['filetype'] <> 'FTP') && (strlen($file['storedfilename']) == 0) ? 'selected="SELECTED"' : '') . ' />DB</option>
        <option value="1" ' . (($file['filetype'] == 'FTP') || (strlen($file['storedfilename']) >  0) ? 'selected="SELECTED"' : '') . ' />Filesystem</option>
      </select>') .
    '</td>
  </tr>
  <tr>
    <td class="tdrow2" width="30%"><strong>Version:</strong></td>
    <td class="tdrow3" width="70%" valign="top">
      <input type="text" name="version" value="'.htmlspecialchars($file['version']).'" />
    </td>
  </tr>' .

  // Added "datecreated" form field processing
  '<tr>
    <td class="tdrow2" width="30%"><strong>Version Added:</strong></td>
    <td class="tdrow3" width="70%" valign="top">
      <input type="text" name="datecreated" size="50" value="'.DisplayDate($file['datecreated']).'" />
    </td>
  </tr>
  <tr>
    <td class="tdrow1" bgcolor="#FCFCFC" colspan="2" align="center">
      <input type="submit" value="Update File Version" />
    </td>
  </tr>
  </table>
  </form>';

  EndSection();

} //DisplayFileVersionEditForm


// ############################### PRINT DEFAULT ###############################

function DisplayDefault()
{
  global $DB, $dlm_currentdir;

  StartSection('Instant Upload');
  echo '<table class="table table-bordered">
  <tr>
  <td>';

  // Create an uploaded object to integrate "plupload" by Moxiecode:
  @require_once(ROOT_PATH.'includes/class_sd_uploader.php');

  $bottom_js = '';
  if(class_exists('SD_Uploader'))
  {
    $uploader_config = array(
      'html_id'            => 'file_'.$this->PLUGINID,
      'html_upload_remove' => 'dummy',
      'singleUpload'       => 'true',
      'afterUpload'        => 'processInstantFile',
      'title'              => '<strong>Select one or more files for upload</strong>',
	  'layout'				=> 'col-sm-6',
	  'border'				=> false,
    );
    $this->uploader = new SD_Uploader($this->PLUGINID, 0, $uploader_config);
    $this->uploader->message_success = 'Upload done.';
    echo '
		<div class="col-sm-6">
			<div class="col-sm-12">
        		For easy, instant uploads, please select the target section, <strong>select</strong> one or multiple files and just click the <strong>upload</strong> button:<br />Section:';
    			DownloadManagerTools::dlm_PrintSectionSelection(
				  array('formId'         => 'instant_sectionid',
						'showmultiple'   => false,
						'displayOffline' => false,
						'extraStyle'     => ''
						));
    echo '
		</div>
		<br />
        <div class="col-sm-12">
          Available target folders related to Sections:<br />
          Folders: <select id="instant_fsprefix" name="instant_fsprefix" class="form-control" >
          <option value="" selected="selected" ></option>
          ';

    // Check for filesystem-prefixes of sections
    if($getsections = $DB->query('SELECT fsprefix FROM {p'.$this->PLUGINID.'_sections} WHERE LENGTH(fsprefix) > 0'))
    {
      while($sec = $DB->fetch_array($getsections,null,MYSQL_ASSOC))
      {
        echo '<option value="'.$sec['fsprefix'].'">'.$sec['fsprefix'].'</option>';
      }
      $DB->free_result($getsections);
    }

    echo '
          </select>
        </div>
      </div>
	    
        '. $this->uploader->getHTML().'
     </td>
	 </tr>
	 </table>';
    $bottom_js = $this->uploader->getJS();
    #<div id="uploader_file_'.$this->PLUGINID.'_messages"></div>
  }

  echo '
    <table class="table table-bordered">
	<thead>
    <tr>
      <th class="table-header">Add/Embed File by URL</th>
      <th  class="table-header">Add File by Upload</th>
      <th  class="table-header">View Section Files</th>
    </tr>
	</thead>
	<tbody>
    <tr>
      <td class="tdrow2" style="padding-left: 5px; vertical-align: top; width: 250px">
        <form method="post" action="'.REFRESH_PAGE.'" style="vertical-align: bottom">
        '.PrintSecureToken().'
        <input type="hidden" name="action" value="displayfileform" />
        <input type="hidden" name="load_wysiwyg" value="0" />
        <input type="hidden" name="ftpfile" value="1" />
        <div class="center"><button class="btn btn-primary btn-lg btn-block" type="submit" value=""><i class="ace-icon fa fa-link bigger-150"></i> Add File by URL</button></div>
        </form>
        <span class="dlm-admin-hint">Used to specify the path of a single file, that has already been
        uploadeded to a file storage folder on the server by FTP
        <i>OR</i> a link pointing to a remote (media site) file (with "http://").<br />
        <strong>Note:</strong> If not specified otherwise in the settings, the default
        storage folder is: "<strong>'.DownloadManagerTools::GetVar('PATH').'ftpfiles</strong>"</span>
      </td>
      <td class="tdrow2" style="padding-left: 5px; vertical-align: top; width: 250px">
        <form method="post" action="'.REFRESH_PAGE.'" style="vertical-align: bottom">
        '.PrintSecureToken().'
        <input type="hidden" name="action" value="displayfileform" />
        <input type="hidden" name="load_wysiwyg" value="0" />
        <button class="btn btn-primary btn-lg btn-block" type="submit" value="Upload new File"><i class="ace-icon fa fa-upload bigger-150"></i> Upload new File</button>
        </form>
        <span class="dlm-admin-hint">Upload a new file from your local machine into the
        database or a specific folder on the webserver.<br />
        Note: each section may point to a separate upload folder which may then
        be used when uploading a file.</span>
      </td>
      <td class="tdrow2" style="padding-left: 5px; vertical-align: top; width: 250px">
        <form method="post" action="'.REFRESH_PAGE.'">
        '.PrintSecureToken().'
        <button class="btn btn-primary btn-lg btn-block" type="submit" value="View Files"><i class="ace-icon fa fa-search bigger-150"></i> View Files</button>
        ';

  DownloadManagerTools::dlm_PrintSectionSelection(
    array('formId'            => 'sectionid',
          'selectedSectionId' => null,
          'displayOffline'    => true,
          'extraStyle'        => 'style="margin-right: 4px; width: 95%;" '
          ));

  echo '<br />Select a single section and click the button
        to view it\'s file listing.
        <input type="hidden" name="action" value="displayfiles" />
        <input type="hidden" name="load_wysiwyg" value="0" />
        </form>
      </td>
    </tr>
  </table>';
  EndSection();

  // Sections - add/manage
  PrintSection('Sections');
  echo '
  <table class="table table-bordered">
  <thead>
  <tr>
    <th class="tdrow1">Add Section</th>
    <th class="tdrow1">Edit Section</th>
  </tr>
  </thead>
  <tr>
    <td class="tdrow2" align="center" width="50%">
      <form method="post" action="'.REFRESH_PAGE.'" style="margin: 10px auto;">
      '.PrintSecureToken().'
      <input type="hidden" name="action" value="displaysectionform" />
      <input type="hidden" name="load_wysiwyg" value="0" />
      <button class="btn btn-primary btn-lg btn-block" type="submit" value="New Section"><i class="ace-icon fa fa-folder-open bigger-150"></i> New Section</i></button>
      <span class="dlm-admin-hint">Files can be organised in as many sections as needed
      within a hierarchical structure, just like directories.
      There always has to be a "top" section, though, that cannot - and must never - be deleted!<br />
      For security each section may have it\'s own permissions for
      either one or multiple usergroups or users.</span>
      </form>
    </td>
    <td class="tdrow2" align="center" width="50%">
      <form method="post" action="'.REFRESH_PAGE.'" style="margin: 10px auto">
      '.PrintSecureToken().'
      <span class="dlm-admin-hint">Select a single section and click
      the button to edit it\'s properties.</span>
      <div style="display: inline; float: left; padding: 4px">
      ';

  DownloadManagerTools::dlm_PrintSectionSelection(
    array('formId'            => 'sectionid',
          'selectedSectionId' => 1,
          'displayCounts'     => false,
          'size'              => 7,
          'extraStyle'        => 'style="min-width: 200px;"'
          ));

  echo '</div>
      <div style="display: inline; float: left; padding-top: 6px; margin-left: 8px;">
      <input type="hidden" name="action" value="displaysectionform" />
      <input type="hidden" name="load_wysiwyg" value="0" />
      <button class="btn btn-primary btn-lg btn-block" type="submit" value="Edit Section"><i class="ace-icon fa fa-edit bigger-150"></i> Edit Section</button>
      </div>
      </form>
    </td>
  </tr>
  </table>';
  EndSection();

  $this->DisplayFiles('Latest Files');

  // Display list with most recent downloads (configurable in Settings)
  $this->DisplayLatestDownloads();

  if($bottom_js)
  {
    echo '
  <script type="text/javascript">
  //<![CDATA[
  function processInstantFile(file_id, file_name, file_title) {
    var up_section = [];
    up_section.push($("#instant_sectionid").val());
    jQuery("#uploader_file_'.$this->PLUGINID.'_messages").load("'.ROOT_PATH.'plugins/'.$dlm_currentdir.'/instant.php #dlm_instant_response", {
      "instant_id": file_id,
      "file": file_name,
      "title" : file_title,
      "fsprefix" : $("#instant_fsprefix").val(),
      "sectionids" : up_section,
      "'.SD_TOKEN_NAME.'": "'.SD_FORM_TOKEN.'"
    },
    function(response, status, xhr) {
      if(status == "error") {
        var msg = "<strong>Sorry but there was an error:</strong> ";
        $("#uploader_file_'.$this->PLUGINID.'_messages").html(msg + xhr.status + " " + xhr.statusText);
      }
      else {
        var filecount = jQuery("#filelist_file_'.$this->PLUGINID.' div");
        if(filecount.length == 0) {
          jQuery("#files_list").load("'.ROOT_PATH.'plugins/'.$dlm_currentdir.'/instant.php #dlm_instant_response",{
            "action": "latestfiles",
            "'.SD_TOKEN_NAME.'": "'.SD_FORM_TOKEN.'"
          });
        }
      }}
    );
  }

  jQuery(document).ready(function() {
    '. $bottom_js.'
  });
  //]]>
  </script>
  ';
  }

} //DisplayDefault


// ############################### USER ACCESS #################################

function PrintUserGroupSelection($formId, $selectedGroupid, $linkedSelect=null)
{
  global $DB;

  echo '<select name="'.$formId.'" id="'.str_replace('[]','',$formId).'" size="10" multiple="multiple" style="min-width:200px;width:200px;padding:2px;">';

  if($getgroups = $DB->query('SELECT usergroupid, name FROM {usergroups} ORDER BY name'))
  {
    $selectedGroupid = empty($selectedGroupid) ? array() : (is_array($selectedGroupid) ? $selectedGroupid : array($selectedGroupid));
    while($group = $DB->fetch_array($getgroups,null,MYSQL_ASSOC))
    {
      echo "<option value=\"$group[usergroupid]\"".
      (is_array($selectedGroupid) && in_array($group['usergroupid'],$selectedGroupid)?' selected="selected"':'').">" .
      $group['name'] . "</option>
      ";
    }
    $DB->free_result($getgroups);
  }
  echo '
  </select>';

} //PrintUserGroupSelection


function PrintUserSelection($formId, $selectedUserid, $linkedSelect=null)
{
  global $DB, $usersystem, $dbname;

  $forumdbname = $usersystem['dbname'];
  $forumname   = $usersystem['name'];
  $tableprefix = $usersystem['tblprefix'];

  // switch to forum database
  if($DB->database != $forumdbname)
  {
    $DB->select_db($forumdbname);
  }

  if($forumname == 'Subdreamer')
  {
    $getusers = $DB->query('SELECT userid, username FROM {users} ORDER BY username');
  }
  else if( ($forumname == 'vBulletin 2') || ($forumname == 'vBulletin 3')  || ($forumname == 'vBulletin 4') )
  {
    $getusers = $DB->query('SELECT userid, username FROM '.$tableprefix.'user ORDER BY username');
  }
  else if($forumname == 'phpBB2' || $forumname == 'phpBB3')
  {
    $getusers = $DB->query('SELECT user_id userid, username FROM '.$tableprefix."users WHERE username != 'Anonymous' ORDER BY username");
  }
  else if($forumname == 'Invision Power Board 2')
  {
    $getusers = $DB->query('SELECT id userid, name username FROM ' . $tableprefix . 'members ORDER BY name');
  }
  else if($forumname == 'Invision Power Board 3') //SD341: fix: IPB3 has "member_id", not "id"
  {
    $getusers = $DB->query('SELECT member_id userid, name username FROM ' . $tableprefix . 'members ORDER BY name');
  }
  else if($forumname == 'Simple Machines Forum 1')
  {
    $getusers = $DB->query('SELECT ID_MEMBER userid, memberName username FROM ' . $tableprefix . 'members ORDER BY memberName');
  }
  else if($forumname == 'Simple Machines Forum 2')
  {
    $getusers = $DB->query('SELECT id_member userid, member_name username FROM ' . $tableprefix . 'members ORDER BY member_name');
  }
  else if($forumname == 'XenForo 1')
  {
    $getusers = $DB->query('SELECT user_id, username FROM ' . $tableprefix . 'user ORDER BY username'); //SD341: fix: "user", not "users"
  }
  else if($forumname == 'MyBB') //SD370
  {
    $getusers = $DB->query('SELECT uid userid, username FROM ' . $tableprefix . 'users ORDER BY username');
  }
  else if($forumname == 'punBB') //SD370
  {
    $getusers = $DB->query('SELECT id userid, username FROM ' . $tableprefix . 'users ORDER BY username');
  }

  echo '<ul id="'.str_replace('[]','',$formId).'">';

  $selectedUserid = empty($selectedUserid) ? array() : (is_array($selectedUserid) ? $selectedUserid : array($selectedUserid));
  if(!empty($getusers))
  {
    while($user = $DB->fetch_array($getusers,null,MYSQL_ASSOC))
    {
      if(is_array($selectedUserid) && in_array($user['userid'], $selectedUserid))
      {
        echo '
        <li id="user_'.(int)$user['userid'].'"><div>'.$user['username'].'</div>
        <input type="hidden" name="file_users[]" value="'.$user['userid'].'" />
        <img alt="[-]" id="deluser" rel="'.$user['userid'].'" src="'.ADMIN_IMAGES_FOLDER.'icons/eraser.png" height="16" width="16" />
        </li>';
      }
    }
    $DB->free_result($getusers);
  }
  echo '</ul>';

  // switch back to subdreamer database
  if($dbname != $forumdbname)
  {
    $DB->select_db($dbname);
  }

} //PrintUserSelection


// ############################## DISPLAY FILES ###############################

function DisplayFiles($viewtype,$allowswitch=true)
{
  global $DB, $plugin, $dlm_currentdir, $sdlanguage, $userinfo;
  


  if($viewtype==='Latest Files') // 15 most recent submissions
  {
    $sectionid = 0;
    $getfiles = $DB->query('SELECT f.*, fv.version, fv.filename
      FROM {p'.$this->PLUGINID.'_files} f
      LEFT JOIN {p'.$this->PLUGINID.'_file_versions} fv ON fv.fileid = f.fileid AND fv.versionid = f.currentversionid
      ORDER BY IF (f.dateupdated = 0, f.dateadded, f.dateupdated) DESC LIMIT 0,10');
  }
  else
  if(($viewtype===-1) || ($viewtype==='Offline Files')) // offline = (not activated, needs admin's review)
  {
    $sectionid = -1;
    $getfiles = $DB->query('SELECT f.*, fv.version, fv.filename
      FROM {p'.$this->PLUGINID.'_files} f
      LEFT JOIN {p'.$this->PLUGINID.'_file_versions} fv ON fv.fileid = f.fileid AND fv.versionid = f.currentversionid
      WHERE activated = 0
      ORDER BY IF (f.dateupdated = 0, f.dateadded, f.dateupdated) DESC');
  }
  else
  {
    $this->sectionid = (int)$viewtype;
    $getfiles = $DB->query('SELECT f.*, fv.version, fv.filename
      FROM {p'.$this->PLUGINID.'_files} f
      LEFT JOIN {p'.$this->PLUGINID.'_file_versions} fv ON fv.fileid = f.fileid AND fv.versionid = f.currentversionid
      INNER JOIN {p'.$this->PLUGINID.'_file_sections} sf ON sf.fileid = f.fileid AND sf.sectionid = %d
      WHERE activated <> 0
      ORDER BY IF (f.dateupdated = 0, f.dateadded, f.dateupdated) DESC', $this->sectionid);
    $viewtype = 'Files';
  }

  if($allowswitch && !Is_Ajax_Request())
  {
    echo '
	<div class="alert alert-info">
    <form id="files_list_switch" method="post" action="'.REFRESH_PAGE.'" " class="form-horizontal">
    '.PrintSecureToken().'
    <input type="hidden" name="action" value="displayfiles" />
	<label class="control-label col-sm-1  align-left no-padding-left">
    <strong>'.AdminPhrase('dlm_files_in_section').'</stron></label>
	<div class="col-sm-6"> ';

    DownloadManagerTools::dlm_PrintSectionSelection(
      array('formName'          => 'sectionid',
            'formId'            => 'files_sectionid',
            'selectedSectionId' => $this->sectionid,
            'displayOffline'    => true,
            'showmultiple'      => false,
            'extraStyle'        => 'style="width: 300px;"'
            ));

    echo '</div>&nbsp;&nbsp;
    <img id="files_loader" style="display: none; margin-top: 4px;" src="'.SD_INCLUDE_PATH.'css/images/loader.gif" width="23" height="23" />
    </form>
	</div>
    <div style="clear:both; margin: 4px;"></div>';
  }

  echo '
  <div id="files_list" style="clear: both;">
  ';
  PrintSection($viewtype);

  echo '
    <form id="updatefiles" name="updatefiles" method="post" action="'.REFRESH_PAGE.'">
    '.PrintSecureToken().'
    <input type="hidden" name="sectionid" value="'.$this->sectionid.'" />
    ';
  $table = '
    <table class="table table-bordered table-striped" summary="'.$viewtype.'" width="100%">
	<thead>
    <tr>
      <th class="tdrow1" width="25">ID</th>
      <th class="tdrow1" width="200">'.AdminPhrase('dlm_head_file').'</th>
      <th class="tdrow1" width="60">'.AdminPhrase('dlm_head_version').'</th>
      <th class="tdrow1" width="100" align="center">'.(DownloadManagerTools::GetSetting('latest_files_thumbnails')?AdminPhrase('dlm_head_thumbnail'):AdminPhrase('dlm_head_thumbnail_short')).'</th>
      <th class="tdrow1" width="70">'.AdminPhrase('dlm_head_sections').'</th>
      <th class="tdrow1" width="70">'.AdminPhrase('dlm_head_author').'</th>
      <th class="tdrow1" width="90">'.AdminPhrase('dlm_head_updated').'</th>
      <th class="tdrow1" width="90">'.AdminPhrase('dlm_head_added').'</th>
      <th class="tdrow1" width="80">'.AdminPhrase('dlm_head_downloaded_short').'</th>
      <th class="tdrow1" width="90" style="width: 90px;">'.AdminPhrase('dlm_head_status').'</th>
      <th class="tdrow1" align="center" width="70" style="width: 70px;">
        <input type="checkbox" checkall="deletefile" value="0" '.
        ' onclick="javascript: return select_deselectAll(\'updatefiles\',this,\'deletefile\');" /> '.AdminPhrase('dlm_head_delete').'
      </th>
    </tr>
	</thead>
	<tbody>';

  $filecount = 0;
  while($file = $DB->fetch_array($getfiles,null,MYSQL_ASSOC))
  {
    if(!$filecount) echo $table;
    $filecount++;
    if(!isset($file['title']) || !strlen($file['title']))
    {
      $file['title'] = DownloadManagerTools::GetPhrase('untitled');
    }
    echo '
    <tr>
      <td class="tdrow2" width="25">'.$file['fileid'].'</td>
      <td class="tdrow2" width="200">
        <a title="'.htmlspecialchars(AdminPhrase('dlm_edit_file'),ENT_QUOTES).
        '" href="'.REFRESH_PAGE.'&amp;action=displayfileform&amp;fileid='.$file['fileid'].'">'.$file['title'].'</a>'.
        (isset($file['filename']) && ($file['title']!=$file['filename']) && strlen(DownloadManagerTools::dlm_HasFileImageExt($file['filename']))
        ?'<br />'.$file['filename']:'').'
      </td>
      <td class="tdrow2" width="60">'.$file['version'].'&nbsp;</td>
      <td class="tdrow2" width="100" align="center">';
    $thumb = (DownloadManagerTools::GetSetting('latest_files_thumbnails') == 1) ?
               (strlen($file['thumbnail']) ?
                DownloadManagerTools::dlm_GetFileImageAsThumb($file,'thumbnail',100,100) :
                '&nbsp;') : $sdlanguage['yes'];
    echo $thumb . '
      </td>
      <td class="tdrow2" width="70">'. $this->DisplayFileSectionsLinks($file['fileid']) . '</td>
      <td class="tdrow2" width="70">'. (strlen($file['author'])  ? $file['author'] : '&nbsp;') . '</td>
      <td class="tdrow2" width="90">'. ($file['dateupdated'] > 0 ? DisplayDate($file['dateupdated']) : '&nbsp;') . '</td>
      <td class="tdrow2" width="90">'. ($file['dateadded'] > 0   ? DisplayDate($file['dateadded'])   : '&nbsp;') . '</td>
      <td class="tdrow2" width="80"><a title="'.htmlspecialchars(AdminPhrase('dlm_view_downloads'),ENT_QUOTES).
        '" href="'.REFRESH_PAGE.'&amp;action=displayfiledownloads&amp;fileid='.$file['fileid'].'">'.$file['downloadcount'].' D/L</a>
      </td>
      <td class="tdrow2" align="center" width="90" style="width: 90px; white-space: nowrap;"><span style="background-color:' .
        ($file['activated']?'green':'red').'">&nbsp;&nbsp;</span>'.
        '<input type="hidden" name="onlinestatus_old['.$file['fileid'].']" value="'.($file['activated']?1:0).'" />
        <select name="onlinestatus['.$file['fileid'].']"  style="width: 85px;">
          <option value="1" ' . ( $file['activated']?'selected="selected"':'') . '>'.AdminPhrase('dlm_online').'</option>
          <option value="0" ' . (!$file['activated']?'selected="selected"':'') . '>'.AdminPhrase('dlm_offline').'</option>
        </select>
      </td>
      <td class="tdrow2" align="center" width="70" style="width: 70px; max-width: 70px;">
        <input type="checkbox" class="ace" name="filedelids['.$file['fileid'].']" checkme="deletefile" value="'.$file['fileid'].'" /><span class="lbl"></span>
      </td>
    </tr>';
  } //while

  if(!$filecount)
  {
    echo '<div style="height: 30px; text-align: center; font-size: 15px; padding: 4px;"><strong>No Files</strong></div>';
  }
  else
  {
    echo '
      <tr>
        <td class="tdrow1" colspan="9">&nbsp;</td>
        <td class="tdrow1" align="center" style="padding-right: 12px;">
          <button class="btn btn-info btn-sm" id="files_list_update" type="submit" name="action" value="Update"><i class="ace-icon fa fa-check bigger-120"></i> Update</button>
        </td>
        <td class="tdrow1" align="center" style="padding-right: 12px;">
          <button class="btn btn-danger btn-sm" id="files_list_delete" type="submit" name="action" value="Delete" /><i class="ace-icon fa fa-trash-o bigger-120"></i> Delete</button>
        </td>
      </tr>
      </table>
      ';
  }
  echo '</form>';
  EndSection();
  echo '
  </div>
  ';

  if(!Is_Ajax_Request())
  echo '
<script language="javascript" type="text/javascript">
//<![CDATA[
if (typeof(jQuery) !== "undefined") {
jQuery(document).ready(function() {
  jQuery("#files_list_switch select").change(function() {
    var token = jQuery("input[name='.SD_TOKEN_NAME.']").val();
    jQuery("#files_loader").show();
    jQuery("#files_list").load("'.ROOT_PATH.'plugins/'.$dlm_currentdir.'/instant.php #dlm_instant_response",{
      "action": "latestfiles",
      "sectionid": jQuery(this).val(),
      "'.SD_TOKEN_NAME.'": token
    }, function(){
      jQuery("#files_loader").hide();
    });
  });
  jQuery(document).delegate("#files_list_delete","click", function() {
    return ConfirmAction("'.AdminPhrase('dlm_prompt_del_files').'");
  });
  jQuery(document).delegate("#files_list_update","click", function() {
    return ConfirmAction("'.AdminPhrase('dlm_prompt_upd_files').'");
  });
})
}
//]]>
</script>
';

} //DisplayFiles


// ########################### DISPLAY FILE DOWNLOADS ############################

function DisplayFileDownloads($fileid)
{
  global $DB;

  $title  = $DB->query_first('SELECT title FROM {p'.$this->PLUGINID.'_files} WHERE fileid = %d',$fileid);

  PrintSection(AdminPhrase('dlm_downloads_for')." '" . $title[0] . "'");

  echo '
    <table class="table table-bordered table-striped">
	<thead>
    <tr>
      <th class="tdrow1">'.AdminPhrase('dlm_user_name').'</th>
      <th class="tdrow1">'.AdminPhrase('dlm_ip_address').'</th>
      <th class="tdrow1">'.AdminPhrase('dlm_download_date').'</th>
    </tr>
	</tead>
	<tbody>
    ';

  // CONDEV 20060804 made entries hyperlinked
  if($getdownloads = $DB->query('SELECT * FROM {p'.$this->PLUGINID.'_file_downloads}
     WHERE fileid = %d ORDER BY downloaddate DESC',$fileid))
  {
    while($download = $DB->fetch_array($getdownloads,null,MYSQL_ASSOC))
    {
      echo '<tr>
      <td class="tdrow2"><a href="'.REFRESH_PAGE.'&amp;action=displayuserdownloads&amp;username='.$download['username'].'">'.
        $download['username'].'&nbsp;</a></td>
      <td class="tdrow2"><a href="'.REFRESH_PAGE.'&amp;action=displayipdownloads&amp;ipaddress='.$download['ipaddress'].'">'.
        $download['ipaddress'].'&nbsp;</a></td>
      <td class="tdrow2">'.DisplayDate($download['downloaddate'],'Y-m-d h:i:s').'&nbsp;</td>
      </tr>';
    }
    $DB->free_result($getdownloads);
  }

  echo '
  </table>
  </form>';

  EndSection();

} //DisplayFileDownloads


// ############################# DISPLAY SETTINGS #############################

function DisplaySettings($settingspage=1)
{
  global $DB, $mainsettings;

  $settings_arr = array();
  if(empty($settingspage) or ($settingspage == 1)) $settings_arr[] = 'Admin Options';
  if(empty($settingspage) or ($settingspage == 2)) $settings_arr[] = 'Download Options';
  if(empty($settingspage) or ($settingspage == 3)) $settings_arr[] = 'Upload Options';
  if(empty($settingspage) or ($settingspage == 4)) $settings_arr[] = 'Frontpage Options';
  if(empty($settingspage) or ($settingspage == 5)) $settings_arr[] = 'File Rating Options';
  PrintPluginSettings($this->PLUGINID, $settings_arr, REFRESH_PAGE);

} //DisplaySettings

function Show()
{
  global $DB;

  // ###########################################################################
  // ########################### MAIN PROCESSING ###############################
  // ###########################################################################

  DownloadManagerTools::DownloadManagerToolsInit();

  $this->action    = GetVar('action',    '',   'string');
  $this->sectionid = GetVar('sectionid', null, 'int');
  if($this->sectionid < -1) $this->sectionid = 0;
  $this->fileid    = GetVar('fileid',    null, 'whole_number');
  $this->imagename = GetVar('imagename', '',   'string');
  $this->versionid = GetVar('versionid', null, 'whole_number');
  $this->username  = GetVar('username',  '',   'string');
  $this->ipaddress = GetVar('ipaddress', '',   'string');
  $this->settingspage = GetVar('settingspage', null, 'whole_number');
  
 

  // ###########################################################################
  // ############################### DLM MENU ##################################
  // ###########################################################################

  $wysiwyg_suffix = '';#(!empty($mainsettings['enablewysiwyg']) ? '&amp;load_wysiwyg=1' : '');

  echo '
    <ul id="submenu" class="sf-menu">
    	<li><a href="' . REFRESH_PAGE . '">Download Manager</a></li>
    	<li><a href="#" onclick="return false;">'.AdminPhrase('dlm_menu_sections').'</a>
      		<ul>
        		<li><a href="' . REFRESH_PAGE . '&amp;action=displaysectionform'.$wysiwyg_suffix.'">'.AdminPhrase('dlm_menu_add_section').'</a></li>
        		<li><a href="#" onclick="return false;">'.AdminPhrase('dlm_menu_edit_section').'</a>
        ';

  DownloadManagerTools::dlm_PrintSectionSelection(
    array('formId'            => '',
          'displayCounts'     => false,
          'showmultiple'      => false,
          'asMenu'            => true,
          'menuAction'        => ('&amp;action=displaysectionform'.$wysiwyg_suffix)
          ));

        echo '</li>
        		<li><a href="#" onclick="return false;">'.AdminPhrase('dlm_menu_list_files_for').'</a>
        ';

  DownloadManagerTools::dlm_PrintSectionSelection(
    array('formId'            => '',
          'displayCounts'     => false,
          'showmultiple'      => false,
          'asMenu'            => true,
          'menuAction'        => ('&amp;action=displayfiles'.$wysiwyg_suffix)
          ));

  echo '</li>
      </ul>
    </li>
    <li><a href="' . REFRESH_PAGE . '&amp;action=displayfiles'.(empty($this->sectionid)?'':'&amp;sectionid='.$this->sectionid).'">'.AdminPhrase('dlm_menu_files').'</a>
      <ul>
      	<li><a href="' . REFRESH_PAGE . '&amp;action=displayfileform'.$wysiwyg_suffix.(empty($this->sectionid)?'':'&amp;sectionid='.$this->sectionid).'">'.AdminPhrase('dlm_menu_add_file_by_upload').'</a></li>
      	<li><a href="' . REFRESH_PAGE . '&amp;action=displayfileform'.$wysiwyg_suffix.(empty($this->sectionid)?'':'&amp;sectionid='.$this->sectionid).'&amp;ftpfile=1">'.AdminPhrase('dlm_menu_add_file_by_url').'</a></li>
      	<li><a href="' . REFRESH_PAGE . '&amp;action=displaybatchimportform">'.AdminPhrase('dlm_menu_batch_import').'</a></li>
      	<li><a href="' . REFRESH_PAGE . '&amp;action=displaybatchuploadform">'.AdminPhrase('dlm_menu_batch_upload').'</a></li>
      </ul>
    </li>
    <li><a href="' . REFRESH_PAGE . '&amp;action=displaysettings'.$wysiwyg_suffix.'">'.AdminPhrase('dlm_menu_settings').'</a>
      <ul>
      <li><a href="' . REFRESH_PAGE . '&amp;action=displaysettings&amp;settingspage=1'.$wysiwyg_suffix.'">'.AdminPhrase('dlm_settings_admin_options').'</a></li>
      <li><a href="' . REFRESH_PAGE . '&amp;action=displaysettings&amp;settingspage=2'.$wysiwyg_suffix.'">'.AdminPhrase('dlm_settings_download_options').'</a></li>
      <li><a href="' . REFRESH_PAGE . '&amp;action=displaysettings&amp;settingspage=3'.$wysiwyg_suffix.'">'.AdminPhrase('dlm_settings_upload_options').'</a></li>
      <li><a href="' . REFRESH_PAGE . '&amp;action=displaysettings&amp;settingspage=4'.$wysiwyg_suffix.'">'.AdminPhrase('dlm_settings_frontpage_options').'</a></li>
      <li><a href="' . REFRESH_PAGE . '&amp;action=displaysettings&amp;settingspage=5'.$wysiwyg_suffix.'">'.AdminPhrase('dlm_settings_rating_options').'</a></li>
      </ul>
    </li>
    </ul>
  <div class="clearfix"></div>
  ';

  // ############################## SELECT FUNCTION ############################

  switch($this->action)
  {
    // Display of all downloads for a specific username.
    // Column headers are linked to view by IP or file.
    case 'displayuserdownloads':
    $this->DisplayUserDownloads($this->username);
    break;

    // Display of all downloads for a specific IP address.
    // Column headers are linked to view by username or file.
    case 'displayipdownloads':
    $this->DisplayIPDownloads($this->ipaddress);
    break;

    // Remove specified thumb/image from a given section (also deletes image file).
    // The image can either be a thumbnail or the main image, determined by the filename.
    case 'deletesectionthumb':
    $this->DeleteSectionImage($this->sectionid, true, $this->imagename);
    break;

    case 'deletesectionimage':
    $this->DeleteSectionImage($this->sectionid, false, $this->imagename);
    break;

    // Display a form to create thumbnail for either file or section image.
    // Supports max. width and height for scaling.
    case 'thumbnailform':
    $useftpdir = GetVar('useftpdir', 0, 'natural_number');
    $useimgext = GetVar('useimgext', '', 'string');
    DownloadManagerTools::dlm_DisplayThumbnailForm($this->fileid, $this->sectionid?$this->sectionid:'',
                                                   $this->imagename, $useftpdir, $useimgext);
    break;

    // Perform actual creation of thumbnail of an image (due to "thumbnailform").
    // Corresponding file or section entries are updated accordingly.
    case 'DoCreateThumbnail':
    DownloadManagerTools::dlm_DoCreateThumbnailByPost($this->fileid, $this->sectionid?$this->sectionid:'', $this->imagename);
    break;

    // Update all listed files with the posted online status.
    case 'Update':
    $this->UpdateFilesOnlineStatus();
    break;

    case 'deletefileimage':
    $this->DeleteFileImage($this->fileid, $this->imagename);
    break;

    case 'batchupload':
    $this->BatchUpload();
    break;

    case 'batchimport':
    $this->BatchImport();
    break;

    case 'insertfile':
    case 'updatefile':
    $this->UpdateFile($this->fileid);
    break;

    case 'Delete':
    $this->DeleteFiles();
    break;

    case 'deletefile':
    $this->DeleteFile($this->fileid, $this->versionid);
    break;

    case 'editversion':
    $this->DisplayFileVersionEditForm($this->fileid, $this->versionid);
    break;

    case 'updatefileversion':
    $this->UpdateFileVersion($this->fileid, $this->versionid);
    break;

    case 'insertsection':
    $this->InsertSection();
    break;

    case 'updatesection':
    $this->UpdateSection();
    break;

    case 'displayfileform':
    $this->DisplayFileForm($this->fileid);
    break;

    case 'displayfiledownloads':
    $this->DisplayFileDownloads($this->fileid);
    break;

    case 'displaybatchuploadform':
    $this->DisplayBatchUploadForm();
    break;

    case 'displaybatchimportform':
    $this->DisplayBatchImportForm();
    break;

    case 'displaysectionform':
    $this->DisplaySectionForm(empty($this->sectionid)?null:$this->sectionid);
    break;

    case 'displayfiles':
    $this->DisplayFiles($this->sectionid);
    break;

    case 'displaysettings':
    $this->DisplaySettings($this->settingspage);
    break;

    default:
    $this->DisplayDefault();
  }
  echo '
  </div>
  ';
  }
} //end of class

$DownloadManagerSettings = new DownloadManagerSettings;
if(!Is_Ajax_Request())
{
  $DownloadManagerSettings->Show();
  unset($DownloadManagerSettings);
}
