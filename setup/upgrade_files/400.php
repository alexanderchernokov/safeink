<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

// Create Widgets
$DB->query("CREATE TABLE IF NOT EXISTS {widgets} (
  id int(11) NOT NULL AUTO_INCREMENT,
  file varchar(100)  NOT NULL,
  title varchar(100) NOT NULL,
  descr text,
  author varchar(50) NOT NULL,
  version varchar(15) NOT NULL,
  active tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (id))");


$DB->query("INSERT INTO {widgets} (file, title, descr, author, version, active) VALUES
('widget_sd_links.php', 'Helpful Links', 'Subdreamer CMS widget to display text', 'Subdreamer CMS', '1.0.0', 1),
('sd_user_stats.php', 'New User Stats', 'Subdreamer CMS widget to display new users for month, week and year', 'Subdreamer CMS', '1.0.0', 1),
('sd_rss_feed.php', 'Subdreamer RSS Feed', 'Subdreamer CMS widget to display recent blog posts via RSS', 'Subdreamer CMS', '1.0.0', 1)");

$DB->query("ALTER TABLE {mainsettings} ADD display TINYINT(1) NOT NULL DEFAULT '1'");
$DB->query("UPDATE {mainsettings} set value = 0 WHERE varname='wysiwyg_starts_off'");
$DB->query("UPDATE {mainsettings} set display = 0 WHERE varname='wysiwyg_starts_off'");
//$DB->query("DELETE FROM {mainsettings} where varname='wysiwyg_starts_off'");

InsertAdminPhrase(0, 'menu_plugins_main_plugins', 'Main Plugins',0, false);
InsertAdminPhrase(0, 'menu_plugins_custom_plugins', 'Custom Plugins',0, false);
InsertAdminPhrase(0, 'menu_plugins_addon_plugins', 'Addon Plugins',0, false);
InsertAdminPhrase(0, 'menu_plugins_cloned_plugins', 'Cloned Plugins',0, false);


InsertAdminPhrase(0, 'update_plugin_display_names', 'Update Plugin Display Names',2, false);
InsertAdminPhrase(0, 'installplugin', 'Install Plugin',2, false);
InsertAdminPhrase(0, 'delete_plugin', 'Uninstall Plugin',2, false);
InsertAdminPhrase(0, 'update_custom_plugin', 'Update Custom Plugin',2, false);
InsertAdminPhrase(0, 'folder', 'Folder',2, false);


InsertAdminPhrase(0, 'import_skin', 'Import Skin',6, false);
InsertAdminPhrase(0, 'display_switch_skin_settings', 'Switch Skin',6, false);

