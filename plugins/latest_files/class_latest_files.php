<?php

if(!defined('IN_PRGM')) exit();

if(!class_exists('LatestFilesClass'))
{
class LatestFilesClass
{
  public $pluginid      = 0;
  public $plugin_folder = '';
  public $language      = array();
  public $settings      = array();
  public $src_pluginid  = 0; //SD362

  function __construct($plugin_folder)
  {
    if($this->pluginid = GetPluginIDbyFolder($plugin_folder))
    {
      $this->plugin_folder = $plugin_folder;
      $this->settings = GetPluginSettings($this->pluginid);
      $this->language = GetLanguage($this->pluginid);
      $this->SetSourcePluginID();
    }
  }

  function SetSourcePluginID($pluginid=0) //SD362
  {
    if(!empty($pluginid) && ($pluginid = Is_Valid_Number($pluginid, 0, 13, 999999)))
    {
      $this->src_pluginid = (int)$pluginid;
      return;
    }
    $this->src_pluginid = $this->settings['source_plugin'];
  }

  public static function GetDLMVersion($dlm_pluginid, $major, $minor)
  {
    global $DB;

    if($version = $DB->query_first('SELECT version FROM {plugins} WHERE pluginid = %d',$dlm_pluginid))
    {
      // Drop the major number
      $ver = explode('.', $version[0]);
      if(count($ver) >= 1 && $ver[0] >= $major)
      {
        if(count($ver) >= 2 && $ver[1] >= $minor)
        {
          return true;
        }
      }
    }
    return false;

  } //GetDLMVersion


  // ############################ DISPLAY LATEST FILES ###########################

  public function DisplayLatestFiles($current_user=false)
  {
    //SD362: added parameters to limit articles for either current user
    // or the specified user id; functionality used by User Profile
    global $DB, $categoryid, $plugin_folder_to_id_arr, $plugin_names, $pluginids, $userinfo;

    if(empty($this->pluginid) || empty($this->src_pluginid) ||
       !($getcategory = $DB->query_first('SELECT categoryid FROM {pagesort} WHERE pluginid = %d LIMIT 1',$this->src_pluginid)))
    {
      return false;
    }

    //SD362: param used by User Profile plugin
    $current_user = !empty($current_user);

    // categoryid for download manager plugin
    $dlm_categoryid = $getcategory['categoryid'];
    $cat_link   = RewriteLink('index.php?categoryid='.$dlm_categoryid);
    $extraquery = '';

    // Exclude Sections?
    if(strlen($this->settings['exclude_sections']) > 0)
    {
      // Process "exclude" IDs and check values
      $excludeids = str_replace(',', ' ', $this->settings['exclude_sections']);
      $excludeids = preg_split('/[\s+]/', $excludeids, -1, PREG_SPLIT_NO_EMPTY);

      $sections = array();
      for($i = 0; $i < count($excludeids); $i++)
      {
        if(Is_Valid_Number($excludeids[$i], 0, 1, 99999999))
        {
          $sections[] = $excludeids[$i];
        }
      }
      if(count($sections))
      {
        $extraquery = ' AND sf.sectionid NOT IN ('. implode(',', $sections).') ';
      }
      unset($excludeids, $sections);
    }

    if($isNewDLM = self::GetDLMVersion($this->src_pluginid, 2, 0))
    {
      $extraquery .= '
      AND ((IFNULL(f.access_groupids,"") = "") OR (f.access_groupids like "%|'.$userinfo['usergroupid'].'|%"))
      AND ((IFNULL(f.access_userids,"")  = "") OR (f.access_userids like "%|'.$userinfo['userid'].'|%"))
      ';
    }

    //SD362: allow for current user or specified user
    if($current_user)
    {
      $extraquery .= " AND (f.author = '".$userinfo['username']."')";
    }

    $maxfiles = empty($this->settings['number_of_files_to_display']) ? 5 : intval($this->settings['number_of_files_to_display']);
    $maxfiles = Is_Valid_Number($maxfiles, 5, 1, 999);

    //SD370: support for SEO URLs in newewst DLM version
    $old_categoryid = $categoryid;
    $doSEO = false;
    $dlm_settings = GetPluginSettings($this->src_pluginid);
    if(!empty($dlm_settings['enable_seo']) && !empty($plugin_folder_to_id_arr))
    {
      // If DLM is not on current page, find first page with it:
      $new_page = $dlm_categoryid;
      if(!isset($pluginids) || !in_array($this->src_pluginid, $pluginids))
      {
        $new_page = GetPluginCategory($this->src_pluginid, $dlm_categoryid);
        if(!empty($new_page)) $GLOBALS['categoryid'] = $new_page;
      }
      $tmp = array_flip($plugin_folder_to_id_arr);

      // Need to check, if source pluginid is installed and a DLM instance:
      if( !empty($new_page) && isset($tmp[$this->src_pluginid]) &&
          isset($plugin_names['base-'+$this->src_pluginid]) &&
          ($plugin_names['base-'+$this->src_pluginid] = 'Download Manager') )
      {
        global $sd_instances;

        // Check if there's a global instance already existing (the case
        // if source plugin is on same page):
        if(isset($sd_instances) && isset($sd_instances[$this->src_pluginid]))
        {
          $dlm_base = $sd_instances[$this->src_pluginid];
        }
        else
        {
          // DLM requires global "dlm_currentdir" to be set; save previous value:
          $old_dir = isset($GLOBALS['dlm_currentdir']) ? $GLOBALS['dlm_currentdir'] : false;
          $GLOBALS['dlm_currentdir'] = $dlm_currentdir = $tmp[$this->src_pluginid];
          $dlm_path = ROOT_PATH.'plugins/'.$tmp[$this->src_pluginid].'/';

          // now include once the DLM class files:
          @include_once($dlm_path.'class_dlm.php');
          @include_once($dlm_path.'class_dlm_tools.php');
          $dlm_base = new DownloadManager($dlm_currentdir);
          if($old_dir !== false)
          {
            $GLOBALS['dlm_currentdir'] = $old_dir;
          }
        }

        // Initiate DLM and check if class exists:
        if($dlm_base->Init($this->src_pluginid) && class_exists('DownloadManager'))
        {
          $doSEO = true;
        }
        unset($dlm_base,$tmp);
      }
    }

    $files = $DB->query('SELECT DISTINCT f.fileid, f.title, f.author,
    sf.sectionid, s.name section_title,
    IF(IFNULL(dateupdated,0)=0,dateadded,dateupdated) filedate
    FROM {p'.$this->src_pluginid.'_files} f
    INNER JOIN {p'.$this->src_pluginid.'_file_sections} sf ON sf.fileid = f.fileid
    INNER JOIN {p'.$this->src_pluginid.'_sections} s ON s.sectionid = sf.sectionid
    WHERE f.activated = 1 AND s.activated = 1
    AND (IFNULL(f.datestart,0) < UNIX_TIMESTAMP())
    AND ((IFNULL(f.dateend,0) = 0) OR (f.dateend > UNIX_TIMESTAMP()))
    '.$extraquery.'
    GROUP BY f.fileid
    ORDER BY IF(IFNULL(f.dateupdated,0)=0,f.dateadded,f.dateupdated) DESC LIMIT 0, '.$maxfiles);

    $header = false;
    $filedateformat = empty($this->settings['file_date_format'])?'':$this->settings['file_date_format'];
    while($file = $DB->fetch_array($files))
    {
      if(!$header)
      {
        $header = true;
        echo '
    <div id="latestfiles-p'.$this->pluginid.'">
    <table border="0" cellspacing="0" cellpadding="0" summary="layout" width="100%">';
      }
      $link = false;
      if($doSEO &&
         ($tmp = DownloadManagerTools::RewriteSectionLink($cat_link, $file['sectionid'], $file['section_title'])))
      {
        $tmp = DownloadManagerTools::RewriteFileLink($tmp, $file['fileid'], $file['title']);
        if(is_array($tmp) && isset($tmp['link']))
        {
          $link = $tmp['link'];
        }
      }

      if(!$doSEO || !$link)
      {
        $link = RewriteLink('index.php?categoryid='.$dlm_categoryid.
                            '&p'.$this->src_pluginid.'_sectionid='.$file['sectionid'].
                            '&p'.$this->src_pluginid.'_fileid='.$file['fileid']);
      }
      echo '
      <tr><td style="padding-bottom: 10px;"><a href="'.$link.'">'.
        (empty($file['title']) ? $this->language['untitled'] : $file['title']).'</a>';

      if($this->settings['display_author_name'] && !empty($file['author']))
      {
        echo ($this->settings['display_author_name_on_new_line'] ? '<br />' : ' - ').
             $this->language['by'] . ' ' . $file['author'];
      }
      if($this->settings['display_file_date'])
      {
        echo '<br />'.DisplayDate($file['filedate'],$filedateformat);
      }
      echo '
        </td>
        </tr>';

    } //while

    if($header)
    {
      echo '</table>
    </div>
    ';
    }
    $GLOBALS['categoryid'] = $old_categoryid;

  } //DisplayLatestFiles

} // End of Class

} // DO NOT REMOVE
