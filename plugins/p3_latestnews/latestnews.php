<?php
if(!defined('IN_PRGM')) exit();

// ###############################################################################
// LATEST NEWS
// ###############################################################################

function p3_LatestNews()
{
  global $DB, $categoryid, $mainsettings, $mainsettings_modrewrite,
         $mainsettings_url_extension, $pages_md_arr, $userinfo;

  $language = GetLanguage(3);

  // get latest news settings
  $settings    = GetPluginSettings(3);
  $p2_settings = GetPluginSettings(2);
  $GLOBALS['sd_ignore_watchdog'] = true;
  $items_per_page      = $settings['display_limit'];              // how many news articles to display (default 10)
  $targeting           = $settings['page_targeting'];             // only display news from the current category (default no)?
  $includeids          = $settings['pages_to_include'];           // the ID of the categories you want to include (only works if targeting is set to off)
  $sorting             = $settings['sorting'];                    // sort by author, title, newest first, oldest first etc (see the news plugin)
  $showcategoryname    = $settings['display_page_name'];          // display categoryname to the right of each news article (default no)?
  $showdescription     = $settings['display_description'];        // display description (default no)?
  //$descriptionlimit      = $settings['Description Limit'];      // decide how many characters to display from description (0 = disabled)
  $showauthor          = $settings['display_author'];             // display authorname (default no)?
  $showdate            = $settings['display_creation_date'];      // display creation date (default no)?
  $showupdatedate      = $settings['display_updated_date'];       // display update date (default no)?
  $showreadmore        = $settings['display_read_more'];          // display read more link (default no)
  $boldtitle           = $settings['title_in_bold'];              // convert news title to link (default yes)?
  $titletolink         = $settings['title_link'];                 // convert news title to link (default yes)?
  $categorytolink      = $settings['page_link'];                  // convert category name to link (default yes)?
  $pagination          = !empty($settings['display_pagination']); // display pagination for multiple pages

  // create global settings
  $globalsettings = 0;
  $globalsettings += $p2_settings['display_title']                  == 1 ? 4    : 0;
  $globalsettings += $p2_settings['display_author']                 == 1 ? 8    : 0;
  $globalsettings += $p2_settings['display_creation_date']          == 1 ? 16   : 0;
  $globalsettings += $p2_settings['display_updated_date']           == 1 ? 32   : 0;
  $globalsettings += $p2_settings['display_print_link']             == 1 ? 64   : 0;
  $globalsettings += $p2_settings['display_description_in_article'] == 1 ? 256  : 0;
  $globalsettings += $p2_settings['display_comments']               == 1 ? 1024 : 0;
  $globalsettings += $p2_settings['display_on_main_page']           == 1 ? 4096 : 0;
  $globalsettings += $p2_settings['sticky_article']                 == 1 ? 8192 : 0;
  $GLOBALS['sd_ignore_watchdog'] = false;

  $extraquery = '';

  if($targeting)
  {
    $extraquery .= ' AND (news.categoryid = '.(int)$categoryid.')';
  }
  else
  if(strlen($includeids))
  {
    $valid_ids = array();
    $includeids = sd_ConvertStrToArray($includeids, ',');
    if(is_array($includeids) && !empty($includeids))
    {
      foreach($includeids as &$catid)
      {
        if(Is_Valid_Number($catid, 0, 1))
        {
          $valid_ids[] = $catid;
        }
      }
      if(!empty($includeids))
      {
        $extraquery .= ' AND (news.categoryid IN ('.implode(',',$valid_ids).'))';
      }
    }
  }

  // if we have elected to hide articles by default, search for those which are online
  if(($globalsettings & 4096 /* displaymainpage */ ) == 0)
  {
    $extraquery .= ' AND (news.settings & 4096)';
  }
  else // else exclude all global articles
  {
    $extraquery .= ' AND ((news.settings & 1) OR (news.settings & 4096))';
  }
  if(!empty($userinfo['categoryviewids']))
  {
    $extraquery .= ' AND (news.categoryid IN ('.implode(',',$userinfo['categoryviewids']).'))';
  }

  //v342: check for usergroup permission's
  if($DB->column_exists(PRGM_TABLE_PREFIX.'p2_news','access_view'))
  {
    $extraquery .= " AND ((IFNULL(news.access_view,'')='') OR (news.access_view like '%|".$userinfo['usergroupid']."|%'))";
  }

  // sorting articles
  $sorting = strtolower($sorting);
  switch($sorting)
  {
    case 'alphaaz':
      $sort = 'news.title ASC';
    break;

    case 'alphaza':
      $sort = 'news.title DESC';
    break;

    case 'authornameaz':
      $sort = 'news.author ASC';
    break;

    case 'authornameza':
      $sort = 'news.author DESC';
    break;

    case 'oldest':
      $sort = 'IF(IFNULL(datecreated,IFNULL(datestart,0))=0,dateupdated,IFNULL(datecreated,IFNULL(datestart,0))) ASC';
    break;

    default:
      $sorting = 'newest';
      $sort = 'IF(IFNULL(datecreated,IFNULL(datestart,0))=0,dateupdated,IFNULL(datecreated,IFNULL(datestart,0))) DESC';
  }

  // bold title?
  $boldtitlestart = $boldtitle ? '<strong>'  : '';
  $boldtitleend   = $boldtitle ? '</strong>' : '';

  $articlecounter = 0;
  $numberofarticles = 0;

  // items_per_page created above
  $page  = Is_Valid_Number(GetVar('p3_page', 1, 'whole_number'), 1, 1);
  $items_per_page = Is_Valid_Number($items_per_page, 5, 1, 1000);

  $Where_Cond = ' INNER JOIN {pagesort} ps ON ps.categoryid = news.categoryid AND ps.pluginid = \'2\''.//SD342
                ' WHERE (settings & 2)'.
                ' AND ((datestart = 0) OR (datestart < ' . TIME_NOW . '))'.
                ' AND ((dateend   = 0) OR (dateend  >= ' . TIME_NOW . ')) '.
                $extraquery;

  if($total_rows = $DB->query_first('SELECT COUNT(*) FROM {p2_news} news' . $Where_Cond))
  {
    $total_rows = (int)$total_rows[0];
  }

  $sql = 'SELECT * FROM {p2_news} news' .$Where_Cond.
         ' ORDER BY '.$sort.
         ' LIMIT ' . (($page-1)*$items_per_page).','.$items_per_page;

  if(!$getarticles = $DB->query($sql))
  {
    return;
  }

  $numberofarticles = $DB->get_num_rows($sql);

  echo '<div class="latest_articles_container">';

  while(($articlecounter < $items_per_page) && ($article = $DB->fetch_array($getarticles)))
  {
    $subtitle = '';

    // does category exist?
    if( (!empty($userinfo['adminaccess']) ||
         (!empty($userinfo['categoryviewids']) && @in_array($article['categoryid'],$userinfo['categoryviewids']))) &&
        ($category = $pages_md_arr[$article['categoryid']]) )
    {
      // does the news plugin exist in that category?
      //SD342: extra select is now incorporated into main select by inner join
      //if($newsplugin = $DB->query_first("SELECT displayorder FROM {pagesort} WHERE categoryid = %d AND pluginid = '2'",
      //                                  $article['categoryid']))
      {
        $articlecounter++;

        echo '<div class="latest_articles">'; //SD313 - enclose in div

        // display category name to the right of each news article
        if($showcategoryname)
        {
          $categoryname = $categorytolink ? ' (<a href="' . RewriteLink('index.php?categoryid='. $article['categoryid']) .
                          '">' . $category['name'] . '</a>)' :' (' . $category['name'] . ')';
        }
        else
        {
          $categoryname = '';
        }

        if($showauthor)
        {
          $subtitle = '<br />' . $language['by'] . ' ' . $article['author'];
        }

        if($showdate)
        {
          $subtitle .= ($showauthor ? ' - ' : '<br />').
                       $language['published'] . ' ' .
                       (!empty($article['datestart']) ? DisplayDate($article['datestart']) : DisplayDate($article['datecreated']));
        }

        if($showupdatedate && ($article['dateupdated'] > 0))
        {
          $subtitle .= ($showauthor && !$showdate) ? ' - ' : '<br />'.
                       $language['updated'] . ' ' . DisplayDate($article['dateupdated']);
        }

        if($mainsettings_modrewrite && strlen($article['seo_title']))
        {
          $articlelink = RewriteLink('index.php?categoryid=' . $article['categoryid']);
          $articlelink = str_replace($mainsettings_url_extension, '/' . $article['seo_title'] . $mainsettings_url_extension, $articlelink);
        }
        else
        {
          $articlelink = RewriteLink('index.php?categoryid=' . $article['categoryid'] . '&p2_articleid='.$article['articleid']);
        }

        // convert title to link?
        $titlelinkstart = $titlelinkend = '';
        if(!empty($titletolink))
        {
          $titlelinkstart = '<a href="' . $articlelink . '">';
          $titlelinkend   = '</a>';
        }

        $articledescription = $showdescription ? '<br />' . $article['description'] : '';

        if(strlen($article['description']) && $showdescription)
        {
          echo $titlelinkstart . $boldtitlestart . $article['title'] . $boldtitleend . $titlelinkend . $categoryname . $subtitle . '<br />' . $articledescription;

          if($showreadmore)
          {
            echo '<br /><a href="' . $articlelink . '">' . $language['read_more'] . '</a><br />';
          }
        }
        else
        {
          echo $titlelinkstart . $boldtitlestart . $article['title'] . $boldtitleend . $titlelinkend .
               $categoryname . $subtitle;
        }
        echo '</div>';
      }
    }
    else
    {
      $total_rows--;
    }
  } //while

  echo '</div>';

  if($pagination && ($total_rows > $items_per_page))
  {
    // pagination
    $p = new pagination;
    $p->parameterName('p3_page');
    $p->items($total_rows);
    $p->limit($items_per_page);
    $p->currentPage($page);
    $p->adjacents(2);
    $p->target(RewriteLink('index.php?categoryid=' . $categoryid));
    $p->show();
  }

} //p3_LatestNews

// ###############################################################################
// FUNCTION CALL
// ###############################################################################

p3_LatestNews();
