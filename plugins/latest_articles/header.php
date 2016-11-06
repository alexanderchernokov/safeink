<?php
if(!defined('IN_PRGM')) exit();

if(defined('IN_ADMIN'))
{
  // Below code is for the online/offline toggle (the "width" is only when
  // toggler is inside a table cell!)
  sd_header_add(array(
  	'css'	=> array('../admin/styles/' . ADMIN_STYLE_FOLDER_NAME .'/assets/css/jdialog.css',),
    'other' => array('
<style type="text/css">
ul.page_matches {
  background-color: #fff;
  border: 1px solid #d0d0d0;
  max-height: 150px;
  overflow-y: scroll;
  padding: 2px;
  margin-bottom: 4px;
  margin-top: 4px;
  width: 90%;
  vertical-align: top;
}
ul.page_matches li {
  background-color: #fff;
  border: none;
  height: 22px !important;
  list-style-type: none;
  padding: 0px;
  margin: 0px;
  vertical-align: top;
}
ul.page_matches div {
  background-color: transparent;
  display: inline;
  float: left;
  padding: 1px;
  margin: 1px;
  overflow: hidden;
  position: relative;
}
ul.page_matches li img {
  display: inline;
  float: right;
  margin: 0px;
  padding-top: 2px;
  position: relative;
  right: 10px;
  width: 16px;
}
ul.page_matches li:hover {
  color: #fff;
  cursor: pointer;
  background-color: #00BFEB;
}
</style>
<script type="text/javascript">
//<![CDATA[
var latest_articles_options = {
  include_path: "'.SD_INCLUDE_PATH.'",
  popup_change_options_title: "'.AdminPhrase('popup_change_options_title').'",
  delete_confirm: "'.AdminPhrase('delete_confirm').'"
};
var $loader=false, $loader_inner=false, $matches_container=false;

if (typeof jQuery !== "undefined") {
jQuery(document).ready(function() {
  $loader       = $("div#loader");
  $loader_inner = $loader.find("div:first");

  $(document).delegate("form#changepageoptions_form","submit",function(e) {
    e.preventDefault();
    if($matches_container !== false) {
      jDialog.close();
      var formdata = $(this).serialize();
      $matches_container.load($(this).attr("action"), formdata, function(){
      });
    }
  });

  $("table#matches-list").delegate("a.match_delete","click",function(e){
    e.preventDefault();
    jDialog.close();
    if(confirm(latest_articles_options.delete_confirm)!==true) return false;
    var url = $(this).attr("href");
    location.replace(url);
  });

  $("table#matches-list").delegate("a.change-matches","click",function(e){
    e.preventDefault();
    jDialog.close();
    var elem, checked;

    // Fetch user status values
    $matches_container = $(this).parent();
    var match_option = parseInt($matches_container.find("input[name=match_option]").val(),10);

    $(this).jDialog({
      align   : "left",
      content : $("#changepageoptions").html(),
      close_on_body_click : true,
      idName  : "status_popup",
      title   : latest_articles_options.popup_change_options_title,
      width   : 300
    });

    pageid = parseInt($(this).attr("rel"),10);
    $("form#changepageoptions_form input[name=pageid]").val(pageid);
    if(match_option === -2) {
      var match_pages = $matches_container.find("input[name=match_pages]").val();
      if(match_pages !== "undefined"){
        var pages = [], select;
        select = $("div#status_popup select#page_matches_options");
        select.removeAttr("selected");
        pages = match_pages.split(",");
        for (var i = 0; i < pages.length; ++i) {
          if(parseInt(pages[i], 10) > 0) {
            select.find("option[value="+pages[i]+"]").attr("selected","selected");
          }
        }
      }
    }

    // Assign values to popup form
    $("div#status_popup input[type=radio]").each(function(){ $(this).attr("checked", ""); });
    if(match_option ===  0) $("div#status_popup input#changeoption_all").attr("checked", "checked");
    if(match_option === -1) $("div#status_popup input#changeoption_current").attr("checked", "checked");
    if(match_option === -2) $("div#status_popup input#changeoption_selected").attr("checked", "checked");
  });
});
}
//]]>
</script>
')));

  return;

} //IN_ADMIN

if(!defined('IN_ADMIN'))
{
  $plugin_folder = sd_GetCurrentFolder(__FILE__);
  if($pluginid = GetPluginIDbyFolder($plugin_folder))
  {
    $js = '<script type="text/javascript">
//<![CDATA[
if(typeof jQuery !== "undefined") {
jQuery(document).ready(function() {
  jQuery(document).delegate("div#p'.$pluginid.' .pagination a","click",function(e){
    var la_page = $(this).attr("href");
    var parampos = la_page.lastIndexOf("latestarticles'.$pluginid.'=");
    if(parampos > 0){
      e.preventDefault();
      la_page = la_page.substring(parampos+'.(15+strlen($pluginid)).');
      jQuery("div#p'.$pluginid.'").load(sdurl+"plugins/'.$plugin_folder.'/latest_articles.php?categoryid='.$categoryid.'&latestarticles'.$pluginid.'="+la_page);
      return false;
    }
    return true;
  });
})
}
//]]>
</script>
';
    sd_header_add(array(
      'other' => array($js)
    ));
  }
}
