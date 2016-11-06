<?php
// DEFINE CONSTANTS
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');
define('SELF_USERS_PHP', 'users.php');

// INIT PRGM
@require(ROOT_PATH . 'includes/init.php');
require_once(SD_INCLUDE_PATH.'enablegzip.php');

// This can be overriden in "branding.php":
defined('EMAIL_BATCH_SIZE') || define('EMAIL_BATCH_SIZE', 30);
defined('EMAIL_NOSEND') || define('EMAIL_NOSEND', false); //SD370: dev option!

// LOAD ADMIN LANGUAGE
$admin_phrases = LoadAdminPhrases(5);

// GET ACTION
$action = GetVar('action', 'display_users', 'string');
$cbox   = GetVar('cbox', 0, 'bool');
$isajax = Is_Ajax_Request() || $cbox;
$script = '';
$js = array();

//SD360: Load Wysiwyg only for initial email form
$load_wysiwyg = 0;
if(empty($_POST) && ($action=='display_email_users_form'))
{
  $load_wysiwyg = 1;
}

if($isajax)
{
  @header('Content-type:text/html; charset=' . SD_CHARSET);
}
else
{
  $sd_other[] = '
<script type="text/javascript">
// <![CDATA[
var users_lang = {
  users_change_userstatus_title: "'.htmlspecialchars(AdminPhrase('users_change_userstatus_title'),ENT_COMPAT).'",
  users_change_usergroup_title: "'.htmlspecialchars(AdminPhrase('users_change_usergroup_title'),ENT_COMPAT).'",
  users_confirm_activationlink: "'.htmlspecialchars(AdminPhrase('users_confirm_activationlink'),ENT_COMPAT).'",
  users_link_edit_usergroup: "'.htmlspecialchars(AdminPhrase('users_link_edit_usergroup'),ENT_COMPAT).'",
  users_link_send_email_user: "'.htmlspecialchars(AdminPhrase('users_link_send_email_user'),ENT_COMPAT).'",
  users_token_name: "'.htmlspecialchars(SD_TOKEN_NAME,ENT_COMPAT).'",
  users_token: "&'.htmlspecialchars(SD_TOKEN_NAME,ENT_COMPAT).'='.SD_FORM_TOKEN.'"
}
// ]]>
</script>
';
}

// Load Users Stylesheet - always
sd_header_add(array(
  'js'	=>	array('styles/'.ADMIN_STYLE_FOLDER_NAME.'/assets/js/jquery-ui.min.js'),
  
  'css' => array('styles/'.ADMIN_STYLE_FOLDER_NAME.'/assets/css/jquery-ui.min.css',
				 SITE_URL . ADMIN_STYLES_FOLDER.'assets/css/chosen.css'),
  'other' => $sd_other),true);


if($action == 'send_user_emails')
{
  sd_header_output();
}


$usersystemerror = false;
if(!$isajax && ($action == 'display_users') || ($action == 'update_users') || $action == 'display_user_form' || $action == 'display_email_users_form')
{
	
// Add everything to header now:
  sd_header_add(array('js' => array('javascript/page_users.js',)),true);
  // Add everything to header now:


  $UserProfile->form_token = SD_TOKEN_NAME;

  if(!strlen($action) || in_array($action, array('display_users','display_user_form')))
  {
    if($usersystem['name'] != 'Subdreamer')
    {
      $usersystemerror = AdminPhrase('forum_integration_msg_users1');
    }
  }
} //!isajax

$sd_js_header = '';
if(!$isajax)
{
  if(in_array($action, array('display_user_form','insert_user','update_user')))
  {
    $sd_js_header .= '
<script type="text/javascript">
// <![CDATA[
if(typeof(jQuery) !== "undefined"){
  jQuery(document).ready(function(){
    (function($){
		
	
    $("#joindate").datepicker();
	$("#lastactivity").datepicker();
	
	$("#avatar_upload").ace_file_input();
	$("#picture_upload").ace_file_input();
	
	 $("[data-rel=popover]").popover({container:"body"});
	 $("[data-rel=tooltip]").tooltip();

		
		
      $("a.sendactivationlink").click(function(e){
        return (true === confirm(users_lang.users_confirm_activationlink));
      });
      $("div.status_switch a").click(function(e){
        e.preventDefault();
        var elm = $(this).parent("div");
        var inp = elm.find("input:first");
        inp.val(1 - parseInt(inp.val(),10));
        elm.find("a").each(function(){ $(this).toggle(); });
      });
	  
	  $("#usergroup_others").chosen();

      if ($.fn.ceebox != "undefined") {
        $("a.smallcbox").each(function(event) {
          $(this).attr("rel", "iframe modal:false height:180 width:400");
        });
        $("a.largecbox").each(function(event) {
          $(this).attr("rel", "iframe modal:false height:660 width:1000");
        });
        '.
        GetCeeboxDefaultJS(false, 'a.ceebox').'
      }
    }(jQuery));
  });
}
// ]]>
</script>
';
  }
  if(strlen($sd_js_header))
	{
   		sd_header_add(array('other' => array($sd_js_header)));
	}

unset($sd_js_header);

}



if(!$cbox)
{
  $allowed_limits = array(10,20,30,50,100,200,500,1000);

  $searchbar_config_arr = array(
    'allow_post' => true,
    'allow_get'  => true,
    'clear_val'  => '---', // if field has this value, reset it to default
    'use_cookie' => false, // store values in a browser cookie; only cookie OR db allowed
    'use_db'     => 'users'.$userinfo['userid'],  // store values in a (hidden) row in "mainsettings" table
    'fields' => array(
      'username'    => array('type' => 'string'),
      'namestart'   => array('type' => 'string', 'default' => '', 'maxlen' => 6, 'hidden' => true),
      'usergroupid' => array('type' => 'natural_number', 'default' => 0, 'min' => 0, 'max' => 9999999),
      'email'       => array('type' => 'string'),
      'status'      => array('type' => 'string', 'default' => '---'),
      'admin_notes' => array('type' => 'string'),
      'limit'       => array('type' => 'whole_number', 'default' => 10, 'min' => 5, 'max' => 1000, 'allowed' => $allowed_limits),
      'sortby'      => array('type' => 'string', 'default' => 'joindate', 'allowed' => array('joindate','lastactivity','username','email','register_ip')),
      'sortorder'   => array('type' => 'string', 'default' => 'desc', 'allowed' => array('asc','desc')),
      'page'        => array('type' => 'whole_number', 'default' => 1, 'min' => 1, 'max' => 99999999),
    ));
  $search = SearchBarInit('_users_search', $searchbar_config_arr);
}

// CHECK PAGE ACCESS
CheckAdminAccess('users');

if($isajax)
{
  if($action == 'email_activation_link')
  {
    $mainsettings['gzipcompress'] = false;
    
    EmailActivationLink();

  }
  else
  if($action == 'send_email_welcome') //SD343
  {
    $mainsettings['gzipcompress'] = false;
    
	EmailWelcomeMessage();

  }
  else
  if($action == 'send_password_reset')
  {
    DisplayAdminHeader('Users', null, AdminPhrase('menu_users'), true);
    DisplayEmailUsersForm();
  }
  else
  if($action == 'send_user_emails')
  {
    SendUserEmails();
  }
  else
  if($action=='getuserlist')
  {
    DisplayUsers();
  }
  else
  if($action=='setuserstatus')
  {
    ChangeUserStatus();
  }
  else
  if($action=='setusergroup')
  {
    ChangeUsersGroup();
  }
  else
  {
    echo 'ERROR: '.$action;
  }
  exit();
}


// DISPLAY ADMIN HEADER
if(in_array($action, array('display_user_form','insert_user','update_user')))
{
  require_once(ROOT_PATH.'plugins/p11_mi_usercp/header.php');
}
DisplayAdminHeader(array('Users', $action), null, AdminPhrase('menu_users'));
if($usersystemerror !== false)
{
  DisplayMessage($usersystemerror, true);
}


// ###########################################################################
// DISPLAY EMAIL USERS FORM
// ###########################################################################

function DisplayEmailUsersForm($errors_arr = array(),$reload=false)
{
  global $DB, $mainsettings, $sdlanguage, $userinfo;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_USERS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $msg = '';
  $total_emails_failed = GetVar('total_emails_failed', null, 'whole_number');
  if(isset($total_emails_failed))
  {
    $msg .= $total_emails_failed . ' ' . AdminPhrase('users_email_emails_not_sent').'<br />';
  }

  $total_emails_sent = GetVar('total_emails_sent', null, 'whole_number');
  if(isset($total_emails_sent))
  {
    $msg .= $total_emails_sent . ' ' . AdminPhrase('users_email_emails_sent').'<br />';
  }

  if($msg)
  {
    DisplayMessage($msg);
    $email_arr = array('email_to_addresses'       => GetVar('email_to_addresses', '', 'string'),
                       'email_user_group_ids_arr' => GetVar('email_user_group_ids_arr', array(), 'array'),
                       'email_from_name'          => GetVar('email_from_name', $userinfo['username'], 'string'),
                       'email_from_address'       => GetVar('email_from_address', TECHNICAL_EMAIL, 'string'),
                       'email_content_type'       => GetVar('email_content_type', 'text/plain', 'string'),
                       'email_subject'            => GetVar('email_subject', '', 'html'),
                       'email_message'            => GetVar('email_message', '', 'html'),
                       'email_empty_password'     => GetVar('email_empty_password', false, 'bool'), //SD344
                       'email_validating_only'    => GetVar('email_validating_only', false, 'bool'), //SD360
                       );
  }

  $single_user = false;
  $userid = GetVar('email_userid', 0, 'whole_number');
  if($userid)
  {
    if($user_arr = $DB->query_first('SELECT * FROM {users} u WHERE userid = %d',$userid))
    {
      $single_user = true;
    }
  }

  if($single_user)
  {
    $email_arr['email_to_addresses'] = $user_arr['email'];
    $email_arr['email_from_address'] = TECHNICAL_EMAIL;
    $email_arr['email_from_name']    = $userinfo['username'];
    $email_arr['email_empty_password'] = false;
    $email_arr['email_validating_only'] = false;
    if($pwdreset = GetVar('pwdreset', 0, 'whole_number'))
    {
      $p12_language = GetLanguage(12);
      $p12_settings = GetPluginSettings(12);
      // generate new password
      $min_pwd_length = $p12_settings['min_password_length'];
      $min_pwd_length = (empty($min_pwd_length) || $min_pwd_length < 6) ? 6 : (int)$min_pwd_length;
      $newpwd         = sd_GeneratePassword($min_pwd_length);
      if($pwdreset = !empty($newpwd))
      {
        echo '
        <input type="hidden" name="email_userid" value="'.$userid.'" />';
        $email_arr['email_userid']  = $userid;
        $email_arr['email_newpass'] = $newpwd;
        $email_arr['email_subject'] = $p12_language['email_subject'];
        $email_arr['email_content_type'] = 'text/plain';
        $email_arr['email_message'] = trim($user_arr['username'] . ","  . EMAIL_CRLF.EMAIL_CRLF.
                                      $p12_language['email_message']  . EMAIL_CRLF.
                                      $email_arr['email_newpass']);
      }
    }
  }
  else
  {
    $get_user_groups = $DB->query('SELECT usergroupid, name FROM {usergroups}'.
                                  ' WHERE usergroupid != ' . GUESTS_UGID .
                                  ' AND banned = 0'.
                                  ' ORDER BY usergroupid ASC');
    if($reload || (isset($errors_arr) && is_array($errors_arr) && count($errors_arr)))
    {
      if(!$reload)
      DisplayMessage($errors_arr, true);

      $email_arr = array('email_to_addresses'       => GetVar('email_to_addresses', '', 'string'),
                         'email_user_group_ids_arr' => GetVar('email_user_group_ids_arr', array(), 'array'),
                         'email_from_name'          => GetVar('email_from_name', $userinfo['username'], 'string'),
                         'email_from_address'       => GetVar('email_from_address', $userinfo['email'], 'string'),
                         'email_content_type'       => GetVar('email_content_type', 'text/plain', 'string'),
                         'email_subject'            => GetVar('email_subject', '', 'html'),
                         'email_message'            => GetVar('email_message', '', 'html'),
                         'email_empty_password'     => GetVar('email_empty_password', false, 'bool'), //SD344
                         'email_validating_only'    => GetVar('email_validating_only', false, 'bool'), //SD360
                         );
    }
    else
    {
      $email_arr = array('email_to_addresses' => '',
                         'email_user_group_ids_arr' => array(),
                         'email_from_name'    => $userinfo['username'],
                         'email_from_address' => $userinfo['email'],
                         'email_content_type' => 'text/plain',
                         'email_subject'      => '',
                         'email_message'      => '',
                         'email_empty_password' => false, //SD344
                         'email_validating_only' => false, //SD360
                         );
    }
  }

  echo '
  <form method="post" action="'.SELF_USERS_PHP.'?action=setupemails" class="form-horizontal">
  '.PrintSecureToken().'
  ';
  //SD332: reset password marking for site admin
  if(!empty($pwdreset))
  {
    echo '
    <input type="hidden" name="email_userid" value="' . $email_arr['email_userid'] . '" />
    <input type="hidden" name="email_newpass" value="' . htmlspecialchars($email_arr['email_newpass'],ENT_COMPAT) . '" />';
  }

  echo '<h3 class="header blue lighter">' . AdminPhrase('users_email_to_and_from') . '</h3>
 		<div class="form-group">
			<label class="control-label col-sm-2" for="email_from_address">' . AdminPhrase('users_email_from_address') . '</label>
			<div class="col-sm-6">
				<input type="text" name="email_from_address" value="' . $email_arr['email_from_address'] . '" class="form-control">
			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-sm-2" for="email_from_name">' . AdminPhrase('users_email_from_name') . '</label>
			<div class="col-sm-6">
				<input type="text" name="email_from_name" value="' . $email_arr['email_from_name'] . '" class="form-control" />
			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-sm-2" for="email_from_name">' . AdminPhrase('users_email_to_address') . '</label>
			<div class="col-sm-6">';
  if(!empty($pwdreset))
  {
    echo '
	<input type="text" class="form-control" value="'.$user_arr['username'].' ('.$email_arr['email_to_addresses'].')" disabled />
    <input type="hidden" name="email_to_addresses" value="' . $email_arr['email_to_addresses'] . '" />
    ';
  }
  else
  {
    echo '<input type="text" name="email_to_addresses" value="' . $email_arr['email_to_addresses'] . '" class="form-control" />';
  }
  echo '</div>
  </div>';

  if(!$single_user)
  {
    echo '
	<div class="form-group">
			<label class="control-label col-sm-2" for="email_user_group_ids_arr">' . AdminPhrase('users_email_send_to_user_groups') . '</label>
			<div class="col-sm-6">
    <select name="email_user_group_ids_arr[]" size="5" multiple="multiple" class="form-control">';

    while($user_group_arr = $DB->fetch_array($get_user_groups,null,MYSQL_ASSOC))
    {
      echo '
      <option value="' . $user_group_arr['usergroupid'] . '" ' .
      (@in_array($user_group_arr['usergroupid'], $email_arr['email_user_group_ids_arr']) ? 'selected="selected"' : '') . '>' .
      $user_group_arr['name'] . '</option>';
    }

    //SD344: added option to only email users with an empty password
    echo '
    </select>
    <br />
    <input type="checkbox" class="ace" name="email_empty_password" value="1" '.
    (empty($email_arr['email_empty_password'])?'':'checked="checked" ').'/><span class="lbl"> '.AdminPhrase('email_empty_password').'</span>
    <br />
    <input type="checkbox" class="ace" name="email_validating_only" value="1" '.
    (empty($email_arr['email_validating_only'])?'':'checked="checked" ').'/><span class="lbl"> '.AdminPhrase('users_filter_validating').'</span>
    </div>
  </div>';
  }

  #if($single_user)
  {
    echo '
	<div class="form-group">
			<label class="control-label col-sm-2" for="email_bcc">' . AdminPhrase('users_email_bcc_address') . '</label>
			<div class="col-sm-6">
				<input type="text" name="email_bcc" value="" class="form-control" />
			</div>
		</div>';
  }
  //SD360: option to add unsubscribe from email link to bottom of email
  echo '
  <div class="form-group">
			<label class="control-label col-sm-2" for="email_bcc">' . AdminPhrase('users_email_unsubscribe_link') . '</label>
			<div class="col-sm-6">
  				<input type="checkbox" class="ace" name="add_unsubscribe_link" value="1" checked="checked" />
				 <span class="lbl"> ' . AdminPhrase('common_yes') .'</span>
			</div>
		</div>';


  echo '<h3 class="header blue lighter">' . AdminPhrase('users_email_subject_and_message') . '</h3>
  <div class="form-group">
			<label class="control-label col-sm-2" for="email_bcc">' . AdminPhrase('users_email_content_type') . '</label>
			<div class="col-sm-6">
      <select name="email_content_type" class="form-control">
        <option value="text/plain"' . (empty($email_arr['email_content_type'])||($email_arr['email_content_type']=='text/plain') ? ' selected="selected"' : '') . '>' . AdminPhrase('users_email_text') . '</option>
        <option value="text/html"' . (isset($email_arr['email_content_type'])&&($email_arr['email_content_type'] == 'text/html') ? ' selected="selected"' : '') . '>' . AdminPhrase('users_email_html') . '</option>
      </select>
    </div>
  </div>
  <div class="form-group">
			<label class="control-label col-sm-2" for="email_bcc">' . AdminPhrase('users_email_subject') . '</label>
			<div class="col-sm-6">
  				<input type="text" name="email_subject" value="';
  if(isset($email_arr['email_subject']))
  {
    echo $DB->escape_string($email_arr['email_subject']);
  }
  echo '" class="form-control" />';
  if(!empty($pwdreset))
  {
    echo '<span class="helper-text">'.AdminPhrase('users_send_password_reset_email_hint'). '</span>';
  }
  echo '
    </div>
  </div>
  <div class="form-group">
			<label class="control-label col-sm-2" for="email_bcc">' . AdminPhrase('users_email_message') . '</label>
			<div class="col-sm-6">';
  $msg = '';
  if(isset($email_arr['email_message']))
  {
    $msg = !empty($pwdreset)?$email_arr['email_message']:$DB->escape_string($email_arr['email_message']);
  }
/*
    <textarea name="email_message" rows="10" cols="80" style="width:99%; height:200px;">';
  if(!empty($email_arr['email_message']))
  {
    echo $msg;
  }
  echo '</textarea>';
  */
  PrintWysiwygElement('email_message', $msg, 10, 80);
  echo '</div>
  </div>
  <div class="center">
  	<button class="btn btn-info" type="submit" value="" /><i class="ace-icon fa fa-check bigger-120"></i> ' . htmlspecialchars(AdminPhrase('users_email_send'),ENT_COMPAT) . '</button>
</div>
  </form>';

} //DisplayEmailUsersForm


