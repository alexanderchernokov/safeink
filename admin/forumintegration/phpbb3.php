<?php

if(!defined('IN_PRGM')) exit();

// ################################ SKIN FORUM #################################

function SkinForum($sdheader, $sdfooter)
{
/*
  global $DB, $usersystem;

  $forumprefix = $usersystem['tblprefix'];
  $forumpath   = $usersystem['folderpath'];

  $sdheader = "<!--StartSdHeader-->
<style type=\"text/css\" media=\"all\">
body * td{padding:0;}
div.sd_padfix * td{padding:2px;}
div.sd_padfix{padding:0;text-align:left;background:transparent;border:0;margin:0;}
</style>
" . $sdheader . "
<div class=\"sd_padfix\">
<!--EndSdHeader-->";

  $sdfooter = "<!--StartSdFooter--></div>" . $sdfooter . "
<!--EndSdFooter--></body>";

  //$sdheader = "<!--StartSdHeader-->" . $sdheader . "<!--EndSdHeader-->";
  //$sdfooter = "<!--StartSdFooter-->" . $sdfooter . "<!--EndSdFooter--></body>";

  // get default style for phpBB3
  $getstyleid = $DB->query_first('SELECT config_value FROM '.$forumprefix.'config WHERE config_name = \'default_style\'');
  $styleid    = (int)$getstyleid['config_value'];

  // get folder name for phpBB3 default styles
  $getstylefolder = $DB->query_first('SELECT style_name FROM '.$forumprefix.'styles WHERE style_id = %d AND style_active = 1', $styleid);
  $stylefolder    = $getstylefolder['style_name'];

  $headerpath = $forumpath.'styles/'.$stylefolder.'/template/overall_header.html';
  $footerpath = $forumpath.'styles/'.$stylefolder.'/template/overall_footer.html';

  // uncache writable errors
  clearstatcache();

  // check if files are writable
  if((!is_writable('../'.$headerpath))||(!is_writable('../'.$footerpath)))
  {
    return '<strong>Before Subdreamer can skin your phpBB3 skin, you need to chmod
      both these files to 666:</strong>
      <br /><br />'.$headerpath.'<br />'.
      $footerpath;
  }

  // ##################################################
  // Process header template file (overall_header.html)
  // ##################################################
  $filename = '../'.$headerpath;
  if(($fp = @fopen($filename, 'r')) !== FALSE)
  {
    $forumheader = fread($fp, filesize($filename));
    fclose($fp);
  }
  else
  {
    return '<br />FAILED to open forum header template:<br />'.$headerpath;
  }

  // check and erase previous subdreamer header
  $forumheader = preg_replace("'<!--StartSdHeader-->(.+?)<!--EndSdHeader-->'ms", '', $forumheader);

  // add subdreamer header
  if(preg_match("'<body (.*)>'", $forumheader, $matches))
  {
    $newbodytag = $sd_body_style = '';
    // IF the sd header has it's own <body ...> tag, filter out any style
    if(preg_match("#<body(.+?)>#ms", $sdheader, $sdmatches))
    {
      // This step is to "move" any inner <body> parameters of the SD skin
      // to the forum's <body> tag and remove the obsolete SD <body> tag

      // 0 = full <body ...> tag
      // 1 = inner part of body tag
      if(count($sdmatches)>1)
      {
        $sd_body_style = $sdmatches[1];
      }
      // Remove <body> tag from sd header
      $sdheader = str_replace($sdmatches[0], '', $sdheader);

      // Apply the inner tag to the forum's body tag
      if(strlen($sd_body_style))
      {
        $newbodytag = preg_replace("#>$#", $sd_body_style.'>', $matches[0]);
        $forumheader = preg_replace("'".$matches[0]."'", $newbodytag, $forumheader);
        $matches[0] = $newbodytag;
      }
    }
    $newheader = str_replace($matches[0], $matches[0].$sdheader, $forumheader);
  }
  else
  {
    // "OLD" way, which isn't good...
    $newheader = preg_replace("'</head>'", '</head>'.$sdheader, $forumheader);
  }

// Debug-Echo for INTERNAL use only!
//echo '<pre>'.htmlspecialchars($newheader).'</pre><br />';
//return '*';

  if(($fp = @fopen($filename, 'w')) !== FALSE)
  {
    @fwrite($fp, $newheader);
    @fclose($fp);
  }
  else
  {
    return '<br />FAILED to write forum header template:<br />'.$filename;
  }

  // ##################################################
  // Process footer template file (overall_footer.html)
  // ##################################################
  $filename = '../'.$footerpath;
  if(($fp = fopen($filename, "rb")) !== FALSE)
  {
    $forumfooter = fread($fp, filesize($filename));
    fclose($fp);
  }
  else
  {
    return '<br />Failed to open forum footer template:<br />'.$footerpath;
  }

  // check and erase previous subdreamer footer
  $forumfooter = preg_replace("'<!--StartSdFooter-->(.+?)<!--EndSdFooter-->'ms", '', $forumfooter);

  // make sure to get rid of any "</body>" tags as we add our own now
  // then add subdreamer skin footer to forum's footer
  $sdfooter  = str_replace("</body>", '', $sdfooter);
  $newfooter = preg_replace("'</body>'", $sdfooter.'</body>', $forumfooter);
  if(($fp = fopen($filename, 'w')) !== FALSE)
  {
    fwrite($fp, $newfooter);
    fclose($fp);
  }
  else
  {
    return '<br />FAILED to write forum footer template:<br />'.$filename;
  }

  $cacheprefix = '../'.$forumpath.'cache/';
  $cachefiles = array(
    $cacheprefix.'ctpl_admin_overall_footer.html.php',
    $cacheprefix.'ctpl_admin_overall_header.html.php',
    $cacheprefix.'tpl_'.$stylefolder.'_overall_footer.html.php',
    $cacheprefix.'tpl_'.$stylefolder.'_overall_header.html.php');
  foreach($cachefiles as $file)
  {
    if(file_exists($file))
    {
      @chmod($file, 0666);
      @unlink($file);
    }
  }

  // override user stle
  $DB->query('UPDATE '.$forumprefix."config SET config_value = '1' WHERE config_name = 'override_user_style'");
*/

} //SkinForum


