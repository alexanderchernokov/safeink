<?php
define('IN_PRGM', true);
define('ROOT_PATH', '../../');

unset($usersettings, $userinfo);
@require(ROOT_PATH.'includes/init.php');
@require(ROOT_PATH.'includes/class_sd_media.php');

if(Is_Ajax_Request() || (!$plugin_folder = sd_GetCurrentFolder(__FILE__)))
{
  $DB->close();
  exit();
}

@require(ROOT_PATH.'plugins/'.$plugin_folder.'/gallery_lib.php');
$GalleryBase = new GalleryBaseClass($plugin_folder);

if($GalleryBase->pluginid < 1) return false;
$pluginid = $GalleryBase->pluginid;
$pre = 'p'.$pluginid;

if(!isset($plugin_names[$pluginid]))
{
  $DB->close();
  exit();
}

$gallery_allow_mod = !empty($userinfo['adminaccess']) ||
                     (!empty($userinfo['pluginadminids']) && @in_array($pluginid, $userinfo['pluginadminids'])) ||
                     (!empty($userinfo['pluginmoderateids']) && @in_array($pluginid, $userinfo['pluginmoderateids']));
if(!$gallery_allow_mod && empty($userinfo['pluginviewids']) && !@in_array($pluginid,$userinfo['pluginviewids']))
{
  echo $sdlanguage['no_view_access'];
  $DB->close();
  exit();
}

// ############################# CHECK PARAMS #################################

$categoryid = Is_Valid_Number(GetVar('categoryid', 0, 'whole_number'),0,1,999999);
$sectionid  = Is_Valid_Number(GetVar($GalleryBase->pre.'_sectionid', 0, 'whole_number'),0,1,999999);
$imageid    = Is_Valid_Number(GetVar($GalleryBase->pre.'_imageid', 0, 'whole_number'),0,1,9999999);
if(empty($categoryid) || empty($sectionid) || empty($imageid))
{
  header("HTTP/1.0 404 Not Found");
  header('Location: '.$sdurl);
  $DB->close();
  exit();
}

// Initialise gallery base and exit if error
$GalleryBase->seo_redirect = false;
if($tmp = $GalleryBase->InitFrontpage(true))
{
  if(!$GalleryBase->SetImageAndSection($imageid))
  {
    $DB->close();
    exit();
  }
}

if(!$tmp || empty($GalleryBase->section_arr['activated']))
{
  echo $GalleryBase->language['section_offline_message'];
  return;
}

$folder = $GalleryBase->IMAGEDIR;
$folder_url = $GalleryBase->IMAGEURL;

// ############################# GET IMAGE INFO ###############################

$image = & $GalleryBase->image_arr;

// Update view count for non-admins/moderators
if(!$gallery_allow_mod && !SD_IS_BOT)
{
  $DB->query('UPDATE '.$GalleryBase->images_tbl.
             ' SET viewcount = IFNULL(viewcount,0) + 1'.
             ' WHERE imageid = '.(int)$imageid);
}

// get sort type of current section
$order = 'datecreated DESC, imageid DESC';
$sorttype = $GalleryBase->section_arr['sorting'];
switch($sorttype)
{
  case 'alpha_az':
  $order = 'title ASC, imageid ASC';
  break;

  case 'alpha_za':
  $order = 'title DESC, imageid DESC';
  break;

  case 'author_name_az':
  $order = 'author ASC, imageid ASC';
  break;

  case 'author_name_za':
  $order = 'author DESC, imageid DESC';
  break;

  case 'oldest_first':
  $order = 'datecreated ASC, imageid ASC';
  break;
}

// fetch list of all images in this list
$imagelist = array();

if($getimagelist = $DB->query('SELECT imageid FROM '.$GalleryBase->images_tbl.
                              ' WHERE sectionid = %d AND activated = 1'.
                              ' ORDER BY ' . $order, $sectionid))
{
  while($img = $DB->fetch_array($getimagelist,null,MYSQL_ASSOC))
  {
	  $imagelist[] = $img['imageid'];
  }
}

// Find where the current image is in the list
$currentID = array_search($image['imageid'], $imagelist);

$previousimagelink = '';
$nextimagelink = '';
$pluginfolder  = sd_GetCurrentFolder(__FILE__); //SD343

if($currentID > 0)
{
	$previousimagelink = '<a rel="nofollow" href="'.$sdurl.'plugins/'.$pluginfolder.'/mediapopup.php?categoryid=' . $categoryid . '&'.$pre.'_sectionid=' . $sectionid . '&'.$pre.'_imageid=' . $imagelist[$currentID - 1] . '">' . $GalleryBase->language['previous_image'] . '</a>';
}

