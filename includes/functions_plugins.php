<?php
if(!defined('IN_PRGM')) exit();

// ############################################################################
// GET PLUGIN ID
// ############################################################################

function GetPluginID($plugin_name = '')
{
  global $DB, $plugin_name_to_id_arr;

  if(!empty($plugin_name) && isset($plugin_name_to_id_arr[$plugin_name]))
  {
    return (int)$plugin_name_to_id_arr[$plugin_name];
  }
  $DB->result_type = MYSQL_ASSOC;
  if($result = $DB->query_first('SELECT pluginid FROM '.PRGM_TABLE_PREFIX."plugins WHERE name = '" .
                                $DB->escape_string($plugin_name) . "' LIMIT 1"))
  {
    return (int)$result['pluginid'];
  }

  return 0;

} //GetPluginID


// ############################################################################
// GET PLUGIN ID BY FOLDER
// ############################################################################

function GetPluginIDbyFolder($plugin_folder = '')
{
  global $DB, $plugin_folder_to_id_arr;

  if(empty($plugin_folder_to_id_arr) || defined('IN_ADMIN') || defined('INSTALLING_PRGM'))
  {
    $DB->result_type = MYSQL_ASSOC;
    if($result = $DB->query_first("SELECT pluginid FROM {plugins} WHERE settingspath LIKE '" .
                                  $DB->escape_string($plugin_folder) . "/%' LIMIT 1"))
    {
      return $result['pluginid'];
    }
  }
  else
  if(!empty($plugin_folder) && isset($plugin_folder_to_id_arr[$plugin_folder]))
  {
    return($plugin_folder_to_id_arr[$plugin_folder]);
  }

  return 0;

} //GetPluginIDbyFolder


// ############################################################################
// CREATE PLUGIN ID
// ############################################################################

function CreatePluginID($plugin_name)
{
  global $DB;

  if(empty($plugin_name) ||
     ($plugin_exists_arr = $DB->query_first("SELECT pluginid FROM {plugins} WHERE name = '" .
                                            $DB->escape_string($plugin_name) . "' LIMIT 1")))
  {
    return 0;
  }
  // SD 322: new plugin id's start from 5000 to avoid clashing with
  // id's of any old plugins!
  $newid = $DB->query_first('SELECT MAX(pluginid) FROM {plugins} WHERE pluginid >= 5000 LIMIT 1');
  $newid = empty($newid[0]) ? 5000 : ((int)$newid[0] + 1);

  $DB->query("INSERT INTO {plugins} (pluginid, name) VALUES (%d, '%s')",
             $newid, $DB->escape_string($plugin_name));

  return $DB->insert_id();

} //CreatePluginID


// ############################################################################
// INSERT ADMIN PHRASE
// ############################################################################
// SD313: new parameter "$replace" to replace an existing phrase if already exists

function InsertAdminPhrase($pluginid, $varname, $defaultphrase, $adminpageid = 0, $replace=false)
{
  global $DB;

  if( (empty($adminpageid) || ($adminpageid===true)) &&
      !empty($pluginid) && ((int)$pluginid > 0) )
  {
    $adminpageid = 2;
  }

  $customphrase = '';
  // only insert if not exists
  if($id = $DB->query_first('SELECT customphrase, COUNT(*) phrase_count'.
                            ' FROM '.PRGM_TABLE_PREFIX.'adminphrases'.
                            " WHERE adminpageid = %d AND pluginid = %d AND varname = '%s'".
                            ' GROUP BY customphrase LIMIT 1',
                            $adminpageid, $pluginid, $DB->escape_string($varname)))
  {
    if(!empty($id['phrase_count']))
    {
      $customphrase = $id['customphrase'];
      if(!empty($replace))
      {
        $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'adminphrases'.
                   ' WHERE adminpageid = %d AND pluginid = %d'.
                   " AND varname = '%s'",
                   $adminpageid, $pluginid, $DB->escape_string($varname));
      }
      else
      {
        return false;
      }
    }
  }

  $DB->query("INSERT INTO {adminphrases} (`adminphraseid`, `adminpageid`, `pluginid`, `varname`, `defaultphrase`, `customphrase`)".
             " VALUES (NULL, %d, %d, '".
              $DB->escape_string($varname)."', '".
              $DB->escape_string($defaultphrase)."', '".
              $DB->escape_string($customphrase)."')",
              $adminpageid, $pluginid);

  return true;

} //InsertAdminPhrase


