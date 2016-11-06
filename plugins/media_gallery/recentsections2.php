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

//########################## MOST RECENT SECTIONS 2 ###########################

// Check if the Gallery is visible in the current CATEGORY
if(false === ($mg_cat = GetPluginCategory($pluginid,$categoryid)))
{
  return true;
}

if(!function_exists('MG_RecentSections2'))
{
  function MG_RecentSections2()
  {
    global $DB, $mainsettings, $mg_cat, $GalleryBase;

    // Fetch most recently added/updated files
    if($getsections = $DB->query(
       'SELECT s.sectionid, s.name, s.imageid, s.datecreated,'.
       " i.media_type, IFNULL(i.folder,'') folder, IFNULL(i.filename,'') filename".#, i.private?
       ' FROM '.$GalleryBase->sections_tbl.' s'.
       ' LEFT JOIN '.$GalleryBase->images_tbl.' i ON i.imageid = s.imageid'.
       ' WHERE ((s.sectionid = 1) OR (IFNULL(s.activated,0) = 1))'.
       $GalleryBase->GroupCheck .
       ' ORDER BY s.datecreated DESC'))
    {
      echo '
      <table class="'.$GalleryBase->pre.'_recentsections">';
      while($section = $DB->fetch_array($getsections))
      {
        $GalleryBase->categoryid = $mg_cat;
        $GalleryBase->plugin_page = RewriteLink('index.php?categoryid='.$GalleryBase->categoryid);
        if(!$GalleryBase->SetSection($section['sectionid'], false) ||
           empty($GalleryBase->section_arr['can_view']))
        {
          continue;
        }

        echo '
        <tr><td><div class="section_inner">';

        $link = $GalleryBase->RewriteSectionLink($section['sectionid']);
        $section_img = '<a href="' . $link . '"><img width="'.$mainsettings['default_avatar_width'].
                       '" alt="'.addslashes($section['name']).'" src="'.$GalleryBase->IMAGEURL;
        if(!empty($section['imageid']) && !empty($section['filename']))
        {
          $folder = $section['folder'];
          $folder .= (strlen($folder) && (sd_substr($folder,strlen($folder)-1,1)!='/') ? '/' : '');
          echo $section_img . $folder . $GalleryBase->TB_PREFIX.$section['filename'].'" /></a>';
        }
        else
        {
          echo $section_img . $GalleryBase->defaultimg.'" /></a>';
        }

        echo '</div></td>
          <td><a title="'.(empty($section['datecreated'])?'':DisplayDate($section['datecreated'])).'" href="'.$link.'">'.sd_unhtmlspecialchars($section['name']).'</a>'.
          #(empty($section['datecreated'])?'':'<br />'.DisplayDate($section['datecreated'])).'
          '</td></tr>';
      }
      echo '</table>
      <div style="clear: both"></div>';
    }
  }
}
MG_RecentSections2();