<?php
if(!defined('IN_PRGM'))
{
  // ##########################################################################
  // DEFINE CONSTANTS AND INIT PROGRAM
  // ##########################################################################

  define('IN_PRGM', true);
  define('ROOT_PATH', '../../');

  require(ROOT_PATH . 'includes/init.php');
  if(!Is_Ajax_Request() || !isset($userinfo) ||
     !defined('SD_FORM_TOKEN') || !CheckFormToken(null, false)) // in functions_security.php
  {
    echo 'Sorry, your request failed!';
    exit();
  }
}


// Quick check for plugin compatibility
$version = $DB->query_first("SELECT version FROM {plugins} WHERE pluginid=6");
if(version_compare($version['version'], '4.2.0', '<'))
{
	echo 'Please upgrade this plugin in the Administrator Control Panel first.';
	return;
}





/**
* Initializes the SD Smarty Template Engine
*
* @param $settings plugin settings
* @param $language plugin language
*/
function p6_InitTemplate($settings, $language)
{
	global $categoryid, $mainsettings_modrewrite, $sdlanguage, $userinfo;
	
	// Instantiate smarty object
	$_smarty = SD_Smarty::getNew(true); //SD370: 1st param must be "true"
	$_smarty->assign('AdminAccess',   ($userinfo['adminaccess'] || (!empty($userinfo['pluginadminids']) &&
											@array_key_exists($this->pluginid,array_flip($userinfo['pluginadminids'])))));
	$_smarty->assign('categoryid',    $categoryid);
	$_smarty->assign('language',      $language);
	$_smarty->assign('settings', $settings);
	
	return $_smarty;
} //p6_InitTemplate


// ############################################################################
// SEND EMAIL
// ############################################################################

function p6_CleanDetail(& $input)
{
  $result = true;
  $input  = sd_unhtmlspecialchars($input);
  if(DetectSQLInjection($input) || DetectXSSinjection($input))
  {
    $result = false;
  }
  $input = strip_alltags($input);
  return $result;
}


