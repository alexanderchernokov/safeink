<?php
// ############################################################################
// DEFINE CONSTANTS
// ############################################################################
define('IN_PRGM', true);
define('ROOT_PATH', '../');
define('INSTALLING_PRGM', true);
define('SETUPDIR', basename(getcwd()));
define('DS', DIRECTORY_SEPARATOR);
define('SITE_URL', '../');

// ############################################################################
// INIT SETUP
// ############################################################################
if(!@include(ROOT_PATH . 'includes/init.php'))  // also includes prgm_info file
{
  echo '<div style="border: 1px solid red; padding: 20px;"><span style="font-size: 20px; font-weight: bold;">Required upgrade file(s) not found!!</span></div>';
  exit();
}
require(ROOT_PATH . SETUPDIR . '/setup_functions.php');
require_once(SD_INCLUDE_PATH . 'xml_parser_php5.php');


$userip = '';
if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND !empty($_SERVER['HTTP_X_FORWARDED_FOR']))
{
  foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $values)
  {
    $values = preg_replace( '/[^0-9a-fA-F:., ]/', '', $values);
    $userip .= long2ip(ip2long($values)) . ' ; ';
  }
  $userip = rtrim($userip,'; ');
}
else
{
  $userip = (!empty($HTTP_SERVER_VARS['REMOTE_ADDR']) ? preg_replace("/^::ffff:/", "", $HTTP_SERVER_VARS['REMOTE_ADDR']) :
            (!empty($HTTP_ENV_VARS['REMOTE_ADDR']) ? $HTTP_ENV_VARS['REMOTE_ADDR'] :
            (isset($_SERVER['REMOTE_ADDR']) ? preg_replace("/^::ffff:/", "", $_SERVER['REMOTE_ADDR']):'unknown')));
  $userip = preg_replace('/[^0-9a-fA-F:., ]/', '', $userip);
  $userip = ($userip == 'unknown') ? $userip : long2ip(ip2long($userip));
}
$userip = addslashes(substr($userip, 0, 40));
define('USER_AGENT', isset($_SERVER['HTTP_USER_AGENT']) ? substr(htmlentities(strip_tags($_SERVER['HTTP_USER_AGENT'])),0,255) : '');
define('USERIP', $userip);
$token = md5(realpath(ROOT_PATH).USER_AGENT);

// ################## CURRENT DIRECTORY AND LANGUAGE FILE #####################
define('CURRENTDIR', basename(dirname(__FILE__)).DS);
$lang = array();
require(ROOT_PATH.CURRENTDIR.'lang/default.php');


// ############################################################################
// Preliminary PHP version check
// ############################################################################
if(version_compare(PHP_VERSION, '5.1.6' /* '5.2.0' */, '<'))
{
  defined('ADMIN_PATH') || define('ADMIN_PATH', 'admin/');
  require(ROOT_PATH . ADMIN_PATH . '/prgm_info.php');
  require(ROOT_PATH . SETUPDIR . '/setup_functions.php');
  DisplaySetupHeader();
  echo '
  <div class="row">
    <div class="span12">
      <div class="alert alert-error"><center>
        <span style="color:red; font-weight: bold; font-size: 22px;">'.$lang_php_version_check_failed.'</span>
        <br><br>'.$lang_php_version_check_msg.'</center>
      </div>
    </div>
  </div>';
  DisplaySetupFooter();
  exit();
}


// ############################################################################
// DISPLAY SETUP HEADER
// ############################################################################

DisplaySetupHeader();

// ############################################################################
// CHECK IF PRGM ALREADY INSTALLED
// ############################################################################
$res = @include(ROOT_PATH . 'includes/config.php');

if($res && empty($_POST['post_install']) && (defined('PRGM_INSTALLED') || defined('SD_INSTALLED')) )
{
  DisplayMessage($lang_err_already_installed_msg, true, $lang_err_already_installed_title);
  DisplaySetupFooter();
}
unset($res);

