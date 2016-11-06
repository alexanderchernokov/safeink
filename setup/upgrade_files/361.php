<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

// Fix wrongly inserted phrases for Forum plugin (since 3.6.0)
if($forumid = GetPluginID('Forum'))
{
  $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'adminphrases'.
             ' WHERE adminpageid = 0 AND pluginid = 0'.
             " AND varname IN ('subforums','likes_enabled','moderated_topics','moderated_posts','err_reset_invalid_parent')");

  InsertAdminPhrase($forumid, 'subforums', 'Subforums');
  InsertAdminPhrase($forumid, 'likes_enabled', 'Likes enabled');
  InsertAdminPhrase($forumid, 'moderated_topics', 'Moderated Topics:');
  InsertAdminPhrase($forumid, 'moderated_posts', 'Moderated Posts:');
  InsertAdminPhrase($forumid, 'err_reset_invalid_parent', 'Forum had INVALID parent forum, reset!');
  unset($forumid);
}

InsertPluginSetting(11, 'Options', 'Disable Email Editing',
  'Disable editing of email address for logged in users (default: No)?',
  'yesno', '0', 210);
InsertPluginSetting(12, 'prevention_options', 'Allowed Email Domains',
  'Enter a list of <strong>exclusively allowed</strong> email domains (one per line) for new registrations.<br />
  If this is not empty, any new registering user must provide an email
  belonging to one of the listed domains. To allow a specific domain enter
  e.g. <strong>@domain.com</strong> without any wildcard characters.',
  'textarea', '', 5, true);