/*
* Emails contact form
*/
function p6_SendContactForm()
{
  global $DB, $sdlanguage, $sd_modules, $categoryid, $mainsettings,
         $userinfo, $usersystem, $p12_settings, $p6_language, $p6_settings;
		 

  $errors_arr = array();
  $emailfields = array();
  
  // Get all fields
  $fields = $DB->query("SELECT * FROM {p6_fields} ORDER BY displayorder ASC");
  
  while($field = $DB->fetch_array($fields))
  {
	  
	$emailfields[$field['field_name']] = GetVar(FormattedFieldName($field['field_name']), '' , 'string');
	
	// For DB Entry
	if($field['id'] == 1)
	{
		$dbfullname = $emailfields[$field['field_name']];
	}
	
	if($field['id'] == 2)
	{
		$dbemailaddr = $emailfields[$field['field_name']];
	}
	
	if($field['id'] == 3)
	{
		$dbsubject = $emailfields[$field['field_name']];
	}

	// Validate
	if($field['required'] && !strlen(end($emailfields)))
	{
		$errors_arr[] = sprintf($p6_language['error_required_field'], $feild['field_name']);
	}
	
	
	switch($field['input_type'])
	{
		case 2: // email address
			if(filter_var(end($emailfields), FILTER_VALIDATE_EMAIL) && strlen(end($emailfields)))
			{
				$errors_arr[] = sprintf($p6_language['error_invalid_email'], $field['field_name']);
			}
		break;
		
		case 6: // URL
			if(filter_var(end($emailfields), FILTER_VALIDATE_URL) && strlen(end($emailfields)))
			{
				$errors_arr[] = sprintf($p6_language['error_invalid_url'], $field['field_name']);
			}
		break;
		
		default:
			// nothing
		break;
	}
  }
  

  $p6_honeypot  = GetVar($p6_honeypot_hash, '', 'string',true,false);
  $details_ok = true;

  //SD343: only allow Contact Form if:
  // a) Javascript is enabled
  // b) honeypot is empty (spam trap)
  // c) if enabled, also the bad behavior screener is present
  if(empty($_POST['js']) || (!empty($p6_honeypot) || strlen($p6_honeypot)) ||
     (isset($sd_modules) && !empty($sd_modules->Modules['bad-behavior']['enabled']) && empty($_POST['bb2_screener_'])))
  {
    $details_ok = false;
    
	if(!Is_Ajax_Request() && (!empty($p6_honeypot) || strlen($p6_honeypot)))
    {
      WatchDog('Contact Form',$sdlanguage['msg_spam_trap_triggered'].
               ' IP: <span class="ipaddress">'.USERIP.'</span>, Email: '.$useremail,
               WATCHDOG_NOTICE);
    }
  }
  else
  {
    //SD343: ip and email ban checks
    $details_ok = !IsIPBanned(USERIP) && !sd_IsEmailBanned($useremail);
  }

  //SD343: SFS checking user's email and IP (optional)
  $sfs = false;
  if($details_ok && !empty($p6_settings['enable_sfs_antispam']) && function_exists('sd_sfs_is_spam'))
  {
    $sfs = sd_sfs_is_spam($useremail,USERIP);
  }
  
  //SD343: support for several blocklist providers
  $blacklisted = false;
  if(!$sfs && $details_ok && function_exists('sd_reputation_check'))
  {
    $blacklisted = sd_reputation_check(USERIP, 6);
    if($blacklisted !== false)
    {
      $errors_arr[] = trim($sdlanguage['ip_listed_on_blacklist'].' '.USERIP);
      WatchDog('Contact Form','<b>'.$blacklisted.'</b>: IP: <b><span class="ipaddress">'.USERIP.'</span></b>',WATCHDOG_ERROR);
    }
  }

  //SD343: check for rejecting words
  if($details_ok && !$sfs)
  {
    $rej_words = trim($p6_settings['reject_words']);
    $rej_words = preg_replace('/\r+/', "\n", $rej_words);
    $rej_words = preg_replace('/\n\n+/', "\n", $rej_words);
    $rej_words = preg_split('/\n/', $rej_words, -1, PREG_SPLIT_NO_EMPTY);

    if(count($rej_words))
    {
      foreach($rej_words as $word)
      {
        if(stristr($message, $word) !== false)
        {
          $details_ok = false;
          break;
        }
      }
    }
  }

  if($details_ok)
  {
    $details_ok = p6_CleanDetail($useremail);    
  }

  if(!$details_ok)
  {
    $errors_arr[] = $p6_language['invalid_information'];
  }

  if(!CaptchaIsValid('p6'))
  {
    $errors_arr[] = $sdlanguage['captcha_not_valid'];
	
  }

  if(Is_Ajax_Request())
  {
    header('Content-type: application/html; charset='.$mainsettings['charset']);
  }
  else
  {
    sleep(2); # distraction
  }
  
  if(!CheckFormToken())
  {
    $errors_arr[] = $sdlanguage['error_invalid_token'];
  }

  $error = 0;
  if(empty($errors_arr))
  {
    $message = $p6_language['message_first_line'] .EMAIL_CRLF . EMAIL_CRLF;
	
	foreach($emailfields as $key => $value)
	{
		$message .= "<strong>" . $key . "</strong>: " . $value . EMAIL_CRLF . EMAIL_CRLF;
	}
	
    $sendername  = '';
    $senderemail = $p6_settings['email_address'];
    if(!empty($p6_settings['user_s_email_as_sender']))
    {
      $sendername  = $fullname;
      $senderemail = $useremail;
    }
	
    /*
    SD341: if the default email format is NOT HTML and since $message went through "htmlspecialchars",
           we have to apply sd_unhtmlspecialchars to get these characters back:
    Single quote: '   &#039;
    Double quote: "   &quote;
    Ampersand: &      &amp;
    */
    $message .= "<strong>IP</strong>: ".USERIP."\r\n"; //SD343: include user ip in body
	
    if(empty($mainsettings['default_email_format']) || ($mainsettings['default_email_format']!=='HTML'))
    {
      //SD343: strip all tags and check content (for security reasons)
      $message = strip_alltags(sd_unhtmlspecialchars($message));
      if(function_exists('DetectXSSinjection') && DetectXSSinjection(unhtmlspecialchars($message)))
      {
        $message = '';
      }
    }
    $emailsent = false;
    if(!empty($message))
    {
      $subject = "[$mainsettings[websitetitle] " . $p6_language['contact_form'] . "] " . sd_unhtmlspecialchars($emailfields['Subject']);
      //SD370: added $useremail for new param "Reply-To" email
      
	    $emailsent = SendEmail($p6_settings['email_address'], $subject, $message,
                             $sendername, $senderemail,
                             null, null, null, null, $useremail);
							 
		// Log to database?
		if($p6_settings['log_entries_in_database'])
		{
			$DB->query("INSERT INTO {p6_submissions} (submit_date, ip_address, user_agent, name, email, subject, message)
						VALUES('".time()."', '".USERIP."', '".USER_AGENT."', '$dbfullname','$dbemailaddr','$dbsubject','$message')");
		}
    }

    if($emailsent)
    {
      if(Is_Ajax_Request())
      {
        $response = $p6_language['email_sent'];
      }
      else
      {
        RedirectFrontPage('', $p6_language['email_sent']);
      }
    }
    else
    {
      if(Is_Ajax_Request())
      {
        $error = 1;
        $response = $p6_language['email_not_sent'];
      }
      else
      {
        RedirectFrontPage('', $p6_language['email_not_sent'], true);
      }
    }
  }
  else
  {
    if(Is_Ajax_Request())
    {
      $error = 1;
      $response = implode("<br />", $errors_arr);
	 
    }
    else
    {
      p6_DisplayContactForm($errors_arr);  // 1 = errors exist
    }
  }


  if(Is_Ajax_Request())
  {
    echo $error . ':' . $response ;
  }

} //p6_SendContactForm


/**
* DISPLAY EMAIL FORM
*
* @param $errors_arr errors
*/
function p6_DisplayContactForm($errors_arr = null)
{
	global $DB, $categoryid, $mainsettings, $userinfo, $sdlanguage,$p6_pluginid, $p6_language, $p6_settings, $p6_smarty;

	$loggedin = !empty($userinfo['loggedin']);
	$form_fields = array();
	$templatevar = array();
  
	$fields = $DB->query("SELECT * FROM {p6_fields} ORDER BY displayorder ASC");
  
	while($field = $DB->fetch_array($fields))
	{
	 $formattedname = FormattedFieldName($field['field_name']);
	 switch ($field['field_type'])
		{
			case 1:	// Text
			case 7: // Number
			case 8: // Date
				$input = '<input type="text" name="'.$formattedname.'" id="'.$formattedname.'" class="'.$field['cssclass'].'" />';
			break;
			
			case 2: // email
				$input = '<input type="text" name="'.$formattedname.'" id="'.$formattedname.'" class="'.$field['cssclass'].'" />';
			break;
			
			case 3: // Textarea
				$input = '<textarea name="'.$formattedname.'" id="'.$formattedname.'" class="'.$field['cssclass'].'" cols="10" rows="5"></textarea>'; 
			break;
			
			case 6: // URL
				$input = '<input type="text" name="'.$formattedname.'" id="'.$formattedname.'" class="'.$field['cssclass'].'" />';
			break;
			
			default:
				$input = '<input type="text" name="'.$formattedname.'" id="'.$formattedname.'" class="'.$field['cssclass'].'" />';
			break;
		}
		
	 $templatevar[] = array('formattedname'	=> $formattedname,
							'displayname'	=>	$field['field_name'],
							'input'			=>	$input);
							
	}
	
  
	$p6_smarty->assign('formfields', $templatevar);
	$p6_smarty->assign('email', $email);
	$p6_smarty->assign('errors_arr', $errors_arr);
	
	if(strlen($p6_settings['contact_form_paragraph']))
	{
		$p6_smarty->assign('contact_form_paragraph', $p6_settings['contact_form_paragraph']);
	}
	
  
  
	//SD343: insert invisible "honeypot" input field in between
	// the regular form fields to randomize the fields' order and
	// thus throw off spammers/bots if
	if(function_exists('mt_rand'))
	{ 
		$idx = mt_rand (1,7);
	}
	else
	{
		$idx = rand(1,7);
	}
	
	$filler = str_repeat('raquo;',$idx);
	$honeypot = '<p class="antispam">Leave this empty: <input type="text" name="antispam" /></p>';
	
	$p6_smarty->assign('captcha', DisplayCaptcha(false,'p6'));
	$p6_smarty->assign('secure_token', PrintSecureToken());
	$p6_smarty->assign('honeypot', $honeypot);
	
	
	// BIND AND DISPLAY TEMPLATE NOW
    // Check if custom version exists, otherwise use default template:
    $err_msg = '<br /><b>'.$plugin_names[$p6_pluginid].' ('.$p6_pluginid.') template file NOT FOUND!</b><br />';
    
	if(defined('SD_SMARTY_VERSION') && (SD_SMARTY_VERSION > 2)) //SD344
    {
      $error = !SD_Smarty::display($p6_pluginid, 'contact_form.tpl', $p6_smarty);
      if($error && !empty($userinfo['adminaccess']))
      {
        $err = SD_Smarty::getLastError();
        echo $err.$err_msg;
      }
    }
    else
    {
		if(is_file(SD_INCLUDE_PATH.'tmpl/contact_form.tpl')) //SD343
		{
			$p6_smarty->display('contact_form.tpl');
		}
		elseif(is_file(SD_INCLUDE_PATH.'tmpl/defaults/contact_form.tpl'))
		{
			$p6_smarty->setTemplateDir(SD_INCLUDE_PATH.'tmpl/defaults/');
			$p6_smarty->display('contact_form.tpl');
		}
		else
		{
		  if($userinfo['adminaccess']) echo 'error';
		}
	}

} //p6_DisplayContactForm


/**
* Builds the input field for the contact form
*/
function BuildFormField($field)
{
	switch ($field['field_type'])
	{
		case 1:	// Text
			//$return = '<input type="text" name="'.$field['formattedname'].'" id="'.$field['formattedname'].'" class="" />';
		break;
		
		case 2: // email
			//$return = '<input type="text" name="'.$field['formattedname'].'" id="'.$field['formattedname'].'" class="" />';
		break;
		
		case 3: // Textarea
			//$return = '<textara name="'.$field['formattedname'].'" id="'.$field['formattedname'].'" class="" cols="10" rows="5"></textarea>'; 
		break;
		
		case 6: // URL
			//$return = '<input type="text" name="'.$field['formattedname'].'" id="'.$field['formattedname'].'" class="" />';
		break;
		
	}	
	
	return $return;
}

/**
* Formats a field name
*/
function FormattedFieldName($field)
{
	return 'field_' . str_replace(" ", "_", strtolower($field));
}


// ############################################################################
// CONTACT FORM FUNCTIONS
// ############################################################################
$p6_pluginid = 6;
$p6_language = GetLanguage($p6_pluginid);
$p6_settings = GetPluginSettings($p6_pluginid);
$p6_action   = GetVar('p6_action', '', 'string');
$p6_smarty	 = p6_InitTemplate($p6_settings, $p6_language);

$p6_cansubmit = !empty($userinfo['adminaccess']) ||
                (!empty($userinfo['pluginadminids'])  && @in_array($p6_pluginid, $userinfo['pluginadminids'])) ||
                (!empty($userinfo['pluginsubmitids']) && @in_array($p6_pluginid, $userinfo['pluginsubmitids']));

if($p6_cansubmit && !SD_IS_BOT)
{
  if($p6_action == 'sendemail')
  {
    p6_SendContactForm();
  }
  else
  {
    p6_DisplayContactForm();
  }
}
else
{
  echo $sdlanguage['no_post_access'];
}

unset($p6_cansubmit, $p6_action, $p6_language, $p6_settings);
