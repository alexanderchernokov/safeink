<?php
if(!defined('IN_PRGM')) exit();

class sd_widget {
	
	
	
	private $path 			=	'../widgets/';
	private $widgets 		=	array(); 
	private $count			=	0;
	
	public  $widgetClasses	= 	array();
	public  $html			= 	array();
	public	$disabled		=	false;
	public	$alert			=  '<div class="alert alert-danger">Widgets have been globally disabled</div>';
	
	public function __construct()
	{
		
	}
	
	/**
	* Loads all widgets from /widgets directory into the class
	*
	*/
	public function LoadWidgets()
	{
		if(defined('ENABLE_WIDGETS') && !ENABLE_WIDGETS)
		{
			$this->disabled = true;
			return;
		}
		// Read the widget directory
		$this->_readdir();
		
		foreach($this->widgets as $widget)
		{
			if(file_exists($this->path . $widget['file']) && $widget['active'])
			{
				$widgetclass = strtolower(str_replace(' ' , '_', $widget['title']));
				require_once($this->path . $widget['file']);
				${$widgetclass} = new $widgetclass;
				${$widgetclass}->init();
				$this->compile(${$widgetclass}->output, $widget);
			}
		}
		
		// Total Widget Count
		$this->count = sizeof($this->widgets);
		
		// Cleanup deleted widgets
		$this->_cleanup();
	}
	
	/**
	* Returns total widget count
	*
	* @return int widget count
	*/
	public function GetCount()
	{
		return $this->count;
	}
	
	/**
	* Returns a single widget in the array
	*
	* @param int key
	* @return array widget
	*/
	public function GetWidget($key)
	{
		return $this->widgets[$key];

	}
	
	/**
	* Traverses the widgets directory to find all widgets
	*/
	private function _readdir()
	{	
		$widgets = array();  // holding value
		if($handle = opendir($this->path))
		{
			while(false !== ($entry = readdir($handle)))
			{
				if($entry != '.' && $entry != '..' && $entry != 'index.html')
				{
					$widgets[] = $entry;
					
				}
			}
			
			closedir($handle);
		}
		
		foreach($widgets as $key => $value)
		{
			$this->_parseWidget($value);
		}
	}
	
	/**
	* Reads widget header info
	*
	* @param string $widget
	*/
	private function _parseWidget($widget)
	{
		global $DB;
		
		// Get contents of widget and grab the required header
		$_widget = file_get_contents($this->path . $widget);
		if(preg_match('/\/\*\*(.+)\*\//is', $_widget, $header))
		{
		
			// Get header info
			$info = explode(':', $header[1]);
			
			$title 		= trim(substr($info[1], 0, -11));
			$desc		= trim(substr($info[2], 0, -6));
			$author 	= trim(substr($info[3], 0, -7));
			$version	= trim($info[4]);
			$active		= 1;
					
			// check database for widget
			if(!$existing = $DB->query_first("SELECT id, active from {widgets} WHERE file = '$widget'"))
			{
				$DB->query("INSERT INTO {widgets} (file, title, descr, author, version, active) 
								VALUES ('$widget', '$title', '$desc', '$author', '$version', 1)");
				$id = $DB->insert_id();
				
			}
			else
			{
				$active = $existing['active'];
				$id 	= $existing['id'];
			}
			
			// Store the values
			$this->widgets[] = array('file'		=>	$widget,
									'title'		=>	$title,
									'descr'		=>	$desc,
									'author'	=>	$author,
									'version'	=>	$version,
									'active'	=>	$active,
									'id'		=>	$id
									);	
		}
	}
	
	/**
	* Adds a key => value pair to the widget variable
	*
	* @param mixed key
	* @param mixed value
	*/
	private function AddKey($key, $value)
	{
		$this->widgets[$key] = $value;
	}
	
	/**
	* Constructs the widget box
	*
	*/
	private function constructWidgetBox($widget, $body)
	{
		$output = '
		<div class="widget-container-col" id="widget-'.$widget['id'].'">
			<div class="widget-box">
				<div class="widget-header">
					<h4 class="widget-title smaller">' . $widget['title'] . '</h4>
					<div class="widget-toolbar">
						<!-- <a data-action="settings" href="#"><i class="ace-icon fa fa-bars"></i></a>
						<a data-action="reload" href="#"><i class="ace-icon fa fa-refresh"></i></a>
						<a data-action="fullscreen" class="orange2" href="#"><i class="ace-icon fa fa-expand"></i></a> -->
						<a data-action="collapse" href="#"><i class="ace-icon fa fa-chevron-up"></i></a>
						<a data-action="close" href="#" class="widget-close"><i class="ace-icon fa fa-times"></i></a>
					</div>
				</div>
				<div class="widget-body">
					<div class="widget-main">' .
					$body
					. '</div>
					<div class="widget-toolbox padding-8">  </div>
				</div>
			</div>
		</div>';
		
		return $output;
	}
	
	/**
	* Removes widgets from the database that are no longer in the directory
	*/
	private function _cleanup()
	{
		global $DB;
		
		$widgetsq = $DB->query("SELECT id, file FROM {widgets}");
		
		while($widget = $DB->fetch_array($widgetsq))
		{
			if(!file_exists($this->path . $widget['file']))
			{
				$DB->query("DELETE FROM {widgets} WHERE id=$widget[id]");
			}
		}
	}
	
	/**
	* Outputs widget to screen
	*/
	public function compile($output, $widget)
	{
		$this->html[] = $this->constructWidgetBox($widget, $output);
	}
	
	/**
	* Loads a single widget
	*
	* @param int widgetid
	*/
	public function LoadSingleWidget($widgetid)
	{
		global $DB;
		
		// Get the widget
		$widget = $DB->query_first("SELECT * FROM {widgets} WHERE id=$widgetid");
		$DB->query("UPDATE {widgets} SET active=1 WHERE id=$widgetid");
		
		if(file_exists($this->path . $widget['filename']))
		{
			$widgetclass = strtolower(str_replace(' ' , '_', $widget['title']));
			require_once($this->path . $widget['file']);
			${$widgetclass} = new $widgetclass;
			${$widgetclass}->init();
			return $this->ConstructWidgetBox($widget, ${$widgetclass}->output);
		}
	}
	
}