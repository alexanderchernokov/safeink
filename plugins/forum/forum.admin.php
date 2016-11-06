<?php
if(!defined('IN_PRGM')) return;

if(!class_exists('SDForumAdminTools'))
{
class SDForumAdminTools
{
  protected $conf;

  function SDForumAdminTools($ForumConfigObj)
  {
    $this->conf = $ForumConfigObj;
  }

  // ##########################################################################

  function DisplayForumSelection($forumid = 0, $showzerovalue = 0, $parentid = 0,
                                 $sublevelmarker = '', $selectname = 'parent_forum_id',
                                 $style='',$ignore_id=0,$size=0)
  {
    global $DB, $userinfo;

    if($sublevelmarker=='- - ') return; // only allow 1 sub-level for forums!!!
    // start selection box
    if($parentid == 0)
    {
      echo '
      <select id="' . $selectname . '" name="' . $selectname . '"'.
      (empty($style)?'':' style="'.$style.'"').
      (empty($size)?'':' size="'.$size.'"').'>';

      if($showzerovalue)
      {
        echo '
        <option value="0">-</option>';
      }
    }
    else
    {
      $sublevelmarker .= '- - ';
    }

    if($getforums = $DB->query('SELECT forum_id, parent_forum_id, title, is_category, online FROM {p_forum_forums}
                                WHERE parent_forum_id = %d'.
                                (!empty($ignore_id)?' AND forum_id <> '.(int)$ignore_id:'').
                                ' ORDER BY display_order, title', $parentid))
    {
      while($forum = $DB->fetch_array($getforums,NULL,MYSQL_ASSOC))
      {
        $suffix = $forum['online'] ? '' : ' *';
        $style = $forum['is_category'] ? 'style="font-weight: bold; background-color:#B0B0B0" ' : '';
        $title = htmlspecialchars(strip_alltags($title = str_replace('&amp;','&',$forum['title'])));
        echo '
          <option '.$style.'value="' . $forum['forum_id'] . '" ' . ($forumid == $forum['forum_id'] ? 'selected="selected"' : '') . '>' .
                             $sublevelmarker.$title.$suffix.'</option>';
        $this->DisplayForumSelection($forumid, $showzerovalue, $forum['forum_id'], $sublevelmarker, '', $style, $ignore_id);
      }
      $DB->free_result($getforums);
    }
    // end the selection box
    if($parentid == 0)
    {
      echo '
      </select>';
    }

  } //DisplayForumSelection

  // ##########################################################################

  // @extra_topic_count: number of topics to delete/add from forum stats
  // @extra_post_count: number of posts to delete/add from forum stats
  function UpdateForumInfo($forum_id, $extra_topic_count = 0, $extra_post_count = 0)
  {
    global $DB;

    if(empty($forum_id)) return false;

    $sql = '';
    $forum_id = (int)$forum_id;
    $extra_topic_count = (int)$extra_topic_count;
    $extra_post_count  = (int)$extra_post_count;
    $last_topic_sql = 'SELECT t.*'.
                      ' FROM {p_forum_topics} t'.
                      ' LEFT JOIN {p_forum_posts} p ON p.topic_id = t.topic_id'.
                      ' WHERE t.forum_id = %d'.
                      ' AND IFNULL(t.moderated,0) = 0 AND IFNULL(p.moderated,0) = 0'.
                      ' ORDER BY p.post_id DESC, t.topic_id DESC LIMIT 1';

    if($last_topic_arr = $DB->query_first($last_topic_sql, (int)$forum_id))
    {
      $UpdateCounts = true;
      $sql = "UPDATE {p_forum_forums}
              SET last_topic_id      = "  . (int)$last_topic_arr['topic_id'] . ",
                  last_topic_title   = '" . trim($last_topic_arr['title']) . "',
                  last_post_id       = "  . (int)$last_topic_arr['last_post_id'] . ",
                  last_post_username = '" . $last_topic_arr['last_post_username'] . "',
                  last_post_date     = "  . (int)$last_topic_arr['last_post_date'] . ",
                  topic_count        = (IFNULL(topic_count,0) + ".(int)$extra_topic_count."),
                  post_count         = (IFNULL(post_count,0) + ".(int)$extra_post_count.")
                  WHERE forum_id = %d";
    }
    else
    if(!$topic_arr = $DB->query_first('SELECT topic_id FROM {p_forum_topics} WHERE forum_id = %d LIMIT 1',$forum_id))
    {
      $UpdateCounts = false;
      // no topics exist
      $sql = "UPDATE {p_forum_forums}
              SET last_topic_id      = 0,
                  last_topic_title   = '',
                  last_post_id       = 0,
                  last_post_username = '',
                  last_post_date     = '0',
                  topic_count        = 0,
                  post_count         = 0
                  WHERE forum_id     = %d";
    }

    if(!empty($sql) && $DB->query($sql, $forum_id))
    {
      if($UpdateCounts)
      {
        // SD313: Update topics- and posts-count correctly
        $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'p_forum_forums ff'.
                   ' SET ff.topic_count = IFNULL((SELECT COUNT(*) FROM '.PRGM_TABLE_PREFIX.'p_forum_topics ft1 '.
                                                ' WHERE ft1.forum_id = ff.forum_id'.
                                                ' AND IFNULL(ft1.moderated,0)=0),0), '.
                       ' ff.post_count  = IFNULL((SELECT SUM(ft2.post_count) FROM '.PRGM_TABLE_PREFIX.'p_forum_topics ft2'.
                                                ' WHERE ft2.forum_id = ff.forum_id'.
                                                ' AND IFNULL(ft2.moderated,0) = 0),0)'.
                   ' WHERE ff.forum_id = %d', $forum_id);
      }
      //SD343: delete forums cache file
      $this->conf->ClearForumsCache();
      return true;
    }

    return false;

  } //UpdateForumInfo


  // ##########################################################################

  // @extra_post_count: number of posts to delete/add from forum stats
  function UpdateTopicInfo($topic_id)
  {
    global $DB;

    // Fetch both the first and last posts in ONE "SELECT", not counting moderated posts:
    $first_last_post_sql =
      '(SELECT \'MIN\' rowtype, fp.post_id, fp.`date` post_date, fp.username, u.userid post_user_id, u.username users_username
       FROM '.PRGM_TABLE_PREFIX.'p_forum_posts fp
       LEFT JOIN '.PRGM_TABLE_PREFIX.'users u ON u.userid = fp.user_id
       WHERE fp.topic_id = %d
       AND (fp.post_id = (SELECT MIN(post_id) FROM '.PRGM_TABLE_PREFIX.'p_forum_posts fp2
                          WHERE fp2.topic_id = fp.topic_id AND IFNULL(fp2.moderated,0) = 0)))
       UNION ALL
       (SELECT \'MAX\' rowtype, fp.post_id, fp.`date` post_date, fp.username, u.userid post_user_id, u.username users_username
       FROM '.PRGM_TABLE_PREFIX.'p_forum_posts fp
       LEFT JOIN '.PRGM_TABLE_PREFIX.'users u ON u.userid = fp.user_id
       WHERE fp.topic_id = %d
       AND (fp.post_id = (SELECT MAX(post_id) FROM '.PRGM_TABLE_PREFIX.'p_forum_posts fp2
                          WHERE fp2.topic_id = fp.topic_id AND IFNULL(fp2.moderated,0) = 0)))
       ORDER BY 1 ASC';

    $last_arr = $first_arr = array();
    $get_first_last_post = $DB->query($first_last_post_sql, $topic_id, $topic_id);
    while($row = $DB->fetch_array($get_first_last_post,null,MYSQL_BOTH))
    {
      if($row['rowtype'] == 'MAX')
      {
        $last_arr = $row;
      }
      else
      if($row['rowtype'] == 'MIN')
      {
        $first_arr = $row;
      }
    }
    unset($get_first_last_post);

    if(empty($last_arr['post_id']) && !empty($first_arr['post_id']))
    {
      $last_arr = $first_arr;
    }
    else
    if(!isset($first_arr['post_id']) && isset($last_arr['post_id']))
    {
      $first_arr = $last_arr;
    }

    // Now both row arrays should be set to go:
    if(!empty($first_arr['post_id']) && !empty($last_arr['post_id']))
    {
      $post_user_id  = !empty($first_arr['post_user_id']) ? (int)$last_arr['post_user_id'] : 0;
      $post_username = !empty($first_arr['users_username']) ? $first_arr['users_username'] :
                        (!empty($first_arr['username']) ? $first_arr['username'] : '');
      $last_post_username = !empty($last_arr['users_username']) ? $last_arr['users_username'] :
                             (!empty($last_arr['username']) ? $last_arr['username'] : '');

      //SD360: Commented out updating of topic starter
      #post_user_id = "   . (int)$post_user_id . ",
      #post_username = '" . $post_username . "',
      $sql = "UPDATE {p_forum_topics}
              SET first_post_id = "  . (int)$first_arr['post_id'] . ",
                  last_post_id = "   . (int)$last_arr['post_id'] . ",
                  last_post_date = " . (int)$last_arr['post_date'] . ",
                  last_post_username = '" . $last_post_username . "'
              WHERE topic_id = %d LIMIT 1";

      if($DB->query($sql, (int)$topic_id))
      {
        //SD313: Update post-count correctly (not counting moderated posts!)
        $DB->query('UPDATE {p_forum_topics} ft'.
                   ' SET ft.post_count = (SELECT COUNT(*) FROM '.PRGM_TABLE_PREFIX.'p_forum_posts ft2'.
                   ' WHERE ft2.topic_id = ft.topic_id AND IFNULL(ft2.moderated,0)=0)'.
                   ' WHERE ft.topic_id = %d', $topic_id);
      }
    }

    $this->conf->ClearForumsCache();

    return true;

  } //UpdateTopicInfo


  // ##########################################################################

  function ProcessPosts($post_id_arr, $posts_action, $forum_id, $topic_id)
  // post_action can be: delete, moderate or unmoderate!
  {
    global $DB;

    $forum_id = Is_Valid_Number($forum_id,0,1);
    $topic_id = Is_Valid_Number($topic_id,0,1);
    if(!is_array($post_id_arr) || empty($posts_action) || !$forum_id || !$topic_id)
    {
      return false;
    }

    // Set moderated flag according to action
    $moderated_value = (substr($posts_action,0,8) == 'moderate') ? 1 : 0;

    //SD343: extra security check
    if($posts_action == 'delete_posts')
    {
      global $userinfo;
      $key = 'p'.md5(md5($userinfo['userid'].$this->conf->forum_arr['forum_id']).
             $this->conf->topic_arr['topic_id'].count($post_id_arr));
      if(empty($_POST[$key])) return false;
    }

    //SD343: remove comment likes
    require_once(SD_INCLUDE_PATH.'class_sd_likes.php');

    // cycle through all the post ids and delete posts
    $deleted_posts = 0;
    foreach($post_id_arr AS $post_id)
    {
      if($post_id = Is_Valid_Number($post_id,0,1))
      {
        if(substr($posts_action,0,6) == 'delete')
        {
          if(substr($posts_action,-5) == 'topic')
          {
            // Delete all posts for a specific topic
            $DB->query('DELETE FROM {p_forum_attachments} WHERE post_id IN
                       (SELECT post_id FROM {p_forum_posts} WHERE topic_id = %d)', $post_id);
            $DB->query('DELETE FROM {p_forum_posts} WHERE topic_id = %d', $post_id);
            //SD343: remove likes for deleted posts (and any existing orphaned likes!)
            $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'users_likes WHERE pluginid = %d'.
                       " AND liked_type = '%s'".
                       ' AND NOT EXISTS(SELECT 1 FROM '.PRGM_TABLE_PREFIX.'p_forum_posts fp'.
                                      ' WHERE fp.post_id = objectid)',
                       $this->conf->plugin_id, SD_LIKED_TYPE_POST);
          }
          else
          {
            // Delete specific post with given id
            $DB->query('DELETE FROM {p_forum_attachments} WHERE post_id = %d', $post_id);
            $DB->query('DELETE FROM {p_forum_posts} WHERE post_id = %d', $post_id);
            //SD343: remove likes for post
            SD_Likes::RemoveLikesForObject($this->conf->plugin_id, $post_id, false);
          }
        }
        else
        {
          // ONLY update rows that are different to the moderation value
          // or otherwise the post-count gets wrong
          $DB->query('UPDATE {p_forum_posts} SET moderated = %d WHERE post_id = %d',
                     $moderated_value, (int)$post_id, $moderated_value);
        }
        // In all cases, the "visible" post count is decreased
        // by the numer of deleted/updated rows
        $deleted_posts += $DB->affected_rows();
      }
    }

    // Update topic; if there are no (unmoderated) posts left, the topic
    // itself is to be deleted if it's not a moderation action
    if($first_post_arr = $DB->query_first('SELECT MIN(post_id) FROM {p_forum_posts}'.
                                          ' WHERE topic_id = %d AND IFNULL(moderated,0) = 0',
                                          $topic_id))
    {
      $this->UpdateTopicInfo($topic_id);
      $this->UpdateForumInfo($forum_id, 0, -$deleted_posts);
    }
    else
    {
      // If action is to delete and no unmoderated(!) posts exist anymore
      // for this topic, then delete the topic itself
      if($posts_action == 'delete_posts')
      {
        $DB->query('DELETE FROM {p_forum_topics} WHERE topic_id = %d', $topic_id);
        $DB->query('DELETE FROM {p_forum_attachments} WHERE post_id IN
                   (SELECT post_id FROM {p_forum_posts} WHERE topic_id = %d)', $topic_id);
        //SD343: remove likes for deleted posts
        $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'users_likes WHERE pluginid = %d'.
                   " AND liked_type = '%s'".
                   ' AND NOT EXISTS(SELECT 1 FROM '.PRGM_TABLE_PREFIX.'p_forum_posts fp'.
                                  ' WHERE fp.post_id = objectid)',
                   $this->conf->plugin_id, SD_LIKED_TYPE_POST);
      }
      else
      {
        // All posts are moderated, so the topic itself needs to become moderated!
        // ONLY do update, if NOT already moderated, otherwise counts get wrong!
        $DB->query('UPDATE {p_forum_topics} SET moderated = '.$moderated_value.
                   ' WHERE topic_id = %d AND IFNULL(moderated,0) != $d',
                   $topic_id, $moderated_value);
      }
      $changed_count = $DB->affected_rows();

      // If the currently viewed topic was deleted, reset members
      if((int)$this->conf->topic_arr['topic_id'] == $topic_id)
      {
        // Reset current topic
        $this->conf->topic_arr = array();
        $this->conf->topic_id = 0;
      }

      // Update the related forum's topic- and post-count
      $this->UpdateForumInfo($forum_id, -$changed_count, -$deleted_posts);
    }

    return $deleted_posts;

  } //ProcessPosts


  // ##########################################################################
  // DISPLAY TOPIC EDIT FORM (ADMIN ONLY)
  // ##########################################################################

  function DisplayTopicEditForm($topic_id)
  {
    if(!$this->conf->forum_arr['forum_id'])
    {
      // user is trying to break the script
      $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr['err_invalid_forum_id'].'</strong>',true);
      return;
    }

    global $DB;

    echo '<br />
    <h2>' . $this->conf->forum_arr['title'] . '</h2>
    <form id="forum-admin" action="' . RewriteLink() . '" method="post">
    '.PrintSecureToken(FORUM_TOKEN).'
    <input type="hidden" name="forum_id" value="'.$this->conf->forum_arr['forum_id'].'" />
    <input type="hidden" name="topic_id" value="'.$this->conf->topic_arr['topic_id'].'" />
    <input type="hidden" name="forum_action" value="topic_options" />
    <input type="hidden" name="topic_action" value="update_topic" />
    <div class="form-wrap">
    <h2>'.$this->conf->plugin_phrases_arr['topic_options_edit_topic'].'</h2>
    <label style="padding-top:10px">'.$this->conf->plugin_phrases_arr['topic_title'].' ('.$this->conf->plugin_phrases_arr['rename'].')</label>
    <input type="text" name="forum_topic_title" value="'.trim($this->conf->topic_arr['title']).'" />
    <fieldset>';

    //SD343: display prefixes available to user (based on sub-forum/usergroup)
    //SD343: fetch prefix (if present and uncensored) and display it
    $prefix_master = 0;
    $prefix = $DB->query_first('SELECT t2.* FROM '.PRGM_TABLE_PREFIX.'tags t1'.
                               ' INNER JOIN '.PRGM_TABLE_PREFIX.'tags t2 ON t2.tagid = t1.tag_ref_id'.
                               ' WHERE t1.pluginid IN (0, %d) AND t1.objectid = %d AND t1.censored = 0'.
                               ' AND t2.pluginid IN (0, %d) AND t2.tagtype = 2 AND t2.censored = 0 LIMIT 1',
                               $this->conf->plugin_id, $this->conf->topic_id, $this->conf->plugin_id);
    if(!empty($prefix['tagid']))
    {
      $prefix_master = (int)$prefix['tagid'];
      echo '
      <p style="padding-top:10px">
        <label for="remove_prefix">'.
      $this->conf->plugin_phrases_arr['topic_prefix'].' '.
      str_replace(array('[tagname]'), array($prefix['tag']), $prefix['html_prefix']).'
        <input type="checkbox" id="remove_prefix" name="remove_prefix" value="1" />
        '.$this->conf->plugin_phrases_arr['topic_options_remove_prefix'].'</label></p>';
    }
    unset($prefix);

    require_once(SD_INCLUDE_PATH.'class_sd_tags.php');
    $tconf = array(
      'elem_name'   => 'prefix_id',
      'elem_size'   => 1,
      'elem_zero'   => 1,
      'chk_ugroups' => !$this->conf->IsSiteAdmin,
      'pluginid'    => $this->conf->plugin_id,
      'objectid'    => 0,
      'tagtype'     => 2,
      'ref_id'      => 0,
      'allowed_id'  => $this->conf->forum_id,
      'selected_id' => $prefix_master,
    );

    $prefix = SD_Tags::GetPluginTagsAsSelect($tconf);
    if(strlen($prefix))
    {
      echo '
      <p style="padding-top:10px">
        <label for="prefix_id">'.$this->conf->plugin_phrases_arr['change_prefix_to'].'
        '.$prefix.'</label></p>';
    }

    echo '
      <p style="padding-top:10px">
        <label for="ft_sticky"><img alt=" " src="'.SDUserCache::$img_path.'sticky.png" width="16" height="16" />
        <input type="checkbox" id="ft_sticky" name="sticky" value="1" '.($this->conf->topic_arr['sticky']?'checked="checked" ':'').'/> '.
        ($this->conf->plugin_phrases_arr['topic_options_stick_topic']).'</label></p>
      <p style="padding-top:10px">
        <label for="ft_closed"><img alt=" " src="'.SDUserCache::$img_path.'lock.png" width="16" height="16" />
        <input type="checkbox" id="ft_closed" name="closed" value="1" '.($this->conf->topic_arr['open']?'':' checked="checked" ').'/> '.
        ($this->conf->plugin_phrases_arr['topic_options_lock_topic']).'</label></p>
      <p style="padding-top:10px">
        <label for="ft_moderated"><img alt=" " src="'.SDUserCache::$img_path.'attention.png" width="16" height="16" />
        <input type="checkbox" id="ft_moderated" name="moderated" value="1" '.($this->conf->topic_arr['moderated']?'checked="checked" ':'').'/> '.
        $this->conf->plugin_phrases_arr['topic_options_moderate_topic'].'</label></p>
      <p style="padding-top:10px"><label for="ft_tags">'.$this->conf->plugin_phrases_arr['topic_tags'].'
        <input type="text" id="ft_tags" name="tags" maxlength="200" size="35" value="'.
        implode(', ',SD_Tags::GetPluginTags($this->conf->plugin_id, $this->conf->topic_arr['topic_id'], 0, 0)).'" /><br />
        '.$this->conf->plugin_phrases_arr['topic_tags_hint'].'</label></p>
    </fieldset>

    <input type="submit" name="submit" value="'.strip_alltags($this->conf->plugin_phrases_arr['update_topic']).'" />
    </div>
    </form>';

  } //DisplayTopicEditForm


  // ##########################################################################
  // DISPLAY POST(S) DELETE CONFIRMATION FORM
  // ##########################################################################

  function DisplayPostDeleteForm()
  {
    global $sdlanguage, $userinfo;

    if(!$this->conf->forum_arr['forum_id'])
    {
      // user is trying to break the script
      $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr['err_invalid_forum_id'].'</strong>',true);
      return;
    }

    // List of post(s) marked for deletion within currently selected topic
    $post_id_arr = GetVar('post_id_arr', array(), 'array', true, false);
    $key = md5(md5($userinfo['userid'].$this->conf->forum_arr['forum_id']).$this->conf->topic_arr['topic_id'].count($post_id_arr));
    echo '<h2>' . $this->conf->topic_arr['title'] . '</h2>
    <form id="forum-admin" action="' . RewriteLink() . '" method="post">
    '.PrintSecureToken(FORUM_TOKEN).'
    <input type="hidden" name="forum_action" value="topic_options" />
    <input type="hidden" name="posts_action" value="delete_posts" />
    <input type="hidden" name="forum_id" value="'.$this->conf->forum_arr['forum_id'].'" />
    <input type="hidden" name="topic_id" value="'.$this->conf->topic_arr['topic_id'].'" />
    <input type="hidden" name="p'.$key.'" value="1" />
    <div class="form-wrap">
      <label for="ft_delete"><input type="checkbox" id="ft_delete" name="post_delete_confirm" value="1" /> <strong>'.
    $this->conf->plugin_phrases_arr['confirm_delete_post'].' ('.count($post_id_arr).' ';
    if(count($post_id_arr) == 1)
      echo $this->conf->plugin_phrases_arr['post_singular'];
    else
      echo $this->conf->plugin_phrases_arr['posts'];
    echo ')</strong></label>';

    foreach($post_id_arr as $post_id)
    {
      echo '<input type="hidden" name="post_id_arr[]" value="'.$post_id.'" />';
    }
    echo '<br /><br />
      <input type="submit" name="" value="'.strip_alltags($sdlanguage['button_confirm']).'" />
    </div>
    </form>
    ';

  } //DisplayPostDeleteForm


  // ##########################################################################
  // DISPLAY TOPIC DELETE CONFIRMATION FORM
  // ##########################################################################

  function DisplayTopicDeleteForm($multiple=false)
  {
    global $sdlanguage, $userinfo;

    if(!$this->conf->forum_arr['forum_id'])
    {
      // user is trying to break the script
      $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr['err_invalid_forum_id'].'</strong>',true);
      return;
    }
    // Depending on a single- or multiple deletion it was sent by either
    // topic or forum options display:
    if(empty($multiple))
    {
      $action = 'topic_options';
    }
    else
    {
      $action = 'forum_options';
    }

    if($multiple)
    {
      // List of topics marked for deletion within currently selected forum
      $topic_id_arr = GetVar('topic_id_arr', array(), 'a_whole', true, false);
    }
    else
    {
      // Only the currently viewed single topic
      $topic_id_arr = array($this->conf->topic_id);
    }

    $count = count($topic_id_arr);
    $key = 't'.md5(md5($userinfo['userid'].$this->conf->forum_arr['forum_id']).$count);

    echo '<h2>' . $this->conf->forum_arr['title'] . '</h2>
    <form id="forum-admin" action="' . RewriteLink() . '" method="post">
    '.PrintSecureToken(FORUM_TOKEN).'
    <input type="hidden" name="forum_action" value="'.$action.'" />
    <input type="hidden" name="topic_action" value="delete_topics" />
    <input type="hidden" name="forum_id" value="'.$this->conf->forum_arr['forum_id'].'" />
    <input type="hidden" name="'.$key.'" value="1" />
    <div class="form-wrap">
      <label for="ft_delete"><input type="checkbox" id="ft_delete" name="topic_delete_confirm" value="1" /> <strong>'.
    $this->conf->plugin_phrases_arr['confirm_delete_topic'].' ('.$count.' ';
    if($count == 1)
    {
      echo $this->conf->plugin_phrases_arr['topic_singular'];
    }
    else
    {
      echo $this->conf->plugin_phrases_arr['topics'].')';
    }
    echo ')</strong></label>';

    foreach($topic_id_arr as $topic_id)
    {
      echo '<input type="hidden" name="topic_id_arr[]" value="'.(int)$topic_id.'" />';
    }
    echo '<br />
      <input type="submit" name="" value="'.strip_alltags($sdlanguage['button_confirm']).'" />
    </div>
    </form>
    ';

  } //DisplayTopicDeleteForm


  // ##########################################################################
  // DISPLAY TOPIC MOVE CONFIRMATION FORM (ADMIN ONLY)
  // ##########################################################################

  function DisplayTopicsMoveForm($multiple=false) //SD321
  {
    global $DB, $sdlanguage, $userinfo;

    if(!$this->conf->forum_arr['forum_id'])
    {
      // user is trying to break the script
      $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr['err_invalid_forum_id'].'</strong>',true);
      return;
    }
    // Depending on a single- or multiple deletion it was sent by either
    // topic or forum options display:
    $action = empty($multiple) ? 'topic_options' : 'forum_options';

    if($multiple)
      // List of topics marked for deletion within currently selected forum
      $topic_id_arr = GetVar('topic_id_arr', array(), 'a_whole', true, false);
    else
      // Only the currently viewed single topic
      $topic_id_arr = array($this->conf->topic_id);

    echo '<br /><h2>' . $this->conf->plugin_phrases_arr['topic_options_move_topic'].
         ' - '.$this->conf->plugin_phrases_arr['breadcrumb_forum'].' "'.
         $this->conf->forum_arr['title'].'"</h2>
    <form id="forum_move_topic_form" action="' .
    RewriteLink((empty($multiple)?'&amp;topic_id='.$this->conf->topic_id:'')) . '" method="post">
    '.PrintSecureToken(FORUM_TOKEN).'
    <input type="hidden" name="forum_action" value="'.$action.'" />
    <input type="hidden" name="topic_action" value="move_topics" />
    <input type="hidden" name="old_forum_id" value="'.$this->conf->forum_arr['forum_id'].'" />
    <div class="form-wrap">
    ';

    $this->DisplayForumSelection(0, 1, 0, '', 'forum_id', '', $this->conf->forum_arr['forum_id'],10);

    echo '<p>* = '.$this->conf->plugin_phrases_arr['forum_offline'];

    echo '</p><br />
      <label for="ft_move"><input type="checkbox" id="ft_move" name="topics_move_confirm" value="1" /> <strong>'.
         $this->conf->plugin_phrases_arr['confirm_move_topic'].'</strong></label>';

    foreach($topic_id_arr as $topic_id)
    {
      echo '<input type="hidden" name="topic_id_arr[]" value="'.(int)$topic_id.'" />';
    }
    echo '<br /><br />
      <input type="submit" name="submit" value="'.strip_alltags($sdlanguage['button_confirm']).'" />
    </div></form>';

  } //DisplayTopicsMoveForm


  // ##########################################################################
  // DISPLAY POSTS MOVE CONFIRMATION FORM (ADMIN ONLY)
  // ##########################################################################

  function DisplayPostsMoveForm() //SD343
  {
    global $DB, $categoryid, $sdlanguage, $userinfo;

    if(!$this->conf->forum_id || !$this->conf->topic_id)
    {
      // user is trying to break the script
      $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr['err_invalid_forum_id'].'</strong>',true);
      return false;
    }

    $posts_id_arr = GetVar('post_id_arr', array(), 'array', true, false);
    foreach($posts_id_arr as $id)
    {
      if(empty($id) || !Is_Valid_Number($id,0,1,99999999))
      {
        $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr['message_invalid_operation'].'</strong>',true);
        return false;
      }
    }

    echo '<br /><h2>' . $this->conf->plugin_phrases_arr['topic_options_move_posts'] .
    ' - "'.$this->conf->topic_arr['title'].'"</h2>
    <form id="forum_move_posts_form" action="'.RewriteLink('index.php?categoryid='.$categoryid).'" method="post">
    '.PrintSecureToken(FORUM_TOKEN).'
    <input type="hidden" name="forum_action" value="topic_options" />
    <input type="hidden" name="posts_action" value="move_posts" />
    <input type="hidden" name="old_topic_id" value="'.$this->conf->topic_id.'" />
    <input type="hidden" name="old_forum_id" value="'.$this->conf->forum_id.'" />
    <div class="form-wrap">
    <label for="ft_move1"><input type="radio" id="ft_move1" name="move_type" value="0" checked="checked" />
    <strong>'.$this->conf->plugin_phrases_arr['move_posts_new_topic'].'</strong></label><br />
    <br />
    '.$this->conf->plugin_phrases_arr['destination_forum'].'<br />
    ';
    $this->DisplayForumSelection(0, 1, 0, '', 'forum_id', '', $this->conf->forum_id,10);
    echo '<br />
    * = '.$this->conf->plugin_phrases_arr['forum_offline'].'<br /><br />
    '.$this->conf->plugin_phrases_arr['enter_new_topic_title'].'<br />
    <input type="text" name="forum_topic_title" value="" maxlength="100" style="padding:2px;" />
    <br />
    <hr />
    <br />
    <label for="ft_move2"><input type="radio" name="move_type" value="1" />
    <strong>'.$this->conf->plugin_phrases_arr['move_posts_existing_topic'].'</strong></label><br />
    <br />
    '.$this->conf->plugin_phrases_arr['enter_existing_topic_id'].'<br />
    <input type="text" name="topic_id" value="" maxlength="8" style="width:50px;padding:2px;" /><br />
    <br />
    <label for="ft_move3"><input type="checkbox" id="ft_move3" name="posts_move_confirm" value="1" /> <strong>'.
    $this->conf->plugin_phrases_arr['confirm_move_posts'].'</strong></label>';

    foreach($posts_id_arr as $post_id)
    {
      if(!empty($post_id) && Is_Valid_Number($post_id,0,1,99999999))
      echo '<input type="hidden" name="posts_id_arr[]" value="'.(int)$post_id.'" />';
    }
    echo '<br /><br />
      <input type="submit" name="submit" value="'.strip_alltags($sdlanguage['button_confirm']).'" />
    </div></form>';

  } //DisplayPostsMoveForm


  // ##########################################################################
  // DISPLAY TOPIC MERGE CONFIRMATION FORM (ADMIN ONLY)
  // ##########################################################################

  function DisplayTopicsMergeForm() //SD321
  {
    global $DB, $sdlanguage, $userinfo;

    if(!$this->conf->forum_arr['forum_id'])
    {
      // user is trying to break the script
      $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr['err_invalid_forum_id'].'</strong>',true);
      return;
    }

    $topic_id_arr = GetVar('topic_id_arr', array(), 'a_whole', true, false);
    if(empty($topic_id_arr))
    $topic_id_arr[] = '-1'; // Dummy to avoid empty array

    echo '<h2>'.$this->conf->plugin_phrases_arr['topic_options_merge_topics'].' - '.$this->conf->plugin_phrases_arr['breadcrumb_forum'].'"'.
        $this->conf->forum_arr['title'] . '"</h2>
        <form id="forum-admin" action="' . RewriteLink() . '" method="post">
        '.PrintSecureToken(FORUM_TOKEN).'
        <input type="hidden" name="forum_action" value="forum_options" />
        <input type="hidden" name="topic_action" value="merge_topics" />
        <input type="hidden" name="forum_id" value="'.$this->conf->forum_arr['forum_id'].'" />
        <div class="form-wrap">
          ' . $this->conf->plugin_phrases_arr['message_topics_merge_hint'] . '<br />';

    $topic_ids = implode(',', $topic_id_arr);
    echo '
          <select name="topic_id" style="min-width: 300px; margin-bottom: 8px;; margin-top: 8px;">';
    if($get_topics = $DB->query(
       'SELECT ft.date topic_date, ft.topic_id, ft.title'.
       ' FROM {p_forum_topics} ft'.
       ' INNER JOIN {p_forum_forums} ff ON ft.forum_id = ff.forum_id'.
       ' WHERE ff.forum_id = %d'.
       ($this->conf->IsSiteAdmin ? '' : ' AND ff.online = 1').
       ' AND ft.topic_id IN ('.$topic_ids.') '.
       $this->conf->GroupCheck.
       ($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ? '' : ' AND (IFNULL(ft.moderated,0) = 0)').
       ' ORDER BY ft.date DESC, ft.title ASC',
       $this->conf->forum_arr['forum_id'], $this->conf->forum_arr['title']))
    {
      while($topic_row = $DB->fetch_array($get_topics,null,MYSQL_ASSOC))
      {
        echo '<option value="'.$topic_row['topic_id'].'">'.
          DisplayDate($topic_row['topic_date']).' - '.
          strip_alltags($topic_row['title']).'</option>';
      } //while
    }
    echo '
          </select>
          <br />';

    echo '
          <label for="ft_merge"><input type="checkbox" id="ft_merge" name="topics_merge_confirm" value="1" /> <strong></label>'.
          $this->conf->plugin_phrases_arr['confirm_merge_topics'];

    foreach($topic_id_arr as $topic_id)
    {
      echo '
          <input type="hidden" name="topic_id_arr[]" value="'.(int)$topic_id.'" />';
    }
    echo '</strong>
          <br /><br />
          <input type="submit" name="submit" value="'.strip_alltags($sdlanguage['button_confirm']).'" />
        </div>
        </form>
        ';

  } //DisplayTopicsMergeForm


  // ##########################################################################
  // DISPLAY TOPIC USER MODERATION FORM (ADMIN ONLY)
  // ##########################################################################

  function DisplayUserModerationForm($moderation=0)
  {
    global $DB, $usersystem;

    $tmp = GetVar('topic_id', '', 'string');
    if(!$this->conf->topic_arr['topic_id'] && ($tmp!='xxx'))
    {
      // user is trying to break the script
      $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr['err_invalid_forum_id'].'</strong>',true);
      return false;
    }

    $user_id = Is_Valid_Number(GetVar('user_id', 0, 'whole_number'),0,1);

    if(!$user_id || !($user_arr = sd_GetUserrow($user_id)))
    {
      $action = $moderation ? 'topic_options_moderate_user' : 'topic_options_unmoderate_user';
      $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr[$action] . '</strong><br /><br />' .
                                 $this->conf->plugin_phrases_arr['message_invalid_operation'],true);
      return false;
    }

    $prev_result_type = $DB->result_type;
    $DB->result_type = MYSQL_ASSOC;
    $post_arr    = $DB->query_first('SELECT ip_address FROM {p_forum_posts} WHERE user_id = '.$user_id.' ORDER BY post_id DESC LIMIT 1');
    $DB->result_type = MYSQL_ASSOC;
    $post_count  = $DB->query_first('SELECT COUNT(*) pcount FROM {p_forum_posts} WHERE user_id = '.$user_id);
    $DB->result_type = MYSQL_ASSOC;
    $topic_count = $DB->query_first('SELECT COUNT(*) tcount FROM {p_forum_topics} WHERE post_user_id = '.$user_id);
    $DB->result_type = MYSQL_ASSOC;
    $forum_count = $DB->query_first('SELECT COUNT(DISTINCT forum_id) fcount FROM {p_forum_topics} WHERE post_user_id = '.$user_id);
    $DB->result_type = $prev_result_type;
    if(!$post_count && !$topic_count && !$forum_count)
    {
      $action = $moderation ? 'topic_options_moderate_user' : 'topic_options_unmoderate_user';
      $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr[$action] . '</strong><br /><br />' .
                                 $this->conf->plugin_phrases_arr['message_no_data_no_changes'],true);
      return false;
    }

    echo '
    <form id="forum-admin" action="' . RewriteLink() . '" method="post">';
    if($tmp=='xxx')
    {
      echo '
      <input type="hidden" name="topic_id" value="xxx" />';
    }
    else
    {
      echo '
      <input type="hidden" name="forum_id" value="'.$this->conf->forum_arr['forum_id'].'" />
      <input type="hidden" name="topic_id" value="'.$this->conf->topic_arr['topic_id'].'" />';
    }
    echo '
      <input type="hidden" name="forum_action" value="topic_options" />
      <input type="hidden" name="topic_action" value="'.($moderation ? 'moderate_user' : 'unmoderate_user').'" />
      '.PrintSecureToken(FORUM_TOKEN).'
      <input type="hidden" name="user_id" value="'.$user_id.'" />
      <input type="hidden" name="username" value=\''.$user_arr['username'].'\' />
      <input type="hidden" name="old_usergroup_id" value="'.$user_arr['usergroupid'].'" />';

    $temp = $this->conf->plugin_phrases_arr['msg_moderate_user_stats'];
    $temp = str_replace(array('[username]','[topics_count]','[posts_count]','[forums_count]'),
                        array($user_arr['username'],
                              $topic_count['tcount'],
                              $post_count['pcount'],
                              $forum_count['fcount']), $temp);

    echo '
      <div class="form-wrap">
        <h2>'.$this->conf->plugin_phrases_arr['topic_options_'.(empty($moderation)?'un':'').'moderate_user'].'</h2><br /><br />
        '.$temp.'
        <div class="clear"></div><br />
        '.$this->conf->plugin_phrases_arr['msg_moderate_user_posts_hint1'].'<br />
        <label for="fp_mod1"><input type="radio" id="fp_mod1" name="mod_posts_action[]" value="1" checked="checked" /> '.
        $this->conf->plugin_phrases_arr['msg_mod_option_posts_not_change'].'</label><br />
        <label for="fp_mod2"><input type="radio" id="fp_mod2" name="mod_posts_action[]" value="2" /> ';
    if($moderation)
    {
      echo $this->conf->plugin_phrases_arr['msg_mod_option_posts_mod_all'].'</label><br />
        <label for="fp_mod3"><input type="radio" id="fp_mod3" name="mod_posts_action[]" value="3" /> '.
        $this->conf->plugin_phrases_arr['msg_mod_option_posts_del_all'];
    }
    else
    {
      echo $this->conf->plugin_phrases_arr['msg_mod_option_posts_unmod_all'];
    }

    echo '</label><br />
        '.$this->conf->plugin_phrases_arr['msg_mod_option_topics_hint1'].'<br />
        <label for="fp_mod4"><input type="radio" id="fp_mod4" name="mod_topics_action[]" value="1" checked="checked" />
        '.$this->conf->plugin_phrases_arr['msg_mod_option_topics_not_change'].'</label>
        <label for="fp_mod5"><input type="radio" id="fp_mod5" name="mod_topics_action[]" value="2" /> ';
    if($moderation)
    {
      echo $this->conf->plugin_phrases_arr['msg_mod_option_topics_mod_all'].'<br />
        <label for="fp_mod6"><input type="radio" id="fp_mod6" name="mod_topics_action[]" value="3" /> '.
        $this->conf->plugin_phrases_arr['msg_mod_option_topics_del_all'].'<br />
        <div style="padding-left: 20px;">'.$this->conf->plugin_phrases_arr['msg_mod_option_topics_del_hint'].'</div>';
    }
    else
    {
      echo $this->conf->plugin_phrases_arr['msg_mod_option_topics_unmod_all'];
    }
    echo '<br />'.$this->conf->plugin_phrases_arr['msg_mod_option_user_mod_hint1'].'<br />
        <div class="clear"></div>
        '.$this->conf->plugin_phrases_arr['msg_mod_option_user_mod_hint2'].'<br />';
    echo '
        <div class="clear"></div>
        <select name="moderated_usergroup" size="5" style="min-width: 250px">';

    $getusergroups = $DB->query('SELECT usergroupid, name FROM {usergroups}'.
                                ' WHERE adminaccess = 0 OR usergroupid = '.$user_arr['usergroupid'].
                                ' ORDER BY usergroupid');
    while($ug = $DB->fetch_array($getusergroups,null,MYSQL_ASSOC))
    {
      echo '
        <option value="'.$ug['usergroupid'].'" '.
            ($ug['usergroupid'] == $user_arr['usergroupid'] ? 'selected="selected"' : '').">".
            strip_tags($ug['name'])."</option>";
    }

    echo '</select><br />
     ';

    if($this->conf->is_sd_users && !empty($post_arr['ip_address']))
    {
      $tmp = $this->conf->plugin_phrases_arr['msg_mod_option_ban_ip_address'];
      $tmp = str_replace('[IP_ADDRESS]',$post_arr['ip_address'],$tmp);
      echo '<div class="clear"><br /></div>
      <label for="fp_mod7"><input id="fp_mod7" name="ban_ip" type="checkbox" value="1" /> '.$tmp.'</label>
      <div class="clear"></div>
      ';
    }

    if(!empty($this->conf->topic_id))
    {
      $link = '<a href="'.RewriteLink('&amp;topic_id=' . $this->conf->topic_id).'">'.
              $this->conf->plugin_phrases_arr['topic_singular'].'</a>';
      echo '<br />'.str_replace('[topiclink]', $link, $this->conf->plugin_phrases_arr['msg_mod_option_user_mod_backlink']);
    }
    echo '<div class="clear"><br /></div>
        <input type="submit" name="" value="'.strip_alltags($this->conf->plugin_phrases_arr['proceed']).'" />
      </div>
      </form>';

  } //DisplayUserModerationForm


  // ##########################################################################

  function DeleteTopic($forum_id, $topic_id)
  {
    global $DB;

    //SD370: remove tags for topic:
    require_once(SD_INCLUDE_PATH.'class_sd_tags.php');
    SD_Tags::RemovePluginObjectTags($this->conf->plugin_id, $topic_id);

    //SD370: remove "likes" for any posts in the topic
    // Note: this will not update the "likes" counter for any users
    // as they should not loose their likes-rating.
    require_once(SD_INCLUDE_PATH.'class_sd_likes.php');
    if($getlikes = $DB->query('SELECT ul.id FROM {users_likes} ul'.
                              ' INNER JOIN {p_forum_posts} p ON p.post_id = ul.objectid'.
                              ' WHERE ul.pluginid = %d AND p.topic_id = %d'.
                              " AND ul.liked_type = '".SD_LIKED_TYPE_POST."'",
                              $this->conf->plugin_id, $topic_id))
    {
      while($like = $DB->fetch_array($getlikes,null,MYSQL_ASSOC))
      {
        SD_Likes::RemoveLikesForObject($this->conf->plugin_id, $like['id'], SD_LIKED_TYPE_POST);
      }
    }

    $DB->query('DELETE FROM {p_forum_posts} WHERE topic_id = %d', $topic_id);
    $post_count = $DB->affected_rows();

    $DB->query('DELETE FROM {p_forum_attachments} WHERE post_id IN
               (SELECT post_id FROM {p_forum_posts} WHERE topic_id = %d)', $topic_id);

    $DB->query('DELETE FROM {p_forum_topics} WHERE topic_id = %d', $topic_id);

    $this->UpdateForumInfo($forum_id, -1, -$post_count);

    return true;

  } //DeleteTopic


  // ##########################################################################

  function ModerateTopic($forum_id, $topic_id, $moderated = 1)
  {
    global $DB;

    $DB->query('UPDATE {p_forum_topics} SET moderated = %d WHERE topic_id = %d',
               $moderated, $topic_id);

    if($this->UpdateTopicInfo($topic_id))
    {
      $this->UpdateForumInfo($forum_id, ($moderated?-1:1));
    }
  } //ModerateTopic


  // ##########################################################################

  function ModerateTopics($forum_id, $topic_id_arr = null, $moderated = 1)
  {
    if(!isset($topic_id_arr))
    {
      $topic_id_arr = GetVar('topic_id_arr', array(), 'a_whole', true, false);
    }

    foreach($topic_id_arr AS $topic_id)
    {
      $this->ModerateTopic($forum_id, $topic_id, $moderated);
    }

    return true;

  } //ModerateTopics


  // ##########################################################################

  function DeleteTopics($forum_id, $topic_id_arr = null)
  {
    global $userinfo;

    if(!isset($topic_id_arr) || !is_array($topic_id_arr))
    {
      return false;
    }

    //SD343: extra security check
    $key = 't'.md5(md5($userinfo['userid'].$this->conf->forum_arr['forum_id']).
               count($topic_id_arr));
    if(empty($_POST[$key])) return false;

    foreach($topic_id_arr AS $topic_id)
    {
      $this->DeleteTopic($forum_id, $topic_id);
    }

    return true;

  } //DeleteTopics


  // ##########################################################################

  function MergeTopics()
  {
    global $DB;

    $confirmed    = GetVar('topics_merge_confirm', false, 'bool', true, false);
    $new_topic_id = GetVar('topic_id', 0, 'whole_number', true, false); // target topic id
    $topic_id_arr = GetVar('topic_id_arr', array(), 'a_whole', true, false); // to-be-merged topic id's
    $forum_id     = GetVar('forum_id', 0, 'whole_number', true, false);

    if($confirmed && !empty($topic_id_arr) && !empty($new_topic_id) && !empty($forum_id))
    {
      foreach($topic_id_arr AS $topic_id)
      {
        if(($topic_id > 0) && ($topic_id != $new_topic_id))
        {
          // Assign posts of "old" topic to the selected target topic
          $DB->query('UPDATE {p_forum_posts} SET topic_id = %d
                      WHERE topic_id = %d',
                     $new_topic_id, (int)$topic_id);
          // Remove the old topic (which now has no posts anymore)
          $DB->query('DELETE FROM {p_forum_topics} WHERE topic_id = %d',
                     (int)$topic_id);
        }
      }
      $this->UpdateTopicInfo($new_topic_id);
      $this->UpdateForumInfo($forum_id);

      $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['message_topics_merged']);
    }
    else
    {
      $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['message_topics_merged_cancelled']);
    }

  } //MergeTopics


  // ##########################################################################

  function MovePosts() //SD343
  {
    global $DB, $userinfo;

    $confirmed    = GetVar('posts_move_confirm', false, 'bool', true, false);
    $move_type    = GetVar('move_type', -1, 'natural_number', true, false);
    $posts_id_arr = GetVar('posts_id_arr', array(), 'array', true, false);
    $forum_id     = GetVar('forum_id', 0, 'whole_number', true, false);
    $topic_id     = GetVar('topic_id', 0, 'whole_number', true, false);
    $old_forum_id = GetVar('old_forum_id', 0, 'whole_number', true, false);
    $old_topic_id = GetVar('old_topic_id', 0, 'whole_number', true, false);
    $topic_title  = trim(GetVar('forum_topic_title', '', 'string', true, false));

    if($confirmed && !empty($posts_id_arr) && ($move_type !== -1) &&
       !empty($old_topic_id) && !empty($old_forum_id) &&
       ( (empty($move_type)  && !empty($forum_id) && (strlen($topic_title)>1)) ||
         (!empty($move_type) && !empty($topic_id) && ($old_topic_id != $topic_id))) )
    {
      if(empty($move_type)) // move into new topic, so create topic now
      {
        $topic_id = 0; $sticky = 0; $moderated = 0;
        // check if forum exists and is not a category
        $f_id = $DB->query_first('SELECT forum_id FROM {p_forum_forums} WHERE is_category = 0 AND forum_id = %d',$forum_id);
        if(!empty($f_id['forum_id']) && ($f_id['forum_id'] == $forum_id))
        {
          $DB->query('INSERT INTO {p_forum_topics}'.
                     ' (forum_id, date, post_count, views, open, post_user_id,'.
                     ' post_username, title, last_post_date, last_post_username, sticky, moderated) VALUES'.
                     ' (%d, %d, 1, 0, 1, ' . $userinfo['userid'] .
                     ", '%s', '%s', %d, '%s', $sticky, $moderated)",
                      $forum_id, TIME_NOW , $DB->escape_string($userinfo['username']),
                      trim($topic_title), TIME_NOW,
                      $DB->escape_string($userinfo['username']));
          if($topic_id = $DB->insert_id())
          {
            $this->conf->forum_id = $forum_id;
            $this->conf->topic_id = $topic_id;
          }
        }
      }

      if(!empty($topic_id))
      {
        foreach($posts_id_arr as $pid)
        {
          $DB->query('UPDATE {p_forum_posts} SET topic_id = %d WHERE post_id = %d',
                     $topic_id, (int)$pid);
        }
        $pcount = count($posts_id_arr);
        $this->UpdateTopicInfo($topic_id);
        $this->UpdateTopicInfo($old_topic_id);
        $this->UpdateForumInfo($old_forum_id, ($old_forum_id==$forum_id?0:-1), ($old_forum_id==$forum_id?0:-$pcount));
        $this->UpdateForumInfo($forum_id, ($old_forum_id==$forum_id?0:1), ($old_forum_id==$forum_id?0:$pcount));

        if(count($posts_id_arr)==1)
        {
          $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['message_post_moved']);
        }
        else
        {
          $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['message_posts_moved']);
        }
        return true;
      }
    }

    $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['message_posts_move_cancelled']);
    return false;

  } //MovePosts


  // ##########################################################################

  function MoveTopics()
  {
    global $DB;

    $confirmed    = GetVar('topics_move_confirm', false, 'bool', true, false);
    $topic_id_arr = GetVar('topic_id_arr', array(), 'a_whole', true, false);
    $forum_id     = GetVar('forum_id', 0, 'whole_number', true, false);
    $old_forum_id = GetVar('old_forum_id', 0, 'whole_number', true, false);

    if($confirmed && !empty($topic_id_arr) && !empty($old_forum_id) && !empty($forum_id) && ($old_forum_id != $forum_id))
    {
      foreach($topic_id_arr AS $topic_id)
      {
        $DB->query('UPDATE {p_forum_topics} SET forum_id = %d WHERE topic_id = %d',
                   $forum_id, (int)$topic_id);
      }
      $this->UpdateForumInfo($forum_id, count($topic_id_arr));
      $this->UpdateForumInfo($old_forum_id, -count($topic_id_arr));

      if(count($topic_id_arr)==1)
      {
        $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['message_topic_moved']);
      }
      else
      {
        $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['message_topics_moved']);
      }
    }
    else
    {
      $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['message_topic_move_cancelled']);
    }

  } //MoveTopics


  // ##########################################################################

  function ModerateUser($moderated = 1, $user_id=null)
  {
    global $DB;

    if(!isset($user_id))
    {
      $user_id = GetVar('user_id', 0, 'whole_number');
    }
    $user_id          = Is_Valid_Number($user_id,0,1);
    $old_usergroup_id = Is_Valid_Number(GetVar('old_usergroup_id', 0, 'whole_number', true, false),0,1);
    $new_usergroup_id = Is_Valid_Number(GetVar('moderated_usergroup', 0, 'whole_number', true, false),0,1);
    $topics_action    = GetVar('mod_topics_action', null, 'array', true, false);
    $topics_action    = is_array($topics_action) ? Is_Valid_Number($topics_action[0],0,1,3) : null;
    $posts_action     = GetVar('mod_posts_action', null, 'array', true, false);
    $posts_action     = is_array($posts_action) ? Is_Valid_Number($posts_action[0],0,1,3) : null;

    // Check if both usergroups exist
    $usergroups_exist = false;
    if($user_id && $old_usergroup_id && $new_usergroup_id &&
       ($groupcount = $DB->query_first('SELECT COUNT(usergroupid) FROM {usergroups}'.
                                       ' WHERE usergroupid IN (%d, %d)',
                                       $new_usergroup_id, $old_usergroup_id)))
    {
      // The count could 0, 1 or 2
      $usergroups_exist = !empty ($groupcount[0]) &&
                          ((($new_usergroup_id == $old_usergroup_id) && ($groupcount[0] == 1)) ||
                           ($groupcount[0] == 2));
    }

    if(!$usergroups_exist || !$user_id || !$topics_action || !$posts_action || !$old_usergroup_id || !$new_usergroup_id)
    {
      // something's wrong, bail out
      return false;
    }

    // ##### TOPICS ACTION #####
    if($topics_action == '2') // moderate
    {
      // Moderate *all* topics the user has *created*
      $post_count  = 0;
      $topic_count = 0;
      $forum_arr   = array();

      if($topic_arr = $DB->query('SELECT forum_id, topic_id FROM {p_forum_topics}'.
                                 ' WHERE post_user_id = %d'.
                                 ' ORDER BY forum_id, topic_id', (int)$user_id))
      {
        if(!isset($forum_arr[$topic_arr['forum_id']]))
        {
          $forum_arr[] = $topic_arr['forum_id'];
          $forum_arr[$topic_arr['forum_id']]['posts'] = 0;
          $forum_arr[$topic_arr['forum_id']]['topics'] = 0;
        }

        // proceeding similar to "ModerateTopics":
        $DB->query('UPDATE {p_forum_posts} SET moderated = %d WHERE topic_id = %d AND user_id = %d',
                   $moderated, $topic_id, $user_id);
        $forum_arr[$topic_arr['forum_id']]['posts'] += $DB->affected_rows();

        $DB->query('UPDATE {p_forum_topics} SET moderated = %d WHERE topic_id = %d AND post_user_id = %d',
                   $moderated, $topic_id, $user_id);
        $forum_arr[$topic_arr['forum_id']]['topics'] += $DB->affected_rows();
      }
      // Update each whose topics were affected
      foreach($forum_arr AS $forum)
      {
        $this->UpdateForumInfo($forum_id, -$forum['topics'], -$forum['posts']);
      }
    }
    else
    if($topics_action == '3') // delete
    {
      // Delete *all* topics the user has *created*
      if($gettopics = $DB->query('SELECT DISTINCT forum_id, topic_id
                                  FROM {p_forum_topics}
                                  WHERE post_user_id = %d
                                  ORDER BY forum_id, topic_id', (int)$user_id))
      {
        while($topic_arr = $DB->fetch_array($gettopics,null,MYSQL_ASSOC))
        {
          $this->ProcessPosts(array($topic_arr['topic_id']), 'delete_topics',
                              $topic_arr['forum_id'], $topic_arr['topic_id'], true);
          $this->DeleteTopic($topic_arr['forum_id'], $topic_arr['topic_id']);
        }
      }
    }

    // ##### POSTS ACTION #####
    // This comes second because most are probably done in above loop
    $sql = 'SELECT DISTINCT ft.forum_id, ft.topic_id, fp.post_id
            FROM {p_forum_topics} ft
            INNER JOIN {p_forum_posts} fp ON fp.topic_id = ft.topic_id
            WHERE fp.user_id = %d
            ORDER BY ft.forum_id, ft.topic_id';

    if($posts_action == '2') // moderate
    {
      // Moderate *all* posts the user created
      $post_count  = 0;
      $topic_count = 0;
      $forum_arr   = array();

      if($gettopics = $DB->query($sql, (int)$user_id))
      {
        while($topic_arr = $DB->fetch_array($gettopics,null,MYSQL_ASSOC))
        {
          if(!isset($forum_arr[$topic_arr['forum_id']]))
          {
            $forum_arr[$topic_arr['forum_id']]['posts'] = 0;
          }

          $DB->query('UPDATE {p_forum_posts} SET moderated = %d'.
                     ' WHERE topic_id = %d AND user_id = %d',
                     $moderated, $topic_arr['topic_id'], $user_id);
          if($affected_rows = $DB->affected_rows())
          {
            $this->UpdateTopicInfo($topic_arr['topic_id']);
            $forum_arr[$topic_arr['forum_id']]['posts'] += $affected_rows;
          }
        }

        // Update each forum whose posts were affected
        foreach($forum_arr AS $forum)
        {
          if(!empty($forum['posts']) && !empty($forum_id))
          {
            $this->UpdateForumInfo($forum_id, 0, -$forum['posts']);
          }
        }
      }
    }
    else
    if($posts_action == '3') // delete
    {
      // Delete *all* posts the user has *created*
      if($gettopics = $DB->query($sql, (int)$user_id))
      {
        while($topic_arr = $DB->fetch_array($gettopics,null,MYSQL_ASSOC))
        {
          // Note: "delete_topics" here means: delete all *posts* of
          // the user with the given topic-id
          $this->ProcessPosts(array($topic_arr['post_id']), 'delete_posts',
                              $topic_arr['forum_id'], $topic_arr['topic_id'], true);
        }
      }
    }

    // Update user to get assigned the new usergroup
    if($this->conf->is_sd_users || ($new_usergroup_id != $old_usergroup_id))
    {
      $DB->query('UPDATE {users} SET usergroupid = %d WHERE userid = %d', $new_usergroup_id, $user_id);
      $DB->query('DELETE FROM {sessions} WHERE userid = %d', $user_id);
    }

    return true;

  } //ModerateUser


  // ##########################################################################

  function DoForumOptions()
  {
    global $DB, $sdlanguage, $userinfo;

    // SD313: security check against spam/bot submissions
    if(!CheckFormToken(FORUM_TOKEN, false))
    {
      $this->conf->RedirectPageDefault('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
      return false;
    }

    if(!$this->conf->IsSiteAdmin && !$this->conf->IsAdmin && !$this->conf->IsModerator)
    {
      $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['err_no_admin_permissions'], true);
      return false;
    }

    $topic_id_arr = GetVar('topic_id_arr', false, 'a_whole',true,false);
    $topic_action = GetVar('topic_action', '', 'string');

    if(!$this->conf->forum_id)
    {
      // User is trying to break the script?
      $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr['err_invalid_forum_id'] . '</strong><br />',true);
      return false;
    }

    if($topic_id_arr === false)
    {
      // User is trying to break the script?
      $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr['err_no_topics_selected'] . '</strong><br />',true);
      return false;
    }

    // Build clean array with non-0 values
    $valid_topic_id_arr = array();
    foreach($topic_id_arr AS $topic_id)
    {
      if(is_numeric($topic_id) && ($topic_id > 0) && (int)$topic_id < 9999999)
      {
        $valid_topic_id_arr[] = (int)$topic_id;
      }
    }

    if(!count($valid_topic_id_arr))
    {
      // user is trying to break the script
      $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['err_no_topics_selected'], true);
      return false;
    }

    $message = '';

    // ########################################
    if($topic_action == 'merge_topics_confirm')
    // ########################################
    {
      $this->DisplayTopicsMergeForm(); //SD321
      return true;
    }
    else
    // ########################################
    if($topic_action == 'merge_topics') //SD321
    // ########################################
    {
      $this->MergeTopics();
      return true;
    }

    // ########################################
    if($topic_action == 'delete_topic_confirm')
    // ########################################
    {
      $this->DisplayTopicDeleteForm(true);
      return true;
    }

    // ########################################
    if($topic_action == 'delete_topics')
    // ########################################
    {
      $confirmed = GetVar('topic_delete_confirm', '', 'string', true, false);
      $message = $this->conf->plugin_phrases_arr['message_topic_deletion_cancelled'];
      if($confirmed)
      {
        if($this->DeleteTopics($this->conf->forum_id, $valid_topic_id_arr))
        {
          $message = $this->conf->plugin_phrases_arr['message_topics_deleted'];
        }
      }
    }
    else
    // ########################################################################
    if(($topic_action == 'lock_topics') || ($topic_action == 'unlock_topics'))
    // ########################################################################
    {
      $open = ($topic_action == 'lock_topics') ? 0 : 1;

      $DB->query('UPDATE {p_forum_topics} SET open = %d WHERE forum_id = %d'.
                 " AND topic_id IN (%s)",
                 $open, $this->conf->forum_id, implode(',', $valid_topic_id_arr));

      $message = ($open ? $this->conf->plugin_phrases_arr['message_topics_unlocked'] :
                          $this->conf->plugin_phrases_arr['message_topics_locked']);
    }
    else
    // ########################################################################
    if( ($topic_action == 'moderate_topics') ||
        ($topic_action == 'unmoderate_topics'))
    // ########################################################################
    {
      $value = ($topic_action == 'moderate_topics') ? 1 : 0;
      $this->ModerateTopics($this->conf->forum_id, $valid_topic_id_arr, $value);

      $message = ($value ? $this->conf->plugin_phrases_arr['message_topics_moderated'] :
                           $this->conf->plugin_phrases_arr['message_topics_unmoderated']);
    }
    else
    // ########################################################################
    if($topic_action == 'move_topics_confirm') //SD321
    // ########################################################################
    {
      $this->DisplayTopicsMoveForm(1);
      return true;
    }
    else
    // ########################################################################
    if($topic_action == 'move_topics') //SD321
    // ########################################################################
    {
      $this->MoveTopics();
      return true;
    }
    else
    // ########################################################################
    if(($topic_action == 'stick_topic') || //SD343
       ($topic_action == 'unstick_topic'))
    // ########################################################################
    {
      $sticky = ($topic_action == 'stick_topic') ? 1 : 0;
      $DB->query('UPDATE {p_forum_topics} SET sticky = %d'.
                 " WHERE topic_id IN (%s)",
                 $sticky,
                 implode(',', $valid_topic_id_arr));
      $this->conf->RedirectPageDefault(
        ($sticky ? $this->conf->plugin_phrases_arr['message_topic_sticky'] :
                   $this->conf->plugin_phrases_arr['message_topic_unsticky']));
      return true;
    }

    $this->conf->RedirectPageDefault($message);
    return true;
  } //DoForumOptions


  // ##########################################################################

  function DoTopicOptions()
  {
    global $DB, $sdlanguage, $userinfo, $usersystem;

    // SD313: security check against spam/bot submissions
    if(!CheckFormToken(FORUM_TOKEN, false))
    {
      $this->conf->RedirectPageDefault('<strong>'.$sdlanguage['error_invalid_token'].'</strong>', true);
      return false;
    }

    if(!$this->conf->IsSiteAdmin && !$this->conf->IsAdmin && !$this->conf->IsModerator)
    {
      $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr['err_no_edit_topic_access'].'</strong>',true);
      return false;
    }

    // First check if all parameters are actually valid
    $valid_posts_actions =
      array('moderate_posts', 'unmoderate_posts', 'delete_posts',
            'delete_posts_confirm', 'no_post_action',
            'move_posts_confirm', 'move_posts');
    $valid_topic_actions =
      array('moderation_user_confirm', 'unmoderation_user_confirm',
            'moderate_user', 'unmoderate_user',
            'moderate_posts', 'unmoderate_posts',
            'delete_topic_confirm', 'delete_topics',
            'move_topic_confirm', 'move_topics', 'stick_topic', 'unstick_topic',
            'lock_topic', 'unlock_topic', 'moderate_topic', 'unmoderate_topic',
            'edit_topic', /*'rename_topic',*/ 'update_topic');

    $post_id_arr  = GetVar('post_id_arr',  array(), 'a_whole');
    $posts_action = GetVar('posts_action', 'no_post_action', 'string');
    $topic_action = GetVar('topic_action', false, 'string');

    if(($posts_action && !in_array($posts_action, $valid_posts_actions)) ||
       ($topic_action && !in_array($topic_action, $valid_topic_actions)) )
    {
      // user is trying to break the script
      $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr['err_invalid_topic_id'].'</strong>',true);
      return false;
    }

    //SD360: user un-/moderation only for admins, not mods:
    if(!$this->conf->IsModerator)
    {
      // ######################################################################
      if( ($topic_action == 'moderation_user_confirm') ||
          ($topic_action == 'unmoderation_user_confirm') )
      // ######################################################################
      {
        // Step 1: display admin a confirmation page about user and the
        // available moderation options:
        $this->DisplayUserModerationForm($topic_action=='moderation_user_confirm' ? 1 : 0);
        return true;
      }

      // ######################################################################
      if(($topic_action == 'moderate_user') ||
         ($topic_action == 'unmoderate_user'))
      // ######################################################################
      {
        //SD342 add user's last known IP address to banned IP list (registration plugin)?
        if($this->conf->is_sd_users && ($user_id=GetVar('user_id',0,'whole_number',true,false)) &&
           ($ban_ip = GetVar('ban_ip',false,'bool',true,false)))
        {
          $DB->result_type = MYSQL_ASSOC;
          if($post_arr = $DB->query_first('SELECT ip_address FROM {p_forum_posts} WHERE user_id = '.$user_id.' ORDER BY post_id DESC LIMIT 1'))
          {
            if(!empty($post_arr['ip_address']) && (strlen($post_arr['ip_address'])>3))
            {
              $DB->query("UPDATE {pluginsettings} SET value = CONCAT(value,'%s') WHERE pluginid = 12 AND title = 'banned_ip_addresses'",
                         $DB->escape_string("\r\n".$post_arr['ip_address']));
            }
          }
          $DB->result_type = MYSQL_BOTH;
        }
        // Step 2: perform the selected actions on moderated user:
        if($this->ModerateUser($topic_action=='moderate_user' ? 1 : 0))
        {
          $this->conf->RedirectPageDefault($sdlanguage['settings_updated']);
        }
        else
        {
          $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['message_invalid_operation'], true);
        }

        return true;
      }
    }

    // ########################################################################
    if($topic_action == 'move_topic_confirm') //SD321
    // ########################################################################
    {
      // Step 1: display admin a confirmation page about topics and the
      // available move options:
      $this->DisplayTopicsMoveForm($topic_action=='move_topics_confirm' ? 1 : 0);
      return true;
    }

    // ########################################################################
    if($topic_action == 'move_topics') //SD321
    // ########################################################################
    {
      // Step 2: update selected topic with new forum
      $this->MoveTopics();
      return true;
    }

    // ########################################################################
    if($posts_action == 'move_posts_confirm') //SD343
    // ########################################################################
    {
      // Step 1: display admin a confirmation page about topics and the
      // available move options:
      $this->DisplayPostsMoveForm();
      return true;
    }

    // ##########################################
    if($posts_action == 'move_posts') //SD343
    // ##########################################
    {
      // Step 2: update selected topic with new forum
      $this->MovePosts();
      return true;
    }

    // ##########################################
    if($topic_action == 'stick_topic') //SD321
    // ##########################################
    {
      $DB->query('UPDATE {p_forum_topics} SET sticky = 1 WHERE topic_id = %d', $this->conf->topic_arr['topic_id']);
      $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['message_topic_sticky']);
      return true;
    }
    else
    // ##########################################
    if($topic_action == 'unstick_topic') //SD321
    // ##########################################
    {
      $DB->query('UPDATE {p_forum_topics} SET sticky = 0 WHERE topic_id = %d', $this->conf->topic_arr['topic_id']);
      $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['message_topic_unsticky']);
      return true;
    }

    // ##########################################
    if($posts_action == 'delete_posts_confirm')
    // ##########################################
    {
      $this->DisplayPostDeleteForm(); //SD343
      return true;
    }

    // ############################################################################
    if(($posts_action == 'delete_posts') ||
       ($posts_action == 'moderate_posts') || ($posts_action == 'unmoderate_posts'))
    // ############################################################################
    // SD313: the only difference between moderation of posts and deletion is that
    //        they are not physically deleted, but hidden from non-admins
    {
      if(!count($post_id_arr))
      {
        $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['err_no_messages_selected'], true);
        return true;
      }

      // SD313: former loop is now in new function ProcessPosts
      $topic_id = $posts_action == 'delete_posts' ? GetVar('topic_id',0,'whole_number',true,false) : (int)$this->conf->topic_arr['topic_id'];
      $this->ProcessPosts($post_id_arr, $posts_action, $this->conf->forum_id, $topic_id);

      if($this->conf->topic_id)
      {
        switch($posts_action)
        {
          case 'moderate_posts'   : $message = 'message_posts_moderated';
            break;
          case 'unmoderate_posts' : $message = 'message_posts_unmoderated';
            break;
          default: //'delete_posts'
                   $message = 'message_posts_deleted';
            break;
        }
        $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr[$message]);
      }
      else
      {
        $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['message_topic_deleted']);
      }
      return true;
    } // delete/moderate posts

    // ##########################################
    if($topic_action == 'delete_topic_confirm')
    // ##########################################
    {
      // Step 1: display deletion confirm page
      $this->DisplayTopicDeleteForm(false);
      return true;
    }

    // ##########################################
    if($topic_action == 'delete_topics')
    // ##########################################
    {
      // Step 2: check if deletion was confirmed
      $confirmed = GetVar('topic_delete_confirm', false, 'bool', true, false);
      $topic_id_arr = GetVar('topic_id_arr', array(), 'a_whole', true, false);

      if($confirmed && !empty($topic_id_arr))
      {
        $this->DeleteTopics($this->conf->forum_id, $topic_id_arr);
        // remove topic from class
        $this->conf->topic_arr = array();
        $this->conf->topic_id = 0;
        $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['message_topic_deleted']);
      }
      else
      {
        $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['message_topic_deletion_cancelled']);
      }
      return true;
    }

    // ########################################################################
    if(($topic_action == 'lock_topic') || ($topic_action == 'unlock_topic'))
    // ########################################################################
    {
      $locked = ($topic_action == 'lock_topic') ? 0 : 1;
      $DB->query('UPDATE {p_forum_topics} SET open = %d WHERE topic_id = %d',
                 $locked, $this->conf->topic_arr['topic_id']);

      // Staying within the topic with this:
      $this->conf->RedirectPageDefault(
        ($locked ? $this->conf->plugin_phrases_arr['message_topic_unlocked'] :
                   $this->conf->plugin_phrases_arr['message_topic_locked']));
      return true;
    }

    // ##########################################
    if(($topic_action == 'moderate_topic') ||
       ($topic_action == 'unmoderate_topic'))
    // ##########################################
    {
      $moderated = ($topic_action == 'moderate_topic') ? 1 : 0;
      $this->ModerateTopic($this->conf->topic_arr['forum_id'], $this->conf->topic_arr['topic_id'], $moderated);
      // Staying within the topic with this:
      $this->conf->RedirectPageDefault(
        ($moderated ? $this->conf->plugin_phrases_arr['message_topic_moderated'] :
                      $this->conf->plugin_phrases_arr['message_topic_unmoderated']));
      return true;
    }

    // ##########################################
    if($topic_action == 'edit_topic')
    // ##########################################
    {
      // Step 1: display entry form for topic to be renamed
      $this->DisplayTopicEditForm($this->conf->topic_id);
      return true;
    }

    // ##########################################
    if($topic_action == 'update_topic')
    // ##########################################
    {
      // Step 2: finalize the edit topic action by updating the topic
      $newtitle = trim(GetVar('forum_topic_title', null, 'string', true, false));
      if(strlen($newtitle) > 1)
      {
        $mod    = GetVar('moderated', 0, 'bool', true, false) ? 1 : 0;
        $sticky = GetVar('sticky', 0, 'bool', true, false) ? 1 : 0;
        $open   = GetVar('closed', 0, 'bool', true, false) ? 0 : 1;

        //SD343: check selected prefix (if present) against tags table, based on sub-forum/usergroup
        if($prefix_id = Is_Valid_Number(GetVar('prefix_id', 0, 'whole_number', true, false),0,1,999999999))
        {
          require_once(SD_INCLUDE_PATH.'class_sd_tags.php');
          $tconf = array(
            'chk_ugroups' => !$this->conf->IsSiteAdmin,
            'pluginid'    => $this->conf->plugin_id,
            'objectid'    => 0,
            'tagtype'     => 2,
            'ref_id'      => 0,
            'allowed_id'  => $this->conf->forum_arr['forum_id'],
          );
          $prefixes = SD_Tags::GetPluginTagsAsArray($tconf);
          if(($prefixes !== false) && isset($prefixes[$prefix_id]))
          {
            SD_Tags::StorePluginTags($this->conf->plugin_id, $this->conf->topic_id, $prefixes[$prefix_id], 2, $prefix_id, true);
          }
        }

        //SD343: Remove topic prefix
        if(GetVar('remove_prefix', 0, 'bool', true, false))
        {
          $DB->query('DELETE FROM {tags} WHERE pluginid = %d AND objectid = %d AND tagtype = 2',
                     $this->conf->plugin_id, $this->conf->topic_id);
        }

        //SD343: add topic tags if allowed for usergroup
        $tag_ug = sd_ConvertStrToArray($this->conf->plugin_settings_arr['tag_submit_permissions'],'|');
        if($this->conf->IsSiteAdmin || $this->conf->IsAdmin ||
           (!empty($tag_ug) && @array_intersect($userinfo['usergroupids'], $tag_ug)))
        {
          $tags = GetVar('tags', '', 'string', true, false);
          require_once(SD_INCLUDE_PATH.'class_sd_tags.php');
          SD_Tags::StorePluginTags($this->conf->plugin_id, $this->conf->topic_id, $tags, 0, 0, true);
        }

        // Update topic
        $DB->query("UPDATE {p_forum_topics} SET title = '%s', moderated = %d, open = %d, sticky = %d".
                   ' WHERE topic_id = %d',
                   trim($newtitle), $mod, $open, $sticky, $this->conf->topic_id);
        // Update forum which has topic as last topic
        $DB->query("UPDATE {p_forum_forums} SET last_topic_title = '%s' WHERE last_topic_id = %d",
                   trim($newtitle), $this->conf->topic_id);

        // Staying within the topic with this:
        $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['message_topic_updated']);
      }
      else
      {
        $this->DisplayTopicEditForm($this->conf->topic_id);
      }
      return true;
    }
    return null;
  } //DoTopicOptions

} //end of class
} //DO NOT REMOVE!!!
