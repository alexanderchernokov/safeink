<?php

define('DB_URI', 'settings.php?display_type=database');

// Stop execution if NOT within SD3 or not called from within tools handler:
if(!defined('IN_PRGM') || !defined('IN_ADMIN') || !defined('SD_INIT_LOADED'))
{
  header('Location: '.DB_URI);
  exit();
}

// CHECK PAGE ACCESS
CheckAdminAccess('settings');

// ###### Test that the backup directory exists and is writable ###############

$currentdir = FixPath(dirname(__FILE__));
$currentdir = basename($currentdir);

$backupEnabled = false;
$backupDir = FixPath(getcwd() . '/backup/');
$backupUrl = SITE_URL . $currentdir . '/backup/';

$errors = array();

if (!is_dir($backupDir))
{
 $errors[] = AdminPhrase('folder_not_found').' ('.$backupDir.')';
}
else
if (!is_writable($backupDir))
{
 $errors[] = AdminPhrase('folder_not_writable').' ('.$backupDir.')<br />'.AdminPhrase('folder_chmod');
}

if (!empty($errors))
{
  PrintErrors($errors, AdminPhrase('configuration_error'));
  unset($action);
}
else
{
  $backupEnabled = true;
}

// New function to clean server path corresponding to OS
function FixPath($fullpath, $addrootpath=true)
{
  // Workaround for WAMP environments: usage of PHP function "filesize()"
  // won't work with mixed slashes/backslashes in path (PHP 5.1.x):
  if(strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')
  {
    $dir_separator = '/';
    $fullpath = str_replace("\\", $dir_separator, $fullpath);
  }
  else
  {
    $dir_separator = "\\";
    $fullpath = str_replace("/", $dir_separator, $fullpath);
  }
  // Make sure, at then end is only ONE directory separator
  if(substr($fullpath,-2) == $dir_separator.$dir_separator)
  {
    $fullpath = substr($fullpath,0,strlen($fullpath)-1);
  }

  // Ensure trailing backslash
  $fullpath = ((substr($fullpath, -1) != $dir_separator) ? $fullpath . $dir_separator : $fullpath);

  /*
  IF a relative path is specified (not starting with "/"), treat the path
  as being relative to the "ROOT_PATH".
  However, for Win* platform (like XAMPP), do not use ROOT_PATH if a colon ":"
  is found, which indicates a drive letter = absolute path.
  */
  if(!empty($addrootpath) &&
     ((strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') && (substr($fullpath,0,1) != '/') ||
      (strtoupper(substr(PHP_OS, 0, 3))  == 'WIN') && (strpos($fullpath,':') === false)))
  {
    $fullpath = ROOT_PATH . $fullpath;
  }

  return $fullpath;
}


// ####################### FILE READ/WRITE USING GZIP  ########################

function openFileWrite($filename)
{
  if(function_exists('gzopen'))
  {
    $filename .= '.gz';
    return gzopen($filename, 'w9');
  }
  return fopen($filename, 'w');
}

function openFileRead($filename)
{
  if(function_exists('gzopen'))
  {
    return gzopen($filename, 'r');
  }
  return fopen($filename, 'r');
}

function writeFileData($handle, $data)
{
  if(function_exists('gzwrite'))
  {
    return gzwrite($handle, $data);
  }
  return fwrite($handle, $data);
}

function readFileData($handle, $size)
{
  if(function_exists('gzread'))
  {
    return gzread($handle, $size);
  }
  return fread($handle, $size);
}

function eof($handle)
{
  if(function_exists('gzeof'))
  {
    return gzeof($handle);
  }
  return feof($handle);
}

function closeFile($handle)
{
  if(function_exists('gzclose'))
  {
    return gzclose($handle);
  }
  fclose($handle);
}

// ###################### END FILE READ FUNCTIONS #############################

function BackupTable($tablename, $fp)
{
  global $DB;

  if(empty($tablename) || empty($fp))
  {
    $msg = AdminPhrase('table_export_failed')." '$tablename'<br />";
    return '';
  }
  // Get the SQL to create the table
  $createTable = $DB->query_first("SHOW CREATE TABLE `$tablename`");

  // Drop if it exists
  $tableDump = "DROP TABLE IF EXISTS `$tablename`;\n" . $createTable['Create Table'] . ";\n\n";

  writeFileData($fp, $tableDump);

  // get data
  if($getRows = $DB->query("SELECT * FROM `$tablename`"))
  {
    $fieldCount = $DB->get_num_fields();
    $rowCount = 0;

    while ($row = $DB->fetch_array($getRows))
    {
      $tableDump = "INSERT INTO `$tablename` VALUES(";

      $fieldcounter = -1;
      $firstfield = true;

      // get each field's data
      while (++$fieldcounter < $fieldCount)
      {
        if(!$firstfield)
        {
          $tableDump .= ', ';
        }
        else
        {
          $firstfield = 0;
        }

        if(!isset($row["$fieldcounter"]))
        {
          $tableDump .= 'NULL';
        }
        else
        if($row["$fieldcounter"] != '')
        {
          $tableDump .= '\'' . addslashes($row["$fieldcounter"]) . '\'';
        }
        else
        {
          $tableDump .= '\'\'';
        }
      }

      $tableDump .= ");\n";

      writeFileData($fp, $tableDump);
      $rowCount++;
    } //while
    $DB->free_result($getRows);
  }
  writeFileData($fp, "\n\n\n");

  $msg = AdminPhrase('exported_table')." '<strong>$tablename</strong>' - ".AdminPhrase('exported_rows')." <strong>$rowCount</strong><br />";

  return $msg;

} //BackupTable


function BackupSingleTable($tablename)
{
  global $backupDir;

  $msg = '';
  if(!empty($tablename))
  {
    $path = $backupDir . $tablename . '_' . DisplayDate(time(),'YmdHis') . '-'.mt_rand(100000,999999) . '.sql';
    if($fp = openFileWrite($path))
    {
      $msg = BackupTable($tablename, $fp);
      closeFile($fp);
      $msg .= '<br />'.AdminPhrase('backup_saved_to');
    }
    else
    {
      $msg = '<br />'.AdminPhrase('file_write_error');
    }
    $msg = $msg . " '<strong>$path</strong>'<br />";
  }
  return $msg;

} //BackupSingleTable


function BatchBackupTable($tablenames)
{
  global $DB, $backupDir;

  $path = $backupDir . $DB->database . '_' . DisplayDate(time(),'YmdHis') . '-'.mt_rand(100000,999999) . '.sql';
  $msg = '';
  if(strlen($DB->database) && (false !== ($fp = openFileWrite($path))))
  {
    for($i = 0; $i < count($tablenames); $i++)
    {
      if(!empty($tablenames[$i])) //SD361
	  {
	    $msg .= BackupTable($tablenames[$i], $fp);
	  }
    }
    closeFile($fp);
    $msg .= '<br />'.AdminPhrase('backup_saved_to');
  }
  else
  {
    $msg = '<br />'.AdminPhrase('file_write_error');
  }

  return $msg . " '<strong>$path</strong>'<br />";

} //BatchBackupTable


function ParseQueries($sql, $delimiter)
{
  $matches = array();
  $output = array();

  $queries = explode($delimiter, $sql);
  $sql = '';

  $query_count = count($queries);
  for ($i = 0; $i < $query_count; $i++)
  {
    if (($i != ($query_count - 1)) || (strlen($queries[$i] > 0)))
    {
      $total_quotes = preg_match_all("/'/", $queries[$i], $matches);
      $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $queries[$i], $matches);
      $unescaped_quotes = $total_quotes - $escaped_quotes;

      if (($unescaped_quotes % 2) == 0)
      {
        $output[] = $queries[$i];
        $queries[$i] = '';
      }
      else
      {
        $temp = $queries[$i] . $delimiter;
        $queries[$i] = '';

        $complete_stmt = false;

        for ($j = $i + 1; (!$complete_stmt && ($j < $query_count)); $j++)
        {
          $total_quotes = preg_match_all("/'/", $queries[$j], $matches);
          $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $queries[$j], $matches);
          $unescaped_quotes = $total_quotes - $escaped_quotes;

          if (($unescaped_quotes % 2) == 1)
          {
            $output[] = $temp . $queries[$j];

            $queries[$j] = '';
            $temp = '';

            $complete_stmt = true;
            $i = $j;
          }
          else
          {
            $temp .= $queries[$j] . $delimiter;
            $queries[$j] = '';
          }
        }
      }
    }
  } //for

  return $output;

} //ParseQueries