// ############################################################################
// DELETE ADMIN PHRASE
// ############################################################################

function DeleteAdminPhrase($pluginid, $varname, $adminpageid = 0)
{
  global $DB;

  if(empty($adminpageid) && !empty($pluginid) && (int)$pluginid > 0)
  {
    $adminpageid = 2;
  }

  $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'adminphrases'.
             ' WHERE adminpageid = %d'.
             " AND pluginid = %d AND varname = '%s'",
             $adminpageid, (int)$pluginid, $DB->escape_string($varname));

  return true;

} //DeleteAdminPhrase


// ############################################################################
// INSERT PHRASE
// ############################################################################

function InsertPhrase($pluginid, $varname, $defaultphrase, $replace = false)
// SD332: IMPORTANT NOTE:
// If phrase already exists and $replace is true, then "customphrase"
// is being updated instead of "defaultphrase"! This is being used by
// Admin|Languages when switching translation.
{
  global $DB, $SDCache;

  if(empty($pluginid) || !is_numeric($pluginid) || ((int)$pluginid < 0) || !strlen($varname))
  {
    $error_msg = 'Phrase Error. Can not insert this phrase:<br />'.
                 "Plugin ID: $pluginid, Varname: $varname";

    DisplayMessage($error_msg);

    return false;
  }

  // only insert if not exists
  $tmp = $DB->query_first('SELECT 1 x FROM {phrases} WHERE pluginid = %d '.
                          "AND varname = '".$DB->escape_string($varname)."' LIMIT 1", $pluginid);
  if(empty($tmp['x']))
  {
    $DB->query("INSERT INTO {phrases} (`phraseid`, `pluginid`, `varname`, `defaultphrase`, `customphrase`) VALUES
               (NULL, %d, '".$DB->escape_string($varname)."', '".$DB->escape_string($defaultphrase)."', '')",
               $pluginid);
  }
  else
  if($replace) // SD322: new param $replace to replace existing defaultphrase
  {
    $DB->query("UPDATE {phrases} SET defaultphrase = '" . $DB->escape_string($defaultphrase) .
               "' WHERE varname = '".$DB->escape_string($varname).
               "' AND pluginid = ".$pluginid);
  }

  // SD313: remove cache file for plugin's settings
  if(isset($SDCache))
  {
    $SDCache->delete_cacheid('planguage_'.$pluginid);
  }

  return true;

} //InsertPhrase


// ############################################################################
// DELETE PHRASE
// ############################################################################

function DeletePhrase($pluginid, $varname)
{
  global $DB, $SDCache;

  if(empty($pluginid) || !is_numeric($pluginid) || ((int)$pluginid < 0) || !strlen($varname))
  {
    DisplayMessage('Phrase Error. Can not delete this phrase:<br />'.
                   "Plugin ID: $pluginid, Varname: $varname");

    return false;
  }

  $DB->query("DELETE FROM {phrases} WHERE pluginid = %d AND varname = '%s'",
              $pluginid, $DB->escape_string($varname));

  // SD313: remove cache file for plugin's settings
  if(isset($SDCache))
  {
    $SDCache->delete_cacheid('planguage_'.$pluginid);
  }

  return true;

} //DeletePhrase


/**
* Updates an Admin Phrase in the databsase
* Only updates the default phrase. Does not update Custom version of phrase
*/
function UpdateAdminPhrase($pluginid, $varname, $defaultphrase, $newvarname = '')
{
	global $DB, $SDCache;
	
	if(empty($pluginid) || !is_numeric($pluginid) || ((int)$pluginid < 0) || !strlen($varname) || !strlen($defaultphrase))
	{
		 DisplayMessage('Phrase Error. Can not update this phrase:<br />'.
                   "Plugin ID: $pluginid, Varname: $varname");
	
		return false;
	}
	
	$DB->query("UPDATE {adminphrases} SET defaultphrase='$defaultphrase' WHERE varname='$varname' AND pluginid=$pluginid");
	
	if(strlen($newvarname))
	{
		$DB->query("UPDATE {adminphrases} SET varname='$newvarname' WHERE pluginid=$pluginid AND varname='$varname'");
	}
	
	return true;
}


