<?php
if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

$plugin_folder = sd_GetCurrentFolder(__FILE__);
$prefix = 'p'.$pluginid.'_';
$tbl_pre = PRGM_TABLE_PREFIX.$prefix;

function p16_IsParentSection($sectionid,$parentid,$idlist=null)
{
  global $DB, $tbl_pre;
  if(empty($sectionid) || (!empty($idlist) && @in_array($sectionid,$idlist)))
  {
    return false;
  }
  $idlist[] = $sectionid;

  if(!empty($sectionid) && Is_Valid_Number($sectionid, 0, 1) &&
     ($section = $DB->query_first('SELECT parentid FROM '.$tbl_pre.'sections WHERE sectionid = %d', $sectionid)))
  {
    if($section[0]==$parentid)
      return true;
    else
      return p16_IsParentSection($section['parentid'],$parentid,$idlist);
  }
  else
    return false;

} //p16_IsParentSection

// ############################# INSERT NEW LINK ##############################

function InsertLink($sectionid, $activated, $allowsmilies, $showauthor, $author, $title, $url, $description, $thumbnail)
{
  global $DB, $refreshpage;

  $activated    = empty($activated)    ? 0 : 1;
  $allowsmilies = empty($allowsmilies) ? 0 : 1;
  $showauthor   = empty($showauthor)   ? 0 : 1;

  if(!isset($title{0}))
  {
    $errors[] = 'You must enter a title for the link';
  }

  if(!isset($url{0}))
  {
    $errors[] = 'You must enter an URL for the link';
  }

  if(!isset($errors))
  {
    global $tbl_pre;
    $DB->query('INSERT INTO '.$tbl_pre."links VALUES (NULL, %d, %d, %d, %d, '%s', '%s', '%s', '%s', '%s', '%s')",
               $sectionid, $activated, $allowsmilies, $showauthor, $author, $title, $url, $description, $thumbnail, USERIP);

    PrintRedirect($refreshpage, 1);
  }
  else
  {
    DisplayLinkForm(null, $errors);  // 1 = errors exist
  }

} //InsertLink


// ############################# INSERT NEW LINK ###############################

function UpdateLink($deletelink, $linkid, $sectionid, $activated, $allowsmilies,
                    $showauthor, $author, $title, $url, $description, $thumbnail)
{
  global $DB, $refreshpage;

  $activated    = empty($activated)    ? 0 : 1;
  $allowsmilies = empty($allowsmilies) ? 0 : 1;
  $showauthor   = empty($showauthor)   ? 0 : 1;

  // delete link?
  if(!empty($deletelink))
  {
    return DeleteLink($linkid);
  }

  if(!isset($title{0}))
  {
    $errors[] = 'You must enter a title for the link';
  }

  if(!isset($url{0}))
  {
    $errors[] = 'You must enter an URL for the link';
  }

  if(!isset($errors))
  {
    global $tbl_pre;
    $DB->query('UPDATE '.$tbl_pre."links
     SET sectionid    = %d,
         activated    = %d,
         allowsmilies = %d,
         showauthor   = %d,
         author       = '%s',
         title        = '%s',
         url          = '%s',
         description  = '%s',
         thumbnail    = '%s'
     WHERE linkid     = %d",
     $sectionid, $activated, $allowsmilies, $showauthor,
     $author, $title, $url, $description, $thumbnail, $linkid);

    PrintRedirect($refreshpage, 1);
  }
  else
  {
    PrintErrors($errors);
    DisplayLinkForm($linkid);
  }

} //UpdateLink


// ############################# DELETE LINK ##################################

function DeleteLink($linkid)
{
  global $DB, $refreshpage;

  if(!empty($linkid) && (int)$linkid > 0)
  {
    global $tbl_pre;
    $DB->query('DELETE FROM '.$tbl_pre.'links WHERE linkid = %d', $linkid);
  }

  PrintRedirect($refreshpage, 1);

} //DeleteLink


// ########################## DELETE MULTIPLE LINKS ###########################

function DeleteLinks()
{
  global $DB, $refreshpage;

  $linkids = GetVar('linkids', array(), 'array', true, false);

  if(count($linkids))
  {
    global $tbl_pre;
    $DB->query('DELETE FROM '.$tbl_pre.'links WHERE linkid IN('.implode(',',$linkids).')');
  }
  PrintRedirect($refreshpage, 1);

} //DeleteLinks


