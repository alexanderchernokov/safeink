<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

InsertPhrase(1, 'user_unsubscribed',     'You have been unsubscribed from our newsletter.');
InsertPhrase(1, 'user_not_unsubscribed', 'Could not find your account, please contact the site owner to unsubscribe.');

$DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."adminphrases (
  `adminphraseid` int(10) NOT NULL AUTO_INCREMENT,
  `adminpageid` int(10) NOT NULL DEFAULT '0',
  `pluginid` int(10) NOT NULL DEFAULT '0',
  `varname` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `defaultphrase` text COLLATE utf8_unicode_ci,
  `customphrase` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`adminphraseid`),
  KEY `adminpageid` (`adminpageid`),
  KEY `pluginid` (`pluginid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

InsertMainSetting('url_extension', 'settings_general_settings', 'URL Extension', 'If the setting Friendly URLs is enabled, then you have the option of setting an extension that will be added to the end of your URLs.<br />Example: .html', 'text', '.html', 10);

$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'users', 'receive_emails', 'TINYINT(1)', "UNSIGNED NOT NULL DEFAULT '1' AFTER `email`");
?>