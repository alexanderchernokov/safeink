<?php
// +---------------------------------------------+
// | Download Manager plugin for Subdreamer      |
// +---------------------------------------------+
// | v2.2.0, September 2013                      |
// | Maintainer: 2007-2013 tobias                |
// | This version requires Subdreamer 3.6+!      |
// +---------------------------------------------+

// This file has to be included from "dlm_downloads.php"!
if(!defined('IN_PRGM') || !isset($dlm_currentdir) || !function_exists('GetPluginID'))
{
  return false;
}

// ############################################################################
// Include required class files
// ############################################################################
// class DownloadManagerTools (only static access, no object instance)
require_once(ROOT_PATH . 'plugins/'.$dlm_currentdir.'/class_dlm_tools.php');
require_once(SD_INCLUDE_PATH.'class_sd_tags.php');

// ############################################################################

if(!defined('DLM_LOADED'))
{
  define('DLM_LOADED', true);


class DownloadManager
{
  var $DLM_Init = false;

  public  $ACTION        = '';
  public  $sectionid     = null;
  public  $fileid        = null;
  public  $versionid     = -1;
  public  $categoryid    = 0;
  public  $file_arr      = array();
  public  $section_arr   = array();
  public  $error_arr     = array();

  public  $IsSiteAdmin   = false;
  public  $IsAdmin       = false;
  public  $IsModerator   = false;
  public  $GroupCheck    = '';
  public  $user_report_perm = false;
  public  $user_report_perm_msg = false;

  public  $cache_on      = false;
  public  $seo_cache     = false;
  public  $seo_enabled   = false;
  public  $seo_redirect  = true;
  public  $dlm_cache     = false;

  private $currentpage   = 0;
  private $currenturl    = '';
  private $pageurl       = '';
  private $ordertype     = '';
  private $pagesize      = 0;
  private $searchby      = '';
  private $searchtext    = '';
  private $allow_bbcode  = false;
  public  $seo_detected  = false;
  public  $seo_section_detected = false;

  // Important values (retrieve from DLMT class)
  private $DLM_PATH      = '';
  private $GETFILE       = '';
  private $HTML_PREFIX   = '';
  private $pluginid      = 0;
  private $URI_TAG       = '';

  function DownloadManager()
  {
    // stays empty for now, call Init() instead!
  }

  // #########################################################################

  public function Init($dlm_pluginid = 0)
  {
    global $bbcode, $mainsettings;

    // ################# Initialize static class object ######################
    DownloadManagerTools::DownloadManagerToolsInit($dlm_pluginid);

    if($this->DLM_Init = NotEmpty(DownloadManagerTools::GetVar('PLUGINID')))
    {
      // Copy over values from DownloadManagerTools class:
      $this->DLM_PATH    = DownloadManagerTools::GetVar('PATH');
      $this->GETFILE     = DownloadManagerTools::GetVar('GETFILE');
      $this->HTML_PREFIX = DownloadManagerTools::GetVar('HTML_PREFIX');
      $this->pluginid    = DownloadManagerTools::GetVar('PLUGINID');
      $this->URI_TAG     = DownloadManagerTools::GetVar('URI_TAG');
      $this->ACTION      = GetVar($this->HTML_PREFIX.'_action', '', 'string');

      $this->cache_on    = DownloadManagerTools::$cache_on;
      $this->seo_enabled = DownloadManagerTools::$seo_enabled;

      $this->allow_bbcode = isset($bbcode) && ($bbcode instanceof BBCode) &&
                            !empty($mainsettings['allow_bbcode']);
    }

    return $this->DLM_Init;
  } //Init

  // #########################################################################

  public function InitFrontpage($checkCache=true) //v2.2.0: called from header.php
  {
    // Init() must have been called previously
    if(!$this->DLM_Init) return false;

    global $DB, $categoryid, $mainsettings, $SDCache, $userinfo, $usersystem,
           $pages_md_arr, $sd_url_params, $sd_variable_arr,
           $mainsettings_modrewrite, $mainsettings_tag_results_page;

    $result = true;

    if($page = $DB->query_first('SELECT categoryid FROM {pagesort}'.
                                " WHERE pluginid = '%d'".
                                ' AND categoryid <> %d',
                                $this->pluginid, $mainsettings_tag_results_page))
    {
      $this->categoryid = (int)$page['categoryid'];
    }
    else
    {
      if(defined('IN_ADMIN') || empty($categoryid))
      {
        $this->categoryid = $categoryid = 1;
      }
    }
    $this->pageurl = RewriteLink('index.php?categoryid='.$this->categoryid);
    $this->currenturl = $this->pageurl;

    $this->IsSiteAdmin = !empty($userinfo['adminaccess']) && !empty($userinfo['loggedin']) && !empty($userinfo['userid']);
    $this->IsAdmin     = (!empty($userinfo['pluginadminids'])    && @in_array($this->pluginid, $userinfo['pluginadminids']));
    $this->IsModerator = (!empty($userinfo['pluginmoderateids']) && @in_array($this->pluginid, $userinfo['pluginmoderateids']));

    // If NOT a site admin, build an extra WHERE condition for checking view permission
    $this->GroupCheck = '';
    if(!$this->IsSiteAdmin)
    {
      // the usergroup id MUST be enclosed in pipes!
      $this->GroupCheck = " AND ((IFNULL(access_groupids,'')='') OR (access_groupids like '%|".$userinfo['usergroupid']."|%'))".
                          " AND ((IFNULL(access_userids,'')='') OR (access_userids like '%|".$userinfo['userid']."|%'))";
    }

    //v2.2.0: SEO URLs support
    // regular URL: 127.0.0.1:8080/sdcom/downloads.htm?p5001_sectionid=8&p5001_fileid=140
    // old 2.6 URL: 127.0.0.1:8080/sdcom/downloads/p5001/sectionid/8
    //          or: 127.0.0.1:8080/sdcom/downloads/p5001/sectionid/8/p5001/fileid/140
    // NEW SEO URL: 127.0.0.1:8080/sdcom/downloads/skins-s8/zb-block-sd-edition-f140.htm

    // Try to detect old 2.6 URLs:
    $fileid = 0;
    $sectionid = 0;
    if($mainsettings_modrewrite && !empty($sd_url_params) && (count($sd_url_params) > 2))
    {
      if(($sd_url_params[0]==$this->HTML_PREFIX) && ($sd_url_params[1]=='sectionid'))
      {
        $this->seo_detected = true;
        $sectionid = (int)$sd_url_params[2];
        if((count($sd_url_params) == 6) &&
           ($sd_url_params[3]==$this->HTML_PREFIX) && ($sd_url_params[4]=='fileid'))
        {
          $fileid = (int)$sd_url_params[5];
        }
      }
    }
    else
    {
      $sectionid = GetVar($this->HTML_PREFIX.'_sectionid', 0, 'whole_number');
      $fileid = GetVar($this->HTML_PREFIX.'_fileid', 0, 'whole_number');
    }
    $this->sectionid = Is_Valid_Number($sectionid,0,0,999999999);
    $this->fileid    = Is_Valid_Number($fileid,0,1,999999999);

    if($this->seo_enabled && !$this->seo_detected && !empty($sd_variable_arr))
    {
      $count = count($sd_variable_arr);
      if($count > 0)
      {
        if(preg_match('#(.*)[\-](s|f)([0-9]*)\.?$#i',$sd_variable_arr[$count-1],$matches))
        {
          if((count($matches)==4) && ctype_digit($matches[3]))
          {
            if($matches[2]=='f') //file
            {
              $this->fileid = (int)$matches[3];
              $this->seo_detected = true;
            }
            else
            if($matches[2]=='s') //section
            {
              $this->sectionid = (int)$matches[3];
              $this->seo_detected = true;
              $this->seo_section_detected = true;
            }
          }
        }
        // Try to detect section from previous URL param:
        if(empty($this->sectionid) && ($count>1) &&
           preg_match('#(.*)[\-](s)([0-9]*)\.?$#i',$sd_variable_arr[$count-2],$matches2))
        {
          if((count($matches2)==4) && ctype_digit($matches2[3]))
          {
            if($matches2[2]=='s')
            {
              $this->sectionid = (int)$matches2[3];
              $this->seo_detected = true;
              $this->seo_section_detected = true;
            }
          }
        }
      }
    }

    // Load or fill cached sections and SEO titles
    if(!empty($checkCache))
    {
      $this->InitSectionCache($this->sectionid);
    }

    if($this->fileid && ($this->fileid > 0))
    {
      $result = $this->SetSectionAndFile($this->seo_detected && isset($matches[1])?(string)$matches[1]:false);
    }
    else
    if($this->sectionid && ($this->sectionid > 0))
    {
      $result = $this->SetSection($this->sectionid, $this->seo_detected && isset($matches[1])?(string)$matches[1]:false);
    }
    else
    {
      $this->sectionid = 1;
      $result = $this->SetSection($this->sectionid, false);
    }

    return $result;

  } //InitFrontpage


  // #############################################################################

  public function InitSectionCache($parent_sectionid=0)
  {
    global $DB, $mainsettings, $userinfo, $SDCache,
           $mainsettings_tag_results_page;

    $parent_sectionid = empty($parent_sectionid)?0:(int)$parent_sectionid;

    if($this->cache_on)
    {
      if( ($this->dlm_cache===false) ||
          empty($this->dlm_cache[$parent_sectionid]['loaded']))
      {
        if(($getcache = $SDCache->read_var('dlm_cache_'.$this->pluginid.'_'.$parent_sectionid,
                                           'cache')) !== false)
        {
          $this->dlm_cache[$parent_sectionid] = $getcache;
        }
      }

      if(($this->seo_cache===false) || empty($this->seo_cache[$parent_sectionid]['loaded']))
      {
        if(($getcache = $SDCache->read_var('dlm_cache_seo_'.$this->pluginid.'_'.$parent_sectionid,
                                           'cache')) !== false)
        {
          $this->seo_cache[$parent_sectionid] = $getcache;
        }
      }
    }

    // ################## FILL CACHE FROM DB #############################
    if( ($this->dlm_cache===false) || ($this->seo_cache===false) ||
        empty($this->dlm_cache[$parent_sectionid]['loaded']) ||
        empty($this->seo_cache[$parent_sectionid]['loaded']) )
    {
      if($this->dlm_cache===false) $this->dlm_cache = array();
      $this->dlm_cache[$parent_sectionid] =
        array('loaded'    => true,
              'sectionid' => $parent_sectionid,
              'sections'  => array());

      if($this->seo_cache===false) $this->seo_cache= array();
      $this->seo_cache[$parent_sectionid] =
        array('by_id'     => array(),
              'by_title'  => array(),
              'loaded'    => true,
              'sectionid' => $parent_sectionid);

      // Fetch sections data now
      if($getdata = $DB->query(
        'SELECT ms.*,
 (SELECT COUNT(*) FROM '.DownloadManagerTools::$tbl_sections.' ms2
  WHERE ms2.parentid = ms.sectionid) subsections
 FROM '.DownloadManagerTools::$tbl_sections.' ms
 WHERE (ms.sectionid = %d)
 UNION
 SELECT ms.*,
 (SELECT COUNT(*) FROM '.DownloadManagerTools::$tbl_sections.' ms2
  WHERE ms2.parentid = ms.sectionid) subsections
 FROM '.DownloadManagerTools::$tbl_sections.' ms
 WHERE (ms.parentid = %d)
 ORDER BY 1,2',
          $parent_sectionid, $parent_sectionid))
      {
        while($entry = $DB->fetch_array($getdata,null,MYSQL_ASSOC))
        {
          $sid = (int)$entry['sectionid'];
          $pid = empty($entry['parentid']) ? 0 : (int)$entry['parentid'];
          /*
          if(isset($entry['seo_title']) && strlen($entry['seo_title']))
          {
            $this->seo_cache[$parent_sectionid]['by_id'][$sid] = $entry['seo_title'];
            $this->seo_cache[$parent_sectionid]['by_title'][$entry['seo_title']] = $sid;
          }
          else
          */
          if(strlen($entry['name']))
          {
            $title = ConvertNewsTitleToUrl($entry['name'],$sid,1,true,false);
            $this->seo_cache[$parent_sectionid]['by_id'][$sid] = $title;
            $this->seo_cache[$parent_sectionid]['by_title'][$title] = $sid;
            $entry['seo_title'] = $title;
          }

          @ksort($entry);
          if($sid == $parent_sectionid)
            $this->dlm_cache[$parent_sectionid]['section'] = $entry;
          else
            $this->dlm_cache[$parent_sectionid]['sections'][$sid] = $entry;
        } //while

        if(count($this->seo_cache[$parent_sectionid]['by_id']))
          @ksort($this->seo_cache[$parent_sectionid]['by_id']);
        if(count($this->seo_cache[$parent_sectionid]['by_title']))
          @ksort($this->seo_cache[$parent_sectionid]['by_title']);

        if($this->cache_on)
        {
          $SDCache->write_var('dlm_cache_'.$this->pluginid.'_'.$parent_sectionid,
                              'cache', $this->dlm_cache[$parent_sectionid]);
          $SDCache->write_var('dlm_cache_seo_'.$this->pluginid.'_'.$parent_sectionid,
                              'cache', $this->seo_cache[$parent_sectionid]);
        }
      }

      // Remove caching progress flag
      $DB->query('DELETE FROM {pluginsettings}'.
                 ' WHERE pluginid = %d'.
                 " AND groupname = '' AND title = 'caching_run'",
                 $this->pluginid);
    }

  } //InitSectionCache

  // #########################################################################

  function SetSection($sectionid, $uri_seo_title='')
  {
    global $DB, $mainsettings, $userinfo;

    $DB->result_type = MYSQL_ASSOC;
    if($section_arr = $DB->query_first('SELECT * FROM '.$this->tbl('sections').
                                       ' WHERE sectionid = %d'.
                                       ($this->IsSiteAdmin ? '' : ' AND activated = 1 ').
                                       $this->GroupCheck . ' LIMIT 1', $sectionid))
    {
      $this->sectionid = (int)$section_arr['sectionid'];
      $this->currenturl = DownloadManagerTools::RewriteSectionLink($this->pageurl, $this->sectionid, $section_arr['name']);

      //v2.2.0: redirect non-SEO section link to SEO section link
      if($this->seo_enabled && empty($this->ACTION))
      {
        $res = ConvertNewsTitleToUrl(sd_unhtmlspecialchars($section_arr['name']),$this->sectionid,0,true,null);
        if($res)
        {
          if( (!empty($uri_seo_title) && ($res != $uri_seo_title)) ||
              isset($_GET[$this->HTML_PREFIX.'_sectionid']) )
          {
            StopLoadingPage('','',301,$this->currenturl);
          }
        }
      }

      $this->section_arr = $section_arr;
      $this->section_arr['access_groupids'] = !empty($this->section_arr['access_groupids']) ? sd_ConvertStrToArray($this->section_arr['access_groupids'],'|'):array();
      $this->section_arr['access_userids']  = !empty($this->section_arr['access_userids'])  ? sd_ConvertStrToArray($this->section_arr['access_userids'],'|'):array();

      //SD343: set user-level permission flags - incl. secondary usergroups
      $canAdmin = !empty($userinfo['pluginadminids']) && in_array($this->pluginid, $userinfo['pluginadminids']);
      $canView  = empty($this->section_arr['access_userids']) ||
                  (!empty($userinfo['userid']) && @in_array($userinfo['userid'], $this->section_arr['access_userids']));
      if(!$canView)
      {
        $canView = empty($this->section_arr['access_groupids']) ||
                   (!empty($userinfo['usergroupids']) && @array_intersect($userinfo['usergroupids'], $this->section_arr['access_groupids']));
      }

      $this->IsAdmin = $canAdmin && $canView;

      //TODO: Reporting of files by user
      $this->user_report_perm = false;
      $this->user_report_perm_msg = false;
      /*
      if(!empty($mainsettings['reporting_enabled']))
      {
        $canSubmit = $this->IsSiteAdmin || ($canView && ($canAdmin || DownloadManagerTools::GetVar('allow_submit')));
        $this->user_report_perm_msg = empty($userinfo['report_message'])?'':$userinfo['report_message'];
        $report_groups =
          (DownloadManagerTools::GetSetting('post_reporting_permissions', false) ?
           sd_ConvertStrToArray(DownloadManagerTools::GetSetting('post_reporting_permissions'),',') :
           array());
        $this->user_report_perm =
          !empty($userinfo['loggedin']) &&
          ( $this->IsSiteAdmin || $this->IsModerator ||
            ($canSubmit && !empty($report_groups) && !empty($userinfo['usergroupids']) &&
             @array_intersect($userinfo['usergroupids'], $report_groups)) );
      }
      */

      return true;
    }

    // Section not found
    if(!headers_sent()) header("HTTP/1.0 404 Not Found");
    $this->error_arr[] = DownloadManagerTools::GetPhrase('no_section_access');
    $this->sectionid   = 0;
    $this->fileid      = 0;
    $this->section_arr = array();
    return false;

  } //SetSection


    // ##########################################################################

  function SetSectionAndFile($uri_seo_title='')
  {
    global $DB, $userinfo;

    $DB->result_type = MYSQL_ASSOC;
    $ug = $userinfo['usergroupid'];
    if($file_arr = $DB->query_first(
      'SELECT f.fileid, f.currentversionid, fv.version,
        fv.filename, fv.filetype, fv.filesize, fv.storedfilename,
        f.title, f.author, f.description, f.uniqueid, f.thumbnail, f.image,
        f.dateadded, f.dateupdated, f.downloadcount, f.licensed,
        f.access_groupids, f.access_userids, f.standalone,
        fv.is_embeddable_media, f.embedded_in_details, f.embedded_in_list,
        f.audio_class, f.video_class, f.media_autoplay, f.media_loop,
        f.embed_width, f.embed_height, f.media_download,
        (SELECT MIN(fs.sectionid) FROM '.$this->tbl('file_sections').' fs
         WHERE fs.fileid = f.fileid) backup_sectionid,
        IFNULL((SELECT fs.sectionid FROM '.$this->tbl('file_sections').' fs
         WHERE fs.fileid = f.fileid AND fs.sectionid = '.$this->sectionid.'),0) current_sectionid,
        cs.name current_sname
      FROM '.$this->tbl('files').' f
      LEFT JOIN '.$this->tbl('file_versions').' fv ON fv.fileid = f.fileid AND fv.versionid = f.currentversionid
      LEFT JOIN '.$this->tbl('sections').' cs ON cs.sectionid = '.$this->sectionid.'
      WHERE ' . DownloadManagerTools::dlm_ActivatedQuery($userinfo['username']) . '
      AND f.fileid = %d
      AND ((IFNULL(f.access_groupids,"") = "") OR (f.access_groupids like "%|'.$ug.'|%"))
      AND ((IFNULL(f.access_userids,"")  = "") OR (f.access_userids like "%|'.$userinfo['userid'].'|%"))
      LIMIT 1',
      $this->fileid))
    {
      // If file is not found in current section, then error
      if(!empty($this->sectionid) && empty($file_arr['current_sectionid']))
      {
        # file is not in current section, let it run into 404!
      }
      else
      {
        $redirect = false;
        // If section is not yet set, take selected backup section:
        if(empty($this->sectionid) && !empty($file_arr['backup_sectionid']))
        {
          $this->sectionid = $file_arr['backup_sectionid'];
          if($this->sectionid > 1)
          {
            $redirect = true;
            $section_name = $DB->query_first('SELECT name FROM '.$this->tbl('sections').
                            ' WHERE sectionid = %d', $this->sectionid);
            $this->currenturl = DownloadManagerTools::RewriteSectionLink($this->pageurl, $this->sectionid, $section_name['name']);
          }
        }
        else
        if(!empty($this->sectionid))
        {
          $this->currenturl = DownloadManagerTools::RewriteSectionLink($this->pageurl, $this->sectionid, $file_arr['current_sname']);
        }

        if($this->seo_enabled && empty($this->ACTION))
        {
          //v2.2.0: check for mis-named SEO param in URI
          $res = ConvertNewsTitleToUrl(sd_unhtmlspecialchars($file_arr['title']),$this->fileid,0,true,null);
          if($res)
          {
            if((!$this->seo_section_detected && ($this->sectionid > 1)) || $redirect ||
               (!empty($uri_seo_title) && ($res != $uri_seo_title)))
            {
              $res = DownloadManagerTools::RewriteFileLink($this->currenturl, $file_arr['fileid'],
                                           sd_unhtmlspecialchars($file_arr['title']));
              StopLoadingPage('','',301,$res['link']);
            }
            else
            if(isset($_GET[$this->HTML_PREFIX.'_fileid']) || isset($_GET[$this->HTML_PREFIX.'_sectionid']))
            {
              // Redirect non-seo URL to seo-enabled link
              $res = DownloadManagerTools::RewriteFileLink($this->currenturl, $file_arr['fileid'],
                                           sd_unhtmlspecialchars($file_arr['title']));
              StopLoadingPage('','',301,$res['link']);
            }
          }
        }
        if(empty($this->sectionid)) $this->sectionid = 1;

        // File exists, does section?
        if($this->SetSection($this->sectionid))
        {
          // section exists, all is good!
          $this->fileid = (int)$file_arr['fileid'];
          $this->file_arr = $file_arr;
          return true;
        }
      }
    }

    // File not found (or not in given section!)
    $this->fileid = 0;
    $this->file_arr = array();
    $this->sectionid= 0;
    $this->section_arr = array();

    if(!headers_sent()) header("HTTP/1.0 404 Not Found");
    $this->error_arr[] = '<strong>'.DownloadManagerTools::GetPhrase('no_section_access').'</strong>';
    return false;

  } //SetSectionAndFile

  // #########################################################################

  function tbl($tbl_name='files')
  {
    switch($tbl_name)
    {
      case 'file_downloads' : return DownloadManagerTools::$tbl_file_downloads;
      case 'file_sections'  : return DownloadManagerTools::$tbl_file_sections;
      case 'file_versions'  : return DownloadManagerTools::$tbl_file_versions;
      case 'sections'       : return DownloadManagerTools::$tbl_sections;
      case 'files' :
      default :               return DownloadManagerTools::$tbl_files;
    }
  }

  // #########################################################################

  function pluginid()
  {
    return $this->pluginid;
  }

  // ############################### PRINT ERROR #############################

  function dlm_PrintError($error)
  {
    global $sdlanguage;

    switch($error)
    {
      case 'dne':
        echo DownloadManagerTools::GetPhrase('file_offline');
      break;

      case 'ra':
        echo $sdlanguage['no_download_access'];
      break;

      case 'al':
        echo DownloadManagerTools::GetPhrase('cannot_link_directly');
      break;

      case 'off':
        echo DownloadManagerTools::GetPhrase('file_offline');
      break;
    }
  }

  // ################################ PAGINATION  ###############################

  function dlm_Pagination($currentpage, $pagesize, $sectionid, $ordertype, $sectionname='')
  {
    global $DB, $categoryid, $userinfo;

    $searchby   = '';
    $searchtext = '';

    if(!empty($this->searchby))
    {
      $searchby = (string)$this->searchby;
    }
    if(isset($this->searchtext))
    {
      $searchtext = rtrim(ltrim((string)$this->searchtext));
    }
    $sectionid = (empty($sectionid)||($searchby=='tags')) ? 0 : intval($sectionid);
    $section = $this->URI_TAG.'_sectionid='.$sectionid;
    $search  = (!empty($searchby)   ? $this->URI_TAG.'_searchby='   . urlencode($searchby)   : '');
    $search .= (!empty($searchtext) ? $this->URI_TAG.'_searchtext=' . urlencode($searchtext) : '');

    $gettotalrows = array('totalrows' => 0);
    if(!empty($searchtext) && (strlen($searchby) > 1) )
    {
      if($searchby=='tags')
        $gettotalrows['totalrows'] = DownloadManagerTools::dlm_GetFileTagCount($searchtext);
      else
        $gettotalrows['totalrows'] = DownloadManagerTools::dlm_GetSectionFileCount($sectionid,
                                       0, ($sectionid<1),#false,
                                       " AND $searchby LIKE '%".$DB->escape_string($searchtext)."%'");
    }
    else
    if(isset($sectionid) && NotEmpty(DownloadManagerTools::GetSetting('display_sections')))
    {
      if(!NotEmpty(DownloadManagerTools::GetSetting('display_sections')))
      {
        $gettotalrows['totalrows'] = DownloadManagerTools::dlm_GetSectionFileCount($sectionid, 0, false);
      }
      else
      {
        $gettotalrows = array();
        $gettotalrows['totalrows'] = DownloadManagerTools::dlm_GetSectionFileCount($sectionid, 0, false);
      }
    }
    else
    {
      $DB->result_type = MYSQL_ASSOC;
      $gettotalrows = $DB->query_first('SELECT COUNT(fileid) AS totalrows'.
                           ' FROM '.$this->tbl('files').' f'.
                           ' WHERE ' . DownloadManagerTools::dlm_ActivatedQuery($userinfo['username']));
    }

    $totalrows  = empty($gettotalrows['totalrows'])?0:$gettotalrows['totalrows'];
    $pagesize   = (empty($pagesize) || ($pagesize < 1) ? 5 : $pagesize);
    $totalpages = ceil($totalrows / $pagesize);

    if($totalpages == 0)
    {
      $totalpages = 1;
    }
    if($totalpages > 1)
    {
      /*
      if(DownloadManagerTools::GetSetting('use_advanced_paging') == 1)
      {
        // Advanced Paging (do NOT use 100% for width!)
        echo '
        <table align="right" border="0" cellpadding="0" cellspacing="0" style="padding: 0px;">
        <tr><td style="vertical-align: top">' . DownloadManagerTools::GetPhrase('page') . '&nbsp;' .
        $currentpage . '&nbsp;' . DownloadManagerTools::GetPhrase('of') . '&nbsp;' . $totalpages . '</td>';

        if($totalpages > 1)
        {
          echo '<td style="padding: 0px 0px 0px 4px; vertical-align: top;"> [';
          for($i = 1; $i <= $totalpages; $i++)
          {
            if($i == $currentpage)
            {
              echo '<a style="color: red; vertical-align: top;" href="'.RewriteLink('index.php?categoryid='.$categoryid.
              $section.$this->URI_TAG.'_currentpage='.$i.(strlen($ordertype)?$this->URI_TAG.'_ordertype=':$ordertype) .
              $this->URI_TAG.'_pagesize='.$pagesize.$search).'"><strong>'.$i.'</strong></a> ';
            }
            else
            {
              echo '<a style="vertical-align: top;" href="' . RewriteLink('index.php?categoryid='.$categoryid.
              $section.$this->URI_TAG.'_currentpage='.$i.(strlen($ordertype)?$this->URI_TAG.'_ordertype=':$ordertype).
              $this->URI_TAG.'_pagesize='.$pagesize.$search).'">'.$i.'</a> ';
            }
          }
          echo ' ]</td>';
        }
        echo '</tr></table>';
      }
      else
      */
      {
        // Default pagination is core SD3 pagination
        // v2.2.0: SEO section links
        $dlm_pages = $this->currenturl.
             (strpos($this->currenturl,'?')===false?'?':'&amp;').
             $this->HTML_PREFIX.'_pagesize=' . $pagesize .
             (strlen($ordertype)?$this->URI_TAG.'_ordertype=':$ordertype) .
             $search;
        $p = new pagination;
        $p->adjacents(5);
        $p->currentPage($currentpage);
        $p->items($totalrows);
        $p->limit($pagesize);
        $p->parameterName($this->HTML_PREFIX.'_currentpage');
        $p->target($dlm_pages);
        return $p->getOutput();
      }
    }
  } //dlm_Pagination


  // ###########################################################################

  // Display table with different file versions depending on file version type.
  // Used for frontpage only.
  function dlm_DisplayFileVersions($file,$sectionid,$extended=false,$isadmin=false)
  {
    global $DB, $categoryid, $userinfo;

    if(empty($file) || empty($file['fileid']))
    {
      return false;
    }

    // By default only the non-current file versions are displayed.
    // But if either "$extended" is true OR the file is a "standalone" file
    // (but not embedded media), then ALL are selected.
    if($extended || (!empty($file['standalone']) && empty($file['is_embeddable_media'])))
    {
      $extra = '';
    }
    else
    {
      $extra = ' AND versionid <> '.(int)$file['currentversionid'];
    }
    $getversions = $DB->query('SELECT * FROM '.$this->tbl('file_versions').' WHERE fileid = %d' .
                        $extra.
                        (empty($file['standalone'])?' ORDER BY datecreated DESC':' ORDER BY versionid ASC'),
                        $file['fileid']);
    $rows = $DB->get_num_rows();
    $headerprinted = false;

    // Both "$extended" and "standalone" trigger a different table header
    if($extended && empty($file['standalone']))
    {
      $headerprinted = true;
      echo '
      <table border="0" cellspacing="0" cellpadding="0" class="dlm-file-versions" summary="layout" width="100%" >
      <tr class="dlm-versions-header">
      <td colspan="4">';
    }

    $idx = 1;
    if($rows > 0)
    {
      if($extended && ($file['standalone'] != 1))
      {
        echo '<strong>'.DownloadManagerTools::GetPhrase('current_file_version').'</strong>';
        echo '</td></tr>';
      }

      while($version = $DB->fetch_array($getversions,null,MYSQL_ASSOC))
      {
        $link = $linkdescr = '';
        if(!$headerprinted && ($file['currentversionid'] != $version['versionid']))
        {
          $headerprinted = true;
          echo '
          <table border="0" cellspacing="0" cellpadding="0" class="dlm-file-versions" summary="layout" width="100%" >
          <tr class="dlm-versions-header">
            <td colspan="4" padding: 5px 5px 5px 0px;">
            <div class="dlm-versions-title">';
          switch($file['standalone'])
          {
            case '1' :
              $linkdescr = DownloadManagerTools::GetPhrase('related_files');
              break;
            case '2' :
              $linkdescr = DownloadManagerTools::GetPhrase('download_locations') . ' ' . $file['title'];
              break;
            default:
              $linkdescr = DownloadManagerTools::GetPhrase('previous_versions');
              break;
          }
          echo $linkdescr . '</div></td></tr>';
        }

        if(!$version['is_embeddable_media'])
        {
          if($file['licensed'] && (strlen(DownloadManagerTools::GetSetting('license_agreement'))))
          {
            $link = '<a class="dlm-link-download" href="' . RewriteLink('index.php?categoryid='.$categoryid.
              $this->URI_TAG.'_sectionid='.$sectionid.
              $this->URI_TAG.'_fileid='.$file['fileid'].
              $this->URI_TAG.'_versionid='.$version['versionid'].
              $this->URI_TAG.'_action=license') . '">';
          }
          else
          {
            $link = '<a class="dlm-link-'.($version['is_embeddable_media']?'open':'download').'" href="'.
              SITE_URL . DownloadManagerTools::GetVar('GETFILE').$categoryid.
              $this->URI_TAG.'_sectionid='.$sectionid.
              $this->URI_TAG.'_fileid='.$file['fileid'].
              $this->URI_TAG.'_versionid='.$version['versionid'] . '">';
          }

          $external = (($version['filetype']=='FTP' && !$version['storedfilename']) ||
                       ((substr(strtolower($version['filename']),0,3)=='www') ||
                       (substr(strtolower($version['filename']),0,4)=='http')))?'External ':'';
          // "Mirror" file display
          if($file['standalone']=='2')
          {
            echo '
              <tr class="dlm-versions-row">
                <td colspan="3">&nbsp;' .
                  $link . $external . DownloadManagerTools::GetPhrase('download_mirror') . ' ' . ($rows>1?$idx:'') . '</a>
                </td>
                <td>(' .
                  DownloadManagerTools::dlm_DisplayReadableFileSize($version['filesize']) . ')
                  </td>';
              $idx++;
          }
          // "Default" and "Standalone" file version display
          else
          {
            echo '
              <tr class="dlm-versions-row">
                <td class="dlm-versions-cell"><strong>' .
                  ($extended || !empty($file['standalone'])?
                  (!empty($external)?'[external link]':$version['filename']).'</strong><br />':'').
                  DisplayDate($version['datecreated']) .
                  '</td>
                <td class="dlm-versions-cell">' .
                  $version['version'] .
                  '</td>
                <td class="dlm-versions-cell">' .
                  DownloadManagerTools::dlm_DisplayReadableFileSize($version['filesize']) .
                  '</td>
                <td class="dlm-versions-cell">' .
                  $link .
                  ($version['is_embeddable_media'] ?
                    DownloadManagerTools::GetPhrase('dlm_open_link') :
                    DownloadManagerTools::GetPhrase('download_now')) . '</a>';

            // Display delete link?
            if(($rows > 1) &&
               (DownloadManagerTools::GetVar('allow_admin') ||
                (NotEmpty(DownloadManagerTools::GetSetting('enable_author_file_delete')) &&
                  DownloadManagerTools::GetVar('allow_submit') && !empty($file['author']) && !empty($userinfo['loggedin']) &&
                  ($userinfo['username'])==$file['author'])))
            {
              echo '<br />'.DownloadManagerTools::dlm_GetDelFileVersionLink($categoryid,$sectionid,
                   $version['fileid'],$version['versionid'],$userinfo['username']);
            }
            echo '</td>';
          }
        }
        echo '</tr>';
      } // while
    }

    if($headerprinted)
    {
      echo '
      </table>
      ';
    }
    return ($rows > 0);

  } //dlm_DisplayFileVersions


  // ****************************************************************************
  // SUBMIT FORM for either a new file or EDITING an existing file.
  // Depending on the user's plugin access rights (e.g. admin) additional options
  // are made available on the form.
  // ****************************************************************************
  function dlm_DisplayEditFileForm($sectionid, $fileid, $versionid=0, $errors=null)
  {
    global $DB, $categoryid, $inputsize, $userinfo;

    // Display section menu on top?
    $needmenu = true;
    if(NotEmpty(DownloadManagerTools::GetSetting('display_sections')) && ($this->ACTION=='submitfileform'))
    {
      echo '
      <div class="dlm-sections-head">
      ';
      DownloadManagerTools::dlm_DisplayMenu($sectionid, $this->ACTION);
      echo '
      </div>
      ';
    }

    // If any errors were passed in, display them now
    if(is_array($errors) && count($errors) > 0)
    {
      DownloadManagerTools::PrintErrors($errors, DownloadManagerTools::GetPhrase('upload_error'));
    }

    // Check if the file with the given fileid is online and available to the user?
    if(!empty($fileid) && is_numeric($fileid))
    {
      $ug = $userinfo['usergroupid'];
      $uid = $userinfo['userid'];
      $DB->result_type = MYSQL_ASSOC;
      if(($this->ACTION !== 'submitfileform') ||
        (!$file = $DB->query_first('SELECT f.fileid, f.activated, f.currentversionid, fv.version,
        fv.filename, fv.filetype, fv.filesize, fv.storedfilename,
        f.title, f.author, f.description, f.uniqueid, f.thumbnail, f.image,
        f.dateadded, f.dateupdated, f.downloadcount, f.licensed, f.standalone
        FROM '.$this->tbl('files').' f
        INNER JOIN '.$this->tbl('file_sections').' fs ON fs.fileid = f.fileid
        INNER JOIN '.$this->tbl('sections').' s ON s.sectionid = fs.sectionid
        LEFT  JOIN '.$this->tbl('file_versions').' fv ON fv.fileid = f.fileid AND fv.versionid = f.currentversionid
        WHERE f.fileid = %d
        AND s.sectionid = %d
        AND ' . DownloadManagerTools::dlm_ActivatedQuery($userinfo['username']) . '
        AND ((s.access_groupids = "") OR (s.access_groupids IS NULL) OR (s.access_groupids LIKE "%|'.$ug.'|%"))
        AND ((s.access_userids  = "") OR (s.access_userids  IS NULL) OR (s.access_userids  LIKE "%|'.$uid.'|%"))
        AND ((f.access_groupids = "") OR (f.access_groupids IS NULL) OR (f.access_groupids LIKE "%|'.$ug.'|%"))
        AND ((f.access_userids  = "") OR (f.access_userids  IS NULL) OR (f.access_userids  LIKE "%|'.$uid.'|%"))',
        $fileid, $sectionid)))
      {
        echo '<br /><div style="font-size:12pt; padding-left: 0px;">' .
             DownloadManagerTools::GetPhrase('file_offline') .
             '</div><br />';
        return;
      }
      $file_title = DownloadManagerTools::GetPhrase('edit_file') . ' "' . $file['title'] . '"';
      $iseditmode = true;

      //v2.3.0: load tags from core "tags" table
      $file['tags'] = '';
      $tags = SD_Tags::GetPluginTags($this->pluginid, $fileid);
      if(!empty($tags) && is_array($tags))
      {
        $file['tags'] = implode(',', $tags);
      }
    }
    else
    {
      $iseditmode = false;
      $file_title = DownloadManagerTools::GetPhrase('submit_file');
      $file = array(
        'fileid'         => 0,
        'activated'      => 0,
        'currentversionid' => 0,
        'version'        => DownloadManagerTools::GetVar('DEFAULT_VERSION'),
        'filename'       => '',
        'filetype'       => '',
        'filesize'       => 0,
        'storedfilename' => '',
        'title'          => GetVar('p'.$this->pluginid.'_title','','string',true,false),
        'author'         => GetVar('p'.$this->pluginid.'_author',$userinfo['username'],'string',true,false),
        'description'    => GetVar('p'.$this->pluginid.'_description','','string',true,false),
        'uniqueid'       => '',
        'thumbnail'      => '',
        'image'          => '',
        'downloadcount'  => 0,
        'dateadded'      => TIME_NOW,
        'dateupdated'    => '',
        'datestart'      => TIME_NOW,
        'dateend'        => 0,
        'licensed'       => 0,
        'standalone'     => 0,
        'tags'           => GetVar('p'.$this->pluginid.'_tags','','string',true,false));
      // If errors were passed in, fetch posted values
      if(is_array($errors) && count($errors) > 0)
      {
        // use GetVar to retrieve $_POST values...
      }
    }
    $errors = array();

    // Preset file storage location depending on current file version
    $filestore = DownloadManagerTools::GetSetting('default_upload_location');

    $max_upload_size = strlen(DownloadManagerTools::GetSetting('max_upload_size')) > 0 ?
                         (DownloadManagerTools::GetSetting('max_upload_size') * 1024) :
                         DownloadManagerTools::GetVar('DEFAULT_MAX_UPLOAD_SIZE');

    $tr_main = DownloadManagerTools::GetSetting('main_color');
    $tr_main = (empty($tr_main) ? '' : ' style="background-color: '.$tr_main.';"');

    // Header (TODO: change from TABLE to DIV for better display?)

    $tr_bg = DownloadManagerTools::GetSetting('main_color');
    echo '
    <table style="padding-right: 10px; padding-top: 5px;" border="0" cellpadding="0" cellspacing="0" summary="layout" width="100%">
    <tr'.(empty($tr_bg) ? '' : ' style="background-color:' . $tr_bg . '"') . '>
    <td style="font-size: 120%; padding: 6px 10px 4px 4px; vertical-align: middle; width: 100%;" valign="middle">
      <strong>' . $file_title . '</strong>
    </td>
    <td style="padding: 6px 0 4px 4px" align="right" valign="top">';

    // Display file's "thumbnail"
    if($iseditmode && !empty($file['thumbnail']))
    {
      echo DownloadManagerTools::dlm_GetFileImageAsThumb($file,'thumbnail');
    }
    echo '</td>
    </tr>
    </table>';

    // File details <form...> and <table...>
    echo '
      <form class="uniForm dlm-fileform" method="post" enctype="multipart/form-data" action="'.$this->currenturl.'">
      '.PrintSecureToken().'
      <input type="hidden" name="p'.$this->pluginid.'_action" value="'.($iseditmode?'updatefile':'insertfile').'" />
      <input type="hidden" name="p'.$this->pluginid.'_oldsection" value="'.$sectionid.'" />
      ';

    if(!NotEmpty(DownloadManagerTools::GetSetting('show_upload_section_selection')))
    {
      echo '
      <input type="hidden" name="p'.$this->pluginid.'_sectionid" value="' . $sectionid. '" />';
    }
    echo '
      <input type="hidden" name="p'.$this->pluginid.'_fileid" value="' . $fileid. '" />';

    // Default author to current username for a new file
    if(!DownloadManagerTools::GetVar('allow_admin') && !$iseditmode)
    {
      echo '
      <input type="hidden" name="p'.$this->pluginid.'_author" value="' . $file['author'] . '" />';
    }

    echo '
      <table class="dlm-file-details" border="0" cellpadding="0" cellspacing="0" summary="layout" width="100%" >
        <tr>';

    // Allow change of activation flag only for Admins - this should not
    // even be allowed for the same author because that same username may
    // become vacant eventually and a different person may assume this
    // identity afterwards.
    if(DownloadManagerTools::GetVar('allow_admin'))
    {
      echo '
        <td valign="top" style="width: 200px; padding-top: 4px;">'.DownloadManagerTools::GetPhrase('file_online').'</td>
        <td valign="top" style="padding-bottom: 5px;">
          <div class="dlm-select-div" style="max-width: 60px; width: 60px;">
          <select class="dlm-select" style="max-width: 56px; width: 56px;" name="p'.$this->pluginid.'_activated">
            <option value="1" ' . (!empty($file['activated'])?'selected="selected"':'') . '>'.DownloadManagerTools::GetPhrase('yes').'</option>
            <option value="0" ' . (empty($file['activated'])?'selected="selected"':'') . '>'.DownloadManagerTools::GetPhrase('no').'</option>
          </select></div>
        </td>
      </tr>';
    }
    else
    {
      // Re-check plugin settings and default activated flag if required
      DownloadManagerTools::SetSetting('auto_approve_uploads', NotEmpty(DownloadManagerTools::GetSetting('auto_approve_uploads'))?1:0);
      $file['activated'] = !$iseditmode ? $file['activated'] : DownloadManagerTools::GetSetting('auto_approve_uploads');
      // If applicable show approval hint for user
      if(!DownloadManagerTools::GetSetting('auto_approve_uploads'))
      {
        if($iseditmode)
        {
          $approvalmsg = NotEmpty(DownloadManagerTools::GetPhrase('file_edit_approval'))?DownloadManagerTools::GetPhrase('file_edit_approval'):
            'Note: File changes are pending approval by website administration.';
        }
        else
        {
          $approvalmsg = NotEmpty(DownloadManagerTools::GetPhrase('file_submit_approval'))?DownloadManagerTools::GetPhrase('file_submit_approval'):
            'Note: File changes are pending approval by website administration.';
        }
      }
      echo '
            <td colspan="2" valign="top" style="padding-bottom: 8px; padding-top: 0px;">' .
            $approvalmsg . '
            <input type="hidden" name="p'.$this->pluginid.'_activated" value="'. $file['activated'] . '" />
        </td>
      </tr>';
    }

    echo '
        <tr>
        <td valign="top" style="width: 200px; padding-top: 4px;">' . DownloadManagerTools::GetPhrase('file_title') . '</td>
        <td valign="top" style="padding-bottom: 5px;">
          <input type="text" name="p'.$this->pluginid.'_title" size="'.$inputsize.'" value="'.
          ($iseditmode ? stripslashes ($file['title']): $file['title']) . '" />
        </td>
      </tr>';

    // Target section selection
    if(NotEmpty(DownloadManagerTools::GetSetting('show_upload_section_selection')))
    {
      echo '
        <tr>
        <td valign="top" style="width: 200px; padding-top: 4px;">' . DownloadManagerTools::GetPhrase('upload_to_section') . '</td>
        <td valign="top" style="padding-bottom: 5px;">';

      DownloadManagerTools::dlm_PrintSectionSelection(
        array('formId'            => $this->HTML_PREFIX.'_sectionid',
              'selectedSectionId' => $sectionid,
              'displayCounts'     => false,
              'displayOffline'    => false));

      echo '
        </td>
      </tr>';
    }

    // Display "author" only for admins and when editing a file - this should
    // not even be allowed for the same author because that same username may
    // become vacant eventually and a different person may assume this
    // identity afterwards.
    if(DownloadManagerTools::GetVar('allow_admin'))
    {
      echo '
      <tr>
        <td valign="top" style="width: 200px; padding-top: 4px;">' . DownloadManagerTools::GetPhrase('author') . '</td>
        <td valign="top" style="padding-bottom: 5px;">
          <input type="text" name="p'.$this->pluginid.'_author" size="'.$inputsize.'" value="'.$file['author'].'" />
          <br />'.DownloadManagerTools::GetPhrase('edit_author_edit').'
        </td>
      </tr>';
    }
    else
    if($iseditmode)
    {
      echo '
      <tr>
        <td valign="top" style="width: 200px; padding-top: 4px;">' . DownloadManagerTools::GetPhrase('author') . '</td>
        <td valign="top" style="padding-bottom: 5px;">
          <input type="text" name="p'.$this->pluginid.'_author" readonly="readonly" size="'.$inputsize.'" value="'.$file['author'].'" />
        </td>
      </tr>';
    }

    // Tags
    echo '
      <tr>
        <td valign="top" style="width: 200px; padding-top: 4px;">' . DownloadManagerTools::GetPhrase('dlm_tags') . '</td>
        <td valign="top" style="padding-bottom: 5px;">
          <input type="text" name="p'.$this->pluginid.'_tags" size="'.$inputsize.'" value="'.$file['tags'].'" />
        </td>
      </tr>';

    // Description
    if($iseditmode)
    {
      // Apply BBCode parsing
      if($this->allow_bbcode)
      {
        $file['description'] = str_replace('&lt;br /&gt;',"\r\n",$file['description']);
      }
      else
      {
        $file['description'] = str_replace('&lt;br /&gt;','',$file['description']);
      }
      echo '
      <tr>
        <td style="padding-bottom: 5px; min-width: 150px;" colspan="2" valign="top">' .
        DownloadManagerTools::GetPhrase('file_description') . '</td>
      </tr>
      <tr>
        <td style="padding-bottom: 5px;" colspan="2" valign="top">';
      $descr_name = 'p'.$this->pluginid.'_description';
      if($this->allow_bbcode)
      {
        DisplayBBEditor($this->allow_bbcode, $descr_name, $file['description'], 'dlm-edit-description', 80, 15);
      }
      else
      {
        echo '<textarea class="dlm-edit-description" style="width:100%;" name="'.$descr_name.'" cols="'.$inputsize.'" rows="10">'.$file['description'].'</textarea>';
      }
      echo '
        </td>
      </tr>
      <tr' . $tr_main . '>
        <td style="font-size: 120%; font-weight: bold; padding-left: 0px; padding-top: 5px; padding-bottom: 5px;" colspan="2">
        '.DownloadManagerTools::GetPhrase('add_file_version').'</td>
      </tr>';
    }
    else
    {
      echo '
      <tr>
        <td valign="top" style="width: 200px; padding-top: 4px;">' . DownloadManagerTools::GetPhrase('file_description') . '</td>
        <td valign="top" style="padding-bottom: 5px;">
          <textarea class="dlm-edit-description" style="width:100%;" name="p'.$this->pluginid.'_description" cols="'.$inputsize.'" rows="10">'.
          $file['description'].'</textarea>
        </td>
      </tr>';
    }

    // Plugin setting for multiple file uploads added when
    // submitting files (up to 10 max); in EDIT mode only 1 at a time.
    $maxfiles = ($iseditmode ? 1 : (!NotEmpty(DownloadManagerTools::GetSetting('user_submit_file_count')) ? 1 : (int)DownloadManagerTools::GetSetting('user_submit_file_count')));
    $maxfiles = ($maxfiles > 10) ? 10 : $maxfiles;
    for($i = 1; $i <= $maxfiles; $i++)
    {
      echo '
        <tr>
          <td valign="top" style="width: 200px; padding-top: 4px;">' .
            DownloadManagerTools::GetPhrase('select_file') . ($maxfiles>1?$i:'') . '</td>
          <td valign="top" style="padding-bottom: 5px;">
            <input type="hidden" name="MAX_FILE_SIZE" value="' . $max_upload_size .
            '" /><input type="file" name="p'.$this->pluginid.'_file[]" size="'.$inputsize.'" style="width:99%" /><br />
            ('.DownloadManagerTools::GetSetting('allowed_file_extensions').')
          </td>
        </tr>';
      if(NotEmpty(DownloadManagerTools::GetSetting('allow_remote_upload_links')))
      {
        echo '
        <tr>
          <td valign="top" style="width: 200px; padding-top: 4px;">' .
            DownloadManagerTools::GetPhrase('user_submit_url') . ($maxfiles>1?$i:'') . '</td>
          <td valign="top" style="padding-bottom: 5px;">
            <input type="text" name="p'.$this->pluginid.'_url[]" size="'.$inputsize.'" /></td>
        </tr>';
      }
    }

    if($iseditmode)
    {
      echo '
      <tr>
        <td valign="top" style="width: 200px; padding-bottom: 10px;">' . DownloadManagerTools::GetPhrase('version') . '</td>
        <td valign="top" style="padding-bottom: 10px;">
          <input type="text" name="p'.$this->pluginid.'_version" size="10" value="'.$file['version'].'" />
          (for newly added file)
        </td>
      </tr>';
    }

    if(DownloadManagerTools::GetVar('allow_admin') || ($iseditmode && NotEmpty(DownloadManagerTools::GetSetting('edit_file_storage_change'))))
    {
      echo '
      <tr>
      <td valign="top" style="width: 200px; padding-top: 4px;">'.DownloadManagerTools::GetPhrase('edit_file_storage').'</td>
      <td valign="top">
        <div class="dlm-select-div" style="max-width: 150px; width: 150px">
        <select class="dlm-select" style="max-width: 146px; width: 146px" name="p'.$this->pluginid.'_filestore">
          <option value="0" '.(empty($filestore)?'selected="selected"':'').'>MySql</option>
          <option value="1" '.(!empty($filestore)?'selected="selected"':'').'>Filesystem</option>
        </select>
        </div>
      </td>
      </tr>';
    }
    else
    {
      echo '<input type="hidden" name="p'.$this->pluginid.'_filestore" value="'.$filestore.'" />';
    }

    // Display file version selection?
    if(NotEmpty(DownloadManagerTools::GetSetting('show_upload_file_version')))
    {
      // File versions are to be treated as standalone files?
      $standalone = (int)$file['standalone'];
      echo '
    <tr>
      <td valign="top" style="width: 200px; padding-top: 4px;">'.DownloadManagerTools::GetPhrase('file_standalone_option').'</td>
      <td valign="top" style="padding-bottom: 10px;">
        <div class="dlm-select-div" style="max-width: 146px; width: 150px">
        <select class="dlm-select" style="max-width: 146px; width: 146px" name="p'.$this->pluginid.'_standalone">
        <option value="0" '.($standalone==0 ? 'selected="selected"' :'').'>'.DownloadManagerTools::GetPhrase('file_version_default').'</option>
        <option value="1" '.($standalone==1 ? 'selected="selected"' :'').'>'.DownloadManagerTools::GetPhrase('file_version_standalone').'</option>
        <option value="2" '.($standalone==2 ? 'selected="selected"' :'').'>'.DownloadManagerTools::GetPhrase('file_version_mirror').'</option>
        </select></div>
      </td>
    </tr>';
    }

    // Display thumbnail creation option?
    if(NotEmpty(DownloadManagerTools::GetSetting('show_upload_create_thumbnail_option')))
    {
      // Create thumbnail if main file is an image of type gif,jpg,jpeg,png?
      echo '
    <tr>
      <td valign="top" style="width: 200px; padding-bottom: 10px;">' . DownloadManagerTools::GetPhrase('submit_create_thumbnail').'</td>
      <td valign="top" style="padding-bottom: 10px;">
        <input type="checkbox" name="p'.$this->pluginid.'_createthumb" value="1" /> ' .
        ($iseditmode?DownloadManagerTools::GetPhrase('submit_create_thumb_hint'):DownloadManagerTools::GetPhrase('submit_create_thumb_hint2')).' </td>
    </tr>
    <tr' . $tr_main . '>
      <td colspan="2" style="font-size: 120%; font-weight: bold; padding: 4px;">
      '.DownloadManagerTools::GetPhrase('optional_thumbnail_and_image').'</td>
    </tr>';
    }
    else
    {
      echo '<input type="hidden" name="p'.$this->pluginid.'_createthumb" value="0" />';
    }

    // Thumbnail name and preview
    if(NotEmpty(DownloadManagerTools::GetSetting('show_upload_optional_thumbnail')))
    {
      echo '
      <tr>
        <td valign="top" style="width: 200px; padding-bottom: 5px; padding-top: 5px;">'.
          DownloadManagerTools::GetPhrase('optional_thumbnail').'</td>
        <td valign="top" style="padding-bottom: 10px;">
        <input type="file" name="p'.$this->pluginid.'_thumbnail" size="'.$inputsize.'" /></td>
      </tr>';
      if($iseditmode && $file['thumbnail'] && !empty($file['thumbnail']))
      {
        echo '<tr><td style="width: 200px;">&nbsp;</td><td valign="top">';
        $img_file = DownloadManagerTools::$imageurl . $file['thumbnail'];
        DownloadManagerTools::dlm_DisplayImageDetails($file['thumbnail'], $img_file, false,
          '<input type="checkbox" name="p'.$this->pluginid.'_deletethumbnail" value="1" /> '.
          DownloadManagerTools::GetPhrase('delete_thumbnail'));
        echo '</td></tr>';
      }
    }

    // Image name and preview
    echo '
    <tr>
      <td style="width: 200px; padding-bottom: 10px;">'.
        DownloadManagerTools::GetPhrase('optional_image').'</td>
      <td valign="top" style="padding-bottom: 10px;">
        <input type="file" name="p'.$this->pluginid.'_image" size="'.$inputsize.'" /></td>
    </tr>';
    if($iseditmode && $file['image'] && !empty($file['image']))
    {
      echo '<tr><td style="width: 200px;">&nbsp;</td><td valign="top">';
      $img_file = DownloadManagerTools::$imageurl . $file['image'];
      DownloadManagerTools::dlm_DisplayImageDetails($file['image'], $img_file, false,
        '<input type="checkbox" name="p'.$this->pluginid.'_deleteimage" value="1" /> '.
        DownloadManagerTools::GetPhrase('delete_image'));
      echo '</td></tr>';
    }

    if($iseditmode)
    {
      echo '<tr><td colspan="2" style="width:100%">';
      $this->dlm_DisplayFileVersions($file,$sectionid,true,DownloadManagerTools::GetVar('allow_admin'));
      echo '</td>
      </tr>';
    }

    // Footer with submit button
    echo '
    <tr>
      <td style="width: 200px; padding-bottom: 15px; padding-top: 5px;">&nbsp;</td>
      <td valign="middle" style="padding: 15px;">
      <input class="dlm-button" type="submit" value="' .
      ($iseditmode?htmlspecialchars(DownloadManagerTools::GetPhrase('update_file_settings'),ENT_COMPAT):
                   htmlspecialchars(DownloadManagerTools::GetPhrase('submit_file'),ENT_COMPAT)) .'" />
      </td>
    </tr>
    </table>
    </form>';

  } //dlm_DisplayEditFileForm


  // ############################# DISPLAY FILES ##############################
  // ############################# DISPLAY FILES ##############################

  function dlm_DisplayFiles()
  {
    global $DB, $categoryid, $Comments, $bbcode, $sdlanguage, $userinfo, $dlm_osm_player;

    if((int)$this->fileid == 0)
    {
      unset($this->fileid);
    }

    // Check if the requested section actually exists and is active/online
    $DB->result_type = MYSQL_ASSOC;
    $section = $DB->query_first('SELECT * FROM '.$this->tbl('sections').
                                ' WHERE sectionid = %d AND activated = 1',
                                $this->sectionid);
    if(NotEmpty(DownloadManagerTools::GetSetting('display_sections')) && !$section)
    {
      echo DownloadManagerTools::GetPhrase('section_offline');
      return;
    }

    // Check that the user is allowed to access this section
    if(!DownloadManagerTools::GetVar('allow_admin') && !DownloadManagerTools::dlm_HasAccess($section))
    {
      echo DownloadManagerTools::GetPhrase('no_section_access');
      return;
    }

    // Display information for admin if upload folder is not writable
    if(DownloadManagerTools::GetVar('allow_admin') &&
       (!@is_dir(DownloadManagerTools::$filesdir) || !@is_writable(DownloadManagerTools::$filesdir)))
    {
      echo DownloadManagerTools::GetPhrase('error_no_upload_dir');
    }

    $tr_main = DownloadManagerTools::GetSetting('main_color');
    $tr_main = (empty($tr_main) ? '' : ' style="background-color: '.$tr_main.';"');

    // Prepare BBCode editor - if enabled - (incl. [code] and [embed] handlers)
    if($this->allow_bbcode)
    {
      // SD313: Replace rule for "[code]" tag with a better = working callback
      // by using new function "sd_BBCode_DoCode" (functions_global.php)
      $bbcode->RemoveRule('code');
      $bbcode->AddRule('code',
          array('mode' => BBCODE_MODE_CALLBACK,
                'class' => 'code',
                'method' => 'sd_BBCode_DoCode',
                'allow_in' => array('listitem', 'block', 'columns'),
                'content' => BBCODE_VERBATIM)
      );

      // SD313: is site-wide BBCode enabled? if not, remove BBCode embed button
      if(!empty($mainsettings['allow_bbcode_embed']))
      {
        $bbcode->AddRule('embed',
            array('mode' => BBCODE_MODE_CALLBACK,
                  'method' => 'sd_BBCode_DoEmbed',
                  'class' => 'link',
                  'allow_in' => array('listitem', 'block', 'columns', 'inline'),
                  'content' => BBCODE_REQUIRED,
                  'before_tag' => '',
                  'after_tag' => ''));
      }
      else
      {
        // Remove btnEmbed
        echo '  <script type="text/javascript">'.
          '  jQuery(document).ready(function() {'.
          '    jQuery("img.button[name=btnEmbed]").remove();'.
          '  });'.
          '  </script>
          ';
      }
    }

    // Display the sections
    if(NotEmpty(DownloadManagerTools::GetSetting('display_sections')))
    {
      echo '
    <div class="dlm-sections-head">
    ';
      DownloadManagerTools::dlm_DisplayMenu($this->sectionid, $this->ACTION);
      echo '
    </div>
    ';
    }

    if($this->ACTION != 'submitfileform')
    {
        // display the "submit a file" link if the user has access
      if(DownloadManagerTools::GetVar('allow_admin') || DownloadManagerTools::GetVar('allow_submit'))
      {
        //v2.2.0: SEO URL
        echo '<a class="dlm-sections-link dlm-submit-link" href="' .
             $this->currenturl.
             (strpos($this->currenturl,'?')===false?'?':'&amp;').
             $this->HTML_PREFIX.'_action=submitfileform'.'">'.
             DownloadManagerTools::GetPhrase('click_here_to_submit_file').'</a><br />';
      }
    }

    //v2.2.0: added tag cloud location option
    $cloud_html = '';
    if(DownloadManagerTools::GetSetting('display_tag_cloud',0))
    {
      $cloud_loc  = DownloadManagerTools::GetSetting('tagcloud_location',1);
      $cloud_html = DownloadManagerTools::dlm_DisplayTagCloud(30,5,false);
      if($cloud_loc != 1)
      {
        echo $cloud_html;
      }
    }

    if(NotEmpty(DownloadManagerTools::GetSetting('display_sections')) && !isset($this->fileid))
    {
      // Get subsections of the current section
      if($getsubsections = $DB->query('SELECT * FROM '.$this->tbl('sections').
                                      ' WHERE parentid = %d'.
                                      ($this->IsSiteAdmin ? '' : ' AND activated = 1 ').
                                      $this->GroupCheck .
                                      ' ORDER BY name', $this->sectionid))
      {
        echo '
        <div class="dlm-subsection-container">
        ';
        // Display the subsections
        while($subsection = $DB->fetch_array($getsubsections,null,MYSQL_ASSOC))
        {
          if(DownloadManagerTools::dlm_HasAccess($subsection))
          {
            $numsectionfiles = DownloadManagerTools::dlm_GetSectionFileCount($subsection['sectionid'], 0, true);

            // v2.2.0: SEO section links
            $link = DownloadManagerTools::RewriteSectionLink($this->currenturl, $subsection['sectionid'],
                                          sd_unhtmlspecialchars($subsection['name']));
            echo '
            <div class="dlm-float-left dlm-subsection">
            <strong><a class="dlm-sections-link" href="' . $link. '">';

            // Display of section's "thumbnail" and "image"
            if(NotEmpty(DownloadManagerTools::GetSetting('show_section_thumbnail')) && !empty($subsection['thumbnail']))
            {
              echo '<img class="dlm-subsection-image" alt=" " border="0" src="' . DownloadManagerTools::$imageurl . $subsection['thumbnail'] . '" /><br />';
            }

            echo $subsection['name']. ' ('. $numsectionfiles. ')</a></strong><br />';

            // Apply BBCode parsing
            if($this->allow_bbcode)
            {
              $subsection['description'] = $bbcode->Parse($subsection['description']);
            }
            echo html_entity_decode($subsection['description']);

            if(($subsection['description'] != '<br>') &&
               ($subsection['description'] != '<br />') &&
               strlen(rtrim($subsection['description'])) > 0)
            {
              echo '<br />';
            }
            echo '
            </div>
            ';
          } //if
        } //while
        echo '
        </div>
        ';
      }
    }

    // get the file(s) - and check if a specific file is viewed?
    $ug = $userinfo['usergroupid'];
    if(!empty($this->fileid) && is_numeric($this->fileid))
    {
      // is the file available and online?
      $DB->result_type = MYSQL_ASSOC;
      if(!$file = $DB->query_first('SELECT f.fileid, f.currentversionid, fv.version,
        fv.filename, fv.filetype, fv.filesize, fv.storedfilename,
        f.title, f.author, f.description, f.uniqueid, f.thumbnail, f.image,
        f.dateadded, f.dateupdated, f.downloadcount, f.licensed,
        f.access_groupids, f.access_userids, f.standalone,
        fv.is_embeddable_media, f.embedded_in_details, f.embedded_in_list,
        f.audio_class, f.video_class, f.media_autoplay, f.media_loop,
        f.embed_width, f.embed_height, f.media_download,
        IFNULL(cc.count,0) com_count
      FROM '.$this->tbl('files').' f
      LEFT JOIN '.$this->tbl('file_versions').' fv ON fv.fileid = f.fileid AND fv.versionid = f.currentversionid
      LEFT JOIN '.PRGM_TABLE_PREFIX.'comments_count cc ON cc.plugin_id = '.$this->pluginid.' AND cc.object_id = f.fileid
      WHERE ' . DownloadManagerTools::dlm_ActivatedQuery($userinfo['username']) . '
      AND f.fileid = %d
      AND ((IFNULL(f.access_groupids,"") = "") OR (f.access_groupids like "%|'.$ug.'|%"))
      AND ((IFNULL(f.access_userids,"")  = "") OR (f.access_userids like "%|'.$userinfo['userid'].'|%"))',
      $this->fileid))
      {
        echo DownloadManagerTools::GetPhrase('file_offline');
        return;
      }
      //v2.3.0: load tags from core "tags" table
      $file['tags'] = '';
      $tags = SD_Tags::GetPluginTags($this->pluginid, $this->fileid);
      if(!empty($tags) && is_array($tags))
      {
        $file['tags'] = implode(',', $tags);
      }
    }
    else // else get all files of this section or by search
    {
      // has the user selected an ordertype?
      if($this->sectionid && strlen($this->ordertype))
      {
        $order = ($this->ordertype == 'dateadded DESC') ? 'IF(dateupdated=0,dateadded,dateupdated) DESC' : $this->ordertype;
      }
      else
      {
        // order by default setting
        switch($section['sorting'])
        {
          case 'Alphabetically A-Z':
            $order = 'title ASC';
          break;

          case 'Alphabetically Z-A':
            $order = 'title DESC';
          break;

          case 'Author Name A-Z':
            $order = 'author ASC';
          break;

          case 'Author Name Z-A':
            $order = 'author DESC';
          break;

          case 'Oldest First':
            $order = 'dateadded ASC';
          break;

          default:
            $order = 'IF(dateupdated=0,dateadded,dateupdated) DESC'; // Newest First
        }
      }

      $extraSql = '';

      //v2.3.0: tag search requires extra join (EXISTS() does not work!)
      $tags_join = '';
      if($this->searchby == 'tags')
      {
        $tags_join = 'INNER JOIN '.PRGM_TABLE_PREFIX.'tags t ON t.pluginid = '.$this->pluginid.
                     ' AND t.objectid = f.fileid'.
                     " AND lower(t.tag) = '".$DB->escape_string(strtolower($this->searchtext))."'";
      }

      $sql = 'SELECT f.fileid, f.currentversionid,
        fv.version, fv.filename, fv.filetype, fv.filesize, fv.storedfilename,
        fv.is_embeddable_media,
        f.embedded_in_details, f.title, f.author, f.description, f.uniqueid, f.thumbnail,
        f.image, f.dateadded, f.dateupdated, f.downloadcount, f.licensed,
        f.access_groupids, f.access_userids, f.standalone, f.embedded_in_list,
        f.audio_class, f.video_class, f.media_autoplay, f.media_loop, f.embed_width,
        f.embed_height, f.media_download, fs.sectionid,
        IFNULL(cc.count,0) com_count
        FROM '.$this->tbl('files').' f '.$tags_join.'
        LEFT JOIN '.$this->tbl('file_versions').' fv ON fv.fileid = f.fileid AND fv.versionid = f.currentversionid
        INNER JOIN '.$this->tbl('file_sections').' fs ON fs.fileid = f.fileid
        INNER JOIN '.$this->tbl('sections').' s ON s.sectionid = fs.sectionid
        LEFT JOIN '.PRGM_TABLE_PREFIX.'comments_count cc ON cc.plugin_id = '.$this->pluginid.' AND cc.object_id = f.fileid
        WHERE ((IFNULL(s.access_groupids,"") = "") OR (s.access_groupids like "%|'.$ug.'|%"))
        AND ((IFNULL(f.access_groupids,"") = "") OR (f.access_groupids like "%|'.$ug.'|%"))
        AND ((IFNULL(f.access_userids,"")  = "") OR (f.access_userids like "%|'.$userinfo['userid'].'|%"))
        AND ' . DownloadManagerTools::dlm_ActivatedQuery($userinfo['username']);

      // Did the user query a search?
      if(!empty($this->searchtext))
      {
        if($this->searchby != 'tags')
        {
          if($this->searchby == 'description')
          {
            $this->searchby = 'f.'.$this->searchby;
          }
          $sql .= " AND $this->searchby LIKE '%" . $DB->escape_string($this->searchtext) . "%'";
        }
        $sql .= " ORDER BY $order LIMIT " . (($this->currentpage -1) * $this->pagesize) . ", " . ($this->pagesize + 1);
        $getfiles = $DB->query($sql);
      }
      else
      {
        if(NotEmpty(DownloadManagerTools::GetSetting('display_sections')))
        {
          $extraSql = "AND fs.sectionid = '".$this->sectionid."'";
        }

        $sql .= " $extraSql ORDER BY $order LIMIT " .
                (($this->currentpage -1) * $this->pagesize) . ", " . ($this->pagesize + 1);
        $getfiles = $DB->query($sql);
      }
      $rows = $DB->get_num_rows($getfiles);
    }  // end else get all files of this section or search


    if($this->sectionid==1)
      $section_link = $this->currenturl;
    else
      $section_link = DownloadManagerTools::RewriteSectionLink($this->currenturl, $this->sectionid,
                                            sd_unhtmlspecialchars($this->section_arr['name']));

    // ****************************************************************
    // Display the header (sort and search options)
    // ****************************************************************
    // If "category view" is off, then show the search header
    // in any case or only one page of files would show up!
    $out = '';
    if(empty($this->fileid) &&
       (NotEmpty(DownloadManagerTools::GetSetting('show_search_header')) ||
        NotEmpty(DownloadManagerTools::GetSetting('display_sections')) ||
        NotEmpty(DownloadManagerTools::GetSetting('display_sort_options'))))
    {
      if(NotEmpty(DownloadManagerTools::GetSetting('show_search_header')))
      {
        if(strlen($this->searchtext) > 0)
        {
          $out .= '<td valign="top"><a class="dlm-search-header-link" href="'.
            $this->currenturl.
            (strpos($this->currenturl,'?')===false?'?':'&amp;').
            $this->HTML_PREFIX.'_pagesize='.$this->pagesize.
            (empty($this->ordertype)?'':$this->URI_TAG.'_ordertype='.$this->ordertype).
            '">' . $section['name'] . '</a> &raquo; ' .
            DownloadManagerTools::GetPhrase('search_results');
        }
        else
        {
          if(NotEmpty(DownloadManagerTools::GetSetting('display_sections')))
          {
            $out .='<td valign="top"><strong>'.$section['name'].'</strong>';
          }
        }
        if($out)
        $out .= '</td>
              <td align="right" style="text-align:right;" valign="top">';
      }
      else
      {
        $out .= '<td valign="top">';
        if(NotEmpty(DownloadManagerTools::GetSetting('display_sections')))
        {
          $out .= '<strong>'.$section['name'].'</strong>';
        }
      }

      // display the number of pages
      if(!isset($this->fileid))
      {
        $out .= $this->dlm_Pagination($this->currentpage, $this->pagesize, $this->sectionid, $this->ordertype);
      }

      if($out)
      {
        echo '
        <table border="0" cellpadding="0" cellspacing="0" class="dlm-search-header" summary="layout" width="100%">
        <tr>
        '.$out . '&nbsp;</td>
        </tr>
        </table>';
      }
      $searchheader_content = '';
      $searchheader_top = '<div id="dlm_search_header">
        <table border="0" cellpadding="0" cellspacing="0" class="dlm-search-table" summary="layout" width="100%" >
        <tr>
        ';
      $td_style = 'style="vertical-align: top" valign="top"';

      // Display sort header
      if(NotEmpty(DownloadManagerTools::GetSetting('display_sort_options')))
      {
        $this->ordertype = empty($this->ordertype) ? 'dateadded DESC' : $this->ordertype;
        $searchheader_content .= '
        <td valign="top">
          <form action="' . $section_link . '" method="post">
          <div class="dlm-searchform">
          '.PrintSecureToken().'
          <input type="hidden" name="'.$this->HTML_PREFIX.'_searchby" value="' . $this->searchby . '" />
          <input type="hidden" name="'.$this->HTML_PREFIX.'_searchtext" value="' . $this->searchtext . '" />
          <table border="0" cellspacing="0" cellpadding="0" summary="layout" width="100%" >
          <tr>
            <td '.$td_style.'>
              ' . DownloadManagerTools::GetPhrase('sort_by') . '
            </td>
            <td '.$td_style.'>
              ' . DownloadManagerTools::GetPhrase('show') . '
            </td>
            <td '.$td_style.'>&nbsp;</td>
          </tr>
          <tr>
            <td style="vertical-align: top" valign="top">
              <div class="dlm-select-div" style="max-width: 80px; width: 80px;">
              <select class="dlm-select" name="'.$this->HTML_PREFIX.'_ordertype" style="max-width: 80px; width: 80px;">
              <option value="dateadded DESC" '.($this->ordertype=='dateadded DESC'?'selected="selected"':'').'>'.
              DownloadManagerTools::GetPhrase('date').'</option>
              <option value="title ASC" '.($this->ordertype=='title ASC'?'selected="selected"':'').'>'.
              DownloadManagerTools::GetPhrase('title').'</option>
              <option value="downloadcount DESC" '.($this->ordertype=='downloadcount DESC'?'selected="selected"':'').'>'.
              DownloadManagerTools::GetPhrase('num_of_downloads').'</option>
              </select></div>
            </td>
            <td '.$td_style.'><div class="dlm-select-div" style="max-width: 50px; width: 50px;">
              <select class="dlm-select" name="'.$this->HTML_PREFIX.'_pagesize" style="max-width: 45px; width: 45px;">
              <option ' . ($this->pagesize == '5' ?'selected="selected"':'') . '>' . DownloadManagerTools::GetPhrase('5') . '</option>
              <option ' . ($this->pagesize == '10'?'selected="selected"':'') . '>' . DownloadManagerTools::GetPhrase('10'). '</option>
              <option ' . ($this->pagesize == '20'?'selected="selected"':'') . '>' . DownloadManagerTools::GetPhrase('20'). '</option>
              </select></div>
            </td>
            <td '.$td_style.'>
              <input class="dlm-button" type="submit" value="' . htmlspecialchars(DownloadManagerTools::GetPhrase('update'),ENT_COMPAT) . '" />
            </td>
          </tr>
          </table>
          </div>
          </form>
        </td>
        ';
      }

      // Display search header
      if(NotEmpty(DownloadManagerTools::GetSetting('show_search_header')))
      {
        $searchheader_content .= '
        <td valign="top">
          <form action="' . $section_link . '" method="post">
          <div class="dlm-searchheader">
          '.PrintSecureToken().'
          <input type="hidden" name="'.$this->HTML_PREFIX.'_ordertype" value="' . $this->ordertype . '" />
          <input type="hidden" name="'.$this->HTML_PREFIX.'_pagesize" value="' . $this->pagesize . '" />
          <table border="0" cellspacing="0" cellpadding="0" summary="layout" width="100%" >
          <tr>
            <td '.$td_style.'>
              ' . DownloadManagerTools::GetPhrase('search_by') . '
            </td>
            <td '.$td_style.'>
              ' . DownloadManagerTools::GetPhrase('search_text') . '
            </td>
            <td '.$td_style.'>&nbsp;</td>
          </tr>
          <tr>
            <td '.$td_style.'><div class="dlm-select-div">
              <select class="dlm-select" name="'.$this->HTML_PREFIX.'_searchby">
              <option value="title" ' . ($this->searchby == 'title' ? 'selected="selected"' : '') . '>' .
                DownloadManagerTools::GetPhrase('title') . '</option>
              <option value="description" ' . ($this->searchby == 'description' ? 'selected="selected"' : '') . '>' .
                DownloadManagerTools::GetPhrase('description') . '</option>
              <option value="tags" ' . ($this->searchby == 'tags' ? 'selected="selected"' : '') . '>' .
                DownloadManagerTools::GetPhrase('dlm_tags') . '</option>
              <option value="author" ' . ($this->searchby == 'author' ? 'selected="selected"' : '') . '>' .
                DownloadManagerTools::GetPhrase('author_name') . '</option>
              </select></div>
            </td>
            <td '.$td_style.'>
              <input class="dlm-input" name="'.$this->HTML_PREFIX.'_searchtext" type="text" value="' . $this->searchtext . '" />
            </td>
            <td '.$td_style.'>
              <input class="dlm-button" type="submit" value="' . htmlspecialchars(DownloadManagerTools::GetPhrase('search'),ENT_COMPAT) . '" />
            </td>
          </tr>
          </table>
          </div>
          </form>
        </td>';
      }
      if(!empty($searchheader_content))
      {
        echo $searchheader_top .
        $searchheader_content .
        '
        </tr>
        </table>
        </div>
        ';
      }
    }  // end display header if there are file(s)

    // now display the file(s)

    if(isset($this->fileid))  // only display one file
    {
      $rows = 1;
    }

    // ###################### CREATE SMARTY TEMPLATE OBJECT ###################
    // Instantiate smarty object
    //SD342 use new SD's Smarty class
    //$smarty = SD_Smarty::getNew($this->DLM_PATH.'smarty',SD_INCLUDE_PATH.'tmpl'.'/comp',SD_INCLUDE_PATH.'tmpl'.'/cache');
    $smarty = SD_Smarty::getNew(false); //SD344
    $smarty->setTemplateDir($this->DLM_PATH.'smarty'); //SD370: setter method
    $smarty->assign('AdminAccess',   ($userinfo['adminaccess'] || (!empty($userinfo['pluginadminids']) && @in_array($this->pluginid, $userinfo['pluginadminids']))));
    $smarty->assign('pluginid',      $this->pluginid);
    $smarty->assign('sdlanguage',    $sdlanguage);
    $smarty->assign('sdurl',         SITE_URL);
    $smarty->assign('securitytoken', $userinfo['securitytoken']);
    $smarty->assign('details_page',  DownloadManagerTools::GetSetting('enable_details_page'));

    // Condition in "FOR", checking against "$this->pagesize", doesn't work right
    // as it did not take into account the actual displayed count of files:
    // since any section/file might have been restricted by usergroup-/user-id's,
    // the loop counter does not represent the displayed items.
    // Therefore added special check by use of "$EntriesCount".
    $EntriesCount = 0;
    $current_secid = $this->sectionid;
    for($i = 0; (($i < $rows) && ($EntriesCount < $this->pagesize)); $i++)
    {
      if(!isset($this->fileid))
      {
        $file = $DB->fetch_array($getfiles,null,MYSQL_ASSOC);
      }
      if(isset($file['sectionid']))
      {
        $this->sectionid = (int)$file['sectionid'];
      }

      //v2.3.0: load tags from core "tags" table
      $file['tags'] = '';
      $tags = SD_Tags::GetPluginTags($this->pluginid, $file['fileid']);
      if(!empty($tags) && is_array($tags))
      {
        $file['tags'] = implode(',', $tags);
      }

      $back_link = null;
      if(DownloadManagerTools::dlm_HasAccess($file))
      {
        // Prepare reusable variables
        $back_tag = '<a href="' . $section_link. '">';
        $back_link = $back_tag . '&laquo; '.DownloadManagerTools::GetPhrase('back').'</a>';
        $fileisimage = (substr($file['filetype'],0,6) == 'images/') &&
                       (strlen(DownloadManagerTools::dlm_HasFileImageExt($file['filename']))>0);
        $EntriesCount++;

        // ##################################################################
        // Include File Layout
        // ##################################################################
        $layoutfile = ROOT_PATH.$this->DLM_PATH.'dlm_layout.php';
        global $dlm_currentdir;
        @include($layoutfile);

        // **************************************************************
        // ***** Common footer section for any layout containing
        // ***** previous file versions and comments
        // **************************************************************

        // For single-file display now show previous files and comments
        if(!empty($this->fileid))
        {
          // Files that are of type <> "Default" override any setting to hide "versions":
          $AddLF = //!$file['is_embeddable_media'] &&
                   ((!empty($file['standalone']) || DownloadManagerTools::GetSetting('display_previous_versions')) &&
                   $this->dlm_DisplayFileVersions($file,$this->sectionid,false)) ? '<br />' : '';

          // if we are viewing a specific file, then show comments (if enabled)
          if(DownloadManagerTools::GetVar('allow_admin') || !empty($userinfo['commentaccess']) || NotEmpty(DownloadManagerTools::GetSetting('allow_user_comments')))
          {
            echo DownloadManagerTools::dlm_GetSeparator($AddLF, true);
            $Comments->plugin_id = $this->pluginid;
            $Comments->object_id = $this->fileid;
            $Comments->url = $this->URI_TAG.'_sectionid='.$this->sectionid.$this->URI_TAG.'_fileid='.$this->fileid;
            $Comments->DisplayComments();
          }
        }
        else
        {
          // Show all file "versions" for "Standalone" files also in the main Downloads list page
          if($file['standalone'] == '1' && !$file['is_embeddable_media'])
          {
            $this->dlm_DisplayFileVersions($file,$this->sectionid,false);
          }
        }

        // Display separator line (could be an image or just an <hr> tag)
        if(empty($this->fileid) && (($EntriesCount>1) || ((($i + 1) < $rows) && (($EntriesCount + 1) < $this->pagesize)) ))
        {
          echo DownloadManagerTools::dlm_GetSeparator('', true);
        }

      } // checking of dlm_HasAccess

    }  // display file(s)

    $this->sectionid = $current_secid;

    // Display "Back to section" - link
    if(isset($this->fileid))
    {
      if(isset($back_link))
      {
        echo '<div style="padding-left: 0px;">' . $back_link . '</div>';
      }
    }
    else
    {
      if(!empty($cloud_html) && !empty($cloud_loc))
      {
        echo $cloud_html;
      }

      // Activate footer display in case it's actually off by configuration,
      // *BUT* there are more files to show...
      $ShowFooter = DownloadManagerTools::GetSetting('show_footer');
      if(!$ShowFooter || !NotEmpty(DownloadManagerTools::GetSetting('display_sections')))
      {
        if(!($ShowFooter) && (($i < $rows) || ($EntriesCount >= $this->pagesize) || ($this->currentpage>1)))
        {
          $ShowFooter = 1;
        }
      }

      // Display footer if there are file(s) being displayed
      if(($ShowFooter == 1) && ($rows > 0 || strlen($this->searchtext) > 0))
      {
        echo '<br />
        <table border="0" cellspacing="0" cellpadding="5" summary="layout" ' . $tr_main . ' width="100%" >
        <tr>
          <td>'. DownloadManagerTools::GetPhrase('section') . ' <strong>'.$section['name'] . '</strong></td>
          <td align="right">'.
        $this->dlm_Pagination($this->currentpage, $this->pagesize, $this->sectionid, $this->ordertype).
        '</td></tr>
        </table>';
      }
    }

  } //DisplayFiles


  // ############################## DISPLAY LICENSE ##############################

  function dlm_DisplayLicense($sectionid, $fileid, $versionid = -1)
  {
    global $DB, $categoryid;

    $res = DownloadManagerTools::RewriteFileLink($this->currenturl, $fileid, sd_unhtmlspecialchars($this->file_arr['title']));
    echo '
      <table>
          <tr>
        <td colspan="2" align="center">' . DownloadManagerTools::GetPhrase('must_agree') . '<br /><br /></td>
      </tr>
      <tr>
        <td colspan="2" align="center"><textarea cols="80" rows="25">' . DownloadManagerTools::GetSetting('license_agreement') . '</textarea></td>
      </tr>
      <tr>
        <td colspan="2" align="center">
        <input type="button" value="' . DownloadManagerTools::GetPhrase('agree') . '" onClick="location.href=\'' .
          SITE_URL.$this->GETFILE.$categoryid.
          $this->URI_TAG.'_sectionid='.$sectionid.
          $this->URI_TAG.'_fileid='.$fileid.
          $this->URI_TAG.'_versionid='.$versionid . '\'" />&nbsp;&nbsp;
        <input type="button" value="' . htmlspecialchars(DownloadManagerTools::GetPhrase('disagree'),ENT_COMPAT) . '" onClick="location.href=\'' .
          #RewriteLink('index.php?categoryid='.$categoryid.$this->URI_TAG.'_sectionid='.$sectionid. $this->URI_TAG.'_fileid=' . $fileid) .
          $res['link'].
        '\'" />
        </td>
      </tr>
      </table>';
  } //dlm_DisplayLicense


  // ################################## SHOW ##################################

  function Show()
  {
    global $userinfo;

    if(!DownloadManagerTools::DMT_Init())
    {
      return false;
    }

    // ########################### VALIDATE VARIABLES #########################
    $this->currentpage = GetVar($this->HTML_PREFIX.'_currentpage', 1, 'whole_number');
    $this->ordertype   = GetVar($this->HTML_PREFIX.'_ordertype', '', 'string');
    $this->ordertype   = (in_array($this->ordertype, array('dateadded DESC','title ASC','downloadcount DESC'))?$this->ordertype:'');
    $this->pagesize    = GetVar($this->HTML_PREFIX.'_pagesize', DownloadManagerTools::GetSetting('page_size'), 'whole_number');
    $this->pagesize    = (in_array($this->pagesize, array('5','10','20','50',DownloadManagerTools::GetSetting('page_size')))?$this->pagesize:DownloadManagerTools::GetSetting('page_size'));
    $this->searchby    = GetVar($this->HTML_PREFIX.'_searchby', 'title', 'string');
    $this->searchby    = (in_array($this->searchby, array('author','description','tags','title'))?$this->searchby:'title');

    // fix spaces
    $this->ordertype   = str_replace('%20', ' ', $this->ordertype);
    $this->searchtext  = GetVar($this->HTML_PREFIX.'_searchtext', '', 'html'); #2012-02-06: "html"
    $this->searchtext  = str_replace('%20', ' ', $this->searchtext);
    #2012-02-06: clean search term (no HTML) with detection, then entities again
    $this->searchtext  = substr(rtrim(ltrim(SanitizeInputForSQLSearch($this->searchtext,true,false,true))),0,200);
    $this->searchtext  = htmlentities($this->searchtext);

    //v2.2: if SEO detection was successful, do not fetch sectionid/fileid again
    if(!$this->seo_detected)
    {
      $this->sectionid = Is_Valid_Number(GetVar($this->HTML_PREFIX.'_sectionid', 1, 'whole_number'),1,0,999999999);
      $this->fileid    = Is_Valid_Number(GetVar($this->HTML_PREFIX.'_fileid', 0, 'whole_number'),0,1,999999999);
    }
    $this->versionid   = Is_Valid_Number(GetVar($this->HTML_PREFIX.'_versionid', -1, 'whole_number'),-1,1,999999999);

    // ########################## BEGIN PRINTING THE PAGE #####################

    if($this->ACTION == 'delfileversion' && ($this->fileid > 0) && ($this->versionid > 0))
    {
      if(DownloadManagerTools::dlm_DeleteFileVersion($this->fileid, $this->versionid))
      {
        echo '<p><strong>' . DownloadManagerTools::GetPhrase('delete_fileversion_success') . '</strong></p>';
      }
      $this->ACTION = false;
    }

    if((($this->ACTION == 'insertfile') || ($this->ACTION == 'updatefile')) &&
        (DownloadManagerTools::GetVar('allow_submit') || DownloadManagerTools::GetVar('allow_admin')))
    {
      // Replaced old "pxx_InsertFile" functions because "UpdateFile" now supports
      // both to post edited and inserted file.
      if(DownloadManagerTools::UpdateFile($this->sectionid, $this->fileid, $this->versionid))
      {
        unset($this->fileid);
      }
      else
      {
        $this->ACTION = 'submitfileform';
        $this->dlm_DisplayEditFileForm($this->sectionid, $this->fileid, $this->versionid);
      }
      return true;
    }

    if(isset($_GET[$this->HTML_PREFIX.'_error']))
    {
      $this->dlm_PrintError($_GET[$this->HTML_PREFIX.'_error']);
    }
    else if(($this->ACTION == 'submitfileform') && (DownloadManagerTools::GetVar('allow_submit') || DownloadManagerTools::GetVar('allow_admin')))
    {
      // Enhanced submit form for user: supports both submitting as well as editing an existing file.
      $this->dlm_DisplayEditFileForm($this->sectionid,$this->fileid,$this->versionid,null);
    }
    else if($this->ACTION == 'license')
    {
      $this->dlm_DisplayLicense($this->sectionid, $this->fileid, $this->versionid);
    }
    else
    {
      // Show special header for "Guests"
      $out = DownloadManagerTools::GetSetting('downloads_header_guests');
      if(strlen(trim($out)) && empty($userinfo['adminaccess']) && empty($userinfo['loggedin']))
      {
        echo $out;
      }
      $this->dlm_DisplayFiles();
    }
  } //Show

} //end of class
} //loaded - never remove!
