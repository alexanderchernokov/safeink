<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

# Admin|Skins: added reimport functionality for currently active SD3 skin

// Change Phrases
InsertAdminPhrase(0, 'common_search', 'Search', 0, false);
InsertAdminPhrase(0, 'admin_control_panel', 'Admin Control Panel', 0, false);
InsertAdminPhrase(0, 'select_your_forum', 'Select Your Forum', 0, false);
InsertAdminPhrase(0, 'select_forum_desc', '	By selecting integration, your members will be able to use their forum usernames
	      and passwords to login to your Subdreamer website. They will also use their
	      forum accounts for any member authentication on your website, such as sending
	      articles, posting in the guestbook, sending links etc...<br /><br />
	      If you decide to use forum integration, please be aware that the members
	      that signed up via the Subdreamer registration page won\'t be able to login
	      anymore (because you are changing usersystems).
', 0, false);
InsertAdminPhrase(0, 'forum_integration', 'Forum Integration', 0, false);
InsertAdminPhrase(0, 'forum_folder_path', 'Forum Folder Path', 0, false);
InsertAdminPhrase(0, 'forum_folder_path_desc', 'Enter the relative path from your main
	    Subdreamer folder to your Forum\'s folder:<br />Only enter the path,
	    do not enter the full URL!<br />
	    Examples: <i>forum/</i> or <i>../phpBB2/</i>
', 0, false);
InsertAdminPhrase(0, 'complete_integration', 'Complete Integration', 0, false);
InsertAdminPhrase(0, 'search', 'Search', 0, false);
InsertAdminPhrase(0, 'common_menu', 'Menu', 0, false);
InsertAdminPhrase(0, 'common_help', 'Help', 0, false);
InsertAdminPhrase(0, 'download', 'Download', 0, false);
InsertAdminPhrase(0, 'nothing_selected', 'Nothing Selected', 0, false);
InsertAdminPhrase(0, 'common_actions', 'Actions', 0, false);
InsertAdminPhrase(0, 'login_enter_your_information', 'Enter your login information', 0, false);
InsertAdminPhrase(0, 'home', 'Home', 0, false);
InsertAdminPhrase(0, 'help', 'Help', 0, false);

InsertAdminPhrase(0, 'tags_confirm_delete_tags', 'Are you sure you want to delete the selected tag(s)?', 1, false);
InsertAdminPhrase(0, 'display_pages', 'Display Pages', 1, false);
InsertAdminPhrase(0, 'edit_page', 'Edit Page', 1, false);
InsertAdminPhrase(0, 'create_page', 'Create Page', 1, false);
InsertAdminPhrase(0, 'pages_append_keywords', 'Keywords &amp; Description', 1, false);
InsertAdminPhrase(0, 'pages_standard', 'Standard', 1, false);
InsertAdminPhrase(0, 'pages_edit_mobile', 'Mobile', 1, false);
InsertAdminPhrase(0, 'pages_parent', 'Parent Page', 1, false);
InsertAdminPhrase(0, 'pages_ssl_disabled', 'SSL access is currently not set', 1, false);

InsertAdminPhrase(2, 'status_pending_review', '<span class="red">Offline</span>. Pending Review.', 2, true);
InsertAdminPhrase(0, 'display_plugins', 'Display Plugins', 2, false);
InsertAdminPhrase(5000, 'menu_forum_title', 'Forum Menu',2, false);
InsertAdminPhrase(0, 'plugins_create', 'Create',2, false);
InsertAdminPhrase(0, 'plugins_custom_plugin', 'Custom Plugin',2, false);
InsertAdminPhrase(0, 'plugins_edit', 'Edit',2, false);
InsertAdminPhrase(0, 'display_install_upgrade_plugins', 'Install / Upgrade Plugins',2, false);

InsertAdminPhrase(0, 'profiles_col_public_input', 'Public Input', 3, false);
InsertAdminPhrase(0, 'media_filter_images', 'Filter Images', 3, false);
InsertAdminPhrase(0, 'add_smilie', 'Add Smilie', 3, false);
InsertAdminPhrase(0, 'smilies_confirm_custom_delete', 'You have chosen at least 1 smilie to delete.  This action cannot be undone.  Are you sure?', 3, false);

InsertAdminPhrase(0, 'reports_confirm_delete_reports', 'Are you sure you want to delete the selected report(s)?', 4, false);
InsertAdminPhrase(0, 'comments_confirm_delete_comments', 'Delete Selected Comments?', 4, false);
InsertAdminPhrase(0, 'report_delete_moderator', 'Delete Moderator?', 4, false);
InsertAdminPhrase(0, 'comments_approve_comment', 'Approve Comment', 4, true);
InsertAdminPhrase(0, 'display_tags', 'Display Tags', 4, false);
InsertAdminPhrase(0, 'display_comments', 'Display Comments', 4, false);
InsertAdminPhrase(0, 'comments_comments', 'Comments', 4, false);
InsertAdminPhrase(0, 'display_organized_comments', 'Comments By Plugin', 4, false);
InsertAdminPhrase(0, 'display_comment_settings', 'Comment Settings', 4, false);
InsertAdminPhrase(0, 'comments_actions', 'Actions', 4, false);
InsertAdminPhrase(0, 'comments_form_footer_descr', 'HTML for the bottom of the frontpage Comments form:This must include at least the HTML submit button but can be extended by extra markup for e.g. site-specific links or further, static information.Default: &lt;input type=\"submit\" value=\"sd_post_comment\" /&gt;\"', 4, true);
InsertAdminPhrase(0, 'display_reports', 'Display Reports',4, false);
InsertAdminPhrase(0, 'display_reason', 'Reasons', 4, false);
InsertAdminPhrase(0, 'display_report_reasons', 'Report Reasons', 4, false);
InsertAdminPhrase(0, 'display_report_settings', 'Report Settings', 4, false);
InsertAdminPhrase(0, 'add_moderator_form', 'Add Moderator', 4, false);
InsertAdminPhrase(0, 'create_global_tag', 'Create Global Tag', 4, false);
InsertAdminPhrase(0, 'display_organized_tags', 'View Tags by Plugin', 4, false);
InsertAdminPhrase(0, 'tags_confirm_delete_tags', 'Are you sure you want to delete the selected tags?', 4, false);

InsertAdminPhrase(0, 'users_other_usergroups', 'Additional Usergroups', 5, true);
InsertAdminPhrase(0, 'Users_other_usergroups_descr', '	The user may be a member of any additional usergroup, whose permissions will be cumulatively added to the ones of the primary usergroup. However, if any of the selected usergroups is banned, only banned usergroups are stored.
	Use CTRL+click to select/unselect indiviual groups.
', 5, true);
InsertAdminPhrase(0, 'user_status_updated', 'User Status Updated', 5, false);
InsertAdminPhrase(0, 'users_email_bcc_address', 'Blind Carbon Copy (BCC)', 5, true);
InsertAdminPhrase(0, 'display_users', 'Display Users', 5, false);
InsertAdminPhrase(0, 'display_user_form', 'Add / Edit User', 5, false);
InsertAdminPhrase(0, 'users_welcome_email_sent', 'Welcome email sent to ', 5, false);
InsertAdminPhrase(0, 'users_ip_address', 'IP Address', 5, false);
InsertAdminPhrase(0, 'users_required', 'Required Field', 5, false);
InsertAdminPhrase(0, 'users_legend', 'Legend', 5, false);
InsertAdminPhrase(0, 'usergroup_info', 'Usergroup Info', 5, false);
InsertAdminPhrase(0, 'usergroup_plugin_permissions', 'Plugin Permissions', 5, false);
InsertAdminPhrase(0, 'usergroup_comment_permissions', 'Comment Permissions', 5, false);
InsertAdminPhrase(0, 'usergroup_extended_permissions', 'Extended Permissions', 5, false);
InsertAdminPhrase(0, 'users_add_title_name', 'Title Name', 5, false);
InsertAdminPhrase(0, 'users_add_title_post_count', 'Post Count', 5, false);
InsertAdminPhrase(0, 'view_user_titles', 'View User Titles', 5, false);
InsertAdminPhrase(0, 'display_user_search_form', 'Search Users', 5, false);
InsertAdminPhrase(0, 'display_email_users_form', 'Email Users', 5, false);
InsertAdminPhrase(0, 'display_usergroup_form', 'Usergroup Form', 5, false);
InsertAdminPhrase(0, 'usergroup_extended_permissions', 'Extended Permissions', 5, false);

InsertAdminPhrase(0, 'skins_skin_errors', 'Skin Errors', 6);
InsertAdminPhrase(0, 'skins_no_layouts_err', 'Skin has no layouts!', 6);
InsertAdminPhrase(0, 'skins_skin_file_error_descr', 'The following errors were identified for file', 6);
InsertAdminPhrase(0, 'skins_entry_reimported', 'entry reimported.', 6);
InsertAdminPhrase(0, 'skins_not_found', 'not found!', 6);
InsertAdminPhrase(0, 'skins_invalid_css_entry_err', 'Invalid CSS entry:', 6);
InsertAdminPhrase(0, 'skins_invalid_plugin_id_err', 'Invalid Plugin ID', 6);
InsertAdminPhrase(0, 'skins_reimport_file', 'Re-Import skin file', 6);
InsertAdminPhrase(0, 'skins_select_xml_import_file', 'Select the source XML file to reimport from', 6);
InsertAdminPhrase(0, 'skins_analyze_xml', 'Analyze XML file', 6);
InsertAdminPhrase(0, 'skins_skin_file_err', 'Skin XML file empty, not found or invalid!', 6);
InsertAdminPhrase(0, 'skins_reimport_cancelled', 'No selection was made, reimport cancelled.', 6);
InsertAdminPhrase(0, 'skins_reimport_xml', 'Reimport XML file', 6);
InsertAdminPhrase(0, 'skins_reimport_finished', 'Reimport finished.', 6);
InsertAdminPhrase(0, 'skins_select_xml_import_file_descr',
  'Use the reimport function if you need to update an already installed skin.<br />
  Reimport will first analyze the XML file and offer options to selectively
  reload individual layouts and/or CSS entries.', 6);
# Fix missing Admin|View Templates phrases:
InsertAdminPhrase(0, 'tmpl_template', 'Template', 6);
InsertAdminPhrase(0, 'tmpl_active', 'Active', 6);
InsertAdminPhrase(0, 'tmpl_inactive', 'Inactive', 6);
InsertAdminPhrase(0, 'tmpl_tpl_type', 'Template Type', 6);
InsertAdminPhrase(0, 'tmpl_sort_by', 'Sort by', 6);
InsertAdminPhrase(0, 'tmpl_tpl_type_asc', 'Type Ascending', 6);
InsertAdminPhrase(0, 'tmpl_tpl_type_descending', 'Type Descending', 6);
InsertAdminPhrase(0, 'tmpl_date', 'Date', 6);
InsertAdminPhrase(0, 'tmpl_date_asc', 'Date Ascending', 6);
InsertAdminPhrase(0, 'tmpl_date_descending', 'Date Descending', 6);
InsertAdminPhrase(0, 'tmpl_uname_asc', 'User Ascending', 6);
InsertAdminPhrase(0, 'tmpl_uname_descending', 'User Descending', 6);
InsertAdminPhrase(0, 'tmpl_clear_filter', 'Clear Filter', 6);
InsertAdminPhrase(0, 'tmpl_apply_filter', 'Apply Filter', 6);
InsertAdminPhrase(0, 'tmpl_displayname', 'Displayname', 6);
InsertAdminPhrase(0, 'tmpl_filename', 'Template Filename', 6);
InsertAdminPhrase(0, 'tmpl_content', 'Template Content', 6);
InsertAdminPhrase(0, 'tmpl_by_plugin', 'Templates by Plugin', 6);
InsertAdminPhrase(0, 'tmpl_plugin', 'Plugin', 6);
InsertAdminPhrase(0, 'tmpl_plugin_name', 'Pluginname', 6);
InsertAdminPhrase(0, 'tmpl_update_template', 'Update Template', 6);
InsertAdminPhrase(0, 'tmpl_template_updated', 'Template updated.', 6);
InsertAdminPhrase(0, 'tmpl_templates_deleted', 'Templates deleted.', 6);
InsertAdminPhrase(0, 'tmpl_edit_template', 'Edit Templates', 6);
InsertAdminPhrase(0, 'tmpl_activate_template', 'Active Template', 6);
InsertAdminPhrase(0, 'tmpl_activate_this_template', 'Activate this template so that it can be used?', 6);
InsertAdminPhrase(0, 'tmpl_delete', 'Delete?', 6);
InsertAdminPhrase(0, 'tmpl_delete_template', 'Delete Template', 6);
InsertAdminPhrase(0, 'tmpl_delete_templates', 'Delete Templates', 6);
InsertAdminPhrase(0, 'tmpl_delete_this_template', 'Delete this template (and all of backups of it)?', 6);
InsertAdminPhrase(0, 'tmpl_settings_updated', 'Template settings updated.', 6);
InsertAdminPhrase(0, 'tmpl_template_settings', 'Template Settings', 6);
InsertAdminPhrase(0, 'tmpl_templates', 'Templates', 6);
InsertAdminPhrase(0, 'tmpl_status', 'Status', 6);
InsertAdminPhrase(0, 'tmpl_username', 'Username', 6);
InsertAdminPhrase(0, 'tmpl_limit', 'Limit', 6);
InsertAdminPhrase(0, 'tmpl_filter', 'Filter', 6);
InsertAdminPhrase(0, 'tmpl_no_templates_found', 'No templates found!', 6);
InsertAdminPhrase(0, 'tmpl_filter_title', 'Filter Templates', 6);
InsertAdminPhrase(0, 'tmpl_core_templates', 'Core Templates', 6);
InsertAdminPhrase(0, 'tmpl_export_to_file', 'Export Template', 6);
InsertAdminPhrase(0, 'tmpl_number_of_templates', 'Number of templates', 6, true);
InsertAdminPhrase(0, 'tmpl_invalid_template_specified', 'Invalid template specified!', 6);
InsertAdminPhrase(0, 'tmpl_abandon_changes', 'Template was changed! Abandon changes???',6);
InsertAdminPhrase(0, 'tmpl_confirm_delete', 'Really DELETE templates?',6);
InsertAdminPhrase(0, 'tmpl_none_deleted', 'NO templates were deleted.',6);
InsertAdminPhrase(0, 'tmpl_restore_original', 'Restore Original Template', 6);
InsertAdminPhrase(0, 'tmpl_restore_prompt', 'This will try to reload to the original template from a file and replace the existing code. Really continue?', 6);
InsertAdminPhrase(0, 'tmpl_restore_msg', 'Template restored from file.', 6);
InsertAdminPhrase(0, 'tmpl_template_not_found', 'Template was not found!', 6);
InsertAdminPhrase(0, 'tmpl_number_of_templates', 'Templates', 6, true);
InsertAdminPhrase(0, 'tmpl_displayname', 'Display Name', 6, true);
InsertAdminPhrase(0, 'tpl_plugin_id', 'Plugin ID', 6, false);
InsertAdminPhrase(0, 'template_quick_chooser', 'Switch Template:', 6, false);

InsertAdminPhrase(0, 'settings_image_display', 'Image Display', 7, false);


$DB->query("INSERT INTO {mainsettings} (varname, groupname, input, title, description, value, displayorder) VALUES
             ('use_data_uri', 'settings_image_display', 'yesno', 'Use Data URI Scheme', 'Use data URI scheme when displaying imaages.  Note: This may not work correctly in all browsers.  Set to no if images are displaying broken.','0', 1)");

// ############################################################################
// RE-CHECK TEMPLATES (FOR VERY LAST UPGRADE STEP!)
// ############################################################################

require_once(ROOT_PATH.'includes/class_sd_smarty.php');

$tpl_path = ROOT_PATH.'includes/tmpl/';
$tpl_path_def = ROOT_PATH.'includes/tmpl/defaults/';

$la_id = GetPluginID('Latest Articles (latest_articles)');
$se_id = GetPluginID('Search Engine');
$new_tmpl = array(
  'articles.tpl' => array('Articles', 2, 'Articles template (full, single article view)'),
  'articles_list.tpl' => array('Articles List', 2, 'Articles list template'),
  'articles_list_rss.tpl' => array('Articles List with RSS', 2, 'Articles list template with special header and RSS icon'),
  'articles_review.tpl' => array('Articles Review', 2, 'Article with review settings'),
  'articles_tagged.tpl' => array('Articles with Tags', 2, 'Article with tags'),
  'user_report_form.tpl' => array('User Report Form', 0, 'User Report Form for prompting user to confirm a report on user content.'),
  'comments.tpl' => array('Comments', 0, 'Comments List'),
  'loginpanel.tpl' => array('Login Panel', 10, 'Login Panel'),
  'registration.tpl' => array('User Registration', 12, 'User Registration form template'),
  'member.tpl' => array('Member', 11, 'Member Page'),
  'ucp.tpl' => array('UCP - Full', 11, 'User Control Panel with full display'),
  'ucp_basic.tpl' => array('UCP - Basic', 11, 'User Control Panel with minimal display'),
  'ucp_dashboard.tpl' => array('UCP Dashboard', 11, 'UCP main dashboard display'),
  'ucp_page_avatar.tpl' => array('UCP Avatar', 11, 'UCP user avatar display'),
  'ucp_page_picture.tpl' => array('UCP Picture', 11, 'UCP user picture display'),
  'ucp_page_mycontent.tpl' => array('UCP MyContent', 11, 'UCP user content display'),
  'ucp_page_myarticles.tpl' => array('UCP MyArticles', 11, 'UCP user articles display'),
  'ucp_page_myforum.tpl' => array('UCP MyForum', 11, 'UCP user forum content display'),
  'ucp_page_myfiles.tpl' => array('UCP MyFiles', 11, 'UCP user files display'),
  'ucp_page_mymedia.tpl' => array('UCP MyMedia', 11, 'UCP user media display'),
  'ucp_page_newmessage.tpl' => array('UCP New Message', 11, 'UCP new message form'),
  'ucp_page_subscriptions.tpl' => array('UCP Subscriptions', 11, 'UCP subscriptions display'),
  'ucp_page_viewdiscussion.tpl' => array('UCP View Discussion', 11, 'UCP display of a single discussion'),
  'ucp_page_viewdiscussions.tpl' => array('UCP View Discussions', 11, 'UCP display of discussions list'),
  'ucp_page_viewmessage.tpl' => array('UCP View Message', 11, 'UCP display of a single message'),
  'ucp_page_viewmessages.tpl' => array('UCP View Messages', 11, 'UCP display of messages list'),
  'ucp_page_viewsentmessages.tpl' => array('UCP View Sent Messages', 11, 'UCP display of sent messages list'),
  'latest_articles.tpl' => array('Latest Articles', $la_id, 'Latest Articles'),
  'search_engine_form.tpl' => array('Search Engine Form', $se_id, 'Search Engine Form'),
  'search_results.tpl' => array('Search Engine Results', $se_id, 'Search Engine Results')
);

// Loop to add DEFAULT templates first:
echo '<br /><b>Adding default system templates...</b>';
foreach($new_tmpl as $tpl_name => $tpl_data)
{
  $rev_id = 0;
  $content = SD_Smarty::GetTemplateContentFor($tpl_data[1], $tpl_name, $rev_id);
  if($content !== false)
  {
    // Replace existing template if it is empty
    if(trim($content)=='')
    {
      if($res = SD_Smarty::CreateTemplateFromFile($tpl_data[1], $tpl_path_def, $tpl_name,
                                                  $tpl_data[0], $tpl_data[2], false,
                                                  'Frontpage', true))
        echo '<br /><b>Updated existing, but empty template "'.$tpl_name.'"</b>';
    }
  }
  else
  {
    $isSys = ($tpl_data[1] == 0) || ($tpl_data[1] == 10)  || ($tpl_data[1] == 11) ? 1 : 0;
    $res = SD_Smarty::CreateTemplateFromFile($tpl_data[1], $tpl_path_def, $tpl_name,
                                             $tpl_data[0], $tpl_data[2], $isSys);
    if($res === FALSE)
    {
      echo '<br />Template "'.$tpl_name.'" not added, probably already existing.';
    }
    else
    {
      echo '<br />Template "'.$tpl_name.'" added.';
      // Add existing, custom templates as newest revisions
      // that would preced defaults (such as for articles):
      if(is_file($tpl_path.$tpl_name) && is_readable($tpl_path.$tpl_name))
      {
        SD_Smarty::AddTemplateRevisionFromFile($tpl_data[1], $tpl_path, $tpl_name, $tpl_data[2]);
      }
    }
  }
}
echo '<br /><b>Default system templates imported.</b><br />';

/*
// Clean up potentially duplicate entries detected since 3.5.0:
$dupe_count = 0;
if($getdupes=$DB->query('SELECT adminpageid, pluginid, varname, count(*) phrasecount, min(adminphraseid) phraseminid
FROM '.PRGM_TABLE_PREFIX.'adminphrases a1
GROUP BY adminpageid, pluginid, varname
HAVING COUNT(*) > 1'))
{
  while($row = $DB->fetch_array($getdupes,null,MYSQL_ASSOC))
  {
    if($row['phrasecount'] > 1)
    {
      $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'adminphrases'.
                 " WHERE adminpageid = %d AND pluginid = %d AND varname = '%s'".
                 ' AND adminphraseid > %d',
                 $row['adminpageid'], $row['pluginid'], $DB->escape_string($row['varname']), $row['phraseminid']);
      if($count = $DB->affected_rows())
      {
        $dupe_count += $count;
        echo $count.' duplicate phrase(s) removed for "'.$row['varname'].'" (pluginid: '.$row['pluginid'].')<br />';
      }
    }
  }
}
if($dupe_count)
{
  echo '<strong>'.$dupe_count.' duplicate phrase(s) removed in total.</strong><br /><br />';
}
*/


