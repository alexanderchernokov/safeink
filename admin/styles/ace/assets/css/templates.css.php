<?php
define('ROOT_PATH', '../../../../../');
define('IN_ADMIN', true);
define('IN_PRGM', true);
require(ROOT_PATH.'includes/init.php');
include(ROOT_PATH.'includes/enablegzip.php');
header("Content-type: text/css");
header("Cache-Control: public");
$font = 'font-family: "Monaco", "Menlo", "Ubuntu Mono", "Consolas", "source-code-pro", "Lucida Console", "Courier New", "Courier";';
$fallback = empty($mainsettings['skins_enable_highlighting']); // global!
 ?>
 
textarea#skincontent {
  <?=$font?>
  display: <?=($fallback?'block':'none');?>;
  margin-right: 8px;
  padding: 4px;
  width: 98%;
  height: 500px;
}

div#skineditor {
  <?=$font?>
  border: 1px solid #c5cbcb;
  display: <?=($fallback?'none':'block');?>;
  position: relative;
  top: 0; right: 0; bottom: 0; left: 0;
  width: 100%;
  height: 550px;
}

div#skineditor * {
  <?=$font?>
}

div#hotkeys_popup h2 {
  border-bottom: 1px solid #c0c0c0;
  font-size: 13px !important; font-weight: bold;
  margin-bottom: 2px;
  padding: 3px;
  text-align: center;
}

div#hotkeys_popup table td { font-size: 10px !important; }
div#indicator { float: right; display: none; }
div#editor_top select { margin-right: 5px !important; outline: none; vertical-align: top; }
div#kbshortcutmenu { width: 300px; }
div#kbshortcutmenu h1 { font-size: 14px; font-weight: bold; margin: 4px 4px 6px 0;}