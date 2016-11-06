<?php

define('IN_PRGM', true);
define('ROOT_PATH', '../../');




// ###############################################################################
// INIT PRGM
// ###############################################################################

include(ROOT_PATH . 'includes/init.php');



// ###############################################################################
// CHECK ACCESS
// ###############################################################################

if(empty($userinfo['pluginviewids'])  && !@in_array(17,$userinfo['pluginviewids']))
{
  echo $sdlanguage['no_view_access'];

  $DB->close();
  exit();
}


// ###############################################################################
// GET VARS
// ###############################################################################

$categoryid = GetVar('categoryid', 0, 'whole_number');
$sectionid  = GetVar('p17_sectionid', 0, 'whole_number');
$imageid    = GetVar('p17_imageid', 0, 'whole_number');

if(!$categoryid OR !$sectionid OR !$imageid)
{
  $DB->close();
  exit();
}



// ###############################################################################
// LOAD LANGUAGE AND SETTINGS
// ###############################################################################

$p17_language = GetLanguage(17);
$p17_settings = GetPluginSettings(17);



// ###############################################################################
// GET IMAGE INFO
// ###############################################################################

if(!$image = $DB->query_first('SELECT * FROM {p17_images} WHERE imageid = %d AND
    sectionid = %d AND activated = 1', $imageid, $sectionid))
{
  $DB->close();
  exit();
}

$DB->query('UPDATE {p17_images} SET viewcount = viewcount + 1 WHERE imageid = %d', $imageid);

// get sort type of current section
$getsorttype = $DB->query_first('SELECT sorting FROM {p17_sections} WHERE sectionid = %d', $sectionid);
$sorttype    = $getsorttype['sorting'];

// get sorting for images
switch($sorttype)
{
  case 'Alphabetically A-Z':
  $order = 'title ASC, imageid ASC';
  break;

  case 'Alphabetically Z-A':
  $order = 'title DESC, imageid DESC';
  break;

  case 'Author Name A-Z':
  $order = 'author ASC, imageid ASC';
  break;

  case 'Author Name Z-A':
  $order = 'author DESC, imageid DESC';
  break;

  case 'Oldest First':
  $order = 'datecreated ASC, imageid ASC';
  break;

  //SD343: make this default to avoid SQL error
  //case 'Newest First':
  default:
  $order = 'datecreated DESC, imageid DESC';
  break;
}

// fetch list of all images in this list
$imagelist = array();
if($getimagelist = $DB->query('SELECT imageid FROM {p17_images} WHERE sectionid = %d AND activated = 1 ORDER BY ' . $order, $sectionid))
{
  while($img = $DB->fetch_array($getimagelist))
  {
	  $imagelist[] = $img[0];
  }
}

// Find where the current image is in the list
$currentID = array_search($image['imageid'], $imagelist);

$previousimagelink = '';
$nextimagelink = '';

if($currentID > 0)
{
	$previousimagelink = '<a href="./popup.php?categoryid=' . $categoryid . '&p17_sectionid=' . $sectionid . '&p17_imageid=' . $imagelist[$currentID - 1] . '">' . $p17_language['previous_image'] . '</a>';
}

if($currentID < count($imagelist)-1)
{
	$nextimagelink = '<a href="./popup.php?categoryid=' . $categoryid . '&p17_sectionid=' . $sectionid . '&p17_imageid=' . $imagelist[$currentID + 1] . '">' . $p17_language['next_image'] . '</a>';
}



// ###############################################################################
// DISPLAY IMAGE
// ###############################################################################

echo '<html>
      <head>
        <title>' . $image['title'] . '</title>
        <meta http-equiv="Content-Type" content="text/html;charset=' . CHARSET . '" />
      </head>
      <body>

      <center>' . $image['title'] . '</center><br />

      <table cellpadding="0" cellspacing="0" width="100%">
      <tr>
        <td colspan="2" style="text-align:center">
          <a href="javascript:self.close();"><img alt="' . htmlspecialchars($image['title'], ENT_QUOTES) . '" src="';

if($p17_settings['save_fullsize_images'] || !file_exists(ROOT_PATH . 'plugins/p17_image_gallery/images/md_' . $image['filename']))
{
	echo $sdurl . 'plugins/p17_image_gallery/images/' . $image['filename'];
}
else
{
	echo $sdurl . 'plugins/p17_image_gallery/images/md_' . $image['filename'];
}

echo '" border="0" /></a>
        </td>
      </tr>
      <tr>
        <td style="padding-top: 7px; padding-right: 20px;" align="left">' .
          (empty($previousimagelink) ? '&nbsp;' : $previousimagelink) . '
        </td>
        <td style="padding-top: 7px;" align="right">' .
          (empty($nextimagelink) ? '&nbsp;' : $nextimagelink) . '
        </td>
      </tr>
      </table>';

if(strlen($image['description']))
{
  echo '<br />';
  echo nl2br($image['description']);
}

if(!empty($image['showauthor']))
{
  if(strlen($image['description']))
  {
    echo '<br /><br />';
  }

  echo $p17_language['submitted_by'] . ' ' . $image['author'];
}

echo '</body>
      </html>';

?>