// ############################ INSERT NEW SECTION ############################

function InsertSection($parentid, $activated, $name, $description, $sorting)
{
  $activated = empty($activated) ? 0 : 1;

  if(!isset($name{0}))
  {
    DisplaySectionForm(null, array('Please enter a name for the section'));
    return;
  }

  global $DB, $refreshpage, $tbl_pre;
  $DB->query('INSERT INTO '.$tbl_pre."sections VALUES (NULL, %d, %d, '%s', '%s', '%s')",
              $parentid, $activated, $name, $description, $sorting);

  PrintRedirect($refreshpage, 1);

} //InsertSection


// ############################## UPDATE SECTION ###############################

function UpdateSection($sectionid, $parentid, $activated, $name, $description, $sorting)
{
  global $DB, $refreshpage;

  // "Root" section always active and has "0" as parent
  if(!empty($sectionid) && ($sectionid==1))
  {
    $parentid  = 0;
    $activated = 1;
  }
  else
  {
    // Any other section has at least the "root" section as parent
    $parentid  = empty($parentid)  ? 1 : (int)$parentid;
    $activated = empty($activated) ? 0 : 1;
  }

  // delete section links
  if(!empty($_POST['deletesectionlinks']))
  {
    DeleteSectionLinks($sectionid);
  }

  // delete section
  if(!empty($_POST['deletesection']))
  {
    return DeleteSection($sectionid);
  }

  $errors = array();
  if(!isset($name{0}))
  {
    $errors[] = 'Please enter a name for this section';
  }

  // do not allow setting a child as parent
  if(!empty($sectionid) && p16_IsParentSection($parentid,$sectionid))
  {
    $errors[] = 'The section\'s parent section is invalid!';
  }

  if($description == '<br>' || $description == '<br />')
  {
    $description = '';
  }

  if(count($errors))
  {
    DisplaySectionForm($sectionid, $errors);
    return;
  }

  global $tbl_pre;
  $DB->query('UPDATE '.$tbl_pre."sections
    SET parentid    = %d,
        activated   = %d,
        name        = '%s',
        description = '%s',
        sorting     = '%s'
    WHERE sectionid = %d",
    $parentid, $activated, $name, $description, $sorting, $sectionid);

  PrintRedirect($refreshpage, 1);

} //UpdateSection


// ############################## DELETE SECTION ###############################

function DeleteSection($sectionid)
{
  global $DB, $refreshpage;

  if(!empty($sectionid) && ($sectionid > 1))
  {
    global $tbl_pre;
    $DB->query('UPDATE '.$tbl_pre.'links SET sectionid = 1 WHERE sectionid = %d', $sectionid);
    $DB->query('DELETE FROM '.$tbl_pre.'sections WHERE sectionid = %d', $sectionid);
  }
  PrintRedirect($refreshpage, 1);

} //DeleteSection


// ############################ DELETE SECTION LINKS ###########################

function DeleteSectionLinks($sectionid)
{
  global $DB;

  // delete all links of given section
  if(!empty($sectionid) && ($sectionid > 0))
  {
    global $tbl_pre;
    $DB->query('DELETE FROM '.$tbl_pre.'links WHERE sectionid = %d', $sectionid);
  }

} //DeleteSectionLinks


// ############################ DISPLAY PLUGIN SETTINGS ########################

function DisplaySettings()
{
  global $pluginid, $plugin, $refreshpage;

  sd_PrintBreadcrumb('&laquo; Back to '.$plugin['displayname'], $refreshpage);

  PrintPluginSettings($pluginid, 'Options', $refreshpage);

} //DisplaySettings


// ############################ DISPLAY SECTION FORM ###########################