// ############################ RESTORE FORUM SKIN #############################

function RestoreForumSkin()
{
/*
  global $DB, $usersystem;

  $forumprefix = $usersystem['tblprefix'];
  $forumpath   = $usersystem['folderpath'];

  // get default style for phpBB3
  $getstyleid = $DB->query_first('SELECT config_value FROM '.$forumprefix.'config WHERE config_name = \'default_style\'');
  $styleid    = $getstyleid['config_value'];

  // get folder name for phpBB3 default styles
  $getstylefolder = $DB->query_first('SELECT style_name FROM '.$forumprefix.'styles WHERE style_id = %d AND style_active = 1',$styleid);
  $stylefolder    = $getstylefolder['style_name'];

  // ##################################################
  // Process header template file (overall_header.html)
  // ##################################################
  $filename = '../' . $forumpath . 'styles/' . $stylefolder . '/template/overall_header.html';
  if(($fp = fopen($filename, 'r')) !== FALSE)
  {
    $forumheader = fread($fp, filesize($filename));
    fclose($fp);
  }
  else
  {
    return '<br />FAILED to open forum header template:<br />'.$filename;
  }

  // Remove possible inline "style" from <body> tag which may have come
  // from SD's skin:
  if(preg_match("#<body(.+?)>#ms", $forumheader, $sdmatches))
  {
    // This step is to "move" any inner <body> parameters of the SD skin
    // to the forum's <body> tag and remove the obsolete SD <body> tag
    if(preg_match("/style=\".+\"/si", $sdmatches[0], $sdmatches))
    {
      // Remove <body> tag from sd header
      $forumheader = str_replace($sdmatches[0], '', $forumheader);
    }
  }
  // remove sd forum header
  $forumheader = preg_replace("'<!--StartSdHeader-->(.+?)<!--EndSdHeader-->'ms", "", $forumheader);

  // restore forum header
  if(($fp = fopen($filename, 'w')) !== FALSE)
  {
    fwrite($fp, $forumheader);
    fclose($fp);
  }
  else
  {
    return '<br />FAILED to write forum header template:<br />'.$filename;
  }

  // ##################################################
  // Process footer template file (overall_footer.html)
  // ##################################################
  $filename = '../' . $forumpath . 'styles/' . $stylefolder . '/template/overall_footer.html';
  if(($fp = fopen($filename, 'r')) !== FALSE)
  {
    $forumfooter = fread($fp, filesize($filename));
    fclose($fp);
  }
  else
  {
    return '<br />FAILED to open forum footer template:<br />'.$filename;
  }

  // erase subdreamer footer
  $forumfooter = preg_replace("'<!--StartSdFooter-->(.+?)<!--EndSdFooter-->'ms", "", $forumfooter);

  // restore forum footer
  if(($fp = fopen($filename, 'w')) !== FALSE)
  {
    fwrite($fp, $forumfooter);
    fclose($fp);
  }
  else
  {
    return '<br />FAILED to write forum header template:<br />'.$filename;
  }

  $cacheprefix = '../'.$forumpath.'cache/';
  $cachefiles = array(
    $cacheprefix.'ctpl_admin_overall_footer.html.php',
    $cacheprefix.'ctpl_admin_overall_header.html.php',
    $cacheprefix.'tpl_'.$stylefolder.'_overall_footer.html.php',
    $cacheprefix.'tpl_'.$stylefolder.'_overall_header.html.php');
  foreach($cachefiles as $file)
  {
    if(file_exists($file))
    {
      @chmod($file, 0666);
      @unlink($file);
    }
  }
*/
} //RestoreForumSkin


