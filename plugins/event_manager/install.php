<?php
if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

// ####################### DETERMINE CURRENT DIRECTORY ########################
$plugin_folder  = sd_GetCurrentFolder(__FILE__);

// ############################ PLUGIN INFORMATION ############################
// Do NOT set $uniqueid or $pluginid here!
$base_plugin = $pluginname = 'Event Manager';
$version        = '2.2.1';
$pluginpath     = $plugin_folder.'/eventmanager.php';
$settingspath   = $plugin_folder.'/settings.php';
$authorname     = 'subdreamer_web';
$authorlink     = 'http://www.subdreamer.com/';
$pluginsettings = '27'; // view/submit/comment/admin

// ############################################################################
// VERSION CHECK
// ############################################################################
if(!requiredVersion(3,5))
{
  if(strlen($installtype))
  {
    echo $pluginname.' ('.$plugin_folder.'): you need to upgrade to '.PRGM_NAME.' v3.5+ to use this plugin.<br />';
  }
  return false;
}

if($inst_check_id = GetPluginIDbyFolder($plugin_folder))
{
  $pluginid = $inst_check_id;
}
else
if($inst_check_id = GetPluginID($pluginname))
{
  $pluginname .= ' ('.$plugin_folder.')';
}
$authorname .= "<br />Plugin folder: '<strong>plugins/$plugin_folder</strong>'";

if(empty($installtype))
{
  // Nothing else to do, so return
  return true;
}

// ########################### UPGRADE TO 2.2.1 ###############################

