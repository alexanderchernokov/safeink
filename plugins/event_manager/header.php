<?php
if(!defined('IN_PRGM')) return false;

if(!$plugin_folder = sd_GetCurrentFolder(__FILE__)) return false;
if(!$pluginid = GetPluginIDbyFolder($plugin_folder)) return false;

$evm_settings = GetPluginSettings($pluginid);
$evm_settings['row_color_1'] = empty($evm_settings['row_color_1']) ? '#fff' : $evm_settings['row_color_1'];
$evm_settings['row_color_2'] = empty($evm_settings['row_color_2']) ? '#fff' : $evm_settings['row_color_2'];
$evm_settings['row_color_3'] = empty($evm_settings['row_color_3']) ? '#fff' : $evm_settings['row_color_3'];

$js_arr = $css_arr = array();
$other = '';
$tmp = GetVar('action', '', 'string');

if(defined('ENABLE_MINIFY') && ENABLE_MINIFY)
{
  if(!defined('IN_ADMIN') || !defined('SD_370'))
  {
    $js_arr[] = ROOT_PATH . MINIFY_PREFIX_F.'includes/javascript/markitup/markitup-full.js';
    $js_arr[] = ROOT_PATH . MINIFY_PREFIX_F.'includes/javascript/markitup/sets/bbcode/set.js';
  }
  if(defined('IN_ADMIN') && ($tmp == 'displayevent'))
  {
    $js_arr[] = ROOT_PATH . MINIFY_PREFIX_G . 'plupload';
  }
}
else
{
  if(!defined('IN_ADMIN') || !defined('SD_370'))
  {
    $js_arr[] = SD_JS_PATH.'markitup/markitup-full.js';
    $js_arr[] = SD_JS_PATH.'markitup/sets/bbcode/set.js';
  }
  if(defined('IN_ADMIN') && ($tmp == 'displayevent'))
  {
    $js_arr[] = SD_INCLUDE_PATH.'plupload/plupload.full.js';
    $js_arr[] = SD_INCLUDE_PATH.'plupload/jquery.plupload.queue.js';
    $js_arr[] = SD_INCLUDE_PATH.'javascript/jquery.progressbar.js';
  }
}
if(!defined('IN_ADMIN') || !defined('SD_370'))
{
  $css_arr[] = SD_JS_PATH.'markitup/sets/bbcode/style.css';
  $css_arr[] = SD_JS_PATH.'markitup/skins/markitup/style.css';
}

if(defined('IN_ADMIN'))
{
  if($tmp == 'displayevent')
  {
    $css_arr[] = SD_INCLUDE_PATH.'plupload/css/plupload.queue.css';
  }
  $other = '
<style type="text/css">
textarea.eventmgr_bbcode { width: 99% }
div.uploader { padding: 4px }
</style>
';
}

sd_header_add(array(
  'js'    => $js_arr,
  'css'   => $css_arr,
  'other' => array($other.'
<script type="text/javascript">
jQuery(document).ready(function() {
(function($){'.
  # *** admin-only JS - START ***
  (defined('IN_ADMIN')?'
  $("form#events-list a.status_link").attr("unselectable","on")
    .css("MozUserSelect","none")
    .bind("dragstart", function(event) { event.preventDefault(); });
  $(document).delegate("form#events-list a.status_link","click",function(e){
    e.preventDefault();
    var pid = '.(int)$pluginid.'
    var elm = $(this).parent("div.status_switch");
    if(elm.length==0) return false;
    // find input which stores the actual value:
    var inp = elm.find("input:first");
    if(inp.length==0) return false;
    // get the actionnable item ("ea_", "ec_") and event id
    var track = $(inp).attr("name");
    track = track.split("_");
    var action = track[0];
    var eventid = parseInt(track[1],10);
    // switch value in form (0 to 1 / 1 to 0)
    var newval = 1 - inp.val();
    inp.val(newval);
    var token = $("form#events-list input:hidden[name=form_token]").val();
    $.post(sdurl+"plugins/'.$plugin_folder.'/settings.php", {
        "pluginid": pid,
        "action":   action,
        "eventid":  eventid,
        "newvalue": newval,
        "form_token": token },
      function(response, status, xhr){
        if(status !== "success" || response.substr(0,5)==="ERROR"){
          alert(response);
        } else {
          // toggle on/off status buttons in parent
          elm.find("a").each(function(){ $(this).toggle(); }).end();
          $.jGrowl(response, {
            easing: "swing", life: 2000,
            animateOpen:  { height: "show" },
            animateClose: { height: "hide", width: "show" }
          });
        };
    });
    return false;
  });':'').
  # *** admin-only JS - END ***
  '
  jQuery("textarea.eventmgr_bbcode").markItUp(myBbcodeSettings);
})(jQuery);
});
</script>
')
));
unset($evm_settings,$css_arr,$js_arr,$plugin_folder,$tmp);
