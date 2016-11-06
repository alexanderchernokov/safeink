<?php
define('IN_CSS', true);
define('IN_PRGM', true);
define('ROOT_PATH', './');
define('SD_INCLUDE_PATH','includes/');
define('CACHE_PAGE_CSS', 'page_css');
define('CACHE_PAGE_PREFIX', 'page_'); //SD343 - was missing
define('MOBILE_CACHE', '_mob_'); //SD370
@error_reporting(0);
@ini_set('display_errors','0');

function DetectXSSinjection($input)
{
  // SD313: detect XSS injection (see http://niiconsulting.com/innovation/snortsignatures.html)
  if(preg_match("/((\%3C)|<)((\%2F)|\/)*[a-z0-9\%]+((\%3E)|>)/i", $input) ||
     preg_match("/((\%3C)|<)((\%69)|i|(\%49))((\%6D)|m|(\%4D))((\%67)|g|(\%47))[^\n]+((\%3E)|>)/i", $input))
  {
    return true;
  }

  return false;

} //DetectXSSinjection


// ############################################################################
// STOP EXECUTION ON COMMON BASIC URL ATTACKS
// ############################################################################
$uri = isset($_SERVER['REQUEST_URI']) ? strip_tags($_SERVER['REQUEST_URI']) : '';
$sd_org_post = false; //SD343
if(isset($_POST) && !empty($_POST))
{
  $sd_org_post = @var_export($_POST,true);
}

//SD343 one more security check on URL:
if(($uri && preg_match('#%00|page=(/proc/self/environ|php\://input)|base64_decode.*\(.*\)|base64_encode.*\(.*\)|(\<|%3C).*script.*(\>|%3E)|GLOBALS(=|\[|\%[0-9A-Z]{0,2})|_REQUEST(=|\[|\%[0-9A-Z]{0,2})|'.
                       'login\.php|userinfo%5B|user_arr%5B|custompluginfile\[|custompluginfile%5B|posting\.php|\.\./proc#', $uri))
   || (isset($_SERVER['QUERY_STRING']) && strpos(urldecode($_SERVER['QUERY_STRING']), chr(0)) !== false) )
{
  header("HTTP/1.0 403 Forbidden");
  echo "HTTP/1.0 403 Forbidden";
  exit();
}
if(DetectXSSinjection($uri))
{
  header("HTTP/1.0 403 Forbidden");
  echo "HTTP/1.0 403 Forbidden";
  exit();
}
if(($sd_org_post !== false) && preg_match("#[\[php\]]?\s*eval\s*\(\s*base64_decode\s*\(\s*'#i",$sd_org_post))
{
  header("HTTP/1.0 403 Forbidden");
  echo "HTTP/1.0 403 Forbidden";
  exit();
}
unset($sd_org_post);

//SD342 build stylesheet content with solely @import statements in it
if(!empty($_GET['import']) && is_string($_GET['import']) && (strlen($_GET['import'])>3))
{
  $prefix = "\n".'@import url("'.SD_INCLUDE_PATH;
  $list = explode(',',$_GET['import']);
  if(empty($list)) exit();
  $count = 0;
  $result = '';
  foreach($list as $css)
  {
    switch($css)
    {
      case 'ceebox':
        $result .= $prefix.'css/ceebox.css");';
        break;
      case 'jdialog':
        $result .= $prefix.'css/jquery.jdialog.css");';
        break;
      case 'syntax':
        $result .= $prefix.'javascript/syntax/jquery.syntax.core.css");'.
                   $prefix.'javascript/syntax/jquery.syntax.brush.css.css");';
        break;
    }
    $count++;
    if($count>10) break; // limit to 10 params (security)
  }
  echo $result;
  exit();
}


// ############################################################################
// FETCH URI PARAMS
// ############################################################################
$categoryid = isset($_GET['pageid']) && is_numeric($_GET['pageid']) ? (int)$_GET['pageid'] : 1;
$categoryid = ($categoryid>0) && ($categoryid<9999999) ? $categoryid : 1;
$style = isset($_GET['style']) ? (string)$_GET['style'] : null;
$root = !empty($_GET['style']);

