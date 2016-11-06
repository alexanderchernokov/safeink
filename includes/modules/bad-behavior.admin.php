<?php
// +-------------------------------------------------+
// |  Copyright (c) 2008, 2011, 2014 131 Studios     |
// |  http://www.subdreamer.com                      |
// |  This file may not be redistributed.            |
// +-------------------------------------------------+

if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

global $userinfo;
if(empty($userinfo['adminaccess']) || empty($userinfo['loggedin']) || !isset($sd_modules))
{
  DisplayMessage(array($sdlanguage['no_view_access']),true);
  $DB->close();
  exit();
}

define('BB2_URI', 'settings.php?display_type=modules');

require(SD_MODULES_PATH . 'bad-behavior.inc.php');
require(SD_MODULES_PATH . 'bad-behavior.php');

// ############################# DISPLAY LOG TABLE #############################

function CalculatePaging($rowCount, $pagestart, $pagesize)
{
  # This variable will hold all the paging information
  $arrPaging = array();

  # Calculate total pages
  $pages = intval($rowCount/$pagesize);

  # If the current page is more than the number of records
  # send back to page 1
  if($pagestart > $rowCount)
  {
    $pagestart = 0;
  }

  # If the result is a REAL number and not an INTEGER, we'll add an extra page to the total pages value
  if ($rowCount%$pagesize)
  {
    $pages++;
  }

  # What page are we on?
  $current = ($pagestart/$pagesize) + 1;

  # If the results are displayed on only one page
  if (($pages < 1) || ($pages == 0))
  {
    $pages = 1;
  }

  # Grabbing the first page's value for the links
  $first = $pagestart + 1;

  # Making sure there's more than one page and that we're not on the last page
  if (!((($pagestart + $pagesize) / $pagesize) >= $pages) && $pages != 1)
  {
    $last = $pagestart + $pagesize;
  }
  else
  {
    $last = $rowCount;
  }

  $arrPaging['rowcount']    = $rowCount;
  $arrPaging['pagesize']    = $pagesize;
  $arrPaging['pagestart']   = $pagestart;
  $arrPaging['totalpages']  = $pages;
  $arrPaging['currentpage'] = $current;
  $arrPaging['firstrow']    = $first;
  $arrPaging['lastrow']     = $last;

  return $arrPaging;
}


function PagingUrl($sort, $order, $pagestart, $pagesize, $search, $mode='')
{
  $url = BB2_URI.'&amp;sort='.urlencode($sort);

  if($order == 'asc' || $order == 'desc')
  {
    $url .= '&amp;order='.urlencode($order);
  }

  if(!empty($pagestart))
  {
    $url .= '&amp;pagestart='.(int)$pagestart;
  }

  $url .= '&amp;pagesize='.(int)$pagesize;

  if(!empty($search))
  {
    $url .= "&amp;search=".urlencode($search);
  }

  if(!empty($mode) && ($mode=='distinct'))
  {
    $url .= "&amp;mode=distinct";
  }

  return $url;
}


