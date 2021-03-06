<?php
if(!defined('IN_PRGM') /*|| !defined('SD_CORE')*/) return false;

require(SD_MODULES_PATH . 'bad-behavior.inc.php');

/*
Bad Behavior - detects and blocks unwanted Web accesses
Copyright (C) 2005,2006,2007,2008,2009,2010,2011 Michael Hampton

Bad Behavior is free software; you can redistribute it and/or modify it under
the terms of the GNU Lesser General Public License as published by the Free
Software Foundation; either version 3 of the License, or (at your option) any
later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License along
with this program. If not, see <http://www.gnu.org/licenses/>.

Please report any problems to bad . bots AT ioerror DOT us
http://www.bad-behavior.ioerror.us/
*/

################################################################################
/*
- SD globals used: SD_CLASS_PATH, $DB, $mainsettings
*/
################################################################################
################################################################################

$bb2_mtime = explode(' ', microtime());
$bb2_timer_start = $bb2_mtime[1] + $bb2_mtime[0];

define('BB2_CWD', SD_CLASS_PATH);

// Bad Behavior callback functions.

// Return current time in the format preferred by your database.
function bb2_db_date()
{
	return gmdate('Y-m-d H:i:s');
}

// Return affected rows from most recent query.
function bb2_db_affected_rows()
{
	global $DB;

	return $DB->affected_rows();
}

// Escape a string for database usage
function bb2_db_escape($string)
{
	global $DB;
	return $DB->escape_string($string);
}

// Return the number of rows in a particular query.
function bb2_db_num_rows($result)
{
  global $DB;
	if ($result !== FALSE)
  {
		return $DB->get_num_rows();
  }
	return 0;
}

// Run a query and return the results, if any.
// Should return FALSE if an error occurred.
// Bad Behavior will use the return value here in other callbacks.
function bb2_db_query($query)
{
	global $DB;
  if(empty($query) || ($query=='--')) return true;
	$DB->ignore_error = true;
	$result = $DB->query($query);
	$DB->ignore_error = false;
	if($DB->errno) return false;
	return $result;
}

/*
function bb2_banned_callback($settings, $package, $key)
{
  if(!function_exists('Watchdog')) return;
  global $DB;
  Watchdog('bb2', 'Access blocked: '.htmlentities($package['request_uri']),WATCHDOG_WARNING);
}
*/

// Return all rows in a particular query.
// Should contain an array of all rows generated by calling mysql_fetch_assoc()
// or equivalent and appending the result of each call to an array.
function bb2_db_rows($result) {
	return $result; // TODO
}

// Create the SQL query for inserting a record in the database.
// See example for MySQL elsewhere.
function bb2_insert($settings, $package, $key)
{
  global $DB, $userinfo, $mainsettings;
  if(empty($settings['logging']) || !empty($userinfo['adminaccess'])) return '--';
  if((trim($key) == '00000000') && (!empty($userinfo['userid']) || (defined('SD_IS_BOT') && SD_IS_BOT))) return '--';

  $ip = $DB->escape_string($package['ip']);
  $date = bb2_db_date();
  $request_method = $DB->escape_string($package['request_method']);
  $request_uri = $DB->escape_string($package['request_uri']);
  if($request_uri=='/includes/ajax/getuserselection.php') return '--';
  if(strpos($request_uri,'/includes/classes/sd_twitter.php')!==false) return '--'; #2013-10-07
  $server_protocol = $DB->escape_string($package['server_protocol']);
  $user_agent = $DB->escape_string($package['user_agent']);
  $headers = "$request_method $request_uri $server_protocol\n";
  foreach ($package['headers'] as $h => $v) {
    $headers .= $DB->escape_string("$h: $v\n");
  }
  $request_entity = "";
  if (!strcasecmp($request_method, "POST"))
  {
    foreach ($package['request_entity'] as $h => $v) {
      if(($h=='loginpassword')||($h=='p12_passwordconfirm')||($h=='p12_password')||($h=='p12_verifycode'))
      {
        $v = '***';
      }
      $request_entity .= $DB->escape_string("$h: $v\n");
    }
  }
  //SD: do not log everything, even with verbose mode
  //if(empty($settings['verbose']) && (trim($key) == '00000000'))
  if(empty($key) || (trim($key) == '00000000'))
  {
    if(!empty($_POST['comment_action']) && ($_POST['comment_action']=='insert_comment')) //SD343 do not log comments
    {
      return '--';
    }
    if(($request_method=='GET')||($request_method=='HEAD'))
    if(preg_match('/[\.html?\?page=\d|\.gif|\.png|\/|\?newposts(=1)?|\?member=\d|\/rss\.php|\/css\.php]$/i', $request_uri))
    return '--';
  }

  if(!empty($userinfo['userid']) && !empty($userinfo['loggedin']))
  {
    $request_entity .= (empty($request_entity)?'':"\r\n").
                       'User: '.$DB->escape_string($userinfo['username']) .' ('.$userinfo['userid'].')';
  }

  //SD344: store additional codes when Project Honeypot is enabled
  //SD360: skip logging if from inside css.php - commented out!
  #if(!defined('IN_CSS') && !IN_CSS)
  {
    //SD362: kill wrongful connect attempt
    $killConnect = false;
    if(!empty($request_uri) && (substr($request_uri,-3)==':25') &&
       !empty($request_method) && (strtoupper($request_method)=='CONNECT'))
    {
      $killConnect = true;
      $request_entity = 'Aborted!';
    }
    if(version_compare($mainsettings['sdversion'],'3.4.4','ge'))
    {
      $httpbl_code = (empty($settings['httpbl_key']) || empty($package['httpbl_code']) ? 0 : (int)$package['httpbl_code']);
      $httpbl_level = (empty($settings['httpbl_key']) || empty($package['httpbl_level']) ? 0 : (int)$package['httpbl_level']);
      return "INSERT INTO `" . $DB->escape_string($settings['log_table']) . "`
        (`ip`, `date`, `request_method`, `request_uri`, `server_protocol`, `http_headers`,
         `user_agent`, `request_entity`, `key`, `httpbl_code`, `httpbl_level`) VALUES
        ('$ip', '$date', '$request_method', '$request_uri', '$server_protocol', '$headers',
         '$user_agent', '$request_entity', '$key', '$httpbl_code', '$httpbl_level')";
    }
    else
    {
      return "INSERT INTO `" . $DB->escape_string($settings['log_table']) . "`
        (`ip`, `date`, `request_method`, `request_uri`, `server_protocol`, `http_headers`,
         `user_agent`, `request_entity`, `key`) VALUES
        ('$ip', '$date', '$request_method', '$request_uri', '$server_protocol', '$headers',
         '$user_agent', '$request_entity', '$key')";
    }
    //SD362: kill wrongful connect attempt
    if($killConnect)
    {
      die();
    }
  }
}

