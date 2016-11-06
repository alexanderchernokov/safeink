<?php
define('IN_PRGM', true);
define('IN_CSS', true);
define('ROOT_PATH', '../../../');
include(ROOT_PATH.'includes/init.php');
include(ROOT_PATH.'includes/enablegzip.php');
header("Content-type: text/css");
?>
/* Download Manager v2.0 CSS styles */

div.dlm-line-separator {
  display:block;
  clear: both;
  background-color: transparent;
  padding-top: 3px;
  padding-bottom: 3px;
}

/* Table containing file (title, description) and all it's details */
table.dlm-table {
  /*color: #000;
  background-color: #F0F0F0;*/
  border: 1px solid #D0D0D0;
  margin: 0px;
  padding: 4px;
  text-align: left;
}

/* Preset common table cell style */
table.dlm-table td {
  background-color: transparent;
  border: none;
  margin: 0px;
  padding: 0px 4px 4px 4px;
  text-align: left;
  vertical-align: top;
}

/* More Info page: left column (thumb) */
.dlm-left-column,
.dlm-table td.dlm-left-column * {
  background-color: transparent;
  border: none;
  padding: 0px;
  /*margin: 10px auto;*/
  text-align: center;
  vertical-align: top;
}

/* More Info page: right column (all details) */
.dlm-table td.dlm-right-column {
  /* background-color: #F0F0F0; */
}

/* File title */
table.dlm-table td.dlm-file-title-td {
  font-weight: bold;
  margin-bottom: 2px;
  padding-top: 2px;
  vertical-align: top;
}

/* Link font size for file's title */
span.dlm-title,
table.dlm-table a.dlm-title-link {
  display: block;
  font-weight: bold;
  font-size: 16px;
  padding-bottom: 6px;
  padding-top: 2px;
}

table.dlm-file-details td {
  padding: 4px 10px 8px 4px;
}

table.dlm-file-details td {
  vertical-align: top;
}

table.dlm-file-details td.dlm-title-cell {
  padding: 0px 10px 4px 4px;
}

/* File detail row - normal background */
.dlm-table tr.dlm-detail-row1 {
  background-color: #F0F0F0;
  color: #000;
}

/* File detail row - darker background */
.dlm-table tr.dlm-detail-row2 {
  background-color: #E0E0E0;
  color: #000;
}

/* File description */
.dlm-table td.dlm-file-description {
  font-weight: normal;
  padding-bottom: 10px;
}

span.dlm-more-link {
  font-style: italic;
}

.dlm-table td.dlm-embed-cell {
  padding: 0px 10px 4px 0px;
}

/* File detail name */
.dlm-table td.dlm-detail-name {
  font-weight: bold; /* normal */
  min-width: 150px;
  padding: 4px 0px 2px 4px;
  text-align: left;
  width: 150px;
  vertical-align: top;
}

/* File detail value */
.dlm-table td.dlm-detail-value {
  font-weight: normal;
  padding: 4px 0px 2px 4px;
  overflow: hidden;
  width: 100%;
}

.dlm-table td.dlm-rating-value {
  font-weight: normal;
  padding: 0px 0px 0px 4px;
}

div.dlm-sections-head {
  background-color: #f0f0f0;
  border: 2px solid #ECECEC;
  clear: both;
  color: #000;
  display: block;
  margin-bottom: 10px;
  margin-top: 8px;
  padding: 4px;
  vertical-align: top;
}

.dlm-subsection-container {
  background-color: #e0e0e0;
  border: 1px solid #d0d0d0;
  display: table;
  margin: 4px 10px 4px 0px;
  padding: 4px;
}

.dlm-subsection {
  background-color: #f0f0f0;
  border: 1px solid #e0e0e0;
  display: inline;
  float: left;
  padding: 4px;
  margin: 2px;
  text-align: center;
}

.dlm-float-left {
  display: inline;
  float: left;
}

.dlm-sections-link{
  font-weight:bold;
  border: 0;
  cursor: pointer;
  padding: 2px;
  margin: 0px;
  text-decoration: none !important;
  vertical-align: top;
}

.dlm-sections-image {
  border: 0;
  cursor: pointer;
  padding: 2px;
  margin: 0px;
  text-decoration: none !important;
  vertical-align: top;
}

.dlm-sections-thumb {
  height: 16px;
  width: 16px;
  padding: 2px;
}

.dlm-subsection-image {
  border: 0;
  height: 48px;
  padding: 2px;
  text-decoration: none;
  width: 48px;
}

.dlm-back-link {
  display: block;
  border: 0;
  clear: both;
  margin: 0px;
  padding: 8px 8px 8px 0px;
}

