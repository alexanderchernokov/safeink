<?php
if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;
defined('SD_ARTICLE_FEATUREDPICS_DIR') || define('SD_ARTICLE_FEATUREDPICS_DIR', 'images/featuredpics/');
defined('SD_ARTICLE_THUMBS_DIR') || define('SD_ARTICLE_THUMBS_DIR', 'images/articlethumbs/');

if(!class_exists('ArticlesSettingsClass'))
{
class ArticlesSettingsClass
{
  public  $action             = false;
  public  $authormode         = false;
  public  $customsearch       = false;
  public  $search             = false;
  public  $pluginid           = 0;
  public  $p_prefix           = '';
  public  $globalsettings     = 0;
  public  $language           = array();
  public  $phrases            = array(); //SD360
  public  $settings           = array();
  public  $table_name         = '';

  //SD360: attachments related
  public  $attach_path        = '';
  public  $attach_path_ok     = false;
  public  $attach_path_exists = false;
  public  $attach_extentions  = '';
  public  $attach_max_size    = 0;

  private $_quoted_url_ext    = '';
  private $_link              = false;
  private $HasAdminRights     = false; //SD344 / v3.5.1
  private $IsSiteAdmin        = false; //SD360
  private $IsAdmin            = false; //SD360
  private $CanAttachFiles     = false; //SD360
  private $_folder            = '';    //SD360
  private $_attachment        = null;  //SD360
  private $_pagecount         = 0;     //SD360
  private $_limitpages        = false; //SD360

  // Article Bitfield Settings (static, so use "self::$articlebitfield"!)
  public static $articlebitfield = array(
    'useglobalsettings'       => 1,
    'displayonline'           => 2,
    'displaytitle'            => 4,
    'displayauthor'           => 8,
    'displaycreateddate'      => 16,
    'displayupdateddate'      => 32,
    'displayprintarticlelink' => 64,
    'displayemailarticlelink' => 128,
    'displaydescription'      => 256,
    'displaysmilies'          => 512,
    'displaycomments'         => 1024,
    'displayviews'            => 2048,
    'displaymainpage'         => 4096,
    'displaysticky'           => 8192,
    'displayratings'          => 16384,
    'displaysocialmedia'      => 32768,
    'displaytags'             => 65536,
    'displaypdfarticlelink'   => 131072,
    'displayaspopup'          => 262144, //SD342
    'ignoreexcerptmode'       => 524288, //SD342
    'linktomainpage'          => 1048576, //SD343
    );

  function ArticlesSettingsInit($pluginid)
  {
    global $DB, $mainsettings, $userinfo;

    $this->pluginid = empty($pluginid) ? 2 : (int)$pluginid;
    $this->_folder = sd_GetCurrentFolder(__FILE__);
    $this->_quoted_url_ext = preg_quote($mainsettings['url_extension'],'#');
    $this->_link = 'articles.php?pluginid='.$this->pluginid;

    // Get plugin's language phrases and settings
    $this->language = LoadAdminPhrases(2 /* admin page id */, $this->pluginid);
    $this->phrases = GetLanguage($this->pluginid); //SD360
    $this->settings = GetPluginSettings($this->pluginid);
    $this->p_prefix = 'p'.$this->pluginid.'_';
    $this->table_name = PRGM_TABLE_PREFIX.$this->p_prefix.'news';

    //SD360: check attachments path
    $this->attach_path = '';
    $this->attach_path_ok = $this->attach_path_exists = false;
    if(isset($this->settings['attachments_upload_path']) &&
       strlen(trim($this->settings['attachments_upload_path'])))
    {
      $this->attach_path = trim($this->settings['attachments_upload_path']);
      $this->attach_path_exists = @is_dir(ROOT_PATH.$this->attach_path);
      $this->attach_path_ok = $this->attach_path_exists &&
                              @is_writable(ROOT_PATH.$this->attach_path);
    }
    $this->attach_extentions = (empty($this->settings['valid_attachment_types'])?'':(string)$this->settings['valid_attachment_types']);
    $this->attach_max_size   = (empty($this->settings['attachments_max_size'])?0:(int)$this->settings['attachments_max_size']);

    //SD344 / v3.5.1: new flag to identify admin-alike permissions
    $this->IsAdmin        = (!empty($userinfo['pluginadminids']) &&
                             @in_array($this->pluginid, $userinfo['pluginadminids'])) &&
                            (!empty($userinfo['admin_pages']) &&
                             in_array('articles', $userinfo['admin_pages']));
    $this->IsSiteAdmin    = !empty($userinfo['adminaccess']) &&
                            !empty($userinfo['loggedin']) &&
                            !empty($userinfo['userid']);
    $this->HasAdminRights = $this->IsSiteAdmin || $this->IsAdmin;
    $this->authormode     = !$this->HasAdminRights &&
                            !empty($userinfo['authormode']);
    $this->settings['post_to_forum_usergroups'] =
      sd_ConvertStrToArray($this->settings['post_to_forum_usergroups'],',');

    if(!empty($this->settings['article_attachment_usergroups']) &&
       ($this->settings['article_attachment_usergroups'] != '-1'))
    {
      $this->settings['article_attachment_usergroups'] =
      sd_ConvertStrToArray($this->settings['article_attachment_usergroups'],',');
    }
    else
    {
      $this->settings['article_attachment_usergroups'] = array();
    }
    $this->CanAttachFiles = $this->attach_path_ok &&
                            ($this->HasAdminRights ||
                             (!empty($this->settings['article_attachment_usergroups']) &&
                              !empty($userinfo['usergroupids']) &&
                              @array_intersect($userinfo['usergroupids'],
                                               $this->settings['article_attachment_usergroups'])));

    // create global settings
    $this->globalsettings = 0;
    $this->globalsettings += $this->settings['display_title']                  == 1 ?      4 : 0;
    $this->globalsettings += $this->settings['display_author']                 == 1 ?      8 : 0;
    $this->globalsettings += $this->settings['display_creation_date']          == 1 ?     16 : 0;
    $this->globalsettings += $this->settings['display_updated_date']           == 1 ?     32 : 0;
    $this->globalsettings += $this->settings['display_print_link']             == 1 ?     64 : 0;
    $this->globalsettings += $this->settings['display_email_link']             == 1 ?    128 : 0;
    $this->globalsettings += $this->settings['display_description_in_article'] == 1 ?    256 : 0;
    $this->globalsettings += $this->settings['display_comments']               == 1 ?   1024 : 0;
    $this->globalsettings += $this->settings['display_views_count']            == 1 ?   2048 : 0;
    $this->globalsettings += $this->settings['display_on_main_page']           == 1 ?   4096 : 0;
    $this->globalsettings += $this->settings['sticky_article']                 == 1 ?   8192 : 0;
    $this->globalsettings += $this->settings['display_user_ratings']           == 1 ?  16384 : 0;
    $this->globalsettings += $this->settings['display_social_media_links']     == 1 ?  32768 : 0;
    $this->globalsettings += !empty($this->settings['display_tags'])                ?  65536 : 0;
    $this->globalsettings += !empty($this->settings['display_pdf_link'])            ? 131072 : 0;
    $this->globalsettings += !empty($this->settings['display_as_popup'])            ? 262144 : 0; //SD343
    $this->globalsettings += !empty($this->settings['ignore_excerpt_mode'])         ? 524288 : 0; //SD343
    $this->globalsettings += !empty($this->settings['link_to_main_page'])           ?1048576 : 0; //SD343

    $this->action = GetVar('articleaction', '', 'string');
    if(empty($this->action))
    {
      $this->action = GetVar('action', 'displayarticles', 'string');
    }

  } //ArticlesSettingsClass

  public function GetHasAdminRights() { return $this->HasAdminRights; }
  public function GetIsAdmin()        { return $this->IsAdmin; }
  public function GetIsSiteAdmin()    { return $this->IsSiteAdmin; }

  public function HasUserAccessTo($articleid)
  {
    return false;
  }

  // ##########################################################################
  // INIT ATTACHMENT
  // ##########################################################################

  function InitAttachment()
  {
    require_once(SD_INCLUDE_PATH.'class_sd_attachment.php');

    if(!empty($this->_attachment) && ($this->_attachment instanceof SD_Attachment))
    {
      return true;
    }

    $this->_attachment = new SD_Attachment($this->pluginid,'articles');
    $this->_attachment->setValidExtensions($this->attach_extentions);
    $this->_attachment->setMaxFilesizeKB($this->attach_max_size);
    if($this->attach_path_ok)
    {
      $this->_attachment->setStorageBasePath($this->attach_path);
      return true;
    }

    return false;

  } //InitAttachment


  // ##########################################################################
  // PROCESS ATTACHMENT (InsertArticle/UpdateArticle)
  // ##########################################################################

  function ProcessAttachment($articleid=0)
  {
    global $DB, $sdlanguage, $userinfo;

    if(empty($articleid) || !$this->CanAttachFiles)
    {
      return false;
    }

    $result = false;
    $error = false;
    $attachment = isset($_FILES['attachment']) ? $_FILES['attachment'] : false;
    if(!empty($attachment['error']) && in_array((int)$attachment['error'],array(1,2,3,6)))
    {
      $error = '<strong>'.$sdlanguage['upload_err_'.(int)$attachment['error']].'</strong><br />';
    }
    else
    if(!empty($attachment['tmp_name']))
    {
      if($this->InitAttachment())
      {
        $this->_attachment->setUserid($userinfo['userid']);
        $this->_attachment->setUsername($userinfo['username']);
        $this->_attachment->setObjectID($articleid);

        $result = $this->_attachment->UploadAttachment($attachment);
        if(empty($result['id']))
        {
          $error = '<strong>'.$result['error'].'</strong><br />';
        }
        else
        {
          $result = true;
        }
      }
      else
      {
        $error = '<strong>'.$sdlanguage['upload_err_path_incorrect'].'</strong><br />';
      }
      unset($this->_attachment);
    }

    if($error!==false)
    {
      DisplayMessage(array($error),true);
    }
    return $result;

  } //ProcessAttachment


  // ##########################################################################
  // DELETE ATTACHMENT
  // ##########################################################################

  function DeleteAttachment($articleid = null, $attachment_id = null)
  {
    global $DB, $sdlanguage, $userinfo;

    if(!CheckFormToken())
    {
      RedirectPage($this->_link,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
      return;
    }

    if(!empty($articleid) && !empty($attachment_id))
    {
      $articleid = Is_Valid_Number($articleid,0,1);
      $attachment_id = Is_Valid_Number($attachment_id,0,1);
    }
    else
    {
      $articleid = Is_Valid_Number(GetVar('articleid', 0, 'whole_number'),0,1);
      $attachment_id = Is_Valid_Number(GetVar('attachment_id', 0, 'whole_number'),0,1);
    }

    if(!$this->attach_path_ok || empty($articleid) || empty($attachment_id))
    {
      return false;
    }


    if(!$this->InitAttachment())
    {
      return false;
    }

    $this->_attachment->setObjectID($articleid, $attachment_id);
    $att = $this->_attachment->FetchAttachmentEntry($articleid, $attachment_id);

    if(!empty($att))
    {
      if($this->IsSiteAdmin || $this->IsAdmin ||
         (!empty($att['userid']) && ($att['userid']==$userinfo['userid'])))
      {
        if($this->_attachment->DeleteAttachment($attachment_id))
        {
          $this->RedirectPageDefault('<strong>'.$sdlanguage['common_attachment_deleted'].'</strong>');
          return true;
        }
      }
    }

    $this->RedirectPageDefault('<strong>'.$sdlanguage['common_attachment_delete_failed'].'</strong>',true);
    return false;

  } //DeleteAttachment


  // ##########################################################################
  // INIT SEARCH (MUST BE BEFORE FIRST PAGE OUTPUT)
  // ##########################################################################
  function SearchInit()
  {
    global $DB;

    if($this->action=='displayarticlesettings') return; //v3.5.0

    //v3.5.0: new setting to default "Status" filter
    if(empty($this->settings['admin_default_filter_status']))
    {
      $this->settings['admin_default_filter_status'] = 'onlineoffline';
    }
    $this->customsearch = GetVar('customsearch', 0, 'bool');
    $clearsearch = GetVar('clearsearch', 0, 'bool');

    // Restore previous search array from cookie
    $this->search_cookie = isset($_COOKIE[COOKIE_PREFIX . '_p'.$this->pluginid.'_search']) ? $_COOKIE[COOKIE_PREFIX . '_p'.$this->pluginid.'_search'] : false;

    if($clearsearch)
    {
      $this->search_cookie = false;
      $this->customsearch  = false;
    }

    if($this->customsearch)
    {
      $this->search = array(
        'title'   => GetVar('searchtitle', '', 'string', true, false),
        'description'   => GetVar('searchdescription', '', 'string', true, false),
        'author'        => GetVar('searchauthor', '', 'string', true, false),
        'categoryid'    => GetVar('searchcategoryid', '', 'string', true, false),
        'onlineoffline' => GetVar('searchonlineoffline', $this->settings['admin_default_filter_status'], 'string', true, false),
        'limit'         => GetVar('searchlimit', 10, 'integer', true, false),
        'sorting'       => GetVar('searchsorting', 'newest_first', 'string', true, false),
        'usergroup'     => GetVar('searchusergroup', 0, 'natural_number', true, false),
        'isfeatured'    => GetVar('isfeatured', 0, 'bool', true, false),
        'hasthumb'      => GetVar('hasthumb', 0, 'bool', true, false)
      );

      // Store search params in cookie
      sd_CreateCookie('_p'.$this->pluginid.'_search',base64_encode(serialize($this->search)));

    }
    else
    if($this->search_cookie !== false)
    {
      $this->search = unserialize(base64_decode(($this->search_cookie)));
    }

    if(empty($this->search) || !is_array($this->search))
    {
      $this->search = array(
        'title'   => '',
        'article'       => '',
        'description'   => '',
        'author'        => '',
        'categoryid'    => 0,
        'onlineoffline' => $this->settings['admin_default_filter_status'], //'onlineoffline'
        'limit'         => 10,
        'sorting'       => 'newest_first',
        'start'         => 0,
        'usergroup'     => 0,
        'isfeatured'    => 0,
        'hasthumb'      => 0
      );

      // Remove search params cookie
      sd_CreateCookie('_p'.$this->pluginid.'_search', '');
    }

    $this->search['title']         = isset($this->search['title'])         ? (string)$this->search['title'] : '';
    $this->search['description']   = isset($this->search['description'])   ? (string)$this->search['description'] : '';
    $this->search['author']        = isset($this->search['author'])        ? (string)$this->search['author'] : '';
    $this->search['categoryid']    = isset($this->search['categoryid'])    ? Is_Valid_Number($this->search['categoryid'],0,1) : '';
    $this->search['onlineoffline'] = !empty($this->search['onlineoffline']) ? (string)$this->search['onlineoffline'] : $this->settings['admin_default_filter_status'];
    $this->search['limit']         = isset($this->search['limit'])         ? Is_Valid_Number($this->search['limit'],10,5,100) : 10;
    $this->search['sorting']       = (empty($this->search['sorting'])      ? 'newest_first' : $this->search['sorting']);
    $this->search['usergroup']     = (empty($this->search['usergroup'])    ? 0 : (int)$this->search['usergroup']); //SD342
    $this->search['isfeatured']    = !empty($this->search['isfeatured']); //SD362
    $this->search['hasthumb']      = !empty($this->search['hasthumb']); //SD362

  } //SearchInit


  // ##########################################################################
  // DISPLAY DEFAULT
  // ##########################################################################
  function DisplayDefault()
  {
    global $DB;

    $function_name = str_replace('_', '', $this->action);
    $action_array = array('deletearticles','displayarticleform','displayarticles',
                          'displayarticlesearchform',
                          'insertarticle','updatearticle','updatearticles');
    //SD322: access to settings not allowed for author mode
    if(!$this->authormode)
    {
      $action_array[] = 'displayarticlepermissions'; //SD342
      $action_array[] = 'displayarticlesettings';
      $action_array[] = 'displaypagearticlesettings';
      $action_array[] = 'updatepagearticlesettings';
      $action_array[] = 'updatearticlepermissions'; //SD342
      /*
      $tbl = PRGM_TABLE_PREFIX.'slugs';
      $tmp = $DB->query_first("SELECT term_id FROM $tbl WHERE name = 'Tag Archive' AND pluginid = %d LIMIT 1", $this->pluginid);
      if(empty($tmp['term_id']))
      {
        echo 'Slugs not configured!<br />';
      }
      */
    }

    //SD360: flag for pages selectors to return "limited" amount of pages
    if(in_array($function_name, array('displayarticleform','displayarticles')))
    {
      $this->_pagecount = $DB->query_first('SELECT COUNT(categoryid) pagecount'.
                                           ' FROM '.PRGM_TABLE_PREFIX.'categories');
      $this->_limitpages = !empty($this->_pagecount['pagecount']) &&
                           ($this->_pagecount['pagecount'] > 500);
    }

    if(!in_array(strtolower($function_name), $action_array))
    {
      DisplayMessage(AdminPhrase('common_page_access_denied'), true);
    }
    else
    {
      call_user_func(array($this, $function_name));
    }

  } //DisplayDefault


  // ############################################################################
  // UPDATE ARTICLE'S STATUS
  // ############################################################################

  function ChangeArticleStatus() //for ajax operation!
  {
    global $DB, $SDCache, $sdlanguage;

    $articleid     = Is_Valid_Number(GetVar('articleid', 0, 'whole_number'),0,1);
    $articlestatus = Is_Valid_Number(GetVar('articlestatus', 0, 'natural_number'),0,0,1);

    if(!CheckFormToken() || empty($articleid) || $this->authormode)
    {
      echo $sdlanguage['no_view_access'];
      exit();
    }

    if($article_arr = $DB->query_first('SELECT articleid, settings'.
                                       ' FROM '.$this->table_name.
                                       ' WHERE articleid = %d',$articleid))
    {
      // Reset 2nd bit and OR with 2 or 0 to make sure the new status is set:
      $articlestatus = $article_arr['settings'] - ($article_arr['settings'] & 2) | (2*$articlestatus);
      $DB->query('UPDATE '.$this->table_name.' SET settings = %d WHERE articleid = %d',
                 $articlestatus, $articleid);
      //SD342 clear article cache file
      if(isset($SDCache) && $SDCache->IsActive())
      $SDCache->delete_cacheid(CACHE_ARTICLE.'-'.$articleid.'-'.$this->pluginid);
      $admin_phrases = LoadAdminPhrases(2,$this->pluginid,true);
      echo $admin_phrases['article_updated'];
    }
    else
    {
      echo 'ERROR: Invalid Article!';
    }
    exit();

  } //ChangeArticleStatus


  // ############################################################################
  // UPDATE ARTICLE'S PAGE
  // ############################################################################

  function ChangeArticlePage() //for ajax operation!
  {
    global $DB, $SDCache, $sdlanguage;

    $articleid  = Is_Valid_Number(GetVar('articleid', 0, 'whole_number'),0,1);
    $categoryid = Is_Valid_Number(GetVar('categoryid', 0, 'natural_number'),0,0);

    if(!CheckFormToken() || $this->authormode)
    {
      echo $sdlanguage['no_view_access'];
      exit();
    }

    if($article_arr = $DB->query_first('SELECT articleid, categoryid'.
                                       ' FROM '.$this->table_name.
                                       ' WHERE articleid = %d',$articleid))
    {
      // Change page only if different
      if($article_arr['categoryid'] !== $categoryid)
      {
        $DB->query('UPDATE '.$this->table_name.' SET categoryid = %d WHERE articleid = %d',
                   $categoryid, $articleid);
        //SD342 clear article cache file
        if(isset($SDCache) && $SDCache->IsActive())
          $SDCache->delete_cacheid(CACHE_ARTICLE.'-'.$articleid.'-'.$this->pluginid);
      }
	  
	  echo '<script>
			jDialog.close();
			var n = noty({
					text: \''.AdminPhrase('article_updated').'\',
					layout: \'top\',
					type: \'success\',	
					timeout: 5000,					
					});
			</script>';
     // echo '<div style="padding:8px;text-align:center;width:auto;"><p style="text-align:center">'.AdminPhrase('article_updated').'</p></div>';
    }
    else
    {
      echo 'ERROR: Invalid Article!';
    }
    exit();

  } //ChangeArticlePage


  // ############################################################################
  // GET ARTICLE'S PAGE NAME (HTML)
  // ############################################################################

  function GetArticlePageHTML() //for ajax operation!
  {
    global $DB, $sdlanguage, $pages_md_arr;

    $articleid = Is_Valid_Number(GetVar('articleid', 0, 'whole_number'),0,1);

    if(!CheckFormToken() || empty($articleid) || $this->authormode)
    {
      echo $sdlanguage['no_view_access'];
      exit();
    }

    if($article_arr = $DB->query_first('SELECT articleid, categoryid'.
                                       ' FROM '.$this->table_name.
                                       ' WHERE articleid = %d',$articleid))
    {
      // Reset 2nd bit and OR with 2 or 0 to make sure the new status is set:
      $name = '';
      if(isset($pages_md_arr[$article_arr['categoryid']]))
      {
        $name = $pages_md_arr[$article_arr['categoryid']]['name'];
      }
      echo '
      <a class="parentselector btn btn-block btn-info" rel="'.$articleid.'" href="#">
      <span>'.
      (empty($article_arr['categoryid']) ? '---' : $name).
      '</span></a>';
    }
    else
    {
      echo 'ERROR: Invalid Article!';
    }
    exit();

  } //GetArticlePageHTML


  // ############################################################################
  // GET ARTICLE'S SETTINGS FROM POST BUFFER
  // ############################################################################

  function GetArticlePostSettings()
  {
    global $DB;

    // create settings
    $settings = 0;
    $settings += !empty($_POST['useglobalsettings'])         ? 1     : 0;
    if(!$this->authormode)
    {
      $settings += !empty($_POST['displayonline'])           ? 2     : 0;
    }

    // don't update these settings if global is selected
    if(empty($_POST['useglobalsettings']))
    {
      $settings += !empty($_POST['displaytitle'])            ? 4      : 0;
      $settings += !empty($_POST['displayauthor'])           ? 8      : 0;
      $settings += !empty($_POST['displaycreateddate'])      ? 16     : 0;
      $settings += !empty($_POST['displayupdateddate'])      ? 32     : 0;
      $settings += !empty($_POST['displayprintarticlelink']) ? 64     : 0;
      $settings += !empty($_POST['displayemailarticlelink']) ? 128    : 0;
      $settings += !empty($_POST['displaydescription'])      ? 256    : 0;
      $settings += !empty($_POST['displaycomments'])         ? 1024   : 0;
      $settings += !empty($_POST['displayviews'])            ? 2048   : 0;
      $settings += !empty($_POST['displaymainpage'])         ? 4096   : 0;
      $settings += !empty($_POST['displaysticky'])           ? 8192   : 0;
      $settings += !empty($_POST['displayratings'])          ? 16384  : 0;
      $settings += !empty($_POST['displaysocialbuttons'])    ? 32768  : 0;
      $settings += !empty($_POST['displaytags'])             ? 65536  : 0;
      $settings += !empty($_POST['displaypdfarticlelink'])   ? 131072 : 0;
      $settings += !empty($_POST['displayaspopup'])          ? 262144 : 0; //SD342
      $settings += !empty($_POST['ignore_excerpt_mode'])     ? 524288 : 0; //SD342
      $settings += !empty($_POST['linktomainpage'])          ?1048576 : 0; //SD343
    }

    return $settings;

  } //GetArticleSettings


  // ##########################################################################
  // DISPLAY ARTICLE PERMISSIONS
  // ##########################################################################

  function DisplayArticlePermissions()
  {
    global $DB, $sdlanguage, $userinfo;

    $articleid = GetVar('articleid', 0, 'whole_number');
    $article = $DB->query_first('SELECT * FROM '.$this->table_name.' WHERE articleid = '.(int)$articleid);
    if( !$articleid || !$article ||
        ($this->authormode && !empty($article['org_author_id']) &&
         ($article['org_author_id'] != $userinfo['userid'])) )
    {
      DisplayMessage($sdlanguage['no_view_access_guests'],true);
      return false;
    }

    StartSection(AdminPhrase('article_permissions').' <i class="ace-icon fa fa-angle-double-right"></i> "'.$article['title'].'"');
    echo '
    <form method="post" action="'.$this->_link.'" name="articleform">
    '.PrintSecureToken().'
    <table class="table table-bordered table-striped">
    <tr>
      <td class="td2" width="50%">' . AdminPhrase('permissions_hint') . '</td>
      <td class="td2" align="center" width="50%">
      <select name="access_view[]" multiple="multiple" size="6" class="form-control">';
    $groups = sd_ConvertStrToArray($article['access_view'], '|');
    $getusergroups = $DB->query('SELECT usergroupid, name FROM {usergroups} ORDER BY usergroupid');
    while($ug = $DB->fetch_array($getusergroups,null,MYSQL_ASSOC))
    {
      echo '<option value="'.$ug['usergroupid'].'" '.
           (in_array($ug['usergroupid'],$groups)?' selected="selected"':'').'">'.$ug['name'].'</option>';

    }
    echo '</select>
      <br />
      </td>
    </tr>
    </table>
    <br />
    <input type="hidden" name="articleaction" value="updatearticlepermissions" />
    <input type="hidden" name="articleid" value="' . $article['articleid'] . '" />
    <input type="hidden" name="load_wysiwyg" value="0" />
    <input type="hidden" name="cbox" value="1" />
    <div class="center">
		<button class="btn btn-info type="submit" value=""><i class="ace-icon fa fa-check"></i> ' . AdminPhrase('update_article') . '</button>
	</div>
    <br />
    </form>';
    EndSection();

  } //DisplayArticlePermissions


  // ##########################################################################
  // DISPLAY ARTICLE SETTINGS
  // ##########################################################################

  function DisplayArticleSettings()
  {
    if(!empty($_POST['updatesettings']))
    {
      UpdatePluginSettings($_POST['settings'], $this->_link.'&amp;articleaction=displayarticlesettings');
    }
    else
    {
      PrintPluginSettings($this->pluginid,
                          array('article_display_settings',
                                'article_admin_settings',
                                'article_notification_settings'),
                          $this->_link.'&amp;articleaction=displayarticlesettings');
    }

  } //DisplayArticleSettings


  // ##########################################################################
  // DISPLAY PAGE ARTICLE SETTINGS
  // ##########################################################################

  function DisplayPageArticleSettings()
  {
    global $DB, $userinfo;

    $getcategories = $DB->query('SELECT categoryid, parentid, name FROM {categories}'.
                                ' ORDER BY parentid, displayorder');

    echo '
    <form method="post" action="'.$this->_link.'">
    '.PrintSecureToken().'
    <input type="hidden" name="articleaction" value="update_page_article_settings" />
    <input type="hidden" name="pluginid" value="'.$this->pluginid.'" />
    ';

    StartSection(AdminPhrase('page_article_settings'));
    echo '
    <table class="table table-bordered table-striped">
	<thead>
    <tr>
      <th class="td1">' . AdminPhrase('page_name') . '</th>
      <th class="td1" width="200">' . AdminPhrase('article_sorting_method') . '</th>
      <th class="td1" width="150">' . AdminPhrase('max_articles_per_page') . '</th>
      <th class="td1">' . AdminPhrase('display_multiple_pages_of_articles') . '?</th>
    </tr>
	</thead>
	<tbody>';

    while($category = $DB->fetch_array($getcategories,null,MYSQL_ASSOC))
    {
      //SD322: only display category (and it's sub-categories) if the current
      // user is either admin or has view permission for it:
      if(!empty($userinfo['adminaccess']) || (!empty($userinfo['categoryviewids']) &&
         @in_array($category['categoryid'], $userinfo['categoryviewids'])) )
      {
        $DB->result_type = MYSQL_ASSOC;
        // Only display pages which display the articles plugin:
        if($DB->query_first('SELECT categoryid FROM {pagesort}'.
                            " WHERE categoryid = %d AND pluginid = '%s'",
                            $category['categoryid'], $this->pluginid))
        {
          $cid = (int)$category['categoryid'];
          $DB->result_type = MYSQL_ASSOC;
          $categorysettings = $DB->query_first('SELECT categoryid,maxarticles,multiplepages,sorting
                                                FROM {p'.$this->pluginid.'_settings}
                                                WHERE categoryid = '.(int)$cid);
          if(empty($categorysettings['categoryid']))
          {
            // settings have not been defined, show default
            $categorysettings = array('maxarticles'   => 10,
                                      'sorting'       => 'newest_first',
                                      'multiplepages' => 1);
          }
          echo '
          <tr>
            <td class="td2">
            <input type="hidden" name="categoryids['.$cid.']" value="'.$cid.'">' . $category['name'] . '</td>
            <td class="td3">
              <select name="sorting['.$cid.']">
              <option value="newest_first" '.($categorysettings['sorting'] == 'newest_first' ?'selected="selected"':'') .' />'.AdminPhrase('newest_first').'</option>
              <option value="oldest_first" '.($categorysettings['sorting'] == 'oldest_first' ?'selected="selected"':'') .' />'.AdminPhrase('oldest_first').'</option>
              <option value="displayorder ASC" ' . ($categorysettings['sorting'] == 'displayorder ASC' ? 'selected="selected"':'') .' />' . AdminPhrase('display_order_asc') . '</option>
              <option value="displayorder DESC" ' . ($categorysettings['sorting'] == 'displayorder DESC'? 'selected="selected"':'') .' />' . AdminPhrase('display_order_desc') . '</option>
              <option value="title ASC" ' . ($categorysettings['sorting'] == 'title ASC'?'selected="selected"':'') .' />' . AdminPhrase('article_title_az') . '</option>
              <option value="title DESC" ' . ($categorysettings['sorting'] == 'title DESC'?'selected="selected"':'') .' />' . AdminPhrase('article_title_za') . '</option>
              <option value="author ASC" ' . ($categorysettings['sorting'] == 'author ASC'?'selected="selected"':'') .' />' . AdminPhrase('author_name_az') . '</option>
              <option value="author DESC" ' . ($categorysettings['sorting'] == 'author DESC'?'selected="selected"':'') .' />' . AdminPhrase('author_name_za') . '</option>
              </select>
            </td>
            <td class="td3"><input type="text" name="maxarticles['.$cid.']" value="'.$categorysettings['maxarticles'].'" size="4" maxlength="4"></td>
            <td class="td3">
              <input type="radio" class="ace" name="multiplepages['.$cid.']" value="1" ' .
              ($categorysettings['multiplepages'] == 1 ? 'checked="checked"':'') . '><span class="lbl"> ' . AdminPhrase('common_yes') . '</span> &nbsp;
              <input type="radio" class="ace" name="multiplepages['.$cid.']" value="0" ' .
              ($categorysettings['multiplepages'] == 0 ? 'checked="checked"':'') . '><span class="lbl"> ' . AdminPhrase('common_no') . ' </span>
            </td>
          </tr>';
        }
      }
    }

    echo '</tbody></table>';
    EndSection();

    echo '
    <div class="center">
		<button class="btn btn-info" type="submit" value="" /><i class="ace-icon fa fa-check"></i> ' . AdminPhrase('common_update_settings') . '</button>
	</div>
    </form>';

  } //DisplayPageArticleSettings


  // ##########################################################################
  // UPDATE PAGE ARTICLE SETTINGS
  // ##########################################################################

  function UpdatePageArticleSettings()
  {
    global $DB, $pluginid, $SDCache;

    if(!CheckFormToken())
    {
      RedirectPage($this->_link,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
      return;
    }

    // following $_POST variables are all arrays
    $categoryids   = GetVar('categoryids',   array(), 'array');
    $maxarticles   = GetVar('maxarticles',   array(), 'array');
    $sorting       = GetVar('sorting',       array(), 'array');
    $multiplepages = GetVar('multiplepages', array(), 'array');

    foreach($categoryids as $cid)
    {
      $DB->result_type = MYSQL_ASSOC;
      $categorysettings = $DB->query_first('SELECT categoryid FROM {p'.$this->pluginid.'_settings}'.
                                           ' WHERE categoryid = %d LIMIT 1', $cid);
      if(empty($categorysettings['categoryid']))
      {
        $DB->query('INSERT INTO {p'.$this->pluginid."_settings} (categoryid, maxarticles, sorting, multiplepages)
                    VALUES (%d, %d, '%s', %d)",
                    $cid, $maxarticles[$cid], $sorting[$cid], $multiplepages[$cid]);
      }
      else
      {
        $DB->query('UPDATE {p'.$this->pluginid."_settings} SET maxarticles = %d, sorting = '%s', multiplepages =%d
                    WHERE categoryid = %d",
                    $maxarticles[$cid], $sorting[$cid], $multiplepages[$cid], $cid);
      }
    }

    // SD313: delete current article's cache file
    if(isset($SDCache) && $SDCache->IsActive())
    $SDCache->delete_cacheid('psettings_'.$pluginid);

    RedirectPage($this->_link.'&amp;articleaction=display_page_article_settings', AdminPhrase('settings_updated'));

  } //UpdatePageArticleSettings


  // ##########################################################################
  // INSERT ARTICLE
  // ##########################################################################

  function InsertArticle()
  {
    global $DB, $plugin_names, $mainsettings, $userinfo, $usersystem;

    // basic article post data
    $categoryid      = GetVar('categoryid', 0, 'whole_number',true,false);
    $views           = GetVar('views', 0, 'natural_number',true,false);
    $datecreated     = GetVar('datecreated', 0,  'string', true, false);
    $datecreated2    = GetVar('datecreated2',null, 'string', true, false);
    $timecreated     = GetVar('timecreated', 0,    'string', true, false);
    $dateupdated     = GetVar('dateupdated', 0,    'string', true, false);
    $dateupdated2    = GetVar('dateupdated2',null, 'string', true, false);
    $timeupdated     = GetVar('timeupdated', 0,    'string', true, false);
    $datestart       = GetVar('datestart',   0,    'string', true, false);
    $datestart2      = GetVar('datestart2',  null, 'string', true, false);
    $timestart       = GetVar('timestart',   0,    'string', true, false);
    $dateend         = GetVar('dateend',     0,    'string', true, false);
    $dateend2        = GetVar('dateend2',    null, 'string', true, false);
    $timeend         = GetVar('timeend',     0,    'string', true, false);
    $author          = GetVar('author', '', 'string',true,false);
    $author          = $DB->escape_string(!empty($userinfo['authorname'])?$userinfo['authorname']:(empty($author)?$userinfo['username']:$author));
    $title           = GetVar('title', '', 'html',true,false);
    $seotitle        = GetVar('seotitle', '', 'html',true,false);
    $metadescription = GetVar('metadescription', '', 'string',true,false);
    $metakeywords    = GetVar('metakeywords', '', 'string',true,false);
    $description     = GetVar('description', '', 'html',true,false);
    $article         = GetVar('article', '', 'html',true,false);
    $tags            = GetVar('tags', '', 'string',true,false);
    $max_comments    = GetVar('max_comments', 0, 'natural_number',true,false);
    $pages           = GetVar('pages', array(), 'array', true, false); //SD341
    //SD342
    $featured        = GetVar('featured', 0, 'bool',true,false);
    $featuredpath    = GetVar('featuredpath', '', 'string',true,false);
    $rating          = GetVar('rating', '', 'string',true,false);
    $thumbnail       = GetVar('thumbnail', '', 'string',true,false);
    $template        = GetVar('template', '', 'string',true,false);
    $template = trim($template);

   	//SD400: Format datetimepicker fileds
	$datecreated_tmp = explode(' ', $datecreated2);
	$timecreated_tmp = $datecreated_tmp[1] . str_replace("",'',$datecreated_tmp[2]);
	
	$dateupdated_tmp = explode(' ', $dateupdated2);
	$timeupdated_tmp = $dateupdated_tmp[1] . str_replace("",'',$dateupdated_tmp[2]);

	
	$datestart_tmp = explode(' ', $datestart2);
	$timestart_tmp = $datestart_tmp[1] . str_replace("",'',$datestart_tmp[2]);
	
	$dateend_tmp = explode(' ', $dateend2);
	$timeend_tmp = $dateend_tmp[1] . str_replace("",'',$dateend_tmp[2]);

    $datecreated  = !empty($datecreated2) && isset($datecreated) ? sd_CreateUnixTimestamp($datecreated_tmp[0], $timecreated_tmp) : TIME_NOW;
    $dateupdated  = !empty($dateupdated2) && isset($dateupdated) ? sd_CreateUnixTimestamp($dateupdated_tmp[0], $timeupdated_tmp) : 0;
    $publishstart = !empty($datestart2)   && isset($datestart)   ? sd_CreateUnixTimestamp($datestart_tmp[0], $timestart_tmp) : 0;
    $publishend   = !empty($dateend2)     && isset($dateend)     ? sd_CreateUnixTimestamp($dateend_tmp[0], $timeend_tmp) : 0;

    //SD360:
    $featured_start      = GetVar('featured_start',   0,    'string', true, false);
    $featured_start2     = GetVar('featured_start2',  null, 'string', true, false);
    $time_featured_start = GetVar('time_featured_start', 0, 'string', true, false);
    $featured_end        = GetVar('featured_end',   0,    'string', true, false);
    $featured_end2       = GetVar('featured_end2',  null, 'string', true, false);
    $time_featured_end   = GetVar('time_featured_end', 0, 'string', true, false);
    $featured_start      = !empty($featured_start2) && $featured_start ? sd_CreateUnixTimestamp($featured_start, $time_featured_start) : 0;
    $featured_end        = !empty($featured_end2) && $featured_end ? sd_CreateUnixTimestamp($featured_end, $time_featured_end) : 0;

    // create settings
    $settings = $this->GetArticlePostSettings();

    // Convert seo title for URL and check if seotitle is taken
    if(!strlen(trim($seotitle)))
    {
      $seotitle = ConvertNewsTitleToUrl($title, 0, 0, true);
    }
    else
    {
      //SD343 2012-08-16: behavior change: do not convert entered "SEO Title"
      // as that may be in non-latin characters (e.g. Greek, Latvian etc.),
      // thus ONLY replace some general special chars just like
      // the "UpdateArticle" does it
      //$seotitle = ConvertNewsTitleToUrl($seotitle, 0, 0, true);
      // fix up at least some unallowed chars
      $sep = $mainsettings['settings_seo_default_separator'];
      $search  = array(';', ',', '"', "'",  '&#039;', '&#39;', '\\', '%', '0x', '?', ' ', '(', ')', '[', ']');
      $replace = array($sep, $sep, '', $sep, $sep, $sep, '', '', '', '',  $sep, $sep, $sep, $sep, $sep);
      $seotitle = str_replace($search, $replace, strip_alltags($seotitle));
    }
    if(strlen($seotitle))
    {
      $DB->result_type = MYSQL_ASSOC;
      //SD343: commented out checking page SEO titles
      /*
      if(strlen($seotitle) && $page_seo_title_exists = $DB->query_first("SELECT urlname FROM {categories} WHERE urlname = '%s'",$seotitle))
      {
        $seotitle = '';
      }
      else  */
      //SD343: check SEO url only against articles on the same page
      //TODO: check secondary pages as well?!
      if(($categoryid > 0) &&
         ($article_seo_title_exists = $DB->query_first('SELECT seo_title FROM '.$this->table_name.
                                                       " WHERE seo_title = '%s' AND categoryid = %d",
                                                       $seotitle, $categoryid)))
      {
        $seotitle = '';
      }
    }

    if(empty($metakeywords)) //SD343 auto generate keywords
    {
      if(!empty($article))
        $metakeywords = sd_getkeywords($article,true,1,10);
      else
        $metakeywords = sd_getkeywords($description,true,1,10);
    }

    if(empty($metadescription)) //SD343 auto generate description
    {
      if(!empty($article))
        $tmp = $article;
      else
        $tmp = $description;
      $tmp = preg_replace('#\[[^\[]*\]#m','',$tmp);
      $tmp = trim(strip_alltags(preg_replace(array('/&#039;/','#\s+#m','#\.\.+#m','#\,#m'),array("'",' ','.',' '), $tmp)));
      if(strlen($tmp))
      {
        $descr_keywords = array_unique(array_slice(array_filter(explode(' ', $tmp)),0,30));
        $metadescription = trim(implode(' ', $descr_keywords));
      }
    }

    // get displayorder
    $DB->result_type = MYSQL_ASSOC;
    $articlecount = $DB->query_first('SELECT COUNT(*) acount FROM '.$this->table_name);
    $displayorder = 1 + (empty($articlecount['acount']) ? 0 : $articlecount['acount']);

    //SD341: new columns: thumbnail, rating, featured, featuredpath
    $DB->skip_curly = true;
    $DB->query('INSERT INTO '.$this->table_name."
                (categoryid, settings, views, displayorder, datecreated, dateupdated, datestart, dateend, author, title,
                 metadescription, metakeywords, description, article, seo_title, tags,
                 org_created_date, org_author_id, org_system_id, org_author_name, max_comments,
                 thumbnail, rating, featured, featuredpath, template,
                 featured_start, featured_end)
                VALUES ($categoryid, $settings, $views, $displayorder, $datecreated, $dateupdated, $publishstart, $publishend, '$author', '" .
                $DB->escape_string($title) . "', '".$DB->escape_string($metadescription)."', '".$DB->escape_string($metakeywords)."', '" . $DB->escape_string($description) . "', '" .
                $DB->escape_string($article) . "', '".$DB->escape_string($seotitle)."', '', ". TIME_NOW.", ". (int)$userinfo['userid'].", ".
                $usersystem['usersystemid'].", '". $DB->escape_string($userinfo['username'])."', ".(int)$max_comments.",'".
                $DB->escape_string($thumbnail)."','".$DB->escape_string($rating)."','".$featured."','".
                $DB->escape_string($featuredpath)."','".$DB->escape_string($template)."', $featured_start, $featured_end)");

    //SD341: process new "pages" array (delete first, then re-insert each page):
    $articleid = $DB->insert_id();
    $DB->skip_curly = false;

    if(!empty($articleid))
    {
      // SD343: store tags in global table
      require_once(SD_INCLUDE_PATH.'class_sd_tags.php');
      SD_Tags::StorePluginTags($this->pluginid, $articleid, $tags);

      //SD360: process file attachment
      $this->ProcessAttachment($articleid);

      //SD360: check filename
      require_once(SD_INCLUDE_PATH.'class_sd_media.php');
      $sdi = new SD_Image();
      if(!empty($featuredpath))
      {
        $file = (substr($featuredpath,0,9)=='featured_'?'':'featured_').$featuredpath;
        if(!SD_Media_Base::is_valid_path($file) ||
           !SD_Media_Base::ImageSecurityCheck(true,ROOT_PATH.SD_ARTICLE_FEATUREDPICS_DIR,$file))
        {
          $featuredpath = '';
          DisplayMessage('Invalid Featured Picture!', true);
        }
      }
      if(!empty($thumbnail))
      {
        if(!SD_Media_Base::is_valid_path($thumbnail) ||
           !SD_Media_Base::ImageSecurityCheck(true,ROOT_PATH.SD_ARTICLE_THUMBS_DIR,$thumbnail))
        {
          $thumbnail = '';
          DisplayMessage('Invalid Thumbnail!', true);
        }
      }
      unset($sdi);

      $DB->query('DELETE FROM {p'.$this->pluginid.'_articles_pages} WHERE articleid = %d', $articleid);
      foreach($pages as $page)
      {
        if(!empty($page) && is_numeric($page) && ($page > 0))
        {
          $DB->query('INSERT INTO {p'.$this->pluginid.'_articles_pages} (articleid, categoryid) VALUES (%d, %d)',
                     $articleid, $page);
        }
      }

      //SD342: email notification in author mode
      if($this->authormode &&
         !empty($this->settings['notification_trigger']) &&
         ( ($this->settings['notification_trigger']==1) ||
           ($this->settings['notification_trigger']==3)))
      {
        $senderemail = $this->settings['notification_recipient_email_address'];
        $senderemail = $senderemail ? $senderemail : $mainsettings['technicalemail'];
        $pluginname  = $plugin_names[$this->pluginid];
        $date        = DisplayDate(TIME_NOW);
        $message     = $this->settings['notification_email_body'];
        $message     = str_replace(array('[username]',          '[pluginname]', '[articletitle]', '[date]'),
                                   array($userinfo['username'], $pluginname,    $title,           $date), $message);
        $subject     = $this->settings['notification_email_subject'];
        $subject     = str_replace(array('[username]',          '[pluginname]', '[articletitle]', '[date]'),
                                   array($userinfo['username'], $pluginname,    $title,           $date), $subject);
        @SendEmail($senderemail, $subject, $message, '', $senderemail, null, null, true);
      }
    }

    // SD350: delete Article SEO to ID's mapping cache file
    global $SDCache;
    if(isset($SDCache) && $SDCache->IsActive())
    {
      $SDCache->delete_cacheid(CACHE_ARTICLES_SEO2IDS.'-'.$this->pluginid);
    }

    RedirectPage($this->_link, AdminPhrase('article_created'));

  } //InsertArticle


  // ##########################################################################
  // UPDATE ARTICLE'S PERMISSIONS
  // ##########################################################################

  function UpdateArticlePermissions()
  {
    global $DB, $sdlanguage, $userinfo;

    if(!CheckFormToken())
    {
      RedirectPage($this->_link,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
      return;
    }

    $articleid = Is_Valid_Number(GetVar('articleid', 0, 'whole_number', true, false),0,1);
    if($articleid)
    {
      $DB->result_type = MYSQL_ASSOC;
      $article = $DB->query_first('SELECT * FROM '.$this->table_name.
                                  ' WHERE articleid = %d', $articleid);
    }
    if( !$articleid || empty($article['articleid']) ||
        ($this->authormode && !empty($article['org_author_id']) &&
         ($article['org_author_id'] != $userinfo['userid'])) )
    {
      DisplayMessage('Invalid Request!',true);
      return false;
    }

    $error = false;
    $ids = array();
    $access_view = '';
    $ac = GetVar('access_view', array(), 'a_int', true, false);

    if(!empty($ac))
    {
      if(count($ac) == 1)
      {
        $access_view = '|'.(int)$ac[0].'|';
      }
      else
      {
        foreach($ac as $id)
        {
          $id = Is_Valid_Number($id,0,1,99999);
          if(!$id)
          {
            $error = true;
            break;
          }
          else
          {
            $ids[] = $id;
          }
        }
        if(!$error && !empty($ids))
        {
          $access_view = (count($ids)==1 ? $ids[0] : implode('|', $ids));
          $access_view =  '|'.$access_view.'|';
        }
      }
    }

    if($error)
    {
      DisplayMessage('Invalid Request!',true);
      return false;
    }

    $DB->query('UPDATE '.$this->table_name." SET access_view = '%s' WHERE articleid = %d", $access_view, $articleid);

    if(IS_CBOX)
    {
      echo '<div><center><div style="text-align: center; width: 200px;">'.
        sd_CloseCeebox(2, AdminPhrase('permissions_updated').'
        <br /><br /><input class="submit" type="button" onclick="parent.jQuery.fn.ceebox.closebox(\'fast\');" value="'.AdminPhrase('close_window').'" />');
        echo '</div></center></div>';
      return;
    }

    RedirectPage($this->_link, AdminPhrase('permissions_updated'));

  } //UpdateArticlePermissions


  // ##########################################################################
  // UPDATE ARTICLE
  // ##########################################################################

  function UpdateArticle()
  {
    global $DB, $SDCache, $load_wysiwyg, $mainsettings, $plugin_names,
           $sdlanguage, $userinfo, $usersystem;
		   
		
    if(!CheckFormToken())
    {
      RedirectPage($this->_link,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
      return;
    }
	

    // basic article post data
    $articleid       = GetVar('articleid',   0,    'whole_number',   true, false);
    $categoryid      = GetVar('categoryid',  0,    'whole_number',   true, false);
    $views           = GetVar('views',       0,    'natural_number', true, false);
    $datecreated     = GetVar('datecreated', 0,    'string', true, false);
    $datecreated2    = GetVar('datecreated2',null, 'string', true, false);
    $timecreated     = GetVar('timecreated', 0,    'string', true, false);
    $dateupdated     = GetVar('dateupdated', 0,    'string', true, false);
    $dateupdated2    = GetVar('dateupdated2',null, 'string', true, false);
    $timeupdated     = GetVar('timeupdated', 0,    'string', true, false);
    $datestart       = GetVar('datestart',   0,    'string', true, false);
    $datestart2      = GetVar('datestart2',  null, 'string', true, false);
    $timestart       = GetVar('timestart',   0,    'string', true, false);
    $dateend         = GetVar('dateend',     0,    'string', true, false);
    $dateend2        = GetVar('dateend2',    null, 'string', true, false);
    $timeend         = GetVar('timeend',     0,    'string', true, false);
    $title           = GetVar('title',       '',   'html', true, false);
    $seotitle        = GetVar('seotitle',    '',   'html', true, false);
    $description     = GetVar('description', '',   'html', true, false);
    $article         = GetVar('article',     '',   'html', true, false);
    $metadescription = GetVar('metadescription', '', 'string', true, false);
    $metakeywords    = GetVar('metakeywords', '', 'string', true, false);
    $CopyArticle     = GetVar('CopyArticle', false, 'bool', true, false);
    $tags            = GetVar('tags', '', 'string', true, false);
    $max_comments    = GetVar('max_comments', 0, 'natural_number', true, false);
    $pages           = GetVar('pages', array(), 'array', true, false);
    //SD342
    $featured        = GetVar('featured',    0, 'bool', true, false);
    $featuredpath    = GetVar('featuredpath','', 'string', true, false);
    $thumbnail       = GetVar('thumbnail',   '', 'string', true, false);
    $rating          = GetVar('rating',      '', 'string', true, false);
    $template        = trim(GetVar('template', '', 'string',true,false));
    $deletethumb     = GetVar('deletethumb', false, 'bool',true,false);

    //SD360: check for invalid characters
    $title           = (UserinputSecCheck($title) ? $title : '');
    $seotitle        = (UserinputSecCheck($seotitle) ? $seotitle : '');

	//SD400: Format datetimepicker fileds
	$datecreated_tmp = explode(' ', $datecreated2);
	$timecreated_tmp = $datecreated_tmp[1] . str_replace("",'',$datecreated_tmp[2]);
	
	$dateupdated_tmp = explode(' ', $dateupdated2);
	$timeupdated_tmp = $dateupdated_tmp[1] . str_replace("",'',$dateupdated_tmp[2]);

	
	$datestart_tmp = explode(' ', $datestart2);
	$timestart_tmp = $datestart_tmp[1] . str_replace("",'',$datestart_tmp[2]);
	
	$dateend_tmp = explode(' ', $dateend2);
	$timeend_tmp = $dateend_tmp[1] . str_replace("",'',$dateend_tmp[2]);

    $datecreated  = !empty($datecreated2) && isset($datecreated) ? sd_CreateUnixTimestamp($datecreated_tmp[0], $timecreated_tmp) : TIME_NOW;
    $dateupdated  = !empty($dateupdated2) && isset($dateupdated) ? sd_CreateUnixTimestamp($dateupdated_tmp[0], $timeupdated_tmp) : 0;
    $publishstart = !empty($datestart2)   && isset($datestart)   ? sd_CreateUnixTimestamp($datestart_tmp[0], $timestart_tmp) : 0;
    $publishend   = !empty($dateend2)     && isset($dateend)     ? sd_CreateUnixTimestamp($dateend_tmp[0], $timeend_tmp) : 0;
	

    //SD344 / v3.5.1
    $newAuthorID  = GetVar('newAuthorID', 0, 'whole_number', true, false);
    $reassign     = GetVar('reassign_author', false, 'bool', true, false);

    //SD360:
    $featured_start      = GetVar('featured_start',   0,    'string', true, false);
    $featured_start2     = GetVar('featured_start2',  null, 'string', true, false);
    $time_featured_start = GetVar('time_featured_start', 0, 'string', true, false);
    $featured_end      = GetVar('featured_end',   0,    'string', true, false);
    $featured_end2     = GetVar('featured_end2',  null, 'string', true, false);
    $time_featured_end = GetVar('time_featured_end', 0, 'string', true, false);
    $featured_start    = !empty($featured_start2) && $featured_start ? sd_CreateUnixTimestamp($featured_start, $time_featured_start) : 0;
    $featured_end      = !empty($featured_end2) && $featured_end ? sd_CreateUnixTimestamp($featured_end, $time_featured_end) : 0;

    // create settings
    $settings = $this->GetArticlePostSettings();

    // Convert seo title for URL and check if seotitle is taken
    if(!strlen(trim($seotitle)))
    {
      $seotitle = ConvertNewsTitleToUrl($title, 0, 0, true);
    }
    else
    {
      // fix up at least some unallowed chars
      $sep = $mainsettings['settings_seo_default_separator'];
      $search  = array(';', ',', '"', "'",  '&#039;', '&#39;', '\\', '%', '0x', '?', ' ', '(', ')', '[', ']');
      $replace = array($sep, $sep, '', $sep, $sep, $sep, '', '', '', '',  $sep, $sep, $sep, $sep, $sep);
      $seotitle = str_replace($search, $replace, strip_alltags($seotitle));
    }

    if(strlen(trim($seotitle)))
    {
      if(strlen($seotitle) &&
         ($page_seo_title_exists = $DB->query_first("SELECT urlname FROM {categories} WHERE urlname = '%s'",
          $DB->escape_string($seotitle))))
      {
        $seotitle = '';
      }
      else
      if($article_seo_title_exists = $DB->query_first('SELECT seo_title FROM '.$this->table_name.
                                                      ' WHERE articleid != '.(int)$articleid.
                                                      " AND seo_title = '".$DB->escape_string($seotitle)."'"))
      {
        echo '<p><strong>Notice: SEO title not generated: same SEO title already exists!</strong></p>';
        $seotitle = '';
      }
    }

    //SD322: full admins are always allowed to change author name;
    // Non-admins are only allowed if author mode is OFF or otherwise
    // if the "author" name in the article is still empty.
    $author = $authorClause = '';
    $arow = $DB->query_first('SELECT thumbnail, featuredpath, author, org_system_id,'.
                             ' org_author_id, org_author_name, settings '.
                             ' FROM '.$this->table_name.
                             ' WHERE articleid = '.(int)$articleid);
    $isonline = !empty($arow['settings']) && (($arow['settings'] & 2) == 2); //v3.5.1

    if(!empty($userinfo['adminaccess']))
    {
      $authorClause = '';
      //SD344 / v3.5.1: if user switched by autocomplete the author AND the
      // user opted to reassign the author (if different from original
      // author), then change the "org_" fields for the article accordingly
      if($reassign && !empty($newAuthorID) && ($newAuthorID != $arow['org_author_id']))
      {
        if(false !== ($newUser = sd_GetUserrow($newAuthorID)))
        {
          $authorClause .= " org_author_id = '".(int)$newAuthorID."', org_system_id = ".
                           $usersystem['usersystemid'].
                           ", org_author_name = '".
                           $DB->escape_string($newUser['username'])."',";
        }
        unset($newUser);
      }

      $author = GetVar('author', '', 'string', true, false);
      $authorClause .= " author = '$author',";
    }
    else
    {
      if($this->authormode)
      {
        if( !empty($arow['org_author_id']) &&
            empty($arow['author']) && empty($userinfo['authorname']) &&
            (!empty($arow['org_system_id']) && ($arow['org_system_id'] == $usersystem['usersystemid'])) &&
            ($arow['org_author_id'] == $userinfo['userid']) )
        {
          $author = trim(GetVar('author', '', 'html', true, false));
          if(strlen($author) && UserinputSecCheck($author))
          {
            $author = strip_alltags(CleanVar($author));
            $author = htmlspecialchars(sd_substr($author,0,250));
            $authorClause = " author = '".$DB->escape_string($author)."',";
          }
        }
      }
    }

    //SD322: if user's authorname is not set, update it in the new table users_data
    // But only if the article was created by the current user!
    if(empty($userinfo['authorname']) && !empty($author) &&
       (!empty($arow['org_system_id']) && ($arow['org_system_id'] == $usersystem['usersystemid'])) &&
       !empty($arow['org_author_id']) && ($arow['org_author_id']==$userinfo['userid']))
    {
      $DB->result_type = MYSQL_ASSOC;
      if($current_authorname = $DB->query_first('SELECT authorname FROM {users_data}'.
                                                ' WHERE userid = %d AND usersystemid = %d',
                                                $userinfo['userid'], $usersystem['usersystemid']))
      {
        if($current_authorname['authorname'] != $author)
        {
          $DB->query("UPDATE {users_data} SET authorname = '%s'".
                     ' WHERE userid = %d AND usersystemid = %d',
                     $DB->escape_string($author), $userinfo['userid'],
                     $usersystem['usersystemid']);
        }
      }
      else
      {
        $DB->query('INSERT INTO {users_data} (usersystemid,userid,authorname,user_text)'.
                   " VALUES (%d,%d,'%s','')",
                   $usersystem['usersystemid'], $userinfo['userid'],
                   $DB->escape_string($author));
      }
    }

    //SD341: process new "pages" array (delete first, then re-insert each page):
    $DB->query('DELETE FROM {p'.$this->pluginid.'_articles_pages}'.
               ' WHERE articleid = '.(int)$articleid);
    foreach($pages as $page)
    {
      if(!empty($page) && is_numeric($page) && ($page > 0))
      {
        $DB->query('INSERT INTO {p'.$this->pluginid.'_articles_pages}'.
                   ' (articleid, categoryid) VALUES (%d, %d)',
                   $articleid, $page);
      }
    }

    if(empty($metakeywords)) //SD343 auto generate keywords
    {
      if(!empty($article))
        $metakeywords = sd_getkeywords($article,true,1,10);
      else
        $metakeywords = sd_getkeywords($description,true,1,10);
    }

    // *** TESTING ONLY ***
    /*
    if(extension_loaded('tidy') && class_exists('tidy'))
    {
      $td_config = array('indent' => TRUE,
                         'output-xhtml' => TRUE,
                         'wrap' => 200);

      $tidy = new tidy;
      $tidy->parseString($article, $td_config, 'utf8');
      $tidy->cleanRepair();
      $article = $tidy;
      unset($tidy,$td_config);
    }
    */
    /*
    if(class_exists('DOMDocument'))
    {
      $dom = new DOMDocument();
      $dom->preserveWhiteSpace = true;
      $dom->strictErrorChecking = false;
      $dom->loadHTML($article);
      $dom->formatOutput = TRUE;
      if(FALSE !== ($tmp = $dom->saveHTML()))
      {
        if(@preg_match('#(.*)?<body>(.*)</body></html>(.*)?#si', $tmp, $matches))
        {
          if(!empty($matches) && isset($matches[2]))
          {
            $article = $matches[2];
          }
        }
      }
    }
    */

    if(empty($metadescription)) //SD343 auto generate description
    {
      if(!empty($article))
        $tmp = $article;
      else
        $tmp = $description;
      $tmp = preg_replace('#\[[^\[]*\]#m','',$tmp);
      $tmp = trim(strip_alltags(preg_replace(array('/&#0?39;/','#\s+#m','#\.\.+#m','#\,#m'),array("'",' ','.',' '), $tmp)));
      if(strlen($tmp))
      {
        $descr_keywords = array_unique(array_slice(array_filter(explode(' ', $tmp)),0,30));
        $metadescription = trim(implode(' ', $descr_keywords));
      }
    }

    //SD344 / v3.5.1: set "approved_by" if article gets set online,
    // otherwise clear approved_by
    $extra = '';
    if($isonline && (($settings & 2) == 0))
    {
      $extra = ", approved_by = '', approved_date = ".TIMENOW;
    }
    else
    if(!$isonline && (($settings & 2) == 2))
    {
      $extra = ", approved_by = '".$DB->escape_string($userinfo['username']).
               "', approved_date = ".TIMENOW;
    }

    //SD360: check filename
    require_once(SD_INCLUDE_PATH.'class_sd_media.php');
    $sdi = new SD_Image();
    if(!empty($featuredpath) && (empty($arow['featuredpath']) || ($arow['featuredpath']!=$featuredpath)))
    {
      $file = (substr($featuredpath,0,9)=='featured_'?'':'featured_').$featuredpath;
      if(!SD_Media_Base::is_valid_path($file) ||
         !SD_Media_Base::ImageSecurityCheck(true,ROOT_PATH.SD_ARTICLE_FEATUREDPICS_DIR,$file))
      {
        $featuredpath = '';
        DisplayMessage('Invalid Featured Picture!', true);
      }
    }
    if(!empty($thumbnail) && (empty($arow['thumbnail']) || ($arow['thumbnail']!=$thumbnail)))
    {
      if(!SD_Media_Base::is_valid_path($thumbnail) ||
         !SD_Media_Base::ImageSecurityCheck(true,ROOT_PATH.SD_ARTICLE_THUMBS_DIR,$thumbnail))
      {
        $thumbnail = '';
        DisplayMessage('Invalid Thumbnail!', true);
      }
    }
    unset($sdi);

    if($deletethumb && strlen($thumbnail)) //SD342
    {
      $this->DeleteArticleImage($articleid,true,true);
      $thumbnail = '';
    }
    $DB->skip_curly = true;
    $DB->query('UPDATE '.$this->table_name." SET categoryid = ".(int)$categoryid.",
      settings = $settings,
      views = $views,
      datecreated = $datecreated,
      dateupdated = $dateupdated,
      datestart = $publishstart,
      dateend = $publishend, ".
      $authorClause."
      title = '" . $DB->escape_string($title) . "',
      metadescription = '" . $DB->escape_string($metadescription)."',
      metakeywords = '" . $DB->escape_string($metakeywords)."',
      description = '" . $DB->escape_string($description) . "',
      article = '" . $DB->escape_string($article) . "',
      seo_title = '".$DB->escape_string($seotitle)."',
      last_modified_date = ".TIME_NOW.",
      last_modifier_id = ".$userinfo['userid'].",
      last_modifier_system_id = ".$usersystem['usersystemid'].",
      last_modifier_name = '". $DB->escape_string($userinfo['username']) ."',
      max_comments = $max_comments,
      thumbnail = '". $DB->escape_string($thumbnail) ."',
      rating = '". $DB->escape_string($rating) ."',
      featured = '". (int)$featured ."',
      featuredpath = '". $DB->escape_string($featuredpath) ."',
      featured_start = $featured_start,
      featured_end = $featured_end,
      template = '". $DB->escape_string($template)."' ".$extra."
      WHERE articleid = ".(int)$articleid);
    $DB->skip_curly = false;

    // SD313: delete current article's cache file and
    // Article SEO to ID's mapping cache file
    if(isset($SDCache) && $SDCache->IsActive())
    {
      $SDCache->delete_cacheid(CACHE_ARTICLE.'-'.$articleid.'-'.$this->pluginid);
      $SDCache->delete_cacheid(CACHE_ARTICLES_SEO2IDS.'-'.$this->pluginid);
    }

    //SD342: email notification
    if( $this->authormode &&
        !empty($this->settings['notification_trigger']) &&
        ($this->settings['notification_trigger'] > 1))
    {
      $senderemail = $this->settings['notification_recipient_email_address'];
      $senderemail = $senderemail ? $senderemail : $mainsettings['technicalemail'];
      $pluginname  = $plugin_names[$this->pluginid];
      $date        = DisplayDate(TIME_NOW);
      $message     = $this->settings['notification_email_body'];
      $message     = str_replace(array('[username]',          '[pluginname]', '[articletitle]', '[date]'),
                                 array($userinfo['username'], $pluginname,    $title,           $date), $message);

      @SendEmail($senderemail, $this->settings['notification_email_subject'], $message, '', $senderemail, null, null, true);
    }

    // SD343: store tags in global table
    require_once(SD_INCLUDE_PATH.'class_sd_tags.php');
    SD_Tags::StorePluginTags($this->pluginid, $articleid, $tags);

    //SD360: new: process file attachment
    $this->ProcessAttachment($articleid);

    //SD360: new: post article as forum topic
    if( $this->IsSiteAdmin ||
        (!empty($this->settings['post_to_forum_usergroups']) &&
         !empty($userinfo['usergroupids']) &&
         @array_intersect($userinfo['usergroupids'],
                          $this->settings['post_to_forum_usergroups'])) )
    {
      if(GetVar('post_to_forum', false, 'bool', true, false))
      {
        $forum_id = Is_Valid_Number(GetVar('post_to_forumid', 0, 'whole_number', true, false),0,1);
        $fid = GetPluginID('Forum');
        if( !empty($fid) && !empty($forum_id) &&
            ($this->IsSiteAdmin ||
             (!empty($userinfo['pluginsubmitids']) &&
              in_array($fid, $userinfo['pluginsubmitids']))) )
        {
          require(ROOT_PATH.'plugins/forum/forum.php');
          $fc = new SDForum();
          if(!empty($fc->conf->forums_cache['forums'][$forum_id]) &&
             empty($fc->conf->forums_cache['forums'][$forum_id]['is_category']))
          {
            $forum = $fc->conf->forums_cache['forums'][$forum_id];
            $perm = !empty($forum['access_post']) ? sd_ConvertStrToArray($forum['access_post'],'|'):array();
            if(empty($forum['access_post']) ||
               (!empty($userinfo['usergroupids']) &&
                @array_intersect($userinfo['usergroupids'], $perm)))
            {
              $fc->conf->SetForum($forum_id);
              $post_descr   = GetVar('post_descr_to_post', false, 'bool', true, false);
              $post_article = GetVar('post_article_to_post', false, 'bool', true, false);
              $backlink     = GetVar('post_link_to_post', false, 'bool', true, false);
              $add_link     = GetVar('post_link_back_to_article', false, 'bool', true, false);

              $article_link = false;
              if($backlink)
              {
                $ar = array('seo_title'  => $seotitle,
                            'categoryid' => $categoryid,
                            'settings'   => $settings,
                            'articleid'  => $articleid);
                $article_link = GetArticleLink($categoryid, $this->pluginid,
                                  $ar, self::$articlebitfield,
                                  false, $this->_folder); //SD360
                if(!empty($article_link))
                {
                  $txt = trim($this->phrases['forum_backlink_text']);
                  if(empty($txt) || !strlen($txt))
                    $txt = '[url='.$article_link.']'.
                           htmlspecialchars($article_link).'[/url]';
                  else
                    $txt = str_replace(array('%%articlelink%%','%%articletitle%%'),
                                       array($article_link,  $title), $txt);
                  $article_link = "\r\n\r\n".$txt."\r\n";
                }
              }

              $topic_id = $fc->InsertTopic(true, $post_descr, $post_article, $article_link);

              if($add_link && ($topic_id !== false))
              {
                // Prepare article suffix text: add "link to topic"
                $txt = trim($this->phrases['article_backlink_text']);
                $topic_link  = $fc->conf->RewriteTopicLink($topic_id,$fc->conf->topic_arr['title']);
                $topic_title = $fc->conf->topic_arr['title'];
                if(empty($txt) || !strlen($txt))
                {
                  $txt = '<a href="[topiclink]">[topictitle]</a>';
                }
                $txt = str_replace(array('[topiclink]','[topictitle]'),
                                   array($topic_link,  $topic_title), $txt);

                // If description has code, then add to it, otherwise to article
                if(!empty($description) && strlen(trim($description)))
                {
                  $column = 'description';
                  $value  = $description;
                }
                else
                {
                  $column = 'article';
                  $value  = $article;
                }
                $value .= ' '.$txt;
                // Update article once more with updated value:
                $DB->skip_curly = true;
                $DB->query('UPDATE '.$this->table_name.
                           ' SET '.$column." = '".$DB->escape_string(trim($value))."'".
                           ' WHERE articleid = '.(int)$articleid);
                $DB->skip_curly = false;
              }
            }
          }
          unset($fc);
        }
      }
    }

    //SD322 - create a copy of the current article
    if($CopyArticle)
    {
      $fieldlist = array();
      $DB->ignore_error = true;
      $getnonkeyfields = $DB->query('SHOW COLUMNS FROM '.$this->table_name." WHERE `Key` <> 'PRI'");
      $DB->ignore_error = false;
      if($DB->errno || empty($getnonkeyfields))
      {
        RedirectPage($this->_link.'&amp;articleaction=displayarticleform&amp;articleid='.$articleid.
                     '&amp;load_wysiwyg='.$load_wysiwyg, AdminPhrase('could_not_create_article_copy'));
      }
      else
      {
        while($field = $DB->fetch_array($getnonkeyfields,null,MYSQL_ASSOC))
        {
          if($field['Field'] != 'articleid')
            $fieldlist[] = $field['Field'];
        }
        $fieldlist = implode(',', $fieldlist);
        $DB->skip_curly = true;
        $DB->query('INSERT INTO '.$this->table_name.' ('.$fieldlist.')'.
                   ' SELECT '.$fieldlist.
                   ' FROM '.$this->table_name.
                   ' WHERE articleid = '.(int)$articleid);
        $articleid = $DB->insert_id();
        $DB->skip_curly = false;

        $DB->result_type = MYSQL_ASSOC;
        $order = $DB->query_first('SELECT MAX(displayorder) maxdisp'.
                                  ' FROM '.$this->table_name.
                                  ' WHERE categoryid = '.(int)$categoryid);
        $order = (isset($order['maxdisp']) ? (int)$order['maxdisp'] : 0);
        $DB->query('UPDATE '.$this->table_name." SET title = CONCAT(title,' *'), views = 0,".
                   " datecreated = %d, dateupdated = 0, seo_title = '', displayorder = %d,".
                   " thumbnail = '', featured = 0, featuredpath = ''".
                   ' WHERE articleid = %d',
                   TIME_NOW, ($order+1), $articleid);
      }
    }

    RedirectPage($this->_link.'&amp;articleaction=displayarticleform&amp;articleid='.$articleid.
                 '&amp;load_wysiwyg='.$load_wysiwyg, AdminPhrase('article_updated'));

  } //UpdateArticle


  // ##########################################################################
  // DISPLAY ARTICLE FORM
  // ##########################################################################

  function DisplayArticleForm()
  {
    global $DB, $mainsettings, $sdurl, $userinfo, $usersystem, $admin_phrases;

    $articleid = Is_Valid_Number(GetVar('articleid', 0, 'whole_number'),0,1);

    if($articleid > 0)
    {
      $DB->result_type = MYSQL_ASSOC;
      $article = $DB->query_first('SELECT * FROM '.$this->table_name.
                                  ' WHERE articleid = '.(int)$articleid);
      //SD322: restrict access for non-admins if "author mode" is active
      if( empty($article['articleid']) ||
          ($this->authormode && !empty($article['org_author_id']) &&
           ($article['org_author_id'] != $userinfo['userid'])) )
      {
        DisplayMessage(AdminPhrase('invalid_request'),true);
        return false;
      }
    }
    else
    {
      $articleid = 0;
      $article = array(
         'articleid'       => 0,
         'categoryid'      => GetVar('pageid', 0, 'whole_number'),
         'settings'        => 3, // 1 + 2 use global settings, display online
         'author'          => 0,
         'views'           => 0,
         'displayorder'    => 0,
         'datecreated'     => 0,
         'dateupdated'     => 0,
         'datestart'       => 0,
         'dateend'         => 0,
         'author'          => '',
         'title'           => '',
         'metadescription' => '',
         'metakeywords'    => '',
         'description'     => '',
         'article'         => '',
         'seo_title'       => '',
         'tags'            => '',
         'max_comments'    => 0,
         'thumbnail'       => '',
         'featured'        => 0,
         'featuredpath'    => '',
         'rating'          => '',
         'template'        => '', //SD342
         'access_view'     => '', //SD342
         'approved_by'     => '', //v3.5.1 / SD344
         'approved_date'   => 0,  //v3.5.1 / SD344
         'org_author_id'   => 0   //v3.5.1 / SD344
         );
    }
    //SD360:
    $article['featured_start'] = isset($article['featured_start'])?(int)$article['featured_start']:0;
    $article['featured_end'] = isset($article['featured_end'])?(int)$article['featured_end']:0;

    $online = (($article['settings'] & (self::$articlebitfield['displayonline'])) > 0);

    // If we are using global settings, show them so we can edit them better
    if($article['settings'] & 1)
    {
      $article['settings'] = $this->globalsettings + 1;
    }

    if($articleid && strlen($article['description']))
    {
      $description_link_style = 'display: none;';
      $description_style = '';
    }
    else
    {
      $description_link_style = '';
      $description_style = 'display: none;';
    }

    echo '
    <form method="post" action="'.$this->_link.'" enctype="multipart/form-data" id="articleform" name="articleform" class="form-horizontal">
    '.PrintSecureToken().'
	<div class="col-sm-9">
		<h3 class="header blue lighter">' . AdminPhrase('article_details') . '</h3>
		
	<ul class="nav nav-tabs" role="tablist">
		<li class="active">
			<a data-toggle="tab" href="#details">'.AdminPhrase('edit_tab_title1').'</a>
		</li>
		<li>
			<a data-toggle="tab" href="#meta">'.AdminPhrase('edit_tab_title2').'</a>
		</li>
		<li>
			<a data-toggle="tab" href="#dates">'.AdminPhrase('edit_tab_title3').'</a>
		</li>
		<li>
			<a data-toggle="tab" href="#other">'.AdminPhrase('edit_tab_title4').'</a>
		</li>
		<li>
			<a data-toggle="tab" href="#attach">'. $this->phrases['title_attachments'].'</a>
		</li>
	</ul>
	<div class="tab-content">
		<div class="tab-pane in active" id="details">
      	<table class="table table-striped ">
      		<tr>
				<td width="10%">' . AdminPhrase('title') . '</td>
				<td width="40%"><input type="text" name="title" class="form-control" value="' . htmlspecialchars($article['title'],ENT_QUOTES) . '" /></td>
				<td width="5%" class="align-right">' . AdminPhrase('seo_title') . '</td>
				<td width="45%">
          			<input type="text" name="seotitle" class="form-control" value="' . $article['seo_title'] . '" />
					<span class="helper-text">' . AdminPhrase('seo_title_hint1') . '</span>
				</td>
      		</tr>';

    echo '
      <tr>
        <td>' . AdminPhrase('status') . '</td>
        <td >' ;

    //SD322: online status/permissions not changeable in author mode
    if(!$this->authormode)
    {
      echo '
		<div class="input-group">
          <select name="displayonline" class="form-control">
            <option value="1"' . ( $online?' selected="selected"':'').'>' . AdminPhrase('online') . '</option>
            <option value="0"' . (!$online?' selected="selected"':'').'>' . AdminPhrase('offline') . '</option>
          </select>
          ';
		if(!empty($articleid)) //SD342: only display permission link for saved articles
      {
        echo '<span class="input-group-addon">
          <a title="'.
          AdminPhrase('article_permissions').'" class="cbox permissionslink" href="'.
          $this->_link.'&amp;action=display_article_permissions&amp;articleid='.
          $articleid . '"><i class="ace-icon fa fa-key orange bigger-110"></i>  ' . /*IMAGE_PERMISSIONS .*/ '</a></span>';
      }
	  echo '</div>';
	  
      //v3.5.1 / SD344: show last approver
      if(!empty($articleid) && !empty($article['approved_by']))
      {
        echo '<span class="helper-text">'.AdminPhrase('approved_by').' '.$article['approved_by'].' - '.
             (empty($article['approved_date'])?'':DisplayDate($article['approved_date'],'',false)).'</span>';
      }
    }
    else
    {
      echo AdminPhrase('status_pending_review');
    }
    echo '</td>
        <td  class="align-right">' . AdminPhrase('author') . '</td>
        <td >
          ';

    if($this->HasAdminRights ||
       ($this->authormode && empty($article['author']) && empty($userinfo['authorname'])) )
    {
      /* SD344 / v3.5.1:
        - for site-/plugin admins:
        - extend author name with autocomplete feature for admins
        - add "Reassign Author" option (shown by JS!) for admins
        - add extra inputs to store original and new author id's
      */
      $id = '';
      if($this->HasAdminRights)
      {
        $id = 'id="UserSearch" ';
      }
      echo '<input type="text" '.$id.'name="author" class="form-control" value="' . $article['author'] . '" />';
      if($this->HasAdminRights)
      {
        echo '
        <input type="hidden" id="newAuthorID" name="newAuthorID" value="'.(int)$article['org_author_id'].'" />
        <input type="hidden" id="org_author_id" value="'.(int)$article['org_author_id'].'" />
        <span class="helper-text" style="display:none"><input type="checkbox" class="ace" id="reassign_author" name="reassign_author" value="1" /><span class="lbl"> '.
        AdminPhrase('reassign_author').'</span></span>';
      }
    }
    else
    {
      if(!empty($userinfo['authorname']))
      {
        echo $userinfo['authorname'];
        echo '<input type="hidden" name="author" style="width: 90%" value="'.$userinfo['authorname'].'" />';
      }
      else
      {
        echo $article['author'];
      }
    }

    echo '</td>
      </tr>';

    echo '
      <tr>
        <td>' . AdminPhrase('page') . '</td>
		<td>';

    //SD341: display article on one or multiple pages
    $page_li_items = '';
    if(!empty($articleid))
    {
      $getpages = $DB->query('SELECT c.categoryid, c.name,'.
        ' IFNULL((SELECT 1 FROM {p'.$this->pluginid.'_articles_pages} a'.
        ' WHERE a.categoryid = c.categoryid AND a.articleid = %d),0) article_present'.
        ' FROM {categories} c ORDER BY c.name', $articleid);
      while($page = $DB->fetch_array($getpages,null,MYSQL_ASSOC))
      {
        if(!empty($page['article_present']))
        {
          $cid = $page['categoryid'];
          $page_li_items .= '
          <li class="delpage" id="page_'.$cid.'">'.
          '<input type="hidden" name="pages[]" value="'.$cid.'" />'.
          '<i class="ace-icon fa fa-trash-o red bigger-110"></i>'.
          $page['name'].'</li>';
        }
      }
    }

    //SD344: only display categories which have the current plugin assigned
    DisplayArticleCategories($this->pluginid, $article['categoryid'], 1, 0, '',
                             'categoryid', '',
                             true, $this->_limitpages);

    echo '</td>
         <td class="td2" width="20%" style="vertical-align: top">
           ' . AdminPhrase('multiple_pages_hint1').'
         </td>
         <td>
		 <div class="input-group">
		';

    //SD360: display all pages, unless limited (WIP)
    DisplayArticleCategories($this->pluginid, $article['categoryid'], 1, 0, '',
                             'page_select', '',
                             true, $this->_limitpages);

    echo '
			<span class="input-group-addon"><i class="ace-icon fa fa-plus green bigger-110" id="addpage"></i></span></div>
			
		
         <br />
         &nbsp;' . AdminPhrase('multiple_pages_hint2').'<br />
         <ul id="article_pages" class="list-unstyled">'.$page_li_items.'</ul>
        </td>
      </tr>';

    //SD322: new fields for original author name and creation date
    if($articleid)
    {
      echo '
      <tr>
        <td class="td2">' . AdminPhrase('original_author') . '</td>
        <td class="td3">' . $article['org_author_name'] .
          (empty($article['org_created_date']) ? '' : '<br />' . DisplayDate($article['org_created_date'])) . '
        </td>
        <td class="td2">' . AdminPhrase('last_modified') . '</td>
        <td class="td3">' . $article['last_modifier_name'] .
          (empty($article['last_modified_date']) ? '' : '<br />' . DisplayDate($article['last_modified_date'])) . '
        </td>
      </tr>';
    }

    echo '
    </table>
	</div>
	<div class="tab-pane" id="meta">

  <table width="100%" class="table table-striped">
      <tr>
        <td class="td2">' . AdminPhrase('meta_description') . '</td>
        <td class="td3" align="center"><textarea name="metadescription" style="width: 95%;" rows="4" cols="30">' . $article['metadescription'] . '</textarea></td>
      </tr>
      <tr>
        <td class="td2">' . AdminPhrase('meta_keywords') . '</td>
        <td class="td3" align="center"><textarea name="metakeywords" style="width: 95%;" rows="4" cols="30">' . $article['metakeywords'] . '</textarea></td>
      </tr>
  </table>

	</div>
	<div class="tab-pane" id="dates">
  <table class="table table-striped">
    ';
    //SD322: all date/time values get assigned by JS, not in below HTML!
    $dst       = empty($userinfo['dstonoff']) ? 0 : 1;
    $tz_offset = (isset($userinfo['timezoneoffset']) ? $userinfo['timezoneoffset'] : 0);
    if($articleid)
    {
      $datecreated = DisplayDate($article['datecreated'], '', false, true);
      $dateupdated = DisplayDate($article['dateupdated'], '', false, true);
    }
    else
    {
      $datecreated = TIME_NOW + 3600 * ($tz_offset + $dst);
      $dateupdated = 0;
    }
    echo '<tr>
      <td class="td2" width="25%">' . AdminPhrase('date_created') . '</td>
      <td class="td3">
        <input type="hidden" id="datecreated" name="datecreated" value="' . DisplayDate($article['datecreated'], 'yyyy-mm-dd') . '" />
		<div class="input-group">
        	<input type="text" id="datecreated2" class="form-control" name="datecreated2" rel="'.($datecreated?$datecreated:'0').'" value="" />
			<span class="input-group-addon">
				<i class="ace-icon fa fa-calendar"></i>
			</span>
		</div>
       
      </td>
    </tr>
    <tr>
      <td class="td2">' . AdminPhrase('date_updated') . '</td>
      <td class="td3">
        <input type="hidden" id="dateupdated" name="dateupdated" value="' . DisplayDate($article['dateupdated'], 'yyyy-mm-dd') . '" />
		<div class="input-group">
        <input type="text" id="dateupdated2" name="dateupdated2" rel="'.($dateupdated?$dateupdated:'0').'" value="" class="form-control" />
		<span class="input-group-addon">
				<i class="ace-icon fa fa-calendar"></i>
			</span>
        </div>
      </td>
    </tr>
    ';

    $datestart = DisplayDate($article['datestart'], '', false, true);
    $dateend   = DisplayDate($article['dateend'], '', false, true);
    //SD360:
    $featured_start = DisplayDate($article['featured_start'], '', false, true);
    $featured_end   = DisplayDate($article['featured_end'], '', false, true);
    echo '
    <tr>
      <td class="td2">' . AdminPhrase('start_publishing') . '</td>
      <td class="td3">
        <input type="hidden" id="datestart" name="datestart" value="' . DisplayDate($article['datestart'], 'yyyy-mm-dd') . '" />
		<div class="input-group">
        <input type="text" id="datestart2" name="datestart2" rel="'.($datestart?$datestart:'0').'" value="" class="form-control" />
		<span class="input-group-addon">
				<i class="ace-icon fa fa-calendar"></i>
		</span>
		</div>
      </td>
    </tr>
    <tr>
      <td class="td2">' . AdminPhrase('end_publishing') . '</td>
      <td class="td3">
        <input type="hidden" id="dateend" name="dateend" value="' . DisplayDate($article['dateend'], 'yyyy-mm-dd') . '" />
		<div class="input-group">
        <input type="text" id="dateend2" name="dateend2" rel="'.($dateend?$dateend:'0').'" value="" class="form-control" />
		<span class="input-group-addon">
				<i class="ace-icon fa fa-calendar"></i>
		</span>
		</div>
      </td>
    </tr>
    <tr>
      <td class="td2">' . AdminPhrase('start_featuring') . '</td>
      <td class="td3" >
        <input type="hidden" id="featured_start" name="featured_start" value="' . DisplayDate($article['featured_start'], 'yyyy-mm-dd') . '" />
		<div class="input-group">
        <input type="text" id="featured_start2" name="featured_start2" rel="'.($featured_start?$featured_start:'0').'" value="" class="form-control" />
        <span class="input-group-addon">
				<i class="ace-icon fa fa-calendar"></i>
		</span>
		</div>
      </td>
    </tr>
    <tr>
      <td class="td2">' . AdminPhrase('end_featuring') . '</td>
      <td class="td3" style="white-space: nowrap;">
        <input type="hidden" id="featured_end" name="featured_end" value="' . DisplayDate($article['featured_end'], 'yyyy-mm-dd') . '" />
		<div class="input-group">
        <input type="text" id="featured_end2" name="featured_end2" rel="'.($featured_end?$featured_end:'0').'" value="" class="form-control" />
        <span class="input-group-addon">
				<i class="ace-icon fa fa-calendar"></i>
		</span>
		</div>
      </td>
    </tr>
  </table>
	</div>
	<div class="tab-pane" id="other">
  <table class="table table-striped">
      <tr>
        <td class="td2" width="20%">' . AdminPhrase('views') . '</td>
        <td class="td3" width="80%"><input type="text" name="views" class="form-control" value="' . $article['views'] . '" /></td>
      </tr>
      <tr>
        <td class="td2" width="20%">' . AdminPhrase('template_name') . '</td>
        <td class="td3" width="80%">';

    //SD360: take into account templates setting for either file- or db-storage
    if(empty($mainsettings['templates_from_db']))
    {
      clearstatcache();
      $folderpath = SD_INCLUDE_PATH.'tmpl/';
      $files1 = @scandir($folderpath);
      $files2 = @scandir($folderpath.'defaults/');
      $files = array_merge((array)$files1, (array)$files2);
      if(empty($files))
      {
        echo '<input type="text" name="template" class="form-control" value="' . $article['template'] . '" />';
      }
      else
      {
        echo '<select name="template" class="form-control">
              <option value="">Default (articles.tpl)</option>';
        $files = array_unique($files);
        natcasesort($files);
        foreach($files as $filename)
        {
          if(($filename != 'articles.tpl') && (substr($filename,0,8) == 'articles') && (strrpos($filename,'.tpl') !== false))
          {
            echo '<option value="'.$filename.'"'.($filename==$article['template']?' selected="selected"':'').'>'.$filename.'</option>';
          }
        }
        echo '</select>';
      }
    }
    else
    {
      if(!class_exists('SD_Smarty'))
      require(SD_INCLUDE_PATH.'class_sd_smarty.php');
      $tmpls = SD_Smarty::GetTemplateNamesForPlugin($this->pluginid,true);
      if($tmpls === false)
      {
        echo '*** NO TEMPLATES AVAILABLE! ***';
      }
      else
      {
        //<option value="">Default (articles.tpl)</option>
        echo '<select name="template" class="form-control">';
        foreach($tmpls as $entry)
        {
          echo '<option value="'.htmlspecialchars($entry['tpl_name']).'"';
          if( ($entry['tpl_name']==$article['template']) ||
              (empty($article['template']) && ($entry['tpl_name']=='articles.tpl')) )
          {
            echo ' selected="selected"';
          }
          echo '>'.$entry['tpl_name'].'</option>';
        }
        echo '</select>';
      }
    }

    require_once(SD_INCLUDE_PATH.'class_sd_tags.php');
    if(!empty($articleid))
    {
      $tags_old = sd_ConvertStrToArray($article['tags'],',');
      //SD343: fetch tags from global table
      $tags = SD_Tags::GetPluginTags($this->pluginid,$articleid);
      $tags = array_unique(array_merge($tags,$tags_old));
      if(!empty($tags)) $article['tags'] = implode(',', $tags);
      unset($tags,$tags_old);
    }

    echo '
        </td>
      </tr>
      <tr>
        <td class="td2" width="20%">' . AdminPhrase('tags') . '<br />';
    //SD343: display all available tags
    $available_tags = SD_Tags::GetPluginTags($this->pluginid,0,array(0,1,3),0); //SD343: 0,1,3
    if(!empty($available_tags) && is_array($available_tags))
    {
      if(count($available_tags)) $available_tags = array_unique(array_values($available_tags));
      echo '<select id="availabletags" size="5" class="form-control">';
      foreach($available_tags as $entry)
      {
        echo '<option value="'.$entry.'">'.$entry.'</option>';
      }
      echo '</select><span class="helper-text">
      <a id="addtag" href="#" onclick="return false;" class="round_button">'.
      '<i class="ace-icon fa fa-tag green bigger-120"></i> '.
        AdminPhrase('add_to_tags').'&nbsp;</a></span>';
    }
    echo '</td>
        <td class="td3" width="50%">
          <input type="text" id="tags" name="tags" class="form-control" value="' . $article['tags'] . '" />
        </td>
      </tr>
  </table>
	</div>
	
	<div class="tab-pane" id="attach">';
	
	 //SD360: new: allow attachment to article
    if($this->CanAttachFiles && $this->InitAttachment())
    {
      $this->_attachment->setUserid($userinfo['userid']);
      $this->_attachment->setUsername($userinfo['username']);
      $this->_attachment->setObjectID($articleid);

      echo '

        <div class="articles-attachments col-sm-4"><p>'.
         $this->phrases['title_attachments'].'</p>
         '.$this->_attachment->getAttachmentsListHTML(false,true).'
        </div>
        <div id="attach_dummy" style="display:none"></div>
      	<div class="articles-attachments col-sm-8">'.$this->phrases['upload_attachments'].'<br />
        <input id="attachment" name="attachment" type="file" /><span class="helper-text">'.
        AdminPhrase('user_allowed_filetypes'). '&nbsp;' .
        $this->settings['valid_attachment_types'] . '&nbsp;( < ' .
        $this->settings['attachments_max_size'].' KB )</span>
        </div>
		<div class="clearfix"></div>';
    }
		
	echo'</div>
</div>

';
	 $descr_exists = strlen(trim($article['description']));

   echo '<div class="space-4"></div>
   <div id="accordian" class="accordian-style2 panel-group">
   		<div class="panel panel-default">
			<div class="panel-heading">
				<h4 class="panel-title">
					<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#collapseOne">
							<i class="ace-icon fa fa-angle-right bigger-110" data-icon-hide="ace-icon fa fa-angle-down" data-icon-show="ace-icon fa fa-angle-right"></i>
									&nbsp;' . AdminPhrase('article_description') . '
					</a>
				</h4>
			</div>
			<div class="panel-collapse collapse" id="collapseOne">
				<div class="panel-body">';
				
				 echo '
    
    <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td class="td1"></td>
    </tr>
    <tr>
      <td class="td3">
      ';

    if(!empty($this->settings['auto_add_linebreaks'])) $article['description'] = sd_simplehtmlformat($article['description']);
    
	
	PrintWysiwygElement('description', $article['description'], 15, 80);
    echo '
      </td>
    </tr>
    </table>
    ';	
					
		echo	'</div>
			</div>
		</div>
		
		<div class="panel panel-default">
			<div class="panel-heading">
				<h4 class="panel-title">
					<a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo">
							<i class="ace-icon fa fa-angle-down bigger-110" data-icon-hide="ace-icon fa fa-angle-down" data-icon-show="ace-icon fa fa-angle-right"></i>
									&nbsp;' . AdminPhrase('article') . '
					</a>
				</h4>
			</div>
			<div class="panel-collapse collapse in" id="collapseTwo">
				<div class="panel-body">';
				
			echo '
    <table id="article-table" width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td class="td1" colspan="2">' . AdminPhrase('pagebreak_description') . '</td>
    </tr>
    <tr>
      <td class="td3" colspan="2">
      ';

    if(!empty($this->settings['auto_add_linebreaks'])) $article['article'] = sd_simplehtmlformat($article['article']);
	
	echo '<div class="table-responsive">
	<table class="table">
	<tr>
		<td>';
    PrintWysiwygElement('article', $article['article'], 30, 80);
    echo '
	</td>
	</tr>
	</table>
	</div>
      </td>
    </tr>';

    //SD360: new: allow attachment to article
    if($this->CanAttachFiles && $this->InitAttachment())
    {
      $this->_attachment->setUserid($userinfo['userid']);
      $this->_attachment->setUsername($userinfo['username']);
      $this->_attachment->setObjectID($articleid);
/*
      echo '
    <tr>
      <td class="td3" width="50%" valign="top">
        <div class="articles-attachments"><p>'.
         $this->phrases['title_attachments'].'</p>
         '.$this->_attachment->getAttachmentsListHTML(false,true).'
        </div>
        <div id="attach_dummy" style="display:none"></div>
      </td>
      <td class="td3"><div class="articles-attachments">'.$this->phrases['upload_attachments'].'<br />
        <input id="attachment" name="attachment" type="file" size="30" /><br />'.
        AdminPhrase('user_allowed_filetypes').' '.
        $this->settings['valid_attachment_types'].'<br />&lt; '.
        $this->settings['attachments_max_size'].' KB
        </div>
      </td>
    </tr>';
	*/
    }

    echo '
    </table>';
				
				
					
		echo	'</div>
			</div>
		</div>
	</div>
';


    echo '
      <input type="hidden" id="pluginid" value="'.$this->pluginid.'" />';
    if($articleid > 0)
    {
      echo '
      <input type="hidden" name="articleaction" value="updatearticle" />
      <input type="hidden" name="articleid" value="'.$articleid.'" />
      <div class="center">
        <button type="submit" class="btn btn-info" value="" ><i class="ace-icon fa fa-check"></i> ' . AdminPhrase('update_article') . '</button>
        <input type="checkbox" class="ace" name="CopyArticle" value="1" /><span class="lbl"> '.AdminPhrase('create_copy').'</span>
      </div>
      ';
    }
    else
    {
      echo '
      <input type="hidden" name="articleaction" value="insertarticle" />
      <div class="center">
	  	<button class="btn btn-info" type="submit" value=""><i class="ace-icon fa fa-plus"></i> ' . AdminPhrase('insert_article') . '</button>
	</div>
      ';
    }

    echo '</div>';
	 
	// ############## RIGHT COLUMN ###########
	echo '<div class="col-sm-3">';

    // ############## ARTICLE SETTINGS ###########

    echo '<h3 class="header blue lighter">' . AdminPhrase('settings') . '</h3>
    <div class="td3" id="options-list">
      <ul id="article-settings" class="list-unstyled">
      <li><label for="articleglobaloptions"><input type="checkbox" class="ace" value="1" id="articleglobaloptions" name="useglobalsettings"' .(($article['settings'] & self::$articlebitfield['useglobalsettings'])? ' checked="checked"':'') . ' onclick="javascript:DisableCheckboxes(-1);" /><span class="lbl"> ' . AdminPhrase('use_global_settings') . '</span></label></li>
      <li><label for="ao1"><input type="checkbox"  value="1" id="ao1" class="globalsetting ace" name="displaytitle"            ' .(($article['settings'] & self::$articlebitfield['displaytitle'])?            'checked="checked"':'') . ' /><span class="lbl"> ' . AdminPhrase('display_title') . '</span></label></li>
      <li><label for="ao2"><input type="checkbox"  value="1" id="ao2" class="globalsetting ace" name="displayauthor"           ' .(($article['settings'] & self::$articlebitfield['displayauthor'])?           'checked="checked"':'') . ' /><span class="lbl"> ' . AdminPhrase('display_author') . '</span></label></li>
      <li><label for="ao3"><input type="checkbox"  value="1" id="ao3" class="globalsetting ace" name="displaycreateddate"      ' .(($article['settings'] & self::$articlebitfield['displaycreateddate'])?      'checked="checked"':'') . ' /><span class="lbl"> ' . AdminPhrase('display_creation_date') . '</span></label></li>
      <li><label for="ao4"><input type="checkbox"  value="1" id="ao4" class="globalsetting ace" name="displayupdateddate"      ' .(($article['settings'] & self::$articlebitfield['displayupdateddate'])?      'checked="checked"':'') . ' /><span class="lbl"> ' . AdminPhrase('display_updated_date') . '</span></label></li>
      <li><label for="ao5"><input type="checkbox"  value="1" id="ao5" class="globalsetting ace" name="displaydescription"      ' .(($article['settings'] & self::$articlebitfield['displaydescription'])?      'checked="checked"':'') . ' /><span class="lbl"> ' . AdminPhrase('display_description_in_article') . '</span></label></li>
      <li><label for="ao6"><input type="checkbox"  value="1" id="ao6" class="globalsetting ace" name="displaycomments"         ' .(($article['settings'] & self::$articlebitfield['displaycomments'])?         'checked="checked"':'') . ' /><span class="lbl"> ' . AdminPhrase('display_comments') . '</span></label></li>
      <li><label for="ao7"><input type="checkbox"  value="1" id="ao7" class="globalsetting ace" name="displayviews"            ' .(($article['settings'] & self::$articlebitfield['displayviews'])?            'checked="checked"':'') . ' /><span class="lbl"> ' . AdminPhrase('display_views_count') . '</span></label></li>
      <li><label for="ao8"><input type="checkbox"  value="1" id="ao8" class="globalsetting ace" name="displaymainpage"         ' .(($article['settings'] & self::$articlebitfield['displaymainpage'])?         'checked="checked"':'') . ' /><span class="lbl"> ' . AdminPhrase('display_on_main_page') . '</span></label></li>
      <li><label for="ao9"><input type="checkbox"  value="1" id="ao9" class="globalsetting ace" name="displaysticky"           ' .(($article['settings'] & self::$articlebitfield['displaysticky'])?           'checked="checked"':'') . ' /><span class="lbl"> ' . AdminPhrase('sticky_article') . '</span></label></li>
      <li><label for="ao10"><input type="checkbox"  value="1" id="ao10" class="globalsetting ace" name="displayratings"          ' .(($article['settings'] & self::$articlebitfield['displayratings'])?          'checked="checked"':'') . ' /><span class="lbl"> ' . AdminPhrase('display_ratings') . '</span></label></li>
      <li><label for="ao11"><input type="checkbox"  value="1" id="ao11" class="globalsetting ace" name="displayemailarticlelink" ' .(($article['settings'] & self::$articlebitfield['displayemailarticlelink'])? 'checked="checked"':'') . ' /><span class="lbl"> ' . AdminPhrase('display_email_link') . '</span></label></li>
      <li><label for="ao12"><input type="checkbox"  value="1" id="ao12" class="globalsetting ace" name="displayprintarticlelink" ' .(($article['settings'] & self::$articlebitfield['displayprintarticlelink'])? 'checked="checked"':'') . ' /><span class="lbl"> ' . AdminPhrase('display_print_link') . '</span></label></li>
      <li><label for="ao13"><input type="checkbox"  value="1" id="ao13" class="globalsetting ace" name="displaypdfarticlelink"   ' .(($article['settings'] & self::$articlebitfield['displaypdfarticlelink'])?   'checked="checked"':'') . ' /><span class="lbl"> ' . AdminPhrase('display_pdf_link') . '</span></label></li>
      <li><label for="ao14"><input type="checkbox"  value="1" id="ao14" class="globalsetting ace" name="displaysocialbuttons"    ' .(($article['settings'] & self::$articlebitfield['displaysocialmedia'])?      'checked="checked"':'') . ' /><span class="lbl"> ' . AdminPhrase('display_social_links') . '</span></label></li>
      <li><label for="ao15"><input type="checkbox"  value="1" id="ao15" class="globalsetting ace" name="displayaspopup"          ' .(($article['settings'] & self::$articlebitfield['displayaspopup'])?          'checked="checked"':'') . ' /><span class="lbl"> ' . AdminPhrase('display_as_popup') . '</span></label></li>
      <li><label for="ao16"><input type="checkbox"  value="1" id="ao16" class="globalsetting ace" name="displaytags"             ' .(($article['settings'] & self::$articlebitfield['displaytags'])?             'checked="checked"':'') . ' /><span class="lbl"> ' . AdminPhrase('display_tags') . '</span></label></li>
      <li><label for="ao17"><input type="checkbox"  value="1" id="ao17" class="globalsetting ace" name="ignore_excerpt_mode"     ' .(($article['settings'] & self::$articlebitfield['ignoreexcerptmode'])?       'checked="checked"':'') . ' /><span class="lbl"> ' . AdminPhrase('ignore_excerpt_mode') . '</span></label></li>
      <li><label for="ao18"><input type="checkbox"  value="1" id="ao18" class="globalsetting ace" name="linktomainpage"          ' .(($article['settings'] & self::$articlebitfield['linktomainpage'])?          'checked="checked"':'') . ' /><span class="lbl"> ' . AdminPhrase('link_to_main_page') . '</span></label></li>
      <li>'.AdminPhrase('articles_max_comments').' <input type="text" maxlength="4" value="'.$article['max_comments'].'" size="4" name="max_comments" /></li>
      </ul>
    </div>
    ';

    // ############## ARTICLE REVIEW RATING ###########

    echo '<h3 class="header blue lighter">' . AdminPhrase('review_rating') . '</h3>';
    echo '
    <div class="td3" id="options-review">
      '.AdminPhrase('review_rating_hint').'<br />
      <input type="text" name="rating" maxlength="4" size="3" value="' . $article['rating'] . '" />&nbsp;/&nbsp;10
    </div>
    ';


    // ############## ARTICLE THUMBNAIL ###########

    echo '<h3 class="header blue lighter">' . AdminPhrase('thumbnail_title') . '</h3>';

    $file = empty($article['thumbnail'])?'':$article['thumbnail'];
    echo '
    <div class="td3" id="options-thumbnail">
      <input type="hidden" id="thumbnailpath" name="thumbnail" value="'.$file.'" />
      <input type="hidden" id="thumbnail_org" name="thumbnail_org" value="'.$file.'" />
      <span>'.AdminPhrase('thumbnail_hint2').'</span>';

    //SD342 thumbnail
    $thumb_ok = false;
    if(($articleid>0) && !empty($file))
    {
      require_once(SD_INCLUDE_PATH.'class_sd_media.php');
      $imagepath = ROOT_PATH.SD_ARTICLE_THUMBS_DIR.$file;
      $sdi = new SD_Image($imagepath);
      $sdi->setImageAlt(AdminPhrase('error_no_thumbnail'));
      $sdi->setImageID('thumbnail');
      $sdi->setImageTitle($file);
      $sdi->setImageUrl($sdurl.SD_ARTICLE_THUMBS_DIR);
      $sdi->ScaleDisplayWidth(200, 200);
      if($sdi->getImageValid())
      {
        $thumb_ok = true;
        echo '<span>'.$sdi->getHtml().'</span>
        <span id="thumb_details">'.$file.'<br />
        '.AdminPhrase('width').' '.$sdi->getImageWidth().'px / '.
          AdminPhrase('height').' '.$sdi->getImageheight().'px<br />
        </span>
        <span>
        <a id="deletethumb" href="'.$articleid.'" onclick="return false;">'.
          AdminPhrase('delete_thumbnail').'</a>
        ';
      }
      else
      {
        echo '<span><strong>Thumb specified, but invalid!</strong></span>';
      }
      unset($sdi);
    }
    if(!$thumb_ok)
    {
      echo '
      <img id="thumbnail" alt="" src="'.SD_INCLUDE_PATH.'css/images/blank.gif" style="clear:both;display:none" />
      <span id="thumb_details">'.AdminPhrase('error_no_thumbnail').'</span>';
    }
    echo '
      <a style="display:'.($thumb_ok?'block':'none').'" id="detachthumb" href="'.
        $articleid.'" onclick="return false;">'.
        AdminPhrase('detach_not_delete_image').'</a>
      <a class="thumbupload" rel="iframe modal:false" href="'.
        SD_INCLUDE_PATH.'thumbupload.php?action=thumb&amp;new=1">'.
        AdminPhrase('thumbnail_hint').'</a><span>
    </div>
    ';


    if(!$this->authormode)
    {
      // ############### FEATURED PICTURE ###############

      echo '<h3 class="header blue lighter">' . AdminPhrase('featured_title') . '</h3>';

      $file = empty($article['featuredpath'])?'':$article['featuredpath'];
      echo '
      <div class="td3" id="options-featured">
        <input type="hidden" id="featuredpath" name="featuredpath" value="'.$file.'" />
        <input type="hidden" id="featured_org" name="featured_org" value="'.$file.'" />
        '.AdminPhrase('featured_title').'

        <select name="featured">
          <option value="1" '.(!empty($article['featured'])?'selected="selected"':'').'>'.AdminPhrase('common_yes').'</option>
          <option value="0" '.( empty($article['featured'])?'selected="selected"':'').'>'.AdminPhrase('common_no').'</option>
        </select>
        <span>'.AdminPhrase('featured_hint').'</span>
        <span><strong>'.AdminPhrase('featured_image').'</strong></span>
        ';

      //SD342 thumbnail
      $thumb_ok = false;
      if(($articleid > 0) && !empty($file))
      {
        require_once(SD_INCLUDE_PATH.'class_sd_media.php');
        $file = ((strpos($file,'featured_')!==0)?'featured_':'').$file;
        $imagepath = ROOT_PATH.SD_ARTICLE_FEATUREDPICS_DIR.$file;
        $sdi = new SD_Image($imagepath);
        $sdi->setImageAlt(AdminPhrase('error_no_thumbnail'));
        $sdi->setImageID('featured');
        $sdi->setImageTitle($file);
        $sdi->setImageUrl($sdurl.SD_ARTICLE_FEATUREDPICS_DIR);
        $sdi->ScaleDisplayWidth(200, 200);
        if($sdi->getImageValid())
        {
          $thumb_ok = true;
          echo '<span>'.$sdi->getHtml().'</span>
          <span id="featuredpic_details">'.$file.'<br />
          '.AdminPhrase('width').' '.$sdi->getImageWidth().'px / '.
            AdminPhrase('height').' '.$sdi->getImageheight().'px</span>
          <a id="deletefeatured" href="'.$articleid.'" onclick="return false;">'.
            AdminPhrase('delete_featuredpic').'</a>';
        }
        else
        {
          echo '<span><strong>Picture specified, but invalid!</strong></span>';
        }
        unset($sdi);
      }
      if(!$thumb_ok)
      {
        echo '
        <img id="featured" alt="" src="'.SD_INCLUDE_PATH.'css/images/blank.gif" style="clear:both;display:none" />
        <span id="featuredpic_details">'.AdminPhrase('error_no_thumbnail').'</span>';
      }

      echo '
        <a style="display:'.($thumb_ok?'block':'none').'" id="detachfeatured" href="'.
          $articleid.'" onclick="return false;">'.
          AdminPhrase('detach_not_delete_image').'</a>
        <a class="featuredpicupload" rel="iframe modal:false" href="'.
          SD_INCLUDE_PATH.'thumbupload.php?action=featured&amp;new=1">'.
          AdminPhrase('featured_image_hint').'</a>
      </div>';


      // ############### POST TO FORUM ###############

      //SD360: new: post article as forum topic
      if( ($articleid>0) && (
           $this->IsSiteAdmin ||
          (!empty($this->settings['post_to_forum_usergroups']) &&
           !empty($userinfo['usergroupids']) &&
           @array_intersect($userinfo['usergroupids'],
                            $this->settings['post_to_forum_usergroups']))) )
      {
        $forum_id = GetPluginID('Forum');
        if(!empty($forum_id) &&
           (!empty($userinfo['pluginsubmitids']) && in_array($forum_id, $userinfo['pluginsubmitids'])))
        {
          echo '<h3 class="header blue lighter">' . AdminPhrase('post_to_forum') . '</h3>';

          echo '
          <div class="td3" id="options-forum-topic">
          <div class="td2">
            <label for="fp1"><input id="fp1" type="checkbox" class="ace" name="post_to_forum" value="1" /><span class="lbl"> <strong>'.AdminPhrase('post_to_forum').'</strong></span></label><br />
          </div>
          <div id="post_as_topic_options">
            <div class="td3">
              <label for="fp2"><input id="fp2" type="checkbox" class="ace" name="post_descr_to_post" value="1" /><span class="lbl"> '.AdminPhrase('post_descr_to_post').'</span></label><br />
              <label for="fp3"><input id="fp3" type="checkbox" class="ace" name="post_article_to_post" value="1" checked="checked" /><span class="lbl"> '.AdminPhrase('post_article_to_post').'</span></label><br />
              <label for="fp4"><input id="fp4" type="checkbox" class="ace" name="post_link_back_to_article" value="1" /><span class="lbl"> '.AdminPhrase('post_link_back_to_article').'</span></label><br />
              <label for="fp5"><input id="fp5" type="checkbox" class="ace" name="post_link_to_post" value="1" /><span class="lbl"> '.AdminPhrase('post_link_to_post').'</span></label><br />
            </div>
            <div class="td3">
              <strong>'.AdminPhrase('post_forum_to_post').'</strong><br />';

          require(ROOT_PATH.'plugins/forum/forum_config.php');
          $fc = new SDForumConfig();
          $fc->InitFrontpage(true);
          if(!empty($fc->forums_cache['forums']))
          {
            echo '
              <select name="post_to_forumid" style="width:98%">
                <option value="0" selected="selected">---</option>';
            foreach($fc->forums_cache['forums'] as $fid => $forum)
            {
              $perm = !empty($forum['access_post']) ? sd_ConvertStrToArray($forum['access_post'],'|'):array();
              $canSubmit = empty($forum['access_post']) ||
                           (!empty($userinfo['usergroupids']) &&
                            @array_intersect($userinfo['usergroupids'], $perm));

              if(empty($forum['is_category']))
              {
                echo '<option value="'.$fid.'">'.$forum['title']."</option>\r\n";
              }
            }
            echo '</select>
            <br />
            <label for="fp6"><input id="fp6" type="checkbox" class="ace" value="1" name="stick_topic" /><span class="lbl"> Sticky</span></label><br />
            <label for="fp7"><input id="fp7" type="checkbox" class="ace" value="1" name="lock_topic" /><span class="lbl"> Locked</span></label><br />
            <label for="fp8"><input id="fp8" type="checkbox" class="ace" value="1" name="moderate_topic" /><span class="lbl"> Moderated</span></label>';
          }
          unset($fc);

          echo '<br />'.AdminPhrase('post_article_hint').'
              </div>
              <!-- </td>
            </tr>
            </table> -->
            </div>
          </div>';

        }
      }
    }

    echo '
	</div>
    </form>

<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function() {
(function($){
'.
GetCeeboxDefaultJS(false,'a.thumbupload,a.featuredpicupload,a.cbox').'
';
  if($article['settings'] & self::$articlebitfield['useglobalsettings'])
  {
    echo '  DisableCheckboxes(1);';
  }
echo '
}(jQuery));
});
//]]>
</script>
';

  } //DisplayArticleForm


  // ##########################################################################
  // UPDATE ARTICLES
  // ##########################################################################

  function UpdateArticles()
  {
    global $DB, $SDCache, $userinfo, $sdlanguage;

    if(!CheckFormToken() || !$this->HasAdminRights)
    {
      RedirectPage($this->_link,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
      return;
    }

    // the following variables are arrays
    $articleids       = isset($_POST['articleids'])?(array)$_POST['articleids']:array();
    $displayorder     = GetVar('displayorder', null, 'array', true, false);
    $onlinestatus     = GetVar('onlinestatus', null, 'array', true, false);
    $categoryids      = GetVar('categoryids',  null, 'array', true, false);
    $deletearticleids = GetVar('deletearticleids', null, 'array', true, false);

    // first update the articles
    for($i = 0; $i < count($articleids); $i++)
    {
      $displayorder[$i]  = Is_Valid_Number(intval(trim($displayorder[$i])),0,0);
      if($articleids[$i] = Is_Valid_Number($articleids[$i],0,1))
      {
        //SD322: online status not changeable in author mode
        if(!$this->authormode)
        {
          // 2 = bitfield value for article settings onlinestatus
          //SD344 / v3.5.1: set "approved_by" if article gets set online, otherwise clear approved_by
          if(!empty($onlinestatus[$i]))
          {
            $DB->query('UPDATE '.$this->table_name.' SET settings = settings + 2,'.
                       " approved_by = '".$DB->escape_string($userinfo['username'])."', approved_date = ".TIMENOW.
                       ' WHERE articleid = %d AND NOT(settings & 2)', $articleids[$i]);
          }
          else
          {
            $DB->query('UPDATE '.$this->table_name.' SET settings = settings - 2'.
                       ", approved_by = '', approved_date = ".TIMENOW.
                       ' WHERE articleid = %d AND (settings & 2)', $articleids[$i]);
          }
        }

        //SD360: here only update displayorder; categoryid is now ajax'ed!
        /*
        $DB->query('UPDATE '.$this->table_name.' SET categoryid = %d, displayorder = %d WHERE articleid = %d',
                   $categoryids[$i], $displayorder[$i], $articleids[$i]);
        */
        $DB->query('UPDATE '.$this->table_name.' SET displayorder = %d WHERE articleid = %d',
                   $displayorder[$i], $articleids[$i]);
        // SD313: delete current article's cache file
        if(isset($SDCache) && $SDCache->IsActive())
        $SDCache->delete_cacheid(CACHE_ARTICLE.'-'.$articleids[$i].'-'.$this->pluginid);
      }
    } //for

    // SD313: delete Article SEO to ID's mapping cache file
    if(isset($SDCache) && $SDCache->IsActive())
    $SDCache->delete_cacheid(CACHE_ARTICLES_SEO2IDS.'-'.$this->pluginid);

    // lets confirm before deleting articles
    if(count($deletearticleids) > 0)
    {
      $this->DeleteArticles();
    }
    else
    {
      RedirectPage($this->_link, AdminPhrase('articles_updated'));
    }

  } //UpdateArticles


  // ##########################################################################
  // DELETE ARTICLE THUMB OR FEATURED PICTURE
  // ##########################################################################

  function DeleteArticleImage($articleid, $isThumb=true, $deleteFile=true) //SD343
  {
    global $DB, $SDCache, $sdlanguage, $userinfo;
    //SD360: - now returns appropiate error message as thumb can
    //         now be used for multiple articles within same plugin
    //       - added 2nd parameter

    $result = $sdlanguage['no_view_access'];
    if(empty($userinfo['loggedin']) || empty($articleid) ||
       !is_numeric($articleid) || ($articleid<1))
    {
      return $result;
    }
    $column = empty($isThumb)?'featuredpath':'thumbnail';

    $DB->result_type = MYSQL_ASSOC;
    $article_arr = $DB->query_first('SELECT articleid, thumbnail, featuredpath, org_author_id'.
                                    ' FROM '.$this->table_name.
                                    ' WHERE articleid = '.(int)$articleid);
    if(empty($article_arr['articleid']))
    {
      return $result;
    }
    if(empty($article_arr[$column]))
    {
      return true;
    }

    // If not allowed, check for author mode and if current user is author
    $allowDelete = $this->HasAdminRights;
    if(!$allowDelete)
    {
      // This allows the creator of the article to delete article when in author mode
      $allowDelete = ($this->authormode && !empty($article_arr['org_author_id']) &&
                      ($article_arr['org_author_id'] == $userinfo['userid']));
    }
    if(!$allowDelete) 
	{
		echo '<script>alert("here");</script>';
		return $result;
	}

    $DB->result_type = MYSQL_ASSOC;
    if(!empty($article_arr[$column]))
    {
      if(!empty($deleteFile))
      {
        //SD360: prevent deletion of image file if used by other articles
        $thumb_count = $DB->query_first('SELECT count(*) thumbcount'.
                                        ' FROM '.$this->table_name.
                                        " WHERE $column = '%s'".
                                        ' AND articleid <> '.(int)$articleid,
                                        $article_arr[$column]);
        if(empty($thumb_count['thumbcount']))
        {
          $file = @preg_replace('#[^a-zA-Z0-9_\-\.]+#m', '', $article_arr[$column]);
          if(($file != $article_arr[$column]) ||
             (strpos($file,'..')!==false))
          {
            return $result;
          }

          if(empty($isThumb))
          {
            $folder = ROOT_PATH.SD_ARTICLE_FEATUREDPICS_DIR;
            if(substr($file,0,9)!=='featured_') $file = 'featured_'.$file;
          }
          else
          {
            $folder = ROOT_PATH.SD_ARTICLE_THUMBS_DIR;
          }

          $thumbpath = $folder.$file;
          if(is_file($thumbpath) && file_exists($thumbpath))
          {
            @unlink($thumbpath);
          }
          if(substr($file,0,6)=='thumb_')
          {
            $thumbpath = $folder.'resize_'.substr($file,7);
            if(is_file($thumbpath) && file_exists($thumbpath))
            {
              @unlink($thumbpath);
            }
          }
        }
      }

      $DB->query('UPDATE '.$this->table_name." SET $column = ''".
                 ' WHERE articleid = '.(int)$articleid);

      // SD313: delete current article's cache file
      if(isset($SDCache) && $SDCache->IsActive())
      {
        $SDCache->delete_cacheid(CACHE_ARTICLE.'-'.$articleid.'-'.$this->pluginid);
      }
      return true;
    }
    return false;

  } //DeleteArticleImage


  // ##########################################################################
  // DELETE ARTICLES
  // ##########################################################################

  function DeleteArticles()
  {
    global $DB, $SDCache, $userinfo;

    if(!CheckFormToken() || !$this->HasAdminRights || $this->authormode)
    {
      RedirectPage($this->_link,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
      return;
    }

    $confirmdelete = GetVar('confirmdelete', '', 'string');

    if(!strlen($confirmdelete))
    {
      $deletearticleids = GetVar('deletearticleids', array(), 'array', true, false);

      // get the article titles
      for($i = 0, $deletearticletitles = $hiddenvalues = ''; $i < count($deletearticleids); $i++)
      {
        $DB->result_type = MYSQL_ASSOC;
        if(($deletearticleids[$i] > 0) &&
           ($article = $DB->query_first('SELECT author, title, org_author_id'.
                                        ' FROM '.$this->table_name.
                                        ' WHERE articleid = '.(int)$deletearticleids[$i])))
        {
          //SD322: if author mode is active, only allow deletion of own articles,
          // not even articles with "empty" author id (e.g. if created before 3.3)
          if( !$this->authormode ||
              (!empty($article['org_author_id']) && ($article['org_author_id'] = $userinfo['userid'])) )
          {
            $deletearticletitles .= ($i + 1) . ') ' . $article['title'] . '<br />';
            $hiddenvalues .= '<input type="hidden" name="deletearticleids[]" value="' . $deletearticleids[$i] . '" />';
          }
        }
      }

      if(!empty($hiddenvalues))
      {
        $description   = AdminPhrase('delete_the_following_articles') . '<br /><br />' . $deletearticletitles;
        $hiddenvalues .= '<input type="hidden" name="articleaction" value="deletearticles" />';
        // arguments: description, hidden input values, form redirect page
        ConfirmDelete($description, $hiddenvalues, $this->_link);
      }
      else
      {
        DisplayArticles();
      }
    }
    else if($confirmdelete == AdminPhrase('common_no'))
    {
      DisplayArticles();
    }
    else if($confirmdelete == AdminPhrase('common_yes'))
    {
      $deletearticleids = GetVar('deletearticleids', array(), 'a_int', true, false);

      for($i = 0, $dc = count($deletearticleids); $i < $dc; $i++)
      {
        if($deletearticleids[$i] = Is_Valid_Number($deletearticleids[$i],0,1))
        {
          // Delete article comments
          DeletePluginComments($this->pluginid, $deletearticleids[$i]);

          //SD343: Remove article ratings
          DeletePluginRatings($this->pluginid, 'p'.$this->pluginid.'-'.(int)$deletearticleids[$i]);

          // SD313: Delete current article's cache file
          if(isset($SDCache) && $SDCache->IsActive())
          {
            $SDCache->delete_cacheid(CACHE_ARTICLE.'-'.(int)$deletearticleids[$i].'-'.$this->pluginid);
          }

          //SD343: Delete article thumb
          $this->DeleteArticleImage($deletearticleids[$i],true,true);
          $this->DeleteArticleImage($deletearticleids[$i],false,true);

          // Delete article
          $DB->query('DELETE FROM '.$this->table_name.' WHERE articleid = '.(int)$deletearticleids[$i]);
        }
      } //for

      // SD313: delete Article SEO to ID's mapping cache file
      if(isset($SDCache) && $SDCache->IsActive())
      $SDCache->delete_cacheid(CACHE_ARTICLES_SEO2IDS.'-'.$this->pluginid);

      RedirectPage($this->_link, AdminPhrase('articles_deleted'));
    }

  } //DeleteArticles


  // ##########################################################################
  // DISPLAY ARTICLES
  // ##########################################################################

  function DisplayArticles($error='')
  {
    global $DB, $mainsettings_modrewrite, $mainsettings_url_extension,
           $pages_md_arr, $userinfo, $usersystem;

    if(!is_dir(SD_INCLUDE_PATH.'tmpl/comp'))
    {
      DisplayMessage(AdminPhrase('template_folder_not_found').' <strong>includes/tmpl/comp</strong>', true);
    }
    else
    if(!is_writable(SD_INCLUDE_PATH.'tmpl/comp'))
    {
      DisplayMessage(AdminPhrase('template_folder_not_writable').' <strong>includes/tmpl/comp</strong>', true);
    }

    if(!empty($error))
    {
      DisplayMessage($error, true);
    }

    // Fetch all usergroups
    $ugroups = array();
    $ugroups[0] = '---';
    if($getusergroups = $DB->query('SELECT usergroupid, name FROM {usergroups} ORDER BY usergroupid ASC'))
    {
      while($usergroup = $DB->fetch_array($getusergroups,null,MYSQL_ASSOC))
      {
        $ugroups[$usergroup['usergroupid']] = $usergroup['name'];
      }
    }
    unset($getusergroups);

    // Build usergroups lists for dialog and selection:
    $ug_select_list = '<select id="searchusergroup" name="searchusergroup" style="width: 140px;">';
    $ug_link_list   = '';
    $skip_first     = true;
    foreach($ugroups as $id => $name)
    {
      $skip_first = false;
      $ug_select_list .= '<option value="'.$id.'"'.($id==$this->search['usergroup']?' selected="selected"':'').'>'.$name.'</option>';
    }
    $ug_select_list .= '</select>';

    $items_per_page = $this->search['limit'];
    $page = GetVar('page', 1, 'whole_number');
    $limit = ' LIMIT '.(($page-1)*$items_per_page).','.$items_per_page;

    $articlequery = 'WHERE';

    // filter for title?
    if(strlen($this->search['title']))
    {
      $articlequery .= ($articlequery != 'WHERE'?' AND':'') . "
        ((title LIKE '%%" . $this->search['title'] . "%%') OR (article LIKE '%%" . $this->search['title'] . "%%'))";
    }

    // filter for article description? (SD322)
    if(strlen($this->search['description']))
    {
      $articlequery .= ($articlequery != 'WHERE'?' AND':'') . " description LIKE '%%" . $this->search['description'] . "%%'";
    }

    // filter for author?
    if(strlen($this->search['author']))
    {
      $articlequery .= ($articlequery != 'WHERE'?' AND':'') . " author LIKE '%%" . $this->search['author'] . "%%'";
    }

    //SD342: filter for usergroup?
    if(!empty($this->search['usergroup']))
    {
      $articlequery .= ($articlequery != 'WHERE'?' AND':'') . " (access_view like '%|".$this->search['usergroup']."|%')";
    }

    // filter for categoryid?
    if($this->search['categoryid'] != 0)
    {
      $articlequery .= ($articlequery != 'WHERE'?' AND':'') .
        ' ((categoryid = '.$this->search['categoryid'].') OR EXISTS(SELECT 1 FROM {p'.$this->pluginid.
        '_articles_pages} ap WHERE ap.categoryid = '.$this->search['categoryid'].
        ' AND ap.articleid = '.$this->table_name.'.articleid))';
    }

    // search by online, offline, both?
    if($this->search['onlineoffline'] != 'onlineoffline')
    {
      if($this->search['onlineoffline'] == 'online')
      {
        $articlequery .= ($articlequery != 'WHERE'?' AND':'') . ' (settings & ' . self::$articlebitfield['displayonline'] . ')';
      }
      else
      {
        $articlequery .= ($articlequery != 'WHERE'?' AND':'') . ' !(settings & ' . self::$articlebitfield['displayonline'] . ')';
      }
    }

    //SD362: filter for featured/thumbnail?
    if(!empty($this->search['isfeatured']) || !empty($this->search['hasthumb']))
    {
      $articlequery .= ($articlequery != 'WHERE'?' AND':' (');
      if(!empty($this->search['isfeatured']))
      {
        $articlequery .= 'featured = 1';
      }
      if(!empty($this->search['hasthumb']))
      {
        $articlequery .= (!empty($this->search['isfeatured']) ?' OR ':'') . "(IFNULL(thumbnail,'') > '')";
      }
      $articlequery .= ')';
    }

    //SD322: restrict access for non-admins if "author mode" is active
    //SD332: same restriction if user is NOT plugin-admin
    if(!$this->HasAdminRights && ($this->authormode || !$this->IsAdmin) )
    {
      $articlequery .= ($articlequery == 'WHERE' ? '' : ' AND ') .
                       ' ((org_system_id = '.$usersystem['usersystemid'].') AND (org_author_id = '.$userinfo['userid'];
      if(!empty($userinfo['authorname']))
      {
        $articlequery .= ") OR (author = '".$DB->escape_string($userinfo['authorname'])."'";
      }
      $articlequery .= "))";
    }

    $DB->result_type = MYSQL_ASSOC;
    $total_rows_arr = $DB->query_first('SELECT count(*) article_count'.
                                      ' FROM '.$this->table_name.
                                      ($articlequery == 'WHERE' ? '' : ' '.$articlequery));
    $total_rows = $total_rows_arr['article_count'];

    // ######################################################################
    // DISPLAY ARTICLES SEARCH BAR
    // ######################################################################

    echo '
    <form action="'.$this->_link.'" id="searcharticles" name="searcharticles" method="post">
    '.PrintSecureToken().'
    <input type="hidden" name="articleaction" value="displayarticles" />
    <input type="hidden" id="pluginid" name="pluginid" value="'.$this->pluginid.'" />
    ';

    StartTable(AdminPhrase('articles_filter_title'), array('table', 'table-bordered'));

    //SD362: added "Featured?", "Thumbnail?" filter options
    echo '
    <input type="hidden" name="customsearch" value="1" />
    <input type="hidden" name="clearsearch" value="0" />
	<thead>
    <tr>
      <th class="tdrow1" width="90">'  . AdminPhrase('title') . ' / '. AdminPhrase('article').'</th>
      <th class="tdrow1" width="90">'  . AdminPhrase('author') . '</th>
      <th class="tdrow1" width="150">' . AdminPhrase('usergroup') . '</th>
      <th class="tdrow1" width="200">' . AdminPhrase('page') . '</th>
      <th class="tdrow1" width="80">'  . AdminPhrase('status') . '</th>
      <th class="tdrow1" width="60" align="center">'  . AdminPhrase('featured_thumb') . '</th>
      <th class="tdrow1" width="160">' . AdminPhrase('sort_by') . '</th>
      <th class="tdrow1" width="40">'  . AdminPhrase('limit') . '</th>
      <th class="tdrow1" width="50">'  . AdminPhrase('articles_filter') . '</th>
    </tr>
	</thead>
	<tbody>
    <tr>
      <td class="tdrow2" width="90"><input type="text" name="searchtitle"  style="width: 85%;" value="' . $this->search['title'] . '" /></td>
      <td class="tdrow2" width="90"><input type="text" name="searchauthor" style="width: 85%;" value="' . $this->search['author'] . '" /></td>
      <td class="tdrow2" width="150">';

    echo $ug_select_list;

    echo '
      </td>
      <td class="tdrow2" width="200">';

    //SD360: cache pages selection, generated only once
    #DisplayCategorySelection($this->search['categoryid'], 1, 0, '', 'searchcategoryid', 'width: 190px; font-size: 12px;', true);
    $page_selector = '';
    ob_start();
    DisplayArticleCategories($this->pluginid, 0, 1, 0, '',
                             'categoryids', 'font-size: 12px; min-width: 100px; max-width: 350px;',
                             true, $this->_limitpages);
    $page_selector = ob_get_clean();

    // Set selected page for filter bar:
    $tmp = str_replace(array('categoryids',' selected="selected"'),
                             array('searchcategoryid', ''),
                             $page_selector);
    if(!empty($this->search['categoryid']))
    {
      $tmp = str_replace('<option value="'.$this->search['categoryid'].'"',
                         '<option value="'.$this->search['categoryid'].'" selected="selected"',
                         $tmp);
    }
    echo $tmp;
    unset($tmp);

    echo '
      </td>
      <td class="tdrow2" width="80">
        <select name="searchonlineoffline" style="width: 95%; font-size: 12px;">
          <option value="onlineoffline" ' . ($this->search['onlineoffline'] == 'onlineoffline'?'selected="selected"':'') .'>'.AdminPhrase('articles_onlineoffline').'</option>
          <option value="online" ' . ($this->search['onlineoffline'] == 'online'?'selected="selected"':'') .'>'.AdminPhrase('articles_online').'</option>
          <option value="offline" ' . ($this->search['onlineoffline'] == 'offline'?'selected="selected"':'') .'>'.AdminPhrase('articles_offline').'</option>
        </select>
      </td>
      <td class="tdrow2" width="60" align="center">
        <input type="checkbox" class="ace" name="isfeatured" title="'.htmlspecialchars(AdminPhrase('hint_featured')).'" value="1"' . (empty($this->search['isfeatured']) ? '' : ' checked="checked"').' />
        <input type="checkbox" class="ace" name="hasthumb" title="'.htmlspecialchars(AdminPhrase('hint_thumbnail')).'" value="1"' . (empty($this->search['hasthumb']) ? '' : ' checked="checked"').' />
      </td>
      <td class="tdrow2" width="160">
        <select name="searchsorting" style="width: 155px;">
          <option value="newest_first" ' . ($this->search['sorting'] == 'newest_first'?'selected="selected"':'') .'>'.AdminPhrase('articles_date_desc').'</option>
          <option value="oldest_first" ' . ($this->search['sorting'] == 'oldest_first'?'selected="selected"':'') .'>'.AdminPhrase('articles_date_asc').'</option>
          <option value="displayorder ASC" ' . ($this->search['sorting'] == 'displayorder ASC'?'selected="selected"':'') .'>'.AdminPhrase('display_order_asc').'</option>
          <option value="displayorder DESC" ' . ($this->search['sorting'] == 'displayorder DESC'?'selected="selected"':'') .'>'.AdminPhrase('display_order_desc').'</option>
          <option value="title ASC" ' . ($this->search['sorting'] == 'title ASC'?'selected="selected"':'') .'>'.AdminPhrase('order_title_asc').'</option>
          <option value="title DESC" ' . ($this->search['sorting'] == 'title DESC'?'selected="selected"':'') .'>'.AdminPhrase('order_title_desc').'</option>
          <option value="author ASC" ' . ($this->search['sorting'] == 'author ASC'?'selected="selected"':'') .'>'.AdminPhrase('order_author_asc').'</option>
          <option value="author DESC" ' . ($this->search['sorting'] == 'author DESC'?'selected="selected"':'') .'>'.AdminPhrase('order_author_desc').'</option>
        </select>
      </td>
      <td class="tdrow2" width="40">
        <input type="text" name="searchlimit" style="width: 35px;" value="' . $items_per_page . '" size="2" />
      </td>
      <td class="center align-middle" width="50">
        <input type="submit" value="'.AdminPhrase('search').'" style="display:none" />
        <a  id="articles-submit-search" href="#" onclick="return false;" title="'.AdminPhrase('articles_apply_filter').'" ><i class="ace-icon fa fa-search blue bigger-120"></i></a>&nbsp;
		 <a  id="articles-clear-search" href="#" onclick="return false;" title="'.AdminPhrase('articles_clear_filter').'" ><i class="ace-icon fa fa-trash-o red bigger-120"></i></a>
      </td>
    </tr>
	</tbody>
    </table>
	</div>
     </form>';

    if(!$total_rows && !$this->customsearch)
    {
      DisplayMessage(AdminPhrase('no_articles_exist'));
      return;
    }

    // Translate sorting
    if($this->search['sorting']=='newest_first')
    {
      $this->search['sorting'] = 'IF(IFNULL(datecreated,IFNULL(datestart,0))=0,dateupdated,IFNULL(datecreated,IFNULL(datestart,0))) DESC';
    }
    else
    if($this->search['sorting']=='oldest_first')
    {
      $this->search['sorting'] = 'IF(IFNULL(datecreated,IFNULL(datestart,0))=0,dateupdated,IFNULL(datecreated,IFNULL(datestart,0))) ASC';
    }
    $DB->ignore_error = true;
    $get_articles = $DB->query('SELECT articleid, categoryid, settings, views, displayorder,'.
                               ' datecreated, datestart, dateend, author, title, seo_title,'.
                               ' featured, thumbnail'.
                               ' FROM '.$this->table_name.
                               ($articlequery == 'WHERE' ? '' : ' '.$articlequery).
                               ' ORDER BY ' . $this->search['sorting'] . $limit);
    if($DB->errno) echo $DB->errdesc;
    if(!$articles_count = $DB->get_num_rows($get_articles))
    {
      DisplayMessage(AdminPhrase('no_articles_found'));
      return;
    }

    // ######################################################################
    // DISPLAY ARTICLES LIST
    // ######################################################################

    echo '
    <form id="articles" name="articles" action="'.$this->_link.'" method="post">
    <input type="hidden" name="articleaction" value="updatearticles" />
    '.PrintSecureToken();

    StartTable(AdminPhrase('articles').' ('.$total_rows.')', array('table','table-bordered','table-striped'));

    echo '
	<thead>
    <tr>
      <th class="td1">' . AdminPhrase('title') . '</th>
      <th class="td1">' . AdminPhrase('author') . '</th>';
    if(!$this->authormode)
    {
      echo '
      <th class="td1" align="center" width="70">' . AdminPhrase('article_permissions') . '</th>';
    }
    echo '
      <th class="td1">' . AdminPhrase('publish_date') . '</th>
      <th class="center" width="5%" align="center">' . AdminPhrase('featured_thumb') . '</th>
      <th class="td1" width="220">' . AdminPhrase('page') . '</th>
      <th class="td1" width="18"> </th>
      ';

    if($this->HasAdminRights)
    {
      //SD370: added check all/none functionality
      echo '
      <th class="td1">'.AdminPhrase('status').'</th>
      <th class="td1" width="80" align="center">'.AdminPhrase('display_order').'</th>
      <th class="center align-middle" width="30" align="center">
        <a id="checkall" rel="0" title="'.htmlspecialchars(AdminPhrase('common_delete'),ENT_COMPAT).
        '" href="#" onclick="javascript:return false;"><i class="ace-icon fa fa-trash-o red bigger-120"></i></a></th>';
    }
    echo '
    </tr>
	</thead>
	<tbody>';

    // ########################################################################
    // MAIN LOOP FOR ARTICLES
    // ########################################################################
    while($article = $DB->fetch_array($get_articles,null,MYSQL_ASSOC))
    {
      $articleid = (int)$article['articleid'];
      echo '<tr>
        <td class="td2" style="white-space:nowrap;overflow:hidden;max-width:200px;">
          <input type="hidden" class="hidden_cat_id" name="categoryids[]" value="'.$article['categoryid'].'" />
          <input type="hidden" name="articleids[]" value="'.$articleid.'" />
          <a href="'.$this->_link.'&amp;articleaction=displayarticleform&amp;articleid='.
            $articleid.'&amp;load_wysiwyg=1"><i class="ace-icon fa fa-edit blud bigger-120"></i>&nbsp;' . $article['title'] . '</a>'.
            (empty($article['seo_title'])?'':'<br /><span class="smaller-80">SEO: '.$article['seo_title'].'</span>').'
        </td>
        <td class="td2"><i class="ace-icon fa fa-user blue bigger-120"></i> ' . $article['author'] . '</td>';

      // Permissions not editable in author mode
      if(!$this->authormode)
      {
        echo '
        <td class="center" width="70">'.
          '<a class="cbox permissionslink" title="'.AdminPhrase('article_permissions').
          '" href="'.$this->_link.'&amp;articleid='.$articleid.
          '&amp;action=display_article_permissions&amp;load_wysiwyg=0"><i class="ace-icon fa fa-key orange bigger-120"></i></a></td>';
      }

      $online = (($article['settings'] & (self::$articlebitfield['displayonline'])) > 0);
      echo '
        <td class="td2"><i class="ace-icon fa fa-calendar blue bigger-110"></i>&nbsp; ' .
        ($article['datestart'] != 0 ? DisplayDate($article['datestart']) : DisplayDate($article['datecreated']));
      if($this->authormode)
      {
        echo ' ('.AdminPhrase($online ? 'online' : 'offline').')';
      }
      echo '
      </td>
      ';

      //SD362: display if featured and/or thumbnailed
      echo '
        <td class="center">';
      if(!empty($article['featured']))
      {
        echo AdminPhrase('featured_shortcut');
      }
      if(!empty($article['thumbnail']))
      {
        echo (empty($article['featured'])?'':' | ').AdminPhrase('thumbnail_shortcut');
      }
      echo '
      </td>
      ';

      //SD360: display link for page-selection popup
      echo '
      <td class="td3 parentselector_top">
        <a class="parentselector btn btn-info btn-block" rel="'.$articleid.'" href="#">
        <span>'.
        (empty($article['categoryid']) ? '---' : $pages_md_arr[$article['categoryid']]['name']).
        '</span></a>
      </td>
      <td class="td2">';

      //SD342: display icon-link to target page where article resides
      //SD360: only display link if primary page is set; use "GetArticleLink()"
      if(!empty($article['categoryid']))
      {
        $articlelink = GetArticleLink($article['categoryid'],$this->pluginid,$article,self::$articlebitfield,false);
        echo '<a class="" href="'.$articlelink.'" target="_blank"><i class="ace-icon fa fa-search blue bigger-120"></i> </a>';
      }
      echo '
        </td>';

      // Online status not changeable with author mode
      if($this->HasAdminRights)
      {
        echo '
        <td class="td2" width="60">
          <div class="status_switch" style="margin:0 auto; width: 94%;">
            <input type="hidden" name="onlinestatus[]" value="'.($online ? 1 : 0).'" />
            <a onclick="javascript:return false;" class="status_link on btn btn-success btn-sm"  style="display: '.( $online ? 'block': 'none').'">'.AdminPhrase('online').'</a>
            <a onclick="javascript:return false;" class="status_link off btn btn-danger btn-sm" style="display: '.(!$online ? 'block': 'none').'">'.AdminPhrase('offline').'</a>
          </div>
        </td>
        <td class="td3" align="center"><input type="text" name="displayorder[]" value="'.$article['displayorder'].'" size="4" /></td>
        <td class="td3" align="center"><input class="delarticle ace" type="checkbox"  name="deletearticleids[]" value="'.$articleid.'" /><span class="lbl"></span></td>';
      }
      echo '
      </tr>';
    } //while

    echo '
    </td></tr>
	</tbody>
    </table></div>';
 

    PrintSubmit('updatearticles',AdminPhrase('update_articles'),'articles','fa-check');

    echo '
    </form>';

    // pagination
    if($total_rows > $items_per_page)
    {
      $p = new pagination;
      $p->items($total_rows);
      $p->limit($items_per_page);
      $p->currentPage($page);
      $p->adjacents(8);
      $p->target($this->_link.'&amp;articleaction=displayarticles');
      $p->show();
    }

    $pages_phrases = LoadAdminPhrases(1);
?>
<div id="parentselector_div" style="display: none;">
<div class="table-header"><?=AdminPhrase('page');?></div>
<table class="table">
	<tbody>
		<tr>
			<td>
  				<?php echo $page_selector; ?>
			</td>
		</tr>
		<tr>
			<td class="align-right">
  				<a class="articlepagechangelink btn btn-success btn-sm" href="<?php echo $_SERVER['PHP_SELF']; ?>"><i class="ace-icon fa fa-check"></i>&nbsp;
  				<?php echo $pages_phrases['pages_update_page']; ?></a>
  			</td>
		</tr>
	</tbody>
</table>
</div>

<script type="text/javascript">
//<![CDATA[
function sd_DialogClose() {
  if(jDialog) jDialog.close();
}
jQuery(document).ready(function() {
(function($){
  $("<link>").appendTo("head").attr({rel: "stylesheet", type: "text/css", href: sdurl+"<?php echo ADMIN_STYLES_FOLDER; ?>assets/css/jdialog.css" });

  var sel_categoryid = 0, sel_articleid = 0, sel_target = false,
      sel_catlink = false, last_selected_input = false;
  var users_token = '<?php echo PrintSecureUrlToken(); ?>';
  var sd_timeout = 1500, sd_timerID = false;
  var articles_url = "<?php echo $_SERVER['PHP_SELF'].'?pluginid='.$this->pluginid; ?>";

  $(document).delegate(".dialog_content select","change",(function(e){
    sel_categoryid = $(this).val();
  }));

  /* Display popup upon click in cell/on article page */
  $(document).delegate("a.parentselector,td.parentselector_top:has(a)","click",function(e){
    e.preventDefault();
    jDialog.close();
    sel_catlink = $(this).parent();
    last_selected_input = sel_catlink.parent().find("input.hidden_cat_id");
    if(last_selected_input.length = 0) return false;
    sel_categoryid = parseInt(last_selected_input.val(),10);
    sel_articleid = $(this).attr("rel");
    if(typeof(sel_articleid) == "undefined") return false;
    sel_articleid = parseInt(sel_articleid,10);
    if(sel_articleid < 1) return false;
    $(this).jDialog({
      align : "left",
      content : $("div#parentselector_div").clone().html(),
      close_on_body_click : true,
      idName : "parentselector_dialog",
      lbl_close: '',
      title: '',
      title_visible : false,
      top_offset : -32,
      width : 335
    });
    $("div#parentselector_dialog select#categoryids").val(sel_categoryid);
    new_href = articles_url+"&amp;articleid="+sel_articleid+"&amp;action=setarticlepage"+users_token;
    $("div#parentselector_dialog a.articlepagechangelink").attr("href", new_href);
    return false;
  });

  /* Click to submit selected page back to article */
  $(document).delegate("a.articlepagechangelink","click",(function(e){
    e.preventDefault();
    $(this).attr("disabled","disabled");
    sel_target = $(this).closest("div");
    if(sel_categoryid >= 0 && sel_articleid >= 0) {
      new_href = this.href+"&amp;categoryid="+sel_categoryid;
      $(this).attr("href", new_href);
      $(sel_target).load(new_href, {}, function(response, status, xhr){
        if(sel_catlink !== false) {
          $(sel_catlink).load(articles_url+"&amp;action=getarticlepage&amp;articleid="+sel_articleid+users_token,
            {}, function(response, status, xhr){ last_selected_input.val(sel_categoryid); });
        }
        sel_catlink = false;
        sd_timerID = setTimeout("sd_DialogClose();", sd_timeout);
      });
    };
    return false;
  }));

}(jQuery));
});
//]]>
</script>
<?php
  } //DisplayArticles


  // ##########################################################################
  // DISPLAY ARTICLE SEARCH FORM
  // ##########################################################################

  function DisplayArticleSearchForm()
  {
    echo '
    <form id="searcharticles" class="form-horizontal" name="searcharticles" method="post" action="'.$this->_link.'">
    <input type="hidden" name="pluginid" value="'.$this->pluginid.'" />
    <input type="hidden" name="articleaction" value="displayarticles" />
    '.PrintSecureToken();

    echo '<h3 class="header blue lighter">' . AdminPhrase('search_articles') . '</h3>';
    echo '
    <input type="hidden" name="customsearch" value="true" />
	<div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('page') . '</label>
		<div class="col-sm-6">';
    		// Default Function Values:
    		// $categoryid = 0, $showzerovalue = 0, $parentid = 0, $sublevelmarker = '', $selectname = 'parentid'
    		DisplayCategorySelection(0, 1, 0, '', 'searchcategoryid', '', true);
		echo '</div>
	</div>
	<div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('title') . ' / ' . AdminPhrase('article') . '</label>
		<div class="col-sm-6">
			<input type="text" name="searchtitle" class="form-control"  value="" />
		</div>
	</div>
    <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('article_description') . '</label>
		<div class="col-sm-6">
			<input type="text" name="searchdescription" class="form-control"  value="" />
		</div>
    </div>
    <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('author') . '</label>
		<div class="col-sm-6">
			<input type="text" name="searchauthor" class="form-control" value=""" />
		</div>
    </div>
     <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('status') . '</label>
		<div class="col-sm-6">
        <select name="searchonlineoffline" class="form-control">
          <option value="onlineoffline" >' . AdminPhrase('online') . '/' . AdminPhrase('offline') . '</option>
          <option value="online">' . AdminPhrase('online') . '</option>
          <option value="offline">' . AdminPhrase('offline') . '</option>
        </select>
      </div>
    </div>
    <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('sort_by') . '</label>
		<div class="col-sm-6">
        <select name="searchsorting" class="form-control">
          <option value="newest_first">' . AdminPhrase('newest_first') . '</option>
          <option value="oldest_first">' . AdminPhrase('oldest_first') . '</option>
          <option value="displayorder ASC">' . AdminPhrase('display_order_asc') . '</option>
          <option value="displayorder DESC">' . AdminPhrase('display_order_desc') . '</option>
          <option value="title ASC">' . AdminPhrase('article_title_az') . '</option>
          <option value="title DESC">' . AdminPhrase('article_title_za') . '</option>
          <option value="author ASC">' . AdminPhrase('author_name_az') . '</option>
          <option value="author DESC">' . AdminPhrase('author_name_za') . '</option>
        </select>
      </div>
    </div>';

    echo '<div class="center">
			<button type="submit" value="" class="input_submit btn btn-info" ><i class="ace-icon fa fa-check"></i> ' . AdminPhrase('search') . '</button>
		</div>
          </form>';

  } //DisplayArticleSearchForm

} // end of class
} // DO NOT REMOVE!
