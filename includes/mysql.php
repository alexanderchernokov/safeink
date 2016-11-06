<?php
if(!defined('IN_PRGM')) return false;

class DB
{
  public $server       = 'localhost';
  public $user         = 'root';
  public $password     = '';
  public $database     = '';
  public $charset      = '';

  public $table_names_arr = array();

  public $conn         = 0;
  public $queryid      = 0;
  public $row          = array();

  public $ignore_error = false;
  public $errdesc      = '';
  public $errno        = 0;

  public $query_count  = 0;
  public $row_count    = 0; // SD313x
  public $last_query   = '';
  public $result_type  = MYSQL_BOTH;
  public $skip_curly   = false; //SD343

  // ##################### connected ######################
  function connected()
  {
    return ($this->conn !== 0);
  }

  // ###################### connect #######################
  function connect($SkipError = false)
  {
    if(!function_exists('mysql_connect'))
    {
      // mysql extensions not installed
      die('<strong>The PHP MySql extension libraries are not installed on this server!</strong>');
    }
    if(0 == $this->conn)
    {
      if($this->password == '')
      {
        $this->conn = @mysql_connect($this->server, $this->user);
      }
      else
      {
        $this->conn = @mysql_connect($this->server, $this->user, $this->password);
      }

      if(!$this->conn)
      {
        if(!$SkipError)
        {
          $this->error('Unable to establish a connection to the MySQL server!');
        }
      }
      else
      if($this->database != '')
      {
        if(!@mysql_select_db($this->database, $this->conn))
        {
          if(!$SkipError)
          {
            $this->error("Cannot select database: " . $this->database . ".");
          }
        }
        else
        {
          // we are connected and have selected a database
          // lets init the table names
          $this->set_names('utf8'); //SD322 2010-11-01
          $this->set_table_names();
          return true;
        }
      }
    }
    return false;
  }

  //SD322: now uses database name as array-key to support storage for multiple DB's
  function set_table_names($IsSubdreamer=true)
  {
    if($this->queryid = mysql_query("SHOW TABLES FROM `" . $this->database . '`'))
    {
      $this->table_names_arr[$this->database] = array();
      while($table_name_arr = mysql_fetch_array($this->queryid))
      {
        $this->table_names_arr[$this->database][] = $IsSubdreamer ? substr($table_name_arr[0], strlen(PRGM_TABLE_PREFIX)) : $table_name_arr[0];
      }
    }
  }

  //SD343
  function db_version()
  {
    return preg_replace('/[^0-9.].*/', '', @mysql_get_server_info($this->conn));
  }

  //SD322: check if table with given name exists
  function table_exists($tablename='')
  {
    if(empty($tablename)) return false;
    if($this->queryid = mysql_query("SHOW FULL TABLES FROM `" . $this->database . '`'))
    {
      while($table_arr = mysql_fetch_array($this->queryid))
      {
        if($table_arr[0] == $tablename)
        {
          return true;
        }
      }
      return false;
    }
  }

  // ###################### select database #######################

  function select_db($database = '', $SkipError = false)
  {
    if(isset($database) && strlen($database))
    {
      $this->database = $database;
    }
    if(!$this->conn)
    {
      $this->connect($SkipError);
    }
    if(!$this->conn || !@mysql_select_db($this->database, $this->conn))
    {
      if(!$SkipError)
      {
        $this->error('Sorry, but database is not available!');
      }
      return false;
    }
    return true;
  }

  // ###################### escape string ###########################

  function escape_string($string)
  {
    if(!isset($string) || !strlen($string)) return '';
    if(function_exists('mysql_real_escape_string'))
    {
      return mysql_real_escape_string($string, $this->conn);
    }
    if(function_exists('mysql_escape_string'))
    {
      return mysql_escape_string($string);
    }
    return addslashes($string);
  }

  // ##################### format database query ####################

