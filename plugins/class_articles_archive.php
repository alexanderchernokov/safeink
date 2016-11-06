<?php
if(!defined('IN_PRGM')) return;

if(!class_exists('ArticlesArchiveClass'))
{

class ArticlesArchiveClass
{
  private $pluginid = 0;
  public $language = array();
  public $settings = array();

  public function __construct($plugin_folder)
  {
    $this->pluginid = GetPluginIDbyFolder($plugin_folder);
    $this->language = GetLanguage($this->pluginid);
    $this->settings = GetPluginSettings($this->pluginid);
  }

  function DisplayContent()
  {
    global $DB, $categoryid, $sdlanguage, $sdurl, $mainsettings, $mainsettings_modrewrite,
           $mainsettings_url_extension, $pages_md_arr, $plugin_names, $userinfo,
           $sd_months_arr, $sd_tag_value, $sd_tagspage_link; //SD343

    if(empty($this->pluginid)) return false;

    // Get settings from source Latest Articles plugin:
    $src_id = !empty($this->settings['article_plugin_selection']) ? (int)$this->settings['article_plugin_selection'] : 0;
    $src_id = Is_Valid_Number($src_id, 2, 2, 9999);
    if($target_page_id = (!empty($this->settings['article_list_page']) ? (int)$this->settings['article_list_page'] : 0))
    {
      $target_page_id = (!empty($userinfo['categoryviewids']) && in_array($target_page_id,$userinfo['categoryviewids'])) ? (int)$target_page_id: 0;
    }
    if(!$src_id || !isset($plugin_names[$src_id]) || empty($target_page_id)) return false;

    $src_settings = GetPluginSettings($src_id);
    $toggle = '';
    $mode   = empty($this->settings['display_mode']) ? 0 : (int)$this->settings['display_mode'];
    if(!$mode)
    {
      $toggle = '<span style="display: none">&laquo;</span><span>&raquo;</span> ';
    }

    echo '
      <div id="p'.$this->pluginid.'_archive">
      ';

    if($mode > 2)
    {
      if(!empty($userinfo['adminaccess'])) echo '<p>Sorry, the categories list is not yet implemented!</p>';
    }
    else
    {
      $articles = $years = array();
      $extra = '';
      $min_year = 0;
      $prefix = PRGM_TABLE_PREFIX.'p'.$src_id.'_';
      if(empty($this->settings['ignore_current_page'])) //SD351
      {
        if(in_array('p'.$src_id.'_'.'articles_pages', $DB->table_names_arr[$DB->database]))
        {
          $extra = ' AND ((categoryid = '.$categoryid.') OR EXISTS (SELECT 1 FROM '.$prefix.'articles_pages ap'.
                   ' WHERE ap.articleid = news.articleid AND ap.categoryid = '.$categoryid.'))';
        }
        else
        {
          $extra = ' AND (categoryid = '.$categoryid.')';
        }
      }

      if(!empty($this->settings['calendar_years_age']))
      {
        $min_year = (@date('Y') - (int)$this->settings['calendar_years_age']+1);
        $year_boundary = mktime(0, 0, 0, 1, 1, $min_year);
        $extra .= ' AND ((IFNULL(datecreated,0) = 0) OR (datecreated >= '.$year_boundary.')) ';
        $extra .= ' AND ((IFNULL(datestart,0) = 0) OR (datestart >= '.$year_boundary.')) ';
      }
      if(empty($src_settings['ignore_publish_end_date'])) //news plugin v3.5.2 (SD351)
      {
        $extra .= 'AND ((IFNULL(dateend,0) = 0) OR (dateend >= '.TIME_NOW.'))';
      }

      $sql = 'SELECT articleid, categoryid, settings, displayorder, datecreated,
        datestart, dateend, author, title, seo_title,
        IF(IFNULL(datecreated,IFNULL(datestart,0))=0,dateupdated,IFNULL(datecreated,IFNULL(datestart,0))) article_date
        FROM '.$prefix."news news
        WHERE (settings & 2) $extra
        AND ((IFNULL(datestart,0) = 0) OR (datestart < ".TIME_NOW."))
        AND ((IFNULL(access_view,'')='') OR (access_view like '%|".$userinfo['usergroupid']."|%'))
        ORDER BY IF(IFNULL(datecreated,IFNULL(datestart,0))=0,dateupdated,IFNULL(datecreated,IFNULL(datestart,0))) DESC";

      // First iterate through all data and store results in arrays
      $getarticles = $DB->query($sql);
      while($a = $DB->fetch_array($getarticles,null,MYSQL_ASSOC))
      {
        $year = (int)@date('Y', $a['article_date']);
        if(!$min_year || ($year >= $min_year))
        {
          $month = (int)@date('m', $a['article_date']);

          if(!isset($years[$year]))
          {
            $years[$year]['months'] = array();
            $years[$year]['counts'] = 0;
          }

          if(!in_array($month, $years[$year]['months']))
          {
            $years[$year]['months'][] = $month;
          }
          $years[$year]['counts']++;
          $articles[$year][$month][] = $a;
        }
      } //while

      $page_link = RewriteLink('index.php?categoryid='.$target_page_id);
      $quoted_url_ext = preg_quote($mainsettings_url_extension,'#');
      if(SD_TAG_DETECTED && !empty($sd_tag_value['year'])) //SD343
        $current_year = (int)$sd_tag_value['year'];
      else
      {
        $current_year = date("Y");
        //SD343: check if article plugin is loaded, check if it has detected URL params
        global $sd_instances;
        if(isset($sd_instances) && !empty($sd_instances[$src_id]) && is_object($sd_instances[$src_id]) &&
           ($sd_instances[$src_id] instanceof ArticlesClass) &&
           !empty($sd_instances[$src_id]->slug_arr['id']) &&
           !empty($sd_instances[$src_id]->slug_arr['year']))
        {
          $current_year = $sd_instances[$src_id]->slug_arr['year'];
        }
      }
      $output = '';

      // Secondly iterate through arrays to create HTML
      foreach($years as $year_num => $year)
      {
        if($mode!=2)
        {
          echo '
          <div class="year_container">
          <a href="#" class="arc_year">'.$toggle.$year_num.' ('.$year['counts'].')</a><br />
          <ul class="arc_months_list" style="display:'.(($current_year==$year_num)?'block':'none').'">';
        }

        foreach($year['months'] as $month)
        {
          $month = (int)$month;
          if($mode==2)
          {
            $output .= '<option value="'.sprintf('%04d',$year_num).'/'.sprintf('%02d',$month).
                       '">'.$sd_months_arr[$month].' '.sprintf('%04d',$year_num).'</option>';
          }
          else
          {
            echo '<li>
              <div class="month_container month-'.$month.'">
              ';

            if($mode==0)
            {
              // Full Year/Month > Articles listing
              echo '
                <a href="#" class="arc_month">'.$toggle.$sd_months_arr[$month].' ('.count($articles[$year_num][$month]).')</a><br />
                <ul class="arc_articles_list" style="display:none">';

              foreach($articles[$year_num][$month] as $a)
              {
                if($mainsettings_modrewrite && strlen($a['seo_title']))
                {
                  $articlelink = RewriteLink('index.php?categoryid=' . $a['categoryid']);
                  $articlelink = str_replace($mainsettings_url_extension, '/' . $a['seo_title'].
                                             $mainsettings_url_extension, $articlelink);
                }
                else
                {
                  $articlelink = RewriteLink('index.php?categoryid=' . $a['categoryid'] .
                                             '&p'.$src_id.'_articleid='.$a['articleid']);
                }
                echo '
                  <li><a title="'.DisplayDate($a['article_date']).'" href="'.$articlelink.'">'.$a['title'].'</a></li>';
              }
              echo '
                </ul>';
            }
            else
            {
              // Only Year and Month listing with month names being clickable
              if($mainsettings_modrewrite)
              {
                $articlelink = preg_replace('#'.$quoted_url_ext.'$#','/', $page_link).
                               sprintf('%04d',$year_num).'/'.sprintf('%02d',$month);
              }
              else
              {
                $articlelink = RewriteLink('index.php?categoryid=' . $target_page_id .
                                           '&year='.sprintf('%04d',$year_num).'&month='.sprintf('%02d',$month));
              }
              //SD351: attach pluginid if cloned articles plugin
              if(!empty($src_id) && ($src_id != 2))
              {
                #$articlelink .= '?pid='.$src_id;
              }

              echo '<a href="'.$articlelink.'" class="arc_month_link">'.($mode==1?$toggle:'').
                    $sd_months_arr[$month].' ('.count($articles[$year_num][$month]).')</a>';
            }
            echo '</div></li>'; // close month
          }
        }

        if($mode!=2) echo '</ul></div>'; // close year

      } //while

      //SD343: output dropdown box
      if($mode==2)
      {
        echo '
        <select name="p'.$this->pluginid.'_selector">'.$output.
        '</select> <a id="p'.$this->pluginid.'_refresh" title="'.htmlspecialchars($sdlanguage['common_refresh']).
        '" rel="nofollow" style="display:none;" href="'.$sd_tagspage_link.'"><img alt="'.htmlspecialchars($sdlanguage['common_refresh']).
        '" width="16" height="16" src="'.$sdurl.'includes/images/refresh.png" /></a>';
      }

    } //mode 0,1,2

    echo '
      </div>'. // close container
      '<div style="clear: both"> </div>';

  } //DisplayContent

} // end of class

} // DO NOT REMOVE!
