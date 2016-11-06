<?php
// SD313: added functions_security.php as new include file
if(!defined('IN_PRGM')) exit();

if(!defined('SD_TOKEN_NAME'))
{
  defined('SD_TOKEN_NAME', 'form_token');
}

// ############################################################################
// strip_alltags
// ############################################################################

/**
 * Remove HTML tags, including invisible text such as style and
 * script code, and embedded objects.  Add line breaks around
 * block-level tags to prevent word joining after tag removal.
 */
 function strip_alltags($text)
{
  // Only run this for UTF-8 character set!
  if(!preg_match('#utf-8#is',SD_CHARSET) && function_exists('iconv'))
  {
    $text = @iconv(SD_CHARSET, 'UTF-8//IGNORE', $text);
  }
  // Source: http://nadeausoftware.com/articles/2007/09/php_tip_how_strip_html_tags_web_page
  $text = preg_replace(
    array(
      // Remove invisible content
      '@<head[^>]*?>.*?</head>@siu',
      '@<style[^>]*?>.*?</style>@siu',
      '@<script[^>]*?.*?</script>@siu',
      '@<object[^>]*?.*?</object>@siu',
      '@<embed[^>]*?.*?</embed>@siu',
      '@<applet[^>]*?.*?</applet>@siu',
      '@<noframes[^>]*?.*?</noframes>@siu',
      '@<noscript[^>]*?.*?</noscript>@siu',
      '@<noembed[^>]*?.*?</noembed>@siu',
      // Add line breaks before & after blocks
      '@<((br)|(hr))@iu',
      '@</?((address)|(blockquote)|(center)|(del))@iu',
      '@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
      '@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
      '@</?((table)|(th)|(td)|(caption))@iu',
      '@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
      '@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
      '@</?((frameset)|(frame)|(iframe))@iu',),
    array(
      ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
      "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
      "\n\$0", "\n\$0",),
    $text
  );

  // Remove all remaining tags and comments and return.
  $text = strip_tags($text);
  return $text;

} //strip_alltags


// ############################################################################
// SanitizeInputForSQLSearch
// ############################################################################

function SanitizeInputForSQLSearch($input, $AllowHTML = false, $removeBlanks = true, $Detection = true)
{
  // ***** DO NOT USE THIS FOR POSTING CONTENTS TO THE DB!
  // ***** This is intended for SQL "LIKE" searches to restrict
  // ***** the input!
  // "$input" must be processed with "unhtmlspecialchars" before calling this!

  // This function detects possible SQL injection and REMOVES
  // (almost) all unwanted characters and HTML tags.
  // The returned value can then be used for e.g. SQL search
  // operations with LIKE operator.

  if(!isset($input) || !strlen($input))
  {
    return '';
  }

  // In case of a potential SQL injection just return empty string
  if(!empty($Detection) && (DetectSQLInjection($input) || DetectXSSinjection($input)))
  {
    $input = '';
  }
  else
  {
    // Replace multiple blanks with single blanks
    if(!empty($removeBlanks))
    {
      $input = preg_replace('/\s\s+/m', ' ', $input);
    }
    // Remove CR, LF, tabs and several other special chars
    $input = preg_replace("/%0A|\\r|%0D|\\n|%00|\\0|%09|\\t|%01|%02|%03|%04|%05|%06|%07|%08|%09|%0B|%0C|%0E|%0F|%10|%11|%12|%13/im", '', $input);
    if(!$AllowHTML)
    {
      $input = strip_alltags($input);
    }
    $search = array('"',"'",'\\','%','0x'/*,'?','_'*/); // unallowed special chars
    $replace = array('','','','',''/*,'\?','\_'*/); // escape ? and _ chars (SQL wildcards)
    $input = str_replace($search, $replace, $input); // this replace comes last!
    $input = CleanVar($input); // convert some tags, do htmlspecialchars
  }

  return $input;

} //SanitizeInputForSQLSearch


function DetectSQLInjection($scan_value)
{ // SD313: Developer note: this function requires unaltered input!
  // If input comes from POST/GET variables, program/plugin
  // MUST have called unhtmlspecialchars() on $scan_value BEFORE this!!
  // From: http://www.erich-kachel.de/seq_lib

  /* Scan for SQL-attack pattern
     http://niiconsulting.com/innovation/snortsignatures.html
  */
  if (preg_match("/(\%27)|(\')|(\')|(%2D%2D)|(\/\*)/i", $scan_value) ||
      preg_match("/\w*(\%27)|'(\s|\+)*((\%6F)|o|(\%4F))((\%72)|r|(\%52))/i", $scan_value) ||
      preg_match("/((\%27)|')(\s|\+)*union/i", $scan_value))
  {
    return true;
  }

  return false;

} //DetectSQLInjection