  function _format_query_callback($match, $init = FALSE)
  {
    static $args = NULL;

    if ($init)
    {
      $args = $match;
      return;
    }

    switch ($match[1])
    {
      case '%d':
        return (int) array_shift($args);
      case '%s':
        /* Could do mysql_real_escape_string here but it's already been done in SD */
        return (string)array_shift($args);
      case '%%':
        return '%';
      case '%f':
        return (float) array_shift($args);
      case '%b': // binary data
        return '';
    }
  }

  function _format_query($sql, $args)
  {
    if (isset($args[0]) and is_array($args[0]))
    {
      $args = $args[0];
    }

    $this->_format_query_callback($args, TRUE);
    $sql = preg_replace_callback('/(%d|%s|%%|%f)/', array($this, '_format_query_callback'), $sql);

    return $sql;
  }

  function prefix_table_name($matches)
  {
    // Array ( [0] => {mainsettings} [1] => mainsettings )
    $table_name = $matches[1];
    //SD322: added checks to not change pagebreak and serialize'd results
    if(($table_name != 'pagebreak') && (substr($table_name,0,2) != 'a:') &&
       isset($this->table_names_arr[$this->database]) &&
       @in_array($table_name, $this->table_names_arr[$this->database]))
    {
      return PRGM_TABLE_PREFIX . $table_name;
    }
    else
    {
      return $matches[0];
    }
  }

  // ###################### query #######################

  function query($query_string)
  {
    $this->row_count = 0;
    $args = func_get_args();
    array_shift($args);

    //SD344: if "skip_curly" is true, then do not process query for
    // curly bracket replacements; e.g. used when storing
    // Smarty templates in table
    if(empty($this->skip_curly))
    {
      $new_query_string = preg_replace_callback("#{([^}|pagebreak|\s]*\w+[^{|pagebreak|\s]*)}#i", array($this, 'prefix_table_name'), $query_string);

      //SD322: do not replace {pagebreak} in statement
      // prefix not added, this can happen for queries like these:
      // DROP TABLE IF EXISTS, CREATE TABLE IF NOT EXISTS etc
      if($new_query_string == $query_string)
      {
        $new_query_string = preg_replace("#{([^}|pagebreak|\s]*\w+[^{|pagebreak|\s]*)}#i", PRGM_TABLE_PREFIX . "$1", $query_string);
      }
    }
    else
    {
      $new_query_string = $query_string;
    }

    if(count($args) > 0)
    {
      $new_query_string = $this->_format_query($new_query_string, $args);
    }

    $this->last_query = $new_query_string; //SD341
    if(!$this->queryid = @mysql_query($new_query_string, $this->conn))
    {
      $this->error('Invalid SQL: '.$new_query_string);
      return false;
    }
    $this->errdesc = ''; $this->errno = 0;
    $this->query_count++;

    return $this->queryid;
  }

  // ###################### query #######################

  function query_unbuffered($query_string)
  {
    $this->row_count = 0;
    $args = func_get_args();
    array_shift($args);

    $new_query_string = preg_replace_callback("#{([^}|pagebreak|\s]*\w+[^{|pagebreak|\s]*)}#i", array($this, 'prefix_table_name'), $query_string);

    //SD322: do not replace {pagebreak} in statement
    // prefix not added, this can happen for queries like these:
    // DROP TABLE IF EXISTS, CREATE TABLE IF NOT EXISTS etc
    if($new_query_string == $query_string)
    {
      $new_query_string = preg_replace("#{([^}|pagebreak|\s]*\w+[^{|pagebreak|\s]*)}#i", PRGM_TABLE_PREFIX . "$1", $query_string);
    }

    if(count($args) > 0)
    {
      $new_query_string = $this->_format_query($new_query_string, $args);
    }

    $this->last_query = $new_query_string; //SD341
    if(!$this->queryid = @mysql_unbuffered_query($new_query_string, $this->conn))
    {
      $this->error("Invalid SQL: " . $new_query_string);
      return false;
    }
    $this->errdesc = ''; $this->errno = 0;
    $this->query_count++;

    return $this->queryid;
  }

