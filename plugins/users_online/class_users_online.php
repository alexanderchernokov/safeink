<?php
if(!defined('IN_PRGM')) return false;

if(!class_exists('UsersOnlineClass'))
{
class UsersOnlineClass
{
  private $is_sd_forum = true;
  private $phrases = 0;
  private $pluginid = 0;
  private $settings = 0;
  private $forum_phrases = array();
  private $forum_settings = array();

  function UsersOnlineClass($plugin_folder)
  {
    global $plugin_name_to_id_arr, $userinfo, $usersystem;

    if($this->pluginid = GetPluginIDbyFolder($plugin_folder))
    {
      $this->phrases  = GetLanguage($this->pluginid);
      $this->settings = GetPluginSettings($this->pluginid,'','',true);
    }

    $this->is_sd_forum = ($usersystem['name']=='Subdreamer');
    if($this->is_sd_forum && ($forum_id = $plugin_name_to_id_arr['Forum']))
    {
      $this->forum_phrases = GetLanguage($forum_id);
      $this->forum_settings = GetPluginSettings($forum_id);
      //SD342 use new static SDUserCache class
      SDUserCache::$ForumAvatarAvailable  = function_exists('ForumAvatar');
      SDUserCache::$img_path              = 'plugins/forum/images/';
      SDUserCache::$bbcode_detect_links   = !empty($this->forum_settings['auto_detect_links']);
      SDUserCache::$lbl_offline           = $this->forum_phrases['user_offline'];
      SDUserCache::$lbl_online            = $this->forum_phrases['user_online'];
      SDUserCache::$lbl_open_profile_page = $this->forum_phrases['open_your_profile_page'];
      SDUserCache::$lbl_view_member_page  = $this->forum_phrases['view_member_page'];
      SDUserCache::$show_avatars          = !empty($this->forum_settings['display_avatar']) &&
                                            (!isset($userinfo['profile']['user_view_avatars']) ||
                                             !empty($userinfo['profile']['user_view_avatars']));
    }
  }

  function DisplayUsersOnline($usersystem)
  {
    global $DB, $dbname, $mainsettings, $userinfo;

    if(empty($usersystem) || !$this->pluginid)
    {
      if($this->pluginid && !empty($userinfo['adminaccess']))
      {
        echo '<p>Invalid usersystem or plugin configuration!</p>';
      }
      return false;
    }

    // Initialise all used variables
    $logged_visible_online = 0;
    $logged_hidden_online  = 0;
    $members_online        = 0;
    $guests_online         = 0;
    $online_userlist       = '';

    $total_online_users    = 0;
    $members_online        = 0;
    $record_online_users   = 0;
    $record_online_date    = 0;

    // Get usersystem settings
    $forumdbname = $usersystem['dbname'];
    $forumname   = $usersystem['name'];
    $forumpath   = $usersystem['folderpath'];
    $tableprefix = $usersystem['tblprefix'];

    // switch to forum database
    if($dbname != $forumdbname)
    {
      $DB->select_db($forumdbname);
    }

    if($this->is_sd_forum)
    {
      $datecut = time() - 900; // show users as online within last 15 minutes
      $usersonline = array();
      $is_basic = defined('UCP_BASIC') && UCP_BASIC;
      $max_users = Is_Valid_Number($this->settings['display_max_usernames'],0,0,99999);

      //$forum_installed = (GetPluginID('Forum') > 0);
      // Note: below used column "color_online" and "display_online" are in
      // table "usergroups" and were introduced with SD 3.3.0
      $DB->result_type = MYSQL_ASSOC;
      if($forumguests = $DB->query_first('SELECT COUNT(DISTINCT ipaddress,useragent) guestcount FROM ' . PRGM_TABLE_PREFIX . 'sessions'.
                                         ' WHERE userid = 0 AND admin = 0 AND loggedin = 0 AND (lastactivity > %d)',$datecut))
      {
        $guests_online += (int)$forumguests['guestcount'];
        unset($forumguests);
      }
      $forumusers = $DB->query('SELECT DISTINCT sessions.userid, sessions.lastactivity'.
                               ' FROM ' . PRGM_TABLE_PREFIX . 'sessions sessions'.
                               ' WHERE sessions.userid > 0 AND sessions.lastactivity > %d'.
                               ' AND sessions.loggedin = 1 AND sessions.admin = 0'.
                               ' ORDER BY sessions.lastactivity DESC', $datecut);
      if(!empty($forumusers))
      while($user = $DB->fetch_array($forumusers,null,MYSQL_ASSOC))
      {
        $cacheduser = SDUserCache::CacheUser($user['userid'],'',false);

        if(empty($userinfo['adminaccess']) && empty($cacheduser['user_allow_viewonline']))
        {
          $guests_online++;
        }
        else
        if(!isset($usersonline[$user['userid']]))
        {
          $uid = (int)$cacheduser['userid'];
          $username = $cacheduser['username'];

          if(empty($cacheduser['activated']) || !empty($cacheduser['banned']) || !empty($cacheduser['usergroup_details']['banned']))
          {
            $logged_hidden_online++;
          }
          else
          {
            $logged_visible_online++;
            if(!empty($this->settings['display_usernames']) && (!$max_users || ($max_users > count($usersonline))))
            {
              $usersonline[$uid] = $cacheduser['profile_link'];//$user_link;
            }
          }
        }
      } //while

      if($count = count($usersonline))
      {
        $online_userlist = implode(', ',array_values($usersonline));
        if($max_users && ($count >= $max_users))
        {
          $online_userlist .= ' '.str_replace('[USERCOUNT]', (1 + $count - $max_users), $this->phrases['x_other_users']);
        }
      }
      unset($usersonline,$user_link,$user_color);

      $DB->result_type = MYSQL_BOTH;

      $members_online     = $logged_visible_online + $logged_hidden_online;
      $total_online_users = $members_online + $guests_online;

      $max_settings = GetPluginSettings($this->pluginid, '', '', true);
      $most_ever = isset($max_settings['max_users_online']) ? (int)$max_settings['max_users_online']:$members_online;
      $most_date = empty($max_settings['max_users_date'])   ? TIME_NOW : (int)$max_settings['max_users_date'];
      if(!isset($max_settings['max_users_online']) || ($total_online_users > (int)$most_ever))
      {
        $most_date = TIME_NOW;
        $most_ever = $total_online_users;
        $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX."pluginsettings WHERE pluginid = %d AND IFNULL(groupname,'')='' AND title IN ('max_users_date','max_users_online')", $this->pluginid);
        $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX."pluginsettings (settingid, pluginid, groupname, title, description, value, displayorder)".
                   " VALUES (null, %d, '', 'max_users_online', '', %d, 0),".
                   " (null, %d, '', 'max_users_date', '', %d, 0)",
                   $this->pluginid, $total_online_users, $this->pluginid, TIME_NOW);
      }
      $record_online_date  = $most_date;
      $record_online_users = $most_ever;
    }
    else
    if($forumname == 'vBulletin 2')
    {
      $cookietimeout = 900; // vB2 default
      $datecut       = time() - $cookietimeout;

      $forumusers = $DB->query(
        'SELECT user.username, user.invisible, session.userid, session.lastactivity'.
        ' FROM '.$tableprefix.'session AS session'.
        ' LEFT JOIN '.$tableprefix.'user user ON(user.userid = session.userid)'.
        ' WHERE session.lastactivity > %d ORDER BY username ASC',$datecut);

      $usersonline = array();

      while($user = $DB->fetch_array($forumusers,null,MYSQL_ASSOC))
      {
        if(empty($user['userid'])) // Guest
        {
          $guests_online++;
        }
        else
        if(empty($usersonline[$user['userid']]))
        {
          $usersonline[$user['userid']] = $user['username'];
          // Only display users that allow it
          if(!empty($user['invisible']))
          {
            $logged_hidden_online++;
          }
          else
          {
            $user_link = '<a href="' . $forumpath . 'member.php?action=getinfo&amp;userid='.$user['userid'].'">'.$user['username'].'</a>';
            $online_userlist .= ( $online_userlist != '' ) ? ', ' . $user_link : $user_link;
            $logged_visible_online++;
          }
        }
      }

      $gettemp  = $DB->query_first("SELECT template FROM template WHERE title = 'maxloggedin'");
      $template = $gettemp[template];

      $maxusers = explode(' ', trim($template));
      $maxusers[0] = intval($maxusers[0]);

      $total_online_users  = $logged_visible_online + $logged_hidden_online + $guests_online;
      $members_online      = $logged_visible_online + $logged_hidden_online;
      $record_online_users = $maxusers[0];
      $record_online_date  = $maxusers[1];

    }  // end if vBulletin 2
    else
    if($forumname == 'vBulletin 3')
    {
      $cookietimeout          = 900; // vB3 default
      $_USEROPTIONS_invisible = 512;
      $datecut                = time() - $cookietimeout;

      $forumusers = $DB->query("SELECT user.username, (user.options & $_USEROPTIONS_invisible) invisible, user.usergroupid,
                                session.userid, session.inforum, session.lastactivity,
                                IF(displaygroupid=0, user.usergroupid, displaygroupid) displaygroupid,
                                ug.usertitle, ug.opentag, ug.closetag
                                FROM " . $tableprefix . "session AS session
                                LEFT JOIN " . $tableprefix . "user AS user ON user.userid = session.userid
                                LEFT JOIN " . $tableprefix . "usergroup AS ug ON ug.usergroupid = user.usergroupid
                                WHERE session.lastactivity > $datecut ORDER BY username ASC");

      $usersonline = array();
      while($user = $DB->fetch_array($forumusers,null,MYSQL_ASSOC))
      {
        if(empty($user['userid'])) // Guest
        {
          $guests_online++;
        }
        else
        if(empty($usersonline[$user['userid']]))
        {
          $usersonline[$user['userid']] = $user['username'];
          // only display this user if he allows it
          if(!empty($user['invisible']))
          {
            $logged_hidden_online++;
          }
          else
          {
            $title = empty($user['usertitle']) ? '' : ' title="'.htmlspecialchars($user['usertitle'], ENT_QUOTES).'" ';
            if(!empty($user['opentag']) && !empty($user['closetag']))
            {
              $user['username'] = $user['opentag'] . $user['username'] . $user['closetag'];
            }
            $user_link = '<a '.$title.' href="' . $forumpath . 'member.php?u='.$user['userid'].'">'.$user['username'].'</a>';
            $online_userlist .= ($online_userlist != '') ? ', ' . $user_link : $user_link;
            $logged_visible_online++;
          }
        }
      }

      $total_online_users  = $logged_visible_online + $logged_hidden_online + $guests_online;
      $members_online      = $logged_visible_online + $logged_hidden_online;
      $record_online_users = '';
      $record_online_date  = '';
      if($datastore = $DB->query_first('SELECT data FROM ' . $tableprefix . "datastore WHERE title = 'maxloggedin'"))
      {
        if($maxusers  = unserialize($datastore['data']))
        {
          $record_online_users = $maxusers['maxonline'];
          $record_online_date  = $maxusers['maxonlinedate'];
        }
      }
    }  // end if vBulletin 3
    else
    if($forumname == 'vBulletin 4')
    {
      $cookietimeout          = 900; // vB3 default
      $_USEROPTIONS_invisible = 512;
      $datecut                = time() - $cookietimeout;

      $forumusers = $DB->query("SELECT user.username, (user.options & $_USEROPTIONS_invisible) AS invisible, user.usergroupid,
                                session.userid, session.inforum, session.lastactivity,
                                IF(displaygroupid=0, user.usergroupid, displaygroupid) AS displaygroupid
                                FROM " . $tableprefix . "session AS session
                                LEFT JOIN " . $tableprefix . "user AS user ON(user.userid = session.userid)
                                WHERE session.lastactivity > $datecut ORDER BY username ASC");

      $usersonline = array();
      while($user = $DB->fetch_array($forumusers,null,MYSQL_ASSOC))
      {
        if(empty($user['userid'])) // Guest
        {
          $guests_online++;
        }
        else
        if(empty($usersonline[$user['userid']]))
        {
          $usersonline[$user['userid']] = $user['username'];
          // only display this user if he allows it
          if(!empty($user['invisible']))
          {
            $logged_hidden_online++;
          }
          else
          {
            $user_link = '<a href="' . $forumpath . 'member.php?u='.$user['userid'].'">'.$user['username'].'</a>';
            $online_userlist .= ( $online_userlist != '' ) ? ', ' . $user_link : $user_link;
            $logged_visible_online++;
          }
        }
      }

      $datastore = $DB->query_first("SELECT data FROM " . $tableprefix . "datastore WHERE title = 'maxloggedin'");
      $maxusers  = unserialize($datastore['data']);

      $total_online_users  = $logged_visible_online + $logged_hidden_online + $guests_online;
      $members_online      = $logged_visible_online + $logged_hidden_online;
      $record_online_users = $maxusers['maxonline'];
      $record_online_date  = $maxusers['maxonlinedate'];

    }  // end if vBulletin 4
    else
    if($forumname == 'phpBB2')
    {
      $prev_user_id    = 0;
      $prev_session_ip = '';

      $result = $DB->query("SELECT u.username, u.user_id, u.user_allow_viewonline, u.user_level, s.session_logged_in, s.session_ip
                            FROM " . $tableprefix . "users u, " . $tableprefix . "sessions s
                            WHERE u.user_id = s.session_user_id AND s.session_time >= ".( time() - 900 ) . "
                            ORDER BY u.username ASC, s.session_ip ASC");

      while($row = $DB->fetch_array($result,null,MYSQL_ASSOC))
      {
        // User is logged in and therefor not a guest
        // Skip multiple sessions for one user
        if( ($row['session_logged_in']) && ($row['user_id'] != $prev_user_id ) )
        {
          // only display this user if he allows it
          if($row['user_allow_viewonline'])
          {
            $user_link = '<a href="' . $forumpath . 'profile.php?mode=viewprofile&u='.$row['user_id'].'">'.$row['username'].'</a>';
            $online_userlist .= ( $online_userlist != '' ) ? ', ' . $user_link : $user_link;
            $logged_visible_online++;
          }
          else
          {
            $logged_hidden_online++;
          }
          $prev_user_id = $row['user_id'];
          $prev_session_ip = $row['session_ip'];
        }
        else if ( $row['session_ip'] != $prev_session_ip )  // user is a guest, Skip multiple sessions for one guest
        {
          $guests_online++;
          $prev_session_ip = $row['session_ip'];
        }
      } // end while loop

      $get_record_online_users = $DB->query_first("SELECT config_value FROM " . $tableprefix . "config WHERE config_name = 'record_online_users'");
      $get_record_online_date  = $DB->query_first("SELECT config_value FROM " . $tableprefix . "config WHERE config_name = 'record_online_date'");

      $total_online_users  = $logged_visible_online + $logged_hidden_online + $guests_online;
      $members_online      = $logged_visible_online + $logged_hidden_online;
      $record_online_users = $get_record_online_users['config_value'];
      $record_online_date  = $get_record_online_date['config_value'];

    }  // end if phpBB2 forum
    else
    if($forumname == 'phpBB3')
    {
      $prev_user_id    = 0;
      $prev_session_ip = '';

      $result = $DB->query("SELECT u.username, u.user_id, u.user_allow_viewonline, u.user_type,
                            s.session_user_id, s.session_viewonline, s.session_time, s.session_ip
                            FROM " . $tableprefix . "users u, " . $tableprefix . "sessions s
                            WHERE u.user_id = s.session_user_id AND u.user_type <> 2
                            AND s.session_time >= ".( time() - 900 ) . "
                            ORDER BY u.username ASC, s.session_ip ASC");

      while($row = $DB->fetch_array($result,null,MYSQL_ASSOC))
      {
        // User is logged in and therefor not a guest
        // Skip multiple sessions for one user
        if(($row['session_user_id'] > 1) && ($row['session_time']) && ($row['user_id'] != $prev_user_id))
        {
          // only display this user if he allows it
          if($row['session_viewonline'])
          {
            $user_link = '<a href="' . $forumpath . 'memberlist.php?mode=viewprofile&u='.$row['user_id'].'">'.$row['username'].'</a>';
            $online_userlist .= ( $online_userlist != '' ) ? ', ' . $user_link : $user_link;
            $logged_visible_online++;
          }
          else
          {
            $logged_hidden_online++;
          }
          $prev_user_id = $row['user_id'];
          $prev_session_ip = $row['session_ip'];
        }
        else
        if($row['session_ip'] != $prev_session_ip )  // user is a guest, Skip multiple sessions for one guest
        {
          $guests_online++;
          $prev_session_ip = $row['session_ip'];
        }
      } // end while loop

      $get_record_online_users = $DB->query_first("SELECT config_value FROM " . $tableprefix . "config WHERE config_name = 'record_online_users'");
      $get_record_online_date  = $DB->query_first("SELECT config_value FROM " . $tableprefix . "config WHERE config_name = 'record_online_date'");

      $total_online_users  = $logged_visible_online + $logged_hidden_online + $guests_online;
      $members_online      = $logged_visible_online + $logged_hidden_online;
      $record_online_users = $get_record_online_users['config_value'];
      $record_online_date  = $get_record_online_date['config_value'];

    }  // end if phpBB3 forum
    else
    if($forumname == 'Invision Power Board 2')
    {
      $prev_user_id    = 0;
      $prev_session_ip = '';

      $cutoff = $DB->query_first("SELECT conf_value, conf_default FROM " . $tableprefix .
                                 "conf_settings WHERE conf_key = 'au_cutoff'");
      $cutoff = ($cutoff['conf_value'] > 0 ? $cutoff['conf_value'] : $cutoff['conf_default']) * 60;
      $time   = time() - (int)$cutoff;

      // if user is online...
      $members = $DB->query("SELECT s.member_id, m.name, s.ip_address
                             FROM " . $tableprefix . "sessions AS s
                             LEFT JOIN " . $tableprefix . "members AS m
                             ON (m.id = s.member_id)
                             WHERE running_time > " . $time . " AND
                             s.login_type <> 1
                             ORDER BY s.member_id, s.ip_address");

      while($member = $DB->fetch_array($members,null,MYSQL_ASSOC))
      {
        if($member['member_id'] > 0 && ($member['member_id'] != $prev_user_id ))
        {
          $user_link = '<a href="' . $forumpath . 'index.php?showuser='.$member['member_id'].'">'.$member['name'].'</a>';
          $online_userlist .= ( $online_userlist != '' ) ? ', ' . $user_link : $user_link;
          $logged_visible_online++;
          $prev_user_id = $member['member_id'];
        }
        else if(!$member['member_id'])
        {
          $guests_online++;
        }
      }
      $statsarray = false;
      if($stats = $DB->query_first("SELECT cs_value FROM " . $tableprefix . "cache_store WHERE cs_key = 'stats'"))
      {
        $statsarray = @unserialize($stats[0]);
      }

      $total_online_users = $logged_visible_online + $logged_hidden_online + $guests_online;
      $members_online     = $logged_visible_online + $logged_hidden_online;
      if(!$statsarray)
      {
        $record_online_users = 0;
        $record_online_date  = 0;
      }
      else
      {
        $record_online_users = $statsarray['most_count'];
        $record_online_date  = $statsarray['most_date'];
      }
    } // end if IPB2 forum
    else
    if($forumname == 'Invision Power Board 3')
    {
      $prev_user_id    = 0;
      $prev_session_ip = '';

      $cutoff = $DB->query_first('SELECT conf_value, conf_default FROM ' . $tableprefix .
                                 " core_sys_conf_settings WHERE conf_key = 'au_cutoff'");
      $cutoff = ($cutoff['conf_value'] > 0 ? $cutoff['conf_value'] : $cutoff['conf_default']) * 60;
      $time   = time() - (int)$cutoff;

      // if user is online...
      $members = $DB->query("SELECT s.member_id, m.name, s.ip_address
                             FROM " . $tableprefix . "sessions AS s
                             LEFT JOIN " . $tableprefix . "members AS m
                             ON (m.member_id = s.member_id)
                             WHERE running_time > " . $time . " AND
                             s.login_type <> 1
                             ORDER BY s.member_id, s.ip_address");

      while($member = $DB->fetch_array($members,null,MYSQL_ASSOC))
      {
        if($member['member_id'] > 0 && ($member['member_id'] != $prev_user_id ))
        {
          $user_link = '<a href="' . $forumpath . 'index.php?showuser='.$member['member_id'].'">'.$member['name'].'</a>';
          $online_userlist .= ( $online_userlist != '' ) ? ', ' . $user_link : $user_link;
          $logged_visible_online++;
          $prev_user_id = $member['member_id'];
        }
        else if(!$member['member_id'])
        {
          $guests_online++;
        }
      }

      $record_online_users = 0;
      $record_online_date  = 0;
      $DB->result_type = MYSQL_ASSOC;
      if($stats = $DB->query_first("SELECT cs_value FROM " . $tableprefix . "cache_store WHERE cs_key = 'stats'"))
      {
        $statsarray = @unserialize($stats['cs_value']);
        $record_online_users = $statsarray['most_count'];
        $record_online_date  = $statsarray['most_date'];
      }

      $total_online_users  = $logged_visible_online + $logged_hidden_online + $guests_online;
      $members_online      = $logged_visible_online + $logged_hidden_online;
    } // end if IPB3 forum
    else
    if($forumname == 'Simple Machines Forum 1')
    {
      $prev_user_id    = 0;
      $prev_session_ip = '';

      // if user is online...
      //SD370: added "is_activated"
      $members = $DB->query("SELECT lo.ID_MEMBER, mem.realName, mem.showOnline, lo.ip
                 FROM " . $tableprefix . "log_online AS lo
                 LEFT JOIN " . $tableprefix . "members AS mem ON (lo.ID_MEMBER = mem.ID_MEMBER)
                 WHERE mem.is_activated = 1
                 ORDER BY lo.ID_MEMBER, lo.ip");

      while($member = $DB->fetch_array($members,null,MYSQL_ASSOC))
        {
        if(!empty($member['ID_MEMBER']) && ($member['ID_MEMBER'] > 0))
        {
          if(!empty($member['showOnline']))
          {
            $user_link = '<a href="' . $forumpath . 'index.php?action=profile;u='.$member['ID_MEMBER'].'">'.$member['realName'].'</a>';
            $online_userlist .= ( $online_userlist != '' ) ? ', ' . $user_link : $user_link;
            $logged_visible_online++;
          }
          else
          {
            $logged_hidden_online++;
          }
        }
        else if( $member['ip'] != $prev_session_ip )  // user is a guest, Skip multiple sessions for one guest
        {
          $guests_online++;
        }
        $prev_session_ip = $member['ip'];
      }

      $DB->result_type = MYSQL_ASSOC;
      $get_record_online_users = $DB->query_first("SELECT value FROM " . $tableprefix . "settings WHERE variable = 'mostOnline'");
      $DB->result_type = MYSQL_ASSOC;
      $get_record_online_date  = $DB->query_first("SELECT value FROM " . $tableprefix . "settings WHERE variable = 'mostDate'");

      $total_online_users  = $logged_visible_online + $logged_hidden_online + $guests_online;
      $members_online      = $logged_visible_online + $logged_hidden_online;
      $record_online_users = $get_record_online_users['value'];
      $record_online_date  = $get_record_online_date['value'];

    } // end if SMF forum 1
    else
    if($forumname == 'Simple Machines Forum 2')
    {
      $prev_user_id    = 0;
      $prev_session_ip = '';

      // if user is online...
      //SD370: added "is_activated"
      $members = $DB->query("SELECT lo.id_member, mem.real_name, mem.show_online, lo.ip
                 FROM " . $tableprefix . "log_online AS lo
                 LEFT JOIN " . $tableprefix . "members AS mem ON (lo.id_member = mem.id_member)
                 WHERE mem.is_activated = 1
                 ORDER BY lo.id_member, lo.ip");

      while($member = $DB->fetch_array($members,null,MYSQL_ASSOC))
      {
        if(!empty($member['id_member']) && ($member['id_member'] > 0))
        {
          if(!empty($member['show_online']))
          {
            $user_link = '<a href="' . $forumpath . 'index.php?action=profile;u='.$member['id_member'].'">'.$member['real_name'].'</a>';
            $online_userlist .= ( $online_userlist != '' ) ? ', ' . $user_link : $user_link;
            $logged_visible_online++;
          }
          else
          {
            $logged_hidden_online++;
          }
        }
        else if( $member['ip'] != $prev_session_ip )  // user is a guest, Skip multiple sessions for one guest
        {
          $guests_online++;
        }
        $prev_session_ip = $member['ip'];
      }

      $DB->result_type = MYSQL_ASSOC;
      $get_record_online_users = $DB->query_first("SELECT value FROM " . $tableprefix . "settings WHERE variable = 'mostOnline'");
      $DB->result_type = MYSQL_ASSOC;
      $get_record_online_date  = $DB->query_first("SELECT value FROM " . $tableprefix . "settings WHERE variable = 'mostDate'");

      $total_online_users  = $logged_visible_online + $logged_hidden_online + $guests_online;
      $members_online      = $logged_visible_online + $logged_hidden_online;
      $record_online_users = $get_record_online_users['value'];
      $record_online_date  = $get_record_online_date['value'];

    } // end if SMF forum
    else
    if($forumname == 'XenForo 1')
    {
      global $SD_XenForo_user;

      $sessionModel = XenForo_Model::create('XenForo_Model_Session');
      $timeout  = $sessionModel->getOnlineStatusTimeout();
      $response = $sessionModel->getSessionActivityQuickList(
                    $SD_XenForo_user->toArray(),
                    array('cutOff' => array('>', $timeout)),
                    ($SD_XenForo_user['user_id'] ? $SD_XenForo_user->toArray() : null)
                  );
      if($response)
      {
        $total_online_users  = $response['total'];
        $members_online      = $response['members'];
        $guests_online       = $response['guests'];
        $record_online_users = 0;
        $record_online_date  = 0;
        foreach($response['records'] as $member)
        {
          if(!empty($member['user_state']) && ($member['user_state'] == 'valid'))
          {
            if(!empty($member['user_id']) && ($member['user_id'] > 0))
            {
              if(!empty($member['visible']) && empty($member['is_banned']))
              {
                $user_link = '<a href="' . ForumLink(4, $member['user_id']) .'">'.$member['username'].'</a>';
                $online_userlist .= ($online_userlist != '') ? ', ' . $user_link : $user_link;
                $logged_visible_online++;
              }
              else
              {
                $logged_hidden_online++;
              }
            }
          }
        }
      }
      unset($response,$timeout,$sessionModel);
    }
    else
    if($forumname == 'MyBB') //SD370
    {
      $prev_user_id    = 0;
      $prev_session_ip = '';

      $result = $DB->query('SELECT u.username, u.uid,
                            s.uid as session_user_id, s.anonymous, s.time, s.ip
                            FROM '.$tableprefix.'sessions s
                            INNER JOIN '.$tableprefix.'users u ON u.uid = s.uid
                            INNER JOIN '.$tableprefix.'usergroups ug ON ug.gid = u.usergroup
                            WHERE ug.isbannedgroup = 0 AND s.time >= '.(TIME_NOW - 900).'
                            ORDER BY u.username ASC, s.ip ASC');

      while($row = $DB->fetch_array($result,null,MYSQL_ASSOC))
      {
        // User is logged in and therefore not a guest
        // Skip multiple sessions for one user
        if(($row['session_user_id'] > 0) && ($row['time'] > 0) && ($row['uid'] != $prev_user_id))
        {
          // only display this user if he allows it
          if(empty($row['anonymous']))
          {
            $user_link = '<a href="'.ForumLink(4, $row['uid'], $row['username']).'">'.$row['username'].'</a>';
            $online_userlist .= ( $online_userlist != '' ) ? ', ' . $user_link : $user_link;
            $logged_visible_online++;
          }
          else
          {
            $logged_hidden_online++;
          }
          $prev_user_id = $row['uid'];
          $prev_session_ip = $row['ip'];
        }
        else
        if($row['ip'] != $prev_session_ip ) // user is a guest, Skip multiple sessions for one guest
        {
          $guests_online++;
          $prev_session_ip = $row['ip'];
        }
      } // end while loop

      $total_online_users  = $logged_visible_online + $logged_hidden_online + $guests_online;
      $members_online      = $logged_visible_online + $logged_hidden_online;
      $record_online_users = 0;
      $record_online_date  = 0;
      $DB->result_type = MYSQL_ASSOC;
      if($get_record_online_users = $DB->query_first("SELECT cache FROM " . $tableprefix . "datacache WHERE title = 'mostonline' AND cache <> 'a:0:{}'"))
      {
        if(false !== ($tmp = unserialize($get_record_online_users['cache'])))
        {
          $record_online_users = $tmp['numusers'];
          $record_online_date  = $tmp['time'];
        }
      }
    }
    else
    if($forumname == 'punBB') //SD370
    {
      $prev_user_id    = 0;
      $prev_session_ip = '';
      global $punbb_config;
      $timeout = isset($punbb_config['o_timeout_online'])?intval($punbb_config['o_timeout_online']):900;
      $result = $DB->query("SELECT o.user_id, o.ident, o.logged, IFNULL(u.username,'Guest') username".
                           ' FROM '.$tableprefix.'online o'.
                           ' LEFT JOIN '.$tableprefix.'users u ON u.id = o.user_id'.
                           ' WHERE o.user_id > 0 AND o.logged >= '.(int)(TIME_NOW - $timeout).
                           ' ORDER BY u.username ASC');

      while($row = $DB->fetch_array($result,null,MYSQL_ASSOC))
      {
        // User_ID > 1 is a logged in user, = 1 is a guest
        if(($row['user_id'] > 1) && !empty($row['logged']) && ($row['user_id'] != $prev_user_id))
        {
          $user_link = '<a href="'.ForumLink(4, $row['user_id'], $row['username']).'">'.$row['username'].'</a>';
          $online_userlist .= ( $online_userlist != '' ) ? ', ' . $user_link : $user_link;
          $logged_visible_online++;
          $prev_user_id = $row['user_id'];
        }
        else
        if(($row['user_id'] == 1) && ($row['ident'] != $prev_session_ip)) // user is a guest
        {
          $guests_online++;
          $prev_session_ip = $row['ident'];
        }
      } // end while loop

      $total_online_users  = $logged_visible_online + $logged_hidden_online + $guests_online;
      $members_online      = $logged_visible_online + $logged_hidden_online;
      $record_online_users = 0;
      $record_online_date  = 0;
    }

    // ##################################
    // Display the users online
    // ##################################
    echo '
    <div id="users_online">
    ';
    if(!empty($this->settings['display_total_online'])) // ex: 5 Online
    {
      echo $this->phrases['online_now'] . ' <strong>' . $total_online_users . '</strong><br />';
    }

    if(!empty($this->settings['display_users_and_guests_online'])) // Display online users and guest numbers? ex: 5 Members | 3 Guests
    {
      echo '<strong>' . $members_online . '</strong> ' .
           ($members_online == 1 ? $this->phrases['member'] : $this->phrases['members']) .
           ' | <strong>' . $guests_online . '</strong> ' .
           ($guests_online == 1 ? $this->phrases['guest'] : $this->phrases['guests']) . '<br />';
    }

    if(!empty($this->settings['display_usernames']) && !empty($online_userlist)) // Display usernames?
    {
      echo $online_userlist . '<br />';
    }

    if(!empty($this->settings['display_most_ever_online']) && !empty($record_online_users)) // Display most users ever online?
    {
      echo '<br />' . $this->phrases['most_online'] . ' ' . $record_online_users . ' ' . $this->phrases['on'] . ' ';
      echo DisplayDate($record_online_date) . ' ' . $this->phrases['at'] . ' ';
      echo DisplayDate($record_online_date, isset($mainsettings['settings_time_format'])?$mainsettings['settings_time_format']:"h:iA") . '.<br />';
    }

    echo '
    </div>
    ';
    // switch back to subdreamer database
    if($dbname != $forumdbname)
    {
      $DB->select_db($dbname);
    }
  }

} // end of class

} // DO NOT REMOVE