/*
 header table  */
table.dlm-search-header {
  background-color: transparent;
  border: 0px solid #d0d0d0;
  border: none; /* 1px solid #E0E0E0; */
  margin: 10px 0px 0px 0px;
  padding: 0px 0px 0px 2px;
  text-align: left;
  vertical-align: middle;
}

a.dlm-search-header-link {
  font-weight: bold;
}

/* Table containing search input / buttons */
table.dlm-search-table {
  background-color: transparent;
  border: 1px solid #E0E0E0;
  margin: 0px;
  margin-bottom: 4px;
  padding: 0px 0px 2px 2px;
  text-align: left;
  vertical-align: middle;
}
table.dlm-search-table td {
  font-size: 11px;
}

/* Table containing file versions (standalone/mirror) */
table.dlm-file-versions {
  background-color: #E0E0E0;
  border: 0;
  margin: 0px;
  padding: 2px;
  text-align: left;
  vertical-align: middle;
}

/* File versions table cells */
table.dlm-file-versions td {
  background-color: transparent;
  border: 0;
  margin: 0px;
  padding: 2px;
  text-align: left;
}

/* Header row for file versions table */
table.dlm-file-versions tr.dlm-versions-header {
  background-color: transparent;
  border: 1px solid #D0D0D0;
}

/* Detail row in filer version table */
table.dlm-file-versions tr.dlm-versions-row {
  background-color: #F0F0F0;
}

div.dlm-versions-title {
  font-size: 10pt;
  font-weight: bold;
  text-transform: uppercase;
  width: 100%;
}

/* Detail cell in file versions table */
table.dlm-file-versions td.dlm-versions-cell {
  border: 1px solid #D0D0D0;
  font-size: 10pt;
  padding-bottom: 2px;
}

/* Edit File  */
a.dlm-link-edit {
  background: url("images/edit.png") no-repeat scroll left center transparent;
  color: red;
  font-size: 10pt;
  padding-left: 20px;
}

/* Special "More Info" background image */
a.dlm-link-details {
  font-size: 10pt;
  background: url("images/info.png") no-repeat scroll left center transparent;
  font-weight: bold;
  padding-left: 20px;
}

/* Special "Download Now" background image */
a.dlm-link-download {
  font-size: 10pt;
  background: url("images/download.png") no-repeat scroll left center transparent;
  font-weight: bold;
  padding-left: 20px;
}

/* Special "Delete link" background image */
a.dlm-link-remove {
  font-size: 10pt;
  background: url("images/removelink.png") no-repeat scroll left center transparent;
  font-weight: bold;
  padding-left: 20px;
}

/* Special "Open Link" background image */
a.dlm-link-open {
  font-size: 10pt;
  background: url("images/openlink.png") no-repeat scroll left center transparent;
  font-weight: bold;
  padding-left: 20px;
}

/* "Filler" row (with separator in it) */
table.dlm-table td.dlm-detail-filler {
  height: auto;
  margin: 0px;
  padding: 0px;
  border: none;
}

/* Open Link / Download Now / More Info */
table.dlm-table a.dlm-link, table.dlm-table a.dlm-link img {
  font-size: 10pt;
  vertical-align: middle;
}

input.dlm-input {
  background-color: #FFF;
	border: 1px solid #ced5d9;
	font-size: 10pt;
	margin: 0px 4px 0px 0px;
	padding: 0px;
  height: 26px;
  vertical-align: top;
  width: 90%;
}

input.dlm-button {
  background-color: #E0E0E0;
  border: 1px solid #C0C0C0;
  font-size: 9pt;
  font-weight: bold;
  margin: 0px 4px 0px 0px;
  min-height: 24px;
  padding: 0px 4px 4px 4px;
  height: 28px;
  vertical-align: top;
  -moz-border-radius-bottomleft: 10px;
  -moz-border-radius-topright: 10px;
  -moz-border-radius-bottomright: 10px;
  -moz-border-radius-topleft: 10px;
  -webkit-border-top-right-radius: 10px;
  -webkit-border-top-left-radius: 10px;
  -webkit-border-bottom-left-radius: 10px;
  -webkit-border-bottom-right-radius: 10px;
}

input.dlm-button:hover {
  background-color: #C0C0C0;
  border: 1px solid #000;
}

div.dlm-select-div {
  color: #000;
  background-color: #FFF;
  border: 1px solid #ced5d9;
  margin: 0px 4px 0px 0px;
  padding: 3px 0px 0px 0px;
  height: 24px;
  width: 95% !important;
}

