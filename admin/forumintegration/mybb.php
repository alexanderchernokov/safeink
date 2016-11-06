<?php

if(!defined('IN_PRGM')) exit();

// ############################### SKIN FORUM #################################

function SkinForum($sdheader, $sdfooter)
{
  //legacy, not supported
} //SkinForum


// ########################### RESTORE FORUM SKIN #############################

function RestoreForumSkin()
{
  //legacy, not supported
} //RestoreForumSkin


// ############################ GET FORUM SYSTEM ##############################

function GetForumSystem($forumfolderpath, $sddbname)
{
  if(!file_exists(ROOT_PATH . $forumfolderpath . '/inc/config.php'))
  {
    echo 'MyBB forum configuration "'.ROOT_PATH.$forumfolderpath.'/inc/config.php" not found.<br />';
  }

  global $DB;

  include(ROOT_PATH . $forumfolderpath . '/inc/config.php');
  $dbname = $config['database']['database'];
  $tableprefix = isset($config['database']['table_prefix'])?
                   trim($config['database']['table_prefix']) : '';

  // connect to forum db for cookie timeout variable, and to update the forums cookiedomain and path
  if($DB->select_db($dbname, true) !== false)
  {
    $DB->ignore_error = true;
    if($getvalues = $DB->query("SELECT name, IFNULL(value,'') value".
                               ' FROM '.$tableprefix.'settings'.
                               " WHERE name IN ('cookiedomain','cookieprefix','cookiepath')"))
    {
      $conf = $DB->fetch_array_all($getvalues,MYSQL_ASSOC);
    }
    $DB->ignore_error = false;
    if(!empty($DB->errno))
    {
      echo 'Error in accessing MyBB settings table:<br />"'.$DB->errdesc.'"<br />';
      return false;
    }

    $forumsystem = array('name'          => 'MyBB',
                         'dbname'        => $dbname,
                         'tblprefix'     => $tableprefix,
                         'folderpath'    => $forumfolderpath,
                         'cookietimeout' => 31536000, # 86400 * 365 = 1 year
                         'cookieprefix'  => (isset($conf['cookieprefix'])?$conf['cookieprefix']:''),
                         'cookiedomain'  => (isset($conf['cookiedomain'])?$conf['cookiedomain']:''),
                         'cookiepath'    => (isset($conf['cookiepath'])?$conf['cookiepath']:'/'),
                         'extra'         => '');
  }
  else
  {
    echo 'MyBB forum database "'.$dbname.'" could not be accessed!<br />';
  }

  // switch back to subdreamer db
  $DB->select_db($sddbname,false);

  return isset($forumsystem) ? $forumsystem : false;
}
