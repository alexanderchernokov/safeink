<?php

// DEFINE CONSTANTS
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');

// INIT PRGM
include(ROOT_PATH . 'includes/init.php');

if(isset($_GET['dologin']))
{
  DisplayAdminLogin();
}
else
{
  // REDIRECT PAGE
  header('Location: ' . ADMIN_DEFAULT_PAGE);
}

$DB->close();
exit();
