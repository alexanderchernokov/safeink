<?php
if(!class_exists('SD_Likes'))
{
defined('SD_LIKED_TYPE_COMMENT') || define('SD_LIKED_TYPE_COMMENT', 'comment');
defined('SD_LIKED_TYPE_COMMENT_NO') || define('SD_LIKED_TYPE_COMMENT_NO', 'comment_no');
defined('SD_LIKED_TYPE_COMMENT_REMOVE') || define('SD_LIKED_TYPE_COMMENT_REMOVE', 'comment_remove');
defined('SD_LIKED_TYPE_POST') || define('SD_LIKED_TYPE_POST', 'post');
defined('SD_LIKED_TYPE_POST_NO') || define('SD_LIKED_TYPE_POST_NO', 'post_no');
defined('SD_LIKED_TYPE_POST_REMOVE') || define('SD_LIKED_TYPE_POST_REMOVE', 'post_remove');
class SD_Likes
{
  protected function __construct()
  {
  }

  // ##########################################################################

  public static function isSupportedLikedType($liked_type='post')
  {
    # "_no" is for dislikes!
    return !empty($liked_type) &&
           in_array($liked_type,array(SD_LIKED_TYPE_COMMENT, SD_LIKED_TYPE_COMMENT_NO,
                                      SD_LIKED_TYPE_COMMENT_REMOVE,
                                      SD_LIKED_TYPE_POST, SD_LIKED_TYPE_POST_NO,
                                      SD_LIKED_TYPE_POST_REMOVE));
  } //isSupportedLikedType

  // ##########################################################################

  public static function GetUserLikesCount($userid,$liked_type='post')
  {
    if(empty($userid) || !is_numeric($userid) || ($userid < 1)) return 0;
    if(!empty($liked_type) && !self::isSupportedLikedType($liked_type)) return 0;

    global $DB;

    $DB->ignore_error = true;
    $getcount = $DB->query_first('SELECT COUNT(id) likescount'.
                                 ' FROM {users_likes}'.
                                 ' WHERE userid = %d'.
                                 ($liked_type?" AND liked_type = '$liked_type'":''),
                                 $userid);
    $DB->ignore_error = false;
    return empty($getcount['likescount']) ? 0 : (int)$getcount['likescount'];

  } //GetUserLikesCount

  // ##########################################################################

  public static function GetUserLikedCount($liked_userid,$liked_type='post',$pluginid=0)
  {
    if(empty($liked_userid) || !is_numeric($liked_userid) || ($liked_userid < 1)) return 0;
    if(!empty($liked_type) && !self::isSupportedLikedType($liked_type)) return 0;

    global $DB;

    $DB->ignore_error = true;
    $getcount = $DB->query_first('SELECT COUNT(DISTINCT(objectid)) likesitems, COUNT(id) likescount'.
                                 ' FROM {users_likes}'.
                                 ' WHERE liked_userid = %d'.
                                 (!empty($pluginid)?' AND pluginid = '.(int)$pluginid:'').
                                 ($liked_type?" AND liked_type = '$liked_type'":''),
                                 $liked_userid);
    $DB->ignore_error = false;
    return !isset($getcount['likescount']) ? false : $getcount;

  } //GetUserLikedCount

  // ##########################################################################

  public static function GetLikesList($source,$doLikes=true) // $source must be array!
  {
    if(empty($source) || !is_array($source))
    {
      return '';
    }
    $doLikes = !empty($doLikes);
    if($doLikes && empty($source['guests']) && (empty($source['users']) || !is_array($source['users']) || !count($source['users'])))
    {
      return '';
    }
    if(!$doLikes && empty($source['guests_no']) && (empty($source['users_no']) || !is_array($source['users_no']) || !count($source['users_no'])))
    {
      return '';
    }

    $source_txt = '';
    if(!isset($source['users'])) $source['users'] = array();
    if(!isset($source['users_no'])) $source['users_no'] = array();
    if(!isset($source['total'])) $source['total'] = 0;
    if(!isset($source['total_no'])) $source['total_no'] = 0;
    if(!isset($source['guests'])) $source['guests'] = 0; //SD370
    if(!isset($source['guests_no'])) $source['guests_no'] = 0; //SD370
    if($doLikes)
    {
      $ucount = count($source['users']) + $source['guests'];
      $total_count = empty($source['total']) ? $ucount : (int)$source['total'];
      $src = $source['users'];
    }
    else
    {
      $ucount = count($source['users_no']) + $source['guests_no'];
      $total_count = empty($source['total_no']) ? $ucount : (int)$source['total_no'];
      $src = $source['users_no'];
    }
    if($total_count < $ucount) $total_count = $ucount;
    if($ucount)
    {
      global $userinfo, $sdlanguage;
      require_once(SD_INCLUDE_PATH.'class_sd_usercache.php');
      $cnt = 0;
      foreach($src as $uid => $uname)
      {
        # don't display profile link for and to any guests
        if(!empty($uid))
        {
          if(!empty($userinfo['loggedin']) && !empty($userinfo['userid']))
          {
            $cacheduser = SDUserCache::CacheUser($uid,'',false);
            $source_txt .= ' '.$cacheduser['profile_link'];
          }
          else
          {
            $source_txt .= ' '.$uname;
          }
          $cnt++;
          if(($total_count==1) || ($cnt >= 3)) break;
          if(($total_count > 3) || (($total_count == 3) && ($cnt==1)))
            $source_txt .= ', ';
          else
            $source_txt .= ' ' . $sdlanguage['likes_and'];
        }
      }
      $like_phrase_1 = $doLikes ? $sdlanguage['likes_like_this'] : $sdlanguage['likes_dislike_this'];
      if($cnt)
      {
        $source_txt = trim($source_txt);
        $source_txt = rtrim($source_txt, ',');
        if(substr($source_txt, -strlen($sdlanguage['likes_and']))==$sdlanguage['likes_and'])
        {
          $source_txt = substr($source_txt, 0,strlen($source_txt)-strlen($sdlanguage['likes_and']));
        }
      }

      //SD370: guests are listed separately
      if(!empty($source['guests']) && $doLikes)
      {
        $source_txt .= ($cnt?', ':'').$source['guests'].' ';
        if($source['guests']==1)
          $source_txt .= (isset($sdlanguage['likes_guest_name'])?$sdlanguage['likes_guest_name']:'Guest');
        else
          $source_txt .= (isset($sdlanguage['likes_guests_name'])?$sdlanguage['likes_guests_name']:'Guests');
      }
      else
      if(!empty($source['guests_no']) && !$doLikes)
      {
        $source_txt .= ($cnt?', ':'').$source['guests_no'].' ';
        if($source['guests_no']==1)
          $source_txt .= (isset($sdlanguage['likes_guest_name'])?$sdlanguage['likes_guest_name']:'Guest');
        else
          $source_txt .= (isset($sdlanguage['likes_guests_name'])?$sdlanguage['likes_guests_name']:'Guests');
      }

      if($total_count == 1)
        $source_txt .= ' '.$sdlanguage[$doLikes ? 'likes_likes_this' : 'likes_dislikes_this'];
      else
      if($total_count <= 3)
        $source_txt .= ' '.$like_phrase_1;
      else
      if($total_count == 4)
        $source_txt .= ' '.$sdlanguage['likes_and'].' '.
                $sdlanguage['likes_one_other'].' '.
                $like_phrase_1;
      else
      if($total_count >= 5)
        $source_txt .= ' '.$sdlanguage['likes_and'].' '.
                ($total_count - 3).' '.
                $sdlanguage['likes_others'].' '.
                $like_phrase_1;
      else
        $source_txt .= ' '.$like_phrase_1;
    }

    return $source_txt;

  } //GetLikesList

  // ##########################################################################

  public static function DoLikeElement($pluginid, $objectid, $liked_type,
                                       $userid, $username, $liked_userid=0)
  {
    //SD370: allow Guests (not logged in)
    if(empty($pluginid) || empty($objectid) || empty($liked_type))
    {
      return false;
    }
    if(empty($liked_type) || !self::isSupportedLikedType($liked_type)) return false;

    global $DB, $sdlanguage;

    $entries = array('users'     => array(),
                     'users_no'  => array(), # dislikes
                     'guests'    => 0,       # SD370: guests likes
                     'guests_no' => 0,       # SD370: guests dislikes
                     'total'     => 0,
                     'total_no'  => 0        # dislikes amount
                    );
    $is_dislike = in_array($liked_type,array(SD_LIKED_TYPE_POST_NO,SD_LIKED_TYPE_COMMENT_NO));
    $is_remove  = in_array($liked_type,array(SD_LIKED_TYPE_POST_REMOVE,SD_LIKED_TYPE_COMMENT_REMOVE));
    if(in_array($liked_type,array(SD_LIKED_TYPE_POST,SD_LIKED_TYPE_POST_NO,SD_LIKED_TYPE_POST_REMOVE)))
    {
      $liked_type_normal = SD_LIKED_TYPE_POST;
      $liked_type_no     = SD_LIKED_TYPE_POST_NO;
    }
    else
    if(in_array($liked_type,array(SD_LIKED_TYPE_COMMENT,SD_LIKED_TYPE_COMMENT_NO,SD_LIKED_TYPE_COMMENT_REMOVE)))
    {
      $liked_type_normal = SD_LIKED_TYPE_COMMENT;
      $liked_type_no     = SD_LIKED_TYPE_COMMENT_NO;
    }
    else return false; //SD370: return

    //SD370: use special phrase for guests
    if(empty($userid) || empty($username) || ($userid < 1))
    {
      $username = (isset($sdlanguage['likes_guest_name'])?$sdlanguage['likes_guest_name']:'Guest');
    }

    $exists = false;
    $exists_dislike = false;
    $del_like = false;
    $del_dislike = false;
    $total = 0;
    $total_dislike = 0;
    if($getlikes = $DB->query('SELECT u.*, count(*) clikes FROM {users_likes} u'.
                              ' WHERE u.pluginid = %d AND u.objectid = %d '.
                              " AND u.liked_type IN ('%s', '%s')".
                              ' GROUP BY u.pluginid, u.objectid, u.userid, u.ip_address'.
                              ' ORDER BY u.liked_type, u.liked_date',
                              $pluginid, $objectid, $liked_type_normal, $liked_type_no))
    {
      while($entry = $DB->fetch_array($getlikes,null,MYSQL_ASSOC))
      {
        //SD370: count guests extra
        if(empty($entry['userid']))
        {
          if($entry['liked_type'] == $liked_type_normal)
          {
            $entries['guests'] += (int)$entry['clikes'];
            $total += (int)$entry['clikes'];
          }
          else
          {
            $entries['guests_no'] += (int)$entry['clikes'];
            $total_dislike += (int)$entry['clikes'];
          }
        }

        // does dis-/like already exist?
        if( (empty($entry['userid']) && empty($userid) && ($entry['ip_address'] == IPADDRESS)) ||
            (!empty($userid) && ($entry['userid'] == $userid)) )
        {
          if($entry['liked_type'] == $liked_type_normal)
            $exists = true;
          else
            $exists_dislike = true;
        }

        # note: max. stored usernames is 3!
        if($entry['liked_type'] == $liked_type_normal)
        {
          if( ($is_remove || $is_dislike) &&
              ( (!empty($userid) && ($entry['userid'] == $userid)) ||
                (empty($userid) && ($entry['userid'] == $userid) && ($entry['ip_address'] == IPADDRESS)) )
            )
          {
            // dislike means to remove existing user from likes list,
            // so don't do anything here
            $del_like = true;
          }
          else
          if(!empty($entry['userid']))
          {
            if($total < 3) $entries['users'][$entry['userid']] = $entry['username'];
            $total++;
          }
        }
        else
        {
          if((!$is_dislike || $is_remove) &&
              ( (!empty($userid) && ($entry['userid'] == $userid)) ||
                (empty($userid) && ($entry['userid'] == $userid) && ($entry['ip_address'] == IPADDRESS)) )
            )
          {
            // like means to remove existing user from dislikes list,
            // so don't do anything here
            $del_dislike = true;
          }
          else
          if(!empty($entry['userid']))
          {
            if($total_dislike < 3) $entries['users_no'][$entry['userid']] = $entry['username'];
            $total_dislike++;
          }
        }
      }
    }

    //SD370: for guests also use ip address
    $extra = '';
    if(empty($userid))
    {
      $extra = " AND ip_address = '".$DB->escape_string(IPADDRESS)."'";
    }

    // Check what actions are to be performed on likes table
    if(!$is_remove && !$is_dislike && !$exists && !$del_like)
    {
      if(empty($userid))
        $entries['guests']++;
      else
        if($total < 3) $entries['users'][$userid] = $username;
      $entries['total'] = 1 + $total;
      $DB->query('INSERT INTO {users_likes}(pluginid,objectid,liked_type,userid,username,liked_date,liked_userid,ip_address)'.
                 " VALUES(%d, %d, '%s', %d, '%s', %d, %d, '%s')",
                 $pluginid, $objectid, $DB->escape_string($liked_type), $userid,
                 $DB->escape_string($username), TIME_NOW, $liked_userid, IPADDRESS);
    }
    else
    if($exists && $del_like)
    {
      $entries['total'] = $total;
      //note: "total" does not count guests!
      if(empty($userid))
      {
        if($entries['guests'] > 0) $entries['guests']--;
      }
      else
      {
        if($total > 0) $total--;
        $entries['total'] = ($total < count($entries['users']) ? count($entries['users']) : $total);
      }
      $DB->query('DELETE FROM {users_likes}'.
                 " WHERE pluginid = %d AND objectid = %d AND liked_type = '%s' AND userid = %d".$extra,
                 $pluginid, $objectid, $DB->escape_string($liked_type_normal), $userid);
    }
    else
    {
      $entries['total'] = $total;
    }

    if(!$is_remove && $is_dislike && !$exists_dislike && !$del_dislike)
    {
      if(empty($userid))
        $entries['guests_no']++;
      else
        if($total_dislike < 3) $entries['users_no'][$userid] = $username;
      $entries['total_no'] = 1 + $total_dislike;
      $DB->query('INSERT INTO {users_likes}(pluginid,objectid,liked_type,userid,username,liked_date,liked_userid,ip_address)'.
                 " VALUES(%d, %d, '%s', %d, '%s', %d, %d, '%s')",
                 $pluginid, $objectid, $DB->escape_string($liked_type_no), $userid,
                 $DB->escape_string($username), TIME_NOW, $liked_userid, IPADDRESS);
    }
    else
    if($exists_dislike && $del_dislike)
    {
      $entries['total_no'] = $total_dislike;
      //note: "total_no" does not count guests!
      if(empty($userid))
      {
        if($entries['guests_no'] > 0) $entries['guests_no']--;
      }
      else
      {
        if($total_dislike > 0) $total_dislike--;
        $entries['total_no'] = ($total_dislike < count($entries['users_no']) ? count($entries['users_no']) : $total_dislike);
      }
      $DB->query('DELETE FROM {users_likes}'.
                 " WHERE pluginid = %d AND objectid = %d AND liked_type = '%s' AND userid = %d".$extra,
                 $pluginid, $objectid, $DB->escape_string($liked_type_no), $userid);
    }
    else
    {
      $entries['total_no'] = $total_dislike;
    }

    return $entries;

  } //DoLikeElement

  // ##########################################################################

  public static function RemoveLikesForObject($pluginid,$objectid,$liked_type)
  {
    if(empty($pluginid) || empty($objectid)) return false;
    $pluginid = Is_Valid_Number($pluginid, 0, 2, 99999);
    $objectid = Is_Valid_Number($objectid, 0, 1, 999999999);
    if(empty($pluginid) || empty($objectid)) return false;
    if(!empty($liked_type) && !self::isSupportedLikedType($liked_type)) return false;

    global $DB;

    $DB->ignore_error = true;
    $DB->query('DELETE FROM {users_likes} WHERE pluginid = %d AND objectid = %d'.
               ($liked_type?" AND liked_type = '".$DB->escape_string($liked_type)."'":''),
               $pluginid, $objectid);
    $DB->ignore_error = false;

  } //RemoveLikesForObject


  // ##########################################################################

  public static function RemoveUserLikesForObject($pluginid,$objectid,$userid)
  {
    if(empty($userid) || empty($pluginid) || empty($objectid)) return false;
    $pluginid = Is_Valid_Number($pluginid, 0, 2, 99999);
    $objectid = Is_Valid_Number($objectid, 0, 1, 999999999);
    if(($userid < 1) || empty($pluginid) || empty($objectid)) return false;

    global $DB;

    $DB->ignore_error = true;
    $DB->query('DELETE FROM {users_likes}'.
               ' WHERE pluginid = %d AND objectid = %d AND userid = %d',
               $pluginid, $objectid, $userid);
    $DB->ignore_error = false;

  } //RemoveUserLikesForObject

} //END OF CLASS
} //DO NOT REMOVE
