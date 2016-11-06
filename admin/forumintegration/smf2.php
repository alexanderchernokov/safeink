<?php

if(!defined('IN_PRGM')) exit();

// ############################# GET FORUM SYSTEM ##############################

function GetForumSystem($forumfolderpath, $sddbname)
{
  global $DB;

  $GLOBALS['forum_error'] = '';
  $file = ROOT_PATH . $forumfolderpath . 'Settings.php';
  if(is_file($file) && file_exists($file))
  {
    echo 'Forum Settings file loaded.<br />';
    if(include($file))
    {
      echo 'Settings file loaded.<br />';
      if(!empty($maintenance)) echo 'Info: Forum is currently in Maintenance mode!<br />';
      // Connect to forum database and process some settings
      if($DB->select_db($db_name, true) !== false)
      {
        // check which version of smf is the user running
        $err = false;
        $sel = 'SELECT value FROM ' . $db_prefix . 'settings WHERE variable = ';
        if($smfversion = $DB->query_first($sel . "'smfVersion'"))
        {
          if(substr($smfversion[0],0,2) !== '2.')
          {
            $err = true;
            $GLOBALS['forum_error'] = '<b>ERROR:</b> Forum version incorrect! Found SMF '.$smfversion[0].' instead of SMF 2.x!';
          }
          if(!isset($boardurl))
          {
            //SD343: check if board URL is set
            $err = true;
            $GLOBALS['forum_error'] = '<b>ERROR:</b> Forum board URL is not set!';
          }
          if(!$err)
          {
            //SD343: check if required database session option is enabled
            $smf_dbsessions = $DB->query_first($sel . "'databaseSession_enable'");
            if(empty($smf_dbsessions))
            {
              $err = true;
              $GLOBALS['forum_error'] = '<b>Error:</b> SMF integration requires database sessions to be enabled!<br />'.
              'Open SMF as admin, menu Administration Center » Server Settings » Cookies and Sessions<br />'.
              'and check option "<b>Use database driven sessions</b>".';
            }
          }
          if(!$err)
          {
            echo 'Board URL: "'.$boardurl.'"<br />Cookie Name: "'.$cookiename.'"<br />';
            $cookiedomain = '';
            //SD343: check cookie settings (local/global)
            $smf_localCookies  = $DB->query_first($sel . "'localCookies'");
            $smf_globalCookies = $DB->query_first($sel . "'globalCookies'");
            $parsed_url = parse_url($boardurl);
            if(preg_match('~^\d{1,3}(\.\d{1,3}){3}$~', $parsed_url['host']) == 0 && preg_match('~(?:[^\.]+\.)?([^\.]{2,}\..+)\z~i', $parsed_url['host'], $parts) == 1)
            {
              if(!empty($globalCookies))
              {
                //@ini_set('session.cookie_domain', '.' . $parts[1]);
                $cookiedomain = '.' . $parts[1];
                echo 'Cookie Domain will be: "'.$cookiedomain.'" (global cookies option)<br />';
              }
              else
              {
                $cookiedomain = $parts[1];
                echo 'Cookie Domain will be: "'.$cookiedomain.'" (local cookies option)<br />';
              }
            }

            // Check timeout value
            $cookietimeout = $DB->query_first($sel . "'cookieTime'");
            if(empty($cookietimeout) || empty($cookietimeout['value']) || !is_numeric($cookietimeout['value']))
            {
              $cookietimeout = array();
              $cookietimeout['value'] = 60;
            }
            echo 'Cookie timeout: '.(int)$cookietimeout['value'].' minutes<br /><br />';

            // --- COOKIE DOMAIN AND COOKIE PATH DO NOT EXIST IN SMF ---
            $forumsystem = array('name'          => 'Simple Machines Forum 2',
                                 'dbname'        => $db_name,
                                 'tblprefix'     => $db_prefix,
                                 'folderpath'    => $forumfolderpath,
                                 'cookietimeout' => (int)$cookietimeout['value'],
                                 'cookieprefix'  => $cookiename,
                                 'cookiedomain'  => $cookiedomain, //SD343
                                 'cookiepath'    => '/',
                                 'extra'         => '');
          }
        }
        else
        {
          $GLOBALS['forum_error'] = 'SMF2 version identifier not found in database!<br />';
        }
      }
      else
      {
        $GLOBALS['forum_error'] = 'Connection to SMF2 database failed!<br />';
      }
      // switch back to subdreamer db
      $DB->select_db($sddbname);
    }
  }

  return isset($forumsystem) && empty($GLOBALS['forum_error']) ? $forumsystem : false;
}
