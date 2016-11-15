<?php
if(!defined('IN_PRGM')) exit();


function RedirectPage($new_page, $message = 'Settings Updated', $delay_in_seconds = 2, $IsError=false)
{
  $message .= '<br /><br /><a class="btn btn-sm btn-info" href="' . $new_page . '" onclick="javascript:if(typeof sd_timerID !== \'undefined\') clearTimeout(sd_timerID);">Click to redirect</a>';
  //$message .= '<br /><br /><a class="btn btn-sm btn-info" href="' . $new_page . '" onclick="javascript:if(typeof sd_timerID !== \'undefined\') clearTimeout(sd_timerID);">' .AdminPhrase('common_click_to_redirect') . '</a>';
  DisplayMessage($message, $IsError);
  AddTimeoutJS($delay_in_seconds, $new_page);
} //RedirectPage




/*
LayoutGetImage()
This function is not to be used directly, but by adding to layout like:
[GetImage:0:5020,w=200,h=100]
Then, on pageload, "CheckLayoutReplacements()" will search for a function which
is prefixed with "Layout" and the keyword, like "LayoutGetImage".
*/
function LayoutGetImage($params=null) // must return string
{
  global $DB, $sdurl, $userinfo, $plugin_names, $plugin_folder_to_id_arr;

  // Below are several security checks to assure, that the passed on plugin-id
  // is valid, the plugin and it's image path exist etc.
  if( !isset($params) || !is_array($params) || (count($params) < 1) ||
      (count($params) > 2) || empty($userinfo['pluginviewids']) )
  {
    return '';
  }
  $image_id  = Is_Valid_Number($params[0], 0, 1);
  $plugin_id = 17; //default!
  $w = $h = 0;
  if(isset($params[1]))
  {
    if($p_arr = @preg_split('#,#',$params[1],0,PREG_SPLIT_NO_EMPTY))
    {
      $plugin_id = Is_Valid_Number($p_arr[0], 17, 1);
      $p_arr = array_slice($p_arr,1);
      foreach($p_arr as $p)
      {
        if($p2_arr = @preg_split('#=#',$p,0,PREG_SPLIT_NO_EMPTY))
        {
          if(($p2_arr[0]=='w') && isset($p2_arr[1])) $w = intval($p2_arr[1]);
          if(($p2_arr[0]=='h') && isset($p2_arr[1])) $h = intval($p2_arr[1]);
        }
      }

    }
  }

  // Sanity checks:
  if( ($image_id < 0) || !$plugin_id || !isset($plugin_names[$plugin_id]) ||
      !in_array($plugin_id, $userinfo['pluginviewids']) )
  {
    return '';
  }

  if(empty($image_id))
  {
    $image_id = ' ORDER BY RAND() LIMIT 1';
  }
  else
  {
    $image_id = ' AND i.imageid = '.(int)$image_id;
  }
  $columns = '';
  if($plugin_id >= 5000)
  {
    $columns= ', s.folder, s.access_view';
  }

  if(!$folder = array_search($plugin_id, $plugin_folder_to_id_arr)) return '';
  $folder = 'plugins/'.$folder.'/images/';
  if(!is_readable(ROOT_PATH.$folder)) return '';

  $sections = empty($userinfo['categoryviewids']) ? '0' : implode(',',$userinfo['categoryviewids']);
  $DB->ignore_error = true;
  $image_arr = $DB->query_first("SELECT i.* $columns FROM {p".$plugin_id.'_images} i'.
                                ' INNER JOIN {p'.$plugin_id.'_sections} s ON s.sectionid = i.sectionid'.
                                ' WHERE i.activated = 1 AND s.activated = 1 '.
                                ' AND s.sectionid IN (%s) %s',
                                $sections, $image_id);
  $DB->ignore_error = false;

  if(!$image_arr || empty($image_arr['imageid']))
  {
    return 'Image not available!';
  }
  $imagetitle = htmlspecialchars($image_arr['title'], ENT_COMPAT);
  if(isset($image_arr['folder'])) $folder .= $image_arr['folder']; // Media Gallery subfolder support
  $img_md_exists = file_exists(ROOT_PATH.$folder . 'md_' . $image_arr['filename']);
  $image_path = $folder . ($img_md_exists ? 'md_' : '') . $image_arr['filename'];
  if(!$w || !$h)
  {
    list($w, $h, $type, $attr) = @getimagesize(ROOT_PATH.$image_path);
  }

  return '<img title="' . $imagetitle . '" alt="image" src="' .
         $sdurl. $image_path . '" width="' . $w . '" height="' . $h . '" />';

} //LayoutGetImage


function CheckLayoutReplacements($matches)
{
  global $userinfo;

  if(empty($matches) || !is_array($matches) || (count($matches) < 2)) return '';

  //SD342: support for $userinfo embedding
  if((count($matches)>2) && ($matches[1]=='userinfo') && ($matches[2]!=='password'))
  {
    if(isset($userinfo[$matches[2]])) return $userinfo[$matches[2]];
    if(isset($userinfo['profile'][$matches[2]])) return $userinfo['profile'][$matches[2]];
  }

  // Map to existing function?
  $func_name = 'Layout'.$matches[1];
  if(function_exists($func_name))
  {
    return $func_name(array_slice($matches,2));
  }

  return '';

} //CheckLayoutReplacements