// ############################################################################
// SETUP EMAILS
// ############################################################################

function SetupEmails()
{
  global $DB, $mainsettings, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_USERS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $errors_arr = array();

  $total_individuals_to_email = 0;
  $total_users_to_email = 0;

  $email_to_addresses = GetVar('email_to_addresses', '', 'string');
  $email_user_group_ids_arr = GetVar('email_user_group_ids_arr', array(), 'array');
  $email_from_name = GetVar('email_from_name', '', 'string');
  $email_from_address = GetVar('email_from_address', '', 'string');
  $email_content_type = GetVar('email_content_type', 'text/plain', 'string');
  $email_subject = GetVar('email_subject', '', 'html');
  $email_message = GetVar('email_message', '', 'html');
  //SD332: react on new password reset
  $email_userid = GetVar('email_userid', 0, 'whole_number');
  $email_newpass = GetVar('email_newpass', '', 'string');
  $email_empty_password = GetVar('email_empty_password', false, 'bool'); //SD344
  $email_validating_only = GetVar('email_validating_only', false, 'bool'); //SD360

  //SD332: BCC only for single-user email
  $email_bcc = '';
  #if($email_userid > 0) //SD360: commented out
  {
    $email_bcc = GetVar('email_bcc', '', 'string');
  }
  $add_unsubscribe_link = GetVar('add_unsubscribe_link', 0, 'bool'); //SD360

  if(!strlen($email_from_name))
  {
    $errors_arr[] = AdminPhrase('users_email_from_name_missing');
  }

  if(!strlen($email_from_address))
  {
    $errors_arr[] = AdminPhrase('users_email_from_address_missing');
  }

  if(!strlen($email_subject))
  {
    $errors_arr[] = AdminPhrase('users_email_subject_missing');
  }

  if(!strlen($email_message))
  {
    $errors_arr[] = AdminPhrase('users_email_message_missing');
  }

  // create an array of the individual email addresses
  if(strlen($email_to_addresses))
  {
    $individual_email_addresses = str_replace(',', ' ', $email_to_addresses);                // get rid of commas
    $individual_email_addresses = preg_replace('/\s\s+/', ' ', $individual_email_addresses); // get rid of extra spaces
    $individual_email_addresses = trim($individual_email_addresses);                         // then trim
    $individual_email_addresses_arr = explode(' ', $individual_email_addresses);

    $total_individuals_to_email = count($individual_email_addresses_arr);
  }

  //SD344: make sure usergroup id's are all int's
  if(count($email_user_group_ids_arr))
  {
    $email_user_group_ids_arr = array_map('intval', $email_user_group_ids_arr);
  }

  // get a count of users to email
  if(count($email_user_group_ids_arr))
  {
    $email_user_group_ids = implode(',', $email_user_group_ids_arr);

    //SD344: new option to only email users without password
    $extra = '';
    if(!empty($email_empty_password))
    {
      $extra .= " AND (IFNULL(password,'') = '')";
    }
    if(!empty($email_validating_only)) //SD360
    {
      $extra .= " AND (activated = 0)";
    }
    else
    {
      $extra .= ' AND (IFNULL(activated,0) = 1)';
    }

    if($gettotal = $DB->query('SELECT DISTINCT userid, email, joindate'.
                              ' FROM {users}'.
                              ' WHERE IFNULL(receive_emails,0) = 1 '.
                              " AND IFNULL(email,'') <> '' ".
                              $extra.
                              ' AND usergroupid IN (' . $email_user_group_ids . ')'))
    {
      $total_users_to_email = $DB->get_num_rows();
    }
  }
  else
  {
    $email_user_group_ids = '';
  }

  if(!$total_individuals_to_email && !$total_users_to_email)
  {
    $errors_arr[] = AdminPhrase('users_email_nobody_to_email');
  }

  if(count($errors_arr))
  {
    DisplayEmailUsersForm($errors_arr);
    return false;
  }

  $email_info_arr = array('total_individuals_to_email' => $total_individuals_to_email,
                          'total_users_to_email' => $total_users_to_email,
                          'email_to_addresses' => $email_to_addresses,
                          'email_user_group_ids' => $email_user_group_ids,
                          'email_from_name' => $email_from_name,
                          'email_from_address' => $email_from_address,
                          'email_subject' => $email_subject,
                          'email_message' => $email_message,
                          'email_content_type' => $email_content_type,
                          'email_newpass' => $email_newpass,
                          'email_userid' => $email_userid,
                          'failed_count' => 0,
                          'email_batch' => 0,
                          'email_bcc' => $email_bcc,
                          'email_empty_password' => $email_empty_password, //SD344
                          'add_unsubscribe_link' => $add_unsubscribe_link, //SD360
                          'email_validating_only' => $email_validating_only, //SD360
                          );

  SendUserEmails($email_info_arr);

} //SetupEmails


// ############################################################################
// SEND EMAILS
// ############################################################################
// first send out all emails to selected usergroups (50 at a time)
// the finally send out the individual emails

function SendUserEmails($email_info_arr = array())
{
  global $DB, $mainsettings, $sdlanguage, $sdurl, $userinfo, $isajax;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_USERS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return false;
  }

  if(@is_array($email_info_arr) && count($email_info_arr))
  {
    extract($email_info_arr);
    $total_emails_sent = 0;
    $number_of_users_cycled_through = 0;
    $limit_start = 0;
    $total_emails_to_send = ($total_individuals_to_email + $total_users_to_email);
  }
  else
  {
    $total_emails_to_send = GetVar('total_emails_to_send', 0, 'whole_number');
    $total_emails_sent = GetVar('total_emails_sent', 0, 'natural_number');
    $number_of_users_cycled_through = GetVar('number_of_users_cycled_through', 0, 'natural_number');
    $total_users_to_email = GetVar('total_users_to_email', 0, 'whole_number');
    $limit_start = GetVar('limit_start', 0, 'natural_number');
    $email_user_group_ids = GetVar('email_user_group_ids', '', 'string');
    $email_from_name = GetVar('email_from_name', '', 'string');
    $email_from_address = GetVar('email_from_address', '', 'string');
    $email_subject = GetVar('email_subject', '', 'html');
    $email_message = GetVar('email_message', '', 'html');
    $email_content_type = GetVar('email_content_type', '', 'string');
    $failed_count = GetVar('failed_count', 0, 'natural_number');
    $email_batch = GetVar('email_batch', 0, 'natural_number');
    $email_bcc = ''; //SD360: no BCC for batch emails
    $add_unsubscribe_link = GetVar('add_unsubscribe_link', 0, 'bool'); //SD360

    $limit_start += EMAIL_BATCH_SIZE;
  }
  $is_html = ($email_content_type == 'text/html');
  $total_individuals_to_email = empty($total_individuals_to_email) ? 0 : (int)$total_individuals_to_email; //SD341
  
  

  if(!$isajax)
  {
    // "Loading" overlay:
    echo '
    <div id="loader" style="display: none; position: absolute; width: 100%; height: 100%; margin-left: -10000; text-align:center; z-index: 10; background-color: rgba(0,0,0,0.7);">
    
     <div style="position: relative; padding: 15px; text-align:center; background-color: #F0F0F0; width: 100px;">
	<i class="ace-icon fa fa-spinner fa-spin blue bigger-250"></i> '.AdminPhrase('users_loading').'</div>
	</div>
	


    <div id="emailabortdiv" style="display: none; position: absolute; top: 100%; left: 45%; text-align: left; padding-top: 12px; height: 32px;">
    <a href="#" class="btn btn-info" onclick="javascript:AbortEmailing();return false;"><span class="btn_bg"><span class="btn_delete"> '.AdminPhrase('users_email_emails_pause').' </span></span></a>
    </div>';
	
	echo '<h3 class="header blue lighter">' . AdminPhrase('users_email_emailing_users') . '</h3>';
  }
  

  echo '
  <div id="emailing_container">
  <table border="0" cellpadding="0" cellspacing="0" width="100%" >
  <tr>
    <td>
     </td>
  </tr>
  <tr>
    <td>' .
      AdminPhrase('users_email_sending_emails') . ' (' . ($total_emails_to_send - $total_emails_sent) . ' ' .
      AdminPhrase('users_email_emails_remaining') . ')<br /><br />';

  // First send out individual emails
  $failed_individual_count = $total_individual_sent = 0;
  if( ($limit_start == 0) && ($total_individuals_to_email > 0) )// meaning this is the first loop
  {
    $individual_email_addresses = str_replace(',', ' ', $email_to_addresses);                  // get rid of commas
    $individual_email_addresses = preg_replace('/\s\s+/', ' ', $individual_email_addresses);   // get rid of extra spaces
    $individual_email_addresses = trim($individual_email_addresses);                           // then trim
    $individual_email_addresses_arr = explode(' ', $individual_email_addresses);

    for($i = 0; $i < $total_individuals_to_email; $i++)
    {
      $isSent = false;
      if(IsValidEmail($individual_email_addresses_arr[$i])) //SD313
      {
        @usleep(250000); //wait 1/4 second
        #@sleep(3); //FOR TESTING!
        if((defined('EMAIL_NOSEND') && EMAIL_NOSEND) ||
           SendEmail($individual_email_addresses_arr[$i], $email_subject, $email_message,$email_from_name, $email_from_address, null, $email_bcc, $is_html)
        )
        {
          echo AdminPhrase('users_email_sent_to') . ' ' . $individual_email_addresses_arr[$i] . '<br />';
          $isSent = true;
          $total_emails_sent++;
          $total_individual_sent++;
          $email_bcc = ''; //reset bcc

          //SD332: password reset email:
          if(!empty($email_newpass) && !empty($email_userid))
          {
            $DB->query("UPDATE {users} SET password='%s', salt='', use_salt=0 WHERE userid = %d", md5($email_newpass), $email_userid);
          }
        }
      }
      if(!$isSent)
      {
        $failed_individual_count++;
      }
    } //for
  }

  // now send out emails to users in batches
  if($total_users_to_email > 0)
  {
    //SD344: new option to only email users without password
    $extra = '';
    if(!empty($email_empty_password))
    {
      $extra .= " AND (IFNULL(password,'') = '')";
    }
    if(!empty($email_validating_only)) //SD360
    {
      $extra .= ' AND (activated = 0)';
    }
    else
    {
      $extra .= ' AND (IFNULL(activated,0) = 1)';
    }

    $get_users_to_email = $DB->query('SELECT DISTINCT userid, email, joindate'.
                                     ' FROM {users}'.
                                     ' WHERE IFNULL(receive_emails,0) = 1'.
                                     " AND IFNULL(email,'') <> '' ".
                                     ' AND usergroupid IN (' . $email_user_group_ids . ')'.
                                     $extra.
                                     ' ORDER BY userid ASC'.
                                     ' LIMIT '.(int)$limit_start.', '.EMAIL_BATCH_SIZE);
                                     // EMAIL_BATCH_SIZE = send xx emails at a time

    //SD344: prevent interruptions by logging
    $old_ignore = $GLOBALS['sd_ignore_watchdog'];
    $GLOBALS['sd_ignore_watchdog'] = false;

    while($user_arr = $DB->fetch_array($get_users_to_email,null,MYSQL_ASSOC))
    {
      $email_addr  = $user_arr['email'];
      $new_message = $email_message;
      if(!empty($add_unsubscribe_link))
      {
        $email_link = $sdurl . 'index.php?unsubscribe_id=' . $user_arr['joindate'] . $user_arr['userid'];
        if($is_html)
        {
          $new_message .= '<br /><br /><a href="' . $email_link . '">' .
                          AdminPhrase('users_email_click_to_unsubscribe') . '</a>';
        }
        else
        {
          $new_message .= EMAIL_CRLF . EMAIL_CRLF . AdminPhrase('users_email_visit_to_unsubscribe') .
                          EMAIL_CRLF . $email_link;
        }
      }
      $isSent = false;
      if(!empty($email_addr) && IsValidEmail($email_addr)) //SD313
      {
        @usleep(200000); //wait 1/5 second
        if((defined('EMAIL_NOSEND') && EMAIL_NOSEND) ||
           SendEmail($email_addr, unhtmlspecialchars($email_subject), $new_message,
                     $email_from_name, $email_from_address, null, $email_bcc, $is_html)
        )
        {
          echo AdminPhrase('users_email_sent_to') . ' ' . $email_addr . '<br />';
          $total_emails_sent++;
          $isSent = true;
          $email_bcc = ''; //reset bcc
        }
      }
      if(!$isSent)
      {
        $failed_count++;
      }
      $number_of_users_cycled_through++;

    } //while

    $GLOBALS['sd_ignore_watchdog'] = $old_ignore;
  }

  echo '</td></tr>';

  // If queue is empty, display extra rows
  $queue_end = false;
  if( (!$total_individuals_to_email || ($total_individuals_to_email-$total_individual_sent-$failed_individual_count < 1)) &&
      (!$total_users_to_email || ($total_users_to_email-$number_of_users_cycled_through-$failed_count < 1)) )
  {
    $queue_end = true;
    if($failed_count + $failed_individual_count)
    {
      echo '<tr><td>
    <strong>'.($failed_count + $failed_individual_count).' '.AdminPhrase('users_email_emails_not_sent').'</strong><br />
    </td></tr>';
    }
    echo '<tr><td>
    <strong>'.$total_emails_sent.' '.AdminPhrase('users_email_emails_sent').'</strong><br /></td></tr>';
    //DO NOT "RETURN" HERE!
  }
  echo '
  </table>';


  $email_batch++;
  //$trigger = ($total_users_to_email && ($total_users_to_email > $number_of_users_cycled_through)) ? 1 : 2;
  $trigger = !$queue_end ? 1 : 2;

  //SD370: added htmlspecialchars() to subject/message text for form:
  echo '
  <form id="send_emails" action="'.SELF_USERS_PHP.'?mailing=1'.SD_URL_TOKEN.'" method="post">
  <input id="trigger" name="trigger" type="hidden" value="'.$trigger.'" />
  <input id="totalsent" name="total_emails_sent" type="hidden" value="' . (int)$total_emails_sent . '" />
  <input id="totalfailed" name="total_emails_failed" type="hidden" value="' . (int)($failed_count + $failed_individual_count) . '" />
  <input type="hidden" name="email_user_group_ids" value="' . $email_user_group_ids . '" />
  <input type="hidden" name="email_from_name" value="' . $email_from_name . '" />
  <input type="hidden" name="email_from_address" value="' . $email_from_address . '" />
  <input type="hidden" name="email_subject" value="' . htmlspecialchars($email_subject,ENT_QUOTES,SD_CHARSET) . '" />
  <input type="hidden" name="email_message" value="' . htmlspecialchars($email_message,ENT_QUOTES,SD_CHARSET) . '" />
  <input type="hidden" name="email_content_type" value="' . $email_content_type . '" />
  <input type="hidden" name="email_bcc" value="0" />
  <input type="hidden" name="add_unsubscribe_link" value="' . $add_unsubscribe_link . '" />
  ';
  if(!empty($email_userid))
  {
    echo '<input type="hidden" name="email_userid" value="' . $email_userid . '" />';
  }
  if(!empty($email_newpass))
  {
    echo '<input type="hidden" name="email_newpass" value="' . $email_newpass. '" />';
  }

  // Refresh page if there are more users in the queue
  if($total_users_to_email && ($total_users_to_email > $number_of_users_cycled_through))
  {
    echo '
    <input type="hidden" name="action" value="send_user_emails" />
    <input type="hidden" name="total_emails_to_send" value="' . (int)$total_emails_to_send . '" />
    <input type="hidden" name="number_of_users_cycled_through" value="' . (int)$number_of_users_cycled_through . '" />
    <input type="hidden" name="total_users_to_email" value="' . (int)$total_users_to_email . '" />
    <input type="hidden" name="limit_start" value="' . (int)$limit_start . '" />
    <input id="email_batch" type="hidden" name="email_batch" value="' . (int)$email_batch. '" />
    ';
  }
  else
  {
    echo '<input type="hidden" name="action" value="display_email_users_form" />';
  }
  echo '
    </form>';

  // If called by Ajax, then no further action/output!
  if($isajax)
  {
    $DB->close();
    exit();
  }


  echo '</div>
