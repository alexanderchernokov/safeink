<?php
// Tag-Types:
// 0 = all plugin-level tags entered by users
// 1 = global tags maintained in admin area
// 2 = "category"
// 100+ = plugin level-maintained tags by type, e.g. type 3 = 103

define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');
define('SD_ADMIN_TAGS_PAGE', true);

// INIT PRGM
include(ROOT_PATH . 'includes/init.php');

define('TAGS_DATE_SORT_DESC',   'datecreated DESC');
define('TAGS_DATE_SORT_ASC',    'datecreated ASC');
define('TAGS_TAG_SORT_ASC',     'tag ASC');
define('TAGS_TAG_SORT_DESC',    'tag DESC');
define('TAGS_TAGTYPE_SORT_ASC', 'tagtype ASC');
define('TAGS_TAGTYPE_SORT_DESC','tagtype DESC');
define('TAGS_PLUGIN_SORT_ASC',  'pluginname ASC');
define('TAGS_PLUGIN_SORT_DESC', 'pluginname DESC');

$search = array();
$customsearch = GetVar('customsearch', 0, 'bool');
$clearsearch  = GetVar('clearsearch',  0, 'bool');

// Restore previous search array from cookie
$search_cookie = isset($_COOKIE[COOKIE_PREFIX . '_tags_search']) ? $_COOKIE[COOKIE_PREFIX . '_tags_search'] : false;

if($clearsearch)
{
  $search_cookie = false;
  $customsearch  = false;
}

if($customsearch)
{
  $search = array('tag'         => GetVar('searchtag', '', 'string', true, false),
                  'tagtype'     => GetVar('searchtagtype', 'all', 'string', true, false),
                  'pluginid'    => GetVar('searchpluginid', 0, 'int', true, false),
                  'usergroupid' => GetVar('searchusergroupid', 0, 'natural_number', true, false),
                  'limit'       => GetVar('searchlimit', 20, 'integer', true, false),
                  'sorting'     => GetVar('searchsorting', '', 'string', true, false),
                  'censored'    => GetVar('searchcensored', 'all', 'string', true, false),
                  );

  // Store search params in cookie
  sd_CreateCookie('_tags_search',base64_encode(serialize($search)));
}
else
{
  $searchpluginid = GetVar('pluginid', 0, 'int');
  if($searchpluginid >= -1)
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
  $search = array('tag'         => '',
                  'tagtype'     => 'all',
                  'pluginid'    => 0,
                  'usergroupid' => 0,
                  'censored'    => 0,
                  'limit'       => 20,
                  'sorting'     => '');

  // Remove search params cookie
  sd_CreateCookie('_tags_search', '');
}

$search['tag']      = isset($search['tag'])  ? (string)$search['tag'] : '';
$search['tagtype']  = isset($search['tagtype']) ? (string)$search['tagtype'] : 'all';
if(!in_array($search['tagtype'], array('all','-1','0','1','2','3','others'))) $search['tagtype'] = 'all';
$search['pluginid'] = (isset($search['pluginid']) && (intval($search['pluginid']) >= -1)) ? (int)$search['pluginid'] : 0;
$search['usergroupid'] = isset($search['usergroupid']) ? (int)$search['usergroupid'] : 0;
$search['limit']    = isset($search['limit'])    ? Is_Valid_Number($search['limit'],20,5,1000) : 20;
$search['censored'] = empty($search['censored']) || ($search['censored']=='all') ? 'all' :
                      ($search['censored']=='censored'?'censored':'not_censored');
$search['sorting']  = empty($search['sorting'])  ? TAGS_DATE_SORT_DESC : $search['sorting'];
$search['sorting'] = in_array($search['sorting'],
                       array(TAGS_DATE_SORT_ASC, TAGS_DATE_SORT_DESC,
                             TAGS_PLUGIN_SORT_ASC, TAGS_PLUGIN_SORT_DESC,
                             TAGS_TAGTYPE_SORT_ASC, TAGS_TAGTYPE_SORT_DESC,
                             TAGS_TAG_SORT_ASC, TAGS_TAG_SORT_DESC)) ? $search['sorting'] : TAGS_DATE_SORT_DESC;
$sort = $search['sorting'];

// ############################################################################
// LOAD ADMIN LANGUAGE
// ############################################################################
$admin_phrases = LoadAdminPhrases(4);

// ############################################################################
// GENERATE SUB MENU
// ############################################################################
//SD322: enable BBCode editor (MarkItUp)

$js_arr = array();
$js_arr[] = ROOT_PATH . MINIFY_PREFIX_F . 'includes/javascript/markitup/markitup-full.js';

