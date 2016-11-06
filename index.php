<?php
// ############################################################################
// DEFINE CONSTANTS
// ############################################################################
//TODO: check for faster 404 upon redirect rule of a missing image file
/* redirect to the default page - english version */

$uri_arr = (explode("/",$_SERVER['REQUEST_URI']));
$langs_arr = array('en','fr','de','il','ru');
if(!isset($uri_arr[1]) OR $uri_arr[1] == ''){
  header("Location:/en");
}
$user_language = $uri_arr[1];

define('IN_PRGM', true);
define('ROOT_PATH', './');
define('EMPTY_PLUGIN_PATH', 'plugins/p1_empty/empty.php');
define('CUSTOMPLUGIN_PATH', 'plugins/customplugins.php');

if (version_compare(PHP_VERSION, '5.1.0','<')) {
  die("<h2>This software requires PHP 5.1+ to run!</h2>");
}

// ############################################################################
// INIT PRGM
// ############################################################################
unset($pages_md_arr, $mainsettings, $secure);
require_once(ROOT_PATH . 'includes/init.php');
if(isset($_GET['logout']) && $_GET['logout']==1){
  session_destroy();
  header("Location:/".$user_language."/login");
}
require_once(ROOT_PATH . 'includes/languages_menu.php');

// ############################################################################
// UNSUBSCRIBE USER FROM EMAILS
// ############################################################################

if(isset($_GET['unsubscribe_id']) && ctype_digit($_GET['unsubscribe_id']))
{
  $unsubscribe_id = (string)$_GET['unsubscribe_id'];

  $user_is_unsubscribed = false;

  if($unsubscribe_id)
  {
    $join_date = substr($unsubscribe_id, 0, 10);
    $user_id   = substr($unsubscribe_id, 10);

    // does user exist?
    if($join_date && $user_id &&
       ($user_arr = $DB->query_first('SELECT userid FROM {users} WHERE userid = %d'.
                                     ' AND joindate = %d LIMIT 1',$user_id, $join_date)))
    {
      $DB->query('UPDATE {users} SET receive_emails = 0 WHERE userid = %d', $user_arr['userid']);
      $user_is_unsubscribed = true;
    }
  }

  $unsubscribe_msg = $user_is_unsubscribed ? $sdlanguage['user_unsubscribed'] : $sdlanguage['user_not_unsubscribed'];
  StopLoadingPage('<h1>' . $unsubscribe_msg . '</h1>', '', 0, '');
}


// ############################################################################
// WEBSITE OFFLINE?
// ############################################################################

if(($mainsettings_siteactivation == 'off') && !$userinfo['adminaccess'] && !$userinfo['offlinecategoryaccess'])
{
  if(isset($mainsettings_site_inactive_redirect)) //SD342
  {
    if(sd_check_url($mainsettings_site_inactive_redirect))
    {
      StopLoadingPage('', $sdlanguage['website_offline'], 503, $mainsettings_site_inactive_redirect);
    }
  }
  StopLoadingPage($mainsettings_offmessage, $sdlanguage['website_offline']);
}
//print_r($userinfo); exit();

// ############################################################################
// BUILD CATEGORIES FOR MENU
// ############################################################################

// If user is not logged in then do not display member-only categories.
// For that "$user_has_categories" stores that flag:
$user_has_categories = !empty($userinfo['categorymenuids']);
unset($categoryname,$categorylink,$categoryids,$sessioncreated);
$i = 0;

if($user_has_categories && !empty($pages_md_arr))
{
  foreach($pages_md_arr as $cat_id => $category)
  {
    if(empty($category['parentid']) && @in_array($cat_id, $userinfo['categorymenuids']))
    {
      if(strlen($category['image'] > 4))
      {
        // SD313x - Shouldn't images only be for old skins' hover menu???
        // hover image (min. length of 4)
        if(isset($category['hoverimage']) && (strlen($category['hoverimage'])>3)  /* ??? && ($design['skin_engine'] != 2) ??? */)
        {
          $categoryname[$i] = '<img name="sdhover' . $cat_id .
            '" src="' . SITE_URL. 'images/' . $category['image'] . '" alt="' .
            addslashes($category['name']) . '" style="border-style: none;" onMouseOver="Rollover(' .
            $cat_id . ', \'' . SITE_URL. 'images/' . $category['hoverimage'] .
            '\', true)" onMouseOut="Rollover(' . $cat_id . ', \'' .
            SITE_URL. 'images/' . $category['image'] . '\', false)" />';
        }
        else
        {
          $categoryname[$i] = '<img src="' . SITE_URL. 'images/' . $category['image'] .
            '" alt="' . addslashes(str_replace('[sdurl]',SITE_URL,$category['name'])) . '" style="border-style: none;" />';
        }
      }
      else
      {
        $categoryname[$i] = str_replace('[sdurl]',SITE_URL,$category['name']);
      }

      $categorylink[$i] = isset($category['link'][0]) ? $category['link'] : RewriteLink('index.php?categoryid=' . $cat_id);

      $categorytarget[$i] = isset($category['target'][0]) ? $category['target'] : '_self';

      $categoryids[$i] = (int)$cat_id;
      $i++;
    }
  }
}
$categoryrows = $i;
unset($i, $cat_id, $category, $category_parents);

unset($article_arr);
$article_id = $page_identified_by_SEO = $page_to_load = false;
$categoryid = 0;
$hasParams = strpos($uri, '?');
$url_variables = '';
$uri = isset($_SERVER['REQUEST_URI'])?(string)$_SERVER['REQUEST_URI']:'';
$do301 = false;
$sd_variable_arr = array();
$sd_variable_arr_count = 0;
$mainsettings_tag_results_page = empty($mainsettings_tag_results_page) ? 0 : Is_Valid_Number($mainsettings_tag_results_page,0,1);