function DisplayPaging($arrPaging, $sort, $order, $search, $mode='')
{
  if($arrPaging['totalpages'] <= 1) return;

  echo '&nbsp;&nbsp;|&nbsp;&nbsp;';

  # If the user isn't on the first page (0) we'll put a "Back" link which will lead to the previous page
  if(!empty($arrPaging['pagestart']))
  {
    $back_page = $arrPaging['pagestart'] - $arrPaging['pagesize'];

    # The links for ID sort and usenet post date sort are different.
    # If the sort type is ID (by DB creation date) we'll show a regular link
    echo "<a href='" . PagingUrl($sort, $order, 0, $arrPaging['pagesize'], $search, $mode) .
    "'>&laquo;</a>&nbsp;<a href='" .
    PagingUrl($sort, $order, $back_page, $arrPaging['pagesize'], $search, $mode) .
    "'>&lt;</a>&nbsp;&nbsp;";
  }

  # Displaying links to all the pages using a loop
  $thispage = $arrPaging['currentpage'];
  $lim      = 10;          // Maximum links to show
  $mid      = ($lim / 2);  // Middle Marker

  if ($arrPaging['currentpage'] > $mid)
  {
    $thispage = $arrPaging['currentpage'] - $mid;
  }
  else
  {
    $thispage = 1;
  }

  if (($arrPaging['currentpage'] > $arrPaging['totalpages'] - $mid) &&
      ($arrPaging['totalpages'] > $lim))
  {
    $thispage = $arrPaging['totalpages'] - $lim;
  }

  if ($arrPaging['totalpages'] <= $lim)
  {
    $lim      = $arrPaging['totalpages'] - 1;
    $thispage = 1;
  }

  for ($i = $thispage; $i <= $thispage + $lim; $i++)
  {
    $ppage = $arrPaging['pagesize']*($i - 1);

    # We don't want to display a link to the current page, so we'll just show the current page in bold
    if ($ppage == $arrPaging['pagestart'])
    {
      echo("<strong>$i</strong>&nbsp;");
    }
    else # If the page number we're displaying isn't the page the user is on we'll link to it
    {
      # Again, the links for ID sort and usenet post date sort are different.
      echo "<a href='" . PagingUrl($sort, $order, $ppage, $arrPaging['pagesize'], $search, $mode) . "'>$i</a>&nbsp;";
    }
  }

  # If the total number of the pages isn't 1 and if we're not on the last page we'll display a "Next" link
  if (!((($arrPaging['pagestart'] + $arrPaging['pagesize']) / $arrPaging['pagesize']) >= $arrPaging['totalpages']) && $arrPaging['totalpages'] != 1)
  {
    $next_page = $arrPaging['pagestart'] + $arrPaging['pagesize'];
    # Again, the links for ID sort and usenet post date sort are different.
    echo "&nbsp;<a href='" . PagingUrl($sort, $order, $next_page, $arrPaging['pagesize'], $search, $mode) .
    "'>&gt;</a>&nbsp;<a href='" . PagingUrl($sort, $order, ($arrPaging['totalpages']-1)*$arrPaging['pagesize'], $arrPaging['pagesize'], $search, $mode) . "'>&raquo;</a>";
  }
}


// #############################################################################

