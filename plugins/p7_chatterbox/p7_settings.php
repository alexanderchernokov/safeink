<?php

if(!defined('IN_SUBDREAMER') || !defined('IN_ADMIN')) exit();

// ############################################################################
// DISPLAY MENU
// ############################################################################

echo '<ul id="contentnavigation">
    <li><a href="'.$refreshpage.'&action=display_chatterbox_messages" class="menu_item">'.AdminPhrase('chatterbox_view_messages').'</a></li>
    <li><a href="'.$refreshpage.'&action=display_chatterbox_settings" class="menu_item">'.AdminPhrase('chatterbox_view_settings').'</a></li>
  </ul>
  <div class="clear"></div>';


// ############################################################################
// DISPLAY SETTINGS
// ############################################################################

function DisplayChatterboxSettings()
{
  global $DB, $pluginid;

  $refreshpage = '';

  PrintPluginSettings($pluginid, 'Options', $refreshpage);

} //DisplayChatterboxSettings


// ############################################################################
// DISPLAY MESSAGES
// ############################################################################

function DisplayChatterboxMessages()
{
  global $DB, $refreshpage;

  $page = GetVar('page', 1, 'whole_number');
  $items_per_page = 10;
  $limit = ' LIMIT '.(($page-1)*$items_per_page).','.$items_per_page;
  $total_rows_arr = $DB->query_first('SELECT count(*) value FROM {p7_chatterbox}');
  $total_rows = $total_rows_arr['value'];

  $get_messages = $DB->query('SELECT commentid, categoryid, username, comment, datecreated, ipaddress
                              FROM {p7_chatterbox}
                              ORDER BY categoryid, datecreated DESC' . $limit);
  $message_rows = $DB->get_num_rows($get_messages);

  $get_categories  = $DB->query('SELECT categoryid, name FROM {categories}');
  $categoryies_arr = array();

  while($category_arr = $DB->fetch_array($get_categories))
  {
    $categoryies_arr[$category_arr['categoryid']] = $category_arr['name'];
  }

  $admin_phrases = LoadAdminPhrases(2,7);

  if(!$message_rows)
  {
    DisplayMessage(AdminPhrase('chatterbox_no_messages'));
  }
  else
  {
    echo '<form method="post" action="' . $refreshpage . '">
          <input type="hidden" name="action" value="update_chatterbox_messages" />
          ' . PrintSecureToken();

    StartSection('Chatterbox '.$admin_phrases['common_messages']);
    echo '<table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td class="td1">'.$admin_phrases['common_username'].'</td>
            <td class="td1">'.$admin_phrases['common_category'].'</td>
            <td class="td1">'.$admin_phrases['common_date_posted'].'</td>
            <td class="td1">'.$admin_phrases['common_messages'].'</td>
            <td class="td1">'.$admin_phrases['chatterbox_ip_address'].'</td>
            <td class="td1">'.$admin_phrases['common_delete'].'</td>
          </tr>';

    while($message_arr = $DB->fetch_array($get_messages))
    {
      echo '<tr>
              <td class="td2">' . $message_arr['username'] . '</td>
              <td class="td2">';

      if(isset($categoryies_arr[$message_arr['categoryid']]))
      {
        echo $categoryies_arr[$message_arr['categoryid']];
      }
      else
      {
        echo $message_arr['categoryid'];
      }

      echo '  </td>
              <td class="td2">' . DisplayDate($message_arr['datecreated']) . '</td>
              <td class="td3">
                <input type="hidden" name="comment_id_arr[]" value="' . $message_arr['commentid'] . '" />
                <input type="text" name="comment_arr[]" value="' . $message_arr['comment'] . '" size="35" style="width: 95%" />
              </td>
              <td class="td2">' . $message_arr['ipaddress'] . '</td>
              <td class="td3"><input type="checkbox" name="delete_comment_id_arr[]" value="' . $message_arr['commentid'] . '" /></td>
            </tr>';
    }

    echo '</table>';
    EndSection();

    echo '<center><input type="submit" value="'.strip_tags($admin_phrases['chatterbox_update_messages']).'" class="input_submit" /></center>
          </form>';
  }

  // pagination
  $p = new pagination;
  $p->items($total_rows);
  $p->limit($items_per_page);
  $p->currentPage($page);
  $p->adjacents(3);
  $p->target($refreshpage);
  $p->show();

} //DisplayChatterboxMessages


// ############################################################################
// UPDATE MESSAGES
// ############################################################################

function UpdateChatterboxMessages()
{
  global $DB, $refreshpage, $sdlanguage;

  // SD320: security check against spam/bot submissions
  if(!CheckFormToken())
  {
    RedirectPage($refreshpage, '<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }
  $comment_id_arr        = GetVar('comment_id_arr', '', 'array');
  $comment_arr           = GetVar('comment_arr', '', 'array');
  $delete_comment_id_arr = GetVar('delete_comment_id_arr', null, 'array');
  $page                  = GetVar('page', 1, 'natural_number');

  // first update comments
  for($i = 0; $i < count($comment_id_arr); $i++)
  {
    if(empty($delete_comment_id_arr) || !@in_array($comment_id_arr[$i], $delete_comment_id_arr))
    {
      $DB->query("UPDATE {p7_chatterbox} SET comment = '%s' WHERE commentid = %d",
                 $comment_arr[$i], (int)$comment_id_arr[$i]);
    }
  }

  // now delete comments (if user selected to delete any)
  if(!empty($delete_comment_id_arr))
  {
    $DB->query('DELETE FROM {p7_chatterbox} WHERE commentid IN (' . implode(',',$delete_comment_id_arr) . ')');
  }

  $admin_phrases = LoadAdminPhrases(2,7);
  RedirectPage($refreshpage . '&action=display_chatterbox_messages&page=' . $page, $admin_phrases['chatterbox_messages_updated']);

} //UpdateChatterboxMessages


// ############################################################################
// GET ACTION
// ############################################################################

$action = GetVar('action', 'display_chatterbox_messages', 'string');

$function_name = str_replace('_', '', $action);

if(is_callable($function_name))
{
  call_user_func($function_name);
}
else
{
  DisplayMessage("Incorrect Function Call: $function_name()", true);
}

?>