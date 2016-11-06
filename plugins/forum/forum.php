<?php
if(!defined('IN_PRGM')) exit();

require_once(SD_INCLUDE_PATH.'class_cache.php');
require_once(ROOT_PATH.'plugins/forum/forum_config.php');

// ############################################################################
// SUBDREAMER FORUM CLASS
// ############################################################################

class SDForum
{
  private $show_forum_ids = array();
  public $conf = null;

  function SDForum()
  {
    global $DB, $bbcode, $mainsettings, $sd_forum_config, $userinfo, $usersystem;

    if(!isset($sd_forum_config) || !defined('SD_FORUM_CONFIG') || !SD_FORUM_CONFIG)
    {
      $this->conf = new SDForumConfig();
      $this->conf->attach_path = 'plugins/forum/attachments/'; // default
    }
    else
    {
      $this->conf = $sd_forum_config;
    }
    if(!$this->conf->InitStatus)
    {
      $this->conf->InitFrontpage(); //SD343
    }

    //SD342 use new static SDUserCache class
    SDUserCache::$ForumAvatarAvailable  = function_exists('ForumAvatar');
    SDUserCache::$img_path              = 'plugins/forum/images/';
    SDUserCache::$bbcode_detect_links   = !empty($this->conf->plugin_settings_arr['auto_detect_links']);
    SDUserCache::$lbl_offline           = $this->conf->plugin_phrases_arr['user_offline'];
    SDUserCache::$lbl_online            = $this->conf->plugin_phrases_arr['user_online'];
    SDUserCache::$lbl_open_profile_page = $this->conf->plugin_phrases_arr['open_your_profile_page'];
    SDUserCache::$lbl_view_member_page  = $this->conf->plugin_phrases_arr['view_member_page'];
    SDUserCache::$show_avatars          = !empty($this->conf->plugin_settings_arr['display_avatar']) &&
                                          (!isset($userinfo['profile']['user_view_avatars']) ||
                                           !empty($userinfo['profile']['user_view_avatars']));

  } //SDForum

  // ##########################################################################
  // DELETE ATTACHMENT
  // ##########################################################################