function DisplayBB2Log($mode='',$info='')
{
  global $DB, $sd_modules;

  if(false === ($bb2_logtable = $sd_modules->GetSetting(MODULE_BAD_BEHAVIOR, 'log_table')))
  {
    return;
  }
  
  
  $page = GetVar('page', 1, 'int');
  $pagesize  = Is_Valid_Number(GetVar('pagesize', 50, 'whole_number'),50,10,9999);
  $limit = ($page-1)* $pagesize;
  $pagestart = Is_Valid_Number(GetVar('pagestart', 0, 'natural_number'),0,0,99999);
  $sort      = strtolower(GetVar('sort', ($mode == 'distinct'?'ip':'id'), 'string'));
  if(!in_array($sort, array('date','id','ip','key','request_entity','request_method','request_uri'))) $sort = 'id';
  $order     = strtolower(GetVar('order', 'desc', 'string'));
  if(!in_array($order, array('asc','desc'))) $order = 'desc';
  $search    = GetVar('search', '', 'string');
  $pagination_target = 'settings.php?display_type=modules&sort='.$sort.'&order='.$order.'&pagesize='.$pagesize;
  
  if(isset($_POST['search']))
    // If a new search was just submitted, start at first page
    $pagestart = 0;

  // ############# PREPARE PAGING #################
  $DB->result_type = MYSQL_ASSOC;
  $DB->ignore_error = true;
  if($mode == 'distinct')
  {
    $row_cnt = $DB->query_first('SELECT COUNT(DISTINCT `ip`) rcount FROM '.$bb2_logtable);
  }
  elseif(empty($search))
  {
    $row_cnt = $DB->query_first('SELECT COUNT(*) rcount FROM '.$bb2_logtable);
  }
  else
  {
    $tmp = '%' . $search . '%';
    $row_cnt = $DB->query_first("SELECT COUNT(*) rcount FROM $bb2_logtable
      WHERE (`request_uri` LIKE '%s') OR (`request_entity` LIKE '%s') OR (`ip` LIKE '%s')",
      $tmp, $tmp, $tmp);
  }
  $row_cnt = $row_cnt['rcount'];
  $paging = CalculatePaging($row_cnt, $pagestart, $pagesize);

  if($mode == 'distinct')
  {
    $tmp = ($sort=='ip') ? 'INET_ATON(b.ip)' : '`'.$sort.'`'; //SD343
    $getsyslogs = $DB->query('SELECT `ip`, `key`, `request_uri`,'.
                             ' (SELECT MAX(b2.date) FROM '.$bb2_logtable.' b2 WHERE INET_ATON(b2.ip) = INET_ATON(b.ip)) date'.
                             ' FROM '.$bb2_logtable.' b'.
                             ' GROUP BY INET_ATON(b.ip), `key`'.
                             ' ORDER BY %s %s LIMIT %d, %d',
                             $tmp, $order, $limit, $pagesize);
  }
  elseif(empty($search))
  {
    $tmp = ($sort=='ip') ? 'INET_ATON(`ip`)' : '`'.$sort.'`'; //SD343
    $getsyslogs = $DB->query("SELECT * FROM $bb2_logtable ORDER BY %s %s LIMIT %d, %d",
                             $tmp, $order, $limit, $pagesize);
  }
  else
  {
    $tmp = '%' . $search . '%';
    $tmp2 = ($sort=='ip') ? 'INET_ATON(`ip`)' : '`'.$sort.'`'; //SD343
    $getsyslogs = $DB->query("SELECT * FROM $bb2_logtable
      WHERE (`request_uri` LIKE '%s') OR (`request_entity` LIKE '%s') OR (`ip` LIKE '%s')
      ORDER BY %s %s LIMIT %d, %d",
      $tmp, $tmp, $tmp, $tmp2, $order, $limit, $pagesize);
  }
  $DB->ignore_error = false;
  
  echo'
  <div class="alert alert-info">';
	//bb2_insert_stats(true);
  if(false !== ($bb2_logtable = $sd_modules->GetSetting(MODULE_BAD_BEHAVIOR, 'log_table')))
  {
    $count1 = $DB->query_first('SELECT COUNT(`key`) count1 FROM '.$bb2_logtable." WHERE `key` = '00000000'");
    $count2 = $DB->query_first('SELECT COUNT(`key`) count2 FROM '.$bb2_logtable." WHERE `key` <> '00000000'");
    
  }
  echo '
  <p><strong>Bad Behavior</strong> is a 3rd party module to unobtrusively block spam and spam-bots, including statistics and logging functionalities. For more information please visit the <a href="http://www.bad-behavior.ioerror.us/" Target="_blank">Bad Behavior</a> website. If you find Bad Behavior valuable, please consider making a <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&amp;business=error%40ioerror%2eus&amp;item_name=I%20Heart%20Bad%20Behavior&amp;amount=10%2e00&amp;page_style=Primary&amp;no_shipping=1&amp;return=http%3a%2f%2ferror%2ewordpress%2ecom%2f&amp;cancel_return=http%3a%2f%2ferror%2ewordpress%2ecom%2f&amp;cn=Comments%20about%20Bad%20Behavior&amp;tax=0&amp;currency_code=USD&amp;bn=PP%2dDonationsBF&amp;charset=UTF%2d8" Target="_blank">financial contribution</a>
  for further development of Bad Behavior, copyright &copy; 2005-20012 Michael Hampton.</p>
  
  <p>
  To check all visitors against an ever growing and extensive collection of millions of spammers, spam commenters etc.,
  enable and support <a href="http://www.projecthoneypot.org/?rf=24694">Project Honey Pot</a> and their http:BL service.
  In order to enable this feature, you must obtain an http:BL access key (free!) and enter this key in settings tab.
  </p>
  </div>';
  
  echo '<ul class="nav nav-tabs" role="tablist">
  			<li class="active">
				<a href="#log" data-toggle="tab">Bad Behavior 2 Log</a>
			</li>
			<li >
				<a href="#settings" data-toggle="tab">Bad Behavior 2 Settings</a>
			</li></ul>
   		 <div class="tab-content">';
  
  echo '<div class="tab-pane in active" id="log""><div class="col-sm-9 align-bottom no-padding-left no-padding-bottom no-margin-bottom">
  			<a href="' . PagingUrl('ip', 'asc', 0, $pagesize, null) . '&amp;mode=distinct" class="mytooltip btn btn-white btn-info btn-bold btn-sm" data-toggle="tooltip" data-placement="top" Title="'.htmlspecialchars(AdminPhrase('mod_bb2_show_distinct')).'">
				<i class="ace-icon fa fa-filter blue bigger-130"></i>
			</a>
 
		  
            <input type="hidden" class="pagesize" value="'.$pagesize.'" />
            <a href="' . PagingUrl($sort, $order, 0, $pagesize, null, '') . '" class="mytooltip btn btn-white btn-info btn-bold btn-sm" data-toggle="tooltip" data-placement="top" Title="'.htmlspecialchars(AdminPhrase('mod_bb2_refresh')).'" />
				<i class="ace-icon fa fa-refresh green bigger-130"></i>
			</a>
          
            <a href="'.BB2_URI.'&action=purgelog" class="mytooltip btn btn-white btn-info btn-bold btn-sm" data-toggle="tooltip" data-placement="top" Title="'.htmlspecialchars(AdminPhrase('mod_bb2_clear_log')).
            '" onclick="return confirm(\''.htmlspecialchars(AdminPhrase('mod_bb2_clear_log_prompt')).'\');" />
			<i class="ace-icon fa fa-trash-o red bigger-130"></i>
			</a>
  		</div>
		<div class="col-sm-3 align-right no-padding-right">
			<form action="' . PagingUrl($sort, $order, $pagestart, $pagesize, null, $mode) . '" method="post">
				<div class="input-group">
					<input type="text" class="form-control search-query" name="search" value="' . $search . '" placeholder="'. AdminPhrase('common_search') . '" />
					<span class="input-group-btn">
						<button type="button" class="btn btn-purple btn-sm">
							Search
							<i class="ace-icon fa fa-search icon-on-right bigger-110"></i>
						</button>
					</span>
				</div>
			</form>
		</div>
		 
		 <div class="space-32"></div>
		 <form action="' . PagingUrl($sort, $order, $pagestart, $pagesize, null, $mode) . '" method="post" id="bb2">';
		 
		 
	

  StartTable('Bad Behavior 2 - Log', array('table', 'table-striped', 'table-bordered'));

  if(!empty($info))
  {
    echo '
    <tr>
      <td  colspan="9" class="center"><strong>'.$info.'</strong></td>
    </tr>';
  }
  echo'
  '.PrintSecureToken().'
  <input type="hidden" name="action" value="deletebb2" />
  <input type="hidden" name="mode" value="'.$mode.'" />
  <thead>
  <tr>
    <th >&nbsp;</td>
    <th ><a href="' . PagingUrl("ip", ($sort=='ip'?($order=='desc'?'asc':'desc'):'asc'), $pagestart, $pagesize, $search, $mode) . '">IP</a></th>
    <th ><a href="' . PagingUrl("key", ($sort=='key'?($order=='desc'?'asc':'desc'):'asc'), $pagestart, $pagesize, $search, $mode) . '">Key</a></th>
    <th >'.($mode=='distinct'?'Method':'<a href="' . PagingUrl("request_method", ($sort=='request_method'?($order=='desc'?'asc':'desc'):'asc'), $pagestart, $pagesize, $search, $mode) . '">Method</a>').'</th>
    <th ><a href="' . PagingUrl("request_uri", ($sort=='request_uri'?($order=='desc'?'asc':'desc'):'asc'), $pagestart, $pagesize, $search, $mode) . '">URI</a></th>
    <th >Request</td>
    <th ><a href="' . PagingUrl("date", ($sort=='date'?($order=='asc'?'desc':'asc'):'desc'), $pagestart, $pagesize, $search, $mode) .'">Date</a></th>
    <th class="center"><a id="checkall" rel="0" title="'.htmlspecialchars(AdminPhrase('common_delete'),ENT_COMPAT).
		  '" href="#" onclick="javascript:return false;"><i class="ace-icon fa fa-trash-o red bigger-130"></i></a></th>
  </tr>
  </thead>
  <tbody>';

  $stylepath = 'styles/'.ADMIN_STYLE_FOLDER_NAME . '/images/';
  @include_once(SD_CLASS_PATH.'bad-behavior/responses.inc.php');

  while($syslog = $DB->fetch_array($getsyslogs,null,MYSQL_ASSOC))
  {
    $bb2_key = $syslog['key'];
    echo '
    <tr>
      <td class="center" width="2%"><i class="ace-icon fa ';

    if(empty($bb2_key) || ($bb2_key=='00000000'))
    {
      $bb2_key = '-';
      echo 'fa-check green bigger-130 mytooltip tooltip-success" alt="-" title="'.htmlspecialchars(AdminPhrase('mod_bb2_allowed')).'"';
    }
    else
    {
      echo 'fa-minus-circle red bigger-130 mytooltip tooltip-error" alt="X" title="'.htmlspecialchars(AdminPhrase('mod_bb2_blocked')).'"';
    }
    if(isset($syslog['date']{3}) && substr($syslog['date'],0,4) < 1971) { $syslog['date'] = ''; }
    $isBanned = sd_IsIPBanned($syslog['ip']);
    // Note: first link in this <td> cell MUST be the link with the IP as text!!!
    echo ' />&nbsp;</td>
      <td class="ip_col align-left" width="10%">
	  <a href="#" class="mytooltip hostname" title="'.htmlspecialchars(AdminPhrase('ip_tools_title')).'"><i class="ace-icon fa fa-wrench"></i><span class="text-hide">'.$syslog['ip'].'</span></a>
        <a rel="iframe modal:false" title="'.htmlspecialchars(AdminPhrase('filter_ip_hint')).'" href="'.BB2_URI.
        '&amp;pagestart=0&amp;pagesize='.(int)$pagesize.'&amp;sort='.$sort.'&amp;search='.$syslog['ip'].
        '"'.($isBanned?' style="color:#ff0000"':'').'>'.$syslog['ip'].'</a>
      </td>
      <td class="align-left">';

    //SD344: if httpbl-codes present, display blocking type
    $coded = false;
    if(!empty($syslog['httpbl_code']))
    {
      echo '<span class="bolder">IP Blacklisted:</span> ';
      $code = (int)$syslog['httpbl_code'];
      if ($code & 1) {
        echo 'Suspicious ';
        $coded = true;
      }
      if ($code & 2) {
        echo 'Harvester<br />';
        $coded = true;
      }
      if ($code & 4) {
        echo 'Comment Spammer<br />';
        $coded = true;
      }
      if ($code & 7) {
        echo 'Threat'.(empty($syslog['httpbl_level'])?'':' Level '.(int)$syslog['httpbl_level']);
        $coded = true;
      }
    }

    if(!$coded && !empty($bb2_key) && ($bb2_key!='-'))
    {
      if($response = bb2_get_response($bb2_key))
        echo '<a class="bb2 cbox" title="BadBehavior '.htmlspecialchars(AdminPhrase('mod_bb2_visitor_message')).'" href="#">'.htmlspecialchars($response['log']).'<br /><span class="bb2key">'.$bb2_key.'</span></a>';
      else
        echo $bb2_key;
    }
    echo '&nbsp;</td>
      <td  class="align-left">' . (isset($syslog['request_method'])?$syslog['request_method']:'-') . '&nbsp;</td>
      <td  class="align-left">' .
      (isset($syslog['request_uri']{0})?wordwrap(htmlspecialchars($syslog['request_uri']),50,"<br />\n",1):'-') . '</td>
      <td  class="align-left">' . (isset($syslog['request_entity']{0})?nl2br(htmlspecialchars($syslog['request_entity'])):'-') . '</td>
      <td  width="10%">'.(!empty($syslog['date']) ? DisplayDate(strtotime($syslog['date']),'Y-m-d H:i:s') : '&nbsp;') . '</td>
      <td  class="center" width="5%">';
    if(!empty($syslog['id']) || ($mode=='distinct'))
    {
      echo '
        <input type="checkbox" class="deletebb2 ace" name="deleteids[]" value="'.($mode=='distinct'?$syslog['ip']:$syslog['id']).'" /><span class="lbl"></span>';
    }
    else
    {
      echo '-';
    }
    echo '</td>
    </tr>
	';
  }
  echo '</tbody>
    </table>
  </div>';
  if(!empty($row_cnt))
  {
    echo '
    <div class="col-sm-6  no-padding-left align-top">';
	
		 // Pagination
  if(!empty($paging['rowcount']))
  {
   
    $p = new pagination;
    $p->items($paging['rowcount']);
    $p->limit($pagesize);
    $p->currentPage($page);
    $p->adjacents(7);
    $p->target($pagination_target);
    $p->show();
 
  }
  
  echo '</div>
  		<div class="col-sm-6 align-right no-padding-right">
        <button class="btn btn-danger btn-sm" type="submit" id="bb2_delete_submit" value=""  />
			<i class="ace-icon fa fa-trash-o bigger-120"></i>'.htmlspecialchars(AdminPhrase('mod_bb2_delete')).'
		</button>
		</div>
    
	';
  }
  echo '
  </form>
  </div>
  ';
  
  

  $extraJS = '
  sd_checkbox_trigger("deleteselect","deletebb2");
  $("a.bb2").attr("rel","iframe modal:false").ceebox({animSpeed:"fast",htmlGallery:false,overlayOpacity:0.8});
  $("a.bb2").click(function(e){
    e.preventDefault();
    var bb2key = $(this).find("span.bb2key").text();
    $(this).attr("href","http://www.ioerror.us/bb2-support-key?key="+encodeURI(bb2key));
    return true;
  });';

  DisplayIPTools('a.hostname', 'td', $extraJS);
}

