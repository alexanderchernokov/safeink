<?php

if(!defined('IN_PRGM')) exit();

// ############################# GET FORUM SYSTEM ##############################

function GetForumSystem($forumfolderpath, $sddbname)
{
  global $DB;

  $file = ROOT_PATH . $forumfolderpath . 'Settings.php';
  if(is_file($file) && file_exists($file))
  {
    echo 'Forum Settings file found.<br />';
    if(include($file))
    {
      echo 'Settings file loaded.<br />';
      // connect to forum db for cookie timeout variable
      if($DB->select_db($db_name, true) !== false)
      {
        // check which version of smf is the user running
        if($smfversion = $DB->query_first('SELECT value FROM ' . $db_prefix . "settings WHERE variable = 'smfVersion'"))
        {
          if(substr($smfversion[0],0,2) !== '1.')
          {
            $GLOBALS['forum_error'] = 'Forum version incorrect! Found SMF '.$smfversion[0].' instead of SMF 1.x!';
          }
          else
          {
            $cookietimeout = $DB->query_first('SELECT value FROM ' . $db_prefix . "settings WHERE variable = 'cookieTime'");

            // --- COOKIE DOMAIN AND COOKIE PATH DO NOT EXIST IN SMF ---
            $forumsystem = array('name'          => 'Simple Machines Forum 1',
                                 'dbname'        => $db_name,
                                 'tblprefix'     => $db_prefix,
                                 'folderpath'    => $forumfolderpath,
                                 'cookietimeout' => $cookietimeout['value'],
                                 'cookieprefix'  => $cookiename,
                                 'cookiedomain'  => '',
                                 'cookiepath'    => '/',
                                 'extra'         => '');
          }
        }
      }
      // switch back to subdreamer db
      $DB->select_db($sddbname);
    }
  }

  return isset($forumsystem) ? $forumsystem : false;
}

?>