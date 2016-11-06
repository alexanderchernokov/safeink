<?php
if(!defined('IN_PRGM')) exit();

if(!defined('IN_ADMIN'))
{
  $plugin_folder = sd_GetCurrentFolder(__FILE__);
  if($pluginid = GetPluginIDbyFolder($plugin_folder))
  {
    global $sd_tagspage_link;
    $js = '<script type="text/javascript">
//<![CDATA[
if(typeof jQuery !== "undefined") {
jQuery(document).ready(function() {
  jQuery(document).delegate("div#p'.$pluginid.'_archive a.arc_year","click",function(e){
    e.preventDefault();
    $(this).find("span").toggle().end();
    var myparent = $(this).parent("div.year_container");
    myparent.find("ul.arc_months_list").toggle();
    return false;
  });
  jQuery(document).delegate("div#p'.$pluginid.'_archive a.arc_month","click",function(e){
    e.preventDefault();
    $(this).find("span").toggle().end();
    var myparent = $(this).parent("div.month_container");
    myparent.find("ul.arc_articles_list").toggle();
    return false;
  });
  jQuery(document).delegate("div#p'.$pluginid.'_archive select[name=p'.$pluginid.'_selector]","change",function(e){
    var tags = jQuery(this).val();
    var mya = jQuery("a#p'.$pluginid.'_refresh");
    jQuery(mya).show();
    jQuery(mya).attr("href", "'.$sd_tagspage_link.'" + tags);
    jQuery(mya).trigger("click");
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
