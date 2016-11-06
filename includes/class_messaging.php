<?php
// ############################################################################
// PRIVATE MESSAGING / DISCUSSIONS
// ############################################################################
if(!defined('IN_PRGM')) exit();

defined('SD_MSG_ERR_INVALID_RECIPIENT') || define('SD_MSG_ERR_INVALID_RECIPIENT', 2);
defined('SD_MSG_ERR_TITLE_MISSING') || define('SD_MSG_ERR_TITLE_MISSING', 4);
defined('SD_MSG_ERR_BODY_EMPTY') || define('SD_MSG_ERR_BODY_EMPTY', 8);

class SDMsg
{
  const   TBL_MASTER          = 'msg_master';
  const   TBL_MSG             = 'msg_messages';
  const   TBL_TEXT            = 'msg_text';
  const   TBL_USER            = 'msg_user';
  const   TBL_USERS_DATA      = 'users_data'; //SD370

  const   DISCUSSIONS_USER_ACTIVE  = 'active';
  const   DISCUSSIONS_USER_DELETED = 'deleted';
  const   DISCUSSIONS_USER_IGNORE  = 'ignore';

  const   MSG_ALL             = 1;
  const   MSG_INBOX           = 2;
  const   MSG_OUTBOX          = 4;
  const   MSG_DISCUSSIONS     = 8;
  const   MSG_STATUS_READ     = 16;
  const   MSG_STATUS_UNREAD   = 32;
  const   MSG_DISCUSSIONS_STARTED = 64;
  const   MSG_DISCUSSIONS_PARTAKE = 128;

  const   ERR_ATTACHMENT        = 8;

  private static $_usersystemid = 0;
  private static $_msg_options  = array();

  private $_cache_obj           = null;
  private $_caching             = false;

  private $_msg_recipient_id    = '';
  private $_msg_recipient_name  = '';
  private $_msg_recipients      = array();
  private $_msg_sender_id       = 0;
  private $_msg_sender_name     = '';
  private $_msg_text            = '';
  private $_msg_title           = '';
  private $_master_id           = 0;
  private $_attachments         = array();

  // The following email settings have to be set if the recipient
  // is to be notified by email:
  public  static $email_address     = '';
  public  static $email_subject     = '';
  public  static $email_body        = '';
  public  static $email_include_msg = false;
  public  static $email_msg_bbcode  = false;
  public  static $profile_page_lbl  = 'user profile page'; //SD344

  public  $errorcode            = 0;
  public  $errortext            = '';

  public function __construct($user_system_id, $cache_obj=null)
  {
    if(is_object($cache_obj) && ($cache_obj instanceof SDCache))
    {
      $this->_cache_obj = $cache_obj;
      $this->_caching = $this->_cache_obj->IsActive();
    }
    $this->clear_message();
    self::$_usersystemid = (int)$user_system_id;
  }


  private static function PrepareIDsList($list)
  {
    global $DB, $database;

    if(!isset($list)) return false;

    // Treat "$list" either as an array of integers or as a single integer
    $ids = array();
    if(is_array($list))
    {
      foreach($list as $id)
      {
        if(!empty($id) && is_numeric($id) && ($id > 0))
        {
          $ids[] = (int)$id;
        }
      }
      $ids = array_unique($ids);
    }
    else
    if(is_numeric($list) && ((int)$list > 0))
    {
      $ids[] = (int)$list;
    }

    return $ids;

  } //PrepareIDsList


  public function clear_message()
  {
    self::$_msg_options = array();
    $this->_msg_sender_id = 0;
    $this->_msg_sender_name = '';
    $this->_msg_title = '';
    $this->_master_id = 0;
    $this->_msg_recipient_id = 0;
    $this->_msg_recipient_name = '';
    $this->_msg_recipients = array();
    $this->_msg_text = '';
  }


  public static function checkUserCanReplyDiscussion($userid,$master_id,$msg_id=0)
  {
    if(empty($master_id) || !is_numeric($master_id) || ($master_id < 1) ||
       empty($userid)    || !is_numeric($userid)    || ($userid < 1) ||
       (!empty($msg_id)  && !is_numeric($msg_id)    && ($msg_id < 1))
    ) return false;

    global $DB, $database;

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);

    $result = false;
    // First check if user is actually an existing participant for given discussion:
    if($row = $DB->query_first('SELECT 1 FROM {'.self::TBL_USER.'} WHERE master_id = %d AND userid = %d',
                               $master_id, $userid))
    {
      // Optionally check if given message actually exists for discussion,
      // so that user can actually submit a reply to it:
      if($msg_id)
      {
        if($row = $DB->query_first('SELECT 1 FROM {'.self::TBL_MSG.'}
                                    WHERE msg_id = %d AND usersystemid = %d AND master_id = %d',
                                    $msg_id, self::$_usersystemid, $master_id))
        {
          $result = true;
        }
      }
      else
      {
        $result = true;
      }
    }
    if($DB->database != $prevDB) $DB->select_db($prevDB);

