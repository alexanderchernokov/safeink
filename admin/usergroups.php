<?php

// DEFINE CONSTANTS
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');
define('FORUM_CACHE_FORUMS', 'sd-forum-forums');

// INIT PRGM
include(ROOT_PATH . 'includes/init.php');
include(ROOT_PATH . 'includes/enablegzip.php');

// LOAD ADMIN LANGUAGE
$admin_phrases = LoadAdminPhrases(5);

// GET ACTION
$action = GetVar('action', 'display_usergroups', 'string');

$css = array(SD_CSS_PATH . '/jPicker-1.1.6.min.css');
$sd_head->AddCSS($css);

$js = array(ROOT_PATH.ADMIN_PATH.'/javascript/functions.js',);
$sd_head->AddJS($js);

$extra_js = '';
if($action=='editpagepermissions')
{
  $extra_js = '
      $("#filterpages select").change(function() {
        $("#filterpages").submit();
      });

      $("a#pages-submit-filter").click(function(e) {
        e.preventDefault(e);
        $("#filterpages").submit();
      });

      $("a#pages-clear-filter").click(function(e) {
        e.preventDefault();
        $("#filterpages input").val("");
        $("#filterpages select").prop("selectedIndex", 0);
        $("#filterpages input[name=clearsearch]").val("1");
        $("#filterpages").submit();
      });

      $("#submit_plugin_selection").click(function(e) {
        e.preventDefault();
        $("#plugin_selection_form", parent.frames["page_plugin_selection_content"].document).submit();
      });

      $("a.subpage").click(function(e) {
        e.preventDefault();
        var new_parent = $(this).attr("rel");
        $("#filterpages #searchparentid").attr("value", new_parent);
        $("#filterpages").submit();
      });

      initCheckboxGroup("usergroups", "display");
      initCheckboxGroup("usergroups", "view");
  ';
}