  // ###################### query first #######################

  function query_first($query_string)
  {
    $args = func_get_args();
    array_shift($args);

    if($queryid = $this->query($query_string, $args))
    {
      if(!in_array($this->result_type, array(MYSQL_BOTH,MYSQL_NUM,MYSQL_ASSOC)))
      {
        $this->result_type = MYSQL_BOTH;
      }
      $returnarray = $this->fetch_array($queryid, $query_string, $this->result_type);
      $this->free_result($queryid);
      $this->result_type = MYSQL_BOTH;
      return $returnarray;
    }
    return false;
  }

  // ###################### fetch array #######################

  function fetch_array($queryid = -1, $query_string = '', $result_type = null)
  {
    if(!empty($queryid) && ($queryid > 0))
    {
      $this->queryid = $queryid;
    }
    if(isset($this->queryid))
    {
      if(isset($result_type))
      {
        if(($result_type==MYSQL_ASSOC) || ($result_type==MYSQL_BOTH))
        {
          $this->result_type = $result_type;
        }
        else
        {
          $this->result_type = MYSQL_BOTH;
        }
      }

      if((false === $this->queryid) || !is_resource($this->queryid))
      {
        $this->row = false;
      }
      else
      {
        if($this->result_type == MYSQL_ASSOC)
        {
          $this->row = @mysql_fetch_assoc($this->queryid);
        }
        else
        {
          $this->row = @mysql_fetch_array($this->queryid, MYSQL_BOTH);
        }
        $this->result_type = MYSQL_BOTH;
        if(empty($this->row))
        {
          return null;
        }
      }
      $this->row_count++;
    }
    else
    {
      if(!empty($query_string))
      {
        $this->error("Invalid query id (".$this->queryid.") on query string: $query_string");
      }
      else
      {
        $this->error("Invalid query id: ".$this->queryid);
      }
    }

    return $this->row;
  }

  function fetch_array_all($queryid=-1, $result_type = MYSQL_ASSOC) // SD313x - new function
  {
    if($queryid != -1)
    {
      $this->queryid = $queryid;
    }

    $returnarray = array();
    if(isset($this->queryid))
    {
      while($row = $this->fetch_array($queryid, '', $result_type))
      {
        $returnarray[] = $row;
      }
      $this->row_count = count($returnarray);
      $this->free_result($queryid);
    }
    return $returnarray;
  }

  function affected_rows()
  {
    if($this->conn)
    {
      $result = @mysql_affected_rows($this->conn);
      return $result;
    }
    else
    {
      return false;
    }
  }

  function found_rows() //SD351
  {
    # ONLY after SELECT like this: SELECT SQL_CALC_FOUND_ROWS ID ...
    if($this->conn && ($getrows = @mysql_query('SELECT FOUND_ROWS()')))
    {
      $dummy = @mysql_fetch_array($getrows);
      return $dummy;
    }
    return false;
  }

  function free_result($queryid=-1)
  {
    if($queryid != -1)
    {
      $this->queryid = $queryid;
    }
    if(@is_resource($this->queryid) &&
       (get_resource_type($this->queryid)==='mysql result'))
    {
      return @mysql_free_result($this->queryid);
    }
    else
    {
      return false;
    }
  }

  function get_num_rows()
  {
    if($this->conn && @is_resource($this->queryid))
    {
      return mysql_num_rows($this->queryid);
    }
    return 0;
  }

  function get_num_fields()
  {
    if(!$this->conn) return false;
    return @mysql_num_fields($this->queryid);
  }

  function insert_id()
  {
    if(!$this->conn) return false;
    return @mysql_insert_id($this->conn);
  }

