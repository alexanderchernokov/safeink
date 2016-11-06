<?php
define('IN_PRGM', true);
define('ROOT_PATH', '../../');
unset($mainsettings);
require(ROOT_PATH . 'includes/init.php');

if( empty($mainsettings['twitter_consumer_key']) ||
    empty($mainsettings['twitter_consumer_secret']) )
{
  exit();
}
if(defined('SD_IS_BOT') && SD_IS_BOT) exit();

// We could receive Twitter call with authentication data:
if($gottoken = GetVar('oauth_token', false, 'string', false, true))
{
  $gottoken = (strlen($gottoken) >= 20);
}
if($gottokenv = GetVar('oauth_verifier', false, 'string', false, true))
{
  $gottokenv = (strlen($gottokenv) >= 20);
}

if((!$gottoken || !$gottokenv) && !CheckFormToken()) exit();

// Common security checks
$uri = isset($_SERVER['REQUEST_URI']) ? strip_tags($_SERVER['REQUEST_URI']) : '';
if(($uri && preg_match('#%00|page=(/proc/self/environ|php\://input)|base64_decode.*\(.*\)|base64_encode.*\(.*\)|(\<|%3C).*script.*(\>|%3E)|GLOBALS(=|\[|\%[0-9A-Z]{0,2})|_REQUEST(=|\[|\%[0-9A-Z]{0,2})|'.
                       'login\.php|userinfo%5B|user_arr%5B|custompluginfile\[|custompluginfile%5B|posting\.php|\.\./proc#', $uri))
   || (isset($_SERVER['QUERY_STRING']) && strpos(urldecode($_SERVER['QUERY_STRING']), chr(0)) !== false) )
{
  header("HTTP/1.0 403 Forbidden");
  echo 'HTTP/1.0 403 Forbidden';
  exit();
}

// IMPORTANT: DO NOT MAKE ANY OUTPUT BEFORE API PROCESSING AS THE OAUTH
// MAY REQUIRE TO SEND REDIRECTS HEADER FOR THE TWITTER API TO AUTHORIZE!!!

$isajax   = Is_Ajax_Request();
$docheck  = GetVar('check', 0, 'bool', false, true) ||
            ($gottoken && $gottokenv);
$pluginid = GetVar('p', 0, 'whole_number', false, true);
if($pluginid>1) $pluginid = Is_Valid_Number($pluginid,0,5000,6000);

// "check" param is for admin and does not allow ajax'ed call
// if not checking, then it must be an ajax call with valid pluginid and POST
if(($docheck && $isajax) || (!$docheck && (!$pluginid || !$isajax))) exit();
if($isajax && (empty($_POST) || !is_array($_POST))) exit();

$client_id     = sd_decodesetting($mainsettings['twitter_consumer_key']);
$client_secret = sd_decodesetting($mainsettings['twitter_consumer_secret']);

if($docheck)
{
  if(empty($userinfo['loggedin']) || empty($userinfo['adminaccess']))
  {
    header("HTTP/1.0 403 Forbidden");
    echo "HTTP/1.0 403 Forbidden<br /><br />You must be logged in as administrator.";
    exit();
  }
  if(empty($client_id) || empty($client_secret))
  {
    echo '<b>Twitter Authentication Check</b><br />
    <b>Error: you must enter your Twitter authentication data in the admin interface!</b>';
  }
}
else
{
  // For security reasons, we only allow Twitter API 1.1:
  if(!isset($_POST['request']['host']) || ($_POST['request']['host'] !== 'api.twitter.com')) exit();
  if(!isset($_POST['request']['url'])  || (substr($_POST['request']['url'],0,5) !== '/1.1/')) exit();

  $allowed_urls = array( # determined by jquery.tweet.js
    '/1.1/lists/statuses.json' => 0,
    '/1.1/favorites/list.json' => 1,
    '/1.1/statuses/user_timeline.json' => 2,
    '/1.1/search/tweets.json' => 3);
  if(!isset($allowed_urls[$_POST['request']['url']])) exit();

  // Check - if given - for valid username (A-Z, digits, underscore; max. length 15):
  if(isset($_POST['request']['parameters']) && isset($_POST['request']['parameters']['owner_screen_name']))
  {
    if(!isset($_POST['request']['parameters']['owner_screen_name'][0]) ||
       !preg_match('/[a-zA-Z0-9_]{1,15}/', $_POST['request']['parameters']['owner_screen_name'][0])) exit();
  }
  if(isset($_POST['request']['parameters']) && isset($_POST['request']['parameters']['screen_name'][0]))
  {
    if(!isset($_POST['request']['parameters']['screen_name'][0]) ||
       !preg_match('/[a-zA-Z0-9_]{1,15}/', $_POST['request']['parameters']['screen_name'][0])) exit();
  }
}
if(empty($client_id) || empty($client_secret)) exit();

