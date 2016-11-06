<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

// not used anymore
$DB->query("DELETE FROM {mainsettings} WHERE varname = 'sdversiontype' LIMIT 1");

// store register path, login path, control panel path in database to avoid extra queries
$user_registration_page_id = $user_profile_page_id = $user_login_panel_page_id = 0;

if($user_login_panel_page_id_arr = $DB->query_first("SELECT categoryid FROM {pagesort} WHERE pluginid = '10' LIMIT 1"))
{
  $user_login_panel_page_id = $user_login_panel_page_id_arr['categoryid'];
}

if($user_profile_page_id_arr = $DB->query_first("SELECT categoryid FROM {pagesort} WHERE pluginid = '11' LIMIT 1"))
{
  $user_profile_page_id = $user_profile_page_id_arr['categoryid'];
}

if($user_registration_page_id_arr = $DB->query_first("SELECT categoryid FROM {pagesort} WHERE pluginid = '12' LIMIT 1"))
{
  $user_registration_page_id = $user_registration_page_id_arr['categoryid'];
}

$DB->query("INSERT INTO {mainsettings} (varname, value, input, description) VALUES
           ('user_login_panel_page_id', $user_login_panel_page_id, '', ''),
           ('user_profile_page_id', $user_profile_page_id, '', ''),
           ('user_registration_page_id', $user_registration_page_id, '', '')");

InsertAdminPhrase(0, 'settings_language_updated', 'Admin Language Updated', 7);
InsertAdminPhrase(0, 'common_page_access_denied', 'You do not have access to view this page.', 0);
InsertAdminPhrase(0, 'common_plugin_settings_updated', 'Plugin Settings Updated', 0);

InsertAdminPhrase(0, 'users_username_exists', 'Username already exists for a different user.', 5);
InsertAdminPhrase(0, 'users_email_exists', 'Email address already exists for a different user', 5);

InsertPhrase(1, 'error_multiple_recaptcha', 'Can not display reCaptcha more than once per page. This form will not submit correctly.', true);
InsertPhrase(1, 'comment_access_denied', 'You do not have access to submit comments.', true);
InsertPhrase(1, 'pagination_next', 'Next', true);
InsertPhrase(1, 'pagination_previous', 'Previous', true);
