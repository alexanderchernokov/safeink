<?php
if(!defined('IN_PRGM') || !function_exists('sd_GetCurrentFolder')) return false;

if(defined('IN_ADMIN'))
{
  $load_wysiwyg = 0;
  sd_header_add(array(
    'css' => array(ROOT_PATH.'includes/css/jPicker-1.1.6.min.css'),
    'js'  => array(SD_JS_PATH.'jpicker-1.1.6.min.js')
  ));
  return true;
}

$plugin_folder = sd_GetCurrentFolder(__FILE__);
if($pluginid = GetPluginIDbyFolder($plugin_folder))
{
  $se_settings = GetPluginSettings($pluginid);
  $search_plugins = sd_ConvertStrToArray($se_settings['search_plugins'], ',');
  if(!empty($se_settings['use_autocomplete']) && in_array(2,$search_plugins))
  {
    sd_header_add(array('other' => array("
<script type=\"text/javascript\"> //<![CDATA[
String.prototype.trim = function () {
  return this.replace(/^\s*/, \"\").replace(/\s*$/, \"\");
}
if(typeof(jQuery) !== \"undefined\") {
  function urlEncodeCharacter(c) { return '%' + c.charCodeAt(0).toString(16); };
  function urlEncode(s) { return encodeURIComponent(s).replace( /\%20/g, '+').replace( /[!'()*~]/g, urlEncodeCharacter); };
  function search(searchString) {
    if(searchString.length < 3) {
      jQuery('#ac').hide(); /* hide suggestion box */
    } else {
      jQuery.post('plugins/search_engine/autocomplete.php', {acstring: urlEncode(searchString)}, function(data){
        if(data.length > 0) {
          jQuery('#ac').show();
          jQuery('#acList').html(data);
        }
        else {
          jQuery('#ac').hide();
        }
      });
    }
  };

  (function($){
  $(document).ready(function() {
   $(\"form#searchform\").submit(function() {
     var tmp_value = $(this).find(\"#searchString\").val();
     tmp_value = tmp_value.trim();
     if(tmp_value.length > 1) return true;
     return false;
   });
  });
  })(jQuery);
}
//]]>
</script>")));
  }
  unset($plugin_folder, $pluginid, $se_settings);
}