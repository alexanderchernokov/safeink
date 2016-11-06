<?php
if(!class_exists('SD_Reports'))
{
class SD_Reports
{
  public static $init_done         = false;
  public static $reporting_plugins = array();
  public static $current_report    = array();
  public static $current_report_id = 0;

  // The following can be used by 3rd party plugins for use of the
  // "SetReportedItemStatus" function: specify the table, key column
  // and status column, which is to be set to 0 or 1 in order to
  // disable or enable the reported item:
  public static $statusupdate_table = ''; // MUST include table prefix!
  public static $statusupdate_key_column = '';
  public static $statusupdate_status_column = '';

  private static $reasonscache     = array();
  private static $reason_cache_id  = 'SD_REASONS_CACHE';

  protected function __construct()
  {
  }

  // ##########################################################################

  public static function Init()
  {
    global $DB, $SDCache;

    if(self::$init_done) return true;
    self::$init_done = true;

    self::$reasonscache = array();
    self::$current_report = array();
    self::$current_report_id = 0;

    $CacheEnabled = !empty($SDCache) && $SDCache->IsActive();
    if($CacheEnabled)
    {
      if((self::$reasonscache = $SDCache->read_var(self::$reason_cache_id, 'reasons_arr')) !== false)
      {
        return true;
      }
    }

    if($getrows = $DB->query('SELECT rp.pluginid, rp.is_active, rs.*'.
       ' FROM '.PRGM_TABLE_PREFIX.'report_reasons rs'.
       ' LEFT JOIN '.PRGM_TABLE_PREFIX.'report_reasons_plugins rp ON rp.reasonid = rs.reasonid'.
       ' ORDER BY rp.pluginid, rs.reasonid'))
    {
      while($row = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
      {
        $pid = empty($row['pluginid']) ? 0 : (int)$row['pluginid'];
        self::$reasonscache[$pid][$row['reasonid']] = $row;
      }
      if($CacheEnabled)
      {
        $SDCache->write_var(self::$reason_cache_id, 'reasons_arr', self::$reasonscache);
      }
    }
    else
    {
      return false;
    }

    return true;

  } //Init


  // ##########################################################################
  // CLEAR REASONS CACHE
  // ##########################################################################

  public static function ClearReasonsCache()
  {
    global $SDCache;
    if(isset($SDCache) && is_object($SDCache))
    {
      $SDCache->delete_cacheid(self::$reason_cache_id);
    }
  } //ClearReasonsCache


  // ###########################################################################
  // DELETE SINGLE REPORT REASON AND LINKED ENTRIES
  // ###########################################################################

  public static function DeleteReasonFully($reasonid)
  {
    global $DB;

    if(!isset($reasonid)) return false;
    $reasonid = Is_Valid_Number($reasonid,0,100,99999999);
    if(!$reasonid) return false;

    $DB->query('DELETE FROM {report_reasons_plugins} WHERE reasonid = '.(int)$reasonid);
    $DB->query('DELETE FROM {report_reasons} WHERE reasonid = '.(int)$reasonid);

    self::ClearReasonsCache();

    return true;

  } //DeleteReasonFully


  // ##########################################################################
  // DELETE LINKS BETWEEN A REPORT REASON AND PLUGINS
  // ##########################################################################

  public static function AddReportReason($title, $description)
  {
    global $DB, $userinfo;

    if(empty($title) || empty($description))
    {
      return false;
    }

    $DB->query('INSERT INTO {report_reasons}'.
               ' (reasonid,title,description,datecreated,dateupdated,created_by)'.
               " VALUES (null,'%s','%s',%d,%d,'%s')",
               $DB->escape_string($title),
               $DB->escape_string($description),
               TIME_NOW, TIME_NOW,
               $DB->escape_string($userinfo['username']));
    $reasonid = $DB->insert_id();

    if(!empty($clearCache))
    self::ClearReasonsCache();

    return $reasonid;

  } //AddReportReason


  // ##########################################################################
  // DELETE LINKS BETWEEN A REPORT REASON AND PLUGINS
  // ##########################################################################

  public static function AddReasonLink($reasonid, $pluginid, $isActive=1, $clearCache=true)
  {
    global $DB, $plugin_names;

    if(empty($reasonid) || empty($pluginid))
    {
      return false;
    }

    $pluginid = Is_Valid_Number($pluginid,0,1,99999999);
    $reasonid = Is_Valid_Number($reasonid,0,1,99999999);
    if(!$reasonid || !$pluginid) return false;

    // Check if plugin allows reporting first
    $DB->result_type = MYSQL_ASSOC;
    $plugin_row = $DB->query_first('SELECT pluginid, reporting FROM {plugins} WHERE pluginid = %d', $pluginid);
    if(empty($plugin_row['pluginid']) ||
       empty($plugin_row['reporting']))
    {
      return false;
    }

    $DB->query('INSERT INTO {report_reasons_plugins} (reasonid,pluginid,is_active)'.
               ' VALUES (%d,%d,%d)',
               $reasonid, $pluginid, (empty($isActive)?0:1));

    if(!empty($clearCache))
    self::ClearReasonsCache();

    return true;

  } //AddReasonLink


  // ##########################################################################
  // DELETE LINKS BETWEEN A REPORT REASON AND PLUGINS
  // ##########################################################################

  public static function DeleteReasonLinks($reasonid,$clearCache=true)
  {
    global $DB;

    if(!isset($reasonid)) return false;
    $reasonid = Is_Valid_Number($reasonid,0,1,99999999);
    if(!$reasonid) return false;

    $DB->query('DELETE FROM {report_reasons_plugins} WHERE reasonid = '.(int)$reasonid);

    if(!empty($clearCache))
    self::ClearReasonsCache();

    return true;

  } //DeleteReasonLinks


  // ###########################################################################
  // DELETE SINGLE REPORT REASON AND LINKED ENTRIES
  // ###########################################################################

  public static function DeletePluginReasons($pluginid)
  {
    global $DB, $plugin_names;

    if(!isset($pluginid) || ($pluginid < 1) || ($pluginid > 999999)) return false;
    if(!isset($plugin_names[$pluginid])) return false;

    $DB->query('DELETE FROM {report_reasons_plugins} WHERE pluginid = '.(int)$pluginid);

    self::ClearReasonsCache();

    return true;

  } //DeletePluginReasons


  // ##########################################################################
  // UPDATE DETAILS FOR A SINGLE REPORT REASON
  // ##########################################################################

  public static function UpdateReasonDetails($reasonid,$description,$title='',$clearCache=true)
  {
    global $DB;

    if(!isset($reasonid) || ($reasonid < 1) || ($reasonid > 999999)) return false;

    // SD-delivered reasons with ID < 100: cannot change title
    $extra = '';
    if(($reasonid > 99) && !empty($title))
    {
      $extra .= ", title = '".$DB->escape_string($title)."'";
    }
    $DB->query("UPDATE {report_reasons} SET description = '%s', dateupdated = %d".
               $extra.
               " WHERE reasonid = %d",
               $DB->escape_string($description), TIME_NOW, $reasonid);

    if(!empty($clearCache))
    self::ClearReasonsCache();

    return true;

  } //UpdateReasonDetails


  // ##########################################################################
  // GET CACHED REASON
  // ##########################################################################

  public static function GetCachedReason($pluginid=0, $rid=0, $activeOnly=false)
  {
    if(!isset($rid) || ($rid < 0)|| ($rid > 99999999)) return false;
    if(!self::$init_done) self::Init();
    if(isset(self::$reasonscache[$pluginid][$rid]))
    {
      if(!empty($activeOnly) && empty(self::$reasonscache[$pluginid][$rid]['is_active']))
      {
        return false;
      }
      return self::$reasonscache[$pluginid][$rid];
    }

    return false;

  } //GetCachedReason


  // ##########################################################################
  // GET CACHED REASON
  // ##########################################################################

  public static function GetReasonsForPluginID($pluginid=0, $activeOnly=false)
  {
    if(!isset($pluginid) || ($pluginid < 0)|| ($pluginid > 99999999)) return false;
    if(!self::$init_done) self::Init();
    if(isset(self::$reasonscache[$pluginid]))
    {
      if(empty($activeOnly))
      {
        return self::$reasonscache[$pluginid];
      }
      $reasons = array();
      foreach(self::$reasonscache[$pluginid] as $key => $val)
      if(!empty($val['is_active']))
      {
        $reasons[$key] = $val;
      }
      return $reasons;
    }

    return false;

  } //GetCachedReason


  // ##########################################################################
  // IS AN ITEM ALREADY REPORTED WITH 2 KEYS?
  // ##########################################################################

  public static function GetReportedItem($pluginid=0, $objectid1=0, $objectid2=0)
  {
    self::$current_report = array();
    self::$current_report_id = 0;

    if(empty($pluginid) || ($pluginid < 0)|| ($pluginid > 99999999))
    {
      return false;
    }

    global $DB;
    $DB->result_type = MYSQL_ASSOC;
    $report = $DB->query_first('SELECT *'.
                ' FROM {users_reports} WHERE pluginid = %d'.
                ' AND objectid1 = %d AND objectid2 = %d',
                $pluginid, $objectid1, $objectid2);

    if(empty($report['reportid']))
    {
      return false;
    }

    self::$current_report = &$report;
    self::$current_report_id = (int)$report['reportid'];

    return self::$current_report;

  } //GetReportedItem


  // ##########################################################################


  public static function GetReportedItemByID($reportid)
  {
    self::$current_report = array();
    self::$current_report_id = 0;

    if(empty($reportid) || ($reportid < 0)|| ($reportid > 99999999))
    {
      return false;
    }

    global $DB;
    $DB->result_type = MYSQL_ASSOC;
    $report = $DB->query_first('SELECT *'.
                ' FROM {users_reports} WHERE reportid = %d',
                $reportid);

    if(empty($report['reportid']))
    {
      return false;
    }

    self::$current_report = &$report;
    self::$current_report_id = (int)$report['reportid'];

    return self::$current_report;

  } //GetReportedItemByID


  // ##########################################################################
  // SEND OUT EMAIL TO ALL CONFIGURED ACTIVE MODERATORS
  // ##########################################################################

  public static function SendReport($reportid, $pluginid, $defaultSubject='')
  {
    global $DB, $mainsettings, $mainsettings_websitetitle_original,
           $plugin_names, $sdurl, $userinfo, $usersystem;

    $message = trim($mainsettings['reporting_email_body']);
    if(!strlen(trim($message)))
    {
      return false;
    }

    $report = self::GetReportedItemByID($reportid);
    if($report === false) return false;

    $emails = array();
    $subject = trim($mainsettings['reporting_email_subject']);
    $subject = (empty($subject) ? (!empty($defaultSubject)?$defaultSubject:'Content reported by [username], [date]') : $subject);

    // First check if moderators are configured
    $DB->result_type = MYSQL_ASSOC;
    if($getmods = $DB->query('SELECT DISTINCT userid, email'.
      ' FROM {report_moderators}'.
      ' WHERE pluginid = '.$pluginid.
      ' AND usersystemid = '.$usersystem['usersystemid'].
      " AND IFNULL(email,'') <> ''".
      ' AND receiveemails = 1'))
      #' AND (subitemid IS NULL OR IFNULL(subitemid,-1) = '.$post_arr['forum_id'].')'.
    {
      while($mod = $DB->fetch_array($getmods,null,MYSQL_ASSOC))
      {
        if(IsValidEmail($mod['email']))
        {
          $emails[$mod['userid']] = $mod['email'];
        }
      }
    }

    // If there are none found, add log entry
    if(!count($emails))
    {
      WatchDog('Forum','Content reported (Plugin ID '.$pluginid.')', WATCHDOG_NOTICE);
      return true;
    }

    // Loop over emails and send them
    $item_content = '';
    $reason = self::GetCachedReason($report['pluginid'], $report['reasonid']);
    $page = RewriteLink('index.php?categoryid='.$report['categoryid']);
    $item_title = SD_Reports::TranslateObjectID($report['pluginid'], $report['objectid1'], $report['objectid2'], $item_content);
    $item_title = strip_alltags($item_title);
    $item_link = SD_Reports::DisplayReportLink($report['categoryid'], $report['pluginid'],
                                               $report['objectid1'], 0, true);
    $item_content = sd_wordwrap(strip_alltags(sd_unhtmlspecialchars($item_content)),70,'<br />');
    $plugin_name = $plugin_names[$report['pluginid']];
    $reported_date = DisplayDate(TIME_NOW);

    $search = array('[reported_user]',
                    '[reported_userid]',
                    '[reported_link]',
                    '[reported_title]',
                    '[report_content]',
                    '[report_reason_title]',
                    '[report_reason_description]',
                    '[reported_date]',
                    '[page]',
                    '[plugin_name]',
                    #'[email]',
                    '[ipaddress]',
                    '[username]',
                    '[siteurl]',
                    '[sitename]');
    $replace = array($report['reported_username'],
                     $report['reported_userid'],
                     $item_link,
                     $item_title,
                     $item_content,
                     (($reason===false)?'':$reason['title']),
                     (($reason===false)?'':$reason['description']),
                     $reported_date,
                     $page,
                     $plugin_name,
                     #$user['email'],
                     USERIP,
                     $userinfo['username'],
                     $sdurl,
                     $mainsettings_websitetitle_original);

    $sent = 0;
    foreach($emails as $mod_id => $mod_email)
    {
      $moderator = SDUserCache::CacheUser($mod_id,'',false,false,true);
      if(!empty($moderator['activated']) && !empty($moderator['email']) &&
         empty($moderator['usergroup_details']['banned']))
      {
        $s = str_replace($search, $replace, $subject);
        $m = str_replace($search, $replace, $message);
        $s = str_replace(array('[moderator]'),
                         array($moderator['username']), $s);
        $m = str_replace(array('[moderator]'),
                         array($moderator['username']), $m);
        if(SendEmail($mod_email, $s, $m, '', $mainsettings['reporting_email_sender'], null, null, true))
        {
          $sent++;
        }
      }
    }

    return $sent;

  } //SendReport


  // ##########################################################################
  // CLOSE AN EXISTING REPORT?
  // ##########################################################################

  public static function CloseReport($reportid, $setStatus=false, $newStatus=0)
  {
    if(empty($reportid) || ($reportid < 1)|| ($reportid > 99999999))
    {
      return false;
    }

    if(!empty($setStatus))
    {
      self::SetReportedItemStatus($reportid, $newStatus);
    }

    global $DB;
    $DB->query('UPDATE {users_reports} SET is_closed = 1'.
               ' WHERE reportid = %d AND is_closed = 0',
               $reportid);

  } //CloseReport


  // ##########################################################################
  // DELETE AN EXISTING REPORT?
  // ##########################################################################

  public static function DeleteReport($reportid, $setStatus=false, $newStatus=0)
  {
    if(empty($reportid) || ($reportid < 1) || ($reportid > 99999999))
    {
      return false;
    }

    if(!empty($setStatus))
    {
      self::SetReportedItemStatus($reportid, $newStatus);
    }

    global $DB;
    $DB->query('DELETE FROM {users_reports} WHERE reportid = %d',$reportid);

    return true;

  } //DeleteReport


  // ##########################################################################
  // CREATE A NEW USER REPORT
  // ##########################################################################

  public static function CreateReport($pluginid, $objectid1, $objectid2,
                                      $reasonid, $categoryid,
                                      $reported_userid, $reported_username,
                                      $user_msg='')
  {
    if(empty($reasonid) || empty($pluginid) || ($pluginid < 0)|| ($pluginid > 99999999))
    {
      return false;
    }

    global $DB, $userinfo, $usersystem;

    $tmp = $DB->ignore_error;
    $DB->ignore_error = true;
    $DB->query(
      'INSERT INTO {users_reports}'.
      ' (userid,username,pluginid,objectid1,objectid2,reasonid,user_msg,'.
       ' ipaddress,datereported,is_closed,reportcount,categoryid,'.
       ' reported_userid,reported_username,usersystemid)'.
      " VALUES(%d, '%s', %d, %d, %d, %d, '%s',".
      " '%s', %d, 0, 1, %d, %d, '%s', %d)",
      $userinfo['userid'], $DB->escape_string($userinfo['username']),
      $pluginid, $objectid1, $objectid2, $reasonid, $DB->escape_string($user_msg),
      IPADDRESS, TIME_NOW, $categoryid, $reported_userid,
      $DB->escape_string($reported_username),
      $usersystem['usersystemid']);
    $DB->ignore_error = $tmp;
    $idx = empty($DB->errno) ? $DB->insert_id() : 0;

    self::GetReportedItem($pluginid, $objectid1, $objectid2);

    return ($idx ? $idx : false);

  } //CreateReport


  // ##########################################################################
  // ADD TO AN EXISTING REPORT
  // ##########################################################################

  public static function AddToReport($reportid, $reasonid=0)
  {
    if(empty($reasonid) || empty($reportid) || ($reportid < 0) || ($reportid > 99999999))
    {
      return false;
    }

    global $DB, $userinfo;
    $DB->result_type = MYSQL_ASSOC;
    $DB->query('UPDATE {users_reports}'.
               " SET userid = %d, reasonid = %d, ipaddress = '%s',".
               ' datereported = %d, is_closed = 0, reportcount = (IFNULL(reportcount,0)+1)'.
               ' WHERE reportid = %d',
               $userinfo['userid'], $reasonid, IPADDRESS, TIME_NOW, $reportid);

  } //AddToReport


  // ##########################################################################
  // DISPLAY AN IMAGE LINK TO FRONTPAGE FOR REPORTED ITEM (PLUGIN)
  // ##########################################################################

  public static function DisplayReportLink($categoryid, $pluginid, $objectid1, $objectid2, $linkOnly=false)
  {
    global $DB, $mainsettings_modrewrite, $mainsettings_url_extension,
           $plugin_names, $sdurl;

    if(empty($pluginid) || empty($objectid1))
    {
      return '-';
    }
    if(empty($categoryid))
    {
      $DB->result_type = MYSQL_ASSOC;
      if($cat = $DB->query_first('SELECT categoryid'.
                                 ' FROM {pagesort}'.
                                 ' WHERE pluginid = %d'.
                                 ' ORDER BY categoryid LIMIT 1',
                                 $pluginid))
      {
        $categoryid = (int)$cat['categoryid'];
      }
      else
      {
        return '-';
      }
    }

    // Any other link just opens the original page
    $link = RewriteLink('index.php?categoryid='.$categoryid);
    if($pluginid == GetPluginID('Forum'))
    {
      require_once(ROOT_PATH.'plugins/forum/forum_config.php');
      $forumconf = new SDForumConfig();

      // Special URL for Forum post
      $DB->result_type = MYSQL_ASSOC;
      $forumconf->current_page = $link;
      $ftitle = $DB->query_first('SELECT t.title'.
                   ' FROM {p_forum_topics} t'.
                   ' INNER JOIN {p_forum_posts} p ON p.topic_id = t.topic_id'.
                   ' WHERE t.topic_id = %d'.
                   (!empty($objectid2)?' AND p.post_id = %d':''),
                   $objectid1, $objectid2);
      if(!empty($objectid1) && !empty($objectid2))
      {
        $link = $forumconf->RewritePostLink($objectid1,
                            (empty($ftitle['title'])?'*':$ftitle['title']),
                            $objectid2);
      }
      else
      if(!empty($objectid1))
      {
        $link = $forumconf->RewritePostLink($objectid1,
                            (empty($ftitle['title'])?'*':$ftitle['title']));
      }
    }
    else
    if( ($pluginid == 2) ||
        (isset($plugin_names['base-'.$pluginid]) &&
         ('Articles' == $plugin_names['base-'.$pluginid])) )
    {
      $DB->result_type = MYSQL_ASSOC;
      $article_arr = $DB->query_first('SELECT articleid, categoryid, seo_title, settings'.
                     ' FROM {p'.$pluginid.'_news}'.
                     ' WHERE articleid = %d',
                     $objectid1);

      $articlebitfield = array();
      $articlebitfield['linktomainpage'] = 1048576;
      $articlebitfield['displayaspopup'] = 262144;
      $link = GetArticleLink($article_arr['categoryid'], $pluginid,
                             $article_arr, $articlebitfield,
                             false, 'p2_news'); //SD351
    }
    else
    if(($pluginid == GetPluginID('Media Gallery')) ||
       (isset($plugin_names['base-'.$pluginid]) && ('Media Gallery' == $plugin_names['base-'.$pluginid])))
    {
      $link = RewriteLink('index.php?categoryid='.$categoryid.
                          '&p'.$pluginid.'_imageid='.$objectid1);

    }
    else
    if( ($pluginid == GetPluginID('Download Manager')) ||
        (isset($plugin_names['base-'.$pluginid]) &&
         ('Download Manager' == $plugin_names['base-'.$pluginid])) )
    {
      $link = RewriteLink('index.php?categoryid='.$categoryid.
                          '&p'.$pluginid.'_fileid='.$objectid1);

    }

    if(empty($linkOnly))
      return '<a href="'.$link.'" target="_blank"><i class="ace-icon fa fa-search"></i></a>';
    else
      return $link;

  } //DisplayReportLink


  // ##########################################################################
  // TRANSALTE OBJECT ID
  // ##########################################################################

  public static function TranslateObjectID($pluginid, $objectid1, $objectid2, &$content)
  {
    global $DB, $sdlanguage;

    $getComment = false;
    $content = '';
    if( empty($pluginid) || ($pluginid < 2) ||
        empty($objectid1) || ($objectid1 < 1))
    {
      return '';
    }
    $objectid2 = empty($objectid2)?0:(int)$objectid2;

    $DB->result_type = MYSQL_ASSOC;
    $plugin_install = $DB->query_first('SELECT * FROM {plugins}'.
                                       ' WHERE pluginid = %d'.
                                       ' AND IFNULL(reporting,0) = 1',
                                       $pluginid);
    if(empty($plugin_install['pluginid']))
    {
      return $sdlanguage['err_plugin_not_available'].' (ID: '.$pluginid.')';
    }

    //SD370: translate now in global function p_LC_TranslateObjectID
    $title = 'Unknown (ID ' . $objectid1 . ')';
    if($res = p_LC_TranslateObjectID($pluginid, $objectid1, $objectid2, 0, true))
    {
      if(strlen($res['title']))
      {
        $title = $res['title'];
        if(!empty($pageid) && strlen($res['link'])) // links source title to category?
        {
          $title = '<a href="'.$res['link'].'" target="_blank">'.$title.'</a>';
        }
      }
      if(isset($res['content']) && is_string($res['content']))
      {
        $content = trim($res['content']);
      }
    }

    return $title;

  } //TranslateObjectID


  // ##########################################################################
  // SET MODERATION/APPROVAL STATUS OF AN ITEM (E.G. A COMMENT OR PLUGIN ITEM)
  // ##########################################################################

  public static function SetReportedItemStatus($reportid, $approved=true)
  {
    $report = self::GetReportedItemByID($reportid);
    if($report === false) return false;
    $pluginid = (int)$report['pluginid'];

    global $DB, $sdlanguage;

    $DB->result_type = MYSQL_ASSOC;
    $plugin_install = $DB->query_first('SELECT * FROM {plugins}'.
                                       ' WHERE pluginid = %d'.
                                       ' AND IFNULL(reporting,0) = 1',
                                       $pluginid);
    if(empty($plugin_install['pluginid']) || empty($plugin_install['reporting']))
    {
      return $sdlanguage['err_plugin_not_available'].' (ID: '.$pluginid.')';
    }
    $objectid1 = $report['objectid1'];
    $objectid2 = $report['objectid2'];

    $DB->result_type = MYSQL_ASSOC;
    $DB->ignore_error = true;

    $base_plugin = !empty($plugin_install['base_plugin']);
    $status = empty($approved) ? 0 : 1;
    $update_comment = false;

    // Articles and clones support (SD 3.4+)
    if(($pluginid==2) || ($plugin_install['name']=='Articles') ||
       ($base_plugin && ($plugin_install['base_plugin'] == 'Articles')))
    {
      # Reporting individual articles not yet supported!
      $update_comment = !empty($objectid2);
    }
    else
    // Download Manager and clones support (SD 3.4+)
    if(($pluginid==13) || ($plugin_install['name']=='Download Manager') ||
       ($base_plugin && ($plugin_install['base_plugin'] == 'Download Manager')))
    {
      /*
      # Reporting individual files not yet supported!
      $DB->query('UPDATE {p'.$pluginid.'_files} SET activated = %d'.
                 ' WHERE fileid = %d',
                 $status, $objectid1);
      */
      $update_comment = !empty($objectid2);
    }
    else
    // Forum support (SD 3.4+)
    if(($plugin_install['name']=='Forum') ||
       ($base_plugin && ($plugin_install['base_plugin'] == 'Forum')))
    {
      $DB->query('UPDATE {p_forum_posts} SET moderated = %d'.
                 ' WHERE topic_id = %d'.
                 ' AND post_id = %d',
                 ($status?0:1), $objectid1, $objectid2);
    }
    else
    // Media/Image Gallery and clones support (SD 3.4+)
    if( ($pluginid==17) || ($plugin_install['name'] == 'Media Gallery') ||
        ($plugin_install['name'] == 'Image Gallery') ||
        ($base_plugin && ($plugin_install['base_plugin'] == 'Media Gallery')))
    {
      /*
      # Reporting individual media files not yet supported!
      $DB->query('UPDATE {p'.$pluginid.'_images} SET activated = %d'.
                 ' WHERE imageid = %d',
                 $status, $objectid1);
      */
      $update_comment = !empty($objectid2);
    }
    else
    {
      // ***** ANY 3RD PARTY PLUGIN ACCESS: *****
      if( !empty($objectid1) && empty($objectid2) &&
          !empty(self::$statusupdate_table) &&
          !empty(self::$statusupdate_key_column) &&
          !empty(self::$statusupdate_status_column) )
      {
        // If ONLY the first objectid is specified, then it is the plugin's
        // own item, so we use the extra properties for table/column/key here:
        if( $DB->table_exists(self::$statusupdate_table) &&
            $DB->column_exists(self::$statusupdate_table, self::$statusupdate_key_column) &&
            $DB->column_exists(self::$statusupdate_table, self::$statusupdate_status_column) )
        {
          $DB->query('UPDATE '.self::$statusupdate_table.
                     ' SET '.self::$statusupdate_status_column.' = %d'.
                     ' WHERE '.self::$statusupdate_key_column.' = %d',
                     $status, $objectid1);
        }
      }

      // Set comment flag if the 2nd ID was specified
      $update_comment = !empty($objectid2);
    }

    // Set the comment status
    if($update_comment && !empty($objectid1) && !empty($objectid2))
    {
      /*
      $DB->query('UPDATE {comments} SET approved = %d'.
                 ' WHERE objectid = %d AND commentid = %d',
                 $status, $objectid1, $objectid2);
      */
      require_once(SD_INCLUDE_PATH . 'class_comments.php');
      $Comments = new Comments();
      $Comments->plugin_id = $pluginid;
      $Comments->object_id = $objectid1;
      $Comments->SetApproved($objectid2, $status);
    }

    $DB->ignore_error = false;
    return true;

  } //SetReportedItemStatus


  // ##########################################################################
  // GET MODERATION/APPROVAL STATUS OF AN ITEM (E.G. A COMMENT OR PLUGIN ITEM)
  // ##########################################################################

  public static function GetReportedItemStatus($reportid, & $msg)
  {
    global $DB, $sdlanguage;

    $msg = '';
    $report = self::GetReportedItemByID($reportid);
    if($report === false)
    {
      $msg = $sdlanguage['err_invalid_report'];
      return false;
    }
    $pluginid = (int)$report['pluginid'];

    $DB->result_type = MYSQL_ASSOC;
    $plugin_install = $DB->query_first('SELECT * FROM {plugins}'.
                                       ' WHERE pluginid = %d'.
                                       ' AND IFNULL(reporting,0) = 1',
                                       $pluginid);
    if(empty($plugin_install['pluginid']) || empty($plugin_install['reporting']))
    {
      $msg = $sdlanguage['err_plugin_not_available'].' (ID: '.$pluginid.')';
      return false;
    }
    $objectid1 = $report['objectid1'];
    $objectid2 = $report['objectid2'];

    $DB->result_type = MYSQL_ASSOC;
    $DB->ignore_error = true;

    $base_plugin = !empty($plugin_install['base_plugin']);
    $get_comment = false;
    $result = false;

    // Articles and clones support (SD 3.4+)
    if(($pluginid==2) || ($plugin_install['name']=='Articles') ||
       ($base_plugin && ($plugin_install['base_plugin'] == 'Articles')))
    {
      # Reporting individual articles not yet supported!
      $get_comment = !empty($objectid2);
    }
    else
    // Download Manager and clones support (SD 3.4+)
    if(($pluginid==13) || ($plugin_install['name']=='Download Manager') ||
       ($base_plugin && ($plugin_install['base_plugin'] == 'Download Manager')))
    {
      # Reporting individual media/image entry
      if(!empty($objectid1) && empty($objectid2))
      {
        $DB->result_type = MYSQL_ASSOC;
        $result = $DB->query_first('SELECT activated FROM {p'.$pluginid.'_files}'.
                                   ' WHERE fileid = %d',
                                   $objectid1);
        $result = empty($result['activated']);
      }

      $get_comment = !empty($objectid2);
    }
    else
    // Forum support (SD 3.4+)
    if(($plugin_install['name']=='Forum') ||
       ($base_plugin && ($plugin_install['base_plugin'] == 'Forum')))
    {
      $DB->result_type = MYSQL_ASSOC;
      $result = $DB->query_first('SELECT moderated FROM {p_forum_posts}'.
                                 ' WHERE topic_id = %d'.
                                 ' AND post_id = %d',
                                 $objectid1, $objectid2);
      $result = empty($result['moderated']);
    }
    else
    // Media/Image Gallery and clones support (SD 3.4+)
    if( ($pluginid==17) || ($plugin_install['name'] == 'Media Gallery') ||
        ($plugin_install['name'] == 'Image Gallery') ||
        ($base_plugin && ($plugin_install['base_plugin'] == 'Media Gallery')) )
    {
      # Reporting individual media/image entry
      if(!empty($objectid1) && empty($objectid2))
      {
        $DB->result_type = MYSQL_ASSOC;
        $result = $DB->query_first('SELECT activated FROM {p'.$pluginid.'_images}'.
                                   ' WHERE imageid = %d',
                                   $objectid1);
        $result = empty($result['activated']);
      }

      $get_comment = !empty($objectid2);
    }
    else
    {
      // ***** ANY 3RD PARTY PLUGIN ACCESS: *****
      if( !empty($objectid1) && empty($objectid2) &&
          !empty(self::$statusupdate_table) &&
          !empty(self::$statusupdate_key_column) &&
          !empty(self::$statusupdate_status_column) )
      {
        // If ONLY the first objectid is specified, then it is the plugin's
        // own item, so we use the extra properties for table/column/key here:
        if( $DB->table_exists(self::$statusupdate_table) &&
            $DB->column_exists(self::$statusupdate_table, self::$statusupdate_key_column) &&
            $DB->column_exists(self::$statusupdate_table, self::$statusupdate_status_column) )
        {
          $DB->result_type = MYSQL_ASSOC;
          $result = $DB->query_first(
                    'SELECT '.self::$statusupdate_status_column.' AS TMPSTAT'.
                    ' FROM `'.self::$statusupdate_table.'`'.
                    ' WHERE '.self::$statusupdate_key_column.' = %d',
                    $status, $objectid1);
          $result = empty($result['TMPSTAT']);
        }
      }

      // Set comment flag if the 2nd ID was specified
      $get_comment = !empty($objectid2);
    }

    // Set the comment status
    if($get_comment && !empty($objectid1) && !empty($objectid2))
    {
      $DB->result_type = MYSQL_ASSOC;
      $result = $DB->query_first('SELECT approved FROM {comments}'.
                                 ' WHERE objectid = %d AND commentid = %d',
                                 $objectid1, $objectid2);
      $result = !empty($result['approved']);
    }

    $DB->ignore_error = false;
    return $result;

  } //GetReportedItemStatus

} //END OF CLASS
} //DO NOT REMOVE