function DetectXSSinjection($input)
{
  // SD313: detect XSS injection (see http://niiconsulting.com/innovation/snortsignatures.html)
  if(!empty($input) &&
     preg_match("/((\%3C)|<)((\%2F)|\/)*[a-z0-9\%]+((\%3E)|>)/i", $input) ||
     preg_match("/((\%3C)|<)((\%69)|i|(\%49))((\%6D)|m|(\%4D))((\%67)|g|(\%47))[^\n]+((\%3E)|>)/i", $input))
  {
    return true;
  }

  return false;

} //DetectXSSinjection


function CheckMailHeader($header)
{
  if(isset($header))
  {
    return preg_match("/(%0A|%0D|\\n+|\\r+)(Content-Transfer-Encoding:|MIME-Version:|content-type:|Subject:|to:|cc:|bcc:|from:|reply-to:)/ims", $header) == 0;
  }
  return true;

} //CheckMailHeader


function UserinputSecCheck($input) //SD343
{
  $input_ok = true;
  if(!empty($input))
  {
    if(DetectXSSinjection($input))
    {
      $input_ok = false;
    }
    else
    {
      // Check if special chars are included (except linebreaks and tabs: \n, \r, \t)
      if($input != preg_replace("/%00|\\0|%01|%02|%03|%04|%05|%06|%07|%08|%09|%0B|%0C|%0E|%0F|%10|%11|%12|%13/im", '', $input))
      {
        $input_ok = false;
      }
    }
  }
  return $input_ok;

} //UserinputSecCheck


function DetectGlobalsOverwrite($input = '')
{
  // Call this function with an URL to detect hacking attempt
  static $s_globalvars = array('_SERVER',
                        'HTTP_SERVER_VARS',
                        '_ENV',
                        'HTTP_ENV_VARS',
                        '_COOKIE',
                        'HTTP_COOKIE_VARS',
                        '_GET',
                        'HTTP_GET_VARS',
                        '_POST',
                        'HTTP_POST_VARS',
                        '_FILES',
                        'HTTP_POST_FILES',
                        '_REQUEST',
                        '_SESSION',
                        'HTTP_SESSION_VARS',
                        'GLOBALS');

  /*
  Detect security vulnerability exploit
  http://www.securityfocus.com/archive/1/462263/30/0/threaded
  */
  return (preg_match("/^(" . implode("|", $s_globalvars) . ")/", $input, $matches));

} //DetectGlobalsOverwrite


function CleanVar($input)
{
  // Replace some special characters in input
  if(!isset($input) || !strlen($input)) return $input;

  $input = str_replace( "&#032;"      , " "            , $input);
  $input = str_replace( chr(0xCA)     , ""             , $input);  //Remove sneaky spaces
  $input = str_replace( "<!--"        , "&#60;&#33;--" , $input);
  $input = str_replace( "-->"         , "--&#62;"      , $input);
  $input = preg_replace( "/<script/i" , "&#60;script"  , $input);
  $input = str_replace( ">"           , "&gt;"         , $input);
  $input = str_replace( "<"           , "&lt;"         , $input);
  $input = str_replace( "\""          , "&quot;"       , $input);
  $input = preg_replace( "/\n/"       , "<br />"       , $input); // Convert literal newlines
  $input = preg_replace( "/\r/"       , ""             , $input); // Remove literal carriage returns
  $input = preg_replace( "/\\\$/"     , "&#036;"       , $input);
  //$input = str_replace( "!"           , "&#33;"        , $input); //SD322 commented out

  return $input;

} //CleanVar


