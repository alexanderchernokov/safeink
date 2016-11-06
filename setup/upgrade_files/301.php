<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

// image gallery updates
$DB->query("UPDATE {p17_sections} SET activated = 1 WHERE sectionid = 1");// some users found their gallery turned off after the last upgrade
$DB->query("UPDATE {pluginsettings} SET `groupname` = 'Upload Options' WHERE pluginid = 17 AND title = 'Allow Root Section Upload' LIMIT 1");
$DB->query("UPDATE {pluginsettings} SET `groupname` = 'Options' WHERE pluginid = 17 AND groupname = 'Section Options'");

?>