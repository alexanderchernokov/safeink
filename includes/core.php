<?php

if(!defined('IN_SUBDREAMER')) return;

// Old plugins use $rootpath, so define ROOT_PATH with it for compatibility
if(!defined('ROOT_PATH') && !empty($rootpath))
{
  define('ROOT_PATH', $rootpath);
}
if(!defined('IN_PRGM'))
{
  define('IN_PRGM', true);
}

include_once('init.php');