if(!class_exists('EventManagerUpgrader221'))
{
class EventManagerUpgrader221
{
  public $pluginid = 0;
  public $pluginsettings = 27;

  public function EventManagerUpgrader221($pluginid, $pluginsettings)
  {
    $this->pluginid = $pluginid;
    $this->pluginsettings = $pluginsettings;
  }

  function UpgradeAll(&$version)
  {
    if(empty($this->pluginid)) return;

    global $DB, $plugin_folder, $currentversion;
    // plugin language
    InsertPhrase($this->pluginid, 'event_name',   'Event Name');
    InsertPhrase($this->pluginid, 'date',         'Date');
    InsertPhrase($this->pluginid, 'location',     'Location ');
    InsertPhrase($this->pluginid, 'venue',        'Venue');
    InsertPhrase($this->pluginid, 'submit_event', 'Submit Event');
    InsertPhrase($this->pluginid, 'next',         'Next &raquo;');
    InsertPhrase($this->pluginid, 'previous',     '&laquo; Previous');
    InsertPhrase($this->pluginid, 'event_name2',  'Event Name:');
    InsertPhrase($this->pluginid, 'date2',        'Date:');
    InsertPhrase($this->pluginid, 'time',         'Time:');
    InsertPhrase($this->pluginid, 'venue2',       'Venue:');
    InsertPhrase($this->pluginid, 'street',       'Street:');
    InsertPhrase($this->pluginid, 'city',         'City:');
    InsertPhrase($this->pluginid, 'state',        'State:');
    InsertPhrase($this->pluginid, 'country',      'Country:');
    InsertPhrase($this->pluginid, 'description',  'Description:');
    InsertPhrase($this->pluginid, 'success',      'Your event has been submitted and awaits approval.');
    InsertPhrase($this->pluginid, 'return',       'Click here to return to the events page.');
    InsertPhrase($this->pluginid, 'email_fromname',   'Event Manager Plugin');
    InsertPhrase($this->pluginid, 'email_subject',    'New event submitted to your website!');
    InsertPhrase($this->pluginid, 'email_body',       'A new Event has been submitted to your Event Manager.');
    InsertPhrase($this->pluginid, 'success_approved', 'Your event has been submitted and approved.');
    InsertPhrase($this->pluginid, 'untitled',         'Untitled');
    InsertPhrase($this->pluginid, 'none',             'None');

    // Plugin Settings
    InsertPluginSetting($this->pluginid, 'options', 'Events Per Page',     'Enter the number of events you would like to see per page:', 'text', '10', 1);
    InsertPluginSetting($this->pluginid, 'options', 'Upcoming Events Per Page', 'Enter the number of events for the upcoming events display:', 'text', '5', 2);
    InsertPluginSetting($this->pluginid, 'options', 'Event Notification',  'This email address will receive an email message when a new event is submitted:<br />Separate multiple addresses with commas.', 'text', '', 5);
    InsertPluginSetting($this->pluginid, 'options', 'Display US States',   'Display a list of US states for venue selection?',  'yesno', '1', 7);
    InsertPluginSetting($this->pluginid, 'options', '24 Hours Display',    'Display times in 24-hours format (otherwise AM/PM format)?',  'yesno', '1', 8);
    InsertPluginSetting($this->pluginid, 'options', 'Row Striping',        'Display rows with alternating background colors (default: Yes)?<br />If enabled, event rows are assigned alternating HTML class names for different colors (see <strong>rowcol2</strong> and <strong>rowcol3</strong> styles in CSS for this plugin).', 'yesno', '1', 9);
    InsertPluginSetting($this->pluginid, 'options', 'Auto-Approve Events', 'Auto-approve user-submitted events (default: No)?', 'yesno', '0', 10);

    // Cleanup
    $DB->query("DELETE FROM {pluginsettings} WHERE (title LIKE 'allow_event_submission%') OR (title LIKE 'Allow Event Submission%') AND pluginid = %d",$this->pluginid);
    $DB->query("DELETE FROM {pluginsettings} WHERE (title IN ('Require VVC Code', 'require_vvc_code')) AND pluginid = %d",$this->pluginid);
    $DB->query("UPDATE {pluginsettings} SET displayorder = 2 WHERE title = 'upcoming_events_per_page' AND pluginid = %d",$this->pluginid);
    $DB->query("UPDATE {pluginsettings} SET displayorder = 5 WHERE title = 'event_notification' AND pluginid = %d",$this->pluginid);
    $DB->query("UPDATE {pluginsettings} SET groupname = 'options' WHERE groupname = 'Options' AND pluginid = %d",$this->pluginid);

    // Resize columns
    $tbl_ok = false;
    $tbl = PRGM_TABLE_PREFIX.'p'.$this->pluginid.'_events';
    if($DB->table_exists($tbl))
    {
      $tbl_ok = true;
    }
    else
    {
      $tbl = PRGM_TABLE_PREFIX.'p18_events';
      if($DB->table_exists($tbl))
      {
        $tbl_ok = true;
      }
    }
    if($tbl_ok)
    {
      $DB->query("ALTER TABLE $tbl CHANGE `title` `title` VARCHAR(254) collate utf8_unicode_ci NOT NULL DEFAULT ''");
      $DB->query("ALTER TABLE $tbl CHANGE `city` `city` VARCHAR(128) collate utf8_unicode_ci NOT NULL DEFAULT ''");
      $DB->query("ALTER TABLE $tbl CHANGE `state` `state` VARCHAR(128) collate utf8_unicode_ci NOT NULL DEFAULT ''");
      $DB->query("ALTER TABLE $tbl CHANGE `country` `country` VARCHAR(64) collate utf8_unicode_ci NOT NULL DEFAULT ''");
      if($DB->index_exists($tbl, 'country'))
      {
        $DB->query("ALTER TABLE $tbl DROP INDEX `country`");
      }
      if(!$DB->index_exists($tbl, 'eventcountry'))
      {
        $DB->add_tableindex($tbl, 'country', 'eventcountry');
      }
    }
    $DB->add_tablecolumn($tbl,'image', 'VARCHAR(128)', "collate utf8_unicode_ci NOT NULL DEFAULT ''");
    $DB->add_tablecolumn($tbl,'thumbnail', 'VARCHAR(128)', "collate utf8_unicode_ci NOT NULL DEFAULT ''");

    // *****************
    // v2 starts here:
    // *****************
    InsertPluginSetting($this->pluginid, 'options', 'Show Old Events', 'Show old events (dated in the past), too? Otherwise only upcoming events will show (default: Yes)?', 'yesno', '1', 20);
    InsertAdminPhrase($this->pluginid, 'options', 'Options');
    InsertAdminPhrase($this->pluginid, 'add_event_or_settings', 'Add an Event or View Settings');
    InsertAdminPhrase($this->pluginid, 'add_event', 'Add Event');
    InsertAdminPhrase($this->pluginid, 'add_event_descr', 'Add a new event to the Event Manager:');
    InsertAdminPhrase($this->pluginid, 'edit_event', 'Edit Event');
    InsertAdminPhrase($this->pluginid, 'delete_event', 'Delete Event:');
    InsertAdminPhrase($this->pluginid, 'delete_event_descr', 'Delete this Event?');
    InsertAdminPhrase($this->pluginid, 'delete_events', 'Delete Events');
    InsertAdminPhrase($this->pluginid, 'new_event', 'New Event');
    InsertAdminPhrase($this->pluginid, 'no_events', 'No events available.');
    InsertAdminPhrase($this->pluginid, 'page_next', 'Next Page');
    InsertAdminPhrase($this->pluginid, 'page_previous', 'Previous Page');
    InsertAdminPhrase($this->pluginid, 'settings', 'Settings');
    InsertAdminPhrase($this->pluginid, 'settings_descr', 'View and change the settings of the Event Manager:');
    InsertAdminPhrase($this->pluginid, 'status', 'Status');
    InsertAdminPhrase($this->pluginid, 'online', 'Online');
    InsertAdminPhrase($this->pluginid, 'offline', 'Offline');
    InsertAdminPhrase($this->pluginid, 'pagesize', 'Pagesize:');
    InsertAdminPhrase($this->pluginid, 'all_events', 'All Events');
    InsertAdminPhrase($this->pluginid, 'view_settings', 'View Settings');
    InsertAdminPhrase($this->pluginid, 'delete_image', 'Delete Image');
    InsertAdminPhrase($this->pluginid, 'delete_thumbnail', 'Delete Thumbnail');
    InsertAdminPhrase($this->pluginid, 'msg_image_uploaded_js', 'Image uploaded!');
    InsertAdminPhrase($this->pluginid, 'create_thumbnail_from_image', 'Create thumbnail from image?');
    InsertAdminPhrase($this->pluginid, 'msg_no_events_deleted', 'No events were deleted!');
    InsertAdminPhrase($this->pluginid, 'msg_events_deleted', 'Events successfully deleted!');
    InsertAdminPhrase($this->pluginid, 'msg_event_deleted', 'The event was successfully deleted!');
    InsertAdminPhrase($this->pluginid, 'msg_event_updated', 'The event was successfully updated.');
    InsertAdminPhrase($this->pluginid, 'msg_event_added', 'The event was successfully added.');
    InsertAdminPhrase($this->pluginid, 'err_deletion_failed', 'ERROR: one or more events were not deleted!');

    InsertPhrase($this->pluginid, 'images_title', 'Images');
    InsertPhrase($this->pluginid, 'select_image_hint', 'Select a single image, then click Upload:');
    InsertPhrase($this->pluginid, 'optional_image', 'Optional Image');
    InsertPhrase($this->pluginid, 'optional_image_descr', 'Browse your computer for an image:<br />
      This image is optionally displayed on the single event details page.
      A custom-sized thumbnail can be generated from this image later on.');
    InsertPhrase($this->pluginid, 'optional_thumbnail', 'Optional Thumbnail');
    InsertPhrase($this->pluginid, 'optional_thumbnail_descr', 'Browse your computer for a thumbnail:<br />
      This thumbnail is optionally displayed in the events list.
      A custom-sized thumbnail can be generated from the <strong>Optional Image</strong>.');
    InsertPhrase($this->pluginid, 'events', 'Events');
    InsertPhrase($this->pluginid, 'update_event', 'Update Event');
    InsertPhrase($this->pluginid, 'option_display', 'Display Event:');
    InsertPhrase($this->pluginid, 'option_display_hint', 'Is this event ready to be displayed?');
    InsertPhrase($this->pluginid, 'option_comments', 'Allow Comments:');
    InsertPhrase($this->pluginid, 'option_comments_hint', 'Enable comments to be posted for this event?');
    InsertPhrase($this->pluginid, 'message_event_updated', 'Event was updated.');
    InsertPhrase($this->pluginid, 'upcoming_back_link', 'See all events');

    $DB->query("UPDATE {plugins} SET authorname = 'subdreamer_web', settings = %d WHERE pluginid = %d",
               $this->pluginsettings, $this->pluginid);

    $settings = GetPluginSettings($this->pluginid);
    if(isset($settings['row_color_1']))
    {
      $rowcol1 = !strlen($settings['row_color_1']) ? '#999999' : '#'.ltrim($settings['row_color_1'],'#');
      $rowcol2 = !strlen($settings['row_color_2']) ? '#e5e5e5' : '#'.ltrim($settings['row_color_2'],'#');
      $rowcol3 = !strlen($settings['row_color_3']) ? '#cccccc' : '#'.ltrim($settings['row_color_3'],'#');
      $CSS = new CSS();
      $CSS->InsertCSS('Event Manager 2 ('.$this->pluginid.')',
                      'plugins/'.$plugin_folder.'/css/styles.css',
                      true,
                      array('pXXXX_'      => 'p'.$this->pluginid.'_',
                            'row_color_1' => $rowcol1,
                            'row_color_2' => $rowcol2,
                            'row_color_3' => $rowcol3
                            ), $this->pluginid);
      unset($CSS);

      // Remove old phrases and settings
      DeletePluginSetting($this->pluginid,'options','row_color_1');
      DeletePluginSetting($this->pluginid,'Options','row_color_1');
      DeletePluginSetting($this->pluginid,'options','row_color_2');
      DeletePluginSetting($this->pluginid,'Options','row_color_2');
      DeletePluginSetting($this->pluginid,'options','row_color_3');
      DeletePluginSetting($this->pluginid,'Options','row_color_3');
      DeleteAdminPhrase($this->pluginid,'row_color_1_descr',2);
      DeleteAdminPhrase($this->pluginid,'row_color_1',2);
      DeleteAdminPhrase($this->pluginid,'row_color_2_descr',2);
      DeleteAdminPhrase($this->pluginid,'row_color_2',2);
      DeleteAdminPhrase($this->pluginid,'row_color_3_descr',2);
      DeleteAdminPhrase($this->pluginid,'row_color_3',2);
    }

    if(version_compare($currentversion, '2.2.0', '<=')) //2013-03-12, 2013-09-12
    {
      global $pluginname;
      // Create default templates (but no duplicates)
      $tpl_names = array('events_list.tpl' => 'Events List Display',
                         'upcoming_events.tpl' => 'Upcoming Events Display',
                         'single_event_display_1.tpl' => 'Single Event Display 1');
      require_once(SD_INCLUDE_PATH.'class_sd_smarty.php');
      $tpl_path = ROOT_PATH.'plugins/'.$plugin_folder.'/tmpl/';
      foreach($tpl_names as $tpl_name => $tpl_title)
      {
        if(false === SD_Smarty::TemplateExistsInDB($this->pluginid, $tpl_name))
        {
          echo '<b>Adding template '.$tpl_title.' for plugin '.$pluginname.' ('.$plugin_folder.')...</b>';
          if(false !== SD_Smarty::CreateTemplateFromFile($this->pluginid, $tpl_path, $tpl_name,
                                                         $tpl_title, $tpl_title))
            echo '<br /><b>Done.</b><br />';
          else
            echo '<br /><b>Failed to add template '.$tpl_title.'!</b><br />';
        }
      }

      InsertPluginSetting($this->pluginid, 'options', 'Display Images',
        'Display an images column for events (default: No)?<br />'.
        'Note: images must be resized to the desired width/height before upload!', 'yesno', '0', 58);
      InsertPluginSetting($this->pluginid, 'options', 'Max Thumbnail Width',
        'Enter the maximum <strong>width</strong> (in pixels) new thumbnail images are to be resized to (default: 100)?',
        'text', '100', 60);
      InsertPluginSetting($this->pluginid, 'options', 'Max Thumbnail Height',
        'Enter the maximum <strong>height</strong> (in pixels) new thumbnail images are to be resized to (default: 100)?',
        'text', '100', 62);
      InsertPluginSetting($this->pluginid, 'options', 'Default Events Sorting',
        'How would you like your events to be sorted in the main events list (default: Oldest First)?',
        "select:\r\ndatea|Oldest First\r\ndatez|Newest First\r\ntitlea|Event A-Z\r\ntitlez|Event Z-A".
        "\r\nlocationa|Location A-Z\r\nlocationz|Location Z-A\r\nvenuea|Venue A-Z\r\nvenuez|Venue Z-A",
        'oldest', 100);
      InsertPluginSetting($this->pluginid, 'options', 'Default Upcoming Sorting',
        'How would you like your <strong>upcoming</strong> events list to be sorted (default: Oldest First)?',
        "select:\r\ndatea|Oldest First\r\ndatez|Newest First\r\ntitlea|Event A-Z\r\ntitlez|Event Z-A".
        "\r\nlocationa|Location A-Z\r\nlocationz|Location Z-A\r\nvenuea|Venue A-Z\r\nvenuez|Venue Z-A",
        'oldest', 110);
      InsertPluginSetting($this->pluginid, 'options', 'Upcoming Thumbnails',
        'Display <strong>upcoming</strong> events list with thumbnails (default: No)?',
        'yesno', '0', 120);
    }

    /*
    // CODE TO ADD DUMMY EVENTS FOR TESTING:
    $countries = $DB->query("SELECT * FROM {countries}");
    $countries = $DB->fetch_array_all($countries,MYSQL_ASSOC);
    $country_count = count($countries)-1;

    $test = range(1,200);
    foreach($test as $value)
    {
      $date = rand( strtotime("Jan 01 2012"), strtotime("Dec 31 2014") );
      $country_idx = rand(0,$country_count);
      $DB->query("INSERT INTO $tbl (activated,allowcomments,title,description,date,venue,street,city,state,country)
                VALUES (1, 1, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                'title'.$value, 'description'.$value, $date,
                'venue'.$value, 'street'.$value, 'city'.$value,
                'state'.$value, $countries[$country_idx]['name']);
    }

    */

    if(empty($version) || (substr($version,0,2) == '1.'))
    {
      $version = '2.2.0';
    }
    //v2.2.0: set base to be clonable
    $DB->query("UPDATE {plugins} SET base_plugin = 'Event Manager' WHERE pluginid = ".$this->pluginid);

    UpdatePluginVersion($this->pluginid, $version);

  } //UpgradeAll

} //END OF CLASS
} //DO NOT REMOVE


// ############################## INSTALL PLUGIN ##############################

if(!$pluginid && ($installtype == 'install'))
{
  // At this point SD3 has to provide a new plugin id
  if(empty($pluginid))
  {
    $pluginid = CreatePluginID($pluginname);
  }

  // If for whatever reason the plugin id is invalid, return false NOW!
  if(empty($pluginid))
  {
    return false;
  }

  // Create events table
  $table = TABLE_PREFIX.'p'.$pluginid.'_events';

  //2012-08-15: if placed inside old "p18..." pluginfolder,
  // rename the old, existing table with new pluginid
  if(($plugin_folder == 'p18_event_manager') && ($pluginid!=18) &&
     $DB->table_exists(TABLE_PREFIX.'p18_events'))
  {
    $DB->query('RENAME TABLE {p18_events} TO '.$table);
  }
  else
  {
    $DB->query('DROP TABLE IF EXISTS '.$table);

    $DB->query("CREATE TABLE IF NOT EXISTS $table (
    eventid       INT(11)      NOT NULL AUTO_INCREMENT,
    activated     TINYINT(1)   NOT NULL DEFAULT 0,
    allowcomments TINYINT(1)   NOT NULL DEFAULT 0,
    title         VARCHAR(128) NOT NULL DEFAULT '',
    description   TEXT         NOT NULL,
    date          INT(10)      NOT NULL DEFAULT 0,
    venue         VARCHAR(128) NOT NULL DEFAULT '',
    street        VARCHAR(128) NOT NULL DEFAULT '',
    city          VARCHAR(128) NOT NULL DEFAULT '',
    state         VARCHAR(128) NOT NULL DEFAULT '',
    country       VARCHAR(64)  NOT NULL DEFAULT '',
    PRIMARY KEY eventid (eventid),
    KEY eventdate (date),
    KEY eventtitle (title),
    KEY eventvenue (venue),
    KEY eventcountry (country))");
  }

  $currentversion = '1.0.0'; //important!
  $EventUpgrader = new EventManagerUpgrader221($pluginid, $pluginsettings);
  $EventUpgrader->UpgradeAll($currentversion);
  unset($EventUpgrader);
  $currentversion = $version;

} // install


// ############################## UPGRADE PLUGIN ##############################

if(!empty($currentversion) && ($currentversion != $version) &&
   ($installtype == 'upgrade'))
{
  if(empty($pluginid)) return false;

  // 2013-02-08:
  // If placed inside old "p18..." pluginfolder,
  // rename the old, existing table with new table name
  if(($plugin_folder == 'p18_event_manager') && ($pluginid!=18) &&
     $DB->table_exists(TABLE_PREFIX.'p18_events'))
  {
    $table = TABLE_PREFIX.'p'.$pluginid.'_events';
    $tmp = @$DB->query('RENAME TABLE {p18_events} TO '.$table);
    unset($table);
  }

  // upgrade all plugin settings *once* for current v1.x installs:
  if((substr($version,0,1) > 1) && (substr($currentversion,0,1) == 1))
  {
    ConvertPluginSettings($pluginid);
  }

  $EMUpgrader = new EventManagerUpgrader221($pluginid, $pluginsettings);
  $EMUpgrader->UpgradeAll($version);
  $currentversion = $version;
  unset($EMUpgrader);

} // upgrade


// ############################ UNINSTALL PLUGIN ##############################

if($installtype == 'uninstall')
{
  if(empty($pluginid)) return false;
  $DB->query('DROP TABLE IF EXISTS {p'.$pluginid.'_events}');
}
