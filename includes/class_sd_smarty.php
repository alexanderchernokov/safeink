<?php
if(!class_exists('SD_Smarty'))
{
// the below functions must be global; used by Smarty class as callback!
function sd_get_template($tpl_name, &$tpl_source, &$smarty_obj)
{
  // do database call here to fetch your template,
  // populating $tpl_source with actual template contents
  global $DB;

  $pluginid = SD_Smarty::getCurrentPluginID();
  $tpl_source = '';
  $DB->result_type = MYSQL_ASSOC;
  if(false !== ($sep_pos = strpos($tpl_name,'@')))
  {
    list($tpl_name,$pluginid) = explode('@',$tpl_name);
    $pluginid = Is_Valid_Number($pluginid,0,1,9999999);
  }
  $sql_extra = '';
  if(!empty($pluginid))
  {
    $sql_extra = ' AND tpl.pluginid = '.SD_Smarty::getCurrentPluginID();
  }
  if($tpl = $DB->query_first('SELECT rev.content FROM {templates} tpl'.
                             ' INNER JOIN {revisions} rev ON rev.revision_id = tpl.revision_id'.
                             " WHERE tpl.tpl_name = '%s'".
                             $sql_extra.
                             ' LIMIT 1',
                             $DB->escape_string($tpl_name)))
  {
    if(isset($tpl['content']))
    {
      $tpl_source = $tpl['content'];
      return true;
    }
  }
  // return true on success, false to generate failure notification
  return false;
}

function sd_get_timestamp($tpl_name, &$tpl_timestamp, &$smarty_obj)
{
  // do database call here to populate $tpl_timestamp
  // with unix epoch time value of last template modification.
  // This is used to determine if recompile is necessary.
  $tpl_timestamp = time(); // this example will always recompile!
  // return true on success, false to generate failure notification
  return true;
}

function sd_get_secure($tpl_name, &$smarty_obj)
{
  // assume all templates are secure
  return true;
}

function sd_get_trusted($tpl_name, &$smarty_obj)
{
  // not used for templates
}

//###########################################################################

class SD_Smarty
{
  private static $_smarty = null;
  private static $_use_v2 = true;
  private static $_tmpl_err = false;
  private static $_tmpl_cache = false;
  private static $_tmpl_cache_ids = false; //SD360
  private static $_pluginid = 0;

  protected function __construct()
  {
    //nada
  }


  private static function _checkVersion()
  {
    // Check whether to use smarty v2 or v3.
    // Defined in prgm_info.php to be 2 be default.
    self::$_use_v2 = !(defined('SD_SMARTY_VERSION') && (SD_SMARTY_VERSION==3));
  }


  public static function Is_Smarty3() //SD360
  {
    self::_checkVersion();
    return !self::$_use_v2;
  }


  public static function getCurrentPluginID()
  {
    return self::$_pluginid;
  }


  public static function setCurrentPluginID($pluginid)
  {
    self::$_pluginid = 0;
    if(!empty($pluginid) && ((int)$pluginid == $pluginid))
      self::$_pluginid = (int)$pluginid;
  }


  public static function getLastError()
  {
    return self::$_tmpl_err;
  }


  public static function getTemplateDir()
  {
    if(isset(self::$_smarty))
    {
      self::_checkVersion();
      return self::$_use_v2 ? self::$_smarty->template_dir : self::$_smarty->getTemplateDir();
    }
    return '';
  }


  public static function setTemplateDir($dir, $objSmarty=false)
  {
    if(!isset(self::$_smarty)) return false;
    if(!empty($objSmarty) && ($objSmarty instanceof Smarty))
    {
      if(self::$_use_v2) $objSmarty->template_dir = $dir; else $objSmarty->setTemplateDir($dir);
    }
    else
    {
      if(self::$_use_v2) self::$_smarty->template_dir = $dir; else self::$_smarty->setTemplateDir($dir);
    }
    return true;
  }


  public static function getCompileDir()
  {
    if(!isset(self::$_smarty)) return false;
    self::_checkVersion();
    return (self::$_use_v2 ? self::$_smarty->compile_dir : self::$_smarty->getCompileDir());
  }


  public static function setCompileDir($dir)
  {
    if(!isset(self::$_smarty)) return false;
    if(self::$_use_v2) self::$_smarty->compile_dir = $dir; else self::$_smarty->setCompileDir($dir);
    return true;
  }


  public static function getCacheDir()
  {
    if(!isset(self::$_smarty)) return false;
    self::_checkVersion();
    return (self::$_use_v2 ? self::$_smarty->cache_dir : self::$_smarty->getCacheDir());
  }


  public static function setCacheDir($dir)
  {
    if(!isset(self::$_smarty)) return false;
    if(self::$_use_v2) self::$_smarty->cache_dir = $dir; else self::$_smarty->setCacheDir($dir);
    return true;
  }


  public static function getInstance()
  {
    if(empty(self::$_smarty))
    {
      return self::getNew();
    }
    return self::$_smarty;
  }


  public static function template_exists($tplName)
  {
    self::_checkVersion();
    if(empty(self::$_smarty)) self::getNew();
    if(self::$_use_v2)
    {
      return self::$_smarty->template_exists($tplName);
    }
    return self::$_smarty->templateExists($tplName);
  }


  public static function display($currentPluginID, $tplNames, $objSmarty=false, $doReplacements=false)
  {
	 
    self::$_tmpl_err = false;
    if(empty($objSmarty) && isset(self::$_smarty)) $objSmarty = self::$_smarty;
    if(!isset($currentPluginID) || ($currentPluginID < 0) || ($currentPluginID > 99999999) ||
       empty($tplNames) || empty($objSmarty) || !($objSmarty instanceof Smarty))
    {
      return false;
    }
    if(!self::$_use_v2)
    {
     Smarty::muteExpectedErrors(); // important!
    }
    self::_checkVersion();
    self::setCurrentPluginID($currentPluginID);
	
    try
    {
      $ds = DIRECTORY_SEPARATOR;
      if(!is_array($tplNames)) $tplNames = array((string)$tplNames);
      $doReplacements = isset($doReplacements) && ($doReplacements===true);

      // Only with v3 we support DB-layer calls
      global $mainsettings_templates_from_db; //SD344
      if(!self::$_use_v2 && !empty($mainsettings_templates_from_db))
      {
        foreach($tplNames as $tplName)
        {
          if(false !== self::TemplateExistsInDB($currentPluginID, $tplName))
          {
            //SD350: add pluginid separated by "@" to make it unique across cloned plugins
            if($doReplacements)
            {
			  $output = $objSmarty->fetch('mysql:'.$tplName.'@'.$currentPluginID);
              echo sd_DoSkinReplacements($output);
            }
            else
            {
              $objSmarty->display('mysql:'.$tplName.'@'.$currentPluginID);
            }
            if(!self::$_use_v2)
            {
              Smarty::unmuteExpectedErrors(); // important!
            }
            return true;
          }
        }
      }

      foreach($tplNames as $tplName)
      {
        // 1. check in "tmpl" folder
        if(is_file(SD_INCLUDE_PATH.'tmpl'.$ds.$tplName))
        {
          self::setTemplateDir(SD_INCLUDE_PATH.'tmpl'.$ds, $objSmarty);
          if($doReplacements)
          {
            $output = $objSmarty->fetch($tplName);
            $output = sd_DoSkinReplacements($output);
          }
          else
          {
            $objSmarty->display($tplName);
          }
          if(!self::$_use_v2)
          {
            Smarty::unmuteExpectedErrors(); // important!
          }
          return true;
        }
        // 2. check in "tmpl/defaults" folder
        else
        if(is_file(SD_INCLUDE_PATH.'tmpl'.$ds.'defaults'.$ds.$tplName))
        {
          self::setTemplateDir(SD_INCLUDE_PATH.'tmpl'.$ds.'defaults'.$ds, $objSmarty);
          $objSmarty->display($tplName);
          if(!self::$_use_v2)
          {
            Smarty::unmuteExpectedErrors(); // important!
          }
          return true;
        }
      }
      // so no template file found...
      return false;
    }
    catch (Exception $e)
    {
	  //nada!
      self::$_tmpl_err = $e->getMessage();
	  if(defined('DEBUG') && DEBUG)
	  {
		  echo $e->getMessage();
	  }
      return false;
    }

    if(!self::$_use_v2)
    {
      Smarty::unmuteExpectedErrors(); // important!
    }

  } //display


  public static function getNew($keepExisting=false,$tpl_path=null,$comp_path=null,$cache_path=null)
  {
    global $mainsettings, $sdlanguage, $sdurl, $userinfo, $user_language;

    self::_checkVersion(); //2012-08-11

    // Instantiate smarty object
    if(!class_exists('Smarty'))
    {
      // SD_SMARTY_PATH must be defined in "admin/prgm_info.php"!
      // e.g. "includes/smarty/" or "includes/smarty3/"
      defined('SD_SMARTY_PATH') || define('SD_SMARTY_PATH', 'includes/smarty3/');
      require_once(ROOT_PATH.SD_SMARTY_PATH.'Smarty.class.php');
    }
    $tmp = new Smarty();
    $ds = DIRECTORY_SEPARATOR;

    //SD342: register the resource name "sd"
    if(self::$_use_v2)
    {
      $tmp->template_dir = (empty($tpl_path) ? SD_INCLUDE_PATH.'tmpl' : (string)$tpl_path);
      $tmp->compile_dir  = (empty($comp_path) ? $tmp->template_dir.$ds.'comp' : (string)$comp_path);
      $tmp->cache_dir    = (empty($cache_path) ? $tmp->template_dir.$ds.'cache' : (string)$cache_path);
      // register "sd" resource to fetch template from SD's "templates" table
      $tmp->register_resource("sd",
        array("sd_get_template",
              "sd_get_timestamp",
              "sd_get_secure",
              "sd_get_trusted"));
    }
    else
    {
      // Assumes Smarty v3.1+
      Smarty::muteExpectedErrors(); // important!
      $tmp->setTemplateDir(empty($tpl_path) ? SD_INCLUDE_PATH.'tmpl' : (string)$tpl_path);
      $tmp->setCompileDir(empty($comp_path) ? $tmp->getTemplateDir(0).$ds.'comp' : (string)$comp_path);
      $tmp->setCacheDir(empty($cache_path) ? $tmp->getTemplateDir(0).$ds.'cache' : (string)$cache_path);
      // register mysql-based resource to fetch template from SD's "templates" table
      require_once(SD_INCLUDE_PATH.'classes'.$ds.'smarty.resource.mysql.php');
      $tmp->registerResource('mysql', new Smarty_Resource_Mysql());
    }

    // Default some common SD values
    $tmp->assign('AdminAccess',    !empty($userinfo['adminaccess']));
    $tmp->assign('SiteAdmin',      !empty($userinfo['adminaccess']));
    $tmp->assign('sdlanguage',     $sdlanguage);
    $tmp->assign('sdurl',          $sdurl);
    $tmp->assign('current_userid', $userinfo['userid']);
    $tmp->assign('logout_link',    '/'.$user_language.'/login?logout=1');
    $tmp->assign('admin_link',     $sdurl.ADMIN_PATH.'/index.php');
    $tmp->assign('can_admin',      !empty($userinfo['adminaccess']) ||
                                   !empty($userinfo['pluginadminids']) ||
                                   !empty($userinfo['custompluginadminids']));
    $tmp->assign('home_link',      RewriteLink('index.php?categoryid=1&logout=1'));
    $tmp->assign('register_link',  false);
    $tmp->assign('lostpwd_link',   false);
    $tmp->assign('SecurityToken',  PrintSecureToken());

    //SD343: provide date format
    $data = isset($userinfo['profile']) ? (array)$userinfo['profile'] : $userinfo;
    $tmp->assign('dateformat', ((isset($data['user_dateformat']) && strlen($data['user_dateformat'])) ? $data['user_dateformat'] : $mainsettings['dateformat']));

    if(defined('REGISTER_PATH') && strlen(REGISTER_PATH) && !empty($settings['display_register_link']))
    {
      $tmp->assign('register_link', REGISTER_PATH);
    }
    if(defined('LOSTPWD_PATH') && strlen(LOSTPWD_PATH) && !empty($settings['display_forgot_password_link']))
    {
      $tmp->assign('lostpwd_link', LOSTPWD_PATH);
    }

    // Must assign newly created object if desired:
    if(empty($keepExisting) || empty(self::$_smarty)) self::$_smarty = $tmp;
    return $tmp;

  } //getNew


  // ------------------------------------------------------------------------
  // DB-related functions for templates management (SD344)
  // ------------------------------------------------------------------------

  protected static function _InitCache()
  {
    global $DB;
    if(empty(self::$_tmpl_cache))
    {
      self::$_tmpl_cache = array();
      self::$_tmpl_cache_ids = array();
      if($getrows = $DB->query('SELECT t.*, ifnull(r.revision_id,0) real_revision_id'.
                               ' FROM '.PRGM_TABLE_PREFIX.'templates t'.
                               ' LEFT JOIN '.PRGM_TABLE_PREFIX.'revisions r ON r.revision_id = t.revision_id'.
                               ' ORDER BY t.tpl_name, t.pluginid'))
      {
        while($row = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
        {
          self::$_tmpl_cache[$row['pluginid']][$row['tpl_name']] = $row;
          self::$_tmpl_cache_ids[$row['pluginid']][$row['template_id']] = $row['tpl_name'];
        }
      }
    }
  } //_InitCache


  public static function GetTemplateContentFor($pluginid, $tplName, & $revision_id)
  {
    global $DB;
    if(empty($tplName) || !isset($pluginid) || ((int)$pluginid!=$pluginid) ||
       ($pluginid < 0) || ($pluginid > 999999))
    {
      return false;
    }
    // Check cache first
    self::_InitCache();
    if(!isset(self::$_tmpl_cache[$pluginid][$tplName]))
    {
      return false;
    }

    // Template exists, so return template content for revision ID
    if(!$revision_id = self::$_tmpl_cache[$pluginid][$tplName]['real_revision_id'])
    {
      return false;
    }

    // Fetch template content from revisions tbl
    if($tpl = $DB->query_first('SELECT r.content'.
                               ' FROM '.PRGM_TABLE_PREFIX.'revisions r'.
                               ' WHERE r.revision_id = '.(int)$revision_id))
    {
      return $tpl['content'];
    }
    return false;
  } //GetTemplateContentFor


  public static function GetTemplateNameByID($pluginid, $tplID) //SD360
  {
    if(empty($tplID) || !isset($pluginid) || ((int)$pluginid!=$pluginid) ||
       ($pluginid < 0) || ($pluginid > 999999))
    {
      return false;
    }
    self::_InitCache();
    if(!isset(self::$_tmpl_cache_ids[$pluginid][$tplID])) return false;
    // If it exists, return its name
    return self::$_tmpl_cache_ids[$pluginid][$tplID];
  } //GetTemplateNameByID


  public static function TemplateExistsInDB($pluginid, $tplName)
  {
    if(empty($tplName) || !isset($pluginid) || ((int)$pluginid!=$pluginid) ||
       ($pluginid < 0) || ($pluginid > 999999))
    {
	  return false;
    }
    self::_InitCache();
    if(!isset(self::$_tmpl_cache[$pluginid][$tplName])) return false;
    // If it exists, return the template_id
    return self::$_tmpl_cache[$pluginid][$tplName]['template_id'];
  } //TemplateExistsInDB


  public static function AddTemplateRevisionFromVar($pluginid, $tplName, $tplContent,
                                                    $tplDescription='', $makeCurrent=True)
  {
    global $DB, $userinfo;

    if(!isset($pluginid) || ((int)$pluginid!=$pluginid) ||
       ($pluginid < 0) || ($pluginid > 999999) ||
       empty($tplName) || !isset($tplContent))
    {
      return false;
    }

    // Check if template actually exists
    if(false === ($tpl_id = self::TemplateExistsInDB($pluginid, $tplName)))
    {
      return false;
    }

    // Insert a new revision row
    $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX.'revisions (pluginid,objectid,userid,datecreated,content,description)'.
               " VALUES (%d, %d, %d, %d, '%s', '%s')",
               $pluginid, $tpl_id,(!empty($userinfo['userid'])?$userinfo['userid']:0),
               TIME_NOW,
               (isset($tplContent) ? $DB->escape_string($tplContent):''),
               (isset($tplDescription) ? $DB->escape_string($tplDescription):''));
    if($rev_id = $DB->insert_id())
    {
      // Update template with new revision_id
      if(!empty($makeCurrent))
      {
        $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'templates SET revision_id = %d'.
                   ' WHERE pluginid = %d AND template_id = %d',
                   $rev_id, $pluginid, $tpl_id);
      }
      return $rev_id;
    }
    return false;
  } //AddTemplateRevisionFromVar


  public static function AddTemplateRevisionFromFile($pluginid, $tplPath, $tplName,
                                                     $tplDescription='', $makeCurrent=True)
  {
    if(empty($tplName) || empty($tplPath) || (strpos($tplName,'..') !== FALSE) ||
       (substr_count($tplPath,'../') > 2) || (substr_count($tplPath,"..\\") > 2))
    {
      return false;
    }

    // Do not allow remote files!
    $tplPath = trim($tplPath);
    if(preg_match('#^(https?|ftp|file)\:#i', $tplPath)) return false;

    // Fetch template content from file
    $tpl_content = '';
    if(file_exists($tplPath.$tplName) && is_file($tplPath.$tplName) && is_readable($tplPath.$tplName))
    {
      $tpl_content = @file_get_contents($tplPath.$tplName);
    }
    // If no content, make it empty string
    if(empty($tpl_content)) $tpl_content = '';

    return self::AddTemplateRevisionFromVar($pluginid, $tplName, $tpl_content,
                                            $tplDescription, $makeCurrent);
  } //AddTemplateRevisionFromFile


  public static function DeleteRevisionByID($revision_id)
  {
    global $DB;
    if(empty($revision_id) || ((int)$revision_id < 1)) return false;
    $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'revisions WHERE revision_id = %d', $revision_id);
    return ($DB->affected_rows() > 0);
  } //DeleteRevisionByID


  public static function DeleteCurrentTemplateRevision($pluginid, $objectid, $tplName)
  {
    global $DB;
    if(empty($tplName) || !is_string($tplName) ||
       !isset($objectid) || ($objectid < 0) || ($objectid > 999999999) ||
       !isset($pluginid) || ($pluginid < 0) || ($pluginid > 999999)) return false;

    $row = $DB->query_first('SELECT t.tpl_name, r.revision_id'.
                            ' FROM '.PRGM_TABLE_PREFIX.'templates t'.
                            ' LEFT JOIN '.PRGM_TABLE_PREFIX.'revisions r ON r.revision_id = t.revision_id'.
                            " WHERE t.pluginid = %d AND t.tpl_name = '%s'",
                            $pluginid, $DB->escape_string($tplName));
    if(!empty($row['revision_id']))
    {
      self::DeleteRevisionByID($row['revision_id']);
    }
    // Update template row with new revision_id (if exists)
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX.'templates SET revision_id ='.
               ' IFNULL(SELECT MAX(revision_id) FROM '.PRGM_TABLE_PREFIX.'revisions r WHERE r.pluginid = {templates}.pluginid AND r.objectid = {templates}.objectid,0)'.
               " WHERE pluginid = %d AND objectid = %d AND tpl_name = '%s'",
               $pluginid, $objectid, $DB->escape_string($tplName));
    return true;
  } //DeleteCurrentTemplateRevision


  public static function DeleteTemplatebyId($pluginid, $template_id)
  {
    global $DB;
    if(empty($template_id) || ($template_id < 1) || ($template_id > 999999999) ||
       !isset($pluginid) || ($pluginid < 0) || ($pluginid > 999999)) return false;

    if($row = $DB->query_first('SELECT t.template_id, t.pluginid, t.system_only, r.revision_id'.
                               ' FROM '.PRGM_TABLE_PREFIX.'templates t'.
                               ' LEFT JOIN '.PRGM_TABLE_PREFIX.'revisions r ON r.revision_id = t.revision_id'.
                               ' WHERE t.pluginid = %d AND t.template_id = %d',
                               $pluginid, $template_id))
    {
      if(!empty($row['system_only'])) return false;
      if(!empty($row['revision_id']))
      {
        self::DeleteRevisionByID($row['revision_id']);
      }
      $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'revisions WHERE pluginid = %d AND objectid = %d', $pluginid, $template_id);
      $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'templates WHERE template_id = %d', $template_id);
      return true;
    }
    return false;
  } //DeleteTemplatebyId


  public static function DeleteTemplatebyPlugin($pluginid)
  {
    global $DB, $plugin_names;
    if(!isset($pluginid) || ($pluginid < 1) || ($pluginid > 999999)) return false;
    if(!isset($plugin_names[$pluginid])) return false;

    $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'revisions WHERE pluginid = %d', $pluginid);
    $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'templates WHERE pluginid = %d', $pluginid);

    return true;
  } //DeleteTemplatebyPlugin


  public static function GetTemplateNamesForPlugin($pluginid, $activeOnly=true) //SD351
  {
    global $DB;
    $pluginid = empty($pluginid)?0:(int)$pluginid;
    if(($pluginid < 0) || ($pluginid > 999999)) return false;

    if($rows = $DB->query('SELECT t.template_id, t.tpl_name, t.displayname'.
                          ' FROM '.PRGM_TABLE_PREFIX.'templates t'.
                          ' WHERE t.pluginid = %d'.
                          (empty($activeOnly)?'':' AND t.is_active = 1').
                          ' ORDER BY t.tpl_name',
                          $pluginid))
    {
      $tmpls = $DB->fetch_array_all($rows, MYSQL_ASSOC);
      return (empty($tmpls)?false:$tmpls);
    }
    return false;
  } //GetTemplateNamesForPlugin


  public static function UpdateTmplRevisionByRevIdFromVar($revision_id, $tplContent)
  {
    global $DB;

    if(empty($revision_id) || ($revision_id < 1)  || ($revision_id > 999999999))
      return false;

    // Check if template with a revision actually exists
    $row = $DB->query_first('SELECT r.revision_id'.
                            ' FROM '.PRGM_TABLE_PREFIX.'revisions r'.
                            ' WHERE r.revision_id = %d', $revision_id);
    if(empty($row['revision_id'])) return false;

    // Update existing revision
    //SD344: "skip_curly" is true, so that $DB will not try to replace any curly brackets
    // within the whole SQL statement
    $DB->skip_curly = true;
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX."revisions SET content = '%s'".
               ' WHERE revision_id = %d',
               $DB->escape_string($tplContent), $revision_id);
    $DB->skip_curly = false;
    return true;
  } //UpdateTmplRevisionByRevIdFromVar


  public static function UpdateTmplRevisionByTmplIdFromVar($pluginid, $template_id, $tplContent,
                                                           $tplActive=1, $tplType='Frontpage')
  {
    global $DB;

    if(empty($template_id) || ($template_id < 1)  || ($template_id > 999999999) ||
       !isset($pluginid) || ($pluginid < 0) || ($pluginid > 999999))
      return false;

    // Check if template with a revision actually exists
    $row = $DB->query_first('SELECT t.template_id, IFNULL(r.revision_id,0) real_revision_id'.
                            ' FROM '.PRGM_TABLE_PREFIX.'templates t'.
                            ' INNER JOIN '.PRGM_TABLE_PREFIX.'revisions r ON r.revision_id = t.revision_id'.
                            ' WHERE t.pluginid = %d AND t.template_id = %d',
                            $pluginid, $template_id);
    if(empty($row['template_id']) || empty($row['real_revision_id'])) return false;

    //SD344: "skip_curly" is true, so that $DB will not try to replace any curly brackets
    // within the whole SQL statement containing the template code
    $DB->skip_curly = true;
    // Update existing revision
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX."revisions SET datecreated = %d, content = '%s'".
               ' WHERE revision_id = %d',
               TIME_NOW, $DB->escape_string($tplContent), $row['real_revision_id']);
    // Update existing template
    $DB->query('UPDATE '.PRGM_TABLE_PREFIX."templates SET dateupdated = %d, is_active = %d, tpl_type = '%s'".
               ' WHERE pluginid = %d AND template_id = %d',
               TIME_NOW, (empty($tplActive)?0:1), $DB->escape_string($tplType),
               $pluginid, $template_id);
    $DB->skip_curly = false;
    return true;
  } //UpdateTmplRevisionByTmplIdFromVar


  public static function CreateTemplateFromVar($pluginid, $tplName, $tplContent,
                                               $tplDisplayname, $tplDescription,
                                               $tplSysOnly, $tplType,
                                               $doReplace = false)
  {
    global $DB, $userinfo;
    if(empty($tplName) || empty($tplDisplayname) || (strpos($tplName,'..') !== FALSE))
      return false;

    if(empty($pluginid) || ((int)$pluginid != $pluginid) || ($pluginid < 0) || ($pluginid > 999999))
    {
      $pluginid = 0;
    }

    $doReplace = !empty($doReplace);
    // Check if template already exists in templates table
    if(false !== self::TemplateExistsInDB($pluginid,$tplName))
    {
      //SD360: added new option to replace existing revision content
      if(!$doReplace) return false;
      $rev_id = self::$_tmpl_cache[$pluginid][$tplName]['real_revision_id'];
      return self::UpdateTmplRevisionByRevIdFromVar($rev_id,$tplContent);
    }

    // Insert a new template row pointing to revision (=content)
    $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX.'templates (template_id,revision_id,userid,pluginid,tpl_name,displayname,description,is_active,datecreated,dateupdated,system_only,tpl_type)'.
      " VALUES(NULL, %d, %d, %d, '%s', '%s', '%s', 1, %d, %d, %d, '%s')",
      0, (!empty($userinfo['userid'])?$userinfo['userid']:0), $pluginid,
      $DB->escape_string($tplName),
      $DB->escape_string($tplDisplayname),
      $DB->escape_string($tplDescription),
      TIME_NOW, TIME_NOW, $tplSysOnly, $DB->escape_string($tplType));
    if($res = $DB->insert_id())
    {
      // Reset cache
      self::$_tmpl_cache = false;
      // Insert a new revision row
      self::AddTemplateRevisionFromVar($pluginid, $tplName, $tplContent, $tplDescription, true);
      return $res;
    }
    return false;
  } //CreateTemplateFromVar


  public static function CreateTemplateFromFile($pluginid, $tplPath, $tplName,
                                                $tplDisplayname, $tplDescription,
                                                $tplSysOnly=0, $tplType='Frontpage',
                                                $doReplace=false)
  {
    if(empty($tplName) || empty($tplPath) || (strpos($tplName,"..") !== FALSE))
      return false;

    $tplPath = str_replace('\\', '/', $tplPath);
    if(substr_count($tplPath,'../') > 3) return false;

    // Do not allow remote files!
    $tplPath = trim($tplPath);
    if(preg_match('#^(https?|ftp|file)\:#i', $tplPath)) return false;

    // Fetch template content from file
    $tpl_content = '';
    if(file_exists($tplPath.$tplName) && is_file(($tplPath.$tplName)))
    {
      $tpl_content = @file_get_contents($tplPath.$tplName);
      if($tpl_content === FALSE) $tpl_content = ''; // if not readable, default to empty
    }
    return self::CreateTemplateFromVar($pluginid, $tplName, $tpl_content,
                                       $tplDisplayname, $tplDescription,
                                       $tplSysOnly, $tplType,
                                       !empty($doReplace));
  } //CreateTemplateFromFile


} //SD_Smarty
} // DO NOT REMOVE
