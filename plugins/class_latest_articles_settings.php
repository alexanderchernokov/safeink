<?php
if(!defined('IN_PRGM')) return false;

class LatestArticlesSettings
{
  private $pluginid = 0;
  private $pluginfolder = '';
  private $settings = array();

  function LatestArticlesSettings($plugin_folder)
  {
    $this->pluginid = GetPluginIDbyFolder($plugin_folder);
    $this->pluginfolder = $plugin_folder;
    $this->settings = GetPluginSettings($this->pluginid);
  } //LatestArticlesSettings

  // ############################# INIT FUNCTION #############################

  function Init()
  {
    global $plugin, $refreshpage;

    $action  = GetVar('action',  '', 'string');
    $matchid = GetVar('matchid', 0,  'whole_number');
    switch($action)
    {
      case 'addmatch':
        $this->AddPageMatch();
      break;

      case 'insertmatch':
      case 'updatematch':
        $this->SavePageMatch($action, $matchid);
      break;

      case 'deletematch':
        $this->DeletePageMatch($matchid);
      break;

      case 'savematches':
        $this->SaveMatches();
      break;

      case 'settings':
        echo '&nbsp;<a style="font-size: 14px;" href="'.$refreshpage.'" target="_self">&laquo; '.$plugin['name'].'</a>';
        PrintPluginSettings($this->pluginid, 'latest_articles_settings', $refreshpage);
      break;

      default:
        $this->OutputStyles();
        $this->DisplayDefault();
    }

  } //Init

  function OutputStyles()
  {
    /*
    echo '
<script type="text/javascript">
//<![CDATA[
//]]>
</script>';
    */
  }

  // ############################# DISPLAY DEFAULT ###########################

  function DisplayDefault()
  {
    global $DB, $refreshpage;

    echo '<form method="post" action="'.$refreshpage.'">
            <input type="hidden" name="action" value="settings" />
            <button class="btn btn-info" type="submit" value="'.AdminPhrase('view_settings').'"><i class="ace-icon fa fa-cog"></i> '.AdminPhrase('view_settings').'</button>
          </form>
		  <br />
          
    ';

    $this->DisplayMatches();

  } //DisplayDefault


  // ########################## GET PAGES WITH PLUGIN ########################

  function GetMatchPages($sourceid, $pages_arr=array())
  {
    global $DB;
    $page_options = '';
    $getpages = $DB->query('SELECT DISTINCT c.categoryid, c.name FROM {categories} c
                            INNER JOIN {pagesort} ps ON ps.categoryid = c.categoryid
                            INNER JOIN {plugins} p ON p.pluginid = ps.pluginid
                            WHERE p.pluginid = %d
                            ORDER BY c.name, c.parentid, c.displayorder, c.categoryid',
                            $sourceid);
    while($p = $DB->fetch_array($getpages))
    {
      $page_options .= '<option value="'.$p['categoryid'].'" '.
                       (in_array($p['categoryid'],$pages_arr)?' selected="selected"':'').
                       '>'.$p['name'].'</option>';
    }
    return $page_options;
  } //GetMatchPages


  // ########################## ADD PAGE TO MATCHES ##########################

  function AddPageMatch()
  {
    global $DB, $refreshpage;

    $pageid   = GetVar('page_id', 0, 'whole_number');
    if(!isset($this->settings[$pageid]))
    {
      $DB->query("INSERT INTO {pluginsettings} (settingid,pluginid,groupname,title,description,input,value)
                 VALUES (null,%d,'page_matches',%d,'','text','0')",
                 $this->pluginid, $pageid);
    }

    RedirectPage($refreshpage);

  } //AddPageMatch


  // ########################## ADD PAGE TO MATCHES ##########################

  function DeletePageMatch($pageid, $redirect=true)
  {
    global $DB, $refreshpage;

    if(!empty($pageid))
    {
      $DB->query("DELETE FROM {pluginsettings} WHERE pluginid = %d AND groupname = 'page_matches' AND title = '%s'",
                 $this->pluginid, $pageid);
    }

    RedirectPage($refreshpage);

  } //AddPageMatch


  // ########################## SAVE DEFAULT MATCHES #########################