function CheckPluginSlugs($pluginid, $checkAll=false) //SD343
{
  global $DB, $plugin_names, $sd_url_params, $url_variables;

  //SD351: tags and "year/month" syntax use either $sd_url_params or $url_variables
  //Before, the below check was only for one of those vars and thus prevented the
  //use of /2013/01 pages if they did not reside on first level of site structure.
  if( (empty($sd_url_params) && empty($url_variables)) ||
     (empty($checkAll) && (empty($pluginid) || !is_numeric($pluginid) || empty($plugin_names[$pluginid]))))
    return false;

  if(!empty($sd_url_params))
    $src = $sd_url_params;
  else
  {
    if(!is_array($url_variables))
      $src = @array_slice(@explode('/',$url_variables),1);
    else
      $src = @array_slice($url_variables,1);
  }

  if(empty($src) || !is_array($src)) return false;

  $result = array();
  if($getslugs = $DB->query('SELECT * FROM {slugs}'.
                            (empty($checkAll)?' WHERE pluginid = '.(int)$pluginid:'').
                            ' ORDER BY parent'))
  {
    while($entry = $DB->fetch_array($getslugs,null,MYSQL_ASSOC))
    {
      $slug = explode('/', $entry['slug']);
      if(empty($slug[0])) $slug = array_splice($slug,1);
      if(!empty($slug) && count($slug) && (count($src)==count($slug)))
      {
        $idx = 0;
        $ok = 0;
        foreach($slug as $key => $item)
        {
          $param = $src[$key];
          $seppos = strpos($param,'?');
          if($seppos !== false) $param = substr($param,0,$seppos);
          if(($item=='[tag]') && !empty($param))
          {
            $result['key'] = 'tags';
            $param = trim(urldecode($param));
            //SD343: 2012-08-13 - allow UTF-8; must sanitize, though
            $param = SanitizeInputForSQLSearch($param,false,false,true);
            $result['value'] = substr((string)$param,0,100);
            $ok++; $idx++; continue;
          }

          // for slug like "articles/2011/12"
          if(($item=='[year]') && !empty($param) && ($param=Is_Valid_Number($param,0,1970,2100)))
          {
            $result['year'] = (int)$param;
            $ok++; $idx++; continue;
          }
          if(!empty($result['year']) && ($item=='[month]') &&
             !empty($param) && Is_Valid_Number($param,0,1,12))
          {
            $result['month'] = (int)$param;
            $ok++; $idx++; continue;
          }
          //SD370: support for "DAY" (e.g. /2011/12/02)
          if(!empty($result['year']) && !empty($result['month']) &&
             ($item=='[day]') &&
             !empty($param) && Is_Valid_Number($param,0,1,31))
          {
            $result['day'] = (int)$param;
            $ok++; $idx++; continue;
          }
          break; // invalid param here!
        }
        if($ok && ($ok == $idx))
        {
          $result['id'] = $entry['term_id'];
          break;
        }
      }
    } //while
    // if ID was filled, we got a match
    if(empty($result['id'])) $result = array();
  }

  return $result;

} //CheckPluginSlugs


// ############################################################################
// DISPLAY CAPTCHA
// ############################################################################

function DisplayCaptcha($dooutput = true, $element_id = '', $forceMode=false)
{
  global $userinfo, $captcha, $mainsettings, $sdlanguage;

  $forceMode = isset($forceMode)?Is_Valid_Number($forceMode,false,1,3):false; //SD342
  //SD332: new flag "require_vvc" added
  if(!empty($userinfo['adminaccess']) ||
     (!$forceMode && (!$userinfo['require_vvc'] || (!empty($userinfo['loggedin']) && !empty($mainsettings['captcha_guests_only'])))) )
  {
    return '';
  }

  $result = '';
  if(($forceMode=='1') || ($mainsettings['captcha_method'] == '1'))
  {
    if(strlen($captcha->privatekey) && strlen($captcha->publickey))
    {
      $result = $captcha->Display(false);
    }
    else
    {
      // Fallback to VVC!
      $mainsettings['captcha_method'] = '2';
    }
  }
  if(($forceMode=='2') || ($mainsettings['captcha_method'] == '2'))
  {
    $vvcid = CreateVisualVerifyCode();
    $result = '
      <div class="sd_vvc">
      <input type="hidden" name="'.$element_id.'_vvcid" value="' . $vvcid . '" />' .
      VVCimage($vvcid) . '<br />
      ' . $sdlanguage['enter_verify_code'] . '<br />
      <input type="text" id="'.$element_id.'_verifycode" name="'.$element_id.'_verifycode" maxlength="'.SD_VVCLEN.'" style="width: '.(SD_VVCLEN*10).'px;" />
      </div>';
  }
  else
  if(($forceMode=='3') || ($mainsettings['captcha_method'] == '3')) //SD342 - simple math
  {
    $a = mt_rand () % 10; // generates the random number
    $b = mt_rand () % 10; // generates the random number
    $code = md5(($a+$b).date("H").'37'); /* a prime number */
    $result = '
    <div class="sd_vvc">
    <input type="hidden" name="'.$element_id.'_vvcid" value="'.$code.'" />
    ' . $sdlanguage['enter_verify_math'] ."<br />
    <strong>$a + $b</strong>&nbsp;=&nbsp;".
    '<input type="text" id="'.$element_id.'_verifycode" name="'.$element_id.'_verifycode" maxlength="2" size="2" style="display: inline; width: 30px;" />
    </div>';
  }

  if(empty($dooutput))
  {
    return $result;
  }
  echo $result;
  if(empty($dooutput)) //SD351: prevent output of "1" if not output wanted
  {
    return true;
  }

} //DisplayCaptcha


// ############################################################################
// CAPTCHA IS VALID
// ############################################################################

function CaptchaIsValid($element_id='',$forceMode=false)
{
  global $mainsettings, $sdlanguage, $userinfo, $captcha;

  $forceMode = isset($forceMode)?Is_Valid_Number($forceMode,false,1,3):false; //SD342
  //SD332: new flag "require_vvc" added
  //SD343: disabled captcha by empty captcha_method was missing
  if(!empty($userinfo['adminaccess']) ||
     (!$forceMode && (!$userinfo['require_vvc'] || empty($mainsettings['captcha_method']) ||
                      (!empty($userinfo['loggedin']) && !empty($mainsettings['captcha_guests_only'])))) )
  {
    return true;
  }

  if(($forceMode=='1') || ($mainsettings['captcha_method'] == '1'))
  {
    if(!strlen($captcha->privatekey) || !strlen($captcha->publickey))
    {
      // Fallback to VVC!
      $mainsettings['captcha_method'] = 2;
    }
    else
    {
      if(isset($_POST['g-recaptcha-response']))
      {
        return $captcha->IsValid();
      }
      else
      {
        return false;
      }
    }
  }

  if(($forceMode=='2') || ($mainsettings['captcha_method'] == '2'))
  {
    $vvcid = GetVar($element_id.'_vvcid', '', 'string', true, false);
    $code  = GetVar($element_id.'_verifycode', null, 'string', true, false);
    if(!empty($vvcid) && !empty($code) && ValidVisualVerifyCode($vvcid, $code))
    {
      return true;
    }
  }

  if(($forceMode=='3') || ($mainsettings['captcha_method'] == '3')) //SD342 - simple math question
  {
    $vvcid = GetVar($element_id.'_vvcid', '', 'string', true, false);
    $code  = GetVar($element_id.'_verifycode', null, 'natural_number', true, false);
    if(!empty($vvcid) && isset($code) && ($vvcid == md5($code.date("H").'37')))
    {
      return true;
    }
  }

  return false;

} //CaptchaIsValid


// ############################################################################
// GET ROOT CATEGORYID
// ############################################################################
// used for CreateSuckerFishMenu, to find current root categoryid