function RestoreBackup($filename)
{
  global $DB, $backupDir;

  // Read the file into memory and then execute it
  if($fp = openFileRead($backupDir . $filename))
  {
    $query = '';
    while (!eof($fp))
    {
      $query .= readFileData($fp, 10000);
    }
    closeFile($fp);

    // Split into discrete statements
    $queries = ParseQueries($query, ';');

    if($cnt = count($queries))
    {
      $inserts = 0;
      for($i = 0; $i < $cnt; $i++)
      {
        $sql = trim($queries[$i]);
        if(!empty($sql))
        {
          if(substr($sql,0,6)=='INSERT') $inserts++;
          //SD370: set flags to avoid curly brackets being replaced in templates.
          // Also ignore errors and bail loop if error code is != 0.
          $DB->skip_curly = true;
          $DB->ignore_error = true;
          $DB->query($sql);
          if($DB->errno > 0)
          {
            echo 'Error occured, please fix backup file and retry!<br />'.$DB->errdesc;
            break;
          }
        }
      }
      //SD370: re-set flags
      $DB->ignore_error = false;
      $DB->skip_curly = false;
      echo '<p class="center"><strong>'.AdminPhrase('processed_total').' '.$cnt.'<br />'.
           AdminPhrase('rows_added_total').' '.$inserts.'</strong></p>';
    }
    PrintRedirect(DB_URI, 3);
  }
  else
  {
    echo '<strong>'.AdminPhrase('file_open_error').'</strong> '.$filename;
    PrintRedirect(DB_URI, 3);
  }
}