  function SaveMatches($redirect=true)
  {
    global $DB, $refreshpage;

    $pageid   = GetVar('pageid', '', 'string');
    $matches  = GetVar('match_option', 0, 'int');
    $selected = GetVar('page_matches_options', array(), 'array');
    if(($matches == '-2') && empty($selected))
    {
      $matches = 0;
    }

    if($matches > -2)
    {
      $new = ($matches == -1) ? '-1' : '0';
      $DB->query("UPDATE {pluginsettings} SET value = '%s' WHERE pluginid = %d AND title = '%s'",
                 $new, $this->pluginid, (empty($pageid)?'default_page_match':$pageid));
    }
    else
    {
      if(empty($selected))
      {
        $selected = array(0);
      }
      else
      {
        $selected[] = '-2';
      }
      @natsort($selected);
      $new = implode(',', $selected);
      $DB->query("UPDATE {pluginsettings} SET value = '%s' WHERE pluginid = %d AND title = '%s'",
                 $new, $this->pluginid, (empty($pageid)?'default_page_match':$pageid));
    }
    if(empty($redirect))
    {
      $this->PrintMatches($pageid,array_values(array_unique(array_merge(array($matches),$selected))));
    }
    else
    {
      RedirectPage($refreshpage);
    }

  } //SaveMatches


  // ################################ PRINT MATCHES ##########################

  function PrintMatches($pageid, $page_matches)
  {
    global $pages_md_arr;

    if(Is_Ajax_Request())
    {
      $GLOBALS['admin_phrases'] = LoadAdminPhrases(3, $this->pluginid);
    }
    if(is_array($page_matches))
    {
      $matches = & $page_matches;
    }
    else
    {
      $matches = sd_ConvertStrToArray($page_matches);
    }
    if(empty($matches) || in_array('0',$matches))
    {
      echo '<input type="hidden" name="match_option" value="0" />
      <a href="#" class="change-matches imgedit" rel="'.$pageid.'" onclick="return false;">&nbsp;<strong>'.AdminPhrase('default_match_all').'</strong></a>';
    }
    else
    if(!empty($matches) && in_array('-1',$matches))
    {
      echo '<input type="hidden" name="match_option" value="-1" />
      <a href="#" class="change-matches imgedit" rel="'.$pageid.'" onclick="return false;">&nbsp;<strong>'.AdminPhrase('default_match_current').'</strong></a>';
    }
    else
    if(!empty($matches))
    {
      $pages = array();
      foreach($matches as $mp)
      {
        if(isset($pages_md_arr[$mp])) $pages[$mp] = $pages_md_arr[$mp]['name'];
      }
      @natcasesort($pages);
      $page_li_items = $pids = '';
      foreach($pages as $pid => $pname)
      {
        if(strlen($page_li_items)) $page_li_items .=  ', ';
        $page_li_items .= $pname;
        $pids .= $pid.',';
      }

      echo '<input type="hidden" name="match_option" value="-2" />
      <input type="hidden" name="match_pages" value="'.$pids.'" />
      <a href="#" class="change-matches imgedit" rel="'.$pageid.'" onclick="return false;" id="p'.
      $pageid.'" title="'.htmlspecialchars(AdminPhrase('change_options')).'">&nbsp;<strong>'.
      AdminPhrase('default_custom_pages').'</strong></a> '.$page_li_items;
    }

  } //PrintMatches


  // ########################## DISPLAY PAGE MATCHES #########################