<script type="text/javascript">
//<![CDATA[
var email_batch, timerID=false, posX, posY, $loader, $loader_inner;
function AbortEmailing() {
  if(timerID!==false) {
    window.clearTimeout(timerID);
    jQuery("#emailabortdiv span span").html(\'<strong> '.addslashes(AdminPhrase('users_email_emails_resume')).' <\/strong>\');
    timerID=false;
  } else {
    jQuery("#emailabortdiv span span").html(\'<strong> '.addslashes(AdminPhrase('users_email_emails_pause')).' <\/strong>\');
    Refresh();
  }
  return false;
}

function Refresh() {
  if(timerID!==false){ window.clearTimeout(timerID); }
  trigger = parseInt(jQuery("input#trigger").val(),10); /* 1 or 2 */
  var form = jQuery("form#send_emails");
  if(trigger === 1) {
    /* Submit page after 10 batches instead of ajax load */
    email_batch = parseInt(form.find("input#email_batch").val(),10);
    if(email_batch >= 10){
      $("input#email_batch").val(0);
      form.trigger("submit");
      return false;
    }
    $content = $("div#emailing_container");
    $loader.css({
      display: "block", margin: 0, padding: 0,
      width:   ($content.width())+"px",
      height:  ($content.height() + 70)+"px",
      top:     posY+"px", left: posX+"px"
    });
    $loader_inner.css({ top: "50%", left: "50%" });

    var formdata = form.serialize();
    var uri = form.attr("action") + "&" + formdata;
    jQuery("div#emailing_container").load(uri, {}, function(){
      $loader.hide();
      timerID = window.setTimeout(Refresh, 1500);
    });
    return false;
  } else {
    jQuery("#emailabortdiv").hide();
    var notsent = parseInt(form.find("input#totalfailed").val(),10);
    if(notsent === 0) {
      var result = form.serialize();
      var uri = "'.SELF_USERS_PHP.'?action=display_email_users_form'. SD_URL_TOKEN.'&amp;"+result;
      window.location = uri;
    } else {
      form.append(\'<center><input type="submit" value="'.addslashes(AdminPhrase('email_prompt_back')).'" style="margin: 8px; padding: 4px;" \/><\/center>\');
    }
  }
}

if (typeof(jQuery) !== "undefined") {
  jQuery(document).ready(function(){
    $loader = jQuery("div#loader");
    $loader_inner = jQuery("div#loader div:first");
    jQuery("#emailabortdiv").show();
    timerID = window.setTimeout(Refresh, 750);
  });
}
//]]>
</script>
';

} //SendUserEmails


// ############################################################################
// UPDATE USER'S USERGROUP
// ############################################################################
function ChangeUsersGroup()
{
  global $DB, $userinfo;

  $userid      = GetVar('userid', 0, 'whole_number');
  $usergroupid = GetVar('changedusergroupid', 0, 'whole_number');

  if(!CheckFormToken() || ($userid==$userinfo['userid']) || !$userid || !$usergroupid)
  {
    echo 'ERROR: Access denied!';
	echo '<script>
	jDialog.close();
	var n = noty({
						text: \'ERROR: Access Denied!\',
						layout: \'top\',
						type: \'error\',	
						timeout: 5000,					
						});</script>';
    exit();
  }

  if($user_arr = $DB->query_first('SELECT userid FROM {users} WHERE userid = %d',$userid))
  {
    $DB->result_type = MYSQL_ASSOC;
    if($group_arr = $DB->query_first('SELECT name,adminaccess,banned FROM {usergroups} WHERE usergroupid = %d',$usergroupid))
    {
      $DB->query('UPDATE {users} SET usergroupid = %d WHERE userid = %d',$usergroupid,$userid);

      // Prepare special color tag for usergroup
      $ug_tag = false;
      if(!empty($group_arr['adminaccess']))
      {
        $ug_tag = '<span class="blue">';
      }
      else
      if(!empty($group_arr['banned']))
      {
        $ug_tag = '<span class="red">';
      }
      echo '<a href="#" rel="'.$userid.'" onclick="javascript:;" class="ug_link"><i class="ace-icon fa fa-group bigger-110"></i> ' . ($ug_tag ? $ug_tag : '').
        $group_arr['name'] . ($ug_tag ? '</span>' : '');
		
		echo '<script>
	jDialog.close();
	var n = noty({
						text: \''. AdminPhrase('user_status_updated').'\',
						layout: \'top\',
						type: \'success\',	
						timeout: 5000,					
						});</script>';
      exit();
    }
	
  }

  echo 'ERROR!';
  echo '<script>
	jDialog.close();
	var n = noty({
						text: \'ERROR\',
						layout: \'top\',
						type: \'error\',	
						timeout: 5000,					
						});</script>';
  exit();

} //ChangeUsersGroup


// ############################################################################
// UPDATE USER'S STATUS
// ############################################################################
function ChangeUserStatus()
{
  global $DB, $userinfo;

  $userid    = GetVar('userid',    0, 'whole_number');
  $activated = GetVar('activated', 0, 'natural_number');
  $banned    = GetVar('banned',    0, 'natural_number');

  if(!CheckFormToken() || ($userid==$userinfo['userid']) || !$userid)
  {
    echo 'ERROR: Access denied!';
	echo '<script>
	jDialog.close();
	var n = noty({
						text: \'ERROR: Access Denied\',
						layout: \'top\',
						type: \'error\',	
						timeout: 5000,					
						});</script>';
    exit();
  }

  if($user_arr = $DB->query_first('SELECT u.*, IFNULL(ug.banned,0) AS ug_banned'.
                                  ' FROM {users} u'.
                                  ' LEFT JOIN {usergroups} ug ON ug.usergroupid = u.usergroupid'.
                                  ' WHERE userid = %d',$userid))
  {
    $user_arr['activated'] = $activated;
    $user_arr['banned']    = $banned;

    $DB->query('UPDATE {users} SET activated = %d, banned = %d WHERE userid = %d',
               $activated,$banned,$userid);

    echo GetUserStatusLink($user_arr);
	echo '<script>
	jDialog.close();
	var n = noty({
						text: \''. AdminPhrase('user_status_updated').'\',
						layout: \'top\',
						type: \'success\',	
						timeout: 5000,					
						});</script>';

    exit();
  }

	echo '<script>
	jDialog.close();
	var n = noty({
						text: \'ERROR\',
						layout: \'top\',
						type: \'error\',	
						timeout: 5000,					
						});</script>';
  echo 'ERROR!';
  exit();

} //ChangeUserStatus


// ############################################################################
// GET LINK TO CHANGE USER'S STATUS
// ############################################################################

function GetUserStatusLink($user)
{
  if(empty($user) || !is_array($user)) return '';
  $res   = '';
  $class = 'user-status-link';
  $color = '#000';
  $href  = '#';
  $title = '';
  $text  = AdminPhrase('users_activated');
  if(empty($user['activated']))
  {
    $class = 'user-status-link';
    $color = '#0000FF'; // blue
    $href  = SELF_USERS_PHP.'?action=email_activation_link';
    $title = AdminPhrase('users_validating_link');
    //SD343: differentiate between validation and activation required
    if(empty($user['validationkey']))
      $text = 'Activation required!';
    else
      $text = AdminPhrase('users_validating');
    $text .= '&nbsp;&nbsp;<span class="sprite sprite-email"></span>';
  }
  else
  if(!empty($user['banned']))
  {
    $color = '#FF0000'; // red
    $text  = AdminPhrase('users_banned');
  }
  else
  {
    if(empty($user['password']))
    {
      $color = '#FF0000'; // red
      $title = 'No password!';
    }
    else
      $color = '#008000'; // dark green
    $text  = AdminPhrase('users_activated');
  }
  $res = '
    <input type="hidden" name="usr_a" value="'.(int)$user['activated'].'" />
    <input type="hidden" name="usr_b" value="'.(int)$user['banned'].'" />
    <a style="color: '.$color.'" href="'.$href.'" '.
         'rel="'.$user['userid'].'" '.
         ($href=='#'?' onclick="javascript:return false;" ':'').
         ($title?' title="'.$title.'" ':'').
         ($class?' class="'.$class.'" ':'').
         '>' . $text . '</a>';
  if(!empty($user['ug_banned']))
  {
    $res .= '<br /><span style="color: #800000">'.AdminPhrase('users_group_banned').'</span>';
  }

  return $res;

} //GetUserStatusLink

// ############################################################################
// MOVE USERS
// ############################################################################

function MoveUsers()
{
  global $DB, $sdlanguage, $userinfo;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_USERS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $errors_arr = array();
  if($gid = GetVar('usergroup_move', 0, 'whole_number', true, false))
  {
    $move_user_ids = GetVar('userids', array(), 'array', true, false);
    if(is_array($move_user_ids) && !empty($move_user_ids))
    {
      $DB->ignore_error = true;
      foreach($move_user_ids as $uid)
      {
        if(Is_Valid_Number($uid, 0, 1, 9999999))
        {
           // Can't delete your own user account
          if($uid == $userinfo['userid'])
          {
            $errors_arr[] = AdminPhrase('users_can_not_delete_self');
          }
          else
          {
            $DB->query('UPDATE {users} SET usergroupid = %d WHERE userid = %d', (int)$gid, (int)$uid);
          }
        }
      }
    }
  }

  if(count($errors_arr))
  {
    DisplayMessage($errors_arr, true);
    DisplayUsers();
  }
  else
  {
    RedirectPage(SELF_USERS_PHP.'?action=display_users', AdminPhrase('users_users_moved'));
  }

} //MoveUsers


// ############################################################################
// UPDATE USERS
// ############################################################################

function UpdateUsers()
{
  global $DB, $sdlanguage, $userinfo, $usersystem;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_USERS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $delete_user_id = GetVar('userids', array(), 'array', true, false);
  $confirmdelete  = GetVar('confirmdelete', '', 'string', true, false);

  $errors_arr = array();

  if($confirmdelete == AdminPhrase('common_no'))
  {
    $GLOBALS['action'] = 'display_users';
    RedirectPage(SELF_USERS_PHP.'?action=display_users', 'Aborted.', 0.01);
    return;
  }

  if(!$confirmdelete)
  {
    if(is_array($delete_user_id) && !empty($delete_user_id))
    {
      $description = AdminPhrase('users_confirm_delete_user');
      $hidden_input_values = '<input type="hidden" name="action" value="update_users" />';
      foreach($delete_user_id as $uid)
      {
        if(Is_Valid_Number($uid, 0, 1, 9999999))
        {
          if($user_arr = $DB->query_first('SELECT username FROM {users} WHERE userid = '.(int)$uid))
          {
            $description .= '<br /><strong>&nbsp;&nbsp;' . $user_arr['username'].'</strong>';
            $hidden_input_values .= '<input type="hidden" name="userids[]" value="' . $uid . '" />';
          }
        }
      }
      // arguments: description, hidden input values, form redirect page
      ConfirmDelete($description, $hidden_input_values, SELF_USERS_PHP);
    }
    else
    {
      $GLOBALS['action'] = 'display_users';
      RedirectPage(SELF_USERS_PHP.'?action=display_users', '', 0.01);
      return;
    }
  }
  else
  if($confirmdelete == AdminPhrase('common_yes'))
  {
    if(is_array($delete_user_id) && !empty($delete_user_id))
    {
      $DB->ignore_error = true;
      $us_id = $DB->query_first('SELECT usersystemid FROM {usersystems}'.
                                " WHERE name = 'Subdreamer'");
      if(empty($us_id['usersystemid'])) return false;
      $us_id = $us_id['usersystemid'];
      foreach($delete_user_id as $uid)
      {
        if(Is_Valid_Number($uid, 0, 1, 9999999))
        {
           // Can't delete your own user account
          if($uid == $userinfo['userid'])
          {
            $errors_arr[] = AdminPhrase('users_can_not_delete_self');
          }
          else
          {
            $DB->query('DELETE FROM {users} WHERE userid = %d',$uid);
            $DB->query('DELETE FROM {users_data} WHERE userid = %d',$uid); //SD342
            $DB->query('DELETE FROM {sessions} WHERE userid = %d',$uid);

            //SD351: add additional userid-driven content
            $DB->query('DELETE FROM {report_moderators} WHERE userid = %d AND usersystemid = %d', $uid, $us_id);
            if($getmst = $DB->query('SELECT master_id, starter_id, recipient_id'.
                                    ' FROM {msg_master}'.
                                    ' WHERE starter_id = %d AND usersystemid = %d',
                                    $uid, $us_id))
            {
              while($msg = $DB->fetch_array($getmst,null,MYSQL_ASSOC))
              {
                $DB->query('DELETE FROM {msg_messages} WHERE usersystemid = %d'.
                           ' AND (master_id = %d OR starter_id = %d OR recipient_id = %d)',
                           $us_id, $msg['master_id'], $uid, $uid);
                $DB->query('DELETE FROM {msg_user} WHERE master_id = %d', $msg['master_id']);
                $DB->query('DELETE FROM {msg_master} WHERE master_id = %d AND usersystemid = %d',
                           $msg['master_id'], $us_id);
              }
            }
            $DB->query('DELETE FROM '.PRGM_TABLE_PREFIX.'msg_text'.
                       ' WHERE NOT EXISTS('.
                         'SELECT 1 FROM '.PRGM_TABLE_PREFIX.'msg_messages ms'.
                         ' WHERE ms.msg_text_id = '.PRGM_TABLE_PREFIX.'msg_text.msg_text_id)');
            $DB->query('DELETE FROM {p7_chatterbox}'.
                       " WHERE userid = %d AND IFNULL(username,'') <> ''",
                       $uid);
            $DB->query('DELETE FROM {ratings} WHERE user_id = %d',$uid);
            $DB->query('DELETE FROM {users_bans} WHERE usersystemid = %d AND userid = %d',
                       $us_id, $uid);
            $DB->query('DELETE FROM {users_likes} WHERE userid = %d', $uid);
            $DB->query('DELETE FROM {users_reports} WHERE usersystemid = %d AND userid = %d',
                       $us_id, $uid);
            $DB->query('DELETE FROM {users_subscribe} WHERE userid = %d',$uid);
          }
        }
      } //foreach
    }

    if(count($errors_arr))
    {
      $GLOBALS['action'] = 'display_users';
    	DisplayMessage($errors_arr, true);
    	DisplayUsers();
    }
    else
    {
      RedirectPage(SELF_USERS_PHP.'?action=display_users', AdminPhrase('users_user_deleted'));
    }
  }

} //UpdateUsers


// ############################################################################
// EMAIL ACTIVATION LINK
// ############################################################################

function EmailActivationLink()
{
  global $DB, $SD_EMAIL_ERROR, $isajax, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_USERS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $errors_arr = array();
  $DB->result_type = MYSQL_ASSOC;
  if(!$userid = GetVar('email_userid', 0, 'whole_number'))
  {
    $errors_arr[] = AdminPhrase('users_user_not_found');
  }
  else
  if($user = $DB->query_first('SELECT username, email FROM {users} WHERE userid = %d', $userid))
  {
    if($categoryid = $DB->query_first('SELECT categoryid FROM {pagesort}'.
                                      " WHERE pluginid = '12' ORDER BY categoryid LIMIT 1"))
    {
      // For min/max username/password length messages
      $p12_language = GetLanguage(12);

      $categoryid = (int)$categoryid['categoryid'];
      $key = CreateGuid();
      $validateurl = str_replace('&amp;', '&', RewriteLink('index.php?categoryid=' . $categoryid . '&p12_val=' . $key));

      // send email
      $subject = $p12_language['email_subject_activation'];
      $message = $user['username'] . "," . EMAIL_CRLF . EMAIL_CRLF .
                 $p12_language['email_message_activation'] . EMAIL_CRLF . EMAIL_CRLF .
                 $validateurl . EMAIL_CRLF;
      if(SendEmail($user['email'], $subject, $message))
      {
        //SD322: validation_time added
        $DB->query("UPDATE {users} SET activated = 0, validationkey = '%s', validation_time = %d WHERE userid = %d",
                   $key, TIME_NOW, $userid);
      }
      else
      {
        $errors_arr = isset($SD_EMAIL_ERROR) ? $SD_EMAIL_ERROR : array();
        $errors_arr[] = AdminPhrase('users_send_activation_link_failed');
      }

    }
    else
    {
      $errors_arr[] = AdminPhrase('users_missing_users_plugin');
    }
  }
  else
  {
    $errors_arr[] = AdminPhrase('users_user_not_found');
  }

  //SD332: support for ajax'ed display: show "close" button
  if($isajax)
  {
    if(count($errors_arr))
    {
      $msg = implode('<br />', $errors_arr);
      $time = 0;
    }
    else
    {
      $msg = AdminPhrase('users_activation_link_sent');
      $time = 4;
    }
	echo '<script>
	jDialog.close();
	var n = noty({
						text: \''.$msg.'\',
						layout: \'top\',
						type: '.(count($errors_arr) ? '\'error\'' : '\'success\'').',	
						timeout: 5000,					
						});</script>';
    //echo $msg.'</div>
  //  <div class="center">
   // <a href="#" class="btn btn-info btn-xs" onclick="parent.jQuery.fn.ceebox.closebox(\'fast\');"><i class="ace-icon fa fa-times"></i> '.AdminPhrase('close_window').'</span></span></a></center>
  //  </div>
	//<div class="space-20"></div>'.
    sd_CloseCeebox($time);

    return;
  }

  if(count($errors_arr))
  {
    DisplayMessage($errors_arr, true);
    DisplayUsers();
  }
  else
  {
    RedirectPage(SELF_USERS_PHP, AdminPhrase('users_activation_link_sent'));
  }

} //EmailActivationLink


// ############################################################################
// EMAIL WELCOME MESSAGE TO USER
// ############################################################################

function EmailWelcomeMessage() //SD343
{
  global $DB, $SD_EMAIL_ERROR, $isajax, $mainsettings_websitetitle_original, $sdlanguage, $sdurl;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_USERS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $errors_arr = array();
  $DB->result_type = MYSQL_ASSOC;
  if(!$userid = GetVar('email_userid', 0, 'whole_number'))
  {
    $errors_arr[] = AdminPhrase('users_user_not_found');
  }
  else
  if($user = $DB->query_first('SELECT username, email, joindate FROM {users} WHERE userid = %d', $userid))
  {
    $p12_settings = GetPluginSettings(12);

    $subject = trim($p12_settings['welcome_message_subject']);
    $message = trim($p12_settings['welcome_message_text']);
    if(strlen($subject) && strlen($message))
    {
      $subject = str_replace(array('[username]', '[date]', '[joindate]', '[email]', '[siteurl]', '[sitename]'),
                             array($user['username'], DisplayDate(TIME_NOW), DisplayDate($user['joindate']), $user['email'], $sdurl, $mainsettings_websitetitle_original), $subject);
      $message = str_replace(array('[username]', '[date]', '[joindate]', '[email]', '[siteurl]', '[sitename]'),
                             array($user['username'], DisplayDate(TIME_NOW), DisplayDate($user['joindate']), $user['email'], $sdurl, $mainsettings_websitetitle_original), $message);
      $sent = SendEmail($user['email'], $subject, $message,
                        $p12_settings['welcome_message_email_from'], $p12_settings['welcome_message_email_sender'],
                        null, null, true);
      if(!$sent || !empty($GLOBALS['SD_EMAIL_ERROR']))
      {
        $errors_arr = isset($SD_EMAIL_ERROR) ? $SD_EMAIL_ERROR : array();
        $errors_arr[] = AdminPhrase('users_send_welcome_failed');
      }
    }
  }
  else
  {
    $errors_arr[] = AdminPhrase('users_user_not_found');
  }

  //SD332: support for ajax'ed display: show "close" button
  if($isajax)
  {
	 
    if(count($errors_arr))
    {
      $msg = implode('<br />', $errors_arr);
      $time = 0;
    }
    else
    {
      $msg = AdminPhrase('users_welcome_email_sent') . $user['email'];//AdminPhrase('users_activation_link_sent');
      $time = 4;
    }
   echo '<script>
   		 	jDialog.close();
			var n = noty({
						text: \''.$msg.'\',
						layout: \'top\',
						type: '.(count($errors_arr) ? '\'error\'' : '\'success\'').',	
						timeout: 5000,	
						});</script>';
						
    sd_CloseCeebox($time);

    return;
  }

  if(count($errors_arr))
  {
    DisplayMessage($errors_arr, true);
    DisplayUsers();
  }
  else
  {
    RedirectPage(SELF_USERS_PHP, AdminPhrase('users_activation_link_sent'));
  }

} //EmailWelcomeMessage


