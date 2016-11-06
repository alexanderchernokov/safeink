<?php
if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return;

PrintPluginSettings($pluginid, 'Options', $refreshpage);

//SD343: assume that changes occured, so delete extra cache file:
$SDCache->delete_cacheid('plugin-activeposters');