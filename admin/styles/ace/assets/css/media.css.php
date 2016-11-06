<?php
define('IN_ADMIN', true);
define('IN_PRGM', true);
define('ROOT_PATH', '../../../../../');
define('SD_IMAGES_PATH', ROOT_PATH.'includes/images/');
require(ROOT_PATH.'includes/init.php');
include(ROOT_PATH.'includes/enablegzip.php');
header("Content-type: text/css");
header("Cache-Control: public");
?>
/* Media page */
/* IE hack: pre-set width/height and later to auto since
   "min-/max-" attributes are no good with IE */
div#preview_container {
  display: block;
  width: 100%;
  height: 100%;
}
div.previewbox {
  background-color: transparent;
  margin: 0px auto;
  /*
  min-height: 300px;
  max-width: 500px;
  */
  padding: 2px;
  overflow: hidden;
  height: 300px;
  width: 500px;
}
div#previewloader {
  background-color: #fff;
  display: inline;
  float: left;
  position: absolute;
  left: -1000px;
  top: -1000px;
  text-align: center;
  z-index: 1000;
  border: 1px #ccc solid;
  border-radius: 2px; -webkit-border-radius: 2px;
  -moz-border-radius: 2px; -o-border-radius: 2px;
  -khtml-border-radius: 2px;
  padding: 5px;
}
.previewlink {
  border: none;
  margin: 0px auto;
  float: left;
  width: 100%;
  height: 100%;
  text-align: center;
}
img#previewimg {
  border: 1px solid red;
  padding: 1px auto;
  max-height: 500px;
  height: 500px;
  max-width: 500px;
  width: 500px;
}
div#preview_details {
  padding-top: 2px;
  min-height: 36px;
  height: auto;
  width: 100%;
  text-align: center;
}
.table_wrap { margin-bottom: 0px !important; }



input.media_size_btn {
  font-size: 10pt;
  padding: 2px;
  margin: 2px;
}

div.uploader {
  background-color: #f0f0f0;
  color: #000;
  padding: 10px;
  margin: 0px;
  min-height: 120px;
  /* height: 120px; */
}
div.uploader_filelist {
  background-color: transparent;
  border: 1px solid #d0d0d0;
  color: #000;
  margin: 4px 8px 4px 0px;
  max-height: 100px;
  min-height: 22px;
  overflow-x: hidden;
  overflow-y: auto;
  padding: 2px;
  width: 400px;
}
div.uploader_filelist div {
  margin: 0;
  overflow: hidden;
  padding: 2px;
  height: 20px;
}
div.uploader_messages {
  border: 1px solid #d0d0d0;
  margin: 4px 8px 4px 0px;
  height: 24px;
  min-height: 24px;
  max-height: 100px;
  overflow-x: hidden;
  overflow-y: auto;
  padding: 2px;
  width: 400px;
}

div#files_list {
  clear: both;
  border: none;
  margin-top: 4px;
}

div#files_list table {
  border: 1px solid #e0e0e0;
  clear: both;
  padding: 0px 0px 2px 0px;
  margin: 0px;
  width: 100%;
}

div#files_list div.filecell {
  margin: 2px auto;
  overflow: hidden;
  text-align: center;
  padding: 0;
  white-space: pre-line;
}

div#files_list div.filecell img {
 min-height: 20px;
 min-width: 20px;
}



/* re-enable height for non-IE browsers the min-/max- attributes */
html > body select,
html > body div.uploader_filelist,
html > body div.uploader_messages,
html > body div#uploader,
html > body div.previewbox,
html > body #previewimg {
  height: auto;
}
/* re-enable width for non-IE browsers the min-/max- attributes */
html > body select#instant_folder,
html > body div.previewbox,
html > body #previewimg { width: auto; }

a.ceetip img { max-height: 100px; }