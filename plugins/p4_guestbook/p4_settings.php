<?php

if(!defined('IN_PRGM') || !defined('IN_ADMIN')) exit();

// ############################################################################
// GET ACTION
// ############################################################################

$action = GetVar('action', 'display_guestbook_messages', 'string');


// ############################################################################
// DISPLAY MENU
// ############################################################################

echo '<div class="no-margin-left">
		<a class="btn btn-info" href="' . $refreshpage . '&action=display_guestbook_messages"><i class="ace-icon fa fa-search"></i> '.AdminPhrase('guestbook_view_messages').'</a>
    &nbsp;<a class="btn btn-info" href="' . $refreshpage . '&action=display_guestbook_settings"><i class="ace-icon fa fa-cog"></i> '.AdminPhrase('guestbook_settings').'</a>
  <div class="clearfix"></div><div class="space-4"></div>';


// ############################################################################
// UPDATE GUESTBOOK MESSAGES
// ############################################################################

function UpdateGuestbookMessages()
{
  global $DB, $refreshpage, $sdlanguage;

  // SD320: security check against spam/bot submissions
  if(!CheckFormToken())
  {
    RedirectPage($refreshpage, '<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }
  $page            = GetVar('page', 1, 'int');
  $messageid       = GetVar('messageid', array(), 'array');
  $username        = GetVar('username', array(), 'array');
  $websitename     = GetVar('websitename', array(), 'array');
  $website         = GetVar('website', array(), 'array');
  $message         = GetVar('message', array(), 'array');
  $deletemessageid = GetVar('deletemessageid', array(), 'array');

  for($i = 0, $mc = count($messageid); $i < $mc; $i++)
  {
    $DB->query("UPDATE {p4_guestbook} SET username    = '%s',
                                          websitename = '%s',
                                          website     = '%s',
                                          message     = '%s'
                                      WHERE messageid = %d",
               $username[$i], $websitename[$i], $website[$i], $message[$i], $messageid[$i]);
  }

  if(!empty($deletemessageid))
  {
    for($i = 0; $i < count($deletemessageid); $i++)
    {
      $DB->query('DELETE FROM {p4_guestbook} WHERE messageid = %d', (int)$deletemessageid[$i]);
    }
  }

  $admin_phrases = LoadAdminPhrases(2,4);
  RedirectPage($refreshpage . '&action=display_guestbook_messages&page=' . $page, $admin_phrases['guestbook_messages_updated']);

} //UpdateGuestbookMessages


// ############################################################################
// DISPLAY GUESTBOOK MESSAGES
// ############################################################################

function DisplayGuestbookMessages()
{
  global $DB, $refreshpage, $sdlanguage;

  $items_per_page = 5;
  $page = GetVar('p4_page', 1, 'int');
  $limit = ' LIMIT '.(($page-1)*$items_per_page).",$items_per_page";

  $total_rows_arr = $DB->query_first('SELECT count(*) value FROM {p4_guestbook}');
  $total_rows = $total_rows_arr['value'];

  $getmessages = $DB->query('SELECT * FROM {p4_guestbook} ORDER BY datecreated DESC ' . $limit);
  $messagescount = $DB->get_num_rows($getmessages);

  $admin_phrases = LoadAdminPhrases(2,4);

  if(empty($messagescount))
  {
    DisplayMessage(iif($page > 1, str_replace('%d', $page, AdminPhrase('guestbook_no_messages_long')),
                       AdminPhrase('guestbook_no_messages_short')));
  }
  else
  {
    echo '<form method="post" action="'.$refreshpage.'&action=update_guestbook_messages&page=' . $page . '" name="deletemessages">
    '.PrintSecureToken();

    echo '<div class="table-header">Guestbook '.$admin_phrases['common_messages'] . '</div>';
    echo '<table class="table table-bordered table-striped">
		<thead>
          <tr>
            <th class="td1">'.$admin_phrases['common_username'].'</th>
            <th class="td1">'.$admin_phrases['common_website_name'].'</th>
            <th class="td1">'.$admin_phrases['common_website_url'].'</th>
            <th class="td1">'.$admin_phrases['ip_address'].'</th>
            <th class="td1">'.$admin_phrases['common_messages'].'</th>
            <th class="td1" width="75"><input type="checkbox" class="ace" checkall="group" onclick="javascript: return select_deselectAll (\'deletemessages\', this, \'group\');"><span class="lbl"> '.$admin_phrases['common_delete'].'</span></th>
          </tr>
		  </thead>
		  <tbody>';

    while($message = $DB->fetch_array($getmessages))
    {
      echo '
        <tr>
          <td class="td3">
            <input type="hidden" name="messageid[]" value="'.$message['messageid'].'" />
            <input type="text" name="username[]" value="'.CleanFormValue($message['username']).'" />
          </td>
          <td class="td3">
            <input type="text" name="websitename[]" value="'.CleanFormValue($message['websitename']).'" />
          </td>
          <td class="td3">
            <input type="text" name="website[]" size="30" value="'.CleanFormValue($message['website']).'" />
          </td>
          <td class="td3">'.$message['ipaddress'].'</td>
          <td class="td3">
            <textarea name="message[]" cols="25" rows="4">'.CleanFormValue($message['message']).'</textarea>
          </td>
          <td class="td3">
            <input type="checkbox" name="deletemessageid[]" value="' . $message['messageid'] . '" checkme="group" />
          </td>
        </tr>';
    }

    $DB->free_result($getmessages);

    echo '  </td>
          </tr>
		  </tbody>
          </table>';


    echo '<center><input class="btn btn-info" type="submit" value="'.strip_tags($admin_phrases['guestbook_update_messages']).'" /></center>
          </form>';
  }

  // pagination
  $p = new pagination;
  $p->parameterName('p4_page');
  $p->items($total_rows);
  $p->limit($items_per_page);
  $p->currentPage($page);
  $p->adjacents(3);
  $p->target($refreshpage . '&action=display_guestbook_messages');
  $p->show();

} //DisplayGuestbookMessages


// ############################################################################
// SELECT FUNCTION
// ############################################################################

switch($action)
{
  case 'display_guestbook_messages':
    DisplayGuestbookMessages();
  break;

  case 'update_guestbook_messages':
    UpdateGuestbookMessages();
  break;

  case 'display_guestbook_settings':
    PrintPluginSettings($pluginid, array('admin_options', 'options'), $refreshpage);
  break;
}
