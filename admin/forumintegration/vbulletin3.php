<?php

if(!defined('IN_SUBDREAMER')) exit();

// ############################# GET FORUM SYSTEM ##############################

function GetForumSystem($forumfolderpath, $sddbname)
{
  global $DB;

  // Init variables
  $vblicensenumber = '';
  $cookieprefix = '';
  $tableprefix = '';
  $dbname = '';

  if(is_file(ROOT_PATH . $forumfolderpath . 'includes/config.php'))
  {
    include(ROOT_PATH . $forumfolderpath . 'includes/config.php');

    // Required: vB 3.5+
    if(!isset($config['Database']['dbname']))
    {
      return false;
    }

    // auto detect vBulletin 3 license for login script
    $filename = ROOT_PATH . $forumfolderpath . 'includes/vbulletin_credits.php';
    if(false !== ($fp = @fopen($filename, 'r')))
    {
      for($line = 1; $line <= 4; $line++)
      {
        $buffer = fgets($fp, 4096);
      }
      $vblicensenumber = trim(strrchr(trim($buffer), ' '));
      fclose($fp);
    }

    $dbname       = $config['Database']['dbname'];
    $tableprefix  = $config['Database']['tableprefix'];
    $cookieprefix = $config['Misc']['cookieprefix'];

    // vB 3.8+: auto detect COOKIE_SALT (if present in functions.php)
    if(!defined('COOKIE_SALT'))
    {
      $COOKIE_SALT = '';
      $DB->query("DELETE FROM {mainsettings} WHERE varname = 'vb_cookie_salt'");
      $filename = ROOT_PATH . $forumfolderpath . 'includes/functions.php';
      if(false !== ($fp = @fopen($filename, 'r')))
      {
        $buffer = fread($fp, 2048);
        $lines = preg_split("#\R#", $buffer, -1, PREG_SPLIT_NO_EMPTY);
        fclose($fp);
        foreach($lines as $line)
        {
          // line is e.g. "define('COOKIE_SALT', 'some cryptic value');"
          if(strpos($line, 'COOKIE_SALT') !== false)
          {
            $GLOBALS['sd_ignore_watchdog']=true;
            @eval($line);
            $GLOBALS['sd_ignore_watchdog']=false;
            $COOKIE_SALT = defined('COOKIE_SALT') ? COOKIE_SALT : '';
            $DB->query("INSERT INTO {mainsettings} (varname,groupname,input,title,value) VALUES ('vb_cookie_salt','','','','%s')",
                       $DB->escape_string($COOKIE_SALT));
            break;
          }
        }
      }
    }

    // connect to forum db for cookie timeout variable
    if($DB->select_db($dbname, true) !== false)
    {
      $cookietimeout = $DB->query_first('SELECT value FROM ' . $tableprefix . "setting WHERE varname = 'cookietimeout'");

      $cookiedomain = $DB->query_first('SELECT value FROM ' . $tableprefix . "setting WHERE varname = 'cookiedomain'");
      $cookiedomain['value'] = trim($cookiedomain['value']);

      $cookiepath = $DB->query_first("SELECT value FROM " . $tableprefix . "setting WHERE varname = 'cookiepath'");
      $cookiepath['value'] = (trim($cookiepath['value']) == '' ? "/" : trim($cookiepath['value']));
      /*
      $DB->query("UPDATE " . $tableprefix . "setting SET value = '$cookiedomain' WHERE varname = 'cookiedomain'");
      $DB->query("UPDATE " . $tableprefix . "setting SET value = '$cookiepath'   WHERE varname = 'cookiepath'");
      */

      $forumsystem = array('name'          => 'vBulletin 3',
                           'dbname'        => $dbname,
                           'tblprefix'     => $tableprefix,
                           'folderpath'    => $forumfolderpath,
                           'cookietimeout' => $cookietimeout['value'],
                           'cookieprefix'  => $cookieprefix,              // included from config.php
                           'cookiedomain'  => $cookiedomain['value'],
                           'cookiepath'    => $cookiepath['value'],
                           'extra'         => $vblicensenumber);
    }
    // switch back to subdreamer db
    $DB->select_db($sddbname);
  }

  return isset($forumsystem) ? $forumsystem : false;

} //GetForumSystem

?>