  function close()
  {
    if($this->conn)
    {
      if(defined('DEBUG_MYSQL') && DEBUG_MYSQL && function_exists('WatchDog'))
      {
        if($res=$this->stat($this->conn))
        {
          if(is_string($res) && strlen($res))
          {
            $this->ignore_error = true;
            Watchdog('mysql',$res);
            $this->ignore_error = false;
          }
        }
      }
      @mysql_close($this->conn);
      return true;
    }
    return false;
  }

  function stat($charset)
  {
    if(!$this->conn) return false;
    return @mysql_stat($this->conn);
  }

  function getclientencoding()
  {
    if(!$this->conn) return false;
    return @mysql_client_encoding($this->conn);
  }

  function geterrdesc()
  {
    if(!$this->conn) return false;
    $this->errdesc = @mysql_error();
    return $this->errdesc;
  }

  function geterrno()
  {
    if(!$this->conn) return false;
    $this->errno = @mysql_errno();
    return $this->errno;
  }

  private function error($msg)
  {
    global $userinfo, $sdlanguage;

    $this->skip_curly = false; //SD343: reset flag for normal ops
    $this->errdesc = mysql_error();
    $this->errno   = mysql_errno();

    if($this->ignore_error) return;

    // not defined during install
    if(!defined('EMAIL_CRLF'))
    {
      define('EMAIL_CRLF', "\n");
    }

    $db_err_message  = PRGM_NAME . " Database Error:" . EMAIL_CRLF . EMAIL_CRLF;
    $db_err_message .= $msg . " " . EMAIL_CRLF . EMAIL_CRLF;
    $db_err_message .= "MySQL Error Description: " . $this->errdesc . " " . EMAIL_CRLF;
    $db_err_message .= "MySQL Error Number: " . $this->errno." " . EMAIL_CRLF;
    $db_err_message .= "Date: " . @gmdate("l jS \of F Y h:i:s A") . EMAIL_CRLF; // SD313x changed date format
    $db_err_message .= "File: http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . EMAIL_CRLF;
    //SD343: added USERIP
    if(defined('USERIP')) $db_err_message .= 'User IP: '.USERIP;

    //SD360: display SQL error during install/upgrade and continue
    if(defined('INSTALLING_PRGM') ||
       (defined('UPGRADING_PRGM') && !empty($userinfo['adminaccess'])))
    {
      echo '<pre>SQL Error: '.htmlspecialchars($db_err_message).'</pre><br />';
      return;
    }

    //SD360: do not send emails for installs/upgrades
    if(defined('TECHNICAL_EMAIL') && !defined('INSTALLING_PRGM') && !defined('INSTALLING_PRGM'))
    {
      SendEmail(TECHNICAL_EMAIL, PRGM_NAME . ' Database Error', $db_err_message);
    }

    if(!empty($userinfo['adminaccess']) &&
       ((defined('DEBUG') && DEBUG) || (defined('DEBUG_MYSQL') && DEBUG_MYSQL) ||
        !defined('TECHNICAL_EMAIL') || !strlen(TECHNICAL_EMAIL)))
    {
      echo '
      <br /><form>
      <textarea style="width: 100%; height: 15em; border: 2px solid #ff0000; padding: 5px;" wrap="off">
      ' . htmlspecialchars($db_err_message) . '
      </textarea>
      </form><br />';
    }
    else
    {
      if(function_exists('DisplayDate'))
      {
        $dd = DisplayDate(time(),'Y-m-d H:i');
      }
      else
      {
        $dd = date('Y-m-d H:i',time());
      }
      $msg = isset($sdlanguage['mysql_error_message']) ? $sdlanguage['mysql_error_message'] :
                   '<p style="font-size: 15px; border: 1px solid red; padding: 10px;">We are sorry, a database error has occurred and pageload stopped, an admin has been notified.</p>';
      if(function_exists('DisplayMessage'))
        DisplayMessage($msg.'<br />'.$dd, true, 'Database Error');
      else
        echo $msg.'<br />'.$dd, true, 'Database Error';
    }

    exit();
  }

