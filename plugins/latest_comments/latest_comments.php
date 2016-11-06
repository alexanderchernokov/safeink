<?php
if(!defined('IN_PRGM') || !function_exists('sd_GetCurrentFolder')) return false;

// ############################################################################
// "p_LC_TranslateObjectID" moved to functions_global.php!
// ############################################################################

if(!function_exists('p_DisplayLatestComments'))
{
  function p_DisplayLatestComments()
  {
    global $DB, $bbcode, $categoryid, $mainsettings_url_extension, $mainsettings_modrewrite,
           $mainsettings_url_extension, $mainsettings_allow_bbcode, $mainsettings,
           $pages_md_arr, $plugin_names, $pluginid, $sdurl, $userinfo, $usersystem;

    $currentdir = sd_GetCurrentFolder(__FILE__);
    if(!$pluginid = GetPluginIDbyFolder($currentdir))
    {
      return;
    }

    $settings       = GetPluginSettings($pluginid);
    $sources        = $settings['comment_source_plugins'];// source plugin(s) for comments
    $sources        = sd_ConvertStrToArray($sources, ',');
    if(empty($sources)) $sources=array(2,17);

    $max_items      = (int)$settings['comment_limit']; // number of comments to show
    $showusername   = !empty($settings['display_username']); // display username?
    $showdate       = !empty($settings['display_date']); // display date?
    $sourcelink     = !empty($settings['source_link']); // links to the source of the comment? (articleid etc...)
    $showcomment    = !empty($settings['display_comment']); // show the comment
    $showsource     = !empty($settings['display_source_title']); // show the title of the source
    $characterlimit = (int)$settings['limit_comment_length']; // character limit for comment
    $displayavatar  = !empty($settings['display_avatar']); // display user's avatar
    $a_width        = empty($settings['avatar_width'])  ? (int)$mainsettings['default_avatar_width']  : (int)$settings['avatar_width'];
    $a_height       = empty($settings['avatar_height']) ? (int)$mainsettings['default_avatar_height'] : (int)$settings['avatar_height'];
    $paginate       = !empty($settings['display_pagination']); // display comments paginated

    $allow_bbcode = isset($bbcode) && ($bbcode instanceof BBCode) && !empty($mainsettings_allow_bbcode);

    $DB->result_type = MYSQL_ASSOC;
    $sql = 'SELECT COUNT(*) CCOUNT FROM '.PRGM_TABLE_PREFIX.'comments WHERE approved = 1 AND pluginid IN ('.implode($sources,',').')';
    $limit = '';
    if($paginate)
    {
      if($totalcount = $DB->query_first($sql))
      {
        $totalcount = $totalcount['CCOUNT'];
      }
      $page = Is_Valid_Number(GetVar('page', 1, 'whole_number'), 1, 1);
      if($items_per_page = Is_Valid_Number($max_items, 5, 0, 1000))
        $limit = ' LIMIT '.(($page-1)*$items_per_page) . ',' . $items_per_page;
    }
    else
    {
      if($limit = Is_Valid_Number($max_items, 0, 0, 1000))
        $limit = ' LIMIT 0,'.$limit;
    }

    $DB->result_type = MYSQL_ASSOC;
    if(!$getcomments = $DB->query(str_replace('COUNT(*) CCOUNT','*',$sql) . ' ORDER BY commentid DESC' . $limit))
      return;
    if(!$count = $DB->get_num_rows($getcomments))
      return;

    echo '
    <div id="p_Latest_Comments-'.$pluginid.'" class="p_Latest_Comments">
    ';

    $IsAdmin = $userinfo['adminaccess'] ||
               (!empty($userinfo['pluginadminids']) && @in_array($pluginid, $userinfo['pluginadminids'])) ||
               (!empty($userinfo['admin_pages']) && @in_array('comments', $userinfo['admin_pages']));

    $avatar_conf = array(
      'output_ok' => $displayavatar,
      'userid'    => -1,
      'username'  => '',
      'Avatar Image Height' => $a_height,
      'Avatar Image Width'  => $a_width,
      'Avatar Column' => 0
    );

    //SD343: user caching of poster, incl. avatar
    include_once(SD_INCLUDE_PATH.'class_sd_usercache.php'); //SD343
    SDUserCache::$avatar_height = $a_height;
    SDUserCache::$avatar_width  = $a_width;
    $use_data = !SDUserCache::IsBasicUCP() && !SD_IS_BOT; //SD343

    while($comment = $DB->fetch_array($getcomments,null,MYSQL_ASSOC))
    {
      $cid = (int)$comment['commentid']; //SD370
      $pid = (int)$comment['pluginid'];
      $oid = (int)$comment['objectid'];
      $is_article = false;
      if($pid > 1)
      {
        $DB->result_type = MYSQL_ASSOC;
        $p = $DB->query_first('SELECT * FROM '.PRGM_TABLE_PREFIX.'plugins WHERE pluginid = %d',$pid);
      }

      if($sourcelink || $showsource) // get article info
      {
        if(($pid==2) || (!empty($p['base_plugin']) && ($p['base_plugin']=='Articles')))
        {
          $is_article = true;
          //SD332: check article's publishing dates and if user has
          // actually permission to view the article's category at all:
          // 2 = online
          $article = sd_cache_article($pid, $oid); //SD342 - use cache
          /*
          $article = $DB->query_first(
            'SELECT categoryid, title, seo_title, settings FROM {p'.$pid.'_news}'.
            ' WHERE (articleid = %d) AND (settings & 2) '.
            ' AND ((IFNULL(datestart,0) = 0) OR (datestart < ' . TIME_NOW . '))'.
            ' AND ((IFNULL(dateend,0)= 0) OR (dateend> ' . TIME_NOW . '))',
            $oid);
          */
          if(empty($article)) continue;
          if(empty($userinfo['categoryviewids']) || !in_array($article['categoryid'],$userinfo['categoryviewids'])) continue;
        }
      }

      //SD343: use SD's global usercache
      $poster = SDUserCache::CacheUser($comment['userid'], $comment['username'], false, $displayavatar);
      $insertuser = $use_data ? $poster['profile_link'] : $comment['username'];
      if ($userinfo['username'] == $comment['username']) {
        $styling    = 'class="backgroundcurrentuser';
        $styling2   = 'class="linkcurrentuser"';
        if($use_data) $insertuser = '<a '.$styling2.' title="' . $comment['username'].'" href="'.$poster['link'].'" rel="nofollow">'.$comment['username'].'</a>';
      } elseif (empty($comment['userid'])) {
        $styling    = 'class="backgroundnouser';
        $styling2   = 'class="linknouser"';
        if($use_data) $insertuser = '<span '.$styling2.'>' . $comment['username'].'</span>';
      } elseif (empty($userinfo['username'])) {
        $styling = 'class="backgroundguest';
        $styling2 = 'class="linkguest"';
        if($use_data) $insertuser = '<a '.$styling2.' title="' . $comment['username'].'" href="'.$poster['link'].'">' . $comment['username'].'</a>';
      } else {
        $styling    = 'class="backgroundotheruser';
        $styling2   = 'class="linkotheruser"';
        if($use_data) $insertuser = '<a '.$styling2.' title="' . $comment['username'].'" href="'.$poster['link'].'" rel="nofollow">'.$comment['username'].'</a>';
      }
      $insertuser = $showusername ? ' <strong>'.$insertuser.'</strong> ' : '';

      echo '<div '.$styling.' sdcommentdiv">';
      if(empty($settings['avatar_column']))
        echo $poster['avatar'];
      else
        echo '<div class="avatar">'.$poster['avatar'].'</div><div class="innercomment">';
      echo ' '.$insertuser;

      if($showdate)
      {
        if($showusername) echo ' - ';
        echo DisplayDate($comment['date']);
      }
      if($showusername || $showdate)
      {
        echo '<br />';
      }

      // Remove linebreaks and apply BBCode parsing
      if($showcomment)
      {
        $toreplace = array("\r\n", "\n", "\r");
        $replacewith = array(" ", " ", " ");
        $comment['comment'] = str_replace($toreplace, $replacewith, $comment['comment']);
        if($allow_bbcode)
        {
          $comment['comment'] = $bbcode->Parse($comment['comment']);
        }

        if($characterlimit && ($characterlimit>0) && (strlen($comment['comment']) > $characterlimit))
        {
          $comment['comment'] = sd_substr($comment['comment'], 0, $characterlimit) . '...';
        }
      }

      $comment_link = '';
      $title = '';
      if($is_article)
      {
        $title = $article['title'];
        $comment_link = '';
        $catid = $article['categoryid'];
        if($sourcelink)
        {
          if($mainsettings_modrewrite && strlen($article['seo_title']))
          {
            $comment_link = RewriteLink('index.php?categoryid='.$catid);
            $comment_link = preg_replace('#'.SD_QUOTED_URL_EXT.'$#', '/' . $article['seo_title'] .
                                         $mainsettings_url_extension, $comment_link);
            //SD360: must add "pid" to support cloned article plugins
            if($pid != 2)
            {
              $comment_link .= '?pid='.$pid;
            }
          }
          else
          {
            $comment_link = RewriteLink('index.php?categoryid='.$catid.'&amp;p'.$pid.'_articleid=' . $oid);
          }
        }
        if($sourcelink && $showsource) // links source title to news article
        {
          $title = '<a href="' . $comment_link . '">' . $title . '</a>';
        }
        if(!empty($settings['page_link']))
        {
          $title .= '<br /><a href="' . RewriteLink('index.php?categoryid='.$catid) . '">' .
                    $pages_md_arr[$catid]['title'].'</a>';
        }
        if($showsource)
        {
          echo $title . '<br />';
        }
      }
      else
      {
        if($getcat = $DB->query_first('SELECT DISTINCT categoryid FROM {pagesort}'.
                                      " WHERE pluginid = '%s'".
                                      ' ORDER BY categoryid ASC LIMIT 1', (int)$pid))
        {
          $pageid = (int)$getcat['categoryid'];
          if(!empty($userinfo['categoryviewids']) && @in_array($pageid,$userinfo['categoryviewids']))
          {
            // Get title of plugin's comment (if available)
            //SD370: call parameters changed for p_LC_TranslateObjectID
            if($res = p_LC_TranslateObjectID($pid, $oid, $cid, $pageid))
            {
              if(($showsource || $sourcelink) && strlen($res['title'])) // links source title to category?
              {
                $title = $res['title'];
                if($sourcelink && strlen($res['link'])) // links source title to category?
                {
                  $title = '<a href="' . $res['link'] . '">' . $title . '</a>';
                }
              }
              if(!empty($settings['page_link']) && !empty($p['displayname']))
              {
                $title .= '<br /><a href="'.
                          RewriteLink('index.php?categoryid='.$pageid).'">'.
                          $p['displayname'].'</a>';
              }
              if(strlen($title))
              {
                echo $title . '<br />';
              }
            }
            else
            if($sourcelink)
            {
              echo '<a href="'.RewriteLink('index.php?categoryid='.$pageid).'">'.
                   $plugin_names[$pid].'</a><br />';
            }
          }
        }
      }
      if($showcomment)
      {
        echo ' ' .$comment['comment'] . '<br />';
      }
      if(!empty($settings['avatar_column'])) echo '</div>';
      echo '
      <div style="clear:both"> </div></div>';
    } //while

    if(!$paginate)
    {
      echo '</div>
      ';
    }
    else
    {
      if($totalcount > $items_per_page)
      {
        $p = new pagination;
        $p->nextLabel('');
        $p->prevLabel('');
        $p->parameterName('page');
        $p->items($totalcount);
        $p->limit($items_per_page);
        $p->currentPage($page);
        $p->adjacents(1);
        $p->target(RewriteLink('index.php?categoryid=' . $categoryid));
        $p->show();
      }
      echo '</div>
      ';

      //SD342: scroll to comments container
      if(isset($_GET['page']) && Is_Valid_Number($_GET['page'],false,1))
      echo '
    <script type="text/javascript">
    //<![CDATA[
    jQuery(document).ready(function() {
      if (typeof jQuery.fn.scrollTo == "undefined") {
        jQuery.getScript("'.$sdurl.MINIFY_PREFIX_F.'includes/javascript/jquery.scrollTo-min.js", function(){
          jQuery.scrollTo("#p_Latest_Comments-'.$pluginid.'", 400);
        });
      } else {
        jQuery.scrollTo("#p_Latest_Comments-'.$pluginid.'", 400);
      }
    });
    //]]>
    </script>
    ';
    }
  } //p_DisplayLatestComments
}

p_DisplayLatestComments();
