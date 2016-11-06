<?php
if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

// ####################### DETERMINE CURRENT DIRECTORY ########################
$plugin_folder  = sd_GetCurrentFolder(__FILE__);

// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,4))
{
  if(strlen($installtype))
  {
    echo 'Chatterbox ('.$plugin_folder.'): you need to upgrade to '.PRGM_NAME.' v3.4+ to use this plugin.<br />';
  }
  return false;
}

// ############################################################################
// PLUGIN INFORMATION
// ############################################################################
$uniqueid       = 7;
$pluginname     = 'Chatterbox';
$version        = '3.7.0';
$pluginpath     = 'p7_chatterbox/chatterbox.php';
$settingspath   = 'p7_chatterbox/p7_settings.php';
$authorname     = 'subdreamer_web';
$authorlink     = 'http://www.subdreamer.com';
$pluginsettings = '59';


// ############################################################################
// UPGRADE PLUGIN
// ############################################################################

function p7_Upgrade_321()
{
  global $DB, $pluginsettings, $uniqueid;

  ConvertPluginSettings($uniqueid);

  $DB->query("ALTER TABLE {p".$uniqueid."_chatterbox} COLLATE utf8_general_ci");
  $DB->query("ALTER TABLE {p".$uniqueid."_chatterbox} CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");

  $DB->query("DELETE FROM {pluginsettings} WHERE pluginid = %d AND title = 'Enable Smilies'", $uniqueid);

  $DB->query('UPDATE {plugins} SET settings = %d WHERE pluginid = %d', $pluginsettings, $uniqueid);
  InsertPhrase($uniqueid, 'repeat_message', 'Repeated message not posted.');
  InsertPhrase($uniqueid, 'chatterbox_history', 'Chatterbox History');
  InsertPhrase($uniqueid, 'view_history', 'View History');

  InsertAdminPhrase($uniqueid, 'chatterbox_view_messages',     'View Messages', 2);
  InsertAdminPhrase($uniqueid, 'chatterbox_view_settings',     'View Settings', 2);
  InsertAdminPhrase($uniqueid, 'chatterbox_no_messages',       'No chatterbox messages found.', 2);
  InsertAdminPhrase($uniqueid, 'chatterbox_update_messages',   'Update Messages', 2);
  InsertAdminPhrase($uniqueid, 'chatterbox_messages_updated',  'Messages updated.', 2);

  InsertPluginSetting($uniqueid, 'Options', 'Chatterbox History',  'Display a link to a popup window that displays the message history of your chatterbox.', 'yesno', '1', 8);
  InsertPluginSetting($uniqueid, 'Options', 'Display Avatar',      'Display users forum avatar (default: No)?', 'yesno', '0', 15);
  InsertPluginSetting($uniqueid, 'Options', 'Avatar Image Width',  'Avatar image <strong>width</strong> in Pixels for sizing the image horizontally (default: 80)?<br />Example: <strong>80</strong><br />If empty, no width tag is being used and the image will appear in full width.', 'text', '80', 20);
  InsertPluginSetting($uniqueid, 'Options', 'Avatar Image Height', 'Avatar image <strong>height</strong> in Pixels for sizing the image vertically (default: 80)?<br />Example: <strong>80</strong><br />If empty, no height tag is being used and the image will appear in full height.', 'text', '80', 25);
  InsertPluginSetting($uniqueid, 'Options', 'Chatterbox History',  'Display a link to a popup window that displays the message history of your chatterbox?', 'yesno', '1', 40);
  InsertPluginSetting($uniqueid, 'Options', 'Require Captcha',     'Secure users submission form with a captcha image (default: yes)?', 'yesno', '1', 50);

} //p7_Upgrade_321