function DisplaySectionForm($sectionid, $errors = null)
{
  global $DB, $refreshpage, $plugin, $tbl_pre;

  sd_PrintBreadcrumb('&laquo; Back to '.$plugin['displayname'], $refreshpage);

  if(!empty($errors))
  {
    PrintErrors($errors);
  }

  if(!empty($sectionid) && ($sectionid > 0))
  {
    // gather section information
    $section = $DB->query_first('SELECT * FROM '.$tbl_pre.'sections WHERE sectionid = %d', $sectionid);
    PrintSection('Edit Section');
  }
  else
  {
    // create empty array
    $section = array('sectionid'   => 0,
                     'parentid'    => 1,
                     'activated'   => 1,
                     'name'        => '',
                     'description' => '',
                     'sorting'     => 'Newest First');
    PrintSection('Create New Section');
  }

  // In case of errors, assume that there are POST values and assign them
  if(!empty($errors))
  {
    // Below variables are already processed by main plugin processing:
    $section['sectionid']   = $_POST['sectionid'];
    $section['parentid']    = $_POST['parentid'];
    $section['activated']   = $_POST['activated'];
    $section['name']        = $_POST['name'];
    $section['description'] = $_POST['description'];
    $section['sorting']     = $_POST['sorting'];
  }

  echo '
  <form method="post" action="'.$refreshpage.'">
  <input type="hidden" name="sectionid"  value="'.(int)$sectionid.'" />
  <input type="hidden" name="loadwysiwyg" value="1" />
  <table width="100%" border="0" cellpadding="5" cellspacing="0">';

  if(!empty($sectionid))
  {
    echo '
    <tr>
      <td class="tdrow2" width="25%"><strong>Delete Section:</strong></td>
      <td class="tdrow3" width="75%" valign="top">';

    if($sectionid == 1)
    {
      echo 'The Root Section can not be deleted.';
    }
    else
    {
      echo '<input type="checkbox" name="deletesection" value="1" /> Delete this Section?';
    }

    echo '</td>
    </tr>';


    echo '
    <tr>
      <td class="tdrow2" width="25%" valign="top"><strong>Delete Links:</strong></td>
      <td class="tdrow3" width="75%" valign="top">
        <input type="checkbox" name="deletesectionlinks" value="1" />
        Delete all links contained in this section?</td>
    </tr>';
  }

  echo '
    <tr>
      <td class="tdrow2" width="25%"><strong>Sub Section Of:</strong></td>
      <td class="tdrow3" width="75%" valign="top">';

  if(!empty($sectionid) && ($sectionid == 1))
  {
    echo 'The Root Section is the parent of all sections and can not be a subsection.';
  }
  else
  {
    PrintSectionSelection('parentid', $section['parentid'], $section['sectionid']);
  }

  echo '  </td>
    </tr>
    <tr>
      <td class="tdrow2" width="25%" valign="top"><strong>Sort Links By:</strong></td>
      <td class="tdrow3" width="75%" valign="top">
      <select name="sorting" style="width: 150px;" />
          <option '.($section['sorting'] == 'Newest First' ?       'selected="selected"' : '') .' >Newest First</option>
          <option '.($section['sorting'] == 'Oldest First' ?       'selected="selected"' : '') .' >Oldest First</option>
          <option '.($section['sorting'] == 'Alphabetically A-Z' ? 'selected="selected"' : '') .' >Alphabetically A-Z</option>
          <option '.($section['sorting'] == 'Alphabetically Z-A' ? 'selected="selected"' : '') .' >Alphabetically Z-A</option>
          <option '.($section['sorting'] == 'Author Name A-Z' ?    'selected="selected"' : '') .' >Author Name A-Z</option>
          <option '.($section['sorting'] == 'Author Name Z-A' ?    'selected="selected"' : '') .' >Author Name Z-A</option>
        </select>
      </td>
    </tr>
    <tr>
      <td class="tdrow2" width="25%"><strong>Section Name:</strong></td>
      <td class="tdrow3" width="75%" valign="top">
        <input type="text" name="name" value="'.CleanFormValue($section['name']).'" />
      </td>
    </tr>
    <tr>
      <td class="tdrow2" width="25%"><strong>Section Description:</strong></td>
      <td class="tdrow3" width="75%">';
  PrintWysiwygElement('description', CleanFormValue($section['description']));
  echo '</td>
    </tr>';

  if(isset($sectionid) && ($sectionid != 1))
  {
    echo '
    <tr>
      <td class="tdrow2" width="25%" valign="top"><strong>Options:</strong></td>
      <td class="tdrow3" width="75%" valign="top">
        <input type="checkbox" name="activated" value="1" '.(empty($section['activated']) ? '' : 'checked="checked"').' />
        <strong>Active:</strong> Display this section online?
      </td>
    </tr>';
  }

  echo '
    <tr>
      <td colspan="2" class="tdrow1" align="center">';

  if(!empty($sectionid) && ($sectionid > 0))
  {
    echo '<input type="hidden" name="action" value="updatesection" />
          <input type="submit" value="Update Section" />';
  }
  else
  {
    echo '<input type="hidden" name="action" value="insertsection" />
          <input type="submit" value="Create Section" />';
  }

  echo '</td>
  </tr>
  </table>
  </form>';
  EndSection();

} //DisplaySectionForm