if(!isset($_GET['continue']) && !isset($_POST['continue']) )
{
	echo '<h3 class="header blue lighter">'.PRGM_NAME.' '.$lang_installation.'</h3>';
}

// ############################################################################
// CHECK CONFIG EXISTS AND IS WRITABLE
// ############################################################################
if(!file_exists(SD_INCLUDE_PATH . 'config.php'))
{
  DisplayMessage($lang_err_config_not_found_msg, true, $lang_err_config_not_found_title);
  DisplaySetupFooter();
}
if(!is_writable(SD_INCLUDE_PATH . 'config.php'))
{
  @chmod(SD_INCLUDE_PATH. 'config.php', intval("0666",8));
  if(!is_writable(SD_INCLUDE_PATH . 'config.php'))
  {
    DisplayMessage($err_config_readonly_msg, true, $lang_err_config_readonly_title);
    DisplaySetupFooter();
  }
}
else
{
  // Also try and actually open the file in case we are running on Windows
  // as there the user may not have access to the file
  // (opening with "a+" makes sure to not delete any existing contents!).
  if(false !== ($fp = @fopen(SD_INCLUDE_PATH . 'config.php', "a+")))
  {
    @fclose($fp);
  }
  else
  {
    DisplayMessage($err_config_readonly_msg, true, $lang_err_config_readonly_title);
    DisplaySetupFooter();
  }
  unset($fp);
}


// ############################################################################
// SETUP VARS/GET $_post VARS
// ############################################################################

$safeMode = sd_safe_mode();
if(!$safeMode) @set_time_limit(300);

$start_install = isset($_POST['install']);
$post_install  = !empty($_POST['post_install']);
if(!$post_install)
{
  $db_server_name   = $start_install ? $_POST['db_server_name']  : 'localhost';
  $db_name          = $start_install ? $_POST['db_name']         : '';
  $db_name          = substr($db_name,0,50);
  $db_username      = $start_install ? $_POST['db_username']     : '';
  $db_username      = substr($db_username,0,50);
  $db_password      = $start_install ? $_POST['db_password']     : '';
  $db_password      = substr($db_password,0,60);
  $db_table_prefix  = $start_install ? $_POST['db_table_prefix'] : DEFAULT_TABLE_PREFIX;
  $db_table_prefix  = substr($db_table_prefix,0,10);
}
$admin_username     = GetVar('admin_username', '','string',true,false);
$admin_username     = substr($admin_username,0,13);
$admin_password     = GetVar('admin_password', '','string',true,false);
$admin_password     = substr($admin_password,0,40);
$admin_email        = GetVar('admin_email', '','string',true,false);;
$admin_email        = substr($admin_email,0,128);

$optionsDemoData    = empty($_POST['optionsDemoData'])?0:1;
$email_as_technical = empty($_POST['email_as_technical'])?0:1;

// ############################################################################
// CHECK FOR ERRORS
// ############################################################################

if(isset($_POST['install']) || $post_install)
{
  if(GetVar('form_token','','string',true,false) != $token)
  {
    echo '<div class="alert alert-danger"><b>Sorry, invalid request!</b></div>';
  }

  $install_errors = array();
  if($post_install)
  {
    require_once(ROOT_PATH . 'includes/config.php');
    $db_server_name = $database['server_name'];
    $db_name        = $database['name'];
    $db_username    = $database['username'];
    $db_password    = $database['password'];
    $db_table_prefix = PRGM_TABLE_PREFIX;
    unset($database);
  }
  else
  {
    define('PRGM_TABLE_PREFIX', $db_table_prefix);
    define('TABLE_PREFIX',      $db_table_prefix);

    if(!strlen($admin_username) || (strlen($admin_username) < 4))
    {
      $install_errors[] = $lang_err_admin_name_too_short;
    }
    if(!strlen($admin_password) || (strlen($admin_password) < 6))
    {
      $install_errors[] = $lang_err_admin_pwd_too_short;
    }
    if(!strlen($db_name) || !strlen($db_username))
    {
      $install_errors[] = $lang_err_credentials_incomplete;
    }
  }

  if(empty($install_errors) && !isset($DB))
  {
    require_once(ROOT_PATH . 'includes/mysql.php');

    $DB = new DB;

    $DB->server   = $db_server_name;
    $DB->database = $db_name;
    $DB->user     = $db_username;
    $DB->password = $db_password;

    if(!$DB->connect(true)) //SD343: check for connection, but do not error here
    {
      $install_errors[] = $lang_err_connection_failed;
      unset($DB);
    }
    else
    {
      // clear out the username and password for protection
      $DB->user = '';
      $DB->password = '';
    }
  }
}

