<?php
define('IN_PRGM', true);
define('ROOT_PATH', '../../');
unset($Comments);
@require(ROOT_PATH . 'includes/init.php');

if(#!Is_Ajax_Request() ||
   !CheckFormToken('securitytoken', false) ||
   !class_exists('Comments') || !isset($Comments) ||
   !is_object($Comments) || !empty($userinfo['banned']))
{
  echo GetCommentError(1);
  $DB->close();
  exit();
}

// Check for specific action to return displayable data
$content  = '';
$categoryid = strtolower(GetVar('categoryid', 1, 'whole_number')); //SD362: allow POST/GET!
$funcid   = strtolower(GetVar('do', '', 'string', false, true));
$action   = strtolower(GetVar('action', '', 'string', false, true)); // for likes-operations
$cid      = GetVar('cid', 0, 'string', false, true); // == Comment ID
if(isset($_POST['comment_plugin_id']))
{
  $pluginid = GetVar('comment_plugin_id', 0, 'whole_number', true, false);
}
else
if(isset($_GET['pluginid']))
{
  $pluginid = GetVar('pluginid', 0, 'whole_number', false, true); // == Plugin ID, default 2
}
else
{
 $pluginid = GetVar('pid', 0, 'whole_number');
}

if(substr($cid,0,1)=='c') $cid = substr($cid,1);
if(!is_numeric($cid))
  $cid = 0;
else
  $cid = (int)$cid;

//SD342: added un-/subscribe actions
//SD360: added reportcomment, report_confirm, insertcomment actions
if(!@in_array($funcid, array('insertcomment','subscribe','unsubscribe')))
{
  $DB->result_type = MYSQL_ASSOC;
  if(empty($cid) ||
     (!$comment_arr = $DB->query_first('SELECT * FROM {comments} WHERE commentid = %d LIMIT 1', $cid)))
  {
    echo GetCommentError(2);
    $DB->close();
    exit();
  }
  $pluginid = empty($comment_arr['pluginid']) ? 0 : (int)$comment_arr['pluginid'];
  $Comments->plugin_id = $pluginid;
  $Comments->object_id = $cid;
  $Comments->setPerm($pluginid, $cid);
}
else
{
  $oid = GetVar('oid', 0, 'string', false, true); // == Object ID
  $Comments->object_id = $oid;
  $Comments->plugin_id = $pluginid;
  $Comments->setPerm($pluginid, $oid);
}

require(SD_INCLUDE_PATH.'class_sd_likes.php');
if(($funcid == 'like') &&
   in_array($action, array('get_likes',SD_LIKED_TYPE_COMMENT,SD_LIKED_TYPE_COMMENT_NO,SD_LIKED_TYPE_COMMENT_REMOVE))) //SD343
{
  header('Content-Type: text/html; charset='.SD_CHARSET);
  if(in_array($action,array(SD_LIKED_TYPE_COMMENT_REMOVE,SD_LIKED_TYPE_COMMENT,SD_LIKED_TYPE_COMMENT_NO)))
  {
    if(empty($Comments->comment_likes))
    {
      echo '0';
    }
    else
    if($Comments->DoLikeComment($action))
    {
      if(!empty($mainsettings['comments_display_likes']))
      {
        $tmp = ' '.$Comments->GetCommentLikes(true).' '.$Comments->GetCommentLikes(false);
        echo $tmp;
      }
    }
  }
  else
  if($action=='get_likes')
  {
    if(!empty($mainsettings['comments_display_likes']))
    {
      echo $Comments->GetCommentLikes(true).' '.$Comments->GetCommentLikes(false);
    }
  }
  else
  {
    echo '0';
  }
  exit();
}

// ############################################################################