// ############################## PURGE LOG TABLE ##############################

function PurgeBB2Log()
{
  global $DB, $sd_modules;

  if(false !== ($bb2_logtable = $sd_modules->GetSetting(MODULE_BAD_BEHAVIOR, 'log_table')))
  {
    $DB->query('TRUNCATE TABLE '.$bb2_logtable);
    $DB->query('OPTIMIZE TABLE '.$bb2_logtable);
  }
} //PurgeBB2Log


function DeleteBB2Entry($selection,$ip,$mode)
{
  global $DB, $sd_modules;
  
  if(!empty($selection) &&
     ( (empty($mode) && is_numeric($selection)) || (is_array($selection) && count($selection)) ))
  {
    if(false !== ($bb2_logtable = $sd_modules->GetSetting(MODULE_BAD_BEHAVIOR, 'log_table')))
    {
      if(!is_array($selection)) $selection = array(0 => $selection);
      foreach($selection as $key => $id)
      {
        if(empty($mode) || is_numeric($id))
        {
          if($id = Is_Valid_Number($id, 0, 1, 9999999))
          $DB->query('DELETE FROM '.$bb2_logtable.' WHERE id = %d', $id);
        }
        else
        if($mode=='distinct')
        {
          if($id==preg_replace('/[^0-9\.]*/i', '', $id))
          {
            $DB->query('DELETE FROM '.$bb2_logtable." WHERE ip = '%s'", $id);
          }
        }
      }
      $DB->query('OPTIMIZE TABLE '.$bb2_logtable);
    }
  }
  
  echo '<script>
			
			var n = noty({
					text: \''.AdminPhrase('common_success').'\',
					layout: \'top\',
					type: \'success\',	
					timeout: 5000,					
					});
			</script>';
  

} //DeleteBB2Entry