if($currentID < count($imagelist)-1)
{
	$nextimagelink = '<a rel="nofollow" href="'.$sdurl.'plugins/'.$pluginfolder.'/mediapopup.php?categoryid=' . $categoryid . '&'.$pre.'_sectionid=' . $sectionid . '&'.$pre.'_imageid=' . $imagelist[$currentID + 1] . '">' . $GalleryBase->language['next_image'] . '</a>';
}

// ############################# DISPLAY IMAGE #################################

echo
'<html>
<head>
<title>' . $image['title'] . '</title>
<meta http-equiv="Content-Type" content="text/html;charset='.$sd_charset.'" />
<script type="text/javascript" src="'.ROOT_PATH.'includes/min/index.php?g=jq"></script>
<script type="text/javascript">
//<![CDATA[
var sdurl = "'.$sdurl.'";
jQuery("body").addClass("js_on");
//]]>
</script>
<link rel="stylesheet" href="'.$sdurl.'css.php" />
<script type="text/javascript" src="'.$sdurl.'includes/min/index.php?f=includes/javascript/markitup/markitup-full.js"></script>
</head>
<body>
<div class="'.$pre.'_popup_container">
<div class="'.$pre.'_popup_title"><h1>' . $image['title'] . '</h1></div>
<table cellpadding="4" cellspacing="0" width="100%">
';

//SD343: Media URL support
$mt = empty($image['media_type'])?0:(int)$image['media_type'];

if(!$mt && $GalleryBase->settings['image_navigation_links'] < 2)
{
  echo '
  <tr>
    <td class="'.$pre.'_nav_link_cell_left">'  .
    (!empty($previousimagelink) ? $previousimagelink : '&nbsp;') . '
    </td>
    <td class="'.$pre.'_nav_link_cell_right">
      ' .(!empty($nextimagelink) ? $nextimagelink : '&nbsp;') . '
    </td>
  </tr>';
}

echo '<tr>
  <td colspan="2" align="center">';

if($mt)
{
  echo SD_Media_Base::getMediaPreview($mt, $image['media_url'],
                       $GalleryBase->language['visit_media_site'],
                       600, 450, true, true);

}
else
{
  echo '<a href="javascript:self.close();"><img alt="' .
    htmlspecialchars($image['title'], ENT_QUOTES) . '" src="';

  if($GalleryBase->settings['save_fullsize_images'] || !file_exists(ROOT_PATH.$folder.$image['folder'].$GalleryBase->MD_PREFIX.$image['filename']))
  {
	  echo $folder_url.$image['folder'].$image['filename'];
  }
  else
  {
	  echo $folder_url.$image['folder'].$GalleryBase->MD_PREFIX. $image['filename'];
  }

  echo '" border="0" /></a>';
}
echo '</td>
  </tr>';

if(!$mt && in_array($GalleryBase->settings['image_navigation_links'], array(0,2)))
{
  echo '
  <tr>
    <td class="'.$pre.'_nav_link_cell_left">' . (empty($previousimagelink) ? '&nbsp;' : $previousimagelink) . '</td>
    <td class="'.$pre.'_nav_link_cell_right">'. (empty($nextimagelink)     ? '&nbsp;' : $nextimagelink) . '</td>
  </tr>';
}
echo '
  </table><br />
  <div style="text-align: center">
  ';

if(strlen($image['description']))
{
  // Apply BBCode parsing
  if($GalleryBase->allow_bbcode && isset($bbcode))
  {
    $bbcode->SetDetectURLs(false);
    $image['description'] = $bbcode->Parse($image['description']);
  }
  echo '<p class="'.$pre.'_description">'.($image['description']) . '</p>';
}

if(!empty($image['showauthor']) &&
   !empty($GalleryBase->section_arr['display_author']))
{
  echo '<p class="'.$pre.'_author">'.$GalleryBase->language['submitted_by'] . ' ' . $image['author'] . '</p>';
}

if(!empty($GalleryBase->settings['display_view_count']))
{
  echo '<p class="'.$pre.'_views">'.$image['viewcount'] . ' '.
        $sdlanguage[$image['viewcount']==1?'common_view':'common_views'] . '</p>';
}

if(!empty($GalleryBase->settings['enable_comments']) &&
   !empty($GalleryBase->section_arr['display_comments']))
{
  $Comments->plugin_id = $GalleryBase->pluginid;
  $Comments->object_id = $image['imageid'];
  $Comments->url = $sdurl . $GalleryBase->PLUGINDIR.'mediapopup.php?categoryid='.$categoryid.
                   '&amp;'.$pre.'_sectionid='.$image['sectionid'].
                   '&amp;'.$pre.'_imageid=' . $image['imageid'];
  $Comments->isArticle = true; // <- this prevents rewrite!
  $Comments->DisplayComments();
}

echo '
</div></div>
</body>
</html>';