function GetRootCategoryid($categoryid)
{
  global $DB, $pages_md_arr, $pages_parents_md_arr;

  if(!empty($pages_parents_md_arr))
  {
    $root_categoryid = 0;
    while(true)
    {
      if(!empty($pages_md_arr[$categoryid]['parentid']))
      {
        $root_categoryid = $categoryid = $pages_md_arr[$categoryid]['parentid'];
      }
      else
      {
        break;
      }
    }
    return $root_categoryid;
  }

  if($category_arr = $DB->query_first('SELECT categoryid, parentid FROM {categories} WHERE categoryid = %d',$categoryid))
  {
    if($category_arr['parentid'])
    {
      $root_categoryid = GetRootCategoryid($category_arr['parentid']);
    }
    else
    {
      return $category_arr['categoryid'];
    }
  }
  else
  {
    return 1; // return home category if category not found
  }

  return $root_categoryid;

} //GetRootCategoryid


// ############################################################################
// CREATE BREADCRUMB
// ############################################################################
//SD370: added 2nd param for either a-only or li/a combo
function CreateBreadcrumb($categoryid, $tagtype='<a>') // or '<li>'
{
  global $DB, $userinfo, $mainsettings, $pages_md_arr, $pages_parents_md_arr;

  if(!isset($pages_parents_md_arr)) return;
  $categories = array();
  $cat_id = $categoryid;
  while(!empty($cat_id))
  {
    if(isset($pages_md_arr[$cat_id]))
    {
      $cat = $pages_md_arr[$cat_id];
      array_unshift($categories, $cat);
      $cat_id = $cat['parentid'];
    }
    else
    {
      break;
    }
  }

  if(empty($categories))
  {
    return '';
  }

  $Breadcrumb = '';
  $count = count($categories);
  $idx = 1;
  // $categories is array with pages for a specific parent page
  foreach($categories as $category_arr)
  {
    // $category_arr contains actual page row (either cached or from DB)
    $category_name   = strlen($category_arr['title'])?$category_arr['title']:$category_arr['name'];
    $category_link   = strlen($category_arr['link']) ? $category_arr['link'] : RewriteLink('index.php?categoryid=' . $category_arr['categoryid']);
    $category_target = strlen($category_arr['target']) ? ' target="' . $category_arr['target'] . '"' : '';
    switch($idx)
    {
      case 1 :     $class ='class="first" '; break;
      case $count: $class ='class="last" '; break;
      default :    $class = '';
    }
    //SD370: allow list items output
    //if($tagtype == '<li>') $Breadcrumb .= '<li>';
    if($category_arr['categoryid'] == $categoryid){
      $Breadcrumb .= '<li>'.$category_name.'</li>';
    }
    else{
      $Breadcrumb .= '<li><a '.$class.'href="'.$category_link.'" '.$category_target.'>'.$category_name.'</a></li>';
    }
    //$Breadcrumb .= '<li><a '.$class.'href="'.$category_link.'" '.$category_target.'>'.$category_name.'</a></li>';
    //if($tagtype == '<li>') $Breadcrumb .= '</li>';
    $idx++;
  }
  $Breadcrumb .= '<div style="clear:both;"></div>'."\n";

  return $Breadcrumb;

} //CreateBreadcrumb


// ############################################################################
// CREATE NAVIGATION
// ############################################################################

