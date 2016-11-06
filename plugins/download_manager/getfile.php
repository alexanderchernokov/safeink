<?php
// +---------------------------------------------+
// | Download Manager plugin for Subdreamer      |
// +---------------------------------------------+
// | v2.2.0, September 2013                      |
// | Maintainer: 2007-2013 tobias                |
// | This version requires Subdreamer 3.6+!      |
// +---------------------------------------------+

// ############################################################################
// DEFINE CONSTANTS & INIT PROGRAM
// ############################################################################
if(!isset($_SERVER['HTTP_USER_AGENT'])) exit();

define('IN_PRGM', true);
define('ROOT_PATH', '../../');
@require(ROOT_PATH . 'includes/init.php');
$dlm_currentdir = sd_GetCurrentFolder(__FILE__);
if(!$pluginid = GetPluginIDbyFolder($dlm_currentdir)) return false;

// Check if required classes are available
if(!class_exists('DownloadManager'))
{
  // class DownloadManager (main frontpage class, object instance required)
  @require(ROOT_PATH . 'plugins/'.$dlm_currentdir.'/class_dlm.php');
}

// Check if class is ok
if(!class_exists('DownloadManagerTools')) return false;
DownloadManagerTools::DownloadManagerToolsInit($pluginid);

define('DLM_HTML_PREFIX', DownloadManagerTools::GetVar('HTML_PREFIX'));
define('DLM_PLUGINID',    DownloadManagerTools::GetVar('PLUGINID'));

/*
DEVELOPER NOTE: $_GET['in_admin'] information:
Error messages should be reported differently if script is called from
within admin panel, which sends the extra variable "in_admin" to let the
script know to redirect to admin area and not the homepage.
*/
$InAdmin = false;
if(!empty($_GET['in_admin']))
{
  if(!empty($userinfo['adminaccess']) || (!empty($userinfo['pluginadminids']) && in_array($pluginid, $userinfo['pluginadminids'])))
  {
    // These vars mean nothing to the admin panel, but must be set for the script to work:
    $_GET['categoryid'] = 1;
    $_GET[DLM_HTML_PREFIX.'_sectionid'] = 1;
    $InAdmin = true;
  }
  else
  {
    $DB->close();
    exit;
  }
}

// Fetch file storage location. At this point $filesdir has been
// checked for trailing slash and relative path.
$ftpdir = DownloadManagerTools::$filesdir;

// ####################### CHECK PARAMS FOR ERRORS ############################

$error = 0;
$categoryid = GetVar('categoryid', 0, 'whole_number', false, true);
$fileid     = GetVar(DLM_HTML_PREFIX.'_fileid',    0, 'whole_number', false, true);
$sectionid  = GetVar(DLM_HTML_PREFIX.'_sectionid', 0, 'whole_number', false, true);
$versionid  = GetVar(DLM_HTML_PREFIX.'_versionid', 0, 'natural_number', false, true);
$forced     = GetVar(DLM_HTML_PREFIX.'_forced',    0, 'whole_number', false, true);
$forced     = empty($forced) ?  (NotEmpty(DownloadManagerTools::GetSetting('force_download')) ? 1 : 0) : 1;

// Exit script if ANY required variable is not filled
if((empty($InAdmin) && empty($categoryid)) || empty($fileid) || empty($sectionid))
{
  // File offline, incorrect fileid or not in specified section
  $error = 'dne';
}

// ############################## ANTI LEECH ##################################

// Note: "HTTP_REFERER" may be inactive (either IE6 or for example because of
// custom settings in Firefox)
if(empty($error) && DownloadManagerTools::GetSetting('block_remote_downloads') &&
   isset($_SERVER['HTTP_REFERER']) && (!stristr($_SERVER['HTTP_REFERER'], $_SERVER['SERVER_NAME'])))
{
  // al = anti leech
  $error = 'al';
  exit;
}

// ##################### FIND FILE WITH VERSION IN SECTION ####################

// Check...
// a) if file exists in specified section
// b) permissions on both section and file
// c) if both section and file are activated
$ug  = (int)$userinfo['usergroupid'];
$uid = (int)$userinfo['userid'];
$sql = 'SELECT f.fileid, f.activated AS f_activated, f.access_groupids,'.
       ' f.access_userids, s.sectionid, s.activated, s.activated AS s_activated,'.
       ' fv.file, fv.storedfilename, fv.filename, fv.filesize, fv.filetype, fv.is_embeddable_media'.
       ' FROM {p'.DLM_PLUGINID.'_files} f'.
       ' INNER JOIN '.PRGM_TABLE_PREFIX.'p'.DLM_PLUGINID.'_file_sections fs ON fs.fileid = f.fileid'.
       ' INNER JOIN '.PRGM_TABLE_PREFIX.'p'.DLM_PLUGINID.'_sections s ON s.sectionid = fs.sectionid'.
       ' LEFT JOIN '.PRGM_TABLE_PREFIX.'p'.DLM_PLUGINID.'_file_versions fv ON fv.fileid = f.fileid AND fv.versionid = '.(int)$versionid.
       ' WHERE f.fileid = '.(int)$fileid.
       (empty($InAdmin)?' AND s.sectionid = '.(int)$sectionid:'').
       ' AND s.activated = 1 AND f.activated = 1'.
       ' AND ((IFNULL(s.access_groupids,"")="") OR (s.access_groupids LIKE "%|'.$ug.'|%"))'.
       ' AND ((IFNULL(f.access_groupids,"")="") OR (f.access_groupids LIKE "%|'.$ug.'|%"))'.
       ' AND ((IFNULL(f.access_userids,"")="")  OR (f.access_userids  LIKE "%|'.$uid.'|%"))'.
       ' AND ' . DownloadManagerTools::dlm_ActivatedQuery($userinfo['username']);