function GetCommentError($error)
{
  global $sdlanguage, $funcid;

  switch($error)
  {
    case 1  : $error_msg = 'NO ACCESS!'; break;
    case 2  : $error_msg = 'NO COMMENT!'; break;
    case 3  : $error_msg = $sdlanguage['ip_listed_on_blacklist']; break;
    default : $error_msg = 'Unauthorized access!';
  }
  if(in_array($funcid,array('editcomment','updatecomment',
                            'reportcomment','report_confirm')))
  {
    $out = GetCommentHead().'
    <div><center><div style="text-align: center; width: 50%; height: 50%">
    <h1>'.$error_msg.'</h1><br />
    <input class="submit" type="button" onclick="parent.jQuery.fn.ceebox.closebox(\'fast\');" value="'.$sdlanguage['common_close'].
    '" /></body></html>';
    return $out;
  }
  else
  {
    return $error_msg;
  }
}

// ############################################################################

function GetCommentHead($loadEditor=true)
{
  global $sdlanguage, $sdurl;

  return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" type="text/css" href="'.$sdurl.'css.php?style=WYSIWYG" />
<link rel="stylesheet" type="text/css" href="'.$sdurl.'css.php?style=messages" />
<link rel="stylesheet" type="text/css" href="'.$sdurl.'includes/css/ceebox.css" />
<script type="text/javascript">
//<![CDATA[
var sdurl = "'.SITE_URL.'";
//]]>
</script>
<script type="text/javascript" src="'.(defined('JQUERY_GA_CDN') && strlen(JQUERY_GA_CDN)?JQUERY_GA_CDN:SD_INCLUDE_PATH.'javascript/'.JQUERY_FILENAME).'"></script>
<script type="text/javascript" src="'.SITE_URL.'includes/javascript/jquery-migrate-1.2.1.min.js"></script>'.
(!empty($loadEditor) ? '
<script type="text/javascript" src="'.SD_INCLUDE_PATH.'javascript/markitup/markitup-full.js"></script>
<link rel="stylesheet" type="text/css" href="'.SD_INCLUDE_PATH.'javascript/markitup/skins/markitup/style.css" />
<link rel="stylesheet" type="text/css" href="'.SD_INCLUDE_PATH.'javascript/markitup/sets/bbcode/style.css" />
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function() {
  jQuery("#comment").markItUp(myBbcodeSettings);
});
//]]>
</script>':'').'
</head>
<body>';
} //GetCommentHead

/*
<!--
<style type="text/css">
body,html {
  background: #f0f2f2;
  color: #222222;
  font-family: Verdana, Arial, Helvetica, sans-serif;
  font-size: 14px;
  height: 360px;
  line-height: 18px;
  margin: 0px;
  padding: 8px;
  text-align: left; }
a:link, a:visited { color: #71767e; text-decoration: none; }
a:active { color: #0094c8; }
a:hover { color: #222222; }
div#error_message {
  background: #ffeaef;
  border: 3px solid #ff829f;
  left: 55px;
  margin-bottom: 15px;
  padding: 15px; }
div#success_message {
  background: #eaf4ff;
  border: 3px solid #82c0ff;
  left: 55px;
  margin-bottom: 15px;
  padding: 15px; }
</style>
!-->
*/

// ############################################################################

function ajaxEditComment()
{
  global $DB, $sdlanguage, $cid, $comment_arr;

  echo GetCommentHead().'<h2>'.$sdlanguage['comments_edit'].'</h2>';

  if(!empty($comment_arr) && ($comment_arr['commentid'] > 0))
  {
    global $userinfo;
    echo '
  <form id="comment-form" action="'.SITE_URL.'includes/ajax/sd_ajax_comments.php?do=updatecomment&amp;pluginid='.
    (int)$comment_arr['pluginid'].'&amp;cid='.$comment_arr['commentid'].'&amp;securitytoken='.$userinfo['securitytoken'].'" method="post">
  <textarea id="comment" name="comment" rows="8" cols="80">' . $comment_arr['comment'] . '</textarea><br />
  <input type="submit" value="' . $sdlanguage['post_comment'] . '" />
  <input class="submit" type="button" onclick="parent.jQuery.fn.ceebox.closebox(\'fast\');" value="'.$sdlanguage['common_close'].'" />
  </form>';
  }
  else
  {
    echo '<strong>'.$sdlanguage['err_invalid_operation'].'</strong>';
  }
  echo '</body></html>';

} //ajaxEditComment