// ############################################################################
// LOAD CONFIG DATA
// ############################################################################
if(!@include(ROOT_PATH . 'includes/config.php')) exit();
if(!defined('PRGM_INSTALLED') && !defined('SD_INSTALLED')) exit();

if(!defined('PRGM_TABLE_PREFIX'))
{
  // user upgrade from 260 (using old config file)
  $database = array();
  $database['name']        = $dbname;
  $database['password']    = $dbpassword;
  $database['server_name'] = $servername;
  $database['username']    = $dbusername;

  unset($servername, $dbusername, $dbpassword);

  if(defined('SD_INSTALLED'))
  {
    define('PRGM_INSTALLED', true);
  }

  // very old versions of sdcms did not have table_prefix defined
  if(!defined('TABLE_PREFIX'))
  {
    define('TABLE_PREFIX', '');
  }

  define('PRGM_TABLE_PREFIX', TABLE_PREFIX);
}
else
{
  // fresh copy of SD3 (new config file)
  define('TABLE_PREFIX', PRGM_TABLE_PREFIX);
  $dbname = $database['name'];
}

// ###############################################################################
// ESTABLISH DATABASE CONNECTION
// ###############################################################################

if(!@include(ROOT_PATH . 'includes/mysql.php')) exit();

$DB = new DB;
$DB->server   = $database['server_name'];
$DB->database = $database['name'];
$DB->user     = $database['username'];
$DB->password = $database['password'];
$DB->connect();

// clear out the username and password for protection
$database['username'] = '';
$database['password'] = '';
$DB->password = '';

// ############################################################################
// INITIALIZE CACHE OBJECT (BOTH admin and frontend! AFTER header call!)
// DO NOT REMOVE OR MAKE DEPENDENT ON ANY CONDITION!!!
// ############################################################################
$SDCache = false;
defined('ADMIN_PATH') || define('ADMIN_PATH', 'admin'); //SD343: make sure it's defined
require(ROOT_PATH . ADMIN_PATH . '/prgm_info.php');
if(empty($style))
{
  require(SD_INCLUDE_PATH . 'class_cache.php');
  if(class_exists('SDCache'))
  {
    $SDCache = new SDCache();
    // If disabled in Settings|Cache, then deactivate cache for frontpage, but
    // caching object must be available in admin so that purging works:
    $enable_caching = $DB->query_first('SELECT value FROM '.PRGM_TABLE_PREFIX."mainsettings WHERE varname = 'enable_caching'");
    if(defined('IN_ADMIN') || !empty($enable_caching['value']))
    {
      $SDCache->SetCacheFolder(defined('SD_CACHE_PATH')  ? SD_CACHE_PATH   : ROOT_PATH.'cache/');
      $SDCache->SetExpireTime(defined('SD_CACHE_EXPIRE') ? SD_CACHE_EXPIRE : (7*86400));
    }
  }
}

$cache_enabled = false;//isset($SDCache) && $SDCache->IsActive();

$enable_gzip = $DB->query_first('SELECT value FROM '.PRGM_TABLE_PREFIX."mainsettings WHERE varname = 'gzipcompress'");
$mainsettings['gzipcompress'] = !empty($enable_gzip['value']);
include(ROOT_PATH.'includes/enablegzip.php');
header("Content-type: text/css");
header("Accept-Charset: utf-8");
header("Charset: utf-8");
if($style!='WYSIWYG')
{
  header('Cache-Control: public');
  header("Cache-Control: max-age=604800, must-revalidate");
  $ExpStr = 'Last-Modified: ' . @gmdate("D, d M Y H:i:s", time() - 3600) . ' GMT';
  header($ExpStr);
  if(empty($style))
  {
    $ExpStr = "Expires: " . @gmdate("D, d M Y H:i:s", time() + 604800) . ' GMT';
    header($ExpStr);
  }
  unset($ExpStr);
}

