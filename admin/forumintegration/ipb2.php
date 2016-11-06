<?php

if(!defined('IN_PRGM')) exit();

// ################################ SKIN FORUM #################################

function SkinForum($sdheader, $sdfooter)
{
/*
  global $DB, $usersystem;

  // don't skin a skinned forum or problems can happen, so lets start by restoring
  RestoreForumSkin();

  // ipb2 database prefix
  $forumprefix = $usersystem['tblprefix'];

  // create subdreamers header and footer
  $sdheader = "<!--StartSdHeader-->\n" . $sdheader . "\n<!--EndSdHeader-->\n<% GENERATOR %>";
  $sdfooter = "<!--StartSdFooter-->\n" . $sdfooter . "\n<!--EndSdFooter-->\n</body>";

  // get ipb2 wrapper (the html code that wraps around the forum) and then surround it with subdreamer's header and footer
  $getwrapper = $DB->query_first("SELECT set_wrapper, set_cache_wrapper FROM " . $forumprefix . "skin_sets WHERE set_default = 1");

  // select the wrapper thats filled, sometimes set_wrapper is filled and other times its set_cache_wrapper
  $wrapper = strlen($getwrapper['set_wrapper']) ? $getwrapper['set_wrapper'] : $getwrapper['set_cache_wrapper'];

  // now add subdreamers header and footer to the wrapper
  $wrapper = str_replace("<% GENERATOR %>", $sdheader, $wrapper);
  $wrapper = str_replace("</body>", $sdfooter, $wrapper);

  // we now need to make some corrections to the ipb2 css to get it working correctly with subdreamer skins
  // so lets first get the ipb2css
  $getcss = $DB->query_first("SELECT set_css, set_cache_css FROM " . $forumprefix . "skin_sets WHERE set_default = 1");

  // select the css thats filled, sometimes set_css is filled and other times its set_cache_css (just like the wrapper)
  $css = strlen($getcss['set_css']) ? $getcss['set_css'] : $getcss['set_cache_css'];

  //
  // now alter the css so that it can work with subdreamer's skins
  //

  // this fixes it so that subdreamer skins don't have default backgrounds of transparent
  $css = preg_replace("'background: transparent;'ms", "<!--StartSdCssBackground-->background: transparent;<!--EndSdCssBackground-->", $css, 1);

  // this fixes an issues with subdreamer's logo being cut off in IE
  $css = preg_replace("'line-height: 135%;'ms", "<!--StartSdCssLineHeight-->line-height: 135%;<!--EndSdCssLineHeight-->", $css, 2);

  // the following fix padding issues, make sure to only replace the first instance - Thanks to Robert Ellis for providing this solution
  $css = preg_replace("'table th,'ms", "div#ipbwrapper div table tr th,", $css, 1);

  // ipb2 steches all tables to 100%, lets have it change only ipb's tables - Thanks again to Robert Ellis for this solution
  $css = preg_replace("'table{'ms", "div#ipbwrapper div table, div#ipbwrapper table{", $css, 1);

  // the following makes the background always white, and we don't want that!
  // actually, this really shouldn't matter as long as we fixed the transparent issues at the top
  // this can be removed, but i'm still not 100%, it might cause problems so i'm leaving it in for now - I suggest testing to see if it can really be removed
  // EZ - As of 2.3.1 the following line creates problems.  Remming.
  //$css = preg_replace("'background: #FFF;'ms", "<!--StartSdBackground-->background: #FFF;<!--EndSdBackground-->", $css, 1);

  //
  // everything is complete, now update the wrapper and css
  //

  $DB->query("UPDATE " . $forumprefix . "skin_sets SET set_css           = '" . addslashes($css) . "',
                                                       set_wrapper       = '" . addslashes($wrapper) . "',
                                                       set_cache_css     = '" . addslashes($css) . "',
                                                       set_cache_wrapper = '" . addslashes($wrapper) . "'
                                                 WHERE set_default       = 1");
*/
} //SkinForum


// ############################ RESTORE FORUM SKIN #############################

