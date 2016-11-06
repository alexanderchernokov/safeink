<?php
if(!defined('IN_PRGM')) exit();

if(!function_exists('ActivePostersOutput'))
{
  function ActivePostersOutput($pluginid, $output_arr)
  {
    if(empty($output_arr['post_count'])) return;

    $result = '<li id="p'.$pluginid.'_'.$output_arr['userid'].'">
      <div class="p'.$pluginid.'_outer">
        <div class="p'.$pluginid.'_avatar">'.$output_arr['a_output'].'</div>
        <div class="p'.$pluginid.'_user">';
    if(!empty($output_arr['user_link']))
    {
      if((substr($output_arr['user_link'],0,2)=='<a') || (substr($output_arr['user_link'],0,5)=='<span'))
        $result .= $output_arr['user_link'];
      else
        $result .= '<a href="'.$output_arr['user_link'].'">'.$output_arr['username'].'</a>';
    }
    else
    {
      $result .= $output_arr['username'];
    }
    $result .= ': '.$output_arr['post_count'].'
        </div>
      </div>
    </li>
    ';
    return $result;
  }
}

if(!function_exists('ActivePostersPlugin'))
{
  function ActivePostersPlugin($pluginid)
  {
    global $DB, $SDCache, $dbname, $userinfo, $usersystem;

    $forumdbname = $usersystem['dbname'];
    $forumname   = $usersystem['name'];
    $forumpath   = $usersystem['folderpath'];
    $tableprefix = $usersystem['tblprefix'];

    // get language and settings
    $language  = GetLanguage($pluginid);
    $settings  = GetPluginSettings($pluginid);

    $postlimit = Is_Valid_Number((int)$settings['number_of_active_posters_to_display'],5,1,99999);
    $minposts  = Is_Valid_Number((int)$settings['minimum_posts'],1,1,999999);
    $days      = Is_Valid_Number((int)$settings['days_to_count_posts'],7,0,99999);
    $groups    = sd_ConvertStrToArray($settings['display_usergroups'],',');

    $printavatar = !empty($settings['display_avatar']);
    if($printavatar)
    {
      $img_h = intval($settings['avatar_image_height']);
      $img_w = intval($settings['avatar_image_width']);
      $settings['avatar_image_height'] = (!empty($img_h) & ($img_h > 1)) ? $img_h : '';
      $settings['avatar_image_width'] = (!empty($img_w) && ($img_w > 1)) ? $img_w : '';
    }

    // Config array as parameter for sd_PrintAvatar (in globalfunctions.php)
    $avatar_conf = array(
      'output_ok'           => $printavatar,
      'userid'              => -1,
      'username'            => '',
      'Avatar Column'       => false,
      'Avatar Image Height' => $settings['avatar_image_height'],
      'Avatar Image Width'  => $settings['avatar_image_width']
      );

    // For active forum integration fetch list of mapped usergroups
    $groups_cond = '';
    if(($forumname != 'Subdreamer') && !empty($groups))
    {
      if($DB->database != $dbname) $DB->select_db($dbname);
      if($getgroups = $DB->query('SELECT forumusergroupid FROM {usergroups}'.
                                 ' WHERE usergroupid IN (%s) ORDER BY forumusergroupid',
                                 implode(',',$groups)))
      {
        $forumgroups = array();
        while($group = $DB->fetch_array($getgroups,null,MYSQL_ASSOC))
        {
          $forumgroups[] = $group['forumusergroupid'];
        }
        if(!empty($forumgroups))
        {
          @sort($forumgroups);
          $groups_cond = ' IN ('.implode(',',$forumgroups).')';
        }
        unset($forumgroups,$getgroups);
      }
    }
    if($DB->database != $forumdbname) $DB->select_db($forumdbname);

    // Start plugin contents
    $title = str_replace('[xxx]', $days, $language['title']);
    echo '
    <div id="p'.$pluginid.'_active_posters">
    <div class="p'.$pluginid.'_header">'.$title.'</div>
    <ul>
    ';

    $out = '';
    $break_time = (($days==0) || ($days>900)) ? (TIME_NOW - 30*86400) : (TIME_NOW - ($days * 86400));
    if($forumname == 'Subdreamer')
    {
      // Check if there's a new post by checking against highest entry
      $last_post_id = false;
      if($getmax = $DB->query_first('SELECT MAX(last_post_id) max_post_id FROM {p_forum_forums}'))
      {
        $last_post_id = (int)$getmax['max_post_id'];
      }
      unset($getmax);
      // Load or fill cached active posters
      $cache = false;
      if(!empty($SDCache) && $SDCache->IsActive())
      {
        if(($getcache = $SDCache->read_var('plugin-activeposters', 'activeposters')) !== false)
        {
          $cache = array();
          if(isset($getcache['users'])) $cache['users'] = (array)$getcache['users'];
          if(isset($getcache['userids'])) $cache['userids'] = (array)$getcache['userids'];
          if(isset($getcache['max_post_id'])) $cache['max_post_id'] = (int)$getcache['max_post_id'];
          unset($getcache);
        }
      }
      if(!empty($groups))
      {
        $groups_cond = ' AND u.usergroupid IN ('.implode(',',$groups).')';
      }

      //SD343: user caching of poster, incl. avatar
      include_once(SD_INCLUDE_PATH.'class_sd_usercache.php'); //SD343
      SDUserCache::$avatar_height = $settings['avatar_image_height'];
      SDUserCache::$avatar_width = $settings['avatar_image_width'];
      $use_data = !SDUserCache::IsBasicUCP() && !SD_IS_BOT; //SD343

      //SD343 check all variables
      if( empty($cache) || empty($cache['userids']) || empty($cache['max_post_id']) ||
          empty($last_post_id) || empty($cache['max_post_id']) || ($last_post_id != $cache['max_post_id']) )
      {
        $getusers = $DB->query(
         'SELECT u.userid, u.username,
          (SELECT COUNT(*) FROM {p_forum_posts} p WHERE p.user_id = u.userid AND p.date >= '.$break_time.' AND moderated = 0) active_post_count
          FROM {users} u
          WHERE u.activated = 1 AND u.banned = 0'.$groups_cond.
          ($break_time?' AND (SELECT COUNT(*) FROM {p_forum_posts} p WHERE p.user_id = u.userid AND p.date >= '.$break_time.' AND moderated = 0) >= '.$minposts:'').
          ' ORDER BY active_post_count DESC, u.username LIMIT %d', $postlimit);
        if(!empty($getusers))
        {
          $cache = array();
          $cache['max_post_id'] = $last_post_id;
          while($user = $DB->fetch_array($getusers,null,MYSQL_ASSOC))
          {
            $cache['users'][] = $user;
            $cache['userids'][$user['userid']] = (int)$user['active_post_count'];
            if(count($cache['users']) > $postlimit) break;
          }
          $DB->free_result($getusers);
          if(!empty($SDCache) && ($SDCache instanceof SDCache) && $SDCache->IsActive())
          {
            $SDCache->write_var('plugin-activeposters', 'activeposters', $cache);
          }
        }
      }
      if(isset($cache['users']) && is_array($cache['users'])) //SD343 extra check
      foreach($cache['users'] AS $idx => $user)
      {
        $poster = SDUserCache::CacheUser($user['userid'], $user['username'], false, $printavatar); //SD343
        $avatar_conf['userid']     = $user['userid'];
        $avatar_conf['username']   = $poster['username'];
        $avatar_conf['post_count'] = $user['active_post_count'];
        $avatar_conf['user_link']  = $use_data ? $poster['profile_link'] : '';
        $avatar_conf['a_output']   = $poster['avatar'];
        $out .= ActivePostersOutput($pluginid, $avatar_conf);
      }
    }
    else if( ($forumname == 'vBulletin 3') || ($forumname == 'vBulletin 4') )
    {
      if(!empty($groups_cond))
      {
        $groups_cond = ' AND u.usergroupid '.$groups_cond;
      }
      $select = 'SELECT u.userid, u.username,
        (SELECT COUNT(*) FROM '.$tableprefix.'post p WHERE p.userid = u.userid AND p.dateline >= '.$break_time.' AND visible = 1) active_post_count
        FROM '.$tableprefix.'user u
        WHERE IFNULL(u.posts,0) > 0 '.$groups_cond.
        ($break_time?' AND (SELECT COUNT(*) FROM '.$tableprefix.'post p WHERE p.userid = u.userid AND p.dateline >= '.$break_time.' AND visible = 1) >= '.$minposts:'').
        ' ORDER BY active_post_count DESC LIMIT '.$postlimit;
      if($getusers = $DB->query($select))
      {
        while($user = $DB->fetch_array($getusers,null,MYSQL_ASSOC))
        {
          $avatar_conf['userid']     = $user['userid'];
          $avatar_conf['username']   = $user['username'];
          $avatar_conf['post_count'] = $user['active_post_count'];
          $avatar_conf['user_link']  = ForumLink(4, $user['userid']);
          $avatar_conf['a_output']   = sd_PrintAvatar($avatar_conf,true);
          $out .= ActivePostersOutput($pluginid, $avatar_conf);
        }
        $DB->free_result($getusers);
      }
    }
    else if($forumname == 'phpBB2')
    {
      //SD370: results were still for "top posters"
      if($getusers = $DB->query("SELECT u.user_id, u.username,
         (SELECT COUNT(*) FROM ".$tableprefix."posts p WHERE p.poster_id = u.user_id AND p.post_time > ".$break_time.") active_post_count
         FROM ".$tableprefix."users u
         WHERE (u.username != 'Anonymous') AND (u.user_id > 1) AND (u.user_active = 1)
         AND (IFNULL(u.user_posts,0) > 0)".
         ($break_time?' AND u.user_lastvisit >= '.$break_time.'
         AND (SELECT COUNT(*) FROM '.$tableprefix.'posts p WHERE p.poster_id = u.user_id AND p.post_time >= '.$break_time.') >= '.$minposts:'').
         ' ORDER BY active_post_count DESC LIMIT %d',$postlimit))
      {
        while($user = $DB->fetch_array($getusers,null,MYSQL_ASSOC))
        {
          $avatar_conf['userid']     = $user['user_id'];
          $avatar_conf['username']   = $user['username'];
          $avatar_conf['post_count'] = $user['active_post_count'];
          $avatar_conf['user_link']  = ForumLink(4, $user['user_id']);
          $avatar_conf['a_output']   = sd_PrintAvatar($avatar_conf,true);
          $out .= ActivePostersOutput($pluginid, $avatar_conf);
        }
        $DB->free_result($getusers);
      }
    }
    else if($forumname == 'phpBB3')
    {
      //SD370: fixed typo in SQL "posert_id" -> "poster_id"
      if($getusers = $DB->query("SELECT u.user_id, u.username,
         (SELECT COUNT(*) FROM ".$tableprefix."posts p WHERE p.poster_id = u.user_id AND p.post_time > ".$break_time." AND post_approved = 1) active_post_count
         FROM ".$tableprefix."users u
         WHERE (u.username != 'Anonymous') AND (u.user_id > 1) AND (IFNULL(u.user_posts,0) > 0) AND (u.group_id <> 13)".
         ($break_time?' AND u.user_lastvisit >= '.$break_time.'
         AND (SELECT COUNT(*) FROM '.$tableprefix.'posts p WHERE p.poster_id = u.user_id AND p.post_time >= '.$break_time.' AND post_approved = 1) >= '.$minposts:'').
         ' ORDER BY active_post_count DESC LIMIT %d',$postlimit))
      {
        while($user = $DB->fetch_array($getusers,null,MYSQL_ASSOC))
        {
          $avatar_conf['userid']     = $user['user_id'];
          $avatar_conf['username']   = $user['username'];
          $avatar_conf['post_count'] = $user['active_post_count'];
          $avatar_conf['user_link']  = ForumLink(4, $user['user_id']);
          $avatar_conf['a_output']   = sd_PrintAvatar($avatar_conf,true);
          $out .= ActivePostersOutput($pluginid, $avatar_conf);
        }
        $DB->free_result($getusers);
      }
    }
    else if(($forumname == 'Invision Power Board 2') || ($forumname == 'Invision Power Board 3'))
    {
      $userid_column = ($forumname == 'Invision Power Board 2') ? 'id' : 'member_id'; //SD322 id vs. member_id
      if($getusers = $DB->query("SELECT $userid_column, name, posts, members_display_name
         FROM " . $tableprefix . 'members
         WHERE posts > 0
         ORDER BY posts DESC LIMIT %d',$postlimit))
      {
        while($user = $DB->fetch_array($getusers,null,MYSQL_ASSOC))
        {
          $avatar_conf['userid']     = $user[$userid_column];
          $avatar_conf['username']   = $user['members_display_name'];
          $avatar_conf['post_count'] = $user['posts'];
          $avatar_conf['user_link']  = ForumLink(4, $user[$userid_column]);
          $avatar_conf['a_output']   = sd_PrintAvatar($avatar_conf,true);
          $out .= ActivePostersOutput($pluginid, $avatar_conf);
        }
        $DB->free_result($getusers);
      }
    }
    else if($forumname == 'Simple Machines Forum 1')
    {
      if($getusers = $DB->query('SELECT ID_MEMBER, memberName, posts
         FROM ' . $tableprefix . 'members
         WHERE posts > 0
         ORDER BY posts DESC LIMIT %d',$postlimit))
      {
        while($user = $DB->fetch_array($getusers,null,MYSQL_ASSOC))
        {
          $avatar_conf['userid']     = $user['ID_MEMBER'];
          $avatar_conf['username']   = $user['memberName'];
          $avatar_conf['post_count'] = $user['posts'];
          $avatar_conf['user_link']  = ForumLink(4, $user['ID_MEMBER']);
          $avatar_conf['a_output']   = sd_PrintAvatar($avatar_conf,true);
          $out .= ActivePostersOutput($pluginid, $avatar_conf);
        }
        $DB->free_result($getusers);
      }
    }
    else if($forumname == 'Simple Machines Forum 2')
    {
      if($getusers = $DB->query('SELECT id_member, real_name, posts
         FROM ' . $tableprefix . 'members
         WHERE posts > 0
         ORDER BY posts DESC LIMIT %d',$postlimit))
      {
        while($user = $DB->fetch_array($getusers,null,MYSQL_ASSOC))
        {
          $avatar_conf['userid']     = $user['id_member'];
          $avatar_conf['username']   = $user['real_name'];
          $avatar_conf['post_count'] = $user['posts'];
          $avatar_conf['user_link']  = ForumLink(4, $user['id_member']);
          $avatar_conf['a_output']   = sd_PrintAvatar($avatar_conf,true);
          $out .= ActivePostersOutput($pluginid, $avatar_conf);
        }
        $DB->free_result($getusers);
      }
    }
    else if($forumname == 'XenForo 1')
    {
      if($getusers = $DB->query('SELECT user_id, username, custom_title, message_count
         FROM ' . $tableprefix . "user
         WHERE message_count > 0 AND user_state = 'valid' AND is_banned = 0 AND visible = 1
         ORDER BY message_count DESC LIMIT %d",$postlimit))
      {
        while($user = $DB->fetch_array($getusers,null,MYSQL_ASSOC))
        {
          $avatar_conf['userid']     = $user['user_id'];
          $avatar_conf['username']   = $user['username'];
          $avatar_conf['post_count'] = $user['message_count'];
          $avatar_conf['user_link']  = ForumLink(4, $user['user_id'], $avatar_conf['username']);
          $avatar_conf['a_output']   = sd_PrintAvatar($avatar_conf,true);
          $out .= ActivePostersOutput($pluginid, $avatar_conf);
        }
        $DB->free_result($getusers);
      }
    }
    else if($forumname == 'MyBB') //SD370
    {
      if($getusers = $DB->query('SELECT u.uid, u.username,
         (SELECT COUNT(*) FROM '.$tableprefix.'posts p WHERE p.uid = u.uid AND p.dateline > '.$break_time.' AND visible = 1) active_post_count
         FROM '.$tableprefix.'users u
         INNER JOIN '.$tableprefix.'usergroups ug ON ug.gid = u.usergroup
         LEFT JOIN '.$tableprefix.'awaitingactivation a ON a.uid = u.uid
         WHERE (u.uid > 0) AND (a.uid IS NULL) AND (IFNULL(u.postnum,0) > 0) AND (ug.isbannedgroup = 0)'.
         ($break_time?' AND (u.lastactive >= '.$break_time.')
         AND ((SELECT COUNT(*) FROM '.$tableprefix.'posts p WHERE p.uid = u.uid AND p.dateline >= '.$break_time.' AND p.visible = 1) >= '.$minposts:'').')
         ORDER BY active_post_count DESC LIMIT %d',$postlimit))
      {
        while($user = $DB->fetch_array($getusers,null,MYSQL_ASSOC))
        {
          $avatar_conf['userid']     = $user['uid'];
          $avatar_conf['username']   = $user['username'];
          $avatar_conf['post_count'] = $user['active_post_count'];
          $avatar_conf['user_link']  = ForumLink(4, $user['uid'], $user['username']);
          $avatar_conf['a_output']   = sd_PrintAvatar($avatar_conf,true);
          $out .= ActivePostersOutput($pluginid, $avatar_conf);
        }
        $DB->free_result($getusers);
      }
    }
    else if($forumname == 'punBB') //SD370
    {
      $tmp = '';
      if($break_time)
      {
        $tmp = '
         AND (u.last_post >= '.$break_time.')
         AND ((SELECT COUNT(*) FROM '.$tableprefix.'posts p WHERE p.poster_id = u.id AND p.posted >= '.$break_time.') >= '.$minposts.')';
      }
      if($getusers = $DB->query('SELECT u.id, u.username,
         (SELECT COUNT(*) FROM '.$tableprefix.'posts p WHERE p.poster_id = u.id AND p.posted > '.$break_time.') active_post_count
         FROM '.$tableprefix.'users u
         INNER JOIN '.$tableprefix.'groups ug ON ug.g_id = u.group_id
         WHERE (u.id > 1) AND (u.group_id > 0)
         AND u.last_post IS NOT NULL AND (IFNULL(u.num_posts,0) > 0)'.
         $tmp.
         ' ORDER BY active_post_count DESC, IFNULL(u.last_post,0) DESC LIMIT %d',$postlimit))
      {
        while($user = $DB->fetch_array($getusers,null,MYSQL_ASSOC))
        {
          $avatar_conf['userid']     = $user['id'];
          $avatar_conf['username']   = $user['username'];
          $avatar_conf['post_count'] = $user['active_post_count'];
          $avatar_conf['user_link']  = ForumLink(4, $user['id'], $user['username']);
          $avatar_conf['a_output']   = sd_PrintAvatar($avatar_conf,true);
          $out .= ActivePostersOutput($pluginid, $avatar_conf);
        }
        $DB->free_result($getusers);
      }
    }

    echo $out.'
    </ul>
    </div>
    ';

    if($DB->database != $dbname) $DB->select_db($dbname);

  } //ActivePostersPlugin

} //if not exists - DO NOT REMOVE!

if(!empty($usersystem['name']))
{
  $sd_forum_installed = (GetPluginID('Forum') > 17);
  if(($usersystem['name'] != 'Subdreamer') || $sd_forum_installed)
  {
    $this_pluginid = GetPluginID('Active Posters');
    if(!empty($userinfo['pluginviewids']) && @in_array($this_pluginid,$userinfo['pluginviewids']))
    {
      ActivePostersPlugin($this_pluginid);
    }
    unset($this_pluginid);
  }
}
