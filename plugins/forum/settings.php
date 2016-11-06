<?php
if(!defined('IN_PRGM') || !defined('IN_ADMIN')) exit();

require_once(ROOT_PATH.'plugins/'.dirname($plugin['settingspath']).'/forum_config.php');
$forum_config = false;

$script = '
<script type="text/javascript">
if (typeof(jQuery) !== "undefined") {
  jQuery(document).ready(function() {
		 $("#image").ace_file_input();
  });
}
</script>';

$js = array(
	SITE_URL . ADMIN_STYLES_FOLDER . '/assets/js/bootbox.min.js',
	);	

// ##########################################################################
// PLUGIN SUB-MENU
// ##########################################################################
$refreshpage = str_replace('&amp;load_wysiwyg=1', '', REFRESH_PAGE);

// ##########################################################################
// GET ACTION
// ##########################################################################

$action = GetVar('action', 'display_forums', 'string');

if($action != "save_forum" && $action != "update_forums")
{
echo '
<div class="btn-group">
<button data-toggle="dropdown" class="btn btn-info dropdown-toggle">
	'. AdminPhrase('menu_forum_title') .'
	<i class="ace-icon fa fa-angle-down icon-on-right"></i>
</button>
<ul class="dropdown-menu dropdown-info dropdown-menu-right">
    <li><a href="'.$refreshpage.'&amp;action=display_forums">'.AdminPhrase('menu_forums').'</a></li>
     <li><a href="'.$refreshpage.'&amp;action=display_forum_form'.SD_URL_TOKEN.'">'.AdminPhrase('menu_create_new_forum').'</a></li>
    <li><a href="'.$refreshpage.'&amp;action=display_settings">'.AdminPhrase('menu_forum_settings').'</a></li>
    <li><a href="'.$refreshpage.'&amp;action=display_statistics">'.AdminPhrase('menu_forum_statistics').'</a></li>
  </ul>
</div>
<div class="space-4"></div>';
}

//SD343: clear forum cache files after settings were updated
if(GetVar('updated', 0, 'bool') && !empty($SDCache) && $SDCache->IsActive())
{
  $SDCache->delete_cacheid(FORUM_CACHE_FORUMS);
  $SDCache->delete_cacheid(FORUM_CACHE_SEO);
}

// ##########################################################################
// FORUM SETTINGS
// ##########################################################################

class ForumSettings
{
  private $forums_tbl = '';
  private $topics_tbl = '';
  private $posts_tbl  = '';
  private $attach_tbl = '';
  private $img_path   = '';
  private $img_prefix = '';
  public $plugin_id   = 18;
  public $settings_arr = array();

  // ##########################################################################

  function ForumSettings()
  {
    if($this->plugin_id = GetPluginID('Forum'))
    {
      $this->settings_arr = GetPluginSettings($this->plugin_id);
      $this->forums_tbl = PRGM_TABLE_PREFIX.'p_forum_forums';
      $this->topics_tbl = PRGM_TABLE_PREFIX.'p_forum_topics';
      $this->posts_tbl  = PRGM_TABLE_PREFIX.'p_forum_posts';
      $this->attach_tbl = PRGM_TABLE_PREFIX.'p_forum_attachments';
      $this->img_path   = 'plugins/forum/images/';
      $this->img_prefix = 'forum_image_';
    }
  } //ForumSettings


  // ##########################################################################
  // DISPLAY FORUM  PERMISSIONS
  // ##########################################################################