function RestoreForumSkin()
{
/*
  global $DB, $usersystem;

  // ipb2 database prefix
  $forumprefix = $usersystem['tblprefix'];

  $sdwrapper = $DB->query_first("SELECT set_wrapper, set_cache_wrapper FROM " . $forumprefix . "skin_sets WHERE set_default = 1");

  if( (preg_match("'<!--StartSdHeader-->(.*)<!--EndSdHeader-->'ms", $sdwrapper['set_wrapper'])) OR (preg_match("'<!--StartSdFooter-->(.*)<!--EndSdFooter-->'ms", $sdwrapper['set_wrapper'])) )
  {
    $ipb2wrapper = preg_replace("'<!--StartSdHeader-->(.*)<!--EndSdHeader-->'ms", "", $sdwrapper['set_wrapper']);
    $ipb2wrapper = preg_replace("'<!--StartSdFooter-->(.*)<!--EndSdFooter-->'ms", "", $ipb2wrapper);
  }
  else if( (preg_match("'<!--StartSdHeader-->(.*)<!--EndSdHeader-->'ms", $sdwrapper['set_cache_wrapper'])) OR (preg_match("'<!--StartSdFooter-->(.*)<!--EndSdFooter-->'ms", $sdwrapper['set_cache_wrapper'])) )
  {
    $ipb2wrapper = preg_replace("'<!--StartSdHeader-->(.*)<!--EndSdHeader-->'ms", "", $sdwrapper['set_cache_wrapper']);
    $ipb2wrapper = preg_replace("'<!--StartSdFooter-->(.*)<!--EndSdFooter-->'ms", "", $ipb2wrapper);
  }

  $getsdcss = $DB->query_first("SELECT set_css, set_cache_css FROM " . $forumprefix . "skin_sets WHERE set_default = 1");

  $ipb2css = strlen($getsdcss['set_css']) ? $getsdcss['set_css'] : $getsdcss['set_cache_css'];

  $ipb2css = str_replace('<!--StartSdCssBackground-->', '', $ipb2css);
  $ipb2css = str_replace('<!--EndSdCssBackground-->',   '', $ipb2css);
  $ipb2css = str_replace('<!--StartSdCssLineHeight-->', '', $ipb2css);
  $ipb2css = str_replace('<!--EndSdCssLineHeight-->',   '', $ipb2css);
  $ipb2css = str_replace('<!--StartSdBackground-->',    '', $ipb2css);
  $ipb2css = str_replace('<!--EndSdBackground-->',      '', $ipb2css);
  $ipb2css = str_replace('<!--StartSd100Percent-->',    '', $ipb2css);
  $ipb2css = str_replace('<!--EndSd100Percent-->',      '', $ipb2css);
  $ipb2css = str_replace('div#ipbwrapper div table, div#ipbwrapper table{', 'table{', $ipb2css);
  $ipb2css = str_replace('div#ipbwrapper div table tr th,',   'table th,', $ipb2css);

  if(isset($ipb2wrapper))
  {
    $DB->query("UPDATE " . $forumprefix . "skin_sets SET set_css           = '" . addslashes($ipb2css) . "',
                                                         set_wrapper       = '" . addslashes($ipb2wrapper) . "',
                                                         set_cache_css     = '" . addslashes($ipb2css) . "',
                                                         set_cache_wrapper = '" . addslashes($ipb2wrapper) . "'
                                                   WHERE set_default = 1");
  }
*/
} //RestoreForumSkin


// ############################# GET FORUM SYSTEM ##############################

function GetForumSystem($forumfolderpath, $sddbname)
{
  global $DB;

  if(is_file(ROOT_PATH . $forumfolderpath . 'conf_global.php'))
  {
    include(ROOT_PATH . $forumfolderpath . 'conf_global.php');

    // connect to forum db for cookie timeout variable, and to update the forums cookiedomain and path
    if($DB->select_db($INFO['sql_database'],true) !== false)
    {
      // default values are empty, so just get the entered settings
      $cookieprefix  = $DB->query_first('SELECT conf_value FROM ' . $INFO['sql_tbl_prefix'] . "conf_settings WHERE conf_key = 'cookie_id'");

      // however this has a normal default of 3600, so use that if the entered info is empty
      $cookietimeout = $DB->query_first('SELECT conf_value FROM ' . $INFO['sql_tbl_prefix'] . "conf_settings WHERE conf_key = 'session_expiration'");
      $cookietimeout['conf_value'] = strlen($cookietimeout['conf_value']) ? $cookietimeout['conf_value'] : 3600;

      $cookiedomain = $DB->query_first('SELECT conf_value FROM ' . $INFO['sql_tbl_prefix'] . "conf_settings WHERE conf_key = 'cookie_domain'");
      $cookiedomain['conf_value'] = trim($cookiedomain['conf_value']);

      $cookiepath = $DB->query_first('SELECT conf_value FROM ' . $INFO['sql_tbl_prefix'] . "conf_settings WHERE conf_key = 'cookie_path'");
      $cookiepath['conf_value'] = (trim($cookiepath['conf_value']) == '' ? "/" : trim($cookiepath['conf_value']));
      /*
      $DB->query('UPDATE ' . $INFO['sql_tbl_prefix'] . "conf_settings SET conf_value = '$cookiedomain' WHERE conf_key = 'cookie_domain'");
      $DB->query('UPDATE ' . $INFO['sql_tbl_prefix'] . "conf_settings SET conf_value = '$cookiepath'   WHERE conf_key = 'cookie_path'");
      */

      $forumsystem = array('name'          => 'Invision Power Board 2',
                           'dbname'        => $INFO['sql_database'],
                           'tblprefix'     => $INFO['sql_tbl_prefix'],
                           'folderpath'    => $forumfolderpath,
                           'cookietimeout' => $cookietimeout['conf_value'],
                           'cookieprefix'  => $cookieprefix['conf_value'],
                           'cookiedomain'  => $cookiedomain['conf_value'],
                           'cookiepath'    => $cookiepath['conf_value'],
                           'extra'         => '');
    }
    // switch back to subdreamer db
    $DB->select_db($sddbname);
  }

  return isset($forumsystem) ? $forumsystem : false;

} //GetForumSystem

?>