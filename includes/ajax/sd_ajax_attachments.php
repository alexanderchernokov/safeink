<?php
define('IN_PRGM', true);
define('ROOT_PATH', '../../');

$plugin_names = array();
if(!empty($_GET['admin'])) //SD370
{
  define('IN_ADMIN',true);
}
@require(ROOT_PATH . 'includes/init.php');
@require_once(SD_INCLUDE_PATH.'class_sd_attachment.php');

// Check for specific action to return displayable data
$content = '';
$funcid = strtolower(GetVar('do', '', 'string'));
$aid    = Is_Valid_Number(GetVar('aid', 0, 'whole_number'),0,1,99999999);
$pid    = Is_Valid_Number(GetVar('pid', 0, 'whole_number'),0,1,99999999);
$oid    = Is_Valid_Number(GetVar('oid', 0, 'whole_number'),0,1,99999999);
$area   = GetVar('area', '', 'string');

if( empty($funcid) ||
    (!empty($pid) && !isset($plugin_names[$pid])) ||
    (!empty($area) && (sd_strlen_greater($area, 20))) ||
    empty($oid) || empty($aid) ||
    empty($userinfo['userid']) || empty($userinfo['loggedin']) ||
    !CheckFormToken() )
{
  $DB->close();
  die($sdlanguage['common_attachment_denied']);
}

$att = new SD_Attachment($pid, $area);
$attachment_arr = $att->FetchAttachmentEntry($oid, $aid);
if(empty($attachment_arr['attachment_id']) ||
   ($attachment_arr['pluginid'] != $pid))
{
  $DB->close();
  die($sdlanguage['common_attachment_unavailable']);
}

// ############################################################################
// MAIN ACTION SECTION
// ############################################################################

// Support Desk support (v1.7.3+)
if($pid==65)
{
  $HasAccess = false;
  // Check all lots of stuff for security...
  if(($area=='supportdesk') && ($funcid=='deleteattachment'))
  {
    $p65_settings = GetPluginSettings(65);
    // Pre-fetch the departments, for which the user has access to, as global array:
    $p65_IsAdmin  = !empty($userinfo['adminaccess']) ||
                    (!empty($userinfo['pluginadminids']) && in_array('65',$userinfo['pluginadminids']));
    // If not admin, check if user is moderator
    if(!$p65_IsAdmin)
    {
      $p65_ismod = $DB->query_first('SELECT COUNT(*) deptcount FROM {p65_moderators}'.
                                    ' WHERE userid = %d', $userinfo['userid']);
      $p65_ismod = !empty($p65_ismod['deptcount']);
    }
    // *Always* check if message actually exists!
    if($p65_uid = $DB->query_first('SELECT userid FROM {p65_messages}'.
                                   ' WHERE messageid = %d', $oid))
    {
      $p65_uid = $p65_uid['userid'];
    }
    if( $p65_IsAdmin ||
        (!empty($p65_ismod) && !empty($p65_settings['attachment_deletion_moderators'])) ||
        (!empty($p65_uid) && ($p65_uid == $userinfo['userid']) &&
         !empty($p65_settings['attachment_deletion_users'])) )
    {
      $HasAccess = !empty($p65_uid);
    }
  }
  if(empty($HasAccess))
  {
    $DB->close();
    die($sdlanguage['common_attachment_denied']);
  }
}
else
{
  $HasAccess = !empty($userinfo['adminaccess']) ||
               (!empty($userinfo['pluginadminids']) && @in_array($pid, $userinfo['pluginadminids'])) ||
               (!empty($userinfo['pluginmoderateids']) && @in_array($pid, $userinfo['pluginmoderateids']));
}

// Set default return message to be a failure:
$content = $sdlanguage['ajax_operation_failed'];

if(($HasAccess || ($userinfo['userid']==$attachment_arr['userid'])) &&
   ($funcid == 'deleteattachment'))
{
  switch($funcid) {
    // ********* ATTACHMENTS **********
    case 'deleteattachment':
      $att->setPluginID($pid);
      $att->setArea($area);
      $att->setObjectID($oid);
      if($att->DeleteAttachment($aid))
        $content = '1'; // must be exactly this for success!
      else
        $content = $sdlanguage['common_attachment_denied'];
      break;
  }
}
else
{
  $content = $sdlanguage['common_attachment_denied'];
}

// Always provide an output to react upon:
if(!headers_sent())
{
  header("Content-Type: text/html; charset=".SD_CHARSET);
}
print $content;

$DB->close();
exit();
