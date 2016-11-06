<?php
if(!defined('IN_PRGM')) return;

defined('SD_MG_MEM_LIMIT') || define('SD_MG_MEM_LIMIT','128M');
defined('SD_MG_MAX_FILESIZE') || define('SD_MG_MAX_FILESIZE', 8388608); #8MB
defined('GALLERY_MAX_DIM') || define('GALLERY_MAX_DIM', 8192);
defined('GALLERY_MAX_DISP_MODE') || define('GALLERY_MAX_DISP_MODE', 7);

if(!defined('SD_GD_SUPPORT'))
{
  // Pre-check if "GD" module is installed and provides support:
  $testGD = get_extension_funcs('gd');
  $gd2 = ($testGD !== false) &&
         in_array('imagecreatetruecolor', $testGD) &&
         function_exists('imagecreatetruecolor');
  define('SD_GD_SUPPORT', $gd2);
  unset($testGD, $gd2);
}

require_once(SD_INCLUDE_PATH.'class_sd_tags.php');

if(!class_exists('GalleryBaseClass'))
{
class GalleryBaseClass
{
  public $MD_PREFIX     = 'md_';
  public $TB_PREFIX     = 'tb_';
  public $PLUGINDIR     = '';
  public $IMAGEDIR      = '';
  public $UPLOADDIR     = '';
  public $IMAGEPATH     = '';
  public $UPLOADPATH    = '';
  public $IMAGEURL      = '';
  public $language      = '';
  public $settings      = '';
  public $pluginid      = 0;
  public $allow_bbcode  = false;
  public $pre           = '';
  public $defaultimg    = 'defaultfolder.png';
  public $section_perm  = null;
  //SD360: SEO enhancements
  public $cache_on      = false;
  public $seo_detected  = false;
  public $seo_enabled   = true;
  public $seo_cache     = false;
  public $seo_redirect  = true;
  public $seo_section_detected = false;
  public $gal_cache     = false;
  public $imageid       = 0;
  public $sectionid     = 0;
  public $categoryid    = 0;
  public $current_page  = '';
  public $plugin_page   = '';
  public $AllowSubmit   = false;
  public $IsAdmin       = false;
  public $IsModerator   = false;
  public $IsSiteAdmin   = false;
  public $GroupCheck    = '';
  public $plugin_folder = '';
  public $default_display_mode = 0;
  public $image_arr     = array();
  public $section_arr   = array();
  public $sections_tbl  = '';
  public $images_tbl    = '';
  public $error_arr     = array();
  public $InitStatus    = false;
  public $adm_maxThumbH = 100;
  public $adm_maxThumbW = 100;
  public $tmpl          = false;
  public $allow_mod     = false;
  //SD362
  public $allow_media_delete   = false;
  public $allow_media_edit     = false;
  public $allow_section_delete = false;
  public $allow_section_edit   = false;
  public $is_section_owner     = false;
  public $is_media_owner       = false;
  public $slug_arr             = array();
  public $doSearch             = false;
  public $SearchTerm           = '';
  public $isTagPage            = false;
  public $allow_files          = false;
  public $allow_links          = false;
  public $allowed_media_str    = '';

  public function __construct($plugin_folder)
  {
    global $DB, $bbcode, $mainsettings, $mainsettings_modrewrite,
           $SDCache, $userinfo;

    require_once(SD_INCLUDE_PATH.'class_sd_media.php');

    $this->pluginid = GetPluginIDbyFolder($plugin_folder);
    if(empty($this->pluginid))
    {
      return false;
    }
    $this->plugin_folder = $plugin_folder;
    $this->pre           = 'p'.$this->pluginid;

    $this->language = GetLanguage($this->pluginid);
    $this->settings = GetPluginSettings($this->pluginid);

    $this->PLUGINDIR  = 'plugins/'.$this->plugin_folder.'/';
    $this->IMAGEDIR   = $this->PLUGINDIR . 'images/';
    $this->UPLOADDIR  = $this->PLUGINDIR . 'upload/';
    $this->IMAGEPATH  = ROOT_PATH . $this->IMAGEDIR;
    $this->UPLOADPATH = ROOT_PATH . $this->UPLOADDIR;
    $this->IMAGEURL   = SITE_URL  . $this->IMAGEDIR;

    $this->sections_tbl = PRGM_TABLE_PREFIX.'p'.$this->pluginid.'_sections';
    $this->images_tbl   = PRGM_TABLE_PREFIX.'p'.$this->pluginid.'_images';

    $this->allow_bbcode = isset($bbcode) && ($bbcode instanceof BBCode) &&
                          !empty($mainsettings['allow_bbcode']);

    $this->IsSiteAdmin = !empty($userinfo['loggedin']) &&
                         !empty($userinfo['userid']) &&
                         !empty($userinfo['adminaccess']);
    $this->IsAdmin     = !empty($userinfo['loggedin']) &&
                         !empty($userinfo['userid']) &&
                         !empty($userinfo['pluginadminids']) &&
                         @in_array($this->pluginid, $userinfo['pluginadminids']);
    $this->IsModerator = !empty($userinfo['loggedin']) &&
                         !empty($userinfo['pluginmoderateids']) &&
                         @in_array($this->pluginid, $userinfo['pluginmoderateids']);
    $this->AllowSubmit = (!empty($userinfo['pluginsubmitids']) &&
                          @in_array($this->pluginid, $userinfo['pluginsubmitids']));
    $this->allow_mod   = $this->IsSiteAdmin || $this->IsAdmin || $this->IsModerator;

    $this->default_display_mode = (int)$this->settings['image_display_mode'];

    $this->section_perm = $DB->column_exists(PRGM_TABLE_PREFIX.
                                             $this->pre.'_sections',
                                             'access_view');
    $this->cache_on = !empty($SDCache) && ($SDCache instanceof SDCache) && $SDCache->IsActive();

    $this->seo_enabled = !empty($mainsettings_modrewrite) &&
                         !empty($this->settings['enable_seo']);

    // must be set AFTET "section_perm"!
    $this->GroupCheck =
      ' AND ((IFNULL(s.publish_start,0) = 0) OR (s.publish_start <= '.TIME_NOW.'))'.
      ' AND ((IFNULL(s.publish_end,0) = 0) OR (s.publish_end >= '.TIME_NOW.'))';
    if(!$this->IsSiteAdmin && !$this->IsAdmin)
    {
      // the usergroup id MUST be enclosed in pipes!
      $this->GroupCheck .= $this->GetUserSectionAccessCondition();
    }

    $this->allowed_media_str = '';
    $allowed_media = isset($this->settings['allow_media_site_links'])?
                     $this->settings['allow_media_site_links']:false;
    if(($allowed_media !== false) && !empty($allowed_media))
    {
      $allowed_media = sd_ConvertStrToArray($allowed_media,',');
      $known_media_sites = array_flip(SD_Media_Base::$known_media_sites);
      // Build string of site names, comma-separated:
      foreach($allowed_media as $entry)
      {
        if(isset($known_media_sites[$entry]))
        {
          $this->allowed_media_str .= ucwords($known_media_sites[$entry]).', ';
        }
      }
      $this->allowed_media_str = rtrim($this->allowed_media_str,' ,');
    }

  } //GalleryBaseClass

  // ##########################################################################

  public function GetUserSectionAccessCondition() //SD343
  {
    global $userinfo;

    if(empty($userinfo)) return 'false'; // as string!

    if(empty($this->section_perm)) return '';

    //SD362: fixed permissions (2nd row condition was incorrectly an "OR")
    $tmp = ' AND (((IFNULL(s.owner_access_only,0) = 0) OR (s.owner_id = '.(int)$userinfo['userid'].'))'.
           " AND (((IFNULL(s.access_view,'') = '') OR".
                " ((s.access_view like '%|".$userinfo['usergroupid']."|%')";

    if(!empty($userinfo['usergroupids']) && is_array($userinfo['usergroupids']))
    {
      foreach($userinfo['usergroupids'] as $gid)
      {
        if($gid != $userinfo['usergroupid'])
        {
          $tmp .= " OR (s.access_view like '%|".$gid."|%')";
        }
      }
    }
    $tmp .= '))))';
    return $tmp;

  } //GetUserSectionAccessCondition

  // ##########################################################################

  public function GetUserActionKey($id)
  {
    global $userinfo;

    if(!isset($userinfo)) return false;
    return 't'.md5(md5($userinfo['sessionid'].$this->sectionid).'-'.$id);
  }

  // ##########################################################################

  public function CheckAccessForSection($section)
  {
    global $userinfo;

    if(!isset($userinfo)) return false;

    if(empty($this->section_perm)) return true;

    $ok = ( empty($section['owner_access_only']) ||
            ($section['owner_id'] == (int)$userinfo['userid']) ) &&
          ( empty($section['access_view']) ||
            (false !== strpos($section['access_view'],'|'.$userinfo['usergroupid'].'|')) );

    if($ok && !empty($section['access_view']) &&
       !empty($userinfo['usergroupids']) && is_array($userinfo['usergroupids']))
    {
      $grp_ok = false;
      foreach($userinfo['usergroupids'] as $gid)
      {
        if( (false !== strpos($section['access_view'],'|'.$gid.'|')) )
        {
          $grp_ok = true;
          break;
        }
      } //foreach
      if(!$grp_ok) $ok = false;
    }

    return $ok;

  } //CheckAccessForSection

  // ##########################################################################

  public function CheckAllowedMediaType($media_url) //SD362
  {
    global $userinfo;

    $type = SD_Media_Base::isMediaUrlValid($media_url,false);
    if($type === false) return $type;
    if(!empty($userinfo['loggedin']) && ($this->IsAdmin || $this->IsSiteAdmin))
    {
      return isset(SD_Media_Base::$known_media_sites[$type]);
    }

    $result = false;
    $allowed_media = isset($this->settings['allow_media_site_links'])?
                     $this->settings['allow_media_site_links']:false;
    if(($allowed_media !== false) && !empty($allowed_media))
    {
      $allowed_media = sd_ConvertStrToArray($allowed_media,',');
      $site_id = SD_Media_Base::$known_media_sites[$type];
      $result = in_array($site_id, $allowed_media);
    }
    return $result;
  }

  // ##########################################################################

  public function InitTemplate() //SD360
  {
    global $categoryid, $inputsize, $mainsettings_enable_rss,
           $sdlanguage, $userinfo, $bbcode;

    // Instantiate smarty object
    include_once(SD_INCLUDE_PATH.'class_sd_smarty.php');
    $this->tmpl = SD_Smarty::getInstance();

    $this->tmpl->assign('user_is_admin', $this->IsSiteAdmin || $this->IsAdmin);
    $this->tmpl->assign('enable_rss',    !empty($mainsettings_enable_rss));
    $this->tmpl->assign('categoryid',    $categoryid);
    $this->tmpl->assign('sdlanguage',    $sdlanguage);
    $this->tmpl->assign('sdurl',         SITE_URL);
    $this->tmpl->assign('page_link',     RewriteLink('index.php?categoryid='.$categoryid));
    $this->tmpl->assign('page_title',    (defined('SD_CATEGORY_TITLE')?SD_CATEGORY_TITLE:''));
    $this->tmpl->assign('securitytoken', $userinfo['securitytoken']);
    $this->tmpl->assign('token_element', PrintSecureToken());#$this->pre.'_token'
    $this->tmpl->assign('pluginid',      $this->pluginid);
    $this->tmpl->assign('plugin_folder', $this->plugin_folder);
    $this->tmpl->assign('prefix',        $this->pre);
    if(!isset($this->language['no_image_available']))
    {
      $this->language['no_image_available'] = 'No images available';
    }
    $this->tmpl->assign('language',      $this->language);
    $this->tmpl->assign('settings',      $this->settings);

    $this->tmpl->assign('plugindir',     $this->PLUGINDIR);
    $this->tmpl->assign('imagedir',      $this->IMAGEDIR);
    $this->tmpl->assign('images_path',   $this->IMAGEPATH);
    $this->tmpl->assign('images_url',    $this->IMAGEURL);
    $this->tmpl->assign('uploaddir',     $this->UPLOADDIR);
    $this->tmpl->assign('uploadpath',    $this->UPLOADPATH);

    $this->tmpl->assign('user_is_guest', empty($userinfo['loggedin']));
    $this->tmpl->assign('user_name',     (empty($userinfo['username'])?'':$userinfo['username']));
    $this->tmpl->assign('inputsize',     $inputsize);

    $this->tmpl->assign('rating_form', '');
    $this->tmpl->assign('imageid', 0);
    $this->tmpl->assign('image', array());
    $this->tmpl->assign('section', array());
    $this->tmpl->assign('sectionid', 0);
    $this->tmpl->assign('sections', array());

    //SD360: social media links for section:
    $this->section_arr['social_title'] = urlencode($this->section_arr['name']);
    $this->section_arr['social_url'] = urlencode($this->current_page);

    $tmp = str_replace('[item]', $this->section_arr['name'], $sdlanguage['social_link_delicious']);
    $this->section_arr['social_delicious_title'] = htmlspecialchars($tmp,ENT_COMPAT);

    $tmp = str_replace('[item]', $this->section_arr['name'], $sdlanguage['social_link_digg']);
    $this->section_arr['social_digg_title'] = htmlspecialchars($tmp,ENT_COMPAT);

    $tmp = str_replace('[item]', $this->section_arr['name'], $sdlanguage['social_link_facebook']);
    $this->section_arr['social_facebook_title'] = htmlspecialchars($tmp,ENT_COMPAT);

    $tmp = str_replace('[item]', $this->section_arr['name'], $sdlanguage['social_link_twitter']);
    $this->section_arr['social_twitter_title'] = htmlspecialchars($tmp,ENT_COMPAT);

    // Apply BBCode parsing to section description
    if(!empty($this->section_arr))
    {
      $this->section_arr['description_org'] = $this->section_arr['description'];
      if( empty($this->imageid) && !empty($this->sectionid) && $this->allow_bbcode )
      {
        $bbcode->SetDetectURLs(false);
        $bbcode->url_targetable = true;
        $bbcode->SetURLTarget('_blank');
        $this->section_arr['description'] = $bbcode->Parse($this->section_arr['description']);
      }
      $this->tmpl->assign('section', $this->section_arr);
    }
    else
    {
      $this->tmpl->assign('section', array());
      $this->tmpl->assign('submit_href', false);
      return;
    }

    $extensions = str_replace('#extensions#', $this->settings['allowed_image_types'], $this->language['image_submit_hint1']);
    $max_upload_size = str_replace('#size#', DisplayReadableFilesize($this->settings['max_upload_size']), $this->language['image_submit_hint2']);
    $this->tmpl->assign('allowed_extensions', $extensions);
    $this->tmpl->assign('max_upload_size', $max_upload_size);

    // Process allowed media site types (if any) and pass to template
    $this->tmpl->assign('allowed_media_sites', $this->allowed_media_str);

    // Determine if user upload is allowed in current section
    $submit_link = '';
    $submit = ($this->sectionid > 0) &&
              (($this->IsAdmin || $this->IsSiteAdmin) ||
               ($this->AllowSubmit &&
                ($this->allow_files || $this->allow_links) &&
                (!empty($this->settings['allow_root_section_upload']) ||
                 ($this->sectionid > 1))));
    if($submit)
    {
      $submit_link = $this->RewriteSectionLink($this->sectionid, false, 1, $this->pre.'_action=submitimage');
    }
    $this->tmpl->assign('submit_href', $submit_link);
    $this->tmpl->assign('allow_files', $this->allow_files);
    $this->tmpl->assign('allow_links', $this->allow_links);
  } //InitTemplate

  // ##########################################################################

  public function InitFrontpage($checkCache=true)
  {
    // Init() must be called separately after extra properties are set
    $this->InitStatus = false;
    if(!$this->pluginid) return false;

    global $DB, $categoryid, $mainsettings, $mainsettings_modrewrite,
           $mainsettings_url_extension, $mainsettings_tag_results_page,
           $pluginids, $sd_url_params, $sd_variable_arr, $uri, $userinfo;

    $result = false;
    //SD362: fixed page detection with plugin on multiple pages; previously
    // it would always point to the first page found, which could be different
    // from the current one, doh!
    if(isset($pluginids) && is_array($pluginids) && in_array($this->pluginid,$pluginids))
      $this->categoryid = $categoryid;
    else
    if($gal_pages = $DB->query('SELECT categoryid FROM {pagesort}'.
                               " WHERE (pluginid = '%d')".
                               ' AND (categoryid <> %d)',
                               $this->pluginid, $mainsettings_tag_results_page))
    {
      while($tmp = $DB->fetch_array($gal_pages,null,MYSQL_ASSOC))
      {
        if($tmp['categoryid']==$categoryid)
        {
          $this->categoryid = $categoryid;
          break;
        }
      }
      unset($gal_pages,$tmp);
    }

    if(defined('IN_ADMIN') || empty($categoryid))
    {
      $this->categoryid = $categoryid = 1;
    }

    $this->current_page = RewriteLink('index.php?categoryid='.$this->categoryid);
    $this->plugin_page = $this->current_page;
    if($uri_test = @parse_url($uri, PHP_URL_PATH))
    {
      $uri = rtrim($uri_test,'/');
    }
    $no_url_ext = ($mainsettings_modrewrite && !empty($mainsettings_url_extension) &&
                   (substr($uri, -strlen($mainsettings_url_extension))!=$mainsettings_url_extension));


    //SD362: allow for special tag detection, e.g. sd3-media-gallery/sd3
    // where sd3-media-gallery.html would be the page
    // (if mod_rewrite is enabled)
    $manual_tag = false;
    if($mainsettings_modrewrite && !empty($sd_variable_arr) &&
       ($tmp = explode('/',$this->current_page)))
    {
      // if URL does not end with current page extention
      if(!preg_match('#(.*)[\-](s|i)([0-9]*)\.?$#i',end($sd_variable_arr)) &&
         (substr($uri, -strlen($mainsettings_url_extension))!=$mainsettings_url_extension))
      {
        $t = str_replace($mainsettings_url_extension, '', end($tmp));
        if(false !== ($idx = array_search($t,$sd_variable_arr)))
        {
          if($t2 = trim(urldecode(end($sd_variable_arr))))
          {
            $_GET[$this->pre.'_tag'] = htmlspecialchars($t2, ENT_QUOTES);
            $manual_tag = true;
          }
        }
      }
    }

    $sectionid = 0;
    $imageid = 0;
    if($mainsettings_modrewrite && !empty($sd_url_params) && (count($sd_url_params) > 2))
    {
      if(($sd_url_params[0]==$this->pre) && ($sd_url_params[1]=='sectionid'))
      {
        $seo_detected = true;
        $sectionid = (int)$sd_url_params[2];
        $_GET[$this->pre.'_sectionid'] = $sectionid;
        if((count($sd_url_params) == 6) &&
           ($sd_url_params[3]==$this->pre) && ($sd_url_params[4]=='imageid'))
        {
          $imageid = (int)$sd_url_params[5];
        }
      }
    }
    else
    {
      $sectionid = GetVar($this->pre.'_sectionid',0,'whole_number');
      $imageid   = GetVar($this->pre.'_imageid',0,'whole_number');
    }
    $this->sectionid = Is_Valid_Number($sectionid,0,1);
    $this->imageid   = Is_Valid_Number($imageid,0,1);

    $this->allow_links          = false; //SD362
    $this->allow_files          = false; //SD362
    $this->allow_media_delete   = false; //SD362
    $this->allow_media_edit     = false; //SD362
    $this->allow_section_delete = false; //SD362
    $this->allow_section_edit   = false; //SD362
    $this->is_section_owner     = false; //SD362
    $this->is_media_owner       = false; //SD362

    //TODO: redirects for "non-seo" urls
    $seo_name = '';
    if($this->seo_enabled && !$this->seo_detected && !empty($sd_variable_arr))
    {
      $count = count($sd_variable_arr);
      if($count > 0)
      {
        if(preg_match('#(.*)[\-](s|i)([0-9]*)\.?$#i',end($sd_variable_arr),$matches))
        {
          if((count($matches)==4) && ctype_digit($matches[3]))
          {
            $seo_name = $matches[1];
            if($matches[2]=='i') //image
            {
              $this->imageid = (int)$matches[3];
              $this->seo_detected = true;
            }
            else
            if($matches[2]=='s') //section
            {
              $this->sectionid = (int)$matches[3];
              $this->seo_detected = true;
              $this->seo_section_detected = true;
              if($no_url_ext) $_GET[$this->pre.'_sectionid'] = $this->sectionid;
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
              if($no_url_ext) $_GET[$this->pre.'_sectionid'] = $this->sectionid;
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

    $result = true;
    if($this->imageid && ($this->imageid > 0))
    {
      $result = $this->SetImageAndSection($this->imageid, ($this->seo_detected && isset($matches[1])?(string)$matches[1]:false));

      if(!$result && $this->SetSection($this->sectionid))
      {
        $res = $this->RewriteSectionLink($this->sectionid);
        $this->error_arr[] = $this->language['image_not_found'].
                             ' <a href="'.$res.'">'.$this->language['go_to_section'].' '.$this->section_arr['name'].'</a>';
      }
    }
    else
    if($this->sectionid && ($this->sectionid > 0))
    {
      // If SetSection() returns false and 404s, then do not redirect
      if($result = $this->SetSection($this->sectionid))
      {
        //SD360: redirect (301) "old" or wrong link to SEO url
        // i.e. "old" = www.mysite.com/gallery.html?p5001_sectionid=2
        // but do not redirect, if tag was detected!
        if($this->seo_redirect && $this->seo_enabled &&
           !empty($this->sectionid) && !$manual_tag)
        {
          if($this->seo_detected && !empty($this->section_arr['seo_title']) &&
             ($seo_name != $this->section_arr['seo_title']) )
          {
            $this->seo_detected = false;
            $_GET[$this->pre.'_sectionid'] = $this->sectionid;
          }
          if(!$this->seo_detected || ($no_url_ext && !empty($_GET[$this->pre.'_sectionid'])))
          {
            $res = $this->RewriteSectionLink($this->sectionid);
            StopLoadingPage('','',301,$res);
          }
        }
      }
    }
    else
    {
      $this->sectionid = 1;
    }

    $this->InitStatus = $result;
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
      if( ($this->gal_cache===false) ||
          empty($this->gal_cache[$parent_sectionid]['loaded']))
      {
        if(($getcache = $SDCache->read_var('mg_cache_'.$this->pluginid.'_'.$parent_sectionid,
                                           'cache')) !== false)
        {
          $this->gal_cache[$parent_sectionid] = $getcache;
        }
      }

      if(($this->seo_cache===false) || empty($this->seo_cache[$parent_sectionid]['loaded']))
      {
        if(($getcache = $SDCache->read_var('mg_cache_seo_'.$this->pluginid.'_'.$parent_sectionid,
                                           'cache')) !== false)
        {
          $this->seo_cache[$parent_sectionid] = $getcache;
        }
      }
    }

    // ################## FILL CACHE FROM DB #############################
    if( ($this->gal_cache===false) || ($this->seo_cache===false) ||
        empty($this->gal_cache[$parent_sectionid]['loaded']) ||
        empty($this->seo_cache[$parent_sectionid]['loaded']) )
    {
      if($this->gal_cache===false) $this->gal_cache = array();
      $this->gal_cache[$parent_sectionid] =
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
 (SELECT COUNT(*) FROM '.$this->sections_tbl.' ms2
  WHERE ms2.parentid = ms.sectionid) subsections
 FROM '.$this->sections_tbl.' ms
 WHERE (ms.sectionid = %d)
 UNION
 SELECT ms.*,
 (SELECT COUNT(*) FROM '.$this->sections_tbl.' ms2
  WHERE ms2.parentid = ms.sectionid) subsections
 FROM '.$this->sections_tbl.' ms
 WHERE (ms.parentid = %d)
 ORDER BY 1,2',
          $parent_sectionid, $parent_sectionid))
      {
        while($entry = $DB->fetch_array($getdata,null,MYSQL_ASSOC))
        {
          $sid = (int)$entry['sectionid'];
          $pid = empty($entry['parentid']) ? 0 : (int)$entry['parentid'];
          if(isset($entry['seo_title']) && strlen($entry['seo_title']))
          {
            $this->seo_cache[$parent_sectionid]['by_id'][$sid] = $entry['seo_title'];
            $this->seo_cache[$parent_sectionid]['by_title'][$entry['seo_title']] = $sid;
          }
          else
          if(strlen($entry['name']))
          {
            $title = ConvertNewsTitleToUrl($entry['name'],$sid,1,true,false);
            $this->seo_cache[$parent_sectionid]['by_id'][$sid] = $title;
            $this->seo_cache[$parent_sectionid]['by_title'][$title] = $sid;
            $entry['seo_title'] = $title;
          }

          @ksort($entry);
          if($sid == $parent_sectionid)
            $this->gal_cache[$parent_sectionid]['section'] = $entry;
          else
            $this->gal_cache[$parent_sectionid]['sections'][$sid] = $entry;
        } //while

        if(count($this->seo_cache[$parent_sectionid]['by_id']))
          @ksort($this->seo_cache[$parent_sectionid]['by_id']);
        if(count($this->seo_cache[$parent_sectionid]['by_title']))
          @ksort($this->seo_cache[$parent_sectionid]['by_title']);

        if($this->cache_on)
        {
          $SDCache->write_var('mg_cache_'.$this->pluginid.'_'.$parent_sectionid,
                              'cache', $this->gal_cache[$parent_sectionid]);
          $SDCache->write_var('mg_cache_seo_'.$this->pluginid.'_'.$parent_sectionid,
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


  // #############################################################################


  public function SetSection($sectionid,$doRedirect=true)
  {
    global $DB, $mainsettings, $userinfo;

    $doRedirect = !empty($doRedirect);
    $this->section_arr = false;
    if(!empty($sectionid))
    {
      if(isset($this->gal_cache[$sectionid]['section']))
      {
        $this->sectionid = (int)$sectionid;
        $this->section_arr = $this->gal_cache[$this->sectionid]['section'];
      }
      else
      if(isset($this->gal_cache[1]['sections'][$sectionid]))
      {
        $this->sectionid = (int)$sectionid;
        $this->section_arr = $this->gal_cache[1]['sections'][$sectionid];
      }
    }

    if(!is_array($this->section_arr) && ($this->section_arr === false))
    {
      $DB->result_type = MYSQL_ASSOC;
      if($section_arr = $DB->query_first(
                              'SELECT * FROM '.$this->sections_tbl.' s'.
                              ' WHERE s.sectionid = %d '.
                              ($this->IsSiteAdmin ? '' :
                               $this->GroupCheck.' AND IFNULL(s.activated,0) = 1').
                              ' LIMIT 1', $sectionid))
      {
        $this->sectionid = (int)$sectionid;
        $this->section_arr = $section_arr;
      }
    }

    if(is_array($this->section_arr) && ($this->section_arr !== false))
    {
      $this->current_page = $this->RewriteSectionLink($this->sectionid);

      //Check group-level permissions, incl. secondary usergroups
      $this->section_arr['access_view'] =
        !empty($this->section_arr['access_view']) ?
        sd_ConvertStrToArray($this->section_arr['access_view'],'|'):
        array();
      $this->section_arr['can_view'] =
        empty($this->section_arr['access_view']) ||
        (!empty($userinfo['usergroupids']) &&
         @array_intersect($userinfo['usergroupids'],
                          $this->section_arr['access_view']));
      $this->section_arr['can_submit'] =
        (!empty($userinfo['pluginsubmitids']) &&
         in_array($this->pluginid, $userinfo['pluginsubmitids']));

      $this->AllowSubmit = $this->IsSiteAdmin || $this->IsAdmin ||
                           ($this->section_arr['can_view'] && $this->section_arr['can_submit']);

      //SD362: detemine user permissions for delete/edit on section/media (admin or owner)?
      $this->CheckPermissions();

      return true;
    }

    if($doRedirect)
    {
      // Section not found
      if(!headers_sent()) header("HTTP/1.0 404 Not Found");
      $this->error_arr[] = $this->language['section_not_found'];
      $this->imageid     = 0;
      $this->image_arr   = array();
      $this->sectionid   = 0;
      $this->section_arr = array();
    }

    return false;

  } //SetSection

  // ##########################################################################

  public function SetImageAndSection($imageid, $doRedirect=true)
  {
    if(empty($imageid) || !Is_Valid_Number($imageid,0,1,99999999))
    {
      return false;
    }
    $doRedirect = !empty($doRedirect);

    global $DB, $userinfo;
    $DB->result_type = MYSQL_ASSOC;
    $tmp = $DB->query_first('SELECT f.* FROM '.$this->images_tbl.' f'.
                            ' INNER JOIN '.$this->sections_tbl.' s ON s.sectionid = f.sectionid'.
                            ' WHERE f.imageid = '.$imageid.
                            ($this->IsSiteAdmin || $this->IsAdmin ? '' :
                             $this->GroupCheck .
                             ' AND (IFNULL(f.activated,0) = 1) '.
                             ' AND (IFNULL(s.activated,0) = 1) '.
                             ' AND ((IFNULL(f.private,0) = 0) OR (f.owner_id = '.$userinfo['userid'].'))').
                            ' LIMIT 1');
    if(empty($tmp['imageid']))
    {
      return false;
    }
    $this->imageid = (int)$imageid;
    $this->image_arr = $tmp;
    if($this->image_arr['sectionid']==1)
    {
      $this->sectionid = 1;
    }
    $this->current_page = $this->RewriteSectionLink($this->image_arr['sectionid']);

    if($this->seo_enabled && $doRedirect)
    {
      /*
      SD360: redirect (301) old link to SEO url
        e.g. URL /sd3-media-gallery.htm?p5020_sectionid=10&p5020_imageid=188
        goes to  /sd3-media-gallery/integrated-display-mode-s10/1572-i188.htm
      SD362: improved redirects
        a) wrong section id /sd3-media-gallery/integrated-display-mode-s123/1572-i188.htm
        needs to redirect to correct location (-s10, not -s123)
        b) URL with only file-SEO needs to redirect to URL incl. the section-SEO
           (for URLs with sectionid > 1 only!)
        e.g. URL /sd3-media-gallery/1572-i188.htm
        goes to  /sd3-media-gallery/integrated-display-mode-s10/1572-i188.htm
      */
      if( (!$this->seo_section_detected && empty($this->sectionid)) ||
          ( !empty($this->sectionid) && !empty($this->image_arr['sectionid']) &&
            ($this->sectionid != $this->image_arr['sectionid']) ) ||
          ( $this->seo_redirect && !$this->seo_detected &&
            !empty($_GET[$this->pre.'_imageid']) ) )
      {
        $res = $this->RewriteImageLink($this->image_arr['sectionid'], $this->imageid,
                      (empty($this->image_arr['title'])?'':$this->image_arr['title']));
        StopLoadingPage('','',301,$res);
      }
    }

    if($this->SetSection($this->image_arr['sectionid'],$doRedirect))
    {
      return true;
    }

    if($doRedirect)
    {
      // Image not found
      if(!headers_sent()) header("HTTP/1.0 404 Not Found");
      $this->error_arr[] = $this->language['image_not_found'];
      $this->imageid     = 0;
      $this->image_arr   = array();
    }

    //SD362: detemine user permissions for delete/edit on section/media (admin or owner)?
    $this->CheckPermissions();

    return false;

  } //SetImageAndSection


  public function CheckPermissions() //SD362
  {
    global $userinfo;
    /*
    Site/Plugin Admins: all permissions (edit,delete,un/approve)
    Plugin Moderators:  all, except delete (edit,un/approve)
    Section/Media Owners: edit/delete (if enabled in settings!), unapprove media
    */

    // Section-level permissions
    $this->is_section_owner = !empty($this->section_arr['owner_id']) && !empty($userinfo['userid']) &&
                              ($userinfo['userid'] == $this->section_arr['owner_id']);
    $this->allow_section_delete = !empty($this->sectionid) &&
           ($this->IsSiteAdmin || $this->IsAdmin ||
            ($this->is_section_owner && !empty($this->settings['allow_section_owner_delete'])));
    $this->allow_section_edit = !empty($this->sectionid) &&
           ($this->IsSiteAdmin || $this->IsAdmin ||
            ($this->is_section_owner && !empty($this->settings['allow_section_owner_edit'])));

    // Media-level permissions
    $this->is_media_owner = !empty($this->imageid) && !empty($this->image_arr['owner_id']) &&
                            !empty($userinfo['userid']) &&
                            ($userinfo['userid'] == $this->image_arr['owner_id']);
    $this->allow_media_delete = !empty($this->imageid) &&
           ($this->IsSiteAdmin || $this->IsAdmin || $this->allow_section_delete ||
            ($this->is_media_owner && !empty($this->settings['allow_media_owner_delete'])));
    $this->allow_media_edit = !empty($this->imageid) &&
           ($this->allow_mod || $this->allow_section_edit ||
            ($this->is_media_owner && !empty($this->settings['allow_media_owner_edit'])));

    $this->allow_files = (($this->IsAdmin || $this->IsSiteAdmin) ||
                          !empty($this->section_arr['allow_submit_file_upload'])) &&
                         !empty($this->settings['allowed_image_types']) &&
                         !empty($this->settings['max_upload_size']);
    $this->allow_links = !empty($this->allowed_media_str) &&
                         ($this->IsAdmin || $this->IsSiteAdmin ||
                          !empty($this->section_arr['allow_submit_media_link']));

  } //CheckPermissions


  // ###########################################################################
  // REWRITE SECTION LINK (SUPPORTING SEO)
  // ###########################################################################

  public function RewriteSectionLink($sectionid, $url_title_only=false,
                                     $page=1, $url_action='')
  {
    global $mainsettings_url_extension;

    if($this->seo_enabled && !empty($sectionid))
    {
      if(isset($this->seo_cache[$this->sectionid]['by_id'][$sectionid]))
      {
        $title = $this->seo_cache[$this->sectionid]['by_id'][$sectionid];
      }
      else
      {
        if(empty($this->gal_cache[$sectionid]['loaded']))
        {
          $this->InitSectionCache($sectionid);
        }
        $title = $this->seo_cache[$sectionid]['by_id'][$sectionid];
      }
      if(!empty($url_title_only))
      {
        return $title;
      }

      if(($sectionid==1) /*&& empty($url_action)*/)
      {
        $link = $this->plugin_page;
        if(!empty($url_action) && (strlen($url_action) > 1))
        {
          $link .= (strpos($link,'?')===false?'?':'&amp;').$url_action;
        }
        return $link;
      }
      $link = $this->plugin_page;

      $title .= '-s'.(int)$sectionid;
      $link = str_replace($mainsettings_url_extension,
                          '/' . $title . $mainsettings_url_extension,
                          $link);
      $hasPage = false;
      if(!empty($page) && ((int)$page > 1))
      {
        $hasPage = true;
        $link .= '?page='.(int)$page;
      }
      if(!empty($url_action) && (strlen($url_action) > 1))
      {
        $link .= ($hasPage?'&amp;':'?').$url_action;
      }

    }
    else
    {
      global $categoryid;
      $link = RewriteLink('index.php?categoryid='.
                          (empty($this->categoryid)?$categoryid:$this->categoryid).
                          (empty($sectionid) || ($sectionid==1)?'':'&'.$this->pre.'_sectionid='.$sectionid));
    }
    return $link;

  } //RewriteSectionLink


  // ###########################################################################
  // REWRITE IMAGE LINK (SUPPORTING SEO)
  // ###########################################################################

  public function RewriteImageLink($sectionid, $imageid, $img_title=null, $page=1, $as_array=false)
  {
    global $mainsettings_url_extension;

    if($this->seo_enabled)
    {
      $link = $this->current_page;
      if(empty($as_array))
      {
        if(empty($img_title) && ($imageid == $this->imageid) &&
           isset($this->image_arr['name']))
        {
          $img_title = $this->image_arr['name'];
        }
        if(!strlen($img_title))
        {
          $img_title = $imageid;
        }
        $title = ConvertNewsTitleToUrl($img_title,$imageid,(int)$page,false,'-i');
      }
      else
      {
        $title = ConvertNewsTitleToUrl($img_title,$imageid,0,false,'-i');
      }
      $link = str_replace($mainsettings_url_extension, '/'.$title, $link);
      if(!empty($as_array))
      {
        return array('link' => $link, 'name' => $title);
      }
    }
    else
    {
      $link = $this->current_page.(strpos($this->current_page,'?')===false?'?':'&amp;').
              $this->pre.'_imageid='.(int)$imageid;
      if(!empty($as_array))
      {
        return array('link' => $link, 'name' => $img_title);
      }
    }
    return $link;

  } //RewriteImageLink


  // #############################################################################


  public function UpdateImageCounts($sectionid=0)
  {
    return $this->UpdateSectionImageCounts($sectionid, 0);
  }

  // #############################################################################

  public function UpdateSectionImageCounts($sectionid, $totalcount)
  {
    global $DB, $SDCache;

    $sectioncount = $DB->query_first('SELECT COUNT(*) ic FROM {p'.$this->pluginid.'_images}'.
                                     ' WHERE sectionid = %d AND IFNULL(activated,0) = 1', $sectionid);
    $localcount = $sectioncount['ic'];

    // Find all sections under this one
    $getsections = $DB->query('SELECT sectionid FROM {p'.$this->pluginid.'_sections} WHERE parentid = %d', $sectionid);
    while($s = $DB->fetch_array($getsections,null,MYSQL_ASSOC))
    {
      $localcount += $this->UpdateSectionImageCounts($s['sectionid'], $localcount);
    }

    $DB->query('UPDATE {p'.$this->pluginid.'_sections}'.
               ' SET imagecount = %d WHERE sectionid = %d',
               $localcount, $sectionid);

    //SD362: clear related cache files
    if(!empty($SDCache))
    {
      $SDCache->delete_cacheid('mg_cache_'.$this->pluginid.'_'.$sectionid);
      $SDCache->delete_cacheid('mg_cache_seo_'.$this->pluginid.'_'.$sectionid);
    }

    return $localcount;

  } //UpdateSectionImageCounts

  // #############################################################################

  public function GetFileErrorDescr($error_id)
  {
    $error = '';
    if(!empty($error_id))
    {
      switch ($error_id)
      {
        case 1: //UPLOAD_ERR_INI_SIZE:
          //$error = "The uploaded file exceeds the upload_max_filesize directive (".ini_get("upload_max_filesize").") in php.ini.";
          $error = 'The uploaded file exceeds the allowed filesize limit.';
          break;
        case 2: //UPLOAD_ERR_FORM_SIZE:
          //$error = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.";
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
          $error = 'Unknown File Error';
          break;
      }
    }
    return $error;

  } //GetFileErrorDescr

  // ##########################################################################

  public function GetSubSectionsSort($sorttype=2)
  {
    // get sort order used for subsections (for SQL)
    $order = 'name ASC'; //Default, equal to 'alpha_az' and '2'
    switch($sorttype)
    {
      case 'newest_first':
      case '0': // Newest First
        $order = 'datecreated DESC';
        break;

      case 'oldest_first':
      case '1': // Oldest First
        $order = 'datecreated ASC';
        break;

      case 'alpha_za':
      case '3': // Alphabetically Z-A
        $order = 'name DESC';
        break;

      default: // Alphabetically A-Z / 2
        $order = 'name ASC';
        break;
    }

    return $order;

  } //GetSubSectionsSort

  // ##########################################################################

  public function GetImagesSort($sorttype='newest_first')
  {
    // get sort order used for images within a section (for SQL)
    // default is newest first (datecreated desc):
    switch($sorttype)
    {
      case 'alpha_az':
        $order = 'f.title ASC, f.imageid ASC';
        break;

      case 'alpha_za':
        $order = 'f.title DESC, f.imageid DESC';
        break;

      case 'author_name_az':
        $order = 'f.author ASC, f.imageid ASC';
        break;

      case 'author_name_za':
        $order = 'f.author DESC, f.imageid DESC';
        break;

      case 'oldest_first':
        $order = 'f.datecreated ASC, f.imageid ASC';
        break;

      default: //newest_first
        $order = 'f.datecreated DESC, f.imageid DESC';
        break;
    }
    return $order;

  } //GetSubSectionsSort

  // #############################################################################

  public function CheckImageExtension($imagename)
  {
    $exts   = array();
    $getext = str_replace(',', ' ', $this->settings['allowed_image_types']);
    $getext = preg_replace('#\s[\s]+#', ' ', $getext);
    $getext = trim($getext);
    if(strlen($getext))
    {
      $exts = explode(' ', $getext);
    }
    $image_name = strtolower($imagename);
    $image_ext  = explode('.', $image_name);
    $image_ext  = (is_array($image_ext) && count($image_ext)) ? $image_ext[count($image_ext) - 1] : null;

    // lets make sure the extension is allowed
    return (isset($image_ext) && !empty($exts) && in_array($image_ext, $exts));

  } //CheckImageExtension

  // #############################################################################

  public function CheckImage($image, $required, $size_error_message, & $errors) // $image = $_FILE object!
  {
    global $userinfo;

    if(!isset($image)) return true;

    $err_count = empty($errors) ? 0 : count($errors);

    if(($image['error'] > 0) && ($image['error'] != 4))
    {
      if($image['error'] == 2)
      {
        $errors[] = $size_error_message;
      }
      else
      {
        $errors[] = $this->GetFileErrorDescr($image['error']);
      }
    }
    else
    if(isset($image) && empty($image['error']) && !empty($image['tmp_name']) &&
       (!isset($image['name']) || ($image['name'] != 'none')))
    {
      // Check if image was really uploaded
      if(!isset($image['name']) || !@is_uploaded_file($image['tmp_name']))
      {
        $errors[] = $this->language['invalid_image_upload'].'<br />';
        if(!defined('IN_ADMIN'))
        Watchdog('Media Gallery '.$this->pluginid, $this->language['watchdog_upload_by'].' '.
          (empty($userinfo['loggedin']) ? 'anonymous user' : ('user '.$userinfo['username'])).
          ': ' . htmlspecialchars($image['name']), WATCHDOG_ERROR);
        return -1;
      }

      // Security check to prevent executables in image name:
      $t = preg_replace(array('/\.asp/', '/\.php([2-6])?/', '/\.scr/', '/\.bat/', '/\.vb/', '/\.pl/', '/\.cgi/', '/\.sh/', '/\.cmd/', '/\.exe/'), '', $image['name']);
      if($t != $image['name'])
      {
        $errors[] = $this->language['invalid_image_upload'].'!<br />';
        if(!defined('IN_ADMIN'))
        Watchdog('Media Gallery '.$this->pluginid, $this->language['watchdog_upload_by'].
          (empty($userinfo['loggedin']) ? 'anonymous user' : ('user '.$userinfo['username'])).
          ': ' . htmlspecialchars($image['name']), WATCHDOG_ERROR);
        return -1;
      }

      if($image['error'] > 0)
      {
        $errors[] = $this->GetFileErrorDescr($image['error']);
      }
      else
      {
        // Check file type
        if(!in_array($image['type'], SD_Media_Base::$known_image_types))
        {
          $errors[] = $this->language['invalid_image_type'];
        }
        else
        {
          if(!$this->CheckImageExtension($image['name']))
          {
            $errors[] = $this->language['image'].' '.$this->language['invalid_image_type'];
          }
        }

        if($image['size'] > $this->settings['max_upload_size'])
        {
          $errors[] = $size_error_message;
        }
      }
    }
    else
    if(!empty($required))
    {
      $errors[] = $this->language['image'].' '.$this->GetFileErrorDescr($image['error']);
    }

    return empty($errors) || ($err_count == count($errors));

  } //CheckImage

  // ##########################################################################

  public function ScaleImage($imagepath, &$width, &$height, &$maxwidth, &$maxheight)
  {
    if(empty($imagepath) || empty($maxwidth) || empty($maxheight))
    {
      return;
    }

    if(!is_readable($imagepath) || !(@list($width2, $height2, $type, $attr) = @getimagesize($imagepath)))
    {
      return;
    }
    $width = $width2;
    $height= $height2;

    $scale = min($maxwidth/$width, $maxheight/$height);

    // If the image is larger than the max shrink it
    if($scale < 1)
    {
      $newwidth  = floor($scale * $width);
      $newheight = floor($scale * $height);
    }
    else
    {
      $newwidth  = $width;
      $newheight = $height;
    }

    // Prevent 0 height or width
    if(($newheight==0) && ($height > 0))
    {
      $newheight = $height;
    }
    if(($newwidth==0) && ($width > 0))
    {
      $newwidth  = $width;
    }

    $maxwidth = $newwidth;
    $maxheight = $newheight;

  } //ScaleImage

  // ##########################################################################

  public function GetImageTags($imageid)
  {
    if(empty($imageid) || !is_numeric($imageid)) return array();
    return SD_Tags::GetPluginTags($this->pluginid,$imageid);
  }

  // ##########################################################################

  public function StoreImageTags($imageid, $tags)
  {
    if(empty($imageid) || !is_numeric($imageid)) return array();
    return SD_Tags::StorePluginTags($this->pluginid, $imageid, $tags, 0);
  }

  // ##########################################################################

  public function GetFileExtension($filename)
  {
    if(empty($filename) || (strlen($filename)<4))
    {
      return '';
    }
    if(function_exists('pathinfo') && (substr(PHP_VERSION,0,5) >= "4.0.3"))
    {
      $ext = @pathinfo($filename, PATHINFO_EXTENSION);
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

  } //GetFileExtension

  // ##########################################################################

  public function HasFileImageExt($filename)
  {
    if(!isset($filename) || (!strlen($filename)))
    {
      return '';
    }
    $ext = strtolower($this->GetFileExtension($filename));

    // If not supported, return NULL as extension
    if(empty($ext) || (($ext!='jpeg') && !array_key_exists($ext,SD_Media_Base::$valid_image_extensions)))
    {
      $ext = '';
    }
    return $ext;

  } //HasFileImageExt

  // ##########################################################################

  public function DeleteImageFiles($filename, $folder) //SD362
  {
    if(empty($filename) || empty($folder) || !is_dir($folder))
    {
      return;
    }

    $image     = $folder . $filename;
    $thumbnail = $folder . $this->TB_PREFIX . $filename;
    $midsize   = $folder . $this->MD_PREFIX . $filename;

    $old = $GLOBALS['sd_ignore_watchdog'];
    $GLOBALS['sd_ignore_watchdog'] = true;
    if(file_exists($image))     @unlink($image);
    if(file_exists($thumbnail)) @unlink($thumbnail);
    if(file_exists($midsize))   @unlink($midsize);
    $GLOBALS['sd_ignore_watchdog'] = $old;
  } //DeleteImageFiles

  // ##########################################################################

  public function DeleteMediaEntry($imageid,$silent=false)
  {
    global $DB;

    if(!empty($this->pluginid) &&
       !empty($imageid) && ($imageid > 1) && ($imageid <= 999999999) &&
       ($getentry = $DB->query_first('SELECT sectionid, filename, folder'.
                                     ' FROM '.$this->images_tbl.
                                     ' WHERE imageid = %d', $imageid)))
    {
      $folder = isset($getentry['folder'])?$getentry['folder']:'';
      $folder .= (strlen($folder) && (substr($folder,-1)!='/') ? '/' : '');
      $folder = $this->IMAGEPATH.$folder;

      $filename  = $getentry['filename'];
      $sectionid = $getentry['sectionid'];

      // Delete image row
      $DB->query('DELETE FROM '.$this->images_tbl.' WHERE imageid = %d', $imageid);

      // Process Tags
      $this->StoreImageTags($imageid, '');

      // Delete image's comments and ratings
      DeletePluginComments($this->pluginid, $imageid);
      DeletePluginRatings($this->pluginid, $this->pre.'-'.$imageid);

      // Delete physical image files (incl. image,thumb,medium)
      if(!empty($filename))
      {
        if(defined('IN_ADMIN') && !is_writable($folder) && !$silent)
        {
          DisplayMessage(AdminPhrase('folder_not_writable'), true);
        }
        $this->DeleteImageFiles($filename, $folder);
      }

      $this->UpdateImageCounts($sectionid);

      return true;
    }

    return false;

  } //DeleteMediaEntry

} // end of class

} // DO NOT REMOVE!
