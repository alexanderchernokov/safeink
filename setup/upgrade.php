<?php
// ############################################################################
// DEFINE CONSTANTS
// ############################################################################
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');
define('UPGRADING_PRGM', true);
define('SETUPDIR', basename(getcwd()));
define('DS', DIRECTORY_SEPARATOR);
define('ADMIN_STYLES_FOLDER', 'ace');

// ############################################################################
// INCLUDE INSTALL FUNCTIONS AND INIT PRGM
// ############################################################################
if(!@include(ROOT_PATH . 'includes/init.php'))  // also includes prgm_info file
{
  echo '<div style="border: 1px solid red; padding: 20px;"><span style="font-size: 20px; font-weight: bold;">Required upgrade file(s) not found!!</span></div>';
  exit();
}
require(ROOT_PATH . SETUPDIR . '/setup_functions.php');
require_once(ROOT_PATH . 'includes/functions_admin.php');

// ################ CURRENT DIRECTORY AND LANGUAGE FILE #######################
define('CURRENTDIR', basename(dirname(__FILE__)).DS);
$currentdir = CURRENTDIR;
$lang = array();
require(ROOT_PATH.CURRENTDIR.'lang/default.php');


// ############################################################################
// DISPLAY SETUP HEADER
// ############################################################################

DisplaySetupHeader();

// SD400: Check for pre release
sd_isPreRelease(PRGM_VERSION);


// ############################################################################
// UPGRADE PRGM
// ############################################################################
// new_version: version being upgraded to
// upgrade_file: filename that will be used to upgrade

function UpgradePrgm($new_version, $upgrade_file = '')
{
  global $DB, $setupdir, $safeMode,
         $lang_upgrade_step, $lang_successfully_upgraded_to_version,
         $lang_err_loading_upgrade_file, $lang_err_upgrade_file_not_found;

  if(!$safeMode) @set_time_limit(300);

  $result = $new_version;
  sd_StartSetupMessages($lang_upgrade_step.' '.$new_version);

  if(strlen($upgrade_file))
  {
    $upgrade_file = ROOT_PATH . CURRENTDIR . 'upgrade_files/' . $upgrade_file;
    if(is_file($upgrade_file))
    {
      if(@include($upgrade_file))
      {
        $DB->query("UPDATE {mainsettings} SET value = '%s' WHERE varname = 'sdversion'", $DB->escape_string($new_version));
        sd_DisplaySetupMessage('<strong>'.$lang_successfully_upgraded_to_version. ' ' . $new_version . '</strong><br />');
        $result = $new_version;
      }
      else
      {
        sd_DisplaySetupMessage('<strong>'.$lang_err_loading_upgrade_file.' '.$new_version.'</strong>');
        $result = CURR_PRGM_VERSION;
      }
    }
    else
    {
      sd_DisplaySetupMessage('<strong>'.$lang_err_upgrade_file_not_found.' ' . $new_version . '</strong>');
    }
  }
  sd_EndSetupMessages();

  return $result;
} //UpgradePrgm


// ############################################################################

function PrintPreviousUpgrades()
{
  global $curr_prgm_version, $reapply_versions;

  echo '
    <center><div style="text-align: center; border: 1px solid #d0d0d0; padding: 10px; margin: 10px; width: 600px">
    <h2>Re-Apply Upgrade Steps</h2>
    <strong>Important Information:</strong><br />
    The below upgrade step(s) are available (if required).<br />
    Reapplying recent upgrade steps will assure that all newer
    settings are correctly added to and/or obsolete settings removed
    from the ' . PRGM_NAME . ' settings and plugins.<br />
    This is usually only necessary if instructed or for release candidates,<br />
    but not for regular upgrades from one version to another!<br />
    ';
  if($curr_prgm_version == PRGM_VERSION)
  {
    echo '<br />
    <form method="post" action="'. ROOT_PATH . SETUPDIR . '/upgrade.php">
    '.PrintSecureToken();
    foreach($reapply_versions as $version)
    {
      if(version_compare($version,'3.5.0','ge'))
      echo '
      <input type="radio" name="v" value="'.$version.'"'.($version==PRGM_VERSION?' checked="checked" ':'').
      '/> <span class="label label-success" style="line-height:18px;vertical-align:top;margin:4px">Version '.
      $version.'</span><br />';
    }
    echo '
    <input class="btn btn-large btn-primary" type="submit" name="upgrade" value="Re-Apply Upgrade Steps Now" />
    </form>
    ';
  }
  // Check if "cache" folder exists and is writable
  if(!is_dir(ROOT_PATH.'cache'))
  {
    echo '<hr /><h4>Cache folder "cache" does not exist, please create it
    within an FTP client and set it\'s permissions to 0777.</h4>';
  }
  else
  if(!is_writable(ROOT_PATH.'cache'))
  {
    echo '<hr /><h4>Cache folder "cache" is not writable, please set it\'s
    permissions to 0777 within an FTP client.</h4>';
  }
  echo '</div></center><br />';

} //PrintPreviousUpgrades


