<?php
// +---------------------------------------------+
// | Download Manager plugin for Subdreamer      |
// +---------------------------------------------+
// | v2.2.0, September 2013                      |
// | Maintainer: 2007-2013 tobias                |
// | This version requires Subdreamer 3.6+!      |
// +---------------------------------------------+

if(!defined('IN_PRGM')) exit();

// ###################### DETERMINE CURRENT DIRECTORY ########################

$dlm_currentdir = sd_GetCurrentFolder(__FILE__); // IMPORTANT VARIABLE!
if(!$dlm_pluginid = GetPluginIDbyFolder($dlm_currentdir))
{
  unset($dlm_pluginid, $dlm_currentdir);
  return;
}

/*
Init plugin header.
Code in this variable would be added *after* any sd_header_add() contents
and may be used for e.g. initialize custom javascript objects etc.
*/
$header = '';

// Load additional HTML code for <HEAD>, depending on admin or not:

if(!function_exists('sd_header_add'))
{
  return true;
}

if(defined('IN_ADMIN'))
{
  // ################# ADMIN HEADER ####################
  $dlm_jscode = '
<style type="text/css">
.uploader {
  background-color: #F0F0F0;
  padding: 10px;
  min-height: 80px;
}
.uploader_filelist {
  border: 1px solid #d0d0d0;
  margin-bottom: 8px;
  margin-top: 8px;
  max-height: 100px;
  min-height: 20px;
  overflow-x: noscroll;
  overflow-y: auto;
  min-width: 300px;
}
.uploader_filelist div {
  overflow: hidden;
  margin: 0;
  padding: 2px;
  height: 20px;
}
.uploader_messages { display: none; }
.uploader_messages {
  border: 1px solid #d0d0d0;
  margin-bottom: 4px;
  margin-top: 4px;
  max-height: 100px;
  overflow-x: noscroll;
  overflow-y: auto;
  padding: 4px;
  width: 90%;
}
ul#file_users {
  border: 1px solid #d0d0d0;
  max-height: 150px;
  overflow-y: scroll;
  padding: 2px;
  width: 194px;
  vertical-align: top;
}
ul#file_users li {
  background-color: transparent;
  border: none;
  height: 22px !important;
  list-style-type: none;
  padding: 0px;
  margin: 0px;
  vertical-align: top;
}
ul#file_users div {
  display: inline;
  float: left;
  padding: 1px;
  margin: 1px;
  overflow-x: hidden;
  position: relative;
  width: 150px;
}
ul#file_users li img {
  display: inline;
  float: left;
  margin: 0px;
  padding-top: 2px;
  position: relative;
  right: 0px;
  width: 16px;
}
ul#file_users li:hover {
  background-color: #d0d0d0;
}
.markItUpEditor { width: 97%; }
div#dlm_content .dlm-btn-section-new {
  background: url("'.$sdurl.'plugins/'.$dlm_currentdir.'/images/misc/section_new.png") no-repeat scroll 12px center transparent !important;
}

div#dlm_content .dlm-btn-section-edit {
  background: url("'.$sdurl.'plugins/'.$dlm_currentdir.'/images/misc/section_edit.png") no-repeat scroll 12px center transparent !important;
}

div#dlm_content .dlm-btn-view-files {
  background: url("'.$sdurl.'plugins/'.$dlm_currentdir.'/images/misc/view_files.png") no-repeat scroll 12px center transparent !important;
}

div#dlm_content .dlm-btn-file-upload {
  background: url("'.$sdurl.'plugins/'.$dlm_currentdir.'/images/misc/file_upload.png") no-repeat scroll 12px center transparent !important;
}

div#dlm_content .dlm-btn-file-new {
  background: url("'.$sdurl.'plugins/'.$dlm_currentdir.'/images/misc/file_new.png") no-repeat scroll 12px center transparent !important;
}

div#dlm_content .dlm-btn-file-edit {
  background: url("'.$sdurl.'plugins/'.$dlm_currentdir.'/images/misc/file_edit.png") no-repeat scroll 12px center transparent !important;
}

div#dlm_content .dlm-btn-file-upload {
  background: url("'.$sdurl.'plugins/'.$dlm_currentdir.'/images/misc/up_blue_48.png") no-repeat scroll 12px center transparent !important;
}