function p7_Upgrade_342()
{
  global $DB, $uniqueid;

  InsertAdminPhrase($uniqueid, 'chatterbox_ip_address', 'IP Address', 2);
  $DB->add_tablecolumn(TABLE_PREFIX.'p'.$uniqueid.'_chatterbox', 'ipaddress', 'VARCHAR(40) collate utf8_unicode_ci', "NOT NULL DEFAULT ''");
  $DB->add_tablecolumn(TABLE_PREFIX.'p'.$uniqueid.'_chatterbox', 'userid', 'INT(11)', 'NULL');
  DeleteAdminPhrase($uniqueid, 'avatar_column',2);
  DeleteAdminPhrase($uniqueid, 'avatar_column_descr',2);
  DeletePluginSetting($uniqueid, 'Options', 'avatar_column');
  InsertPluginSetting($uniqueid, 'Options', 'Censor Messages',
    'Automatically censor all messages using the <strong>Censor Words</strong> list (default: No)?<br />The censored words list can be maintained in the <strong>Main Settings</strong> page.', 'yesno', '0', 7);

} //p7_Upgrade_342


function p7_Upgrade_345()
{
  global $DB, $uniqueid;

  // Fix old admin phrases
  $DB->query('UPDATE '.PRGM_TABLE_PREFIX."adminphrases SET varname = CONCAT(varname,'r') WHERE pluginid = %d AND varname LIKE '%_desc'",$uniqueid);
  $DB->query('UPDATE '.PRGM_TABLE_PREFIX."pluginsettings SET description = CONCAT(description,'r') WHERE pluginid = %d AND description LIKE '%_desc'",$uniqueid);

  // prepare for IPv6
  $tbl = PRGM_TABLE_PREFIX.'p7_chatterbox';
  $DB->query("ALTER TABLE $tbl CHANGE `ipaddress` `ipaddress` VARCHAR(40) collate utf8_unicode_ci NOT NULL DEFAULT ''");

  InsertPluginSetting($uniqueid, 'Options', 'Enable SFS AntiSpam',
    'Enable the checking of the senders email address and IP address against the <strong>StopForumSpam</strong> (SFS) database (default: No)?<br />
    Note: this requires to query their database remotely and may not be possible on all servers, please try it out.<br />
    Please visit <a href="http://www.stopforumspam.com" target="_blank"><strong>StopForumSpam.com</strong></a> for further information
    and terms of usage. If enabled, please consider supporting them by donating to their project.', 'yesno','0', 100);
  InsertPluginSetting($uniqueid, 'Options', 'Enable Blocklist Checks',
    'Enable checking of IP address against one or more <strong>blocklists</strong> (default: Disabled)?<br />
    Note: this requires to query the lists remotely and may not be technically possible on all servers, please try it out
    and check frequently your System Log (Settings page) for warnings or error messages.<br />
    Please visit any used list <strong>BEFORE</strong> enabling and consult their respective terms of use/usage policies.<br />
    <strong>Not obeying their rules may get your server IP blacklisted or banned!</strong>',
    "select-multi:\r\n0|Disabled\r\n1|sbl.spamhaus.org\r\n2|zen.spamhaus.org\r\n4|multi.sburl.org\r\n8|bl.spamcop.net\r\n16|dnsbl.njabl.org\r\n32|dnsbl.sorbs.net",
    0, 110);

} //p7_Upgrade_345


// ############################################################################
// INSTALL PLUGIN
// ############################################################################

if($installtype == 'install')
{
  $CSS = new CSS();
  $CSS->InsertCSS($pluginname, 'plugins/'.$plugin_folder.'/css/default.css',true,null,$uniqueid); #2013-10-05
  unset($CSS);

  $DB->query("CREATE TABLE IF NOT EXISTS {p7_chatterbox} (
    `commentid` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `categoryid` int(11) UNSIGNED NOT NULL DEFAULT 0,
    `username` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
    `comment` TEXT COLLATE utf8_unicode_ci NULL,
    `datecreated` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`commentid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

  $DB->query("INSERT INTO {phrases} (`phraseid`, `pluginid`, `varname`, `defaultphrase`) VALUES
  (NULL, $uniqueid, 'name', 'Name:'),
  (NULL, $uniqueid, 'comment', 'Comment:'),
  (NULL, $uniqueid, 'say', 'Say'),
  (NULL, $uniqueid, 'no_username', 'No username supplied!'),
  (NULL, $uniqueid, 'no_comment', 'No comment supplied!'),
  (NULL, $uniqueid, 'view_history', 'View Message History'),
  (NULL, $uniqueid, 'delete_comment', 'Delete Comment')");

  $DB->query("INSERT INTO {pluginsettings} (`settingid`, `pluginid`, `groupname`, `title`, `description`, `input`, `value`, `displayorder`) VALUES
  (NULL, $uniqueid, 'Options', 'Number of comments to display', 'Select the number of comments you would like to be displayed in your chatterbox:', 'text', '5', 3),
  (NULL, $uniqueid, 'Options', 'Word Wrap', 'Wraps a string to the given number of characters (0 = disabled):', 'text', '30', 4),
  (NULL, $uniqueid, 'Options', 'Maximum Username Length', 'Enter the maximum length in characters for the username:', 'text', '15', 5),
  (NULL, $uniqueid, 'Options', 'Maximum Comment Length', 'Enter the maximum length in characters for comments:', 'text', '35', 6),
  (NULL, $uniqueid, 'Options', 'Category Targeting', 'Only display messages which were posted in the category where the Chatterbox resides.', 'yesno', '1', 8),
  (NULL, $uniqueid, 'Options', 'Display Date', 'Display the date and time the message was posted?', 'yesno', '0', 10),
  (NULL, $uniqueid, 'Options', 'Time Format', 'Format in which the time is presented:<br /><br />See: <a href=\'http://us2.php.net/manual/en/function.date.php\' target=\'_blank\'>http://us2.php.net/manual/en/function.date.php</a>', 'text', 'h:i:s A', 11)");

  p7_Upgrade_321();
  p7_Upgrade_342();
  p7_Upgrade_345();

} //install


if(!empty($currentversion) && ($installtype == 'upgrade'))
{
  if($currentversion != '3.2.1')
  {
    p7_Upgrade_321();
    UpdatePluginVersion($uniqueid, '3.2.1');
    $currentversion = '3.2.1';
  }
  if($currentversion == '3.2.1')
  {
    UpdatePluginVersion($uniqueid, '3.4.0');
    $currentversion = '3.4.0';
  }
  if($currentversion == '3.4.0')
  {
    /*
    + added actual Avatar display
    + added storage of user IP address with every message and admin view
    + added censoring option (new with SD 3.4.2)
    + added username-linking for member pages
    */
    p7_Upgrade_342();
    UpdatePluginVersion($uniqueid, '3.4.2');
    $currentversion = '3.4.2';
  }
  if(($currentversion == '3.4.2') || ($currentversion == '3.4.3') || ($currentversion == '3.4.4'))
  {
    /*
    + added antispam features (SFS, selection of blocklists)
    + added honeytrap
    */
    p7_Upgrade_345();
    UpdatePluginVersion($uniqueid, '3.4.5');
    $currentversion = '3.4.5';
  }
  if($currentversion == '3.4.5')
  {
    $DB->query("ALTER TABLE {p".$uniqueid."_chatterbox} COLLATE utf8_unicode_ci");
    $DB->query("ALTER TABLE {p".$uniqueid."_chatterbox} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci");
    UpdatePluginVersion($uniqueid, '3.4.6');
    $currentversion = '3.4.6';
  }
  if($currentversion == '3.4.6') #2013-10-05
  {
    $CSS = new CSS();
    $CSS->InsertCSS($pluginname, 'plugins/'.$plugin_folder.'/css/default.css',true,null,$uniqueid); #2013-10-05
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
  $DB->query('DROP TABLE IF EXISTS {p'.$uniqueid.'_chatterbox}');
}
