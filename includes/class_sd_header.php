<?php
if(!defined('IN_PRGM')) exit();

/**
* Compiles and outputs components of html <head> tags
* including javascript and css
*/
class sd_header {
	
	private $header_js 		= array();
	private $header_css 	= array();
	private $header_script 	= '';
	private $settings		= array();
	private $header_meta	= array();
	private $header_js_prepend = '';

	/**
	* Constructor
	*/
	public function __construct()
	{
		/*
		Start building the global header used in all pages
		These scripts load in a specific order.  We'll add them
		here first
		*/
		$this->header_js_prepend = '
		<!-- ace settings handler -->
		<script src="' . SITE_URL . ADMIN_STYLES_FOLDER . 'assets/js/ace-extra.min.js"></script>

		<!-- HTML5shiv and Respond.js for IE8 to support HTML5 elements and media queries -->

		<!--[if lte IE 8]>
		<script src="' . SITE_URL . ADMIN_STYLES_FOLDER . 'assets/js/html5shiv.min.js"></script>
		<script src="' . SITE_URL . ADMIN_STYLES_FOLDER . 'assets/js/respond.min.js"></script>
		<![endif]--> ' . ( defined('JQUERY_GA_CDN') ? '<script src="' . JQUERY_GA_CDN .'"></script>' : '<!--[if !IE]> -->
		<script type="text/javascript">
			window.jQuery || document.write("<script src=\"' . SD_JS_PATH . JQUERY_FILENAME .'\">"+"<"+"/script>");
		</script>
		<!-- <![endif]-->'). '
		<!--[if IE]>
        <script type="text/javascript">
         window.jQuery || document.write("<script src=\"' . SITE_URL . ADMIN_STYLES_FOLDER . 'assets/js/jquery1x.min.js\">"+"<"+"/script>");
        </script>
        <![endif]-->
		
		<script type="text/javascript">
			if("ontouchstart" in document.documentElement) document.write("<script src=\"' . SITE_URL . ADMIN_STYLES_FOLDER . 'assets/js/jquery.mobile.custom.min.js\">"+"<"+"/script>");
		</script>
		';
		
	}
	
	/**
	* Adds a Subdreamer Mainsettings variable or
	* any other variable to the class for use later
	*
	* @param string setting_name
	* @param string setting_value
	*/
	public function AddSetting($name, $value)
	{
		$this->settings[$name] = $value;
	}
	
	/**
	* Adds Meta tag information to header
	*
	* @param string array meta
	*/
	public function AddMeta($meta)
	{
		if(is_array($meta))
		{
			$this->header_meta = array_merge($this->header_meta, $meta);
		}
		else
		{
			$this->header_meta = $meta;
		}
	}
	
	/**
	* Adds custom JavaScript to the head
	*
	* @param string array $script
	*/
	public function AddScript($script)
	{
		if(is_array($script))
		{
			foreach($script as $value)
			{
				$this->header_script .= $value;
			}
		}
		else
		{
			$this->header_script .= $script;
		}
	}
	
	/**
	* Adds new line of JavaScript include files to header
	*
	* @param string array js
	*/
	public function AddJS($js)
	{
		if(is_array($js))
		{
			$this->header_js = array_merge($this->header_js, $js);
		}
		else
		{
			$this->header_js[] = $js;
		}
	}
	
	/**
	* Adds new line of CSS to header
	*
	* @param string array js
	*/
	public function AddCSS($css)
	{
		if(is_array($css))
		{
			$this->header_css = array_merge($this->header_css, $css);
		}
		else
		{
			$this->header_css[] = $css;
		}
	}
	
	/**
	* Prints inline JavaScript & jQuery
	*
	*/
	public function PrintScript()
	{
		
		echo '<script type="text/javascript">
			if (typeof(jQuery) !== "undefined") {
				jQuery(document).ready(function() {
			  	(function($){
					$("[data-rel=popover]").popover({
						container:"body",
						html: true,});
					$(".mytooltip").tooltip();
			  	})(jQuery);
				});
			}
			</script>';
		
		echo $this->header_script;

	}
	
		
	
	/**
	* Prints JavaScript include Files
	*/
	public function PrintJavaScript()
	{
		// SD400
		// Move any instance of g=admin_all to the top of the array
		// Only in minify in enabled
		if(defined('ENABLE_MINIFY') && ENABLE_MINIFY)
		{
			foreach($this->header_js as $key => $value)
			{
				if(stristr($value, 'index.php?g=admin_all') !== false)
				{
					$tmp = $this->header_js[$key];
					unset($this->header_js[$key]);
					array_unshift($this->header_js, $tmp);
				}
				// Probably not using minify at this stage.  We still need jquery
				
			}
		}
		
		
		$this->header_js = array_filter($this->header_js);
		
		// Need to include this above JavaScript Files call
		// as certain files need the variables referenced
		// display global Scripts first
		echo '<script type="text/javascript">
		//<![CDATA[
		var sdurl = "' . SITE_URL . '";
		var sd_page_title = "' . addslashes($this->settings['title']) . '";
		var admin_images = "'. SITE_URL.ADMIN_STYLES_FOLDER.'images/";
		var wysiwyg_disabled = '.(empty($this->settings["wysiwyg_disabled"])?'false':'true').';
		//]]>
		</script>';
		echo "\n" . $this->header_js_prepend;
		
		// echo global JavaScript
		//echo array_shift($this->header_js);
		echo "\n" . '<!-- Page Specific Plugin Scripts -->' . "\n";
		
		foreach($this->header_js as $key => $value)
		{
			
			echo '<script src="' . $value . '"></script>' . "\n";
		
		}
		
		// echo global admin scripts
		//echo'
		//<!-- ace scripts -->
		//<script src="' . SITE_URL . ADMIN_STYLES_FOLDER . 'assets/js/ace-elements.min.js"></script>
		//<script src="' . SITE_URL . ADMIN_STYLES_FOLDER . 'assets/js/ace.min.js"></script>';
	}
	
	/**
	* Prints CSS included files
	*/
	public function PrintCSS()
	{
		foreach($this->header_css as $value)
		{
			echo "\t" . '<link rel="stylesheet" type="text/css" href="' . $value . '" />' . "\n";
		}
	}
}