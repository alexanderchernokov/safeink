<?php
// ########## IMPORTANT: THIS FILE MUST BE SAVED IN UTF-8 FORMAT!!! ##########

if(!defined('IN_PRGM')) return;

/**
 * Legacy function (empty)
 *
 * @param string $phrase
 * @return string $phrase
 */
function AddSmilies($phrase)
{ // only for legacy plugin code!
  return $phrase;
}

/**
 * Outputs JS code to redirect to $new_page after $delay_in_seconds seconds
 *
 * @param int $delay_in_seconds
 * @param string $new_page
 * @return null
 */
function AddTimeoutJS($delay_in_seconds, $new_page)
{
  echo '
<script type="text/javascript">
//<![CDATA[
var sd_timeout = ' . (int)($delay_in_seconds * 1000) . ';
var sd_timerID = false;
function sd_Refresh() {
  clearTimeout(sd_timerID);
  window.location="' . str_replace('&amp;', '&', $new_page) . '";
}
if(typeof(jQuery) !== "undefined"){
jQuery(document).ready(function(){
  (function($){
    sd_timerID = setTimeout("sd_Refresh();", sd_timeout);
  })(jQuery);
});
}
//]]>
</script>';

} //AddTimeoutJS








// ############################################################################
// AGO
// ############################################################################
// thanks to andrew macrobert's: php.net/manual/en/function.time.php

// SD 313: till SD312 "Ago" never displayed the actual date (even for "years").
// With the added parameter $datelimit a number of days can be provided
// which is the highest number of days after which the actual date shall be
// displayed. Default: Null (standard behavior)
//
// All "Ago" phrases are now translatable in the Admin "Languages".
//
// Optionally there are 2 text parameters (could also be empty string) which
// would be displayed before and after the timestamp in case the "ago" format
// were used.
// If these are NOT specified, the default language phrases would be used
// (which were installed for English with upgrade to SD313)!
/**
 * Outputs in human readable format what time has passed since $timestamp with optional date limit
 *
 * @param int $timestamp Un*x-Timestamp which must be in the past
 * @param int $datedisplaylimit Number of days after which the normal date is displayed
 * @param string $dateformat Date format for display if $datedisplaylimit is reached
 * @param string $text_before Text output in front of display
 * @param string $text_after Text output after display
 * @return string
 */
function Ago($timestamp, $datedisplaylimit=null, $dateformat=null, $text_before=null, $text_after=null)
{
  global $userinfo, $sdlanguage;

  if( ( is_string($timestamp) && !is_numeric($timestamp)) ||
      (!is_string($timestamp) && !is_int($timestamp)) )
  {
    return '';
  }

  $difference = abs(TIME_NOW - $timestamp);
  // SD 313: if topic is older than "$datedisplaylimit" days, then
  // display the date in with given format "$dateformat"
  if(isset($datedisplaylimit) && is_numeric($datedisplaylimit) && ($difference > (int)$datedisplaylimit*24*3600))
  {
    return DisplayDate($timestamp, $dateformat, true);
  }

  return sd_TimeDifference($timestamp);

  // SD313: "ago" is now fully translatable!
  //$periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
  $period_single =
    array($sdlanguage['ago_one_second'],
          $sdlanguage['ago_one_minute'],
          $sdlanguage['ago_one_hour'],
          $sdlanguage['ago_one_day'],
          $sdlanguage['ago_one_week'],
          $sdlanguage['ago_one_month'],
          $sdlanguage['ago_one_year'],
          $sdlanguage['ago_one_decade']);

  $periods_multiple =
    array($sdlanguage['ago_seconds'],
          $sdlanguage['ago_minutes'],
          $sdlanguage['ago_hours'],
          $sdlanguage['ago_days'],
          $sdlanguage['ago_weeks'],
          $sdlanguage['ago_months'],
          $sdlanguage['ago_years'],
          $sdlanguage['ago_decades']);

  $lengths = array(60,60,24,7,4.35,12,10);
  for($j = 0; $difference >= $lengths[$j]; $j++)
  {
    $difference /= $lengths[$j];
  }
  $org_difference = $difference;
  $difference = round($difference);
  //SD370: 23h50m said 24h, should be 1 day; Test: $difference = 84900;
  if(($org_difference != $difference) &&
     ($j < count($lengths)) && ($difference == $lengths[$j]))
  {
    $difference = 1;
    $j++;
  }
  if($difference == 1)
  {
    $period = $period_single[$j];
  }
  else
  {
    $period = $periods_multiple[$j];
  }

  // SD313: replaced hard-coded word "ago" with before/after phrases
  // because the "ago" word is placed differently in non-english languages!!!
  if(!isset($text_before)) $text_before = $sdlanguage['ago_before']; // e.g. "Posted"
  if(!isset($text_after))  $text_after  = $sdlanguage['ago_after']; // e.g. "ago"
  $text = "$text_before $difference $period $text_after";

  return $text;

} //Ago


function sd_TimeDifference($until, $use_brackets = false)
{
  global $sdlanguage;

  $difference = abs(TIME_NOW - intval($until));

  $years = $months = $weeks = $days = $hours = $minutes = 0;
  if($difference > 31449599)
  {
    $years = floor($difference/31449600);
    $difference = $difference - ($years*31449600);
  }

  if($difference > 2591999)
  {
    $months = floor($difference/2592000);
    $difference = $difference - ($months*2592000);
  }

  /*
  if($difference > 604799)
  {
    $weeks = floor($difference/604800);
    $difference = $difference - ($weeks*604800);
  }
  */

  if($difference > 86399)
  {
    $days = floor($difference/86400);
    $difference = $difference - ($days*86400);
  }

  if($difference > 3599)
  {
    $hours = floor($difference/3600);
    $difference = $difference - ($hours*3600);
  }

  if($difference > 59)
  {
    $minutes = floor($difference/60);
    $difference = $difference - ($minutes*60);
  }

  $seconds = $difference;
  $result = array();
  if(!empty($years)) { $result[] =  $years . ' ' . $sdlanguage[$years  == 1 ? 'ago_one_year' : 'ago_years']; }
  if(!empty($months)){ $result[] =  $months. ' ' . $sdlanguage[$months == 1 ? 'ago_one_month': 'ago_months'];  }
  #if($weeks > 1)
    if(!empty($weeks))  { $result[] =  $weeks . ' ' . $sdlanguage[$weeks == 1 ? 'ago_one_week'  : 'ago_weeks'];  }
  #else
    if(!empty($days))  { $result[] =  $days  . ' ' . $sdlanguage[$days   == 1 ? 'ago_one_day'  : 'ago_days'];  }
  if(!$years && !$months && ($days <= 7))
  {
    if(!empty($hours) || !empty($minutes))
    {
      if(!empty($hours))
      $result[] = $hours . ' ' . $sdlanguage[$hours  == 1 ? 'ago_one_hour' : 'ago_hours'];
      if($minutes && !$days && (!$hours || ($hours <= 12)))
      $result[] = $minutes . ' ' . $sdlanguage[$years == 1 ? 'ago_one_minute' : 'ago_minutes'];
    }
    else
    {
      $result[] = $seconds. ' ' . $sdlanguage[$seconds == 1 ? 'ago_one_second' : 'ago_seconds'];
    }
  }
  return ($use_brackets ? '(' : '') .  $sdlanguage['ago_before'] . ' ' .
         implode(', ', $result) . ' ' . $sdlanguage['ago_after'] . ($use_brackets ? ')' : '');

} //sd_TimeDifference

// ############################################################################
// START SECTION
// ############################################################################

/**
 * Returns or outputs a headlined (h1) section with fixed classnames and optional styles
 *
 * @param string $section_name
 * @param string $table_wrap_style defaults to empty string
 * @param string $output Output or return the result (defaults to true)
 * @return string or null
 */
function StartSection($section_name, $table_wrap_style='', $output = true, $headeronly = false)
{
	if($headeronly)
	{
		$r = '<h3 class="header blue lighter">' . $section_name . '</h3>';
	}
	else
	{
	
	$r = '
		<!-- Start Table -->
		<div class="table-responsive">
			<div class="table-header"> ' . $section_name . '</div>';
	}
 /*
  $r = '
    <!-- start section -->'.
    (empty($section_name)?'':'<h1'.($table_wrap_style?' style="'.$table_wrap_style.'"':'').'>' . $section_name . '</h1>').'
    <div class="table_wrap"'.($table_wrap_style?' style="'.$table_wrap_style.'"':'').'>
    <div class="form_wrap"'.($table_wrap_style?' style="'.$table_wrap_style.'"':'').'>
    ';
*/
  if(empty($output)) return $r;
  echo $r;
}

/**
 * Returns or outputs a headlined (h2) section with fixed classnames and optional styles
 *
 * @param string $section_name
 * @param string $output Output or return the result (defaults to true)
 * @return string or null
 */
function StartSectionSmall($section_name, $output = true)
{
  $r = (empty($section_name)?'':'<h3 class="box-header">' . $section_name . '</h3>');
 
  if(empty($output)) 
  {
	  return $r;
  }
  echo $r;
}


// ############################################################################
// END SECTION
// ############################################################################

/**
 * Closes a headlined section created by StartSection/Small
 *
 * @param string $output Output or return the result (defaults to true)
 * @return string or null
 */
function EndSection($output = true)
{
	
	$r = '
    </div> <!-- table responsive -->
    ';
  /*
  $r = '
    </div> <!-- form_wrap -->
    </div> <!-- table_wrap -->
    ';
*/
  if(empty($output)) return $r;
  echo $r;
}


// ############################################################################
// REWRITE LINK
// ############################################################################
// @url: the non-seo url that will be converted to seo (if seo is enabled)
// url can be formatted in four different ways:
// 1) index.php?categoryid=1&arguments=values (categoryid is now legacy, page_id should be used)
// 2) &arguments=values (in which case current page id is used)
// 3) arguments=values (in which case current page id is used)
// 4) (empty) returns the current page id and no arguments
// @encodeEntities: legacy argument, not used anymore
// @append_sid: legacy argument, not used anymore

/**
 * Returns a rewritten SD URL depending on SEO URLs being enabled or not
 *
 * @param string $url
 * @param bool $encodeEntities
 * @param bool $append_sid (legacy param, not used)
 * @return string
 */
function RewriteLink($url = '', $encodeEntities = true, $append_sid = true)
{
  global $categoryid, $pages_md_arr, $mainsettings_modrewrite, $mainsettings_url_subcategories,
         $mainsettings_url_extension, $mainsettings_sslurl, $mainsettings_sdurl;

  // if index.php was not included, then add it in and link it to current page
  if(substr($url, 0, 9) != 'index.php')
  {
    // check if arguments exist, if so does it start with a "&"?
    if(sd_strlen($url) && isset($url[0]) && ($url[0] != '&'))
    {
      $url = '&' . $url;
    }

    $url = 'index.php?categoryid=' . $categoryid . $url;
  }

  // find the page_id (or legacy categoryid) if not available then use PAGE_ID
  // SD322: use preg_match now
  if(@preg_match("/index.php\?categoryid=([0-9]+)/i", $url, $matches))
  {
    $page_id = (int)$matches[1];  // [0] = the $url, [1] = the first () match
  }

  if(!empty($page_id) && !empty($pages_md_arr[$page_id]) && !empty($pages_md_arr[$page_id]['sslurl']))
    $base_url = $mainsettings_sslurl;
  else
    $base_url = $mainsettings_sdurl; //SD343: for non-SSL pages use normal URL

  // return url if seo is disabled
  if(!$mainsettings_modrewrite)
  {
    $url = str_replace('&', '&amp;', $url);
    return $base_url . $url;
  }

  // get arguments
  $url = str_replace('&amp;', '&', $url);
  if(($arguments_pos = strpos($url, '&')) !== false)
  {
    // we don't want the first "&"
    // arguments should look like this: arguments=values
    $arguments = substr($url, ($arguments_pos + 1));
  }
  else
  {
    $arguments = '';
  }

  // page_id not found, use current PAGE_ID
  if(empty($page_id) || ($page_id < 1) || ($page_id > 999999))
  {
    $page_id = (int)$categoryid;
  }

  // we have page_id, but does it exist?
  if(!isset($pages_md_arr[$page_id]))
  {
    // page not found, this should really never happen
    // only thing to do is return non-seo url
    $url = str_replace('&', '&amp;', $url);
    return $base_url . $url;
  }

  // full transformation example:
  // index.php?categoryid=1&p4_start=0
  // index.html?p4_start=0
  $seo_url = '';

  // add child pages into url?
  if($mainsettings_url_subcategories)
  {
    $pages_arr = array();
    $pages_arr[] = $pages_md_arr[$page_id]['urlname'];

    $page_id_key = $page_id;

    while($pages_md_arr[$page_id_key]['parentid'] != 0)
    {
      $parent_page_id = $pages_md_arr[$page_id_key]['parentid'];
      $pages_arr[] = $pages_md_arr[$parent_page_id]['urlname'];
      $page_id_key = $parent_page_id;
    }

    $seo_url = implode('/', array_reverse($pages_arr));
  }
  else
  {
    // do not add child pages, just replace current page
    // change index.php?categoryid=1 into home, etc...
    $seo_url = $pages_md_arr[$page_id]['urlname'];
  }

  // append extension
  $seo_url .= $mainsettings_url_extension;

  // append arguments
  if(sd_strlen($arguments))
  {
    $seo_url .= '?' . $arguments;
  }

  // fix up the amp!
  $seo_url = str_replace('&', '&amp;', $seo_url);

  // url.com/home should be returned as url.com/ (otherwise google will treat home as two pages)
  if(($page_id == 1) && ($seo_url == $pages_md_arr[$page_id]['urlname']))
  {
    return $base_url;
  }
  else
  {
    if((substr($base_url, -1) == '/') && (substr($seo_url, 0, 1) == '/'))
    {
      $seo_url = substr($seo_url, 1);
    }

    return $base_url . $seo_url;
  }

} //RewriteLink


// ############################################################################
// CREATE GUID
// ############################################################################
// used during user registration, to validate accounts

/**
 * Returns a GUID string based on a random MD5 string
 *
 * @return string
 */
function CreateGuid()
{
  if (function_exists('com_create_guid'))
  {
    $uuid = com_create_guid();
    $uuid = str_replace('{', '', $uuid);
    $uuid = str_replace('}', '', $uuid);
    return $uuid;
  }
  else
  {
    mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
    $charid = strtoupper(md5(uniqid(mt_rand(), true)));
    $hyphen = chr(45);// "-"
    $uuid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
    return $uuid;
  }

} //CreateGuid


// ############################################################################
// DISPLAY MESSAGE
// ############################################################################
//
// @messages: ARRAY OR STRING - message to be displayed to user
// @is_error: BOOL/INT - if true, message will be displayed as an error (in red)
// @message_title: STRING, title of message (optional)

/**
 * Outputs or returns the passed $messages (array/string) within a DIV container
 *
 * @param array $messages Array or messages or single (string) message
 * @param bool $is_error
 * @param string $message_title
 * @param string $custom_id
 * @param bool $doOutput
 * @return string or null
 */
function DisplayMessage($messages, $is_error = false, $message_title = '', $custom_id=null, $doOutput=true)
{
  $res = '';
  if(!empty($custom_id))
  {
    $id = $custom_id;
  }
  else
  {
    $id = ($is_error ? 'alert-danger' : 'alert-success');
  }
  $res .= '<div class="alert ' . $id . '">';

  if(sd_strlen($message_title))
  {
    $res .= '<strong><i class="ace-icon fa ' .($is_error ? 'fa-times' : 'fa-check') .'"></i> ' . $message_title . '</strong><br />';
  }

  if(@is_array($messages))
  {
    for($i = 0; $i < count($messages); $i++)
    {
      $res .=  (sd_strlen($message_title) ? $messages[$i] : '<i class="ace-icon fa ' .($is_error ? 'fa-times' : 'fa-check') .'"></i> ' . $messages[$i]);

      if( ($i + 1) < count($messages))
      {
        $res .= '<br />';
      }
    }
  }
  else
  {
    $res .= '<i class="ace-icon fa ' .($is_error ? 'fa-times' : 'fa-check') .'"></i> ' .$messages;
  }

  $res .= '</div>
  <div class="clearfix"></div>';

  if(empty($doOutput)) return $res;
  echo $res;

} //DisplayMessage


/**
 * Convert passed string from Czech to Latin characters to be used for e.g. SEO URLs
 *
 * @param string $string String to be converted to Latin
 * @return string
 */
function sd_convert_czech($string) //SD343: added Czech transliteration
{
  if(!isset($string) || !sd_strlen($string)) return '';
  $map = array(  'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
    'ž' => 'z', 'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T',
    'Ů' => 'U', 'Ž' => 'Z');
  return @str_replace(array_keys($map), array_values($map), $string);
}

/**
 * Convert passed string from Greek to Latin characters to be used for e.g. SEO URLs
 *
 * @param string $string String to be converted to Latin
 * @return string
 */
function sd_convert_greek($string) //SD343: added Greek transliteration
{
  if(!isset($string) || !sd_strlen($string)) return '';
  static $map = array (
    'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
    'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
    'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
    'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
    'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',
    'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
    'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
    'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
    'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'W', 'Ϊ' => 'I',
    'Ϋ' => 'Y');
  return @str_replace(array_keys($map), array_values($map), $string);
}

/**
 * Convert passed string from Latvian to Latin characters to be used for e.g. SEO URLs
 *
 * @param string $string String to be converted to Latin
 * @return string
 */
function sd_convert_latvian($string) //SD343: added Latvian transliteration
{
  if(!isset($string) || !sd_strlen($string)) return '';
  $map = array('ā' => 'a', 'č' => 'c', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
    'š' => 's', 'ū' => 'u', 'ž' => 'z', 'Ā' => 'A', 'Č' => 'C', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i',
    'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N', 'Š' => 'S', 'Ū' => 'u', 'Ž' => 'Z');
  return @str_replace(array_keys($map), array_values($map), $string);
}

/**
 * Convert passed string from Polish to Latin characters to be used for e.g. SEO URLs
 *
 * @param string $string String to be converted to Latin
 * @return string
 */
function sd_convert_polish($string) //SD343: added Polish transliteration
{
  if(!isset($string) || !sd_strlen($string)) return '';
  $map = array('ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z',
    'ż' => 'z', 'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', 'Ł' => 'L', 'Ń' => 'N', 'Ó' => 'O', 'Ś' => 'S',
    'Ź' => 'Z', 'Ż' => 'Z');
  return @str_replace(array_keys($map), array_values($map), $string);
}

/**
 * Convert passed string from Ukrainian to Latin characters to be used for e.g. SEO URLs
 *
 * @param string $string String to be converted to Latin
 * @return string
 */
function sd_convert_ukrainian($string) //SD343: added Ukrainian transliteration
{
  if(!isset($string) || !sd_strlen($string)) return '';
  $map = array('Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G', 'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g');
  return @str_replace(array_keys($map), array_values($map), $string);
}

/**
 * Convert passed string from Cyrillic to latin characters to be used for e.g. SEO URLs
 *
 * @param string $string String to be converted to Latin
 * @return string
 */
function sd_convert_cyrillic($string)
{
  if(!isset($string) || !sd_strlen($string)) return '';
  $replace_cyr = array(
  'а' => 'a',
  'А' => 'A',
  'б' => 'b',
  'Б' => 'B',
  'в' => 'v',
  'В' => 'V',
  'г' => 'g',
  'Г' => 'G',
  'д' => 'd',
  'Д' => 'D',
  'е' => 'e',
  'Е' => 'E',
  'ё' => 'e',
  'Ё' => 'E',
  'ж' => 'zh',
  'Ж' => 'Zh',
  'з' => 'z',
  'З' => 'Z',
  'и' => 'i',
  'И' => 'I',
  'й' => 'j',
  'Й' => 'J',
  'к' => 'k',
  'К' => 'K',
  'л' => 'l',
  'Л' => 'L',
  'м' => 'm',
  'М' => 'M',
  'н' => 'n',
  'Н' => 'N',
  'о' => 'o',
  'О' => 'O',
  'п' => 'p',
  'П' => 'P',
  'р' => 'r',
  'Р' => 'R',
  'с' => 's',
  'С' => 'S',
  'т' => 't',
  'Т' => 'T',
  'у' => 'u',
  'У' => 'U',
  'ф' => 'f',
  'Ф' => 'F',
  'х' => 'h',
  'Х' => 'H',
  'ц' => 'c',
  'Ц' => 'C',
  'ч' => 'ch',
  'Ч' => 'CH',
  'ш' => 'sh',
  'Ш' => 'SH',
  'щ' => 'sh',
  'Щ' => 'SH',
  'ъ' => '',
  'Ъ' => '',
  'ы' => 'y',
  'Ы' => 'Y',
  'ь' => '' ,
  'Ь' => '' ,
  'э' => 'e',
  'Э' => 'E',
  'ю' => 'ju',
  'Ю' => 'JU',
  'я' => 'ja',
  'Я' => 'JA'
  );

  return @str_replace(array_keys($replace_cyr), array_values($replace_cyr), $string);

} //sd_convert_cyrillic


function sd_strtolower($string='')
{
  if(function_exists('mb_strtolower'))
    $string = mb_strtolower($string);
  else
    $string = strtolower($string);
  return $string;
} //sd_strtolower


/**
 * Converts the passed SEO article $title into SEO URL-compatible formatted string.
 * If $title_only is set to false, this can be used for any URL parameters.
 *
 * @param string $title SEO title of the article
 * @param int $artId Article ID
 * @param int $page Page of article (defaults to 1)
 * @param bool $title_only If true, the article id and page are not used
 * @param string $tail If $title_only is false, specifies default suffix for combination with id
 * @return string or null
 */
