<?php
define('ROOT_PATH', '../../../../../');
define('IN_PRGM', true);
define('IN_ADMIN', true);
require(ROOT_PATH.'includes/init.php');
$mainsettings['gzipcompress'] = true; //trick it
include(ROOT_PATH.'includes/enablegzip.php');
header("Content-type: text/css");
header("Cache-Control: public");
?>
select#page_select { width: 190px; }
.status_link { width: 85%; }
ul#article_pages {
  background-color: #fff;
  border: 1px solid #d0d0d0;
  height: 50px;
  max-height: 150px;
  list-style-type: none;
  overflow: hidden;
  overflow-y: scroll;
  padding: 4px;
  margin: 4px 0px 4px 0;
  width: 210px;
  vertical-align: top;
}
ul#article_pages li {
  background-color: #fff;
  border: 1px solid #c5cbcb;
  height: 18px !important;
  list-style-type: none;
  padding: 2px;
  margin: 1px;
  vertical-align: middle;
  text-align: left;
  width: 185px;
}
ul#article_pages li:hover {
  color: #fff;
  cursor: pointer;
  background-color: #00BFEB;
}
ul#article_pages li img {
  display: inline-block;
  margin: 0px 6px 0 2px;
  padding: 0px;
  width: 16px;
}

span.smalltitle { padding-left: 22px; font-size: 11px !important; text-transform: none !important; }

/* SD360 */
ul#article-settings { list-style-type: none; }
ul#article-settings li { padding: 1px; }
div.articles-attachments {
  padding:4px;
  font-size:10px;
  border:1px solid #E0E0E0;
  margin:0px;
  vertical-align:middle; }
div.articles-attachments ul li{
  height:18px;
  list-style-type:none;
  margin-top:6px;
  margin-left:4px; }
div.articles-attachments ul li a{
  color:#000;
  font-size:14px; }
div#options-thumbnail,
div#options-featured       { padding: 4px }
div#options-thumbnail span,
div#options-featured  span,
div#options-thumbnail a,
div#options-featured  a    { clear: both; display:block; margin-bottom:6px}

/* SD362 */
td.parentselector_top { padding:0 !important;margin:12px !important;}
a.parentselector { margin: 8px; }
a.parentselector,
a.parentselector span { display:block; padding: 2px; max-width: 95% !important; }
a.parentselector span { padding:4px; margin: 0; min-height:14px; vertical-align:bottom !important; }
select#categoryids { width: 98% }