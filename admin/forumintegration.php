<?php
// DEFINE CONSTANTS
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');

// INIT PRGM
include(ROOT_PATH . 'includes/init.php');

// LOAD ADMIN LANGUAGE
$admin_phrases = LoadAdminPhrases(5);

// CHECK PAGE ACCESS
CheckAdminAccess('users');

$script = '<script type="text/javascript">
// <![CDATA[
if(typeof(jQuery) !== "undefined"){
  jQuery(document).ready(function(){
    (function($){

	 $("[data-rel=popover]").popover({container:"body"});
	 $("[data-rel=tooltip]").tooltip();
    }(jQuery));
  });
}
// ]]>
</script>
';
$sd_head->AddScript($script);

// GET POST VALUES
$forumname  = GetVar('forumname', '', 'string', true, false);
$systemname = GetVar('systemname', '', 'string', true, false);
$action = GetVar('action', '', 'string');
$action = in_array($action, array('updatesystemsettings','updatesystemsettings2')) ? $action : '';

if(($usersystem['name']=='XenForo 1') || ($systemname == 'XenForo 1'))
{
  $mainsettings['gzipcompress'] = 0;
}

// DISPLAY ADMIN HEADER
DisplayAdminHeader(array('Users', ($action != '' ? $action : 'forum_integration')));


if(empty($userinfo['adminaccess']) || empty($userinfo['loggedin']))
{
  PrintErrors($sdlanguage['no_view_access']);
  $DB->close();
  exit();
}

// ########################### GET FORUM FUNCTIONS ############################

// get forum functions if we are updating to a forum, or if we are currently
// already integrated with a forum
if( (isset($systemname) && ($systemname != 'Subdreamer')) ||
    ($usersystem = $DB->query_first("SELECT name, dbname, tblprefix, folderpath FROM {usersystems} WHERE activated = '1' AND name != 'Subdreamer'")) )
{
  $switchvar = strlen($systemname) ? $systemname : (string)$usersystem['name'];

  switch($switchvar)
  {
    case 'Invision Power Board 2':  include('./forumintegration/ipb2.php');       break;
    case 'Invision Power Board 3':  include('./forumintegration/ipb3.php');       break;
    case 'MyBB':                    include('./forumintegration/mybb.php');       break; //SD370
    case 'phpBB2':                  include('./forumintegration/phpbb2.php');     break;
    case 'phpBB3':                  include('./forumintegration/phpbb3.php');     break;
    case 'punBB':                   include('./forumintegration/punbb.php');      break; //SD370
    case 'vBulletin 3':             include('./forumintegration/vbulletin3.php'); break;
    case 'vBulletin 4':             include('./forumintegration/vbulletin4.php'); break;
    case 'Simple Machines Forum 1': include('./forumintegration/smf1.php');       break;
    case 'Simple Machines Forum 2': include('./forumintegration/smf2.php');       break;
    case 'XenForo 1':               include('./forumintegration/xenforo1.php');   break;
  }
}

// ######################### UPDATE SYSTEM SETTINGS ###########################