function ConvertNewsTitleToUrl($title, $artId, $page = 1, $title_only = false, $tail='a')
{
  global $mainsettings_settings_seo_default_separator, $mainsettings_settings_seo_lowercase,
         $mainsettings_url_extension;

  if(!isset($title) || !sd_strlen(trim($title))) return '';

  $separator = $mainsettings_settings_seo_default_separator;
  $seppattern = preg_quote($separator, '/');
  $tail = (!isset($tail) ? 'a' : ($tail!==false?strip_tags($tail):false)); //SD343

  $search = array (
    '@<script[^>]*?>.*?</script>@si', // Strip out javascript
    '@<[\/\!]*?[^<>]*?>@si',          // Strip out HTML tags
    "@(\R)[\s]+@",                    // Strip out white space
    '@&(quot|#34);@i',                // Replace HTML entities
    '@&(amp|#38);@i',
    '@&(lt|#60);@i',
    '@&(gt|#62);@i',
    '@&(nbsp|#160);@i',
    '@&(iexcl|#161);@i',
    '@&(cent|#162);@i',
    '@&(pound|#163);@i',
    '@&(copy|#169);@i',
    '@(\?|!|\'|"|~|^)@i',
    '@&#?0?39;@i'
    );
  $replace = array ('','',$separator,'','','','','','','','','','','');
  $title = @preg_replace($search, $replace, $title);

  //SD343: added optional Czech transliteration
  if(defined('SD_TRANSLIT_CZ') && SD_TRANSLIT_CZ)
    $title = sd_convert_czech($title);

  //SD343: added optional Greek transliteration
  if(defined('SD_TRANSLIT_GR') && SD_TRANSLIT_GR)
    $title = sd_convert_greek($title);

  //SD343: added optional Latvian transliteration
  if(defined('SD_TRANSLIT_LV') && SD_TRANSLIT_LV)
    $title = sd_convert_latvian($title);

  //SD343: added optional Polish transliteration
  if(defined('SD_TRANSLIT_PL') && SD_TRANSLIT_PL)
    $title = sd_convert_polish($title);

  //SD343: added optional Ukrainian transliteration
  if(defined('SD_TRANSLIT_UA') && SD_TRANSLIT_UA)
    $title = sd_convert_ukrainian($title);

  $title = sd_convert_cyrillic($title);

  //SD342: run manual replace before "iconv" to avoid duplicate "-" chars
  $search  = array('/','À', 'Á', 'Â', 'Ã', 'Ä',  'Å', 'Æ',  'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö',  'Ø', 'Ù', 'Ú', 'Û', 'Ü',  'Ý', 'ß',  'à', 'á', 'â', 'ã', 'ä', 'å', 'æ',  'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö',  'ø', 'ù', 'ú', 'û', 'ü',  'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ',  'ĳ',  'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ',  'œ',  'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ',  'ǽ',  'Ǿ', 'ǿ','þ','Þ','ð','^','~');
  $replace = array('-','A', 'A', 'A', 'A', 'AE', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'Oe', 'O', 'U', 'U', 'U', 'UE', 'Y', 'ss', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'oe', 'o', 'u', 'u', 'u', 'ue', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o','s','S','g','','');
  $title = @str_replace($search, $replace, $title);

  if(function_exists('iconv'))
  {
    //SD343: now with "//IGNORE"
    $old_ignore = $GLOBALS['sd_ignore_watchdog'];
    $GLOBALS['sd_ignore_watchdog'] = true;
    $new_title = @iconv(SD_CHARSET, 'ASCII//TRANSLIT//IGNORE', $title);
    if($new_title !== FALSE) $title = $new_title;
    unset($new_title);
    $GLOBALS['sd_ignore_watchdog'] = $old_ignore;
  }

  $title = @preg_replace('#[^a-zA-Z0-9_!\-\+/ ]+#m', $separator, $title);
  $title = trim(@preg_replace('/\s+/', $separator, $title));
  $title = @preg_replace('#[\.!\#$;:%\+\*\(\)\[\]]#im', $separator, $title);
  if(!empty($mainsettings_settings_seo_lowercase))
  {
    $title = sd_strtolower($title);
  }
  $title = @preg_replace('/[\+]+/', $separator, $title);

  //SD343: process and apply new SEO options
  global $mainsettings;

  static $seo_filter_words, $seo_stop_words_list,
         $seo_protect_words, $seo_protected_words_list,
         $seo_max_title_length, $seo_min_word_length, $seo_remove_short_words;

  if(!isset($seo_max_title_length))
    $seo_max_title_length = Is_Valid_Number($mainsettings['seo_max_title_length'],30,10,100);

  if(!isset($seo_filter_words))
  {
    global $sd_seo_stop_words_list_flip;
    $seo_filter_words = !empty($sd_seo_stop_words_list_flip);
    if($seo_filter_words && !isset($seo_stop_words_list))
    {
      $seo_stop_words_list = $sd_seo_stop_words_list_flip;
    }
  }

  if(!isset($seo_protect_words))
  {
    global $sd_seo_protect_words_flip;
    $seo_protect_words = !empty($sd_seo_protect_words_flip);
    if($seo_protect_words && !isset($seo_protected_words_list))
    {
      $seo_protected_words_list = $sd_seo_protect_words_flip;
      #if(count($seo_protected_words_list)) $seo_protected_words_list = array_flip($seo_protected_words_list);
    }
  }

  $seo_filter_words &= !empty($mainsettings['seo_filter_words']);
  if(!isset($seo_remove_short_words))
    $seo_remove_short_words = !empty($mainsettings['seo_remove_short_words']);
  if(!isset($seo_min_word_length))
    $seo_min_word_length = Is_Valid_Number($mainsettings['seo_min_word_length'],3,1,10);

  $words = explode($separator,$title);
  $title_new = '';
  $keep_protected_only = false;
  $title_org = $title;

  for ( $i = 0; $i < count($words); $i++ )
  {
    $lc_word = sd_strtolower($words[$i] = trim($words[$i]));
    $is_protected = $seo_protect_words && !empty($seo_protected_words_list) && isset($seo_protected_words_list[$lc_word]);
    $is_skipped   = $seo_filter_words && isset($seo_stop_words_list) && isset($seo_stop_words_list[$lc_word]);
    if(!$is_skipped || $is_protected)
    {
      if( !$is_protected && ($keep_protected_only || ($seo_remove_short_words && (sd_strlen($lc_word) <= $seo_min_word_length))) )
        continue;
      $title_new .= $separator . $words[$i];
      if(sd_strlen($title_new) >= $seo_max_title_length)
      {
        if($seo_protect_words)
        {
          $keep_protected_only = true;
          continue;
        }
        break;
      }
    }
  }
  $title = sd_strlen($title_new) ? $title_new : $title_org;

  $title = @preg_replace('/^'.$seppattern.'+|'.$seppattern.'+$/', '', $title);

  if(empty($title_only) && ($tail!==false))
  {
    if(!empty($artId) && ($artId > 0)) $title .= $separator.$tail.(int)$artId;
    if(!empty($page)  && ($page > 1))  $title .= $separator.'page'.(int)$page;
  }

  $title = @preg_replace("/$seppattern+/", $separator, $title);

  return !empty($title_only) ? $title : $title . $mainsettings_url_extension;

} //ConvertNewsTitleToUrl


/**
* Creates a thumbnail of an existing image file. Resulting image is scaled down to given max dimensions,
* optionally either squared or with kept aspect ratio.
* Globals: $sdlanguage
* Includes: class_sd_media.php (class SD_Image)
*
* @param string $imagefile      Path/filename for EXISTING image file, ex: ./images/test.jpg
* @param string $thumbnailfile  Path/filename where to save the thumbnail, ex: .images/test_thumbnail.jpg
* @param int $maxwidth          Max. width in pixels of resulting thumbnail
* @param int $maxheight         Max. height in pixels of resulting thumbnail
* @param bool $squareoff        Create square thumbnail (width equals height)
* @param bool $keepratio        Keep aspect ratio to avoid deformation/stretching
* @param array $conf_arr        Array with additional filter parameters for phpThumb
* @return TRUE if no errors, otherwise error text
*/
function CreateThumbnail($imagefile, $thumbnailfile, $maxwidth=100, $maxheight=100,
                         $squareoff=false, $keepratio=true, $conf_arr=array())
{
  global $sdlanguage;

  $errormsg = '';


  require_once(SD_INCLUDE_PATH.'class_sd_media.php');
  $sdi = new SD_Image($imagefile);
  if($sdi->getImageValid())
  {
    if(true !== $sdi->CreateThumbnail($thumbnailfile, $maxwidth, $maxheight, $squareoff, $keepratio, $conf_arr))
    {
      $errormsg = $sdi->getErrorMessage();
    }
  }
  else
  {
    $errormsg = $sdlanguage['common_thumbnail_failed'];
  }
  unset($sdi);
  return empty($errormsg) ? null : $errormsg;

} //CreateThumbnail


// ##################### LOG SYSTEM ERRORS TO THE DATABASE #####################

/**
* Internal function assigned as error handler for PHP
*
* @param int $errno
* @param string $message
* @param string $filename
* @param int $line
* @return null
*/
function ErrorHandler($errno, $message, $filename, $line) // SD313
{
  global $sd_ignore_watchdog;

  static $ErrorHandlerCount = 0, $types, $last_msg;

  if(!empty($sd_ignore_watchdog) || ($ErrorHandlerCount > WATCHDOG_MAX_ERRORS))
  return; //SD342

  if(!isset($types))
  {
    $types = array(1    => 'error',         2     => 'warning',         4    => 'parse error',
                   8    => 'notice',        16    => 'core error',      32   => 'core warning',
                   64   => 'compile error', 128   => 'compile warning', 256  => 'user error',
                   512  => 'user warning', //E_USER_WARNING
                   1024  => 'user notice', //E_USER_NOTICE
                   2047 => 'all', // previous
                   2048 => 'strict', // PHP 5+ E_STRICT
                   4096 => 'recoverable error', //PHP 5.2+ E_RECOVERABLE_ERROR
                   6143 => 'all', //PHP 5.2+ E_ALL
                   8192 => 'deprecated', //PHP 5.3+ E_DEPRECATED
                   16384 => 'deprecated', // PHP 5.3+ E_USER_DEPRECATED
                   30719 => 'all', //PHP 5.3.x E_ALL
                   32767 => 'all', //PHP 5.4.x E_ALL
                   );
  }
  $errno = (int)$errno;
  $err_log_level = defined(ERROR_LOG_LEVEL) ? (int)ERROR_LOG_LEVEL : -1;
  if(($err_log_level === -1) || ($errno === $err_log_level) || ($errno & $err_log_level))
  {
    $ErrorHandlerCount++;
    if(!isset($last_msg) || ($last_msg != $message))
    {
      $last_msg = $message;
    }
    else return;
    $entry = (isset($types[$errno])?$types[$errno]:'warning') . ": $message - File: $filename (line $line)";
    if($ErrorHandlerCount < WATCHDOG_MAX_ERRORS)
    {
      Watchdog('php', $entry, ($errno == 1) ? WATCHDOG_ERROR : WATCHDOG_WARNING);
    }
    else
    if($ErrorHandlerCount == WATCHDOG_MAX_ERRORS)
    {
      Watchdog( 'Watchdog', 'More than '.WATCHDOG_MAX_ERRORS.
        ' <strong>PHP messages</strong> detected, logging stopped at: '.$entry, WATCHDOG_ERROR);
    }
  }

} //ErrorHandler


// ############################################################################
// GET LANGUAGE
// ############################################################################

function GetLanguage($pluginid, $noCache = false)
{
  global $DB, $SDCache;

  if(empty($pluginid) || !is_numeric($pluginid) || ((int)$pluginid < 1) || ((int)$pluginid > 99999))
  {
    return array();
  }
  // SD313x: Check for cached results (not for admin panel!)
  $CacheEnabled = isset($SDCache) && ($SDCache instanceof SDCache) && $SDCache->IsActive();
  if($CacheEnabled && !defined('IN_ADMIN') && empty($noCache))
  {
    if(($language = $SDCache->read_var('planguage_'.$pluginid, 'language')) !== false)
    {
      if(is_array($language)) return $language;
    }
  }

  $language = array();

  // SD313: added ORDER BY to speed up usage
  if($getlanguage = $DB->query('SELECT * FROM {phrases} WHERE pluginid = %d ORDER BY varname',$pluginid))
  {
    while($languagearray = $DB->fetch_array($getlanguage,null,MYSQL_ASSOC))
    {
      if(isset($languagearray['customphrase']) && $languagearray['customphrase'])
      {
        $language[$languagearray['varname']] = $languagearray['customphrase'];
      }
      else
      {
        $language[$languagearray['varname']] = $languagearray['defaultphrase'];
      }
    }
  }

  //SD313: Rewrite cache file if enabled (always in admin)
  if($CacheEnabled && (defined('IN_ADMIN') || empty($noCache)))
  {
    $SDCache->write_var('planguage_'.$pluginid, 'language', $language);
  }
  return $language;

} //GetLanguage

if(!function_exists('NotEmpty'))
{
function NotEmpty($term=null)
{
  return !empty($term);
}
}

// ############################################################################
// GET USER INFO
// ############################################################################

function GetUserInfo($user = array())
{
  global $DB, $database, $mainsettings, $usersystem, $UserProfile;

  // Pre-set as Guest
  if(!is_array($user) || !count($user) || empty($user['userid']) || ($user['userid'] < 1))
  {
    $user_permissions       = isset($user['user_permissions'])?$user['user_permissions']:'';
    $user = array();
    $user['userid']         = 0;
    $user['usergroupid']    = -1;
    $user['usergroupids']   = array(-1);
    $user['username']       = '';
    $user['email']          = '';
    $user['loggedin']       = 0;
    $user['timezoneoffset'] = isset($mainsettings['timezoneoffset'])?(double)$mainsettings['timezoneoffset']:0;
    $user['dateformat']     = '';
    $user['dstonoff']       = 0;
    $user['banned']         = false;
    $user['ipaddress']      = '';
    $user['sessionurl']     = '';
    $user['salt']           = '';
    $user['user_permissions'] = $user_permissions;
    $user['require_vvc']    = 0;
    $user['report_message'] = 0; //SD360
  }
  extract($usersystem, EXTR_PREFIX_ALL | EXTR_REFS, 'usersystem');
  if(($usersystem_name=='Subdreamer') && !empty($user['userid']) && ($user['userid']>0))
  {
    $user['loggedin'] = 1;
  }

  // load guest usergroup at the end of this function unless this var turns true
  $userinfocreated = false;

  // usergroupids (filled in usersystem file) needs to be an array
  if(isset($user['usergroupids']))
  {
    $usergroupids = is_array($user['usergroupids']) ? $user['usergroupids'] : array($user['usergroupids']);
  }
  else
  {
    $usergroupids = array((int)$user['usergroupid']);
  }

  // Check for other usergroups
  if(!empty($user['usergroup_others']))
  {
    $GLOBALS['sd_ignore_watchdog'] = true;
    if($others = @unserialize($user['usergroup_others']))
    {
      if(is_array($others) && count($others))
      {
        $usergroupids = array_unique(array_merge($usergroupids, $others));
      }
    }
    $GLOBALS['sd_ignore_watchdog'] = false;
  }

  // if integrating with a forum, then the usergroupids in $user are from a forum database and not subdreamer
  $usergroupidcolumn = (($usersystem_name == 'Subdreamer') || !defined('SD_331')) ? 'usergroupid' : 'forumusergroupid';

  // start building empty array
  $userinfo                      = array();

  $userinfo['usergroupid']       = (isset($user['usergroupid']) ? $user['usergroupid'] : $user['usergroupids'][0]);
  $userinfo['usergroupids']      = array();
  $userinfo['usergroup_name']    = ''; //SD322
  $userinfo['userid']            = $user['userid'];
  $userinfo['username']          = $user['username'];
  $userinfo['displayname']       = ((isset($user['displayname']) && !empty($mainsettings['use_displayname']))?$user['displayname']:$user['username']);
  $userinfo['dateformat']        = (isset($user['dateformat'])?$user['dateformat']:'');
  $userinfo['dstonoff']          = (isset($user['dstonoff'])?$user['dstonoff']:'0');
  $userinfo['ipaddress']         = isset($user['ipaddress'])?$user['ipaddress']:USERIP;
  $userinfo['email']             = $user['email'];
  $userinfo['loggedin']          = $user['loggedin'];
  $userinfo['sessionurl']        = isset($user['sessionurl'])?$user['sessionurl']:'';
  $userinfo['timezoneoffset']    = isset($user['timezoneoffset'])?$user['timezoneoffset']:$mainsettings['timezoneoffset'];
  $userinfo['sessionid']         = isset($user['sessionid'])?$user['sessionid']:'';
  $userinfo['salt']              = isset($user['salt']) ? $user['salt'] : ''; //SD343
  $userinfo['securitytoken']     = isset($user['securitytoken']) ? $user['securitytoken'] : ''; //SD343
  $userinfo['securitytoken_raw'] = isset($user['securitytoken_raw']) ? $user['securitytoken_raw'] : ''; //SD343

  //SD370: MyBB allows users in the "Awaiting Activation" group to login,
  // so the mybb integration file also passes an "activated" attribute:
  if($usersystem['name'] == 'MyBB')
  {
    $userinfo['activated']       = !empty($user['activated']);
  }

  $prevDB = $DB->database;
  if($DB->database != SD_DBNAME) $DB->select_db(SD_DBNAME);

  //SD322: For logged-in user fetch all profile fields using new
  // userprofile object (see bottom of init.php)
  if(!empty($userinfo['userid']) && (defined('IN_ADMIN') || !Is_Ajax_Request()) && defined('SD_342') &&
     !defined('INSTALLING_PRGM') && !defined('UPGRADING_PRGM'))
  {
    $UserProfile_local = false;
    if(!class_exists('SDUserProfile') || !isset($UserProfile))
    {
      $UserProfile_local = true;
      require_once(SD_INCLUDE_PATH.'class_userprofile.php');
      // Instantiate Userprofile and load user data (users_data)
      $UserProfile = new SDUserProfile();
    }
    else
      SDProfileConfig::init(11);
    $UserProfile->LoadUser($userinfo['userid']);
    $userinfo['profile'] = SDProfileConfig::GetUserdata();
    //SD343: move usergroup info to userinfo; sort userinfo and remove
    // duplicates from profile array compared to userinfo
    if(isset($userinfo['profile']['usergroup_details']))
    {
      $userinfo['usergroup_details'] = $userinfo['profile']['usergroup_details'];
      unset($userinfo['profile']['usergroup_details']);
    }
    ksort($userinfo);
    if(!empty($userinfo['profile']))
    foreach(array_keys($userinfo) as $k => $v)
    {
      if(isset($userinfo['profile'][$v])) unset($userinfo['profile'][$v]);
    }
    if(!empty($userinfo['profile'])) ksort($userinfo['profile']);
    if($UserProfile_local)
    {
      unset($UserProfile);
    }
  }

  //SD322: for SD usersystem: if not yet set, generate a salt value for the user
  //SD343: salt value may be set under "profile" if forum is integrated!
  $userinfo['salt'] = !empty($user['salt']) ? $user['salt'] : (isset($userinfo['profile']['salt'])?$userinfo['profile']['salt']:'');

  if(empty($usergroupids))
  {
    $usergroupids[] = $userinfo['usergroupid'];
  }

  // Fill permission related items with default values
  $userinfo['adminaccess']           = 0;
  $userinfo['commentaccess']         = 0;
  $userinfo['offlinecategoryaccess'] = 0;

  $userinfo['categoryviewids']       = array();
  $userinfo['categorymenuids']       = array();
  $userinfo['categorymobilemenuids'] = array(); //SD370
  $userinfo['pluginviewids']         = array();
  $userinfo['pluginsubmitids']       = array();
  $userinfo['plugindownloadids']     = array();
  $userinfo['plugincommentids']      = array();
  $userinfo['pluginmoderateids']     = array();
  $userinfo['pluginadminids']        = array();
  $userinfo['custompluginviewids']   = array();
  $userinfo['custompluginadminids']  = array();
  $userinfo['authormode']            = 0;
  $userinfo['forumusergroupids']     = $usergroupids;
  $userinfo['admin_pages']           = array(); //SD322 - new: gets filled from usergroups
  $userinfo['user_permissions']      = isset($user['user_permissions'])?$user['user_permissions']:'';
  $userinfo['maintain_customplugins']= 0; //SD331
  $userinfo['require_vvc']           = 0; //SD332
  $userinfo['report_message']        = 0; //SD360

  $org_usergroupid = $userinfo['usergroupid'];
  $userinfo['usergroupid'] = -1;

  if($DB->database != SD_DBNAME) $DB->select_db(SD_DBNAME);
  for($i = 0, $ugcount = count($usergroupids); $i < $ugcount; $i++)
  {
    if(!empty($usergroupids[$i]) &&
       ($getusergroups = $DB->query('SELECT * FROM '.PRGM_TABLE_PREFIX."usergroups WHERE `%s` = %d",
        $usergroupidcolumn, (int)$usergroupids[$i])))
    {
      while($usergroup = $DB->fetch_array($getusergroups,null,MYSQL_ASSOC))
      {
        $userinfocreated = true;

        if(($usersystem_name!='Subdreamer') && ($userinfo['usergroupid'] < 0) && !empty($usergroup['forumusergroupid']) && ($org_usergroupid == $usergroup['forumusergroupid']))
        {
          $userinfo['usergroupid'] = $usergroup['usergroupid'];
        }
        else
        if(($usersystem_name=='Subdreamer') && ($userinfo['usergroupid'] < 0) && ($org_usergroupid == $usergroup['usergroupid']))
        {
          $userinfo['usergroupid'] = $usergroup['usergroupid'];
        }
        $userinfo['usergroupids'][] = (int)$usergroup['usergroupid'];

        //SD322: fill new key "admin_access_pages" with admin-access for pages
        if(!empty($usergroup['admin_access_pages']) && (substr($usergroup['admin_access_pages'],0,2)=='a:'))
        {
          $usergroup['admin_access_pages'] = sd_strtolower($usergroup['admin_access_pages']);
          if(!empty($usergroup['admin_access_pages']) && ($admin_access_pages = @unserialize($usergroup['admin_access_pages'])) !== false)
          {
            $userinfo['admin_pages'] = array_unique(array_merge($userinfo['admin_pages'], $admin_access_pages));
          }
        }

        // Take over usergroup setting from primary usergroup
        //SD343: only if not already present as it may already be loaded by user profile
        if($usergroup['usergroupid'] == $userinfo['usergroupid'])
        {
          if(!isset($userinfo['usergroup_details']))
            $userinfo['usergroup_details'] = array();
          else
            $userinfo['usergroup_details'] = array_merge($userinfo['usergroup_details'], $usergroup);
          // check some values if possible
          $userinfo['usergroup_details']['adminaccess'] = !empty($userinfo['usergroup_details']['adminaccess']);
          $userinfo['usergroup_details']['banned'] = !empty($userinfo['usergroup_details']['banned']);
          $userinfo['usergroup_details']['color_online'] = empty($userinfo['usergroup_details']['color_online']) ? '' : '#'.$userinfo['usergroup_details']['color_online'];
          $userinfo['usergroup_details']['display_online'] = !empty($userinfo['usergroup_details']['display_online']);
          $userinfo['usergroup_details']['displayname'] = !empty($userinfo['usergroup_details']['displayname']) ? $userinfo['usergroup_details']['displayname'] : $usergroup['name'];
          $userinfo['usergroup_details']['description'] = !empty($userinfo['usergroup_details']['description']) ? $userinfo['usergroup_details']['description'] : '';
          $userinfo['usergroup_details']['excerpt_mode'] = !empty($userinfo['usergroup_details']['excerpt_mode']);
          $userinfo['usergroup_details']['excerpt_message'] = isset($userinfo['usergroup_details']['excerpt_message'])?(string)$userinfo['usergroup_details']['excerpt_message']:'';
          $userinfo['usergroup_details']['excerpt_length'] = empty($userinfo['usergroup_details']['excerpt_length'])?0:(int)$userinfo['usergroup_details']['excerpt_length'];
          $userinfo['usergroup_details']['name'] = $usergroup['name'];
          // Usergroup config values:
          $userinfo['usergroup_details']['msg_enabled'] = !empty($userinfo['usergroup_details']['msg_enabled']);
          $userinfo['usergroup_details']['sig_enabled'] = !empty($userinfo['usergroup_details']['sig_enabled']);
          //SD343: "usergroup_name" filled with name of primary usergroup
          $userinfo['usergroup_name'] = $usergroup['name'];
          ksort($userinfo['usergroup_details']); //SD343 sort entries
        }

        // override only if new setting is 1, otherwise revert back to previous loop value or default setting of 0
        $userinfo['adminaccess']           = $userinfo['adminaccess'] || $usergroup['adminaccess'];
        $userinfo['commentaccess']         = $userinfo['commentaccess'] || $usergroup['commentaccess'];
        $userinfo['offlinecategoryaccess'] = $userinfo['offlinecategoryaccess'] || $usergroup['offlinecategoryaccess'];
        $userinfo['maintain_customplugins']= !empty($userinfo['maintain_customplugins']) || !empty($usergroup['maintain_customplugins']);
        //SD343: if a usergroup does not have option "require_vvc" set, then remove it for current user.
        // i.e. user with primary usergroup "Registered Users" with require_vvc being checked
        // will not have that option if a secondary usergroup (like "Moderators") exists which
        // does not have that option checked:
        $userinfo['require_vvc']           = !empty($userinfo['require_vvc']) && !empty($usergroup['require_vvc']);

        // the following are arrays, so add arrays together for multiple usergroup support
        $userinfo['categoryviewids']       = $usergroup['categoryviewids']       ? array_merge($userinfo['categoryviewids'],      explode(',', $usergroup['categoryviewids']))      : $userinfo['categoryviewids'];
        $userinfo['categorymenuids']       = $usergroup['categorymenuids']       ? array_merge($userinfo['categorymenuids'],      explode(',', $usergroup['categorymenuids']))      : $userinfo['categorymenuids'];
        $userinfo['pluginviewids']         = $usergroup['pluginviewids']         ? array_merge($userinfo['pluginviewids'],        explode(',', $usergroup['pluginviewids']))        : $userinfo['pluginviewids'];
        $userinfo['pluginsubmitids']       = $usergroup['pluginsubmitids']       ? array_merge($userinfo['pluginsubmitids'],      explode(',', $usergroup['pluginsubmitids']))      : $userinfo['pluginsubmitids'];
        $userinfo['plugindownloadids']     = $usergroup['plugindownloadids']     ? array_merge($userinfo['plugindownloadids'],    explode(',', $usergroup['plugindownloadids']))    : $userinfo['plugindownloadids'];
        $userinfo['plugincommentids']      = $usergroup['plugincommentids']      ? array_merge($userinfo['plugincommentids'],     explode(',', $usergroup['plugincommentids']))     : $userinfo['plugincommentids'];
        $userinfo['pluginmoderateids']     = $usergroup['pluginmoderateids']     ? array_merge($userinfo['pluginmoderateids'],    explode(',', $usergroup['pluginmoderateids']))    : $userinfo['pluginmoderateids'];
        $userinfo['pluginadminids']        = $usergroup['pluginadminids']        ? array_merge($userinfo['pluginadminids'],       explode(',', $usergroup['pluginadminids']))       : $userinfo['pluginadminids'];
        $userinfo['custompluginviewids']   = $usergroup['custompluginviewids']   ? array_merge($userinfo['custompluginviewids'],  explode(',', $usergroup['custompluginviewids']))  : $userinfo['custompluginviewids'];
        $userinfo['custompluginadminids']  = $usergroup['custompluginadminids']  ? array_merge($userinfo['custompluginadminids'], explode(',', $usergroup['custompluginadminids'])) : $userinfo['custompluginadminids'];
        $userinfo['categorymobilemenuids'] = !empty($usergroup['categorymobilemenuids']) ? array_merge($userinfo['categorymobilemenuids'],explode(',', $usergroup['categorymobilemenuids'])): $userinfo['categorymobilemenuids']; //SD370

        // inherit author mode from every usergroup
        $userinfo['authormode'] = $userinfo['authormode'] ||
                                  !empty($usergroup['articles_author_mode']);

        // SD313: if user is in a "Banned" usergroup, set user's banned flag
        if($usergroup['banned'])
        {
          $userinfo['banned'] = 1;
          $userinfo['loggedin'] = 0; //SD343
        }
      }
    }
  }
  $userinfo['usergroupids']          = array_unique($userinfo['usergroupids']);
  $userinfo['categoryviewids']       = array_unique($userinfo['categoryviewids']);
  $userinfo['categorymenuids']       = array_unique($userinfo['categorymenuids']);
  $userinfo['categorymobilemenuids'] = array_unique($userinfo['categorymobilemenuids']); //SD370
  $userinfo['pluginviewids']         = array_unique($userinfo['pluginviewids']);
  $userinfo['pluginsubmitids']       = array_unique($userinfo['pluginsubmitids']);
  $userinfo['plugindownloadids']     = array_unique($userinfo['plugindownloadids']);
  $userinfo['plugincommentids']      = array_unique($userinfo['plugincommentids']);
  $userinfo['pluginmoderateids']     = array_unique($userinfo['pluginmoderateids']);
  $userinfo['pluginadminids']        = array_unique($userinfo['pluginadminids']);
  $userinfo['custompluginviewids']   = array_unique($userinfo['custompluginviewids']);
  $userinfo['custompluginadminids']  = array_unique($userinfo['custompluginadminids']);
  if(isset($userinfo['usergroupid']) && !strlen($userinfo['usergroupid']))
  {
    $userinfo['error'] = true;
  }
  if($userinfo['adminaccess']) // no author mode if full admin
  {
    $userinfo['authormode'] = 0;
  }

  // NOT FOR SMF forum: if a usergroup hasn't been created (most likely because
  // an admin hasn't associated a forum usergroup to the subdreamer usergroup),
  // then load the "Guests" usergroup:
  if(!$userinfocreated || ((substr($usersystem_name,0,6) != 'Simple') &&
     (false !== in_array(GUESTS_UGID, $userinfo['usergroupids']))))
  {
    $usergroup = $DB->query_first('SELECT * FROM '.PRGM_TABLE_PREFIX.'usergroups WHERE usergroupid = %d',GUESTS_UGID);
    $userinfo['usergroupid']           = GUESTS_UGID;
    $userinfo['usergroupids']          = array(GUESTS_UGID);
    //SD322: Subdreamer user system: new key "usergroup_name" filled with (display-)name of primary usergroup
    $userinfo['usergroup_name']        = $usergroup['name'];
    //SD342
    $userinfo['usergroup_details'] = array();
    $userinfo['usergroup_details']['name'] = empty($usergroup['displayname']) ? $usergroup['name'] : $usergroup['displayname'];
    $userinfo['usergroup_details']['color_online'] = empty($usergroup['color_online']) ? '' : $usergroup['color_online'];
    $userinfo['usergroup_details']['display_online'] = !empty($usergroup['display_online']);
    $userinfo['usergroup_details']['excerpt_mode'] = !empty($usergroup['excerpt_mode']);
    $userinfo['usergroup_details']['excerpt_message'] = (string)$usergroup['excerpt_message'];
    $userinfo['usergroup_details']['excerpt_length'] = (int)$usergroup['excerpt_length'];

    $userinfo['adminaccess']           = 0;
    $userinfo['commentaccess']         = 0;
    $userinfo['offlinecategoryaccess'] = 0;
    $userinfo['require_vvc']           = !empty($usergroup['require_vvc']);
    $userinfo['authormode']            = 0;

    // the following are arrays, so add arrays together for multiple usergroup support
    $userinfo['categoryviewids']       = $usergroup['categoryviewids']      ? explode(',', $usergroup['categoryviewids'])      : array(1);
    $userinfo['categorymenuids']       = $usergroup['categorymenuids']      ? explode(',', $usergroup['categorymenuids'])      : array();
    $userinfo['categorymobilemenuids'] = $usergroup['categorymobilemenuids']? explode(',', $usergroup['categorymobilemenuids']): array(); //SD370
    $userinfo['pluginviewids']         = $usergroup['pluginviewids']        ? explode(',', $usergroup['pluginviewids'])        : array(1);
    $userinfo['pluginsubmitids']       = $usergroup['pluginsubmitids']      ? explode(',', $usergroup['pluginsubmitids'])      : array();
    $userinfo['plugindownloadids']     = $usergroup['plugindownloadids']    ? explode(',', $usergroup['plugindownloadids'])    : array();
    $userinfo['plugincommentids']      = $usergroup['plugincommentids']     ? explode(',', $usergroup['plugincommentids'])     : array();
    $userinfo['pluginmoderateids']     = $usergroup['pluginmoderateids']    ? explode(',', $usergroup['pluginmoderateids'])    : array();
    $userinfo['pluginadminids']        = $usergroup['pluginadminids']       ? explode(',', $usergroup['pluginadminids'])       : array();
    $userinfo['custompluginviewids']   = $usergroup['custompluginviewids']  ? explode(',', $usergroup['custompluginviewids'])  : array();
    $userinfo['custompluginadminids']  = $usergroup['custompluginadminids'] ? explode(',', $usergroup['custompluginadminids']) : array();
  }
  $userinfo['usergroupids'] = array_unique($userinfo['usergroupids']);
  sort($userinfo['usergroupids']);

  // IF usergroup is still not set, assign the first ID as that is usually
  // the one with the most permissions
  if(($userinfo['usergroupid'] < 1) && count($userinfo['usergroupids']))
  {
    $userinfo['usergroupid'] = $userinfo['usergroupids'][0];
  }

  //SD360: set "report_message" to indicate if user is allowed to
  // submit a manual message when reporting, based on usergroups
  $msg_groups =
    ( !empty($mainsettings['reporting_allow_user_message']) ?
      sd_ConvertStrToArray($mainsettings['reporting_allow_user_message'],',') :
      array() );
  $userinfo['report_message'] =
    !empty($mainsettings['reporting_allow_user_message']) &&
    !empty($userinfo['usergroupids']) &&
    @array_intersect($userinfo['usergroupids'], $msg_groups);

  if($DB->database != $prevDB) $DB->select_db($prevDB);

  // Performing a natural sort in order to provide a speed increase
  natsort($userinfo['categoryviewids']);
  natsort($userinfo['categorymenuids']);
  natsort($userinfo['categorymobilemenuids']); //SD370
  natsort($userinfo['custompluginviewids']);
  natsort($userinfo['custompluginadminids']);
  natsort($userinfo['pluginviewids']);
  natsort($userinfo['pluginsubmitids']);
  natsort($userinfo['plugindownloadids']);
  natsort($userinfo['plugincommentids']);
  natsort($userinfo['pluginmoderateids']);
  natsort($userinfo['pluginadminids']);
  natsort($userinfo['usergroupids']);
  sort($userinfo['admin_pages']);

  return $userinfo;

} //GetUserInfo


// ############################################################################
// GET SETTINGS
// ############################################################################

function GetSettings($groupname = '')
{
  global $DB, $SDCache;

  if(strlen($groupname))
  {
    // Set no cache id here!
    $sql = "SELECT varname, value FROM {mainsettings}
            WHERE groupname = '".$DB->escape_string($groupname)."' ORDER BY varname";
  }
  else
  {
    if(!defined('IN_ADMIN')) $cache_id = CACHE_ALL_MAINSETTINGS;
    $sql = 'SELECT varname, value FROM {mainsettings} ORDER BY varname';
  }

  // SD313: do not read cached Main Settings in Admin panel!
  if(!defined('IN_ADMIN') && isset($SDCache) &&
     isset($cache_id) && $SDCache->IsActive()) // SD313x
  {
    if(($settings = $SDCache->read_var($cache_id, 'mainsettings')) !== false)
    {
      return $settings;
    }
  }

  $get_settings = $DB->query($sql);
  while($setting_arr = $DB->fetch_array($get_settings,null,MYSQL_ASSOC))
  {
    $settings[$setting_arr['varname']] = $setting_arr['value'];
  }

  if(isset($SDCache) && isset($cache_id) && $SDCache->IsActive()) // SD313x
  {
    $SDCache->write_var($cache_id, 'mainsettings', $settings);
  }

  if(!empty($settings))
  {
    $DB->free_result($get_settings);
  }

  return isset($settings) ? $settings : array();

} //GetSettings


// ############################################################################
// UNHTMLSPECIALCHARS
// ############################################################################
// IMPORTANT: due to clashes with vB function names the function unhtmlspecialchars
// was MOVED to "init.php"!!!
//SD360: allows calling with array if key 1 exists (i.e. $string[1])!
// This is used in messaging as preg replace callback!
function sd_unhtmlspecialchars($string)
{
  if(!isset($string) && !is_string($string) && !is_array($string))
  {
    return isset($string)?'':null;
  }
  if(is_array($string)) //SD360: accepts preg replace callback
  {
    if(isset($string[1]))
    {
      $output = sd_unhtmlspecialchars($string[1]);
      return $output;
    }
    else return '';
  }
  // special character handling
  // utf8_decode and reconvert HTML entities (e.g. &uuml; ==> Ã¼)
  $trans = get_html_translation_table(HTML_ENTITIES);

  // ipb uses different (or old) entities, so lets fix em to our style!
  $string = str_replace('&#39;', '&#039;', $string);
  $string = str_replace('&#33;', '&#033;', $string);

  // some versions of PHP match single quotes to &#39;
  $trans["'"] = '&#039;';

  // there are some forums like vBulletin that do not store entities, but
  // instead they store the special chars themselves. So when adding articles
  // to the forum we need to use unhtmlspecialchars (tinymce automatically converts & for good reasons
  // like not breaking the textarea), however this proves to be a problem because there
  // are certain characters that do not go through unhtmlspecialchars. So,
  // lets try and list the most popular ones here:
  $trans["“"] = '&ldquo;';
  $trans["”"] = '&rdquo;';
  $trans["‘"] = '&lsquo;';
  $trans["’"] = '&rsquo;';
  $trans["«"] = '&laquo;';
  $trans["»"] = '&raquo;';
  $trans["©"] = '&copy;';
  $trans["®"] = '&reg;';
  $trans["™"] = '&trade;';
  $trans["!"] = '&#033;';
  $trans['$'] = '&#036;';

  $trans = array_flip ($trans);

  // return backslashes back to normal, during preclean backslashes are protected
  $string = str_replace('\\\\', '\\', $string );

  return @strtr($string, $trans);
} //sd_unhtmlspecialchars

/*
function unhtmlspecialchars($string)
{
  $string = str_replace('&amp;', '&', $string );
  $string = str_replace('&#039;', '\'', $string );
  $string = str_replace('&quot;', '"', $string );
  $string = str_replace('&lt;', '<', $string );
  $string = str_replace('&gt;', '>', $string );

  // return backslashes back to normal, during preclean backslashes are protected
  $string = str_replace('\\\\', '\\', $string );

  return $string;
}
*/

// SD313: new include! DO NOT MOVE THIS LINE!
require_once(SD_INCLUDE_PATH . 'functions_security.php');
require_once(SD_INCLUDE_PATH.'class_sd_smarty.php'); //SD342


// ############################################################################
// DisplayBBEditor
// ############################################################################
// SD313: helper function to display textarea with or without BBCode editor
// depending on "$allow_bbcode" (which could come from main settings)
function DisplayBBEditor($allow_bbcode, $textarea_name, $content = '', $class=null, $cols=80, $rows=8, $return=false)
{
  global $mainsettings;
  
  $output = '';

  if($allow_bbcode)
  {
    $class = !empty($class) ? ' '.$class : '';
    $output = '
    <textarea class="bbeditor'.$class.'" cols="'.$cols.'" rows="'.$rows.'" id="'.$textarea_name.'" name="'.$textarea_name.'">' . $content . '</textarea>';
  }
  else
  {
    $class = !empty($class) ? 'class="'.$class.'" ' : '';
   $output = '
    <textarea '.$class.'cols="'.$cols.'" rows="'.$rows.'" id="'.$textarea_name.'" name="'.$textarea_name.'">' . $content . '</textarea>';
  }
  
  if($return) return $output;
  
  echo $output;
} //DisplayBBEditor


// ############################################################################
// DisplayDate
// ############################################################################

function DisplayDate($gmepoch, $dateformat = '', $useuserformat = 0, $returndatevalue = false, $user_time=false)
{
  // Note: $gmepoch MUST be Un*x timestamp, not GMT-time!

  global $sdlanguage, $mainsettings, $mainsettings_dateformat, $mainsettings_daylightsavings,
         $mainsettings_timezoneoffset, $userinfo, $usersystem;

  $data = isset($userinfo['profile']) ? (array)$userinfo['profile'] : $userinfo; //SD342

  $server_has_dst = @date('I'); // 1 or 0
  $gmt_diff       = @date('O'); // e.g. "+0200"
  $gmt_diff       = doubleval(substr($gmt_diff,0,3))+(substr($gmt_diff,3,2)=='00'?0:0.5);
  $useuserformat  = !empty($useuserformat);
  $dst = 0;
  if(!empty($user_time) || $useuserformat)
  {
    if($useuserformat)
    {
      $dateformat = (isset($data['user_dateformat']) && sd_strlen($data['user_dateformat'])) ? $data['user_dateformat'] : $dateformat;
    }
    $dst = empty($data['user_dst']) ? 0 : 1;
    $timezoneoffset = (isset($data['user_timezone']) ? (double)$data['user_timezone'] : 0);
  }
  else
  {
    $dst = empty($mainsettings_daylightsavings) ? 0 : $server_has_dst;
    $timezoneoffset = $mainsettings_timezoneoffset;
  }

  if(empty($dateformat))
  {
    $dateformat = $mainsettings_dateformat;
    $dateformat = str_replace('|', '', $dateformat);
  }

  // return a date (if <> 0)
  if(!empty($gmepoch))
  {
    if(!empty($user_time) || $useuserformat)
    {
      $gmepoch = $gmepoch - 3600 * $gmt_diff + 3600 * ($timezoneoffset + $dst);
    }
    return empty($returndatevalue) ? strtr(@date($dateformat, (int)$gmepoch), $sdlanguage) : $gmepoch; //SD343: "(int)"
  }
  else
  {
    return '';
  }

} //DisplayDate


function gmgetdate2($ts = null)
{
  //Example to create output:
  //echo '<pre>'.print_r(gmgetdate2(),true).'</pre>';
  static $k = array('seconds','minutes','hours','mday',
                    'wday','mon','year','yday','weekday','month',0);
  return(array_combine($k,split(":",
         @gmdate('s:i:G:j:w:n:Y:z:l:F:U',is_null($ts)?time():$ts))));
}


// ############################################################################
// DISPLAY READABLE FILESIZE
// ############################################################################

function DisplayReadableFilesize($filesize)
{
  static $kb = 1024; // Kilobyte
  static $mb = 1048576; // Megabyte
  static $gb = 1073741824; // Gigabyte
  static $tb = 1099511627776; // Terabyte

  $decimals = 2;
  if(doubleval($filesize) < $kb)
  {
    $ext = ' B';
    $decimals = 0;
    $size = $filesize;
  }
  elseif(doubleval($filesize) < $mb)
  {
    $ext = ' KB';
    $size = round($filesize/$kb,2);
  }
  elseif(doubleval($filesize) < $gb)
  {
    $ext = ' MB';
    $size = round($filesize/$mb,2);
  }
  elseif(doubleval($filesize) < $tb)
  {
    $ext = ' GB';
    $size = round($filesize/$gb,2);
  }
  else
  {
    $ext = ' TB';
    $size = round($filesize/$tb,2);
    if($size > 1024) //SD343
    {
      $ext = ' PB';
      $size = round($size/1024,2);
    }
  }
  return (!isset($size) || !strlen($size)) ? '-' : (number_format($size,$decimals).$ext);
} //DisplayReadableFilesize


// ############################################################################
// GET PLUGIN NAMES FOR MAIN/DOWNLOADABLE PLUGINS
// ############################################################################
//SD322: return array with all (non-custom) plugins with 2 entries per plugin,
// e.g. for the "Articles" plugin:
// array['Articles'] == <translated name>
// array[2] == <translated name>
function LoadPluginNames()
{
  global $DB, $sdlanguage;

  $names = array();
  //SD360: include "base_plugin" in array, but first check if it exists
  $hasBase = defined('SD_342') ||
             $DB->column_exists(PRGM_TABLE_PREFIX.'plugins','base_plugin');
  $p_extra = '';
  if($hasBase)
  {
    $p_extra = ', base_plugin';
  }
  $get_plugins = $DB->query('SELECT pluginid, name'.$p_extra.
                            ' FROM {plugins} ORDER BY pluginid');
  while($plugin_arr = $DB->fetch_array($get_plugins,null,MYSQL_ASSOC))
  {
    $id = $plugin_arr['pluginid'];
    if(isset($sdlanguage['plugin_name_'.$id]))
    {
      $plugin_name = $sdlanguage['plugin_name_'.$id];
    }
    else
    {
      $plugin_name = $plugin_arr['name'];
    }
    $names[$plugin_arr['name']] = $plugin_name;
    $names[$id] = $plugin_name;
    //SD360: add base plugin to array
    if(!empty($plugin_arr['base_plugin']))
    {
      $names['base-'.$id] = $plugin_arr['base_plugin'];
    }
  }
  if(!empty($names)) @ksort($names);

  return $names;
} //LoadPluginNames


// ############################### LOAD SMILIES ################################

function LoadSmilies() //SD342
{
  global $DB, $bbcode;
  if(!isset($DB) || !isset($bbcode) || empty($bbcode)) return false;
  $DB->ignore_error = true;
  if($getsmilies = $DB->query('SELECT * FROM {smilies}'))
  {
    while($smilie = $DB->fetch_array($getsmilies,null,MYSQL_ASSOC))
    {
      $bbcode->smileys[$smilie['text']] = $smilie['image'];
    }
  }
  $DB->ignore_error = false;
} //LoadSmilies


// ############################################################################
// PRECLEAN
// ############################################################################
// PreClean is used for all post, get, and cookie data in "init.php" and
// applies "addslashes" so contents is ready for use with database.

static $sd_php_hsc_compat = array(
  '1251' => 0,
  '1252' => 1,
  '866' => 2,
  '932' => 3,
  '936' => 4,
  '950' => 5,
  'big5' => 6,
  'big5-hkscs' => 7,
  'cp1251' => 8,
  'cp1252' => 9,
  'cp866' => 10,
  'euc-jp' => 11,
  'eucjp' => 12,
  'gb2312' => 13,
  'ibm866' => 14,
  'iso-8859-1' => 15,
  'iso-8859-15' => 16,
  'iso8859-1' => 17,
  'iso8859-15' => 18,
  'koi8-r' => 19,
  'koi8-ru' => 20,
  'koi8r' => 21,
  'shift_jis' => 22,
  'sjis' => 23,
  'utf-8' => 24,
  'win-1251' => 25,
  'windows-1251' => 26,
  'windows-1252' => 27);
static $sd_php_use_charset = null; //SD370: must be null
static $sd_mbencoding = null; //SD370: must be null

function PreCleanValue(& $data)
{
  global $sd_mbencoding, $sd_php_hsc_compat, $sd_php_use_charset;

  if(!isset($data) || ($data==='') || ($data==='null') || !sd_strlen($data)) return;

  // Change the following chars: & ' " < > into their html entities
  // however, htmlspecialchars might give trouble to other languages (e.g.: russian)
  // If so then try adding the charset argument, read this page for more information:
  // http://us4.php.net/manual/en/function.htmlspecialchars.php
  if(!isset($sd_php_use_charset) && is_array($sd_php_hsc_compat))
  {
    $sd_php_use_charset = (SD_CHARSET=='utf-8') || @isset($sd_php_hsc_compat[SD_CHARSET]);
  }

  //SD370: replace invalid utf-8 characters for security:
  if(!isset($sd_mbencoding))
  {
    $sd_mbencoding = function_exists('mb_convert_encoding');
  }
  if($sd_mbencoding && (SD_CHARSET=='utf-8'))
  {
    $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
  }
  /*
  $data = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]'.
    '|(?<=^|[\x00-\x7F])[\x80-\xBF]+'.
    '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*'.
    '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})'.
    '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/',
    '', $data);
  $data = preg_replace('/\xE0[\x80-\x9F][\x80-\xBF]|'.
                       '\xED[\xA0-\xBF][\x80-\xBF]/S', '', $data);
  */
  if($sd_php_use_charset)
  {
    $data = htmlspecialchars($data, ENT_QUOTES, SD_CHARSET);
  }
  else
  {
    $data = htmlspecialchars($data, ENT_QUOTES);
  }

  // htmlspecialchars doesn't protect against the backslash (\) and the NULL Byte
  // so run addslashes which will take care of those two items
  $data = addslashes($data);

} //PreCleanValue


function PreClean($data)
{
  global $sd_php_hsc_compat, $sd_php_use_charset;

  if(!isset($data)) return $data;

  if(is_array($data))
  {
    $out = array();
    foreach($data as $key => $val)
    {
      $out[$key] = PreClean($val);
    }
  }
  else
  {
    if(!sd_strlen($data)) return $data;

    // Change the following chars: & ' " < > into their html entities
    // however, htmlspecialchars might give trouble to other languages (e.g.: russian)
    // If so then try adding the charset argument, read this page for more information:
    // http://us4.php.net/manual/en/function.htmlspecialchars.php
    if(!isset($sd_php_use_charset) && is_array($sd_php_hsc_compat))
    {
      $sd_php_use_charset = @in_array(SD_CHARSET, $sd_php_hsc_compat);
    }
    if($sd_php_use_charset)
    {
      $out = htmlspecialchars($data, ENT_QUOTES, SD_CHARSET);
    }
    else
    {
      $out = htmlspecialchars($data, ENT_QUOTES);
    }

    // htmlspecialchars doesn't protect against the backslash (\) and the NULL Byte
    // so run addslashes which will take care of those two items
    $out = addslashes($out);
  }

  return $out;

} //PreClean


// ############################################################################
// STRIP SLASHES ARRAY
// ############################################################################
//
// remove slashes that PHP might have added if magic quotes was turned on

function StripSlashesValue(& $data)
{
  if(isset($data)) $data = stripslashes($data);
}

function StripSlashesArray($data)
{
  if(is_array($data))
  {
    if(!isset($return)) $return = array();
    foreach($data as $key => $val)
    {
      $return[$key] = StripSlashesArray($val);
    }
    return isset($return) ? $return : array();
  }
  else
  {
    if(isset($data))
      return stripslashes($data);
    else
      return null;
  }
} //StripSlashesArray


// ############################################################################
// GET AVATAR PATH
// ############################################################################

function GetDefaultAvatarImage($imageWidth=0,$imageHeight=0)
{
  global $mainsettings;
  if(empty($imageWidth) || !is_numeric($imageWidth) || empty($imageHeight) || !is_numeric($imageHeight))
  {
    $width  = empty($mainsettings['default_avatar_width'])  ? 60 : (int)$mainsettings['default_avatar_width'];
    $height = empty($mainsettings['default_avatar_height']) ? 60 : (int)$mainsettings['default_avatar_height'];
  }
  else
  {
    $width  = (int)$imageWidth;
    $height = (int)$imageHeight;
  }
  return '<img class="avatar" alt=" " width="'.$width.'" height="'.$height.'" src="includes/images/default_avatar.png" />';
}


function GetAvatarPath($user_email = '', $user_id = 0, $overrideWidth=0)
{
  // SD313: if gravatars are disabled or no user identified, display default avatar
  global $DB, $mainsettings, $sdurl, $database, $usersystem;

  $default_avatar = $sdurl.'includes/images/default_avatar.png';

  if(empty($mainsettings['enable_gravatars']))
  {
    return $default_avatar;
  }

  $user_id = Is_Valid_Number($user_id,0,1,9999999);

  if(empty($user_email))
  {
    if(!empty($user_id) && ($usersystem['name']=='Subdreamer'))
    {
      $user_arr = $DB->query_first('SELECT email FROM {users} WHERE userid = %d',$user_id);
      if(!empty($user_arr['email']))
      {
        $user_email = $user_arr['email'];
        unset($user_arr);
      }
      else
      {
        return $default_avatar;
      }
    }
    else
    {
      return $default_avatar;
    }
  }

  $defWidth = Is_Valid_Number($mainsettings['default_avatar_width'],40,1,512);
  $width = !empty($overrideWidth) && is_numeric($overrideWidth) ? $overrideWidth : $defWidth;
  $width = Is_Valid_Number($width,40,1,512);

  //SD343: support secure images to avoid different content messages
  global $mainsettings_sslurl;
  if($sdurl == $mainsettings_sslurl)
    $url = 'https://secure.gravatar.com';
  else
    $url = 'http://www.gravatar.com';
  $gravatar_url = $url . '/avatar/' . md5(strtolower($user_email)) .
                  '?default=' . urlencode($default_avatar) . '&amp;size='.(int)$width;

  return $gravatar_url;

} //GetAvatarPath


// ############################################################################
// GET VAR
// ############################################################################
// if $type == string, and $variable strlen == 0, then $variable will resort to $default_value value

function GetVar($var_name, $default_value = '', $type = 'string', $UsePost = true, $UseGet = true)
{
  static $valid_types_arr = false;

  if(!$valid_types_arr)
  {
    $valid_types_arr = array(
      'whole_number'   => 1, // 1,2,3...
      'bool'           => 2, // 0,1,true,false (values true/false become 1/0)
      'float'          => 3, // -2.4,-3,0,3,4.50 (decimal)
      'html'           => 4, // html, be very careful when allowing html
      'int'            => 5, // -2,-1,0,1,2...
      'integer'        => 6, // same as int
      'natural_number' => 7, // 0,1,2,3...
      'null'           => 8,
      'object'         => 9,
      'string'         => 10, // non-html string
      'array'          => 11,
      'array_keys'     => 12, //SD343
      'a_int'          => 13, //SD344: array of "int" values only
      'a_whole'        => 14, //SD344: array of "whole_number" values only
      'a_natural'      => 15, //SD344: array of "natural_number" values only
    );
  }

  if(!empty($UseGet))
  {
    $result = isset($_GET["$var_name"]) ? $_GET["$var_name"] : null;
  }

  if(empty($result) && !empty($UsePost))
  {
    $result = isset($_POST["$var_name"]) ? $_POST["$var_name"] : null;
  }

  if(!isset($result))
  {
    return $default_value;
  }

  if(!isset($valid_types_arr[$type]))
  {
    $type = 'string';
  }
  // SD 313: improved "int" checking
  if(($type == 'int') || ($type == 'integer'))
  {
    if(!is_numeric($result))
    {
      $result = $default_value;
    }
    else
    {
      $result = (int)$result;
    }
  }
  else if( ($type == 'natural_number') || ($type == 'whole_number') )
  {
    // don't use intval (doesn't support long numbers)
    // integer range is -2147483648 to 2147483647

    // allow only numbers (0-9) for natural numbers
    // allow only numbers (1-9) for whole numbers
    if(is_array($result) || !is_numeric($result) ||
       (($result == 0) && ($type == 'whole_number')))
    {
      $result = $default_value;
    }
    else
    {
      $result = intval(substr($result,0,11)); //SD343: substr 11 chars max
    }
  }
  else if($type == 'bool')
  {
    if( ($result == '0') || (strcasecmp($result, 'false') == 0)  || (strcasecmp($result, 'no') == 0) )
    {
      $result = 0;
    }
    else
    if( ($result == '1') || (strcasecmp($result, 'true') == 0) ||
        (strcasecmp($result, 'yes') == 0)  || (strcasecmp($result, 'on') == 0) )
    {
      $result = 1;
    }
    else
    {
      $result = $default_value;
    }
  }
  else if($type == 'float')
  {
    // Get rid of any thousand separators (commas), ex: 1,503.40 becomes 1503.40
    // Because within SQL a comma is a special character, you can't save a
    // numeric variable containing a comma, only decimal separator "." is allowed.
    $result = str_replace(',', '', $result);
    $result = (float)$result;
  }
  else if($type == 'string')
  {
    if(!is_string($result))
    {
      $result = $default_value;
    }
  }
  else if($type == 'html')
  {
    $result = sd_unhtmlspecialchars($result);
  }
  else if($type == 'array')
  {
    if(!is_array($result)) $result = $default_value;
  }
  else if($type == 'a_int') //SD344
  {
    if(!is_array($result))
      $result = $default_value;
    else
      $result = array_map('intval', $result);
  }
  else if(($type == 'a_whole') || ($type == 'a_natural')) //SD344
  {
    if(!is_array($result))
    {
      $result = $default_value;
    }
    else
    {
      $err = false;
      foreach($result as $key => $val)
      {
        if(isset($val) &&
           (!ctype_digit((string)$val) || (($type == 'a_whole') && empty($val))))
        {
          $err = true; break;
        }
      }
      if($err) $result = $default_value;
    }
  }
  else if($type == 'array_keys') //SD343: new: only keys are filled
  {
    if(!is_array($result))
      $result = $default_value;
    else
    {
      $err = false;
      foreach($result as $val)
      {
        if(!is_numeric($val) || !ctype_digit((string)$val))
        {
          $err = true; break;
        }
      }
      if($err) $result = $default_value;
    }
  }
  else
  {
    @settype($result, $type);
  }

  return $result;

} //GetVar


// ############################################################################
// sd_gzip (from http://php.net/manual/en/function.gzcompress.php)
// ############################################################################
function sd_gzip($data = "", $level = 6, $filename = "", $comments = "")
{
  $flags = (empty($comment)? 0 : 16) + (empty($filename)? 0 : 8);
  $mtime = time();

  return (pack("C1C1C1C1VC1C1", 0x1f, 0x8b, 8, $flags, $mtime, 2, 0xFF) .
              (empty($filename) ? "" : $filename . "\0") .
              (empty($comment) ? "" : $comment . "\0") .
              gzdeflate($data, $level) .
              pack("VV", crc32($data), sd_strlen($data)));
}


// ############################################################################

function sd_simplehtmlformat($input='')
{
  if(!isset($input) || (trim($input)=='')) return '';
  $input = preg_replace("#(<br[^>]*/>|</p>)[\r|\n]*#s","$1\n",$input);
  return preg_replace("/\r\n|\n\r|\n|\r/","\n",$input);
}


// ############################################################################
// IsValidEmail
// ############################################################################
// checks if given email is valid

function IsValidEmail($email_address)
{
  // SD313: new regular expression from:
  // http://squiloople.com/2009/12/20/email-address-validation/#more-1
  return (false !== strpos($email_address,'@')) && (false !== strpos($email_address,'.')) &&
         preg_match('/^(?!(?:\x22?(?:\x5C[\x00-\x7E]|[^\x22\x5C])\x22?){255,})(?!(?:\x22?(?:\x5C[\x00-\x7E]|[^\x22\x5C])\x22?){65,}@)(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|\x5C[\x00-\x7F])*\x22))(?:\.(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|\x5C[\x00-\x7F])*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){0,126}(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])(?:\.(?:25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])){3}))\]))$/i', $email_address);

} //IsValidEmail


// ############################################################################
// IIF
// ############################################################################

if((defined('IN_ADMIN') || (substr($usersystem['name'],0,9) != 'vBulletin')) &&
   !function_exists('iif'))
{
  function iif($expression, $returntrue, $returnfalse = '')
  {
    return $expression ? $returntrue : $returnfalse;
  }
}


// ############################################################################
// Is_Valid_Number
// ############################################################################
/* SD313
Returns $num_value if it is numeric and is between (including) the $min and
$max value boundaries; $min is defaulted to 1 if NULL; $max is only used if
it is not NULL.
If the $num_values is valid, it is returned, otherwise $default.
Example: $myint = Is_Valid_Number($input, 0, 1, 255);
*/
function Is_Valid_Number($num_value, $default=0, $min=0, $max=PHP_INT_MAX)
{
  $min = isset($min) ? (int)$min : 1;
  $max = isset($max) ? (int)$max : PHP_INT_MAX;

  if(isset($num_value))
  {
    //SD370: convert to int first so that min/max are checked
    if(is_string($num_value) && is_numeric($num_value))
    {
      $num_value = intval($num_value);
    }
    if( (!isset($min) || ($num_value >= $min)) &&
        (!isset($max) || ($num_value <= $max)) &&
        (!is_string($num_value) && is_int($num_value)) )
    {
      return intval($num_value);
    }
  }
  return intval($default);

} //Is_Valid_Number


// ############################################################################
// GET TIMEZONE SELECT TAG
// ############################################################################

// SD313: returns "timezone" select output (used in e.g. PrintPluginSettings)
function GetTimezoneSelect($select_name, $default_value, $id_attr='', $class_attr='')
{
  global $DB;
  $tz_arr = array();
  if(!$gettz = $DB->query("SELECT varname, IFNULL(customphrase,'') customphrase, defaultphrase
                           FROM {adminphrases} WHERE varname LIKE 'timezone_gmt%%'")) return '';
  while($tz = $DB->fetch_array($gettz,null,MYSQL_ASSOC))
  {
    $tz_arr[$tz['varname']] = !empty($tz['customphrase']) ? $tz['customphrase'] : $tz['defaultphrase'];
  }
  return '
      <select class="form-control" name="'.$select_name.'"'.
        (!empty($id_attr)?' id="'.$id_attr.'"':'').
        (!empty($class_attr)?' class="'.$class_attr.'"':'').
        '>
        <option value="-12"  '.($default_value=="-12" ? ' selected="selected"'  : '') .'>'.$tz_arr['timezone_gmt_m12'].'</option>
        <option value="-11"  '.($default_value=="-11" ?  ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_m11'].'</option>
        <option value="-10"  '.($default_value=="-10" ?  ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_m10'].'</option>
        <option value="-9"   '.($default_value=="-9" ?   ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_m9'] .'</option>
        <option value="-8"   '.($default_value=="-8" ?   ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_m8'] .'</option>
        <option value="-7"   '.($default_value=="-7" ?   ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_m7'] .'</option>
        <option value="-6"   '.($default_value=="-6" ?   ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_m6'] .'</option>
        <option value="-5"   '.($default_value=="-5" ?   ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_m5'] .'</option>
        <option value="-4"   '.($default_value=="-4" ?   ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_m4'] .'</option>
        <option value="-3.5" '.($default_value=="-3.5" ? ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_m35'].'</option>
        <option value="-3"   '.($default_value=="-3" ?   ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_m3'] .'</option>
        <option value="-2"   '.($default_value=="-2" ?   ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_m2'] .'</option>
        <option value="-1"   '.($default_value=="-1" ?   ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_m1'] .'</option>
        <option value="0"    '.($default_value=="0" ?    ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_0']  .'</option>
        <option value="1"    '.($default_value=="1" ?    ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_p1'] .'</option>
        <option value="2"    '.($default_value=="2" ?    ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_p2'] .'</option>
        <option value="3"    '.($default_value=="3" ?    ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_p3'] .'</option>
        <option value="3.5"  '.($default_value=="3.5" ?  ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_p35'].'</option>
        <option value="4"    '.($default_value=="4" ?    ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_p4'] .'</option>
        <option value="4.5"  '.($default_value=="4.5" ?  ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_p45'].'</option>
        <option value="5"    '.($default_value=="5" ?    ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_p5'] .'</option>
        <option value="5.5"  '.($default_value=="5.5" ?  ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_p55'].'</option>
        <option value="6"    '.($default_value=="6" ?    ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_p6'] .'</option>
        <option value="7"    '.($default_value=="7" ?    ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_p7'] .'</option>
        <option value="8"    '.($default_value=="8" ?    ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_p8'] .'</option>
        <option value="9"    '.($default_value=="9" ?    ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_p9'] .'</option>
        <option value="9.5"  '.($default_value=="9.5" ?  ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_p95'].'</option>
        <option value="10"   '.($default_value=="10" ?   ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_p10'].'</option>
        <option value="11"   '.($default_value=="11" ?   ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_p11'].'</option>
        <option value="12"   '.($default_value=="12" ?   ' selected="selected"' : '') .'>'.$tz_arr['timezone_gmt_p12'].'</option>
      </select>
      ';
} //GetTimezoneSelect


