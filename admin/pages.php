<?php
// DEFINE CONSTANTS
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');

// INIT PRGM
@require(ROOT_PATH . 'includes/init.php');
include_once(ROOT_PATH.'includes/enablegzip.php');

// LOAD ADMIN LANGUAGE - REQUIRED!
// $admin_phrases is globally used core array!
$admin_phrases = LoadAdminPhrases(1);


// GET ACTION
$action = GetVar('action', 'display_pages', 'string');

if(Is_Ajax_Request())
{
  $result = 'ERROR: '.AdminPhrase('pages_error_invalid_data');
  if(($action=='getpagehint'))
  {
    $page = GetVar('hinttarget', '', 'string',false,true);
    if( CheckFormToken('securitytoken', false) &&
        preg_match('/^pageedit-([a-b])-\d{1,5}$/',$page,$matches) &&
        ($pageid = (int)substr($page,11)) )
    {
      $mobile = ($matches[0]=='b');
      if($category = $DB->query_first('SELECT c.*, d.designid, d.design_name'.
                                      ' FROM {categories} c'.
                                      ' LEFT JOIN {designs} d ON d.designid = c.'.($mobile?'mobile_':'').'designid '.
                                      ' WHERE c.categoryid = %d LIMIT 1', $pageid))
      {
        // Load Skins phrases
        $skin_phrases = LoadAdminPhrases(6);
        $pagename = (strlen($category['name']) ? htmlspecialchars($category['name'],ENT_COMPAT,SD_CHARSET) : AdminPhrase('pages_untitled'));
        $result = '<strong>'.$pagename.' - '.$skin_phrases['skins_layout'].': '.
                  (strlen($category['design_name']) ? $category['design_name'] : $category['designid']) .
                  '</strong><br>';
        $result .= GetPluginListForPage($category['categoryid'], false, true, $mobile);
      }
    }
    unset($pageid,$matches,$mobile);
  }
  elseif(($action=='getparentname')) //SD343
  {
    $categoryid = GetVar('categoryid', 0, 'whole_number',false,true);
    if($categoryid && ($categoryid > 0))
    {
      $DB->result_type = MYSQL_ASSOC;
      // Get current category
      if($category = isset($pages_md_arr[$categoryid])?$pages_md_arr[$categoryid]:false)
      {
        // Get parent category
        $category = isset($pages_md_arr[$category['categoryid']])?$pages_md_arr[$category['categoryid']]:false;
		
      }
      if(!empty($category))
      {
        $result = '---';
        if(!empty($category['parentid']))
        {
			
          $result = strlen($pages_md_arr[$category['parentid']]['name']) ? $pages_md_arr[$category['parentid']]['name'] : $pages_md_arr[$category['parentid']]['title'];
		 
        }
        $result = '<a class="parentselector btn btn-block btn-primary" rel="'.$category['parentid'].'" href="#" style="color:#008000"><span>'.$result.'</span></a>';
      }
    }
  }
  else
  if(($action=='setparentpage')) //SD343
  {
    $result = UpdateParentPage();
  }

  if(!headers_sent()) header("Content-Type: text/html; charset=".$sd_charset);

  if(($action=='setparentpage'))
    echo '<div style="padding:8px;text-align:center;width:auto;"><p style="text-align:center">'.$result.'</p></div>';
  else
    echo $result;

  $DB->close();
  exit();

} // ### END OF AJAX ###

// ############################################################################
// PRE-PROCESS SEARCH PARAMETERS COOKIE
// ############################################################################

$customsearch = GetVar('customsearch', 0, 'bool');
$clearsearch  = GetVar('clearsearch',  0, 'bool');

// Restore previous search array from cookie
$search_cookie = isset($_COOKIE[COOKIE_PREFIX . '_pages_search']) ? $_COOKIE[COOKIE_PREFIX . '_pages_search'] : false;

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

//SD342 for non-full admins check some filter values if permissions exist
if(empty($userinfo['adminaccess']))
{
  if(!empty($search['parentid']) &&
     (empty($userinfo['categoryviewids']) || !@in_array($search['parentid'],$userinfo['categoryviewids'])))
  {
    $search['parentid'] = 0;
  }
  //SD342 clear filter for page if no permission
  if(!empty($search['pluginid']))
  {
    $tmp = substr($search['pluginid'],0,1);
    if($tmp=='c')
    {
      $tmp = substr($search['pluginid'],1);
      if((empty($userinfo['custompluginadminids']) || !@in_array($tmp,$userinfo['custompluginadminids'])) &&
         (empty($userinfo['custompluginviewids']) || !@in_array($tmp,$userinfo['custompluginviewids'])))
      {
        $search['pluginid'] = 0;
      }
    }
    else
    if((empty($userinfo['pluginadminids']) || !@in_array($search['pluginid'],$userinfo['pluginadminids'])) &&
       (empty($userinfo['pluginviewids']) || !@in_array($search['pluginid'],$userinfo['pluginviewids'])))
    {
      $search['pluginid'] = 0;
    }
  }
}

if($customsearch)
{
  // Store search params in cookie
  sd_CreateCookie('_pages_search',base64_encode(serialize($search)));
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
  sd_CreateCookie('_pages_search', '');
}

$search['name']     = isset($search['name'])     ? (string)$search['name'] : '';
$search['title']    = isset($search['title'])    ? (string)$search['title'] : '';
$search['usergroupid'] = isset($search['usergroupid']) ? Is_Valid_Number($search['usergroupid']) : '';
$search['parentid'] = isset($search['parentid']) ? Is_Valid_Number($search['parentid']) : '';
$search['designid'] = isset($search['designid']) ? Is_Valid_Number($search['designid']) : '';
$search['pluginid'] = isset($search['pluginid']) ? (string)$search['pluginid'] : '';
$search['secure']   = isset($search['secure'])   ? (string)$search['secure'] : '';
$search['secure']   = in_array($search['secure'], array('0','1','all')) ? $search['secure'] : 'all';

if(!$customsearch) //SD370: activate filter if any field is set
{
  $customsearch = strlen($search['name']) || $search['title'] ||
                  strlen($search['usergroupid']) || strlen($search['parentid']) ||
                  strlen($search['designid']) || strlen($search['pluginid']) ||
                  ($search['secure']!=='all');

}

// ############################################################################
// DISPLAY ADMIN HEADER
// ############################################################################

//SD322: "cbox" is a flag to notify of the page being called by CeeBox
//       "$NoMenu" is currently only used for displaying plugin permissions
$cbox   = GetVar('cbox', false, 'bool');
define('IS_CBOX', (bool)$cbox);
$NoMenu = $cbox || ($action=='display_page_permissions');

// SD313 - Bind click event for deletion links to prompt user for confirmation
// SD322 - If JS is enabled, pass a parameter with every plugin permissions link,
//         process all "permissionlink" anchors to use CeeBox (if available)
// NOTE: do NOT move CSS outside or otherwise styles won't work as needed!!!