function UpdateSystemSettings()
{
  global $DB, $dbname, $forum_error, $systemname, $sd_charset, $mainsettings;

  $forumfolderpath = GetVar('forumfolderpath','','string',true,false);

  // update forum's to use these values:
  $cookiedomain = '';
  $cookiepath   = '/';

  if($systemname == 'Subdreamer')
  {
    $DB->query("UPDATE {usersystems} SET activated = '0' WHERE activated = '1'");
    $DB->query("UPDATE {usersystems} SET activated = '1' WHERE name = 'Subdreamer'");
  }
  else
  if(strlen($forumfolderpath))
  {
    // fix the systemfolderpath, first get rid of the wrong slash
    $forumfolderpath = str_replace('\\\\', '/', trim($forumfolderpath));

    // now get rid of the starting slash
    if($forumfolderpath{0} == '/')
    {
      $forumfolderpath = substr($forumfolderpath, 1);
    }

    // now add a last slash
    if(substr($forumfolderpath, -1) != '/')
    {
      $forumfolderpath .= '/';
    }

    // get forum details and upgrade database
    $forum_error = '';
    if($forumsystem = GetForumSystem($forumfolderpath, $dbname))
    {
      $DB->query("UPDATE {usersystems}
        SET dbname        = '" . $forumsystem['dbname']        . "',
            tblprefix     = '" . $forumsystem['tblprefix']     . "',
            folderpath    = '" . $forumsystem['folderpath']    . "',
            cookietimeout = '" . $forumsystem['cookietimeout'] . "',
            cookieprefix  = '" . $forumsystem['cookieprefix']  . "',
            cookiedomain  = '" . $forumsystem['cookiedomain']  . "',
            cookiepath    = '" . $forumsystem['cookiepath']    . "',
            extra         = '" . $forumsystem['extra']         . "'
        WHERE name        = '" . $systemname . "'");

      echo '<h3 class="header blue lighter">' . $forumsystem['name'] . ' Usergroup Configuration' . '</h3>';
      echo '<div class="well well-lg">
          <h4 class="blue lighter">Please read these instructions carefully</h4>
          <p>
          In order to successfully integrate with <strong>' . $forumsystem['name'] . '</strong>, you must associate its usergroups with Subdreamer\'s usergroups.<br />
          <b>Meaning from now on - once this page is submitted - you will use your Forum Admin account to login in to Subdreamer!</b><br /><br />
          Subdreamer will assign the <u>Subdreamer Guests Usergroup</u> to any forum users that are in a forum usergroup which has not been
          associated with a Subdreamer Usergroup (see below step 4).<br /><br />
		  <ol>
		  <li>Associate Forum Usergroups with Subdreamer Usergroups. (Admin &raquo; Admin, Guests &raquo; Guests, etc...)</li>
          <li>Subdreamer will log you out.</li>
          <li>Login with your Forum Admin account.</li>
          <li>Make sure to create in "Users|Usergroups" for each mapped forum
          usergroup also a Subdreamer usergroup or otherwise users in
          these usergroups will be treated as Guests only!
          Meaning all to-be-used, non-predefined forum usergroups have to
          have an equal usergroup in Subdreamer.</li>
		  </ol>
		  </p>';

      if($forumsystem['name'] == 'phpBB3')
      {
        echo '<h4 class="blue lighter">phpBB3 NOTES:</h4>
        <ol>
        <li>Pre-defined phpBB3 groups are: Administrators, Bots, Global Moderators, Guests, Registered and Register_coppa.</li>
        <li>If you upgraded from phpBB2, please be aware, that you may have in addition to phpBB3\'s "ADMINISTRATORS"
        group also an imported "phpBB2 - Administrators" group, which by default
        has NO privileges as it first needs to be created and configured in Subdreamer as well.</li>
        <li>Unless configured correctly, it is advised to use phpBB3\'s ADMINISTRATORS
        group at first integration or you won\'t be able to log in as Subdreamer
        admin after submitting this page, especially true for any "imported" usergroup.</li>
        <li>It is advised to clear your browser cookies to ensure session is created properly with new settings.</li>
        ';
      }

      if(substr($forumsystem['name'],20) == 'Invision Power Board')
      {
        echo '<br /><b>NOTE:</b> Invision Power Board users can match the
              Administrators group in Subdreamer with "Root Admin" or
              "Administrators" groups.<br />
              Ensure you have accounts populated in these groups before integration,
              or you may get locked out of Subdreamer.';
      }
	  
	  echo '</div>';

   
      // get usergroups
      $getusergroups = $DB->query('SELECT usergroupid, name, forumusergroupid FROM {usergroups} ORDER BY usergroupid');

      switch($forumsystem['name'])
      {
        case 'Invision Power Board 2':
        case 'Invision Power Board 3':
          $forumusergroup_sql = 'SELECT g_id AS usergroupid, g_title AS usergroupname FROM ' . $forumsystem['tblprefix'] . 'groups ORDER BY g_id';
        break;

        case 'MyBB': //SD370
          $forumusergroup_sql = 'SELECT gid AS usergroupid, title AS usergroupname FROM ' . $forumsystem['tblprefix'] . "usergroups WHERE type = 1 AND title <> '' ORDER BY gid";
        break;

        case 'phpBB2':
          $forumusergroup_sql = 'SELECT group_id AS usergroupid, group_name AS usergroupname FROM ' . $forumsystem['tblprefix'] . "groups WHERE group_name <> '' ORDER BY group_id"; //AND group_type <> 1
        break;
        case 'phpBB3':
          $forumusergroup_sql = 'SELECT group_id AS usergroupid, group_name AS usergroupname FROM ' . $forumsystem['tblprefix'] . "groups WHERE group_name <> '' ORDER BY group_id";
        break;

        case 'punBB': //SD370
          $forumusergroup_sql = 'SELECT g_id AS usergroupid, g_title AS usergroupname FROM ' . $forumsystem['tblprefix'] . "groups WHERE g_title <> '' ORDER BY g_id";
        break;

        case 'Simple Machines Forum 1':
          $forumusergroup_sql = 'SELECT ID_GROUP AS usergroupid, groupName AS usergroupname FROM ' . $forumsystem['tblprefix'] . 'membergroups ORDER BY ID_GROUP';
        break;

        case 'Simple Machines Forum 2':
          $forumusergroup_sql = 'SELECT id_group AS usergroupid, group_name AS usergroupname FROM ' . $forumsystem['tblprefix'] . 'membergroups ORDER BY id_group';
        break;

        case 'vBulletin 3':
        case 'vBulletin 4':
          $forumusergroup_sql = 'SELECT usergroupid AS usergroupid, title AS usergroupname FROM ' . $forumsystem['tblprefix'] . 'usergroup ORDER BY usergroupid';
        break;

        case 'XenForo 1': //SD342
          $forumusergroup_sql = 'SELECT user_group_id AS usergroupid, title AS usergroupname FROM ' . $forumsystem['tblprefix'] . 'user_group ORDER BY user_group_id';
        break;
      }

      // switch database? $dbname is include from config.php
      if($forumsystem['dbname'] != $dbname)
      {
        $DB->select_db($forumsystem['dbname']);
      }
      $forumusergroupid = array();
      $forumusergroupname = array();
      $getforumusergroups = $DB->query($forumusergroup_sql);

      if(is_resource($getforumusergroups) && (false !== $getforumusergroups))
      {
        if(false !== $DB->get_num_rows($getforumusergroups))
        {
          $to_charset = !empty($mainsettings['forum_character_set']) ? $mainsettings['forum_character_set'] : $sd_charset;
          $do_conv = (!empty($sd_charset) && function_exists('sd_GetConverter') &&
                      !empty($to_charset) && ($to_charset !== $sd_charset));
          if($do_conv)
          {
            // Create new object for forum character set
            $convobj = sd_GetConverter($to_charset, $sd_charset,1);
            if(defined('SD_CVC'))
            {
              if(!isset($convobj) || !isset($convobj->CharsetTable))
              {
                $do_conv = false;
              }
            }
          }
          for($i = 0; $forumusergroup = $DB->fetch_array($getforumusergroups); $i++)
          {
            $forumusergroupid[$i] = $forumusergroup['usergroupid'];
            if($do_conv && is_object($convobj))
            {
              $forumusergroupname[$i] = $convobj->Convert($forumusergroup['usergroupname']);
            }
            else
            {
              $forumusergroupname[$i] = $forumusergroup['usergroupname'];
            }
          }
        }
      }

      // switch back to subdreamer database? $dbname is include from config.php
      if($forumsystem['dbname'] != $dbname)
      {
        $DB->select_db($dbname);
      }

      if(empty($forumusergroupid))
      {
        echo '<h2 style="text-align: center"><strong>No usergroups retrievable from Forum database!</strong><br /></h2>';
      }
      else
      {
        echo '
        <form method="post" action="forumintegration.php">
        <input type="hidden" name="action" value="updatesystemsettings2" />
        <input type="hidden" name="forumname" value="' . $forumsystem['name'] . '" />';
		StartTable('Associate Usergroups', array('table', 'table-bordered', 'table-striped'));
		echo'
		<thead>
        <tr>
          <th >Subdreamer Usergroups</th>
          <th class="center"><i class="ace-icon fa fa-angle-double-right blue bigger-110"></i></th>
          <th>' . $forumsystem['name'] . ' Usergroups</th>
        </tr>
		</thead>
		<tbody>';

        while($usergroup = $DB->fetch_array($getusergroups))
        {
          echo '
          <tr>
            <td class="tdrow2" width="15%">' . $usergroup['name'] . '</td>
            <td class="tdrow3" width="5%" align="center"><i class="ace-icon fa fa-angle-double-right blue bigger-110"></i></td>
            <td class="tdrow2" width="45%">
              <select  name="subdreamerusergroupids[' . $usergroup['usergroupid'] . ']" class="form-control">
              <option value=""></option>';

          if($forumsystem['name'] == 'phpBB2')
          {
            echo '
              <option value="-3">Registered Users*</option>';
          }

          for($i = 0, $fgc = count($forumusergroupid); $i < $fgc; $i++)
          {
            echo '
              <option '.($usergroup['forumusergroupid']==$forumusergroupid[$i]?'selected="selected" ':'').
              'value="' . $forumusergroupid[$i] . '">' . $forumusergroupname[$i] . '</option>';
          }

          echo '
              </select>
            </td>
          </tr>';
        } //while
       
	    echo '</table>
		</div>
			<div class="center">
				<button class="btn btn-info" type="submit"><i class="ace-icon fa fa-check bigger-120"></i> ' . AdminPhrase('complete_integration') . '</button>
			</div>';
      }
		echo '
      </form>';
    }
    else
    {
      if(empty($forum_error))
      {
        $errors[] = 'Subdreamer could not locate your ' . $systemname . ' forum, please make sure the path entered is correct.';
      }
      else
      {
        $errors[] = $forum_error;
      }
    }
  }
  else
  {
    $errors[] = 'Subdreamer could not locate your ' . $systemname . ' forum, please make sure the path entered is correct.';
  }

  if(isset($errors))
  {
    PrintErrors($errors, 'Forum Integration Errors');
    DisplayDefault();
  }
  else if($systemname == 'Subdreamer')
  {
    RedirectPage('forumintegration.php');
  }

} //UpdateSystemSettings


// ###################################
if($action == 'updatesystemsettings2')
// ###################################
{
  if(!strlen($forumname))
  {
    RedirectPage('forumintegration.php', 'ERROR: Invalid Forum Name!', 3);
    exit();
  }

  $subdreamerusergroupids = GetVar('subdreamerusergroupids', null, 'array', true, false);

  // update settings
  if(is_array($subdreamerusergroupids))
  {
    foreach($subdreamerusergroupids as $key => $value)
    {
      if(empty($value))
      {
        $value = 0;
      }
      if(is_numeric($key) && is_numeric($value))
      {
        $DB->query("UPDATE {usergroups} SET forumusergroupid = '%s' WHERE usergroupid = %d",$value,$key);
      }
    }

    $DB->query("UPDATE {usersystems} SET activated = '0' WHERE activated = '1'");
    $DB->query("UPDATE {usersystems} SET activated = '1' WHERE name = '%s'",$forumname);

    //SD350: clear cache to avoid issues on frontpage
    if(isset($SDCache) && ($SDCache instanceof SDCache))
    {
      $SDCache->purge_cache();
    }

    RedirectPage('forumintegration.php?logout=1');
  }
} //updatesystemsettings2


// ############################### PRINT DEFAULT ###############################

function DisplayDefault()
{
  global $DB, $systemname;

  $usersystem = $DB->query_first("SELECT * FROM {usersystems} WHERE activated = '1'");

  $usersystem['folderpath'] = isset($_POST['forumfolderpath']) ? $_POST['forumfolderpath'] : $usersystem['folderpath'];
  $usersystem['name']       = strlen($systemname) ? $systemname: $usersystem['name'];

 echo '<h3 class="header blue lighter">' . AdminPhrase('forum_integration') . '</h3>';

  echo '
  <form method="post" action="forumintegration.php" class="form-horizontal" id="fiForm">
  <input type="hidden" name="action" value="updatesystemsettings" />
  <div class="form-group">
  	<label class="control-label col-sm-2" for="systemname">' . AdminPhrase("select_your_forum") . '
	<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="' . AdminPhrase('select_forum_desc') . '" title="Help">?</span>
	</label>
	<div class="col-sm-9">
     <select name="systemname" class="form-control">
       <option value="Subdreamer"              ' . ($usersystem['name'] == 'Subdreamer'?              'selected="selected"': '') . '>Off</option>
       <option value="Invision Power Board 2"  ' . ($usersystem['name'] == 'Invision Power Board 2'?  'selected="selected"': '') . '>Invision Power Board 2</option>
       <option value="Invision Power Board 3"  ' . ($usersystem['name'] == 'Invision Power Board 3'?  'selected="selected"': '') . '>Invision Power Board 3</option>
       <option value="MyBB"'./* SD370 */'      ' . ($usersystem['name'] == 'MyBB'?                    'selected="selected"': '') . '>MyBB</option>
       <option value="phpBB2"                  ' . ($usersystem['name'] == 'phpBB2'?                  'selected="selected"': '') . '>phpBB2</option>
       <option value="phpBB3"                  ' . ($usersystem['name'] == 'phpBB3'?                  'selected="selected"': '') . '>phpBB3</option>
       <option value="punBB"'./* SD370 */'     ' . ($usersystem['name'] == 'punBB'?                   'selected="selected"': '') . '>punBB</option>
       <option value="Simple Machines Forum 1" ' . ($usersystem['name'] == 'Simple Machines Forum 1'? 'selected="selected"': '') . '>Simple Machines Forum 1</option>
       <option value="Simple Machines Forum 2" ' . ($usersystem['name'] == 'Simple Machines Forum 2'? 'selected="selected"': '') . '>Simple Machines Forum 2</option>
       <option value="vBulletin 3"             ' . ($usersystem['name'] == 'vBulletin 3'?             'selected="selected"': '') . '>vBulletin 3</option>
       <option value="vBulletin 4"             ' . ($usersystem['name'] == 'vBulletin 4'?             'selected="selected"': '') . '>vBulletin 4</option>
       <option value="XenForo 1"               ' . ($usersystem['name'] == 'XenForo 1'?               'selected="selected"': '') . '>XenForo 1</option>
     </select>
    </div>
</div>
<div class="form-group">
  	<label class="control-label col-sm-2" for="systemname">' . AdminPhrase("forum_folder_path") . '
	<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="' . AdminPhrase('forum_folder_path_desc') . '" title="Help">?</span>
	</label>
	<div class="col-sm-9">
  	<input type="text" class="form-control" name="forumfolderpath" value="'.CleanFormValue($usersystem['folderpath']).'" />
</div>
</div>';

  PrintSubmit('updatesystemsettings', 'Save Settings','fiForm','fa-check');

  echo '
  </form>';

} //DisplayDefault


// ############################## SELECT FUNCTION ##############################

switch($action)
{
  case 'updatesystemsettings':
    UpdateSystemSettings();
  break;

  default:
    if($action != 'updatesystemsettings2')
    {
      DisplayDefault();
    }
}

DisplayAdminFooter();