function CreateMenu($parentid = 0, $top_level_only = false, $no_menu = false,
                    $top_only = false, $bottom_only = false)
{
  global $DB, $userinfo, $mainsettings, $categoryid, $root_parent_categoryid,
         $mainsettings_sslurl, $pages_md_arr, $pages_parents_md_arr,
         $SDCache, $theme_arr;

  // SD313: try to use cache variable
  $usecache = false;
  if($SDCache->IsActive() && isset($pages_parents_md_arr))
  {
    $usecache = true;
    $categories = isset($pages_parents_md_arr[$parentid]) ? $pages_parents_md_arr[$parentid] : false;
  }
  else
  {
    $get_categories = $DB->query('SELECT * FROM {categories} WHERE parentid = %d'.
                                 ' ORDER BY displayorder ASC', $parentid);
    $categories = $DB->fetch_array_all($get_categories);
  }

  if(!$categories)
  {
    return '';
  }

  $menu = '';
  //SD370: added mobile handling
  $pre = defined('SD_MOBILE_ENABLED') && SD_MOBILE_ENABLED ? 'mobile_' : '';
  if(!$no_menu)
  {
    if(empty($theme_arr) || !is_array($theme_arr))
    {
      if(!isset($theme_arr)) $theme_arr = array();
      if($pre == '')
        $theme_arr['menu_level0_opening'] = '<ul class="sf-menu">';
      else
        $theme_arr['mobile_menu_level0_opening'] = '<ul class="fb-menu">';
      $theme_arr[$pre.'menu_level0_opening'] = '<ul>';
      $theme_arr[$pre.'menu_level0_closing'] = '</ul>';
      $theme_arr[$pre.'menu_submenu_opening'] = '<ul>';
      $theme_arr[$pre.'menu_submenu_closing'] = '</ul>';
      $theme_arr[$pre.'menu_item_opening'] = '<li>';
      $theme_arr[$pre.'menu_item_closing'] = '</li>';
      $theme_arr[$pre.'menu_item_link'] = '<a href="[LINK]"[TARGET]><span>[NAME]</span></a>';
    }
    if($parentid == 0)
    {
      $menu = $theme_arr[$pre.'menu_level0_opening'];// e.g. '<ul class="sf-menu">';
    }
    else
    {
      $menu = $theme_arr[$pre.'menu_submenu_opening'];// e.g. '<ul>';
    }
  }

  $visible_categories = 0;
  // $categories is array with pages for a specific parent page
  if(is_array($userinfo['categorymenuids']) && !empty($userinfo['categorymenuids']))
  {
    $menu_ids = array_flip($userinfo['categorymenuids']);
    $theme_menu_item_link = $theme_arr[$pre.'menu_item_link'];
    $theme_menu_item_opening = $theme_arr[$pre.'menu_item_opening'];
    foreach($categories as $category_arr)
    {
      // $category_arr contains actual page row (either cached or from DB)
      $category_arr = $usecache ? $pages_md_arr[$category_arr] : $category_arr;
      $base_url = empty($category_arr['sslurl']) ? SITE_URL : $mainsettings_sslurl;

      //SD343: in_top_navigation, in_bottom_navigation
      $nav_flag = empty($category_arr['navigation_flag']) ? 0 : (int)$category_arr['navigation_flag'];
      $cat_id = $category_arr['categoryid'];
      //SD343: added checks for page-level settings for mobile display and top/bottom navigation
      if( ($nav_flag != 4) // 4 == No menu listing!
          && @isset($menu_ids[$cat_id])
          && (!$top_only || !$nav_flag || ($nav_flag == 1))
          && (!$bottom_only || !$nav_flag || ($nav_flag == 2))
        )
      {
        $visible_categories++;
        if((strlen($category_arr['image']) > 4) && !$no_menu)
        {
          // hover image (min. length of 4)
          if(strlen($category_arr['hoverimage']) > 4)
          {
            $category_name = '<img name="sdhover' . $cat_id .
              '" src="' . $base_url . 'images/' . $category_arr['image'] . '" alt="' .
              addslashes($category_arr['name']) . '" style="border-style: none;" onMouseOver="Rollover(' .
              $cat_id . ', \'' . $base_url. 'images/' . $category_arr['hoverimage'] .
              '\', true)" onMouseOut="Rollover(' . $cat_id . ', \'' .
              $base_url . 'images/' . $category_arr['image'] . '\', false)" />';
          }
          else
          {
            $category_name = '<img src="' . $base_url . 'images/' . $category_arr['image'] .
                             '" alt="' . addslashes($category_arr['name']) . '" style="border-style: none;" />';
          }
        }
        else
        {
          $category_name = !$no_menu ? $category_arr['name'] :
                              (strlen($category_arr['title']) ?
                               $category_arr['title'] : $category_arr['name']);
        }

        $category_arr['html_class'] = isset($category_arr['html_class'])?$category_arr['html_class']:'';
        $category_arr['html_id'] = isset($category_arr['html_id'])?$category_arr['html_id']:'';
        $category_link   = strlen($category_arr['link']) ? $category_arr['link'] : RewriteLink('index.php?categoryid=' . $cat_id);
        $category_target = strlen($category_arr['target']) ? ' target="' . $category_arr['target'] . '"' : '';

        //SD370: support for HTML_CLASS and HTML_ID (set in admin|Pages per page)
        // Requires Skins|Menu Settings|Menuitem Link to contain [HTML_CLASS] and/or [HTML_ID]
        $item_link = str_replace(array('[LINK]',       '[TARGET]',       '[NAME]',
                                       '[HTML_CLASS]', '[HTML_ID]'),
                                 array($category_link, $category_target, $category_name,
                                       $category_arr['html_class'],
                                       $category_arr['html_id']),
                                 $theme_menu_item_link);

        $item = $no_menu ? '>' : $theme_menu_item_opening;

        if(($item !== '>') && (substr($item_link,0,3)!=='</a') &&
           (($cat_id == $categoryid) || ($cat_id == $root_parent_categoryid)))
        {
          $item = preg_replace ('#>#', ' class="current">', $item, 1);
        }

        //SD370: added support for HTML_CLASS and HTML_ID
        if(!$no_menu)
        {
          $item = str_replace('[HTML_ID]', $category_arr['html_id'], $item);
          if(preg_match('/class="/', $item) && (stripos($item, '[HTML_CLASS]') !== false))
          {
            $item = str_replace('[HTML_CLASS]', '', $item);
            $item = str_replace('class="', ' class="'.$category_arr['html_class'].' ', $item);
          }
          else
          {
            if(strlen(trim($category_arr['html_class'])))
              $item = str_replace('[HTML_CLASS]', ' class="'.$category_arr['html_class'].'"', $item);
            else
              $item = str_replace('[HTML_CLASS]', '', $item);
          }
        }

        $menu .= $no_menu ? substr($item_link,0,-1).$item : ($item . "\n  ". $item_link . "\n");

        if(!$no_menu && empty($top_level_only))
        {
          if($theme_arr[$pre.'menu_submenu_opening'] != ($submenu = CreateMenu($cat_id)))
          {
            $menu .= $submenu;
          }
        }

        $menu .= $no_menu ? '' : $theme_arr[$pre.'menu_item_closing']."\n";
      }
    } //foreach
  }

  if($no_menu) return $menu;

  // were there any menu items displayed?
  if(substr($menu, -(strlen($theme_arr[$pre.'menu_submenu_opening']))) == $theme_arr[$pre.'menu_submenu_opening'])
  {
    $menu = substr($menu, 0, strlen($theme_arr[$pre.'menu_submenu_opening']));
  }
  else
  if($visible_categories)
  {
    if($parentid == 0)
      $menu .= $theme_arr[$pre.'menu_level0_closing']."\n";
    else
      $menu .= $theme_arr[$pre.'menu_submenu_closing']."\n";
  }

  return $menu;

} //CreateMenu


// ############################################################################
// CREATE MOBILE NAVIGATION
// ############################################################################

