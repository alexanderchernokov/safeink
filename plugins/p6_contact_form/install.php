<?php
if(!defined('IN_PRGM')) return false;

// ########################## PLUGIN DETAILS ##################################
$uniqueid	  =  $pluginid   = 6;
$pluginname   = 'Contact Form';
$version      = '4.2.1';
$pluginpath   = 'p6_contact_form/contactform.php';
$settingspath = 'p6_contact_form/p6_settings.php';
$authorname   = 'subdreamer_web';
$authorlink   = 1;
$pluginsettings = 19; /* view/submit/admin */

// ####################### DETERMINE CURRENT DIRECTORY ########################
$plugin_folder  = sd_GetCurrentFolder(__FILE__);

//############################# VERSION CHECK #################################

if($installtype == 'upgrade')
{
  if(!RequiredVersion(3,6))
  {
    echo $pluginname.': you must upgrade '.PRGM_NAME.' to v3.6+ or above to use this plugin.';
    return false;
  }
}

if(!function_exists('p6_Upgrade330'))
{
function p6_Upgrade330()
{
  global $DB, $uniqueid;

  InsertPluginSetting($uniqueid, 'contact_form_settings', 'User\'s email as sender', 'Use email of logged-in user as email sender:<br />In case the email server rejects emails, please turn this option off.', 'yesno', '0', 10);
  InsertPluginSetting($uniqueid, 'Custom Fields', 'Custom Field 1', 'Label for 1. custom field (hidden if empty):', 'text', '', 10);
  InsertPluginSetting($uniqueid, 'Custom Fields', 'Custom Field 2', 'Label for 2. custom field (hidden if empty):', 'text', '', 20);
  InsertPluginSetting($uniqueid, 'Custom Fields', 'Custom Field 3', 'Label for 3. custom field (hidden if empty):', 'text', '', 30);
  InsertPhrase($uniqueid, 'entry_missing', 'Please enter a value!');

} //p6_Upgrade330
}

if(!function_exists('p6_Upgrade343'))
{
function p6_Upgrade343()
{
  global $DB, $installtype, $uniqueid;

  // Add plugin settings
  $DB->query("UPDATE {adminphrases} SET varname = CONCAT(varname,'r') WHERE pluginid = %d AND varname LIKE '%\_desc'", $uniqueid);
  $DB->query("UPDATE {pluginsettings} SET description = CONCAT(description,'r') WHERE pluginid = %d AND description LIKE '%\_desc'", $uniqueid);
}
}

if(!function_exists('p6_Upgrade400'))
{
	function p6_Upgrade400()
	{
		global $DB, $pluginid, $plugin_folder;

		require_once(SD_INCLUDE_PATH.'class_sd_smarty.php');
	
		echo '<b>Adding template to Contact Form plugin ('.$plugin_folder.')...</b>';
	
		$new_tmpl = array('contact_form.tpl' => array('Contact Form', 'Contact Form Template'));
		$tpl_path = SD_INCLUDE_PATH.'tmpl/';
		$tpl_path_def = SD_INCLUDE_PATH.'tmpl/defaults/';
	
		// Loop to add template(s):
		foreach($new_tmpl as $tpl_name => $tpl_data)
		{
		  // Do not create duplicate
		  if(false !== SD_Smarty::TemplateExistsInDB($pluginid, $tpl_name))
		  {
			continue;
		  }
		  if(false !== SD_Smarty::CreateTemplateFromFile($pluginid, $tpl_path_def, $tpl_name,
														 $tpl_data[0], $tpl_data[1]))
		  {
			// Add existing, custom template as newest revision, which would
			// would override the default one:
			if(is_file($tpl_path.$tpl_name) && is_readable($tpl_path.$tpl_name))
			{
			  SD_Smarty::AddTemplateRevisionFromFile($pluginid, $tpl_path, $tpl_name, $tpl_data[1], true);
			}
		  }
		}
		echo '<br /><b>Done.</b><br />';
	}
}


