<?php
if(!defined('IN_PRGM')) return false;

$articles_js = '';
if(!defined('IN_ADMIN'))
{
  if(!$plugin_folder = sd_GetCurrentFolder(__FILE__)) return false;
  if(!$pluginid = GetPluginIDbyFolder($plugin_folder)) return false;

  if(!isset($sd_instances)) $sd_instances = array();
  @require_once(SD_INCLUDE_PATH . 'class_articles.php');

  global $mainsettings_tag_results_page, $mainsettings_search_results_page, $uri;

  $ap = new ArticlesClass($plugin_folder);

  $article_page_text = '';
  if(($article_page = GetVar('p'.$pluginid.'_start', 0, 'whole_number', false, true)) > 1)
  {
    $article_page_text = $ap->language['meta_page_phrase'];
    $article_page_text = ' '.str_replace('[page]', $article_page, $article_page_text);
  }

  //SD360: detect if search param for Search Engine is present when on
  // search results page to avoid display of results
  if(!empty($mainsettings_search_results_page) &&
     ($categoryid == $mainsettings_search_results_page) )
  {
    $action = GetVar('action', '', 'string');
    $q = GetVar('q', '', 'string');
    if(($action=='search') && !empty($q))
    {
      $ap->SearchTerm = trim(sd_substr(urldecode($q),0,100));
      $ap->doSearch = true;
    }
    unset($action,$q);
  }

  if(!empty($url_variables)&&
     (!empty($mainsettings_tag_results_page) || !empty($mainsettings_search_results_page)) )
  {
    $ap->slug_arr = CheckPluginSlugs($ap->pluginid);
    if(!is_array($ap->slug_arr) || empty($ap->slug_arr['id']))
    {
      $ap->slug_arr = false;
    }

    if(isset($ap->slug_arr) && ($ap->slug_arr!==false) && is_array($ap->slug_arr))
    {
      if(!empty($ap->slug_arr['year']) && Is_Valid_Number($ap->slug_arr['year'],0,1900,2050) &&
         !empty($ap->slug_arr['month']) && Is_Valid_Number($ap->slug_arr['month'],0,1,12) )
      {
        $p2_tmp = strip_tags(str_replace(array('[month]','[year]'),
                               array($sd_months_arr[$ap->slug_arr['month']],
                                     sprintf('%04d',$ap->slug_arr['year'])),
                               $ap->language['article_year_month_head']));
        sd_header_add(array(
          'meta'  => array(
            'title' => ($p2_tmp.' '.$ap->slug_arr['year'].$article_page_text),
            'description' => ($p2_tmp.$article_page_text)
          )));
      }
      else
      if(!empty($ap->slug_arr['key']) && !empty($ap->slug_arr['value']))
      {
        $p2_tmp = strip_tags(str_replace(array('"','&quot;','[tag]'),
                                         array('','',$ap->slug_arr['value']),
                                         $ap->language['article_tags_head']));
        sd_header_add(array(
          'meta'  => array(
            'title' => ($p2_tmp.$article_page_text),
            'description' => ($p2_tmp.$article_page_text)
          )));
      }
      unset($p2_tmp);
    }
  }
  else
  {
    if(strlen($article_page_text))
    {
      //SD343: expand meta data with page as suffix
      sd_header_add(array(
        'meta'  => array(
          'title_suffix' => $article_page_text,
          'description_suffix' => $article_page_text
        )));
    }
    else
    if(isset($article_arr) && is_array($article_arr) && !empty($article_arr['articleid'])) //SD370
    {
      $meta_prop_arr = array();
      $meta_prop_arr['og:title'] = sd_substr(str_replace('"','&quot;',strip_tags(sd_unhtmlspecialchars($article_arr['title']))),0,128);
      if(!empty($article_arr['description']) && strlen(trim($article_arr['description'])))
        $meta_prop_arr['og:description'] = sd_substr(str_replace('"','&quot;',strip_tags(sd_unhtmlspecialchars($article_arr['description']))),0,128);
      else
      if(!empty($article_arr['metadescription']) && strlen(trim($article_arr['metadescription'])))
        $meta_prop_arr['og:description'] = sd_substr(str_replace('"','&quot;',strip_tags(sd_unhtmlspecialchars($article_arr['metadescription']))),0,128);
      $meta_prop_arr['og:site_name'] = $mainsettings_websitetitle;
      $meta_prop_arr['og:type'] = 'article';
      if( $mainsettings_modrewrite && strlen($article_arr['seo_title']))
      {
        $article_link = RewriteLink('index.php?categoryid='.$article_arr['categoryid']);
        $article_link = preg_replace('#'.SD_QUOTED_URL_EXT.'$#', '/' . $article_arr['seo_title'] .
                                     $mainsettings_url_extension, $article_link);
        $meta_prop_arr['og:url'] = $article_link;
      }
      if(!empty($article_arr['datecreated']))
        $meta_prop_arr['article:published_time'] = DisplayDate($article_arr['datecreated'],'c',false);
      if(!empty($article_arr['dateupdated']))
        $meta_prop_arr['article:modified_time'] = DisplayDate($article_arr['dateupdated'],'c',false);
      if(!empty($article_arr['org_author_name']))
        $meta_prop_arr['article:author'] = $article_arr['org_author_name'];
      include_once(SD_INCLUDE_PATH.'class_sd_tags.php');
      if($tmp = SD_Tags::GetPluginTags($ap->pluginid, $article_arr['articleid']))
      if(is_array($tmp) && count($tmp))
        $meta_prop_arr['article:tag'] = implode(',', $tmp);
      if(!empty($article_arr['featuredpath']))
      {
        include_once(SD_INCLUDE_PATH.'class_sd_media.php');
        $imgpath = 'images/featuredpics/'.$article_arr['featuredpath'];
        $sdi = new SD_Image(ROOT_PATH.$imgpath);
        if($sdi->getImageValid())
        {
          $meta_prop_arr['og:image'] = SITE_URL.$imgpath;
          $meta_prop_arr['og:image:width'] = $sdi->getImageWidth();
          $meta_prop_arr['og:image:height'] = $sdi->getImageHeight();
          $meta_prop_arr['og:image:type'] = $sdi->getMimeType();
        }
      }
      unset($tmp,$ext,$sdi);

      sd_header_add(array('meta_prop' => $meta_prop_arr));
    }
  }
  $sd_instances[$pluginid] = $ap;
  unset($article_page_text);
} // end of frontpage

//JS code for frontpage article popup and attachment deletion
$articles_js = '
<script type="text/javascript">
//<![CDATA[
if(typeof(jQuery) !== "undefined"){
jQuery(document).ready(function() {
  '.(defined('IN_ADMIN')?'':GetCeeboxDefaultJS(false,'div#p'.$pluginid.'_container .popup,div.article_container .popup')).'
  jQuery("a.imgdelete").click(function(event) {
    event.preventDefault();
    if(confirm("'.addslashes($sdlanguage['common_attachment_delete_prompt']).'")) {
      var aid = jQuery(this).attr("id");
      var target = jQuery(this).parents("li:first");
      if(target == "undefined") return false;
      jQuery(target).load(jQuery(this).attr("href"), null,
        function(responseText, textStatus){
          if(textStatus=="success" && responseText=="1") {
            jQuery(target).remove();
            alert("'.addslashes($sdlanguage['common_attachment_deleted']).'");
          } else {
            alert("'.addslashes($sdlanguage['common_attachment_delete_failed']).'");
          }
      });
    }
    return false;
  });
});
}
//]]>
</script>
';

sd_header_add(array(
  'other'  => array($articles_js)
));
unset($articles_js);