function CreateMobileMenu($parentid = 0, $top_level_only = false, $no_menu = false,
                          $top_only = false, $bottom_only = false)
{
  global $DB, $userinfo, $mainsettings, $categoryid, $root_parent_categoryid,
         $mainsettings_sslurl, $pages_md_arr, $pages_parents_md_arr,
         $SDCache, $theme_arr;

  if(!defined('IN_ADMIN') && !SD_MOBILE_ENABLED) return ''; //SD370

  if( !is_array($userinfo['categorymobilemenuids']) ||
      empty($userinfo['categorymobilemenuids']) )
  {
    return '';
  }

  // SD313: try to use cache variable
  $usecache = false;
  if($SDCache->IsActive() && isset($pages_parents_md_arr))
  {
    $usecache = true;
    $categories = isset($pages_parents_md_arr[$parentid]) ? $pages_parents_md_arr[$parentid] : false;
  }
  else
  {
    $get_categories = $DB->query('SELECT * FROM {categories} WHERE parentid = %d'.
                                 ' ORDER BY displayorder ASC', $parentid);
    $categories = $DB->fetch_array_all($get_categories);
  }

  if(empty($categories) || !count($categories))
  {
    return '';
  }

  if(!is_array($theme_arr) || empty($theme_arr))
  {
    if(!isset($theme_arr)) $theme_arr = array();
    $theme_arr['mobile_menu_level0_opening'] = '<ul class="fb-menu">';
    $theme_arr['mobile_menu_level0_closing'] = '</ul>';
    $theme_arr['mobile_menu_submenu_opening'] = '<ul>';
    $theme_arr['mobile_menu_submenu_closing'] = '</ul>';
    $theme_arr['mobile_menu_item_opening'] = '<li>';
    $theme_arr['mobile_menu_item_closing'] = '</li>';
    $theme_arr['mobile_menu_item_link'] = '<a href="[LINK]"[TARGET]><span>[NAME]</span></a>';
  }

  $menuArray = array();
  $visible_categories = 0;
  // $categories is array with pages for a specific parent page
  $menu_ids = array_flip($userinfo['categorymobilemenuids']);
  $theme_menu_item_link = $theme_arr['mobile_menu_item_link'];
  $theme_menu_item_opening = $theme_arr['mobile_menu_item_opening'];
  foreach($categories as $category_arr)
  {
    // $category_arr contains actual page row (either cached or from DB)
    $category_arr = $usecache ? $pages_md_arr[$category_arr] : $category_arr;
    $base_url = empty($category_arr['sslurl']) ? SITE_URL : $mainsettings_sslurl;

    //SD343: in_top_navigation, in_bottom_navigation
    $nav_flag = empty($category_arr['navigation_flag']) ? 0 : (int)$category_arr['navigation_flag'];
    $cat_id = $category_arr['categoryid'];
    //SD343: added checks for page-level settings for mobile display and top/bottom navigation
    if( ($nav_flag != 4) // 4 == No menu listing!
        && @isset($menu_ids[$cat_id])
        && (!$top_only || !$nav_flag || ($nav_flag == 1))
        && (!$bottom_only || !$nav_flag || ($nav_flag == 2))
      )
    {
      $visible_categories++;
      $array = array();
      $array['name']   = !$no_menu ? $category_arr['name'] :
                             (strlen($category_arr['title']) ?
                              $category_arr['title'] : $category_arr['name']);

      $array['link']   = strlen($category_arr['link']) ? $category_arr['link'] : RewriteLink('index.php?categoryid=' . $cat_id);
      $array['target'] = strlen($category_arr['target']) ? ' target="' . $category_arr['target'] . '"' : '';
      $array['link']   = str_replace(array('[LINK]',       '[TARGET]',       '[NAME]'),
                               array($array['link'], $array['target'], $array['name']),
                               $theme_menu_item_link);

      if(empty($parentid) && empty($top_level_only))
      {
        $array['children'] = CreateMobileMenu($cat_id);
      }

      $menuArray[] = $array;
    }
  } //foreach

  if(!count($menuArray)) return '';
  if($parentid != 0) return $menuArray;

  $menu = $theme_arr['mobile_menu_level0_opening'];
  $theme_arr['mobile_menu_item_opening'] =
    str_replace(array('[HTML_CLASS]','[HTML_ID]'),array(),$theme_arr['mobile_menu_item_opening']);
  foreach($menuArray as $item)
  {
    $menu .= $theme_arr['mobile_menu_item_opening']."\n";
    if(isset($item['children']) && is_array($item['children']) && count($item['children']))
    {
      $menu .= '<a href="#">'.$item['name'].'</a>' . "\n" . $theme_arr['mobile_menu_submenu_opening']."\n".
               $theme_arr['mobile_menu_item_opening'].$item['link'].
               $theme_arr['mobile_menu_item_closing']."\n";

      foreach($item['children'] as $subItem)
      {
        $menu .= $theme_arr['mobile_menu_item_opening'].
                 str_replace(array('[LINK]','[TARGET]','[NAME]','[HTML_CLASS]','[HTML_ID]'),
                             array($subItem['link'], $subItem['target'], "-- ".$subItem['name'],'',''),
                             $subItem['link']).
                 $theme_arr['mobile_menu_item_closing']."\n";
      }
      $menu .= $theme_arr['mobile_menu_submenu_closing']."\n";
    }
    else
      $menu .= $item['link']."\n";

    $menu .= $theme_arr['mobile_menu_item_closing']."\n";
  }
  $menu .= $theme_arr['mobile_menu_level0_closing']."\n";

  return $menu;

} //CreateMobileMenu


// ############################################################################
// STOP LOADING PAGE
// ############################################################################
// 3 cases: website offline, page not found, no page access

