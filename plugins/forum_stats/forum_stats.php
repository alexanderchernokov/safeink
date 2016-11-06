<?php

if(!defined('IN_PRGM')) exit();

// ################################ Forum Stats ################################

function Forum_Stats_Display()
{
  global $DB, $dbname, $userinfo, $usersystem;

  if(!$pluginid = GetPluginID('Forum Stats'))
  {
    return;
  }
  $forumdbname = $usersystem['dbname'];
  $forumname   = $usersystem['name'];
  $forumpath   = $usersystem['folderpath'];
  $tableprefix = $usersystem['tblprefix'];

  $language    = GetLanguage($pluginid);
  $settings    = GetPluginSettings($pluginid);

  // switch to forum database
  if($dbname != $forumdbname)
  {
    $DB->select_db($forumdbname);
  }

  echo '
  <div id="forum_stats">
  ';
  //SD322: support for SD Forum plugin
  if( $forumname == 'Subdreamer' )
  {
    $forum_id = GetPluginID('Forum');
    if(!empty($settings['member_count'])) // Display member count?
    {
      $DB->result_type = MYSQL_ASSOC;
      if($count = $DB->query_first('SELECT COUNT(*) c FROM {users}'.
                                   ' WHERE activated = 1 AND banned = 0'))
      {
        echo $language['members'] . ' <strong>' . number_format($count['c'],0) . '</strong>';
      }

      // Display the number of users having joined in the past 7 days:
      $DB->result_type = MYSQL_ASSOC;
      if(!empty($settings['last_7_days_count']))
      if($count = $DB->query_first('SELECT COUNT(*) c FROM {users}'.
                       ' WHERE joindate > %d AND activated = 1'.
                       ' AND banned = 0',
                       TIME_NOW - (86400*7)))
      {
        if(!empty($count['c']))
        {
          echo ' ('.$language['members_last_7_days'].' <strong>' . number_format($count['c'],0) . '</strong>)';
        }
      }
      echo '<br />';
    }

    //SD343: user caching of poster, incl. avatar
    include_once(SD_INCLUDE_PATH.'class_sd_usercache.php'); //SD343
    $use_data = !SDUserCache::IsBasicUCP() && !SD_IS_BOT; //SD343

    if(!empty($forum_id))
    {
      $IsAdmin = $userinfo['adminaccess'] ||
                 (!empty($userinfo['pluginadminids']) && @in_array($forum_id, $userinfo['pluginadminids']));
      $GroupCheck = '';
      if(!$IsAdmin)
      {
        // the usergroup id MUST be enclosed in pipes!
        $GroupCheck = " AND (IFNULL(access_view,'') LIKE '%|".$userinfo['usergroupid']."|%')";
      }

      if(!empty($settings['thread_count']) || !empty($settings['post_count']))
      {
        $DB->result_type = MYSQL_ASSOC;
        $sql = 'SELECT IFNULL(SUM(topic_count),0) t, IFNULL(SUM(post_count),0) p
                FROM {p_forum_forums}
                WHERE online = 1 AND is_category = 0
                '. $GroupCheck;
        $count = $DB->query_first($sql);
        // Display thread count?
        if(!empty($settings['thread_count']))
        {
          echo $language['threads'] .  ' <strong>' . number_format($count['t'],0) . '</strong><br />';
        }

        // Display post count?
        if(!empty($settings['post_count']))
        {
          echo $language['posts'] . ' <strong>' . number_format($count['p'],0) . '</strong><br />';
        }
      }

      // Display top poster?
      if(!empty($settings['top_poster']))
      {
        $DB->result_type = MYSQL_ASSOC;
        if($topposter = $DB->query_first('SELECT posts.user_id, posts.username, COUNT(*) c'.
           ' FROM {p_forum_posts} posts'.
           ' INNER JOIN {users} users on users.userid = posts.user_id'.
           ' INNER JOIN {usergroups} ug on ug.usergroupid = users.usergroupid'.
           ' WHERE users.banned = 0 AND ug.banned = 0'.
           " AND users.activated = 1 AND IFNULL(users.validationkey,'') = ''".
           ' AND IFNULL(posts.moderated,0) = 0'.
           ' GROUP BY posts.user_id, posts.username'.
           ' ORDER BY COUNT(*) DESC LIMIT 1'))
        {
          echo $language['top_poster'] . ' ';
          if($use_data)
          {
            $tmp = SDUserCache::CacheUser($topposter['user_id'], $topposter['username'], false, false);
            echo $tmp['forum'];
          }
          else
          {
            echo $topposter['username'];
          }
          echo ' (<strong>' . number_format(intval($topposter['c']),0) . '</strong>)<br />';
        }
      }
    }

    if(!empty($settings['welcome_message'])) // Display a welcome message to the newest member?
    {
      $DB->result_type = MYSQL_ASSOC;
      if($newest = $DB->query_first('SELECT userid, username FROM {users} users'.
                                   ' INNER JOIN {usergroups} ug on ug.usergroupid = users.usergroupid'.
                                   ' WHERE users.banned = 0 AND ug.banned = 0 AND users.activated = 1'.
                                   " AND IFNULL(users.validationkey,'') = ''".
                                   ' ORDER BY users.userid DESC LIMIT 1'))
      {
        echo '<br />' . $language['welcome_newest'] . ' <strong>';
        if($use_data)
        {
          $tmp = SDUserCache::CacheUser($newest['userid'], $newest['username'], false, false);
          echo $tmp['forum'];
        }
        else
        {
          echo stripslashes($newest['username']);
        }
        echo '</strong>';
      }
    }

  }
  else if( ($forumname == 'vBulletin 2') || ($forumname == 'vBulletin 3')  || ($forumname == 'vBulletin 4') )
  {
    if(!empty($settings['member_count'])) // Display member count?
    {
      if($count= $DB->query_first('SELECT COUNT(*) c FROM '.$tableprefix.'user'))
      {
        echo $language['members'] . ' ' . number_format($count['c'],0);
      }

      //SD370: Display the number of users having joined in the past 7 days:
      if(!empty($settings['last_7_days_count']))
      if($joined_week = $DB->query_first('SELECT COUNT(*) c
         FROM '.$tableprefix.'user u
         WHERE u.joindate > %d', TIME_NOW - (86400*7)))
      {
        if(!empty($joined_week[0]))
        {
          echo ' ('.$language['members_last_7_days'].' <strong>'.
               number_format($joined_week['c'],0) . '</strong>)';
        }
      }
      echo '<br />';
    }

    if(!empty($settings['thread_count'])) // Display thread count?
    {
      if($count = $DB->query_first('SELECT COUNT(*) c FROM '.$tableprefix.'thread'))
      {
        echo $language['threads'] .  ' ' . number_format($count['c'],0) . '<br />';
      }
    }

    if(!empty($settings['post_count'])) // Display post count?
    {
      if($count = $DB->query_first('SELECT COUNT(*) c FROM '.$tableprefix.'post'))
      {
        echo $language['posts'] . ' ' . number_format($count['c'],0) . '<br />';
      }
    }

    if(!empty($settings['top_poster'])) // Display top poster?
    {
      if($topposter = $DB->query_first('SELECT userid, username, posts FROM '.
                           $tableprefix.'user ORDER BY posts DESC LIMIT 1'))
      {
        echo $language['top_poster'] . ' <a href="' .
             ForumLink(4, $topposter[0]) . '">' . $topposter[1] . '</a> (' . $topposter[2] . ')<br />';
      }
    }

    if(!empty($settings['welcome_message'])) // Display a welcome message to the newest member?
    {
      if($newest = $DB->query_first('SELECT userid, username FROM '.
                        $tableprefix.'user ORDER BY joindate DESC LIMIT 1'))
      {
        echo '<br />' . $language['welcome_newest'] . '  <a href="' .
             ForumLink(4, $newest[0]) . '">' . $newest[1] . '</a>';
      }
    }

  }
  else if(($forumname == 'phpBB2') || ($forumname == 'phpBB3'))
  {
    if(!empty($settings['member_count'])) // Display member count?
    {
      if($forumname == 'phpBB2')
        $where = "username != 'Anonymous'";
      else
        $where = "username != 'Anonymous' AND user_type <> 2";
      if($count = $DB->query_first('SELECT COUNT(*) c FROM '.$tableprefix.
                                   'users WHERE '.$where))
      {
        echo $language['members'] . ' ' . number_format($count['c'],0);
      }

      //SD370: Display the number of users having joined in the past 7 days:
      if(!empty($settings['last_7_days_count']))
      if($joined_week = $DB->query_first('SELECT COUNT(*) c
         FROM '.$tableprefix.'users
         WHERE user_regdate > %d AND '.$where.'
         LIMIT 1', TIME_NOW - (86400*7)))
      {
        if(!empty($joined_week[0]))
        {
          echo ' ('.$language['members_last_7_days'].' <strong>'.
               number_format($joined_week['c'],0) . '</strong>)';
        }
      }
      echo '<br />';
    }

    if(!empty($settings['thread_count'])) // Display thread count?
    {
      if($count = $DB->query_first('SELECT COUNT(*) c FROM '.$tableprefix.'topics'))
      {
        echo $language['threads'] .  ' ' . number_format($count['c'],0) . '<br />';
      }
    }

    if(!empty($settings['post_count'])) // Display post count?
    {
      if($count = $DB->query_first('SELECT COUNT(*) c FROM '.$tableprefix.'posts'))
      {
        echo $language['posts'] . ' ' . number_format($count['c'],0) . '<br />';
      }
    }

    if(!empty($settings['top_poster'])) // Display top poster?
    {
      $where = ($forumname == 'phpBB3' ? ' AND user_type <> 2 ': '');
      if($top = $DB->query_first('SELECT user_id, username, user_posts FROM '.
         $tableprefix."users WHERE username != 'Anonymous' ".$where.
         " ORDER BY user_posts DESC LIMIT 1"))
      {
        echo $language['top_poster'] . ' <a href="' .
             ForumLink(4, $top[0]) . '">' . $top['username'] . '</a> (' . $top['user_posts'] . ')<br />';
      }
    }

    if(!empty($settings['welcome_message'])) // Display a welcome message to the newest member?
    {
      $where = ($forumname == 'phpBB3' ? 'AND user_type <> 2': '');
      if($newest = $DB->query_first('SELECT user_id, username FROM '.$tableprefix.
         "users WHERE username != 'Anonymous' ".$where.
         " ORDER BY user_regdate DESC LIMIT 1"))
      {
        echo '<br />' . $language['welcome_newest'] . ' <a href="' .
             ForumLink(4, $newest[0]) . '">' . $newest['username'] . '</a>';
      }
    }
  }
  else if(($forumname == 'Invision Power Board 2') || ($forumname == 'Invision Power Board 3'))
  {
    if(!empty($settings['member_count'])) // Display member count?
    {
      if($count = $DB->query_first('SELECT COUNT(*) c FROM '.$tableprefix.'members'))
      {
        echo $language['members'] . ' ' . number_format($count['c'],0);
      }

      //SD370: Display the number of users having joined in the past 7 days:
      if(!empty($settings['last_7_days_count']))
      if($joined_week = $DB->query_first('SELECT COUNT(*) c
         FROM '.$tableprefix.'members u
         WHERE u.joined > %d',
         TIME_NOW - (86400*7)))
      {
        if(!empty($joined_week[0]))
        {
          echo ' ('.$language['members_last_7_days'].' <strong>'.
               number_format($joined_week['c'],0) . '</strong>)';
        }
      }
      echo '<br />';
    }

    if(!empty($settings['thread_count'])) // Display thread count?
    {
      if($count = $DB->query_first('SELECT COUNT(*) c FROM '.$tableprefix.'topics'))
      {
        echo $language['threads'] .  ' ' . number_format($count['c'],0) . '<br />';
      }
    }

    if(!empty($settings['post_count'])) // Display post count?
    {
      if($count = $DB->query_first('SELECT COUNT(*) c FROM '.$tableprefix.'posts'))
      {
        echo $language['posts'] . ' ' . number_format($count['c'],0) . '<br />';
      }
    }

    if($forumname == 'Invision Power Board 2')
      $fld_id = 'id';
    else
      $fld_id = 'member_id';

    if(!empty($settings['top_poster'])) // Display top poster?
    {
      if($topposter = $DB->query_first('SELECT '.$fld_id.', name, posts FROM '.
                           $tableprefix.'members ORDER BY posts DESC LIMIT 1'))
      {
        echo $language['top_poster'] . ' <a href="' .
             ForumLink(4, $topposter[0]) . '">' . $topposter[1] . '</a> (' . $topposter[2] . ')<br />';
      }
    }

    if(!empty($settings['welcome_message'])) // Display a welcome message to the newest member?
    {
      if($newest = $DB->query_first('SELECT '.$fld_id.', name FROM '.$tableprefix.
                        'members ORDER BY joined DESC LIMIT 1'))
      {
        echo '<br />' . $language['welcome_newest'] . '  <a href="' .
             ForumLink(4, $newest[0]) . '">' . $newest['name'] . '</a>';
      }
    }
  }
  else if($forumname == 'Simple Machines Forum 1')
  {
    if(!empty($settings['member_count'])) // Display member count?
    {
      if($count = $DB->query_first('SELECT COUNT(*) c FROM '.$tableprefix.'members'))
      {
        echo $language['members'] . ' ' . number_format($count['c'],0);
      }

      //SD370: Display the number of users having joined in the past 7 days:
      if(!empty($settings['last_7_days_count']))
      if($joined_week = $DB->query_first('SELECT COUNT(*) c
         FROM '.$tableprefix.'members u
         WHERE u.dateRegistered > %d',
         TIME_NOW - (86400*7)))
      {
        if(!empty($joined_week[0]))
        {
          echo ' ('.$language['members_last_7_days'].' <strong>'.
               number_format($joined_week['c'],0) . '</strong>)';
        }
      }
      echo '<br />';
    }

    if(!empty($settings['thread_count'])) // Display thread count?
    {
      if(list($count) = $DB->query_first('SELECT SUM(numTopics) c FROM '.
                                         $tableprefix.'boards'))
      {
        echo $language['threads'] . ' ' . number_format($count,0) . '<br />';
      }
    }

    if(!empty($settings['post_count'])) // Display post count?
    {
      if(list($count) = $DB->query_first('SELECT SUM(numPosts) c FROM '.
                                         $tableprefix.'boards'))
      {
        echo $language['posts'] . ' ' . number_format($count,0) . '<br />';
      }
    }

    if(!empty($settings['top_poster'])) // Display top poster?
    {
      if($topposter = $DB->query_first('SELECT ID_MEMBER, realName, posts FROM '.
         $tableprefix.'members ORDER BY posts DESC LIMIT 1'))
      {
        echo $language['top_poster'] . ' <a href="' .
             ForumLink(4, $topposter[0]) . '">' . $topposter[1] . '</a> (' . $topposter['posts'] . ')<br />';
      }
    }

    if(!empty($settings['welcome_message'])) // Display a welcome message to the newest member?
    {
      if($newest = $DB->query_first('SELECT ID_MEMBER, realName FROM '.$tableprefix.
                                    'members ORDER BY dateRegistered DESC LIMIT 1'))
      {
        echo '<br />' . $language['welcome_newest'] . '  <a href="' .
             ForumLink(4, $newest[0]) . '">' . $newest[1] . '</a>';
      }
    }
  }
  else if($forumname == 'Simple Machines Forum 2')
  {
    if(!empty($settings['member_count'])) // Display member count?
    {
      //SD370: added "is_activated"
      if(list($count) = $DB->query_first('SELECT COUNT(*) c FROM '.
         $tableprefix.'members WHERE is_activated = 1'))
      {
        echo $language['members'] . ' ' . number_format($count,0);
      }

      //SD370: Display the number of users having joined in the past 7 days:
      if(!empty($settings['last_7_days_count']))
      if($joined_week = $DB->query_first('SELECT COUNT(*) c
         FROM '.$tableprefix.'members u
         WHERE u.date_registered > %d',
         TIME_NOW - (86400*7)))
      {
        if(!empty($joined_week[0]))
        {
          echo ' ('.$language['members_last_7_days'].' <strong>'.
               number_format($joined_week['c'],0) . '</strong>)';
        }
      }
      echo '<br />';
    }

    if(!empty($settings['thread_count'])) // Display thread count?
    {
      //SD370: added "approved"
      if(list($count) = $DB->query_first('SELECT COUNT(*) c FROM '.
         $tableprefix.'topics WHERE approved = 1'))
      {
        echo $language['threads'] . ' ' . number_format($count,0) . '<br />';
      }
    }

    if(!empty($settings['post_count'])) // Display post count?
    {
      //SD370: added "approved"
      if(list($count) = $DB->query_first('SELECT COUNT(*) c FROM '.
         $tableprefix.'messages WHERE approved = 1'))
      {
        echo $language['posts'] . ' ' . number_format($count,0) . '<br />';
      }
    }

    if(!empty($settings['top_poster'])) // Display top poster?
    {
      if($topposter = $DB->query_first('SELECT id_member, real_name, posts FROM '.
         $tableprefix.'members ORDER BY posts DESC LIMIT 1'))
      {
        echo $language['top_poster'] . ' <a href="' .
             ForumLink(4, $topposter[0]) . '">' . $topposter[1] . '</a> (' . $topposter['posts'] . ')<br />';
      }
    }

    if(!empty($settings['welcome_message'])) // Display a welcome message to the newest member?
    {
      if($newest = $DB->query_first('SELECT id_member, real_name FROM '.
         $tableprefix.'members ORDER BY date_registered DESC LIMIT 1'))
      {
        echo '<br />' . $language['welcome_newest'] . '  <a href="' .
             ForumLink(4, $newest[0]) . '">' . $newest[1] . '</a>';
      }
    }
  }
  else if($forumname == 'XenForo 1')
  {
    if(!empty($settings['member_count'])) // Display member count?
    {
      if(list($count) = $DB->query_first('SELECT COUNT(*) c FROM '.
         $tableprefix."user WHERE user_state = 'valid'
         AND is_banned = 0 AND visible = 1"))
      {
        echo $language['members'] . ' ' . number_format($count,0);
      }

      //SD370: Display the number of users having joined in the past 7 days:
      if(!empty($settings['last_7_days_count']))
      if($joined_week = $DB->query_first('SELECT COUNT(*) c
         FROM '.$tableprefix."user u
         WHERE register_date >= %d AND user_state = 'valid'
         AND is_banned = 0 AND visible = 1",
         TIME_NOW - (86400*7)))
      {
        if(!empty($joined_week[0]))
        {
          echo ' ('.$language['members_last_7_days'].' <strong>'.
               number_format($joined_week['c'],0) . '</strong>)';
        }
      }
      echo '<br />';
    }

    if(!empty($settings['thread_count'])) // Display thread count?
    {
      if(list($count) = $DB->query_first('SELECT COUNT(*) c FROM '.
         $tableprefix."thread WHERE discussion_state = 'visible'
         AND discussion_type <> 'redirect'"))
      {
        echo $language['threads'] . ' ' . number_format($count,0) . '<br />';
      }
    }

    if(!empty($settings['post_count'])) // Display post count?
    {
      if(list($count) = $DB->query_first('SELECT COUNT(*) c FROM '.
         $tableprefix."post WHERE message_state = 'visible'"))
      {
        echo $language['posts'] . ' ' . number_format($count,0) . '<br />';
      }
    }

    if(!empty($settings['top_poster'])) // Display top poster?
    {
      if($top = $DB->query_first('SELECT user_id, username, message_count FROM '.
         $tableprefix."user WHERE user_state = 'valid'
         ORDER BY message_count DESC LIMIT 1"))
      {
        echo $language['top_poster'] . ' <a href="' .
             ForumLink(4, $top['user_id']) . '">' . $top['username'] . '</a> (' . $top['message_count'] . ')<br />';
      }
    }

    if(!empty($settings['welcome_message'])) // Display a welcome message to the newest member?
    {
      if($newest = $DB->query_first('SELECT user_id, username FROM '.
         $tableprefix."user WHERE user_state = 'valid'
         ORDER BY register_date DESC LIMIT 1"))
      {
        echo '<br />' . $language['welcome_newest'] . '  <a href="' .
             ForumLink(4, $newest['user_id']) . '">' . $newest['username'] . '</a>';
      }
    }
  }
  else if($forumname == 'MyBB') //SD370
  {
    if(!empty($settings['member_count'])) // Display member count?
    {
      if(list($count) = $DB->query_first('SELECT COUNT(*) c
         FROM '.$tableprefix.'users WHERE uid > 0 AND invisible = 0'))
      {
        echo $language['members'] . ' ' . number_format($count,0);
      }

      // Display the number of users having joined in the past 7 days:
      if(!empty($settings['last_7_days_count']))
      if($joined_week = $DB->query_first('SELECT COUNT(*) c
         FROM '.$tableprefix.'users u
         INNER JOIN '.$tableprefix.'usergroups ug ON ug.gid = u.usergroup
         LEFT JOIN '.$tableprefix.'awaitingactivation a ON a.uid = u.uid
         WHERE u.regdate > %d AND a.uid IS NULL AND ug.isbannedgroup = 0',
         TIME_NOW - (86400*7)))
      {
        if(!empty($joined_week[0]))
        {
          echo ' ('.$language['members_last_7_days'].' <strong>'.
               number_format($joined_week['c'],0) . '</strong>)';
        }
      }
      echo '<br />';
    }

    if(!empty($settings['thread_count'])) // Display thread count?
    {
      if(list($count) = $DB->query_first('SELECT SUM(threads) c
         FROM '.$tableprefix."forums
         WHERE type = 'f' AND active = 1"))
      {
        echo $language['threads'] . ' ' . number_format($count,0) . '<br />';
      }
    }

    if(!empty($settings['post_count'])) // Display post count?
    {
      if(list($count) = $DB->query_first('SELECT SUM(posts) c
         FROM '.$tableprefix."forums
         WHERE type = 'f' AND active = 1"))
      {
        echo $language['posts'] . ' ' . number_format($count,0) . '<br />';
      }
    }

    if(!empty($settings['top_poster'])) // Display top poster?
    {
      if($top = $DB->query_first('SELECT u.uid, u.username, u.postnum
        FROM '.$tableprefix.'users u
        INNER JOIN '.$tableprefix.'usergroups ug ON ug.gid = u.usergroup
        WHERE u.uid > 0 AND u.invisible = 0 AND ug.isbannedgroup = 0
        ORDER BY u.postnum DESC LIMIT 1'))
      {
        echo $language['top_poster'] . ' <a href="' .
             ForumLink(4, $top['uid']) . '">' . $top['username'] . '</a> (' . $top['postnum'] . ')<br />';
      }
    }

    if(!empty($settings['welcome_message'])) // Display a welcome message to the newest member?
    {
      if($newest = $DB->query_first('SELECT u.uid, u.username
        FROM '.$tableprefix.'users u
        INNER JOIN '.$tableprefix.'usergroups ug ON ug.gid = u.usergroup
        WHERE u.uid > 0 AND u.invisible = 0 AND ug.isbannedgroup = 0
        ORDER BY u.regdate DESC LIMIT 1'))
      {
        echo '<br />' . $language['welcome_newest'] . ' <a href="' .
             ForumLink(4, $newest['uid']) . '">' . $newest['username'] . '</a>';
      }
    }
  }
  else if($forumname == 'punBB') //SD370
  {
    if(!empty($settings['member_count'])) // Display member count?
    {
      if(list($count) = $DB->query_first('SELECT COUNT(*) c FROM '.
                             $tableprefix.'users WHERE id > 1'.
                             ' AND group_id > 0 AND group_id <> 2'))
      {
        echo $language['members'] . ' ' . number_format($count,0);
      }

      // Display the number of users having joined in the past 7 days:
      if(!empty($settings['last_7_days_count']))
      if($joined_week = $DB->query_first('SELECT COUNT(*) c
         FROM '.$tableprefix.'users u
         WHERE u.group_id > 0 AND u.registered > %d', TIME_NOW - (86400*7)))
      {
        if(!empty($joined_week[0]))
        {
          echo ' ('.$language['members_last_7_days'].' <strong>'.
               number_format($joined_week['c'],0) . '</strong>)';
        }
      }
      echo '<br />';
    }

    if(!empty($settings['thread_count'])) // Display thread count?
    {
      if(list($count) = $DB->query_first('SELECT SUM(num_topics) c
         FROM '.$tableprefix.'forums WHERE num_topics > 0'))
      {
        echo $language['threads'] . ' ' . number_format($count,0) . '<br />';
      }
    }

    if(!empty($settings['post_count'])) // Display post count?
    {
      if(list($count) = $DB->query_first('SELECT SUM(num_posts) c
         FROM '.$tableprefix.'forums WHERE num_posts > 0'))
      {
        echo $language['posts'] . ' ' . number_format($count,0) . '<br />';
      }
    }

    if(!empty($settings['top_poster'])) // Display top poster?
    {
      if($top = $DB->query_first('SELECT id, username, num_posts
        FROM '.$tableprefix.'users
        WHERE id > 0 AND group_id > 0
        ORDER BY num_posts DESC LIMIT 1'))
      {
        echo $language['top_poster'] . ' <a href="' .
             ForumLink(4, $top['id']) . '">' . $top['username'] . '</a> (' . $top['num_posts'] . ')<br />';
      }
    }

    if(!empty($settings['welcome_message'])) // Display a welcome message to the newest member?
    {
      if($newest = $DB->query_first('SELECT id, username, num_posts
        FROM '.$tableprefix.'users
        WHERE id > 0 AND group_id > 0
        ORDER BY registered DESC LIMIT 1'))
      {
        echo '<br />' . $language['welcome_newest'] . ' <a href="' .
             ForumLink(4, $newest['id']) . '">' . $newest['username'] . '</a>';
      }
    }
  }

  echo '
  </div>
  ';

  // switch back to subdreamer database
  if($DB->database != $dbname)
  {
    $DB->select_db($dbname);
  }

} //Forum_Stats_Display


// ############################ Start Forum Stats #############################

if(!empty($usersystem))
{
  Forum_Stats_Display();
}