// ############################################################################
// Is_Ajax_Request
// ############################################################################
function Is_Ajax_Request()
{
  return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));

} //Is_Ajax_Request


// ############################################################################
// GetRatingsHandlingJS
// ############################################################################

function GetRatingsHandlingJS($inScriptTags=true)
{
  // Called from "index.php" to add below jQuery code to the HEAD of the page.
  // This JS will enable click-functionality to rating links/images.
  $JS = '';
  if(!empty($inScriptTags))
  {
    $JS = '
<script type="text/javascript">
//<![CDATA[
if(typeof(jQuery) !== "undefined"){
jQuery(document).ready(function(){';
  }
  $JS .= '
  jQuery("div.score a").click(function() {
    jQuery(this).parent().parent().parent().addClass("rating_scored");
    jQuery.get("'.SITE_URL.'includes/rating.php" + jQuery(this).attr("href") + "&update=1", {}, function(data){
      jQuery("div.rating_scored").fadeOut("normal",function() {
        jQuery(this).html(data);
        jQuery(this).fadeIn().removeClass("rating_scored");
      });
    });
    return false;
  });';

  if(!empty($inScriptTags))
  {
    $JS .= '
});
}
//]]>
</script>
';
  }

  return $JS;

} //GetRatingsHandlingJS


// ############################################################################
// GetRatingForm
// ############################################################################
/*
This function displays the ajax-driven 5-star rating elements.
(styled by Skin CSS "rating"). Rating *requires* jQuery (JavaScript enabled).

It MUST be called from within a SD plugin for each item that may be rated.

As first parameter it MUST be passed a valid "rating_id".
The actual rating action happens by Ajax-request, transmitting the (unique)
rating id and the clicked score (5 stars == 1 to 5).

The "rating_id" MUST have this specific format: pX-Y
with the following meanings:
"p" : single lowercase "p" character at beginning (required)
"X" : digits-only plugin ID (min. length 1 digit)
"-" : single dash as separator
"Y" : plugin-specific numeric identifier (min. length 1 digit)

Example: p67-123

The "Y" should be a plugin-specific number, such as an article id,
image id, file id, post id or similar so that a plugin can identify
ratings in the ratings table.

Any clearance of ratings has to be taken care of within the plugin
itself (except when plugin is uninstalled)!
*/

function GetRatingForm($rating_id, $pluginid = 0, $displayLabel=true)
{
  global $DB, $sdlanguage, $userinfo;

  $output = '';

  $ip = USERIP; // defined in "session.php" with every pageload

  // If no rating id is specified, assume that it's an Ajax call
  if(Is_Ajax_Request() && !isset($rating_id) && isset($_GET['rating_id']))
  {
    $rating_id = $_GET['rating_id'];
  }

  // Create rating object and bail out in case the rating id is invalid
  $rating = new rating($rating_id, $pluginid);
  if(!$rating->is_valid()) return false;
  $user_voted = $rating->already_voted();

  $pluginid_str = empty($pluginid) || !Is_Valid_Number($pluginid,0,2) ? '' : '&amp;pluginid='.(int)$pluginid;

  $status = '<div class="score">
        <a class="score1" rel="nofollow" title="'.$sdlanguage['rating_poor'].'" href="?score=1&amp;rating_id='.$rating_id.$pluginid_str.'">1</a>
        <a class="score2" rel="nofollow" title="'.$sdlanguage['rating_fair'].'" href="?score=2&amp;rating_id='.$rating_id.$pluginid_str.'">2</a>
        <a class="score3" rel="nofollow" title="'.$sdlanguage['rating_average'].'" href="?score=3&amp;rating_id='.$rating_id.$pluginid_str.'">3</a>
        <a class="score4" rel="nofollow" title="'.$sdlanguage['rating_good'].'" href="?score=4&amp;rating_id='.$rating_id.$pluginid_str.'">4</a>
        <a class="score5" rel="nofollow" title="'.$sdlanguage['rating_excellent'].'" href="?score=5&amp;rating_id='.$rating_id.$pluginid_str.'">5</a>
      </div>
  ';

  // Guests, banned users are not allowed to vote; also do not double-vote.
  if($user_voted)
  {
    $status = $sdlanguage['rating_already_rated'];
  }
  else
  if(!empty($userinfo['banned']) || empty($userinfo['loggedin']))
  {
    $status = $sdlanguage['rating_login_to_vote'];
    if(defined('LOGIN_PATH') && sd_strlen(LOGIN_PATH))
    {
      $status = '<a href="'.LOGIN_PATH.'">'.$status.'</a>';
    }
  }
  else
  if(isset($_GET['score']))
  {
    $score = Is_Valid_Number($_GET['score'], 0, 1, 5);
    if($score && ($rating_id == $_GET['rating_id']))
    {
      $rating->set_rating($score, $ip);
      $status = $rating->status;
    }
  }

  if(!Is_Ajax_Request() || !isset($_GET['update']))
  {
    $output .= '
    <div class="rating_wrapper">';
  }

  $output .= '<div class="sp_rating">'.
    ($displayLabel ? '<div class="rating">'.$sdlanguage['rating_vote'].'</div>' : '').'
    <div class="base"><div class="average" style="width: '.(int)$rating->average.'%">'.($rating->average/20).'</div></div>
    <div class="votes">'.
    ($rating->votes==0 ? $sdlanguage['rating_no_votes'] : $rating->votes.' '.($rating->votes==1 ? $sdlanguage['rating_vote'] : $sdlanguage['rating_votes'])).
    '</div>
    <div id="rating-status-'.$rating->rating_id.'" class="rating_status">'.$status.'</div>
  </div>
  ';

  if(!Is_Ajax_Request() || !isset($_GET['update']))
  {
    $output .= '</div>';

    if(empty($userinfo['banned']) && !empty($userinfo['loggedin']) && !$user_voted)
    {
      $output .= '
<script type="text/javascript">
//<![CDATA[
if(typeof(jQuery) !== "undefined"){
jQuery(document).ready(function() {
  jQuery("div#rating-status-'.$rating->rating_id.'").prepend("<div id=\"score_this_'.$rating->rating_id.'\" class=\"rating_score_this\"><a href=\"#\"> '.$sdlanguage['rating_rate_this'].'<\/a><\/div>");
  jQuery("div.rating_score_this").click(function(){ jQuery(this).slideUp(); return false; });
});
}
//]]>
</script>
';
    }

  }
  unset($rating);

  return $output;

} //GetRatingForm


// ############################################################################
// RedirectFrontPage
// ############################################################################

function RedirectFrontPage($new_page, $message = '', $delay_in_seconds = 2, $IsError=false)
{
  global $sdlanguage;

  if(is_array($message) && !empty($message))
  {
    $message = implode('<br />', $message);
  }
  if(isset($message) && ($message == 'EMPTY'))
  {
    $message = '';
  }
  else
  if(!empty($message) && !sd_strlen($message))
  {
    $message = $sdlanguage['settings_updated'].'<br />';
  }
  if(empty($new_page))
  {
    $new_page = RewriteLink();
  }
  $message .= '<br /><br /><a href="' . $new_page . '" onclick="javascript:clearTimeout(timerID);">'.
              $sdlanguage['message_redirect'].'</a>';

  DisplayMessage($message, $IsError);

  AddTimeoutJS($delay_in_seconds, $new_page);

} //RedirectFrontPage


// ############################################################################
// SD313: remove leading and trailing BR tags from $content:
function RemoveBRtags($content)
{
  return preg_replace('#(\A[\s]*<br[^>]*>[\s]*|'              // remove <br /> from beginning
                      .'<br[^>]*>[\s]*\Z)#is', '', $content); // remove <br /> from end
}


function sd_cache_article($pluginid=2,$articleid, $regenerate=false) //SD342
{
  global $DB, $SDCache, $usersystem;

  if(empty($pluginid) || !is_numeric($pluginid) || empty($articleid) || !is_numeric($articleid) ||
     !(($pluginid == 2) || (($pluginid >= 5000) && ($pluginid <= 9999))))
  {
    return false;
  }
  $pluginid = (int)$pluginid;

  //if(!isset($DB->table_names_arr[$usersystem['dbname']][PRGM_TABLE_PREFIX.'p'.$pluginid.'_news'])) return false;

  // First try to read from cache unless $regenerate is true
  $cache_id = CACHE_ARTICLE.'-'.$articleid.'-'.$pluginid;
  if(empty($regenerate) && ($article_arr = $SDCache->read_var($cache_id, 'article_arr')))
  {
    $article_arr['pluginid'] = (int)$pluginid;
    return $article_arr;
  }

  $DB->ignore_error = true;
  $prev_type = $DB->result_type;
  $DB->result_type = MYSQL_ASSOC;
  $article_arr = $DB->query_first('SELECT * FROM {p'.$pluginid.'_news} where articleid = %d',$articleid);
  $DB->result_type = $prev_type;
  $DB->ignore_error = false;

  if(!empty($article_arr))
  {
    $article_arr['pluginid'] = (int)$pluginid;
    $SDCache->write_var($cache_id, 'article_arr', $article_arr, false);
  }
  return is_array($article_arr) ? $article_arr : false;

} //sd_cache_article


function sd_cache_articles($pluginid=2, $cache_all=false)
{
  global $DB, $SDCache, $usersystem;

  // Regenerate cached mapping file (containing only seo title and ID!)
  // For performance reasons this does not regenerate each article cache file.

  if(empty($pluginid) || !is_numeric($pluginid) || ($pluginid < 2) || ($pluginid > 9999)) return false;
  //if(!isset($DB->table_names_arr[$usersystem['dbname']][PRGM_TABLE_PREFIX.'p'.$pluginid.'_news'])) return false;

  $pluginid = (int)$pluginid;
  $seo_cacheid = CACHE_ARTICLES_SEO2IDS.'-'.(int)$pluginid;

  // First try to read from cache - if not all articles are to be cached
  if(empty($cache_all) && $SDCache->CacheExistsForID($seo_cacheid))
  {
    if($article_seo_arr = $SDCache->read_var($seo_cacheid, CACHE_ARTICLES_SEO2IDS))
    {
      return $article_seo_arr;
    }
  }

  $fieldlist = empty($cache_all) ? 'articleid, seo_title' : '*';
  $DB->ignore_error = true;
  $getarticles = $DB->query('SELECT '.$fieldlist.' FROM {p'.$pluginid.'_news} ORDER BY articleid');
  $DB->ignore_error = false;

  if(!empty($getarticles))
  {
    $article_seo_arr = array();
    while($row = $DB->fetch_array($getarticles,null,MYSQL_ASSOC))
    {
      if(sd_strlen(trim($row['seo_title'])))
      {
        $article_seo_arr[$row['seo_title']] = $row['articleid'];
      }
      if(!empty($cache_all))
      {
        $SDCache->write_var(CACHE_ARTICLE.'-'.$articleid.'-'.$pluginid, 'article_arr', $row, false);
      }
    }

    // Sort and rewrite article seo cache file
    @ksort($article_seo_arr);
    $SDCache->write_var($seo_cacheid, CACHE_ARTICLES_SEO2IDS, $article_seo_arr, false);
    return $article_seo_arr;
  }
  return false;
} //sd_cache_articles

// ########################## GetArticleLink ###################################

