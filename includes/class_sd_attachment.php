<?php
if(!defined('IN_PRGM')) exit();
if(!class_exists('SD_Attachment'))
{
class SD_Attachment
{
  private $pluginid, $area, $objectid,
          $_userid, $_username,
          $_max_filesize,
          $valid_attachment_types = '*';
  private $storage_path = '';

  public static function getInstance($pluginid=0, $area='')
  {
    return new SD_Attachment($pluginid, $area);
  }

  /**
   * Create a new attachment object
   * @param    number   $pluginid   Unique pluginid; sets default storage path to "attachments".
   * @param    string   $area       Default area specification
   * @return   void
   */
  public function __construct($pluginid=0, $area='')
  {
    $this->setPluginID($pluginid);
    $this->setArea($area);
    $this->setStorageBasePath('attachments/');
    $this->_userid = 0;
    $this->_username = '';
    $this->_max_filesize = 0;
    $this->valid_attachment_types = '';
  }

  /**
   * Returns the current, relative to CMS, storage path ("./attachments" for frontpage)
   * @return   string
   */
  public function getStorageBasePath()
  {
    return $this->storage_path;
  }

  /**
   * Sets the storage folder relative to CMS root (e.g. "attachments") and tries
   * to create all intermediate folders and make them writable.
   * @param    string   $path   Path to storage folder, relative to CMS, no absolute path!
   */
  public function setStorageBasePath($path)
  {
    $this->storage_path = ROOT_PATH . $path;
    if(!empty($path) && strlen($path))
    {
      $this->_makePathWritable($this->storage_path);
      $this->storage_path .= (substr($this->storage_path,-1) != '/' ? '/' : '');
    }
  } //setStorageBasePath

  /**
   * Return the currently set "area", which is also used as a storage folder name.
   * @return   string
   */
  public function getArea()
  {
    return $this->area;
  }

  /**
   * Sets the current "area", which must be filesystem-compatible name as it is also used as a storage folder name.
   * All blanks are automatically converted to underlines.
   * @param    string   $area   Name of area
   */
  public function setArea($area='')
  {
    $this->area = (string)str_replace(array('%20',' '),array('_','_'), trim($area));
  } //setArea

  /**
   * Returns currently set object id, e.g. a form wizard response id or a forum post id.
   * @return   integer
   */
  public function getObjectID()
  {
    return (int)$this->objectid;
  }

  /**
   * Sets the current object id (integer), e.g. article or forum post id (can be 0).
   * @param    integer   $objectid   0+ integer identifying an object
   */
  public function setObjectID($objectid=0)
  {
    $objectid = !isset($objectid)?0:Is_Valid_Number($objectid,0,1,999999999);
    $this->objectid = (int)$objectid;
  } //setObjectID

  /**
   * Returns the current plugin id
   * @return   integer
   */
  public function getPluginID()
  {
    return (int)$this->pluginid;
  }

  /**
   * Sets the current plugin id (integer), e.g. 2 for Articles plugin (can be 0).
   * @param    integer   $pluginid   0 or a valid plugin id
   */
  public function setPluginID($pluginid=0)
  {
    $pluginid = !isset($pluginid)?0:Is_Valid_Number($pluginid,0,1,999999999);
    $this->pluginid = (int)$pluginid;
  } //setPluginID

  /**
   * Return the currently set user id, which is optional.
   * @return   integer
   */
  public function getUserID()
  {
    return (int)$this->_userid;
  }

  /**
   * Sets the current user id (integer), which is stored with following attachments.
   * If 0 then the current internal username is emptied.
   * @param    integer   $userid   0 or a valid user id
   */
  public function setUserID($userid=0)
  {
    $userid = !isset($userid)?0:Is_Valid_Number($userid,0,1,999999999);
    if(empty($userid))
    {
      $this->_username = '';
    }
    $this->_userid = (int)$userid;
  } //setUserID

  /**
   * Returns the current internal username.
   * @return   string
   */
  public function getUsername()
  {
    return $this->_username;
  }

  /**
   * Sets the current username to store with attachments.
   * @param    string   $username   Name of user (default: blank).
   */
  public function setUsername($username='')
  {
    if(!isset($username) || !is_string($username))
    {
      $username = '';
    }
    $this->_username = (string)$username;
  } //setUsername

  /**
   * Returns the currently set filesize limit in KB (or 0).
   * @return   integer
   */
  public function getMaxFilesizeKB()
  {
    return $this->_max_filesize;
  }

  /**
   * Sets the current filesize limit in KB.
   * @param    integer   $SizeInKB   0 or a valid plugin id
   */
  public function setMaxFilesizeKB($SizeInKB=0)
  {
    $SizeInKB = !isset($SizeInKB)?0:Is_Valid_Number($SizeInKB,0,1,999999999);
    $this->_max_filesize = (int)$SizeInKB;
  } //setMaxFilesizeKB

  /**
   * Returns the currently set types of allowed attachments (comma-separated list).
   * @return   string
   */
  public function getValidExtensions()
  {
    return $this->valid_attachment_types;
  }

  /**
   * Sets the list of allowed file extentions as comma-separated list or "*" for any.
   * @param    integer   $ext   Comma-separated list or "*" for any file (no blanks)
   */
  public function setValidExtensions($ext='*')
  {
    $this->valid_attachment_types = (empty($ext) || !is_string($ext)) ? '*' : strtolower(str_replace(' ', '', $ext));
  } //setValidExtensions

  /**
   * Private: create $path folder with all intermediate folders, sets folder writable
   * @param    string   $path   Path to target folder
   * @return   boolean
   */
  private function _makePathWritable($path)
  {
    if(empty($path)) return false;
    if(!is_dir($path))
    {
      return @mkdir($path, intval("0777", 8), true);
    }
    else
    {
      if(!is_writable($path))
      @chmod($path, intval("0777", 8));
    }
    return true;
  } //_makePathWritable


  // ##########################################################################
  // FETCH A SPECIFIC ATTACHMENT ROW
  // ##########################################################################

  /**
   * Returns attachments row for specified object and attachment ID and sets $this properties.
   * @param    integer   $objectid
   * @param    integer   $attachmentid
   * @return   array     false if no row found
   */
  public function FetchAttachmentEntry($objectid, $attachmentid=0)
  {
    global $DB;

    if(empty($this->pluginid) || empty($objectid) || empty($attachmentid))
    {
      return false;
    }
    $objectid = Is_Valid_Number($objectid, 0, 1, 9999999);
    $attachmentid = Is_Valid_Number($attachmentid, 0, 1, 9999999);

    $result = false;
    if(!empty($objectid) && !empty($attachmentid))
    {
      $DB->result_type = MYSQL_ASSOC;
      if($result = $DB->query_first(
                    'SELECT *'.
                    ' FROM {attachments}'.
                    ' WHERE objectid = %d AND attachment_id = %d',
                    $objectid, $attachmentid))
      {
        $this->_userid = (int)$result['userid'];
        $this->_username = (string)$result['username'];
        $this->area = !empty($result['area'])?(string)$result['area']:'';
        $this->objectid = (int)$result['objectid'];
      }
    }
    return $result;

  } //FetchAttachmentEntry


  // ##########################################################################
  // ADD 1 TO THE ATTACHMENT'S DOWNLOAD COUNTER
  // ##########################################################################

  /**
   * Increments attachments' download counter for specified object and attachment ID.
   * @param    integer   $objectid
   * @param    integer   $attachmentid
   * @return   bool      true if an attachments' counter was updated
   */
  public function AddDownloadCount($objectid, $attachmentid)
  {
    global $DB;

    if(empty($this->pluginid) || empty($objectid) || empty($attachmentid))
    {
      return false;
    }
    $objectid = Is_Valid_Number($objectid, 0, 1, 9999999);
    $attachmentid = Is_Valid_Number($attachmentid, 0, 1, 9999999);

    if(!empty($objectid) && !empty($attachmentid))
    {
      $DB->query('UPDATE {attachments}'.
                 ' SET download_count = IFNULL(download_count,0) + 1'.
                 ' WHERE pluginid = %d AND attachment_id = %d',
                 $this->pluginid, $attachmentid);
      return ($DB->affected_rows() > 0);
    }
    return false;

  } //AddDownloadCount


  // ##########################################################################
  // UPLOAD ATTACHMENT
  // ##########################################################################

  /**
   * Uploads a form $_FILE element in $attachment_arr and is successful, adds it as an attachment to current plugin/object.
   * Uploaded file permission is set to 0666; if image, it's checked to be valid for security reasons.
   * @param    array     $attachment_arr is a form's $_FILE entry
   * @return   array     array of result with "id", "filename" (physical storage name without path) and an optional "error" string
   */
  public function UploadAttachment($attachment_arr)
  {
    global $DB, $sdlanguage, $usersystem;

    $filename = '';
    $attachment_uploaded = false;
    $attachment_stored_filename = '';
    //SD343: don't allow dangerous names
    if(!empty($attachment_arr['name']) &&
       preg_match("#<script|<html|<head|<?php<title|<body|<pre|<table|<a\s+href|<img|<plaintext|<cross\-domain\-policy#si",
                  $attachment_arr['name']))
    {
      $attachment_arr['name'] = '';
    }
    $error = '';
    if(empty($this->_userid) || !is_numeric($this->_userid) || ($this->_userid<1) ||
       empty($this->_username) || !strlen($this->_username))
    {
      $error = 'invalid_user';
    }
    else
    if(($attachment_arr['error'] == 4) || empty($attachment_arr['size']) ||
       empty($attachment_arr['name']) || !is_uploaded_file($attachment_arr['tmp_name']))
    {
      $error = 'no_file_or_empty';
    }
    else
    if(!empty($attachment_arr['error']) && !empty($attachment_arr['error']))
    {
      if(in_array($attachment_arr['error'], array(1,2,3,6)))
        $error = $sdlanguage['upload_err_'.(int)$attachment_arr['error']];
      else
        $error = $sdlanguage['upload_err_general'];
    }
    else
    {
      $accept_all = false;
      $attachment_type_is_valid = false;
      if($this->valid_attachment_types=='*')
        $accept_all = true;
      else
      {
        $valid_attachment_types = strtolower(str_replace(' ', ',', $this->valid_attachment_types));
        $valid_attachment_types_arr = sd_ConvertStrToArray($valid_attachment_types,',');
      }

      $pos = strrpos($attachment_arr['name'], '.'); // search for the period in the filename
      if($pos !== false)
      {
        $attachment_extension = strtolower(substr($attachment_arr['name'], $pos + 1));
        $attachment_type_is_valid = $accept_all || in_array($attachment_extension,$valid_attachment_types_arr);
        #SD343: do not allow PHP extention anywhere in the filename!
        if(!$accept_all && !in_array('php',$valid_attachment_types_arr) &&
           preg_match('/\.php([2-6])?|\.phtml|\.html?|\.pl|\.cgi|\.sh|\.cmd/', $attachment_arr['name']))
        {
          $attachment_type_is_valid = false;
        }
      }
      //SD362: security check on "type"
      if($attachment_type_is_valid && !empty($attachment_arr['type']))
      {
        if(preg_match("#<script|<html|<head|<?php<title|<body|<pre|<table|<a\s+href|<img|<plaintext|<cross\-domain\-policy#si",$attachment_arr['type']) ||
           preg_match('/\.php([2-6])?|\.phtml|\.html?|\.pl|\.cgi|\.sh|\.cmd/', $attachment_arr['type']))
        {
          $attachment_type_is_valid = false;
        }
      }

      if(!$attachment_type_is_valid)
      {
        $error = $sdlanguage['upload_err_invalid_extension'];
      }
      else
      {
        // attachment is okay, try and move it over
        $file_path = $this->storage_path;
        $this->_makePathWritable($file_path);
        if(@is_writable($file_path))
        {
          $file_path .= (int)$this->pluginid.'/';
          $this->_makePathWritable($file_path);
          $file_path .= (strlen($this->area) ? $this->area.'/' : '');
          $this->_makePathWritable($file_path);
          while(true)
          {
            $filename = CreateGuid() . '.dat';
            if(!file_exists($file_path.$filename)) break;
          }
          $file_path .= $filename;
          if(@move_uploaded_file($attachment_arr['tmp_name'], $file_path))
          {
            @chmod($file_path, intval('0666', 8));
            if($filesize = @filesize($file_path))
            {
              //SD362: in case of images do a security check, delete on error!
              require_once(SD_INCLUDE_PATH.'class_sd_media.php');
              if(isset(SD_Media_Base::$known_type_ext[$attachment_arr['type']]))
              {
                if(!SD_Media_Base::ImageSecurityCheck(true, dirname($file_path).'/', $filename))
                {
                  $error = $sdlanguage['upload_err_general'];
                }
              }
              if(isset($file_path))
              {
                if(floor($filesize / 1024) <= $this->_max_filesize)
                {
                  $DB->query("INSERT INTO {attachments}(pluginid,objectid,area,usersystemid,userid,username,
                      attachment_name,filename,filesize,filetype,uploaded_date)
                      VALUES(%d,%d,'%s',%d,%d,'%s','%s','%s',%d,'%s',%d)",
                      $this->pluginid,  $this->objectid,
                      $DB->escape_string($this->area),
                      $usersystem['usersystemid'], $this->_userid,
                      $DB->escape_string($this->_username),
                      $DB->escape_string(strip_alltags($attachment_arr['name'])),
                      $filename, $filesize,
                      $DB->escape_string($attachment_arr['type']), TIME_NOW);
                  $attachment_uploaded = $DB->insert_id();
                }
                else
                {
                  $error = $sdlanguage['upload_err_size_limit'];
                }
              }
            }
            else
            {
              $error = $sdlanguage['upload_err_access_error'];
            }
            if($error && file_exists($file_path)) @unlink($file_path);
          }
        }
        if(!$attachment_uploaded && !$error)
        {
          $error = $sdlanguage['upload_err_general'];
        }
      }
    }

    return array('id'=>$attachment_uploaded, 'filename'=>$filename, 'error'=>$error);

  } //UploadAttachment


  // ##########################################################################
  // DELETE ATTACHMENT
  // ##########################################################################

  /**
   * Delete a specific attachment identified by $attachment_id.
   * @param    array   $attachment_id   unique ID of the attachment in DB
   * @return   bool    true if attachment was found and deleted, else false
   */
  public function DeleteAttachment($attachment_id=0)
  {
    global $DB;

    if(empty($attachment_id) && !is_numeric($attachment_id) || ($attachment_id < 0))
    {
      return false;
    }

    $DB->result_type = MYSQL_ASSOC;
    if($attachment_arr = $DB->query_first(
                         'SELECT * FROM {attachments}'.
                         ' WHERE attachment_id = %d',
                         $attachment_id))
    {
      $file_path  = $this->storage_path;
      $file_path .= (int)$attachment_arr['pluginid'].'/';
      $file_path .= (strlen($attachment_arr['area']) ? $attachment_arr['area'].'/' : '');
      $file_path .= $attachment_arr['filename'];
      if(file_exists($file_path))
      {
        @unlink($file_path);
      }
      $DB->query('DELETE FROM {attachments}'.
                 ' WHERE pluginid = %d'.
                 ' AND objectid = %d AND attachment_id = %d',
                 $this->pluginid, $this->objectid, $attachment_id);
      return true;
    }

    return false;

  } //DeleteAttachment


  // ##########################################################################
  // DELETE ALL ATTACHMENTS FOR A SPECIFIC OBJECT
  // ##########################################################################

  /**
   * Delete all attachments for current object and area.
   * @return   bool    true if attachments were found and deleted
   */
  public function DeleteAllObjectAttachments()
  {
    global $DB;

    // $this->objectid must not be invalid
    if(empty($this->objectid) || ($this->objectid < 1))
    {
      return false;
    }

    if(!$getrows = $DB->query('SELECT * FROM {attachments}'.
                              ' WHERE pluginid = %d'.
                              " AND objectid = %d AND IFNULL(area,'') = '%s'",
                              $this->pluginid, $this->objectid,
                              $DB->escape_string($this->area)))
    {
      return false;
    }

    // First delete all found physical files
    clearstatcache();
    $basepath = $this->storage_path;
    $olddog = $GLOBALS['sd_ignore_watchdog'];
    $GLOBALS['sd_ignore_watchdog'] = true;
    while($row = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
    {
      $file_path  = $basepath . (int)$row['pluginid'].'/';
      $file_path .= (strlen($row['area']) ? $row['area'].'/' : '');
      if(is_dir($file_path))
      {
        $file_path .= $row['filename'];
        if(@file_exists($file_path))
        {
          @unlink($file_path);
        }
      }
    } //while
    $GLOBALS['sd_ignore_watchdog'] = $olddog;

    // Finally, remove all attachment rows for object:
    $DB->query('DELETE FROM {attachments}'.
               ' WHERE pluginid = %d'.
               " AND objectid = %d AND IFNULL(area,'') = '%s'",
                $this->pluginid, $this->objectid,
                $DB->escape_string($this->area));

    return true;

  } //DeleteAllObjectAttachments


  // ##########################################################################
  // PROCESS PLUGIN ATTACHMENTS (CHECK PERMISSIONS OR DELETE)
  // ##########################################################################

  /**
   * Reset permissions OR delete a plugin's attachments folder recursively. Usable by e.g. uninstall steps of plugins.
   * @param    bool   $delete  If true, delete everything, else reset file/folder permissions (0666,0777)
   * @return   bool   true on success, false on failure
   */
  public function ProcessPluginAttachments($delete=false) //SD370
  {
    global $DB;

    $delete = !empty($delete);

    // First delete all found physical files
    clearstatcache();
    $olddog = $GLOBALS['sd_ignore_watchdog'];
    $GLOBALS['sd_ignore_watchdog'] = true;
    if(!empty($delete))
    {
      return sd_xdelete($this->storage_path.$this->pluginid.'/');
    }

    if(empty($this->pluginid) || ($this->pluginid < 1) || ($this->pluginid > 999999) ||
       (!$getrows = $DB->query('SELECT * FROM {attachments}'.
                               ' WHERE pluginid = %d', $this->pluginid)))
    {
      return false;
    }
    $base_path = $this->storage_path.(int)$row['pluginid'].'/';
    while($row = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
    {
      $file_path = $base_path . (strlen($row['area']) ? $row['area'].'/' : '');
      if(is_dir($file_path))
      {
        @chmod($file_path, intval('0777',8));
        $file_path .= $row['filename'];
        if(@file_exists($file_path))
        {
          @chmod($file_path, intval('0666',8));
        }
      }
    } //while
    $GLOBALS['sd_ignore_watchdog'] = $olddog;

    // Finally, remove all attachments for plugin:
    $DB->query('DELETE FROM {attachments}'.
               ' WHERE pluginid = %d',
                $this->pluginid);

    return true;

  } //DeletePluginAttachments


  // ##########################################################################
  // GET AN ARRAY-LIST OF ATTACHMENTS FOR CURRENTLY SET pluginid/area/objectid
  // ##########################################################################

  /**
   * Returns an array of all attachments for current plugin/object and optionally a specific attachment identified by $attachment_id.
   * Both params are optional.
   * @param    bool    $checkPermissions   currently not used!
   * @param    array   $attachment_id      unique ID of the attachment in DB (optional)
   * @return   array   array with all rows, else false if none were found; empty string for invalid plugin/object!
   */
  public function getAttachmentsArray($checkPermissions=true,$attachment_id=0)
  {
    //SD362: added $attachment_id parameter to get single attachment
    global $DB, $sdurl, $userinfo;

    if(!Is_Valid_Number($this->pluginid,0,1) || !Is_Valid_Number($this->objectid,0,1))
    {
      return '';
    }

    $result = false;
    if($get_attachments = $DB->query(
       'SELECT * FROM '.PRGM_TABLE_PREFIX.'attachments'.
       ' WHERE pluginid = %d AND objectid = %d'.
       " AND IFNULL(area,'') = '%s'".
       (!empty($attachment_id)&&is_numeric($attachment_id)?' AND attachment_id ='.(int)$attachment_id:'').
       ' ORDER BY attachment_name ASC',
       $this->pluginid, $this->objectid,
       $DB->escape_string($this->area)))
    {
      $result = $DB->fetch_array_all($get_attachments);
    }

    return $result;

  } //getAttachmentsArray


  // ##########################################################################
  // GET A LIST OF ATTACHMENTS FOR CURRENTLY SET pluginid/area/objectid
  // ##########################################################################

  /**
   * Returns a preformatted UL list (class="attachments") for all attachments for current plugin/object.
   * Both params are optional.
   * @param    bool    $doOutput           return or display (default) output?
   * @param    bool    $checkPermissions   if current user has no access to attachments only attachment name is returned (default: true)
   * @param    bool    $allow_delete       if true or current user is site admin an additional delete icon is included in output (default: false)
   * @param    array   $attachment_id      unique ID of a specific attachment in DB (optional; default 0)
   * @param    array   $showSizes          output attachment file sizes (default: true)
   * @return   string  if $doOutput is true, returns null, otherwise HTML string with full output
   */
  public function getAttachmentsListHTML($doOutput=true, $checkPermissions=true, $allow_delete=false,
                                         $attachment_id=0, $showSizes=true)
  {
    global $DB, $sdurl, $userinfo;

    if(!Is_Valid_Number($this->pluginid,0,1) || !Is_Valid_Number($this->objectid,0,1))
    {
      return '';
    }
    $attachment_id = isset($attachment_id)?Is_Valid_Number($attachment_id,0,1):0;

    $attachment_arr = $this->getAttachmentsArray($checkPermissions,$attachment_id);
    if(empty($attachment_arr))
    {
      return '';
    }

    $output = '<ul class="attachments list-unstyled">';

    $hasAccess = !empty($userinfo['adminaccess']) ||
                 (empty($checkPermissions) ||
                  ( (!empty($userinfo['pluginadminids']) &&
                     @in_array($this->pluginid, $userinfo['pluginadminids'])) ||
                    (!empty($userinfo['plugindownloadids']) &&
                     @in_array($this->pluginid, $userinfo['plugindownloadids']))));
    foreach($attachment_arr as $item)
    {
      if($hasAccess)
      {
        $output .= '
          <li><a class="" target="_blank" rel="nofollow" href="'.
            $sdurl.
            'includes/attachments.php?pid='.$this->pluginid.
            '&amp;objectid='.$item['objectid'].
            '&amp;id='.$item['attachment_id'].'">'.
            $item['attachment_name'].'</a>';

        if(!empty($userinfo['adminaccess']) || !empty($allow_delete) ||
           (empty($allow_delete) && !empty($userinfo['userid']) && ($userinfo['userid'] == $item['userid'])))
        {
          $output .= ' <a target="_blank" class="imglink imgdelete" id="aid'.$item['pluginid'].'_'.$item['attachment_id'].
          '" rel="nofollow" href="'.$sdurl.'includes/ajax/sd_ajax_attachments.php?'.
          'do=deleteattachment&amp;pid='.$item['pluginid'].
          '&amp;area='.urlencode($item['area']).
          '&amp;oid='.urlencode($item['objectid']).
          (defined('IN_ADMIN')?'&admin=1':''). //SD370
          '&amp;aid='.$item['attachment_id'].SD_URL_TOKEN.'"><i class="ace-icon fa fa-trash-o red bigger-110"></i></a>&nbsp;';
        }
        if(!empty($showSizes))
        {
          $output .= '('.DisplayReadableFilesize($item['filesize']).')';
        }
        $output .= '</li>';
      }
      else
      {
        $output .= '<li>'.$item['attachment_name'].'</li>';
      }
    }

    if(!$hasAccess)
    {
      global $sdlanguage;
      $output .= '<li>'.$sdlanguage['err_member_no_download'].'</li>';
    }
    $output .= "</ul>\r\n";

    if(empty($doOutput))
    {
      return $output;
    }

    echo $output;

  } //getAttachmentsListHTML

} // end of class SD_Attachment

} //do not remove