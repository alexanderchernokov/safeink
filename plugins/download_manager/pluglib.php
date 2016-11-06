<?php
// +---------------------------------------------+
// | Download Manager plugin for Subdreamer      |
// +---------------------------------------------+
// | v2.2.0, September 2013                      |
// | Maintainer: 2007-2013 tobias                |
// | This version requires Subdreamer 3.6+!      |
// +---------------------------------------------+

/*
  This file contains useful functions for subdreamer developers when
  developing plugin upgrades requiring operations like:

  a) changes to table structures (add/remove fields)
  b) add/replace/remove plugin phrases
  c) add/replace/remove plugin settings

  Additionally, "adding" operations check for pre-existing rows with
  same keys to avoid duplicate entries, optionally existing rows can
  be deleted.

  The same especially applies to adding or removing table columns
  to avoid SQL errors like "column already exists" (or not), so that
  a script can run without (technical) errors (should an error occur,
  it might be due to syntax or permission conflicts).

  For instructions carefully read the inline comments on top of functions!
*/

if(!defined('IN_PRGM') || defined('DLM_PLUGLIB_LOADED')) return;

if(!defined('DLM_PLUGLIB_LOADED'))
{
define('DLM_PLUGLIB_LOADED', true);

// ############################################################################
// ########## ADD A SPECIFIED INDEX ON A COLUMN AND TABLE #####################
// ############################################################################
// This function returns TRUE if the specified column "$columnname" had
// successfully created a key with optional name $indexname on table
//  "$tablename", otherwise returns FALSE.
// $columnname can actually contain a compound key like "firstidx, secidx" etc.
// If $indexname is left empty, MySQL will decide, usually it's the column name.
// "$tablename" HAS to be the full name, i.e. including the SD prefix if it
// is a SD-table!
// As of now, this function does not allow to specify the index type or
// work to create a unique key!
function sd_addtableindex($tablename,$columnname,$indexname='')
{
  global $DB;

  if(!isset($tablename{0}) || !isset($columnname{0}))
  {
    return false;
  }

  if(sd_columnindexexists($tablename,$columnname,$indexname))
  {
    return true;
  }
  $result = $DB->query("ALTER TABLE $tablename ADD ".
    (strlen(trim($indexname)) ? "`$indexname`" : '') .
    " INDEX ( `$columnname` )");

  return $result;

} //sd_addtableindex


// ############################################################################
// ####### DETECT IF A TABLE HAS ALREADY A KEY ON A (SINGLE) COLUMN ###########
// ############################################################################
// This function returns TRUE if the specified column "$columnname" is either
// part of an index or a same-name index exists for table "$tablename",
// otherwise it returns FALSE.
// "$tablename" HAS to be the full name, i.e. including the SD prefix if it
// is a SD-table!
function sd_columnindexexists($tablename,$columnname,$indexname='')
// $keyname is optional and could be used in case of compound keys
{
  global $DB;

  if(!isset($tablename{0}) || !isset($columnname{0}))
  {
    return false;
  }

  /* Another approach to go by INFORMATION_SCHEMA:
  SELECT * FROM `KEY_COLUMN_USAGE` ORDER BY `KEY_COLUMN_USAGE`.`TABLE_NAME` ASC
  WHERE TABLE_NAME = xxx AND COLUMN_NAME = yyy
  */
  if($getindex = $DB->query('SHOW INDEX FROM '.$tablename))
  {
    while($result = $DB->fetch_array($getindex))
    {
      if($result['Column_name'] == $columnname)
      {
        if((strlen(trim($indexname)) == 0) || ($result['Key_name'] == $indexname))
        {
          return true;
        }
      }
    }
    return isset($result) && isset($result['Column_name']) && ($result['Column_name'] == $columnname);

  }
  else
  {
    return false;
  }

} //sd_columnindexexists


// ############### DETERMINE IF A COLUMN IS ALREADY INDEXED ###################
// This function returns TRUE if the specified column "$columnname" already
// exists as index for the table "$tablename", otherwise returns FALSE.
// "$tablename" HAS to be the full name, i.e. including the SD prefix if it
// is a SD-table!
function sd_tablecolumnindexexists($tablename,$columnname)
{
  global $DB;

  if(!isset($tablename{0}) || !isset($columnname{0}))
  {
    return false;
  }

  $result = $DB->query_first("SHOW INDEXES IN `$tablename` WHERE `column_name` = '$columnname'");
  return isset($result) && ($result !== False) && ($result['Column_name'] == $columnname);

} //sd_tablecolumnindexexists


// ########## DETECT IF A COLUMN IS ALREADY PRESENT IN A TABLE ################
// This function returns TRUE if the specified column "$columnname" already
// exists in table "$tablename", otherwise returns FALSE.
// "$tablename" HAS to be the full name, i.e. including the SD prefix if it
// is a SD-table!
function sd_tablecolumnexists($tablename,$columnname)
{
  global $DB;

  if(!isset($tablename{0}) || !isset($columnname{0}))
  {
    return false;
  }

  $result = $DB->query_first("SHOW COLUMNS FROM `$tablename` WHERE `field` = '$columnname'");
  return isset($result) && ($result !== False) && is_array($result) && (count($result)>0);

} //sd_tablecolumnexists


// ####### ADD COLUMN TO AN EXISTING TABLE IF FIELD DOES NOT EXIST YET ########
// This function will ONLY execute "$statement" - which HAS to contain an
// "ALTER TABLE ADD..." command - if the given column "$fieldname" does
// NOT yet exist in table "$tablename" and returns TRUE is succesfull
// -OR- FALSE if the column did exist ("$statement" wasn't executed!)
/*
// --- EXAMPLE CODE ---
$upg_tbl = "{pXXX_myplugintable}";
$upg_cmd = "ALTER TABLE $upg_tbl ADD ";
sd_addtablecolumn($upg_tbl, 'newcolumn', $upg_cmd .' newcolumn TINYINT NOT NULL DEFAULT 0');
*/
function sd_addtablecolumn($tablename,$columnname,$statement,$dolog=false)
{
  global $DB;

  if(!isset($tablename) || !isset($columnname) || sd_tablecolumnexists($tablename,$columnname))
  {
    if($dolog)
    {
      echo "- Column '$columnname' already exists in '$tablename', skipped.<br />";
    }
    return false;
  }
  else
  {
    $result = ($DB->query($statement));
    if($dolog)
    {
      echo "- <strong>Column '$columnname' added to '$tablename', OK.</strong><br />";
    }
    return true;
  }

} // sd_addtablecolumn


// ################ REMOVE AN EXISTING COLUMN FROM A TABLE ####################
// "$tablename" HAS to be the FULL tablename (incl. SD's prefix if it is
// a SD-table of course)!
function sd_removetablecolumn($tablename,$columnname,$dolog=false)
{
  global $DB;

  if(!isset($tablename) || !isset($columnname) ||
    !sd_tablecolumnexists($tablename,$columnname))
  {
    if($dolog)
    {
      echo "- Column '$columnname' did NOT exist in table '$tablename', skipped.<br />";
    }
    return false;
  }
  else
  {
    if($result = ($DB->query("ALTER TABLE ".$tablename." DROP `".$columnname."`")))
    {
      if($dolog)
      {
        echo "- <strong>Column '$columnname' REMOVED from table \"$tablename\", OK.</strong><br />";
      }
      return true;
    }
    else
    {
      // At this point the script has already been aborted anyway...
      return false;
    }
  }

} //sd_removetablecolumn


// ################### DELETE/INSERT PLUGINSETTING(S) #########################
// Remove a "pluginsetting" specified by "groupname" and "title".
// IMPORTANT: an EMPTY "title" will delete ALL settings of the same group!
function sd_removesetting($pluginid,$groupname,$title,$dolog=false)
{
  global $DB;

  if(empty($pluginid) || ((int)$pluginid < 1) || !isset($groupname{0}))
  {
    return false;
  }

  // IF $title is empty, then delete all entries for $groupname
  $AddSQL = (isset($title{0}) ? " AND title = '".$DB->escape_string($title)."'" : '');
  if($DB->query("DELETE FROM {pluginsettings}
     WHERE pluginid = %d AND groupname = '%s' " . $AddSQL,
     $pluginid, $DB->escape_string($groupname)))
  {
    if($dolog)
    {
      echo "- <strong>Removed setting '$groupname'/'".strip_tags($title)."' for plugin $pluginid.</strong><br />";
    }
  }

  return true;

} //sd_removesetting


// ########################## ADD A PLUGINSETTING #############################
// Adds a new "pluginsetting", optionally replacing an existing one.
// If the same setting already exists, it is kept by default unless
// "$replace" is passed as TRUE to replace it.
// NOTE: For simplicity reasons not ALL "pluginsettings" columns are used
//       as parameters yet!
// IMPORTANT: all values have to be escaped already!!
function sd_checksetting($pluginid,$groupname,$title,$descr,$input,$value,$disp,$replace=false,$dolog=false)
{
  global $DB;

  if(empty($pluginid) || ((int)$pluginid < 1) || !isset($title{0}))
  {
    return false;
  }

  if(!isset($title{0}) && isset($descr{0}))
  {
    $result = ($DB->query_first("SELECT 1 FROM {pluginsettings}
               WHERE pluginid = %d AND groupname = '%s' AND description = '%s'",
               $pluginid, $DB->escape_string($groupname), $DB->escape_string($descr)));
  }
  else
  {
    $result = ($DB->query_first("SELECT 1 FROM {pluginsettings}
               WHERE pluginid = %d AND groupname = '%s' AND title = '%s'",
               $pluginid, $DB->escape_string($groupname), $DB->escape_string($title)));
  }
  if($result)
  {
    if(!$replace)
    {
      if($dolog)
      {
        echo "- Setting '$groupname'/'".strip_tags($title)."' for plugin $pluginid already exists, skipped.<br />";
      }
      return false;
    }
    // Remove existing setting
    else
    {
      sd_removesetting($pluginid, $groupname, $title, $dolog);
    }
  }

  // Now insert the setting; SQL enlists all columns to also cover many
  // potential changes to pluginsettings-table (unless one of those
  // were actually renamed/removed)
  $DB->query("INSERT INTO {pluginsettings}
    (settingid, pluginid, groupname, title, description, input, value, displayorder)
    VALUES
    (NULL, %d, '%s', '%s', '%s', '%s', '%s', %d)",
    $pluginid, $DB->escape_string($groupname), $DB->escape_string($title),
    $DB->escape_string($descr), $input, $DB->escape_string($value), (int)$disp);

  if($dolog)
  {
    echo "- <strong>Setting '$groupname'/'".strip_tags($title)."' for plugin $pluginid added.</strong><br />";
  }

  return true;

} //sd_checksetting

/*
 This function will automatically create admin phrases for title and descr within SD3.
 IMPORTANT: THIS WORKS ONLY FOR LATIN CHARACTERS, i.e. the "$title" MUST ONLY contain
 latin characters or numericals ("a-zA-Z0-9")! The "$title" parameter is REQUIRED!

 It will automatically convert "$title" to a shortcut value by replacing blanks with
 underscores, e.g. a setting with title = "One Example Setting" will be actually
 be added with the title "one_example_setting". This converted name then must be used
 in the plugin only!
 Correspondingly, the 2 new admin phrases - for above example - would then be:
   "one_example_setting" : containing the "$title" value
   and
   "one_example_setting_desc" : containing the "$descr" value
 The phrase for the description is always the new setting's name + "_descr"!
*/
function sd_checksetting3($pluginid,$groupname,$title,$descr,$input,$value,$disp,$replace=false,$dolog=false)
{
  global $DB;

  if(empty($pluginid) || ((int)$pluginid < 1) || !isset($title{0}))
  {
    return false;
  }

  // Convert "$title" to lowercase and replace all invalid characters by underscore "_" character
  $ident = strtolower(preg_replace('/[^0-9a-zA-Z_]/i', '_', $title));
  if($result = ($DB->query_first("SELECT 1 FROM {pluginsettings}
               WHERE pluginid = %d AND groupname = '%s' AND title = '%s'",
               $pluginid, $DB->escape_string($groupname), $ident)))
  {
    if(!$replace)
    {
      // At least reset the displayorder:
      if(!empty($disp) && ($disp>0))
      {
        $DB->query_first("UPDATE {pluginsettings} SET displayorder = %d
               WHERE pluginid = %d AND groupname = '%s' AND title = '%s'",
               $disp, $pluginid, $DB->escape_string($groupname), $ident);
      }
      if($dolog)
      {
        echo "- Setting '$groupname'/'".strip_tags($title)."' for plugin $pluginid already exists, skipped.<br />";
      }
      return true;
    }
    // Remove existing setting
    else
    {
      sd_removesetting($pluginid, $groupname, $ident, $dolog);
    }
  }

  // Now insert the setting; SQL enlists all columns to avoid errors
  // upon changes to pluginsettings-table (unless one of these columns
  // were actually renamed/removed)
  $DB->query("INSERT INTO {pluginsettings}
    (pluginid, groupname, title, description, input, value, displayorder)
    VALUES
    (%d, '%s', '%s', '%s', '%s', '%s', %d)",
    $pluginid, $DB->escape_string($groupname), $ident, $ident.'_descr',
    $input, $DB->escape_string($value), $disp);

  if($dolog)
  {
    echo "- <strong>Setting '$groupname'/'".strip_tags($title)."' for plugin $pluginid added.</strong><br />";
  }

  // Now add the shortcut admin phrases with the actual values
  sd_checkadminphrase($pluginid, $ident, $title, '');
  sd_checkadminphrase($pluginid, $ident.'_descr', $descr, '');

} //sd_checksetting3


// ########################## ADD A MAINSETTING ###############################
// Adds a new row to "mainsettings", optionally replacing an existing one.
// If the same setting already exists, it is kept by default unless
// "$replace" is passed as TRUE to replace it.
function sd_checkmainsetting($varname,$groupname,$input,$title,$descr,$value,$replace=false,$dolog=false)
{
  global $DB;

  $result = ($DB->query_first("SELECT 1 FROM {mainsettings} WHERE varname = '%s'",$DB->escape_string($varname)));
  if(!empty($result[0]))
  {
    if(!$replace)
    {
      if($dolog)
      {
        echo "- Main setting '$groupname'/'".strip_tags($title)."' already exists, skipped.<br />";
      }
      return;
    }
    // Remove existing setting
    else
    {
      $DB->query("DELETE FROM {mainsettings} WHERE varname = '%s' AND groupname = '%s'",
                 $DB->escape_string($varname), $DB->escape_string($groupname));
    }
  }

  // Now insert the setting:
  $DB->query("INSERT INTO {mainsettings} (settingid,varname,groupname,input,title,description,value)
    VALUES (NULL, '%s', '%s', '%s', '%s', '%s', '%s')",
    $DB->escape_string($varname), $DB->escape_string($groupname), $DB->escape_string($input),
    $DB->escape_string($title), $DB->escape_string($descr), $DB->escape_string($value));

  if($dolog)
  {
    echo "- <strong>Main Setting '$groupname'/'".strip_tags($title)."' added.</strong><br />";
  }

} //sd_checkmainsetting


// ##################### DELETE A PLUGIN'S PHRASE #############################

function sd_removephrase($pluginid,$varname,$dolog=false)
{
  global $DB;

  if(empty($varname) || empty($pluginid) || ((int)$pluginid < 1))
  {
    return false;
  }

  if($DB->query("DELETE FROM {phrases} WHERE pluginid = %d AND varname = '%s'",
                $pluginid, $DB->escape_string($varname)))
  {
    if($dolog)
    {
      echo "- <strong>Removed phrase '$varname' for plugin $pluginid.</strong><br />";
    }
  }

  return true;
} //sd_removephrase


// ########################### ADD A PLUGIN PHRASE ############################
// Adds a new plugin "phrase", optionally replacing an existing phrase.
// If the same phrase already exists, it is kept by default unless
// "$replace" is TRUE to replace it.
function sd_checkphrase($pluginid,$vname,$defaultphrase,$replace=false,$dolog=false)
{
  global $DB;

  if(empty($pluginid) || ((int)$pluginid < 1))
  {
    return false;
  }

  if($result = ($DB->query_first('SELECT 1 FROM {phrases} WHERE pluginid = %d '.
                                 "AND varname = '%s'",
                                 $pluginid, $DB->escape_string($vname))))
  {
    if(empty($replace))
    {
      if($dolog)
      {
        echo "- Phrase '$vname'/'".strip_tags($defaultphrase)."' for plugin $pluginid already exists, skipped.<br />";
      }
      return false;
    }
    else
    {
      // Remove existing phrase
      sd_removephrase($pluginid, $vname);
    }
  }

  // Now insert phrase for plugin
  $DB->query('INSERT INTO {phrases} (phraseid,pluginid,varname,defaultphrase,customphrase)'.
             "VALUES (NULL, %d, '%s', '%s', '')",
             $pluginid, $DB->escape_string($vname), $DB->escape_string($defaultphrase));

  if($dolog)
  {
    echo "- <strong>Phrase '$vname'/'".strip_tags($defaultphrase)."' added for plugin $pluginid.</strong><br />";
  }

  return true;

} //sd_checkphrase


// ########################### ADD A PLUGIN PHRASE ############################
// Adds a new "adminphrase", optionally replacing an existing phrase.
// If the same phrase already exists, it is kept by default unless
// "$replace" is TRUE to replace it.
function sd_checkadminphrase($pluginid, $vname, $defaultphrase, $adminpage=0,
                             $replace=false, $dolog=false)
{
  global $DB;

  if(empty($pluginid) || ((int)$pluginid < 0))
  {
    return false;
  }

  if($result = ($DB->query_first("SELECT 1 FROM {adminphrases} WHERE pluginid = %d and varname = '%s'",
                $pluginid, $DB->escape_string($vname))))
  {
    if(empty($replace))
    {
      if($dolog)
      {
        echo "- Admin-Phrase <strong>'$vname'/'".strip_tags($defaultphrase)."'</strong> for plugin $pluginid already exists, skipped.<br />";
      }
      return false;
    }
    else
    {
      // Remove existing phrase
      if(function_exists('DeleteAdminPhrase'))
      {
        DeleteAdminPhrase($pluginid,$vname); // core SD3 function
      }
    }
  }
  if(!empty($pluginid)) $adminpage = 2; //2=Plugins
  // Now insert phrase for plugin
  $DB->query('INSERT INTO {adminphrases} (adminphraseid,adminpageid,pluginid,varname,defaultphrase,customphrase)'.
             "VALUES (NULL, %d, %d, '%s', '%s', '')",
             $adminpage, $pluginid, $DB->escape_string($vname), $DB->escape_string($defaultphrase));

  if($dolog)
  {
    echo "- Admin-Phrase <strong>'$vname'/'".strip_tags($defaultphrase)."'</strong> added for plugin $pluginid.<br />";
  }

  return true;

} //sd_checkadminphrase

} //DO NOT REMOVE!