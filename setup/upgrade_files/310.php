<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

$DB->query("UPDATE {phrases} SET defaultphrase = 'Comments' WHERE varname = 'comments' AND pluginid = 1 LIMIT 1");

$DB->query("UPDATE {adminphrases} SET defaultphrase = 'You are using an old skin that doesn\'t support online editing.'
            WHERE adminpageid = 6 AND varname = 'skins_old_engine_notice' LIMIT 1");

$DB->query("UPDATE {adminphrases} SET defaultphrase = 'New Layout'
            WHERE adminpageid = 6 AND varname = 'skins_create_new_layout' LIMIT 1");

$DB->query("ALTER TABLE {skins} CHANGE `name` `name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
CHANGE `previewimage` `previewimage` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
CHANGE `authorname` `authorname` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
CHANGE `authorlink` `authorlink` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
CHANGE `folder_name` `folder_name` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL");

$DB->query("UPDATE {adminphrases} SET defaultphrase = 'Enter your public reCaptcha key:<br /><a href=\"http://recaptcha.net/api/getkey\" target=\"_blank\">Click here to get your public and private reCaptcha keys.</a>'
            WHERE varname = 'settings_captcha_publickey_desc' AND adminpageid = 7");

$DB->query("ALTER TABLE {plugins} CHANGE `authorlink` `authorlink` VARCHAR(250) NULL");

// install forum plugin
$forum_plugin_id = CreatePluginID('Forum');
$DB->query("REPLACE INTO {plugins} (pluginid, name, displayname, version, pluginpath, settingspath, authorname, authorlink, settings)
            VALUES ($forum_plugin_id, 'Forum', '', '1.0', 'forum/forum.php', 'forum/settings.php', 'subdreamer_web', 'http://www.subdreamer.com/', 23)");

$DB->query("CREATE TABLE IF NOT EXISTS {p_forum_forums} (
  `forum_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `online` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `is_category` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `parent_forum_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `last_topic_id` int(10) unsigned NOT NULL DEFAULT '0',
  `last_topic_title` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_post_id` int(10) unsigned NOT NULL DEFAULT '0',
  `last_post_username` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_post_date` int(10) unsigned NOT NULL DEFAULT '0',
  `topic_count` int(10) unsigned NOT NULL DEFAULT '0',
  `post_count` int(10) unsigned NOT NULL DEFAULT '0',
  `display_order` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`forum_id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE IF NOT EXISTS {p_forum_posts} (
  `post_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `topic_id` int(10) unsigned NOT NULL DEFAULT '0',
  `username` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `date` int(10) unsigned NOT NULL DEFAULT '0',
  `post` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `ip_address` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `is_entities` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`post_id`),
  KEY `topic_id` (`topic_id`),
  KEY `user_id` (`user_id`),
  KEY `date` (`date`),
  FULLTEXT KEY `post` (`post`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("CREATE TABLE IF NOT EXISTS {p_forum_topics} (
  `topic_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `forum_id` int(10) unsigned NOT NULL DEFAULT '0',
  `date` int(10) unsigned NOT NULL DEFAULT '0',
  `post_count` int(10) unsigned NOT NULL DEFAULT '0',
  `views` int(10) unsigned NOT NULL DEFAULT '0',
  `open` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `post_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `post_username` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(250) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `first_post_id` int(10) unsigned NOT NULL DEFAULT '0',
  `last_post_id` int(10) unsigned NOT NULL DEFAULT '0',
  `last_post_date` int(10) unsigned NOT NULL DEFAULT '0',
  `last_post_username` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`topic_id`),
  KEY `forum_id` (`forum_id`),
  KEY `date` (`date`),
  KEY `post_user_id` (`post_user_id`),
  KEY `last_post_date` (`last_post_date`),
  FULLTEXT KEY `title` (`title`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query('INSERT INTO {p_forum_forums} (forum_id, online, is_category, parent_forum_id, title)'.
           " VALUES (NULL, 1, 1, 0, 'Forums')");

// install default usergroup settings
$usergroups = $DB->query('SELECT usergroupid, pluginviewids, pluginsubmitids, plugindownloadids, pluginadminids'.
                         ' FROM {usergroups} ORDER BY usergroupid');

$pluginbitfield = array('canview'     => 1,
                        'cansubmit'   => 2,
                        'candownload' => 4,
                        'cancomment'  => 8,
                        'canadmin'    => 16,
                        'canmoderate' => 32);

$pluginsettings = 23;

while($usergroup = $DB->fetch_array($usergroups,null,MYSQL_ASSOC))
{
  if($usergroup['usergroupid'] == 1)
  {
    // ADMINISTRATOR USERS
    $pluginviewids     = (!$pluginsettings & $pluginbitfield['canview'])     ? $usergroup['pluginviewids']     : (strlen($usergroup['pluginviewids'])     ? $usergroup['pluginviewids']      . ',' . $forum_plugin_id : $forum_plugin_id);
    $pluginsubmitids   = (!$pluginsettings & $pluginbitfield['cansubmit'])   ? $usergroup['pluginsubmitids']   : (strlen($usergroup['pluginsubmitids'])   ? $usergroup['pluginsubmitids']    . ',' . $forum_plugin_id : $forum_plugin_id);
    $plugindownloadids = (!$pluginsettings & $pluginbitfield['candownload']) ? $usergroup['plugindownloadids'] : (strlen($usergroup['plugindownloadids']) ? $usergroup['plugindownloadids']  . ',' . $forum_plugin_id : $forum_plugin_id);
    $pluginadminids    = (!$pluginsettings & $pluginbitfield['canadmin'])    ? $usergroup['pluginadminids']    : (strlen($usergroup['pluginadminids'])    ? $usergroup['pluginadminids']     . ',' . $forum_plugin_id : $forum_plugin_id);
  }
  else
  if(($usergroup['usergroupid'] == 2) || ($usergroup['usergroupid'] == 3))
  {
    // MODERATORS AND REGISTERED USERS
    $pluginviewids     = (!$pluginsettings & $pluginbitfield['canview'])     ? $usergroup['pluginviewids']     : (strlen($usergroup['pluginviewids'])     ? $usergroup['pluginviewids']      . ',' . $forum_plugin_id : $forum_plugin_id);
    $pluginsubmitids   = (!$pluginsettings & $pluginbitfield['cansubmit'])   ? $usergroup['pluginsubmitids']   : (strlen($usergroup['pluginsubmitids'])   ? $usergroup['pluginsubmitids']    . ',' . $forum_plugin_id : $forum_plugin_id);
    $plugindownloadids = (!$pluginsettings & $pluginbitfield['candownload']) ? $usergroup['plugindownloadids'] : (strlen($usergroup['plugindownloadids']) ? $usergroup['plugindownloadids']  . ',' . $forum_plugin_id : $forum_plugin_id);
    $pluginadminids    = $usergroup['pluginadminids'];
  }
  else
  {
    // GUESTS, BANNED and other created usergroup users
    $pluginviewids     = (!$pluginsettings & $pluginbitfield['canview'])     ? $usergroup['pluginviewids']     : (strlen($usergroup['pluginviewids'])     ? $usergroup['pluginviewids']      . ',' . $forum_plugin_id : $forum_plugin_id);
    $pluginsubmitids   = $usergroup['pluginsubmitids'];
    $plugindownloadids = $usergroup['plugindownloadids'];
    $pluginadminids    = $usergroup['pluginadminids'];
  }

  // update usergroup row
  $DB->query("UPDATE {usergroups}
    SET pluginviewids     = '$pluginviewids',
        pluginsubmitids   = '$pluginsubmitids',
        plugindownloadids = '$plugindownloadids',
        pluginadminids    = '$pluginadminids'
    WHERE usergroupid     = %d", $usergroup['usergroupid']);
}
unset($pluginviewids,$pluginsubmitids,$plugindownloadids,$pluginadminids,$usergroup,$usergroups);

// fix up some columns to support mysql 5 strict settings
$DB->query("ALTER TABLE {users} CHANGE `admin_notes` `admin_notes` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
                                CHANGE `user_notes` `user_notes` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL");

InsertPhrase(1, 'comment_posted', 'Comment Posted');

InsertAdminPhrase(0, 'pages_content', 'Content', 1);
InsertAdminPhrase(0, 'pages_edit_plugin_positions', 'Edit Plugin Positions', 1);
InsertAdminPhrase(0, 'pages_save_plugin_positions', 'Save Plugin Positions', 1);
InsertAdminPhrase(0, 'skins_layout_inserted', 'Layout Created', 6);
InsertAdminPhrase(0, 'skins_cant_delete_pages_exist', 'Can not delete this layout, there are pages still using it.', 6);
InsertAdminPhrase(0, 'skins_delete_layout', 'Delete Layout?', 6);
InsertAdminPhrase(0, 'skins_layout_deleted', 'Layout Deleted', 6);
InsertAdminPhrase(0, 'skins_confirm_new_layout', 'Create new layout?', 6);

DeleteAdminPhrase(0, 'pages_page_link_settings', 1);
DeleteAdminPhrase(0, 'pages_link_hover_image', 1);
DeleteAdminPhrase(0, 'pages_delete_image', 1);
DeleteAdminPhrase(0, 'skins_html_doctype', 6);
DeleteAdminPhrase(0, 'skins_html_start_tag', 6);
DeleteAdminPhrase(0, 'skins_body_start_tag', 6);
DeleteAdminPhrase(0, 'skins_select_structure', 6);
DeleteAdminPhrase(0, 'skins_install_old_skin', 6);
DeleteAdminPhrase(0, 'skins_import_path', 6);
DeleteAdminPhrase(0, 'skins_import_skin', 6);

// old 2.6 setting that never got deleted
$DB->query("DELETE FROM {mainsettings} WHERE varname IN ('common_header_html')");


$DB->add_tablecolumn(PRGM_TABLE_PREFIX.'comments', 'userid', 'INT(10)', "UNSIGNED NOT NULL DEFAULT '0' AFTER `date`");

$get_comments = $DB->query("SELECT c.commentid, c.username, u.userid FROM {comments} c INNER JOIN {users} u ON c.username = u.username");

while($comment_arr = $DB->fetch_array($get_comments))
{
  $DB->query("UPDATE {comments} SET userid = $comment_arr[userid] WHERE commentid = $comment_arr[commentid]");
}

$DB->query("CREATE TABLE IF NOT EXISTS {skin_css} (
`skin_css_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
`skin_id`     INT UNSIGNED NOT NULL DEFAULT '0',
`plugin_id`   INT UNSIGNED NOT NULL DEFAULT '0',
`var_name`    VARCHAR(250) NULL DEFAULT NULL,
`css`         MEDIUMTEXT COLLATE utf8_unicode_ci,
INDEX (`plugin_id`, `skin_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$DB->query("INSERT INTO {skin_css} (skin_id, plugin_id, var_name, css) VALUES
            (0, 0, 'skin-css', '')");

$get_skins = $DB->query("SELECT * FROM {skins} WHERE skin_engine = 2");
while($skin_arr = $DB->fetch_array($get_skins))
{
  $new_header = '';
  $new_footer = '';

  if($skin_arr['name'] == 'Sequel')
  {
    // screws up the width for plugins
    $skin_arr['css'] = str_replace('div#left_column.full { width: auto; }', 'div#left_column.full { width: 100%; }', $skin_arr['css']);

    // nobody using the ad spot, and nobody removes it, looks ugly when its not used
    $skin_arr['header'] = str_replace('<div id="ad"><!-- Insert Ad Here --></div>', '', $skin_arr['header']);
  }

  $new_header = $skin_arr['doctype'] . "\n"
              . $skin_arr['starting_html_tag'] . "\n"
              . "<head>\n"
              . "  [CMS_HEAD_INCLUDE]\n"
              . $skin_arr['head_include'] . "\n"
              . "</head>\n"
              . $skin_arr['starting_body_tag'] . "\n"
              . $skin_arr['header'];

  $new_footer = $skin_arr['footer'] . "\n"
              . "</body>\n"
              . "</html>";

  // SD3.2: only do this in case of upgrade since the original sequel.xml
  // was fixed with correct layout and headers
  if(!defined('INSTALLING_PRGM'))
  {
    $DB->query("UPDATE {skins} SET header = '" . mysql_real_escape_string($new_header) . "',
                                   footer = '" . mysql_real_escape_string($new_footer) . "' WHERE skinid = $skin_arr[skinid] LIMIT 1");
  }
  $DB->query("INSERT INTO {skin_css} (skin_id, var_name, css) VALUES
              ($skin_arr[skinid], 'skin-css', '" . mysql_real_escape_string($skin_arr['css']) . "')");
} //while

if(!defined('INSTALLING_PRGM'))
{
  $DB->query("UPDATE {designs} SET layout = CONCAT('[HEADER]\n', layout, '\n[FOOTER]\n') WHERE LENGTH(layout) > 0");
  $DB->query("UPDATE {skins} SET error_page = CONCAT('<html>\n<head>\n  [CMS_HEAD_INCLUDE]\n</head>\n<body>\n', error_page, '\n</body>\n</html>') WHERE skin_engine = 2 AND LENGTH(error_page) > 0");
}

$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'skins', 'doctype');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'skins', 'starting_html_tag');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'skins', 'starting_body_tag');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'skins', 'head_include');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'skins', 'css');
$DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'skins', 'prgm_css');
