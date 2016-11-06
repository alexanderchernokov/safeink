<?php
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');

include(ROOT_PATH . 'includes/init.php');
$admin_phrases = LoadAdminPhrases(1);
CheckAdminAccess('pages');

// GET VARS
$action     = GetVar('action', 'display_plugin_selection', 'string');
$categoryid = GetVar('categoryid', 0, 'whole_number');
$designid   = GetVar('designid', 0, 'whole_number');
$mobile     = (!empty($_GET['mobile']) && SD_MOBILE_FEATURES?'1':'0'); //SD370

// DISPLAY FRAMSET
echo '<html>
<frameset rows="50px,*"  FRAMEBORDER=NO FRAMESPACING=0 BORDER=0>
  <frame noresize="noresize" name="page_plugin_selection_header" src="page_plugin_selection_header.php?mobile='.$mobile.'&action=' . $action . '&categoryid=' . $categoryid . '&designid=' . $designid . SD_URL_TOKEN . '" />
  <frame name="page_plugin_selection_content" src="page_plugin_selection_content.php?mobile='.$mobile.'&action=' . $action . '&categoryid=' . $categoryid . '&designid=' . $designid . SD_URL_TOKEN . '" />
</frameset>
</html>';
