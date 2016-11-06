<?php
if(!class_exists('SD_Tags'))
{
class SD_Tags
{
  public static $plugins       = array();
  public static $classname     = 'tagcloud-style';
  public static $gradation     = 6;
  public static $maxentries    = 30;
  public static $tag_as_param  = false;
  public static $tag_order     = 0;
  public static $tags_title    = '';
  public static $targetpageid  = 0;

  // default values for tagcloud plugin:
  public static $html_container_start = '<div class="tagcloud"><strong>[tags_title]</strong><br />';
  public static $html_container_end   = '</div>';
  public static $html_tag_template    = '<a class="[tagclass]" href="[taglink]">[tagname]</a>';
  public static $html_tag_separator   = ', ';

  protected function __construct()
  {
  }

  // ##########################################################################

  public static function GetPluginTags($pluginid, $objectid, $tagtype=0, $ref_id=-1,
                                       $allowed_id=-1, $returnGroups=false)
  {
    $tags = array();
    $tmp = '';
    $pluginid = empty($pluginid) ? 0 : Is_Valid_Number($pluginid,0,2,99999);
    $objectid = empty($objectid) ? 0 : Is_Valid_Number($objectid,0,1,999999999);
    $tagtype  = empty($tagtype)  ? 0 : is_numeric($tagtype) ? Is_Valid_Number($tagtype,-1,0,999999999) :
                (is_array($tagtype) ? $tagtype : 0);

    if(is_array($tagtype))
    {
      $tt = array();
      foreach($tagtype as $entry)
      {
        $entry = Is_Valid_Number($entry,0,0,999);
        if(!in_array($entry, $tt)) $tt[] = $entry;
      }
      if(!empty($tt))
      {
        if(in_array(1,$tt))
          $tmp .= ' AND ((tagtype = 1 AND pluginid = 0) OR (tagtype IN ('.implode(',',$tagtype).')))';
        else
          $tmp .= ' AND tagtype IN ('.implode(',',$tagtype).')';
      }
    }
    else
    if(isset($tagtype) && (($tagtype = Is_Valid_Number($tagtype,-1,0,999)) >= 0))
    {
      $tmp .= ' AND tagtype = '.(int)$tagtype;
      if(!empty($pluginid) && ($tagtype==2))
      {
        $tmp .= ' AND pluginid IN (0,'.(int)$pluginid.')';
        if($objectid) $tmp .= ' AND objectid = '.(int)$objectid;
      }
    }
    if($pluginid && (strpos($tmp, 'AND pluginid')===false))
    {
      $tmp .= ' AND pluginid = '.(int)$pluginid;
      if($objectid) $tmp .= ' AND objectid = '.(int)$objectid;
    }


    if(isset($ref_id) && ((int)$ref_id == $ref_id) &&
       (($ref_id = Is_Valid_Number($ref_id,-1,0,99999999))>=0))
    {
      $tmp .= ' AND tag_ref_id = '.(int)$ref_id;
    }

    if(!empty($allowed_id) && ((int)$allowed_id == $allowed_id) &&
       (($allowed_id = Is_Valid_Number($allowed_id,-1,0,99999999)) >= 0))
    {
      $tmp .= " AND ((IFNULL(allowed_object_ids,'')='') OR (allowed_object_ids LIKE '%|".$allowed_id."|%'))";
    }

    global $DB;
    $DB->ignore_error = true;
    $gettags = $DB->query('SELECT tag, tagid, allowed_groups FROM {tags} WHERE censored = 0 '.
                          $tmp.
                          ' ORDER BY tag ASC');
    $DB->ignore_error = false;

    if(!empty($gettags))
    {
      while($tag = $DB->fetch_array($gettags,null,MYSQL_ASSOC))
      {
        if(trim($tag['tag']))
        {
          if(empty($returnGroups))
            $tags[$tag['tagid']] = $tag['tag'];
          else
            $tags[$tag['tagid']] = array('tag' => $tag['tag'], 'groups' => $tag['allowed_groups']);
        }
      }
    }
    return $tags;

  } //GetPluginTags

  // ##########################################################################

  public static function GetPluginTagsAsSelect($tconf)
  {
    //SD343: display all available tags
    /*
    Example:
      $tconf = array(
        'elem_name'   => 'prefix_id',
        'elem_size'   => 1,
        'elem_zero'   => 1,
        'chk_ugroups' => !$this->conf->IsSiteAdmin,
        'pluginid'    => $this->conf->plugin_id,
        'objectid'    => 0,
        'tagtype'     => 2,
        'ref_id'      => 0,
        'allowed_id'  => $this->conf->forum_id,
        'selected_id' => $prefix_master,
        'names_only'  => false                  #SD370
      );
    */
    if(empty($tconf) || !is_array($tconf)) return '';

    global $DB, $userinfo;

    $res = '';
    $elem_name   = isset($tconf['elem_name'])  ? (string)$tconf['elem_name'] : '';
    $elem_size   = isset($tconf['elem_size'])  ? (int)$tconf['elem_size'] : 1;
    $chk_ugroups = !empty($tconf['chk_ugroups']);
    $names_only  = isset($tconf['names_only']) ? !empty($tconf['names_only']) : false;
    $selected_id = isset($tconf['selected_id']) ? (string)$tconf['selected_id'] : false;

    $available_tags = self::GetPluginTags($tconf['pluginid'], $tconf['objectid'], $tconf['tagtype'],
                                          $tconf['ref_id'], $tconf['allowed_id'], $chk_ugroups);

    if(empty($available_tags) || !is_array($available_tags)) return '';
    if($names_only)
    {
      $available_tags = array_unique(array_values($available_tags));
    }

    $sel = '<select '.
           ($elem_name ? 'name="'.$elem_name.'" ' : '').
           ($elem_size > 1 ? 'size="'.$elem_size.'" ' : '').
           '>';
    if(!empty($tconf['elem_zero']))
    {
      $sel .= '<option value="0"'.($selected_id===false?' selected="selected"':'').'>---</option>';
    }
    foreach($available_tags as $key => $entry)
    {
      if(!empty($tconf['chk_ugroups']) && isset($entry['groups']))
      {
        $allowed = sd_ConvertStrToArray($entry['groups'],'|');
        if(!empty($allowed))
        {
          if(!array_intersect($allowed,$userinfo['usergroupids']))
          {
            continue;
          }
        }
      }
      if($names_only || !$chk_ugroups)
        $res .= '<option value="'.$entry.'"'.
                (!empty($selected_id)&&($selected_id==$entry)?' selected="selected"':'').'>'.
                $entry.'</option>';
      else
        $res .= '<option value="'.$key.'"'.
                (!empty($selected_id)&&($selected_id==$key)?' selected="selected"':'').'>'.$entry['tag'].'</option>';
    }
    if(empty($res)) return '';

    return $sel . $res . '</select>';

  } //GetPluginTagsAsSelect

  // ##########################################################################

  public static function GetPluginTagsAsArray($tconf)
  {
    //SD343: display all available tags
    /*
    Example:
      $tconf = array(
        'chk_ugroups' => !$this->conf->IsSiteAdmin,
        'pluginid'    => $this->conf->plugin_id, # optional
        'objectid'    => 0,                      # optional
        'tagtype'     => 2,
        'ref_id'      => 0, // or -1
        'allowed_id'  => $this->conf->forum_id, // or -1
      );
    */
    if(empty($tconf) || !is_array($tconf)) return false;

    global $DB, $userinfo;

    $res = array();
    $available_tags = self::GetPluginTags($tconf['pluginid'], $tconf['objectid'], $tconf['tagtype'],
                                          $tconf['ref_id'], $tconf['allowed_id'], true);

    if(!empty($available_tags) && is_array($available_tags))
    {
      foreach($available_tags as $key => $entry)
      {
        if(!empty($tconf['chk_ugroups']) && isset($entry['groups']))
        {
          $allowed = sd_ConvertStrToArray($entry['groups'],'|');
          if(empty($userinfo['adminaccess']) && !empty($allowed))
          {
            if(!array_intersect($allowed,$userinfo['usergroupids']))
            {
              continue;
            }
          }
        }
        $res[$key] = $entry['tag'];
      }
    }
    if(empty($res)) return false;
    return $res;

  } //GetPluginTagsAsArray

  // ##########################################################################

  /**
  * Removes tags for a specific plugin item.
  * If tagtype is not given (or -1), then ALL tags for the item are removed.
  * @param int $pluginid  ID of the plugin (required)
  * @param int $objectid  ID of the plugin item (required)
  * @param mixed $tagtype If >= 0 then only specific tag types are removed
  * @return null
  */
  public static function RemovePluginObjectTags($pluginid, $objectid, $tagtype=-1)
  {
    global $DB;

    // Sanity checks
    if( !is_numeric($pluginid) || !isset($objectid) || ((int)$objectid != $objectid) ||
        ($pluginid < 1) || ($pluginid > 99999) ||
        ($objectid < 1) || ($objectid > 99999999) )
    {
      return false;
    }
    $tagtype = Is_Valid_Number($tagtype,-1,0,999);

    $DB->query('DELETE FROM {tags} WHERE pluginid = %d AND objectid = %d'.
               ($tagtype >= 0 ? ' AND tagtype = '.(int)$tagtype : ''),
               $pluginid, $objectid);

  } //RemovePluginObjectTags

  // ##########################################################################

  /**
  * Store a comma-separated list $tags for a plugin ($objectid empty) or for a
  * specific plugin item ($ojbectid provided).  Removes tags for a specific plugin item.
  * If tagtype is not given (or -1), then ALL tags for the item are removed.
  * @param int $pluginid  ID of the plugin (required)
  * @param int $objectid  ID of the plugin item (required)
  * @param string $tags   List of tags, comma separated if multiple
  * @param int $tagtype   0,1,2 are predefined, higher values are plugin-dependent
  * @param int $ref_id    Link by ID to an existing admin-maintained tag
  * @param bool  $applyCensorWords Should tags be checked against censored tags?
  * @return bool True if at least one tag was stored, False if no tags were stored or wrong params passed
  */
  public static function StorePluginTags($pluginid, $objectid, $tags, $tagtype=0,
                                         $ref_id=0, $applyCensorWords=false)
  {
    global $DB;
    // Sanity checks
    if(!is_numeric($pluginid) || !isset($objectid) || ((int)$objectid != $objectid) ||
       ($pluginid < 1) || ($pluginid > 99999) ||
       ($objectid < 1) || ($objectid > 99999999) || !isset($tags))
    {
      return false;
    }
    if(($tagtype = Is_Valid_Number($tagtype,-1,0,999)) < 0) return false;

    //SD370: delete moved to RemovePluginObjectTags()
    self::RemovePluginObjectTags($pluginid, $objectid, $tagtype);
    if(!strlen(trim($tags))) return false;

    $ref_id = Is_Valid_Number($ref_id,0,0,99999999);
    $org_ref_id = $ref_id; //SD370: store original
    $tags = preg_replace('/[\s\s+]/',' ',$tags); // make only single spaces
    $new_tags = preg_split('/[,]/', $tags, -1, PREG_SPLIT_NO_EMPTY); // split by comma
    $new_tags = array_unique($new_tags); // unique tags only
    $added = 0;
    foreach($new_tags as $key => $value)
    {
      $value = SanitizeInputForSQLSearch(substr(trim(strip_tags($value)),0,100),false);
      if(!DetectXSSinjection($value) && (strlen($value)>1))
      {
        if(empty($tagtype))
        {
          $getrefs = $DB->query("SELECT * FROM {tags} WHERE (tag = '%s') ".
                     " AND pluginid IN (0,%d) AND (tagtype > 0) AND (IFNULL(tag_ref_id,0) = 0)".
                     " ORDER BY pluginid, tagtype DESC",
                     $value, $pluginid);
          while($ref = $DB->fetch_array($getrefs,null,MYSQL_ASSOC))
          {
            // Prevent users from adding censored tags
            $value = sd_removeBadWords($value);
            if(!defined('IN_ADMIN') && !empty($ref['censored'])) $value = '';
            $ref_id = $ref['tagid'];
            // If no value left or reference tag found, exit the loop
            if(empty($value) || !empty($ref_id)) break;
          } //while
        }

        if(!empty($applyCensorWords)) //SD343
        {
          $tmp = sd_removeBadWords($value);
          if($value != $tmp) $value = '';
        }

        if(!empty($value))
        {
          $DB->query('INSERT INTO {tags} (pluginid, objectid, tagtype, tag,  datecreated, tag_ref_id)'.
                     " VALUES(%d, %d, %d, '%s', %d, %d)",
                     $pluginid, $objectid, $tagtype, $value, TIME_NOW, $ref_id);
          $added++;

          //SD370: for "0" tagtype the "ref_id" must be reset now
          if(empty($tagtype)) $ref_id = $org_ref_id;
        }
        if($added > 99) break; // limit!
      }
    }
    return true;

  } //StorePluginTags

  // ##########################################################################

  public static function DisplayCloud($doDisplay=true, $tagslist=null)
  {
    if(empty(self::$plugins)) return false;

    global $DB, $sdlanguage, $mainsettings_tag_results_page, $mainsettings_url_extension,
           $mainsettings_modrewrite;

    $result = '';

    //SD370: allow passing of tags list (e.g. by DLM)
    if(!empty($tagslist) && is_array($tagslist))
    {
      $tags = $tagslist;
    }
    else
    {
      $plugins_where = '';
      if(is_array(self::$plugins))
      {
        $tmp = array();
        foreach(self::$plugins as $key => $val)
        {
          if(is_numeric($val) && Is_Valid_Number($val,0,2,99999999))
          {
            $tmp[] = (int)$val;
          }
        }
        if(count($tmp)) $plugins_where = ' AND pluginid IN ('.implode(',', $tmp).')';
      }
      else
      if(is_numeric(self::$plugins))
      {
        $plugins_where = ' AND pluginid = '.(int)self::$plugins;
      }

      if(empty(self::$targetpageid)) self::$targetpageid = $mainsettings_tag_results_page;

      $tags = array();
      if($gettags = $DB->query('SELECT LOWER(tag) tag FROM {tags} WHERE LENGTH(tag) > 1 '.
                               ' AND tagtype = 0 AND objectid > 0'.
                               ' AND censored = 0'.$plugins_where))
      {
        while($row = $DB->fetch_array($gettags,null,MYSQL_ASSOC))
        {
          $tags[] = rtrim(ltrim($row['tag']));
        }
      }

      // Determine the number of occurences per tag:
      $tags = array_count_values($tags);
    }
    if(empty($tags) || !is_array($tags)) return false; //SD370: afterwards

    // Sort by # and only keep the first $maxentries:
    arsort($tags);

    self::$maxentries = Is_Valid_Number(self::$maxentries, 30, 4, 999);
    $tags = array_chunk($tags, self::$maxentries, true);
    $tags = $tags[0];

    // Scale logarithmitically the count of each tag:
    foreach ($tags as $tagkey => $count)
    {
      $tags[$tagkey] = round($count = 100 * log($count + 2)); // for font-size
    }

    // Determine highest/lowest occurences:
    $values = array_values($tags);
    $min    = min($values);
    $max    = max($values);
    unset($values);

    // Sort tags alphabetically (display order):
    self::$tag_order = empty(self::$tag_order) ? 0 : Is_Valid_Number(self::$tag_order, 0,0,4);
    if(self::$tag_order==0)
      @ksort($tags);
    else
    if(self::$tag_order==1)
      @krsort($tags);

    // Calculate for each font-class the minimal boundary:
    self::$gradation = empty(self::$gradation) ? 6 : (int)self::$gradation;
    $diff  = $max - $min;
    $delta = $diff / self::$gradation;
    for ($i = 1; $i <= self::$gradation; $i++)
    {
      $thresh[$i] = round($min + $i * $delta);
    }

    $prefix = self::$html_container_start;
    $prefix = str_replace('[tags_title]', self::$tags_title, $prefix);
    $suffix = self::$html_container_end;

    $quoted_url_ext = preg_quote($mainsettings_url_extension,'#');
    // Create cloud entries
    foreach($tags as $tag => $count)
    {
      $tagstyle = '';
      // Determine the tag's font style:
      for($i = self::$gradation; (empty($tagstyle) && ($i > 0)); $i--)
      {
        if($count > $thresh[$i])
        {
          $tagstyle = self::$classname.$i;
        }
      }
      $tag = rtrim(ltrim($tag));
      if(strlen($tag))
      {
        $tagstyle = empty($tagstyle) ? self::$classname.'1' : $tagstyle;
        //SD370: replace ampersand, then urlencode
        $enc = urlencode(str_replace('&amp;','&', $tag));
        if(empty(self::$tag_as_param))
        {
          $tag_link = RewriteLink('index.php?categoryid='.(int)self::$targetpageid);
          $tag_link = preg_replace('#'.$quoted_url_ext.'$#','/'.$enc, $tag_link);
        }
        else
        {
          $tag_link = RewriteLink('index.php?categoryid='.(int)self::$targetpageid.'&'.self::$tag_as_param.'='.$enc);
        }
        #e.g. <a class="[tagclass]" href="[taglink]">[tagname]</a>
        $result .= str_replace(array('[tagclass]','[taglink]','[tagname]'),
                               array($tagstyle, $tag_link, $tag), self::$html_tag_template);
        $result .= self::$html_tag_separator;
      }
    }

    // Remove last occurence of separator from output
    if(strlen($result) && strlen(self::$html_tag_separator))
    {
      $result = rtrim($result,self::$html_tag_separator);
    }

    if(empty($doDisplay))
    {
      if(!strlen($result))
        return '';
      else
        return $prefix . $result . $suffix;
    }
    else
    {
      if(strlen($result)) echo $prefix . $result . $suffix;
    }
  } //DisplayCloud

} //END OF CLASS
} //DO NOT REMOVE
