<?php
# Never put any code into this file except "define" statements!!!

# Name the CMS here:
define('PRGM_NAME', 'Subdreamer CMS');

# Link to your company's website here:
# this link is used in two areas:
# 1) When clicking on the main upper left logo in the admin panel
# 2) When clicking on "Website Powered by (Program Name)" in the front-end
define('PRGM_WEBSITE_LINK', 'http://antiref.com/?http://www.subdreamer.com/');

# Enter the name of the admin panel style folder you wish to use:
# The default value and only style that currently exists is 'flipside'.
# You can copy and rename the 'ace' folder to create your own personal style.
define('ADMIN_STYLE_FOLDER_NAME', 'ace');

# Admin Panel Skin
# Admin panel skin  Use skin-1, skin-2 or no-skin skin-3
define('ADMIN_SKIN', 'no-skin');

# ADMIN NAVBAR
# Setting NAVBAR_FIXED to true will stick the navbar
# to the screen when scrolling.
define('NAVBAR_FIXED', false);

# ADMIN SIDEBAR
# Setting SIDEBAR_FIXED to true will stick the sidebar 
#  to the screen when scrolling.
define('SIDEBAR_FIXED', false);

# Enter the filename located in the admin folder that should be loaded
# when a user logs into the admin panel. This is the default adming loading page.
# (system default is pages.php)
#define('ADMIN_DEFAULT_PAGE', 'pages.php');
# Example to open Articles page by default:
#define('ADMIN_DEFAULT_PAGE', 'articles.php?pluginid=2&action=displayarticles');
define('ADMIN_DEFAULT_PAGE', 'cphome.php');



# This is the admin panel logo you need to change.
# What you need to is create a new logo and move your new logo
# into the admin/styles/(ADMIN_STYLE_FOLDER_NAME)/assets/images/ folder and then enter the
# new filename here:
define('ADMIN_LOGIN_LOGO_IMG', 'login_logo.png');   // admin panel login logo

#define('BRANDING_FREE', true);
define('TINYMCE_NOCACHE', false); // in case of editor not loading replace "false" with "true"
define('EMAIL_BATCH_SIZE', 100); // "Users" page: amount of emails per batch; default: 50;
#define('DISPLAY_PLUGIN_ADMIN_SHORTCUTS', false);
#define('UCP_BASIC', true); //SD343: use very basic user profile
#define('DEBUG', true); // do not uncomment in production status!
#define('DEBUG_MYSQL', false); // do not uncomment in production status!

# To disable admin widgets uncomment the following line
#define('DISABLE_WIDGETS', true);

# SD343: enable enhanced pages display in admin (Pages, Usergroups)
# Note: automatically enabled when > 500 pages exist!
define('SD_ENHANCED_PAGES', true); //default: false

define('SD_MAX_GET_VARS', 128);   //SD343: max. number of URL parameters ($_GET); if > x then 403 error
define('SD_MAX_POST_VARS', 1000); //SD343: max. number of submitted entries ($_POST); if > x then 403 error


# Uncomment below line to enable ZB Block:
# http://www.spambotsecurity.com/zbblock.php
# For special SD adapation see: http://www.subdreamer.org/plugins.html
# Note: HAS TO BE installed in /includes/zbblock folder!
#define('ZB_BLOCK', true); //commented out by default

# Defines for special languages to use extra transliteration to create
# roman-alphabet SEO titles (ConvertNewsTitleToUrl function):
#define('SD_TRANSLIT_GR', true);
#define('SD_TRANSLIT_LV', true);
#define('SD_TRANSLIT_CZ', true);
#define('SD_TRANSLIT_PL', true);
#define('SD_TRANSLIT_UA', true);

// Special define for Latest Posts plugin and vBulletin 3 to convert special
// turkish characters of topic titles (IF vB is not using utf-8!):
#define('SD_FIX_TURKISH', true);

/*
# for CUSTOM menu structure only!
$admin_menu_arr = // DO NOT TRANSLATE CORE ENTRIES BELOW!!!
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
            'Settings' 	=> array('settings.php', 'fa-cog'),
			'NEW'		=> array('newpage.php' , 'icon-font')
			);
*/

# replace "false" with "true" to enable sidebar in admin:
##### DEPRECIATED as of SD400 ##########
#define('SD_ADMIN_SIDEBAR', false); //SD370

# replace "true" with "false" if problems with mod_security etc.:
define('ENABLE_MINIFY', true);
