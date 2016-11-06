<?php
if(!defined('IN_PRGM')) return false;

if(!class_exists('EventManagerClass'))
{
class EventManagerClass
{
  private $cat_link     = '';
  private $tmpl         = false;
  private $allow_submit = false;
  private $all_fields   = array('title','description','day','month','year','hour','minute',
                                'venue','street','city','state','country','allowcomments',
                                'activated','image','thumbnail');

  public  $event          = array();
  public  $pluginid       = 0;
  public  $plugin_folder  = '';
  public  $language       = array();
  public  $settings       = array();
  public  $IsAdmin        = false;
  public  $IsSiteAdmin    = false;
  public  $HasAdminRights = false;
  public  $allow_bbcode   = false;
  public  $image_folder   = '';
  public  $pref           = '';
  public  $table          = '';
  public  $TB_PREFIX      = 'tb_';

  function EventManagerClass($plugin_folder)
  {
    global $DB, $bbcode, $categoryid, $mainsettings, $userinfo;

    $this->pluginid       = GetPluginIDbyFolder($plugin_folder);
    $this->plugin_folder  = $plugin_folder;
    $this->image_folder   = 'plugins/'.$plugin_folder.'/images/';
    $this->language       = GetLanguage($this->pluginid);
    $this->settings       = GetPluginSettings($this->pluginid);
    $this->pref           = 'p'.$this->pluginid.'_';

    $tbl_ok = false;
    $tbl = PRGM_TABLE_PREFIX.$this->pref.'events';
    if($DB->table_exists($tbl))
    {
      $tbl_ok = true;
    }
    else
    {
      $tbl = PRGM_TABLE_PREFIX.'p18_events';
      if($DB->table_exists($tbl))
      {
        $tbl_ok = true;
      }
    }
    if($tbl_ok)
      $this->table = $tbl;
    else
      $this->table = '';

    $this->IsAdmin        = (!empty($userinfo['pluginadminids']) &&
                             @in_array($this->pluginid, $userinfo['pluginadminids'])) &&
                            (!empty($userinfo['admin_pages']) &&
                             in_array('plugins', $userinfo['admin_pages']));
    $this->IsSiteAdmin    = !empty($userinfo['adminaccess']) &&
                            !empty($userinfo['loggedin']) &&
                            !empty($userinfo['userid']);
    $this->HasAdminRights = $this->IsSiteAdmin || $this->IsAdmin;

    $this->allow_submit   = $this->HasAdminRights ||
                            (!empty($userinfo['pluginsubmitids']) &&
                             @in_array($this->pluginid, $userinfo['pluginsubmitids']));
    $this->cat_link       = 'index.php?categoryid=' . $categoryid;
    $this->allow_bbcode   = isset($bbcode) && ($bbcode instanceof BBCode) &&
                            !empty($mainsettings['allow_bbcode']);

  } //EventManagerClass

  // ########################### GET TIMEZONE SETTINGS ########################

  static function GetTZSettings()
  {
    global $DB, $mainsettings;

    $settings = array();
    $settings['timezoneoffset']  = empty($mainsettings['timezoneoffset'])  ? 0 : $mainsettings['timezoneoffset'];
    $settings['daylightsavings'] = empty($mainsettings['daylightsavings']) ? 0 : 1;

    return $settings;

  } //GetTZSettings

  // ############################# FLIP ROW COLOR  ############################

  static function FlipRowColor($currcolor, $rowcolor2, $rowcolor3)
  {
    return ($currcolor==$rowcolor2) ? $rowcolor3 : $rowcolor2;
  }

  // ############################### FORMAT DATE ##############################

  static function FormatDate($day, $month, $year, $hour, $minute)
  {
    $tzsettings = self::GetTZSettings();
    $date = @gmmktime($hour, $minute, 0, $month, $day, $year)
            #+(3600 * ($tzsettings['timezoneoffset']))
            ;
    return $date;
  } //FormatDate

  // ########################### FETCH EVENT BUFFER ###########################

  public function FetchEventBuffer()
  {
    $title        = GetVar($this->pref.'title', $this->language['untitled'], 'string', true, false);
    $description  = GetVar($this->pref.'description', '', 'string', true, false);
    $day          = GetVar($this->pref.'day', @gmdate('d', time()), 'natural_number', true, false);
    $day          = Is_Valid_Number($day, 1, 1, 31);
    $month        = GetVar($this->pref.'month', @gmdate('m', time()), 'natural_number', true, false);
    $month        = Is_Valid_Number($month, 1, 1, 12);
    $year         = GetVar($this->pref.'year', @gmdate('Y', time()), 'natural_number', true, false);
    $year         = Is_Valid_Number($year, @gmdate('Y', time()), 1900, 2100);
    $hour         = GetVar($this->pref.'hour',  @gmdate('H', time()), 'natural_number', true, false);
    $hour         = Is_Valid_Number($hour, 0, 0, 23);
    $minute       = GetVar($this->pref.'minute', '00', 'natural_number', true, false);
    $minute       = Is_Valid_Number($minute, 0, 0, 59);
    $venue        = GetVar($this->pref.'venue', '', 'string', true, false);
    $street       = GetVar($this->pref.'street','', 'string', true, false);
    $city         = GetVar($this->pref.'city',  '', 'string', true, false);
    $state        = GetVar($this->pref.'state', '', 'string', true, false);
    $country      = GetVar($this->pref.'country', '', 'string', true, false);
    $activated    = (GetVar($this->pref.'activated', 0, 'bool', true, false)?1:0);
    $allowcomments= (GetVar($this->pref.'allowcomments', 0, 'bool', true, false)?1:0);
    $image        = defined('IN_ADMIN') && isset($_FILES[$this->pref.'image'])?$_FILES[$this->pref.'image']:false;
    $thumbnail    = defined('IN_ADMIN') && isset($_FILES[$this->pref.'thumbnail'])?$_FILES[$this->pref.'thumbnail']:false;

    $this->event = compact($this->all_fields);

  } //FetchEventBuffer


  // ############################# DISPLAY EVENTS #############################

  private function InitTemplate()
  {
    global $categoryid, $mainsettings;

    // Instantiate smarty object
    $this->tmpl = SD_Smarty::getNew();
    $this->tmpl->assign('is_admin',     $this->HasAdminRights);
    $this->tmpl->assign('categoryid',   $categoryid);
    $this->tmpl->assign('pluginid',     $this->pluginid);
    $this->tmpl->assign('phrases',      $this->language);
    $this->tmpl->assign('settings',     $this->settings);
    $this->tmpl->assign('seo_enabled',  !empty($mainsettings['modrewrite']));
    $this->tmpl->assign('allow_submit', $this->allow_submit);
    $this->tmpl->assign('is_ajax_request', Is_Ajax_Request());
    $this->tmpl->assign('dateformat',   $mainsettings['dateformat']); //SD351

  } //InitTemplate

  // ############################# DISPLAY EVENTS #############################

  public function CheckSortType($getVar, $varname, $sortdefault=false)
  {
    if(empty($getVar))
      $sorttype = $varname;
    else
      $sorttype = GetVar($varname, '', 'string', false, true);

    if(empty($sorttype))
    {
      if(empty($sortdefault))
        $sorttype = $this->settings['default_events_sorting'];
      else
        $sorttype = $sortdefault;
    }
    if(empty($sorttype) ||
       !in_array($sorttype,array('datea','datez','locationa','locationz',
                                 'titlea','titlez','venuea','venuez')))
    {
      $sorttype = 'datez';
    }
    switch($sorttype)
    {
      case 'datea':
        $sortcol = 2; $order = 'date ASC'; break;

      case 'datez':
        $sortcol = 2; $order = 'date DESC'; break;

      case 'locationa':
        $sortcol = 3; $order = 'city ASC'; break;

      case 'locationz':
        $sortcol = 3; $order = 'city DESC'; break;

      case 'titlea':
        $sortcol = 1; $order = 'title ASC'; break;

      case 'titlez':
        $sortcol = 1; $order = 'title DESC'; break;

      case 'venuea':
        $sortcol = 4; $order = 'venue ASC'; break;

      case 'venuez':
        $sortcol = 4; $order = 'venue DESC'; break;

      default:
        $sortcol = 2; $sorttype = 'datez'; $order = 'date DESC';
    }
    return array($sorttype,$sortcol,$order);
  }

  // ############################# DISPLAY EVENTS #############################

  public function DisplayEvents($upcoming=false,$target_page=0)
  {
    global $DB, $categoryid, $mainsettings, $plugin_names, $sdlanguage, $userinfo;

    require_once(ROOT_PATH.'includes/class_sd_media.php');

    // for upcoming events display use different settings/page
    if($upcoming = !empty($upcoming))
    {
      if(!empty($target_page))
      {
        $target_page = Is_Valid_Number($target_page,$categoryid,1,999999);
        $this->cat_link = 'index.php?categoryid='.$target_page;
      }
      $page = 1;
      $pagesize = Is_Valid_Number($this->settings['upcoming_events_per_page'],5,1,999); // max events per page
      list($sorttype,$sortcol,$order) = $this->CheckSortType(false, $this->settings['default_upcoming_sorting']);
    }
    else
    {
      $pagesize = Is_Valid_Number($this->settings['events_per_page'],10,1,999);
      $page = Is_Valid_Number(GetVar($this->pref.'page', 1, 'whole_number',false,true),0,1,99999999);
      list($sorttype,$sortcol,$order) = $this->CheckSortType(true, $this->pref.'sorttype');
    }
    $sorttype_uri = '&'.$this->pref.'sorttype=';

    $link = $this->cat_link.$sorttype_uri;

    $uri_extras = $sorttype_uri.$sorttype.($page<1?'':'&'.$this->pref.'page='.$page);

    $asc = (substr($order, -3) == 'ASC');
    $arrow = $asc ? '&uarr;' : '&darr;';

    $diff = 0;#(3600 * $mainsettings['timezoneoffset']);
    $where = '';
    if(empty($this->settings['show_old_events']))
    {
      $where = ' AND e.date >= '.(int)(gmdate(TIME_NOW)+$diff);
    }

    if($total_rows = $DB->query_first('SELECT COUNT(*) rcount'.
                                      ' FROM '.$this->table.' e'.
                                      ' WHERE IFNULL(e.activated,0) = 1'.
                                      $where))
    {
      $total_rows = (int)$total_rows['rcount'];
    }

    //SD 2012-11-16: fix paging (ceil instead of floor)
    if($page * $pagesize > $total_rows)
    {
      $page = ceil($total_rows / $pagesize);
    }
    $page = ($page < 1) ? 1 : $page; //2013-02-08
    $getevents = $DB->query('SELECT * FROM '.$this->table.' e'.
                            ' WHERE IFNULL(e.activated,0) = 1'.
                            $where.
                            ' ORDER BY e.'.$order.
                            ' LIMIT '.(($page-1)*$pagesize).', '.$pagesize);

    $rows = $DB->get_num_rows($getevents);

    // Cache Pagination:
    $pagination_html = '';
    if(!$upcoming)
    {
      ob_start();
      $p = new pagination;
      $p->parameterName($this->pref.'page');
      $p->items($total_rows);
      $p->limit($pagesize);
      $p->currentPage($page);
      $p->adjacents(2);
      $p->target(RewriteLink($link.$sorttype));
      $p->show();
      $pagination_html = ob_get_clean();
      unset($p);
    }

    //v2.1.0: use template for output
    $this->InitTemplate();
    $this->tmpl->assign('plugin_name', $plugin_names[$this->pluginid]);
    $this->tmpl->assign('show_pagination', false);
    $this->tmpl->assign('pagination', '');
    $this->tmpl->assign('language', $this->language);
    $this->tmpl->assign('settings', $this->settings);
    $this->tmpl->assign('prefix', $this->pref);
    $this->tmpl->assign('sortcol', $sortcol);
    $this->tmpl->assign('sorttype', $sorttype);
    $this->tmpl->assign('arrow', $arrow);
    $this->tmpl->assign('page', $page);
    $this->tmpl->assign('pagesize', $pagesize);
    $this->tmpl->assign('events_count', $total_rows);
    $this->tmpl->assign('events_page_link',         RewriteLink($this->cat_link));
    $this->tmpl->assign('col_header_title_link',    RewriteLink($link.($sortcol!=1?'titlea':($asc?'titlez':'titlea'))));
    $this->tmpl->assign('col_header_date_link',     RewriteLink($link.($sortcol!=2?'datez':($asc?'datez':'datea'))));
    $this->tmpl->assign('col_header_location_link', RewriteLink($link.($sortcol!=3?'locationa':($asc?'locationz':'locationa'))));
    $this->tmpl->assign('col_header_venue_link',    RewriteLink($link.($sortcol!=4?'venuea':($asc?'venuez':'venuea'))));

    /* a different display method for dates could be:
    setlocale(LC_TIME, "de_DE"); // set locale
    $dispformat = '%V. %B %Y';
    strtr(strftime($dispformat,$event['date']), $sdlanguage)
    */
    $w = !isset($this->settings['max_thumbnail_width'])?0:Is_Valid_Number($this->settings['max_thumbnail_width'],0,10,999);
    $h = !isset($this->settings['max_thumbnail_height'])?0:Is_Valid_Number($this->settings['max_thumbnail_height'],0,10,999);

    // Remove timestamps
    $dispformat = str_replace(array('H:i:s','H:i','h:i:s','h:i','G:i:s','G:i','a',':'),
                              array(''),$mainsettings['dateformat']);
    $this->tmpl->assign('date_displayformat', $dispformat);

    $eventslist = array();
    $rotate = false;
    for($i = 0; $i < $rows AND $i < $pagesize; $i++)
    {
      if($event = $DB->fetch_array($getevents,null,MYSQL_ASSOC))
      {
        if(empty($this->settings['row_striping']))
          $color = 2;
        else
          $color = ($rotate ? 3 : 2);
        $event['color'] = $color;
        $event['details_url'] = RewriteLink($this->cat_link.'&'.$this->pref.'eventid='.$event['eventid'].
                                            ($upcoming?'':$uri_extras));
        $event['date_display'] = #SD371: use gmdate instead of DisplayDate
                                 @gmdate($dispformat, $event['date']).' '.
                                 @gmdate((empty($this->settings['24_hours_display'])?'g:ia':'G:i'), $event['date']);
        $rotate = !$rotate;

        $event['image_html'] = '';
        if(!empty($event['image']))
        {
          $src = ROOT_PATH.$this->image_folder.$event['image'];
          $event['image'] = $src;
          $event['image_html'] = SD_Image_Helper::GetThumbnailTag($src,'','','',false,false);
        }

        $event['thumbnail_html'] = '';
        if(!empty($event['thumbnail']))
        {
          $src = ROOT_PATH.$this->image_folder.$event['thumbnail'];
          $event['thumbnail'] = $src;
          $event['thumbnail_html'] = SD_Image_Helper::GetThumbnailTag($src,'','','',false,false, $w, $h);
        }

        $eventslist[] = $event;
      }
    }
    if($rows) $DB->free_result($getevents);
    $this->tmpl->assign('events', $eventslist);

    $this->tmpl->assign('event_submit_link', '');
    if(!$upcoming && $this->allow_submit)
    {
      $this->tmpl->assign('event_submit_link',
        RewriteLink($this->cat_link.'&'.$this->pref.'action=submitevent'.$uri_extras));
    }
    if(($total_rows > $pagesize) && !empty($pagination_html) &&
       ($pagination_html != '<div class="pagination"></div>'))
    {
      $this->tmpl->assign('show_pagination', true);
      $this->tmpl->assign('pagination', $pagination_html);
    }


    // BIND AND DISPLAY TEMPLATE NOW
    // Check if custom version exists, otherwise use default template:
    $tmpl = $upcoming ? 'upcoming_events.tpl' : 'events_list.tpl';
    if(!SD_Smarty::display($this->pluginid, $tmpl, $this->tmpl))
    {
      if($userinfo['adminaccess'])
      {
        $err = SD_Smarty::getLastError();
        if(!empty($err)) echo 'ERROR:<br />'.$err;
      }
      return false;
    }

    return true;

  } //DisplayEvents


  // ############################# EVENT DETAILS  ###############################

  function DisplayEventDetails()
  {
    if(!$eventid = Is_Valid_Number(GetVar($this->pref.'eventid', 0, 'whole_number'),0,1,99999999))
    {
      return false;
    }

    global $DB, $bbcode, $mainsettings, $plugin_names, $sdlanguage, $sdurl, $userinfo;

    $DB->result_type = MYSQL_ASSOC;
    if(!$event = $DB->query_first('SELECT * FROM '.$this->table.' WHERE eventid = %d',$eventid))
    {
      return false;
    }

    // Remove timestamps
    $dispformat = str_replace(array('H:i:s','H:i','h:i:s','h:i','G:i:s','G:i','a',':'),
                              array(''),$mainsettings['dateformat']);

    require_once(ROOT_PATH.'includes/class_sd_media.php');
    $event['image_html'] = '';
    if(!empty($event['image']))
    {
      $src = $sdurl.$this->image_folder.$event['image'];
      $event['image'] = $src;
      $event['image_html'] = SD_Image_Helper::GetThumbnailTag($src,'event_image','','',false,false,1000,1000);
    }

    $event['thumbnail_html'] = '';
    if(!empty($event['thumbnail']))
    {
      $src = $sdurl.$this->image_folder.$event['thumbnail'];
      $event['thumbnail'] = $src;
      $event['thumbnail_html'] = SD_Image_Helper::GetThumbnailTag($src,'event_thumb','','',false,false,1000,1000);
    }

    $currcolor = 2;
    if(!empty($event['date']))
    {
      $event['date_display'] = #SD371: use gmdate instead of DisplayDate
                               @gmdate($dispformat, $event['date'])
                               #.' '.@gmdate((empty($this->settings['24_hours_display'])?'g:ia':'G:i'), $event['date'])
                               ;
      $event['date_color'] = $currcolor;

      if(!empty($this->settings['row_striping']))
      {
        $currcolor = self::FlipRowColor($currcolor, 2, 3);
      }
      $event['time_color'] = $currcolor;
      /*
      $event['time'] = strtr(@gmdate((empty($this->settings['24_hours_display'])?'g:ia':'G:i'), $event['date']),
                             $sdlanguage);
      */
      $event['time'] = @gmdate((empty($this->settings['24_hours_display'])?'g:ia':'G:i'), $event['date']);

      if(!empty($this->settings['row_striping']))
      $currcolor = self::FlipRowColor($currcolor, 2, 3);
    }

    if(!empty($event['venue']))
    {
      $event['venue_color'] = $currcolor;
      if(!empty($this->settings['row_striping']))
      $currcolor = self::FlipRowColor($currcolor, 2, 3);
    }

    if(!empty($event['street']))
    {
      $event['street_color'] = $currcolor;
      if(!empty($this->settings['row_striping']))
      $currcolor = self::FlipRowColor($currcolor, 2, 3);
    }

    if(!empty($event['city']))
    {
      $event['city_color'] = $currcolor;
      if(!empty($this->settings['row_striping']))
      $currcolor = self::FlipRowColor($currcolor, 2, 3);
    }

    if(!empty($event['state']))
    {
      $event['state_color'] = $currcolor;
      if(!empty($this->settings['row_striping']))
      $currcolor = self::FlipRowColor($currcolor, 2, 3);
    }

    if(!empty($event['country']))
    {
      $event['country_color'] = $currcolor;
      if(!empty($this->settings['row_striping']))
      $currcolor = self::FlipRowColor($currcolor, 2, 3);
    }

    if(!empty($event['description']))
    {
      $event['description_color'] = $currcolor;

      //SD341: BBCode support
      if($this->allow_bbcode)
      {
        $bbcode->RemoveRule('code');
        $bbcode->AddRule('code',
            array('mode' => BBCODE_MODE_CALLBACK,
              'class' => 'code',
              'method' => 'sd_BBCode_DoCode',
              'allow_in' => array('listitem', 'block', 'columns'),
              'content' => BBCODE_VERBATIM
            )
        );
        $bbcode->url_targetable = true;
        $bbcode->SetURLTarget('_blank');
        $bbcode->SetURLPattern('<a rel="nofollow" href="{$url/h}">{$text/h}</a>');
        $event['description'] = str_replace('&amp;','&',$event['description']);
        $event['description'] = $bbcode->Parse($event['description']);
      }
      else
      {
        $event['description'] = preg_replace("#\R#m", '<br />', $event['description']);
      }

      if(!empty($this->settings['row_striping']))
      $currcolor = self::FlipRowColor($currcolor, 2, 3);
    }

    $comments_html = false;
    if(!empty($event['allowcomments']))
    {
      ob_start();
      Comments($this->pluginid, $event['eventid'], $this->cat_link.'&'.
               $this->pref.'action=displayeventdetails&'.$this->pref.'eventid='.$event['eventid']);
      $comments_html = ob_get_clean();
    }

    //v2.1.0: use template for output
    $this->InitTemplate();
    $this->tmpl->assign('plugin_name',    $plugin_names[$this->pluginid]);
    $this->tmpl->assign('prefix',         $this->pref);
    $this->tmpl->assign('display_format', $dispformat);
    $this->tmpl->assign('comments_html',  $comments_html);
    $this->tmpl->assign('return_link',    RewriteLink($this->cat_link));
    $this->tmpl->assign('event',          $event);

    if(!SD_Smarty::display($this->pluginid, 'single_event_display_1.tpl', $this->tmpl))
    {
      if($userinfo['adminaccess'])
      {
        $err = SD_Smarty::getLastError();
        if(!empty($err)) echo $err;
      }
    }

    return true;

  } //DisplayEventDetails

  // ############################## INSERT EVENT ################################

  private function InsertEvent($errors=null)
  {
    global $DB, $sdlanguage, $userinfo;

    if(!$this->allow_submit)
    {
      DisplayMessage($sdlanguage['no_post_access'], true);
      $this->DisplayEvents();
      return false;
    }

    // Security check against spam/bot submissions
    if(!CheckFormToken())
    {
      RedirectFrontPage(RewriteLink($this->cat_link), $sdlanguage['error_invalid_token'].'<br />', 2, true);
      return false;
    }

    if(!$this->HasAdminRights || !empty($userinfo['require_vvc']))
    {
      if(!CaptchaIsValid($this->pref))
      {
        $this->DisplaySubmitEventForm(array($sdlanguage['captcha_not_valid']));
        return false;
      }
    }

    $this->FetchEventBuffer();

    $date = self::FormatDate($this->event['day'], $this->event['month'], $this->event['year'],
                             $this->event['hour'], $this->event['minute']);
    $approved = ($this->HasAdminRights || !empty($this->settings['auto_Approve_events'])) ? 1 : 0;
    $DB->query('INSERT INTO '.$this->table." (activated,allowcomments,title,description,date,venue,street,city,state,country)
                VALUES (%d, 0, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                $approved, $this->event['title'], $this->event['description'], $DB->escape_string($date),
                $this->event['venue'], $this->event['street'], $this->event['city'],
                $this->event['state'], $this->event['country']);

    $email = $this->settings['event_notification'];
    if(strlen($email)>5) // format "1@3.56"
    {
      @SendEmail($email,$this->language['email_subject'],
                 $this->language['email_body'].EMAIL_CRLF.$this->language['event_name'].': '.$this->event['title'],
                 $this->language['email_fromname']);
    }

    echo $approved ? $this->language['success_approved'] : $this->language['success'];
    echo '<br />
    <a href="' . RewriteLink($this->cat_link) . '">' . $this->language['return'] . '</a>
    <br />';

  } //InsertEvent


  // ############################## SUBMIT EVENT ################################

  private function DisplaySubmitEventForm($errors=null)
  {
    global $DB, $mainsettings, $inputsize, $sdlanguage, $userinfo;

    if(!$this->allow_submit)
    {
      return false;
    }

    if(!is_null($errors) && is_array($errors))
    {
      DisplayMessage($errors,true);
      $this->FetchEventBuffer();
    }

    $inputsize = Is_Valid_Number($inputsize,30,10,100);
    $page      = Is_Valid_Number(GetVar($this->pref.'page', 1, 'whole_number',false,true),0,1,99999999);
    $pagesize  = Is_Valid_Number($this->settings['events_per_page'],10,1,999);
    $sorttype = GetVar($this->pref.'sorttype', '', 'string', false, true);
    if(empty($sorttype))
    {
      $sorttype = $this->settings['default_events_sorting'];
    }
    if(empty($sorttype) ||
       !in_array($sorttype,array('datea','datez','locationa','locationz',
                                 'titlea','titlez','venuea','venuez')))
    {
      $sorttype = 'datez';
    }
    $this->FetchEventBuffer();

    echo '
    <form id="'.$this->pref.'form" method="post" action="' . RewriteLink($this->cat_link).'">
    '.PrintSecureToken().'
    <input type="hidden" name="'.$this->pref.'action" value="insertevent" />
    <input type="hidden" name="'.$this->pref.'page" value="'.$page.'" />
    <input type="hidden" name="'.$this->pref.'sorttype" value="'.$sorttype.'" />
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
      <td class="tdem1" colspan="2" class="rowcol1"><strong>' . $this->language['submit_event'] . '</strong></td>
    </tr>
    <tr>
      <td class="tdem1">' . $this->language['event_name2'] . ' *</td>
      <td class="tdem2"><input type="text" name="'.$this->pref.'title" maxlength="128" value="' . $this->event['title'] . '" size="'.$inputsize.'" /></td>
    </tr>
    <tr>
      <td class="tdem1" valign="top">' . $this->language['date2'] . '</td>
      <td class="tdem2" valign="top">
      <select name="'.$this->pref.'month">';

    $p_day    = $this->event['day'];
    $p_hour   = $this->event['hour'];
    $p_minute = $this->event['minute'];
    $p_month  = $this->event['month'];

    $months = range(1,12);
    $month_arr = array($sdlanguage['January'], $sdlanguage['February'], $sdlanguage['March'],
                       $sdlanguage['April'],   $sdlanguage['May'],      $sdlanguage['June'],
                       $sdlanguage['July'],    $sdlanguage['August'],   $sdlanguage['September'],
                       $sdlanguage['October'], $sdlanguage['November'], $sdlanguage['December']);
    foreach($months as $value)
    {
      echo '
        <option value="'.$value.'"' . ($p_month == $value ? ' selected="selected"' : '') . '>'.$month_arr[$value-1].'</option>';
    }
    echo '
      </select>
      <select name="'.$this->pref.'day">';

    $days = range(1,31);
    foreach($days as $value)
    {
      $value = sprintf("%02d", $value);
      echo '
        <option value="'.$value.'"' . ($p_day == $value ? ' selected="selected"' : '') . '>'.$value.'</option>';
    }
    echo '
      </select>
      <input type="text" name="'.$this->pref.'year" size="4" maxlength="4" value="' . $this->event['year'] . '" /> (ex: '.@gmdate('Y').')
      </td>
    </tr>
    <tr>
      <td class="tdem1" valign="top">' . $this->language['time'] . '</td>
      <td class="tdem2"  valign="top">
      <select name="'.$this->pref.'hour">
        ';

    if(empty($this->settings['24_hours_display']))
    {
      echo '
      <option value="00"' . ($p_hour == '00' ? ' selected="selected"' : '') . '>12 am</option>
      <option value="01"' . ($p_hour == '01' ? ' selected="selected"' : '') . '>1 am</option>
      <option value="02"' . ($p_hour == '02' ? ' selected="selected"' : '') . '>2 am</option>
      <option value="03"' . ($p_hour == '03' ? ' selected="selected"' : '') . '>3 am</option>
      <option value="04"' . ($p_hour == '04' ? ' selected="selected"' : '') . '>4 am</option>
      <option value="05"' . ($p_hour == '05' ? ' selected="selected"' : '') . '>5 am</option>
      <option value="06"' . ($p_hour == '06' ? ' selected="selected"' : '') . '>6 am</option>
      <option value="07"' . ($p_hour == '07' ? ' selected="selected"' : '') . '>7 am</option>
      <option value="08"' . ($p_hour == '08' ? ' selected="selected"' : '') . '>8 am</option>
      <option value="09"' . ($p_hour == '09' ? ' selected="selected"' : '') . '>9 am</option>
      <option value="10"' . ($p_hour == '10' ? ' selected="selected"' : '') . '>10 am</option>
      <option value="11"' . ($p_hour == '11' ? ' selected="selected"' : '') . '>11 am</option>
      <option value="12"' . ($p_hour == '12' ? ' selected="selected"' : '') . '>12 pm</option>
      <option value="13"' . ($p_hour == '13' ? ' selected="selected"' : '') . '>1 pm</option>
      <option value="14"' . ($p_hour == '14' ? ' selected="selected"' : '') . '>2 pm</option>
      <option value="15"' . ($p_hour == '15' ? ' selected="selected"' : '') . '>3 pm</option>
      <option value="16"' . ($p_hour == '16' ? ' selected="selected"' : '') . '>4 pm</option>
      <option value="17"' . ($p_hour == '17' ? ' selected="selected"' : '') . '>5 pm</option>
      <option value="18"' . ($p_hour == '18' ? ' selected="selected"' : '') . '>6 pm</option>
      <option value="19"' . ($p_hour == '19' ? ' selected="selected"' : '') . '>7 pm</option>
      <option value="20"' . ($p_hour == '20' ? ' selected="selected"' : '') . '>8 pm</option>
      <option value="21"' . ($p_hour == '21' ? ' selected="selected"' : '') . '>9 pm</option>
      <option value="22"' . ($p_hour == '22' ? ' selected="selected"' : '') . '>10 pm</option>
      <option value="23"' . ($p_hour == '23' ? ' selected="selected"' : '') . '>11 pm</option>
      ';
    }
    else
    {
      $hours = range(0,23);
      foreach($hours as $value)
      {
        $value = sprintf("%02d", $value);
        echo '
        <option value="'.$value.'"'.($p_hour==$value?' selected="selected"':'').'>'.$value.'</option>';
      }
    }
    echo '
      </select>
       :
      <select name="'.$this->pref.'minute">
        <option value="00"' . ($p_minute == '00' ? ' selected="selected"' : '') . '>00</option>
        <option value="15"' . ($p_minute == '05' ? ' selected="selected"' : '') . '>05</option>
        <option value="15"' . ($p_minute == '10' ? ' selected="selected"' : '') . '>10</option>
        <option value="15"' . ($p_minute == '15' ? ' selected="selected"' : '') . '>15</option>
        <option value="15"' . ($p_minute == '20' ? ' selected="selected"' : '') . '>20</option>
        <option value="15"' . ($p_minute == '25' ? ' selected="selected"' : '') . '>25</option>
        <option value="30"' . ($p_minute == '30' ? ' selected="selected"' : '') . '>30</option>
        <option value="30"' . ($p_minute == '35' ? ' selected="selected"' : '') . '>35</option>
        <option value="30"' . ($p_minute == '40' ? ' selected="selected"' : '') . '>40</option>
        <option value="45"' . ($p_minute == '45' ? ' selected="selected"' : '') . '>45</option>
        <option value="45"' . ($p_minute == '50' ? ' selected="selected"' : '') . '>50</option>
        <option value="45"' . ($p_minute == '55' ? ' selected="selected"' : '') . '>55</option>
      </select>
      </td>
    </tr>
    <tr>
      <td class="tdem1" valign="top">' . $this->language['venue2'] . '</td>
      <td class="tdem2" valign="top"><input type="text" name="'.$this->pref.'venue" maxlength="128" value="' . $this->event['venue'] . '" size="'.$inputsize.'" /></td>
    </tr>
    <tr>
      <td class="tdem1" valign="top">' . $this->language['street'] . '</td>
      <td class="tdem2" valign="top"><input type="text" name="'.$this->pref.'street" maxlength="128" value="' . $this->event['street'] . '" size="'.$inputsize.'" /></td>
    </tr>
    <tr>
      <td class="tdem1" valign="top">' . $this->language['city'] . '</td>
      <td class="tdem2" valign="top"><input type="text" name="'.$this->pref.'city" maxlength="128" value="' . $this->event['city'] . '" size="'.$inputsize.'" /></td>
    </tr>
    ';

    if(empty($this->settings['display_us_states']))
    {
      echo '
    <tr>
      <td class="tdem1" valign="top">' . $this->language['state'] . '</td>
      <td class="tdem2" valign="top"><input type="text" name="'.$this->pref.'state" maxlength="128" value="' . $this->event['state'] . '" size="'.$inputsize.'" /></td>
    </tr>
    ';
    }
    else
    {
      //2013-05-11: store state codes in uppercase now
      $p_state = strtoupper($this->event['state']);
      echo '
    <tr>
      <td class="tdem1" valign="top">' . $this->language['state'] . '</td>
      <td class="tdem2" valign="top">
        <select name="'.$this->pref.'state">
          <option value=""' . (empty($p_state)?' selected="selected"':'').'>'.$this->language['none'].'</option>
          <option value="AL"' . ($p_state == 'AL' ? ' selected="selected"' : '') . '>Alabama</option>
          <option value="AK"' . ($p_state == 'AK' ? ' selected="selected"' : '') . '>Alaska</option>
          <option value="AZ"' . ($p_state == 'AZ' ? ' selected="selected"' : '') . '>Arizona</option>
          <option value="AR"' . ($p_state == 'AR' ? ' selected="selected"' : '') . '>Arkansas</option>
          <option value="CA"' . ($p_state == 'CA' ? ' selected="selected"' : '') . '>California</option>
          <option value="CO"' . ($p_state == 'CO' ? ' selected="selected"' : '') . '>Colorado</option>
          <option value="CT"' . ($p_state == 'CT' ? ' selected="selected"' : '') . '>Connecticut</option>
          <option value="DE"' . ($p_state == 'DE' ? ' selected="selected"' : '') . '>Delaware</option>
          <option value="DC"' . ($p_state == 'DC' ? ' selected="selected"' : '') . '>District of Columbia</option>
          <option value="FL"' . ($p_state == 'FL' ? ' selected="selected"' : '') . '>Florida</option>
          <option value="GA"' . ($p_state == 'GA' ? ' selected="selected"' : '') . '>Georgia</option>
          <option value="HI"' . ($p_state == 'HI' ? ' selected="selected"' : '') . '>Hawaii</option>
          <option value="ID"' . ($p_state == 'ID' ? ' selected="selected"' : '') . '>Idaho</option>
          <option value="IL"' . ($p_state == 'IL' ? ' selected="selected"' : '') . '>Illinois</option>
          <option value="IN"' . ($p_state == 'IN' ? ' selected="selected"' : '') . '>Indiana</option>
          <option value="IA"' . ($p_state == 'IA' ? ' selected="selected"' : '') . '>Iowa</option>
          <option value="KS"' . ($p_state == 'KS' ? ' selected="selected"' : '') . '>Kansas</option>
          <option value="KY"' . ($p_state == 'KY' ? ' selected="selected"' : '') . '>Kentucky</option>
          <option value="LA"' . ($p_state == 'LA' ? ' selected="selected"' : '') . '>Louisiana</option>
          <option value="ME"' . ($p_state == 'ME' ? ' selected="selected"' : '') . '>Maine</option>
          <option value="MD"' . ($p_state == 'MD' ? ' selected="selected"' : '') . '>Maryland</option>
          <option value="MA"' . ($p_state == 'MA' ? ' selected="selected"' : '') . '>Massachusetts</option>
          <option value="MI"' . ($p_state == 'MI' ? ' selected="selected"' : '') . '>Michigan</option>
          <option value="MN"' . ($p_state == 'MN' ? ' selected="selected"' : '') . '>Minnesota</option>
          <option value="MS"' . ($p_state == 'MS' ? ' selected="selected"' : '') . '>Mississippi</option>
          <option value="MO"' . ($p_state == 'MO' ? ' selected="selected"' : '') . '>Missouri</option>
          <option value="MT"' . ($p_state == 'MT' ? ' selected="selected"' : '') . '>Montana</option>
          <option value="NE"' . ($p_state == 'NE' ? ' selected="selected"' : '') . '>Nebraska</option>
          <option value="NV"' . ($p_state == 'NV' ? ' selected="selected"' : '') . '>Nevada</option>
          <option value="NH"' . ($p_state == 'NH' ? ' selected="selected"' : '') . '>New Hampshire</option>
          <option value="NJ"' . ($p_state == 'NJ' ? ' selected="selected"' : '') . '>New Jersey</option>
          <option value="NM"' . ($p_state == 'NM' ? ' selected="selected"' : '') . '>New Mexico</option>
          <option value="NY"' . ($p_state == 'NY' ? ' selected="selected"' : '') . '>New York</option>
          <option value="NC"' . ($p_state == 'NC' ? ' selected="selected"' : '') . '>North Carolina</option>
          <option value="ND"' . ($p_state == 'ND' ? ' selected="selected"' : '') . '>North Dakota</option>
          <option value="OH"' . ($p_state == 'OH' ? ' selected="selected"' : '') . '>Ohio</option>
          <option value="OK"' . ($p_state == 'OK' ? ' selected="selected"' : '') . '>Oklahoma</option>
          <option value="OR"' . ($p_state == 'OR' ? ' selected="selected"' : '') . '>Oregon</option>
          <option value="PA"' . ($p_state == 'PA' ? ' selected="selected"' : '') . '>Pennsylvania</option>
          <option value="RI"' . ($p_state == 'RI' ? ' selected="selected"' : '') . '>Rhode Island</option>
          <option value="SC"' . ($p_state == 'SC' ? ' selected="selected"' : '') . '>South Carolina</option>
          <option value="SD"' . ($p_state == 'SD' ? ' selected="selected"' : '') . '>South Dakota</option>
          <option value="TN"' . ($p_state == 'TN' ? ' selected="selected"' : '') . '>Tennessee</option>
          <option value="TX"' . ($p_state == 'TX' ? ' selected="selected"' : '') . '>Texas</option>
          <option value="UT"' . ($p_state == 'UT' ? ' selected="selected"' : '') . '>Utah</option>
          <option value="VT"' . ($p_state == 'VT' ? ' selected="selected"' : '') . '>Vermont</option>
          <option value="VA"' . ($p_state == 'VA' ? ' selected="selected"' : '') . '>Virginia</option>
          <option value="WA"' . ($p_state == 'WA' ? ' selected="selected"' : '') . '>Washington</option>
          <option value="WV"' . ($p_state == 'WV' ? ' selected="selected"' : '') . '>West Virginia</option>
          <option value="WI"' . ($p_state == 'WI' ? ' selected="selected"' : '') . '>Wisconsin</option>
          <option value="WY"' . ($p_state == 'WY' ? ' selected="selected"' : '') . '>Wyoming</option>
        </select>
      </td>
    </tr>';
    }
    echo '
    <tr>
      <td class="tdem1" valign="top">' . $this->language['country'] . '</td>
      <td class="tdem2" valign="top"><input type="text" name="'.$this->pref.'country" maxlength="64" value="' . $this->event['country']. '" size="'.$inputsize.'" /></td>
    </tr>
    <tr>
      <td class="tdem1" valign="top">' . $this->language['description'] . '</td>
      <td class="tdem2" valign="top"><textarea class="eventmgr_bbcode" name="'.
        $this->pref.'description" style="width: 100%" cols="' . $inputsize . '" rows="10">'.
        $this->event['description'] . '</textarea></td>
    </tr>
    </table>';

    if(!$this->HasAdminRights || !empty($userinfo['require_vvc']))
    {
      DisplayCaptcha(true,$this->pref);
    }

    echo '
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
      <td class="tdem1" valign="top">
        <input class="btn btn-primary" type="submit" value="' . htmlspecialchars(strip_tags($this->language['submit_event']), ENT_COMPAT) . '" />
      </td>
    </tr>
    <tr>
      <td class="tdem1"><a href="' . RewriteLink($this->cat_link) . '">&laquo; ' . $this->language['return'] . '</a></td>
    </tr>
    </table>
    </form>
    ';

    return true;

  } //DisplaySubmitEventForm


  // ############################# UPCOMING EVENTS ############################

  public function UpcomingEvents()
  {
    global $DB, $categoryid, $mainsettings, $pluginids, $plugin_names, $sdlanguage;

    if(empty($this->pluginid)) return;

    // default: use plugin on current page, if it exists there;
    // otherwise find first other page which has this plugin:
    $cat_id = $categoryid;
    if(!in_array($this->pluginid, $pluginids)) #$pluginids = current page layout
    {
      if($findem = $DB->query_first('SELECT categoryid FROM {pagesort}'.
                                    " WHERE pluginid = '%s'".
                                    ' ORDER BY categoryid LIMIT 1',
                                    (string)$this->pluginid))
        $cat_id = $findem['categoryid'];
      else
        return; #Not found, bail out!
    }

    $this->DisplayEvents(true, $cat_id);

  } //UpcomingEvents


  // ############################ DISPLAY DEFAULT #############################

  function DisplayDefault()
  {
    if(empty($this->table)) return false;

    $action = GetVar($this->pref.'action', '', 'string');
    if($eventid = Is_Valid_Number(GetVar($this->pref.'eventid', 0, 'whole_number',false,true),0,1,99999999))
    {
      $action = 'displayeventdetails';
    }

    switch($action)
    {
      case 'submitevent':
        if(!$this->DisplaySubmitEventForm()) // display the submit event form
        {
          $this->DisplayEvents();
        }
      break;

      case 'insertevent':
        $this->InsertEvent();               // insert a new event into the database
      break;

      case 'displayeventdetails':
        if(!$this->DisplayEventDetails())   // display the details of a user selected event
        {
          $this->DisplayEvents();
        }
      break;

      default:
        $this->DisplayEvents();             // display all events
    }

  } //DisplayDefault

} // end of class

} // DO NOT REMOVE
