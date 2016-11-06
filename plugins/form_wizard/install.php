<?php
if(!defined('IN_PRGM')) return;

// ############################################################################
// PLUGIN INFORMATION
// ############################################################################
$plugin_folder  = sd_GetCurrentFolder(__FILE__);
$pluginname     = 'Form Wizard';
$version        = '1.3.2';
$pluginpath     = $plugin_folder.'/form_wizard.php';
$settingspath   = $plugin_folder.'/settings.php';
$authorname     = 'subdreamer_web';
$authorlink     = 'http://www.subdreamer.com';
$pluginsettings = 27; // !!!

/* ######################### PRE-CHECK FOR CLONING ############################
   If no install type is set, then SD is scanning for new plugins.
   This is the right time to pre-check, if a plugin with the same name within
   a different folder has already been installed.
*/

// 1. Check if this plugin is already installed from within the current folder:
// Only *IF* it is installed, set the plugin id so that it is treated
// as installed and does not show up for installation again
if($inst_check_id = GetPluginIDbyFolder($plugin_folder))
{
  $pluginid = $inst_check_id;
}
else
// 2. Check if a plugin with the same name is already installed
if($inst_check_id = GetPluginID($pluginname))
{
  $pluginname .= ' ('.$plugin_folder.')';
}
$authorname .= "<br />Plugin folder: '<b>plugins/$plugin_folder</b>'";

if(empty($installtype))
{
  // Nothing else to do, so return
  return true;
}