// Return emergency contact email address.
function bb2_email() {
	return defined('TECHNICAL_EMAIL') ? TECHNICAL_EMAIL : '';
}

// retrieve settings from database
function bb2_read_settings()
{
  global $sd_modules; // SD core
  //SD344: added eu_cookie (BB v2.2.7)
  $test = $sd_modules->GetSettings(MODULE_BAD_BEHAVIOR);
  if(empty($test))
    return array(
    'log_table' => PRGM_TABLE_PREFIX.'bad_behavior',
    'display_stats' => false,
    'httpbl_key' => '',
    'logging' => false,
    'offsite_forms' => false,
    'reverse_proxy' => false,
    'strict'  => false,
    'verbose' => false,
    'httpbl_whitelisted_groups' => array(),
    'httpbl_threat' => 25,
    'eu_cookie' => false);

  global $agent_is_mobile, $userinfo;
  if(!empty($userinfo['adminaccess'])) $test['httpbl_key'] = '';
  $test['httpbl_maxage'] = isset($test['httpbl_maxage'])?Is_Valid_Number($test['httpbl_maxage'],30,10,900):30;
  $test['httpbl_threat'] = isset($test['httpbl_threat_level'])?Is_Valid_Number($test['httpbl_threat_level'],25,10,80):25;
  $test['httpbl_threat'] = empty($agent_is_mobile) ? $test['httpbl_threat'] : 40;
  $test['offsite_forms'] = !empty($test['offsite_forms']);
  $test['reverse_proxy'] = !empty($test['reverse_proxy']);
  $test['reverse_proxy_addresses'] = (empty($test['reverse_proxy_addresses'])?array():preg_split("/[\s,]+/", $test['reverse_proxy_addresses']));
  $test['httpbl_whitelisted_groups'] = !empty($test['httpbl_whitelisted_groups']) ? explode(',',$test['httpbl_whitelisted_groups']) : array();
  $test['eu_cookie'] = !empty($test['eu_cookie']); //SD344
  return $test;
}

// write settings to database
function bb2_write_settings($settings)
{
  global $sd_modules; // SD core
  if(isset($sd_modules) && is_object($sd_modules))
  {
    $sd_modules->SetSettings(MODULE_BAD_BEHAVIOR, $settings);
  }
}

// installation
function bb2_install()
{
  $settings = bb2_read_settings();
  //bb2_db_query(bb2_table_structure($settings['log_table']));
}

// Screener
// Insert this into the <head> section of your HTML through a template call
// or whatever is appropriate. This is optional we'll fall back to cookies
// if you don't use it.
function bb2_insert_head() {
	global $bb2_javascript;
	return $bb2_javascript;
}

// Display stats? This is optional.
function bb2_insert_stats($force = false)
{
  global $DB;

	$settings = bb2_read_settings();
	if ($force || !empty($settings['display_stats']))
  {
		$blocked = $DB->query_first('SELECT COUNT(*) logcount FROM ' . $settings['log_table'] . " WHERE `key` NOT LIKE '00000000'");
		if(!empty($blocked['logcount']))
    {
			echo sprintf('<p><a href="http://www.bad-behavior.ioerror.us/">%1$s</a> %2$s <strong>%3$s</strong> %4$s</p>', 'Bad Behavior', 'has blocked', $blocked[0]["COUNT(*)"], 'access attempts in the last 7 days.');
		}
	}
}

// Return the top-level relative path of wherever we are (for cookies)
// You should provide in $url the top-level URL for your site.
function bb2_relative_path()
{
  global $sdurl;
  return $sdurl;
}

// Calls inward to Bad Behavor itself.
//require_once(BB2_CWD . '/bad-behavior/version.inc.php');
require_once(BB2_CWD . '/bad-behavior/core.inc.php');
//bb2_install();

// Check if BB2 is actually enabled within SD:
if(!defined('IN_ADMIN') && isset($sd_modules) && !empty($sd_modules->Modules[MODULE_BAD_BEHAVIOR]['enabled']))
{
  bb2_start(bb2_read_settings());
}

/*
$bb2_mtime = explode(' ', microtime());
$bb2_timer_stop = $bb2_mtime[1] + $bb2_mtime[0];
$bb2_timer_total = $bb2_timer_stop - $bb2_timer_start;
*/