// ############################################################################

function ajaxGetComment()
{
  global $DB, $bbcode, $mainsettings, $comment_arr;

  if(!empty($comment_arr) && isset($comment_arr['comment']))
  {
    $tmp = trim($comment_arr['comment']);
    if(!empty($mainsettings['allow_bbcode']) && isset($bbcode) && ($bbcode instanceof BBCode))
    {
      $tmp = $bbcode->Parse($tmp);
    }
    return $tmp;
  }
  return '';

} //ajaxGetComment

// ############################################################################

function ajaxReportCommentForm()
{
  global $DB, $categoryid, $sdlanguage, $userinfo,
         $Comments, $cid, $comment_arr;

  echo GetCommentHead(true).'<h2>'.$sdlanguage['comments_report'].'</h2>'.
       $sdlanguage['comment_report_descr'].'<br />';

  if(empty($comment_arr) || empty($comment_arr['commentid']) || ($comment_arr['commentid'] < 1))
  {
    echo '<strong>'.$sdlanguage['err_invalid_operation'].'</strong>';
  }

  require_once(SD_INCLUDE_PATH.'class_sd_reports.php');

  // Was comment already reported?
  $report = SD_Reports::GetReportedItem($comment_arr['pluginid'],$comment_arr['objectid'],$cid);
  if(!empty($reported['reportid']))
  {
    $this->conf->RedirectPageDefault($sdlanguage['comment_already_reported'], true);
    return false;
  }

  // Prepare template and data for report form
  $captcha = '';
  if(!$Comments->IsAdmin || !empty($userinfo['require_vvc']))
  {
    $captcha = DisplayCaptcha(false,'amihuman');
  }
  $link = '';
  $reasons = SD_Reports::GetReasonsForPluginID($comment_arr['pluginid'], true);

  $rep_config = array(
    'form_action'     => SITE_URL.'includes/ajax/sd_ajax_comments.php?do=report_confirm&amp;pluginid='.
                         (int)$comment_arr['pluginid'].'&amp;cid='.(int)$comment_arr['commentid'].'&amp;securitytoken='.$userinfo['securitytoken'],
    'item_link'       => '',
    'item_title'      => '',
    'form_token_id'   => '',
    'form_token'      => '',#PrintSecureToken(),
    'objectid1'       => $Comments->plugin_id,
    'objectid2'       => $cid,
    'reasons'         => $reasons,
    'hidden'          => array(
        0  => array('name' => 'categoryid', 'value' => $categoryid),
        #1  => array('name' => 'yyy', 'value' => 'val2'),
        ),
     'report_user_message' => $Comments->comment_reports_msg,
    'form'            => array(
      'do_captcha'    => (!$Comments->IsAdmin || !empty($userinfo['require_vvc'])),
      'captcha_elem'  => 'amihuman',
      'captcha_html'  => $captcha,
      'confirm'       => $sdlanguage['confirm_report_comment'],
      'title'         => '',
      'subtitle'      => '',
      'submit'        => strip_alltags($sdlanguage['comment_send_report']),
      'show_close'    => 1,#'<input class="submit" type="button" onclick="parent.jQuery.fn.ceebox.closebox(\'fast\');" value="'.$sdlanguage['common_close'].'" />',
      'html_bottom'   => '</body></html>',
      'html_top'      => '',
    )
  );

  $tmp_smarty = SD_Smarty::getInstance();
  $tmp_smarty->error_reporting = E_ALL & ~E_NOTICE;
  $tmp_smarty->assign('AdminAccess',   $Comments->IsAdmin);
  $tmp_smarty->assign('pluginid',      $comment_arr['pluginid']);
  $tmp_smarty->assign('sdurl',         SITE_URL);
  $tmp_smarty->assign('sdlanguage',    $sdlanguage);
  $tmp_smarty->assign('securitytoken', $userinfo['securitytoken']);
  foreach($rep_config as $key => $val)
  {
    $tmp_smarty->assign($key, $val);
  }

  SD_Smarty::display(0, 'user_report_form.tpl');
  unset($tmp_smarty);

} //ajaxReportCommentForm

