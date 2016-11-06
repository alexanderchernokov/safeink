<?php
if(!defined('IN_PRGM')) return;

if(!defined('SD_INFO_INCLUDED'))
{
  define('SD_INFO_INCLUDED', true);
  ////////////////////////////////////////////////
  //             !! WARNING !!                  //
  ////////////////////////////////////////////////
  //                                            //
  // DO NOT EDIT ANY INFORMATION IN THIS FILE!  //
  // DOING SO CAN BREAK THE CMS PROGRAM         //
  //                                            //
  ////////////////////////////////////////////////

  define('PRGM_VERSION', '4.2.1');
  define('DEFAULT_TABLE_PREFIX', 'sd_');

  //SD362: switched from 1.7.2 to 1.10.2 (2013-09-07)!
  //SD371: switched to 1.11.0 (2014-01-27)!
  define('JQUERY_FILENAME', 'jquery-1.11.0.min.js');
  // ALTERNATIVELY use a CDN download link:
  // see: http://jquery.com/download/
  //define('JQUERY_GA_CDN', '//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js');

  // SD313: init menu array; branding may add entries:
  $admin_menu_arr = false; //SD360: init to be safe
  if(file_exists(ROOT_PATH . ADMIN_PATH . '/branding.php'))
  {
    @include(ROOT_PATH . ADMIN_PATH . '/branding.php');
  }

  // Make sure required constants are set regardless of branding:
  defined('PRGM_NAME')               	|| 	define('PRGM_NAME', 'Subdreamer CMS');
  defined('PRGM_WEBSITE_LINK')       	|| 	define('PRGM_WEBSITE_LINK', 'http://antiref.com/?http://www.subdreamer.com/');
  defined('ADMIN_STYLE_FOLDER_NAME') 	|| 	define('ADMIN_STYLE_FOLDER_NAME', 'ace');
  defined('ADMIN_STYLE_FOLDER_NAME_MIN')||	define('ADMIN_STYLE_FOLDER_NAME_MIN', '/styles/'.ADMIN_STYLE_FOLDER_NAME); //SD400	
  defined('ADMIN_DEFAULT_PAGE')      	|| 	define('ADMIN_DEFAULT_PAGE', 'cphome.php');
  //SD400
  defined('ADMIN_SKIN')					|| 	define('ADMIN_SKIN', 'no-skin');
  defined('SIDEBAR_FIXED')				||	define('SIDEBAR_FIXED', false);
  defined('NAVBAR_FIXED')				||	define('NAVBAR_FIXED',	false);
  //SD411
  defined('ENABLE_WIDGETS')				||	define('ENABLE_WIDGETS',true);
  

  //SD360: do not run defines if called from /includes/min folder:
  if(basename(dirname($_SERVER['PHP_SELF']))!=='min')
  {
    //SD322: shortcut to current admin theme's images folder
    //SD360: use "defined()" to allow override in branding file
    defined('ADMIN_STYLES_FOLDER') || define('ADMIN_STYLES_FOLDER', str_replace('//','/', ADMIN_PATH . '/styles/' . ADMIN_STYLE_FOLDER_NAME. '/'));
    defined('ADMIN_IMAGES_FOLDER') || define('ADMIN_IMAGES_FOLDER', ROOT_PATH . str_replace('//','/', ADMIN_STYLES_FOLDER . 'assets/images/'));
    defined('ADMIN_LOGO') || define('ADMIN_LOGO', '<a class="logo" href="'.PRGM_WEBSITE_LINK.'" title="' . PRGM_NAME .'" target="_blank"><img alt="'.PRGM_NAME.'" src="'.  ADMIN_IMAGES_FOLDER .'af-logo.png"></a>');
	defined('ADMIN_LOGIN_LOGO_IMG') || define('ADMIN_LOGIN_LOGO_IMG', 'login_logo.png');  //SD410

	// SD400: Below values are depreciated
    $_pretag = '<img alt="" width="16" height="16" src="' . ADMIN_IMAGES_FOLDER; //SD362
	defined('IMAGE_EDIT')       || define('IMAGE_EDIT', $_pretag . 'icons/icon-edit.gif" />');
    defined('IMAGE_USERGROUPS') || define('IMAGE_USERGROUPS', $_pretag . 'icons/usergroups.png" />');
    defined('IMAGE_COPY')       || define('IMAGE_COPY', $_pretag . 'icons/copy.png" />');
    defined('IMAGE_DELETE')     || define('IMAGE_DELETE', $_pretag . 'icons/icon-del.png" />');
    defined('IMAGE_DOWNLOAD')   || define('IMAGE_DOWNLOAD', $_pretag . 'icons/download.png" />');
    defined('IMAGE_PREVIEW')    || define('IMAGE_PREVIEW', $_pretag . 'icons/magnifier.png" />');
    defined('IMAGE_SETTINGS')   || define('IMAGE_SETTINGS', $_pretag . 'icons/settings.png" />');
    defined('IMAGE_PERMISSIONS')|| define('IMAGE_PERMISSIONS', $_pretag . 'icons/key.png" />');
    defined('IMAGE_EMAIL')      || define('IMAGE_EMAIL', $_pretag . 'icons/email.png" />');
    defined('IMAGE_DENIED')     || define('IMAGE_DENIED', $_pretag . 'icons/denied.png" />');
    defined('IMAGE_PALETTE')    || define('IMAGE_PALETTE', $_pretag . 'icons/palette.png" />');
    defined('IMAGE_PLUGIN')     || define('IMAGE_PLUGIN', $_pretag . 'icons/plugin.png" />');
    defined('IMAGE_THUMBNAIL')  || define('IMAGE_THUMBNAIL', $_pretag . 'icons/thumbnail.png" />');
    defined('IMAGE_SHIELD')     || define('IMAGE_SHIELD', $_pretag . 'icons/exclamation-shield-frame.png" />');
    defined('IMAGE_SHORTCUT')   || define('IMAGE_SHORTCUT', $_pretag . 'icons/shortcut.png" />');
    defined('IMAGE_FOLDER')     || define('IMAGE_FOLDER', $_pretag . 'icons/folder.png" />');
    defined('IMAGE_PLUS')       || define('IMAGE_PLUS', $_pretag . 'icons/plus.png" />');
    defined('IMAGE_PLUGIN_OPTION') || define('IMAGE_PLUGIN_OPTION', $_pretag . 'icons/plugin-option.png" />');
    defined('IMAGE_MAGNIFIER')  || define('IMAGE_MAGNIFIER', $_pretag . 'icons/magnifier.png" />'); //SD322
    defined('IMAGE_LAYER')      || define('IMAGE_LAYER', $_pretag . 'icons/layer.png" />');
    defined('IMAGE_DATA')       || define('IMAGE_DATA', $_pretag . 'icons/data.png" />');
    defined('IMAGE_TAGS')       || define('IMAGE_TAGS', $_pretag . 'icons/tags.png" />');
    defined('IMAGE_TAG_GREEN')  || define('IMAGE_TAG_GREEN', $_pretag . 'icons/tag-label-green.png" />');
    defined('IMAGE_TAG_RED')    || define('IMAGE_TAG_RED', $_pretag . 'icons/tag-label-red.png" />');
    unset($_pretag);

    //SD342:
    defined('IMAGE_DB_CHECK_NAME')    || define('IMAGE_DB_CHECK_NAME',    ADMIN_IMAGES_FOLDER . 'icons/db-check.png');
    defined('IMAGE_DB_OPTIMIZE_NAME') || define('IMAGE_DB_OPTIMIZE_NAME', ADMIN_IMAGES_FOLDER . 'icons/db-optimize.png');
    defined('IMAGE_DB_REPAIR_NAME')   || define('IMAGE_DB_REPAIR_NAME',   ADMIN_IMAGES_FOLDER . 'icons/db-repair.png');
    defined('IMAGE_DB_EXPORT_NAME')   || define('IMAGE_DB_EXPORT_NAME',   ADMIN_IMAGES_FOLDER . 'icons/db-export.png');

    //SD313: put core menu at the top of the menu array
    $_tmp_arr = // DO NOT TRANSLATE ANYTHING BELOW!!!
      array('Dashboard'	=> array('cphome.php','fa-tachometer'),
	  		'Pages' 	=> array('pages.php','fa-file'),
            'Articles' 	=> array('articles.php', 'fa-font'),
            'Plugins' 	=> array('plugins.php', 'fa-puzzle-piece'),
            'Media' 	=> array('media.php', 'fa-file-image-o'),
            'Comments' 	=> array('comments.php', 'fa-comment'),
            'Reports' 	=> array('reports.php', 'fa-info-circle'),
            'Tags' 		=> array('tags.php', 'fa-tag'),
            'Users &amp; Groups' 	=> array('users.php', 'fa-group'),
            'Skins' 	=> array('skins.php', 'fa-pencil'),
            'Settings' 	=> array('settings.php', 'fa-cog')
			);
    // Note: using @ for array-ops fixes notice on 5.4.x!
    // Only if "$admin_menu_arr" is set/array, try to make
    // it the "main" menu and merge the default menu:
    if(isset($admin_menu_arr) && is_array($admin_menu_arr))
    {
      $_tmp_arr = @array_merge($admin_menu_arr,$_tmp_arr);
    }
    //issue: _unique would remove entries if pointing to same target file
    //$admin_menu_arr = @array_unique($_tmp_arr);
    $admin_menu_arr = $_tmp_arr;
    unset($_tmp_arr);

    define('SD_SMARTY_VERSION', 3); // currently used Smarty version (2 or 3); DO NOT CHANGE!
    define('SD_SMARTY_PATH', 'includes/smarty3/'); // relative path of Smarty folder

    // default expiration time for new cache files (in minutes):
    // default: 1 week = 10080 = 7*24*1440 minutes
    define('SD_CACHE_EXPIRE', 10080);
    define('SD_CACHE_PATH', ROOT_PATH.'cache/'); // path to writable cache folder
  }

  //SD362: set default defines if not done already:
  defined('ENABLE_MINIFY')    || define('ENABLE_MINIFY', true);
  defined('SD_ADMIN_SIDEBAR') || define('SD_ADMIN_SIDEBAR', false);

} // DO NOT REMOVE!
