<?php

if(!defined('IN_PRGM')) exit();

// ################################ SKIN FORUM #################################

function SkinForum($sdheader, $sdfooter)
{
/*
  global $DB, $usersystem;

  $forumprefix = $usersystem['tblprefix'];
  $forumpath   = $usersystem['folderpath'];

  $sdheader = "</head><!--StartSdHeader-->\n" . $sdheader . "\n<!--EndSdHeader-->";
  $sdfooter = "<!--StartSdFooter-->\n" . $sdfooter . "\n<!--EndSdFooter--></body>";

  // get default style for phpBB2
  $getstyleid = $DB->query_first("SELECT config_value FROM ".$forumprefix."config WHERE config_name = 'default_style'");
  $styleid    = $getstyleid['config_value'];

  // get folder name for phpBB2 default styles
  $getstylefolder = $DB->query_first("SELECT template_name FROM ".$forumprefix."themes WHERE themes_id = '$styleid'");
  $stylefolder    = $getstylefolder['template_name'];

  // check if files are writable
  if( (!is_writable('../' . $forumpath . 'templates/' . $stylefolder . '/overall_header.tpl')) OR (!is_writable('../' . $forumpath . 'templates/' . $stylefolder . '/overall_footer.tpl')) )
  {
    return '<b>Before Subdreamer can skin your phpBB2 skin, you need to chmod both these files to 766:</b>
          <br /><br />' . $forumpath . 'templates/' . $stylefolder . '/overall_header.tpl
          <br />' . $forumpath . 'templates/' . $stylefolder . '/overall_footer.tpl';
  }

  // open up overall_header.tpl and get contents
  $filename = '../' . $forumpath . 'templates/' . $stylefolder . '/overall_header.tpl';
  $fp = fopen($filename, "r");
  $forumheader = fread($fp, filesize($filename));
  fclose($fp);

  // check and erase previous subdreamer header
  $forumheader = preg_replace("'<!--StartSdHeader-->(.*)<!--EndSdHeader-->'ms", "", $forumheader);

  // add subdreamer header
  $newheader = preg_replace("'</head>'", $sdheader, $forumheader);
  $fp = fopen($filename, 'w');
  fwrite($fp, $newheader);
  fclose($fp);

  // open up overall_footer.tpl
  $filename = '../' . $forumpath . 'templates/' . $stylefolder . '/overall_footer.tpl';
  $fp = fopen($filename, "r");
  $forumfooter = fread($fp, filesize($filename));
  fclose($fp);

  // check and erase previous subdreamer footer
  $forumfooter = preg_replace("'<!--StartSdFooter-->(.*)<!--EndSdFooter-->'ms", "", $forumfooter);

  // add subdreamer skin footer
  $newfooter = preg_replace("'</body>'", $sdfooter, $forumfooter);
  $fp = fopen($filename, 'w');
  fwrite($fp, $newfooter);
  fclose($fp);

  // override user stle
  $DB->query("UPDATE ".$forumprefix."config SET config_value = '1' WHERE config_name = 'override_user_style'");
*/
}


// ############################ RESTORE FORUM SKIN #############################

function RestoreForumSkin()
{
/*
  global $DB, $usersystem;

  $forumprefix = $usersystem['tblprefix'];
  $forumpath   = $usersystem['folderpath'];

  // get default style for phpBB2
  $getstyleid = $DB->query_first("SELECT config_value FROM ".$forumprefix."config WHERE config_name = 'default_style'");
  $styleid    = $getstyleid['config_value'];

  // get folder name for phpBB2 default styles
  $getstylefolder = $DB->query_first("SELECT template_name FROM ".$forumprefix."themes WHERE themes_id = '$styleid'");
  $stylefolder    = $getstylefolder['template_name'];

  // open up overall_header.tpl and get contents
  $filename = '../' . $forumpath . 'templates/' . $stylefolder . '/overall_header.tpl';
  $fp = fopen($filename, "r");
  $forumheader = fread($fp, filesize($filename));
  fclose($fp);

  // erase subdreamer header
  $forumheader = preg_replace("'<!--StartSdHeader-->(.*)<!--EndSdHeader-->'ms", "", $forumheader);

  // restore forum header
  $fp = fopen($filename, 'w');
  fwrite($fp, $forumheader);
  fclose($fp);


  // open up overall_footer.tpl
  $filename = '../' . $forumpath . 'templates/' . $stylefolder . '/overall_footer.tpl';
  $fp = fopen($filename, "r");
  $forumfooter = fread($fp, filesize($filename));
  fclose($fp);

  // erase subdreamer footer
  $forumfooter = preg_replace("'<!--StartSdFooter-->(.*)<!--EndSdFooter-->'ms", "", $forumfooter);

  // restore forum footer
  $fp = fopen($filename, 'w');
  fwrite($fp, $forumfooter);
  fclose($fp);
*/
}


// ############################# GET FORUM SYSTEM ##############################

function GetForumSystem($forumfolderpath, $sddbname)
{
  global $DB;

  if(file_exists(ROOT_PATH . $forumfolderpath . 'config.php'))
  {
    include(ROOT_PATH . $forumfolderpath . 'config.php');

    // connect to forum db for cookie timeout variable
    if($DB->select_db($dbname, true) !== false)
    {
      $cookietimeout = $DB->query_first('SELECT config_value FROM ' . $table_prefix . "config WHERE config_name = 'session_length'");
      $cookieprefix  = $DB->query_first('SELECT config_value FROM ' . $table_prefix . "config WHERE config_name = 'cookie_name'");

      $cookiedomain = $DB->query_first('SELECT config_value FROM ' . $table_prefix . "config WHERE config_name = 'cookie_domain'");
      $cookiedomain['config_value'] = trim($cookiedomain['config_value']);

      $cookiepath = $DB->query_first('SELECT config_value FROM ' . $table_prefix . "config WHERE config_name = 'cookie_path'");
      $cookiepath['config_value'] = (trim($cookiepath['config_value']) == '' ? "/" : trim($cookiepath['config_value']));
      /*
      $DB->query("UPDATE " . $table_prefix . "config SET config_value = '$cookiedomain' WHERE config_name = 'cookie_domain'");
      $DB->query("UPDATE " . $table_prefix . "config SET config_value = '$cookiepath'   WHERE config_name = 'cookie_path'");
      */

      $forumsystem = array('name'          => 'phpBB2',
                           'dbname'        => $dbname,
                           'tblprefix'     => $table_prefix,
                           'folderpath'    => $forumfolderpath,
                           'cookietimeout' => $cookietimeout['config_value'],
                           'cookieprefix'  => $cookieprefix['config_value'],
                           'cookiedomain'  => $cookiedomain['config_value'],
                           'cookiepath'    => $cookiepath['config_value'],
                           'extra'         => '');
    }

    // switch back to subdreamer db
    $DB->select_db($sddbname);
  }

  return isset($forumsystem) ? $forumsystem : false;
}

?>