  function DeleteAttachment($p_id = null, $a_id = null)
  {
    global $DB, $userinfo;

    if(!$this->conf->attach_path_ok || empty($userinfo['loggedin'])) return false; //SD342

    if(!CheckFormToken(FORUM_TOKEN, false))
    {
      $this->conf->RedirectPageDefault('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
      return;
    }

    if(!empty($p_id) && !empty($a_id))
    {
      $post_id = Is_Valid_Number($p_id,0,1);
      $attachment_id = Is_Valid_Number($a_id,0,1);
    }
    else
    {
      $post_id = Is_Valid_Number(GetVar('post_id', 0, 'whole_number'),0,1);
      $attachment_id = Is_Valid_Number(GetVar('attachment_id', 0, 'whole_number'),0,1);
    }

    if(!empty($post_id) && !empty($attachment_id) && !empty($userinfo['userid']) &&
       ($attachment_arr = $DB->query_first('SELECT attachment_id, user_id, filename'.
                                            ' FROM {p_forum_attachments}'.
                                            ' WHERE post_id = %d AND attachment_id = %d',
                                            $post_id, $attachment_id)))
    {
      if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ||
         ($attachment_arr['user_id']==$userinfo['userid']))
      {
        $file_path = ROOT_PATH.$this->conf->attach_path.implode('/', preg_split('//', $userinfo['userid'], -1, PREG_SPLIT_NO_EMPTY)).'/'.$attachment_arr['filename'];
        if(file_exists($file_path))
        {
          @unlink($file_path);
        }
        $DB->query('DELETE FROM {p_forum_attachments} WHERE post_id = %d AND attachment_id = %d',
                   $post_id, $attachment_id);
        $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr['attachment_deleted'].'</strong>');
        return true;
      }
    }
    $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr['attachment_delete_failed'].'</strong>',true);
    return false;

  } //DeleteAttachment


  // ###############################################################################
  // UPLOAD ATTACHMENT
  // ###############################################################################

  function UploadAttachment($attachment_arr)
  {
    global $DB, $sdlanguage, $userinfo;

    if(!$this->conf->attach_path_ok) return false; //SD342

    $attachment_uploaded = false;
    $attachment_stored_filename = '';
    $error = '';
    if(empty($userinfo['userid']))
    {
      $error = $this->conf->plugin_phrases_arr['attachment_upload_invalid'];
    }
    else
    if(!empty($attachment_arr['error']))
    {
      if(in_array($attachment_arr['error'], array(1,2,3,6)))
      {
        $error = $sdlanguage['upload_err_'.(int)$attachment_arr['error']];
      }
      else
      {
        $error = false;
      }
    }
    if(empty($attachment_arr['size']))
    {
      $error = $this->conf->plugin_phrases_arr['attachment_filesize_error'];
    }
    else
    {
      $accept_all = false;
      $attachment_type_is_valid = false;
      if($this->conf->plugin_settings_arr['valid_attachment_types']=='*')
        $accept_all = true;
      else
      {
        $valid_attachment_types = strtolower(str_replace(' ', ',', $this->conf->plugin_settings_arr['valid_attachment_types']));
        $valid_attachment_types_arr = sd_ConvertStrToArray($valid_attachment_types,',');
      }

      $pos = strrpos($attachment_arr['name'], '.'); // search for the period in the filename
      if($pos !== false)
      {
        $attachment_extension = strtolower(substr($attachment_arr['name'], $pos + 1));
        $attachment_type_is_valid = in_array($attachment_extension,$valid_attachment_types_arr);
        #SD343: do not allow PHP extention anywhere in the filename!
        if(!$accept_all && !in_array('php',$valid_attachment_types_arr) &&
           preg_match('/\.php([2-6])?|\.phtml|\.html?|\.pl|\.cgi|\.sh|\.cmd/', $attachment_arr['name']))
        {
          $attachment_type_is_valid = false;
        }
      }

      if(!$attachment_type_is_valid)
      {
        $error = $this->conf->plugin_phrases_arr['invalid_file_type'];
      }
      else
      {
        // attachment is okay, try and move it over
        $file_path = ROOT_PATH.$this->conf->attach_path;
        $file_path .= (substr($file_path,-1) == '/' ? '' : '/');
        if(@is_writable($file_path))
        {
          // create folder structure
          $file_path .= implode('/', preg_split('//', $userinfo['userid'], -1, PREG_SPLIT_NO_EMPTY)).'/';
          if(!is_dir($file_path))
          {
            @mkdir($file_path, intval("0777", 8), true);
          }
          // create unique filename within folder
          while(true)
          {
            $attachment_stored_filename = CreateGuid() . '.dat';
            if(!file_exists($file_path.$attachment_stored_filename)) break;
          }
          // move file and set permissions
          $file_path .= $attachment_stored_filename;
          if(@move_uploaded_file($attachment_arr['tmp_name'], $file_path))
          {
            $attachment_uploaded = true;
            @chmod($file_path, intval("0644", 8));
          }
        }
        if(!$attachment_uploaded)
        {
          $error = $this->conf->plugin_phrases_arr['upload_error'];
        }
      }
    }

    return array($attachment_uploaded, $attachment_stored_filename, $error);

  } //UploadAttachment


  // ##########################################################################
  // DISPLAY ATTACHMENTS
  // ##########################################################################

  function DisplayAttachments($topic_id, $post_id)
  {
    global $DB, $userinfo;

    if(!Is_Valid_Number($post_id,0,1))
    {
      return '';
    }

    if(!$this->conf->attach_path_exists) return ''; //SD342

    $get_attachments = $DB->query('SELECT attachment_id, attachment_name, download_count, user_id'.
                                  ' FROM {p_forum_attachments}'.
                                  ' WHERE post_id = %d'.
                                  ' ORDER BY attachment_id ASC', $post_id);

    if(!$DB->get_num_rows($get_attachments))
    {
      unset($get_attachments);
      return;
    }

    echo '<div class="forum-attachments"><p>'.$this->conf->plugin_phrases_arr['title_attachments'].'</p>
          <ul>';

    $hasAccess = !empty($userinfo['adminaccess']) ||
                 ( (!empty($userinfo['pluginadminids']) && @in_array($this->conf->plugin_id, $userinfo['pluginadminids'])) ||
                   (!empty($userinfo['plugindownloadids']) && @in_array($this->conf->plugin_id, $userinfo['plugindownloadids'])));
    while($attachment_arr = $DB->fetch_array($get_attachments,null,MYSQL_ASSOC))
    {
      if($hasAccess)
      {
        $canDelete = $this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ||
                     ($userinfo['userid'] == $attachment_arr['user_id']);
        echo '<li><a rel="nofollow" href="'.ROOT_PATH.'plugins/forum/attachments.php?post_id='.(int)$post_id.'&amp;attachment_id='.$attachment_arr['attachment_id'].
          '"><img src="'.SDUserCache::$img_path.'download.png" width="16" height="16" alt="" /> ' .
             $attachment_arr['attachment_name'] . '</a> ('.$attachment_arr['download_count'] . ') ';
        if($canDelete)
        {
          $link = $this->conf->RewriteTopicLink($this->conf->topic_id,
                   (isset($this->conf->topic_arr['title'])?$this->conf->topic_arr['title']:0));
          echo '<a class="forum_attachment_delete" href="' .
                  $link.($this->conf->seo_enabled?'?':'&amp;').
                  'post_action=attdel&amp;post_id='.(int)$post_id.'&amp;attachment_id='.
                  $attachment_arr['attachment_id'].PrintSecureUrlToken(FORUM_TOKEN).'">
                <img src="'.SDUserCache::$img_path.'delete.png" alt="X" width="16" height="16" /></a>';
        }
        echo '</li>';
      }
      else
      {
        echo '<li>'.$attachment_arr['attachment_name'] . ' ('.$attachment_arr['download_count'] . ')</li>';
      }
    }

    echo '</ul>
          ';
    if(!$hasAccess)
    {
      echo $this->conf->plugin_phrases_arr['err_member_no_download'];
    }
    echo '
          </div>';

  } //DisplayAttachments


  private function GetTopicPrefix($topicid)
  {
    global $DB;
    if(empty($topicid) || ($topicid < 1)) return false;

    $DB->result_type = MYSQL_ASSOC;
    $prefix = $DB->query_first(
      'SELECT t2.tagid, t2.tag, t2.html_prefix'.
      ' FROM '.PRGM_TABLE_PREFIX.'tags t1'.
      ' INNER JOIN '.PRGM_TABLE_PREFIX.'tags t2 ON t2.tagid = t1.tag_ref_id'.
      ' WHERE t1.pluginid IN (0, %d) AND t1.objectid = %d AND t1.censored = 0'.
      ' AND t2.pluginid IN (0, %d) AND t2.tagtype = 2 AND t2.censored = 0 LIMIT 1',
      $this->conf->plugin_id, $topicid, $this->conf->plugin_id);
    if(empty($prefix['tagid'])) return false;

    return $prefix;
  }


  // ##########################################################################
  // INSERT TOPIC
  // ##########################################################################
  // This must be public for Articles "Post to Forum" feature!

  public function InsertTopic($fromArticle=false,$useArticleDescr=false,
                              $useArticleText=false,$link2article=false)
  {
    global $DB, $categoryid, $sdlanguage, $sdurl, $userinfo, $usersystem;

    //SD351: create topic from article
    $fromArticle = (defined('IN_ADMIN') && !empty($fromArticle));
    if($fromArticle && empty($useArticleDescr) && empty($useArticleText))
    {
      return false;
    }

    // SD313: security check against spam/bot submissions
    if(empty($fromArticle))
    {
      if(!CheckFormToken(FORUM_TOKEN, false))
      {
        $this->conf->RedirectPageDefault('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
        return false;
      }
      if(empty($this->conf->forum_arr['forum_id']) || ($this->conf->forum_arr['forum_id']<1))
      {
        $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr['err_invalid_forum_id'] . '</strong>',true);
        return false;
      }
    }

    $errors_arr = array();

    if(empty($fromArticle))
    {
      $topic_title = trim(GetVar('forum_topic_title', '', 'string', true, false));
      $post_text   = trim(GetVar('forum_post', '', 'string', true, false));

      if(!($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator) ||
         !empty($userinfo['require_vvc'])) //SD340: require_vvc
      {
        if(!CaptchaIsValid('amihuman'))
        {
          $errors_arr[] = $sdlanguage['captcha_not_valid'];
        }
      }

      //SD343: combined blacklist check
      if($this->conf->IsUserBlacklisted())
      {
        WatchDog('Forum','<b>Forum spam topic: '.$userinfo['username'].
                 '</b>, IP: </b><span class="ipaddress">'.USERIP.'</span></b><br />'.
                 'in TopicID: '.$this->conf->topic_arr['topic_id'].', PostID: '.$post_id,
                  WATCHDOG_ERROR);
        $this->DisplayTopic(true,$sdlanguage['ip_listed_on_blacklist'].' '.USERIP);
        return false;
      }

      //SD342: censor non-admin text if enabled
      if($this->conf->censor_posts)
      {
        $topic_title = sd_censor($topic_title);
        $post_text = sd_censor($post_text);
      }
    }
    else
    {
      // #################################################################
      // SD351: post article as forum topic
      // #################################################################
      $topic_title = trim(GetVar('title', '', 'html', true, false));
      $topic_title = htmlspecialchars(strip_alltags($topic_title));

      $post_text = '';
      if(!empty($useArticleDescr))
      {
        $article_descr = trim(GetVar('description', '', 'html', true, false));
        if(sd_strlen($article_descr))
        {
          $post_text = trim(sd_ConvertHtmlToBBCode($article_descr));
          if(sd_strlen($post_text))
          {
            $post_text .= "\r\n\r\n";
          }
        }
      }
      if(!empty($useArticleText))
      {
        $article_text = trim(GetVar('article', '', 'html', true, false));
        $article_text = sd_ConvertHtmlToBBCode($article_text);
        if(sd_strlen($article_text))
        {
          $post_text2 = trim($article_text);
          if(sd_strlen($post_text2))
          {
            $post_text .= "\r\n\r\n".$post_text2;
          }
        }
      }
      if(!empty($link2article) && ($link2article!==false))
      {
        $post_text .= $link2article;
      }
      unset($article_descr,$article_text,$post_text2,$useArticleDescr);
    }

    $sticky = 0;
    $open = 1;
    $moderated = GetVar('moderate_topic', false, 'bool', true, false)?1:0;
    if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator)
    {
      $open = GetVar('lock_topic', 0, 'bool', true, false)?0:1; //SD351
      $sticky = GetVar('stick_topic', false, 'bool', true, false)?1:0;
    }
    else
    {
      if(!empty($this->conf->forum_arr['moderated']))
      {
        $moderated = SDForumConfig::UsergroupsModerated($userinfo['usergroupids'],
                                    $this->conf->forum_arr['moderated'])?1:0;
      }
    }

    //SD343: content filter for moderated posts
    if(empty($errors_arr) && $moderated &&
       !$this->conf->IsSiteAdmin && !$this->conf->IsAdmin && !$this->conf->IsModerator)
    {
      //SD343: up to x posts within y minutes if moderated
      if($this->conf->mod_post_limit && $this->conf->mod_time_limit)
      {
        $trigger = $DB->query_first('SELECT COUNT(post_id) postcount FROM {p_forum_posts}'.
                                    " WHERE ((user_id = %d) OR (ip_address = '%s')) AND (`date` > %d) AND (moderated = 1)",
                                    $userinfo['userid'], USERIP, (TIME_NOW - ($this->conf->mod_time_limit*60)));
        if(!empty($trigger['postcount']) && ($trigger['postcount'] >= $this->conf->mod_post_limit))
        {
          $errors_arr[] = $this->conf->plugin_phrases_arr['message_too_many_moderated'];
        }
      }

      if(empty($errors_arr))
      {
        $topic_title = htmlspecialchars(strip_alltags(sd_htmlawed(unhtmlspecialchars($topic_title))));
        $post_text = htmlspecialchars(sd_htmlawed(unhtmlspecialchars($post_text)));
      }
    }

    //SD343: min. topic/post length checks
    $topic_title = trim($topic_title);
    $post_text = trim($post_text);
    $plen = strlen($topic_title);

    if(empty($fromArticle))
    {
      if(($plen < 3) || ($plen < $this->conf->plugin_settings_arr['minimum_topic_title_length']))
      {
        $errors_arr[] = $this->conf->plugin_phrases_arr['err_topic_no_title'];
      }

      $plen = strlen($post_text);
      if(($plen < 2) || ($plen < $this->conf->plugin_settings_arr['minimum_post_text_length']))
      {
        $errors_arr[] = $this->conf->plugin_phrases_arr['post_too_short'];
      }
    }

    if(empty($errors_arr))
    {
      $DB->result_type = MYSQL_ASSOC;
      if($topic_exists_arr = $DB->query_first('SELECT ft.topic_id FROM {p_forum_topics} ft'.
                             ' INNER JOIN {p_forum_forums} ff ON ft.forum_id = ff.forum_id'.
                             ' WHERE ff.forum_id = %d'.
                             ($this->conf->IsSiteAdmin ? '' : ' AND ff.online = 1').
                             " AND trim(ft.title) = '%s' AND ft.post_user_id = %d",
                             $this->conf->forum_arr['forum_id'],
                             $DB->escape_string($topic_title),
                             $userinfo['userid']))
      {
        $errors_arr[] = $this->conf->plugin_phrases_arr['err_topic_no_repeat'];
      }
    }

    if(count($errors_arr))
    {
      DisplayMessage('<strong>'.implode('<br />', $errors_arr). '</strong>', true);
      if(empty($fromArticle))
      {
        $this->DisplayTopicForm();
      }
      return false;
    }

    // all is good, insert new topic
    $DB->query('INSERT INTO {p_forum_topics}'.
               ' (forum_id, date, post_count, views, open, post_user_id,'.
               ' post_username, title, last_post_date, last_post_username, sticky, moderated)'.
               ' VALUES (%d, %d, 1, 0, %d, ' . $userinfo['userid'] .
               ", '%s', '%s', %d, '%s', $sticky, $moderated)",
               $this->conf->forum_id, TIME_NOW, $open,
               $DB->escape_string($userinfo['username']),
               $DB->escape_string($topic_title),
               TIME_NOW,
               $DB->escape_string($userinfo['username']));

    if($topic_id = $DB->insert_id())
    {
      $DB->query('INSERT INTO {p_forum_posts}'.
                 ' (topic_id, username, user_id, date, post, ip_address, moderated) VALUES'.
                 " (%d, '%s', %d, %d, '%s', '%s', %d)",
                 $topic_id, $DB->escape_string($userinfo['username']),
                 $userinfo['userid'], TIME_NOW,
                 $DB->escape_string($post_text), IPADDRESS, $moderated);

      if($post_id = $DB->insert_id())
      {
        //SD351: set config's settings
        $this->conf->topic_id = $topic_id;
        $this->conf->topic_arr = array();
        $this->conf->topic_arr['topic_id']  = (int)$topic_id;
        $this->conf->topic_arr['forum_id']  = (int)$this->conf->forum_arr['forum_id'];
        $this->conf->topic_arr['title']     = $topic_title;
        $this->conf->topic_arr['sticky']    = $sticky;
        $this->conf->topic_arr['moderated'] = $moderated;
        $this->conf->topic_arr['open']      = $open;
        $this->conf->topic_arr['post_user_id']   = (int)$userinfo['userid'];
        $this->conf->topic_arr['post_username']  = $userinfo['username'];
        $this->conf->topic_arr['first_post_id']  = (int)$post_id;
        $this->conf->topic_arr['last_post_id']   = (int)$post_id;
        $this->conf->topic_arr['last_post_date'] = TIME_NOW;
        $this->conf->topic_arr['last_post_username'] = $userinfo['username'];

        $DB->query("UPDATE {p_forum_topics} SET first_post_id = $post_id, last_post_id = $post_id
                    WHERE topic_id = $topic_id LIMIT 1");

        if(!$moderated)
        {
          $DB->query("UPDATE {p_forum_forums} SET last_topic_title = '".$DB->escape_string(trim($topic_title))."',
                      topic_count = (IFNULL(topic_count,0) + 1),
                      post_count = (IFNULL(post_count,0) + 1),
                      last_post_date = " . TIME_NOW . ",
                      last_post_username = '" . $DB->escape_string($userinfo['username']) . "',
                      last_topic_id = $topic_id,
                      last_post_id = $post_id
                      WHERE forum_id = " . $this->conf->forum_arr['forum_id'] . '
                      LIMIT 1');

          //SD322: update user's thread count
          //SD332: update to "user_post_count" was missing till now
          if($this->conf->is_sd_users) //SD342
          $DB->query('UPDATE {users} SET user_thread_count = (IFNULL(user_thread_count,0) + 1),'.
                     ' user_post_count = (IFNULL(user_post_count,0) + 1) WHERE userid = %d', $userinfo['userid']);

          //SD351: update user title
          SDUserCache::UpdateUserTitle($userinfo['userid']);
        }

        if(empty($fromArticle))
        {
          //SD343: check selected prefix (if present) against tags table, based on sub-forum/usergroup
          if($prefix_id = Is_Valid_Number(GetVar('prefix_id', 0, 'whole_number', true, false),0,1,999999999))
          {
            $tconf = array(
              'chk_ugroups' => !$this->conf->IsSiteAdmin,
              'pluginid'    => $this->conf->plugin_id,
              'objectid'    => 0,
              'tagtype'     => 2,
              'ref_id'      => 0,
              'allowed_id'  => $this->conf->forum_arr['forum_id'],
            );
            require_once(SD_INCLUDE_PATH.'class_sd_tags.php');
            $prefixes = SD_Tags::GetPluginTagsAsArray($tconf);
            if(($prefixes !== false) && isset($prefixes[$prefix_id]))
            {
              SD_Tags::StorePluginTags($this->conf->plugin_id, $topic_id, $prefixes[$prefix_id], 2, $prefix_id, true);
            }
          }

          //SD343: add topic tags if allowed for usergroup
          $tag_ug = sd_ConvertStrToArray($this->conf->plugin_settings_arr['tag_submit_permissions'],'|');
          if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ||
             (!empty($tag_ug) && @array_intersect($userinfo['usergroupids'], $tag_ug)))
          {
            require_once(SD_INCLUDE_PATH.'class_sd_tags.php');
            $tags = GetVar('tags', '', 'string', true, false);
            SD_Tags::StorePluginTags($this->conf->plugin_id, $topic_id, $tags, 0, 0, true);
          }

          // insert attachment
          if($this->conf->attach_path_ok && isset($_FILES['attachment']) && !empty($_FILES['attachment']['name']) &&
             ($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ||
              (!empty($userinfo['plugindownloadids']) &&
               @in_array($this->conf->plugin_id,$userinfo['plugindownloadids']) &&
               !empty($this->conf->plugin_settings_arr['valid_attachment_types']))))
          {
            $attachment_arr = $_FILES['attachment'];
            // if an attachment was uploaded, then it will be inserted after the ticket is created
            $attachment_uploaded = false;
            // check if attachment was uploaded
            if($attachment_arr['error'] != 4)
            {
              list($attachment_uploaded, $attachment_stored_filename, $error) = $this->UploadAttachment($attachment_arr);
              if(!$attachment_uploaded)
              {
                if($error !== false)
                $errors_arr[] = $error;
              }
              else
              {
                $DB->query("INSERT INTO {p_forum_attachments}
                            (attachment_id, post_id, attachment_name, filename, filesize, filetype, user_id, username, uploaded_date)
                            VALUES (NULL, %d, '%s', '%s', %d, '%s', %d, '%s', " . TIME_NOW . ")",
                            $post_id,
                            $DB->escape_string($attachment_arr['name']),
                            $DB->escape_string($attachment_stored_filename),
                            $attachment_arr['size'],
                            $DB->escape_string($attachment_arr['type']),
                            $userinfo['userid'],
                            $DB->escape_string($userinfo['username']));
                $DB->query('UPDATE {p_forum_posts}'.
                           ' SET attachment_count = (IFNULL(attachment_count,0)+1)'.
                           ' WHERE post_id = %d', $post_id);
              }
            }
          }
        } //$fromArticle

      }
    }

    //SD351: if from article then return topic_id
    if(!empty($fromArticle))
    {
      return empty($topic_id)?false:(int)$topic_id;
    }

    //SD342: send email notifications for subscriptions
    if(empty($errors_arr))
    {
      if($sub = new SDSubscription($userinfo['userid'],$this->conf->plugin_id,
                                   $this->conf->forum_arr['forum_id'],'forum',$categoryid))
      {
        $sub->SendNotifications();
      }
      unset($sub);

      global $SDCache;
      if(!empty($SDCache) && $SDCache->IsActive()) $SDCache->delete_cacheid(FORUM_CACHE_FORUMS);

      $link = $this->conf->RewritePostLink($topic_id,$topic_title,$post_id);
      RedirectFrontPage($link, $this->conf->plugin_phrases_arr[$moderated ?
                        'message_topic_awaits_approval' : 'message_topic_created']);

      return true;
    }
    $this->conf->RedirectPageDefault('Error!');
    return false;
  } //InsertTopic


  // ###########################################################################
  // INSERT POST
  // ###########################################################################

  function InsertPost()
  {
    global $DB, $categoryid, $sdlanguage, $userinfo;

    // SD313: security check against spam/bot submissions
    if(!CheckFormToken(FORUM_TOKEN, false))
    {
      $this->conf->RedirectPageDefault('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
      return;
    }

    if(empty($this->conf->topic_arr['topic_id']) || ($this->conf->topic_arr['topic_id']<1))
    {
      // user is trying to break the script
      $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['err_invalid_topic_id'], true);
      return false;
    }

    if(!($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator) ||
       !empty($userinfo['require_vvc'])) //SD340: require_vvc
    {
      if(!CaptchaIsValid('amihuman'))
      {
        $this->DisplayTopic(true, $sdlanguage['captcha_not_valid']);
        return false;
      }
    }

    $post_text = GetVar('forum_post', '', 'string', true, false);
    /*
    //SD343: content filter
    if(!$this->conf->IsSiteAdmin && !$this->conf->IsAdmin)
    {
      $post_text = htmlspecialchars(sd_htmlawed(unhtmlspecialchars($post_text)));
    }
    */
    if(!UserinputSecCheck($post_text))
    {
      $this->DisplayTopic(true, $sdlanguage['err_forbidden_content']);
      return false;
    }

    $plen = strlen($post_text);
    if(($plen < 2) ||
       (!empty($this->conf->plugin_settings_arr['minimum_post_text_length']) &&
        ($plen < $this->conf->plugin_settings_arr['minimum_post_text_length'])))
    {
      DisplayMessage($this->conf->plugin_phrases_arr['post_too_short'], true);
      $this->DisplayPostForm();
      return false;
    }

    //SD342: censor non-admin text if enabled
    if($this->conf->censor_posts)
    {
      $post_text = sd_censor($post_text);
      $this->conf->topic_arr['title'] = sd_censor($this->conf->topic_arr['title']);
    }

    if(strlen($post_text) &&
       ($previous_post_arr = $DB->query_first('SELECT post FROM {p_forum_posts}
         WHERE topic_id = %d AND user_id = %d ORDER BY post_ID DESC LIMIT 1',
         $this->conf->topic_arr['topic_id'], $userinfo['userid'])))
    {
      if($previous_post_arr['post'] == $post_text)
      {
        DisplayMessage($this->conf->plugin_phrases_arr['err_topic_no_repeat'], true);
        $this->DisplayTopic();
        return;
      }
    }

    //SD343: combined blacklist check
    if($this->conf->IsUserBlacklisted())
    {
      WatchDog('Forum','<b>Forum spam post: '.$userinfo['username'].
               '</b>, IP: </b><span class="ipaddress">'.USERIP.'</span></b><br />'.
               'in TopicID: '.$this->conf->topic_arr['topic_id'].', PostID: '.$post_id,
                WATCHDOG_ERROR);
      DisplayMessage($sdlanguage['ip_listed_on_blacklist'].' '.USERIP, true);
      return;
    }

    //SD343: sticky/moderated options
    $moderated = 0;
    $moderated_post = 0;
    if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator)
    {
      $sticky = GetVar('stick_topic', false, 'bool', true, false)?1:0;
      $mod = GetVar('moderate_topic', false, 'bool', true, false)?1:0;
      $open = GetVar('lock_topic', false, 'bool', true, false)?0:1;
      $moderated_post = GetVar('reply_moderated', false, 'bool', true, false)?1:0;
      $DB->query('UPDATE {p_forum_topics}'.
                 ' SET moderated = %d, sticky = %d, open = %d'.
                 ' WHERE topic_id = %d',
                 $mod, $sticky, $open, $this->conf->topic_arr['topic_id']);
    }
    else
    {
      if(!empty($this->conf->forum_arr['moderated']))
      {
        $moderated = SDForumConfig::UsergroupsModerated($userinfo['usergroupids'],$this->conf->forum_arr['moderated'])?1:0;
      }
    }

    //SD343: content filter for moderated posts
    if(empty($errors_arr) && $moderated && !$this->conf->IsSiteAdmin &&
       !$this->conf->IsAdmin && !$this->conf->IsModerator)
    {
      //SD343: up to x posts within y minutes if moderated
      if($this->conf->mod_post_limit && $this->conf->mod_time_limit)
      {
        $trigger = $DB->query_first('SELECT COUNT(post_id) postcount FROM {p_forum_posts}'.
                                    " WHERE ((user_id = %d) OR (ip_address = '%s')) AND (`date` > %d) AND (moderated = 1)",
                                    $userinfo['userid'], USERIP, (TIME_NOW - ($this->conf->mod_time_limit*60)));
        if(!empty($trigger['postcount']) && ($trigger['postcount'] >= $this->conf->mod_post_limit))
        {
          DisplayMessage($this->conf->plugin_phrases_arr['message_too_many_moderated'], true);
          return;
        }
      }

      $post_text = htmlspecialchars(sd_htmlawed(unhtmlspecialchars($post_text)));
    }

    $DB->query('INSERT INTO {p_forum_posts}
                (topic_id, date, user_id, username, post, ip_address, attachment_count, moderated) VALUES
                (' . $this->conf->topic_arr['topic_id'] . ', ' . TIME_NOW . ', ' .
                $userinfo['userid'] . ", '%s', '%s', '%s', 0, %d)",
                $DB->escape_string($userinfo['username']), $DB->escape_string(trim($post_text)),
                $DB->escape_string(defined('USERIP')?USERIP:IPADDRESS),
                ($moderated_post || $moderated ? 1 : 0));

    $post_id = $DB->insert_id();

    // insert attachment
    if($this->conf->attach_path_ok && isset($_FILES['attachment']) && !empty($_FILES['attachment']['name']) &&
       ($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ||
        (!empty($userinfo['plugindownloadids']) && @in_array($this->conf->plugin_id, $userinfo['plugindownloadids']) &&
         !empty($this->conf->plugin_settings_arr['valid_attachment_types']))))
    {
      $attachment_uploaded = false;
      $attachment_arr = $_FILES['attachment'];
      // if an attachment was uploaded, then it will be inserted after the ticket is created
      // check if attachment was uploaded
      if($attachment_arr['error'] != 4)
      {
        list($attachment_uploaded, $attachment_stored_filename, $error) = $this->UploadAttachment($attachment_arr);

        if(!$attachment_uploaded)
        {
          DisplayMessage($error,true);
        }
        else
        {
          $DB->query("INSERT INTO {p_forum_attachments}
                      (attachment_id, post_id, attachment_name, filename, filesize, filetype, user_id, username, uploaded_date)
                      VALUES (NULL, $post_id, '%s', '$attachment_stored_filename', $attachment_arr[size], '%s',
                      $userinfo[userid], '%s', " . TIME_NOW . ")",
                      $DB->escape_string($attachment_arr['name']), $DB->escape_string($attachment_arr['type']),
                      $DB->escape_string($userinfo['username']));
          $DB->query('UPDATE {p_forum_posts} SET attachment_count = (IFNULL(attachment_count,0) + 1)
                      WHERE post_id = %d', $post_id);
        }
      }
    }

    $DB->query('UPDATE {p_forum_topics}
               SET last_post_date = ' . TIME_NOW . ",
                   post_count = (IFNULL(post_count,0) + 1),
                   last_post_username = '".$DB->escape_string($userinfo['username'])."',
                   last_post_id = %d
                   WHERE topic_id = %d",
                   $post_id, $this->conf->topic_arr['topic_id']);

    $DB->query("UPDATE {p_forum_forums}
               SET last_topic_title = '" . $DB->escape_string(trim($this->conf->topic_arr['title'])) . "',
                   post_count = (IFNULL(post_count,0) + 1),
                   last_post_date = " . TIME_NOW . ",
                   last_post_username = '".$DB->escape_string($userinfo['username'])."',
                   last_topic_id = " . $this->conf->topic_arr['topic_id'] . ",
                   last_post_id = %d
                   WHERE forum_id = %d",
                   $post_id, $this->conf->topic_arr['forum_id']);

    //SD322: update user's post count (for SD only!)
    if($this->conf->is_sd_users) //SD342
    {
      $DB->query('UPDATE {users} SET user_post_count = (IFNULL(user_post_count,0) + 1)'.
                 ' WHERE userid = %d', $userinfo['userid']);
      //SD351: update user title
      SDUserCache::UpdateUserTitle($userinfo['userid']);
    }

    //SD342: send email notifications for subscriptions
    if(!empty($this->conf->topic_arr['topic_id']))
    {
      if($sub = new SDSubscription($userinfo['userid'], $this->conf->plugin_id,
                                   $this->conf->topic_arr['topic_id'], 'topic', $categoryid))
      {
        $sub->SendNotifications();
      }
      unset($sub);

      global $SDCache;
      if(!empty($SDCache) && $SDCache->IsActive()) $SDCache->delete_cacheid(FORUM_CACHE_FORUMS);

      $link = $this->conf->RewritePostLink($this->conf->topic_arr['topic_id'],$this->conf->topic_arr['title'],$post_id);
      RedirectFrontPage($link, $this->conf->plugin_phrases_arr[$moderated ? 'message_post_awaits_approval' : 'message_post_posted']);
      return true;
    }

    $this->conf->RedirectPageDefault('Error!');
    return false;

  } //InsertPost


  // ###########################################################################
  // DISPLAY TOPIC FORM
  // ###########################################################################

  function DisplayTopicForm()
  {
    global $DB, $userinfo;

    if(!$this->conf->forum_arr['forum_id'])
    {
      // user is trying to break the script
      $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr['err_invalid_forum_id'] . '</strong>',true);
      return;
    }

    $content   = GetVar('forum_post', '', 'string', true, false);
    $title     = GetVar('forum_topic_title', '', 'string', true, false);
    $sticky    = GetVar('stick_topic', 0, 'bool', true, false)?1:0;
    $closed    = GetVar('closed', 0, 'bool', true, false)?1:0;
    $moderate  = GetVar('moderate_topic', 0, 'bool', true, false)?1:0;
    $prefix_id = GetVar('prefix_id', 0, 'whole_number', true, false);
    $tags      = GetVar('tags', '', 'string', true, false);

    //SD343: display prefixes available to user (based on sub-forum/usergroup)
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
      'selected_id' => $prefix_id,
    );

    echo '
    <h2>' . $this->conf->forum_arr['title'] . '</h2>
    <form '.($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ? 'id="forum-admin" ':'').
      'action="' . $this->conf->current_page . '" enctype="multipart/form-data" method="post">
    '.PrintSecureToken(FORUM_TOKEN).'
    <input type="hidden" name="forum_action" value="insert_topic" />
    <input type="hidden" name="forum_id" value="'.$this->conf->forum_arr['forum_id'].'" />
    <div class="form-wrap">
      <h2 style="margin:4px">'.$this->conf->plugin_phrases_arr['new_topic'].'</h2>
      <p style="margin:8px 4px 0px 4px">
      <label>'.$this->conf->plugin_phrases_arr['topic_title'].'</label>
      <input type="text" name="forum_topic_title" value="'.$title.'" /></p>
      ';

    DisplayBBEditor(SDUserCache::$allow_bbcode, 'forum_post', $content, FORUM_REPLY_CLASS, 80, 10);

    $out = '
      <fieldset>';

    $prefix = SD_Tags::GetPluginTagsAsSelect($tconf);
    if(strlen($prefix))
    {
      $out .= '
      <p style="padding-top:10px">
        <label for="prefix_id">
        '.$this->conf->plugin_phrases_arr['topic_prefix'].'
        '.$prefix.'</label></p>';
    }

    if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator)
    {
      $out .= '
      <p style="padding-top:10px">
        <label for="ft_sticky"><img alt=" " src="'.SDUserCache::$img_path.'sticky.png" width="16" height="16" />
        <input type="checkbox" id="ft_sticky" name="stick_topic" value="1"'.($sticky?' checked="checked"':'').' /> '.
        ($this->conf->plugin_phrases_arr['topic_options_stick_topic']).'</label></p>
      <p style="padding-top:10px">
        <label for="ft_closed"><img alt=" " src="'.SDUserCache::$img_path.'lock.png" width="16" height="16" />
        <input type="checkbox" id="ft_closed" name="closed" value="1"'.($closed?' checked="checked"':'').' /> '.
        ($this->conf->plugin_phrases_arr['topic_options_lock_topic']).'</label></p>
      <p style="padding-top:10px">
        <label for="ft_moderated"><img alt=" " src="'.SDUserCache::$img_path.'attention.png" width="16" height="16" />
        <input type="checkbox" id="ft_moderated" name="moderate_topic" value="1"'.($moderate?' checked="checked"':'').' /> '.
        $this->conf->plugin_phrases_arr['topic_options_moderate_topic'].'</label></p>
      ';
    }

    $tag_ug = sd_ConvertStrToArray($this->conf->plugin_settings_arr['tag_submit_permissions'],'|');
    if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ||
       (!empty($tag_ug) && @array_intersect($userinfo['usergroupids'], $tag_ug)))
    {
      $out .= '
        <p style="padding-top:10px"><label for="ft_tags">'.$this->conf->plugin_phrases_arr['topic_tags'].'
        <input type="text" id="ft_tags" name="tags" maxlength="200" size="35" value="'.$tags.'" />
        <br />'.$this->conf->plugin_phrases_arr['topic_tags_hint'].'</label></p>';
    }

    if(!empty($this->conf->plugin_settings_arr['attachments_upload_path']) &&
       ($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ||
        (!empty($this->conf->plugin_settings_arr['valid_attachment_types']) &&
         (!empty($userinfo['plugindownloadids']) && @in_array($this->conf->plugin_id, $userinfo['plugindownloadids'])))) )
    {
      $out .= '
        <p style="padding-top:10px"><label for="post_attachment">'.$this->conf->plugin_phrases_arr['upload_attachments'].'
        <input id="post_attachment" name="attachment" type="file" size="35" /> (' .
        $this->conf->plugin_settings_arr['valid_attachment_types'] . ')</label></p>';
    }

    $out .= '
    </fieldset>';

    if(strlen($out))
    {
      echo '
      <p>'.$out.'</p>';
    }

    if(!($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator) ||
       !empty($userinfo['require_vvc'])) //SD340: require_vvc
    {
      DisplayCaptcha(true,'amihuman');
    }

    echo '<br />
      <input type="submit" name="create_topic" value="'.strip_alltags($this->conf->plugin_phrases_arr['create_topic']).'" />
      </div>
      </form>
<script type="text/javascript">
jQuery(document).ready(function() {
  (function($){ $("textarea#forum_post").focus(); })(jQuery);
});
</script>
';

  } //DisplayTopicForm


  // ##########################################################################
  // DISPLAY POST FORM
  // ##########################################################################

  function DisplayPostForm($errored=false)
  {
    global $DB, $userinfo;

    if(!$this->conf->topic_arr['topic_id'])
    {
      // user is trying to break the script
      $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['err_invalid_topic_id'], true);
      return false;
    }

    if(!$this->conf->IsSiteAdmin && !$this->conf->IsAdmin &&
       !$this->conf->IsModerator && !$this->conf->AllowSubmit)
    {
      return;
    }

    // are we editing a post?
    $post_id = Is_Valid_Number(GetVar('post_id', 0, 'whole_number'),0,1);
    $topic_moderated = false;
    $post_moderated = false;
    $sticky_topic = false;
    $locked_topic = false;

    // Common form header
    echo '<h2><a rel="nofollow" href="'.RewriteLink('index.php?categoryid='.PAGE_ID.'&forum_id='.
          $this->conf->forum_arr['forum_id'].'&topic_id='.
          $this->conf->topic_arr['topic_id']).'">'.$this->conf->topic_arr['title'].'</a></h2>
      <form action="'.$this->conf->current_page.'" enctype="multipart/form-data" method="post" style="margin-top:10px">
      <input type="hidden" name="forum_action" value="'.($post_id?'update_post':'insert_post').'" />
      <input type="hidden" name="forum_id" value="'.$this->conf->forum_arr['forum_id'].'" />
      <input type="hidden" name="topic_id" value="'.$this->conf->topic_arr['topic_id'].'" />'.
      PrintSecureToken(FORUM_TOKEN).
      ($post_id?'<input type="hidden" name="post_id" value="'.$post_id.'" />':'').'
      <div class="form-wrap">';

    if($post_id)
    {
      $submit_text = strip_alltags($this->conf->plugin_phrases_arr['update_post']);
      $DB->result_type = MYSQL_ASSOC;
      if(!$post_arr = $DB->query_first(
         'SELECT fp.*, ft.moderated topic_moderated, ft.open topic_open, ft.sticky, ft.first_post_id'.
         ' FROM {p_forum_posts} fp'.
         ' INNER JOIN {p_forum_topics} ft ON ft.topic_id = fp.topic_id'.
         ' WHERE fp.post_id = %d'.
         ($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ? '' : ' AND (IFNULL(fp.moderated,0) = 0)'),
         $post_id))
      {
        $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['err_message_not_found'], true);
        return false;
      }

      if(!$this->conf->IsSiteAdmin && !$this->conf->IsAdmin && !$this->conf->IsModerator)
      {
        if($post_arr['user_id'] != $userinfo['userid'])
        {
          $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['err_no_update_post_access'], true);
          return false;
        }

        //SD343: allow editing of posts for regular users only within given timeframe (in minutes):
        $edit_timeout = empty($this->conf->plugin_settings_arr['user_edit_timeout']) ? 0 :
                        ((int)$this->conf->plugin_settings_arr['user_edit_timeout'] * 60);
        if(!$user_edit = !empty($post_arr['date']) &&
                         $this->conf->user_post_edit &&
                         ((TIME_NOW <= ($post_arr['date'] + 300)) || // 5 minutes grace period
                          (TIME_NOW <= ($post_arr['date'] + $edit_timeout))) )
        {
          $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['err_no_edit_post_access'], true);
          return false;
        }
      }
      else
      {
        $topic_moderated = !empty($post_arr['topic_moderated']);
        $post_moderated = !empty($post_arr['moderated']);
        $sticky_topic = !empty($post_arr['sticky']);
        $locked_topic = empty($post_arr['topic_open']);
      }
      //SD370: if moderated, apply class for it:
      if($post_moderated)
        echo '<h2 class="topic-moderated">';
      else
        echo '<h2>';
      echo $this->conf->plugin_phrases_arr['edit_post'].'</h2>';

      DisplayBBEditor(SDUserCache::$allow_bbcode, 'forum_post',
                      $post_arr['post'], FORUM_REPLY_CLASS, 80, 10);
    }
    else
    {
      $submit_text = htmlspecialchars($this->conf->plugin_phrases_arr['post_reply']);
      $quote_post = '';
      if($quote_post_id = Is_Valid_Number(GetVar('quote_post_id', 0, 'whole_number'),0,1))
      {
        //SD342: with forum integration fetch user info differently
        if($this->conf->is_sd_users)
        {
          if($quote_post_arr = $DB->query_first('SELECT fp.post, u.username users_username'.
               ' FROM {p_forum_posts} fp'.
               ' INNER JOIN {p_forum_topics} ft ON ft.topic_id = fp.topic_id'.
               ' INNER JOIN {p_forum_forums} ff ON ff.forum_id = ft.forum_id'.
               ' LEFT JOIN {users} u ON u.userid = fp.user_id'.
               ' WHERE fp.topic_id = ' . $this->conf->topic_arr['topic_id'] .
               ' AND fp.post_id = ' . $quote_post_id .
               ($this->conf->IsSiteAdmin ? '' : ' AND ff.online = 1 ').
               $this->conf->GroupCheck.
               ($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ? '' : ' AND (IFNULL(ft.moderated,0) = 0)').
               ($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ? '' : ' AND (IFNULL(fp.moderated,0) = 0)').
               ' LIMIT 1'))
          {
            $quote_post = '[quote=' .
              (isset($quote_post_arr['users_username']) ? $quote_post_arr['users_username'] : $quote_post_arr['username']) .
              ']' . $quote_post_arr['post'] . "[/quote]\n\n";
          }
        }
        else
        {
          if($quote_post_arr = $DB->query_first('SELECT fp.post, fp.user_id, fp.username'.
               ' FROM {p_forum_posts} fp'.
               ' INNER JOIN {p_forum_topics} ft ON ft.topic_id = fp.topic_id'.
               ' INNER JOIN {p_forum_forums} ff ON ff.forum_id = ft.forum_id'.
               ' WHERE fp.topic_id = ' . $this->conf->topic_arr['topic_id'] .
               ' AND fp.post_id = ' . $quote_post_id .
               ($this->conf->IsSiteAdmin ? '' : ' AND ff.online = 1 ').
               $this->conf->GroupCheck.
               ($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ? '' : ' AND (IFNULL(ft.moderated,0) = 0)').
               ($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ? '' : ' AND (IFNULL(fp.moderated,0) = 0)').
               ' LIMIT 1'))
          {
            $quote_post = '[quote='.strip_alltags($quote_post_arr['username']).']'.$quote_post_arr['post']."[/quote]\n\n";
          }
        }
      }

      echo '<h2>'.$this->conf->plugin_phrases_arr['new_post'].'</h2>';

      DisplayBBEditor(SDUserCache::$allow_bbcode, 'forum_post',
                           "\n\n".$quote_post, FORUM_REPLY_CLASS, 80, 10);
    }

    if(!($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator) ||
       !empty($userinfo['require_vvc'])) //SD340: require_vvc
    {
      DisplayCaptcha(true,'amihuman');
    }

    if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ||
       (!empty($this->conf->plugin_settings_arr['attachments_upload_path']) &&
        !empty($this->conf->plugin_settings_arr['valid_attachment_types']) &&
        (!empty($userinfo['plugindownloadids']) &&
         @in_array($this->conf->plugin_id, $userinfo['plugindownloadids']))))
    {
      echo '
      <div>&nbsp;';
      if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator)
      {
        echo '
        <input type="checkbox" name="stick_topic" value="1"'.($sticky_topic?' checked="checked" ':'').'/> '.
        $this->conf->plugin_phrases_arr['topic_options_stick_topic'].'
        &nbsp;<input type="checkbox" name="lock_topic" value="1"'.($locked_topic?' checked="checked" ':'').'/> '.
        $this->conf->plugin_phrases_arr['topic_options_lock_topic'].'
        &nbsp;<input type="checkbox" name="moderate_topic" value="1"'.($topic_moderated?' checked="checked" ':'').'/> '.
        $this->conf->plugin_phrases_arr['topic_options_moderate_topic'];
        //SD370: if editing first post in topic, no need for "reply_moderated"
        // option (user still can use "moderate_topic" option instead),
        // but always display if already moderated
        if( !empty($post_arr['first_post_id']) || $post_moderated ||
            ($post_id != $post_arr['first_post_id']) )
          echo ' &nbsp;<input type="checkbox" name="reply_moderated" value="1" '.($post_moderated?'checked="checked"':'').'/> '.
          $this->conf->plugin_phrases_arr['reply_options_moderate_post'];
        else
          echo ' <input type="hidden" name="reply_moderated" value="0" />';
        echo '<br />';
      }
      if(!empty($this->conf->plugin_settings_arr['attachments_upload_path']) &&
         !empty($this->conf->plugin_settings_arr['valid_attachment_types']))
      {
        echo $this->conf->plugin_phrases_arr['upload_attachments'].'
        <input id="post_attachment" name="attachment" type="file" size="35" /> (' .
        $this->conf->plugin_settings_arr['valid_attachment_types'] . ')';
      }
      echo '
      </div>';
    }
    echo '<br /><input type="submit" name="" value="'.$submit_text.'" />
      </div>
      </form>