/* //SD342
Note: regardless of "modrewrite" option check the URL to allow for
way better detection of old links and needed redirecting.
Supported cases and notes ("http://..." left out)
*) Clones of the Articles plugin require an extra plugin id URL parameter
   "pid" so that SEO works, like
   /blog.htm?pid=5008&p5008_articleid=2
*) blog.htm?p2_articleid=5
   TODO: Article is opened, but no redirect yet if SEO is enabled
*) /blog/p2_articleid/5
   If SEO is ON and the article does have a SEO title, this will 301 to
   the correct SEO-enabled URL, e.g. http://www.dom.com/blog/myarticle.htm
*) eventually /blog/myarticle.htm?p2_page=4 -> /blog/myarticle/page/4 ???

*/
$check_vars = $article_url_wrong = $next_is_articleid = false;
$uri = urldecode($uri); //SD343 2012-08-16 needed for non-latin languages
$olddog = !empty($GLOBALS['sd_ignore_watchdog']);
$GLOBALS['sd_ignore_watchdog'] = true;
$parsed_uri = @parse_url($uri); //SD370
$GLOBALS['sd_ignore_watchdog'] = $olddog;
if(strlen(SITE_URL))
{
  // Test: http://127.0.0.1/index.php?categoryid=1&p2_articleid=59#comments
  if(strlen($uri) && !strstr($uri, '/index.php') && substr($uri, -4) != '.php')
  {
    // get request_uri and remove any trailing slash
    // ex: /program_folder/features.html?test=1
    // ex: /program_folder
    $uri = substr($uri, -1) == '/' ? substr($uri, 0, -1) : $uri;

    // find the subfolders of the url
    // ex: /program_folder/
    $sub_folders = preg_replace("#https?://[^/]+(/?.*)#", "\$1", SITE_URL);

    // now subtract the subfolders from the request_uri
    // this will leave us with the variables in the url
    // ex: home/articles.html?test=1

    //SD370: applied "parse_url"
    if(strlen($uri) && is_array($parsed_uri) && isset($parsed_uri['path']))
    {
      $url_variables = substr($parsed_uri['path'], strlen($sub_folders));
    }

    $sd_url_params = array();
    $prev_seo = '';
    $idx = $last_valid_var = $prev_id = 0;
    $article_id = 0;
    if(strlen($url_variables))
    {
       // explode the url variables
      $sd_variable_arr = @preg_split('#/#', $url_variables, -1, PREG_SPLIT_NO_EMPTY);
      $sd_variable_arr_count = count($sd_variable_arr);
      $last_var = $sd_variable_arr_count - 1;
      if(($last_var < 0) || ($last_var > 255)) // SD, 2014-08-06 - check index
      {
        $last_var = 0;
        #$sd_variable_arr[0] = '/';
      }
      else
      {
        // search and remove real php arguments
        // we only want the friendly url variables
        // the last key in the array could be something like:
        // home.html?alpha=1&beta=2 (so get rid of ?alpha=1&beta=2)
        if(($args_pos = strpos($sd_variable_arr[$last_var], '?')) !== false)
        {
          $sd_variable_arr[$last_var] = substr($sd_variable_arr[$last_var], 0, $args_pos);
        }

        // remove url extension
        $last_var_org = $sd_variable_arr[$last_var];
        if(strlen($mainsettings_url_extension) && (strpos($sd_variable_arr[$last_var], $mainsettings_url_extension) !== false) )
        {
          $sd_variable_arr[$last_var] = substr($sd_variable_arr[$last_var], 0, -strlen($mainsettings_url_extension));
        }
      }

      //SD341: check and extract for SD 2.6 SEO names like "&pXXXX_articleid=YYYY" (incl. clones)
      if(preg_match('#p([0-9]*)_articleid=([0-9]*)#',$url_variables,$article_matches) && (count($article_matches)==3))
      {
        array_shift($article_matches);
        $pid = Is_Valid_Number($article_matches[0],0,2,9999);
        $article_id = Is_Valid_Number($article_matches[1],0,1,999999);
        if($article_arr = sd_cache_article($pid, $article_id))
        {
          $categoryid = (int)$article_arr['categoryid'];
          if($mainsettings_modrewrite && isset($article_arr['seo_title']) && strlen($article_arr['seo_title']))
          {
            $do301 = true;
            $page_identified_by_SEO = true;
          }
        }
      }
      unset($article_matches);
      
      //SD342: if sub-categories in URL are allowed AND there are more than 1 variables:
      if(!$page_identified_by_SEO && $sd_variable_arr_count)
      {
        $pid = GetVar('pid', 2, 'whole_number');
        foreach($sd_variable_arr as $tmp)
        {
          $tmp2 = false;
          // Check if variable is a SEO page
          if($tmp2 = (!empty($pages_seo_arr[$tmp]) ? $pages_md_arr[$pages_seo_arr[$tmp]] : false))
          {
            if($tmp2 && isset($pages_parents_md_arr[$prev_id]) &&
               @in_array($tmp2['categoryid'], $pages_parents_md_arr[$prev_id]))
            {
              // Redirect SEO URL to "index.php?categoryid=xxx" format
              if(!$mainsettings_modrewrite) $do301 = true;
              $categoryid = (int)$tmp2['categoryid'];
              $last_valid_var = $idx;
            }
            else
            {
              if(!empty($tmp2['categoryid'])) //SD342 if present, use it
              {
                $categoryid = (int)$tmp2['categoryid'];
                $page_identified_by_SEO = true;
              }
              else
              if($idx < $sd_variable_arr_count)
              {
                $do301 = true;
                break;
              }
            }
          }
          // Check if current param is like "pXXX_articleid" and next param is an article id
          elseif(($idx < $last_var) && preg_match('#p([0-9]*)_articleid#',$tmp,$matches) && (count($matches)==2))
          {
            if($pid = Is_Valid_Number($matches[1],0,2,9999))
            {
              $next_is_articleid = ($pid == 2) || (($pid >= 5000) && ($pid <= 9999));
            }
            else
            {
              unset($pid);
            }
          }
          elseif($next_is_articleid)
          {
            // Catches old 2.6 article link like "/blog/p2_articleid/59"
            $next_is_articleid = false;
            $article_id = Is_Valid_Number($tmp,0,1,999999);
            if($article_arr = sd_cache_article($pid, $article_id))
            {
              $article_arr['pluginid'] = $pid;
              $categoryid = (int)$article_arr['categoryid'];
              // if SEO is enabled and article has SEO title, then redirect
              if($mainsettings_modrewrite && isset($article_arr['seo_title']) && strlen($article_arr['seo_title']))
              {
                $do301 = true;
                $page_identified_by_SEO = true;
              }
            }
            else
            {
              $article_url_wrong = true;
              unset($article_arr,$article_id);
            }
          }
          // Check if the LAST param is an article SEO
          elseif($idx == $last_var)
          {
            if($article_seo_arr = sd_cache_articles($pid))
            {
              if(isset($article_seo_arr[$tmp]))
              {
                $article_id = $article_seo_arr[$tmp];
              }
              else //SD342 check for old 2.6 article link
              if(preg_match('#-a([0-9]*)$#',$tmp,$matches) && (count($matches)==2) && (false!==(array_search($matches[1],$article_seo_arr))))
              {
                $article_id = (int)$matches[1];
                $do301 = true;
              }
            }
          }
          else
          {
            // Unknown "variable", assume from here on "friendly" params and quit
            if(strlen($prev_seo))
            {
              $page_identified_by_SEO = true;
              $page_to_load = $prev_seo;
              $check_vars = true;
            }
            break;
          }
          $idx++;
          if($idx == $sd_variable_arr_count)
          {
            // If the loop did not break and this is the last variable, then the URL is valid
            $page_identified_by_SEO = true;
            $page_to_load = !empty($article_id) ? $prev_seo : $tmp;
          }
          if($tmp2!==false)
          {
            $prev_id  = $tmp2['categoryid'];
            $prev_seo = $tmp2['urlname'];
          }
          if(!empty($categoryid) && ($categoryid == $mainsettings_tag_results_page)) //SD343
          {
            break;
          }
        }
      }

      if($page_identified_by_SEO && !empty($article_id) && $pid && !isset($article_arr))
      {
        if($article_arr = sd_cache_article($pid, $article_id))
        {
          //SD342 if article is not active, then do 404 and redirect to page
          $hasAccess = !empty($userinfo['adminaccess']) ||
                       (empty($article_arr['access_view']) ||
                        in_array($userinfo['usergroupid'], explode('|',$article_arr['access_view'])));
          //news plugin v3.5.2 (SD351): error if "Publish End" date for article passed
          if($hasAccess)
          {
            $pid_settings = GetPluginSettings($pid);
            if(empty($pid_settings['ignore_publish_end_date']) &&
               !empty($article_arr['dateend']) &&
               ($article_arr['dateend'] < TIME_NOW))
            {
              $hasAccess = false;
            }
          }
          if(!$hasAccess || empty($article_arr['settings']) || (($article_arr['settings'] & 2) == 0))
          {
            $new_link = RewriteLink('index.php?categoryid='.(int)$categoryid);
            StopLoadingPage('<a href="' . $new_link . '">' . $sdlanguage['redirect_to_homepage'] . '</a>',
                            $sdlanguage['page_not_found'], 404, $new_link, true);
          }
          else
          if($sd_variable_arr_count > 1)
          {
            $page_to_load = $sd_variable_arr[$sd_variable_arr_count - 2];
            $page_identified_by_SEO = true;
          }
        }
      }

      // If indicated by "$check_vars", copy extra params to "$sd_url_params"
      // so that these can be picked up by a plugin
      // Example: http://localhost:8080/sdcom/testing/sd3-media-gallery/p5020/gallery/2
      // Page is: "sd3-media-gallery.htm" with params "p5020/section/2"
      if($check_vars)
      {
        for($idx; $idx <= $last_var; $idx++)
        {
          $sd_url_params[] = $sd_variable_arr[$idx];
        }
      }
      unset($idx, $prev_id, $prev_seo, $pid, $tmp, $tmp2);

      //SD341: redirect old article URL to new SEO URL:
      if($do301 && $page_identified_by_SEO && isset($article_arr) && is_array($article_arr))
      { //old link: http://www.site.com/old-article-url-a59.htm = article 59
        $new_link = RewriteLink('index.php?categoryid='.(int)$article_arr['categoryid']);
        if($mainsettings_modrewrite)
        {
          $new_link = preg_replace('#'.SD_QUOTED_URL_EXT.'$#', '/' . $article_arr['seo_title'] .
                                       $mainsettings_url_extension, $new_link);
        }
        else
        {
          $pid = (int)$article_arr['pluginid'];
          $new_link .= '&pid='.$pid.'&p'.$pid.'_articleid='.$article_arr['articleid'];
        }
        StopLoadingPage('', '', 301, $new_link);
      }
      elseif($do301 && $categoryid)
      {
        $new_link = RewriteLink('index.php?categoryid='.(int)$categoryid);
        StopLoadingPage('', '', 301, $new_link);
      }
      elseif(!$categoryid)
      {
        StopLoadingPage('<a href="' . RewriteLink('index.php?categoryid=1') . '">' . $sdlanguage['redirect_to_homepage'] . '</a>', $sdlanguage['page_not_found'],
          404, RewriteLink('index.php?categoryid=1'), true);
      }
    }
  }
  else
  // If "index.php" is detected then check for categoryid.
  // This will then redirect (301) to the SEO URL of the targeted page if user
  // has permission for it or otherwise do 404 and redirect to homepage.
  if($mainsettings_modrewrite && strlen($uri) && strstr($uri, '/index.php'))
  {
    $categoryid = (int)GetVar('categoryid', 1, 'whole_number');
    if($user_has_categories && isset($pages_md_arr[$categoryid]))
    {
      $new_link = RewriteLink('index.php?categoryid='.(int)$categoryid);
      //SD342: check and extract for SD 2.6 SEO names like "&pXXXX_articleid=YYYY" (incl. clones)
      //http://127.0.0.1:8080/sdcom/index.php?categoryid=1&p2_articleid=59#comments
      if(preg_match('#p([0-9]*)_articleid=([0-9]*)#',$uri,$article_matches) && (count($article_matches)==3))
      {
        array_shift($article_matches);
        $pid = Is_Valid_Number($article_matches[0],0,2,9999);
        $article_id = Is_Valid_Number($article_matches[1],0,1,999999);
        if(!empty($article_id) && ($article_arr = sd_cache_article($pid, $article_id)))
        {
          if($mainsettings_modrewrite && isset($article_arr['seo_title']) && strlen($article_arr['seo_title']))
          {
            $new_link = RewriteLink('index.php?categoryid='.(int)$categoryid.'&p'.$pid.'_articleid='.$article_id);
          }
        }
      }
      StopLoadingPage('', '', 301, $new_link);
    }
    else
    {
      $new_link = RewriteLink('index.php?categoryid=1');
      StopLoadingPage('', '', 404, $new_link, true);
    }
  }
  else
  //SD342: SEO off, check and extract article id if possible
  if(!$mainsettings_modrewrite && strlen($uri) && strstr($uri, '/index.php'))
  {
    $categoryid = (int)GetVar('categoryid', 1, 'whole_number');
    if($user_has_categories && isset($pages_md_arr[$categoryid]))
    {
      $new_link = RewriteLink('index.php?categoryid='.(int)$categoryid);
      if(preg_match('#p([0-9]*)_articleid=([0-9]*)#',$uri,$article_matches) && (count($article_matches)==3))
      {
        array_shift($article_matches);
        $pid = Is_Valid_Number($article_matches[0],0,2,9999);
        if($article_id = Is_Valid_Number($article_matches[1],0,1,999999))
          $article_arr = sd_cache_article($pid, $article_id);
      }
    }
  }
}
//echo $categoryid; exit();
if(!$categoryid)
{
  $categoryid = GetVar('categoryid', 1, 'whole_number');
}
elseif($article_url_wrong)
{
  // If an article was specified, but not found, then do 404
  $new_link = RewriteLink('index.php?categoryid='.$categoryid);
  StopLoadingPage('', '', 404, $new_link, true);
}
unset($check_vars, $article_url_wrong, $next_is_articleid);
define('PAGE_ID', (int)$categoryid);
//SD361: redirect page immediately, if it has an external link:
if(isset($pages_md_arr[PAGE_ID]) && strlen(trim(($pages_md_arr[PAGE_ID]['link']))))
{
  StopLoadingPage('', '', 301, $pages_md_arr[PAGE_ID]['link']);
}