// ############################ DISPLAY LINK FORM ##############################

function DisplayLinkForm($linkid, $errors = null)
{
  global $DB, $refreshpage, $userinfo, $plugin, $tbl_pre;

  sd_PrintBreadcrumb('&laquo; Back to '.$plugin['displayname'], $refreshpage);

  if(!empty($errors))
  {
    PrintErrors($errors);
  }

  if(!empty($linkid) && ($linkid > 0))
  {
    // gather link information
    $link = $DB->query_first('SELECT * FROM '.$tbl_pre.'links WHERE linkid = %d', $linkid);
    PrintSection('Edit Link');
  }
  else
  {
    // get current loggedin username
    $username = $userinfo['username'];
    // create empty array
    $link = array("sectionid"    => '1',
                  "author"       => $username,
                  "title"        => '',
                  "url"          => '',
                  "description"  => '',
                  "thumbnail"    => '',
                  "activated"    => 1,
                  "showauthor"   => 1,
                  "allowsmilies" => 1 );
    PrintSection('Insert New Link');
  }

  // In case of errors, assume that there are POST values and assign them
  if(!empty($errors))
  {
    // Below variables are already processed by main plugin processing:
    $link['sectionid']    = $_POST['sectionid'];
    $link['author']       = $_POST['author'];
    $link['title']        = $_POST['title'];
    $link['url']          = $_POST['url'];
    $link['description']  = $_POST['description'];
    $link['activated']    = $_POST['activated'];
    $link['showauthor']   = $_POST['showauthor'];
    $link['allowsmilies'] = $_POST['allowsmilies'];
    $link['thumbnail']    = $_POST['thumbnail'];
  }

  echo '
  <form method="post" action="'.$refreshpage.'">
  <input type="hidden" name="linkid" value="'.$linkid.'" />
  <input type="hidden" name="loadwysiwyg" value="1" />
  <table border="0" cellpadding="5" cellspacing="0" width="100%">';

  if(!empty($linkid) && ($linkid > 0))
  {
    // delete link option
    echo '<tr>
      <td class="tdrow2" width="25%"><strong>Delete Link:</strong></td>
      <td class="tdrow3" width="75%" valign="top">
        <input type="checkbox" name="deletelink" value="1" /> Delete this link?
      </td>
    </tr>';
  }

  echo '<tr>
      <td class="tdrow2" width="25%"><strong>Author:</strong></td>
      <td class="tdrow3" width="75%" valign="top">
        <input type="text" name="author" value="'.CleanFormValue($link['author']).'" />
      </td>
    </tr>
    <tr>
      <td class="tdrow2" width="25%"><strong>Section:</strong></td>
      <td class="tdrow3" width="75%" valign="top">';

  PrintSectionSelection('sectionid', $link['sectionid']);

  echo '  </td>
    </tr>
    <tr>
      <td class="tdrow2" width="25%"><strong>Title:</strong></td>
      <td class="tdrow3" width="75%" valign="top">
        <input type="text" name="title" size="64" maxlength="128" value="'.CleanFormValue($link['title']).'" />
      </td>
    </tr>
    <tr>
      <td class="tdrow2" width="25%" valign="top"><strong>URL:</strong></td>
      <td class="tdrow3" width="75%" valign="top">
        <input type="text" name="url" size="64" value="'.CleanFormValue($link['url']).'" />
      </td>
    </tr>
    <tr>
      <td class="tdrow2" width="25%" valign="top"><strong>Description:</strong></td>
      <td class="tdrow3" width="75%" valign="top">';
  PrintWysiwygElement('description', CleanFormValue($link['description']));
  echo '</td>
    </tr>
    <tr>
      <td class="tdrow2" width="15%" valign="top"><strong>Thumbnail URL:</strong></td>
      <td class="tdrow3" width="75%" valign="top">
        <input type="text" name="thumbnail" size="64" maxlength="128" value="'.CleanFormValue($link['thumbnail']).'" />
      </td>
    </tr>
    <tr>
      <td class="tdrow2" width="25%" valign="top"><strong>Options:</strong></td>
      <td class="tdrow3" width="75%" valign="top">
        <input type="checkbox" name="activated"    value="1" '.(!empty($link['activated']) ? 'checked="checked"' : '').' />
        <strong>Publish:</strong> Are you ready to publish this link on your site?<br />
        <input type="checkbox" name="showauthor"   value="1" '.(!empty($link['showauthor']) ? 'checked="checked"' : '').' />
        <strong>Display Author Name:</strong> Would you like the author\'s name (of the submission) under the title of the link?<br />
      </td>
    </tr>
    <tr>
      <td colspan="2" align="center" class="tdrow1">';
        //<input type="checkbox" name="allowsmilies" value="1" '.(!empty($link['allowsmilies']) ? 'checked="checked"' : '').' />
        //<strong>Smilies:</strong> Enable smilies in the description of the link?<br />

  if(!empty($linkid) && ($linkid > 0))
  {
    echo '<input type="hidden" name="action" value="updatelink" />
          <input type="submit" value="Update Link" />';
  }
  else
  {
    echo '<input type="hidden" name="action" value="insertlink" />
          <input type="submit" value="Submit Link" />';
  }

  echo '</td>
  </tr>
  </table>
  </form>';
  EndSection();

} //DisplayLinkForm


