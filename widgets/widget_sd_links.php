<?php
/**
Widget Name: Helpful Links
Description: Subdreamer CMS widget to display text
Author: Subdreamer CMS
Version: 0.1a
*/

class helpful_links extends sd_widget {
	
	public $title	= 	'';
	public $desc	= 	'';
	public $author	= 	'';
	public $version	=	'';
	public $output	= 	'';
	
	public function __construct($widget = array())
	{
		$this->title 	= $widget['title'];
		$this->desc		= $widget['desc'];
		$this->author	= $widget['author'];
		$this->version	= $widget['version'];
	}
	
	public function init()
	{
		$this->output = '
		<ul class="list-unstyled">
  <li><i class="ace-icon fa fa-support blue bigger-110"></i> Subdreamer CMS <a href="http://antiref.com/?http://www.subdreamer.com/orders/members.html?view=support_desk" target="_blank">Support Desk</a></li>
  <li><i class="ace-icon fa fa-comment blue bigger-110"></i> Subdreamer CMS <a href="http://antiref.com/?http://www.subdreamer.com/forum/announcements-f80.html" target="_blank">Announcements</a></li>
  <li><i class="ace-icon fa fa-book blue bigger-110"></i >Consult Subdreamer CMS <a href="http://antiref.com/?http://www.subdreamer.com/docs/index.php" target="_blank">Manuals</a></li>
  <li><i class="ace-icon fa fa-pencil blue bigger-110"></i> Browse Subdreamer CMS <a href="http://antiref.com/?http://www.subdreamer.com/downloads/skins.html" target="_blank">Skins</a></li>
  <li><i class="ace-icon fa fa-puzzle-piece blue bigger-110"></i> Browse Subdreamer CMS <a href="http://antiref.com/?http://www.subdreamer.com/downloads/plugins.html" target="_blank">Plugins</a></li>
  <li><i class="ace-icon fa fa-comments blue bigger-110"></i> Subdreamer CMS <a href="http://antiref.com/?http://www.subdreamer.com/forum.html" target="_blank">Community Forum</a>.</li>
  </ul>';
	}
}