  function DisplayMatches()
  {
    global $DB, $plugin, $pages_md_arr, $refreshpage;

    $source = (int)$this->settings['article_plugin_selection'];

    // Build Default Page Match
    $default_arr    = sd_ConvertStrToArray($this->settings['default_page_match']);
    $custom_default = !empty($default_arr) && in_array(-2,$default_arr);
    $default_select = '<select id="page_matches_options" name="page_matches_options[]" multiple="multiple" size="8" class="form-control">'.
                      $this->GetMatchPages($source, $default_arr).
                      '</select>';
    $matches_select = '<select id="page_matches_options" name="page_matches_options[]" multiple="multiple" size="8" class="form-control">'.
                      $this->GetMatchPages($source).
                      '</select>';

    echo '<h2 class="header blue lighter">' . AdminPhrase('default_page_match') . '</h2>';
    echo '
	 <form action="'.$refreshpage.'" method="post" class="form-horizontal">
    <div class="form-group">
		<label class="col-sm-6 control-label">'.AdminPhrase('default_page_match_descr').'</label>
		<div class="col-sm-6">
       	<strong>'.AdminPhrase('default_match_hint').'</strong><br />
          <input type="radio" class="ace" value="0" name="match_option"'.(empty($default_arr) || in_array(0,$default_arr)?' checked="checked"':'').' /><span class="lbl"> '.AdminPhrase('default_match_all').'</span><br /><br />
          <input type="radio" class="ace" value="-1" name="match_option"'.(in_array(-1,$default_arr)?' checked="checked"':'').' /> <span class="lbl"> '.AdminPhrase('default_match_current').'</span><br /><br />
          <input type="radio" class="ace" value="-2" name="match_option"'.($custom_default?' checked="checked"':'').' /><span class="lbl"> '.AdminPhrase('default_custom_pages').'</span><br /><br />
          '.$default_select.'
          <input type="hidden" name="action" value="savematches" />
          <br /><br />
          <button class="btn btn-info" type="submit" value="'.AdminPhrase('save_default_match').'" /><i class="ace-icon fa fa-save"></i> '.AdminPhrase('save_default_match').'</button>
          </form>
        </div>
	</div>';
	
	echo '<h2 class="header blue lighter">' . AdminPhrase('page_matches') . '</h2>';
	echo '<form method="post" action="'.$refreshpage.'" class="form-horizontal">
			<div class="form-group">
				<label class="control-label col-sm-6">'.AdminPhrase('match_add_descr').'</label>
				<div class="col-sm-6">
            <select class="form-control" id="add_page_id" name="page_id">
            '.$this->GetMatchPages($this->pluginid).'
            </select>
		</div>
		</div>
		<div class="center">
			 <input type="hidden" name="action" value="addmatch" />
            <button type="submit" class="btn btn-info" value="'.AdminPhrase('match_add').'"><i class="ace-icon fa fa-plus"></i> '.AdminPhrase('match_add').'</button>
		</div>
          </form><br />';

    PrintSection(AdminPhrase('page_matches'));
    echo PrintSecureToken().'
	<img style="position: absolute; margin-left: -10000;left: -10000;" src="'.SD_INCLUDE_PATH.'css/images/overlay.png" width="16" height="16" />
    <table id="matches-list" class="table table-bordered table-striped">';

    $matchcount = 0;
    if($getmatches = $DB->query("SELECT c.name category_name, ps.title page_id, ps.value page_matches
                                FROM {pluginsettings} ps
                                LEFT JOIN {categories} c ON c.categoryid = ps.title
                                WHERE ps.pluginid = %d AND ps.groupname = 'page_matches'
                                ORDER BY c.name", (int)$this->pluginid))
    {
      $matchcount = $DB->get_num_rows($getmatches);
    }

    if($matchcount)
    {
      echo '
	  <thead>
          <tr>
            <th class="tdrow1">'.AdminPhrase('column1_title').'</th>
            <th class="tdrow1">'.AdminPhrase('column2_title').'</th>
          </tr>
		  </thead>';
      while($m = $DB->fetch_array($getmatches))
      {
        if(isset($pages_md_arr[$m['page_id']]))
        {
          echo '
          <tr>
            <td class="tdrow2" width="30%" style="padding: 6px">
              <span class="pull-right"> <a href="'.$refreshpage.'&action=deletematch&matchid='.$m['page_id'].'" title="'.AdminPhrase('match_delete').'" class="match_delete" onclick="return false;">
             <i class="ace-icon fa fa-trash-o red bigger-120"></i></a></span>
              <div style="display: inline; margin: 4px 8px 4px 4px;">
              <strong>'.$m['category_name'].'</strong>
              </div>
            </td>
            <td align="left" class="tdrow2" style="border-right: none;" width="70%">
            ';

          $this->PrintMatches($m['page_id'], $m['page_matches']);

          echo '
            </td>
          </tr>';
        }

      } //while
    }
    else
    {
      echo '<tr><td class="tdrow2" colspan="2"><strong>'.AdminPhrase('no_matches_found').'</strong></td></tr>';
    }

    echo '
    </table>';

    EndSection();

    // Dialog content:
    echo '
    <div id="changepageoptions" style="display: none;">
    <form action="'.ROOT_PATH.'plugins/'.$this->pluginfolder.'/latest_articles_settings.php" id="changepageoptions_form" method="post">
      '.PrintSecureToken().'
      <input type="hidden" name="action" value="savepageoptions" />
      <input type="hidden" name="pageid" value="0" />
      <div style="border: 1px solid #222; clear: both; padding: 8px; margin: 4px; line-height: 18px; text-align: left;">
        <input type="radio" id="changeoption_all" value="0" name="match_option" checked="checked" />'.AdminPhrase('default_match_all').'<br />
        <input type="radio" id="changeoption_current" value="-1" name="match_option" />'.AdminPhrase('default_match_current').'<br />
        <input type="radio" id="changeoption_selected" value="-2" name="match_option" />'.AdminPhrase('default_custom_pages').'<br />
        '.$matches_select.'
        <br />
        <input type="submit" value="'.AdminPhrase('save_matches').'" />
      </div>
    </form>
    </div>';
  } //DisplayMatches

} //end of class