function StopLoadingPage($message = '', $message_title = '', $header_code = 0, $redirect_url = '', $JSredirect=false)
{
  global $DB, $mainsettings, $mainsettings_websitetitle, $theme_arr,
         $sdlanguage, $sdurl;

  switch($header_code)
  {
    case 301 :
      @header("HTTP/1.0 301 Moved Permanently");
    break;

    case 302 :
      @header("HTTP/1.0 302 Found");
    break;

    case 303 :
      @header("HTTP/1.0 303 See Other");
    break;

    case 404 :
      @header("HTTP/1.0 404 Not Found");
    break;

    case 503 :
      @header("HTTP/1.0 503 Service Unavailable");
    break;
  }

  // redirect page, close connection, or display error message
  if(strlen($redirect_url) && ($header_code!=404))
  {
    if(!headers_sent() && ($header_code!=404)) @header('Location: ' . $redirect_url);
  }
  else
  if((isset($message) && strlen($message)) || (isset($message_title) && strlen($message_title)))
  {
    $theme_arr = $DB->query_first('SELECT * FROM {skins} WHERE activated = 1');

    // please do not remove or hide this unless you have purchased branding free or white label
    $copyright = $mainsettings['copyrighttext'];
    //SD343: take into account old BFO column AND new option from branding file
    if(!defined('BRANDING_FREE') && empty($mainsettings['bfo']))
    {
      $copyright .= ' <a class="copyright" href="' . PRGM_WEBSITE_LINK . '" title="' .
                    $sdlanguage['website_powered_by'] . ' ' . PRGM_NAME .
                    '">' . $sdlanguage['website_powered_by'] . ' ' . PRGM_NAME . '</a>';
    }
    // is there a hover menu involved?
    if($DB->query_first('SELECT categoryid FROM {categories} WHERE parentid != 0 LIMIT 1'))
    {
      $sucker_fish_header = "\n" .
          '<script type="text/javascript" src="includes/javascript/superfish/js/jquery.hoverIntent.min.js"></script>' . "\n"
        . '<script type="text/javascript" src="includes/javascript/superfish/js/superfish.js"></script>' . "\n"
        . '<script type="text/javascript" src="includes/javascript/superfish/js/supersubs.js"></script>' . "\n"
        . '<script type="text/javascript" src="includes/javascript/superfish/js/jquery.bgiframe.min.js"></script>' . "\n"
        . '<script type="text/javascript">' . "\n"
        . "//<![CDATA[\n"
        . 'jQuery(function(){' . "\n"
        . "  jQuery('ul.sf-menu').supersubs({minWidth: 12, maxWidth: 27, extraWidth:  1}).superfish();\n"
        . "  jQuery('.sf-menu ul').bgiframe();\n"
        . "});\n"
        . "//]]>\n"
        . "</script>\n";
    }
    else
    {
      $sucker_fish_header = '';
    }

    $cms_head_nomenu   = "<title>" . $mainsettings['websitetitle'] . "</title>\n"
                       . "<base href=\"" . SITE_URL . "\" /><!--[if IE]></base><![endif]-->\n"
                       . "<meta http-equiv=\"Content-Type\" content=\"text/html;charset=" . SD_CHARSET. "\" />\n"
                       . "<link rel=\"stylesheet\" type=\"text/css\" href=\"css.php\" />\n"
                       . '<script type="text/javascript" src="'.(defined('JQUERY_GA_CDN') && strlen(JQUERY_GA_CDN)?JQUERY_GA_CDN:SITE_URL.'includes/javascript/'.JQUERY_FILENAME).'"></script>'."\n"
                       . '<script type="text/javascript" src="'.SITE_URL.'includes/javascript/jquery-migrate-1.2.1.min.js"></script>';
    $cms_head_include  = $cms_head_nomenu . $sucker_fish_header;

    if(!strlen(trim($theme_arr['error_page'])))
    {
      $theme_arr['error_page'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
[CMS_HEAD_INCLUDE]
<style type="text/css">
body { margin: 10% auto; }
div#error_page {
  background-color: #FFFFFF;
  border: 2px solid #D2CFC3;
  color: #000;
  font-size: 18px;
  font-weight: bold;
  width: 720px;
  padding: 10px 20px;
  background: rgb(252, 252, 255);
  border-bottom-left-radius: 5px; -webkit-border-bottom-left-radius:  5px; -moz-border-radius-bottomleft:  5px; -khtml-border-bottom-left-radius:  5px;
  border-bottom-right-radius: 5px; -webkit-border-bottom-right-radius:  5px; -moz-border-radius-bottomright:  5px; -khtml-border-bottom-right-radius:  5px;
  box-shadow: 0px 5px 30px #2b485c; -webkit-box-shadow: 0px 5px 30px #2b485c; -moz-box-shadow: 0px 5px 30px #2b485c; -khtml-box-shadow: 0px 5px 30px #2b485c;
  margin: 50px auto 0px auto;
  min-height: 50px;
  text-align: center;
}
div#error_page a, div#error_page p { margin: 10px; font-size: 18px; font-weight: bold; }
</style>
</head>
<body>
<div id="error_page">
  <h1>[ERROR_PAGE_MESSAGE_TITLE]</h1>
  <br />[ERROR_PAGE_MESSAGE]
</div>
</body>
</html>';
    }
    $error_page = $theme_arr['error_page'];
    $error_page = str_replace(array('[ERROR_PAGE_MESSAGE]', '[ERROR_PAGE_MESSAGE_TITLE]', '[SITE_LINK]', '[SITE_TITLE]'),
                              array($message, $message_title, $sdurl, $mainsettings_websitetitle),
                              $error_page);

    $pages_menu = CreateMenu(0, false, false, true, false);
    $pages_menu_top = CreateMenu(0, true, false, true, false);
    $pages_toplevel_nomenu = CreateMenu(0, true, true, true, false);
    $pages_bottom_only = CreateMenu(0, true, false, false, true); //SD344
    $pages_bottom_only_nomenu = CreateMenu(0, true, true, false, true); //SD344
    $pages_top_only = CreateMenu(0, true, false, true, false); //SD344
    $pages_top_only_nomenu = CreateMenu(0, true, true, true, false); //SD344
    if(isset($userinfo['userid']) AND $userinfo['userid'] !=''){
      $cms_head_user_button = '<ul><li><a href="#" class="ot-btn large-btn btn-main-color icon-btn-left"><i class="fa fa-user" aria-hidden="true"></i> '.$userinfo['display_name'].'</a></li></ul>';
    }
    else{
      $cms_head_user_button = '<ul>
                <li><span class="has-icon sm-icon"><span class="lnr lnr-phone-handset icon-set-1 icon-xs"></span> <span class="sub-text-icon text-middle sub-text-middle">0112-826-2789</span></span></li>
                <li>
                    <a href="#" class="ot-btn btn-rounded btn-orange-color icon-btn-left"><i class="fa fa-key" aria-hidden="true"></i> SIGN IN</a>
                </li>
                <li>
                    <a href="#" class="ot-btn btn-rounded btn-hightlight-color icon-btn-left"><i class="fa fa-sign-in" aria-hidden="true"></i> SIGN UP</a>
                </li>
            </ul>';
    }
    $replace_search = array('[HEADER]',
                            '[FOOTER]',
                            '[NAVIGATION]',
                            '[BREADCRUMB]',
                            '[NAVIGATION-TOPLEVEL]',
                            '[NAVIGATION-TOPLEVEL-NOMENU]',
                            '[NAVIGATION_BOTTOM_ONLY]', //SD344
                            '[NAVIGATION_BOTTOM_ONLY_NOMENU]', //SD344
                            '[NAVIGATION_TOP_ONLY]', //SD344
                            '[NAVIGATION_TOP_ONLY_NOMENU]', //SD344
                            '[SUBNAVIGATION]',
                            '[SIBLINGPAGES]', //SD344
                            '[LOGO]',
                            '[CMS_HEAD_INCLUDE]',
                            '[CMS_HEAD_NOMENU]',
                            '[CMS_HEAD_USER_BUTTON]',
                            '[COPYRIGHT]',
                            '[PAGE_TITLE]',
                            '[PAGE_NAME]',
                            '[PAGE_HTML_CLASS]', //SD362
                            '[PAGE_HTML_ID]',    //SD362
                            '[REGISTER_PATH]',   //SD343
                            '[USERCP_PATH]',     //SD343
                            '[LOSTPWD_PATH]',    //SD343
                            '[LOGIN_PATH]'       //SD343
                            );
    $replace_values = array($theme_arr['header'],
                            $theme_arr['footer'],
                            $pages_menu,
                            '', #Breadcrumb,
                            $pages_menu_top,
                            $pages_toplevel_nomenu,
                            $pages_bottom_only, //SD344
                            $pages_bottom_only_nomenu, //SD344
                            $pages_top_only, //SD344
                            $pages_top_only_nomenu, //SD344
                            '', #Subnavigation
                            '', #Siblingpages
                            $mainsettings['currentlogo'],
                            $cms_head_include,
                            $cms_head_nomenu,
                            $cms_head_user_button,
                            $copyright,
                            $message_title, #Pagetitle
                            '', #Pagename
                            '', #HTML Class
                            '', #HTML ID
                            (defined('REGISTER_PATH')?REGISTER_PATH:''),
                            (defined('USERCP_PATH')?USERCP_PATH:''),
                            (defined('LOSTPWD_PATH')?LOSTPWD_PATH:''),
                            (defined('LOGIN_PATH')?LOGIN_PATH:'') );
    $error_page = str_replace($replace_search, $replace_values, $error_page);

    if(strlen($error_page) && ($theme_arr['skin_engine'] == 2))
    {
      echo $error_page;
      if(!empty($JSredirect) && !empty($redirect_url)) AddTimeoutJS(3,$redirect_url);
    }
    else
    {
      echo "<html>\r\n";
      if(!empty($JSredirect) && !empty($redirect_url))
      {
        echo "<head>\r\n".$cms_head_nomenu."\r\n<br />";
        AddTimeoutJS(3,$redirect_url);
        echo "</head>\r\n";
      }
      echo '<body><h2>'.$message.'</h2></body></html>';
    }
  }
  else
  if(!empty($JSredirect) && !empty($redirect_url)) AddTimeoutJS(3,$redirect_url);

  exit;

} //StopLoadingPage