sd_header_add(array(
  'js'    => $js_arr,
   // NO ROOT_PATH for "css" entries!
  'css'   => array(SD_JS_PATH . 'markitup/skins/markitup/style.css',
                   SD_JS_PATH . 'markitup/sets/bbcode/style.css'),
  'other' => array('
<script type="text/javascript">
if (typeof(jQuery) !== "undefined") {
  jQuery(document).ready(function() {
    (function($){
      $("#searchtags select").change(function() {
		  alert("her");
        $("#searchtags").submit();
      });

      $("a#tags-submit-search").click(function() {
        $("#searchtags").submit();
      });

      $("a#tags-clear-search").click(function() {
        $("#searchtags input").val("");
        $("#searchtags select").prop("selectedIndex", 0);
        $("#searchtags input[name=clearsearch]").val("1");
        $("#searchtags").submit();
      });
	  
	  $("#deletetags").on("click", function(e) {
	  	e.preventDefault();
  		bootbox.confirm("'.AdminPhrase('tags_confirm_delete_tags') .'", function(result) {
    	if(result) {
       		$("form#tags").submit();
    	}
  		});
	});
	
	$("a#checkall").click(function(e){
      e.preventDefault();
      var ischecked = 1 - parseInt($(this).attr("rel"),10);
      if(ischecked==1) {
        $("input.deletetag").attr("checked","checked");
        $("form#tags tr:not(thead tr)").addClass("danger");
      } else {
        $("form#tags input.deletetag").removeAttr("checked");
        $("form#tags tr").removeClass("danger");
      }
      $(this).attr("rel",ischecked);
      return false;
    });
	
	$("input[type=checkbox].deletetag").on("change",function(){
      var tr = $(this).parents("tr");
      $(tr).toggleClass("danger");
    });

      $(".bbcode").markItUp(myBbcodeSettings);
    }(jQuery));
  });
}
</script>')
), false);


// ############################################################################
// GET/POST VARS
// ############################################################################
$action   = GetVar('action', 'display_tags', 'string');
$tagid    = GetVar('tagid', 0, 'whole_number');
$pluginid = GetVar('pluginid', 0, 'whole_number');

// ############################################################################
// DISPLAY ADMIN HEADER
// ############################################################################
DisplayAdminHeader(array('Tags', $action));


// ############################################################################
// CHECK PAGE ACCESS
// ############################################################################

CheckAdminAccess('tags');

// ############################################################################
// INSERT TAG
// ############################################################################

function InsertTag()
{
  global $DB, $SDCache, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage(REFRESH_PAGE, '<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />', 2, true);
    return;
  }

  $tagid     = GetVar('tagid',     0, 'natural_number',true,false);
  $pluginid  = GetVar('pluginid',  0, 'natural_number',true,false);
  $tag_type  = GetVar('tag_type',  0, 'natural_number',true,false);
  $tag       = trim(GetVar('tag', '', 'string',true,false));
  $global    = GetVar('global',    0, 'natural_number',true,false)?1:0;
  if(!$global) $tag_type = 0;
  if(!strlen($tag) || ($pluginid < 0) || ($pluginid > 999999) || ($tag_type < 0))
  {
    DisplayMessage('Invalid data!', true);
    return;
  }

  $censored  = GetVar('censored', 0, 'bool',true,false)?1:0;
  $groups    = GetVar('allowed_groups',  array(), 'array',true,false);
  $hpre      = GetVar('html_prefix', '','html',true,false);

  if(!empty($groups))
  {
    $allowed = array();
    foreach($groups as $key => $val)
    {
      if(Is_Valid_Number($val,0,1,9999)) $allowed[] = $val;
    }
    if(!empty($allowed))
    {
      $allowed = count($allowed) > 1 ? implode('|', $allowed) : $allowed[0];
      $allowed = empty($allowed) ? '' : '|'.$allowed.'|';
    }
  }
  else $allowed = '';

  $DB->query('INSERT INTO {tags} (pluginid, objectid, tagtype, tag, datecreated, tag_ref_id, censored, allowed_groups, html_prefix)'.
             " VALUES (%d, %d, %d, '%s', %d, %d, %d, '%s', '%s')",
             $pluginid, 0, $tag_type, $tag, TIME_NOW, 0, $censored, $allowed,
             $DB->escape_string($hpre));
  $tagid = $DB->insert_id();

  /*'?action=display_global_tags'*/
  RedirectPage('tags.php'.(empty($global)?'':'?action=display_tag_form&tagid='.$tagid), AdminPhrase('tags_tag_created'));

} //InsertTag


// ############################################################################
// UPDATE TAG
// ############################################################################

