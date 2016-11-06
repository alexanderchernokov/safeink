<?php

if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

define('ARTICLES_PLUGINID', (int)$pluginid);
RedirectPage(ROOT_PATH . ADMIN_PATH . '/articles.php'.($pluginid>2?'?pluginid='.$pluginid:''), 'Redirecting...', 1);