$DB->result_type = MYSQL_ASSOC;
if(empty($error) && ($file = $DB->query_first($sql)))
   // Section User-Level permissions not implemented!
   //' AND ((IFNULL(s.access_userids,"")="") OR (s.access_userids LIKE "%|'.$uid.'|%"))'.
{
  if(empty($file['fileid']) || empty($file['sectionid'])) // <- should not happen
  {
    // ra = restricted access
    $error = 'ra';
  }

  // ############################# CHECK USER ACCESS ############################

  // Check general plugin permission for downloads
  if(empty($error) && !$file['is_embeddable_media'] && (empty($userinfo['plugindownloadids']) || !in_array(DLM_PLUGINID, $userinfo['plugindownloadids'])))
  {
    // ra = restricted access
    $error = 'ra';
  }

  // Proceed if no error occured
  if(empty($error))
  {
    // Lets make sure there's actually a file to be downloaded OR that it's a link
    if( (($file['filetype'] != 'FTP') && ((int)$file['filesize'] < 1)) ||
        (($file['filetype'] == 'FTP') &&
         (substr(strtolower($file['filename']),0,4)!='http') &&
         (substr(strtolower($file['filename']),0,3)!='www')) )
    {
      // No file size, and it's not a link, kick user
      $error = 'dne';
    }
    else
    {
      // So far everything is okay, lets give the user the file

      // Log the download - but not if we are in admin or a range was given
      //v2.0.6: do not count embeddable media
      if(!$InAdmin && empty($_SERVER['HTTP_RANGE']) && empty($file['is_embeddable_media']) &&
         (!in_array(DLM_PLUGINID,$userinfo['pluginadminids']) ||
          NotEmpty(DownloadManagerTools::GetSetting('count_admin_downloads'))) )
      {
        $DB->query('UPDATE {p'.DLM_PLUGINID.'_files} SET downloadcount = (downloadcount+1)'.
                   ' WHERE fileid = %d', $fileid);

        if(NotEmpty(DownloadManagerTools::GetSetting('log_downloads')))
        {
          $DB->query('INSERT INTO {p'.DLM_PLUGINID."_file_downloads} VALUES(%d, '%s', '%s', ".time().")",
               $file['fileid'], $DB->escape_string($userinfo['username']), USERIP);
        }
      }

      // If file version is a "local" file and it really does exist,
      // set the "storedfilename" to the actual filename (fixes admin upload)
      if(empty($error) && ($file['filetype']=='FTP') && ($file['storedfilename']=='') && is_file($ftpdir.$file['filename']))
      {
        $file['storedfilename'] = $file['filename'];
      }

      // If storedfilename is still empty, assume it as a link
      if(empty($error) && ($file['filetype']=='FTP') && ($file['storedfilename']==''))
      {
        if((substr(strtolower($file['filename']),0,4)=='http') || (substr(strtolower($file['filename']),0,3)=='www'))
        {
          $filepath = $file['filename'];
          if(substr($file['filename'],0,3)=='www')
          {
            $filepath = 'http://' . str_replace('&amp;','&',$filepath);
          }
        }
        else
        {
          $filepath = $ftpdir . $file['filename'];
        }
        $DB->close();
        header("Location: $filepath");
        exit;
      }
      else
      if(empty($error))
      {
        //SD370: images are stored "as is" in "images" subfolder, which broke
        // the download from here onward:
        $isImage = !empty($file['storedfilename']) &&
                   (substr($file['storedfilename'],-4)!='.dat');
        if($isImage && @is_file($ftpdir.'../images/'.$file['storedfilename']))
        {
          $ftpdir .= '../images/';
        }

        // Exit if actual file could not be found
        if(!empty($file['storedfilename']) && !@is_file($ftpdir.$file['storedfilename']) &&
           !$isImage)
        {
          $error = 'off';
        }
        else
        {
          // If the user has gzip compression switched on, it would corrupt
          // the download in Internet Explorer, thus try to disable it
          if(function_exists('ini_set') && @ini_get('zlib.output_compression'))
          {
            @ini_set('zlib.output_compression', 0);
          }

          // Note: "filename" contains the target download filename,
          // "storedfilename" the physical file's name on the server
          $name = basename($file['filename']);
          $size = $file['filesize'];
          $type = $file['filetype'];
          $disposition = (!empty($forced) || $InAdmin || $isImage ? 'attachment' : 'inline');
          // Try to avoid PHP timeout (1 hour) if PHP is not in Safe Mode
          if(!sd_safe_mode())
          {
            @set_time_limit(3600);
          }
          if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE"))
          { //Fixing IE name issue
            $name = preg_replace('/\./', '%2e', $name, substr_count($name, '.') - 1);
          }
          /* TODO: check content type
          $contentType = 'application/octet-stream';
          $agent = env('HTTP_USER_AGENT');
          if (preg_match('%Opera(/| )([0-9].[0-9]{1,2})%', $agent) || preg_match('/MSIE ([0-9].[0-9]{1,2})/', $agent)) {
            $contentType = 'application/octetstream';
          }
          */
          // IF file is NOT stored in database, i.e. stored in filesystem,
          // and support for download managers is activated?
          if(!empty($file['storedfilename']) &&
             @is_file($ftpdir.$file['storedfilename']) &&
             (NotEmpty(DownloadManagerTools::GetSetting('allow_download_accelerators'))))
          {
            if(!is_readable($ftpdir.$file['storedfilename']))
            {
              $error = 'dnx1';
            }
            else
            {
              // Download of file stored in filesystem
              header("Pragma: public"); // required
              header("Expires: 0");
              header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
              header("Cache-Control: private", false); // required for certain browsers
              header("Content-Type: $type");
              header('Content-Disposition: '.$disposition.'; filename="'.$name.'"');
              header("Accept-Ranges: bytes");

              // Check if range was actually specified
              if(isset($_SERVER['HTTP_RANGE']))
              { //Checking if it's a partial content request
                list($a, $range) = explode("=",$_SERVER['HTTP_RANGE']);
                $dash = strpos($range,'-');
                if($dash===false)
                { //e.g. 500
                  $first = intval($range);
                  $last  = $size-1;
                }
                else
                {
                  $first = trim(substr($range, 0, $dash));
                  $last  = trim(substr($range,$dash+1));
                  if ($first!='' AND $last=='')
                  { //e.g. 500-
                    $first = intval($first);
                    $last = $size-1;
                  }
                  else if ($first!='' AND $last!='')
                  { //e.g. 500-600
                    $first = intval($first);
                    $last = intval($last);
                  }
                  else if ($first=='' AND $last!='')
                  { //e.g. -600
                    $suffix = $last;
                    $last   = $size-1;
                    $first  = $size-$suffix;
                    if($first<0) $first=0;
                  }
                }
                if(!$first OR $first>$last OR $first>($size-1))
                {
                  $first = 0;
                }
                if(!$last OR $last>($size-1))
                {
                  $last = $size-1;
                }
                $length = $last - $first + 1;
                header("HTTP/1.1 206 Partial Content");
                header("Content-Length: $length");
                header("Content-Range: bytes $first-$last/$size");
              }
              else
              {
                $last = $size-1;
                header("Content-Range: bytes 0-$last/$size");
                header("Content-Length: ".$size);
              }
              $first = isset($first)?(int)$first:0;
              if(!empty($file['storedfilename']))
              {
                if(false !== ($fp = @fopen($ftpdir.$file['storedfilename'],"rb")))
                {
                  $GLOBALS['sd_ignore_watchdog'] = true; //SD370
                  fseek($fp,$first);
                  // 2010-08-26: commented out "connection_status" as it is
                  // still buggy in PHP in several versions!
                  while(!feof($fp) /* && connection_status() == 0 */)
                  {
                    print(fread($fp,8192)); //chunks of 8KB
                    @ob_flush(); //SD370
                    flush(); //SD370
                  }
                  fclose($fp);
                }
              }
            }
          }
          else
          {
            // Download local file or one stored in database
            header("Pragma: public"); // required
            header("Expires: 0");
            //header("Cache-Control: max-age=60");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private", false); // required for certain browsers
            header("Content-Type: $type");
            header('Content-Disposition: '.$disposition.'; filename="'.$name.'"');
            #header("Content-Transfer-Encoding: binary");
            header("Content-Length: $size");
            if(!empty($file['storedfilename']))
            {
              if($DB->conn) $DB->close();
              @DownloadManagerTools::dlm_readfile_chunked($ftpdir.$file['storedfilename']);
            }
            else
            if(isset($file['file']) && ($file['file']!=''))
            {
              echo @pack("H*", $file['file']);
            }
          }
        }
      }
    }
  }
  else
  {
    // some error occured
    if(empty($error)) $error = 'dnx2';
  }
}
else
{
  // File offline, incorrect fileid or not in specified section
  if(empty($error)) $error = 'dnx3';
}

if(!empty($error))
{
  DownloadManagerTools::dlm_SendHeaderError($error);
}
