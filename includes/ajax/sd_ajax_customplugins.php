<?php
if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
   ($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')) exit();

define('IN_PRGM', true);
define('ROOT_PATH', '../../');
@require(ROOT_PATH . 'includes/init.php');

// Disable error reporting
@error_reporting(0);
$sd_ignore_watchdog = true;

// Set default return message to be a failure:
$content = $sdlanguage['ajax_operation_failed'];

// Check for specific action to return displayable data
$cat_id = Is_Valid_Number(GetVar('catid', 0, 'whole_number', false, true),0,1,999999);
$cid    = Is_Valid_Number(GetVar('cid',   0, 'whole_number', false, true),0,1,999999);
$page   = Is_Valid_Number(GetVar('page',  0, 'whole_number', false, true),0,1,9999);

if($cat_id && $cid && $page)
{
  $HasAccess = !empty($userinfo['adminaccess']) ||
               ( (!empty($userinfo['categoryviewids']) &&
                  @in_array($cat_id, $userinfo['categoryviewids'])) &&
                 (!empty($userinfo['maintain_customplugins']) ||
                  (!empty($userinfo['custompluginviewids']) &&
                   @in_array($cid, $userinfo['custompluginviewids']))));
  if($HasAccess)
  {
    $current_page_url = RewriteLink('index.php?categoryid='.$cat_id);
    $content = sd_PaginateCustomPlugin(null, $current_page_url, $cid, $page, $haspages);
  }
}

header("Content-Type: text/html; charset=".SD_CHARSET);

print $content;

$DB->close();
exit();