// DISPLAY ADMIN HEADER
sd_header_add(array(
  //SD370: jPicker files moved to core admin loader!
  'other' => array(
    '<style type="text/css">td.td3 { text-align: center; }</style>
<script type="text/javascript" language="javascript"> //<![CDATA[
if (typeof(jQuery) !== "undefined") {
  jQuery(document).ready(function() {
    '.$extra_js.'
    if(typeof(jQuery.fn.jPicker) !== "undefined") {
      jQuery("div.jPicker").remove(); /* remove extra layer */
      jQuery(".colorpicker").jPicker({
        images: {clientPath: "'.SD_INCLUDE_PATH.'css/images/jpicker/"}
      }).addClass("jPickered");
    }
  });
}

</script>')
), false);

// PLUGIN BITFIELD
$pluginbitfield = array('canview'     => 1,
                        'cansubmit'   => 2,
                        'candownload' => 4,
                        'cancomment'  => 8,
                        'canadmin'    => 16,
                        'canmoderate' => 32);

// ############################################################################
// PRE-PROCESS SEARCH PARAMETERS COOKIE //SD343
// ############################################################################

$customsearch = GetVar('customsearch', 0, 'bool');
$clearsearch  = GetVar('clearsearch',  0, 'bool');

// Restore previous search array from cookie
$search_cookie = isset($_COOKIE[COOKIE_PREFIX . '_groupspages_search']) ? $_COOKIE[COOKIE_PREFIX . '_groupspages_search'] : false;

if($clearsearch)
{
  $search_cookie = false;
  $customsearch  = false;
}

if($customsearch)
{
  $search = array('name'        => GetVar('searchname', '', 'string'),
                  'title'       => GetVar('searchtitle', '', 'string'),
                  'usergroupid' => GetVar('searchusergroupid', 0, 'whole_number'),
                  'parentid'    => GetVar('searchparentid', 0, 'whole_number'),
                  'designid'    => GetVar('searchdesignid', 0, 'whole_number'),
                  'pluginid'    => GetVar('searchpluginid', '0', 'string'),
                  'secure'      => GetVar('searchsecure', 'all', 'string'));
}
else
if($search_cookie !== false)
{
  $search = unserialize(base64_decode(($search_cookie)));
}

if($customsearch)
{
  // Store search params in cookie
  sd_CreateCookie('_groupspages_search',base64_encode(serialize($search)));
}

if(empty($search) || !is_array($search))
{
  $search = array('name'        => '',
                  'title'       => '',
                  'usergroupid' => 0,
                  'parentid'    => 0,
                  'designid'    => 0,
                  'pluginid'    => '0',
                  'secure'      => 'all');

  // Remove search params cookie
  sd_CreateCookie('_groupspages_search', '');
}

$search['name']     = isset($search['name'])     ? (string)$search['name'] : '';
$search['title']    = isset($search['title'])    ? (string)$search['title'] : '';
$search['usergroupid'] = isset($search['usergroupid']) ? Is_Valid_Number($search['usergroupid']) : '';
$search['parentid'] = isset($search['parentid']) ? Is_Valid_Number($search['parentid']) : '';
$search['designid'] = isset($search['designid']) ? Is_Valid_Number($search['designid']) : '';
$search['pluginid'] = isset($search['pluginid']) ? (string)$search['pluginid'] : '';
$search['secure']   = isset($search['secure'])   ? (string)$search['secure'] : '';
$search['secure']   = in_array($search['secure'], array('0','1','all')) ? $search['secure'] : 'all';

// CHECK PAGE ACCESS
CheckAdminAccess('usergroups');

DisplayAdminHeader(array('Users', $action));

if(($usersystem['name'] != 'Subdreamer') && (!strlen($action) || ($action == 'display_usergroups')))
{
  DisplayMessage(AdminPhrase('forum_integration_msg_users1'), true);
}


// ############################################################################
// CREATE A COPY OF AN EXISTING USERGROUP
// ############################################################################

function CreateUsergroupCopy()
{
  global $DB, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage('usergroups.php','<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $usergroupid = GetVar('usergroupid', 0, 'whole_number');
  if(!empty($usergroupid) && ($usergroupid > 1) && ($usergroupid < 9999999))
  {
    //SD370: added categorymobilemenuids
    $DB->query("INSERT INTO {usergroups}
     (usergroupid, name, displayname, description, color_online, adminaccess, admin_access_pages,
      banned, offlinecategoryaccess, commentaccess, categoryviewids,
      categorymenuids, categorymobilemenuids, pluginviewids, pluginsubmitids,
      plugindownloadids, plugincommentids, pluginmoderateids, pluginadminids,
      custompluginviewids, custompluginadminids,
      articles_author_mode, maintain_customplugins, display_online)
      SELECT NULL, CONCAT(name,' *'), '', '', color_online, adminaccess, admin_access_pages,
      banned, offlinecategoryaccess, commentaccess, IFNULL(categoryviewids,''),
      IFNULL(categorymenuids,''), IFNULL(categorymobilemenuids,''), pluginviewids, pluginsubmitids,
      plugindownloadids, plugincommentids, pluginmoderateids, pluginadminids,
      custompluginviewids, custompluginadminids,
      articles_author_mode, maintain_customplugins, display_online
      FROM {usergroups}
      WHERE usergroupid = %d", $usergroupid);
    $new_usergroupid = $DB->insert_id();

    //SD351: process forum plugin's permissions for new/old group
    if(!empty($new_usergroupid) &&
       ($rows = $DB->query('SELECT forum_id, access_post, access_view'.
                           ' FROM {p_forum_forums}')))
    {
      while($forum = $DB->fetch_array($rows,null,MYSQL_ASSOC))
      {
        $changed = 0;
        $forum_id = $forum['forum_id'];
        $access_post = $access_view = '';
        $forum_ap = $forum_av = array();
        if(!empty($forum['access_post']) && ($forum['access_post'] != '||') && strlen($forum['access_post']))
        {
          $forum_ap = trim($forum['access_post'], '|');
          $forum_ap = @explode('|', $forum_ap);
          if(in_array($usergroupid,$forum_ap))
          {
            $changed = 1;
            $forum_ap[] = $new_usergroupid;
            sort($forum_ap, SORT_NUMERIC);
            $access_post = '|'.implode('|', $forum_ap).'|';
          }
        }
        if(!empty($forum['access_view']) && ($forum['access_view'] != '||') && strlen($forum['access_view']))
        {
          $forum_av = trim($forum['access_view'], '|');
          $forum_av = @explode('|', $forum_av);
          if(in_array($usergroupid,$forum_av))
          {
            $changed = 1;
            $forum_av[] = $new_usergroupid;
            sort($forum_av, SORT_NUMERIC);
            $access_view = '|'.implode('|', $forum_av).'|';
          }
        }

        if($changed)
        {
          $DB->query("UPDATE {p_forum_forums} SET access_post = '%s', access_view = '%s'".
                     ' WHERE forum_id = %d',
                     $access_post, $access_view, $forum_id);
        }
      }
      global $SDCache;
      if(!empty($SDCache) && $SDCache->IsActive()) $SDCache->delete_cacheid(FORUM_CACHE_FORUMS);
    }

    RedirectPage('usergroups.php?action=display_usergroup_form&usergroupid='.$new_usergroupid.
                 SD_URL_TOKEN, AdminPhrase('users_usergroup_created'));
    return;
  }

  RedirectPage('usergroups.php?action=display_usergroups' . SD_URL_TOKEN, $sdlanguage['error_invalid_token']);
}

// ############################################################################
// DELETE USERGROUP
// ############################################################################

function DeleteUsergroup()
{
  global $DB, $SDCache, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage('usergroups.php','<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $usergroupid = GetVar('usergroupid', 0, 'whole_number');
  $confirmdelete = GetVar('confirmdelete', 0, 'string');

  if(($confirmdelete === AdminPhrase('common_no')) || empty($usergroupid))
  {
    $action = 'displaydefault';
    DisplayUsergroups();
    return;
  }
  else
  if(empty($confirmdelete))
  {
    // get the usergroupname
    $usergroup = $DB->query_first('SELECT name, displayname FROM {usergroups} WHERE usergroupid = %d', $usergroupid);

    $description = AdminPhrase('users_usergroup') . ': <strong>' . $usergroup['name'] .
                   (strlen($usergroup['displayname']) ? ' ('.$usergroup['displayname'] . ')' : '').
                   '</strong><br /><br />' .
                   AdminPhrase('users_confirm_delete_usergroup');

    $hiddenvalues = '<input type="hidden" name="usergroupid" value="'.$usergroupid.'" />
                     <input type="hidden" name="action" value="deleteusergroup" />
                     '.PrintSecureToken();

    // arguments: description, hidden input values, form redirect page
    ConfirmDelete($description, $hiddenvalues, 'usergroups.php');
  }
  else if($confirmdelete == AdminPhrase('common_yes'))
  {
    if(!empty($usergroupid) && is_numeric($usergroupid) && Is_Valid_Number($usergroupid, 0, 2, 99999))
    {
      $DB->query('UPDATE {users} SET usergroupid = %d WHERE usergroupid = %d',MEMBERS_UGID,$usergroupid);
      $DB->query('DELETE FROM {usergroups} WHERE usergroupid = %d',$usergroupid);
      RemoveObsoleteUsergroupsConfigs(); //SD342

      $SDCache->delete_cacheid('USERGROUPS');
      $SDCache->delete_cacheid('UCP_GROUPS_CONFIG');

      //SD351: process forum plugin's permissions for new/old group
      if($rows = $DB->query('SELECT forum_id, access_post, access_view'.
                            ' FROM {p_forum_forums}'))
      {
        while($forum = $DB->fetch_array($rows,null,MYSQL_ASSOC))
        {
          $changed = 0;
          $forum_id = $forum['forum_id'];
          $access_post = $access_view = '';
          $forum_ap = $forum_av = array();
          if(!empty($forum['access_post']) && ($forum['access_post'] != '||') && strlen($forum['access_post']))
          {
            $forum_ap = trim($forum['access_post'], '|');
            if((false !== ($forum_ap = @explode('|', $forum_ap))) &&
               count($forum_ap))
            {
              $forum_ap = array_flip($forum_ap);
              if(array_key_exists($usergroupid,$forum_ap))
              {
                $changed = 1;
                unset($forum_ap[$usergroupid]);
                $forum_ap = array_flip($forum_ap);
                sort($forum_ap, SORT_NUMERIC);
                $access_post = '|'.implode('|', $forum_ap).'|';
              }
            }
          }
          if(!empty($forum['access_view']) && ($forum['access_view'] != '||') && strlen($forum['access_view']))
          {
            $forum_av = trim($forum['access_view'], '|');
            if((false !== ($forum_av = @explode('|', $forum_av))) &&
               count($forum_av))
            {
              $forum_av = array_flip($forum_av);
              if(array_key_exists($usergroupid,$forum_av))
              {
                $changed = 1;
                unset($forum_av[$usergroupid]);
                $forum_av = array_flip($forum_av);
                sort($forum_av, SORT_NUMERIC);
                $access_post = '|'.implode('|', $forum_av).'|';
              }
            }
          }

          if($changed)
          {
            $DB->query("UPDATE {p_forum_forums} SET access_post = '%s', access_view = '%s'".
                       ' WHERE forum_id = %d',
                       $access_post, $access_view, $forum_id);
          }
        }
        global $SDCache;
        if(!empty($SDCache) && $SDCache->IsActive()) $SDCache->delete_cacheid(FORUM_CACHE_FORUMS);
      }
    }

    RedirectPage('usergroups.php', AdminPhrase('users_usergroup_deleted'));
  }
} //DeleteUsergroup


// ############################################################################
// DISPLAY PAGE PERMISSIONS - OLD STYLE
// ############################################################################

function DisplayCategoriesPermissions($parentid, $category_permissions_arr, $indent = '')
{
  global $DB;

  if(!isset($parentid))
  {
    return;
  }

  $categorymenuids = $category_permissions_arr['categorymenuids'];
  $categorymobilemenuids = $category_permissions_arr['categorymobilemenuids']; //SD370
  $categoryviewids = $category_permissions_arr['categoryviewids'];

  if(($getcategories = $DB->query('SELECT categoryid, name, parentid FROM {categories}
      WHERE parentid = %d ORDER BY parentid, displayorder', $parentid)) && $DB->get_num_rows($getcategories))
  {
    while($category = $DB->fetch_array($getcategories,null,MYSQL_ASSOC))
    {
      $menuitemstatus = (!empty($categorymenuids) && @in_array($category['categoryid'],$categorymenuids)) ? 'checked="checked"' : '';
      $mobilemenuitemstatus = (!empty($categorymobilemenuids) && @in_array($category['categoryid'],$categorymobilemenuids)) ? 'checked="checked"' : '';
      $canaccessstatus = (!empty($categoryviewids) && @in_array($category['categoryid'],$categoryviewids)) ? 'checked="checked"' : '';
      $parentid   = (int)$category['parentid'];
      $categoryid = (int)$category['categoryid'];
      echo '
      <tr>
        <td width="40%">'.($parentid?'':'<strong>').$indent.$category['name'].($parentid?'':'</strong>').'</td>
        <td width="20%" style="text-align: left;"><input type="checkbox" class="ace" name="categorymenuids[]" value="' . $categoryid . '" ' . $menuitemstatus  . ' checkme="display" /><span class="lbl"></span></td>
        <td width="20%" style="text-align: left;"><input type="checkbox" class="ace" name="categorymobilemenuids[]" value="' . $categoryid . '" ' . $mobilemenuitemstatus  . ' checkme="mobile" /><span class="lbl"></span></td>
        <td width="20%" style="text-align: left;"><input type="checkbox" class="ace" name="categoryviewids[]" value="' . $categoryid . '" ' . $canaccessstatus . ' checkme="view" /><span class="lbl"></span></td>
      </tr>';
      DisplayCategoriesPermissions($category['categoryid'], $category_permissions_arr, $indent.'-&nbsp;&nbsp;');
    }
  }

} //DisplayCategoriesPermissions


// ############################################################################
// UPDATE USERGROUP CONFIG
// ############################################################################

function SaveUsergroupConfig($usergroupid) //SD342
{
  global $DB;

  if(empty($usergroupid) || !is_numeric($usergroupid) || ($usergroupid<1)) return false;

  // SD342: Usergroup's extra settings
  $msg_enabled          = empty($_POST['msg_enabled']) ? 0 : 1;
  $msg_inbox_limit      = GetVar('msg_inbox_limit', 10, 'whole_number', true, false);
  $msg_keep_limit       = GetVar('msg_keep_limit', 10, 'whole_number', true, false);
  $msg_recipients_limit = GetVar('msg_recipients_limit', 1, 'whole_number', true, false);
  $msg_requirevvc       = empty($_POST['msg_requirevvc']) ? 0 : 1;
  $msg_notification     = empty($_POST['msg_notification']) ? 0 : 1;
  $sig_enabled          = empty($_POST['sig_enabled']) ? 0 : 1;

  $avatar_enabled       = GetVar('avatar_enabled', 0, 'natural_number', true, false);
  $avatar_enabled       = $avatar_enabled | GetVar('avatar_upload', 0, 'natural_number', true, false);
  $avatar_enabled       = $avatar_enabled | GetVar('avatar_link', 0, 'natural_number', true, false);
  $avatar_extensions    = GetVar('avatar_extensions', '', 'string', true, false);
  $avatar_max_width     = GetVar('avatar_max_width', '80', 'natural_number', true, false);
  $avatar_max_width     = Is_Valid_Number($avatar_max_width,80,10,999); //SD344
  $avatar_max_height    = GetVar('avatar_max_height', '80', 'natural_number', true, false);
  $avatar_max_height    = Is_Valid_Number($avatar_max_height,80,10,999); //SD344
  $avatar_max_size      = GetVar('avatar_max_size', '20480', 'natural_number', true, false);
  $avatar_max_size      = Is_Valid_Number($avatar_max_size,20480,2048,999999);
  $avatar_path          = GetVar('avatar_path', '', 'string', true, false);

  //SD344: profile picture settings
  $picture_enabled      = GetVar('picture_enabled', 0, 'natural_number', true, false);
  $picture_enabled      = $picture_enabled | GetVar('picture_upload', 0, 'natural_number', true, false);
  $picture_enabled      = $picture_enabled | GetVar('picture_link', 0, 'natural_number', true, false);
  $picture_extensions   = GetVar('picture_extensions', '', 'string', true, false);
  $picture_max_width    = GetVar('picture_max_width', '120', 'natural_number', true, false);
  $picture_max_width    = Is_Valid_Number($picture_max_width,120,10,9999);
  $picture_max_height   = GetVar('picture_max_height', '120', 'natural_number', true, false);
  $picture_max_height   = Is_Valid_Number($picture_max_height,120,10,9999);
  $picture_max_size     = GetVar('picture_max_size', '10240', 'natural_number', true, false);
  $picture_max_size     = Is_Valid_Number($picture_max_size,102400,2048,99999999);
  $picture_path         = GetVar('picture_path', '', 'string', true, false);

  $enable_attachments   = empty($_POST['enable_attachments']) ? 0 : 1;
  $attachments_max_size   = GetVar('attachments_max_size', '2048', 'natural_number', true, false);
  $attachments_extensions = GetVar('attachments_extensions', '*', 'string', true, false);
  $enable_subscriptions   = empty($_POST['enable_subscriptions']) ? 0 : 1;
  //SD343: extra comments permissions
  $edit_own_comments      = empty($_POST['edit_own_comments']) ? 0 : 1;
  $delete_own_comments    = empty($_POST['delete_own_comments']) ? 0 : 1;
  $approve_comment_edits  = empty($_POST['approve_comment_edits']) ? 0 : 1;
  $hide_avatars_guests    = empty($_POST['hide_avatars_guests']) ? 0 : 1; //SD360
  $allow_uname_change     = empty($_POST['allow_uname_change']) ? 0 : 1; //SD370

  $DB->query(
   "UPDATE {usergroups_config}
    SET msg_enabled           = $msg_enabled,
        msg_inbox_limit       = $msg_inbox_limit,
        msg_keep_limit        = $msg_keep_limit,
        msg_recipients_limit  = $msg_recipients_limit,
        msg_requirevvc        = $msg_requirevvc,
        msg_notification      = $msg_notification,
        enable_attachments    = $enable_attachments,
        attachments_max_size  = $attachments_max_size,
        attachments_extensions= '%s',
        sig_enabled           = $sig_enabled,
        avatar_enabled        = $avatar_enabled,
        avatar_extensions     = '%s',
        avatar_max_width      = $avatar_max_width,
        avatar_max_height     = $avatar_max_height,
        avatar_max_size       = $avatar_max_size,
        avatar_path           = '%s',
        pub_img_enabled       = $picture_enabled,
        pub_img_extensions    = '%s',
        pub_img_max_width     = $picture_max_width,
        pub_img_max_height    = $picture_max_height,
        pub_img_max_size      = $picture_max_size,
        pub_img_path          = '%s',
        enable_subscriptions  = $enable_subscriptions,
        edit_own_comments     = $edit_own_comments,
        delete_own_comments   = $delete_own_comments,
        approve_comment_edits = $approve_comment_edits,
        hide_avatars_guests   = $hide_avatars_guests,
        allow_uname_change    = $allow_uname_change
    WHERE usergroupid         = %d",
    $attachments_extensions, $avatar_extensions, $avatar_path,
    $picture_extensions, $picture_path,
    $usergroupid);

  RemoveObsoleteUsergroupsConfigs(); //SD342

} //SaveUsergroupConfig


function RemoveObsoleteUsergroupsConfigs() //SD342
{
  global $DB;
  $DB->query("DELETE FROM ".PRGM_TABLE_PREFIX."usergroups_config WHERE NOT EXISTS
             (SELECT 1 FROM ".PRGM_TABLE_PREFIX."usergroups ug
              WHERE ug.usergroupid = ".PRGM_TABLE_PREFIX."usergroups_config.usergroupid)");

} //RemoveObsoleteUsergroupsConfigs


// ############################################################################
// SAVE (=INSERT/UPDATE) USERGROUP
// ############################################################################

function SaveUsergroup()
{
  global $DB, $SDCache, $sdlanguage;

  // SD313: security check against invalid submissions
  if(!CheckFormToken())
  {
    RedirectPage('usergroups.php','<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $usergroupid = GetVar('usergroupid', 0, 'whole_number');
  $is_new = empty($usergroupid);

  $name                   = GetVar('name', AdminPhrase('usergroup_unnamed'), 'string', true, false);
  $displayname            = GetVar('displayname', '', 'string', true, false);
  $description            = GetVar('description', '', 'string', true, false); //SD322
  $color_online           = GetVar('color_online', '', 'string', true, false);
  $admin_access_pages     = GetVar('admin_access_pages', '', 'array', true, false);
  $admin_access_pages     = !empty($admin_access_pages) ? serialize($admin_access_pages) : '';
  $adminaccess            = GetVar('adminaccess', 0, 'natural_number', true, false);
  $adminaccess            = Is_Valid_Number($adminaccess, 0, 0, 1);
  $banned                 = !$adminaccess && GetVar('banned', 0, 'natural_number', true, false);
  $banned                 = empty($banned) ? 0 : 1;
  $commentaccess          = GetVar('commentaccess', 0, 'bool', true, false);
  $commentaccess          = empty($commentaccess)? 0 : 1;
  $offlinecategoryaccess  = GetVar('offlinecategoryaccess', 0, 'bool', true, false);
  $offlinecategoryaccess  = empty($offlinecategoryaccess)? 0 : 1;
  $articles_author_mode   = GetVar('articles_author_mode', 0, 'bool', true, false);
  $articles_author_mode   = empty($articles_author_mode)? 0 : 1;
  $maintain_customplugins = GetVar('maintain_customplugins', 0, 'bool', true, false); //SD331
  $maintain_customplugins = empty($maintain_customplugins)? 0 : 1;
  $display_online         = GetVar('display_online', 0, 'bool', true, false); //SD331
  $display_online         = empty($display_online)? 0 : 1;
  $require_vvc            = GetVar('require_vvc', 0, 'bool', true, false); //SD332
  $require_vvc            = empty($require_vvc)? 0 : 1;
  $excerpt_mode           = GetVar('excerpt_mode', 0, 'bool', true, false); //SD342
  $excerpt_mode           = empty($excerpt_mode)? 0 : 1;
  $excerpt_message        = GetVar('excerpt_message', '', 'string', true, false); //SD342
  $excerpt_length         = GetVar('excerpt_length', '80', 'whole_number', true, false); //SD342

  $pluginviewids          = GetVar('pluginviewids', array(1), 'array', true, false); // 1 = "empty plugin"
  $pluginsubmitids        = GetVar('pluginsubmitids', '', 'array', true, false);
  $plugindownloadids      = GetVar('plugindownloadids', '', 'array', true, false);
  $plugincommentids       = GetVar('plugincommentids', '', 'array', true, false);
  $pluginadminids         = GetVar('pluginadminids', '', 'array', true, false);
  $pluginmoderateids      = GetVar('pluginmoderateids', '', 'array', true, false); //SD322
  $custompluginviewids    = GetVar('custompluginviewids', '', 'array', true, false);
  $custompluginadminids   = GetVar('custompluginadminids', '', 'array', true, false);

  $pluginviewids         = !empty($pluginviewids) ? sd_SortIdList(@implode(',', $pluginviewids)) : '1';
  $pluginsubmitids       = !empty($pluginsubmitids) ? sd_SortIdList(@implode(',', $pluginsubmitids)) : '';
  $plugindownloadids     = !empty($plugindownloadids) ? sd_SortIdList(@implode(',', $plugindownloadids)) : '';
  $plugincommentids      = !empty($plugincommentids) ? sd_SortIdList(@implode(',', $plugincommentids)) : '';
  $pluginadminids        = !empty($pluginadminids) ? sd_SortIdList(@implode(',', $pluginadminids)) : '';
  $pluginmoderateids     = !empty($pluginmoderateids) ? sd_SortIdList(@implode(',', $pluginmoderateids)) : ''; //SD322
  $custompluginviewids   = !empty($custompluginviewids) ? sd_SortIdList(@implode(',', $custompluginviewids)) : '';
  $custompluginadminids  = !empty($custompluginadminids) ? sd_SortIdList(@implode(',', $custompluginadminids)) : '';

  if(!$is_new)
  {
    $DB->query(
    "UPDATE {usergroups}
      SET name               = '$name',
      displayname            = '$displayname',
      color_online           = '$color_online',
      description            = '%s',
      adminaccess            = $adminaccess,
      admin_access_pages     = '%s',
      commentaccess          = $commentaccess,
      offlinecategoryaccess  = $offlinecategoryaccess,
      pluginviewids          = '$pluginviewids',
      pluginsubmitids        = '$pluginsubmitids',
      plugindownloadids      = '$plugindownloadids',
      plugincommentids       = '$plugincommentids',
      pluginadminids         = '$pluginadminids',
      pluginmoderateids      = '$pluginmoderateids',
      custompluginviewids    = '$custompluginviewids',
      custompluginadminids   = '$custompluginadminids',
      articles_author_mode   = '$articles_author_mode',
      banned                 = $banned,
      maintain_customplugins = $maintain_customplugins,
      display_online         = $display_online,
      require_vvc            = $require_vvc,
      excerpt_mode           = $excerpt_mode,
      excerpt_message        = '%s',
      excerpt_length         = $excerpt_length
      WHERE usergroupid      = %d",
      $description, $admin_access_pages, $excerpt_message, $usergroupid);
  }
  else
  {
    $DB->query("INSERT INTO {usergroups}
             (usergroupid, name, displayname, description, color_online, adminaccess, admin_access_pages,
              categoryviewids, categorymenuids,
              banned, offlinecategoryaccess, commentaccess,
              pluginviewids, pluginsubmitids, plugindownloadids, plugincommentids,
              pluginmoderateids, pluginadminids, custompluginviewids, custompluginadminids,
              articles_author_mode, maintain_customplugins, display_online, require_vvc,
              excerpt_mode, excerpt_message, excerpt_length)
              VALUES
             (NULL, '$name', '$displayname', '%s', '$color_online', $adminaccess, '%s',
              '', '',
              $banned, $offlinecategoryaccess, $commentaccess,
              '$pluginviewids', '$pluginsubmitids', '$plugindownloadids', '$plugincommentids',
              '$pluginmoderateids', '$pluginadminids', '$custompluginviewids', '$custompluginadminids',
              '$articles_author_mode', $maintain_customplugins, $display_online, $require_vvc,
              $excerpt_mode, '%s', $excerpt_length)",
              $description, $admin_access_pages, $excerpt_message);
    $usergroupid = $DB->insert_id();
  }

  SaveUsergroupConfig($usergroupid); //SD342

  $SDCache->delete_cacheid('USERGROUPS');
  $SDCache->delete_cacheid('UCP_GROUPS_CONFIG');

  RedirectPage('usergroups.php?action=display_usergroup_form&amp;usergroupid=' .
               $usergroupid . SD_URL_TOKEN,
               AdminPhrase($is_new ? 'users_usergroup_created' : 'users_usergroup_updated'));

} //SaveUsergroup


// ############################################################################
// SAVE (=UPDATE) USERGROUP'S PAGE PERMISSIONS - OLD STYLE FOR LOW VOLUME
// ############################################################################

function SavePagePermissions()
{
  global $DB, $SDCache, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage('usergroups.php','<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  if(!$usergroupid = GetVar('usergroupid', 0, 'whole_number'))
  {
    DisplayMessage('Invalid Usergroup!',true);
    return false;
  }

  if(!$usergroup = $DB->query_first('SELECT usergroupid, name, categoryviewids, categorymenuids, categorymobilemenuids'.
                                    ' FROM {usergroups} WHERE usergroupid = %d', $usergroupid))
  {
    DisplayMessage('Usergroup not found!', true);
    return false;
  }

  $categoryviewids = GetVar('categoryviewids', array(), 'array', true, false);
  $categorymenuids = GetVar('categorymenuids', array(), 'array', true, false);
  $categorymobilemenuids = GetVar('categorymobilemenuids', array(), 'array', true, false);
  $categoryviewids = empty($categoryviewids) ? '1' : sd_SortIdList(@implode(',',$categoryviewids),',');
  $categorymenuids = empty($categorymenuids) ? '1' : sd_SortIdList(@implode(',',$categorymenuids),',');
  $categorymobilemenuids = empty($categorymobilemenuids) ? '1' : sd_SortIdList(@implode(',',$categorymobilemenuids),',');

  $DB->query("UPDATE {usergroups}
              SET categoryviewids = '$categoryviewids',
                  categorymenuids = '$categorymenuids',
                  categorymobilemenuids = '$categorymobilemenuids'
              WHERE usergroupid = %d",
              $usergroupid);

  $SDCache->delete_cacheid('USERGROUPS');

  RedirectPage('usergroups.php?action=editpagepermissions&amp;usergroupid=' .
               $usergroupid . SD_URL_TOKEN,
               AdminPhrase('users_usergroup_updated'));

} //SavePagePermissions


// ############################################################################
// UPDATE USERGROUP'S PAGE PERMISSIONS - FOR DRILL-DOWN STYLE
// ############################################################################

function SavePagePermissions2()
{
  global $DB, $SDCache, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage('usergroups.php','<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  if(!$usergroupid = GetVar('usergroupid', 0, 'whole_number'))
  {
    DisplayMessage('Invalid Usergroup!',true);
    return false;
  }

  if(!$usergroup = $DB->query_first('SELECT usergroupid, name, categoryviewids, categorymenuids, categorymobilemenuids'.
                                    ' FROM {usergroups} WHERE usergroupid = %d', $usergroupid))
  {
    DisplayMessage('Usergroup not found!', true);
    return false;
  }

  $org_categoryviewids = (empty($usergroup['categoryviewids']) ? array(1): @explode(',', $usergroup['categoryviewids']));
  if(count($org_categoryviewids)) $org_categoryviewids = array_flip($org_categoryviewids);
  $org_categorymenuids = (empty($usergroup['categorymenuids']) ? array(1): @explode(',', $usergroup['categorymenuids']));
  if(count($org_categorymenuids)) $org_categorymenuids = array_flip($org_categorymenuids);
  $org_categorymobilemenuids = (empty($usergroup['categorymobilemenuids']) ? array(1): @explode(',', $usergroup['categorymobilemenuids']));
  if(count($org_categorymobilemenuids)) $org_categorymobilemenuids = array_flip($org_categorymobilemenuids);

  $categoryids = GetVar('categoryids', array(), 'a_int', true, false);
  $categoryviewids = GetVar('categoryviewids', array(), 'a_int', true, false);
  $categorymenuids = GetVar('categorymenuids', array(), 'a_int', true, false);
  $categorymobilemenuids = GetVar('categorymobilemenuids', array(), 'a_int', true, false);
  $page = GetVar('page', 1, 'whole_number', true, false);

  if(count($categoryids))
  {
    foreach($categoryids as $tmp => $categoryid)
    {
      if(in_array($categoryid, $categoryviewids))
      {
        if(!isset($org_categoryviewids[$categoryid])) $org_categoryviewids[$categoryid] = '1';
      }
      else
      {
        if(isset($org_categoryviewids[$categoryid])) unset($org_categoryviewids[$categoryid]);
      }
      if(in_array($categoryid, $categorymenuids))
      {
        if(!isset($org_categorymenuids[$categoryid])) $org_categorymenuids[$categoryid] = '1';
      }
      else
      {
        if(isset($org_categorymenuids[$categoryid])) unset($org_categorymenuids[$categoryid]);
      }
      if(in_array($categoryid, $categorymobilemenuids))
      {
        if(!isset($org_categorymobilemenuids[$categoryid])) $org_categorymobilemenuids[$categoryid] = '1';
      }
      else
      {
        if(isset($org_categorymobilemenuids[$categoryid])) unset($org_categorymobilemenuids[$categoryid]);
      }
    }
    @ksort($org_categoryviewids);
    @ksort($org_categorymenuids);
    @ksort($org_categorymobilemenuids);
    $org_categoryviewids = implode(',', array_keys($org_categoryviewids));
    $org_categorymenuids = implode(',', array_keys($org_categorymenuids));
    $org_categorymobilemenuids = implode(',', array_keys($org_categorymobilemenuids));

    $DB->query("UPDATE {usergroups}
                SET categoryviewids = '$org_categoryviewids',
                    categorymenuids = '$org_categorymenuids',
                    categorymobilemenuids = '$org_categorymobilemenuids'
                WHERE usergroupid = %d",
                $usergroupid);

    $SDCache->delete_cacheid('USERGROUPS');
  }
  RedirectPage('usergroups.php?action=editpagepermissions2&amp;usergroupid='.$usergroupid.
               '&amp;page='.$page.SD_URL_TOKEN,
               AdminPhrase('users_usergroup_updated'));

} //SavePagePermissions2


// ############################################################################
// DISPLAY USERGROUPS
// ############################################################################

function DisplayUsergroups()
{
  global $DB, $mainsettings;

  $wysiwyg_suffix = ($mainsettings['enablewysiwyg'] ? '&amp;load_wysiwyg=1' : '');

  $page = GetVar('page', 1, 'whole_number');
  $pagesize = Is_Valid_Number(GetVar('pagesize', 10, 'natural_number'),10,5,5000);
  if(isset($_POST['search']) && strlen($_POST['search'])) $page = 1;
  $limit     = ' LIMIT '.(($page-1)*$pagesize).','.$pagesize;
  $sort      = strtolower(GetVar('sort', 'usergroupid', 'string', false));
  $sort      = in_array($sort, array('usergroupid','name','displayname')) ? $sort : 'usergroupid';
  $order     = strtoupper(GetVar('order', 'ASC', 'string', false));
  $order     = (($order=='ASC') || ($order=='DESC')) ? $order : 'DESC';
  $searchname = GetVar('searchname', '', 'string', true, false);
  $searchdescr = GetVar('searchdescr', '', 'string', true, false);
  $searchdisplay = GetVar('searchdisplay', '', 'string', true, false);
  $searchcount = GetVar('searchcount', -1, 'natural_number', true, false);

  $pagesquery = 'WHERE'; // this MUST be here, do not change/move this line!
  $customsearch = false;
  if(!empty($_POST['submit']))
  {
    $comparison = ' AND ';
    // search for name?
    if(strlen($searchname))
    {
      $pagesquery .= ($pagesquery != 'WHERE' ? $comparison:'') . " ug.name LIKE '%%" . $searchname . "%%'";
    }

    // search for description?
    if(strlen($searchdescr))
    {
      $pagesquery .= ($pagesquery != 'WHERE' ? $comparison:'') . " ug.description LIKE '%%" . $searchdescr . "%%'";
    }

    // search for display_name?
    if(strlen($searchdisplay))
    {
      $pagesquery .= ($pagesquery != 'WHERE' ? $comparison:'') . " ug.display_name LIKE '%%" . $searchdisplay . "%%'";
    }

    // search for groups with min. # of users?
    if($searchcount >= 0)
    {
      $pagesquery .= ($pagesquery != 'WHERE' ? $comparison:'');
      if($searchcount == 0)
        $pagesquery .= ' ((SELECT COUNT(*) FROM '.PRGM_TABLE_PREFIX.'users u WHERE u.usergroupid = ug.usergroupid) = 0)';
      else
        $pagesquery .= ' ((SELECT COUNT(*) FROM '.PRGM_TABLE_PREFIX.'users u WHERE u.usergroupid = ug.usergroupid) > '.(int)$searchcount.')';
    }
    $customsearch = ($pagesquery != 'WHERE');
  }

  // get the current usergroups
  $sql = 'SELECT ug.usergroupid, ug.name, ug.displayname, ug.adminaccess, ug.banned, ug.description,'.
         ' ug.require_vvc, ug.excerpt_mode, ug.articles_author_mode, ug.display_online'.
         ' FROM '.PRGM_TABLE_PREFIX.'usergroups ug';
  if($customsearch)
  {
    $total_count = $DB->query_first('SELECT COUNT(*) ug_count FROM '.PRGM_TABLE_PREFIX.'usergroups ug '.$pagesquery);
    $total_count = $total_count['ug_count'];
    $getusergroups = $DB->query($sql . ' ' . $pagesquery . ' ORDER BY %s %s', $sort, $order);
  }
  else
  {
    $total_count = $DB->query_first('SELECT COUNT(*) ug_count FROM '.PRGM_TABLE_PREFIX.'usergroups ug ');
    $total_count = $total_count['ug_count'];
    $getusergroups = $DB->query($sql . ' ORDER BY %s %s %s', $sort, $order, $limit);
  }
  $usergrouprows = $DB->get_num_rows($getusergroups);

  echo '
  <form action="usergroups.php" method="post">
  <input type="hidden" name="action" value="display_usergroups" />
  '.PrintSecureToken();

	StartTable(AdminPhrase('users_usergroups') . ' ('.$total_count.')', array('table', 'table-bordered', 'table-striped'));

  if(empty($usergrouprows))
  {
    echo '<tr><td colspan="7">No results.</td></tr>';
  }
  else
  {
    
	echo '
    <thead>
    <tr>
      <th width="200">' . AdminPhrase('users_view_usergroup') . '</th>
      <th width="200">' . AdminPhrase('usergroup_description') . '</th>
      <th width="200">' . AdminPhrase('usergroup_displayname') . '</th>
      <th align="center" width="80">' . AdminPhrase('users_copy_usergroup') . '</th>
      <th class="center" width="80"><i class="ace-icon fa fa-key blue bigger-120"></i></th>
      <th align="center" width="80">' . AdminPhrase('users_user_count') . '</th>
      <th align="center" width="80">' . AdminPhrase('users_banned') . '</th>
      <th align="center" width="80">' . AdminPhrase('users_delete') . '</th>
    </tr>
	</thead>
	<tbody>
    
    <tr>
      <td width="200"><input type="text" name="searchname" value="'.$searchname.'" maxlength="50" style="min-width: 90%" /></td>
      <td width="200"><input type="text" name="searchdescr" value="'.$searchdescr.'" maxlength="200" style="min-width: 90%" /></td>
      <td width="200"><input type="text" name="searchdisplay" value="'.$searchdisplay.'" maxlength="200" style="min-width: 90%" /></td>
      <td colspan="2">&nbsp;</td>
      <td align="center" width="80"><input type="text" name="searchcount" value="'.($searchcount>=0?$searchcount:'').'" maxlength="5" /></td>
      <td align="center" width="80">&nbsp;</td>
      <td align="center" width="80"><button class="btn btn-info btn-xs" type="submit" name="submit" value="Search"><i class="ace-icon fa fa-search"></i> ' . AdminPhrase('common_search') .'</button></td>
    </tr>
    ';

    while($usergroup = $DB->fetch_array($getusergroups,null,MYSQL_ASSOC))
    {
      $totalusergroupusers = $DB->query_first('SELECT COUNT(*) AS value'.
        ' FROM {users} WHERE usergroupid = %d', $usergroup['usergroupid']);

      echo '
      <tr>
        <td>
          <a href="usergroups.php?action=display_usergroup_form&amp;usergroupid='.
            $usergroup['usergroupid'].$wysiwyg_suffix.SD_URL_TOKEN.
            '"><i class="ace-icon fa fa-group  bigger-110"></i>&nbsp;' .
          (!empty($usergroup['name']) ? $usergroup['name'] : AdminPhrase('usergroup_unnamed')) . '</a><br /></td>
        <td>' . unhtmlspecialchars($usergroup['description']) . ' </td>
        <td>';
      //SD322: added color-coded usergroup displayname
      $color = '';
      if(!empty($usergroup['color_online']))
      {
        $color = 'color: '.$usergroup['color_online'];
      }
      echo '<span style="font-weight: bold;'.$color.'">'.
           $usergroup['displayname'] . '&nbsp;</span>
        </td>
        <td class="center">
          <a href="usergroups.php?action=create_usergroup_copy&amp;usergroupid='.
            $usergroup['usergroupid'].SD_URL_TOKEN.'"><i class="ace-icon fa fa-copy bigger-110"></i></a></td>
        <td class="center"><a href="usergroups.php?action=editpagepermissions&amp;usergroupid='.
          $usergroup['usergroupid'].PrintSecureUrlToken().
          '"><i class="ace-icon fa fa-key bigger-110"></i></a></td>
        <td align="right" style="padding-right: 10px">' . $totalusergroupusers['value'] . '</td>
        <td class="center">' . ($usergroup['banned'] ? '<span class="red"><i class="ace-icon fa fa-check red bigger-120"></i> ' .AdminPhrase('common_yes') . '</span>' : '<span class="green"><i class="ace-icon fa fa-times green bigger-120"></i> ' . AdminPhrase('common_no') . '</span>'). '</td>
        <td class="center">';
      // Certain default Usergroups MUST NOT be deleted
      if((int)$usergroup['usergroupid'] <= 4)
      {
        echo '<i class="ace-icon fa fa-minus-circle red bigger-120"></i>';
      }
      else
      {
        echo '<a href="usergroups.php?action=deleteusergroup&amp;usergroupid=' . $usergroup['usergroupid'] .
             SD_URL_TOKEN. '"><i class="ace-icon fa fa-trash-o red bigger-120"></i></a>';
      }
      echo '</td>
      </tr>';
    } //while
    echo '</table></div>';
  }

  echo '</form>';

  // pagination
  if(!empty($total_count) && !$customsearch)
  {
    echo '<i class="ace-icon fa fa-key blue bigger-120"></i> = '.AdminPhrase('users_page_permissions').'<br /><br />';
    $p = new pagination;
    $p->items($total_count);
    $p->limit($pagesize);
    $p->currentPage($page);
    $p->adjacents(5);
    $p->target('usergroups.php?action=display_usergroups');
    $p->show();
  }

} //DisplayUsergroups


// ############################################################################
// DISPLAY PLUGIN PERMISSIONS
// ############################################################################

function DisplayPluginsPermissions($plugins, $plugin_permissions_arr, $pluginstype, $formgroup)
{
  global $DB, $plugin_names, $pluginbitfield;

  // return if there are no rows to print
  if(!$DB->get_num_rows($plugins))
  {
    return false;
  }

  $pluginviewids        = $plugin_permissions_arr['pluginviewids'];
  $pluginsubmitids      = $plugin_permissions_arr['pluginsubmitids'];
  $plugindownloadids    = $plugin_permissions_arr['plugindownloadids'];
  $plugincommentids     = $plugin_permissions_arr['plugincommentids'];
  $pluginadminids       = $plugin_permissions_arr['pluginadminids'];
  $pluginmoderateids    = $plugin_permissions_arr['pluginmoderateids'];
  $custompluginviewids  = $plugin_permissions_arr['custompluginviewids'];
  $custompluginadminids = $plugin_permissions_arr['custompluginadminids'];

  echo '
  <thead>
    <tr>
      <th width="15%">';

  echo AdminPhrase($pluginstype);

  echo '</th>
      <td width="15%"><input type="checkbox" class="ace" checkall="view_' . $formgroup . '" onclick="javascript: return select_deselectAll (\'usergroups\', this, \'view_' . $formgroup . '\');" /><span class="lbl"> ' . AdminPhrase('users_allow_view') . '</span></td>
      <td width="15%"><input type="checkbox" class="ace" checkall="submit_' . $formgroup . '" onclick="javascript: return select_deselectAll (\'usergroups\', this, \'submit_' . $formgroup . '\');" /><span class="lbl">  ' . AdminPhrase('users_allow_submit') . '</span></td>
      <td width="15%"><input type="checkbox" class="ace" checkall="download_' . $formgroup . '" onclick="javascript: return select_deselectAll (\'usergroups\', this, \'download_' . $formgroup . '\');" /><span class="lbl">  ' . AdminPhrase('users_allow_download') . '</span></td>
      <td width="15%"><input type="checkbox" class="ace" checkall="comment_' . $formgroup . '" onclick="javascript: return select_deselectAll (\'usergroups\', this, \'comment_' . $formgroup . '\');" /><span class="lbl">  ' . AdminPhrase('users_allow_comments') . '</span></td>
      <td width="15%"><input type="checkbox" class="ace" checkall="moderate_' . $formgroup . '" onclick="javascript: return select_deselectAll (\'usergroups\', this, \'moderate_' . $formgroup . '\');" /><span class="lbl">  ' . AdminPhrase('users_allow_moderate') . '</span></td>
      <td width="15%"><input type="checkbox" class="ace" checkall="admin_' . $formgroup . '" onclick="javascript: return select_deselectAll (\'usergroups\', this, \'admin_' . $formgroup . '\');" /><span class="lbl">  ' . AdminPhrase('users_allow_administer') . ' ' . '</span></td>
    </tr>
	</thead>';

  // start printing the plugins
  if($pluginstype == 'common_custom_plugins')
  {
    while($plugin = $DB->fetch_array($plugins))
    {
      if(empty($plugin['settings'])) $plugin['settings'] = 17;
      $canviewstatus  = (!empty($custompluginviewids)  && @in_array($plugin['custompluginid'], $custompluginviewids))  ? 'checked="checked"' : '';
      $canadminstatus = (!empty($custompluginadminids) && @in_array($plugin['custompluginid'], $custompluginadminids)) ? 'checked="checked"' : '';

      if(isset($plugin_names[$plugin['name']]))
      {
        $plugin['name'] = $plugin_names[$plugin['name']];
      }
      echo '
    <tr>
      <td>' . $plugin['name'] . '</td>
      <td>' .
      (($plugin['settings'] & $pluginbitfield['canview']) ?
        '<input type="checkbox" class="ace" name="custompluginviewids[]" value="'.$plugin['custompluginid'].'" '.$canviewstatus.' checkme="view_'.$formgroup.'" /><span class="lbl"></span>':
        '<i class="ace-icon fa fa-minus-circle red bigger-110"></i>') . '</td>
      <td><i class="ace-icon fa fa-minus-circle red bigger-110"></i></td><td><i class="ace-icon fa fa-minus-circle red bigger-110"></i></td><td><i class="ace-icon fa fa-minus-circle red bigger-110"></i></td><td><i class="ace-icon fa fa-minus-circle red bigger-110"></i></td>
      <td>' .
      (($plugin['settings'] & $pluginbitfield['canadmin']) ?
        '<input type="checkbox" class="ace" name="custompluginadminids[]" value="'.$plugin['custompluginid'].'" '.$canadminstatus.' checkme="admin_'.$formgroup.'" /><span class="lbl"></span>':
        '-') . '
      </td>
    </tr>';
    }
  }
  else
  {
    while($plugin = $DB->fetch_array($plugins))
    {
      $pid = isset($plugin['pluginid']) ? $plugin['pluginid'] : -1;
      if($pid > 0)
      {
        $canviewstatus     = (!empty($pluginviewids)     && @in_array($pid, $pluginviewids))     ? 'checked="checked"' : '';
        $cansubmitstatus   = (!empty($pluginsubmitids)   && @in_array($pid, $pluginsubmitids))   ? 'checked="checked"' : '';
        $candownloadstatus = (!empty($plugindownloadids) && @in_array($pid, $plugindownloadids)) ? 'checked="checked"' : '';
        $cancommentstatus  = (!empty($plugincommentids)  && @in_array($pid, $plugincommentids))  ? 'checked="checked"' : '';
        $canmoderatestatus = (!empty($pluginmoderateids) && @in_array($pid, $pluginmoderateids)) ? 'checked="checked"' : ''; //SD322
        $canadminstatus    = (!empty($pluginadminids)    && @in_array($pid, $pluginadminids))    ? 'checked="checked"' : '';

        if(isset($plugin_names[$plugin['name']]))
        {
          $plugin['name'] = $plugin_names[$plugin['name']];
        }
        echo '
      <tr>
        <td>' . $plugin['name'] . '</td>
        <td>' . ($plugin['settings'] & $pluginbitfield['canview']    ? '<input type="checkbox" class="ace" name="pluginviewids[]"     value="' . $plugin['pluginid'] . '" ' . $canviewstatus     . ' checkme="view_' . $formgroup . '"  />' : '-') . '<span class="lbl"></span></td>
        <td>' . ($plugin['settings'] & $pluginbitfield['cansubmit']  ? '<input type="checkbox" class="ace" name="pluginsubmitids[]"   value="' . $plugin['pluginid'] . '" ' . $cansubmitstatus   . ' checkme="submit_' . $formgroup . '" />' : '-') . '<span class="lbl"></span></td>
        <td>' . ($plugin['settings'] & $pluginbitfield['candownload']? '<input type="checkbox" class="ace" name="plugindownloadids[]" value="' . $plugin['pluginid'] . '" ' . $candownloadstatus . ' checkme="download_' . $formgroup . '" />' : '-') . '<span class="lbl"></span></td>
        <td>' . ($plugin['settings'] & $pluginbitfield['cancomment'] ? '<input type="checkbox" class="ace" name="plugincommentids[]"  value="' . $plugin['pluginid'] . '" ' . $cancommentstatus  . ' checkme="comment_' . $formgroup . '" />' : '-') . '<span class="lbl"></span></td>
        <td>' . ($plugin['settings'] & $pluginbitfield['canmoderate']? '<input type="checkbox" class="ace" name="pluginmoderateids[]" value="' . $plugin['pluginid'] . '" ' . $canmoderatestatus . ' checkme="moderate_' . $formgroup . '" />' : '-') . '<span class="lbl"></span></td>
        <td>' . ($plugin['settings'] & $pluginbitfield['canadmin']   ? '<input type="checkbox" class="ace" name="pluginadminids[]"    value="' . $plugin['pluginid'] . '" ' . $canadminstatus    . ' checkme="admin_' . $formgroup . '" />' : '-') . '<span class="lbl"></span></td>
      </tr>';
      }
    }
  }

  return true;

} //DisplayPluginsPermissions


// ############################################################################
// DISPLAY USERGROUP PAGE PERMISSIONS FORM - FULL LISTING FOR LOW VOLUME (OLD)
// ############################################################################

function EditPagePermissions()
{
  global $DB, $sdlanguage, $admin_menu_arr, $admin_phrases, $core_pluginids_arr;

  // SD313: security check against spam/bot submissions
  if(!CheckFormToken())
  {
    RedirectPage('usergroups.php','<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $usergroupid = GetVar('usergroupid', 0, 'whole_number');
  if(($usergroupid < 1) || ($usergroupid > 9999999))
  {
    DisplayMessage(AdminPhrase('users_error_invalid_usergroup'), true);
    return false;
  }

  $DB->result_type = MYSQL_ASSOC;
  if(!$usergroup = $DB->query_first('SELECT usergroupid, name, categoryviewids, categorymenuids, categorymobilemenuids'.
                                    ' FROM {usergroups} WHERE usergroupid = %d', $usergroupid))
  {
    DisplayMessage(AdminPhrase('users_error_invalid_usergroup'), true);
    return false;
  }

  $category_permissions_arr = array();
  $category_permissions_arr['categoryviewids'] = (empty($usergroup['categoryviewids']) ? array(1): @explode(',', $usergroup['categoryviewids']));
  $category_permissions_arr['categorymenuids'] = (empty($usergroup['categorymenuids']) ? array(1): @explode(',', $usergroup['categorymenuids']));
  $category_permissions_arr['categorymobilemenuids'] = (empty($usergroup['categorymobilemenuids']) ? array(1): @explode(',', $usergroup['categorymobilemenuids']));

  echo '
  <p style="padding:2px 2px 12px 4px">
    <a class="round_button" href="usergroups.php?action=displayusergroupform&amp;usergroupid='.$usergroupid.PrintSecureUrlToken().'">&nbsp;'.AdminPhrase('users_back_btn').'&nbsp;</a>
  </p>
  <form method="post" action="usergroups.php" id="usergroups" name="usergroups">
  <input type="hidden" name="usergroupid" value="'.$usergroupid.'" />
  '.PrintSecureToken();

  echo '<h3 class="header blue lighter">' . AdminPhrase('users_edit_usergroup') . ': ' . $usergroup['name']. '</h3>';

  StartTable(AdminPhrase('users_page_permissions'), array('table', 'table-bordered', 'table-striped'));
  
  echo'
  <thead>
  <tr>
    <th width="10%">' . AdminPhrase('users_page_name') . '</th>
    <th width="20%"><input type="checkbox" class="ace" checkall="display" onclick="javascript: return select_deselectAll (\'usergroups\', this, \'display\');" /><span class="lbl"> ' . AdminPhrase('common_display_in_menu') . '</span></th>
    <th width="30%"><input type="checkbox" class="ace" checkall="mobile" onclick="javascript: return select_deselectAll (\'usergroups\', this, \'mobile\');" /><span class="lbl"> ' . AdminPhrase('common_display_in_mobile_menu') . '</span></th>
    <th width="20%"><input type="checkbox" class="ace" checkall="view" onclick="javascript: return select_deselectAll (\'usergroups\', this, \'view\');" /><span class="lbl"> ' . AdminPhrase('common_allow_view') . '</span></th>
  </tr>
  </thead></tbody>';

  // Category Permissions, get all categories
  DisplayCategoriesPermissions(0, $category_permissions_arr);

  echo '</table></div>';
  
  PrintSubmit('savepagepermissions',AdminPhrase('users_update_usergroup'),'usergroups','fa-check');

  echo '
  </form>';

} //EditPagePermissions


// ############################################################################
// DISPLAY PLUGIN PERMISSIONS - DRILL-DOWN STYLE FOR LARGE VOLUME
// ############################################################################

function EditPagePermissions2()
{
  global $DB, $customsearch, $parentselection, $plugin_names, $listedcategories,
         $sdlanguage, $skin_phrases, $userinfo, $search;

  $usergroupid = GetVar('usergroupid', 0, 'whole_number');
  if(($usergroupid < 1) || ($usergroupid > 9999999))
  {
    DisplayMessage(AdminPhrase('users_error_invalid_usergroup'), true);
    return false;
  }

  $DB->result_type = MYSQL_ASSOC;
  if(!$usergroup = $DB->query_first('SELECT usergroupid, name, categorymenuids, categorymobilemenuids, categoryviewids'.
                                    ' FROM {usergroups} WHERE usergroupid = %d', $usergroupid))
  {
    DisplayMessage(AdminPhrase('users_error_invalid_usergroup'), true);
    return false;
  }

  $items_per_page = GetVar('searchlimit', 20, 'whole_number', true, false);
  $page = GetVar('page', 1, 'whole_number');
  $limit = ' LIMIT '.(($page-1)*$items_per_page).','.$items_per_page;
  $parent_page = false;
  if(!empty($search['parentid'])) //SD343
  {
    $DB->result_type = MYSQL_ASSOC;
    $parent_page = $DB->query_first('SELECT categoryid,name,parentid FROM {categories} WHERE categoryid = %d',$search['parentid']);
  }

  // LOAD LANGUAGE FOR "Pages"
  $pages_phrases = LoadAdminPhrases(1);

  echo '<h3 class="header blue lighter">'.AdminPhrase('users_edit_usergroup') . ': ' . $usergroup['name'].'</h3>';

  // #########################################################################
  // First function call: display filter bar and list headers
  // #########################################################################
  $DB->result_type = MYSQL_ASSOC;
  $skin_engine_arr = $DB->query_first('SELECT skinid, skin_engine FROM {skins} WHERE activated = 1');
  $skin_id     = $skin_engine_arr['skinid'];
  $skin_engine = $skin_engine_arr['skin_engine'];

  // NOTE: search-related parameters have already been pre-processed at top of file!

  $pagesquery = 'WHERE'; // this MUST be here, do not change/move this line!
  if($customsearch)
  {
    $comparison = ' AND ';
    // search for name?
    if(strlen($search['name']))
    {
      $pagesquery .= ($pagesquery != 'WHERE' ? $comparison:'') . " c.name LIKE '%%" . $search['name'] . "%%'";
    }

    // search for title?
    if(strlen($search['title']))
    {
      $pagesquery .= ($pagesquery != 'WHERE' ? $comparison:'') .
      " ((c.title LIKE '%%" . $search['title'] . "%%') OR (c.urlname LIKE '%%" . $search['title'] . "%%'))";
    }

    // SD343: filter by usergroupid?
    if(!empty($search['usergroupid']))
    {
      $pagesquery .= ($pagesquery != 'WHERE' ? $comparison:'') .
      ' EXISTS(SELECT 1 FROM '.PRGM_TABLE_PREFIX.'usergroups g '.
      " WHERE g.usergroupid = ".(int)$search['usergroupid'].
      " AND CONCAT(',',g.categoryviewids,',') LIKE CONCAT('%,',c.categoryid,'%'))";
    }

    // search for parent id?
    if(!empty($search['parentid']))
    {
      $pagesquery .= ($pagesquery != 'WHERE' ? $comparison:'') . " c.parentid = " . $search['parentid'];
    }

    // search for layout id?
    if(!empty($search['designid']))
    {
      $pagesquery .= ($pagesquery != 'WHERE' ? $comparison:'') . ' c.designid = ' . $search['designid'];
    }

    // search for secure pages (ssl)?
    if($search['secure'] == '0' || $search['secure'] == '1')
    {
      $pagesquery .= ($pagesquery != 'WHERE' ? $comparison:'') . ' c.sslurl = ' . (int)$search['secure'];
    }

    // search for used plugins?
    if(!empty($search['pluginid']))
    {
      $pagesquery .= ($pagesquery != 'WHERE' ? $comparison:'') .
      " EXISTS(SELECT 1 FROM {pagesort} WHERE categoryid = c.categoryid AND pluginid = '" . (string)$search['pluginid'] . "')";
    }

    $customsearch = ($pagesquery != 'WHERE');
  }
  //SD342: only show pages for which the user has access to
  if(!$userinfo['adminaccess'])
  {
    if(empty($userinfo['categoryviewids'])) $userinfo['categoryviewids'] = '0';
    $pagesquery .= ($pagesquery != 'WHERE'?$comparison:'') . ' c.categoryid IN (' . implode(',',$userinfo['categoryviewids']).') ';
  }

  $total_rows = 0;
  if($total_rows_arr = $DB->query_first('SELECT count(*) page_count FROM {categories} c '.
                                        ($customsearch ? $pagesquery : '')))
  {
    $total_rows = $total_rows_arr['page_count'];
  }
  else
  {
    DisplayMessage($pages_phrases['no_pages_exist']);
    return;
  }

  // ######################################################################
  // DISPLAY FILTER BAR
  // ######################################################################

  echo '
    <img alt="" src="' .ADMIN_IMAGES_FOLDER. 'ceetip_arrow.png" style="display:none;" width="21" height="11" />
    <form action="usergroups.php?action=editpagepermissions" id="filterpages" name="filterpages" method="post" style="position:relative;z-index:1;">
    <input type="hidden" name="customsearch" value="0" />
    <input type="hidden" name="clearsearch" value="0" />
    <input type="hidden" name="usergroupid" value="'.$usergroupid.'" />
    <input type="hidden" name="page" value="'.$page.'" />
    ';

  StartSection($pages_phrases['filter_pages']);

  echo '
    <input type="hidden" name="customsearch" value="1" />
    <table class="table table-bordered">
	<thead>
    <tr>
      <th class="tdrow1">' . $pages_phrases['pages_name'] . '</th>
      <th class="tdrow1">' . $pages_phrases['pages_title'] . '</th>
      <th class="tdrow1" width="250">' . $pages_phrases['pages_parent_page'] . '</th>
      <th class="tdrow1">' . $pages_phrases['pages_skin_layout'] . '</th>
      <th class="tdrow1">' . $pages_phrases['pages_plugins'] . '</th>
      <th class="tdrow1" width="68">' . $pages_phrases['pages_secure'] . '</th>
      <th class="tdrow1">' . $pages_phrases['pages_filter'].'</th>
    </tr>
	</thead>
	<tbody>
    <tr>
      <td class="tdrow2" width="100"><input type="text" name="searchname"  value="' . $search['name'] . '" size="10" style="width: 85%; margin-right:14px" /></td>
      <td class="tdrow2" width="100"><input type="text" name="searchtitle" value="' . $search['title'] . '" size="10" style="width: 85%; margin-right:14px" /></td>
      <td class="tdrow2" width="160">';

  // #####################################
  // Filter by parent category
  // (see function_admins.php for details)
  // #####################################
  DisplayCategorySelection($search['parentid'], 1, 0, '', 'searchparentid', 'width: 254px; font-size: 12px;');

  echo '</td>';

  // #####################################
  // Filter by used design
  // #####################################
  echo '
      <td class="tdrow2">
        <select id="searchdesignid" name="searchdesignid" class="form-control">
        <option value="0"'.(empty($search['designid'])?' selected="selected"':'').'>-</option>';
  if($get_designs = $DB->query('SELECT designid, design_name FROM {designs}
                                WHERE skinid = %d
                                ORDER BY designid', $skin_id))
  {
    while($design_arr = $DB->fetch_array($get_designs,null,MYSQL_ASSOC))
    {
      echo '
        <option value="'.$design_arr['designid'].'" ' . ($search['designid']==$design_arr['designid']?' selected="selected" ':'').'>';
      if(strlen($design_arr['design_name']))
      {
        echo $design_arr['design_name'];
      }
      else
      {
        echo $pages_phrases['pages_skin_layout'].' '.$design_arr['designid'];
      }
      echo '</option>';
    }
  }
  echo '
        </select>
      </td>';

  // #####################################
  // Filter by plugin used on page
  // #####################################
  echo '
      <td class="tdrow2">
        <select id="searchpluginid" name="searchpluginid" class="form-control">
          <option value="0"'.(empty($search['pluginid'])?'selected="selected"':'').'>-</option>';

  if($get_plugins = GetPluginListForPage(0, true, true))
  {
    $separator = false;
    while($plugin_arr = $DB->fetch_array($get_plugins,null,MYSQL_ASSOC))
    {
      if($plugin_arr['pluginid'] != '1') // do not list "empty" plugin
      {
        $pname = ($plugin_arr['isplugin'] && isset($plugin_names[$plugin_arr['pluginid']]) ? $plugin_names[$plugin_arr['pluginid']] : $plugin_arr['displayname']);
        //SD342: added separator for custom plugins
        if(!$separator && !$plugin_arr['isplugin'])
        {
          echo '
          <option value="-" style="font-weight: bold; background-color:#B0B0B0">---- ' . AdminPhrase('common_custom_plugins') . ' ----</option>';
          $separator = true;
        }
        echo '
          <option value="'.$plugin_arr['pluginid']. '"' . ($search['pluginid']==$plugin_arr['pluginid']?' selected="selected"':'') .
          '>'.$pname.' ('.$plugin_arr['pluginid'].')</option>';
      }
    }
  }
  echo '
        </select>
      </td>
      <td class="tdrow2" align="center" width="68">
        <select name="searchsecure" class="form-control">
          <option value="all" '.($search['secure'] == 'all'?'selected="selected"':'') .'>-</option>
          <option value="0" ' . (empty($search['secure'])  ?'selected="selected"':'') .'>'.AdminPhrase('common_no').'</option>
          <option value="1" ' . ($search['secure'] == '1'  ?'selected="selected"':'') .'>'.AdminPhrase('common_yes').'</option>
        </select>
      </td>
      <td class="align-middle center" width="5%">
        <input type="submit" value="'.$pages_phrases['pages_filter'].'" style="display: none;" />
		 <a id="pages-submit-filter" href="#" onclick="return false;" title="'.$pages_phrases['pages_apply_filter'].'"><i class="ace-icon fa fa-search blue bigger-120"></i></a> &nbsp;
        <a id="pages-clear-filter" href="#" onclick="return false;" title="'.$pages_phrases['pages_remove_filter'].'"><i class="ace-icon fa fa-trash-o red bigger-120"></i></a>
       
      </td>
    </tr>
	</tbody>
    </table>
	</div>
    ';

  echo '
  </form>
  <!-- end of search bar -->
  <div id="editsave" style="display:none;"></div>
  ';

  // ########################################################################
  // PAGES TABLE HEADER
  // ########################################################################
  echo '
  <form action="usergroups.php" id="usergroups" name="usergroups" method="post">
  '.PrintSecureToken().'
  <input type="hidden" name="usergroupid" value="'.$usergroupid.'" />
  <input type="hidden" name="page" value="'.$page.'" />
  ';

  StartSection($pages_phrases['pages_pages'] . ' ('.$total_rows.')');

  echo '
  <table class="table table-bordered table-striped">
  <thead>';

  if(is_array($parent_page))
  {
    echo '
    <tr><td colspan="5">
      <a class="subpage btn btn-info btn-white" rel="'.(int)$parent_page['parentid'].'" href="#"><i class="ace-icon fa fa-angle-double-left"></i> '.$parent_page['name'].'</a></td></tr>';
  }
  echo '
  <tr>
    <th width="5%" class="center"><i class="ace-icon fa fa-angle-double-right"></i> </th>
    <th width="35%">Page</th>
    <th align="center" width="20%"><input type="checkbox" class="ace" checkall="display" onclick="javascript: return select_deselectAll (\'usergroups\', this, \'display\');" /><span class="lbl"></span> ' . AdminPhrase('common_display_in_menu') . '</th>
    <th align="center" width="25%"><input type="checkbox" class="ace" checkall="mobile" onclick="javascript: return select_deselectAll (\'usergroups\', this, \'mobile\');" /><span class="lbl"></span> ' . AdminPhrase('common_display_in_mobile_menu') . '</th>
    <th class="center" width="10%"><input type="checkbox" class="ace" checkall="view" onclick="javascript: return select_deselectAll (\'usergroups\', this, \'view\');" /><span class="lbl"></span> ' . AdminPhrase('common_allow_view') . '</th>
  </tr>
  </thead>
  <tbody>';


  // If no pages were found (due to filtering), then just display a message
  if(empty($total_rows))
  {
    echo '</table></div>';
    if($customsearch)
    {
      DisplayMessage($pages_phrases['no_pages_found'], false);
    }
    else
    {
      DisplayMessage($pages_phrases['no_pages_exist'], true);
    }
    return false;
  }

  // ##########################################################################
  // ##########################################################################
  // MAIN DATA LOOP
  // ##########################################################################
  // ##########################################################################
  $unlistedcategories = array();
  $sql = ' FROM '.PRGM_TABLE_PREFIX.'categories c'.
         ' LEFT JOIN '.PRGM_TABLE_PREFIX.'categories c2 ON c2.categoryid = c.parentid '.
         ' LEFT JOIN '.PRGM_TABLE_PREFIX.'designs d ON d.designid = c.designid '.
         $pagesquery;
  if(!$customsearch) $sql .= ($pagesquery == 'WHERE' ? '' : ' AND ');
  if(!$customsearch)
  {
    $sql .= ' ((c.parentid = '.$search['parentid'].') OR (c.parentid > 0 AND c2.categoryid IS NULL)) ';
  }

  $categoryviewids = (empty($usergroup['categoryviewids']) ? array(1): @explode(',', $usergroup['categoryviewids']));
  if(count($categoryviewids)) $categoryviewids = array_flip($categoryviewids);
  $categorymenuids = (empty($usergroup['categorymenuids']) ? array(1): @explode(',', $usergroup['categorymenuids']));
  if(count($categorymenuids)) $categorymenuids = array_flip($categorymenuids);
  $categorymobilemenuids = (empty($usergroup['categorymobilemenuids']) ? array(1): @explode(',', $usergroup['categorymobilemenuids']));
  if(count($categorymobilemenuids)) $categorymobilemenuids = array_flip($categorymobilemenuids);

  //for pagination:
  $total_rows = 0;
  if($total_rows_arr = $DB->query_first('SELECT count(*) page_count '.$sql))
    $total_rows = (int)$total_rows_arr['page_count'];
  $getcategories = $DB->query('SELECT c.*, d.designid, d.design_name, c2.categoryid real_parentid, c2.name parent_name'.
                              $sql.
                              ' ORDER BY c.parentid, c.displayorder, c.name '.
                              $limit);
  while($category = $DB->fetch_array($getcategories,null,MYSQL_ASSOC))
  {
    $categoryid = (int)$category['categoryid'];
    $pagename = (strlen($category['name']) ? htmlspecialchars($category['name'],ENT_COMPAT,SD_CHARSET) : $pages_phrases['pages_untitled']);
    if(strlen($category['name']))
    {
      $listedcategories[] = $category['name'];
      if(!empty($category['parentid']) && !isset($category['real_parentid']))
        $unlistedcategories[] = $category['name'];
    }
    $sub_pages = 0;
    $sub_pages_link = '-';
    $DB->result_type = MYSQL_ASSOC;
    if($total_rows_arr = $DB->query_first('SELECT count(*) page_count FROM {categories} c'.
                                          ' WHERE c.parentid = %d',$categoryid))
    {
      if($sub_pages = (int)$total_rows_arr['page_count'])
      {
        $sub_pages_link = '<a class="subpage btn btn-white btn-sm btn-bold btn-info" rel="'.$categoryid.'" title="'.
                          htmlspecialchars($pages_phrases['view_subpages']).'" href="#">'.
                          $sub_pages.' <i class="ace-icon fa fa-angle-double-right"></i></span></a>';
      }
    }
    echo '
    <tr id="pagerow_'.$categoryid.'">
      <td class="center" width="5%">
        <input type="hidden" name="categoryids[]" value="' . $categoryid  . '" />
        '.$sub_pages_link.'
      </td>
      <td width="35%"><span class="mytooltip" ';
    $titles = array();
    if(strlen($category['title']))
      $titles[] = htmlspecialchars($pages_phrases['pages_title'].': '.$category['title'], ENT_QUOTES);
    if(strlen($category['urlname']))
      $titles[] = htmlspecialchars($pages_phrases['pages_seo_name'].': '.$category['urlname'], ENT_QUOTES);
    if(count($titles)) echo 'title="'.implode(", ",$titles).'" ';
    echo '>&nbsp;' . $pagename . '</span>
      </td>';

    $in_menu   = isset($categorymenuids[$categoryid]) ? 'checked="checked"' : '';
    $in_mobile = isset($categorymobilemenuids[$categoryid]) ? 'checked="checked"' : '';
    $can_view  = isset($categoryviewids[$categoryid]) ? 'checked="checked"' : '';
    echo '
      <td width="20%" align="center"><input type="checkbox" class="ace" name="categorymenuids[]" value="'.$categoryid.'" '.$in_menu.' checkme="display" /><span class="lbl"></span></td>
      <td width="20%" align="center"><input type="checkbox" class="ace" name="categorymobilemenuids[]" value="'.$categoryid.'" '.$in_mobile.' checkme="mobile" /><span class="lbl"></span></td>
      <td width="20%" align="center"><input type="checkbox" class="ace" name="categoryviewids[]" value="'.$categoryid.'" '.$can_view.' checkme="view" /><span class="lbl"></span></td>
    </tr>';

    if(!$customsearch)
    {
      if(count($listedcategories) > $items_per_page) break; //SD343
    }

  } //while

  echo '</tbody></table></div>';

  PrintSubmit('savepagepermissions2',AdminPhrase('users_update_usergroup'),'usergroups','fa-check');

  echo '
  </form>';

  if(!empty($total_rows)) //SD343
  {
    $p = new pagination;
    $p->items($total_rows);
    $p->limit($items_per_page);
    $p->currentPage($page);
    $p->adjacents(8);
    $p->target('usergroups.php?action=editpagepermissions&amp;usergroupid='.$usergroupid);
    $p->show();
    echo '<br />';
  }

} //EditPagePermissions2


// ############################################################################
// DISPLAY USERGROUP FORM
// ############################################################################

function DisplayUsergroupForm()
{
  global $DB, $sdlanguage, $admin_menu_arr, $admin_phrases, $core_pluginids_arr;
  
  // SD313: security check against spam/bot submissions
  if(!CheckFormToken())
  {
    RedirectPage('usergroups.php','<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $usergroupid = GetVar('usergroupid', 0, 'whole_number');

  if($usergroupid)
  {
    $DB->result_type = MYSQL_ASSOC;
    $usergroup = $DB->query_first('SELECT * FROM {usergroups} WHERE usergroupid = %d', $usergroupid);

    $pluginviewids     = empty($usergroup['pluginviewids'])    ? array(1): @explode(',', $usergroup['pluginviewids']);
    $pluginsubmitids   = empty($usergroup['pluginsubmitids'])  ? array() : @explode(',', $usergroup['pluginsubmitids']);
    $plugindownloadids = empty($usergroup['plugindownloadids'])? array() : @explode(',', $usergroup['plugindownloadids']);
    $plugincommentids  = empty($usergroup['plugincommentids']) ? array() : @explode(',', $usergroup['plugincommentids']);
    $pluginmoderateids = empty($usergroup['pluginmoderateids'])? array() : @explode(',', $usergroup['pluginmoderateids']); //SD322
    $pluginadminids    = empty($usergroup['pluginadminids'])   ? array() : @explode(',', $usergroup['pluginadminids']);

    $custompluginviewids  = empty($usergroup['custompluginviewids']) ? array() : @explode(',', $usergroup['custompluginviewids']);
    $custompluginadminids = empty($usergroup['custompluginadminids'])? array() : @explode(',', $usergroup['custompluginadminids']);
    $admin_access_pages   = empty($usergroup['admin_access_pages'])  ? array() : @unserialize($usergroup['admin_access_pages']); //SD322
  }
  else
  {
    $usergroup = array('usergroupid'           => 0,
                       'name'                  => '',
                       'color_online'          => '',
                       'description'           => '',
                       'displayname'           => '',
                       'adminaccess'           => 0,
                       'offlinecategoryaccess' => 0,
                       'commentaccess'         => 0,
                       'banned'                => 0,
                       'maintain_customplugins'=> 0,
                       'require_vvc'           => 0);

    $pluginviewids = $pluginsubmitids = $plugindownloadids = $plugincommentids =
    $pluginmoderateids = $pluginadminids = $custompluginviewids = $custompluginadminids =
    $admin_access_pages = array();
  }

  // multi dimensional arrays
  $plugin_permissions_arr = array();
  $plugin_permissions_arr['pluginviewids']     = $pluginviewids;
  $plugin_permissions_arr['pluginsubmitids']   = $pluginsubmitids;
  $plugin_permissions_arr['plugindownloadids'] = $plugindownloadids;
  $plugin_permissions_arr['plugincommentids']  = $plugincommentids;
  $plugin_permissions_arr['pluginmoderateids'] = $pluginmoderateids; //SD322
  $plugin_permissions_arr['pluginadminids']    = $pluginadminids;

  $plugin_permissions_arr['custompluginviewids']  = $custompluginviewids;
  $plugin_permissions_arr['custompluginadminids'] = $custompluginadminids;
  
 echo '<h3 class="header blue lighter">' . ($usergroupid ? AdminPhrase('users_edit_usergroup') : AdminPhrase('users_create_usergroup')) . '</h3>';
 
   echo'<form method="post" action="usergroups.php?action=saveusergroup" id="usergroups" name="usergroups" class="form-horizontal">
  '.PrintSecureToken();
 
  echo '
  		<ul class="nav nav-tabs" role="tablist">
			<li class="active">
				<a href="#main" data-toggle="tab">
				<i class="blue ace-icon fa fa-info-circle bigger-120"></i> '.AdminPhrase('usergroup_info').'</a>
			</li>
			<li>
				<a href="#ugperms" data-toggle="tab">
				<i class="blue ace-icon fa fa-key bigger-120"></i> '.AdminPhrase('usergroup_plugin_permissions').'</a>
			</li>
			<li>
				<a href="#commentperms" data-toggle="tab">
				<i class="blue ace-icon fa fa-comment bigger-120"></i> '.AdminPhrase('usergroup_comment_permissions').'</a>
			</li>
			<li>
				<a href="#extendedperms" data-toggle="tab">
				<i class="blue ace-icon fa fa-arrows-h bigger-120"></i> '.AdminPhrase('usergroup_extended_permissions').'</a>
			</li>
		</ul>';

  echo '
  <div class="tab-content">
  	 <div class="tab-pane in active" id="main">
  <div class="form-group">
  	<label class="control-label col-sm-2" for="name">' . AdminPhrase('users_usergroup_name') . '</label>
	<div class="col-sm-9">
		<input type="hidden" name="usergroupid" value="'.$usergroupid.'" />
      	<input type="text" name="name" maxlength="100" value="'.$usergroup['name'].'" class="form-control" />
		<span class="helper-text">' . AdminPhrase('users_usergroup_name_descr') . '</span>';
		if(!empty($usergroupid))
  {
    echo '
      <br />
      <p style="padding:6px"><a class="round_button" href="usergroups.php?action=editpagepermissions&amp;usergroupid='.
        $usergroupid.PrintSecureUrlToken().
        '"><span class="sprite sprite-key"></span> '.AdminPhrase('users_page_permissions').'</a></p>
      <p style="padding:6px">'.AdminPhrase('usergroup_submit_first').'</p>';
  }
  echo'
	</div>
</div>';

  echo '
  <div class="form-group has-error">
  	<label class="control-label col-sm-2" for="name">'.AdminPhrase('users_full_admin').'
	<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="' . AdminPhrase('users_full_admin_descr') . '" title="Help">?</span>
	</label>
	<div class="col-sm-9">
		<input type="radio" class="ace" name="adminaccess" value="1" ' .
        (!empty($usergroup['adminaccess']) ? 'checked="checked"' : '') . ' /><span class="lbl"> ' . AdminPhrase('common_yes') . '</br>
         <input type="radio" name="adminaccess" class="ace" value="0" ' . (empty($usergroup['adminaccess']) ? 'checked="checked"' : '') . ' /><span class="lbl"> ' . AdminPhrase('common_no') . '</span>
	</div>
</div>';

	echo '
	<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('users_pages_admin_access') . '
		<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="' . AdminPhrase('users_pages_admin_access_descr') . '" title="Help">?</span>
		</label>
		<div class="col-sm-9">';

  //SD322: acess to specific pages if not full admin:
  $tmp = array();
  foreach($admin_menu_arr AS $page_name => $page_filename)
  {
    if(is_array($page_filename))
    {
	  $dupcheck = '';
      foreach($page_filename AS $page_name2 => $page_filename2)
      {
		if($dupcheck != $page_name)
		{
        	$tmp[$page_name.'|'.$page_name2] = $page_filename2;
		}
		$dupcheck = $page_name;
      }
    }
    else 
	{
		$tmp[$page_name] = $page_filename;
	}
  }
  
  echo '<select name="admin_access_pages[]" multiple="multiple" size="9" class="form-control">';
  foreach($tmp AS $page_name => $page_filename)
  {
    if(false !== ($pos = strpos($page_name,'|')))
    {
      $menu_item_name = AdminPhrase('menu_'.strtolower(substr($page_name,0, $pos)));
      $page_name = substr($page_name,0,$pos);
    }
    else
      $menu_item_name = AdminPhrase('menu_'.strtolower($page_name));
    if(empty($menu_item_name) || (substr($menu_item_name,0,9) == 'Undefined'))
    {
      $menu_item_name = $page_name;
    }
    echo '
      <option value="'.$page_name.'" '.
      (is_array($admin_access_pages) && in_array($page_name, $admin_access_pages)?'selected="selected"':'').'>'.$menu_item_name.'</option>';
  }

  $color_online = $usergroup['color_online'];
  if(substr($color_online,0,1)=='#')
  {
    $color_online = substr($color_online,1,6);
  }
  echo '
      </select>
	 </div>
	</div>';
	
	
	echo'
	<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('users_usergroup_displayname') . '
		<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="' . AdminPhrase('users_usergroup_displayname_descr') . '" title="Help">?</span>
		</label>
		<div class="col-sm-9">
      	<input type="text" name="displayname" class="form-control" value="' . $usergroup['displayname'] . '" />
	</div>
</div>';

	echo'
	<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('users_usergroup_displaycolor') . '
		<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="' . AdminPhrase('users_usergroup_displayname_descr') . '" title="Help">?</span>
		</label>
		<div class="col-sm-9">
      	<input type="text" class="colorpicker" id="colorpicker" name="color_online" value="' . $color_online . '" maxlength="7" style="width: 55px;" />
	</div>
</div>';

	echo'
	<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('usergroup_description') . '
		<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="'.AdminPhrase('users_usergroup_description_descr').'" title="Help">?</span>
		</label>
		<div class="col-sm-9">';
  			PrintWysiwygElement('description', $usergroup['description'], 5, 100);
  echo '
  		</div>
	</div>
    <div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('users_require_vvc') . '
		<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="'.AdminPhrase('users_usergroup_description_descr').'" title="Help">?</span>
		</label>
		<div class="col-sm-9">
      <input type="radio" name="require_vvc" class="ace" value="1" ' .
        (!empty($usergroup['require_vvc']) ? 'checked="checked"' : '') .' /><span class="lbl"> ' . AdminPhrase('common_yes') .
        '</span><br/> <input type="radio" name="require_vvc" class="ace" value="0" ' .
        (empty($usergroup['require_vvc']) ? 'checked="checked"' : '') . ' /><span class="lbl"> ' . AdminPhrase('common_no') . '</span>
    </div>
	</div>';
	
	echo'
	<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('plugin_excerpt_mode') . '
		<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="' . AdminPhrase('plugin_excerpt_mode_desc') . '" title="Help">?</span>
		</label>
		<div class="col-sm-9">
      	<input type="radio" name="excerpt_mode" class="ace" value="1" ' .
        (!empty($usergroup['excerpt_mode']) ? 'checked="checked"' : '') .' /><span class="lbl"> ' . AdminPhrase('common_yes') .
        '</span></br /> <input type="radio" name="excerpt_mode" class="ace" value="0" ' .
        (empty($usergroup['excerpt_mode']) ? 'checked="checked"' : '') . ' /><span class="lbl"> ' . AdminPhrase('common_no') . '</span>
	</div>
</div>';

	echo'
	<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('plugin_excerpt_message') . '
		<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="'.AdminPhrase('plugin_excerpt_message_descr').'" title="Help">?</span>
		</label>
		<div class="col-sm-9">';
  			PrintWysiwygElement('excerpt_message',(isset($usergroup['excerpt_message'])?$usergroup['excerpt_message']:''), 5, 100);
  echo '</div>
 	</div>
    <div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('plugin_excerpt_length') . '
		<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="' . AdminPhrase('plugin_excerpt_length_descr') . '" title="Help">?</span>
		</label>
		<div class="col-sm-9">
      		<input type="text" class="form-control" name="excerpt_length" maxlength="4" size="4" value="' .
        (isset($usergroup['excerpt_length'])?(int)$usergroup['excerpt_length']:0) . '" />
		</div>
	</div>
    <div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('users_offline_access') . '
		<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="' . AdminPhrase('users_offline_access_descr') . '" title="Help">?</span>
		</label>
		<div class="col-sm-9">
		 <input type="radio" name="offlinecategoryaccess" class="ace" value="1" ' .
        (!empty($usergroup['offlinecategoryaccess']) ? 'checked="checked"' : '') .' /><span class="lbl"> ' . AdminPhrase('common_yes') .
        '</span><br /> <input type="radio" name="offlinecategoryaccess" class="ace" value="0" ' .
        (empty($usergroup['offlinecategoryaccess']) ? 'checked="checked"' : '') . ' /><span class="lbl">' . AdminPhrase('common_no') . '</span>
		</div>
	</div>';
	
	echo'
	 <div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('users_author_mode') . '
		<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="' . AdminPhrase('users_author_mode_descr') . '" title="Help">?</span>
		</label>
		<div class="col-sm-9">
		 <input type="radio" class="ace" name="articles_author_mode" value="1" ' .
        (!empty($usergroup['articles_author_mode']) ? 'checked="checked"' : '') .' /> <span class="lbl">' . AdminPhrase('common_yes') .
        '</span><br /> <input type="radio" class="ace" name="articles_author_mode" value="0" ' . (empty($usergroup['articles_author_mode']) ? 'checked="checked"' : '') . ' /><span class="lbl"> ' . AdminPhrase('common_no') . '</span>
	</div>
</div>';

echo'
    <div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('users_maintain_custom_plugins') . '
		<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="' . AdminPhrase('users_maintain_custom_plugins_descri') . '" title="Help">?</span>
		</label>
		<div class="col-sm-9">
      <input type="radio" class="ace" name="maintain_customplugins" value="1" ' .
        (!empty($usergroup['maintain_customplugins']) ? 'checked="checked"' : '') .' /><span class="lbl"> ' . AdminPhrase('common_yes') .
        '</span><br /> <input type="radio" name="maintain_customplugins" class="ace" value="0" ' . (empty($usergroup['maintain_customplugins']) ? 'checked="checked"' : '') . ' /><span class="lbl"> ' . AdminPhrase('common_no') . '</span>
	</div>
</div>';

	echo'
	<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('users_banned') . '
		<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="' . AdminPhrase('users_group_banned_descr') . '" title="Help">?</span>
		</label>
		<div class="col-sm-9">
      <input type="radio" name="banned" class="ace" value="1" ' . (!empty($usergroup['banned']) ? 'checked="checked"' : '') .' /><span class="lbl"> ' . AdminPhrase('common_yes') .
      '</span></br /> <input type="radio" name="banned" class="ace" value="0" ' . (empty($usergroup['banned']) ? 'checked="checked"' : '') . ' /><span class="lbl"> ' . AdminPhrase('common_no') . '</span>
    </div>
</div>
</div>
 <div class="tab-pane" id="ugperms">';

  StartTable(AdminPhrase('users_plugin_permissions'), array('table', 'table-bordered', 'table-striped'));

  // CORE PLUGINS
  // Get Main Plugins
  $core_pluginids = implode(',',$core_pluginids_arr);
  if($forum_id = GetPluginID('Forum'))
  {
    $core_pluginids .= ','.$forum_id;
  }
  $plugins = $DB->query("SELECT pluginid, name, settings FROM {plugins} WHERE (pluginid <> 1) AND (pluginid in (".$core_pluginids.")) ORDER BY name");
  DisplayPluginsPermissions($plugins, $plugin_permissions_arr, 'common_main_plugins', 'main');

  // CLONED PLUGINS
  $plugins = $DB->query("SELECT pluginid, name, settings FROM {plugins} WHERE (pluginid <> 1) AND (authorname = 'subdreamer_cloner') ORDER BY name");
  DisplayPluginsPermissions($plugins, $plugin_permissions_arr, 'common_cloned_plugins', 'clone');

  // CUSTOM PLUGINS
  $plugins = $DB->query("SELECT custompluginid, name, settings FROM {customplugins} ORDER BY name");
  DisplayPluginsPermissions($plugins, $plugin_permissions_arr, 'common_custom_plugins', 'custom');

  // DOWNLOADED PLUGINS
  $plugins = $DB->query("SELECT pluginid, name, settings FROM {plugins} WHERE (pluginid <> 1) AND NOT (pluginid in (".$core_pluginids.")) AND (authorname <> 'subdreamer_cloner') ORDER BY name");
  DisplayPluginsPermissions($plugins, $plugin_permissions_arr, 'common_downloaded_plugins', 'downloaded');

  echo '</table></div></div>';


  //###########################################################################
  // Comments-specific permissions and settings (SD343)
  // Requires loading the corresponding usergroup_config row!
  //###########################################################################

  // Make sure, usergroup config rows exist:
  $DB->query("INSERT INTO ".PRGM_TABLE_PREFIX."usergroups_config (usergroupid) SELECT usergroupid FROM ".
             PRGM_TABLE_PREFIX."usergroups ug WHERE NOT EXISTS(SELECT 1 FROM ".
             PRGM_TABLE_PREFIX."usergroups_config uc WHERE uc.usergroupid = ug.usergroupid)");

  $DB->result_type = MYSQL_ASSOC;
  $uc = $DB->query_first('SELECT * FROM {usergroups_config} WHERE usergroupid = %d',$usergroup['usergroupid']);

  echo ' <div class="tab-pane" id="commentperms"><h3 class="header blue lighter">' . AdminPhrase('comments_permissions') . '</h3>';
  echo '
  <div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('users_comment_access') . '
		<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="' . AdminPhrase('users_comment_access_descr') . '" title="Help">?</span>
		</label>
		<div class="col-sm-9">
		<input type="checkbox" class="ace" value="1" name="commentaccess" '.(empty($usergroup['commentaccess'])?'':' checked="checked"').' />
		<span class="lbl"> ' . AdminPhrase('common_yes') . '</span>
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('comments_allow_edit') . '
		</label>
		<div class="col-sm-9">
    	<input type="checkbox" class="ace" value="1" name="edit_own_comments" '.(empty($uc['edit_own_comments'])?'':' checked="checked"').' />
		<span class="lbl"> ' . AdminPhrase('common_yes') . '</span>
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('comments_allow_delete') . '
		</label>
		<div class="col-sm-9">
    	<input type="checkbox" class="ace" value="1" name="delete_own_comments" '.(empty($uc['delete_own_comments'])?'':' checked="checked"').' />
		<span class="lbl"> ' . AdminPhrase('common_yes') . '</span>
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('comments_approve_edits') . '
		</label>
		<div class="col-sm-9">
    	<input type="checkbox" class="ace" value="1" name="approve_comment_edits" '.(empty($uc['approve_comment_edits'])?'':' checked="checked"').' />
		<span class="lbl"> ' . AdminPhrase('common_yes') . '</span>
	</div>
</div></div>';


  //###########################################################################
  // Other userprofile/-group specific permissions and settings (SD342)
  //###########################################################################
	echo ' <div class="tab-pane" id="extendedperms"><h3 class="header blue lighter">' . AdminPhrase('usergroups_extended_permissions') . '</h3>';

  echo '
  <div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('allow_uname_change') . '
		</label>
		<div class="col-sm-9">
  			<input type="checkbox" class="ace" name="allow_uname_change" value="1" '.(!empty($uc['allow_uname_change'])?' checked="checked"':'').'" />
			<span class="lbl"></span>
		</div>
	</div>
  <div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('enable_subscriptions') . '
		</label>
		<div class="col-sm-9">
		<input type="checkbox" class="ace" value="1" name="enable_subscriptions" '.(empty($uc['enable_subscriptions'])?'':' checked="checked"').' />
		<span class="lbl"></span>
	</div>
</div>
<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('msg_enabled') . '
		</label>
		<div class="col-sm-9">
		<input type="checkbox" class="ace" value="1" name="msg_enabled" '.(empty($uc['msg_enabled'])?'':' checked="checked"').' />
		<span class="lbl"></span>
	</div>
</div>
<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('msg_inbox_limit') . '
		</label>
		<div class="col-sm-9">
		<input type="text" size="4" class="form-contorl" maxlength="4" name="msg_inbox_limit" value="'.(empty($uc['msg_inbox_limit'])?50:(int)$uc['msg_inbox_limit']).'" />
	</div>
</div>
<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('msg_recipients_limit') . '
		</label>
		<div class="col-sm-9">
		 <input type="text" size="4" maxlength="4" name="msg_recipients_limit" value="'.(empty($uc['msg_recipients_limit'])?50:(int)$uc['msg_recipients_limit']).'" />
	</div>
</div>
<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('msg_keep_limit') . '
		</label>
		<div class="col-sm-9">
		 <input type="text" size="4" maxlength="4" class="form-control" name="msg_keep_limit" value="'.(empty($uc['msg_keep_limit'])?50:(int)$uc['msg_keep_limit']).'" />
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('enable_attachments') . '
		</label>
		<div class="col-sm-9">
		 <input type="checkbox" class="ace" value="1" name="enable_attachments" '.(empty($uc['enable_attachments'])?'':' checked="checked"').' />
		 <span class="lbl"></span>
	</div>
</div>
  
<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('attachments_extensions') . '
		</label>
		<div class="col-sm-9">
		 <input type="text" name="attachments_extensions" class="form-control" size="40" value="'.(!empty($uc['attachments_extensions'])?(string)$uc['attachments_extensions']:'').'" />
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('attachments_max_size') . '
		</label>
		<div class="col-sm-9">
		 <input type="text" size="6" maxlength="6" name="attachments_max_size" class="form-control" value="'.(empty($uc['attachments_max_size'])?2048:(int)$uc['attachments_max_size']).'" /> KB
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('msg_requirevvc') . '
		</label>
		<div class="col-sm-9">
		 <input type="checkbox" class="ace" value="1" name="msg_requirevvc" '.(empty($uc['msg_requirevvc'])?'':' checked="checked"').' />
		 <span class="lbl"></span>
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('msg_notification') . '
		</label>
		<div class="col-sm-9">
		 <input type="checkbox" class="ace" value="1" name="msg_notification" '.(empty($uc['msg_notification'])?'':' checked="checked"').' />
		 <span class="lbl"></span>
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('sig_enabled') . '
		</label>
		<div class="col-sm-9">
		 <input type="checkbox" class="ace" value="1" name="sig_enabled" '.(empty($uc['sig_enabled'])?'':' checked="checked"').' />
		 <span class="lbl"></span>
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('profile_picture_enabled') . '
		</label>
		<div class="col-sm-9">
		 <label for="uc_up1"><input id="uc_up1" type="checkbox" class="ace" name="picture_enabled" value="1" '.(!empty($uc['pub_img_enabled'])&&($uc['pub_img_enabled']&1)?' checked="checked"':'').' /><span class="lbl"> Gravatar (if enabled in Settings)</span></label><br />
      <label for="uc_up2"><input id="uc_up2" type="checkbox" class="ace" name="picture_upload"  value="2" '.(!empty($uc['pub_img_enabled'])&&($uc['pub_img_enabled']&2)?' checked="checked"':'').' /><span class="lbl"> Image upload</span></label><br />
      <label for="uc_up3"><input id="uc_up3" type="checkbox" class="ace" name="picture_link"    value="4" '.(!empty($uc['pub_img_enabled'])&&($uc['pub_img_enabled']&4)?' checked="checked"':'').' /><span class="lbl"> External link</span></label>
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('picture_path') . '
		</label>
		<div class="col-sm-9">
		 <input type="text" name="picture_path" size="40" value="'.(!empty($uc['pub_img_path'])?(string)$uc['pub_img_path']:'').'" />
		 <span class="lbl"></span>
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('avatar_extensions') . '
		</label>
		<div class="col-sm-9">
		 <input type="text" name="picture_extensions" size="40" value="'.(!empty($uc['pub_img_extensions'])?(string)$uc['pub_img_extensions']:'').'" />
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('avatar_extensions') . '
		</label>
		<div class="col-sm-9">
		 <input type="text" name="picture_extensions" size="40" value="'.(!empty($uc['pub_img_extensions'])?(string)$uc['pub_img_extensions']:'').'" />
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('picture_max_width') . '
		</label>
		<div class="col-sm-9">
		 <input type="text" name="picture_max_width" size="3" maxlength="4" value="'.(!empty($uc['pub_img_max_width'])?(int)$uc['pub_img_max_width']:'120').'" /> Pixels
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('picture_max_height') . '
		</label>
		<div class="col-sm-9">
		 <input type="text" name="picture_max_height" size="3" maxlength="4" value="'.(!empty($uc['pub_img_max_height'])?(int)$uc['pub_img_max_height']:'120').'" /> Pixels
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('picture_max_size') . '
		</label>
		<div class="col-sm-9">
		 <input type="text" name="picture_max_size" size="7" maxlength="8" value="'.(!empty($uc['pub_img_max_size'])?(int)$uc['pub_img_max_size']:'102400').'" /> Bytes
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('avatar_enabled') . '
		</label>
		<div class="col-sm-9">
		  <label for="uc_av1"><input id="uc_av1" type="checkbox" class="ace" name="avatar_enabled" value="1" '.(!empty($uc['avatar_enabled'])&&($uc['avatar_enabled']&1)?' checked="checked"':'').' /><span class="lbl"> Gravatar (if enabled in Settings)</span></label><br />
      <label for="uc_av2"><input id="uc_av2" type="checkbox" class="ace" name="avatar_upload"  value="2" '.(!empty($uc['avatar_enabled'])&&($uc['avatar_enabled']&2)?' checked="checked"':'').' /><span class="lbl"> Image upload</span></label><br />
      <label for="uc_av3"><input id="uc_av3" type="checkbox" class="ace" name="avatar_link"    value="4" '.(!empty($uc['avatar_enabled'])&&($uc['avatar_enabled']&4)?' checked="checked"':'').' /><span class="lbl"> External link</span></label>
	</div>
</div>
  
  
  <div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('hide_avatars_guests') . '
		</label>
		<div class="col-sm-9">
		 <input type="checkbox" class="ace" name="hide_avatars_guests" value="1"'.(empty($uc['hide_avatars_guests'])?'':' checked="checked"').'" />
		 <span class="lbl"></span>
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('avatar_path') . '
		</label>
		<div class="col-sm-9">
		 <input type="text" name="avatar_path" size="40" value="'.(!empty($uc['avatar_path'])?(string)$uc['avatar_path']:'').'" />
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('avatar_extensions') . '
		</label>
		<div class="col-sm-9">
		 <input type="text" name="avatar_extensions" size="40" value="'.(!empty($uc['avatar_extensions'])?(string)$uc['avatar_extensions']:'').'" />
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('avatar_max_width') . '
		</label>
		<div class="col-sm-9">
		 <input type="text" name="avatar_max_width" size="3" maxlength="4" value="'.(!empty($uc['avatar_max_width'])?(int)$uc['avatar_max_width']:'80').'" /> Pixels
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('avatar_max_height') . '
		</label>
		<div class="col-sm-9">
		 <input type="text" name="avatar_max_height" size="3" maxlength="4" value="'.(!empty($uc['avatar_max_height'])?(int)$uc['avatar_max_height']:'80').'" /> Pixels
	</div>
</div>

<div class="form-group">
		<label class="control-label col-sm-2" for="admin_access_pages">' . AdminPhrase('avatar_max_size') . '
		</label>
		<div class="col-sm-9">
		 <input type="text" name="avatar_max_size" size="6" maxlength="7" value="'.(!empty($uc['avatar_max_size'])?(int)$uc['avatar_max_size']:'20480').'" /> Bytes
	</div>
</div></div></div>
<div class="space-30"></div>';


  PrintSubmit('saveusergroup',AdminPhrase($usergroupid?'users_update_usergroup':'users_create_usergroup'),'usergroups','fa-check');

  // INITIALISE CHECKBOX GROUPS
  echo '
  </form>

<script type="text/javascript">
//<![CDATA[
function initGroups() {
  initCheckboxGroup("usergroups", "view_main");
  initCheckboxGroup("usergroups", "submit_main");
  initCheckboxGroup("usergroups", "download_main");
  initCheckboxGroup("usergroups", "moderate_main");
  initCheckboxGroup("usergroups", "admin_main");

  initCheckboxGroup("usergroups", "view_cloned");
  initCheckboxGroup("usergroups", "submit_cloned");
  initCheckboxGroup("usergroups", "download_cloned");
  initCheckboxGroup("usergroups", "moderate_cloned");
  initCheckboxGroup("usergroups", "admin_cloned");

  initCheckboxGroup("usergroups", "view_custom");
  initCheckboxGroup("usergroups", "admin_custom");

  initCheckboxGroup("usergroups", "view_downloaded");
  initCheckboxGroup("usergroups", "submit_downloaded");
  initCheckboxGroup("usergroups", "download_downloaded");
  initCheckboxGroup("usergroups", "moderate_downloaded");
  initCheckboxGroup("usergroups", "admin_downloaded");
}

jQuery(document).ready(function() {
  initGroups();
});
// ]]>
</script>';

} //DisplayUsergroupForm


// ############################################################################
// SELECT FUNCTION
// ############################################################################

//SD343: auto-switch to enhanced pages display for large Pages volume
if(in_array($action,array('editpagepermissions','savepagepermissions')))
{
  $UseEnhanced = (defined('SD_ENHANCED_PAGES') && SD_ENHANCED_PAGES);
  if(!$UseEnhanced)
  {
    $ug_count = $DB->query_first('SELECT COUNT(*) ugcount FROM {usergroups} LIMIT 1');
    if($ug_count['ugcount'] > 500) $UseEnhanced = true;
  }
  if($UseEnhanced) $action .= '2';
}

$function_name = str_replace('_', '', $action);

if(is_callable($function_name))
{
  call_user_func($function_name);
}
else
{
  DisplayMessage("Incorrect Function Call: $function_name()", true);
}


// ############################################################################
// DISPLAY ADMIN FOOTER
// ############################################################################

DisplayAdminFooter();
