<?php

// Stop execution if NOT within SD3 or not called from within tools handler:
if(!defined('IN_PRGM'))
{
  header("Location: index.php");
  exit();
}

define('SYSLOG_URI', 'settings.php?display_type=syslog');

PrintHeader('System Log');

// #############################################################################

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

	$arrPaging['rowcount']  	= $rowCount;
	$arrPaging['pagesize']  	= $pagesize;
	$arrPaging['pagestart'] 	= $pagestart;
	$arrPaging['totalpages']	= $pages;
	$arrPaging['currentpage'] = $current;
	$arrPaging['firstrow']    = $first;
	$arrPaging['lastrow']     = $last;

	return $arrPaging;
}

function DisplayPaging($arrPaging, $sort, $order, $search, $mode='')
{
	if($arrPaging['totalpages'] <= 1)
	{
	  return;
	}

	# Displaying links to all the pages using a loop
	$thispage = $arrPaging['currentpage'];
	$lim 		  = 10;	        // Maximum links to show
	$mid 		  = ($lim / 2);	// Middle Marker

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
		$lim 		  = $arrPaging['totalpages'] - 1;
		$thispage = 1;
	}
	
	echo '<ul class="pagination">';
	
	# If the user isn't on the first page (0) we'll put a "Back" link which will lead to the previous page
  if ($arrPaging['pagestart'] >= 0)
  {
    $back_page = $arrPaging['pagestart'] - $arrPaging['pagesize'];

    # The links for ID sort and usenet post date sort are different.
    # If the sort type is ID (by DB creation date) we'll show a regular link
    echo '<li><a title="Page 1" href="' . PagingUrl($sort, $order, 0, $arrPaging['pagesize'], $search, $mode) .
      '"><i class="ace-icon fa fa-angle-double-left"></i></a></li>';
  }

	for ($i = $thispage; $i < $thispage + $lim; $i++)
	{
		$ppage = $arrPaging['pagesize']*($i - 1);

		# We don't want to display a link to the current page, so we'll just show the current page in bold
		if ($ppage == $arrPaging['pagestart'])
		{
			echo("<li class='active'><a href='#'>$i</i></li>");
		}
		else # If the page number we're displaying isn't the page the user is on we'll link to it
		{
			# Again, the links for ID sort and usenet post date sort are different.
			echo "<li><a href='" . PagingUrl($sort, $order, $ppage, $arrPaging['pagesize'], $search, $mode) . "'>$i</a></li>";
		}
	}

 
  if($thispage > $lim)
  {
    //echo ' ';
    $diff = round($thispage / 3);
    if($diff > 3)
    {
      $next_page = $thispage - (2*$diff) - 1;
     // echo '<li><a title="Page '.($next_page).'" href="' . PagingUrl($sort, $order, (($next_page-1)*$arrPaging['pagesize']), $arrPaging['pagesize'], $search, $mode) .'">'.$next_page.'</a></li>';
      $next_page += $diff;
      if($next_page > 0)
      {
       // echo '<li><a title="Page '.($next_page).'" href="' . PagingUrl($sort, $order, (($next_page-1)*$arrPaging['pagesize']), $arrPaging['pagesize'], $search, $mode) .'">'.$next_page.'</a></li>';
      }
    }
  }

	# If the total number of the pages isn't 1 and if we're not on the last page we'll display a "Next" link
	if (!((($arrPaging['pagestart'] + $arrPaging['pagesize']) / $arrPaging['pagesize']) >= $arrPaging['totalpages']) && $arrPaging['totalpages'] != 1)
	{
    $diff = round(($arrPaging['totalpages'] - $thispage - $lim) / 3);
    if($diff > 3)
    {
      $next_page = $thispage + $lim + $diff - 1;
     // echo '<li><a title="Page '.($next_page).'" href="' . PagingUrl($sort, $order, (($next_page-1)*$arrPaging['pagesize']), $arrPaging['pagesize'], $search, $mode) .'">'.$next_page.'</a></li>';
      $next_page += $diff;
      if($next_page <= $arrPaging['totalpages'])
      {
      //  echo '<li><a title="Page '.($next_page).'" href="' . PagingUrl($sort, $order, (($next_page-1)*$arrPaging['pagesize']), $arrPaging['pagesize'], $search, $mode) .'">'.$next_page.'</a></li>';
      }
    }

		$next_page = $arrPaging['pagestart'] + $arrPaging['pagesize'];
		echo '<li><a title="Page '.($thispage+1).'" href="' . PagingUrl($sort, $order, $next_page, $arrPaging['pagesize'], $search, $mode) .
    '"><i class="ace-icon fa fa-angle-double-right"></i></a></li>';
	}
	
	echo '</ul>';
}