function sd_PaginateCustomPlugin($content, $pageuri, $pluginid, $pagenum, &$haspages) //SD342
{
  global $DB, $mainsettings_enable_custom_plugin_paging;

  $result = '';
  $haspages = false;
  $ignore_excerpt_mode = false;
  $pluginid = !isset($pluginid) ? '' : $pluginid;
  if(substr($pluginid,0,1)=='c')
  {
    $pluginid = substr($pluginid,1);
    $pluginid = Is_Valid_Number($pluginid,0,1,999999);
  }
  $pagenum  = empty($pagenum)   ? 0 : Is_Valid_Number($pagenum,0,1,9999);

  // If the Custom Plugin content was not passed on, fetch it first:
  if($pluginid && $pagenum && (!isset($content) || ($content=='')))
  {
    $DB->result_type = MYSQL_ASSOC;
    if($content = $DB->query_first('SELECT plugin, ignore_excerpt_mode'.
                                   ' FROM '.PRGM_TABLE_PREFIX.'customplugins'.
                                   ' WHERE custompluginid = '.(int)$pluginid))
    {
      $ignore_excerpt_mode = !empty($content['ignore_excerpt_mode']);
      $content = $content['plugin'];
    }
    else
    {
      return ''; //SD360: early return
    }
    $DB->result_type = MYSQL_BOTH;

    if(!$ignore_excerpt_mode && ($output = CheckExcerptMode($content)))
    {
      $result = $output['content'].'
      <div class="excerpt_message">'.$output['message'].'</div>
      ';
      return $result;
    }
  }

  if(!$mainsettings_enable_custom_plugin_paging)
  {
    $result = $content;
  }
  else
  if($cpages = preg_split('/{(pagebreak)\s*(.*?)}/i', $content))
  {
    $cpagecount = count($cpages);
    if($cpagecount > 1)
    {
      $haspages = true;
      $p = new pagination;
      $p->className = $p->className . ' cp-paging';
      $p->items($cpagecount);
      $p->limit(1);
      $p->parameterName((empty($pluginid)?'':'c'.$pluginid.'-').'page');
      $p->target($pageuri);
      $p->currentPage($pagenum);
      $p->adjacents(1);
      if(isset($cpages[$pagenum-1]))
      {
        $cpagination = trim($p->getOutput());
        $result = '<div id="c'.$pluginid.'-content">'.$cpages[$pagenum-1] . "\r\n" . $cpagination .'</div>';
      }
    }
    else
    {
      $result = $cpages[0];
    }
  }
  return $result;
} //sd_PaginateCustomPlugin


// ############################ CREATE HOVER MENU #############################
// Legacy hover menu for SD 2.x skin engine