// ############################################################################
// Check for re-apply version parameter
// ############################################################################
$reapply_versions = array('3.3.0','3.3.1','3.4.0','3.4.1','3.4.2','3.4.3', '3.5.0','3.6.0','3.6.1','3.7.0','3.7.1','4.0.0b1','4.0.0b2','4.0.0','4.1.0','4.2.0','4.2.1');

unset($curr_prgm_version_arr);
$do_reapply = false;
if(isset($_POST['v']) && in_array($_POST['v'], $reapply_versions))
{
  if(!CheckFormToken())
  {
    DisplayMessage($sdlanguage['error_invalid_token'],true);
    exit();
  }
  $do_reapply = true;
  if(isset($_POST['upgrade']))
  {
    define('CURR_PRGM_VERSION', $_POST['v']);
  }
}
else
{
  unset($_POST['v']);
}

// ############################################################################
// GET CURR PRGM VERSION
// ############################################################################

if(!defined('CURR_PRGM_VERSION'))
{
  $DB->ignore_error = true;
  if($curr_prgm_version_arr = $DB->query_first("SELECT value FROM {mainsettings} WHERE varname = 'sdversion'"))
  {
    define('CURR_PRGM_VERSION', $curr_prgm_version_arr['value']);
  }
  unset($curr_prgm_version_arr);
}

if(!defined('CURR_PRGM_VERSION'))
{
  echo '<div id="error_message" class="alert alert-danger">ERROR: Current version cannot be determined! Please contact support!</div>';
  DisplaySetupFooter();
}

$safeMode = sd_safe_mode();
$curr_prgm_version = CURR_PRGM_VERSION;

if(version_compare($curr_prgm_version,'3.3','ge') && empty($userinfo['adminaccess']))
{
  echo '<div id="error_message" class="alert alert-danger">
  ERROR: You must be currently logged-in within the Admin panel to run an upgrade!</div>';
  DisplaySetupFooter();
}