// ############################################################################
// ConvertPluginSettings
// ############################################################################

function ConvertPluginSettings($pluginid)
//SD322: convert "old" plugin settings to translatable settings, e.g.
//       "My Old Setting" => phrases "my_old_setting" and "my_old_setting_descr"
{
  global $DB, $SDCache, $core_pluginids_arr;

  if(empty($pluginid) || !is_numeric($pluginid) || ((int)$pluginid < 1))
  {
    return false;
  }
  $pluginid = (int)$pluginid;

  $get_settings = $DB->query('SELECT settingid, groupname, title, description FROM '.PRGM_TABLE_PREFIX.'pluginsettings'.
                             ' WHERE pluginid = %d ORDER BY settingid ASC', $pluginid);
  while($setting_arr = $DB->fetch_array($get_settings,null,MYSQL_ASSOC))
  {
    // Convert title and description to lowercase and replace all invalid characters
    // (incl. blanks) by underscore "_" character; then update the pluginsettings-
    // row with each new value
    $title = $setting_arr['title'];
    $description = $setting_arr['description'];

    $ident = (strlen($title) ? $title : $description);
    $ident = strtolower(preg_replace('/[^0-9a-zA-Z_]/', '_', $ident));

    if(strlen($ident))
    {
      if(strlen($title))
      {
        // Add a new admin phrase with the new title
        InsertAdminPhrase($pluginid, $ident, $title, 2);
        if($ident != $setting_arr['title'])
        {
          $DB->query("UPDATE {pluginsettings} SET title = '%s' WHERE settingid = %d",
                     $ident, $setting_arr['settingid']);
        }
        // If set, add a new admin phrase with the new description
        if(strlen($description))
        {
          InsertAdminPhrase($pluginid, $ident.'_descr', $description, 2);
          if($ident.'_descr' != $setting_arr['description'])
          {
            $DB->query('UPDATE '.PRGM_TABLE_PREFIX."pluginsettings SET description = '%s' WHERE settingid = %d",
                       $ident.'_descr', $setting_arr['settingid']);
          }
        }
      }
      else
      if(strlen($description))
      {
        InsertAdminPhrase($pluginid, $ident, $descr, 2);
        $DB->query("UPDATE {pluginsettings} SET description = '%s' WHERE settingid = %d",
                   $ident, $setting_arr['settingid']);
      }
    }
  } //while

  // remove cache file for plugin's settings
  if(isset($SDCache))
  {
    $SDCache->delete_cacheid('psettings_'.$pluginid);
  }

  return true;

} //ConvertPluginSettings