<script type="text/javascript">
jQuery(document).ready(function() {
  (function($){ $("textarea#forum_post").focus(); })(jQuery);
});
</script>
';

  } //DisplayPostForm


  // ###########################################################################
  // DISPLAY REPORTING POST FORM
  // ###########################################################################

  function DisplayReportingForm() //SD351
  {
    global $DB, $sdlanguage, $userinfo;

    // Does user have reporting permissions (no guests allowed)?
    if(!$this->conf->user_report_perm)
    {
      $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['err_no_options_access'], true);
      return false;
    }

    // Is a valid topic selected?
    if(empty($this->conf->topic_id))
    {
      $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['err_invalid_topic_id'], true);
      return false;
    }

    // Does post exist and is viewable by reporting user?
    // E.g. moderated posts/topics or offline forums are not valid.
    $post_arr = $this->conf->GetPostByPostedId();
    if(($post_arr === false) || empty($post_arr['post_id']))
    {
      $DB->result_type = MYSQL_BOTH;
      echo $this->plugin_phrases_arr['err_message_not_found'];
      return -4;
    }
    $post_id = (int)$post_arr['post_id'];

    require_once(SD_INCLUDE_PATH.'class_sd_reports.php');

    // Was post already reported?
    $report = SD_Reports::GetReportedItem($this->conf->plugin_id,$this->conf->topic_id,$post_id);
    if(!empty($reported['reportid']))
    {
      $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['post_already_reported'], true);
      return false;
    }

    // Prepare template and data for report form
    $captcha = '';
    if(!($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator) ||
       !empty($userinfo['require_vvc']))
    {
      $captcha = DisplayCaptcha(false,'amihuman');
    }
    $link = $this->conf->RewritePostLink($this->conf->topic_id,$this->conf->topic_arr['title'],$this->conf->post_id);
    $reasons = SD_Reports::GetReasonsForPluginID($this->conf->plugin_id, true);

    $rep_config = array(
      'form_action'     => $this->conf->current_page,
      'item_link'       => $link,
      'item_title'      => $this->conf->topic_arr['title'],
      'form_token_id'   => FORUM_TOKEN,
      'form_token'      => PrintSecureToken(FORUM_TOKEN),
      'objectid1'       => $this->conf->topic_id,
      'objectid2'       => $post_id,
      'reasons'         => $reasons,
      'hidden'          => array(
        0  => array('name' => 'forum_action', 'value' => 'report_post_confirm'),
        1  => array('name' => 'forum_id', 'value' => $this->conf->forum_arr['forum_id']),
        2  => array('name' => 'topic_id', 'value' => $this->conf->topic_id),
        3  => array('name' => 'post_id',  'value' => $this->conf->post_id),

      ),
      'form'            => array(
        'do_captcha'    => (!($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator) ||
                            !empty($userinfo['require_vvc'])),
        'captcha_elem'  => 'amihuman',
        'captcha_html'  => $captcha,
        'confirm'       => $this->conf->plugin_phrases_arr['confirm_report_post'],
        'title'         => $this->conf->plugin_phrases_arr['report_post_title'],
        'subtitle'      => $this->conf->plugin_phrases_arr['report_post_descr'],
        'submit'        => strip_alltags($this->conf->plugin_phrases_arr['report_send']),
        'show_close'    => 0,
        'html_bottom'   => '',
        'html_top'      => '',
      )
    );

    $tmp_smarty = SD_Smarty::getInstance();
    $tmp_smarty->error_reporting = E_ALL & ~E_NOTICE;
    $tmp_smarty->assign('AdminAccess',   ($this->conf->IsAdmin || $this->conf->IsSiteAdmin ||
                                          $this->conf->IsModerator));
    $tmp_smarty->assign('pluginid',      $this->conf->plugin_id);
    $tmp_smarty->assign('page_title',    SD_CATEGORY_TITLE);
    $tmp_smarty->assign('sdurl',         SITE_URL);
    $tmp_smarty->assign('sdlanguage',    $sdlanguage);
    $tmp_smarty->assign('securitytoken', $userinfo['securitytoken']);
    $tmp_smarty->assign('report_user_message', $this->conf->user_report_perm_msg);
    foreach($rep_config as $key => $val)
    {
      $tmp_smarty->assign($key, $val);
    }

    SD_Smarty::display(0, 'user_report_form.tpl');
    unset($tmp_smarty);

  } //DisplayReportingForm


  // ###########################################################################
  // UPDATE POST
  // ###########################################################################

  function UpdatePost()
  {
    global $DB, $sdlanguage, $userinfo;

    // SD313: security check against spam/bot submissions
    if(!CheckFormToken(FORUM_TOKEN, false))
    {
      $this->conf->RedirectPageDefault('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
      return;
    }

    $post_id   = Is_Valid_Number(GetVar('post_id', 0, 'whole_number'),0,1);
    $post_text = GetVar('forum_post', '', 'string', true, false);
    //SD343: apply HTML content filter?
    /*
    if(!$this->conf->IsSiteAdmin && !$this->conf->IsAdmin && !$this->conf->IsModerator )
    {
      $post_text = str_replace(array('&gt;','&lt;'),array('>','<'),htmlspecialchars(sd_htmlawed($post_text)));
    }
    */

    $DB->result_type = MYSQL_ASSOC;
    if(!$post_id ||
       !($post_arr = $DB->query_first('SELECT p.*, t.title topic_title'.
                     ' FROM {p_forum_posts} p'.
                     ' INNER JOIN {p_forum_topics} t ON t.topic_id = p.topic_id'.
                     ' WHERE p.post_id = '.(int)$post_id)))
    {
      $DB->result_type = MYSQL_BOTH;
      DisplayMessage($this->conf->plugin_phrases_arr['err_message_not_found'],true);
      return;
    }

    //SD343: allow editing of posts for regular users only within given timeframe (in minutes):
    $moderated_post = empty($post_arr['moderated'])?0:1;
    if(!$this->conf->IsSiteAdmin && !$this->conf->IsAdmin && !$this->conf->IsModerator)
    {
      $edit_timeout = !$this->conf->plugin_settings_arr['user_edit_timeout'] ? 0 :
                      ($this->conf->plugin_settings_arr['user_edit_timeout'] * 60);
      if(!$user_edit = !empty($post_arr['date']) &&
                       ($post_arr['user_id'] == $userinfo['userid']) &&
                       $this->conf->user_post_edit &&
                       ((TIME_NOW <= ($post_arr['date'] + 300)) || // 5 minutes grace period
                        (TIME_NOW <= ($post_arr['date'] + $edit_timeout))) )
      {
        $this->conf->RedirectPageDefault($this->conf->plugin_phrases_arr['err_no_edit_post_access'], true);
        return;
      }
      if( ($post_arr['user_id'] != $userinfo['userid']) &&
          (empty($userinfo['pluginadminids']) ||
           !@in_array($this->conf->plugin_id, $userinfo['pluginadminids'])) )
      {
        DisplayMessage($this->conf->plugin_phrases_arr['err_no_update_post_access'], true);
        $this->DisplayForumCategories();
        return;
      }
    }
    else
    {
      //SD360: extra admin options
      $sticky = GetVar('stick_topic', false, 'bool', true, false)?1:0;
      $open = GetVar('lock_topic', false, 'bool', true, false)?0:1;
      $mod = GetVar('moderate_topic', false, 'bool', true, false)?1:0;
      $moderated_post = GetVar('reply_moderated', false, 'bool', true, false)?1:0;
      $DB->query('UPDATE {p_forum_topics}'.
                 ' SET moderated = %d, sticky = %d, open = %d'.
                 ' WHERE topic_id = %d',
                 $mod, $sticky, $open, $post_arr['topic_id']);
    }

    if(!($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator) ||
       !empty($userinfo['require_vvc'])) //SD340: require_vvc
    {
      if(!CaptchaIsValid('amihuman'))
      {
        DisplayMessage($sdlanguage['captcha_not_valid'], true);
        $this->conf->EditPost($post_id);
        return false;
      }
    }

    if(!strlen($post_text))
    {
      DisplayMessage($this->conf->plugin_phrases_arr['err_empty_post'], true);
      $this->conf->EditPost($post_id);
      return;
    }

    //SD342: censor non-admin text if enabled
    if($this->conf->censor_posts)
    {
      $post_text = sd_censor($post_text);
    }

    if($this->conf->attach_path_ok && isset($_FILES['attachment']) && !empty($_FILES['attachment']['name']) &&
       ($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ||
        (!empty($userinfo['plugindownloadids']) && @in_array($this->conf->plugin_id, $userinfo['plugindownloadids']) &&
         !empty($this->conf->plugin_settings_arr['valid_attachment_types']))))
    {
      $attachment_arr = $_FILES['attachment'];
      // if an attachment was uploaded, then it will be inserted after the ticket is created
      $attachment_uploaded = false;
      // check if attachment was uploaded
      if($attachment_arr['error'] != 4)
      {
        list($attachment_uploaded, $attachment_stored_filename, $error) = $this->UploadAttachment($attachment_arr);
        if(!$attachment_uploaded)
        {
          $errors_arr[] = $error;
        }
        else
        {
          $DB->query("INSERT INTO {p_forum_attachments}
                      (attachment_id, post_id, attachment_name, filename, filesize, filetype, user_id, username, uploaded_date)
                      VALUES (NULL, $post_id, '%s', '$attachment_stored_filename', $attachment_arr[size], '%s',
                      $userinfo[userid], '%s', " . TIME_NOW . ")",
                      $DB->escape_string($attachment_arr['name']), $DB->escape_string($attachment_arr['type']),
                      $DB->escape_string($userinfo['username']));
          $DB->query('UPDATE {p_forum_posts} SET attachment_count = (IFNULL(attachment_count,0) + 1)
                      WHERE post_id = %d', $post_id);
        }
      }
    }

    // admins can edit every post, so don't add in a userid clause
    $DB->query("UPDATE {p_forum_posts} SET post = '%s', moderated = %d".
               ' WHERE post_id = %d',
               trim($post_text), $moderated_post, $post_id);

    $link = $this->conf->RewritePostLink($post_arr['topic_id'],$post_arr['topic_title'],$post_arr['post_id']);
    RedirectFrontPage($link, $this->conf->plugin_phrases_arr['message_post_updated']);

  } //UpdatePost


  // ###########################################################################
  // SEARCH FORUMS
  // ###########################################################################

  function SearchForums()
  {
    global $DB, $categoryid, $sdlanguage, $userinfo;

    if(!$newposts = isset($_GET['newposts']))
    {
      // If search term submitted, then check token for security
      if(!empty($this->conf->search_text) && !CheckFormToken(FORUM_TOKEN, false))
      {
        $this->conf->RedirectPageDefault('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
        return;
      }
    }

    //SD313: if search bar is not enabled or search text too short, return
    if( !$newposts &&
        (empty($this->conf->plugin_settings_arr['enable_search']) ||
         ((!isset($this->conf->search_tag)  || (strlen($this->conf->search_tag) < 2)) &&
          (!isset($this->conf->search_text) || (strlen($this->conf->search_text) < 3)))) )
    {
      $this->DisplayForumCategories();
      return true;
    }

    //SD343: display optional tag cloud
    $forum_tags = false;
    if(!empty($this->conf->plugin_settings_arr['display_tagcloud']))
    {
      require_once(SD_INCLUDE_PATH.'class_sd_tags.php');
      SD_Tags::$maxentries   = 30;
      SD_Tags::$plugins      = $this->conf->plugin_id;
      SD_Tags::$tags_title   = $sdlanguage['tags_title'];
      SD_Tags::$targetpageid = $categoryid;
      SD_Tags::$tag_as_param = 'tag';
      if($forum_tags = SD_Tags::DisplayCloud(false))
      {
        if(in_array($this->conf->plugin_settings_arr['display_tagcloud'],array(1,3)))
        {
          echo $forum_tags;
        }
      }
    }

    // SD313: use new setting for forum page size
    $items_per_page = Is_Valid_Number($this->conf->plugin_settings_arr['forum_page_size'],15,1,200);

    $colcount = 4;

    $curr_topic = 0;
    $page  = Is_Valid_Number(GetVar('page', 1, 'whole_number'),1,1);
    $items_per_page = Is_Valid_Number(GetVar('pagesize', $items_per_page, 'whole_number'),$items_per_page,1,200);
    $limit = ' LIMIT '.(($page-1)*$items_per_page).','.$items_per_page;

    $sql_fields = '';
    $sql_body = '';
    $sql_order = '';

    if($newposts)
    {
      $sql_fields =
      'SELECT DISTINCT ft.forum_id, ft.topic_id, ft.title, ft.post_count, ft.last_post_id,'.
      ' ft.last_post_username, ft.last_post_date post_date, ft.views, ft.moderated,'.
      ' ft.last_post_id post_id, ft.sticky, ft.open,'.
      ' ff.seo_title forum_seo_title, ff.title forum_title, ff.access_view, ff.access_post, ff.online,'.
      ' fp.user_id poster_id, fp.post last_post_text, fp2.post first_post_text,'.
      ' fp2.post_id first_post_id, fp2.user_id first_poster_id, fp2.username first_post_username';
      $sql_body =
      ' FROM '.PRGM_TABLE_PREFIX.'p_forum_topics ft'.
      ' INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_forums ff ON ff.forum_id = ft.forum_id'.
      ' INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_posts fp  ON (fp.post_id = ft.last_post_id AND fp.moderated = 0)'.
      ' INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_posts fp2 ON (fp2.post_id = ft.first_post_id AND fp2.moderated = 0)'.
      ' WHERE ft.last_post_date >= '.(TIME_NOW - 7776000 /* last 90 days */).
       ($this->conf->IsSiteAdmin ? '' : ' AND ff.online = 1 AND ff.is_category = 0').
       ($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ? '' : ' AND IFNULL(ft.moderated,0) = 0').
       $this->conf->GroupCheck;
       $sql_order = ' ORDER BY ft.last_post_date DESC, ft.title ASC';
    }
    else
    if(!empty($this->conf->search_tag)) //SD343
    {
      $sql_fields =
      'SELECT DISTINCT ft.forum_id, ft.topic_id, ft.title, ft.post_count, ft.last_post_id,'.
      ' ft.last_post_username, ft.last_post_date post_date, ft.views, ft.moderated,'.
      ' ft.last_post_id post_id, ft.sticky, ft.open,'.
      ' ff.seo_title forum_seo_title, ff.title forum_title, ff.access_view, ff.access_post, ff.online,'.
      ' fp.user_id poster_id, fp.post last_post_text, fp2.post first_post_text,'.
      ' fp2.post_id first_post_id, fp2.user_id first_poster_id, fp2.username first_post_username';
      $sql_body =
      ' FROM '.PRGM_TABLE_PREFIX.'p_forum_topics ft'.
      ' INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_forums ff ON ff.forum_id = ft.forum_id'.
      ' INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_posts fp  ON (fp.post_id = ft.last_post_id AND fp.moderated = 0)'.
      ' INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_posts fp2 ON (fp2.post_id = ft.first_post_id AND fp2.moderated = 0)'.
      ' WHERE ft.topic_id > 0'.
       ($this->conf->IsSiteAdmin ? '' : ' AND ff.online = 1 AND ff.is_category = 0').
       ($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ? '' : ' AND IFNULL(ft.moderated,0) = 0').
       $this->conf->GroupCheck.
       // important part: check tags table for existing tag
       ' AND EXISTS(SELECT 1 FROM '.PRGM_TABLE_PREFIX.'tags tt WHERE tt.pluginid = '.$this->conf->plugin_id.
                  ' AND tt.objectid = ft.topic_id AND tt.censored = 0 AND tagtype = 0'.
                  " AND tt.tag = '".$DB->escape_string($this->conf->search_tag)."')";
       $sql_order = ' ORDER BY ft.last_post_date DESC, ft.title ASC';
    }
    else
    {
      //NOTE: for the MATCH function any "?" or "_" in the search term
      // should NOT be escaped; remove duplicate blanks and prefix all
      // separate words with "+" (=AND condition):
      $this->conf->search_text = str_replace('\_', '_', $this->conf->search_text);
      $this->conf->search_text = str_replace('\?', '?', $this->conf->search_text);
      $this->conf->search_text = str_replace(',', ' ', $this->conf->search_text);
      $this->conf->search_text = trim(preg_replace('#\s\s+#', ' ', $this->conf->search_text));
      $this->conf->search_text = '+'.str_replace(' ', '+', $this->conf->search_text);
      $this->conf->search_text = str_replace('++', '+', $this->conf->search_text);

      // Probably apply limit to search only within the last 365 days?
      // AND ft.last_post_date > UNIX_TIMESTAMP()-31536000

      $searchtopics = !empty($_POST['searchtopics']);
      $searchposts  = !empty($_POST['searchposts']);
      $searchusers  = GetVar('searchusers','','string');
      $searchtags   = !empty($_POST['searchtags']); //SD343

      $searchfields = '';
      $searchfields .= $searchtopics ? 'ft.title,' : '';
      $searchfields .= $searchposts  ? 'fp.post,' : '';
      $searchfields .= $searchusers  ? 'fp.username, ft.post_username,' : '';

      $extra = '';
      if(strlen($searchfields))
      {
        $searchfields = substr($searchfields, 0, -1);
      }
      else
      {
        $searchfields = 'ft.title, fp.post';
      }
      if(!empty($searchtags))
      {
       // important part: check tags table for existing tag
       $extra .=
       ' OR EXISTS(SELECT 1 FROM '.PRGM_TABLE_PREFIX.'tags tt WHERE tt.pluginid = '.$this->conf->plugin_id.
                 ' AND tt.objectid = ft.topic_id AND tt.censored = 0 AND tagtype = 0'.
                 " AND tt.tag = '".str_replace('+','',$this->conf->search_text)."')";
      }

      $sql_fields =
      "SELECT DISTINCT ft.forum_id, ft.topic_id, ft.title, ft.post_count,
      IF(IsNull(fp.date), ft.last_post_date, fp.date) post_date,
      fp.post_id last_post_id, fp.user_id poster_id, fp.post last_post_text,
      ft.last_post_username, ft.views, ft.sticky, ft.open, ft.moderated,
      ff.seo_title forum_seo_title, ff.title forum_title, ff.access_view, ff.access_post, ff.online,
      fp2.post first_post_text, fp2.post_id first_post_id, fp2.user_id first_poster_id, fp2.username first_post_username,
      MATCH($searchfields) AGAINST('".($this->conf->search_text)."' IN BOOLEAN MODE) AS relevance";
      $sql_body =
      ' FROM '.PRGM_TABLE_PREFIX."p_forum_forums ff
      INNER JOIN ".PRGM_TABLE_PREFIX."p_forum_topics ft ON (ft.forum_id = ff.forum_id AND IFNULL(ft.moderated,0) = 0)
      INNER JOIN ".PRGM_TABLE_PREFIX."p_forum_posts  fp ON (fp.topic_id = ft.topic_id AND IFNULL(fp.moderated,0) = 0)
      INNER JOIN ".PRGM_TABLE_PREFIX."p_forum_posts fp2 ON (fp2.post_id = ft.first_post_id AND IFNULL(fp2.moderated,0) = 0)
      WHERE ff.forum_id > 0 ".
      ($this->conf->IsSiteAdmin ? '' : ' AND ff.online = 1 ').
      $this->conf->GroupCheck.
      ($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ? '' : ' AND IFNULL(ft.moderated,0)=0 ').
      ' AND ((MATCH('.$searchfields.") AGAINST('".($this->conf->search_text)."' IN BOOLEAN MODE) > 0)".
      $extra . ')';
      $sql_order = ' ORDER BY relevance DESC, ft.topic_id DESC, last_post_id DESC';
    }

    // Determine total amount of rows first
    $DB->ignore_error = true;
    //$get_results_arr = $DB->query('SELECT COUNT(*)' . $sql_body);
    //$total_rows = $DB->get_num_rows($get_results_arr);
    if($total_rows = $DB->query_first('SELECT COUNT(*) fcount ' . $sql_body))
    {
      $total_rows = $total_rows['fcount'];
    }
    $DB->ignore_error = false;

    // Fetch the actual data now
    if($newposts && !empty($total_rows) && ($total_rows > 999)) //SD342 limit "most recent" to 1000
    {
      $total_rows = 1000;
      if($page*$items_per_page > $total_rows)
      {
        $page = ceil($total_rows / $items_per_page);
        $limit = ' LIMIT '.(($page-1)*$items_per_page).','.$items_per_page;
      }
    }

    $DB->ignore_error = true;
    $get_results_arr = $DB->query($sql_fields . $sql_body . $sql_order . $limit);
    $DB->ignore_error = false;

    echo '
    <table border="0" class="forum-category" cellpadding="0" cellspacing="0" summary="layout" width="100%">
      <thead>
      <tr>
        <th class="col-forum-title">'.$this->conf->plugin_phrases_arr['column_forum'].'</th>
        <th class="col-search-topic">'.$this->conf->plugin_phrases_arr['column_topic'].'</th>
        <th class="col-post-count">'.$this->conf->plugin_phrases_arr['column_posts'].'</th>';
    // SD313: diplay view count
    if(!empty($this->conf->plugin_settings_arr['display_view_count']))
    {
      $colcount++;
      echo '<th class="col-post-count">'.$this->conf->plugin_phrases_arr['column_view_count'].'</th>
            ';
    }
    echo '
        <th class="col-last-updated">'.$this->conf->plugin_phrases_arr['column_last_updated'].'</th>
      </tr>
      </thead>
      <tbody>';

    if(!empty($this->conf->search_tag)) //SD343
    {
      echo '<h2>'.$this->conf->plugin_phrases_arr['message_tagged_topics'].' <strong>'.
           htmlspecialchars($this->conf->search_tag).'</strong></h3>';
    }

    if(!empty($get_results_arr))
    while($search_arr = $DB->fetch_array($get_results_arr,null,MYSQL_ASSOC))
    {
      // If topics search mode is checked, then skip row to avoid duplicate
      // topic rows even if multiple posts matched:
      if(!$newposts && isset($searchtopics) && ($curr_topic == $search_arr['topic_id']))
      {
        $total_rows--;
        continue;
      }

      //SD342: add first 100 chars as link title for preview
      $first_post_hint = $last_post_hint = '';
      if(isset($search_arr['first_post_text'])) $first_post_hint = SDForumConfig::GetBBCodeExtract($search_arr['first_post_text']);
      if(isset($search_arr['last_post_text']))  $last_post_hint  = SDForumConfig::GetBBCodeExtract($search_arr['last_post_text']);
      //SD342 SEO title
      $link = $this->conf->RewriteTopicLink($search_arr['topic_id'],$search_arr['title']);

      $post_link = '<a class="jump-post-link" href="'.$link.
                   ($this->conf->seo_enabled?'?':'&amp;').'post_id=';
      if(!empty($search_arr['post_id']))
      {
        $post_link .= $search_arr['post_id'].'#p'.$search_arr['post_id'].'">';
      }
      else
      {
        $post_link .= $search_arr['last_post_id'].'#p'.$search_arr['last_post_id'].'">';
      }
      $curr_topic = $search_arr['topic_id'];

      //SD342 SEO title
      $link = $this->conf->RewriteForumLink($search_arr['forum_id']);
      echo '
      <tr>
        <td class="col-forum-title">
          <a href="' . $link . '">' .
          (empty($search_arr['online'])?'<img alt="" src="'.SDUserCache::$img_path.'attention.png" width="16" height="16" /> ':'').
          $search_arr['forum_title'] . '</a>
        </td>
        <td class="col-search-topic"'.$first_post_hint.'>';

      //SD342: user caching of first poster, incl. avatar
      $first_poster = SDUserCache::CacheUser($search_arr['first_poster_id'], $search_arr['first_post_username'], false, true);
      if($first_poster['avatar'])
        echo $first_poster['topic_avatar'];
      else
        echo '<div class="topic-avatar">'.GetDefaultAvatarImage(SDUserCache::$avatar_width,SDUserCache::$avatar_height).' </div>';

      //SD342 SEO title
      $link = $this->conf->RewriteTopicLink($search_arr['topic_id'],$search_arr['title']);
      echo '
      <div class="topic-cell">
        ';

      echo (empty($search_arr['open'])? '<img alt="locked" src="'.SDUserCache::$img_path.'lock.png" width="16" height="16" title="'.htmlspecialchars($this->conf->plugin_phrases_arr['topic_locked']).'" /> ' : '');
      echo (!empty($search_arr['sticky'])? '<img alt="sticky" src="'.SDUserCache::$img_path.'sticky.png" width="16" height="16" title="'.htmlspecialchars($this->conf->plugin_phrases_arr['sticky']).'" /> ' : '');
      echo (!empty($search_arr['moderated'])? '<img alt="!" src="'.SDUserCache::$img_path.'attention.png" width="16" height="16" /> ' : '');

      //SD343: fetch prefix (if present and uncensored) and display it
      if($prefix = $this->GetTopicPrefix($search_arr['topic_id']))
      {
        echo str_replace('[tagname]', $prefix['tag'], $prefix['html_prefix']);
      }

      echo '<a class="topic-link" href="'.$link.'" rel="'.$search_arr['topic_id'].'">'.
           trim($search_arr['title']) . '</a><br />';

      if(!empty($search_arr['first_poster_id']))
        #echo $first_poster['topic'];
        echo $first_poster['forum'];
      else
        echo $search_arr['first_post_username'];

      echo '</div>
        </td>
        <td class="col-post-count">' . $search_arr['post_count'] . '</td>';
      // SD313: diplay view count
      if(!empty($this->conf->plugin_settings_arr['display_view_count']))
      {
        echo '
        <td class="col-post-count">' . $search_arr['views'] . '</td>';
      }
      echo '
        <td class="col-last-updated"'.$last_post_hint.'>';

      if($search_arr['post_date'])
      {
        echo $this->conf->plugin_phrases_arr['posted_by'].' ';
        if(!empty($search_arr['poster_id']))
        {
          $tmp = SDUserCache::CacheUser($search_arr['poster_id'], $search_arr['last_post_username'], false);
          echo $tmp['forum'];
        }
        else
        {
          echo '<strong>'.$search_arr['last_post_username'].'</strong>';
        }

        echo '<br />
          ' . $post_link . $this->conf->GetForumDate($search_arr['post_date']).'</a> ';
      }

      echo '
        </td>
      </tr>';
    }
    if($total_rows<1)
    {
      echo '
      <tr><td colspan="'.$colcount.'">'.$this->conf->plugin_phrases_arr['search_no_results'].'</td></tr>
      ';
    }
    echo '
    </tbody>
    </table>
    ';

    if($total_rows > $items_per_page)
    {
      echo '
      <div id="topic-footer">';
      // pagination
      $p = new pagination;
      $p->enablePrevNext = true; //SD343
      $p->className = 'forum-pagination';
      $p->items($total_rows);
      $p->limit($items_per_page);
      $p->currentPage($page);
      $p->adjacents($this->conf->paging_adjacents);
      if($newposts)
      {
        $p->target(RewriteLink('index.php?categoryid='.PAGE_ID.'&newposts=1'));
      }
      else
      {
        $p->target(RewriteLink('index.php?categoryid='.PAGE_ID.'&forum_search='.urlencode($this->conf->search_text).FORUM_URL_TOKEN));
      }
      $pagination_html = $p->getOutput();
      if(trim($pagination_html)=='<div class="forum-pagination"></div>') $pagination_html='';
      echo $pagination_html.'
      </div>';
    }
    // end topic-footer

    //SD343: display optional tag cloud
    if(!empty($forum_tags) && !empty($this->conf->plugin_settings_arr['display_tagcloud']) &&
       in_array($this->conf->plugin_settings_arr['display_tagcloud'],array(2,3)))
    {
      echo $forum_tags;
    }

    echo '
    <div class="clear"></div>
    ';

  } //SearchForums


  // ###########################################################################
  // DISPLAY TOPIC
  // ###########################################################################

  function DisplayTopic($errored=false, $error_msg='')
  {
    global $DB, $bbcode, $categoryid, $mainsettings, $sdlanguage, $userinfo,
           $UserProfile, $usersystem;

    // Prepare BBCode editor - if enabled - (incl. [code] and [embed] handlers)
    if(SDUserCache::$allow_bbcode)
    {
      // SD313: Replace rule for "[code]" tag with a better = working callback
      // by using new function "sd_BBCode_DoCode" (functions_global.php)
      $bbcode->RemoveRule('code');
      $bbcode->AddRule('code',
          array('mode' => BBCODE_MODE_CALLBACK,
            'class' => 'code',
            'method' => 'sd_BBCode_DoCode',
            'allow_in' => array('listitem', 'block', 'columns'),
            'content' => BBCODE_VERBATIM
          )
      );

      // SD313: enable embedding of video links?
      if(!empty($this->conf->plugin_settings_arr['enable_embedding']))
      {
        $bbcode->AddRule('embed',
            array('mode' => BBCODE_MODE_CALLBACK,
                  'method' => 'sd_BBCode_DoEmbed',
                  'class' => 'link',
                  'allow_in' => array('listitem', 'block', 'columns', 'inline'),
                  'content' => BBCODE_REQUIRED,
                  'before_tag' => '',
                  'after_tag' => ''
                  ));
      }
    }

    // Output required JS code if needed (depending on 2 conditions)
    if(SDUserCache::$allow_bbcode && empty($mainsettings['allow_bbcode_embed']))
    {
      // SD341: embedding JS moved to header.php
      echo '
<script type="text/javascript">//<![CDATA[
jQuery(document).ready(function() {
  jQuery("img.button[name=btnEmbed]").remove(); });
//]]>
</script>
';
    }

    // Prepare variables
    $topic_arr = $this->conf->topic_arr;
    $topic_id  = $topic_arr['topic_id'];

    $forum_arr = $this->conf->forum_arr;
    $forum_id  = $forum_arr['forum_id'];

    $post_id   = Is_Valid_Number(GetVar('post_id', 0, 'whole_number'),0,1);
    $page      = Is_Valid_Number(GetVar('page',    1, 'whole_number'),1,1);

    // Update topic view count
    if(!defined('SD_IS_BOT') || !SD_IS_BOT)
    {
      $DB->query('UPDATE {p_forum_topics} SET views = (IFNULL(views,0)+1) WHERE topic_id = %d AND IFNULL(moderated,0)=0',$topic_id);
    }

    // SD313: display how many posts per page?
    $items_per_page = Is_Valid_Number($this->conf->plugin_settings_arr['topic_page_size'],15,1,100);

    if($post_id)
    {
      $DB->result_type = MYSQL_ASSOC;
      $previous_post_count_arr = $DB->query_first(
        'SELECT COUNT(*) value FROM '.PRGM_TABLE_PREFIX.'p_forum_posts'.
        ' WHERE topic_id = %d AND post_id <= %d'.
        ($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ? '' : ' AND IFNULL(moderated,0) = 0'),
        $topic_id, $post_id);
      $previous_post_count = $previous_post_count_arr['value'];
      $page = ($previous_post_count > 0) ? ceil($previous_post_count / $items_per_page) : 1;
    }

    $first_post_idx = ($page-1)*$items_per_page;
    $limit = ' LIMIT '.$first_post_idx.','.$items_per_page;

    // SD313: display moderated topic's title in red (for now)
    $moderated_class = empty($topic_arr['moderated']) ? '' : ' topic-moderated';

    echo (empty($topic_arr['open'])? '<img alt="locked" src="'.SDUserCache::$img_path.'lock.png" width="16" height="16" title="'.htmlspecialchars($this->conf->plugin_phrases_arr['topic_locked']).'" /> ' : '');
    echo (!empty($topic_arr['sticky'])? '<img alt="sticky" src="'.SDUserCache::$img_path.'sticky.png" width="16" height="16" title="'.htmlspecialchars($this->conf->plugin_phrases_arr['sticky']).'" /> ' : '');
    echo (!empty($topic_arr['moderated'])? '<img alt="!" src="'.SDUserCache::$img_path.'attention.png" width="16" height="16" /> ' : '');

    //SD343: fetch prefix (if present and uncensored) and display it
    //SD370: it's now a function
    if($prefix = $this->GetTopicPrefix($this->conf->topic_id))
    {
      echo str_replace('[tagname]', $prefix['tag'], $prefix['html_prefix']).' ';
    }
    unset($prefix);

    echo '<h2 class="forum-topic-title'.$moderated_class.'" editable>' .
         trim($topic_arr['title']) . '</h2>';

    if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ||
       !empty($this->conf->topic_arr['open']))
    {
      /*
      if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->AllowSubmit) //SD313: only display "Reply" link if allowed
      {
        echo '<a class="reply-link button-link" href="' . RewriteLink('index.php?categoryid='.PAGE_ID.'&topic_id=' .
             $topic_arr['topic_id'] . '&forum_action=submit_post'.FORUM_URL_TOKEN) . '"><span>'.
             $this->conf->plugin_phrases_arr['reply'].'</span></a>';
      }
      */
    }
    else
    {
      DisplayMessage($this->conf->plugin_phrases_arr['topic_locked'], true);
    }

    // For Admin start a form to submit topic options
    if($this->conf->IsSiteAdmin || $this->conf->IsAdmin)
    {
      echo '
      <form id="topic-options" action="' . $this->conf->current_page . '" method="post">
      <input type="hidden" name="forum_action" value="topic_options" />
      <input type="hidden" name="topic_id" value="' . $topic_arr['topic_id'].'" />
      '.PrintSecureToken(FORUM_TOKEN).'
      ';
    }
    echo '
      <table border="0" cellpadding="5" cellspacing="0" summary="layout" width="100%">
      <tbody id="posts">';

    // Initialize some variables
    $pagination_html  = '';
    $postcount        = 0;
    $total_rows       = 0;
    $paginated        = false;
    $sticky_topic     = false;
    $topic_moderated  = false;
    $locked_topic     = false; //SD351
    $post_idx = $first_post_idx + 1;

    require_once(SD_INCLUDE_PATH.'class_sd_likes.php');

    $real_post_count = 0;
    $DB->result_type = MYSQL_ASSOC;
    if($real_post_count = $DB->query_first('SELECT COUNT(*) realcount FROM '.PRGM_TABLE_PREFIX.'p_forum_posts'.
                                           ' WHERE topic_id = %d', $topic_id))
    {
      $real_post_count = $real_post_count['realcount'];
    }
    if($real_post_count < $first_post_idx) //SD343: fix invalid page
    {
      $page = 1;
      $limit = ' LIMIT 0,'.$items_per_page;
    }

    if($usersystem['name'] == 'Subdreamer')
    {
      $posts_sql =
      'SELECT fp.*, fp.`date` post_date, IFNULL(ft.post_count,0) topic_post_count,
       users.username users_username, users.email user_email,
       users.joindate, IFNULL(users.user_post_count,0) user_post_count,
       IFNULL(users.user_thread_count,0) user_thread_count,
       ud.user_screen_name, ft.moderated topic_moderated, ft.sticky, ft.open
       FROM '.PRGM_TABLE_PREFIX.'p_forum_posts fp
       INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_topics ft ON ft.topic_id = fp.topic_id
       INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_forums ff ON ff.forum_id = ft.forum_id
       LEFT JOIN '.PRGM_TABLE_PREFIX.'users users ON users.userid = fp.user_id
       LEFT JOIN '.PRGM_TABLE_PREFIX.'users_data ud ON (ud.usersystemid = '.(int)$usersystem['usersystemid'].' AND ud.userid = fp.user_id)
       WHERE fp.topic_id = %d '.
       ($this->conf->IsSiteAdmin ? '' : ' AND ff.online = 1 ').
       $this->conf->GroupCheck.
       ($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ? '' : ' AND (IFNULL(fp.moderated,0)=0)').
       ' ORDER BY fp.post_id ASC'. $limit;
    }
    else
    {
      $posts_sql =
      'SELECT fp.*, fp.`date` post_date, IFNULL(ft.post_count,0) topic_post_count,
       ud.user_screen_name, ft.moderated topic_moderated, ft.sticky, ft.open
       FROM '.PRGM_TABLE_PREFIX.'p_forum_posts fp
       INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_topics ft ON ft.topic_id = fp.topic_id
       INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_forums ff ON ft.forum_id = ff.forum_id
       LEFT JOIN {users_data} ud ON (ud.usersystemid = '.(int)$usersystem['usersystemid'].' AND ud.userid = fp.user_id)
       WHERE fp.topic_id = %d '.
       ($this->conf->IsSiteAdmin ? '' : ' AND ff.online = 1 ').
       $this->conf->GroupCheck.
       ($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ? '' : ' AND (IFNULL(fp.moderated,0)=0)').
       ' ORDER BY fp.post_id ASC'. $limit;
    }
    $get_posts = $DB->query($posts_sql, $topic_id);

    while($post_arr = $DB->fetch_array($get_posts,null,MYSQL_ASSOC))
    {
      // Topic header (with pagination output)
      if(!$paginated) // only process this once per loop
      {
        $topic_moderated = !empty($post_arr['topic_moderated']);
        $sticky_topic = !empty($post_arr['sticky']);
        $locked_topic = empty($post_arr['open']);
        $paginated = true;
        if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator)
        {
          $total_rows = $real_post_count;
        }
        else
        {
          $total_rows = $post_arr['topic_post_count'];
        }
        // Cache Pagination:
        $p = new pagination;
        $p->enablePrevNext = true; //SD343
        $p->className = 'forum-pagination';
        $p->showCounter = true;
        $p->pagesT = $this->conf->plugin_phrases_arr['pagination_posts'];
        $p->items($total_rows);
        $p->limit($items_per_page);
        $p->currentPage($page);
        $p->adjacents($this->conf->paging_adjacents);

        //SD343 SEO Forum Title linking
        $link = $this->conf->RewriteTopicLink($topic_id, $topic_arr['title']);
        $p->target($link);
        $pagination_html = $p->getOutput();
        if(trim($pagination_html)=='<div class="forum-pagination"></div>') $pagination_html='';
        if(($total_rows > $items_per_page) && $pagination_html)
        {
          $pagination_html = preg_replace('#\?page=1"#im','"',$pagination_html);
          echo '<tr><td colspan="2" class="pagination-top">'.$pagination_html.'</td></tr>';
        }
      }
      else
      {
        echo '<tr><td colspan="2" class="post-separator"> </td></tr>';
      }
      $postcount++;
      // display moderated posts with different class
      $moderated_class = !empty($post_arr['moderated']) ? ' post-moderated' : '';

      // ############################################################
      // Fetch user details and cache html and data for each
      // ############################################################

      //SD342: user caching
      $user = SDUserCache::CacheUser($post_arr['user_id'], $post_arr['username'], true, true);

      $html = '<tr>
                <td class="col-user-details%%moderated%%" valign="top">';
      if(!empty($user))
      {
        $html .= '<div class="author-name">'.$user['forum'].'</div>';

        if(!empty($user['user_title'])) //SD351
        {
          $html .= '<div class="author-title">'.$user['user_title'].'</div>';
        }

        //SD342: unless explicitely disabled in profile, assume to show all avatars
        $html .= (!empty($user['author_avatar']) ? $user['author_avatar'] : '');

        if($user['joindate'])
        {
          $html .= '<div class="author-joined">'.$this->conf->plugin_phrases_arr['joined'] . ' ' .
                   DisplayDate($user['joindate'],'Y-m-d').'</div>';
        }
        //SD343: only show online status if allowed by user
        if(!isset($user['user_allow_viewonline']) || !empty($user['user_allow_viewonline']))
        {
          $html .= $user['online_img'];
        }
        if(!empty($user['post_count']))
        {
          $html .= '<div class="author-stats">'.$this->conf->plugin_phrases_arr['posts'] . ': ' .
                   number_format($user['post_count']).'</div>';
        }

        //SD342: output messaging icons - if marked public and enabled
        if(!empty($user['public_fields']))
        {
          $messenging = '';
          foreach(SDUserCache::$messaging_fields as $field)
          {
            $name = str_replace('user_','',$field);
            if(!empty($this->conf->plugin_settings_arr['display_user_'.$name.'_icon_in_topic']))
            if(!empty($user[$field]) && in_array($field, $user['public_fields']))
            {
              $messenging .= SDUserProfile::GetMessengingLink('ext_'.$name, $user[$field],false).' ';
            }
          }
          if($messenging!==false)
          {
            $html .= '<div class="author-msg">'.$messenging.'</div>';
          }
        }

        if(!empty($this->conf->plugin_settings_arr['display_user_ip']) && //SD342
           $this->conf->IsSiteAdmin && !empty($post_arr['ip_address']))
        {
          $html .= '<div class="author-ip">IP: '.$post_arr['ip_address'].'</div>';
        }
        $html .= '</td>';

        // Store html and data in usercache
        //$this->usercache[$post_arr['user_id']]['html'] = $html;
        $user['html'] = $html;
        SDUserCache::AddCachedUserProperty($post_arr['user_id'],'html', $html);
        unset($messenging, $field);

      } // user html caching
      else
      {
        $user['html'] = $html .= '<div class="author-name">'.$post_arr['username'].'</div></td>';
        SDUserCache::AddCachedUserProperty($user['user_id'],'html', $html);
      }

      echo str_replace('%%moderated%%', $moderated_class, $user['html']);

      //SD343 extra HTML check
      $post_arr['post'] = str_replace(array('[php]','[/php]','[PHP]','[/PHP]','[HTML]','[/HTML]','"',"'",'<','>'),
                                      array('[code]','[/code]','[code]','[/code]','[code]','[/code]','&quot;','&#039;','&lt;','&gt;'),
                                      $post_arr['post']);

      // Apply BBCode parsing
      if(SDUserCache::$allow_bbcode)
      {
        $olddetect = $bbcode->GetDetectURLs();
        $oldpattern = $bbcode->GetURLPattern();
        $bbcode->SetDetectURLs(true);
        $bbcode->url_targetable = true;
        $bbcode->SetURLTarget('_blank');
        if(!SDUserCache::$bbcode_detect_links) //SD341
        {
          $bbcode->SetURLPattern('{$text/h}'); //SD342
        }
        else
        {
          $bbcode->SetURLPattern('<a rel="nofollow" href="{$url/h}">{$text/h}</a>'); //SD341
        }
        $post_arr['post'] = str_replace(array('[CODE]','[/CODE]'),
                                        array('[code]','[/code]'),$post_arr['post']); //SD370
        $post_arr['post'] = $bbcode->Parse($post_arr['post']);
        $bbcode->SetURLPattern($oldpattern);
        $bbcode->SetDetectURLs($olddetect);
      }

      echo '
            <td class="col-post" valign="top">
            <div class="post-header">
              <a name="p'.$post_arr['post_id'].'"></a>';
      echo $this->conf->GetForumDate($post_arr['post_date']);

      if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator)
      {
        echo ' <input type="checkbox" name="post_id_arr[]" value="' . $post_arr['post_id'] . '" />';
      }

      //SD342 SEO title
      $link = $this->conf->RewritePostLink($topic_arr['topic_id'],$topic_arr['title'],$post_arr['post_id']);
      echo '
              <a class="forum-post-number" href="' . $link . '">#' . $post_idx . '</a>
            </div>
            <div class="post-content">
            <div class="post" id="post_id_' . $post_arr['post_id'] . '">';

      echo $post_arr['post'];

      echo '</div>';
      //SD342: user signature: only display if enabled to be viewed - OR for guests
      if((empty($userinfo['loggedin']) || !empty($userinfo['profile']['user_view_sigs'])) &&
         isset($user['signature']) && $user['signature'])
      {
        echo '<div class="signature">'.$user['signature'].'</div>';
      }
      echo '
            </div>';

      // ##################################################
      // Display post attachments
      // ##################################################
      if(!empty($post_arr['attachment_count']))
      {
        $this->DisplayAttachments($topic_arr['topic_id'], $post_arr['post_id']);
      }

      echo '
            <div class="post-footer">';

      // ##################################################
      // Display edit / administration links
      // ##################################################
      //SD343: allow editing of posts for regular users only within given timeframe (in minutes):
      $edit_timeout = !$this->conf->plugin_settings_arr['user_edit_timeout'] ? 0 :
                      (int)$this->conf->plugin_settings_arr['user_edit_timeout'] * 60;
      $user_edit = !empty($post_arr['date']) &&
                   ($post_arr['user_id'] == $userinfo['userid']) &&
                   $this->conf->user_post_edit &&
                   ((TIME_NOW <= ($post_arr['date'] + 300)) || // 5 minutes grace period
                    (TIME_NOW <= ($post_arr['date'] + $edit_timeout)));
      //SD351: allow user to report this post?
      $user_report = $this->conf->user_report_perm &&
                     empty($post_arr['moderated']) &&
                     !empty($userinfo['userid']);
                     #($post_arr['user_id'] != $userinfo['userid']);
      if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ||
         $user_edit || $user_report)
      {
        echo '<div class="private-links">';

        if($this->conf->IsSiteAdmin || $this->conf->IsAdmin ||
           $this->conf->IsModerator || $user_edit)
        {
          echo '<a class="edit-post-link" href="' .
                RewriteLink('index.php?categoryid='.PAGE_ID.
                  '&topic_id=' . $this->conf->topic_arr['topic_id'] .
                  '&post_id='.$post_arr['post_id'].
                  '&forum_action=edit_post'.FORUM_URL_TOKEN) . '">'.
                  $this->conf->plugin_phrases_arr['edit'].'</a> ';
        }
        //SD351
        if($user_report)
        {
          echo '<a class="report-post-link" href="' .
                RewriteLink('index.php?categoryid='.PAGE_ID.
                  '&topic_id=' . $this->conf->topic_arr['topic_id'] .
                  '&post_id='.$post_arr['post_id'].
                  '&forum_action=report_post'.FORUM_URL_TOKEN) . '">'.
                  $this->conf->plugin_phrases_arr['report_post'].'</a> ';
        }
        //SD360: user un-/moderation only for admins, not mods
        if($this->conf->IsSiteAdmin || $this->conf->IsAdmin)
        {
          // SD313: links for user moderation
          $action = '&forum_action=topic_options&topic_action=';
          echo '<a class="admin-link" href="' .
                RewriteLink('index.php?categoryid='.PAGE_ID.'&topic_id=' . $this->conf->topic_arr['topic_id'] .
                  $action . 'moderation_user_confirm&moderation=1&user_id='.$post_arr['user_id'].FORUM_URL_TOKEN).'">'.
                  $this->conf->plugin_phrases_arr['topic_options_moderate_user'].'</a> ';
          echo '<a class="admin-link" href="' .
                RewriteLink('index.php?categoryid='.PAGE_ID.'&topic_id=' . $this->conf->topic_arr['topic_id'] .
                  $action . 'unmoderation_user_confirm&moderation=0&user_id='.$post_arr['user_id'].FORUM_URL_TOKEN).'">'.
                  $this->conf->plugin_phrases_arr['topic_options_unmoderate_user'].'</a>';
        }

        echo '</div>';
      }

      echo '<div class="public-links">&nbsp;';
      // SD313: only display "Quote" form if allowed
      if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ||
         ($this->conf->topic_arr['open'] && $this->conf->AllowSubmit))
      {
        // Display publicly available post links, e.g. "Quote"
        echo '<a class="quote-link" rel="nofollow" href="' .
          RewriteLink('index.php?categoryid='.PAGE_ID.'&topic_id=' . $topic_arr['topic_id'] .
          '&forum_action=submit_post&quote_post_id=' . $post_arr['post_id'].FORUM_URL_TOKEN).'">'.
          $this->conf->plugin_phrases_arr['quote'].'</a>';
      }

      echo '<a class="goto-top-link" href="#" rel="nofollow" onclick="return false;">'.$this->conf->plugin_phrases_arr['topic_goto_top'].
           '</a>&nbsp;</div>&nbsp;';

      //SD343: display "like this post" results
      #$this->conf->GetPostLikesLinks($post_arr); //???
      $likes_txt = '';
      if(!empty($this->conf->plugin_settings_arr['display_likes_results']) &&
         !empty($post_arr['user_likes']) && ($likes = @unserialize($post_arr['user_likes'])))
      {
        $likes_txt  = SD_Likes::GetLikesList($likes,true);
        $likes_txt .= ' '.SD_Likes::GetLikesList($likes,false);
        $likes_txt = trim($likes_txt);
      }

      //SD343: "like" post link
      if($this->conf->user_likes_perm)
      {
        $out = ''; $out1 = ''; $out2 = '';
        $showRemove = false;
        $liked = $DB->query_first('SELECT 1 postliked FROM {users_likes}'.
                                  " WHERE pluginid = %d AND objectid = %d ".
                                  " AND userid = %d AND liked_type = '%s' LIMIT 1",
                                 $this->conf->plugin_id, $post_arr['post_id'],
                                 $userinfo['userid'], SD_LIKED_TYPE_POST);
        if(empty($liked['postliked']))
        {
          $out1 = ' <a class="like-link" rel="nofollow" href="' .
          RewriteLink('index.php?categoryid='.PAGE_ID.'&forum_action=like_post&topic_id=' . $topic_arr['topic_id'] .
          '&post_id=' . $post_arr['post_id'].FORUM_URL_TOKEN).'">'.
          $sdlanguage['like_post'].'</a>';
        }
        else $showRemove = 1;

        $disliked = $DB->query_first('SELECT 1 postdisliked FROM {users_likes}'.
                                  " WHERE pluginid = %d AND objectid = %d ".
                                  " AND userid = %d AND liked_type = '%s' LIMIT 1",
                                  $this->conf->plugin_id, $post_arr['post_id'],
                                  $userinfo['userid'], SD_LIKED_TYPE_POST_NO);
        if(empty($disliked['postdisliked']))
        {
          if($this->conf->user_dislikes_perm)
          {
            $out2 = ' <a class="dislike-link" rel="nofollow" href="' .
              RewriteLink('index.php?categoryid='.PAGE_ID.'&forum_action=dislike_post&topic_id=' . $topic_arr['topic_id'] .
              '&post_id=' . $post_arr['post_id'].FORUM_URL_TOKEN).'">'.
              $sdlanguage['dislike_post'].'</a>';
          }
        }
        else $showRemove = 2;

        if(($showRemove==1) || ($showRemove==2))
        {
          $out = ' <a class="remove-like-link" rel="nofollow" href="' .
          RewriteLink('index.php?categoryid='.PAGE_ID.'&forum_action=remove_like&topic_id=' . $topic_arr['topic_id'] .
          '&post_id=' . $post_arr['post_id'].FORUM_URL_TOKEN).'">'.
          $sdlanguage[$showRemove==1?'remove_like':'remove_dislike'].'</a>';
        }
        if($out1 || $out2 || strlen($likes_txt))
          echo '<div class="likes-box">'.$likes_txt.(!$out1||!$out2?$out:'').$out1.$out2.'</div>';
      }
      else
      if(strlen($likes_txt))
      {
        echo '<div class="likes-box">'.$likes_txt.'</div>';
      }
      unset($likes_txt, $total_count, $ucount);

      echo '
           </div>
           </td></tr>';

      $post_idx++;
    } //while

    //SD343: display topic tags
    if(!empty($this->conf->topic_id))
    {
      if($topic_tags = $this->conf->GetTags($this->conf->topic_id))
      {
        echo '<tr><td colspan="2"><div class="topic-tags"><div class="topic-tags-title">'.
             $this->conf->plugin_phrases_arr['topic_tags'].'</div> '.
             $topic_tags.
             ' </div></td></tr>';
      }
      unset($topic_tags);
    }

    //SD342: display subscribe/unsubscribe link
    $subscription_html = '';
    if(!empty($userinfo['userid']) && !empty($this->conf->topic_id))
    {
      if($sub = new SDSubscription($userinfo['userid'], $this->conf->plugin_id,
                                   $this->conf->topic_id, 'topic', $categoryid))
      {
        if($sub->IsSubscribed())
        {
          $sub->UpdateRead();
          $subscription_html = '<a class="unsubscribe-link button-link" href="' .
               RewriteLink('index.php?categoryid='.PAGE_ID.'&forum_id=' . $forum_id .
               '&forum_action=unsubscribe_topic&topic_id='.$this->conf->topic_id.FORUM_URL_TOKEN) .
               '" title="'.$this->conf->plugin_phrases_arr['unsubscribe_from_topic'].'"><span>'.
               $this->conf->plugin_phrases_arr['unsubscribe'].'</span></a>';
        }
        else
        if(!empty(SDProfileConfig::$usergroups_config[$userinfo['usergroupid']]['enable_subscriptions']))
        {
          $subscription_html = '<a class="subscribe-link button-link" href="' .
               RewriteLink('index.php?categoryid='.PAGE_ID.'&forum_id='.$forum_id.
               '&forum_action=subscribe_topic&topic_id='.$this->conf->topic_id.FORUM_URL_TOKEN).
               '" title="'.$this->conf->plugin_phrases_arr['subscribe_to_topic'].'"><span>'.
               $this->conf->plugin_phrases_arr['subscribe'].'</span></a>';
        }
      }
      unset($sub);
    }

    if($this->conf->AllowSubmit)
    {
      $subscription_html .= ' <a class="new-topic-link button-link" href="' .
           RewriteLink('index.php?categoryid='.PAGE_ID.'&forum_id=' . $forum_id .
           '&forum_action=submit_topic'.FORUM_URL_TOKEN) . '"><span>'.
           $this->conf->plugin_phrases_arr['create_new_topic'].'</span></a> ';
    }
    //SD343: display optional no-access message
    else
    if(!empty($this->conf->plugin_settings_arr['display_no_post_access_topic']))
    {
      $subscription_html .= '<br />'.$this->conf->plugin_settings_arr['message_no_post_access_topic'];
    }

    if($paginated && $pagination_html)
    {
      if($subscription_html)
      {
        echo '<tr><td>'.$subscription_html.'</td><td class="pagination-bottom">'. $pagination_html;
      }
      else
      {
        echo '<tr><td colspan="2" class="pagination-bottom">'. $pagination_html;
      }
    }
    else
    {
      echo '<tr><td colspan="2">'. $subscription_html;
    }

    echo '</td></tr>';
    echo '</tbody></table>

    <div id="topic-footer">';

    // ##################################################
    // Display Admin Options
    // ##################################################
    if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator)
    {
      // Display Topic Options
      echo '<div class="topic-options">
            <input type="hidden" name="forum_id" value="'.$this->conf->forum_id.'" />
            '.PrintSecureToken(FORUM_TOKEN).'

            <select name="topic_action">
            <option value="0">'.$this->conf->plugin_phrases_arr['topic_options'].'</option>';

      if($this->conf->topic_arr['open'])
      {
        echo '<option value="lock_topic">'.$this->conf->plugin_phrases_arr['topic_options_lock_topic'].'</option>';
      }
      else
      {
        echo '<option value="unlock_topic">'.$this->conf->plugin_phrases_arr['topic_options_unlock_topic'].'</option>';
      }
      //SD321: move_topic_confirm added
      echo '<option value="delete_topic_confirm">'.$this->conf->plugin_phrases_arr['topic_options_delete_topic'].'</option>
            <option value="edit_topic">'.$this->conf->plugin_phrases_arr['topic_options_edit_topic'].'</option>
            <option value="move_topic_confirm">'.$this->conf->plugin_phrases_arr['topic_options_move_topic'].'</option>
            ';

      //SD321: stick/unstick topic
      if(empty($this->conf->topic_arr['sticky']))
      {
        echo '<option value="stick_topic">'.$this->conf->plugin_phrases_arr['topic_options_stick_topic'].'</option>';
      }
      else
      {
        echo '<option value="unstick_topic">'.$this->conf->plugin_phrases_arr['topic_options_unstick_topic'].'</option>';
      }

      if(empty($this->conf->topic_arr['moderated']))
      {
        echo '<option value="moderate_topic">'.$this->conf->plugin_phrases_arr['topic_options_moderate_topic'].'</option>';
      }
      else
      {
        echo '<option value="unmoderate_topic">'.$this->conf->plugin_phrases_arr['topic_options_unmoderate_topic'].'</option>';
      }
      echo '</select>';

      // Display Posts Options
      echo '
          <select id="posts_action" name="posts_action">
            <option value="0">'.$this->conf->plugin_phrases_arr['do_with_selected_posts'].'</option>
            <option value="delete_posts_confirm">'.$this->conf->plugin_phrases_arr['delete_posts'].'</option>
            <option value="moderate_posts">'.$this->conf->plugin_phrases_arr['topic_options_moderate_posts'].'</option>
            <option value="unmoderate_posts">'.$this->conf->plugin_phrases_arr['topic_options_unmoderate_posts'].'</option>
            <option value="move_posts_confirm">'.$this->conf->plugin_phrases_arr['topic_options_move_posts'].'</option>
          </select>
          '.$this->conf->plugin_phrases_arr['select'].
          ': <a id="forum-check-all-posts" href="#" onclick="return false">'.
            $this->conf->plugin_phrases_arr['select_all'].
          '</a> | <a id="forum-uncheck-all-posts" href="#" onclick="return false">'.
            $this->conf->plugin_phrases_arr['select_none'].'</a>
          | <a class="new-topic-link2" href="' .
          RewriteLink('index.php?categoryid='.PAGE_ID.'&forum_id=' . $forum_id .
                      '&forum_action=submit_topic'.FORUM_URL_TOKEN) . '">'.
         $this->conf->plugin_phrases_arr['create_new_topic'].'</a>
        </div>
        ';
    }

    // end topic-footer
    echo '</div>';

    echo '<div class="clear"></div>';

    if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator)
    {
      echo '</form>';
    }

    // ########################
    // Display Quick Reply form
    // ########################
    if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ||
       (($this->conf->topic_arr['open']) && $this->conf->AllowSubmit))
    {
      $post_msg = '';
      echo '
      <form action="' . $this->conf->current_page . '" enctype="multipart/form-data" method="post">
        <input type="hidden" name="topic_id" value="'.$this->conf->topic_arr['topic_id'].'" />
        <input type="hidden" name="forum_action" value="insert_post" />
        '.PrintSecureToken(FORUM_TOKEN).'
        <div class="form-wrap">
          <h2>'.$this->conf->plugin_phrases_arr['quick_reply'].'</h2>
          <br />';

      if(!empty($errored))
      {
        if(!empty($error_msg))
        {
          echo '<br />';
          DisplayMessage($error_msg, true);
        }
        $post_msg = GetVar('forum_post', '', 'string', true, false);
        /*
        //SD343: content filter
        if(!$this->conf->IsSiteAdmin && !$this->conf->IsAdmin && !$this->conf->IsModerator)
        {
          $post_msg = htmlspecialchars(sd_htmlawed($post_msg));
        }
        */
      }

      DisplayBBEditor(SDUserCache::$allow_bbcode, 'forum_post', $post_msg, FORUM_REPLY_CLASS, 80, 10);

      if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ||
         (!empty($this->conf->plugin_settings_arr['attachments_upload_path']) &&
          !empty($this->conf->plugin_settings_arr['valid_attachment_types']) &&
          (!empty($userinfo['plugindownloadids']) &&
           @in_array($this->conf->plugin_id, $userinfo['plugindownloadids']))))
      {
        echo '
        <div>&nbsp;';
        if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator)
        {
          echo '
          <input type="checkbox" name="stick_topic" value="1"'.($sticky_topic?' checked="checked" ':'').'/> '.
          $this->conf->plugin_phrases_arr['topic_options_stick_topic'].'
          &nbsp;<input type="checkbox" name="lock_topic" value="1"'.($locked_topic?' checked="checked" ':'').'/> '.
          $this->conf->plugin_phrases_arr['topic_options_lock_topic'].'
          &nbsp;<input type="checkbox" name="moderate_topic" value="1"'.($topic_moderated?' checked="checked" ':'').'/> '.
          $this->conf->plugin_phrases_arr['topic_options_moderate_topic'].'
          &nbsp;<input type="checkbox" name="reply_moderated" value="1" /> '.$this->conf->plugin_phrases_arr['reply_options_moderate_post'].'
          <br />';
        }

        if(!empty($this->conf->plugin_settings_arr['attachments_upload_path']) &&
           !empty($this->conf->plugin_settings_arr['valid_attachment_types']))
        {
          echo $this->conf->plugin_phrases_arr['upload_attachments'].'
          <input id="post_attachment" name="attachment" type="file" size="35" /> (' .
          $this->conf->plugin_settings_arr['valid_attachment_types'] . ')';
        }
        echo '
        </div>';
      }
      if(!($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator) ||
         !empty($userinfo['require_vvc'])) //SD340: require_vvc
      {
        DisplayCaptcha(true,'amihuman');
      }
      echo '
        <div style="float: right; display: inline;">
          <small>'.$this->conf->plugin_phrases_arr['bbcode_allowed'].': '.
          (!SDUserCache::$allow_bbcode ? $sdlanguage['no'] :'<a rel="nofollow" href="http://nbbc.sourceforge.net/doc/bbc.html" target="_blank">'.$sdlanguage['yes'].'</a>') .
          '</small>
        </div>
        <br />
        <input type="submit" name="submit" value="'.strip_alltags($this->conf->plugin_phrases_arr['post_reply']).'" />
      </div>
      </form>';
    }

  } //DisplayTopic


  // ##########################################################################


  function SubscribeForum() //SD342
  {
    global $DB, $categoryid, $userinfo, $sdlanguage;

    if(!CheckFormToken(FORUM_TOKEN, false))
    {
      $this->conf->RedirectPageDefault('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
      return;
    }

    $sub = new SDSubscription($userinfo['userid'], $this->conf->plugin_id, $this->conf->forum_id, 'forum', $categoryid);
    if(($sub->objectid==$this->conf->forum_id) && !$sub->IsSubscribed())
    {
      $sub->categoryid = $categoryid;
      if($sub->Subscribe($this->conf->plugin_id,$this->conf->forum_id, 'forum', $categoryid))
      $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr['you_are_subscribed'].'</strong>');
    }
    return true;
  } //SubscribeForum


  function UnsubscribeForum() //SD342
  {
    global $DB, $categoryid, $userinfo, $sdlanguage;

    if(!CheckFormToken(FORUM_TOKEN, false))
    {
      $this->conf->RedirectPageDefault('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
      return;
    }

    $sub = new SDSubscription($userinfo['userid'], $this->conf->plugin_id, $this->conf->forum_id, 'forum', $categoryid);
    if(($sub->objectid==$this->conf->forum_id) && $sub->IsSubscribed())
    {
      if($sub->Unsubscribe($this->conf->plugin_id,$this->conf->forum_id, 'forum'))
      $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr['you_are_unsubscribed'] . '</strong>');
    }
    return true;
  } //UnsubscribeForum


  function SubscribeTopic() //SD342
  {
    global $DB, $categoryid, $userinfo, $sdlanguage;

    if(!CheckFormToken(FORUM_TOKEN, false))
    {
      $this->conf->RedirectPageDefault('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
      return;
    }

    $sub = new SDSubscription($userinfo['userid'],$this->conf->plugin_id,$this->conf->topic_id,'topic',$categoryid);
    if(($sub->objectid==$this->conf->topic_id) && !$sub->IsSubscribed())
    {
      $sub->categoryid = $categoryid;
      if($sub->Subscribe($this->conf->plugin_id,$this->conf->topic_id,'topic',$categoryid))
      $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr['you_are_subscribed'].'</strong>');
    }
    return true;
  } //SubscribeTopic


  function UnsubscribeTopic() //SD342
  {
    global $DB, $categoryid, $userinfo, $sdlanguage;

    if(!CheckFormToken(FORUM_TOKEN, false))
    {
      $this->conf->RedirectPageDefault('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
      return;
    }

    $sub = new SDSubscription($userinfo['userid'],$this->conf->plugin_id,$this->conf->topic_id,'topic',$categoryid);
    if(($sub->objectid==$this->conf->topic_id) && $sub->IsSubscribed())
    {
      if($sub->Unsubscribe($this->conf->plugin_id,$this->conf->topic_id, 'topic'))
      $this->conf->RedirectPageDefault('<strong>'.$this->conf->plugin_phrases_arr['you_are_unsubscribed'].'</strong>');
    }
    return true;
  } //UnsubscribeTopic


  // ###########################################################################
  // DISPLAY FORUM MENU
  // ###########################################################################

  function DisplayMenu()
  {
    global $DB, $sdlanguage, $userinfo;

    $newposts = isset($_GET['newposts']);
    $outer_div = '
    <div id="forum-breadcrumb">
    ';
    $inner_div = '
      <div class="breadcrumb">
      ';

    $part1 = '';
    if($this->conf->topic_id)
    {
      //SD343 SEO Forum Title linking
      $link = $this->conf->RewriteForumLink($this->conf->topic_arr['forum_id']);
      $part1 .= '<a href="' . $this->conf->current_page . '">'.$this->conf->plugin_phrases_arr['breadcrumb_forum'].'</a>';
      $part1 .= ' &raquo; <a href="' . $link . '">' .
                trim($this->conf->forum_arr['title']) . '</a>';
    }
    else if($this->conf->forum_id)
    {
      $part1 .= '<a href="' . $this->conf->current_page . '">'.$this->conf->plugin_phrases_arr['breadcrumb_forum'].'</a>';
    }
    else
    if(isset($this->conf->search_text) && (strlen($this->conf->search_text) > 2))
    {
      $this->conf->search_text = str_replace('\_', '_', $this->conf->search_text);
      $this->conf->search_text = str_replace('\?', '?', $this->conf->search_text);
      $part1 .= '<a href="' . $this->conf->current_page . '">'.$this->conf->plugin_phrases_arr['breadcrumb_forum'].'</a> &raquo; '.
                $this->conf->plugin_phrases_arr['search_results_for'].
                ' <strong>'.unhtmlspecialchars($this->conf->search_text).'</strong>';
    }
    else
    if(isset($this->conf->search_tag) && (strlen($this->conf->search_tag) > 2))
    {
      $this->conf->search_tag = str_replace('\_', '_', $this->conf->search_tag);
      $this->conf->search_tag = str_replace('\?', '?', $this->conf->search_tag);
      $part1 .= '<a href="' . $this->conf->current_page . '">'.
                $this->conf->plugin_phrases_arr['breadcrumb_forum'].'</a> ';
    }
    else
    if($newposts)
    {
      $part1 .= '<a href="' . $this->conf->current_page . '">'.$this->conf->plugin_phrases_arr['breadcrumb_forum'].'</a>';
    }

    if(strlen($part1))
    {
      $part1 =  $inner_div . $part1.'
      </div>';
    }

    // SD313: enable search header?
    $inner_div = '
      <div style="float: right; display: inline; padding: 4px;">
      <span style="display: inline"><a href="'.RewriteLink('index.php?categoryid='.PAGE_ID.'&newposts=1').'">'.$this->conf->plugin_phrases_arr['new_posts'].'</a></span>
      <span style="display: inline"> | </span>
      <a rel="nofollow" href="#" title="'.$this->conf->plugin_phrases_arr['search_forums'].'" id="forum_search_link">'.$this->conf->plugin_phrases_arr['search_forums'].'</a>
      </div>
      ';
    $part2 = '';
    if($this->conf->plugin_settings_arr['enable_search'] && (!empty($userinfo['loggedin']) || empty($this->conf->plugin_settings_arr['disable_guest_search'])))
    {
      $searchtopics = !isset($_POST['forum_search']) || !empty($_POST['searchtopics']);
      $searchposts  = !isset($_POST['forum_search']) || !empty($_POST['searchposts']);
      $searchusers  = false;
      $searchtags   = !empty($_POST['searchtags']); //SD343
      $part2 = '
      <div class="forum-search" style="display: none">
      <form id="forum-searchbar" action="' .$this->conf->current_page . '" method="post">
        '.PrintSecureToken(FORUM_TOKEN).'
        <input type="text" tabindex="1001" id="forum_search" value="'.
          ($this->conf->action == 'search_forums' ? htmlspecialchars($this->conf->search_text, ENT_COMPAT) : '') .
          '" name="forum_search" /><input type="submit" tabindex="1002" id="search" value="'.
          htmlspecialchars($this->conf->plugin_phrases_arr['search'], ENT_COMPAT).'" />
        <br />
        <label for="forum_st"><input type="checkbox" name="searchtopics" value="1" id="forum_st" tabindex="1003" '.($searchtopics?'checked="checked"':'').' /> '.$this->conf->plugin_phrases_arr['search_topics'].'</label><br />
        <label for="forum_sp"><input type="checkbox" name="searchposts"  value="1" id="forum_sp" tabindex="1004" '.($searchposts ?'checked="checked"':'').' /> '.$this->conf->plugin_phrases_arr['search_posts'].'</label><br />
        <label for="forum_su"><input type="checkbox" name="searchusers" value="1" id="forum_su" tabindex="1005" '.($searchusers ?'checked="checked"':'').' /> '.$this->conf->plugin_phrases_arr['search_usernames'].'</label><br />
        <label for="forum_tag"><input type="checkbox" name="searchtags" value="1" id="forum_tag" tabindex="1006" '.($searchtags?'checked="checked"':'').' /> '.$sdlanguage['common_search_tags'].'</label><br />
      </form>
      ';
    }

    if(strlen($part2))
    {
      $part2 =  $inner_div . $part2.'
      </div>';
    }

    $forum_menu = $part1.$part2;
    if(strlen($forum_menu))
    {
      echo $forum_menu.'
      <div class="clear"></div>
      ';
    }

    return true;

  } //DisplayMenu


  // ###########################################################################
  // DISPLAY FORUMS (BELONGING TO A CATEGORY)
  // ###########################################################################

  function DisplayForums($parent_id, $forum_id, $title)
  {
    global $DB, $categoryid, $mainsettings, $userinfo, $usersystem;

    // SD313: check for view permission for usergroup (id enclosed in pipes!)
    //        and moderation status of forums
    $forums_tbl = PRGM_TABLE_PREFIX.'p_forum_forums';
    $posts_tbl  = PRGM_TABLE_PREFIX.'p_forum_posts';
    $topics_tbl = PRGM_TABLE_PREFIX.'p_forum_topics';
    $forum_id = (int)$forum_id;

    if(empty($parent_id) || !isset($this->conf->forums_cache['parents'][$parent_id])) // SD343: "isset"
    {
      $source = array($forum_id);
    }
    else
    {
      $parent_id = (int)$parent_id;
      $source = &$this->conf->forums_cache['parents'][$parent_id];
    }

    $output = '';
    $do_body = false;
    if(isset($source) && is_array($source))
    foreach($source as $fid)
    {
      $forum_arr = isset($this->conf->forums_cache['forums'][$fid]) ?
                    (array)$this->conf->forums_cache['forums'][$fid] : false;
      if(!$forum_arr) continue;
      $forum_groups = sd_ConvertStrToArray($forum_arr['access_view'],'|');
      if(!$this->conf->IsSiteAdmin)
      {
        if(empty($forum_arr['online'])) continue;
        if(!empty($forum_groups) && !count(@array_intersect($userinfo['usergroupids'], $forum_groups)))
          continue;
      }

      if(!$do_body)
      {
        $output .=  '
        <tbody>';
        $do_body = true;
      }

      $external = false;
      $target = '';
      if(!empty($forum_arr['is_category']))
      {
        $link = '#';
      }
      else
      if(empty($forum_arr['link_to']) || !strlen(trim($forum_arr['link_to'])))
      {
        //SD343 SEO Forum Title linking
        $link = $this->conf->RewriteForumLink($fid);
      }
      else
      {
        $external = true;
        $link = trim($forum_arr['link_to']);
        $target = strlen($forum_arr['target']) ? ' target="'.$forum_arr['target'].'" ' : '';
      }

      // Display forum image
      $img_col = false;
      if(!empty($this->conf->plugin_settings_arr['display_forum_image']))
      {
        $image = false;
        if(!empty($forum_arr['image']) && !empty($forum_arr['image_h']) && !empty($forum_arr['image_w']))
        {
          $image = $forum_arr['image'].'" alt="" width="'.$forum_arr['image_w'].'" height="'.$forum_arr['image_h'].'" style="border:none" />';
        }
        if($image) $image = '<img src="'.SDUserCache::$img_path.$image;
        $img_col = '<td class="col-forum-icon"><a class="forum-title-link" '.$target.'href="'.$link.'">'. $image.'</a></td>';
      }

      $output .= '
        <tr>'.($img_col?$img_col:'').'
        <td class="col-forum-title"><a class="forum-title-link" '.$target.'href="'.$link.'">'.
          (!$forum_arr['online'] ? '<img src="'.SDUserCache::$img_path.'lock.png" alt="'.
           strip_alltags($this->conf->plugin_phrases_arr['forum_offline']).'" />' : '').$forum_arr['title'].'</a>'.
          (strlen($forum_arr['description']) ? ' <p class="forum-description">' . $forum_arr['description'].'</p>' : '');

      // Display sub-forums of current forum
      if(!empty($forum_arr['subforums']))
      {
        $sub_links = array();
        foreach($this->conf->forums_cache['parents'][$fid] as $subid)
        {
          $sub_output = '';
          $sub  = $this->conf->forums_cache['forums'][$subid];
          if(!$this->conf->IsSiteAdmin)
          {
            if(empty($sub['online'])) continue;
            $subforum_groups = sd_ConvertStrToArray($sub['access_view'],'|');
            if(!empty($subforum_groups) && !count(@array_intersect($userinfo['usergroupids'], $subforum_groups)))
              continue;
          }

          $image = '';
          if(empty($sub['online']) && (empty($sub['link_to']) || !strlen(trim($sub['link_to']))))
          {
            $image = '<img src="'.SDUserCache::$img_path.'lock.png" width="16" height="16" alt="'.
                     htmlspecialchars($this->conf->plugin_phrases_arr['forum_offline'], ENT_COMPAT).'" />';
          }
          else
          if(!empty($this->conf->plugin_settings_arr['display_forum_image']))
          {
            if(!empty($sub['image']) && !empty($sub['image_h']) && !empty($sub['image_w']))
            {
              $image = '<img src="'.SDUserCache::$img_path.$sub['image'].'" alt="" width="'.$sub['image_w'].'" height="'.$sub['image_h'].'" style="border:none" />';
            }
            $descr = strip_tags(str_replace(array('<br>','<br />','&quot;'),array(' ',' ',''), $sub['description']));
          }

          if(empty($sub['link_to']) || !strlen(trim($sub['link_to'])))
          {
            $target = '';
            //SD343 SEO Forum Title linking
            $link = $this->conf->RewriteForumLink($sub['forum_id']);
          }
          else
          {
            $link = trim($sub['link_to']);
            $target = strlen($sub['target']) ? ' target="'.$sub['target'].'" ' : '';
            if(!$image)
            $image = '<img src="'.SDUserCache::$img_path.'arrow-right.png" width="14" height="14" alt="" />';
          }
          if($descr = strip_tags(str_replace(array('<br>','<br />','&quot;'),array(' ',' ',' '), $sub['description'])))
          {
            $descr = 'title="'.htmlspecialchars($descr).'" ';
          }
          $sub_output .= $image.'<a class="forum-sublink" '.$target.$descr.'href="'.$link.'">'.$sub['title'].'</a>';
          $sub_links[] = $sub_output;
        }
        if(!empty($sub_links))
        $output .= '
        <p class="sub-forums">'.implode(', ',$sub_links).'</p>';
        unset($sub_links);
      }

      $output .= '
          </td>';
      if(!empty($mainsettings['enable_rss_forum']))
      {
        $output .= '
        <td class="col-rss"><a title="RSS" class="rss-icon" href="forumrss.php?forum_id=' . $fid . '">RSS</a></td>';
      }

      //SD342: add first 100 chars as link title for preview
      $post_hint = '';
      if(!empty($forum_arr['post_text']))
      $post_hint = SDForumConfig::GetBBCodeExtract($forum_arr['post_text']);

      $output .= '
        <td class="col-topic-count">' . number_format($forum_arr['topic_count']) . '</td>
        <td class="col-post-count">'  . number_format($forum_arr['post_count'])  . '</td>
        <td class="col-last-updated"'.$post_hint.' style="cursor:normal">';

      if(!empty($forum_arr['last_post_date']))
      {
        $thread_title = $forum_arr['last_topic_title'];
        //SD370: fetch prefix (if present and uncensored) and display it
        $thread_prefix = '';
        if(!empty($forum_arr['last_topic_id']))
        {
          if($prefix = $this->GetTopicPrefix($forum_arr['last_topic_id']))
          {
            $thread_prefix = str_replace('[tagname]', $prefix['tag'], $prefix['html_prefix']).' ';
          }
          unset($prefix);
        }

        if(strlen($thread_title) > 30)
        {
          $space_position = strrpos($thread_title, " ") - 1;
          //SD343: respect utf-8
          if($space_position === false)
            $space_position = 30;
          else
            $space_position++;
          $thread_title = sd_substr($thread_title, 0, $space_position) . '...';
        }

        //SD343 SEO Forum Title linking
        $link = $forum_arr['last_topic_seo_url'];
        $output .= '<a class="jump-post-link" href="'.$link.'">'.$thread_prefix.trim($thread_title).'</a>';
        if(!empty($forum_arr['poster_id']))
        {
          $poster_id = (int)$forum_arr['poster_id'];
          //SD342: user caching
          $poster = SDUserCache::CacheUser($poster_id, $forum_arr['last_post_username'], false);
          $output .= '<br />'.$this->conf->plugin_phrases_arr['posted_by'].' '.$poster['profile_link'];
        }
        else
        {
          $output .= $forum_arr['last_post_username'];
        }
        $output .= '<br />
          ' . $this->conf->GetForumDate($forum_arr['last_post_date']);
      }

      $output .= '</td>
        </tr>';

    } //while

    if($do_body)
    {
       $output .= '
        </tbody>
        <!-- DisplayForums -->
        ';
       return $output;
    }
    return false;

  } //DisplayForums


  // ###########################################################################
  // DISPLAY FORUM CATEGORIES
  // ###########################################################################

  function DisplayForumCategories()
  {
    global $DB, $categoryid, $mainsettings, $sdlanguage, $sdurl, $userinfo, $usersystem;

    //SD343: display optional tag cloud
    $forum_tags = false;
    if(!empty($this->conf->plugin_settings_arr['display_tagcloud']))
    {
      require_once(SD_INCLUDE_PATH.'class_sd_tags.php');
      SD_Tags::$maxentries   = 30;
      SD_Tags::$plugins      = $this->conf->plugin_id;
      SD_Tags::$tags_title   = $sdlanguage['tags_title'];
      SD_Tags::$targetpageid = $categoryid;
      SD_Tags::$tag_as_param = 'tag';
      if($forum_tags = SD_Tags::DisplayCloud(false))
      {
        if(in_array($this->conf->plugin_settings_arr['display_tagcloud'],array(1,3)))
        {
          echo $forum_tags;
        }
      }
    }

    echo '
    <table border="0" class="forums" cellpadding="0" cellspacing="0" summary="layout" width="100%"><thead><tr>';
    if(!empty($this->conf->plugin_settings_arr['display_forum_image']))
    {
      echo '<th class="col-forum-icon"> </th>';
    }
    echo '<th class="col-forum-title">'.$this->conf->plugin_phrases_arr['column_forum'].'</th>';
    if(!empty($mainsettings['enable_rss_forum']))
    {
      echo '<th class="col-rss"><a title="RSS" class="rss-icon" href="forumrss.php">RSS</a></th>';
    }
    echo '<th class="col-topic-count">'.$this->conf->plugin_phrases_arr['column_topics'].'</th>
          <th class="col-post-count">'.$this->conf->plugin_phrases_arr['column_posts'].'</th>
          <th class="col-last-updated">'.$this->conf->plugin_phrases_arr['column_last_updated'].'</th>
        </tr></thead>';

    if(isset($this->conf) && isset($this->conf->forums_cache['categories'])) //SD343
    foreach($this->conf->forums_cache['categories'] as $fid)
    {
      if(!isset($this->conf->forums_cache['forums'][$fid])) continue; //SD343
      $entry = $this->conf->forums_cache['forums'][$fid];

      if(!$this->conf->IsSiteAdmin)
      {
        if(empty($entry['online'])) continue;
        $entry_groups = sd_ConvertStrToArray($entry['access_view'],'|');
        if(!empty($entry_groups) && !count(@array_intersect($userinfo['usergroupids'], $entry_groups)))
          continue;
      }

      if(empty($entry['parent_forum_id']) && empty($entry['is_category']))
      {
        if($res = $this->DisplayForums(0,$fid,$entry['title']))
        {
          echo $res;
        }
        continue;
      }

      $output = '
      <tbody>
      <tr>
        <td colspan="6" class="category-cell">
        <div class="category-title-container">';

      if(strlen($entry['title']))
      {
        $image = '';
        if(!empty($entry['image']) && !empty($entry['image_h']) && !empty($entry['image_w']))
        {
          $image = '<img src="'.SDUserCache::$img_path.$entry['image'].'" alt="" width="'.(int)$entry['image_w'].'" height="'.(int)$entry['image_h'].'" />';
        }
        $output .= '<span class="category-image">'.($image?$image:'&nbsp;').'</span> <span class="category-title">'.$entry['title'].'</span>';
        if(strlen($entry['description']))
        {
          $output .= '
          <span class="category-description">' . $entry['description']. '</span>';
        }
      }
      $output .= '
        </div>
        </td>
      </tr>
      </tbody>';

      if($res = $this->DisplayForums($fid,0,$entry['title']))
      {
        echo $output . $res;
      }

    } //foreach

    echo '
    </table>
    ';

    //SD343: display optional tag cloud
    if(!empty($forum_tags) && !empty($this->conf->plugin_settings_arr['display_tagcloud']) &&
       in_array($this->conf->plugin_settings_arr['display_tagcloud'],array(2,3)))
    {
      echo $forum_tags;
    }

  } //DisplayForumCategories


  // ###########################################################################
  // DISPLAY FORUM
  // ###########################################################################

  function DisplayForum()
  {
    global $DB, $categoryid, $sdlanguage, $sdurl, $userinfo, $usersystem;

    $search_mod = (in_array($this->conf->action, array('search_forums','mod_topics','mod_posts')));

    //SD343: display optional tag cloud
    $forum_tags = false;
    if(!$search_mod && !empty($this->conf->plugin_settings_arr['display_tagcloud']))
    {
      require_once(SD_INCLUDE_PATH.'class_sd_tags.php');
      SD_Tags::$maxentries   = 30;
      SD_Tags::$plugins      = $this->conf->plugin_id;
      SD_Tags::$tags_title   = $sdlanguage['tags_title'];
      SD_Tags::$targetpageid = $categoryid;
      SD_Tags::$tag_as_param = 'tag';
      if($forum_tags = SD_Tags::DisplayCloud(false))
      {
        if(in_array($this->conf->plugin_settings_arr['display_tagcloud'],array(1,3)))
        {
          echo $forum_tags;
        }
      }
    }

    $forum_arr = $this->conf->forum_arr;
    $forum_id = $forum_arr['forum_id'];

    $image = '';

    // SD313: use new setting for forum page size
    $items_per_page = Is_Valid_Number($this->conf->plugin_settings_arr['forum_page_size'],15,1,100);

    $page = Is_Valid_Number(GetVar('page', 1, 'whole_number'),1,1);
    $limit = ' LIMIT '.(($page-1)*$items_per_page).','.$items_per_page;

    $total_rows = $forum_arr['topic_count'];
    if($search_mod) //SD351
    {
      if($this->conf->action == 'mod_topics')
      {
        $sql =
      "SELECT ft.*, fp.user_id poster_id, fp.username last_post_username, '' last_post_text,
       ft.post_user_id first_poster_id, IFNULL(fp2.username,'') first_post_username,
       '' first_post_text,
       IFNULL(ud.user_screen_name, fp.username) user_screen_name
       FROM ".PRGM_TABLE_PREFIX.'p_forum_topics ft
       INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_forums ff ON ft.forum_id = ff.forum_id
       LEFT JOIN '.PRGM_TABLE_PREFIX.'p_forum_posts fp ON (fp.post_id = ft.last_post_id)
       LEFT JOIN '.PRGM_TABLE_PREFIX.'p_forum_posts fp2 ON (fp2.post_id = ft.first_post_id)
       LEFT JOIN '.PRGM_TABLE_PREFIX.'users_data ud ON (ud.usersystemid = %d AND ud.userid = fp.user_id)
       WHERE ff.forum_id = %d AND ft.moderated = 1'.
       $this->conf->GroupCheck.
       ' ORDER BY IF(ft.last_post_date=0,ft.date,ft.last_post_date) DESC';
      }
      else
      {
        $sql =
      "SELECT ft.*, fp.user_id poster_id, fp.username last_post_username, '' last_post_text,
       ft.post_user_id first_poster_id, IFNULL(fp.username,'') first_post_username,
       '' first_post_text,
       IFNULL(ud.user_screen_name, fp.username) user_screen_name,
       fp.post_id real_post_id, fp.date real_post_date, fp.user_id real_poster_id
       FROM ".PRGM_TABLE_PREFIX.'p_forum_topics ft
       INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_forums ff ON ft.forum_id = ff.forum_id
       INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_posts fp ON (fp.topic_id = ft.topic_id)
       LEFT JOIN '.PRGM_TABLE_PREFIX.'users_data ud ON (ud.usersystemid = %d AND ud.userid = fp.user_id)
       WHERE ff.forum_id = %d AND fp.moderated = 1'.
       $this->conf->GroupCheck.
       ' ORDER BY IF(ft.last_post_date=0,ft.date,ft.last_post_date) DESC';
      }
      $get_topics = $DB->query($sql, $usersystem['usersystemid'], $forum_id);
    }
    else
    {
      $get_topics = $DB->query(
        "SELECT ft.*, fp.user_id poster_id, fp.username last_post_username, fp.post last_post_text,
         ft.post_user_id first_poster_id, IFNULL(fp2.username,'') first_post_username,
         IFNULL(fp2.post,'') first_post_text,
         IFNULL(ud.user_screen_name, fp.username) user_screen_name
         FROM ".PRGM_TABLE_PREFIX.'p_forum_topics ft
         INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_forums ff ON ft.forum_id = ff.forum_id
         INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_posts fp  ON (fp.post_id = ft.last_post_id)
         INNER JOIN '.PRGM_TABLE_PREFIX.'p_forum_posts fp2 ON (fp2.post_id = ft.first_post_id)
         LEFT  JOIN '.PRGM_TABLE_PREFIX.'users_data ud     ON (ud.usersystemid = %d AND ud.userid = fp.user_id)
         WHERE ff.forum_id = %d '.
         ($this->conf->IsSiteAdmin ? '' : ' AND ff.online = 1 ').
         ($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator ? '' :
            ' AND (IFNULL(ft.moderated,0) = 0) AND (IFNULL(fp.moderated,0) = 0)').
         $this->conf->GroupCheck.
         ' ORDER BY ft.sticky DESC, IF(ft.last_post_date=0,ft.date,ft.last_post_date) DESC ' . $limit,
         $usersystem['usersystemid'], $forum_id);
    }
    $topic_count = $DB->get_num_rows($get_topics);
    if($search_mod) $total_rows = $topic_count;
    if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator)
    {
      echo '
      <form id="topic-options" action="' . $this->conf->current_page . '" method="post">
      <input type="hidden" name="forum_id" value="'.$forum_id.'" />
      <input type="hidden" name="forum_action" value="forum_options" />
      '.PrintSecureToken(FORUM_TOKEN);
    }
    //SD343: added class="forum-title"
    echo '
      <div class="forum-title">
      <h2>';
    if(empty($forum_arr['online']))
    {
      $image = '<img src="'.SDUserCache::$img_path.'lock.png" alt="'.
               htmlspecialchars($this->conf->plugin_phrases_arr['forum_offline']).'" /> ';
    }
    else
    if(!empty($this->conf->plugin_settings_arr['display_forum_image']))
    {
      if(!empty($forum_arr['image']) && !empty($forum_arr['image_h']) && !empty($forum_arr['image_w']))
      {
        $image = '<img class="forum-thumb" src="'.SDUserCache::$img_path.$forum_arr['image'].'" alt="'.
                 htmlspecialchars($forum_arr['title']).'" width="'.(int)$forum_arr['image_w'].'" height="'.(int)$forum_arr['image_h'].'" /> ';
      }
    }
    echo $image . trim($forum_arr['title']).'</h2>
      </div>';

    // Cache Pagination:
    $pagination_html = '';
    if($topic_count)
    {
      $p = new pagination;
      $p->enablePrevNext = true; //SD343
      $p->className = 'forum-pagination';
      $p->showCounter = true;
      $p->pagesT = $this->conf->plugin_phrases_arr['topics'];
      $p->items($total_rows);
      $p->limit($items_per_page);
      $p->currentPage($page);
      $p->adjacents($this->conf->paging_adjacents);
      //SD343 SEO Forum Title linking
      $link = $this->conf->RewriteForumLink($forum_id);
      if($search_mod)
      {
        $link .= ((strpos($link,'?')===false)?'?':'&amp;') .
                 'forum_action='.$this->conf->action;
      }
      $p->target($link);
      $pagination_html = $p->getOutput();
      if(trim($pagination_html)=='<div class="forum-pagination"></div>') $pagination_html='';
      $pagination_html = preg_replace('#\?page=1"#im','"',$pagination_html);
      unset($p);
    }

    if($this->conf->IsSiteAdmin || $this->conf->IsAdmin ||
       $this->conf->IsModerator || $this->conf->AllowSubmit)
    {
      echo '<a class="new-topic-link button-link" href="' .
        RewriteLink('index.php?categoryid='.PAGE_ID.'&forum_id=' . $forum_id .
        '&forum_action=submit_topic'.FORUM_URL_TOKEN) . '"><span>'.
        $this->conf->plugin_phrases_arr['create_new_topic'].'</span></a>';
    }

    echo ($topic_count && $pagination_html ? "\n      ".$pagination_html:'');

    echo '
        <table border="0" cellpadding="0" cellspacing="0" summary="layout" width="100%">
        <thead>
        <tr>
          <th class="col-topic-title">'.$this->conf->plugin_phrases_arr['column_topic'].'</th>
          <th class="col-post-count">'.$this->conf->plugin_phrases_arr['column_posts'].'</th>';
    // SD313: diplay view count
    if(!empty($this->conf->plugin_settings_arr['display_view_count']))
    {
      echo '<th class="col-post-count">'.$this->conf->plugin_phrases_arr['column_view_count'].'</th>';
    }
    echo '<th class="col-last-updated">'.$this->conf->plugin_phrases_arr['column_last_updated'].'</th>
        </tr>
        </thead>
        <tbody>';

    if($topic_count)
    while($topic_arr = $DB->fetch_array($get_topics,null,MYSQL_ASSOC))
    {
      $tr_class = !isset($tr_class) ? 'topic' : ($tr_class == 'topic' ? 'topic alt' : 'topic');

      $moderated_class = empty($topic_arr['moderated']) ? '' : ' topic-moderated';

      //SD342: add first 150 chars as link title for preview
      $first_post_hint = $last_post_hint = '';
      if(isset($topic_arr['first_post_text'])) $first_post_hint = SDForumConfig::GetBBCodeExtract($topic_arr['first_post_text']);
      if(isset($topic_arr['last_post_text']))  $last_post_hint  = SDForumConfig::GetBBCodeExtract($topic_arr['last_post_text']);

      echo '<tr class="' . $tr_class . '">
          <td class="col-topic-title"'.$first_post_hint.'>';

      //SD342: user caching of first poster, incl. avatar
      $first_poster = SDUserCache::CacheUser($topic_arr['first_poster_id'], $topic_arr['first_post_username'], true, true);

      if(SDUserCache::$show_avatars && $first_poster['avatar'])
        echo $first_poster['topic_avatar'];
      else
        echo '<div class="topic-avatar">'.GetDefaultAvatarImage(SDUserCache::$avatar_width,SDUserCache::$avatar_height).'</div> ';

      echo (empty($topic_arr['open'])? '<img alt="locked" src="'.SDUserCache::$img_path.'lock.png" width="16" height="16" title="'.htmlspecialchars($this->conf->plugin_phrases_arr['topic_locked']).'" /> ' : '');
      echo (!empty($topic_arr['sticky'])? '<img alt="sticky" src="'.SDUserCache::$img_path.'sticky.png" width="16" height="16" title="'.htmlspecialchars($this->conf->plugin_phrases_arr['sticky']).'" /> ' : '');
      echo (!empty($topic_arr['moderated'])? '<img alt="!" src="'.SDUserCache::$img_path.'attention.png" width="16" height="16" /> ' : '');

      //SD343: fetch prefix (if present and uncensored) and display it
      if($prefix = $this->GetTopicPrefix($topic_arr['topic_id']))
      {
        echo str_replace('[tagname]', $prefix['tag'], $prefix['html_prefix']);
      }
      unset($prefix);

      //SD343 SEO Forum Title linking
      $link = $this->conf->RewriteTopicLink($topic_arr['topic_id'],$topic_arr['title']);
      echo '<a class="topic-link'.$moderated_class.'" href="'.$link.'" rel="'.$topic_arr['topic_id'].'">' .
           trim($topic_arr['title']) . '</a>';
      if($this->conf->IsAdmin || $this->conf->IsSiteAdmin || $this->conf->IsModerator)
      {
        echo '<span class="editable" style="display:none;float:right;"></span>';
      }
      echo '<br />';

      if(!empty($first_poster['forum']))
      {
        echo $first_poster['forum'];
      }
      else
      {
        echo $topic_arr['first_post_username'];
      }
      echo '
          </td>
          <td class="col-post-count">'.$topic_arr['post_count'].'</td>';
      // SD313: diplay view count
      if(!empty($this->conf->plugin_settings_arr['display_view_count']))
      {
        echo '<td class="col-post-count">'.$topic_arr['views'].'</td>';
      }
      echo '<td class="col-last-updated"'.$last_post_hint.'>';
      //SD 313: check permissions to display checkbox
      if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator)
      {
        echo '<input type="checkbox" name="topic_id_arr[]" value="'.$topic_arr['topic_id'].'" style="float: right;" />';
      }
      //SD343 SEO Forum Title linking
      if($search_mod && ($this->conf->action=='mod_posts'))
      {
        $postdate = $topic_arr['real_post_date'];
        $postid   = $topic_arr['real_post_id'];
        $posterid = $topic_arr['real_poster_id'];
      }
      else
      {
        $postdate = $topic_arr['last_post_date'];
        $postid   = $topic_arr['last_post_id'];
        $posterid = $topic_arr['poster_id'];
      }
      $link = $this->conf->RewritePostLink($topic_arr['topic_id'],$topic_arr['title'],$postid);
      //SD 313: show actual date as link's "title" attribute
      echo '<a class="jump-post-link" href="'.$link.'" title="'.DisplayDate($postdate,'',true).'">' .
           $this->conf->GetForumDate($postdate) . ' </a>
           <br />
           '.$this->conf->plugin_phrases_arr['posted_by'].' ';

      if(!empty($posterid))
      {
        $last_poster = SDUserCache::CacheUser($posterid,$topic_arr['last_post_username'], false);
        echo $last_poster['forum'];
      }
      else
      {
        echo $topic_arr['last_post_username'];
      }
      echo '</td>
        </tr>';
    } //while
    unset($first_poster,$last_poster);

    if(!$topic_count)
    {
      echo '<tr><td colspan="4">'.$this->conf->plugin_phrases_arr['no_topics_found'].'</td></tr>';
    }
    //SD342: display subscribe/unsubscribe link
    $subscription_html = '';
    if(!empty($userinfo['userid']) && !empty($this->conf->forum_id))
    {
      if($sub = new SDSubscription($userinfo['userid'],$this->conf->plugin_id,$this->conf->forum_id,'forum',$categoryid))
      {
        if($sub->IsSubscribed())
        {
          $sub->UpdateRead();
          $subscription_html = '<a class="unsubscribe-link button-link" href="' .
            RewriteLink('index.php?categoryid='.PAGE_ID.'&forum_id=' . $forum_id .
              '&forum_action=unsubscribe_forum'.FORUM_URL_TOKEN) .
            '" title="'.
            $this->conf->plugin_phrases_arr['unsubscribe_from_forum'].'"><span>'.
            $this->conf->plugin_phrases_arr['unsubscribe_forum'].'</span></a>';
        }
        elseif(!empty(SDProfileConfig::$usergroups_config[$userinfo['usergroupid']]['enable_subscriptions']))
        {
          $subscription_html = '<a class="subscribe-link button-link" href="' .
            RewriteLink('index.php?categoryid='.PAGE_ID.'&forum_id=' . $forum_id .
              '&forum_action=subscribe_forum'.FORUM_URL_TOKEN) . '" title="'.
            $this->conf->plugin_phrases_arr['subscribe_to_forum'].'"><span>'.
            $this->conf->plugin_phrases_arr['subscribe_forum'].'</span></a>';
        }
      }
      unset($sub);
    }

    echo '<tr><td colspan="4">';
    if($subscription_html) echo $subscription_html;
    if($pagination_html) echo $pagination_html;
    if($this->conf->IsSiteAdmin || $this->conf->IsAdmin ||
       $this->conf->IsModerator || $this->conf->AllowSubmit)
    {
      echo ($subscription_html?'<br />':'').'
        <a class="new-topic-link button-link" href="' .
           RewriteLink('index.php?categoryid='.PAGE_ID.'&forum_id=' . $forum_id .
             '&forum_action=submit_topic'.FORUM_URL_TOKEN) .
           '"><span>'.$this->conf->plugin_phrases_arr['create_new_topic'].' </span></a>';
    }
    echo '
        </td></tr>
      </tbody>
      </table>';

    if($topic_count)
    {
      echo '
      <div id="topic-footer">';

      if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator)
      {
        echo '
        <div class="topic-options">
        <select name="topic_action">
        <option value="0">'.$this->conf->plugin_phrases_arr['topic_options'].'</option>
        <option value="lock_topics">'.$this->conf->plugin_phrases_arr['topic_options_lock_topics'].'</option>
        <option value="unlock_topics">'.$this->conf->plugin_phrases_arr['topic_options_unlock_topics'].'</option>
        <option value="delete_topic_confirm">'.$this->conf->plugin_phrases_arr['topic_options_delete_topics'].'</option>
        <option value="merge_topics_confirm">'.$this->conf->plugin_phrases_arr['topic_options_merge_topics'].'</option>
        <option value="move_topics_confirm">'.$this->conf->plugin_phrases_arr['topic_options_move_topics'].'</option>
        <option value="moderate_topics">'.$this->conf->plugin_phrases_arr['topic_options_moderate_topics'].'</option>
        <option value="unmoderate_topics">'.$this->conf->plugin_phrases_arr['topic_options_unmoderate_topics'].'</option>
        <option value="stick_topic">'.$this->conf->plugin_phrases_arr['topic_options_stick_topic'].'</option>
        <option value="unstick_topic">'.$this->conf->plugin_phrases_arr['topic_options_unstick_topic'].'</option>
        </select>
        '.$this->conf->plugin_phrases_arr['select'].': <a id="forum-check-all-topics" href="' .
          RewriteLink('index.php?categoryid='.PAGE_ID.'&forum_id=' . $this->conf->forum_id) . '">'.
          $this->conf->plugin_phrases_arr['select_all'].'</a> | <a id="forum-uncheck-all-topics" href="' .
          RewriteLink('index.php?categoryid='.PAGE_ID.'&forum_id=' . $this->conf->forum_id) . '">'.
          $this->conf->plugin_phrases_arr['select_none'].'</a>
        </div>';
      }

      // end topic-footer
      echo '</div>';
    }
    echo '<div class="clear"></div>';

    if($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator)
    {
      echo '</form>';
    }

    //SD343: display optional tag cloud
    if(!empty($forum_tags) && !empty($this->conf->plugin_settings_arr['display_tagcloud']) &&
       in_array($this->conf->plugin_settings_arr['display_tagcloud'],array(2,3)))
    {
      echo $forum_tags;
    }

  } //DisplayForum

  // ##########################################################################

  function StartForum()
  {
    global $userinfo;

    // SD343: IF an error exists already, it's because of an invalid
    //  forum or topic id, so bail out with error message
    if(!empty($this->conf->error_arr))
    {
      $this->conf->RedirectPageDefault($this->conf->error_arr,true);
      return false;
    }

    $this->conf->action = GetVar('forum_action', 'display_forums', 'string');
    $post_action  = GetVar('post_action',  '', 'string');
    $att_id = GetVar('attachment_id', 0, 'whole_number');
    if(($post_action=='attdel') && ($att_id > 0)) //SD343
    {
      #do nothing here
    }
    else
    if(($post_action=='attdel') && ($att_id < 1)) //SD343
      $post_action = '';
    else
    // SD313: is search enabled?
    if(!empty($this->conf->plugin_settings_arr['enable_search']))
    {
      $this->conf->search_tag  = GetVar('tag', false, 'string', false, true);
      $this->conf->search_text = GetVar('forum_search', false, 'string');
      $this->conf->action = ($this->conf->search_tag || $this->conf->search_text) ? 'search_forums' : $this->conf->action;
      // ------------------------------------------------------
      // Security: sanitize/check search text restrictively...
      // ------------------------------------------------------
      //test case:
      //$this->conf->search_text = "\n''\r'`~^ \t \' \r  ''#";
      if(!empty($this->conf->search_tag)) //SD343
      {
        $this->conf->search_tag = SanitizeInputForSQLSearch($this->conf->search_tag, true);
        if(strlen($this->conf->search_tag))
        {
          // trim search tag and only allow for max. 30 chars
          $this->conf->search_tag = sd_substr(trim($this->conf->search_tag),0,30);
        }
      }
      if(!empty($this->conf->search_text))
      {
        $this->conf->search_text = SanitizeInputForSQLSearch($this->conf->search_text, true);
        if(strlen($this->conf->search_text))
        {
          // trim search text and only allow for max. 30 chars
          $this->conf->search_text = sd_substr(trim($this->conf->search_text),0,30);
        }
      }
    }

    $this->DisplayMenu();

    // ########################################################################
    // SD313: Check permissions for posting relacted actions right upfront!
    // ########################################################################
    $this->conf->error_arr = array();
    // In case of access denied, the "AllowSubmit" flag is set to false in
    // any case so that post/edit/quote/reply links won't show
    if(!$userinfo['loggedin'])
    {
      //$this->conf->AllowSubmit = false;
      if(!empty($this->conf->plugin_settings_arr['display_guest_header']))
      {
        $message = $this->conf->plugin_phrases_arr['message_signup_login'];
        $message = str_replace(array('[REGISTER_PATH]','[LOGIN_PATH]'), array(REGISTER_PATH,LOGIN_PATH), $message);
        DisplayMessage($this->conf->plugin_phrases_arr['err_member_access_only'].' '.$message);
      }
    }
    if(!empty($this->conf->action) && ($this->conf->action != 'display_forums'))
    {
      //SD351: added mod_* links for use from within admin
      if(!($this->conf->IsSiteAdmin || $this->conf->IsAdmin || $this->conf->IsModerator) &&
         (in_array($this->conf->action, array('forum_options','topic_options','mod_topics','mod_posts'))))
      {
        $this->conf->AllowSubmit = false;
        $this->conf->error_arr[] = $this->conf->plugin_phrases_arr['err_no_options_access'];
      }
      else
      if(!$this->conf->IsSiteAdmin && !$this->conf->IsAdmin && !$this->conf->IsModerator &&
         !$this->conf->AllowSubmit && ($this->conf->action!='search_forums'))
      {
        $this->conf->AllowSubmit = false;
        $this->conf->error_arr[] = $this->conf->plugin_phrases_arr['err_no_post_access'];
      }
    }

    // SD313: IF a forum is selected thus far AND the "access_post" is set,
    // check it against the currently logged in user's usergroup:
    if(empty($this->conf->error_arr) && !$this->conf->IsSiteAdmin && !$this->conf->IsAdmin &&
       !$this->conf->IsModerator && $this->conf->forum_id &&
       !empty($this->conf->forum_arr['access_post']))
    {
      // At this point a forum is selected AND an extra permission list exists,
      // now search in permission for usergroup id, which MUST be enclosed in pipes!
      if(!$this->conf->AllowSubmit)
      {
        // IF the user's usergroup does NOT have posting permissions AND
        // IF $this->conf->action is posting-related: display an error message
        // IF there's no other error yet set:
        if(empty($this->conf->error_arr) && !empty($this->conf->action) &&
           !in_array($this->conf->action, array('display_forums','search_forums')))
        {
          $this->conf->error_arr[] = $this->conf->plugin_phrases_arr['err_no_post_access'];
          $this->conf->action = '';
        }
      }
    }

    if(count($this->conf->error_arr))
    {
      $this->conf->RedirectPageDefault($this->conf->error_arr, true);
      return;
    }
/*
    if((empty($this->conf->action) || ($this->conf->action=='display_forums')) && !$this->conf->topic_id && !$this->conf->forum_id)
    {
      $this->DisplayForumCategories();
      return;
    }
*/
    //SD322: special action to delete a single attachment
    if(!empty($userinfo['userid']) && $post_action == 'attdel')
    {
      $this->DeleteAttachment();
      return;
    }

    if(!empty($userinfo['userid']) && $this->conf->action == 'subscribe_topic')
    {
      $this->SubscribeTopic(); //SD342
      return;
    }
    else
    if(!empty($userinfo['userid']) && $this->conf->action == 'unsubscribe_topic')
    {
      $this->UnsubscribeTopic(); //SD342
      return;
    }
    else
    if(!empty($userinfo['userid']) && $this->conf->action == 'subscribe_forum')
    {
      $this->SubscribeForum(); //SD342
      return;
    }
    else
    if(!empty($userinfo['userid']) && $this->conf->action == 'unsubscribe_forum')
    {
      $this->UnsubscribeForum(); //SD342
      return;
    }
    else
    if($this->conf->action == 'submit_topic')
    {
      $this->DisplayTopicForm();
      return;
    }
    elseif(!empty($userinfo['userid']) && ($this->conf->action == 'report_post')) //SD351
    {
      $this->DisplayReportingForm();
      return;
    }
    else
    if($this->conf->action == 'insert_topic')
    {
      $this->InsertTopic();
      return;
    }
    else
    if(($this->conf->action == 'edit_post') || ($this->conf->action == 'submit_post'))
    {
      $this->DisplayPostForm();
      return;
    }
    else
    if(($this->conf->action == 'like_post'))
    {
      $this->conf->DoLikePost();
      return;
    }
    else
    if(!empty($userinfo['userid']) && ($this->conf->action == 'report_post_confirm')) //SD351
    {
      $this->conf->DoReportPost();
      return;
    }
    else
    if($this->conf->action == 'insert_post')
    {
      $this->InsertPost();
      return;
    }
    else
    if($this->conf->action == 'update_post')
    {
      $this->UpdatePost();
      return;
    }
    else
    if(($this->conf->action == 'search_forums') || isset($_GET['newposts']))
    {
      $this->SearchForums();
      return;
    }

    if(in_array($this->conf->action, array('forum_options', 'topic_options')))
    {
      include_once(ROOT_PATH.'plugins/forum/forum.admin.php');
      if(class_exists('SDForumAdminTools'))
      {
        $forumadmin = new SDForumAdminTools($this->conf);
        if($this->conf->action == 'topic_options')
        {
          $result = $forumadmin->DoTopicOptions();
        }
        elseif($this->conf->action == 'forum_options')
        {
          $result = $forumadmin->DoForumOptions();
        }
        unset($forumadmin);
        if(isset($result)) return;
      }
    }

    if($this->conf->topic_id)
    {
      $this->DisplayTopic();
      return;
    }
    else
    if($this->conf->forum_id)
    {
      $this->DisplayForum();
      return;
    }

    $this->DisplayForumCategories();

  } //StartForum

} // end of class


// ############################################################################
// INITIALIZE CLASS AND PROPERTIES
// ############################################################################

if(!defined('IN_ADMIN'))
{
  if($categoryid == $mainsettings_search_results_page) return true;
  echo '
    <div id="forum">
    ';

  $Forum = new SDForum();
  $Forum->StartForum();

  echo '
    </div><!-- end of forum -->
    ';

  unset($Forum);
}