function GetArticleLink($categoryid, $pluginid, $article_arr,
                        $articlebitfield,
                        $isTagPage=false, $plugin_folder='')
{
  //SD360: outsourced from articles plugin; caches unique pages internally
  global $mainsettings_modrewrite, $mainsettings_url_extension, $sdurl;

  static $pcache = array();

  if(empty($article_arr['articleid'])) return '';
  $aid = (int)$article_arr['articleid'];

  $article_link = '';
  if($mainsettings_modrewrite && sd_strlen($article_arr['seo_title']))
  {
    // Hmm... omit "home" page's seo name *if* article is on "home" page???
    #if($categoryid == $categoryids[0])
    #{
    #  $article_link  = $sdurl . $article_arr['seo_title'] .
    #                   $mainsettings_url_extension;
    #}
    #else
    {
      if(!$isTagPage && !empty($articlebitfield) &&
         ($article_arr['settings'] & $articlebitfield['displayaspopup'])) //SD342 new "popup"
      {
        $article_link = $sdurl.'plugins/'.$plugin_folder.'/popup.php?articleid='.$aid;
      }
      else
      {
        if(defined('IN_ADMIN') && !isset($GLOBALS['categoryid']))
        {
          $GLOBALS['categoryid'] = $categoryid;
        }
        //SD343: new option to always open the article on "main" article page, not secondary page
        $useCategoryID = $categoryid;
        if($isTagPage || (!empty($articlebitfield) && ($article_arr['settings'] & $articlebitfield['linktomainpage']))) //SD343
          $useCategoryID = $article_arr['categoryid'];
        if(isset($pcache[$useCategoryID]))
          $article_link = $pcache[$useCategoryID];
        else
        {
          $article_link = RewriteLink('index.php?categoryid='.$useCategoryID);
          $pcache[$useCategoryID] = $article_link;
        }
        $article_link = preg_replace('#'.preg_quote($mainsettings_url_extension,'#').'$#', '/' .
                                     $article_arr['seo_title'] .
                                     $mainsettings_url_extension, $article_link);
      }
    }
  }
  else
  {
    $suffix = '&p'.$pluginid.'_articleid='.$aid;
    if($isTagPage || (!empty($articlebitfield) && ($article_arr['settings'] & $articlebitfield['linktomainpage']))) //SD343
      $useCategoryID = $article_arr['categoryid'];
    else
      $useCategoryID = $categoryid;
    if(isset($pcache[$useCategoryID]))
      $article_link = $pcache[$useCategoryID];
    else
    {
      $article_link = RewriteLink('index.php?categoryid='.$useCategoryID);
      $pcache[$useCategoryID] = $article_link;
    }
    $article_link .= $suffix;
  }
  if($pluginid > 2)
    $article_link .= (strpos($article_link,'?')===false?'?':'&amp;').'pid='.$pluginid;
  return $article_link;

} //GetArticleLink


// ############################################################################
// Parse a SD setting into a full HTML SELECT tag incl. phrases
// ############################################################################

function sd_ParseToSelect($input, $value, $phrase_ident, $tag_name, $phrases, $style=null)
// From a simple set of lines build a full "SELECT" HTML code with
// trying to match option phrases to e.g. plugin phrase (in $phrases) which
// has as phrases ["select_" + $phrase_ident + underscore + value] (all lowercased!)
// as the key with $phrase_ident being e.g. a plugin setting name.
// For phrase matching any value's character not being a latin letter or digit
// will be treated as a "_" (underscore) character!
//
// Full example: main setting "siteactivation" looks like this (3 lines):
// select:
// on|On
// off|Off
// For phrases it will look for "select_siteactivation_on" and
// "select_siteactivation_off" as it has values "on" and "off".
//
// $input - example 1: "select:\r\n0|Site - Category (default)\r\n1|Category - Site"
// $input - example 2: "select-multi:\r\npages|Pages\r\nplugins|Plugins"
// $value - current value of the SELECT tag
//          for example 1: "0" or "1"
//          for example 2: "pages,plugins" or "pages" or "plugins" or empty
// $phrase_ident - used to get "option" phrases, e.g. "title_order" to result in
//                 for example 1: "select_title_order_0" and "select_title_order_1"
// $tag_name - unique tag name for SELECT for use in a form
// $pluginphrases - should contain e.g. plugin phrases = GetLanguage($pluginid)
{
  $result = '';
  // first line in $input is either "select:" or "select:multi":
  $is_multi = substr($input,0,13) == 'select-multi:';
  $cutoff = $is_multi ? 13 : 7;
  // $value may contain none, one or multiple values (separated by comma):
  $value = $is_multi ? explode(',', $value) : $value;
  // normalize line breaks in $input
  $input = preg_replace("/(\r\n|\n\r|\r|\n)+/", "\r", trim($input));
  // split up individual lines into an array:
  $select_arr = preg_split('/\r/', substr($input, $cutoff), -1, PREG_SPLIT_NO_EMPTY);
  if(!empty($select_arr))
  {
    // $phrase_ident is the uniqe phrase name
    $phrase_ident = preg_replace('/[^0-9a-zA-Z_]/', '_', $phrase_ident);
    $result = '<select class="form-control" name="'.$tag_name.($is_multi?'[]" multiple="multiple" size="5"':'"').
              (isset($style) ? ' style="'.$style.'"':'').'>';
    foreach($select_arr AS $sel_entry)
    {
      // each line contains pipe-delimited "value" and "phrase" per option
      // try to get a translated value from $phrases, which could be plugin/admin phrases etc.
      @list($sel_value, $sel_phrase) = explode('|', $sel_entry);
      $sel_value = strtolower($sel_value);
      $phrase_id = 'select_'.strtolower($phrase_ident.'_'.$sel_value);
      $sel_phrase = isset($phrases[$phrase_id]) ? $phrases[$phrase_id] : $sel_phrase;
      $result .= '<option value="'.$sel_value.'" ';
      if($is_multi)
      {
        $result .= (in_array($sel_value, $value) ? 'selected="selected"' : '');
      }
      else
      {
        $result .= ($value==$sel_value ? 'selected="selected"' : '');
      }
      $result .= '>'.$sel_phrase."</option>\r\n";
    }
    $result .= '</select>';
  }
  return $result;

} //sd_ParseToSelect


function sd_PrintPagesizeSelect($input, $value, $size=5)
{
  $size = Is_Valid_Number($size, 5, 1);
  $value = empty($value) ? 'LETTER' : strtoupper($value);
  echo '
<select class="form-control" id="'.$input.'" name="'.$input.'" size="'.$size.'">
<option value="" style="background-color: #c0c0c0; font-weight: bold;">ISO 216 A Series + 2 SIS 014711 extensions</option>
<option value="A0" '.($value=='A0'?'selected="selected"':'').'>A0 (841x1189 mm ; 33.11x46.81 in)</option>
<option value="A1" '.($value=='A1'?'selected="selected"':'').'>A1 (594x841 mm ; 23.39x33.11 in)</option>
<option value="A2" '.($value=='A2'?'selected="selected"':'').'>A2 (420x594 mm ; 16.54x23.39 in)</option>
<option value="A3" '.($value=='A3'?'selected="selected"':'').'>A3 (297x420 mm ; 11.69x16.54 in)</option>
<option value="A4" '.($value=='A4'?'selected="selected"':'').'>A4 (210x297 mm ; 8.27x11.69 in)</option>
<option value="A5" '.($value=='A5'?'selected="selected"':'').'>A5 (148x210 mm ; 5.83x8.27 in)</option>
<option value="A6" '.($value=='A6'?'selected="selected"':'').'>A6 (105x148 mm ; 4.13x5.83 in)</option>
<option value="A7" '.($value=='A7'?'selected="selected"':'').'>A7 (74x105 mm ; 2.91x4.13 in)</option>
<option value="A8" '.($value=='A8'?'selected="selected"':'').'>A8 (52x74 mm ; 2.05x2.91 in)</option>
<option value="A9" '.($value=='A9'?'selected="selected"':'').'>A9 (37x52 mm ; 1.46x2.05 in)</option>
<option value="A10" '.($value=='A10'?'selected="selected"':'').'>A10 (26x37 mm ; 1.02x1.46 in)</option>
<option value="A11" '.($value=='A11'?'selected="selected"':'').'>A11 (18x26 mm ; 0.71x1.02 in)</option>
<option value="A12" '.($value=='A12'?'selected="selected"':'').'>A12 (13x18 mm ; 0.51x0.71 in)</option>
<option value="" style="background-color: #c0c0c0; font-weight: bold;">ISO 216 B Series + 2 SIS 014711 extensions</option>
<option value="B0" '.($value=='B0'?'selected="selected"':'').'>B0 (1000x1414 mm ; 39.37x55.67 in)</option>
<option value="B1" '.($value=='B1'?'selected="selected"':'').'>B1 (707x1000 mm ; 27.83x39.37 in)</option>
<option value="B2" '.($value=='B2'?'selected="selected"':'').'>B2 (500x707 mm ; 19.69x27.83 in)</option>
<option value="B3" '.($value=='B3'?'selected="selected"':'').'>B3 (353x500 mm ; 13.90x19.69 in)</option>
<option value="B4" '.($value=='B4'?'selected="selected"':'').'>B4 (250x353 mm ; 9.84x13.90 in)</option>
<option value="B5" '.($value=='B5'?'selected="selected"':'').'>B5 (176x250 mm ; 6.93x9.84 in)</option>
<option value="B6" '.($value=='B6'?'selected="selected"':'').'>B6 (125x176 mm ; 4.92x6.93 in)</option>
<option value="B7" '.($value=='B7'?'selected="selected"':'').'>B7 (88x125 mm ; 3.46x4.92 in)</option>
<option value="B8" '.($value=='B8'?'selected="selected"':'').'>B8 (62x88 mm ; 2.44x3.46 in)</option>
<option value="B9" '.($value=='B9'?'selected="selected"':'').'>B9 (44x62 mm ; 1.73x2.44 in)</option>
<option value="B10" '.($value=='B10'?'selected="selected"':'').'>B10 (31x44 mm ; 1.22x1.73 in)</option>
<option value="B11" '.($value=='B11'?'selected="selected"':'').'>B11 (22x31 mm ; 0.87x1.22 in)</option>
<option value="B12" '.($value=='B12'?'selected="selected"':'').'>B12 (15x22 mm ; 0.59x0.87 in)</option>
<option value="" style="background-color: #c0c0c0; font-weight: bold;">ISO 216 C Series + 2 SIS 014711 extensions + 2 EXTENSION</option>
<option value="C0" '.($value=='C0'?'selected="selected"':'').'>C0 (917x1297 mm ; 36.10x51.06 in)</option>
<option value="C1" '.($value=='C1'?'selected="selected"':'').'>C1 (648x917 mm ; 25.51x36.10 in)</option>
<option value="C2" '.($value=='C2'?'selected="selected"':'').'>C2 (458x648 mm ; 18.03x25.51 in)</option>
<option value="C3" '.($value=='C3'?'selected="selected"':'').'>C3 (324x458 mm ; 12.76x18.03 in)</option>
<option value="C4" '.($value=='C4'?'selected="selected"':'').'>C4 (229x324 mm ; 9.02x12.76 in)</option>
<option value="C5" '.($value=='C5'?'selected="selected"':'').'>C5 (162x229 mm ; 6.38x9.02 in)</option>
<option value="C6" '.($value=='C6'?'selected="selected"':'').'>C6 (114x162 mm ; 4.49x6.38 in)</option>
<option value="C7" '.($value=='C7'?'selected="selected"':'').'>C7 (81x114 mm ; 3.19x4.49 in)</option>
<option value="C8" '.($value=='C8'?'selected="selected"':'').'>C8 (57x81 mm ; 2.24x3.19 in)</option>
<option value="C9" '.($value=='C9'?'selected="selected"':'').'>C9 (40x57 mm ; 1.57x2.24 in)</option>
<option value="C10" '.($value=='C10'?'selected="selected"':'').'>C10 (28x40 mm ; 1.10x1.57 in)</option>
<option value="C11" '.($value=='C11'?'selected="selected"':'').'>C11 (20x28 mm ; 0.79x1.10 in)</option>
<option value="C12" '.($value=='C12'?'selected="selected"':'').'>C12 (14x20 mm ; 0.55x0.79 in)</option>
<option value="C76" '.($value=='C76'?'selected="selected"':'').'>C76 (81x162 mm ; 3.19x6.38 in)</option>
<option value="DL" '.($value=='DL'?'selected="selected"':'').'>DL (110x220 mm ; 4.33x8.66 in)</option>
<option value="" style="background-color: #c0c0c0; font-weight: bold;">SIS 014711 E Series</option>
<option value="E0" '.($value=='E0'?'selected="selected"':'').'>E0 (879x1241 mm ; 34.61x48.86 in)</option>
<option value="E1" '.($value=='E1'?'selected="selected"':'').'>E1 (620x879 mm ; 24.41x34.61 in)</option>
<option value="E2" '.($value=='E2'?'selected="selected"':'').'>E2 (440x620 mm ; 17.32x24.41 in)</option>
<option value="E3" '.($value=='E3'?'selected="selected"':'').'>E3 (310x440 mm ; 12.20x17.32 in)</option>
<option value="E4" '.($value=='E4'?'selected="selected"':'').'>E4 (220x310 mm ; 8.66x12.20 in)</option>
<option value="E5" '.($value=='E5'?'selected="selected"':'').'>E5 (155x220 mm ; 6.10x8.66 in)</option>
<option value="E6" '.($value=='E6'?'selected="selected"':'').'>E6 (110x155 mm ; 4.33x6.10 in)</option>
<option value="E7" '.($value=='E7'?'selected="selected"':'').'>E7 (78x110 mm ; 3.07x4.33 in)</option>
<option value="E8" '.($value=='E8'?'selected="selected"':'').'>E8 (55x78 mm ; 2.17x3.07 in)</option>
<option value="E9" '.($value=='E9'?'selected="selected"':'').'>E9 (39x55 mm ; 1.54x2.17 in)</option>
<option value="E10" '.($value=='E10'?'selected="selected"':'').'>E10 (27x39 mm ; 1.06x1.54 in)</option>
<option value="E11" '.($value=='E11'?'selected="selected"':'').'>E11 (19x27 mm ; 0.75x1.06 in)</option>
<option value="E12" '.($value=='E12'?'selected="selected"':'').'>E12 (13x19 mm ; 0.51x0.75 in)</option>
<option value="" style="background-color: #c0c0c0; font-weight: bold;">SIS 014711 G Series</option>
<option value="G0" '.($value=='G0'?'selected="selected"':'').'>G0 (958x1354 mm ; 37.72x53.31 in)</option>
<option value="G1" '.($value=='G1'?'selected="selected"':'').'>G1 (677x958 mm ; 26.65x37.72 in)</option>
<option value="G2" '.($value=='G2'?'selected="selected"':'').'>G2 (479x677 mm ; 18.86x26.65 in)</option>
<option value="G3" '.($value=='G3'?'selected="selected"':'').'>G3 (338x479 mm ; 13.31x18.86 in)</option>
<option value="G4" '.($value=='G4'?'selected="selected"':'').'>G4 (239x338 mm ; 9.41x13.31 in)</option>
<option value="G5" '.($value=='G5'?'selected="selected"':'').'>G5 (169x239 mm ; 6.65x9.41 in)</option>
<option value="G6" '.($value=='G6'?'selected="selected"':'').'>G6 (119x169 mm ; 4.69x6.65 in)</option>
<option value="G7" '.($value=='G7'?'selected="selected"':'').'>G7 (84x119 mm ; 3.31x4.69 in)</option>
<option value="G8" '.($value=='G8'?'selected="selected"':'').'>G8 (59x84 mm ; 2.32x3.31 in)</option>
<option value="G9" '.($value=='G9'?'selected="selected"':'').'>G9 (42x59 mm ; 1.65x2.32 in)</option>
<option value="G10" '.($value=='G10'?'selected="selected"':'').'>G10 (29x42 mm ; 1.14x1.65 in)</option>
<option value="G11" '.($value=='G11'?'selected="selected"':'').'>G11 (21x29 mm ; 0.83x1.14 in)</option>
<option value="G12" '.($value=='G12'?'selected="selected"':'').'>G12 (14x21 mm ; 0.55x0.83 in)</option>
<option value="" style="background-color: #c0c0c0; font-weight: bold;">ISO Press</option>
<option value="RA0" '.($value=='RA0'?'selected="selected"':'').'>RA0 (860x1220 mm ; 33.86x48.03 in)</option>
<option value="RA1" '.($value=='RA1'?'selected="selected"':'').'>RA1 (610x860 mm ; 24.02x33.86 in)</option>
<option value="RA2" '.($value=='RA2'?'selected="selected"':'').'>RA2 (430x610 mm ; 16.93x24.02 in)</option>
<option value="RA3" '.($value=='RA3'?'selected="selected"':'').'>RA3 (305x430 mm ; 12.01x16.93 in)</option>
<option value="RA4" '.($value=='RA4'?'selected="selected"':'').'>RA4 (215x305 mm ; 8.46x12.01 in)</option>
<option value="SRA0" '.($value=='SRA0'?'selected="selected"':'').'>SRA0 (900x1280 mm ; 35.43x50.39 in)</option>
<option value="SRA1" '.($value=='SRA1'?'selected="selected"':'').'>SRA1 (640x900 mm ; 25.20x35.43 in)</option>
<option value="SRA2" '.($value=='SRA2'?'selected="selected"':'').'>SRA2 (450x640 mm ; 17.72x25.20 in)</option>
<option value="SRA3" '.($value=='SRA3'?'selected="selected"':'').'>SRA3 (320x450 mm ; 12.60x17.72 in)</option>
<option value="SRA4" '.($value=='SRA4'?'selected="selected"':'').'>SRA4 (225x320 mm ; 8.86x12.60 in)</option>
<option value="" style="background-color: #c0c0c0; font-weight: bold;">German DIN 476</option>
<option value="4A0" '.($value=='4A0'?'selected="selected"':'').'>4A0 (1682x2378 mm ; 66.22x93.62 in)</option>
<option value="2A0" '.($value=='2A0'?'selected="selected"':'').'>2A0 (1189x1682 mm ; 46.81x66.22 in)</option>
<option value="" style="background-color: #c0c0c0; font-weight: bold;">Variations on the ISO Standard</option>
<option value="A2_EXTRA" '.($value=='A2_EXTRA'?'selected="selected"':'').'>A2_EXTRA (445x619 mm ; 17.52x24.37 in)</option>
<option value="A3PLUS" '.($value=='A3PLUS'?'selected="selected"':'').'>A3+ (329x483 mm ; 12.95x19.02 in)</option>
<option value="A3_EXTRA" '.($value=='A3_EXTRA'?'selected="selected"':'').'>A3_EXTRA (322x445 mm ; 12.68x17.52 in)</option>
<option value="A3_SUPER" '.($value=='A3_SUPER'?'selected="selected"':'').'>A3_SUPER (305x508 mm ; 12.01x20.00 in)</option>
<option value="SUPER_A3" '.($value=='SUPER_A3'?'selected="selected"':'').'>SUPER_A3 (305x487 mm ; 12.01x19.17 in)</option>
<option value="A4_EXTRA" '.($value=='A4_EXTRA'?'selected="selected"':'').'>A4_EXTRA (235x322 mm ; 9.25x12.68 in)</option>
<option value="A4_SUPER" '.($value=='A4_SUPER'?'selected="selected"':'').'>A4_SUPER (229x322 mm ; 9.02x12.68 in)</option>
<option value="SUPER_A4" '.($value=='SUPER_A4'?'selected="selected"':'').'>SUPER_A4 (227x356 mm ; 8.94x14.02 in)</option>
<option value="A4_LONG" '.($value=='A4_LONG'?'selected="selected"':'').'>A4_LONG (210x348 mm ; 8.27x13.70 in)</option>
<option value="F4" '.($value=='F4'?'selected="selected"':'').'>F4 (210x330 mm ; 8.27x12.99 in)</option>
<option value="SO_B5_EXTRA" '.($value=='SO_B5_EXTRA'?'selected="selected"':'').'>SO_B5_EXTRA (202x276 mm ; 7.95x10.87 in)</option>
<option value="A5_EXTRA" '.($value=='A5_EXTRA'?'selected="selected"':'').'>A5_EXTRA (173x235 mm ; 6.81x9.25 in)</option>
<option value="" style="background-color: #c0c0c0; font-weight: bold;">ANSI Series</option>
<option value="ANSI_E" '.($value=='ANSI_E'?'selected="selected"':'').'>ANSI_E (864x1118 mm ; 34.00x44.00 in)</option>
<option value="ANSI_D" '.($value=='ANSI_D'?'selected="selected"':'').'>ANSI_D (559x864 mm ; 22.00x34.00 in)</option>
<option value="ANSI_C" '.($value=='ANSI_C'?'selected="selected"':'').'>ANSI_C (432x559 mm ; 17.00x22.00 in)</option>
<option value="ANSI_B" '.($value=='ANSI_B'?'selected="selected"':'').'>ANSI_B (279x432 mm ; 11.00x17.00 in)</option>
<option value="ANSI_A" '.($value=='ANSI_A'?'selected="selected"':'').'>ANSI_A (216x279 mm ; 8.50x11.00 in)</option>
<option value="" style="background-color: #c0c0c0; font-weight: bold;">Traditional "Loose" North American Paper Sizes</option>
<option value="LEDGER" '.($value=='LEDGER'?'selected="selected"':'').'>LEDGER, USLEDGER (432x279 mm ; 17.00x11.00 in)</option>
<option value="TABLOID" '.($value=='TABLOID'?'selected="selected"':'').'>TABLOID, USTABLOID, BIBLE, ORGANIZERK (279x432 mm ; 11.00x17.00 in)</option>
<option value="LETTER" '.($value=='LETTER'?'selected="selected"':'').'>LETTER, USLETTER, ORGANIZERM (216x279 mm ; 8.50x11.00 in)</option>
<option value="LEGAL" '.($value=='LEGAL'?'selected="selected"':'').'>LEGAL, USLEGAL (216x356 mm ; 8.50x14.00 in)</option>
<option value="GLETTER" '.($value=='GLETTER'?'selected="selected"':'').'>GLETTER, GOVERNMENTLETTER (203x267 mm ; 8.00x10.50 in)</option>
<option value="JLEGAL" '.($value=='JLEGAL'?'selected="selected"':'').'>JLEGAL, JUNIORLEGAL (203x127 mm ; 8.00x5.00 in)</option>
<option value="" style="background-color: #c0c0c0; font-weight: bold;">Other North American Paper Sizes</option>
<option value="QUADDEMY" '.($value=='QUADDEMY'?'selected="selected"':'').'>QUADDEMY (889x1143 mm ; 35.00x45.00 in)</option>
<option value="SUPER_B" '.($value=='SUPER_B'?'selected="selected"':'').'>SUPER_B (330x483 mm ; 13.00x19.00 in)</option>
<option value="QUARTO" '.($value=='QUARTO'?'selected="selected"':'').'>QUARTO (229x279 mm ; 9.00x11.00 in)</option>
<option value="FOLIO" '.($value=='FOLIO'?'selected="selected"':'').'>FOLIO, GOVERNMENTLEGAL (216x330 mm ; 8.50x13.00 in)</option>
<option value="EXECUTIVE" '.($value=='EXECUTIVE'?'selected="selected"':'').'>EXECUTIVE, MONARCH (184x267 mm ; 7.25x10.50 in)</option>
<option value="MEMO" '.($value=='MEMO'?'selected="selected"':'').'>MEMO, STATEMENT, ORGANIZERL (140x216 mm ; 5.50x8.50 in)</option>
<option value="FOOLSCAP" '.($value=='FOOLSCAP'?'selected="selected"':'').'>FOOLSCAP (210x330 mm ; 8.27x13.00 in)</option>
<option value="COMPACT" '.($value=='COMPACT'?'selected="selected"':'').'>COMPACT (108x171 mm ; 4.25x6.75 in)</option>
<option value="ORGANIZERJ" '.($value=='ORGANIZERJ'?'selected="selected"':'').'>ORGANIZERJ (70x127 mm ; 2.75x5.00 in)</option>
<option value="" style="background-color: #c0c0c0; font-weight: bold;">Canadian standard CAN 2-9.60M</option>
<option value="P1" '.($value=='P1'?'selected="selected"':'').'>P1 (560x860 mm ; 22.05x33.86 in)</option>
<option value="P2" '.($value=='P2'?'selected="selected"':'').'>P2 (430x560 mm ; 16.93x22.05 in)</option>
<option value="P3" '.($value=='P3'?'selected="selected"':'').'>P3 (280x430 mm ; 11.02x16.93 in)</option>
<option value="P4" '.($value=='P4'?'selected="selected"':'').'>P4 (215x280 mm ; 8.46x11.02 in)</option>
<option value="P5" '.($value=='P5'?'selected="selected"':'').'>P5 (140x215 mm ; 5.51x8.46 in)</option>
<option value="P6" '.($value=='P6'?'selected="selected"':'').'>P6 (107x140 mm ; 4.21x5.51 in)</option>
<option value="" style="background-color: #c0c0c0; font-weight: bold;">North American Architectural Sizes</option>
<option value="ARCH_E" '.($value=='ARCH_E'?'selected="selected"':'').'>ARCH_E (914x1219 mm ; 36.00x48.00 in)</option>
<option value="ARCH_E1" '.($value=='ARCH_E1'?'selected="selected"':'').'>ARCH_E1 (762x1067 mm ; 30.00x42.00 in)</option>
<option value="ARCH_D" '.($value=='ARCH_D'?'selected="selected"':'').'>ARCH_D (610x914 mm ; 24.00x36.00 in)</option>
<option value="ARCH_C" '.($value=='ARCH_C'?'selected="selected"':'').'>ARCH C, BROADSHEET (457x610 mm ; 18.00x24.00 in)</option>
<option value="ARCH_B" '.($value=='ARCH_B'?'selected="selected"':'').'>ARCH_B (305x457 mm ; 12.00x18.00 in)</option>
<option value="ARCH_A" '.($value=='ARCH_A'?'selected="selected"':'').'>ARCH_A (229x305 mm ; 9.00x12.00 in)</option>
<option value="" style="background-color: #c0c0c0; font-weight: bold;">Announcement Envelopes</option>
<option value="ANNENV_A2" '.($value=='ANNENV_A2'?'selected="selected"':'').'>ANNENV_A2 (111x146 mm ; 4.37x5.75 in)</option>
<option value="ANNENV_A6" '.($value=='ANNENV_A6'?'selected="selected"':'').'>ANNENV_A6 (121x165 mm ; 4.75x6.50 in)</option>
<option value="ANNENV_A7" '.($value=='ANNENV_A7'?'selected="selected"':'').'>ANNENV_A7 (133x184 mm ; 5.25x7.25 in)</option>
<option value="ANNENV_A8" '.($value=='ANNENV_A8'?'selected="selected"':'').'>ANNENV_A8 (140x206 mm ; 5.50x8.12 in)</option>
<option value="ANNENV_A10" '.($value=='ANNENV_A10'?'selected="selected"':'').'>ANNENV_A10 (159x244 mm ; 6.25x9.62 in)</option>
<option value="ANNENV_SLIM" '.($value=='ANNENV_SLIM'?'selected="selected"':'').'>ANNENV_SLIM (98x225 mm ; 3.87x8.87 in)</option>
<option value="" style="background-color: #c0c0c0; font-weight: bold;">Commercial Envelopes</option>
<option value="COMMENV_N6_1/4" '.($value=='COMMENV_N6_1/4'?'selected="selected"':'').'>COMMENV_N6_1/4 (89x152 mm ; 3.50x6.00 in)</option>
<option value="COMMENV_N6_3/4" '.($value=='COMMENV_N6_3/4'?'selected="selected"':'').'>COMMENV_N6_3/4 (92x165 mm ; 3.62x6.50 in)</option>
<option value="COMMENV_N8" '.($value=='COMMENV_N8'?'selected="selected"':'').'>COMMENV_N8 (98x191 mm ; 3.87x7.50 in)</option>
<option value="COMMENV_N9" '.($value=='COMMENV_N9'?'selected="selected"':'').'>COMMENV_N9 (98x225 mm ; 3.87x8.87 in)</option>
<option value="COMMENV_N10" '.($value=='COMMENV_N10'?'selected="selected"':'').'>COMMENV_N10 (105x241 mm ; 4.12x9.50 in)</option>
<option value="COMMENV_N11" '.($value=='COMMENV_N11'?'selected="selected"':'').'>COMMENV_N11 (114x263 mm ; 4.50x10.37 in)</option>
<option value="COMMENV_N12" '.($value=='COMMENV_N12'?'selected="selected"':'').'>COMMENV_N12 (121x279 mm ; 4.75x11.00 in)</option>
<option value="COMMENV_N14" '.($value=='COMMENV_N14'?'selected="selected"':'').'>COMMENV_N14 (127x292 mm ; 5.00x11.50 in)</option>
<option value="" style="background-color: #c0c0c0; font-weight: bold;">Catalogue Envelopes</option>
<option value="CATENV_N1" '.($value=='CATENV_N1'?'selected="selected"':'').'>CATENV_N1 (152x229 mm ; 6.00x9.00 in)</option>
<option value="CATENV_N1_3/4" '.($value=='CATENV_N1_3/4'?'selected="selected"':'').'>CATENV_N1_3/4 (165x241 mm ; 6.50x9.50 in)</option>
<option value="CATENV_N2" '.($value=='CATENV_N2'?'selected="selected"':'').'>CATENV_N2 (165x254 mm ; 6.50x10.00 in)</option>
<option value="CATENV_N3" '.($value=='CATENV_N3'?'selected="selected"':'').'>CATENV_N3 (178x254 mm ; 7.00x10.00 in)</option>
<option value="CATENV_N6" '.($value=='CATENV_N6'?'selected="selected"':'').'>CATENV_N6 (191x267 mm ; 7.50x10.50 in)</option>
<option value="CATENV_N7" '.($value=='CATENV_N7'?'selected="selected"':'').'>CATENV_N7 (203x279 mm ; 8.00x11.00 in)</option>
<option value="CATENV_N8" '.($value=='CATENV_N8'?'selected="selected"':'').'>CATENV_N8 (210x286 mm ; 8.25x11.25 in)</option>
<option value="CATENV_N9_1/2" '.($value=='CATENV_N9_1/2'?'selected="selected"':'').'>CATENV_N9_1/2 (216x267 mm ; 8.50x10.50 in)</option>
<option value="CATENV_N9_3/4" '.($value=='CATENV_N9_3/4'?'selected="selected"':'').'>CATENV_N9_3/4 (222x286 mm ; 8.75x11.25 in)</option>
<option value="CATENV_N10_1/2" '.($value=='CATENV_N10_1/2'?'selected="selected"':'').'>CATENV_N10_1/2 (229x305 mm ; 9.00x12.00 in)</option>
<option value="CATENV_N12_1/2" '.($value=='CATENV_N12_1/2'?'selected="selected"':'').'>CATENV_N12_1/2 (241x318 mm ; 9.50x12.50 in)</option>
<option value="CATENV_N13_1/2" '.($value=='CATENV_N13_1/2'?'selected="selected"':'').'>CATENV_N13_1/2 (254x330 mm ; 10.00x13.00 in)</option>
<option value="CATENV_N14_1/4" '.($value=='CATENV_N14_1/4'?'selected="selected"':'').'>CATENV_N14_1/4 (286x311 mm ; 11.25x12.25 in)</option>
<option value="CATENV_N14_1/2" '.($value=='CATENV_N14_1/2'?'selected="selected"':'').'>CATENV_N14_1/2 (292x368 mm ; 11.50x14.50 in)</option>
<option value="" style="background-color: #c0c0c0; font-weight: bold;">Japanese (JIS P 0138-61) Standard B-Series</option>
<option value="JIS_B0" '.($value=='JIS_B0'?'selected="selected"':'').'>JIS_B0 (1030x1456 mm ; 40.55x57.32 in)</option>
<option value="JIS_B1" '.($value=='JIS_B1'?'selected="selected"':'').'>JIS_B1 (728x1030 mm ; 28.66x40.55 in)</option>
<option value="JIS_B2" '.($value=='JIS_B2'?'selected="selected"':'').'>JIS_B2 (515x728 mm ; 20.28x28.66 in)</option>
<option value="JIS_B3" '.($value=='JIS_B3'?'selected="selected"':'').'>JIS_B3 (364x515 mm ; 14.33x20.28 in)</option>
<option value="JIS_B4" '.($value=='JIS_B4'?'selected="selected"':'').'>JIS_B4 (257x364 mm ; 10.12x14.33 in)</option>
<option value="JIS_B5" '.($value=='JIS_B5'?'selected="selected"':'').'>JIS_B5 (182x257 mm ; 7.17x10.12 in)</option>
<option value="JIS_B6" '.($value=='JIS_B6'?'selected="selected"':'').'>JIS_B6 (128x182 mm ; 5.04x7.17 in)</option>
<option value="JIS_B7" '.($value=='JIS_B7'?'selected="selected"':'').'>JIS_B7 (91x128 mm ; 3.58x5.04 in)</option>
<option value="JIS_B8" '.($value=='JIS_B8'?'selected="selected"':'').'>JIS_B8 (64x91 mm ; 2.52x3.58 in)</option>
<option value="JIS_B9" '.($value=='JIS_B9'?'selected="selected"':'').'>JIS_B9 (45x64 mm ; 1.77x2.52 in)</option>
<option value="JIS_B10" '.($value=='JIS_B10'?'selected="selected"':'').'>JIS_B10 (32x45 mm ; 1.26x1.77 in)</option>
<option value="JIS_B11" '.($value=='JIS_B11'?'selected="selected"':'').'>JIS_B11 (22x32 mm ; 0.87x1.26 in)</option>
<option value="JIS_B12" '.($value=='JIS_B12'?'selected="selected"':'').'>JIS_B12 (16x22 mm ; 0.63x0.87 in)</option>
<option value="" style="background-color: #c0c0c0; font-weight: bold;">Standard Photographic Print Sizes</option>
<option value="PASSPORT_PHOTO" '.($value=='PASSPORT_PHOTO'?'selected="selected"':'').'>PASSPORT_PHOTO (35x45 mm ; 1.38x1.77 in)</option>
<option value="E" '.($value=='E'?'selected="selected"':'').'>E (82x120 mm ; 3.25x4.72 in)</option>
<option value="3R" '.($value=='3R'?'selected="selected"':'').'>3R, L (89x127 mm ; 3.50x5.00 in)</option>
<option value="4R" '.($value=='4R'?'selected="selected"':'').'>4R, KG (102x152 mm ; 4.02x5.98 in)</option>
<option value="4D" '.($value=='4D'?'selected="selected"':'').'>4D (120x152 mm ; 4.72x5.98 in)</option>
<option value="5R" '.($value=='5R'?'selected="selected"':'').'>5R, 2L (127x178 mm ; 5.00x7.01 in)</option>
<option value="6R" '.($value=='6R'?'selected="selected"':'').'>6R, 8P (152x203 mm ; 5.98x7.99 in)</option>
<option value="8R" '.($value=='8R'?'selected="selected"':'').'>8R, 6P (203x254 mm ; 7.99x10.00 in)</option>
<option value="S8R" '.($value=='S8R'?'selected="selected"':'').'>S8R, 6PW (203x305 mm ; 7.99x12.01 in)</option>
<option value="10R" '.($value=='10R'?'selected="selected"':'').'>10R, 4P (254x305 mm ; 10.00x12.01 in)</option>
<option value="S10R" '.($value=='S10R'?'selected="selected"':'').'>S10R, 4PW (254x381 mm ; 10.00x15.00 in)</option>
<option value="11R" '.($value=='11R'?'selected="selected"':'').'>11R (279x356 mm ; 10.98x14.02 in)</option>
<option value="S11R" '.($value=='S11R'?'selected="selected"':'').'>S11R (279x432 mm ; 10.98x17.01 in)</option>
<option value="12R" '.($value=='12R'?'selected="selected"':'').'>12R (305x381 mm ; 12.01x15.00 in)</option>
<option value="S12R" '.($value=='S12R'?'selected="selected"':'').'>S12R (305x456 mm ; 12.01x17.95 in)</option>
<option value="" style="background-color: #c0c0c0; font-weight: bold;">Common Newspaper Sizes</option>
<option value="NEWSPAPER_BROADSHEET" '.($value=='NEWSPAPER_BROADSHEET'?'selected="selected"':'').'>NEWSPAPER_BROADSHEET (750x600 mm ; 29.53x23.62 in)</option>
<option value="NEWSPAPER_BERLINER" '.($value=='NEWSPAPER_BERLINER'?'selected="selected"':'').'>NEWSPAPER_BERLINER (470x315 mm ; 18.50x12.40 in)</option>
<option value="NEWSPAPER_COMPACT" '.($value=='NEWSPAPER_COMPACT'?'selected="selected"':'').'>NEWSPAPER_COMPACT, NEWSPAPER_TABLOID (430x280 mm ; 16.93x11.02 in)</option>
<option value="" style="background-color: #c0c0c0; font-weight: bold;">Business Cards</option>
<option value="CREDIT_CARD" '.($value=='CREDIT_CARD'?'selected="selected"':'').'>CREDIT_CARD, BUSINESS_CARD, BUSINESS_CARD_ISO7810 (54x86 mm ; 2.13x3.37 in)</option>
<option value="BUSINESS_CARD_ISO216" '.($value=='BUSINESS_CARD_ISO216'?'selected="selected"':'').'>BUSINESS_CARD_ISO216 (52x74 mm ; 2.05x2.91 in)</option>
<option value="BUSINESS_CARD_IT" '.($value=='BUSINESS_CARD_IT'?'selected="selected"':'').'>BUSINESS_CARD_IT/UK/FR/DE/ES (55x85 mm ; 2.17x3.35 in)</option>
<option value="BUSINESS_CARD_US" '.($value=='BUSINESS_CARD_US'?'selected="selected"':'').'>BUSINESS_CARD_US, BUSINESS_CARD_CA (51x89 mm ; 2.01x3.50 in)</option>
<option value="BUSINESS_CARD_JP" '.($value=='BUSINESS_CARD_JP'?'selected="selected"':'').'>BUSINESS_CARD_JP (55x91 mm ; 2.17x3.58 in)</option>
<option value="BUSINESS_CARD_HK" '.($value=='BUSINESS_CARD_HK'?'selected="selected"':'').'>BUSINESS_CARD_HK (54x90 mm ; 2.13x3.54 in)</option>
<option value="BUSINESS_CARD_AU" '.($value=='BUSINESS_CARD_AU'?'selected="selected"':'').'>BUSINESS_CARD_AU/DK/SE (55x90 mm ; 2.17x3.54 in)</option>
<option value="BUSINESS_CARD_RU" '.($value=='BUSINESS_CARD_RU'?'selected="selected"':'').'>BUSINESS_CARD_RU/CZ/FI/HU/IL (50x90 mm ; 1.97x3.54 in)</option>
</select>
';

} //sd_PrintPagesizeSelect


