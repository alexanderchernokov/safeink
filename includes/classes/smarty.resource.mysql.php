<?php
/**
 * MySQL Resource
 *
 * Resource Implementation based on the Custom API to use
 * MySQL as the storage resource for Smarty's templates and configs.
 *
 */
# Adapted for use within SD 3.4.4+; uses global SD DB object $DB and SD_Smarty class!
class Smarty_Resource_Mysql extends Smarty_Resource_Custom {

  public function __construct()
  {
    // not much to do here
  }

  private function fetchTpl($name)
  {
    global $DB;
    //SD350: "@" is used to make tpl unique across cloned plugins
    $pluginid = SD_Smarty::getCurrentPluginID();
    if(false !== ($sep_pos = strpos($name,'@')))
    {
      list($name,$pluginid) = explode('@',$name);
      $pluginid = Is_Valid_Number($pluginid,0,1,9999999);
    }

    // If name has ".tpl" extention, then search via "tpl_name",
    // else via "displayname"
    $column = 'displayname';
    if(substr($name,strlen($name)-4)=='.tpl') $column = 'tpl_name';

    $DB->result_type = MYSQL_ASSOC;
    $row = $DB->query_first('SELECT r.revision_id, r.content, t.dateupdated, r.datecreated'.
                            ' FROM {revisions} r'.
                            ' INNER JOIN {templates} t ON t.revision_id = r.revision_id'.
                            " WHERE t.pluginid = %d AND t.$column = '%s' AND t.is_active = 1 LIMIT 1",
                            $pluginid, $DB->escape_string($name));
    $DB->result_type = MYSQL_BOTH;
    return $row;
  }

  /**
   * Fetch a template and its modification time from database
   *
   * @param string $name template name
   * @param string $source template source
   * @param integer $mtime template modification timestamp (epoch)
   * @return void
   */
  protected function fetch($name, &$source, &$mtime)
  {
    $source = null;
    $mtime = null;
    if(empty($name)) return;
    $row = $this->fetchTpl($name);
    if(!empty($row['revision_id']))
    {
      $source = $row['content'];
      $mtime = !empty($row['datecreated']) ? (int)$row['datecreated'] : (int)$row['dateupdated'];
    }
    unset($row);
  }
}
