<?php
if(!defined('IN_PRGM')) return false;
if(defined('IN_ADMIN'))
{
  //v2.0.0: make sure that empty "source" options submit "-1" back
  $js_code = '
<script type="text/javascript">
jQuery(document).ready(function() {
  $("form#pluginsettings").on("submit", function(e){
    $("form#pluginsettings select").each(function(){
      if($(this).val() === null){
        $(this).val("-1");
      }
    });
  });
});
</script>';
  sd_header_add(array(
    'other' => array($js_code)
  ));

  return true; //nothing else for admin
}

if(!$plugin_folder = sd_GetCurrentFolder(__FILE__)) return false;
if(!$pid = GetPluginIDbyFolder($plugin_folder)) return false;

//v2.0.0: support for "year/month" slug type for search/tags pages
if(empty($mainsettings_modrewrite))
{
  $pCal_slug_arr = array(
    'id'    => -1,
    'year'  => GetVar('year',0,'whole_number'),
    'month' => GetVar('month',0,'whole_number'),
    'day'   => GetVar('day',0,'whole_number'));
}
else
if(!empty($url_variables) && (strpos($uri,$mainsettings_url_extension)===false))
{
  $pCal_slug_arr = CheckPluginSlugs($pid);
}

if(isset($pCal_slug_arr) && is_array($pCal_slug_arr) && !empty($pCal_slug_arr['id']))
{
  if(!empty($pCal_slug_arr['year']) && Is_Valid_Number($pCal_slug_arr['year'],0,1900,2050) &&
     !empty($pCal_slug_arr['month']) && Is_Valid_Number($pCal_slug_arr['month'],0,1,12) )
  {
    unset($_GET['p'.$pid.'_year']);
    unset($_GET['p'.$pid.'_month']);
    unset($_GET['p'.$pid.'_day']);
    defined('SD_TAG_DETECTED') || define('SD_TAG_DETECTED', true);
    define('P'.$pid.'_YEAR',  (int)$pCal_slug_arr['year']);
    define('P'.$pid.'_MONTH', (int)$pCal_slug_arr['month']);
    if(!empty($pCal_slug_arr['day']) && Is_Valid_Number($pCal_slug_arr['day'],0,1,31))
    {
      define('P'.$pid.'_DAY', (int)$pCal_slug_arr['day']);
    }
  }
}

$js_code = '';
if(!defined('P'.$pid.'_YEAR') && !empty($_GET['p'.$pid.'_year']) &&
   is_numeric($_GET['p'.$pid.'_year']))
{
  $js_code = '
<script type="text/javascript">
jQuery(document).ready(function() {
  var p'.$pid.'_top = jQuery("div#p'.$pid.'_month").offset().top;
  window.scrollTo(0,p'.$pid.'_top);
});
</script>';
}

$pCal_settings = GetPluginSettings($pid);
$css_array = array();
if(!empty($pCal_settings['style']) && strlen($pCal_settings['style']))
{
  $pCal_Style = strtolower($pCal_settings['style']);
  if(!(($pCal_Style=='advanced') || ($pCal_Style=='default') ||
       ($pCal_Style=='gravity')  || ($pCal_Style=='grey')))
  {
    $pCal_Style = 'default';
  }
  $css_array = array('plugins/'.$plugin_folder.'/css/'.$pCal_Style.'/'.$pCal_Style.'.css');
  unset($pCal_Style);
}

sd_header_add(array(
  'css'   => $css_array,
  'other' => array($js_code)
));

unset($css_array,$js_code,$pCal_settings,$pid);