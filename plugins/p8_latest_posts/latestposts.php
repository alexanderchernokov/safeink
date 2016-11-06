<?php
if(!defined('IN_PRGM')) exit();

//SD342: for turkish vBulletin 3 forum integration with tables not in UTF8
// this function simply replaces wrong characters to correct turkish ones.
// This may not be complete or depending on system configurations may
// require more or different entries.
// In "admin/branding.php" this line must exist to enable:
// define('SD_FIX_TURKISH', true);
if(defined('SD_FIX_TURKISH') && !function_exists('sd_fix_turkish'))
{
  function sd_fix_turkish($string)
  {
    // 'ç,ş,ı,ğ,ö,ü'
    if(!isset($string) || !strlen($string)) return '';
    $replace_cyr = array(
    'ý' => 'ı',
    'Ý' => 'İ',
    'þ' => 'ş',
    'Þ' => 'Ş',
    'ð' => 'ğ',
    );

    return str_replace(array_keys($replace_cyr), array_values($replace_cyr), $string);

  } //sd_fix_turkish
}


if(!function_exists('p8_ShortenTitle'))
{
  function p8_ShortenTitle($settings, $title)
  {
    $chars = empty($settings['shorten_titles']) ? 0 : Is_Valid_Number($settings['shorten_titles'],0,0,2000);
    if(!empty($chars) && (strlen($title) > $chars))
    {
      $title = sd_substr($title,0,$chars);
      if(false !== ($pos = strrpos($title,' ')))
      {
        $title = sd_substr($title,0,$pos);
      }
      $title = trim($title) . '...';
    }
    return $title;
  } //p8_ShortenTitle
}