// ############################################################################
// INSERT PLUGIN SETTING (incl. automatic admin phrases)
// ############################################################################
/* SD313
 This function adds a setting entry for a plugin if it does not exist already.
 In addition it will automatically create admin phrases from the title and the
 description within Subdreamer 3.
 IMPORTANT: the "$title" parameter is REQUIRED! It MUST only contain LATIN
 characters (a-z, A-Z), numerals (0-9) or the underscore character since
 *ALL* others will be replaced by underscore!

 For automatic admin phrase creation it will convert the "$title" to a lowercased
 shortcut value by replacing unused characters, like blanks, with underscores.
 Example: a setting with title "One Example Setting!" will be transformed to
 "one_example_setting" (as that is going to be used as key for admin phrases).
 The description will be added to the admin phrases with the new title's name
 with suffix "_descr".

 FULL EXAMPLE:
 InsertPluginSetting($pluginid, 'Options', 'Funny Option', 'It will do stuff', 'text', '', 1);

 The 2 new admin phrases created are:
   "funny_option" : containing the "$title" value "Funny Option"
   "funny_option_desc" : containing the "$descr" value "It will do stuff"
 *** The NEW setting title MUST be used in the plugin code itself afterwards! ***
*/
function InsertPluginSetting($pluginid, $groupname, $title, $descr, $input, $value, $disp, $replace=false)
{
  global $DB, $SDCache;

  // Allow title to be empty with descr being filled (for Articles plugin)
  if(empty($pluginid) || !is_numeric($pluginid) || ((int)$pluginid < 1) || (empty($title) && empty($descr)))
  {
    DisplayMessage('Setting Error. Can not insert this setting:<br />'.
                   "Plugin ID: $pluginid, Varname: $title");

    return false;
  }
  $pluginid = (int)$pluginid;

  // Convert "$title" to lowercase and replace all invalid characters
  // (incl. blanks) by underscore "_" character
  if(strlen($title))
  {
    $ident = strtolower(preg_replace('/[^0-9a-zA-Z_]/', '_', $title));
  }
  else
  {
    $ident = strtolower(preg_replace('/[^0-9a-zA-Z_]/', '_', $descr));
  }
  $oldValue = '';
  if($result = $DB->query_first("SELECT value FROM {pluginsettings}
               WHERE pluginid = %d AND groupname = '%s' AND title = '%s'",
               $pluginid, $DB->escape_string($groupname), $ident))
  {
    $oldValue = $result['value'];
    if(!$replace)
    {
      // At least update the displayorder of the setting
      if(!empty($disp) && ($disp>0))
      {
        $DB->query('UPDATE {pluginsettings} SET displayorder = %d'.
                   " WHERE pluginid = %d AND groupname = '%s' AND title = '%s'",
                   $disp, $pluginid, $DB->escape_string($groupname), $ident);
      }
      return false;
    }
    else
    {
      // Remove existing setting now
      DeletePluginSetting($pluginid, $groupname, $ident);
      $value = $oldValue; // keep previous setting
    }
  }

  //#################################################################
  //SD322: new input type "select:" processing for plugin phrases
  // "select-multi:" is for select tags with "multiple" attribute
  //#################################################################
  if(is_string($input) && ((substr($input,0,7)=='select:') || (substr($input,0,13)=='select-multi:')))
  {
    // "$input" is a string, so convert it to an array first
    $isMulti = substr($input,0,13)=='select-multi:';
    $cutOff = $isMulti ? 13 : 7;
    $input = preg_replace('#(\\\r\\\n)#', "\r", trim($input));
    $select_arr = preg_split('/\r/', substr($input, $cutOff), -1, PREG_SPLIT_NO_EMPTY);
    foreach($select_arr AS $sel_entry)
    {
      // Split entry into a "VALUE" and a "PHRASE" part, used in HTML like:
      // <option value="VALUE">PHRASE</option>
      list($sel_value, $sel_phrase) = explode('|', $sel_entry);
      // Finally create an admin phrase, containing a prefix, the varname
      // and at the end the value itself.
      // Function "InsertAdminPhrase" will escape the variables itself.
      $tmp = isset($title) ? strtolower(preg_replace('/[^0-9a-zA-Z_]/', '_', $title)) : '';
      InsertPhrase($pluginid, 'select_'.$tmp.'_'.strtolower($sel_value), $sel_phrase, $replace);
    }
  }

  // Now insert the setting; SQL enlists all columns to avoid errors
  // upon changes to pluginsettings-table (unless one of these columns
  // were actually renamed/removed)
  $DB->query("INSERT INTO {pluginsettings}
    (settingid, pluginid, groupname, title, description, input, value, displayorder)
    VALUES (NULL, %d, '%s', '%s', '%s', '%s', '%s', %d)",
    $pluginid, $DB->escape_string($groupname), $ident, $ident.'_descr',
    $input, $DB->escape_string($value), $disp);

  // Now add the shortcut admin phrases with the original text values
  if(strlen($title))
  {
    InsertAdminPhrase($pluginid, $ident, $title, 2, $replace);
    if(strlen($descr))
    {
      InsertAdminPhrase($pluginid, $ident.'_descr', $descr, 2, $replace);
    }
  }
  else
  {
    InsertAdminPhrase($pluginid, $ident, $descr, 2, $replace);
  }

  // SD313: remove cache file for plugin's settings
  if(isset($SDCache))
  {
    $SDCache->delete_cacheid('psettings_'.$pluginid);
  }

  return true;

} //InsertPluginSetting