function CreateHoverMenu($catid = 0, $vn = 1, $use_charset = '', $convobj = null, $top_only = true)
{
  global $DB, $categoryid, $mainsettings_sslurl, $userinfo, $sdurl, $sd_utf8_encoding;

  if(empty($userinfo['categorymenuids']) || !is_array($userinfo['categorymenuids']))
  {
    return;
  }
  $bottom_only = false;
  $menu_ids = array_flip($userinfo['categorymenuids']);

  if(empty($use_charset) && strlen(SD_CHARSET))
  {
    $use_charset = SD_CHARSET;
  }

  if(!$getcategories = $DB->query('SELECT categoryid, parentid, name,
     link, target, image, hoverimage, menuwidth, sslurl, navigation_flag
     FROM {categories} WHERE parentid = %d ORDER BY displayorder', $catid))
  {
    return;
  }

  $count = 0;
  while($category = $DB->fetch_array($getcategories)) #No params here!
  {
    $count++;
    if(!in_array($category['categoryid'], $userinfo['categorymenuids']))
    {
      continue;
    }
    list($catid, $parentid, $name, $clink, $target, $image, $hoverimage, $menuwidth, $ssl) = $category;

    //SD370: added nav.flag processing
    $nav_flag = empty($category['navigation_flag']) ? 0 : (int)$category['navigation_flag'];
    $useit = ($nav_flag != 4) // 4 == No menu listing!
             && @isset($menu_ids[$catid])
             && (!$top_only || !$nav_flag || ($nav_flag == 1))
             && (!$bottom_only || !$nav_flag || ($nav_flag == 2));
    if(!$useit) continue;

    if(empty($vn))
    {
      echo ',';
    }
    $vn = 0;
    $base_url = empty($ssl) ? $sdurl : $mainsettings_sslurl;

    $icon      = strlen($image) ? (', \'' .$base_url . 'images/' . $image . '\'')      : ', \'' . $base_url . 'includes/javascript/hovermenu/pixel.gif width=16\'';
    $hovericon = strlen($image) ? (', \'' .$base_url . 'images/' . $hoverimage . '\'') : ', \'' . $base_url . 'includes/javascript/hovermenu/pixel.gif width=16\'';

    if(strlen($clink))
    {
      if(substr($clink, 0, 4) == 'http')
      {
        $categorylink = $clink;
      }
      else if(substr($clink, 0, 3) == 'www')
      {
        // URL needs to be fully qualified
        $categorylink = (empty($ssl) ? 'http://' : 'https://') . $clink;
      }
      else
      {
        $url_split = explode('/', $base_url);
        // Support for absolute link (regardless of SD in a sub-folder):
        if(substr($clink,0,1) == '/')
        {
          $categorylink = $url_split[0] . '//' . $url_split[2]. '/' . substr($clink,1);
        }
        else
        {
          $categorylink = $base_url . $clink;
        }
        // If present, append session id
        if(isset($userinfo['sessionurl']) && strlen($userinfo['sessionurl']))
        {
          $categorylink .= (!strstr($categorylink, '?') ? '?' : '&');
          if($usersystem['name'] == 'phpBB3')
          {
            $categorylink .= str_replace('s=','sid=',$userinfo['sessionurl']);
          }
          else
          {
            $categorylink .= $userinfo['sessionurl'];
          }
        }
      }
    }
    else
    {
      $categorylink = RewriteLink('index.php?categoryid='.$catid);
    }

    $optionsTag = (strlen($target) ? ('\'tw\': \'' . $target . '\'') : '');

    // We need to make sure that $name is converted to the desired
    // character set (if specified AND different):
    $name = addslashes($name);

    if(defined('SD_CVC') && !empty($use_charset) && ($use_charset !== SD_CHARSET))
    {
      if((SD_CHARSET !== 'utf-8') && isset($sd_utf8_encoding) && isset($sd_utf8_encoding->CharsetTable))
      {
        $name = $sd_utf8_encoding->Convert($name);
      }
      else
      {
        // Create new object for other character set
        if(!isset($convobj) || !is_object($convobj))
        {
          $convobj = sd_GetConverter(SD_CHARSET, $use_charset, 1);
        }
        if(isset($convobj) && isset($convobj->CharsetTable))
        {
          $name = $convobj->Convert($name);
        }
      }
    }

    if(empty($parentid))
    {
      if(!empty($menuwidth) && is_numeric($menuwidth))
      {
        $optionsTag = (strlen($optionsTag) ? $optionsTag . ',' : '') . '\'sw\': ' . (int)$menuwidth;
      }

      echo '[wrap_root(\'' . $name . '\'), \'' . $categorylink . '\', {' . $optionsTag . '}';
    }
    else
    if($isparent = $DB->query_first('SELECT categoryid FROM {categories} WHERE parentid = %d LIMIT 1', $catid))
    {
      echo '[wrap_parent(\'' . $name . '\'' . $icon . $hovericon . '), \'' . $categorylink . '\', {' . $optionsTag . '}';
    }
    else
    {
      echo '[wrap_child(\'' . $name . '\'' . $icon . $hovericon . '), \'' . $categorylink . '\', {' . $optionsTag . '}';
    }

    CreateHoverMenu($catid, $vn, $use_charset, $convobj, false);

    echo ']';

  } //while

  if(!empty($count))
  {
    if(isset($getcategories)) $DB->free_result($getcategories);
  }

} //CreateHoverMenu


// ########################### CREATE VISUAL VERIFY CODE #######################

function CreateVisualVerifyCode()
{
  global $DB;

  static $vvc_timeout = 1200; // 20 minutes

  // Delete old codes
  $DB->query("DELETE FROM {vvc} WHERE (datecreated IS NULL) OR (datecreated < %d)", (TIME_NOW - $vvc_timeout));

  $USERAGENT = defined('USER_AGENT') ? $DB->escape_string(USER_AGENT) : '';
  $USERIP    = defined('USERIP') ? $DB->escape_string(USERIP) : '';
  if(!empty($USERAGENT) && !empty($USERIP))
  {
    if($vvcid = $DB->query_first("SELECT vvcid FROM {vvc} WHERE useragent = '%s' AND ipaddress = '%s'", $USERAGENT, $USERIP))
    {
      if(!empty($vvcid['vvcid'])) return $vvcid['vvcid'];
    }
  }

  // Random string generator; seed for the random number
  //srand((double)microtime()*1000000); <-- unneeded since PHP 4.2!!!

  // Runs the string through the md5 function
  //$verifycode = md5(mt_rand(0,99999));
  // creates the new string
  //$verifycode = substr($verifycode, 17, SD_VVCLEN);
  $possible = '23456789bcdfghjkmnpqrstvwxyz';
  $p_len = strlen($possible)-1;
  $verifycode= '';
  $i = 0;
  for($i = 0; $i < SD_VVCLEN; $i++)
  {
    $verifycode .= substr($possible, mt_rand(0, $p_len), 1);
  }

  $DB->query("INSERT INTO {vvc} (verifycode, datecreated, useragent, ipaddress) ".
             " VALUES ('%s', %d, '%s', '%s')", $verifycode, TIME_NOW,
             $USERAGENT, $USERIP);

  return $DB->insert_id();

} //CreateVisualVerifyCode


// ######################## CHECK VALIDITY OF VERIFY CODE #####################

function ValidVisualVerifyCode($vvcid, $enteredcode)
{
  global $DB;

  $vvcid = empty($vvcid) ? 0 : (int)$vvcid;
  $enteredcode  = empty($enteredcode) ? '' : trim((string)$enteredcode);
  if(($vvcid > 0) && (strlen($enteredcode) == SD_VVCLEN))
  {
    $verifycode = $DB->query_first("SELECT verifycode FROM {vvc} WHERE vvcid = %d", $vvcid);
    $DB->query_first("DELETE FROM {vvc} WHERE vvcid = %d", $vvcid);
    if(!empty($verifycode))
    {
      return (strtolower(trim($verifycode['verifycode'])) == strtolower($enteredcode));
    }
  }

  return false;

} //ValidVisualVerifyCode


// ############################## DISPLAY VVC IMAGE ###########################

function VVCimage($vvcid)
{
  global $sdlanguage;

  return '<img id="vvc_image'.uniqid(mt_rand()).'" width="180" height="40" src="' .
         SITE_URL .
         'includes/vvc.php?vvcid=' . $vvcid . '" alt="Security Image" title="' . $sdlanguage['refresh'] .
         '" onclick="javascript: this.src=\''.SITE_URL.'includes/vvc.php?vvcid=' . $vvcid .
         '&amp;time=\' + (new Date()).getTime();" />';

} //VVCimage


// ########################## CHECK EXCERPT MODE (SD342) #######################
/* Based on user's primary usergroup -> excerpt_mode = true: this will process
   the given $content to create a non-html excerpt of it with "excerpt_length"
   characters length.
   Returns false if no content or excerpt mode if off.
   Otherwise returns array(content, message).
*/

function CheckExcerptMode($content, $overrideLength=0)
{
  global $DB, $sdlanguage, $userinfo;
  static $separator = '<br />';

  $length = (empty($overrideLength) || !is_numeric($overrideLength) || ($overrideLength < 10)) ? false : (int)$overrideLength;
  if(!$length)
  {
    $length = (empty($userinfo['usergroup_details']['excerpt_length']) ||
               !is_numeric($userinfo['usergroup_details']['excerpt_length']) ||
               ($userinfo['usergroup_details']['excerpt_length'] < 10)) ? 0 : (int)$userinfo['usergroup_details']['excerpt_length'];
  }

  if(empty($content) || !strlen($content) || !$length ||
     empty($userinfo['usergroup_details']['excerpt_mode']))
  {
    return false;
  }

  $message = '';
  if(isset($userinfo['usergroup_details']['excerpt_message']))
  {
    $message = unhtmlspecialchars($userinfo['usergroup_details']['excerpt_message']);
    $message = str_replace(array('[REGISTER_PATH]','[LOGIN_PATH]'), array(REGISTER_PATH,LOGIN_PATH), $message);
  }

  $content = preg_replace('#<span style="font-size: xx-small;">(.*)</span>#','', $content);
  $content = strip_tags(str_replace($separator,' ',$content));
  $content = sd_wordwrap($content, $length, $separator, false);
  if(false !== ($br_pos = strpos($content, $separator)))
  {
    $content = substr($content, 0, $br_pos);
  }
  $result = array('content' => $content.'...', 'message' => $message);

  return $result;

} //CheckExcerptMode

//SD343: if censor function does not exist, create at least an alternative
if(!function_exists('sd_censor'))
{
  function sd_censor($content)
  {
    return sd_removeBadWords($content);
  }
}