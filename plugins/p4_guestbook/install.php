<?php
if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,7))
{
  if(strlen($installtype))
  {
    echo 'Guestbook ('.$plugin_folder.'): you need to upgrade to '.PRGM_NAME.' v3.7+ to use this plugin.<br />';
  }
  return false;
}

// ####################### DETERMINE CURRENT DIRECTORY ########################
$plugin_folder  = sd_GetCurrentFolder(__FILE__);

// ############################################################################
// PLUGIN INFORMATION
// ############################################################################

$uniqueid       = 4;
$pluginname     = 'Guestbook';
$version        = '3.7.0';
$pluginpath     = $plugin_folder.'/guestbook.php';
$settingspath   = $plugin_folder.'/p4_settings.php';
$authorname     = 'subdreamer_web';
$authorlink     = 'http://www.subdreamer.com/';
$pluginsettings = '59';


// ############################################################################
// INSTALL PLUGIN
// ############################################################################

if($installtype == 'install')
{
  $CSS = new CSS();
  $CSS->InsertCSS($pluginname, 'plugins/'.$plugin_folder.'/css/default.css',true,null,$uniqueid); #2013-10-05
  unset($CSS);

  $DB->query("CREATE TABLE IF NOT EXISTS {p4_guestbook} (
    `messageid` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
    `websitename` VARCHAR(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
    `website` VARCHAR(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
    `message` TEXT COLLATE utf8_unicode_ci NULL,
    `datecreated` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `ipaddress` VARCHAR(40) collate utf8_unicode_ci NOT NULL DEFAULT '',
    PRIMARY KEY (`messageid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

  $DB->query("INSERT INTO {phrases} (`phraseid`, `pluginid`, `varname`, `defaultphrase`) VALUES
  (NULL, $uniqueid, 'name', 'Name:'),
  (NULL, $uniqueid, 'message', 'Message:'),
  (NULL, $uniqueid, 'submit_message', 'Submit Message'),
  (NULL, $uniqueid, 'no_username', 'Please fill out the username field.'),
  (NULL, $uniqueid, 'no_message', 'Please fill out the message field.'),
  (NULL, $uniqueid, 'message_too_long', 'Message length must be less than'),
  (NULL, $uniqueid, 'url_invalid', 'Website URL is invalid.'),
  (NULL, $uniqueid, 'no_site_name', 'Please enter the site name of the url specified.'),
  (NULL, $uniqueid, 'repeat_comment', 'Repeat comment not posted.'),
  (NULL, $uniqueid, 'sign_guestbook', 'Click here to sign the guestbook!'),
  (NULL, $uniqueid, 'characters', 'characters'),
  (NULL, $uniqueid, 'website_url', 'Website URL:'),
  (NULL, $uniqueid, 'website_name', 'Website Name:')");

  $DB->query("INSERT INTO {pluginsettings} (`settingid`, `pluginid`, `groupname`, `title`, `description`, `input`, `value`, `displayorder`) VALUES
  (NULL, $uniqueid, 'Admin Options', 'Display Delete Link', 'Display <strong>delete</strong> link on frontpage for administrators and moderators (default: yes)?', 'yesno', '1', 1),
  (NULL, $uniqueid, 'Options', 'Messages Per Page', 'Enter the number of messages you would you like to see in the guestbook:', 'text', '5', 3),
  (NULL, $uniqueid, 'Options', 'Message Length', 'Please enter the maximum length a message can be (in characters):', 'text', '100', 4),
  (NULL, $uniqueid, 'Options', 'Word Wrap', 'Wraps a string to the given number of characters (0 = disabled):', 'text', '0', 5)");

  p4_Upgrade_321();
  p4_Upgrade_342();
  p4_Upgrade_344();
}


// ############################################################################
// UPGRADE PLUGIN
// ############################################################################

function p4_Upgrade_321()
{
  global $DB, $uniqueid;

  $DB->query('ALTER TABLE {p4_guestbook} COLLATE utf8_unicode_ci');
  $DB->query('ALTER TABLE {p4_guestbook} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');

  $DB->query('UPDATE {plugins} SET settings = 59 WHERE pluginid = %d',$uniqueid);

  // Convert all plugin settings to new naming method to allow translation
  ConvertPluginSettings($uniqueid);

  InsertPluginSetting($uniqueid, 'Options', 'Show Post Date', 'Show the date the message was posted?', 'yesno', '1', 6);

  InsertPhrase($uniqueid, 'delete_message', '[Delete Message]');
  InsertPhrase($uniqueid, 'message_deleted', 'Message deleted.');

  InsertAdminPhrase($uniqueid, 'guestbook_view_messages',     'View Guestbook Messages', 2);
  InsertAdminPhrase($uniqueid, 'guestbook_settings',          'Guestbook Settings', 2);
  InsertAdminPhrase($uniqueid, 'guestbook_no_messages_long',  'No guestbook messages on page %d, try going back a previous page.', 2);
  InsertAdminPhrase($uniqueid, 'guestbook_no_messages_short', 'No messages in your guestbook.', 2);
  InsertAdminPhrase($uniqueid, 'guestbook_update_messages',   'Update Messages', 2);
  InsertAdminPhrase($uniqueid, 'guestbook_messages_updated',  'Guestbook Messages Updated', 2);

} //p4_Upgrade_321

function p4_Upgrade_342()
{
  global $DB, $uniqueid;
  InsertAdminPhrase($uniqueid, 'admin_options', 'Administrator Options', 2, true);
  InsertAdminPhrase($uniqueid, 'options', 'Options', 2, true);
  $DB->query("DELETE FROM {adminphrases} WHERE pluginid = %d AND varname IN('message_length_desc','display_delete_link_desc','messages_per_page_desc','word_wrap_desc')",$uniqueid);
  $DB->query("UPDATE {pluginsettings} SET groupname = 'admin_options' WHERE pluginid = %d AND groupname IN ('Admin Options','Admin Settings')",$uniqueid);
  $DB->query("UPDATE {pluginsettings} SET groupname = 'options' WHERE pluginid = %d AND groupname = 'Options'",$uniqueid);
  InsertPluginSetting($uniqueid, 'options', 'Prompt Website Info', 'Prompt the user for website information like website name and link (default: Yes)?<br />If No, these 2 entries will not be displayed for input.', 'yesno', '1', 10);

  InsertPluginSetting($uniqueid, 'options', 'Entry Separator', 'HTML code to separate guestbook entries:', 'text', '<br /><hr />', 9);
  InsertAdminPhrase($uniqueid, 'ip_address', 'IP Address', 2, true);
  InsertPhrase($uniqueid, 'max_length_hint', 'Please note the maximum message length (in characters): ');
}

function p4_Upgrade_344()
{
  global $DB, $uniqueid;

  $tbl = PRGM_TABLE_PREFIX.'p'.$uniqueid.'_guestbook';
  $DB->add_tablecolumn($tbl, 'ipaddress', 'VARCHAR(40)', "COLLATE utf8_unicode_ci NOT NULL DEFAULT ''");
  $DB->query("ALTER TABLE $tbl CHANGE `datecreated` `datecreated` INT(11) UNSIGNED NOT NULL DEFAULT 0");
  $DB->query("ALTER TABLE $tbl CHANGE `ipaddress` `ipaddress` VARCHAR(40) collate utf8_unicode_ci NOT NULL DEFAULT ''");

  InsertPluginSetting($uniqueid, 'options', 'Website Info Required', 'If Website Info is enabled, should user input be required for those 2 fields (default: Yes)?', 'yesno', '1', 12);
  InsertPluginSetting($uniqueid, 'options', 'Enable SFS AntiSpam',
    'Enable the checking of the senders email address and IP address against the <strong>StopForumSpam</strong> (SFS) database (default: No)?<br />
    Note: this requires to query their database remotely and may not be possible on all servers, please try it out.<br />
    Please visit <a href="http://www.stopforumspam.com" target="_blank"><strong>StopForumSpam.com</strong></a> for further information
    and terms of usage. If enabled, please consider supporting them by donating to their project.', 'yesno','0', 30);
  InsertPluginSetting($uniqueid, 'options', 'Enable Blocklist Checks',
    'Enable checking of IP address against one or more <strong>blocklists</strong> (default: Disabled)?<br />
    Note: this requires to query the lists remotely and may not be technically possible on all servers, please try it out
    and check frequently your System Log (Settings page) for warnings or error messages.<br />
    Please visit any used list <strong>BEFORE</strong> enabling and consult their respective terms of use/usage policies.<br />
    <strong>Not obeying their rules may get your server IP blacklisted or banned!</strong>',
    "select-multi:\r\n0|Disabled\r\n1|sbl.spamhaus.org\r\n2|zen.spamhaus.org\r\n4|multi.sburl.org\r\n8|bl.spamcop.net\r\n16|dnsbl.njabl.org\r\n32|dnsbl.sorbs.net",
    0, 40);

  InsertPhrase($uniqueid, 'err_too_long', 'Entered value is too long!');
}

if(!empty($currentversion) && ($installtype == 'upgrade'))
{
  if(version_compare(substr($currentversion,0,3),'3.3','<'))
  {
    p4_Upgrade_321();
    UpdatePluginVersion($uniqueid, '3.3.0');
    $currentversion = '3.3.0';
  }

  if(in_array($currentversion, array('3.3.0','3.4.0','3.4.1')))
  {
    p4_Upgrade_342(); // incl. previous 3.4.0 upgrade steps
    UpdatePluginVersion($uniqueid, '3.4.2');
    $currentversion = '3.4.2';
  }

  if(($currentversion == '3.4.2') || ($currentversion == '3.4.3'))
  {
    /*
    + added antispam features (SFS, selection of blocklists)
    + added honeytrap
    + added field length checks etc.
    */
    p4_Upgrade_344();
    UpdatePluginVersion($uniqueid, '3.4.4');
    $currentversion = '3.4.4';
  }
  if($currentversion=='3.4.4')
  {
    $DB->query('ALTER TABLE {p4_guestbook} COLLATE utf8_unicode_ci');
    $DB->query('ALTER TABLE {p4_guestbook} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
    UpdatePluginVersion($uniqueid, '3.4.5');
    $currentversion = '3.4.5';
  }
  if($currentversion=='3.4.5') #2013-10-05
  {
    $CSS = new CSS();
    $CSS->InsertCSS($pluginname, 'plugins/'.$plugin_folder.'/css/default.css',true,null,$uniqueid);
    unset($CSS);
    UpdatePluginVersion($uniqueid, '3.7.0');
    $currentversion = '3.7.0';
  }
}

// ############################################################################
// UNINSTALL PLUGIN
// ############################################################################

if($installtype == 'uninstall')
{
  $DB->query('DROP TABLE IF EXISTS {p4_guestbook}');
}