// ############################################################################
// InsertMainSetting
// ############################################################################

// Similar to InsertPluginSetting, but for mainsettings
// It will automatically create admin phrases for $title and $descr!
// Should ONLY be called from within plugin installations
function InsertMainSetting($varname, $groupname, $title, $descr, $input, $value, $disp=0, $replace=false)
{
  global $DB, $SDCache;

  if(empty($varname))
  {
    DisplayMessage('Setting Error. Can not insert main setting without varname!<br />');

    return false;
  }

  // Convert "$title" to lowercase and replace all invalid characters
  // (incl. blanks) by underscore "_" character
  $ident = strtolower(preg_replace('/[^0-9a-zA-Z_]/', '_', $title));

  //#################################################################
  //SD322: new input type "select:" processing for phrases
  //#################################################################
  /*
  "$input" is a string containing 2 or more lines, with the first line ONLY
   containing "select:" and all following lines like "value|phrase".
   NOTES:
   - the "|" (pipe) character MUST NOT be used in either value or phrase!
   - all values (in front of the pipe) MUST BE lower-cased!
   Example 1 (note: actual contents must start at 1st column in each line!):
select:
0|Disable Captcha
1|reCaptcha (default)
2|VVC Image
  (4 lines in total, text must begin at line-start, no leading blanks!)

   Example 2 (note: actual contents must start at 1st column in each line!):

  */
  if(is_string($input) && (substr($input,0,7)=='select:'))
  {
    // "$input" is a string, so convert it to an array first
    $select_arr = preg_split('/\R/', substr($input,7), -1, PREG_SPLIT_NO_EMPTY);
    foreach($select_arr AS $sel_entry)
    {
      // Split entry into a "VALUE" and a "PHRASE" part, used in HTML like:
      // <option value="VALUE">PHRASE</option>
      $sel_entry = trim($sel_entry);
      if(strpos($sel_entry, '|') === false)
      {
        $sel_value = $sel_phrase = $sel_entry;
      }
      else
      {
        list($sel_value, $sel_phrase) = explode('|', trim($sel_entry));
        // Finally create an admin phrase, containing a prefix, the varname
        // and at the end the value itself.
        // Function "InsertAdminPhrase" will escape the variables itself.
        $tmp = strtolower(preg_replace('/[^0-9a-zA-Z_]/', '_', $varname));
        InsertAdminPhrase(0, 'select_'.$tmp.'_'.strtolower($sel_value), $sel_phrase, 0, $replace);
      }
    }
  }

  $groupname_ident = '';
  if(isset($groupname) && strlen($groupname))
  {
    $groupname_ident = strtolower(preg_replace('/[^0-9a-zA-Z_]/', '_', $groupname));
  }

  // Check, if the setting already exists and if it should be replaced then:
  if($result = ($DB->query_first("SELECT 1 FROM {mainsettings} WHERE varname = '%s' AND groupname = '%s'",
                                 $DB->escape_string($varname), $DB->escape_string($groupname_ident))))
  {
    if(!$replace)
    {
      // At least update the display order value:
      $DB->query("UPDATE {mainsettings} SET displayorder = %d WHERE varname = '%s' AND groupname = '%s'",
                 (int)$disp, $DB->escape_string($varname), $DB->escape_string($groupname_ident));
      $result = false;
    }
    else
    {
      // Remove existing setting now
      $DB->query("DELETE FROM {mainsettings} WHERE varname = '%s' AND groupname = '%s'",
                 $DB->escape_string($varname), $DB->escape_string($groupname_ident));
      $result = true;
    }
  }
  else
  {
    $result = true;
  }

  // Now insert the setting; SQL enlists all columns to avoid errors
  // upon changes to pluginsettings-table (unless one of these columns
  // were actually renamed/removed)
  if($result)
  {
    $DB->query("INSERT INTO {mainsettings}
      (settingid, varname, groupname, title, description, input, value, displayorder) VALUES
      (NULL, '%s', '%s', '%s', '%s', '%s', '%s', %d)",
      $DB->escape_string($varname), $DB->escape_string($groupname_ident),
      $ident, (strlen($ident)?$ident.'_descr':''), $input, $DB->escape_string($value), (int)$disp);
  }

  // Now add the shortcut admin phrases with the original text values
  if(strlen($title))
  {
    InsertAdminPhrase(0, $ident, $title, 0, $replace);
    InsertAdminPhrase(0, $ident.'_descr', $descr, 0, $replace);
  }

  // SD313: remove cache file for plugin's settings
  if(isset($SDCache))
  {
    $SDCache->delete_cacheid(CACHE_ALL_MAINSETTINGS);
  }

  return true;

} //InsertMainSetting


