<?php

if(!defined('IN_PRGM')) exit();

// ###########################################################################
// COMMENTS CLASS
// ###########################################################################

class Comments
{
  public $comments_count_md_arr = array();
  public $CacheObj            = null; //SD313: SD's reference to cache object
  public $default_plugin_id   = 0;
  public $default_object_id   = 0;
  public $default_url         = '';
  public $isArticle           = false;
  public $object_id           = 0;
  public $plugin_id           = 0;
  public $comment_id          = 0; //SD370: set after insert
  public $allow_comments      = true;
  public $hasPostAccess       = false;
  public $url                 = '';
  public $max_comments        = 0; //SD342
  public $IsAdmin             = false; //SD343
  public $comment_likes       = false; //SD343
  public $like_groups         = array(); //SD343
  public $comment_reports     = false; //SD351
  public $comment_reports_msg = false; //SD351
  public $report_groups       = array(); //SD351
  private $_internal_call     = false; //SD343

  public function Comments()
  {
  }

  public function setPerm($plugin_id, $object_id=null)
  {
    global $mainsettings, $userinfo;

    $this->plugin_id = (!empty($plugin_id) && is_numeric($plugin_id) && ($plugin_id > 1)) ? (int)$plugin_id : 0;
    if(isset($object_id))
    {
      $this->object_id = (!empty($object_id) && is_numeric($object_id) && ($object_id > 0)) ? (int)$object_id : 0;
    }
    $loggedin = !empty($userinfo['loggedin']);
    //SD350: take into account pluginmoderateids
    $this->IsAdmin = $loggedin &&
                     ( !empty($userinfo['adminaccess']) ||
                       (!empty($userinfo['pluginadminids']) && @in_array($this->plugin_id, $userinfo['pluginadminids'])) ||
                       (!empty($userinfo['pluginmoderateids']) && @in_array($this->plugin_id, $userinfo['pluginmoderateids'])) ||
                       (!empty($userinfo['admin_pages']) && @in_array('comments', $userinfo['admin_pages'])) );
    $this->hasPostAccess = !empty($this->plugin_id) && !empty($this->object_id) && !SD_IS_BOT &&
                           ($this->IsAdmin || !empty($userinfo['commentaccess']) ||
                            (!empty($userinfo['plugincommentids']) && @in_array($this->plugin_id, $userinfo['plugincommentids'])));
    $this->like_groups   = (!empty($mainsettings['comments_enable_likes']) ?
                            sd_ConvertStrToArray($mainsettings['comments_enable_likes'],',') :
                            array());
    $this->comment_likes = $this->hasPostAccess && !empty($userinfo['usergroupids']) &&
                           @array_intersect($userinfo['usergroupids'], $this->like_groups);
    //SD360: reporting of comments
    $this->report_groups = ( !empty($mainsettings['comments_enable_reports']) ?
                             sd_ConvertStrToArray($mainsettings['comments_enable_reports'],',') :
                             array() );
    $this->comment_reports = $this->IsAdmin ||
                             ( $loggedin &&
                               ( !empty($mainsettings['reporting_enabled']) &&
                                 (!empty($userinfo['pluginmoderateids']) && @in_array($this->plugin_id, $userinfo['pluginmoderateids'])) ||
                                 ($this->hasPostAccess && !empty($userinfo['usergroupids']) &&
                                  @array_intersect($userinfo['usergroupids'], $this->report_groups))) );
    $this->comment_reports_msg = $userinfo['report_message'];

  } //setPerm

  // ##########################################################################
  // APPROVE SPECIFIC COMMENT
  // ##########################################################################

  public function SetApproved($comment_id, $approved=1) //SD322
  {
    global $DB;

    if(Is_Valid_Number($comment_id, 0, 1,99999999))
    {
      $DB->result_type = MYSQL_ASSOC;
      $comment = $DB->query_first('SELECT commentid, objectid, pluginid FROM {comments}'.
                                  ' WHERE commentid = %d',
                                  $comment_id);
      if(!empty($comment['commentid']))
      {
        $DB->query("UPDATE {comments} SET approved = %d WHERE commentid = %d",
                   (empty($approved) ? 0 : 1), $comment_id);
        $this->UpdateCommentsCount($comment['pluginid'], $comment['objectid']);
        return true;
      }
    }
    return false;

  } //SetApproved


  // ##########################################################################
  // DELETE SPECIFIC COMMENT
  // ##########################################################################

  public function DeleteComment($comment_id) //SD322
  {
    global $DB;

    if(!Is_Valid_Number($comment_id, 0, 1,99999999)) return false;

    if($comment = $DB->query_first('SELECT pluginid, objectid FROM {comments}'.
                                   ' WHERE commentid = %d LIMIT 1', $comment_id))
    {
      //SD343: remove likes/dislikes for current comment
      require_once(SD_INCLUDE_PATH.'class_sd_likes.php');
      SD_Likes::RemoveLikesForObject($comment['pluginid'], $comment['objectid'], false);

      //SD351: remove reports for comment
      require_once(SD_INCLUDE_PATH.'class_sd_reports.php');
      $report = SD_Reports::GetReportedItem($comment['pluginid'], $comment['objectid'], $comment_id);
      if(!empty($report['reportid']))
      {
        SD_Reports::DeleteReport($report['reportid'], false);
      }
      unset($report);

      $DB->query("DELETE FROM {comments} WHERE commentid = %d", $comment_id);
      $this->UpdateCommentsCount($comment['pluginid'], $comment['objectid']);
      return true;
    }
    return false;

  } //DeleteComment


  // ##########################################################################
  // RESET MEMBERS
  // ##########################################################################

  public function ResetMembers()
  {
    $this->plugin_id = $this->default_plugin_id;
    $this->object_id = $this->default_object_id;
    $this->url = $this->default_url;

    return true;

  } //ResetMembers


  // ##########################################################################
  // INIT COMMENTS COUNT
  // ##########################################################################

  public function InitCommentsCount($forceRecount=false)
  {
    global $DB;

    // Load "Comments Count"-array from cache if exists
    if(empty($forceRecount) && isset($this->CacheObj) && ($this->CacheObj instanceof SDCache))
    {
      if(($this->comments_count_md_arr = $this->CacheObj->read_var('comments_count', 'comments_count_md_arr')) !== false)
      {
        return true;
      }
    }
    if($get_comments_count = $DB->query('SELECT * FROM {comments_count}'))
    {
      while($comments_count_arr = $DB->fetch_array($get_comments_count,null,MYSQL_ASSOC))
      {
        $this->comments_count_md_arr[$comments_count_arr['plugin_id']][$comments_count_arr['object_id']] = $comments_count_arr['count'];
      }
    }

    // Save "Comments Count"-array back to cache
    if(isset($this->CacheObj) && ($this->CacheObj instanceof SDCache))
    {
      $this->CacheObj->write_var('comments_count', 'comments_count_md_arr', $this->comments_count_md_arr);
    }
    return true;

  } //InitCommentsCount


  // ##########################################################################
  // GET COMMENTS COUNT
  // ##########################################################################

  public function GetCommentsCount($resetMembers = true)
  {
    $comments_count = 0;

    if(isset($this->comments_count_md_arr[$this->plugin_id][$this->object_id]))
    {
      $comments_count = (int)$this->comments_count_md_arr[$this->plugin_id][$this->object_id];
    }
    else
    {
      $comments_count = 0;
    }

    if(!empty($resetMembers)) $this->ResetMembers();

    return $comments_count;

  } //GetCommentsCount


