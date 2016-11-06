<?php
if(!defined('IN_PRGM')) exit();

// ############################################################################
// ############ DO NOT CHANGE ANY ENTRIES UNLESS ABSOLUTELY SURE!!! ###########
// ############################################################################
// This file and function are ONLY used in:
//   functions_admin.php -> DisplayAdminHeader()
// in order to display the main menu at the top of the Admin panel.
function GetSubMenuEntries()
{
  if(!defined('IN_ADMIN')) return '';

  global $DB, $mainsettings, $userinfo, $admin_phrases, $core_pluginids, $plugin_names;

  $wysiwyg_suffix = ($mainsettings['enablewysiwyg'] ? '&amp;load_wysiwyg=1' : '');
  $phrases = $admin_phrases;

  // DO NOT TRANSLATE ANYTHING BELOW!!!

  // Plugins page menu permissions
  if(!empty($userinfo['adminaccess']) || !empty($userinfo['pluginadminids']) || !empty($userinfo['custompluginadminids']) ||
     !empty($userinfo['maintain_customplugins'])
    )
  {
    //$plugins_arr = array($phrases['menu_plugins_view_plugins'] => 'plugins.php?action=display_plugins');
	$plugins_arr[$phrases['menu_plugins_main_plugins']] = 'plugins.php?action=display_plugins&tab=main';
	$plugins_arr[$phrases['menu_plugins_custom_plugins']] = 'plugins.php?action=display_plugins&tab=custom';
	$plugins_arr[$phrases['menu_plugins_addon_plugins']] = 'plugins.php?action=display_plugins&tab=addon';
	$plugins_arr[$phrases['menu_plugins_cloned_plugins']] = 'plugins.php?action=display_plugins&tab=cloned';
    if(!empty($userinfo['adminaccess']) || !empty($userinfo['maintain_customplugins']))
    {
      $plugins_arr[$phrases['menu_plugins_create_custom_plugin']] = 'plugins.php?action=display_custom_plugin_form'.$wysiwyg_suffix;
    }
    if(!empty($userinfo['adminaccess']))
    {
      $phrases['menu_plugins_install_upgrade_plugins'] = str_replace('& ','&amp; ',$phrases['menu_plugins_install_upgrade_plugins']);
      $plugins_arr[$phrases['menu_plugins_install_upgrade_plugins']] = 'plugins.php?action=display_install_upgrade_plugins';
    }
  }

  // Articles page menu permissions
  //SD341: support for multiple Article plugins/clones as menu items
  $articles_plugins = array();
  $pid = 2;
  if(!empty($userinfo['adminaccess']) ||
     ( (!empty($userinfo['pluginadminids']) && @in_array($pid, $userinfo['pluginadminids'])) ||
       (!empty($userinfo['authormode']) && @in_array($pid, $userinfo['pluginsubmitids']))
     ))
  {
    $pname = isset($plugin_names[2]) ? $plugin_names[2] : 'Articles';
    $articles_plugins[$pname] = 2;
  }
  if(version_compare($mainsettings['sdversion'], '3.4.1', 'ge') && $DB->column_exists(PRGM_TABLE_PREFIX.'plugins','base_plugin'))
  {
    $extra_sql_query = "(base_plugin = 'Articles') AND (pluginid >= 2000)"; // incl. clones
  }
  else
  {
    $extra_sql_query = "(name LIKE 'Articles (%)') AND (pluginid >= 5000) AND (authorname NOT LIKE 'subdreamer_cloner%')";
  }
  $get_plugins = $DB->query('SELECT pluginid, name FROM {plugins} WHERE (pluginid > 1) AND ' . $extra_sql_query);
  if($DB->get_num_rows($get_plugins))
  {
    while($plugin_arr = $DB->fetch_array($get_plugins))
    {
      $pid = $plugin_arr['pluginid'];
      if(!empty($userinfo['adminaccess']) ||
         ( (!empty($userinfo['pluginadminids']) && @in_array($pid, $userinfo['pluginadminids'])) ||
           (!empty($userinfo['authormode']) && @in_array($pid, $userinfo['pluginsubmitids']))
         ))
      {
        $pname = isset($plugin_names[$plugin_arr['pluginid']]) ? $plugin_names[$plugin_arr['pluginid']] : $plugin_arr['name'];
        $articles_plugins[$pname] = (int)$plugin_arr['pluginid'];
      }
    }
  }

  // If there is at least one Articles plugin (original or clone), create a menu entry:
  $articles_arr = array();
  if(count($articles_plugins))
  {
    ksort($articles_plugins);
    foreach($articles_plugins as $pname => $pid)
    {
      //SD343, 2012-03-25: articles plugin now has it's own translateble menu phrases!
      $pp = LoadAdminPhrases(2,$pid,true);
      $link = '<a href="articles.php'.($pid>2?'?pluginid='.$pid:'').'">'.$pname.'</a>';
      $a = array();
      $term = !empty($pp['menu_articles_view_articles']) ? $pp['menu_articles_view_articles']  : $phrases['menu_articles_view_articles'];
      $a[$term] = 'articles.php'.($pid>2?'?pluginid='.$pid:'');
      $term = !empty($pp['menu_articles_write_article']) ? $pp['menu_articles_write_article']   : $phrases['menu_articles_write_article'];
      $a[$term] = 'articles.php'.($pid>2?'?pluginid='.$pid.'&amp;':'?').'articleaction=displayarticleform'.$wysiwyg_suffix;
      if(!empty($userinfo['adminaccess']) ||
         (!empty($userinfo['pluginadminids']) && @in_array($pid, $userinfo['pluginadminids'])) )
      {
        $term = !empty($pp['menu_articles_settings']) ? $pp['menu_articles_settings']: $phrases['menu_articles_settings'];
        $a[$term] = 'articles.php'.($pid>2?'?pluginid='.$pid.'&amp;':'?').'articleaction=display_article_settings';
        $term = !empty($pp['menu_articles_page_settings']) ? $pp['menu_articles_page_settings']: $phrases['menu_articles_page_settings'];
        $a[$term] = 'articles.php'.($pid>2?'?pluginid='.$pid.'&amp;':'?').'articleaction=display_page_article_settings';
      }
      $term = !empty($pp['menu_articles_search']) ? $pp['menu_articles_search']: $phrases['menu_articles_search'];
      $a[$term] = 'articles.php'.($pid>2?'?pluginid='.$pid.'&amp;':'?').'articleaction=display_article_search_form';

      $articles_arr[$pid] = array('pluginid' => $pid, 'name' => $pname, 'menu' => $a);
    }
  }
  

  $setting_arr = array($phrases['menu_settings_main_settings'] => 'settings.php',
                       $phrases['menu_settings_logo_settings'] => 'settings.php?display_type=logo'.$wysiwyg_suffix);
  if(!empty($userinfo['adminaccess']))
  {
    $setting_arr = array_merge($setting_arr, array(
                      $phrases['menu_settings_languages'] => 'languages.php',
                      $phrases['menu_settings_cache'] => 'settings.php?display_type=info_cache',
                      $phrases['menu_settings_database'] => 'settings.php?display_type=database',
                      $phrases['menu_settings_info_mysql'] => 'settings.php?display_type=info_mysql',
                      $phrases['menu_settings_info_php'] => 'settings.php?display_type=info_php',
                      $phrases['menu_settings_modules'] => 'settings.php?display_type=modules', //SD343
                      $phrases['menu_settings_syslog'] => 'settings.php?display_type=syslog'));
  }

  return array(
				'Pages'    => array($phrases['menu_pages_view_pages'] => 'pages.php',
					  $phrases['menu_pages_create_page'] => 'pages.php?action=create_page'),
				'Articles' => $articles_arr,
				'Plugins'  => (isset($plugins_arr)?$plugins_arr:array()),
				'Media'    => array($phrases['menu_media_view_images'] => 'media.php',
					  $phrases['menu_media_view_smilies'] => 'smilies.php'),
				'Comments' => array($phrases['menu_comments_view_comments'] => 'comments.php?action=display_comments',
						$phrases['menu_comments_view_comments_by_plugin'] => 'comments.php?action=display_organized_comments',
						$phrases['menu_comments_view_comment_settings'] => 'comments.php?action=display_comment_settings'.$wysiwyg_suffix),
				'Reports'  => array($phrases['menu_reports_view_reports'] => 'reports.php?action=display_reports',
						$phrases['menu_reports_view_reports_by_plugin'] => 'reports.php?action=display_organized_reports',
						$phrases['menu_reports_view_report_reasons'] => 'reports.php?action=display_report_reasons',
						$phrases['menu_reports_view_settings'] => 'reports.php?action=display_report_settings'),
				'Tags'     => array($phrases['menu_tags_view_tags'] => 'tags.php?action=display_tags',
						$phrases['menu_tags_view_tags_by_plugin'] => 'tags.php?action=display_organized_tags',),
				'Users &amp; Groups'    => array($phrases['menu_users_view_users'] => 'users.php?action=display_users',
					  $phrases['menu_users_add_user'] => 'users.php?action=display_user_form'.SD_URL_TOKEN,
					  $phrases['menu_users_search_users'] => 'users.php?action=display_user_search_form',
					  $phrases['menu_users_titles'] => 'users.php?action=view_user_titles',
					  $phrases['menu_users_email_users'] => 'users.php?action=display_email_users_form'.SD_URL_TOKEN,
					  $phrases['menu_users_forum_integration'] => 'forumintegration.php?action=display'.SD_URL_TOKEN,
					  $phrases['menu_users_view_user_groups'] => 'usergroups.php?action=display_usergroups',
					  $phrases['menu_users_add_user_group'] => 'usergroups.php?action=display_usergroup_form'.SD_URL_TOKEN),
				'Skins'    => array($phrases['menu_skins_view_current_skin'] => 'skins.php?action=display_skin' . SD_URL_TOKEN,
					  $phrases['menu_skins_view_all_skins']    => 'skins.php?action=display_skins' . SD_URL_TOKEN,
					  $phrases['menu_skins_create_new_skin']   => 'skins.php?action=display_create_skin' . SD_URL_TOKEN,
					  $phrases['menu_skins_import_skins']      => 'skins.php?action=display_import_skins' . SD_URL_TOKEN,
					  $phrases['menu_skins_view_templates']    => 'templates.php?action=display_templates' . SD_URL_TOKEN, //SD344
					  $phrases['menu_skins_view_templates_by_plugin'] => 'templates.php?action=display_organized_templates', //SD344
					  $phrases['menu_skins_view_template_settings'] => 'templates.php?action=display_template_settings', //SD344
					  ),
				'Settings' => $setting_arr
  );

} //GetSubMenuEntries
