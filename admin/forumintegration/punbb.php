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
  global $DB;

  if(is_file(ROOT_PATH . $forumfolderpath . '/config.php'))
  {
    include(ROOT_PATH . $forumfolderpath . '/config.php');

    // connect to forum db for cookie timeout variable, and to update the
    // forums cookiedomain and path
    if($DB->select_db($db_name, true) !== false)
    {
      /*
      # "visit" timeout is not cookie expiration!
      list($cookietimeout) = $DB->query_first("SELECT IFNULL(conf_value,'') value".
                                              ' FROM ' . $db_prefix . 'config'.
                                              " WHERE name = 'o_timeout_visit'");
      */
      $cookietimeout = 1209600; #hard-coded in punBB
      $forumsystem = array('name'          => 'punBB',
                           'dbname'        => $db_name,
                           'tblprefix'     => $db_prefix,
                           'folderpath'    => $forumfolderpath,
                           'cookietimeout' => $cookietimeout,
                           'cookieprefix'  => $cookie_name,
                           'cookiedomain'  => $cookie_domain,
                           'cookiepath'    => $cookie_path,
                           'extra'         => $cookie_secure);
    }
    else
    {
      echo 'punBB forum database "'.$dbname.'" could not be accessed!<br />';
    }

    // switch back to subdreamer db
    $DB->select_db($sddbname);
  }
  else
  {
    echo 'punBB forum configuration "'.ROOT_PATH.$forumfolderpath.'/config.php" not found.<br />';
  }

  return isset($forumsystem) ? $forumsystem : false;
}