function DeleteBackup($filename)
{
  global $DB, $backupDir;

  $msg = '';
  $fname = $backupDir . $filename;
  if(is_file($fname))
  {
    if(!@unlink($fname))
    {
      $msg = AdminPhrase('backup_remove_error');
    }
    else
    {
      $msg = AdminPhrase('backup_removed');
    }
  }

  PrintRedirect(DB_URI, 3, $msg." '<strong>$fname'</strong>");
}

// ####################### Display Action Results ######################

function PrintResults($title, $message)
{

  if(strpos($message, 'ERROR') === false)
  {	
  echo '
 <div class="alert alert-success">
      ' . $message . '
 </div>';
  }
  else
  {
	  echo '
 <div class="alert alert-danger">
      ' . $message . '
 </div>';
  }
}

// ####################### Perform OP on Table ######################

function TableOperation($tablename, $OP)
{
  global $DB;

  if(!empty($tablename) && in_array($OP, array('CHECK','OPTIMIZE','REPAIR')))
  {
    $result = $DB->query_first("$OP TABLE `$tablename`");
    return AdminPhrase('table_operation')." <strong>'".$tablename."': " . $result['Msg_text'];
  }
  else
  {
    return AdminPhrase('table_operation_error');
  }

} //TableOperation


function BatchTableOperation($tablenames, $OP)
{
  global $DB;

  $msg = '';
  if(!empty($tablenames) && !empty($OP) && in_array($OP, array('CHECK','OPTIMIZE','REPAIR')))
  {
    for($i = 0; $i < count($tablenames); $i++)
    {
      if(!sd_safe_mode()) set_time_limit(300);
      $msg = $msg . TableOperation($tablenames[$i], $OP);
    }
  }
  else
  {
    $msg = AdminPhrase('table_operation_error');
  }

  return $msg;

} //BatchTableOperation


// ########################## List Backup Files ################################

