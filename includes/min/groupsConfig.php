<?php
/**
 * Groups configuration for default Minify implementation
 * @package Minify
 */

//*****************************************************************************
// SD: in config.php also required CMS files are loaded for defines
// This file is included by both "min/index.php" as well as SD's admin header!
//*****************************************************************************
// DO NOT USE CSS FILES WITH MINIFY *IF* THE CSS USES IMAGES!!!
//*****************************************************************************
$min_serveOptions['encodeOutput'] = true;

$groups = array(
  'jq' => array(
    (!defined('IN_ADMIN') ? SD_JS_PATH . JQUERY_FILENAME : ''),
    SD_JS_PATH . 'jquery-migrate-1.2.1.min.js',
    SD_JS_PATH . 'superfish/js/jquery.hoverIntent.min.js'
  ),
  'menu' => array(
    SD_JS_PATH . 'superfish/js/superfish.js',
    SD_JS_PATH . 'superfish/js/supersubs.js',
    SD_JS_PATH . 'superfish/js/jquery.bgiframe.min.js'
  ),
  'ceebox' => array(
    SD_JS_PATH . 'jquery.ceebox-min.js'
  ),
  'admin' => array(
  	ROOT_PATH.ADMIN_PATH.ADMIN_STYLE_FOLDER_NAME_MIN.'/assets/js/bootstrap.min.js',
    SD_JS_PATH . 'jquery.metadata.js',
    SD_JS_PATH . 'jquery.cookie.js',
    SD_JS_PATH . 'jquery.blockui.min.js',
    SD_JS_PATH . 'jquery.form.min.js',
    SD_JS_PATH . 'jquery.ceebox-min.js',
    SD_JS_PATH . 'jquery.ceetip-min.js',
    SD_JS_PATH . 'jquery.cluetip.min.js', //SD343
    SD_JS_PATH . 'jquery.crypt.min.js', //SD370
    SD_JS_PATH . 'jquery.easing-1.3.pack.js', //SD370
    SD_JS_PATH . 'jquery.datepick.min.js',
    SD_JS_PATH . 'jquery.dcmegamenu.1.3.3.min.js', //SD344: v1.3.3
    SD_JS_PATH . 'jquery.hotkeys.js', //SD370
    SD_JS_PATH . 'jquery.imgareaselect.min.js',
    SD_JS_PATH . 'jquery.jdialog.js',
    SD_JS_PATH . 'jquery.jgrowl.min.js', //SD370
    SD_JS_PATH . 'jquery.maskedinput.min.js', //SD370
    SD_JS_PATH . 'jquery.microtabs-0.1.min.js',
    SD_JS_PATH . 'jquery.tag.editor.js', //SD370
    SD_JS_PATH . 'jquery.timeentry.min.js',
    SD_JS_PATH . 'jquery.validate.min.js',
    SD_JS_PATH . 'jpicker-1.1.6.min.js',
    SD_JS_PATH . 'uni-form.jquery.js', //SD370
    ROOT_PATH.ADMIN_PATH.'/javascript/functions.js',
    ROOT_PATH.ADMIN_PATH.'/javascript/xfading.js',
    SD_JS_PATH . 'jquery.mousewheel.min.js',
	ROOT_PATH.ADMIN_PATH.ADMIN_STYLE_FOLDER_NAME_MIN . '/assets/js/chosen.jquery.min.js',
	ROOT_PATH.ADMIN_PATH.ADMIN_STYLE_FOLDER_NAME_MIN . '/assets/js/jquery.noty.packaged.min.js',
	ROOT_PATH.ADMIN_PATH.ADMIN_STYLE_FOLDER_NAME_MIN . '/assets/js/bootbox.min.js',
	/* Make sure the following 2 JS files are LAST */
	ROOT_PATH.ADMIN_PATH.'/styles/ace/assets/js/ace-elements.min.js',
	ROOT_PATH.ADMIN_PATH.'/styles/ace/assets/js/ace.min.js'
  ),
  'admin_media' => array( //SD360
    SD_JS_PATH . 'jquery.media.js',
    SD_JS_PATH . 'jquery.jplayer.js',
    SD_JS_PATH . 'video-min.js',
    SD_JS_PATH . 'json2.min.js'
    //SD370: moved to "admin":
    #SD_JS_PATH . 'jquery.cookie.js',
    #SD_JS_PATH . 'jquery.jgrowl.min.js'
  ),
  'admin_skins' => array(
    //SD370: moved to "admin":
    #SD_JS_PATH . 'jquery.blockui.min.js' //SD343
    #SD_JS_PATH . 'jquery.easing-1.3.pack.js',
    #SD_JS_PATH . 'jquery.jgrowl.min.js'
  ),
  'plupload' => array( //SD370
    SD_INCLUDE_PATH.'plupload/plupload.full.min.js',
    SD_INCLUDE_PATH.'plupload/jquery.plupload.queue.min.js',
    SD_JS_PATH.'jquery.progressbar.min.js'
  ),
  'profile_front' => array(
    SD_JS_PATH . 'uni-form.jquery.js',
    SD_JS_PATH . 'jquery.validate.min.js',
    SD_JS_PATH . 'jquery.mousewheel.js',
    SD_JS_PATH . 'jquery.datepick.min.js',
    SD_JS_PATH . 'jquery.autocomplete.min.js',
    SD_JS_PATH . 'jquery.form.min.js'
  ),
  'bbcode' => array(
    SD_JS_PATH . 'markitup/markitup-full.js',
    SD_JS_PATH . 'markitup/sets/bbcode/set.js'
  ),
  'codaslider' => array(
    SD_JS_PATH.'ycodaslider-2.0.pack.js',
    SD_JS_PATH.'coda-slider.1.1.1.pack.js',
    SD_JS_PATH.'jquery-easing-1.3.pack.js',
    SD_JS_PATH.'jquery.innerfade.js',
    SD_JS_PATH.'slide-custom.js'
  ),
  'tiny' => array(
    ROOT_PATH.'includes/tiny_mce/tinymce.min.js',
   // ROOT_PATH.ADMIN_PATH.'/javascript/tiny_init_jq.js'
  ),
  'css-min' => array(
    // Here *only* CSS files which do NOT reference any images:
    SD_CSS_PATH.'jquery.datepick.css',
    SD_CSS_PATH.'redmond.datepick.css',
    SD_CSS_PATH.'jquery.timeentry.css',
    SD_CSS_PATH.'jquery.autocomplete.css'
  )
);
if(!defined('IN_ADMIN'))
{
  $groups['css'] = array(
      SD_INCLUDE_PATH.'css/jquery.jdialog.css',
      SD_INCLUDE_PATH.'css/ceebox.css');
}

// all groups - except TinyMCE - in one big file:
$groups['jqmenu'] = array_merge($groups['jq'],$groups['menu']);
$groups['admin_all'] = array_merge($groups['jqmenu'],$groups['admin'],$groups['bbcode']);


return $groups;