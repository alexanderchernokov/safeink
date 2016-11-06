<?php
if(!defined('IN_PRGM')) return;

/*
TODO: use classes like SD_Media/SD_Image etc. for thumbnailing etc.
TODO: "VVC" code generation and check needs to use SD3 methods
TODO: image security checks using section folders?
TODO: sort order for sub-sections per section?
*/

if(!class_exists('MediaGalleryClass'))
{

class MediaGalleryClass
{
  private $pluginid   = 0;
  private $action     = '';
  private $imageid    = 0;
  private $sections   = array(); // collection of sections
  private $sectionid  = 0;
  private $submit     = false;
  private $id         = 0;
  private $start      = 0;
  private $base       = null;
  private $tag        = '';
  private $js_code    = ''; //SD362
  private $sql_order  = ''; //SD362
  private $tag_active = ''; //SD362

  public function __construct($plugin_folder)
  {
    global $pluginid, $sd_instances;

    if(isset($sd_instances[$pluginid]) && ($sd_instances[$pluginid] instanceof GalleryBaseClass))
    {
      $this->base = & $sd_instances[$pluginid];
    }
    else
    {
      $this->base = new GalleryBaseClass($plugin_folder);
    }
    $this->pluginid = $this->base->pluginid;
    require_once(SD_INCLUDE_PATH.'class_sd_media.php');
  }

  public function GetBase() //SD362
  {
    return $this->base;
  }

  public function DisplayContent()
  {
    global $DB, $categoryid, $mainsettings_search_results_page,
           $sdlanguage, $userinfo;

    if(empty($this->base->pluginid)) return false;

    //SD362: if access to section was denied, redirect to plugin's main page
    if(empty($this->base->IsSiteAdmin) && empty($this->base->IsAdmin))
    if(empty($this->base->InitStatus) ||
       (!empty($this->base->sectionid) &&
        isset($this->base->section_arr['can_view']) &&
        empty($this->base->section_arr['can_view'])))
    {
      RedirectFrontPage($this->base->plugin_page,$sdlanguage['no_view_access'],2,true);
      $DB->close();
      exit();
    }

    //SD362: if search term was indicated, do nothing and assume, that the
    // Search Engine plugin takes care of results
    if(!$this->base->doSearch && ($categoryid == $mainsettings_search_results_page))
    {
      return true;
    }

    if(!sd_safe_mode())
    {
      $old_ignore = $GLOBALS['sd_ignore_watchdog'];
      $GLOBALS['sd_ignore_watchdog'] = false;
      @set_time_limit(3600);
      $GLOBALS['sd_ignore_watchdog'] = $old_ignore;
    }

    $this->action = GetVar($this->base->pre.'_action','','string');
    $this->id     = GetVar($this->base->pre.'_id',0,'whole_number');
    $this->start  = GetVar($this->base->pre.'_start',1,'whole_number');
    $this->tag    = GetVar($this->base->pre.'_tag','','string',false,true);

    //SD362: global tags page support
    if(!empty($this->base->slug_arr) && is_array($this->base->slug_arr))
    {
      $this->base->isTagPage = true;
      $this->base->sectionid = $this->sectionid = 1;
      $this->base->imageid = 0;
    }
    else
    {
      //SD360: make use of base instance, that may already have
      // processed section/image parameters by SEO etc.
      if($this->base->InitStatus)
      {
        // Use parameters from base instance
        $this->sectionid = $this->base->sectionid;
        $this->imageid   = $this->base->imageid;
      }
      else
      {
        // Retrieve parameters from buffer
        $this->sectionid = GetVar($this->base->pre.'_sectionid',0,'whole_number');
        $this->imageid   = GetVar($this->base->pre.'_imageid',0,'whole_number');
      }

      if(empty($this->imageid) || ($this->imageid < 1) || ($this->imageid > 99999999))
      {
        $this->imageid = $this->base->imageid = 0;
      }
    }

    if(empty($this->sectionid) || ($this->sectionid < 1) || ($this->sectionid > 99999999))
    {
      $this->sectionid = $this->base->sectionid = 0;
    }

    $this->sections = array();
    #TESTING:
    if(($this->sectionid == 1) && isset($this->base->gal_cache[0]))
    {
      #$this->base->gal_cache[1] = $this->base->gal_cache[0];
      #unset($this->base->gal_cache[0]);
    }
    if($this->base->InitStatus &&
       !empty($this->base->gal_cache[$this->sectionid]['loaded']) &&
       (!empty($this->tag) || isset($this->base->gal_cache[$this->sectionid]['sections'])) )
    {
      if(isset($this->base->gal_cache[$this->sectionid]['sections']))
        $this->sections = & $this->base->gal_cache[$this->sectionid]['sections'];
      else
        $this->sections = array();
      $this->sections[$this->sectionid] = & $this->base->gal_cache[$this->sectionid]['section'];
      if(!empty($this->sections) && is_array($this->sections))
      {
        ksort($this->sections);
      }
    }
    else
    if($getsections = $DB->query('SELECT * FROM '.$this->base->sections_tbl.' s'.
                                 ' WHERE sectionid IN (1,%d) '.
                                 $this->base->GroupCheck.
                                 ' ORDER BY s.sectionid', $this->sectionid))
    {
      while($section = $DB->fetch_array($getsections,null,MYSQL_ASSOC))
      {
        $this->sections[(int)$section['sectionid']] = $section;
      }
    }

    if(empty($this->tag) && !isset($this->sections[$this->sectionid]))
    {
      $this->sectionid = 1;
    }
    $this->base->sectionid = $this->sectionid;
    $this->base->section_arr = & $this->sections[$this->sectionid];

    // Process moderation OR "owner" actions (SD360/SD362)
    if(!empty($this->id) && in_array($this->action, array('ai','di','img_delete_confirm','ui',
                                                    'update_media','update_section')))
    {
      // Security check against spam/bot submissions
      if(!CheckFormToken())
      {
        DisplayMessage('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
        return false;
      }

      //SD362: new actions require correct settings
      if($this->action == 'update_section')
      {
        if(!$this->base->SetSection($this->id,false))
        {
          return false;
        }
      }
      else
      if(!$this->base->SetImageAndSection($this->id,false))
      {
        return false;
      }

      $result = false;
      $this->imageid = $this->base->imageid;
      $this->sectionid = $this->base->sectionid;
      $this->base->CheckPermissions(); //SD362

      if(($this->action == 'di') && $this->base->allow_media_delete &&
         !empty($this->base->settings['display_delete_link']))
      {
        return $this->DisplayImageDeleteForm();
      }

      if(($this->action == 'img_delete_confirm') && $this->base->allow_media_delete &&
         !empty($this->base->settings['display_delete_link']))
      {
        $key = $this->base->GetUserActionKey($this->imageid);
        $posted_key = GetVar($this->base->pre.'_'.$key, false, 'bool', true, false);
        if(empty($posted_key))
        {
          RedirectFrontPage($this->base->current_page,
                            $this->base->language['delete_image_unconfirmed'], 2);
          return false;
        }
        $result = $this->DeleteImage();
      }
      else
      if(!empty($this->base->allow_mod) && ($this->action == 'ai') &&
         !empty($this->base->settings['display_approve_link']))
      {
        $result = $this->ApproveImage();
      }
      else
      if(!empty($this->base->allow_mod) && ($this->action == 'ui') &&
         !empty($this->base->settings['display_unapprove_link']))
      {
        $result = $this->UnapproveImage();
      }
      else
      if($this->action == 'update_media') //SD362
      {
        $result = $this->UpdateMedia();
        if($result && ($this->base->imageid > 0) && ($this->base->sectionid > 0))
        {
          $this->base->current_page = $this->base->RewriteImageLink($this->base->sectionid, $this->base->imageid);
        }
      }
      else
      if($this->action == 'update_section') //SD362
      {
        $result = $this->UpdateSection();
      }

      // Clear variables
      $this->imageid = $this->base->imageid = 0;
      $this->base->image_arr = array();
      unset($this->id,$_GET[$this->base->pre.'_action'],$_POST[$this->base->pre.'_action']);
      if($result)
      {
        RedirectFrontPage($this->base->current_page,
                          $this->base->language['action_successful'], 2);
      }
      else
      {
        RedirectFrontPage($this->base->current_page,
                          $sdlanguage['err_invalid_operation'], 2, true);
      }
      return $result;
    }

    $this->submit = ($this->base->sectionid > 0) &&
                    (($this->base->IsAdmin || $this->base->IsSiteAdmin) ||
                     ($this->base->AllowSubmit &&
                      ($this->base->allow_files || $this->base->allow_links) &&
                      (!empty($this->base->settings['allow_root_section_upload']) ||
                       ($this->base->sectionid > 1))));
    echo '
    <!-- Media Gallery '.$this->pluginid.' plugin - Start //-->
    <div id="'.$this->base->pre.'_imagegallery">
    ';

    if(($this->action == 'insertmedia') && $this->submit)
    {
      $this->InsertMedia($this->sectionid);
    }
    else
    if(($this->action == 'submitimage') && $this->submit)
    {
      $this->SubmitImageForm();
    }
    else
    if($this->base->allow_mod ||
       (!empty($userinfo['pluginviewids']) &&
        @in_array($this->pluginid,$userinfo['pluginviewids'])))
    {
      $this->DisplayImages();
    }

    echo '
    </div>
    <!-- Media Gallery '.$this->pluginid.' plugin - End //-->
    ';

  } //DisplayContent


  // ##########################################################################
  // DISPLAY HEADER
  // ##########################################################################

  public function DisplayHeader($doInitTemplate=false)
  {
    global $bbcode, $sdlanguage, $userinfo;

    if(!empty($doInitTemplate))
    {
      $this->base->InitTemplate();
      $this->base->tmpl->assign('sectionid', $this->sectionid);
      $this->base->tmpl->assign('tag', (empty($this->tag)?false:(string)$this->tag));
      $this->base->tmpl->assign('display_mode', (int)$this->base->section_arr['display_mode']);
      $this->base->tmpl->assign('image_nav_links', '');
      $this->base->tmpl->assign('pagination_html', false);
      $this->base->tmpl->assign('submit_allowed', $this->submit);
    }

    $this->base->tmpl->assign('section_menu_items', false);
    $this->base->tmpl->assign('section_hierarchy', '');

    //SD362: do not fill jump menu for submit page or when option disabled
    if(!empty($this->base->settings['display_jump_menu']) &&
       ($this->action != 'submitimage'))
    {
      ob_start();
      $this->PrintSectionItems(1, null);
      $tmp = ob_get_contents();
      ob_end_clean();
      $this->base->tmpl->assign('section_menu_items', $tmp);
    }

    ob_start();
    $this->PrintSectionHierarchy($this->sectionid);
    $tmp = ob_get_contents();
    ob_end_clean();
    $this->base->tmpl->assign('section_hierarchy', $tmp);

    // OUTPUT MEDIA GALLERY HEADER
    $tmpl_done = SD_Smarty::display($this->pluginid, 'gallery_header.tpl');
    if(!$tmpl_done && $this->base->IsSiteAdmin)
    {
      echo '<pre>'.sd_wordwrap(sd_unhtmlspecialchars(SD_Smarty::getLastError()),60,'<br />').'</pre>';
    }

  } //DisplayHeader


  // ##########################################################################
  // PRINT SECTION HIERARCHY
  // ##########################################################################

  public function PrintSectionItems($parentid, $parentname)
  {
    global $DB, $categoryid, $userinfo;

    $DB->result_type = MYSQL_ASSOC;
    $sql =
      'SELECT s.sectionid, s.name FROM '.$this->base->sections_tbl.' s'.
      ' WHERE (s.parentid = %d)'.
      #($this->base->IsSiteAdmin || $this->base->IsAdmin ? '':
      ' AND (IFNULL(s.activated,0) = 1) '
      #)
      .$this->base->GroupCheck .
      ' ORDER BY s.' .
      $this->base->GetSubSectionsSort($this->base->settings['section_sort_order']);

    $getsection = $DB->query($sql, $parentid);

    while($section = $DB->fetch_array($getsection,null,MYSQL_ASSOC))
    {
      echo '
      <option value="' . $section['sectionid'] . '"';

      if($this->sectionid == $section['sectionid'])
      {
        echo ' selected="selected" ';
      }

      if(strlen($parentname) > 0)
      {
        $sectionname = $parentname . ' &raquo; ' . $section['name'];
      }
      else
      {
        $sectionname = $section['name'];
      }

      echo '>' . $sectionname . '</option>';

      $this->PrintSectionItems($section['sectionid'], $sectionname);
    }
  } //PrintSectionItems

  // #############################################################################

  public function PrintSectionHierarchy($sectionid)
  {
    global $DB, $userinfo;

    $DB->result_type = MYSQL_ASSOC;
    if($section = $DB->query_first(
       'SELECT s.parentid, s.name'.
       ' FROM '.$this->base->sections_tbl.' s'.
       ' WHERE s.sectionid = %d'.
       ' AND (s.sectionid = 1 OR s.activated = 1) '.
       $this->base->GroupCheck .
       ' ORDER BY s.'.$this->base->GetSubSectionsSort($this->base->settings['section_sort_order']),
       $sectionid))
    {
      if($section['parentid'] > 0)
      {
        $this->PrintSectionHierarchy($section['parentid']);
        echo '&nbsp;&raquo;&nbsp;';
      }

      echo '<a href="'.$this->base->RewriteSectionLink($sectionid).'">'.$section['name'].'</a>';
    }

  } //PrintSectionHierarchy


  // ##########################################################################
  // INSERT A NEW MEDIA ENTRY
  // ##########################################################################

  public function InsertMedia()
  {
    global $DB, $sdlanguage, $userinfo;

    // Security check against spam/bot submissions
    if(!CheckFormToken('', false))
    {
      DisplayMessage('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
      return;
    }

    // get post variables
    $image         = ($this->base->allow_files && !empty($_FILES[$this->base->pre.'_image'])) ? $_FILES[$this->base->pre.'_image'] : null;
    $thumbnail     = ($this->base->allow_files && !empty($_FILES[$this->base->pre.'_thumbnail'])) ? $_FILES[$this->base->pre.'_thumbnail'] : null;
    $showauthor    = 1;
    $author        = trim(GetVar($this->base->pre.'_author','','string',true,false));
    $title         = trim(GetVar($this->base->pre.'_title', '', 'string',true,false));
    $description   = trim(GetVar($this->base->pre.'_description', '', 'string',true,false));
    $tags          = trim(GetVar($this->base->pre.'_tags','','string',true,false));
    $activated     = ($this->base->IsAdmin || $this->base->settings['auto_approve_images']?1:0);
    $owner_id      = empty($userinfo['userid'])?0:$userinfo['userid'];
    if($this->base->allow_links) //SD362
      $media_url     = GetVar($this->base->pre.'_media_url','','string',true,false); //SD360
    else
      $media_url     = '';
    $allowcomments = (GetVar($this->base->pre.'_comments',0,'bool',true,false)?1:0); //SD362
    $allowratings  = (GetVar($this->base->pre.'_ratings',0,'bool',true,false)?1:0); //SD362
    $private       = (GetVar($this->base->pre.'_private',0,'bool',true,false)?1:0); //SD362

    $errors = array();
    if(!CaptchaIsValid('amihuman'))
    {
      $errors[] = $sdlanguage['captcha_not_valid'];
    }

    //SD360: allow media site links
    $imageid = 0;
    $mt = 0;

    if(!empty($media_url))
    {
      if((strlen(trim($media_url)) < 5) ||
         !($mt = SD_Media_Base::isMediaUrlValid($media_url,false)))
      {
        $errors[] = $this->base->language['media_url_unsupported'];
        $mt = 0;
        $media_url = '';
      }
      else
      {
        $mt = SD_Media_Base::GetMediaType(sd_unhtmlspecialchars($media_url));
      }
    }
    else
    {
      $media_url = '';
    }

    // Check uploaded IMAGE (malicious attempts will halt)
    if(!empty($image))
    {
      // -1 means serious error, potentially harmful attempt!
      if($this->base->CheckImage($image, false, $this->base->language['imagesize_error'], $errors) === -1)
      {
        DisplayMessage($errors, true);
        return false;
      }

      if(!empty($this->base->settings['auto_create_thumbs']) && isset($image) &&
         ($image['type'] == 'image/gif') && !function_exists('imagecreatefromgif'))
      {
        $errors[] = $this->base->language['no_gif_support'];
      }
    }

    // Check uploaded THUMBNAIL (malicious attempts will halt)
    if(!empty($thumbnail) &&
       ($this->base->CheckImage($thumbnail, empty($this->base->settings['auto_create_thumbs']),
                                $this->base->language['thumbsize_error'], $errors) === -1))
    {
      DisplayMessage($errors, true);
      return false;
    }

    // Check attributes (title, author, description etc.)
    if(!empty($this->base->settings['image_title_required']) && !strlen($title))
    {
      $errors[] = $this->base->language['enter_title'];
    }

    if(!empty($this->base->settings['image_author_required']) && !strlen($author))
    {
      $errors[] = $this->base->language['enter_author'];
    }

    if(!empty($this->base->settings['image_description_required']) && !strlen($description))
    {
      $errors[] = $this->base->language['enter_description'];
    }

    // ***** IF errors exist, display them and go back to submit form *****
    if(!empty($errors))
    {
      $this->SubmitImageForm($errors);
      return false;
    }

    //SD343: don't allow dangerous titles, file names or descriptions
    if( preg_match("#\.php([2-6]?)|\.bat|\.(p?)html?|\.pl|\.sh|\.js|\.cgi|\.cmd|\.com|\.exe|\.vb|<script|<html|<head|<\?php|<title|<body|<pre|<table|<a(\s*)href|<img|<plaintext|<cross\-domain\-policy#si", sd_unhtmlspecialchars($title)) ||
        preg_match("#<script|<html|<head|<\?php|<title|<body|<pre|<table|<a\s+href|<img|<plaintext|<cross\-domain\-policy#si", sd_unhtmlspecialchars($description)) ||
        preg_match("#<script|<html|<head|<\?php|<title|<body|<pre|<table|<a\s+href|<img|<plaintext|<cross\-domain\-policy#si", sd_unhtmlspecialchars($author)) ||
        (isset($image['name']) &&
         preg_match("#\.php([2-6]?)|\.bat|\.(p?)html?|\.pl|\.sh|\.js|\.cgi|\.cmd|\.com|\.exe|\.vb|<script|<html|<head|<\?php|<title|<body|<pre|<table|<a(\s*)href|<img|<plaintext|<cross\-domain\-policy#si", $image['name'])) )
    {
      $errors[] = $this->base->language['invalid_image_upload'].'<br />';
      $this->SubmitImageForm($errors);
      return false;
    }

    // Data checks are done, let's get stuff added...

    // Determine target folder depending on section:
    $section_folder = '';
    $target_folder = $this->base->IMAGEPATH;
    $DB->result_type = MYSQL_ASSOC;
    if(($this->sectionid > 0) && isset($this->base->section_arr['folder']) &&
       strlen(trim($this->base->section_arr['folder'])) &&
       ($this->base->section_arr['folder'] != '0'))
    {
      $section_folder = $this->base->section_arr['folder'];
      $section_folder .= (strlen($section_folder) && (sd_substr($section_folder,strlen($section_folder)-1,1)!='/') ? '/' : '');
      $target_folder .= $section_folder;
    }

    // At this point do the actual insert to get an ID
    if(!empty($image['name']) || !empty($mt))
    {
      $DB->query('INSERT INTO '.$this->base->images_tbl." (sectionid, activated, filename,
        allowcomments, allow_ratings, private,
        showauthor, author, title, description, datecreated,
        px_width, px_height, owner_id, media_type, media_url)
        VALUES (%d, %d, '',
        %d, %d, %d,
        %d, '%s', '%s', '%s', %d,
        0, 0, %d, %d, '%s')",
        $this->sectionid, $activated,
        $allowcomments, $allowratings, $private,
        $showauthor, $author, $title, $description, TIME_NOW,
        $owner_id, $mt, $DB->escape_string(sd_unhtmlspecialchars($media_url)));

      if(!$imageid = $DB->insert_id())
      {
        return false;
      }
    }

    // In case an image was uploaded, process the actual file now
    if(!empty($image['name']))
    {
      $filetype = isset($image['type']) ? (string)$image['type'] : 'image/jpeg';
      $extention = array_key_exists($filetype, SD_Media_Base::$known_type_ext) ?
                                    SD_Media_Base::$known_type_ext[$filetype] : 'jpg';
      $filename = $imageid.'.'.$extention;

      if(!strlen($title) || empty($imageid) ||
         !is_uploaded_file($image['tmp_name']) ||
         !@move_uploaded_file($image['tmp_name'], $target_folder.$filename))
      {
        $errors[] = $this->base->language['invalid_image_type'].'<br />';
        $DB->query('DELETE FROM '.$this->base->images_tbl.' WHERE imageid = %d', $imageid);
        $this->imageid = 0;
        $this->SubmitImageForm($errors);
        return;
      }

      // Security check
      if(!SD_Media_Base::ImageSecurityCheck(true,#$this->base->settings['image_security_check'],
                                            $target_folder, $filename))
      {
        @unlink($target_folder.$filename);
        if(!empty($imageid))
        {
          $DB->query("DELETE FROM {".$this->pre."_images} WHERE imageid = %d", (int)$imageid);
        }
        Watchdog('Media Gallery '.$this->pluginid, 'Malicious image upload attempt by '.
          (empty($userinfo['loggedin']) ? 'guest' : ('user: '.$userinfo['username'])), WATCHDOG_ERROR);
        echo $this->base->language['invalid_image_upload'].'<br />';
        $this->imageid = 0;
        return false;
      }

      // Determine dimensions of image:
      $sql = '';
      if(file_exists($target_folder.$filename) && ($size = @getimagesize($target_folder.$filename)))
      {
        $sql = ', px_width = '.(int)$size[0].', px_height = '.(int)$size[1];
      }

      // Update image row with final filename and dimensions (if applicable):
      $DB->query('UPDATE '.$this->base->images_tbl.
                 " SET filename = '%s', folder = '%s'".$sql.' WHERE imageid = %d',
                 $filename, ($section_folder!==false?$DB->escape_string($section_folder):''), $imageid);

      // Make sure it's readable
      @chmod($target_folder . $filename, intval("0644", 8));

      // Create thumbnail from uploaded image?
      if(!empty($this->base->settings['auto_create_thumbs']))
      {
        SD_Media_Base::requestMem();
        $res = CreateThumbnail($target_folder . $filename, $target_folder . $this->base->TB_PREFIX . $filename,
                               $this->base->settings['max_thumb_width'],
                               $this->base->settings['max_thumb_height'],
                               $this->base->settings['square_off_thumbs']);
        if(!empty($res))
        {
          echo $res;
        }
        else
        if(is_file($target_folder . $this->base->TB_PREFIX . $filename))
        {
          @chmod($target_folder . $this->base->TB_PREFIX . $filename, 0644);
        }
        unset($thumbnail);

        if($this->base->settings['create_midsize_images'])
        {
          $res = CreateThumbnail($target_folder . $filename, $target_folder . $this->base->MD_PREFIX . $filename,
                                 $this->base->settings['max_midsize_width'],
                                 $this->base->settings['max_midsize_height'],
                                 $this->base->settings['square_off_midsize']);
          if(!empty($res))
          {
            echo $res;
          }
          else
          {
            if(is_file($target_folder.$this->base->MD_PREFIX.$filename))
            {
              @chmod($target_folder.$this->base->MD_PREFIX.$filename, 0644);
            }
            if(!$this->base->settings['save_fullsize_images'])
            {
              @unlink($target_folder.$filename);
              // Here we would usually clear the 'filename' column in the DB but unfortunately
              // the thumbnail/midsize code needs it so we'll use file_exists instead
            }
          }
        }
      }
    }

    // Process uploaded thumbnail file
    if(!empty($thumbnail) && !empty($thumbnail['tmp_name']) && empty($thumbnail['error']))
    {
      if(true === ($msg = SD_Image_Helper::UploadImageAndCreateThumbnail(
                            $this->base->pre.'_thumbnail', !empty($mt),
                            $this->base->IMAGEDIR.$section_folder,
                            $imageid.(empty($mt)?'-tmp':''),
                            (empty($mt)?'':$this->base->TB_PREFIX).$imageid,
                            $this->base->settings['max_thumb_width'],
                            $this->base->settings['max_thumb_height'],
                            8192*1024, false, true, true, $img_obj)))
      {
        $filename = $imageid.'.'.$img_obj->getImageExt();
        if(!empty($mt))
        {
          $DB->query('UPDATE '.$this->base->images_tbl.
                     " SET filename = '%s', folder = '%s' WHERE imageid = %d",
                     $filename, ($section_folder!==false?$DB->escape_string($section_folder):''), $imageid);
        }
      }
      else
      {
        $errors[] = $this->base->language['invalid_thumb_upload'];
      }
      unset($img_obj);
    }

    // Process Tags
    $this->base->StoreImageTags($imageid, $tags);

    $email = $this->base->settings['image_notification'];

    if(!empty($email))
    {
      // obtain emails
      $getemails = str_replace(',', ' ', $email);            // get rid of commas
      $getemails = preg_replace('#\s[\s]+#', ' ', $getemails); // get rid of extra spaces
      $getemails = trim($getemails);                         // then trim
      $emails_arr = explode(" ", $getemails);

      $fullname = $this->base->language['notify_email_from'];
      $subject  = $this->base->language['notify_email_subject'];
      $message  = $this->base->language['notify_email_message'] . EMAIL_CRLF;
      $message .= $this->base->language['notify_email_author'] . ' - ' . $author . EMAIL_CRLF;
      $message .= $this->base->language['notify_email_title'] . ' - ' . $title . EMAIL_CRLF;
      $message .= $this->base->language['notify_email_description'] . ' - ' . $description . EMAIL_CRLF;

      for($i = 0; $i < count($emails_arr); $i++)
      {
        SendEmail(trim($emails_arr[$i]), $subject, $message, $fullname, null,null,null,false);
      }
    }

    if($activated)
    {
      $this->base->UpdateImageCounts();
    }
    RedirectFrontPage($this->base->current_page,$this->base->language['image_submitted'],2);
    return;

  } //InsertMedia


  // ##########################################################################
  // SUBMIT IMAGE
  // ##########################################################################

  public function SubmitImageForm($errors = null)
  {
    global $DB, $categoryid, $userinfo, $inputsize, $sdlanguage;

    $this->DisplayHeader(true);

    echo '<div class="form_header">'.$this->base->language['submitting_image'] . '</div>';

    if(!empty($errors))
    {
      DisplayMessage($errors,true);
    }
    if(strlen($this->base->settings['allowed_image_types']) < 3)
    {
      echo $this->base->language['submit_offline'];
      return false;
    }

    $this->base->InitTemplate();

    $captcha = DisplayCaptcha(false,'amihuman');

    $title  = trim(GetVar($this->base->pre.'_title', '', 'string', true, false));
    $author = trim(GetVar($this->base->pre.'_author', '', 'string', true, false));
    $descr  = trim(GetVar($this->base->pre.'_description', '', 'string', true, false));
    $url    = trim(GetVar($this->base->pre.'_media_url', '', 'string', true, false));
    $tags   = trim(GetVar($this->base->pre.'_tags', '', 'string', true, false)); //SD362
    $allowcomments = (GetVar($this->base->pre.'_comments',0,'bool',true,false)?1:0); //SD362
    $allowratings  = (GetVar($this->base->pre.'_ratings',0,'bool',true,false)?1:0); //SD362
    $private       = (GetVar($this->base->pre.'_private',0,'bool',true,false)?1:0); //SD362

    //Note: "section" variable is assigned to template in DisplayHeader()
    $this->base->tmpl->assign('media_author', $author);
    $this->base->tmpl->assign('media_description', $descr);
    $this->base->tmpl->assign('media_title', $title);
    $this->base->tmpl->assign('media_url', $url);
    $this->base->tmpl->assign('media_tags', $tags);
    $this->base->tmpl->assign('media_option_comments', $allowcomments);
    $this->base->tmpl->assign('media_option_ratings', $allowratings);
    $this->base->tmpl->assign('media_option_private', $private);
    $this->base->tmpl->assign('upload_captcha', $captcha);
    $upload_link = $this->base->current_page.(strpos($this->base->current_page,'?')===false?'?':'&amp;').$this->base->pre.'_action=insertmedia';
    $this->base->tmpl->assign('upload_link', $upload_link);

    // OUTPUT UPLOAD FORM TEMPLATE
    $tmpl_done = SD_Smarty::display($this->pluginid, 'upload_form.tpl');
    if(!$tmpl_done && $this->base->IsSiteAdmin)
    {
      echo '<pre>upload_form: '.sd_wordwrap(sd_unhtmlspecialchars(SD_Smarty::getLastError()),60,'<br />').'</pre>';
    }

  } //SubmitImageForm

  // #############################################################################

  public function DisplayPrevNextLinks($page, $items_per_page, $imagecount, $class='')
  {
    global $categoryid;

    $result = '
    <table border="0" cellpadding="0" cellspacing="0" '.($class?'class="'.$class.'" ':'').'summary="layout" width="100%">
    <tr>
      <td class="previous_img" align="left">';
    if($page > 1)
    {
      $result .= '<a href="' .
        $this->base->RewriteSectionLink($this->sectionid,false,$page-1).
        '">'.$this->base->language['previous_images'].'</a>';
    }
    $result .= '</td><td class="next_img" align="right">';
    if(($page * $items_per_page) < $imagecount)
    {
      $result .= '<a href="' .
        $this->base->RewriteSectionLink($this->sectionid,false,$page+1).
        '">'.$this->base->language['more_images'].'</a>';
    }
    $result .= "</td></tr></table>\n";

    return $result;

  } //PrintPageLink

  // #############################################################################

  private function GetModLinks($isActive) //SD360
  {
    global $userinfo;

    $result = '';
    if(empty($userinfo['userid']) || empty($this->base->image_arr['imageid']) ||
       empty($this->base->section_arr['sectionid'])) return $result;

    $suffix = $this->base->pre.'_id='.$this->base->image_arr['imageid'].'&amp;'.$this->base->pre.'_action=';
    if(!empty($this->base->allow_mod))
    {
      if(empty($isActive))
      {
        if(!empty($this->base->settings['display_approve_link']))
        {
          $result = '<a href="'.
            $this->base->RewriteSectionLink($this->base->section_arr['sectionid'],false,1,
            $suffix.'ai'.PrintSecureUrlToken()).
            '">'.$this->base->language['approve'].'</a>';
        }
      }
      else
      {
        if(!empty($this->base->settings['display_unapprove_link']))
        {
          $result = '<a href="'.
            $this->base->RewriteSectionLink($this->base->section_arr['sectionid'],false,1,$suffix.
            'ui'.PrintSecureUrlToken()).
            '">'.$this->base->language['unapprove'].'</a>';
        }
      }
    }

    //SD360: links for user to delete own media or media in own section
    if(!empty($this->base->settings['display_delete_link']) && $this->base->allow_media_delete)
    {
      if($result != '') $result .= ' - ';
      $result .= ' <a href="'.
        $this->base->RewriteSectionLink($this->sectionid,false,1,$suffix.
        'di'.PrintSecureUrlToken()).
                     '">'.$this->base->language['delete'].'</a>';
    }

    return $result;

  } //GetModLinks

  // #############################################################################

  public function DisplaySubsections()
  {
    // IMPORTANT: below code MUST implement all viewing permission-rules,
    // that would normally be done by SQL condition, because of dealing
    // with cached values, not Selects!
    global $DB, $categoryid, $sdurl, $sdlanguage, $userinfo, $bbcode;

    // Fullfil special Ajax request for paginated sections display
    if(Is_Ajax_Request())
    {
      if(!$categoryid = Is_Valid_Number(GetVar('categoryid', 1, 'whole_number'),0,1,99999999))
      {
        return;
      }
      if(!$sectionid = Is_Valid_Number(GetVar('sectionid', 1, 'whole_number'),0,1,99999999))
      {
        return;
      }
      $this->base->categoryid = (int)$categoryid;
      $this->base->InitSectionCache($sectionid);
      $this->sectionid = $sectionid;
      $this->base->section_arr = & $this->base->gal_cache[$sectionid]['section'];

      $this->base->plugin_page = RewriteLink('index.php?categoryid='.$categoryid);
      $this->base->current_page = $this->base->RewriteSectionLink($this->sectionid);

      $this->base->InitTemplate();
      $this->base->tmpl->assign('sectionid', $this->sectionid);
      $this->base->tmpl->assign('tag', (empty($this->tag)?false:(string)$this->tag));
      $this->base->tmpl->assign('display_mode', (int)$this->base->section_arr['display_mode']);
      $this->base->tmpl->assign('image_nav_links', '');
      $this->base->tmpl->assign('pagination_html', false);
      $this->base->tmpl->assign('submit_allowed', $this->submit);
    }

    if(isset($this->base->section_arr['section_sorting']))
    {
      $sorttype = $this->base->section_arr['section_sorting'];
    }
    $sorttype = isset($sorttype)?(string)$sorttype:'0';
    // This switch supports values from either plugin
    // setting or section itself:
    $sortkey = 'datecreated';
    $sortdir = 'desc';
    switch($sorttype)
    {
      case '0':
      case 'newest_first':
        $sortkey = 'datecreated';
        $sortdir = 'desc';
        break;
      case '1':
      case 'oldest_first':
        $sortkey = 'datecreated';
        $sortdir = 'asc';
        break;
      case '2':
      case 'alpha_az':
        $sortkey = 'name';
        $sortdir = 'asc';
        break;
      case '3':
      case 'alpha_za':
        $sortkey = 'name';
        $sortdir = 'desc';
        break;
    }

    $page_start = 0;
    $page = 1;
    $limit = '';
    $items_per_page = empty($this->base->section_arr['max_subsections'])?0:(int)$this->base->section_arr['max_subsections'];
    if($items_per_page > 0)
    {
      $page = Is_Valid_Number(GetVar('sections', 1, 'whole_number'),1,1,999999);
      $page_start = ($page-1)*$items_per_page;
      $limit = ' LIMIT '.$page_start.','.$items_per_page;
    }

    $load_subs = true;
    $section_table_opened = false;
    $sectionrows = 0;
    $sections = array();
    if(!empty($this->base->gal_cache[$this->sectionid]['loaded']))
    {
      // Fetch sections from cache and sort them
      $this->base->section_arr = $this->base->gal_cache[$this->sectionid]['section'];
      if($sectionrows = (empty($this->base->section_arr['subsections'])?0:(int)$this->base->section_arr['subsections']))
      {
        $load_subs = false;
        $cache_sections = $this->base->gal_cache[$this->sectionid]['sections'];
        if(!empty($cache_sections) && is_array($cache_sections))
        {
          // Remove "current" section, which is part of cache:
          unset($cache_sections[$this->sectionid]);

          // Filter sections pending permissions
          $count = 0;
          foreach($cache_sections as $sect)
          {
            if(!empty($sect['activated']) &&
               (#($this->base->IsAdmin || $this->base->IsSiteAdmin) ||
                $this->base->CheckAccessForSection($sect)) &&
               (empty($sect['publish_start']) || ($sect['publish_start'] <= TIME_NOW)) &&
               (empty($sect['publish_end']) || ($sect['publish_end'] >= TIME_NOW))
               )
            {
              $sections[strtolower($sect[$sortkey])] = $sect;
            }
          }
          // Sort sections based on key and direction
          if(count($sections))
          {
            if($sortdir=='asc')
              usort($sections, "sd_natsortarrayname");
            else
              usort($sections, "sd_natsortarraynamereverse");
          }
          unset($cache_sections,$sect);

          // Take into account pagination (pagestart):
          if(!empty($items_per_page) && !empty($page) && !empty($sections) )
          {
            $sections = array_slice($sections, $page_start, $items_per_page);
          }
        }
      }
    }
    #else
    if($load_subs && ($getsubsections = $DB->query(
       'SELECT s.* FROM '.$this->base->sections_tbl.' s'.
       ' WHERE s.parentid = %d AND s.activated = 1 '.
       ' AND s.sectionid <> %d '.
       $this->base->GroupCheck .
       ' ORDER BY s.'.$this->base->GetSubSectionsSort($sorttype),
       $this->sectionid, $this->sectionid)))
    {
      if($sectionrows = $DB->get_num_rows($getsubsections))
      {
        $count = 0;
        // display subsections
        while($subsection = $DB->fetch_array($getsubsections,null,MYSQL_ASSOC))
        {
          $count++;
          $sections[(int)$subsection['sectionid']] = $subsection;
          if(($items_per_page > 0) && ($count >= $items_per_page))
          {
            break;
          }
        }
      }
    }

    //SD362: generate list of uniqe contributors with link and counts (excl. owner):
    // obeys the new "private" flag for the image owner (except for admins)
    $contribs_arr = array();
    if($getcontribs = $DB->query(
       'SELECT f.owner_id, COUNT(*) ic'.
       ' FROM '.$this->base->images_tbl.' f'.
       ' WHERE f.sectionid = %d AND f.activated = 1'.
       ' AND (IFNULL(f.owner_id,0) > 0)'.
       ($this->base->IsSiteAdmin || $this->base->IsAdmin ? '' :
       ' AND ((IFNULL(f.private,0) = 0) OR (f.owner_id='.(int)$userinfo['userid'].'))').
       (empty($subsection['owner_id'])?'':' AND f.owner_id <> '.$subsection['owner_id']).
       ' GROUP BY f.owner_id ORDER BY COUNT(*) DESC',
       $this->sectionid))
    {
      while($contrib = $DB->fetch_array($getcontribs,NULL,MYSQL_ASSOC))
      {
        if($cid = empty($contrib['owner_id'])?0:(int)$contrib['owner_id'])
        {
          $tmp = SDUserCache::CacheUser($cid,'',false,false);
          if(!empty($tmp['userid']) && !empty($tmp['activated']) && !empty($tmp['username']) && empty($tmp['banned']))
          {
            if(isset($contribs_arr[$cid]))
              $contribs_arr[$cid]['count'] += $contrib['ic'];
            else
              $contribs_arr[$cid] = array('link' => $tmp['profile_link'], 'count' => (int)$contrib['ic']);
          }
        }
      }
    }
    $this->base->tmpl->assign('contributors', $contribs_arr);
    unset($contribs_arr,$contrib,$cid,$tmp);

    $this->base->tmpl->assign('sections_count', $sectionrows);
    if($sectionrows)
    {
      // initialize a few variables
      $curr_section_table_column = 0;
      // display subsections
      foreach($sections as & $subsection)
      {
        $subsection['owner_details'] = false;
        $subsection['section_added_label'] = '';
        if(!empty($subsection['owner_id']))
        {
          if($subsection['owner_details'] = SDUserCache::CacheUser($subsection['owner_id'],'',false,false))
          {
            $tmp = $this->base->language['section_added_label'];
            $tmp = str_replace('[owner]',$subsection['owner_details']['profile_link'],$tmp);
            $tmp = str_replace('[datecreated]',DisplayDate($subsection['datecreated'],'',true),$tmp);
            $subsection['section_added_label'] = $tmp;
          }
        }

        if(!empty($subsection['link_to']))
          $subsection['link'] = $subsection['link_to'];
        else
          $subsection['link'] = $this->base->RewriteSectionLink($subsection['sectionid']);

        if($this->base->settings['display_section_image_count'])
        {
          $numsectionimages = $subsection['imagecount'];
          $numsectionimages_phrase = (empty($this->base->settings['display_sections_as_images'])?'&nbsp;':'<br />') .
            '(' . $numsectionimages . ' ' .
            ($numsectionimages==1?$this->base->language['image2']:$this->base->language['images']) . ')';
        }
        else
        {
          $numsectionimages_phrase = '';
        }

        if($this->base->settings['display_sections_as_images'])
        {
          $curr_section_table_column++;

          // open the table for the sections
          if(!$section_table_opened)
          {
            $section_table_opened = true;
          }
          $tmp = $sdurl.$this->base->IMAGEDIR;
          $section_img = '<img alt="'.addslashes($subsection['name']).'" src="'.$tmp;
          if($subsection['imageid'] > 0)
          {
            $DB->result_type = MYSQL_ASSOC;
            if($sectionimage = $DB->query_first("SELECT imageid, filename, IFNULL(folder,'') folder".
                                                ' FROM '.$this->base->images_tbl.
                                                ' WHERE imageid = %d',
                                                $subsection['imageid']))
            {
              $tmp2 = $sectionimage['folder'].$this->base->TB_PREFIX.$sectionimage['filename'];
              $subsection['imagefile'] = $tmp.$tmp2;
            }
          }
          else
          {
            $subsection['imagefile'] = $tmp.$this->base->defaultimg;
          }

          // Apply BBCode parsing
          if($this->base->allow_bbcode)
          {
            $bbcode->SetDetectURLs(false);
            $bbcode->url_targetable = true;
            $bbcode->SetURLTarget('_blank');
            $subsection['description'] = $bbcode->Parse($subsection['description']);
          }
          if($curr_section_table_column == $this->base->settings['section_images_per_row'])
          {
            $curr_section_table_column = 0;
            $subsection['new_row'] = 1;
          }
        }

      } // end sections loop
      $this->base->tmpl->assign('subsections', $sections);

      # Pagination for subsections:
      $this->base->tmpl->assign('subsections_pagination', false);
      if( $sectionrows > $items_per_page )
      {
        $p = new pagination;
        if($this->base->seo_enabled)
          $p->parameterName('sections');
        else
          $p->parameterName($this->base->pre.'_sectionid='.$this->sectionid.'&amp;sections');
        $p->items($sectionrows);
        $p->limit($items_per_page);
        $p->currentPage($page);
        $p->adjacents(4);
        $p->html_id = $this->base->pre.'_sections_nagivation';
        $p->target($this->base->current_page);
        $pagination = $p->getOutput();
        $this->base->tmpl->assign('subsections_pagination', $pagination);
      }

      // OUTPUT SUBSECTIONS VIA TEMPLATE
      $tmpl_done = false;
      if(empty($this->base->section_arr['subsection_template_id']))
        $tmpl = 'subsections1.tpl'; //default
      else
        $tmpl = SD_Smarty::GetTemplateNameByID($this->base->pluginid,$this->base->section_arr['subsection_template_id']);
      if($tmpl !== false)
      {
        $tmpl_done = SD_Smarty::display($this->pluginid, $tmpl);
      }
      if(!$tmpl_done && $this->base->IsSiteAdmin)
      {
        echo '<pre>'.$tmpl.': '.sd_wordwrap(sd_unhtmlspecialchars(SD_Smarty::getLastError()),60,'<br />').'</pre>';
      }

    } // end if sections

  } //DisplaySubsections

  // #############################################################################

  private function DisplayMediaEditForm() //SD362
  {
    if(!$this->base->allow_media_edit || empty($this->base->imageid) ||
       empty($this->base->sectionid)) return false;

    $descr = GetVar($this->base->pre.'_description', $this->base->image_arr['description'], 'string', true, false);
    $link = $this->base->RewriteImageLink($this->base->sectionid, $this->base->imageid,
                         $this->base->image_arr['title']);
    $this->base->tmpl->assign('editlink', $link);

    $key = $this->base->GetUserActionKey($this->imageid);
    $this->base->tmpl->assign('deletekey', $key);

    DisplayBBEditor($this->base->allow_bbcode, $this->base->pre.'_description', $descr);

    DisplayCaptcha(true,'amihuman');

  } //DisplayMediaEditForm

  // #############################################################################

  private function DisplaySectionEditForm() //SD362
  {
    if(!$this->base->allow_section_edit || empty($this->sectionid)) return false;

    $descr = isset($this->base->section_arr['description_org'])?$this->base->section_arr['description_org']:$this->base->section_arr['description'];
    $descr = GetVar($this->base->pre.'_description', $descr, 'string', true, false);
    $link = $this->base->RewriteSectionLink($this->sectionid);
    $this->base->tmpl->assign('editlink', $link);

    $key = $this->base->GetUserActionKey($this->sectionid);
    $this->base->tmpl->assign('deletekey', $key);

    DisplayBBEditor($this->base->allow_bbcode, $this->base->pre.'_description', $descr);

    DisplayCaptcha(true,'amihuman');

  } //DisplaySectionEditForm

  //##########################################################################

  public function DisplayImages()
  {
    global $DB, $categoryid, $sdurl, $sdlanguage, $userinfo, $bbcode,
           $sd_tag_value, $sd_tagspage_link, $mainsettings_search_results_page,
           $mainsettings_tag_results_page;

    $sdm = new SD_Media();
    $sdm->base_url = SITE_URL . 'plugins/'.$this->base->PLUGINDIR.'/images/';
    $this->js_code = '';

    // ##########################
    // Update image view counter
    // ##########################
    if(isset($this->imageid) && ($this->imageid > 0))
    {
      //SD360: take into account base instance' data
      // lets try and get the image
      if($this->base->InitStatus && !empty($this->base->image_arr['imageid']))
      {
        $image = & $this->base->image_arr;
        if(!SD_IS_BOT) //SD343
        $DB->query('UPDATE '.$this->base->images_tbl.
                   ' SET viewcount = IFNULL(viewcount,0) + 1'.
                   ' WHERE imageid = %d', $this->base->image_arr['imageid']);
      }
      else
      {
        $DB->result_type = MYSQL_ASSOC;
        if($image = $DB->query_first('SELECT * FROM '.$this->base->images_tbl.
                                     ' WHERE imageid = %d', $this->imageid))
        {
          $this->sectionid = $image['sectionid'];
          if(!SD_IS_BOT) //SD343
          $DB->query('UPDATE '.$this->base->images_tbl.
                     ' SET viewcount = IFNULL(viewcount,0) + 1'.
                     ' WHERE imageid = %d', $this->imageid);
        }
        else
        {
          unset($_GET[$this->base->pre.'_imageid']);
          $this->imageid = 0;
        }
      }
    }
    $single_image = ($this->imageid > 0);
    $folder     = '';
    $folder_url = $this->base->IMAGEURL;

    // Fetch owner details for the current section's owner (if available):
    $this->base->section_arr['owner_details'] = false;
    $this->base->section_arr['section_added_label'] = '';
    if(!empty($this->base->section_arr['owner_id']))
    {
      $this->base->section_arr['owner_details'] = SDUserCache::CacheUser($this->base->section_arr['owner_id'],'',false,false);
      $tmp = $this->base->language['section_added_label'];
      $tmp = str_replace('[owner]',$this->base->section_arr['owner_details']['profile_link'],$tmp);
      $tmp = str_replace('[datecreated]',DisplayDate($this->base->section_arr['datecreated'],'',true),$tmp);
      $this->base->section_arr['section_added_label'] = $tmp;
    }

    $this->base->section_arr['link'] = '';
    if(!empty($this->base->section_arr['sectionid']))
    {
      $this->base->section_arr['link'] = $this->base->RewriteSectionLink($this->base->section_arr['sectionid']);
    }

    // ########################################################################
    // INIT SMARTY TEMPLATE
    // ########################################################################
    $this->base->InitTemplate();
    $this->base->tmpl->assign('sectionid', $this->sectionid);
    $this->base->tmpl->assign('tag', (empty($this->tag)?false:(string)$this->tag));
    $this->base->tmpl->assign('user_is_admin', $this->base->IsSiteAdmin || $this->base->IsAdmin);
    $this->base->tmpl->assign('submit_allowed', $this->submit);
    $this->base->tmpl->assign('image_nav_links', '');
    $this->base->tmpl->assign('pagination_html', false);
    $this->base->tmpl->assign('current_url', $this->base->current_page);

    // ########################################################################
    // DISPLAY GALLERY HEADER
    // ########################################################################
    //SD362: integrate tag/search results pages, finally ;)
    // Flag could already be set from within header.php, therefore " |= "
    $this->base->isTagPage |= !empty($this->tag) || ($this->base->doSearch && ($categoryid == $mainsettings_search_results_page));

    // Do not show header on search/tags results page
    if( (empty($mainsettings_search_results_page) || ($categoryid != $mainsettings_search_results_page)) &&
        (empty($mainsettings_tag_results_page) || ($categoryid != $mainsettings_tag_results_page)) )
    {
      $this->DisplayHeader(false);
    }

    if(!$this->base->isTagPage)
    {
      //SD362: display errors first (e.g. when no access to media)
      if(!empty($this->base->error_arr))
      {
        DisplayMessage($this->base->error_arr,true);
        return false;
      }

      if( !isset($this->sections[$this->sectionid]) ||
          (#!$this->base->IsSiteAdmin && !$this->base->IsAdmin &&
           empty($this->sections[$this->sectionid]['activated'])) )
      {
        if(!SD_Smarty::display($this->pluginid, 'section_offline.tpl'))
        {
          echo '<p><strong>'. $this->base->language['section_offline_message'].'</strong></p><br />';
        }
        return false;
      }
    }

    // ########################################################################
    // Display subsections of current section?
    // ########################################################################
    $this->base->tmpl->assign('subsections', false);
    if(empty($this->imageid) && !$this->base->isTagPage)
    {
      $this->DisplaySubsections();
    }

    //SD362: init section permissions
    $this->base->CheckPermissions();
    $this->base->tmpl->assign('allow_section_delete', $this->base->allow_section_delete);
    $this->base->tmpl->assign('allow_section_edit',   $this->base->allow_section_edit);

    // Get sort type of current section
    $sorttype = isset($this->sections[$this->sectionid]['sorting']) ? $this->sections[$this->sectionid]['sorting'] : '';
    $this->sql_order = $this->base->GetImagesSort($sorttype);
    $search = '';
    $imagelist = array();

    // ########################################################################
    // Display individual image?
    // ########################################################################
    if($single_image)
    {
      // Fetch list of all images in this list
      if($getimagelist = $DB->query('SELECT f.*'.
                         ' FROM '.$this->base->images_tbl.' f'.
                         ' INNER JOIN '.$this->base->sections_tbl.' s ON s.sectionid = f.sectionid'.
                         ' WHERE f.sectionid = %d AND s.activated = 1 '.
                         $this->base->GroupCheck .#AND f.activated = 1
                         ($this->base->allow_media_edit ? '' :
                          ' AND f.activated = 1 '.
                          ' AND ((IFNULL(f.private,0) = 0) OR (f.owner_id = '.$userinfo['userid'].'))').
                         ' ORDER BY ' . $this->sql_order,
                         $this->sectionid))
      {
        while($img = $DB->fetch_array($getimagelist,null,MYSQL_ASSOC))
        {
          if($img['imageid'] == $image['imageid'])
          {
            $currentID = count($imagelist);
          }
          //do not change order!
          $img['folder'] = isset($img['folder'])?(string)$img['folder']:''; //catch NULL
          $imagelist[] = $img;
        }
      }

      if(isset($currentID) && !empty($imagelist))
      {
        $this->base->image_arr = $imagelist[$currentID];
        $this->base->image_arr['owner_details'] = false;
        $this->base->image_arr['image_added_label'] = false;
        $this->base->tmpl->assign('display_author',
          !empty($this->base->image_arr['showauthor']) &&
          !empty($this->base->section_arr['display_author']));

        $this->base->image_arr['owner_details'] = false;
        if(!empty($this->base->image_arr['owner_id']))
        {
          $this->base->image_arr['owner_details'] = SDUserCache::CacheUser($this->base->image_arr['owner_id'],'',false,false);
          if($this->base->image_arr['owner_details'] !== false)
          {
            $tmp = $this->base->language['image_added_label'];
            $tmp = str_replace('[owner]',$this->base->image_arr['owner_details']['profile_link'],$tmp);
            $tmp = str_replace('[datecreated]',DisplayDate($this->base->image_arr['datecreated'],'',true),$tmp);
            $this->base->image_arr['image_added_label'] = $tmp;
          }
        }

        $this->base->image_arr['social_title'] = urlencode($this->base->image_arr['title']);
        $this->base->image_arr['social_url'] =
          urlencode($this->base->RewriteImageLink($this->base->image_arr['sectionid'],
                                                  $this->base->image_arr['imageid'],
                                                  $this->base->image_arr['title']));

        $tmp = str_replace('[item]', $this->base->image_arr['title'], $sdlanguage['social_link_delicious']);
        $this->base->image_arr['social_delicious_title'] = htmlspecialchars($tmp,ENT_COMPAT);

        $tmp = str_replace('[item]', $this->base->image_arr['title'], $sdlanguage['social_link_digg']);
        $this->base->image_arr['social_digg_title'] = htmlspecialchars($tmp,ENT_COMPAT);

        $tmp = str_replace('[item]', $this->base->image_arr['title'], $sdlanguage['social_link_facebook']);
        $this->base->image_arr['social_facebook_title'] = htmlspecialchars($tmp,ENT_COMPAT);

        $tmp = str_replace('[item]', $this->base->image_arr['title'], $sdlanguage['social_link_twitter']);
        $this->base->image_arr['social_twitter_title'] = htmlspecialchars($tmp,ENT_COMPAT);

        $this->base->image_arr['filesize_readable'] = false; //SD362

        //SD362: process tags
        $tags = $this->base->GetImageTags($this->base->image_arr['imageid']);
        $this->base->image_arr['tags_text'] = implode(',', $tags);
        $sep = (strpos($this->base->plugin_page,'?')===false ? '?' : '&amp;');
        foreach ($tags as $tagkey => $value)
        {
          $enc = urlencode(str_replace('&amp;','&', $value));
          $tags[$tagkey] = '<a href="'.$this->base->plugin_page.$sep.$this->base->pre.'_tag='.$enc.'">'.$value.'</a> ';
        }
        $this->base->image_arr['tags'] = $tags;

        if($currentID > 0)
        {
          $tmp = $this->base->RewriteImageLink($imagelist[$currentID - 1]['sectionid'],
                                               $imagelist[$currentID - 1]['imageid'],
                                               $imagelist[$currentID - 1]['title']);
          $this->base->image_arr['prev_img_url'] = $tmp;
          $this->base->image_arr['prev_img_arr'] = $imagelist[$currentID - 1];
        }
        if($currentID < count($imagelist)-1)
        {
          $tmp = $this->base->RewriteImageLink($imagelist[$currentID + 1]['sectionid'],
                                               $imagelist[$currentID + 1]['imageid'],
                                               $imagelist[$currentID + 1]['title']);
          $this->base->image_arr['next_img_url'] = $tmp;
          $this->base->image_arr['next_img_arr'] = $imagelist[$currentID + 1];
        }

        // Display rating html
        $this->base->image_arr['rating_form'] = false;
        if( function_exists('GetRatingForm') &&
            !empty($this->base->section_arr['display_ratings']) &&
            !empty($this->base->image_arr['allow_ratings']))
        {
          $tmp = GetRatingForm($this->base->pre.'-'.$this->imageid, $this->pluginid);
          $this->base->image_arr['rating_form'] = $tmp;
        }

        $folder  = (isset($this->base->image_arr['folder']) ? $this->base->image_arr['folder'] : '');
        $folder .= (strlen($folder) && (sd_substr($folder,strlen($folder)-1,1)!='/') ? '/' : '');
        $folder_url .= $folder;
        $folder = $this->base->IMAGEDIR . $folder;
        $imagetitle  = htmlspecialchars($this->base->image_arr['title'], ENT_COMPAT);
        $this->base->image_arr['imagetitle'] = $imagetitle;

        $image_path     = ROOT_PATH.$folder.$this->base->image_arr['filename'];
        $mid_image_path = ROOT_PATH.$folder.$this->base->MD_PREFIX.$this->base->image_arr['filename'];

        $mid_w = $mid_h = 0;
        $new_width  = $max_width  = intval($this->base->settings['integrated_view_max_width']);
        $new_height = $max_height = intval($this->base->settings['integrated_view_max_height']);

        //SD343: Media URL support
        $mt = empty($this->base->image_arr['media_type'])?0:(int)$this->base->image_arr['media_type'];
        if($mt)
        {
          // Were embedding dimensions specified?
          if(!empty($this->base->image_arr['px_width']) or !empty($this->base->image_arr['px_height']))
          {
            $new_width  = Is_Valid_Number(intval($this->base->image_arr['px_width']),0,0,2000);
            $new_height = Is_Valid_Number(intval($this->base->image_arr['px_height']),0,0,2000);
          }
          $tmp = SD_Media_Base::getMediaPreview($mt, sd_unhtmlspecialchars($this->base->image_arr['media_url']),
                              $this->base->language['visit_media_site'],
                              $new_width, $new_height, true, true);
          $this->base->image_arr['media_html'] = $tmp;
        }
        else
        {
          // Determine which image to use
          if(file_exists($mid_image_path))
          {
            $img_file = $this->base->MD_PREFIX . $this->base->image_arr['filename'];
            $this->base->ScaleImage($mid_image_path, $mid_w, $mid_h, $new_width, $new_height);
          }
          else
          {
            $img_file = $this->base->image_arr['filename'];
            $this->base->ScaleImage($image_path, $mid_w, $mid_h, $new_width, $new_height);
            if(file_exists($image_path) && (false !== ($fsize = @filesize($image_path))))
            {
              $this->base->image_arr['filesize_readable'] = DisplayReadableFilesize($fsize);
            }
          }

          $popup_needed = (($max_width <= $this->base->image_arr['px_width']) ||
                           ($max_height <= $this->base->image_arr['px_height']));
          $this->base->image_arr['popup_needed'] = $popup_needed;
          $this->base->image_arr['folder_url'] = $folder_url;
          $this->base->image_arr['image_file_full'] = $this->base->image_arr['filename'];
          $this->base->image_arr['image_file_thumb'] = $img_file;
          $this->base->image_arr['image_width'] = (int)$new_width;
          $this->base->image_arr['image_height'] = (int)$new_height;
          $this->base->image_arr['max_width'] = (int)$max_width;
          $this->base->image_arr['max_height'] = (int)$max_height;
        }

        // ****************************************************************
        // Moderation links, comments, view count...
        // ****************************************************************
        //SD362: check media permissions
        $this->base->CheckPermissions();
        $this->base->tmpl->assign('allow_media_delete', $this->base->allow_media_delete);
        $this->base->tmpl->assign('allow_media_edit',   $this->base->allow_media_edit);
        $this->base->tmpl->assign('is_section_thumb',   false);
        if(!empty($this->base->section_arr['imageid']) &&
           ($this->base->section_arr['imageid'] == $this->base->imageid))
        {
          $this->base->tmpl->assign('is_section_thumb', true);
        }

        $tmp = $this->GetModLinks(!empty($this->base->image_arr['activated'])); //SD360
        $this->base->image_arr['mod_links'] = $tmp;

        $this->base->image_arr['display_comments'] = false;
        $this->base->image_arr['comments_count'] = 0;
        if(!empty($this->base->settings['enable_comments']) &&
           !empty($this->base->section_arr['display_comments']))
        {
          $this->base->image_arr['display_comments'] = true;
          $this->base->image_arr['comments_count'] = GetCommentsCount($this->pluginid, $this->imageid);
        }

        //SD362: add form for editing the image's title,description
        //Note: core form is part of "single_image.tpl" already!
        //This just adds editor+captcha html and JS code for toggle-link
        //NOTE: must be BEFORE BBCode is applied!
        $editor_html = '';
        if($this->base->allow_media_edit)
        {
			echo 'hre';
          ob_start();
          $this->DisplayMediaEditForm();
          $editor_html = ob_get_clean();
          if(strlen($editor_html))
          {
			  
            $this->js_code .= '
  $("div.media-editform a:first").click(function(e){
    e.preventDefault();
    $("div.media-editform form").toggle("fast");
    return false;
  });
  if(typeof($.fn.tagEditor) !== "undefined") {
    $(".tags").tagEditor({
      completeOnSeparator: true,
      completeOnBlur: true,
      confirmRemoval: true,
      separator: ",",
      confirmRemovalText: "'.addslashes($sdlanguage['common_remove_tag']).'"
    });
  }
  $("ul.tagEditor li").attr("unselectable","on").css("MozUserSelect","none").bind("dragstart", function(event) { event.preventDefault(); });
  ';
          }
        }
        $this->base->tmpl->assign('editor_html', $editor_html);

        // Apply BBCode parsing to description (AFTER edit form!)
        if(strlen(trim($this->base->image_arr['description'])) && $this->base->allow_bbcode)
        {
          $bbcode->SetDetectURLs(false);
          $bbcode->url_targetable = true;
          $bbcode->SetURLTarget('_blank');
          $this->base->image_arr['description'] = $bbcode->Parse($this->base->image_arr['description']);
        }

        // Finally assign image array to template:
        $this->base->tmpl->assign('image', $this->base->image_arr);

        $this->base->tmpl->assign('display_author',
          !empty($this->base->image_arr['showauthor']) &&
          !empty($this->base->section_arr['display_author']));

        // OUTPUT IMAGE BY TEMPLATE
        if(!$tmpl_done = SD_Smarty::display($this->pluginid, 'single_image.tpl')) //SD360
        {
          if(!empty($userinfo['adminaccess']))
          {
            echo '<pre>single_image: '.sd_wordwrap(sd_unhtmlspecialchars(SD_Smarty::getLastError()),60,'<br />').'</pre>';
          }
        }

        if(!empty($this->base->settings['enable_comments']) &&
           !empty($this->base->image_arr['allowcomments']))
        {
          Comments($this->pluginid, $this->base->image_arr['imageid'], 'index.php?categoryid='.$categoryid.
                   '&'.$this->base->pre.'_sectionid='.$this->sectionid.
                   '&'.$this->base->pre.'_imageid='.$this->base->image_arr['imageid'],
                   !empty($this->base->settings['show_comments_expanded']));
        }
      }
    }
    // ************************************************************************
    // ***** End of single image view
    // ************************************************************************
    ELSE
    {
      // ######################################################################
      // Display images list for section (paginated)?
      // ######################################################################
      $display_mode = 0; //SD362: display tag results always in integrated mode!
      $this->tag_active = '';
      $TagParams = '';
      $search = ' ((IFNULL(f.private,0) = 0) OR (f.owner_id = '.$userinfo['userid'].')) ';

      if(!empty($this->base->doSearch) && !empty($this->base->SearchTerm))
      {
        //TODO: output results here if no Search Engine plugin on same page??
        $this->base->isTagPage = true;

        // Build WHERE clause to search media title/description
        $seachparts = array();
        #foreach($this->base->SearchTerm as $i => $val)
        $val = $this->base->SearchTerm;
        {
          $seachparts[] = "f.title LIKE '%" . $DB->escape_string(sd_substr($val,0,100)) . "%'";
          $seachparts[] = "f.description LIKE '%" . $DB->escape_string(sd_substr($val,0,100)) . "%'";
        }
        if(empty($seachparts))
        {
          #echo $this->phrases['no_results'];
        }
        else
        $search .= ' AND ('.implode(' OR ', $seachparts).')';
      }
      else
      if(SD_TAG_DETECTED && is_array($sd_tag_value) && !empty($sd_tag_value))
      {
        if(!empty($sd_tag_value['key']) && ($sd_tag_value['key']=='tags') && !empty($sd_tag_value['value']))
        {
          $this->tag_active = $sd_tag_value['value'];
          $search .= ' AND EXISTS(SELECT 1 FROM '.PRGM_TABLE_PREFIX.'tags tags WHERE tags.pluginid = '.
                        $this->pluginid.' AND tags.objectid = f.imageid'.
                        " AND tags.tagtype = 0 AND tags.tag LIKE '".
                        ($DB->escape_string($this->tag_active))."')\r\n";
          $this->base->isTagPage = true;
          $TagParams = $this->tag_active;
          $url = $sd_tagspage_link.$TagParams;
        }
        else
        if(!empty($sd_tag_value['year']) && !empty($sd_tag_value['month']) &&
           Is_Valid_Number($sd_tag_value['year'],0,2000,2050) &&
           Is_Valid_Number($sd_tag_value['month'],0,1,12) )
        {
          $this->tag_active = '';
          $search .= " AND DATE_FORMAT(FROM_UNIXTIME(f.datecreated),'%Y%m') = ".
                        sprintf('%02d',$sd_tag_value['year']).sprintf('%02d',$sd_tag_value['month']);
          $this->base->isTagPage = true;
          $TagParams = sprintf('%02d',$sd_tag_value['year']).'/'.sprintf('%02d',$sd_tag_value['month']);
          $url = $sd_tagspage_link.$TagParams;
        }
        else
        {
          // Invalid search/tag specified! Create empty results!
          $search = ' 1 = 0 ';
        }
      }
      else
      if(!empty($this->base->slug_arr['id']) || !empty($this->tag))
      {
        if(!empty($this->tag))
        {
          $this->base->slug_arr = array();
          $this->base->slug_arr['key'] = 'tags';
          $this->base->slug_arr['value'] = $this->tag;
        }
        if(!empty($this->base->slug_arr['key']) && ($this->base->slug_arr['key']=='tags') && !empty($this->base->slug_arr['value']))
        {
          $this->tag_active = $this->base->slug_arr['value'];
          $search .= ' AND EXISTS(SELECT 1 FROM '.PRGM_TABLE_PREFIX.'tags tags WHERE tags.pluginid = '.
                        $this->pluginid.' AND tags.objectid = f.imageid'.
                        " AND tags.tagtype = 0 AND tags.tag LIKE '".
                        $DB->escape_string($this->tag_active)."')\r\n";
          $this->base->isTagPage = true;
          $TagParams = $this->tag_active;
        }
        else
        if(!empty($this->base->slug_arr['year']) && !empty($this->base->slug_arr['month']) &&
           Is_Valid_Number($this->base->slug_arr['year'],0,2000,2050) &&
           Is_Valid_Number($this->base->slug_arr['month'],0,1,12) )
        {
          $this->tag_active = '';
          $search .= " AND DATE_FORMAT(FROM_UNIXTIME(f.datecreated),'%Y%m') = ".
                        sprintf('%02d',$this->base->slug_arr['year']).sprintf('%02d',$this->base->slug_arr['month']);
          $this->base->isTagPage = true;
          $TagParams = sprintf('%02d',$this->base->slug_arr['year']).'/'.sprintf('%02d',$this->base->slug_arr['month']);
          $url = preg_replace('#'.SD_QUOTED_URL_EXT.'$#', '/',
                              RewriteLink('index.php?categoryid='.$categoryid)).$TagParams;
        }
        else
        {
          // Invalid search/tag specified! Create empty results!
          $search = ' 1 = 0 ';
        }
      }

      if($this->base->isTagPage)
      {
        $this->sectionid = $this->base->sectionid = 0;
      }
      else
      {
        $display_mode = (int)$this->sections[$this->sectionid]['display_mode'];
        $search .= ' AND f.sectionid = '.(int)$this->sectionid;
      }
      $search .= ( ((!$this->base->IsSiteAdmin && !$this->base->IsAdmin) ||
                    empty($this->base->settings['display_approve_link'])) ?
                    ' AND f.activated = 1 ' : '');


      $DB->result_type = MYSQL_ASSOC;
      $getimagecount = $DB->query_first('SELECT COUNT(imageid) img_count'.
                                        ' FROM '.$this->base->images_tbl.' f'.
                                        ' WHERE ' . $search);
      $getimagecount = empty($getimagecount['img_count']) ? false : (int)$getimagecount['img_count'];

      $search_message = '';
      if(!$getimagecount)
      {
        if($this->base->isTagPage)
        {
          $search_message .= '
          <div id="p'.$this->pluginid.'_tag_head">'.
          str_replace('[tag]', (string)$this->tag_active,
            $this->base->language[$this->base->doSearch?'msg_no_results':'msg_no_tag_results']).
          '</div>';
        }
      }
      else
      {
        if($this->base->isTagPage)
        {
          if(!empty($sd_tag_value['id']))
          {
            $search_message .= '
            <div id="p'.$this->pluginid.'_tag_head">';
            if(!empty($this->tag_active) && ($sd_tag_value['id']='tags'))
              $search_message .= str_replace('[tag]', (string)$this->tag_active,
                                             $this->base->language['media_tags_head']);
            else
            if(!empty($sd_tag_value['month']) && !empty($sd_tag_value['year']))
            {
              global $sd_months_arr;
              $search_message .= str_replace(array('[month]','[year]'),
                                   array($sd_months_arr[$sd_tag_value['month']],
                                         sprintf('%04d',$sd_tag_value['year'])),
                                   $this->base->language['media_year_month_head']);
            }
            $search_message .= '
            </div>';
          }
          else
          if(!empty($this->slug_arr['id']))
          {
            $search_message .= '
            <div id="p'.$this->pluginid.'_tag_head">';
            if(!empty($this->tag_active) && ($this->slug_arr['id']='tags'))
              $search_message .= str_replace('[tag]', (string)$this->tag_active,
                                             $this->base->language['article_tags_head']);
            else
            if(!empty($this->slug_arr['month']) && !empty($this->slug_arr['year']))
            {
              global $sd_months_arr;
              $search_message .= str_replace(array('[month]','[year]'),
                                   array($sd_months_arr[$this->slug_arr['month']],
                                         sprintf('%04d',$this->slug_arr['year'])),
                                   $this->base->language['media_year_month_head']);
            }
            $search_message .= '
            </div>';
          }
          else
          if(!empty($this->base->doSearch))
          {
            $search_message .= '
            <br /><div id="p'.$this->pluginid.'_tag_head">'.
            str_replace('[tag]', (string)$this->tag_active,
              $this->base->language['msg_search_results']).
            '</div>';
          }
        }

        // items_per_page created above
        $this->base->section_arr['images_per_page'] = isset($this->base->section_arr['images_per_page'])?$this->base->section_arr['images_per_page']:10;
        $this->base->section_arr['images_per_row']  = isset($this->base->section_arr['images_per_row'])?$this->base->section_arr['images_per_row']:3;

        $page = Is_Valid_Number(GetVar('page', 1, 'whole_number'), 1, 1);
        $items_per_page = Is_Valid_Number($this->base->section_arr['images_per_page'], 0, 0, 999);
        $items_per_page = empty($items_per_page) ? Is_Valid_Number($this->base->settings['default_images_per_page'], 10, 1, 999) : $items_per_page;
        $page_start = ($page-1)*$items_per_page;
        $limit = ' LIMIT ' . $page_start . ',' . $items_per_page;

        // get images
        $rows = 0;
        $DB->result_type = MYSQL_ASSOC;
        if($getimages = $DB->query('SELECT f.* FROM '.$this->base->images_tbl.' f'.
                                   ' WHERE ' . $search .
                                   ' ORDER BY ' . $this->sql_order . ' ' . $limit,
                                   $this->sectionid))
        {
          $rows = $DB->get_num_rows($getimages);
        }

        // Only for Integrated display mode OR if tag search results
        if(empty($display_mode) || $this->base->isTagPage)
        {
          $tr_opened = false;
          $td_count = 0;
          $items_per_row = Is_Valid_Number($this->base->section_arr['images_per_row'], 0, 1, 99);
          $items_per_row = empty($items_per_row) ? Is_Valid_Number($this->base->settings['default_images_per_row'], 1, 1, 99) : $items_per_row;
          $td_width = round(100 / $items_per_row);

          $this->base->tmpl->assign('images_per_row', $items_per_row);
          $this->base->tmpl->assign('image_cell_width', $td_width);
        }

        // Create pagination markup if not 3 = "None"
        $this->base->tmpl->assign('pagination_needed', ($getimagecount > $items_per_page));
        $this->base->tmpl->assign('pagination_links',  (int)$this->base->settings['pagination_links']);

        $pagination = '';
        if($getimagecount > $items_per_page)
        {
          if($this->base->settings['pagination_links'] < 3)
          {
            //SD362: pagination params differ for tag and search pages!
            $tag_param = '';
            if($this->base->isTagPage)
            {
              if(!empty($this->tag))
              {
                $tag_param = $this->base->pre.'_tag='.urlencode($this->tag).'&amp;';
              }
              else
              if(!empty($this->base->SearchTerm))
              {
                $tag_param = 'action=search&amp;q='.urlencode($this->base->SearchTerm).'&amp;';
              }
            }
            $p = new pagination;
            if(!$this->base->isTagPage && !$this->base->seo_enabled)
              $p->parameterName($this->base->pre.'_sectionid='.$this->sectionid.'&amp;page');
            else
              $p->parameterName($tag_param.'page');
            $p->items($getimagecount);
            $p->limit($items_per_page);
            $p->currentPage($page);
            $p->adjacents(4);
            if($this->base->isTagPage)
            {
              global $uri;
              $tmpuri = $uri;
              if(false !== ($qpos = strpos($tmpuri,'?')))
              {
                $tmpuri = substr($tmpuri,0,$qpos);
              }
              $p->target('//'.$_SERVER['HTTP_HOST'].$tmpuri);
            }
            else
              $p->target($this->base->current_page);
            $pagination = $p->getOutput();
            $this->base->tmpl->assign('pagination_html', $pagination);
          }
          else
          if(!$this->base->isTagPage)
          {
            $tmp = $this->DisplayPrevNextLinks($page, $items_per_page, $getimagecount,
                                               $this->base->pre.'_navlinks_top');
            $this->base->tmpl->assign('image_nav_links', $tmp);
          }
        }

        // Temp container for all images for template:
        $img_list = array();

        // Main loop to display all images for current page:
        for($i = 0; $i < $rows AND $i < $items_per_page; $i++)
        {
          $this->base->image_arr = $DB->fetch_array($getimages,null,MYSQL_ASSOC);

          // Extra values for templating:
          if(empty($display_mode))
          {
            $this->base->image_arr['SD_open_tr'] = false;
            $this->base->image_arr['SD_close_tr'] = false;
          }

          $this->base->imageid = $this->imageid = (int)$this->base->image_arr['imageid'];
          $imagetitle_org = $this->base->image_arr['title'];
          $imagetitle = addslashes($imagetitle_org);

          $folder = (isset($this->base->image_arr['folder']) ? '' : $this->base->image_arr['folder']);
          $folder .= (strlen($folder) && (sd_substr($folder,strlen($folder)-1,1)!='/') ? '/' : '');
          $folder = $this->base->IMAGEDIR . $folder;
          $folder_url = $this->base->IMAGEURL . $this->base->image_arr['folder'];
          $this->base->tmpl->assign('folder', $folder);
          $this->base->tmpl->assign('folder_url', $folder_url);

          // Apply BBCode parsing
          if($this->base->allow_bbcode && !empty($this->base->image_arr['description']))
          {
            $bbcode->SetDetectURLs(false);
            $bbcode->url_targetable = true;
            $bbcode->SetURLTarget('_blank');
            $this->base->image_arr['description'] = $bbcode->Parse($this->base->image_arr['description']);
          }

          // Check target folder, does section have one?
          $target_folder = $this->base->IMAGEPATH;
          if(!empty($this->base->image_arr['folder']))
          {
            $target_folder .= $this->base->image_arr['folder'];
          }
          $this->base->image_arr['folder'] = $target_folder;

          //SD343: Media URL support
          $mt = empty($this->base->image_arr['media_type'])?0:(int)$this->base->image_arr['media_type'];
          if($mt)
          {
            // Image must point to own media popup page:
            $img = $sdurl . $this->base->PLUGINDIR.
                   'mediapopup.php?categoryid=' . $categoryid . '&amp;'.$this->base->pre.'_sectionid=' .
                   $this->base->image_arr['sectionid'] . '&amp;'.$this->base->pre.'_imageid=' . $this->imageid;

            if(!file_exists($target_folder.$this->base->TB_PREFIX.$this->base->image_arr['filename']))
            {
              $tb_img_src = SD_Media_Base::getMediaImgLink($mt, sd_unhtmlspecialchars($this->base->image_arr['media_url']));
            }
            else
            {
              // If a mid-size or thumbnail images exist, use'em
              $mid_image_path = ROOT_PATH.$folder.$this->base->MD_PREFIX.$this->base->image_arr['filename'];
              $tb_image_path = ROOT_PATH.$folder.$this->base->TB_PREFIX.$this->base->image_arr['filename'];
              if(file_exists($mid_image_path))
              {
                $tb_img_src = $mid_image_path;
              }
              else
              if(file_exists($tb_image_path))
              {
                $tb_img_src = $tb_image_path;
              }
              else // at last try to fetch media thumb
              {
                $tb_img_src = SD_Media_Base::getMediaImgLink($mt, sd_unhtmlspecialchars($this->base->image_arr['media_url']));
              }
            }
            $folder = '';
            $folder_url = '';
          }
          else
          {
            if($this->base->settings['save_fullsize_images'] ||
               !file_exists(ROOT_PATH.$folder.$this->base->MD_PREFIX.$this->base->image_arr['filename']))
            {
              $img = $this->base->image_arr['filename'];
            }
            else
            {
              $img = $this->base->MD_PREFIX.$this->base->image_arr['filename'];
            }
            $tb_img_src = $folder_url . $this->base->TB_PREFIX . $this->base->image_arr['filename'];
          }

          //SD362: detemine user permissions for delete/edit on section/media (admin or owner)?
          $this->base->CheckPermissions();

          // Integrated image display mode (0)
          if(empty($display_mode))
          {
            // *** SIMPLE DISPLAY MODE ***
            if(!$tr_opened)
            {
              $tr_opened = true;
              $this->base->image_arr['SD_open_tr'] = true;
            }
            if($mt && ((false!==strpos($tb_img_src,'<img')) ||
                       (false!==strpos($tb_img_src,'<iframe'))) )
            {
              $this->base->image_arr['a_href'] = $img;
              $this->base->image_arr['a_text'] = $tb_img_src;
            }
            else
            {
              $tmp = $this->base->RewriteImageLink($this->base->image_arr['sectionid'], $this->imageid,
                                                   $this->base->image_arr['title']);
              $this->base->image_arr['a_href'] = $tmp;
              $tmp = '<img title="' . $imagetitle . '" alt="" src="' . $tb_img_src . '" />';
              $this->base->image_arr['a_text'] = $tmp;
            }

            $this->base->image_arr['mod_links'] = $this->GetModLinks(!empty($this->base->image_arr['activated'])); //SD360

            $td_count++;
            if($td_count >= $items_per_row)
            {
              $tr_opened = false;
              $td_count = 0;
              $this->base->image_arr['SD_close_tr'] = true;
            }

          }
          else // ############ DISPLAY MODES > 0 ############
          {
            $this->base->image_arr['display_comments'] = false;
            $this->base->image_arr['comments_count'] = 0;
            $this->base->image_arr['a_href'] = '';
            if($display_mode == 1) // Popup
            {
              if(!$mt && is_file(ROOT_PATH . $folder . $img))
              {
                list($sizew, $sizeh) = @getimagesize(ROOT_PATH . $folder . $img);
                if($sizeh > 500) $sizeh = 500;
              }
              else
              {
                $sizew = 600;
                $sizeh = 400;
              }
              $this->base->image_arr['width']  = min($sizew,600);
              $this->base->image_arr['height'] = min($sizeh,400);
              $this->base->image_arr['a_text'] = '<img alt="" title="'.$imagetitle.'" src="'.$tb_img_src.'" />';
              $this->base->image_arr['a_href'] = "<a href=\"#\" onclick=\"window.open('" . $sdurl . $this->base->PLUGINDIR.
                  'mediapopup.php?categoryid=' . $categoryid . '&'.$this->base->pre.'_sectionid=' .
                  $this->base->image_arr['sectionid'] . '&'.$this->base->pre.'_imageid='.$this->imageid.
                  "', '', 'width=" . min($sizew,600) . ',height=' . min($sizeh,400) .
                  ",directories=no,location=no,menubar=no,scrollbars=yes,status=no,toolbar=no,resizable=yes');".
                  'return false;" target="_blank">'.
                  $this->base->image_arr['a_text'].'</a>';
            }
            else
            if($display_mode == 2) // Ceebox
            {
              if($mt)
                $this->base->image_arr['a_href'] = '<a rel="iframe width:800 height:600" class="gallerybox-p'.
                $this->pluginid.'" href="'.$folder_url.$img.'" title="'.$imagetitle .
                '"><img alt="" src="' . $tb_img_src . '" /></a><br />
                ';
              else
                $this->base->image_arr['a_href'] = '<a rel="image" class="gallerybox-p'.$this->pluginid.'" href="'.
                $folder_url.$img.'" title="'.$imagetitle.'"><img alt="" src="'.$tb_img_src.'" /></a><br />
                ';
            }
            else
            if($display_mode == 3) // Fancybox
            {
              //Note: do NOT switch "rel=" to e.g. "class=" as "rel" allows for prev/next buttons!
              $this->base->image_arr['a_href'] = '<a rel="fancybox-p'.$this->pluginid.'" href="' . $folder_url . $img . '" title="' .
                addslashes($imagetitle .
                 (!empty($this->base->image_arr['description']) ? ' &raquo; ' . htmlspecialchars($this->base->image_arr['description'], ENT_COMPAT) : '')).
                '"><img alt="" src="' . $tb_img_src . '" /></a>';
            }
            else
            if($display_mode == 4) // Galleria
            {
              if($mt)
                $this->base->image_arr['a_href'] = '<a target="_blank" href="'.
                  $folder_url . $img. '" title="'.$imagetitle.'"><img alt="'.
                  addslashes($imagetitle).'" src="' . $tb_img_src . '" /></a>';
              else
                $this->base->image_arr['a_href'] = '<a href="' . $folder_url . $img. '" title="'.
                  $imagetitle.'"><img alt="'.addslashes($imagetitle).'" src="'. $tb_img_src . '" /></a>';
            }
            else
            if($display_mode == 5) // Slide Rotator
            {
              // tobias: original "slideshow.js" needed a fix (li:first/last needed "#slideShow ul " prefix)!
              // http://demo.tutorialzine.com/2010/11/rotating-slideshow-jquery-css3/
              $this->base->image_arr['a_href'] = '<img src="' .
                   ($mt ? SD_Media_Base::getMediaImgLink($mt, sd_unhtmlspecialchars($this->base->image_arr['media_url'])) : $folder_url . $img).
                   '" alt="'.addslashes($imagetitle).'" height="100%" width="100%" />';
            }
            else
            if($display_mode == 6) // mbGallery
            {
              $this->base->image_arr['a_href'] = $tb_img_src;
              $this->base->image_arr['a_href_full'] = ($mt ? SD_Media_Base::getMediaImgLink($mt, sd_unhtmlspecialchars($this->base->image_arr['media_url'])) : $folder_url . $img);
            }
            else
            if($display_mode == 7) // jqGalViewII
            {
              $this->base->image_arr['a_href'] = '<a href="' .
               ($mt ? SD_Media_Base::getMediaImgLink($mt, sd_unhtmlspecialchars($this->base->image_arr['media_url'])) : $folder_url . $img) .'">'.
               '<img src="' . $tb_img_src . '" alt="'.$imagetitle.
               (!empty($this->base->image_arr['description'])?" <br> ".$this->base->image_arr['description']:'').'" /></a>';
            }

            // If NOT Galleria/Slideshow...
            if($display_mode <= 3)
            {
              $this->base->image_arr['display_comments'] = false;
              $this->base->image_arr['comments_count'] = 0;
              if(!empty($this->base->settings['enable_comments']) &&
                 !empty($this->base->section_arr['display_comments']))
              {
                $this->base->image_arr['display_comments'] = true;
                $this->base->image_arr['comments_count'] = GetCommentsCount($this->pluginid, $this->imageid);
              }

              // Show moderation links
              $tmp = $this->GetModLinks(!empty($this->base->image_arr['activated']));
              $this->base->image_arr['mod_links'] = $tmp;
            }
          }

          // Add to template image list:
          $imagelist[] = $this->base->image_arr;

        } //for loop end ######################################################

        $this->base->tmpl->assign('images_list', $imagelist);

        if(empty($display_mode))
        {
          // SIMPLE MODE ENDING BLOCK
          $this->base->tmpl->assign('fillup_cells', 0);
          if($tr_opened)
          {
            $tr_opened = false;
            $this->base->tmpl->assign('fillup_cells', ($items_per_row - $td_count));
          }
        }
      } // if rows
      $this->base->tmpl->assign('search_message', $search_message);

      //SD362: add form for editing the image's title,description
      //Note: core form is part of "single_image.tpl" already!
      //This just adds editor+captcha html and JS code for toggle-link
      //NOTE: must be BEFORE BBCode is applied!
      $editor_html = '';
      if(!$this->base->isTagPage && $this->base->allow_section_edit)
      {
        ob_start();
        $this->DisplaySectionEditForm();
        $editor_html = ob_get_clean();
        if(strlen($editor_html))
        {
          $this->js_code .= '
          jQuery("div.media-editform a:first").click(function(e){
            e.preventDefault();
            jQuery("div.media-editform form").toggle("fast");
            return false;
          });
          ';
        }
      }
      $this->base->tmpl->assign('editor_html', $editor_html);
      $this->base->tmpl->assign('display_mode', $display_mode); //set at end

      // OUTPUT SECTION HEAD
      $tmpl_done = SD_Smarty::display($this->pluginid, 'section_head.tpl');
      if(!$tmpl_done && $this->base->IsSiteAdmin)
      {
        echo '<pre>section_head: '.sd_wordwrap(sd_unhtmlspecialchars(SD_Smarty::getLastError()),60,'<br />').'</pre>';
      }

      // OUTPUT IMAGES LIST CONTENTS
      $tmpl_done = SD_Smarty::display($this->pluginid, 'images_display_mode_'.$display_mode.'.tpl');
      if(!$tmpl_done && $this->base->IsSiteAdmin)
      {
        echo '<pre>images_display_mode_'.$display_mode.': '.sd_wordwrap(sd_unhtmlspecialchars(SD_Smarty::getLastError()),60,'<br />').'</pre>';
      }

      // OUTPUT SECTION FOOTER
      $tmpl_done = SD_Smarty::display($this->pluginid, 'section_footer.tpl');
      if(!$tmpl_done && $this->base->IsSiteAdmin)
      {
        echo '<pre>section_footer: '.sd_wordwrap(sd_unhtmlspecialchars(SD_Smarty::getLastError()),60,'<br />').'</pre>';
      }

    } // else if displaying all images

    //SD362: skip rest if tag/search page!
    if($this->base->isTagPage)
    {
      // Do not display Tag Cloud on search results page
     if( !empty($this->base->settings['display_tags']) &&
         (empty($mainsettings_search_results_page) || ($categoryid != $mainsettings_search_results_page)) &&
         (empty($mainsettings_tag_results_page) || ($categoryid != $mainsettings_tag_results_page)) )
      {
        $this->DisplayTagCloud();
      }
      return;
    }

    if(!$single_image || !empty($this->js_code))
    {
      echo '
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function() {
'.$this->js_code;
    }

    if(!$single_image)
    {
      echo '
  jQuery(document).delegate("div#'.$this->base->pre.'_sections_nagivation a","click",function(e){
    e.preventdefault;
    var sid = $("input#'.$this->base->pre.'_sectionid").val();
    var la_page = $(this).attr("href");
    var paramrex = /sections=(\d+)$/i;
    paramrex.exec(la_page);
    if(RegExp.$1 >= 1) {
      jQuery("table.'.$this->base->pre.'_sections").load(
        sdurl+"plugins/'.$this->base->plugin_folder.'/media_gallery.php?action=getsections'.
        '&categoryid='.$categoryid.PrintSecureUrlToken().
        '&sectionid="+sid+"&sections="+RegExp.$1);
      return false;
    }
    return true;
  });
  jQuery(document).delegate("#'.$this->base->pre.'_imagegallery div.section","click",function(){
    var link = jQuery(this).find("a").attr("href");
    window.location = link;
  });

  var section_height = 0, h = 0;
  jQuery("table.'.$this->base->pre.'_sections div.section").each(function(){
    var h = parseInt(jQuery(this).css("height"),10);
    if(h > section_height) { section_height = h; }
  });
  if(section_height > 0) {
    jQuery("#'.$this->base->pre.'_imagegallery div.section").css("height", section_height+"px");
  }
';
      // Note: MOST javascript for the different image display modes is
      // located within the header.php, but we need some here due to section setting!
      if(!empty($display_mode))
      {
        if($display_mode == 6)
        {
          //SD351: pluginid within selectors
          $tmp = '';
          if(!empty($this->sectionid) && !empty($this->sections[$this->sectionid]))
          {
            $tmp = $this->sections[$this->sectionid]['name'];
          }
          echo "
  var mymb = jQuery('#mb".$this->pluginid."_gallery');
  if(mymb.length > 0){
  jQuery(mymb).mbGallery({
    cssURL: sdurl+'".$this->base->PLUGINDIR."css/',
    addRaster: false,
    fullScreen: true,
    maskBgnd: '#ccc',
    overlayOpacity: 0.9,
    galleryTitle: '".addslashes($tmp)."',
    minWidth: ".(intval($this->base->settings['integrated_view_max_width'])+10).",
    minHeight: ".(intval($this->base->settings['integrated_view_max_height'])+40).",
    containment: 'mb".$this->pluginid."_containment',
    skin: 'white',
    startFrom: 1,
    exifData: false,
    printOutThumbs:false
  });
  jQuery('div.galleryCloseIcon').hide();
  }
  ";
        }
      } // Display Mode with Javascript
    }

    if(!$single_image || !empty($this->js_code))
    {
      echo '
});
//]]>
</script>';
      }

    if( !empty($this->base->settings['display_tags']) &&
        (empty($mainsettings_search_results_page) || ($categoryid != $mainsettings_search_results_page)) &&
        (empty($mainsettings_tag_results_page) || ($categoryid != $mainsettings_tag_results_page)) )
    {
      $this->DisplayTagCloud();
    }

  } //DisplayImages


  // ############################################################################


  function DisplayImageDeleteForm($multiple=false)
  {
    global $sdlanguage, $userinfo;

    $err = (!$this->base->sectionid || empty($this->imageid) || !$this->base->SetImageAndSection($this->imageid));
    if(!$err)
    {
      $err = !$this->base->allow_media_delete;
    }
    if($err)
    {
      // user is trying to break the script
      RedirectFrontPage($this->base->current_page,$sdlanguage['err_invalid_operation'],2,true);
      return false;
    }

    $key = $this->base->GetUserActionKey($this->imageid);

    echo '
    <h2>' . $this->base->language['delete_image_title'].' - '.
      $this->base->language['section'].' &quot;'.$this->base->section_arr['name'].'&quot;</h2>
    <form action="'.$this->base->current_page.'" method="post">
    '.PrintSecureToken().'
    <input type="hidden" name="'.$this->base->pre.'_action" value="img_delete_confirm" />
    <input type="hidden" name="'.$this->base->pre.'_sectionid" value="'.$this->base->sectionid.'" />
    <input type="hidden" name="'.$this->base->pre.'_id" value="'.$this->imageid.'" />
    <div class="form-wrap">
      <p>'.$this->base->language['image'].' <strong>'.$this->base->image_arr['title'].'</strong></p>
      <p><input type="checkbox" name="'.$this->base->pre.'_'.$key.'" value="1" />
        <strong>'.$this->base->language['confirm_delete_image'].' </strong></p>
      <p><input type="submit" name="" value="'.strip_alltags($sdlanguage['button_confirm']).'" /></p>
    </div>
    </form>
    ';
    return true;

  } //DisplayImageDeleteForm


  // ##########################################################################


  public function DisplayTagCloud($maxelements=30,$gradation=6)
  {
    global $categoryid, $mainsettings_modrewrite;

    #SD362: converted to use core tag cloud display method
    SD_Tags::$maxentries   = 30;
    SD_Tags::$plugins      = $this->pluginid;
    SD_Tags::$tags_title   = $this->base->language['tags'];
    SD_Tags::$targetpageid = $categoryid;
    SD_Tags::$tag_as_param = $mainsettings_modrewrite?false:$this->base->pre.'_tag';

    echo SD_Tags::DisplayCloud(true);

  } //DisplayTagCloudx

  // ##########################################################################

  private function UnapproveImage()
  {
    global $DB, $userinfo;

    if(empty($this->base->allow_mod) || empty($this->base->settings['display_unapprove_link']) ||
       empty($this->base->pluginid) || empty($this->sectionid) || empty($this->imageid) ||
       empty($this->base->section_arr['sectionid']) || empty($this->base->image_arr['imageid']) ||
       ($this->base->image_arr['imageid'] != $this->imageid))
    {
      return false;
    }

    $DB->query('UPDATE '.$this->base->images_tbl.' SET activated = 0 WHERE imageid = %d AND activated = 1',$this->imageid);
    if(!empty($this->base->settings['log_moderation_actions']) &&  $DB->affected_rows())
    {
      WatchDog('Moderation', $this->base->language['watchdog_unapproved'].' (ID '.$this->imageid.
      ') '.$this->base->language['watchdog_by_user'].' "'.htmlentities($userinfo['username']).'" (ID'.$userinfo['userid'].')');
    }
    return true;

  } //UnapproveImage


  private function ApproveImage()
  {
    global $DB, $userinfo;

    if(empty($this->base->allow_mod) || empty($this->base->settings['display_approve_link']) ||
       empty($this->base->pluginid) || empty($this->sectionid) || empty($this->imageid) ||
       empty($this->base->section_arr['sectionid']) || empty($this->base->image_arr['imageid']) ||
       ($this->base->image_arr['imageid'] != $this->imageid))
    {
      return false;
    }

    $DB->query('UPDATE '.$this->base->images_tbl.' SET activated = 1 WHERE imageid = %d AND activated = 0',$this->imageid);
    if(!empty($this->base->settings['log_moderation_actions']) &&  $DB->affected_rows())
    {
      WatchDog('Moderation', $this->base->language['watchdog_approved'].' (ID '.$this->imageid.
      ') '.$this->base->language['watchdog_by_user'].' "'.htmlentities($userinfo['username']).'" (ID'.$userinfo['userid'].')');
    }
    return true;

  } //ApproveImage


  private function DeleteImage()
  {
    //SD360: changed behavior to "soft delete" = unapprove
    global $DB, $userinfo;

    if(empty($userinfo['loggedin']) || empty($this->base->settings['display_delete_link']) ||
       empty($this->base->pluginid) || empty($this->sectionid) || empty($this->imageid) ||
       empty($this->base->section_arr['sectionid']) || empty($this->base->image_arr['imageid']) ||
       ($this->base->image_arr['imageid'] != $this->imageid))
    {
      return false;
    }

    // Admins and section owners (option!) are allowed to do a "hard" delete
    if($this->base->IsSiteAdmin || $this->base->IsAdmin ||
       (!empty($this->base->is_section_owner) &&
        !empty($this->base->settings['allow_section_owner_delete'])) ||
       (!empty($this->base->is_media_owner) &&
        !empty($this->base->settings['allow_media_owner_delete'])) )
    {
      return $this->base->DeleteMediaEntry($this->imageid,true);
    }

    // Owners are only allowed to inactivate a media item
    if($this->base->allow_media_delete)
    {
      $DB->query('UPDATE '.$this->base->images_tbl.
                 ' SET activated = 0'.
                 ' WHERE imageid = %d  AND sectionid = %d AND activated = 1',
                 $this->imageid, $this->sectionid);

      if(!empty($this->base->settings['log_moderation_actions']))
      {
        WatchDog('Moderation', $this->base->language['watchdog_unapproved'].
          ' (ID '.$this->imageid.') '.$this->base->language['watchdog_by_user'].
          ' "'.htmlentities($userinfo['username']).'" (ID'.$userinfo['userid'].')');
      }
      return true;
    }

    return false;

  } //DeleteImage


  public function UpdateMedia() //SD362
  {
    global $DB, $userinfo;

    if(empty($this->base->pluginid) || empty($this->base->allow_media_edit) ||
       empty($this->sectionid) || empty($this->imageid) ||
       empty($this->base->section_arr['sectionid']) || empty($this->base->image_arr['imageid']) ||
       ($this->base->image_arr['imageid'] != $this->imageid))
    {
      return false;
    }

    if(!CaptchaIsValid('amihuman'))
    {
      DisplayMessage($sdlanguage['captcha_not_valid'],true);
      return false;
    }

    if(!empty($this->base->allow_media_delete) || !empty($this->base->allow_section_delete))
    {
      $key = $this->base->GetUserActionKey($this->imageid);
      $keyval = GetVar($this->base->pre.'_'.$key, false, 'bool', true, false);
      if(!empty($keyval))
      {
        return $this->DeleteImage();
      }
    }

    // Set current image as the section's thumb image?
    if(GetVar($this->base->pre.'_sectionthumb', false, 'bool', true, false))
    {
      $DB->query('UPDATE '.$this->base->sections_tbl.
                 ' SET imageid = %d WHERE sectionid = %d',
                 $this->imageid, $this->sectionid);
    }

    $title = trim(GetVar($this->base->pre.'_title', '', 'string', true, false));
    $activated = (GetVar($this->base->pre.'_unapprove', false, 'bool', true, false)?0:1);
    $descr = trim(GetVar($this->base->pre.'_description', '', 'string', true, false));
    $pr    = GetVar($this->base->pre.'_private', false, 'bool', true, false)?1:0;
    $ac    = GetVar($this->base->pre.'_allowcomments', false, 'bool', true, false)?1:0;
    $ar    = GetVar($this->base->pre.'_allowratings', false, 'bool', true, false)?1:0;
    $sa    = GetVar($this->base->pre.'_showauthor', false, 'bool', true, false)?1:0;

    $DB->query('UPDATE '.$this->base->images_tbl.
               " SET title = IFNULL(%s,title), description = '%s', activated = %d,".
               " private = %d, allowcomments = %d, allow_ratings = %d, showauthor = %d".
               ' WHERE imageid = %d AND sectionid = %d',
               (strlen($title)?"'$title'":'NULL'), $descr, $activated,
               $pr, $ac, $ar, $sa,
               $this->imageid, $this->sectionid);

    // Store image tags:
    $tags = trim(GetVar($this->base->pre.'_tags', '', 'string', true, false));
    $this->base->StoreImageTags($this->imageid,$tags);

    // Set current image as the section's thumbnail
    if($this->base->allow_section_edit &&
        empty($this->base->image_arr['media_type']) &&
       !empty($this->base->image_arr['filename']) &&
       GetVar($this->base->pre.'_sectionthumb', false, 'bool', true, false))
    {
      $DB->query('UPDATE '.$this->base->sections_tbl.
                 ' SET imageid = %d'.
                 ' WHERE sectionid = %d',
                 $this->imageid, $this->sectionid);

      global $SDCache;
      if(!empty($SDCache))
      {
        $SDCache->delete_cacheid('mg_cache_'.$this->base->pluginid.'_'.$this->sectionid);
        $SDCache->delete_cacheid('mg_cache_seo_'.$this->base->pluginid.'_'.$this->sectionid);
        if(!empty($this->base->section_arr['parentid']))
        {
          $SDCache->delete_cacheid('mg_cache_'.$this->base->pluginid.'_'.$this->base->section_arr['parentid']);
          $SDCache->delete_cacheid('mg_cache_seo_'.$this->base->pluginid.'_'.$this->base->section_arr['parentid']);
        }
      }
    }

    return true;

  } //UpdateMedia


  public function UpdateSection() //SD362
  {
    global $DB, $sdlanguage, $userinfo;

    if(empty($this->base->pluginid) || !$this->base->allow_section_edit ||
       empty($this->sectionid) || empty($this->base->section_arr['sectionid']) ||
       ($this->base->section_arr['sectionid'] != $this->sectionid))
    {
      return false;
    }

    if(!CaptchaIsValid('amihuman'))
    {
      DisplayMessage($sdlanguage['captcha_not_valid'],true);
      return false;
    }

    /*
    if(!empty($this->base->allow_section_delete))
    {
      $key = $this->base->GetUserActionKey($this->sectionid);
      $keyval = GetVar($this->base->pre.'_'.$key, false, 'bool', true, false);
      if(!empty($keyval))
      {
        return $this->DeleteSection();
      }
    }
    */

    $name      = trim(GetVar($this->base->pre.'_name', '', 'string', true, false));
    $activated = GetVar($this->base->pre.'_activated', false, 'bool', true, false)?1:0;
    $descr     = trim(GetVar($this->base->pre.'_description', '', 'string', true, false));
    $dc = GetVar($this->base->pre.'_displaycomments', false, 'bool', true, false)?1:0;
    $dr = GetVar($this->base->pre.'_displayratings', false, 'bool', true, false)?1:0;
    $ds = GetVar($this->base->pre.'_displaysocial', false, 'bool', true, false)?1:0;
    $dv = GetVar($this->base->pre.'_displayviews', false, 'bool', true, false)?1:0;

    $DB->query('UPDATE '.$this->base->sections_tbl.
               " SET name = IFNULL(%s,name), description = '%s',".
               ' display_comments = %d, display_ratings = %d, display_social_media = %d, display_view_counts = %d'.
               ($this->sectionid > 1 ? ', activated = '.(int)$activated : '').
               ' WHERE sectionid = %d',
               (strlen($name)?"'$name'":'NULL'), $descr, $dc, $dr, $ds, $dv,
               $this->sectionid);
    /*
    // Set current image as the section's thumbnail
    if($this->base->allow_section_edit &&
        empty($this->base->image_arr['media_type']) &&
       !empty($this->base->image_arr['filename']) &&
       GetVar($this->base->pre.'_sectionthumb', false, 'bool', true, false))
    {
      $DB->query('UPDATE '.$this->base->sections_tbl.
                 ' SET imageid = %d'.
                 ' WHERE sectionid = %d',
                 $this->imageid, $this->sectionid);

    }
    */
    global $SDCache;
    if(!empty($SDCache))
    {
      $SDCache->delete_cacheid('mg_cache_'.$this->base->pluginid.'_'.$this->sectionid);
      $SDCache->delete_cacheid('mg_cache_seo_'.$this->base->pluginid.'_'.$this->sectionid);
      if(!empty($this->base->section_arr['parentid']))
      {
        $SDCache->delete_cacheid('mg_cache_'.$this->base->pluginid.'_'.$this->base->section_arr['parentid']);
        $SDCache->delete_cacheid('mg_cache_seo_'.$this->base->pluginid.'_'.$this->base->section_arr['parentid']);
      }
    }

    return true;

  } //UpdateSection

} // end of class

} // DO NOT REMOVE!