if(!function_exists('p8_LatestPosts'))
{
  function p8_LatestPosts($pluginid)
  {
    global $DB, $categoryid, $sdurl, $dbname, $mainsettings, $userinfo, $usersystem,
           $sd_charset, $sd_forum_installed;

    $forumdbname = $usersystem['dbname'];
    $forumname   = $usersystem['name'];
    $forumpath   = $usersystem['folderpath'];
    $tableprefix = $usersystem['tblprefix'];

    // get language
    $language = GetLanguage($pluginid);

    // get settings and init variables
    $settings = GetPluginSettings($pluginid);

    $postlimit = (empty($settings['number_of_posts_to_display']) ? 5 : (int)$settings['number_of_posts_to_display']);
    $postlimit = Is_Valid_Number($postlimit, 5, 1, 999); //SD370
    $wordwrap  = empty($settings['word_wrap'])?0:(int)$settings['word_wrap'];
    $forumids  = $settings['exclude_forums'];
    $first_topic = empty($settings['thread_display']);

    $columnid  = '';
    if(($forumname == 'Subdreamer') || ($sd_forum_installed && !empty($settings['forum_plugin'])))
    {
      $columnid = 'ff.forum_id';
    }
    else if( (substr($forumname,0,9) == 'vBulletin') )
    {
      $columnid = 'forumid';
    }
    else if($forumname == 'phpBB2' || $forumname == 'phpBB3')
    {
      $columnid = 'forum_id';
    }
    else if(($forumname == 'Invision Power Board 2') || ($forumname == 'Invision Power Board 3'))
    {
      $columnid = 'forum_id';
    }
    else if($forumname == 'Simple Machines Forum 1')
    {
      $columnid = 'ID_BOARD';
    }
    else if($forumname == 'Simple Machines Forum 2')
    {
      $columnid = 'id_board';
    }
    else if($forumname == 'XenForo 1')
    {
      $columnid = 'node_id';
    }
    else if($forumname == 'MyBB') //SD370
    {
      $columnid = 'f.fid';
    }
    else if($forumname == 'punBB') //SD370
    {
      $columnid = 'f.id';
    }

    $extraquery = '';
    $forumids = trim(str_replace(',', ' ', $forumids));
    if(strlen($forumids))
    {
      if($privateids = sd_ConvertStrToArray($forumids,' '))
      {
        // Make sure user input is an integer value
        for($i = 0, $pid = count($privateids); $i < $pid; $i++)
        {
          if(!ctype_digit($privateids[$i])) unset($privateids[$i]);
        }
        if(count($privateids))
        {
          $extraquery = ' WHERE NOT (' . $columnid . ' IN (' .implode(',',$privateids). ')) ';
        }
      }
    }

    $ForumLinkFuncOK = function_exists('ForumLink');

    $printavatar = !empty($settings['display_avatar']) &&
                   (!isset($userinfo['profile']['user_view_avatars']) ||
                    !empty($userinfo['profile']['user_view_avatars']));
    if($printavatar)
    {
      $img_h = (int)$settings['avatar_image_height'];
      $img_w = (int)$settings['avatar_image_width'];
      if(empty($img_h) || empty($img_w))
      {
        $settings['avatar_image_height'] = (int)$mainsettings['default_avatar_height'];
        $settings['avatar_image_width']  = (int)$mainsettings['default_avatar_width'];
      }
    }

    // Config array as parameter for sd_PrintAvatar (in globalfunctions.php)
    $avatar_conf = array(
      'output_ok' => $printavatar,
      'userid'    => -1,
      'username'  => '',
      'Avatar Column'       => !empty($settings['avatar_column']),
      'Avatar Image Height' => $settings['avatar_image_height'],
      'Avatar Image Width'  => $settings['avatar_image_width']
      );

    echo '
    <table id="p8_latest_posts" border="0" cellpadding="0" cellspacing="0" width="100%">
    ';

    // switch to forum database
    if(($forumname != 'Subdreamer') && (!$sd_forum_installed || empty($settings['forum_plugin']))) //SD342
    if($DB->database != $forumdbname)
    {
      $DB->select_db($forumdbname);
    }
    if(($forumname == 'Subdreamer') || ($sd_forum_installed && !empty($settings['forum_plugin'])))
    {
      $forum_id = GetPluginID('Forum');
      if($forum_cat_id = $DB->query_first("select categoryid from {pagesort} where pluginid = '%d'",$forum_id))
      {
        $forum_cat_id = (int)$forum_cat_id['categoryid'];
      }
      else
      {
        $forum_cat_id = $categoryid;
      }
      $GroupCheck = '';
      $IsAdmin = !empty($userinfo['adminaccess']) ||
                 (!empty($userinfo['pluginadminids']) && @in_array($forum_id, $userinfo['pluginadminids']));

      if(!$IsAdmin)
      {
        // the usergroup id MUST be enclosed in pipes!
        $extraquery .= (empty($extraquery) ? ' WHERE ' : ' AND ').
                       "((IFNULL(ff.access_view,'') = '') OR ".
                       "(ff.access_view like '%|".$userinfo['usergroupid']."|%'))";
      }
      $extraquery .= (empty($extraquery) ? ' WHERE ' : ' AND ').
                     ' ff.is_category = 0 AND ff.online = 1'.
                     ' AND ft.moderated = 0 AND fp.moderated = 0';
      if($getthreads = $DB->query('SELECT ft.title, ft.topic_id, ft.last_post_id,'.
         ' ft.last_post_username, ft.last_post_date, fp.user_id, fp.username'.
         ' FROM {p_forum_topics} ft'.
         ' INNER JOIN {p_forum_forums} ff ON ff.forum_id = ft.forum_id'.
         ' INNER JOIN {p_forum_posts} fp ON fp.post_id = ft.last_post_id AND fp.moderated = 0 '.
         $extraquery .
         ' ORDER BY ft.last_post_date DESC LIMIT '.(int)$postlimit))
      {
        //SD343: forum config class (for static calls to topic rewrite function)
        global $sd_forum_config;
        if(!isset($sd_forum_config))
        {
          include_once(ROOT_PATH.'plugins/forum/forum_config.php');
          $sd_forum_config = new SDForumConfig();
        }
        $sd_forum_config->InitFrontpage();
        //SD343: user caching of poster, incl. avatar
        include_once(SD_INCLUDE_PATH.'class_sd_usercache.php'); //SD343
        SDUserCache::$avatar_height = $settings['avatar_image_height'];
        SDUserCache::$avatar_width  = $settings['avatar_image_width'];
        $use_data = !SDUserCache::IsBasicUCP() && !SD_IS_BOT;
        $seo_enabled = !empty($mainsettings['modrewrite']); //SD343

        while($thread = $DB->fetch_array($getthreads,null,MYSQL_ASSOC))
        {
          $poster = SDUserCache::CacheUser($thread['user_id'], $thread['last_post_username'], false, $printavatar);
          //SD343: SEO topic titles
          $res = $sd_forum_config->RewriteTopicLink($thread['topic_id'],$thread['title'],1,true);
          if(!$first_topic) //SD343
          {
            $res['link'] .= (strpos($res['link'],'?')===false?'?':'&amp;').
                            'post_id='.$thread['last_post_id'].
                            '#p'.$thread['last_post_id'];
          }
          $link = $res['link'];
          $thread['title'] = p8_ShortenTitle($settings, $thread['title']);
          $thread['title'] = sd_wordwrap($thread['title'], $wordwrap, '<br />', 1);
          $thread['postername'] = sd_wordwrap($poster['username'], $wordwrap, '<br />', 1);
          echo '
          <tr><td>' . ($printavatar?$poster['avatar'].'</td><td>':'') .
            '<a href="'.$link.'">'.$thread['title'].'</a><br />
            '.$language['posted_by'].' '.($use_data ? $poster['profile_link'] : $poster['username']);

          if(!empty($settings['display_post_date']))
          {
            echo '<br />'. DisplayDate($thread['last_post_date'], '', true);
          }
          echo '</td>
          </tr>';
        }
        $DB->free_result($getthreads);
      }
    }
    else if(($forumname == 'vBulletin 3') || ($forumname == 'vBulletin 4'))
    {
      $extraquery = strlen($extraquery) ? $extraquery . ' AND visible = 1 ' : ' WHERE visible = 1 ';

      $vb36 = false;
      $getversion = $DB->query_first('SELECT value FROM ' . $tableprefix . "setting WHERE varname = 'templateversion'");
      if(isset($getversion['value']))
      {
        $ver = explode('.', $getversion['value']);
        if(intval($ver[0]) > 3 || (intval($ver[0]) == 3 && intval($ver[1]) >= 6))
        {
          $vb36 = true;
        }
      }
      if($vb36)
      {
        $res = ($getthreads = $DB->query('SELECT title, threadid, lastposter, lastpostid, lastpost, forumid'.
          ' FROM `'.$tableprefix.'thread` '.$extraquery.
          ' ORDER BY lastpost DESC LIMIT %d',$postlimit));
      }
      else
      {
        $res = ($getthreads = $DB->query('SELECT title, threadid, lastposter, lastpost, forumid'.
          ' FROM '.$tableprefix.'thread '.$extraquery.
          ' ORDER BY lastpost DESC LIMIT %d',$postlimit));
      }

      if($res)
      {
        // prefetch permissions of current user for forum
        $permlist = array();
        $perm = @implode(',', $userinfo['forumusergroupids']);
        if(isset($perm) && strlen(trim($perm)))
        {
          if($getperm = $DB->query('SELECT * FROM `'.$tableprefix.'forumpermission`
            WHERE usergroupid IN (%s) ORDER BY usergroupid',
            $perm))
          {
            while($perm = $DB->fetch_array($getperm,null,MYSQL_ASSOC))
            {
              $permlist[$perm['forumid']] = (isset($permlist[$perm['forumid']]) ? $permlist[$perm['forumid']] : $perm['forumpermissions']) & $perm['forumpermissions'];
            }
          }
        }

        $check_redir = $DB->table_exists($tableprefix.'threadredirect'); //SD343

        while($thread = $DB->fetch_array($getthreads,null,MYSQL_ASSOC))
        {
          // check for "redirected" (moved) threads and if the user
          // has permission for the forum where the thread is located in
          $is_redir = false;
          if($check_redir)
          {
            $is_redir = $DB->query_first('SELECT 1 FROM '.$tableprefix.'threadredirect
               WHERE threadid = %d AND expires >= %d',
               $thread['threadid'], TIME_NOW);
            $is_redir = !empty($is_redir);
          }

          if(TRUE || !$is_redir && (!isset($permlist[$thread['forumid']]) ||
            (($permlist[$thread['forumid']] & 1 == 1) || ($permlist[$thread['forumid']] & 2 == 2) || ($permlist[$thread['forumid']] & 524288 == 524288)) ))
            // permissions: "Can View Forum", "Can View Other's Threads". "Can View Thread Content"
          {
            $thread['title'] = p8_ShortenTitle($settings, $thread['title']); //2012-04-18
            if(defined('SD_FIX_TURKISH')) //SD342: if defined in e.g. branding.php
            {
              $thread['title'] = sd_wordwrap(sd_fix_turkish($thread['title']), $wordwrap, '<br />', 1);
              $thread['postername'] = sd_wordwrap(sd_fix_turkish($thread['lastposter']), $wordwrap, '<br />', 1);
              $avatar_conf['username'] = sd_fix_turkish($thread['lastposter']);
            }
            else
            {
              $thread['title'] = sd_wordwrap($thread['title'], $wordwrap, '<br />', 1);
              $thread['postername'] = sd_wordwrap($thread['lastposter'], $wordwrap, '<br />', 1);
              $avatar_conf['username'] = $thread['lastposter'];
            }

            echo '
            <tr><td>' . sd_PrintAvatar($avatar_conf) . '
              <a href="' . $sdurl . $forumpath . 'showthread.php?';
            if($vb36)
            {
              echo ($first_topic ? 't='.$thread['threadid'] : 'p='.$thread['lastpostid'].'#post'.$thread['lastpostid']) .
                '">' . $thread['title'] . '</a>';
            }
            else
            {
              echo 'threadid=' . $thread['threadid'] . ($first_topic ? '' : '&goto=lastpost').
                '">' . $thread['title'] . '</a>';
            }
            echo '<br />' . $language['posted_by'] . ' ' . $thread['postername'];

            if(!empty($settings['display_post_date']))
            {
              echo '<br />'. DisplayDate($thread['lastpost'], '', 1);
            }
            echo '<br /><br /></td>
            </tr>';
          }
        }
        $DB->free_result($getthreads);
      }
    }
    else if($forumname == 'phpBB2')
    {
      if($getposts = $DB->query('SELECT post_id, topic_id, poster_id, post_username, post_time'.
         ' FROM '.$tableprefix.'posts '.$extraquery.
         ' ORDER BY post_id DESC LIMIT %d',$postlimit))
      {
        while($post = $DB->fetch_array($getposts,null,MYSQL_ASSOC))
        {
          $thread = $DB->query_first('SELECT topic_title FROM '.$tableprefix."topics WHERE topic_id = '".$post['topic_id']."' ");

          if(empty($post['post_username']))
          {
            $lastposter = $DB->query_first("SELECT username FROM ".$tableprefix."users WHERE user_id = '".$post['poster_id']."' ");
            $post['post_username'] = $lastposter['username'];
          }

          $thread['topic_title'] = p8_ShortenTitle($settings, $thread['topic_title']); //2012-04-18
          $thread['topic_title'] = sd_wordwrap($thread['topic_title'], $wordwrap, '<br />', 1);
          $post['postername']    = sd_wordwrap($post['post_username'], $wordwrap, '<br />', 1);
          $avatar_conf['userid'] = $post['poster_id'];
          $avatar_conf['username'] = $post['post_username'];

          echo '
          <tr><td>' . sd_PrintAvatar($avatar_conf) . '<a href="' . $sdurl . $forumpath . 'viewtopic.php?'.
            ($first_topic ? 't='.$post['topic_id'] : 'p='.$post['post_id'].'#'.$post['post_id']) . '">'.
            $thread['topic_title'] . '</a><br />' . $language['posted_by'];

          if($ForumLinkFuncOK)
          {
            echo ' <a href="' . ForumLink(4, $post['poster_id']) . '">' . $post['postername'] . '</a>';
          }
          else
          {
            echo $post['postername'];
          }

          if(!empty($settings['display_post_date']))
          {
            echo '<br />'. DisplayDate($post['post_time'], '', 1);
          }
          echo '<br /><br />
            </td>
          </tr>';
        }
        $DB->free_result($getposts);
      }
    }
    else if($forumname == 'phpBB3')
    {
      $extraquery .= (strlen($extraquery) ? ' AND' : ' WHERE'). ' t.topic_approved = 1';
      $extraquery = str_replace('forum_id', 't.forum_id', $extraquery);
      if($getthreads = $DB->query("SELECT t.topic_title, t.topic_id, t.forum_id,
        t.topic_last_poster_id, t.topic_last_poster_name, t.topic_last_post_id,
        t.topic_last_post_time, t.topic_approved, p.post_approved
        FROM {$tableprefix}topics t, {$tableprefix}posts p
        {$extraquery} AND t.topic_last_post_id = p.post_id AND p.post_approved = 1
        ORDER BY t.topic_last_post_time DESC LIMIT %d",$postlimit))
      {
        while($post = $DB->fetch_array($getthreads,null,MYSQL_ASSOC))
        {
          // check phpBB3 core permisions
          $phpBB3_perm = sd_GetForumPermissions($post['forum_id'],'f_read');
          if(!empty($phpBB3_perm))
          {
            if(empty($post['topic_last_poster_name']))
            {
              $lastposter = $DB->query_first('SELECT username FROM '.$tableprefix.
                'users WHERE user_id = %d', $post['topic_last_poster_id']);
              $post['topic_last_poster_name'] = $lastposter['username'];
            }

            $post['topic_title'] = p8_ShortenTitle($settings, $post['topic_title']); //2012-04-18
            if(!empty($sd_charset) && strtolower($sd_charset)=='utf-8')
            {
              $post['topic_title'] = utf8_wordwrap($post['topic_title'], $wordwrap, '<br />', 1);
              $post['postername']  = utf8_wordwrap($post['topic_last_poster_name'], $wordwrap, '<br />', 1);
            }
            else
            {
              $post['topic_title'] = sd_wordwrap($post['topic_title'], $wordwrap, '<br />', 1);
              $post['postername']  = sd_wordwrap($post['topic_last_poster_name'], $wordwrap, '<br />', 1);
            }
            $avatar_conf['userid']   = $post['topic_last_poster_id'];
            $avatar_conf['username'] = $post['topic_last_poster_name'];

            echo '
            <tr><td>' . sd_PrintAvatar($avatar_conf) .
              '<a href="' . $sdurl . $forumpath . 'viewtopic.php?f='.$post['forum_id'].'&amp;t='.$post['topic_id'] .
              ($first_topic ? '' : '&amp;p=' . $post['topic_last_post_id'] . '#p' . $post['topic_last_post_id']) .
              '">' . $post['topic_title'] . '</a><br />' . $language['posted_by'];

            if($ForumLinkFuncOK)
            {
              echo ' <a href="' . ForumLink(4, $post['topic_last_poster_id']) . '">' . $post['postername'] . '</a>';
            }
            else
            {
              echo $post['postername'];
            }

            if(!empty($settings['display_post_pate']))
            {
              echo '<br />'. DisplayDate($post['topic_last_post_time'], '', 1);
            }
            echo '<br /><br />
              </td>
            </tr>';
          }
        }
      }
    }
    else if($forumname == 'Invision Power Board 2')
    {
      $extraquery .= (strlen($extraquery) ? ' AND' : ' WHERE'). ' t.approved = 1';
      if($getthreads = $DB->query('SELECT t.title, t.tid, t.last_poster_name, t.last_post,
        t.forum_id, f.permission_array
        FROM ' . $tableprefix . "topics t,
        " . $tableprefix . "forums f
        $extraquery AND t.forum_id = f.id
        ORDER BY last_post DESC LIMIT %d",$postlimit))
      {
        while($thread = $DB->fetch_array($getthreads,null,MYSQL_ASSOC))
        {
          // check if the user has view permission for the forum
          // where the thread is located in
          $allowed = false;
          $perm = @unserialize(stripslashes($thread['permission_array']));
          if($perm !== false)
          {
            if($perm['show_perms'] == '*')
            {
              $allowed = true;
            }
            else
            {
              $perm = explode(',',$perm['show_perms']);
              $allowed = array_intersect($userinfo['forumusergroupids'], $perm);
              $allowed = !empty($allowed);
            }
          }
          if($allowed)
          {
            $thread['title'] = p8_ShortenTitle($settings, $thread['title']); //2012-04-18
            $thread['title'] = sd_wordwrap($thread['title'], $wordwrap, '<br />', 1);
            $thread['postername'] = sd_wordwrap($thread['last_poster_name'], $wordwrap, '<br />', 1);
            $avatar_conf['username'] = $thread['last_poster_name'];

            echo '
            <tr><td>' . sd_PrintAvatar($avatar_conf) .
              '<a href="'.$sdurl.$forumpath.'index.php?showtopic='.$thread['tid'].
              ($first_topic ? '' : '&view=getlastpost'). '">' .
              $thread['title'] . '</a>
              <br />' . $language['posted_by'] . ' ' . $thread['postername'];

            if(!empty($settings['display_post_date']))
            {
              echo '<br />'. DisplayDate($thread['last_post'], '', 1);
            }
            echo '<br /></td>
            </tr>';
          }
        }
        $DB->free_result($getthreads);
      }
    }
    else if($forumname == 'Invision Power Board 3')
    {
      $extraquery .= (strlen($extraquery) ? ' AND' : ' WHERE'). ' t.topic_approved = 1';
      // No longer exists: "forums.permission_array"
      if($getthreads = $DB->query('SELECT t.title, t.tid, t.last_poster_name, t.last_post, t.forum_id
        FROM ' . $tableprefix . "topics t
        INNER JOIN " . $tableprefix . "forums f ON t.forum_id = f.id
        $extraquery
        ORDER BY last_post DESC LIMIT %d",$postlimit))
      {
        while($thread = $DB->fetch_array($getthreads,null,MYSQL_ASSOC))
        {
          $thread['title'] = p8_ShortenTitle($settings, $thread['title']); //2012-04-18
          $thread['title'] = sd_wordwrap($thread['title'], $wordwrap, '<br />', 1);
          $thread['postername'] = sd_wordwrap($thread['last_poster_name'], $wordwrap, '<br />', 1);
          $avatar_conf['username'] = $thread['last_poster_name'];

          echo '
          <tr><td>' . sd_PrintAvatar($avatar_conf) .
            '<a href="'.$sdurl.$forumpath.'index.php?showtopic='.$thread['tid'].
            ($first_topic ? '' : '&view=getlastpost'). '">' .
            $thread['title'] . '</a>
            <br />' . $language['posted_by'] . ' ' . $thread['postername'];

          if(!empty($settings['display_post_date']))
          {
            echo '<br />'. DisplayDate($thread['last_post'], '', 1);
          }
          echo '<br /></td>
          </tr>';
        }
        $DB->free_result($getthreads);
      }
    }
    else if($forumname == 'Simple Machines Forum 1')
    {
      if($getthreads = $DB->query('SELECT subject, ID_MSG, ID_TOPIC, posterName, posterTime
          FROM ' . $tableprefix . 'messages ' . $extraquery . '
          ORDER BY posterTime DESC LIMIT %d',$postlimit))
      {
        while($thread = $DB->fetch_array($getthreads,null,MYSQL_ASSOC))
        {
          $thread['subject'] = p8_ShortenTitle($settings, $thread['subject']); //2012-04-18
          $thread['subject'] = sd_wordwrap($thread['subject'], $wordwrap, '<br />', 1);
          $thread['postername'] = sd_wordwrap($thread['posterName'], $wordwrap, '<br />', 1);
          $avatar_conf['username'] = $thread['posterName'];

          echo '
          <tr><td>' . sd_PrintAvatar($avatar_conf) .
            '<a href="' . $sdurl . $forumpath . 'index.php?topic=' . $thread['ID_TOPIC'] .
            ($first_topic ? '.0' : '.msg' . $thread['ID_MSG'] . '#msg' . $thread['ID_MSG']) .
            '">' . htmlentities($thread['subject']) . '</a><br />
            ' . $language['posted_by'] . ' ' . $thread['postername'];

            if(!empty($settings['display_post_date']))
            {
              echo '<br />'. DisplayDate($thread['posterTime'], '', 1);
            }
            echo '<br /></td>
            </tr>';
        }
        $DB->free_result($getthreads);
      }
    }
    else if($forumname == 'Simple Machines Forum 2')
    {
      if($getthreads = $DB->query('SELECT subject, id_msg, id_topic, poster_name, poster_time'.
        ' FROM ' . $tableprefix . 'messages ' .
        $extraquery .
        ' ORDER BY poster_time DESC LIMIT %d',$postlimit))
      {
        while($thread = $DB->fetch_array($getthreads,null,MYSQL_ASSOC))
        {
          $thread['subject'] = p8_ShortenTitle($settings, $thread['subject']); //2012-04-18
          $thread['subject'] = sd_wordwrap($thread['subject'], $wordwrap, '<br />', 1);
          $thread['postername'] = sd_wordwrap($thread['poster_name'], $wordwrap, '<br />', 1);
          $avatar_conf['username'] = $thread['poster_name'];

          echo '
          <tr><td>' . sd_PrintAvatar($avatar_conf) .
            '<a href="' . $sdurl . $forumpath . 'index.php?topic=' . $thread['id_topic'] .
            ($first_topic ? '.0' : '.' . $thread['id_msg'] . '#msg' . $thread['id_msg']) .
            '">' . $thread['subject'] . #SD370: htmlentities removed
            '</a><br />' . $language['posted_by'] . ' ' . $thread['poster_name'];

            if(!empty($settings['display_post_date']))
            {
              echo '<br />'. DisplayDate($thread['poster_time'], '', ($forumname=='Subdreamer'));
            }
            echo '<br /></td>
            </tr>';
        }
        $DB->free_result($getthreads);
      }
    }
    else if($forumname == 'XenForo 1')
    {
      $extraquery .= (empty($extraquery) ? 'WHERE ' : ' AND ') . " (discussion_state = 'visible') AND (discussion_type <> 'redirect')";
      if($getthreads = $DB->query('SELECT node_id, thread_id, title, discussion_state,
         last_post_date, last_post_id, last_post_user_id, last_post_username
         FROM ' . $tableprefix . 'thread ' . $extraquery . '
         ORDER BY last_post_date DESC LIMIT %d',$postlimit))
      {
        $m_thread = XenForo_Model::create('XenForo_Model_Thread');
        $m_forum  = XenForo_Model::create('XenForo_Model_Forum');
        while($thread = $DB->fetch_array($getthreads,null,MYSQL_ASSOC))
        {
          $f = $m_forum->getForumById($thread['node_id']);
          $t = $m_thread->getThreadById($thread['thread_id']);
          $p = XenForo_Visitor::getInstance()->getNodePermissions($thread['node_id']);
          if(!empty($p['view']))
          {
            $targetUrl = XenForo_Link::buildPublicLink('threads', $t);
            $thread['title'] = p8_ShortenTitle($settings, $thread['title']); //2012-04-18
            $thread['title'] = sd_wordwrap($thread['title'], $wordwrap, '<br />', 1);
            $thread['postername'] = sd_wordwrap($thread['last_post_username'], $wordwrap, '<br />', 1);
            $avatar_conf['username'] = $thread['last_post_username'];

            echo '
            <tr><td>' . sd_PrintAvatar($avatar_conf) .
            '<a href="' . $sdurl . $forumpath . $targetUrl .
            '">' . $thread['title'] . '</a><br />
            ' . $language['posted_by'] . ' ' . $thread['last_post_username'];

            if(!empty($settings['display_post_date']))
            {
              echo '<br />'. DisplayDate($thread['last_post_date'], '', 1);
            }
            echo '<br /></td>
            </tr>';
          }
        }
        $DB->free_result($getthreads);
        unset($m_forum, $m_thread, $t, $f);
      }
    }
    else if($forumname == 'MyBB') //SD370
    {
      $allGroups = !empty($userinfo['forumusergroupids'])?$userinfo['forumusergroupids']:array(1);
      $fgid = $allGroups[0];

      $sql = 'SELECT
        p.fid, p.tid, p.pid, p.uid, p.dateline, p.visible,
        t.subject, t.views, t.replies, p.username,
        fp.pid permission_id, fp.canview, fp.canviewthreads, fp.canonlyviewownthreads,
        (SELECT MAX(pid) pid FROM '.$tableprefix.'posts WHERE tid = t.tid) lastpost_id
        FROM '.$tableprefix.'threads t
        LEFT JOIN '.$tableprefix.'posts AS p ON p.tid = t.tid
        LEFT JOIN '.$tableprefix.'forums AS f ON f.fid = p.fid
        LEFT JOIN '.$tableprefix.'forumpermissions AS fp ON fp.fid = f.fid AND fp.gid = '.$fgid.'
        '.$extraquery.
        ' AND p.visible = 1' .
        ' ORDER BY dateline DESC LIMIT %d';
      if($getthreads = $DB->query($sql, $postlimit*2))
      {
        #while($thread = $DB->fetch_array($getthreads,null,MYSQL_ASSOC))
        for($i = 0; ($postlimit > 0) && ($thread = $DB->fetch_array($getthreads,null,MYSQL_ASSOC)); $i++)
        {
          // Viewing permission of user's main forum usergroup
          // For our purposes, a user must have BOTH canview/canviewthreads permissions!
          if(!$canview = (is_null($thread['permission_id']) ||
                          (($thread['canview']==1) && ($thread['canviewthreads']==1))))
          {
            // Viewing permissions of additional usergroups (if exist)
            if(count($allGroups) > 1)
            $canview = mybb_canview_forum($thread['fid']);
          }
          if(!$canview) continue;

          $thread['subject'] = p8_ShortenTitle($settings, $thread['subject']);
          $thread['subject'] = sd_wordwrap($thread['subject'], $wordwrap, '<br />', 1);
          $thread['username'] = sd_wordwrap($thread['username'], $wordwrap, '<br />', 1);
          $avatar_conf['username'] = $thread['username'];

          echo '
          <tr><td>'.
            sd_PrintAvatar($avatar_conf).
            '<a href="' . $sdurl . $forumpath . 'showthread.php?tid='.$thread['tid'].
            ($first_topic ? '' : '&amp;action=lastpost') . '">'.
            htmlentities($thread['subject']) . '</a><br />'.
            $language['posted_by'] . ' ' . $thread['username'];

          if(!empty($settings['display_post_date']))
          {
            echo '<br />'. DisplayDate($thread['dateline'], '', 1);
          }
          echo '<br /></td>
          </tr>';
          $postlimit--;
        }
        $DB->free_result($getthreads);
      }
    }
    else if($forumname == 'punBB') //SD370
    {
      if($getthreads = $DB->query('SELECT p.*, t.subject'.
        ' FROM ' . $tableprefix . 'topics t '.
        ' INNER JOIN ' . $tableprefix . 'forums f ON f.id = t.forum_id'.
        ' INNER JOIN ' . $tableprefix . 'posts p ON p.topic_id = t.id AND p.id = t.last_post_id '.
        $extraquery .
        ' ORDER BY p.posted DESC LIMIT %d',$postlimit))
      {
        while($thread = $DB->fetch_array($getthreads,null,MYSQL_ASSOC))
        {
          $thread['subject'] = p8_ShortenTitle($settings, $thread['subject']);
          $thread['subject'] = sd_wordwrap($thread['subject'], $wordwrap, '<br />', 1);
          $thread['username'] = sd_wordwrap($thread['poster'], $wordwrap, '<br />', 1);
          $avatar_conf['userid'] = $thread['poster_id'];
          $avatar_conf['username'] = $thread['username'];

          echo '
          <tr><td>' . sd_PrintAvatar($avatar_conf) .
            '<a href="' . $sdurl . $forumpath . 'viewtopic.php?';
          if($first_topic)
            echo 'id=' . $thread['topic_id'];
          else
            echo 'pid=' . $thread['id'] . '#p' . $thread['id'];
          echo '">' . htmlentities($thread['subject']) . '</a><br />
            ' . $language['posted_by'] . ' ' . $thread['username'];

            if(!empty($settings['display_post_date']))
            {
              echo '<br />'. DisplayDate($thread['posted'], '', 1);
            }
            echo '<br /></td>
            </tr>';
        }
        $DB->free_result($getthreads);
      }
    }

    echo '
    </table>
    ';

    // switch back to subdreamer database
    if($DB->database != $dbname)
    {
      $DB->select_db($dbname);
    }

  } //p8_LatestPosts
} // NOT EXISTS - DO NOT REMOVE!

if(!empty($usersystem['name']))
{
  $sd_forum_installed = (GetPluginID('Forum') > 17);
  if(($usersystem['name'] != 'Subdreamer') || $sd_forum_installed)
  {
    $p8_pluginid = GetPluginID('Latest Posts');
    if(!empty($userinfo['pluginviewids']) && @in_array($p8_pluginid,$userinfo['pluginviewids']))
    {
      $DB->ignore_error = true;
      p8_LatestPosts($p8_pluginid);
      $DB->ignore_error = false;
    }
  }
}
