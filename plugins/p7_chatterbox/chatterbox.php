<?php

if(!defined('IN_PRGM')) exit;

// ############################################################################
// INSERT MESSAGE
// ############################################################################

function p7_InsertMessage()
{
  global $DB, $categoryid, $mainsettings, $sdlanguage, $userinfo,
         $chatterbox_language, $chatterbox_settings;

  sleep(1); #distraction

  // SD320: security check against spam/bot submissions
  if(!CheckFormToken())
  {
    sleep(1);
    RedirectFrontPage(RewriteLink('index.php?categoryid='.PAGE_ID),$sdlanguage['error_invalid_token'],2,true);
    return;
  }
  //SD343: honeytrap against dumb bots
  $honeytrap = GetVar('p7_website','','string');
  if(($honeytrap!='DO NOT CHANGE') || (count($_POST)>9))
  {
    sleep(3);
    RedirectFrontPage(RewriteLink('index.php?categoryid='.PAGE_ID),$sdlanguage['ip_listed_on_blacklist'],2,true);
    return false;
  }

  $errors   = array();
  $username = ($userinfo['loggedin'] ? $userinfo['username'] : GetVar('p7_username','','string',true,false));
  $comment  = GetVar('p7_comment','','string',true,false);
  if(strlen($comment))
  {
    $comment = preg_replace(array("/\r/", "/\n/"), array(' ',' '), $comment);
  }

  // first check if the user entered a username and comment
  if(!strlen($username))
  {
    $errors[] = $chatterbox_language['no_username'];
  }
  else
  if(!strlen($comment))
  {
    $errors[] = $chatterbox_language['no_comment'];
  }

  // SD322: Check if either VVC or reCaptcha is correct
  if(!empty($chatterbox_settings['require_captcha']))
  {
    if(!CaptchaIsValid('p7'))
    {
      $errors[] = $sdlanguage['captcha_not_valid'];
    }
  }

  //SD343: antispam features
  if(empty($errors))
  {
    $blacklisted = false;
    if(!empty($chatterbox_settings['enable_sfs_antispam']) && function_exists('sd_sfs_is_spam'))
    {
      if(sd_sfs_is_spam('',USERIP))
      {
        $blacklisted = true;
      }
    }
    if(!$blacklisted && !empty($chatterbox_settings['enable_blocklist_checks']) && function_exists('sd_reputation_check'))
    {
      $blacklisted = sd_reputation_check(USERIP, 4);
    }
    if($blacklisted !== false)
    {
      sleep(2);
      RedirectFrontPage(RewriteLink('index.php?categoryid='.PAGE_ID),$sdlanguage['ip_listed_on_blacklist'].' '.USERIP,2,true);
      return false;
    }
  }

  // if a username and message are entered then check if it's a repeat comment
  // this isn't done by default to save a query, pluse we don't want to check
  // the database for a record that doesn't have a username or comment
  if(empty($errors))
  {
    $max_length = Is_Valid_Number($chatterbox_settings['maximum_comment_length'],50,40,9999);
    $comment = sd_substr($comment, 0, $max_length);
    // we don't want to match everything because the same user might have said
    // 'hi' last month so lets just check agains the last comment entered into
    // the database
    if($lastentry = $DB->query_first('SELECT username, comment FROM {p7_chatterbox}
      WHERE categoryid = %d ORDER BY commentid DESC LIMIT 1', $categoryid))
    {
      if(($lastentry['username'] == $username) && ($lastentry['comment'] == $comment))
      {
        $errors[] = $chatterbox_language['repeat_message'];
      }
    }
  }

  // if there are no errors, then submit the new message to the database
  if(empty($errors))
  {
    if(empty($userinfo['adminaccess']) && !empty($chatterbox_settings['censor_messages']))
    {
      $comment = sd_censor($comment);
    }
    //SD342: added userid, ipaddress
    $DB->query("INSERT INTO {p7_chatterbox} (categoryid, username, comment, datecreated, userid, ipaddress)
                VALUES (%d, '%s', '%s', %d, %d, '%s')",
                $categoryid, $DB->escape_string($username), $comment, TIME_NOW, $userinfo['userid'], IPADDRESS);
  }

  // display the errors along with the chatterbox
  p7_DisplayMessages((isset($errors)?$errors:null));

} //p7_InsertMessage


// ############################################################################
// DISPLAY MESSAGES
// ############################################################################

function p7_DisplayMessages($errors = null)
{
  global $DB, $mainsettings, $sdurl, $userinfo, $categoryid, $inputsize, $sdlanguage,
         $chatterbox_cansubmit, $chatterbox_language, $chatterbox_settings;

  $printavatar = !empty($chatterbox_settings['display_avatar']);
  if($printavatar)
  {
    $img_h = Is_Valid_Number($chatterbox_settings['avatar_image_height'], 40, 5, 500);
    $img_w = Is_Valid_Number($chatterbox_settings['avatar_image_width'], 40, 5, 500);
    if(!empty($img_h) & ($img_h > 1))
    {
      $chatterbox_settings['avatar_image_height'] = $img_h;
    }
    else
    {
      $printavatar = false;
    }

    if(!empty($img_w) && ($img_w > 1))
    {
      $chatterbox_settings['avatar_image_width'] = $img_w;
    }
    else
    {
      $printavatar = false;
    }
  }

  // Config array as parameter for sd_PrintAvatar (in globalfunctions.php)
  $avatar_conf = array(
    'output_ok'           => $printavatar,
    'userid'              => -1,
    'username'            => '',
    'Avatar Image Height' => $chatterbox_settings['avatar_image_height'],
    'Avatar Image Width'  => $chatterbox_settings['avatar_image_width'],
    'Avatar Column'       => 0
    );

  SDUserCache::$avatar_height = $chatterbox_settings['avatar_image_height'];
  SDUserCache::$avatar_width  = $chatterbox_settings['avatar_image_width'];

  echo '<div id="chatterbox">';
  // display messages
  $maxcomments = empty($chatterbox_settings['number_of_comments_to_display']) ? 5 : (int)$chatterbox_settings['number_of_comments_to_display'];
  $getmessages = $DB->query('SELECT username, comment, datecreated, commentid, userid FROM {p7_chatterbox} ' .
                 (!empty($chatterbox_settings['category_targeting']) ? ' WHERE categoryid = '.(int)$categoryid : '') .
                 ' ORDER BY commentid DESC LIMIT 0, %d', $maxcomments);

  while($message = $DB->fetch_array($getmessages,null,MYSQL_ASSOC))
  {
    $username = $message['username'];
    $userid   = isset($message['userid'])?(int)$message['userid']:-1;
    $comment  = $message['comment'];

    $avatar_conf['userid'] = $userid;
    $avatar_conf['username'] = $username;

    if($chatterbox_settings['word_wrap'])
    {
      $username = sd_wordwrap($username, $chatterbox_settings['word_wrap'], '<br />', true);
      $comment  = sd_wordwrap($comment,  $chatterbox_settings['word_wrap'], '<br />', true);
    }

    //SD343: use user cache
    $tmp = SDUserCache::CacheUser($userid, $username, false, true);
    echo $tmp['avatar'] . ' ' . $tmp['profile_link'] . '<br />';

    if($chatterbox_settings['display_date'] && !empty($message['datecreated']))
    {
      if($chatterbox_settings['time_format'])
      {
        echo DisplayDate($message['datecreated'], $chatterbox_settings['time_format']);
      }
      else
      {
        echo DisplayDate($message['datecreated']);
      }

      echo '<br />';
    }

    echo $comment . '<div style="clear:both;height:2px;margin-bottom:8px;border:none;border-bottom: 1px solid #ddd"></div>';
  }


  // display submit field
  if($chatterbox_cansubmit)
  {
    echo '
      <form action="' . RewriteLink() . '" method="post" id="p7_chatterbox">
      <input type="hidden" name="p7_submit" value="1" />
      '. PrintSecureToken();

    if($userinfo['loggedin'])
    {
      echo '
      <input type="hidden" name="p7_username" value="' . $userinfo['username'] . '" />
      ';
    }
    else
    {
      echo $chatterbox_language['name'] . '<br />
      <input type="text" name="p7_username" value="" maxlength="' . $chatterbox_settings['maximum_username_length'] . '" />
      <br />';
    }
    $max_length = $chatterbox_settings['maximum_comment_length'];
    $max_length = empty($max_length) ? 50 : intval($max_length);

    //SD343: "p7_website" is honeytrap!
    echo $chatterbox_language['comment'] . '<br />
         <input type="hidden" name="p7_website" value="DO NOT CHANGE" maxlength="3" style="display:none !important;width:0;height:0;" />
         <input type="text" name="p7_comment"  maxlength="' . $max_length . '" value="" />';

    // SD322: captcha row
    if(!empty($chatterbox_settings['require_captcha']))
    {
      echo '<br />';
      DisplayCaptcha(true, 'p7');
    }

    echo '
          <br /><input type="submit" value="' . strip_tags($chatterbox_language['say']) . '" />
          </form>';
  }
  else
  {
    echo $sdlanguage['no_post_access'] . '<br /><br />';
  }

  if(!empty($errors))
  {
    foreach($errors as $key => $value)
    {
      echo "<strong>$value</strong><br />";
    }
  }

  if(!empty($chatterbox_settings['chatterbox_history']))
  {
    echo '
    <script type="text/javascript">
    <!--
      function p7_ViewHistory()
      {
        window.open("' . SITE_URL . 'plugins/p7_chatterbox/viewhistory.php?categoryid='.
        $categoryid.'", "", "width=300,height=500,resizable=yes,scrollbars=yes");
      }
      document.write("<a href=\"#\" onclick=\"p7_ViewHistory();return false;\">' . $chatterbox_language['view_history'] . '</a>");
    //-->
    </script>
    <noscript>
    <p><a href="#" onclick="p7_ViewHistory();return false;">' . $chatterbox_language['view_history'] . '</a></p>
    </noscript>
    ';
  }

  echo '<br />
  </div>
  ';

} //p7_DisplayMessages


// ############################################################################
// SELECT FUNCTION
// ############################################################################

#if($pluginid = GetPluginID('Chatterbox'))
$pluginid = 7;
{
  $chatterbox_language = GetLanguage($pluginid);
  $chatterbox_settings = GetPluginSettings($pluginid);
  $chatterbox_cansubmit = !empty($userinfo['adminaccess']) ||
                          (!empty($userinfo['pluginadminids']) && @in_array($pluginid, $userinfo['pluginadminids'])) ||
                          (!empty($userinfo['pluginsubmitids']) && @in_array($pluginid, $userinfo['pluginsubmitids']));

  if(!empty($_POST['p7_submit']) && $chatterbox_cansubmit)
  {
    p7_InsertMessage();
  }
  else
  {
    p7_DisplayMessages();
  }

  unset($chatterbox_language, $chatterbox_settings, $chatterbox_cansubmit);
}