function UpdateTag()
{
  global $DB, $SDCache;

  if(!CheckFormToken())
  {
    RedirectPage(REFRESH_PAGE, '<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />', 2, true);
    return;
  }

  $tagid     = GetVar('tagid',     0, 'whole_number');
  $pluginid  = GetVar('pluginid',  0, 'whole_number');
  $tag       = trim(GetVar('tag', '', 'string'));
  $tag_type  = GetVar('tag_type',  0, 'natural_number');

  if(!strlen($tag) || ($pluginid < 0) || ($pluginid > 99999) || ($tagid < 0) || ($tag_type < 0) || ($tag_type > 99999))
  {
    DisplayMessage('Invalid data!', true);
    return;
  }

  $deletetag = GetVar('deletetag', 0, 'bool');
  $global    = GetVar('global',    0, 'bool');
  $global   |= !empty($tag_type) || empty($pluginid);

  if($deletetag)
  {
    if($tagid > 0)
    $DB->query('DELETE FROM {tags} WHERE tagid = %d',$tagid);
    RedirectPage('tags.php'.($global || !empty($tag_type) || empty($pluginid)?'?action=display_global_tags':''), AdminPhrase('tags_tag_deleted'));
    return;
  }

  $censored  = GetVar('censored', 0, 'bool',true,false)?1:0;
  $groups    = GetVar('allowed_groups', null, 'array',true,false);
  $hpre      = GetVar('html_prefix', '','html',true,false);
  $allowed_object_ids = GetVar('allowed_object_ids', null, 'array');

  if(isset($groups))
  {
    $allowed = array();
    foreach($groups as $key => $val)
    {
      if(Is_Valid_Number($val,0,1,9999)) $allowed[] = $val;
    }
    if(!empty($allowed))
    {
      $allowed = count($allowed) > 1 ? implode('|', $allowed) : $allowed[0];
      $allowed = empty($allowed) ? '' : '|'.$allowed.'|';
    }
  }

  if(($tag_type == '2') && isset($allowed_object_ids))
  {
    $allowed_obj = array();
    foreach($allowed_object_ids as $key => $val)
    {
      if(Is_Valid_Number($val,0,1,99999999)) $allowed_obj[] = $val;
    }
    if(!empty($allowed_obj))
    {
      $allowed_obj = count($allowed_obj) > 1 ? implode('|', $allowed_obj) : $allowed_obj[0];
      $allowed_obj = empty($allowed_obj) ? '' : '|'.$allowed_obj.'|';
    }
  }

  $DB->query("UPDATE {tags} SET tag = '%s', tagtype = %d, censored = %d, pluginid = %d,".
             " allowed_groups = '%s', allowed_object_ids = '%s', html_prefix = '%s'".
             ' WHERE tagid = %d',
             $tag, $tag_type, $censored, $pluginid,
             (empty($allowed)?'':$allowed), (empty($allowed_obj)?'':$allowed_obj),
             $DB->escape_string($hpre), $tagid);

  RedirectPage('tags.php'.($global || !empty($tag_type) || empty($pluginid)?'?action=display_global_tags':''), AdminPhrase('tags_tag_updated'));

} //UpdateTag


// ############################################################################
// DELETE TAGS
// ############################################################################

