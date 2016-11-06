<?php
if(!defined('IN_PRGM')) exit();

$js_arr = array();
$js_init = $js_admin = '';
$basic_ucp = defined('UCP_BASIC') && UCP_BASIC;

if(!$basic_ucp)
{
  if(defined('IN_ADMIN'))
  {
    $action = GetVar('action', 'profile_config', 'string');
    if($action == 'profile_config')
    {
      $js_init = '
  if(typeof(jQuery) !== "undefined"){
    jQuery(document).ready(function(){
      (function($){
        $("a.deletelink").click(function(e){
          return (true === confirm("'.addslashes(AdminPhrase('profiles_confirm_group_delete')).'"));
        });
        $("a.profilefieldadd").click(function(e){
          var href = $(this).attr("href");
          $(this).attr("href", href+"&gid="+$("#fieldgroup").val());
          return true;
        });
        '.GetCeeboxDefaultJS(false,'a.group_permissionslink').'
      }(jQuery));
    });
  }';
    }
  }
  else
  {
    if(!headers_sent())
    {
      header('Cache-control: private');
      header("Cache-Control: max-age=0, must-revalidate");
      $ExpStr = "Expires: " . @gmdate("D, d M Y H:i:s", (TIME_NOW-300)) . ' GMT';
      header($ExpStr);
      unset($ExpStr);
    }

    $isMemberPage = (GetVar('member', 0, 'whole_number', false, true) > 0);
    if(!$isMemberPage)
    {
      $js_init = '
/* ------------------------------------------------------------------------- */
// parseUri 1.2.2
// (c) Steven Levithan <stevenlevithan.com>
// MIT License

function parseUri (str) {
  var o   = parseUri.options,
      m   = o.parser[o.strictMode ? "strict" : "loose"].exec(str),
      uri = {},
      i   = 14;

  while (i--) uri[o.key[i]] = m[i] || "";

  uri[o.q.name] = {};
  uri[o.key[12]].replace(o.q.parser, function ($0, $1, $2) {
    if ($1) uri[o.q.name][$1] = $2;
  });

  return uri;
}

parseUri.options = {
  strictMode: false,
  key: ["source","protocol","authority","userInfo","user","password","host","port","relative","path","directory","file","query","anchor"],
  q:   {
    name:   "queryKey",
    parser: /(?:^|&)([^&=]*)=?([^&]*)/g
  },
  parser: {
    strict: /^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,
    loose:  /^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/)?((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/
  }
};

/* ------------------------------------------------------------------------- */

var sd_member_page = '.($isMemberPage?'true':'false').';
var profilepages = ["dashboard"';
//SD370: output available profile pages (URL hash checking)
$t = array_values(SDProfileConfig::$profile_pages);
foreach($t as $key => $pagename)
{
  if(is_string($pagename))
  {
    $js_init .= ',"'.addslashes($pagename).'"';
  }
  else
  if(is_array($pagename))
  {
    $js_init .= ',"'.addslashes($pagename['title']).'"';
    //SD370: take into account "My Content" pages
    if(isset($pagename['pages']) && is_array($pagename['pages']))
    {
      foreach($pagename['pages'] as $pn => $ptitle)
      {
        $js_init .= ',"'.addslashes($ptitle).'"';
      }
      unset($pn,$ptitle);
    }
  }
}
$js_init .= '];
var profile_indicator, msg_op_submit_lang = "Go";
var api, currenturl = "", lasthash = "", lastparams = "", skiploading = 0;
var p11_dateupdate = '.(empty(SDProfileConfig::$settings['datetime_autoupdate'])?'false':'true').';
var p11_userid = '.(int)$userinfo['userid'].';
var p11_link = sdurl+"plugins/p11_mi_usercp/usercp.php";
var p11_viewmessages = "'.addslashes(SDProfileConfig::$profile_pages['page_viewmessages']).'";
var cp_path = "'.CP_PATH.'";
var p11_seo = '.($mainsettings_modrewrite?'true':'false').';
var p11_dateupdates = 0;
var p11_lang = {
  lang_go_lbl: "'.addslashes(preg_replace('/[\r\n|\n]/',' ',SDProfileConfig::$phrases['lbl_options_go'])).'",
  loading_msg: "'.addslashes(preg_replace('/[\r\n|\n]/',' ',SDProfileConfig::$phrases['loading_msg'])).'",
  recipients_limit: "'.addslashes(preg_replace('/[\r\n|\n]/',' ',SDProfileConfig::$phrases['err_recipients_limit'])).'",
  delete_attachment_prompt: "'.addslashes(preg_replace('/[\r\n|\n]/',' ',SDProfileConfig::$phrases['delete_attachment_prompt'])).'",
  remove_recipient_prompt: "'.addslashes(preg_replace('/[\r\n|\n]/',' ',SDProfileConfig::$phrases['remove_recipient_prompt'])).'"
};

function showIndicator() { jQuery(profile_indicator).css("display", "inline"); }
function hideIndicator() { jQuery(profile_indicator).hide(); }
function updateItemsCount() {
  var newval = jQuery("#ucpForm .msg_check:checked").length;
  if(typeof msg_op_submit_lang == "undefined") {
    msg_op_submit_lang = p11_lang.go_lbl;
  }
  jQuery("#msg_op_submit").val(msg_op_submit_lang+" ("+newval+")");
  jQuery("#msg_op_submit").attr("disabled", (newval=="0"));
  return true;
}
function checkRecipientLimit(){
  var entry_count = $("#recipients_list li").length + $("#bcc_list li").length;
  var recip_limit = parseInt($("#ucp_recipient_limit").val(),10);
  if((recip_limit > 0) && (entry_count >= recip_limit))
  {
    alert(p11_lang.recipients_limit);
    return false;
  }
  return true;
}
function ConfirmDeleteAttachment() {
  if(confirm(p11_lang.delete_attachment_prompt)) {
    return true;
  } else {
    return false;
  }
}
function ConfirmRemoveRecipient() {
  if(confirm(p11_lang.remove_recipient_prompt)) {
    return true;
  } else {
    return false;
  }
}
function RefreshDatetime() {
  $(".datetime").load(p11_link+"?action=currentdate");
}

function LoadCPContent(anchor) {
  if(typeof(anchor)==="undefined") return false;
  if(skiploading > 0) return false;
  skiploading = 1;

  var dovalue = "";
  var hashes = api.parse(anchor);
  var params = parseUri(anchor);

  /* "do" value must identify either a page or a group */
  if(params && params.queryKey["do"]) {
    var dovalue = params.queryKey["do"];
  }
  else
  if(hashes && hashes["do"]) {
    var dovalue = hashes["do"];
  }
  else {
    return false;
  }

  var idx = profilepages.indexOf(dovalue);
  if(idx === -1) {
    /* not a page, check if it is a group like "abc-def.n" */
    var dot = dovalue.split(".",2);
    if(dot.length !== 2 || !dot[1].match(/[0-9]/i)) return false;
  }

  var paramstr = "&amp;profile="+parseInt(params.queryKey["profile"],10);
  delete params.queryKey["profile"]; /* no longer wanted */
  if(hashes["profile"]) delete hashes["profile"];

  /* copy other query params, like page, page_size, id, d etc. */
  $.each(params.queryKey, function(key, value) {
    paramstr += "&"+key+"="+value;
    if(hashes[key]) delete hashes[key];
  });

  /* need to manually add page number from form (if present) */
  var pageparam = "";
  if(!params.queryKey["page"]) {
    if($("#ucpForm input[name=page]").length===1) {
      var page = $("#ucpForm input[name=page]").val();
      if(page > 0) {
        pageparam = "&page="+page;
        paramstr += pageparam;
      }
      if(hashes["page"]) {
        delete hashes["page"];
      }
    }
  }

  /* copy anchor params (if any), like recipientid etc. */
  $.each(hashes, function(key, value) {
    paramstr += "&"+key+"="+value;
  });
  lasthash = api.parseHash(); /* store previous hash */
  currenturl = anchor; /* save for later */

  /* change URL hash */
  if(!params || !params.queryKey["do"]) {
    api.replace({"do":dovalue});
  } else {
    api.replace(params.queryKey);
  }
  lastparams = paramstr;

  showIndicator();

  /* ajax load the new content page with new params */
  $(".content_cell").html(\'<div class="ucp-groupheader">\'+p11_lang.loading_msg+\'<\/div>\').load(p11_link+"?action=loadpage"+paramstr, function(e){
    hideIndicator();
    $("div.msg_text a").attr("target", "_blank");
    $(".ucp_bbcode").markItUp(myBbcodeSettings);
    if(typeof jQuery.fn.uniform !== "undefined"){ $("#ucpForm").uniform(); }
    $("#ucpDelForm").attr("action", p11_link+"?action=delete"+paramstr);
    $("#ucpDelForm").ajaxForm(function(e){
      location.hash = "do="+p11_viewmessages+pageparam;
    });
    skiploading = 0;
  });
  return true;
}

if(typeof(jQuery) !== "undefined"){
  jQuery(document).ready(function(){
  (function($){
    profile_indicator = $("#profile_indicator");
    $(document).delegate("#ucpForm .msg_check","click",function(e){
      updateItemsCount();
    });
    $(document).delegate("#ucpForm #checkall","click",function(e){
      var newval = $(this).attr("checked");
      var selector = "#ucpForm .msg_check:not([disabled=\'disabled\'])";
      if(newval==="checked") {
        $(selector).attr("checked", "checked");
      } else {
        $(selector).removeAttr("checked");
      }
      updateItemsCount();
      return true;
    });
    $(document).delegate("#ucpForm ul li img.list_deluser","click",function(e){
      if(!ConfirmRemoveRecipient()) {
        e.preventDefault();
        return false;
      }
      var user_id = jQuery(this).attr("rel");
      if(typeof user_id !== "undefined") {
        jQuery(this).parent("li").remove();
      }
      return true;
    });

    $(document).delegate("ul.ucp_groupitems:not(:last) a.profilelink,td.ucp_left_col a.grouplink,#ucpForm a.msg_title,#ucpForm a.profilelink,#ucpForm .pagination a,#ucpForm a.viewmessagelink,#ucpForm a.messageprivate,#ucpForm a.messagequote","click",function(e){
      /* ul.ucp_groupitems:first */
      e.preventDefault;
      return !LoadCPContent(this.href);
    });

    $(document).delegate("#ucpForm #msg_op_submit","click",function(e){
      updateItemsCount();
      if($("#msg_operation").val() == "0") {
        e.preventDefault();
        return false;
      }
      $("#ucp_action").val("submit");
      return true;
    });

    $(document).delegate("select#pagesize","change",function(){
      LoadCPContent($("form#ucpForm").attr("action")+"&page_size="+$(this).val());
    });

    $("div.msg_text a").each(function(){
      $(this).attr("target", "_blank");
    });

    if(typeof($.fn.aciFragment) !== "undefined") {
      $(document).bind("acifragment", function(event, api, anchorChanged) {
        if(skiploading > 0) { skiploading--; return false; }
        var found = false;
        var anchor = api.get("do", false);

        /* anchor value must identify either a page or a group */
        if(anchor && anchor !== "dashboard") {
          var idx = profilepages.indexOf(anchor);
          if(idx === -1) {
            /* not a page, check if it is a group like "abc-def.n" */
            var dovalue = anchor.toString();
            var dot = dovalue.split(".",2);
            if(dot.length === 2 && dot[1].match(/[0-9]/i)) { found = true; }
          }
          else {
            found = true;
          }
          if(found) {
            var selector = \'table#ucp_table a[href*="do=\'+anchor+\'"]\';
            if(selector && selector.length > 0) {
              var newanchor = $(selector).eq(0);
              if(newanchor.length) {
                /* assume current user id as the profile id and set "do" */
                var newurl = cp_path+(p11_seo?"?":"&")+"profile='.(int)$userinfo['userid'].'"+"#do="+anchor;

                var href = $(newanchor).attr("href");
                var params = parseUri(location.href);
                var hashes = api.parse(location.href);

                /* check query params first */
                if(params) {
                  if(params.queryKey["profile"]) delete params.queryKey["profile"]; /* no longer wanted */
                  if(params.queryKey["do"]) delete params.queryKey["do"]; /* no longer wanted */
                  $.each(params.queryKey, function( key, value) {
                    newurl += "&"+key+"="+value;
                  });
                }
                /* check anchor params */
                if(hashes) {
                  $.each(hashes, function(key, value) {
                    if(key !== "profile" && key !== "do")
                      newurl += "&"+key+"="+value;
                  });
                }
                if(location.href.toLowerCase() != newurl.toLowerCase()) {
                  location.href = newurl;
                }else{
                  LoadCPContent(newurl);
                }
                return;
              }
            }
          }
        }
        /* if nothing found, trick it into loading the dashboard */
        if(!found && (!anchor || anchor == "dashboard")) {
          LoadCPContent(cp_path+(p11_seo?"?":"&")+"profile="+p11_userid+"&do=dashboard");
        }

      });

      $.fn.aciFragment.defaults.scroll = null;
      api = $(document).aciFragment("api");
      $(document).aciFragment();
    }

    /* Refresh date/time display in profile page up to 30 minutes */
    if(p11_dateupdate && p11_dateupdates < 30) {
      setInterval("RefreshDatetime();", 60000);
      p11_dateupdates++;
    }

    $(document).delegate("form#ucpForm","submit",function(e) {
      e.preventDefault();
      token = $("input[name=ucp_token]",this).val();
      var pagelink = $(this).attr("action");
      $(this).ajaxSubmit({
        beforeSubmit: showIndicator,
        clearForm: false,
        dataType:  "html",
        timeout:   60000,
        type:      "POST",
        url:       p11_link+"?action=submit",
        success:   function(responseText, statusText, xhr, $form) {
          if(typeof(responseText) === "undefined") {
            alert("Error!");
          } else
          if(responseText !== "") {
            $(".content_cell").html(responseText);
          } else {
            alert("Error!");
          }
          hideIndicator();
        }
      });
      return false;
    });

  }(jQuery));
  });
}
  ';
    } #!isMemberPage
  } # end frontpage
}
        #"pagelink": $(this).attr("action"),
        #"securitytoken": token,

if(defined('ENABLE_MINIFY') && ENABLE_MINIFY)
{
  $js_arr = array($sdurl.MINIFY_PREFIX_G.'profile_front');
}
else
{
  $js_arr = array(
    SD_JS_PATH . 'uni-form.jquery.js',
    SD_JS_PATH . 'jquery.validate.min.js',
    SD_JS_PATH . 'jquery.mousewheel.js',
    SD_JS_PATH . 'jquery.datepick.min.js',
    SD_JS_PATH . 'jquery.autocomplete.min.js',
    SD_JS_PATH . 'jquery.form.min.js'
  );
}
if(!$basic_ucp)
{
  $lang = empty($mainsettings['lang_region']) ? 'en-GB' : $mainsettings['lang_region'];
  if(file_exists(ROOT_PATH.'includes/javascript/datetime/jquery.datepick-'.$lang.'.js'))
  {
    $js_arr[] = ROOT_PATH.'includes/javascript/datetime/jquery.datepick-'.$lang.'.js';
  }
}

$css_arr = array(
  SD_CSS_PATH.'uni-form.css',
  SD_CSS_PATH.'default.uni-form.css',
);
if(!defined('IN_ADMIN'))
{
  $css_arr[] = SD_CSS_PATH.'jquery.autocomplete.css';
  $css_arr[] = SD_CSS_PATH.'jquery.datepick.css';
  $css_arr[] = SD_CSS_PATH.'jquery.timeentry.css';
  $css_arr[] = SD_CSS_PATH.'redmond.datepick.css';
  $js_arr[] = 'jquery.aciPlugin.min.js'; //SD370
  $js_arr[] = 'jquery.aciFragment.min.js'; //SD370
}
else
{
  $js_admin = '
    jQuery(".microtabs").microTabs({ selected: 0 });
    jQuery("a.fields-hide,a.fields-show").click(function(e){
      e.preventDefault();
      var f_rel = jQuery(this).attr("rel");
      jQuery("#fields-hide-"+f_rel+",#fields-show-"+f_rel+",#table-"+f_rel).toggle();
      return false;
    });';
}

sd_header_add(array(
   // NO ROOT_PATH for "css" entries!
  'css'   => $css_arr,
  'js'    => $js_arr,
  'other' => array('
<script type="text/javascript">
// <![CDATA[
  jQuery(document).ready(function(){
    var SyntaxRoot = "'.SD_JS_PATH.'syntax/";
    if($("#ucpForm .syntax").length) {
      $.getScript(SyntaxRoot + "jquery.syntax.min.js", function () {
        $.syntax({ root: SyntaxRoot, tabWidth: 2, replace: true, context: $(".bbcode_code") });
      });
    }
    if(typeof jQuery.fn.uniform !== "undefined"){
      jQuery("form").uniform();
    }'.
  (!defined('IN_ADMIN') ? '
    if(typeof jQuery.fn.markItUp !== "undefined"){
      jQuery(".ucp_bbcode").markItUp(myBbcodeSettings);
    }
    ' : $js_admin).'
  });'
.$js_init.'
// ]]>
</script>
')
));

unset($css_arr,$js_admin,$js_arr,$js_init,$lang,$basic_ucp);