//SD322: If user is logged in, update session with current location
if(($usersystem['name'] == 'Subdreamer') && !empty($userinfo['userid']))
{
  $DB->query("UPDATE {sessions} SET location = '%s' WHERE userid = %d AND location <> '%s' AND admin = 0",
             PAGE_ID, $userinfo['userid'], PAGE_ID);
}
$sd_instances = array(); //SD343: contains plugin class instances (objects; key is pluginid)

// Below variable is used in "functions_frontend()" -> "CreateMenu()"
$root_parent_categoryid = GetRootCategoryid(PAGE_ID);

// GET SKIN, PAGE AND LAYOUT
// Check for skin-design's cache file, containing batches of 10 pages:
$theme_arr = false;
$designs = array();
$base_designs_cacheid = $designs_cacheid = (int)floor(PAGE_ID/10);

//SD371: skin override for admins (e.g. for site testing)
$skinid_override = $ipsum_override = 0;
if(!empty($userinfo['adminaccess']))
{
  $ipsum_override = GetVar('loremipsum', 0, 'bool', false, true);
  if($skinid_override = GetVar('skinid', 0, 'whole_number', false, true))
  {
    if(!$DB->query_first('SELECT 1 FROM {skins} WHERE skinid = '.$skinid_override))
    {
      $skinid_override = 0;
    }
  }
}

if(!$skinid_override)
{
  if(SD_MOBILE_ENABLED) //SD370: switch to mobile cache
  {
    $designs_cacheid = MOBILE_CACHE.$designs_cacheid;
  }

  if( $SDCache && $SDCache->IsActive() &&
      (($getdesigns = $SDCache->read_var(CACHE_PAGE_DESIGN.$designs_cacheid, 'designs')) !== false) )
  {
    if(isset($getdesigns['designs']) &&
       isset($getdesigns['designs'][PAGE_ID]) &&
       is_array($getdesigns['designs'][PAGE_ID]))
    {
      $theme_arr = (array)$getdesigns['designs'][PAGE_ID];
    }
  }
}
if(!$theme_arr)
{
  //SD370: make sure that new html_class and html_id do not break site before upgrade
  //SD370: mobile layout support
  // Get all important design data; w/COMPLETE row from "skins" for further use!
  $pre = '';
  if($skinid_override) //SD371: load different skin data
  {
    $join = ' {skin_bak_cat} c2 '.
            ' INNER JOIN {designs} d ON d.designid = c2.designid'.
            ' INNER JOIN {categories} c ON c.categoryid = c2.categoryid'.
            ' INNER JOIN {skins} s ON s.skinid = c2.skinid'.
            ' WHERE c2.skinid = '.$skinid_override.
            ' AND c.categoryid = '.PAGE_ID;
  }
  else
  {
    $join = '{designs} d INNER JOIN {categories} c ON ';
    if(SD_MOBILE_FEATURES && SD_MOBILE_ENABLED)
    {
      $join .= 'c.mobile_designid = d.designid';
      $pre = 'mobile_';
    }
    else
    {
      $join .= 'c.designid = d.designid';
    }
    $join .= ' INNER JOIN {skins} s ON s.skinid = d.skinid
    WHERE c.categoryid BETWEEN %d AND %d';
  }
  if($getdesigns = $DB->query(
    'SELECT c.categoryid, d.maxplugins, d.designpath, IFNULL(d.designid,0) designid'.
    (SD_MOBILE_FEATURES?', IFNULL(c.mobile_designid,c.designid) mobile_designid':'').
    ", d.design_name, c.sslurl, c.name AS categoryname, c.metadescription,
    c.metakeywords, c.appendkeywords, c.urlname, c.title,
    IFNULL(c.html_class,'') html_class, IFNULL(c.html_id,'') html_id,
    s.skinid, s.skin_engine, s.name skinname, s.activated, s.numdesigns,
    s.previewimage, s.authorname, s.authorlink, s.folder_name,
    s.".$pre."menu_level0_opening, s.".$pre."menu_level0_closing,
    s.".$pre."menu_submenu_opening, s.".$pre."menu_submenu_closing,
    s.".$pre."menu_item_opening, s.".$pre."menu_item_closing,
    s.".$pre."menu_item_link, s.".$pre."header, s.".$pre."footer,
    d.layout, s.header, s.footer, s.error_page
    FROM $join",
    $base_designs_cacheid*10, $base_designs_cacheid*10+9))
  {
    while($design = $DB->fetch_array($getdesigns,null,MYSQL_ASSOC))
    {
      $cid = (int)$design['categoryid'];
      if($cid==PAGE_ID)
      {
        $theme_arr = $design;
      }
      $designs[$cid] = $design;
    }
    if(!$skinid_override && $designs_cacheid && $SDCache && $SDCache->IsActive())
    {
      $SDCache->write_var(CACHE_PAGE_DESIGN.$designs_cacheid, 'designs', array('designs' => $designs), false);
    }
  }
}
unset($base_designs_cacheid,$cid,$designs,$designs_cacheid,$getdesigns,$join,$pre);

if(!$theme_arr || empty($theme_arr['categoryid']))
{
  StopLoadingPage('<a href="' . RewriteLink('index.php?categoryid=1') . '">' .
                  $sdlanguage['redirect_to_homepage'] . '</a>', $sdlanguage['page_not_found'],
                  404, RewriteLink('index.php?categoryid=1'), true);
}

//SD370: make sure to set some new fields:
$theme_arr['html_class'] = isset($theme_arr['html_class'])?$theme_arr['html_class']:'';
$theme_arr['html_id']    = isset($theme_arr['html_id'])?$theme_arr['html_id']:'';
$theme_arr['mobile_header'] = isset($theme_arr['mobile_header'])?$theme_arr['mobile_header']:'';
$theme_arr['mobile_footer'] = isset($theme_arr['mobile_footer'])?$theme_arr['mobile_footer']:'';

define('SKIN_ENGINE', (int)$theme_arr['skin_engine']);
define('SKIN_DESIGN', isset($theme_arr['design_name'])?$theme_arr['design_name']:''); //SD360
define('SD_CATEGORY_TITLE_RAW', $theme_arr['title']); //SD350
define('SD_CATEGORY_TITLE', strip_alltags($theme_arr['title'])); //SD350


// ############################################################################
// CHECK SSL ACCESS (SD322)
// ############################################################################