function DeleteTags()
{
  global $DB, $SDCache, $sdlanguage;

  // SD313: security token check
  if(!CheckFormToken())
  {
    RedirectPage(SITE_URL,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',true);
    return;
  }

  $tag_id_arr    = GetVar('tag_id_arr', array(), 'array');
  $plugin_id_arr = GetVar('plugin_id_arr', array(), 'array');
  $object_id_arr = GetVar('object_id_arr', array(), 'array');
  $delete_tag_id_arr = GetVar('delete_tag_id_arr', array(), 'array');

  $skipped = array();
  for($i = 0; $i < count($tag_id_arr); $i++)
  {
    $tag_id    = $tag_id_arr[$i];
    $plugin_id = $plugin_id_arr[$i];
    $object_id = $object_id_arr[$i];

    if(@in_array($tag_id, $delete_tag_id_arr))
    {
      $tagcount = 0;
      if($getcount = $DB->query_first('SELECT COUNT(tagid) tagcount'.
                                      ' FROM {tags} WHERE tag_ref_id = %d',$tag_id))
      {
        if($tagcount = (int)$getcount['tagcount'])
        {
          $tag = $DB->query_first('SELECT tag, pluginid, tagtype FROM {tags} WHERE tagid = %d',$tag_id);
          if($tag['tagtype']==2)
            $skipped[] = "Prefix '".$tag['tag']."' referenced ".$tagcount." time(s)! NOT deleted!";
          else
            $skipped[] = "Tag '".$tag['tag']."' referenced ".$tagcount." time(s)! NOT deleted!";
        }
        else
        {
          $DB->query('DELETE FROM {tags} WHERE tagid = %d LIMIT 1',$tag_id);
        }
      }

    }
  }
  if(count($skipped))
    DisplayMessage($skipped, true);
  else
    RedirectPage('tags.php', AdminPhrase('tags_tags_deleted'),2);

} //DeleteTags


// ############################################################################
// TRANSALTE OBJECT ID
// ############################################################################

function TranslateObjectID($pluginid, $objectid)
{
  global $DB, $sdlanguage;

  if(empty($pluginid) || empty($objectid))
  {
    return '';
  }

  if(!$plugin_install = $DB->query_first('SELECT * FROM {plugins} WHERE pluginid = %d',$pluginid))
  {
    return $sdlanguage['err_plugin_not_available'].' (ID: '.$pluginid.')';
  }

  $title = 'Unknown (ID ' . $objectid . ')';
  $forum_id = GetPluginID('Forum');
  if($forum_id == $pluginid)
  {
    $title2 = $DB->query_first('SELECT title FROM {p_forum_topics} WHERE topic_id = %d', $objectid);
    $title = isset($title2[0]) ? $title2[0] : $title;
  }
  else
  switch($pluginid)
  {
    case 2: // News
    $title2 = $DB->query_first('SELECT title FROM {p2_news} WHERE articleid = %d', $objectid);
    $title = isset($title2[0]) ? $title2[0] : $title;
    break;

    case 17: // Image Gallery
    $title2 = $DB->query_first('SELECT title FROM {p17_images} WHERE imageid = %d',$objectid);
    $title = isset($title2[0]) ? $title2[0] : $title;
    break;

    case 13: // Download Manager (incl. old version)
    $title2 = $DB->query_first('SELECT title FROM {p13_files} WHERE fileid = %d',$objectid);
    $title = isset($title2[0]) ? $title2[0] : $title;
    break;

    default:
      /*
      TODO: this needs to be expanded so that a plugin could "register" a function name
            to receive the title of a tag's owner-plugin item
      */

      // Article clones support (SD 3.4+)
      if(($plugin_install['name']=='Articles') ||
         (!empty($plugin_install['base_plugin']) && ($plugin_install['base_plugin'] == 'Articles')))
      {
        $title2 = $DB->query_first('SELECT title FROM {p'.$pluginid.'_news} WHERE articleid = %d', $objectid);
        $title = isset($title2[0]) ? $title2[0] : $title;
        return $title;
      }

      // Download Manager clones support (SD 3.4+)
      if(($plugin_install['name']=='Download Manager') ||
         (!empty($plugin_install['base_plugin']) && ($plugin_install['base_plugin'] == 'Download Manager')))
      {
        $title2 = $DB->query_first('SELECT title FROM {p'.$pluginid.'_files} WHERE fileid = %d',$objectid);
        $title = isset($title2[0]) ? $title2[0] : $title;
        return $title;
      }

      // Media Gallery support (SD 3.4+)
      if(($plugin_install['name']=='Media Gallery') || ($plugin_install['name']=='Image Gallery') ||
         (!empty($plugin_install['base_plugin']) && ($plugin_install['base_plugin'] == 'Media Gallery')))
      {
        $title2 = $DB->query_first('SELECT title FROM {p'.$pluginid.'_images} WHERE imageid = %d',$objectid);
        $title = isset($title2[0]) ? $title2[0] : $title;
        return $title;
      }

      $plugin_install_path = ROOT_PATH.'plugins/'.dirname($plugin_install['settingspath']).'/install.php';
      if(file_exists($plugin_install_path))
      {
        $installtype = '';
        @include_once($plugin_install_path);
        $GLOBALS['pluginid'] = $pluginid; // Important!
        $func = "p{$pluginid}_GetTagTitle";
        if(function_exists($func))
        {
          return $func($objectid);
        }
        if(empty($plugin_install['base_plugin'])) return $title;
        /* TT: this wouldn't be possible for several plugins anyway:
        $func = "p_GetTagTitle";
        if(function_exists($func))
        {
          return $func($objectid);
        }
        */
      }
  }
  return $title;

} //TranslateObjectID


// ############################################################################
// CREATE (GLOBAL) TAG
// ############################################################################

function CreateTag()
{
  DisplayTagForm(true, false); // see functions_admin.php
}

function CreateGlobalTag()
{
  DisplayTagForm(true, true); // see functions_admin.php
}


// ############################################################################
// DISPLAY TAG FORM: moved to "functions_admin.php"!
// ############################################################################


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

  RedirectPage('tags.php', AdminPhrase('tags_settings_updated'));

} //UpdateSettings


// ############################################################################
// DISPLAY TAG SETTINGS
// ############################################################################

function DisplayTagSettings()
{
  global $DB, $admin_phrases;

  $getsettings = $DB->query("SELECT * FROM {mainsettings} WHERE groupname = 'tag_options' ORDER BY displayorder ASC");

  StartTable(AdminPhrase('tags_tag_settings'), array('table', 'table-bordered', 'table-striped'));

  echo '
  <form id="tags_settings" method="post" action="tags.php">
  <input type="hidden" name="action" value="updatesettings" />
  '.PrintSecureToken().'

  <tr>
    <td width="70%">' . AdminPhrase('common_setting_description') . '</td>
    <td>' . AdminPhrase('common_setting_value') . '</td>
  </tr>';

  while($setting = $DB->fetch_array($getsettings,null,MYSQL_ASSOC))
  {
    PrintAdminSetting($setting);
  }

  echo '
  </table>';

  PrintSubmit('updatesettings',AdminPhrase('common_update_settings'),'tags_settings');

  echo '
  </form>';

  EndSection();

} //DisplayTagSettings


