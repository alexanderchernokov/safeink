<?php
// ############################################################################
// CACHE FILE FUNCTIONS (SD313x)
// ############################################################################
/*
* Instantiate object with passing the cache folder as string parameter;
  cache will only activate if folder exists and is writable
  Example: $SDCache = new SDCache($myfolder);

* use SetCacheFolder to initiate or switch cache folder; if folder exists and
  is writable the cache will activate;
  Important: cache folder MUST have a trailing slash, e.g. "./cache/"

* call SetExpireTime(60) to set cache items invalid after 60 minutes,
  i.e. if a cached item file is older than the expiration time, a "read_var"
  call will return false and program should recreate it's content and
  call "write_var" again

* check if cache is actually active by calling IsActive() (returns true/false)

* Caching routines "read_var" and "write_var" return false if cache is disabled

* Common parameter "$cache_id" (string; min. 4 characters) identifies
  the cached item.

*/
if(!defined('IN_PRGM')) exit();

class SDCache
{
  private $cache_active = false;
  private $cache_folder = '';
  private $cache_ids    = array();
  private $cache_hits   = 0;
  private $cache_expire = 86400; // holds seconds; default: 24 hours
  private $init = false;

  function SDCache($cache_folder=null)
  {
    $this->init = true;
    $this->cache_active = $this->SetCacheFolder($cache_folder);
    $this->init = false;
  }

  function IsActive()
  {
    return $this->cache_active;
  }

  function SetCacheFolder($cache_folder)
  {
    if(!empty($cache_folder) && is_dir($cache_folder) && is_writable($cache_folder))
    {
      $cache_id = 'CACHE_WRITABLE';
      $cache_folder .= substr($cache_folder,-1) == '/' ? '' : '/';
      $this->cache_folder = $cache_folder;
      $cache_file = $this->CalcCachefileForID($cache_id);
      $this->init = true;
      if((is_file($cache_file) && is_writable($cache_file)) ||
         ($this->write_var($cache_id, 'OK', 'true') !== false))
      {
        $this->init = false;
        return true;
      }
    }
    $this->cache_folder = '';
    $this->cache_active = false;
    $this->init = false;
    return false;
  }

  function SetExpireTime($minutes=0)
  {
    // If value is invalid or below 1, then deactivate cache
    if(!isset($minutes) || (intval($minutes) < 1))
    {
      $this->cache_active = false;
      $this->cache_expire = 86400; // 24 hours
      return false;
    }

    // Sanity check for expiration, minimum is 5 minutes
    if(($minutes < 5) || ($minutes > (30*24*3600))) // min. 1 hour, max. 30 days
    {
      $minutes = 86400; // 24 hours
    }

    $this->cache_active = true;
    $this->cache_expire = (int)$minutes * 60;

    return $this->cache_expire;
  }

  function GetCacheHits()
  {
    return $this->cache_hits;
  }

  function CalcCacheIDForID($identifier)
  {
    if(empty($identifier) || (strlen($identifier) < 4))
    {
      return false;
    }
    //SD344: clean identifier from unwanted chars; max. 128 chars
    $identifier = preg_replace('/[^a-zA-Z0-9\\.=_,]/', '', $identifier);
    $identifier = substr($identifier, 0, 128);
    //SD343: cache ids internally
    if(isset($this->cache_ids[$identifier]))
    {
      $this->cache_hits++;
      return $this->cache_ids[$identifier];
    }
    $res = md5('cache_'.$identifier) . '.php';
    $this->cache_ids[$identifier] = $res;
    return $res;
  }

  function CalcCachefileForID($identifier, $cache_folder=null)
  {
    if(($cache_id = $this->CalcCacheIDForID($identifier)) !== false)
    {
      return (!empty($cache_folder) ? $cache_folder : $this->cache_folder) . $cache_id;
    }

    return false;
  }

  function CacheExistsForID($identifier, $cache_folder=false)
  {
    if(($cache_file = $this->CalcCachefileForID($identifier)) !== false)
    {
      return is_file($cache_file);
    }

    return false;
  }

