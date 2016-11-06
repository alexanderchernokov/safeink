<?php

define('IN_PRGM', true);
define('ROOT_PATH', '../../');

// ########################### LOAD SUBDREAMER CORE ############################
require(ROOT_PATH . 'includes/init.php');

$pluginid = GetPluginID('Chatterbox');
if(empty($pluginid))
{
  $DB->close();
  exit();
}

// ###################### CHECK CHATTERBOX PERMISSIONS #########################

if(empty($userinfo['pluginviewids']) || !@in_array($pluginid, $userinfo['pluginviewids']))
{
  echo $sdlanguage['no_view_access'];
  $DB->close();
  exit();
}

// ######################### CHECK FOR INCORRECT DATA ##########################

$categoryid = GetVar('categoryid', 0, 'whole_number', false, true);
if(empty($categoryid))
{
  $DB->close();
  exit();
}

// ######################### GET CHATTERBOX SETTINGS ###########################

$settings = GetPluginSettings($pluginid);
$language = GetLanguage($pluginid);

// ################### SET LOCALE TIME AND HEADER INFORMATION ##################
header("Content-type: text/html; charset=$sd_charset");

// ############################## DISPLAY HISTORY ##############################

$limit = isset($settings['maximum_history_length']) ? $settings['maximum_history_length'] : 10;
$limit = empty($limit) ? '' : 'LIMIT ' . (int)$limit;

$printavatar = !empty($settings['display_avatar']);
if($printavatar)
{
  $img_h = intval($settings['avatar_image_height']);
  $img_w = intval($settings['avatar_image_width']);
  if(!empty($img_h) & ($img_h > 1))
  {
    $settings['avatar_image_height'] = ' height="'.$img_h.'" ';
  }
  else
  {
    $settings['avatar_image_height'] = '';
  }

  if(!empty($img_w) && ($img_w > 1))
  {
    $settings['avatar_image_width'] = ' width="'.($img_w).'" ';
  }
  else
  {
    $settings['avatar_image_width'] = '';
  }
}

// Config array as parameter for sd_PrintAvatar (in globalfunctions.php)
$avatar_conf = array(
  'output_ok'           => $printavatar,
  'userid'              => -1,
  'username'            => '',
  'Avatar Image Height' => $settings['avatar_image_height'],
  'Avatar Image Width'  => $settings['avatar_image_width'],
  'Avatar Column'       => $settings['avatar_column']
  );

echo '
<html>
<head>
  <title>'.$language['chatterbox_history'].'</title>
</head>
<body bgcolor="#B4BFD3">

<table width="95%" height="100%" border="0" cellspacing="2" cellpadding="0" align="center">
<tr>
  <td style="background-color: #FFFFFF;border: 1px solid #000000; padding: 4px;" valign="top">
  <table id="p7_chatterbox" border="0" cellpadding="0" cellspacing="0" width="100%" >
  ';

$getmessages = $DB->query("SELECT username, comment, datecreated FROM {p7_chatterbox} ".
  (!empty($settings['category_targeting'])?"WHERE categoryid = %d":'') . "
  ORDER BY commentid DESC " . $limit, $categoryid);

while($message = $DB->fetch_array($getmessages))
{
  $username = $message['username'];
  $comment  = $message['comment'];
  $avatar_conf['username'] = $username;

  if(!empty($settings['word_wrap']))
  {
    $username = sd_wordwrap($username, $settings['word_wrap'], "<br />", 1);
    $comment  = sd_wordwrap($comment,  $settings['word_wrap'], "<br />", 1);
  }

  echo '
  <tr>' . sd_PrintAvatar($avatar_conf) . '
  <strong>' . $username . '</strong><br />';

  if($settings['display_date'])
  {
    $dmask = $mainsettings['dateformat'];
    if(!empty($dmask) && strpos($dmask,':i:s'))
    {
      $dmask = str_replace('h:i:s','',$dmask);
      $dmask = str_replace('H:i:s','',$dmask);
      $dmask = str_replace(' A','',$dmask);
    }
    echo DisplayDate($message['datecreated'], $dmask . ' ' . $settings['time_format']) . '<br />';
  }

  echo $comment . '<br /><br />
    </td></tr>';

} //while

echo '</table>';

echo '
  </td>
</tr>
</table>
</body>
</html>';

?>