<?php
/*
Based on: ColorRating (c) 2009 Jack Moore - jack@colorpowered.com.
          Released under the MIT License.

*** Special adaptation for use in Subdreamer (requires SD 3.2.x!)

*** Rating is ONLY enabled for non-banned, logged-in users!

*** For usage within a SD plugin see function "PrintRatingForm" in file
*** "functions_global.php" for further instructions!

Translatable phrases are to be found under Main Website Phrases.

*/

// ############################################################################
// IF NOT CALLED FROM INSIDE SD ASSUME AN AJAX CALL
// Is_Ajax_Request defined in functions_global.php!
// ############################################################################

if(defined('IN_PRGM')) return true;

define('IN_PRGM', true);
define('ROOT_PATH', '../');

if(!@require(ROOT_PATH . 'includes/init.php')) exit();
if(!Is_Ajax_Request()) exit();

header('Cache-Control: no-cache'); // Prevent IE from caching XMLHttpRequest response

// USERIP (is set within session.php) and $userinfo are required!
if(defined('USERIP') && isset($userinfo) &&
   empty($userinfo['banned']) && !empty($userinfo['loggedin']) &&
   isset($_GET['update']) && isset($_GET['rating_id']))
{
  $pluginid = GetVar('pluginid', 0, 'whole_number', false, true);
  echo GetRatingForm($_GET['rating_id'], $pluginid, false);
}