// ############################ PRINT SECTION'S CHILDREN #######################

function PrintSectionChildren($parentid, $selected, $exclude, $indent, $displaycounts = 0)
{
  global $DB, $tbl_pre;

  $getsections = $DB->query('SELECT sectionid, name, activated FROM '.$tbl_pre.'sections'.
                            ' WHERE parentid = %d ORDER BY name', $parentid);

  while($sections = $DB->fetch_array($getsections,null,MYSQL_ASSOC))
  {
    if($exclude != $sections['sectionid'])
    {
      $name = $indent . ' ' . $sections['name'] .($sections['sectionid'] == 1 ? ' (root)' : '');
      if($displaycounts)
      {
        $getcount = $DB->query_first('SELECT COUNT(*) linkcount FROM '.$tbl_pre.'links'.
                                     ' WHERE activated = 1 AND sectionid = %d', $sections['sectionid']);
        $name .= ' ('.$getcount['linkcount'].')';
      }
      if(empty($sections['activated']))
      {
        $name .= ' *';
      }
      echo "<option value=\"$sections[sectionid]\" ".($selected == $sections['sectionid'] ? 'selected="selected"' : '')." />$name</option>\n";
    }
    PrintSectionChildren($sections['sectionid'], $selected, $exclude, $indent . '- - ', $displaycounts);
  }
} //PrintSectionChildren


// ######################## PRINT SECTION SELECTION EX #########################

