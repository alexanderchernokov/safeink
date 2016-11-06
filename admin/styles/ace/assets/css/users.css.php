<?php
define('ROOT_PATH', '../../../../');
define('IN_ADMIN', true);
define('IN_PRGM', true);
require(ROOT_PATH.'includes/init.php');
include(ROOT_PATH.'includes/enablegzip.php');
header("Content-type: text/css");
header("Cache-Control: public");
?>

/* Users page */
div#loader {
  display: none;
  position: absolute;
  width: 100%; height: 100%;
  margin-left: -10000;
  text-align:center;
  opacity: .7; filter: alpha(opacity=70);
}
div#loader div {
  background-color: #e0e0e0;
  color: #000;
  padding: 10px;
  border-radius: 5px;
  -webkit-border-radius: 5px;
  -moz-border-radius: 5px;
  -o-border-radius: 5px;
  -khtml-border-radius: 5px;
}

div#loader {
  background-color: #e0e0e0;
  display: none;
  position: absolute;
  width: 100%; height: 100%;
  margin-left: -10000;
  text-align:center;
  opacity: .7; filter: alpha(opacity=70);
}

#userlist a.user-status-link {
  padding: 3px;
}

#userlist a.user-status-link:hover {
  background-color: #f0f0f0;
  cursor: pointer;
}

div#pagesarea {
  line-height: 2em;
  margin: 4px 0 4px 0;
  padding:0;
  vertical-align: top;
}



div.ucp-groupheader {
  background-color: #f0f0f0;
  color: #000;
  font-weight: bold;
  font-size: 16px;
  padding: 4px;
  margin: 8px 0px 5px 0px;
}

/* SD343 */
tr.marked td { /* highlight marked table rows */
    background-color: #fcbdbd !important; /* #FFD900 = yellow */
}