  function DisplayForumPermissions($embedded = false, $forum = null)
  {
    global $DB, $refreshpage, $sdlanguage;

    if(!empty($embedded) && @is_array($forum))
    {
      $forum_id = (int)$forum['forum_id'];
    }
    else
    {
      if(!CheckFormToken())
      {
        RedirectPage($refreshpage, '<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />', 2, true);
        return;
      }
      $forum_id = GetVar('forum_id', 0, 'whole_number');
    }
    // Was a valid forum id provided?
    if(!$embedded &&
       (empty($forum_id) ||
        !($forum = $DB->query_first('SELECT forum_id, title, access_post, access_view, moderated, post_edit
                                     FROM {p_forum_forums} WHERE forum_id = %d LIMIT 1',$forum_id))))
    {
      DisplayMessage(AdminPhrase('err_invalid_forum_id'), true);
      return false;
    }

    // Initialize variables
    $groups = array();
    $forum_ap = $forum_av = $forum_mod = $forum_pedit = array();

    // Split access permissions into arrays (if set)
    if(!empty($forum['access_post']) && ($forum['access_post'] != '||') && strlen($forum['access_post']))
    {
      $forum_ap = @explode('|', $forum['access_post']);
    }
    if(!empty($forum['access_view']) && ($forum['access_view'] != '||') && strlen($forum['access_view']))
    {
      $forum_av = @explode('|', $forum['access_view']);
    }
    if(!empty($forum['moderated']) && ($forum['moderated'] != '||') && strlen($forum['moderated']))
    {
      $forum_mod = @explode('|', $forum['moderated']);
    }
    //SD343: "post_edit" added
    if(!empty($forum['post_edit']) && ($forum['post_edit'] != '||') && strlen($forum['post_edit']))
    {
      $forum_pedit = @explode('|', $forum['post_edit']);
    }

    $options_cv = $options_cp = $options_mod = $options_pedit = '';

    // Fetch all usergroups (do not exclude Administrators here!)
    $getusergroups = $DB->query('SELECT usergroupid, name FROM {usergroups} ORDER BY usergroupid');
    while($ug = $DB->fetch_array($getusergroups,null,MYSQL_ASSOC))
    {
      $ugid = $ug['usergroupid'];
      $ugname = $ug['name'];
      $options_cv .= '<option value="'.$ugid.'" '.
                     (in_array($ugid,$forum_av) ? 'selected="selected"' : '').">".$ugname."</option>
                     ";
      $options_cp .= '<option value="'.$ugid.'" '.
                     (in_array($ugid,$forum_ap) ? 'selected="selected"' : '').">".$ugname."</option>
                     ";
      $options_mod .= '<option value="'.$ugid.'" '.
                      (in_array($ugid,$forum_mod) ? 'selected="selected"' : '').">".$ugname."</option>
                      ";
      //SD343: for "post_edit"
      $options_pedit .= '<option value="'.$ugid.'" '.
                        (in_array($ugid,$forum_pedit) ? 'selected="selected"' : '').">".$ugname."</option>
                        ";
    } //while

    if(empty($embedded) || @!is_array($forum))
    {
      echo '
    <form name="p_forum_access" method="post" action="'.$refreshpage.'&amp;action=update_forum_permissions&amp;forum_id=' . $forum_id . '" class="form-horizontal">
    '. PrintSecureToken();

      echo '<h3 class="header blue lighter">' . AdminPhrase('forum_permissions_title').' "'.$forum['title'].'</h3>';
    }

    echo '<div class="form-group">
<label class="control-label col-sm-2">'.AdminPhrase('forum_options_title').'</label>
    <div class="col-sm-6">
        <select class="form-control" name="forum_moderated[]" size="10" multiple="multiple">
        '.$options_mod.'
        </select>
		<span class="helper-text">'.AdminPhrase('auto_moderate_descr').'</span>
	</div>
</div>
   <div class="form-group">
<label class="control-label col-sm-2">'.AdminPhrase('view_permissions_title').'</label>
    <div class="col-sm-6">
        <select class="form-control"  name="forum_access_view[]" size="10" multiple="multiple">
        '.$options_cv.'
        </select>
      <span class="helper-text">'.AdminPhrase('view_permissions_descr').'</span>
	 </div>
</div>
<div class="form-group">
<label class="control-label col-sm-2">'.AdminPhrase('posting_permissions_title').'</label>
    <div class="col-sm-6">
        <select class="form-control"  name="forum_access_post[]" size="10" multiple="multiple">
        '.$options_cp.'</select>
		<span class="helper-text">'.AdminPhrase('posting_permissions_descr').'</span>
	</div>
</div>
 <div class="form-group">
<label class="control-label col-sm-2">'.AdminPhrase('post_edit_permissions_title').'</label>
    <div class="col-sm-6">
        <select class="form-control"  name="forum_post_edit[]" size="10" multiple="multiple">
        '.$options_pedit.'</select>
		<span class="helper-text">'.AdminPhrase('post_edit_permissions_descr').'</span>
	</div>
</div>
      
    ';

    if(empty($embedded) || @!is_array($forum))
    {


      echo '<div class="center">'.AdminPhrase('posting_permissions_note').'<br />
    <button type="submit" class="btn btn-info" value="" /><i class="ace-icon fa fa-check"></i>'.htmlspecialchars(AdminPhrase('update_permissions'),ENT_COMPAT).'</button>
	</div>
    </form>';
    }

  } //DisplayForumPermissions


  // ##########################################################################
  // UPDATE FORUM PERMISSIONS
  // ##########################################################################

  function UpdateForumPermissions($forum_id=null)
  {
    global $DB, $refreshpage, $sdlanguage;

    $is_embedded = isset($forum_id);
    if(!$is_embedded)
    {
      // SD313: security token check
      if(!CheckFormToken())
      {
        RedirectPage($refreshpage, '<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />', 2, true);
        return;
      }

      $forum_id = GetVar('forum_id', 0, 'whole_number');
    }

    // Was a valid forum id provided?
    if(empty($forum_id) ||
       !$DB->query_first('SELECT forum_id FROM {p_forum_forums} WHERE forum_id = %d LIMIT 1',$forum_id))
    {
      DisplayMessage(AdminPhrase('invalid_forum_id'), true);
      return false;
    }

    $forum_access_view = GetVar('forum_access_view', array(), 'array', true, false);
    $forum_access_post = GetVar('forum_access_post', array(), 'array', true, false);
    $forum_moderated   = GetVar('forum_moderated',   array(), 'array', true, false);
    $forum_post_edit   = GetVar('forum_post_edit',   array(), 'array', true, false); //SD343

    $access_view = '';
    if(!empty($forum_access_view))
    {
      $access_view = count($forum_access_view) > 1 ? implode('|', $forum_access_view) : $forum_access_view[0];
      $access_view = empty($access_view) ? '' : '|'.$access_view.'|';
    }

    $access_post = '';
    if(!empty($forum_access_post))
    {
      $access_post = count($forum_access_post) > 1 ? implode('|', $forum_access_post) : $forum_access_post[0];
      $access_post = empty($access_post) ? '' : '|'.$access_post.'|';
    }

    $moderated = '';
    if(!empty($forum_moderated))
    {
      $moderated = count($forum_moderated) > 1 ? implode('|', $forum_moderated) : $forum_moderated[0];
      $moderated = empty($moderated) ? '' : '|'.$moderated.'|';
    }

    //SD343
    $post_edit = '';
    if(!empty($forum_post_edit))
    {
      $post_edit = count($forum_post_edit) > 1 ? implode('|', $forum_post_edit) : $forum_post_edit[0];
      $post_edit = empty($post_edit) ? '' : '|'.$post_edit.'|';
    }

    $DB->query("UPDATE {p_forum_forums} SET access_post = '%s', access_view = '%s', moderated = '%s', post_edit = '%s'".
               ' WHERE forum_id = %d',
               $access_post, $access_view, $moderated, $post_edit, $forum_id);

    global $SDCache;
    if(!empty($SDCache) && $SDCache->IsActive()) $SDCache->delete_cacheid(FORUM_CACHE_FORUMS);

    if(!$is_embedded)
    {
      RedirectPage($refreshpage, AdminPhrase('forum_permissions_updated'));
    }

  } //UpdatePluginPermissions

  // ##########################################################################

  function DeleteForum()
  {
    global $DB, $refreshpage, $sdlanguage;

    // SD313: security token check
    if(!CheckFormToken())
    {
      RedirectPage($refreshpage, '<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />', 2, true);
      return;
    }

    if(!$forum_id = GetVar('forum_id', 0, 'whole_number'))
    {
      return;
    }

    if($get_topics = $DB->query('SELECT topic_id FROM {p_forum_topics} WHERE forum_id = %d', $forum_id))
    {
      while($topic_arr = $DB->fetch_array($get_topics))
      {
        $DB->query('DELETE FROM {p_forum_posts} WHERE topic_id = %d', $topic_arr['topic_id']);
      }
    }

    $DB->query('DELETE FROM {p_forum_topics} WHERE forum_id = %d', $forum_id);
    $DB->query('DELETE FROM {p_forum_forums} WHERE forum_id = %d LIMIT 1', $forum_id );

    $unlink = ROOT_PATH.$this->img_path.$this->img_prefix.$forum_id; //SD342
    if(file_exists($unlink))
    {
      @unlink($unlink);
    }

    global $SDCache;
    if(!empty($SDCache) && $SDCache->IsActive()) $SDCache->delete_cacheid(FORUM_CACHE_FORUMS);

    RedirectPage($refreshpage, AdminPhrase('forum_deleted'));
  }

  // ##########################################################################

  function SaveForum()
  {
    global $DB, $refreshpage, $sdlanguage;

    // SD313: security token check
    if(!CheckFormToken())
    {
      RedirectPage($refreshpage, '<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />', 2, true);
      return;
    }

    $forum_id = GetVar('forum_id', 0, 'natural_number', true, false);
    if($forum_id && GetVar('forum_delete', 0, 'bool', true, false)) //SD343
    {
      $ForumSettings->DeleteForum(); // also redirects
      return;
    }

    $online           = GetVar('online', 0, 'natural_number', true, false);
    $is_category      = Is_Valid_Number(GetVar('is_category', 0, 'natural_number', true, false),false,0,1);
    $parent_forum_id  = GetVar('parent_forum_id', 0, 'natural_number', true, false);
    $display_order    = GetVar('display_order', 0, 'natural_number, true, false');
    $link             = GetVar('link_to', '', 'html', true, false);
    $target           = GetVar('target', '', 'string', true, false);
    $title            = GetVar('title', 'untitled', 'html', true, false);
    $seotitle         = GetVar('seo_title', '', 'string', true, false);
    $description      = GetVar('description', '', 'html', true, false);
    $del_image        = GetVar('delete_image', false, 'whole_number', true, false);
    $metadescription  = GetVar('metadescription', '', 'string', true, false);
    $metakeywords     = GetVar('metakeywords', '', 'string', true, false);
    $enable_likes     = GetVar('enable_likes', 0, 'bool', true, false)?1:0;
    $errors = array();
    /*
    Extra precautions:
    a) In NO case show the "Is Category" option if topics already exist!
    b) If an existing forum is currently NOT a category, then do NOT show the checkbox class="ace"
       for the "Is Category" option, IF there already topics exist within it
    c) If an existing forum IS currently a category, then do NOT show the checkbox class="ace"
       for the "Is Category" option, IF there already forums linked to it (children)
    */
    $topic_count = $child_count = 0;
    if(!empty($forum_id))
    {
      if($get_topic_count = $DB->query_first('SELECT COUNT(*) FROM '.$this->topics_tbl.' WHERE forum_id = %d', $forum_id))
      {
        $topic_count = (int)$get_topic_count[0];
      }
      if($get_child_count = $DB->query_first('SELECT COUNT(*) FROM '.$this->forums_tbl.' WHERE parent_forum_id = %d', $forum_id))
      {
        $child_count = (int)$get_child_count[0];
      }
      unset($get_child_count,$get_topic_count);
      if($is_category && ($topic_count > 0))
      {
        $is_category = 0;
        echo '<p>'.AdminPhrase('err_category_ignored').'</p>';
      }
    }
    if(!empty($is_category)) // Category must not have a parent other than 0!
    {
      $parent_forum_id = 0;
    }

    // Convert seo title for URL and check if seotitle is taken
    $title = preg_replace('/&[^amp;]/i', '&amp;', $title); //SD343
    if(!strlen(trim($seotitle)))
    {
      $seotitle = ConvertNewsTitleToUrl($title, 0, 0, true);
    }
    else
    {
      $seotitle = ConvertNewsTitleToUrl($seotitle, 0, 0, true);
    }
    if(strlen($seotitle))
    {
      if($DB->query_first("SELECT seo_title FROM {p_forum_forums} WHERE seo_title = '%s' AND forum_id <> %d",
                           $seotitle, (int)$forum_id))
      {
        $seotitle = '';
        echo AdminPhrase('err_seo_title_existing').'<br />';
      }
    }

    // Check image options (not for new forums)
    $image = '';
    $image_w = $image_h = 0;
    if(!empty($forum_id))
    {
      // Is option do delete existing image set?
      if($del_image)
      {
        $unlink = ROOT_PATH.$this->img_path.$this->img_prefix.$forum_id;
        if(file_exists($unlink))
        {
          @unlink($unlink);
        }
      }

      // Upload an image for forum
      if(isset($_FILES['forum_image']) && !empty($_FILES['forum_image']['name']))
      {
        if(!class_exists('SD_Media_Base'))
        {
          @require_once(SD_INCLUDE_PATH.'class_sd_media.php');
        }

        // lets make sure the extension is correct
        $img = new SD_Image();
        $res = $img->UploadImage('forum_image',$this->img_path,$this->img_prefix.$forum_id,
                                 SD_Media_Base::getImageExtentions(),512000,400,400);
        if(is_array($res))
        {
          $errors = $res;
        }
        else
        {
          if($img->getImageValid())
          {
            if(!SD_Media_Base::ImageSecurityCheck(true, ROOT_PATH.$this->img_path, basename($img->image_file)))
            {
              $errors[] = AdminPhrase('err_invalid_upload');
            }
            else
            {
              $image = basename($img->image_file);
              $image_w = $img->getImageWidth();
              $image_h = $img->getImageheight();
            }
          }
        }
        unset($img);
      }
    }
    if(!empty($errors))
    {
      DisplayMessage($errors, true, AdminPhrase('err_upload_errors'));
    }

    $title = $DB->escape_string($title);
    $seotitle = $DB->escape_string($seotitle);
    $description = $DB->escape_string($description);
    $link = $DB->escape_string($link);
    if(empty($forum_id))
    {
      if(strlen($image))
      {
        $DB->query(
        "INSERT INTO ".$this->forums_tbl." (forum_id, is_category, online, parent_forum_id, display_order,
         title, seo_title, description, image, image_w, image_h, link_to, target, metadescription, metakeywords,enable_likes) VALUES
        (NULL, $is_category, $online, $parent_forum_id, $display_order, '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', '%s', '%s', %d)",
        $title, $seotitle, $description, $image, $image_w, $image_h, $link, $target, $metadescription, $metakeywords, $enable_likes);
      }
      else
      {
        $DB->query(
        "INSERT INTO ".$this->forums_tbl." (forum_id, is_category, online, parent_forum_id, display_order,
        title, seo_title, description, link_to, target, metadescription, metakeywords, enable_likes) VALUES
        (NULL, $is_category, $online, $parent_forum_id, $display_order, '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d)",
        $title, $seotitle, $description, $link, $target, $metadescription, $metakeywords, $enable_likes);
      }

      if($forum_id) $this->UpdateForumPermissions($forum_id);
      RedirectPage($refreshpage, AdminPhrase('new_forum_created'));
    }
    else
    {
      if(strlen($image) || $del_image)
      {
        $DB->query("UPDATE ".$this->forums_tbl."
                    SET is_category     = $is_category,
                        online          = $online,
                        parent_forum_id = $parent_forum_id,
                        display_order   = $display_order,
                        title           = '%s',
                        seo_title       = '%s',
                        description     = '%s',
                        link_to         = '%s',
                        target          = '%s',
                        image           = '%s',
                        image_w         = %d,
                        image_h         = %d,
                        metadescription = '%s',
                        metakeywords    = '%s',
                        enable_likes    = %d
                   WHERE forum_id = %d",
                   $title, $seotitle, $description, $link, $target, $image, $image_w, $image_h,
                   $metadescription, $metakeywords, $enable_likes, $forum_id);
      }
      else
      {
        $DB->query("UPDATE ".$this->forums_tbl."
                    SET is_category     = $is_category,
                        online          = $online,
                        parent_forum_id = $parent_forum_id,
                        display_order   = $display_order,
                        title           = '%s',
                        seo_title       = '%s',
                        description     = '%s',
                        link_to         = '%s',
                        target          = '%s',
                        metadescription = '%s',
                        metakeywords    = '%s',
                        enable_likes    = %d
                   WHERE forum_id = %d",
                   $title, $seotitle, $description, $link, $target,
                   $metadescription, $metakeywords, $enable_likes, $forum_id);
      }
      $this->UpdateForumPermissions($forum_id);
      RedirectPage($refreshpage, AdminPhrase('forum_updated'));
    }

    global $SDCache;
    if(!empty($SDCache) && $SDCache->IsActive()) $SDCache->delete_cacheid(FORUM_CACHE_FORUMS);

  } //SaveForum

  // ##########################################################################

  function UpdateForums()
  {
    global $DB, $refreshpage, $sdlanguage;

    // SD313: security token check
    if(!CheckFormToken())
    {
      RedirectPage($refreshpage, '<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />', 2, true);
      return;
    }

    $forum_id_arr = GetVar('forum_id_arr', array(), 'array');
    $status_arr = GetVar('status_arr', array(), 'array');
    $display_order_arr = GetVar('display_order_arr', array(), 'array');
    //$title_arr = GetVar('title_arr', array(), 'array');
    //$description_arr = GetVar('description_arr', array(), 'array');

    for($i = 0; $i < count($forum_id_arr); $i++)
    {
      $DB->query("UPDATE {p_forum_forums}
        SET online = $status_arr[$i],
            display_order = $display_order_arr[$i]
        WHERE forum_id = $forum_id_arr[$i] LIMIT 1");
        //title = '$title_arr[$i]',
        //description = '$description_arr[$i]'
    }

    global $SDCache;
    if(!empty($SDCache) && $SDCache->IsActive()) $SDCache->delete_cacheid(FORUM_CACHE_FORUMS);

    RedirectPage($refreshpage, AdminPhrase('forums_updated'));

  } //UpdateForums

  // ##########################################################################

  function DisplaySettings()
  {
    global $DB, $refreshpage;

    PrintPluginSettings($this->plugin_id, array('forum_display_settings','forum_topic_settings'), $refreshpage.'&updated=1');

  } //DisplaySettings

  // ##########################################################################

  function DisplayStatistics($DoUpdate)
  {
	  
    global $DB, $refreshpage, $forum_config;

    $get_forums = $DB->query('SELECT ff.forum_id, ff.display_order, ff.title
      FROM '.TABLE_PREFIX.'p_forum_forums ff
      WHERE IFNULL(ff.is_category,0) = 0
      ORDER BY ff.display_order ASC');

    StartSection(AdminPhrase('forum_statistics'));

    echo '
    <table class="table table-striped table-bordered" summary="layout">
    <tr>
      <td class="td1" width="100" align="center">'.AdminPhrase('lbl_display_order').'</td>
      <td class="td1" align="center">'.AdminPhrase('lbl_forum_title').'</td>
      <td class="td1" align="center">'.AdminPhrase('lbl_topics_mod').'</td>
      <td class="td1" align="center">'.AdminPhrase('lbl_posts_mod').'</td>
      <td class="td1" align="center">'.AdminPhrase('lbl_unique_users').'</td>
    </tr>';
    while($forum_arr = $DB->fetch_array($get_forums))
    {
      $forum_id = $forum_arr['forum_id'];
      // Get sub-statistics by single SELECT's since a combined
      // SQL with multiple sub-selects takes up to several
      // minutes on larger databases
      $stat_topic_count = 0;
      if($stat_topic_count = $DB->query_first(
         'SELECT COUNT(*) fcount
         FROM '.TABLE_PREFIX.'p_forum_topics ft
         WHERE ft.forum_id = %d', $forum_id))
      {
        $stat_topic_count = $stat_topic_count['fcount'];
      }

      $stat_mod_topic_count = 0;
      if($stat_mod_topic_count = $DB->query_first(
         'SELECT COUNT(*) fcount
          FROM '.TABLE_PREFIX.'p_forum_topics ft
          WHERE ft.forum_id = %d
          AND IFNULL(ft.moderated,0) = 1', $forum_id))
      {
        $stat_mod_topic_count = $stat_mod_topic_count['fcount'];
      }

      $stat_post_count = 0;
      if($stat_post_count = $DB->query_first(
         'SELECT COUNT(fp.post_id) fcount
          FROM '.TABLE_PREFIX.'p_forum_posts  fp
          INNER JOIN '.TABLE_PREFIX.'p_forum_topics ft
             ON ft.topic_id = fp.topic_id
          WHERE ft.forum_id = %d', $forum_id))
      {
        $stat_post_count = $stat_post_count['fcount'];
      }

      $stat_user_count = 0;
      if($stat_user_count = $DB->query_first(
         'SELECT COUNT(DISTINCT fp.user_id) fcount
          FROM '.TABLE_PREFIX.'p_forum_posts  fp
          INNER JOIN '.TABLE_PREFIX.'p_forum_topics ft
             ON ft.topic_id = fp.topic_id
          WHERE ft.forum_id = %d', $forum_id))
      {
        $stat_user_count = $stat_user_count['fcount'];
      }

      $stat_user_mod_p_count = 0;
      if($stat_user_mod_p_count = $DB->query_first(
         'SELECT COUNT(DISTINCT fp.user_id) fcount
          FROM '.TABLE_PREFIX.'p_forum_posts  fp
          INNER JOIN '.TABLE_PREFIX.'p_forum_topics ft
             ON ft.topic_id = fp.topic_id
          WHERE ft.forum_id = %d
            AND IFNULL(fp.moderated,0) = 1', $forum_id))
      {
        $stat_user_mod_p_count = $stat_user_mod_p_count['fcount'];
      }

      $max_topic_id = 0;
      if($max_topic_id = $DB->query_first(
         'SELECT MAX(topic_id) fcount'.
         ' FROM '.TABLE_PREFIX.'p_forum_topics'.
         ' WHERE forum_id = %d', $forum_id))
      {
        $max_topic_id = $max_topic_id['fcount'];
      }
      else $max_topic_id = 0;

      $max_post_id = 0;
      if($max_topic_id && $max_post_id = $DB->query_first(
         'SELECT MAX(post_id) fcount'.
         ' FROM '.TABLE_PREFIX.'p_forum_posts'.
         ' WHERE topic_id = %d', $max_topic_id))
      {
        $max_post_id = $max_post_id['fcount'];
      }
      else $max_post_id = 0;

      $topic_arr = $DB->query_first('
        SELECT ft.topic_id, ft.title, fp.post_id, fp.username, fp.`date` post_date,
        u.username users_name
        FROM '.TABLE_PREFIX.'p_forum_topics ft
        INNER JOIN '.TABLE_PREFIX.'p_forum_posts fp ON fp.topic_id = ft.topic_id
        LEFT  JOIN '.TABLE_PREFIX.'users u ON u.userid = fp.user_id
        WHERE ft.forum_id = %d
        AND ft.topic_id = ' . (int)$max_topic_id . '
        AND fp.post_id  = ' . (int)$max_post_id .'
        LIMIT 1',
        $forum_id);

      if($DoUpdate)
      {
        if(!empty($forum_id))
        {
          $DB->query('UPDATE {p_forum_forums}
                      SET last_topic_id = '      . (int)$topic_arr['topic_id'] . ','.
                        " last_topic_title = '"  . $DB->escape_string($topic_arr['title']) . "',".
                        ' last_post_id = '       . (int)$topic_arr['post_id'] . ','.
                        " last_post_username = '". $DB->escape_string(!empty($topic_arr['users_name']) ? $topic_arr['users_name'] : $topic_arr['username']) . "',".
                        ' last_post_date = '     . (int)$topic_arr['post_date'] . ','.
                        ' topic_count = '        . (int)$stat_topic_count . ','.
                        ' post_count = '         . (int)$stat_post_count .
                    ' WHERE forum_id = ' . $forum_id);
        }
        //SD332: update user's post/topic's count
        $DB->query('UPDATE {users} users'.
                   ' SET user_post_count   = IFNULL((SELECT COUNT(*) FROM {p_forum_posts} posts WHERE posts.user_id = users.userid),0),'.
                       ' user_thread_count = IFNULL((SELECT COUNT(*) FROM {p_forum_topics} topics WHERE topics.post_user_id = users.userid),0)');
      }
      echo '
          <tr>
            <td class="center">' . $forum_arr['display_order'] . '</td>
            <td class="center">' . $forum_arr['title'] . '</td>
            <td class="center">' . $stat_topic_count;
      if($stat_mod_topic_count)
      {
        $link = $forum_config->RewriteForumLink($forum_arr['forum_id']);
        $link2 = $link.((strpos($link,'?')===false)?'?':'&amp;').'forum_action=mod_topics';
        echo '&nbsp;&nbsp;/&nbsp;&nbsp;<strong><a href="'.$link2.'" style="color:red" target="_blank">['.
             $stat_mod_topic_count.
             ']</a></strong>';
      }
      echo '</td>
            <td class="center">' . $stat_post_count;
      if($stat_user_mod_p_count)
      {
        $link = $forum_config->RewriteForumLink($forum_arr['forum_id']);
        $link2 = $link.((strpos($link,'?')===false)?'?':'&amp;').'forum_action=mod_posts';
        echo '&nbsp;&nbsp;/&nbsp;&nbsp;<strong><a href="'.$link2.'" style="color:red" target="_blank">['.
             $stat_user_mod_p_count.
             ']</a></strong>';
      }
      echo '</td>
            <td class="center">' . $stat_user_count . '</td>
          </tr>
          <tr>
            <td class="td3" colspan="2"> </td>
            <td class="td3" align="center" colspan="3">'.
            (empty($stat_topic_count) ? '' :
                   'Last post: '.DisplayDate($topic_arr['post_date']).
                   ' by User: ' . $topic_arr['users_name']) . '
            </td>
          </tr>
          ';
    } //while

    echo '
        </table>
        ';

    if($DoUpdate)
    {
      echo '<div">'.AdminPhrase('statistics_updated').'</div>
        ';
    }

    echo '
        <form method="post" action="'.$refreshpage.'&amp;action=update_statistics">
        '.PrintSecureToken().'
        <button type="submit" class="btn btn-info" value=""><i class="ace-icon fa fa-check"></i> '.AdminPhrase('update_all_statistics').'</button><br />
        '.AdminPhrase('update_all_statistics_hint').'
        </form>
        ';
 

  } //DisplayStatistics


  function DisplayForumSelection($forumid = 0, $showzerovalue = 0, $parentid = 0,
                                 $sublevelmarker = '', $selectname = 'parent_forum_id',
                                 $style='',$ignore_id=0,$size=0)
  {
    global $DB, $userinfo;

    if($sublevelmarker=='- - ') return; // only allow 1 sub-level for forums!!!
    // start selection box
    if($parentid == 0)
    {
      echo '
      <select class="form-control" id="' . $selectname . '" name="' . $selectname . '"'.
      (empty($style)?'':' style="'.$style.'"').
      (empty($size)?'':' size="'.$size.'"').'>';

      if($showzerovalue)
      {
        echo '
        <option value="0">-</option>';
      }
    }
    else
    {
      $sublevelmarker .= '- - ';
    }

    if($getforums = $DB->query('SELECT forum_id, parent_forum_id, title, is_category, online FROM '.$this->forums_tbl.'
                                WHERE parent_forum_id = %d'.
                                (!empty($ignore_id)?' AND forum_id <> '.(int)$ignore_id:'').
                                ' ORDER BY display_order, title', $parentid))
    {
      while($forum = $DB->fetch_array($getforums))
      {
        $suffix = $forum['online'] ? '' : ' *';
        $style = $forum['is_category'] ? 'style="font-weight: bold; background-color:#B0B0B0" ' : '';
        echo '
          <option '.$style.'value="' . $forum['forum_id'] . '" ' . ($forumid == $forum['forum_id'] ? 'selected="selected"' : '') . '>' .
                             $sublevelmarker.htmlspecialchars(strip_alltags($forum['title']),ENT_COMPAT).$suffix.'</option>';
        $this->DisplayForumSelection($forumid, $showzerovalue, $forum['forum_id'], $sublevelmarker, '', $style, $ignore_id);
      }
      $DB->free_result($getforums);
    }
    // end the selection box
    if($parentid == 0)
    {
      echo '
      </select>';
    }

  } //DisplayForumSelection


  // ###############################################################################
  // DISPLAY FORUM FORM
  // ###############################################################################

  function DisplayForumForm($forum_id=0)
  {
    global $DB, $mainsettings, $sdurl, $userinfo, $usersystem, $admin_phrases, $refreshpage;

    if($forum_id > 0)
    {
      $forum = $DB->query_first('SELECT * FROM '.$this->forums_tbl.' WHERE forum_id = %d', $forum_id);
    }
    else
    {
      $forum_id = 0;
      $forum = array(
         'forum_id'         => 0,
         'online'           => 0,
         'is_category'      => 0,
         'parent_forum_id'  => 0,
         'title'            => '',
         'description'      => '',
         'display_order'    => 0,
         'access_post'      => '',
         'access_view'      => '',
         'moderated'        => '',
         #SD342:
         'seo_title'        => '',
         'metakeywords'     => '',
         'metadescription'  => '',
         'pwd'              => '', #TODO: unused
         'pwd_salt'         => '', #TODO: unused
         'link_to'          => '',
         'target'           => '',
         'image'            => 0,
         'image_w'          => 0,
         'image_h'          => 0,
         'allow_rss'        => 1,  #TODO: unused
         'publish_start'    => 0,  #TODO: unused
         'publish_end'      => 0,  #TODO: unused
         #SD343:
         'enable_likes'     => 0,
         'post_edit'        => '',
         );
      $parent_id = 0;
      if($parent_id = $DB->query_first('SELECT forum_id FROM {p_forum_forums} WHERE is_category = 1 AND online = 1 ORDER BY forum_id LIMIT 1'))
      {
        $forum['parent_forum_id'] = (int)$parent_id[0];
      }
      unset($parent_id);
    }

    $online = !empty($forum['online']);
    $status_online = AdminPhrase('status_online',true) ? AdminPhrase('status_online') : 'Online';
    $status_offline = AdminPhrase('status_offline',true) ? AdminPhrase('status_offline') : 'Offline';

    echo '
    <form enctype="multipart/form-data" method="post" action="'.$refreshpage.'" name="forumform" class="form-horizontal">
    '.PrintSecureToken().'
    <input type="hidden" name="action" value="save_forum" />
    <input type="hidden" name="forum_id" value="' . $forum_id . '" />';

    echo '<h3 class="header blue lighter">' . AdminPhrase('forum_details') . '</h3>';

    echo '<div class="form-group">
  <label class="control-label col-sm-2">' . AdminPhrase('lbl_title') . ' (HTML)</label><div class="col-sm-6">
          <input type="text" class="form-control" name="title" style="min-with: 100px; width: 95%;" value="' . ($forum['title']) . '" /></div>
</div><div class="form-group">
  <label class="control-label col-sm-2">' . AdminPhrase('forum_seo_title') . '</label><div class="col-sm-6">
          <input type="text" class="form-control" name="seo_title" style="min-with: 100px; width: 95%;" value="' . $forum['seo_title'] . '" />
		  <span class="helper-text"> ' . AdminPhrase('forum_seo_title_hint') . '</span></div>
</div><div class="form-group">
  <label class="control-label col-sm-2">'.AdminPhrase('lbl_status').'</label><div class="col-sm-6">
          <select name="online" class="form-control">
            <option value="0" ' . (!$online ? 'selected="selected"' : '') . '>'.$status_offline.'</option>
            <option value="1" ' . ( $online ? 'selected="selected"' : '') . '>'.$status_online.'</option>
          </select></div>
</div>';

    /*
    Extra precautions:
    a) In NO case show the "Is Category" option if topics already exist!
    b) If an existing forum is currently NOT a category, then do NOT show the checkbox class="ace"
       for the "Is Category" option, IF there already topics exist within it
    c) If an existing forum IS currently a category, then do NOT show the checkbox class="ace"
       for the "Is Category" option, IF there already forums linked to it (children)
    */
    $topic_count = $child_count = 0;
    if(!empty($forum_id))
    {
      if($get_topic_count = $DB->query_first("SELECT COUNT(*) FROM ".$this->topics_tbl." WHERE forum_id = %d", $forum_id))
      {
        $topic_count = (int)$get_topic_count[0];
      }
      if($get_child_count = $DB->query_first("SELECT COUNT(*) FROM ".$this->forums_tbl." WHERE parent_forum_id = %d", $forum_id))
      {
        $child_count = (int)$get_child_count[0];
      }
      unset($get_child_count,$get_topic_count);
    }

    echo '<div class="form-group">
  <label class="control-label col-sm-2">Forum is a Category?</label><div class="col-sm-6">';
    if($topic_count+$child_count==0)
    {
      echo '<input type="checkbox" class="ace" id="check_category" name="is_category" value="1"' . ($forum['is_category']?' checked="checked"':'') .
           ' onclick="javascript:jQuery(\'#parent_row\').toggle();" /><span class="lbl"> '.AdminPhrase('is_category') . '</span>';
    }
    else
    {
      if(empty($forum['is_category']))
        echo AdminPhrase('category_not_possible');
      else
        echo ' <input type="hidden" name="is_category" value="1" /> '.AdminPhrase('category_not_possible2');
    }
    echo '</div></div>';

    echo '<div class="form-group">
  <label class="control-label col-sm-2">'.AdminPhrase('forum_parent_forum') . '</label><div class="col-sm-6">';

    $this->DisplayForumSelection($forum['parent_forum_id'], 1, 0, '', 'parent_forum_id', 'min-with: 100px; width: 95%;',$forum['forum_id']);

    echo '</div></div>';

    echo '<div class="form-group">
  <label class="control-label col-sm-2">'.AdminPhrase('lbl_display_order').'</label><div class="col-sm-6">
          <input type="text" class="form-control" name="display_order" value="' . $forum['display_order'] . '" /></div>
</div><div class="form-group">
  <label class="control-label col-sm-2">'.AdminPhrase('lbl_enable_likes').'</label><div class="col-sm-6">
          <input type="checkbox" class="ace" name="enable_likes" value="1"'.(empty($forum['enable_likes'])?'':' checked="checked"').' /><span class="lbl"></span></div>
</div><div class="form-group">
  <label class="control-label col-sm-2">'.AdminPhrase('forum_link_to').'</label><div class="col-sm-6">
          <input type="text" class="form-control" name="link_to" style="min-with: 100px; width: 95%;" value="' . htmlentities($forum['link_to'], ENT_QUOTES) . '" size="30" />
		  <span class="helper-text"> '.AdminPhrase('forum_link_to_hint').'</span>
		  </div>
</div><div class="form-group">
  <label class="control-label col-sm-2">'.AdminPhrase('forum_external_link_target').'</label><div class="col-sm-6">
          <select name="target" class="form-control">
            <option value="" ' . (empty($forum['target']) ? 'selected="selected"' : '') . '>-</option>
            <option value="_blank" ' . ($forum['target'] == '_blank'  ? 'selected="selected"' : '') . '>_blank</option>
            <option value="_self" ' .  ($forum['target'] == '_self'   ? 'selected="selected"' : '') . '>_self</option>
            <option value="_parent" ' .($forum['target'] == '_parent' ? 'selected="selected"' : '') . '>_parent</option>
            <option value="_top" ' .   ($forum['target'] == '_top'    ? 'selected="selected"' : '') . '>_top</option>
          </select></div>
</div>';

    $image = false;
    if(!empty($forum['image']) && !empty($forum['image_h']) && !empty($forum['image_w']))
    {
      $image = '<img src="'.ROOT_PATH.$this->img_path.$forum['image'].'?'.TIME_NOW.'" alt="" width="'.$forum['image_w'].'" height="'.$forum['image_h'].'" style="border:none" />';
    }
    echo '
      <div class="form-group">
  <label class="control-label col-sm-2">'.AdminPhrase('forum_upload_image').'</label><div class="col-sm-6">';
    if($image)
    {
      echo AdminPhrase('current_image').'<br />'.$image.'<br /><br />
      <input type="checkbox" class="ace" name="delete_image" value="1" /><span class="lbl"> '.AdminPhrase('delete_image') . '</span>';
    }
    else
    {
      echo AdminPhrase('no_image_uploaded');
    }
    echo '</div>
	</div>
    <div class="form-group">
  <label class="control-label col-sm-2">'.AdminPhrase('upload_new_image').'</label><div class="col-sm-6">
          <input id="image" name="forum_image" type="file" size="30" /><br />';
    if(!is_writable(ROOT_PATH.$this->img_path))
    {
      echo '<span style="color:#FF0000"><strong>'.AdminPhrase('note_folder_not_writable').' '.$this->img_path.'</strong></span><br />';
    }
    echo '<span class="helper-text">
          Images are stored under "'.$this->img_path.'" with a filename like "'.$this->img_prefix.'xxx.ext"
          with "xxx" being the forum id and "ext" the image extension.</span>
</div>
</div>
     <div class="form-group">
  <label class="control-label col-sm-2">'.AdminPhrase('lbl_description').' (HTML)</label><div class="col-sm-6">';

    PrintWysiwygElement('description', htmlspecialchars($forum['description']), 8, 80);

    echo '</div>
      </div>
      <div class="form-group">
  <label class="control-label col-sm-2">' . AdminPhrase('forum_meta_keywords') . '</label><div class="col-sm-6">
       <textarea class="form-control" name="metakeywords">' . $forum['metakeywords'] . '</textarea>
	</div>
</div>
        <div class="form-group">
  <label class="control-label col-sm-2">' . AdminPhrase('forum_meta_description') . '</label><div class="col-sm-6">
       <textarea class="form-control" name="metadescription">' . $forum['metadescription'] . '</textarea></div>
	</div>
	<div class="form-group">
  <label class="control-label col-sm-2">Delete Forum?</label><div class="col-sm-6">' . IMAGE_DELETE . '<inputtype="checkbox" class="ace" name="delete_forum" value="1" /><span class="lbl"> Delete Forum?</span></div></div>
     ';

    $this->DisplayForumPermissions(true, $forum);

    echo '
    <div class="center">
    <button type="submit" value="" class="btn btn-info"><i class="ace-icon fa fa-check"></i> ' . ($forum_id ? AdminPhrase('forum_update_forum') : AdminPhrase('forum_insert_forum')).'
    </div>
    </form>
    ';

  } //DisplayForumForm

  // ##########################################################################

  function DisplayForums($parent_id, $forum_id, $parent_title, $is_invalid=false)
  {
    global $DB, $refreshpage;

    $forums_tbl = $this->forums_tbl;
    $topics_tbl = $this->topics_tbl;
    $posts_tbl  = $this->posts_tbl;
    $forum_id   = (int)$forum_id;

    if($is_invalid)
    {
      $DB->query("UPDATE $forums_tbl ff SET parent_forum_id = 0 WHERE forum_id = %d",$forum_id);
      $get_forums = $DB->query("SELECT ff.* FROM $forums_tbl ff WHERE forum_id = %d",$forum_id);
    }
    else
    if(empty($parent_id))
    {
      $get_forums = $DB->query("SELECT ff.* FROM $forums_tbl ff
        WHERE ff.parent_forum_id = 0 AND forum_id = $forum_id AND ff.is_category = 0
        ORDER BY ff.display_order, title");
    }
    else
    {
      $parent_id = (int)$parent_id;
      $get_forums = $DB->query("SELECT
        concat(
          lpad(IF(ff.parent_forum_id <> $parent_id, fp.display_order, ff.display_order),4,'0'),
          lpad(IF(ff.parent_forum_id <> $parent_id, fp.forum_id, ff.forum_id),6,'0')) sortorder,
        (SELECT COUNT(*) FROM $forums_tbl ff2 WHERE ff2.parent_forum_id = ff.forum_id) subforums,
        ff.*, fp.forum_id parent_id
        FROM $forums_tbl ff
        LEFT JOIN $forums_tbl fp on fp.forum_id = ff.parent_forum_id
        WHERE ff.parent_forum_id = $parent_id AND ff.is_category = 0
        ORDER BY sortorder, subforums DESC");
    }
    $forum_count = $DB->get_num_rows($get_forums);

    if(!$forum_count)
    {
      echo '
        <tr>
          <td class="center" colspan="5">
            No forums listed for this category.
          </td>
        </tr>';
    }
    else
    {
      global $forum_config;

      $status_online = AdminPhrase('status_online',true) ? AdminPhrase('status_online') : 'Online';
      $status_offline = AdminPhrase('status_offline',true) ? AdminPhrase('status_offline') : 'Offline';
      $do_header = true;

      while($forum_arr = $DB->fetch_array($get_forums,null,MYSQL_ASSOC))
      {
		  
		 // Display sub-forums of current forum
		 $subforums = '';
        if(!empty($forum_arr['subforums']))
        {
          $subforums .= '&nbsp;<div class="btn-group mytooltip" title="'.AdminPhrase('subforums').'">
		  			<a  class="" data-toggle="dropdown" href="#">
						<span  class="ace-icon fa fa-caret-down bigger-110"></span>
					</a>
					<ul class="dropdown-menu dropdown-info">';
          if($get_subforums = $DB->query(
             'SELECT forum_id, title, description, image, image_w, image_h'.
             " FROM $forums_tbl ff".
             ' WHERE parent_forum_id = %d'.
             ' ORDER BY display_order, title',
             $forum_arr['forum_id']))
          {
            while($sub = $DB->fetch_array($get_subforums))
            {
              $descr = strip_tags(str_replace(array('<br>','<br />'),array(' ',' '), $sub['description']));
              $sublink = $refreshpage.'&amp;action=display_forum_form&amp;sub=1&amp;forum_id='.$sub['forum_id'].PrintSecureUrlToken();
              if(strlen($output)) $output .= ', ';
              $subforums .= '<li><a class="mytooltip" title="'.htmlspecialchars($descr,ENT_COMPAT).'" href="'.$sublink.'"><i class="ace-icon fa fa-file-o"></i>'.
                              '&nbsp;' . strip_tags($sub['title']) . '</a></li>';

            } //while
          }
          $subforums .= '</ul></div>';
        }
     
        $tmod_count = $DB->query_first(
          "SELECT COUNT(*) modcount FROM $topics_tbl".
          ' WHERE forum_id = %d'.
          ' AND IFNULL(moderated,0) = 1',
          $forum_arr['forum_id']);
        $pmod_count = $DB->query_first(
          "SELECT COUNT(DISTINCT(p.post_id)) modcount FROM $posts_tbl p".
          " INNER JOIN $topics_tbl t ON p.topic_id = t.topic_id".
          ' WHERE t.forum_id = %d'.
          ' AND IFNULL(p.moderated,0) = 1',
          $forum_arr['forum_id']);

        $online  = $forum_arr['online']?true:false;
        $link    = $refreshpage.'&amp;action=display_forum_form&amp;forum_id='.$forum_arr['forum_id'].PrintSecureUrlToken();
        $style   = $is_invalid?'danger':'';
        $is_root = !empty($forum_arr['is_category']) && empty($forum_arr['parent_form_id']);
        $image = '';
					   
        echo '
        <tr class="'.$style.'">
          <td class="center">
            <input type="hidden" name="forum_id_arr[]" value="'.$forum_arr['forum_id'].'" />
			<input type="text" class="form-control" class="form-control" name="display_order_arr[]" value="'. (string)$forum_arr['display_order'].'" size="3" maxlength="5" />
          </td>
          <td>
		  	
              <a class="mytooltip" title="'.
                htmlspecialchars(AdminPhrase('lbl_edit_forum'),ENT_COMPAT).
                '" href="'.$link.
                '"><h5 class="lighter no-padding-top no-margin-top"> - - <i class="ace-icon fa fa-file-o"></i>&nbsp;'.htmlspecialchars(strip_alltags($forum_arr['title'])).
                '</a>' . $subforums .'</h5>';


        if($is_invalid)
        {
          echo '<br /><strong>'.AdminPhrase('err_reset_invalid_parent').'</strong>';
        }
       

        $descr = trim(htmlspecialchars(strip_alltags($forum_arr['description'])));
        if(strlen($descr))
        {
          echo '<span>'.$descr.'</span>';
        }
		
		echo '<div class="align-right no-padding-bottom no-margin-bottom">';
		
		//SD351: display count of moderated topics/posts
        if(!empty($tmod_count['modcount']) || !empty($pmod_count['modcount']))
        {
          $link = $forum_config->RewriteForumLink($forum_arr['forum_id']);
          if(!empty($tmod_count['modcount']))
          {
            $link2 = $link.((strpos($link,'?')===false)?'?':'&amp;') . 'forum_action=mod_topics';
            echo '<a href="'.$link2.'" target="_blank" class="mytooltip tooltip-warning" title="'.AdminPhrase('moderated_topics').' '.$tmod_count['modcount'].'"><i class="ace-icon fa fa-warning orange bigger-120"></i></a>&nbsp;&nbsp;';
            
          }
          if(!empty($pmod_count['modcount']))
          {
            $link2 = $link.((strpos($link,'?')===false)?'?':'&amp;') . 'forum_action=mod_posts';
            echo '<a href="'.$link2.'" target="_blank" class="mytooltip tooltip-warning" title="'.AdminPhrase('moderated_posts').' '.$pmod_count['modcount'].'"><i class="ace-icon fa fa-comment-o orange bigger-120"></i></a>&nbsp;&nbsp;</a>';
          }
        }

		if(!empty($forum_arr['enable_likes']))
        {
          echo '<i class="mytooltip tooltip-info ace-icon fa fa-thumbs-up bigger-120 blue" title="'.htmlspecialchars(AdminPhrase('likes_enabled'),ENT_COMPAT).'"></i>';
        }
		
		echo '</div>
          </td>
          <td class="td3" width="60">
            <div class="status_switch">
              <input type="hidden" name="status_arr[]" value="'.($online ? '1' : '0').'" />
              <a onclick="javascript:;" class="status_link on btn btn-success btn-sm"  style="display: '.( $online ? 'block': 'none').'">'.$status_online .'</a>
              <a onclick="javascript:;" class="status_link off btn btn-danger btn-sm" style="display: '.(!$online ? 'block': 'none').'">'.$status_offline.'</a>
            </div>
          </td>
          <td class="center">
            <a onclick="return confirm(forum_delete_prompt);" href="'.$refreshpage.
            '&amp;action=delete_forum&amp;forum_id=' . $forum_arr['forum_id'] . SD_URL_TOKEN.'"><i class="ace-icon fa fa-trash-o red bigger-120"></i></a>
          </td>
          <td class="td3" align="center" '.$style.'>
            <a href="'.$refreshpage.'&amp;action=display_forum_permissions'.
            '&amp;forum_id='.$forum_arr['forum_id'].SD_URL_TOKEN.'"><i class="ace-icon fa fa-key orange bigger-120"></i></a>
          </td>
        </tr>';
      }

    }

  } //DisplayForums

  // ##########################################################################

  function DisplayForumCategories()
  {
    global $DB, $refreshpage;

    $get_forums = $DB->query('SELECT ff.*, ff2.forum_id father, ff3.forum_id grandfather
                              FROM '.$this->forums_tbl.' ff
                              LEFT JOIN '.$this->forums_tbl.' ff2 ON ff2.forum_id = ff.parent_forum_id
                              LEFT JOIN '.$this->forums_tbl.' ff3 ON ff3.forum_id = ff2.parent_forum_id
                              WHERE (ff.is_category = 1) OR (ff.parent_forum_id = 0)
                                 OR (ff.parent_forum_id > 0 AND ff2.forum_id IS NULL)
                                 OR (ff2.parent_forum_id > 0 AND ff3.is_category = 0 AND ff3.forum_id > 0)
                              ORDER BY IF(ff.parent_forum_id=0 AND ff.is_category=0,0,1), ff.display_order, ff.title');

    if(!$DB->get_num_rows($get_forums))
    {
      DisplayMessage(AdminPhrase('message_no_forums'));
      return;
    }

    echo '
    <script type="text/javascript">
    // <![CDATA[
    var forum_delete_prompt = "'.htmlspecialchars(AdminPhrase('forum_delete_prompt'),ENT_COMPAT).'";
    // ]]>
    </script>';

    while($forum_arr = $DB->fetch_array($get_forums,null,MYSQL_ASSOC))
    {
      $is_invalid = (!empty($forum_arr['parent_forum_id']) && !isset($forum_arr['father']));
      if( $is_invalid || (empty($forum_arr['parent_forum_id']) && empty($forum_arr['is_category'])) )
      {
        $this->DisplayForums(0, $forum_arr['forum_id'], $forum_arr['title'], $is_invalid);
        continue;
      }

      StartSection(AdminPhrase('forum_category').' &raquo; '.strip_tags($forum_arr['title']));

      echo '
      <form method="post" action="'.$refreshpage.'&amp;action=update_forums">
      '.PrintSecureToken();

      echo '
      <table class="table table-bordered table-striped" summary="layout">
	  <thead>
      <tr>
        <th class="td1" width="80" align="center">'.AdminPhrase('lbl_display_order').'</th>
        <th class="td1">'.AdminPhrase('lbl_category_title').'</th>
        <th class="td1" width="100" align="center">'.AdminPhrase('lbl_status').'</th>
        <th class="td1" width="50" align="center">'.AdminPhrase('lbl_delete').'</th>
        <th class="td1" width="75" align="center">'.AdminPhrase('plugins_permissions').'</th>
      </tr>
	  </thead>
	  <tbody>';

      $status_online = AdminPhrase('status_online',true) ? AdminPhrase('status_online') : 'Online';
      $status_offline = AdminPhrase('status_offline',true) ? AdminPhrase('status_offline') : 'Offline';
      $online  = $forum_arr['online']?true:false;
      $link    = $refreshpage.'&amp;action=display_forum_form&amp;forum_id='.$forum_arr['forum_id'].PrintSecureUrlToken();
      $style   = '';

      $image = '';
      if(!empty($forum_arr['image']) && !empty($forum_arr['image_h']) && !empty($forum_arr['image_w']))
      {
        $image = '<img src="'.ROOT_PATH.$this->img_path.$forum_arr['image'].'?'.TIME_NOW.'" alt="" width="'.$forum_arr['image_w'].'" height="'.$forum_arr['image_h'].'"/>';
      }
      $image .= ' (ID: '.$forum_arr['forum_id'].')';
      echo '
      <tr class="'.$style.'">
        <td class="center">
          <input type="hidden" name="forum_id_arr[]" value="'.$forum_arr['forum_id'].'" />
          <input type="text" class="form-control" class="form-control" name="display_order_arr[]" value="'.$forum_arr['display_order'].'" />
        </td>
        <td>
          <a  class="mytooltip" title="'.
          htmlspecialchars(AdminPhrase('lbl_edit_category'),ENT_COMPAT).'" href="'.$link.'"><h5 class="lighter">
		  <i class="ace-icon fa fa-file-o"></i>'.
          '&nbsp;' . strip_tags($forum_arr['title']) . "</h5></a>";

      if(strlen($forum_arr['description']))
      {
        echo '<span>'.$forum_arr['description'].'</span>';
      }
      echo '
        </td>
        <td class="center" width="60">
            <input type="hidden" name="status_arr[]" value="'.($online ? '1' : '0').'" />
            <a onclick="javascript:;" class="status_link on btn btn-success btn-sm"  style="display: '.( $online ? 'block': 'none').'">'.$status_online .'</a>
            <a onclick="javascript:;" class="status_link off btn btn-danger btn-sm" style="display: '.(!$online ? 'block': 'none').'">'.$status_offline.'</a>
          
        </td>
        <td class="center">
          <a onclick="return confirm(forum_delete_prompt);" href="'.$refreshpage.
          '&amp;action=delete_forum&amp;forum_id=' . $forum_arr['forum_id'] . SD_URL_TOKEN.'"><i class="ace-icon fa fa-trash-o red bigger-120"></i></a>
        </td>
        <td class="center"><a href="'.$refreshpage.'&amp;action=display_forum_permissions&amp;forum_id=' .
        $forum_arr['forum_id'] . SD_URL_TOKEN . '"><i class="ace-icon fa fa-key orange bigger-120"></i></a></td>
      </tr>';

      $this->DisplayForums($forum_arr['forum_id'], 0, $forum_arr['title']);

      echo '
       
      </table>
	  </div>
      <div class="center">
	  	<button type="submit" class="btn btn-info" value=""><i class="ace-icon fa fa-check"></i> '.AdminPhrase('update_category').'</button>
      </form>
	  <div class="space-20"></div>';

    } //while

  } //DisplayForumCategories

} // end of class


// ############################################################################
// FORUM ACTION
// ############################################################################

$ForumSettings = new ForumSettings();
if(($action=='display_forums') || ($action=='display_statistics'))
{
  $forum_config = new SDForumConfig(); //SD351
  $forum_config->InitFrontpage();
}

switch($action)
{
  case 'display_forum_form':
    $forum_id = GetVar('forum_id', 0, 'whole_number', false, true);
    $ForumSettings->DisplayForumForm($forum_id);
  break;

  case 'save_forum':
    $ForumSettings->SaveForum();
  break;

  case 'display_forums':
    $ForumSettings->DisplayForumCategories();
  break;

  case 'display_settings':
    $ForumSettings->DisplaySettings();
  break;

  // SD313: new action
  case 'display_statistics':
  case 'update_statistics':
    $ForumSettings->DisplayStatistics($action == 'update_statistics');
  break;

  case 'update_forums':
    $ForumSettings->UpdateForums();
  break;

  case 'delete_forum':
    $ForumSettings->DeleteForum();
  break;

  case 'display_forum_permissions':
    $ForumSettings->DisplayForumPermissions();
  break;

  case 'update_forum_permissions':
    $ForumSettings->UpdateForumPermissions();
  break;
}