    return $result;

  } //checkUserCanReplyDiscussion


  public static function getDiscussionUsers($master_id,$exclude_id=0,$active_only=true)
  {
    if(empty($master_id) || !is_numeric($master_id) || ($master_id < 1)) return false;

    global $DB, $database;

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);

    $result = array();
    // First check if user is actually an existing participant for given discussion:
    if($getusers = $DB->query('SELECT mu.userid, mu.username, ud.user_screen_name, ud.first_name, ud.last_name, ud.user_pm_email
                               FROM {'.self::TBL_USER.'} mu
                               LEFT JOIN {users_data} ud ON ud.userid = mu.userid
                               WHERE mu.master_id = %d'.
                               (empty($exclude_id)?'':' AND mu.userid <> '.(int)$exclude_id).
                               (empty($active_only)?'':" AND mu.status = 'active'"),
                               $master_id))
    {
      while($row = $DB->fetch_array($getusers,null,MYSQL_ASSOC))
      {
        $result[$row['userid']] = $row;
      }
    }
    if($DB->database != $prevDB) $DB->select_db($prevDB);

    return empty($result)?false:(array)$result;

  } //getDiscussionUsers


  public static function getDiscussionTitle($master_id)
  {
    if(empty($master_id) || !is_numeric($master_id) || ($master_id < 1)) return false;

    global $DB, $database;

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);

    $result = '';
    $DB->result_type = MYSQL_ASSOC;
    if($row = $DB->query_first('SELECT master_id,master_title FROM {'.self::TBL_MASTER.'} WHERE master_id = %d', $master_id))
    {
      $result = !empty($row['master_id']) ? $row['master_title'] : '';
    }
    if($DB->database != $prevDB) $DB->select_db($prevDB);

    return $result;
  } //getDiscussionTitle


  public function setSender($userid)
  {
    $this->_msg_sender_id = 0;
    $this->_msg_sender_name = '';
    if(empty($userid) || !is_numeric($userid) || ($userid < 1)) return false;

    global $DB, $database;

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);

    $result = false;
    if($row = sd_GetUserrow($userid, array('user_screen_name')))
    {
      $this->_msg_sender_id = (int)$userid;
      $this->_msg_sender_name = !empty($row['user_screen_name']) ? $row['user_screen_name'] : $row['username'];
      $result = true;
    }

    if($DB->database != $prevDB) $DB->select_db($prevDB);

    return $result;
  }


  public function setRecipient($recipientid)
  {
    $this->_msg_recipient_id = 0;
    $this->_msg_recipient_name = '';
    if(empty($recipientid) || !is_numeric($recipientid) || ($recipientid < 1)) return false;

    global $DB, $database;

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);

    $result = false;
    if($row = sd_GetUserrow($recipientid, array('user_screen_name')))
    {
      $this->_msg_recipient_id = (int)$recipientid;
      $this->_msg_recipient_name = !empty($row['user_screen_name']) ? $row['user_screen_name'] : $row['username'];
      $result = true;
    }

    if($DB->database != $prevDB) $DB->select_db($prevDB);

    return $result;
  }


  public static function getOption($option)
  {
    if(!isset($option) || !strlen($option)) return false;
    return (isset(self::$_msg_options[$option]) ? self::$_msg_options[$option] : false);
  }


  public static function setOption($option, $value)
  {
    if(!isset($option)) return false;
    if(!isset($value))
    {
      unset(self::$_msg_options[$option]);
    }
    else
    {
      self::$_msg_options[$option] = $value;
    }
    return true;
  }


  public function setDiscussionID($master_id)
  {
    $this->_master_id = 0;
    if(empty($master_id) || !is_numeric($master_id)) return false;
    $this->_master_id = $master_id;
    return true;
  }


  public function setTitle($title)
  {
    if(!is_string($title)) return false;
    $this->_msg_title = $title;
    return true;
  }


  public function setMessageText($text)
  {
    if(!isset($text) || !is_string($text)) return false;
    $this->_msg_text = $text;
    return true;
  }


  public function addAttachment($file_var)
  {
    if(empty($file_var) || !is_array($file_var) || empty($file_var['size']) ||
       empty($file_var['name']) || empty($file_var['tmp_name']) ||
       !empty($file_var['error'])) return false;
    $this->_attachments[] = $file_var;
    return true;
  }


  public function addRecipients($recipients, $bcc=false) //SD343: added bcc
  {
    if(!is_array($recipients) || empty($recipients)) return false;

    global $DB, $database;

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);

    foreach($recipients as $rid)
    {
      if(!empty($rid) && is_numeric($rid) && ($rid > 0))
      if(!isset($this->_msg_recipients[$rid]))
      {
        if($row = sd_GetUserrow($rid))
        {
          //SD343: store also BCC flag
          $this->_msg_recipients[$rid] = array('username'=>$row['username'],'bcc'=>!empty($bcc));
        }
      }
    }
    if($DB->database != $prevDB) $DB->select_db($prevDB);

    return true;
  }


  public static function addUserToDiscussion($userid, $master_id, $params=null)
  {
    global $DB, $database;

    if(empty($userid)    || !is_numeric($userid)    || ($userid < 1) ||
       empty($master_id) || !is_numeric($master_id) || ($master_id < 1))
    {
      return false;
    }

    $prevDB = $DB->database;

    // Check if user and master-user row already exist
    if($row = sd_GetUserrow($userid))
    {
      $row = GetUserInfo($row);
      if($DB->database != $database['name']) $DB->select_db($database['name']);
      // Check, if usergroup has messaging enabled/allowed within SD itself
      if($data = $DB->query_first('SELECT msg_enabled FROM {usergroups_config} WHERE usergroupid = %d',$row['usergroupid']))
      {
        $row['msg_enabled'] = empty($data['msg_enabled']) ? 0 : 1;
      }
      // Check if master actually exists and if user is already participant
      if($data = $DB->query_first('SELECT m.master_id, u.userid
                                   FROM {'.self::TBL_MASTER.'} m
                                   LEFT JOIN {'.self::TBL_USER.'} u ON (u.master_id = m.master_id AND u.userid = %d)
                                   WHERE m.usersystemid = %d AND m.master_id = %d',
                                   $userid, self::$_usersystemid, $master_id))
      {
        if(!empty($data['master_id']) && !empty($data['userid'])) $result = true; //already participant
      }
      else
      {
        $result = false; // Invalid master
      }
    }
    else
    {
      $result = false; // Invalid user
    }

    if(empty($row) || (isset($result) && !$result))
    {
      if($DB->database != $prevDB) $DB->select_db($prevDB);
      return $result;
    }

    // Is private messaging allowed for usergroup AND does user have p/m enabled?
    if( (isset($row['profile']['user_allow_pm']) && empty($row['profile']['user_allow_pm'])) ||
        (isset($row['msg_enabled']) && empty($row['msg_enabled'])) )
    {
      $result = $row['username'];
    }

    if(!isset($result))
    {
      if($DB->database != $database['name']) $DB->select_db($database['name']);
      $DB->query('INSERT INTO {'.self::TBL_USER."}
                  (`master_id`, `userid`, `username`, `last_read`, `is_moderator`, `allow_invites`, `status`)
                  VALUES (%d, %d, '%s', 0, %d, %d, '%s')",
                  $master_id, $userid, $DB->escape_string($row['username']),
                  (empty($params['is_moderator'])?0:1),
                  (empty($params['allow_invites'])?0:1),
                  self::DISCUSSIONS_USER_ACTIVE);
      $result = true;
    }

    if($DB->database != $prevDB) $DB->select_db($prevDB);

    return $result;

  } //addUserToDiscussion


  public static function getAttachments($msg_id, $sender_id=0)
  {
    if(!class_exists('SD_Attachment'))
    @include_once(SD_INCLUDE_PATH.'class_sd_attachment.php');

    $att = new SD_Attachment(11,'messaging');
    $att->setObjectID($msg_id);
    $att->setUserID($sender_id);

    return $att->getAttachmentsListHTML(false,true);

  } //getAttachments


  public static function updateUserInboxCount($userid=0)
  {
    global $DB, $database;

    if(empty($userid) || ($userid < 1)) return false;

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);

    //SD370: re-calc unread inbox and pass to users_data row:
    $unread = SDMsg::getMessageCounts($userid, SDMsg::MSG_INBOX | SDMsg::MSG_STATUS_UNREAD);

    $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'users_data'.
               ' SET user_unread_privmsg = %d, msg_in_count = (SELECT COUNT(msg_id)'.
               ' FROM '.PRGM_TABLE_PREFIX.'msg_messages mm WHERE mm.master_id = 0'.
               ' AND mm.recipient_id = '.PRGM_TABLE_PREFIX.'users_data.userid'.
               ' AND mm.usersystemid = '.PRGM_TABLE_PREFIX.'users_data.usersystemid'.
               ' AND mm.outbox_copy = 0)'.
               ' WHERE usersystemid = %d and userid = %d',
               $unread, self::$_usersystemid, $userid);
    if($DB->database != $prevDB) $DB->select_db($prevDB);

    return true;

  } //updateUserInboxCount


  public static function updateUserOutboxCount($userid=0)
  {
    global $DB, $database;

    if(empty($userid) || ($userid < 1)) return false;

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'users_data SET msg_out_count = (SELECT COUNT(msg_id) FROM '.PRGM_TABLE_PREFIX.
               'msg_messages mm WHERE mm.master_id = 0 AND mm.userid = '.PRGM_TABLE_PREFIX.
               'users_data.userid AND mm.outbox_copy = 1 AND mm.usersystemid = '.PRGM_TABLE_PREFIX.
               'users_data.usersystemid) WHERE usersystemid = %d and userid = %d',
               self::$_usersystemid, $userid);
    if($DB->database != $prevDB) $DB->select_db($prevDB);

    return true;

  } //updateUserInboxCount


  public static function userIsDiscussionMaintainer($userid=0)
  {
    global $DB, $database;

    if(empty($userid) || ($userid < 1)) return false;

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);
    $count = $DB->query_first('SELECT COUNT(*) count_d FROM '.PRGM_TABLE_PREFIX.self::TBL_MASTER.' m
                               WHERE m.usersystemid = %d
                               AND (m.starter_id = %d OR EXISTS(
                                 SELECT 1 FROM '.PRGM_TABLE_PREFIX.self::TBL_USER.' u
                                 WHERE u.userid = %d AND u.master_id = m.master_id
                                 AND u.is_moderator = 1))',
                              self::$_usersystemid, $userid, $userid);
    if($DB->database != $prevDB) $DB->select_db($prevDB);

    return !empty($count['count_d']);

  } //userIsDiscussionMaintainer


  public static function userIsDiscussionOwner($userid,$master_id)
  {
    global $DB, $database;

    if(empty($userid) || ($userid < 1) || empty($master_id) || ($master_id < 1))
      return false;

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);
    if($result = $DB->query_first('SELECT 1 is_owner FROM '.PRGM_TABLE_PREFIX.self::TBL_MASTER.' m
                                  WHERE m.master_id = %d AND m.usersystemid = %d AND m.starter_id = %d',
                                  $master_id, self::$_usersystemid, $userid))
    {
      $result = !empty($result['is_owner']);
    }
    if($DB->database != $prevDB) $DB->select_db($prevDB);

    return !empty($result);

  } //userIsDiscussionOwner


  public function save()
  {
    global $DB, $database, $mainsettings_websitetitle_original, $mainsettings_default_email_format;

    $this->errorcode = 0;
    $this->errortext = '';

    $delete   = $this->getOption('delete');
    $reply_id = (int)$this->getOption('reply_id');
    if(!empty($delete) && !empty($reply_id))
    {
      self::DeletePrivateMessages($this->_msg_sender_id, $reply_id);
    }

    $isprivate = (empty(self::$_msg_options['is_private']) || !empty($this->_master_id) ? 0 : 1);
    $master_id = $isprivate ? 0 : (int)$this->_master_id;

    if(empty($this->_master_id) && empty($this->_msg_recipient_id) && empty($this->_msg_recipients))
    {
      $this->errorcode = SD_MSG_ERR_INVALID_RECIPIENT;
      return false;
    }
    if(empty($reply_id) && empty($master_id) && (strlen($this->_msg_title) < 2))
    {
      $this->errorcode = SD_MSG_ERR_TITLE_MISSING;
      return false;
    }
    if(strlen($this->_msg_text) < 2)
    {
      $this->errorcode = SD_MSG_ERR_BODY_EMPTY;
      return false;
    }
    if((strlen($this->_msg_title) < 2) && (strlen($this->_msg_text)<2))
    {
      return true;
    }

    // Add "single" recipient to the top of the recipients list
    if(empty($this->_msg_recipient_id))
    {
      if(!empty($this->_msg_recipients))
      {
        if(is_array($this->_msg_recipients))
        {
          $keys = array_keys($this->_msg_recipients); //avoids strict notice
          $this->_msg_recipient_id = array_shift($keys);
          unset($keys);
        }
        else
          $this->_msg_recipient_id = (int)$this->_msg_recipients;
      }
    }
    else
    {
      if(empty($this->_msg_recipients))
      {
        $this->_msg_recipients = array($this->_msg_recipient_id => $this->_msg_recipient_name);
      }
      else
      {
        $this->_msg_recipients[$this->_msg_recipient_id] = $this->_msg_recipient_name;
      }
    }

    if($master_id && empty($this->_msg_recipients))
    {
      $this->_msg_recipients = array(0=>''); //Set a dummy here
    }
    $master_created = false;
    $msg_id = 0;
    $outbox = 0;
    $save_copy = $this->getOption('save_copy');
    $read_notify = $this->getOption('read_notify');
    $allow_invites = (empty(self::$_msg_options['allow_invites'])?0:1);
    $attachments_extensions = (empty(self::$_msg_options['attachments_extensions'])?'':(string)self::$_msg_options['attachments_extensions']);
    $attachments_max_size   = (empty(self::$_msg_options['attachments_max_size'])?0:(int)self::$_msg_options['attachments_max_size']);

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);

    // Save message body only once
    $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX.self::TBL_TEXT.
              "(`msg_text_id`, `msg_text`) VALUES (NULL, '%s')",
              //SD360: escape msg since it can have quotes in it
              $DB->escape_string($this->_msg_text));
    $text_id = $DB->insert_id();

    // Process attachments and link them to the message text id "$text_id":
    if(!empty($this->_attachments))
    {
      include_once(SD_INCLUDE_PATH.'class_sd_attachment.php');
      $att = new SD_Attachment(11,'messaging');
      //$att->setStorageBasePath('attachments/');
      $att->setUserid($this->_msg_sender_id);
      $att->setUsername($this->_msg_sender_name);
      $att->setObjectID($text_id);
      $att->setValidExtensions($attachments_extensions);
      $att->setMaxFilesizeKB($attachments_max_size);

      foreach($this->_attachments as $file)
      {
        $result = $att->UploadAttachment($file);
        if(empty($result['id']))
        {
          $this->errorcode = self::ERR_ATTACHMENT;
          $this->errortext = $result['error'];
          if($text_id)
          {
            $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.self::TBL_TEXT.
                       ' WHERE msg_text_id = %d',$text_id);
          }
          if($DB->database != $prevDB) $DB->select_db($prevDB);
          unset($att);
          return false;
        }
      }
      unset($att);
    }

    // ONLY if it is an open discussion do create a master row.
    // Otherwise (=private) each recipient will receive an individual copy of the message.
    // The sending user will receive 1 or 2 copies: one as an "outbox" copy and one
    // extra - if sent to self
    $users_loaded = false;
    if(!$isprivate || $master_id)
    {
      if($master_id)
      {
        // A new message is posted to an existing discussion. Normally the recipients
        // list is empty here (or with "0" entry), so add all recipients to list:
        // This allows to trigger the below loop to send notifications
        if(empty($this->_msg_recipients) || (count($this->_msg_recipients)==1) && empty($this->_msg_recipients[0]))
        {
          //SD360: if sender is the only active discussion participant, sender must be added
          // to have the message entry created
          $this->_msg_recipients = self::getDiscussionUsers($master_id, $this->_msg_sender_id);
          if(false === ($users_loaded = ($this->_msg_recipients !== false)))
          {
            $row = sd_GetUserrow($this->_msg_sender_id, array('first_name','last_name','user_pm_email','user_screen_name'));
            $this->_msg_recipients = array($this->_msg_sender_id => $row);
            $users_loaded = true;
          }
        }
      }
      else
      {
        // If open discussion, create a single master row:
        $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX.self::TBL_MASTER."
          (`master_id`, `usersystemid`, `starter_id`, `starter_username`, `recipient_id`,
           `master_title`, `master_date`, `msg_count`, `msg_user_count`, `first_msg_id`, `first_text_id`,
           `last_msg_id`, `last_text_id`, `last_msg_date`,
           `last_msg_username`, `views`, `is_private`, `allow_invites`,
           `approved`, `access_view`, `access_post`, `access_moderate`)
          VALUES
          (NULL, %d, %d, '%s', 0, '%s',
           %d, 1, %d, 0, %d,
           0, %d, %d,
           '%s', 0, %d, %d,
           1, '', '', '')",
           self::$_usersystemid, $this->_msg_sender_id, $DB->escape_string($this->_msg_sender_name),
           $this->_msg_title, TIME_NOW, count($this->_msg_recipients), $text_id,
           $text_id, TIME_NOW,
           $DB->escape_string($this->_msg_sender_name), 0, 1);
        $master_id = $DB->insert_id();
        $master_created = true;

        // Add discussion starter to the discussion-users table
        // Only the discussion starter receives initial permissions to moderate and invite
        self::addUserToDiscussion($this->_msg_sender_id, $master_id,
                                  array('is_moderator' => 1, 'allow_invites' => 1));
      }
    }

    //SD343: $targetinfo now is an array(username, bcc)!
    foreach($this->_msg_recipients as $targetid => $targetinfo)
    {
      // Add each recipient to the discussion-users table.
      // Only the discussion starter receives initial permissions to moderate and invite
      if(($targetid > 0))
      {
        if(!$users_loaded && ($targetid>0) && !$isprivate)
        {
          $tmp = self::addUserToDiscussion($targetid, $master_id);
          if($tmp !== true)
          {
            // Skip recipients w/o valid or disabled messaging
            $this->errorcode = SD_MSG_ERR_INVALID_RECIPIENT;
            $this->errortext = ($tmp===false) ? '' : $tmp;
            if($DB->database != $prevDB) $DB->select_db($prevDB);
            continue;
          }
        }

        // Do not reset flag for new messages for sender
        if(!$users_loaded && ($targetid != $this->_msg_sender_id))
        {
          // Make sure, that recipient has a corresponding "users_data" row
          if(!$row = sd_GetUserrow($targetid, array('first_name','last_name','user_pm_email','user_screen_name')))
          {
            $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX.'users_data (usersystemid, userid, user_new_privmsg, user_last_privmsg)
                        VALUES (%d, %d, 1, %d)',
                        self::$_usersystemid, $targetid, TIME_NOW);
            $row = sd_GetUserrow($targetid, array('first_name','last_name','user_pm_email','user_screen_name'));
          }

          // Send email notification - if enabled in usergroup
          if($isprivate && !empty($row['user_pm_email']) &&
             ($data = $DB->query_first('SELECT msg_notification FROM {usergroups_config} WHERE usergroupid = %d',$row['usergroupid'])))
          {
            if(!empty($data['msg_notification']))
            {
              if(isset($row['user_screen_name']) && strlen($row['user_screen_name']))
              {
                $row['username'] = $row['user_screen_name'];
              }
              // If user has no first/last name, set the last name to the username
              if(empty($row['first_name']) && empty($row['last_name']))
              {
                $row['first_name'] = '';
                $row['last_name'] = $row['username'];
              }
              $sendername  = '';
              $senderemail = self::$email_address;
              $subject     = self::$email_subject; // e.g. 'Private Message Notification'
              $subject     = str_replace(array('[sitename]',
                                               '[siteurl]',
                                               '[firstname]',
                                               '[lastname]',
                                               '[date]',
                                               '[username]',
                                               '[sendername]'),
                                         array($mainsettings_websitetitle_original,
                                               SITE_URL,
                                               $row['first_name'],
                                               $row['last_name'],
                                               DisplayDate(TIME_NOW, '', true),
                                               $row['username'],
                                               $this->_msg_sender_name), $subject);

              /*
              SD344:
              + new "[profilepage]" macro for recipient's profile page
              * notifications now always HTML in order to correctly cover BBCode
                and links (e.g. new link to profile page etc)
              */
              $email = self::$email_body; // e.g. 'You received a private message ...'
              // Replace "macros" within message body with live values:
              $email = str_replace('[sitename]', $mainsettings_websitetitle_original, $email); //SD342
              $email = str_replace('[siteurl]', SITE_URL, $email);
              $email = str_replace('[firstname]', (strlen($row['first_name'])?$row['first_name']:$row['username']), $email);
              $email = str_replace('[lastname]', $row['last_name'], $email);
              $email = str_replace('[date]', DisplayDate(TIME_NOW, '', true), $email);
              $email = str_replace('[username]', $row['username'], $email);
              $email = str_replace('[sendername]', $this->_msg_sender_name, $email);
              $email = preg_replace("/(\r\n|\n\r|\r|\n)+/", '<br>', trim($email)); //SD370

              $this->_msg_text = sd_unhtmlspecialchars($this->_msg_text); //SD370
              if(self::$email_msg_bbcode)
              {
                global $bbcode;
                if(isset($bbcode) && is_object($bbcode))
                {
                  $bbcode->SetURLPattern('<a href="{$url/h}" target="_blank">{$text/h}</a>');
                  $this->_msg_text = $bbcode->Parse($this->_msg_text);
                }
              }

              $email = str_replace('[message]', $this->_msg_text, $email);
              $email = str_replace('[messagetitle]', $this->_msg_title, $email);
              //SD344: added "[profilepage]"
              $profilepagelink = '';
              if(function_exists('ForumLink'))
              {
                $profilepagelink = '<a href="'.ForumLink(2, $targetid).'">'.self::$profile_page_lbl.'</a>';
              }
              $email = str_replace('[profilepage]', $profilepagelink, $email); //SD344

              SendEmail($row['email'],$subject,$email,$sendername,$senderemail,null,null,true);
            }
          }

          // Make sure the user does have a "users_data" row, otherwise create it
          if(empty($row))
          {
            $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX.self::TBL_USERS_DATA.'
                        (usersystemid, userid, user_new_privmsg, user_last_privmsg, user_unread_privmsg)
                        VALUES (%d, %d, 1, %d, 1)',
                        self::$_usersystemid, $targetid, TIME_NOW);
          }
          else
          {
            $DB->query('UPDATE '.PRGM_TABLE_PREFIX.self::TBL_USERS_DATA.'
                        SET user_new_privmsg = 1, user_last_privmsg = %d
                        WHERE usersystemid = %d AND userid = %d',
                        TIME_NOW, self::$_usersystemid, $targetid);
          }
        }
        //SD343: store main recipient to be included in BCC messages
      }

      // Create a copy of the message for each recipient now.
      // If sender sends message to self, there have to be two separate messages
      // to be inserted, one each for inbox and outbox; otherwise only one for inbox:
      if($isprivate || !$msg_id)
      {
        $outbox = $isprivate && !empty($save_copy) ? 1 : 0;
        while($outbox >= 0)
        {
          $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX.self::TBL_MSG."
           (`msg_id`, `usersystemid`, `userid`, `username`,
            `master_id`, `msg_title`, `msg_text_id`, `recipient_id`, `recipient_name`,
            `msg_date`, `msg_read`, `private`, `outbox_copy`, `ip_address`,
            `is_reply_to`, `msg_read_notify`, `is_bcc`, `org_recipientid`)
           VALUES
           (NULL, %d, %d, '%s', %d,
            '%s', %d, %d, '%s', %d,
            0, %d, %d, '%s', %d, %d,
            %d, %d)",
           self::$_usersystemid, $this->_msg_sender_id, $DB->escape_string($this->_msg_sender_name),
           $master_id, $this->_msg_title, $text_id, $targetid,
           (isset($targetinfo['username'])?$DB->escape_string($targetinfo['username']):''),
           TIME_NOW, $isprivate, $outbox, $DB->escape_string(USERIP),
           (empty($reply_id)?0:(int)$reply_id), (!$save_copy || $users_loaded?$read_notify:0),
           (empty($targetinfo['bcc'])?0:1), (!$outbox && empty($targetinfo['bcc'])?0:$this->_msg_recipient_id)
          );
          $msg_id = $DB->insert_id();
          $save_copy = 0;
          $outbox--;
        } //while
      }

      // Update inbox-counter for recipient:
      self::updateUserInboxCount($targetid);

    } //foreach

    // Update outbox-counter for sender:
    self::updateUserOutboxCount($this->_msg_sender_id);

    // Use only first message from sender as the entry for updating the master's values
    if(!$isprivate && $master_id && $msg_id)
    {
      if($master_created)
      {
        $sql = ', first_msg_id = '.$msg_id.', first_text_id = '.$text_id;
      }
      else
      {
        $sql = ', msg_count = IFNULL(msg_count,0)+1';
      }
      $DB->query('UPDATE '.PRGM_TABLE_PREFIX.self::TBL_MASTER."
                  SET last_msg_id = %d, last_msg_date = %d, last_msg_username = '%s', last_text_id = %d,
                  msg_user_count = (SELECT COUNT(userid) FROM ".PRGM_TABLE_PREFIX.self::TBL_USER." u WHERE u.master_id = %d AND u.status = 'active')".
                  $sql . ' WHERE master_id = %d',
                  $msg_id, TIME_NOW, $this->_msg_sender_name, $text_id, $master_id, $master_id);
    }

    if($DB->database != $prevDB) $DB->select_db($prevDB);

    return true;

  } //save


  public static function getMessageCounts($userid, $flag=0, $master_id=0)
  {
    if(empty($userid) || !is_numeric($userid) || ($userid < 1)) return false;

    global $DB, $database;

    if(empty($flag) || !is_numeric($flag) || (int)$flag!==$flag)
    {
      $flag = self::MSG_INBOX | self::MSG_STATUS_UNREAD;
    }

    $total_count = 0;

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);

    if(($flag & self::MSG_DISCUSSIONS) || ($flag & self::MSG_DISCUSSIONS_STARTED) || ($flag & self::MSG_DISCUSSIONS_PARTAKE))
    {
      // ##### COUNT DISCUSSIONS? #####
      $extra1 = $extra2 = '';
      if($flag & self::MSG_STATUS_UNREAD)
      {
        $extra1 = ' OR m.starter_id = ' . $userid;
      }
      if(!empty($master_id)) // if master is given, count messages within discussion
      {
        $extra2 = ' AND m.master_id = '.(int)$master_id;
      }
      if($flag & self::MSG_STATUS_UNREAD)
      {
        $extra2 .= ' AND ((u.last_read = 0) OR (u.last_read < m.last_msg_date)) ';
      }
      $DB->result_type = MYSQL_ASSOC;
      $msg_count = $DB->query_first(
        'SELECT COUNT(DISTINCT m.master_id) msg_count FROM '.PRGM_TABLE_PREFIX.self::TBL_MASTER." m
         INNER JOIN ".PRGM_TABLE_PREFIX.self::TBL_USER." u ON m.master_id = u.master_id
         WHERE m.usersystemid = %d AND u.status = '%s'
         AND u.userid = $userid %s",
         self::$_usersystemid, self::DISCUSSIONS_USER_ACTIVE, $extra2);

      $total_count += (empty($msg_count['msg_count']) ? 0 : $msg_count['msg_count']);
    }
    else
    {
      // ##### COUNT MESSAGES? #####
      $extra  = '';
      $column = '';
      $inbox  = ($flag & self::MSG_INBOX)  ? 1 : 0;
      $outbox = ($flag & self::MSG_OUTBOX) ? 1 : 0;
      if(($inbox != $outbox) && ($inbox || $outbox))
      {
        $extra = ' AND '.($inbox ? 'recipient_id' : 'userid').' = '.$userid.
                 ' AND IFNULL(outbox_copy,0) = '.($outbox ? 1 : ($inbox ? 0: 1));
      }
      if($inbox && ($flag & SDMsg::MSG_STATUS_UNREAD))
      {
        $extra .= ' AND msg_read = 0';
      }
      $DB->result_type = MYSQL_ASSOC;
      $msg_count = $DB->query_first('SELECT COUNT(*) msg_count'.
                              ' FROM '.PRGM_TABLE_PREFIX.self::TBL_MSG.
                              ' WHERE usersystemid = %d AND master_id = 0 %s',
                              self::$_usersystemid, $extra);

      $total_count += (empty($msg_count['msg_count']) ? 0 : (int)$msg_count['msg_count']);
    }

    if($DB->database != $prevDB) $DB->select_db($prevDB);

    return $total_count;

  } //getMessageCounts


  public static function DeletePrivateMessages($userid, $msg_id)
  {
    global $DB, $database;

    if(empty($userid) || !is_numeric($userid) || ($userid < 1) || empty($msg_id))
    {
      return false;
    }

    // Treat "$msg_id" either as an array of message id's or single id
    $list = self::PrepareIDsList($msg_id);
    if(empty($list)) return false;

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);

    // Invoke attachment object for message attachments
    include_once(SD_INCLUDE_PATH.'class_sd_attachment.php');
    $att = new SD_Attachment(11,'messaging');

    foreach($list as $msg_id)
    {
      // Only delete *private* message if it has no "master" and if either
      // the specified user is the sender or recipient of the message
      $DB->result_type = MYSQL_ASSOC;
      if($row = $DB->query_first('SELECT * FROM '.PRGM_TABLE_PREFIX.self::TBL_MSG.'
                                  WHERE msg_id = %d AND usersystemid = %d AND master_id = 0 AND private = 1',
                                  $msg_id, self::$_usersystemid))
      {
        if(($row['userid']==$userid) || ($row['recipient_id']==$userid))
        {
          // Delete message body ONLY if not referenced by other messages
          $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.self::TBL_TEXT.' WHERE msg_text_id = %d
                      AND NOT EXISTS(SELECT 1 FROM '.PRGM_TABLE_PREFIX.self::TBL_MSG.' msg
                        WHERE msg.msg_id <> %d AND msg.msg_text_id = %d)',
                      $row['msg_text_id'], $row['msg_id'], $row['msg_text_id']);

          // Delete attachments linked to that body ONLY if not referenced by other messages
          if($DB->affected_rows())
          {
            // Reminder: attachments are linked to message body (msg_text_id == objectid)
            $att->setObjectID($row['msg_text_id']);
            $att->DeleteAllObjectAttachments();
          }

          // Delete actual message at last:
          $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.self::TBL_MSG.' WHERE msg_id = %d', $msg_id);
        }
      }
    }

    self::updateUserInboxCount($userid);
    self::updateUserOutboxCount($userid);

    if($DB->database != $prevDB) $DB->select_db($prevDB);
    return true;

  } //DeletePrivateMessages


  public static function setUserDiscussionReadDate($userid, $master_id, $date=null)
  {
    global $DB, $database;

    if(empty($userid) || !is_numeric($userid) || ($userid < 1) || empty($master_id) || !is_numeric($master_id) || ($master_id < 1))
    {
      return false;
    }
    $date       = empty($date) || !is_numeric($date) ? TIME_NOW : (int)$date;
    $master_id  = (int)$master_id;
    $userid     = (int)$userid;

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);

    $DB->query('UPDATE '.PRGM_TABLE_PREFIX.self::TBL_USER.' SET last_read = %d
                WHERE master_id = %d AND userid = %d AND last_read < %d',
                $date, $master_id, $userid, $date);

    if($DB->database != $prevDB) $DB->select_db($prevDB);
    return true;

  } //setUserDiscussionReadDate


  public static function setDiscussionsClosed($userid, $closed, $id_list=null)
  {
    global $DB, $database, $userinfo;

    if(empty($userid) || !is_numeric($userid) || ($userid < 1))
    {
      return false;
    }
    $closed = empty($closed) ? 0 : 1;

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);

    // Treat the "$id_list" as an array of discussion message id's
    $list = self::PrepareIDsList($id_list);
    if(empty($list)) return false;

    $list = ' AND master_id IN ('.implode(',', $list).')';
    if(empty($userinfo['adminaccess']))
    {
      $list .= ' AND starter_id = '.(int)$userid;
    }

    $DB->query('UPDATE '.PRGM_TABLE_PREFIX.self::TBL_MASTER.' SET is_closed = %d
                WHERE usersystemid = %d '.$list,
               $closed, self::$_usersystemid);

    if($DB->database != $prevDB) $DB->select_db($prevDB);
    return true;

  } //setDiscussionsClosed


  public static function setDiscussionMessagesApproved($master_id, $approved, $id_list=null)
  {
    global $DB, $database, $userinfo;

    $approved = empty($approved) ? 0 : 1;

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);

    // Treat the "$id_list" as an array of discussion message id's
    $list = self::PrepareIDsList($id_list);
    if(empty($list) && (empty($master_id) || !is_numeric($master_id) || ($master_id < 1)))
    {
      return false;
    }

    if(!empty($master_id))
    {
      $list = ' AND msg_id IN ('.implode(',', $list).')';
      $DB->result_type = MYSQL_ASSOC;
      $m = $DB->query_first('SELECT starter_id
                            FROM '.PRGM_TABLE_PREFIX.self::TBL_MASTER.'
                            WHERE usersystemid = %d AND master_id = %d',
                            self::$_usersystemid, $master_id);
      if(!empty($userinfo['adminaccess']) ||
         (!empty($m['starter_id']) && ($m['starter_id'] == $userinfo['userid'])))
      {
        $DB->query('UPDATE '.PRGM_TABLE_PREFIX.self::TBL_MSG.' SET approved = %d
                   WHERE usersystemid = %d AND master_id = %d'.$list,
                   $approved, self::$_usersystemid, $master_id);

        $DB->query('UPDATE '.PRGM_TABLE_PREFIX.self::TBL_MASTER.' SET msg_count =
                   (SELECT COUNT(*) FROM '.PRGM_TABLE_PREFIX.self::TBL_MSG.' msg
                    WHERE usersystemid = %d AND msg.master_id = %d AND msg.approved = 1)
                   WHERE usersystemid = %d AND master_id = %d',
                   self::$_usersystemid, $master_id,
                   self::$_usersystemid, $master_id);
      }
    }
    else
    {
      $list = ' AND master_id IN ('.implode(',', $list).')';
      $list .= (empty($userinfo['adminaccess']) ? ' AND starter_id = '.$userinfo['userid'] : '');
      $DB->query('UPDATE '.PRGM_TABLE_PREFIX.self::TBL_MASTER.' SET approved = %d
                  WHERE usersystemid = %d ' . $list,
                  $approved, self::$_usersystemid);
    }
    if($DB->database != $prevDB) $DB->select_db($prevDB);
    return true;

  } //setDiscussionMessagesApproved


  public static function setDiscussionsRead($userid=0, $id_list=null, $setunread=false)
  {
    global $DB, $database;

    if(empty($userid) || !is_numeric($userid) || ($userid < 1))
    {
      return false;
    }

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);

    // Treat the "%id_list" as an array of discussion (master-) id's
    $list = self::PrepareIDsList($id_list);
    if(empty($list)) return false;

    $list = '('.implode(',', $id_list).')';

    $DB->query('UPDATE '.PRGM_TABLE_PREFIX.self::TBL_USER.' SET last_read = %d
                WHERE userid = %d AND last_read = 0 AND master_id IN '.$list,
                (empty($setunread)?TIME_NOW:0), $userid);

    if($DB->database != $prevDB) $DB->select_db($prevDB);
    return true;

  } //setDiscussionsRead


  public static function setDiscussionUserStatus($userid, $status, $master_id, $id_list)
  {
    global $DB, $database;

    if( empty($userid) || !is_numeric($userid) || ($userid < 1) ||
        (empty($status) && (empty($master_id) || !is_numeric($master_id) || ($master_id < 1))) ||
        !in_array($status, array(self::DISCUSSIONS_USER_IGNORE, self::DISCUSSIONS_USER_ACTIVE, self::DISCUSSIONS_USER_DELETED)) )
    {
      return false;
    }

    if($master_id > 0)
    {
      //$list = ' = '.(int)$master_id;
      $list = array((int)$master_id);
    }
    else
    {
      // Treat the "%id_list" as an array of discussion (master-) id's
      $list = self::PrepareIDsList($id_list);
      if(empty($list)) return false;
      //$list = ' IN('.implode(',', $list).')';
    }

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);

    $untouched = 0;
    foreach($list as $master_id)
    {
      //TODO: if user is owner of discussion, should we delete it???
      //For now we won't allow to leave it.
      if(self::userIsDiscussionOwner($userid, $master_id))
      {
        $untouched++;
      }
      else
      {
        $DB->query('UPDATE '.PRGM_TABLE_PREFIX.self::TBL_USER."
                    SET status = '%s', is_moderator = 0
                    WHERE master_id = %d AND userid = %d
                    AND EXISTS(SELECT 1 FROM ".PRGM_TABLE_PREFIX.self::TBL_MASTER." m
                      WHERE m.usersystemid = %d AND m.master_id = %d)",
                    $status, $userid, self::$_usersystemid, $master_id);
      }
    }
    if($DB->database != $prevDB) $DB->select_db($prevDB);

    return $untouched;

  } //setDiscussionUserStatus


  public static function setMessagesRead($userid=0, $id_list=null, $setunread=false)
  {
    global $DB, $database;

    if(empty($userid) || ($userid < 1) || empty($id_list))
    {
      return false;
    }

    // Treat the "%id_list" as an array of discussion (master-) id's
    $list = self::PrepareIDsList($id_list);
    if(empty($list))
    {
      return false;
    }

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);

    $list = ' ('.implode(',',$list).')';

    $DB->query('UPDATE '.PRGM_TABLE_PREFIX.self::TBL_MSG.' SET msg_read = %d, msg_read_notify = 0
                WHERE usersystemid = %d
                AND (userid = %d OR recipient_id = %d)
                AND msg_id IN '.$list,
                (empty($setunread)?TIME_NOW:0), self::$_usersystemid, $userid, $userid);

    if($DB->database != $prevDB) $DB->select_db($prevDB);
    return true;

  } //setMessagesRead


  private static function _prepareMessage($msg)
  {
    global $DB, $bbcode, $mainsettings, $userinfo, $usersystem;

    if(isset($msg) && is_array($msg))
    {
      $avatar_conf = array(
        'output_ok'           => true,
        'userid'              => -1,
        'username'            => '',
        'Avatar Image Height' => (int)$mainsettings['default_avatar_height'],
        'Avatar Image Width'  => (int)$mainsettings['default_avatar_width'],
        'Avatar Column'       => false
      );
      $msg['msg_date_raw']       = (int)$msg['msg_date'];
      $msg['msg_date_text']      = DisplayDate($msg['msg_date'], 'Y-m-d', false, false, true).'<br />'.
                                   DisplayDate($msg['msg_date'], 'H:i', false, false, true);
      $msg['msg_date_text_nobr'] = DisplayDate($msg['msg_date'], 'Y-m-d G:i', true, false, true);
      $msg['msg_sender_link']    = ForumLink(4, $msg['userid']);
      $msg['msg_recipient_link'] = $msg['recipient_id'] ? ForumLink(4, $msg['recipient_id']) : '';
      $msg['msg_text_raw']       = $msg['msg_text'];
      //SD360: fill "msg_recipients_list" with list of all recipients (if available)
      //SD361: make sure to hide BCC'd usernames from recipient list
      $msg['msg_recipients_list'] = false;
      if(!empty($msg['msg_text_id']) &&
         $getrows = $DB->query('SELECT DISTINCT userid, recipient_id, recipient_name, is_bcc'.
                               ' FROM '.PRGM_TABLE_PREFIX.self::TBL_MSG.
                               ' WHERE msg_text_id = '.(int)$msg['msg_text_id'].
                               ' ORDER BY msg_id'))
      {
        $tmp = array();
        while($row = $DB->fetch_array($getrows))
        {
          if(empty($row['is_bcc']) || (!empty($userinfo['userid']) && ($userinfo['userid'] == $row['userid'])))
          $tmp[] = '<a class="msg_sender" href="'.ForumLink(4, $row['recipient_id']).'">'.$row['recipient_name'].'</a>';
        }
        if(count($tmp))
        $msg['msg_recipients_list'] = implode(', ', $tmp);
      }

      if(!empty($bbcode) && ($bbcode instanceof BBCode))
      {
        $bbcode->SetURLPattern('<a href="{$url/h}" target="_blank">{$text/h}</a>');
        $msg['msg_text'] = $bbcode->Parse($msg['msg_text']);
      }

      $avatar = '';
      $avatar_conf['userid'] = $msg['userid'];
      $avatar = sd_PrintAvatar($avatar_conf, true);
      $msg['msg_avatar'] = $avatar;
    }
    return $msg;

  } //_prepareMessage


  public static function loadMessage($msg_id=0)
  {
    global $DB, $database;

    if(empty($msg_id) || !is_numeric($msg_id) || ($msg_id < 1)) return false;

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);

    $DB->result_type = MYSQL_ASSOC;
    $result = $DB->query_first('SELECT m1.*, m2.msg_text FROM '.PRGM_TABLE_PREFIX.self::TBL_MSG.' m1
              LEFT JOIN '.PRGM_TABLE_PREFIX.self::TBL_TEXT.' m2 ON m1.msg_text_id = m2.msg_text_id
              WHERE usersystemid = %d AND msg_id = %d',
              self::$_usersystemid, $msg_id);
    $result = self::_prepareMessage($result);

    if($DB->database != $prevDB) $DB->select_db($prevDB);
    return $result;

  } //loadMessage


  public static function getDiscussions($userid, $master_id=0, $updateViews=false)
  {
    global $DB, $database, $userinfo;

    if(empty($userid) || !is_numeric($userid) || ($userid < 1)) return false;

    if(!isset(self::$_msg_options['currentpage']) || (!$cp = self::$_msg_options['currentpage'])) $cp = 1;
    if(!isset(self::$_msg_options['pagesize']) || (!$ps = self::$_msg_options['pagesize'])) $ps = 50;
    $order  = isset(self::$_msg_options['order']) ? self::$_msg_options['order'] : 'desc';
    $order  = ($order=='asc' || $order=='desc') ? $order : 'asc';
    $limit  = ' LIMIT '.(int)(($cp-1)*$ps).','.$ps;

    $userid = (int)$userid;
    $result = array();

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);
    $prevtype = $DB->result_type;
    $DB->result_type = MYSQL_ASSOC;

    if($master_id > 0)
    {
      if(!empty($updateViews))
      {
        $DB->query('UPDATE '.PRGM_TABLE_PREFIX.self::TBL_MASTER.' SET views = IFNULL(views,0)+1 WHERE master_id = %d',$master_id);
      }
      // Get messages for a specific discussion
      $sql1 = "SELECT msg.*, m.master_id, m.master_title, m.master_date, mt.msg_text_id,
              m.views, m.msg_count, m.msg_user_count, m.starter_id, m.starter_username,
              m.last_user_id, m.last_msg_id, m.last_msg_username, m.last_msg_date, m.is_private,
              m.approved master_approved, msg.userid, m.is_private, m.is_closed,
              ud.last_read user_last_read, IFNULL(mt.msg_text,'') msg_text, ud.last_read user_last_read";
      $sql2 = " FROM ".PRGM_TABLE_PREFIX.self::TBL_MSG." msg
              INNER JOIN ".PRGM_TABLE_PREFIX.self::TBL_MASTER." m ON m.master_id = msg.master_id
              INNER JOIN ".PRGM_TABLE_PREFIX.self::TBL_USER." ud ON ud.master_id = m.master_id
              INNER JOIN ".PRGM_TABLE_PREFIX.self::TBL_TEXT." mt ON mt.msg_text_id = msg.msg_text_id
              WHERE m.usersystemid = ".(int)self::$_usersystemid."
              AND m.master_id = ".(int)$master_id." AND ud.userid = ".(int)$userid."
              AND ((ud.userid = ".(int)$userid." AND (ud.status = '%s')))
              ".($userinfo['adminaccess'] ? '' : 'AND m.approved = 1 AND msg.approved = 1')."
              AND msg.outbox_copy = 0 AND msg.private = 0
              ORDER BY msg.msg_date $order";
      $total_rowcount = $DB->query_first('SELECT COUNT(*) rowcount'.$sql2,self::DISCUSSIONS_USER_ACTIVE);
      $get = $DB->query($sql1.$sql2.' '.$limit, self::DISCUSSIONS_USER_ACTIVE);
    }
    else
    {
      // Get discussions (master-listing only)
      $sql1 = "SELECT m.master_id, m.master_title, m.master_date,
              m.views, m.msg_count, m.msg_user_count, m.starter_id, m.starter_username,
              m.last_user_id, m.last_msg_id, m.last_msg_username, m.last_msg_date, m.is_private,
              m.approved master_approved, m.starter_id userid, m.last_msg_date msg_date,
              m.is_private, m.is_closed, ud.last_read user_last_read, '' username,
              '' msg_text, 0 msg_id, 0 recipient_id, '' recipient_name";
      $sql2 = " FROM ".PRGM_TABLE_PREFIX.self::TBL_MASTER.' m
              INNER JOIN '.PRGM_TABLE_PREFIX.self::TBL_USER." ud ON ud.master_id = m.master_id
              WHERE m.usersystemid = ".(int)self::$_usersystemid."
              AND ((ud.userid = ".(int)$userid." AND IFNULL(ud.status,'') = '%s'))
              ".($userinfo['adminaccess'] ? '' : 'AND m.approved = 1')."
              ORDER BY m.last_msg_date $order";
      $total_rowcount = $DB->query_first('SELECT COUNT(*) rowcount'.$sql2, self::DISCUSSIONS_USER_ACTIVE);
      $get = $DB->query($sql1.$sql2.' '.$limit, self::DISCUSSIONS_USER_ACTIVE);
    }
    $total_rowcount = isset($total_rowcount) ? $total_rowcount['rowcount'] : 0;

    if($get)
    {
      while($row = $DB->fetch_array($get,null,MYSQL_ASSOC))
      {
        $mid = $row['master_id'];
        if(!isset($result[$mid]))
        {
          $result[$mid] = array(
            'master_id'         => $mid,
            'master_title'      => $row['master_title'],
            'starter_id'        => (int)$row['starter_id'],
            'started_by'        => $row['starter_username'],
            'starter_link'      => ForumLink(4, (int)$row['starter_id']),
            'started_date'      => (DisplayDate((int)$row['master_date'], 'Y-m-d', false).'<br />'.
                                    DisplayDate((int)$row['master_date'], 'H:i', false)),
            'started_date_nobr' => DisplayDate((int)$row['master_date'], '', false),
            'started_date_raw'  => (int)$row['master_date'],
            'last_msg_text'     => (DisplayDate((int)$row['last_msg_date'], 'Y-m-d', false).'<br />'.
                                    DisplayDate((int)$row['last_msg_date'], 'H:i', false)),
            'last_msg_nobr'     => DisplayDate((int)$row['last_msg_date'], '', false),
            'last_msg_raw'      => (int)$row['last_msg_date'],
            'last_msg_id'       => (int)$row['last_msg_id'],
            'last_msg_user'     => $row['last_msg_username'],
            'title'             => $row['master_title'],
            'views'             => (int)$row['views'],
            'users_count'       => (int)$row['msg_user_count'],
            'message_count'     => (int)$row['msg_count'],
            'total_rowcount'    => (int)$total_rowcount,
            'starter_avatar'    => GetAvatarPath('',$row['starter_id']),
            'user_last_read'    => (int)$row['user_last_read'],
            'approved'          => (int)$row['master_approved'],
            'is_private'        => (int)$row['is_private'],
            'is_closed'         => (int)$row['is_closed'],
          );

          // Fetch all participants
          {
            if($getusers = $DB->query('SELECT userid, username, last_read FROM {'.self::TBL_USER.
                                      "} WHERE master_id = %d AND status = '%s' ORDER BY username",
                                      $mid, self::DISCUSSIONS_USER_ACTIVE))
            {
              while($u = $DB->fetch_array($getusers,null,MYSQL_ASSOC))
              {
                if(!isset($result[$mid]['users'][$u['userid']]))
                {
                  $result[$mid]['users'][$u['userid']] = array(
                    'id'   => (int)$u['userid'],
                    'name' => $u['username'],
                    'last_read' => (int)$u['last_read'],
                    'link' => ForumLink(4,(int)$u['userid'])
                  );
                }
              }
            }
          }
        }
        if(!isset($result[$mid]['messages'][$row['msg_id']]))
        {
          $result[$mid]['messages'][$row['msg_id']] = self::_prepareMessage($row);
          // Fetch attachments
          if(!empty($row['msg_text_id']) && ($att = self::getAttachments($row['msg_text_id'],$row['userid'])))
          {
            $result[$mid]['messages'][$row['msg_id']]['attachments'] = $att;
          }
        }
        if(!empty($row['recipient_id']) && !isset($result[$mid]['users'][$row['recipient_id']]))
        {
          $result[$mid]['users'][$row['recipient_id']] = array(
            'id'   => $row['recipient_id'],
            'name' => $row['recipient_name'],
            'link' => ForumLink(4,$row['recipient_id'])
          );
        }
      } //while
    }
    $DB->result_type = $prevtype;
    if($DB->database != $prevDB) $DB->select_db($prevDB);

    return $result;

  } //getDiscussions


  private static function _getMessages($inbox=true,$userid)
  {
    global $DB, $database;

    if(empty($userid) || !is_numeric($userid) || ($userid < 1)) return false;

    if(!$cp = self::$_msg_options['currentpage']) $cp = 1;
    if(!$ps = self::$_msg_options['pagesize']) $ps = 50;
    $limit = ' LIMIT '.(int)(($cp-1)*$ps).','.$ps;

    $userid = (int)$userid;
    $result = array();
    $cond = empty($inbox) ? "userid = $userid AND IFNULL(outbox_copy,0) = 1" :
                            "recipient_id = $userid AND IFNULL(outbox_copy,0) = 0";

    $prevDB = $DB->database;
    if($DB->database != $database['name']) $DB->select_db($database['name']);

    if($getmessages = $DB->query('SELECT m1.*, m2.msg_text FROM '.PRGM_TABLE_PREFIX.self::TBL_MSG.' m1
                      LEFT JOIN '.PRGM_TABLE_PREFIX.self::TBL_TEXT.' m2 ON m1.msg_text_id = m2.msg_text_id
                      WHERE usersystemid = %d AND master_id = 0 AND %s
                      ORDER BY msg_date DESC '.$limit,
                      self::$_usersystemid, $cond))
    {
      while($row = $DB->fetch_array($getmessages,null,MYSQL_ASSOC))
      {
        $result[] = self::_prepareMessage($row);
      }
    }

    if($DB->database != $prevDB) $DB->select_db($prevDB);

    return $result;

  } //getInboxMessages


  public static function getInboxMessages($userid)
  {
    return self::_getMessages(true, $userid);
  } //getInboxMessages


  public static function getOutboxMessages($userid)
  {
    return self::_getMessages(false, $userid);
  } //getOutboxMessages

} // class SDMsg