function sd_GetUserrow($userid, $extrafields=null)
{
  global $DB, $usersystem, $database;

  if(empty($userid) || !is_numeric($userid)) return false;

  $prevdb = $DB->database;
  $systemname = $usersystem['name'];
  $prefix = $usersystem['tblprefix'];
  $in = ' = '.(int)$userid;
  $prev_result_type = $DB->result_type;
  $DB->result_type = MYSQL_ASSOC;
  $DB->ignore_error = true;

  // switch to usersystem database
  if($DB->database != $usersystem['dbname']) $DB->select_db($usersystem['dbname']);

  if($systemname == 'Subdreamer')
  {
    $row = $DB->query_first('SELECT * FROM {users} WHERE userid '.$in);
  }
  else if( substr($systemname,0,9) == 'vBulletin' )
  {
    $row = $DB->query_first('SELECT * FROM '.$prefix.'user WHERE userid '.$in);
  }
  else if( substr($systemname,0,5) == 'phpBB' )
  {
    $row = $DB->query_first('SELECT u.user_id userid, u.* FROM '.$prefix.
                            "users u WHERE u.username != 'Anonymous' AND u.user_id $in");
  }
  else if( $systemname == 'Invision Power Board 2' )
  {
    $row = $DB->query_first('SELECT u.id userid, u.name username, u.* FROM ' . $prefix .
                            'members u WHERE u.id '.$in);
  }
  else if( $systemname == 'Invision Power Board 3' ) //SD342 "member_id", not "id"
  {
    $row = $DB->query_first('SELECT u.member_id userid, u.name username, u.* FROM ' . $prefix .
                            'members u WHERE u.id '.$in);
  }
  else if($systemname == 'Simple Machines Forum 1')
  {
    $row = $DB->query_first('SELECT u.ID_MEMBER userid, u.memberName username, u.* FROM ' . $prefix .
                            'members u WHERE u.ID_MEMBER '.$in);
  }
  else if($systemname == 'Simple Machines Forum 2')
  {
    $row = $DB->query_first('SELECT u.id_member userid, u.member_name username, u.* FROM ' . $prefix .
                            'members u WHERE u.id_member '.$in);
  }
  else if($systemname == 'XenForo 1')
  {
    $row = $DB->query_first('SELECT u.user_id userid, u.* FROM ' . $prefix .
                            'user u WHERE u.user_id '.$in);
  }
  else if($systemname == 'MyBB') //SD370
  {
    $row = $DB->query_first('SELECT u.uid userid, u.* FROM ' . $prefix .
                            'users u WHERE u.uid '.$in);
  }
  else if($systemname == 'punBB') //SD370
  {
    $row = $DB->query_first('SELECT u.id userid, u.* FROM ' . $prefix .
                            'users u WHERE u.id '.$in);
  }
  else
  {
    $DB->result_type = $prev_result_type;
    $DB->ignore_error = false;
    if($DB->database != $prevdb) $DB->select_db($prevdb);
    return false;
  }

  // Fetch extra user data now:
  if(isset($extrafields) && is_string($extrafields))
  {
    $extrafields = array((string)$extrafields);
  }
  if(!empty($row) && is_array($extrafields) && !empty($extrafields))
  {
    if($DB->database != $database['name']) $DB->select_db($database['name']);
    $DB->result_type = MYSQL_ASSOC;
    if($userdata = $DB->query_first('SELECT * FROM '.PRGM_TABLE_PREFIX.'users_data'.
                                    ' WHERE usersystemid = %d AND userid '.$in.' LIMIT 1',
                                    $usersystem['usersystemid']))
    {
      foreach($extrafields as $field)
      {
        $row[$field] = isset($userdata[$field]) ? $userdata[$field] : null;
      }
    }
  }

  $DB->result_type = $prev_result_type;
  $DB->ignore_error = false;
  if($DB->database != $prevdb) $DB->select_db($prevdb);

  if(!empty($row))
  {
    unset($row['password'],$row['salt']);
  }
  return $row;

} //sd_GetUserrow


function sd_GetUserSelection($formId, $usersElementName, $selectedUserid=array(), $classname='recipients_list')
{
  global $DB, $usersystem;

  $prevdb = $DB->database;
  $systemname = $usersystem['name'];
  $prefix = $usersystem['tblprefix'];
  $prev_result_type = $DB->result_type;
  $DB->result_type = MYSQL_ASSOC;

  // switch to usersystem database
  if($DB->database != $usersystem['dbname']) $DB->select_db($usersystem['dbname']);

  $in = '= 0';
  $selectedUserid = empty($selectedUserid) ? array(0) : (is_array($selectedUserid) ? $selectedUserid : array($selectedUserid));
  if($selectedUserid_text = implode(',',$selectedUserid))
  {
    $in = ' IN ('. $selectedUserid_text . ') ';
  }

  if($systemname == 'Subdreamer')
  {
    $rows = $DB->query("SELECT userid, username FROM {users} WHERE userid $in ORDER BY username");
  }
  else if( substr($systemname,0,9) == 'vBulletin' )
  {
    $rows = $DB->query('SELECT userid, username FROM '.$prefix."user WHERE userid $in ORDER BY username");
  }
  else if( substr($systemname,0,5) == 'phpBB' )
  {
    $rows = $DB->query('SELECT user_id as userid, username FROM '.$prefix.
                           "users WHERE username != 'Anonymous' AND user_id $in ORDER BY username");
  }
  else if( substr($systemname,0,20) == 'Invision Power Board' )
  {
    $rows = $DB->query('SELECT id as userid, name as username FROM ' . $prefix . "members WHERE id $in ORDER BY name");
  }
  else if($systemname == 'Simple Machines Forum 1')
  {
    $rows = $DB->query('SELECT ID_MEMBER as userid, memberName as username FROM ' . $prefix .
                           "members WHERE ID_MEMBER $in ORDER BY memberName");
  }
  else if($systemname == 'Simple Machines Forum 2')
  {
    $rows = $DB->query('SELECT id_member as userid, member_name as username FROM ' . $prefix .
                           "members WHERE id_member $in ORDER BY member_name");
  }
  else if($systemname == 'XenForo 1')
  {
    //SD370: "as userid" was missing
    $rows = $DB->query('SELECT user_id as userid, username FROM ' . $prefix . "user WHERE user_id $in ORDER BY username");
  }
  else if($systemname == 'MyBB') //SD370
  {
    $rows = $DB->query('SELECT uid as userid, username FROM ' . $prefix . "users WHERE uid $in ORDER BY username");
  }
  else if($systemname == 'punBB') //SD370
  {
    $rows = $DB->query('SELECT id userid, username FROM ' . $prefix . "users WHERE id $in ORDER BY username");
  }
  $DB->result_type = $prev_result_type;

  $output = '<ul id="' . $formId . '" class="'.$classname.'">';

  if(!empty($rows))
  {
    while($user = $DB->fetch_array($rows,null,MYSQL_ASSOC))
    {
      if(is_array($selectedUserid) && in_array($user['userid'], $selectedUserid))
      {
        if($id = $user['userid'])
        $output .= '
        <li id="msg_recipient_'.$id.'"><div>'.$user['username'].'</div>
        <input type="hidden" name="'.$usersElementName.'[]" value="'.$id.'" />
        <img alt="[-]" class="list_deluser" rel="'.$id.'" src="'.SITE_URL.'includes/images/delete.png" height="16" width="16" />
        </li>';
      }
    }
    unset($rows,$user);
  }
  $output .= '</ul>';

  if($DB->database != $prevdb) $DB->select_db($prevdb);

  return $output;

} //sd_GetUserSelection


// ############################################################################
// Print Avatar depending on User System
// ############################################################################

function sd_PrintAvatar($settings, $imageOnly=false)
{
  global $DB;
  /* Example input array:
  $settings = array(
    'output_ok'           => true,
    'userid'              => 1,
    'username'            => 'some name',
    'Avatar Column'       => true/false,
    'Avatar Image Height' => 80,
    'Avatar Image Width'  => 80
    )
  */
  $avatar = '';
  if(function_exists('ForumAvatar') && !empty($settings['output_ok']))
  {
    $imageOnly = !empty($imageOnly);
    $prev_db = $DB->database;
    $avatar = ForumAvatar($settings['userid'], $settings['username']);
    if(empty($avatar))
    {
      $avatar = GetDefaultAvatarImage((int)$settings['Avatar Image Width'],(int)$settings['Avatar Image Height']);
    }
    $avatar = preg_replace("/\s?width=\"([0-9])*[px]*\"\s?/", ' ', $avatar);
    $avatar = preg_replace("/\s?height=\"([0-9])*[px]*\"\s?/", ' ', $avatar);
    $avatar = preg_replace("/\s?alt=\"\s*\"\s?/", ' ', $avatar);
    $avatar = preg_replace("/\s?class=\"avatar\"\s?/", ' ', $avatar);
    $avatar = str_replace('<img ','<img alt="" class="avatar" height="'.(int)$settings['Avatar Image Height'].'" width="'.(int)$settings['Avatar Image Width'].'" ', $avatar);
    if(!$imageOnly)
    {
      if(!empty($settings['Avatar Column']))
      {
        $avatar = '<td valign="top" style="padding:2px;" width="10%">'. $avatar .'</td><td valign="top" style="padding:2px;">';
      }
      else
      {
        $avatar = '<td valign="top" style="padding:2px;">'. $avatar.' ';
      }
    }
    // make sure to switch back to forum database (ForumAvatar switches)!
    if($DB->database != $prev_db) $DB->select_db($prev_db);
  }
  else
  {
    $avatar = '';//empty($imageOnly)?'<td valign="top" style="padding:2px;">':'';
  }

  return $avatar;

} //sd_PrintAvatar


/* ############################################################################
  This function is complementing the "ForumLink" functions in the forum
  integration files, as it will accept a username instead of an userid
  (sometimes only a username is stored without the original ID).
  For this it will resolve the name to an ID first and then return the actual
  "ForumLink" function's result. If no user was found, it returns empty string.
*/
function sd_ForumLinkByName($linkType, $username = '')
{
  if(isset($username) && sd_strlen($username))
  {
    $user = sd_GetForumUserInfo(0,$username); //SD342 fix: 1st param here 0
    if(!empty($user) && function_exists('ForumLink'))
    {
      return ForumLink($linkType, $user['userid']);
    }
  }

  return '';

} //sd_ForumLinkByName


//SD343: Returns an array of enabled blocklist providers, else false.
//Supported by e.g. Contact Form (6), Login Panel (10) and User Registration (12).
//Note: uses $mainsettings if plugin id is 1!
// References:
// http://en.wikipedia.org/wiki/DNSBL#Terminology
// http://spamlinks.net/filter-dnsbl-lists.htm
// http://www.sdsc.edu/~jeff/spam/Blacklists_Compared.html
function sd_get_banlists($pluginid=12, $settingid='enable_blocklist_checks')
{
  static $sd_all_banlists = array(
    1 => 'sbl.spamhaus.org',
    2 => 'zen.spamhaus.org',
    4 => 'multi.sburl.org',
    8 => 'bl.spamcop.net',
   16 => 'dnsbl.njabl.org',
   32 => 'dnsbl.sorbs.net',
  );

  $pluginid = Is_Valid_Number($pluginid,0,1,99999);
  if(empty($pluginid)) $pluginid = 12;
  if($pluginid==1)
  {
    global $mainsettings;
    $opt = empty($mainsettings[$settingid]) ? false : (string)$mainsettings[$settingid];
  }
  else
  {
    $settings = GetPluginSettings($pluginid);
    $opt = empty($settings[$settingid]) ? false : (string)$settings[$settingid];
  }
  if(empty($settingid) || empty($opt)) return false;

  $opt = sd_ConvertStrToArray($opt);

  $result = array();
  foreach($sd_all_banlists as $bit => $entry)
  {
    if(in_array($bit,$opt)) $result[] = $entry;
  }
  return empty($result) ? false : $result;

} //sd_get_banlists


/*SD343: function to check URL/IP against DNSBL
 * see also: http://www.surbl.org/lists
 * For URL checking it requires 2 files:
 *   http://www.surbl.org/tld/two-level-tlds
 *   http://www.surbl.org/tld/three-level-tlds
*/
function sd_reputation_check($url_or_ip_to_be_checked='', $pluginid=12, $settingid='enable_blocklist_checks')
{
  static $dontCheckIPs = array('127'=>1,'10'=>1,'192'=>1,'196'=>1);

  if(empty($url_or_ip_to_be_checked)) return false;

  // IF it is an IP, return false if a non-checkable IP
  if(@ip2long($url_or_ip_to_be_checked) !== false)
  {
    $parts = explode('.', $url_or_ip_to_be_checked);
    if(isset($dontCheckIPs[$parts[0]])) return false;
  }

  $check_list = sd_get_banlists($pluginid, $settingid);
  if(empty($check_list) || !is_array($check_list)) return false;

  $lists = array();
  foreach($check_list as $entry)
  {
    if((strpos($entry,'.') !== false) && (substr($entry,0,1)!='.'))
    {
      $lists[] = $entry;
    }
  }
  if(empty($lists)) return false;

  //see: http://www.phpclasses.org/package/6874-PHP-Check-if-a-given-address-is-in-a-DNS-blacklist.html
  // MIT licensed
  @include_once(SD_CLASS_PATH.'uri_reputation.php');
  if(!class_exists('URIReputation')) return false;
  $surbl = new URIReputation();

  $result = false;
  foreach($lists as $entry)
  {
    $surbl->set_list($entry);
    $result = ip2long($surbl->check_url($url_or_ip_to_be_checked));
    if($result) break;
  }
  if(!empty($result) && ($result > 0x7f000001) && ($result <= 0x7f00ffff))
  {
    // URL detected on 1 or more lists
    /*
    $found_on_sc_surbl_org = ($result & 2);
    $found_on_ws_surbl_org = ($result & 4);
    $found_on_ph_surbl_org = ($result & 8);
    $found_on_ob_surbl_org = ($result & 16);
    $found_on_ab_surbl_org = ($result & 32);
    $found_on_jp_surbl_org = ($result & 64);
    return $found_on_sc_surbl_org + $found_on_ws_surbl_org + $found_on_ph_surbl_org +
           $found_on_ob_surbl_org + $found_on_ab_surbl_org + $found_on_jp_surbl_org;
    */
    return $entry;
  }
  else
  {
    // URL not found on any lists
    return false;
  }
} //sd_reputation_check


//SD343: function to check URL/IP against DNSBL
// based on code here: http://php.net/manual/de/function.checkdnsrr.php
// Notes:
// * Only for IPv4 and NOT for Win platforms!
// * Above sd_reputation_check should be used!
function sd_check_ip_blacklists($ip)
{
  $on_win = substr(PHP_OS, 0, 3) == "WIN" ? 1 : 0;
  $cdr = function_exists("checkdnsrr");
  if(empty($ip) || (@ip2long($ip) === false)) return false;
  $parts = explode('.', $ip);
  if(in_array($parts[0],array('127','10','192','196'))) return false;

  {
    $dnsbl_lists = array('bl.spamcop.net', 'sbl.spamhaus.org', 'zen.spamhaus.org');
    $reverse_ip = implode('.', array_reverse($parts));
    foreach ($dnsbl_lists as $dnsbl_list)
    {
      if($cdr && !$on_win)
      {
        if(@checkdnsrr($reverse_ip . '.' . $dnsbl_list . '.', 'A'))
        {
          return $reverse_ip . '.' . $dnsbl_list;
        }
      }
      else
      if($on_win)
      {
        $lookup = '';
        @exec("nslookup -type=A " . $reverse_ip . "." . $dnsbl_list . ".", $lookup);
        foreach ($lookup as $line) {
            if (strstr($line, $dnsbl_list)) {
                return $reverse_ip . '.' . $dnsbl_list;
            }
        }
      }
    }
  }
  return false;
}

//SD343: function to check StopForumSpam against email/ip for being flagged
// Requires a valid API key! Visit: http://www.stopforumspam.com/forum
function sd_sfs_is_spam($email='', $ip_address='')
{
  if(//!empty($mainsettings['sfs_api_key']) &&
     empty($email) && empty($ip_address)) return false;

  $params = false;
  $check_email = false;
  $check_ip = false;
  if(!empty($ip_address) && (sd_strlen($ip_address) > 7))
  {
    $check_ip = true;
    $params .= '&ip='.urlencode($ip_address);
  }
  if(!empty($email) && (sd_strlen($email) > 4))
  {
    $check_email = true;
    $params .= '&email='.urlencode($email);
  }
  if(!$params) return false;

  static $loaded_extensions, $use_curl;
  if(!isset($loaded_extensions)) $loaded_extensions = get_loaded_extensions();
  if(!isset($use_curl))
    $use_curl = version_compare(PHP_VERSION,'5.1.3','ge') && !empty($loaded_extensions) &&
                in_array('curl', $loaded_extensions);

  $use_json = version_compare(PHP_VERSION,'5.2.0','ge') && function_exists('json_decode');
  $request_url = 'http://www.stopforumspam.com/api?f='.($use_json ? 'json' : 'serial').$params;

  if($use_curl)
  {
    $method = 1;
    if(($curl_handle=@curl_init())===false) return false;
    @curl_setopt($curl_handle, CURLOPT_URL, $request_url);
    @curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
    @curl_setopt($curl_handle, CURLOPT_TIMEOUT, 20);
    @curl_setopt($curl_handle, CURLOPT_FAILONERROR, 1);
    $result = @curl_exec($curl_handle);
    $curl_info = @curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
    @curl_close($curl_handle);
    if((empty($curl_info) || ($curl_info!='200')) ||
       (isset($result) && ($result===false))) return false;
  }
  else
  if($use_json && @ini_get('allow_url_fopen'))
  {
    $tmp = $GLOBALS['sd_ignore_watchdog'];
    $GLOBALS['sd_ignore_watchdog'] = true;
    $method = 2;
    $result = @file_get_contents($request_url);
    $GLOBALS['sd_ignore_watchdog'] = $tmp;
  }

  // Sanity checks:
  if(empty($method) || !isset($result) || empty($result)) return false;

  if($use_json)
  {
    $data = @json_decode($result);
    if(empty($data) || !is_object($data)) return false;
    foreach($data as $datapoint)
    {
      // not 'success' datapoint AND spammer
      if(!empty($datapoint->appears)) return true;
    }
  }
  else
  {
    if($data = @unserialize($result) !== false) return false;
    if(empty($data) || !is_array($data)) return false;
    if(empty($data['success'])) return false;
    if($check_ip && !empty($data['ip']['appears']))
    {
      // if timestamp available, check if not older than 60 days
      if(isset($data['lastseen']))
      {
        $date = strtotime($data['lastseen']);
        if(($date!==false) && ($date < strtotime("-2 months"))) return false;
      }
      return true;
    }
    if($check_email && !empty($data['email']['appears'])) return true;
  }

  return false;

} //sd_sfs_is_spam


// ############################################################################

function sd_GetForumUserInfo($searchby = 0, $searchvalue = '', $alldetails = false, $extrafields=null, $view_status=0)
{
/*
  SD322:
  - first param "searchby": 0 = username (default)
                            1 = userid
                            2 = email
  - "$alldetails" returns the full row, otherwise only userid and username
  - "$view_status" == 0 -> all "extrafields", regardless of public status or not
                   == 1 -> only public "extrafields"
*/

  global $DB, $database, $usersystem, $userinfo;

  // Is $usersystem valid?
  if(empty($usersystem))
  {
    return false;
  }
  $forumname = $usersystem['name'];

  switch($searchby)
  {
    case  1 : $searchbycolumn = 'useridcolumn'; break;
    case  2 : $searchbycolumn = 'useremailcolumn'; break;
    default : $searchbycolumn = 'usernamecolumn';
  }

  // initialise, not available in all forums
  $usergroupcolumn  = '';
  $userjoinedcolumn = '';
  $usergroupprefix  = 'forum';

  // Find user details depending on the integrated forum
  if($forumname == 'Subdreamer')
  {
    $usertable       = 'users';
    $useridcolumn    = 'userid';
    $useremailcolumn = 'email';
    $usernamecolumn  = 'username';
    $usergroupcolumn = 'usergroupid';
    $userjoinedcolumn = 'joindate';
    $usersystem['tblprefix'] = PRGM_TABLE_PREFIX;
    $usergroupprefix  = '';
  }
  // 'vBulletin 2' no longer supported
  elseif(substr($forumname,0,9) == 'vBulletin')
  {
    $usertable       = 'user';
    $useridcolumn    = 'userid';
    $useremailcolumn = 'email';
    $usernamecolumn  = 'username';
    $usergroupcolumn = 'usergroupid';
    $userjoinedcolumn = 'joindate';
  }
  elseif($forumname == 'phpBB2')
  {
    $usertable       = 'users';
    $useridcolumn    = 'user_id';
    $useremailcolumn = 'user_email AS email';
    $usernamecolumn  = 'username';
    $userjoinedcolumn = 'user_regdate AS joindate';
  }
  elseif($forumname == 'phpBB3')
  {
    $usertable       = 'users';
    $useridcolumn    = 'user_id';
    $useremailcolumn = 'user_email AS email';
    $usernamecolumn  = 'username';
    $usergroupcolumn = 'group_id AS usergroupid';
    $userjoinedcolumn = 'user_regdate AS joindate';
  }
  elseif($forumname == 'Invision Power Board 2')
  {
    $usertable       = 'members';
    $useridcolumn    = 'id';
    $useremailcolumn = 'email';
    $usernamecolumn  = 'name';
    $usergroupcolumn = 'mgroup AS usergroupid';
    $userjoinedcolumn = 'joined AS joindate';
  }
  elseif($forumname == 'Invision Power Board 3')
  {
    $usertable       = 'members';
    $useridcolumn    = 'member_id';
    $useremailcolumn = 'email';
    $usernamecolumn  = 'name';
    $usergroupcolumn = 'member_group_id AS usergroupid';
    $userjoinedcolumn = 'joined AS joindate';
  }
  elseif($forumname == 'Simple Machines Forum 1')
  {
    $usertable       = 'members';
    $useridcolumn    = 'ID_MEMBER';
    $useremailcolumn = 'emailAddress AS email';
    $usernamecolumn  = 'memberName';
    $usergroupcolumn = 'ID_GROUP AS usergroupid';
    $userjoinedcolumn = 'dateRegistered AS joindate';
  }
  elseif($forumname == 'Simple Machines Forum 2')
  {
    $usertable       = 'members';
    $useridcolumn    = 'id_member';
    $useremailcolumn = 'email_address AS email';
    $usernamecolumn  = 'member_name';
    $usergroupcolumn = 'id_group AS usergroupid';
    $userjoinedcolumn = 'date_registered AS joindate';
  }
  elseif($forumname == 'XenForo 1')
  {
    $usertable       = 'user';
    $useridcolumn    = 'user_id';
    $useremailcolumn = 'email';
    $usernamecolumn  = 'username';
    $usergroupcolumn = 'user_group_id AS usergroupid';
    $userjoinedcolumn = 'register_date AS joindate';
  }
  elseif($forumname == 'MyBB') //SD370
  {
    $usertable       = 'users';
    $useridcolumn    = 'uid';
    $useremailcolumn = 'email';
    $usernamecolumn  = 'username';
    $usergroupcolumn = 'usergroup AS usergroupid';
    $userjoinedcolumn = 'regdate AS joindate';
  }
  elseif($forumname == 'punBB') //SD370
  {
    $usertable       = 'users';
    $useridcolumn    = 'id';
    $useremailcolumn = 'email';
    $usernamecolumn  = 'username';
    $usergroupcolumn = 'group_id AS usergroupid';
    $userjoinedcolumn = 'registered AS joindate';
  }
  else
  {
    return false; // No valid usersystem identified, so return false
  }

  $user = false;
  $prevdb = $DB->database;
  $prev_result_type = $DB->result_type;
  $DB->ignore_error = true;
  if($DB->database != $usersystem['dbname']) $DB->select_db($usersystem['dbname']);

  $DB->result_type = MYSQL_ASSOC;
  //SD322 if "$alldetails" is true, return the full user row
  if(empty($alldetails))
  {
    $user = $DB->query_first(
            "SELECT u.$useridcolumn userid, u.$usernamecolumn, u.$useremailcolumn, u.$userjoinedcolumn" .
            (strlen($usergroupcolumn) ? ", u.$usergroupcolumn" : '').
            ' FROM ' . $usersystem['tblprefix'] . $usertable . " u
            WHERE u.%s = '%s'",
            ${$searchbycolumn}, $DB->escape_string($searchvalue));
  }
  else
  {
    $user = $DB->query_first(
            "SELECT u.$useridcolumn userid, u.$usernamecolumn, u.$useremailcolumn, u.$userjoinedcolumn" .
            (strlen($usergroupcolumn) ? ", u.$usergroupcolumn" : '').
            ', u.*
            FROM ' . $usersystem['tblprefix'] . $usertable . " u
            WHERE u.%s = '%s'",
            ${$searchbycolumn}, $DB->escape_string($searchvalue));
  }

  // SD342 Fetch usergroup info from first mapped usergroup
  if(!empty($user['usergroupid']))
  {
    $user['usergroup_details'] = array();
    $DB->result_type = MYSQL_ASSOC;
    if($DB->database != SD_DBNAME) $DB->select_db(SD_DBNAME);
    if($ug = $DB->query_first(
      'SELECT ug.name,ug.adminaccess,ug.banned,ug.displayname,ug.description,ug.color_online,ug.display_online,'.
      ' ugc.*'.
      ' FROM '.PRGM_TABLE_PREFIX.'usergroups ug'.
      ' LEFT JOIN '.PRGM_TABLE_PREFIX.'usergroups_config ugc ON ugc.usergroupid = ug.usergroupid'.
      ' WHERE ug.%s = %d ORDER BY ug.usergroupid LIMIT 1',
      $usergroupprefix.'usergroupid', $user['usergroupid']))
    {
      $user['usergroup_details'] = $ug;
      unset($ug);
    }
  }

  $user['userid'] = empty($user['userid'])?0:(int)$user['userid'];
  $user['public_fields'] = array();
  $user['usergroup_details']['allow_uname_change'] = empty($user['usergroup_details']['allow_uname_change'])?0:1; //SD370
  // Fetch extra user data now:
  if(!empty($user) && !empty($extrafields) && is_array($extrafields))
  {
    $DB->result_type = MYSQL_ASSOC;
    if($DB->database != SD_DBNAME) $DB->select_db(SD_DBNAME);
    //SD360: users_titles added; first check if available
    $tmp = '';
    if(in_array('users_titles', $DB->table_names_arr[$DB->database]))
    {
      $tmp = ', (SELECT title FROM '.PRGM_TABLE_PREFIX.'users_titles'.
             ' WHERE titleid = '.PRGM_TABLE_PREFIX.'users_data.user_titleid) user_title ';
    }
    if($userdata = $DB->query_first(
         'SELECT '.($alldetails?'*':('public_fields, '.implode(',',$extrafields))).
         $tmp.
         ' FROM '.PRGM_TABLE_PREFIX.'users_data'.
         ' WHERE usersystemid = %d AND userid = %d LIMIT 1',
         $usersystem['usersystemid'], $user['userid']))
    {
      // If status == 1 then ONLY include extra fields, that are fully publicly visible
      // TODO: later the status could have additional values, e.g.
      //   2 == Friends
      //   3 == Contacts/Buddies
      if(isset($view_status) && ($view_status==1))
      {
        global $userinfo;
        $UserProfile_local = false;
        if(!class_exists('SDUserProfile') || !isset($UserProfile))
        {
          $UserProfile_local = true;
          require_once(SD_INCLUDE_PATH.'class_userprofile.php');
          // Instantiate Userprofile and load user data (users_data)
          $UserProfile = new SDUserProfile();
        }
        else
          SDProfileConfig::init(11);
        #$UserProfile->LoadUser($userinfo['userid']);
        #$userinfo['profile'] = SDProfileConfig::GetUserdata();
        $tmpFlds = array_flip(SDProfileConfig::$public_fieldnames);
        @ksort($tmpFlds);
        $user_public = false;
        if(!empty($userdata['public_fields']))
        {
          $user_public = @explode(',',$userdata['public_fields']);
          if(count($user_public))
          {
            $user_public = array_flip($user_public);
            @ksort($user_public);
          }
        }
        // Only continue if there are *any* public fields configured by the user
        foreach($extrafields as $field)
        {
          // Check if "extra" field is allowed for public view configured in plugin settings
          if(isset($tmpFlds[$field]) && isset(SDProfileConfig::$public_fieldnameids[$field]))
          {
            $field_id = SDProfileConfig::$public_fieldnameids[$field]['fieldnum'];
            // Check if "extra" field is allowed by user for public viewing
            if(($user_public!==false) && isset($user_public[$field_id]))
            {
              $user['public_fields'][] = $field;
            }
          }
          $user[$field] = $userdata[$field];
        }
        unset($tmpFlds,$field);
      }
      else
      {
        $user = array_merge($user, $userdata);
      }
    }
  }
  $DB->ignore_error = false;
  $DB->result_type = $prev_result_type;

  if($DB->database != $prevdb) $DB->select_db($prevdb);

  return $user;

} //sd_GetForumUserInfo


