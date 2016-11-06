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
    echo 'Link Directory ('.$plugin_folder.'): you need to upgrade to '.PRGM_NAME.' v3.4+ to use this plugin.<br />';
  }
  return false;
}

// ############################################################################
// PLUGIN INFORMATION
// ############################################################################
// Do NOT set $pluginid or $pluginid here!

$pluginname     = 'Link Directory';
$version        = '3.4.7';
$pluginpath     = $plugin_folder.'/link_directory.php';
$settingspath   = $plugin_folder.'/p16_settings.php';
$authorname     = 'subdreamer_web';
$authorlink     = 'http://www.subdreamer.com';
$pluginsettings = '19';

/* ######################### PRE-CHECK FOR CLONING ############################
   If no install type is set, then SD is scanning for new plugins.
   This is the right time to pre-check, if a plugin with the same name within
   a different folder has already been installed.
*/

// 1. Check if this plugin is already installed from within the current folder:
// Only *IF* it is installed, set the plugin id so that it is treated
// as installed and does not show up for installation again
if($inst_check_id = GetPluginIDbyFolder($plugin_folder))
{
  $pluginid = $inst_check_id;
}
else
// 2. Check if a plugin with the same name is already installed
if($inst_check_id = GetPluginID($pluginname))
{
  $pluginname .= ' ('.$plugin_folder.')';
}
$authorname .= "<br />Plugin folder: '<b>plugins/$plugin_folder</b>'";

if(empty($installtype))
{
  // Nothing else to do, so return
  return true;
}