function DisplayDBBackups()
{
  global $DB, $backupDir, $backupUrl;

  clearstatcache();
  StartTable(AdminPhrase('database_backups_title'), array('table', 'table-bordered', 'table-striped'));

  echo '
  <thead>
  <tr>
    <th class="tdrow1" width="50%">'.AdminPhrase('file_name').'</th>
    <th class="tdrow1">'.AdminPhrase('file_size').'</th>
    <th class="tdrow1">'.AdminPhrase('file_date').'</th>
    <th class="tdrow1" colspan="3">&nbsp;</th>
  </tr>
  </thead>
  <tbody>';
  $filestats = array();
  if($dir = @opendir($backupDir))
  {
    while (false !== ($file = readdir($dir)))
    {
      if((substr($file,0,1) != '.') && (strpos(strtolower($file),'.sql') !== false))
      {
        if($stats = @stat($backupDir . $file))
        {
          $files[strtolower($file)] = array(
            'idx'   => strtolower($file),
            'file'  => $file,
            'size'  => $stats['size'],
            'mtime' => $stats['mtime'],
            'error' => false);
        }
        else
        {
          $files[strtolower($file)] = array('idx' => strtolower($file),'file' => $file, 'error' => true);
        }
      }
    }
    if(!empty($files) && is_array($files))
    {
      asort($files);
      //for($i = 0, $fc = count($filestats); $i < $fc; $i++)
      foreach($files as $key => $filestats)
      {
        if(empty($filestats['error']))
        {
          echo '
        <tr>
          <td class="tdrow3">' . $filestats['file'] . '</td>
          <td class="tdrow3">' . DisplayReadableFilesize($filestats['size']) . '</td>
          <td class="tdrow3">' . DisplayDate($filestats['mtime'],'',true) . '</td>
          <td class="tdrow3"><a href="'.DB_URI.'&amp;dbaction=restorebackup&amp;filename=' . $filestats['file'].
          '" onclick="return confirm(\''.addslashes(AdminPhrase('file_restore_prompt')).'\');">'.
          AdminPhrase('file_restore').'</a></td>
          <td class="tdrow3"><a href="' . $backupUrl . $filestats['file']. '">'.
          AdminPhrase('file_download').'</a></td>
          <td class="tdrow3"><a class="deletelink" href="'.DB_URI.'&amp;dbaction=deletebackup&amp;filename=' . $filestats['file'].
          '">'.
          AdminPhrase('file_delete').'</a></td>
        </tr>';
        }
        else
        {
          echo '
        <tr>
          <td class="tdrow3">'.$filestats['file'].'</td>
          <td class="tdrow3" colspan="5">'.AdminPhrase('file_info_error').'</td>
        </tr>';
        }
      } //for
    }
  }

  echo '<tr>
    <td class="info" colspan="6"> '.AdminPhrase('backup_folder').' <i><strong>'.$backupDir.'</strong></i></td>
  </tr></table></div>';


} //DisplayDBBackups


// ####################### List Database Tables ######################