if(!$docheck)
{
  // First try to load cached content via cache file (if enabled):
  if($cache_enabled = !empty($mainsettings['enable_caching']) &&
                      defined('SD_CACHE_PATH') && is_writable(SD_CACHE_PATH))
  {
    $SDCache->SetExpireTime(15);
    if($cached = $SDCache->read_var('twitter_feed'.$pluginid, 'feed'))
    {
      echo $cached;
      exit();
    }
  }
}

require(SD_CLASS_PATH.'http.php');
require(SD_CLASS_PATH.'oauth_client.php');
#require(SD_CLASS_PATH.'xml_writer_class.php');
#require(SD_CLASS_PATH.'rss_writer_class.php');
require(SD_CLASS_PATH.'sd_db_oauth_client.php'); #SD: DB layer for token storage
require(SD_CLASS_PATH.'twitter_feed.php');

$client = new twitter_feed_class;
$client->session_cookie = COOKIE_PREFIX.$client->session_cookie.(empty($dbname)?'':substr(md5($dbname),0,5));
$client->client_id      = $client_id;
$client->client_secret  = $client_secret;
$client->server         = 'Twitter';
$client->offline        = true; //MUST BE TRUE FOR SESSION MANAGEMENT!!!
$client->userid         = 0;
$client->debug          = ($docheck && !empty($userinfo['adminaccess']));
$client->debug_http     = $client->debug;
$client->prefer_curl    = !function_exists("extension_loaded") ||
                          !extension_loaded("openssl");
$client->redirect_uri   = $sdurl.'includes/classes/sd_twitter.php';
$client->exit_on_ajax   = true;

if($success = $client->Initialize())
{
  if($docheck)
  {
    if($success = $client->GetToken())
    if(!headers_sent())
    {
      echo '<b>Twitter Authentication Check OK</b><br />';
    }
  }
  else
  {
    /*
     * Statuses may be mentions_timeline, user_timeline, home_timeline,
     * retweets_of_me
     */
    $url        = $_POST['request']['url'];
    $parameters = $_POST['request']['parameters'];
    // we support only a single username for now
    if(isset($parameters['screen_name']) && is_array($parameters['screen_name']))
    {
      $parameters['screen_name'] = $parameters['screen_name'][0];
    }
    if(isset($parameters['owner_screen_name']) && is_array($parameters['owner_screen_name']))
    {
      $parameters['owner_screen_name'] = $parameters['owner_screen_name'][0];
    }
    $client->screen_name_prefix = false;

    // Purge old, unauthorized access tokens older than an hour
    $DB->query("DELETE FROM {oauth_session}".
               " WHERE authorized <> '1' AND creation < %d", TIME_NOW - 3600);

    /*
    --- IMPORTANT ---
    IF the cookie does not exist, check in DB first for an authenticated token
    for Twitter with userid = 0 and if so, use that one.
    Otherwise each visit will result in a new, unauthenticated token and
    therefore invalid session.
    */
    if(!isset($_COOKIE[$client->session_cookie]))
    {
      if($session = $DB->query_first("SELECT * FROM {oauth_session} WHERE server = 'Twitter'".
                                     " AND userid = 0 AND authorized = '1'".
                                     " ORDER BY creation DESC LIMIT 1"))
      {
        $_COOKIE[$client->session_cookie] = $session['session'];
        setcookie($client->session_cookie, $session['session'], TIME_NOW+(86400*7), $client->session_path);
      }
    }

    if($success = $client->GetToken())
    {
      if($success = $client->GetStatuses($url, $parameters))
      {
        if($cache_enabled)
        {
          ob_start();
          $client->Output();
          $output = ob_get_clean();
          $SDCache->write_var('twitter_feed'.$pluginid, 'feed', $output);
          echo $output;
        }
        else
        {
          $client->Output();
        }
      }
    }
    else
    if(!headers_sent())
    {
      // Clear cookie
      unset($_COOKIE[$client->session_cookie]);
      setcookie($client->session_cookie, '', 0, $client->session_path);
    }
  }
	$client->Finalize($success);
}

if($client->exit) exit;

/*
 * Use this script from the command line, so no HTML output is needed.
 */
if(!empty($client->debug))
{
  if($success)
  {
    if(strlen($client->access_token))
    {
      echo '<br />API succesful!';
    }
    else
      echo '<br />The access token is not available!', "\n";
  }
  else
  if(!empty($client->error) && !headers_sent())
  {
    #echo '<br />Error: ', $client->error, "\n";
  }

  if(!headers_sent())
  {
    #echo '<br />'.$client->Output();
    echo '<br />'.str_replace("\n", '<br />', $client->debug_output);
  }
}
$DB->close();
unset($client);