// ############################################################################
// START UPGRADE OR DISPLAY UPGRADE FORM
// ############################################################################
if(isset($_POST['upgrade']))
{
  StartSection(PRGM_NAME . ' Upgrade ' . PRGM_VERSION);

  echo '<div id="upgrade_steps" style="text-align: left; border: 1px solid #d0d0d0; padding: 8px; margin-left:auto; margin-right:auto; overflow: auto; overflow-x: hidden; overflow-y: scroll; max-height: 300px; height: 300px; width: 100%">';
 
  $curr_prgm_version = CURR_PRGM_VERSION;

  $db_is_UTF8 = (strpos(strtolower(GetCurrentDBCharset()),'utf8')!==false);
  if(!$db_is_UTF8)
  {
    if(!empty($_POST['convert_db']))
    {
      sd_DisplaySetupMessage('<strong>Setting database character set, please wait...</strong>');
      $DB->query('ALTER DATABASE `'.$DB->database.'` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci');
      sd_DisplaySetupMessage('<strong>Database character set changed.</strong><br />');
    }
    if(!empty($_POST['convert_tables']))
    {
      sd_DisplaySetupMessage('<strong>Converting tables to utf8 now, please wait...</strong>');
      foreach($DB->table_names_arr[$DB->database] as $table)
      {
        $DB->query('ALTER TABLE {'.$table.'} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        echo $table.', ';
      }
      sd_DisplaySetupMessage('<br /><strong>Table conversion done.</strong><br />');
    }
  }

  // upgrade prgm to latest version
  if($do_reapply || (CURR_PRGM_VERSION != PRGM_VERSION))
  {
    $curr_prgm_version = CURR_PRGM_VERSION;

    // upgrade to 3.0.0
    if(version_compare($curr_prgm_version,'3.0.0','<'))
    {
      $curr_prgm_version = UpgradePrgm('3.0.0', '300.php');
    }

    // upgrade to 3.0.1
    if($curr_prgm_version == '3.0.0')
    {
      $curr_prgm_version = UpgradePrgm('3.0.1', '301.php');
    }

    // upgrade to 3.0.2
    if($curr_prgm_version == '3.0.1')
    {
      $curr_prgm_version = UpgradePrgm('3.0.2', '302.php');
    }

    // upgrade to 3.0.3
    if($curr_prgm_version == '3.0.2')
    {
      $curr_prgm_version = UpgradePrgm('3.0.3', '303.php');
    }

    // upgrade to 3.0.4
    if($curr_prgm_version == '3.0.3')
    {
      $curr_prgm_version = UpgradePrgm('3.0.4', '304.php');
    }

    // upgrade to 3.1.0
    if($curr_prgm_version == '3.0.4')
    {
      $curr_prgm_version = UpgradePrgm('3.1.0', '310.php');
    }

    // upgrade to 3.1.1
    if($curr_prgm_version == '3.1.0')
    {
      $curr_prgm_version = UpgradePrgm('3.1.1', '311.php');
    }

    // upgrade to 3.1.2
    if($curr_prgm_version == '3.1.1')
    {
      $curr_prgm_version = UpgradePrgm('3.1.2', '312.php');
    }


    if(!defined('TABLE_PREFIX') && defined('PRGM_TABLE_PREFIX')) define('TABLE_PREFIX', PRGM_TABLE_PREFIX); //SD342
    array_unshift($reapply_versions, '3.2.0','3.2.1');
    $prev_step = '';
    foreach($reapply_versions as $upgrade_step)
    {
      if(($do_reapply && CURR_PRGM_VERSION <= $upgrade_step) || (!$do_reapply && ($curr_prgm_version == $prev_step)) ||
         (substr($curr_prgm_version,0,3) < substr($upgrade_step,0,3))) // 3.2.1 or 3.2.2 -> 3.3.0
      {
        $curr_prgm_version = UpgradePrgm($upgrade_step, str_replace('.','',$upgrade_step).'.php');
      }
      $prev_step = $upgrade_step;
    }
	
	// Optimize 
  	include('upgrade_files/optimize.php');
  }

  $DB->query("UPDATE {adminphrases} SET defaultphrase = REPLACE(defaultphrase, 'sd_pagebreak', concat(char(123),'pagebreak',char(125))),
             customphrase = REPLACE(customphrase, 'sd_pagebreak', concat(char(123),'pagebreak',char(125)))
             WHERE pluginid = 2 AND varname = 'pagebreak_description'");

  // If all went ok, the current version now equals PRGM_VERSION -> display success message
  if($curr_prgm_version == PRGM_VERSION)
  {
    $mainsettings['sdversion'] = PRGM_VERSION; // needed for install scripts
    $mainsettings_sdversion = PRGM_VERSION; // needed for install scripts
    //SD341: new: install main plugins and upgrade other installed plugins automatically:
    $upgrade_file = ROOT_PATH . CURRENTDIR . 'upgrade_files/plugin_upgrades.php';
    if(file_exists($upgrade_file))
    {
      echo '<h4>*** Automatic Plugin Upgrades ***</h4>';
      require($upgrade_file);
    }

    // Purge all cache files (if possible)
    if(isset($SDCache) && $SDCache && ($SDCache instanceof SDCache))
    {
      $SDCache->purge_cache();
      echo '<br /><i class="ace-icon fa fa-angle-double-right"></i> Cache folder purged.';
    }
	
	 echo '
    <h4 class="header blue lighter">Please do not forget:</h4>
    <i class="ace-icon fa fa-angle-double-right"></i> Check the permissions of the file "<strong>includes/config.php</strong>" being 0644 in FTP program.<br /><br />
    <i class="ace-icon fa fa-angle-double-right"></i> Password protect OR delete the folder "<strong>setup</strong>" <span style="color:red; font-weight: bold;">immediately</span>!<br /><br />
    <i class="ace-icon fa fa-angle-double-right"></i> For caching, templating, articles and other plugins to work, set the permissions of the following folders to 0777 within your FTP client as well:<br />
    <ul>
    <li><strong>/attachments</strong></li>
    <li><strong>/cache</strong></li>
    <li><strong>/images/avatars</li>
    <li><strong>/images/articlethumbs</li>
    <li><strong>/images/featuredpics</li>
    <li><strong>/images/profiles</li>
    <li><strong>/includes/tmpl/cache</strong></li>
    <li><strong>/includes/tmpl/comp</strong></li>
    <li><strong>/plugins/forum/attachments</strong></li>
    <li><strong>/plugins/forum/images</strong></li>
    <li><strong>/plugins/download_manager/ftpfiles</strong></li>
    <li><strong>/plugins/download_manager/images</strong></li>
    <li><strong>/plugins/download_manager/import</strong></li>
    <li><strong>/plugins/media_gallery/upload</strong></li>
    <li><strong>/plugins/media_gallery/images</strong></li>
    <li><strong>/plugins/p17_image_gallery/images</strong></li>
    <li><strong>/plugins/p17_image_gallery/upload</strong></li>
    </ul>
    <br />
    <i class="ace-icon fa fa-angle-double-right"></i> Also, PDF export of articles requires a separate download! Please see file "/includes/html2pdf/DOWNLOAD_read_me.txt" for instructions!<br /><br />
    ';

    echo '</div>';
    #echo '<hr />';

    if(!$do_reapply && (CURR_PRGM_VERSION == PRGM_VERSION))
    {
      $msg = '<strong>' . PRGM_NAME . ' is already current.</strong>';
    }
    else
    {
      $msg = 'Upgrade completed!';
    }
    echo '
   		<div class="space-20"></div>
     	<div class="alert alert-success">
         '.$msg.' <br /> Go to your <a class="text-primary" href="' . ROOT_PATH . 'admin/index.php">Administration Panel</a>
          or <a class="text-primary"  href="' . ROOT_PATH . 'index.php">view your website</a> now.
        </div>';
  }
  else
  {
    // Display error since the version has not been updated
    echo '<strong>' . PRGM_NAME . ' Version mismatch or failed to upgrade to version: ' . PRGM_VERSION . '</strong>';
  }

  DisplaySetupFooter();
}

// ############################################################################
// DISPLAY UPGRADE FORM
// ############################################################################
echo '<div id="upgrade_steps">';

ob_start();
$req_error = DisplayRequirementsChecker();
$req_output = ob_get_clean();
$req_err_msg = ' <strong>At least one of the above requirements have FAILED! The upgrade cannot continue</strong>';

if(CURR_PRGM_VERSION == PRGM_VERSION)
{
  if($req_error)
  {
    echo $req_output;
    DisplayMessage($req_err_msg, true);
  }
  else
  {
    DisplayMessage('<center><span style="font-weight:bold;font-size:18px;">'.PRGM_NAME.
    ' is already at current version '.PRGM_VERSION.', no upgrade required.</span>
    <br /><br />
    Please <strong><a title="View Admin Panel" href="' . ROOT_PATH . 'admin/index.php">click here</a></strong> to view your Administration panel
    or <strong><a title="View Website" href="' . ROOT_PATH . 'index.php">click here</a></strong> to view your website.</center>', false);
    PrintPreviousUpgrades();
  }
}
else
{
	echo $req_output;
  if($req_error)
  {
	  
    $message = $req_err_msg;
    if((substr(CURR_PRGM_VERSION,0,2) == '2.') && (substr(CURR_PRGM_VERSION,0,3) != '2.6'))
    {
      $message .= '<br />You must upgrade '.PRGM_NAME.' to version 2.6 first!<br />
      Please follow these steps:<br />
      1) Upload the <a href="http://antiref.com/?http://www.subdreamer.com/docs/index.php?section=upgrading&amp;page=upgrading_from_version_2">'.PRGM_NAME.' 2.6RC3</a>
      files and run the <strong>install/upgrade.php</strong> script first.<br />
      2) Once the upgrade to v2.6 was successfully completed, you must upload the
      '.PRGM_NAME.' v3/4 files and run the <strong>setup/upgrade.php</strong> script!';
    }
    else
    {
      $message .= 'Please <strong><a href="' . ROOT_PATH . 'admin/index.php">click here</a></strong> to view
      your Administration panel or <strong><a href="' . ROOT_PATH . 'index.php">click here</a></strong>
      to view your website.';
    }
    DisplayMessage($message, true);
  }
  else
  {
  echo '
  <div class="clearfix"></div>
	<div class="alert alert-danger"><i class="ace-icon fa fa-exclamation-circle red bigger-120"></i> 
   		Note: Please remember to backup your database before <i>ANY</i> upgrade! This script will upgrade your program to version <strong>' . PRGM_VERSION . '</strong>
	</div>
	 <form method="post" action="'. ROOT_PATH . SETUPDIR . '/upgrade.php">
    '.PrintSecureToken().'
    <div class="center">
        <button class="btn btn-info" type="submit" name="upgrade" value="Begin Upgrade" /><i class="ace-icon fa fa-check"></i> Begin Upgrade</button>
	</div>
      
    </form>
    ';
  }

}

echo '</div>';
DisplaySetupFooter();
