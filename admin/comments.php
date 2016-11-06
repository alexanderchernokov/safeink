<?php

// ############################################################################
// DEFINE CONSTANTS
// ############################################################################

define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');

// ############################################################################
// INIT PRGM
// ############################################################################

include(ROOT_PATH . 'includes/init.php');

define('COMMENTS_DATE_SORT_DESC',   'date DESC');
define('COMMENTS_DATE_SORT_ASC',    'date ASC');
define('COMMENTS_IP_SORT_ASC',      'ipaddress ASC');
define('COMMENTS_IP_SORT_DESC',     'ipaddress DESC');
define('COMMENTS_UNAME_SORT_ASC',   'username ASC');
define('COMMENTS_UNAME_SORT_DESC',  'username DESC');

$search = array();
$customsearch = GetVar('customsearch', 0, 'bool');
$clearsearch  = GetVar('clearsearch',  0, 'bool');

// Restore previous search array from cookie
$search_cookie = isset($_COOKIE[COOKIE_PREFIX . '_comments_search']) ? $_COOKIE[COOKIE_PREFIX . '_comments_search'] : false;

if($clearsearch)
{
  $search_cookie = false;
  $customsearch  = false;
}

if($customsearch)
{
  $search = array('comment'   => GetVar('searchcomment', '', 'string', true, false),
                  'approved'  => GetVar('searchapproved', 'all', 'string', true, false),
                  'username'  => GetVar('searchusername', '', 'string', true, false),
                  'pluginid'  => GetVar('searchpluginid', '', 'string', true, false),
                  'limit'     => GetVar('searchlimit', 20, 'integer', true, false),
                  'sorting'   => GetVar('searchsorting', '', 'string', true, false),
                  'ipaddress' => GetVar('ipaddress', '', 'string', true, false));

  // Store search params in cookie
  sd_CreateCookie('_comments_search',base64_encode(serialize($search)));
}
else
{
  $searchpluginid = GetVar('pluginid',   0, 'whole_number');
  if(!empty($searchpluginid))
  {
    $search['pluginid'] = $searchpluginid;
  }

  if($search_cookie !== false)
  {
    $search = unserialize(base64_decode(($search_cookie)));
  }
}

if(empty($search) || !is_array($search))
{
  $search = array('comment'  => '',
                  'approved' => 'all',
                  'username' => '',
                  'pluginid' => 0,
                  'limit'    => 20,
                  'sorting'  => '',
                  'ipaddress'  => '');

  // Remove search params cookie
  sd_CreateCookie('_comments_search', '');
}

$search['comment']  = isset($search['comment'])  ? (string)$search['comment'] : '';
$search['approved'] = isset($search['approved']) ? (string)$search['approved'] : 'all';
$search['username'] = isset($search['username']) ? (string)$search['username'] : '';
$search['pluginid'] = isset($search['pluginid']) ? (int)$search['pluginid'] : '';
$search['limit']    = isset($search['limit'])    ? Is_Valid_Number($search['limit'],20,5,100) : 20;
$search['sorting']  = empty($search['sorting']) ? COMMENTS_DATE_SORT_DESC : $search['sorting'];
//SD343: added sorting by IP
$sort = in_array($search['sorting'], array(COMMENTS_DATE_SORT_ASC,
                                           COMMENTS_DATE_SORT_DESC,
                                           COMMENTS_IP_SORT_ASC,
                                           COMMENTS_IP_SORT_DESC,
                                           COMMENTS_UNAME_SORT_ASC,
                                           COMMENTS_UNAME_SORT_DESC)) ? $search['sorting'] : COMMENTS_DATE_SORT_DESC;
$search['sorting'] = $sort;
$search['ipaddress']= isset($search['ipaddress']) ? (string)$search['ipaddress'] : '';

// ############################################################################
// LOAD ADMIN LANGUAGE
// ############################################################################
$admin_phrases = LoadAdminPhrases(4);



