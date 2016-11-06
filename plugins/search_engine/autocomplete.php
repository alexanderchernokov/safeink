<?php
define('IN_PRGM', true);
define('ROOT_PATH', '../../');

// ############################################################################
// INIT PRGM
// ############################################################################
require(ROOT_PATH . 'includes/init.php');

// Tobias, 2012-01-22: permissions check
if(!Is_Ajax_Request() || (empty($userinfo['adminaccess']) &&
   (empty($userinfo['pluginviewids']) || !in_array(2, $userinfo['pluginviewids']))))
{
  exit();
}

// ############################################################################
// BEGIN AUTOCOMPLETE
// ############################################################################

// Get search term, perform security checks
$acstring = urldecode(GetVar('acstring', '', 'string'));
$acstring = trim(sd_substr($acstring,0,100));
if(empty($acstring) || (strlen($acstring) < 2) || empty($userinfo['usergroupid']))
{
  $DB->close();
  exit();
}

$src_id = 2;
$order = ' ORDER BY (settings & 8192) DESC, IF(IFNULL(datecreated,IFNULL(datestart,0))=0,dateupdated,IFNULL(datecreated,IFNULL(datestart,0))) '; # 8192 = display sticky
$where = ' WHERE (settings & 2)'.
         " AND ((IFNULL(access_view,'')='') OR (access_view like '%|".(int)$userinfo['usergroupid']."|%'))".
         ' AND ((datestart = 0) OR (datestart < ' . TIME_NOW . '))'.
         ' AND ((dateend   = 0) OR (dateend   > ' . TIME_NOW . '))'.
         " AND (title LIKE '%".$DB->escape_string($acstring)."%')";
if($acarts = $DB->query('SELECT * FROM '.PRGM_TABLE_PREFIX.'p'.$src_id.'_news '.
                        $where. $order.' LIMIT 10'))
{
  header('Content-type: application/html; charset='.$mainsettings['charset']);
  while($art = $DB->fetch_array($acarts,null,MYSQL_ASSOC))
  {
    // Check permissions for category
    if(empty($userinfo['adminaccess']) &&
       (empty($userinfo['categoryviewids']) || !in_array($art['categoryid'], $userinfo['categoryviewids'])))
    {
      continue;
    }
    // Check to see if SEO is enabled
    if(!empty($mainsettings['modrewrite']) && !empty($art['seo_title']))
    {
      $articlelink = RewriteLink('index.php?categoryid=' . $art['categoryid']);
      $articlelink = str_replace($mainsettings['url_extension'], '/' . $art['seo_title'] . $mainsettings['url_extension'], $articlelink);
    }
    else
    {
      $articlelink = RewriteLink('index.php?categoryid=' . $art['categoryid'] . '&p2_articleid=' . $art['articleid']);
    }
    echo '<li><a href="' . $articlelink . '">' . $art['title'] . '</a></li>';
  }
}
$DB->close();