  function read_var($cache_id, $varname)
  {
    if(!$this->cache_active || empty($varname) || !isset($varname) ||
       (($cachefile = $this->CalcCachefileForID($cache_id)) === false))
    {
      return false;
    }

    // Does file exist and is readable?
    if(is_file($cachefile) && ($info = @stat($cachefile)))
    {
      // Has cache file expired?
      if(($info['mtime'] + $this->cache_expire) < time())
      {
        return false;
      }
      // Including the file within this function only loads the variable
      // into the local buffer and does not overwrite any "globals"
      if(@include($cachefile))
      {
        // If $varname is a single name, return value or false
        if(!is_array($varname))
        {
          return isset(${"$varname"}) ? ${"$varname"} : false;
        }

        // Multiple values are requested, return filled array
        $result = array();
        foreach($varname as $key)
        {
          if(isset(${"$key"}))
          {
            $result[$key] = ${"$key"};
          }
        }

        //TODO: store a copy of result internally to cache in memory??
        return (array)$result;
      }
    }
    return false;

  } //read_var

  function write_var($cache_id, $varname, $values, $multiple = false)
  {
    static $line_end = ";\r\n";

    if((!$this->cache_active && !$this->init) || (empty($varname) && !isset($values)))
    {
      return false;
    }
    if(($cachefile = $this->CalcCachefileForID($cache_id)) === false)
    {
      return false;
    }
    if(!is_writable($this->cache_folder))
    {
      return false;
    }

    // Return false if open or any write failed
    $ok = false;
    $olddog = isset($GLOBALS['sd_ignore_watchdog'])?$GLOBALS['sd_ignore_watchdog']:false; //SD372: use isset
    $GLOBALS['sd_ignore_watchdog'] = true;
    if(($handle = @fopen($cachefile, 'w')) !== false)
    {
      // Try to get a lock on file first
      if(!@flock($handle, LOCK_EX))
      {
        @fclose($handle);
        $GLOBALS['sd_ignore_watchdog'] = $olddog;
        return false;
      }

      if(false !== ($ok = @fwrite($handle, '<'."?php\r\n\$cachestamp = ".time().$line_end)))
      {
        $ok = @fwrite($handle, '// Cache ID: ' . $cache_id . $line_end);

        if($ok && !empty($multiple) && is_array($values))
        {
          foreach($values as $key => $value)
          {
            $ok = @fwrite($handle, '$' . $varname . $key . ' = ');
            if($ok !== false)
            {
              if(isset($value))
              {
                $ok = @fwrite($handle, var_export($value, true) . $line_end);
              }
              else
              {
                $ok = @fwrite($handle, "''".$line_end);
              }
            }
            if($ok === false) break;
          }
        }
        else
        if($ok)
        {
          $ok = @fwrite($handle, '$'.$varname .' = ');
          if($ok !== false)
          {
            if(isset($values))
            {
              @fwrite($handle, var_export($values, true) . $line_end);
            }
            else
            {
              @fwrite($handle, 'array()'.$line_end);
            }
          }
        }
        if($ok)
        {
          fwrite($handle, "\r\n");
        }
      }
      @flock($handle, LOCK_UN);
      @fclose($handle);
      $GLOBALS['sd_ignore_watchdog'] = $olddog;
      return ($ok === false) ? false : true;
    }

    $GLOBALS['sd_ignore_watchdog'] = $olddog;
    return false;

  } //write_var

  function delete_cacheid($cache_id)
  {
    if(empty($cache_id)) return false;
    if(($cachefile = $this->CalcCachefileForID($cache_id)) !== false)
    {
      if(is_file($cachefile)) @unlink($cachefile);
      return true;
    }
    return false;
  } //delete_cacheid

  private function purge_folder($folder,$ignoreLength=false,$phpOnly=false)
  {
    if(!$this->cache_active) return false;
    if(!is_dir($folder) ||
       (false === ($d = @dir($folder))))
    {
      return false;
    }
    $cachefile_len = strlen($this->CalcCacheIDForID('dummy'));
    while (false !== ($entry = $d->read()))
    {
      if( (substr($entry,0,1)!='.') && is_file($folder.$entry) &&
          ( ( (!empty($ignoreLength) || (strlen($entry) == $cachefile_len)) &&
              in_array(substr($entry,-4), array('.css','.dat','.php')))
            ||
            (empty($phpOnly) &&
             (in_array(substr($entry,-4), array('.css','.dat','.php')) ||
              (substr($entry,0,3)=='min') || (substr($entry,-3)=='.gz'))) )
        )
      {
        $fn = $folder . $entry;
        @unlink($fn);
      }
    }
    $d->close();

    unset($d, $cachefile_len);
    return true;

  } //purge_folder

  function purge_cache($phpOnly=false)
  {
    $result = $this->purge_folder($this->cache_folder,false,$phpOnly);
    if(empty($phpOnly))
    {
      $result &= $this->purge_folder(SD_INCLUDE_PATH.'tmpl/comp/',true);
    }
    return $result;
  } //purge_cache

} // end of class