//SD371: skin override (e.g. for site testing)
$skinid_override = 0;
if(isset($_GET['skinid']) && ctype_digit($_GET['skinid']) &&
   ($skinid_override = (int)$_GET['skinid']))
{
  if(!$DB->query_first('SELECT 1 FROM {skins} WHERE skinid = '.$skinid_override))
  {
    $skinid_override = 0;
  }
}

$legacy = true;
$check_plugins = false;
$DB->ignore_error = true;
$DB->result_type = MYSQL_ASSOC; //SD342
//SD370: fringe case: use latest skin available if none is activated properly
if(!$skin_engine = $DB->query_first('SELECT skinid, skin_engine FROM '.
                   PRGM_TABLE_PREFIX.'skins'.
                   ' WHERE '.($skinid_override?'skinid = '.$skinid_override:' activated = 1') ))
{
  //SD370: in case of wrong skin install (no active one), pick the last one:
  $DB->result_type = MYSQL_ASSOC;
  $skin_engine = $DB->query_first('SELECT skinid, skin_engine FROM '.
                 PRGM_TABLE_PREFIX.'skins ORDER BY skinid DESC LIMIT 1');
}
$legacy = isset($skin_engine['skin_engine']) ? ($skin_engine['skin_engine'] != 2) : false;
$skinid = isset($skin_engine['skinid'])?(int)$skin_engine['skinid']:0;
unset($skin_engine);

$pagesort = PRGM_TABLE_PREFIX.'pagesort';
$mobile = !empty($_GET['mobile']) && $DB->table_exists($pagesort.'_mobile'); //SD370
$plugins = array(); //SD343
$skin_css = false;
if($cache_enabled)
{
  $skin_css = $SDCache->read_var(CACHE_PAGE_CSS.'_'.($mobile?MOBILE_CACHE:'').$categoryid, 'skin_css');
}
if(!$skin_css && ($categoryid || !isset($style)) && ($style!='WYSIWYG')) //SD343
{
  if(!empty($categoryid))
  {
    //SD343: Fetch all plugins for targeted page; is_object()
    //SD370: added mobile
    if(!$cache_enabled || !is_object($SDCache) ||
       (($plugins = $SDCache->read_var(($mobile?MOBILE_CACHE:'').CACHE_PAGE_PREFIX.$categoryid, 'c_pluginids')) === false))
    {
      $pagesort .= ($mobile ? '_mobile' : ''); //SD370
      $getplugins = $DB->query('SELECT pluginid FROM '.$pagesort.
                               ' WHERE categoryid = '.(int)$categoryid.
                               " AND pluginid <> '1'");
      while($p = $DB->fetch_array($getplugins,null,MYSQL_ASSOC))
      {
        $plugins[] = $p['pluginid'];
      }
    }
    $plugins = array_unique(array_values($plugins));
    foreach($plugins AS $k => $v)
    {
      if(($v=='1') || (substr($v,0,1)=='c')) unset($plugins[$k]);
    }
    if(!empty($plugins))
    {
      @sort($plugins);
    }
    $check_plugins = true;
  }
}