// ############################# GET FORUM SYSTEM ##############################

function GetForumSystem($forumfolderpath, $sddbname)
{
  global $DB;

  if(is_file(ROOT_PATH . $forumfolderpath . 'config.php'))
  {
    include(ROOT_PATH . $forumfolderpath . 'config.php');

    // connect to forum db for cookie timeout variable, and to update the forums cookiedomain and path
    if($DB->select_db($dbname, true) !== false)
    {
      $cookietimeout = $DB->query_first('SELECT config_value FROM ' . $table_prefix . "config WHERE config_name = 'session_length'");
      $cookieprefix  = $DB->query_first('SELECT config_value FROM ' . $table_prefix . "config WHERE config_name = 'cookie_name'");

      $cookiedomain = $DB->query_first('SELECT config_value FROM ' . $table_prefix . "config WHERE config_name = 'cookie_domain'");
      $cookiedomain['config_value'] = trim($cookiedomain['config_value']);

      $cookiepath = $DB->query_first('SELECT config_value FROM ' . $table_prefix . "config WHERE config_name = 'cookie_path'");
      $cookiepath['config_value'] = (trim($cookiepath['config_value']) == '' ? "/" : trim($cookiepath['config_value']));
      /*
      $DB->query('UPDATE ' . $table_prefix . "config SET config_value = '$cookiedomain' WHERE config_name = 'cookie_domain'");
      $DB->query('UPDATE ' . $table_prefix . "config SET config_value = '$cookiepath'   WHERE config_name = 'cookie_path'");
      */
      $forumsystem = array('name'          => 'phpBB3',
                           'dbname'        => $dbname,
                           'tblprefix'     => $table_prefix,
                           'folderpath'    => $forumfolderpath,
                           'cookietimeout' => $cookietimeout['config_value'],
                           'cookieprefix'  => $cookieprefix['config_value'],
                           'cookiedomain'  => $cookiedomain['config_value'],
                           'cookiepath'    => $cookiepath['config_value'],
                           'extra'         => '');
    }
    else
    {
      echo 'phpBB forum database "'.$dbname.'" could not be accessed!<br />';
    }

    // switch back to subdreamer db
    $DB->select_db($sddbname);
  }
  else
  {
    echo 'phpBB forum configuration "'.ROOT_PATH.$forumfolderpath.'config.php" not found.<br />';
  }

  return isset($forumsystem) ? $forumsystem : false;
}

?>