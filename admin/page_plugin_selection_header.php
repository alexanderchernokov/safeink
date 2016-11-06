<?php
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');

include(ROOT_PATH . 'includes/init.php');
$admin_phrases = LoadAdminPhrases(1);
CheckAdminAccess('pages');

$action     = GetVar('action', 'display_plugin_selection', 'string');
$categoryid = GetVar('categoryid', 0, 'whole_number');
$designid   = GetVar('designid', 0, 'whole_number');
$mobile     = (!empty($_GET['mobile']) && SD_MOBILE_FEATURES?'1':'0'); //SD370

$DB->result_type = MYSQL_ASSOC;
if($theme_arr = $DB->query_first('SELECT * FROM {skins} WHERE activated = 1'))
{
  // GET CATEGORY (= PAGE)
  $category_name = '';
  if($categoryid && !$designid)
  {
    $DB->result_type = MYSQL_ASSOC;
    if($category_arr = $DB->query_first('SELECT name, '.($mobile?'mobile_':'').'designid as designid FROM {categories}'.
                                        ' WHERE categoryid = %d LIMIT 1',
                                        $categoryid))
    {
      $category_name = $category_arr['name'];
      $designid = $category_arr['designid'];
    }
  }

  // GET ALL DESIGNS
  $design_selection = '';
  if($get_designs = $DB->query('SELECT designid, design_name FROM {designs}'.
                               ' WHERE skinid = %d ORDER BY design_name',
                               $theme_arr['skinid']))
  {
    $design_selection = AdminPhrase('pages_select_plugin_layout')
                        . '<select id="designid" class="col-sm-2">';

    for($design_count = 1; $design_arr = $DB->fetch_array($get_designs,null,MYSQL_ASSOC); $design_count++)
    {
      $design_selection .= '<option value="' . $design_arr['designid'] . '" ' .
                           ($design_arr['designid'] == $designid ? 'selected="selected"' : '') . '>' .
                           AdminPhrase('pages_layout') . ' ' .
                           (strlen($design_arr['design_name']) ? $design_arr['design_name'] : $design_arr['designid']) .'</option>';
    }

    $design_selection .= '</select></form>';
    if($design_count > 1)
    {
      $DB->free_result($get_designs);
    }

    unset($get_designs, $design_count, $design_arr);
  }
}


// ############################################################################
// DISPLAY PLUGIN SELECTION HEADER
// ############################################################################

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<style type="text/css">
body {
  background-color: #fff;
  margin: 0;
  padding: 0;
  font-size: 12px;
  font-family: "Helvetica", Arial, sans-serif; }
div#left {
  position: absolute;
  width: auto;
  height: auto;
  top: 15px;
  left: 15px;
  margin: 0;
  padding: 0; }
div#middle {
  position: absolute;
  width: auto;
  height: auto;
  margin: 0 auto;
  padding: 0; }
div#middle {
  position: relative;
  width: 200px;
  height: auto;
  margin: 10px auto 0 auto; }
div#right {
  position: absolute;
  width: auto;
  height: auto;
  top: 15px;
  right: 15px;
  margin: 0;
  padding: 0;
  font-size: 16px;
  font-weight: bold; }
#footer {
  position: absolute;
  bottom: 0;
  width: 100%;
  height: 1px;
  background: #000; }
select {
  width: 180px;
  margin-left: 10px; }
select, option {
  background: #fff repeat scroll 0 0;
  color: #272727;
  font-size: 12px; }
input[type="submit"] {
  background: #D7DBDC url(styles/' . ADMIN_STYLE_FOLDER_NAME . '/images/input_submit_bg.gif) repeat-x scroll center top;
  border: 1px solid #C5CBCB;
  color: #222222;
  cursor: pointer;
  font-size: 12px;
  font-weight: 600;
  height: 30px;
  min-width: 150px;
  padding: 4px 6px; }
</style>
<script type="text/javascript" src="'.(defined('JQUERY_GA_CDN') && strlen(JQUERY_GA_CDN)?JQUERY_GA_CDN:SITE_URL.'includes/javascript/'.JQUERY_FILENAME).'"></script>
<script type="text/javascript" src="'.SITE_URL.'includes/javascript/jquery-migrate-1.2.1.min.js"></script>
<script type="text/javascript">
jQuery(document).ready(function() {
  jQuery("#designid").change(function() {
    var designid = $(this).val();
    parent.frames["page_plugin_selection_content"].location="page_plugin_selection_content.php?action=' .
      $action.'&categoryid='.$categoryid.'&designid=" + designid + "'.SD_URL_TOKEN.'&mobile='.($mobile?1:0).'";
  });

  jQuery("#submit_plugin_selection").click(function() {
    jQuery("#plugin_selection_form", parent.frames["page_plugin_selection_content"].document).submit();
  });
});
</script>
</head>
<body>
<div id="left">' . $design_selection . '</div>
<div id="middle"><input id="submit_plugin_selection" type="submit" class="btn btn-info" value="' . AdminPhrase('pages_save_plugin_positions') . '" /></div>
<div id="right">' . $category_name . ($mobile ? ' - '.AdminPhrase('pages_mobile_content') : '').'</div>
<div id="footer"></div>
</body>
</html>';
