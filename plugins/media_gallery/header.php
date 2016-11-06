<?php
if(!defined('IN_PRGM')) return false;

if(!$plugin_folder = sd_GetCurrentFolder(__FILE__))
{
  return;
}

// INCLUDE CORE SD MEDIA CLASS
require_once(SD_INCLUDE_PATH.'class_sd_media.php');

// INCLUDE MEDIA GALLERY LIBRARY
require_once(ROOT_PATH.'plugins/'.$plugin_folder.'/gallery_lib.php');

$formID = 'p'.$pluginid.'_upload_form';

//SD370: common JS for live ajax-driven media url checking (admin+frontpage)!
$mg_media_url_check_js =
  // SD370: trigger live check upon image click
  '
  jQuery(document).delegate("div.gallery_media_url_check a","click",function(e){
    e.preventDefault();
    var div = $(this).parent("div");
    div.find("input").trigger("blur", e);
  });'.
  // SD370: check media url and return type (returns class name)
  '
  jQuery("div.gallery_media_url_check input:first").blur(function(e){
    e.preventDefault();
    var upform = $("form#'.$formID.'");
    var div = $(this).parent("div");
    var media_edit = div.find("input:first");
    var token = upform.find("input[name=form_token]:hidden").val();
    var pid = upform.find("input[name=pluginid]:hidden").val();
    if(!upform || !div || !media_edit || !token || !pid) {
      div.find("a").hide();
      return false; }
    var mediaurl = media_edit.val().trim();
    jQuery("a",div).hide();
    jQuery("a#check_indicator",div).show();
    jQuery.post(sdurl+"plugins/'.$plugin_folder.'/media_gallery.php", {
        "pluginid": pid,
        "action": "check_media_url",
        "media_url": mediaurl,
        "form_token": token },
      function(response, status, xhr){
        div.find("a#check_indicator").hide();
        if(status !== "success" || response.substr(0,5)==="ERROR"){
          div.find("a.media_error").show();
          alert(response);
        } else {
          jQuery("a."+response,div).show();
        }
    });
  });
';