  // ############### switch mysql to use specific collation ###################

  function set_names($charset)
  {
    if($this->conn && !empty($charset))
    {
        $charset_tmp = strtolower($charset);
        // Map HTML character set to MySQL character set
        $charsets = array(
          // Chinese-traditional.
          'big5' => 'big5',
          // Chinese-simplified.
          'gbk' => 'gbk',
          // West European.
          'iso-8859-1' => 'latin1',
          // Romanian.
          'iso-8859-2' => 'latin2',
          // Turkish.
          'iso-8859-9' => 'latin5',
          // Thai.
          'tis-620' => 'tis620',
          // Persian, Chinese, etc.
          'utf-8' => 'utf8',
          // Russian.
          'windows-1251' => 'cp1251',
          // Greek.
          'windows-1253' => 'utf8', // not native to MySQL!
          // Hebrew.
          'windows-1255' => 'utf8', // not native to MySQL!
          // Arabic.
          'windows-1256' => 'cp1256'
        );

        $charset_tmp = str_replace(array_keys($charsets), array_values($charsets), $charset_tmp);

        $collate = '';
        if(($charset == 'utf-8') || ($charset == 'utf8')) // SD313x - also "utf8"
        {
          $collate = " COLLATE 'utf8_unicode_ci'";
        }

        //SD342 check PHP version
        if(version_compare(PHP_VERSION, '5.2.3', 'ge'))
        {
          return @mysql_set_charset($charset,$this->conn);
        }
        else
        {
          $this->query("SET NAMES '%s' $collate", $this->escape_string($charset));
        }
    }
  }


  // SD313: check if a given table has a specified column
  function column_exists($tablename, $columnname)
  {
    if(!isset($tablename[0]) || !isset($columnname[0]))
    {
      return false;
    }
    $prevIgnore = $this->ignore_error; //SD342
    $this->ignore_error = true;
    $result = $this->query_first("SHOW COLUMNS FROM `%s` WHERE `field` = '%s'",
                                 $this->escape_string($tablename),
                                 $this->escape_string($columnname) );
    $this->ignore_error = $prevIgnore;
    return isset($result) && ($result !== false) && is_array($result) && (count($result)>0);
  }


  // SD313: check if a given table's column is contained in *any* index
  // for the given table; this shorter version is slightly faster than below.
  function index_for_column_exists($tablename, $columnname)
  {
    if(empty($tablename) || empty($columnname)) return false;

    $result = $this->query_first("SHOW INDEXES IN `%s` WHERE `column_name` = '%s'",
                                 $this->escape_string($tablename),
                                 $this->escape_string($columnname) );

    return isset($result) && ($result !== false) && ($result['Column_name'] == $columnname);

  } //index_for_column_exists


  // SD342: check if a given table's index name already exists
  function index_exists($tablename, $indexname)
  {
    if(empty($tablename) || empty($indexname)) return false;

    $result = $this->query_first("SHOW INDEXES IN `%s` WHERE `Key_name` = '%s'",
                                 $this->escape_string($tablename),
                                 $this->escape_string($indexname) );

    return isset($result) && ($result !== false) && ($result['Key_name'] == $indexname);

  } //index_exists


  // SD360: drop a index on specified table (if it exists)
  function drop_index($tablename, $indexname)
  {
    if(empty($tablename) || empty($indexname)) return false;

    if(!$this->index_exists($tablename, $indexname)) return true;

    return $this->query('ALTER TABLE %s DROP INDEX `%s`',
                        $this->escape_string($tablename),
                        $this->escape_string($indexname) );

  } //drop_index


