<?php
// +---------------------------------------------+
// | Download Manager plugin for Subdreamer      |
// +---------------------------------------------+
// | v2.2.0, September 2013                      |
// | Maintainer: 2007-2013 tobias                |
// | This version requires Subdreamer 3.6+!      |
// +---------------------------------------------+
global $dlm_currentdir;
if(!defined('IN_PRGM') || !isset($dlm_currentdir)) return false;

if(!function_exists('NotEmpty'))
{
  function NotEmpty($term=null)
  {
    return !empty($term);
  }
}

if(!defined('DLMT_LOADED'))
{
  define('DLMT_LOADED', true);

class DownloadManagerTools
{
  public static $filesdir  = '';
  public static $imagesdir = '';
  public static $imageurl  = '';
  public static $embevi    = null;
  public static $KnownAudioTypes = array();
  public static $KnownVideoTypes = array();

  public static $cache_on     = false;
  public static $seo_enabled  = false;
  public static $seo_cache    = false;
  public static $seo_redirect = true;
  public static $dlm_cache    = false;

  private static $DLM_VARS  = array();
  private static $DMT_Init  = false;
  private static $phrases   = array();
  private static $settings  = array();
  public  static $tbl_files = '';
  public  static $tbl_file_downloads = '';
  public  static $tbl_file_sections = '';
  public  static $tbl_file_versions = '';
  public  static $tbl_sections = '';

  public static function GetPhrase($phrase)
  {
    return isset(self::$phrases[$phrase]) ? self::$phrases[$phrase] : '';
  }

  public static function DMT_Init()
  {
    return self::$DMT_Init;
  }

  public static function GetVar($var)
  {
    return isset(self::$DLM_VARS[$var]) ? self::$DLM_VARS[$var] : null;
  }

  public static function GetSetting($setting, $default=null)
  {
    return isset(self::$settings[$setting]) ? self::$settings[$setting] : $default;
  }

  public static function SetSetting($setting, $value)
  {
    self::$settings[$setting] = $value;
  }

  public static function DownloadManagerToolsInit($dlm_pluginid = null)
  {
    global $DB, $dlm_currentdir, $layout_index, $pluginids, $pluginid,
           $mainsettings_modrewrite, $SDCache, $userinfo;

    self::$DMT_Init = false;
    self::$DLM_VARS = array();

    // If not in admin panel, there's no $pluginid, so check for it:
    if(!defined('IN_ADMIN') && isset($layout_index))
    {
      // Only for either admin area OR SD3 skin
      $pluginid = (int)$pluginids[$layout_index];
    }

    if(defined('IN_ADMIN') && is_numeric($pluginid) && !empty($pluginid))
    {
      //nothing here!
    }
    else
    {
      // Figure out pluginid by checking the pluginpath:
      /*
      $IsInstalled  = $DB->query_first('SELECT pluginid FROM {plugins} '.
                      "WHERE pluginpath = '$dlm_currentdir/dlm_downloads.php'");
      if(!empty($IsInstalled[0]))
      {
        // This IS installed, so return the plugin id so that it will
        // not show the "Install..." button again
        $pluginid = $IsInstalled[0];
      }
      else
      {
        //echo "Download Manager not available ($dlm_currentdir)";
        return false;
      }
      */
      if(!empty($dlm_pluginid))
      {
        $pluginid = $dlm_pluginid;
      }
      else
      {
        return false;
      }
    }

    self::$DLM_VARS['PLUGINID'] = (int)$pluginid;

    $pre = '{p'.$pluginid;
    self::$tbl_files = $pre.'_files}';
    self::$tbl_file_downloads = $pre.'_file_downloads}';
    self::$tbl_file_sections = $pre.'_file_sections}';
    self::$tbl_file_versions = $pre.'_file_versions}';
    self::$tbl_sections = $pre.'_sections}';

    // Initialize all required variables (retrievable by GetVar)
    // This way DEFINE's are not used, which are not re-writable, and the
    // plugin may be included multiple times in the page.
    self::$DLM_VARS['PATH']        = 'plugins/'.$dlm_currentdir.'/';
    self::$DLM_VARS['GETFILE']     = self::GetVar('PATH').'getfile.php?categoryid=';
    self::$DLM_VARS['HTML_PREFIX'] = 'p'.self::GetVar('PLUGINID');
    self::$DLM_VARS['URI_TAG']     = '&amp;'.self::GetVar('HTML_PREFIX');

    self::$DLM_VARS['DEFAULT_VERSION'] = '1.0.0';
    self::$DLM_VARS['DEFAULT_EXT']     = '.dat';
    self::$DLM_VARS['DEFAULT_MAX_UPLOAD_SIZE'] = 10485760;

    self::$DMT_Init = true;
    self::$phrases  = GetLanguage(self::GetVar('PLUGINID'));
    self::$settings = GetPluginSettings(self::GetVar('PLUGINID'));

    // Array with known file extensions of embeddable video files
    self::$KnownVideoTypes = explode(',', 'avi,flv,mp4,m4v,ogv,m3u8,mov,mpg,mpeg,swf,webm,wmv,xaml');

    // Array with known file extensions of embeddable video files
    self::$KnownAudioTypes = explode(',', 'au,aac,aif,gsm,midi,mp3,m4a,oga,ogg,rm,wma');

    self::$DLM_VARS['allow_admin']  = (!empty($userinfo['pluginadminids'])  && @in_array(self::GetVar('PLUGINID'), $userinfo['pluginadminids']));
    self::$DLM_VARS['allow_submit'] = (!empty($userinfo['pluginsubmitids']) && @in_array(self::GetVar('PLUGINID'), $userinfo['pluginsubmitids']));

    // Include EmbeVi Class for media file embedding
    self::$embevi = null;
    if(@include_once(ROOT_PATH.'includes/embevi.class.php'))
    {
      if(class_exists('EmbeVi'))
      {
        // Instantiate EmbeVi class
        self::$embevi = new EmbeVi();
      }
    }

    // Path and URL for images
    self::$imagesdir = self::dlm_FixPath(dirname(__FILE__) . '/images/');
    self::$imageurl  = SITE_URL . self::GetVar('PATH').'images/';

    // Set default for File Storage Location for all uploaded files
    // This is configured in plugin settings, otherwise "ftpfiles" in DLM path
    self::$filesdir = strlen(self::GetSetting('file_storage_location')) ?
                        self::GetSetting('file_storage_location') :
                        (dirname(__FILE__).'/ftpfiles/');
    self::$filesdir = self::dlm_FixPath(self::$filesdir);

    //v2.2.0: SEO and caching support
    self::$cache_on = !empty($SDCache) && ($SDCache instanceof SDCache) &&
                      $SDCache->IsActive();

    self::$seo_enabled = !empty($mainsettings_modrewrite) &&
                         !empty(self::$settings['enable_seo']);

    self::$DMT_Init = true;

    return true;

  } //DownloadManagerToolsInit

  // ###########################################################################

  public static function dlm_FixPath($fullpath, $addrootpath=true)
  {
    // Workaround for WAMP environments: usage of PHP function "filesize()"
    // won't work with mixed slashes/backslashes in path (PHP 5.1.x):
    if(strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')
    {
      $dir_separator = '/';
      $fullpath = str_replace("\\", $dir_separator, $fullpath);
    }
    else
    {
      $dir_separator = "\\";
      $fullpath = str_replace("/", $dir_separator, $fullpath);
    }
    // Make sure that at the end only ONE directory separator exists
    if(substr($fullpath,-2) == $dir_separator.$dir_separator)
    {
      $fullpath = substr($fullpath,0,strlen($fullpath)-1);
    }

    // Ensure trailing backslash
    $fullpath = ((substr($fullpath, -1) != $dir_separator) ? $fullpath . $dir_separator : $fullpath);

    /*
    IF a relative path is specified (not starting with "/"), treat the path
    as being relative to the "ROOT_PATH".
    However, for Win* platform (like XAMPP), do not use ROOT_PATH if a colon ":"
    is found, which indicates a drive letter = absolute path.
    */
    if(!empty($addrootpath) &&
       ((strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') && (substr($fullpath,0,1) != '/') ||
        (strtoupper(substr(PHP_OS, 0, 3))  == 'WIN') && (strpos($fullpath,':') === false)))
    {
      $fullpath = ROOT_PATH . $fullpath;
    }

    return $fullpath;
  } //dlm_FixPath

  // ###########################################################################
  // REWRITE SECTION LINK
  // ###########################################################################

  public static function RewriteSectionLink($currenturl, $sectionid, $title='', $page=0)
  {
    global $categoryid, $mainsettings_modrewrite, $mainsettings_url_extension;

    if($sectionid == 1)
    {
      return RewriteLink('index.php?categoryid='.$categoryid);
    }

    if(self::$seo_enabled && !empty($sectionid))
    {
      $link = $currenturl;
      $title = ConvertNewsTitleToUrl(sd_unhtmlspecialchars($title),$sectionid,0,true,null);
      $title .= '-s'.(int)$sectionid;
      if(!empty($page) && ((int)$page > 1)) $title .= '?page='.(int)$page;
      $link = str_replace($mainsettings_url_extension,
                          '/'.$title.$mainsettings_url_extension,
                          $link);

    }
    else
    {
      global $categoryid;
      $link = RewriteLink('index.php?categoryid='.$categoryid.'&'.
                          self::$DLM_VARS['HTML_PREFIX'].'_sectionid='.$sectionid);
    }
    return $link;

  } //RewriteSectionLink


  // #########################################################################
  // REWRITE FILE LINK
  // #########################################################################

  public static function RewriteFileLink($currenturl, $fileid, $file_title=null, $page=1)
  {
    global $categoryid, $mainsettings_url_extension;

    #$currenturl = RewriteLink('index.php?categoryid='.$categoryid);
    if(self::$seo_enabled)
    {
      $link  = $currenturl;
      $title = ConvertNewsTitleToUrl($file_title, $fileid, 0, false,'-f');
      $link  = str_replace($mainsettings_url_extension, '/'.$title, $link);
      return array('link' => $link, 'title' => $title);
    }
    else
    {
      $link = $currenturl.
              (strpos($currenturl,'?')===false?'?':'&amp;').
              self::GetVar('HTML_PREFIX').'_fileid='.$fileid;
      return array('link' => $link, 'title' => $file_title);
    }
    return $link;

  } //RewriteFileLink

  // ###########################################################################

  public static function dlm_GetAutoThumbnail($filename, $embeddable=false)
  {
    if(empty($filename)) return false;

    $ext = DownloadManagerTools::dlm_GetFileExtension($filename);
    if($embeddable)
    {
      $type_image = self::$imagesdir.'filetypes/video.png';
    }
    else
    {
      $type_image = self::$imagesdir.'filetypes/'.$ext.'.png';
    }
    if(file_exists($type_image))
    {
      return $type_image;
    }

    return false;
  }


  // ###########################################################################

  public static function dlm_GetMimeType($filename)
  {
    if(empty($filename)) return false;

    $filetype = 'application/octet-stream';

    @include_once(SD_INCLUDE_PATH . 'mimetypes.php');
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
  }

  // ###########################################################################

  public static function dlm_LinkAsMedia($link=null)
  {
    if(!empty($link) && class_exists('EmbeVi') && (self::$embevi instanceof EmbeVi))
    {
      return self::$embevi->parseText($link,false,false,false);
    }
    return false;
  }

  // ####################### GET SECTION FILE COUNT ###########################

  public static function dlm_GetFileTagCount($searchterm='')
  {
    global $DB, $userinfo;

    if(!strlen($searchterm)) return 0;
    $ug = $userinfo['usergroupid'];
    $uid = $userinfo['userid'];

    // Get total file count for files with tags containing searchterm
    $sql = 'SELECT COUNT(f.fileid) totalrows'.
           ' FROM '.self::$tbl_files.' f'.
           ' INNER JOIN '.self::$tbl_file_sections.' sf ON sf.fileid = f.fileid'.
           ' INNER JOIN '.self::$tbl_sections.' s ON s.sectionid = sf.sectionid'.
           ' INNER JOIN '.PRGM_TABLE_PREFIX.'tags t ON t.objectid = f.fileid'.
             ' AND t.pluginid = '.self::GetVar('PLUGINID').
             " AND lower(t.tag) = '".$DB->escape_string(strtolower($searchterm))."'".
           ' WHERE ' . DownloadManagerTools::dlm_ActivatedQuery($userinfo['username']).
           ' AND ((s.access_groupids = "") OR (s.access_groupids IS NULL) OR (s.access_groupids LIKE "%|'.$ug.'|%"))'.
           ' AND ((s.access_userids  = "") OR (s.access_userids  IS NULL) OR (s.access_userids  LIKE "'.$uid.'"))'.
           ' AND ((f.access_groupids = "") OR (f.access_groupids IS NULL) OR (f.access_groupids LIKE "%|'.$ug.'|%"))'.
           ' AND ((f.access_userids  = "") OR (f.access_userids  IS NULL) OR (f.access_userids  LIKE "'.$uid.'"))';
    $filecount = 0;
    $DB->result_type = MYSQL_ASSOC;
    if($filecount = $DB->query_first($sql))
    {
      $filecount = (int)$filecount['totalrows'];
    }

    return $filecount;

  } //dlm_GetFileTagCount


  // ####################### GET SECTION FILE COUNT ###########################

  public static function dlm_GetSectionFileCount($sectionid, $filecount,
                                                 $countsubs=true, $extraWhere='')
  {
    global $DB, $userinfo;

    $ug = $userinfo['usergroupid'];
    $uid = $userinfo['userid'];

    // Get total file count of current section depending on usergroup
    //SD370: $sectionid < 1 now means to search ALL sections
    $sql = 'SELECT COUNT(f.fileid) totalrows'.
           ' FROM '.self::$tbl_files.' f'.
           ' INNER JOIN '.self::$tbl_file_sections.' sf ON sf.fileid = f.fileid'.
           ' INNER JOIN '.self::$tbl_sections.' s ON s.sectionid = sf.sectionid'.
           ' WHERE '.
           ($sectionid < 1 ? '' : ' s.sectionid = %d AND ').
           DownloadManagerTools::dlm_ActivatedQuery($userinfo['username']).
           ' AND (s.count_files = 1)'. #v2.4.0
           ' AND ((s.access_groupids = "") OR (s.access_groupids IS NULL) OR (s.access_groupids LIKE "%|'.$ug.'|%"))'.
           ' AND ((s.access_userids  = "") OR (s.access_userids  IS NULL) OR (s.access_userids  LIKE "'.$uid.'"))'.
           ' AND ((f.access_groupids = "") OR (f.access_groupids IS NULL) OR (f.access_groupids LIKE "%|'.$ug.'|%"))'.
           ' AND ((f.access_userids  = "") OR (f.access_userids  IS NULL) OR (f.access_userids  LIKE "'.$uid.'")) '.
           $extraWhere;
    $DB->result_type = MYSQL_ASSOC;
    if($filecount = $DB->query_first($sql, $sectionid))
    {
      $filecount = (int)$filecount['totalrows'];
    }

    // Check for subsections?
    if($countsubs)
    {
      if($getsubsections = $DB->query('SELECT sectionid FROM '.self::$tbl_sections.
                                      ' WHERE parentid = %d AND activated = 1'.
                                      ' AND count_files = 1',$sectionid))
      {
        while($subsection = $DB->fetch_array($getsubsections,null,MYSQL_ASSOC))
        {
          $filecount += self::dlm_GetSectionFileCount($subsection['sectionid'], $filecount, $countsubs, $extraWhere);
        }
      }
    }

    return $filecount;

  } //dlm_GetSectionFileCount


  // ########### Display sections for either SELECT tag or UL/LI list ##########
  // This works for both the frontpage and the admin panel by using different links.

  public static function dlm_PrintChildSections($config_arr)
  /*
    Do NOT change any default values of parameters!
    parentid               required
    selectedSectionId      null
    displayCounts          false
    parentText             ''
    asMenu                 false
    menuAction             ''
  */
  {
    global $DB;
	$return = '';

    // If no valid array is passed, do nothing and return:
    if(empty($config_arr) || !is_array($config_arr)) return;

    static $default_array = array(
                              'selectedSectionId' => '',
                              'excludedSectionId' => '',
                              'displayCounts'     => false,
                              'parentText'        => '',
                              'asMenu'            => false,
                              'menuAction'        => '',
                              'li_attrib'         => ''
                            );

    $config_arr = array_merge($default_array, $config_arr);

    // Extract array keys as "$conf_*" variables:
    extract($config_arr, EXTR_PREFIX_ALL, 'conf');

    $excludeSql = '';
    if(!empty($conf_excludedSectionId) && !is_array($conf_excludedSectionId))
    {
      $conf_excludedSectionId = array($conf_excludedSectionId);
      $excludeSql = ' AND NOT (sectionid IN ('.implode(',',$conf_excludedSectionId).'))';
    }

    if($getsections = $DB->query('SELECT sectionid, name FROM '.self::$tbl_sections.
                           ' WHERE parentid = %d'.
                           (defined('IN_ADMIN') ? '' : ' AND ACTIVATED = 1').
                           $excludeSql.
                           ' ORDER BY name',$conf_parentid))
    {
      while($section = $DB->fetch_array($getsections,null,MYSQL_ASSOC))
      {
        $name = $section['name'];
        if(strlen($name) <= 0)
        {
          if(empty($conf_parentid))
            $name = '&lt;Root&gt;';
          else
            $name = '&lt; ' . self::GetPhrase('untitled') . ' ' . $section['sectionid'] . '&gt;';
        }
        $section_count = false;
        if(!$conf_asMenu && $conf_displayCounts)
        {
          $filecount = 0;
          $filecount = self::dlm_GetSectionFileCount($section['sectionid'],$filecount,false);
		  if(!$conf_return)
		  {
          echo "<option value=\"$section[sectionid]\" ".
            (is_array($conf_selectedSectionId) && @in_array($section['sectionid'], $conf_selectedSectionId) ? 'selected="selected"' : '').">
            $conf_parentText$name (".$filecount.")</option>";
		  }
		  else
		  {
		   	$return .= "<option value=\"$section[sectionid]\" ".
            (is_array($conf_selectedSectionId) && @in_array($section['sectionid'], $conf_selectedSectionId) ? 'selected="selected"' : '').">
            $conf_parentText$name (".$filecount.")</option>";
			}
        }
        else
        {
          // Display as menu?
          if($conf_asMenu && !empty($conf_menuAction))
          {
            $DB->result_type = MYSQL_ASSOC;
            $section_count = $DB->query_first('SELECT COUNT(*) scount'.
                                  ' FROM '.self::$tbl_sections.
                                  ' WHERE parentid = %d'.
                                  (defined('IN_ADMIN') ? '' : ' AND ACTIVATED = 1'),
                                  $section['sectionid']);
            if(defined('IN_ADMIN'))
				if(!$conf_return)
				{
              		echo '<li><a href="'.REFRESH_PAGE.$conf_menuAction.'&amp;sectionid='.$section['sectionid'].'">'.$name.'</a>';
				}
				else
				{
					 $return .= '<li><a href="'.REFRESH_PAGE.$conf_menuAction.'&amp;sectionid='.$section['sectionid'].'">'.$name.'</a>';
				}
            else
				if(!$conf_return)
				{
              		echo '<li><a href="'.RewriteLink('index.php'.$conf_menuAction.self::GetVar('URI_TAG').'_sectionid='.$section['sectionid']).'">'.$name.'</a>';
				}
				else
				{
					$return .= '<li><a href="'.RewriteLink('index.php'.$conf_menuAction.self::GetVar('URI_TAG').'_sectionid='.$section['sectionid']).'">'.$name.'</a>';
				}
            if(!empty($section_count['scount'])) echo '<ul>';
          }
          else
          {
			  if(!$conf_return)
			  {
            echo "<option value=\"$section[sectionid]\" ".
            (is_array($conf_selectedSectionId) && @in_array($section['sectionid'], $conf_selectedSectionId) ? 'selected="selected"' : '').">
            $conf_parentText$name</option>";
			  }
			  else
			  {
				  $return .= "<option value=\"$section[sectionid]\" ".
            (is_array($conf_selectedSectionId) && @in_array($section['sectionid'], $conf_selectedSectionId) ? 'selected="selected"' : '').">
            $conf_parentText$name</option>";
			  }
          }
        }

        // Recursive call for current section
        $config_arr['parentid'] = $section['sectionid'];
        $config_arr['parentText'] = (!$conf_asMenu ? $conf_parentText.$name.'\\' : '');
        self::dlm_PrintChildSections($config_arr);

        if($conf_asMenu)
        {
          if(!empty($section_count['scount']))echo '</ul>';
			  
			
         echo '</li>';
        }
      }
      $DB->free_result($getsections);
	  if($conf_return) return $return;
    }
  } //dlm_PrintChildSections


  public static function dlm_PrintSectionSelection($config_arr)
  /*
  "$config_arr" may contain the following keys (in any order):
    Key                    Default
    ----------------------------------------------
    formId                 required, no default
    formName               usually same as formId
    selectedSectionId      ''
    displayCounts          true
    displayOffline         false
    showmultiple           true
    asMenu                 false
    menuAction             ''
    size                   5
    extraStyle             ''
    excludeSectionId       0

    Any missing key is defaulted to above mentioned values.

  Example call:
      DownloadManagerTools::dlm_PrintSectionSelection(
        array('formId'            => DownloadManagerTools::GetVar('HTML_PREFIX').'_sectionid',
              'selectedSectionId' => $sectionid,
              'displayCounts'     => false,
              'displayOffline'    => false));
  */
  {
	 $return = '';
    // If no valid array is passed, do nothing and return:
    if(empty($config_arr) || !is_array($config_arr)) return;

    static $default_array = array(
                              'selectedSectionId' => '',
                              'excludedSectionId' => '',
                              'displayCounts'     => true,
                              'displayOffline'    => false,
                              'showmultiple'      => true,
                              'asMenu'            => false,
                              'menuAction'        => '',
                              'ul_attrib'         => '',
                              'li_attrib'         => '',
                              'size'              => 5,
                              'extraStyle'        => '',
							  'return'			  => false,
                            );

    $config_arr = array_merge($default_array, $config_arr);

    // Extract array keys as "$conf_*" variables:
    extract($config_arr, EXTR_PREFIX_ALL, 'conf');

    // Form's ID is required if not displayed as menu:
    if(empty($conf_formId) && empty($conf_asMenu)) return;
    if(empty($conf_formName))
    {
      $conf_formName = $conf_formId;
    }
    if(!empty($conf_asMenu))
    {
       if(!$conf_return)
	   {
	   	 	echo '<ul'.$conf_ul_attrib.'>';
	   }
	   else
	   {
		   $return .= '<ul'.$conf_ul_attrib.'>';
	   }
	
    }
    else
    {
		if(!$conf_return)
		{
      	echo $output = "
      <select class=\"form-control\" id=\"$conf_formId\" name=\"$conf_formName\" ".$conf_extraStyle. (empty($conf_showmultiple) ? '' : ' size ="'.$conf_size.'" multiple="multiple"').'>
      ';
		}
		else
		{
			$return .= "
      <select class=\"form-control\" id=\"$conf_formId\" name=\"$conf_formName\" ".$conf_extraStyle. (empty($conf_showmultiple) ? '' : ' size ="'.$conf_size.'" multiple="multiple"').'>
      ';
		}
    }

    if(!empty($conf_selectedSectionId) && !is_array($conf_selectedSectionId))
    {
      $conf_selectedSectionId = array($conf_selectedSectionId);
    }

    $config_sub_arr = array('parentid'          => 0,
                            'selectedSectionId' => $conf_selectedSectionId,
                            'excludedSectionId' => (isset($conf_excludedSectionId)?$conf_excludedSectionId:-1),
                            'displayCounts'     => $conf_displayCounts,
                            'parentText'        => '',
                            'li_attrib'         => $conf_li_attrib,
							'ul_attrib'			=> $conf_ul_attrib,
                            'asMenu'            => $conf_asMenu,
                            'menuAction'        => $conf_menuAction,
							'return'			=>	$conf_return);
    self::dlm_PrintChildSections($config_sub_arr);

    // Show Offline files?
    if(!$conf_asMenu && $conf_displayOffline)
    {
      global $DB;
      $DB->result_type = MYSQL_ASSOC;
      if($offlinerows = $DB->query_first('SELECT COUNT(fileid) fcount FROM '.self::$tbl_files.' WHERE IFNULL(activated,0) = 0'))
      {
       	if(!$conf_return)
		{
	    echo '<option value="-1"'.
             (is_array($conf_selectedSectionId) && @in_array(-1, $conf_selectedSectionId) ? ' selected="selected"' : '').
             '>'.self::GetPhrase('dlm_offline_files').' (' . $offlinerows['fcount'] . ')</option>';
		}
		else
		{
			$return .= '<option value="-1"'.
             (is_array($conf_selectedSectionId) && @in_array(-1, $conf_selectedSectionId) ? ' selected="selected"' : '').
             '>'.self::GetPhrase('dlm_offline_files').' (' . $offlinerows['fcount'] . ')</option>';
		}
      }
    }

    if($conf_return)
		return empty($conf_asMenu) ? $return . '</select>'  :  $return . '</ul>';
		
	echo empty($conf_asMenu) ? '</select>' : '</ul>';

  } //dlm_PrintSectionSelection


  // ########################### PRINT ERRORS #################################

  public static function PrintErrors($errors, $errortitle = 'Errors')
  {
    echo '
    <table width="100%" border="0" cellpadding="2" cellspacing="2">
    <tr>
      <td style="border: 1px solid #FF0000; font-size: 12px;" bgcolor="#FFE1E1"><u>' .
      $errortitle .'</u><br />'.self::GetPhrase('errors_header').'<br />';

    if(is_array($errors))
    {
      $fc = count($errors);
      for($i = 0; $i < $fc; $i++)
      {
        echo '<strong>' . ($i + 1) . ') ' . $errors[$i] . '</strong><br />';
      }
    }
    else
    {
      echo '<strong>1) ' . $errors . '</strong><br />';
    }

    echo '</td>
    </tr>
    </table><br />';

  } //PrintErrors

  // ########################## UPDATE FILE THUMBNAIL #########################
  // Remove a previous image of a specific fileid and update
  // file details with new thumbnail or image name ($colname).
  // The new image file is expected to be already copied!
  // Result is TRUE if an existing image was replaced, otherwise FALSE.
  public static function dlm_ReplaceFileImage($colname,$fileid,$imagepath,$newthumbname,$isnewfile=false,$keepfile=false)
  {
    global $DB;

    if((strlen($colname)>0) && ($fileid>0))
    {
      if((!$isnewfile) || (strlen($newthumbname)==0))
      {
        // delete old thumbnail
        $oldimg = $DB->query_first('SELECT '.$DB->escape_string($colname).
                                   ' FROM '.self::$tbl_files.' WHERE fileid = %d',$fileid);
        $isnewfile = (strlen($oldimg[$colname])==0) || ($oldimg[$colname] <> $newthumbname);
        if((strlen($oldimg[$colname])>0) && !$keepfile)// && ($oldimg[$colname] <> $newthumbname))
        {
          $oldimgpath = $imagepath . $oldimg[$colname];
          @unlink($oldimgpath);
          $isnewfile = true;
        }
      }

      // Update the file entry with the new thumbnail name
      if(($colname == 'image') || ($colname == 'thumbnail'))
      {
        $DB->query('UPDATE '.self::$tbl_files." SET $colname = '%s' WHERE fileid = %d",
                   $DB->escape_string($newthumbname),$fileid);
      }
    }

    return $isnewfile;

  } //dlm_ReplaceFileImage


  // ########################### REPLACE SECTION THUMBNAIL #####################
  // Replace a previous imagename of a specific sectionid and updates
  // section with either a new thumbnail or image name:
  // $colname has to be the physical column name, e.g. "image" or "thumbnail"!
  //
  // Any *new* image file itself HAS to be already copied!
  //
  // Depending on the passed in value of "$isnewsection" the actual file
  // removal operation may be prohibited, so that only the section table
  // entry is being updated.
  //
  // Result is TRUE if the image name has changed, otherwise FALSE.
  public static function dlm_ReplaceSectionImage($colname,$sectionid,$imagepath,$newthumbname,$isnewsection=false)
  {
    global $DB;

    if((strlen($colname)>0) && ($sectionid>0))
    {
      if(!$isnewsection)
      {
        // Fetch previous image filename and remove physical file
        // from the specified "$imagepath" directory
        $oldimg = $DB->query_first("SELECT $colname FROM ".self::$tbl_sections.' WHERE sectionid = %d',$sectionid);
        if((strlen($oldimg[$colname])>0) && ($oldimg[$colname] <> $newthumbname))
        {
          $oldimgpath = $imagepath . $oldimg[$colname];
          if(is_file($oldimgpath))
          {
            @unlink($oldimgpath);
          }
          $isnewsection = true;
        }
      }

      // Update the section entry with the new filename
      // the actual file must have been copied already!
      $DB->query('UPDATE '.self::$tbl_sections." SET $colname = '%s' WHERE sectionid = %d",
                 $DB->escape_string($newthumbname), $sectionid);
    }
    return $isnewsection;

  } //dlm_ReplaceSectionImage


  // ######################## SCALE IMAGE DIMENSIONS ###########################
  // &$width, &$height return original image dimensions (if no error)
  // &$maxwidth, &$maxheight will pass-in the max. values and return
  // the new, scaled values.
  // NOTE: "$imagepath" should (ideally) be the local webserver path to the
  //       plugins' "images" directory, not a URL - getimagesize works better
  //       and faster that way as it is for internal page preparation.
  public static function dlm_ScaleImage($imagepath, &$width, &$height, &$maxwidth, &$maxheight)
  {
    if(empty($imagepath) || empty($maxwidth) || empty($maxheight))
    {
      return;
    }

    // if path is for local images, switch to relative path to avoid errors
    // on servers which do not allow opening remote URLs:
    $tmp = $imagepath;
    if(strpos($imagepath, self::GetVar('PATH').'images/') !== FALSE)
    {
      $tmp = self::$imagesdir.basename($imagepath);
    }
    if(!is_readable($imagepath) || !(list($width2, $height2, $type, $attr) = @getimagesize($tmp)))
    {
      return;
    }
    $width = $width2;
    $height= $height2;

    $scale = min($maxwidth/$width, $maxheight/$height);

    // If the image is larger than the max shrink it
    if($scale < 1)
    {
      $newwidth = floor($scale * $width);
      $newheight = floor($scale * $height);
    }
    else
    {
      $newwidth = $width;
      $newheight = $height;
    }

    // Prevent 0 height or width
    if(($newheight==0) && ($height > 0))
    {
      $newheight  = $height;
    }
    if(($newwidth==0) && ($width > 0))
    {
      $newwidth  = $width;
    }

    $maxwidth = $newwidth;
    $maxheight = $newheight;

  } //dlm_ScaleImage


  public static function dlm_GetFileExtension($filename)
  {
    if(empty($filename) || (strlen($filename)<4))
    {
      return null;
    }
    if(function_exists('pathinfo') && (substr(PHP_VERSION,0,5) >= "4.0.3"))
    {
      $img_parts = pathinfo($filename);
      $ext = isset($img_parts['extension'])?strtolower($img_parts['extension']):'';
    }
    // backward compatibility
    else
    {
      // For versions of PHP < 4.0.3
      $filename = strtolower($filename);
      if(substr($filename, -5) == '.jpeg')
      {
        $ext = 'jpeg';
      }
      else
      {
        $ext = substr($filename, -4);
        if(substr($ext, 0, 1) != '.')
        {
          $ext = '';
        }
        else
        {
          $ext = substr($ext, 1);
        }
      }
    }
    return $ext;
  } //dlm_GetFileExtension


  // ######################## IS FILENAME AN IMAGE? ############################
  // Based on a given filename this returns the extension for known
  // image types, otherwise NULL.
  public static function dlm_HasFileImageExt($filename)
  {
    if(!isset($filename) || (!strlen($filename)))
    {
      return null;
    }
    $ext = self::dlm_GetFileExtension($filename);

    // If not supported, return NULL as extension
    if(empty($ext) || !(@in_array($ext,array('jpeg','jpg','gif','bmp','png'))))
    {
      return null;
    }
    else
    {
      return $ext;
    }

  } //dlm_HasFileImageExt


  // ########################## CHECK IMAGE FILE ###############################
  // Check an uploaded image as "$_FILES" array for validity.
  // Returns true or false (if new error entries were made)
  // Returned variables:
  //   "$errors": array of error messages (existing+new ones)
  //   "$extension": is only for supported image type the file's extension
  public static function dlm_CheckImageFile($image,&$errors,&$extension)
  {
    $extension = '';

    if(!isset($image) || (!isset($image['name'])))
    {
      return false;
    }

    // List of accepted image types
    $known_photo_types = array(
      'image/pjpeg' => 'jpg',
      'image/jpeg'  => 'jpg',
      'image/gif'   => 'gif',
      'image/bmp'   => 'bmp',
      'image/x-png' => 'png',
      'image/png'   => 'png');

    // Check if $errors already has entries to later detect new ones
    $errcount = (isset($errors)?count($errors):0);

    // Check if an image was uploaded
    if(isset($image['error']) && $image['error'] != 0 && $image['error'] != 4)
    {
      if($image['error'] <= 2)
      {
        $errors[] = self::GetPhrase('image_too_big') . ' ' . $image['name'];
      }
      else
      {
        $errors[] = self::GetPhrase('image_upload_error') . ' - ' . self::dlm_GetFileErrorDescr($image['error']) .
                    ' ' . $image['name'];
      }
    }
    // Has uploaded file a valid size?
    else if($image['size'] > 0)
    {
      if(!isset($known_photo_types[$image['type']]))
      {
        $errors[] = self::GetPhrase('unknown_image_type') . ' (' . $image['name'] . ')';
      }
      else
      {
        $imagetype = $image['type'];
        $extension = $known_photo_types[$imagetype];
      }
    }
    // Any other, unknown error
    else
    {
      if(($extension=='') || (!isset($image['error']) && ($image['error'] > 0) && (strlen($image['name'])>0)))
      {
        $errors[] = 'Not a valid image file';
      }
      return false;
    }

    // Return true/false depending on new errors being added
    return !isset($errors) || (count($errors)<=$errcount);

  } //dlm_CheckImageFile


  // ######### RETURN THUMBNAIL IMAGE LINK WITH GIVEN SIZE (UNSCALED) ##########

  // Function to return a full "<img src=" tag for a given "$file".
  // NOTE: It assumes, that it is to be found in the plugins' "images"
  //       directory. For scaling it uses the webserver's local path but
  //       for the client browser it uses the URL (self::$imageurl).
  public static function dlm_GetFileImageAsThumb($file,$colname='thumbnail',$displaywidth=100,
                                                 $displayheight=100,$addtags='',$showexternal=true)
  {
    global $DB;

    if(!empty($file) && !empty($colname) && (strlen($file[$colname])>0))
    {
      // Check if we external files are to be skipped
      if(!$showexternal && ((substr(strtolower($file['filename']), 0, 3) == 'www') || (substr(strtolower($file['filename']), 0, 4) == 'http')))
      {
        return $file['title'] . '&nbsp;';
      }
      else
      {
        if(empty($addtags))
        {
          $addtags = $file['title']; // Title
        }
        self::dlm_ScaleImage(self::$imagesdir . $file[$colname], $width, $height, $displaywidth, $displayheight);
        return '<img alt="" src="' . self::$imageurl . $file[$colname] . '" title="' . $addtags . '" '.
          (!empty($displaywidth)?'width="'.($displaywidth<40?40:$displaywidth).'" height="'.($displayheight<40?40:$displayheight).'"':'').
          ' style="vertical-align: middle" />';
      }
    }
    return '&nbsp;';

  } //dlm_GetFileImageAsThumb


  // ######################### DISPLAY IMAGE DETAILS ###########################

  // This is an adapted function from the "Image Manager" plugin.
  // Function either echoes or returns ($returnlink) a 2-column table with a)
  // the scaled image (as "preview") and b) dimensions (width/height);
  // display of the imagename itself is optional;
  // "$extratext" may be used to append extra text or links (e.g. in the "Edit
  // File" dialog such as "Delete Thumbnail" etc.)
  public static function dlm_DisplayImageDetails($imagename, $imagepath, $showimagename = false,
      $extratext = '', $returnlinkonly = false, $maxwidth = 200, $maxheight = 200)
  {
    if(!$imagepath)
    {
      return '';
    }
    // if path is for local images, switch to relative path to avoid errors
    // on servers which do not allow opening remote URLs:
    $tmp = $imagepath;
    if(strpos($imagepath, self::GetVar('PATH').'images/') !== FALSE)
    {
      $tmp = self::$imagesdir.basename($imagepath);
    }
    if(file_exists($tmp))
    {
      list($width2, $height2, $type, $attr) = @getimagesize($tmp);
    }
    else
    {
      $width2 = $height2 = 0;
    }

    // If possible, use phpThumb for scaling:
    if(file_exists(SD_INCLUDE_PATH.'phpthumb/phpthumb.functions.php'))
    {
      include_once(SD_INCLUDE_PATH.'phpthumb/phpthumb.functions.php');
      $tf = new phpthumb_functions();
      list($newW, $newH) = $tf->ScaleToFitInBox($width2, $height2, $maxwidth, $maxheight, false, true);
      $url = SD_INCLUDE_PATH.'phpthumb/phpThumb.php?src='.self::$imagesdir . $imagename.'&amp;w='.$maxwidth.'&amp;h='.$maxheight;
      $imgref = '<img alt="" src="'.$url.'" width="'.$newW.'" height="'.$newH.'" style="border: 1px solid #000" />';
      unset($tf);
    }
    else
    {
      $width  = $maxwidth;
      $height = $maxheight;
      self::dlm_ScaleImage($imagepath, $width, $height, $maxwidth, $maxheight);
      $imgref = '<img alt="" src="'.self::$imageurl.$imagename.'" width="'.$maxwidth.'" height="'.$maxheight.'" style="border: 1px solid #000" />';
    }

    $out = '
    <table width="100%" border="0" cellpadding="8" cellspacing="0">
    <tr>
      <td width="10" valign="top" style="padding-right: 5px;">
        <a class="ceebox" href="' . self::$imageurl . $imagename . '" target="_blank">' . $imgref . '</a>
      </td>
      <td valign="top">' .
        ($showimagename?('<p><strong>' . $imagename . '</strong></p>'):'') .
        'Width:  ' . $width2 . ' Pixels<br />Height: ' . $height2 . ' Pixels' .
        (strlen($extratext)?('<br /><br />' . $extratext):'') . '
      </td>
    </tr>
    </table>';

    if($returnlinkonly)
    {
      return $out;
    }
    else
    {
      echo $out;
    }

  } //dlm_DisplayImageDetails


  // ############################# CREATE THUMBNAIL ##############################
  // "$useform" = true only when called to show the thumbnailform (errors) and redirect
  public static function dlm_DoCreateThumbnailByParams($fileid,$sectionid,$imagepath,$folderpath,
            $thumbfilename, $maxwidth=100, $maxheight=100, $useform=false, $conf_arr=array())
  {
    $err = CreateThumbnail($imagepath, $folderpath . $thumbfilename, $maxwidth, $maxheight,
                           !empty($conf_arr['squaredoff']), !empty($conf_arr['keepratio']),
                           $conf_arr);
    if(isset($err))
    {
      DisplayMessage($err, true);
      if($useform)
      {
        self::dlm_DisplayThumbnailForm($fileid, $sectionid, $imagepath);
      }
      return false;
    }
    else
    {
      $url = false;
      // Update file or section table with new thumbnail
      if($fileid > 0)
      {
        $url = '&amp;fileid='.(int)$fileid;
        self::dlm_ReplaceFileImage('thumbnail', $fileid, self::$imagesdir, $thumbfilename, $useform);
      }
      else
      if($sectionid > 0)
      {
        $url = '&amp;sectionid='.(int)$sectionid;
        self::dlm_ReplaceSectionImage('thumbnail', $sectionid, self::$imagesdir, $thumbfilename, $useform);
      }
      if($useform && $url)
      {
        PrintRedirect(REFRESH_PAGE . '&amp;action=displayfileform'.$url, 1);
      }
      return true;
    }

  } //dlm_DoCreateThumbnailByParams


  public static function dlm_DoCreateThumbnailByPost($fileid,$sectionid,$imagepath)
  {
    $folderpath    = GetVar('folderpath',0,'string',true,false);
    $thumbfilename = GetVar('thumbfilename',0,'string',true,false);
    $maxwidth      = GetVar('maxwidth',0,'whole_number',true,false);
    $maxheight     = GetVar('maxheight',0,'whole_number',true,false);
    $conf_arr      = array();
    $apply_watermark = GetVar('apply_watermark',0,'bool',true,false);
    if($apply_watermark)
    {
      $conf_arr['text']  = GetVar('watermark_text','','string',true,false);
      $conf_arr['alignment'] = GetVar('watermark_alignment','TL','string',true,false);
      $conf_arr['pos_x'] = GetVar('watermark_x',0,'natural_number',true,false);
      $conf_arr['pos_y'] = GetVar('watermark_y',0,'natural_number',true,false);
      $conf_arr['color'] = GetVar('watermark_color','','string',true,false);
      $conf_arr['font']  = GetVar('watermark_font','0','string',true,false);
      $conf_arr['size']  = GetVar('watermark_size',4,'whole_number',true,false);
    }
    $conf_arr['keepratio'] = GetVar('keepratio',0,'natural_number',true,false);
    $conf_arr['squaredoff'] = GetVar('squaredoff',0,'natural_number',true,false);
    self::dlm_DoCreateThumbnailByParams($fileid,$sectionid,$imagepath,$folderpath,
                                        $thumbfilename,$maxwidth,$maxheight,true,$conf_arr);

  } //dlm_DoCreateThumbnail


  // ##################### DISPLAY THUMBNAIL FORM DETAILS #######################

  public static function dlm_DisplayThumbnailForm($fileid, $sectionid, $imagename, $useftpdir=0, $useimgext='')
  {
    global $DB;

    if((!is_numeric($fileid) && !is_numeric($sectionid)) || (strlen($imagename)==0))
    {
      // No valid parameters so jump back
      PrintRedirect(REFRESH_PAGE, 1);
      return;
    }

    $imgext      = self::dlm_HasFileImageExt($imagename);
    $imagefolder = (($useftpdir==1) || empty($imgext) ? self::$filesdir : self::$imagesdir); // only foldername
    $imagepath   = $imagefolder . $imagename; // full path to image
    $imageurl    = self::$imageurl . $imagename; // full URL to image
    $imageextension = (strlen($useimgext)==0?substr($imagename, -4):'.'.$useimgext);
    $img_suffix  = (!empty($sectionid) ? 's_' : '_'); // only if sectionid is given, use "s_" as suffix
    $thumbname   = 'tb' . $img_suffix . ($fileid?$fileid:'') . ($sectionid?$sectionid:'') . '.png';
    $setting     = self::GetSetting('thumbnail_display_width');
    $thumbheight = $thumbwidth = (!empty($setting)?(int)$setting:200);

    PrintSection('Create Thumbnail');
    echo '
    <form method="post" action="'.REFRESH_PAGE.'&pluginid='.self::GetVar('PLUGINID').'">
    '.PrintSecureToken().'
    <input type="hidden" name="action" value="DoCreateThumbnail" />
    <input type="hidden" name="fileid" value="' . (empty($fileid)?0:$fileid) . '" />
    <input type="hidden" name="sectionid" value="' . (empty($sectionid)?0:$sectionid) . '" />
    <input type="hidden" name="folderpath" value="' . self::$imagesdir /* target thumbnail directory */ . '" />
    <input type="hidden" name="imagename" value="' . $imagepath  /* full path of source image */ . '" />
    <input type="hidden" name="thumbfilename" value="' . $thumbname . '" />
    <table width="100%" border="0" cellpadding="5" cellspacing="0">
    <tr>
      <td class="tdrow1" colspan="2">Image</td>
    </tr>
    <tr>
      <td class="tdrow2" width="50%">Create a thumbnail from this image:</td>
      <td class="tdrow3">';

    $imgref = '';
    if(!$imgext)
    {
      if($useimgext)
      {
        $fileurl = self::GetSetting('file_storage_location_url');
        if(!empty($fileurl))
        {
          $imagepath = $fileurl . $imagename;
          $imgref = '<object data="' . $imagepath . '" '.
            'width="' . $thumbheight . '" height="' . $thumbheight . '" '.
            ' type="image/'.$useimgext.'" '.
            '><param name="src" value="'.$imagepath.'">no image</object>';

            echo $imgref;
        }
      }
    }
    else
    {
      self::dlm_DisplayImageDetails($imagename, $imagepath, false, '', false, $thumbheight, $thumbheight);
    }

    echo '</td>
    </tr>
    <tr>
      <td class="tdrow1" colspan="2">Max Thumbnail Width</td>
    </tr>
    <tr>
      <td class="tdrow2">Enter the maximum width (in pixels) for the thumbnail:</td>
      <td class="tdrow3"><input type="text" name="maxwidth" value="' . $thumbwidth . '" size="4" /></td>
    </tr>
    <tr>
      <td class="tdrow1" colspan="2">Max Thumbnail Height</td>
    </tr>
    <tr>
      <td class="tdrow2">Enter the maximum height for the thumbnail:</td>
      <td class="tdrow3"><input type="text" name="maxheight" value="' . $thumbheight . '" size="4" /></td>
    </tr>';

  // Only for ".png" thumbnails, if possible, use phpThumb for processing:
  if(file_exists(SD_INCLUDE_PATH.'phpthumb/phpthumb.class.php'))
  {
    echo '
    </table>
    <h1>Image Processing Options</h1>
    <table width="100%" border="0" cellpadding="5" cellspacing="0">
    <tr>
      <td class="tdrow1" colspan="2">Watermark</td>
    </tr>
    <tr>
      <td class="tdrow2" width="50%">Apply watermark to image?</td>
      <td class="tdrow3" width="50%"><input type="checkbox" name="apply_watermark" value="1" /></td>
    </tr>
    <tr>
      <td class="tdrow2">Watermark Text:</td>
      <td class="tdrow3"><input type="text" name="watermark_text" value="" size="40" style="width: 96%" /></td>
    </tr>
    <tr>
      <td class="tdrow2">Watermark Alignment:</td>
      <td class="tdrow3">
        <select name="watermark_alignment" style="width: 200px">
        <option value="TL" selected="selected">Top Left</option>
        <option value="T">Top Center</option>
        <option value="TR">Top Right</option>
        <option value="C">Center Center</option>
        <option value="L">Center Left</option>
        <option value="R">Center Right</option>
        <option value="BL">Bottom Left</option>
        <option value="B">Bottom Center</option>
        <option value="BR">Bottom Right</option>
        </select>
      </td>
    </tr>
    <tr>
      <td class="tdrow2">Watermark Font:</td>
      <td class="tdrow3">
        Note: Font files reside in <strong>/includes/fonts</strong>, which are also used for VVC Images.<br />
        <select name="watermark_font" style="width: 200px">
        <option value="0" selected="selected">Internal Font</option>
        <option value="1">1 Halter</option>
        <option value="2">2 Abscissa Bold</option>
        <option value="3">3 Acidic</option>
        <option value="4">4 Helvetica-Black Semi-Bold</option>
        <option value="5">5 Activa</option>
        <option value="6">6 Alberta Regular</option>
        <option value="7">7 Alien League</option>
        <option value="8">8 AllHookedUp</option>
        <option value="9">9 Alpha Romanie G98</option>
        <option value="10">10 Opossum</option>
        </select>
      </td>
    </tr>
    <tr>
      <td class="tdrow2">Watermark Text Size (built-in font: 1-6, >6 only for TTF fonts):</td>
      <td class="tdrow3">
        <input type="text" name="watermark_size" value="6" size="3" />
      </td>
    </tr>
    <tr>
      <td class="tdrow2">Watermark Margin Left (X):</td>
      <td class="tdrow3"><input type="text" name="watermark_x" value="" size="4" /></td>
    </tr>
    <tr>
      <td class="tdrow2">Watermark Margin Top (Y):</td>
      <td class="tdrow3"><input type="text" name="watermark_y" value="" size="4" /></td>
    </tr>
    <tr>
      <td class="tdrow2">Watermark Text Color:</td>
      <td class="tdrow3"><input type="text" class="colorpicker" name="watermark_color" value="#000000" size="6" /></td>
    </tr>
    <tr>
      <td class="tdrow1" colspan="2">Scaling Options</td>
    </tr>
    <tr>
      <td class="tdrow2">Keep aspect ratio?<br />
      </td>
      <td class="tdrow3"><input type="checkbox" name="keepratio" value="1" checked="checked" />
        Resize the image to fit within the provided max. width/height limits,
        which may result in non-square image sizes (default: yes).<br />
        If not checked, the image will be resized to fill the full max. width
        and height limits as specified.
      </td>
    </tr>
    </table>
    <table width="100%" border="0" cellpadding="5" cellspacing="0">
    ';
  }

  echo '
    <tr>
      <td class="tdrow2" width="50%">Square-off image?<br />
      </td>
      <td class="tdrow3" width="50%"><input type="checkbox" name="squaredoff" value="1" />
        Resize the image to have the same height and width within the above specified
        max. limits, resulting in left/right or top/bottom borders depending on whether
        the width is greater than the height or vice versa.
      </td>
    </tr>
    <tr>
      <td class="tdrow1" bgcolor="#FCFCFC" colspan="2" align="center">
        <input type="submit" value="Create Thumbnail" />
        <input type="submit" value="Cancel"
         onclick="window.location=\''.REFRESH_PAGE. '&amp;action=' .
         (!empty($fileid)?'displayfileform&amp;fileid='.$fileid:'displaysectionform&amp;sectionid='.$sectionid) .
         '\'; return false;" />
      </td>
    </tr>
    </table>
    </form>';
    EndSection();

  } //dlm_DisplayThumbnailForm


  // ###########################################################################


  // Return an "IMG" tag for an image file residing either in the plugin's
  // "images/" directory (default), or a subfolder specified by parameter
  // "$imagesubfolder".
  // Basefolder is in any case the plugin's "images" directory.
  // Imagename should only be the filename and not include any paths.
  // Function does not check for the actual existence of the file!
  public static function dlm_GetMiscImageLink($imagename,$titletag,$imagesubfolder,$extraAttr='')
  {
    // If not an image, then assume HTML code and return
    if(self::dlm_HasFileImageExt($imagename) == NULL)
    {
      return $imagename;
    }

    if(!empty($imagesubfolder))
    {
      $imagesubfolder = (substr($imagesubfolder, -1) != '/' ? $imagesubfolder . '/' : $imagesubfolder);
    }

    return '<img src="' . self::$imageurl . $imagesubfolder . $imagename . '" ' . $extraAttr .
        ' title="'.$titletag.'" alt="'.$titletag.'" style="vertical-align:middle" />';

  } //dlm_GetMiscImageLink


  // ###########################################################################


  // Get an opening "<a href=" tag back for an image file (must reside
  // in plugin's "images" folder).
  // Closing tag HAS to be added outside of this function manually.
  // Function does not check for the actual existence of the file!
  public static function dlm_GetImageAhref($imagename,$titletag,$imagesubfolder='',$linkextra='')
  {
    if(!empty($imagesubfolder))
    {
      $imagesubfolder = (substr($imagesubfolder, -1) != '/' ? $imagesubfolder . '/' : $imagesubfolder);
    }

    return '<a href="' . self::$imageurl . $imagesubfolder . $imagename . '"' .
        ' title="' . $titletag . '" '.$linkextra.' style="vertical-align: middle;">';

  } //dlm_GetImageAhref


  public static function dlm_GetCurrentFileVersion($fileid)
  {
    global $DB;
    $DB->result_type = MYSQL_ASSOC;
    if($lastver = $DB->query_first('SELECT version FROM '.self::$tbl_file_versions.
                  ' WHERE fileid = %d'.
                  ' ORDER BY versionid DESC LIMIT 1',$fileid))
    {
      return $lastver['version'];
    }
    return false;
  } //dlm_GetCurrentFileVersion


  public static function dlm_GetCurrentFileVersionID($fileid)
  {
    global $DB;
    $DB->result_type = MYSQL_ASSOC;
    if($lastver = $DB->query_first('SELECT versionid FROM '.self::$tbl_file_versions.
                  ' WHERE fileid = %d'.
                  ' ORDER BY versionid DESC LIMIT 1',$fileid))
    {
      return $lastver['versionid'];
    }
    return false;
  } //dlm_GetCurrentFileVersionID


  public static function dlm_GetDelFileVersionLink($categoryid, $sectionid, $fileid, $fileversion, $fileauthor)
  {
    global $userinfo;

    if(self::GetVar('allow_admin') ||
       (NotEmpty(self::GetSetting('enable_author_file_delete')) &&
        self::GetVar('allow_submit') && !empty($fileauthor) && !empty($userinfo['loggedin']) &&
        ($userinfo['username'] == $fileauthor)))
    {
        return '<a class="dlm-link-remove" href="' . RewriteLink('index.php?categoryid='.$categoryid.
          self::GetVar('URI_TAG').'_action=delfileversion'.
          self::GetVar('URI_TAG').'_sectionid='.$sectionid.
          self::GetVar('URI_TAG').'_fileid='.$fileid.
          self::GetVar('URI_TAG').'_versionid='.$fileversion) . '"> ' .
          self::GetPhrase('delete_fileversion') . ' </a>';
    }
    else
    {
      return '';
    }

  } //dlm_GetDelFileVersionLink

  public static function dlm_DeleteFileVersion($fileid, $versionid)
  {
    global $DB, $userinfo;

    // If this is the current version, try and change the currentversion
    $file = $DB->query_first('SELECT author, currentversionid FROM '.self::$tbl_files.' WHERE fileid = %d',$fileid);

    $allowed = !empty($file) && (self::GetVar('allow_admin') ||
               (NotEmpty(self::GetSetting('enable_author_file_delete')) &&
                self::GetVar('allow_submit') && !empty($file['author']) && !empty($userinfo['loggedin']) &&
                ($userinfo['username']) == $file['author']));
    if(!$allowed)
    {
      return false;
    }

    if($file['currentversionid'] == $versionid)
    {
      $DB->result_type = MYSQL_ASSOC;
      $lastver = $DB->query_first('SELECT versionid FROM '.self::$tbl_file_versions.
                                  ' WHERE fileid = %d AND versionid <> %d'.
                                  ' ORDER BY datecreated DESC LIMIT 1',$fileid,$versionid);

      // If it's the only version, don't let them delete it
      if(!isset($lastver['versionid']))
      {
        PrintErrors(array(self::GetPhrase('delete_fileversion_error')), self::GetPhrase('title_delete_error'));
        return false;
      }
      else
      {
        // Update the version id
        $DB->query('UPDATE '.self::$tbl_files.' SET currentversionid = %d WHERE fileid = %d',
             $lastver['versionid'], $fileid);
      }
    }

    // Get version Info
    $filever = $DB->query_first('SELECT filetype, storedfilename, filename'.
                    ' FROM '.self::$tbl_file_versions.
                    ' WHERE fileid = %d AND versionid = %d',
                    $fileid, $versionid);

    if(strlen($filever['storedfilename']))
    {
      $ftpfile = self::$filesdir . $filever['storedfilename'];
      if(is_file($ftpfile))
      {
        @unlink($ftpfile);
      }
    }

    $ftpfile = self::$filesdir . $filever['filename'];
    if(($filever['filetype']=='FTP') && is_file($ftpfile) &&
        ((substr(strtolower($filever['filename']),0,3) != 'www') &&
         (substr(strtolower($filever['filename']),0,4) != 'http')))
    {
      // it's an ftp file, delete it
      @unlink($ftpfile);
    }

    // delete file versions
    $DB->query('DELETE FROM '.self::$tbl_file_versions.' WHERE fileid = %d AND versionid = %d',
               $fileid, $versionid);

    return true;

  } //dlm_DeleteFileVersion


  // Displays a table (table tags are optional) with those file details,
  // that are currently set to "Yes" for display in the plugin layout
  // Settings - except the file's "Description".
  // Also, some details are only shown if the "dateupdated" field has a
  // value (=user upload) to match the default behavior of DLM 1.4.2.
  //
  // Parameters:
  // - "$file" is to be the array containing all file details.
  // - "$defaultlayout" == 0||1 specifying if the details are displayed like
  //   the standard file details (1 per row), otherwise 2 per row.
  // - "$docreatetable" == true will create contents within it's own
  //   <table...> which is the default. Otherwise it will only create
  //   the <td...> entries for an existing table.
  // - "$doclosetable" == true will echo the </table> tag, else not.
  // Output will be in 2 columns (TD's) in any case.
  // Returns true if at least one detail was displayed, else false.
  // TODO: MOVE THIS TO DownloadManager class
  public static function dlm_DisplayFileDetailsTable(&$detailrows, $file, $docreatetable=false,
                           $isembeddable=false, $returnoutput=false)
  {
    global $sdlanguage, $userinfo;

    if(!is_array($file) || empty($file) || ($file['fileid']<1))
    {
      return false;
    }

    $defaultlayout = NotEmpty(self::GetSetting('file_details_rows'));
    if(NotEmpty(self::GetSetting('file_details_ignore_date_updated')))
    {
      $colorflag = '0';
    }
    else
    {
      $colorflag = ($file['dateupdated'] > 0) ? '1' : '0';
    }

    // Collect all relevant file details in arrays
    if(self::GetSetting('display_sections') == 0)
    {
      $details[] = array (
        "labels" => strip_tags(self::GetPhrase('sections')),
        "values" => self::dlm_DisplayFileSections($file['fileid']),
        "coloridx" => 0);
    }

    if(NotEmpty(self::GetSetting('display_filesize')) && isset($file['filesize']) && !$isembeddable)
    {
      $details[] = array (
        "labels" => strip_tags(self::GetPhrase('file_size')),
        "values" => (empty($file['filesize'])?'-':self::dlm_DisplayReadableFilesize($file['filesize'])),
        "coloridx" => 0);
    }

    if((NotEmpty(self::GetSetting('display_version')) == 1) && isset($file['version']) &&
       ($file['version'] != '') && !$isembeddable)
    {
      $details[] = array (
        "labels" => strip_tags(self::GetPhrase('version')),
        "values" => $file['version'],
        "coloridx" => $colorflag);
    }

    if((self::GetSetting('display_author') == 1) && isset($file['author']))
    {
      $details[] = array (
        "labels" => strip_tags(self::GetPhrase('author')),
        "values" => $file['author'],
        "coloridx" => 0);
    }

    if(self::GetSetting('display_download_count') == 1)
    {
      $details[] = array (
        "labels" => ($isembeddable ? strip_tags(self::GetPhrase('dlm_view_count')) : strip_tags(self::GetPhrase('download_count'))),
        "values" => $file['downloadcount'],
        "coloridx" => $colorflag);
    }

    if((self::GetSetting('display_date_added') == 1) && ($file['dateadded'] > 0))
    {
      $details[] = array (
        "labels" => strip_tags(self::GetPhrase('date_added')),
        "values" => DisplayDate($file['dateadded']),
        "coloridx" => $colorflag);
    }

    if((self::GetSetting('display_date_updated') == 1) && ($file['dateupdated'] > 0))
    {
      $details[] = array (
        "labels" => strip_tags(self::GetPhrase('date_updated')),
        "values" => DisplayDate($file['dateupdated']),
        "coloridx" => $colorflag);
    }

    //v2.2.0: display comments count
    if(self::GetSetting('display_comment_counts',false))
    {
      $details[] = array (
        "labels" => strip_tags($sdlanguage['comments']),
        "values" => (empty($file['com_count'])?0:(int)$file['com_count']),
        "coloridx" => $colorflag);
    }
    $result = '';
    // Start creation of own <table...> if wanted
    if(!empty($docreatetable))
    {
      $result .= '<table style="padding: 0px;" border="0" cellspacing="0" cellpadding="0" width="100%" >';
    }

    // Now start creating the output
    if(!empty($details))
    {
      // Display file details ("description" only by plugin setting)
      $idx = 0;
      $detailcount = count($details);
      $alt = '1';
      while($idx < $detailcount)
      {
        if($defaultlayout)
        {
          $result .= '
          <tr class="dlm-detail-row'.$alt.'">
            <td class="dlm-detail-name"><strong>' . $details[$idx]['labels'] . '</strong></td>
            <td class="dlm-detail-value">'. $details[$idx]['values'] . '</td>
          </tr>';
          $idx++;
          $detailrows++;
        }
        else
        {
          $entry1 = ($idx < $detailcount) ? ('<strong>'.$details[$idx]['labels'].'</strong> '.$details[$idx]['values'])  :'&nbsp;';
          $idx++;
          $entry2 = ($idx < $detailcount) ? ('<strong>'.$details[$idx]['labels'].'</strong> '.$details[$idx]['values']) : '&nbsp;';
          $idx++;
          $result .= '
          <tr class="dlm-detail-row'.$alt.'">
            <td class="dlm-detail-name">' . $entry1 . '</td>
            <td class="dlm-detail-value">' . $entry2 . '</td>
          </tr>';
          $detailrows++;
        }
        // Alternate class name for TR
        $alt = ($alt == '1' ? '2' : '1');
      } //while
    }

    // Close table if parameter said so
    if(!empty($doclosetable))
    {
      $result .= '</table>';
    }

    if($returnoutput)
    {
      return empty($result) ? false : $result;
    }
    else
    {
      echo $result;
    }

  } //dlm_DisplayFileDetailsTable


  // ###########################################################################

  // Return either just a <HR> or a plugin settings' defined image file.
  // Optionally a "prefix" may be passed in for e.g. additional <br /> etc.
  public static function dlm_GetSeparator($prefix='', $usesettings=false)
  {
    $prefix = empty($prefix) ? '' : $prefix;
    if($usesettings && NotEmpty(self::GetSetting('file_separator_image')))
    {
      $sep = self::dlm_GetMiscImageLink($usesettings['file_separator_image'],'','misc');
      return (!$sep && !$prefix) ? '' : $prefix.'<div class="dlm-line-separator">'.$sep.'</div>';
    }
    else
    {
      return !$prefix ? "\n" : $prefix;
    }

  } //dlm_GetSeparator


  // ##########################################################################

  public static function dlm_GetFileErrorDescr($error_id)
  {
    $error_id = (int)$error_id;
    $error = '';
    if(!empty($error_id))
    {
      switch ($error_id)
      {
        case 1: //UPLOAD_ERR_INI_SIZE:
          $error = 'The uploaded file exceeds the allowed filesize limit.';
          break;
        case 2: //UPLOAD_ERR_FORM_SIZE:
          $error = 'The uploaded file exceeds the allowed filesize limit.';
          break;
        case 3: //UPLOAD_ERR_PARTIAL:
          $error = 'The uploaded file was only partially uploaded.';
          break;
        case 4: //UPLOAD_ERR_NO_FILE:
          $error = 'No file was uploaded.';
          break;
        case 6: //UPLOAD_ERR_NO_TMP_DIR:
          $error = 'Missing a temporary folder.';
          break;
        case 7: //UPLOAD_ERR_CANT_WRITE:
          $error = 'Failed to write file to disk.';
          break;
        case 8: //UPLOAD_ERR_EXTENSION:
          $error = 'File upload stopped by extension.';
          break;
        default:
          $error = 'Unknown File/Upload Error';
          break;
      }
    }
    return $error;
  } //dlm_GetFileErrorDescr


  // ##########################################################################

  public static function SplitAssociativeArray($b, $t)
  { /* Michael Berndt
       http://www.michael-berndt.de/ie/tux/assoziativen_array_zerteilen.htm
    */
    $a = 0; $i = 0; $z = 0; $n = array();
    foreach($b as $k=>$v)
    {
      if($i%$t)
      {
        $n{$z}[$k]=$v;
      }
      else
      {
        $n{$a}[$k]=$v;
        $z = $a;
        $a++;
      }
      $i++;
    }
    return $n;

  } //SplitAssociativeArray


  public static function dlm_DisplayTagCloud($maxelements=30,$gradation=6,$doOutput=true)
  {
    global $DB, $categoryid;

    /*
    $tags = array();
    if($result = $DB->query('SELECT tags FROM '.self::$tbl_files.' WHERE LENGTH(tags) > 2'))
    {
      while($row = $DB->fetch_array($result,null,MYSQL_ASSOC))
      {
        $row['tags'] = preg_replace('/[\s+]/',' ',$row['tags']);
        $new_tags = preg_split('/[,]/', $row['tags'], -1, PREG_SPLIT_NO_EMPTY);
        foreach($new_tags as $key => $value)
        {
          $value = rtrim(ltrim($value));
          $tags[] = $value;
        }
      }
      $DB->free_result($result);
    }

    if(empty($tags)) return false;

    // Determine the number of occurences per tag:
    $tags = array_count_values($tags);
    // Sort by # and only keep the first $maxelements:
    arsort($tags);
    if(function_exists("array_chunk")) // PHP >= 4.2.0
    {
      $tags = array_chunk($tags, $maxelements, true);
    }
    else
    {
      $tags = SplitAssociativeArray($tags, $maxelements);
    }
    $tags = $tags[0];

    // Scale logarithmitically the count of each tag:
    foreach ($tags as $tagkey => $count)
    {
      $tags[$tagkey] = (int)round($count = 100 * log($count + 2));
    }
    */

    #v2.2.0: converted to use core tag cloud display method
    #v2.3.0: moved file-level "tags" to core "tags" table
    require_once(SD_INCLUDE_PATH.'class_sd_tags.php');
    SD_Tags::$maxentries   = 30;
    SD_Tags::$plugins      = self::GetVar('PLUGINID');
    SD_Tags::$tags_title   = DownloadManagerTools::GetPhrase('dlm_tags');
    SD_Tags::$targetpageid = $categoryid;
    SD_Tags::$tag_as_param = self::GetVar('HTML_PREFIX').'_searchby=tags'.self::GetVar('URI_TAG').'_searchtext';

    $result = '
    '.SD_Tags::DisplayCloud(false/*,$tags*/).'
    ';

    if(empty($doOutput)) return $result;

    echo $result;

  } //dlm_DisplayTagCloud


  // ##########################################################################

  public static function UpdateFile($sectionid, $fileid, $versionid)
  {
    global $DB, $categoryid, $userinfo, $sdlanguage;

    $errors = array();

    if(!CheckFormToken())
    {
      RedirectPage(RewriteLink('index.php?categoryid='.$categoryid),'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      return;
    }

    // clear variables
    $filedata  = '';
    $filename  = '';
    $filesize  = '';
    $filetype  = '';
    $image     = '';
    $thumbnail = '';

    // File Related
    $activated     = empty($_POST[self::GetVar('HTML_PREFIX').'_activated'])?0:1;
    $activated     = self::GetVar('allow_admin') ? $activated : (NotEmpty(self::GetSetting('auto_approve_uploads'))?1:0);

    $title         = GetVar(self::GetVar('HTML_PREFIX').'_title','','string',true,false);
    $title         = preg_replace('/[\s+]/',' ',ltrim(rtrim($title)));
    $author        = GetVar(self::GetVar('HTML_PREFIX').'_author','','string',true,false);
    $description   = GetVar(self::GetVar('HTML_PREFIX').'_description','','string',true,false);
    $description   = ltrim(rtrim($description));
    $tags          = GetVar(self::GetVar('HTML_PREFIX').'_tags','','string',true,false);
    $tags          = preg_replace('/[\s+]/',' ',ltrim(rtrim($tags)));
    $thumbcreate   = GetVar(self::GetVar('HTML_PREFIX').'_createthumb',0,'natural_number',true,false);
    $standalone    = GetVar(self::GetVar('HTML_PREFIX').'_standalone',0,'natural_number',true,false);
    $deletethumbnail = GetVar(self::GetVar('HTML_PREFIX').'_deletethumbnail',0,'natural_number',true,false);
    $deleteimage   = GetVar(self::GetVar('HTML_PREFIX').'_deleteimage',0,'natural_number',true,false);
    $thumbnail     = isset($_FILES[self::GetVar('HTML_PREFIX').'_thumbnail'])?$_FILES[self::GetVar('HTML_PREFIX').'_thumbnail']:null;
    $image         = isset($_FILES[self::GetVar('HTML_PREFIX').'_image'])?$_FILES[self::GetVar('HTML_PREFIX').'_image']:null;
    /* Currently admin-only:
    $audio_class   = GetVar(self::GetVar('HTML_PREFIX').'_audio_class', '', 'html', true, false);
    $video_class   = GetVar(self::GetVar('HTML_PREFIX').'_video_class', '', 'html', true, false);
    $media_autoplay= GetVar(self::GetVar('HTML_PREFIX').'_media_autoplay', 0, 'natural_number', true, false);
    $media_loop    = GetVar(self::GetVar('HTML_PREFIX').'_media_loop', 0, 'natural_number', true, false);
    */
    $old_sectionid = GetVar(self::GetVar('HTML_PREFIX').'_oldsection',$sectionid,'whole_number',true,false);
    if(!empty($_POST[self::GetVar('HTML_PREFIX').'_sectionid']) && NotEmpty(self::GetSetting('show_upload_section_selection')))
    {
      $sectionid = (int)$_POST[self::GetVar('HTML_PREFIX').'_sectionid'];
    }

    // Version Related
    $filestore     = GetVar(self::GetVar('HTML_PREFIX').'_filestore',0,'natural_number',true,false);
    if(!self::GetVar('allow_admin') && !NotEmpty(self::GetSetting('edit_file_storage_change')))
    {
      $filestore = self::GetSetting('default_upload_location');
    }
    // Both $files and $fileurl variables are arrays!
    $files   = $_FILES[self::GetVar('HTML_PREFIX').'_file'];
    $fileurl = GetVar(self::GetVar('HTML_PREFIX').'_url',null,'array',true,false);
    $version = (!empty($fileid) ? GetVar(self::GetVar('HTML_PREFIX').'_version',self::GetVar('DEFAULT_VERSION'),'string',true,false) : self::GetVar('DEFAULT_VERSION'));

    $isnewfile = empty($fileid) || ($fileid < 1);
    /*
    if((strlen($title)<5) || (strlen($description)<5))
    {
      $errors[] = 'Title and Description have to be at least 5 characters long';
    }
    */
    require_once(SD_INCLUDE_PATH.'class_sd_tags.php');
    // Multiple-file upload (up to 10 max)
    $firstfile = true; // First found file is the "main" file
    $uploaded  = 0;
    $filescount = count($files['name'])>10?10:count($files['name']); //Array!!!
    for($i = 0; $i < $filescount; $i++)
    {
      // Was a file uploaded OR a URL specified?
      $embeddable = 0;
      $isfile  = (isset($files['name'][$i]) && strlen($files['name'][$i]) && ( (int) $files['size'][$i] > 0));
      $isurl   = (isset($fileurl[$i]) && ((substr(strtolower($fileurl[$i]),0,4)=='http') || (substr(strtolower($fileurl[$i]),0,3)=='www')));
      $file_ok = $isfile || $isurl;
      if($file_ok && $isfile)
      {
        // Security Check
        if(!@is_uploaded_file($files['tmp_name'][$i]))
        {
          $file_ok = false;
          if(function_exists('watchdog'))
          {
            watchdog('Watchdog', 'Possible malicious file upload attempt (p'.self::GetVar('PLUGINID').'): '.
            'User: '.htmlspecialchars($userinfo['username']).
            ' Filename: '.htmlspecialchars($files['name'][$i]), WATCHDOG_ERROR);
          }
        }

        if($file_ok && !self::dlm_CheckFileExtension($files['name'][$i], self::GetSetting('allowed_file_extensions')))
        {
          // Filetype NOT allowed, set error
          $errors[] = self::GetPhrase('error_invalid_file_ext').' (' . $files['name'][$i] . ')';
          $file_ok = false;
        }
      }
      else
      if($file_ok && $isurl)
      {
        if(NotEmpty(self::GetSetting('check_user_remote_link')) &&
           !self::dlm_CheckURI($fileurl[$i]))
        {
          $errors[] = self::GetPhrase('user_url_invalid'); // doesn't accept links without http!
          $file_ok = false;
        }
        if($file_ok)
        {
          $embeddable = ((DownloadManagerTools::dlm_LinkAsMedia($fileurl[$i])==$fileurl[$i]) ? 1 : 0);
        }
        /*
        if(!self::dlm_CheckFileExtension(basename($fileurl[$i]), self::GetSetting('allowed_file_extensions')))
        {
          // Filetype NOT allowed, set error
          $errors[] = self::GetPhrase('error_invalid_file_ext') . '  (' . $fileurl[$i] . ')';
          $file_ok = false;
        }
        */
        //SD343: "urlencode"
        //$fileurl[$i] = str_replace(' ','%20',$fileurl[$i]);
        $fileurl[$i] = urlencode($fileurl[$i]);
      }

      // Init vars
      $filetype = '';
      $filepath = '';
      $filesize = 0;
      $storedfilename = '';
      $filedata = '';

      // Uploaded file still valid?
      if($file_ok && $isurl)
      {
        $filename = $fileurl[$i];
        $filetype = 'FTP';
      }
      else
      if($file_ok && $isfile)
      {
        // Check file errors
        if(isset($files['error'][$i]) && $files['error'][$i] != 0 && $files['error'][$i] != 4)
        {
          if($files['error'][$i] <= 2)
          {
            $errors[] = self::GetPhrase('file_too_big') . ' ' . $files['name'][$i];
            $file_ok = false;
          }
          else
          {
            $errors[] = self::GetPhrase('file_upload_error') . ' - ' . self::dlm_GetFileErrorDescr($files['error'][$i]) . ' ' . $files['name'][$i];
            $file_ok = false;
          }
        }
        else
        {
          $filename = $files['name'][$i];
          $filetype = $files['type'][$i];
          $filepath = $files['tmp_name'][$i];
          $filesize = $files['size'][$i];

          $file_ok = false;

          // Is file an embeddable media file with a know file extension?
          $embeddable = 0;
          if($ext = DownloadManagerTools::dlm_GetFileExtension($filename))
          {
            $embeddable = in_array($ext, DownloadManagerTools::$KnownAudioTypes) ||
                          in_array($ext, DownloadManagerTools::$KnownVideoTypes) ? 1 : 0;
          }

          // In any case first move the file before trying
          // to process it (keyword: "open_basedir" directive)!
          $tmp_file = CreateGuid() . self::GetVar('DEFAULT_EXT');
          $storedfilename = $tmp_file;
          $file_ok = @move_uploaded_file($filepath, self::$filesdir.$storedfilename);
          if($file_ok)
          {
            if($filestore == 1) // Filesystem
            {
              if($file_ok)
              {
                if(!empty($settings['default_chmod'])) #sd343: fixed setting name
                {
                  $chm = substr($settings['default_chmod'],0,4);
                  // Only if 3 digits, convert to octal value
                  if(strlen($chm) == 3)
                  {
                    $chm = intval($chm, 8);
                  }
                  @chmod(self::$filesdir.$storedfilename, (empty($chm) ? 0644 : $chm));
                }
                if(NotEmpty(self::GetSetting('default_owner')))
                {
                  @chown(self::$filesdir.$storedfilename,self::GetSetting('default_owner'));
                }

              }
              else
              {
                $file_ok = false;
                $errors[] = self::GetPhrase('file_upload_error');
              }

              $filedata = '';
            }
            else // MySql
            {
              if(!function_exists('file_get_contents'))
              {
                if(FALSE !== ($fhandle = @fopen(self::$filesdir.$storedfilename, "rb")))
                {
                  if(FALSE === ($filedata = @fread($fhandle, $filesize)))
                  {
                    $filedata = '';
                  }
                  @fclose($fhandle);
                }
              }
              else
              {
                //$filedata = addslashes($filedata);
                $filedata = @file_get_contents(self::$filesdir.$storedfilename);
              }
              $filedata = ($filedata==''?'':'0x' . bin2hex($filedata));
              $storedfilename = '';
            }
          }
        }
      } //$file_ok

      if($file_ok)
      {
        // INSERT new file or UPDATE existing file
        if($isnewfile)
        {
          // Insert (first!) file in files table
          if($firstfile)
          {
            $DB->query('INSERT INTO '.self::$tbl_files.' (activated, title, description, '.
                  'author, tags, dateadded, datestart, dateend, embedded_in_details,
                  access_groupids, access_userids) VALUES ' .
              "('$activated', '" .
              $DB->escape_string($title) . "', '" .
              $DB->escape_string($description) . "', '".
              $DB->escape_string($author) . "', '".
              $DB->escape_string($tags) . "', '".
              TIME_NOW . "', '" . TIME_NOW . "', 0, ".$embeddable.",
              '', '')");

            $fileid = $DB->insert_id();
            if(!empty($fileid))
            {
              SD_Tags::StorePluginTags(self::GetVar('PLUGINID'), $fileid, $tags); //SD370
            }
          }
          $uploaded++;
          // Create a new version entry for uploaded file
          $DB->query('INSERT INTO '.self::$tbl_file_versions."
            (fileid,  version,  file,      filename,  storedfilename,  filesize,  filetype,  datecreated, is_embeddable_media, duration, lyrics, ipaddress, userid)
            VALUES
            (%d,      '%s',     '%s',      '%s',      '%s',            %d,        '%s',      %d,          %d,   0, '', '%s',   %d)",
             $fileid, $version, $filedata, $filename, $storedfilename, $filesize, $filetype, TIME_NOW,     $embeddable, USERIP, $userinfo['userid']);

          $versionid = $DB->insert_id();

          // Only process first uploaded file any further
          if($firstfile)
          {
            $firstfile = false;
            $DB->query('UPDATE '.self::$tbl_files.' SET currentversionid = %d where fileid = %d', $versionid, $fileid);

            // Insert the section link
            $DB->ignore_error = true; //2011-11-14 - ignore key error
            $DB->query('INSERT INTO '.self::$tbl_file_sections.' VALUES(%d, %d)', $fileid, $sectionid);
            $DB->ignore_error = false;
          }
        }
        else
        {
          // Now we have a new file so create a new version
          if(!empty($filename))
          {
            $DB->query('INSERT INTO '.self::$tbl_file_versions."
              (fileid,  version,  file,      filename,  storedfilename,  filesize,  filetype,  datecreated, is_embeddable_media, duration, lyrics, ipaddress, userid)
              VALUES
              (%d,      '%s',     '%s',      '%s',      '%s',            %d,        '%s',      %d,          %d,   0, '', '%s',   %d)",
               $fileid, $version, $filedata, $filename, $storedfilename, $filesize, $filetype, TIME_NOW,     $embeddable, USERIP, $userinfo['userid']);

            $versionid = $DB->insert_id();
            $DB->query('UPDATE '.self::$tbl_files.' SET currentversionid = %d WHERE fileid = %d',$versionid,$fileid);
          }
          $uploaded++;

          // Update the main file table with the data from the form
          $DB->query('UPDATE '.self::$tbl_files." SET activated = %d,title = '%s'".
               // author change only allowed for admins:
               (self::GetVar('allow_admin') ? (", author = '".$DB->escape_string($author)."'") : '').
               ",description = '%s', dateupdated = ".time().", standalone = %d".
               ' WHERE fileid = %d',
               $activated,
               $DB->escape_string($title),
               $DB->escape_string($description),
               $standalone, $fileid);
        }
      } // errorcheck

    } //for

    if($isnewfile && empty($uploaded))
    {
      $errors[] = 'No files were uploaded.';
    }
    else
    if(!empty($fileid))
    {
      if($fileid && $sectionid && $old_sectionid && ($old_sectionid != $sectionid))
      {
        $DB->query('DELETE FROM '.self::$tbl_file_sections.' WHERE fileid = %d AND sectionid = %d',$fileid, $old_sectionid);
        $DB->query('INSERT INTO '.self::$tbl_file_sections.' (fileid, sectionid) VALUES (%d, %d)',$fileid, $sectionid);
      }
      // Update the main file table with the data from the form
      $DB->query('UPDATE '.self::$tbl_files.' SET activated = %d,'
          ."title = '%s',"
          ."tags  = '%s',"
          // author change only allowed for admins:
          .(self::GetVar('allow_admin') ? ("author = '".$DB->escape_string($author)."', ") : '')
          // Do not change "dateupdated" if editing is done by admin
          .(!empty($userinfo['adminaccess']) && ($author != $userinfo['username'])? '' : ' dateupdated = '.time().',')
          ." description = '%s', "
          ." standalone = %d".
          ' WHERE fileid = %d',
          $activated,
          $DB->escape_string($title),
          $DB->escape_string($tags),
          $DB->escape_string($description),
          $standalone, $fileid);

      SD_Tags::StorePluginTags(self::GetVar('PLUGINID'), $fileid, $tags); //SD371
      if(strlen($version))
      {
        if($versionid = self::dlm_GetCurrentFileVersionID($fileid))
        {
          $DB->query('UPDATE '.self::$tbl_file_versions." SET version = '%s' where versionid = %d",
                     $version, $versionid);
        }
      }
    }

    // Have file(s) been uploaded without errors?
    if(!count($errors))
    {
      // Check if an image was uploaded
      if(!empty($image) && (!empty($image['name'])))
      {
        if(is_uploaded_file($image['tmp_name']) && self::dlm_CheckImageFile($image, $errors, $extension))
        {
          $imagename = $fileid . '.' . $extension;
          self::dlm_ReplaceFileImage('image', $fileid, self::$imagesdir, $imagename);
          $file_ok = //!is_executable($image['tmp_name']) && // no server-executables allowed!
                     @move_uploaded_file($image['tmp_name'], self::$imagesdir . $imagename);
          if($file_ok)
          {
            chmod(self::$imagesdir . $imagename, 0644);
          }
          else
          {
            $errors[] = self::GetPhrase('file_upload_error') . " ($filename)";
          }
        }
      }
      else
      {
        if($deleteimage)
        {
          self::dlm_ReplaceFileImage('image', $fileid, self::$imagesdir, '', true);
        }
      }

      // Automatic thumbnail creation for new image uploads?
      if(!empty($uploaded) && $thumbcreate && ($Ext = self::dlm_HasFileImageExt($filename)))
      {
        $thumbfile = 'tb_' . $fileid . '.' . $Ext;
        $thumbwidth = NotEmpty(self::GetSetting('thumbnail_display_width')) ? self::GetSetting('thumbnail_display_width') : 100;
        $imgfile = self::$filesdir . $tmp_file;
        if(self::dlm_DoCreateThumbnailByParams($fileid,0,$imgfile,self::$imagesdir,
                                                $thumbfile, $thumbwidth, $thumbwidth))
        {
          $DB->query('UPDATE '.self::$tbl_files." SET thumbnail = '%s' where fileid = %d",
                     $DB->escape_string($thumbfile),$fileid);
        }
        else
        {
          $errors[] = self::GetPhrase('file_thumbnail_error') . " ($filename)";
        }
      }
      else
      // Check if a thumbnail was uploaded
      if(!empty($thumbnail['name']) && !empty($thumbnail['size']))
      {
        if(is_uploaded_file($thumbnail['tmp_name']) && self::dlm_CheckImageFile($thumbnail, $errors, $extension))
        {
          $thumbname = 'tb_' . $fileid . '.' . $extension;
          self::dlm_ReplaceFileImage('thumbnail', $fileid, self::$imagesdir, $thumbname);
          if(@move_uploaded_file($thumbnail['tmp_name'], self::$imagesdir . $thumbname))
          {
            chmod(self::$imagesdir . $thumbname, 0644);
          }
        }
      }
      else
      {
        if($deletethumbnail)
        {
          self::dlm_ReplaceFileImage('thumbnail', $fileid, self::$imagesdir, '', true);
        }
      }

    }

    if(!count($errors))
    {
      // Email Notification as with the regular file submittal
      $email = self::GetSetting('file_notification_email');
      if(strlen($email))
      {
        // obtain emails
        $getemails = str_replace(',', ' ', $email);            // get rid of commas
        $getemails = preg_replace('#\s\s+#', ' ', $getemails); // get rid of extra spaces
        $getemails = trim($getemails);                         // then trim
        $emails    = explode(' ', $getemails);

        $fullname = 'Download Manager';

        $author = $userinfo['username'];
        if($uploaded)
        {
          $subject  = $author . ' ' . self::GetPhrase('user_submit_new_subject');
          $message  = self::GetPhrase('user_submit_new_message')."\n\n";
        }
        else
        {
          $subject  = $author . self::GetPhrase('user_submit_change_subject');
          $message  = self::GetPhrase('user_submit_change_message')."\n\n";
        }
        $message .= "Location: http://".strip_tags($_SERVER['HTTP_HOST'])."/admin\n\n";
        $message .= "Author: " . $author . "\n";
        $message .= "Title: " . $title . " ($filename) \n";
        $message .= "Date: " . DisplayDate(TIME_NOW) . "\n\n";
        $message .= "Description: " . htmlentities($description, ENT_QUOTES)."\n";

        for($i = 0; $i < count($emails); $i++)
        {
          if(!IsValidEmail($emails[$i]))
          {
            $errors[] = self::GetPhrase('invalid_email_address').$emails[$i];
          }
          else
          {
            if(!SendEmail($emails[$i], $subject, $message))
            {
              $errors[] = self::GetPhrase('email_notification_failed').$emails[$i];
            }
          }

        }
        if(count($errors) != 0)
        {
          PrintErrors($errors, self::GetPhrase('title_notification_error'));
        }
      } //email notification

      self::dlm_DisplayMenu($sectionid);

      echo '<br /><div style="font-size:11pt; padding-left: 0px;">'.self::GetPhrase('file_submitted').'</div>';

        // Check for auto approval
      if(!NotEmpty(self::GetSetting('auto_approve_uploads')) && !$activated)
      {
        echo '<br /><div style="font-size:11pt; padding-left: 0px;">'.self::GetPhrase('needs_approval').'</div>';
      }

      // Back to section - link
      // SD343: if file is not active, link back to section, not file!
      $backlink = '<a href="' . RewriteLink('index.php?categoryid='.$categoryid.
                  self::GetVar('URI_TAG').'_sectionid='.$sectionid).
                  (!$activated ? '' : self::GetVar('URI_TAG').'_fileid='.$fileid) .
                  '">' . '&laquo; '.self::GetPhrase('back').'</a>';
      echo '<div class="dlm-back-link">' . $backlink . '</div>';
      return true;
    }
    else
    {
      self::dlm_DisplayMenu($sectionid);
      self::PrintErrors($errors, self::GetPhrase('title_upload_error'));
      return false;
    }

  } //UpdateFile


  public static function dlm_SendHeaderError($err_code)
  {
    global $DB, $categoryid;

    $url = RewriteLink('index.php?categoryid='.$categoryid.
                       str_replace('&amp;','&',self::GetVar('URI_TAG')).
                       '_error='.$err_code);
    Header('Location: '.$url);
    if(isset($DB) && method_exists($DB, 'close'))
    {
      $DB->close();
    }
    exit();
  } //dlm_SendHeaderError


  public static function dlm_CheckFileExtension($filename,$allowedexts)
  {
    $file_ext_allow = FALSE;
    if(!empty($allowedexts))
    {
      // remove blanks from exts first
      $allowedexts = str_replace(' ','',$allowedexts);
      $file_types_array = explode(',',$allowedexts);
      $pos = strrpos($filename,'.');
      if($pos===false)
      {
        // No "." in the filename so we'll let it through
        return true;
      }
      else
      {
        $filenameext = strtolower(substr($filename, $pos+1));
        for($x = 0, $ftc = count($file_types_array); $x < $ftc; $x++)
        {
          if($filenameext == strtolower($file_types_array[$x]))
          {
            return true;
          }
        } //for
        return false;
      }
    }
    else
    {
      // If none set, allow all files
      return true;
    }
  } //dlm_CheckFileExtension


  public static function dlm_CreateUnixTimestamp($stringtime)
  {
    return sd_CreateUnixTimestamp($stringtime);
  } // dlm_CreateUnixTimestamp


  // ############################ READABLE FILESIZE #############################

  public static function dlm_DisplayReadableFilesize($filesize)
  {
    $kb = 1024;    // Kilobyte
    $mb = 1048576; // Megabyte

    if($filesize < $kb)
    {
      $size = $filesize . ' B';
    }
    else if($filesize < $mb)
    {
      $size = round($filesize/$kb,2) . ' KB';
    }
    else
    {
      $size = round($filesize/$mb,2) . ' MB';
    }
    return (!isset($size) OR ($size==' B'))?(strlen(self::GetPhrase('unknown'))?self::GetPhrase('unknown'):'-'):$size;
  } // dlm_DisplayReadableFilesize


  // ############################## CREATE A GUID ###############################

  public static function dlm_CheckURI($url)
  {
    // "filter_var" requires PHP 5.2 and has bugs with 5.2.x or so :(
    // if(filter_var($uri, FILTER_VALIDATE_URL) !== false)

    // From PEAR:validate package:
    if (preg_match(
             '&^(?:([a-z][-+.a-z0-9]*):)?                              # 1. scheme
              (?://                                                    # authority start
              (?:((?:%[0-9a-f]{2}|[-a-z0-9_.!~*\'();:\&=+$,])*)@)?     # 2. authority-userinfo
              (?:((?:[a-z0-9](?:[-a-z0-9]*[a-z0-9])?\.)*[a-z](?:[a-z0-9]+)?\.?)  # 3. authority-hostname OR
              |([0-9]{1,3}(?:\.[0-9]{1,3}){3}))                        # 4. authority-ipv4
              (?::([0-9]*))?)                                          # 5. authority-port
              ((?:/(?:%[0-9a-f]{2}|[-a-z0-9_.!~*\'():@\&=+$,;])*)*/?)? # 6. path
              (?:\?([^#]*))?                                           # 7. query
              (?:\#((?:%[0-9a-f]{2}|[-a-z0-9_.!~*\'();/?:@\&=+$,])*))? # 8. fragment
              $&xi', $url, $matches))
    {
      return true;
    }
    return false;
  }

  public static function dlm_CreateGuidForFolder($foldername, $ext)
  {
    // Both $foldername and $ext MUST be filled!
    if(empty($foldername) || empty($ext))
    {
      return CreateGuid();
    }
    $success = false;
    while(!$success)
    {
      $guid = CreateGuid();
      $success = !is_file($foldername.$guid.$ext);
    }
    return $guid;
  } // dlm_CreateGuidForFolder


  // ################################# GET MENU ##################################

  public static function dlm_GetMenu($sectionid, $currsectionid)
  {
    global $DB, $categoryid;

    $section = array();
    if($getsection = $DB->query('SELECT sectionid, parentid, name, thumbnail'.
                                ' FROM '.self::$tbl_sections.
                                ' WHERE sectionid = %d', $sectionid))
    {
      $section = $DB->fetch_array($getsection,null,MYSQL_ASSOC);
    }
    while($sectionid > 1)
    {
      $sectionid = self::dlm_GetMenu($section['parentid'], $currsectionid);
    }

    // If display of section thumbnails is activated and an image is
    // defined and exists, then display it in front of the section name
    if((self::GetSetting('show_section_thumbnail') != 1) || (strlen($section['thumbnail'])==0))
    {
      echo '<span class="dlm-sections-link"> &raquo; </span>';
    }
      else
    {
      echo ' <img class="dlm-sections-image" alt="" width="16" height="16" src="' . self::$imageurl . $section['thumbnail'] . '" /> ';
    }
    //v2.2.0: SEO section links
    if(self::$seo_enabled)
    {
      $link = DownloadManagerTools::RewriteSectionLink(RewriteLink('index.php?categoryid='.$categoryid),
                                                       $section['sectionid'], $section['name']);
    }
    else
    {
      $link = RewriteLink('index.php?categoryid='.$categoryid.self::GetVar('URI_TAG').'_sectionid='.$section['sectionid']);
    }

    echo '<a class="dlm-sections-link" href="' . $link .'">'.$section['name'].'</a>';

    return $sectionid;
  } //dlm_GetMenu


  // ############################### DISPLAY MENU ################################

  public static function dlm_DisplayMenu($sectionid, $action='')
  {
    global $DB, $categoryid, $userinfo;

    if(!empty($sectionid))
    {
      // display the sections menu
      if(NotEmpty(self::GetSetting('display_sections')))
      {
        echo (NotEmpty(self::GetSetting('display_sections')) ? self::GetPhrase('sections') : '');
        self::dlm_GetMenu($sectionid, $sectionid);
      }
    }
  } //dlm_DisplayMenu


  public static function dlm_DisplayFileSections($fileid)
  {
    // Display the names of the sections in which the given file shows up
    global $DB, $userinfo;

    $sectionnames = '';
    $fileid = (isset($fileid) && ctype_digit($fileid)) ? (int)$fileid : 0;
    if(!empty($fileid))
    {
      $ug = $userinfo['usergroupid'];
      $uid = $userinfo['userid'];
      if($sections = $DB->query('SELECT name FROM '.self::$tbl_sections.' s
         INNER JOIN '.self::$tbl_file_sections.' fs ON fs.sectionid = s.sectionid
         WHERE fs.fileid = %d AND s.activated = 1
         AND ((s.access_groupids = "") OR (s.access_groupids IS NULL) OR (s.access_groupids LIKE "%|'.$ug.'|%"))
         AND ((s.access_userids  = "") OR (s.access_userids  IS NULL) OR (s.access_userids  LIKE "%|'.$uid.'|%"))
         ORDER BY name',$fileid))
      {
        while($section = $DB->fetch_array($sections,null,MYSQL_ASSOC))
        {
          $sectionnames .= $section['name'] . ', ';
        }
        $DB->free_result($sections);
        if(!empty($sectionnames))
        {
          $sectionnames = substr($sectionnames,0,-2);
        }
      }
    }
    return $sectionnames;
  } // dlm_DisplayFileSections


  // Returns the query for figuring out if this file is available for download
  // (which may depend on the global- or user-download-count for that file)
  public static function dlm_ActivatedQuery($username)
  {
    global $DB;

    $result = '(f.activated = 1) AND (f.datestart <= UNIX_TIMESTAMP())'.
              ' AND (f.dateend = 0 OR f.dateend > UNIX_TIMESTAMP())';
    if(NotEmpty(self::GetSetting('allow_per_user_limits')) && strlen($username))
    {
      //SD 2013-08-02: fixed: sub-select was on wrong table, causing SQL error
      return $result . ' AND
          ((f.maxdownloads = 0)
           OR (f.maxtype = 0 AND (f.downloadcount < f.maxdownloads))
           OR (f.maxtype = 1 AND
               (SELECT COUNT(f2.fileid) FROM '.self::$tbl_file_downloads." f2
                WHERE f2.fileid = f.fileid
                AND username = '".$DB->escape_string($username)."') < f.maxdownloads))";
    }
    else
    {
      return $result .
         ' AND (f.maxdownloads = 0 OR (f.maxtype = 0 AND f.downloadcount < f.maxdownloads))';
    }
  } // dlm_ActivatedQuery


  public static function dlm_readfile_chunked($filename,$retbytes=true)
  {
     $chunksize = 32768; // how many bytes per chunk
     $buffer = '';
     $cnt =0;

     if(false !== ($handle = @fopen($filename, 'rb')))
     {
       while(!feof($handle))
       {
         if($buffer=@fread($handle, $chunksize))
           echo $buffer;
         else
           break;
         if($retbytes)
         {
           $cnt += strlen($buffer);
         }
       }
       $status = @fclose($handle);
       if($retbytes && $status)
       {
         return $cnt; // return num. bytes delivered like readfile() does.
       }

       return $status;
       }
     else
     {
       return false;
     }
  } // dlm_readfile_chunked


  public static function dlm_HasAccess($item)
  { // Note: "$item" is expected to have both "access_groupids" and "access_userids"
    //       originating from either a File or Section entry of the DLM
    global $DB, $userinfo;

    // No access if user has no group at all or is banned
    if(empty($userinfo['usergroupid']) || !empty($userinfo['banned']))
    {
      return false;
    }

    // Admins always have access
    if(!empty($userinfo['adminaccess']) || @in_array(self::GetVar('PLUGINID'), $userinfo['pluginadminids']))
    {
      return true;
    }

    // Check item's permissions on group-/user-level
    if( ( !empty($item['access_groupids']) && @!in_array($userinfo['usergroupid'], @explode('|',$item['access_groupids'])) )
        OR
        ( !empty($item['access_userids'])  && @!in_array($userinfo['userid'],      @explode('|',$item['access_userids'])) ) )
    {
      return false;
    }

    return true;
  } //dlm_HasAccess

} //end of class DownloadManagerTools

} // defined()-Check - never remove!!!
