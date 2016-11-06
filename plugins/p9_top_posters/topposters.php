<?php

if(!defined('IN_PRGM')) exit();

if(!function_exists('p9_TopPostersOutput'))
{
  function p9_TopPostersOutput($output_arr)
  {
    if(empty($output_arr['post_count'])) return;

    echo '<li id="p9-'.$output_arr['userid'].'">
      <div class="p9-outer">
        <div class="p9-avatar">'.$output_arr['a_output'].'</div>
        <div class="p9-user">';
    if(!empty($output_arr['user_link']))
    {
      echo '<a href="'.$output_arr['user_link'].'">'.$output_arr['username'].'</a>';
    }
    else
    {
      echo $output_arr['username'];
    }
    echo ': '.$output_arr['post_count'].'
        </div>
      </div>
    </li>
    ';
  }
}

if(!function_exists('p9_TopPosters'))
{
  function p9_TopPosters($pluginid)
  {
    global $DB, $dbname, $userinfo, $usersystem;

    $forumdbname = $usersystem['dbname'];
    $forumname   = $usersystem['name'];
    $forumpath   = $usersystem['folderpath'];
    $tableprefix = $usersystem['tblprefix'];

    // get language and settings
    $language  = GetLanguage($pluginid);
    $settings  = GetPluginSettings($pluginid);
    $postlimit = (int)$settings['number_of_top_posters_to_display'];
    $postlimit = empty($postlimit) ? 5 : $postlimit;

    $printavatar  = !empty($settings['display_avatar']);
    $printavatar &= (!isset($userinfo['profile']['user_view_avatars']) ||
                     !empty($userinfo['profile']['user_view_avatars']));
    if($printavatar)
    {
      $img_h = intval($settings['avatar_image_height']);
      $img_w = intval($settings['avatar_image_width']);
      if(!empty($img_h) & ($img_h > 1))
      {
        $settings['avatar_image_height'] = $img_h;//' height="'.$img_h.'" ';
      }
      else
      {
        $settings['avatar_image_height'] = '';
      }

      if(!empty($img_w) && ($img_w > 1))
      {
        $settings['avatar_image_width'] = $img_w;//' width="'.($img_w).'" ';
      }
      else
      {
        $settings['avatar_image_width'] = '';
      }
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

    // switch to forum database
    if($dbname != $forumdbname)
    {
      $DB->select_db($forumdbname);
    }

    // Start plugin contents
    echo '
    <div id="p9_top_posters">
    <div class="p9_header">'.$language['user'].' / '.$language['posts'].'</div>
    <ul>
    ';

    if($forumname == 'Subdreamer')
    {
      if($getusers = $DB->query(
        'SELECT userid, username FROM '.PRGM_TABLE_PREFIX.'users
         WHERE IFNULL(banned,0) = 0 AND activated = 1 AND IFNULL(user_post_count,0) > 0
         ORDER BY IFNULL(user_post_count,0) DESC LIMIT %d', $postlimit))
      {
        //SD343: user caching of poster, incl. avatar
        include_once(SD_INCLUDE_PATH.'class_sd_usercache.php'); //SD343
        SDUserCache::$avatar_height = $settings['avatar_image_height'];
        SDUserCache::$avatar_width  = $settings['avatar_image_width'];
        while($user = $DB->fetch_array($getusers))
        {
          $poster = SDUserCache::CacheUser($user['userid'], $user['username'], false, $printavatar);
          $avatar_conf = array(
            'userid'      => $poster['user_id'],
            'username'    => $poster['profile_link'],
            'post_count'  => $poster['post_count'],
            'user_link'   => '',
            'a_output'    => ($printavatar && $poster['avatar'] ? $poster['avatar'] : '')
            );
          p9_TopPostersOutput($avatar_conf);
        }
        $DB->free_result($getusers);
      }
    }
    else if( ($forumname == 'vBulletin 3') || ($forumname == 'vBulletin 4') )
    {
      if($getusers = $DB->query("SELECT userid, username, posts
        FROM ".$tableprefix."user WHERE IFNULL(posts,0) > 0
        ORDER BY IFNULL(posts,0) DESC LIMIT %d",$postlimit))
      {
        while($user = $DB->fetch_array($getusers))
        {
          $avatar_conf['userid']     = $user['userid'];
          $avatar_conf['username']   = $user['username'];
          $avatar_conf['post_count'] = $user['posts'];
          $avatar_conf['user_link']  = ForumLink(4, $user['userid']);
          $avatar_conf['a_output']   = sd_PrintAvatar($avatar_conf,true);
          p9_TopPostersOutput($avatar_conf);
        }
        $DB->free_result($getusers);
      }
    }
    else if($forumname == 'phpBB2')
    {
      if($getusers = $DB->query("SELECT user_id, username, user_posts
        FROM ".$tableprefix."users
        WHERE (username != 'Anonymous') AND (user_id > 1) AND (IFNULL(user_posts,0) > 0)
        ORDER BY user_posts DESC LIMIT %d", $postlimit))
      {
        while($user = $DB->fetch_array($getusers))
        {
          $avatar_conf['userid']     = $user['user_id'];
          $avatar_conf['username']   = $user['username'];
          $avatar_conf['post_count'] = $user['user_posts'];
          $avatar_conf['user_link']  = ForumLink(4, $user['user_id']);
          $avatar_conf['a_output']   = sd_PrintAvatar($avatar_conf,true);
          p9_TopPostersOutput($avatar_conf);
        }
        $DB->free_result($getusers);
      }
    }
    else if($forumname == 'phpBB3')
    {
      if($getusers = $DB->query("SELECT user_id, username, user_posts
         FROM ".$tableprefix."users
         WHERE (username != 'Anonymous') AND (user_id > 1) AND (IFNULL(user_posts,0) > 0) AND (group_id <> 13)
         ORDER BY user_posts DESC LIMIT %d",$postlimit))
      {
        while($user = $DB->fetch_array($getusers))
        {
          $avatar_conf['userid']     = $user['user_id'];
          $avatar_conf['username']   = $user['username'];
          $avatar_conf['post_count'] = $user['user_posts'];
          $avatar_conf['user_link']  = ForumLink(4, $user['user_id']);
          $avatar_conf['a_output']   = sd_PrintAvatar($avatar_conf,true);
          p9_TopPostersOutput($avatar_conf);
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
        while($user = $DB->fetch_array($getusers))
        {
          $avatar_conf['userid']     = $user[$userid_column];
          $avatar_conf['username']   = $user['members_display_name'];
          $avatar_conf['post_count'] = $user['posts'];
          $avatar_conf['user_link']  = ForumLink(4, $user[$userid_column]);
          $avatar_conf['a_output']   = sd_PrintAvatar($avatar_conf,true);
          p9_TopPostersOutput($avatar_conf);
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
        while($user = $DB->fetch_array($getusers))
        {
          $avatar_conf['userid']     = $user['ID_MEMBER'];
          $avatar_conf['username']   = $user['memberName'];
          $avatar_conf['post_count'] = $user['posts'];
          $avatar_conf['user_link']  = ForumLink(4, $user['ID_MEMBER']);
          $avatar_conf['a_output']   = sd_PrintAvatar($avatar_conf,true);
          p9_TopPostersOutput($avatar_conf);
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
        while($user = $DB->fetch_array($getusers))
        {
          $avatar_conf['userid']     = $user['id_member'];
          $avatar_conf['username']   = $user['real_name'];
          $avatar_conf['post_count'] = $user['posts'];
          $avatar_conf['user_link']  = ForumLink(4, $user['id_member']);
          $avatar_conf['a_output']   = sd_PrintAvatar($avatar_conf,true);
          p9_TopPostersOutput($avatar_conf);
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
        while($user = $DB->fetch_array($getusers))
        {
          $avatar_conf['userid']     = $user['user_id'];
          $avatar_conf['username']   = $user['username'];
          $avatar_conf['post_count'] = $user['message_count'];
          $avatar_conf['user_link']  = ForumLink(4, $user['user_id'], $avatar_conf['username']);
          $avatar_conf['a_output']   = sd_PrintAvatar($avatar_conf,true);
          p9_TopPostersOutput($avatar_conf);
        }
        $DB->free_result($getusers);
      }
    }
    else if($forumname == 'MyBB') //SD370
    {
      if($getusers = $DB->query('SELECT u.uid, u.username, u.postnum
         FROM '.$tableprefix.'users u
         INNER JOIN '.$tableprefix.'usergroups ug ON ug.gid = u.usergroup
         LEFT JOIN '.$tableprefix.'awaitingactivation a ON a.uid = u.uid
         WHERE u.postnum > 0 AND ug.isbannedgroup = 0 AND u.invisible = 0
         AND a.aid IS NULL
         ORDER BY u.postnum DESC LIMIT %d',$postlimit))
      {
        while($user = $DB->fetch_array($getusers))
        {
          $avatar_conf['userid']     = $user['uid'];
          $avatar_conf['username']   = $user['username'];
          $avatar_conf['post_count'] = $user['postnum'];
          $avatar_conf['user_link']  = ForumLink(4, $user['uid'], $avatar_conf['username']);
          $avatar_conf['a_output']   = sd_PrintAvatar($avatar_conf,true);
          p9_TopPostersOutput($avatar_conf);
        }
        $DB->free_result($getusers);
      }
    }
    else if($forumname == 'punBB') //SD370
    {
      if($getusers = $DB->query('SELECT u.id, u.username, u.num_posts
         FROM '.$tableprefix.'users u
         INNER JOIN '.$tableprefix.'groups ug ON ug.g_id = u.group_id
         WHERE u.id > 1 AND u.group_id > 0 AND u.num_posts > 0
         ORDER BY u.num_posts DESC LIMIT %d',$postlimit))
      {
        while($user = $DB->fetch_array($getusers))
        {
          $avatar_conf['userid']     = $user['id'];
          $avatar_conf['username']   = $user['username'];
          $avatar_conf['post_count'] = $user['num_posts'];
          $avatar_conf['user_link']  = ForumLink(4, $user['id'], $user['username']);
          $avatar_conf['a_output']   = sd_PrintAvatar($avatar_conf,true);
          p9_TopPostersOutput($avatar_conf);
        }
        $DB->free_result($getusers);
      }
    }

    echo '
    </ul>
    </div>
    ';

    // switch back to subdreamer database
    if($dbname != $forumdbname)
    {
      $DB->select_db($dbname);
    }

  } //p9_TopPosters

} //if not exists - DO NOT REMOVE!

if(!empty($usersystem['name']))
{
  $sd_forum_installed = (GetPluginID('Forum') > 17);
  if(($usersystem['name'] != 'Subdreamer') || $sd_forum_installed)
  {
    $p9_pluginid = GetPluginID('Top Posters');
    if(!empty($userinfo['pluginviewids']) && @in_array($p9_pluginid,$userinfo['pluginviewids']))
    {
      p9_TopPosters($p9_pluginid);
    }
  }
}
