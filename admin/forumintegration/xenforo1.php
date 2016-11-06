<?php

if(!defined('IN_PRGM')) exit();

// ############################# GET FORUM SYSTEM ##############################

function GetForumSystem($forumfolderpath, $sddbname)
{
  global $DB;

  $file = ROOT_PATH . $forumfolderpath . 'library/config.php';
  if(is_file($file) && file_exists($file))
  {
    echo 'Forum Settings file found.<br />';
    if(include($file))
    {
      echo 'Settings file loaded.<br />';
      // connect to forum db for cookie timeout variable
      $DB->ignore_error = true;
      if($DB->select_db($config['db']['dbname'], true) !== false)
      {
        $tbl_prefix = 'xf_';
        // check which version of XenForo is the user running
        if($version = $DB->query_first('SELECT option_value FROM ' . $tbl_prefix . "option WHERE option_id = 'currentVersionId'"))
        {
          if(substr($version[0],0,1) !== '1')
          {
            $GLOBALS['forum_error'] = 'Forum version incorrect! Found XenForo '.substr($version[0],0,1).' instead of XenForo 1.x!';
          }
          else
          {
            $err = false;
            try
            {
              // Reset error handler to SD's own one
              if(function_exists('ErrorHandler')) set_error_handler('ErrorHandler'); // in functions_global.php
              @ob_end_flush();

              $XenForo_fileDir = ROOT_PATH . $forumfolderpath;
              require_once($XenForo_fileDir . '/library/XenForo/Autoloader.php');
              XenForo_Autoloader::getInstance()->setupAutoloader($XenForo_fileDir . '/library');
              XenForo_Application::disablePhpErrorHandler();
              XenForo_Application::initialize($XenForo_fileDir . '/library', $XenForo_fileDir);
            } catch (Exception $e) {
              $err = $e->getMessage();
            }
            //SD: skip "already initialized" exception if already integrated!
            if(($err !== false) && ($err !== 'Registry is already initialized'))
            {
              echo 'Exception occured during XF startup: '.$err.'<br />';
            }
            else
            {
              $forumsystem = array('name'          => 'XenForo 1',
                                   'dbname'        => $config['db']['dbname'],
                                   'tblprefix'     => $tbl_prefix,
                                   'folderpath'    => $forumfolderpath,
                                   'cookietimeout' => 3600,
                                   'cookieprefix'  => XenForo_Application::get('config')->cookie->prefix,
                                   'cookiedomain'  => XenForo_Application::get('config')->cookie->domain,
                                   'cookiepath'    => XenForo_Application::get('config')->cookie->path,
                                   'extra'         => '');
            }
          }
        }
        else
        {
          echo '<strong>ERROR: failed to retrieve forum version!</strong><br />';
        }
      }
      else
      {
        echo '<strong>ERROR: connecting to XenForo DB failed!</strong><br />';
      }
      if(!empty($DB->errdesc))
        echo '<strong>DB ERROR:</strong>'.$DB->errdesc.'<br />';
      $DB->ignore_error = false;
      // switch back to subdreamer db
      $DB->select_db($sddbname);
    }
  }

  return isset($forumsystem) ? $forumsystem : false;
}