function sd_htmlawed($content,$config=array(),$spec=array()) // SD342 - wrapper for htmLawed
{
  include_once(SD_INCLUDE_PATH.'htmLawed.php');
  if(!function_exists('htmLawed')) return strip_tags($content);

  static $default_config;
  static $all_elements_allowed = Array(
        'a' => 1,
        'abbr' => 1,
        'acronym' => 1,
        'address' => 1,
        'area' => 1,
        'b' => 1,
        'bdo' => 1,
        'big' => 1,
        'blockquote' => 1,
        'br' => 1,
        'button' => 1,
        'caption' => 1,
        'cite' => 1,
        'code' => 1,
        'col' => 1,
        'colgroup' => 1,
        'dd' => 1,
        'del' => 1,
        'dfn' => 1,
        'div' => 1,
        'dl' => 1,
        'dt' => 1,
        'em' => 1,
        'fieldset' => 1,
        'form' => 1,
        'h1' => 1,
        'h2' => 1,
        'h3' => 1,
        'h4' => 1,
        'h5' => 1,
        'h6' => 1,
        'hr' => 1,
        'i' => 1,
        'img' => 1,
        'input' => 1,
        'ins' => 1,
        'kbd' => 1,
        'label' => 1,
        'legend' => 1,
        'li' => 1,
        'map' => 1,
        'noscript' => 1,
        'ol' => 1,
        'optgroup' => 1,
        'option' => 1,
        'p' => 1,
        'param' => 1,
        'pre' => 1,
        'q' => 1,
        'rb' => 1,
        'rbc' => 1,
        'rp' => 1,
        'rt' => 1,
        'rtc' => 1,
        'ruby' => 1,
        'samp' => 1,
        'select' => 1,
        'small' => 1,
        'span' => 1,
        'strong' => 1,
        'sub' => 1,
        'sup' => 1,
        'table' => 1,
        'tbody' => 1,
        'td' => 1,
        'textarea' => 1,
        'tfoot' => 1,
        'th' => 1,
        'thead' => 1,
        'tr' => 1,
        'tt' => 1,
        'ul' => 1,
        'var' => 1,
      );

  static $all_elements_unallowed = Array(
        'a' => 0,
        'abbr' => 0,
        'acronym' => 0,
        'address' => 0,
        'area' => 0,
        'b' => 0,
        'bdo' => 0,
        'big' => 0,
        'blockquote' => 0,
        'br' => 0,
        'button' => 0,
        'caption' => 0,
        'cite' => 0,
        'code' => 0,
        'col' => 0,
        'colgroup' => 0,
        'dd' => 0,
        'del' => 0,
        'dfn' => 0,
        'div' => 0,
        'dl' => 0,
        'dt' => 0,
        'em' => 0,
        'fieldset' => 0,
        'form' => 0,
        'h0' => 0,
        'h2' => 0,
        'h3' => 0,
        'h4' => 0,
        'h5' => 0,
        'h6' => 0,
        'hr' => 0,
        'i' => 0,
        'iframe' => 0,
        'img' => 0,
        'input' => 0,
        'ins' => 0,
        'kbd' => 0,
        'label' => 0,
        'legend' => 0,
        'li' => 0,
        'map' => 0,
        'noscript' => 0,
        'ol' => 0,
        'optgroup' => 0,
        'option' => 0,
        'p' => 0,
        'param' => 0,
        'pre' => 0,
        'q' => 0,
        'rb' => 0,
        'rbc' => 0,
        'rp' => 0,
        'rt' => 0,
        'rtc' => 0,
        'ruby' => 0,
        'samp' => 0,
        'select' => 0,
        'small' => 0,
        'span' => 0,
        'strong' => 0,
        'sub' => 0,
        'sup' => 0,
        'table' => 0,
        'tbody' => 0,
        'td' => 0,
        'textarea' => 0,
        'tfoot' => 0,
        'th' => 0,
        'thead' => 0,
        'tr' => 0,
        'tt' => 0,
        'ul' => 0,
        'var' => 0,
      );

  if(!isset($default_config))
  $default_config = Array
  (
    'abs_url' => 0,
    'and_mark' => 0,
    'anti_link_spam' => 1,
    'anti_mail_spam' => 'NO@SPAM',
    'anti_mail_spam1' => 'NO@SPAM',
    'balance' => 1,
    'base_url' => 0,
    'cdata' => 1,
    'clean_ms_char' => 1,
    'comment' => 1,
    'css_expression' => 0,
    'deny_attribute' => 'on*',
    'direct_list_nest' => 0,
    'elements' => '* -a -b -br -div -h0 -h1 -h2 -h3 -h4 -h5 -h6 -hr -i -img -script -strong -style',
    'hexdec_entity' => 1,
    'hook' => 0,
    'hook_tag' => 0,
    'keep_bad' => 0,
    'lc_std_val' => 1,
    'named_entity' => 1,
    'no_deprecated_attr' => 0,
    'parent' => '',
    'safe' => 1,
    'schemes' => Array(
        'href' => Array('http' => 1),
        '*' => Array(
            'file' => 0,
            'http' => 1,
            'https' => 0,),
        'style' => Array('!' => 0)),
    'style_pass' => 1,
    'tidy' => 0,
    'unique_ids' => 1,
    'valid_xhtml' => 1,
    'show_setting' => 0,
    'make_tag_strict' => 2,
    'xml:lang' => 2,
    'and_mark' => 0
  );

  return htmLawed($content, (!empty($config)?$config:$default_config), $spec);

} //sd_htmlawed


// ############################################################################
// GenerateSecureToken
// ############################################################################