  // This function returns TRUE if the specified column "$columnname" is either
  // part of any index OR optionally of a specified index "$indexname" for
  // table "$tablename", otherwise it returns FALSE.
  // "$tablename" HAS to be the full name, i.e. including the SD prefix if it
  // is a SD-table!
  function columnindex_exists($tablename,$columnname,$indexname='')
  // $indexname is optional and could be used in case of compound keys
  {
    global $DB;

    if(empty($tablename) || empty($columnname)) return false;

    /* Another approach to go by INFORMATION_SCHEMA:
    SELECT * FROM `KEY_COLUMN_USAGE` ORDER BY `KEY_COLUMN_USAGE`.`TABLE_NAME` ASC
    WHERE TABLE_NAME = xxx AND COLUMN_NAME = yyy
    */
    if($getindex = $DB->query('SHOW INDEX FROM `'.$this->escape_string($tablename).'`'))
    {
      while($result = $DB->fetch_array($getindex,null,MYSQL_ASSOC))
      {
        if($result['Column_name'] == $columnname)
        {
          if(!strlen(trim($indexname)) || ($result['Key_name'] == $indexname))
          {
            return true;
          }
        }
      }

      return isset($result) && isset($result['Column_name']) && ($result['Column_name'] == $columnname);

    }
    return false;
  } //columnindex_exists


  // SD313: adds either a un-/named index on a table for a given column
  // if it does not exist yet
  function add_tableindex($tablename, $columnname, $indexname='', $asc = true, $length=false)
  {
    if(empty($tablename) || empty($columnname)) return false;

    if(isset($indexname) && strlen(trim($indexname))) //SD342
    {
      if($this->index_exists($tablename,trim($indexname)))
      {
        return true;
      }
    }
    else
    if(strlen(trim($columnname)) && (false===strpos(',',$columnname))) //SD342
    {
      if($this->index_exists($tablename,$columnname))
      {
        return true;
      }
    }
    if($this->columnindex_exists($tablename,$columnname,$indexname))
    {
      return true;
    }
    $result = $this->query('CREATE INDEX '.
                            (strlen(trim($indexname)) ?
                              "`".$this->escape_string($indexname)."`" :
                              "`".$this->escape_string($columnname)."`") .
                           " ON %s (%s".
                           ($length ? '('.(int)$length.')' : ''). // index length (must be used on TEXT, BLOB!)
                           (!empty($asc) ? ' ASC' : ' DESC').     // index sort order (default: ASC)
                           ')',
                           $this->escape_string($tablename),
                           $this->escape_string($columnname) );

    return $result;

  } //add_tableindex


  // SD313: adds a named column to given table if *not exists* and MUST pass
  //        the column type (incl.length!) and optionally further specifics
  //        like "NOT NULL" and "DEFAULT xxx" parameters in "$nullable".
  // NOTE: tablename MUST be the FULL name incl. table prefix (no "{ }")
  /* EXAMPLE USAGE:
     sd_addtablecolumn('sd_mytable', 'column1', 'TINYINT', 'NOT NULL DEFAULT 0');
     sd_addtablecolumn('sd_othertable', 'columnx', 'VARCHAR(255)', "NOT NULL DEFAULT ''");
  */
  function add_tablecolumn($tablename, $columnname, $columntype, $nullable='')
  {
    if(empty($tablename) || empty($columnname) || empty($columntype) ||
       $this->column_exists($tablename,$columnname))
    {
      return false;
    }
    $nullable = isset($nullable) ? $nullable : '';
    $statement = "ALTER TABLE `%s` ADD `%s` %s $nullable";
    return $this->query($statement,
                        $this->escape_string($tablename),
                        $this->escape_string($columnname),
                        $columntype);
  } // add_tablecolumn


  function remove_tablecolumn($tablename, $columnname)
  {
    if(empty($tablename) || empty($columnname) ||
       !$this->column_exists($tablename,$columnname))
    {
      return false;
    }
    return $this->query('ALTER TABLE `%s` DROP `%s`',
                        $this->escape_string($tablename),
                        $this->escape_string($columnname));
  } //remove_tablecolumn

} // end of class