div.dlm-select-div .dlm-select {
  color: #000;
  background-color: #FFF;
	border: 0;
	font-size: 10pt;
  margin: 0px 2px 0px 2px;
	padding: 2px;
  height: 22px;
  vertical-align: top;
  width: 98%;
}

/* Embedded media file */
div.dlm-embed {
  background: transparent;
  border: none;
  margin: 6px 0px 6px 0px;
  padding: 0px;
  text-align: left;
  width: 10%;
}

/* ##### Tag Cloud styles ##### */
div.dlm-tagcloud {
  background-color: #fff;
  border: 0px solid #D0D0D0;
  clear: both;
  display: block;
  line-height: 1.8em;
  margin: 0px;
  padding: 4px;
  text-align: justify;
}

a.dlm-tagcloud-style1 { font-size: 14px; color: #479; }
a.dlm-tagcloud-style2 { font-size: 15px; color: #659; }
a.dlm-tagcloud-style3 { font-size: 16px; color: #859; }
a.dlm-tagcloud-style4 { font-size: 17px; color: #a59; }
a.dlm-tagcloud-style5 { font-size: 19px; color: #b49; }
a.dlm-tagcloud-style6 { font-size: 21px; color: #b49; }
a.dlm-tagcloud-style7 { font-size: 23px; color: #c3a; }
a.dlm-tagcloud-style8 { font-size: 25px; color: #d2a; }
a.dlm-tagcloud-style9 { font-size: 27px; color: #e1a; }

/* ##### For ADMIN panel ONLY ##### */

div#dlm_content span.dlm-admin-hint {
  font-size: 12px;
}

div.roundbutton, div.inner { text-align: center; }
div.roundbutton  { float: left; width: 12em; padding: 20px; margin: 1em; cursor:pointer; }

div#dlm_content input.dlm-admin-btn {
  display: block;
  clear: both;
  background-color: #f0f0f0;
  border-top: 1px solid #B0B0B0;
  border-left: 1px solid #B0B0B0;
  border-bottom: 1px solid #A0A0A0;
  border-right: 1px solid #A0A0A0;
  font-size: 15px;
  font-weight: bold;
  height: 58px;
  padding-left: 55px;
  margin-bottom: 8px;
  min-width: 150px;
  text-align: center;
  -moz-border-radius-bottomleft: 10px;
  -moz-border-radius-topright: 10px;
  -moz-border-radius-bottomright: 10px;
  -moz-border-radius-topleft: 10px;
  -webkit-border-top-right-radius: 10px;
  -webkit-border-top-left-radius: 10px;
  -webkit-border-bottom-left-radius: 10px;
  -webkit-border-bottom-right-radius: 10px;
}

div#dlm_content input.dlm-admin-btn:hover {
  background-color: #E0E0E0;
}

div#dlm_content .dlm-admin-link {
  background-color: #E04229; /* blue: #58B9EB; */
  border: 1px solid #D0D0D0;
  color: #FFFFFF;
  cursor: pointer;
  font-family: Helvetica,Arial,sans-serif;
  font-size: 12px;
  font-weight: bold;
  padding: 6px 8px 6px 8px;
  margin: 4px 4px 8px 0px;
  width: auto;
  -moz-border-radius:5px;
  -webkit-border-radius:5px;
  border-radius:5px;
}

div#dlm_content .dlm-admin-link:hover {
  background-color: #e54a32;
}

div#dlm_content .dlm-btn-section-new {
  background: url("../images/misc/section_new.png") no-repeat scroll 12px center transparent;
}

div#dlm_content .dlm-btn-section-edit {
  background: url("../images/misc/section_edit.png") no-repeat scroll 12px center transparent;
}

div#dlm_content .dlm-btn-view-files {
  background: url("../images/misc/view_files.png") no-repeat scroll 12px center transparent;
}

div#dlm_content .dlm-btn-file-upload {
  background: url("../images/misc/file_upload.png") no-repeat scroll 12px center transparent;
}

div#dlm_content .dlm-btn-file-new {
  background: url("../images/misc/file_new.png") no-repeat scroll 12px center transparent;
}

div#dlm_content .dlm-btn-file-edit {
  background: url("../images/misc/file_edit.png") no-repeat scroll 12px center transparent;
}

div#dlm_content .dlm-btn-file-upload {
  background: url("../images/misc/up_blue_48.png") no-repeat scroll 12px center transparent;
}

div#dlm_content .dlm-btn-instant-upload {
  background: url("../images/misc/up_green_48.png") no-repeat scroll 12px center transparent;
}
