<?php
if(!defined('IN_PRGM') || !function_exists('sd_GetCurrentFolder')) return false;

if(!class_exists('LinkDirectoryClass'))
{
class LinkDirectoryClass
{
  private $pluginid  = 16;
  private $phrases   = array();
  private $settings  = array();
  private $tbl_pre   = '';
  private $prefix    = '';
  private $isAdmin   = false;
  private $sectionid = 0;
  private $secError  = 0;
  private $errors    = array();

  public function LinkDirectoryClass($plugin_folder)
  {
    if($this->pluginid = GetPluginIDbyFolder($plugin_folder))
    {
      global $userinfo;
      $this->phrases  = GetLanguage($this->pluginid);
      $this->settings = GetPluginSettings($this->pluginid);
      $this->prefix   = 'p'.$this->pluginid.'_';
      $this->tbl_pre  = PRGM_TABLE_PREFIX.$this->prefix;
      $this->isAdmin  = !empty($userinfo['loggedin']) &&
                        (!empty($userinfo['adminaccess']) ||
                         (!empty($userinfo['pluginadminids']) && @in_array($this->pluginid, $userinfo['pluginadminids'])) ||
                         (!empty($userinfo['admin_pages']) && @in_array('plugins', $userinfo['admin_pages'])));

    }
  } //LinkDirectoryClass

  // ########################## CHECK USER INPUT ##############################

  private function _checkInput(&$input, $enabled=true, $required=true, $type='text',
                               $minLen=0, $maxLen=0, $errMsg='', $noHtml=true)
  {
    $res = true;
    if(!empty($enabled))
    {
      if(!empty($required) && empty($input))
      {
        $this->errors[] = $errMsg;
        $res = false;
      }
      else
      {
        if((!empty($required) && (strlen($input) < $minLen)) ||
           (!empty($maxLen) && (strlen($input) > $maxLen)) ||
           (strlen($input) && ($type=='url') && !sd_check_url($input)) ||
           (strlen($input) && ($type=='img-url') && !sd_check_image_url($input))
           )
        {
          $this->errors[] = $errMsg;
          $res = false;
        }
        else
        {
          if(!UserinputSecCheck($input)) $this->secError = 1;
        }
      }
    }
    else
    if(empty($enabled) && !empty($input)) // invalid form data!
    {
      $this->secError = 1;
      $res = false;
    }

    if($res && !empty($noHtml))
    {
      $inp_old = $input;
      $input = strip_tags($input);
      if($input !== $inp_old)
      {
        $this->errors[] = $errMsg;
        $res = false;
      }
    }
    return $res;
  } //_checkInput

  // ######################## RETURN HONEYTRAP NAME ###########################

  private function _GetHoneytrap()
  {
    return substr(md5(md5(USER_AGENT.'||'.USERIP)),0,8);
  }

  // ########################### RETURN MENU OUTPUT ###########################

  public function GetMenu($sectionid, $currsectionid)
  {
    global $DB, $categoryid;

    if($getsection = $DB->query('SELECT sectionid, parentid, name FROM '.$this->tbl_pre.'sections'.
                                ' WHERE sectionid = %d', $sectionid))
    {
      $section = $DB->fetch_array($getsection,null,MYSQL_ASSOC);
      while($sectionid != 1)
      {
        $sectionid = $this->GetMenu($section['parentid'], $currsectionid);
      }

      if($section['sectionid'] == $currsectionid)
      {
        echo $section['name'];
      }
      else
      {
        echo '<a href="'.
             RewriteLink('index.php?categoryid='.$categoryid.'&'.$this->prefix.
                         'sectionid='.(int)$section['sectionid']) .
             '">'.$section['name'].'</a> &raquo; ';
      }
    }
    return $sectionid;

  } //GetMenu

  // ############################## INSERT LINK ###############################

  public function InsertLink()
  {
    global $DB, $mainsettings, $sdlanguage, $userinfo;

    $p16_author      = trim(GetVar($this->prefix.'author', false, 'string', true, false));
    $p16_title       = trim(GetVar($this->prefix.'title', false, 'string', true, false));
    $p16_url         = trim(GetVar($this->prefix.'url', false, 'string', true, false));
    $p16_description = trim(GetVar($this->prefix.'description', false, 'string', true, false));
    $this->errors    = array();
    $this->secError  = false;

    //v3.4.6 - check honeytrap against bots
    $trapname = $this->prefix.$this->_GetHoneytrap();
    $p16_honeytrap   = isset($_POST[$trapname]) ? $_POST[$trapname] : false;
    if(!empty($p16_honeytrap))
    {
      DisplayMessage($this->phrases['error_invalid_form'],true);
      return false;
    }

    // did guest enter an author name?
    if(!empty($userinfo['loggedin']) && !empty($userinfo['userid']))
    {
      if(empty($userinfo['username']))
        $this->secError |= 1;
      else
        $p16_author = $DB->escape_string($userinfo['username']);
    }
    else
    {
      $this->_checkInput($p16_author, !empty($this->settings['author_name_input']),
                         !empty($this->settings['author_name_required']),
                         'text', 5, 200, $this->phrases['enter_name']);
    }

    // did user enter a title for the link?
    $this->_checkInput($p16_title, !empty($this->settings['website_name_input']),
                       !empty($this->settings['website_name_required']),
                       'text', 2, 200, $this->phrases['enter_site_name']);

    // did user enter a link?
    if(!empty($p16_url) && (substr($p16_url, -1) == '/')) $p16_url = substr($p16_url, 0, -1);
    $this->_checkInput($p16_url, true, true, 'url', 5, 200, $this->phrases['url_invalid']);

    // did user enter a description?
    $this->_checkInput($p16_description, !empty($this->settings['link_description_input']),
                       !empty($this->settings['link_description_required']),
                       'text', 2, 0, $this->phrases['enter_description']);

    $p16_thumbnail = GetVar($this->prefix.'thumbnail', '', 'string', true, false);
    $this->_checkInput($p16_thumbnail, !empty($this->settings['allow_user_thumbnail']),
                       false, 'img-url', 5, 200, $this->phrases['thumb_url_error']);

    // SD322: Check if either VVC or reCaptcha is correct
    if($this->secError)
    {
      DisplayMessage($this->phrases['error_invalid_form'],true);
      return false;
    }

    //v3.4.6: support for SFS
    if(empty($this->errors) && !empty($this->settings['enable_sfs_antispam']) && function_exists('sd_sfs_is_spam'))
    {
      if(sd_sfs_is_spam('',USERIP))
      {
        $this->errors[] = $this->phrases['sfs_error'];
      }
    }

    //v3.4.6: support for several blocklist providers
    $blacklisted = false;
    if(empty($this->errors) && function_exists('sd_reputation_check'))
    {
      $blacklisted = sd_reputation_check(USERIP, $this->pluginid);
      if($blacklisted !== false)
      {
        $this->errors[] = trim($sdlanguage['ip_listed_on_blacklist'].' '.USERIP);
      }
    }

    // SD322: Check if either VVC or reCaptcha is correct
    if(empty($this->errors) && !CaptchaIsValid())
    {
      $this->errors[] = $sdlanguage['captcha_not_valid'];
    }

    //v3.4.6: check for duplicates in section
    if(empty($this->settings['allow_duplicate_links']))
    {
      if(empty($p16_title))
        $exists = $DB->query_first('SELECT linkid FROM '.$this->tbl_pre.'links'.
                                   " WHERE sectionid = %d AND url = '%s'",
                                   $this->sectionid, $p16_url);
      else
        $exists = $DB->query_first('SELECT linkid FROM '.$this->tbl_pre.'links'.
                                   " WHERE sectionid = %d AND ((trim(title) = '%s') OR (trim(url) = '%s'))",
                                   $this->sectionid, $p16_title, $p16_url);
      if(!empty($exists['linkid']))
      {
        $this->errors[] = $this->phrases['error_duplicate_entry'];
      }
    }

    if(!empty($this->errors))
    {
      DisplayMessage($this->errors,true);

      $this->SubmitLink($this->sectionid);
      return false;
    }

    // Note: the sectionid is checked at the bottom of script!
    $approved = $this->isAdmin || !empty($this->settings['auto_approve_links']) ? 1 : 0;
    if($DB->query('INSERT INTO '.$this->tbl_pre."links VALUES (NULL, %d, %d, 1, 1, '%s', '%s', '%s', '%s', '%s', '%s')",
                  $this->sectionid, $approved, $p16_author, $p16_title, $p16_url, $p16_description, $p16_thumbnail, USERIP))
    {
      if(!empty($this->settings['link_notification']) && ($email = $this->settings['link_notification']))
      {
        // obtain emails
        $getemails = str_replace(',', ' ', $email);
        $getemails = preg_replace('/\s\s+/m', ' ', $getemails);
        $getemails = trim($getemails);
        if(strlen($getemails))
        {
          $emails   = @explode(" ", $getemails);

          $fullname = $this->phrases['notify_email_from'];
          $subject  = $this->phrases['notify_email_subject'];
          $message  = $this->phrases['notify_email_message'] . EMAIL_CRLF;
          $message .= $this->phrases['notify_email_author'] . ' - ' . $p16_author . EMAIL_CRLF;
          $message .= $this->phrases['notify_email_website'] . ' - ' . $p16_title . EMAIL_CRLF;
          $message .= $this->phrases['notify_email_url'] . ' - ' . $p16_url . EMAIL_CRLF;
          $message .= $this->phrases['notify_email_description'] . ' - ' . $p16_description . EMAIL_CRLF;
          if(!empty($this->settings['allow_user_thumbnail']) && (strlen($p16_thumbnail) > 4))
          {
            $message .= $this->phrases['notify_email_thumbnail'] . ' - ' . htmlspecialchars($p16_thumbnail) . EMAIL_CRLF;
          }

          for($i = 0, $ec = count($emails); $i < $ec; $i++)
          {
            SendEmail($emails[$i], $subject, $message, $fullname);
          }
        }
      }
    }

    #echo $this->phrases['link_submitted'] . '<br /><br />';
    #$this->DisplayLinks();
    RedirectFrontPage(RewriteLink('index.php?categoryid='.PAGE_ID),$this->phrases['link_submitted']);

  } //InsertLink


  // ################################ SUBMIT LINK ################################

  public function SubmitLink()
  {
    global $categoryid, $mainsettings, $userinfo, $inputsize, $sdlanguage;

    echo $this->phrases['sections'] . ' ';
    $this->GetMenu($this->sectionid, $this->sectionid);
    echo ' - ' . $this->phrases['submitting_link'] . '<br /><br />';

    echo '
      <div id="'.$this->prefix.'form">
      <form method="post" action="' . RewriteLink('index.php?categoryid='.$categoryid.'&'.$this->prefix.'action=insertlink').'">
      <input type="hidden" name="'.$this->prefix.'sectionid" value="'.$this->sectionid.'" />
      ';
    if($userinfo['loggedin'])
    {
      echo '
      <input type="hidden" name="'.$this->prefix.'author" value="'.htmlspecialchars($userinfo['username'],ENT_COMPAT).'" />';
    }
    echo '
      <table border="0" cellspacing="0" cellpadding="0" summary="layout" width="100%">';

    if(empty($userinfo['loggedin']) && !empty($this->settings['author_name_input']))
    {
      $author = GetVar($this->prefix.'author', '', 'string', true, false);
      echo '
      <tr>
        <td style="width: 100px;">' . $this->phrases['your_name'] . '</td>
        <td>
          <input size="'.$inputsize.'" type="text" name="'.$this->prefix.'author" value="'.htmlspecialchars($author,ENT_COMPAT).'" maxlength="200" style="width:90%" />
        </td>
      </tr>';
    }

    $url   = GetVar($this->prefix.'url', '', 'string', true, false);
    if(!empty($this->settings['website_name_input']))
    {
      $title = GetVar($this->prefix.'title', '', 'string', true, false);
      echo '
      <tr>
        <td style="width: 100px;">' . $this->phrases['website_name'] . '</td>
        <td>
          <input size="'.$inputsize.'" type="text" name="'.$this->prefix.'title" value="'.htmlspecialchars($title,ENT_COMPAT).'" maxlength="200" style="width:90%" /></td>
      </tr>';
    }
    echo '
      <tr>
        <td style="width: 100px;">' . $this->phrases['website_url'] . '</td>
        <td>
          <input size="'.$inputsize.'" type="text" name="'.$this->prefix.'url" value="' .htmlspecialchars($url,ENT_COMPAT).'" maxlength="512" style="width:90%" />
        </td>
      </tr>
      ';
    if(!empty($this->settings['allow_user_thumbnail']))
    {
      $thumbnail = GetVar($this->prefix.'thumbnail', '', 'string', true, false);
      echo '
      <tr>
        <td style="width: 100px;">' . $this->phrases['thumbnail_url'] . '</td>
        <td>
          <input size="'.$inputsize.'" type="text" name="'.$this->prefix.'thumbnail" value="' .htmlspecialchars($thumbnail,ENT_COMPAT).'" maxlength="512" style="width:90%" />
        </td>
      </tr>
      ';
    }
    if(!empty($this->settings['link_description_input']))
    {
      $description = GetVar($this->prefix.'description', '', 'string', true, false);
      echo '
      <tr>
        <td style="width: 100px;">' . $this->phrases['description'] . '</td>
        <td>
          <textarea name="'.$this->prefix.'description" cols="40" rows="5" style="width:90%">'.htmlentities($description).'</textarea></td>
      </tr>
      ';
    }

    // SD322: captcha row
    $captcha_display = DisplayCaptcha(false, 'p16');
    if(!empty($captcha_display))
    {
      echo '
      <tr>
        <td>&nbsp;</td>
        <td align="left" class="link-submit-captcha">
          '.$captcha_display.'
        </td>
      </tr>
      <tr>';
    }
    echo '
        <td>&nbsp;</td>
        <td align="left" class="link-submit-cell">
        <input size="'.$inputsize.'" type="text" maxlength="10" name="'.$this->prefix.$this->_GetHoneytrap().'" value="" style="display:none !important;width:0 !important;height:0 !important;" />
        <input type="submit" name="'.$this->prefix.'Submit" value="'.htmlspecialchars(strip_tags($this->phrases['submit_link']),ENT_COMPAT).'" /></td>
      </tr>
      </table>
      </form>
      </div>
      ';

  } //SubmitLink


  // ######################## GET SECTION LINK COUNT ##########################

  public function GetSectionLinkCount($sectionid, $linkcount)
  {
    global $DB;

    // get total links of the section
    if($getlinkcount = $DB->query_first('SELECT COUNT(*) linkcount FROM '.$this->tbl_pre.'links WHERE sectionid = %d AND activated = 1', $sectionid))
    {
      $linkcount += (int)$getlinkcount['linkcount'];
    }

    // are there any sub-sections?
    if($getsubsections = $DB->query('SELECT sectionid FROM '.$this->tbl_pre.'sections WHERE parentid = %d', $sectionid))
    while(!empty($getsubsections) && ($subsection = $DB->fetch_array($getsubsections,null,MYSQL_ASSOC)))
    {
      $linkcount = $this->GetSectionLinkCount($subsection['sectionid'], $linkcount);
    }

    return $linkcount;

  } //GetSectionLinkCount


  // ############################# DISPLAY LINKS ##############################

  public function DisplayLinks($start=0)
  {
    global $DB, $categoryid, $userinfo;

    echo '
    <div class="link-directory-container" id="p'.$this->pluginid.'-container">
    ';
    // show menu ?
    if($this->settings['display_menu'])
    {
      echo '<div class="link-directory-menu">'.$this->phrases['sections'] . ' ';
      $this->GetMenu($this->sectionid, $this->sectionid);
      echo '<br /></div>';
    }

    $isadmin = !empty($userinfo['adminaccess']) ||
               (!empty($userinfo['pluginsubmitids']) && @in_array($this->pluginid, $userinfo['pluginsubmitids']));
    $allowsubmit = $isadmin || !empty($userinfo['pluginsubmitids']) && @in_array($this->pluginid, $userinfo['pluginsubmitids']);

    // display 'submit link'?
    if($allowsubmit)
    {
      echo '
      <div class="link-directory-submit">
      <a href="' . RewriteLink('index.php?categoryid='.$categoryid.'&'.$this->prefix.'sectionid='.$this->sectionid.
                               '&'.$this->prefix.'action=submitlink').'">'.$this->phrases['submit_a_link'].'</a><br />
      </div>
      ';
    }

    // variable for marking DOM elements with a unique id:
    $p16_id = -1;
    // string with built list of links for each DOM element id:
    $p16_js_links = '';

    // get sort type of current section
    $getsorttype = $DB->query_first('SELECT sorting FROM '.$this->tbl_pre.'sections WHERE sectionid = %d', $this->sectionid);
    $sorttype    = $getsorttype['sorting'];

    switch($sorttype)
    {
      case 'Alphabetically A-Z':
        $order = 'title ASC';
        $order_s = 'name ASC';
      break;

      case 'Alphabetically Z-A':
        $order = 'title DESC';
        $order_s = 'name DESC';
      break;

      case 'Author Name A-Z':
        $order = 'author ASC';
        $order_s = 'name ASC';
      break;

      case 'Author Name Z-A':
        $order = 'author DESC';
        $order_s = 'name DESC';
      break;

      case 'Oldest First':
        $order = 'linkid ASC';
        $order_s = 'sectionid ASC';
      break;

      default:
        $order = 'linkid DESC';  // Newest First
        $order_s = 'sectionid DESC';
    }

    $row_height = (int)$this->settings['link_row_height'];
    $row_height = (empty($row_height) || ($row_height < 1))? '' : ('height:'.$row_height.'px;');

    // get subsections of sectionid
    $getsubsections = $DB->query('SELECT * FROM '.$this->tbl_pre.'sections'.
                                 ' WHERE parentid = %d AND activated = 1 ORDER BY %s',
                                 $this->sectionid, $order_s);
    if(!empty($getsubsections) && ($rowcount = $DB->get_num_rows($getsubsections)))
    {
      echo '
      <table cellpadding="0" cellspacing="0" class="link-directory-sections" summary="layout">
      <tr>';

      // display subsections
      while($subsection = $DB->fetch_array($getsubsections,null,MYSQL_ASSOC))
      {
        $numsectionlinks = $this->GetSectionLinkCount($subsection['sectionid'], 0);

        $link = RewriteLink('index.php?categoryid='.$categoryid.'&'.$this->prefix.'sectionid='.(int)$subsection['sectionid'],false);
        // Add link to list of links for JavaScript output:
        $p16_id++;
        $p16_js_links .= '  '.$this->prefix.'links[' . $p16_id . '] = new Array("' . $link . '","'.
          str_replace('%s',addslashes($subsection['name']),$this->phrases['goto_section'])."\", 0);\n";

        echo '
        <td id="'.$this->prefix.'links_'.$p16_id.'" style="width:100%;'.$row_height.'">
          <span class="linksectiontitle"><a href="'.$link.'">'.$subsection['name'].'</a></span><br />
          <span class="linksectiondescr">'.unhtmlspecialchars($subsection['description']).'</span>
        </td>';

        if($numsectionlinks > 0)
        {
          echo '
          <td class="link-directory-cell" nowrap="nowrap">
            <i><strong>' . $numsectionlinks . '</strong> '.$this->phrases['count_suffix'].'</i>
          </td>';
        }
        else
        {
          echo '<td>&nbsp;</td>';
        }

        echo '
        </tr>
        ';

      } //while

      echo '
      </table>
      <hr />
      ';
    }

    $rows = 0;
    if($rows = $DB->query_first('SELECT COUNT(linkid) linkcount'.
                                 ' FROM '.$this->tbl_pre.'links WHERE sectionid = %d AND activated = 1',
                                 $this->sectionid))
    {
      $rows = (int)$rows['linkcount'];
    }

    $Links_per_Page = Is_Valid_Number($this->settings['links_per_page'],5,1,9999);
    $Links_per_Row  = Is_Valid_Number($this->settings['number_of_links_per_row'],2,1,6);
    $start = Is_Valid_Number($start,1,1,999999);
    if($start > $rows) $start = 0;

    $getlinks = $DB->query('SELECT * FROM '.$this->tbl_pre.'links WHERE sectionid = %d AND activated = 1'.
                           ' ORDER BY ' . $order . ' LIMIT '.($start>0?($start-1):0).', ' . $Links_per_Page,
                           $this->sectionid);

    $curcol = 0; // current column

    echo '
    <table border="0" class="link-directory-links" cellpadding="0" cellspacing="0">
    ';
    $colwidth = '15%';
    $colwidth_list = array(
      1 => '100%',
      2 => '50%',
      3 => '33%',
      4 => '25%',
      5 => '20%',
      6 => '16%'
    );
    // If there are less entries than configured columns, reset config:
    if($rows < $Links_per_Row)
    {
      $Links_per_Row = $rows;
    }
    if(isset($colwidth_list[$Links_per_Row]))
    {
      $colwidth = $colwidth_list[$Links_per_Row];
    }
    unset($colwidth_list);

    // ********** Use simple layout or not??? **********
    if(!empty($this->settings['simple_display_layout']))
    {
      // *** Simple Layout (pre-2.5.x) ***
      // display links
      for($i = 0; $i < $rows AND $i < $Links_per_Page; $i++)
      {
        $link = $DB->fetch_array($getlinks,null,MYSQL_ASSOC);

        if($curcol == 0) echo "\n      <tr>";

        echo '
        <td style="width:'.$colwidth.'">';

        if(!empty($this->settings['website_name_input']))
        {
          if(!empty($link['url']))
          {
            echo '<div style="margin-bottom: 0px;">';
            echo '<a href="'.$link['url'].'" rel="nofollow" target="_blank">'.$link['title'].'</a>';
            echo '</div>';
          }
          else
            echo '&nbsp;';
        }

        if(!empty($this->settings['allow_user_thumbnail']) && !empty($link['thumbnail']))
        {
          echo '<div style="text-align:center"><a alt="" href="'.$link['url'].'" rel="nofollow" target="_blank"><img src="'.
                $link['thumbnail'] . '" title="'. $link['title'] . '" /></a></div>';
        }
        if(!empty($this->settings['author_name_input']) && !empty($link['showauthor']))
          echo '<br />' . $this->phrases['submitted_by'] . ' ' . $link['author'];

        if(!empty($this->settings['link_description_input']) && !empty($link['showauthor']))
          echo '<br />'.$link['description'];

        echo '</td>';

        $curcol++;

        if($curcol == ($Links_per_Row))
        {
          echo "\n       </tr>";
          $curcol = 0;
        }

      }

      if($curcol != $Links_per_Row)
        echo '<td colspan="' . ($Links_per_Row - $curcol) . '">&nbsp;</td>
      </tr>';

      echo '
      </table>';
    }
    else
    {
      // *** Advanced Layout ***
      for($i = 0; $i < $rows AND $i < $Links_per_Page; $i++)
      {
        $link = $DB->fetch_array($getlinks,null,MYSQL_ASSOC);

        if($curcol == 0) echo '<tr>';

        // Add link to list of links for JavaScript output:
        $p16_id++;
        $p16_js_links .= '  '.$this->prefix.'links['.$p16_id.'] = new Array("'.$link['url'].'","'.
                         str_replace('%s',addslashes($link['url']), $this->phrases['goto_link'])."\", 1);\n";

        echo '
          <td id="'.$this->prefix.'links_'.$p16_id.'"'.($row_height?' style="'.$row_height.'"':'').' style="width:'.$colwidth.'">
          <table cellpadding="0" cellspacing="0" style="width:100%;'.$row_height.'">';
        if(!empty($this->settings['allow_user_thumbnail']))
        {
          echo '
            <tr>
              <td colspan="'.(!empty($this->settings['author_name_input'])?2:1).'" style="'.$row_height.'vertical-align:top; padding: 0px;">
              ';
          if(!empty($link['thumbnail']))
          {
            echo '<div align="center"><a href="'.$link['url'].
                 '" rel="nofollow" target="_blank"><img alt="" src="'.$link['thumbnail'].'"';
            if(!empty($this->settings['website_name_input']))
            {
              echo ' title="'.$link['title'].'"';
            }
            echo ' /></a></div>';
          }
          echo '</td>
            </tr>';
        }
        echo '
            <tr>
              <td style="'.$row_height.'vertical-align:top; padding: 0px;">';

        if(!empty($this->settings['website_name_input']))
        {
          echo '<a class="linklinktitle" id="'.$this->prefix.$i.'" rel="nofollow" href="'.$link['url'].'">' . $link['title'] . '</a>';
        }
        else
        {
          echo '<a class="linklinktitle" id="'.$this->prefix.$i.'" rel="nofollow" href="'.$link['url'].'">' . $link['url'] . '</a>';
        }

        if(!empty($this->settings['link_description_input']) && strlen($link['description']))
        {
          echo '<br /><span class="linklinkdescr"> ' . unhtmlspecialchars($link['description']) . '</span>';
        }
        echo '</td>';

        if(!empty($this->settings['author_name_input']) && !empty($link['showauthor']))
        {
          echo '<td style="width:145px; '.$row_height.'vertical-align:top; padding: 4px;" nowrap="nowrap"><center><strong>'.
                  $this->phrases['submitted_by'].'</strong> <br /><i>' . $link['author'] . '</i></center></td>';
        }

        echo'
            </tr>
            </table>
          </td>';

        $curcol++;

        if($curcol == $Links_per_Row)
        {
          echo '
          </tr>
          ';
          $curcol = 0;
        }

      } //for

      if($curcol AND ($curcol != $Links_per_Row))
      {
        echo '<td colspan="' . ($Links_per_Row - $curcol) . '">&nbsp;</td></tr>';
      }

      echo '
      </table>

<script type="text/javascript">
//<![CDATA[
  var '.$this->prefix.'links = new Array();

  '.$p16_js_links.'

  function '.$this->prefix.'onLoad()
  {
    // Process anchor tags for links and their <td> table cell:
    for(i=0; i < '.$this->prefix.'links.length; i++)
    {
      // Deactivate click on link
      ah = jQuery("#p'.$this->pluginid.'"+i);
      if ((typeof(ah) !== undefined)) {
        ah.href = "#";
        jQuery(ah).click(function(){ return false; });
      }

      // Assign click and mouse events to <td> tag:
      ah = jQuery("#'.$this->prefix.'links_"+i);
      if ((typeof(ah) !== undefined)) {
        jQuery(ah).click(function() {
          linkelement = jQuery(this).find("a:first").attr("href");
          if (typeof(linkelement) !== undefined) {
            if(linkelement.indexOf(sdurl) == -1){
              window.open(linkelement,"_blank","status=1,toolbar=1,location=1,menubar=1,directories=1,resizable=1,scrollbars=1");
            } else {
              location.href = linkelement;
            }
          }
          return false;
        });
        ';
      if(!empty($this->settings['enable_hover_effect']))
      {
        echo "
        jQuery(ah).hover(
          function() {
            jQuery(this).css('cursor','hand');
            jQuery(this).css('background-color','".$this->settings['background_hover_colour']."');
            },
          function() {
            jQuery(this).css('cursor','pointer');
            jQuery(this).css('background-color','transparent');
            }
          );
        ";
      }
      echo '
      }
    }
  }
  jQuery(document).ready(function() {
    '.$this->prefix.'onLoad();
  });
//]]>
</script>';
    }

    // multiple pages
    if(($start > 0) && ($rows > $Links_per_Page))
    {
      echo '
      <br /><hr /><br />
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
      <tr>';
      if(($start - $Links_per_Page) > 0)
      {
        echo '
        <td><a href="'.
          RewriteLink('index.php?categoryid='.$categoryid.
            '&'.$this->prefix.'sectionid='.$this->sectionid.
            '&'.$this->prefix.'start='.($start - $Links_per_Page)). '">'.
          $this->phrases['previous_links'].'</a></td>';
      }

      if(($start + $Links_per_Page) < $rows)
      {
        $start += $Links_per_Page;
        echo '
        <td align="right"><a href="'.
          RewriteLink('index.php?categoryid='.$categoryid.
            '&'.$this->prefix.'sectionid='.$this->sectionid.
            '&'.$this->prefix.'start='.$start).'">'.
          $this->phrases['more_links'].'</a></td>';
      }

      echo '
      </tr>
      </table>
      ';
    }

    echo '</div>';

  } //DisplayLinks


  // ############################# MAIN FUNCTION ##############################

  public function DisplayLinkDirectory()
  {
    global $DB, $userinfo;

    if(empty($userinfo['pluginviewids']) || !in_array($this->pluginid,$userinfo['pluginviewids']))
    {
      return false;
    }
    $p16_action      = GetVar($this->prefix.'action', '', 'string');
    $this->sectionid = GetVar($this->prefix.'sectionid', 1, 'whole_number');
    $p16_start       = GetVar($this->prefix.'start', 0, 'natural_number');

    if(!$DB->query_first('SELECT 1 FROM '.$this->tbl_pre.'sections WHERE sectionid = %d AND activated = 1',$this->sectionid))
    {
      DisplayMessage($this->phrases['error_invalid_section']);
    }
    else
    {
      $p16_submitok = !empty($userinfo['pluginsubmitids']) && @in_array($this->pluginid, $userinfo['pluginsubmitids']);

      if($p16_action == 'submitlink' && $p16_submitok)
      {
        $this->SubmitLink($this->sectionid);
      }
      else if($p16_action == 'insertlink' && $p16_submitok)
      {
        $this->InsertLink($this->sectionid);
      }
      else
      {
        $this->DisplayLinks($p16_start);
      }
    }
  }
} //end of class
} //DO NOT REMOVE!
