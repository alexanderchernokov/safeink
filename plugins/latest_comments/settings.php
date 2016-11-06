<?php
if(!defined('IN_PRGM') || !defined('IN_ADMIN') || empty($pluginid)) return false;

PrintPluginSettings($pluginid, 'options', $refreshpage);
