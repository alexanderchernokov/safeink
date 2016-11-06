<?php

// ############################################################################
// INIT ARTICLES PLUGIN: COULD BE DEFAULT (STAND-ALONE ADMIN PAGE) OR A CLONE!
// ############################################################################
if(!defined('ARTICLES_PLUGINID') || !defined('ROOT_PATH'))
{
  // Assume regular admin menu request for Articles, normal bootstrap required:
  $standalone = true;
  defined('IN_PRGM') || define('IN_PRGM', true);
  defined('IN_ADMIN') || define('IN_ADMIN', true);
  defined('ROOT_PATH') || define('ROOT_PATH', '../');
  require(ROOT_PATH . 'includes/init.php');
  require(ROOT_PATH . 'includes/enablegzip.php');

  // Check, if URL has a special plugin id param, as there
  // could be any number of article plugins and we'd need
  // to know the right plugin id for any later action:
  if(preg_match('#'.preg_quote(ADMIN_PATH.'/articles.php').'$#', $script_name))
  {
    $pluginid = Is_Valid_Number(GetVar('pluginid', null, 'whole_number'),0,2,999999);
    // Sanity check for plugin id:
    if(!empty($pluginid) &&
       (($pluginid == 2) || // default plugin
        (($pluginid >= 2000) && ($pluginid < 3000)) || // old cloned plugin
        (($pluginid >= 5000) && ($pluginid <= 9999)) )) // new "self-cloned" plugin
    {
      define('ARTICLES_PLUGINID', $pluginid);
    }
  }
}
else
{
  // Since special define for article plugin id is set, assume that a page
  // call from an article settings file was made (article clone with new
  // codebase):
  if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;
  $standalone = false;
}
defined('ARTICLES_PLUGINID') || define('ARTICLES_PLUGINID', 2);

//SD344: include autocomplete JS
//SD370: moved to core admin loader!
/*
sd_header_add(array(
  'css' => array(
    SD_INCLUDE_PATH . 'css/jquery.autocomplete.css',
  ),
  'js'  => array(
    ROOT_PATH . MINIFY_PREFIX_F . 'includes/javascript/jquery.autocomplete.min.js',
  )
));
*/

// Does plugin with given id actually exist in SD?
if(!isset($plugin_names[ARTICLES_PLUGINID])) return false;

// ############################################################################
// CHECK PAGE ACCESS
// ############################################################################
CheckAdminAccess('articles');

$admin_phrases = LoadAdminPhrases(2 /* admin page id */, ARTICLES_PLUGINID);

// Require article settings class and instantiate it with plugin id:
require(ROOT_PATH . 'includes/class_articles_settings.php');
$article_settings = new ArticlesSettingsClass();
$article_settings->ArticlesSettingsInit(ARTICLES_PLUGINID);

$action = GetVar('action', '', 'string');
$cbox   = GetVar('cbox', false, 'bool');
defined('IS_CBOX') || define('IS_CBOX', (bool)$cbox);

$NoMenu = $cbox || ($action=='display_article_permissions');

// ############################################################################
// CHECK FOR AJAX CALL AND PERFORM ACTION(S), THEN EXIT
// ############################################################################
if(Is_Ajax_Request() && ($action!='display_article_permissions'))
{
  switch($article_settings->action)
  {
    case 'deletethumb': //SD342
    case 'detachthumb': //SD351
      // Delete the specified article thumb
      if($articleid = Is_Valid_Number(GetVar('articleid', 0, 'whole_number'),0,1,999999999))
      {
        if($article_settings->GetHasAdminRights() || !$article_settings->authormode)
        {
          //SD351: revised response to display returned message
          $tmp = $article_settings->DeleteArticleImage($articleid,true,
            ($article_settings->action=='deletethumb'));
          if($tmp !== true)
            echo $tmp;
          else
            echo '1';
        }
      }
      break;
    case 'deletefeatured': //SD351
    case 'detachfeatured': //SD351
      // Delete the specified article's featured pic
      if($articleid = Is_Valid_Number(GetVar('articleid', 0, 'whole_number'),0,1,999999999))
      {
        if($article_settings->GetHasAdminRights() || !$article_settings->authormode)
        {
          $tmp = $article_settings->DeleteArticleImage($articleid,false,
            ($article_settings->action=='deletefeatured'));
          if($tmp !== true)
            echo $tmp;
          else
            echo '1';
        }
      }
      break;
    case 'getarticlepage': //SD360
      // Instant page change for a given article
      $article_settings->GetArticlePageHTML();
      break;
    case 'setarticlepage': //SD360
      // Instant page change for a given article
      $article_settings->ChangeArticlePage();
      break;
    case 'setarticlestatus':
      // Instant status change for a given article (online->offline etc.)
      $article_settings->ChangeArticleStatus();
      break;
    default: echo 'ERROR';
  }
  $DB->close();
  exit();
}