// SD313: returns a "hard-to-guess" MD5 of DB server, username and -id which
// can be used as an hidden input value for forms as extra security.
// This is used by default in "init.php" to define the global SD_FORM_TOKEN.
function GenerateSecureToken()
{
  global $DB, $userinfo, $mainsettings;

  $result = '';
  if(empty($userinfo['loggedin']))
  {
    $result = md5($DB->server . realpath(ROOT_PATH) . (defined('USER_AGENT')?USER_AGENT:(defined('USERIP')?USERIP:$DB->server)));
  }
  else
  {
    //SD322: for logged in user use the new securitytoken
    if(isset($userinfo['securitytoken_raw']) && (strlen($userinfo['securitytoken_raw'])>5))
    {
      $result = $userinfo['securitytoken_raw'];
    }
    else
    {
      $result = md5($DB->server . $userinfo['username'] . $userinfo['userid']);
    }
  }
  return $result;

} //GenerateSecureToken


// ############################################################################
// CheckFormToken
// ############################################################################

// SD313: returns only true, if the $_GET/$_POST buffer has a valid token value in
// the specified $token_name variable, which must be the same as SD_FORUM_TOKEN
// (generated in init.php!)
function CheckFormToken($token_name = SD_TOKEN_NAME, $displayError = false)
{
  global $sdlanguage, $userinfo;
    
  if(empty($userinfo['loggedin']) || !defined('SD_FORM_TOKEN'))
  {
    $userinfo['securitytoken_raw'] = GenerateSecureToken();
    $sd_token = TIME_NOW . '-' . sha1(TIME_NOW . $userinfo['securitytoken_raw']);
  }
  else
    $sd_token = SD_FORM_TOKEN;

  $token_name = !isset($token_name) || !strlen(trim($token_name)) ? SD_TOKEN_NAME : trim($token_name);
  $form_token = GetVar($token_name, '', 'string');
  if( (strlen($form_token)==51) && (strlen($sd_token)==51) && is_numeric(substr($form_token,0,10)) &&
      (substr($form_token,10,1) == '-') )
  {
    $time_code  = substr($form_token,0,10);
    $token_code = substr($form_token,11,40);

    // The provided token code must be the same for the current user's raw token
    if($token_code != sha1($time_code . (string)$userinfo['securitytoken_raw']))
    {
      if($displayError)
      {
        DisplayMessage($sdlanguage['error_invalid_token'], true);
      }
      return false;
    }

    //Check if security token has expired (no longer valid, too)
    if(empty($userinfo['adminaccess']) && TIME_NOW - (int)$time_code > 3600)
    {
      if($displayError)
      {
        DisplayMessage($sdlanguage['error_token_expired'], true);
      }
      return false;
    }
  }
  else
  if($form_token != $sd_token)
  {
    if($displayError)
    {
	  DisplayMessage($sdlanguage['error_invalid_token'], true);
    }
    return false;
  }

  return true;

} //CheckFormToken


// ############################################################################
// PrintSecureToken
// ############################################################################

// SD313: Returns a hidden input field with a form token value.
// This should be used for forms as extra security against spam/bots submissions.
function PrintSecureToken($token_name = SD_TOKEN_NAME)
{
  global $DB;

  $token_name = !isset($token_name) || !strlen(trim($token_name)) ? SD_TOKEN_NAME : trim($token_name);
  $token = defined('SD_FORM_TOKEN') ? SD_FORM_TOKEN : GenerateSecureToken();

  return '  <input type="hidden" name="' . $token_name . '" value="' . $token . '" />';

} //PrintSecureToken


// ############################################################################
// PrintSecureUrlToken
// ############################################################################

// SD313: Returns a URL-parameter with a form token value.
// This should be used for URL's requiring extra security
// (e.g. links to insert/update/delete content/items etc.).
function PrintSecureUrlToken($token_name = SD_TOKEN_NAME)
{
  global $DB;

  $token_name = !isset($token_name) || !strlen($token_name ) ? SD_TOKEN_NAME : trim($token_name);
  $token = defined('SD_FORM_TOKEN') ? SD_FORM_TOKEN : GenerateSecureToken();

  return '&amp;' . $token_name . '='. $token;

} //PrintSecureUrlToken


// ############################################################################
// ImageSecurityCheck
// ############################################################################
// $imagename must be a valid path+filename of the image that needs checking.
// Function will read first 1KB of the image and try to detect invalid (HTML)
// tags in it that could corrupt the site or be dangerous to visitors etc.
function ImageSecurityCheck($imagename)
{
  //SD362: legacy (e.g. p17); now uses SD_Media_Base
  require_once(SD_INCLUDE_PATH.'class_sd_media.php');
  return SD_Media_Base::ImageSecurityCheck(true,dirname($imagename).'/',basename($imagename));
} //ImageSecurityCheck