function PagingUrl($sort, $order, $pagestart, $pagesize, $search, $mode = '')
{
  $url = SYSLOG_URI.'&amp;sort='.$sort;

  if($mode == 'distinct')
  {
    $url .= '&amp;mode=distinct';
  }

  $order = strtolower($order);
  if(($order == 'asc') || ($order == 'desc'))
	{
		$url .= '&amp;order='.$order;
	}

	if(!empty($pagestart))
	{
		$url .= '&amp;pagestart='.(int)$pagestart;
	}

	$url .= '&amp;pagesize='.(int)$pagesize;

	if(isset($search) && strlen($search))
	{
    $search = htmlspecialchars(CleanVar(SanitizeInputForSQLSearch(unhtmlspecialchars($search),false,false)));
    if(strlen($search))
		$url .= "&amp;search=".urlencode($search);
	}

	return $url;
}


// #############################################################################

function PurgeLog()
{
  global $DB, $sdlanguage;

  // SD313: security token check
  if(!CheckFormToken())
  {
    RedirectPage(SYSLOG_URI,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'syslog');
  $DB->query('OPTIMIZE TABLE {syslog}');
  RedirectPage(SYSLOG_URI,'Purge done.',1,false);
  exit;

}

// #############################################################################

function DisplaySysLog($mode='',$info='')
{
  global $DB, $stylepath;

  $pagesize  = Is_Valid_Number(GetVar('pagesize', 20, 'natural_number'),20,10,99999);
	$pagestart = Is_Valid_Number(GetVar('pagestart', 0, 'natural_number', false),0,0,99999);
  $sort      = strtolower(GetVar('sort', 'timestamp', 'string', false));
	$sort      = in_array($sort, array('hostname','location','message','timestamp','type','username')) ? $sort : 'timestamp';
  $order     = strtoupper(GetVar('order', 'DESC', 'string', false));
  $order     = (($order=='ASC') || ($order=='DESC')) ? $order : 'DESC';
  $search    = GetVar('search', '', 'string');
  if(isset($_POST['search']) && strlen($_POST['search'])) $pagestart = 0;

	/* ############# PAGING ################# */

  $mysqlinfo = @mysql_get_server_info();
  $grouptimestamp = (version_compare($mysqlinfo, '5.0.27', '>='));

  if($mode == 'distinct')
  {
    $pagesize = $pagesize < 25 ? 25 : $pagesize;
    $row_cnt = $DB->query_first('SELECT COUNT(DISTINCT message) FROM {syslog}');
  }
  else if(empty($search))
  {
	  $row_cnt = $DB->query_first('SELECT COUNT(*) FROM {syslog}');
  }
  else
  {
    $row_cnt = $DB->query_first("SELECT COUNT(*) FROM {syslog}
      WHERE (type LIKE '%s') OR (message LIKE '%s') OR (hostname LIKE '%s')",
      '%' . $search . '%', '%' . $search . '%', '%' . $search . '%');
  }
  $row_cnt = $row_cnt[0];
	$paging = CalculatePaging($row_cnt, $pagestart, $pagesize);

  $dateformat = 'Y-m-d H:i:s';

  if(($mode != 'distinct') && ($sort=='hostname')) $sort = 'INET_ATON(hostname)'; //SD343
  if($mode == 'distinct')
  {
    $addfield = '';
    if($grouptimestamp) $addfield = 'MAX(timestamp) AS timestamp, ';
    $getsyslogs = $DB->query("SELECT DISTINCT(message), $addfield type, severity, wid
      FROM {syslog}
      GROUP BY message
      ORDER BY $sort $order, message");
  }
  else if(empty($search))
  {
    $getsyslogs = $DB->query("SELECT wid, severity, message, type, timestamp, hostname, location, username
			FROM {syslog} ORDER BY %s %s LIMIT %d, %d",
      $sort, $order, $pagestart, $pagesize);
  }
  else
  {
    $getsyslogs = $DB->query("SELECT wid, severity, message, type,
      timestamp, hostname, location, username
			FROM {syslog} WHERE (type LIKE '%s') OR (message LIKE '%s') OR (hostname LIKE '%s')
      ORDER BY %s %s LIMIT %d, %d",
      '%' . $search . '%', '%' . $search . '%', '%' . $search . '%', $sort, $order, $pagestart, $pagesize);
  }
  
  echo '
  	<div class="col-sm-9 align-bottom no-padding-left no-padding-bottom no-margin-bottom">
	<form action="' . PagingUrl($sort, $order, $pagestart, $pagesize, null, $mode) . '" method="post" id="syslogsearch">
            '.PrintSecureToken().'
  			<a class="mytooltip btn btn-white btn-success btn-bold btn-sm" data-toggle="tooltip" data-placement="top" Title="' . AdminPhrase('syslog_refresh') . '" href="'.SYSLOG_URI.'"><i class="ace-icon fa fa-refresh green bigger-130"></i></a>&nbsp;
			<a class="mytooltip btn btn-white btn-info btn-bold btn-sm" data-toggle="tooltip" data-placement="top" href="'.PagingUrl('timestamp', 'DESC', 0, $pagesize, null, $mode) . '&amp;mode=distinct" Title="' . AdminPhrase('syslog_distinct_messages') . '"><i class="ace-icon fa fa-filter blue bigger-130"></i></a>
			 <a class="mytooltip btn btn-white btn-danger btn-bold btn-sm clearsystemlog" data-toggle="tooltip" data-placement="top" href="'.SYSLOG_URI.'&amp;action=purgelog'.SD_URL_TOKEN.'" Title="'.
          AdminPhrase('syslog_clear_log') . '"><i class="ace-icon fa fa-trash-o red bigger-130"></i></a>
	</div>
	<div class="col-sm-3 align-right no-padding-right">
	
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
	<div class="space-28"></div>';

  StartTable('System Log', array('table', 'table-bordered', 'table-striped'));

echo'
  <form id="syslogform" action="' . PagingUrl($sort, $order, $pagestart, $pagesize, null, $mode) . '" method="post">
  '.PrintSecureToken().'
  <input type="hidden" name="action" value="delete_entries" />
  <input type="hidden" name="mode" value="'.$mode.'" />

  <thead>
  <tr>
    <th class="td1">&nbsp;</td>
    <th class="td1"><a href="' . PagingUrl("type",     ($sort == 'type'?($order=='DESC'?'ASC':'DESC'):$order), $pagestart, $pagesize, $search, $mode) . '">Source</a></th>
    <th class="td1"><a href="' . PagingUrl("message",  ($sort == 'message'?($order=='DESC'?'ASC':'DESC'):$order), $pagestart, $pagesize, $search, $mode) . '">Message</a></th>
    <th class="td1"><a href="' . PagingUrl("username", ($sort == 'username'?($order=='DESC'?'ASC':'DESC'):$order), $pagestart, $pagesize, $search, $mode) . '">User</a></th>
    <th class="td1"><a href="' . PagingUrl("hostname", ($sort == 'hostname'?($order=='DESC'?'ASC':'DESC'):$order), $pagestart, $pagesize, $search, $mode) . '">Host</a></th>
    <th class="td1"><a href="' . PagingUrl("location", ($sort == 'location'?($order=='DESC'?'ASC':'DESC'):$order), $pagestart, $pagesize, $search, $mode) . '">Location</a></th>
    <th class="td1"><a href="' . PagingUrl("timestamp",($sort == 'timestamp'?($order=='DESC'?'ASC':'DESC'):$order), $pagestart, $pagesize, $search, $mode) . '">Date/Time'.($mode=='distinct'?' (last)':'').'</a></th>
    <th class="td1" width="80" ><input id="deleteselect" type="checkbox" class="ace" rel="1" /><span class="lbl"> '.AdminPhrase('common_delete').'</span></th>
  </tr>
  </thead>
  <tbody>';

  while($syslog = $DB->fetch_array($getsyslogs,null,MYSQL_ASSOC))
  {
    $DB->result_type = MYSQL_ASSOC;
    echo '
    <tr>
		  <td class="center" ><i class="ace-icon fa ';

    if($syslog['severity'] == WATCHDOG_ERROR)
    {
      echo 'fa-times red bigger-120 mytooltip tooltip-error" alt="!!!" title="'.htmlspecialchars(AdminPhrase('syslog_error')).'"';
    }
    else if($syslog['severity'] == WATCHDOG_WARNING)
    {
      echo 'fa-warning orange bigger-120 mytooltip tooltip-warning" alt="!" title="'.htmlspecialchars(AdminPhrase('syslog_warning')).'"';
    }
    else
    {
      echo 'fa-check green bigger-120 mytooltip tooltip-success" alt="Info" title="'.htmlspecialchars(AdminPhrase('syslog_information')).'"';
    }
    $entry = str_replace(array('<br />','<br>'),array(' - ',' - '),$syslog['message']);

    if(($syslog['type']!='Usersystem')   && ($syslog['type']!='Profile') &&
       ($syslog['type']!='Contact Form') && ($syslog['type']!='Forum') &&
       ($syslog['type']!='Login Error')  && ($syslog['type']!='Registration'))
    {
      $entry = wordwrap(htmlentities(strip_tags($entry)),50,"<br />\n",0);
    }
    echo ' />&nbsp;</td>
		  <td class="center" >' . (isset($syslog['type'])?$syslog['type']:'&nbsp;') . '</td>
	    <td class="center" >' . $entry. '</td>
	    <td class="center" >' . (!empty($syslog['username'])?$syslog['username']:'-') . '&nbsp;</td>
      <td class="center"  width="140">';
    if(isset($syslog['hostname']{0}))
    {
      $isBanned = sd_IsIPBanned(trim($syslog['hostname']));
      // Note: first link in this <td> cell MUST be the link with the IP as text!!!
      echo '
        <a title="'.htmlspecialchars(AdminPhrase('filter_ip_hint')).'" href="'.SYSLOG_URI.
        '&amp;action=syslogsearch&amp;mode='.$mode.SD_URL_TOKEN.
        '&amp;search='.urlencode($syslog['hostname']).'&amp;pagestart='.(int)$pagestart.'&amp;pagesize='.(int)$pagesize.
        '&amp;sort='.$sort.'&amp;order='.$order.'" '.($isBanned?' class="red ':'class="').'hostname">'.$syslog['hostname'].'</a>
        &nbsp;<a href="#" class="imgtools hostname" style="text-indent:-99999px !important;float:left;" title="'.htmlspecialchars(AdminPhrase('ip_tools_title')).'">'.$syslog['hostname'].'</a>';
    }
    else
    {
      echo '-';
    }
    echo '</td>
      <td class="center" >' . (isset($syslog['location']{0})?wordwrap($syslog['location'],40,"<br />\n",1):'-') . '</td>
	    <td class="center">'.(isset($syslog['timestamp'])?DisplayDate($syslog['timestamp'],'',true):'-') . '</td>
	    <td class="center" >';
    if(!empty($syslog['wid']) || ($mode=='distinct'))
    {
      echo '
        <input type="checkbox" class="del_entry ace" name="deleteids[]" value="'.$syslog['wid'].'" /><span class="lbl"> ';
      if($mode!='distinct')
        echo '
        <a href="'.SYSLOG_URI.'&amp;action='.($mode=='distinct'?'delete_ip':'deletelog').'&amp;mode='.$mode.SD_URL_TOKEN.
        '&amp;wid='.$syslog['wid'].'&amp;pagestart='.(int)$pagestart.
        '&amp;pagesize='.(int)$pagesize.'&amp;sort='.$sort.'&amp;order='.$order.'">'.
        htmlspecialchars(AdminPhrase('common_delete')).'</a></span>';
    }
    else
    {
      echo '-';
    }
    echo '</td>
	  </tr>';
  }
  $DB->result_type = MYSQL_BOTH;

  if(!empty($row_cnt))
  {
    $DB->free_result($getsyslogs);
    echo '
    </tbody>
	</table>
	</div>
	<div class="center">
      <td colspan="9" class="align-right">
        <button class="btn btn-danger btn-sm" type="submit" id="deletebutton" /><i class="ace-icon fa fa-trash-o"></i> '.addslashes(AdminPhrase('common_delete')).
        '</button>
	</div>
	</form>';
  }
  echo '</table>
  </div>
  </form>';
  
  echo '<div class="align-left">';
  
  DisplayPaging($paging, $sort, $order, $search, $mode);
  echo '</div>';



  $extraJS = '
  sd_checkbox_trigger("deleteselect","del_entry");';

  DisplayIPTools('a.hostname', 'td', $extraJS); //SD343
}

// #############################################################################

function DeleteLogEntriesByIP($ip) //SD342
{
  global $DB;

  if(!empty($ip) && is_string($ip) && (strlen($ip)>1) && ($ip==preg_replace('/[^0-9\.]*/i', '', $ip)))
  {
    $DB->query("DELETE FROM {syslog} WHERE hostname LIKE '%s%'", $ip);
    $DB->query('OPTIMIZE TABLE {syslog}');
  }
} //DeleteLogEntriesByIP

// #############################################################################

function DeleteLog($selection,$ip,$mode='')
{
  global $DB, $sdlanguage;

  // SD313: security token check
  if(!CheckFormToken())
  {
    RedirectPage(SYSLOG_URI,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
    return;
  }

  if(!empty($selection) &&
     ( (empty($mode) && is_numeric($selection)) || (is_array($selection) && count($selection)) ))
  {
    if(!is_array($selection)) $selection = array(0 => $selection);
    $deleted = 0;
    $DB->ignore_error = true;
    foreach($selection as $key => $id)
    {
      if(!$id = Is_Valid_Number($id, 0, 1, 9999999)) break;
      if(empty($mode))
      {
        if($DB->query('DELETE FROM {syslog} WHERE wid = %d', $id))
        {
          $deleted += $DB->affected_rows();
        }
      }
      else
      if(($mode=='distinct'))
      {
        // Delete all messages that have the same message text
        if($msgrow = $DB->query_first('SELECT message FROM {syslog} WHERE wid = %d',$id))
        {
          if($DB->query("DELETE FROM {syslog} WHERE message = '%s'",
                        $DB->escape_string($msgrow['message'],ENT_COMPAT)))
          {
            $deleted += $DB->affected_rows();
          }
        }
      }
      else break;
    } //foreach;
    $DB->query('OPTIMIZE TABLE {syslog}');
    $DB->ignore_error = false;
	$msg = '<strong>'.AdminPhrase('syslog_messages_deleted').' '.$deleted.'</strong>';
	
	echo '<script>
	jDialog.close();
	var n = noty({
						text: \''.$msg.'\',
						layout: \'top\',
						type: '.(count($errors_arr) ? '\'error\'' : '\'success\'').',	
						timeout: 5000,					
						});</script>';
    
  }
} //DeleteLog

// #############################################################################

$stylepath = ROOT_PATH . ADMIN_PATH . '/styles/' . ADMIN_STYLE_FOLDER_NAME . '/';
$action = GetVar('action', '', 'string');
$id     = GetVar('wid', 0, 'whole_number');
$ip     = GetVar('ip', '', 'string');
$mode   = GetVar('mode', '', 'string');

switch($action)
{
  case 'delete_ip':
    DeleteLogEntriesByIP($ip);
    break;
  case 'delete_entries':
    $deleteids = GetVar('deleteids', array(), 'array', true, false);
    if(count($deleteids)) DeleteLog($deleteids,$ip,$mode);
    break;
  case 'deletelog':
    DeleteLog($id,$ip,$mode);
    break;
  case 'purgelog':
    PurgeLog();
    break;
  default:
}

DisplaySysLog($mode);
