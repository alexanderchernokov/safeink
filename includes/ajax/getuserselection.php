<?php
define('IN_PRGM', true);
define('ROOT_PATH', '../../');

// INIT PRGM
require(ROOT_PATH . 'includes/init.php');
$forumdbname = $usersystem['dbname'];
$forumname   = $usersystem['name'];
$tableprefix = $usersystem['tblprefix'];

$action = GetVar('action', '', 'string');
$searchuser = strip_alltags(trim(CleanVar(GetVar('term', '', 'html'))));
$searchuser = str_replace(array("'", '&039;'), array('&#039;','&#039;'), $searchuser); //SD343
$searchtype = Is_Valid_Number(GetVar('t', 0, 'whole_number'),0,1,2);


//SD342: added user availability check for p12 plugin
if(empty($userinfo['loggedin']) && Is_Ajax_Request() && ($action=='p12_checkuser'))
{
  header('Content-type: application/text; charset='.SD_CHARSET);
  $result = 0; //default: username not available
  if(!empty($searchtype) && !empty($searchuser) && (strlen($searchuser)>2))
  {
    $searchuser_org = trim(SanitizeInputForSQLSearch($searchuser));
    $DB->ignore_error = true;
    //SD343: "2" = check value as email
    if(empty($searchuser_org) || ($searchuser_org != $searchuser) ||
       (($searchtype==1) && sd_IsUsernameInvalid($searchuser_org)) ||
       (($searchtype==2) && (sd_IsEmailBanned($searchuser_org) || !IsValidEmail($searchuser_org))) )
    {
      $result = 2; //invalid username or email
    }
    else
    {
      if($DB->database != $forumdbname) $DB->select_db($forumdbname);
      if($searchtype==1)
      {
        if(!$usercheck = $DB->query_first('SELECT 1 FROM '.PRGM_TABLE_PREFIX.'users'.
                                          " WHERE trim(username) = '%s'",
                                          $DB->escape_string($searchuser_org)))
        {
          $result = '1'; //username available
        }
      }
      else
      {
        $isSpam = false;
        //SD343: support for StopForumSpam.com
        if(function_exists('sd_sfs_is_spam'))
        {
          if($isSpam = sd_sfs_is_spam($searchuser_org))
          {
            $result = '2';
          }
        }

        if(!$isSpam &&
           !$usercheck = $DB->query_first('SELECT 1 FROM '.PRGM_TABLE_PREFIX.'users'.
                                          " WHERE email = '%s'",
                                          $DB->escape_string($searchuser_org)))
        {
          $result = '1'; //email available
        }
      }
    }
    $DB->ignore_error = false;
  }
  echo $result;
  $DB->close();
  exit();
}

if(!Is_Ajax_Request()  || !strlen($searchuser))
{
	
  header('HTTP/1.1 400 Bad Request');
  $DB->close();
  exit();
}

// ############################################################################
// GET USER ID
// ############################################################################


$towrite .= "Error 2";
$limit = ' LIMIT 0,10';


header('Content-type: application/text; charset='.SD_CHARSET);

$searchuser_org = trim(SanitizeInputForSQLSearch($searchuser));
if(empty($searchuser_org) || sd_IsUsernameInvalid($searchuser_org) || ($searchuser_org != $searchuser))
{
  $DB->close();
  exit;
}

// switch to forum database
if($DB->database != $forumdbname) $DB->select_db($forumdbname);

$DB->result_type = MYSQL_ASSOC;
if($forumname=='Subdreamer')
{
  //SD342 switch to SD database; preference for user screen name - if present - over username
  if($DB->database != $dbname) $DB->select_db($dbname);
  $getusers = $DB->query("SELECT u.userid, IF(IFNULL(ud.user_screen_name,'')='',u.username,ud.user_screen_name) username".
                          ' FROM '.PRGM_TABLE_PREFIX.'users u'.
                          ' LEFT JOIN '.PRGM_TABLE_PREFIX.'users_data ud ON ud.userid = u.userid AND ud.usersystemid = %d'.
                          " WHERE (ud.user_screen_name IS NOT NULL AND ud.user_screen_name LIKE '%s')".
                          " OR (IFNULL(ud.user_screen_name,'')='' AND u.username LIKE '%s')".
                          " ORDER BY IF(ud.user_screen_name = '',u.username,ud.user_screen_name) ".$limit,
                          $usersystem['usersystemid'], $DB->escape_string($searchuser).'%', $DB->escape_string($searchuser).'%');
}
else if(substr($forumname,0,9) == 'vBulletin')
{
  $getusers = $DB->query('SELECT userid, username FROM '.$tableprefix."user
                          WHERE username LIKE '%s'
                          ORDER BY username".$limit,
                          $DB->escape_string($searchuser).'%');
}
else if($forumname == 'phpBB2' || $forumname == 'phpBB3')
{
  $getusers = $DB->query('SELECT user_id userid, username FROM '.$tableprefix."users
                         WHERE username != 'Anonymous'
                         AND username like '%s'
                         ORDER BY username".$limit,
                         $DB->escape_string($searchuser).'%');
}
else if(($forumname == 'Invision Power Board 2') || ($forumname == 'Invision Power Board 3'))
{
  $getusers = $DB->query('SELECT id userid, name username FROM ' . $tableprefix . "members
                         WHERE name like '%s'
                         ORDER BY name".$limit,
                         $DB->escape_string($searchuser).'%');
}
else if($forumname == 'Simple Machines Forum 1')
{
  $getusers = $DB->query('SELECT ID_MEMBER userid, memberName username FROM ' . $tableprefix . "members
                          WHERE memberName like '%s'
                          ORDER BY memberName".$limit,
                          $DB->escape_string($searchuser).'%');
}
else if($forumname == 'Simple Machines Forum 2')
{
  $getusers = $DB->query('SELECT id_member userid, member_name username FROM ' . $tableprefix . "members
                          WHERE member_name like '%s'
                          ORDER BY member_name".$limit,
                          $DB->escape_string($searchuser).'%');
}
else if($forumname == 'XenForo 1')
{
  $getusers = $DB->query('SELECT user_id userid, username FROM ' . $tableprefix . "user
                          WHERE username like '%s'
                          ORDER BY username ".$limit,
                          $DB->escape_string($searchuser).'%');
}
else if($forumname == 'MyBB') //SD370
{
  $getusers = $DB->query('SELECT uid userid, username FROM ' . $tableprefix . "users
                          WHERE username like '%s'
                          ORDER BY username ".$limit,
                          $DB->escape_string($searchuser).'%');
}
else if($forumname == 'punBB') //SD370
{
  $getusers = $DB->query('SELECT id userid, username FROM ' . $tableprefix . "users
                          WHERE username like '%s'
                          ORDER BY username ".$limit,
                          $DB->escape_string($searchuser).'%');
}
else
{
  exit();
}

$result = '';
$user_row = array();
if(isset($getusers))
{
  $i = 0;
  $users_arr = array();
  while($user = $DB->fetch_array($getusers,null,MYSQL_ASSOC))
  {
	  $user['value'] = htmlentities(stripslashes($user['username']));
	  $user['id']	= (int)$user['userid'];
	  $user_row[] = $user;
   // echo json_encode(str_replace(array("'", '&#039;'), array("'","'"), $user['username']) . '|' . $user['userid'] . "\n");
    #echo $user['username'] . '|' . $user['userid'] . "\n";
    $i++;
    if($i>10) break; //SD343
  }
  
  echo json_encode($user_row);
}


$DB->close();