// ############################################################################

function ajaxUpdateComment()
{
  global $mainsettings, $userinfo, $pluginid, $cid;

  $comment = trim(GetVar('comment', '', 'string', true, false));
  if(empty($cid) || (strlen($comment) < 2) && ($cid < 1)) return false;

  if(function_exists('DetectXSSinjection') && DetectXSSinjection(unhtmlspecialchars($comment)))
  {
    return false;
  }

  $blacklisted = false;
  //SD343: SFS checking user's email and IP
  $sfs = false;
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
    WatchDog('Comments','<b>Comment rejected (blacklisted): '.htmlentities($userinfo['username']).
             '</b>, IP: </b><span class="ipaddress">'.USERIP.'</span></b><br />'.
             ' for plugin id: '.$pluginid.', comment id: '.$cid,
              WATCHDOG_ERROR);
    return -1;
  }

  global $DB, $Comments, $info;
  //SD350: take into account "approve_comment_edits" usergroup option
  $approved = ($Comments->IsAdmin ||
               !empty($info['usergroup_details']['approve_comment_edits'])) ? 1 : 0;
  $DB->query('UPDATE '.PRGM_TABLE_PREFIX."comments SET comment = '%s', approved = %d".
             ' WHERE commentid = %d',
             $comment, $approved, $cid);
  return true;

} //ajaxUpdateComment


// #####################
// MAIN ACTION SECTION
// #####################

if(in_array($funcid,array('insertcomment','subscribe','unsubscribe')))
{
  require_once(SD_INCLUDE_PATH.'class_userprofile.php');
  SDProfileConfig::init();
  if(empty($Comments->IsAdmin) && ($funcid != 'insertcomment'))
  {
    $Comments->hasPostAccess = $Comments->hasPostAccess &&
                               !empty(SDProfileConfig::$usergroups_config[$userinfo['usergroupid']]['enable_subscriptions']);
  }
}

// Set default return message to be a failure:
$content = $sdlanguage['ajax_operation_failed'];
$doError = true;

