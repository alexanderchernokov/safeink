<?php
if(!defined('IN_PRGM')) exit();

// Add an extra main setting for skins
$mainsettings['skinheader'] =
(defined('SITE_URL') ? '<base href="' . SITE_URL . '" /><!--[if IE]></base><![endif]-->':'') . '
  ' . $rss_link . '
<script type="text/javascript" src="'.(defined('JQUERY_GA_CDN') && strlen(JQUERY_GA_CDN)?JQUERY_GA_CDN:SITE_URL.'includes/javascript/'.JQUERY_FILENAME).'"></script>
<script type="text/javascript" src="'.SITE_URL.'includes/javascript/jquery-migrate-1.2.1.min.js"></script>
<script type="text/javascript">
<!--
 var TMenu_path_to_files="'.SITE_URL.'includes/javascript/hovermenu/";
//-->
</script>
<script type="text/javascript" src="includes/javascript/skin_functions.js"></script>
<script type="text/javascript" src="includes/javascript/hovermenu/menu.js"></script>
' . $ExtraHeader /* SD313: do NOT remove/change! */ . "\r\n";

$errormsg = false;
if(empty($design['designpath']))
{
  $errormsg = '<strong>Error: The specified URL could not be found.</strong><br />
  The requested page may have been deleted.<br /><br />
  <a href="index.php">Click here to go to the home page.</a>';
}
else
if(!file_exists(ROOT_PATH.'skins/'.$design['designpath']))
{
  $errormsg = "Failed to load the files for skin <strong>'" . $design['skinname'] . "' . </strong><br /><br />
  The file '" . 'skins/'.$design['designpath']. "' does not exist.<br /><br />
  <a href='index.php'>Click here to load the home section.</a>";
}
else
if(!include(ROOT_PATH.'skins/'.$design['designpath']))
{
  $errormsg = 'Failed to load the file: <strong>' . $design['designpath'] . '</strong><br /><br />
  <a href="index.php">Click here to load the home section.</a>';
}

if($errormsg)
{
  DisplayMessage($errormsg, true);
}