function DisplayDBTables()
{
  global $DB, $backupEnabled, $sd_ignore_watchdog;


  StartTable(AdminPhrase('database_tables_title'), array('table', 'table-bordered', 'table-striped'));

  echo '
  <form method="post" action="'.DB_URI.'" id="tables" name="tables">
  <input type="hidden" name="dbaction" value="" />';

  $showCollation = true;
  $DB->ignore_error = true;
  if(!$gettables = $DB->query("SELECT TABLE_NAME tablename, TABLE_ROWS `Rows`, DATA_LENGTH `Data_length`, INDEX_LENGTH `Index_length`, DATA_FREE `Data_free`, TABLE_COLLATION `Collation`".
                              " FROM INFORMATION_SCHEMA.TABLES ".
                              " WHERE TABLE_SCHEMA = '" . $DB->database .
                              "' AND TABLE_NAME LIKE '" . PRGM_TABLE_PREFIX . // only show SD tables!
                              "%' ORDER BY TABLE_NAME"))
  {
    $showCollation = false;
    $gettables = $DB->query("SHOW TABLES FROM `" . $DB->database . "` LIKE '" . PRGM_TABLE_PREFIX ."%'");
  }

  echo '
  <thead>
  <tr>
    <th class="tdrow1"><input type="checkbox" class="ace" checkall="group" onclick="javascript: return select_deselectAll (\'tables\', this, \'group\');" /><span class="lbl"></span></th>
    <th class="tdrow1">'.AdminPhrase('column_table_name').'</th>
    <th class="tdrow1">'.AdminPhrase('column_rows').'</th>
    <th class="tdrow1">'.AdminPhrase('column_data_length').'</th>
    <th class="tdrow1">'.AdminPhrase('column_index_length').'</th>
    <th class="tdrow1">'.AdminPhrase('column_overhead').'</th>
    <th class="tdrow1" colspan="4">'.AdminPhrase('column_operations').'</th>';
  if($showCollation)
  {
    echo '
    <th class="tdrow1">Collation</th>';
  }
  echo '
  </tr>
  </thead>
  <tbody>';

  $tbl_count = 0;
  if($gettables)
  {
    $old_reporting = error_reporting(0);
    error_reporting(0);
    $sd_ignore_watchdog = true;
    $DB->ignore_error = true;
    $DB->result_type = MYSQL_ASSOC;
    while($table = $DB->fetch_array($gettables,MYSQL_ASSOC))
    {
      $tbl_count++;
      $tableinfo = $DB->query_first("SHOW TABLE STATUS LIKE '" . $table['tablename'] . "'");
      echo '
      <tr>
        <td class="tdrow2"><input type="checkbox" class="ace" name="tablenames[]" value="' . $tableinfo['Name'] . '" checkme="group" /><span class="lbl"></span></td>
        <td class="tdrow3">';

      $rows = empty($tableinfo['Rows'])?0:(int)$tableinfo['Rows'];
      $tblname = $tableinfo['Name'];
      if(($tableinfo['Name']==PRGM_TABLE_PREFIX.'syslog') && ($rows>0))
      {
        echo '<a onclick="return confirm(\''.AdminPhrase('syslog_clear_log_prompt').'\');" href="settings.php?display_type=syslog&amp;action=purgelog'.SD_URL_TOKEN.'" title="Clear System Log" class="red">'.$tblname.'</a>';
        $rows = '<strong>'.number_format($rows).'<strong>';
      }
      else
      {
        echo $tblname;
        $rows = number_format($rows);
      }
      echo '</td>
        <td class="tdrow3" align="right">'.$rows.'</td>
        <td class="tdrow3" align="right">'.(isset($tableinfo['Data_length'])?DisplayReadableFilesize($tableinfo['Data_length']):'-').'</td>
        <td class="tdrow3" align="right">'.(isset($tableinfo['Index_length'])?DisplayReadableFilesize($tableinfo['Index_length']):'-').'</td>
        <td class="tdrow3" align="right">'.(!empty($tableinfo['Data_free'])?'<strong>':'') . DisplayReadableFilesize($tableinfo['Data_free']).(!empty($tableinfo['Data_free'])?'</strong>':'').'</td>
        <td class="tdrow2" align="center"><a href="'.DB_URI.'&amp;dbaction=checktable&amp;tablename='.$tblname.
          '" class="dbcheck"><i class="ace-icon fa fa-database bigger-120"></i></a></td>
        <td class="tdrow2" align="center"><a href="'.DB_URI.'&amp;dbaction=optimizetable&amp;tablename='.$tblname.
          '" class="dboptimize""><i class="ace-icon fa fa-wrench bigger-120"></i></a></td>
        <td class="tdrow2" align="center"><a href="'.DB_URI.'&amp;dbaction=repairtable&amp;tablename='.$tblname.
          '" class="dbrepair"><i class="ace-icon fa fa-life-saver bigger-120"></i></a></td>
        <td class="tdrow2" align="center">'.($backupEnabled?'<a href="'.DB_URI.'&amp;dbaction=backuptable&amp;tablename='.$tblname.
          '" class="dbexport"><i class="ace-icon fa fa-save bigger-120"></i></a>':'').'</td>';
      if($showCollation)
      {
        echo '
        <td class="tdrow3">' . $tableinfo['Collation'] . '</td>';
      }
      echo '</tr>';
      $DB->result_type = MYSQL_ASSOC;

    } //while

    if($tbl_count) $DB->free_result($gettables);
    $DB->result_type = MYSQL_BOTH;
    $DB->ignore_error = false;
    $sd_ignore_watchdog = false;
    error_reporting($old_reporting);
  }

  echo '
    <tr>
      <td class="tdrow1"><input type="checkbox" checkall="group" class="ace" onclick="javascript: return select_deselectAll (\'tables\', this, \'group\');" /><span class="lbl"></span></td>
      <td class="tdrow1">'.$tbl_count.' tables</td>
      <td class="tdrow1" colspan="4" align="right">Operation for ALL checked: </td>
      <td class="tdrow1" align="center"><a class="dbcheck" href="#" onclick="document.forms[\'tables\'].dbaction.value = \'checkall\';document.forms[\'tables\'].submit();return false;"><i class="ace-icon fa fa-database bigger-120"></i></a></td>
      <td class="tdrow1" align="center"><a class="dboptimize" href="#" onclick="document.forms[\'tables\'].dbaction.value = \'optimizeall\';document.forms[\'tables\'].submit();return false;"><i class="ace-icon fa fa-wrench bigger-120"></a></td>
      <td class="tdrow1" align="center"><a class="dbrepair" href="#" onclick="document.forms[\'tables\'].dbaction.value = \'repairall\';document.forms[\'tables\'].submit();return false;"><i class="ace-icon fa fa-life-saver bigger-120"></i></a></td>
      <td class="tdrow1" align="center"><a class="dbbackup" href="#" onclick="document.forms[\'tables\'].dbaction.value = \'backupall\';document.forms[\'tables\'].submit();return false;" '.($backupEnabled?'':'disabled'). '><i class="ace-icon fa fa-save bigger-120"></i></a></td>
      '.($showCollation?'<td class="tdrow1" colspan="6">&nbsp;</td>':'').'</tr>
    </table>
    </form>
	</div>';


  DisplayDBBackups();

  echo '