if(!function_exists('p_FormWizard_CheckPhrases132'))
{
  function p_FormWizard_CheckPhrases132()
  {
    global $pluginid;

    // New Admin Phrase: Export to Word
    InsertAdminPhrase($pluginid, 'default_sender', '(Default)');
    InsertAdminPhrase($pluginid, 'sender_email', 'Sender Email');
    InsertAdminPhrase($pluginid, 'sender_email_hint', 'If applicable, select a different '.
      'recipient whose email address is being used as the email sender.<br />'.
      'If this is left empty, then the email of the selected recipient will be used (default).<br />'.
      'Note: this option is only used if the <strong>User Email as Sender</strong> plugin option is set to <strong>No</strong>.'
      );

    // Sections
    InsertAdminPhrase($pluginid, 'section_select_values', 'Option Values');
    InsertAdminPhrase($pluginid, 'section_update_field', 'Update Field');
    InsertAdminPhrase($pluginid, 'section_add_field', 'Add Field');
    InsertAdminPhrase($pluginid, 'section_fields_for_form', 'Fields For Form');
    InsertAdminPhrase($pluginid, 'section_form_operations', 'Form Operations');
    InsertAdminPhrase($pluginid, 'section_update_form', 'Update Form');
    InsertAdminPhrase($pluginid, 'section_create_form', 'Create New Form');
    InsertAdminPhrase($pluginid, 'section_all_forms', 'All Forms');
    InsertAdminPhrase($pluginid, 'section_pages_for_form', 'Pages For Form');
    InsertAdminPhrase($pluginid, 'section_update_recipient', 'Update Recipient');
    InsertAdminPhrase($pluginid, 'section_create_recipient', 'Create New Recipient');
    InsertAdminPhrase($pluginid, 'section_recipients', 'Recipients');
    InsertAdminPhrase($pluginid, 'section_view_responses', 'View Responses');
    InsertAdminPhrase($pluginid, 'section_form_responses', 'Form Responses');
    InsertAdminPhrase($pluginid, 'section_responses_for_form', 'Responses for Form',2,true);
    InsertAdminPhrase($pluginid, 'section_how_to', 'How To Use');

    // Error Messages
    InsertAdminPhrase($pluginid, 'err_opt_value', 'Error: Invalid value specified.');
    InsertAdminPhrase($pluginid, 'err_opt_exists', 'Error: You have already added this value to the selected field.');
    InsertAdminPhrase($pluginid, 'err_field_no_name', 'Error: The field must have a name.');
    InsertAdminPhrase($pluginid, 'err_field_dup_email', 'Only one Email Address field allowed per form.');
    InsertAdminPhrase($pluginid, 'err_param', 'Parameter Error!');
    InsertAdminPhrase($pluginid, 'err_no_form_name', 'You must enter a name to create your form.');
    InsertAdminPhrase($pluginid, 'err_no_submit_value', 'You must enter a value for the submit text to create your form.');
    InsertAdminPhrase($pluginid, 'err_no_recipient', 'You must select at least one recipient to submit by email.',2,true);
    InsertAdminPhrase($pluginid, 'err_no_formid', 'Invalid or no form specified!');
    InsertAdminPhrase($pluginid, 'err_cat_prev_added', 'Error: this form was already added to the selected page!');
    InsertAdminPhrase($pluginid, 'err_invalid_email', 'Please enter a valid email address');

    // Form Configuration
    InsertAdminPhrase($pluginid, 'send_email', 'E-Mail');
    InsertAdminPhrase($pluginid, 'send_db', 'Database');
    InsertAdminPhrase($pluginid, 'send_db_and_email', 'E-Mail and Database');
    InsertAdminPhrase($pluginid, 'recipients_needed_to_complete', 'Forms cannot be created until recipients have been added!',2,true);
    InsertAdminPhrase($pluginid, 'disp_pages', 'Pages to display form',2,true);
    InsertAdminPhrase($pluginid, 'disp_fields', 'Form Fields',2,true);
    InsertAdminPhrase($pluginid, 'disp_responses', 'Display Responses');
    InsertAdminPhrase($pluginid, 'form_details', 'Form Details');
    InsertAdminPhrase($pluginid, 'form_name', 'Form Name');
    InsertAdminPhrase($pluginid, 'form', 'Form');
    InsertAdminPhrase($pluginid, 'submit_text', 'Submit Text');
    InsertAdminPhrase($pluginid, 'submit_but_text', 'Submit Button Text');
    InsertAdminPhrase($pluginid, 'submit_to', 'Submit To');
    InsertAdminPhrase($pluginid, 'active', 'Active');
    InsertAdminPhrase($pluginid, 'delete', 'Delete');
    InsertAdminPhrase($pluginid, 'recipients', 'Recipients');
    InsertAdminPhrase($pluginid, 'recipients_desc', 'Select the email recipients to which the form should be sent to:',2,true);
    InsertAdminPhrase($pluginid, 'recipients_desc_hint', '(use CTRL/Shift+click to select multiple recipients)',2,true);
    InsertAdminPhrase($pluginid, 'recipients_disp', 'Recipients Display');
    InsertAdminPhrase($pluginid, 'recipients_disp_desc', 'How should the recipient details be displayed?');
    InsertAdminPhrase($pluginid, 'disp_name', 'Display Name');
    InsertAdminPhrase($pluginid, 'disp_email', 'Display E-Mail');
    InsertAdminPhrase($pluginid, 'disp_name_and_email', 'Display Name and E-Mail');
    InsertAdminPhrase($pluginid, 'recipient_mode', 'Recipient Mode');
    InsertAdminPhrase($pluginid, 'recipient_mode_desc', 'Should the user be able to select a recipient? Or should it be sent to all recipients for this form?');
    InsertAdminPhrase($pluginid, 'form_text', 'Form Text');
    InsertAdminPhrase($pluginid, 'intro_text', 'Intro Text');
    InsertAdminPhrase($pluginid, 'intro_text_desc', 'This will be displayed above the form, useful for explaining the purpose of filling it out to users.<br /><br />'.
                                 '<b><u>Allowed BBcode:</u></b><br /><br />[b]Text[/b], [i]Text[/i], [u]Text[/u]');
    InsertAdminPhrase($pluginid, 'success_text', 'Success Text');
    InsertAdminPhrase($pluginid, 'success_text_desc', 'This will be displayed when the form has been successfully submitted.<br /><br /><b><u>Allowed BBcode:</u></b><br /><br />[b]Text[/b], [i]Text[/i], [u]Text[/u]');
    InsertAdminPhrase($pluginid, 'disp_form_online', 'Display Form Online?');
    InsertAdminPhrase($pluginid, 'responses', 'Responses');
    InsertAdminPhrase($pluginid, 'view_all_responses', 'View All Responses');
    InsertAdminPhrase($pluginid, 'view_all_responses_desc', 'Click on a form name in the list below to view its responses.');
    InsertAdminPhrase($pluginid, 'add_to_category', 'Add to Page');
    InsertAdminPhrase($pluginid, 'add_to_category_desc', 'Add this form to a page on your website');

    // General
    InsertAdminPhrase($pluginid, 'update_recipient_but', 'Update Recipient');
    InsertAdminPhrase($pluginid, 'create_recipient_but', 'Create Recipient');
    InsertAdminPhrase($pluginid, 'create_form_but', 'Create Form');
    InsertAdminPhrase($pluginid, 'add_value_but', 'Add Value');
    InsertAdminPhrase($pluginid, 'values', 'Values');
    InsertAdminPhrase($pluginid, 'update_form_but', 'Update Form');
    InsertAdminPhrase($pluginid, 'delete_form_but', 'Delete Form');
    InsertAdminPhrase($pluginid, 'create_field_but', 'Add Field');
    InsertAdminPhrase($pluginid, 'update_field_but', 'Update Field');
    InsertAdminPhrase($pluginid, 'delete_but', 'Delete');
    InsertAdminPhrase($pluginid, 'delete_question', 'Delete?');
    InsertAdminPhrase($pluginid, 'category_name', 'Page Name');
    InsertAdminPhrase($pluginid, 'please_add_at_least_one_cat', 'Please add this form to at least one page.');
    InsertAdminPhrase($pluginid, 'please_add_at_least_one_cat_desc', 'Users will not see the form unless it is added to a page.');
    InsertAdminPhrase($pluginid, 'please_add_at_least_one_recipient', 'Please add at least one recipient.');
    InsertAdminPhrase($pluginid, 'create_field_details', 'Details');
    InsertAdminPhrase($pluginid, 'create_field_name', 'Name');
    InsertAdminPhrase($pluginid, 'create_field_label', 'Label');
    InsertAdminPhrase($pluginid, 'create_field_label_desc', 'This will be displayed next to the field to explain it to users',2,true);
    InsertAdminPhrase($pluginid, 'create_field_type', 'Field Type');
    InsertAdminPhrase($pluginid, 'create_field_width', 'Field Width');
    InsertAdminPhrase($pluginid, 'create_field_width_desc', 'This must be a valid non-fractional number.<br />'.
                                 'If it is a Text Box, Text Area or Email Box, only put the number which represents columns.',2,true);
    InsertAdminPhrase($pluginid, 'create_field_height', 'Field Height');
    InsertAdminPhrase($pluginid, 'create_field_height_desc', 'This must be a valid non-fractional number.<br />'.
                                 ' If it is a Text Box, Text Area or Email Box, only put the number which represents rows.',2,true);
    InsertAdminPhrase($pluginid, 'create_field_type_text', 'Text (single line)',2,true);
    InsertAdminPhrase($pluginid, 'create_field_type_textarea', 'Textarea (multi-line)',2,true);
    InsertAdminPhrase($pluginid, 'create_field_type_select', 'Drop-Down Selection',2,true);
    InsertAdminPhrase($pluginid, 'create_field_type_checkbox', 'Check Box (single)',2,true);
    InsertAdminPhrase($pluginid, 'create_field_type_email', 'E-Mail Address');
    InsertAdminPhrase($pluginid, 'create_field_validator_type', 'Validation Method');
    InsertAdminPhrase($pluginid, 'create_field_validator_type_none', 'None');
    InsertAdminPhrase($pluginid, 'create_field_validator_type_empty', 'Not Empty');
    InsertAdminPhrase($pluginid, 'create_field_validator_type_number', 'Valid Number');
    InsertAdminPhrase($pluginid, 'create_field_validator_type_email', 'Valid E-Mail Address');
    InsertAdminPhrase($pluginid, 'create_field_validator_type_url', 'Valid URL');
    InsertAdminPhrase($pluginid, 'create_field_online_question', 'Display Online?');
    InsertAdminPhrase($pluginid, 'create_field_online_yes', 'Yes');
    InsertAdminPhrase($pluginid, 'create_field_online_no', 'No');
    InsertAdminPhrase($pluginid, 'new_form', 'New Form');
    InsertAdminPhrase($pluginid, 'new_form_desc', 'Create a new form with its own fields');
    InsertAdminPhrase($pluginid, 'recipient_email_address', 'E-Mail Address');
    InsertAdminPhrase($pluginid, 'recipient_name', 'Name');
    InsertAdminPhrase($pluginid, 'recipient_details', 'Details');
    InsertAdminPhrase($pluginid, 'create_recipient', 'Create Recipient');
    InsertAdminPhrase($pluginid, 'create_recipient_desc', 'Create a new recipient for receiving form responses',2,true);
    InsertAdminPhrase($pluginid, 'form_values', 'Form Values');
    InsertAdminPhrase($pluginid, 'response_info', 'Response Info');
    InsertAdminPhrase($pluginid, 'submitted_by', 'Submitted By');
    InsertAdminPhrase($pluginid, 'ip_address', 'IP Address');
    InsertAdminPhrase($pluginid, 'date_created', 'Date Created');
    InsertAdminPhrase($pluginid, 'date_submitted', 'Date submitted');
    InsertAdminPhrase($pluginid, 'username', 'Username');
    InsertAdminPhrase($pluginid, 'no_responses_available', 'No responses available',2,true);
    InsertAdminPhrase($pluginid, 'export_to_csv', 'Export to CSV');
    InsertAdminPhrase($pluginid, 'export_to_doc', 'Export to Word');
    InsertAdminPhrase($pluginid, 'add_value', 'Add a Value');
    DeleteAdminPhrase($pluginid, 'add_value_desc');
    InsertAdminPhrase($pluginid, 'how_to_use', 'How To Use Form Wizard');
    InsertAdminPhrase($pluginid, 'how_to_use_desc', 'The Form Wizard plugin allows you to create one or more forms which can be used to submit information via email or directly into your database.<br /><br /> The first step is to create one or multiple recipients in order to start creating forms. Each form can have as many fields as you need (but at least one) and can be reused on multiple pages (if required.)<br /><br /> When you have created your forms, remember to assign your forms to any page in which they shall be displayed. This has to be done in addition to placing the plugin - as usual - to an existing page, otherwise no form will be displayed to the user.',2,true);

    // CREATE LANGUAGE PHRASES
    InsertPhrase($pluginid, 'form_submitted', 'Successfully Sent!');
    InsertPhrase($pluginid, 'is_empty', 'cannot be empty');
    InsertPhrase($pluginid, 'not_date', 'must be a valid date');
    InsertPhrase($pluginid, 'not_number', 'must be a valid number');
    InsertPhrase($pluginid, 'not_whole_number', 'must be a whole, positive number');
    InsertPhrase($pluginid, 'not_integer', 'must be a valid, whole number)');
    InsertPhrase($pluginid, 'not_email', 'must be a valid email address');
    InsertPhrase($pluginid, 'not_url', 'must be a valid URL with http://');
    InsertPhrase($pluginid, 'must_alloc_cat', 'You must place this form on a page.');
    InsertPhrase($pluginid, 'email_subject', 'New Form Submission');
    InsertPhrase($pluginid, 'form_name', 'Results From Form');
    InsertPhrase($pluginid, 'from_username', 'Submitted By');
    InsertPhrase($pluginid, 'from_ip', 'IP Address');
    InsertPhrase($pluginid, 'form_values', 'Form Values');
    InsertPhrase($pluginid, 'errors_have occurred', 'you have one or more errors with your submission:');
    InsertPhrase($pluginid, 'reset', 'Reset');
    InsertPhrase($pluginid, 'recipient', 'Recipient');

    //v1.2.2
    InsertAdminPhrase($pluginid, 'submit_form', 'Submit Form');
    InsertAdminPhrase($pluginid, 'form_submit_success', 'Form successfully submitted.');
    InsertAdminPhrase($pluginid, 'invalid_form_request', 'Invalid Form or Form does not exist in database!');
    InsertAdminPhrase($pluginid, 'recipient_mode_0', 'Allow user to choose a recipient',2,true);
    InsertAdminPhrase($pluginid, 'recipient_mode_1', 'Send to all');
    InsertAdminPhrase($pluginid, 'form_untitled', 'Form (untitled)');
    InsertAdminPhrase($pluginid, 'fields', 'Fields');
    InsertAdminPhrase($pluginid, 'add_new_field_title', 'Add a New Field');
    InsertAdminPhrase($pluginid, 'add_new_field_descr', 'Add a new field to the selected form:');
    InsertAdminPhrase($pluginid, 'add_field', 'Add Field');
    InsertAdminPhrase($pluginid, 'field_list', 'Field List');
    InsertAdminPhrase($pluginid, 'fields_col_field', 'Field');
    InsertAdminPhrase($pluginid, 'fields_col_type', 'Type');
    InsertAdminPhrase($pluginid, 'fields_col_validator', 'Validator');
    InsertAdminPhrase($pluginid, 'fields_col_active', 'Active');
    InsertAdminPhrase($pluginid, 'fields_col_sortorder', 'Sort Order');
    InsertAdminPhrase($pluginid, 'fields_col_date', 'Date Created');
    InsertAdminPhrase($pluginid, 'fields_col_delete', 'Delete?');
    InsertAdminPhrase($pluginid, 'btn_delete', 'Delete');
    InsertAdminPhrase($pluginid, 'please_add_fields', 'Please add Fields to this Form.');
    InsertAdminPhrase($pluginid, 'create', 'Create');
    InsertAdminPhrase($pluginid, 'responses', 'Responses');
    InsertAdminPhrase($pluginid, 'guest', 'Guest');
    InsertAdminPhrase($pluginid, 'settings', 'Settings');

    InsertPhrase($pluginid, 'email_response_id', 'Response ID:');
    InsertPhrase($pluginid, 'email_form_name', 'Form Name:');
    InsertPhrase($pluginid, 'email_username', 'Username:');
    InsertPhrase($pluginid, 'email_ip_address', 'IP Address:');
    InsertPhrase($pluginid, 'email_date', 'Date submitted:');
    InsertPhrase($pluginid, 'email_recipients', 'Recipients:');
    InsertPhrase($pluginid, 'guest', 'Guest');
    InsertPhrase($pluginid, 'msg_no_responses', '<h3>No responses available for export!</h3><p>Use your browser\'s "Back" button to return.</p>');

    //1.2.5
    InsertPhrase($pluginid, 'msg_no_submit', 'Sorry, but there is currently no content available for you.');
    InsertPhrase($pluginid, 'msg_invalid_form', 'Sorry, but the requested form was not found!');

    //1.3.2
    InsertPhrase($pluginid, 'untitled', 'untitled');
    InsertPhrase($pluginid, 'allowed_file_extentions_hint', 'Allowed extentions for uploaded file:');
    InsertPhrase($pluginid, 'allowed_file_size_hint', 'Max. accepted filesize (in KB):',2,true);
    InsertPhrase($pluginid, 'message_js_required', 'Please enable Javascript for registration and reload this page, thank you.');
    DeleteAdminPhrase($pluginid, 'update_form', 'Update Form');
    InsertAdminPhrase($pluginid, 'msg_no_forms_available', 'No forms available yet.');
    InsertAdminPhrase($pluginid, 'display_settings', 'Display Settings');
    InsertAdminPhrase($pluginid, 'view_recipient', 'View Recipient');
    InsertAdminPhrase($pluginid, 'attachment_name', 'Uploaded file:',2,true);
    InsertAdminPhrase($pluginid, 'no_file_uploaded', 'No file was uploaded for this field.');
    InsertAdminPhrase($pluginid, 'form_export_settings', 'Form Export Settings');
    InsertAdminPhrase($pluginid, 'create_field_type_bbcode', 'Text Box with BBCode');
    InsertAdminPhrase($pluginid, 'create_field_type_checkboxes', 'Check Boxes',2,true);
    InsertAdminPhrase($pluginid, 'create_field_type_date', 'Date');
    InsertAdminPhrase($pluginid, 'create_field_type_time', 'Time');
    InsertAdminPhrase($pluginid, 'create_field_type_file', 'File (any)');
    InsertAdminPhrase($pluginid, 'create_field_type_image', 'Image/Foto');
    InsertAdminPhrase($pluginid, 'create_field_type_music', 'Music');
    InsertAdminPhrase($pluginid, 'create_field_type_archive', 'Archive');
    InsertAdminPhrase($pluginid, 'create_field_type_documents', 'Documents');
    InsertAdminPhrase($pluginid, 'create_field_type_radio', 'Multiple Choice (1-of-many)',2,true);
    InsertAdminPhrase($pluginid, 'create_field_type_timezone', 'Timezone Selection');
    InsertAdminPhrase($pluginid, 'create_field_allowed_fileext', 'Allowed file extentions (comma-separated like png,gif,jpg):',2,true);
    InsertAdminPhrase($pluginid, 'create_field_allowed_filesize', 'Allowed max. filesize (in KB):');
    InsertAdminPhrase($pluginid, 'create_field_validator_type_date', 'Valid Date');
    InsertAdminPhrase($pluginid, 'create_field_validator_type_int', 'Valid Integer (...-2,-1,0,1,2,3...)',2,true);
    InsertAdminPhrase($pluginid, 'create_field_validator_type_whole', 'Valid Whole Number (1,2,3...)',2,true);
    InsertAdminPhrase($pluginid, 'none', 'None');
    InsertAdminPhrase($pluginid, 'submit_type_db', 'Database');
    InsertAdminPhrase($pluginid, 'submit_type_email', 'Email');
    InsertAdminPhrase($pluginid, 'submit_type_both', 'Email and Database');
    InsertAdminPhrase($pluginid, 'update_field_options_but', 'Update Field Options');
    InsertAdminPhrase($pluginid, 'option_name_label', 'Option Displayname');
    InsertAdminPhrase($pluginid, 'option_value_label', 'Option Value');
    InsertAdminPhrase($pluginid, 'option_display_order', 'Display Order');
    InsertAdminPhrase($pluginid, 'add_option_order_desc', '<strong>Display order</strong> for the option (whole number)?');
    InsertAdminPhrase($pluginid, 'add_option_name_desc', '<strong>Displayed name</strong> for the option (max. 128 characters)?');
    InsertAdminPhrase($pluginid, 'add_option_value_desc', '<strong>Stored value</strong> for the option (max. 128 characters)?');
    InsertAdminPhrase($pluginid, 'form_permissions_title', 'Form Submit Permissions');
    InsertAdminPhrase($pluginid, 'form_permissions_descr',
      'If a usergroup has permission to submit to the plugin, either all '.
      '(if no selection is made) or just the selected usergroups may view and submit this form.<br /><br />'.
      'Please use CTRL+click to select/deselect any usergroup(s), that shall '.
      'be allowed to submit this form.',2);
    InsertAdminPhrase($pluginid, 'form_wizard_settings', 'Form Wizard Settings',2,true);
    InsertAdminPhrase($pluginid, 'form_name_descr', 'Would you like to display the form\'s name to the user?',2,true);
    InsertAdminPhrase($pluginid, 'update_response_but', 'Update Response');

    DeletePluginSetting($pluginid, 'form_wizard_settings', 'Form Name');
    DeletePluginSetting($pluginid, 'Form Wizard Settings', 'Form Name');

    DeletePluginSetting($pluginid, 'form_wizard_settings', 'Require Captcha');
    DeletePluginSetting($pluginid, 'form_wizard_settings', 'require_captcha');
    DeleteAdminPhrase($pluginid, 'require_captcha', 2);
    DeleteAdminPhrase($pluginid, 'require_captcha_descr', 2);

    InsertPluginSetting($pluginid, 'form_wizard_settings', 'User Email As Sender Email',
      'Use the user\'s email address as the email "From" (sender email) (default: No)?<br />
      Some email servers will not allow sending emails with different domain names in which case this option must be set to "No"',
      'yesno', 0, 30, true);
    InsertPluginSetting($pluginid, 'form_wizard_settings', 'Display Form Name',
      'Would you like to display the actual forms\' name to the user?', 'yesno', '1', '1', true);

  } //p_FormWizard_CheckPhrases132
}