  // ##########################################################################
  // UPDATE COMMENTS COUNT
  // ##########################################################################

  public function UpdateCommentsCount($pluginid=null,$objectid=null)
  {
    global $DB;

    //SD322: added parameters to make it usable for other tasks, too
    if(!empty($pluginid) && !empty($objectid))
    {
      $plugin_id = (int)$pluginid;
      $object_id = (int)$objectid;
    }
    else
    {
      $plugin_id = $this->plugin_id;
      $object_id = $this->object_id;
    }

    $count = $DB->query_first('SELECT COUNT(*) com_count FROM {comments}'.
                              ' WHERE pluginid = %d AND objectid = %d'.
                              ' AND approved = 1',
                              $plugin_id, $object_id);
    $count = empty($count['com_count']) ? 0 : (int)$count['com_count'];

    $DB->query('DELETE FROM {comments_count}'.
               ' WHERE plugin_id = %d AND object_id = %d',
               $plugin_id, $object_id);
    $DB->query('INSERT INTO {comments_count} (plugin_id, object_id, count)'.
               ' VALUES (%d, %d, %d)',
               $plugin_id, $object_id, $count);

    $this->comments_count_md_arr[$plugin_id][$object_id] = $count;

    // Save "Comments Count"-array back to cache
    if(isset($this->CacheObj) && ($this->CacheObj->IsActive()))
    {
      //SD342 remove cache file an re-count
      $this->InitCommentsCount(true);
    }

    return true;

  } //UpdateCommentsCount


  // ##########################################################################
  // LIKE A COMMENT
  // SD343: single-vote "thumbs up" for a single comment; logged-in users only
  // ##########################################################################

  private function _GetCommentByPostedId()
  {
    global $DB;
    if($cid = Is_Valid_Number(GetVar('cid', 0, 'whole_number'),0,1,99999999))
    {
      $DB->result_type = MYSQL_ASSOC;
      $c_arr = $DB->query_first('SELECT * FROM {comments} WHERE commentid = %d LIMIT 1', $cid);
      return $c_arr;
    }
    return false;
  }

  // ##########################################################################
  // GET LIKES FOR A COMMENT
  // ##########################################################################