// SD313: Callback function for BBCode "[embed]..[/embed]" tag
// Can only be used as parameter for $BBCode->AddRule()!
function sd_BBCode_DoEmbed($bbcode, $action, $name, $default, $params, $content)
{
  global $DB;

  if(empty($bbcode) || (defined('BBCODE_CHECK') && ($action == BBCODE_CHECK)))
  {
    return true;
  }
  $url = is_string($default) ? $default : $bbcode->UnHTMLEncode(strip_tags($content));
  if($bbcode->IsValidURL($url))
  {
    return '
    <div class="embedded_media">
      <a href="' . htmlspecialchars($url) . '" class="bbcode_embedly">' . $content . '</a>
    </div>
    ';
  }
  else
  {
    return ''; // no content allowed!
  }

} //sd_BBCode_DoEmbed


// ########################### BBCode Callback ################################

// SD313: Callback function for BBCode "[code]..[/code]" tag
// Can only be used as parameter for $BBCode->AddRule()!
function sd_BBCode_DoCode($bbcode, $action, $name, $default, $params, $content)
{
  global $sdlanguage, $pluginid, $userinfo;

  if(empty($bbcode) || (defined('BBCODE_CHECK') && ($action == BBCODE_CHECK)))
  {
    return true;
  }

  $title = isset($sdlanguage['bbcode_code_title'])?$sdlanguage['bbcode_code_title']:'Code:';

  //SD322: do not allow code blocks be readable if not logged in
  if(empty($userinfo['loggedin']) &&
     (empty($userinfo['plugindownloadids']) || !in_array($pluginid, $userinfo['plugindownloadids'])) )
  {
    $content = '<strong>Sorry, you have no access to view code snippets.';
    $options = array();
    if(defined('REGISTER_PATH') and sd_strlen(REGISTER_PATH))
    {
      $options[] = ' <a style="text-decoration: underline;" href="'.REGISTER_PATH.'">'.$sdlanguage['common_signup'].'</a>';
    }
    if(defined('LOGIN_PATH') and sd_strlen(LOGIN_PATH))
    {
      $options[] = ' <a style="text-decoration: underline;" href="'.LOGIN_PATH.'">'.$sdlanguage['common_login'].'</a>';
    }
    if(count($options))
    {
      $content .= implode(' '.$sdlanguage['common_or'].' ', $options);
    }
    $content .= '</strong>';

    return '
    <div class="bbcode_code"><div class="bbcode_code_head">'.$title."</div>\n".$content."\n</div>";
  }

  $content = nl2br(htmlspecialchars(unhtmlspecialchars(html_entity_decode($content)), ENT_NOQUOTES));
  $content = RemoveBRtags($content);
  return '
  <div class="bbcode_code"><div class="bbcode_code_head">'.$title.'</div>
    <pre class="syntax php-script">'.$content.'</pre>
  </div>
  ';
  // style="white-space: pre"

} //sd_BBCode_DoCode


// ############################################################################
// GENERATE A RANDOM SALT STRING
// ############################################################################

function sd_generate_user_salt()
{
  $salt = '';
  for ($i = 0; $i < 20; $i++)
  {
    $salt .= chr(rand(33, 126));
  }
  return $salt;
}


// ############################################################################
// USE jGrowl TO DISPLAY A MESSAGE
// ############################################################################

function sd_GrowlMessage($message, $doOutput=true, $milseconds=3000) //SD370
{
  if(!isset($message)) return false;
  $milseconds = isset($milseconds)?max(500,intval($milseconds)):3000;
  $result = '
<script type="text/javascript">
if(typeof jQuery.fn.jGrowl !== "undefined") {
  jQuery.jGrowl("'.addslashes($message).'", {
    easing: "swing", life: '.$milseconds.', pool: 5,
    animateOpen:  { height: "show" },
    animateClose: { height: "hide", width: "show" }
  });
}
</script>
';
  if(empty($doOutput)) return $result;
  echo $result;
  return true;
}

// ############################################################################
// GET CURRENT FOLDER NAME
// ############################################################################

function sd_GetCurrentFolder($file__file /* call with __FILE__ ! */)
//SD322: determines the current folder name, given that
// the parameter was "__FILE__" from the calling file
// inside that folder.
{
  if(!isset($file__file)) return '';

  $result = dirname($file__file /*__FILE__*/);
  if(strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')
  {
    $dir_separator = '/';
    $result = str_replace("\\", $dir_separator, $result);
  }
  else
  {
    $dir_separator = "\\";
    $result = str_replace("/", $dir_separator, $result);
  }
  // Make sure, at then end is only ONE directory separator
  if(substr($result,-2) == $dir_separator.$dir_separator)
  {
    $result = substr($result,0,-1);
  }

  return basename($result);

} //sd_GetCurrentFolder


// ######################## ADD ENTRIES TO HEADER ARRAY ########################

/**
 * Add JS/CSS links or JS code to global "header" variable, which is
 * passed to the page HEAD tag.
 * @param array $header_array with "css", "js" or "other" sub-arrays. "other" is plain HTML, also allowing for full JS code scripts.
 * @param bool $addFirst place entries at top of list (recommended only for JS or CSS)
 * @return null
 */
function sd_header_add($header_array, $addFirst = true)
{
  /*
  If an entry in $header_array contains NO slash "/", then automatically either
  "includes/javascript" or "includes/css" is prepended.

  For CSS entries, an entry may be an array with 2 items, the first being the
  file reference and the 2nd entry the class name. This is so far only used
  for including jQuery-UI stylesheets, which get a class of e.g. "ui-theme".
  */
  global $sd_header, $sd_header_crc, $sdurl, $sd_head;

  if(empty($header_array) || !is_array($header_array))
  {
    return;
  }

  //SD343: added "meta" entry
  // Process this array's items "as-is"; processed first as these could be "meta" lines:
  if(!empty($header_array['meta']) && is_array($header_array['meta']) && ($num = count($header_array['meta'])))
  {
    foreach($header_array['meta'] as $k => $v)
    {
      //SD370: htmlspecialchars($v)
      if(!empty($v)) $sd_header['meta'][$k] = htmlspecialchars($v);
    }
  }
  
  if(defined('IN_ADMIN'))
  {
	  $sd_head->AddMeta($sd_header['meta']);
  }

  //SD370: added "meta property" entry
  // Process this array's items "as-is"; processed first as these could be "meta" lines:
  if(!empty($header_array['meta_prop']) && is_array($header_array['meta_prop']) && ($num = count($header_array['meta_prop'])))
  {
    foreach($header_array['meta_prop'] as $k => $v)
    {
      //SD370: htmlspecialchars($v)
      if(!empty($v)) $sd_header['meta_prop'][$k] = htmlspecialchars($v);
    }
  }
  
  if(defined('IN_ADMIN'))
  {
	  $sd_head->AddMeta($sd_header['meta_prop']);
  }

  // Process "other" array items "as-is"; processed first as these could be "meta" lines:
  if(!empty($header_array['other']) && is_array($header_array['other']) && ($num = count($header_array['other'])))
  {
    for($i = 0; $i < $num; $i++)
    {
      if(!empty($header_array['other'][$i]))
      {
        $line = $header_array['other'][$i];
        $crc = crc32($line);
        if(!in_array($crc, $sd_header_crc))
        {
          $sd_header_crc[] = $crc;
          $sd_header['other'][$line] = $line;
        }
      }
    }
  }
  
  if(defined('IN_ADMIN'))
  {
	  $sd_head->AddScript($sd_header['other']);
  }

  // Process "css" array items
  if(!empty($header_array['css']) && is_array($header_array['css']) && ($num = count($header_array['css'])))
  {
    $aTmp = array();
    for($i = 0; $i < $num; $i++)
    {
      if(!empty($header_array['css'][$i]))
      {
        $class = '';
        $line = $header_array['css'][$i];
        // Special case: entry is array with class name:
        if(is_array($line))
        {
          if(isset($line[1])) $class = $line[1];
          if(isset($line[0])) $line  = $line[0];
        }
        // avoid duplicates:
        $crc = crc32($line);
        if(!in_array($crc, $sd_header_crc))
        {
          $sd_header_crc[] = $crc;
          $tmp  = $line;
          if(strpos($tmp,'/') === false)
          {
            $tmp = $sdurl . 'includes/css/' . $tmp;
          }
          else
          if(substr($tmp,0,strlen(ROOT_PATH)) === ROOT_PATH)
          {
            $tmp = $sdurl . substr($tmp,strlen(ROOT_PATH));
          }
          else
          if(substr($tmp,0,8) === 'plugins/')
          {
            $tmp = $sdurl . $tmp;
          }
		  
		  if(!defined('IN_ADMIN'))
		  {
          	$link = '<link rel="stylesheet" type="text/css" href="'.$tmp.'" '.
                  (strlen($class) ? 'class="'.$class.'" ' : '').'/>';
		  }
		  else
		  {
			  $link = $tmp;
		  }
		  
          $aTmp[] = $link;
        }
      }
    }
    if(!empty($aTmp))
    {
      if($addFirst) {
        $sd_header['css'] = array_merge($aTmp, $sd_header['css']);
      } else {
        $sd_header['css'] = array_merge($sd_header['css'], $aTmp);
      }
    }
  }
  
  if(defined('IN_ADMIN'))
  {
	  $sd_head->AddCSS($sd_header['css']);
  }


  // Process "css" array items
  if(!empty($header_array['css_import']) && is_array($header_array['css_import']) && ($num = count($header_array['css_import'])))
  {
    $aTmp = array();
    for($i = 0; $i < $num; $i++)
    {
      if(!empty($header_array['css_import'][$i]))
      {
        $class = '';
        $line = $header_array['css_import'][$i];
        // Special case: entry is array with class name:
        if(is_array($line))
        {
          if(isset($line[1])) $class = $line[1];
          if(isset($line[0])) $line  = $line[0];
        }
        // avoid duplicates:
        $crc = crc32($line);
        if(!in_array($crc, $sd_header_crc))
        {
          $sd_header_crc[] = $crc;
          $aTmp[] = $line;
        }
      }
    }
    if(!empty($aTmp))
    {
      if($addFirst) {
        $sd_header['css_import'] = array_merge($aTmp, $sd_header['css_import']);
      } else {
        $sd_header['css_import'] = array_merge($sd_header['css_import'], $aTmp);
      }
    }
  }
  
  if(defined('IN_ADMIN'))
  {
	  $sd_head->AddCSS($sd_header['css_import']);
  }

  // Process "css-ie" array items
  if(!empty($header_array['css-ie']) && is_array($header_array['css-ie']) && ($num = count($header_array['css-ie'])))
  {
    $aTmp = array();
    for($i = 0; $i < $num; $i++)
    {
      $line = $header_array['css-ie'][$i];
      $link = '<link rel="stylesheet" type="text/css" href="'.(strpos($line,'/') === false ? 'includes/css/' : '').$line.'" />';
      $crc = crc32($line);
      if(!in_array($crc, $sd_header_crc))
      {
        $sd_header_crc[] = $crc;
        $aTmp[] = $link;
      }
    }
    if(!empty($aTmp))
    {
      if($addFirst) {
        $sd_header['css-ie'] = array_merge($aTmp, $sd_header['css-ie']);
      } else {
        $sd_header['css-ie'] = array_merge($sd_header['css-ie'], $aTmp);
      }
    }
  }
  
  if(defined('IN_ADMIN'))
  {
	  $sd_head->AddCSS($sd_header['css-ie']);
  }

  // Process "js" array items
  if(!empty($header_array['js']) && is_array($header_array['js']) && ($num = count($header_array['js'])))
  {
    $aTmp = array();
    $jquery = '';
    $jquerycrc = '';
    for($i = 0; $i < $num; $i++)
    {
      if(!empty($header_array['js'][$i]))
      {
        //SD370: extended to work with arrays (e.g. in minify is off)
        $loop = $header_array['js'][$i];
        if(!is_array($loop)) $loop = array($loop);
        foreach($loop as $line)
        {
          $tmp = $line;
          if(strpos($tmp,'/') === false)
          {
            $tmp = $sdurl . 'includes/javascript/' . $tmp;
          }
          else
          if(substr($tmp,0,strlen(ROOT_PATH)) === ROOT_PATH)
          {
            $tmp = $sdurl . substr($tmp,strlen(ROOT_PATH));
          }
          else
          if(substr($tmp,0,8) === 'plugins/')
          {
            $tmp = $sdurl . $tmp;
          }
		  
		  if(!defined('IN_ADMIN'))
		  {
          	$link = '<script type="text/javascript" src="'.$tmp.'"></script>';
		  }
		  else
		  {
			  $link = $tmp;
		  }
		  
          $crc = crc32($line);
          // ignore jquery itself as it is core in SD3
          //SD360: use regex for better detection of common jQuery naming in $line
          #if(($line == 'jquery.js') || ($line == JQUERY_FILENAME) || ($line == 'JQUERY_GA_CDN')) //SD343 JQUERY_GA_CDN
          if(preg_match('#jquery(-?[0-9]\.[0-9](\.[0-9])?\.min)?\.js#i', $line))
          {
            if(($line == 'JQUERY_GA_CDN') && defined(JQUERY_GA_CDN) && strlen(JQUERY_GA_CDN))
              $jquery = JQUERY_GA_CDN;
            else
              $jquery = $link;
            $jquerycrc = $crc;
          }
          else
          if(!in_array($crc, $sd_header_crc))
          {
            $sd_header_crc[] = $crc;
            $aTmp[] = $link;
          }
        }
      }
    }
	
    if(!empty($aTmp))
    {
      if($addFirst) {
        $sd_header['js'] = array_merge($aTmp, $sd_header['js']);
      } else {
        $sd_header['js'] = array_merge($sd_header['js'], $aTmp);
      }
    }
	
	if(defined('IN_ADMIN'))
  	{
	  $sd_head->AddJS($sd_header['js']);
  	}

    /*
    // make sure that jquery is at top of list:
    if(!empty($jquery) && in_array($jquerycrc, $sd_header_crc))
    {
     $keypos = array_search($jquery, $sd_header['js']);
     if(!empty($keypos))
     {
       $sd_header['js'][$keypos] = null;
       array_unshift($sd_header['js'], $jquery);
     }
    }
    */
  }
 
  // make sure the global "sd_header" is set
  $GLOBALS['sd_header'] = $sd_header;
  if(defined('IN_ADMIN')) $sd_header = array('other' => array(),'css' => array(),'js' => array()); 	//SD400: unsset $sd_header so we don't duplicate output

} //sd_header_add



/**
 * sd_header_output outputs a global header array with each item being a single line
 * All entries' "value" gets cleared after display so that consecutive calls
 * do not output the same lines twice during page build (keys stay intact):
 * @param bool $direct_output if true directly echo the output, otherwise return it
 * @param bool $doFlush if true directly echo the output, otherwise return it
 */
function sd_header_output($direct_output=true, $doFlush=true)
{
  global $sd_header;

  $result = '';
  if(isset($sd_header) && is_array($sd_header))
  {
    //SD342 collect CSS files for "css.php" to return an @import file
    $section = 'css_import';
    if(isset($sd_header[$section]) && count($sd_header[$section]))
    {
      //ex.: $result = '<style type="text/css">@import url("css.php?styles,ceebox");</style>';
      $result = "\n".'<link rel="stylesheet" type="text/css" href="css.php?import=';
      foreach($sd_header[$section] as $key => $line)
      {
        $result .= $line.',';
        if($direct_output && isset($GLOBALS['sd_header'][$section][$key]))
        {
          unset($GLOBALS['sd_header'][$section][$key]);
        }
      }
      $result = substr($result,0,-1).'" />';
    }

    foreach(array('css','js','other') as $section)
    {
      if(isset($sd_header[$section]) && count($sd_header[$section]))
      {
        foreach($sd_header[$section] as $key => $line)
        {
          $result .= strlen($line) ? "\n".$line : '';
          if($direct_output && isset($GLOBALS['sd_header'][$section][$key]))
          {
            unset($GLOBALS['sd_header'][$section][$key]);
          }
        }
      }
    }

    $section = 'css-ie';
    if(isset($sd_header[$section]) && count($sd_header[$section]))
    {
      $result .=  "\n<!--[if IE]>";
        foreach($sd_header[$section] as $key => $line)
        {
          $result .=  strlen($line) ? "\n".$line : '';
          if($direct_output && isset($GLOBALS['sd_header'][$section][$key]))
          {
            unset($GLOBALS['sd_header'][$section][$key]);
          }
        }
      $result .= "<![endif]-->\n";
    }
  }

  if($direct_output)
  {
    echo $result;
  }
  else
  {
    return $result;
  }
} //sd_header_output


/**
 * SD: Output global header and clear it afterwards
 */
function sd_header_flush($direct_output = true)
{
  $result = sd_header_output($direct_output);
  $GLOBALS['sd_header'] = array('other' => array(),'css' => array(),'js' => array());
  return $result;
} //sd_header_flush


// ################# CONVERT URLS INTO LINKS IN GIVEN TEXT ####################

function sd_check_url($url) // SD 322
{
  // source: Brian Bothwell, http://regexlib.com/REDetails.aspx?regexp_id=501
  return preg_match("`^(http|https|ftp)\://([a-zA-Z0-9\.\-]+(\:[a-zA-Z0-9\.&amp;%\$\-]+)*@)*((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])|localhost|([a-zA-Z0-9\-]+\.)*[a-zA-Z0-9\-]+\.(com|edu|gov|int|mil|net|org|biz|arpa|info|name|pro|aero|coop|museum|[a-zA-Z]{2}))(\:[0-9]+)*(/($|[a-zA-Z0-9\.\,\?\'\\\+&amp;%\$#\=~_\-]+))*$`", $url);
}

function sd_check_image_url($url) //SD322
{
  //return preg_match("#(http(s?):)|([/|.|\w|\s])*\.(?:jpg|gif|png|jpeg)#", $url);
  return preg_match('`(?:([^:/?#]+):)?(?://([^/?#]*))?([^?#]*\.(?:jpg|jpeg|gif|png|bmp|tif|svg))(?:\?([^#]*))?(?:#(.*))?`i', $url);
}

// ################# CONVERT URLS INTO LINKS IN GIVEN TEXT ####################

function sd_links2anchors($text) //SD344: fixed/renamed
{
  return preg_replace('`\b(https?|ftp|file)\://[-A-Z0-9+&@#/%?=~_|!:,.;]*[-A-Z0-9+&@#/%=~_|]`i', '<a href="\0">\0</a>', $text);
}

// ################### CHECK FOLDER NAME BEING VALID TEXT #####################

function sd_check_foldername($folder) //SD360: fixed/renamed
{
  return (preg_match('#\R|\^|\?|\*|"|\'|\<|\>|\:|\|\\|\/#', $folder) ? false : true);
}

function sd_check_pathchars($path) //SD360
{
  return (preg_match('#\R|\^|\?|\*|"|\'|\<|\>|\:|\|#', $path) ? false : true);
}

// ######################## CHECK (GIVEN) UPLOAD FOLDER #######################

function sd_check_upload_folder($folder) //SD313
{
  // check specified folder
  if(!empty($folder) && is_dir($folder) && is_writable($folder))
    return true;

  // check server's temporary file upload folder
  $upload_tmp_dir = @ini_get("upload_tmp_dir");

  if(empty($upload_tmp_dir) || !is_dir($upload_tmp_dir) || !is_writable($upload_tmp_dir))
    return false;

  return true;

} //sd_check_upload_folder


/**
 * Copy a file, or recursively copy a folder and its contents
 * @param    string   $source    Source path
 * @param    string   $dest      Destination path
 * @param    string   $permissions New folder creation permissions (default: 0777)
 * @return   bool     Returns true on success, false on failure
 */
 //From: http://stackoverflow.com/questions/2050859/copy-entire-contents-of-a-directory-to-another-using-php
 // with additions for chmod on files and intval on permissions
function sd_xcopy($source, $dest, $permissions = '0777', $firstCall=true)
{
  // Check for symlinks
  if(is_link($source))
  {
    return symlink(readlink($source), $dest);
  }

  // Simple copy for a file
  if(is_file($source))
  {
    $res = true;
    if(file_exists($dest))
    {
      //First set full write permissions, then unlink
      @chmod($dest, intval('0666', 8));
      @unlink($dest);
    }
    if($res = @copy($source, $dest))
    {
      @chmod($dest, intval('0666', 8));
    }
    return $res;
  }

  // Make destination directory
  if(!is_dir($dest))
    @mkdir($dest, intval($permissions, 8));
  else
    //MUST use 0777 or FTP won't be able to unlink files
    @chmod($dest, intval('0777', 8));

  // Loop through the folder
  $source = rtrim($source,'/\\');
  if(false !== ($dir = @dir($source)))
  {
    if(!empty($firstCall))
    {
      $olddog = $GLOBALS['sd_ignore_watchdog'];
      $GLOBALS['sd_ignore_watchdog'] = true;
    }

    $dest = rtrim($dest,'/\\');
    while(false !== $entry = $dir->read())
    {
      // Skip pointers
      if($entry == '.' || $entry == '..') continue;

      // Deep copy directories
      sd_xcopy($source.'/'.$entry, $dest.'/'.$entry, false);
    }

    // Clean up
    $dir->close();

    if(!empty($firstCall))
    {
      $GLOBALS['sd_ignore_watchdog'] = $olddog;
    }
  }

  return true;

} //sd_xcopy


/**
 * Delete a file, or recursively delete a folder and its contents
 * @param    string   $source    Source path
 * @return   bool     Returns true on success, false on failure
 */
 // Based on above sd_xcopy
function sd_xdelete($source, $firstCall=true)
{
  // Simple deletion of a file
  if(is_file($source))
  {
    $olddog = $GLOBALS['sd_ignore_watchdog'];
    $GLOBALS['sd_ignore_watchdog'] = true;
    @chmod($source, intval('0666', 8));
    $res = @unlink($source);
    $GLOBALS['sd_ignore_watchdog'] = $olddog;
    return $res;
  }
  $res = true;

  // Loop through the folder
  $source = rtrim($source,'/\\');
  if(false !== ($dir = @dir($source)))
  {
    if(!empty($firstCall))
    {
      $olddog = $GLOBALS['sd_ignore_watchdog'];
      $GLOBALS['sd_ignore_watchdog'] = true;
    }

    while(false !== $entry = $dir->read())
    {
      // Skip pointers
      if($entry == '.' || $entry == '..') continue;

      // Recursive delete directory
      $res &= sd_xdelete($source.'/'.$entry, false);
    }

    // Clean up
    $dir->close();
    @rmdir($source);

    if(!empty($firstCall))
    {
      $GLOBALS['sd_ignore_watchdog'] = $olddog;
    }
  }

  return $res;

} //sd_xdelete


// ############################################################################
// GET JS FOR CeeBox (REQUIRES jQuery!)
// ############################################################################
//SD322: 2010-11-01 - new function "GetCeeboxDefaultJS"
//SD322: 2010-11-22 - added "$unloadCall" parameter (optional) which specifies
//                    the full call of an existing JS function when used
//                    right after Ceebox has been closed, e.g. "ReloadSettings();"
function GetCeeboxDefaultJS($withScriptTags = true, $JS_selector = 'a.cbox', $unloadCall='')
{
  // Return JS for (modal-)popup window for special links using the jQuery
  // plugin "Ceebox" (www.catcubed.com), replacing the sh*box
  // Used e.g. for plugin "Permissions" links in plugins.php and customplugins.php
  $result = '';
  if(!empty($withScriptTags))
  {
    $result .= '
<script type="text/javascript">
//<![CDATA[
if(typeof(jQuery) !== "undefined"){
jQuery(document).ready(function() {
  ';
  }

//Note: below 2 Ceebox options cause errors in IE, so leave them out!
//      "borderColor": "#FF0000",
//      "boxColor": "#dcdcdc",

  $result .= 'if(typeof(jQuery.fn.ceebox) !== "undefined") {
    jQuery("'.$JS_selector.'").ceebox({
      animSpeed: "fast", fadeOut: 100, fadeIn: 100, borderWidth: "1px",
      html: true, htmlGallery: true, imageGallery: true, overlayOpacity: 0.8, margin: "70", padding: "14", titles: false';
  if(!empty($unloadCall))
  {
    $result .= '
      ,unload: function(){ '.$unloadCall.' }';
  }
  $result .= '
    });
  }';

  if(!empty($withScriptTags))
  {
    $result .= '
});
}
//]]>
</script>
';
  }

  return $result;

} //PrintCeeboxDefaultJS