div#dlm_content .dlm-btn-instant-upload {
  background: url("'.$sdurl.'plugins/'.$dlm_currentdir.'/images/misc/up_green_48.png") no-repeat scroll 12px center transparent !important;
}
</style>
<script type="text/javascript">
//<![CDATA[
if (typeof(jQuery) !== "undefined") {
jQuery(document).ready(function() {
	
  $(".ace-file").ace_file_input();
  /* Correct some too wide css settings... */
  jQuery("td select:not([multiple=\"\"])").css({fontSize: "12px", "min-height": "24px", padding: "2px", margin: "2px"});
  if(typeof($.fn.supersubs) !== "undefined") {
    jQuery("ul#submenu").supersubs({ minWidth: 12, maxWidth: 25, extraWidth: 1 }).superfish();
  }
  if(typeof($.fn.jPicker) !== "undefined") {
    $(".colorpicker").jPicker({
      images: {clientPath: sdurl+"includes/css/images/jpicker/"}
    }).addClass("jPickered");
  }
  if(typeof($.fn.ceebox) !== "undefined") {
    jQuery(".ceebox").ceebox({
      animSpeed: "fast",
      borderWidth: "2px",
      overlayOpacity: 0.7,
      html: true,
      htmlGallery: true,
      imageGallery: true,
      margin: "100",
      padding: "14",
      titles: false
    });
  }
  if(typeof($.fn.tagEditor) !== "undefined") {
    jQuery("#tags").tagEditor({
      completeOnSeparator: true,
      completeOnBlur: true,
      confirmRemoval: true,
      separator: ",",
      confirmRemovalText: "'.addslashes($sdlanguage['common_remove_tag']).'"
    });
  }
  jQuery(".file_descr").markItUp(myBbcodeSettings);
})
}
//]]>
</script>
';

  //SD362: moved most JS files to core admin loader!
  if(defined('ENABLE_MINIFY') && ENABLE_MINIFY)
  {
    $js_arr = array(ROOT_PATH . MINIFY_PREFIX_G . 'plupload');
  }
  else
  {
    $js_arr = array(
      SD_INCLUDE_PATH.'plupload/plupload.full.js',
      SD_INCLUDE_PATH.'plupload/jquery.plupload.queue.js',
      SD_INCLUDE_PATH.'javascript/jquery.progressbar.js',
	  );
  }
  $js_arr[] = ROOT_PATH.ADMIN_STYLES_FOLDER .'/assets/js/jquery-ui.min.js';
  sd_header_add(array(
    'other' => array($dlm_jscode),
    'css'   => array(
        //SD362: moved secondary CSS files to core admin loader!
        ROOT_PATH.'plugins/'.$dlm_currentdir.'/css/styles.css.php',
        SD_INCLUDE_PATH.'plupload/css/plupload.queue.css',
		SD_CSS_PATH . 'jquery.tag.editor.css',
		ROOT_PATH.ADMIN_STYLES_FOLDER. '/assets/css/jquery-ui.min.css',
		ROOT_PATH.ADMIN_STYLES_FOLDER. '/assets/css/sf-menu.css',
      ),
    'js'    => $js_arr
  ));

  unset($dlm_currentdir, $dlm_jscode,$js_arr);
}
else
{
  // ################# FRONTPAGE HEADER ####################
  define('DLM_SMARTY', true);
  $meta_arr = array();

  $header_js = $header_css = array();
  $header_css[] = 'plugins/'.$dlm_currentdir.'/css/styles.css.php';

  // Include required class file and check if class is available
  if(!class_exists('DownloadManager'))
  {
    @include_once(ROOT_PATH . 'plugins/'.$dlm_currentdir.'/class_dlm.php');
  }
  if(!class_exists('DownloadManagerTools') || !class_exists('DownloadManager'))
  {
    return false;
  }

  // Store current plugin instance as reference
  if(!isset($sd_instances) || !is_array($sd_instances)) $sd_instances = array();
  $dlm_base = new DownloadManager();
  $sd_instances[$dlm_pluginid] = & $dlm_base;

  // Initiate plugin and frontpage (incl. SEO handling)
  if(!$dlm_base->Init($dlm_pluginid) || !$dlm_base->InitFrontpage())
  {
    return false;
  }

  $dlm_osm_header = '';

  //URL Example:
  //http://localhost/downloads.html?amp;p5001_action=submitfileform&amp;p5001_sectionid=10&amp;p5001_fileid=126
  if(($dlm_base->ACTION !== '') && ($dlm_base->ACTION == 'submitfileform'))
  {
  }
  else
  if(($dlm_base->fileid !== false) && ($dlm_base->fileid > 0))
  {
    if($dlm_file = $DB->query_first('SELECT fileid, title, description'.
                                    ' FROM {p'.$dlm_pluginid.'_files}'.
                                    ' WHERE fileid = %d LIMIT 1',
                                    $dlm_base->fileid))
    {
      if(($dlm_file['fileid'] == $dlm_base->fileid) && !empty($dlm_file['title']))
      {
        $meta_arr['title'] = $dlm_file['title'];
        if(!empty($dlm_file['description']))
          $meta_arr['description'] = $dlm_file['description'];
        else
          $meta_arr['description'] = $dlm_file['title'];
        $meta_arr['keywords'] = $dlm_file['title'];
      }
    }
    unset($dlm_file);

    $header_css[] = SD_INCLUDE_PATH.'css/video-js.min.css';
    $header_css[] = SD_INCLUDE_PATH.'css/tube.css';
    $header_css[] = 'plugins/'.$dlm_currentdir.'/css/jplayer.blue.monday.css';
    if(defined('ENABLE_MINIFY') && ENABLE_MINIFY)
    {
      $header_js[] = ROOT_PATH . MINIFY_PREFIX_F. 'includes/javascript/jquery.media.js';
      $header_js[] = ROOT_PATH . MINIFY_PREFIX_F. 'includes/javascript/jquery.jplayer.js';
      $header_js[] = ROOT_PATH . MINIFY_PREFIX_F. 'includes/javascript/video-min.js';
    }
    else
    {
      $header_js[] = 'includes/javascript/jquery.media.js';
      $header_js[] = 'includes/javascript/jquery.jplayer.js';
      $header_js[] = 'includes/javascript/video-min.js';
    }
    if(file_exists(ROOT_PATH.'includes/javascript/osm/OSMPlayer.php'))
    {
      include_once(SD_INCLUDE_PATH.'javascript/osm/OSMPlayer.php');
      $dlm_osm_player = new OSMPlayer(array(
                  'disablePlaylist' => true,
                  'playerPath' => SD_JS_PATH.'osm',
                  'theme' => 'dark-hive',
                  'template' => 'simpleblack'
      ));
      //SD370: make absolute links
      $dlm_osm_header = str_replace('./includes/',$sdurl.'includes/',$dlm_osm_player->getHeader());
    }
  }

  if(!empty($dlm_base->sectionid) && is_numeric($dlm_base->sectionid) && ($dlm_base->sectionid > 0))
  {
    if($dlm_section = $DB->query_first('SELECT sectionid, name, description FROM {p'.$dlm_pluginid.'_sections}'.
                                       ' WHERE sectionid = %d LIMIT 1',
                                       $dlm_base->sectionid))
    {
      if(($dlm_section['sectionid']==$dlm_base->sectionid) && !empty($dlm_section['name']))
      {
        if(empty($meta_arr['title'])) $meta_arr['title'] = $dlm_section['name'];
        if(!empty($dlm_section['description']))
          $meta_arr['description'] = (!empty($meta_arr['description'])?$meta_arr['description'].',':'') . $dlm_section['description'];
        else
          $meta_arr['description'] = (!empty($meta_arr['description'])?$meta_arr['description'].',':'') . $dlm_section['name'];
        $meta_arr['keywords'] = (!empty($meta_arr['keywords'])?$meta_arr['keywords'].',':'') . $dlm_section['name'];
      }
    }
  }

  if(!empty($meta_arr['description']))
  {
    $post = preg_replace('#\[[^\[]*\]#m','',$meta_arr['description']);
    $post = trim(strip_alltags(preg_replace(array('/&quot;/','/&amp;quot;/','/&#039;/','#\s+#m','#\.\.+#m','#\,#m'),array(' ',' ',"'",' ','.',' '), $post)));
    if(strlen($post))
    {
      $descr_keywords = array_unique(array_slice(array_filter(explode(' ', $post)),0,30));
      $meta_arr['description'] = trim(implode(' ', $descr_keywords));
    }
    $meta_arr['keywords'] = sd_getkeywords($post,true,1,10);
    unset($post, $descr_keywords);
  }
  // Add all entries to header now:
  sd_header_add(array(
    'css'   => $header_css,
    'js'    => $header_js,
    'meta'  => $meta_arr,
    'other' => array($dlm_osm_header.'
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function() {
  jQuery("textarea.dlm-edit-description").markItUp(myBbcodeSettings);

  var SyntaxRoot = "'.SITE_URL.'includes/javascript/syntax/";
  jQuery.cachedScript = function(url, options) {
    options = $.extend(options || {}, { dataType: "script", cache: true, url: url });
    return jQuery.ajax(options);
  };
  if(jQuery(".dlm-file-description .bbcode_code").length) {
    jQuery.cachedScript(SyntaxRoot + "jquery.syntax.cache.js");
    jQuery.cachedScript(SyntaxRoot + "jquery.syntax.min.js").done(function(script, textStatus) {
      jQuery.syntax({ root: SyntaxRoot, theme: "grey", replace: true,
                      layout: "inline", context: $(".dlm-file-description .bbcode_code") });
    });
  }
});
//]]>
</script>
')
  ));

  // Cleanup
  unset($dlm_base, $dlm_osm_header, $header_js, $header_css, $meta_arr);
}
