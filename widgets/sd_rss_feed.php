<?php
/**
Widget Name: Subdreamer RSS Feed
Description: Subdreamer CMS widget to display recent blog posts via RSS
Author: Subdreamer CMS
Version: 1.0.0
*/

class subdreamer_rss_feed extends sd_widget {
	
	public $title	= 	'';
	public $desc	= 	'';
	public $author	= 	'';
	public $version	=	'';
	public $output	= 	'';
	public $feed	= 	'http://antiref.com/?http://subdreamer.com/rss.php';
	public $limit	=	5;
	
	public function __construct($widget = array())
	{
		$this->title 	= $widget['title'];
		$this->desc		= $widget['desc'];
		$this->author	= $widget['author'];
		$this->version	= $widget['version'];
	}
	
	public function init()
	{
		 /*$rss = simplexml_load_file($this->feed);
    

		$i=0;
		foreach ($rss->channel->item as $item) 
		{
			if($i == $this->limit) break;
			$link = (string) $item->link; // Link to this item
			$title = (string) $item->title; // Title of this item
			$guid	= (string) $item->guid;	// URL
			$description = (string) $item->description;
			$i++;
			
			$this->output .= '<h5 class="lighter no-margin-bottom no-padding-bottom"><a href="'.$link.'" Target="_blank">' .$title.'</a></h5>
								<p>' . $this->trimDesc($description) . '&nbsp;&nbsp;<a href="'.$link.'" Target="_blank">Read More.</a></p>';
			
		}
		 */
	}
	
	private function trimDesc($desc)
	{
		$width = 200;
		$desc = strip_tags($desc);
		if (strlen($desc) > $width)
		{
			$desc = wordwrap($desc, 200);
			$i = strpos($desc, "\n");
			if ($i) {
				$desc = substr($desc, 0, $i);
			}
		}

		return $desc . '...';
	}
	
}
