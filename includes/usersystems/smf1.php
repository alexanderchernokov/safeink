<?php

if(!defined('IN_PRGM')) return;

// ########################### SPECIAL INCLUDE CHECK ###########################

if(!defined('SD_SMF_INCLUDED'))
{
  define('SD_SMF_INCLUDED', true);

  $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr(htmlentities(strip_tags($_SERVER['HTTP_USER_AGENT'])),0,255) : '';
  define('USER_AGENT', $user_agent);

  // check which version of smf is the user running
  $smfversion = $DB->query_first('SELECT value FROM ' . $usersystem['tblprefix'] .
    'settings WHERE variable = \'smfVersion\'');

  if(substr($smfversion['value'], 0, 3) == '1.1')
  {
    // load the newer file
    include(ROOT_PATH. 'includes/usersystems/smf11.php');
  }
  else
  {
    // load the older file (1.0)
    include(ROOT_PATH. 'includes/usersystems/smf10.php');
  }

  unset($smfversion);
}

?>