if(!function_exists('p6_Upgrade420'))
{
	function p6_Upgrade420()
	{
		global $DB, $pluginid, $plugin_folder;
		
		if(!RequiredVersion(4,0))
  		{
    		echo $pluginname.': you must upgrade '.PRGM_NAME.' to v4.0 or above to use this plugin.';
    		return false;
  		}
		
		echo '<b>Upgrading Contact Form plugin ('.$plugin_folder.')...</b>';
		
		$DB->query("CREATE TABLE IF NOT EXISTS {p6_fields} (
		id	INT(11) NOT NULL AUTO_INCREMENT,
		field_name	VARCHAR(50) NOT NULL,
		field_type	int(10) NOT NULL,
		required	INT(11) NOT NULL,
		cssclass		varchar(100),
		displayorder INT(11) NOT NULL,
		PRIMARY KEY(id))");
		
		$DB->query("CREATE TABLE IF NOT EXISTS {p6_submissions} (
		id	INT(11) NOT NULL AUTO_INCREMENT,
		submit_date	VARCHAR(50)	NOT NULL,
		ip_address	VARCHAR(16) NOT NULL,
		user_agent	VARCHAR(150) NOT NULL,
		name		VARCHAR(50),
		email		VARCHAR(100),
		subject		VARCHAR(200),
		message		TEXT		NOT NULL,
		PRIMARY KEY(id))");
		
		$DB->query("INSERT INTO {p6_fields} (id, field_name, field_type, required, cssclass, displayorder) VALUES(1,'Full Name', '1', 1, 'form-control', 10)");
		$DB->query("INSERT INTO {p6_fields} (id, field_name, field_type, required, cssclass, displayorder) VALUES(2,'Email', '2', 1, 'form-control', 20)");
		$DB->query("INSERT INTO {p6_fields} (id, field_name, field_type, required, cssclass, displayorder) VALUES(3,'Subject', '1', 1, 'form-control', 30)");
		$DB->query("INSERT INTO {p6_fields} (id, field_name, field_type, required, cssclass, displayorder) VALUES(4,'Message', '3', 1, 'form-control', 40)");
		
		echo 'Inserting Plugin Settings';
		InsertPluginSetting($pluginid, 'contact_form_settings', 'Log Entries in Database', 'Log all submitted contact form entries in the database', 'yesno', '1', 100);
		InsertPluginSetting($pluginid, 'Styles', 'CSS Error Class', 'Class for error message &lt;div&gt; that is displayed above form', 'text', 'alert alert-danger', 10);
		InsertPluginSetting($pluginid, 'Styles', 'CSS Success Class', 'Class for success message &lt;div&gt; that is displayed above form', 'text', 'alert alert-success', 20);
		
		InsertAdminPhrase($pluginid, 'email_address', 'Recipient Email Address(es)','',true);
		InsertAdminPhrase($pluginid, 'email_address_descr', 'Enter the email address where the submitted contact forms will be sent. Separate multiple addresses with commas.','',true);
		InsertAdminPhrase($pluginid, 'contact_form_paragraph', 'Contact Form Header','',true);
		InsertAdminPhrase($pluginid, 'contact_form_paragraph_desc', 'Enter text that will appear at the top of your contact form','',true);
		
		InsertAdminPhrase($pluginid, 'form_fields', 'Contact Form Fields');
		InsertAdminPhrase($pluginid, 'plugin_settings', 'Plugin Settings');
		InsertAdminPhrase($pluginid, 'new_field', 'New Form Field');
		InsertAdminPhrase($pluginid, 'field_name', 'Field Name');
		InsertAdminPhrase($pluginid, 'input_type', 'Input Type');
		InsertAdminPhrase($pluginid, 'display_order', 'Display Order');
		InsertAdminPhrase($pluginid, 'required', 'Required');
		InsertAdminPhrase($pluginid, 'delete', 'Delete');
		InsertAdminPhrase($pluginid, 'field_text', 'Text');
		InsertAdminPhrase($pluginid, 'field_email', 'Email Address');
		InsertAdminPhrase($pluginid, 'field_textarea', 'Textarea');
		InsertAdminPhrase($pluginid, 'field_yesno', 'Yes/No');
		InsertAdminPhrase($pluginid, 'field_checkbox', 'Checkbox');
		InsertAdminPhrase($pluginid, 'field_url', 'URL');
		InsertAdminPhrase($pluginid, 'field_required', 'Required');
		InsertAdminPhrase($pluginid, 'close', 'Close');
		InsertAdminPhrase($pluginid, 'save', 'Save');
		InsertAdminPhrase($pluginid, 'new_form_field', 'New Contact Form Field');
		InsertAdminPhrase($pluginid, 'error_general_error_occurred', 'There was an error with the submitted form.  Please try again');
		InsertAdminPhrase($pluginid, 'error_duplicate_field', 'A field with that name already exists');
		InsertAdminPhrase($pluginid, 'field_created_successfully', 'Field created successfully');
		InsertAdminPhrase($pluginid, 'css_class', 'CSS Class');
		InsertAdminPhrase($pluginid, 'contact_form_styles', 'Styles');
		InsertAdminPhrase($pluginid, 'confirm_delete_field', 'Are you sure you want to delete this field?');
		InsertAdminPhrase($pluginid, 'confirm_delete_entry', 'Are you sure you want to delete this entry?');
		InsertAdminPhrase($pluginid, 'submissions', 'Form Submissions');
		InsertAdminPhrase($pluginid, 'error_log_entries_not_enabled', 'Logging of contact form entries to the database is currently disabled.  To enable this option set <strong>Log Entries In Database</strong> to yes in the plugin settings menu');
		InsertAdminPhrase($pluginid, 'id', 'ID');
		InsertAdminPhrase($pluginid, 'date', 'Date');
		InsertAdminPhrase($pluginid, 'ip_address', 'IP Address');
		InsertAdminPhrase($pluginid, 'message', 'Message');
		InsertAdminPhrase($pluginid, 'full_name', 'Full Name');
		InsertAdminPhrase($pluginid, 'email', 'Email Address');
		InsertAdminPhrase($pluginid, 'subject', 'Subject');
		
		InsertPhrase($pluginid, 'message_first_line', 'This message was sent through your website at ' . $mainsettings['sdurl'] . ' using the contact form', true);
		InsertPhrase($pluginid, 'contact_form', 'Contact Form');
		InsertPhrase($pluginid, 'error_required_field', '%s is a required field');
		InsertPhrase($pluginid, 'error_invalid_url', '%s is not a valid url');
		InsertPhrase($pluginid, 'error_invalid_email', '%s is not a valid email address');
		
		// move contact form paragraph
		$DB->query("UPDATE {pluginsettings} set displayorder=50 WHERE pluginid=$pluginid AND groupname='contact_form_settings' AND title='contact_form_paragraph'");
		
		// Convert custom fields into new {p6_fields} table
		$customfields = $DB->query("SELECT title, value FROM {pluginsettings} WHERE groupname='Custom Fields' and pluginid=$pluginid");
		
		while($field = $DB->fetch_array($customfields))
		{
			if(strlen($field['value']))
			{
				$DB->query("INSERT INTO {p6_fields} (field_name, field_type, required, cssclass, displayorder) VALUES ('$field[value]','1',0, 'form-control', 0)");
			}
		}
		
		echo '<br /><b>Done.</b><br />';
		
	}

if(!function_exists(p6_Upgrade421))
{
	function p6_Upgrade421()
	{
		global $DB, $pluginid, $plugin_folder;
		
		InsertPhrase($pluginid, 'validate_field_required', 'This field is required');
		InsertPhrase($pluginid, 'validate_maxlength', 'Please enter no more than {0} characters');
		InsertPhrase($pluginid, 'validate_minlength', 'Please enter at least {0} characters');
		InsertPhrase($pluginid, 'validate_rangelength', 'Please enter between {0} and {1} characters');
		InsertPhrase($pluginid, 'validate_email', 'Please enter a valid email address');
		InsertPhrase($pluginid, 'validate_url', 'Please enter a valid URL.');
		InsertPhrase($pluginid, 'validate_date', 'Please enter a valid date');
		InsertPhrase($pluginid, 'validate_number', 'Please enter a valid number');
		InsertPhrase($pluginid, 'validate_digits', 'Please enter only digits.');
		InsertPhrase($pluginid, 'validate_equalto', 'Please enter the same value again.');
		InsertPhrase($pluginid, 'validate_range', 'Please enter a value between {0} and {1}');
		InsertPhrase($pluginid, 'validate_max', 'Please enter a value less than or equal to {0}');
		InsertPhrase($pluginid, 'validate_min', 'Please enter a value greater than or equal to {0}');
		InsertPhrase($pluginid, 'validate_creditcard', 'Please enter a valid credit card number.');
		InsertPhrase($pluginid, 'sending', 'Sending...');
		InsertPhrase($pluginid, 'message_sent', 'Message Sent.');
	}
}
}

