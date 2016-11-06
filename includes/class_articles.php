<?php

if(!defined('IN_PRGM')) return false;

if(!class_exists('ArticlesClass'))
{
  class ArticlesClass
  {
    public  $article          = false;
    public  $pluginid         = 0;
    public  $p_prefix         = '';
    public  $globalsettings   = 0;
    public  $language         = array();
    public  $settings         = array();
    public  $slug_arr         = array(); //SD343
    public  $table_name       = '';
    public  $authormode       = false;

    //SD351: attachments related
    public  $attach_path        = '';
    public  $attach_path_ok     = false;
    public  $attach_path_exists = false;
    public  $attach_extentions  = '';
    public  $attach_max_size    = 0;
    public  $doSearch           = false; //SD350
    public  $SearchTerm         = false; //SD350

    private $_attachment        = null;
    private $_IsAdmin           = false;
    private $_IsSiteAdmin       = false;
    private $_HasAdminRights    = false;
    private $_candownload       = false;

    private $_plugin_folder   = '';
    private $_quoted_url_ext  = '';
    private $_smarty          = false;
    private $_rating          = false; //SD342
    private $_tag_active      = false; //SD343
    private $_isTagPage       = false; //SD343
    private $_articleCounter  = 0; //SD344

    // Article Bitfield Settings (static, so use "self::$articlebitfield"!)
    public static $articlebitfield = array( // max. 32 options!
        'useglobalsettings'       => 1,
        'displayonline'           => 2,
        'displaytitle'            => 4,
        'displayauthor'           => 8,
        'displaycreateddate'      => 16,
        'displayupdateddate'      => 32,
        'displayprintarticlelink' => 64,
        'displayemailarticlelink' => 128,
        'displaydescription'      => 256,
        'displaysmilies'          => 512,
        'displaycomments'         => 1024,
        'displayviews'            => 2048,
        'displaymainpage'         => 4096,
        'displaysticky'           => 8192,
        'displayratings'          => 16384,
        'displaysocialmedia'      => 32768,
        'displaytags'             => 65536,
        'displaypdfarticlelink'   => 131072,
        'displayaspopup'          => 262144,
        'ignoreexcerptmode'       => 524288, //SD342
        'linktomainpage'          => 1048576, //SD343
    );

    public function __construct($plugin_folder)
    {
      global $DB, $mainsettings_url_extension, $userinfo;

      $this->_plugin_folder = $plugin_folder;
      $this->pluginid = GetPluginIDbyFolder($this->_plugin_folder);
      $this->_quoted_url_ext = SD_QUOTED_URL_EXT;

      // Get plugin's language phrases and settings
      $this->language = GetLanguage($this->pluginid);
      $this->settings = GetPluginSettings($this->pluginid);
      $this->p_prefix = 'p'.$this->pluginid.'_';
      $this->table_name = $this->p_prefix . 'news';

      //SD351: check attachments path
      $this->attach_path = '';
      $this->attach_path_ok = $this->attach_path_exists = false;
      if(isset($this->settings['attachments_upload_path']) &&
          strlen(trim($this->settings['attachments_upload_path'])))
      {
        $this->attach_path = trim($this->settings['attachments_upload_path']);
        $this->attach_path_exists = @is_dir(ROOT_PATH.$this->attach_path);
        $this->attach_path_ok = $this->attach_path_exists &&
            @is_writable(ROOT_PATH.$this->attach_path);
      }
      $this->attach_extentions = (empty($this->settings['valid_attachment_types'])?'':
          (string)$this->settings['valid_attachment_types']);
      $this->attach_max_size   = (empty($this->settings['attachments_max_size'])?0:
          (int)$this->settings['attachments_max_size']);

      $this->authormode        = empty($userinfo['adminaccess']) &&
          !empty($userinfo['authormode']);

      $this->_candownload    = (!empty($userinfo['plugindownloadids']) &&
          @in_array($this->pluginid, $userinfo['plugindownloadids']));
      $this->_IsAdmin        = (!empty($userinfo['pluginadminids']) &&
          @in_array($this->pluginid, $userinfo['pluginadminids']));
      $this->_IsSiteAdmin    = !empty($userinfo['adminaccess']) && !empty($userinfo['loggedin']) &&
          !empty($userinfo['userid']);
      $this->_HasAdminRights = $this->_IsSiteAdmin ||
          (!$this->authormode &&
              ((!empty($userinfo['admin_pages']) && in_array('articles', $userinfo['admin_pages'])) &&
                  (!empty($userinfo['pluginadminids']) && in_array($this->pluginid, $userinfo['pluginadminids']))));

      // create global article settings based on plugin settings
      $this->globalsettings = 0;
      $this->globalsettings += $this->settings['display_title']                  == 1 ?      4 : 0;
      $this->globalsettings += $this->settings['display_author']                 == 1 ?      8 : 0;
      $this->globalsettings += $this->settings['display_creation_date']          == 1 ?     16 : 0;
      $this->globalsettings += $this->settings['display_updated_date']           == 1 ?     32 : 0;
      $this->globalsettings += $this->settings['display_print_link']             == 1 ?     64 : 0;
      $this->globalsettings += $this->settings['display_email_link']             == 1 ?    128 : 0;
      $this->globalsettings += $this->settings['display_description_in_article'] == 1 ?    256 : 0;
      $this->globalsettings += $this->settings['display_comments']               == 1 ?   1024 : 0;
      $this->globalsettings += $this->settings['display_views_count']            == 1 ?   2048 : 0;
      $this->globalsettings += $this->settings['display_on_main_page']           == 1 ?   4096 : 0;
      $this->globalsettings += $this->settings['sticky_article']                 == 1 ?   8192 : 0;
      $this->globalsettings += $this->settings['display_user_ratings']           == 1 ?  16384 : 0;
      $this->globalsettings += $this->settings['display_social_media_links']     == 1 ?  32768 : 0;
      $this->globalsettings += !empty($this->settings['display_tags'])                ?  65536 : 0;
      $this->globalsettings += !empty($this->settings['display_pdf_link'])            ? 131072 : 0;
      $this->globalsettings += !empty($this->settings['display_as_popup'])            ? 262144 : 0;
      $this->globalsettings += !empty($this->settings['ignore_excerpt_mode'])         ? 524288 : 0; //SD342
      $this->globalsettings += !empty($this->settings['link_to_main_page'])           ?1048576 : 0; //SD343

      require_once(SD_INCLUDE_PATH.'class_sd_tags.php');

    } //ArticlesClass


    // ##########################################################################
    // INIT ATTACHMENT
    // ##########################################################################

    function InitAttachment()
    {
      global $DB, $sdlanguage, $userinfo;

      require_once(SD_INCLUDE_PATH.'class_sd_attachment.php');

      if(!empty($this->_attachment) && ($this->_attachment instanceof SD_Attachment))
      {
        return true;
      }

      $this->_attachment = new SD_Attachment($this->pluginid,'articles');
      $this->_attachment->setValidExtensions($this->attach_extentions);
      $this->_attachment->setMaxFilesizeKB($this->attach_max_size);
      if($this->attach_path_ok)
      {
        $this->_attachment->setStorageBasePath($this->attach_path);
      }

      return true;

    } //InitAttachment


    private function InitTemplate()
    {
      global $categoryid, $mainsettings, $sdlanguage, $userinfo;

      // Instantiate smarty object
      //$this->_smarty = SD_Smarty::getNew(); //SD342
      $this->_smarty = SD_Smarty::getInstance(); //SD343
      $this->_smarty->assign('AdminAccess',   ($userinfo['adminaccess'] || (!empty($userinfo['pluginadminids']) &&
              @array_key_exists($this->pluginid,array_flip($userinfo['pluginadminids'])))));
      $this->_smarty->assign('categoryid',    $categoryid);
      $this->_smarty->assign('pluginid',      $this->pluginid);
      $this->_smarty->assign('sdlanguage',    $sdlanguage);
      $this->_smarty->assign('sdurl',         SITE_URL);
      $this->_smarty->assign('securitytoken', $userinfo['securitytoken']);
      $this->_smarty->assign('article_lang',  $this->language);
      $this->_smarty->assign('page_title',    (defined('SD_CATEGORY_TITLE')?SD_CATEGORY_TITLE:''));
      $this->_smarty->assign('enable_rss',    !empty($mainsettings['enable_rss']));
      $this->_smarty->error_reporting = E_ALL & ~E_NOTICE;

      $this->_smarty->assign('article_rating_form', '');
      $this->_smarty->assign('article_display_tags', false);
      $this->_smarty->assign('article_tags', array());
      $this->_smarty->assign('article_tagcount', 0);
      $this->_smarty->assign('article_display_article', false);
      $this->_smarty->assign('article_display_description', false);
      $this->_smarty->assign('article_display_pagination', false);
      $this->_smarty->assign('article_description', '');
      $this->_smarty->assign('article_pagination', '');
      $this->_smarty->assign('article_read_more', false);
      $this->_smarty->assign('article_published', '');
      $this->_smarty->assign('article_updated', '');
      $this->_smarty->assign('article_display_author', false);
      $this->_smarty->assign('article_display_published', false);
      $this->_smarty->assign('article_display_updated', false);
      $this->_smarty->assign('article_display_footer', false);
      $this->_smarty->assign('article_link', '');
      $this->_smarty->assign('article_title_link', true);
      $this->_smarty->assign('article_share_title', '');
      $this->_smarty->assign('article_share_url', '');
      $this->_smarty->assign('article_display_social', false);
      $this->_smarty->assign('article_display_email', false);
      $this->_smarty->assign('article_display_print', false);
      $this->_smarty->assign('article_display_pdf', false);
      $this->_smarty->assign('article_display_comments', false);

    } //InitTemplate


    public function GetArticle($article_id)
    {
      global $DB;
      $DB->result_type = MYSQL_ASSOC;
      $this->article = $DB->query_first('SELECT * FROM {'.$this->table_name.'} WHERE articleid = %d', $article_id);
    } //GetArticle


    // ##########################################################################
    // DISPLAY ARTICLE
    // ##########################################################################

    public function DisplayArticle($article_arr, $viewing_all_articles = false, $preselected=false)
    {
      global $sdlanguage, $userinfo;
      //var_dump($article_arr);
      //SD342 check for article being active or not AND
      //      if current user has view permissions for it
      $article_groups = isset($article_arr['access_view'])?sd_ConvertStrToArray($article_arr['access_view'],'|'):array();
      //SD343: check all usergroups, not only primary
      $hasAccess = !empty($userinfo['adminaccess']) ||
          empty($article_groups) ||
          (is_array($article_groups) && count(array_intersect($userinfo['usergroupids'],$article_groups)));
      if(!$hasAccess || empty($article_arr['settings']) || (($article_arr['settings'] & 2) == 0))
      {
        DisplayMessage($sdlanguage['no_view_access'], true);
        return false;
      }

      global $DB, $Comments, $categoryid, $categoryids, $mainsettings, $mainsettings_url_extension,
             $mainsettings_modrewrite, $sdurl, $usersystem;

      // check which page of the article we are on
      // this variable needs to be created before displaying the article becuase it's used
      // in the description printing area

      // use global settings for this article?
      if($article_arr['settings'] & self::$articlebitfield['useglobalsettings'])
      {
        $article_arr['settings'] = $this->globalsettings;
      }

      // *** SMARTY TEMPLATE ***
      $this->InitTemplate();
      $article_display_title = $article_arr['settings'] & self::$articlebitfield['displaytitle'];
      $this->_smarty->assign('plugin_id', $this->pluginid); //SD342
      $this->_smarty->assign('plugin_folder', $this->_plugin_folder); //SD342
      $this->_smarty->assign('article_id', $article_arr['articleid']);
      $this->_smarty->assign('article_title_popup', false);

      //SD351: article link generation moved to GetArticleLink (functions_global.php)
      $article_link = GetArticleLink($categoryid, $this->pluginid, $article_arr,
          self::$articlebitfield,
          $this->_isTagPage, $this->_plugin_folder); //SD351

      $this->_smarty->assign('article_display_title', $article_display_title);
      $this->_smarty->assign('article_title_link', $viewing_all_articles && $this->settings['title_link']);
      $this->_smarty->assign('article_title', $article_arr['title']);
      $this->_smarty->assign('article_link', $article_link);
      //SD342
      $this->_smarty->assign('article_thumbnail', $article_arr['thumbnail']);
      $this->_smarty->assign('article_featured', !empty($article_arr['featured']));
      $this->_smarty->assign('article_featuredpath', $article_arr['featuredpath']);
      $this->_smarty->assign('article_rating', $article_arr['rating']);
      $this->_smarty->assign('article_article', $article_arr['article']);

      echo $article_arr['article'];



      // ############################### Article ##################################
    } //DisplayArticle


    // ############################################################################
    // DISPLAY ARTICLES
    // ############################################################################

    public function DisplayArticles()
    {
      global $DB, $categoryid, $sd_tagspage_link, $sdlanguage, $userinfo;

      // get category settings, if none then resort to default
      $DB->result_type = MYSQL_ASSOC;
      if($categorysettings = $DB->query_first('SELECT * FROM {'.$this->p_prefix.'settings} WHERE categoryid = %d', $categoryid))
      {
        $items_per_page = (int)$categorysettings['maxarticles'];
        $items_per_page = empty($items_per_page) || !is_numeric($items_per_page) ? 10 : (int)$items_per_page;
        $order          = $categorysettings['sorting'];
        $multiplepages  = !empty($categorysettings['multiplepages']);
      }
      else
      {
        $items_per_page = 10;
        $order          = 'newest_first';
        $multiplepages  = 1;
      }

      // Translate sorting
      $default_order = 'IF(IFNULL(datecreated,IFNULL(datestart,0))=0,dateupdated,IFNULL(datecreated,IFNULL(datestart,0))) ';
      if($order == 'oldest_first')
        $order = $default_order.'ASC';
      else
        if(!$order || ($order == 'newest_first'))
          $order = $default_order.'DESC';

      //SD343: display tag cloud
      $article_tags = false;
      if(!empty($this->settings['display_tagcloud']) && ($this->settings['display_tagcloud'] > 0))
      {
        SD_Tags::$maxentries   = 30;
        SD_Tags::$plugins      = $this->pluginid;
        SD_Tags::$tags_title   = $sdlanguage['tags_title'];
        SD_Tags::$targetpageid = 0; // auto-detect (tags page)
        SD_Tags::$tag_as_param = ''; // must be empty
        if($article_tags = SD_Tags::DisplayCloud(false))
        {
          if($this->settings['display_tagcloud'] == 1)
          {
            echo $article_tags;
          }
        }
      }

      $extraquery = '';
      //SD343: check for special params
      // a) first check for tags determined by SD core
      // b) check for params determined by header file
      $this->_tag_active = false;
      global $sd_tag_value;
      $this->_isTagPage = false;
      $TagParams = '';
      $url = RewriteLink('index.php?categoryid=' . $categoryid);

      //SD350: check for search term coming from search engine plugin
      // (is set within header.php)
      if(!empty($this->doSearch) && !empty($this->SearchTerm))
      {
        //TODO: output results here if no Search Engine plugin on same page??
      }
      else
        if(SD_TAG_DETECTED && is_array($sd_tag_value))
        {
          if(!empty($sd_tag_value['key']) && ($sd_tag_value['key']=='tags') && !empty($sd_tag_value['value']))
          {
            $this->_tag_active = $sd_tag_value['value'];
            $extraquery = ' AND EXISTS(SELECT 1 FROM '.PRGM_TABLE_PREFIX.'tags tags WHERE tags.pluginid = '.
                $this->pluginid.' AND tags.objectid = '.PRGM_TABLE_PREFIX.$this->table_name.
                ".articleid AND tags.tagtype = 0".
                " AND lower(tags.tag) = '".
                ($DB->escape_string(strtolower($this->_tag_active)))."')\r\n";
            $this->_isTagPage = true;
            $TagParams = $this->_tag_active;
            $url = $sd_tagspage_link.$TagParams;
          }
          else
            if(!empty($sd_tag_value['year']) && !empty($sd_tag_value['month']) &&
                Is_Valid_Number($sd_tag_value['year'],0,2000,2050) &&
                Is_Valid_Number($sd_tag_value['month'],0,1,12) )
            {
              $this->_tag_active = '';
              $extraquery = " AND DATE_FORMAT(FROM_UNIXTIME(IF(datestart>0,datestart,datecreated)),'%Y%m') = ".
                  sprintf('%02d',$sd_tag_value['year']).sprintf('%02d',$sd_tag_value['month']);
              $this->_isTagPage = true;
              $TagParams = sprintf('%02d',$sd_tag_value['year']).'/'.sprintf('%02d',$sd_tag_value['month']);
              $url = $sd_tagspage_link.$TagParams;
            }
            else
            {
              // Invalid search/tag specified! Create empty results!
              $extraquery = ' AND 1 = 0 ';
            }
        }
        else
          if(!empty($this->slug_arr['id']))
          {
            if(!empty($this->slug_arr['key']) && ($this->slug_arr['key']=='tags') && !empty($this->slug_arr['value']))
            {
              $this->_tag_active = $this->slug_arr['value'];
              $extraquery = ' AND EXISTS(SELECT 1 FROM '.PRGM_TABLE_PREFIX.'tags tags WHERE tags.pluginid = '.
                  $this->pluginid.' AND tags.objectid = '.PRGM_TABLE_PREFIX.$this->table_name.
                  ".articleid AND tags.tagtype = 0".
                  " AND lower(tags.tag) = '".
                  ($DB->escape_string(strtolower($this->_tag_active)))."')\r\n";
              $this->_isTagPage = true;
              $TagParams = $this->_tag_active;
            }
            else
              if(!empty($this->slug_arr['year']) && !empty($this->slug_arr['month']) &&
                  Is_Valid_Number($this->slug_arr['year'],0,2000,2050) &&
                  Is_Valid_Number($this->slug_arr['month'],0,1,12) )
              {
                $this->_tag_active = '';
                $extraquery = " AND DATE_FORMAT(FROM_UNIXTIME(IF(datestart>0,datestart,datecreated)),'%Y%m') = ".
                    sprintf('%02d',$this->slug_arr['year']).sprintf('%02d',$this->slug_arr['month']);
                $this->_isTagPage = true;
                $TagParams = sprintf('%02d',$this->slug_arr['year']).'/'.sprintf('%02d',$this->slug_arr['month']);
                $url = preg_replace('#'.$this->_quoted_url_ext.'$#', '/',
                        RewriteLink('index.php?categoryid='.$categoryid)).$TagParams;
              }
              else
              {
                // Invalid search/tag specified! Create empty results!
                $extraquery = ' AND 1 = 0 ';
              }
          }

      // if articles are hidden by default, then find the ones with 'display on main page listing' enabled
      if(empty($sd_tag_value['id']))
      {
        if(($this->globalsettings & self::$articlebitfield['displaymainpage']) == 0)
          $extraquery .= ' AND (settings & '.self::$articlebitfield['displaymainpage'].')';
        else
          $extraquery .= ' AND ((settings & 1) OR (settings & '.self::$articlebitfield['displaymainpage'].'))';
      }

      //SD342: check for usergroup permission's
      $extraquery .= " AND ((IFNULL(access_view,'')='') OR (access_view like '%|".(int)$userinfo['usergroupid']."|%'))";

      //$where = ' WHERE (settings & '.self::$articlebitfield['displayonline'].')';
      $where = 'categoryid = '.$categoryid;
      //v3.5.2: added "slug_arr['id']" to ignore categories
      /*if( (empty($sd_tag_value['id']) && empty($this->slug_arr['id'])) ||
          empty($this->settings['ignore_pages_for_date_tags']) ) //SD360
      {
        $where .= ' (categoryid = '.$categoryid.')'.
                  ' OR EXISTS(SELECT 1 FROM {p'.$this->pluginid.'_articles_pages} ap'.
                  ' WHERE ap.categoryid = '.$categoryid.
                  ' AND ap.articleid = {'.$this->table_name.'}.articleid)';
      }
      */
      //$where .= ' AND ((datestart = 0) OR (datestart < ' . TIME_NOW . '))';

      if(empty($this->settings['ignore_publish_end_date'])) //news plugin v3.5.2 (SD36)
      {
        //$where .= ' AND ((dateend = 0) OR (dateend > ' . TIME_NOW . '))';
      }
      $get_articles = $DB->query("SELECT * FROM `sd_p2_news` WHERE `categoryid`=".$categoryid);
      $article_arr = $DB->fetch_array($get_articles);

      $this->DisplayArticle($article_arr, true);
    } //DisplayArticles


    // ############################################################################
    // SELECT FUNCTION
    // ############################################################################
    public function Display($article_arr)
    {
      global $DB, $categoryid, $userinfo, $mainsettings_search_results_page;

      //SD360: if search term was indicated, do nothing and assume, that the
      // Search Engine plugin takes care of results
      if($this->doSearch && ($categoryid == $mainsettings_search_results_page))
      {
        return true;
      }

      $extra = '';
      if(empty($this->settings['ignore_publish_end_date'])) //news plugin v3.5.2 (SD3.5.1)
      {
        $extra = ' AND ((dateend = 0) OR (dateend > %d))';
      }

      $articleid = GetVar($this->p_prefix.'articleid', false, 'whole_number');
      if(!empty($article_arr) && ($article_arr['pluginid']==$this->pluginid))
      {
        //news plugin v3.5.2 (SD3.5.1): check for dateend if cached
        if(!empty($this->settings['ignore_publish_end_date']) ||
            empty($article_arr['dateend']) ||
            ($article_arr['dateend'] >= TIME_NOW) )
        {
          $this->DisplayArticle($article_arr, false, true);
        }
      }
      else
        if($articleid && ($article = $DB->query_first(
                'SELECT * FROM '.PRGM_TABLE_PREFIX.$this->table_name.' news WHERE articleid = %d'.
                ' AND ((categoryid = %d) OR EXISTS (SELECT 1 FROM '.PRGM_TABLE_PREFIX.'p'.$this->pluginid.'_articles_pages ap'.
                ' WHERE ap.articleid = news.articleid AND ap.categoryid = %d))'.
                ' AND (settings & 2) AND ((datestart = 0) OR (datestart < %d)) '.$extra,
                $articleid, $categoryid, $categoryid, TIME_NOW, TIME_NOW)))
        {
          $this->DisplayArticle($article);
        }
        else
        {
          // display easy "submit article to this page" link
          if( $userinfo['loggedin'] && defined('DISPLAY_PLUGIN_ADMIN_SHORTCUTS') && DISPLAY_PLUGIN_ADMIN_SHORTCUTS &&
              ( $userinfo['adminaccess'] ||
                  ( (!empty($userinfo['pluginadminids']) && @in_array($this->pluginid, $userinfo['pluginadminids'])) ||
                      (!empty($userinfo['admin_pages']) && in_array('articles', $userinfo['admin_pages']) &&
                          !empty($userinfo['pluginsubmitids']) && @in_array($this->pluginid, $userinfo['pluginsubmitids']))
                  )
              )
          )
          {
            echo IMAGE_EDIT . ' <a href="'.ADMIN_PATH.'/articles.php'.
                ($this->pluginid>2?'?pluginid='.$this->pluginid:'').
                '&amp;articleaction=displayarticleform&amp;load_wysiwyg=1&amp;pageid='.$categoryid.'">'.
                $this->language['insert_article_into_page'] . '</a><br /><br />';
          }

          $this->DisplayArticles();
        }

    } //Display

  } // end of class
} // DO NOT REMOVE!