if($Comments->hasPostAccess &&
   in_array($funcid,array('approvecomment','deletecomment','disapprovecomment',
                          'getcomment','editcomment','insertcomment','updatecomment',
                          'reportcomment','report_confirm',
                          'subscribe','unsubscribe')))
{
  //SD343: permissions for user edit/delete
  $info = sd_GetForumUserInfo(1, $userinfo['userid']);
  $comment_owner   = !empty($userinfo['userid']) && !empty($comment_arr) &&
                     ($userinfo['userid'] == $comment_arr['userid']);
  $edit_comments   = $Comments->IsAdmin ||
                     ($Comments->hasPostAccess && $comment_owner &&
                      !empty($info['usergroup_details']['edit_own_comments']));
  $delete_comments = $Comments->IsAdmin ||
                     ($Comments->hasPostAccess && $comment_owner &&
                      !empty($info['usergroup_details']['delete_own_comments']));
  $insert_comments = $Comments->IsAdmin || $Comments->hasPostAccess;

  // ********* COMMENT ACTIONS **********
  switch($funcid)
  {
    case 'getcomment':
      if(empty($comment_arr['approved']))
        $content = $sdlanguage['unapproved'];
      else
        $content = ajaxGetComment();
      $doError = false;
      break;
    case 'approvecomment': // admin-only
      if($Comments->IsAdmin)
      {
        $doError = !$Comments->SetApproved($cid, 1);
      }
      break;
    case 'disapprovecomment': // admin-only
      if($Comments->IsAdmin)
      {
        $doError = !$Comments->SetApproved($cid, 0);
      }
      break;
    case 'deletecomment':
      if($Comments->IsAdmin || $delete_comments)
      {
        if($Comments->DeleteComment($cid))
        {
          $doError = false;
          $content = $sdlanguage['comments_deleted'];
          $content = '1';
        }
      }
      break;
    case 'editcomment':
      if($Comments->IsAdmin || $edit_comments)
      {
        ajaxEditComment();
        $doError = false;
      }
      break;
    case 'insertcomment': //SD360
      if($insert_comments)
      {
        $doError = false;
        $res = $Comments->InsertComment();
        if($res===true)
        {
          $content = 'success';
        }
        else
        {
          $content = $res;
        }
      }
      break;
    case 'reportcomment': //SD360
      if($Comments->IsAdmin || $Comments->comment_reports)
      {
        ajaxReportCommentForm();
        $doError = false;
      }
      break;
    case 'report_confirm': //SD360
      if($Comments->IsAdmin || $Comments->comment_reports)
      {
        $doError = false;
        $res = $Comments->DoReportComment($msg);
        $content = GetCommentHead(false).'
          <div><br /><center><div style="text-align:center;width:75%;">';
        if($res)
        {
          $content .= sd_CloseCeebox(2, $msg, false);
        }
        else
        {
          $content .= sd_CloseCeebox(2, $sdlanguage['comments_comment_not_reported'], false);
        }
        $content .=  '
          <input class="submit btn btn-primary" type="button" onclick="parent.jQuery.fn.ceebox.closebox(\'fast\');" value="'.$sdlanguage['common_close'].'" />
          </div></center></div></html></body>';
      }
      break;
    case 'updatecomment':
      if($Comments->IsAdmin || $edit_comments)
      {
        $doError = false;
        $content = GetCommentHead(false).'
          <div><br /><center><div style="text-align:center; width:75%;">';
        $res = ajaxUpdateComment($cid);
        if($res===true)
        {
          $content .= sd_CloseCeebox(2, $sdlanguage['comments_saved'], false);
        }
        else
        if($res===-1)
        {
          $content .= sd_CloseCeebox(2, $sdlanguage['ip_listed_on_blacklist'], false);
        }
        else
        {
          $content .= sd_CloseCeebox(2, $sdlanguage['err_invalid_operation'], false);
        }
        $content .= '
          <input class="submit btn btn-primary" type="button" onclick="parent.jQuery.fn.ceebox.closebox(\'fast\');" value="'.$sdlanguage['common_close'].'" />
          </div></center></div></html></body>';
      }
      break;
    case 'subscribe': //SD342
      include_once(ROOT_PATH.'includes/class_userprofile.php');
      SDProfileConfig::init(11);
      $catid = GetVar('catid', 0, 'whole_number', false, true);
      $oid = GetVar('oid', 0, 'whole_number', false, true);
      if(empty($oid) || empty($pluginid) || ($pluginid > 9999)) continue;
      $sub = new SDSubscription($userinfo['userid'],$pluginid,$oid,'comments',$catid);
      if(!$sub->IsSubscribed())
      {
        if($sub->Subscribe($pluginid,$oid,'comments',$catid))
        {
          $content = 'subscribed';
          $doError = false;
        }
      }
      break;
    case 'unsubscribe': //SD342
      include_once(ROOT_PATH.'includes/class_userprofile.php');
      SDProfileConfig::init(11);
      $catid = GetVar('catid', 0, 'whole_number', false, true);
      $oid = GetVar('oid', 0, 'whole_number', false, true);
      if(empty($oid) || empty($pluginid) || ($pluginid > 9999)) continue;
      $sub = new SDSubscription($userinfo['userid'],$pluginid,$oid,'comments',$catid);
      if($sub->IsSubscribed())
      {
        if($sub->Unsubscribe($pluginid,$oid,'comments'))
        {
          $content = 'unsubscribed';
          $doError = false;
        }
      }
      break;
  }
}
else
{
  $content = $Comments->hasPostAccess ? 'Unknown error!' : GetCommentError(1);
}

// Always provide an output to react upon:
if(($funcid != 'editcomment') && ($funcid != 'reportcomment'))
{
  if(!headers_sent())
  {
    header("Content-Type: text/html; charset=utf-8");
  }
  echo $content;
}
else
if($doError)
{
  echo GetCommentError(0);
}
$DB->close();