// ############################################################################
// DELETE PLUGIN SETTING
// ############################################################################
// SD313: delete a plugin setting identified by plugin id, groupname and title

function DeletePluginSetting($pluginid, $groupname, $title)
{
  global $DB, $SDCache;

  if(empty($pluginid) || !is_numeric($pluginid) || ((int)$pluginid < 1) || empty($title))
  {
    DisplayMessage('Setting Error. Can not delete this setting:<br />'.
                   "Plugin ID: $pluginid, Varname: $title");

    return false;
  }

  $pluginid = (int)$pluginid;
  $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'pluginsettings WHERE pluginid = %d'.
             " AND groupname = '%s' AND title = '%s'",
             $pluginid, $DB->escape_string($groupname), $DB->escape_string($title));

  // SD313: remove cache file for plugin's settings
  if(isset($SDCache))
  {
    $SDCache->delete_cacheid('psettings_'.$pluginid);
  }

  return true;

} //DeletePluginSetting


// ############################################################################
// GET PLUGIN SETTINGS
// ############################################################################

function GetPluginSettings($pluginid, $groupname = '', $title = '',
                           $noCache = false, $only_ids = false)
{
  //SD370: added $only_ids to return setting details, not only title/value
  global $DB, $SDCache, $database;

  if(empty($pluginid) || !is_numeric($pluginid) || ((int)$pluginid < 1))
  {
    return array();
  }
  $only_ids = !empty($only_ids); //SD370
  $pluginid = (int)$pluginid;
  $CacheEnabled = isset($SDCache) && ($SDCache instanceof SDCache) && $SDCache->IsActive();

  // SD313: Check for cached results (not for admin panel!)
  if(!defined('IN_ADMIN') && empty($noCache) && $CacheEnabled)
  {
    if(($pluginsettings = $SDCache->read_var('psettings_'.$pluginid, 'pluginsettings')) !== false)
    {
      return $pluginsettings;
    }
  }

  $settings_arr = array();

  // start building sql statement
  $prevDB = $DB->database;
  if($DB->database != $database['name']) $DB->select_db($database['name']); //SD370
  $sql = "SELECT settingid, IFNULL(title,'') title, IFNULL(description,'') description,".
         " IFNULL(input,'') input, IFNULL(value,'') value, IFNULL(groupname,'') groupname".
         ' FROM {pluginsettings}'.
         ' WHERE pluginid = '.(int)$pluginid.
         //SD370: re-added groupname condition for ADMIN area!
         (!defined('IN_ADMIN') || empty($groupname)?'':" AND groupname = '".$DB->escape_string($groupname)."'").
         ' ORDER BY displayorder ASC';

  // get plugin settings
  $result = array();
  if($get_settings = $DB->query($sql))
  {
    while($setting_arr = $DB->fetch_array($get_settings,null,MYSQL_ASSOC))
    {
      //SD370: return array with details if $only_ids = true
      if($only_ids)
      {
        $result[$setting_arr['settingid']] = array(
          'title' => $setting_arr['title'],
          'value' => $setting_arr['value'],
          'input' => $setting_arr['input']
        );
        continue;
      }
      // Some settings have titles, some have descriptions
      $title = strlen($setting_arr['title']) ? $setting_arr['title'] : $setting_arr['description'];
      $settings_arr[$title] = $setting_arr['value'];
      // SD322: Only return those items which obey the conditions
      if((empty($groupname) || ($setting_arr['groupname'] == $groupname)) &&
         (empty($title) || ($setting_arr['title'] == $title)))
      {
        $result[$title] = $setting_arr['value'];
      }
    }
    if(!empty($settings_arr))
    {
      $DB->free_result($get_settings);
    }
  }
  if($DB->database != $prevDB) $DB->select_db($prevDB); //SD370

  //SD313: Rewrite cache file if enabled (always in admin)
  if($CacheEnabled && (defined('IN_ADMIN') || empty($noCache)))
  {
    $SDCache->write_var('psettings_'.$pluginid, 'pluginsettings', $settings_arr);
  }

  return $result;

} //GetPluginSettings