// Let's check if this category is allowed to be viewed without SSL... if not it is a good time to escape
if(!empty($mainsettings['forcessl']) && !empty($theme_arr['sslurl']) &&
   (empty($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS'])=='off'))
{
  StopLoadingPage('<a href="' . RewriteLink('index.php?categoryid=1') . '">' .
                  $sdlanguage['redirect_to_homepage'] . '</a>', $sdlanguage['page_not_found'],
                  404, RewriteLink('index.php?categoryid=1'), true);
}


// ############################################################################
// CHECK PAGE ACCESS
// ############################################################################

if((!$user_has_categories || !@in_array(PAGE_ID, $userinfo['categoryviewids'])) &&
   (!$userinfo['adminaccess'] && !$userinfo['offlinecategoryaccess']) )
{
  //SD342: differentiate error message for guests; added new title phrase
  if(empty($userinfo['loggedin']))
  {
    StopLoadingPage($sdlanguage['no_view_access_guests'].
                    '<br /><a href="' . RewriteLink('index.php?categoryid=1') . '">' .
                    $sdlanguage['redirect_to_homepage'] . '</a>',
                    $sdlanguage['no_view_access_title_guests']);
  }
  StopLoadingPage($sdlanguage['no_view_access'].
                  '<br /><a href="' . RewriteLink('index.php?categoryid=1') . '">' .
                  $sdlanguage['redirect_to_homepage'] . '</a>',
                  $sdlanguage['no_view_access_title']);
}

//SD343 redirect if wrong protocol
if(!empty($secure) && (substr($sdurl,0,5)!='https'))
{
  //SD370: redirect to configured SSL URL if set, otherwise to non-SSL URL
  if(!empty($mainsettings_sslurl))
    StopLoadingPage('','',301,$mainsettings_sslurl,true);
  else
    StopLoadingPage('','',404,$sdurl,true);
}
else
if(empty($secure) && (substr($sdurl,0,5)=='https'))
{
  $sdurl = 'http'.substr($sdurl,5);
  StopLoadingPage('','',404,$sdurl,true);
}

// ############################################################################
// SET COPYRIGHT
// ############################################################################

$copyright = $mainsettings_copyrighttext;
//SD322: take into account old BFO column AND new option from branding file
if(!defined('BRANDING_FREE') && empty($mainsettings_bfo))
{
  $copyright .= ' <a class="copyright" href="'.PRGM_WEBSITE_LINK.
                '" title="'.$sdlanguage['website_powered_by'].' '.PRGM_NAME.'">'.
                $sdlanguage['website_powered_by'].' '.PRGM_NAME.'</a>';
}


// ############################################################################
// SET RSS LINKS
// ############################################################################

$rss_link = '';
if($mainsettings_enable_rss)
{
  $rss_link .= '<link rel="alternate" type="application/rss+xml" title="' .
               htmlspecialchars($mainsettings_websitetitle) .
               '" href="' . SITE_URL . 'rss.php" />' . "\n";
}
if($mainsettings_enable_rss_forum)
{
  //SD370: translated plugin name instead of hardcoded "Forums"
  $tmp = isset($plugin_names['Forum'])?$plugin_names['Forum']:'Forums';
  $rss_link .= '<link rel="alternate" type="application/rss+xml" title="' .
               htmlspecialchars($mainsettings_websitetitle) .
               ' - '.$tmp.'" href="' . SITE_URL . 'forumrss.php" />' . "\n";
}

// ############################################################################
// FORMAT WEBSITE TITLE
// ############################################################################

if(!strlen($mainsettings_title_separator))
{
  $mainsettings_title_separator = ' ';
}

// combine page title with website title?
if($mainsettings_categorytitle && strlen(SD_CATEGORY_TITLE_RAW))
{
  if(empty($mainsettings_title_order))
  {
    $mainsettings_websitetitle .= $mainsettings_title_separator . SD_CATEGORY_TITLE_RAW;
  }
  else
  {
    $mainsettings_websitetitle = SD_CATEGORY_TITLE_RAW . $mainsettings_title_separator . $mainsettings_websitetitle;
  }
}

// ############################################################################
// FORMAT WEBSITE META DESCRIPTION AND KEYWORDS
// ############################################################################

$ExtraHeader = ''; //SD343 init extra header
$mainsettings_metadescription = trim($mainsettings_metadescription);
$mainsettings_metakeywords    = trim($mainsettings_metakeywords);

if(isset($article_arr) && @is_array($article_arr))
{
  $metadescription = empty($article_arr['metadescription']) ? '' : trim($article_arr['metadescription']);

  // Replace main meta description with article meta description...
  if(strlen($metadescription))
  {
    $mainsettings_metadescription = $metadescription;
  }
  else
  {
    $article_arr['metadescription'] = $metadescription;
    // ...or add first 15 words of article (or description otherwise) to meta description
    $tmp = ($article_arr['article']);
    if(!strlen($tmp))
    {
      $tmp = ($article_arr['description']);
    }
    if(strlen($tmp))
    {
      // Replace special tags by single blank so that words do not combine
      $tmp = trim(strip_alltags(preg_replace(array('#\s+#m','#&nbsp;?#im','#<br\s+/?>#im','#</div>#im','#</span>#im'),
                                             array(' ', ' ', ' ', ' ', ' '), $tmp)));
    }
    if(strlen($tmp))
    {
      $mainsettings_metadescription = trim(implode(' ', array_slice(array_filter(explode(' ', $tmp)),0,15)));
    }
  }

  unset($metadescription);

  // Add article meta keywords to current meta keywords
  if(isset($article_arr['metakeywords']) && strlen(trim($article_arr['metakeywords'])))
  {
    $mainsettings_metakeywords = trim($article_arr['metakeywords']);//.','.$mainsettings_metakeywords;
  }

  //SD342: different combinations with article title first
  //SD350: default behavior change: add article title even for 0/1
  $tmp_title = strlen($article_arr['title']) ? strip_tags($article_arr['title']) : false;
  switch($mainsettings_title_order)
  {
    case 0: $mainsettings_websitetitle .= $tmp_title ? ($mainsettings_title_separator.$tmp_title) : '';
            break;
    case 1: $mainsettings_websitetitle = $mainsettings_categorytitle?$mainsettings_websitetitle:SD_CATEGORY_TITLE_RAW.$mainsettings_title_separator.$mainsettings_websitetitle;
            $mainsettings_websitetitle .= $tmp_title ? $tmp_title : '';
            break;
    case 2: $mainsettings_websitetitle = $tmp_title ? $tmp_title : ''; break;
    case 3: $mainsettings_websitetitle = $tmp_title ? ($mainsettings_websitetitle):$mainsettings_websitetitle; break;
    case 4: $mainsettings_websitetitle = $tmp_title ? (SD_CATEGORY_TITLE_RAW):$mainsettings_websitetitle; break;
    case 5: $mainsettings_websitetitle = $tmp_title ? (SD_CATEGORY_TITLE_RAW.$mainsettings_title_separator.$mainsettings_websitetitle_original):$mainsettings_websitetitle; break;
    case 6: $mainsettings_websitetitle = $tmp_title ? ($mainsettings_websitetitle_original.$mainsettings_title_separator.SD_CATEGORY_TITLE_RAW):$mainsettings_websitetitle; break;
  }
  unset($tmp_title);
}
else
{
  if(!empty($theme_arr['appendkeywords']))
  {
    if(!empty($theme_arr['metakeywords']))
    {
      $mainsettings['metakeywords'] .= (strlen($mainsettings['metakeywords']) ? ',' : '') . $theme_arr['metakeywords'];
    }
    if(!empty($theme_arr['metadescription']))
    {
      $mainsettings['metadescription'] .= (strlen($mainsettings['metadescription']) ? ',' : '') . $theme_arr['metadescription'];
    }
  }
  else
  {
    if(isset($theme_arr['metakeywords']) && strlen($theme_arr['metakeywords']=trim($theme_arr['metakeywords'])))
    {
      $mainsettings_metakeywords = $theme_arr['metakeywords'];
    }
    if(isset($theme_arr['metadescription']) && strlen($theme_arr['metadescription']=trim($theme_arr['metadescription'])))
    {
      $mainsettings_metadescription = $theme_arr['metadescription'];
    }
  }
}

if(isset($mainsettings_metakeywords))
{
  $mainsettings_metakeywords = trim($mainsettings_metakeywords,', ');
}
$mainsettings_metadescription = str_replace('"',"'", $mainsettings_metadescription); //SD342
$mainsettings_metakeywords    = str_replace(array(', ','"'),array(',',"'"),
                                            $mainsettings_metakeywords); //SD342

//SD343: for special Tag results page
$sd_tag_value = false;
$sd_tagspage_link = '';
if($mainsettings_tag_results_page)
{
  $sd_tagspage_link = preg_replace('#'.SD_QUOTED_URL_EXT.'$#', '/',
                      RewriteLink('index.php?categoryid='.$mainsettings_tag_results_page));
}
if(PAGE_ID && !empty($mainsettings_tag_results_page) &&
   (PAGE_ID == $mainsettings_tag_results_page))
{
  if(false !== ($res = CheckPluginSlugs(0,true)) && is_array($res) && !empty($res))
  {
    define('SD_TAG_DETECTED', true);
    $sd_tag_value = $res;
    if(!empty($sd_tagspage_link))
    $ExtraHeader .= "\n".'<link rel="canonical" href="'.$sd_tagspage_link.'" />';
  }
}
defined('SD_TAG_DETECTED') || define('SD_TAG_DETECTED', false);

// DO NOT CLEAR "$article_arr" variable!


// ############################################################################
// INITIALIZE VARIABLES TO PREVENT ATTACK
// ############################################################################

$customplugincount = 0;
$customplugin     = array();
$custompluginfile = array();
$customplugin_ids = array();
$pluginids        = array();
$pluginname       = array();
$pluginpath       = array();
$custompluginoptions   = array(); //SD342
$c_customplugin_ids    = array();
$c_customplugin        = array();
$c_custompluginfile    = array();
$c_custompluginoptions = array();
$c_pluginids   = array();
$c_pluginname  = array();
$c_pluginpath  = array();
$plugin_header = ''; //SD313: reintroduced
$design_maxplugins = isset($theme_arr['maxplugins']) ? (int)$theme_arr['maxplugins'] : 0;


// ############################################################################
// INITIALIZE AND LOAD CACHED CATEGORY IF ENABLED
// ############################################################################

$IsCached = false; // DO NOT REMOVE!
$cache_id = (SD_MOBILE_ENABLED?MOBILE_CACHE:'').CACHE_PAGE_PREFIX.PAGE_ID;

if(!$skinid_override && $SDCache && $SDCache->IsActive()) // SD313x
{
  // Check for category cache file, containing multiple arrays:
  if(($c_categoryid = $SDCache->read_var($cache_id, 'c_categoryid')) !== false)
  {
    // Plugin arrays from cache file are loaded in global context
    if($cache_file = $SDCache->CalcCachefileForID($cache_id))
    {
      if(is_file($cache_file) && @include($cache_file))
      {
        // Is category really correct?
        if(!empty($c_categoryid) && ($c_categoryid == PAGE_ID))
        {
          // Assign cached arrays to the regular arrays:
          $IsCached = true;
          $customplugin = $c_customplugin;
          $custompluginfile = $c_custompluginfile;
          $customplugin_ids = $c_customplugin_ids;
          $custompluginoptions = isset($c_custompluginoptions)?$c_custompluginoptions:array(); //SD342
          $pluginids  = $c_pluginids;
          $pluginname = $c_pluginname;
          $pluginpath = $c_pluginpath;
        }
        unset($c_customplugin,$c_customplugin_ids,$c_custompluginfile,$c_pluginids,$c_pluginname,$c_pluginpath);
      }
    }
    // For security reasons init arrays here again
    if(!$IsCached)
    {
      $customplugin_ids = array();
      $customplugin     = array();
      $custompluginfile = array();
      $custompluginoptions = array();
      $pluginids   = array();
      $pluginname  = array();
      $pluginpath  = array();
    }
  }
}

// ############################################################################
// LOAD PLUGINS (if not loaded from cache)
// ############################################################################

if(!$IsCached) // If cache disabled or file outdated/not existing
{
  $custom_idx = 0;
  // SD313 - Coded to a single statement for processing of all skin's plugin slots!
  // Saves up to "maxplugins - 1" individual SELECT statements if all slots are set!
  $extra = '';
  if(defined('SD_342')) $extra = 'c.ignore_excerpt_mode, ';
  //SD370: if upgraded and mobile detected, switch to mobile pagesort
  if($skinid_override) //SD371
  {
    $pagesort = PRGM_TABLE_PREFIX.'skin_bak_pgs';
  }
  else
  {
    $pagesort = PRGM_TABLE_PREFIX.'pagesort';
    if(SD_MOBILE_ENABLED) $pagesort .= '_mobile';
  }

  //SD371: Pre-set arrays
  for($i = 0; $i < $design_maxplugins; $i++)
  {
    // default every plugin slot to be empty
    $pluginids[$i]  = 1;
    $pluginname[$i] = '';
    $pluginpath[$i] = EMPTY_PLUGIN_PATH;
  }

  $get_pagesort = $DB->query('SELECT ps.displayorder, ps.pluginid,
    IF(c.custompluginid is not null, 0, 1) isplugin,
    IF(c.custompluginid is not null, c.custompluginid, ps.pluginid) realpluginid,
    IF(c.custompluginid is not null, c.displayname, p.displayname) displayname,
    IF(c.custompluginid is not null, \'plugins/customplugins.php\', concat(\'plugins/\',p.pluginpath)) pluginpath,
    c.plugin, c.includefile, '.$extra.'p.authorname
    FROM '.$pagesort.' ps
    LEFT JOIN {plugins} p ON p.pluginid = ps.pluginid
    LEFT JOIN {customplugins} c ON c.custompluginid = substr(ps.pluginid,2,6) AND substr(ps.pluginid,1,1) = \'c\'
    WHERE ps.categoryid = %d'.($skinid_override?' AND ps.skinid = '.$skinid_override:'').'
    ORDER BY ps.displayorder',PAGE_ID);

  #for($i = 0; $i < $design_maxplugins; $i++)
  while($pagesort_arr = $DB->fetch_array($get_pagesort,null,MYSQL_ASSOC))
  {
    #if($pagesort_arr = $DB->fetch_array($get_pagesort,null,MYSQL_ASSOC))
    $i = $pagesort_arr['displayorder']-1;
    if(($i >= 0) && ($i < $design_maxplugins))
    {
      $pluginid = (int)$pagesort_arr['realpluginid'];
      $isCustom = (substr($pagesort_arr['pluginid'],0,1) == 'c');
      if(!empty($pluginid))
      {
        $bAllowed = ( $isCustom && @in_array($pluginid, $userinfo['custompluginviewids'])) ||
                    (!$isCustom && @in_array($pluginid, $userinfo['pluginviewids']));
        if(file_exists($pagesort_arr['pluginpath']))
        {
          // IF cache is enabled, it ALWAYS stores full category layout
          if($SDCache && $SDCache->IsActive())
          {
            if(empty($pagesort_arr['isplugin']))
            {
              $c_customplugin_ids[$custom_idx]               = $pagesort_arr['pluginid'];
              $c_customplugin[$pagesort_arr['pluginid']]     = $pagesort_arr['plugin'];
              $c_custompluginfile[$pagesort_arr['pluginid']] = $pagesort_arr['includefile'];
              $c_custompluginoptions[$pagesort_arr['pluginid']]['ignore_excerpt_mode'] =
                !empty($pagesort_arr['ignore_excerpt_mode']); //SD342
            }
            $c_pluginids[$i]  = $pagesort_arr['pluginid'];
            $c_pluginname[$i] = $pagesort_arr['displayname'];
            $c_pluginpath[$i] = $pagesort_arr['pluginpath'];
          }
          if($isCustom)
          {
            $custom_id = $pagesort_arr['pluginid'];
            $customplugin_ids[$custom_idx] = $pagesort_arr['pluginid'];
            $customplugin[$custom_id]      = $pagesort_arr['plugin'];
            $custompluginfile[$custom_id]  = $pagesort_arr['includefile'];
            $custompluginoptions[$custom_id] = !empty($pagesort_arr['ignore_excerpt_mode']); //SD342
          }
          $pluginids[$i]  = $pagesort_arr['pluginid'];
          $pluginname[$i] = $pagesort_arr['displayname'];
          $pluginpath[$i] = $pagesort_arr['pluginpath'];

          if($isCustom)
          {
            $custom_idx++;
          }
        }
      }
    }
  } //for

  // Rewrite cache file (if enabled)
  if(!$skinid_override && $SDCache && $SDCache->IsActive())
  {
    $SDCache->write_var($cache_id, '',
      array('c_categoryid'        => PAGE_ID,
            'c_pluginids'         => $c_pluginids,
            'c_pluginname'        => $c_pluginname,
            'c_pluginpath'        => $c_pluginpath,
            'c_customplugin_ids'  => $c_customplugin_ids,
            'c_customplugin'      => $c_customplugin,
            'c_custompluginfile'  => $c_custompluginfile,
            'c_custompluginoptions' => $c_custompluginoptions), true);
  }
}

// do some cleanup now...
unset($extra, $admin_menu_arr, $pluginid, $cache_file, $cache_id, $cachestamp,
      $get_pagesort, $pagesort_arr, $isCustom, $IsCached, $c_pluginids,
      $c_pluginname, $c_pluginpath, $c_customplugin_ids, $c_customplugin,
      $c_custompluginfile, $c_custompluginoptions, $args_pos, $do301, $tmp,
      $last_valid_page, $last_valid_var, $last_var, $last_var_org, $pagesort);


// #############################################################################
// PROCESS PLUGIN HEADERS (and optionally admin menu links)
// #############################################################################
$edit_plugin_link_arr = array();

// Preset some frequently used variables to lessen array searches
$user_custompluginviewids = $userinfo['custompluginviewids'];
$user_pluginviewids       = $userinfo['pluginviewids'];

// SD313: Loop through plugin slots to get extra headers from plugins (header.php)
// and re-evaluate view permissions if cached
$custom_idx = 0;
$plugin_real_count = 0;
for($current_plugin_index = 0; $current_plugin_index < $design_maxplugins; $current_plugin_index++)
{
  $pluginid = isset($pluginids[$current_plugin_index]) ? (string)$pluginids[$current_plugin_index] : '1';
  $isCustom = (substr($pluginid,0,1)=='c');
  $current_plugin_path = isset($pluginpath[$current_plugin_index]) ? (string)$pluginpath[$current_plugin_index] : '';

  // Access is granted based on plugin view permissions:
  $HasAccess = ($current_plugin_path == EMPTY_PLUGIN_PATH) ||
               (($current_plugin_path == CUSTOMPLUGIN_PATH) && !empty($user_custompluginviewids) &&
                 @in_array(substr($pluginid,1,5), $user_custompluginviewids)) ||
               (($current_plugin_path != CUSTOMPLUGIN_PATH) && !empty($user_pluginviewids) &&
                 @in_array($pluginid, $user_pluginviewids));

  if($ipsum_override) //SD371: do nottin' for lorem ipsum test ;)
  {
    $pluginname[$current_plugin_index] = 'Lorem Ipsum '.($current_plugin_index+1);
    $pluginids[$current_plugin_index]  = '1';
    $pluginpath[$current_plugin_index] = '';
  }
  else
  {
    // Note: cached page contains ALL (custom) plugins, so IF plugins are loaded
    // from cache, reset all plugins for which the current user does not have permission!
    if(isset($current_plugin_path) && !$HasAccess && ($current_plugin_path != EMPTY_PLUGIN_PATH))
    {
      if($isCustom)
      {
        $custom_id = $customplugin_ids[$custom_idx];
        $customplugin[$custom_id] = '';
        $custompluginfile[$custom_id] = '';
        $custompluginoptions[$custom_id] = false; //SD342
        $pluginname[$current_plugin_index] = '';
      }
      else
      {
        $pluginids[$current_plugin_index]  = '1';
        $pluginname[$current_plugin_index] = '';
        $pluginpath[$current_plugin_index] = EMPTY_PLUGIN_PATH;
      }
    }
    if($isCustom)
    {
      $custom_idx++;
    }

    // Check plugin-specific "header.php" file to allow inclusion of e.g. additional
    // CSS or JavaScript files into the header by sd_adder_head() calls:
    // Note: ONLY for main- and downloaded plugins!
    if($HasAccess && !$isCustom && isset($current_plugin_path) &&
       ($current_plugin_path != EMPTY_PLUGIN_PATH))
    {
      $plugin_real_count++;
      $headerfile = ROOT_PATH . dirname($pluginpath[$current_plugin_index]).'/header.php';
      if(is_file($headerfile) && file_exists($headerfile))
      {
        $pluginid = $pluginids[$current_plugin_index];
        @include($headerfile);
      }
    }
  }
  unset($HasAccess);

  // This code is only active for SD3 skins AND if the following constant is true
  // (best in admin/branding.php or alternatively in includes/config.php)
  if(!$ipsum_override && ($theme_arr['skin_engine'] == 2) &&
     defined('DISPLAY_PLUGIN_ADMIN_SHORTCUTS') && DISPLAY_PLUGIN_ADMIN_SHORTCUTS)
  {
    if(substr($pluginids[$current_plugin_index], 0, 1) == 'c')
    {
      if(@in_array(substr($pluginids[$current_plugin_index], 1), $userinfo['custompluginadminids']))
      {
        $edit_plugin_link_arr[$current_plugin_index] =
          '<a target="_blank" href="admin/plugins.php?action=display_custom_plugin_form&amp;custompluginid=' .
          substr($pluginids[$current_plugin_index], 1) .
          ($mainsettings_enablewysiwyg ? '&amp;load_wysiwyg=1' : '') .
          '">' . IMAGE_EDIT . '</a>';
      }
    }
    else
    {
      if(@in_array($pluginids[$current_plugin_index], $userinfo['pluginadminids']))
      {
        if($pluginids[$current_plugin_index] > 2)
        {
          $edit_plugin_link_arr[$current_plugin_index] =
            ' - <a target="_blank" href="admin/view_plugin.php?pluginid=' .
            $pluginids[$current_plugin_index] .
            ($mainsettings_enablewysiwyg ? '&amp;load_wysiwyg=1' : '').
            '">' . IMAGE_EDIT . ' View Plugin</a>';
        }
      }
    }
  }
} //for

// another cleanup
unset($pluginid,$isCustom,$custom_id,$current_plugin_index,$current_plugin_path,
      $headerfile,$user_custompluginviewids,$user_pluginviewids,$plugin_header);

// SD313: $ExtraHeader receives complete output of extra plugin headers!
// This also used in "legacy_skin.php" with SD313:
$ExtraHeader .= '
<script type="text/javascript">
//<![CDATA[
var sdurl = "'. SITE_URL . '";';

$HeaderJS = '';
if(!empty($article_arr['articleid']))
{
  $HeaderJS .= '
  var article_offset = jQuery("a[name=p'.$article_arr['pluginid'].'_'.$article_arr['articleid'].']").offset();
  if(article_offset) { window.scrollTo(0,article_offset.top); }';
}
//SD342: ajax-loading for custom plugin pagination
if(!empty($customplugin_ids) && !empty($mainsettings_enable_custom_plugin_paging) &&
   !empty($mainsettings_enable_custom_plugin_ajax))
{
  $HeaderJS .= '
  jQuery(document).delegate("div.cp-paging a","click",function(e){
    e.preventdefault;
    var la_page = $(this).attr("href");
    var paramrex = /c(\d+)-page=(\d+)$/i;
    paramrex.exec(la_page);
    if(RegExp.$1 > 1 && RegExp.$2 > 0) {
      jQuery("div#c"+RegExp.$1+"-content").load(sdurl+"includes/ajax/sd_ajax_customplugins.php?catid='.PAGE_ID.'&amp;cid="+RegExp.$1+"&amp;page="+RegExp.$2);
      return false;
    }
    return true;
  });
';
}

if($HeaderJS)
{
  $ExtraHeader .= '
if(typeof(jQuery) !== "undefined") {
jQuery(document).ready(function() {
'.$HeaderJS.'
});
}
';
}
unset($HeaderJS);

$ExtraHeader .= '
//]]>
</script>';


//SD341: "Canonical tag" for articles with URL params present
if( $mainsettings_modrewrite && isset($article_arr) && @is_array($article_arr) &&
    !empty($article_arr['articleid']) && strlen($article_arr['seo_title']))
{
  $article_link = RewriteLink('index.php?categoryid='.$article_arr['categoryid']);
  $article_link = preg_replace('#'.SD_QUOTED_URL_EXT.'$#', '/' . $article_arr['seo_title'] .
                               $mainsettings_url_extension, $article_link);
  $ExtraHeader = '<link rel="canonical" href="'.$article_link.'" />'.$ExtraHeader;
}

// Include "Markitup" as BBCode-editor - if enabled - which is at least
// used by COMMENTS and Forum plugin etc.
if(!empty($mainsettings_allow_bbcode))
{
  if(defined('ENABLE_MINIFY') && ENABLE_MINIFY)
    sd_header_add(array('js' => array(SD_INCLUDE_PATH.'min/index.php?g=bbcode')));
  else
    sd_header_add(array('js' => array(SD_JS_PATH . 'markitup/markitup-full.js')));
}

//SD343: backup meta before header flushing
$sd_meta_data = ''; //SD370
if(isset($sd_header['meta']) && is_array($sd_header['meta']) && count($sd_header['meta']))
{
  $sd_header_meta = $sd_header['meta'];
  unset($sd_header['meta']);
  foreach($sd_header_meta as $k => $v)
  {
    if(empty($v)) continue;
    switch($k)
    {
      case 'description': $mainsettings_metadescription = $sd_header_meta['description']; break;
      case 'keywords': $mainsettings_metakeywords = $sd_header_meta['keywords']; break;
      case 'title':
        //SD360: restore original website title before processing meta array;
        // obey the title order as specified in main settings as well
        $mainsettings_websitetitle = $mainsettings_websitetitle_original;
        $tmp_title = strlen($v) ? sd_unhtmlspecialchars(strip_tags($v)) : false;
        switch($mainsettings_title_order)
        {
          case 0: $mainsettings_websitetitle .= $tmp_title ? ($mainsettings_title_separator.$tmp_title) : '';
                  break;
          case 1: $mainsettings_websitetitle = $mainsettings_categorytitle?$mainsettings_websitetitle:SD_CATEGORY_TITLE_RAW.$mainsettings_title_separator.$mainsettings_websitetitle;
                  $mainsettings_websitetitle .= $tmp_title ? $tmp_title : '';
                  break;
          case 2: $mainsettings_websitetitle = $tmp_title ? $tmp_title : ''; break;
          case 3: $mainsettings_websitetitle = $tmp_title ? ($mainsettings_websitetitle):$mainsettings_websitetitle; break;
          case 4: $mainsettings_websitetitle = $tmp_title ? (SD_CATEGORY_TITLE_RAW):$mainsettings_websitetitle; break;
          case 5: $mainsettings_websitetitle = $tmp_title ? (SD_CATEGORY_TITLE_RAW.$mainsettings_title_separator.$mainsettings_websitetitle_original):$mainsettings_websitetitle; break;
          case 6: $mainsettings_websitetitle = $tmp_title ? ($mainsettings_websitetitle_original.$mainsettings_title_separator.SD_CATEGORY_TITLE_RAW):$mainsettings_websitetitle; break;
        }
        unset($tmp_title);
        break;
      case 'title_suffix': $mainsettings_websitetitle .= $sd_header_meta['title_suffix']; break;
      case 'description_suffix': $mainsettings_metadescription .= $sd_header_meta['description_suffix']; break;
      default: $sd_meta_data .= '<meta name="'.$k.'" content="'.$v.'" />'."\n";
    }
  }
}

//SD370: added "meta_prop" array for OpenGraph properties
$current_page = ($secure ? 'https://' : 'http://') . $server_name;
if($server_port && (($secure && $server_port <> 443) || (!$secure && $server_port <> 80)))
{
  if(strpos($server_name, ':') === false) $current_page .= ':' . $server_port;
}
if(Is_Ajax_Request())
  $current_page .= $uri;
else
  $current_page .= str_replace('&','&amp;', $uri);

if(!isset($sd_header['meta_prop']) || !is_array($sd_header['meta_prop']))
  $sd_header_meta_prop = array();
else
  $sd_header_meta_prop = $sd_header['meta_prop'];
if(!isset($sd_header_meta_prop['og:url']))
  $sd_header_meta_prop['og:url'] = $current_page;
if(!isset($sd_header_meta_prop['og:title']))
  $sd_header_meta_prop['og:title'] = sd_substr($mainsettings_websitetitle,0,150);
if(!isset($sd_header_meta_prop['og:type']))
  $sd_header_meta_prop['og:type'] = 'website';
if(!isset($sd_header_meta_prop['og:site_name']))
  $sd_header_meta_prop['og:site_name'] = $mainsettings_websitetitle_original;
if(!isset($sd_header_meta_prop['og:description']))
  $sd_header_meta_prop['og:description'] = str_replace(array("\r\n", "\r", "\n"), ' ', $mainsettings_metadescription);
if(!isset($sd_header_meta_prop['og:locale']))
  $sd_header_meta_prop['og:locale'] = $mainsettings_lang_region;

if(!isset($sd_header_meta_prop['twitter:card']))
  $sd_header_meta_prop['twitter:card'] = 'summary';
#$sd_header_meta_prop['twitter:domain'] = '';
if(!isset($sd_header_meta_prop['twitter:url']))
  $sd_header_meta_prop['twitter:url'] = $sd_header_meta_prop['og:url'];
if(!isset($sd_header_meta_prop['twitter:title']))
  $sd_header_meta_prop['twitter:title'] = $sd_header_meta_prop['og:title'];
if(!isset($sd_header_meta_prop['twitter:description']))
  $sd_header_meta_prop['twitter:description'] = $sd_header_meta_prop['og:description'];
if(isset($sd_header_meta_prop['og:image']) && !isset($sd_header_meta_prop['twitter:image:src']))
  $sd_header_meta_prop['twitter:image:src'] = $sd_header_meta_prop['og:image'];

foreach($sd_header_meta_prop as $k => $v)
{
  $sd_meta_data .= '<meta property="'.$k.'" content="'.$v.'" />'."\n";
}

unset($sd_header_meta,$sd_header_meta_prop,$sd_header['meta_prop']);
$ExtraHeader .= sd_header_flush(false);

// ******* Include JS for Ajax-rating *******
$ExtraHeader .= GetRatingsHandlingJS();

// ############################################################################
// SD343: Check for module "onpageheader" events to add to the page header:
if( defined('SD_MODULES_PATH') && !empty($sd_modules->Module_Events) &&
    isset($sd_modules->Module_Events[SD_EVENT_HEADER]) )
{
  foreach($sd_modules->Module_Events[SD_EVENT_HEADER] as $header_proc => $enabled)
  {
    if(is_callable($header_proc) && !empty($enabled))
    {
      $ExtraHeader .= $header_proc();
    }
  }
  unset($header_proc,$enabled);
}


// ############################################################################
// LOAD LAYOUT AND BUILD HOVER MENU (IF NEEDED)
// ############################################################################
$category_meta = $DB->query("SELECT `metadescription`,`metakeywords` FROM `sd_categories` WHERE `categoryid`=%d",$categoryid);
$cat_meta = $DB->fetch_array($category_meta);

$menu_header = '';
$cms_head_include =
    '<base href="' . SITE_URL . "\" /><!--[if IE]></base><![endif]-->\n"
  . '<meta http-equiv="Content-Type" content="text/html;charset=' . CHARSET . "\" />\n"
  . '<title>' . $mainsettings_websitetitle . "</title>\n"
  . '<meta name="description" content="' . htmlspecialchars($cat_meta['metadescription']) . "\" />\n"
  . '<meta name="keywords" content="' . htmlspecialchars($cat_meta['metakeywords']) . "\" />\n"

  . '<meta name="title" content="' . str_replace('"','&quot;',strip_tags(sd_unhtmlspecialchars($mainsettings_websitetitle))) . '" />'."\n"
  . '<meta name="generator" content="' . str_replace('"','&quot;',strip_tags(sd_unhtmlspecialchars(PRGM_NAME))) . '" />'."\n"
  . $rss_link
  . '<link rel="stylesheet" type="text/css" href="'.SITE_URL.'css.php?pageid='.PAGE_ID.
  (SD_MOBILE_ENABLED?'&amp;mobile=1':'').
  ($skinid_override?'&amp;skinid='.$skinid_override:'').
  '&amp;v='.str_replace('.','',PRGM_VERSION).'" />'."\n". load_framework('fontawesome') ."\n" . load_framework('bootstrap') . $sd_meta_data;
  
  
unset($sd_meta_data);

//SD370: include jQuery migrate JS for v1.9+; optionally jQuery mobile?
$sd_jqery_migrate = '<script type="text/javascript" src="'.SITE_URL.'includes/javascript/jquery-migrate-1.2.1.min.js"></script>'."\n";
if(SD_MOBILE_ENABLED)
{
  #NOT YET ENABLED/INCLUDED!
  #$sd_jqery_migrate .= '<script type="text/javascript" src="'.SITE_URL.'includes/javascript/jquery.mobile-1.3.2.min.js"></script>'."\n";
}

//SD360: make sure to include jQuery in CMS_HEAD_NOMENU
$sd_jquery_link = "\n".'<script type="text/javascript" src="'.SITE_URL.'includes/javascript/'.JQUERY_FILENAME.'"></script>'."\n";
$minify_header = '';
if(defined('JQUERY_GA_CDN') && (strlen(JQUERY_GA_CDN))) //SD343
{
  $cms_head_include .= "\n".'<script type="text/javascript" src="'.JQUERY_GA_CDN.'"></script>'."\n".$sd_jqery_migrate;
}
else
{
  if(defined('ENABLE_MINIFY') && ENABLE_MINIFY)
    $minify_header = "\n".'<script type="text/javascript" src="'.SITE_URL.'includes/min/index.php?g=jq"></script>';
  else
    $cms_head_include .= $sd_jquery_link.$sd_jqery_migrate;
}
//SD332: new for CMS_HEAD_NOMENU: head excluding menu JS
$cms_head_nomenu = $cms_head_include . $sd_jquery_link.$sd_jqery_migrate.$ExtraHeader;


// is there a hover menu involved?
$HoverNeeded = isset($pages_parents_md_arr)
               ? (count($pages_parents_md_arr) > 1)
               : $DB->query_first('SELECT categoryid FROM {categories} WHERE parentid != 0 LIMIT 1');
if(!empty($HoverNeeded))
{
  $menu_header = '';
  //SD322: preliminary minify support
  if(defined('ENABLE_MINIFY') && ENABLE_MINIFY)
  {
    $minify_header .="\n".'<script type="text/javascript" src="'.SITE_URL.'includes/min/index.php?g=menu"></script>';
  }
  else
  {
    $menu_header = "\n" .
      '<script type="text/javascript" src="'.SITE_URL.'includes/javascript/superfish/js/jquery.hoverIntent.min.js"></script>' . "\n"
    . '<script type="text/javascript" src="'.SITE_URL.'includes/javascript/superfish/js/supersubs.js"></script>' . "\n"
    . '<script type="text/javascript" src="'.SITE_URL.'includes/javascript/superfish/js/superfish.js"></script>' . "\n"
    . '<script type="text/javascript" src="'.SITE_URL.'includes/javascript/superfish/js/jquery.bgiframe.min.js"></script>' . "\n";
  }
  if(SD_MOBILE_ENABLED) //SD370
  {
    $menu_header = #"\n" .'<script type="text/javascript" src="'.SITE_URL.'includes/javascript/jquery.hoverAccordion.min.js"></script>'.
                   "\n".$mainsettings_mobile_menu_javascript;
  }
  else
  {
    $menu_header .= "\n".$mainsettings_frontpage_menu_javascript;
  }
}

  $languages_logo ='<a href="/'.$user_language.'"><img src="/images/Header/logo.png" class="img-responsive" alt="Safeink - insure your payment"></a>';
  $languages_logo_footer ='<a href="/'.$user_language.'"><img src="/images/Footer/logo-footer.png" class="img-responsive" alt="Safeink - insure your payment"></a>';



$lang_values = array(
  'en'=>'English',
  'fr'=>'Franch',
  'de'=>'Germany',
  'ru'=>'Russian',
  'il'=>'Hebrew',
);



$cms_head_user_button = '<ul>
                            <li style="position: relative;">
                                <a href="javascript:;" id="language_button"><img src="/images/Icon/languages/24/'.$user_language.'-'.$user_language.'.png"> '.$lang_values[$user_language].' <i class="fa fa-angle-down" aria-hidden="true"></i></a>
                                <ul class="language_submenu">
                                    <li><a href="/en"><img src="/images/Icon/languages/24/en-en.png"> '.$lang_values['en'].'</a></li>
                                    <li><a href="/fr"><img src="/images/Icon/languages/24/fr-fr.png"> '.$lang_values['fr'].'</a></li>
                                    <li><a href="/de"><img src="/images/Icon/languages/24/de-de.png"> '.$lang_values['de'].'</a></li>
                                    <li><a href="/ru"><img src="/images/Icon/languages/24/ru-ru.png"> '.$lang_values['ru'].'</a></li>
                                    <li><a href="/il"><img src="/images/Icon/languages/24/il-il.png"> '.$lang_values['il'].'</a></li>
                                </ul>
                            </li>';
if(isset($userinfo['userid']) AND $userinfo['userid'] !=''){
  $cms_head_user_button .= '<li style="position: relative;">
                                <a href="javascript:;" id="user_button" class="ot-btn btn-rounded btn-main-color icon-btn-left"><i class="fa fa-user" aria-hidden="true"></i> '.$userinfo['displayname'].'</a>
                                <ul class="user_submenu">
                                    <li><a href="/'.$user_language.'/user-profile"><i class="fa fa-user" aria-hidden="true"></i> Profile</a></li>
                                    <li><a href="/'.$user_language.'/payments"><i class="fa fa-money" aria-hidden="true"></i> Payments</a></li>
                                    <li><a href="/'.$user_language.'/login?logout=1"><i class="fa fa-sign-out" aria-hidden="true"></i> Log out</a></li>
                                </ul>
                            </li>';
}
else{
  $cms_head_user_button .= '
                <li>
                    <a href="/'.$user_language.'/login" class="ot-btn btn-rounded btn-orange-color icon-btn-left"><i class="fa fa-key" aria-hidden="true"></i> SIGN IN</a>
                </li>
                <li>
                    <a href="/'.$user_language.'/register" class="ot-btn btn-rounded btn-hightlight-color icon-btn-left"><i class="fa fa-sign-in" aria-hidden="true"></i> SIGN UP</a>
                </li>
            </ul>';
}
$cms_head_user_button .= '</ul>';
$cms_head_include .= $minify_header.$menu_header.$ExtraHeader;

// Add reCaptcha 2.0 Functions
  if($mainsettings_captcha_method == 1)
  {
	  $cms_head_include .= $captcha->writeHeader();
  }

//SD370: init all skinvars, regardless of skin engine:
$sd_cache['skinvars']['HEADER'] = '';
$sd_cache['skinvars']['MOBILE_HEADER'] = '';
$sd_cache['skinvars']['FOOTER'] = '';
$sd_cache['skinvars']['MOBILE_FOOTER'] = '';
$sd_cache['skinvars']['MOBILENAVIGATION'] = ''; //SD370
$sd_cache['skinvars']['BREADCRUMB'] = '';
$sd_cache['skinvars']['NAVIGATION'] = '';
$sd_cache['skinvars']['NAVIGATION_BOTTOM_ONLY'] = '';
$sd_cache['skinvars']['NAVIGATION_BOTTOM_ONLY_NOMENU'] = '';
$sd_cache['skinvars']['NAVIGATION_TOP_ONLY'] = '';
$sd_cache['skinvars']['NAVIGATION_TOP_ONLY_NOMENU'] = '';
$sd_cache['skinvars']['NAVIGATION_TOPLEVEL'] = '';
$sd_cache['skinvars']['NAVIGATION_TOPLEVEL_NOMENU'] = '';
$sd_cache['skinvars']['SUBNAVIGATION'] = '';
$sd_cache['skinvars']['SIBLINGPAGES'] = '';
$sd_cache['skinvars']['LOGO'] = '';
$sd_cache['skinvars']['CMS_HEAD_INCLUDE'] = '';
$sd_cache['skinvars']['CMS_HEAD_NOMENU'] = '';
$sd_cache['skinvars']['CMS_HEAD_NOMENU'] = '';
$sd_cache['skinvars']['CMS_HEAD_USER_BUTTON'] = '';
$sd_cache['skinvars']['COPYRIGHT'] = '';
$sd_cache['skinvars']['ARTICLE_TITLE'] = '';
$sd_cache['skinvars']['PAGE_TITLE'] = '';
$sd_cache['skinvars']['PAGE_NAME'] = '';
$sd_cache['skinvars']['PAGE_HTML_CLASS'] = '';
$sd_cache['skinvars']['PAGE_HTML_ID'] = '';
$sd_cache['skinvars']['REGISTER_PATH'] = '';
$sd_cache['skinvars']['USERCP_PATH'] = '';
$sd_cache['skinvars']['LOSTPWD_PATH'] = '';
$sd_cache['skinvars']['LOGIN_PATH'] = '';

if($theme_arr['skin_engine'] == 2) // SD3-specific, xml-based skin
{
  if(SD_MOBILE_ENABLED) //SD370
    $full_layout = var_export(array($theme_arr['layout'],$theme_arr['mobile_header'],$theme_arr['mobile_footer'],$theme_arr['error_page']),true);
  else
    $full_layout = var_export(array($theme_arr['layout'],$theme_arr['header'],$theme_arr['footer'],$theme_arr['error_page']),true);

  if(stripos($full_layout, '[MOBILENAVIGATION]') !== false) //SD370
  {
    $sd_cache['skinvars']['MOBILENAVIGATION'] = CreateMobileMenu(0, false, false, true, false);
  }
  if(stripos($full_layout, '[NAVIGATION]') !== false)
  {
    $sd_cache['skinvars']['NAVIGATION'] = CreateMenu(0, false, false, true, false);
  }
  if(stripos($full_layout, '[SUBNAVIGATION]') !== false)
  {
    $sd_cache['skinvars']['SUBNAVIGATION'] = CreateMenu(PAGE_ID);
  }
  //SD350: new param "SIBLINGPAGES": display all sibiling pages
  // of the current page (if current page has a parent)
  if((stripos($full_layout, '[SIBLINGPAGES]') !== false) &&
     !empty($pages_md_arr[PAGE_ID]))
  {
    $sd_cache['skinvars']['SIBLINGPAGES'] = CreateMenu($pages_md_arr[PAGE_ID]['parentid']);
  }
  if(stripos($full_layout, '[BREADCRUMB]') !== false)
  {
    $sd_cache['skinvars']['BREADCRUMB'] = CreateBreadcrumb(PAGE_ID);
  }
  if(stripos($full_layout, '[NAVIGATION-TOPLEVEL]') !== false)
  {
    $sd_cache['skinvars']['NAVIGATION_TOPLEVEL'] = CreateMenu(0, true, false, true, false);
  }
  if(stripos($full_layout, '[NAVIGATION-TOPLEVEL-NOMENU]') !== false)
  {
    $sd_cache['skinvars']['NAVIGATION_TOPLEVEL_NOMENU'] = CreateMenu(0, true, true, true, false);
  }
  if(stripos($full_layout, '[NAVIGATION-TOP-ONLY]') !== false)
  {
    $sd_cache['skinvars']['NAVIGATION_TOP_ONLY'] = CreateMenu(0, true, false, true, false);
  }
  if(stripos($full_layout, '[NAVIGATION-TOP-ONLY-NOMENU]') !== false)
  {
    $sd_cache['skinvars']['NAVIGATION_TOP_ONLY_NOMENU'] = CreateMenu(0, true, true, true, false);
  }
  if(stripos($full_layout, '[NAVIGATION-BOTTOM-ONLY]') !== false)
  {
    $sd_cache['skinvars']['NAVIGATION_BOTTOM_ONLY'] = CreateMenu(0, true, false, false, true);
  }
  if(stripos($full_layout, '[NAVIGATION-BOTTOM-ONLY-NOMENU]') !== false)
  {
    $sd_cache['skinvars']['NAVIGATION_BOTTOM_ONLY_NOMENU'] = CreateMenu(0, true, true, false, true);
  }
  unset($full_layout);
  //SD350: add all missing replacements to "skinvars" cache:
  $sd_cache['skinvars']['HEADER'] = $theme_arr['header'];
  $sd_cache['skinvars']['FOOTER'] = $theme_arr['footer'];
  $sd_cache['skinvars']['MOBILE_HEADER'] = $theme_arr['mobile_header']; //SD370
  $sd_cache['skinvars']['MOBILE_FOOTER'] = $theme_arr['mobile_footer']; //SD370
  $sd_cache['skinvars']['LOGO'] = $mainsettings_currentlogo;
  $sd_cache['skinvars']['LANGUAGES_LOGO'] = $languages_logo;
  $sd_cache['skinvars']['LANGUAGES_LOGO_FOOTER'] = $languages_logo_footer;
  $sd_cache['skinvars']['CMS_HEAD_INCLUDE'] = $cms_head_include;

  $sd_cache['skinvars']['CMS_HEAD_NOMENU'] = $cms_head_nomenu;
  $sd_cache['skinvars']['CMS_HEAD_USER_BUTTON'] = $cms_head_user_button;
  $sd_cache['skinvars']['LANGUAGES_TOP_NAVIGATION'] = $languages_top_navigation;
  $sd_cache['skinvars']['COPYRIGHT'] = $copyright;
  $sd_cache['skinvars']['ARTICLE_TITLE'] = (isset($article_arr['title']) && strlen($article_arr['title']) ? strip_tags($article_arr['title']) : ''); //SD370
  $sd_cache['skinvars']['PAGE_TITLE'] = SD_CATEGORY_TITLE_RAW;
  $sd_cache['skinvars']['PAGE_NAME'] = $theme_arr['categoryname'];
  $sd_cache['skinvars']['PAGE_HTML_CLASS'] = $theme_arr['html_class']; //SD370
  $sd_cache['skinvars']['PAGE_HTML_ID'] = $theme_arr['html_id']; //SD370
  $sd_cache['skinvars']['REGISTER_PATH'] = (defined('REGISTER_PATH')?REGISTER_PATH:'');
  $sd_cache['skinvars']['USERCP_PATH'] = (defined('USERCP_PATH')?USERCP_PATH:'');
  $sd_cache['skinvars']['LOSTPWD_PATH'] = (defined('LOSTPWD_PATH')?LOSTPWD_PATH:'');
  $sd_cache['skinvars']['LOGIN_PATH'] = (defined('LOGIN_PATH')?LOGIN_PATH:'');
//echo $cms_head_user_button; exit;
  //SD350: replacement encapsulated in new function "sd_DoSkinReplacements"
  $theme_arr['layout'] = sd_DoSkinReplacements($theme_arr['layout']);
  $current_layout = &$theme_arr['layout'];

  // Cleanup
  unset($menu_header,$cms_head_include,$cms_head_nomenu,$ExtraHeader,
        $c_categoryid,$last_var,$custom_idx,$HoverNeeded,$user_has_categories,
        $sub_folders,$url_variables,$get_designs,$active_skin_id,$args_pos,$pid);

  // cycle through all the plugins
  // step 1: check if each plugin has plugin_name tags
  // if so then replace the tags with the plugin name
  // step 2: remove the plugin tags
  for($current_plugin_index = 0; $current_plugin_index < $design_maxplugins; $current_plugin_index++)
  {
    // STEP 1: does current <plugin></plugin> have plugin_name tags?
    // search for starting plugin tag
    $plugin_open_tag_pos = strpos($current_layout, '<plugin>');

    // search for ending plugin tag
    $plugin_close_tag_pos = strpos($current_layout, '</plugin>');

    // okay now we know where current plugin is located, does it have a plugin name?
    // last @argument for substr_count = the length of characters to grab
    // (which happens to be "plugin_close_tag_pos - plugin_open_tag_pos +9"
    // the + 13 represents the extra "</plugin>" characters
    // SD322: replace substr_count with substr/strpos and $tmp variable due to
    // problems with previously used "substr_count"
    $tmp = substr($current_layout, $plugin_open_tag_pos, ($plugin_close_tag_pos - $plugin_open_tag_pos + 13));
    if(@strpos($tmp, '<plugin_name>') !== false)
    {
      // plugin name found, wonderful, now lets remove the plugin name tags
      if(isset($pluginname[$current_plugin_index]) && strlen($pluginname[$current_plugin_index]))
      {
        // there is a plugin name, so only remove the FIRST pair of plugin_name tags
        // we are left with something like <h1>[PLUGIN_NAME]</h1>
        $current_layout = preg_replace("'<plugin_name>'",  '', $current_layout, 1);
        $current_layout = preg_replace("'</plugin_name>'", '', $current_layout, 1);
      }
      else
      {
        // There is no plugin name, so remove the plugin_name tags and all code between
        // them and then replace it with [PLUGIN_NAME], which eventually will be
        // replaced by an empty string.
        $current_layout = preg_replace("'<plugin_name>(.*?)</plugin_name>'ms", '[PLUGIN_NAME]', $current_layout, 1);
      }

      // Now check if the variable <PLUGIN_NAME> actually exists.
      // For example, a skin author might have written this code:
      // <plugin_name><h1>hello world</h1></plugin_name>
      // So lets definitely make sure it exists before trying to replace it
      // and then replace it with a real name.
      // SD322: replace substr_count with substr/strpos and $tmp variable
      $tmp = substr($current_layout, $plugin_open_tag_pos, ($plugin_close_tag_pos - $plugin_open_tag_pos + 13));
      if(@strpos($tmp, '[PLUGIN_NAME]') !== false)
      {
        $ptitle = isset($pluginname[$current_plugin_index]) ? $pluginname[$current_plugin_index] : '';
        // it was found, lets replace it with the plugin name
        $current_layout = preg_replace('/\[PLUGIN_NAME\]/', $ptitle, $current_layout, 1);
      }
    }
    unset($tmp,$ptitle);

    // STEP 2: remove <plugin> tags
    if($pluginpath[$current_plugin_index] == EMPTY_PLUGIN_PATH)
    {
      // plugin is empty, remove the plugin tags and everything in between them
      // then replace it with [PLUGIN]
      $current_layout = preg_replace("'<plugin>(.*?)</plugin>'ms", '[PLUGIN]', $current_layout, 1);
    }
    else
    {
      // A plugin exists, so remove the plugin tags (a single pair)
      $current_layout = preg_replace("'<plugin>'", '', $current_layout, 1);
      $current_layout = preg_replace("'</plugin>'", '', $current_layout, 1);
    }
  }

  // SD313 - clean up all unused variables
  unset($bAllowed, $category, $column_name, $column_value,
        $current_plugin_index, $get_plugins, $headerfile, $i,
        $replace_search, $user_arr);

  //SD330: check for placeholders in skin layout
  // Ex.: [GetImage:0:5020,w=200,h=100]
  // Functions must be prefixed by "Layout", here: "LayoutGetImage()"
  // must be defined in e.g. functions_frontend.php
  $current_layout = preg_replace_callback('/\[([^\s\]:]*):([^\s\]]*):([^\s\]]*)\]/',
                                          'CheckLayoutReplacements', $current_layout);

  //SD370: skin variable replacements
  $current_layout = sd_DoSkinReplacements($current_layout);

  // okay the layout is ready to be exploded, lets split it up into an array
  $layout_arr = @explode('[PLUGIN]', $current_layout);
  unset($current_layout);

  // SD313: shouldn't we add this to categories table since many plugins
  // use this for sizing input fields correctly??
  $inputsize = '30'; // legacy - default "input" width (characters)

  $loremipsum = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed
  do eiusmod tempor incididunt ut labore et dolore magna aliqua.';

  // display layout
  $current_page_url = RewriteLink('index.php?categoryid='.PAGE_ID); //SD342
  if($usePlugins = (!empty($layout_arr) && is_array($layout_arr)))
  for($layout_index = 0; $layout_index < count($layout_arr); $layout_index++)
  {
    // "$layout_arr[$layout_arr]" contains skin code between previous (or start of skin)
    // and the currently loading plugin, which is eval'ed to both output skin HTML
    // as well as process included PHP code:
    $tmp = trim($layout_arr[$layout_index]);
    if($usePlugins && strlen($tmp))
    {
      $layout_arr[$layout_index] = ' ?>' . $tmp . '<?php ';
      @eval($layout_arr[$layout_index]);
    }

    if($layout_index < $design_maxplugins)
    {
      if($ipsum_override) //SD371: only display lorem ipsum text ;)
      {
        echo $loremipsum;
      }
      else
      if(!empty($pluginpath[$layout_index]))
      {
        if(($pluginpath[$layout_index] != EMPTY_PLUGIN_PATH) &&
           is_file($pluginpath[$layout_index]))
        {
          $pluginid = $pluginids[$layout_index]; // SD313 - reintroducing $pluginid
          @include($pluginpath[$layout_index]);
        }
      }
    }
  }//for
}
else
{
  // Load legacy skin
  $ExtraHeader .=
    '<link rel="stylesheet" type="text/css" href="'.SITE_URL.'css.php?pageid='.
    PAGE_ID.(SD_MOBILE_ENABLED?'&amp;mobile=1':'').'&amp;v='.
    str_replace('.','',PRGM_VERSION).'" />'."\n";

  $mainsettings['skinheader'] = '';
  $logo = $mainsettings_currentlogo;
  $design = &$theme_arr;
  include(ROOT_PATH . 'includes/legacy_skin.php');
}

// ############################################################################
// CLOSE CONNECTION
// ############################################################################

if(isset($DB) && $DB->conn) $DB->close();