if(!function_exists('LinkDirectoryUpgrade346'))
{
function LinkDirectoryUpgrade346()
{
  global $DB, $pluginid, $plugin_folder;

  if(empty($pluginid)) return;
  $ld_prefix = PRGM_TABLE_PREFIX.'p'.$pluginid;

  DeletePluginSetting($pluginid, 'Options', 'require_vvc_code');
  DeletePluginSetting($pluginid, 'Options', 'require_captcha');
  InsertPluginSetting($pluginid, 'Options', 'Links per Page',           'How many links would you like to be displayed per page?', 'text', '20', 1);
  InsertPluginSetting($pluginid, 'Options', 'Number of links per Row',  'Enter the number of links to be displayed per row:', 'text', '4', 2);
  InsertPluginSetting($pluginid, 'Options', 'Link Notification',        'This email address will receive an email message when a new link is submitted:<br />\r\nSeparate multiple addresses with commas.', 'text', '', 3);
  InsertPluginSetting($pluginid, 'Options', 'Display Menu',             'Displays the link directory navigation:', 'yesno', '1', 6);
  InsertPluginSetting($pluginid, 'Options', 'Auto Approve Links',       'Automatically approve user submitted links (default: No)?', 'yesno', '0', 7);
  InsertPluginSetting($pluginid, 'Options', 'Enable Hover Effect',      'Enable a hover colour effect when mouse moves over a link cell (default: yes)?<br />Note: requires JavaScript enabled in client browser.', 'yesno', '1', 9);
  InsertPluginSetting($pluginid, 'Options', 'Background Hover Colour',  'Background colour when mouse moves over a link/section (with Javascript enabled)?', 'text', '#ACACAC', 10);
  InsertPluginSetting($pluginid, 'Options', 'Link Row Height',          'Minimum height of sections or link rows in Pixels:<br />Default: <strong>0</strong> to let browser decide.', 'text', '0', 15);
  InsertPluginSetting($pluginid, 'Options', 'Simple Display Layout',    'If Yes, the Link Directory will use the old display format, otherwise a new layout with JS-hovering support is used (if above hover option is also Yes).', 'yesno', '0', 20);
  InsertPluginSetting($pluginid, 'Options', 'Allow User Thumbnail',     'If Yes, users may submit an image link. This defaults to No as it may pose a security risk.', 'yesno', '0', 30);

  $DB->query("DELETE FROM {adminphrases} WHERE pluginid = %d and varname in ('require_captcha','require_captcha_descr','require_vvc_code','require_vvc_code_descr')",$pluginid);

  InsertPhrase($pluginid, 'goto_link',               'Visit %s website');
  InsertPhrase($pluginid, 'goto_section',            'Go to %s section');
  InsertPhrase($pluginid, 'count_suffix',            'link(s)');
  InsertPhrase($pluginid, 'sections',                'Sections:');
  InsertPhrase($pluginid, 'submitting_link',         'Submitting Link');
  InsertPhrase($pluginid, 'submit_link',             'Submit Link');
  InsertPhrase($pluginid, 'submit_a_link',           'Click here to submit a link to this section!');
  InsertPhrase($pluginid, 'submitted_by',            'Submitted by:');
  InsertPhrase($pluginid, 'your_name',               'Your Name:');
  InsertPhrase($pluginid, 'website_name',            'Website Name:');
  InsertPhrase($pluginid, 'website_url',             'Website URL:');
  InsertPhrase($pluginid, 'description',             'Description:');
  InsertPhrase($pluginid, 'previous_links',          '&laquo; Previous Links');
  InsertPhrase($pluginid, 'more_links',              'More Links &raquo;');
  InsertPhrase($pluginid, 'enter_name',              'Please enter your name.');
  InsertPhrase($pluginid, 'enter_site_name',         'Please enter the name of the site.');
  InsertPhrase($pluginid, 'enter_site_url',          'Please enter the url of the site.');
  InsertPhrase($pluginid, 'url_invalid',             'Website URL is invalid.');
  InsertPhrase($pluginid, 'enter_description',       'Please enter the description.');
  InsertPhrase($pluginid, 'link_submitted',          'Thanks! Your link has been submitted.');
  InsertPhrase($pluginid, 'notify_email_from',       'Link Directory Plugin');
  InsertPhrase($pluginid, 'notify_email_subject',    'New link submitted to your website!');
  InsertPhrase($pluginid, 'notify_email_message',    'A new link has been submitted to your link directory.');
  InsertPhrase($pluginid, 'notify_email_author',     'Author');
  InsertPhrase($pluginid, 'notify_email_website',    'Website Name');
  InsertPhrase($pluginid, 'notify_email_url',        'Website URL');
  InsertPhrase($pluginid, 'notify_email_description','Description');
  InsertPhrase($pluginid, 'notify_email_thumbnail',  'Thumbnail Image Link:');
  InsertPhrase($pluginid, 'thumbnail_url',           'Thumbnail URL:');
  InsertPhrase($pluginid, 'thumb_url_error',         'Thumbnail URL not valid:<br />File must be only jpg, gif or png image format.');
  InsertPhrase($pluginid, 'sfs_error',               'No link submissions allowed from hosts flagged by StopForumSpam.com!');
  InsertPhrase($pluginid, 'error_invalid_form',      'Invalid form submission, declined!');
  InsertPhrase($pluginid, 'error_invalid_section',   'Sorry, but the specified section is not online or does not exist!');
  InsertPhrase($pluginid, 'error_duplicate_entry',   'Sorry, but the specified link already exists!');

  $DB->add_tablecolumn($ld_prefix.'_links', 'ipaddress', 'VARCHAR(40)', "collate utf8_unicode_ci NOT NULL DEFAULT ''");
  $DB->add_tableindex($ld_prefix.'_sections', 'parentid', 'parentid');

  $CSS = new CSS();
  $CSS->InsertCSS('Link Directory ('.$pluginid.')', 'plugins/'.$plugin_folder.'/css/default.css',true,null,$pluginid);
  unset($CSS);

  //v3.4.6:
  InsertPluginSetting($pluginid, 'Options', 'Link Description Input',
    'If set to Yes (default), an entry field for the description of the submitted link will be displayed.', 'yesno', '1', 100);
  InsertPluginSetting($pluginid, 'Options', 'Link Description Required',
    'If set to Yes (default), a description for the submitted link must be provided when submitted, otherwise it can be left empty.', 'yesno', '1', 110);
  InsertPluginSetting($pluginid, 'Options', 'Website Name Input',
    'If set to Yes (default), an entry field for the link name will be displayed.', 'yesno', '1', 120);
  InsertPluginSetting($pluginid, 'Options', 'Website Name Required',
    'If set to Yes (default), a website name must be provided when submitted, otherwise it can be left empty.', 'yesno', '1', 130);
  InsertPluginSetting($pluginid, 'Options', 'Author Name Input',
    'For Guests: if set to Yes (default), an entry field for the guests name will be displayed.', 'yesno', '1', 140);
  InsertPluginSetting($pluginid, 'Options', 'Author Name Required',
    'For Guests: if set to Yes (default), the guest must provide a name when submitting a link, otherwise it can be left empty.', 'yesno', '1', 150);
  InsertPluginSetting($pluginid, 'Options', 'Allow duplicate Links',
    'Allow duplicate links and titles per section (default: No)?', 'yesno', '0', 160);

  InsertPluginSetting($pluginid, 'Options', 'Enable SFS AntiSpam',
    'Enable the checking of the senders email address and IP address against the <strong>StopForumSpam</strong> (SFS) database (default: No)?<br />
    Note: this requires to query their database remotely and may not be possible on all servers, please try it out.<br />
    Please visit <a href="http://www.stopforumspam.com" target="_blank"><strong>StopForumSpam.com</strong></a> for further information
    and terms of usage. If enabled, please consider supporting them by donating to their project.', 'yesno','0', 200);
  InsertPluginSetting($pluginid, 'Options', 'Enable Blocklist Checks',
    'Enable checking of IP address against one or more <strong>blocklists</strong> (default: Disabled)?<br />
    Note: this requires to query the lists remotely and may not be technically possible on all servers, please try it out
    and check frequently your System Log (Settings page) for warnings or error messages.<br />
    Please visit any used list <strong>BEFORE</strong> enabling and consult their respective terms of use/usage policies.<br />
    <strong>Not obeying their rules may get your server IP blacklisted or banned!</strong>',
    "select-multi:\r\n0|Disabled\r\n1|sbl.spamhaus.org\r\n2|zen.spamhaus.org\r\n4|multi.sburl.org\r\n8|bl.spamcop.net\r\n16|dnsbl.njabl.org\r\n32|dnsbl.sorbs.net",
    0, 210);
}
} //DO NOT REMOVE!

// ############################################################################
// INSTALL PLUGIN
// ############################################################################

if(($installtype == 'install') && empty($pluginid))
{
  if(empty($pluginid))
  {
    if(!$pluginid = CreatePluginID($pluginname)) return false;
  }

  $DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."p".$pluginid."_links (
  linkid       INT(10)      UNSIGNED NOT NULL AUTO_INCREMENT,
  sectionid    INT(10)      UNSIGNED NOT NULL DEFAULT 0,
  activated    TINYINT(1)            NOT NULL DEFAULT 0,
  allowsmilies TINYINT(1)            NOT NULL DEFAULT 0,
  showauthor   TINYINT(1)            NOT NULL DEFAULT 0,
  author       VARCHAR(200) collate utf8_unicode_ci NOT NULL DEFAULT '',
  title        VARCHAR(200) collate utf8_unicode_ci NOT NULL DEFAULT '',
  url          TEXT         collate utf8_unicode_ci NOT NULL,
  description  TEXT         collate utf8_unicode_ci NOT NULL,
  thumbnail    VARCHAR(250) collate utf8_unicode_ci NULL,
  ipaddress    VARCHAR(40)  collate utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY linkid (linkid),
  INDEX (sectionid))
  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

  $DB->query("CREATE TABLE IF NOT EXISTS ".PRGM_TABLE_PREFIX."p".$pluginid."_sections (
  sectionid   INT(10)      UNSIGNED NOT NULL              AUTO_INCREMENT,
  parentid    INT(10)      UNSIGNED NOT NULL DEFAULT 0,
  activated   TINYINT(1)            NOT NULL DEFAULT 0,
  name        VARCHAR(128) collate utf8_unicode_ci NOT NULL DEFAULT '',
  description TEXT         collate utf8_unicode_ci NOT NULL,
  sorting     VARCHAR(32)  collate utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY sectionid (sectionid),
  INDEX (name),
  INDEX (parentid))
  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

  // insert default section
  $DB->query("INSERT INTO ".PRGM_TABLE_PREFIX."p".$pluginid."_sections
  (sectionid, parentid, activated, name, description, sorting) VALUES (null, 0, 1, 'Links', '', 'Newest First')");

  LinkDirectoryUpgrade346();

} //install


// ############################################################################
// UPGRADE PLUGIN
// ############################################################################

if(!empty($currentversion) && ($installtype == 'upgrade'))
{
  $ld_prefix = PRGM_TABLE_PREFIX.'p'.$pluginid;
  if(version_compare(substr($currentversion,0,3),'3.3','lt'))
  {
    $DB->query('ALTER TABLE '.$ld_prefix.'_links COLLATE utf8_unicode_ci');
    $DB->query('ALTER TABLE '.$ld_prefix.'_links CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
    $DB->query('ALTER TABLE '.$ld_prefix.'_sections COLLATE utf8_unicode_ci');
    $DB->query('ALTER TABLE '.$ld_prefix.'_sections CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');

    // Convert all plugin settings to new naming method to allow translation
    ConvertPluginSettings($pluginid);

    UpdatePluginVersion($pluginid, '3.3.0');
    $currentversion = '3.3.0';
  }
  // v3.4.6: Shortened code, moved code to LinkDirectoryUpgrade346
  if((substr($currentversion,0,3) == '3.3') || version_compare(substr($currentversion,0,3),'3.4.6','lt'))
  {
    LinkDirectoryUpgrade346();

    UpdatePluginVersion($pluginid, '3.4.6');
    $currentversion = '3.4.6';
  }
  // v3.4.7
  if($currentversion=='3.4.6')
  {
    $DB->query('ALTER TABLE '.$ld_prefix.'_links COLLATE utf8_unicode_ci');
    $DB->query('ALTER TABLE '.$ld_prefix.'_links CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
    $DB->query('ALTER TABLE '.$ld_prefix.'_sections COLLATE utf8_unicode_ci');
    $DB->query('ALTER TABLE '.$ld_prefix.'_sections CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');

    UpdatePluginVersion($pluginid, '3.4.7');
    $currentversion = '3.4.7';
  }
  unset($ld_prefix);
} //upgrade


// ############################################################################
// UNINSTALL PLUGIN
// ############################################################################

if(($installtype == 'uninstall') && !empty($pluginid))
{
  $ld_prefix = PRGM_TABLE_PREFIX.'p'.$pluginid;
  $DB->query('DROP TABLE IF EXISTS '.$ld_prefix.'_links');
  $DB->query('DROP TABLE IF EXISTS '.$ld_prefix.'_sections');
  unset($ld_prefix);
}