function DefaultPluginInUsergroups($uniqueid, $pluginsettings=17)
{
  global $DB, $pluginbitfield;

  if(empty($uniqueid) || !is_numeric($uniqueid) || ($uniqueid < 12)) return;

    // install default usergroup settings
  if($usergroups = $DB->query('SELECT name, usergroupid, pluginviewids, pluginsubmitids,
                               plugindownloadids, plugincommentids, pluginadminids
                               FROM {usergroups}
                               ORDER BY usergroupid'))
  while($usergroup = $DB->fetch_array($usergroups,null,MYSQL_ASSOC))
  {
    if($usergroup['usergroupid'] == 1)
    {
      // ADMINISTRATOR USERS
      $pluginviewids     = (!$pluginsettings & $pluginbitfield['canview'])     ? $usergroup['pluginviewids']     : (strlen($usergroup['pluginviewids'])     ? $usergroup['pluginviewids']      . ',' . $uniqueid : $uniqueid);
      $pluginsubmitids   = (!$pluginsettings & $pluginbitfield['cansubmit'])   ? $usergroup['pluginsubmitids']   : (strlen($usergroup['pluginsubmitids'])   ? $usergroup['pluginsubmitids']    . ',' . $uniqueid : $uniqueid);
      $plugindownloadids = (!$pluginsettings & $pluginbitfield['candownload']) ? $usergroup['plugindownloadids'] : (strlen($usergroup['plugindownloadids']) ? $usergroup['plugindownloadids']  . ',' . $uniqueid : $uniqueid);
      $plugincommentids  = (!$pluginsettings & $pluginbitfield['cancomment'])  ? $usergroup['plugincommentids']  : (strlen($usergroup['plugincommentids'])  ? $usergroup['plugincommentids']   . ',' . $uniqueid : $uniqueid);
      $pluginadminids    = (!$pluginsettings & $pluginbitfield['canadmin'])    ? $usergroup['pluginadminids']    : (strlen($usergroup['pluginadminids'])    ? $usergroup['pluginadminids']     . ',' . $uniqueid : $uniqueid);
    }
    else
    if(($usergroup['usergroupid'] == 2) || ($usergroup['usergroupid'] == 3))
    {
      // MODERATORS AND REGISTERED USERS
      $pluginviewids     = (!$pluginsettings & $pluginbitfield['canview'])     ? $usergroup['pluginviewids']     : (strlen($usergroup['pluginviewids'])     ? $usergroup['pluginviewids']      . ',' . $uniqueid : $uniqueid);
      $pluginsubmitids   = (!$pluginsettings & $pluginbitfield['cansubmit'])   ? $usergroup['pluginsubmitids']   : (strlen($usergroup['pluginsubmitids'])   ? $usergroup['pluginsubmitids']    . ',' . $uniqueid : $uniqueid);
      $plugindownloadids = (!$pluginsettings & $pluginbitfield['candownload']) ? $usergroup['plugindownloadids'] : (strlen($usergroup['plugindownloadids']) ? $usergroup['plugindownloadids']  . ',' . $uniqueid : $uniqueid);
      $plugincommentids  = (!$pluginsettings & $pluginbitfield['cancomment'])  ? $usergroup['plugincommentids']  : (strlen($usergroup['plugincommentids'])  ? $usergroup['plugincommentids']   . ',' . $uniqueid : $uniqueid);
      $pluginadminids    = $usergroup['pluginadminids'];
    }
    else
    if($usergroup['name'] == 'Banned') //SD370: no'tin for da ban'd!
    {
      // BANNED
      $pluginviewids     = '1';
      $pluginsubmitids   = $usergroup['pluginsubmitids'];
      $plugindownloadids = $usergroup['plugindownloadids'];
      $plugincommentids  = $usergroup['plugincommentids'];
      $pluginadminids    = $usergroup['pluginadminids'];
    }
    else
    {
      // GUESTS, and other created usergroup users
      $pluginviewids     = (!$pluginsettings & $pluginbitfield['canview'])     ? $usergroup['pluginviewids']     : (strlen($usergroup['pluginviewids'])     ? $usergroup['pluginviewids']      . ',' . $uniqueid : $uniqueid);
      $pluginsubmitids   = $usergroup['pluginsubmitids'];
      $plugindownloadids = $usergroup['plugindownloadids'];
      $plugincommentids  = $usergroup['plugincommentids'];
      $pluginadminids    = $usergroup['pluginadminids'];
    }

    // update usergroup row
    $DB->query("UPDATE {usergroups}
                SET pluginviewids     = '$pluginviewids',
                    pluginsubmitids   = '$pluginsubmitids',
                    plugindownloadids = '$plugindownloadids',
                    plugincommentids  = '$plugincommentids',
                    pluginadminids    = '$pluginadminids'
                WHERE usergroupid     = %d", $usergroup['usergroupid']);
  } //while

} //DefaultPluginInUsergroups


function FillPluginsTitlesTable()
{
  //SD362, added 2013-09-10
  // Called in SD upgrade or after a plugin/clone was installed.
  // Allows to fetch titles for plugin items, used by e.g. Latest Comments
  global $DB, $SDCache;

  $added = 0;
  $tbl = PRGM_TABLE_PREFIX.'plugins_titles';
  $tmp = $DB->query('SELECT pluginid, name, base_plugin FROM '.PRGM_TABLE_PREFIX.'plugins'.
                    ' WHERE pluginid > 1');
  while(list($pid,$pname,$pbase) = $DB->fetch_array($tmp,null,MYSQL_BOTH))
  {
    $doAdd = false;
    $entry = array();
    if(($pid==2) || (!empty($pbase) && ($pbase=='Articles')))
    {
      $doAdd = true;
      $entry = array('p'.$pid.'_news', 'articleid', 'title');
    }
    else
    if(($pid==13) || (!empty($pbase) && ($pbase=='Download Manager')))
    {
      $doAdd = true;
      $entry = array('p'.$pid.'_files', 'fileid', 'title');
    }
    else
    if(($pid==17) || (!empty($pbase) && (($pbase=='Image Gallery') || ($pbase=='Media Gallery'))))
    {
      $doAdd = true;
      $entry = array('p'.$pid.'_images', 'imageid', 'title');
    }
    else
    if((substr($pname,0,13)=='Event Manager') || (!empty($pbase) && ($pbase=='Event Manager')))
    {
      $doAdd = true;
      $entry = array('p'.$pid.'_events', 'eventid', 'title');
    }
    else
    if($pname=='Forum')
    {
      $doAdd = true;
      $entry = array('p_forum_topics', 'topic_id', 'title');
    }
    else
      continue;

    if($doAdd)
    {
      if(!$DB->query_first('SELECT 1 FROM '.$tbl.' WHERE pluginid = %d'.
                           " AND id_column = '%s'",
                           $pid, $DB->escape_string($entry[1])))
      {
        $added++;
        $DB->query('INSERT INTO '.$tbl.'(pluginid,tablename,id_column,title_column,activated)'.
        "VALUES (%d,'%s','%s','%s',1)",
        $pid, $DB->escape_string($entry[0]), $DB->escape_string($entry[1]), $DB->escape_string($entry[2]));
      }
    }
  }

  // delete existing cache file to have it refreshed:
  if(!empty($added) && !empty($SDCache))
  {
    $SDCache->delete_cacheid(CACHE_PLUGIN_TITLES);
  }

} //FillPluginsTitlesTable