sd_header_add(array(
  #SD362: moved to core admin loader!
  #'css'   => array( SD_INCLUDE_PATH . 'css/jquery.jgrowl.css' ),
  #'js'    => array( ROOT_PATH . MINIFY_PREFIX_F . 'includes/javascript/jquery.easing-1.3.pack.js',
  #                  ROOT_PATH . MINIFY_PREFIX_F . 'includes/javascript/jquery.jgrowl.min.js' ),
  'other' => array('
<script type="text/javascript">
function ReloadPageTip() {
  var target = jQuery("#editsave").text();
  if(target !== "") {
    var link_obj = jQuery("#"+target);
    if(link_obj !== "undefined") {
      jQuery(link_obj).hide();
      var newurl = "pages.php?action=getpagehint&hinttarget="+target+"&securitytoken='.$userinfo['securitytoken'].'";
      jQuery.ajax({ async: true, cache: false, url: newurl,
        success: function(data){
          if(data !== undefined && data !== "0"){ jQuery(link_obj).prop("title", data); }
          jQuery(link_obj).show();
        }
      });
      jQuery("#editsave").text("");
    }
  }
}

if (typeof(jQuery) !== "undefined") {
jQuery(document).ready(function() {
  (function($){
	  
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
	
	$("a.deletelink").on("click", function(e) {
		var link = $(this).attr("href");
	  	e.preventDefault();
  		bootbox.confirm("'.AdminPhrase('pages_confirm_delete_page') .'", function(result) {
    	if(result) {
       		window.location.href = link;
    	}
  		});
	});

    $("#submit_plugin_selection").click(function(e) {
      e.preventDefault();
      $("#plugin_selection_form", parent.frames["page_plugin_selection_content"].document).submit();
    });

    $("a.subpage").click(function(e) { /* SD343 */
      e.preventDefault();
      var new_parent = $(this).attr("rel");
      $("#filterpages #searchparentid").attr("value", new_parent);
      $("#filterpages").submit();
    });
   
    $(".ugids_menu").chosen();
	$(".ugids_mobile").chosen();
	$(".ugids_view").chosen();
	$("#navigation_flag").chosen();
	$(".chosen-select").chosen({allow_single_deselect:true}); 
				//resize the chosen on window resize
			
				$(window)
				.off("resize.chosen")
				.on("resize.chosen", function() {
					$(".chosen-select").each(function() {
						 var $this = $(this);
						 $this.next().css({"width": $this.parent().width()});
					})
				}).trigger("resize.chosen");


    if ($.fn.ceebox != "undefined") {
      $("a.permissionslink").each(function(event) {
        $(this).addClass("cbox").attr("rel", "iframe modal:false height:360 width:1000");
        var link = $(this).attr("href") + "&cbox=1";
        $(this).attr("href", link);
      });

      $("a.editpagelink, a#editpagebutton").each(function() {
        $(this).addClass("cbox2").attr("rel", "iframe modal:false");
        var link = $(this).attr("href") + "&cbox=1";
        $(this).attr("href", link);
      });
      '.
      //SD322 - CeeBox'ed
      GetCeeboxDefaultJS(false, 'a.cbox').
      GetCeeboxDefaultJS(false, 'a.cbox2', 'ReloadPageTip();').
      '
    }

    $("a.editpagelink").click(function() {
      $("#editsave").text($(this).attr("id"));
    });
  })(jQuery);
});
}
</script>
<script type="text/javascript">// <![CDATA[
function sd_DialogClose() {
  if(jDialog && typeof jDialog !== "undefined") jDialog.close();
}
jQuery(document).ready(function() {
(function($){
  var sel_categoryid = 0, sel_parentid = 0, sel_target = false, sel_catlink = false, new_href = "";
  var users_token = \''.PrintSecureUrlToken().'\';
  var sd_timeout = 1500;
  var sd_timerID = false;

  $("<link>").appendTo("head").attr({rel: "stylesheet", type: "text/css", href: "./styles/'. ADMIN_STYLE_FOLDER_NAME .'/assets/css/jdialog.css" });
  $(document).delegate(".dialog_content select","change",(function(e){
    sel_parentid = $(this).val();
  }));

  $("a.parentselector").click(function(e){
		e.preventDefault();
    	jDialog.close();
    	var elem = $(this);
    	if(elem.is("td")) { elem = elem.find("a.parentselector"); }
    if(!elem) return false;
    sel_catlink = elem.parent();
    sel_categoryid = parseInt(elem.parent().parent().find("input.hidden_cat_id").val(),10);
    sel_parentid = parseInt(elem.attr("rel"),10);
    $(elem).jDialog({
      align : "left",
      content : $("div#parentselector_div").clone().html(),
      close_on_body_click : true,
      idName : "parentselector_dialog",
      lbl_close: "",
      title : "",
      title_visible : false,
      top_offset : -35,
      width : 350
    });
    $("div#parentselector_dialog select#categoryparentid").val(sel_parentid);
    new_href = "pages.php?categoryid="+sel_categoryid+"&amp;action=setparentpage" + users_token;
    $("div#parentselector_dialog a.parentpagechangelink").attr("href", new_href);
    return false;
  });

  $(document).delegate("a.parentpagechangelink","click",(function(e){
    e.preventDefault();
    $(this).attr("disabled","disabled");
    sel_target = $(this).closest("div");
    if(sel_categoryid > 0 && sel_parentid >= 0) {
      new_href = this.href+"&amp;parentid=" + sel_parentid;
      $(this).attr("href", new_href);
      $(sel_target).load(new_href, {}, function(response, status, xhr){
        if(sel_catlink!==false) {
        $(sel_catlink).load("pages.php?action=getparentname&amp;categoryid="+sel_categoryid, {}, function(response, status, xhr){});
        }
        sd_timerID = setTimeout("sd_DialogClose();", sd_timeout);
      });
    };
    return false;
  }));

}(jQuery));
});
// ]]>
</script>
')));
// SD313 - Bind click event for deletion links to prompt user for confirmation
// SD322 - If JS is enabled, pass a parameter with every plugin permissions link,
//         process all "permissionlink" anchors to use CeeBox (if available)
// NOTE: do NOT move CSS outside or otherwise styles won't work as needed!!!


// ############################################################################
// CHECK PAGE ACCESS
// ############################################################################
CheckAdminAccess('pages');

$load_wysiwyg = 0;
DisplayAdminHeader(array('Pages', $action), null, '', $NoMenu);
$pcount = $DB->query_first("SELECT COUNT(*) as total FROM {categories}");
$parentselection = CreateParentSelection('','','',$pcount);
if(($pcount['total'] > 100) && !defined('SD_ENHANCED_PAGES')) define('SD_ENHANCED_PAGES', true);

$listedcategories = array(); // used for finding lost categories
                             // which can happen if you make 2 categories parents each
                             // other (news parent = gallery, gallery parent = news)

// ############################################################################
// DELETE PAGE
// ############################################################################

function DeletePage()
{
  global $DB, $sdlanguage, $SDCache;

  // SD313: security check against spam/bot submissions
  if(!CheckFormToken())
  {
    RedirectPage('pages.php','<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $categoryid = GetVar('categoryid', 0, 'string');
  //SD343: better check of category id
  if(!ctype_digit($categoryid) || !Is_Valid_Number($categoryid,0,2))
  {
    RedirectPage('pages.php','<strong>Invalid page specified!</strong>',2,true);
    return;
  }

  // SD313x: remove existing page cache file
  if(!empty($categoryid) && isset($SDCache))
  {
    $SDCache->delete_cacheid(CACHE_PAGE_PREFIX.(int)$categoryid);
    $SDCache->delete_cacheid(MOBILE_CACHE.CACHE_PAGE_PREFIX.(int)$categoryid); //SD370
  }

  // delete the category
  $DB->query('DELETE FROM {categories} WHERE categoryid = %d', $categoryid);
  $DB->query('DELETE FROM {pagesort}   WHERE categoryid = %d', $categoryid);
  if(SD_MOBILE_FEATURES) //SD370
  $DB->query('DELETE FROM {pagesort_mobile} WHERE categoryid = %d', $categoryid);

  // remove this categoryid from the usergroup rows
  $usergroups = $DB->query('SELECT usergroupid, categoryviewids, categorymenuids'.
                           (SD_MOBILE_FEATURES?',categorymobilemenuids':'').
                           ' FROM {usergroups} ORDER BY usergroupid');

  while($usergroup = $DB->fetch_array($usergroups))
  {
    // search and if found then remove the deleted categoryid from the allow view column
    $categoryviewids = sd_ConvertStrToArray($usergroup['categoryviewids']);
    if(false !== ($idx = array_search($categoryid, $categoryviewids)))
    {
      unset($categoryviewids[$idx]);
    }
    $categoryviewids = @implode(',', $categoryviewids);

    // search and if found then remove the deleted categoryid from the allow menu column
    $categorymenuids = sd_ConvertStrToArray($usergroup['categorymenuids']);
    if(false !== ($idx = array_search($categoryid, $categorymenuids)))
    {
      unset($categorymenuids[$idx]);
    }
    $categorymenuids = @implode(',', $categorymenuids);

    // search and if found then remove the deleted categoryid from mobile column
    if(SD_MOBILE_FEATURES) //SD370
    {
      $categorymobilemenuids = sd_ConvertStrToArray($usergroup['categorymobilemenuids']);
      if(false !== ($idx = array_search($categoryid, $categorymobilemenuids)))
      {
        unset($categorymobilemenuids[$idx]);
      }
      $categorymobilemenuids = @implode(',', $categorymobilemenuids);
    }

    // now update the usergroup with the updated category values
    $DB->query('UPDATE {usergroups}'.
               " SET categoryviewids = '$categoryviewids',
                     categorymenuids = '$categorymenuids'".
               (SD_MOBILE_FEATURES?",categorymobilemenuids = '$categorymobilemenuids'":'').
               " WHERE usergroupid = %d", $usergroup['usergroupid']);
  }

  // delete subcategories of the category
  DeleteSubcategories($categoryid);

  RedirectPage('pages.php', AdminPhrase('pages_page_deleted'));

} //DeletePage


// ############################################################################
// DISPLAY PAGE PERMISSIONS
// ############################################################################

function DisplayPagePermissions($embedded = false)
{
  global $DB;

  $categoryid = GetVar('categoryid', 0, 'whole_number');

  if(!$embedded)
  {
    echo '
    <form method="post" action="pages.php" class="form-horizontal">
   '.PrintSecureToken().'
    <input type="hidden" name="action" value="update_page_permissions" />
    <input type="hidden" name="categoryid" value="' . $categoryid . '" />
    <input type="hidden" name="cbox" value="' . IS_CBOX. '" />
   ';
  }

  $getusergroups = $DB->query('SELECT usergroupid, name, categorymenuids, categoryviewids, categorymobilemenuids
                               FROM {usergroups} ORDER BY usergroupid');

	

  // Build selection listboxes
  $selm = '<select class="form-control ugids_menu" name="ugids_menu[]" size="10" multiple="multiple"">
           <option value="0">[' . AdminPhrase('pages_usergroup_none') . ']</option>';

  $selmm = '<select class="form-control ugids_mobile" name="ugids_mobile_menu[]" size="10" multiple="multiple" >
           <option value="0">[' . AdminPhrase('pages_usergroup_none') . ']</option>';

  $selv = '<select class="form-control ugids_view " name="ugids_view[]" size="10" multiple="multiple" >
           <option value="0">[' . AdminPhrase('pages_usergroup_none') . ']</option>';

  while($usergroup = $DB->fetch_array($getusergroups,null,MYSQL_ASSOC))
  {
    $uid = $usergroup['usergroupid'];

    $categorymenuids = '';
    if(strlen($usergroup['categorymenuids']))
    {
      $categorymenuids = sd_ConvertStrToArray($usergroup['categorymenuids']);
    }

    if($categoryid)
    {
      $selm .= '<option value="' . $uid . '" ' .
               (!empty($categorymenuids) && @in_array($categoryid, $categorymenuids)?' selected="selected"':'').
               '>' . $usergroup['name'] . '</option>';
    }
    else
    {
      $selm .= '<option value="' . $uid . '" selected="selected">' . $usergroup['name'] . '</option>';
    }

    $categoryviewids = '';
    if(strlen($usergroup['categoryviewids']))
    {
      $categoryviewids = sd_ConvertStrToArray($usergroup['categoryviewids']);
    }

    if($categoryid)
    {
      $selv .= '<option value="' . $uid . '" '.
               (!empty($categoryviewids) && @in_array($categoryid, $categoryviewids)?' selected="selected"':'').
               '>' . $usergroup['name'] . '</option>';
    }
    else
    {
      $selv .= '<option value="' . $uid . '" selected="selected">' . $usergroup['name'] . '</option>';
    }

    //SD370:
    $categorymobilemenuids = '';
    if(strlen($usergroup['categorymobilemenuids']))
    {
      $categorymobilemenuids = sd_ConvertStrToArray($usergroup['categorymobilemenuids']);
    }

    if($categoryid)
    {
      $selmm .= '<option value="' . $uid . '" '.
                (!empty($categorymobilemenuids) && @in_array($categoryid, $categorymobilemenuids)?' selected="selected"':'').
                '>' . $usergroup['name'] . '</option>';
    }
    else
    {
      $selmm .= '<option value="' . $uid . '" selected="selected">' . $usergroup['name'] . '</option>';
    }
  }

  $selm  .= '</select>';
  $selmm .= '</select>';
  $selv  .= '</select>';

  $DB->free_result($getusergroups);
	
	echo '<h3 class="header blue lighter">' . AdminPhrase('pages_usergroup_page_permissions') . '</h3>
		<div class="space-20"></div>
			<div class="form-group">
				<label class="col-sm-3 control-label" for="">' . AdminPhrase('common_display_in_menu') . '</label>
				<div class="col-sm-6">
					' . $selm . '
				</div>
			</div>
		<div class="form-group">
			<label class="col-sm-3 control-label" for="">' . AdminPhrase('common_display_in_mobile_menu') . '</label>
			<div class="col-sm-6">
				' . $selmm .'
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label" for="">' . AdminPhrase('common_allow_view') . '</label>
			<div class="col-sm-6">
				' . $selv .'
			</div>
		</div>';
  

  if(!$embedded)
  {
    echo '<div class="center"><button type="submit" class="btn btn-info" value="" /><i class="ace-icon fa fa-check"></i> ' . AdminPhrase('pages_usergroup_update') . '</button>
    </form>';
  }

} //DisplayPagePermissions


// ############################################################################
// UPDATE CATEGORY PERMISSIONS
// ############################################################################

function UpdatePagePermissions($categoryid = 0)
{
  global $DB, $sdlanguage;

  // SD313: security check against spam/bot submissions
  if(!CheckFormToken())
  {
    RedirectPage('pages.php','<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $action            = GetVar('action', '', 'string');
  $usergroups_menu   = GetVar('ugids_menu', array(), 'a_int');
  $usergroups_mobile = GetVar('ugids_mobile_menu', array(), 'array'); //SD370
  $usergroups_view   = GetVar('ugids_view', array(), 'a_int');

  // if we are creating or saving a page, then categoryid is sent as an argument
  if(!$categoryid)
  {
    $categoryid = GetVar('categoryid', 0, 'whole_number');
  }

  // switch menu and view permissions for usergroups
  if($getusergroups = $DB->query('SELECT * FROM {usergroups} ORDER BY usergroupid'))
  {
    $ugcats = $ugmobile = $ugviews = array();
    // Iterate through all existing usergroups
    while($usergroup = $DB->fetch_array($getusergroups,null,MYSQL_ASSOC))
    {
      $ugcats[$usergroup['usergroupid']]   = $usergroup['categorymenuids'];
      $ugviews[$usergroup['usergroupid']]  = $usergroup['categoryviewids'];
      $ugmobile[$usergroup['usergroupid']] = isset($usergroup['categorymobilemenuids'])?$usergroup['categorymobilemenuids']:''; //SD370
    }
    $DB->free_result($getusergroups);
    unset($getusergroups);

    foreach($ugcats as $uid => $entry)
    {
      // ***** Process "Display in Menu" categories *****
      $cats = strlen($entry) ? sd_ConvertStrToArray($entry) : array(1);
      $cats = array_values($cats);
      if(in_array($uid, $usergroups_menu))
      {
        // Add category to menu for usergroup (and resort)
        if(!in_array($categoryid, $cats))
        {
          $cats[] = $categoryid;
          sort($cats, SORT_NUMERIC);
        }
      }
      else
      {
        // Remove category from menu for usergroup
        if(in_array($categoryid, $cats) && (($idx = array_search($categoryid,$cats)) !== false))
        {
          unset($cats[$idx]);
        }
      }
      $cats = implode(',',$cats);

      // ***** Process "Allow view" categories *****
      $views = strlen($ugviews[$uid]) ? sd_ConvertStrToArray($ugviews[$uid]) : array(1);
      $views = array_values($views);
      if(in_array($uid, $usergroups_view))
      {
        if(!in_array($categoryid, $views))
        {
          $views[] = $categoryid;
          sort($views, SORT_NUMERIC);
        }
      }
      else
      {
        if(in_array($categoryid, $views) && (($idx = array_search($categoryid,$views)) !== false))
        {
          unset($views[$idx]);
        }
      }
      $views = implode(',',$views);

      // ***** Process "Display in Mobile Menu" categories *****
      $mobi = strlen($ugmobile[$uid]) ? sd_ConvertStrToArray($ugmobile[$uid]) : array(1);
      $mobi = array_values($mobi);
      if(in_array($uid, $usergroups_mobile))
      {
        if(!in_array($categoryid, $mobi))
        {
          $mobi[] = $categoryid;
          sort($mobi, SORT_NUMERIC);
        }
      }
      else
      {
        if(in_array($categoryid, $mobi) && (($idx = array_search($categoryid,$mobi)) !== false))
        {
          unset($mobi[$idx]);
        }
      }
      $mobi = implode(',',$mobi);

      $DB->query("UPDATE {usergroups}
                  SET categorymenuids = '%s',
                      categoryviewids = '%s',
                      categorymobilemenuids = '%s'
                  WHERE usergroupid = %d", $cats, $views, $mobi, $uid);

    } //foreach

  } // categorymenuids / categoryviewids


  if($action == 'update_page_permissions')
  {
    //SD322: either close CeeBox (if JS is enabled) or redirect page
    if(IS_CBOX)
    {
      echo '<div class="alert alert-success">'.
      sd_CloseCeebox(2, AdminPhrase('pages_usergroup_permissions_updated').'
      <br /><br /><input class="btn btn-info" type="button" onclick="parent.jQuery.fn.ceebox.closebox(\'fast\');" value="'.AdminPhrase('close_window').'" />');
      echo '
      </div>
      ';
    }
    else
    {
      RedirectPage('pages.php?action=display_pages', AdminPhrase('pages_usergroup_permissions_updated'));
    }
  }
  else // saving page
  {
    return true;
  }

} //UpdatePagePermissions


// ############################################################################
// CATEGORY PARENT SELECTION
// ############################################################################

function CreateParentSelection($parentselection = '', $parentid = 0, $sublevelmarker = '', &$pcount=0)
{
  global $DB;

  // start selection box
  if($parentid != 0)
  {
    $sublevelmarker .= '- - ';
  }

  if($getcategories = $DB->query('SELECT categoryid, parentid, name FROM {categories}
                               WHERE parentid = %d ORDER BY displayorder',$parentid))
  while($category = $DB->fetch_array($getcategories,null,MYSQL_ASSOC))
  {
    $pcount++;
    $parentselection .= '<option value="' . $category['categoryid'] . '">' . $sublevelmarker . $category['name'] . '</option>';
    $parentselection = CreateParentSelection($parentselection, $category['categoryid'], $sublevelmarker, $pcount);
  }

  // end the selection box
  if($parentid == 0)
  {
    return '<select id="categoryparentid" name="categoryparentid" style="min-with:100px;width:98%;font-size:12px;"><option value="0">---</option>' . $parentselection . '</select>';
  }
  else
  {
    return $parentselection;
  }

} //CreateParentSelection


// ############################################################################

function GetPageHint($skin_engine, $pagename, array $category, $mobile=false)
{
  // Load Skins phrases
  $skin_phrases = LoadAdminPhrases(6);

  // Depending on Skin Engine either "old" plugin position editing within one form
  // OR with the new engine open an Ajax-Popup with full preview.
  $result = '';
  $categoryid = $category['categoryid'];
  $plugins_list = GetPluginListForPage($categoryid, false, true, !empty($mobile));

  $title_attr = $pagename.' - '.$skin_phrases['skins_layout'].': ';
  if(!empty($mobile))
  {
    if(isset($category['mobile_design_name']))
      $title_attr .= $category['mobile_design_name'];
    else
      $title_attr .= $category['designid'];
  }
  else
  {
    $title_attr .= (strlen($category['design_name']) ? $category['design_name'] : $category['designid']);
  }
  $title_attr .= ' <br /> ' . $plugins_list;
 

  if($skin_engine != 2)
    $result = '<a data-toggle="popover" data-rel="popover" data-placement="right" data-trigger="hover" class="pagehint" href="pages.php?action=edit_page&amp;categoryid=';
  else
  if(!empty($mobile))
    $result = '<a data-toggle="popover" data-rel="popover" data-placement="right" data-trigger="hover" id="pageedit-b-'.$categoryid.'" class="pagehint editpagelink" href="page_plugin_selection.php?mobile=1&categoryid=';
  else
    $result = '<a data-toggle="popover" data-rel="popover" data-placement="right" data-trigger="hover" id="pageedit-a-'.$categoryid.'" class="pagehint editpagelink" href="page_plugin_selection.php?categoryid=';

  $result .= $categoryid . SD_URL_TOKEN . '" title="'.$pagename.'" data-content="' . $title_attr . '"><i class="ace-icon fa fa-pencil bigger-130"></i></a>';

  return $result;

} //GetPageHint


// ############################################################################

function DisplayPages($parentid = 0, $sublevelmarker = '',
                      $skin_id=0, $skin_engine=0, $pagesquery='')
{
  global $DB, $customsearch, $parentselection, $plugin_names, $listedcategories,
         $sdlanguage, $userinfo, $search, $pages_md_arr;

  // $listcategories is used for finding lost categories, which can happen
  // if you make 2 categories a parent to each other
  // (news parent = gallery, gallery parent = news)

  if(isset($sublevelmarker) && (strlen($sublevelmarker) > 80)) return;
  $items_per_page = GetVar('searchlimit', 20, 'whole_number', true, false);
  $page = GetVar('page', 1, 'whole_number');
  $limit = '';
  $isEnhanced = defined('SD_ENHANCED_PAGES') && SD_ENHANCED_PAGES; //SD343
  if($isEnhanced) //SD343
  {
    $limit = ' LIMIT '.(($page-1)*$items_per_page).','.$items_per_page;
  }
  
  $parent_page = false;
  if( !empty($search['parentid']) && $isEnhanced &&
      isset($pages_md_arr[$search['parentid']]) ) //SD343
  {
    $parent_page = $pages_md_arr[$search['parentid']];
  }
  $parent_crumb = $table_header = ''; //SD370

  // ##########################################################################
  // First function call: display filter bar and list headers
  // ##########################################################################
  if(!empty($parentid))
  {
    $sublevelmarker .= '- - ';
    $customsearch = 0;
  }
  else
  {
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
      DisplayMessage(AdminPhrase('no_pages_exist'));
      return;
    }

    // ########################################################################
    // DISPLAY FILTER BAR
    // ########################################################################

    echo '
    <img alt="" src="' .ADMIN_IMAGES_FOLDER. 'ceetip_arrow.png" style="display:none;" width="21" height="11" />
    <form action="pages.php?action=display_pages" id="filterpages" name="filterpages" method="post" >
    <input type="hidden" name="customsearch" value="0" />
    <input type="hidden" name="clearsearch" value="0" />
    <input type="hidden" name="customsearch" value="1" />
    <div class="table-header">
      ' . AdminPhrase('filter_pages') . '
    </div>
    <div class="table-responsive">
	<table  class="table table-bordered">
		<thead>
    <tr>
      <th>' . AdminPhrase('pages_name') . '</th>
      <th>' . AdminPhrase('pages_title') . '</th>
      <th>' . AdminPhrase('common_usergroup') . '</th>
      <th>' . AdminPhrase('pages_parent_page') . '</th>
      <th>' . AdminPhrase('pages_skin_layout') . '</th>
      <th>' . AdminPhrase('pages_plugins') . '</th>
      <th>' . AdminPhrase('pages_secure') . '</th>
      <th width="5%">' . AdminPhrase('pages_filter').'</th>
    </tr>
	</thead>
	<tbody>

    <tr>
      <td><input class="form-control" type="text" name="searchname"  value="' . $search['name'] . '" /></td>
      <td><input 4147098756723409,  type="text" name="searchtitle" value="' . $search['title'] . '" /></td>';

    // ####################
    // Filter by usergroup
    // ####################
    // Build usergroups lists for dialog and selection:
    echo '
      <td>
        <select id="searchusergroupid" name="searchusergroupid" class="form-control">
        <option value="0">-</option>';
    if($getusergroups = $DB->query('SELECT usergroupid, name FROM {usergroups} ORDER BY usergroupid ASC'))
    {
      while($usergroup = $DB->fetch_array($getusergroups,null,MYSQL_ASSOC))
      {
        $id = $usergroup['usergroupid'];
        echo '<option value="'.$id.'"'.($id==$search['usergroupid']?' selected="selected"':'').'>'.$usergroup['name'].'</option>';
      }
      unset($getusergroups,$id );
    }

    echo '
        </select>
      </td>
      <td>';

    // ######################################
    // Filter by parent category
    // (see function_admins.php for details)
    // ######################################
    DisplayCategorySelection($search['parentid'], 1, 0, '', 'searchparentid',
                             '');

    echo '</td>';

    // ######################
    // Filter by used design
    // ######################
    echo '
      <td>
        <select id="searchdesignid" name="searchdesignid" class="form-control">
        <option value="0" '.(empty($search['designid'])?' selected="selected"':'').'>-</option>';
    if($get_designs = $DB->query('SELECT designid, design_name FROM {designs}
                                  WHERE skinid = %d
                                  ORDER BY design_name', $skin_id))
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
          echo AdminPhrase('pages_skin_layout').' '.$design_arr['designid'];
        }
        echo '</option>';
      }
    }
    echo '
        </select>
      </td>';

    // ##############################
    // Filter by plugin used on page
    // ##############################
    echo '
      <td>
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
      <td>
        <select name="searchsecure" class="form-control">
          <option value="all" '.($search['secure'] == 'all'?'selected="selected"':'') .'>-</option>
          <option value="0" ' . (empty($search['secure'])  ?'selected="selected"':'') .'>'.AdminPhrase('common_no').'</option>
          <option value="1" ' . ($search['secure'] == '1'  ?'selected="selected"':'') .'>'.AdminPhrase('common_yes').'</option>
        </select>
      </td>
      <td class="align-middle">
        
        <a id="pages-submit-filter" href="#" onclick="return false;" title="'.
          AdminPhrase('pages_apply_filter').'" ><i class="ace-icon fa fa-search bigger-130"></i></a>&nbsp;&nbsp;
		  
        <a id="pages-clear-filter" href="#" onclick="return false;" title="'.
          AdminPhrase('pages_remove_filter').'" ><i class="red ace-icon fa fa-trash-o bigger-130"></i></a>
		  <input type="submit" value="'.AdminPhrase('pages_filter').'" style="display: none;" />
      </td>
    </tr>
	</tbody>
    </table></div>
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
    <form action="pages.php" id="pagestable" method="post">
    '.PrintSecureToken();
	
    echo '
   <div class="table-responsive">
    <div class="table-header">
      ' . AdminPhrase('pages_pages') . ' (' . $total_rows . ')
      </div>
    <table id="pages-table" class="table table-bordered table-striped">';

    if(is_array($parent_page))
    {
      $crumb = ' <i class="ace-icon fa fa-angle-double-left"></i> <b>'.$pages_md_arr[$search['parentid']]['name'].'</b>';
      $crumb_page = $parent_page;
      while(!empty($crumb_page['parentid']))
      {
        $crumb_page = $pages_md_arr[$crumb_page['parentid']];
        $crumb = '
        <a class="subpage btn btn-sm btn-white btn-info btn-bold" rel="'.(int)$crumb_page['categoryid'].
        '" href="#"><i class="ace-icon fa fa-angle-double-left"></i>'.
        $crumb_page['name'].'</a> '.$crumb;
      }
      $parent_crumb = '<a class="subpage" rel="0" href="#">'.
                      '<span class="btn_bg" style="min-width:6px !important"></span></a> '.
                      $crumb;
      unset($crumb_page,$crumb);
    }

    $table_header = '
    <thead><tr>';
    if($isEnhanced) //SD343
    {
      $table_header .= '<th class="center"><span class="btn btn-sm btn-white btn-primary btn-bold"><i class="ace-icon fa fa-angle-double-right"></i></span> </th>';
    }
	
    $table_header .= '
      <th width="5%">' . AdminPhrase('pages_display_order') . '</th>
      <th>' . AdminPhrase('pages_view_page') . '</th>
      <th>' . AdminPhrase('pages_name') . ' (HTML)</th>
      <th class="align-center">' . AdminPhrase('pages_parent_page') . '</th>
      <th width="5%">' . AdminPhrase('pages_content') . '</th>
      <th width="5%">' . AdminPhrase('pages_mobile_content') . '</th>
      <th colspan="3" class="align-center">' . AdminPhrase('pages_page_options') . '</th>
    </tr>
	</thead>
	<tbody>';

  } // END OF HEADER BAR

  // If no pages were found (due to filtering), then just display a message
  if(empty($total_rows) && ($parentid==0))
  {
    echo '</tbody></table></div>';
    if($customsearch)
    {
      DisplayMessage(AdminPhrase('no_pages_found'), false);
    }
    else
    {
      DisplayMessage(AdminPhrase('no_pages_exist'), true);
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
         (!SD_MOBILE_FEATURES?'':
         ' LEFT JOIN '.PRGM_TABLE_PREFIX.'designs d2 ON d2.designid = c.mobile_designid ').
         $pagesquery;
  if(!$customsearch) $sql .= ($pagesquery == 'WHERE' ? '' : ' AND ');
  if(!$isEnhanced) //SD343
  {
    if(!$customsearch)
    {
      $sql .= ' ((c.parentid = '.$parentid.') OR (c.parentid > 0 AND c2.categoryid IS NULL)) ';
    }
  }
  else
  {
    if(!$customsearch)
    {
      $sql .= ' ((c.parentid = '.$search['parentid'].') OR (c.parentid > 0 AND c2.categoryid IS NULL)) ';
    }
    //for pagination:
    $total_rows = 0;
    if($total_rows_arr = $DB->query_first('SELECT count(*) page_count '.$sql))
      $total_rows = (int)$total_rows_arr['page_count'];
  }
  $getcategories = $DB->query('SELECT c.*, d.design_name, '.
    (!SD_MOBILE_FEATURES?"''":'d2.design_name').' as mobile_design_name, '.
    'c2.categoryid real_parentid, c2.name parent_name'.
    $sql.' ORDER BY c.parentid, c.displayorder, c.name '.$limit);

  //SD370: with enhanced display mode, the parent page was not available here;
  // now we push the "parent" page to the top of the list;
  // instead of while loop we use fetch_array_all and foreach
  $tmp = $DB->fetch_array_all($getcategories);
  if($isEnhanced && !empty($parent_page))
  {
    array_unshift($tmp, $parent_page);
  }
  foreach($tmp as $pidx => $category)
  {
    $pagename = (strlen($category['name']) ?
                  htmlspecialchars(strip_alltags(sd_unhtmlspecialchars($category['name'],ENT_COMPAT,SD_CHARSET))):
                  AdminPhrase('pages_untitled'));
    if(strlen($category['name']))
    {
      $listedcategories[] = $category['name'];
      if(!empty($category['parentid']) && !isset($category['real_parentid']))
        $unlistedcategories[] = $category['name'];
    }

    if(!$isEnhanced)
    {
      if(!$pidx) echo $table_header;
      echo '<tr>';
    }
    else
    {
      if(empty($pidx) && ($parent_page['categoryid'] > 1))
      {
        echo  '
      <tr>
        <td colspan="10">
          '.$parent_crumb.'
        </td>
      </tr>';
      }

      if($customsearch && !empty($parentid)) // show header for search, too
      {
        echo $table_header;
        $table_header = '';
        $customsearch = false;
      }
	  else
	  {
		  echo $table_header;
		  $table_header = '';
	  }

      if($category['categoryid'] != $parent_page['categoryid'])
      {
        if(empty($pidx) && ($category['categoryid']<=1))
        {
          echo $table_header;
        }
        $sub_pages = 0;
        $sub_pages_link = '-';
        $DB->result_type = MYSQL_ASSOC;
        if($total_rows_arr = $DB->query_first('SELECT count(*) page_count FROM {categories} c'.
                                              ' WHERE c.parentid = %d',
                                              $category['categoryid']))
        {
          if($sub_pages = (int)$total_rows_arr['page_count'])
          {
            $sub_pages_link = '<a  class="btn btn-sm btn-white btn-primary btn-bold subpage" rel="'.(int)$category['categoryid'].'" title="'.
                              htmlspecialchars(AdminPhrase('view_subpages')).'" href="#">'.  $sub_pages . '&nbsp;<i class="ace-icon fa fa-angle-double-right"></i></a>';
          }
        }
        echo '
        <tr id="pagerow_'.(int)$category['categoryid'].'">
          <td class="center">
            '.$sub_pages_link.'
          </td>';
      }
      else
      {
        echo '
        <tr id="pagerow_'.(int)$category['categoryid'].'">
          <td  colspan="2" class="center" >
            <a class=" btn btn-sm btn-white btn-primary btn-bold subpage" rel="'.(int)$parent_page['parentid'].
            '" href="#"><i class="ace-icon fa fa-angle-double-left"> </i></a>
            <input type="hidden" class="hidden_cat_id" name="categoryids[]" value="'.$category['categoryid'].'" />
            <input type="hidden" name="displayorders[]" value="'.$category['displayorder'].'" />
          </td>
          ';
      }
    }
    if(!$isEnhanced || ($category['categoryid'] != $parent_page['categoryid']))
    {
      echo '
      <td >
        <input type="hidden" class="hidden_cat_id" name="categoryids[]" value="'.$category['categoryid'].'" />
        <input type="text" class="col-xs-10" name="displayorders[]" value="'.$category['displayorder'].'"  maxlength="4" />
      </td>';
    }
    echo '
      <td>
        ' . $sublevelmarker .
        '<a data-toggle="popover"  data-rel="popover" data-placement="right" data-trigger="hover" class="pagehint" title="' . $pagename .'"'.
        'href="pages.php?action=edit_page&amp;categoryid=' .
        $category['categoryid'] . SD_URL_TOKEN . '"';

    $titles = array();
    if(strlen($category['title']))
      $titles[] = '<strong>' . AdminPhrase('pages_title').':</strong> '.$category['title'];
    if(strlen($category['urlname']))
      $titles[] = '<strong>' . AdminPhrase('pages_seo_name').':</strong> '.$category['urlname'];
    if(count($titles))
      echo ' data-content="'.implode("<br />",$titles).'" ';

    echo '><i class="blue ace-icon fa fa-edit bigger-130"></i>&nbsp;' . htmlspecialchars($pagename) . '</a>
      </td>
      <td class="align-middle">
        <input type="text" name="categorynames[]" maxlength="64" class="col-xs-10" value="' .
        htmlspecialchars($category['name']) . '"  />
      </td>
      <td class="parentselector_top center" >
        <a class="parentselector btn btn-primary btn-block" rel="'.$category['parentid'].'" href="#" >
        <span>'.
        (empty($category['real_parentid']) ? '---' : $category['parent_name']).
        '</span></a>
      </td>
      <td class="align-middle center" >';

    //SD322: if category links to an external page, do NOT display plugin positioning!
    echo (strlen($category['link']) ? '-' : GetPageHint($skin_engine, $pagename, $category)).'</td>
      <td class="align-middle center">';

    //SD370: added mobile page hint
    echo (strlen($category['link']) ? '-' : GetPageHint($skin_engine, $pagename, $category, true)).'
      </td>
      <td class="align-middle center">';
    if(strlen($category['link']) > 0)
    {
      if((strtolower(substr($category['link'], 0, 4)) == 'http') || (strtolower(substr($category['link'], 0, 3)) == 'www'))
      {
        $link = $category['link'];
      }
      else
      {
        $link = SITE_URL . $category['link'];
      }
      echo '<a title="'.AdminPhrase('pages_preview').'" href="'.$link.
           '" target="_blank"><i class="blue ace-icon fa fa-search bigger-130"></i></a> ';
    }
    else
    {
      //TODO: what is with SSL?
      echo '<a title="'.AdminPhrase('pages_preview').'" href="'.
           SITE_URL.'index.php?categoryid=' . $category['categoryid'] .
           '" target="_blank"><i class="blue ace-icon fa fa-search bigger-130"></i></a> ';
    }
    echo '</td><td class="align-middle center">';

    if($category['categoryid'] == 1)
    {
      echo '<i class="red ace-icon fa fa-times-circle bigger-130"></i>';
    }
    else
    {
      echo '<a class="deletelink" title="'.AdminPhrase('pages_delete').
           '" href="pages.php?action=delete_page&amp;categoryid=' . $category['categoryid'] .
           SD_URL_TOKEN.'"><i class="red ace-icon fa fa-trash-o bigger-130"></i></a>';
    }

    echo '</td><td class="align-middle center">';
    echo '<a title="'.AdminPhrase('pages_permissions').
         '" class="permissionslink" href="'.
         'pages.php?action=display_page_permissions&amp;categoryid=' .
         $category['categoryid'].SD_URL_TOKEN.
         '"><i class="ace-icon fa fa-key bigger-130 orange"></i></a></td>
          </tr>';

    //SD370: display column titles below parent row in enhanced mode
    if(empty($pidx) && ($parent_page['categoryid']>1))
    {
      echo $table_header;
    }

    // In case of wrong parenting (id = parentid) do exit the function now!
    if(($pagesquery != 'WHERE') && ($category['categoryid'] == $parentid))
    {
      return;
    }

    if(!$customsearch)
    {
      if($isEnhanced) //SD343
      {
        if(count($listedcategories) > $items_per_page) break; //SD343
      }
      if(($category['categoryid'] > 0) && !$isEnhanced) //SD343
      DisplayPages($category['categoryid'], $sublevelmarker, $skin_id, $skin_engine, $pagesquery);
    }

  } //while

  // Table end and submit
  if($parentid == 0)
  {
    echo '</tbody></table></div>';
    $GLOBALS['listedcategories'] = $listedcategories; //SD343

    PrintSubmit('update_pages',AdminPhrase('pages_update_pages'),'pagestable','fa-check');

    echo '</form>';

    if(!empty($total_rows) && $isEnhanced) //SD343
    {
      $p = new pagination;
      $p->items($total_rows);
      $p->limit($items_per_page);
      $p->currentPage($page);
      $p->adjacents(8);
      $p->target('pages.php?action=display_pages');
      $p->show();
      echo '<br />';
      //SD343: TODO: DisplayLostPages needs to be rewritten for this mode!
    }
    else
    if($userinfo['adminaccess'] && !$customsearch)
    {
      DisplayLostPages();
    }

    echo '
<div id="parentselector_div" style="display: none;">
<div class="table-header">' . AdminPhrase('pages_parent') . '</div>
<table class="table">
	<tbody>
		<tr>
			<td>
  				'.$parentselection.'
			</td>
		</tr>
		<tr>
			<td class="align-right">
  				<a class="parentpagechangelink btn btn-xs btn-success" href="pages.php"><i class="ace-icon fa fa-check"></i>'. AdminPhrase('pages_update_page').'</a>
  			</td>
		</tr>
	</tbody>
</table>
</div>
';
  }

} //DisplayPages


// ############################################################################
// DELETE SUBCATEGORIES - Recursive Function
// ############################################################################

function DeleteSubcategories($parentid)
{
  global $DB, $SDCache;

  //SD343: check if parent id is valid
  if(!ctype_digit($parentid) || !Is_Valid_Number($parentid,0,1,999999999))
  {
    return;
  }
  $subcategories = $DB->query('SELECT categoryid FROM {categories} WHERE parentid = %d',$parentid);

  while($subcategory = $DB->fetch_array($subcategories))
  {
    DeleteSubcategories($subcategory['categoryid']);

    // SD313: remove existing page cache file
    if(!empty($subcategory['categoryid']) && isset($SDCache))
    {
      $SDCache->delete_cacheid(CACHE_PAGE_PREFIX.(int)$subcategory['categoryid']);
      $SDCache->delete_cacheid(MOBILE_CACHE.CACHE_PAGE_PREFIX.(int)$subcategory['categoryid']); //SD370
    }
    $DB->query('DELETE FROM {categories} WHERE categoryid = %d', $subcategory['categoryid']);
    $DB->query('DELETE FROM {pagesort}   WHERE categoryid = %d', $subcategory['categoryid']);
    if(SD_MOBILE_FEATURES) //SD370
    $DB->query('DELETE FROM {pagesort_mobile} WHERE categoryid = %d', $subcategory['categoryid']);
  }

} //DeleteSubcategories


// ############################################################################
// ALLOW PARENT
// ############################################################################

function AllowParent($categoryid, $parentid)
{
  global $DB;

  // cylce through all this categoryid's children and see if the selected parentid for categoryid is equal
  // if so then return false, because category's can not be a parent of a child and also the child be a parent for that same category!
  $getcategories = $DB->query("SELECT * FROM " . TABLE_PREFIX . "categories WHERE parentid = $categoryid");

  while($category = $DB->fetch_array($getcategories))
  {
    if($category['categoryid'] == $parentid)
    {
      return 0;
    }
    else
    {
      // go deeper and check all children of this categoryid
      if(!AllowParent($category['categoryid'], $parentid))
      {
        return 0;
      }
    }
  }

  return 1;

} //AllowParent


// ############################################################################
// UPDATE PAGES
// ############################################################################

function UpdatePages()
{
  global $DB, $sdlanguage, $SDCache;

  if(!CheckFormToken())
  {
    RedirectPage('pages.php','<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $categoryids   = GetVar('categoryids',   array(), 'a_int', true, false);
  $displayorders = GetVar('displayorders', array(), 'a_int', true, false);
  $names         = GetVar('categorynames', array(), 'array', true, false);
  $customsearch  = GetVar('customsearch',  null, 'bool',  true, false);

  // SD313: perform some sanity checks on arrays:
  $categorycount = count($categoryids);
  $OK = is_array($categoryids) && is_array($displayorders) && is_array($names) &&
        (count($displayorders) === $categorycount) &&
        (count($names) === $categorycount);

  if(!$OK)
  {
    DisplayMessage(AdminPhrase('pages_error_invalid_data'), true);
    return false;
  }

  UpdateUserPluginsPageIDs(); //SD322
  UpdateUserPluginsPageIDs(true); //SD370

  // only update the display order and page names
  for($i = 0; $i < $categorycount; $i++)
  {
    //SD344: smarter check for valid numbers
    $categoryid = Is_Valid_Number(intval($categoryids[$i]),0,0);
    $order      = Is_Valid_Number(intval($displayorders[$i]),0,0);

    // SD313x: sanity checks
    if( !empty($categoryid) && ($categoryid > 0) &&
        (empty($order) || ($order > 0)) )
    {
      $names[$i] = $DB->escape_string(sd_unhtmlspecialchars($names[$i]));
      $DB->query("UPDATE {categories}
                 SET displayorder = %d, name = '%s'
                 WHERE categoryid = %d",
                 $order, $names[$i], $categoryid);
    }
    else
    {
      DisplayMessage(AdminPhrase('pages_error_invalid_data'), true);
      DisplayPages();
      return false;
    }
  }

  // SD343: remove existing all-pages cache file
  if(isset($SDCache) && ($SDCache instanceof SDCache))
  {
    $SDCache->delete_cacheid(CACHE_ALL_CATEGORIES);
  }

  RedirectPage('pages.php', AdminPhrase('pages_pages_updated'),1);
} //UpdatePages


// ############################################################################
// DISPLAY LOST PAGES
// ############################################################################

function DisplayLostPages()
{
  return;
  global $DB, $parentselection, $listedcategories;

  // lets see if any categories are lost
  $getallcategories = $DB->query('SELECT * FROM {categories} ORDER BY displayorder');

  if(count($listedcategories) != $DB->get_num_rows($getallcategories))
  {
    echo '<br />';
    StartSection(AdminPhrase('pages_lost_pages_found'));
    echo '<form action="pages.php" method="post">
    '.PrintSecureToken().'
    <input type="hidden" name="action" value="retrieve_lost_pages" />
    <table width="100%" cellpadding="5" cellspacing="0">
    <tr>
     <td class="td1"> </td>
    </tr>
    <tr>
      <td class="td2">' . AdminPhrase('pages_lost_pages_description') . ' ';

    while($category = $DB->fetch_array($getallcategories))
    {
      if(!in_array($category['name'], $listedcategories))
      {
        echo $category['name'] . ' <input type="hidden" name="lostcategoryids[]" value="' . $category['categoryid'] . '" />';
      }
    }

    echo '  </td>
    </tr>
    <tr>
      <td class="td4"><input type="submit" value="' . AdminPhrase('pages_retrieve_lost_pages') . '" /></td>
    </tr>
    </table>
    </form>';
    EndSection();
  }

} //DisplayLostPages

// ############################################################################
// RETRIEVE LOST PAGES
// ############################################################################

function RetrieveLostPages()
{
  return;
  global $DB;

  $lostcategoryids = GetVar('lostcategoryids', array(), 'array');

  for($i = 0; $i < count($lostcategoryids); $i++)
  {
    $DB->query('UPDATE {categories} SET parentid = 0 WHERE categoryid = %d', $lostcategoryids[$i]);
  }

  RedirectPage('pages.php?action=display_pages', AdminPhrase('pages_lost_pages_retrieved'));
} //RetrieveLostPages


// ############################################################################
// DISPLAY PAGE FORM
// ############################################################################
function DisplayPageForm($categoryid = 0, $oldSkinMode=false)
{
  global $DB, $core_pluginids_arr, $mainsettings;

  $core_pluginids = implode(',',$core_pluginids_arr);
  if($forum_id = GetPluginID('Forum'))
  {
    $core_pluginids .= ','.$forum_id;
  }

  $skin_engine = 1;
  $skinid = 0;
  $DB->result_type = MYSQL_ASSOC;
  if($skin_engine_arr = $DB->query_first('SELECT skin_engine, skinid FROM {skins} WHERE activated = 1'))
  {
    $skin_engine = $skin_engine_arr['skin_engine'];
    $skinid = $skin_engine_arr['skinid'];
  }

  $DB->result_type = MYSQL_ASSOC;
  if($categoryid && ($category = $DB->query_first('SELECT * FROM {categories} WHERE categoryid = %d', $categoryid)))
  {
    $new_page = false;
    $DB->result_type = MYSQL_ASSOC;
    // get the category's current design image and it's max plugins
    if(empty($category['designid']))
    {
      $currentdesign = $DB->query_first('SELECT designid, skinid, maxplugins, imagepath
                                         FROM {designs} WHERE skinid = %d
                                         ORDER BY designid ASC LIMIT 1', $skinid);
    }
    else
    {
      $currentdesign = $DB->query_first('SELECT designid, skinid, maxplugins, imagepath
                                         FROM {designs} WHERE designid = %d', $category['designid']);
    }

    // get the current selected plugins for this category
    $getselectedplugins = $DB->query('SELECT pluginid FROM {pagesort} WHERE categoryid = %d ORDER BY displayorder', $categoryid);
    while($selectedplugin = $DB->fetch_array($getselectedplugins,null,MYSQL_ASSOC))
    {
      $selectedpluginid[] = $selectedplugin['pluginid'];
    }

    $hidden_input_fields = '&nbsp;';
  }
  else
  {
    $new_page = true;

    // get the default design from the activated skin
    $DB->result_type = MYSQL_ASSOC;
    $currentdesign = $DB->query_first('SELECT d.designid, d.skinid, d.maxplugins, d.imagepath'.
                                      ' FROM {designs} d, {skins} s'.
                                      ' WHERE s.skinid = d.skinid AND s.activated = 1'.
                                      ' ORDER BY d.designid ASC LIMIT 1');

    $hidden_input_fields = '
    <input type="hidden" name="designselection" value="' . $currentdesign['designid'] . '" />' . "\n";

    for($i = 0; $i < $currentdesign['maxplugins']; $i++)
    {
      $hidden_input_fields .= '        <input type="hidden" name="plugins[]" value="1" />' . "\n";
    }

    $category = array('name' => '',
                      'parentid' => 0,
                      'urlname' => '',
                      'title' => '',
                      'metakeywords' => '',
                      'metadescription' => '',
                      'link' => '',
                      'image' => '',
                      'hoverimage' => '',
                      'target' => '',
                      'designid' => $currentdesign['designid'],
                      'sslurl' => 0,
                      'appendkeywords' => 0,  //SD343
                      'navigation_flag' => 0, //SD343
                      'html_id' => '',        //SD370
                      'html_class' => '',     //SD370
                      'menuwidth' => 0        //SD370
                      );
  }

  // get all the designs for the current activated skin
  $getdesigns = $DB->query('SELECT d.designid, d.maxplugins, d.imagepath'.
                           ' FROM {designs} d, {skins} s'.
                           ' WHERE s.skinid = d.skinid AND s.activated = 1'.
                           ' ORDER BY designid ASC');
  $designrows = $DB->get_num_rows($getdesigns);

  if(($skin_engine == 1) || !empty($oldSkinMode)) //SD343: $oldSkinMode
  {
    // get the cloned plugins for selection, core plugins are hardcoded into this script
    $getclonedplugins = $DB->query('SELECT pluginid, name FROM {plugins}'.
                                   " WHERE authorname = 'subdreamer_cloner'".
                                   ' ORDER BY name');
    $clonedpluginrows = $DB->get_num_rows($getclonedplugins);

    while($clonedplugin = $DB->fetch_array($getclonedplugins,null,MYSQL_ASSOC))
    {
      $clonedpluginid[]   = $clonedplugin['pluginid'];
      $clonedpluginname[] = $clonedplugin['name'];
    }

    // get the downloaded plugins for selection
    $getdownloadedplugins = $DB->query('SELECT pluginid, name FROM {plugins}'.
                                       ' WHERE NOT (pluginid IN ('.$core_pluginids.'))'.
                                       " AND authorname != 'subdreamer_cloner'".
                                       ' ORDER BY name');
    $downloadedpluginrows = $DB->get_num_rows($getdownloadedplugins);

    while($downloadedplugin = $DB->fetch_array($getdownloadedplugins,null,MYSQL_ASSOC))
    {
      $downloadedpluginid[]   = $downloadedplugin['pluginid'];
      $downloadedpluginname[] = $downloadedplugin['name'];
    }

    // get all custom plugins to be displayed in the selection boxes
    $getcustomplugins = $DB->query('SELECT custompluginid, name FROM {customplugins} ORDER BY name');
    $custompluginrows = $DB->get_num_rows($getcustomplugins);

    while($customplugin = $DB->fetch_array($getcustomplugins,null,MYSQL_ASSOC))
    {
      $custompluginid[]   = $customplugin['custompluginid'];
      $custompluginname[] = $customplugin['name'];
    }

    // create the plugin selection boxes
    for($i = 0; $design = $DB->fetch_array($getdesigns,null,MYSQL_ASSOC); $i++)
    {
      // start of the table
      $pluginselection[$i] = '<table border="0" cellspacing="5" cellpadding="0">';

      // display 'i' amount of selection plugins for the category
      for($pluginselectioncount = 0; $pluginselectioncount < $design['maxplugins']; $pluginselectioncount++)
      {
        // this code makes sure that the unselected design's plugins are all set to empty,
        // although this code can possible mess up a selected design and set it's plugins to empty by
        // previously setting an unselected design's $selectedpluginid to 1, therefore we need to keep a
        // copy of the selectedpluginid and revert it back to normal at the end of this for loop
        if($currentdesign['designid'] != $design['designid'])
        {
          $oldselectedpluginid = empty($selectedpluginid[$pluginselectioncount])?1:$selectedpluginid[$pluginselectioncount];
          $selectedpluginid[$pluginselectioncount] = 1;
        }

        if(!isset($selectedpluginid[$pluginselectioncount]))
        {
          $selectedpluginid[$pluginselectioncount] = 1;
        }

        $pluginselection[$i] .= '<tr>';
        $pluginselection[$i] .= '<td width="120">' . AdminPhrase('pages_select_plugin') . ' ' . ($pluginselectioncount + 1) . ':</td>';
        $pluginselection[$i] .= '<td width="200">';

        $pluginselection[$i] .= '<select id="selection'.$pluginselectioncount.'" name="plugins[]" style="width: 300px;">'.
                                GetPluginsSelect($selectedpluginid[$pluginselectioncount]);
        $pluginselection[$i] .= '</td><td width="20">';

        if($selectedpluginid[$pluginselectioncount] == 1)
        {
          $pluginselection[$i] .= '&nbsp;';
        }
        else if($selectedpluginid[$pluginselectioncount] == 2)
        {
          $pluginselection[$i] .= '<a href="articles.php"><span class="sprite sprite-shortcute"></span></a>';
        }
        else if(substr($selectedpluginid[$pluginselectioncount],0,1) == 'c')
        {
          $pluginselection[$i] .= '<a href="plugins.php?action=display_custom_plugin_form&amp;custompluginid=' .
            substr($selectedpluginid[$pluginselectioncount], 1).
            '&amp;load_wysiwyg=true"><span class="sprite sprite-shortcut"></span></a>';
        }
        else
        {
          $pluginselection[$i] .= '<a href="view_plugin.php?pluginid='.
            $selectedpluginid[$pluginselectioncount].
            '"><span class="sprite sprite-shortcut"></span></a>';
        }

        $pluginselection[$i] .= '</td><td style="padding-top:4px;"><input name="pluginall['.$pluginselectioncount.
          ']" type="checkbox" value="1" /> ' . AdminPhrase('pages_set_for_all').
          '</td></tr>';

        if($currentdesign['designid'] != $design['designid'])
        {
          $selectedpluginid[$pluginselectioncount] = $oldselectedpluginid;
        }

      } // end the for loop

      // end the table
      $pluginselection[$i] .= '</table>';

    } // end the while loop
  } // if skin engine == 1
  
  // the following form code must remain here, moving under under a div element (ex: under StartSection(page settings)
  // will mess up the form and $_POST['plugins'] will not submit correctly
  echo '
  <form role="form" class="form-horizontal" enctype="multipart/form-data" action="pages.php" id="categoryform" name="categoryform" method="post">
  '.PrintSecureToken().'
  <input type="hidden" name="categoryid" value="' . $categoryid . '" />';

	if(empty($categoryid))
  {
	  
	  echo'<div class="form-group bg-info middle">
			 <label class="col-sm-5 control-label" for="copyfromid">' . AdminPhrase('pages_copy_page_descr') . '</label>
			 <div class="col-sm-7 align-middle">
			 <div class="space-16"></div>';
			 
			  DisplayCategorySelection(0, 1, 0, '', 'copyfromid', '');
        echo'
			 </div>
		  </div>
		  <div class="space-20"></div>';
	
  }
  else
  {
    echo'<div class="form-group bg-info">
			 <label class="col-sm-5 control-label" for="create_copy">'.AdminPhrase('pages_copy_current_page_descr').' </label>
			 <div class="col-sm-5 center ">
			 <div class="space-4"></div>
				<input type="checkbox" class="ace" name="create_copy" value="1" />
				<span class="lbl"></span>
        	 </div>
		  </div>
		  <div class="space-20"></div>';
	
  }
  
  echo'
  		<div class="form-group">
			 <label class="col-sm-2 control-label" for="categoryname">' . AdminPhrase('pages_name') . '</label>
			 <div class="col-sm-6">
			 	<input type="text" class="form-control" name="categoryname" id="categoryname" value="' .htmlspecialchars($category['name'],ENT_COMPAT,SD_CHARSET) . '" />
			 </div>
		  </div>
	';

  echo'
  		<div class="form-group">
			<label class="col-sm-2 control-label" for="parentid">' . AdminPhrase('pages_parent_page') . '</label>
			<div class="col-sm-6">';
        // first argument:  categoryid that should be selected
        // second argument: display an empty first row of categoryid with value zero (1 = yes, 0 = no)
        DisplayCategorySelection($category['parentid'], 1, 0, '', 'parentid', '');
echo '</div></div>
  ';
  
  echo'
  		<div class="form-group">
			<label class="col-sm-2 control-label" for="categorytitle">' . AdminPhrase('pages_title') . '</label>
			<div class="col-sm-6">
			<input type="text" class="form-control" name="categorytitle"  value="' . $category['title'] . '" />
			</div>
		</div>
	';

  echo'
  		<div class="form-group">
			<label class="col-sm-2 control-label" for="categoryurlname">' . AdminPhrase('pages_seo_name') . '</label>
			<div class="col-sm-6">
    		<input type="text" class="form-control" name="categoryurlname"  value="' . htmlspecialchars($category['urlname'],ENT_COMPAT,SD_CHARSET) . '" />
			</div>
		</div>
	';
	
	echo'
  		<div class="form-group">
			<label class="col-sm-2 control-label" for="categorylink">' . AdminPhrase('pages_external_link') . '</label>
			<div class="col-sm-6">
			<input type="text" class="form-control" name="categorylink"  value="' . htmlentities($category['link'], ENT_QUOTES) . '" size="30" />
			</div>
		</div>
	';

  echo'
  		<div class="form-group">
			<label class="col-sm-2 control-label" for="categorytarget">' . AdminPhrase('pages_external_link_target') . '</label>
			<div class="col-sm-6">
      <select name="categorytarget" class="form-control">
        <option value=""' .(empty($category['target']) ? ' selected="selected"':'').'>-</option>
        <option value="_blank"  ' .($category['target'] == '_blank'  ? 'selected="selected"' : '') . '>_blank</option>
        <option value="_self"   ' .($category['target'] == '_self'   ? 'selected="selected"' : '') . '>_self</option>
        <option value="_parent" ' .($category['target'] == '_parent' ? 'selected="selected"' : '') . '>_parent</option>
        <option value="_top"    ' .($category['target'] == '_top'    ? 'selected="selected"' : '') . '>_top</option>
      </select>
	  </div>
    </div>
';

   //SD362: HTML class and id
  echo '
      <div class="form-group">
      <label class="col-sm-2 control-label" for="html_class">'.AdminPhrase('page_html_class').'</label>
	  <div class="col-sm-6">
        <input class="form-control" type="text" name="html_class"  value="'.$category['html_class'].'" />
      <span class="helper-text">'.AdminPhrase('page_html_class_descr').'</span>
	  </div>
    </div>
';

echo'
      <div class="form-group">
      <label class="col-sm-2 control-label" for="html_id">' . AdminPhrase('page_html_id') . '</label>
	  <div class="col-sm-6">
        <input type="text" class="form-control" name="html_id" value="'.$category['html_id'].'" />
      <span class="helper-text">'.AdminPhrase('page_html_id_descr').'</span>
	  </div>
    </div>

';

  //SD343: option for navigation display
  $navigation_flag = empty($category['navigation_flag']) ? 0 : Is_Valid_Number($category['navigation_flag'],0,0,7);

  echo '
  		<div class="form-group">
			<label class="col-sm-2 control-label" for="navigation_flag">
				' . AdminPhrase('page_navigation_flag') . '
			</label>
			<div class="col-sm-6">
      <select class="form-control" id="navigation_flag" name="navigation_flag[]" multiple="multiple" >
        <option value="0" ' . (!$navigation_flag    ? 'selected="selected"':'') . '>' . AdminPhrase('page_navigation_flag_opt_all') . '</option>
        <option value="1" ' . ($navigation_flag & 1 ? 'selected="selected"':'') . '>' . AdminPhrase('page_navigation_flag_opt_top') . '</option>
        <option value="2" ' . ($navigation_flag & 2 ? 'selected="selected"':'') . '>' . AdminPhrase('page_navigation_flag_opt_bottom') . '</option>
        <option value="4" ' . ($navigation_flag & 4 ? 'selected="selected"':'') . '>' . AdminPhrase('page_navigation_flag_opt_none') . '</option>
      </select>
	  </div>
	     </div>
';

 echo'
      <div class="form-group">
        <label class="col-sm-2 control-label" for="metadescription">' . AdminPhrase('pages_meta_description') . '</label>
		<div class="col-sm-6">
        <textarea class="form-control" name="metadescription" >' . $category['metadescription'] . '</textarea>
		</div>
      </div>
';

echo'
      <div class="form-group">
        <label class="col-sm-2 control-label" for="metakeywords">' . AdminPhrase('pages_meta_keywords') . '</label>
		<div class="col-sm-6">
        <textarea class="form-control" name="metakeywords" >' . $category['metakeywords'] . '</textarea>
		</div>
      </div>';
echo '
  	<div class="form-group">
		  <label class="col-sm-2 control-label" for="sslurl">' . AdminPhrase('pages_ssl_url') . '
		  ' . (empty($mainsettings['sslurl']) ? '<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="'.AdminPhrase('pages_ssl_disabled').'" title="'.AdminPhrase('common_help').'">?</span>' : '') .'</label>
		  <div class="col-sm-6">
  	   
		      <label>
			       <input '. (empty($mainsettings['sslurl']) ? 'disabled' : '') .' type="radio" name="sslurl" '. (!empty($category['sslurl']) ? 'checked="checked"' : '').' value="1" class="ace" />
			       <span class="lbl"> ' . AdminPhrase('common_yes') . '</span>
		      </label>
	     		&nbsp;
		      <label>  
			       <input '. (empty($mainsettings['sslurl']) ? 'disabled' : '') .' type="radio" name="sslurl" '.  (empty($category['sslurl'])  ? 'checked="checked"' : '').' value="0" class="ace" />
			       <span class="lbl"> ' . AdminPhrase('common_no') .'</span>
		      </label>
	     
	   </div>
	   </div>
';

  


  //SD343: re-introducing "append/overwrite meta data" option
  echo '
  		<div class="form-group">
			 <label class="col-sm-2 control-label" for="appendkeywords">' . AdminPhrase('pages_append_keywords') . '
			 <span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="'.AdminPhrase('pages_appenkeywords_descr').'" title="'.AdminPhrase('common_help').'">?</span>
			 </label>
			 <div class="col-sm-6">
        		<select class="form-control" name="appendkeywords">
          			<option value="0" ' . ( empty($category['appendkeywords'])?'selected="selected"':'') . '>Overwrite</option>
          			<option value="1" ' . (!empty($category['appendkeywords'])?'selected="selected"':'') . '>Append</option>
        		</select>
			</div>
      </div>
';

  if($skin_engine==1) #SD362: allow to enter "Menu width" for SD 2.x skins
  {
    echo '
  		<div class="form-group">
			<label class="col-sm-2 control-label" for="menuwidth">' . AdminPhrase('page_menuwidth') . '</label>
			<div class="col-sm-6">
    		<input class="form-control" type="text" name="menuwidth" size="4" maxlength="3" value="'. (empty($category['menuwidth'])?0:(int)$category['menuwidth']).'" />
			<span class="helper-text"> '.AdminPhrase('page_menuwidth_descr').'</span>
			</div>
		</div>
';
  }
  else
  {
    echo '<input type="hidden" name="menuwidth" value="0" />';
  }

  $designrows = 0;
  //SD370: commented out: the page itself can still be opened by direct URL,
  // so the plugin positions/design still need to be editable!
  /*
  if(strlen($category['link']))
  {
    StartSection(AdminPhrase('pages_page_content'));
    echo '<center><strong>'.AdminPhrase('pages_external_no_plugins') . '</strong></center>';
    EndSection();
    if($skin_engine == 2)
    {
      echo '
      <div id="hidden_input_fields" style="display:none;margin:0;padding:0;height:0;">' . $hidden_input_fields . '</div>';
    }
  }
  else
  */
  if(($skin_engine == 2) && empty($oldSkinMode))
  {
    if(!empty($categoryid))
    {
      echo '
          <h3 class="header blue">' . AdminPhrase('pages_page_content') . '</h3>
		  <div class="space=20"></div>';

      echo'
	  <div class="form-gorup">
        <div class="col-sm-6 center ">
              <a class="btn btn-success btn-app radius-4" id="editpagebutton" href="page_plugin_selection.php'.
                ($categoryid ? '?categoryid=' . $categoryid : '') . '"><i class="ace-icon fa fa-desktop align-top bigger-230"></i> ' .
                AdminPhrase('pages_standard') . 
              '</a>
            </div>
            <div class="col-sm-6 center">
              <a class="btn btn-success btn-app radius-4" id="editpagebutton" href="page_plugin_selection.php'.
              ($categoryid ? '?categoryid='.$categoryid.'&amp;' : '?') . 'mobile=1"><i class="ace-icon fa fa-tablet bigger-230"></i> ' .
              AdminPhrase('pages_edit_mobile') . '</a>
            </div>
		</div>
     ';
    }
    
    echo '<div id="hidden_input_fields" style="display:none;">' . $hidden_input_fields . '</div>';
  }

  else
  {
     echo'
      <div class="form-group">
        <label class="col-sm-2 control-label" for="designselection">' . AdminPhrase('pages_page_layout') . '</label>
		<div class="col-sm-6">';
		
		 // get all the designs for this skin
            $getdesigns = $DB->query('SELECT designid, maxplugins, imagepath FROM {designs} WHERE skinid = %d ORDER BY designid', $currentdesign['skinid']);
            $designrows = $DB->get_num_rows($getdesigns);

    echo '<select name="designselection" class="form-control" id="designselection">';

    for($designcount = 1; $design = $DB->fetch_array($getdesigns,null,MYSQL_ASSOC); $designcount++)
    {
      echo '<option value="' . $design['designid'] . '"' . ($currentdesign['designid']==$design['designid']?' selected="selected"':'') . '>' . AdminPhrase('pages_layout') . ' ' . $designcount . '</option>';
    }

    echo '
        </select>          
          <span class="helper-text">' . AdminPhrase('pages_plugin_selection_description') . '</span>
		 </div>
			<div class="col-sm-12 center">
            <img name="designimage" src="./../skins/' . $currentdesign['imagepath'] . '" border="0" alt="" /></div>';

            // get all the designs for this skin
            $getdesigns = $DB->query('SELECT designid, maxplugins, imagepath FROM {designs} WHERE skinid = %d ORDER BY designid', $currentdesign['skinid']);
            $designrows = $DB->get_num_rows($getdesigns);

  

    echo '
        
        <div id="pluginselection">' . AdminPhrase('pages_please_wait') . '...</div>';
	
  } // if skin engine == 1

  DisplayPagePermissions(true);
  
  if($new_page)
    PrintSubmit('insert_page', AdminPhrase('pages_create_page'), 'categoryform','fa-check');
  else
    PrintSubmit('update_page', AdminPhrase('pages_update_page'), 'categoryform','fa-check');

  echo '
  </form>';

  if(!strlen($category['link']) && !empty($designrows) && ($skin_engine == 1) && @mysql_data_seek($getdesigns, 0))
  {
    echo '
    <script language="javascript" type="text/javascript">
    var layouts_arr = new Array();';

    while($design_arr = $DB->fetch_array($getdesigns))
    {
      echo 'layouts_arr[' . $design_arr['designid'] . '] = "' . $design_arr['imagepath'] . '";';
    }

    echo 'var pluginselectiontext = new Array();';

    for($i = 0, $pc = count($pluginselection); $i < $pc; $i++)
    {
      echo 'pluginselectiontext[' . $i . '] = \'' . addslashes($pluginselection[$i]) . "';\r\n";
    }

    echo '
    function change_design(designid, layouts_arr){
      document.designimage.src = "./../skins/" + layouts_arr[designid];
    }

    function DisplayPluginSelection(which){
      $("#pluginselection").empty();
      $("#pluginselection").html (pluginselectiontext[which]);
      $("#pluginselection").show("fast");
    }

    $(document).ready(function(){
      DisplayPluginSelection(document.categoryform.designselection.selectedIndex);

      $("#designselection").change(function(){
        change_design(document.categoryform.designselection[this.selectedIndex].value, layouts_arr);
        DisplayPluginSelection(this.selectedIndex);
      });
    });
    </script>
    ';
  } // if skin engine == 1



  

} //DisplayPageForm


// ############################################################################
// UPDATE PARENT PAGE (AJAX)
// ############################################################################

function UpdateParentPage() //SD343
{
  global $DB, $mainsettings, $sdlanguage, $SDCache;

  $categoryid = GetVar('categoryid', 0, 'whole_number',false,true);
  $parentid   = GetVar('parentid',  -1, 'natural_number',false,true);

  $error = false;
  if(!CheckFormToken() || !$categoryid || ($categoryid==$parentid) || ($parentid < 0))
  {
    $error = AdminPhrase('pages_error_invalid_parent');
  }
  else
  if(!(AllowParent($categoryid, $parentid)))
  {
    $errors = AdminPhrase('pages_error_invalid_parent');
  }

  if(!$error)
  {
    global $DB;
    $DB->result_type = MYSQL_ASSOC;
    $cat = $DB->query_first('SELECT categoryid FROM {categories} WHERE categoryid = %d',$categoryid);
    $DB->result_type = MYSQL_ASSOC;
    if($parentid > 0)
    $parent = $DB->query_first('SELECT categoryid FROM {categories} WHERE categoryid = %d',$parentid);
    if(empty($cat['categoryid']) || (!empty($parentid) && empty($parent['categoryid'])))
    {
      $error = AdminPhrase('pages_error_invalid_parent');
    }
    $DB->query('UPDATE {categories} SET parentid = %d WHERE categoryid = %d',
               $parentid, $categoryid);
  }

  if($error!==false)
  {
  	return '<script>
			jDialog.close();
			var n = noty({
					text: \''.$error.'\',
					layout: \'top\',
					type: \'error\',	
					timeout: 5000,					
					});
			</script>';
  }
  

  // remove existing page cache files
  if(isset($SDCache) && $SDCache->IsActive())
  {
    //SD370: added mobile
    $SDCache->delete_cacheid(CACHE_PAGE_PREFIX.(int)$categoryid);
    $SDCache->delete_cacheid(MOBILE_CACHE.CACHE_PAGE_PREFIX.(int)$categoryid);
    $cacheid = (int)floor($categoryid/10);
    $SDCache->delete_cacheid(CACHE_PAGE_DESIGN.$cacheid);
    $SDCache->delete_cacheid(CACHE_PAGE_DESIGN.MOBILE_CACHE.$cacheid);
    $SDCache->delete_cacheid(CACHE_ALL_MAINSETTINGS);
    $SDCache->delete_cacheid(CACHE_ALL_CATEGORIES);
  }
  
  return '<script>
			jDialog.close();
			var n = noty({
					text: \''.AdminPhrase('pages_page_saved').'\',
					layout: \'top\',
					type: \'success\',	
					timeout: 5000,					
					});
			</script>';
  //return '<span class="btn_ok"></strong>'.AdminPhrase('pages_page_saved').'</strong></span>';

} //UpdateParentPage


// ############################################################################
// SAVE PAGE
// ############################################################################
// this function handles page inserts & updates

function SavePage()
{
  global $DB, $mainsettings, $sdlanguage, $SDCache;

  if(!CheckFormToken())
  {
    RedirectPage('pages.php','<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $errors = array();

  $action          = GetVar('action', '', 'string');
  $categoryid      = GetVar('categoryid', 0, 'natural_number');
  $parentid        = GetVar('parentid', 0, 'natural_number');
  $categoryname    = trim(GetVar('categoryname', '', 'html'));
  $categoryurlname = trim(strip_alltags(GetVar('categoryurlname', '', 'html')));
  $categorytitle   = trim(GetVar('categorytitle', '', 'html'));
  $categorylink    = trim(strip_alltags(GetVar('categorylink', '', 'html')));
  $categorytarget  = GetVar('categorytarget', '', 'string');
  if(!in_array($categorytarget,array('_blank','_self','_parent','_top')))
  {
    $categorytarget  = '';
  }
  $plugins         = GetVar('plugins', array(), 'array');
  $metadescription = GetVar('metadescription', '', 'string');
  $metakeywords    = GetVar('metakeywords', '', 'string');
  $appendkeywords  = GetVar('appendkeywords', false, 'boolean')?1:0; //SD343
  $designid        = GetVar('designselection', 0, 'whole_number');
  $sslurl          = GetVar('sslurl', 0, 'whole_number'); //SD322
  if($navigation_flag = GetVar('navigation_flag', 0, 'array')) //SD343
  {
    $navigation_flag = array_sum($navigation_flag);
    $navigation_flag = Is_Valid_Number($navigation_flag, 0, 0, 7);
  }
  $copy_id         = GetVar('copyfromid', 0, 'integer');
  $create_copy     = GetVar('create_copy', 0, 'bool');
  $html_class      = GetVar('html_class', '', 'string'); //SD362
  $html_id         = GetVar('html_id', '', 'string');    //SD362
  $menuwidth       = Is_Valid_Number(GetVar('menuwidth', 0, 'natural_number'),0,0,999); //SD362

  // Error checks:
  if(!isset($categoryid) || ($categoryid < 0) || ($categoryid > 99999999) ||
     ($copy_id < 0) || ($copy_id > 99999999))
  {
    PrintErrors('Invalid data submitted!', 'Page Errors');
    return false;
  }
  // Check skin
  $skin_error = false;
  $DB->result_type = MYSQL_ASSOC;
  if(!$skin = $DB->query_first('SELECT skinid, skin_engine FROM {skins} WHERE activated = 1 LIMIT 1'))
  {
    $skin_error = true;
  }
  $DB->result_type = MYSQL_ASSOC;
  if(!$skin_error &&
     (!$design = $DB->query_first('SELECT designid, maxplugins FROM {designs}
                 WHERE skinid = %d ORDER BY designid LIMIT 1',$skin['skinid'])))
  {
    $skin_error = true;
  }
  if($skin_error)
  {
    PrintErrors('ERROR: Skin not installed correctly! Cannot save Page!', 'Page Errors');
    return false;
  }
  $design['maxplugins'] = (int)$design['maxplugins'];

  // auto assign category name if empty
  $categoryname = strlen($categoryname) ? $categoryname : 'Untitled';

  // update the plugins unless a new design has been selected
  $updateplugins = true;

  // used when copying a category's image and hover images
  $dirname = substr(dirname(__FILE__), 0, -5);

  // Convert seo title for URL
  if(!strlen(trim($categoryurlname)))
  {
    if(strlen(trim($categorytitle)))
    {
      $categoryurlname = ConvertNewsTitleToUrl($categorytitle, 0, 0, true);
    }
    else
    if(strlen(trim($categoryname))) //SD342 if title not set, use name instead
    {
      $categoryurlname = ConvertNewsTitleToUrl($categoryname, 0, 0, true);
    }
  }
  else
  {
    // fix up at least some special characters
    $sep = $mainsettings['settings_seo_default_separator'];
    $search  = array('"', "'",  '&#039;', '&#39;', '\\', '%', '0x', '?', ' ',  '(',  ')',  '[',  ']');
    $replace = array('',  $sep, $sep,     $sep,    '',   '',  '',   '',  $sep, $sep, $sep, $sep, $sep);
    $categoryurlname   = str_replace($search, $replace, $categoryurlname);
  }

  // insert/update category information
  if($action == 'insert_page')
  {
    //SD322: Copy settings from another page (if it exists)?
    $DB->result_type = MYSQL_ASSOC;
    if(!empty($copy_id) && ($source = $DB->query_first('SELECT designid, metadescription, metakeywords,
        appendkeywords, designid, sslurl, name, link, parentid, html_class, html_id, menuwidth
        FROM {categories} WHERE categoryid = %d', $copy_id)))
    {
      $designid        = (int)$source['designid'];
      $metadescription = (strlen($metadescription)?$metadescription:$DB->escape_string($source['metadescription']));
      $metakeywords    = (strlen($metakeywords)?$metakeywords:$DB->escape_string($source['metakeywords']));
      $appendkeywords  = (int)$source['appendkeywords'];
      $designid        = (int)$source['designid'];
      $sslurl          = $DB->escape_string($source['sslurl']);
      $categoryname    = (strlen($categoryname)?$categoryname:$source['name'].' *');
      $categorytitle   = (strlen($categorytitle)?$categorytitle:'');
      $categorylink    = (strlen($categorylink)?$categorylink:$source['link']);
      $parentid        = (!empty($parentid)?$parentid:(int)$source['parentid']);
      $html_class      = (strlen($html_class)?$html_class:$DB->escape_string($source['html_class']));
      $html_id         = (strlen($html_id)?$html_id:$DB->escape_string($source['html_id']));
      $menuwidth       = (!empty($menuwidth)?$menuwidth:(int)$source['menuwidth']);
      unset($source);
    }
    else
    {
      // Something's wrong, reset it:
      unset($copy_id);
    }

    $DB->query("INSERT INTO {categories}
      (name, urlname, link, target, title, metadescription, metakeywords, appendkeywords, designid, sslurl, parentid, navigation_flag, html_class, html_id, menuwidth) VALUES
      ('%s', '%s', '%s', '$categorytarget', '%s', '$metadescription', '$metakeywords', $appendkeywords, $designid, $sslurl, $parentid, $navigation_flag, '%s', '%s', '$menuwidth')",
       $DB->escape_string($categoryname), $DB->escape_string($categoryurlname),
       $DB->escape_string($categorylink), $DB->escape_string($categorytitle),
       $html_class, $html_id);

    // ############################
    $categoryid = $DB->insert_id();
    // ############################

    //SD322: Copy over plugin positions etc.
    if(!empty($copy_id))
    {
      UpdatePagePermissions($categoryid);

      //SD370: also create mobile pagesort entries
      foreach(array('','_mobile') as $suffix)
      {
        if($suffix && !SD_MOBILE_FEATURES) break;
        $tbl = PRGM_TABLE_PREFIX.'pagesort'.$suffix;
        $plugins = array(); // Empty extra variable to avoid further processing
        $create_blank = true;
        if($plugins_arr = $DB->query('SELECT pluginid FROM '.$tbl.
                                     ' WHERE categoryid = %d ORDER BY categoryid, displayorder', $copy_id))
        {
          // Fetch all plugins from pagesort and build INSERT SQL statement:
          $insert_sql = 'INSERT INTO '.$tbl.' (categoryid, pluginid, displayorder) VALUES ';
          $i = 0;
          while($source = $DB->fetch_array($plugins_arr,null,MYSQL_ASSOC))
          {
            $i++;
            $insert_sql .= "(".(int)$categoryid.", '".$DB->escape_string($source['pluginid'])."', $i),";
          }
          if($i > 0) //SD342 check if empty
          {
            $insert_sql = substr($insert_sql,0,-1); // remove last comma
            $DB->query($insert_sql);
          }
          $create_blank = false;
        }

        if($create_blank)
        {
          // Enter "--empty--" plugin into pagesort for new category.
          // This is a must-have, NEVER delete this!
          $DB->query('DELETE FROM '.$tbl.' WHERE categoryid = %d', $categoryid);
          for($i = 1; $i <= $design['maxplugins']; $i++)
          {
            $DB->query("INSERT INTO $tbl (categoryid, pluginid, displayorder)
                        VALUES ($categoryid, '1', $i)");
          }
        }
      }
    }
  }
  else
  {
    $design_cond = '';
    if($skin['skin_engine'] == 1)
    {
      $design_cond = ', designid = '.(int)$designid;
    }
    $DB->query("UPDATE {categories}
                SET name             = '%s',
                    urlname          = '%s',
                    link             = '%s',
                    target           = '$categorytarget',
                    title            = '%s',
                    metadescription  = '$metadescription',
                    metakeywords     = '$metakeywords',
                    appendkeywords   = $appendkeywords,
                    sslurl           = %d,
                    navigation_flag  = %d $design_cond,
                    html_class       = '%s',
                    html_id          = '%s',
                    menuwidth        = %d
                    WHERE categoryid = %d",
                    $DB->escape_string($categoryname),
                    $DB->escape_string($categoryurlname),
                    $DB->escape_string($categorylink),
                    $DB->escape_string($categorytitle),
                    $sslurl, $navigation_flag,
                    $html_class, $html_id, $menuwidth,
                    $categoryid);

    //SD322 create copy of current page
    if(!empty($create_copy) && ($source = $DB->query_first('SELECT designid, designid, sslurl, name, parentid
        FROM {categories} WHERE categoryid = %d', $categoryid)))
    {
      // Note: copy leaves empty the SEO url (urlname) and appends * to the title!
      $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX."categories
      (name, link, target, title, metadescription, metakeywords, appendkeywords, designid, sslurl, parentid,
       navigation_flag, html_class, html_id) VALUES
      ('".$DB->escape_string($categoryname)." *', '', '$categorytarget',
      '".$DB->escape_string($categorytitle)."', '$metadescription', '$metakeywords', $appendkeywords, ".
      $source['designid'].', '.$source['sslurl'].', '.$source['parentid'].', '.$navigation_flag.
      ",'$html_class', '$html_id')");

      if($categoryid_copy = $DB->insert_id())
      {
        //SD370: also create mobile pagesort entries
        foreach(array('','_mobile') as $suffix)
        {
          if($suffix && !SD_MOBILE_FEATURES) break;
          $tbl = PRGM_TABLE_PREFIX.'pagesort'.$suffix;
          $DB->query("INSERT INTO $tbl (categoryid, pluginid, displayorder)
                      SELECT %d, pluginid, displayorder
                      FROM {pagesort}
                      WHERE categoryid = %d",
                      $categoryid_copy, $categoryid);
        }
        UpdatePagePermissions($categoryid_copy);
      }
    }

  }
  // remove existing page cache file
  if(isset($SDCache) && $SDCache->IsActive())
  {
    if(!empty($categoryid))
    {
      //SD370: added mobile
      $SDCache->delete_cacheid(CACHE_PAGE_PREFIX.(int)$categoryid);
      $SDCache->delete_cacheid(MOBILE_CACHE.CACHE_PAGE_PREFIX.(int)$categoryid);
      $cacheid = (int)floor($categoryid/10);
      $SDCache->delete_cacheid(CACHE_PAGE_DESIGN.$cacheid);
      $SDCache->delete_cacheid(CACHE_PAGE_DESIGN.MOBILE_CACHE.$cacheid);
    }
    $SDCache->delete_cacheid(CACHE_ALL_MAINSETTINGS); //because of p10-p12!
    $SDCache->delete_cacheid(CACHE_ALL_CATEGORIES);
  }

  if($categoryid)
  UpdatePagePermissions($categoryid);

  if(($action == 'insert_page') && !empty($copy_id))
  {
    RedirectPage('pages.php?action=edit_page&amp;categoryid='.$categoryid.SD_URL_TOKEN,
                 AdminPhrase('pages_page_saved'));
    return true;
  }

  // update design
  if($designid)
  {
    $DB->query('UPDATE {categories} SET designid = %d WHERE categoryid = %d',$designid,$categoryid);
  }

  // Check if the friendly name already exists:
  //SD343: check only for same parent page for duplicate
  if(strlen($categoryurlname) &&
     ($exists = $DB->query_first('SELECT 1 FROM {categories}'.
                                 " WHERE (urlname <> '') AND (urlname = '%s') AND (categoryid <> %d) AND (parentid = %d)",
                                  $DB->escape_string($categoryurlname), $categoryid, $parentid)))
  {
    $errors[] = AdminPhrase('pages_error_invalid_page_name');
  }
  else
  {
    $DB->query("UPDATE {categories} SET urlname = '%s' WHERE categoryid = %d",
               $DB->escape_string($categoryurlname), $categoryid);
  }

  // update parentid
  if($categoryid != $parentid)
  {
    // check and see if the parent selection is a child of this category (that will really mess things up!)
    if(AllowParent($categoryid, $parentid))
    {
      $DB->query('UPDATE {categories} SET parentid = %d WHERE categoryid = %d', $parentid, $categoryid);
    }
    else
    {
      $errors[] = AdminPhrase('pages_error_invalid_parent');
    }
  }
  else
  {
    $errors[] = AdminPhrase('pages_error_invalid_parent');
  }

  // when updating a page that is using skin_engine = 2, then this code is not used
  if(!empty($plugins) && count($plugins))
  {
    // update plugins
    // check to see if a plugin was selected twice
    $checkKeysUniqueComparison = create_function('$value','if ($value > 1) return true;');
    $result = array_keys(array_filter(array_count_values($plugins), $checkKeysUniqueComparison));

    for($i = 0, $rc = count($result); $i < $rc; $i++)
    {
      if($result[$i] != 1)
      {
        $pluginerror = 1;
        break;
      }
    }

    if(isset($pluginerror))
    {
      $errors[] = AdminPhrase('pages_error_common_plugins');
    }
    else
    {
      // update plugins

      // first delete pagesort plugins for this category
      $DB->query('DELETE FROM {pagesort} WHERE categoryid = %d', $categoryid);

      // now insert the new plugins into pagesort
      for($i = 0, $pc = count($plugins); $i < $pc; $i++)
      {
        // convert Menus (ex: + Custom Plugins) to the empty plugin
        if(empty($plugins[$i]))
        {
          $plugins[$i] = 1;
        }
        $DB->query("INSERT INTO {pagesort} (categoryid, pluginid, displayorder)
                    VALUES (%d, '%s', %d)",
                    $categoryid, $DB->escape_string($plugins[$i]), ($i+1));

        // find and update the page IDs for user profile, user registration and user login panel
        if(($plugins[$i]=='10') || ($plugins[$i]=='11') || ($plugins[$i]=='12')) //SD342
        UpdateUserPluginsPageIDs();
      }

      // Check the "Set for all?" option of each plugin
      $pall = GetVar('pluginall',null,'array',true,false); //SD342
      if(!empty($pall))
      {
        // get all categories which use the same design
        $DB->result_type = MYSQL_ASSOC;
        $designid = $DB->query_first('SELECT designid FROM {categories} WHERE categoryid = %d', $categoryid);
        $designid = (int)$designid['designid'];
        $catids = array();
        if($getcategory = $DB->query('SELECT categoryid FROM {categories} WHERE designid = %d', $designid))
        {
          while($category = $DB->fetch_array($getcategory,null,MYSQL_ASSOC))
          {
            $catids[] = (int)$category['categoryid'];
          }
        }
        // Process all "set all" plugins for all categories with the same design
        foreach($pall as $key => $value)
        {
          $key = (int)$key; //SD342
          if(($key>= 0) && !empty($value))
          {
            foreach($catids as $cat)
            {
              // Replace the same plugin - if it is NOT the empty plugin AND
              // already exists in a different position within the category -
              // with the "empty" plugin to avoid any duplicates:
              if($plugins[$key] != '1') // is a string!
              {
                $DB->query("UPDATE {pagesort} SET pluginid = '1'
                  WHERE (categoryid = %d) AND (displayorder <> %d) AND (pluginid = '%s')",
                  $cat, ($key+1), $DB->escape_string($plugins[$key]));
              }
              // Delete an existing plugin from the given position:
              $DB->query('DELETE FROM {pagesort} WHERE (categoryid = %d) AND (displayorder = %d)',
                         $cat, ($key+1));
              // Finally insert the plugin at the selected position:
              $DB->query("INSERT INTO {pagesort} (categoryid, pluginid, displayorder) VALUES (%d, '%s', %d)",
                         $cat, $DB->escape_string($plugins[$key]), ($key+1));
            }
          }
        } //foreach
      }
    }
  } // if(count($plugins))

  if(count($errors) > 0)
  {
    DisplayMessage($errors, true);
    DisplayPageForm($categoryid);
  }
  else
  {
    RedirectPage('pages.php?action=edit_page&amp;categoryid=' . $categoryid.SD_URL_TOKEN,
                  AdminPhrase('pages_page_saved'));
  }

} //SavePage


// ############################################################################
// SELECT FUNCTION
// ############################################################################

switch($action)
{
  case 'display_pages':
    DisplayPages();
  break;

  case 'update_pages':
    UpdatePages();
  break;

  case 'create_page':
    DisplayPageForm();
  break;

  case 'edit_page':
    DisplayPageForm($_GET['categoryid']);
  break;

  case 'insert_page':
    SavePage();
  break;

  case 'update_page':
    SavePage();
  break;

  case 'retrieve_lost_pages':
    RetrieveLostPages();
  break;

  case 'display_page_permissions':
    DisplayPagePermissions();
  break;

  case 'update_page_permissions':
    UpdatePagePermissions();
  break;

  case 'delete_page':
    DeletePage();
  break;
}

// ############################################################################
// DISPLAY ADMIN FOOTER
// ############################################################################

DisplayAdminFooter($NoMenu);