function DeleteBB2IP($ip)
{
  global $DB, $sd_modules;


  if(!empty($ip) && is_string($ip) && (strlen($ip)>1) && ($ip==preg_replace('/[^0-9\.]*/i', '', $ip)))
  {
    if(false !== ($bb2_logtable = $sd_modules->GetSetting(MODULE_BAD_BEHAVIOR, 'log_table')))
    {
      $DB->query('DELETE FROM '.$bb2_logtable." WHERE ip = '%s'", $ip);
      $DB->query('OPTIMIZE TABLE '.$bb2_logtable);
      DisplayMessage('IP deleted: '.$ip,false);
    }
  }

} //DeleteBB2IP


// ############################## UPDATE SETTINGS ##############################

function UpdateBB2Settings()
{
  global $sd_modules;

	if(isset($_POST['settings']))
  {
    $sd_modules->SetSettings(MODULE_BAD_BEHAVIOR, $_POST['settings']);
    echo '<script>
			
			var n = noty({
					text: \''.AdminPhrase('settings_updated').'\',
					layout: \'top\',
					type: \'success\',	
					timeout: 5000,					
					});
			</script>';
	
	//echo '<div ><p><strong>&nbsp;'.AdminPhrase('settings_updated').'</strong></p></div><br />';
  }
} //UpdateBB2Settings


function DisplayBB2Settings()
{
  global $DB, $sd_modules;

  echo '<div class="tab-pane" id="settings">';
  $sd_modules->DisplaySettingsForm(MODULE_BAD_BEHAVIOR);
  echo '</div></div>';
  
} //DisplayBB2Settings

// #############################################################################

echo '<h3 class="header blue lighter"> Module: Bad Behavior </h3>';

$action = GetVar('action', 'displaysettings', 'string');
$id     = GetVar('id', 0, 'whole_number');
$ip     = GetVar('ip', '', 'string');
$mode   = GetVar('mode', '', 'string');

switch($action)
{
  case 'updatemodulesettings':
    UpdateBB2Settings();
    break;
  case 'delete_ip':
    DeleteBB2IP($ip);
    break;
  case 'deletebb2':
    $deleteids = GetVar('deleteids', array(), 'array', true, false);
    if(count($deleteids)) DeleteBB2Entry($deleteids,$ip,$mode);
    break;
  case 'deletelog':
    DeleteBB2Entry($id,$ip,$mode);
    break;
  case 'purgelog':
    PurgeBB2Log();
    break;
}
DisplayBB2Log($mode);
DisplayBB2Settings();

