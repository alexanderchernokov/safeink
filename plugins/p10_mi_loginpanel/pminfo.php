<?php
if(!defined('IN_PRGM')) exit();
if(!function_exists('p10_DisplayPMs'))
{
function p10_DisplayPMs($usersystem, $language=null, $settings=null) //SD342 added 2 params
{
  global $DB, $sdurl, $dbname, $userinfo, $mainsettings, $mainsettings_modrewrite;

  $forumdbname = $usersystem['dbname'];
  $forumname   = $usersystem['name'];
  $forumpath   = $usersystem['folderpath'];
  $tableprefix = $usersystem['tblprefix'];

  if(!isset($language)) $language = GetLanguage(10);
  if(!isset($settings)) $settings = GetPluginSettings(10);

  $showpopups = !defined('IN_ADMIN') && !empty($settings['show_popups']);

  // switch to forum database
  if($dbname != $forumdbname)
  {
    $DB->select_db($forumdbname);
  }
  // Initialize variables
  $pmurl      = '';
  $pmtotal    = 0;
  $pmunread   = 0;
  $pmtitle    = '';
  $pmfromuser = '';
  $pmpopupurl = '';
  $userid     = (int)$userinfo['userid'];

  if($forumname == 'Subdreamer')
  {
    global $UserProfile;
    include_once(SD_INCLUDE_PATH.'class_messaging.php');
    $msg = SDProfileConfig::GetMsgObj(); // must have! initializes objects if need be!
    //SD370: "user_unread_privmsg" was not used before; it's updated whenever
    // as user receives a new PM, so as long as this is 0, do manual recount
    // (i.e. it saves a SQL once a user received first message):
    if(empty($userinfo['profile']['user_unread_privmsg']))
      $pmunread = SDMsg::getMessageCounts($userid, SDMsg::MSG_INBOX | SDMsg::MSG_STATUS_UNREAD);
    else
      $pmunread = $userinfo['profile']['user_unread_privmsg'];
    $userdata = SDProfileConfig::GetUserdata();
    $pmtotal  = isset($userdata['msg_in_count'])?(int)$userdata['msg_in_count']:0;
    $profile_page = SDProfileConfig::$profile_pages['page_viewmessages'];
    $cp_exists = !empty($userinfo['userid']) && (defined('CP_PATH') && strlen(CP_PATH));
    if($cp_exists)
    {
      $cpbase = CP_PATH . (stripos(CP_PATH,'?')===false?'?':'&amp;').
                'profile='.$userid.'#do=';
      $pmurl  = $cpbase . $profile_page;
    }

    SDMsg::setOption('currentpage', 1);
    SDMsg::setOption('pagesize', 1);
    $newpm = SDMsg::getInboxMessages($userid);

    global $pluginids;
    //SD342 avoid popup on page with Login Panel; also avoid 2nd popup if current page
    // was opened because of popup and Login Panel is on Profile page
    if( (defined('IN_ADMIN') || ((!is_array($pluginids) || !in_array(11,$pluginids)) && $showpopups)) &&
        empty($_GET['nopopup']) && !empty($pmunread) && !empty($newpm[0]['userid']) )
    {
      $pmtitle    = isset($newpm[0]['msg_title'])?strip_tags(html_entity_decode($newpm[0]['msg_title'])):'';
      $pmfromuser = isset($newpm[0]['username'])?$newpm[0]['username']:'?';
      if($cp_exists && !empty($userinfo['profile']['user_notify_pm']))
      {
        $pmpopupurl = $cpbase . SDProfileConfig::$profile_pages['page_viewmessages'].
                      '&id=' . $newpm[0]['msg_id'].
                      '&nopopup=1'; //SD342 avoid 2nd popup
      }
    }
  }
  elseif($forumname == 'vBulletin 2')
  {
    // Only allow user to override if they are enabled globally
    if($showpopups)
    {
      // VB lets the user choose whether to show popups
      if($pmpopup = $DB->query_first("SELECT pmpopup FROM `".$tableprefix."user` WHERE userid = %d",$userid))
      {
        $showpopups = (int)$pmpopup;
      }
    }

    if($pmcount = $DB->query_first("SELECT COUNT(privatemessageid) AS pmtotal,
      SUM(IF(messageread = 0 AND folderid = 0, 1, 0)) AS pmunread
      FROM `" . $tableprefix . "privatemessage` AS pm
      WHERE userid = %d", $userid))
    {
      $pmtotal  = (int)$pmcount['pmtotal'];
      $pmunread = (int)$pmcount['pmunread'];
      $pmurl = $forumpath . 'private.php';

      if(!empty($showpopups) && !empty($pmunread) &&
        ($newpm = $DB->query_first("SELECT pm.privatemessageid, title, u.username
          FROM `" . $tableprefix . "privatemessage` AS pm
          LEFT JOIN `" . $tableprefix . "user` AS u ON u.userid = pm.fromuserid
          WHERE (pm.userid = %d) AND (pm.messageread = 0)
          ORDER BY dateline DESC
          LIMIT 1", $userid)))
      {
        $pmtitle    = isset($newpm['title'])?html_entity_decode($newpm['title']):'';
        $pmfromuser = isset($newpm['username'])?$newpm['username']:'';
        $pmpopupurl = $forumpath . 'private.php?action=show&privatemessageid=' . $newpm['privatemessageid'];
      }
    }
  }
  elseif($forumname == 'vBulletin 3')
  {
    // Only allow user to override if they are enabled globally
    if($showpopups)
    {
      // VB lets the user choose whether to show popups
      if($pmpopup = $DB->query_first("SELECT pmpopup FROM `".$tableprefix."user` WHERE userid = %d",$userid))
      {
        $showpopups = (int)$pmpopup;
      }
    }

    if($pmcount = $DB->query_first("SELECT COUNT(pmid) AS pmtotal,
        SUM(IF(messageread = 0 AND folderid = 0, 1, 0)) AS pmunread
        FROM `" . $tableprefix . "pm` AS pm
        WHERE userid = %d", $userid))
    {
      $pmtotal  = (int)$pmcount['pmtotal'];
      $pmunread = (int)$pmcount['pmunread'];
      $pmurl = $forumpath . 'private.php';

      if(!empty($showpopups) && !empty($pmunread) &&
        ($newpm = $DB->query_first("SELECT pm.pmid, title, fromusername
          FROM `" . $tableprefix . "pmtext` AS pmtext
          LEFT JOIN `" . $tableprefix . "pm` AS pm USING(pmtextid)
          WHERE (pm.userid = %d) AND (pm.messageread = 0)
          ORDER BY dateline DESC
          LIMIT 1", $userid)))
      {
        $pmtitle    = isset($newpm['title'])?html_entity_decode($newpm['title']):'';
        $pmfromuser = isset($newpm['fromusername'])?$newpm['fromusername']:'';
        $pmpopupurl = $forumpath . 'private.php?do=showpm&pmid=' . $newpm['pmid'];
      }
    }
  }
  elseif($forumname == 'phpBB2')
  {
    if($pmcount = $DB->query_first("SELECT COUNT(privmsgs_id) AS pmtotal,
         SUM(IF(privmsgs_type = 5 OR privmsgs_type = 1, 1, 0)) AS pmunread
         FROM `" . $tableprefix . "privmsgs`
         WHERE privmsgs_to_userid = %d
         AND (privmsgs_type <> 4) AND (privmsgs_type <> 2)", $userid))
    {
      $pmtotal  = (int)$pmcount['pmtotal'];
      $pmunread = (int)$pmcount['pmunread'];
      $pmurl = $forumpath . 'privmsg.php';

      if(!empty($showpopups) && !empty($pmunread) &&
        ($newpm = $DB->query_first("SELECT pm.privmsgs_id, pm.privmsgs_subject, u.username
            FROM `" . $tableprefix . "privmsgs` AS pm
            LEFT JOIN `" . $tableprefix . "users` As u ON u.user_id = pm.privmsgs_from_userid
            WHERE (pm.privmsgs_to_userid = %d)
            AND ((pm.privmsgs_type = 1) OR (pm.privmsgs_type = 5))
            ORDER BY pm.privmsgs_date DESC
            LIMIT 1", $userid)))
      {
        $pmtitle    = isset($newpm['privmsgs_subject'])?html_entity_decode($newpm['privmsgs_subject']):'';
        $pmfromuser = isset($newpm['username'])?$newpm['username']:'';
        $pmpopupurl = $forumpath . 'privmsg.php?folder=inbox&mode=read&p=' . $newpm['privmsgs_id'];
      }
    }
  }
  elseif($forumname == 'phpBB3')
  {
    $pmtotal = $pmunread = 0;
    if($getmsg = $DB->query("SELECT folder_id, COUNT(msg_id) AS num_messages, SUM(pm_unread) AS num_unread
         FROM `" . $tableprefix . "privmsgs_to`
         WHERE (user_id = %d) AND (folder_id <> '-3')
         GROUP BY folder_id", $userid))
    {
      $pmtotal = $pmunread = array();
      while($row = $DB->fetch_array($getmsg,null,MYSQL_ASSOC))
      {
        $pmtotal[(int)  $row['folder_id']] = $row['num_messages'];
        $pmunread[(int) $row['folder_id']] = $row['num_unread'];
      }
      $pmtotal  = (isset($pmtotal[0])  ? $pmtotal[0]  : 0) + (isset($pmtotal[-2]) ? $pmtotal[-2] : 0);
      $pmunread = (isset($pmunread[0]) ? $pmunread[0] : 0);
    }
    $pmurl = $forumpath . 'ucp.php?i=pm&folder=inbox';

    if(!empty($showpopups) && !empty($pmunread) &&
      ($newpm = $DB->query_first("SELECT pm.msg_id, pm.message_subject, u.username, u.user_notify_pm, pt.folder_id
        FROM `" . $tableprefix . "privmsgs` AS pm
        LEFT JOIN `" . $tableprefix . "privmsgs_to` AS pt ON pt.msg_id = pm.msg_id
        LEFT JOIN `" . $tableprefix . "users` AS u ON u.user_id = pm.author_id
        WHERE (pt.user_id = %d) AND (pt.folder_id = 0) AND (u.user_notify_pm = 1)
        ORDER BY pm.message_time DESC
        LIMIT 1", $userid)))
    {
      $pmtitle    = isset($newpm['message_subject']) ? html_entity_decode($newpm['message_subject']) : '';
      $pmfromuser = isset($newpm['username']) ? $newpm['username'] : '';
      $pmpopupurl = $forumpath . 'ucp.php?i=pm&f=inbox';
    }
  }
  elseif($forumname == 'Invision Power Board 2')
  {
    if($pmcount = $DB->query_first("SELECT COUNT(mt_id) AS pmtotal,
         SUM(IF(mt_user_read = 0, 1, 0)) AS pmunread
         FROM `" . $tableprefix . "message_topics`
         WHERE (mt_vid_folder = 'in') AND (mt_to_id = %d)", $userid))
    {
      $pmtotal  = (int)$pmcount['pmtotal'];
      $pmunread = (int)$pmcount['pmunread'];
      $pmurl = $forumpath . 'index.php?act=Msg&CODE=01';

      if(!empty($showpopups) && !empty($pmunread) &&
        ($newpm = $DB->query_first("SELECT pm.mt_id, pm.mt_title, m.name, m.members_display_name
          FROM `" . $tableprefix . "message_text` AS pmtext
          LEFT JOIN `" . $tableprefix . "message_topics` AS pm ON pm.mt_msg_id = pmtext.msg_id
          LEFT JOIN `" . $tableprefix . "members` AS m ON m.id = pm.mt_from_id
          WHERE (pm.mt_to_id = %d) AND (pm.mt_user_read = 0) AND (mt_vid_folder = 'in')
          ORDER BY pm.mt_date DESC
          LIMIT 1", $userid)))
      {
        $pmtitle    = isset($newpm['mt_title'])?html_entity_decode($newpm['mt_title']):'';
        $pmfromuser = isset($newpm['members_display_name'])?$newpm['members_display_name']:$newpm['name'];
        //$pmfromuser = isset($newpm['name'])?$newpm['name']:'';
        $pmpopupurl = $forumpath . 'index.php?act=Msg&CODE=03&VID=in&MSID='.$newpm['mt_id'].'';
      }
    }
  }
  elseif($forumname == 'Simple Machines Forum 1')
  {
    // check which version of smf is the user running
    $smfversion = $DB->query_first("SELECT value FROM `".$tableprefix."settings` WHERE variable = 'smfVersion'");

    if(substr($smfversion['value'], 0, 3) == '1.1')
    {
      if($pmcount = $DB->query_first("SELECT COUNT(ID_PM) AS pmtotal,
           SUM(IF(is_read = 0, 1, 0)) AS pmunread
           FROM `" . $tableprefix . "pm_recipients`
           WHERE (ID_MEMBER = %d) AND (deleted = 0)", $userid))
      {
        $pmtotal  = (int)$pmcount['pmtotal'];
        $pmunread = (int)$pmcount['pmunread'];
        $pmurl = $forumpath . 'index.php?action=pm';

        if(!empty($showpopups) && !empty($pmunread) &&
          ($newpm = $DB->query_first("SELECT pm.ID_PM, pmtext.subject, pmtext.fromName
            FROM `" . $tableprefix . "personal_messages` AS pmtext
            LEFT JOIN `" . $tableprefix . "pm_recipients` AS pm ON pm.ID_PM = pmtext.ID_PM
            WHERE (pm.ID_MEMBER = %d) AND (pm.deleted = 0) AND (pm.is_read = 0)
            ORDER BY pmtext.msgtime DESC
            LIMIT 1", $userid)))
        {
          $pmtitle    = isset($newpm['subject'])?html_entity_decode($newpm['subject']):'';
          $pmfromuser = isset($newpm['fromName'])?$newpm['fromName']:'';
          $pmpopupurl = $forumpath . 'index.php?action=pm;f=inbox#'.$newpm['ID_PM'];
        }
      }
    }
    else
    {
      if($pmcount = $DB->query_first("SELECT COUNT(ID_PM) AS pmtotal,
           SUM(IF(is_read = 0, 1, 0)) AS pmunread
           FROM `" . $tableprefix . "im_recipients`
           WHERE (ID_MEMBER = %d) AND (deleted = 0)", $userid))
      {
        $pmtotal  = (int)$pmcount['pmtotal'];
        $pmunread = (int)$pmcount['pmunread'];
        $pmurl = $forumpath . 'index.php?action=pm';

        if(!empty($showpopups) && !empty($pmunread) &&
          ($newpm = $DB->query_first("SELECT pm.ID_PM, pmtext.subject, pmtext.fromName
            FROM `" . $tableprefix . "instant_messages` AS pmtext
            LEFT JOIN `" . $tableprefix . "im_recipients` AS pm ON pm.ID_PM = pmtext.ID_PM
            WHERE (pm.ID_MEMBER = %d) AND (pm.deleted = 0) AND (pm.is_read = 0)
            ORDER BY pmtext.msgtime DESC
            LIMIT 1", $userid)))
        {
          $pmtitle    = isset($newpm['subject'])?html_entity_decode($newpm['subject']):'';
          $pmfromuser = isset($newpm['fromName'])?$newpm['fromName']:'';
          $pmpopupurl = $forumpath . 'index.php?action=pm;f=inbox#'.$newpm['ID_PM'];
        }
      }
    }
  }
  elseif($forumname == 'Simple Machines Forum 2')
  {
    if($pmcount = $DB->query_first("SELECT COUNT(id_pm) AS pmtotal,
         SUM(IF(is_read = 0, 1, 0)) AS pmunread
         FROM `" . $tableprefix . "pm_recipients`
         WHERE (id_member = %d) AND (deleted = 0)", $userid))
    {
      $pmtotal  = (int)$pmcount['pmtotal'];
      $pmunread = (int)$pmcount['pmunread'];
      $pmurl = $forumpath . 'index.php?action=pm';

      if(!empty($showpopups) && !empty($pmunread) &&
        ($newpm = $DB->query_first("SELECT pm.id_pm, pmtext.subject, pmtext.from_name
          FROM `" . $tableprefix . "personal_messages` AS pmtext
          LEFT JOIN `" . $tableprefix . "pm_recipients` AS pm ON pm.id_pm = pmtext.id_pm
          WHERE (pm.id_member = %d) AND (pm.deleted = 0) AND (pm.is_read = 0)
          ORDER BY pmtext.msgtime DESC LIMIT 1", $userid)))
      {
        $pmtitle    = isset($newpm['subject'])?html_entity_decode($newpm['subject']):'';
        $pmfromuser = isset($newpm['from_name'])?$newpm['from_name']:'';
        $pmpopupurl = $forumpath . 'index.php?action=pm;f=inbox#'.$newpm['id_pm'];
      }
    }
  }
  elseif($forumname == 'XenForo 1')
  {
    global $SD_XenForo_user;
    $pmtotal  = $SD_XenForo_user['message_count'];
    //alerts_unread
    $pmunread = $SD_XenForo_user['conversations_unread'];
    $pmurl = ForumLink(5, $SD_XenForo_user['user_id']);
  }
  elseif($forumname == 'MyBB') //SD370
  {
    if($pmcount = $DB->query_first("SELECT totalpms pmtotal, unreadpms pmunread
         FROM `" . $tableprefix . "users`
         WHERE uid = %d", $userid))
    {
      $pmtotal  = (int)$pmcount['pmtotal'];
      $pmunread = (int)$pmcount['pmunread'];
      $pmurl = $forumpath . 'private.php';

      if(!empty($showpopups) && !empty($pmunread) &&
        ($newpm = $DB->query_first("SELECT pm.pmid, pm.subject, pm.fromid, u.username
          FROM `".$tableprefix."privatemessages` pm
          INNER JOIN `".$tableprefix."users` u ON u.uid = pm.fromid
          WHERE (pm.uid = %d) AND (pm.deletetime = 0) AND (pm.readtime = 0)
          ORDER BY pm.dateline DESC LIMIT 1", $userid)))
      {
        $pmtitle    = isset($newpm['subject'])?html_entity_decode($newpm['subject']):'';
        $pmfromuser = isset($newpm['username'])?$newpm['username']:'';
        $pmpopupurl = $forumpath . 'private.php?action=read&pmid='.$newpm['pmid'];
      }
    }
  }
  elseif($forumname == 'punBB') //SD370
  {
    // no messaging built-in!
  }

  if(!empty($pmurl))
  {
    //SD370: output only unread messages
    if(defined('IN_ADMIN'))
    {
      if(!empty($pmunread) && strlen($pmtitle))
	  {
      	return array('pmurl'	=> $pmurl,
	  				'pmunread'	=>	$pmunread
					);
	  }
    }
    else
    {
      echo '<a href="'.$pmurl.'">'.$language['private_messages'].'</a> '.
      (empty($pmunread)?'0':('<strong>'.$pmunread.'</strong>')).
      '&nbsp;'.trim($language['unread_total']).
      '&nbsp;'.(empty($pmtotal)?'0':$pmtotal);
    }
  }

  if(!defined('IN_ADMIN') && !empty($pmpopupurl) && strlen(trim($pmpopupurl)))
  {
    if(!empty($mainsettings_modrewrite) && (substr($pmpopupurl,0,1) != '/') && (substr($pmpopupurl,0,4) != 'http'))
    {
      //2.4.4: avoid JS errors, see:
      // http://www.subdreamer.com/forum/project.php?issueid=24
      $pmpopupurl = '/' . $pmpopupurl;
    }
    echo '
<script type="text/javascript">
//<![CDATA[
function p10_NotifyMsg() {
  if (confirm("'.addslashes($language['new_priv_msg']).
    '\r\n\r\n'.addslashes($language['sender']).' '.
    $pmfromuser.'\r\n'.addslashes($language['title']).
    ' \''.addslashes($pmtitle).'\'\r\n\r\n'.
    addslashes($language['click_ok']).'")) {
    if (confirm("'.addslashes($language['open_msg']).'")) {
      var winobj = window.open("'.$pmpopupurl.'", "msgwin", "menubar=yes,scrollbars=yes,toolbar=yes,location=yes,directories=yes,resizable=yes,top=50,left=50");
      if (winobj == null) { alert("'.addslashes($language['popup_alert']).'"); }
    } else { window.location = "'.$pmpopupurl.'"; }
  }
}
if (typeof jQuery !== "undefined") {
jQuery(document).ready(function() {
  jQuery(document).ready(function() { p10_NotifyMsg(); });
})
}
else window.onLoad = p10_NotifyMsg();
//]]>
</script>';
  }

  if($DB->database != $dbname) $DB->select_db($dbname);

} //p10_DisplayPMs
} //DO NOT REMOVE