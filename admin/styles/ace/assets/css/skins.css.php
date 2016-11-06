<?php
define('ROOT_PATH', '../../../../../');
define('IN_ADMIN', true);
define('IN_PRGM', true);
require(ROOT_PATH.'includes/init.php');
include(ROOT_PATH.'includes/enablegzip.php');
header("Content-type: text/css");
header("Cache-Control: public");
?>
/* Skins page */

div.sectionbar {
  background-color: transparent;
  font-weight: bold;
  /*height: 25px; line-height: 24px;*/
  padding: 5px;
  overflow: hidden;
  margin: 0px;
  vertical-align: middle;
}

div.skin_folder_name {
  display: inline;
  position: absolute;
  right: 50px;
  padding: 0; margin: 0;
  vertical-align: top;
}
div.skin_folder_name img {
  padding-top: 4px;
}

div.skin-items {
  border: 1px solid #c5cbcb;
  clear: both;
  display: block;
  height: 220px;
  margin: 0px 0px 8px 0px;
  max-height: 220px;
  overflow-x: hidden;
  overflow-y: scroll;
  padding-left: 1px;
  width: auto;/*174px;*/
}

/* re-enable height for non-IE browsers the min-/max- attributes */
html > body div.skin-items {
  height: auto;
}

td#items_cell { max-width: 190px; width: 190px; }

#iframe-content {
  border: 0;
  padding: 0;
  margin: 0;
  height: 680px;
  width: 100%;
}

a.iframe-link-no-highlight,
a.iframe-link {
  clear: left;
  display: block;
  line-height: 22px;
  height: 22px;
  padding: 2px 0px 1px 5px;
  margin: 1px;
  white-space: nowrap;
  width: auto;
  overflow:hidden;
}

a.iframe-link-no-highlight { padding-left: 2px; }
a.css-link { /* SD362: added css-link */
  display: inline-block;
  overflow-x: hidden;
  vertical-align: bottom !important;
  width: 70%;
}

div.layout-link-container {
  display: block;
  padding: 2px 8px 2px 0px;
  margin: 0px;
  width: auto;
  overflow: hidden !important;
}

a.layout-link {
  display: inline;
  float: left;
  line-height: 22px;
  height: 22px;
  padding: 1px 2px 1px 5px;
  margin: 0px;
  margin-right: 4px;
  overflow: hidden;
  width: 70%;
  white-space: nowrap;
}
a.css-status-link,
a.delete-layout-link {
  display: inline;
  float: right;
  position: relative;
  padding-top: 2px;
  right: 0;
  top: 0;
}
a.css-status-link span,
a.delete-layout-link span {
  min-width: 16px
}
.current-item, .current-item a {
  background-color: #D9EDF7 !important;
  color: #57A1DE !important;
}
table { border: 0px; width: 100%; }
textarea#skincontent {
  margin-right: 8px;
  padding: 4px;
  font-family: "Monaco", "Menlo", "Ubuntu Mono", "Consolas", "source-code-pro", "Lucida Console", "Courier New", "Courier";
  width: 98%;
  height: auto;
}
#content select, #content input {
  border: 2px solid #d0d0d0;
  background: #ffffff;
  color: #222222;
  font-size: 12px;
  line-height: 18px;
  margin: 3px 2px 4px 2px !important;
  padding: 1px;
}
#content select { height: 25px; }
#content input { height: 20px; }

table#skin-layout-tbl td.content_cell {
  color: #000;
  background: #fff;
  border: 0;
  border-top: none;
  padding: 0px 4px 0px 4px;
  width: 735px;
}


div#skineditor {
  font-family: "Monaco", "Menlo", "Ubuntu Mono", "Consolas", "source-code-pro", "Lucida Console", "Courier New", "Courier";
  border: 1px solid #c5cbcb;
  display: block;
  overflow: hidden;
  margin: 0;
  margin-right: 4px;
  padding: 0;
  padding-right: 4px;
  position: relative;
  bottom: 0;
  left: 0;
  top: 0;
  right: 0;
  width: 99%;
}
div#skineditor * {
  font-family: "Monaco", "Menlo", "Ubuntu Mono", "Consolas", "source-code-pro", "Lucida Console", "Courier New", "Courier";
  font-size: 14px;
  line-height: 17px;
}
div#editor_bottom,
div#editor_top {
  width: 99%;
}
div#editor_top {
  border: 1px solid transparent;
  clear:both;
  display: block;
  font-size: 12px;
  height: auto;
  padding: 2px 8px 4px 2px;
  position: relative;
  vertical-align: top;
}
input#cpicker { height: 22px; font-size: 12px; margin: 0; padding: 0; }
div#editor_bottom {
  border: none;
  bottom: 2px;
  font-size: 12px;
  position: relative;
  padding: 15px 4px 4px 0px;
  margin-bottom: 12px;
  min-height: 36px;
  height: 36px;
  text-align: center;
}
.content_header,
.content_header_layout {
  font-size: 13px !important;
  font-weight: bold;
  clear: none;
  display: inline-block;
  float: left;
  left: 0;
  margin: 2px 5px 2px 0px;
  width: auto;
}
.content_header {
  padding: 6px 0px 0px 4px;
}
.content_header_layout {
  height: 26px;
}
div#items_container { display: block; width: auto; /* 190px;*/ }
div#indicator {
  border: none;
  display: none;
  float: right;
  height: 18px;
  left: 154px;
  overflow: hidden;
  padding: 2px;
  position: static;
  top: 2px;
  width: 18px;
}
div#indicator img { display: inline; }

div#hotkeys_popup h2 {
  border-bottom: 1px solid #c0c0c0;
  font-size: 13px !important; font-weight: bold;
  margin-bottom: 2px;
  padding: 3px;
  text-align: center;
}
div#hotkeys_popup table td { font-size: 10px !important; }

/* SD370: */
div#editor_top select { margin-right: 5px !important; outline: none; vertical-align: top; }
div#kbshortcutmenu { width: 300px; }
div#kbshortcutmenu h1 { font-size: 14px; font-weight: bold; margin: 4px 4px 6px 0;}
div.delete_layout_bottom { display:inline-block;float:right;padding:0;margin:0 }
a#hotkeys_help { font-weight: bold; border: 1px solid #d0d0d0; padding: 3px; }
label { clear: both; display: block; }