// ############################# INSTALL PLUGIN ###############################

if($installtype == 'install')
{
  // not installable!
  return;
}

// ############################# UPGRADE PLUGIN ###############################

if(!empty($currentversion) && ($currentversion != $version) && ($installtype == 'upgrade'))
{
  if($currentversion == '3.2.0')
  {
    p6_Upgrade330();
    UpdatePluginVersion($uniqueid, '3.3.0');
    $currentversion = '3.3.0';
  }

  if($currentversion == '3.3.0')
  {
    UpdatePluginVersion($uniqueid, '3.4.0');
    $currentversion = '3.4.0';
  }

  if($currentversion == '3.4.0')
  {
    p6_Upgrade343();
    UpdatePluginVersion($uniqueid, '3.4.3');
    $currentversion = '3.4.3';
  }

  if($currentversion == '3.4.3')
  {
    UpdatePluginVersion($uniqueid, '3.7.0');
    $currentversion = '3.7.0';
  }
  
  if($currentversion == '3.7.0')
  {
	  p6_Upgrade400();
	  UpdatePluginVersion($uniqueid, '4.0.0');
	  $currentversion = '4.0.0';
  }
  
  if($currentversion == '4.0.0')
  {
	  p6_Upgrade420();
	  UpdatePluginVersion($uniqueid, '4.2.0');
	  $currentversion = '4.2.0';
  }
  
  if($currentversion == '4.2.0')
  {
  	p6_Upgrade421();
  	UpdatePluginVersion($uniqueid, '4.2.1');
  	$currentversion = '4.2.1';
  }
}

// ############################ UNINSTALL PLUGIN ##############################

if($installtype == 'uninstall')
{
  // not uninstallable!
}
