<?php
if(!defined('IN_PRGM')) die("Hacking attempt!");

/*
Tobias, SD (2013-08-31):
 - plugin requires SD 3.6.2+!
 - plugin rewritten as clonable plugin
 - plugin now correctly supports legacy Image Gallery and current Media Gallery plugin
 - support for Media Gallery images' "folder" locations
 - better checks if image paths exist
 - applies "width" and "border" in common "style" attribute for IMG tag
*/
if(!class_exists('RandomImageClass'))
{
class RandomImageClass
{
  public  $pluginid      = 0;
  public  $plugin_folder = '';
  public  $language      = array();
  public  $settings      = array();
  private $src_pluginid  = 0; //SD370
  private $gal           = false; //SD370
  private $src_folder    = false; //SD370
  private $src_is_mg     = false; //SD370

  public function __construct($plugin_folder)
  {
    // get plugin settings
    $this->plugin_folder = $plugin_folder;
    if($pluginid = GetPluginIDbyFolder($this->plugin_folder))
    {
      $this->pluginid = $pluginid;
      $this->settings = GetPluginSettings($this->pluginid);
      $this->language = GetLanguage($this->pluginid);

      $this->SetSourcePluginID();
    }
  }

  public function SetSourcePluginID($pluginid=0)
  {
    global $plugin_folder_to_id_arr, $plugin_names;

    unset($this->gal);
    $this->gal = false;

    if(!empty($pluginid) && ($pluginid = Is_Valid_Number($pluginid, 0, 17, 99999)))
      $this->src_pluginid = (int)$pluginid;
    else
      $this->src_pluginid = (int)$this->settings['gallery_plugin'];

    // is source plugin actually installed?
    if(!$this->src_folder = array_search($this->src_pluginid, $plugin_folder_to_id_arr))
    {
      $this->src_pluginid = 0;
      return false;
    }

    // is source plugin a Media Gallery plugin?
    $this->src_is_mg = isset($plugin_names['base-'.$this->src_pluginid])?
                       ('Media Gallery'==$plugin_names['base-'.$this->src_pluginid]):false;
    if(!$this->src_is_mg) return true;

    // include Media Gallery class if needed
    if(!class_exists('GalleryBaseClass'))
    {
      $path = ROOT_PATH.'plugins/'.$this->src_folder.'/gallery_lib.php';
      if(file_exists($path))
        @include_once($path);
      else
        return false;
    }
    if(class_exists('GalleryBaseClass'))
    {
      $this->gal = new GalleryBaseClass($this->src_folder);
    }

    return true;

  } //SetSourcePluginID

  // ##########################################################################

  private function GetImageFromFolder($location)
  {
    if(empty($this->settings['allowed_file_types']))
    {
      $this->settings['allowed_file_types'] = 'gif,jpg,jpeg,png';
    }

    $image = $filetypes = array();

    if(empty($location) || (trim($location) == '') || !realpath($location) ||
       (strlen(realpath($location)) < strlen($_SERVER['DOCUMENT_ROOT'])) || //SD370
       !is_dir($location))
      return $image;

    if($filetypes = explode(',' ,strtolower(str_replace(' ', '', trim($this->settings['allowed_file_types'])))))
    {
      $filetypes = array_flip($filetypes);
    }

    $olddog = $GLOBALS['sd_ignore_watchdog'];
    $GLOBALS['sd_ignore_watchdog'] = true;
    if($handle = @opendir($location))
    {
      while($file = @readdir($handle))
      {
        if(($file != '.') && ($file != '..'))
        {
          $extension = strtolower(trim(substr($file, strrpos($file, '.')+1)));
          if(isset($filetypes[$extension]))
          {
            $image[] = $file;
          }
        }
      } //while
      @closedir($handle);
    }
    $GLOBALS['sd_ignore_watchdog'] = $olddog;
    return $image;

  } //GetImageFromFolder

  // ##########################################################################

  private function GetImageFromGallery()
  {
    global $DB, $userinfo, $mainsettings_search_results_page, $mainsettings_tag_results_page;

    // find which category has the source gallery (except search/tags page!)
    if(empty($this->src_pluginid) ||
       (!$getcategory = $DB->query_first('SELECT categoryid FROM {pagesort}'.
                                         " WHERE pluginid = '%s'".
                                         ' AND categoryid NOT IN (%d,%d)',
                                         $this->src_pluginid,
                                         $mainsettings_search_results_page,
                                         $mainsettings_tag_results_page)))
    {
      if(!empty($userinfo['adminaccess']))
        echo $this->language['gallery_not_found'].'<br />';
      return false;
    }

    //SD370: added support for Media Gallery to use folder, private columns
    // and special permissions check
    $DB->result_type = MYSQL_ASSOC;
    if($image = $DB->query_first(
      'SELECT f.imageid, f.sectionid, f.filename, f.author, f.title'.
      ($this->src_is_mg ? ", IFNULL(f.folder,'') folder" : '').
      ' FROM '.PRGM_TABLE_PREFIX.'p'.$this->src_pluginid.'_images f'.
      ' INNER JOIN '.PRGM_TABLE_PREFIX.'p'.$this->src_pluginid.'_sections s'.
      ' WHERE f.activated = 1 AND s.activated = 1 '.
      ($this->src_is_mg && $this->gal ? $this->gal->GroupCheck .' AND f.private <> 1' : '').
      // faster random selection:
      ' AND f.imageid >= (SELECT FLOOR(MAX(imageid) * RAND()) FROM '.
      PRGM_TABLE_PREFIX.'p'.$this->src_pluginid.'_images)'.
      ' ORDER BY imageid LIMIT 1'))
      #OLD: ' ORDER BY RAND() LIMIT 1'

    {
      $image['categoryid'] = (int)$getcategory['categoryid'];
      return $image;
    }

    return false;

  } //GetImageFromGallery


  // ##########################################################################

  public function DisplayRandomImage()
  {
    global $plugin_names, $sdurl;

    if(empty($this->pluginid)) return false;

    $use_gallery_mode = empty($this->settings['image_source']);
    $imagename = '';
    if(!$use_gallery_mode)
    {
      $location = $this->settings['images_folder_path'];
      if(substr($location, -1) != '/')
      {
        $location .= '/';
      }
      $image = $this->GetImageFromFolder($location);
      if(count($image) > 0)
      {
        $numimages = count($image) - 1;
        $randomnum = mt_rand(0, $numimages);
        $imagename = $sdurl.$location.$image[$randomnum];
      }
    }
    else
    {
      if(empty($this->src_pluginid)) return false;

      $imgpath = 'plugins/'.$this->src_folder.'/images/';
      $randomnum = 'imageid';
      if(!is_dir($imgpath)) return false;

      if(false === ($image = $this->GetImageFromGallery())) return false;

      $folder = isset($image['folder'])?$image['folder']:'';
      $imgpath .= $folder . (strlen($folder) && (sd_substr($folder,strlen($folder)-1,1)!='/') ? '/' : '');
      if(!is_dir($imgpath)) return false;

      if(@file_exists($imgpath.'tb_' . $image['filename']))
      {
        $image['filename'] = 'tb_' . $image['filename'];
      }
      $imagename = $sdurl.$imgpath.$image['filename'];
      if($this->src_is_mg)
      {
        $this->gal->categoryid = $image['categoryid'];
        $this->gal->plugin_page = RewriteLink('index.php?categoryid='.$this->gal->categoryid);
        if(!$this->gal->SetImageAndSection($image['imageid'], false))
        {
          return false;
        }
        if(empty($this->gal->section_arr['can_view'])) return false;
        $link = $this->gal->RewriteImageLink($image['sectionid'],$image['imageid'],$image['title']);
      }
      else
      {
        $link = RewriteLink('index.php?categoryid='.$image['categoryid'].
                  '&p'.$this->src_pluginid.'_sectionid='.$image['sectionid'].
                  '&p'.$this->src_pluginid.'_imageid='.$image['imageid']);
      }
      $link = '<a title="'.addslashes($image['title']).'" href="'.$link.'">';
    }

    if(!empty($image) && !empty($imagename) && isset($image[$randomnum]))
    {
      $bc = empty($this->settings['border_color'])?'':$this->settings['border_color'];
      echo '
      <div class="randompicture" align="center">'.
        ($use_gallery_mode?$link:'').'<img alt=" " src="'.$imagename.'" ';

      if(!empty($this->settings['border']) || !empty($this->settings['specify_image_width']))
      {
        echo ' style="';
        if(!empty($this->settings['border']))
        {
          echo 'border: 1px solid ' . $bc . ';';
        }
        if(!empty($this->settings['specify_image_width']))
        {
          echo 'max-width:' . $this->settings['specify_image_width'];
        }
        echo '"';
      }

      echo ' />'.($use_gallery_mode?'</a>':'').'<br />';

      if(!empty($this->settings['show_image_title']) && !empty($image['title']))
      {
        echo $link . $image['title']. '</a> ';
      }
      if(!empty($this->settings['show_image_author']) && !empty($image['author']))
      {
        echo '<br />'.$this->language['by']. ' ' . $image['author'];
      }
      echo '</div>
      ';

      return true;
    }

    echo $this->language['no_image_available'];
    return false;

  } //DisplayRandomImage

} // End of Class

} // DO NOT REMOVE