function PrintSectionSelectionEx()
{
  global $DB, $tbl_pre;

  echo "\n<select name=\"sectionid\" style=\"width: 200px;\" />\n";

  PrintSectionChildren(0, null, null, '', 1);

  $getofflinelinks = $DB->query_first('SELECT count(*) inactivelinks FROM '.$tbl_pre.'links WHERE activated = 0');
  if(!empty($getofflinelinks['inactivelinks']))
  {
    echo "\n<option value=\"Offline Links\" />Offline Links ($getofflinelinks[0])</option>\n";
  }

  // 2.4.4: show links assigned to an invalid section:
  $getunassigned = $DB->query_first('SELECT count(linkid)
      FROM '.$tbl_pre.'links l
      LEFT OUTER JOIN '.$tbl_pre.'sections s ON l.sectionid = s.sectionid
      WHERE IFNULL(s.sectionid,0) < 1');
  if($getunassigned[0] > 0)
  {
    echo "\n<option value=\"Lost Links\" />Lost Links ($getunassigned[0])</option>\n";
  }
  echo "</select>\n";

} //PrintSectionSelectionEx


// ############################ PRINT SELECTION BOX ############################

function PrintSectionSelection($name, $selected = null, $exclude = null)
{
  echo "\n<select name=\"$name\" style=\"width: 200px;\" />\n";

  PrintSectionChildren(0, $selected, $exclude, '');

  echo "\</select>\n";

} //PrintSectionSelection


// ############################# DISPLAY LIST OF LINKS #########################

function DisplayLinks($viewtype)
{
  global $DB, $refreshpage, $plugin, $tbl_pre;

  switch($viewtype)
  {
    case 'Lost Links': // "lost" links (invalid section assigned)
      sd_PrintBreadcrumb('&laquo; Back to '.$plugin['displayname'], $refreshpage);
      $getlinks = $DB->query('SELECT l.*
      FROM '.$tbl_pre.'links l
      LEFT OUTER JOIN '.$tbl_pre.'sections s ON l.sectionid = s.sectionid
      WHERE (s.sectionid IS NULL) OR (s.sectionid < 1)');
    break;

    case 'Latest Links': // 20 most recent submissions
      $getlinks = $DB->query('SELECT * FROM '.$tbl_pre.'links ORDER BY linkid DESC LIMIT 0,20');
    break;

    case 'Offline Links': // offline = not activated
      sd_PrintBreadcrumb('&laquo; Back to '.$plugin['displayname'], $refreshpage);
      $getlinks = $DB->query('SELECT * FROM '.$tbl_pre.'links WHERE activated = 0 ORDER BY linkid DESC');
    break;

    default:
      sd_PrintBreadcrumb('&laquo; Back to '.$plugin['displayname'], $refreshpage);
      $sectionid = $viewtype;
      $getlinks  = $DB->query('SELECT * FROM '.$tbl_pre.'links WHERE sectionid = %d ORDER BY linkid DESC', $sectionid);
      $viewtype  = 'Links';
  }

  PrintSection($viewtype);

  echo '
  <form action="'.$refreshpage.'" method="POST" name="deletelinksform">
  <input type="hidden" name="action" value="deletelinks" />
  <table width="100%" border="0" cellpadding="5" cellspacing="0">
  <tr>
    <td class="tdrow1">Title</td>
    <td class="tdrow1">Section</td>
    <td class="tdrow1">Author</td>
    <td class="tdrow1">Status</td>
    <td class="tdrow1" width="75">
      <input type="checkbox" checkall="group" onclick="javascript: return select_deselectAll (\'deletelinksform\', this, \'group\');" /> Delete?
    </td>
  </tr>';

  while($link = $DB->fetch_array($getlinks,null,MYSQL_ASSOC))
  {
    $section = $DB->query_first('SELECT name FROM '.$tbl_pre.'sections WHERE sectionid = %d', $link['sectionid']);
    echo '
    <tr>
      <td class="tdrow2">&nbsp;<a href="'.$refreshpage.'&action=displaylinkform&linkid='.$link['linkid'].'&loadwysiwyg=1"><u>'.$link['title'].'</u></a></td>
      <td class="tdrow3">&nbsp;'.$section['name'].'</td>
      <td class="tdrow2">&nbsp;'.$link['author'].'</td>
      <td class="tdrow3">'.($link['activated']=='1' ? '<div style="color:green">Online</div>' : '<div style="color:red"><strong>Offline</strong></div>').'</td>
      <td class="tdrow2">&nbsp;<input type="checkbox" name="linkids[]" value="'.$link['linkid'].'" checkme="group" /></td>
    </tr>';
  }

  echo '
    <tr>
    <td class="tdrow1" bgcolor="#FCFCFC" colspan="5" align="right" style="padding-right: 20px;">
     <input type="submit" value="Delete Links" />
    </td>
  </tr>
  </table>
  </form>';

  EndSection();

} //DisplayLinks


// ############################### PLUGIN MAIN PAGE ############################

function DisplayDefault()
{
  global $DB, $refreshpage, $pluginid;

  // plugin settings
  PrintSection('Settings');
  echo '
  <table width="100%" border="0" cellpadding="5" cellspacing="0">
  <tr><td class="tdrow1" colspan="2">Options</td></tr>
  <tr><td class="tdrow2" width="60%">View and change your settings for the links directory:</td>
      <td class="tdrow3" style="padding-left: 40px;">
      <form method="post" action="'.$refreshpage.'">
      <input type="hidden" name="action" value="displaysettings" />
      <input type="submit" value="Display Settings" />
      </form>
      </td></tr>
  </table>';
  EndSection();

  // links - add/manage
  PrintSection('Links');
  echo '
  <table width="100%" border="0" cellpadding="5" cellspacing="0">
  <tr><td class="tdrow1" colspan="2">Add Link</td></tr>
  <tr><td class="tdrow2" width="60%">Add a new entry to your links directory:</td>
      <td class="tdrow3" style="padding-left: 40px;">
      <form method="post" action="'.$refreshpage.'">
      <input type="hidden" name="action" value="displaylinkform" />
      <input type="hidden" name="loadwysiwyg" value="1" />
      <input type="submit" value="New Link" />
      </form>
      </td></tr>
  <tr><td class="tdrow1" colspan="2">Manage Links</td></tr>
  <tr><td class="tdrow2" width="60%">List and edit a section\'s links:</td>
      <td class="tdrow3" style="padding-left: 40px;">
      <form method="post" action="'.$refreshpage.'">';

  PrintSectionSelectionEx();

  echo '
      <input type="hidden" name="action" value="displaylinks" />
      <input type="submit" value="View Links" />
      </form>
      </td></tr>
  </table>';
  EndSection();

  // sections - add/manage
  PrintSection('Sections');
  echo '
  <table width="100%" border="0" cellpadding="5" cellspacing="0">
  <tr><td class="tdrow1" colspan="2">Add Section</td></tr>
  <tr>
    <td class="tdrow2" width="60%">You can organize your links into many
    leveles of different sections.<br />
    Notes: The default section is "(root)" and cannot be deleted.
    Inactive (hidden) sections are marked with a "*".
    </td>
      <td class="tdrow3" style="padding-left: 40px;">
        <form method="post" action="'.$refreshpage.'">
        <input type="hidden" name="action" value="displaysectionform" />
        <input type="hidden" name="loadwysiwyg" value="1" />
        <input type="submit" value="Create New Section" />
        </form>
      </td>
  </tr>
  <tr><td class="tdrow1" colspan="2">Edit Section</td></tr>
  <tr>
    <td class="tdrow2" width="60%">Edit a section here:</td>
      <td class="tdrow3" style="padding-left: 40px;">
      <form method="post" action="'.$refreshpage.'">
      <input type="hidden" name="action" value="displaysectionform" />
      <input type="hidden" name="loadwysiwyg" value="1" />
      ';

  PrintSectionSelection('sectionid', '');

  echo '
      <input type="submit" value="Edit Section" />
      </form>
      </td>
    </tr>
  </table>';
  EndSection();

  DisplayLinks('Latest Links');

} //DisplayDefault


// ######################## ACTION SELECTOR / VARIABLES ########################

// get values; "GetVar" in "globalfunctions.php" (2.5+)
$action       = GetVar('action','','string');
$sectionid    = GetVar('sectionid', 0, 'whole_number');
$linkid       = GetVar('linkid', 0, 'whole_number');
$activated    = GetVar('activated', 0, 'whole_number');
$allowsmilies = GetVar('allowsmilies', 0, 'whole_number');
$showauthor   = GetVar('showauthor', 0, 'whole_number');
$author       = GetVar('author', '');
$name         = GetVar('name', '');
$title        = GetVar('title', '');
$url          = GetVar('url', '');
$description  = GetVar('description', '');
$deletelink   = GetVar('deletelink', 0, 'whole_number');
$parentid     = GetVar('parentid', 1, 'whole_number');
$sorting      = GetVar('sorting', '');
$thumbnail    = GetVar('thumbnail', '');

switch($action)
{
  case 'insertlink':
    InsertLink($sectionid, $activated, $allowsmilies, $showauthor, $author, $title, $url, $description, $thumbnail);
  break;

  case 'updatelink':
    UpdateLink($deletelink, $linkid, $sectionid, $activated, $allowsmilies, $showauthor, $author, $title, $url, $description, $thumbnail);
  break;

  case 'deletelinks':
    DeleteLinks();
  break;

  case 'deletelink':
    DeleteLink($linkid);
  break;

  case 'insertsection':
    InsertSection($parentid, $activated, $name, $description, $sorting);
  break;

  case 'updatesection':
    UpdateSection($sectionid, $parentid, $activated, $name, $description, $sorting);
  break;

  case 'displaylinkform':
    DisplayLinkForm($linkid);
  break;

  case 'displaysectionform':
    DisplaySectionForm($sectionid);
  break;

  case 'displaylinks':
    DisplayLinks($sectionid);
  break;

  case 'displaysettings':
    DisplaySettings();
  break;

  default:
    DisplayDefault();
}