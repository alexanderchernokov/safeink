<?php
/**
Widget Name: New User Stats
Description: Subdreamer CMS widget to display new users for month, week and year
Author: Subdreamer CMS
Version: 1.0.0
*/

class new_user_stats extends sd_widget {
	
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
		global $DB;
		
		$timezone = date_default_timezone_get();
		date_default_timezone_set('UTC');
		$time = time();
		$colort = $colorw = $colory = 'red';
		$statt = $statw = $staty = 'important';
		
		$todaystart = $starttime = strtotime(date('Y-m-d 00:00:00', $time));  
		$weekstart	= strtotime('last sunday', $time);  
		$yearstart	= strtotime(date('Y-1-1 00:00:00', $time)); 
		
		// Get users for today and this week
		if($todaystart && $weekstart && $yearstart)
		{
			$today = $DB->query_first("SELECT COUNT(*) as tot FROM {users} WHERE joindate > $todaystart");       
			$thisweek = $DB->query_first("SELECT COUNT(*) as tot FROM {users} WHERE joindate > $weekstart");
			$thisyear = $DB->query_first("SELECT COUNT(*) as tot FROM {users} WHERE joindate > $yearstart");
						
			// compute statistics
			if((int)$today['tot'] > 0)
			{
				$colort = 'green';
				$statt = 'success';
			}
			
			// compute statistics
			if((int)$thisweek['tot'] > 0)
			{
				$colorw = 'green';
				$statw = 'success';
			}
			
			if((int)$thisyear['tot'] > 0)
			{
				$colory = 'green';
				$staty = 'success';
			}
			
		}
		
		$this->output = '
		<form class="form-search" method="POST" action="users.php">
		 '.PrintSecureToken().'
  		<input type="hidden" name="action" value="display_users" />
  		<input type="hidden" name="search" value="true" />
		<div class="input-group">
			<input type="text" class="form-control search-query" name="username" placeholder="User Search" />
			<span class="input-group-btn">
				<button type="submit" class="btn btn-purple btn-sm">
					Search
					<i class="ace-icon fa fa-search icon-on-right bigger-110"></i>
				</button>
			</span>
		</div>
		</form>
		<div class="space-10"></div>
		<div class="infobox infobox-'.$colort.'">
			<div class="infobox-icon">
				<i class="ace-icon fa fa-user"></i>
			</div>
			<div class="infobox-data">
				<span class="infobox-data-number">' . ($today['tot'] !== false ? $today['tot'] : '?') . '</span>
				<div class="infobox-content">New users today</div>
			</div>
			
		</div>
		<div class="infobox infobox-'.$colorw.'">
			<div class="infobox-icon">
				<i class="ace-icon fa fa-user"></i>
			</div>
			<div class="infobox-data">
				<span class="infobox-data-number">' . ($thisweek['tot'] !== false ? $thisweek['tot'] : '?') . '</span>
				<div class="infobox-content">New users this week</div>
			</div>
			
			
		</div>
		<div class="infobox infobox-'.$colory.'">
			<div class="infobox-icon">
				<i class="ace-icon fa fa-user"></i>
			</div>
			<div class="infobox-data">
				<span class="infobox-data-number">' . ($thisyear['tot'] !== false ? $thisyear['tot'] : '?') . '</span>
				<div class="infobox-content">New users this year</div>
			</div>
		</div>
		';

	}
	
	private function getLastWeek()
	{
		$previous_week = strtotime("-1 week +1 day");

		$start_week = strtotime("last sunday midnight",$previous_week);
		$end_week = strtotime("next saturday",$start_week);

		$start_week = date("Y-m-d",$start_week);
		$end_week = date("Y-m-d",$end_week);

		return array('start' => $start_week, 'end' => $end_week);
	}
	
	private function getYesterday()
	{
		$starttime = mktime(0, 0, 0, date('m'), date('d')-1, date('Y'));     
		$endtime = mktime(23, 59, 59, date('m'), date('d')-1, date('Y')); 
		
		return array('start' => $starttime, 'end' => $endtime );
	}
}