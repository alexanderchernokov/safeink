<?php
if(!defined('IN_PRGM')) return;

// SD313: enable embedding of video links?
if(!defined('IN_ADMIN'))
{
  //SD343: init forum config in header already; check for meta data
  $js_output = '';
  $header_other = '';
  $meta_arr = array();
  include_once(ROOT_PATH.'plugins/forum/forum_config.php');
  $sd_forum_config = new SDForumConfig();
  $sd_forum_config->attach_path = 'plugins/forum/attachments/'; // default
  if($sd_forum_config->InitFrontpage())
  {
    define('SD_FORUM_CONFIG', true);
    /*
    - References:
    http://www.seomoz.org/learn-seo/meta-description
    http://www.seomoz.org/learn-seo/title-tag
    TODO:
    - DONE: META KEYWORDS: topic title, prefix, forum keywords, sites keywords
    - DONE: META DESCRIPTION: first x amount of words from the first post; 25-30 words / max. 80-100 characters
      -> overwrite site meta description
    - DONE: Forum/Topic title as browser title same as for articles
    - DONE: 301 redirects for other url format (either xxx-t1234.html OR forum.html?topic_id=1234
    - DONE: canonical tag for all pages for single topic (re: pagination)
    */
    if($sd_forum_config->topic_id)
    {
      $meta_arr['title'] = $sd_forum_config->topic_arr['title'];
      $meta_arr['description'] = $sd_forum_config->topic_arr['title'];
      $meta_arr['keywords'] = '';
      $keywords = array();
      $descr_keywords = array();
      if($sd_forum_config->post_id != $sd_forum_config->topic_arr['first_post_id'])
      {
        $DB->result_type = MYSQL_ASSOC;
        $sel_post = $DB->query_first('SELECT post_id, post FROM {p_forum_posts}'.
                                     ' WHERE post_id = %d',
                                     $sd_forum_config->topic_arr['first_post_id']);
      }
      else
      {
        $sel_post = $sd_forum_config->post_arr;
      }

      if(!empty($sel_post['post_id']))
      {
        $post = preg_replace('#\[[^\[]*\]#m','',$sel_post['post']);
        $post = trim(strip_alltags(preg_replace(array('/&quot;/','/&amp;quot;/','/&#039;/','#\s+#m','#\.\.+#m','#\,#m'),array(' ',' ',"'",' ','.',' '), $post)));
        if(strlen($post))
        {
          $descr_keywords = array_unique(array_slice(array_filter(explode(' ', $post)),0,30));
          $meta_arr['description'] = trim(implode(' ', $descr_keywords));
        }
        $meta_arr['keywords'] = sd_getkeywords($post,true,1,10);
        unset($post);
      }
      $link = $sd_forum_config->RewriteTopicLink($sd_forum_config->topic_id,$sd_forum_config->topic_arr['title']);
      $header_other = '<link rel="canonical" href="'.$link.'" />';
      unset($link);

    }
    else
    if($sd_forum_config->forum_id)
    {
      $meta_arr['title'] = $sd_forum_config->forum_arr['title'];
      $meta_arr['description'] = $sd_forum_config->forum_arr['metadescription'];
      $meta_arr['keywords'] = $sd_forum_config->forum_arr['metakeywords'];

      if(empty($forum_arr['is_category']) && empty($forum_arr['link_to']))
      {
        //SD343 SEO Forum Title linking
        $link = $sd_forum_config->RewriteForumLink($sd_forum_config->forum_id);
        $header_other = '<link rel="canonical" href="'.$link.'" />';
        unset($link);
      }
    }
    else
    {
      $link = RewriteLink();
      $header_other = '<link rel="canonical" href="'.$link.'" />';
    }

    //SD351: inline editing of topic names for admins
    if($sd_forum_config->IsAdmin || $sd_forum_config->IsSiteAdmin)
    {
      //Uses jEditable plugin (customized!):
      //http://www.appelsiini.net/projects/jeditable

      //Allow editing of topic title in a forum's topics list (double-click)
      $js_output .= '
      $("#forum td.col-topic-title").blur(function(){
        $("#forum td.col-topic-title span.editable").each(function(){
          $(this).hide();
        });
      });
      $("#forum td.col-topic-title").hover(
        function(){
          elem = $(this).children("span.editable");
          elem.html("<img alt=\"\" width=\"16\" height=\"16\" src=\""+sdurl+"includes/images/icon-edit.gif\" />");
          elem.show();
          elem.click(function(){
            $(this).trigger("dblclick");
          });
        },
        function(){
          elem = $(this).children("span.editable");
          elem.unbind("click");
          elem.html("");
          elem.hide();
        }
      );
      $("#forum td.col-topic-title").editable(sdurl+"plugins/forum/ajax.php", {
        "event"    : "dblclick",
        "onblur"   : "cancel",
        "name"     : "topic_title",
        "cancel"   : "'.addslashes($sdlanguage['common_btn_cancel']).'",
        "submit"   : "'.addslashes($sdlanguage['common_btn_save']).'",
        "style"    : "display:inline;",
        "selector" : "a[class=topic-link]",
        "data"     : function(value, settings) {
          $("#forum td.col-topic-title span.editable").each(function(){
            $(this).hide();
          });
          return settings.selectorHtml;
        },
        "callback" : function( sValue, y ) {
          if(sValue==-1) {
            alert("'.addslashes($sdlanguage['err_invalid_operation']).'");
            return false;
          }
          $(this).html(sValue);
        },
        "submitdata": function ( value, settings ) {
          return {
            "forum_action" : "rename-topic",
            "topic_view"   : "forum_list",
            "form_token"   : "'.htmlspecialchars(SD_FORM_TOKEN).'",
            "topic_id"     : settings.selectorRel
          };
        },
        "height": "18px",
        "width": "300px"
      });

      /* SD360: Open signature links in new tab */
      $("div.signature a").each(function(){
        $(this).attr("target", "_blank");
      });
      ';

      /* SD360: allow editing of topic title when viewing single topic */
      $js_output .= '
      $("#forum h2.forum-topic-title").editable(sdurl+"plugins/forum/ajax.php", {
        "event"    : "dblclick",
        "onblur"   : "cancel",
        "name"     : "topic_title",
        "cancel"   : "'.addslashes($sdlanguage['common_btn_cancel']).'",
        "submit"   : "'.addslashes($sdlanguage['common_btn_save']).'",
        "tooltip"  : "'.addslashes($sdlanguage['common_click_tip']).'",
        "style"    : "display:inline;",
        "callback" : function( sValue, y ) {
          if(sValue==-1) {
            alert("'.addslashes($sdlanguage['err_invalid_operation']).'");
            return false;
          }
          $(this).html(sValue);
        },
        "submitdata": function ( value, settings ) {
          return {
            "forum_action" : "rename-topic",
            "form_token"   : "'.htmlspecialchars(SD_FORM_TOKEN).'",
            "topic_id"     : $("form#topic-options input[name=topic_id]").val(),
            "topic_view"   : "topic_page"
          };
        },
        "height": "18px",
        "width": "300px"
      });
      ';
    }

  } //Forums InitFrontPage done

  $js_arr = array();
  $js_arr[] = SITE_URL . MINIFY_PREFIX_F . 'includes/javascript/jquery.ceebox-min.js';
  $js_arr[] = SITE_URL . MINIFY_PREFIX_F . 'includes/javascript/jquery.jdialog.js';
  $js_arr[] = SITE_URL . MINIFY_PREFIX_F . 'includes/javascript/jquery.resizeimg.js'; //SD343
  $js_arr[] = SITE_URL . MINIFY_PREFIX_F . 'includes/javascript/jquery.cluetip.min.js'; //SD343
  //SD360: allow in-place topic title editing for admins
  if($sd_forum_config->IsAdmin || $sd_forum_config->IsSiteAdmin)
  {
    $js_arr[] = SITE_URL . MINIFY_PREFIX_F . 'includes/javascript/jquery.jeditable.js';
  }

  // JS for automatic image resizing
  if($max_img_width = (int)$sd_forum_config->plugin_settings_arr['max_image_width'])
  {
    //SD343: improved automatic image resizing and popup application
    $js_output .= '
    $("#forum div.post-content img:not([class=\'bbcode_smiley\'])").resizeImg({maxWidth: '.(int)$max_img_width.',
      onResize: function(im){
        if(im && ($.fn.ceebox !== "undefined")) {
          var Tmp = $(im).wrap(\'<a href="\'+jQuery(im).attr("src")+\'" class="forum-image" rel="image" target="_blank">\');
          Tmp.attr("title", "'.htmlspecialchars($sd_forum_config->plugin_phrases_arr['img_click_to_enlarge'],ENT_COMPAT).'");
        }
      }
    });
    if(typeof $.fn.ceebox !== "undefined") {
      $().ceebox({ animSpeed: "fast", width: "95%", height: "95%" });
      $(document).delegate("#forum a.forum-image","click",function(e){
        e.preventDefault();
        var fimg = $(this).find("img:first").clone();
        fimg.removeAttr("width").removeAttr("height").css({ "minWidth": "50%", "maxWidth": "98%", "maxHeight": "98%", padding: "8px" });
        $.fn.ceebox.popup(fimg,
          { animSpeed: "fast", fadeIn: 100, fadeOut: 100, iframe: true, modal: false,
            htmlGallery: true, html:true, image:true, titles: false, type: "image",
            borderWidth: "1px", margin: "80", padding: "14" });
      });
    }
    ';
  }

  $forum_embed = '';
  if(!empty($sd_forum_config->plugin_settings_arr['enable_embedding']))
  {
    /*
    // OLD embedly code
    $forum_embed = '
    if($("a.bbcode_embedly").length) {
      $.getScript("'.SITE_URL.'includes/javascript/jquery.embedly.min.js", function () {
        $("div#forum a.bbcode_embedly").embedly({
          "maxHeight" : ' . (int)$sd_forum_config->plugin_settings_arr['embedding_max_height'].',
          "maxWidth"  : ' . (int)$sd_forum_config->plugin_settings_arr['embedding_max_width'].',
          "method"    : "after",
          "wrapElement": "div" });
      });
    }';
    */
    $forum_embed = '
    if($("a.bbcode_embedly").length) {
      $.getScript("'.SITE_URL.'includes/javascript/jquery.oembed.js", function () {
        $("div#forum a.bbcode_embedly").oembed(null, {
          embedMethod: "auto",
          vimeo: { autoplay: false, maxHeight: ' . (int)$sd_forum_config->plugin_settings_arr['embedding_max_height'].', maxWidth: ' . (int)$sd_forum_config->plugin_settings_arr['embedding_max_width'].' },
          maxHeight : ' . (int)$sd_forum_config->plugin_settings_arr['embedding_max_height'].',
          maxWidth  : ' . (int)$sd_forum_config->plugin_settings_arr['embedding_max_width'].'
        });
      });
    }';
  }

  // Add JS to page header
  //SD360: use upgraded jQuery Syntax Highlighting script
  sd_header_add(array(
    'css'   => array('includes/css/jquery.cluetip.css'),
    'meta'  => $meta_arr,
    'js'    => $js_arr,
    'other' => array($header_other.'
<script type="text/javascript">
//<![CDATA[
function ForumGetSelectedText() {
  var text = "";
  if (window.getSelection) {
    text = "" + window.getSelection();
  } else
  if (document.selection && document.selection.createRange &&
      document.selection.type == "Text") {
    text = document.selection.createRange().text;
  }
  return text;
}
if(typeof jQuery !== "undefined") {
var forum_likelink = "", forum_dislikelink = "", forum_liketarget;
jQuery(document).ready(function() {
  (function($){
    $("<link>").appendTo("head").attr({rel: "stylesheet", type: "text/css", href: sdurl+"includes/css/ceebox.css" });
    $("<link>").appendTo("head").attr({rel: "stylesheet", type: "text/css", href: sdurl+"includes/css/jquery.jdialog.css" });'.
    (empty($sd_forum_config->topic_id) ? '' : '
    var SyntaxRoot = "'.SITE_URL.'includes/javascript/syntax/";
    jQuery.cachedScript = function(url, options) {
      // allow user to set any option except for dataType, cache, and url
      options = $.extend(options || {}, { dataType: "script", cache: true, url: url });
      // Use $.ajax() since it is more flexible than $.getScript
      // Return the jqXHR object so we can chain callbacks
      return jQuery.ajax(options);
    };
    if($("#forum div.bbcode_code .syntax").length) {
      $.cachedScript(SyntaxRoot + "jquery.syntax.cache.js");
      $.cachedScript(SyntaxRoot + "jquery.syntax.min.js").done(function(script, textStatus) {
        $.syntax({ root: SyntaxRoot, theme: "grey", replace: true,
                   layout: "inline", context: $(".bbcode_code") });
      });
    }').
    $forum_embed.'
    $("#forum td.col-post a").each( function(){ $(this).attr("rel", "nofollow"); });
    '. // DO NOT REMOVE DOT!
    // ADD ADMIN-ONLY JS CODE HERE!!!
    ($sd_forum_config->IsSiteAdmin || $sd_forum_config->IsAdmin? '
    $("#forum #forum-check-all-posts").click(function() {
      $("#forum input[type=\'checkbox\']:not([disabled=\'disabled\'])").attr("checked", true);
      return false;
    });
    $("#forum #forum-uncheck-all-posts").click(function() {
      $("#forum input[type=\'checkbox\']:not([disabled=\'disabled\'])").attr("checked", false);
      return false;
    });
    $("div.forum-attachments a.forum_attachment_delete").click(function(event) {
      if(confirm(\'Delete?\'))
      { return true; }
      else
      { event.preventDefault(); }
      return false;
    });
    $("#topic-options select").change(function(e){
      e.preventDefault();
      var this_action = $(this).val();
      if(this_action && this_action != "0") {
        $("#topic-options").submit();
      }
    });
    $("#forum-check-all-topics").click(function() {
      $("#forum input[type=\'checkbox\']:not([disabled=\'disabled\'])").attr("checked", true);
      return false;
    });
    $("#forum-uncheck-all-topics").click(function() {
      $("#forum input[type=\'checkbox\']:not([disabled=\'disabled\'])").attr("checked", false);
      return false;
    });':'').
    '
    $(document).delegate("a.goto-top-link","click",function(e) {
      e.preventDefault();
      window.scrollTo(0,0);
      return false;
    });
    $("#forum_search_link").click(function() {
      $(this).jDialog({
        align   : "right"
        ,content : jQuery(".forum-search").html()
        ,close_on_body_click : true
        ,idName  : "forum_search_popup"
        ,title   : ""
        /*,width   : 270*/
      });
      return false;
    });
    if (typeof myBbcodeSettings !== "undefined") {
      $("#forum_post").markItUp(myBbcodeSettings);
    }
    $("#forum .public-links a.quote-link").click(function(event){
      var quoted = ForumGetSelectedText();
      if(quoted.length){
        $("#forum_post").val("[quote]"+quoted+"[/quote]").focus();
        event.preventDefault();
        return false;
      }
    });
    '.$js_output.
    (SD_IS_BOT ? '':'
    $("a.member-link, a.forum-memberlink").each(function(){
      var link = $(this).attr("href");
      var uid = link.match("member=([0-9]*)", "gi");
      if(uid) {
        uid = uid[0];
        uid = uid.replace("member=","");
        $(this).attr("rel","plugins/forum/useroptions.php?userid="+uid)
        .attr("title","'.$sd_forum_config->plugin_phrases_arr['user_options'].'")
        .cluetip({
          activation: "click", ajaxCache: false, arrows: true, closePosition: "title",
          closeText: \'<img src="'.$sdurl.'includes/images/check-fail.png" alt="close" width="16" height="16" />\',
          cluetipClass: "jtip", dropShadow: true, hoverIntent: true, mouseOutClose: true,
          positionBy: "mouse", sticky: true,width: "220px"
        });
      }
    });').
    (SD_IS_BOT || empty($sd_forum_config->plugin_settings_arr['enable_like_this_post']) ? '':'
    $("#forum a.like-link, #forum a.dislike-link, #forum a.remove-like-link").each(function(){
      forum_likelink = $(this).attr("href");
      var qpos = forum_likelink.lastIndexOf("?");
      if(qpos > 0) {
        uid = forum_likelink.substr(qpos);
        if($(this).hasClass("like-link"))
          $(this).attr("title","'.addslashes($sdlanguage['like_post']).'");
        else
        if($(this).hasClass("like-link"))
          $(this).attr("title","'.addslashes($sdlanguage['dislike_post']).'");
        else
          $(this).attr("title","");

        $(this).attr("rel",sdurl+"plugins/forum/useroptions.php"+uid)
        .cluetip({
          activation: "click", ajaxCache: false, arrows: true, attribute: "rel", closePosition: "title",
          closeText: \'<img src="\'+sdurl+\'includes/images/check-fail.png" alt="close" width="16" height="16" />\',
          cluetipClass: "jtip", dropShadow: true, hoverIntent: true, mouseOutClose: true,
          multiple: false, positionBy: "mouse", showTitle: false, sticky: true, width: "250px",
          onShow: function(ct, ci){
            forum_likelink = $(this).attr("href");
            forum_liketarget = $(this).parent("div");
          },
          onHide: function(ct, ci){
            uidload = forum_likelink.replace("remove_like","get_likes");
            uidload = uidload.replace("dislike_post","get_likes");
            uidload = uidload.replace("like_post","get_likes");
            qpos = uidload.lastIndexOf("?");
            uidload = sdurl + "plugins/forum/useroptions.php" + uidload.substr(qpos);
            if(uidload !== undefined) $(forum_liketarget).load(uidload);
            return false;
          }
        });
      };

    });
    $(document).keydown(function(e) { if (e.keyCode == 27) { $(document).trigger("hideCluetip"); } });
    '
    ).'
  })(jQuery);
});
}
//]]>
</script>
')));

  if(!headers_sent())
  {
    header('Cache-control: private');
    header("Cache-Control: max-age=0, must-revalidate");
    $ExpStr = "Expires: " . @gmdate("D, d M Y H:i:s", (TIME_NOW-300)) . ' GMT';
    header($ExpStr);
    unset($ExpStr);
  }
  return true; // leave file
}

// ADMIN ONLY:
// Below code is for the online/offline toggle (the "width" is only when
// toggler is inside a table cell!)
sd_header_add(array(
  'css'   => array(
    'styles/' . ADMIN_STYLE_FOLDER_NAME .'/css/plugin_submenu.css'),
  'other' => array('
<style type="text/css">
a.status_link { width: 85%; }
</style>
<script type="text/javascript">
//<![CDATA[
if (typeof jQuery !== "undefined") {
jQuery(document).ready(function() {
  jQuery("div.status_switch").click(function(event){
    event.preventDefault();
    var elm = jQuery(this);
    var inp = elm.find("input:first");
    inp.val(1 - inp.val());
    elm.find("a.on").toggle();
    elm.find("a.off").toggle();
    return false;
  });
});
}
//]]>
</script>
')));