$js_arr = array();
$js_arr[] = ROOT_PATH . MINIFY_PREFIX_F . 'includes/javascript/markitup/markitup-full.js';

sd_header_add(array(
  'js'    => $js_arr,
   // NO ROOT_PATH for "css" entries!
  'css'   => array(SD_JS_PATH . 'markitup/skins/markitup/style.css',
                   SD_JS_PATH . 'markitup/sets/bbcode/style.css'),
  // SD370: added "checkall" code
  'other' => array('
<script type="text/javascript">
//<![CDATA[
if (typeof(jQuery) !== "undefined") {
jQuery(document).ready(function() {
  (function($){
    $("form#searchcomments select").change(function() {
      $("#searchcomments").submit();
    });

    $("a#comments-submit-search").click(function() {
      $("#searchcomments").submit();
    });
	
	$("[data-rel=popover]").popover({container:"body"});
	// $("select[multiple]").chosen();
	$(".mytooltip").tooltip();

    $("a#comments-clear-search").click(function() {
      $("#searchcomments input").val("");
      $("#searchcomments select").prop("selectedIndex", 0);
      $("#searchcomments input[name=clearsearch]").val("1");
      $("#searchcomments").submit();
    });

   $(".bbcode").markItUp(myBbcodeSettings);
   
   $("#submit_comments").on("click", function(e) {
	  	e.preventDefault();
  		bootbox.confirm("'.AdminPhrase('comments_confirm_delete_comments') .'", function(result) {
    	if(result) {
       		$("form#comments").submit();
    	}
  		});
	});

    $("form#comments").delegate("a#checkall","click",function(e){
      e.preventDefault();
      var ischecked = 1 - parseInt($(this).attr("rel"),10);
      if(ischecked==1) {
        $("form#comments input.deletecomment").attr("checked","checked");
        $("form#comments tr:not(thead tr)").addClass("danger");
      } else {
        $("form#comments input.deletecomment").removeAttr("checked");
        $("form#comments tr").removeClass("danger");
      }
      $(this).attr("rel",ischecked);
      return false;
    });
    $("input[type=checkbox].deletecomment").on("change",function(){
      var tr = $(this).parents("tr");
      $(tr).toggleClass("danger");
    });
	
	

  }(jQuery));
});
}
//]]>
</script>')
), false);


// ############################################################################
// GET/POST VARS
// ############################################################################
$action    = GetVar('action', 'display_comments', 'string');
$commentid = GetVar('commentid', 0, 'whole_number');
$pluginid  = GetVar('pluginid', 0, 'whole_number');

// ############################################################################
// DISPLAY ADMIN HEADER
// ############################################################################
DisplayAdminHeader(array(AdminPhrase('comments_comments'), $action));


// ############################################################################
// CHECK PAGE ACCESS
// ############################################################################
CheckAdminAccess('comments');

// ############################################################################
// UPDATE COMMENT
// ############################################################################

function UpdateComment()
{
  global $DB, $SDCache;

  $commentid = GetVar('commentid', 0, 'whole_number');
  $plugin_id = GetVar('plugin_id', 0, 'whole_number');
  $object_id = GetVar('object_id', 0, 'whole_number');
  $original_status = GetVar('original_status', 0, 'whole_number');
  $comment   = GetVar('comment', '', 'string');
  $username  = GetVar('username', '', 'string');
  $username  = sd_substr($username,0,64);
  $approved  = GetVar('approved', 0, 'bool');
  $ipaddress = GetVar('ipaddress', '', 'string');
  $ipaddress = substr($ipaddress,0,32);
  $deletecomment = GetVar('deletecomment', 0, 'bool');

  if(($plugin_id < 1) || ($plugin_id > 999999) || ($object_id < 1) || ($commentid < 1)) return;

  if($deletecomment)
  {
    $DB->query('DELETE FROM {comments} WHERE commentid = %d',$commentid);
    $DB->query('UPDATE {comments_count} SET count = (IFNULL(count,1)-1)
                WHERE plugin_id = %d AND object_id = %d AND (count > 0)',
                $plugin_id, $object_id);
  }
  else
  {
    $DB->query("UPDATE {comments}
                SET comment = '%s', username = '%s', ipaddress = '%s', approved = %d
                WHERE commentid = %d",
                $comment, $username, $ipaddress, $approved, $commentid);

    if($original_status != $approved) // if approved status has changed
    {
      if($original_status == 0) // meaning the comment was unapproved before
      {
        $DB->query('UPDATE {comments_count} SET count = (IFNULL(count,0)+1)
                    WHERE plugin_id = %d AND object_id = %d',
                    $plugin_id, $object_id);
      }
      else // meaning this has now been unapproved
      {
        $DB->query("UPDATE {comments_count} SET count = (IFNULL(count,1)-1)
                    WHERE plugin_id = %d AND object_id = %d AND (count > 0)",
                    $plugin_id, $object_id);
      }
    }
  }

  if(isset($SDCache) && ($SDCache instanceof SDCache))
  {
    $SDCache->delete_cacheid('comments_count');
  }

  RedirectPage('comments.php', AdminPhrase('comments_comment_updated'));

} //UpdateComment


// ############################################################################
// DELETE COMMENTS
// ############################################################################

function DeleteComments()
{
  global $DB, $SDCache, $sdlanguage;

  // SD313: security token check
  if(!CheckFormToken())
  {
    RedirectPage(SITE_URL,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
    return;
  }

  $comment_id_arr = GetVar('comment_id_arr', array(), 'array');
  $plugin_id_arr = GetVar('plugin_id_arr', array(), 'array');
  $object_id_arr = GetVar('object_id_arr', array(), 'array');
  $delete_comment_id_arr = GetVar('delete_comment_id_arr', array(), 'array');

  for($i = 0; $i < count($comment_id_arr); $i++)
  {
    $comment_id = $comment_id_arr[$i];
    $plugin_id  = $plugin_id_arr[$i];
    $object_id  = $object_id_arr[$i];

    if(@in_array($comment_id, $delete_comment_id_arr))
    {
      $DB->query('DELETE FROM {comments} WHERE commentid = %d LIMIT 1',$comment_id);

      $DB->query('UPDATE {comments_count} SET count = (count - 1) WHERE
                  plugin_id = %d AND object_id = %d AND (count > 0)',
                  $plugin_id, $object_id);
    }
  }

  if(isset($SDCache) && ($SDCache instanceof SDCache))
  {
    $SDCache->delete_cacheid('comments_count');
  }

  RedirectPage('comments.php', AdminPhrase('comments_comments_deleted'));

} //DeleteComments


// ############################################################################
// TRANSALTE OBJECT ID
// ############################################################################

function TranslateObjectID($pluginid, $objectid, $pageid=0)
{
  //SD370: uses new function "p_LC_TranslateObjectID" (functions_global.php)
  global $DB, $plugin_names, $sdlanguage;

  if(empty($pluginid) || empty($objectid))
  {
    return '';
  }

  if(!isset($plugin_names[$pluginid]))
  {
    return $sdlanguage['err_plugin_not_available'].' (ID: '.$pluginid.')';
  }

  //SD370: translate now in global function p_LC_TranslateObjectID
  $title = 'Unknown (ID ' . $objectid . ')';
  if($res = p_LC_TranslateObjectID($pluginid, $objectid, 0, $pageid))
  {
    if(strlen($res['title']))
    {
      $title = $res['title'];
      if(isset($res['link']) && /*!empty($pageid) &&*/ strlen($res['link'])) // links source title to category?
      {
        $title = '<a href="'.$res['link'].'" target="_blank">'.$title.'</a>';
      }
    }
  }

  return $title;

} //TranslateObjectID


// ############################################################################
// DISPLAY COMMENT
// ############################################################################

function DisplayComment()
{
  global $DB, $plugin_names;

  $commentid = GetVar('commentid', 0, 'whole_number');

  $comment_arr = $DB->query_first('SELECT c.*, p.name as pluginname'.
                                  ' FROM {comments} c'.
                                  ' LEFT JOIN {plugins} p ON p.pluginid = c.pluginid'.
                                  ' WHERE commentid = %d LIMIT 1', $commentid);
 echo '
  <form method="post" role="form" class="form-horizontal" id="comment" action="comments.php?commentid=' . $commentid . '">
  '.PrintSecureToken().'
  <input type="hidden" name="plugin_id" value="' . $comment_arr['pluginid'] . '" />
  <input type="hidden" name="object_id" value="' . $comment_arr['objectid'] . '" />
  <input type="hidden" name="original_status" value="' . $comment_arr['approved'] . '" />	
	<div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('comments_plugin') . '</label>
		<div class="col-sm-9">
			<input type="text" class="form-control" value="'.$comment_arr['pluginname'].'" disabled /></div>
	</div>
	
	<div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('comments_item') . '</label>
		<div class="col-sm-9">
		<input type="text" class="form-control" value="'.
    TranslateObjectID($comment_arr['pluginid'],
                      $comment_arr['objectid'],
                      $comment_arr['categoryid']).'" disabled /></div>
	</div>
	
	<div class="form-group">
		<label class="control-label col-sm-2" for="username">' . AdminPhrase('comments_user_name') . '</label>
		<div class="col-sm-9">
      		<input type="text" class="form-control" name="username" value="'.$comment_arr['username'].'" />
		</div>
	</div>
	
	<div class="form-group">
		<label class="control-label col-sm-2" for="ipaddress">' . AdminPhrase('comments_ipaddress') . '</label>
		<div class="col-sm-9">
      		<input type="text" class="form-control" name="ipaddress" value="'.$comment_arr['ipaddress'].'" />
	 </div>
	</div>
   <div class="form-group">
   	<label class="control-label col-sm-2" for="comment">' . AdminPhrase('comments_comment') . '</label>
	<div class="col-sm-9">
	      <textarea class="bbcode" class="form-control" id="comment" name="comment" rows="10" style="width:99%">'.$comment_arr['comment'].'</textarea>
	</div>
	</div>
	
	 <div class="form-group">
  	<label class="control-label col-sm-2" for="deletecomment">'.AdminPhrase('comments_actions').'</label>
	<div class="col-sm-9">
      	<input type="checkbox" name="deletecomment" class="ace" value="1" />
		<span class="lbl"> '.AdminPhrase('comments_delete_comment').'</span>
		<br />
		<br />
		<input type="checkbox" name="approved" value="1" class="ace" ' . (empty($comment_arr['approved'])?'':'checked').' /> 
	  <span class="lbl"> '.AdminPhrase('comments_approve_comment').'</span>
	</div>
</div>
  ';

   PrintSubmit('updatecomment',AdminPhrase('comments_update_comment'),'comment','fa-check');

  echo '
  </form>';

} //DisplayComment


// ############################################################################
// UPDATE SETTINGS
// ############################################################################

function UpdateSettings()
{
  global $DB, $SDCache;

  if(isset($_POST['settings']) && is_array($_POST['settings']))
  {
    $settings = $_POST['settings'];
    while(list($key, $value) = each($settings))
    {
      //SD332: support for array'ed options
      if(is_array($value))
      {
        $value = unhtmlspecialchars(implode(',',$value));
      }
      else
      {
        $value = unhtmlspecialchars($value);
      }
      $DB->query("UPDATE {mainsettings} SET value = '%s' WHERE settingid = %d",
                 $DB->escape_string($value), $key);
    }

    //SD342: delete Main Settings cache file
    if(isset($SDCache))
    {
      $SDCache->delete_cacheid(CACHE_ALL_MAINSETTINGS);
    }
  }

  RedirectPage('comments.php', AdminPhrase('comments_settings_updated'),1);

} //UpdateSettings


// ############################################################################
// DISPLAY COMMENT SETTINGS
// ############################################################################

function DisplayCommentSettings()
{
  global $DB, $admin_phrases;

  $getsettings = $DB->query("SELECT * FROM {mainsettings} WHERE groupname = 'comment_options' ORDER BY displayorder ASC");

  echo '
  <form method="post" action="comments.php" id="commentsettings" class="form-horizontal" role="form">
  <input type="hidden" name="action" value="updatesettings" />
  '.PrintSecureToken();

  while($setting = $DB->fetch_array($getsettings,null,MYSQL_ASSOC))
  {
    PrintAdminSetting($setting);
  }

  PrintSubmit('updatesettings',AdminPhrase('common_update_settings'),'commentsettings','fa-check');

  echo '
  </form>';



} //DisplayCommentSettings


// ############################################################################
// DISPLAY COMMENTS
// ############################################################################

function DisplayComments($pluginid = 0)
{
  global $DB, $plugin_names, $search, $sdlanguage;

  $items_per_page = $search['limit'];
  $page = GetVar('page', 1, 'int');
  $limit = ' LIMIT '.(($page-1)*$items_per_page).','.$items_per_page;
  $pagination_target = 'comments.php?action=display_comments';

  $where = 'WHERE';

  if($pluginid)
  {
    $title = $DB->query_first('SELECT name FROM {plugins} WHERE pluginid = %d', $pluginid);
    $title = $title['name'] . ' Comments';

    $where .= ' p.pluginid = '.(int)$pluginid;
  }
  else
  {
    // Latest Comments
    $title = AdminPhrase('comments_latest_comments');
  }

  // search for comment?
  if(strlen($search['comment']))
  {
    $where .= ($where != 'WHERE' ? ' AND' : '') .
               " (c.comment LIKE '%%" . $search['comment'] . "%%')";
  }

  // search for approved?
  if(strlen($search['approved']) && ($search['approved'] != 'all'))
  {
    $where .= ($where != 'WHERE' ? ' AND' : '') .
               ' (c.approved = ' . ($search['approved']=='approved'?1:0) . ')';
  }

  // search for username?
  if(strlen($search['username']))
  {
    $where .= ($where != 'WHERE' ? ' AND' : '') .
               " (c.username LIKE '%%" . $DB->escape_string($search['username']) . "%%')";
  }

  // search for ipaddress?
  if(strlen($search['ipaddress']))
  {
    $where .= ($where != 'WHERE' ? ' AND' : '') .
               " (c.ipaddress LIKE '%%" . $DB->escape_string($search['ipaddress']) . "%%')";
  }

  // search for pluginid?
  if(strlen($search['pluginid']) && (int)$search['pluginid'] > 1)
  {
    $where .= ($where != 'WHERE' ? ' AND' : '') .
               ' (c.pluginid = ' . (int)$search['pluginid'] . ')';
  }

  $where = ($where=='WHERE') ? '' : $where;
  $select = 'SELECT COUNT(*) ccount'.
            ' FROM {comments} c'.
            ' LEFT JOIN {plugins} p ON p.pluginid = c.pluginid '.
            $where;

  // Get the total count of comments with conditions
  $comments_count = 0;
  $DB->result_type = MYSQL_ASSOC;
  if($getcount = $DB->query_first($select))
  {
    $comments_count = (int)$getcount['ccount'];
  }

  $sort = $search['sorting'];
  if($sort == COMMENTS_IP_SORT_ASC)
    $search['sorting'] = 'INET_ATON(ipaddress) ASC';
  else
  if($sort == COMMENTS_IP_SORT_DESC)
    $search['sorting'] = 'INET_ATON(ipaddress) DESC';

  // Select all comment rows
  $select = 'SELECT c.*, p.name AS pluginname'.
            ' FROM '.PRGM_TABLE_PREFIX.'comments c'.
            ' LEFT JOIN '.PRGM_TABLE_PREFIX.'plugins p ON p.pluginid = c.pluginid '.
            $where.
            ' ORDER BY '.$search['sorting'].$limit;
  $getcomments = $DB->query($select);

  // Fetch distinct list of all referenced plugins from comments
  $plugins_arr = array();
  if($getplugins = $DB->query('SELECT DISTINCT p.pluginid, p.name'.
                              ' FROM {comments} c'.
                              ' INNER JOIN {plugins} p ON p.pluginid = c.pluginid'))
  {
    while($plugin = $DB->fetch_array($getplugins,null,MYSQL_ASSOC))
    {
      $plugins_arr[$plugin['pluginid']] = (isset($plugin_names[$plugin['pluginid']]) ?
                                             $plugin_names[$plugin['pluginid']] :
                                             $plugin['name']);
    }
    ksort($plugins_arr);
  }

  // ######################################################################
  // DISPLAY COMMENTS SEARCH BAR
  // ######################################################################

  echo '
  <form action="comments.php?action=displaycomments" id="searchcomments" name="searchcomments" method="post">
  '.PrintSecureToken().'
  <input type="hidden" name="customsearch" value="1" />
  <input type="hidden" name="clearsearch" value="0" />
  <div class="table-header">' . AdminPhrase('comments_filter_title') . '</div>
  <table class="table table-striped table-bordered">
  	<thead>
	  <tr>
		<th>' . AdminPhrase('menu_comments').'</th>
		<th>' . AdminPhrase('comments_status') . '</th>
		<th>' . AdminPhrase('comments_username') . '</th>
		<th>' . AdminPhrase('comments_plugin') . '</th>
		<th>' . AdminPhrase('comments_ip') . '</th>
		<th>' . AdminPhrase('comments_sort_by') . '</th>
		<th>' . AdminPhrase('comments_limit') . '</th>
		<th>' . AdminPhrase('comments_filter') . '</th>
	  </tr>
	 </thead>
	 <tbody>
  <tr>
    <td ><input type="text" class="form-control" name="searchcomment" value="' . $search['comment'] . '" /></td>
    <td >
      <select name="searchapproved" class="form-control" style="width: 95%;">
        <option value="all"' .       ($search['approved'] == 'all'       ?' selected="selected"':'') .'>---</option>
        <option value="approved"' .  ($search['approved'] == 'approved'  ?' selected="selected"':'') .'>'.AdminPhrase('comments_approved').'</option>
        <option value="unapproved"'. ($search['approved'] == 'unapproved'?' selected="selected"':'') .'>'.AdminPhrase('comments_unapproved').'</option>
      </select>
    </td>
    <td ><input type="text" class="form-control" id="username" name="searchusername" style="width: 90%;" value="' . $search['username'] . '" /></td>
    <td >
      <select id="searchpluginid" name="searchpluginid" style="width: 95%">
        <option value="0"'.(empty($search['pluginid'])?' selected="selected"':'').'>---</option>';
  foreach($plugins_arr as $p_id => $p_name)
  {
    if($p_id > 1) // do not list "empty" plugin
    {
      echo '
        <option value="'.$p_id. '" ' . ($search['pluginid']==$p_id?'selected="selected"':'') .'>'. $p_name . '</option>';
    }
  }
  echo '
      </select>
    </td>
    <td ><input type="text" class="form-control" id="ipaddress" name="ipaddress" value="' . $search['ipaddress']. '" size="8" style="width:90%" /></td>
    <td >
      <select class="form-control" name="searchsorting" style="width:95%">
        <option value="'.COMMENTS_DATE_SORT_DESC.'" ' .
        ($sort == COMMENTS_DATE_SORT_DESC?'selected="selected"':'') .'>'.AdminPhrase('comments_date_descending').'</option>
        <option value="'.COMMENTS_DATE_SORT_ASC.'" ' .
        ($sort == COMMENTS_DATE_SORT_ASC?'selected="selected"':'') .'>'.AdminPhrase('comments_date_asc').'</option>
        <option value="'.COMMENTS_UNAME_SORT_ASC.'" ' .
        ($sort == COMMENTS_UNAME_SORT_ASC?'selected="selected"':'') .'>'.AdminPhrase('comments_uname_asc').'</option>
        <option value="'.COMMENTS_UNAME_SORT_DESC.'" ' .
        ($sort == COMMENTS_UNAME_SORT_DESC?'selected="selected"':'') .'>'.AdminPhrase('comments_uname_descending').'</option>
        <option value="'.COMMENTS_IP_SORT_ASC.'" ' .
        ($sort == COMMENTS_IP_SORT_ASC?'selected="selected"':'') .'>'.AdminPhrase('comments_ip_asc').'</option>
        <option value="'.COMMENTS_IP_SORT_DESC.'" ' .
        ($sort == COMMENTS_IP_SORT_DESC?'selected="selected"':'') .'>'.AdminPhrase('comments_ip_descending').'</option>
      </select>
    </td>
    <td  style="width: 40px;"><input type="text" class="form-control" name="searchlimit" style="width: 35px;" value="' . $items_per_page . '" size="2" /></td>
    <td  class="align-middle center" style="width: 80px;">
      <input type="submit" value="'.AdminPhrase('search').'" style="display:none" />
      <a id="comments-submit-search" href="#" onclick="return false;" title="'.AdminPhrase('comments_apply_filter').'"><i class="ace-icon fa fa-search blue bigger-130"></i></a>&nbsp;
	   <a id="comments-clear-search" href="#" onclick="return false;" title="'.AdminPhrase('comments_clear_filter').'" ><i class="ace-icon fa fa-trash-o red bigger-130"></i></a>
    </td>
  </tr>
  </tbody>
  </table>
  </form>
  ';

  if(!$comments_count)
  {
    DisplayMessage(AdminPhrase('comments_no_comments_found'));
    return;
  }

  //SD370: added check all/none functionality
  echo '
  <form action="comments.php" method="post" id="comments" name="comments">
  '.PrintSecureToken().'
	<div class="table-header">'.$title . ' ('.$comments_count.')'.'</div>
  <table class="table table-bordered table-striped">
  	<thead>
	  <tr>
		<th>' . AdminPhrase('comments_comment2') . '</th>
		<th width="70" align="center">' . AdminPhrase('comments_approved') . '</th>
		<th>' . AdminPhrase('comments_plugin_name') . '</th>
		<th>' . AdminPhrase('comments_plugin_item') . '</th>
		<th>' . AdminPhrase('comments_username2') . '</th>
		<th>' . AdminPhrase('comments_ipaddress') . '</th>
		<th>' . AdminPhrase('comments_date') . '</th>
		<th width="30" class="center">
		  <a id="checkall" rel="0" title="'.htmlspecialchars(AdminPhrase('comments_delete'),ENT_COMPAT).
		  '" href="#" onclick="javascript:return false;"><i class="ace-icon fa fa-trash-o red bigger-130"></i></a></th>
	  </tr>
	  </thead>
	  <tbody>';

  while($comment = $DB->fetch_array($getcomments,null,MYSQL_ASSOC))
  {
    if(!strlen($comment['comment']))
    {
      $comment['comment'] = ' ---';
    }
    else if(strlen($comment['comment']) > 30)
    {
      $comment['comment'] = sd_substr($comment['comment'], 0, 30) . '...';
    }

    echo '
    <tr>
      <td><a href="comments.php?action=display_comment&amp;commentid='.
        $comment['commentid'].'"><i class="ace-icon fa fa-edit bigger-120"></i>&nbsp;'.
        $comment['comment'] . '</a></td>
      <td class="center">' .
        ($comment['approved']? '<i class="ace-icon fa fa-check green bigger-140" Title="'.AdminPhrase('common_yes').'"></i>' : '<i class="ace-icon fa fa-minus-circle red bigger-140" Title="'.AdminPhrase('common_no').'"></i>') . '
      </td>
      <td>'.
      (isset($plugin_names[$comment['pluginid']]) ? $plugin_names[$comment['pluginid']] :$comment['pluginname']) . '</td>
      <td>'.
        TranslateObjectID($comment['pluginid'],
                          $comment['objectid'],
                          (empty($comment['categoryid'])?0:(int)$comment['categoryid'])).'</td>
      <td><a class="username" title="'.htmlspecialchars(AdminPhrase('filter_username_hint')).'" href="#" onclick="return false;"><i class="ace-icon fa fa-user"></i> '.$comment['username'].'</a></td>
      <td>';
      // Note: first link in this <td> cell MUST be the link with the IP as text!!!
      $ip = !empty($comment['ipaddress']) ? (string)$comment['ipaddress'] : '';
      $isBanned = sd_IsIPBanned($ip);
      echo $ip ? '
        <a href="#" class="mytooltip hostname" title="IP Tools"><i class="ace-icon fa fa-wrench bigger-110"></i><span class="text-hide">'.$ip.'</a>
		<a class="mytooltip ipaddress" title="'.htmlspecialchars(AdminPhrase('filter_ip_hint')).'" href="#" onclick="return false;">'.$ip.'</a>
        ' : '';

      echo '</td>
      <td>' . DisplayDate($comment['date']) . '</td>
      <td class="center">
        <input type="hidden" name="comment_id_arr[]" value="' . $comment['commentid'] . '" />
        <input type="hidden" name="plugin_id_arr[]" value="' . $comment['pluginid'] . '" />
        <input type="hidden" name="object_id_arr[]" value="' . $comment['objectid'] . '" />
        <input type="checkbox" name="delete_comment_id_arr[]" class="ace deletecomment" value="' . $comment['commentid'] . '" /><span class="lbl"></span>
      </td>
    </tr>';
  }
  
  echo '
  </td></tr>
  </tbody>
  </table>';
  PrintSubmit('delete_comments',AdminPhrase('comments_delete_comments'),'comments','fa-trash-o','center','','btn-danger');

  echo'
  </form>
  ';



  // pagination
  if(!empty($comments_count))
  {
    $p = new pagination;
    $p->items($comments_count);
    $p->limit($items_per_page);
    $p->currentPage($page);
    $p->adjacents(7);
    $p->target($pagination_target);
    $p->show();
  }

  // SD343: make IP address clickable
  $moreJS = '
  $("a.ipaddress").click(function(e) {
    e.preventDefault();
    var new_val = $(this).text();
    $("#searchcomments #ipaddress").attr("value", new_val);
    $("#searchcomments").submit();
  });
  $("a.username").click(function(e) {
    e.preventDefault();
    var new_val = $(this).text();
    $("#searchcomments #username").attr("value", new_val);
    $("#searchcomments").submit();
  });
  ';
  DisplayIPTools('a.hostname', 'td', $moreJS); //SD343

} //DisplayComments


// ############################################################################
// DISPLAY ORGANIZED COMMENTS
// ############################################################################

function DisplayOrganizedComments()
{
  global $DB;

  $getcomments = $DB->query("SELECT p.pluginid, p.name AS pluginname, COUNT(*) AS count
                               FROM {plugins} p, {comments} c
                              WHERE p.pluginid = c.pluginid
                           GROUP BY p.pluginid
                           ORDER BY count DESC");

  echo '<div class="table-header">' . AdminPhrase('comments_by_plugin') . '</div>
  		<table class="table table-bordered table-striped">
        	<thead>
		<tr>
          <th>' . AdminPhrase('comments_plugin') . '</th>
          <th>' . AdminPhrase('comments_number_of_comments') . '</th>
        </tr>
		</thead>
		<tbody>';

  while($comment = $DB->fetch_array($getcomments,null,MYSQL_ASSOC))
  {
    echo '<tr>
            <td><a href="comments.php?action=display_comments&amp;pluginid=' .
            $comment['pluginid']. '">' . $comment['pluginname'] . '</a></td>
            <td>' . $comment['count'] . '</td>
          </tr>';
  }
  echo '</tbody></table>';

}


// ############################################################################
// SELECT FUNCTION
// ############################################################################

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
