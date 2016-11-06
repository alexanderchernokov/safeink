<?php
if(!defined('IN_PRGM')) return false;

// ############################################################################
// INSERT MESSAGE
// ############################################################################

function p4_InsertMessage($language)
{
  global $DB, $sdlanguage, $p4_settings, $p4_admin;

  //SD343: security check and honeytrap against dumb bots
  if(!CheckFormToken())
  {
    RedirectFrontPage(RewriteLink('index.php?categoryid='.PAGE_ID),'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return false;
  }

  $honeytrap = GetVar('p4_ht', '', 'string');
  if(($honeytrap!='DO NOT CHANGE') || (count($_POST)>9))
  {
    RedirectFrontPage(RewriteLink('index.php?categoryid='.PAGE_ID),'<strong>'.$sdlanguage['msg_spam_trap_triggered'].'</strong><br />',2,true);
    return false;
  }

  $messagelength  = Is_Valid_Number($p4_settings['message_length'],200,200,1024);

  $p4_message     = trim(GetVar('p4_message',     '', 'string', true, false));
  $p4_username    = trim(GetVar('p4_username',    '', 'string', true, false));
  $p4_website     = trim(GetVar('p4_website',     '', 'string', true, false));
  $p4_websitename = trim(GetVar('p4_websitename', '', 'string', true, false));

  if(strlen($p4_username) < 5) //SD343
  {
    $errors[] = $language['no_username'];
  }

  if(strlen($p4_message) < 5) //SD343
  {
    $errors[] = $language['no_message'];
  }
  else
  if(!empty($messagelength) && (strlen($p4_message) > $messagelength))
  {
    $errors[] = $language['message_too_long'].' '.$messagelength.' '.$language['characters'];
  }

  if(!$p4_admin && !CaptchaIsValid('p4'))
  {
    $errors[] = $sdlanguage['captcha_not_valid'];
  }

  if(!empty($p4_settings['prompt_website_info']))
  {
    if(!empty($p4_settings['website_info_required']) && (strlen($p4_websitename) < 3))
    {
      $errors[] = $language['no_site_name'];
    }
    else
    if(strlen($p4_websitename) > 128) //SD343
    {
      $errors[] = $language['err_too_long'].' '.$language['website_name'].' > 128 '.$language['characters'];
    }

    // get rid of any trailing slash
    if(!empty($p4_settings['website_info_required']) && (strlen($p4_website) < 3))
    {
      $errors[] = $language['url_invalid'];
    }
    else
    if(strlen($p4_website) > 3)
    {
      if(substr($p4_website, -1) == '/') $p4_website = substr($p4_website, 0, -1);

      // add http if needed
      if(substr($p4_website, 0, 3) == 'www')
      {
        $p4_website = 'http://' . $p4_website;
      }

      if(strlen($p4_website) > 128) //SD343
      {
        $errors[] = $language['err_too_long'].' '.$language['website_url'].' > 128 '.$language['characters'];
      }
      else
      if((strlen($p4_website > 128)) || !sd_check_url($p4_website)) //SD343: no "ereg" anymore
      {
        $errors[] = $language['url_invalid'];
      }
    }
  }
  else
  {
    if(strlen($p4_website) || strlen($p4_websitename))
    {
      $errors[] = $sdlanguage['msg_spam_trap_triggered'];
    }
  }

  //SD343: antispam features
  if(empty($errors))
  {
    $blacklisted = false;
    if(!empty($p4_settings['enable_sfs_antispam']) && function_exists('sd_sfs_is_spam'))
    {
      if(sd_sfs_is_spam('',USERIP))
      {
        $blacklisted = true;
      }
    }
    if(!$blacklisted && !empty($p4_settings['enable_blocklist_checks']) && function_exists('sd_reputation_check'))
    {
      $blacklisted = sd_reputation_check(USERIP, 4);
    }
    if($blacklisted !== false)
    {
      RedirectFrontPage(RewriteLink('index.php?categoryid='.PAGE_ID),$sdlanguage['ip_listed_on_blacklist'].' '.USERIP,2,true);
      return false;
    }
  }

  if(empty($errors))
  {
    // check for repeat posting
    $lastentry = $DB->query_first('SELECT username, message FROM {p4_guestbook} ORDER BY messageid DESC LIMIT 1');

    if(!empty($lastentry) && ($lastentry['username'] == $p4_username) && ($lastentry['message'] == $p4_message))
    {
      echo $language['repeat_comment'] . '<br />';
    }
    else
    {
      $DB->query("INSERT INTO {p4_guestbook} (username, websitename, website, message, datecreated, ipaddress)
                  VALUES ('%s', '%s', '%s', '%s', %d, '%s')",
                  $p4_username, $p4_websitename, $p4_website, $p4_message, TIME_NOW,(defined('USERIP')?USERIP:''));
    }

    p4_DisplayMessages($language);
    return true;
  }
  else
  {
    DisplayMessage($errors, true);
    p4_DisplayMessageForm($language);
    return false;
  }

} //p4_InsertMessage


// ############################################################################
// SUBMIT MESSAGE
// ############################################################################

function p4_DisplayMessageForm($language)
{
  global $DB, $categoryid, $userinfo, $p4_admin, $p4_settings;

  echo '<form method="post" action="' . RewriteLink('index.php?categoryid=' . $categoryid .
       '&p4_action=insertmessage') . '">
       '.PrintSecureToken(); //SD343

  echo '<table border="0" cellspacing="0" cellpadding="0" summary="Guestbook" width="100%">';

  $p4_message     = GetVar('p4_message',     '', 'string', true, false);
  $p4_username    = GetVar('p4_username',    '', 'string', true, false);
  $p4_website     = GetVar('p4_website',     '', 'string', true, false);
  $p4_websitename = GetVar('p4_websitename', '', 'string', true, false);

  echo '
    <tr>
      <td>' . $language['name'] . '</td>
      <td>';
  if(!empty($userinfo['loggedin']))
  {
    echo '<input type="hidden" name="p4_username" value="' . $userinfo['username'] . '" />'. $userinfo['username'];
  }
  else
  {
    echo '<input type="text" name="p4_username" value="'.(empty($p4_username)?'':$p4_username) . '" />';
  }
  echo '</td>
    </tr>';

  if(!empty($p4_settings['prompt_website_info']))
  {
    echo '
    <tr>
      <td width="130">' . $language['website_name'] . '</td>
      <td><input type="text" name="p4_websitename" value="' . (empty($p4_websitename)?'':$p4_websitename) . '" /></td>
    </tr>
    <tr>
      <td>' . $language['website_url'] . '</td>
      <td><input type="text" name="p4_website" value="' . (empty($p4_website)?'':$p4_website) . '" /></td>
    </tr>';
  }
  echo '
    <tr>
      <td valign="top">' . $language['message'] . '</td>
      <td>
        <textarea name="p4_message" rows="5" cols="20">' . (empty($p4_message)?'':$p4_message) . '</textarea><br />
        ' . (!empty($language['max_length_hint'])?$language['max_length_hint'].' '.(int)$p4_settings['message_length']:'').'
      </td>
    </tr>';
  if(!$p4_admin)
  {
    echo '
    <tr>
      <td></td>
      <td>';

    DisplayCaptcha(true,'p4');

    echo '
      </td>
    </tr>';
  }
  echo '
    <tr>
      <td><input type="hidden" name="p4_ht" size="1" maxlength="10" value="DO NOT CHANGE" style="display:none !important;width:0;height:0" /></td>
      <td><input type="submit" name="p4_Submit" value="' . strip_tags($language['submit_message']) . '" /></td>
    </tr>
    </table>
    </form>';

} //p4_DisplayMessageForm


// ############################################################################
// DISPLAY MESSAGES
// ############################################################################

function p4_DisplayMessages($language)
{
  global $DB, $categoryid, $sdlanguage, $userinfo, $p4_admin, $p4_mod, $p4_language, $p4_settings;

  $page = GetVar('p4_page', 1, 'whole_number');
  $items_per_page = (int)$p4_settings['messages_per_page'];
  $items_per_page = ($items_per_page < 1 ? 5 : $items_per_page);
  $limit = ' LIMIT '.(($page-1)*$items_per_page).','.$items_per_page;
  $total_rows_arr = $DB->query_first('SELECT COUNT(*) FROM {p4_guestbook}');
  $total_rows = $total_rows_arr[0];

  $wordwrap = (int)$p4_settings['word_wrap'];

  if(!empty($userinfo['adminaccess']) ||
     (!empty($userinfo['pluginsubmitids']) && @in_array(4, $userinfo['pluginsubmitids'])))
  {
    echo '<a href="' . RewriteLink('index.php?categoryid=' . $categoryid . '&p4_action=submitmessage') . '">' .
         $language['sign_guestbook'] . '</a><br /><br />';
  }
  echo '
  <div id="p4_container">
  ';

  $get_messages = $DB->query('SELECT * FROM {p4_guestbook} ORDER BY messageid DESC ' . $limit);
  while($message_arr = $DB->fetch_array($get_messages))
  {
    $username    = $message_arr['username'];
    $websitename = $message_arr['websitename'];
    $comment     = nl2br($message_arr['message']);

    if($wordwrap)
    {
      $username    = sd_wordwrap($username,    $wordwrap, "<br />", 1);
      $websitename = sd_wordwrap($websitename, $wordwrap, "<br />", 1);
      $comment     = sd_wordwrap($comment,     $wordwrap, "<br />", 1);
    }

    echo $comment . '<br /><strong>' . $username;
    if(!empty($p4_settings['show_post_date']))
    {
      echo ', ' . DisplayDate($message_arr['datecreated']);
    }

    if(!empty($p4_settings['prompt_website_info']) && !empty($message_arr['website']))
    {
      echo '<br /><a href="' . $message_arr['website'] . '" target="_blank">' . $message_arr['websitename'] . '</a>';
    }

    if(($p4_mod || $p4_admin) && !empty($p4_settings['display_delete_link']))
    {
      echo '<br /><a href="'. RewriteLink('index.php?categoryid=' . $categoryid . '&p4_id=' .
        $message_arr['messageid'] . '&p4_action=dm'.PrintSecureUrlToken()).'">'.
        $p4_language['delete_message'].'</a>';
    }

    echo '</strong>'.$p4_settings['entry_separator'];
  } //while
  echo '</div>';

  // pagination
  if($total_rows)
  {
    $p = new pagination;
    $p->items($total_rows);
    $p->limit($items_per_page);
    $p->parameterName('p4_page');
    $p->currentPage($page);
    $p->adjacents(3);
    $p->target(RewriteLink('index.php?categoryid=' . $categoryid));
    $p->show();
  }

} //p4_DisplayMessages


// #############################################################################

function p4_DeleteMessage($messageid)
{
  global $DB, $sdlanguage, $userinfo, $p4_admin, $p4_mod, $p4_language;

  if(!CheckFormToken()) //SD343
  {
    RedirectFrontPage(RewriteLink('index.php?categoryid='.PAGE_ID),'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  if(($p4_admin || $p4_mod) && !empty($messageid) && ($messageid > 0) && ($messageid < 999999))
  {
    $DB->query('DELETE FROM {p4_guestbook} WHERE messageid = %d', $messageid);
    //SD343:
    RedirectFrontPage(RewriteLink('index.php?categoryid='.PAGE_ID), $p4_language['message_deleted'], 2, false);
    return;
  }
  RedirectFrontPage(RewriteLink('index.php?categoryid='.PAGE_ID));

} //p4_DeleteMessage


// ############################################################################
// SELECT FUNCTION
// ############################################################################

$p4_admin     = !empty($userinfo['loggedin']) &&
                (!empty($userinfo['adminaccess']) ||
                 !empty($userinfo['pluginadminids']) && in_array(4, $userinfo['pluginadminids']));
$p4_mod       = !empty($userinfo['loggedin']) &&
                (!empty($userinfo['pluginmoderateids']) && @in_array(4, $userinfo['pluginmoderateids']));
$p4_canSubmit = !SD_IS_BOT &&
                !empty($userinfo['pluginsubmitids']) && in_array(4, $userinfo['pluginsubmitids']);
$p4_canView   = !empty($userinfo['pluginviewids']) && in_array(4,$userinfo['pluginviewids']);

if($p4_admin || $p4_mod || $p4_canView)
{
  $p4_action   = GetVar('p4_action', 'displaymessages', 'string');
  $p4_id       = Is_Valid_Number(GetVar('p4_id', 0, 'whole_number', false, true),0,1);
  $p4_language = GetLanguage(4);
  $p4_settings = GetPluginSettings(4);
  $p4_submitok = $p4_admin || $p4_mod || $p4_canSubmit;

  if(($p4_admin || $p4_mod) && ($p4_id>0) && ($p4_action == 'dm') && !empty($p4_settings['display_delete_link']))
  {
    p4_DeleteMessage($p4_id);
    unset($_GET['p4_action'],$_POST['p4_action']);
  }
  else
  {
    if($p4_submitok && ($p4_action == 'insertmessage'))
    {
      p4_InsertMessage($p4_language);
    }
    else if($p4_submitok && ($p4_action == 'submitmessage'))
    {
      p4_DisplayMessageForm($p4_language);
    }
    else
    {
      p4_DisplayMessages($p4_language);
    }
  }
}
unset($p4_action,$p4_admin,$p4_canSubmit,$p4_canView,$p4_id,$p4_mod,$p4_language,$p4_submitok,$p4_settings);
