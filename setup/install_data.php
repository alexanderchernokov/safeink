<?php
if(!defined('SD_IN_INSTALL')) die('Invalid call!');

// ************************************************
// ****** OPTIONAL INSTALLATION OF DEMO DATA ******
// ************************************************
// This is called AFTER installation is FULLY done,
// including all available upgrade steps!
if(defined('SD_INSTALL_DEMO') && SD_INSTALL_DEMO) //SD344
{
  $DB->ignore_error = true;
  if(empty($optionsDemoData))
  {
    // *** At least add Home Page ***
    $order = 10;
    $cat_insert = "INSERT INTO {categories} (`categoryid`,`parentid`,`designid`,`name`,`urlname`,`displayorder`,".
                  "`link`,`target`,`image`,`hoverimage`,`menuwidth`,`metadescription`,`metakeywords`,`appendkeywords`,`title`)".
                  " VALUES (NULL, 0, ";
    $DB->query($cat_insert . "1, 'Home', 'home', $order, '', '', '', '', 125, '', '', 0, 'Home')");
    $DB->query("UPDATE {usergroups} SET categoryviewids = '1', categorymenuids = '1'");
  }
  else
  {
    $DB->ignore_error = False;

    // ********** Pages (Categories) **********

    $order = 10;
    $cat_insert = "INSERT INTO {categories} (`categoryid`,`parentid`,`designid`,`name`,`urlname`,`displayorder`,".
                  "`link`,`target`,`image`,`hoverimage`,`menuwidth`,`metadescription`,`metakeywords`,`appendkeywords`,`title`)".
                  " VALUES (NULL, ";
    $DB->query($cat_insert . "0, 1, 'Home', 'home', $order, '', '', '', '', 125, '', '', 0, 'Home')");
    $homepage = $DB->insert_id(); $order += 10;

      $DB->query($cat_insert . $homepage.", 1, 'search', 'search', 10, '', '', '', '', 125, '', '', 0, 'Search Results')");
      $search_results = $DB->insert_id();
      $DB->query("UPDATE {mainsettings} SET value = %d WHERE varname = 'search_results_page'", $search_results);

      $DB->query($cat_insert . $homepage.", 1, 'tags', 'tags', 20, '', '', '', '', 125, '', '', 0, 'Tags Results')");
      $tags_results = $DB->insert_id();
      $DB->query("UPDATE {mainsettings} SET value = %d WHERE varname = 'tag_results_page'", $tags_results);

    $DB->query($cat_insert . "0, 1, 'Downloads', 'downloads', $order, '', '', '', '', 125, '', '', 0, 'Downloads')");
    $page_downloads = $DB->insert_id(); $order += 10;

    $DB->query($cat_insert . "0, 1, 'Media Gallery', 'media', $order, '', '', '', '', 125, '', '', 0, 'Media Gallery')");
    $page_media_gallery = $DB->insert_id(); $order += 10;

    $DB->query($cat_insert . "0, 1, 'Plugins', 'plugins', $order, '', '', '', '', 80, '', '', 0, 'Plugins')");
    $plugins_sampler = $DB->insert_id(); $order += 10;

      $DB->query($cat_insert . $plugins_sampler.", 1, 'Image Gallery', 'gallery', 10, '', '', '', '', 100, '', '', 0, 'Image Gallery')");
      $page_image_gallery = $DB->insert_id();

      $DB->query($cat_insert . $plugins_sampler.", 1, 'Form Wizard', 'forms', 20, '', '', '', '', 100, '', '', 0, 'Form Wizard')");
      $page_form_wizard = $DB->insert_id();

    $DB->query($cat_insert . "0, 2, 'Forum', 'forum', $order, '', '', '', '', 82, '', '', 0, 'Forum')");
    $page_forum = $DB->insert_id(); $order += 10;

    $DB->query($cat_insert . "0, 2, 'Contact', 'contact', $order, '', '', '', '', 70, '', '', 0, 'Contact')");
    $page_contact = $DB->insert_id(); $order += 10;

    $DB->query($cat_insert . "0, 2, 'Register', 'register', $order, '', '', '', '', 90, '', '', 0, 'Register')");
    $page_register = $DB->insert_id(); $order += 10;

    $DB->query($cat_insert . "0, 2, 'Profile', 'profile', $order, '', '', '', '', 82, '', '', 0, 'Profile')");
    $page_profile = $DB->insert_id(); $order += 10;

    // *** Articles demo ***
    $DB->query("INSERT INTO {p2_settings} (`categoryid`, `maxarticles`, `sorting`, `multiplepages`) VALUES
    (1, 10, 'IF(IFNULL(datecreated,0)=0, dateupdated, datecreated) DESC', 1)");

    $DB->query("INSERT INTO {p2_news} (`articleid`, `categoryid`, `settings`, `views`, `displayorder`, `datecreated`, `dateupdated`, `datestart`, `dateend`, `author`, `title`, `metadescription`, `metakeywords`, `description`, `article`, `seo_title`, `tags`) VALUES
    (NULL, 1, 3, 1, 1, " . (TIME_NOW-120) . ", 0, 0, 0, 'Demo', 'Welcome to Subdreamer', '', '', '', '<h2>Your installation was successful.</h2>\r\n<h2>Time to add some content to your new website.</h2>\r\n<p>Thank you very much for choosing Subdreamer! We are nearly there now, please follow <a href=\"admin/index.php\">this link</a> to begin updating and changing your website from the admin panel.</p>', 'welcome_to_subdreamer', ''),
    (NULL, 1, 3, 1, 2, " . (TIME_NOW-(86400*60)) . ", 0, 0, 0, 'Demo', 'Need a professional <strong>Theme?</strong>', '', '', '', '<p>Contributing to the community as an official partner for more than half a decade since the initial release of Subdreamer already, Max from indiqo.media probably is the most experienced, talented and reliable web designer available for hire and should be the very first choice for anybody who\'s in need of a well designed, unique and clean coded custom theme.</p>\r\n<p>Don\'t miss <a title=\"Visit the beautiful indiqo.media website\" href=\"http://indiqo.eu/\">his website</a> for further information or feel free to directly send an <a title=\"Get in touch with Max\" href=\"mailto:max@indiqo.eu\">eMail to Max</a> whenever you need somebody to  help you turning a sophisticated idea into reality.</p>', 'indiqo_media_themes', '')");

    // *** Image Gallery ***
    $DB->query("INSERT INTO {p17_sections} (`sectionid`, `parentid`, `activated`, `name`, `description`, `sorting`, `imageid`, `datecreated`, `imagecount`) VALUES
    (NULL, 0, 1, 'Images', '', 'Newest First', NULL, 1258201810, 1),
    (NULL, 1, 1, 'Nature', '', 'Newest First', 0, 1260387234, 1)");

    $DB->query("INSERT INTO {p17_images} (`imageid`, `sectionid`, `activated`, `filename`, `allowsmilies`, `allowcomments`, `showauthor`, `author`, `title`, `description`, `viewcount`, `datecreated`) VALUES
    (NULL, 2, 1, '1.jpg', 0, 0, 0, 'Jon Sullivan', 'Banana Leaf', '', 0, ".TIME_NOW.")");

    // Forum: add a sample topic and a single post
    $forum_id = GetPluginIDbyFolder('forum');
    if($forum_id &&
       ($row = $DB->query_first("SELECT forum_id FROM {p_forum_forums} WHERE is_category = 0 ORDER BY forum_id LIMIT 1")))
    {
      if(!empty($row['forum_id']))
      {
        $DB->query('INSERT INTO {p_forum_topics}'.
                   ' (forum_id, date, post_count, views, open, post_user_id,'.
                   ' post_username, title, last_post_date, last_post_username, sticky, moderated) VALUES'.
                   ' (' . $row['forum_id'] . ', '.TIME_NOW.', 1, 1, 1, 1, ' .
                   " 'Demo', 'Welcome to our forums', ".TIME_NOW.", 'Demo', 1, 0)");
        if($tid = $DB->insert_id())
        {
          $DB->query('INSERT INTO {p_forum_posts}'.
                     ' (topic_id, username, user_id, date, post, ip_address, moderated) VALUES'.
                     " ($tid, 'Demo', 1,".TIME_NOW.", '[b]This is a welcome message![/b]', '', 0)");
          $fpostid = $DB->insert_id();
          $DB->query("UPDATE {p_forum_forums} SET `last_topic_id` = '%d',".
                     " `last_topic_title` = 'Welcome to our forums',".
                     " `last_post_id` = %d, `last_post_username` = 'Demo',".
                     " `last_post_date` = ".TIME_NOW.", `topic_count` = 1,".
                     " `post_count` = 1".
                     " WHERE forum_id = ".$row['forum_id'],
                     $tid, $fpostid);
          $DB->query("UPDATE {p_forum_topics} SET `first_post_id` = '%d',`last_post_id` = '%d'".
                     " WHERE topic_id = %d",
                     $fpostid,$fpostid,$tid);
        }
      }
    }

    $dlm_id = GetPluginIDbyFolder('download_manager');
    if($latest_files_id = GetPluginIDbyFolder('latest_files'))
    {
      $DB->query("UPDATE {pluginsettings} SET value = '%d' WHERE pluginid = %d AND title = 'source_plugin'",
                 $dlm_id, $latest_files_id);
    }

    if(!$form_wizard_id = GetPluginIDbyFolder('form_wizard')) $form_wizard_id = 1;
    if($form_wizard_id >= 5000)
    {
      $DB->query("INSERT INTO {p".$form_wizard_id."_form} (`form_id`, `name`, `submit_type`, `submit_text`, `intro_text`, `success_text`, `showemailaddress`, `sendtoall`, `active`, `date_created`, `email_sender_id`, `access_view`)
      VALUES (1, 'First Test Form', 2, 'Submit Form', 'Create flexible forms for user input with the Form Wizard!', 'Form successfully submitted.', 0, 1, 1, ".TIME_NOW.", 0, '')");
      $DB->query("INSERT INTO {p".$form_wizard_id."_formcategory} (`form_id`, `category_id`) VALUES (1, %d)",$page_form_wizard);
      $DB->query("INSERT INTO {p".$form_wizard_id."_recipient} (`recipient_id`, `email`, `name`)
      VALUES (1, '".$DB->escape_string($admin_email)."', '".$DB->escape_string($admin_username)."')");

      $DB->query("INSERT INTO {p".$form_wizard_id."_formfield} (`field_id`, `form_id`, `field_type`, `name`, `validator_type`, `label`, `width`, `height`, `sort_order`, `active`, `date_created`, `allowed_fileext`, `max_filesize`)
      VALUES (1, 1, 1, 'Hobbies', 0, 'Your hobbies?', 0, 0, 1, 1, ".TIME_NOW.", '', 0)");
    }

    if(!$event_manager_id = GetPluginIDbyFolder('event_manager')) $event_manager_id = 1;
    if($event_manager_id >= 5000)
    {
      $DB->query("INSERT INTO {p".$event_manager_id."_events} (`eventid`, `activated`, `allowcomments`, `title`, `description`, `date`, `venue`, `street`, `city`, `state`, `country`, `image`, `thumbnail`)
      VALUES (1, 1, 0, 'Visit Subdreamer', 'Please visit the Subdreamer CMS website for latest news and updates.', ".TIME_NOW.", 'Your browser', '', 'Anyhwere', '', '', '', '')");
    }

    if($guestbook_id = GetPluginIDbyFolder('p4_guestbook'))
    {
      $DB->query("INSERT INTO {p4_guestbook} (`messageid`, `username`, `websitename`, `website`, `message`, `datecreated`, `ipaddress`)
      VALUES (1, 'admin', 'Subdreamer CMS', 'http://antiref.com/?http://www.subdreamer.com', 'We love Subdreamer!', 1380966711, '127.0.0.1')");
    }

    $calendar2_id = GetPluginIDbyFolder('calendar2');
    if($article_archive_id = GetPluginIDbyFolder('articles_archive'))
    {
      $DB->query("UPDATE {pluginsettings} SET value = '%d' WHERE pluginid = %d AND title = 'article_list_page'",
                 $homepage, $article_archive_id);
    }

    if($linkdir_id = GetPluginIDbyFolder('p16_link_directory'))
    {
      $DB->query("INSERT INTO {p".$linkdir_id."_links} (`linkid`, `sectionid`, `activated`, `allowsmilies`, `showauthor`,".
                 " `author`, `title`, `url`, `description`, `thumbnail`, `ipaddress`) VALUES".
                 " (NULL, '1', '1', '1', '0', 'Demo', 'Subdreamer CMS', 'http://antiref.com/?http://www.subdreamer.com', 'Thank you for choosing Subdreamer CMS!', '', '127.0.0.1')");
    }

    // Other delivered plugins
    $forumstats_id = GetPluginIDbyFolder('forum_stats');
    $users_online_id = GetPluginIDbyFolder('users_online');
    $latest_articles_id = GetPluginIDbyFolder('latest_articles');
    $random_media_id = GetPluginIDbyFolder('random_media');
    $search_engine_id = GetPluginIDbyFolder('search_engine');
    $tagcloud_id = GetPluginIDbyFolder('tagcloud');
    $latest_images_id = GetPluginIDbyFolder('latest_images');

    // ****** Custom Plugins ******
    $cp_insert = "INSERT INTO {customplugins} (`custompluginid`, `name`, `displayname`, `plugin`, `includefile`, `settings`) VALUES ";
    $DB->query($cp_insert ."(NULL, 'Contact Text', 'Contact us!', 'Need to contact us?<br /><br /><a href=\"index.php?categoryid=".(int)$page_contact."\">Click here!</a>', '', 17)");
    $cp_contact = $DB->insert_id();

    $DB->query($cp_insert ."(NULL, 'Download Manager Tag Cloud', 'Downloads Tags', '', 'plugins/download_manager/dlm_tagcloud.php', 17)");
    $cp_dlm = $DB->insert_id();

    $mg_cp_recent1 = $mg_cp_recent2 = '1';
    if($media_gal_id = max(1,GetPluginID('Media Gallery')))
    {
      $DB->query($cp_insert ."(NULL, 'Recent Galleries 1', 'Recent Galleries 1', '', 'plugins/media_gallery/recentsections.php', 17)");
      $mg_cp_recent1 = $DB->insert_id();
      $DB->query($cp_insert ."(NULL, 'Recent Galleries 2', 'Recent Galleries 2', '', 'plugins/media_gallery/recentsections2.php', 17)");
      $mg_cp_recent2 = $DB->insert_id();

      if(!$DB->query_first('SELECT imageid FROM {p'.$media_gal_id.'_images} LIMIT 1'))
      $DB->query("INSERT INTO {p".$media_gal_id ."_images}".
                 " (`imageid`, `sectionid`, `activated`, `filename`, `allowcomments`, `showauthor`, `author`, `title`,
                    `description`, `viewcount`, `datecreated`, `px_width`, `px_height`, `folder`, `allow_ratings`, `media_type`, `media_url`)
                    VALUES(NULL, 1, 1, '1.jpg', 1, 1, 'CMS', 'Demo Image', '', 1, %d, 1024, 768, '', 1, 0, '')",
                    TIME_NOW);
    }

    // ***********************************************
    // Set some fixed permissions for all usergroups:
    // ***********************************************
    $cp_list = implode(',', array($cp_contact, $cp_dlm, $mg_cp_recent1, $mg_cp_recent2));

    // Admins
    $pagelist = implode(',', array($search_results, $tags_results, $page_downloads, $page_media_gallery,
                                   $plugins_sampler, $page_image_gallery, $page_form_wizard,
                                   $page_forum, $page_contact, $page_profile));
    $pagelist_menu = implode(',', array($page_downloads, $page_media_gallery,
                                   $plugins_sampler, $page_image_gallery, $page_form_wizard,
                                   $page_forum, $page_contact, $page_profile));
    $DB->query("UPDATE {usergroups} SET categoryviewids = '1,%s', categorymenuids = '1,%s',".
               " custompluginviewids = '%s'".
               ' WHERE usergroupid < 5',
               $pagelist, $pagelist_menu, $cp_list);

    // Moderators
    $DB->query("UPDATE {usergroups} SET adminaccess = 1, pluginmoderateids = '2".
               ($forum_id?','.$forum_id:'').($dlm_id?','.$dlm_id:'')."', pluginadminids = '',".
               " pluginsubmitids = '', commentaccess = 1, offlinecategoryaccess = 0,".
               " admin_access_pages = 'comments'".
               ' WHERE usergroupid = 2');

    // Disallow most for Registered Users/Guests/Banned
    $DB->query("UPDATE {usergroups} SET adminaccess = 0, pluginmoderateids = '', pluginsubmitids = '',".
               " pluginadminids = '', admin_access_pages = '', commentaccess = 0, offlinecategoryaccess = 0".
               ' WHERE usergroupid >= 3');

    // Shared between Guests/Banned
    $DB->query("UPDATE {usergroups} SET categoryviewids = '1', categorymenuids = '1',".
               " plugindownloadids = '', plugincommentids = '', custompluginviewids = ''".
               ' WHERE usergroupid >= 4');

    // Guests at last
    $pagelist = implode(',', array($search_results, $tags_results, $page_downloads, $page_media_gallery,
                                   $plugins_sampler, $page_image_gallery, $page_form_wizard,
                                   $page_forum, $page_contact, $page_register));
    $pagelist_menu = implode(',', array($page_downloads, $page_media_gallery,
                                   $plugins_sampler, $page_image_gallery, $page_form_wizard,
                                   $page_forum, $page_contact, $page_register));
    $DB->query("UPDATE {usergroups} SET categoryviewids = '1,%s', categorymenuids = '1,%s',".
               " custompluginviewids = '%s', pluginsubmitids = '6,10,12'".
               ' WHERE usergroupid = 4',
               $pagelist, $pagelist_menu, $cp_list);

    // ****** Pagesort with Custom-/Plugins ******
    $DB->query("INSERT INTO {pagesort} (`categoryid`, `pluginid`, `displayorder`) VALUES
    (1, '$search_engine_id', 1),
    (1, '2', 2),
    (1, '1', 3),
    (1, '1', 4),
    (1, '$tagcloud', 5),
    (1, '$article_archive_id', 6),
    (1, '$latest_articles_id', 7),
    (1, '$latest_images_id', 8),
    (1, 'c$cp_contact', 9),
    (1, '10', 10),
    ($page_downloads, '$dlm_id', 1),
    ($page_downloads, 'c$cp_dlm', 2),
    ($page_downloads, '1', 3),
    ($page_downloads, '1', 4),
    ($page_downloads, '1', 5),
    ($page_downloads, '$latest_files_id', 6),
    ($page_downloads, '$tagcloud', 7),
    ($page_downloads, '10', 8),
    ($page_downloads, '1', 9),
    ($page_downloads, '1', 10),

    ($plugins_sampler, '$calendar2_id', 1),
    ($plugins_sampler, '$event_manager_id', 2),
    ($plugins_sampler, '1', 3),
    ($plugins_sampler, '$linkdir_id', 4),
    ($plugins_sampler, '$guestbook_id', 5),
    ($plugins_sampler, '$random_media_id', 6),
    ($plugins_sampler, '10', 7),
    ($plugins_sampler, '1', 8),
    ($plugins_sampler, '1', 9),
    ($plugins_sampler, '1', 10),

        ($page_image_gallery, '17', 1),
        ($page_image_gallery, '1', 2),
        ($page_image_gallery, '1', 3),
        ($page_image_gallery, '1', 4),
        ($page_image_gallery, '1', 5),
        ($page_image_gallery, '1', 6),
        ($page_image_gallery, '10', 7),
        ($page_image_gallery, '1', 8),
        ($page_image_gallery, '1', 9),
        ($page_image_gallery, '1', 10),

        ($page_form_wizard, '$form_wizard_id', 1),
        ($page_form_wizard, '1', 2),
        ($page_form_wizard, '1', 3),
        ($page_form_wizard, '1', 4),
        ($page_form_wizard, '1', 5),
        ($page_form_wizard, '1', 6),
        ($page_form_wizard, '10', 7),
        ($page_form_wizard, '1', 8),
        ($page_form_wizard, '1', 9),
        ($page_form_wizard, '1', 10),

    ($search_results, '$search_engine_id', 1),
    ($search_results, '2', 2),
    ($search_results, '$forum_id', 3),
    ($search_results, '1', 4),
    ($search_results, '1', 5),
    ($search_results, '$article_archive_id', 6),
    ($search_results, '10', 7),
    ($search_results, '1', 8),
    ($search_results, '1', 9),
    ($search_results, '1', 10),

    ($tags_results, '$search_engine_id', 1),
    ($tags_results, '2', 2),
    ($tags_results, '$forum_id', 3),
    ($tags_results, '1', 4),
    ($tags_results, '$tagcloud_id', 5),
    ($tags_results, '1', 6),
    ($tags_results, '$article_archive_id', 7),
    ($tags_results, '10', 8),
    ($tags_results, '1', 9),
    ($tags_results, '1', 10),

    ($page_media_gallery, '$media_gal_id', 1),
    ($page_media_gallery, '1', 2),
    ($page_media_gallery, '1', 3),
    ($page_media_gallery, '1', 4),
    ($page_media_gallery, '1', 5),
    ($page_media_gallery, '1', 6),
    ($page_media_gallery, 'c$mg_cp_recent1', 7),
    ($page_media_gallery, 'c$mg_cp_recent2', 8),
    ($page_media_gallery, '10', 9),
    ($page_media_gallery, '1', 10),

    ($page_forum, '$forum_id', 1),
    ($page_forum, '$users_online_id', 2),
    ($page_forum, '$forumstats_id', 3),
    ($page_forum, '8', 4),
    ($page_forum, '9', 5),

    ($page_contact, '6', 1),
    ($page_contact, '1', 2),
    ($page_contact, '1', 3),
    ($page_contact, '1', 4),
    ($page_contact, '10', 5),

    ($page_register, '12', 1),
    ($page_register, '1', 2),
    ($page_register, '1', 3),
    ($page_register, '1', 4),
    ($page_register, '10', 5),

    ($page_profile, '11', 1),
    ($page_profile, '1', 2),
    ($page_profile, '1', 3),
    ($page_profile, '1', 4),
    ($page_profile, '10', 5)");

    // store register path, login path, control panel path in database to avoid extra queries
    $DB->query("UPDATE {mainsettings} SET value = '1' WHERE varname = 'user_login_panel_page_id'");
    $DB->query("UPDATE {mainsettings} SET value = '%d' WHERE varname = 'user_profile_page_id'", $page_profile);
    $DB->query("UPDATE {mainsettings} SET value = '%d' WHERE varname = 'user_registration_page_id'", $page_register);
  }

  return true;

} //DO NOT REMOVE
// **************************** END OF DEMO DATA ******************************


//SD370: finally allow install from renamed "setup" folder, e.g. "setup_"
$website_url = "http://" . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['SCRIPT_NAME']));
$website_url = rtrim($website_url,'\\/ ').'/';

// ###############################################################################
// DROP TABLES
// ###############################################################################

$DB->query("DROP TABLE IF EXISTS {adminphrases}");
$DB->query("DROP TABLE IF EXISTS {categories}");
$DB->query("DROP TABLE IF EXISTS {comments}");
$DB->query("DROP TABLE IF EXISTS {comments_count}");
$DB->query("DROP TABLE IF EXISTS {customplugins}");
$DB->query("DROP TABLE IF EXISTS {designs}");
$DB->query("DROP TABLE IF EXISTS {mainsettings}");
$DB->query("DROP TABLE IF EXISTS {p_forum_forums}");
$DB->query("DROP TABLE IF EXISTS {p_forum_posts}");
$DB->query("DROP TABLE IF EXISTS {p_forum_topics}");
$DB->query("DROP TABLE IF EXISTS {p2_news}");
$DB->query("DROP TABLE IF EXISTS {p2_settings}");
$DB->query("DROP TABLE IF EXISTS {p17_images}");
$DB->query("DROP TABLE IF EXISTS {p17_sections}");
$DB->query("DROP TABLE IF EXISTS {pagesort}");
$DB->query("DROP TABLE IF EXISTS {phrases}");
$DB->query("DROP TABLE IF EXISTS {plugins}");
$DB->query("DROP TABLE IF EXISTS {pluginsettings}");
$DB->query("DROP TABLE IF EXISTS {ratings}");
$DB->query("DROP TABLE IF EXISTS {sessions}");
$DB->query("DROP TABLE IF EXISTS {skins}");
$DB->query("DROP TABLE IF EXISTS {skin_bak_cat}");
$DB->query("DROP TABLE IF EXISTS {skin_bak_pgs}");
$DB->query("DROP TABLE IF EXISTS {skin_css}");
$DB->query("DROP TABLE IF EXISTS {tags}");
$DB->query("DROP TABLE IF EXISTS {usergroups}");
$DB->query("DROP TABLE IF EXISTS {users}");


// ###############################################################################
// CREATE TABLES
// ###############################################################################

$DB->query("CREATE TABLE {categories} (
  `categoryid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parentid` int(11) unsigned NOT NULL DEFAULT 0,
  `designid` int(11) unsigned NOT NULL DEFAULT 0,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `urlname` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sslurl` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `displayorder` int(11) unsigned NOT NULL DEFAULT 0,
  `link` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `target` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `image` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `hoverimage` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `menuwidth` smallint(5) unsigned NOT NULL DEFAULT 0,
  `metadescription` text COLLATE utf8_unicode_ci,
  `metakeywords` text COLLATE utf8_unicode_ci,
  `appendkeywords` tinyint(1) NOT NULL DEFAULT 0,
  `title` varchar(250) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`categoryid`),
  KEY `parentid` (`parentid`),
  KEY `displayorder` (`displayorder`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE {comments} (
  `commentid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pluginid` int(11) unsigned NOT NULL DEFAULT 0,
  `objectid` int(11) unsigned NOT NULL DEFAULT 0,
  `date` int(11) unsigned NOT NULL DEFAULT 0,
  `username` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `comment` text COLLATE utf8_unicode_ci,
  `approved` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`commentid`),
  KEY `pluginid` (`pluginid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE {customplugins} (
  `custompluginid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `displayname` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `plugin` mediumtext COLLATE utf8_unicode_ci,
  `includefile` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `settings` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`custompluginid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE {designs} (
  `designid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `skinid` int(11) unsigned NOT NULL DEFAULT 0,
  `maxplugins` int(11) unsigned NOT NULL DEFAULT 0,
  `designpath` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `imagepath` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `layout` mediumtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`designid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE {mainsettings} (
  `settingid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `varname` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `groupname` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `input` mediumtext COLLATE utf8_unicode_ci,
  `title` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` mediumtext COLLATE utf8_unicode_ci,
  `value` mediumtext COLLATE utf8_unicode_ci,
  `displayorder` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`settingid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE IF NOT EXISTS {p2_news} (
  `articleid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `categoryid` int(11) unsigned NOT NULL DEFAULT 0,
  `settings` int(11) unsigned NOT NULL DEFAULT 0,
  `views` int(11) unsigned NOT NULL DEFAULT 0,
  `displayorder` int(11) unsigned NOT NULL DEFAULT 0,
  `datecreated` int(11) unsigned NOT NULL DEFAULT 0,
  `dateupdated` int(11) unsigned NOT NULL DEFAULT 0,
  `datestart` int(11) unsigned NOT NULL DEFAULT 0,
  `dateend` int(11) unsigned NOT NULL DEFAULT 0,
  `author` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `metadescription` text COLLATE utf8_unicode_ci,
  `metakeywords` text COLLATE utf8_unicode_ci,
  `description` text COLLATE utf8_unicode_ci,
  `article` mediumtext COLLATE utf8_unicode_ci,
  `seo_title` varchar(250) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`articleid`),
  KEY `categoryid` (`categoryid`),
  KEY `datecreated` (`datecreated`),
  KEY `dateupdated` (`dateupdated`),
  KEY `datestart` (`datestart`),
  KEY `dateend` (`dateend`),
  KEY `displayorder` (`displayorder`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE IF NOT EXISTS {p2_settings} (
  `categoryid` int(11) unsigned NOT NULL DEFAULT 0,
  `maxarticles` int(11) unsigned NOT NULL DEFAULT '10',
  `sorting` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Newest First',
  `multiplepages` tinyint(1) NOT NULL DEFAULT 0,
  KEY `categoryid` (`categoryid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE {p17_images} (
  `imageid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sectionid` int(11) unsigned NOT NULL DEFAULT 0,
  `activated` tinyint(1) NOT NULL DEFAULT 0,
  `filename` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `allowsmilies` tinyint(1) NOT NULL DEFAULT 0,
  `allowcomments` tinyint(1) NOT NULL DEFAULT 0,
  `showauthor` tinyint(1) NOT NULL DEFAULT 0,
  `author` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8_unicode_ci,
  `viewcount` int(11) unsigned NOT NULL DEFAULT 0,
  `datecreated` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`imageid`),
  KEY `sectionid` (`sectionid`),
  KEY `activated` (`activated`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE {p17_sections} (
  `sectionid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parentid` int(11) unsigned NOT NULL DEFAULT 0,
  `activated` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8_unicode_ci,
  `sorting` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `imageid` int(11) unsigned DEFAULT NULL,
  `datecreated` int(11) unsigned NOT NULL DEFAULT 0,
  `imagecount` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`sectionid`),
  KEY `parentid` (`parentid`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE {pagesort} (
  `categoryid` int(11) unsigned NOT NULL DEFAULT 0,
  `pluginid` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `displayorder` int(11) unsigned NOT NULL DEFAULT 0,
  KEY `categoryid` (`categoryid`),
  KEY `pluginid` (`pluginid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

// don't give a default value to defaultphrase & customphrase
// text columns can not have a default value
$DB->query("CREATE TABLE {phrases} (
  `phraseid` int(11) NOT NULL AUTO_INCREMENT,
  `pluginid` int(11) NOT NULL DEFAULT 0,
  `varname` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `defaultphrase` text COLLATE utf8_unicode_ci,
  `customphrase` text COLLATE utf8_unicode_ci,
  `font` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `color` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `size` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `bold` tinyint(1) NOT NULL DEFAULT 0,
  `italic` tinyint(1) NOT NULL DEFAULT 0,
  `underline` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`phraseid`),
  KEY `pluginid` (`pluginid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE {plugins} (
  `pluginid` int(11) unsigned NOT NULL DEFAULT 0,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `displayname` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `version` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `pluginpath` varchar(254) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `settingspath` varchar(254) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `authorname` varchar(254) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `authorlink` int(11) unsigned NOT NULL DEFAULT 0,
  `settings` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`pluginid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE {pluginsettings} (
  `settingid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pluginid` int(11) unsigned NOT NULL DEFAULT 0,
  `groupname` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Options',
  `title` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` mediumtext COLLATE utf8_unicode_ci,
  `input` mediumtext COLLATE utf8_unicode_ci,
  `value` mediumtext COLLATE utf8_unicode_ci,
  `displayorder` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`settingid`),
  KEY `pluginid` (`pluginid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE {sessions} (
  `sessionid` char(32) COLLATE utf8_unicode_ci NOT NULL,
  `userid` int(11) unsigned NOT NULL DEFAULT 0,
  `ipaddress` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `useragent` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lastactivity` int(11) unsigned NOT NULL DEFAULT 0,
  `admin` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`sessionid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE {skins} (
  `skinid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `skin_engine` smallint(1) unsigned NOT NULL DEFAULT '1',
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `activated` tinyint(1) NOT NULL DEFAULT 0,
  `numdesigns` int(11) unsigned NOT NULL DEFAULT 0,
  `previewimage` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `authorname` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `authorlink` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `folder_name` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `doctype` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `starting_html_tag` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `starting_body_tag` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `head_include` mediumtext COLLATE utf8_unicode_ci,
  `css` mediumtext COLLATE utf8_unicode_ci,
  `prgm_css` mediumtext COLLATE utf8_unicode_ci,
  `header` mediumtext COLLATE utf8_unicode_ci,
  `footer` mediumtext COLLATE utf8_unicode_ci,
  `error_page` mediumtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`skinid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE {skin_bak_cat} (
  `skinid` int(11) unsigned NOT NULL DEFAULT 0,
  `categoryid` int(11) unsigned NOT NULL DEFAULT 0,
  `designid` int(11) unsigned NOT NULL DEFAULT 0,
  KEY `sbcat` (`skinid`,`categoryid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");

$DB->query("CREATE TABLE {skin_bak_pgs} (
  `skinid` int(11) unsigned NOT NULL DEFAULT 0,
  `categoryid` int(11) unsigned NOT NULL DEFAULT 0,
  `displayorder` int(11) unsigned NOT NULL DEFAULT 0,
  `pluginid` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  KEY `sbpgs` (`skinid`,`categoryid`,`displayorder`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE {usergroups} (
  `usergroupid` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `forumusergroupid` smallint(5) NOT NULL DEFAULT 0,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `adminaccess` tinyint(1) NOT NULL DEFAULT 0,
  `admin_access_pages` mediumtext COLLATE utf8_unicode_ci null,
  `commentaccess` tinyint(1) NOT NULL DEFAULT 0,
  `offlinecategoryaccess` tinyint(1) NOT NULL DEFAULT 0,
  `categoryviewids` text COLLATE utf8_unicode_ci null,
  `categorymenuids` text COLLATE utf8_unicode_ci null,
  `pluginviewids` text COLLATE utf8_unicode_ci null,
  `pluginsubmitids` text COLLATE utf8_unicode_ci null,
  `plugindownloadids` text COLLATE utf8_unicode_ci null,
  `plugincommentids` text COLLATE utf8_unicode_ci null,
  `pluginadminids` text COLLATE utf8_unicode_ci null,
  `pluginmoderateids` mediumtext COLLATE utf8_unicode_ci null,
  `custompluginviewids` text COLLATE utf8_unicode_ci null,
  `custompluginadminids` text COLLATE utf8_unicode_ci null,
  `banned` tinyint(1) NOT NULL DEFAULT '0',
  `displayname` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8_unicode_ci null,
  `color_online` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `display_online` tinyint(1) NOT NULL DEFAULT '0',
  `articles_author_mode` tinyint(1) NOT NULL DEFAULT '0',
  `maintain_customplugins` tinyint(1) NOT NULL DEFAULT '0',
  `require_vvc` tinyint(1) NOT NULL DEFAULT '0',
  `excerpt_mode` tinyint(1) NOT NULL DEFAULT '0',
  `excerpt_message` mediumtext COLLATE utf8_unicode_ci null,
  `excerpt_length` int(10) NOT NULL DEFAULT '200',
  PRIMARY KEY (`usergroupid`),
  KEY `forumusergroupid` (`forumusergroupid`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE {users} (
  `userid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `usergroupid` smallint(5) unsigned NOT NULL DEFAULT 0,
  `username` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `password` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` TEXT COLLATE utf8_unicode_ci,
  `banned` tinyint(1) NOT NULL DEFAULT 0,
  `activated` tinyint(1) NOT NULL DEFAULT 0,
  `validationkey` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `joindate` int(11) unsigned NOT NULL DEFAULT 0,
  `lastactivity` int(11) unsigned NOT NULL DEFAULT 0,
  `admin_notes` text COLLATE utf8_unicode_ci,
  `user_notes` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`userid`),
  KEY `usergroupid` (`usergroupid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");



// ###############################################################################
// INSERT DATA
// ###############################################################################

$DB->query("INSERT INTO {mainsettings} (`settingid`, `varname`, `groupname`, `input`, `title`, `description`, `value`, `displayorder`) VALUES
(NULL, 'sdurl', 'General Settings', 'text', 'Website URL', 'Please enter the full URL to your website:<br />This setting is required in order for your site to function correctly.', '" . $website_url . "', 1),
(NULL, 'enablewysiwyg', 'General Settings', 'yesno', 'Enable WYSIWYG Editor', 'Enable the WYSIWYG editor in the admin panel?', '1', 5),
(NULL, 'admincookietimeout', 'General Settings', 'text', 'Admin Timeout', 'Number of seconds of idle time before you are automatically logged out of the admin control panel (default 7200 = 2 hours)<br />Entering a value of 0 here will mean that your session will never timeout:', '7200', 4),
(NULL, 'siteactivation', 'Site Activation', '<select name=\\\\\"settings[\$setting[settingid]]\\\\\">\r\n  <option value=\\\\\"on\\\\\" \".(\$setting[value]==\"on\"  ? \"selected\" : \"\").\">On</option>\r\n  <option value=\\\\\"off\\\\\" \".(\$setting[value]==\"off\"? \"selected\" : \"\").\">Off</option>\r\n  </select>', 'Turn Site On/Off', 'You may want to turn off your site while performing updates or other types of maintenance.', 'on', 0),
(NULL, 'offmessage', 'Site Activation', 'textarea', 'Off Reason', 'You may provide a short description to your members explaining why your site has been switched off:', 'We are currently performing updates, please check back soon!', 0),
(NULL, 'websitetitle', 'General Settings', 'text', 'Website Title', 'Name your website:', 'Website Title', 2),
(NULL, 'categorytitle', 'SEO Settings', 'yesno', 'Combining Title Tag with Category Name', 'By selecting this option the name of the displayed Category will be added to your website''s title.', '1', 3),
(NULL, 'title_order', 'SEO Settings', '<select name=\\\\\"settings[\$setting[settingid]]\\\\\">\r\n   <option value=\\\\\"0\\\\\" \".(empty(\$setting[value])?\"selected\":\"\").\">Site - Category (default)</option>\r\n   <option value=\\\\\"1\\\\\" \".(\$setting[value]==\"1\"?\"selected\":\"\").\">Category - Site</option>\r\n   </select>', 'Title - Category Order', 'Display combination of site- and category title', '0', 4),
(NULL, 'title_separator', 'SEO Settings', 'text', 'Title - Category Separator', 'Separator text between site- and category title. Default: `<strong> - </strong>`<br />\r\n  If no separator is specified (i.e. it is left empty), the default is being used.', ' - ', 5),
(NULL, 'metakeywords', 'SEO Settings', 'text', 'Meta Keywords', 'Type in keywords separated by commas that describe your website. <br />\r\nThese keywords will help your site be listed in search engines:', 'Website Keywords', 7),
(NULL, 'metadescription', 'SEO Settings', 'text', 'Meta Description', 'Description of your website: <br />\r\nHelps your website''s position in search engines.', 'Website description', 6),
(NULL, 'copyrighttext', 'General Settings', 'text', 'Copyright', 'Enter your copyright information here:', '', 3),
(NULL, 'modrewrite', 'SEO Settings', 'yesno', 'Friendly URLs', 'If your server is running off of apache and has Mod Rewrite enabled, then you can turn this setting on to make your URLs more user friendly:', '0', 1),
(NULL, 'dateformat', 'Date and Time Options', 'text', 'Format For Date', 'Format in which the date is presented:<br /><br />See: <a href=''http://us2.php.net/manual/en/function.date.php'' target=''_blank''>http://us2.php.net/manual/en/function.date.php</a>', 'F j, Y', 0),
(NULL, 'timezoneoffset',     'Date and Time Options',
   '<select name=\\\\\"settings[\$setting[settingid]]\\\\\">
    <option value=\\\\\"-12\\\\\"  \".iif(\$setting[value]==\"-12\",  \"selected\", \"\").\">(GMT -12:00) Eniwetok, Kwajalein</option>
    <option value=\\\\\"-11\\\\\"  \".iif(\$setting[value]==\"-11\",  \"selected\", \"\").\">(GMT -11:00) Midway Island, Samoa</option>
    <option value=\\\\\"-10\\\\\"  \".iif(\$setting[value]==\"-10\",  \"selected\", \"\").\">(GMT -10:00) Hawaii</option>
    <option value=\\\\\"-9\\\\\"   \".iif(\$setting[value]==\"-9\",   \"selected\", \"\").\">(GMT -9:00) Alaska</option>
    <option value=\\\\\"-8\\\\\"   \".iif(\$setting[value]==\"-8\",   \"selected\", \"\").\">(GMT -8:00) Pacific Time (US &amp; Canada)</option>
    <option value=\\\\\"-7\\\\\"   \".iif(\$setting[value]==\"-7\",   \"selected\", \"\").\">(GMT -7:00) Mountain Time (US &amp; Canada)</option>
    <option value=\\\\\"-6\\\\\"   \".iif(\$setting[value]==\"-6\",   \"selected\", \"\").\">(GMT -6:00) Central Time (US &amp; Canada), Mexico City</option>
    <option value=\\\\\"-5\\\\\"   \".iif(\$setting[value]==\"-5\",   \"selected\", \"\").\">(GMT -5:00) Eastern Time (US &amp; Canada), Bogota, Lima</option>
    <option value=\\\\\"-4\\\\\"   \".iif(\$setting[value]==\"-4\",   \"selected\", \"\").\">(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz</option>
    <option value=\\\\\"-3.5\\\\\" \".iif(\$setting[value]==\"-3.5\", \"selected\", \"\").\">(GMT -3:30) Newfoundland</option>
    <option value=\\\\\"-3\\\\\"   \".iif(\$setting[value]==\"-3\",   \"selected\", \"\").\">(GMT -3:00) Brazil, Buenos Aires, Georgetown</option>
    <option value=\\\\\"-2\\\\\"   \".iif(\$setting[value]==\"-2\",   \"selected\", \"\").\">(GMT -2:00) Mid-Atlantic</option>
    <option value=\\\\\"-1\\\\\"   \".iif(\$setting[value]==\"-1\",   \"selected\", \"\").\">(GMT -1:00 hour) Azores, Cape Verde Islands</option>
    <option value=\\\\\"0\\\\\"    \".iif(\$setting[value]==\"0\",    \"selected\", \"\").\">(GMT) Western Europe Time, London, Lisbon, Casablanca</option>
    <option value=\\\\\"1\\\\\"    \".iif(\$setting[value]==\"1\",    \"selected\", \"\").\">(GMT +1:00 hour) Brussels, Copenhagen, Madrid, Paris</option>
    <option value=\\\\\"2\\\\\"    \".iif(\$setting[value]==\"2\",    \"selected\", \"\").\">(GMT +2:00) Kaliningrad, South Africa</option>
    <option value=\\\\\"3\\\\\"    \".iif(\$setting[value]==\"3\",    \"selected\", \"\").\">(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburg</option>
    <option value=\\\\\"3.5\\\\\"  \".iif(\$setting[value]==\"3.5\",  \"selected\", \"\").\">(GMT +3:30) Tehran</option>
    <option value=\\\\\"4\\\\\"    \".iif(\$setting[value]==\"4\",    \"selected\", \"\").\">(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi</option>
    <option value=\\\\\"4.5\\\\\"  \".iif(\$setting[value]==\"4.5\",  \"selected\", \"\").\">(GMT +4:30) Kabul</option>
    <option value=\\\\\"5\\\\\"    \".iif(\$setting[value]==\"5\",    \"selected\", \"\").\">(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent</option>
    <option value=\\\\\"5.5\\\\\"  \".iif(\$setting[value]==\"5.5\",  \"selected\", \"\").\">(GMT +5:30) Bombay, Calcutta, Madras, New Delhi</option>
    <option value=\\\\\"6\\\\\"    \".iif(\$setting[value]==\"6\",    \"selected\", \"\").\">(GMT +6:00) Almaty, Dhaka, Colombo</option>
    <option value=\\\\\"7\\\\\"    \".iif(\$setting[value]==\"7\",    \"selected\", \"\").\">(GMT +7:00) Bangkok, Hanoi, Jakarta</option>
    <option value=\\\\\"8\\\\\"    \".iif(\$setting[value]==\"8\",    \"selected\", \"\").\">(GMT +8:00) Beijing, Perth, Singapore, Hong Kong</option>
    <option value=\\\\\"9\\\\\"    \".iif(\$setting[value]==\"9\",    \"selected\", \"\").\">(GMT +9:00) Tokyo, Seoul, Osaka, Sapporo, Yakutsk</option>
    <option value=\\\\\"9.5\\\\\"  \".iif(\$setting[value]==\"9.5\",  \"selected\", \"\").\">(GMT +9:30) Adelaide, Darwin</option>
    <option value=\\\\\"10\\\\\"   \".iif(\$setting[value]==\"10\",   \"selected\", \"\").\">(GMT +10:00) Eastern Australia, Guam, Vladivostok</option>
    <option value=\\\\\"11\\\\\"   \".iif(\$setting[value]==\"11\",   \"selected\", \"\").\">(GMT +11:00) Magadan, Solomon Islands, New Caledonia</option>
    <option value=\\\\\"12\\\\\"   \".iif(\$setting[value]==\"12\",   \"selected\", \"\").\">(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka</option>
    </select>'
   , 'Default Time Zone Offset', 'Time zone offset for guests and new users. Do not take DST into consideration, rather use the next option to enable/disable DST.', '-8', 0),

(NULL, 'daylightsavings', 'Date and Time Options', 'yesno', 'Enable Daylight Savings', 'If Daylight Savings Time is currently in effect for the above time zone, enable this option so that guests will see the correct times', '0', 0),
(NULL, 'language', 'NA', '', 'Language', '', 'English.php|1.0|utf-8', 0),
(NULL, 'bfo', 'NA', '', 'bfo', '', '0', 0),
(NULL, 'sdversion', '', '', 'version', 'Subdreamer Version', '" . PRGM_VERSION . "', 0),
(NULL, 'sdversiontype', '', '', 'Version Type', 'The version type is either Basic or Pro. Do not change this value manually, it will cause problems.', 'Pro', 0),
(NULL, 'currentlogo', '', '', 'Current Logo', '', '<strong>Hello</strong> there.', 0),
(NULL, 'commentorder', 'Comment Options', '<select name=\\\\\"settings[\$setting[settingid]]\\\\\">\r\n  <option value=\\\\\"0\\\\\" \".(\$setting[value]==\"0\"?\"selected\":\"\").\">Oldest First</option>\r\n  <option value=\\\\\"1\\\\\" \".(\$setting[value]==\"1\"?\"selected\":\"\").\">Newest First</option></select>', 'Display Order', 'Display comments on frontpage sorted in ascending or descending order (default: Oldest First)?', '1', 0),
(NULL, 'com_show_dates', 'Comment Options', 'yesno', 'Show Comments Dates', 'Display the date of each comment (default: Yes)?', '1', 0),
(NULL, 'url_subcategories', 'SEO Settings', 'yesno', 'Subcategories in URLs', 'If set to Yes, URL''s will contain all subcategories leading to the target category.', '1', 2),
(NULL, 'technicalemail', 'Email Settings', 'text', 'Technical Email', 'Any errors or problems will be mailed to this address:<br />\r\nLeave blank if you do not wish to receive technical emails.<br />\r\nSeparate multiple addresses with commas.', '', 0),
(NULL, 'charset', 'Character Encoding', 'text', 'Character Set', 'Enter the character set of your website:<br />Default is UTF-8.', 'UTF-8', 1),
(NULL, 'db_charset', 'Character Encoding', 'text', 'Database Character Set', 'Enter the character set of your database:<br />The default for the CMS is <b>utf8</b>.', 'utf8', 2)");

$DB->query("INSERT INTO {phrases} (`phraseid`, `pluginid`, `varname`, `defaultphrase`) VALUES
(NULL, 1, 'Sunday', 'Sunday'),
(NULL, 1, 'Monday', 'Monday'),
(NULL, 1, 'Tuesday', 'Tuesday'),
(NULL, 1, 'Wednesday', 'Wednesday'),
(NULL, 1, 'Thursday', 'Thursday'),
(NULL, 1, 'Friday', 'Friday'),
(NULL, 1, 'Saturday', 'Saturday'),
(NULL, 1, 'Sun', 'Sun'),
(NULL, 1, 'Mon', 'Mon'),
(NULL, 1, 'Tue', 'Tue'),
(NULL, 1, 'Wed', 'Wed'),
(NULL, 1, 'Thu', 'Thu'),
(NULL, 1, 'Fri', 'Fri'),
(NULL, 1, 'Sat', 'Sat'),
(NULL, 1, 'January', 'January'),
(NULL, 1, 'February', 'February'),
(NULL, 1, 'March', 'March'),
(NULL, 1, 'April', 'April'),
(NULL, 1, 'May', 'May'),
(NULL, 1, 'June', 'June'),
(NULL, 1, 'July', 'July'),
(NULL, 1, 'August', 'August'),
(NULL, 1, 'September', 'September'),
(NULL, 1, 'October', 'October'),
(NULL, 1, 'November', 'November'),
(NULL, 1, 'December', 'December'),
(NULL, 1, 'Jan', 'Jan'),
(NULL, 1, 'Feb', 'Feb'),
(NULL, 1, 'Mar', 'Mar'),
(NULL, 1, 'Apr', 'Apr'),
(NULL, 1, 'May', 'May'),
(NULL, 1, 'Jun', 'Jun'),
(NULL, 1, 'Jul', 'Jul'),
(NULL, 1, 'Aug', 'Aug'),
(NULL, 1, 'Sep', 'Sep'),
(NULL, 1, 'Oct', 'Oct'),
(NULL, 1, 'Nov', 'Nov'),
(NULL, 1, 'Dec', 'Dec'),
(NULL, 1, 'enter_comment_name', 'Please fill out the name field.'),
(NULL, 1, 'enter_comment', 'Please fill out the comment field.'),
(NULL, 1, 'repeat_comment', 'Repeat comment not posted.'),
(NULL, 1, 'users_name', 'User\'s Name:'),
(NULL, 1, 'your_name', 'Your Name:'),
(NULL, 1, 'comment', 'Comment:'),
(NULL, 1, 'update_comment', 'Update Comment'),
(NULL, 1, 'post_comment', 'Post Comment'),
(NULL, 1, 'comments', 'Comments:'),
(NULL, 1, 'edit', 'Edit'),
(NULL, 1, 'login_post_comments', 'You must be logged in to post comments.'),
(NULL, 1, 'login_view_page', 'Sorry, you must be logged in to view this page!'),
(NULL, 1, 'wrong_password', 'Wrong Username or Password'),
(NULL, 1, 'wrong_username', 'Wrong Username'),
(NULL, 1, 'please_enter_username', 'Please enter a username.'),
(NULL, 1, 'you_are_banned', 'You have been banned'),
(NULL, 1, 'ip_banned', 'Your IP address has been banned'),
(NULL, 1, 'no_view_access', 'Sorry, your account does not have access to this section.'),
(NULL, 1, 'no_post_access', 'Sorry, your account does not have access to submit information.'),
(NULL, 1, 'no_download_access', 'Sorry, your account does not have access to downloads.'),
(NULL, 1, 'no_comment_access', 'Sorry, your account does not have access to post comments.'),
(NULL, 1, 'website_offline', 'Website Offline'),
(NULL, 1, 'page_not_found', 'Page Not Found!'),
(NULL, 1, 'redirect_to_homepage', 'Click here to redirect to the homepage.'),
(NULL, 1, 'not_yet_activated', 'Your haven\'t activated your account. Check your email for the activation instructions'),
(NULL, 2, 'published', 'Published on '),
(NULL, 2, 'updated', 'Updated on '),
(NULL, 2, 'by', 'by'),
(NULL, 2, 'read_more', 'Read More...'),
(NULL, 2, 'print', 'Print'),
(NULL, 2, 'comments', 'Comments'),
(NULL, 3, 'by', 'By'),
(NULL, 3, 'read_more', 'Read More...'),
(NULL, 3, 'published', 'Published: '),
(NULL, 3, 'updated', 'Updated: '),
(NULL, 6, 'full_name', 'Full Name:'),
(NULL, 6, 'your_email', 'Your Email:'),
(NULL, 6, 'subject', 'Subject:'),
(NULL, 6, 'message', 'Message:'),
(NULL, 6, 'send_message', 'Send Message'),
(NULL, 6, 'invalid_email', 'Invalid Email entered!'),
(NULL, 6, 'email_sent', 'Thank you, your message has been sent.'),
(NULL, 6, 'email_not_sent', 'A system error occured. Your message was not sent.'),
(NULL, 6, 'empty_fields', 'Error: One of the required field(s) is empty.'),
(NULL, 10, 'my_account', 'My Account'),
(NULL, 10, 'welcome_back', 'Welcome back'),
(NULL, 10, 'logout', 'Log Out'),
(NULL, 10, 'username', 'Username:'),
(NULL, 10, 'password', 'Password:'),
(NULL, 10, 'remember_me', 'Remember Me'),
(NULL, 10, 'login', 'Login'),
(NULL, 10, 'not_registered', 'Not registered?'),
(NULL, 10, 'register_now', 'Register now!'),
(NULL, 10, 'forgot_password', 'Forgot your password?'),
(NULL, 10, 'admin_panel', 'Admin Panel'),
(NULL, 11, 'no_changes_entered', 'No Changes Entered!'),
(NULL, 11, 'enter_valid_password', 'Please enter a new password that consists only of letters and numbers.'),
(NULL, 11, 'password_unmatched', 'The new password you entered did not match the new confirmed password.'),
(NULL, 11, 'enter_valid_email', 'Please enter a valid email address.'),
(NULL, 11, 'email_already_exists', 'email already exists, please try another.'),
(NULL, 11, 'profile_updated', 'User Profile Updated!'),
(NULL, 11, 'new_password', 'New Password:'),
(NULL, 11, 'confirm_password', 'Confirm New Password:'),
(NULL, 11, 'new_email', 'New Email:'),
(NULL, 11, 'update_profile', 'Update Profile'),
(NULL, 11, 'visit_cp', 'Click here to visit your control panel.'),
(NULL, 11, 'confirm_email', 'Confirm New Email:'),
(NULL, 11, 'email_unmatched', 'The new email you entered did not match the new confirmed email.'),
(NULL, 11, 'user_name', 'Username:'),
(NULL, 12, 'user_name', 'User Name:'),
(NULL, 12, 'password', 'Password:'),
(NULL, 12, 'password_again', 'Enter Password Again:'),
(NULL, 12, 'email', 'Email:'),
(NULL, 12, 'email_again', 'Enter Email Again:'),
(NULL, 12, 'register', 'Register'),
(NULL, 12, 'enter_alnum_username', 'Please enter a username that consists only of letters and numbers.'),
(NULL, 12, 'password_unmatched', 'Your confirmed password does not match the entered password.'),
(NULL, 12, 'unvalid_email', 'Please enter a valid email address.'),
(NULL, 12, 'email_unmatched', 'The email you entered did not match the confirmed email.'),
(NULL, 12, 'username_exists', 'Username already exists, please try another.'),
(NULL, 12, 'email_exists', 'Email already exists, please try another.'),
(NULL, 12, 'register_success', 'Thank you for registering!'),
(NULL, 12, 'already_logged_in', 'You are already logged in as:'),
(NULL, 12, 'logout', 'Click here if you wish to log out.'),
(NULL, 12, 'register_now', 'Click here to Register!'),
(NULL, 12, 'email_banned', 'Your Email address has been banned'),
(NULL, 12, 'pwd_reset', 'Reset My Password'),
(NULL, 12, 'email_not_found', 'No account could be found that matched the entered email address:'),
(NULL, 12, 'email_subject', 'Your new Password'),
(NULL, 12, 'email_message', 'Here is your new password: '),
(NULL, 12, 'password_reset_success', 'Your password was reset successfully. An email will arrive shortly with your new login details.'),
(NULL, 12, 'activation_required', 'Thank you for registering. An email has been sent to your email address with instructions on how to activate your new account'),
(NULL, 12, 'email_subject_activation', 'Thanks for registering'),
(NULL, 12, 'email_message_activation', 'Thanks for registering with us. Please click the following link to activate your account.'),
(NULL, 12, 'validation_key_not_found', 'Your account was not found. Please ensure you copied the link correctly.'),
(NULL, 12, 'already_validated', 'Your account has already been activated. You may login using your username and password'),
(NULL, 12, 'validation_success', 'Thank you for activating your account. You may now login using your username and password'),
(NULL, 17, 'sections', 'Sections:'),
(NULL, 17, 'no_gif_support', 'Images with the .gif extension are not supported.'),
(NULL, 17, 'submitted_by', 'Submitted by:'),
(NULL, 17, 'submit_an_image', 'Submit an image to this section'),
(NULL, 17, 'submitting_image', 'Submitting Image'),
(NULL, 17, 'your_name', 'Your Name:'),
(NULL, 17, 'description', 'Description:'),
(NULL, 17, 'thumbnail', 'Thumbnail:'),
(NULL, 17, 'image', 'Image:'),
(NULL, 17, 'submit_image', 'Submit Image'),
(NULL, 17, 'image_title', 'Title:'),
(NULL, 17, 'enter_title', 'Please enter a title.'),
(NULL, 17, 'enter_author', 'Please enter the author\'s name.'),
(NULL, 17, 'select_image', 'Please select an image.'),
(NULL, 17, 'select_thumbnail', 'Please select a thumbnail.'),
(NULL, 17, 'enter_description', 'Please enter a description.'),
(NULL, 17, 'image_submitted', 'Thank you! Your image has been submitted. '),
(NULL, 17, 'previous_image', '&laquo; Previous Image'),
(NULL, 17, 'next_image', 'Next Image &raquo;'),
(NULL, 17, 'invalid_image_type', 'Invalid image type.'),
(NULL, 17, 'views', 'Views'),
(NULL, 17, 'comments', 'Comments'),
(NULL, 17, 'comment', 'Comment'),
(NULL, 17, 'image2', 'Image'),
(NULL, 17, 'images', 'Images'),
(NULL, 17, 'images2', 'Images:'),
(NULL, 17, 'notify_email_from', 'Image Gallery Plugin'),
(NULL, 17, 'notify_email_subject', 'New image submitted to your website!'),
(NULL, 17, 'notify_email_message', 'A new image has been submitted to your image gallery.'),
(NULL, 17, 'notify_email_author', 'Author'),
(NULL, 17, 'notify_email_title', 'Title'),
(NULL, 17, 'notify_email_description', 'Description'),
(NULL, 17, 'imagesize_error', 'No image uploaded or image filesize is too large.'),
(NULL, 17, 'thumbsize_error', 'No thumbnail uploaded or thumbnail filesize is too large.'),
(NULL, 17, 'image_submit_hint1', 'Uploaded images may have one of these file extensions: #extensions#'),
(NULL, 17, 'image_submit_hint2', 'The maximum attached file size is: #size#'),
(NULL, 17, 'invalid_image_ext_error', 'The uploaded file\'s extension is not allowed!'),
(NULL, 17, 'invalid_image_upload', 'Invalid image upload!'),
(NULL, 17, 'invalid_thumb_upload', 'Invalid thumbnail upload!'),
(NULL, 17, 'submit_offline', 'Image upload currently deactivated.')");

// DO NOT CHANGE VERSION NUMBERS BELOW: UPGRADES NEED THOSE!
$link = 'http://www.subdreamer.com/';
$DB->query("INSERT INTO {plugins} (`pluginid`, `name`, `displayname`, `version`, `pluginpath`, `settingspath`, `authorname`, `authorlink`, `settings`) VALUES
(1, '--empty--', '', '', 'p1_empty/empty.php', '', 'subdreamer_web', 1, 17),
(2, 'Articles', '', '2.6.0', 'p2_news/news.php', 'p2_news/p2_settings.php', 'subdreamer_web', '$link', 17),
(3, 'Latest Articles', 'Latest Articles', '2.6.0', 'p3_latestnews/latestnews.php', 'p3_latestnews/p3_settings.php', 'subdreamer_web', '$link', 17),
(6, 'Contact Form', 'Contact us!', '2.6.0', 'p6_contact_form/contactform.php', 'p6_contact_form/p6_settings.php', 'subdreamer_web', '$link', 19),
(10, 'User Login Panel', 'Login Panel', '2.6.0', 'p10_mi_loginpanel/loginpanel.php', 'p10_mi_loginpanel/p10_settings.php', 'subdreamer_web', '$link', 19),
(11, 'User Profile', 'User CP', '2.6.0', 'p11_mi_usercp/usercp.php', 'p11_mi_usercp/p11_settings.php', 'subdreamer_web', '$link', 19),
(12, 'User Registration', 'Registration', '2.6.0', 'p12_mi_registration/register.php', 'p12_mi_registration/p12_settings.php', 'subdreamer_web', '$link', 19),
(17, 'Image Gallery', 'Image Gallery', '2.6.0', 'p17_image_gallery/imagegallery.php', 'p17_image_gallery/p17_settings.php', 'subdreamer_web', '$link', 59)");
unset($link);

$DB->query("INSERT INTO {pluginsettings} (`settingid`, `pluginid`, `groupname`, `title`, `description`, `input`, `value`, `displayorder`) VALUES
(NULL, 3, 'Options', 'Limit', 'The Latest News plugin will display links to the most recent news on your site. Enter the number of links to be shown:', 'text', '10', 1),
(NULL, 3, 'Options', 'Category Targeting', 'Only display the latest news of the category which the latest news plugin resides in. Include Categories or Matching Categories will not work if targeting is on.', 'yesno', '0', 2),
(NULL, 3, 'Options', 'Include Categories', 'Enter the ID\'s of the categories you want to select latest news from, separate values with comma. Leave empty to select news from all categories. It can also be used together with the Matching Categories option.', 'text', '', 3),
(NULL, 3, 'Options', 'Sorting', 'How would you like your articles to be sorted (the latest news plugin always selects the latest news, but this option makes it possible to sort within those articles and within groups if you are using the Grouping option below)?',
'<select name=\\\\\"settings[\$setting[settingid]]\\\\\">\r\n
<option value=\\\\\"newest\\\\\" \".(\$setting[value]==\"newest\" ? \"selected\" : \"\").\">Newest First</option>\r\n
<option value=\\\\\"oldest\\\\\" \".(\$setting[value]==\"oldest\" ? \"selected\" : \"\").\">Oldest First</option>\r\n
<option value=\\\\\"alphaAZ\\\\\" \".(\$setting[value]==\"alphaAZ\" ? \"selected\" : \"\").\">Alphabetically A-Z</option>\r\n
<option value=\\\\\"alphaZA\\\\\" \".(\$setting[value]==\"alphaZA\" ? \"selected\" : \"\").\">Alphabetically Z-A</option>\r\n
<option value=\\\\\"authornameAZ\\\\\" \".(\$setting[value]==\"authornameAZ\" ? \"selected\" : \"\").\">Author Name A-Z</option>\r\n
<option value=\\\\\"authornameZA\\\\\" \".(\$setting[value]==\"authornameZA\" ? \"selected\" : \"\").\">Author Name Z-A</option>\r\n
</select>', 'newest', 5),
(NULL, 3, 'Options', 'Display Category Name', 'Display category name to the right of each news title?', 'yesno', '0', 6),
(NULL, 3, 'Options', 'Display Description', 'Display description under each news title?', 'yesno', '0', 7),
(NULL, 3, 'Options', 'Read More', 'Display \'Read more...\' link for each news article (it will use the language settings for the news plugin)?', 'yesno', '0', 8),
(NULL, 3, 'Options', 'Display Author', 'Display author name under each news article?', 'yesno', '0', 9),
(NULL, 3, 'Options', 'Display Creation Date', 'Display creation date under each news article?', 'yesno', '0', 10),
(NULL, 3, 'Options', 'Display Updated Date', 'Display updated date under each news article?', 'yesno', '0', 11),
(NULL, 3, 'Options', 'Bold Title', 'Do you want the title of each news article to be bold?', 'yesno', '0', 14),
(NULL, 3, 'Options', 'Title Link', 'Do you want to convert the title of each news article into a link?', 'yesno', '1', 15),
(NULL, 3, 'Options', 'Category Link', 'Do you want to use the category name as a link to the category? This will only work if you group articles by category name or if you choose do display category names to the right of each article.', 'yesno', '0', 17),
(NULL, 3, 'Options', 'Display Pagination', 'Display pagination for multiple pages.<br />Default value: Yes?', 'yesno', '1', 24),
(NULL, 6, 'Options', 'Email', 'Enter the email address where the submitted contact forms should be sent to:<br />\r\nSeparate multiple addresses with commas.', 'text', '', 1),
(NULL, 6, 'Options', 'Contact Form Title', 'Title for the contact form:', 'wysiwyg', '', 5),
(NULL, 6, 'Attachment Options', 'Allow Attachments', 'Allow users to send you attachments?', 'yesno', '0', 2),
(NULL, 6, 'Attachment Options', 'Allow Guest Attachments', 'Allow guests to submit attachments (default: No)?', 'yesno', '0', 2),
(NULL, 6, 'Attachment Options', 'Allowed File Extensions', 'You can restrict the files which can be attached by your users by entering a list of file extensions, separated by commas (example: <strong>zip, jpg, gif</strong>). If left blank/empty, all files are allowed.', 'text', '', 3),
(NULL, 6, 'Attachment Options', 'Max Attachment Size', 'You can specify here a maximum size for attached files. The size should be in <strong>Bytes</strong> so 1MB would be <strong>1048576</strong><br />Note: The actual maximum size is limited by your PHP configuration.', 'text', '1048576', 4),
(NULL, 10, 'Login Panel Options', 'Display Admin Link', 'Display a link to the admin panel in the login panel for those with admin access?', 'yesno', '1', 4),
(NULL, 10, 'Login Panel Options', 'Display My Account Link', 'Display a link to the My Account page in the Login Panel?', 'yesno', '1', 5),
(NULL, 10, 'Login Panel Options', 'Display Registration Link', 'Display a link to the Registration page in the Login Panel?', 'yesno', '1', 6),
(NULL, 10, 'Login Panel Options', 'Display Forgot Password Link', 'Display a link to the Forgot Password page in the Login Panel?', 'yesno', '1', 7),
(NULL, 11, 'User CP Options', 'User CP Form Title', 'Title for the user control panel form:', 'wysiwyg', '', 2),
(NULL, 12, 'Registration Options', 'Maximum Username Length', 'Enter the maximum username length:', 'text', '13', 2),
(NULL, 12, 'Registration Options', 'Banned Email Addresses', 'Enter a list of banned email addresses (separated by spaces):<br />To ban a specific address enter \"email@domain.com\", to ban an entire domain enter \"@domain.com\"', 'textarea', '', 4),
(NULL, 12, 'Registration Options', 'Banned IP Addresses', 'Enter a list of banned ip addresses (separated by spaces)<br />You can ban entire subnets (0-255) by using wildcard characters (192.168.0.*, 192.168.*.*) or enter a full ip address:', 'textarea', '', 5),
(NULL, 12, 'Registration Options', 'Require Email Activation', 'Require new registrants to validate their email address when registering:', 'yesno', '0', 6),
(NULL, 12, 'Registration Options', 'Reset Password Title', 'Title for the password reset form:', 'wysiwyg', '<strong>Please enter your registered email to where the new password should be sent:</strong><br /><br />', 8),
(NULL, 12, 'Registration Options', 'Registration Form Title', 'Title for the registration form:', 'wysiwyg', '<strong>Please enter your name, password and email for registration:</strong><br /><br />', 9),
(NULL, 17, 'Upload Options', 'Allowed Image Types', 'List of allowed image types for upload (comma separated)?<br />E.g.: <strong>gif, jpg, jpeg, png<strong>', 'text', 'gif, jpg, jpeg, png', 2),
(NULL, 17, 'Upload Options', 'Max Image Upload Size', 'Maximum image filesize for user uploads in Bytes?<br />Default: <strong>1048576</strong> (= 1MB)', 'text', '1048576', 3),
(NULL, 17, 'Upload Options', 'Image Notification', 'This email address will receive an email message when a new image is submitted:<br />Separate multiple addresses with commas.', 'text', '', 4),
(NULL, 17, 'Upload Options', 'Auto Approve Images', 'Automatically approve user submitted images?', 'yesno', '0', 5),
(NULL, 17, 'Upload Options', 'Upload Requires Title', 'Is the image <strong>Title</strong> required for image uploads?', 'yesno', '1', 11),
(NULL, 17, 'Upload Options', 'Upload Requires Description', 'Is the image <strong>Description</strong> required for image uploads?', 'yesno', '1', 12),
(NULL, 17, 'Upload Options', 'Upload Requires Author', 'Is the image <strong>Author</strong> required for image uploads?', 'yesno', '1', 13),
(NULL, 17, 'Options', 'Image Display Mode', 'Mode for displaying a single image?',
'<select name=\\\\\"settings[\$setting[settingid]]\\\\\">\r\n
<option value=\\\\\"0\\\\\" \".(\$setting[value]==\"0\" ? \"selected\" : \"\").\">Integrated (Default)</option>\r\n
<option value=\\\\\"1\\\\\" \".(\$setting[value]==\"1\" ? \"selected\" : \"\").\">Popup (New Window)</option>\r\n
</select>', '0', 1),
(NULL, 17, 'Options', 'Number of images per Row', 'Enter the number of images to be displayed per row:', 'text', '4', 2),
(NULL, 17, 'Options', 'Images Per Page', 'Enter the number of images that a section should display per page:', 'text', '12', 3),
(NULL, 17, 'Options', 'Show View Counts', 'Show the number of times the image has been viewed?', 'yesno', '1', 6),
(NULL, 17, 'Options', 'Show Submitted By', 'Show the name of the image submitter (default: Yes)? If set to No, this will disable the display for all images.', 'yesno', '1', 7),
(NULL, 17, 'Options', 'Center Images', 'Would you like to center the images on the page?', 'yesno', '1', 10),
(NULL, 17, 'Options', 'Enable Comments', 'Enable display/submittal of comments for images plugin-wide? If disabled, no comments are shown at all. Default: Yes', 'yesno', '1', 12),
(NULL, 17, 'Options', 'Section Sort Order', 'Sort sections by', '
<select name=\\\\\"settings[\$setting[settingid]]\\\\\">\r\n
<option value=\\\\\"0\\\\\" \".(empty(\$setting[value]) ? \"selected\" : \"\").\">Newest First</option>\r\n
<option value=\\\\\"1\\\\\" \".(\$setting[value]==\"1\" ? \"selected\" : \"\").\">Oldest First</option>\r\n
<option value=\\\\\"2\\\\\" \".(\$setting[value]==\"2\" ? \"selected\" : \"\").\">Alphabetically A-Z</option>\r\n
<option value=\\\\\"3\\\\\" \".(\$setting[value]==\"3\" ? \"selected\" : \"\").\">Alphabetically Z-A</option>\r\n
</select>', '2', 9),
(NULL, 17, 'Thumbnail Options', 'Image Resizing', 'Automatically Resize images to thumbnails?:', 'yesno', '1', 1),
(NULL, 17, 'Thumbnail Options', 'Max Thumbnail Width', 'Enter the max width that a thumbnail should be resized to:', 'text', '100', 2),
(NULL, 17, 'Thumbnail Options', 'Max Thumbnail Height', 'Enter the max height that a thumbnail should be resized to:', 'text', '100', 3),
(NULL, 17, 'Thumbnail Options', 'Square Off Thumbnails', 'When automatically creating thumbnails, should the image be cropped so that it is square?', 'yesno', '0', 4),
(NULL, 17, 'MidSize Options', 'Create MidSize Images', 'Automatically create midsize images?<br />This allows you to show a larger image before the full size image is shown:', 'yesno', '1', 1),
(NULL, 17, 'MidSize Options', 'Keep FullSize Images', 'When an image is uploaded should we keep the fullsize image?<br />If this is set to no and \'Create MidSize Images\' is set to yes, this gives you ability to restrict the maximum size of the image. In this case the original image will be deleted leaving you with the fixed size MideSize image. Only active when \'Create MidSize Images\' is enabled:', 'yesno', '1', 2),
(NULL, 17, 'MidSize Options', 'Max MidSize Width', 'Enter the max width that the midsize image should be resized to:', 'text', '400', 3),
(NULL, 17, 'MidSize Options', 'Max MidSize Height', 'Enter the max height that the midsize image should be resized to:', 'text', '400', 4),
(NULL, 17, 'MidSize Options', 'Square Off MidSize', 'When automatically creating midsize images, should the image be cropped so that it is square?', 'yesno', '0', 5),
(NULL, 17, 'Section Options', 'Display Section Image Count', 'Display the image count in each section? <br />Disable this option if you have many images and your image gallery is loading slowly.', 'yesno', '1', 1),
(NULL, 17, 'Section Options', 'Display Sections as Images', 'Display sections as images instead of text?', 'yesno', '1', 2),
(NULL, 17, 'Section Options', 'Number of Section Images per Row', 'Enter the number of section images to be displayed per row:', 'text', '4', 3),
(NULL, 17, 'Section Options', 'Allow Root Section Upload', 'Allow user uploads to top/root section?', 'yesno', '0', 5)");


// ############################################################################
// INSERT USERGROUPS
// ############################################################################
$pview = '2,3,4,6,7,8,9,10,11,12,16,17,5000,5001,5002,5003,5004,5005,5006,5007,5008,5009,5010,5011,5012,5013,5014,5015,5016,5017';
$DB->query("INSERT INTO {usergroups}
(`usergroupid`, `forumusergroupid`, `name`, `adminaccess`, `commentaccess`, `offlinecategoryaccess`, `require_vvc`, `categoryviewids`, `categorymenuids`,
 `pluginviewids`, `pluginsubmitids`, `plugindownloadids`, `pluginadminids`, `plugincommentids`, `pluginmoderateids`,
 `custompluginviewids`, `custompluginadminids`, `banned`) VALUES
(1, 0, 'Administrators', 1, 1, 1, 0, '1,2,3,4,6,7,8,9,10,11,12', '1,4,6,7,8,9,10,11,12',
 '$pview',
 '2,4,6,7,10,11,12,16,17,5000,5001,5002,5003,5004,5014',
 '2,17,5000,5001,5002',
 '2,3,4,6,10,11,12,17,5000,5001,5002,5003,5004,5005,5006,5007,5008,5009,5010,5011,5012,5013,5014,5015,5016,5017',
 '2,17,5001,5002,5003',
 '2,4,6,7,10,11,12,17,5000,5001,5002,5011,5012,5013,5014',
 '1,2,3,4', '1,2,3,4', 0),
(2, 0, 'Moderators', 0, 1, 1, 0, '1,2,3,4,6,8,9,10,11,12', '1,4,6,8,9,10,11,12',
 '$pview',
 '4,6,7,10,11,12,16,17,5000,5001,5002,5003,5004,5014',
 '2,17,5000,5001,5002',
 '',
 '2,17,5001,5002,5003',
 '2,4,7,17,5000,5001,5002,5003,5014',
 '1,2,3,4', '', 0),
(3, 0, 'Registered Users', 0, 0, 0, 0, '1,2,3,4,6,8,9,10,11,12', '1,4,6,8,9,10,11,12',
 '$pview',
 '4,6,7,10,11,12,16,5000,5004,5014',
 '2,17,5000,5001,5002',
 '',
 '2,17,5001,5002,5003',
 '',
 '1,2,3,4', '', 0),
(4, 0, 'Guests', 0, 0, 0, 1, '1,2,3,4,5,7,9,10,11', '1,4,5,6,7,9,10,11',
 '$pview',
 '6,10,12,5004',
 '',
 '',
 '',
 '',
 '1,2,3,4', '', 0),
(5, 0, 'Banned', 0, 0, 0, 1, '1', '1',
 '1', '', '', '', '', '',
 '', '', 1)");

// ############################################################################
// INSTALL SEQUEL SKIN
// ############################################################################

$xml_file_path = ROOT_PATH . 'skins/sequel/skin.xml';

// load the xml document into a variable
$xml = file_get_contents($xml_file_path);

// set up the parser object
$parser = new XMLParser($xml);

// parse the xml
$parser->Parse();

$name              = @mysql_real_escape_string($parser->document->name[0]->tagData);
$author_name       = @mysql_real_escape_string($parser->document->author_name[0]->tagData);
$author_link       = @mysql_real_escape_string($parser->document->author_link[0]->tagData);
$folder_name       = @mysql_real_escape_string($parser->document->folder_name[0]->tagData);
$doctype           = @mysql_real_escape_string($parser->document->doctype[0]->tagData);
$starting_html_tag = @mysql_real_escape_string($parser->document->starting_html_tag[0]->tagData);
$starting_body_tag = @mysql_real_escape_string($parser->document->starting_body_tag[0]->tagData);
$head_include      = @mysql_real_escape_string($parser->document->head_include[0]->tagData);
$css               = @mysql_real_escape_string($parser->document->css[0]->tagData);
$prgm_css          = @mysql_real_escape_string($parser->document->prgm_css[0]->tagData);
$header            = @mysql_real_escape_string($parser->document->header[0]->tagData);
$footer            = @mysql_real_escape_string($parser->document->footer[0]->tagData);
$error_page        = @mysql_real_escape_string($parser->document->error_page[0]->tagData);

$layout_count = count($parser->document->layout);

$previewimage = $folder_name . '/preview.jpg';

$DB->query("INSERT INTO {skins}
           (skinid, skin_engine, name, activated, numdesigns, previewimage, authorname, authorlink, folder_name, doctype, starting_html_tag,
            starting_body_tag, head_include, css, prgm_css, header, footer, error_page) VALUES
           (NULL, 2, '$name', 1, $layout_count, '$previewimage', '$author_name', '$author_link', '$folder_name', '$doctype', '$starting_html_tag',
            '$starting_body_tag', '$head_include', '$css', '$prgm_css', '$header', '$footer', '$error_page')");

$skinid = $DB->insert_id();

for($i = 0; $i < $layout_count; $i++)
{
  $imagepath = $folder_name . '/layout' . ($i + 1) . '.jpg';

  $layout = mysql_real_escape_string($parser->document->layout[$i]->tagData);

  $maxplugins = substr_count($layout, '[PLUGIN]');

  $DB->query("INSERT INTO {designs} (designid, skinid, maxplugins, imagepath, layout)
              VALUES (NULL, %d, %d, '$imagepath', '$layout')",$skinid,$maxplugins);
}

// SD313: the below array needs to be filled for GetPluginID to work:
$plugin_name_to_id_arr = array();
if($get_plugins = $DB->query('SELECT pluginid, name FROM {plugins} ORDER BY pluginid'))
{
  while($plugin_arr = $DB->fetch_array($get_plugins))
  {
    $plugin_name_to_id_arr[$plugin_arr['name']] = $plugin_arr['pluginid'];
  }
  unset($get_plugins);
}