if(empty($skin_css))
{
  $skin_css = $skinWhere = '';
  $default_css = $skin_css = $css_vars = array();

  if(strtolower($style)=='wysiwyg')
  {
    $skinWhere = " AND sc.skin_id IN(%d,0) AND sc.var_name = 'WYSIWYG'";
    $order = " ORDER BY skin_id DESC LIMIT 1";
  }
  else
  {
    if($mobile)
      $order = " ORDER BY IF(sc.var_name='mobile-skin-css',0,1),sc.plugin_id";
    else
      $order = " ORDER BY IF(sc.var_name='skin-css',0,1),sc.plugin_id";
    $skinWhere = ' AND sc.skin_id = %d';

    if(!empty($style))
    {
      $skinWhere .= " AND sc.var_name = '".$DB->escape_string($style)."'";
    }
    else
    {
      /*
      if($legacy)
      {
        $skinWhere .= " AND sc.admin_only = 0 AND sc.var_name != 'skin-css'";
      }
      else
      {
        $skinWhere .= ' AND sc.admin_only = 0';
      }
      */
      $skinWhere .= ' AND sc.admin_only = 0';
    }
    $skinWhere .= " AND sc.var_name != 'WYSIWYG' ";

    if($check_plugins)
    {
      $plugins = empty($plugins) ? array(1) : $plugins;
      //SD370: added "trim"
      $skinWhere .= ' AND (sc.plugin_id = 0 OR (sc.plugin_id IN ('.trim(implode(',',$plugins),',').')))';
    }
    //SD370
    if($DB->column_exists(PRGM_TABLE_PREFIX.'skin_css', 'mobile'))
    {
      /*
      #commented out: decided not to have each CSS entry to have a 2nd entry!
      $skinWhere .= ' AND sc.mobile = '.($mobile?'1':'0');
      */

      if($mobile)
        # for mobile exclude the "skin-css" entry
        $skinWhere .= " AND sc.var_name != 'skin-css' ";
      else
        $skinWhere .= " AND sc.mobile = 0 AND sc.var_name != 'mobile-skin-css'";
    }
    else
      $skinWhere .= " AND sc.var_name != 'mobile-skin-css'";
  }

  // ##########################################################################
  // Fetch current page's css
  // ##########################################################################
  if($get_css = $DB->query('SELECT sc.var_name, sc.css, sc.disabled'.
                           ' FROM '.PRGM_TABLE_PREFIX.'skin_css sc'.
                           ' WHERE sc.var_name IS NOT NULL '. $skinWhere . $order, $skinid))
  {
    while($d = $DB->fetch_array($get_css,null,MYSQL_ASSOC))
    {
      if(empty($d['disabled']))
      {
        $skin_css[$d['var_name']] = $d['css'];
        if(!in_array($d['var_name'],$css_vars)) $css_vars[$d['var_name']] = 1;
      }
    }
  }
  if(count($css_vars)) @ksort($css_vars);
  unset($get_css);

  // ##########################################################################
  // Fetch all default CSS
  // ##########################################################################
  //SD342: added "disabled"
  if(strtolower($style)!='wysiwyg')
  {
    if($get_css = $DB->query('SELECT sc.var_name, sc.css FROM '.PRGM_TABLE_PREFIX.'skin_css sc'.
                             " WHERE sc.disabled = 0 AND sc.var_name != 'WYSIWYG' ".
                             $skinWhere.
                             " ORDER BY IF(sc.var_name='".($mobile?'mobile-':'')."skin-css',0,1),sc.plugin_id", 0))
    {
      while($d = $DB->fetch_array($get_css,null,MYSQL_ASSOC))
      {
        if(!empty($d['css']) && strlen($d['css']))
        {
          if(!isset($css_vars[$d['var_name']])) $default_css[$d['var_name']] = $d['css'];
        }
      }
    }
    unset($get_css);
  }

  // Merge skin's CSS with default CSS
  foreach($default_css AS $var_name => $css)
  {
    if(!isset($css_vars[$var_name]))
    {
      $skin_css[$var_name] = $css . "\n";
    }
  }
  $skin_css = implode("\r\n",$skin_css);

  if($root)
  {
    // Fix some CSS paths relative to includes folder
    $skin_css = str_replace('url(includes/', 'url(./../includes/',$skin_css);
  }

  // SD313: Store CSS in cache file (if not single style request)
  if($cache_enabled && empty($style))
  {
    $SDCache->write_var(CACHE_PAGE_CSS.'_'.($mobile?MOBILE_CACHE:'').$categoryid, 'skin_css', $skin_css);
  }
}

echo $skin_css;

// ############################################################################
// CLOSE CONNECTION
// ############################################################################

if(isset($DB) && $DB->conn)
{
  $DB->close();
}