  function GetCommentLikes($doLikes=true)
  {
    global $DB, $mainsettings, $sdlanguage, $userinfo;

    // SD313: security check against spam/bot submissions
    if(!CheckFormToken('securitytoken', false) ||
       (!Is_Ajax_Request() && empty($this->plugin_id)))
    {
      if(Is_Ajax_Request()) return;
      $this->RedirectPageDefault('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
      return;
    }
    if(!$this->comment_likes) return;

    $post_arr = $this->_GetCommentByPostedId();
    if(!isset($post_arr) || empty($post_arr['commentid']))
    {
      $DB->result_type = MYSQL_BOTH;
      return 'Error';
    }

    if(!empty($post_arr['user_likes']) && ($likes = @unserialize($post_arr['user_likes'])))
    {
      require_once(SD_INCLUDE_PATH.'class_sd_likes.php');
      return SD_Likes::GetLikesList($likes,$doLikes);
    }
    return '';

  } //GetCommentLikes


  // ##########################################################################
  // LIKE A COMMENT
  // SD343: single-vote "thumbs up" for a single comment; logged-in users only
  // ##########################################################################

  function DoLikeComment($action)
  {
    global $DB, $mainsettings, $sdlanguage, $userinfo;

    require_once(SD_INCLUDE_PATH.'class_sd_likes.php');

    //SD370: allow likes by Guests (not logged in)
    if(!CheckFormToken('securitytoken') || empty($this->plugin_id) ||
       !$this->hasPostAccess || #empty($userinfo['loggedin']) ||
       empty($userinfo['usergroupids'])  || !@array_intersect($userinfo['usergroupids'], $this->like_groups) ||
       !in_array($action,array(SD_LIKED_TYPE_COMMENT_REMOVE,SD_LIKED_TYPE_COMMENT,SD_LIKED_TYPE_COMMENT_NO)) )
    {
      if(Is_Ajax_Request()) return false;
      echo '<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />';
      return false;
    }

    if($comment_id = Is_Valid_Number(GetVar('cid', 0, 'whole_number'),0,1,99999999))
    {
      $DB->result_type = MYSQL_ASSOC;
      $comment_arr = $DB->query_first('SELECT commentid, pluginid, objectid, userid FROM {comments}'.
                                      ' WHERE commentid = %d AND pluginid = %d',
                                      $comment_id, $this->plugin_id);
    }
    if(!isset($comment_arr) || empty($comment_arr['commentid']))
    {
      $DB->result_type = MYSQL_BOTH;
      return false;
    }
    $this->object_id = $comment_arr['objectid'];

    $entries = SD_Likes::DoLikeElement($this->plugin_id, $comment_id,
                                       ($action===SD_LIKED_TYPE_COMMENT_REMOVE?SD_LIKED_TYPE_COMMENT_REMOVE:($action==SD_LIKED_TYPE_COMMENT?SD_LIKED_TYPE_COMMENT:SD_LIKED_TYPE_COMMENT_NO)),
                                       $userinfo['userid'], $userinfo['username'],
                                       $comment_arr['userid']);
    if(!empty($entries) && is_array($entries))
    {
      $c_likes = (empty($entries['total'])?0:$entries['total']) +
                 (empty($entries['guests'])?0:$entries['guests']);
      $c_dislikes = (empty($entries['total_no'])?0:$entries['total_no']) +
                    (empty($entries['guests_no'])?0:$entries['guests_no']);
      $DB->query("UPDATE {comments} SET user_likes = '%s', likes_count = %d , dislikes_count = %d WHERE commentid = %d",
                 $DB->escape_string(serialize($entries)),
                 $c_likes, $c_dislikes, $comment_id);

      if(Is_Ajax_Request())
      {
        if($action==SD_LIKED_TYPE_COMMENT_REMOVE)
        {
          echo $sdlanguage['message_comment_like_removed'];
        }
        return true;
      }
      $msg = '';
      switch($action)
      {
        case SD_LIKED_TYPE_COMMENT: $msg = $sdlanguage['message_comment_liked']; break;
        case SD_LIKED_TYPE_COMMENT_NO: $msg = $sdlanguage['message_comment_disliked']; break;
        case SD_LIKED_TYPE_COMMENT_REMOVE: $msg = $sdlanguage['message_comment_like_removed']; break;
      }
      RedirectFrontPage($this->isArticle ? $this->url : RewriteLink($this->url), $msg);
      return true;
    }
    return false;

  } //DoLikeComment


  // ##########################################################################
  // REPORT A COMMENT
  // SD360: user reports a single comment
  // ##########################################################################

  function DoReportComment(& $msg)
  {
    global $DB, $categoryid, $mainsettings, $sdlanguage, $userinfo;

    $msg = '';

    if(!CheckFormToken('securitytoken') || empty($this->plugin_id) || !$this->comment_reports)
    {
      if(Is_Ajax_Request()) return false;
      $msg = '<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />';
      return false;
    }

    if($comment_id = Is_Valid_Number(GetVar('cid', 0, 'whole_number'),0,1,99999999))
    {
      $DB->result_type = MYSQL_ASSOC;
      $comment_arr = $DB->query_first('SELECT commentid, pluginid, objectid, userid, username'.
                                      ' FROM {comments}'.
                                      ' WHERE commentid = %d AND pluginid = %d',
                                      $comment_id, $this->plugin_id);
    }
    if(empty($comment_arr['commentid']))
    {
      $DB->result_type = MYSQL_BOTH;
      return false;
    }
    $this->object_id = (int)$comment_arr['objectid'];

    require_once(SD_INCLUDE_PATH.'class_sd_reports.php');
    SD_Reports::Init();

    $confirmed = GetVar('report_confirm', false, 'string', true, false)?1:0;
    $user_msg  = '';
    if($this->comment_reports_msg)
    {
      $user_msg  = GetVar('user_msg', '', 'string', true, false);
    }
    if($reasonid = Is_Valid_Number(GetVar('report_reason', 0, 'whole_number',true,false),0,1,999999))
    {
      $reason = SD_Reports::GetCachedReason($this->plugin_id, $reasonid, true);
    }
    if(empty($reason) || empty($confirmed))
    {
      $msg = $sdlanguage['err_invalid_report'];
      return false;
    }

    // Check if comment needs reporting?
    $reportcount = 1;
    if(false !== ($report = SD_Reports::GetReportedItem($this->plugin_id, $this->object_id, $comment_id)))
    {
      // Update existing report
      $reportcount = empty($report['reportcount'])?0:(1+$report['reportcount']);
      SD_Reports::AddToReport($report['reportid'], $reasonid);
      $msg = $sdlanguage['comment_already_reported'];
    }
    else
    {
      // Add new report for comment
      $pageid = GetVar('categoryid', $categoryid, 'whole_number', true, false);
      $reportid = SD_Reports::CreateReport($this->plugin_id, $this->object_id,
                              $comment_id, $reasonid, $pageid,
                              $comment_arr['userid'], $comment_arr['username'],
                              $user_msg);
      if($reportid === false)
      {
        $msg = $sdlanguage['err_invalid_report'];
        return false;
      }
      $msg = $sdlanguage['comments_comment_reported'];

      SD_Reports::SendReport($reportid, $this->plugin_id,
                             'Comment reported by [username], [date]');
    }

    // Moderate post if amount of reports reached
    if(!empty($mainsettings['reports_moderate_items']) &&
       ($reportcount >= $mainsettings['reports_moderate_items']))
    {
      $this->SetApproved($comment_id, 0);
    }

    return true;

  } //DoReportComment


  // ##########################################################################
  // DISPLAY COMMENTS
  // ##########################################################################

  public function DisplayComments()
  {
    global $mainsettings, $userinfo, $sdlanguage;

    //SD343: first check permissions and settings
    $this->setPerm($this->plugin_id, $this->object_id);
    if(empty($this->plugin_id) || empty($this->object_id))
    {
      return false;
    }
    $skipOutput = false;
    $this->_internal_call = true; //SD343

    echo '
    <!-- Comments -->
    <div id="comments">
    <h3 class="comments-title">'.trim($sdlanguage['comments']).'</h3>';

    //SD343: process a user liking a comment
    require_once(SD_INCLUDE_PATH.'class_sd_likes.php');
    $like_action = GetVar('like_action', '', 'string', false, true); //SD343
    if($this->hasPostAccess && in_array($like_action,array(SD_LIKED_TYPE_COMMENT_REMOVE,SD_LIKED_TYPE_COMMENT,SD_LIKED_TYPE_COMMENT_NO)))
    {
      $skipOutput = $this->DoLikeComment($like_action);
    }

    if(!$skipOutput)
    {
      $comment_action = GetVar('comment_action', '', 'string'); //SD343
      if($this->hasPostAccess && ($comment_action=='insert_comment') && $this->allow_comments)
      {
        $this->InsertComment();
        //SD313 if insert was successful, clear insert flag and show comment form again
        unset($_POST['insert_comment']);
        if(!$this->allow_comments) DisplayMessage($sdlanguage['comments_closed'], false);
      }
      else
      {
        if(!empty($mainsettings['commentorder'])) $this->DisplayCommentRows(false,true);

        if($this->hasPostAccess)
        {
          if($this->allow_comments)
          {
            $this->DisplayCommentForm();
          }
          else
          {
            DisplayMessage($sdlanguage['comments_closed'], false);
          }
        }
        else
        {
          DisplayMessage($sdlanguage['comment_access_denied'], true);
        }

        if(empty($mainsettings['commentorder'])) $this->DisplayCommentRows(false,true);
      }
    }
    $this->ResetMembers();

    echo '
    </div>
    ';

    return true;

  } //DisplayComments


  // ##########################################################################
  // DISPLAY COMMENT ROWS
  // ##########################################################################

  public function DisplayCommentRows($lastComment=false)
  {
   
    global $DB, $bbcode, $categoryid, $mainsettings, $sdurl, $sdlanguage,
           $userinfo, $usersystem, $smarty;

    if(empty($this->_internal_call))
    {
      $this->setPerm($this->plugin_id,$this->object_id);
    }
    if(empty($this->plugin_id) || empty($this->object_id))
    {
      return false;
    }

    $page = Is_Valid_Number(GetVar('cpage', 1, 'whole_number'), 1, 1); //SD342
    $limit = 0;
    $limit_cond = '';
    $first_comment_id = 0;

    if($this->isArticle)
    {
      $a_settings = GetPluginSettings($this->plugin_id);
      $limit = isset($a_settings['comments_display_limit']) ? $a_settings['comments_display_limit'] : 0;
    }
    $items_per_page = Is_Valid_Number($limit, 10, 1, 1000); //SD342
    if($this->isArticle && $limit)
    {
      $limit_cond = ' LIMIT ' .(($page-1)*$items_per_page) . ',' . $items_per_page; //SD342
    }

    $cond = (!empty($mainsettings['commentorder']) ? 'ASC' : 'DESC');

    // Is only the very last comment requested? Useful for Ajax call after submit
    if(!empty($lastComment))
    {
      $cond = ' DESC LIMIT 1';
      $limit_cond = '';
    }
    if($comment_count = $DB->query_first('SELECT COUNT(*) C_COUNT FROM {comments} c'.
                                         ' WHERE c.pluginid = %d AND c.objectid = %d AND c.approved = 1',
                                         $this->plugin_id, $this->object_id))
    {
      $comment_count = (int)$comment_count['C_COUNT'];
    }

    // SD313: added join on users to avoid 2nd select in "GetAvatarPath" (for SD usersystem only!)
    if($usersystem['name'] == 'Subdreamer')
    {
      $get_comments = $DB->query(
        "SELECT c.*, IFNULL(u.email,'') email".
        ' FROM {comments} c'.
        ' LEFT JOIN {users} u ON c.userid = u.userid' .
        ' WHERE c.pluginid = %d AND c.objectid = %d'.
        ' AND c.approved = 1'.
        ' ORDER BY c.commentid ' . $cond . $limit_cond,
        $this->plugin_id, $this->object_id);
    }
    else
    {
      $get_comments = $DB->query(
        'SELECT c.*'.
        ' FROM {comments} c'.
        ' WHERE c.pluginid = %d AND c.objectid = %d'.
        ' AND c.approved = 1'.
        ' ORDER BY c.commentid ' . $cond . $limit_cond,
        $this->plugin_id, $this->object_id);
    }
    if(empty($comment_count))
    {
      return false;
    }

    // SD313: is site-wide BBCode enabled?
    $allow_bbcode = !empty($mainsettings['allow_bbcode']) && isset($bbcode) && ($bbcode instanceof BBCode);

    // Config array as parameter for sd_PrintAvatar (in globalfunctions.php)
    $sdlanguage['comments_delete'] = htmlspecialchars($sdlanguage['comments_delete']);
    $sdlanguage['comments_edit']   = htmlspecialchars($sdlanguage['comments_edit']);
    $sdlanguage['comments_report'] = htmlspecialchars($sdlanguage['comments_report']); //SD360
    $link = $this->isArticle ? $this->url : RewriteLink($this->url);

    // Instantiate smarty object
    $smarty = SD_Smarty::getNew(); //SD342 use new SD's Smarty class
    $smarty->assign('AdminAccess',   $this->IsAdmin);
    $smarty->assign('CommentsOrder', $mainsettings['commentorder']);
    $smarty->assign('ListTag',       ($comment_count && empty($lastComment)));
    $smarty->assign('pluginid',      $this->plugin_id);
    $smarty->assign('objectid',      $this->object_id);
    $smarty->assign('sdlanguage',    $sdlanguage);
    $smarty->assign('sdurl',         SITE_URL);
    $smarty->assign('securitytoken', $userinfo['securitytoken']);
    $smarty->assign('userid',        $userinfo['userid']);
    $smarty->assign('cp_path',       CP_PATH);
    $smarty->assign('url',           $link);
    $smarty->assign('allow_new',     $this->allow_comments);
    $smarty->assign('categoryid',     $categoryid);
    //SD343: permissions for user edit/delete
    $allow_edit_own_comments = $this->IsAdmin || ($this->hasPostAccess && !empty($userinfo['usergroup_details']['edit_own_comments']));
    $allow_delete_own_comments = $this->IsAdmin || ($this->hasPostAccess && !empty($userinfo['usergroup_details']['delete_own_comments']));
    $smarty->assign('edit_comments', $allow_edit_own_comments);
    $smarty->assign('delete_comments', $allow_delete_own_comments);
    $smarty->assign('report_comments', $this->comment_reports); //SD360
    $smarty->assign('report_user_message', $this->comment_reports_msg);

    $display_admin_area = $this->IsAdmin ||
                          $allow_edit_own_comments ||
                          $allow_delete_own_comments ||
                          $this->comment_reports;

    //SD343: use new static SDUserCache class
    include_once(SD_INCLUDE_PATH.'class_sd_usercache.php');
    SDUserCache::$ForumAvatarAvailable  = function_exists('ForumAvatar');
    SDUserCache::$img_path              = 'images/';
    SDUserCache::$bbcode_detect_links   = false;
    SDUserCache::$lbl_offline           = 'Offline';
    SDUserCache::$lbl_online            = 'Online';
    SDUserCache::$lbl_open_profile_page = '';
    SDUserCache::$lbl_view_member_page  = '';
    SDUserCache::$show_avatars          = (!isset($userinfo['profile']['user_view_avatars']) ||
                                           !empty($userinfo['profile']['user_view_avatars']));
    $comments_tmpl = array();
    $smarty->assign('send_msg_title', SDProfileConfig::$profile_pages['page_newmessage']); //SD343
    $jscode = $jsready = '';

    //SD343: comments "Likes" feature
    require_once(SD_INCLUDE_PATH.'class_sd_likes.php');
    $MessagingOn = !empty($mainsettings['display_messaging_symbols']);
    $securitytoken = '&amp;securitytoken='.$userinfo['securitytoken'];

    while($comment_arr = $DB->fetch_array($get_comments,null,MYSQL_ASSOC))
    {
      if(!$first_comment_id) $first_comment_id = (int)$comment_arr['commentid'];

      //SD343: user caching
      $commenter = SDUserCache::CacheUser($comment_arr['userid'], $comment_arr['username'], true, false);

      //SD343: pass public messaging icons to template
      if($MessagingOn && !empty($commenter['public_fields']))
      {
        $messenging = '';
        foreach(SDUserCache::$messaging_fields as $field)
        {
          $name = str_replace('user_','',$field);
          if(!empty($commenter[$field]) && in_array($field, $commenter['public_fields']))
          {
            $messenging .= SDUserProfile::GetMessengingLink('ext_'.$name, $commenter[$field],false).' ';
          }
        }
        if($messenging!==false)
        {
          $comment_arr['messaging_html'] = $messenging;
        }
      }
      //SD343: pass on flag if messaging is actually available
      $comment_arr['msg_enabled'] = (!isset($commenter['user_allow_pm']) || !empty($commenter['user_allow_pm'])) &&
                                    !empty($commenter['usergroup_details']['msg_enabled']) &&
                                    !empty($userinfo['usergroup_details']['msg_enabled']);
      $comment_arr['user_link'] = $commenter['profile_link'];
      $comment_arr['user_link_href'] = ForumLink(4, (int)$comment_arr['userid']);
      $comment_arr['date'] = DisplayDate($comment_arr['date']);
      //SD360: hide avatar (usergroup config)?
      if(!empty($userinfo['loggedin']) || empty($commenter['usergroup_details']['hide_avatars_guests']))
        $comment_arr['avatar'] = ForumAvatar($comment_arr['userid'], $comment_arr['username']);
      else
        $comment_arr['avatar'] = GetDefaultAvatarImage();

      if($allow_bbcode) //SD313
      {
        $comment_arr['comment'] = $bbcode->Parse($comment_arr['comment']);
      }

      //SD343: "Likes" feature
      $comment_arr['likes_html'] = '';
      $likes_txt = '';
      if(!empty($mainsettings['comments_display_likes']) &&
         !empty($comment_arr['user_likes']) && ($likes = @unserialize($comment_arr['user_likes'])))
      {
        $likes_txt  = SD_Likes::GetLikesList($likes,true);
        $likes_txt .= ' '.SD_Likes::GetLikesList($likes,false);
        $likes_txt = trim($likes_txt);
      }

      //SD343: "like" post link
      if($this->comment_likes)
      {
        $link2 = $link.(strpos($link,'?')===false ? '?' : '&amp;');

        $out1 = $out2 = '';
        $showRemove = false;
        $cid = $comment_arr['commentid'];
        //SD370: for guests also check IP address
        $extra = '';
        if(empty($userinfo['userid']) || empty($userinfo['loggedin']))
        {
          $extra = " AND ip_address = '".$DB->escape_string(IPADDRESS)."' ";
        }
        $liked = $DB->query_first('SELECT 1 postliked FROM {users_likes}'.
                                  " WHERE pluginid = %d AND objectid = %d ".
                                  $extra.
                                  " AND userid = %d AND liked_type = '%s' LIMIT 1",
                                 $this->plugin_id, $comment_arr['commentid'],
                                 $userinfo['userid'], SD_LIKED_TYPE_COMMENT);
        if(empty($liked['postliked']))
        {
          $out1 = ' <a class="like-link" rel="nofollow" href="'.$link2.
                  'do=like&amp;action='.SD_LIKED_TYPE_COMMENT.'&amp;cid='.$cid.$securitytoken.'">'.
                  $sdlanguage['like_comment'].'</a>';
        }
        else $showRemove = 1;

        $disliked = $DB->query_first('SELECT 1 postdisliked FROM {users_likes}'.
                                     " WHERE pluginid = %d AND objectid = %d ".
                                     $extra.
                                     " AND userid = %d AND liked_type = '%s' LIMIT 1",
                                     $this->plugin_id, $comment_arr['commentid'],
                                     $userinfo['userid'], SD_LIKED_TYPE_COMMENT_NO);
        if(empty($disliked['postdisliked']))
        {
          $out2 = ' <a class="dislike-link" rel="nofollow" href="'.$link2.
                  'do=like&amp;action='.SD_LIKED_TYPE_COMMENT_NO.'&amp;cid='.$cid.$securitytoken.'">'.
                  $sdlanguage['dislike_comment'].'</a>';
        }
        else $showRemove = 2;

        if(($showRemove==1) || ($showRemove==2))
        {
          $out = ' <a class="remove-like-link" rel="nofollow" href="'.$link2.
                  'do=like&amp;action='.SD_LIKED_TYPE_COMMENT_REMOVE.'&amp;cid='.$cid.$securitytoken.'">'.
                  $sdlanguage[$showRemove==1?'remove_like':'remove_dislike'].'</a>';
        }
        if($out1 || $out2 || strlen($likes_txt))
          $comment_arr['likes_html'] = '<div class="likes-box">'.$likes_txt.(!$out1||!$out2?$out:'').$out1.$out2.'</div>';
      }
      else
      if(strlen($likes_txt))
      {
        $comment_arr['likes_html'] = '<div class="likes-box">'.$likes_txt.'</div>';
      }
      unset($likes_txt, $total_count, $ucount);

      // Add to smarty array:
      $comments_tmpl[] = $comment_arr;

    } //while

    $smarty->assign('comments_list', $comments_tmpl);

    //SD343: add Likes-JS
    if($this->comment_likes)
    {
      $sub_url = SITE_URL.'includes/ajax/sd_ajax_comments.php?';
      $jsready .= '
      $("#comments a.like-link, #comments a.dislike-link, #comments a.remove-like-link").each(function(){
        comments_likelink = $(this).attr("href");
        var qpos = comments_likelink.lastIndexOf("?");
        if(qpos > 0) {
          uid = comments_likelink.substr(qpos+1);
          if($(this).hasClass("like-link"))
            $(this).attr("title","'.addslashes($sdlanguage['like_post']).'");
          else
          if($(this).hasClass("like-link"))
            $(this).attr("title","'.addslashes($sdlanguage['dislike_post']).'");
          else
            $(this).attr("title","");

          $(this).attr("href","'.$sub_url.'pluginid='.$this->plugin_id.
                                '&amp;oid='.$this->object_id.'&amp;catid='.$categoryid.'&amp;"+uid);

          $(this).click(function(event){
            event.preventDefault();
            var linkparent = $(this).parent("div");
            jQuery.ajax({
              async: true, cache: false, dataType: "html", url: $(this).attr("href"),
              error: function(data) { alert("'.addslashes($sdlanguage['ajax_operation_failed']).'"); },
              success: function(data) {
                if((typeof data !== "undefined") && (data != "0")){
                  $(linkparent).html(data);
                }
                else {
                  alert("'.addslashes($sdlanguage['ajax_operation_failed']).'");
                }
              }
            });
            return false;
          });
        }
      });
      ';
    }

    //SD342: display subscribe/unsubscribe link
    require_once(SD_INCLUDE_PATH.'class_userprofile.php');
    $subscription_html = false;
    $smarty->assign('subscription_enabled', false);
    if(!empty($userinfo['userid']) && !empty($this->object_id) &&
       !empty(SDProfileConfig::$usergroups_config[$userinfo['usergroupid']]['enable_subscriptions']))
    {
      //SD343: moved output into template and -variables
      $subscription_html = true;
      $smarty->assign('subscription_enabled', true);
      $sub = new SDSubscription($userinfo['userid'],$this->plugin_id,$this->object_id,'comments');
      $sub_type = false;

      if($this->allow_comments)
      {
        $smarty->assign('subscribe_link', true);
      }
      $smarty->assign('is_subscribe', false);
      if($sub->IsSubscribed())
      {
        $smarty->assign('is_subscribed', true);
        $sub_type = 'un';
        $sub->UpdateRead();
      }
      elseif(!empty(SDProfileConfig::$usergroups_config[$userinfo['usergroupid']]['enable_subscriptions']))
      {
        $sub_type = $this->allow_comments?'':false;
      }
      unset($sub);

      if($subscription_html && ($sub_type!==false))
      {
        $sub_url = SITE_URL.'includes/ajax/sd_ajax_comments.php?pluginid='.$this->plugin_id.
                   '&amp;oid='.$this->object_id.'&amp;securitytoken='.$userinfo['securitytoken'].
                   '&amp;catid='.$categoryid.'&amp;do=';
        $subid = 'div#sub-'.$this->plugin_id.'-'.$this->object_id;
        $jsready .= '
        jQuery("'.$subid.' a").hide();
        jQuery("'.$subid.' a.'.$sub_type.'subscribe-link").show().parent().show();
        jQuery("'.$subid.' a").click(function(event){
          event.preventDefault();
          var sub_url  = "'.$sub_url.'";
          var sub_type = jQuery(this).attr("rel")=="0"?"unsubscribe":"subscribe";
          jQuery.ajax({
            async: true, cache: false, dataType: "html", url: sub_url+sub_type,
            error: function(data) { alert("'.addslashes($sdlanguage['ajax_operation_failed']).'"); },
            success: function(data) {
              if((typeof data !== "undefined") && (data != "0")){
                if(data=="subscribed") {
                  jQuery("'.$subid.' a").toggle();
                  alert("'.addslashes($sdlanguage['subscribed']).'");
                } else
                if(data=="unsubscribed") {
                  jQuery("'.$subid.' a").toggle();
                  alert("'.addslashes($sdlanguage['unsubscribed']).'");
                }
              }
              else { alert("'.addslashes($sdlanguage['ajax_operation_failed']).'"); }
            }
          });
          return false;
        });
        ';
      }
    }

    //SD342: pagination output
    if($this->isArticle && $limit && ($comment_count > $items_per_page))
    {
      $p = new pagination;
      $p->nextLabel('');
      $p->prevLabel('');
      $p->parameterName('cpage');
      $p->items($comment_count);
      $p->limit($items_per_page);
      $p->currentPage($page);
      $p->adjacents(1);
      $p->target($this->url);
      $pagination = trim($p->getOutput());
      $smarty->assign('display_pagination', (strlen(strip_tags($pagination)) > 0));
      $smarty->assign('comments_pagination', $pagination);
    }

    // BIND AND DISPLAY TEMPLATE NOW
    if(defined('SD_SMARTY_VERSION') && (SD_SMARTY_VERSION > 2)) //SD344
    {
      $tmpl_done = SD_Smarty::display(0, 'comments.tpl');
    }
    else
    {
      // Check if custom version exists, otherwise use default template:
      if(is_file(SD_INCLUDE_PATH.'tmpl/comments_frontpage.tpl'))
      {
        $smarty->display('comments_frontpage.tpl');
      }
      else
      if(is_file(SD_INCLUDE_PATH.'tmpl/comments.tpl'))
      {
        $smarty->display('comments.tpl');
      }
      else
      if(is_file(SD_INCLUDE_PATH.'tmpl/defaults/comments.tpl'))
      {
        $smarty->setTemplateDir(SD_INCLUDE_PATH.'tmpl/defaults/');
        $smarty->display('comments.tpl');
      }
      else
      {
        if($userinfo['adminaccess']) echo '<br />Comments Template NOT FOUND!<br />';
      }
    }

    //SD342: upon pagination scroll first comment into view
    if(isset($_GET['cpage']) && Is_Valid_Number($_GET['cpage'],false,1) && !empty($first_comment_id))
    {
      $jsready .= '
      if (typeof jQuery.fn.scrollTo == "undefined") {
        jQuery.getScript("'.$sdurl.MINIFY_PREFIX_F.'includes/javascript/jquery.scrollTo-min.js", function(){
          jQuery.scrollTo("li#comment-'.$first_comment_id.'", 200);
        });
      } else {
          jQuery.scrollTo("li#comment-'.$first_comment_id.'", 200);
      }';
    }

    //SD322: admin options JS
    //SD343: now uses $display_admin_area instead of IsAdmin
    if($display_admin_area && empty($lastComment) && !defined('SD_COMMENTS_JS'))
    {
      define('SD_COMMENTS_JS', true); //SD332 new: prevent duplicate JS
      // removed from "newurl":  &amp;pluginid='.$this->plugin_id.'
      $jscode .= '
    function ReloadComment() {
      var commentid = jQuery("#com_editsave").text();
      if(commentid) {
        var target = jQuery("p#comment-"+commentid);
        jQuery(target).html("<img alt=\"\" src=\"includes/css/images/indicator.gif\" height=\"16\" width=\"16\" /> <strong>'.addslashes($sdlanguage['message_loading']).'<\/strong>");
        var newurl = sdurl+"includes/ajax/sd_ajax_comments.php?do=getcomment&amp;cid="+commentid+"&amp;securitytoken='.urlencode($userinfo['securitytoken']).'";
        jQuery.ajax({ async: true, cache: false, url: newurl,
          success: function(data){
            if((typeof data !== "undefined") && (data != "0")) jQuery(target).html(data);
            jQuery("#comment-loader-"+commentid).hide();
          }
        });
      }
    }

    function ConfirmDeleteComment() {
      if(confirm("'.
      addslashes($sdlanguage['comments_confirm_delete']).
      '")) return true; else return false;
    }

    function InitCommentEdit() {
      if(typeof jQuery.fn.ceebox != "undefined") {
        jQuery("a.comment-edit").each(function(event) {
            jQuery(this).addClass("cbox").attr("rel", "iframe modal:true height:390 width:750");
            var link = jQuery(this).attr("href") + "&amp;cbox=1";
            jQuery(this).attr("href", link);
          });

        jQuery("a.comment-report").each(function(event) {
            jQuery(this).addClass("cbox").attr("rel", "iframe modal:true width:750");
            var link = jQuery(this).attr("href") + "&amp;cbox=1&amp;categoryid='.$categoryid.'";
            jQuery(this).attr("href", link);
          });

        jQuery("a.comment-edit").ceebox({
          animSpeed: "fast", borderWidth: "2px", htmlGallery: false, html: true,
          overlayOpacity: 0.7, margin: "80", padding: "14", titles: false,
          onload: function(){ jQuery(".markItUpHeader").hide(); },
          unload: function(){ jQuery(".markItUpHeader").show(); ReloadComment(); }
        });

        jQuery("a.comment-report").ceebox({
          animSpeed: "fast", borderWidth: "2px", htmlGallery: false, html: true,
          overlayOpacity: 0.7, margin: "80", padding: "14", titles: false,
          onload: function(){ jQuery(".markItUpHeader").hide(); },
          unload: function(){ jQuery(".markItUpHeader").show(); ReloadComment(); }
        });
      }
    }';

    //SD360: added "a.comment-report" JS event
    $jsready .= '
    jQuery(".comment-admin").css("display","inline");

    jQuery("a.comment-edit,a.comment-report").click(function(){
      jQuery("#com_editsave").text(jQuery(this).attr("target"));
    });

    jQuery("a.comment-delete").click(function(e){
      e.preventDefault();
      var parent = jQuery(this).parents("li");
      var oldBorder = jQuery(parent).css("borderColor");
      jQuery(parent).css("borderColor", "red");
      if(ConfirmDeleteComment()) {
        var href = jQuery(this).attr("href");
        jQuery.ajax({ async: true, cache: false, url: href,
          success: function(data){
            if((typeof (data) !== "undefined") && (data == "1")) {
              jQuery(parent).find(".comment-admin").remove();
              jQuery(parent).find("p.comment-text").parents("div.comment").replaceWith("<div id=\'success_message\'>'.
              addslashes($sdlanguage['comments_deleted']).'<\/div>");
            } else {
              if(data.length > 0){ alert(data); }
            }
            return false;
          }
        });
      }
      jQuery(parent).css("borderColor", oldBorder);
      return false;
    });

    jQuery("<link>").appendTo("head").attr({rel: "stylesheet", type: "text/css", href: "'.$sdurl.'/includes/css/ceebox.css" });
    if (typeof (jQuery.fn.ceebox) == "undefined") {
      jQuery.getScript(sdurl+"includes/javascript/jquery.ceebox-min.js", InitCommentEdit);
    } else {
      InitCommentEdit();
    }
    ';
    } //Edit/Delete-JS

    if(strlen($jscode) || strlen($jsready))
    {
      echo '
    <script type="text/javascript">
    //<![CDATA[';
      if(strlen($jscode))
      {
        echo $jscode;
      }

      if(strlen($jsready))
      {
        echo '
    jQuery(document).ready(function() {
    '.$jsready.'
    });';
      }

      echo '
    //]]>
    </script>
    ';
    }

  } //DisplayCommentRows


  // ##########################################################################
  // DISPLAY COMMENT FORM
  // ##########################################################################

  public function DisplayCommentForm($comment_id = null)
  {
    global $DB, $bbcode, $categoryid, $mainsettings, $sdlanguage, $userinfo, $sdurl;

    if(empty($this->_internal_call))
    {
      $this->setPerm($this->plugin_id,$this->object_id);
    }
    if(empty($this->plugin_id) || empty($this->object_id))
    {
      return false;
    }

    if(!$this->hasPostAccess)
    {
      return false;
    }

    if(!empty($_POST['insert_comment']))
    {
      $comment_username = GetVar('comment_username', '', 'string');
      $comment_comment  = GetVar('comment_comment', '', 'string');
    }
    else
    {
      $comment_username = '';
      $comment_comment  = '';
    }

    // SD313: use last comment for current object as page hash
    $lastentry = $DB->query_first('SELECT MAX(commentid) max_id'.
                                  ' FROM {comments}'.
                                  ' WHERE pluginid = %d AND objectid = %d'.
                                  ' LIMIT 1', $this->plugin_id, $this->object_id);

    $link = $this->isArticle ? $this->url : RewriteLink($this->url);
    $anchor = isset($lastentry['max_id']) ? '&amp;c='.$lastentry['max_id'].'#c'.$lastentry['max_id'] : '';

    //SD360: ajax-submittal of new comment to avoid post-buffer on reload
    if($this->allow_comments && $this->hasPostAccess)
	{
		// Instantiate smarty object
		$_smarty = SD_Smarty::getNew(true); //SD370: 1st param must be "true"
		$_smarty->assign('AdminAccess',   ($userinfo['adminaccess'] || (!empty($userinfo['pluginadminids']) &&
												@array_key_exists($this->plugin_id,array_flip($userinfo['pluginadminids'])))));
		$_smarty->assign('categoryid',  $categoryid);
		$_smarty->assign('pluginid',	$this->plugin_id);
		$_smarty->assign('objectid',	$this->object_id);
		$_smarty->assign('language',    $sdlanguage);
		$_smarty->assign('settings', 	$mainsettings);
		$_smarty->assign('userinfo',	$userinfo);
		$_smarty->assign('sdurl',		$sdurl);
		$_smarty->assign('secure_token', PrintSecureToken('comment_token'));
		$_smarty->assign('link',		$link);
		$_smarty->assign('comment_username', $comment_username);
		$_smarty->assign('comment_comment',	 $comment_comment);
	

    // SD313: is site-wide BBCode enabled?
    $allow_bbcode = !empty($mainsettings['allow_bbcode']) && isset($bbcode) && ($bbcode instanceof BBCode);

   
    $_smarty->assign('bbeditor',  DisplayBBEditor($allow_bbcode, 'comment_comment', $comment_comment,'','','', true));
	  $_smarty->assign('captcha',	   DisplayCaptcha(false));
    $_smarty->assign('allow_bbcode', $allow_bbcode);
	
    //SD370: output new custom comments form footer option
    $footer_done = false;
    if(!empty($mainsettings['comments_form_footer']) && ($tmp = $mainsettings['comments_form_footer']) &&
       (strlen($tmp) > 10) && (strpos($tmp, 'type="submit"')!==false))
    {
      $_smarty->assign('footer',  str_replace('{post_comment}',htmlspecialchars($sdlanguage['post_comment']),$tmp));
      $footer_done = true;
    }
	
    if(!$footer_done)
    {
      $_smarty->assign('footer', '<input type="submit" value="'.htmlspecialchars($sdlanguage['post_comment']).'" />');
    }
	
	$err_msg = '<br /><b>'.$plugin_names[$this->plugin_id].' ('.$this->plugin_id.') template file NOT FOUND!</b><br />';
    
    if(defined('SD_SMARTY_VERSION') && (SD_SMARTY_VERSION > 2)) //SD344
    {
      $error = !SD_Smarty::display(0, 'comment_form.tpl', $_smarty);
      
      if($error && !empty($userinfo['adminaccess']))
      {
        $err = SD_Smarty::getLastError();
        echo $err.$err_msg;
      }
    }
    else
    {
		  if(is_file(SD_INCLUDE_PATH.'tmpl/comment_form.tpl')) //SD343
		  {
			  $_smarty->display('comment_form.tpl');
		  }
		  elseif(is_file(SD_INCLUDE_PATH.'tmpl/defaults/comment_form.tpl'))
		  {
			  $_smarty->setTemplateDir(SD_INCLUDE_PATH.'tmpl/defaults/');
			  $_smarty->display('comment_form.tpl');
		  }
	  }

  }
    return true;

  } //DisplayCommentForm


  public function SendNotification($username, $plugin_id, $comment, $approved)
  {
    global $categoryid, $mainsettings, $pages_md_arr, $plugin_names, $sd_cache,
           $sdlanguage, $userinfo;

    $trigger     = $mainsettings['comments_email_trigger'];
    $noadmin     = !empty($mainsettings['comments_email_no_admin']);
    $senderemail = trim($mainsettings['comments_email_notification']);
    $senderemail = $senderemail ? $senderemail : trim($mainsettings['technicalemail']);
    $fromemail   = $senderemail;
    $sendername  = '';
    if(!empty($mainsettings['comments_email_user_as_sender']) && !empty($userinfo['loggedin']))
    {
      $sendername = $userinfo['name'];
      $fromemail  = $userinfo['email'];
    }
    $this->setPerm($plugin_id);
    if(empty($username))
    {
      $username = $sdlanguage['comments_no_username'];
    }
    if(!strlen($senderemail) ||  empty($this->plugin_id) || empty($comment) ||
       empty($trigger) || ($trigger == 'off') ||
       ($noadmin && $this->IsAdmin) || (($trigger == 'approval') && !empty($approved)))
    {
      return;
    }

    $subject = $mainsettings['comments_email_subject'];
    $message = $mainsettings['comments_email_body'];
    $date    = DisplayDate(TIME_NOW);
    if($this->isArticle)
    {
      // pass through URL
      $link = $this->url;
    }
    else
    {
      $link = RewriteLink($this->url);
      //SD362: try to resolve to the plugin item URL, if possible:
      if($res = p_LC_TranslateObjectID($this->plugin_id, $this->object_id,
                                       $this->comment_id, $categoryid))
      {
        $link = !empty($res['link'])?$res['link']:$link;
      }
    }
    $link = '<a href="'.$link.'">'.$link.'</a>';
    $pagename = !empty($pages_md_arr[$categoryid]['title']) ?
                       $pages_md_arr[$categoryid]['title'] :
                       $pages_md_arr[$categoryid]['name'];
    $pluginname = isset($plugin_names[$this->plugin_id]) ? $plugin_names[$this->plugin_id] : 'unknown';

    $status = $sdlanguage[$approved ? 'approved' : 'unapproved'];
    //SD343: added [ipaddress]
    $message = str_replace(array('[username]', '[commentstatus]', '[comment]', '[date]', '[pluginname]', '[pagename]', '[pagelink]', '[ipaddress]'),
                           array( $username,    $status,           $comment,   $date,     $pluginname,    $pagename,    $link,       USERIP), $message);

    return SendEmail($fromemail, $subject, $message, $sendername, $senderemail, null, null, true);

  } //SendNotification


  // ##########################################################################
  // INSERT COMMENT
  // ##########################################################################
  // $objectid can be anything like categoryid, articleid, imageid, etc...

  public function InsertComment()
  {
    global $DB, $categoryid, $mainsettings, $sdlanguage, $sdurl, $userinfo;

    if(empty($this->_internal_call))
    {
      $this->setPerm($this->plugin_id,$this->object_id);
    }
    if(empty($this->plugin_id) || empty($this->object_id))
    {
      return false;
    }
    $errors_arr = array();

    if(!CheckFormToken('comment_token', false))
    {
      $errors_arr[] = $sdlanguage['error_invalid_token'];
    }
    else
    if(!$this->hasPostAccess)
    {
      $errors_arr[] = $sdlanguage['comment_access_denied'];
    }

    $comment = GetVar('comment_comment', '', 'string', true, false);
	
    if(empty($userinfo['loggedin']))
    {
      $username = GetVar('comment_username', '', 'string', true, false);
      //SD343:
      if((strlen($username) < 3) ||
         ($username != preg_replace("/%0A|\\r|%0D|\\n|%00|\\0|\\t|%01|%02|%03|%04|%05|%06|%07|%08|%09|%0B|%0C|%0E|%0F|%10|%11|%12|%13/im", '', $username)) ||
         (function_exists('DetectXSSinjection') && DetectXSSinjection(unhtmlspecialchars($comment))) )
      {
        $errors_arr[] = $sdlanguage['enter_comment_name'];
      }
    }
    else
    {
      $username = $userinfo['username'];
    }

    #$comment = htmlspecialchars(strip_tags(sd_htmlawed(unhtmlspecialchars($comment))));
    if(strlen($comment) < 3)
    {
      $errors_arr[] = $sdlanguage['enter_comment'];
    }

    if(!CaptchaIsValid())
    {
      $errors_arr[] = $sdlanguage['captcha_not_valid'];
    }

    // ########################################################################
    // ##################### SECURITY CHECK - START ###########################
    // ########################################################################
    // SD313: for security - detect SQL/XSS injection in original post
    // Note: functions are in "functions_security.php" which is normally
    // included already in "init.php", but better check by "function_exists"
    if(strlen($comment))
    {
      $comment = sd_unhtmlspecialchars($comment);
      $comment = preg_replace("/%0A|%0D|%00|\\0|%01|%02|%03|%04|%05|%06|%07|%08|%0B|%0C|%0E|%0F|%11|%12/im", '', $comment);
      if(function_exists('DetectXSSinjection') && DetectXSSinjection($comment))
      {
        $comment = '';
        $errors_arr[] = $sdlanguage['comment_rejected'];
        unset($_POST['comment_comment']);
      }
      else
      {
        $comment = htmlspecialchars(strip_alltags($comment));
      }
    }

    //SD343: combined blacklist check and SFS checking user's email and IP
    $blacklisted = false;
    if(empty($errors_arr))
    {
      if(!empty($mainsettings['comments_sfs_antispam']) && defined('USERIP') &&
         function_exists('sd_sfs_is_spam') &&
         sd_sfs_is_spam((empty($userinfo['email'])?null:$userinfo['email']),USERIP))
      {
        $blacklisted = true;
      }
      if(!$blacklisted && !empty($mainsettings['comments_enable_blocklist_checks']) &&
         function_exists('sd_reputation_check'))
      {
        $blacklisted = sd_reputation_check(USERIP, 1, 'comments_enable_blocklist_checks');
      }

      if($blacklisted)
      {
        WatchDog('Comments','<b>Comment rejected (blacklisted): '.$username.
                 '</b>, IP: </b><span class="ipaddress">'.USERIP.'</span></b><br />'.
                 ' for plugin id: '.$this->plugin_id.', object id: '.$this->object_id,
                  WATCHDOG_ERROR);
        $errors_arr[] = $sdlanguage['ip_listed_on_blacklist'];
      }
    }

    // ########################################################################
    // ####################### SECURITY CHECK - END ###########################
    // ########################################################################

    $comment_id = false;
    $result = false;
    if(empty($errors_arr))
    {
      unset($_POST['comment_comment']);
      // check for repeat posting
      $DB->result_type = MYSQL_ASSOC;
      $lastentry = $DB->query_first('SELECT username, comment FROM {comments}
                                     WHERE pluginid = %d AND objectid = %d
                                     ORDER BY commentid DESC LIMIT 1',
                                     $this->plugin_id, $this->object_id);
      if(($lastentry['username'] == $username) && ($lastentry['comment'] == $comment))
      {
        $addJS = true;
        //SD360: return error message if called by Ajax
        if(Is_Ajax_Request())
        {
          return $sdlanguage['repeat_comment'];
        }
        else
        {
          if(empty($mainsettings['commentorder']))
          {
            $this->DisplayCommentForm();
            DisplayMessage($sdlanguage['repeat_comment'], true);
          }
          $this->DisplayCommentRows();
          if(!empty($mainsettings['commentorder']))
          {
            $this->DisplayCommentForm();
            DisplayMessage($sdlanguage['repeat_comment'], true);
          }
        }
      }
      else
      {
        $approved = ($userinfo['loggedin'] ||
                    !empty($mainsettings['comments_guest_auto_approve'])) ? 1 : 0;
        //SD322: check for groups with approval being required:
        if($approved && empty($userinfo['adminaccess']))
        {
          $groups = isset($mainsettings['comments_require_approval_groups'])?$mainsettings['comments_require_approval_groups']:'';
          if(@in_array($userinfo['usergroupid'], sd_ConvertStrToArray($groups)))
          {
            $approved = 0;
          }
        }
        //SD342: censor comments if enabled
        if(!empty($mainsettings['censor_comments']))
        {
          $comment = sd_removeBadWords($comment);
        }
        if(strlen($comment) &&
           $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX.'comments (pluginid, objectid, date, userid, username, comment, approved, ipaddress, categoryid)'.
                      " VALUES (%d, %d, %d, %d, '%s', '%s', %d, '%s', %d)",
                      $this->plugin_id, $this->object_id, TIME_NOW, $userinfo['userid'],
                      $username, $DB->escape_string($comment), $approved, USERIP, $categoryid))
        {
          $this->comment_id = $DB->insert_id();
          $this->UpdateCommentsCount();

          //SD342 check for max comments
          if(!empty($this->max_comments) && ($this->GetCommentsCount(false) >= $this->max_comments))
          {
            $this->allow_comments = false;
          }

          $this->SendNotification($username, $this->plugin_id, htmlspecialchars(strip_alltags(unhtmlspecialchars($comment))), $approved); //SD332

          //SD342: send subscription notifications if comment was approved
          if($approved)
          {
            if(!class_exists('SDSubscription'))
            {
              include_once(SD_INCLUDE_PATH.'class_userprofile.php');
            }
            $sub = new SDSubscription($userinfo['userid'],$this->plugin_id,$this->object_id,'comments',$categoryid);
            $sub->SendNotifications();
            unset($sub);
          }
        }

        //SD360: return true if called by ajax
        if(Is_Ajax_Request())
        {
          return true;
        }

        if($this->allow_comments && empty($mainsettings['commentorder']))
        {
          $this->DisplayCommentForm();
          DisplayMessage($sdlanguage['comment_posted']);
        }
        $this->DisplayCommentRows();
        if($this->allow_comments && !empty($mainsettings['commentorder']))
        {
          $this->DisplayCommentForm();
          DisplayMessage($sdlanguage['comment_posted']);
        }
        $result = true;
      }
    }
    else
    if(!Is_Ajax_Request())
    {
      if(empty($mainsettings['commentorder']))
      {
        $this->DisplayCommentForm();
        DisplayMessage($errors_arr, true);
      }
      $this->DisplayCommentRows();
      if(!empty($mainsettings['commentorder']))
      {
        $this->DisplayCommentForm();
        DisplayMessage($errors_arr, true);
      }
    }

    //SD360: return true or first error message for ajax call
    if(Is_Ajax_Request())
    {
      return empty($errors_arr)?'success':$errors_arr[0];
    }

    $div_name = ($result?'success_message':'error_message');
    echo '
<script type="text/javascript">
jQuery(document).ready(function() {
  if (typeof jQuery.fn.scrollTo == "undefined") {
    jQuery.getScript("'.$sdurl.MINIFY_PREFIX_F.'includes/javascript/jquery.scrollTo-min.js", function(){
    jQuery.scrollTo("form[name=comment-form-p'.$this->plugin_id.']", 400);
    jQuery("div#'.$div_name.'").delay(3000).slideUp();
    });
  } else {
    jQuery.scrollTo("form[name=comment-form-p'.$this->plugin_id.']", 400);
    jQuery("div#'.$div_name.'").delay(3000).slideUp();
  };
});
</script>
    ';

    return $result;

  } //InsertComment

} // end of class