<script type="text/javascript">
jQuery(document).ready(function() {
  (function($){
    $("a.dbcheck").attr("title", "'.addslashes(AdminPhrase('operation_check')).'");
    $("a.dboptimize").attr("title", "'.addslashes(AdminPhrase('operation_optimize')).'");
    $("a.dbrepair").attr("title", "'.addslashes(AdminPhrase('operation_repair')).'");
    $("a.dbbackup").attr("title", "'.addslashes(AdminPhrase('operation_backup')).'");

  })(jQuery);
});
</script>  ';

} //DisplayDBTables


// Note: $tablename and $tablenames are 2 different settings!
$action = GetVar('dbaction', '', 'string');
$filename = GetVar('filename', '', 'string');
$tablename = GetVar('tablename', '', 'string');
$tablenames = GetVar('tablenames', '', 'array');

if(!empty($action))
{
  switch ($action)
  {
    case 'checktable':
      PrintResults(AdminPhrase('check_table_results'), TableOperation($tablename, 'CHECK'));
      break;
    case 'checkall':
      PrintResults(AdminPhrase('check_table_results'), BatchTableOperation($tablenames, 'CHECK'));
      break;
    case 'optimizetable':
      PrintResults(AdminPhrase('optimize_table_results'), TableOperation($tablename, 'OPTIMIZE'));
      break;
    case 'optimizeall':
      PrintResults(AdminPhrase('optimize_table_results'),BatchTableOperation($tablenames, 'OPTIMIZE'));
      break;
    case 'repairtable':
      PrintResults(AdminPhrase('repair_table_results'),TableOperation($tablename, 'REPAIR'));
      break;
    case 'repairall':
      PrintResults(AdminPhrase('repair_table_results'),BatchTableOperation($tablenames, 'REPAIR'));
      break;
    case 'backuptable':
      PrintResults(AdminPhrase('backup_table_results'),BackupSingleTable($tablename));
      break;
    case 'backupall':
      PrintResults(AdminPhrase('backup_table_results'), BatchBackupTable($tablenames));
      break;
    case 'restorebackup':
      RestoreBackup($filename);
      break;
    case 'deletebackup':
      DeleteBackup($filename);
      break;
  }
}

if(!in_array($action, array('deletebackup','restorebackup')))
{
  DisplayDBTables();
}
