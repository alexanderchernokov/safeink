<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

// Add Comment Form Template
require_once(SD_INCLUDE_PATH.'class_sd_smarty.php');
	
$new_tmpl = array('comment_form.tpl' => array('Comment Form', 'Comment Form Template'));
$tpl_path = SD_INCLUDE_PATH.'tmpl/';
$tpl_path_def = SD_INCLUDE_PATH.'tmpl/defaults/';

// Loop to add template(s):
foreach($new_tmpl as $tpl_name => $tpl_data)
{
  // Do not create duplicate
  if(false !== SD_Smarty::TemplateExistsInDB(0, $tpl_name))
  {
	continue;
  }
  if(false !== SD_Smarty::CreateTemplateFromFile(0, $tpl_path_def, $tpl_name,
												 $tpl_data[0], $tpl_data[1]))
  {
	// Add existing, custom template as newest revision, which 
	// would override the default one:
	if(is_file($tpl_path.$tpl_name) && is_readable($tpl_path.$tpl_name))
	{
	  SD_Smarty::AddTemplateRevisionFromFile(0, $tpl_path, $tpl_name, $tpl_data[1], true);
	}
  }
}

// Bootstrap & FontAwesome CDN
$DB->query("INSERT INTO {mainsettings} (varname, groupname, input, title, description, value, displayorder) VALUES
             ('bootstrap_cdn', 'settings_external_libraries', 'yesno', 'Use Twitter Bootstrap CDN', 'Load Twitter Bootstrap using external Content Delivery Network (CDN).','1', 2)");
			 
$DB->query("INSERT INTO {mainsettings} (varname, groupname, input, title, description, value, displayorder) VALUES
             ('fontawesome_cdn', 'settings_external_libraries', 'yesno', 'Use FontAwesome CDN', 'Load FontAwesome Icon Library using external Content Delivery Network (CDN)','1', 3)");
			 
$DB->query("INSERT INTO {mainsettings} (varname, groupname, input, title, description, value, displayorder) VALUES
             ('load_bootstrap', 'settings_external_libraries', 'yesno', 'Load Twitter Bootstrap Framework', 'Load the Twitter Bootsrap framework on front end.','0', 1)");
			 
			 
InsertAdminPhrase(0, 'settings_external_libraries', 'Frameworks', 7, false);

// Update Admin Settings Phrases
InsertAdminPhrase(0, 'settings_site_activation', 'Website On/Off', 7, true);
InsertAdminPhrase(0, 'settings_date_time_settings', 'Date &amp; Time Settings', 7, true);
InsertAdminPhrase(0, 'skins_options', 'Skin Settings', 7, true);
InsertAdminPhrase(0, 'twitter', 'Twitter Settings', 7, true);
InsertAdminPhrase(0, 'image_display', 'Image Settings', 7, true);
InsertAdminPhrase(0, 'display_comment', 'Display Comment', 4, true);
InsertAdminPhrase(0, 'displaycomments', 'Display Comments', 4, true);
InsertAdminPhrase(0, 'display_report', 'Display Report', 4, true);