// ############################################################################
// SAVE USER (update or insert)
// ############################################################################

function SaveUser($action='')
{
  global $DB, $sdlanguage, $usersystem, $UserProfile;
  
  if(!CheckFormToken() || empty($action))
  {
    RedirectPage(SELF_USERS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $userid = GetVar('userid', 0, 'whole_number', true, false);
  if(($action == 'update_user') && (($userid < 1) || ($userid > 9999999)))
  {
    $errors = array(); //SD343
    $errors[] = 'Invalid User ID!';
    DisplayMessage($errors, true);
    return false;
  }

  $usergroupid    = GetVar('usergroupid', MEMBERS_UGID, 'whole_number', true, false); // default MEMBERS_UGID = 3
  $username       = trim(GetVar('nameofuser', '', 'string', true, false));
  $password       = trim(GetVar('password', '', 'string', true, false));
  $password       = sd_unhtmlspecialchars($password); //SD370
  $admin_notes    = trim(GetVar('admin_notes', '', 'string', true, false));
  $activated      = (GetVar('activated', 0, 'bool', true, false) ? 1 : 0);
  $banned         = (GetVar('banned', 0, 'bool', true, false) ? 1 : 0);
  $email          = GetVar('useremail', '', 'string', true, false);
  $ignore_email   = (GetVar('ignore_email', 0, 'natural_number', true, false) ? 1 : 0);
  $authorname     = trim(GetVar('authorname', '','string', true, false)); //SD322
  $receive_emails = GetVar('receive_emails', 0, 'bool', true, false); //SD322
  $delete_avatar  = GetVar('delete_avatar', 0, 'bool', true, false) ? 1 : 0;
  $delete_pub_img = GetVar('delete_pub_image', 0, 'bool', true, false) ? 1 : 0;
  $disable_avatar = GetVar('disable_avatar', 0, 'bool', true, false) ? 1 : 0;
  $disable_pub_img= GetVar('disable_pub_image', 0, 'bool', true, false) ? 1 : 0;
  $register_ip    = GetVar('register_ip', 0, 'string', true, false); //SD343

  // SD322: Load all profile field configurations (users_fields) and values (users_data)
  $UserProfile->LoadUser($userid);

  //SD322: is user is not banned, then support multiple usergroups
  $usergroup_others = $banned ? '' : GetVar('usergroup_others', '', 'array', true, false);
  if(!empty($usergroup_others))
  {
    // Check if selected usergroups pose a violation to any other.
    // Meaning: if *any* usergroup has the "banned" option, the user cannot
    // be a member of any other non-banned usergroup simultaneously and
    // will ONLY receive the usergroups that have the "banned" option set!
    // Example: groups "Admins" + "Banned" will only return "Banned"
    $ugroups = $ug_admin = $ug_banned = array();
    foreach($usergroup_others as $ugid)
    {
      $DB->result_type = MYSQL_ASSOC;
      if(is_numeric($ugid) &&
         ($ug_arr = $DB->query_first('SELECT usergroupid, adminaccess, banned
          FROM {usergroups} WHERE usergroupid = %d
          ORDER BY adminaccess DESC, banned DESC',$ugid)))
      {
        if(($ugid > 0) && ($ugid <= 9999999))
        {
          if(!empty($ug_arr['banned']))
          {
            if(empty($ug_arr['adminaccess']))
            {
              $ug_banned[] = $ug_arr['usergroupid'];
            }
          }
          else if(!empty($ug_arr['adminaccess']))
          {
            $ug_admin[] = $ug_arr['usergroupid'];
          }
          else
          {
            $ugroups[] = $ug_arr['usergroupid'];
          }
        }
      }
    }
    $ugroups = empty($ug_banned) ? array_unique(array_merge($ugroups, $ug_admin)) : $ug_banned;
    $usergroup_others = '';
    if(!empty($ugroups))
    {
      sort($ugroups, SORT_NUMERIC);
      $usergroup_others = serialize($ugroups);
    }
  }

  if(!strlen($username))
  {
    $errors[] = AdminPhrase('users_enter_username');
  }

  if(($action == 'insert_user') && strlen($password) && (strlen($password) < 4))
  {
    $errors[] = AdminPhrase('users_enter_password');
  }

  if(!strlen($email) || !IsValidEmail($email))
  {
    $errors[] = AdminPhrase('users_enter_email');
  }

  // check if username already exists
  if($username_exists_arr = $DB->query_first("SELECT 1 FROM {users} WHERE username = '%s' AND userid != %d LIMIT 1",$username,$userid))
  {
    $errors[] = AdminPhrase('users_username_exists');
  }
  // check if email already exists
  if($email_exists_arr = $DB->query_first("SELECT 1 FROM {users} WHERE email = '%s' AND userid != %d LIMIT 1",$email,$userid))
  {
    if(!$ignore_email)
    $errors[] = AdminPhrase('users_email_exists');
  }

  // check if authorname already exists for same usersystem (SD322)
  $DB->result_type = MYSQL_ASSOC;
  if(!empty($authorname) &&
     ($authorname_exists_arr = $DB->query_first('SELECT 1 FROM {users_data}'.
                               " WHERE authorname = '$authorname' AND usersystemid = %d".
                               ' AND userid != %d LIMIT 1',
                               $usersystem['usersystemid'], $userid)))
  {
    $errors[] = AdminPhrase('users_authorname_exists');
  }

  if(empty($errors))
  {
    if($action == 'insert_user')
    {
      $salt = sd_generate_user_salt();
      $DB->query("INSERT INTO {users}
        (usergroupid, username, password, salt, use_salt, email, activated, joindate, validationkey,
         admin_notes, banned, usergroup_others, receive_emails)
        VALUES (%d, '%s', '%s', '%s', 1, '%s', %d, %d, '',
         '%s', %d, '%s', %d)",
        $usergroupid, $username, md5($salt.md5($password)), $DB->escape_string($salt), $email, $activated, TIME_NOW,
        $admin_notes, $banned, $usergroup_others, $receive_emails);
      $userid = $DB->insert_id();
    }
    else
    {
      // change password only if specified
      $upd_pwd = '';
      if(strlen($password))
      {
        $DB->result_type = MYSQL_ASSOC;
        $salt = $DB->query_first('SELECT salt FROM {users} WHERE userid = %d LIMIT 1',$userid);
        if(empty($salt['salt']))
        {
          $salt = sd_generate_user_salt();
          $upd_pwd = ", use_salt = 1, salt = '".$DB->escape_string($salt)."', password = '".md5($salt.md5($password))."'";
        }
        else
        {
          $upd_pwd = ", password = '".md5($salt['salt'].md5($password))."'";
        }
      }
      $DB->query("UPDATE {users}
      SET usergroupid = %d,
          username    = '%s',
          email       = '%s',
          activated   = %d,
          admin_notes = '%s',
          banned      = %d,
          receive_emails = %d,
          register_ip = '%s',
          usergroup_others = '%s'" . $upd_pwd ."
      WHERE userid = %d",
      $usergroupid, $username, $email, $activated, $admin_notes, $banned,
      $receive_emails, $register_ip, $usergroup_others, $userid);
    }
    if(!$DB->query_first('SELECT 1 FROM {users_data}'.
                         ' WHERE usersystemid = %d AND userid = %d'.
                         ' ORDER BY usersystemid LIMIT 1',
                         $usersystem['usersystemid'], $userid))
    {
      $DB->query('INSERT INTO '.PRGM_TABLE_PREFIX."users_data (usersystemid, userid, user_new_privmsg, user_last_privmsg, user_text)
                  VALUES (%d, %d, 0, 0, '')",
                  $usersystem['usersystemid'], $userid);
    }
    $DB->query('UPDATE {users_data}'.
               " SET authorname = '%s', avatar_disabled = %d, profile_img_disabled = %d".
               ' WHERE usersystemid = %d AND userid = %d',
               $DB->escape_string($authorname), $disable_avatar, $disable_pub_img,
               $usersystem['usersystemid'], $userid);

    $UserProfile->UseFormData = true;
    $UserProfile->UpdateProfile(true, false, $userid);

    // ##################### AVATAR UPLOAD HANDLING #####################
    //SD351: added avatar upload
    require_once(SD_INCLUDE_PATH.'class_sd_media.php');
    $usergroup_options = array();
    $imgname = '';
    $image_w = 0;
    $image_h = 0;
    $avatar_uploaded = false;

    if(isset(SDProfileConfig::$usergroups_config[$usergroupid]))
    {
      $usergroup_options = SDProfileConfig::$usergroups_config[$usergroupid];
    }
    $avatar_path = isset($usergroup_options['avatar_path'])?(string)$usergroup_options['avatar_path']:false;
    if($avatar_path !== false)
    {
      $avatar_path = SD_Media::FixPath($avatar_path);
      SDProfileConfig::makePathWritable(ROOT_PATH.$avatar_path);
      $avatar_path .= (substr($avatar_path,-1)=='/'?'':'/').floor($userid / 1000).'/';
      SDProfileConfig::makePathWritable(ROOT_PATH.$avatar_path);
      $avatar_path = (!empty($avatar_path) && is_dir(ROOT_PATH.$avatar_path) &&
                      is_writable(ROOT_PATH.$avatar_path)) ? $avatar_path : false;
      $avatar = ($avatar_path && isset($_FILES['avatar_upload'])) ? $_FILES['avatar_upload'] : false;

      if(($avatar !== false) && !empty($_FILES['avatar_upload']))
      {
        $img_obj   = false;
        $maxwidth  = Is_Valid_Number($usergroup_options['avatar_max_width'],60,20,4096);
        $maxheight = Is_Valid_Number($usergroup_options['avatar_max_height'],60,20,4096);
        if(true === ($avatar_uploaded = SD_Image_Helper::UploadImageAndCreateThumbnail(
                                           'avatar_upload', false, $avatar_path,
                                           'a'.$userid, 'a'.$userid.'-'.TIME_NOW,
                                           $maxwidth, $maxheight, 8192*1024,
                                           false, true, true, $img_obj)))
        {
          $imgname = basename($img_obj->image_file);
          $image_w = $img_obj->getImageWidth();
          $image_h = $img_obj->getImageheight();
        }
        unset($img_obj);
      }
    }

    if( ($delete_avatar || ($avatar_uploaded === true)) &&
        ($old_avatar = $DB->query_first(
                       'SELECT userid,user_avatar,user_avatar_link,avatar_disabled'.
                       ' FROM {users_data}'.
                       ' WHERE usersystemid = %d AND userid = %d',
                       SDProfileConfig::$usersystem['usersystemid'], $userid)))
    {
      if(!empty($old_avatar['user_avatar']) && ($avatar_path!==false))
      {
        if(is_file(ROOT_PATH.$avatar_path.$old_avatar['user_avatar']))
        {
          @unlink(ROOT_PATH.$avatar_path.$old_avatar['user_avatar']);
        }
      }
      if($avatar_uploaded !== true)
      {
        $DB->query('UPDATE {users_data}'.
                   " SET user_avatar = '', user_avatar_link = '', user_avatar_type = 0,".
                   " user_avatar_width = 0, user_avatar_height = 0".
                   ' WHERE usersystemid = %d AND userid = %d',
                   $usersystem['usersystemid'], $userid);
      }
    }

    // If new avatar was uploaded, update user row with image data
    if(($avatar_uploaded === true) && strlen($imgname))
    {
      $DB->query('UPDATE {users_data}'.
                 " SET user_avatar = '%s', user_avatar_link = '',".
                 " user_avatar_type = 1, user_avatar_width = %d,".
                 ' user_avatar_height = %d'.
                 ' WHERE usersystemid = %d AND userid = %d',
                 $imgname, $image_w, $image_h,
                 $usersystem['usersystemid'], $userid);
    }

    // ##################### PICTURE UPLOAD HANDLING #####################

    //SD351: added profile picture upload
    $avatar_path = isset($usergroup_options['pub_img_path'])?(string)$usergroup_options['pub_img_path']:false;
    $avatar_path = SD_Media::FixPath($avatar_path);
    SDProfileConfig::makePathWritable(ROOT_PATH.$avatar_path);
    $avatar_path .= (substr($avatar_path,-1)=='/'?'':'/').floor($userid / 1000).'/';
    SDProfileConfig::makePathWritable(ROOT_PATH.$avatar_path);
    $avatar_path = (!empty($avatar_path) && is_dir(ROOT_PATH.$avatar_path) &&
                    is_writable(ROOT_PATH.$avatar_path)) ? $avatar_path : false;

    $imgname = '';
    $image_w = 0;
    $image_h = 0;
    $avatar_uploaded = false;
    $avatar = ($avatar_path && isset($_FILES['picture_upload'])) ? $_FILES['picture_upload'] : false;

    if(($avatar !== false) && !empty($_FILES['picture_upload']))
    {
      $img_obj   = false;
      $maxwidth  = Is_Valid_Number($usergroup_options['pub_img_max_width'],60,20,4096);
      $maxheight = Is_Valid_Number($usergroup_options['pub_img_max_height'],60,20,4096);
      if(true === ($avatar_uploaded = SD_Image_Helper::UploadImageAndCreateThumbnail(
                                         'picture_upload', false, $avatar_path,
                                         'p'.$userid, 'p'.$userid.'-'.TIME_NOW,
                                         $maxwidth, $maxheight, 8192*1024,
                                         false, true, true, $img_obj)))
      {
        $imgname = basename($img_obj->image_file);
        $image_w = $img_obj->getImageWidth();
        $image_h = $img_obj->getImageheight();
      }
      unset($img_obj);
    }

    if( ($delete_pub_img || ($avatar_uploaded === true)) &&
        ($old_avatar = $DB->query_first(
                       'SELECT userid,user_profile_img,profile_img_link,profile_img_disabled'.
                       ' FROM {users_data}'.
                       ' WHERE usersystemid = %d AND userid = %d',
                       SDProfileConfig::$usersystem['usersystemid'], $userid)))
    {
      if(!empty($old_avatar['user_profile_img']) && ($avatar_path!==false))
      {
        if(is_file(ROOT_PATH.$avatar_path.$old_avatar['user_profile_img']))
        {
          @unlink(ROOT_PATH.$avatar_path.$old_avatar['user_profile_img']);
        }
      }
      if($avatar_uploaded !== true)
      {
        $DB->query('UPDATE {users_data}'.
                   " SET user_profile_img = '', profile_img_link = '', profile_img_type = 0,".
                   " profile_img_height = 0, profile_img_width = 0".
                   ' WHERE usersystemid = %d AND userid = %d',
                   $usersystem['usersystemid'], $userid);
      }
    }

    // If new avatar was uploaded, update user row with image data
    if(($avatar_uploaded === true) && strlen($imgname))
    {
      $DB->query('UPDATE {users_data}'.
                 " SET user_profile_img = '%s', profile_img_link = '',".
                 " profile_img_type = 1, profile_img_width = %d,".
                 ' profile_img_height = %d'.
                 ' WHERE usersystemid = %d AND userid = %d',
                 $imgname, $image_w, $image_h,
                 $usersystem['usersystemid'], $userid);
    }

   RedirectPage(SELF_USERS_PHP.'?action=display_user_form&amp;userid='.$userid.SD_URL_TOKEN,
                AdminPhrase('users_user_'.($action=='update_user'?'updated':'inserted')));
  }
  else
  {
    DisplayUserForm($errors);
  }

} //SaveUser


// ############################################################################
// DISPLAY USER FORM
// ############################################################################

function DisplayUserForm($errors = false)
{
  global $DB, $mainsettings, $sdlanguage, $userinfo, $admin_phrases,
         $UserProfile;

  if(empty($userinfo['adminaccess']) && !CheckFormToken() && !CheckFormToken($UserProfile->form_token))
  {
    RedirectPage(SELF_USERS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $userid = GetVar('userid', 0, 'natural_number');
  if($userid && !$DB->query_first('SELECT userid FROM '.PRGM_TABLE_PREFIX.'users'.
                                  ' WHERE userid = %d LIMIT 1', $userid))
  {
    RedirectPage(SELF_USERS_PHP,'<strong>Invalid user specified!</strong>',2,true);
    return;
  }

  $p11_phrases = GetLanguage(11);
  $p12_settings = GetPluginSettings(12);
  //SD332: activation link expiration (in days)
  $expire = empty($p12_settings['activation_link_expiration']) ? 14 : (int)$p12_settings['activation_link_expiration'];
  $expire = Is_Valid_Number($expire,14,2,365);

  // SD322: Load all profile field configurations (users_fields) and values (users_data)
  $profile_fields = SDProfileConfig::GetProfileFields();
  $UserProfile->LoadUser($userid);

  $user = array();
  if(!empty($userid))
  {
    $usergroup_others = array();
    $user = sd_GetUserrow($userid, array('authorname','public_fields','avatar_disabled',
              'user_avatar_type','user_avatar','user_avatar_height','user_avatar_width',
              'profile_img_type','profile_img_disabled','user_profile_img','profile_img_height','profile_img_width'));

    //SD360: if from DB, then process "usergroup_others" into an array
    if(isset($user['usergroup_others']) && is_string($user['usergroup_others']) &&
       (strlen($user['usergroup_others'])>3) && (substr($user['usergroup_others'],0,2)=='a:'))
    {
      $GLOBALS['sd_ignore_watchdog'] = true;
      if(false === ($usergroup_others = @unserialize($user['usergroup_others'])))
      {
        $usergroup_others = array();
      }
      $GLOBALS['sd_ignore_watchdog'] = false;
    }
    $user['usergroup_others'] = $usergroup_others;
  }

  if($errors)
  {
    DisplayMessage($errors, true);
    // error after insert/update of user
    $user['userid']           = $userid;
    $user['usergroupid']      = GetVar('usergroupid', 3, 'int');
    $user['usergroup_others'] = GetVar('usergroup_others', array(), 'array');

    //SD360: if empty username posted, use the one from DB
    $username = GetVar('nameofuser', '', 'html');
    $username = htmlspecialchars(trim(strip_alltags($username)));
    if(!empty($username) && (strlen($username)>1))
    {
      $user['username'] = $username;
    }

    $user['authorname']       = GetVar('authorname', '', 'string'); //SD322
    $user['email']            = GetVar('useremail', '', 'string');
    $user['activated']        = GetVar('activated', 0, 'int');
    $user['admin_notes']      = GetVar('admin_noted', '', 'string');
    $user['joindate']         = GetVar('joindate', '', 'string');
    $user['lastactivity']     = '';
    $user['receive_emails']   = 0;
    $user['banned']           = GetVar('banned', 0, 'whole_number');
    $user['disable_avatar']   = (GetVar('disable_avatar', 0, 'natural_number')?1:0);
    $user['remove_avatar']    = (GetVar('remove_avatar', 0, 'natural_number')?1:0);
  }
  else
  if(empty($userid))
  {
    $user = array('userid'            => 0,
                  'usergroupid'       => 3, // Registered Users (lets leave it as the default)
                  'usergroup_others'  => array(),
                  'username'          => '',
                  'authorname'        => '', //SD322
                  'email'             => '',
                  'activated'         => 1,
                  'admin_notes'       => '',
                  'joindate'          => '',
                  'lastactivity'      => '',
                  'receive_emails'    => 0,
                  'banned'            => 0,
                  'usergroup_others'  => '',
                  'disable_avatar'    => 0,
                  'remove_avatar'     => 0);
  }
  
  //SD332: added several email options
  if(!empty($userid))
  {
    echo '
    <div class="btn-group">
		<button data-toggle="dropdown" class="btn btn-info dropdown-toggle">
			' . AdminPhrase('users_user_actions') . '
			<span class="ace-icon fa fa-caret-down icon-on-right"></span>
		</button>
		<ul class="dropdown-menu dropdown-info">';
		
    $text = AdminPhrase('users_validating_link');
    $href = SELF_USERS_PHP.'?action=email_activation_link&amp;cbox=1&amp;email_userid='.$user['userid'].SD_URL_TOKEN;
    echo '
      <li><a class="ceebox smallcbox" href="'.$href.'"><i class="ace-icon fa fa-link"></i> ' . $text . '</a></li>';

    $text = AdminPhrase('users_send_password_reset_email');
    $href = SELF_USERS_PHP.'?action=display_email_users_form&amp;pwdreset=1&amp;email_userid='.$user['userid'].SD_URL_TOKEN;
    echo '<li><a  href="'.$href.'" target="_blank"><i class="ace-icon fa fa-key"></i> ' . $text . '</a></li>';

    $text = AdminPhrase('users_open_email');
    $href = SELF_USERS_PHP.'?action=display_email_users_form'.SD_URL_TOKEN.'&amp;email_userid='.$user['userid'];
    echo '<li><a  href="'.$href.'" target="_blank"><i class="ace-icon fa fa-envelope"></i> ' . $text . '</a></li>';

    $text = AdminPhrase('users_open_external_email');
    $href = 'mailto:'.$user['email'];
    echo ' <li><a href="'.$href.'"><i class="ace-icon fa fa-external-link"></i> ' . $text . '</a></li>
	</ul>
    </div>
	';
  }


  // a few variable names here are a bit strange such as nameofuser
  // this is so that browsers won't store saved information when viewing user details!
  echo '
  <form id="ucpForm" name="ucpForm" class=" form-horizontal" enctype="multipart/form-data" action="'.SELF_USERS_PHP.'" method="POST">
  <input type="hidden" name="'.$UserProfile->form_token.'" value="'.SD_FORM_TOKEN.'" />
  <input type="hidden" name="userid" value="' . $user['userid'] . '" />
  <input type="hidden" name="joindate" value="' . $user['joindate'] . '" />
  ';

/*
  //SD332: added several email options
  if(!empty($userid))
  {
	 echo '<h3 class="blue lighter">' . AdminPhrase('users_options') . '</h3>
	 	<div class="col-sm-3">';
    			$text = AdminPhrase('users_validating_link');
   				 $href = SELF_USERS_PHP.'?action=email_activation_link&amp;cbox=1&amp;email_userid='.$user['userid'].SD_URL_TOKEN;
   
    echo '
      <a class="ceebox smallcbox btn btn-info" href="'.$href.'">' . $text . '</a>
   
         '.AdminPhrase('users_activation_expiration').' <strong>'.$expire.'</strong><br />
      '.AdminPhrase('users_activation_expiration_hint').'</div>
      </div>';

	echo '<div class="col-sm-3">';
	
    $text = AdminPhrase('users_send_password_reset_email');
    $href = SELF_USERS_PHP.'?action=display_email_users_form&amp;pwdreset=1&amp;email_userid='.$user['userid'].SD_URL_TOKEN;
    echo '
      <a class="btn btn-info" href="'.$href.'" target="_blank">' . $text . '</a>
      </div>';
	  
	  
	echo '<div class="col-sm-3">';
	
    $text = AdminPhrase('users_open_email');
    $href = SELF_USERS_PHP.'?action=display_email_users_form'.SD_URL_TOKEN.'&amp;email_userid='.$user['userid'];
    echo '
        <a class="btn btn-info" href="'.$href.'" target="_blank">' . $text . '</a>';

    $text = AdminPhrase('users_open_external_email');
    $href = 'mailto:'.$user['email'];
    echo '
        <br /><a class="btn btn-info" href="'.$href.'">' . $text . '</a>
        </div>';
   
  }
  
  */

  echo '<h3 class="header blue lighter"> '. (empty($userid) ? AdminPhrase('users_add_user') : AdminPhrase('users_edit_user')) . '</h3>';
  
  echo '
  	<div class="form-group">
  		<label class="control-label col-sm-2" for="nameofuser">' . AdminPhrase('users_username') . '</label>
		<div class="col-sm-6">
			<input type="text" class="form-control" id="nameofuser" maxlength="64" name="nameofuser" value="' . $user['username'] . '" />
		</div>
	</div>';
	
	echo '
  	<div class="form-group">
  		<label class="control-label col-sm-2" for="password">' . AdminPhrase('users_password') . '</label>
		<div class="col-sm-6">
			<input type="password" class="form-control" id="password" maxlength="30" name="password"/>
		</div>
	</div>';
	
	echo '
  	<div class="form-group">
  		<label class="control-label col-sm-2" for="useremail">' . AdminPhrase('users_email') . '</label>
		<div class="col-sm-6">
			<input type="text" class="form-control" id="email" name="useremail" maxlength="320" value="' . $user['email'] . '" />
			<input type="checkbox" class="ace" name="ignore_email" value="1" '.(empty($user['userid'])?'':' checked="checked" ').'/>
			<span class="lbl"> '.AdminPhrase('users_ignore_duplicate_email') . '</span>
		</div>
	</div>';
	
	 echo '
  	<div class="form-group">
  		<label class="control-label col-sm-2" for="">' . AdminPhrase('users_account_status') . '</label>
		<div class="col-sm-6">
			<div class="status_switch spac-10">
        		<input type="hidden" name="activated" value="'.(empty($user['activated'])?'0':'1').'" />
        		<a onclick="return false;" class="status_link on btn btn-success btn-sm"  style="display: '.(!empty($user['activated'])? '': 'none').'">'.AdminPhrase('users_activated') .'</a>
        		<a onclick="return false;" class="status_link off btn btn-danger btn-sm" style="display: '.( empty($user['activated'])? '': 'none').'">'.AdminPhrase('users_not_activated').'</a>
      		</div>
      		'.(empty($user['activated'])?'<strong>&nbsp; '.AdminPhrase('users_validating').'</strong>':'').'
		</div>
	</div>';
	
	echo '
  	<div class="form-group">
  		<label class="control-label col-sm-2" for="">' . AdminPhrase('users_banned') . '
		<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="' . AdminPhrase('users_banned_descr') . '" title="Help">?</span>
		</label>
		<div class="col-sm-6">
			<input type="checkbox" class="ace ace-switch ace-switch-5" id="banned" name="banned" value="1" ' . (!empty($user['banned'])?'checked="checked" ':'') . '/>
			<span class="lbl"></span>
		</div>
	</div>';
	
	echo '
  	<div class="form-group">
  		<label class="control-label col-sm-2" for="usergroupid">' . AdminPhrase('users_usergroup') . '</label>
		<div class="col-sm-6">
				<select class="form-control" name="usergroupid">';

  //SD322: get usergroups for both the primary selection and "other usergroups":
  $user['usergroup_others'] = empty($user['usergroup_others']) ? array() : (array)$user['usergroup_others'];
  $usergroup_options_primary = '';
  $usergroup_options_others = '';
  $getusergroups = $DB->query('SELECT usergroupid, name FROM {usergroups} ORDER BY usergroupid');
  while($usergroup = $DB->fetch_array($getusergroups,null,MYSQL_ASSOC))
  {
    $ug_id = (int)$usergroup['usergroupid'];
    $usergroup_options_primary .= '            <option value="' . $ug_id . '"' .
          ($user['usergroupid'] == $ug_id ? ' selected="selected"' : '') . '>' .
          $usergroup['name'] . "</option>\r\n";
    $usergroup_options_others .= '            <option value="' . $ug_id . '"';
    if(!empty($user['usergroup_others']) && @in_array($ug_id, $user['usergroup_others']))
    {
      $usergroup_options_others .= ' selected="selected"';
    }
    $usergroup_options_others .= '>' . $usergroup['name'] . "</option>\r\n";
  }

  echo $usergroup_options_primary.'
      </select>
		</div>
	</div>';
	
	echo '
  	<div class="form-group">
  		<label class="control-label col-sm-2" for="usergroup_others">' . AdminPhrase('users_other_usergroups') . '
		<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="right" data-content="' . AdminPhrase('Users_other_usergroups_descr') . '" title="Help">?</span>
		</label>
		<div class="col-sm-6">
			<select class="form-control" id="usergroup_others" name="usergroup_others[]" multiple="multiple" size="5">
     			 '.$usergroup_options_others.'
      		</select>
		</div>
	</div>';
  
 
  if(!empty($userid))
  {
    //SD343: fetch session's user IP if no registration IP available
    if(empty($user['register_ip']) &&
       $getips = $DB->query_first('SELECT distinct ipaddress FROM '.PRGM_TABLE_PREFIX.'sessions WHERE userid = %d AND admin = 0'.
                                  ' ORDER BY lastactivity DESC LIMIT 1', $user['userid']))
    {
      $user['register_ip'] = $getips['ipaddress'];
    }
	
	echo '
  	<div class="form-group">
  		<label class="control-label col-sm-2" for="">' . AdminPhrase('users_join_date') . ' / ' . AdminPhrase('users_last_activity_date') . ' / IP</label>
		<div class="col-sm-6">
			<div class="input-group">
			<input type="text" name="joindate" class="form-control date-picker" id="joindate" value="'.date("m/d/Y",$user['joindate']).'">
			<span class="input-group-addon">
				<i class="fa fa-calendar bigger-110"></i>
			</span>
		</div>
		</div>
	</div>';
	
	echo '
	 <div class="form-group">
	 	<label class="control-label col-sm-2" for="lastactivity">' . AdminPhrase('users_last_activity_date') .'</label>
		<div class="col-sm-6">
		<div class="input-group">
			<input type="text" name="lastactivity" class="form-control date-picker" id="lastactivity" value="'.date("m/d/Y", $user['lastactivity']).'">
			<span class="input-group-addon">
				<i class="fa fa-calendar bigger-110"></i>
			</span>
		</div>
		</div>
	</div>';
	
	echo'
	<div class="form-group">
	 	<label class="control-label col-sm-2" for="lastactivity">' . AdminPhrase('users_ip_address') .'</label>
		<div class="col-sm-6">';	
    //SD343: allow editing of registration IP address
    $user['admin_notes'] = isset($user['admin_notes']) ? $user['admin_notes'] : '';
    $user['register_ip'] = isset($user['register_ip']) ? $user['register_ip'] : '';
    echo '<br />
        <input type="text" class="form-control" id="register_ip" name="register_ip" maxlength="32" value="' . $user['register_ip'] . '" />
		</div>
	</div>';
  }
  echo '
  <div class="form-group">
	 	<label class="control-label col-sm-2" for="receive_emails">' . AdminPhrase('users_receive_emails') . '</label>
		<div class="col-sm-6">
      		<input type="checkbox"  class="ace ace-switch ace-switch-5" name="receive_emails" value="1" ' . (!empty($user['receive_emails']) ? 'checked="checked"' : '') .' />
			<span class="lbl"></span>
    	</div>
	</div>';
	
	echo'
	<div class="form-group">
	 	<label class="control-label col-sm-2" for="admin_notes">' . AdminPhrase('users_admin_notes') . '</label>
		<div class="col-sm-6">
      		<textarea  class="form-control" id="admin_notes" name="admin_notes">' . $user['admin_notes'] .'</textarea>
			<span class="helper-text">'. AdminPhrase('users_admin_notes_text') .'</span>
		</div>
	</div>';

  // ***** User Profile Avatar *****
  echo '
  <div class="form-group">
	 	<label class="control-label col-sm-2" for="admin_notes">'.$p11_phrases['page_avatar_title'].'</label>
		<div class="col-sm-6">
      <input type="hidden" name="avatar_disabled" value="'.(isset($user['avatar_disabled'])?$user['avatar_disabled']:0).'" />
      <input type="hidden" name="user_avatar_type" value="'.(isset($user['user_avatar_type'])?$user['user_avatar_type']:0).'" />
      <input type="hidden" name="user_avatar" value="'.(isset($user['user_avatar'])?$user['user_avatar']:'').'" />
      <input type="hidden" name="user_avatar_height" value="'.(isset($user['user_avatar_height'])?$user['user_avatar_height']:0).'" />
      <input type="hidden" name="user_avatar_width" value="'.(isset($user['user_avatar_width'])?$user['user_avatar_width']:0).'" />
    ';

  if(!empty($user) && isset($user['user_avatar_type']) && ($user['user_avatar_type']==1))
  {
    $usergroup_options = SDProfileConfig::$usergroups_config[$userinfo['usergroupid']];
    $avatar_path = isset($usergroup_options['avatar_path'])?(string)$usergroup_options['avatar_path']:'';
    $avatar_path = (!empty($avatar_path) && is_dir(ROOT_PATH.$avatar_path) && is_writable(ROOT_PATH.$avatar_path)) ? $avatar_path : false;
    if($avatar_path !== false)
    {
      $avatar_path .= (substr($avatar_path,-1)=='/'?'':'/').floor($userid / 1000).'/';
      $avatar_path = is_dir(ROOT_PATH.$avatar_path) ? ROOT_PATH.$avatar_path : false;
      if($avatar_path !== false)
      {
        $avatar_w = (int)$user['user_avatar_width'];
        $avatar_h = (int)$user['user_avatar_height'];
        echo '
        <img alt="" border="0" class="avatar" width="'.$avatar_w.'" height="'.$avatar_h.'" src="' . $avatar_path . $user['user_avatar'] . '" />
        <br /><br />
        <input type="checkbox" class="ace" name="delete_avatar" value="1" /><span class="lbl"> '.
        AdminPhrase('users_remove_avatar').'</span><br />';
      }
      else
      {
        echo AdminPhrase('err_no_avatar_uploaded').'<br />';
      }
    }
  }
  else
  {
    $avatar_w = (int)$mainsettings['default_avatar_width'];
    $avatar_h = (int)$mainsettings['default_avatar_height'];
    $avatar_path = GetAvatarPath($user['email'], $userid);
    echo '<img alt="" border="0" class="avatar" width="40" height="40" src="'.$avatar_path . '" /> <br />';
  }

  echo '<input type="checkbox" class="ace" name="disable_avatar" value="1" '.
       (empty($user['avatar_disabled'])?'':'checked="checked" ').'/><span class="lbl"> '.
       AdminPhrase('users_disable_avatar') . '</span>
	  </div>
	 </div>';

  //SD351: admin feature to upload avatar
  echo '
  <div class="form-group">
	 	<label class="control-label col-sm-2" for="avatar_upload">'.$p11_phrases['avatar_new_upload'].'</label>
		<div class="col-sm-6">
    		<input type="file" name="avatar_upload" id="avatar_upload">
		</div>
	</div>';

  // ***** User Profile Picture *****
  echo '
  <div class="form-group">
	 	<label class="control-label col-sm-2" for="avatar_upload">'.$p11_phrases['page_picture_title'].'</label>
		<div class="col-sm-6">
      <input type="hidden" name="profile_img_disabled" value="'.(isset($user['profile_img_disabled'])?$user['profile_img_disabled']:0).'" />
      <input type="hidden" name="profile_img_type" value="'.(isset($user['profile_img_type'])?$user['profile_img_type']:0).'" />
      <input type="hidden" name="user_profile_img" value="'.(isset($user['user_profile_img'])?$user['user_profile_img']:'').'" />
      <input type="hidden" name="profile_img_height" value="'.(isset($user['profile_img_height'])?$user['profile_img_height']:0).'" />
      <input type="hidden" name="profile_img_width" value="'.(isset($user['profile_img_width'])?$user['profile_img_width']:0).'" />
    ';

  $usergroup_options = SDProfileConfig::$usergroups_config[$userinfo['usergroupid']];
  $pub_img_path = isset($usergroup_options['pub_img_path'])?(string)$usergroup_options['pub_img_path']:'';
  $pub_img_path = (!empty($pub_img_path) && is_dir(ROOT_PATH.$pub_img_path) && is_writable(ROOT_PATH.$pub_img_path)) ? $pub_img_path : false;
  if(!empty($user) && isset($user['profile_img_type']) && ($user['profile_img_type']==1))
  {
    if($pub_img_path !== false)
    {
      $pub_img_path .= (substr($pub_img_path,-1)=='/'?'':'/').floor($userid / 1000).'/';
      $pub_img_path = is_dir(ROOT_PATH.$pub_img_path) ? ROOT_PATH.$pub_img_path : false;
      if($pub_img_path !== false)
      {
        $avatar_w = (int)$user['profile_img_width'];
        $avatar_h = (int)$user['profile_img_height'];
        echo '
        <img alt="" border="0" class="avatar" width="'.$avatar_w.'" height="'.$avatar_h.'" src="'.
          $pub_img_path . $user['user_profile_img'] . '" />
        <br /><br />
        <input type="checkbox" class="ace" name="delete_pub_image" value="1" />
		<span class="lbl"> '.
          AdminPhrase('users_remove_pub_image').'</span>';
      }
      else
      {
        echo AdminPhrase('err_no_profile_image_uploaded').'<br />';
      }
    }
  }
  else
  if(!empty($user) && isset($user['profile_img_type']) && ($user['profile_img_type']==2))
  {
    $avatar_w = (int)$mainsettings['default_avatar_width'];
    $avatar_h = (int)$mainsettings['default_avatar_height'];
    echo '<img alt="" border="0" class="avatar" width="40" height="40" src="'.$pub_img_path.'" /> ';
  }

  echo '<label for="disable_pub_image"><input type="checkbox" class="ace" id="disable_pub_image" name="disable_pub_image" value="1" '.
       (empty($user['profile_img_disabled'])?'':'checked="checked" ').'/><span class="lbl"> '.
       AdminPhrase('users_disable_pub_image').'</span>
	 </div>
	</div>';
	
echo '
	<div class="form-group">
	 	<label class="control-label col-sm-2" for="avatar_upload">'.$p11_phrases['picture_new_upload'].'</label>
		<div class="col-sm-6">
		 <input type="file" name="picture_upload" id="picture_upload" />
		</div>
	</div>';

  //SD351: admin feature to upload avatar
   
  $p11_admin = LoadAdminPhrases(2,11,true);
  echo '<h3 class="header blue lighter">'.$p11_admin['users_profile_fields'].'</h3>
  ';

  // Display user profile fields (users_fields/users_data)
  
  $group = '';
  $public_fields = empty($user['public_fields']) ? array() : @explode(',',$user['public_fields']);
  foreach($profile_fields AS $field)
  {
    if(($field['name']!='username') && (substr($field['name'],0,2)!='p_'))
    {
      // Generate articial value for "DisplayProfileInput", if field was marked
      // as publicly visible for member page:
      $field['user_config_public'] = !empty($field['fieldnum']) && empty($field['public_req']) &&
                                     @in_array($field['fieldnum'], $public_fields);
      $UserProfile->DisplayProfileInput($field, true);
    }
  }


  if($userid)
    PrintSubmit('update_user', AdminPhrase('users_update_user'), 'ucpForm', 'fa-check');
  else
    PrintSubmit('insert_user', AdminPhrase('users_add_user'), 'ucpForm', 'fa-check');

  echo '
  </form>';

  //SD360: prevent empty username in JS
  echo '
<script type="text/javascript">
jQuery(document).ready(function() {
  (function($){
    $(".ucp_bbcode").markItUp(myBbcodeSettings);
    $("#ucpForm").validate();

    $("form#ucpForm").submit(function(e){
      tmp_value = $("input#nameofuser").val();
      if(tmp_value.length < 2) {
        e.preventDefault();
        alert("'.addslashes(AdminPhrase('users_enter_username')).'");
        return false;
      }
      return true;
    });

    '.$UserProfile->js_output.'
    ';

  // this part makes sure browsers don't fill out empty fields
  if(!empty($userid))
  {
    echo '
    $("#password").val("");';
  }
  else
  {
    echo '
    $("#email, #nameofuser, #password").val("");
    ';
  }
  echo '
  })(jQuery);
});
</script>
';

} //DisplayUserForm


// ############################################################################
// DISPLAY USER SEARCH FORM
// ############################################################################

function DisplayUserSearchForm()
{
  global $DB;

  $get_user_groups = $DB->query("SELECT usergroupid, name FROM {usergroups} ORDER BY usergroupid ASC");

  echo '
  <form method="post" action="'.SELF_USERS_PHP.'" class="form-horizontal">
  '.PrintSecureToken().'
  <input type="hidden" name="action" value="display_users" />
  <input type="hidden" name="search" value="true" />
  ';

  echo '<h3 class="header blue lighter">' . AdminPhrase('users_search_users') . '</h3>';

  echo '
  		<div class="form-group">
			<label class="control-label col-sm-2 for="usergroupid">' . AdminPhrase('users_usergroup') . '</label>
			<div class="col-sm-6">
			  <select name="usergroupid" class="form-control">
        <option value="0">' . AdminPhrase('users_search_all') . '</option>';

  while($user_group_arr = $DB->fetch_array($get_user_groups,null,MYSQL_ASSOC))
  {
    echo '<option value="' . $user_group_arr['usergroupid'] . '">' . $user_group_arr['name'] . '</option>';
  }

  echo '
      </select>
	  </div>
	 </div>
	<div class="form-group">
			<label class="control-label col-sm-2 for="usergroupid">' . AdminPhrase('users_account_status') . '</label>
			<div class="col-sm-6">
				<select name="status" class="form-control">
        <option value="0">' . AdminPhrase('users_search_all') . '</option>
        <option value="1">' . AdminPhrase('users_activated') . '</option>
        <option value="2">' . AdminPhrase('users_not_activated') . '</option>
        <option value="3">' . AdminPhrase('users_banned') . '</option>
      </select>
   			</div>
		</div>
    
  <div class="form-group">
			<label class="control-label col-sm-2 for="usergroupid">' . AdminPhrase('users_username') . '</label>
			<div class="col-sm-6">
				<input type="text" name="username" value="" class="form-control" />
			</div>
		</div>
 <div class="form-group">
			<label class="control-label col-sm-2 for="usergroupid">' . AdminPhrase('users_email') . '</label>
			<div class="col-sm-6">
				<input type="text" name="email" value="" class="form-control" />
			</div>
		</div>
 <div class="form-group">
			<label class="control-label col-sm-2 for="usergroupid">' . AdminPhrase('users_admin_notes') . '</label>
			<div class="col-sm-6">
				<input type="text" name="admin_notes" value="" class="form-control" />
			</div>
		</div>';

  echo '
  <div class="align-center"><button  class="btn btn-info" type="submit"/><i class="ace-icon fa fa-check"></i> ' . AdminPhrase('users_search_users') . '</button>
 	</div>
  </form>';

} //DisplayUserSearchForm


// ############################################################################
// DISPLAY USERS
// ############################################################################

function UsersProcessStr($str)
{
  global $DB;
  return $DB->escape_string(str_replace(array('&#039;','&#39;','%22'), array("'","'","'"), sd_unhtmlspecialchars($str)));
}

function DisplayUsers()
{
  global $DB, $userinfo, $isajax, $allowed_limits, $search, $searchbar_config_arr;

  $items_per_page = (int)$search['limit'];
  $page = (int)$search['page'];
  $pagination_target = '';
  $filter = false;

  $search_query = '';
  $pagination_target .= '';
/*
*** TEST script to list IP addresses for which multiple users exist (SD342+ only)
SELECT register_ip, count(*)
FROM `sd_users`
WHERE IFNULL(register_ip,'') <> ''
GROUP BY register_ip
HAVING COUNT(*) > 1

*** TEST script to remove inactive users (with no data row) per email-domain:
SELECT *
FROM `sd_users` u
left join sd_users_data d on d.userid = u.userid
WHERE u.email LIKE '%@163.com'
and u.lastactivity >= 0
and d.userid is not null
*/

  $search_username       = &$search['username'];
  $search_namestart      = &$search['namestart']; // for expert bar
  $search_usergroupid    = &$search['usergroupid'];
  $search_email          = &$search['email'];
  $search_account_status = &$search['status'];
  $search_admin_notes    = &$search['admin_notes'];
  $sortby                = &$search['sortby'];
  if($sortby=='register_ip')
    $sortby = 'INET_ATON(u.register_ip)'; //SD343
  else
  if(!empty($sortby))
    $sortby = 'u.'.$sortby;
  else
    $sortby = 'u.joindate';
  $sortorder             = &$search['sortorder'];

  $search_query = '';

  if(!empty($search_usergroupid))
  {
    //SD351: extend usergroup search to secondary groups
    #$search_query .= ' AND u.usergroupid = '.$search_usergroupid;
    $search_query .=
    ' AND ((u.usergroupid = '.$search_usergroupid.') OR '.
          "(u.usergroup_others like '%\"".$search_usergroupid."\"%'))";
  }

  if(!empty($search_account_status))
  {
    switch($search_account_status)
    {
      case 1: // Activated
        $filter = 1;
        $search_query .= ' AND u.activated = 1';
      break;

      case 2: // validating / Not Activated
        $filter = 2;
        $search_query .= ' AND u.activated = 0';
      break;

      case 3: // Banned
        $filter = 3;
        $search_query .= ' AND (u.banned = 1 OR ug.banned = 1)';
      break;
    }
  }

  if(strlen($search_namestart))
  {
    // Special search by non-letters or numbers
    if($search_namestart == 'others')
    {
      $filter = 'others';
      $search_query .= " AND u.username NOT REGEXP(\"^[a-zA-Z]\")";
    }
    else
    {
      $filter = substr($search_namestart,0,1);
      $search_query .= " AND u.username LIKE '".substr($search_namestart,0,1)."%'";
    }
  }

  if(strlen($search_username))
  {
    $search_query .= " AND u.username LIKE '%".UsersProcessStr($search_username)."%'";
  }

  if(strlen($search_email))
  {
    $search_email = UsersProcessStr($search_email);
    $search_query .= " AND ((u.email LIKE '%".$search_email."%') || (u.register_ip LIKE '%".$search_email."%'))";
  }

  if(strlen($search_admin_notes))
  {
    $search_query .= " AND u.admin_notes LIKE '%".UsersProcessStr($search_admin_notes)."%'";
  }

  if(strlen($search_query))
  {
    // remove the first ' AND'
    $search_query = ' WHERE ' . substr($search_query, 4);
  }

  $DB->ignore_error = true;
  $get_total = $DB->query_first('SELECT COUNT(*) usercount FROM {users} u '.
                                ' LEFT JOIN {usergroups} ug ON ug.usergroupid = u.usergroupid '.
                                $search_query);
  $DB->ignore_error = false;
  if($DB->errdesc) echo 'ERROR: '.$DB->errdesc.'<br />';
  $total_rows = empty($get_total['usercount']) ? 0 : (int)$get_total['usercount'];
  unset($get_total);

  //SD342: if "old" page index exceeds the actual number of rows, reset to 0
  $pagestart = ($page-1)*$items_per_page;
  if($pagestart > $total_rows) {
    $page = 1;
    $pagestart = 0;
  }
  $limit = ' LIMIT '.$pagestart.','.$items_per_page;

  $count_allusers = $DB->query_first('SELECT COUNT(*) usercount FROM {users}');
  $count_allusers = empty($count_allusers['usercount']) ? 0 : (int)$count_allusers['usercount'];

  if(!$search)
  {
    $total_rows = $count_allusers;
  }
  $DB->result_type = MYSQL_ASSOC;
  $count_banned = $DB->query_first('SELECT COUNT(DISTINCT u.userid) bancount
                                    FROM {users} u
                                    INNER JOIN {usergroups} ug ON ug.usergroupid = u.usergroupid
                                    WHERE u.banned = 1 or ug.banned = 1');
  $DB->result_type = MYSQL_ASSOC;
  $count_validating = $DB->query_first("SELECT COUNT(*) vcount FROM {users} WHERE IFNULL(activated,0) = 0 AND IFNULL(banned,0) = 0");
  $count_activeusers = $count_allusers - $count_validating['vcount'] - $count_banned['bancount'];
  $sorting = ' ORDER BY '.$sortby.' '.$sortorder;
  $getusers = $DB->query(
  "SELECT u.*, ug.banned ug_banned, ug.name usergroup_name, ug.adminaccess,
    (SELECT COUNT(ses.ipaddress) FROM ".PRGM_TABLE_PREFIX."sessions ses
     WHERE ses.userid = u.userid AND ses.admin = 0 LIMIT 1) count_ips
   FROM ".PRGM_TABLE_PREFIX."users u
   LEFT JOIN ".PRGM_TABLE_PREFIX."usergroups ug ON ug.usergroupid = u.usergroupid
   ". $search_query . $sorting . $limit);

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
  $ug_select_list = '<select id="usergroup_move" name="usergroup_move">';
  $ug_link_list   = '';
  $skip_first     = true;
  foreach($ugroups as $id => $name)
  {
    if(!$skip_first) // skip "---" entry for dialog markup
    {
      $ug_link_list .= '<a class="grouplink" href="'.SELF_USERS_PHP.'?changedusergroupid='.$id.'">'.$name.'</a><br />';
    }
    $skip_first = false;
    $ug_select_list .= '<option value="'.$id.'">'.$name.'</option>';
  }
  $ug_select_list .= '</select>';

  if(!$isajax)
  {
    // "Loading" overlay:
    echo '
    <div id="loader" style="display: none; position: absolute; width: 100%; height: 100%; margin-left: -10000; text-align:center; opacity: .7; filter: alpha(opacity=70); background-color: #F0F0F0;">
    <div style="position: relative; text-align:center; color: #fff; ; opacity: .9; filter: alpha(opacity=100); width: 120px; height: 25px;">
    <i class="ace-icon fa fa-spinner fa-spin blue bigger-250"></i>'.AdminPhrase('users_loading').'</div></div>';

    // "Change Usergroup" dialog content:
    echo '<div id="groupselector" style="display: none">
		<div class="table-header">'.AdminPhrase('users_change_usergroup_descr').'</div>
		<table class="table">
			<tr>
				<td>
      				'.$ug_link_list.'
				</td>
			</tr>
		</table>
	</div>';

    // "Change User Status" dialog content:
    echo '
    <div id="statusselector" style="display: none">
    <form action="'.SELF_USERS_PHP.'" id="userstatuschange" method="post">
	<div class="table-header">'.AdminPhrase('users_change_userstatus_descr').'</div>
	<table class="table">
		<tr class="info">
			<td>
        		<input class="changestatusactivated ace" type="checkbox"  name="activated" value="1" /><span class="lbl"> '.AdminPhrase('users_activated').'</span>&nbsp;
        		<input class="changestatusbanned ace" type="checkbox"   name="banned" value="1" /><span class="lbl"> '.AdminPhrase('users_banned').'</span>
			</td>
		</tr>
		<tr>
		<td>';

    $href = SELF_USERS_PHP.'?action=display_email_users_form&amp;pwdreset=1';
    $text = AdminPhrase('users_send_password_reset_email');
    echo '
        <div class="space-4"></div>
        <a class="pwdresetlink btn btn-xs btn-info btn-white" href="'.$href.'" target="_blank"><i class="ace-icon fa fa-key blue bigger-120"></i> ' . $text . '</a>
        ';

    $href = SELF_USERS_PHP.'?action=email_activation_link';
    $text = AdminPhrase('users_validating_link');
    echo '
        <div class="space-4"></div>
        <a class="sendactivationlink btn btn-xs btn-info btn-white" href="'.$href.'" title="' . $text . '"><i class="ace-icon fa fa-envelope-o blue bigger-120"></i> ' . $text . '</a>';

    $href = SELF_USERS_PHP.'?action=send_email_welcome'; //SD343
    $text = AdminPhrase('users_welcome_message');
    echo '
        <div class="space-4"></div>
        <a class="sendwelcomemessage btn btn-xs btn-info btn-white" href="'.$href.'" title="' . $text . '"><i class="ace-icon fa fa-envelope-o blue bigger-120"></i> ' . $text . '</a>
        ';

    echo '
        <div class="space-8"></div>
		<div class="align-right">
        <a class="statuschangelink btn btn-xs btn-success" href="#"><i class="ace-icon fa fa-check"></i> ' . AdminPhrase('users_update_user') . '</span></span></a>
        </div>
    </td>
	</tr>
	</table>
	</form>
    </div>';

    // ######################################################################
    // CONFIGURE AND DISPLAY USERS FILTER/SEARCH BAR
    // ######################################################################

    $searchbar_config_arr['form'] = array(
    'action'  => SELF_USERS_PHP.'?action=getuserlist',
    'id'      => 'searchusers',
    'method'  => 'post',
    'title'   => AdminPhrase('users_filter_title'),
    'endtag'  => true, // output closing form tag or not?
    'hiddenfields' => array(
      'customsearch'  => '1',
      'clearsearch'   => '0',
      'namestart'     => $search_namestart,
      'page'          => $search['page'],
    ),
    'form_class' => 'searchbar',
    'cell_class' => 'tdrow1',
    'columns' => array(
        'username' => array(
            'title'       => AdminPhrase('users_username'),
            'type'        => 'text',
            'style'       => 'width: 90%;',
            'value'       => $search['username'],
            'size'        => '8',
            'style_cell'  => 'width: 90px;',
            ),
        'usergroupid' => array(
            'title'       => AdminPhrase('users_usergroup'),
            'type'        => 'select',//'lookup',
            'options'     => $ugroups,
            'style'       => 'width: 95%;',
            'value'       => $search['usergroupid'],
            'style_cell'  => 'width: 90px;',
            'lookup'      => array(
                'table'         => 'usergroups',
                'keyfield'      => 'usergroupid',
                'displayfield'  => 'name',
                ),
            ),
        'email' => array(
            'title'       => AdminPhrase('users_email_ip'),
            'type'        => 'text',
            'style'       => 'width: 90%;',
            'value'       => $search['email'],
            'size'        => '8',
            'style_cell'  => 'width: 90px;',
            ),
        'status' => array(
            'title'       => AdminPhrase('users_status'),
            'type'        => 'select',
            'style'       => 'width: 95%;',
            'value'       => $search['status'],
            'options'     => array(
                '---'     => '---',
                '1'       => AdminPhrase('users_activated'),
                '2'       => AdminPhrase('users_not_activated'),
                '3'       => AdminPhrase('users_banned'),
                ),
            'style_cell' => 'width: 80px;',
            ),
        'sortby' => array(
            'title'       => AdminPhrase('users_sort_by'),
            'type'        => 'select',
            'style'       => 'width: 95%;',
            'value'       => $search['sortby'],
            'style_cell'  => 'width: 110px;',
            'options' => array(
                'joindate'      => AdminPhrase('users_join_date'),
                'lastactivity'  => AdminPhrase('users_last_activity'),
                'username'      => AdminPhrase('users_username'),
                'email'         => AdminPhrase('users_email'),
                'activated'     => AdminPhrase('users_activated'),
                'register_ip'   => 'IP',
                ),
            ),
        'sortorder'       => array(
            'title'       => AdminPhrase('users_order'),
            'type'        => 'select',
            'style'       => 'width: 95%;',
            'value'       => $search['sortorder'],
            'style_cell'  => 'width: 90px;',
            'options'     => array(
                'asc'     => AdminPhrase('users_sort_asc'),
                'desc'    => AdminPhrase('users_sort_descending'),
                )
            ),
        'limit' => array(
            'title'       => AdminPhrase('users_limit'),
            'type'        => 'select',
            'style'       => 'width: 100%;',
            'value'       => $search['limit'],
            'style_cell'  => 'width: 50px; max-width: 50px;',
            'options'     => array_combine($allowed_limits,$allowed_limits),
            ),
        'buttons' => array(
            'title'       => AdminPhrase('users_filter'),
            'type'        => 'html',
            'style_cell'  => 'width: 35px; max-width: 40px;',
            'html'        =>
                '<a id="users-submit-search" href="#" onclick="javascript:return false;" title="'.
                  AdminPhrase('users_apply_filter').'">
				  <i class="ace-icon fa fa-search blue bigger-120"></i></a>&nbsp;
				  <a id="users-clear-search" href="#" onclick="javascript:return false;" title="'.
                  AdminPhrase('users_clear_filter').'"><i class="ace-icon fa fa-trash-o red bigger-120"></i></a>',
            ),
      ),
    );
    echo SearchBarOutput($searchbar_config_arr);

    echo '
    <div id="users_container" >
    ';
  }

  // Pagination and filter bar
  echo PrintSecureToken().'
    <div id="filterarea" class="">
		<h3 class="header blue lighter">'.AdminPhrase('users_filter_users').' '.$total_rows. '</h3> 
		<span class="info">' .AdminPhrase('users_filter_filterby').'</span> ';
  if($filter == false && $filter != 'others')
    echo '<button class="current btn btn-white btn-default btn-xs btn-round" disabled><i class="ace-icon fa fa-filter blue bigger-110"></i> '.AdminPhrase('users_filter_allusers').' ('.$count_allusers.')</button>&nbsp;';
  else
    echo '<span><a class="status-link btn btn-white btn-default btn-xs btn-round" href="'.SELF_USERS_PHP.'?status=---"><i class="ace-icon fa fa-filter blue bigger-110"></i> '.AdminPhrase('users_filter_allusers').' ('.$count_allusers.')</a></span>&nbsp;';
  if($filter == 1)
    echo '<button class="current btn btn-white btn-default btn-xs btn-round" disabled><i class="ace-icon fa fa-filter blue bigger-110"></i> '.AdminPhrase('users_filter_activated').' ('.$count_activeusers.')</button>&nbsp;';
  else
    echo '<span><a class="status-link btn btn-white btn-default btn-xs btn-round" href="'.SELF_USERS_PHP.'?status=1"><i class="ace-icon fa fa-filter blue bigger-110"></i> '.AdminPhrase('users_filter_activated').' ('.$count_activeusers.')</a></span>&nbsp;';

  if($filter == 2)
    echo '<button class="current btn btn-white btn-default btn-xs btn-round" disabled><i class="ace-icon fa fa-filter blue bigger-110"></i> '.AdminPhrase('users_filter_validating').' ('.$count_validating['vcount'].')</button>&nbsp;';
  else
    echo '<span><a class="status-link btn btn-white btn-default btn-xs btn-round" href="'.SELF_USERS_PHP.'?status=2"><i class="ace-icon fa fa-filter blue bigger-110"></i> '.AdminPhrase('users_filter_validating').' ('.$count_validating['vcount'].')</a></span>&nbsp;';

  if($filter == 3)
    echo '<button class="current btn btn-white btn-default btn-xs btn-round" disabled><i class="ace-icon fa fa-filter blue bigger-110"></i> '.AdminPhrase('users_filter_banned').' ('.$count_banned['bancount'].')</button>&nbsp;';
  else
    echo '<span><a class="status-link btn btn-white btn-default btn-xs btn-round" href="'.SELF_USERS_PHP.'?status=3"><i class="ace-icon fa fa-filter blue bigger-110"></i> '.AdminPhrase('users_filter_banned').' ('.$count_banned['bancount'].')</a></span>';

  // Alphabetical shortcuts and "other"
  echo '
 <br /> <ul class="pagination">
  ';
  foreach(range('A', 'Z') as $letter)
  {
    if($filter == $letter)
    {
      echo '<li class="active"><a class="letter-link" href="'.SELF_USERS_PHP.'?namestart='.$letter.'" title="'.AdminPhrase('users_letter').' '.
            $letter.'">'.$letter.'</a></li>
      ';
    }
    else
    {
      echo '<li><a class="letter-link" href="'.SELF_USERS_PHP.'?namestart='.$letter.'" title="'.AdminPhrase('users_letter').' '.
            $letter.'">'.$letter.'</a></li>
      ';
    }
  }

  if($filter == 'others')
    echo '<li class="active"><a href="#">?</a></li>';
  else
    echo '<li><a class="letter-link" title="'.AdminPhrase('users_filter_others').'" href="'.SELF_USERS_PHP.'?namestart=others">?</a></li>';

  echo '</ul></div>'; // FilterContainer-div
  
  // Pagination
  if($total_rows)
  {
    echo '<div id="pagesarea" class="pagination" style="margin:0;">';
    $p = new pagination;
    $p->items($total_rows);
    $p->limit($items_per_page);
    $p->currentPage($page);
    $p->adjacents(7);
    $p->target($pagination_target);
    $p->show();
    echo '</div>';
  }

  

  // ############################### DISPLAY USERS ############################

  echo '
  <form id="userlist" action="'.SELF_USERS_PHP.'" method="post">
  '.PrintSecureToken().'
  <input type="hidden" name="action" value="update_users" />
  <input type="hidden" name="page" value="'.$page.'" />';

  StartTable(AdminPhrase('users_users'), array('table', 'table-bordered', 'table-striped'));

  echo '
  <thead>
  <tr>
    <th>' . AdminPhrase('users_view_user') . '</th>
    <th>' . AdminPhrase('users_usergroup') . '</th>
    <th>' . AdminPhrase('users_email') . '</th>
    <th width="90">IP</th>
    <th class="center">' . AdminPhrase('users_status') . '</th>
    <th>' . ($sortby=='lastactivity'?AdminPhrase('users_last_activity'):AdminPhrase('users_join_date')) . '</th>
    <th width="30" class="center">
      <a id="checkall" rel="0" title="'.htmlspecialchars(AdminPhrase('users_check_all'),ENT_COMPAT).
      '" href="#" onclick="javascript:return false;"><i class="ace-icon fa fa-trash-o red bigger-120"></i></a></td>
  </tr>
  </thead>
  <tbody>';

  //#####################################
  // Loop through all user rows
  //#####################################
  while($user = $DB->fetch_array($getusers,null,MYSQL_ASSOC))
  {
    if($userinfo['userid'] != $user['userid'])
    {
      $delete_user_link = '<input class="usercheckbox ace" type="checkbox" name="userids[]" value="' . $user['userid'] . '" /><span class="lbl"></span>';
    }
    else
    {
      $delete_user_link = '<i class="ace-icon fa fa-minus-circle red bigger-120"></i>';
    }

    // Prepare special color tag for usergroup
    $ug_tag = false;
    if(!empty($user['adminaccess']))
    {
      $ug_tag = '<span class="blue">';
    }
    else
    if(!empty($user['ug_banned']))
    {
      $ug_tag = '<span class="red">';
    }

    $reg_ip = !empty($user['register_ip']) ? (string)$user['register_ip'] : '';
    $ipaddress = $reg_ip ? '<a href="#" class="imgtools hostname" title="IP Tools"><i class="ace-icon fa fa-wrench blue bigger-110"></i> '.$user['register_ip'].'</a><br />' : '';
    if(!empty($user['count_ips']))
    {
      if($getips = $DB->query('SELECT distinct ipaddress FROM '.PRGM_TABLE_PREFIX.'sessions WHERE userid = %d AND admin = 0'.
                              ' ORDER BY lastactivity DESC LIMIT 3', $user['userid']))
      {
        while($getip=$DB->fetch_array($getips,null,MYSQL_ASSOC))
        {
          if($reg_ip != $getip['ipaddress'])
            $ipaddress .= '<a href="#" class="imgtools hostname" title="IP Tools"><i class="ace-icon fa fa-wrench blue bigger-110"></i>  '.$getip['ipaddress'].'</a><br />';
        }
      }
	  
	
    }

    $ahref = 'usergroups.php?action=display_usergroup_form&amp;usergroupid=' . $user['usergroupid'];
    echo '<tr ' . (!empty($user['ug_banned']) ? 'class="danger"' : '' ) .'>'.
      '<td><a class="token imgedit" href="'.SELF_USERS_PHP.'?action=display_user_form&amp;userid='.$user['userid'].'"><i class="ace-icon fa fa-user blue bigger-110"></i>&nbsp;' . $user['username'].'</a></td>'.
      '<td><a class="token ugtitle imgedit" href="' . $ahref . '">&nbsp;</a>'.
      '<a href="#" rel="'.$user['userid'].'" onclick="javascript:;" class="ug_link"><i class="ace-icon fa fa-group blue bigger-110"></i>&nbsp;' . ($ug_tag ? $ug_tag : '').$user['usergroup_name'] . ($ug_tag ? '</span>' : '').
      '</a>';
    //SD351: display secondary usergroups
    if(!empty($user['usergroup_others']) && (substr($user['usergroup_others'],0,2)=='a:'))
    {
      $GLOBALS['sd_ignore_watchdog'] = true;
	  echo '<ul class="list-unstyled">';
		
      if(!empty($user['usergroup_others']) &&
         (($usergroup_others = @unserialize($user['usergroup_others'])) !== false))
      {
        if(is_array($usergroup_others))
        {
          foreach($usergroup_others as $key => $val)
          {
            echo '<li class="text-success smaller-75"><i class="ace-icon fa fa-plus green"></i>' . $ugroups[$val] . '</li>';
          }
        }
      }
	  echo '</ul>';
      $GLOBALS['sd_ignore_watchdog'] = false;
    }
    echo '</td>'.
      '<td><a href="mailto:' . $user['email'] . '" title="'.AdminPhrase('users_link_send_email_user').'"><i class="ace-icon fa fa-envelope-o blue bigger-110"></i> ' . $user['email'] . '</a></td>'.
      '<td class="align-left" width="140">'.$ipaddress.'</td>'.
      '<td class="center">'.GetUserStatusLink($user).'</td>'.
      '<td style="width: 100px; white-space: nowrap;">';
    $out = '';
    $dt = 'Y-m-d G:i';
    if($sortby=='lastactivity')
    {
      $out = DisplayDate($user['lastactivity'],$dt);
      if(!empty($user['joindate']))
      {
        $out = '<a href="#" onclick="javascript:return false;" title="'.
               AdminPhrase('users_join_date').': '.DisplayDate($user['joindate'],$dt).' - '.EMAIL_CRLF.
               Ago($user['joindate'],99999,$dt,'','').
               '">'.$out.'</a>';
      }
    }
    else
    {
      $out = DisplayDate($user['joindate'],$dt);
      if(!empty($user['lastactivity']))
      {
        $out = '<a href="#" onclick="javascript:return false;" title="'.
               AdminPhrase('users_last_activity').': '.DisplayDate($user['lastactivity'],$dt).' - '.EMAIL_CRLF.
               Ago($user['lastactivity'],99999,$dt,'','').
               '">'.$out.'</a>';
      }
    }
    echo $out . '&nbsp;</td>'.
      '<td class="center" style="width: 30px;">' . $delete_user_link . '</td></tr>';

  } //while

  echo '
  <tbody>
  </table>
  </div>
  <input style="display: none; position: absolute; left: -10000; margin-left: -10000" type="submit" value="Submit" />
  ';

  echo '
  <div style="width: 100%; clear: both; height: 23px; padding: 0px; margin: 0px; ">
    <div id="updateoptions" style="display:inline-block; float: right; text-align: right; padding: 0px; margin: 2px 10px 12px 6px; height: 24px;">
      <a class="btn btn-xs btn-danger" id="updateusers" href="#"><i class="ace-icon fa fa-trash-o"></i> ' . AdminPhrase('users_delete') . '</a>
    </div>
    <div id="moveoptions" style="float: right; text-align: right; padding: 0px; margin: 0px; height: 25px;">
      <a class="btn btn-info btn-xs" id="moveusers" href="#"><i class="ace-icon fa fa-arrow-left bigger"></i> ' . AdminPhrase('users_move_users') . '</a>
      '.$ug_select_list.'
    </div>
  </div>
  </form>
  </div>
 ';

  if(!$isajax) DisplayIPTools('a.hostname'); //SD343

} //DisplayUsers

// ############################################################################

function ViewUserTitles()
{
  global $DB, $categoryid;

  $sorttype = GetVar('sorttype', '', 'string');
  switch($sorttype)
  {
    case 'titlea':
      $order = 'title ASC';
    break;

    case 'titlez':
      $order = 'title DESC';
    break;

    case 'counta':
      $order = 'post_count ASC';
    break;

    case 'countz':
      $order = 'post_count DESC';
    break;

    default:
      $sorttype = 'counta';
      $order = 'post_count ASC';
  }

  $asc = (substr($order, -3) == 'ASC');
  $arrow = $asc ? '&uarr;' : '&darr;';

  echo '<h3 class="header blue lighter">' . AdminPhrase('users_add_title') . '</h3>';
  echo '
  <form method="post" action="'.SELF_USERS_PHP.'" class="form-horizontal">
  <input type="hidden" name="action" value="users_add_title" />
  '.PrintSecureToken().'
  <div class="form-group">
  	<label class="control-label col-sm-2" for="title">'.AdminPhrase('users_add_title_name').'</label>
	<div class="col-sm-6">
      <input type="text" name="title" value="" class="form-control">
	 </div>
	</div>
    <div class="form-group">
  	<label class="control-label col-sm-2" for="title">'.AdminPhrase('users_add_title_post_count').'</label>
	<div class="col-sm-6">
       <input type="text" name="post_count" value="0" class="form-control" maxlength="5" />
	 </div>
	</div>
     <div class="center">
      <button class="btn btn-success" type="submit" value=""/><i class="ace-icon fa fa-plus"></i> '.htmlspecialchars(AdminPhrase('users_add_title'),ENT_COMPAT).'</button>
	 </div>
  </form>
  <div class="space-30"></div>';


  echo '
  <form id="titles-list" method="post" action="'.SELF_USERS_PHP.'">
  <input type="hidden" name="action" value="update_user_titles" />
  <input type="hidden" name="sorttype" value="'.$sorttype.'" />
  '.PrintSecureToken();
  
  StartTable(AdminPhrase('users_titles'), array('table','table-bordered','table-striped'));
 echo'
 	<thead>
  <tr>
    <th ><a href="'.SELF_USERS_PHP.'?action=viewusertitles&amp;sorttype='.($asc?'titlez':'titlea').'">'.AdminPhrase('users_user_title').'</a></th>
    <th class="center"width="100"><a href="'.SELF_USERS_PHP.'?action=viewusertitles&amp;sorttype='.($asc?'countz':'counta').'">'.AdminPhrase('users_title_min_count').'</a></th>
    <th class="center" width="90">'.AdminPhrase('users_delete_user_title').'</th>
  </tr>
  </thead>
  <tbody>';
  $idx = 0;
  $getrows = $DB->query('SELECT * FROM {users_titles} ORDER BY '.$order);
  while($title = $DB->fetch_array($getrows,null,MYSQL_ASSOC))
  {
    $idx++;
    $id = (int)$title['titleid'];
    echo '
    <tr>
      <td>
        <input class="form-control" type="text" name="title_'.$id.'" maxlength="250" value="'.$title['title'].'" />
      </td>
      <td class="center">
        <input type="text" class="form-control" name="postcount_'.$id.'" maxlength="5" size="5" value="'.$title['post_count'].'" />
        </center>
      </td>
      <td class="center">
        <input type="checkbox" class="ace" name="delete_ids[]" value="'.$id.'" /><span class="lbl"></span>
      </td>
    </tr>';
  }

  if(!empty($idx))
  {
    echo '</table></div>
	<div class="center">
        <button class="btn btn-info" type="submit" value=""><i class="ace-icon fa fa-check bigger-120"></i> '.AdminPhrase('users_update_titles').'</button>
	</div>';
  }
  else
  {
    echo '<tr class="info"><td colspan="3" class="center">
      '.AdminPhrase('users_no_titles').'
    </td></tr></table></div>';
  }

  echo '</form>';


} //ViewUserTitles

// ############################################################################

function UsersAddTitle()
{
  global $DB, $sdlanguage, $userinfo, $usersystem;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_USERS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $title = trim(GetVar('title', '', 'text', true, false));
  $count = Is_Valid_Number(GetVar('post_count', 0, 'int', true, false), 0, -1, 9999999);

  if(!empty($title))
  {
    $DB->query("INSERT INTO {users_titles} (title, post_count) VALUES('%s',%d)",
               $title, $count);

    // Update user's title
    SDUserCache::UpdateUserTitle($userinfo['userid']);

    RedirectPage(SELF_USERS_PHP.'?action=view_user_titles',
                 AdminPhrase('users_title_added'));
    return;
  }

  RedirectPage(SELF_USERS_PHP.'?action=view_user_titles',
               AdminPhrase('err_users_title_empty'),1,true);

} //UsersAddTitle

// ############################################################################

function UpdateUserTitles()
{
  global $DB, $sdlanguage, $userinfo, $usersystem;

  if(!CheckFormToken())
  {
    RedirectPage(SELF_USERS_PHP,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $delete_ids = GetVar('delete_ids', array(), 'array', true, false);
  if(is_array($delete_ids) && !empty($delete_ids))
  {
    foreach($delete_ids as $tid)
    {
      if(Is_Valid_Number($tid, 0, 1, 9999999))
      {
        $DB->query('DELETE FROM {users_titles} WHERE titleid = %d',$tid);
      }
    }
  }

  foreach($_POST as $key => $entry)
  {
    if(substr($key,0,6)=='title_')
    {
      if($id = intval(substr($key,6)))
      {
        $DB->query("UPDATE {users_titles} SET title = '%s' WHERE titleid = %d",
                   trim($entry), $id);
      }
    }
    else
    if(substr($key,0,10)=='postcount_')
    {
      if($id = intval(substr($key,10)))
      {
        $DB->query("UPDATE {users_titles} SET post_count = %d WHERE titleid = %d",
                   $entry, $id);
      }
    }
  }

  // Update user's title
  #SDUserCache::UpdateUserTitle($userinfo['userid']);
  SDUserCache::UpdateAllUsersTitles();

  RedirectPage(SELF_USERS_PHP.'?action=view_user_titles',
               AdminPhrase('users_titles_updated'));

} //UpdateUserTitles


// ############################################################################
// SELECT FUNCTION
// ############################################################################

$function_name = str_replace('_', '', $action);

if(($action == 'insert_user') || ($action == 'update_user'))
{
  SaveUser($action);
}
else
if(is_callable($function_name))
{
  call_user_func($function_name);
}
else
{
  DisplayMessage("Invalid function call: $function_name()", true);
}


// ############################################################################
// DISPLAY ADMIN FOOTER
// ############################################################################

DisplayAdminFooter();
