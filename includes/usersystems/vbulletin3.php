<?php

if(!defined('IN_PRGM')) exit();

// NOTE: Minimum required version of vB is 3.7!
// Since user password verification had changed in 3.8,
// there are now 2 separate integration files!

// Are we running VB 3.7 or 3.8+?
$vb37 = $vb38 = false;
$getversion = $DB->query_first('SELECT value FROM ' . $usersystem['tblprefix'] . "setting WHERE varname = 'templateversion'");
if(isset($getversion[0]))
{
  //echo $getversion[0];
  $ver = explode('.', $getversion[0]);
  if(intval($ver[0]) == 3 && intval($ver[1]) >= 7)
  {
    if(intval($ver[1]) == 7)
    {
      $vb37 = true;
    }
    else
    {
      $vb38 = true;
    }
  }
}

if($vb38)
{
  require(SD_INCLUDE_PATH.'usersystems/vbulletin3_8.php');
}
else
//if($vb37)
{
  require(SD_INCLUDE_PATH.'usersystems/vbulletin3_7.php');
}
//else
{
  //Wrong forum version!
}