// ############################################################################
// PREPARE HEADER
// ############################################################################

$js_arr = array();
if(!IS_CBOX)
{
  
  //SD370: moved all to core DisplayAdminHeader() function!
  // SD322 - add JS to page header
  // First lets check if for the date and time entries also a regional JS
  // language file exists and add that to the page header HTML
  
  if(file_exists(SD_JS_PATH.'datetime/jquery.timeentry-'.$lang.'.js'))
  {
    $js_arr[] = SD_JS_PATH.'datetime/jquery.timeentry-'.$lang.'.js';
  }
  
  $lang = empty($mainsettings['lang_region']) ? 'en-GB' : $mainsettings['lang_region'];
  if(!file_exists(SD_JS_PATH.'datetime/jquery.datepick-'.$lang.'.js'))
  {
    $lang = 'en-GB';
  }
  $js_arr[] =	ROOT_PATH.ADMIN_STYLES_FOLDER .'/assets/js/jquery-ui.min.js';
  $js_arr[] = 'javascript/page_articles.js';
  $js_arr[] = ROOT_PATH.ADMIN_PATH.ADMIN_STYLE_FOLDER_NAME_MIN . '/assets/js/date-time/moment.min.js ';
  $js_arr[] = ROOT_PATH.ADMIN_PATH.ADMIN_STYLE_FOLDER_NAME_MIN . '/assets/js/date-time/bootstrap-datetimepicker.min.js';
   
  sd_header_add(array(
  'css'   => array( 
  					ROOT_PATH.ADMIN_STYLES_FOLDER.'assets/css/bootstrap-datetimepicker.css',
					ROOT_PATH.ADMIN_STYLES_FOLDER. '/assets/css/jquery-ui.min.css',
					ROOT_PATH . '/includes/css/jquery.tag.editor.css',  ),
  'js'    => $js_arr,
  'other' => array('
<script type="text/javascript">
//<![CDATA[
if (typeof(jQuery) !== "undefined") {
jQuery(document).ready(function() {
  (function($){
	  $("#attachment").ace_file_input();
  })(jQuery);
});
}

var article_options = {
  include_path: "'.SD_INCLUDE_PATH.'",
  lang_name: "'.$lang.'",
  featuredpic_path: "'.SD_ARTICLE_FEATUREDPICS_DIR.'",
  thumbnail_path: "'.SD_ARTICLE_THUMBS_DIR.'",
  lang_hide_extras: "'.addslashes(AdminPhrase('hide_extras')).'",
  lang_show_extras: "'.addslashes(AdminPhrase('show_extras')).'",
  lang_confirm_remove_tag: "'.addslashes(AdminPhrase('confirm_remove_tag')).'",
  lang_confirm_remove_tag_title: "'.addslashes(AdminPhrase('confirm_remove_tag_title')).'",
  lang_confirm_delete_thumbnail: "'.addslashes(AdminPhrase('confirm_delete_thumbnail')).'",
  lang_confirm_delete_featurepic: "'.addslashes(AdminPhrase('confirm_delete_featurepic')).'",
  lang_thumbnail_removed: "'.addslashes(AdminPhrase('thumbnail_removed')).'",
  lang_featuredpic_removed: "'.addslashes(AdminPhrase('featuredpic_removed')).'"
};
//]]>
</script>
')));


}


// Init article search bar, which must be done before Admin Header due to
// cookie handling and headers:
$article_settings->SearchInit();

// ############################################################################
// DISPLAY ADMIN HEADER
// ############################################################################
if($standalone) DisplayAdminHeader('Articles', null, AdminPhrase('menu_articles'), $NoMenu);

// ############################################################################
// PROCEED WITH REGULAR SETTINGS PAGE
// ############################################################################
$article_settings->DisplayDefault();

// ############################################################################
// DISPLAY ADMIN FOOTER
// ############################################################################
if(!$NoMenu && $standalone) DisplayAdminFooter();
