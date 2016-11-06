<?php

if(!defined('IN_PRGM')) exit();

if(!class_exists('LatestImagesClass'))
{
class LatestImagesClass
{
  public  $pluginid      = 0;
  public  $plugin_folder = '';
  public  $language      = array();
  public  $settings      = array();
  public  $src_pluginid  = 0; //SD362
  private $gal           = false; //SD362
  private $src_folder    = false; //SD362

  function __construct($plugin_folder)
  {
    $this->plugin_folder = $plugin_folder;
    if($pluginid = GetPluginIDbyFolder($this->plugin_folder))
    {
      $this->pluginid = $pluginid;
      $this->settings = GetPluginSettings($this->pluginid);
      $this->language = GetLanguage($this->pluginid);

      $this->SetSourcePluginID(); //SD362:
    }
  }

  function SetSourcePluginID($pluginid=0) //SD362
  {
    global $plugin_folder_to_id_arr;

    if(!empty($pluginid) && ($pluginid = Is_Valid_Number($pluginid, 0, 17, 999999)))
      $this->src_pluginid = (int)$pluginid;
    else
      $this->src_pluginid = $this->settings['source_plugin'];

    // INCLUDE MEDIA GALLERY CLASSES
    unset($this->gal);
    $this->gal = false;
    if(!$this->src_folder = array_search($this->src_pluginid, $plugin_folder_to_id_arr))
    {
      return false;
    }

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

  // ####################### DISPLAY LATEST IMAGES ############################

  public function DisplayLatestImages($current_user=false, $useUserID=0)
  {
    //SD362: added parameters to limit articles for either current user
    // or the specified user id; functionality used by User Profile
    global $DB, $categoryid, $plugin_names, $plugin_folder_to_id_arr, $sdurl, $userinfo;

    if(empty($this->pluginid) || empty($this->src_pluginid))
    {
      return false;
    }
    if(isset($this->gal) && isset($this->gal->pluginid) &&
       (false === ($categoryid = GetPluginCategory($this->gal->pluginid,$categoryid))))
    {
      return false;
    }

    // categoryid for source plugin
    $extra = $extraquery = '';

    //SD362: custom display, used by User Profile plugin!
    $current_user = !empty($current_user);
    $useUserID = empty($useUserID)?0:Is_Valid_Number($useUserID,0,1,999999999);
    $customDisplay = ($current_user || $useUserID);

    // Exclude Sections on regular frontpage display?
    if(!$customDisplay && strlen($this->settings['exclude_sections']))
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
        $extraquery = ' AND s.sectionid NOT IN ('. implode(',', $sections).') ';
      }
      unset($excludeids, $sections);
    }

    //SD370: check plugin's base name to differentiate between Image and Media Gallery plugins
    $isMG = (isset($plugin_folder_to_id_arr['media_gallery']) &&
              ($plugin_folder_to_id_arr['media_gallery'] == $this->src_pluginid)) ||
            (isset($plugin_names['base-'.$this->src_pluginid]) ?
              ('Media Gallery'==$plugin_names['base-'.$this->src_pluginid]):false);
    if($isMG)
    {
      $this->gal->categoryid = $categoryid;
      $this->gal->plugin_page = RewriteLink('index.php?categoryid='.$categoryid);
    }

    // get popup setting
    $src_settings = GetPluginSettings($this->src_pluginid);
    if(!$isMG)
    {
      $popup = !empty($src_settings['Popup Window']) ||
               !empty($src_settings['image_display_mode']);
    }
    else
    {
      $popup = !$customDisplay && ($src_settings['image_display_mode']==1);
    }
    $fullsize = !empty($src_settings['Keep FullSize Images']) ||
                !empty($src_settings['keep_fullsize_images']);

    $maxcol    = !empty($this->settings['images_per_row']) ? (int)$this->settings['images_per_row'] : 1;
    $maximages = empty($this->settings['number_of_images_to_display']) ? 5 : intval($this->settings['number_of_images_to_display']);
    $maximages = Is_Valid_Number($maximages, 3, 1, 999);

    $curcol = 0; // current column

    if($customDisplay)
    {
      //SD362: allow for current user or specified user
      $uid = ($current_user ? (int)$userinfo['userid'] : $useUserID);
      //SD362: check owner, private status for Media Gallery plugins
      if($isMG)
      {
        $extraquery .= ' AND ((f.owner_id = '.$uid.') OR ((s.owner_id = '.$uid.') AND (IFNULL(f.private,0) = 0)))';
      }

      $orderby = 's.name, s.sectionid DESC, f.datecreated DESC, f.imageid';
    }
    else
    {
      //SD362: use Media Gallery's comprehensive "groupCheck"!
      //SD362: new private status
      if($isMG)
      {
        $extraquery .= $this->gal->GetUserSectionAccessCondition()."\r\n AND (IFNULL(f.private,0) = 0)";
      }

      #SD362: use date also, since this can get updated now
      $orderby = 'f.datecreated DESC, f.imageid';
    }

    $sql = 'SELECT f.*, s.name section_title'."\r\n".
           ' FROM {p'.$this->src_pluginid.'_images} f'."\r\n".
           ' INNER JOIN {p'.$this->src_pluginid.'_sections} s ON s.sectionid = f.sectionid'."\r\n".
           ' WHERE f.activated = 1 AND s.activated = 1'."\r\n".
           $extraquery."\r\n".
           ' ORDER BY '.$orderby.' DESC LIMIT 0, '.$maximages;
    if(!$images = $DB->query($sql))
    {
      return;
    }

    $old = $GLOBALS['sd_ignore_watchdog'];
    $GLOBALS['sd_ignore_watchdog'] = true;

    $header = false;
    $prev_sectionid = 0;
    $tb = 'tb_';
    $md = 'md_';

    $DB->result_type = MYSQL_ASSOC;
    while($image = $DB->fetch_array($images,null,MYSQL_ASSOC))
    {
      $imageid = (int)$image['imageid'];
      $sid = (int)$image['sectionid'];

      //SD362: SEO support for image URLs
      if($isMG)
      {
        if(!$this->gal->SetImageAndSection($imageid, false)) continue;
        if(empty($this->gal->section_arr['can_view'])) continue;
      }

      $title = (empty($image['title']) ? $this->language['untitled'] : $image['title']);
      $folder = isset($image['folder'])?$image['folder']:'';
      $folder .= (strlen($folder) && (sd_substr($folder,strlen($folder)-1,1)!='/') ? '/' : '');

      if($isMG)
      {
        $filename = $this->gal->IMAGEDIR.$folder;
      }
      else
      {
        $filename = 'plugins/'.$this->src_folder.'/images/'.$folder;
      }
      $image['filename'] = isset($image['filename'])?$image['filename']:'';
      if($fullsize || !strlen($image['filename']))
      {
        $filename .= $image['filename'];
      }
      else
      if(file_exists(ROOT_PATH.$filename.$tb.$image['filename']))
      {
        $filename .= $tb.$image['filename'];
      }
      else
      if(file_exists(ROOT_PATH.$filename.$md.$image['filename']))
      {
        $filename .= $md.$image['filename'];
      }
      else
      {
        $filename = '';
      }

      // *** Start output ***
      if(!$header)
      {
        $header = true;
        echo "\r\n".'    <div class="latestimages" id="p'.$this->pluginid.'_latestimages">';
      }

      //SD362: row change upon section change
      if($customDisplay && ($prev_sectionid !== $sid))
      {
        $curcol = 0;
        if($prev_sectionid)
        {
          echo '
        </ul>
        <div style="clear:both"> </div><br />
        ';
        }
        echo '
        <a href="'.$this->gal->current_page.'"><h2>'.$image['section_title'].'</h2></a>
        ';
      }
      if($curcol == 0)
      {
        echo '
        <ul class="image-row">';
      }

      $out = '
          <li><ul class="image">';

      if($popup && strlen($image['filename']))
      {
        $sizew = 0; $sizeh = 0;
        if(file_exists(ROOT_PATH.$filename) && ($size = @getimagesize(ROOT_PATH.$filename)))
        {
          $sizew = (int)$size[0];
          $sizeh = (int)$size[1];
        }
        $link = "<a href='#' onclick=\"window.open('" . SITE_URL.$this->gal->IMAGEDIR.
                '/popup.php?categoryid='.$categoryid.'&amp;p'.$this->src_pluginid.
                '_sectionid='.$sid."&amp;p".$this->src_pluginid.
                "_imageid=".$imageid ."', '', 'width=".($sizew+500).
                ",height=".($sizeh+500).",directories=no,location=no,menubar=no,scrollbars=yes,status=no,toolbar=no,resizable=yes');return false;\" target=\"_blank\">";
      }
      else
      {
        if(!$isMG)
          $link = '<a href="' . RewriteLink('index.php?categoryid='.$categoryid.
                  '&p'.$this->src_pluginid.'_sectionid='.(int)$sid.
                  '&p'.$this->src_pluginid.'_imageid='.$imageid).'">';
        else
          $link = '<a href="' . $this->gal->RewriteImageLink($sid,$imageid,$title).'">';
      }

      $link_done = false;
      if($this->settings['display_image_name'] || ($this->settings['display_author_name'] && !empty($image['author'])))
      {
        $out .= '
              <li class="'.($this->settings['display_image_name']?'title':'author').'">';

        if($this->settings['display_image_name'])
        {
          $out .= $link . $image['title'] . '</a>';
          $link_done = true;
        }

        if($this->settings['display_author_name'] && !empty($image['author']) &&
          (!isset($file['showauthor']) || ($file['showauthor']==1)))
        {
          $closing = '';
          if($this->settings['display_image_name'])
          {
            if($this->settings['display_author_name_on_new_line'])
            {
              $out .= '</li><li class="author">';
            }
            else
            {
              $out .= ' <span class="author">';
              $closing = '</span>';
            }
          }
          $out .= $this->language['by'] . ' ' . $image['author'] . $closing;
        }
        $out .= '</li>';
      }

      if($this->settings['display_image_date'])
      {
        $out .= '
            <li class="date">'.DisplayDate($image['datecreated'],$this->settings['image_date_format'],
                                           empty($this->settings['image_date_format'])).'</li>';
      }

      $thumb_ok = false;
      $mt = empty($image['media_type'])?0:(int)$image['media_type'];
      if(isset($src_settings['max_thumb_width'])) //SD 3+ settings
      {
        $imgw = $sizew = (int)$src_settings['max_thumb_width'];
        $imgh = $sizeh = (int)$src_settings['max_thumb_height'];
      }
      else
      {
        $imgw = $sizew = (int)$src_settings['Max Thumbnail Width'];
        $imgh = $sizeh = (int)$src_settings['Max Thumbnail Height'];
      }

      //SD362: use local thumb regardless of media urls
      if(strlen($filename) && !empty($this->settings['display_thumbnail']) &&
         (!$mt || file_exists(ROOT_PATH.$filename)))
      {
        if($size = @getimagesize(ROOT_PATH.$filename))
        {
          $thumb_ok = true;
          $imgw = $size[0];
          $imgh = $size[1];
          $sizew = empty($sizew) ? $imgw : ($imgw > $sizew ? $sizew : $imgw);
          $sizeh = empty($sizeh) ? $imgh : ($imgh > $sizeh ? $sizeh : $imgh);
          $link_done = true;
          $out .= '
            <li class="thumb">'.$link.'<img title="'.$image['title'].'" class="latest_image" alt="" width="'.$sizew.'" height="'.$sizeh.
            '" style="max-width:'.$sizew.'px;max-height:'.$sizeh.'px" src="'.$sdurl.$filename.'" /></a></li>';
        }
      }
      else
      {
        // Try to display embedded media preview
        if($mt)
        {
          $thumb_ok = true;
          $link_done = true;
          $embed = SD_Media_Base::getMediaPreviewImgTag($mt, unhtmlspecialchars($image['media_url']),$imgw, $imgh);
          $out .= '<li class="thumb">'.$link.$embed.'</a></li>';
        }
        else
        {
          // If thumb not available, then only display image if the name is displayed at least
          $thumb_ok = $this->settings['display_image_name'];
        }
      }

      if(!$link_done) $out .= $link;

      $out .= '
          </ul>
          </li>';
      if($thumb_ok) echo $out;

      $curcol++;

      //SD362: row change upon section change
      if($customDisplay && $prev_sectionid && ($prev_sectionid !== $sid))
      {
        $curcol = 0;
        echo '
        </ul>
        <div style="clear:both"> </div>
        ';
      }
      else
      if($curcol >= $maxcol)
      {
        $curcol = 0;
        echo '
        </ul>
        ';
      }

      $prev_sectionid = $sid;

    } //while
    $GLOBALS['sd_ignore_watchdog'] = $old;

    if($header)
    {
      if(($curcol > 0) && ($curcol < $maxcol))
      {
        echo '
      </ul>';
      }

      echo '
    </div>
    <div style="clear:both"> </div>
    ';
    }

  } //DisplayLatestImages

} // End of Class

} // DO NOT REMOVE
