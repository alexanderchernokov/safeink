<?php
if(!defined('IN_PRGM') && !defined('IN_SUBDREAMER')) return;
// Prevent multiple inclusions
if(defined('SD_INIT_LOADED')) return;
define('SD_INIT_LOADED', true);
defined('IN_SUBDREAMER') || define('IN_SUBDREAMER', true);
define('SD_DOCROOT', realpath(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR);
// Prevent too many buffer values (see branding.php!)
$sd_max_param_fail = false; //SD343
$sd_org_post = null; //SD343
if(isset($_GET) && !empty($_GET))
{
  if(defined('SD_MAX_GET_VARS') && (SD_MAX_GET_VARS > 5) && (count($_GET) > SD_MAX_GET_VARS))
    $sd_max_param_fail = true;
}
if(isset($_POST) && !empty($_POST))
{
  if(defined('SD_MAX_POST_VARS') && (SD_MAX_POST_VARS > 5) && (count($_GET) > SD_MAX_POST_VARS))
    $sd_max_param_fail = true;
  else
  {
    $sd_org_post = substr(@var_export($_POST,true), 0, 4194304);
    if(!empty($sd_org_post) &&
       (preg_match('#\CONCAT\(0x726b77617777\(#i', $sd_org_post) ||
        (preg_match('#\beval\(#i', $sd_org_post) &&
         preg_match('#\bbase64_decode\(|\bbase64_encode\(|\bgzinflate\(#i', $sd_org_post))))
    {
      $sd_max_param_fail = 1;
    }
  }
}
else
{
  unset($sd_org_post);
}
if($sd_max_param_fail)
{
  header("HTTP/1.0 403 Forbidden");
  echo "HTTP/1.0 403 Forbidden";
  exit();
}
unset($sd_max_param_fail,$pages_parents_md_arr,$pages_md_arr,$pages_seo_arr);

define('SD_BOT_AGENTS', "#(Mozilla/4\.0 (compatible;)|Mozilla/5\.0 (compatible;)|pingdom\.com|rogerbot/1\.0|JikeSpider|Nigma\.ru|sogou\.com|magpie-crawler|ezooms.bot@gm|SolomonoBot/1|Sosospider|Jyxobot|ScoutJet|chattertrap\.com|findexa|findlinks|gaisbo|SymantecSpider|Speedy Spider|postrank\.com|SockrollBot|seoprofiler\.com/bot|DotBot/1|Baiduspider|bingbot/2|Sosoimagespider|surveybot|bloglines|blogsearch|ubsub|syndic8|userland|GoogleBot|YandexBot|NimbleCrawler|www\.fi crawler|ICF_Site|ZyBorg|Virgo|OmniExplorer_Bot|Python-urllib|IRLbot|MJ12bot|WebStripper|become\.com|Gigabot|WNMbot|Snapbot|fast-webcrawler|slurp@inktomi|LinkChecker|technorati|TurnitinBot|Exabot|Accoona-AI|jeeves|lycos|nutch|ia_archiver|kroolari|scooter|Twisted PageGetter|google|msnbot|yahoo)#si");
defined('PHP5') || define('PHP5', (PHP_VERSION >= 5));
defined('PHP53') || define('PHP53', version_compare(PHP_VERSION, '5.3.0', '>='));
defined('E_DEPRECATED') || define('E_DEPRECATED', 8192);
defined('IN_CSS') || define('IN_CSS', (isset($_SERVER['PHP_SELF']) &&
                            ( (substr($_SERVER['PHP_SELF'],-4)=='.css') ||
                              (substr($_SERVER['PHP_SELF'],-7)=='css.php') )));
							  
$sd_ignore_watchdog = false;
$uri = isset($_SERVER['REQUEST_URI']) ? strip_tags($_SERVER['REQUEST_URI']) : '';

//SD343: exit in case a non-existant image url got rewritten to index.php
if(!defined('IN_ADMIN') && preg_match('#index\.php$#',$_SERVER['PHP_SELF']))
{
  if(preg_match('#images/articlethumbs/(\w*(\.(png$|jpg$|gif$))*)#', $uri)) exit();
  if(preg_match('#images/avatars/(\w*(\.(png$|jpg$|gif$))*)#', $uri)) exit();
  if(preg_match('#images/(\w*(\.(png$|jpg$|gif$))*)#', $uri)) exit();
}

// ############################################################################
// TURN ON STRIPSLASHES FROM INCOMING DATABASE QUERIES
// ############################################################################
/*
If magic_quotes_runtime is enabled, most functions that return data from any
sort of external source including databases and text files will have quotes
escaped with a backslash.
We don't want PHP messing with any incoming data, so lets turn it off!
Note: this function is no longer supported in 5.3.0
*/

if(PHP53)
  @ini_set('magic_quotes_runtime', 0); // SD313
else
  @set_magic_quotes_runtime(0);

// ############################## SET PHP Options ##############################

// init script starts by loading the database connection or redirecting to the
// install page. if a connection is established then disable magic_quotes_runtime,
// stripslashes if gpc is turned on, clean all post/get/cookie data

@ini_set('arg_separator.output','&'); // Get rid of '&' in links
@ini_set('session.use_cookies', true); //SD343
@ini_set('session.use_only_cookies', false); //SD343
if(session_name()=='') @ini_set('session.use_trans_sid','0'); // Disable PHP adding session ids

if (isset($_SERVER['HTTP_HOST']))
{
  $domain = '.'. preg_replace('`^www.`', '', $_SERVER['HTTP_HOST']);
  // Per RFC 2109, cookie domains must contain at least one dot other than the
  // first. For hosts such as 'localhost', we don't set a cookie domain.
  if (count(explode('.', $domain)) > 2) @ini_set('session.cookie_domain', $domain);
  unset($domain);
}

//SD322: remove ampersands at beginning of URL params:
foreach($_GET as $key => $value)
{
  if(substr($key,0,5)=='&amp;')
  {
    unset($_GET[$key]);
    $_GET[substr($key,5)] = $value;
  }
  else if(substr($key,0,4)=='amp;')
  {
    unset($_GET[$key]);
    $_GET[substr($key,4)] = $value;
  }
}

// added security against malicious requests
if(@ini_get('register_globals') && isset ($_REQUEST))
{
  while(list($key, $value) = each($_REQUEST))
  {
    $GLOBALS[$key] = null;
    unset ($GLOBALS[$key]);
  }
}
$targets = array ('PHP_SELF', 'HTTP_REFERER', 'QUERY_STRING');
foreach ($targets as $target) {
  $_SERVER[$target] = isset ($_SERVER[$target]) ? htmlspecialchars($_SERVER[$target], ENT_QUOTES) : null;
}
unset($targets, $target);

// ######################### SEARCH ENGINE REDIRECT ############################

$agent_is_bot = false;
if(!empty($_SERVER['HTTP_USER_AGENT']))
{
  $agent_is_bot = preg_match(SD_BOT_AGENTS, $_SERVER['HTTP_USER_AGENT']);
}
define('SD_IS_BOT', $agent_is_bot);
if(SD_IS_BOT) unset($_POST);

if(isset($_GET['s']) && (empty($_SERVER['HTTP_USER_AGENT']) || SD_IS_BOT))
{
  header("HTTP/1.0 301 Moved Permanently");
  header('Location: ' . preg_replace('#\?s=[0-9a-f]+#', '', $uri));
  exit();
}

// ############################################################################
// PRE-DECLARED VARIABLES LIKE PLUGIN BITFIELD AND MONTHS ARRAY
// ############################################################################

// SD313: array with id's of core plugins which cannot be
// re-installed or uninstalled
$core_pluginids_arr = array(1,2,3,6,10,11,12,17); //SD341: no longer main: 4,7,8,9,16

$known_photo_types = array(
    'jpg'   => 'image/pjpeg', //IE8
    'jpg'   => 'image/jpeg',
    'jpeg'  => 'image/jpeg',
    'pjpeg' => 'image/jpeg',
    'gif'   => 'image/gif',
    'bmp'   => 'image/bmp',
    'png'   => 'image/png');

$pluginbitfield = array('canview'     => 1,
                        'cansubmit'   => 2,
                        'candownload' => 4,
                        'cancomment'  => 8,
                        'canadmin'    => 16,
                        'canmoderate' => 32);

// ############################################################################
// DEFINE IMPORTANT VARIABLES AND CONSTANTS
// ############################################################################

// SD313: Global header variable to receive HTML code intended for <head> section
// available to both admin backend as well as frontend.
// Populated by sd_header_add() and output/returned by sd_header_output()
$sd_header = array('other' => array(), 'css' => array(), 'css_import' => array(), 'css-ie' => array(), 'js' => array());
$theme_arr = $designs = $sd_header_crc = array();

define('TIME_NOW', time());
define('TIMENOW', TIME_NOW); // legacy

// Set Email CRLF depending on platform
if(strtoupper(substr(PHP_OS, 0, 3) == 'WIN'))
  define('EMAIL_CRLF', "\r\n");
else if(strtoupper(substr(PHP_OS, 0, 3) == 'MAC'))
  define('EMAIL_CRLF', "\r");
else
  define('EMAIL_CRLF', "\n");

// Pre-defined Usergroup ID's
define('ADMINS_UGID', 1);
define('MODERATORS_UGID', 2);
define('MEMBERS_UGID', 3);
define('GUESTS_UGID', 4);

// SD313: reintroducing WatchDog
define('WATCHDOG_NOTICE',   0);
define('WATCHDOG_WARNING',  1);
define('WATCHDOG_ERROR',    2);
define('WATCHDOG_MAX_ERRORS', 50);

// Define some important cache id's that are used across files:
define('CACHE_ALL_CATEGORIES',   'all_categories');
define('CACHE_ALL_MAINSETTINGS', 'all_mainsettings');
define('CACHE_ALL_PLUGINS',      'all_plugins');
define('CACHE_ALL_SKIN_CSS',     'all_skin_css');
define('CACHE_NAVIGATION',       'all_navigation'); //SD343
define('CACHE_PAGE_DESIGN',      'page_design_'); //SD342
define('CACHE_ARTICLE',          'article_id');
define('CACHE_ARTICLES_SEO2IDS', 'article_seo2ids');
define('CACHE_PAGE_PREFIX',      'page_');
define('CACHE_PLUGIN_TITLES',    'plugins_titles'); //SD362
define('SD_TOKEN_NAME',          'form_token');
defined('SD_VVCLEN') or define('SD_VVCLEN', 5);

// ############################################################################
// INIT SCRIPT START TIME AND SET ERROR REPORTING
// ############################################################################



// ############################################################################
// SPECIAL SYSTEM-WIDE DEFINES
// ############################################################################
defined('ROOT_PATH') or define('ROOT_PATH', '../');
$rootpath = ROOT_PATH;
define('SD_INCLUDE_PATH', ROOT_PATH . 'includes/');
define('SD_CSS_PATH',     SD_INCLUDE_PATH . 'css/'); //SD342
define('SD_JS_PATH',      SD_INCLUDE_PATH . 'javascript/'); //SD322
define('SD_JUI_PATH',     SD_JS_PATH . 'jquery-ui/'); //SD322

/*
SD343: Define path to classes directory (default: /includes/classes"), which
could be set in the "config.php" file to a relative path outside the web-root:
NB: this folder *HAS* to be under the above "includes/" folder in any case!
*/
defined('SD_CLASS_PATH') or define('SD_CLASS_PATH', SD_INCLUDE_PATH . 'classes/');

// ############################################################################
// LOAD CONFIG DATA
// ############################################################################

// Security: clear important variables
unset($dbname, $database, $dbusername, $dbpassword, $userinfo, $usersettings,
      $usersystem, $servername);

if(!defined('INSTALLING_PRGM'))
{
  if( !defined('SETUPDIR') &&
      (!file_exists(SD_INCLUDE_PATH . 'config.php') ||
       !filesize(SD_INCLUDE_PATH . 'config.php')) )
  {
    header("Location: " . ROOT_PATH . 'setup/install.php');
    exit();
  }

  require(ROOT_PATH . 'includes/config.php');

  if( ( defined('PRGM_TABLE_PREFIX') && (!is_array($database) || !defined('PRGM_INSTALLED'))) ||
      (!defined('PRGM_TABLE_PREFIX') && (empty($servername)   || !defined('SD_INSTALLED'))) )
  {
    #header("Location: " . ROOT_PATH . 'setup/install.php');
    header("HTTP/1.0 404 Page not found");
    header("HTTP/1.1 404 Page not found");
    echo 'Page not found!';
    exit;
  }

  if(!defined('PRGM_TABLE_PREFIX'))
  {
    // user upgrade from 260 (using old config file)
    $database = array();
    $database['name']        = (string)$dbname;
    $database['password']    = (string)$dbpassword;
    $database['server_name'] = (string)$servername;
    $database['username']    = (string)$dbusername;

    unset($servername, $dbusername, $dbpassword);

    if(defined('SD_INSTALLED')) define('PRGM_INSTALLED', true);

    // very old versions of sdcms did not have table_prefix defined
    defined('TABLE_PREFIX') or define('TABLE_PREFIX', '');
    define('PRGM_TABLE_PREFIX', TABLE_PREFIX);
  }
  else
    // fresh copy of SD3 (new config file)
    $dbname = $database['name'];

  define('SD_DBNAME', $dbname); //SD343
  define('PTP', PRGM_TABLE_PREFIX . 'p_'); // plugin table prefix

  //SD370: DB-dependent system hash
  define('PRGM_HASH', substr(sha1(md5($database['name']).md5($database['username'].md5($database['password']))),0,32));

  if(defined('PRGM_TABLE_PREFIX') && !defined('TABLE_PREFIX'))
    define('TABLE_PREFIX', PRGM_TABLE_PREFIX);
  else
    defined('PRGM_TABLE_PREFIX') or define('PRGM_TABLE_PREFIX', 'sd_');
}

// ############################################################################
// SD322: ADMIN_PATH could be pre-defined in config.php if the site admin
// wants to rename or move that folder; otherwise assume default name "admin"
// ############################################################################
defined('ADMIN_PATH') or define('ADMIN_PATH', 'admin');

// ############################################################################
// LOAD PRGM INFO
// ############################################################################
//SD322: load program info file AFTER config file
// Note: this also includes "branding.php" if it exists

// ############################################################################
// REDIRECT TO INSTALLATION IF NOT INSTALLED
// ############################################################################

if(!defined('PRGM_INSTALLED') && !defined('INSTALLING_PRGM'))
{
  if(file_exists(ROOT_PATH . 'setup/install.php'))
  {
    header('Location: ' . ROOT_PATH . 'setup/install.php');
    exit;
  }
  else
  {
    die("ERROR: " . PRGM_NAME . " is not installed and the installation script does not exist.");
  }
}

//SD360: skip some code if called by min script
if(!IN_CSS && (basename(dirname($_SERVER['PHP_SELF']))!=='min') )
{
  require(ROOT_PATH . ADMIN_PATH . '/prgm_info.php');

  //SD343: Flood checking, default: disabled; not finalized
  if(!defined('INSTALLING_PRGM') && defined('SD_FLOOD_CONTROL') && SD_FLOOD_CONTROL)
  {
    // see: http://www.thewebhelp.com/php/scripts/anti-flood-script/
    if (!isset($_SESSION)) session_start();
    // anti flood protection
    if(isset($_SESSION['sd_last_request']) && is_numeric($_SESSION['sd_last_request']) &&
       ($_SESSION['sd_last_request'] > time() - 2))
    {
      # users will be redirected to this page if it makes requests faster than 2 seconds
      if(defined('SD_FLOOD_PAGE') && strlen(SD_FLOOD_PAGE))
      {
        header("location: ".SD_FLOOD_PAGE);
      }
      exit();
    }
    $_SESSION['sd_last_request'] = time();
  }
}

// report all errors except E_NOTICE
// This is the default value set in php.ini
if(defined('DEBUG') && DEBUG)
{
	// Report all PHP errors
	error_reporting(-1);

	// Same as error_reporting(E_ALL);
	ini_set('error_reporting', E_ALL);
}
else
{
	error_reporting(0);
}

// ############################################################################
// ESTABLISH DATABASE CONNECTION
// ############################################################################

require_once(SD_INCLUDE_PATH . 'mysql.php');

if(!defined('INSTALLING_PRGM'))
{
  $DB = new DB;
  $DB->database = SD_DBNAME;
  $DB->password = $database['password'];
  $DB->server   = $database['server_name'];
  $DB->user     = $database['username'];
  if(!$DB->connect(true))
  {
    if(file_exists(SD_INCLUDE_PATH.'error_db_down.php')) //SD341
    {
      @include(SD_INCLUDE_PATH.'error_db_down.php');
      exit();
    }
    die('<div style="background-color: #ffeaef; margin: 10% auto; font-size: 15px; font-weight: bold; border: 1px solid red; padding: 10px; text-align: center; width: 80%">We are sorry, this page cannot be loaded right now due to a database error.<br /><br />Please visit this site later.</div>');
  }
}
// clear out the username and password
$database['username'] = $database['password'] = '';

// ############################################################################
// GET USERSYSTEM
// ############################################################################

if(isset($DB) && ($current_version = $DB->query_first("SELECT value FROM {mainsettings} WHERE varname = 'sdversion' LIMIT 1")))
{
  if(version_compare($current_version[0], '3.3.1', '>=')) define('SD_331',true);
  if(version_compare($current_version[0], '3.4.2', '>=')) define('SD_342',true);
  if(version_compare($current_version[0], '3.5.0', '>=')) define('SD_350',true);
  if(version_compare($current_version[0], '3.6.2', '>=')) define('SD_362',true);
  if(version_compare($current_version[0], '3.7.0', '>=')) define('SD_370',true);
}
else
{
  if(version_compare(PRGM_VERSION, '3.3.1', '>=')) define('SD_331',true);
  if(version_compare(PRGM_VERSION, '3.4.2', '>=')) define('SD_342',true);
  if(version_compare(PRGM_VERSION, '3.5.0', '>=')) define('SD_350',true);
  if(version_compare(PRGM_VERSION, '3.6.2', '>=')) define('SD_362',true);
  if(version_compare(PRGM_VERSION, '3.7.0', '>=')) define('SD_370',true);
}

if(isset($DB))
{
  $DB->ignore_error = true;
  $DB->result_type = MYSQL_ASSOC;
  $usersystem = $DB->query_first("SELECT * FROM {usersystems} WHERE activated = '1' ORDER BY usersystemid ASC LIMIT 1");
  $DB->result_type = MYSQL_BOTH;
  $DB->ignore_error = false;
}
if(!isset($DB) || empty($usersystem) || !in_array('usersystems', $DB->table_names_arr[$DB->database]))
{
  $database['name'] = defined('INSTALLING_PRGM') ? '' : SD_DBNAME;
  $usersystem = array('name' => 'Subdreamer', 'dbname' => $database['name'],
                      'usersystemid' => 1, 'queryfile' => 'subdreamer.php',
                      'tblprefix' => (defined('PRGM_TABLE_PREFIX')?PRGM_TABLE_PREFIX:''));
}
if(!defined('INSTALLING_PRGM') && empty($usersystem['dbname']))
{
  $usersystem['dbname'] = SD_DBNAME;
}

if(!defined('INSTALLING_PRGM') && !defined('UPGRADING_PRGM') && defined('PRGM_TABLE_PREFIX') &&
   !defined('TABLE_PREFIX') && (substr($usersystem['name'],0,9) != 'vBulletin'))
  define('TABLE_PREFIX', PRGM_TABLE_PREFIX);

// ############################################################################
// INCLUDE REQUIRED CMS FILES
// ############################################################################

require_once(SD_INCLUDE_PATH . 'functions_global.php'); // also includes functions_security
require_once(SD_INCLUDE_PATH . 'functions_plugins.php');
require_once(SD_INCLUDE_PATH . 'class_css.php');

if(!defined('INSTALLING_PRGM') && !defined('UPGRADING_PRGM'))
{
  require_once(SD_INCLUDE_PATH . 'class_sd_usercache.php'); //SD342
  #if(file_exists(SD_INCLUDE_PATH . 'censor.php')) @include(SD_INCLUDE_PATH . 'censor.php'); //SD342
  if(defined('IN_ADMIN'))
  {
	  require_once(SD_INCLUDE_PATH . 'class_pagination_admin.php');
  }
  else
  {
	  require_once(SD_INCLUDE_PATH . 'class_pagination.php');
  }
  require_once(SD_INCLUDE_PATH . 'nbbc.php');
  if(defined('IN_ADMIN'))
  {
    require_once(SD_INCLUDE_PATH . 'functions_admin.php');	
  }
  else
  {
    require_once(SD_INCLUDE_PATH . 'recaptcha'.DIRECTORY_SEPARATOR.'recaptchalib.php');
    require_once(SD_INCLUDE_PATH . 'functions_frontend.php');
    require_once(SD_INCLUDE_PATH . 'class_captcha.php');
    require_once(SD_INCLUDE_PATH . 'class_comments.php');
    require_once(SD_INCLUDE_PATH . 'class_rating.php');
  }
  @include(SD_INCLUDE_PATH . 'functions_legacy.php');

  // SD313: security check (MUST BE AFTER ABOVE INCLUDES!)
  if(DetectGlobalsOverwrite($uri)) exit();
}

// Define special prefixes depending on Minify being enabled (prgm_info.php):
define('MINIFY_PREFIX_F', ((defined('ENABLE_MINIFY') && ENABLE_MINIFY) ? 'includes/min/index.php?f=' : ''));
define('MINIFY_PREFIX_G', ((defined('ENABLE_MINIFY') && ENABLE_MINIFY) ? 'includes/min/index.php?g=' : ''));

if(defined('DEBUG') && DEBUG)
{
	
  if(defined('E_DEPRECATED'))
    define('ERROR_LOG_LEVEL', E_ALL & ~E_DEPRECATED & ~E_STRICT);
  else
    define('ERROR_LOG_LEVEL', E_ALL);
  error_reporting(ERROR_LOG_LEVEL);
  @ini_set('display_startup_errors', 1);
  @ini_set('display_errors', 1);
  @ini_set('log_errors', 1);
}
else
{
  if(defined('E_RECOVERABLE_ERROR')) // PHP 5.2+
    define('ERROR_LOG_LEVEL', E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR);
  else
    define('ERROR_LOG_LEVEL', E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING);
  error_reporting(ERROR_LOG_LEVEL);
  @ini_set("display_startup_errors","0");
  @ini_set("display_errors","0");
}

// SD313: Set the error handler *after* the database has been connected!
if(function_exists('ErrorHandler'))
  set_error_handler('ErrorHandler'); // in functions_global.php

// ############################################################################
// DEFINE CHARSETS FOR CMS AND DATABASE
// ############################################################################
// DB_CHARSET exists for information that was saved incorrecly in the db
// for example utf-8 data saved as latin in the database

$mainsettings = array();
if(defined('INSTALLING_PRGM') && INSTALLING_PRGM)
{
  define('CHARSET', 'utf-8');
  define('SD_CHARSET', 'utf-8');
  define('DB_CHARSET', 'utf8');
}
else
{
  //SD342 pre-fetch 3 important main settings instead of 3 Selects
  if(isset($DB))
  {
    $pre_main = $DB->query('SELECT varname,value FROM '.PRGM_TABLE_PREFIX."mainsettings WHERE varname IN ('charset','db_charset','enable_caching','sdversion')");
    while($preset = $DB->fetch_array($pre_main,null,MYSQL_ASSOC))
    {
      $mainsettings[$preset['varname']] = $preset['value'];
    }
    unset($pre_main);
  }

  $sd_charset = empty($mainsettings['charset']) ? 'utf-8' : strtolower($mainsettings['charset']);
  $sd_charset = ($sd_charset=='utf8' ? 'utf-8' : $sd_charset);
  define('CHARSET', $sd_charset);
  define('SD_CHARSET', $sd_charset);
  $tmp = empty($mainsettings['db_charset'])?'utf8':strtolower($mainsettings['db_charset']);
  $tmp = ($tmp=='utf-8' ? 'utf8' : $tmp);
  define('DB_CHARSET', $tmp);
  unset($tmp);
  define('SD_VERSION', (empty($mainsettings['sdversion'])?'3.4.0':$mainsettings['sdversion'])); //SD342

  // Instantiate core utf-8 conversion object (2.5.3):
  if(function_exists('sd_GetConverter'))
  {
    if($sd_utf8_encoding = sd_GetConverter(SD_CHARSET, 'utf-8', 1))
    if(!defined('SD_CVC') || !isset($sd_utf8_encoding) || ($sd_utf8_encoding->CharsetTable === FALSE))
      unset($sd_utf8_encoding);
  }

  // ##### SET DB CONNECTION CHARSET #####
  $DB->set_names(DB_CHARSET);

  //SD343: include utf8 support lib
  //SD360: not if phpBB3 is integrated due to naming conflicts
  if((SD_CHARSET == 'utf-8') && ($usersystem['name'] != 'phpBB3'))
  {
    include_once(SD_INCLUDE_PATH.'utf8/utf8.php');
  }

} // !installing


// ############################################################################
// REMOVE ADDSLASHES THAT WERE ADDED BY PHP
// ############################################################################
/*
SD handles its own addslashing, don't let PHP mess with addslashes:
"magic_quotes_gpc" is DIFFERENT than "magic_quotes_runtime", we need to
StripSlashesArray if it's turned on. There's also no way to turn it off.
SD362: removed with PHP 5.4+!
*/
if(version_compare(PHP_VERSION, '5.4.0', '<') && get_magic_quotes_gpc())
{
  if(ini_get('register_long_arrays')) // SD313
  {
    //SD341: array_walk_recursive
    if(isset($HTTP_GET_VARS))     @array_walk_recursive($HTTP_GET_VARS, 'StripSlashesValue');
    if(isset($HTTP_POST_VARS))    @array_walk_recursive($HTTP_POST_VARS, 'StripSlashesValue');
    if(isset($HTTP_COOKIE_VARS))  @array_walk_recursive($HTTP_COOKIE_VARS, 'StripSlashesValue');
  }
  if(isset($_GET))     @array_walk_recursive($_GET, 'StripSlashesValue');
  if(isset($_COOKIE))  @array_walk_recursive($_COOKIE, 'StripSlashesValue');
  if(isset($_POST))    @array_walk_recursive($_POST, 'StripSlashesValue');
  if(isset($_REQUEST)) @array_walk_recursive($_REQUEST, 'StripSlashesValue');
}

//SD322: Ajax requests have to send appropiate header explicitely
if(!IN_CSS && (!function_exists('Is_Ajax_Request') || !Is_Ajax_Request()))
{
  header('Content-type:text/html; charset='.SD_CHARSET);
}

// ############################################################################
// CLEAN G/P/C/S DATA IN FRONTEND & BACKEND
// ############################################################################
// Applies htmlspecialchars()

// SD313 - added HTTP*VARS arrays
if(ini_get('register_long_arrays'))
{
    //SD341: array_walk_recursive
  if(isset($HTTP_GET_VARS))     @array_walk_recursive($HTTP_GET_VARS, 'PreCleanValue');
  if(isset($HTTP_POST_VARS))    @array_walk_recursive($HTTP_POST_VARS, 'PreCleanValue');
  if(isset($HTTP_COOKIE_VARS))  @array_walk_recursive($HTTP_COOKIE_VARS, 'PreCleanValue');
}

if(isset($_GET))     @array_walk_recursive($_GET, 'PreCleanValue');
if(isset($_POST))    @array_walk_recursive($_POST, 'PreCleanValue');
if(isset($_COOKIE))  @array_walk_recursive($_COOKIE, 'PreCleanValue');
if(isset($_REQUEST)) @array_walk_recursive($_REQUEST, 'PreCleanValue');


// ############################################################################
// ###################### RETURN IF INSTALLING PRGM ###########################
// ############################################################################
if(defined('INSTALLING_PRGM')) return true;

// ############################################################################
// INITIALIZE CACHE OBJECT (BOTH admin and frontend! AFTER header call!)
// DO NOT REMOVE OR MAKE DEPENDENT ON ANY CONDITION!!!
// ############################################################################
$SDCache = false;
require(SD_INCLUDE_PATH . 'class_cache.php');
if(class_exists('SDCache'))
{
  $SDCache = new SDCache();
  // If disabled in Settings|Cache, then deactivate cache for frontpage, but
  // caching object must be available in admin so that purging works:
  if(defined('IN_ADMIN') || !empty($mainsettings['enable_caching']))
  {
    $SDCache->SetCacheFolder(defined('SD_CACHE_PATH')  ? SD_CACHE_PATH   : '');
    $SDCache->SetExpireTime(defined('SD_CACHE_EXPIRE') ? SD_CACHE_EXPIRE : (7*24*1440));
  }
}

// ############################################################################
// LOAD SITE SETTINGS
// ############################################################################

$mainsettings = GetSettings();

// SD313 - check for both new and legacy wysiwyg parameters
// If deactivated in mainsettings, it'll be turned off in init.php!
if(defined('IN_ADMIN'))
{
  $load_wysiwyg = GetVar('loadwysiwyg', 0, 'bool');
  if(empty($load_wysiwyg)) $load_wysiwyg = GetVar('load_wysiwyg', $load_wysiwyg, 'bool');
  $load_wysiwyg = $load_wysiwyg || $mainsettings['enablewysiwyg'];
}

// SD313: to avoid frequent array searches, extract all main settings as
// stand-alone variables (*by reference* to their original $mainsettings key),
// e.g. use "$mainsettings_sdurl" instead of "$mainsettings['sdurl']" now
extract($mainsettings, EXTR_PREFIX_ALL | EXTR_REFS, 'mainsettings');
define('SD_QUOTED_URL_EXT', preg_quote($mainsettings_url_extension,'#')); //SD342
unset($mainsettings_email_smtp_pwd,$mainsettings['email_smtp_pwd']); //SD343


//SD370: moved here from index.php, BEFORE mobile detection because of _SESSION!
if(!Is_Ajax_Request())
require_once(ROOT_PATH . 'includes/enablegzip.php');

// Set default timezone - PHP 5.1+
if(function_exists('date_default_timezone_get'))
{
  $sd_ignore_watchdog = true;
  $sd_timezone = @date_default_timezone_get();
  @date_default_timezone_set($sd_timezone);
  /*
  if(false !== ($timezone = sd_getLocalTimezone()))
  {
    @date_default_timezone_set($timezone);
  }
  */
  $sd_ignore_watchdog = false;
}

$mainsettings_websitetitle_original = $mainsettings_websitetitle; //SD342
$mainsettings_censored_words = isset($mainsettings_censored_words) ?
                                 preg_split('/[\r\n]+/', $mainsettings_censored_words, -1, PREG_SPLIT_NO_EMPTY):
                                 array(); //SD342
								 
							

$mainsettings_settings_seo_default_separator =
  !empty($mainsettings_settings_seo_default_separator) ?
  trim($mainsettings_settings_seo_default_separator) : '-';

//SD322: allow back-slashes for date format to escape single characters,
// e.g. "j \d\e F, Y" will output like "4 de Decembre, 2010"
if(strpos($mainsettings_dateformat, '\\\\') !== false)
  $mainsettings_dateformat = stripslashes($mainsettings_dateformat);

// ############################################################################
// DEFINE TECHNICAL EMAIL
// ############################################################################
if(strlen($mainsettings_technicalemail))
  define('TECHNICAL_EMAIL', $mainsettings_technicalemail);
else
  define('TECHNICAL_EMAIL', false);

// ############################################################################
// LOAD SDURL
// ############################################################################
// Check stored sdurl and if currently loaded page is a secure page (SSL):
$server_name = (!empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : (!empty($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : getenv('SERVER_NAME')));
$server_port = (!empty($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : (int) getenv('SERVER_PORT'));
$script_name = (!empty($_SERVER['REQUEST_URI']) ? preg_replace("/\?.*/", '', $_SERVER['REQUEST_URI']) : '');
$secure = ($server_port == 443) || (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') ? 1 : 0;
$mainsettings_sdurl  .= (strlen($mainsettings_sdurl)  && (substr($mainsettings_sdurl, -1) != '/') ? '/' : '');
$mainsettings_sslurl .= (strlen($mainsettings_sslurl) && (substr($mainsettings_sslurl, -1) != '/') ? '/' : '');
$sdurl = ($secure && !SD_IS_BOT && (strlen($mainsettings_sslurl)>4)) ? $mainsettings_sslurl : $mainsettings_sdurl;
// Fallback: if no URL was specified in settings, try to determine current path:
if(!isset($sdurl) || !strlen($sdurl))
{
  // Replace any number of consecutive backslashes and/or slashes with a single slash
  // (could happen on some proxy setups and/or Windows servers)
  if(substr($mainsettings_sdurl, -1) == '/')
    $script_path = trim($script_name);
  else
    $script_path = trim(dirname($script_name));
  $script_path = preg_replace('#[\\\\/]{2,}#', '/', $script_path);
  $url = ($secure ? 'https://' : 'http://') . $server_name;

  if($server_port && (($secure && $server_port <> 443) || (!$secure && $server_port <> 80)))
  {
    // HTTP HOST can carry a port number...
    if(strpos($server_name, ':') === false) $url .= ':' . $server_port;
  }
  $script_path = str_replace('\\', '/', $script_path);
  $script_path = explode('/', $script_path);
  if(isset($script_path[0]) && empty($script_path[0])) $script_path = array_slice($script_path,1);
  $sdurl = $url . (!empty($script_path[0]) && ($script_path[0]!='/')? '/' . $script_path[0] : '') . '/';
  $mainsettings_sdurl = $sdurl;
  unset($url);
}

// Note: $sdurl is kept for compatibility, SITE_URL should be used now:
define('SITE_URL', $sdurl);

if(IN_CSS) return true;

//SD343 TODO: support "rsd" by http://archipelago.phrasewise.com/rsd
/*
if(isset($_GET['rsd']))
{
  header('Content-Type: text/xml; charset=' . SD_CHARSET);
  echo '<?xml version="1.0" encoding="'.SD_CHARSET.'"?'.'>'; ?>
<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd">
  <service>
    <engineName>Subdreamer</engineName>
    <engineLink>http://www.subdreamer.com</engineLink>
    <homePageLink><?php echo SITE_URL; ?></homePageLink>
    <apis>
      <api name="Atom" blogID="" preferred="true" apiLink="<?php echo SITE_URL.'rss.php'; ?>" />
    </apis>
  </service>
</rsd>
<?php
exit;
}
*/

// ############################################################################
// INIT PLUGIN NAME TO ID ARRAY (NOT for admin panel! using cache if possible)
// ############################################################################

$plugin_name_to_id_arr = array();
$plugin_folder_to_id_arr = array();
$plugin_id_base_arr = array(); //SD370
if(!$SDCache || (($plugin_name_to_id_arr   = $SDCache->read_var(CACHE_ALL_PLUGINS, 'plugin_name_to_id_arr')) === false)
             || (($plugin_folder_to_id_arr = $SDCache->read_var(CACHE_ALL_PLUGINS, 'plugin_folder_to_id_arr')) === false)
             || (($plugin_id_base_arr      = $SDCache->read_var(CACHE_ALL_PLUGINS, 'plugin_id_base_arr')) === false))
{
  //SD370: added base_plugin (if exists)
  $tmp = '';
  if($DB->column_exists(PRGM_TABLE_PREFIX.'plugins', 'base_plugin'))
  {
    $tmp = ', base_plugin';
  }
  $get_plugins = $DB->query('SELECT pluginid, name, settingspath'.$tmp.
                            ' FROM {plugins} WHERE pluginid <> 1'.
                            ' ORDER BY pluginid');
  while($plugin_arr = $DB->fetch_array($get_plugins,null,MYSQL_ASSOC))
  {
    $pid = (int)$plugin_arr['pluginid'];
    $plugin_name_to_id_arr[$plugin_arr['name']] = $pid;
    if(strlen($plugin_arr['settingspath']))
    {
      $plugin_folder_to_id_arr[dirname($plugin_arr['settingspath'])] = $pid;
    }
    //SD370: store "base plugin" if present for plugin
    if(!empty($plugin_arr['base_plugin']) && !isset($plugin_id_base_arr[$pid]))
    {
      $plugin_id_base_arr[$pid] = $plugin_arr['base_plugin'];
    }
  }
  if(count($plugin_name_to_id_arr)) $DB->free_result($get_plugins);

  // Rewrite cache file
  if($SDCache)
  $SDCache->write_var(CACHE_ALL_PLUGINS, '',
                      array('plugin_name_to_id_arr'   => $plugin_name_to_id_arr,
                            'plugin_folder_to_id_arr' => $plugin_folder_to_id_arr,
                            'plugin_id_base_arr'      => $plugin_id_base_arr), true);
  unset($get_plugins, $plugin_arr);
}

if(defined('IN_ADMIN'))
{
  //SD313: If not allowed in mainsettings, disable WYSIWYG editor load in admin
  $load_wysiwyg = (($mainsettings_enablewysiwyg && !empty($load_wysiwyg)) ? 1 : 0);
}

// ############################################################################
// GET ALL PAGES DATA (or load from cache if enabled)
// ############################################################################

// Clear/initialize variables for safety
unset($categoryname, $categorylink, $categoryids, $category_urlname);

// Initialize global "sd_cache" variable for further use (always!)
$sd_cache = array();
$sd_cache['categories']       = array();
$sd_cache['category_parents'] = array();
$sd_cache['category_urlname'] = array();
$sd_cache['skinvars']         = array(); //SD343

// Load "categories"-related cached values (it'll be false if inactive/not found)
// The result will be stored in global "sd_cache" array variable!
if(!defined('IN_ADMIN') && $SDCache && $SDCache->IsActive() &&
   ($result = $SDCache->read_var(CACHE_ALL_CATEGORIES,
                                 array('categories',
                                       'category_parents',
                                       'category_urlname'))) !== false)
{
  $sd_cache['categories']       = (isset($result['categories'])      ? $result['categories'] :'');
  $sd_cache['category_parents'] = (isset($result['category_parents'])? $result['category_parents']:'');
  $sd_cache['category_urlname'] = (isset($result['category_urlname'])? $result['category_urlname']:'');
  $sd_cache['skinvars']         = (isset($result['skinvars'])        ? $result['skinvars']: false); //SD343
  $category_count = count($sd_cache['categories']);
}
else
{
  // Load all pages (full rows) and store in cache - if enabled and not admin panel
  //SD371: upgrade from 260 failed due to "design_name" column:
  if(version_compare(SD_VERSION,'3.0.0','<'))
    $dcol = "'sd260skin'";
  else
    $dcol = "IFNULL(d.design_name,'')";
  if($getcategories = $DB->query("SELECT c.*, ".$dcol.' design_name'.
                                 ' FROM {categories} c'.
                                 ' LEFT JOIN {designs} d ON d.designid = c.designid'.
                                 ' ORDER BY c.parentid, c.displayorder'))
  {
    while($category = $DB->fetch_array($getcategories,null,MYSQL_ASSOC))
    {
      $cat_id = $category['categoryid'];
      $sd_cache['categories'][$cat_id] = $category;
      $sd_cache['category_parents'][$category['parentid']][] = $cat_id;
      $sd_cache['category_urlname'][$category['urlname']] = $cat_id;
    }
    $category_count = count($sd_cache['categories']);

    //  Rewrite cache file (if enabled)
    if(!defined('IN_ADMIN') && $SDCache && $SDCache->IsActive())
    {
      $SDCache->write_var(CACHE_ALL_CATEGORIES, '',
                          array('categories'       => $sd_cache['categories'],
                                'category_parents' => $sd_cache['category_parents'],
                                'category_urlname' => $sd_cache['category_urlname']), true);
    }
  }
  unset($dcol);
  if($category_count) $DB->free_result($getcategories);
}

//SD362: new table and cache for "{plugins_titles}", which contains
// structural info to get to a plugins' items titles
$sd_cache['plugins_titles'] = false;
if(!defined('IN_ADMIN') && $SDCache && $SDCache->IsActive() &&
   ($result = $SDCache->read_var(CACHE_PLUGIN_TITLES,
                                 array('plugins_titles'))) !== false)
{
  $sd_cache['plugins_titles'] = (isset($result['plugins_titles']) ? $result['plugins_titles'] : false);
}
else
if($DB->table_exists(PRGM_TABLE_PREFIX.'plugins_titles'))
{
  // Load plugins_titles and store in cache - if enabled and not admin panel
  if($tmp = $DB->query('SELECT * FROM {plugins_titles} ORDER BY pluginid'))
  {
    while($p = $DB->fetch_array($tmp,null,MYSQL_ASSOC))
    {
      $sd_cache['plugins_titles'][$p['pluginid']] = $p;
    }
  }
  //  Rewrite cache file (if enabled)
  if(!defined('IN_ADMIN') && $SDCache && $SDCache->IsActive())
  {
    $SDCache->write_var(CACHE_PLUGIN_TITLES, '',
                        array('plugins_titles' => $sd_cache['plugins_titles']), true);
  }
  unset($tmp,$p);
}
unset($cat_id,$category,$getcategories,$result);

// Fast access to categories:
$pages_parents_md_arr = &$sd_cache['category_parents']; //SD343
$pages_md_arr  = &$sd_cache['categories'];
$pages_seo_arr = array(); #SD371
//SD360: sort by key/pagenames:
if(isset($sd_cache['category_urlname']))
{
  $pages_seo_arr = &$sd_cache['category_urlname'];
  $pages_seo_arr = array_flip($pages_seo_arr);
  natcasesort($pages_seo_arr);
  $pages_seo_arr = array_flip($pages_seo_arr);
}
$plugins_titles_arr = &$sd_cache['plugins_titles']; //SD362


// ############################################################################
// LOAD GLOBAL LANGUAGE PHRASES
// ############################################################################
$sdlanguage = GetLanguage(1);

//SD313: new array:
$sd_months_arr = array('unknown', // index 0!
                       $sdlanguage['January'], $sdlanguage['February'], $sdlanguage['March'],
                       $sdlanguage['April'],   $sdlanguage['May'],      $sdlanguage['June'],
                       $sdlanguage['July'],    $sdlanguage['August'],   $sdlanguage['September'],
                       $sdlanguage['October'], $sdlanguage['November'], $sdlanguage['December']);
//SD342:
$sd_days_long_arr = array( $sdlanguage['Sunday'],   $sdlanguage['Monday'],  $sdlanguage['Tuesday'],
                           $sdlanguage['Wednesday'],$sdlanguage['Thursday'],$sdlanguage['Friday'],
                           $sdlanguage['Saturday']);
$sd_days_short_arr = array( $sdlanguage['Sun'], $sdlanguage['Mon'], $sdlanguage['Tue'],
                            $sdlanguage['Wed'], $sdlanguage['Thu'], $sdlanguage['Fri'],
                            $sdlanguage['Sat']);

//SD343: global list of "stop words" (for meta keywords, seo titles); also before userprofile!
$sd_seo_filter_words = !empty($mainsettings_seo_stop_words_list);
$sd_seo_stop_words_list = (!defined('UPGRADING_PRGM') && $sd_seo_filter_words ?
                            sd_ConvertStrToArray($mainsettings_seo_stop_words_list, "\r|\n") :
                            array());
$sd_seo_stop_words_list_flip = @array_flip($sd_seo_stop_words_list);

$sd_seo_protect_words = !empty($mainsettings['seo_protect_words']);
$sd_seo_protect_words = (!defined('UPGRADING_PRGM') && $sd_seo_protect_words ?
                            sd_ConvertStrToArray($mainsettings['seo_protect_words'], "\r|\n") :
                            array());
$sd_seo_protect_words_flip = array_flip($sd_seo_protect_words);

// ############################################################################
// LOAD SESSION (INCL. FORUM INTEGRATION)
// ############################################################################
// IF NOT UPGRADING...
$UserProfile = false;
if(!Is_Ajax_Request() && !defined('UPGRADING_PRGM'))
{
  //SD322: load user profile fields (stored in table "users_data")
  if(defined('SD_342')) //SD342 avoid extended errors on upgrade
  {
    include_once(SD_INCLUDE_PATH.'class_userprofile.php');
    if(class_exists('SDUserProfile'))
    $UserProfile = new SDUserProfile();
  }
}

if((strtolower(substr($usersystem['name'],0,9)) == 'vbulletin') && !empty($mainsettings_vb_cookie_salt))
{
  define('COOKIE_SALT', $mainsettings_vb_cookie_salt);
  define('SD_COOKIE_SALT', $mainsettings_vb_cookie_salt);
}
else
{
  define('SD_COOKIE_SALT', SD_DBNAME); // alternative cookie salt
}

@require_once(ROOT_PATH . 'includes/session.php');

if(!defined('UPGRADING_PRGM')) $p12_settings = GetPluginSettings(12); //SD342

//SD370
define('SD_MOBILE_FEATURES', $DB->table_exists(PRGM_TABLE_PREFIX.'pagesort_mobile'));

// NOW CHECK FOR USER LOGIN - ONLY IN FRONTPAGE
if(!defined('IN_ADMIN') && !empty($usersystem))
{
  //SD343: check for "Auto-Block URIs"
  if(!empty($p12_settings['auto_block_uris']) && strlen($uri))
  if(sd_IsUriBlocked($uri, $p12_settings['auto_block_uris']))
  {
    sleep(3);
    header("HTTP/1.0 403 Forbidden");
    DisplayMessage('Not allowed.',true);
    exit();
  }

  if($DB->database != $usersystem['dbname']) $DB->select_db($usersystem['dbname']);
  require(SD_INCLUDE_PATH . 'usersystems/' . $usersystem['queryfile']);
  $dbname = SD_DBNAME;
  if($DB->database != SD_DBNAME) $DB->select_db(SD_DBNAME);

  //SD370: moved here from "session.php" for frontpage
  if(!defined('COOKIE_TIMEOUT'))
  {
    // The frontpage cookie timeout is not yet configurable, so set it to 7 days:
    if(isset($mainsettings['usercookietimeout']))
    {
      $timeout = intval($mainsettings['usercookietimeout']);
      // Allow 0 = never timout), otherwise lowest value is 1 day:
      $timeout = (($timeout != 0) && ($timeout < 86400)) ? 86400 : $timeout;
      define('COOKIE_TIMEOUT', $timeout);
      unset($timeout);
    }
    defined('COOKIE_TIMEOUT') || define('COOKIE_TIMEOUT', 86400 * 7); // 7 days
  }

  //SD322: make sure that both constants are filled
  if(!defined('USERIP') && defined('IPADDRESS'))
  {
    define('USERIP', IPADDRESS);
  }
  else
  if(defined('USERIP') && !defined('IPADDRESS'))
  {
    define('IPADDRESS', USERIP);
  }

  $userinfo = GetUserInfo(isset($usersettings)?$usersettings:array());
  unset($usersettings);


  //SD343: special tool to block known bad hosts/bots
  //ZB Block: http://www.spambotsecurity.com/zbblock.php
  //MUST be enabled (defined) within "branding.php"!
  if( !defined('IN_ADMIN') && defined('ZB_BLOCK') && ZB_BLOCK &&
      file_exists(ROOT_PATH.'/includes/zbblock/zbblock.php') )
  {
    include_once(ROOT_PATH.'/includes/zbblock/zbblock.php');
  }

  // ###################### DETECT MOBILE DEVICE ##############################
  //SD343: mobile detection, see http://blog.mobileesp.com/?page_id=101
  //SD370: added session handling for mobile view override

  $isMobile = $isMobile_org = $sd_mobile_detect = $sd_session_started = false;
  if(!defined('IN_ADMIN') && !empty($mainsettings_enable_mobile_detection) && SD_MOBILE_FEATURES)
  {
    @include_once(SD_CLASS_PATH . 'mdetect.php');
    if(class_exists('uagent_info'))
    {
      $sd_mobile_detect = new uagent_info();
      $isMobile = $isMobile_org = $sd_mobile_detect->DetectMobileLong();
    }
    // enforce mobile view (only for one pageload)?
    if(!$isMobile && isset($_GET['sd_mob']) && ($_GET['sd_mob'] == 'on'))
    {
      $_GET['sd_mob'] = 0; $isMobile = 1;
    }
    if($isMobile && function_exists('session_start') && !isset($_SESSION))
       /*&& (session_status()==PHP_SESSION_NONE)*/
    {
      $sd_prev_session_name = session_name("sdmobile");
      $sd_session_started = session_start();
    }
    // Check for override param
    if(isset($_GET['sd_mob']))
    {
      $isMobile = ($_GET['sd_mob']!=1);
      $_SESSION['mobile_override'] = $isMobile?0:1;
    }
    else
    if(isset($_SESSION['mobile_override']))
    {
      $isMobile = ($_SESSION['mobile_override']!=1);
    }
  }
  define('SD_MOBILE_DEVICE', $isMobile_org);
  define('SD_MOBILE_ENABLED', $isMobile);
  unset($isMobile_org,$isMobile,$sd_mobile_detect,$sd_session_started);
}
defined('SD_MOBILE_DEVICE')  || define('SD_MOBILE_DEVICE', false);
defined('SD_MOBILE_ENABLED') || define('SD_MOBILE_ENABLED', false);
defined('MOBILE_CACHE')      || define('MOBILE_CACHE', '_mob_');
if($DB->database != SD_DBNAME) $DB->select_db(SD_DBNAME);


// ############################################################################
// STOP EXECUTION ON COMMON BASIC URL ATTACKS
// ############################################################################

//SD343 more security checks:
$doBlock = false;
$sd_ignore_watchdog = true;
if(($uri && preg_match('#%00|-dallow_url_include|page=/proc/self/environ|php\://input|base64_decode.*\(.*\)|base64_encode.*\(.*\)|'.
                       '(\<|%3C).*script.*(\>|%3E)|GLOBALS(=|\[|\%[0-9A-Z]{0,2})|_REQUEST(=|\[|\%[0-9A-Z]{0,2})|'.
                       'wp-content/|userinfo(\[|%5B)|user_arr\b|custompluginfile\b|templatefile\b|'.
                       'zZz_ADOConnection|%7Deval%20|(\$|%24)_GET|(\$|%24)_POST|timthumb\.php|posting\.php|\.\./proc|'.
                       '&lt;\?|foreach.?array#i', urldecode($uri)))
   || (isset($_SERVER['QUERY_STRING']) && strpos(urldecode($_SERVER['QUERY_STRING']), chr(0)) !== false) )
{
  $doBlock = 2;
}
unset($sd_org_post);

if($doBlock !== false)
{
  if(function_exists('WatchDog'))
  {
    if($doBlock==2)
      WatchDog('System', 'URL hacking attempt blocked!', WATCHDOG_ERROR);
    else
      WatchDog('System', 'Suspicious form data submitted (potential hacking attempt)!', WATCHDOG_ERROR);
  }
  sleep(3);
  header("HTTP/1.0 403 Forbidden");
  header("HTTP/1.1 403 Forbidden");
  echo "HTTP/1.1 403 Forbidden";
  exit();
}
$sd_ignore_watchdog = false;


// SD341: Re-apply own error handler
if(function_exists('ErrorHandler'))
{
  set_error_handler('ErrorHandler'); // in functions_global.php
}

// ############################# LOAD MODULES #################################
//SD343: re-introducing SD Modules (from SD 2.6x)
$sd_modules = null;
if(!defined('INSTALLING_PRGRM') && !defined('UPGRADING_PRGRM') &&

   !defined('SD_MODULES_PATH') && strpos($_SERVER['REQUEST_URI'],'vvc.php?vvcid=')===false)
{
  define('SD_MODULES_PATH', SD_INCLUDE_PATH . 'modules'.DIRECTORY_SEPARATOR);
  if(file_exists(SD_CLASS_PATH . 'class_sd_modules.php'))
  {
    include_once(SD_CLASS_PATH . 'class_sd_modules.php');
    if(class_exists('SD_Modules'))
    {
      $sd_modules = new SD_Modules(SD_MODULES_PATH);
      $sd_modules->LoadModules();

      // Now - within the global scope! - "include()" the actual module file:
      if(!defined('IN_ADMIN'))
      {
        foreach($sd_modules->Modules as $currmod)
        {
          if($currmod['enabled'] && (!defined('IN_ADMIN') || $currmod['load_admin']))
          {
            @include_once(SD_MODULES_PATH . $currmod['modulepath']);
          }
        }
        unset($currmod);
      }
    }
  }
}

if(!empty($userinfo['userid']))
{
  if(empty($userinfo['salt'])) $userinfo['salt'] = substr($userinfo['username'],0,30);
  $userinfo['securitytoken_raw'] = sha1($userinfo['userid'] . sha1($userinfo['salt']) . sha1(SD_COOKIE_SALT));
}
else
{
  $userinfo['securitytoken_raw'] = GenerateSecureToken();
}
$userinfo['securitytoken'] = TIME_NOW . '-' . sha1(TIME_NOW . $userinfo['securitytoken_raw']);

// SD313: extra token for forms to add security against spam/bot submissions
// Takes into account (logged in) user. Located in "functions_security.php"!
define('SD_FORM_TOKEN', $userinfo['securitytoken']);
define('SD_URL_TOKEN',  PrintSecureUrlToken());

// ############################################################################
// ###################### RETURN IF UPGRADING PRGM ############################
// ############################################################################
if(defined('UPGRADING_PRGM')) return true;

$bbcode = false;
if(!empty($mainsettings_allow_bbcode))
{
  $bbcode = new BBCode;
  $bbcode->SetAllowAmpersand(true);
  $bbcode->SetSmileyDir(ROOT_PATH . 'includes/images/smileys'); //SD332: Smileys moved!
  $bbcode->SetSmileyURL(SITE_URL  . 'includes/images/smileys');
  $bbcode->SetLocalImgURL(SITE_URL  . 'images');
  $bbcode->SetLocalImgDir(ROOT_PATH . 'images');
  if(isset($mainsettings_allow_smilies) && !$mainsettings_allow_smilies)
  {
    $bbcode->ClearSmileys();
  }
  else
  {
    //SD342: merge configured smilies with bbcode's smilies
    if(function_exists('LoadSmilies')) LoadSmilies();
  }
}

if(defined('SD_342') && class_exists('SDUserCache'))
{
  SDUserCache::Init(); //SD342 this requires $mainsettings!
}

// ############################################################################
// INIT CAPTCHA AND COMMENT CLASS (FRONTPAGE ONLY)
// ############################################################################

#if(!defined('IN_ADMIN'))  //SD370: needed for admin now!
{
  // ########## REGISTER_PATH, USERCP_PATH, LOSTPWD_PATH, LOGIN_PATH ##########
  $cppath = $forgotpwdpath = $login_path = $regpath = '';
  if($usersystem['name'] != 'Subdreamer')
  {
    if(function_exists('ForumLink'))
    {
      $cppath = ForumLink(2);
      $regpath = ForumLink(1);
      $forgotpwdpath = ForumLink(3);
    }
    else
    {
      $database['name'] = isset($database['name']) ? $database['name'] : '';
      $usersystem = array('name' => 'Subdreamer', 'dbname' => $database['name'],
                          'usersystemid' => 1, 'queryfile' => 'subdreamer.php',
                          'tblprefix' => (defined('PRGM_TABLE_PREFIX')?PRGM_TABLE_PREFIX:''));
    }
  }
  else
  {
    //SD370: added mobile links
    if(SD_MOBILE_ENABLED && !empty($mainsettings_mobile_registration_page_id))
    {
      $regpath = RewriteLink('index.php?categoryid=' . (int)$mainsettings_mobile_registration_page_id);
      $forgotpwdpath = RewriteLink('index.php?categoryid=' . (int)$mainsettings_mobile_registration_page_id.'&p12_forgotpwd=1');
    }
    else
    if(!empty($mainsettings_user_registration_page_id))
    {
      $regpath = RewriteLink('index.php?categoryid=' . (int)$mainsettings_user_registration_page_id);
      $forgotpwdpath = RewriteLink('index.php?categoryid=' . (int)$mainsettings_user_registration_page_id.'&p12_forgotpwd=1');
    }
    if(SD_MOBILE_ENABLED && !empty($mainsettings_mobile_profile_page_id))
    {
      $cppath = RewriteLink('index.php?categoryid=' . (int)$mainsettings_mobile_profile_page_id);
    }
    else
    if(!empty($mainsettings_user_profile_page_id))
    {
      $cppath = RewriteLink('index.php?categoryid=' . (int)$mainsettings_user_profile_page_id);
    }
  }
  if(SD_MOBILE_ENABLED &&
     !empty($mainsettings_mobile_panel_page_id) && !empty($userinfo['categoryviewids']) &&
     @in_array($mainsettings_mobile_panel_page_id, $userinfo['categoryviewids']))
  {
    $login_path = RewriteLink('index.php?categoryid=' . (int)$mainsettings_mobile_panel_page_id);
  }
  else
  if(!empty($mainsettings_user_login_panel_page_id) && !empty($userinfo['categoryviewids']) &&
     @in_array($mainsettings_user_login_panel_page_id, $userinfo['categoryviewids']))
  {
    $login_path = RewriteLink('index.php?categoryid=' . (int)$mainsettings_user_login_panel_page_id);
  }

  define('REGISTER_PATH', $regpath); // user registration path
  define('LOSTPWD_PATH', $forgotpwdpath); // path to lost username/password
  define('CP_PATH', $cppath);  // user control panel
  define('LOGIN_PATH', $login_path);

  unset($regpath, $forgotpwdpath, $cppath, $login_path);
}
if($DB->database != SD_DBNAME) $DB->select_db(SD_DBNAME);

if(!defined('IN_ADMIN'))
{
  $captcha = new Captcha();
  $captcha->publickey  = $mainsettings_captcha_publickey;
  $captcha->privatekey = $mainsettings_captcha_privatekey;

  unset($mainsettings_captcha_publickey, $mainsettings_captcha_privatekey);

  if(!class_exists('Comments'))
  {
    require_once(SD_INCLUDE_PATH . 'class_comments.php');
  }
  $Comments = new Comments();
  if($SDCache)
  {
    $Comments->CacheObj =& $SDCache; //SD313: pass cache object
  }
  $Comments->InitCommentsCount();
}

//SD322: moved declaration of "iif" from functions_global here because the
// same function may have been defined by integrated vB forum files
if(!function_exists('iif'))
{
  function iif($expression, $returntrue, $returnfalse = '')
  {
    return $expression ? $returntrue : $returnfalse;
  }
}

//SD322: moved declaration of "unhtmlspecialchars" from functions_global here
// because the same function may have been defined by integrated vB forum files
if(!function_exists('unhtmlspecialchars'))
{
  function unhtmlspecialchars($string)
  {
    return sd_unhtmlspecialchars($string);
  }
}

// FOR LEGACY CODE:
$stylepath = '';



if(defined('IN_ADMIN')) $admin_phrases = LoadAdminPhrases();
$plugin_names = LoadPluginNames(); //SD332 now also for frontpage

//SD362: load admin sidebar if enabled in branding.php
if(defined('IN_ADMIN') && defined('SD_ADMIN_SIDEBAR') && SD_ADMIN_SIDEBAR)
{
  @include(ROOT_PATH . ADMIN_PATH . '/sidebar.php');
}

// SD400: include header class
if(defined('IN_ADMIN'))
{
	// SD Header
	require_once(SD_INCLUDE_PATH . 'class_sd_header.php');
	$sd_head = new sd_header();
	
	// SD Widget
	require_once(SD_INCLUDE_PATH . 'class_sd_widget.php');
	$sd_widget = new sd_widget();
}
defined('SD_ADMIN_SIDEBAR_JS') || define('SD_ADMIN_SIDEBAR_JS', '');
defined('SD_ADMIN_SIDEBAR_LOADED') || define('SD_ADMIN_SIDEBAR_LOADED', false);
