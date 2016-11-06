<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;
$DB->query("ALTER TABLE {plugins} CHANGE `pluginid` `pluginid` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT");

$DB->query("UPDATE {mainsettings} SET groupname = 'settings_site_activation' WHERE groupname = 'Site Activation'");
$DB->query("UPDATE {mainsettings} SET groupname = 'settings_general_settings' WHERE groupname = 'General Settings'");
$DB->query("UPDATE {mainsettings} SET groupname = 'settings_date_time_settings' WHERE groupname = 'Date and Time Options'");
$DB->query("UPDATE {mainsettings} SET groupname = 'settings_email_settings' WHERE groupname = 'Email Settings'");
$DB->query("UPDATE {mainsettings} SET groupname = 'settings_character_encoding' WHERE groupname = 'Character Encoding'");
$DB->query("UPDATE {mainsettings} SET groupname = 'settings_seo_settings' WHERE groupname = 'SEO Settings'");

$DB->query("INSERT INTO {mainsettings}
(`settingid`, `varname`, `groupname`, `input`, `title`, `description`, `value`, `displayorder`) VALUES
(NULL , 'captcha_publickey', 'settings_captcha', 'text', 'settings_captcha_publickey_title', 'settings_captcha_publickey_desc', '', '1'),
(NULL , 'captcha_privatekey', 'settings_captcha', 'text', 'settings_captcha_privatekey_title', 'settings_captcha_privatekey_desc', '', '2'),
(NULL , 'enable_rss', 'settings_general_settings', 'yesno', 'settings_enable_rss_title', 'settings_enable_rss_desc', '1', '6')");

InsertAdminPhrase(0, 'settings_captcha', 'Captcha Settings', 7);
InsertAdminPhrase(0, 'settings_captcha_publickey_title', 'reCaptcha Public Key', 7);
InsertAdminPhrase(0, 'settings_captcha_publickey_desc', 'Enter your public reCaptcha key:<br /><a href="recaptcha.net/api/getkey">Click here to get your public and private reCaptcha keys.</a>', 7);
InsertAdminPhrase(0, 'settings_captcha_privatekey_title', 'reCaptcha Private Key', 7);
InsertAdminPhrase(0, 'settings_captcha_privatekey_desc', 'Enter your private reCaptcha key:', 7);
InsertAdminPhrase(0, 'settings_enable_rss_title', 'Enable RSS', 7);
InsertAdminPhrase(0, 'settings_enable_rss_desc', 'Enable RSS Syndication?', 7);

InsertAdminPhrase(0, 'comments_user_name', 'Username:', 4);
InsertAdminPhrase(0, 'plugins_custom_plugin_deleted', 'Custom Plugin Deleted', 2);
InsertAdminPhrase(0, 'plugins_plugin_uninstalled', 'Plugin Uninstalled', 2);
InsertAdminPhrase(0, 'common_confirm_deletion', 'Confirm Deletion', 0);
InsertAdminPhrase(0, 'users_confirm_delete_user', 'Delete this user:', 5);

$DB->query("UPDATE {adminphrases} SET defaultphrase = 'If deleted, all users from this usergroup will be assigned to the \"Registered Users\" usergroup.'
            WHERE varname = 'users_confirm_delete_usergroup' AND adminpageid = 5 LIMIT 1");

InsertPhrase(1, 'captcha_not_valid', 'You did not type the security words correctly.');
InsertPhrase(2, 'insert_article_into_page', 'Insert new article into this page.');

DeletePhrase(1, 'wrong_password');
DeletePhrase(1, 'wrong_username');
DeletePhrase(1, 'please_enter_username');

InsertPhrase(1, 'incorrect_login', 'Incorrect login information.');