// ############################################################################
// START OF ADMIN-ONLY
// ############################################################################
if(defined('IN_ADMIN') && !empty($pluginid))
{
  $action       = GetVar('action', '', 'string');
  $customsearch = GetVar('customsearch', 0, 'bool');
  $clearsearch  = GetVar('clearsearch',  0, 'bool');

  if(empty($action) || ($action=='display_images') || ($action=='displayimages'))
  {
    // Restore previous search array from cookie
    $search_cookie_name = '_'.$pluginid.'_images_search';
    $search_cookie = isset($_COOKIE[COOKIE_PREFIX.$search_cookie_name])?$_COOKIE[COOKIE_PREFIX.$search_cookie_name]:false;

    if($clearsearch)
    {
      $search_cookie = false;
      $customsearch  = false;
    }

    if($customsearch)
    {
      $sectionid = GetVar('sectionid', 0, 'whole_number');
      $search = array(
        'image'     => GetVar('searchimage',   '', 'string', true, false),
        'status'    => GetVar('searchstatus',  'all', 'string', true, false),
        'width'     => GetVar('searchwidth',   'all', 'integer', true, false),
        'sectionid' => GetVar('searchsection', $sectionid, 'whole_number', true, false),
        'author'    => GetVar('searchauthor',  '', 'string', true, false),
        'limit'     => GetVar('searchlimit',   3, 'integer', true, false),
        'sorting'   => GetVar('searchsorting', 0, 'integer', true, false),
        'tag'       => GetVar('searchtag',     '', 'string', true, false)
      );
      if(empty($search['tag'])) $search['tag'] = GetVar('tag', '', 'string', false, true);
      $search['limit'] = Is_Valid_Number($search['limit'], 5, 3, 9999);
      // Store search params in cookie
      sd_CreateCookie($search_cookie_name, base64_encode(serialize($search)));
    }
    else
    {
      if($search_cookie !== false)
      {
        $search = @unserialize(@base64_decode(($search_cookie)));
      }
    }

    if(empty($search) || !is_array($search))
    {
      $search = array(
        'image'     => '',
        'status'    => 'all',
        'width'     => 'all',
        'sectionid' => 0,
        'author'    => '',
        'limit'     => 10,
        'sorting'   => '',
        'tag'       => ''
      );
      // Remove search params cookie
      sd_CreateCookie($search_cookie_name, '');
    }
    unset($search_cookie_name);
  }

  //SD360: keep track of images listing page
  $page_cookie_name = '_'.$pluginid.'_page';
  $gallery_page = !isset($_GET['page'])?false:Is_Valid_Number(GetVar('page', 1, 'whole_number',false,true),1,1);
  $page_cookie  = isset($_COOKIE[COOKIE_PREFIX.$page_cookie_name]) ? $_COOKIE[COOKIE_PREFIX.$page_cookie_name] : false;
  if(($gallery_page===false) && ($page_cookie !== false))
  {
    $gallery_page = Is_Valid_Number($page_cookie,1,1);
  }
  if($gallery_page===false)
  {
    $gallery_page = 1;
  }
  // Store page setting
  sd_CreateCookie($page_cookie_name, $gallery_page);
  $mg_lang = GetLanguage($pluginid);

  //SD370: - added autocomplete and datepicker files
  //SD370: - loading of JS files only when needed

  //SD370: most JS/CSS moved to core admin load (mainly functions_admin.php and
  // admin.css.php, some into min/grousConfig.php)!!!
  // e.g. easing, jgrowl, tag.editor, autocomplete
  // SD370: Jcrop jQuery plugin to live-crop an image
  $js_arr = array();
  if($action=='displayimageform')
  {
    if(defined('ENABLE_MINIFY') && ENABLE_MINIFY)
    {
	  $js_arr[] = ROOT_PATH.MINIFY_PREFIX_G.'plupload';
    }
    else
    {
		
      $js_arr = array(
        SD_INCLUDE_PATH.'plupload/plupload.full.min.js',
        SD_INCLUDE_PATH.'plupload/jquery.plupload.queue.min.js',
        SD_JS_PATH.'jquery.progressbar.min.js');
    }
    $js_arr[] = ROOT_PATH.MINIFY_PREFIX_F.'includes/javascript/jquery.Jcrop.min.js';
	$js_arr[] = ROOT_PATH.ADMIN_STYLES_FOLDER .'/assets/js/jquery-ui.min.js';
  }

  $lang = 'en-GB';
  if(!empty($action) && ($action==='displaysectionform'))
  {
    // First lets check if for the date and time entries also a regional JS
    // language file exists and add that to the page header HTML:
    $lang = empty($mainsettings['lang_region']) ? 'en-GB' : $mainsettings['lang_region'];
    if(!file_exists(SD_JS_PATH.'datetime/jquery.datepick-'.$lang.'.js'))
    {
      $lang = 'en-GB';
    }

    $js_arr[] = SD_JS_PATH.'datetime/jquery.datepick-'.$lang.'.js';
    if(file_exists(SD_JS_PATH.'datetime/jquery.timeentry-'.$lang.'.js'))
    {
      $js_arr[] = SD_JS_PATH.'datetime/jquery.timeentry-'.$lang.'.js';
    }
  }

  sd_header_add(array(
  'js'    => $js_arr,
  'css'   => array(
               SD_INCLUDE_PATH . 'plupload/css/plupload.queue.css',
               SD_CSS_PATH     . 'jquery.Jcrop.min.css',
			   SD_CSS_PATH . 'jquery.jdialog.css',
			   SD_CSS_PATH . 'jquery.datepick.css',
			   SD_CSS_PATH . 'jquery.tag.editor.css',
			   ROOT_PATH.ADMIN_STYLES_FOLDER. '/assets/css/jquery-ui.min.css',
			   SD_CSS_PATH . '/jPicker-1.1.6.min.css'
             ),
  'other' => array('
<style type="text/css">
label {clear: both; display: block}
ul#galmenu { height: 39px; }
ul#galmenu li a { font-size: 12px; }
ul#galmenu li a:hover {background-position: 100% -40px; }
ul#galmenu li.mega-unit { max-height: 350px; overflow-x: hidden; overflow-y: scroll; }
ul#galmenu li .sub li { width: 235px; }
ul#galmenu li .sub li.mega-hdr a.mega-hdr-a,
ul#galmenu .sub li.mega-hdr li a,
ul#galmenu li .sub .row li a { font-size: 11px; }
ul#galmenu ul#gal_filter,
ul#galmenu ul#gal_sections { visibility: hidden; }
hr { clear:both;display:block;border-size:1px;border-bottom:1px;height:1px;padding:0;margin:4px 0 4px 0; }
.td_tl { background-color: #fff; padding: 2px 2px 0px 8px; border-right: 0; border-bottom: 0; border-left:  1px solid red; border-top: 1px solid red; }
.td_tr { background-color: #fff; padding: 2px 2px 0px 8px; border-left:  0; border-bottom: 0; border-right: 1px solid red; border-top: 1px solid red; }
.td_bl { background-color: #fff; padding: 2px 2px 4px 8px; border-right: 0; border-top: 0; border-left:  1px solid red; border-bottom: 1px solid red; }
.td_br { background-color: #fff; padding: 2px 2px 4px 8px; border-left:  0; border-top: 0; border-right: 1px solid red; border-bottom: 1px solid red; }
a.status_link_small { /* SD370 */
  color: #fff !important;
  cursor: pointer;
  float: left;
  font-weight: bold;
  height: 16px;
  margin: 2px;
  padding: 3px 5px 3px 5px;
  text-align: center;
  white-space: nowrap;
}
/* SD370: media url upload form styles */

div.bbcode_preview { border: 1px solid #d0d0d0; margin: 4px; padding: 6px; }
div.bbcode_preview li { list-style-position: inside; padding: 4px; }
a.btn:hover { outline: none; text-decoration:none; }
form.mg_form_options img {
  border: 1px solid #DDDDDD;
  border-radius: 6px;
  margin: 2px 0 5px;
  padding: 4px;
  vertical-align: middle;
}
#mg_image { max-width: 700px; max-height: 400px; }
#mg_options { list-style-type:none }
#mg_options li { list-style:none; margin-bottom: 8px}
div#cropdata { border:1px solid #d0d0d0; padding: 8px; width: 140px}
div#cropdata div * { font: 12px "Lucida Console", "Courier New", monospace !important; font-weight: bold; }
</style>
<script type="text/javascript">
//<![CDATA[
if(typeof(jQuery) !== "undefined") {

function padLeft(nr, n, str) {
  return Array(n-String(nr).length+1).join(str||" ")+nr;
}

function hexFromRGB(r, g, b) {
  var hex = [
    r.toString( 16 ),
    g.toString( 16 ),
    b.toString( 16 )
  ];
  $.each( hex, function( nr, val ) {
    if ( val.length === 1 ) {
      hex[ nr ] = "0" + val;
    }
  });
  return hex.join( "" ).toUpperCase();
}

function ShowBlocker() {
  jQuery("div.contextual_dialog_content").block({
    timeout: 1500, showOverlay: false,
    css: {
      backgroundColor: "#000",
      border: "1px solid #808080",
      color: "#fff", fadeOut: 100,
      fontSize: "18px", fontWeight: "bold",
      "border-radius": "10px",
      "-moz-border-radius": "10px",
      "-webkit-border-radius": "10px",
      opacity: .6, padding: "15px",
      width: 300
    }
  });
}

function ApplyColorPicker() {
  if(typeof(jQuery.fn.jPicker) !== "undefined") {
    jQuery("div.jPicker").remove(); /* remove extra layer */
    jQuery("#mg_options_popup input.color").jPicker({
      images: { clientPath: sdurl+"includes/css/images/jpicker/"},
      window: { position: { y: "bottom"} }
    }).addClass("jPickered");
  }
}

jQuery(document).ready(function() {
(function($){
	
	if(typeof($.fn.tagEditor) !== "undefined") {
    $(".tags").tagEditor({
      completeOnSeparator: true,
      completeOnBlur: true,
      confirmRemoval: true,
      separator: ",",
      confirmRemovalText: "'.addslashes($sdlanguage['common_remove_tag']).'"
    });
  }

  $("td select:not([multiple=\"\"])").css({
    fontSize: "12px", "min-height": "20px", padding: "3px", margin: "2px"
  });

  if(typeof($.fn.ceebox) !== "undefined") {
    $(".ceebox").ceebox({
      animSpeed: "fast",
      borderWidth: "2px",
      overlayOpacity: 0.7,
      html: false,
      htmlGallery: false,
      imageGallery: true,
      margin: "100",
      padding: "14",
      titles: false,
      itemCaption: "",
      onload: function(){ jQuery(".markItUpHeader").hide(); },
      unload: function(){ jQuery(".markItUpHeader").show(); }
    });
  }

  $("form#searchimages select").change(function() {
    $("#searchimages").submit();
  });

  $("a#images-submit-search").click(function(e) {
    e.preventDefault();
    $("#searchimages").submit();
  });

  $("a#images-clear-search").click(function(e) {
    e.preventDefault();
    var form = $("#searchimages");
    form.find("input").val("");
    form.find("select").prop("selectedIndex", 0);
    form.find("input[name=clearsearch]").val("1");
    form.submit();
  });

  $(document).delegate("form#media_list a.status_link","click",function(e){
    e.preventDefault();
    var pid = $("form#media_list").find("input[name=pluginid]:hidden").val();
    var div = $(this).parent("div");
    var inp = div.find("input:first");
    var newval = 1 - parseInt(inp.val(),10);
    inp.val(newval);
    elm = div;
    elm = elm.parent("td").parent("tr").find("td:first input:hidden[name*=mediaids]");
    if(elm.length===0) return;
    var imageid = elm.val();
    var token = $("form#media_list input[name=form_token]").val();
    $.post(sdurl+"plugins/'.$plugin_folder.'/gallery_settings.php", {
        "pluginid": pid,
        "action": "setimagestatus",
        "imageid": imageid,
        "imagestatus": newval,
        "form_token": token },
      function(response, status, xhr){
        if(status !== "success" || response.substr(0,5)==="ERROR"){
          alert(response);
        } else {
          inp.val(newval);
          div.find("a").each(function(){ $(this).toggle(); }).end();
          var n = noty({
					text: response,
					layout: "top",
					type: "success",	
					timeout: 5000,					
					});
        }
    });
  });

  if(typeof($.fn.autocomplete) !== "undefined") {
    jQuery("input#UserSearch").blur(function(){
      var newuser = $(this).val();
      newuser = parseInt(newuser,10);
      jQuery("input#newOwnerID").val(newuser);
    }).autocomplete({
       source: sdurl+"includes/ajax/getuserselection.php",
      useCache: false,
      onItemSelect: function(item) {
        if(item.data.length) {
          var user_id = 0, old_id = 0;
          user_id = parseInt(item.data,10);
          // Assign selected, new owner id
          if(user_id > 0) { jQuery("input#newOwnerID").val(user_id); }
        }
      },
      maxItemsToShow: 10, 
	   minLength: 2,
    });
  }


  /* SD360: instant on-/offline switching for media files */
  /* SD370: make text within tags not "selectable" */
  $("form#media_list a.status_link,ul.tagEditor li").attr("unselectable", "on").css("MozUserSelect","none").bind("dragstart", function(event) { event.preventDefault(); });
'.
//SD370: JS code for other admin pages:
'
  /* SD370: owner name autocomplete */
  org_author_id = jQuery("input#owner_id").val();
  org_author_id = parseInt(org_author_id,10);

  if(typeof($.fn.markItUp) !== "undefined") {
    $(".bbeditor").markItUp(myBbcodeSettings);
    $(".bbeditor").css("width", "99%");
  }
  

  /* SD370: date/time picker for publish start/end fields */
  if (typeof($.fn.datepick) !== "undefined") {
    $.datepick.setDefaults($.datepick.regional["'.$lang.'"]);

    var timeEntryOptions = {
      show24Hours: true,
      separator: ":",
      spinnerImage: sdurl+"includes/css/images/spinnerOrange.png"
    };
    var datePickerOptions = {
      yearRange: "2010:2099",
      altFormat: "yyyy-mm-dd",
      showTrigger: \'<i class="ace-icon fa fa-calendar bigger-120"></i>\'
    };

    $("#timestart,#timeend").timeEntry(timeEntryOptions);

    var a_updated = false;

    dvalue = $("#datestart2").attr("rel") * 1000;
    dc = new Date(dvalue);
    $("#datestart2").datepick($.extend(datePickerOptions, { altField: "#datestart"}));
    if(dc > 0) {
      $("#datestart2").datepick("setDate", dc);
    }

    dvalue = $("#dateend2").attr("rel") * 1000;
    dc = new Date(dvalue);
    $("#dateend2").datepick($.extend(datePickerOptions, { altField: "#dateend"}));
    if(dc > 0) {
      $("#dateend2").datepick("setDate", dc);
    }

    $("#datestart2,#dateend2").blur(function() {
      a_updated = $(this).val();
      if(!a_updated || a_updated === "") {
        $(this).attr("rel", "0");
        $(this).prev("input").val("0");
      }
    });
  }

  /* #########################################################
     PREVIEW IMAGE / IMAGE FILTERS PROCESSING
     ######################################################### */

  /* Reload original image into "preview" image */
  $(document).delegate(".mg_filter_reset","click",function(e){
    e.preventDefault;
    if(image_source_original !== "") {
      var ts = Math.round(new Date()/1000);
      $("img#mg_image").attr("src", image_source_original+"?"+ts);
      $(".mg_form_options").clearForm();
    }
    return false;
  });

  $(document).delegate("a.mg_filter_preview","click",function(e){
    e.preventDefault;
    $(".mg_form_options").submit();
    return false;
  });

  /* SD370: using "maskedinput" jQuery plugin */
  $.mask.definitions["~"] = "[+-9]";
  $.mask.definitions["h"] = "[A-Fa-f0-9]";

  /* SD370: live image filter options with preview/reset */
  var image_source_original = $("img#mg_image").attr("src");
  var mg_container = $("#mg_options_container");
  var jcrop_api;

  /* Crop button action: jWindowCrop jQuery plugin */
  $("#mg_crop_apply").click(function(e){
    e.preventDefault();

    if(jcrop_api === null || typeof jcrop_api === "undefined") {
      $("#mg_filter_options_btn").show("fast");
      return false;
    }

    if(!confirm("'.addslashes($sdlanguage['common_msg_crop_confirm_js']).'")) return false;

    var c = jcrop_api.tellSelect();
    var queryString = "filter=1&adm=1&save=1&crop=1&x1="+c.x+"&y1="+c.y+"&x2="+c.x2+"&y2="+c.y2+"&w="+c.w+"&h="+c.h;
    var form_token = $("#'.$formID.' input[name=form_token]").val();
    var imageid = $("#'.$formID.' input[name=imageid]").val();

    ShowBlocker();
    $("#mg_filter_crop_btn").trigger("click");
    $("#mg_image").attr("src", "");

    jQuery.post("'.$sdurl.'plugins/'.$plugin_folder.'/img.php?"+queryString,
      { "formtoken": form_token,
        "imageid": imageid },
      function(response, status, xhr){
        if(status !== "success" || response.substr(0,5)==="ERROR"){
          alert(response);
        } else {
        }
        $(".mg_filter_reset").trigger("click");
        $("#mg_image").css({ width: c.w, height: c.h });
        $("#mg_image_dimensions").text(c.w+"px / "+c.h+"px");
    });
    return false;
  });

  $("#mg_filter_crop_btn").click(function(e){
    e.preventDefault();

    if(jcrop_api !== null && typeof jcrop_api !== "undefined") {
      $("#mg_filter_crop_btn span").text("'.addslashes($sdlanguage['common_media_start_cropping']).'");
      $("#cropdata").css({ display: "none" });
      $("#mg_filter_options_btn").show("fast");
      jcrop_api.destroy();
      jcrop_api = null;
      return false;
    }

    $("#mg_filter_options_btn").hide("fast");
    $("#mg_filter_crop_btn span").text("'.addslashes($sdlanguage['common_media_stop_cropping']).'");
    jDialog.close();
    $("#cropdata").css({ display:"inline-block" });
    var imgw = $("#mg_image").width();
    var imgh = $("#mg_image").height();
    var crop_options = {
      bgColor: "#d0d0d0",
      boxWidth: imgw, boxHeight: imgh,
      maxSize: 50, minSize: 50,
      setSelect: [ 50, 50, 150, 150 ],
      onSelect: function(){ },
      onChange: function(c){
        $("span#crop_x1").text(padLeft(c.x,3,"0"));
        $("span#crop_y1").text(padLeft(c.y,3,"0"));
        $("span#crop_x2").text(padLeft(c.x2,3,"0"));
        $("span#crop_y2").text(padLeft(c.y2,3,"0"));
        $("span#crop_w").text(padLeft(c.w,3,"0"));
        $("span#crop_h").text(padLeft(c.h,3,"0"));
      }
    };
    $("img#mg_image").Jcrop(crop_options,function(){
      jcrop_api = this;
    });

    return false;
  });

  /* Show options upon button click via jDialog */
  $("#mg_filter_options_btn").click(function(e){
    e.preventDefault();
    jDialog.close();
    $(this).jDialog({
      close_on_body_click: false,
      content   : mg_container.html(),
      idName    : "mg_options_popup",
      title     : "<b>'.addslashes($sdlanguage['common_media_image_filters']).'<\/b>",
      top_offset: -33,
      width     : 400
    });

    ApplyColorPicker();

    /* Apply input masks */
    $(".mg_brightness, .mg_contrast").mask("~9?99");
    $(".mg_colorize").mask("hh");
    $(".mg_rotate, .mg_smooth").mask("9?99");
    $(".mg_gamma").mask("9?.99");
    $(".mg_pixelate").mask("9?9");
    $(".colorx").mask("hhhhhh");

    return false;
  });

  /* Process preview, then assign new "src" to preview image */
  $(document).delegate("form.mg_form_options","submit",function(event) {
    event.preventDefault();
    ShowBlocker();
    var queryString = $(this).formSerialize()+"&filter=1&adm=1";
    queryString = queryString.replace(/\_+/,""); /* remove placeholder! */
    var ts = Math.round(new Date()/1000);
    var newurl = "'.$sdurl.'plugins/'.$plugin_folder.'/img.php?"+queryString+"&t="+ts;
    $("#mg_image").attr("src", newurl);
    return false;
  });

  /* Apply options to original image and reload image */
  $(document).delegate("a.mg_filter_apply","click",function(event) {
    event.preventDefault();
    if(!confirm("'.addslashes($sdlanguage['common_msg_filters_confirm_js']).'")) return false;
    ShowBlocker();
    $("#mg_image").attr("src", "");
    var queryString = $("#mg_options_popup form").formSerialize()+"&filter=1&adm=1&save=1";
    queryString = queryString.replace(/\_+/,""); /* remove placeholder! */
    var ts = Math.round(new Date()/1000);
    var newurl = "'.$sdurl.'plugins/'.$plugin_folder.'/img.php?"+queryString+"&t="+ts;
    $("#mg_image").attr("src", newurl);
    jQuery.post(newurl, { },
      function(response, status, xhr){
        if(status !== "success" || response.substr(0,5)==="ERROR"){
          alert(response);
        } else {
          $(".mg_filter_reset").trigger("click");
        }
    });
    return false;
  });

  '.$mg_media_url_check_js. /* SD370 */ '
})(jQuery);
});
}
//]]>
</script>')
  ), false);

  return true;
}
// #############
// END OF ADMIN
// #############

// ############################################################################
// FRONTPAGE-ONLY HEADER
// ############################################################################

#used globals: $mainsettings_tag_results_page, $mainsettings_search_results_page, $uri;

$pluginid = GetPluginIDbyFolder($plugin_folder);
// Store current plugin header instance as reference
if(!isset($sd_instances)) $sd_instances = array();
$sd_instances[$pluginid] = new GalleryBaseClass($plugin_folder);

// Check for global plugin header instance collection (by reference!)
$media_gallery_base = & $sd_instances[$pluginid];

// Init frontpage processing of plugin
$result = $media_gallery_base->InitFrontpage(true);

if(!$result ||
   (empty($media_gallery_base->IsSiteAdmin) && empty($media_gallery_base->IsAdmin) &&
    (empty($media_gallery_base->sectionid) ||
     (!empty($media_gallery_base->sectionid) &&
      isset($media_gallery_base->section_arr['can_view']) &&
      empty($media_gallery_base->section_arr['can_view'])))))
{
  // reset relevant url params and send error header
  if(isset($_GET[$media_gallery_base->pre.'_imageid']))
     unset($_GET[$media_gallery_base->pre.'_imageid']);
  if(isset($_GET[$media_gallery_base->pre.'_sectionid']))
     unset($_GET[$media_gallery_base->pre.'_sectionid']);
  if(!headers_sent())
  {
    if(empty($media_gallery_base->sectionid))
      @header("HTTP/1.0 404 Not Found");
    else
      @header("HTTP/1.0 403 Forbidden");
  }
  //Do not exit here, let main plugin file take care of rest!
}

$meta_arr = $meta_prop_arr = array();

$mg_action = GetVar('p'.$media_gallery_base->pluginid.'_action','','string');

$js = '';
if(($mg_action!='insertmedia') &&
   ($media_gallery_base->sectionid || $media_gallery_base->imageid)) //SD342: for scrolling page to image
{
  $js = '
  var '.$media_gallery_base->pre.'_offset = jQuery("#'.$media_gallery_base->pre.'_imagegallery").offset();
  if('.$media_gallery_base->pre.'_offset) { window.scrollTo(0,'.$media_gallery_base->pre.'_offset.top); }';
}

//SD370: support for "Tags" page; check for slugs...
$media_page_text = '';
if(($mg_media_page = GetVar('p'.$media_gallery_base->pluginid.'_start', 0, 'whole_number', false, true)) > 1)
{
  $media_page_text = $media_gallery_base->language['meta_page_phrase'];
  $media_page_text = ' '.str_replace('[page]', $mg_media_page, $media_page_text);
}

//SD370: detect if search param from Search Engine plugin is present when on
// search results page to avoid display of results
$media_gallery_base->doSearch = false;
$media_gallery_base->SearchTerm = '';
if(!empty($mainsettings_search_results_page) &&
   ($categoryid == $mainsettings_search_results_page) )
{
  $q = GetVar('q', '', 'string');
  if((GetVar('action', '', 'string')=='search') && !empty($q))
  {
    $media_gallery_base->SearchTerm = trim(sd_substr(urldecode($q),0,100));
    $media_gallery_base->doSearch = true;
  }
  unset($q);
}

if(!empty($url_variables) && (strpos($uri,$mainsettings_url_extension)===false) &&
   (!empty($mainsettings_tag_results_page) || !empty($mainsettings_search_results_page)) )
{
  $media_gallery_base->slug_arr = CheckPluginSlugs($media_gallery_base->pluginid);
  if(!empty($media_gallery_base->slug_arr) && is_array($media_gallery_base->slug_arr))
  {
    if(!empty($media_gallery_base->slug_arr['year']) &&
       Is_Valid_Number($media_gallery_base->slug_arr['year'],0,2000,2050) &&
       !empty($media_gallery_base->slug_arr['month']) &&
       Is_Valid_Number($media_gallery_base->slug_arr['month'],0,1,12) )
    {
      $media_gallery_base->isTagPage = true;
      $mg_tmp = strip_tags(str_replace(array('[month]','[year]'),
                             array($sd_months_arr[$media_gallery_base->slug_arr['month']],
                                   sprintf('%04d',$media_gallery_base->slug_arr['year'])),
                             $media_gallery_base->language['media_year_month_head']));
      sd_header_add(array(
        'meta'  => array(
          'title' => ($mg_tmp.' '.$media_gallery_base->slug_arr['year'].$media_page_text),
          'description' => ($mg_tmp.$media_page_text)
        )));
    }
    else
    if(!empty($media_gallery_base->slug_arr['key']) && !empty($media_gallery_base->slug_arr['value']))
    {
      $media_gallery_base->isTagPage = true;
      $mg_tmp = strip_tags(str_replace(array('"','&quot;','[tag]'),
                                       array('','',$media_gallery_base->slug_arr['value']),
                                       $media_gallery_base->language['media_tags_head']));
      sd_header_add(array(
        'meta'  => array(
          'title' => ($mg_tmp.$media_page_text),
          'description' => ($mg_tmp.$media_page_text)
        )));
    }
    unset($mg_tmp);
  }
}
else
if(strlen($media_page_text))
{
  //SD370: expand meta data with page as suffix
  sd_header_add(array(
    'meta'  => array(
      'title_suffix' => $media_page_text,
      'description_suffix' => $media_page_text
    )));
}
else
//SD360: process meta data of Section
if(!empty($media_gallery_base->imageid) && !empty($media_gallery_base->image_arr))
{
  $meta_arr['keywords'] = '';
  $meta_arr['title'] = rtrim($media_gallery_base->section_arr['name'] .' - '.
                             $media_gallery_base->image_arr['title']);
  $meta_arr['description'] = $media_gallery_base->image_arr['description'];
  $tmp_meta = $meta_arr['description'].' '.
              $media_gallery_base->image_arr['title'];
  if(!empty($media_gallery_base->section_arr['description']))
  {
    $meta_arr['description'] .= ' '.$media_gallery_base->section_arr['description'];
  }
  if(!empty($media_gallery_base->section_arr['metadescription']))
  {
    $tmp_meta .= ' '.$media_gallery_base->section_arr['metadescription'];
  }
  $tmp_meta = preg_replace('#\[[^\[]*\]#m','',$tmp_meta); // remove BBCode tags
  $tmp_meta = trim(strip_alltags(preg_replace(array('/&quot;/','/&amp;quot;/','/&#039;/','#\s+#m','#\.\.+#m','#\,#m'),array(' ',' ',"'",' ','.',' '), $tmp_meta)));
  if(strlen($tmp_meta))
  {
    $descr_keywords = array_unique(array_slice(array_filter(explode(' ', $tmp_meta)),0,30));
    $meta_arr['description'] = trim(implode(' ', $descr_keywords));
    $meta_arr['keywords'] = sd_getkeywords($tmp_meta,true,1,10);
  }
  //SD370: add extra meta data commonly used for videos/images
  $meta_prop_arr['og:title'] = sd_substr(str_replace('"','&quot;',strip_tags(sd_unhtmlspecialchars($media_gallery_base->image_arr['title']))),0,128);
  $meta_prop_arr['og:site_name'] = $mainsettings_websitetitle;
  $meta_prop_arr['og:url'] = $media_gallery_base->RewriteImageLink($media_gallery_base->sectionid,$media_gallery_base->imageid,$media_gallery_base->image_arr['title']);
  if(!empty($meta_arr['description']))
  {
    $meta_arr['twitter:description'] = $meta_arr['description'];
    $meta_prop_arr['og:description'] = $meta_arr['description'];
  }
  $folder = isset($media_gallery_base->image_arr['folder'])?$media_gallery_base->image_arr['folder']:'';
  $folder .= (strlen($folder) && sd_substr($media_gallery_base->image_arr['folder'],-1)!='/' ? '/' : '');
  $imgpath = $media_gallery_base->IMAGEPATH.$folder.$media_gallery_base->image_arr['filename'];
  $sdi = new SD_Image(ROOT_PATH.$imgpath);
  if($sdi->getImageValid())
  {
    $meta_prop_arr['og:image'] = $meta_prop_arr['og:image:url'] = $media_gallery_base->IMAGEURL.$folder.$media_gallery_base->image_arr['filename'];
    $meta_prop_arr['og:image:width'] = $sdi->getImageWidth();
    $meta_prop_arr['og:image:height'] = $sdi->getImageHeight();
    $meta_prop_arr['og:image:type'] = $sdi->getMimeType();
    $meta_arr['twitter:image'] = $meta_prop_arr['og:image'];
  }
  if(!empty($media_gallery_base->image_arr['media_type']))
  {
    $meta_prop_arr['og:type'] = 'video.other';
    if(!empty($media_gallery_base->image_arr['px_width']))
      $meta_prop_arr['og:video:width'] = $media_gallery_base->image_arr['px_width'];
    if(!empty($media_gallery_base->image_arr['px_height']))
      $meta_prop_arr['og:video:height'] = $media_gallery_base->image_arr['px_height'];
    $meta_arr['twitter:player'] = $meta_prop_arr['og:url'];
    $meta_arr['twitter:title'] = $meta_prop_arr['og:title'];
    $meta_arr['twitter:card'] = 'player';
    if(!empty($media_gallery_base->image_arr['px_width']) && !empty($media_gallery_base->image_arr['px_height']))
    {
      $meta_arr['twitter:player:width'] = $media_gallery_base->image_arr['px_width'];
      $meta_arr['twitter:player:height'] = $media_gallery_base->image_arr['px_height'];
    }
    if(!empty($meta_arr['twitter:player'])) $meta_prop_arr['og:video'] = $meta_arr['twitter:player'];
  }
  unset($folder,$imgpath,$sdi,$tmp_meta,$descr_keywords);
}
else
if(!empty($media_gallery_base->sectionid) && !empty($media_gallery_base->section_arr))
{
  $meta_arr['title'] = $media_gallery_base->section_arr['name'];
  $meta_arr['description'] = $media_gallery_base->section_arr['metadescription'];
  $meta_arr['keywords'] = $media_gallery_base->section_arr['metakeywords'];
}

echo 'robb';
sd_header_add(array(
  'meta'      => $meta_arr,
  'meta_prop' => $meta_prop_arr,
  'css'       => array( SD_CSS_PATH . 'jquery.tag.editor.css' ),
  'js'        => array( SD_JS_PATH  . 'jquery.tag.editor.js' ),
  'other'     => array('
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function(){
  jQuery("#'.$media_gallery_base->pre.'_sectionid").change(function(){
    if(this.value > 0) {
      var link = jQuery("form#'.$media_gallery_base->pre.'_FormMenu").attr("action");
      if(this.value > 1) {
        link += ((link.indexOf("?") === -1) ? "?" : "&") + "'.$media_gallery_base->pre.'_sectionid=" + this.value;
      }
      window.location = link;
    }
  });
  jQuery("input#'.$media_gallery_base->pre.'_tags").tagEditor({
    completeOnSeparator: true,
    completeOnBlur: true,
    confirmRemoval: true,
    separator: ",",
    confirmRemovalText: "'.addslashes($sdlanguage['common_remove_tag']).'"
  });
  jQuery(".'.$media_gallery_base->pre.'_sections .section_inner img").hover(function () {
    jQuery(this).parent().stop(false, false).animate({ opacity: 0.75 }, 500);
  }, function () {
    jQuery(this).parent().stop(false, false).animate({ opacity: 1 }, 500);
  });
  jQuery("textarea#'.$media_gallery_base->pre.'_description").markItUp(myBbcodeSettings);
  jQuery("textarea#'.$media_gallery_base->pre.'_description").css("width", "98%");'.$js.'
  '.$mg_media_url_check_js.'
});
//]]>
</script>
')));


// Load additional files as needed by display mode option:
$disp_mode = empty($media_gallery_base->section_arr['display_mode'])?0:$media_gallery_base->section_arr['display_mode'];
if(($mg_action!='insertmedia') && !$media_gallery_base->isTagPage)
switch($disp_mode)
{
  // Ceebox:
  case 2:
  {
    sd_header_add(array(
      'js'  => array(
        'jquery.ceebox-min.js'
      ),
      'css' => array(
        'includes/css/ceebox.css'
      ),
      'other' => array('
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function(){
  jQuery("a.gallerybox-p'.$media_gallery_base->pluginid.'").ceebox({
    animSpeed: "fast", borderWidth: "2px", overlayOpacity: 0.7,
    html: true, htmlGallery: true, imageGallery: true, margin: "100",
    padding: "14", titles: true, itemCaption: "", ofCaption: " / "
  });
});
//]]>
</script>
')
    ), false);
    break;
  }
  // Fancybox library for both Integrated and Fancybox display modes:
  case 0:
  case 3:
  {
    sd_header_add(array(
      'js'  => array(
        'includes/javascript/jquery.easing-1.3.pack.js',
        'includes/javascript/fancybox/jquery.fancybox-1.3.4.pack.js',
      ),
      'css' => array(
        'includes/javascript/fancybox/jquery.fancybox-1.3.4.css'
      ),
      'other' => array('
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function(){
if(typeof jQuery.fn.fancybox !== "undefined") {
  jQuery("a[rel*=fancybox-p'.$media_gallery_base->pluginid.']").fancybox({
    "titlePosition"   : "inside",
    "transitionIn"    : "elastic",
    "transitionOut"   : "elastic",
    "type"            : "image",
    "easingIn"        : "swing", //"easeOutBack",
    "easingOut"       : "swing", //"easeInBack",
    "overlayOpacity"  :  0.8,
    "overlayColor"    :  "#000"
  });
}
});
//]]>
</script>
')
    ), false);
    break;
  }
  // Galleria jQuery Plugin:
  case 4:
  {
    sd_header_add(array(
      'js'  => array(
        'galleria-1.2.2.min.js'
      ),
      //'css' => array('plugins/'.$plugin_folder.'/css/galleria.css'),
      'other' => array("
<script type=\"text/javascript\">Galleria.loadTheme('plugins/".$plugin_folder."/js/galleria.classic.js');</script>
<script type=\"text/javascript\">
//<![CDATA[
jQuery(document).ready(function(){
  jQuery('ul.p".$media_gallery_base->pluginid."_gallery_unstyled').addClass('p".$media_gallery_base->pluginid."_gallery');
  jQuery('ul.p".$media_gallery_base->pluginid."_gallery').galleria({
    autoplay  : 5000,
    image_crop: false,
    thumb_crop: false,
    transition: 'fade',
    height    : 600,
    width     : \"100%\",
    history   : false, // activates the history object for bookmarking, back-button etc.
    clicknext : false, // helper for making the image clickable
    insert    : '#p".$media_gallery_base->pluginid."_main_image' // the containing selector for our main image
  });
});
//]]>
</script>
")
    ), false);
    break;
  }
  // Rotating Slides jQuery Plugin:
  case 5:
  {
    sd_header_add(array(
      'js'  => array(
        'jquery.rotate.js',
        SD_JS_PATH.'slideshow.js'
      ),
      'css' => array(
        'plugins/'.$plugin_folder.'/css/slideshow.css'
      )
    ), false);
    break;
  }
  // mb.Gallery jQuery Plugin:
  case 6:
  {
    sd_header_add(array(
      'js'  => array(
        'mbGallery.js'
      )
      ,'css' => array('plugins/'.$plugin_folder.'/css/mbgallery.white.css')
    ), false);
    break;
  }
  // jqGalViewII
  case 7:
  {
    sd_header_add(array(
      'js'  => array(
        'jqgalview2.js'
      ),
      'css' => array(
        'plugins/'.$plugin_folder.'/css/jqgalview2.css'
      ),
      'other' => array("
<script type=\"text/javascript\">
//<![CDATA[
jQuery(document).ready(function(){
  jQuery('#p".$media_gallery_base->pluginid."_imagegallery ul.galleryimages').jqGalViewII();
});
//]]>
</script>")
    ), false);
    break;
  }
} //switch

unset($js,$media_gallery_base,$mg_media_url_check_js);