function sd_CloseCeebox($timeout = 2, $message=null, $doOutput=true)
{ //SD360: added $doOutput; pass it to DisplayMessage if needed
  $out = '';
  if(isset($message))
  {
    $out = DisplayMessage($message, false, '', '', false);
  }
  if(!$timeout)
  {
    if($doOutput) echo $out;
    return;
  }
  $out .= '
<script type="text/javascript">
//<![CDATA[
  if(typeof(parent.jQuery.fn.ceebox) !== "undefined") {
    setTimeout(function(){ parent.jQuery.fn.ceebox.closebox(\'fast\'); }, '.($timeout*1000).');
  }
//]]>
</script>
';
  if($doOutput)
    echo $out;
  else
    return $out;
} //sd_CloseCeebox


// ############################# GET COLORS ###################################

function sd_getcolors($color)
{
  $r = sscanf($color, "#%2x%2x%2x");
  $red   = (array_key_exists(0, $r) && is_numeric($r[0]) ? $r[0] : 0);
  $green = (array_key_exists(1, $r) && is_numeric($r[1]) ? $r[1] : 0);
  $blue  = (array_key_exists(2, $r) && is_numeric($r[2]) ? $r[2] : 0);

  return array($red, $green, $blue);

} //sd_getcolors


// ################# INCLUDE SPECIAL CLASS FOR CHARACTER CONVERSION ###########

function sd_GetConverter($from_charset, $to_charset, $entities)
{
  global $rootpath;

  if(!defined('SD_CVC'))
  {
    @include_once(SD_INCLUDE_PATH . 'ConvertCharset.class.php');
    if(class_exists('ConvertCharset'))
    {
      define('SD_CVC', true);
    }
  }
  // NO "else" here for the init process!!!
  if(defined('SD_CVC'))
  {
    // Instantiate a default conversion object for conversion to UTF-8
    if(sd_strtolower($from_charset) != $to_charset)
    {
      $newconv = new ConvertCharset($from_charset, $to_charset, empty($entities));
      return $newconv;
    }
  }

  return null;

} //sd_GetConverter


// ############################################################################
// CREATE UNIX TIMESTAMP
// ############################################################################
// Date MUST be formatted in a PHP-compatible format for "strtotime()"!
function sd_CreateUnixTimestamp($date = '', $time = '')
{
  global $DB, $mainsettings_daylightsavings, $mainsettings_timezoneoffset;
  
  // $date = MM/DD/YYYY or DD.MM.YYYY
  // $time = HH:MMAP
  if($date)
  {
    $month = $day = $year = 0;
    // date start is formatted like this: yyyy-mm-dd
    if(isset($date) && strlen($date))
    {
      $date = @strtotime($date);
      if(strlen($date))
      {
        $date = strftime('%Y-%m-%d', $date);
        @list($year, $month, $day) = @explode('-', $date);
      }
    }

    if($time)
    {
      // time start is formatted like this: HH:MMAP
      list($hour, $minute) = explode(':', $time);
      $hour   = intval($hour);
      $ampm   = substr($minute, -2);
      $minute = intval(str_replace(' ','',substr($minute, 0, 2)));
      $second = 0;

      if( (strtolower($ampm) == 'am') && ($hour == 12) )
      {
        $hour = 0;
      }
      else if( (strtolower($ampm) == 'pm') && ($hour < 12) )
      {
        $hour += 12;
      }
    }
    else
    {
      $hour = $minute = $second = 0;
    }

    // change into timestamp and subtract the users timezonesettings and dst settings
    $timezoneoffset = (double)$mainsettings_timezoneoffset;
    $dst = empty($mainsettings_daylightsavings) ? 0 : 1;

    return (/*gm*/mktime($hour, $minute, $second, $month, $day, $year) /*- (3600 * ($timezoneoffset + $dst))*/);
  }
  else
  {
    return 0;
  }

} //sd_CreateUnixTimestamp


// ############################################################################
// CREATE USER SALT STRING
// ############################################################################

function sd_CreateUserSalt($userid)
{
  global $DB;
  if(Is_Valid_Number($userid,0,1,99999999))
  {
    $salt = sd_generate_user_salt();
    $DB->query("UPDATE {users} SET salt = '%s', use_salt = 1 WHERE userid = %d",
               $DB->escape_string($salt), $userid);
    return $salt;
  }
  return '';
} //sd_CreateUserSalt


// ################### CONVERT HTML TO BBCODE (BASIC) #########################

function sd_ConvertHtmlToBBCode($input) //SD360
{ //NOTE: $input must be "real" HTML, not htmlspecialchars'ed!

  if(!isset($input) || is_array($input) || !strlen($input))
  {
    return '';
  }

  global $sdurl;

  // replace common html to bbcode
  static $htmlreplace = array('@<ul[^>]*?>@siu',   '@</ul>@siu',
                              '@<li[^>]*?>@siu',   '@</li>@siu',
                              '@<(strong|b)>@siu', '@</(strong|b)>@siu',
                              '@<(italic|i)>@siu', '@</(italic|i)>@siu',
                              '@<u>@siu',          '@</u>@siu',
                              '@</?(br|div|p)[^>]*?>@siu');
  static $bbcodematch = array("\r\n[list]",      "[/list]\r\n",
                              "\r\n[*]",         "\r\n",
                              '[b]',             '[/b]',
                              '[i]',             '[/i]',
                              '[u]',             '[/u]',
                              "\r\n");
  $input   = str_replace(array("\r", "\n"), array(' ',' '), $input);

  $input   = preg_replace($htmlreplace, $bbcodematch, $input);

  // replace "html'ised color bbcode" code back to bbcode
  // (e.g. pasted output originating from bbcode editor)
  $input   = preg_replace('@<span\sstyle="color:\s?(red|blue|yellow|orange|green|purple|white|gray|black);?">(.*?)</span>@siu','[color=$1]$2[/color]',$input);

  // replace img tags
  $input   = preg_replace('@<img[^>]*src="([^"]*)"[^>]*?>@siu',"[img]\$1[/img]\r\n",$input);

  // prefix relative-pathed images with site url
  $input   = preg_replace("#(\[img\])(?!http+)#siu", '$1'.$sdurl, $input);

  // replace url tags
  $input   = preg_replace('@<a[^>]*href="([^"]*)"[^>]*?>([^<]*)</a>@siu',"[url=\$1]\$2[/url]\r\n",$input);

  // strip all tags to clean it up
  $input   = htmlspecialchars(strip_alltags($input));
  return $input;

} //sd_ConvertHtmlToBBCode


// #################### CONVERT A STRING TO AN ARRAY ##########################

function sd_ConvertStrToArray($input, $separator = ',')
{
  if(isset($input) && !is_array($input) && strlen($input))
  {
    $temp = trim($input);
    $seppattern = preg_quote($separator, '/');
    //SD343: replace duplicate blanks with single blank if different from separator
    if($separator != ' ') $temp = preg_replace('/[ ]{2,}/', ' ', $temp);
    // replace duplicate separators
    $temp = preg_replace('/['.$seppattern.']{2,}/', $separator, $temp);
    // split into array
    $temp = preg_split('/'.$seppattern.'/', $temp, -1, PREG_SPLIT_NO_EMPTY);
    return $temp;
  }
  else
  {
    return array();
  }
} //sd_ConvertStrToArray


// ###################### REPLACE CORE SKIN VARIABLES #########################

function sd_DoSkinReplacements($input) //SD344
{
  global $sd_cache, $sdurl;
  if(empty($input)) return isset($input)?$input:'';
  $uri = RewriteLink();
  $replace_search = array('[HEADER]',
                          '[MOBILE_HEADER]',   //SD370
                          '[FOOTER]',
                          '[MOBILE_FOOTER]',   //SD370
                          '[MOBILENAVIGATION]',//SD370
                          '[MOBILE_RETURN]',
                          '[BREADCRUMB]',
                          '[NAVIGATION]',
                          '[NAVIGATION-TOPLEVEL]',
                          '[NAVIGATION-TOP-ONLY]',
                          '[NAVIGATION-TOPLEVEL-NOMENU]',
                          '[NAVIGATION-TOP-ONLY-NOMENU]',
                          '[NAVIGATION-BOTTOM-ONLY]',
                          '[NAVIGATION-BOTTOM-ONLY-NOMENU]',
                          '[SUBNAVIGATION]',
                          '[SIBLINGPAGES]',
                          '[LOGO]',
                          '[LANGUAGES_LOGO]',
                          '[LANGUAGES_LOGO_FOOTER]',
                          '[CMS_HEAD_INCLUDE]',
                          '[CMS_HEAD_NOMENU]',
                          '[CMS_HEAD_USER_BUTTON]',
                          '[LANGUAGES_TOP_NAVIGATION]',
                          '[COPYRIGHT]',
                          '[ARTICLE_TITLE]',   //SD370
                          '[PAGE_TITLE]',
                          '[PAGE_NAME]',
                          '[PAGE_HTML_CLASS]', //SD370
                          '[PAGE_HTML_ID]',    //SD370
                          '[REGISTER_PATH]',
                          '[USERCP_PATH]',
                          '[LOSTPWD_PATH]',
                          '[LOGIN_PATH]');
  $replace_values = array($sd_cache['skinvars']['HEADER'],
                          $sd_cache['skinvars']['MOBILE_HEADER'],    //SD370
                          $sd_cache['skinvars']['FOOTER'],
                          $sd_cache['skinvars']['MOBILE_FOOTER'],    //SD370
                          $sd_cache['skinvars']['MOBILENAVIGATION'], //SD370
                          ($uri.(strpos($uri,'?')===false?'?':'&amp;').'sd_mob='.(defined('SD_MOBILE_ENABLED') && SD_MOBILE_ENABLED?'1':'2')), //SD370
                          $sd_cache['skinvars']['BREADCRUMB'],
                          $sd_cache['skinvars']['NAVIGATION'],
                          $sd_cache['skinvars']['NAVIGATION_TOPLEVEL'],
                          $sd_cache['skinvars']['NAVIGATION_TOP_ONLY'],
                          $sd_cache['skinvars']['NAVIGATION_TOPLEVEL_NOMENU'],
                          $sd_cache['skinvars']['NAVIGATION_TOP_ONLY_NOMENU'],
                          $sd_cache['skinvars']['NAVIGATION_BOTTOM_ONLY'],
                          $sd_cache['skinvars']['NAVIGATION_BOTTOM_ONLY_NOMENU'],
                          $sd_cache['skinvars']['SUBNAVIGATION'],
                          $sd_cache['skinvars']['SIBLINGPAGES'],
                          $sd_cache['skinvars']['LOGO'],
                          $sd_cache['skinvars']['LANGUAGES_LOGO'],
                          $sd_cache['skinvars']['LANGUAGES_LOGO_FOOTER'],
                          $sd_cache['skinvars']['CMS_HEAD_INCLUDE'],
                          $sd_cache['skinvars']['CMS_HEAD_NOMENU'],
                          $sd_cache['skinvars']['CMS_HEAD_USER_BUTTON'],
                          $sd_cache['skinvars']['LANGUAGES_TOP_NAVIGATION'],
                          $sd_cache['skinvars']['COPYRIGHT'],
                          $sd_cache['skinvars']['ARTICLE_TITLE'],
                          $sd_cache['skinvars']['PAGE_TITLE'],
                          $sd_cache['skinvars']['PAGE_NAME'],
                          $sd_cache['skinvars']['PAGE_HTML_CLASS'],
                          $sd_cache['skinvars']['PAGE_HTML_ID'],
                          $sd_cache['skinvars']['REGISTER_PATH'],
                          $sd_cache['skinvars']['USERCP_PATH'],
                          $sd_cache['skinvars']['LOSTPWD_PATH'],
                          $sd_cache['skinvars']['LOGIN_PATH'] );
  return str_replace($replace_search, $replace_values, $input);
}

// ###########################################################################

function sd_CheckEmailsList($emails, $error_display = true)
{
  global $sdlanguage;

  $emails_checked = array();
  $email_a = sd_ConvertStrToArray($emails);

  if(count($email_a))
  foreach ($email_a as $email)
  {
    if((substr($email,0,1) == '<') && (substr($email,-1) == '>'))
    {
      $email = substr($email,1,-1);
    }
    if(IsValidEmail($email) && CheckMailHeader($email))
    {
      $emails_checked[] = $email;
    }
    else
    {
      if(!empty($error_display))
      {
        echo $sdlanguage['email_recipient_invalid'].': '.htmlspecialchars($email).'<br />';
      }
    }
  } //for

  return $emails_checked;

} //sd_CheckEmailsList


// ############################################################################
// SEND EMAIL (OLD VERSION)
// ############################################################################

function sd_SendEmail($email_to_address, $email_subject, $email_body, $email_from_name = TECHNICAL_EMAIL, $email_from_address = TECHNICAL_EMAIL)
{
  global $mainsettings;

  unset($GLOBALS['SD_EMAIL_ERROR']); //do not remove!

  // SD313: Check to see if someone is trying to do email injection
  // If so, drop the email!
  if(!CheckMailHeader($email_from_name) ||
     !CheckMailHeader($email_from_address) ||
     !CheckMailHeader($email_to_address) ||
     !CheckMailHeader($email_subject))
  {
    return false;
  }

  $email_from_name_arr = sd_ConvertStrToArray($email_from_name);
  $email_to_name_arr = sd_ConvertStrToArray($email_to_address);

  if(!count($email_from_name_arr) || !count($email_to_name_arr))
  {
    return false;
  }
  $email_from_name = $email_from_name_arr[0]; // "there can be only one" :)

  $email_to_address = implode(', ', $email_to_name_arr);

  $email_headers  = "From: \"$email_from_name\" <$email_from_address>\n";
  $email_headers .= "Reply-To: \"$email_from_name\" <$email_from_address>\n";
  $email_headers .= "Return-Path: <$email_from_address>\n";
  $email_headers .= "MIME-Version: 1.0\n";
  $email_headers .= "Content-Transfer-Encoding: 8bit\n";
  $email_headers .= "Content-type: text/plain; charset=" . SD_CHARSET . "\n";
  $email_headers .= "X-Mailer: PHP/" . phpversion();

  return @mail($email_to_address, $email_subject, $email_body, $email_headers);

} //sd_SendEmail


// ###################### SEND EMAIL (USING PHPMAILER) ########################

function SendEmail($toAddress, $subject, $message, $sendername = null,
                   $senderemail = null, $cc = null, $bcc = null, $html = null,
                   $attachments = null, $replyto = null)
{
  //SD370: added "$replyto" param for Contact Form plugin

  // If PHPMailer does not exist, fall back to OLD email message
  if(!file_exists(SD_INCLUDE_PATH.'phpmailer/class.phpmailer.php'))
  {
    //WatchDog('Email', 'PHPMailer class file not found!', WATCHDOG_ERROR);
    sd_SendEmail($toAddress, $subject, $message,
                 (isset($sendername)?$sendername:TECHNICAL_EMAIL),
                 (isset($senderemail)?$senderemail:TECHNICAL_EMAIL));
    return true;
  }

  global $mainsettings, $sdlanguage, $userinfo;

  //SD370: if explicitly specified, use new $replyto as Reply-To address:
  if(!empty($replyto) && CheckMailHeader($replyto) && IsValidEmail($replyto))
    $replyemail = $replyto;
  else
    $replyemail = sd_strtolower(!empty($senderemail) ? sd_strtolower($senderemail) : TECHNICAL_EMAIL);
  $replyname = !empty($sendername) ? $sendername : $mainsettings['websitetitle'];
  if(isset($senderemail) && strlen($senderemail) && empty($mainsettings['techfromemail']))
    $fromemail = $senderemail;
  else
    $fromemail = TECHNICAL_EMAIL;

  // Check the recipient(s), which could be a list:
  if(empty($toAddress))
  {
    echo $sdlanguage['email_recipient_invalid'].'<br />';
    return false;
  }
  $cc_checked = $bcc_checked = array();
  $toAddress_checked = sd_CheckEmailsList($toAddress, false);
  $fromemail_checked = sd_CheckEmailsList($fromemail, false);
  $replyemail_checked = sd_CheckEmailsList($replyemail, false);
  if(!empty($cc))  $cc_checked  = sd_CheckEmailsList($cc, false);
  if(!empty($bcc)) $bcc_checked = sd_CheckEmailsList($bcc, false);

  if(empty($fromemail_checked))
  {
    echo $sdlanguage['email_sender_invalid'].'<br />';
    return false;
  }

  foreach($fromemail_checked as $sender)
  {
    if(!IsValidEmail($sender))
    {
      echo $sdlanguage['email_sender_invalid'].'<br />';
      return false;
    }
  }

  // Check to see if someone is trying to do something naughty
  // If so, drop the email!
  if(!CheckMailHeader($senderemail)||
     !CheckMailHeader($sendername) ||
     !CheckMailHeader($replyname)  ||
     !CheckMailHeader($replyemail) ||
     !CheckMailHeader($fromemail)  ||
     !CheckMailHeader($subject) )
  {
    return false;
  }

  include_once(SD_INCLUDE_PATH.'phpmailer/class.phpmailer.php');
  $mail = new PHPMailer(false);
  $mail->PluginDir = SD_INCLUDE_PATH.'phpmailer/';
  $mail->XMailer = ''; //SD343: empty string

  if($mainsettings['email_mailtype']==1)
  {
    $mail->Sendmail = $mainsettings['email_sendmail_path'];
    $mail->IsSendmail();

  }
  else
  if($mainsettings['email_mailtype']==2)
  {
    $mail->IsSMTP();
    $mail->Host = $mainsettings['email_smtp_server'];
    $mail->SMTPAuth = $mainsettings['email_smtp_auth'];
    $mail->Username = $mainsettings['email_smtp_user'];
    //SD343: store pwd in local scope, not mainsettings
    static $smtp_pwd;
    if(!isset($smtp_pwd))
    {
      global $DB;
      $smtp_pwd = false;
      $old_ignore = $DB->ignore_error;
      $DB->ignore_error = true;
      if($pwd = $DB->query_first('SELECT value FROM '.PRGM_TABLE_PREFIX."mainsettings WHERE varname = 'email_smtp_pwd'"))
      {
        $smtp_pwd = $pwd['value'];
      }
      $DB->ignore_error = $old_ignore;
    }
    if($smtp_pwd) $mail->Password = $smtp_pwd;
    if($mainsettings['email_smtp_secure']==1)
    {
      $mail->SMTPSecure = 'ssl';
    }
    else if($mainsettings['email_smtp_secure']==2)
    {
      $mail->SMTPSecure = 'tls';
    }
  }
  else
  {
    $mail->IsMail();
  }

  $errors = array();
  $mail->From = $fromemail_checked[0];
  $mail->Sender = $fromemail_checked[0];
  $mail->FromName = empty($sendername) ? $fromemail_checked[0] : $sendername;
  foreach ($toAddress_checked as $email)
  {
    if(IsValidEmail($email))
    {
      if(!$mail->AddAddress($email)) $errors[] = $mail->ErrorInfo;
    }
  }
  foreach ($cc_checked as $email)
  {
    if(IsValidEmail($email))
    {
      if(!$mail->AddCC($email)) $errors[] = $mail->ErrorInfo;
    }
  }
  foreach ($bcc_checked as $email)
  {
    if(IsValidEmail($email))
    {
      if(!$mail->AddBCC($email)) $errors[] = $mail->ErrorInfo;
    }
  }
  foreach ($replyemail_checked as $email)
  {
    if(IsValidEmail($email))
    {
      if(!$mail->AddReplyTo($email)) $errors[] = $mail->ErrorInfo;
    }
  }

  $mail->CharSet = empty($mainsettings['email_encoding_charset']) ? 'iso-8859-1' : $mainsettings['email_encoding_charset'];
  $mail->WordWrap = 0;
  if(!isset($html) || ($html==false))
  {
    // If called with unset HTML option, strip tags from message and subject
    // (for security reasons). However, the email itself can still be send
    // in HTML format depending on the main setting.
    $message = preg_replace('#<br[\s+]?[/]?>#',EMAIL_CRLF,$message);
    $message = strip_tags($message);
    if(!isset($html))
    {
      $html = !empty($mainsettings['default_email_format']) && ($mainsettings['default_email_format']=='HTML');
      // Reapply linebreaks if HTML
      if($html)
      {
        $message = str_replace(EMAIL_CRLF,'<br />',$message);
      }
    }
  }

  if(!strlen($subject) || !strlen($message))
  {
    return false;
  }
  $mail->Subject = $subject;

  if(!empty($html))
  {
    $mail->IsHTML(true);
    $alt_body = str_replace(array('</p>','<br />','<br/>','<br>'),array(EMAIL_CRLF,EMAIL_CRLF,EMAIL_CRLF,EMAIL_CRLF),$message);
    $alt_body = strip_tags($alt_body);
    $mail->AltBody = $alt_body;
    $mail->MsgHTML($message);
  }
  else
  {
    $mail->IsHTML(false);
    $mail->Body = $message;
  }
  $safeMode = sd_safe_mode();
  try
  {
    if(isset($attachments) && is_array($attachments))
    {
      foreach($attachments as $filename => $title)
      {
        if($safeMode===false)
        {
          @set_time_limit(300);
        }
        if(isset($filename) && is_string($filename) && is_file($filename))
        {
          $mail->AddAttachment($filename, (isset($title{0}) ? $title : ''));
        }
        else if(isset($filename) && is_object($filename) && isset($filename['tmp_name']{0}) && is_file($filename['tmp_name']))
        {
          $mail->AddAttachment($filename['tmp_name'], (isset($title{0}) ? $title : ''));
        }
      }
    }

    if($safeMode===false)
    {
      @set_time_limit(300);
    }
    if(!@$mail->Send())
    {
      $errors[] = $mail->ErrorInfo;
      $GLOBALS['SD_EMAIL_ERROR'] = $errors;
      return false;
    }
    return true;
  }
  catch (phpmailerException $e)
  {
    $GLOBALS['SD_EMAIL_ERROR'] = $e->errorMessage();
    return false;
  }
  catch (Exception $e) {
    $GLOBALS['SD_EMAIL_ERROR'] = $e->errorMessage();
    return false;
  }
} //SendEmail


// #################### SEND HTML EMAIL WITHOUT ATTACHMENT ####################

function SendHTMLEmail($toAddress, $subject, $message, $sendername = null, $senderemail = null, $cc = null, $bcc = null, $attachments = null)
{
  return SendEmail($toAddress, $subject, $message, $sendername, $senderemail, $cc, $bcc, true, (isset($attachments)?$attachments:null));
}

// ####################### HTML EMAIL WITH ATTACHMENT #########################

function SendHTMLEmailAttachment($toAddress, $subject, $message, $attachment,
  $filename, $MIMEType = null, $sendername = null, $senderemail = null, $cc = null, $bcc = null)
{
  // $MIMEType no longer used since 2.6!
  return SendEmail($toAddress, $subject, $message, $sendername, $senderemail,
                   $cc, $bcc, true, array($attachment, $filename));
}


// ######################### EMAIL WITH ATTACHMENT ############################

function SendEmailAttachment($toAddress, $subject, $message, $attachment,
  $filename, $MIMEType = null, $sendername = null, $senderemail = null,
  $cc = null, $bcc = null, $html = null)
{
  // $MIMEType no longer used since 2.6!
  return SendEmail($toAddress, $subject, $message, $sendername, $senderemail,
                   $cc, $bcc, $html, array($attachment => $filename));
} //SendEmailAttachment


// Quoted printable support variables:
$qpKeyString = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7F\x80\x81\x82\x83\x84\x85\x86\x87\x88\x89\x8A\x8B\x8C\x8D\x8E\x8F\x90\x91\x92\x93\x94\x95\x96\x97\x98\x99\x9A\x9B\x9C\x9D\x9E\x9F\xA0\xA1\xA2\xA3\xA4\xA5\xA6\xA7\xA8\xA9\xAA\xAB\xAC\xAD\xAE\xAF\xB0\xB1\xB2\xB3\xB4\xB5\xB6\xB7\xB8\xB9\xBA\xBB\xBC\xBD\xBE\xBF\xC0\xC1\xC2\xC3\xC4\xC5\xC6\xC7\xC8\xC9\xCA\xCB\xCC\xCD\xCE\xCF\xD0\xD1\xD2\xD3\xD4\xD5\xD6\xD7\xD8\xD9\xDA\xDB\xDC\xDD\xDE\xDF\xE0\xE1\xE2\xE3\xE4\xE5\xE6\xE7\xE8\xE9\xEA\xEB\xEC\xED\xEE\xEF\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9\xFA\xFB\xFC\xFD\xFE\xFF";
$qpKeyString_Array = explode("\\",$qpKeyString);
$qpReplaceValues = array(
        "=00","=01","=02","=03","=04","=05","=06","=07",
        "=08","=09","=0A","=0B","=0C","=0D","=0E","=0F",
        "=10","=11","=12","=13","=14","=15","=16","=17",
        "=18","=19","=1A","=1B","=1C","=1D","=1E","=1F",
        "=7F","=80","=81","=82","=83","=84","=85","=86",
        "=87","=88","=89","=8A","=8B","=8C","=8D","=8E",
        "=8F","=90","=91","=92","=93","=94","=95","=96",
        "=97","=98","=99","=9A","=9B","=9C","=9D","=9E",
        "=9F","=A0","=A1","=A2","=A3","=A4","=A5","=A6",
        "=A7","=A8","=A9","=AA","=AB","=AC","=AD","=AE",
        "=AF","=B0","=B1","=B2","=B3","=B4","=B5","=B6",
        "=B7","=B8","=B9","=BA","=BB","=BC","=BD","=BE",
        "=BF","=C0","=C1","=C2","=C3","=C4","=C5","=C6",
        "=C7","=C8","=C9","=CA","=CB","=CC","=CD","=CE",
        "=CF","=D0","=D1","=D2","=D3","=D4","=D5","=D6",
        "=D7","=D8","=D9","=DA","=DB","=DC","=DD","=DE",
        "=DF","=E0","=E1","=E2","=E3","=E4","=E5","=E6",
        "=E7","=E8","=E9","=EA","=EB","=EC","=ED","=EE",
        "=EF","=F0","=F1","=F2","=F3","=F4","=F5","=F6",
        "=F7","=F8","=F9","=FA","=FB","=FC","=FD","=FE",
        "=FF"
        );


function sd_IsMailPrintable($str)
{
  global $qpKeyString;
  return (strcspn($str, $qpKeyString) == strlen($str));
} //sd_IsMailPrintable


function sd_encodeQuotedPrintable($str, $lineLength = 76, $lineEnd = EMAIL_CRLF)
{
  global $qpKeyString_Array, $qpReplaceValues;

  $out = '';
  $str = str_replace('=', '=3D', $str);
  $str = @str_replace($qpKeyString_Array, $qpReplaceValues, $str);
  $str = rtrim($str);

  // Split encoded text into separate lines
  while ($str)
  {
    $ptr = strlen($str);
    if ($ptr > $lineLength) {
        $ptr = $lineLength;
    }

    // Ensure we are not splitting across an encoded character
    $pos = strrpos(substr($str, 0, $ptr), '=');
    if ($pos !== false && $pos >= $ptr - 2) {
        $ptr = $pos;
    }

    // Check if there is a space at the end of the line and rewind
    if ($ptr > 0 && $str[$ptr - 1] == ' ') {
        --$ptr;
    }

    // Add string and continue
    $out .= substr($str, 0, $ptr) . '=' . $lineEnd;
    $str = substr($str, $ptr);
  }

  $out = rtrim($out, $lineEnd);
  $out = rtrim($out, '=');
  return $out;

} //sd_encodeQuotedPrintable


// ######################## ENCODE MAIL HEADER ################################
function EncodeMailHeader($header, $charset = '', $transencoding = 'Q')
{
  if(!isset($header))
  {
    return null;
  }

  if(!sd_IsMailPrintable($header))
  {
    $charset = empty($charset) ? SD_CHARSET : $charset;
    $quotedValue = sd_encodeQuotedPrintable($header);
    $quotedValue = str_replace(array('?', ' '), array('=3F', '=20'), $quotedValue);
    if($transencoding == 'Q')
    {
      return '=?' . $charset . '?Q?' . $quotedValue . '?=';
    }
    else
    {
      return $quotedValue;
    }
  }

  return $header;

} //EncodeMailHeader


// ############################################################################

function sd_GeneratePassword($length = 10)
{
  global $mainsettings;

   $dummy = array_merge(range('0', '9'), range('a', 'z'), range('A', 'Z'), array('#','&','@','$','_','%','?','+'));

   // shuffle array
   mt_srand((double)microtime()*1000000);

   $length = Is_Valid_Number($length,10,5,32);
   for ($i = 1; $i <= (count($dummy)*2); $i++)
   {
     $swap = mt_rand(0,count($dummy)-1);
     $tmp = $dummy[$swap];
     $dummy[$swap] = $dummy[0];
     $dummy[0] = $tmp;
   }

   // get password
   return substr(implode('',$dummy),0, $length);

} //sd_GeneratePassword


// ############################################################################

function sd_removeBadWords($input, $replacement = '***')
{
  global $mainsettings, $mainsettings_censored_words;

  $new = $input;
  if(!empty($input) && !empty($mainsettings_censored_words) && count($mainsettings_censored_words))
  {
    //SD343: preg_quot'ing
    $new = preg_replace('#\b('.implode('|',
             array_map('preg_quote',$mainsettings_censored_words,array('#'))).')\b#',
             $replacement, $new);
  }
  return $new;

} //sd_removeBadWords


// ############################################################################

function sd_IsUsernameInvalid($username = '')
//SD322: checks given $username against the User Registration plugin's
// list of invalid usernames (p12-plugin setting "invalid_usernames")
{
  global $DB;

  $invalids = GetPluginSettings(12, 'user_registration_settings', 'invalid_usernames');
  if(!isset($username) || !isset($invalids) || !strlen(trim($invalids=$invalids['invalid_usernames'])))
  {
    return false;
  }

  $invalids = isset($invalids) ? preg_split('/[\s,]+/', trim($invalids), -1, PREG_SPLIT_NO_EMPTY) : array();

  if(count($invalids) > 0)
  {
    foreach($invalids as $inv)
    {
      if(strpos($inv, '*') === false)
      {
        // Not wildcard so go for exact match
        if(strcasecmp($inv, $username) == 0)
        {
          return true;
        }
      }
      else
      if(preg_match('#'.$inv.'#i', $username))
      {
        return true;
      }
    }
  }

  return false;

} //sd_IsUsernameInvalid


// ############################################################################
// SOME FUNCTIONS TAKEN OVER FROM SD 2.6 FOR SEO/ARTICLE HANDLING
// ############################################################################

function sd_is_url_param($param)
{
  return isset($param) && strlen($param) && preg_match('#(^p[0-9]+_)|(^com_)|^logout$#', $param);
}


function sd_redirect($code, $url = '', $message = null, $showlogin = false)
{
  global $DB, $sdlanguage, $sdurl;

  switch($code)
  {
    case 301 :
      header("HTTP/1.0 301 Moved Permanently");
      break;
    case 302 :
      header("HTTP/1.0 302 Found");
      break;
    case 303 :
      header("HTTP/1.0 303 See Other");
      break;
    case 304 :
      header("HTTP/1.0 304 Not Modified");
      break;
    case 307 :
      header("HTTP/1.0 307 Temporary Redirect");
      break;
    case 400 :
      header("HTTP/1.0 400 Bad Request");
      break;
    case 401 :
      header("HTTP/1.0 401 Unauthorized");
      break;
    case 403 :
      header("HTTP/1.0 403 Forbidden");
      break;
    case 404 :
      header("HTTP/1.0 404 Not Found");
      if(!isset($message))
      {
        $message = $sdlanguage['page_not_found'];
      }
      $message .= '<br /><br /><a href="' . $sdurl . '">' . $sdlanguage['redirect_to_homepage'] . '</a>';
      $showlogin = false;
      $url = '';
  }
  if(isset($url{0}))
  {
    header('Location: ' . $url);
  }
  if(isset($message) && isset($message{0}))
  {
    PrintMessage($message);
  }
  else
  {
    if(isset($DB))
    {
      $DB->close();
    }
    exit();
  }

} //sd_redirect


function RewriteNewsLink($categoryid, $articleid, $page = 1, $friendlytitle = null, $pluginid = 2)
{
  global $DB, $sdurl, $mainsettings, $userinfo, $article_seo_to_ids;

  if(empty($pluginid) || ((int)$pluginid != $pluginid))
  {
    $pluginid = 2;
  }
  // IF the article plugin "Use SEO title" option is off, use regular RewriteLink:
  if(empty($article_seo_to_ids) || empty($mainsettings['modrewrite']))
  {
    return RewriteLink('index.php?categoryid='.$categoryid.'&p'.$pluginid.'_articleid='.$articleid.
           ($pluginid>2?'&pid='.$pluginid:'').
           (isset($page) && ($page > 1) ? ('&p'.$pluginid.'_page='.$page) : ''));
  }

  // Get the "base" URL for the category:
  if($mainsettings['modrewrite'])
  {
    $friendlyurl = RewriteLink('index.php?categoryid='.$categoryid,true,false);
    $friendlyurl .= substr($friendlyurl,-1) == '/' ? '' : '/';
  }
  else
  {
    $friendlyurl = RewriteLink('index.php?categoryid='.$categoryid.
      '&p'.$pluginid.'_articleid='.$articleid.(!empty($page) && $page > 1 ? '&p'.$pluginid.'_page='.$page : '').'&title='
      ,true,false);
  }

  if($article = $DB->query_first('SELECT title, seo_title FROM {p'.$pluginid.'_news} WHERE articleid = %d', $articleid))
  {
    if(isset($article['seo_title']{1}))
    {
      $friendlyurl .= $article['seo_title'] /*. '-a'. $articleid*/;
      if(!empty($page) && ($page > 1))
      {
        $friendlyurl .=  '-page' . (int)$page;
      }
      $friendlyurl = preg_replace('#[-]+#', '-', $friendlyurl) . $mainsettings['url_extension'];
    }
    else if(isset($friendlytitle{0}))
    {
      // IF a friendly name is already given, then only append page if needed
      $friendlyurl .= $friendlytitle;

      if(!empty($page) && ($page > 1))
      {
        $friendlyurl = preg_replace('#(\.html$)|(\.htm$)|(\.php$)#', "-page$page".$mainsettings['url_extension'], $friendlyurl);
      }
    }
    else
    {
      $friendlyurl .= ConvertNewsTitleToUrl($article['title'], $articleid, $page);
    }
  }

  $article_suffix = '';
  if(isset($userinfo['sessionurl']{0}))
  {
    $article_suffix .= (!strstr($friendlyurl, '?') ? '?' : '&') . $userinfo['sessionurl'];
  }

  return $friendlyurl . $article_suffix;

} //RewriteNewsLink


function sd_checkarticle($articleid, $redirect = false)
{
  global $DB, $userinfo, $sdlanguage, $article_seo_to_ids, $sd_article_err;

  /* At this point we need to detect already if
   a) the article exists at all (no = send 404)
   b) if the user has permission for both category AND article plugin
   We do not check here if the article is offline or not, that is to
   be done by the article plugin itself!
  */
  $article = $DB->query_first('SELECT title, settings, datestart, dateend, categoryid FROM {p2_news} WHERE articleid = %d', $articleid);
  if(!empty($article))
  {
    if(empty($userinfo['categoryviewids']) || !@in_array($_GET['categoryid'], $userinfo['categoryviewids']) ||
       empty($userinfo['pluginviewids'])   || !@in_array(2, $userinfo['pluginviewids']))
    {
      $categoryid = $_GET['categoryid']; // required for login panel!
      PrintMessage($sdlanguage['no_view_access'], 1);
    }

    // IF article plugin's option "Use SEO links" is active, then
    // redirect to new link, otherwise proceed as normal
    if($article_seo_to_ids && $redirect)
    {
      $url = RewriteNewsLink((int)$_GET['categoryid'], $articleid, isset($_GET['p2_page'])?(int)$_GET['p2_page']:null);
      sd_redirect(301, $url);
    }
  }

  // IF the article was not found at all, send 404 error:
  if(empty($article))
  {
    sd_redirect(($sd_article_err ? 404 : 301), "/");
  }

} //sd_checkarticle


// ############################################################################
// sd_IsIPBanned (SD343)
// ############################################################################

function sd_IsIPBanned($clientip, $iplist=null)
{
  if(empty($clientip)) return false;

  global $DB, $SDCache, $mainsettings, $p12_settings;

  $addresses = array();
  if(isset($iplist) && is_string($iplist)) //SD342
  {
    $addresses = $iplist;
  }
  else
  {
    $DB->result_type = MYSQL_ASSOC;
    if(!empty($p12_settings) && is_array($p12_settings))
    {
      $addresses = $p12_settings['banned_ip_addresses'];
    }
    else
    if($getbanip = $DB->query_first("SELECT value FROM {pluginsettings} WHERE pluginid = 12 AND title = 'banned_ip_addresses'"))
    {
      $addresses = $getbanip['value'];
    }
    $DB->result_type = MYSQL_BOTH;
  }
  $addresses = trim($addresses);
  $addresses = preg_replace('/\s\s+/', ' ', $addresses);
  $addresses = preg_split('/ /', $addresses, -1, PREG_SPLIT_NO_EMPTY);

  $result = false;
  if(count($addresses) > 0)
  {
    if(@in_array($clientip,$addresses)) return true;
    foreach($addresses as $ip)
    {
      //SD343: use else branch to allow for trailing dot in IP, like "123.234.34."
      if((substr($ip,-1) !== '.') && (strpos($ip, '*') === false))
      {
        // Not wildcard so go for exact match
        if($ip == $clientip)
        {
          $result = true;
          break;
        }
      }
      else
      {
        if(preg_match('#^'.str_replace('\*','.*',preg_quote($ip,'*')).'#', $clientip))
        {
          $result = true;
          break;
        }
      }
    }
  }
  if(!defined('IN_ADMIN') && $result && isset($SDCache) && !empty($mainsettings['count_banned_ips']))
  {
    Watchdog('Login Error','Banned user login attempt. (IP: '.$clientip.')'.
             (!empty($_POST['loginusername'])?', Username: '.$_POST['loginusername']:''),
             WATCHDOG_WARNING);
    if(($bans = $SDCache->read_var('sd_banned_ip_attempts', 'ip_banned')) == false)
    {
      $bans = array();
      $bans[$clientip] = 1;
    }
    else
    {
      if(is_array($bans) && array_key_exists($clientip,$bans))
      {
        $bans[$clientip]++;
      }
      else
      {
        $bans = array();
        $bans[$clientip] = 1;
      }
    }
    $SDCache->write_var('sd_banned_ip_attempts', 'ip_banned', $bans);
  }

  return $result;

} //sd_IsIPBanned


// ############################################################################
// sd_IsUriBlocked (SD343)
// ############################################################################

function sd_IsUriBlocked($uri, $snippets)
{
  if(empty($uri) || empty($snippets) || !is_string($snippets)) return false;

  $snippets = trim($snippets);
  $snippets = preg_replace('/\s\s+/', ' ', $snippets);
  $snippets = preg_split('/ /', $snippets, -1, PREG_SPLIT_NO_EMPTY);

  if(!count($snippets)) return false;
  $result = false;
  foreach($snippets as $entry)
  {
    if(preg_match('#'.str_replace('\*','*',preg_quote($entry,'*')).'#', $uri))
    {
      $result = true;
      break;
    }
  }

  return $result;

} //sd_IsUriBlocked


// ############################################################################
// CHECK IF EMAIL IS BANNED
// ############################################################################

function sd_IsEmailBanned($newEmail = '')
//SD322: checks given $email against the User Registration plugin's
// list of banned emails (p12-plugin setting "banned_emails")
{
  global $p12_settings;

  //SD342: p12 settings may already be loaded
  if(!isset($p12_settings) || !is_array($p12_settings))
  {
    $p12_settings = GetPluginSettings(12);
  }
  $addresses = $p12_settings['banned_emails'];
  if(!isset($newEmail) || !isset($addresses) || !strlen(trim($newEmail)) || !strlen(trim($addresses)))
  {
    return false;
  }

  if(!IsValidEmail($newEmail))
  {
    return true;
  }

  $addresses = preg_split('#[\s?,]+#', trim($addresses), -1, PREG_SPLIT_NO_EMPTY);

  if(is_array($addresses) && count($addresses))
  {
    $newEmail = trim($newEmail);

    foreach($addresses as $email)
    {
      $email = trim($email);
      if(strpos($email,'@') === 0) //domain ban
      {
        if(stripos($newEmail, $email) !== false)
        {
          return true;
        }
      }
      // Any user @domain?
      // Expand the match expression to catch hosts and sub-domains
      if(substr($email,-1)=='@')
      {
        $email = @preg_replace("#\.#", "\\.", $email);
        // User at any host?
        if(@preg_match("#^$email#i", $newEmail))
        {
          return true;
        }
      }
      else
      {
        // email identical to banned entry?
        if(strcasecmp($email, $newEmail) == 0)
        {
          return true;
        }
      }
    }
  }

  return false;

} //sd_IsEmailBanned


// ############################################################################
// CHECK IF EMAIL DOMAIN IS ALLOWED (IF CONFIGURED)
// ############################################################################

function sd_IsEmailDomainAllowed($newEmail = '')
//SD360: checks given $email against the User Registration plugin's
// list of allowed email domains (p12-plugin setting "allowed_email_domains")
// Note: IF setting is empty, it will return true as being allowed.
{
  global $p12_settings;

  // p12 settings may already be loaded
  if(!isset($p12_settings) || !is_array($p12_settings))
  {
    $p12_settings = GetPluginSettings(12);
  }
  if(empty($p12_settings['allowed_email_domains'])) return true;

  $addresses = trim($p12_settings['allowed_email_domains']);
  if(!isset($newEmail) || !strlen(trim($newEmail)) ||
     !strlen($addresses) || !IsValidEmail($newEmail))
  {
    return false;
  }

  $addresses = preg_split('#[\s?,]+#', trim($addresses), -1, PREG_SPLIT_NO_EMPTY);
  if(is_array($addresses) && count($addresses))
  {
    $newEmail = trim($newEmail);
    foreach($addresses as $entry)
    {
      $entry = preg_quote(trim($entry),'#');
      if(preg_match('#'.$entry.'$#iu',$newEmail))
      {
        return true;
      }
    }
  }
  return false;

} //sd_IsEmailDomainAllowed


// ############################################################################

function sd_strlen($string)
{
  if(!isset($string) || !strlen($string)) return 0;

  if((SD_CHARSET == 'utf-8') && function_exists('mb_strlen'))
    return mb_strlen($string);
  else
    return strlen($string);

} //sd_strlen

// ############################################################################

function sd_strlen_greater($string, $charcount=0)
{
  return ((isset($string) && $charcount) ? (sd_strlen($string) > $charcount) : false);

} //sd_strlen_greater

// ############################################################################

function sd_substr($string, $start, $length=NULL) //SD343
{
  if(!isset($string) || !strlen($string)) return '';

  if(SD_CHARSET == 'utf-8')
    return utf8_substr($string, $start, $length);
  else
    return substr($string, $start, $length);

} //sd_substr

// ##########################################################################

function GetPluginCategory($pluginid, $defaultcategory=0) //SD342
{
  global $DB, $plugin_name_to_id_arr, $mainsettings_search_results_page,
          $mainsettings_tag_results_page;

  $pluginid = Is_Valid_Number($pluginid,0,2,999999);
  $defaultcategory = isset($defaultcategory)?Is_Valid_Number($defaultcategory,0,1,9999999):0;
  if(empty($pluginid) || empty($plugin_name_to_id_arr) ||
     !@array_search($pluginid,$plugin_name_to_id_arr))
  {
    return false;
  }

  $c = array();
  if($getpages = $DB->query('SELECT categoryid FROM '.PRGM_TABLE_PREFIX.'pagesort'.
                            " WHERE pluginid = '%s'".
                            ' AND NOT (categoryid IN (%d, %d))'.
                            ' ORDER BY categoryid',
                            $pluginid,
                            (empty($mainsettings_search_results_page)?-1:$mainsettings_search_results_page),
                            (empty($mainsettings_tag_results_page)?-1:$mainsettings_tag_results_page)))
  {
    while($entry = $DB->fetch_array($getpages,null,MYSQL_ASSOC))
    {
      if($defaultcategory && ($entry['categoryid']==$defaultcategory))
      {
        return (int)$entry['categoryid'];
      }
      $c[] = $entry['categoryid'];
      //Do not break here, need to check all rows!
    }
  }
  //IF one or more pages found, return the first one
  return count($c)?$c[0]:false;

} //GetPluginCategory

// ############################################################################
// SD_KEYWORDS
// ############################################################################

function sd_getkeywords($str, $asString=true, $min_density=2, $max_results=10) //SD343
{
  global $sd_seo_stop_words_list_flip;

  if(empty($str))
    $str = '';
  else
    $str = preg_replace('#<(div|option|ul|li|table|tr|td|th|input|select|textarea|form)#i',' <\\1', $str);

  // str_word_count($str,1) - returns an array containing all the words found inside the string
  $str = str_replace('{pagebreak}', ' ', strip_alltags($str));
  $words = str_word_count(sd_strtolower($str),1,'0123456789');
  $numWords = count($words);

  // array_count_values() returns an array using the values of the input array as keys and their frequency in input as values.
  $word_count = (array_count_values($words));
  arsort($word_count);

  $result = array();
  foreach ($word_count as $key=>$val)
  {
    //echo "$key = $val. Density: ".number_format(($val/$numWords)*100)."%<br />\n";
    if(($val >= $min_density) && (strlen($key) > 2))
    if(isset($sd_seo_stop_words_list_flip) && !isset($sd_seo_stop_words_list_flip[$key]))
    if(!in_array($key, $result))
      $result[] = $key;
    if(count($result) >= $max_results) break;
  }
  if($asString)
    return implode(',',$result);
  else
    return $result;
} //sd_getkeywords


// ############################################################################
// SD_WORDWRAP
// ############################################################################
// this function will wordwrap an htmlentities string correctly

function sd_wordwrap($string, $width = 75, $break = '<br />', $cut = true)
{
  if(!isset($string) || !strlen($string) || empty($width) || !isset($break) || !strlen($break))
  {
    return $string;
  }
  else
  {
    $string = unhtmlspecialchars($string); // first decode the html
    $string = @wordwrap($string, $width, $break, !empty($cut));  // now wrap the string
    $string = htmlspecialchars($string, ENT_QUOTES); // switch back to htmlspecialchars
    return str_replace(htmlspecialchars($break, ENT_QUOTES), $break, $string);  // fix the line break and return string
  }

} //sd_wordwrap


// ####################### INTERNAL SYSTEM LOG MESSENGER #######################

function Watchdog($type, $message, $severity = WATCHDOG_NOTICE) // SD313
{
  global $DB, $database, $mainsettings, $userinfo, $sd_ignore_watchdog;

  if(empty($DB->database) || defined('INSTALLING_PRGM') || defined('UPGRADING_PRGM'))
    return;

  // Skip avoidable notices if possible
  if(empty($message) || empty($mainsettings['syslog_enabled']) || !isset($DB)
     || (stripos($message,'nbbc.php') !== false)
     || (stripos($message,'already defined') !== false)
     || (stripos($message,'undefined index') !== false)
     || (stripos($message,'undefined variable') !== false)
     || (stripos($message,'mainsettings') !== false)
     || (stripos($message,'ob_end_clean()') !== false) //SD360: XF issue
     || (stripos($message,'eval()\'d') !== false)
     || (stripos($message,'strict') !== false)
     || (stripos($message,'deprecated') !== false) //SD342
     )
  {
    if(!defined('DEBUG') || !DEBUG)
    return;
  }
  if(stripos($message,'nbbc.php') !== false) return; //SD342
  if(stripos($message,'/includes/tmpl/comp') !== false) return; //SD342
  if(stripos($message,'function.date') !== false) return; //SD342
  if(stripos($message,'function.getdate') !== false) return; //SD342
  if(stripos($message,'default_timezone') !== false) return; //SD360

  $username = isset($userinfo['username']) ? $userinfo['username'] : '';

  // Note: log the exact, entire absolute URL.
  if (isset($_SERVER['REQUEST_URI']))
  {
    $uri = $_SERVER['REQUEST_URI'];
  }
  else
  {
    if (isset($_SERVER['argv']))
    {
      $uri = $_SERVER['PHP_SELF'] .'?'. $_SERVER['argv'][0];
    }
    else
    {
      $uri = $_SERVER['PHP_SELF'] .'?'. $_SERVER['QUERY_STRING'];
    }
  }
  $uri = htmlentities(strip_alltags($uri)); //for security
  $time = ($severity == WATCHDOG_ERROR) ? (time()+1) : time();

  $prevDB = $DB->database;
  if($DB->database != $database['name']) $DB->select_db($database['name']);

  if(isset($DB) && is_object($DB) && $DB->conn)
  {
    $referer = CleanVar(isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'');
    $remote  = CleanVar(isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'');
    $remote  = preg_replace("/^::ffff:/", "", $remote); //SD370
    $DB->ignore_error = true;
    $DB->query("INSERT INTO {syslog} (username, type, message, severity, location, referer, hostname, timestamp)
    VALUES ('%s', '%s', '%s', %d, '%s', '%s', '%s', %d)",
    $DB->escape_string(substr($username,0,128)),
    $DB->escape_string(substr($type,0,32)),
    $DB->escape_string($message),
    (int)$severity,
    $DB->escape_string(substr($uri,0,255)),
    $DB->escape_string(substr($referer,0,255)),
    $DB->escape_string(substr($remote,0,255)),
    $time);
    $DB->ignore_error = false;
  }
  if(!empty($prevDB)) $DB->select_db($prevDB);

} //Watchdog

// ########################### GET LOCAL TIMEZONE #############################

// see comments: http://us2.php.net/manual/en/function.date-default-timezone-set.php
function sd_getLocalTimezone()
{
  static $zonelist = array(
    'Kwajalein' => -12.00,
    'Pacific/Midway' => -11.00,
    'Pacific/Honolulu' => -10.00,
    'America/Anchorage' => -9.00,
    'America/Los_Angeles' => -8.00,
    'America/Denver' => -7.00,
    'America/Tegucigalpa' => -6.00,
    'America/New_York' => -5.00,
    'America/Caracas' => -4.30,
    'America/Halifax' => -4.00,
    'America/St_Johns' => -3.30,
    'America/Argentina/Buenos_Aires' => -3.00,
    'America/Sao_Paulo' => -3.00,
    'Atlantic/South_Georgia' => -2.00,
    'Atlantic/Azores' => -1.00,
    'Europe/Dublin' => 0,
    'Europe/Amsterdam' => 1.00,
    'Europe/Belgrade' => 1.00,
    'Europe/Paris' => 1.00,
    'Europe/Minsk' => 2.00,
    'Asia/Kuwait' => 3.00,
    'Asia/Tehran' => 3.30,
    'Asia/Muscat' => 4.00,
    'Asia/Yekaterinburg' => 5.00,
    'Asia/Kolkata' => 5.30,
    'Asia/Katmandu' => 5.45,
    'Asia/Dhaka' => 6.00,
    'Asia/Rangoon' => 6.30,
    'Asia/Krasnoyarsk' => 7.00,
    'Asia/Brunei' => 8.00,
    'Asia/Seoul' => 9.00,
    'Australia/Darwin' => 9.30,
    'Australia/Canberra' => 10.00,
    'Asia/Magadan' => 11.00,
    'Pacific/Fiji' => 12.00,
    'Pacific/Tongatapu' => 13.00
  );
  $iTime = time();
  $arr = @localtime($iTime);
  $arr[5] += 1900;
  $arr[4]++;
  $iTztime = gmmktime($arr[2], $arr[1], $arr[0], $arr[4], $arr[3], $arr[5]);
  $offset = doubleval(($iTztime-$iTime)/(60*60));
  $index = array_keys($zonelist, $offset);
  if(sizeof($index)!=1)
    return false;
  return $index[0];
} //sd_getLocalTimezone

if(!function_exists('sd_natsortarrayname'))
{
function sd_natsortarrayname($str_a, $str_b)
{
  if(is_array($str_a) && is_array($str_b))
    return strnatcasecmp($str_a['name'], $str_b['name']);
  else
    return strnatcasecmp($str_a, $str_b);
}
}
if(!function_exists('sd_natsortarraynamereverse'))
{
function sd_natsortarraynamereverse($str_a, $str_b)
{
  if(is_array($str_a) && is_array($str_b))
    return -strnatcasecmp($str_a['name'], $str_b['name']);
  else
    return -strnatcasecmp($str_a, $str_b);
}
}


// ############################################################################
// DELETE PLUGIN COMMENTS
// ############################################################################
//SD370: moved here from "functions_admin.php"
function DeletePluginComments($pluginid, $objectid)
{
  if(empty($pluginid) || ($pluginid < 2) || ($pluginid > 99999999) ||
     !isset($objectid) || ($objectid < 0))
  {
    return;
  }

  global $DB;
  $DB->query('DELETE FROM {comments} WHERE pluginid = %d AND objectid = %d',$pluginid,$objectid);
  $DB->query('DELETE FROM {comments_count} WHERE plugin_id = %d AND object_id = %d',$pluginid,$objectid);

  //SD370: use new function DeletePluginLikes()
  DeletePluginLikes($pluginid, $objectid);

} //DeletePluginComments


// ############################################################################
// DELETE PLUGIN LIKES
// ############################################################################
function DeletePluginLikes($pluginid, $objectid) //SD370
{
  if(empty($pluginid) || ($pluginid < 2) || ($pluginid > 99999) ||
     !isset($objectid) || ($objectid < 0))
  {
    return;
  }

  //SD343: remove comment likes
  require_once(SD_INCLUDE_PATH.'class_sd_likes.php');
  SD_Likes::RemoveLikesForObject($pluginid, $objectid, false);

} //DeletePluginLikes


// ############################################################################
// DELETE PLUGIN RATINGS
// ############################################################################
//SD370: moved here from "functions_admin.php"
function DeletePluginRatings($pluginid, $rating_id='') //SD322
{
  if(empty($pluginid) || ($pluginid < 2) || ($pluginid > 99999))
  {
    return;
  }

  global $DB;
  $extraSQL = '';
  if(isset($rating_id) && strlen(($rating_id)))
  {
    $extraSQL = " AND rating_id = '%s'";
  }
  $DB->query("DELETE FROM {ratings} WHERE pluginid = %d" . $extraSQL,
             $pluginid, $DB->escape_string($rating_id));

} //DeletePluginRatings


// ############################################################################
// DELETE PLUGIN TAGS
// ############################################################################
/**
* Remove all types of tags for a given plugin and/or plugin item/object.
* Used when a plugin is being removed or a plugin needs to remove tags
* for deleted items.
* CAUTION: make absolutely sure to pass correct parameters!
* @global class_sd_tags.php
* @param int $pluginid  ID of the plugin (required)
* @param int $objectid  ID of the plugin item (required)
* @return none
*/
function DeletePluginTags($pluginid, $objectid=-1) //SD370
{
  if(empty($pluginid) || ($pluginid < 2) || ($pluginid > 99999))
  {
    return;
  }

  require_once(SD_INCLUDE_PATH.'class_sd_tags.php');
  SD_Tags::RemovePluginObjectTags($pluginid, $objectid);

} //DeletePluginTags


// ############################################################################

function sd_safe_mode() //SD370
{
  static $sd_safe_mode;
  if(isset($sd_safe_mode)) return $sd_safe_mode;
  $tmp = @ini_get('safe_mode');
  $sd_safe_mode = !empty($tmp) && version_compare(PHP_VERSION, '5.4.0', '<');
  return $sd_safe_mode;
}

// ############################################################################
// JSON alternatives (PHP < 5.2)
// ############################################################################
//SD370: use PEAR's json class to emulate PHP 5.2 functions
//Note: each only supporst first parameter, others are unused!
if(!function_exists('json_encode'))
{
  function json_encode($value, $options=0)
  {
    if(is_null($value)) return;
    $result = '';
    require_once(SD_INCLUDE_PATH.'JSON.php');
    $obj = new Services_JSON(SERVICES_JSON_SUPPRESS_ERRORS);
    $result = $obj->encode($value);
    unset($obj);
    return isset($result)?$result:null;
  }
}
if(!function_exists('json_decode'))
{
  function json_decode($json, $assoc=false, $depth=512, $options=0)
  {
    if(is_null($json) || !is_string($json) || !strlen($json)) return null;
    require_once(SD_INCLUDE_PATH.'JSON.php');
    $obj = new Services_JSON(SERVICES_JSON_SUPPRESS_ERRORS);
    $result = $obj->decode($json);
    unset($obj);
    return isset($result)?$result:null;
  }
}

function p_LC_TranslateObjectID($pluginid, $objectid1, $objectid2=0, $pageid=0, $fetchContent=false)
{
  //SD370: new table and cache for "{plugins_titles}", which contains
  // structural info to get titles for plugins' items (files,images,articles)
  // MOVED here from latest_comments.php! 2013-09-11
  global $DB, $plugins_titles_arr, $plugin_names;

  static $isSD370;

  if(empty($pluginid) || ($pluginid < 2) || empty($objectid1) || ($objectid1 < 1))
  {
    return false;
  }
  $title = $link  = '';
  $pluginid = (int)$pluginid;
  $objectid1 = (int)$objectid1;
  if(!isset($isSD370)) $isSD370 = (defined('SD_370') && SD_370);

  if($isSD370 && is_array($plugins_titles_arr) && isset($plugins_titles_arr[$pluginid]))
  {
    $data = $plugins_titles_arr[$pluginid];
    $DB->ignore_error = true;
    $DB->result_type = MYSQL_ASSOC;

    //SD370: special case for articles+clones to get full row for SEO generation
    $article_arr = false;
    $basename = isset($plugin_names['base-'.$pluginid]) ? $plugin_names['base-'.$pluginid] : '';
    if( isset($plugin_names[$pluginid]) &&
        (($pluginid==2) || ($basename == 'Articles')) )
    {
      if($article_arr = $DB->query_first('SELECT *'.
                                    ' FROM '.PRGM_TABLE_PREFIX.$data['tablename'].
                                    ' WHERE '.$data['id_column'].' = %d',
                                    $objectid1))
      {
        $title = $article_arr[$data['title_column']];
      }
    }
    else
    {
      if($title2 = $DB->query_first('SELECT '.$data['title_column'].
                                    ' FROM '.PRGM_TABLE_PREFIX.$data['tablename'].
                                    ' WHERE '.$data['id_column'].' = %d',
                                    $objectid1))
      {
        $title = $title2[$data['title_column']];
      }
    }
    $DB->ignore_error = false;

    // If page not given, try to find first page for plugin:
    if (empty($pageid))
    {
      $pageid = GetPluginCategory($pluginid,0);
    }

    // If page is found, build link to comments:
    if (!empty($pageid) && ($pageid > 0))
    {
      if (($pluginid==2) && !empty($article_arr))
      {
        $link = GetArticleLink($pageid, $pluginid, $article_arr, 0, false, '');
      }
      else
      {
        $link = '&p'.$pluginid.'_'.$data['id_column'].'='.$objectid1;
        $link = RewriteLink('index.php?categoryid='.$pageid.$link);
      }
    }

    // Get the content of a reported comment (objectid2)?
    $content = '';
    if(!empty($objectid2) && !empty($fetchContent))
    {
      if($basename == 'Forum')
      {
        if($title2 = $DB->query_first('SELECT ft.title, fp.post'.
                     ' FROM {p_forum_topics} ft'.
                     ' INNER JOIN {p_forum_posts} fp ON fp.topic_id = ft.topic_id'.
                     ' WHERE fp.post_id = %d',$objectid2))
        {
          $title = isset($title2['title']) ? $title2['title'] : $title;
          $content = $title2['post'];
        }
      }
      else
      {
        //SD370: fetch plugin's details to check if id2 is for a comment
        global $pluginbitfield;
        $DB->result_type = MYSQL_ASSOC;

        $plugin_install = $DB->query_first('SELECT * FROM {plugins}'.
                                           ' WHERE pluginid = %d',
                                           $pluginid);

        $settings = (int)$plugin_install['settings'];
        if($settings & $pluginbitfield['cancomment'])
        {
          $DB->result_type = MYSQL_ASSOC;
          if($tmp = $DB->query_first('SELECT comment FROM {comments}'.
                                     ' WHERE pluginid = '.(int)$pluginid.
                                     ' AND objectid = %d AND commentid = %d',
                                     $objectid1, $objectid2))
          {
            $content = $tmp['comment'];
          }
        }
      }
    }

    return array('title' => $title, 'link' => $link, 'content' => $content);
  }

  $link = empty($pageid)?false:RewriteLink('index.php?categoryid='.$pageid);
  return array('title' => '', 'link' => $link);

} //p_LC_TranslateObjectID


function sd_decodesetting($input='') //SD370
{
  //SD370: if installed, use mcrypt library, otherwise only base64
  if(!isset($input) || !strlen($input)) return '';

  $result = '';

  // Silence watchdog
  $olddog = $GLOBALS['sd_ignore_watchdog'];
  $GLOBALS['sd_ignore_watchdog'] = true;

  if(function_exists('mcrypt_encrypt') && function_exists('mcrypt_encrypt'))
  {
    if(false !== ($tmp = base64_decode($input)))
    {
      $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
      $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
      $result = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, PRGM_HASH, $tmp, MCRYPT_MODE_ECB, $iv);
      $result = rtrim($result,"\0\4"); // IMPORTANT!
    }
  }
  else
  {
    //SD370: otherwise just base64 encode it
    if(false !== ($tmp = base64_decode($input)))
    {
      $result = $tmp;
    }
  }
  $GLOBALS['sd_ignore_watchdog'] = $olddog;
  return $result;
} //sd_decodesetting


function sd_encodesetting($input='') //SD370
{
  //SD370: if installed, use mcrypt library, otherwise only base64
  if(!isset($input) || !strlen($input)) return '';

  $result = '';

  // Silence watchdog
  $olddog = $GLOBALS['sd_ignore_watchdog'];
  $GLOBALS['sd_ignore_watchdog'] = true;

  if(function_exists('mcrypt_get_iv_size') &&
     function_exists('mcrypt_encrypt'))
  {
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $enc = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, PRGM_HASH, $input, MCRYPT_MODE_ECB, $iv);
    $result = base64_encode($enc);
  }
  else
  {
    $result = base64_encode($input);
  }

  $GLOBALS['sd_ignore_watchdog'] = $olddog;
  return $result;
} //sd_encodesetting


/**
 * sd_inet_pton(), sd_inet_ntop(), sd_fetch_ip_range(), escape_binary
 * (c) MyBB, v1.8(?) branch (LGPL)
 * @link https://github.com/mybb/mybb/commit/5e6e1a9a8ae9cbb840fb305de05bb3dd8606b5bb
 * @author Stefan-ST
 */
/**
 * Converts a human readable IP address to its packed in_addr representation
 *
 * @param string The IP to convert
 * @return string IP in 32bit or 128bit binary format
 */
function sd_inet_pton($ip)
{
  if(function_exists('inet_pton'))
  {
    return @inet_pton($ip);
  }
  else
  {
    /**
     * Replace inet_pton()
     *
     * @category    PHP
     * @package     PHP_Compat
     * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
     * @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
     * @link        http://php.net/inet_pton
     * @author      Arpad Ray <arpad@php.net>
     * @version     $Revision: 269597 $
     */
    $r = ip2long($ip);
    if($r !== false && $r != -1)
    {
      return pack('N', $r);
    }

    $delim_count = substr_count($ip, ':');
    if($delim_count < 1 || $delim_count > 7)
    {
      return false;
    }

    $r = explode(':', $ip);
    $rcount = count($r);
    if(($doub = array_search('', $r, 1)) !== false)
    {
      $length = (!$doub || $doub == $rcount - 1 ? 2 : 1);
      array_splice($r, $doub, $length, array_fill(0, 8 + $length - $rcount, 0));
    }

    $r = array_map('hexdec', $r);
    array_unshift($r, 'n*');
    $r = call_user_func_array('pack', $r);

    return $r;
  }
}

/**
 * Converts a packed internet address to a human readable representation
 *
 * @param string IP in 32bit or 128bit binary format
 * @return string IP in human readable format
 */
function sd_inet_ntop($ip)
{
  if(function_exists('inet_ntop'))
  {
    return @inet_ntop($ip);
  }
  else
  {
    /**
     * Replace inet_ntop()
     *
     * @category    PHP
     * @package     PHP_Compat
     * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
     * @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
     * @link        http://php.net/inet_ntop
     * @author      Arpad Ray <arpad@php.net>
     * @version     $Revision: 269597 $
     */
    switch(strlen($ip))
    {
      case 4:
        list(,$r) = unpack('N', $ip);
        return long2ip($r);
      case 16:
        $r = substr(chunk_split(bin2hex($ip), 4, ':'), 0, -1);
        $r = preg_replace(
          array('/(?::?\b0+\b:?){2,}/', '/\b0+([^0])/e'),
          array('::', '(int)"$1"?"$1":"0$1"'),
          $r);
        return $r;
    }
    return false;
  }
}

/**
 * Fetch an binary formatted range for searching IPv4 and IPv6 IP addresses.
 *
 * @param string The IP address to convert to a range
 * @rturn mixed If a full IP address is provided, the in_addr representation, otherwise an array of the upper & lower extremities of the IP
 */
function sd_fetch_ip_range($ipaddress)
{
  // Wildcard
  if(strpos($ipaddress, '*') !== false)
  {
    if(strpos($ipaddress, ':') !== false)
    {
      // IPv6
      $upper = str_replace('*', 'ffff', $ipaddress);
      $lower = str_replace('*', '0', $ipaddress);
    }
    else
    {
      // IPv4
      $upper = str_replace('*', '255', $ipaddress);
      $lower = str_replace('*', '0', $ipaddress);
    }
    $upper = sd_inet_pton($upper);
    $lower = sd_inet_pton($lower);
    if($upper === false || $lower === false)
    {
      return false;
    }
    return array($lower, $upper);
  }
  // CIDR notation
  elseif(strpos($ipaddress, '/') !== false)
  {
    $ipaddress = explode('/', $ipaddress);
    $ip_address = $ipaddress[0];
    $ip_range = intval($ipaddress[1]);

    if(empty($ip_address) || empty($ip_range))
    {
      // Invalid input
      return false;
    }
    else
    {
      $ip_address = sd_inet_pton($ip_address);

      if(!$ip_address)
      {
        // Invalid IP address
        return false;
      }
    }

    /**
     * Taken from: https://github.com/NewEraCracker/php_work/blob/master/ipRangeCalculate.php
     * Author: NewEraCracker
     * License: Public Domain
     */

    // Pack IP, Set some vars
    $ip_pack = $ip_address;
    $ip_pack_size = strlen($ip_pack);
    $ip_bits_size = $ip_pack_size*8;

    // IP bits (lots of 0's and 1's)
    $ip_bits = '';
    for($i = 0; $i < $ip_pack_size; $i = $i+1)
    {
      $bit = decbin(ord($ip_pack[$i]));
      $bit = str_pad($bit, 8, '0', STR_PAD_LEFT);
      $ip_bits .= $bit;
    }

    // Significative bits (from the ip range)
    $ip_bits = substr($ip_bits, 0, $ip_range);

    // Some calculations
    $ip_lower_bits = str_pad($ip_bits, $ip_bits_size, '0', STR_PAD_RIGHT);
    $ip_higher_bits = str_pad($ip_bits, $ip_bits_size, '1', STR_PAD_RIGHT);

    // Lower IP
    $ip_lower_pack = '';
    for($i=0; $i < $ip_bits_size; $i=$i+8)
    {
      $chr = substr($ip_lower_bits, $i, 8);
      $chr = chr(bindec($chr));
      $ip_lower_pack .= $chr;
    }

    // Higher IP
    $ip_higher_pack = '';
    for($i=0; $i < $ip_bits_size; $i=$i+8)
    {
      $chr = substr($ip_higher_bits, $i, 8);
      $chr = chr( bindec($chr) );
      $ip_higher_pack .= $chr;
    }

    return array($ip_lower_pack, $ip_higher_pack);
  }
  // Just on IP address
  else
  {
    return sd_inet_pton($ipaddress);
  }
}

function escape_binary($string)
{
  global $DB;
  return $DB->escape_string(bin2hex($string));
}

/**
* Includes Libraries and Frameworks to front/back end
*
* @param string $framework
*/
function load_framework($framework)
{
	global $mainsettings;
	
	
	switch($framework)
	{
		case 'fontawesome':
			return '<link rel="stylesheet" href='.($mainsettings['fontawesome_cdn'] ? "https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css" : ROOT_PATH . "includes/css/font-awesome.min.css").'>';
		break;
		
		case 'bootstrap':
		 
		  if($mainsettings['load_bootstrap'] || (defined('IN_ADMIN') && IN_ADMIN))
		  {
		    echo 'here';
			  if($mainsettings['bootstrap_cdn'])
			  {
				return '
				<!-- Latest compiled and minified CSS -->
				<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
		
				<!-- Latest compiled and minified JavaScript -->
				<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>';
			  }
			  
			  else
			  {
				 return '
				<link rel="stylesheet" href="'.ROOT_PATH.'includes/css/bootstrap.min.css">
		
				<!-- Latest compiled and minified JavaScript -->
				<script src="'.ROOT_PATH.'includes/javascripts/bootstrap.min.js"></script>';
			  }
				  
		  }
		  else
		  
		 break;
	}
}


