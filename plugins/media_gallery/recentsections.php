<?php
if(!defined('IN_PRGM')) return;

if(!$plugin_folder = sd_GetCurrentFolder(__FILE__)) return true;
$pluginid = GetPluginIDbyFolder($plugin_folder);

@require_once(ROOT_PATH . 'plugins/'.$plugin_folder.'/gallery_lib.php');

if(isset($sd_instances[$pluginid]) && ($sd_instances[$pluginid] instanceof GalleryBaseClass))
{
  $GalleryBase = & $sd_instances[$pluginid];
}
else
{
  $GalleryBase = new GalleryBaseClass($plugin_folder);
  $sd_instances[$pluginid] = & $GalleryBase;
}

if($GalleryBase->pluginid < 1) return true;

if(!($GalleryBase->IsAdmin || $GalleryBase->IsSiteAdmin || $GalleryBase->IsModerator) &&
   (empty($userinfo['pluginviewids']) || !@in_array($pluginid,$userinfo['pluginviewids'])))
{
  return true;
}

//########################## MOST RECENT SECTIONS 1 ###########################

// Check if the Gallery is visible in the current CATEGORY
if(false === ($mg_cat = GetPluginCategory($pluginid,$categoryid)))
{
  return true;
}

if(!function_exists('MG_RecentSections1'))
{
  function MG_RecentSections1()
  {
    global $DB, $GalleryBase, $mg_cat;

    // Fetch most recently added/updated files
    $DB->result_type = MYSQL_ASSOC;
    if($getsections = $DB->query(
       'SELECT s.sectionid, s.name, s.datecreated'.
       ' FROM '.$GalleryBase->sections_tbl.' s'.
       ' WHERE ((s.sectionid = 1) OR (IFNULL(s.activated,0) = 1)) '.
       $GalleryBase->GroupCheck .
       ' ORDER BY s.datecreated DESC'))
    {
      echo '
      <!-- '.$GalleryBase->pre.' -->
      <div class="gallery_sections_container">
      <ul class="gallery_section">';
      while($section = $DB->fetch_array($getsections,null,MYSQL_ASSOC))
      {
        if(!$GalleryBase->SetSection($section['sectionid'], false) ||
           empty($GalleryBase->section_arr['can_view']))
        {
          continue;
        }
        $GalleryBase->categoryid = $mg_cat;
        $GalleryBase->plugin_page = RewriteLink('index.php?categoryid='.$mg_cat);

        $link = $GalleryBase->RewriteSectionLink($section['sectionid']);
        echo '
        <li><a title="'.(empty($section['datecreated'])?'':DisplayDate($section['datecreated'])).
        '" href="'.$link.'">'.sd_unhtmlspecialchars($section['name']).'</a>'.
        '</li>';
	    }
      echo '
      </ul>
      <div style="clear: both;"></div>
      </div>
      ';
    }
  }
}
MG_RecentSections1();
unset($mg_cat);