// ############################################################################
// DISPLAY TAGS
// ############################################################################

function DisplayGlobalTags()
{
  DisplayTags(true);
}

// ############################################################################
// DISPLAY TAGS
// ############################################################################

function DisplayTags($globaltags=false)
{
  global $DB, $plugin_names, $search, $sdlanguage;

  $items_per_page = (int)$search['limit'];
  $page = Is_Valid_Number(GetVar('page', 1, 'int'),1,1,99999);
  $limit = ' LIMIT '.(($page-1)*$items_per_page).','.$items_per_page;
  $pagination_target = 'tags.php?action=display_tags';

  $where = 'WHERE tagid > 0'; //dummy!

  $pluginid = GetVar('pluginid', 0, 'natural_number');
  if($pluginid)
  {
    $title = $DB->query_first('SELECT name FROM {plugins} WHERE pluginid = %d', $pluginid);
    $title = $title['name'] . AdminPhrase('tags');
    $where .= ' AND IFNULL(p.pluginid,0) = '.(int)$pluginid;
  }
  else
  {
    // Latest Tags
    $title = empty($globaltags) ? AdminPhrase('tags_plugin_item').' '.AdminPhrase('tags') : AdminPhrase('tags_global_tags');
  }

  // search for tag?
  if(strlen($search['tag']))
  {
    $where .= " AND (t.tag LIKE '%%" . $search['tag'] . "%%')";
  }

  // filter for tag type?
  if($search['tagtype']!='all')
  {
    if($search['tagtype']=='others')
      $where .= ' AND (t.tagtype > 3)';
    else
    if($search['tagtype']=='-1')
      $where .= ' AND (t.tagtype between 1 AND 3)';
    else
      $where .= ' AND t.tagtype = '.(int)$search['tagtype'].
                ($search['tagtype']!=0 ? ' AND t.objectid = 0' : '');
  }

  // search for censored?
  if(!empty($search['censored']) && in_array($search['censored'], array('censored','not_censored')))
  {
    $censored = $search['censored'];
    $where .= ' AND (t.censored= ' . ($censored == 'censored'?1:0) . ')';
  }
  else $censored = 'all';

  // search for pluginid?
  if(strlen($search['pluginid']))
  {
    if($search['pluginid'] > 1)
    {
      $where .= ' AND (t.pluginid = ' . (int)$search['pluginid'] . ')';
    }
    else
    if($search['pluginid'] == -1)
    {
      $where .= ' AND IFNULL(t.pluginid,0) = 0';
    }
  }

  // filter by usergroupid?
  if(!empty($search['usergroupid']))
  {
    $where .= " AND ((IFNULL(t.allowed_groups,'')='') OR (t.allowed_groups LIKE '%|".(int)$search['usergroupid']."|%'))";
  }

  $select = 'SELECT COUNT(*) ccount FROM {tags} t'.
            ' LEFT JOIN {plugins} p ON p.pluginid = t.pluginid '.$where;

  // Get the total count of tags with conditions
  $tags_count = 0;
  $DB->result_type = MYSQL_ASSOC;
  if($getcount = $DB->query_first($select))
  {
    $tags_count = (int)$getcount['ccount'];
  }

  $sort = $search['sorting'];

  // Select all tag rows
  $select = 'SELECT t.*, p.name AS pluginname FROM '.PRGM_TABLE_PREFIX.'tags t'.
            ' LEFT JOIN '.PRGM_TABLE_PREFIX.'plugins p ON p.pluginid = t.pluginid '.
            $where.
            ' ORDER BY '.$search['sorting'].$limit;
  $gettags = $DB->query($select);

  // Fetch distinct list of all referenced plugins from tags
  $plugins_arr = array();
  if($getplugins = $DB->query('SELECT DISTINCT p.pluginid, p.name FROM {tags} t
                              INNER JOIN {plugins} p ON p.pluginid = t.pluginid'))
  {
    while($plugin = $DB->fetch_array($getplugins,null,MYSQL_ASSOC))
    {
      $plugins_arr[$plugin['pluginid']] = (isset($plugin_names[$plugin['pluginid']]) ? $plugin_names[$plugin['pluginid']] : $plugin['name']);
    }
    if(!empty($plugins_arr)) ksort($plugins_arr);
  }

  // ######################################################################
  // DISPLAY COMMENTS SEARCH BAR
  // ######################################################################

  StartTable(AdminPhrase('tags_filter_title'), array('table','table-bordered', 'table-striped'));

  echo '
  <form action="tags.php?action=displaytags" id="searchtags" name="searchtags" method="post">
  '.PrintSecureToken().'
  <input type="hidden" name="customsearch" value="1" />
  <input type="hidden" name="clearsearch" value="0" />
  <input type="hidden" name="global" value="'.(int)$globaltags.'" />
  <thead>
  <tr>
    <th width="20%">' . AdminPhrase('menu_tags').'</th>
    <th width="10%">' . AdminPhrase('tags_tag_type') . '</th>
    <th width="15%">' . AdminPhrase('tags_plugin') . '</th>
    <th width="15%">' . AdminPhrase('common_usergroup') . '</th>
    <th >' . AdminPhrase('tags_censored') . '</th>
    <th >' . AdminPhrase('tags_sort_by') . '</th>
    <th width="5%">' . AdminPhrase('tags_limit') . '</th>
    <th>' . AdminPhrase('tags_filter') . '</th>
  </tr>
  </thead>
  <tbody>
  <tr>
    <td><input type="text" name="searchtag"  style="width: 90%;" value="' . $search['tag'] . '" /></td>
    <td>';
  // Tag-Types:
  // 0 = all plugin-level tags entered by users
  // 1 = global tags maintained in admin area
  // 2 = "category"
  // 100+ = plugin level-maintained tags by type, e.g. type 3 = 103
  echo '
      <select name="searchtagtype" class="form-control">
        <option value="all" ' .
        ($search['tagtype']=='all'?'selected="selected"':'').'>- ('.AdminPhrase('tags_no_filter').')</option>
        <option value="0" ' .
        ($search['tagtype']=='0'?'selected="selected"':'').'>'.AdminPhrase('tags_tag_type_user').'</option>
        <option value="1" ' .
        ($search['tagtype']=='1'?'selected="selected"':'').'>'.AdminPhrase('tags_tag_type_global').'</option>
        <option value="2" ' .
        ($search['tagtype']=='2'?'selected="selected"':'').'>'.AdminPhrase('tags_tag_type_prefix').'</option>
        <option value="3" ' .
        ($search['tagtype']=='3'?'selected="selected"':'').'>'.AdminPhrase('tags_tag_type_category').'</option>
        <option value="-1" ' .
        ($search['tagtype']=='-1'?'selected="selected"':'').'>'.AdminPhrase('tags_tag_type_admin_only').'</option>
        <option value="-">-</option>
        <option value="others" ' .
        ($search['tagtype']=='others'?'selected="selected"':'').'>'.AdminPhrase('tags_tag_type_others').'</option>
      </select>
    </td>
    <td>
      <select id="searchpluginid" name="searchpluginid" class="form-control">
        <option value="0"'.(empty($search['pluginid'])?' selected="selected"':'').'>- ('.AdminPhrase('tags_no_filter').')</option>
        <option value="-1"'.($search['pluginid']=='-1'?' selected="selected"':'').'>'.AdminPhrase('tags_no_plugin_configured').'</option>
        ';
  foreach($plugins_arr as $p_id => $p_name)
  {
    if($p_id > 1) // do not list "empty" plugin
    {
      echo '
        <option value="'.$p_id.'" '.($search['pluginid']==$p_id?'selected="selected"':'').'>'.$p_name.'</option>';
    }
  }

  echo '
      </select>
    </td>
    ';

  // #####################################
  // Filter by usergroup
  // #####################################
  // Build usergroups lists for dialog and selection:
  echo '
      <td>
        <select id="searchusergroupid" name="searchusergroupid" class="form-control">
        <option value="0">- ('.AdminPhrase('tags_no_filter').')</option>';
  if($getusergroups = $DB->query('SELECT usergroupid, name FROM {usergroups} ORDER BY usergroupid ASC'))
  {
    while($usergroup = $DB->fetch_array($getusergroups,null,MYSQL_ASSOC))
    {
      $id = $usergroup['usergroupid'];
      echo '<option value="'.$id.'"'.($id==$search['usergroupid']?' selected="selected"':'').'>'.$usergroup['name'].'</option>';
    }
    unset($getusergroups,$id );
  }
  echo '</select>
    </td>
    <td>
      <select name="searchcensored" class="form-control">
        <option value="all" ' .
        ($censored == 'all' ? 'selected="selected"':'') .'>- ('.AdminPhrase('tags_no_filter').')</option>
        <option value="not_censored" ' .
        ($censored == 'not_censored'?'selected="selected"':'') .'>'.AdminPhrase('tags_not_censored').'</option>
        <option value="censored" ' .
        ($censored == 'censored'?'selected="selected"':'') .'>'.AdminPhrase('tags_censored').'</option>
      </select>
    </td>
    <td>
      <select name="searchsorting" class="form-control">
        <option value="'.TAGS_DATE_SORT_DESC.'" '.  ($sort == TAGS_DATE_SORT_DESC?'selected="selected"':'').'>'.AdminPhrase('tags_date_descending').'</option>
        <option value="'.TAGS_DATE_SORT_ASC.'" '.   ($sort == TAGS_DATE_SORT_ASC?'selected="selected"':'') .'>'.AdminPhrase('tags_date_asc').'</option>
        <option value="'.TAGS_TAG_SORT_ASC.'" '.    ($sort == TAGS_TAG_SORT_ASC?'selected="selected"':'') .'>'.AdminPhrase('tags_tag_asc').'</option>
        <option value="'.TAGS_TAG_SORT_DESC.'" '.   ($sort == TAGS_TAG_SORT_DESC?'selected="selected"':'') .'>'.AdminPhrase('tags_tag_descending').'</option>
        <option value="'.TAGS_TAGTYPE_SORT_ASC.'" '.($sort == TAGS_TAGTYPE_SORT_ASC?'selected="selected"':'') .'>'.AdminPhrase('tags_tagtype_asc').'</option>
        <option value="'.TAGS_TAGTYPE_SORT_DESC.'" '.($sort == TAGS_TAGTYPE_SORT_DESC?'selected="selected"':'') .'>'.AdminPhrase('tags_tagtype_descending').'</option>
        <option value="'.TAGS_PLUGIN_SORT_ASC.'" '. ($sort == TAGS_PLUGIN_SORT_ASC?'selected="selected"':'') .'>'.AdminPhrase('tags_plugin_asc').'</option>
        <option value="'.TAGS_PLUGIN_SORT_DESC.'" '.($sort == TAGS_PLUGIN_SORT_DESC?'selected="selected"':'') .'>'.AdminPhrase('tags_plugin_descending').'</option>
      </select>
    </td>
    <td style="width: 40px;">
      <select name="searchlimit" class="form-control">';
    $allowed_limits = array(10,20,30,50,100,200,500,1000);
    foreach($allowed_limits as $limit)
    {
      echo '
        <option value="'.$limit.'"'.($limit==$search['limit']?' selected="selected"':'').'>'.$limit.'</option>';
    }
    echo '</select>
    </td>
    <td class="center align-middle">
      <input type="submit" value="'.AdminPhrase('search').'" style="display:none" />
      <a id="tags-submit-search" href="#" onclick="return false;" title="'.AdminPhrase('tags_apply_filter').'"><i class="ace-icon fa fa-search blue bigger-130"></i></a>&nbsp;
      <a id="tags-clear-search" href="#" onclick="return false;" title="'.AdminPhrase('tags_clear_filter').'" ><i class="ace-icon fa fa-trash-o red bigger-130"></i></a>
    </td>
  </tr>
 </tbody> </table>
  </form>
  ';

  if(!$tags_count && empty($globaltags))
  {
 
	 echo '<div class="align-left">
  			<a class="mytooltip btn btn-success" data-toggle="tooltip" data-placement="top" href="#" Title="'.AdminPhrase('tags_global_tip').'" href="tags.php?action=create_global_tag"><i class="ace-icon fa fa-plus bigger-120"></i>&nbsp;'.
      AdminPhrase('tags_create_global_tag').'</a>
	  </div><br />';
    DisplayMessage(AdminPhrase('tags_no_tags_found'));
    return;
  }

  echo '<div class="align-left">
  			<a class="mytooltip btn btn-success" href="tags.php?action=create_global_tag" Title="'.AdminPhrase('tags_global_tip').'"><i class="ace-icon fa fa-plus bigger-120"></i>&nbsp;'.
      AdminPhrase('tags_create_global_tag').'</a>
      
	  </div>
	  <div class="space-4"></div>
	   <form action="tags.php" method="post" id="tags" name="tags">';

  StartTable(AdminPhrase('tags') . ' ('.$tags_count.')', array('table','table-bordered','table-striped'));

  echo '
 
  <input type="hidden" name="action" value="delete_tags" />
  '.PrintSecureToken().'
  <thead>
  <tr>
    <th>' . AdminPhrase('tags_tag2') . '</th>
    <th>' . AdminPhrase('tags_plugin_name') . '</th>
    <th>' . AdminPhrase('tags_plugin_item') . '</th>
    <th>' . AdminPhrase('tags_tag_type') . '</th>
    <th>' . AdminPhrase('tags_date') . '</th>
    <th class="center" width="70">
      <a id="checkall" rel="0" title="'.htmlspecialchars(AdminPhrase('tags_delete'),ENT_COMPAT).
		  '" href="#" onclick="javascript:return false;"><i class="ace-icon fa fa-trash-o red bigger-130"></i></a>
    </th>
  </tr>
  </thead>
  <tbody>';

  while($tag = $DB->fetch_array($gettags,null,MYSQL_ASSOC))
  {
    if(!strlen($tag['tag']))
    {
      $tag['tag'] = '(None)';
    }
    else if(strlen($tag['tag']) > 30)
    {
      $tag['tag'] = substr($tag['tag'], 0, 30) . '...';
    }

    echo '
  <tr>
    <td>
      <a href="tags.php?action=display_tag_form&amp;tagid=' . $tag['tagid'] .
      '"><i class="ace-icon fa fa-edit ';
    if(empty($tag['censored']))
	{
      echo ' "></i> ' . $tag['tag'];
	}
    else
	{
      echo ' red"></i> <span class="red"> '.$tag['tag'].'</span>';
	}
	
    echo '</a></td>
    <td>';
	
    if(empty($tag['pluginname']) || empty($tag['pluginid']))
    {
      echo '-</td><td>';
	  
      if($tag['tagtype']==2)
	  {
        echo $tag['html_prefix'];
	  }
      else
	  {
        echo '-';
	  }
	  
      echo '</td>';
    }
    else
    {
      echo empty($plugin_names[$tag['pluginid']]) ?  $tag['pluginname'] : $plugin_names[$tag['pluginid']];
      echo '</td>
    <td>';
	
      if(($tag['tagtype']==2) && empty($tag['objectid']))
	  {
        echo $tag['html_prefix'];
	  }
      else
	  {
        echo (empty($tag['objectid'])?'-':TranslateObjectID($tag['pluginid'], $tag['objectid']));
	  }
	  
      echo '</td>';
    }
	
    echo '
    <td>';
// Tag-Types:
// 0 = all plugin-level tags entered by users
// 1 = global tags maintained in admin area
// 2 = prefix (HTML)
// 3 = "category"
// 100+ = plugin level-maintained tags by type, e.g. type 3 = 103
    switch($tag['tagtype'])
    {
      case 0 : echo AdminPhrase('tags_tag_type_user'); break;
      case 1 : echo '<span class="blue">'.AdminPhrase('tags_tag_type_global').'</span>'; break;
      case 2 :
        if(!empty($tag['objectid']))
          echo '<span class="orange">User '.AdminPhrase('tags_tag_type_prefix').'</span>';
        else
          echo '<span class="brown">'.AdminPhrase('tags_tag_type_prefix').'</span>';
        break;
      case 3 : echo AdminPhrase('tags_tag_type_category'); break;
      default:
        echo $tag['tagtype'].' (special)';
        break;
    }
    echo '</td>
    <td>' . DisplayDate($tag['datecreated'],'',true) . '</td>
    <td class="center">
      <input type="hidden" name="tag_id_arr[]" value="' . $tag['tagid'] . '" />
      <input type="hidden" name="plugin_id_arr[]" value="' . $tag['pluginid'] . '" />
      <input type="hidden" name="object_id_arr[]" value="' . $tag['objectid'] . '" />
      <input type="checkbox" class="ace deletetag" name="delete_tag_id_arr[]" checkme="deletetag" value="' . $tag['tagid'] . '" />
	  <span class="lbl"></span>
    </td>
  </tr>';
  }
  echo  '</table></div>
  <div class="align-right">
      <button class="btn btn-sm btn-danger" id="deletetags" type="submit" value="'.AdminPhrase('tags_delete_tags').'" /><i class="ace-icon fa fa-trash-o"></i>&nbsp; ' . AdminPhrase('tags_delete_tags') .'</button>
    </div>
  
  </form>
  ';

  // pagination
  if(!empty($tags_count))
  {
    $p = new pagination;
    $p->items($tags_count);
    $p->limit($items_per_page);
    $p->currentPage($page);
    $p->adjacents(7);
    $p->target($pagination_target);
    $p->show();
  }

 

} //DisplayTags


// ############################################################################
// DISPLAY ORGANIZED COMMENTS
// ############################################################################

function DisplayOrganizedTags()
{
  global $DB;

  $gettags = $DB->query('SELECT p.pluginid, p.name AS pluginname, COUNT(*) AS tcount'.
                        ' FROM {plugins} p, {tags} t'.
                        ' WHERE p.pluginid = t.pluginid'.
                        ' GROUP BY p.pluginid ORDER BY tcount DESC');

  StartTable(AdminPhrase('tags_by_plugin'), array('table', 'table-bordered', 'table-striped'));
  echo '
 	<thead>
  <tr>
    <th>' . AdminPhrase('tags_plugin') . '</th>
    <th>' . AdminPhrase('tags_number_of_tags') . '</th>
  </tr>
  </thead>
  <tbody>';

  while($tag = $DB->fetch_array($gettags,null,MYSQL_ASSOC))
  {
    echo '
  <tr>
    <td><a href="tags.php?action=display_tags&amp;pluginid=' . $tag['pluginid']. '">' . $tag['pluginname'] . '</a></td>
    <td>' . $tag['tcount'] . '</td>
  </tr>';
  }
  echo '</tbody></table>';


} //DisplayOrganizedTags


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