if(!function_exists('p_FormWizard_Upgrade132')) // 2013-03-07
{
  function p_FormWizard_Upgrade132()
  {
    global $DB, $pluginid, $plugin_folder, $pluginname;

    $CSS = new CSS();
    $CSS->InsertCSS($pluginname.' ('.$pluginid.')', 'plugins/'.$plugin_folder.'/css/default.css', false, array(), $pluginid);
    unset($CSS);

    // Create default templates (but no duplicates)
    $tpl_names = array('form_wizard.tpl'   => array('User form template',    'Frontpage template displaying the form to the user'),
                       'form_response.tpl' => array('Form summary response', 'Template for summary display after user submitted a form'));
    require_once(SD_INCLUDE_PATH.'class_sd_smarty.php');
    $tpl_path = ROOT_PATH.'plugins/'.$plugin_folder.'/tmpl/';
    foreach($tpl_names as $tpl_name => $tpl_details)
    {
      if(false === SD_Smarty::TemplateExistsInDB($pluginid, $tpl_name))
      {
        echo '<b>Adding template '.$tpl_details[0].' for plugin '.$pluginname.' ('.$plugin_folder.')...</b>';
        if(false !== SD_Smarty::CreateTemplateFromFile($pluginid, $tpl_path, $tpl_name,
                                                       $tpl_details[0], $tpl_details[1]))
          echo '<br /><b>Done.</b><br />';
        else
          echo '<br /><b>Failed to add template '.$tpl_details[0].'!</b><br />';
      }
    }

    $tbl = PRGM_TABLE_PREFIX.'p'.$pluginid.'_';
    $DB->add_tablecolumn($tbl.'formfield', 'allowed_fileext', 'TINYTEXT', 'NULL');
    $DB->add_tablecolumn($tbl.'formfield', 'max_filesize', 'INT(11) UNSIGNED', 'NOT NULL DEFAULT 0');
    $DB->add_tablecolumn($tbl.'formoption', 'optionvalue', 'VARCHAR(128)', "NOT NULL DEFAULT ''");
    $DB->add_tablecolumn($tbl.'formoption', 'displayorder', 'INT(8)', "NOT NULL DEFAULT 0");
    $DB->add_tableindex($tbl.'formoption', 'field_id', 'field_id');
    $DB->add_tableindex($tbl.'formoption', 'name', 'name');
    $DB->query('UPDATE '.$tbl."formoption SET optionvalue = name WHERE IFNULL(optionvalue,'') = ''");
    $DB->add_tablecolumn($tbl.'form', 'access_view', 'VARCHAR(254) collate utf8_unicode_ci NULL');

    $DB->query('UPDATE '.PRGM_TABLE_PREFIX."pluginsettings SET groupname = 'form_export_settings'
      WHERE pluginid = %d AND title = 'email_only_filled_values' AND groupname = 'form_wizard_settings'",$pluginid);

    InsertPluginSetting($pluginid, 'form_wizard_settings', 'Display Time Format',
      'This will display time in either 12 or 24 hour format (default: 12) for <b>time</b> fields.',
      "select:\r\n0|12 hours\r\n1|24 hours", "0", 40);
    InsertPluginSetting($pluginid, 'form_wizard_settings', 'Form Attachment Usergroups',
      'Allow selected usergroups to submit files for form fields of type <strong>File</strong> (default: none):<br />'.
      'Notes:<br />&bullet; allowed file types and sizes are determined by the primary usergroup of the user<br />'.
      '&bullet; for security reasons it is highly recommended to NOT allow <strong>Guests</strong> to upload anything<br />'.
      'De-/select single or multiple entries by CTRL/Shift+click.',
      'usergroups', '-1', 50, true);
    InsertPluginSetting($pluginid, 'form_wizard_settings', 'Display Response Summary',
      'Display a summary of provided data and selected options to the user after a form was submitted (default: No)?',
      'yesno', 0, 60, true);
    InsertPluginSetting($pluginid, 'form_wizard_settings', 'Response Summary Top',
      'Provide an optional text (bbcode) to be displayed before the response summary, like a thank you message or similar:',
      'bbcode', "Thank you very much for your submittal.\r\n\r\nBelow is a summary of the information you provided:\r\n\r\n", 70, true);
    InsertPluginSetting($pluginid, 'form_wizard_settings', 'Response Summary Bottom',
      'Provide an optional text (bbcode) to be displayed displaying after the response summary (default: empty):',
      'bbcode', '', 80);

    InsertPluginSetting($pluginid, 'form_export_settings', 'CSV Delimiter',
      'Delimiter character to separate column values (default: comma)?',
      "select:\r\n0|Comma\r\n1|Tab\r\n2|Semicolon ;", '0', 10);
    InsertPluginSetting($pluginid, 'form_export_settings', 'Empty Values Default',
      'Default text for fields that have no value (default: empty)?',
      'text', '', 20);
    InsertPluginSetting($pluginid, 'form_export_settings', 'Export Option Names',
      'Export option names instead of values (default: No)?<br />
       Some field types offer to add one or more labelled options, such as for
       radio buttons or dropdown boxes. If this option is set to Yes, then
       instead of the stored values (e.g. 0,1,2) their name will be exported.',
      'yesno', 0, 30);
    InsertPluginSetting($pluginid, 'form_export_settings', 'Email Only Filled Values',
      'When sending a form to recipients, only send fields that actually have values (default: No)?',
      'yesno', 0, 40);
    InsertPluginSetting($pluginid, 'form_export_settings', 'Export Date Format',
      'Default date format for exporting dates (default: Y-m-d G:i)?<br />'.
      'If left empty, the date format configured in Main Settings is being used.',
      'text', 'Y-m-d G:i', 100);

  } //p_FormWizard_Upgrade132
}

// ###########################################################################
// INSTALL PLUGIN
// ###########################################################################

if($installtype == 'install')
{
  // ##############
  // VERSION CHECK
  // ##############
  if(!requiredVersion(3,5))
  {
    if(strlen($installtype))
    {
      echo $pluginname.': you must upgrade '.PRGM_NAME.
           ' to at least v3.5+ to install this plugin.<br />';
    }
    return false;
  }

  // At this point SD3 has to provide a new plugin id
  $pluginid = CreatePluginID($pluginname);

  // If for whatever reason the plugin id is invalid, return false NOW!
  if(empty($pluginid))
  {
    return false;
  }

  $tbl = 'CREATE TABLE '.PRGM_TABLE_PREFIX.'p'.$pluginid.'_';
  $engine = ' ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';
  $DB->query($tbl."form (
	form_id          INT(11)       NOT NULL AUTO_INCREMENT,
	name             VARCHAR(64)   NOT NULL,
	submit_type      INT(11)       NOT NULL,
	submit_text      VARCHAR(64)   NOT NULL,
	intro_text       TEXT          NOT NULL,
	success_text     TEXT          NOT NULL,
	showemailaddress TINYINT(1)    NOT NULL DEFAULT 1,
	sendtoall        TINYINT(1)    NOT NULL DEFAULT 0,
	active           TINYINT       NOT NULL,
  date_created     INT(11)       NOT NULL,
	email_sender_id  INT(11)       NOT NULL,
	PRIMARY KEY (form_id))".$engine);

  $DB->query($tbl."formcategory (
	form_id          INT(11)       NOT NULL,
	category_id      INT(11)       NOT NULL)".$engine);

  $DB->query($tbl."formfield (
	field_id         INT(11)       NOT NULL AUTO_INCREMENT,
	form_id          INT(11)       NOT NULL,
	field_type       INT(11)       NOT NULL,
	name             VARCHAR(128)  NOT NULL,
	validator_type   INT(11)       NOT NULL,
	label            VARCHAR(255)  NOT NULL,
	width            INT(10)       NOT NULL DEFAULT 0,
	height           INT(10)       NOT NULL DEFAULT 0,
	sort_order       INT(11)       NOT NULL,
	active           TINYINT       NOT NULL DEFAULT 1,
	date_created     INT(11)       NOT NULL DEFAULT 0,
	PRIMARY KEY (field_id))".$engine);

  $DB->query($tbl."formoption (
  option_id        INT(11)       NOT NULL AUTO_INCREMENT,
	field_id         INT(11)       NOT NULL,
  name             VARCHAR(128)  NOT NULL DEFAULT '',
	optionvalue      VARCHAR(128)  NOT NULL DEFAULT '',
	PRIMARY KEY (option_id),
  INDEX(field_id))".$engine);

  $DB->query($tbl."formresponse (
	response_id      INT(11)       NOT NULL AUTO_INCREMENT,
	form_id          INT(11)       NOT NULL,
	username         VARCHAR(128)  NOT NULL DEFAULT '',
	ip_address       VARCHAR(15)   NOT NULL DEFAULT '',
	date_created     INT(11)       NOT NULL DEFAULT 0,
	PRIMARY KEY (response_id))".$engine);

  $DB->query($tbl."formresponsefields (
	response_id      INT(11)       NOT NULL,
	field_id         INT(11)       NOT NULL,
	value            TEXT          NOT NULL,
  PRIMARY KEY (response_id,field_id))".$engine);

  $DB->query($tbl."recipient (
	recipient_id     INT(11)       NOT NULL AUTO_INCREMENT,
	email            VARCHAR(64)   NOT NULL,
	name             VARCHAR(64)   NOT NULL,
	PRIMARY KEY (recipient_id))".$engine);

  $DB->query($tbl."formrecipient (
	form_id          INT(11)       NOT NULL,
	recipient_id     INT(11)       NOT NULL,
	PRIMARY KEY (form_id, recipient_id))".$engine);

  $DB->query($tbl."formresponserecipient (
	response_id      INT(11)       NOT NULL,
	recipient_id     INT(11)       NOT NULL,
	PRIMARY KEY (response_id, recipient_id))".$engine);

  InsertAdminPhrase($pluginid, 'form_wizard_settings', 'Form Wizard Settings');

  // CREATE SETTINGS
  InsertPluginSetting($pluginid, 'form_wizard_settings', 'Display Form Name', 'Would you like to display the Form name to users?', 'yesno', '1', '1');

  $DB->query('UPDATE {plugins} SET settings = 27 WHERE pluginid = %d',$pluginid);
  $tbl = PRGM_TABLE_PREFIX.'p'.$pluginid.'_';
  $DB->add_tableindex($tbl.'formfield', 'form_id', 'form_id');
  $DB->add_tableindex($tbl.'formfield', 'name', 'name');
  $DB->add_tableindex($tbl.'formresponse', 'form_id', 'form_id');
  $DB->add_tableindex($tbl.'formresponse', 'username', 'username');
  $DB->add_tableindex($tbl.'formresponsefields', 'response_id', 'response_id');
  $DB->add_tableindex($tbl.'formresponsefields', 'field_id', 'field_id');
  $DB->ignore_error = false;

  p_FormWizard_Upgrade132(); // 2013-03-07

  p_FormWizard_CheckPhrases132();

} //install

// ############################################################################
// UNINSTALL PLUGIN
// ############################################################################

if(isset($currentversion) && ($installtype == 'upgrade'))
{
  if(empty($pluginid)) return false;

  if(version_compare($currentversion, '1.2.2','le'))
  {
    // New Admin Phrase: Export to Word
    InsertAdminPhrase($pluginid, 'export_to_doc', 'Export to Word');

    // Alter Admin Phrase: Intro Text
    $DB->query("UPDATE {adminphrases} SET defaultphrase = 'This will be displayed above the form, useful for explaining the purpose of filling it out to users.<br /><br /><b><u>Allowed BBcode:</u></b><br /><br />[b]Text[/b], [i]Text[/i], [u]Text[/u]' WHERE pluginid ='" . $pluginid . "' AND varname ='intro_text_desc'");

    // Alter Admin Phrase: Success Text
    $DB->query("UPDATE {adminphrases} SET defaultphrase = 'This will be displayed when the form has been successfully submitted.<br /><br /><b><u>Allowed BBcode:</u></b><br /><br />\"[b]Text[/b]\", \"[i]Text[/i]\", \"[u]Text[/u]\"' WHERE pluginid ='" . $pluginid . "' AND varname ='success_text_desc'");

    $DB->query('UPDATE {plugins} SET settings = 27 WHERE pluginid = %d',$pluginid);
    $DB->ignore_error = true;
    $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_formfield', 'form_id', 'form_id');
    $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_formfield', 'name', 'name');
    $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_formresponse', 'form_id', 'form_id');
    $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_formresponse', 'username', 'username');
    $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_formresponsefields', 'response_id', 'response_id');
    $DB->add_tableindex(PRGM_TABLE_PREFIX.'p'.$pluginid.'_formresponsefields', 'field_id', 'field_id');
    $DB->ignore_error = false;

    $currentversion = '1.2.3';
    UpdatePluginVersion($pluginid, $currentversion);
  }

  if ($currentversion == '1.2.3')
  {
    // Upgrade settings to new format; added new email setting
    $DB->query("DELETE FROM {adminphrases} WHERE pluginid = %d AND varname IN ('form_name','form_name_desc','display_recaptcha','display_recaptcha_desc')", $pluginid);

    $DB->query("UPDATE {pluginsettings} SET groupname = 'form_wizard_settings', description = 'form_name_descr', displayorder = 10 WHERE pluginid = %d AND title = 'form_name'", $pluginid);
    $DB->query("UPDATE {pluginsettings} SET groupname = 'form_wizard_settings', title = 'display_captcha', description = 'display_captcha_descr', displayorder = 20 WHERE pluginid = %d AND title = 'display_recaptcha'", $pluginid);

    $currentversion = '1.2.4';
    UpdatePluginVersion($pluginid, $currentversion);
  }

  if(version_compare($currentversion,'1.2.6','<'))
  {
    $tbl = PRGM_TABLE_PREFIX.'p'.$pluginid.'_';
    $DB->add_tablecolumn($tbl.'form', 'email_sender_id', 'INT(11) UNSIGNED', 'NOT NULL DEFAULT 0');

    $currentversion = '1.2.6';
    UpdatePluginVersion($pluginid, $currentversion);
  }

  if(version_compare($currentversion,'1.3.2','<')) #2013-09-23
  {
    p_FormWizard_Upgrade132();
    $currentversion = '1.3.2';
    UpdatePluginVersion($pluginid, $currentversion);
  }

  //Make sure that all phrases exist:
  p_FormWizard_CheckPhrases132();

} //upgrade

// ############################################################################
// UNINSTALL PLUGIN
// ############################################################################

if($installtype == 'uninstall')
{
  if($pluginid = GetPluginIDbyFolder($plugin_folder))
  {
    $sql = 'DROP TABLE IF EXISTS '.PRGM_TABLE_PREFIX.'p'.$pluginid;
    $DB->query($sql."_formresponserecipient");
    $DB->query($sql."_formrecipient");
    $DB->query($sql."_recipient");
    $DB->query($sql."_formresponse");
    $DB->query($sql."_formresponsefields");
    $DB->query($sql."_formfield");
    $DB->query($sql."_formoption");
    $DB->query($sql."_formcategory");
    $DB->query($sql."_form");
    unset($sql);

    //SD370: remove all attachments for plugin
    include_once(SD_INCLUDE_PATH.'class_sd_attachment.php');
    $a = new SD_Attachment($pluginid);
    $a->ProcessPluginAttachments(true);
    unset($a);
  }
}
