<?php
// ############################################################################
// DEFINE CONSTANTS
// ############################################################################
define('ROOT_PATH', '../');

// ############################################################################
// LOAD CONFIG DATA
// ############################################################################
if(is_file(ROOT_PATH . 'includes/config.php'))
{
  include(ROOT_PATH . 'includes/config.php');
}
else
{
  // user is installing
  header("location: install.php");
}

// ############################################################################
// REDIRECT TO UPGRADE OR INSTALL
// ############################################################################
if(defined('PRGM_INSTALLED') OR defined('SD_INSTALLED'))
{
  // user is upgrading
  header("location: upgrade.php");
}
else
{
  // user is installing
  header("location: install.php");
}

exit;
