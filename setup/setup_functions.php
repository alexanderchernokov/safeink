<?php
if(!defined('IN_PRGM')) exit();

// ############################################################################
// DISPLAY SETUP HEADER
// ############################################################################

if(!function_exists('DisplaySetupHeader'))
{
function DisplaySetupHeader()
{
  $install_type = defined('UPGRADING_PRGM') ? 'Upgrade' : 'Installation';

   ?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset='<?=SD_CHARSET?>'" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<base href="<?=SITE_URL.ADMIN_PATH?>/" />
		<meta http-equiv="Content-Type" content="text/html;charset=<?=SD_CHARSET?>" />
        <title>
            <?=PRGM_NAME . ' ' . $install_type?>
        </title>

		<meta name="description" content="overview &amp; stats" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
        
   		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="<?=SITE_URL?>admin/styles/ace/assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="<?=SITE_URL?>includes/css/font-awesome.min.css" />

		<!-- page specific plugin styles -->

		<!-- text fonts -->
		<link rel="stylesheet" href="<?=SITE_URL?>admin/styles/ace/assets/css/ace-fonts.css" />

		<!-- ace styles -->
		<link rel="stylesheet" href="<?=SITE_URL?>admin/styles/ace/assets/css/ace.min.css" id="main-ace-style" />

		<!--[if lte IE 9]>
			<link rel="stylesheet" href="<?=SITE_URL?>admin/styles/ace/assets/css/ace-part2.min.css" />
		<![endif]-->
		<link rel="stylesheet" href="<?=SITE_URL?>admin/styles/ace/assets/css/ace-skins.min.css" />
		<link rel="stylesheet" href="<?=SITE_URL?>admin/styles/ace/assets/css/ace-rtl.min.css" />

		<!--[if lte IE 9]>
		  <link rel="stylesheet" href="<?=SITE_URL?>admin/styles/ace/assets/css/ace-ie.min.css" />
		<![endif]-->

		<!-- inline styles related to this page -->
        
        <!-- ace settings handler -->
		<script src="<?=SITE_URL?>admin/styles/ace/assets/js/ace-extra.min.js"></script>

		<!-- HTML5shiv and Respond.js for IE8 to support HTML5 elements and media queries -->

		<!--[if lte IE 8]>
		<script src="<?=SITE_URL?>admin/styles/ace/assets/js/html5shiv.min.js"></script>
		<script src="<?=SITE_URL?>admin/styles/ace/assets/js/respond.min.js"></script>
		<![endif]-->
        
        <!-- basic scripts -->

		<!--[if !IE]> -->
		<script type="text/javascript">
			window.jQuery || document.write("<script src='<?=SITE_URL?>admin/styles/ace/assets/js/jquery.min.js'>"+"<"+"/script>");
		</script>

		<!-- <![endif]-->

		<!--[if IE]>
        <script type="text/javascript">
         window.jQuery || document.write("<script src='<?=SITE_URL?>admin/styles/ace/assets/js/jquery1x.min.js'>"+"<"+"/script>");
        </script>
        <![endif]-->
		<script type="text/javascript">
			if("ontouchstart" in document.documentElement) document.write("<script src='<?=SITE_URL?>admin/styles/ace/assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
		</script>
		<script src="<?=SITE_URL?>admin/styles/ace/assets/js/bootstrap.min.js"></script>
		
        
        
	</head>
   <body class="no-skin">
		<!-- #section:basics/navbar.layout -->
		<div id="navbar" class="navbar navbar-default">
			<script type="text/javascript">
				try{ace.settings.check('navbar' , 'fixed')}catch(e){}
			</script>

			<div class="navbar-container" id="navbar-container">
				<!-- #section:basics/sidebar.mobile.toggle -->
			

				<!-- /section:basics/sidebar.mobile.toggle -->
				<div class="navbar-header pull-left">
					<!-- #section:basics/navbar.layout.brand -->
					<a href="#" class="navbar-brand">
						<small>
							<?=PRGM_NAME?>
						</small>
					</a>

					<!-- /section:basics/navbar.layout.brand -->

					<!-- #section:basics/navbar.toggle -->

					<!-- /section:basics/navbar.toggle -->
				</div>

			

				<!-- /section:basics/navbar.dropdown -->
			</div><!-- /.navbar-container -->
		</div>
        <!-- /section:basics/navbar.layout -->

		<div class="main-container" id="main-container">
			<script type="text/javascript">
				try{ace.settings.check('main-container' , 'fixed')}catch(e){}
			</script>


			
			<div class="main-content">
               
                <!-- #section:basics/content.breadcrumbs -->
				<div class="breadcrumbs" id="breadcrumbs">
					<script type="text/javascript">
						try{ace.settings.check('breadcrumbs' , 'fixed')}catch(e){}
					</script>

					<ul class="breadcrumb">
						<li>
							<i class="ace-icon fa fa-home home-icon"></i>
							<a href="#"><?=PRGM_NAME?></a>
						</li>
						<li class="active"><?=$install_type?></li>
					</ul><!-- /.breadcrumb -->
                    
                   

					<!-- /section:basics/content.searchbox -->


          <!-- /section:basics/content.searchbox -->
				</div><!-- /section:basics/content.breadcrumbs -->
            
				
        <div class="page-content">

			<div class="page-content-area">
            <div class="row">
              <div class="col-xs-12">			
 <?php

} // DisplaySetupHeader


// ############################################################################
// DISPLAY SETUP FOOTER
// ############################################################################

function DisplaySetupFooter()
{
 ?>
 </div> <!-- /. col-xs-12 -->
 			</div> <!-- /.row -->
	 </div><!-- /.page-content-area -->
   </div><!-- / . page-content -->
</div><!-- /.main-content -->
 <div class="footer">
    <div class="footer-inner">
        <!-- #section:basics/footer -->
        <div class="footer-content">
            <span class="bigger-105">
 <?php
 if(empty($noCopyright))
  {
    @include(SD_INCLUDE_PATH.'build.php');
	
	echo 'Powered By <strong>' . PRGM_NAME . '</strong>';
			
	if(PRGM_NAME == 'Subdreamer CMS')
	{
		echo "<br>&copy; <a href='http://antiref.com/?http://www.subdreamer.com/' target='_blank' title='131 Studios Web Development'>131 Studios Web Development</a>";
	}
	
	
  }
  ?>
        </span>
    </div>
    <!-- /section:basics/footer -->
	</div>
 </div>

</div><!-- /.main-container -->
</body>
</html>
<?php

  exit;

} //DisplaySetupFooter


function GetCurrentDBCharset()
{
  global $DB;

  $result = '';
  // Check for character set of database itself
  $DB->ignore_error = true;
  if($result = $DB->query_first('SHOW CREATE DATABASE `%s`', $DB->database))
  {
    $DB->ignore_error = false;
    $result = array_pop($result);
    $charset = 'unknown';
    if(preg_match('/DEFAULT CHARACTER SET (.*)? /im', $result, $matches))
    {
      $charset = $matches[1];
    }
    else
    {
      $charset = $result;
    }
    $result = $charset;
  }
  else
  if($result = $DB->query_first("SHOW VARIABLES LIKE 'collation_database'"))
  {
    $result = $result[1];
  }

  $DB->ignore_error = false;
  return $result;

} //GetCurrentDBCharset

// ############################################################################
// DISPLAY REQUIREMENTS CHECK
// ############################################################################

function DisplayRequirementsChecker()
{
  global $DB;

  // Check upgrade requirements
  $gd_info = $gd_ver = ''; //SD342 avoid PHP notices if GD is not installed
  $db_is_UTF8 = false;
  $PHP_OK = version_compare(PHP_VERSION, '5.1.0', '>=');
  if(isset($DB))
  {
    $DB_ver = $DB->query_first('SELECT version()');
    if(!empty($DB_ver)) $DB_ver = $DB_ver[0];
    $charset = GetCurrentDBCharset();
    $db_is_UTF8 = (strpos(strtolower($charset),'utf8')!==false);

    //Potential fixes:
    //$DB->query('ALTER DATABASE `'.$DB->database.'` CHARACTER SET utf8, COLLATE utf8_unicode_ci');
    //$DB->query('ALTER TABLE `%s` DEFAULT CHARACTER SET utf8', $tablename);
    // Convert columns to binary fieldtype pendants, then back to original fieldtype with charset utf8
    //$columns = SHOW FULL COLUMNS FROM $tablename
    //ALTER TABLE blah CHANGE `column1` `column1` DEFAULT NULL||'$columns['Default']' $columns['Null']=='YES' ? 'NULL' : 'NOT NULL'  CHARACTER SET utf8
    // OR:
    // ALTER TABLE `table` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
  }
  else
  {
    ob_start();
    phpinfo(INFO_MODULES);
    $info = ob_get_contents();
    ob_end_clean();
    $info = strip_tags($info);
    $info = stristr($info, 'Client API version');
    preg_match('/[1-9].[0-9].[1-9][0-9]/', $info, $match);
    $DB_ver = $match[0];
    if(strpos($DB_ver,'.')===false)
    {
      $info = stristr($info, 'mysql');
      $info = trim(substr($info, strpos($info, ' '), strpos($info, "\n")));
      $DB_ver = substr($info, 0, strpos($info, ' '));
    }
  }
  #$imagick = extension_loaded('imagick') && class_exists('Imagick');
  $mysql = extension_loaded('mysql') && function_exists('mysql_connect');
  #$mysqli = extension_loaded('mysqli') && function_exists('mysqli_connect');
  $MySQL_OK = $mysql && !empty($DB_ver) && (substr($DB_ver, 0,2) == '5.');
  $mbstring = extension_loaded('mbstring');
  $iconv    = extension_loaded('iconv');
  $cache_exists = is_dir(ROOT_PATH.'cache');
  $cache_writable = is_writable(ROOT_PATH.'cache');
  $reg_globals = ini_get('register_globals')?'On':'Off';
  $max_mem = max(intval(ini_get('memory_limit')), intval(get_cfg_var('memory_limit')));
  $gd_ok = false;
  if(extension_loaded('gd') && function_exists('gd_info')) {
    $gd_info = gd_info();
    $gd_ver = $gd_info['GD Version'];
    $gd_ver = strtolower(substr($gd_ver,0,9)) == 'bundled (' ? substr($gd_ver,9) : $gd_ver;
    $gd_ver = strpos($gd_ver, ' ') !== false ? substr($gd_ver,0,strpos($gd_ver, ' ')) : $gd_ver;
    $gd_ok = (strpos($gd_ver, '2.') !== false);
  }
  $curl = in_array('curl', get_loaded_extensions());

  // Downgrade not possible
  $SD_OK = true;
  if(defined('UPGRADING_PRGM'))
  {
    $SD_OK  = (substr(CURR_PRGM_VERSION,0,3) == '2.6') ||
              (substr(CURR_PRGM_VERSION,0,2) == '3.') ||
			  (substr(CURR_PRGM_VERSION,0,2) == '4.');	// SD400
    if(substr(CURR_PRGM_VERSION,0,2) == '3.' &&
       substr(PRGM_VERSION,0,2) == '2.')
    {
      $SD_OK = false;
    }
  }
  
  global $lang_req_requirement_title, $lang_requirement_title,
         $lang_req_version_title,$lang_req_status_title,
         $lang_req_pass, $lang_req_fail, $lang_req_folder,
         $lang_req_folder_ok, $lang_req_folder_not_writable,
         $lang_req_folder_not_found, $lang_req_db_charset,
         $lang_req_warning, $lang_req_change_db_charset,
         $lang_req_conv_tables, $lang_req_recommended,
         $lang_requirements_title, $lang_admin_skin;

  // Display check results
  echo '
<div class="space-20"></div>
<div class="col-sm-6 col-sm-offset-3">
<div class="table-header">'.$lang_requirements_title.'</div>
  <table class="table table-bordered table-condensed table-hover table-striped">
  <thead>
  <tr>
    <th class="center">'.$lang_req_requirement_title.'</th>
    <th class="center">'.$lang_req_version_title.'</th>
    <th class="center">'.$lang_req_status_title.'</th>
  </tr>
  </thead>
  <tbody>
  ';
  if(defined('UPGRADING_PRGM'))
  {
    echo '
  <tr>
    <td>Subdreamer 2.6.x or 3.x</td>
    <td>'.CURR_PRGM_VERSION.'</td>
    <td><span class="bolder '.($SD_OK ? 'green">'.$lang_req_pass : 'red">'.$lang_req_fail).'</span></td>
  </tr>
  ';
  }
  echo '
  <tr>
    <td>PHP 5.1+</td>
    <td>'.phpversion().'</td>
    <td><span class="bolder '.($PHP_OK ? 'green">'.$lang_req_pass : 'red">'.$lang_req_fail).'</span></td>
  </tr>
  <tr>
    <td>MySQL 5.0+</td>
    <td>'.$DB_ver.'</td>
    <td><span class="bolder '.($MySQL_OK ? 'green">'.$lang_req_pass : 'red">'.$lang_req_fail).'</span></td>
  </tr>
  <tr>
    <td>&quot;cache&quot; '.$lang_req_folder.' </td>
    <td>'.($cache_exists ? ($cache_writable ? $lang_req_folder_ok : $lang_req_folder_not_writable) : $lang_req_folder_not_found).'</td>
    <td><span class="bolder '.($cache_exists && $cache_writable ? 'green">'.$lang_req_pass : 'red">'.$lang_req_fail).'</span></td>
  </tr>';

  if(isset($DB))
  {
    echo '
  <tr>
    <td>'.$lang_req_db_charset.' (utf8)</td>
    <td>'.$charset.'</td>
    <td><span class="bolder '.($db_is_UTF8 ? 'green">'.$lang_req_pass : 'red">'.$lang_req_warning).'</span>
    ';
    if(!$db_is_UTF8 && ($charset != 'unknown'))
    {
      echo '<br />
      <input name="convert_db" type="checkbox" class="ace" value="1" checked="checked" /><span class="lbl"> '.$lang_req_change_db_charset.'</span><br />
      <input name="convert_tables" type="checkbox" class="ace" value="1" /><span class="lbl"> '.$lang_req_conv_tables.'</span>
      ';
    }
    echo '</td>
  </tr>
  ';
  }

  echo '
  <tr>
    <td>GD Image Library</td>
    <td>'.$gd_ver.'</td>
    <td><span class="bolder '.($gd_ok ? 'green">'.$lang_req_pass : 'red">'.$lang_req_fail).'</span></td>
  </tr>
  <tr>
    <td>PHP Register Globals</td>
    <td>'.$reg_globals.'</td>
    <td><span class="bolder '.($reg_globals=='Off' ? 'green">'.$lang_req_pass : '#FF8080">'.$lang_req_recommended.': Off').'</span></td>
  </tr>
  <tr>
    <td>PHP max. Memory Usage</td>
    <td>'.$max_mem.'M</td>
    <td><span class="bolder '.(!$max_mem || $max_mem > 20 ? 'green">'.$lang_req_pass : '#FF8080">'.$lang_req_recommended.': > 20 MB').'</span></td>
  </tr>
  <tr>
    <td>PHP mbstring Extension</td>
    <td>'.($mbstring ? 'Installed' : 'Not installed').'</td>
    <td><span class="bolder '.($mbstring ? 'green">'.$lang_req_pass : '#FF8080">'.$lang_req_recommended).'</span></td>
  </tr>
  <tr>
    <td>PHP iconv Extension</td>
    <td>'.($iconv ? 'Installed' : 'Not installed').'</td>
    <td><span class="bolder '.($iconv ? 'green">'.$lang_req_pass : '#FF8080">'.$lang_req_recommended).'</span></td>
  </tr>
  <tr>
    <td>PHP <a href="http://www.php.net/manual/en/intro.curl.php" target="_blank">curl</a> Extension</td>
    <td>'.($curl ? 'Installed' : 'Not installed').'</td>
    <td><span class="bolder '.($curl? 'green">'.$lang_req_pass : '#FF8080">'.$lang_req_recommended).'</span></td>
  </tr>
  </tbody>
  </table>
  </div>
  </div>
  <div class="clearfix"></div>
  ';

  $req_error = !$PHP_OK || /*!$MySQL_OK || !$gd_ok || */ !$SD_OK;

  return $req_error;

} //DisplayRequirementsChecker


// ############################################################################
// DISPLAY SETUP MESSAGE
// ############################################################################

function sd_StartSetupMessages($header='')
{
  echo '
  <div id="setup-messages">
    '.(strlen($header)?'<h4>'.$header.'</h4>':'').'
    ';
}

function sd_EndSetupMessages()
{
  echo '
  </div>';
}

function sd_DisplaySetupMessage($message='', $noBR = false)
{
  echo $message.(empty($noBR) ? '<br />' : '');
}

function sd_isPreRelease($version)
{
	$install_type = defined('UPGRADING_PRGM') ? 'Upgrade' : 'Install';
	
	if(strpos(strtolower($version), 'a') || strpos(strtolower($version), 'b') || strpos(strtolower($version), 'rc'))
	{
	 	echo '<div class="alert alert-danger"><i class="ace-icon fa fa-exclamation-circle red bigger-110"></i> You are about to ' . $install_type . ' to a Pre-release (Alpha, Beta, Release Candidate (RC)) version of Subdreamer CMS! Please be aware that Pre-Release versions of Subdreamer CMS are NOT SUPPORTED and should never be installed on a production website. </div>';
	}
}

}//DO NOT REMOVE