if(!defined('TABLE_PREFIX') && defined('PRGM_TABLE_PREFIX')) define('TABLE_PREFIX', PRGM_TABLE_PREFIX); //SD342


// ############################################################################
// START INSTALL PROCESS (INSERT DATA INTO DATABASE)
// ############################################################################

if($post_install && empty($install_errors))
{
  // ##########################################################################
  // POST-INSTALL PROCESS (RUN UPGRADES, DEMO DATA)
  // ##########################################################################
  if(GetVar('form_token','','string',true,false) != $token)
  {
    echo '<div class="alert alert-danger"><b>Sorry, invalid request!</b></div>';
  }
  echo '
  <div style="text-align: left; border: 1px solid #d0d0d0; padding: 8px; margin-left:auto; margin-right:auto; overflow: auto; overflow-x: hidden; overflow-y: scroll; max-height: 350px; height: 650px; width: 100%">';
  $sd_upgrades = array('3.0.0','3.0.1','3.0.2','3.0.3','3.0.4','3.1.0','3.1.1','3.1.2', '3.2.0','3.2.1','3.3.0','3.3.1','3.4.0','3.4.1','3.4.2','3.4.3','3.5.0','3.6.0','3.6.1','3.7.0','3.7.1','4.0.0b1','4.0.0b2','4.0.0','4.1.0','4.2.0','4.2.1');
  foreach($sd_upgrades as $upgrade_step)
  {
    if(($upgrade_step=='3.2.0') && !$safeMode) @set_time_limit(300);
    sd_DisplaySetupMessage('<h3>'.$lang_upgrading_to.' '.$upgrade_step.' ...</h3>',true);
    if(include('upgrade_files/'.str_replace('.','',$upgrade_step).'.php'))
    {
      $DB->query("UPDATE {mainsettings} SET value = '".$upgrade_step.
                 "' WHERE varname = 'sdversion'");
    }
    $mainsettings['sdversion'] = $upgrade_step;
    echo '<br />';
  }
  
  // Optimize 
  include('upgrade_files/optimize.php');
  
  //SD370: set and use salt for improved security
  $salt = sd_generate_user_salt();
  $DB->query("INSERT INTO {users} (`userid`, `usergroupid`, `username`, `password`, `salt`, `use_salt`, `email`, `banned`, `activated`, `validationkey`, `joindate`, `lastactivity`, `admin_notes`, `user_notes`) VALUES
    (NULL, 1, '".$admin_username."', '".md5($salt.md5(sd_unhtmlspecialchars($admin_password)))."', '".$DB->escape_string($salt)."', 1, '".$DB->escape_string($admin_email)."', 0, 1, '', ".TIME_NOW.", 0, '', '')");
  $DB->query("UPDATE {usergroups} SET pluginviewids = '1' WHERE name = 'Banned'");

  if(!empty($email_as_technical)) //SD360
  {
    $DB->query("UPDATE {mainsettings} SET value = '%s' WHERE varname = 'technicalemail'",
               $DB->escape_string($admin_email));
  }

  $DB->query("UPDATE {adminphrases} SET defaultphrase = REPLACE(defaultphrase, 'sd_pagebreak', concat(char(123),'pagebreak',char(125))),
             customphrase = REPLACE(customphrase, 'sd_pagebreak', concat(char(123),'pagebreak',char(125)))
             WHERE pluginid = 2 AND varname = 'pagebreak_description'");

  //SD341: new: install main plugins and upgrade other installed plugins automatically:
  $upgrade_file = ROOT_PATH . CURRENTDIR . 'upgrade_files/plugin_upgrades.php';
  if(file_exists($upgrade_file))
  {
    require($upgrade_file);
  }

  //SD344: install demo data if enabled
  define('SD_IN_INSTALL', true);
  define('SD_INSTALL_DEMO', true);
  require('./install_data.php');

  echo '
    '.$lang_post_install_hint2.'<br>
    <ul>
    <li><strong>/admin/backup</strong></li>
    <li><strong>/attachments</strong></li>
    <li><strong>/cache</strong></li>
    <li><strong>/images/articlethumbs</strong></li>
    <li><strong>/images/avatars</strong></li>
    <li><strong>/images/featuredpics</strong></li>
    <li><strong>/images/profiles</strong></li>
    <li><strong>/includes/tmpl/cache</strong></li>
    <li><strong>/includes/tmpl/comp</strong></li>
    <li><strong>/plugins/download_manager/ftpfiles</strong></li>
    <li><strong>/plugins/download_manager/images</strong></li>
    <li><strong>/plugins/download_manager/import</strong></li>
    <li><strong>/plugins/forum/attachments</strong></li>
    <li><strong>/plugins/forum/images</strong></li>
    <li><strong>/plugins/media_gallery/upload</strong></li>
    <li><strong>/plugins/media_gallery/images</strong></li>
    <li><strong>/plugins/p17_image_gallery/images</strong></li>
    <li><strong>/plugins/p17_image_gallery/upload</strong></li>
    </ul>
  ';

  // Make sure that not old cache files linger around,
  // so purge all cache files (if possible)
  require(SD_INCLUDE_PATH.'class_cache.php');
  if(class_exists('SDCache'))
  {
    $SDCache = new SDCache();
    $SDCache->SetCacheFolder(defined('SD_CACHE_PATH') ? SD_CACHE_PATH : ROOT_PATH.'cache');
    $SDCache->SetExpireTime(3600);
    $SDCache->purge_cache();
    unset($SDCache);
    echo '<br />&raquo; '.$lang_cache_folder_purged.'<br /><hr />';
  }

  echo '</div></div></div>';
  
  echo '
  <div class="space-20"></div>
    <div class="alert alert-success">
      <strong>'.$lang_install_completed.'<br />'.$lang_post_install_hint1.'</strong>
    </div>';

  DisplaySetupFooter();
}
else
if(isset($_POST['install']) && empty($install_errors))
{
  // ##########################################################################
  // START INSTALL PROCESS (INSERT DATA INTO DATABASE)
  // ##########################################################################
  // reference:
  // tinyint unsigned  = (0->255)
  // smallint unsigned = (0->65,535)
  // mediumint unsigned = (0->16,777,215)
  define('SD_IN_INSTALL', true);
  define('SD_INSTALL_DEMO', false);
  require('./install_data.php');

  // write config file last off in case installation fails
  $configfile = '<' . "?php
# Timestamped: ".date('Y-m-d G:i')."
define('PRGM_INSTALLED', true);
define('PRGM_TABLE_PREFIX', '$db_table_prefix');
define('ADMIN_PATH', 'admin');

\$database = array();
\$database['server_name']  = '$db_server_name';
\$database['name']         = '$db_name';
\$database['username']     = '$db_username';
\$database['password']     = '$db_password';

?" . '>'; //DO NOT CHANGE!

  // write config file
  if(false !== ($filenum = @fopen(SD_INCLUDE_PATH . 'config.php', 'w')))
  {
    ftruncate($filenum, 0);
    fwrite($filenum, $configfile);
    fclose($filenum);

    echo '
<div class="well well-lg">
  <h4 class="blue lighter">Step 2/3: '.$lang_core_install_done.' </h4>
    <form id="install_step_1_done" method="post" action="'.ROOT_PATH . SETUPDIR .'/install.php">
    <input type="hidden" name="post_install" value="1" />
    <input type="hidden" name="optionsDemoData" value="'.$optionsDemoData.'" />
    <input type="hidden" name="email_as_technical" value="'.$email_as_technical.'" />
    <input type="hidden" name="admin_email" value="'.$admin_email.'" />
    <input type="hidden" name="admin_username" value="'.$admin_username.'" />
    <input type="hidden" name="admin_password" value="'.$admin_password.'" />
    <input type="hidden" name="form_token" value="' . $token . '" />
    '.$lang_base_install_done_msg.'
	<br />
        <div id="infotext" class="center">
        '.$lang_install_will_continue.'
       <br />
        <button id="fsubmit" type="submit" name="install" class="btn btn-primary" data-loading-text="' . $lang_please_wait .'">'.addslashes($lang_install_continue_btn2).'</button>
        </div>
</div>
<script type="text/javascript">
//<![CDATA[
var timerID=false;
function ContinueStep2() {
  jQuery("div#infotext").addClass("alert alert-info").html("<span style=\"font-size:16px;font-weight:bold\">'.addslashes($lang_please_wait).'<\/span>&nbsp;<i class=\"ace-icon fa fa-spin fa-spinner blue bigger-200\"></i>");
  jQuery("form#install_step_1_done").submit();
  return false;
}
if (typeof(jQuery) !== "undefined") {
  jQuery(document).ready(function(){
    timerID = window.setTimeout(ContinueStep2, 2000);
	
	$("#fsubmit").on(ace.click_event, function () {
					var btn = $(this);
					btn.button("loading")
					setTimeout(function () {
						btn.button("reset")
					}, 2000)
				});

  })
}
//]]>
</script>
';
  }
  else
  {
    echo $lang_err_install_failed_hint1.'
    <br /><br />
    '.$lang_err_install_failed_hint2.'<br />
    <pre>' . $configfile . '</pre>';
  }
  DisplaySetupFooter();
}


// ############################################################################
// DISPLAY INSTALL FORM
// ############################################################################

if(!isset($_POST['install']) || !empty($install_errors))
{
  if(!isset($_GET['continue']) && !isset($_POST['continue']))
  {
	  sd_isPreRelease(PRGM_VERSION);
  	$req_error = DisplayRequirementsChecker();

	  if($req_error)
	  {
		$message =  $lang_err_requirements_failed;
	
		DisplayMessage($message, true);
		DisplaySetupFooter();
	  }
	  
	  echo '<div class="center">
	  			<a class="btn btn-info" href="'.ROOT_PATH . SETUPDIR .'/install.php?continue">'.$lang_continue.'</a>
			</div>';
  }
  else
  {
  if(!empty($install_errors))
  {
    echo '
    <div class="alert alert-danger"><strong>'.$lang_installation_errors_title.'</strong>
    <br /><br />
    '.$lang_installation_errors_hint1.'<br /><br />';

    for($i = 0; $i < count($install_errors); $i++)
    {
      echo '<strong>' . ($i + 1) . ') ' . $install_errors[$i] . '</strong><br /><br />';
    }

    echo '</div>';
  }

  if($req_error)
  {
    echo '<div class="alert alert-danger">'.$lang_installation_not_possible.'</div>';
  }
  else
  {
    // Installation details form
    echo '

  <h3 class="header blue lighter">'.$lang_step_1_title.'</h3>


<div class="col-sm-6 col-md-offset-3">
    <form class="form-horizontal well" method="post" action="'.ROOT_PATH . SETUPDIR . '/install.php">
	<input type="hidden" name="continue" value="1">
    <input type="hidden" name="form_token" value="' . $token . '" />
	<fieldset>
      <legend class="blue">'.$lang_prompt_database.'</legend>
      <div class="form-group">
        <label class="control-label col-sm-4 col-sm-4" for="db_server_name">'.$lang_prompt_host.'</label>
        <div class="col-sm-6">
          <input type="text" class="form-control" id="db_server_name" name="db_server_name" value="'.$db_server_name.'" />
          <span class="helper-text">'.$lang_prompt_host_hint.'</span>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-sm-4 col-sm-4" for="db_name">'.$lang_prompt_db_name.'</label>
        <div class="col-sm-6">
          <input type="text" class="form-control" maxlength="50" '.(empty($db_name)?'required="required" ':'').'id="db_name" name="db_name" value="'.$db_name.'" />
          <span class="helper-text">'.$lang_prompt_db_name_hint.'</span>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-sm-4 col-sm-4" for="db_username">'.$lang_prompt_db_user.'</label>
        <div class="col-sm-6">
          <input type="text" class="form-control" maxlength="50" '.(empty($db_username)?'required="required" ':'').'id="db_username" name="db_username" value="'.$db_username.'" placeholder="" />
          <span class="helper-text">'.$lang_prompt_db_user_hint.'</span>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-sm-4 col-sm-4" for="db_password">'.$lang_prompt_db_pwd.'</label>
        <div class="col-sm-6">
          <input type="password" class="form-control"  maxlength="60" id="db_password" name="db_password" value="" />
          <span class="helper-text">'.$lang_prompt_db_pwd_hint.'</span>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-sm-4" for="db_table_prefix">'.$lang_prompt_tbl_prefix.'</label>
        <div class="col-sm-4">
          <input type="text" class="form-control" maxlength="10" id="db_table_prefix" name="db_table_prefix" value="' . $db_table_prefix . '" />
          <span class="helper-text">'.$lang_prompt_tbl_prefix_hint.'</span>
        </div>
      </div>

      <legend class="blue">'.$lang_create_admin_account.'</legend>
      <div class="form-group">
        <label class="control-label col-sm-4" for="admin_username">'.$lang_prompt_cms_username.'</label>
        <div class="col-sm-6">
          <input type="text" class="form-control" maxlength="13" '.(empty($admin_username)?'required="required" ':'').'id="admin_username" name="admin_username" value="' . $admin_username . '" />
          <span class="helper-text">'.$lang_prompt_cms_user_hint.'</span>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-sm-4" for="admin_password">'.$lang_prompt_cms_pwd.'</label>
        <div class="col-sm-6">
          <input type="password" class="form-control" maxlength="40" required="required" id="admin_password" name="admin_password" value="" />
          <span class="helper-text">'.$lang_prompt_cms_pwd_hint.'</span>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-sm-4" for="admin_email">'.$lang_prompt_cms_email.'</label>
        <div class="col-sm-6">
          <input type="text" class="input-xlarge" maxlength="128" '.(empty($admin_email)?'required="required"':'').'id="admin_email" name="admin_email" value="' . $admin_email . '" />
          <span class="helper-text">
          <input type="checkbox" class="ace" id="email_as_technical" name="email_as_technical" value="1" checked="checked" />
		  <span class="lbl">'.$lang_prompt_cms_email_receive.'</span></span>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-sm-4" for="optionsDemoData">'.$lang_prompt_install_demo.'</label>
        <div class="col-sm-6">
          <input type="checkbox" class="ace" id="optionsDemoData" name="optionsDemoData" value="1" checked="checked" />
		  <span class="lbl"> '.$lang_prompt_install_demo_hint.'</span>
        </div>
      </div>

    <div class="alert alert-info alert-block center">
      '.$lang_credentials_hint.'
    </div>

    <div class="center">
      <button type="submit" class="btn btn-large btn-primary" name="install" value="">'.addslashes($lang_install_now_btn).'</button>
    </div>

    </form>
</div>
</div>
<script type="text/javascript">
//<![CDATA[
if (typeof(jQuery) !== "undefined") {
  jQuery(document).ready(function(){
    jQuery("input#db_password").val("");
    jQuery("input#admin_password").val("");
  })
}
//]]>
</script>
';

  }
  }
}

// ############################################################################
// DISPLAY SETUP FOOTER
// ############################################################################

DisplaySetupFooter();
