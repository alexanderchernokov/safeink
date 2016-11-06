<?php
// +---------------------------------------------------+
// |    Code taken from Giorgos (many thanks)          |
// |    http://freshmeat.net/projects/activecalendar/  |
// |    Revision 1: Done by Sublu                      |
// |    Revision 2: Done by abcohen                    |
// |    Please give credit to where its due.           |
// |    Big Credits to Subduck                         |
// |    http://www.subdreamer.com                      |
// +---------------------------------------------------+
/*
v3.7.0, 2013-10-05, tobias:
- renamed plugin to "Calendar2"
- plugin is clonable, no more fixed ID "45"
- added options for source articles/event manager plugins
v3.7.1, 2014-03-03, tobias:
- added options to display x prior and/or y following months
- added option for displaying week numbers
*/

if(!function_exists('DisplayCalendar2'))
{
function DisplayCalendar2()
{
  // Cloning implemented, renamed anything with "45" to use the
  // plugin id determined by plugins' folder
	global $DB, $categoryid, $sdlanguage, $mainsettings,
         $mainsettings_modrewrite, $mainsettings_url_extension,
         $mainsettings_timezoneoffset, $mainsettings_dateformat,
         $plugin_names, $plugin_id_base_arr, $userinfo;

  if(!$plugin_folder = sd_GetCurrentFolder(__FILE__))
  {
    return false;
  }
  if(!$pid = GetPluginIDbyFolder($plugin_folder))
  {
    return false;
  }

  //v1.3.0: support for article-level usergroup permissions (SD 3.4.2!)
  //v1.3.1: support for publish/start end and article plugin options
  //v1.4.0: new settings for source plugin selections

  $language = GetLanguage($pid);
  $settings = GetPluginSettings($pid);

	// include Giorgos's Calendar Class
  if(file_exists(SD_CLASS_PATH.'calendar_class.php'))
    require_once(SD_CLASS_PATH.'calendar_class.php');
  else
	  require_once(ROOT_PATH.'plugins/'.$plugin_folder.'/calendar_class.php');

  $quoted_url_ext = preg_quote($mainsettings_url_extension,'#');

  // v1.3.0: style sheet inclusion moved to "header.php"!

  // Get URL parameters for calendar
  $isSlugged = defined('SD_TAG_DETECTED') && defined('P'.$pid.'_YEAR') && defined('P'.$pid.'_YEAR');
  if($isSlugged)
  {
    $p_yearid  = Is_Valid_Number(constant('P'.$pid.'_YEAR'),date('Y'),2000,2099);
    $p_monthid = Is_Valid_Number(constant('P'.$pid.'_MONTH'),date('m'),1,12);
    $p_dayid   = (!defined('P'.$pid.'_DAY')?0:Is_Valid_Number(constant('P'.$pid.'_DAY'),date('d'),1,31));
  }
  else
  {
	  $p_yearid  = Is_Valid_Number(GetVar('p'.$pid.'_year',  0, 'whole_number', false, true),date('Y'),2000,2099);
	  $p_monthid = Is_Valid_Number(GetVar('p'.$pid.'_month', 0, 'whole_number', false, true),date('m'),1,12);
	  $p_dayid   = Is_Valid_Number(GetVar('p'.$pid.'_day',   0, 'natural_number', false, true),0,0,31);
  }

  $startyear  = $p_yearid;
  $startmonth = $p_monthid;
  $startday   = $p_dayid;
  $settings['cal_prev_months'] = !isset($settings['cal_prev_months'])?0:Is_Valid_Number($settings['cal_prev_months'],0,0,12);
  $settings['cal_next_months'] = !isset($settings['cal_next_months'])?0:Is_Valid_Number($settings['cal_next_months'],0,0,12);
  $total_months = 1 + $settings['cal_prev_months'] + $settings['cal_next_months'];
  if($settings['cal_prev_months'])
  {
    if($startmonth <= $settings['cal_prev_months'])
    {
      $startyear--;
      $startmonth = 13 - $settings['cal_prev_months'];
    }
    else
    {
      $startmonth -= $settings['cal_prev_months'];
    }
  }

  //v371: multi-month display
  $output_started = false;
  $newsItems = array();
  $events = array();
  // Current page url
  $page_url = RewriteLink('index.php?categoryid=' . $categoryid); //v1.3.1

  // Loop from starting month to ending month
  for($month = 0; $month < $total_months; $month++)
  {

	  // Construct new calendar instance and configure it
    $startday = 0;
    if(($startmonth == $p_monthid) && ($startyear == $p_yearid))
    {
      $startday = $p_dayid;
    }
	  $calendar = new activeCalendar($startyear, $startmonth, $startday,
                                   $mainsettings['timezoneoffset']);

	  $calendar->yearID  = 'p'.$pid.'_year';
	  $calendar->monthID = 'p'.$pid.'_month';
	  $calendar->dayID   = 'p'.$pid.'_day';

	  // initialize phrases
	  $calendar->setMonthNames(array($sdlanguage['Jan'],
	                                 $sdlanguage['Feb'],
	                                 $sdlanguage['Mar'],
	                                 $sdlanguage['Apr'],
	                                 $sdlanguage['May'],
	                                 $sdlanguage['Jun'],
	                                 $sdlanguage['Jul'],
	                                 $sdlanguage['Aug'],
	                                 $sdlanguage['Sep'],
	                                 $sdlanguage['Oct'],
	                                 $sdlanguage['Nov'],
	                                 $sdlanguage['Dec']));

	  $calendar->setDayNames(array($sdlanguage['Sun'], // v1.3.0: Sunday MUST be first here!
                                 $sdlanguage['Mon'],
                                 $sdlanguage['Tue'],
                                 $sdlanguage['Wed'],
                                 $sdlanguage['Thu'],
                                 $sdlanguage['Fri'],
                                 $sdlanguage['Sat']));

	  // append url to the number on the calendar
	  $calendar->enableDayLinks($page_url);

	  // enable front and back buttons for the months
    if(($startmonth == $p_monthid) && ($startyear == $p_yearid))
    {
	    $calendar->enableMonthNav($page_url);
    }
    if(!empty($settings['cal_week_numbers'])) //v371
    {
      $calendar->enableWeekNum();
    }
    //??? $calendar->enableDatePicker();

    $isSlugged = true; //SD370 preset
    $diff = intval(3600 * $mainsettings_timezoneoffset);

    // ################### Fetch articles for selected month ####################

    $sources_arr = !empty($settings['article_sources']) ? $settings['article_sources'] : '';
    $sources_arr = sd_ConvertStrToArray($sources_arr,',');
    if(!empty($sources_arr) && is_array($sources_arr))
    foreach($sources_arr as $sourceid)
    {
      if($getpage = $DB->query_first('SELECT categoryid FROM {pagesort}'.
                                     " WHERE pluginid = '%d' LIMIT 1",
                                     $sourceid))
      {
        $articles_settings = GetPluginSettings($sourceid);

        //v2.0.0: if slugged, then stay on current page
        $target_page = $isSlugged ? $categoryid : (int)$getpage['categoryid'];
        $page_link = RewriteLink('index.php?categoryid='.$target_page);

        //v1.3.1: obey publish start/end dates and articles options
        $where = ' AND ((datestart = 0) OR (datestart < ' . TIME_NOW . '))';
        if(empty($articles_settings['ignore_publish_end_date'])) //v3.5.2 (SD360)
        {
          $where .= ' AND ((dateend = 0) OR (dateend > ' . TIME_NOW . '))';
        }
        if($DB->column_exists(PRGM_TABLE_PREFIX.'p2_news','access_view'))
        {
          $where .= " AND ((IFNULL(access_view,'')='') OR (access_view like '%|".$userinfo['usergroupid']."|%'))";
        }

	      $getnewsdates = $DB->query('SELECT articleid, title, categoryid, seo_title,
          IF(IFNULL(datestart,0)=0,IF(IFNULL(dateupdated,0)=0,datecreated, dateupdated),datestart) AS `real_article_date`
		      FROM {p'.$sourceid.'_news}
		      WHERE (settings & 2)
		      AND MONTH(FROM_UNIXTIME(IF(IFNULL(datestart,0)=0,IF(IFNULL(dateupdated,0)=0,datecreated,dateupdated),datestart))) = '.$calendar->actmonth.'
          AND  YEAR(FROM_UNIXTIME(IF(IFNULL(datestart,0)=0,IF(IFNULL(dateupdated,0)=0,datecreated,dateupdated),datestart))) = '.$calendar->actyear.
          $where);

	      while($article = $DB->fetch_array($getnewsdates,null,MYSQL_ASSOC))
	      {
          if(empty($article['real_article_date']) || empty($article['categoryid']))
          {
            continue;
          }
		      $articleday   = date('j', $article['real_article_date']);
		      $articleyear  = date('Y', $article['real_article_date']);
		      $articlemonth = date('n', $article['real_article_date']);

          // Calendar links in Year/Month/Day slug format
          if($isSlugged)
          {
            if($mainsettings_modrewrite)
            {
              $link = preg_replace('#'.$quoted_url_ext.'$#','/', $page_link).
                      sprintf('%04d',$articleyear).'/'.
                      sprintf('%02d',$articlemonth).'/'.
                      sprintf('%02d',$articleday);
            }
            else
            {
              $link = RewriteLink('index.php?categoryid='.$target_page.
                                  '&p'.$pid.'_year='.sprintf('%04d',$articleyear).
                                  '&p'.$pid.'_month='.sprintf('%02d',$articlemonth).
                                  '&p'.$pid.'_day='.sprintf('%02d',$articleday));
            }
          }

		      $calendar->setEvent($articleyear, $articlemonth, $articleday,
                              false, ($isSlugged ? $link : false));

		      if( ($articleday   == $calendar->actday) &&
              ($articlemonth == $calendar->actmonth) &&
              ($articleyear  == $calendar->actyear))
		      {
			      $newsItems[] = array($sourceid,
                                 $article['title'],
                                 $article['categoryid'],
                                 $article['articleid'],
                                 (!empty($article['seo_title'])?$article['seo_title']:false));
		      }
	      } //while
      } //articles page
    } //foreach

	  // #################### Fetch Event Manager entries ####################

    $sources_arr = !empty($settings['event_sources']) ? $settings['event_sources'] : '';
    $sources_arr = sd_ConvertStrToArray($sources_arr,',');
    if(!empty($sources_arr) && is_array($sources_arr))
    {
      foreach($sources_arr as $sourceid)
      {
        if(isset($plugin_id_base_arr[$sourceid]) &&
           ($plugin_id_base_arr[$sourceid] == 'Event Manager'))
        {
          if($getpage = $DB->query_first('SELECT categoryid FROM {pagesort}'.
                                         " WHERE pluginid = '%d' LIMIT 1",
                                         $sourceid))
          {
            $target_page = $isSlugged ? $categoryid : (int)$getpage['categoryid'];
            $page_link = RewriteLink('index.php?categoryid='.$target_page);
		        $getdates = $DB->query('SELECT (date + '.$diff.') date, eventid, title'.
                                   ' FROM {p'.$sourceid.'_events}'.
                                   ' WHERE MONTH(FROM_UNIXTIME(date + '.$diff.')) = '.(int)$calendar->actmonth.
								                   ' AND YEAR(FROM_UNIXTIME(date + '.$diff.')) = '.(int)$calendar->actyear);
		        while($evententry = $DB->fetch_array($getdates,null,MYSQL_ASSOC))
		        {
			        $eventyear  = date('Y', $evententry['date']);
			        $eventmonth = date('m', $evententry['date']);
              $eventday   = date('d', $evententry['date']);

              // Calendar links in Year/Month/Day slug format
              if($isSlugged)
              {
                if($mainsettings_modrewrite)
                {
                  $link = preg_replace('#'.$quoted_url_ext.'$#','/', $page_link).
                          sprintf('%04d',$eventyear).'/'.
                          sprintf('%02d',$eventmonth).'/'.
                          sprintf('%02d',$eventday);
                }
                else
                {
                  $link = RewriteLink('index.php?categoryid='.$target_page.
                                      '&year='.sprintf('%04d',$eventyear).
                                      '&month='.sprintf('%02d',$eventmonth).
                                      '&day='.sprintf('%02d',$eventday));
                }
              }

			        $calendar->setEvent($eventyear, $eventmonth, $eventday,
                                  false, ($isSlugged ? $link : false));

			        if( ($eventday   == $calendar->actday) &&
                  ($eventmonth == $calendar->actmonth) &&
                  ($eventyear  == $calendar->actyear) )
			        {
				        $events[] = array($sourceid, $target_page,
                                  $evententry['eventid'], $evententry['title']);
			        }
            }
          }
        }
	    } //foreach
    } //events sources

    // ################### DISPLAY MONTH CALENDAR ###################
    if(!$output_started)
    {
      $output_started = true;
	    echo '
    <div id="p'.$pid.'_month">
    ';
    }

    echo $calendar->showMonth(false); #$calendar->showYear()

    unset($calendar);

    // Switch month and potentially year
    $startmonth++;
    if($startmonth>12)
    {
      $startmonth=1;
      $startyear++;
    }

  } //for loop for months display

  if($output_started)
  {
    echo '
    </div>
    <br />';
  }

  // ################### DISPLAY ACTUAL ITEMS FOR DAY ###################

  $maxchar = (!empty($settings['maxchar']) && is_numeric($settings['maxchar'])) ?
             (int)$settings['maxchar'] : 50;

	// If either a specific day is picked ($p_dayid) OR there is data for the day:
	if($p_dayid || !empty($newsItems) || !empty($events))
	{

    // ################# DISPLAY ARTICLES ###################
    if(!empty($newsItems))
    {
		  echo '
      <table align="center" border="0" class="News" cellpadding="0" cellspacing="0">
      <tr>
        <td align="center" class="NewsHead">
          <p style="text-align: center; font-weight:bold">' .
            #DisplayDate(gmmktime(0, 0, 0, $calendar->actmonth, $calendar->actday, $calendar->actyear)).
            DisplayDate(gmmktime(0, 0, 0, $p_monthid, $p_dayid, $p_yearid)).
            ' ' . $language['news'] . '</p>
        </td>
      </tr>';

		  if(!empty($newsItems))
		  {
			  foreach($newsItems as $news)
			  {
				  echo '
        <tr>
          <td class="NewsDSP">
            <a title="'.htmlspecialchars($news[1],ENT_COMPAT).'" href="';

          // v1.3.0: fix URLs to be SEO-compatible with SD3
          if($mainsettings_modrewrite && ($news[1] !== false))
          {
            $articlelink = RewriteLink('index.php?categoryid='.$news[2]).
                           ($news[0]==2?'':'?pluginid='.$news[0]);
            $articlelink = str_replace($mainsettings['url_extension'],
                             '/'.$news[4].$mainsettings['url_extension'],
                             $articlelink);
          }
          else
          {
            $articlelink = RewriteLink('index.php?categoryid='.$news[2].
                                       '&p'.$news[0].'_articleid='.$news[3].
                                       ($news[0]==2?'':'&pluginid='.$news[0]));
          }
          // v1.3.1: check if we need "..."?
          echo $articlelink . '">' .
            sd_substr($news[1], 0, $maxchar).
            (strlen($news[1]) >= $maxchar ? '...' : '') . '</a>
          </td>
        </tr>';
			  }
		  }
		  else
		  {
			  echo '
        <tr><td class="NewsDSP"><center>' . $language['no_news_found'] . '</center></td></tr>';
		  }

		  echo '
      </table>
		  <br />';

    } //end of articles display


    // ################### DISPLAY EVENTS ###################
		if(!empty($events))
		{
      $dispformat = str_replace('H:i:s','',$mainsettings_dateformat);
      $dispformat = str_replace('H:i','',$dispformat);
      $dispformat = str_replace('h:i:s','',$dispformat);
      $dispformat = str_replace('h:i','',$dispformat);
      $dispformat = str_replace('G:i:s','',$dispformat);
      $dispformat = str_replace('G:i','',$dispformat);
      $dispformat = str_replace('a','',$dispformat);
			echo '
      <table align="center" border="0" class="News" cellpadding="0" cellspacing="0">
      <tr>
        <td align="center" class="NewsHead"><b>'.
        #DisplayDate(mktime(0, 0, 0, $calendar->actmonth, $calendar->actday, $calendar->actyear),$dispformat,false).
        DisplayDate(mktime(0, 0, 0, $p_monthid, $p_dayid, $p_yearid),$dispformat,false).
        ' '.$language['events'] . '</center>
        </td>
      </tr>';

			if(count($events))
			{
				foreach($events as $event)
				{
					echo '
      <tr><td class="NewsDSP"><a href="'.
          RewriteLink('index.php?categoryid='.$event[1].
                      '&p'.$event[0].'_action=displayeventdetails&p'.
                      $event[0].'_eventid='.$event[2].
                      '&p'.$pid.'_month=' . $p_monthid /*$calendar->actmonth*/ .
                      '&p'.$pid.'_day='   . $p_dayid /*$calendar->actday*/ .
                      '&p'.$pid.'_year='  . $p_yearid /*$calendar->actyear*/).
          '">'.$event[3] . (strlen($event[3]) >= $maxchar ? '...' : '') . '</a>
      </td></tr>';
				}
			}
			else
			{
				echo '
        <tr><td class="NewsDSP"><center>' . $language['no_events_found'] . '</center></td></tr>';
			}

			echo '
      </table>
      ';
		}
	}

	echo '<br />';

} // end of function
} //DO NOT REMOVE!

DisplayCalendar2();
