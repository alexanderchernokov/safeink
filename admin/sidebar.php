<?php
if(!defined('IN_ADMIN') || !defined('SD_ADMIN_SIDEBAR') || !SD_ADMIN_SIDEBAR) return true;

# ***** HEAD content for sidebar incl. styles and JS code *****
define('SD_ADMIN_SIDEBAR_HEAD', '
<!-- SIDEBAR START -->
<link rel="stylesheet" type="text/css" href="'.$sdurl.'includes/css/mbExtruder.css" />
<script type="text/javascript">
// <![CDATA[
jQuery(document).ready(function() {
  $("#sd_admin_sidebar").buildMbExtruder({
    position:"right",
    width:200,
    top:126,
    extruderOpacity:0.8,
    hidePanelsOnClose:true,
    accordionPanels:true,
    slideTimer:300,
    onExtOpen:function(){},
    onExtContentLoad:function(){},
    onExtClose:function(){}
  });
  $("#wbt").hover(
    function () { $(this).addClass("hover"); },
    function () { $(this).removeClass("hover"); }
  );
  $("#wbt").click(function(e){
    e.preventDefault();
    $("ul#wbtlist").toggle();
    return false;
  });
});
// ]]>
</script>
<style type="text/css">
  .extruder .text {font:14px/16px Helvetica,Arial,sans-serif;color:white;padding:10px;border-bottom:1px solid #333333;}
  .extruder .right.a .flap{
    font-size:18px;
    color:white;
    top:0;
    padding:10px 0 10px 10px;
    background:#772B14;
    width:30px;
    position:absolute;
    right:0;
    -moz-border-radius:0 10px 10px 0;
    -webkit-border-top-right-radius:10px;
    -webkit-border-bottom-right-radius:10px;
    -moz-box-shadow:#666 2px 0px 3px;
    -webkit-box-shadow:#666 2px 0px 3px;
  }

  .extruder ul { list-style: none }
  .extruder ul li { list-style-type: none }
</style>
<!-- SIDEBAR END -->');

# ***** Sidebar content incl. dynamic section with favorites/recent pages *****
$admin_sidebar = '
<div id="sd_admin_sidebar" class="{title:\'More\'}" >
  <div class="text optionsPanelBg">
    <span class="sprite" style="display:inline-block"><i class="icon-dashboard"></i></span>
    <a href="skins.php" style="display:inline;">'.AdminPhrase('menu_skins').'</a><br />

    <span class="sprite" style="display:inline-block"><i class="icon-edit-sign"></i></span>
    <a href="templates.php?action=display_templates" style="display:inline;">'.AdminPhrase('menu_templates').'</a><br />
';
// Add list of favorite/recently used plugins (experimental)
global $plugin_names;
$default = array(
  'p2_news' => 'font',
  'media_gallery' => 'film',
  'gallery' => 'picture',
  'p17_image_gallery' => 'camera',
  'forum' => 'user',
  'download_manager' => 'download');
foreach($default as $key => $class)
{
  if($pid = GetPluginIDbyFolder($key))
  {                                     #style="display:inline"
    #$admin_sidebar .= '<a class="labelx sprite sprite-'.$plugin_names[$pid].'" href="view_plugin.php?pluginid='.$pid.'">'.$plugin_names[$pid].'</a>';
    $admin_sidebar .= '
    <span class="sprite" style="display:inline-block"><i class="icon-'.$class.'"></i></span>
    <a href="view_plugin.php?pluginid='.$pid.'" style="display:inline;">'.$plugin_names[$pid].'</a><br />';
  }
}
$admin_sidebar .= '
  </div>
  <div class="voice {}"><a class="icon-search label" href="#" onclick="javascript:return false;"> Search</a> </div>
  <div class="voice {}"><a class="icon-rss label" target="_blank" href="http://antiref.com/?http://www.subdreamer.com/rss.php" title="RSS"> RSS</a> </div>
  <div class="voice {}"><a class="icon-twitter label" target="_blank" href="https://www.twitter.com/subdreamer" title="Twitter"> Twitter</a> </div>
  <div class="voice {}"><a class="icon-facebook label" target="_blank" href="https://www.facebook.com/SubdreamerCMS" title="Facebook">&nbsp; Facebook</a> </div>
  <div class="text">
    <a id="wbt" class="icon-bolt label"> Web Tools</a>
    <ul class="optionsPanelx" id="wbtlist" style="display:none">
      <li><br /></li>
      <li><a class="cbox" target="_blank" href="http://www.colorzilla.com/gradient-editor/">CSS Gradient Generator</a></li>
      <li><a class="cbox" target="_blank" href="http://tools.dynamicdrive.com/imageoptimizer/">Image Optimizer</a></li>
      <li><a class="cbox" target="_blank" href="http://tools.dynamicdrive.com/favicon/">FavIcon Generator</a></li>
      <li><a class="cbox" target="_blank" href="http://tools.dynamicdrive.com/animatedgif/">Animated Gif Generator</a></li>
      <li><a class="cbox" target="_blank" href="http://www.dynamicdrive.com/emailriddler/">Email Riddler</a></li>
      <li><a class="cbox" target="_blank" href="http://tools.dynamicdrive.com/gradient/">Gradient Image Maker</a></li>
      <li><a class="cbox" target="_blank" href="http://tools.dynamicdrive.com/password/">.htaccess Password Generator</a></li>
      <li><a class="cbox" target="_blank" href="http://tools.dynamicdrive.com/userban/">.htaccess Banning Generator</a></li>
      <li><a class="cbox" target="_blank" href="http://tools.dynamicdrive.com/button/">Button Maker</a></li>
      <li><a class="cbox" target="_blank" href="http://tools.dynamicdrive.com/ribbon/">Ribbon Rules Generator</a></li>
    </ul>
  </div>
</div>
';
/*
  <div class="voice {}"><a class="label" href="http://firefox.com" target="_blank">get FireFox</a> </div>
  <div class="voice {}"><a class="label" href="http://www.google.com/chrome" target="_blank">get Chrome</a> </div>
  <div class="voice {}"><a class="label" href="http://www.apple.com/safari/" target="_blank">get Safari</a> </div>
  <div class="voice {}"><a class="label" href="http://www.opera.com/" target="_blank">get Opera</a> </div>
*/

// DO NOT CHANGE ANYTHING BELOW!
define('SD_ADMIN_SIDEBAR_LOADED', true);
define('SD_ADMIN_SIDEBAR_HTML', $admin_sidebar);
unset($admin_sidebar);
