<?php
if(!defined('IN_PRGM')) return;

if(!$plugin_folder = sd_GetCurrentFolder(__FILE__))
{
  return;
}

if(!defined('IN_ADMIN'))
{
  // ######################## FRONTPAGE-ONLY #########################
  $js_arr = array();
  $js_arr[] = $sdurl.MINIFY_PREFIX_G.'profile_front';
  $js_arr[] = $sdurl.'includes/javascript/jquery.timeentry.min.js';
  $lang = empty($mainsettings['lang_region']) ? 'en-GB' : $mainsettings['lang_region'];
  if(file_exists(SD_JS_PATH.'datetime/jquery.datepick-'.$lang.'.js'))
  {
    $js_arr[] = SD_JS_PATH.'datetime/jquery.datepick-'.$lang.'.js';
  }
  if(file_exists(SD_JS_PATH.'datetime/jquery.timeentry-'.$lang.'.js'))
  {
    $js_arr[] = SD_JS_PATH.'datetime/jquery.timeentry-'.$lang.'.js';
  }
  if(file_exists(SD_JS_PATH.'validate/messages_'.$lang.'.js'))
  {
    $js_arr[] = SD_JS_PATH.'validate/messages_'.$lang.'.js';
  }

  $css_arr = array(
    SD_CSS_PATH.'uni-form.css',
    SD_CSS_PATH.'default.uni-form.css',
    SD_CSS_PATH.'jquery.autocomplete.css',
    SD_CSS_PATH.'jquery.datepick.css',
    SD_CSS_PATH.'redmond.datepick.css',
    SD_JS_PATH.'markitup/skins/markitup/style.css',
    SD_JS_PATH.'markitup/sets/bbcode/style.css'
  );

  sd_header_add(array(
    'css'   => $css_arr,
    'js'    => $js_arr,
    'other' => array('
<script type="text/javascript">
//<![CDATA[
var sd_datetime_region = "'.addslashes($lang).'";
//]]>
</script>')));
  unset($css_arr,$js_arr);
}
else
{
  // ######################## ADMIN-ONLY #########################
  //SD362: after ajax-delete: clear attachment via ajax
  require_once(ROOT_PATH.'plugins/'.$plugin_folder.'/lib.php');

  $css_arr = array(
    SD_INCLUDE_PATH.'javascript/markitup/skins/markitup/style.css',
    SD_INCLUDE_PATH.'javascript/markitup/sets/bbcode/style.css'
  );
  $js_arr = array();
  if(!defined('SD_370')) //SD362: moved to core admin loader in 362+!
  {
    $css_arr[] = SD_INCLUDE_PATH.'css/jquery.jgrowl.css';
    $js_arr = array(
      ROOT_PATH.MINIFY_PREFIX_F.'includes/javascript/jquery.easing-1.3.pack.js',
      ROOT_PATH.MINIFY_PREFIX_F.'includes/javascript/jquery.jgrowl.min.js',
      ROOT_PATH.MINIFY_PREFIX_G.'bbcode'
    );
  }
  sd_header_add(array(
    'js'  => $js_arr,
    'css' => $css_arr,
    'other' => array('
<style type="text/css">
div.response-attachments ul { list-style:none; list-style-type: none; }
</style>
<script type="text/javascript">
//<![CDATA[
var fw_file_types = ["'.FIELD_FILE.'","'.FIELD_IMAGE.'","'.FIELD_MUSIC.'","'.FIELD_ARCHIVE.'"];
if(typeof jQuery !== "undefined") {
  function SortList(field_id, SortOrder, SortDirection){
    var form = $("form#sortform");
    if(form.length){
      $("input#action",form).val("sortfields");
      $("input#fieldid",form).val(field_id);
      $("input#currentSort",form).val(SortOrder);
      $("input#direction",form).val(SortDirection);
      form.submit();
    }
  }
  $(document).ready(function() {
    if (typeof myBbcodeSettings !== "undefined") {
      $("#intro_text, #success_text").markItUp(myBbcodeSettings);
    };
    $("form#addformpage").submit(function(e) {
      var pagesel = $("select",this);
      if(!pagesel.length || pagesel.val() < 1){
        e.preventDefault();
        return false;
      }
      return true;
    });
    $("select#field_type").change(function(e) {
      e.preventDefault();
      var newtype, isfile, doshow;
      newtype = $(this).val();
      isfile = $.inArray(newtype, fw_file_types);
      doshow = (isfile === -1 ? "none" : "block");
      $("div#filesettings").css("display", doshow);
      if(newtype == "'.FIELD_IMAGE.'"){
        $("input#allowed_fileext").val("gif,png,jpg,bmp");
      }
      if(newtype == "'.FIELD_MUSIC.'"){
        $("input#allowed_fileext").val("mp3,wma,ogg");
      }
      if(newtype == "'.FIELD_ARCHIVE.'"){
        $("input#allowed_fileext").val("zip,rar,7z,gz,bzip,arj,cab");
      }
      if(newtype == "'.FIELD_FILE.'"){
        $("input#allowed_fileext").val("*");
      }
      if(newtype == "'.FIELD_DOCUMENTS.'"){
        $("input#allowed_fileext").val("pdf,txt,doc,docx,xls,xlsx,xlm,ppt,pptx,odt,rtf,csv");
      }
    });
    $("div.response-attachments a.imgdelete").click(function(e) {
      e.preventDefault();
      if(confirm("'.addslashes($sdlanguage['common_attachment_delete_prompt']).'")) {
        var fid = $(this).parents("div:first").find("span").text();
        var rid = $("input[name=response_id]").val();
        var target = $(this).parents("td:first");
        var token  = $("input[name=form_token]").val();
        if(!target || !fid || !rid || !token) return false;

        $(target).load($(this).attr("href"), null,
          function(responseText, textStatus){
            if(textStatus=="success" && responseText=="1") {
              $(target).html("'.addslashes(AdminPhrase('no_file_uploaded')).'");
              $.jGrowl("'.addslashes($sdlanguage['common_attachment_deleted']).'", {
                easing: "swing", life: 2000,
                animateOpen:  { height: "show", width: "show" },
                animateClose: { height: "hide", width: "show" }
              });
              $.post(sdurl+"plugins/'.$plugin_folder.'/settings.php", {
                "action": "checkfile", "pid": '.(int)$pluginid.',
                "fid": fid, "rid": rid, "form_token": token });
            } else {
              $.jGrowl("'.addslashes($sdlanguage['common_attachment_delete_failed']).'", {
                easing: "swing", life: 2000,
                animateOpen:  { height: "show", width: "show" },
                animateClose: { height: "hide", width: "show" }
              });
            }
        });
      }
    });
  })
}
//]]>
</script>')));
}