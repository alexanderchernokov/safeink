<?php
if(!defined('IN_PRGM')) return false;

// ############################################################################
// GET PLUGIN ID/LIBRARY
// ############################################################################
$plugin_folder = sd_GetCurrentFolder(__FILE__);
if(!$pluginid = GetPluginIDbyFolder($plugin_folder)) return false;

include($rootpath . 'plugins/'.$plugin_folder.'/lib.php');
require_once(SD_INCLUDE_PATH.'class_sd_attachment.php');

if(!class_exists('FormWizard'))
{
class FormWizard
{
   public $pluginid   = 0;
   public $settings   = array();
   public $language   = array();
   public $can_admin  = false;
   public $can_submit = false;
   private $_pre      = '';
   private $_tbl      = '';

   public function FormWizard($pluginid)
   {
     global $userinfo;

     $this->pluginid = (int)$pluginid;
     $this->settings = GetPluginSettings($this->pluginid);
     //v1.3.0: prep attachment settings
     $this->settings['form_attachment_usergroups'] =
       !isset($this->settings['form_attachment_usergroups'])?array():
       sd_ConvertStrToArray($this->settings['form_attachment_usergroups'],',');

     $this->language = GetLanguage($this->pluginid);
     $this->can_admin = !empty($userinfo['adminaccess']) ||
                        (!empty($userinfo['pluginadminids']) &&
                         @in_array($this->pluginid, $userinfo['pluginadminids']));
     $this->can_submit = (!empty($userinfo['pluginsubmitids']) &&
                          @in_array($this->pluginid, $userinfo['pluginsubmitids']));
     $this->_pre = 'p'.$this->pluginid;
     $this->_tbl = PRGM_TABLE_PREFIX.$this->_pre.'_';
   }

   // #########################################################################

   private function InsertDB($formid, $formValues)
   {
      global $DB, $userinfo;

      $DB->query('INSERT INTO '.$this->_tbl."formresponse VALUES (NULL, %d, '%s', '%s', %d)",
                 $formid, $DB->escape_string($userinfo['username']), USERIP, TIME_NOW);
      if(!$response_id = $DB->insert_id()) return false;

      foreach($formValues as $key => $val)
      {
        $DB->query('INSERT INTO '.$this->_tbl."formresponsefields VALUES(%d, '%s', '%s')",
                   $response_id, $key, (is_array($val)?$val[0]:$val));
      }

      return $response_id;

  } //InsertDB

  // #########################################################################

  private function EmailForm($formid, $formValues, $emailTo,
                             $formName, $SenderEmail, $SenderName)
  {
    global $DB, $mainsettings, $userinfo;

    $msgbody = $this->language['form_name'] . " '" . $formName . "'" . EMAIL_CRLF;
    $msgbody .= $this->language['from_username'] . ' - ' . $userinfo['username']  . EMAIL_CRLF;
    $msgbody .= $this->language['from_ip'] . ' - ' . USERIP . EMAIL_CRLF;
    $msgbody .= EMAIL_CRLF . $this->language['form_values'] . EMAIL_CRLF;

    foreach($formValues as $key => $val)
    {
      //v1.2.7: option "email_only_filled_values"
      if( empty($this->settings['email_only_filled_values']) ||
          (isset($val[0]) && strlen($val[0])) ||
          (isset($val[2]) && strlen($val[2])) )
      {
        if($getname = $DB->query_first('SELECT name'.
                      ' FROM '.$this->_tbl.'formoption'.
                      ' WHERE field_id = '.$key.
                      " AND optionvalue = '%s'",
                      $DB->escape_string($val[0])))
        {
          $val[2] = $getname['name'];
        }
        $msgbody .= $val[1] . ' - ' . $val[0] . # <- "name - value"
                    (!empty($val[2]) && ($val[0]!=$val[2])?' ('.$val[2].')':''). # <- " (filename or option name)
                    EMAIL_CRLF;
      }
    }

    SendEmail($emailTo, unhtmlspecialchars($this->language['email_subject'] . ' - ' . $formName),
              unhtmlspecialchars($msgbody), $SenderName, $SenderEmail,null,null,false);

  } //EmailForm

  // #########################################################################

  private function ValidateField($validator, $name, $value)
  {
    switch($validator)
    {
      case VALIDATOR_NOT_EMPTY: /* Not Empty */
        if(!isset($value) || !strlen($value))
        {
          return '<strong>'.$name.'</strong> '.$this->language['is_empty'];
        }
        break;

      case VALIDATOR_NUMBER: // Validate Number
        if(!is_numeric($value))
        {
          return '<strong>'.$name.'</strong> '.$this->language['not_number'];
        }
        break;

      case VALIDATOR_INTEGER: // Validate integer, v1.3.0
        if(!is_numeric($value) || !ctype_digit($value))
        {
          return '<strong>'.$name.'</strong> '.$this->language['not_integer'];
        }
        break;

      case VALIDATOR_WHOLE_NUM: // Validate pos. integer, v1.3.0
        if(!is_numeric($value) || !ctype_digit($value) || ((int)$value < 0))
        {
          return '<strong>'.$name.'</strong> '.$this->language['not_whole_number'];
        }
        break;

      case VALIDATOR_EMAIL: /* Validate Email */
        $valid = false;
        if(function_exists('IsValidEmail'))
        {
          $valid = IsValidEmail($value);
        }
        else
        {
          $valid = preg_match("/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+$/", $value);
        }
        if(!$valid)
        {
          return '<strong>'.$name.'</strong> '.$this->language['not_email'];
        }
        break;

      case VALIDATOR_URL: /* Validate URL */
        if(function_exists('sd_check_url'))
          $valid = sd_check_url($value);
        else
          $valid = !preg_match("/^(http(s?):\/\/|ftp:\/\/{1})((\w+\.){1,})\w{2,}$/i", $value);
        if(!$valid)
        {
          return '<strong>'.$name.'</strong> '.$this->language['not_url'];
        }
        break;
    }

    return '';

  } //ValidateField

   // #########################################################################

   public function DisplayForm($formid, $errors_arr=false)
   {
    global $DB, $bbcode, $inputsize, $categoryid, $mainsettings_allow_bbcode,
           $sdlanguage, $userinfo, $formid;

    if(empty($formid) || !Is_Valid_Number($formid,0,1,9999999))
    {
      return false;
    }

    $prefix = 'p'.$this->pluginid;
    $DB->result_type = MYSQL_ASSOC;
    $formvar = $DB->query_first('SELECT * FROM '.$this->_tbl.'form WHERE form_id = '.(int)$formid);
    if(empty($formvar['form_id'])) return false;

    $DB->result_type = MYSQL_ASSOC;
    $getfields = $DB->query('SELECT * FROM '.$this->_tbl.'formfield'.
                            ' WHERE form_id = %d AND active = 1'.
                            ' ORDER BY sort_order, field_id',
                            $formid);
    if(empty($getfields) || !($fieldcount = $DB->get_num_rows($getfields)))
    {
      return false;
    }

    // ################################################
    // v1.3.0: output display moved to template!
    // ################################################
    $tmpl = SD_Smarty::getNew();

    if(strlen($formvar['intro_text']) && ($formvar['intro_text'] != '<br />'))
    {
      if(!empty($bbcode) && ($bbcode instanceof BBCode))
      {
        $formvar['intro_text'] = $bbcode->Parse($formvar['intro_text']);
      }
    }

    $att = new SD_Attachment($this->pluginid,'formresponse');

    $attach_path = ROOT_PATH.$att->getStorageBasePath();
    $attach_path_ok = @is_dir($attach_path) && @is_writable($attach_path);
    $CanAttachFiles = $attach_path_ok &&
                      (($formvar['submit_type'] == SUBMIT_DB) ||
                       ($formvar['submit_type'] == SUBMIT_EMAIL_DB)) &&
                      ($this->can_admin ||
                       (!empty($this->settings['form_attachment_usergroups']) &&
                        !empty($userinfo['usergroupids']) &&
                       @array_intersect($userinfo['usergroupids'],
                        $this->settings['form_attachment_usergroups'])));

    $tmpl->assign('loggedin', !empty($userinfo['loggedin']));
    $tmpl->assign('SecurityFormToken', PrintSecureToken($prefix.'_token'));
    $tmpl->assign('sdlanguage',$sdlanguage);
    $tmpl->assign('pluginid',  $this->pluginid);
    $tmpl->assign('settings',  $this->settings);
    $tmpl->assign('phrases',   $this->language);
    $tmpl->assign('form_data', $formvar);
    if(empty($userinfo['adminaccess']))
    {
      $tmpl->assign('captcha', DisplayCaptcha(false, $prefix));
    }
    $tmpl->assign('form_action', RewriteLink('index.php?categoryid='.$categoryid.'&action=processform'));

    if(!empty($errors_arr))
    {
      $tmpl->assign('form_errors', !is_array($errors_arr)?false:implode('<br />',$errors_arr));
    }

    if(($formvar['submit_type'] != 2) && empty($formvar['sendtoall']))
    {
      $tmpl->assign('display_recipients_list', $this->PrintRecipientSelection($formid, $formvar['showemailaddress']));
    }
    else
      $tmpl->assign('display_recipients_list', false);

    // Build array enlisting all form fields:
    $idx = 1;
    $fields = array();
    $js_form_id = '#p'.$this->pluginid.'_'.$formid;
    /*
    messages: {
      required: "This field is required.",
      remote: "Please fix this field.",
      email: "Please enter a valid email address.",
      url: "Please enter a valid URL.",
      date: "Please enter a valid date.",
      dateISO: "Please enter a valid date (ISO).",
      number: "Please enter a valid number.",
      digits: "Please enter only digits.",
      creditcard: "Please enter a valid credit card number.",
      equalTo: "Please enter the same value again.",
      maxlength: $.validator.format("Please enter no more than {0} characters."),
      minlength: $.validator.format("Please enter at least {0} characters."),
      rangelength: $.validator.format("Please enter a value between {0} and {1} characters long."),
      range: $.validator.format("Please enter a value between {0} and {1}."),
      max: $.validator.format("Please enter a value less than or equal to {0}."),
      min: $.validator.format("Please enter a value greater than or equal to {0}.")
    },
    errorLabelContainer: $("'.$js_form_id.' div.error_message"),
    errorContainer: $("'.$js_form_id.' div.error_message"),
    rules: {
      email:    { required: true },
      password: { required: true }
    },
    */
    $js_output = '
  if(typeof jQuery.fn.uniform !== "undefined"){
    jQuery("'.$js_form_id.'").uniform();
  }
  $("'.$js_form_id.'").validate({
    submitHandler: function(form) {
      form.submit();
    }
  });
  var formelements = $("'.$js_form_id.' input[type!=\'submit\'], textarea, select");
  formelements.focus(function(){
    $(this).parents("div").addClass("ui-state-highlight");
  });
  formelements.blur(function(){
    $(this).parents("div").removeClass("ui-state-highlight");
  });
    ';
    $bbcode_list  = array(); //list of IDs for bbcode
    $date_added   = false;
    $time_added   = false;
    $allow_bbcode = !empty($mainsettings_allow_bbcode) &&
                    !empty($bbcode) && ($bbcode instanceof BBCode);
    $time_format = empty($this->settings['display_time_format'])?'h:ia':'H:i';

    // Process all fields before passing to template:
    while($field = $DB->fetch_array($getfields,null,MYSQL_ASSOC))
    {
      $width  = (strlen($field['width'])  && !empty($field['width']))  ? $field['width'] : $inputsize;
      $height = (strlen($field['height']) && !empty($field['height'])) ? $field['height'] : '';
      $field_id = (int)$field['field_id'];
      $key    = $this->_pre.'_field_'.$field_id;
      $val    = isset($_POST[$key]) ? $_POST[$key] : '';

      $out = array(
        'outer_class'     => 'ctrlHolder',          #<div class="ctrlHolder">
        'input_name'      => $key,                  #<label for="{$p5006_email_hash}">
        'input_id'        => $key,
        'input_class'     => 'textInput auto',      #<input class="textInput auto" ...
        'input_type'      => $field['field_type'],
        'input_value'     => (is_array($val)?'':htmlentities($val)),
        'input_attr'      => '',
        'input_title'     => $field['name'],
        'label_text'      => (empty($field['label'])?$field['name']:$field['label']),
        'input_required'  => !empty($field['validator_type']),
        'options'         => false,
        'do_honepot'      => false,#($honeypot_idx==$idx),
        'extra_html'      => '',
        /* Example:
        'extra_html'  => '    <img id="p12_checkimg1" src="includes/css/images/blank.gif" alt="" width="16" height="16" style="display:none;" />'."\r\n".
                         '      <span id="p12_err_usr"></span>'."\r",
        */
      );
      if($out['input_required'])
      {
        $out['input_class'] .= ' required';
      }

      if(($field['field_type']==FIELD_BBCODE) || ($field['field_type']==FIELD_TEXTAREA))
      {
        $out['input_attr'] = (empty($field['width'])?'':' cols='.(int)$field['width'].'"').
                             (empty($field['height'])?'':' rows='.(int)$field['height'].'"');
      }

      // Fetch options for field:
      if(FormWizard_FieldHasOptions($field['field_type']))
      {
        unset($out['input_value']);
        $out['options'] = array();
        if($opts = $DB->query('SELECT * FROM '.$this->_tbl.'formoption'.
                              ' WHERE field_id = %d'.
                              ' ORDER BY displayorder, optionvalue, name',
                              $field_id))
        while($opt = $DB->fetch_array($opts,null,MYSQL_ASSOC))
        {
          $opt['checked'] = 0;
          if($field['field_type']==FIELD_CHECKMULTI)
          {
            if(!empty($val) && is_array($val) && in_array($opt['optionvalue'],$val))
            {
              $opt['checked'] = 1;
            }
          }
          else
          if(isset($val) && !is_array($val) && ($val===$opt['optionvalue']))
          {
            $opt['checked'] = 1;
          }
          $out['options'][] = $opt;
        }
        if(empty($out['options'])) $out['options'] = false;
      }

      switch($field['field_type'])
      {
        case FIELD_TIME: //v1.3.0
          if(empty($val)) $val = TIME_NOW;
          if(is_numeric($val))
          {
            $val = DisplayDate($val,$time_format,false);
          }
          $out['input_value'] = $val;
          $out['input_type'] = 'text';
          $out['input_attr'] = 'maxlength="5" size="8"';
          if(!$time_added)
          {
            $time_added = true;
            $js_output .= '
  var timeEntryOptions = {show24Hours: true, separator: ":", spinnerImage: "includes/css/images/spinnerOrange.png" };';
          }
          $js_output .= '
  if (typeof(jQuery.fn.timeEntry) !== "undefined") {
    jQuery("#'.$out['input_id'].'").timeEntry(timeEntryOptions);
  }';
          break;

        case FIELD_DATE: //v1.3.0
          if(empty($val)) $val = '';
          $out['input_value'] = $val;
          if(!$date_added)
          {
            $date_added = true;
            $js_output .= '
  var datePickerOptions = { altFormat: "yyyy-mm-dd", yearRange: "1900:2020", showTrigger: "<"+"img alt=\'...\' src=\''.SD_INCLUDE_PATH.'css/images/calendar.png\' width=\'16\' height=\'16\' \/>"};';
          }
          $js_output .= '
  var dvalue = jQuery("#'.$key.'").attr("rel") * 1000;
  var dc = new Date(dvalue);
  jQuery("#'.$key.'_date").datepick(
    jQuery.extend(datePickerOptions, {
    '.(!empty($val) ? 'defaultDate: dc,' : '') . ' altField: "#'.$key.'"
    })
  );
  if((dvalue != 0) && (typeof(dc) !== "undefined")) {
    jQuery("#'.$key.'_date").datepick("setDate", dc);
  }';
          // alternate field to store date in ISO format
          $out['extra_html'] = '<input type="hidden" id="'.$key.'" name="'.$key.'" rel="'.$val.'" value="' .
                     (!empty($val) ? DisplayDate($val, '', true) : '') . '" />';
          $out['input_id']    = $key.'_date';
          $out['input_name']  = $key.'_date';
          $out['input_value'] = DisplayDate($val);
          $out['input_type']  = 'text';
          $out['input_attr']  = 'maxlength="10"';
          break;

        case FIELD_BBCODE: //v1.3.0
          $out['input_type']  = ($allow_bbcode?'bbcode':'textarea');
          if($allow_bbcode)
          {
            $bbcode_list[] = $out['input_id'];
            $out['input_type'] = 'bbcode';
            $out['input_class'] = 'bbeditor';
          }
          else
          {
            $out['input_type'] = 'textarea';
          }
          break;

        case FIELD_TEXTAREA:
          $out['input_type'] = 'textarea';
          break;

        case FIELD_TIMEZONE: //v1.3.0
          if(empty($val)) $val = '0';
          $out['input_type'] = 'timezone';
          $out['extra_html'] = GetTimezoneSelect($out['input_name'], $val, $out['input_name'], 'selectInput');
          break;

        case FIELD_SELECT:
          $out['input_type'] = 'select';
          if(empty($out['options']))
          {
            $out = false;
          }
          else
          {
            $out['extra_html'] = '
            <select id="'.$key.'" name="'.$key.'" '.
            (empty($field['height']) ? '' : 'size="'.$field['height'].'" ').
            (empty($field['width'])  ? '' : 'style="width:'.$field['width'].'"').'>';
            foreach($out['options'] as $key => $opt)
            {
              $out['extra_html'] .= '
              <option value="'.$opt['optionvalue'].'"'.
                ($opt['optionvalue']==$val?' selected="selected"':'').
                '>'.$opt['name'].'</option>';
            }
            $out['extra_html'] .= '</select>';
          }
          break;

        case FIELD_CHECKBOX:
          $out['input_type'] = 'checkbox';
          break;

        case FIELD_CHECKMULTI: //v1.3.0
          $out['input_type'] = 'checkboxes';
          break;

        case FIELD_RADIO: //v1.3.0
          $out['input_type'] = 'radio';
          break;

        //v1.3.0: support for file attachment
        case FIELD_FILE:
        case FIELD_IMAGE:
        case FIELD_MUSIC:
        case FIELD_ARCHIVE:
        case FIELD_DOCUMENTS:
          $out['input_type'] = 'file';
          $out['input_class'] = 'fileUpload auto';

          $out['extra_html'] = '';
          if(!empty($field['allowed_fileext']) && ($field['allowed_fileext']!='*'))
          {
            $out['extra_html'] .= '<br />'.$this->language['allowed_file_extentions_hint'].' '.$field['allowed_fileext'];
          }
          if(!empty($field['max_filesize']))
          {
            $out['extra_html'] .= '<br />'.$this->language['allowed_file_size_hint'].' '.$field['max_filesize'];
          }
          // If upload not allowed, empty field
          if(!$CanAttachFiles) unset($out);
          break;

        case FIELD_EMAIL:
          $out['input_type'] = 'text';
          $out['input_class'] .= ' email required';
          break;
        default: // Text
          $out['input_type'] = 'text';
          if($field['validator_type']==VALIDATOR_EMAIL)
          {
            $out['input_class'] .= ' email required';
          }
          else
          if($field['validator_type']==VALIDATOR_URL)
          {
            $out['input_class'] .= ' url';
          }
          else
          if($field['validator_type']==VALIDATOR_NUMBER)
          {
            $out['input_class'] .= ' required number';
          }
          else
          if($field['validator_type']==VALIDATOR_WHOLE_NUM)
          {
            $out['input_class'] .= ' required digits';
          }
        break;
      }

      if(!empty($out))
      {
        $fields[] = $out;
        $idx++;
      }

    } //while

    $p_hash = date('Ymd').USERIP.'-'.$this->pluginid;
    $p_honeypot_hash = 'p'.md5($p_hash.'dummyvalue');

    if(!$fieldcount = count($fields))
    {
      return false;
    }

    if(function_exists('mt_rand'))
      $honeypot_idx = mt_rand (1,$fieldcount);
    else
      $honeypot_idx = rand(1,$fieldcount);
    $filler = str_repeat('raquo;',$honeypot_idx);
    $honeypot = array(
      'outer_class' => 'ctrlHolder" style="display:none;visibility:hidden',
      'input_name'  => $p_honeypot_hash,
      'input_id'    => $p_honeypot_hash,
      'input_class' => 'textInput auto',
      'input_type'  => 'text',
      'input_value' => '', //Must be empty!
      'input_attr'  => ' maxlength="30" autocomplete="off" ',
      'label_text'  => $sdlanguage['msg_do_not_enter_anything'],
      'extra_html'  => '',
      'do_honepot'  => false
    );
    $fields[$honeypot_idx]['do_honepot'] = true;

    $tmpl->assign('honeypot', $honeypot);
    $tmpl->assign('honeypot_idx', $honeypot_idx);
    $tmpl->assign('form_fields_count', $fieldcount);

    // The main "source" for the form are its fields:
    $tmpl->assign('form_fields', $fields);

    // Add JS for BBCode-enabled fields:
    if($allow_bbcode && !empty($bbcode_list))
    {
      $js_output .= '
  if(typeof jQuery.fn.markItUp !== "undefined" &&
     typeof myBbcodeSettings !== "undefined") {
    jQuery(".form_wizard_form #'.implode(',#',$bbcode_list).'").markItUp(myBbcodeSettings);
  };';
    }

    $tmpl->assign('p_js', false);
    if(!empty($js_output))
    {
      $tmpl->assign('p_js', '
<script type="text/javascript">
if(typeof(jQuery) !== "undefined"){
jQuery(document).ready(function() {
  ' . $js_output.'
});
}
</script>
  ');
    }

    // Finally, call Smarty to display template containing the form:
    $tmpl_done = SD_Smarty::display($this->pluginid, 'form_wizard.tpl');
    if(!$tmpl_done && !empty($userinfo['adminaccess']))
    {
      echo '<pre>'.sd_wordwrap(sd_unhtmlspecialchars(SD_Smarty::getLastError()),60,'<br />').'</pre>';
    }

  } //DisplayForm

  // #########################################################################

  public function ProcessForm()
  {
    global $DB, $categoryid, $plugin_names, $sdlanguage, $sdurl, $userinfo;

    $errors = $values = array();
    $emailField = null;
    $pagelink = RewriteLink();

    // Security check against spam/bot submissions
    if(!CheckFormToken('p'.$this->pluginid.'_token'))
    {
      RedirectFrontPage($pagelink,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      return false;
    }

    //v1.3.0: check banned IPs
    $p12_settings = GetPluginSettings(12);
    if(sd_IsIPBanned(USERIP, $p12_settings['banned_ip_addresses']))
    {
      DisplayMessage($sdlanguage['you_are_banned'], true, '');
      return false;
    }

    //v1.3.0: anti-spam check: honeypot input must be empty
    $p_hash = date('Ymd').USERIP.'-'.$this->pluginid;
    $p_honeypot_hash = 'p'.md5($p_hash.'dummyvalue');
    if(!empty($_POST[$p_honeypot_hash]) || (strlen($_POST[$p_honeypot_hash])>0))
    {
      WatchDog(strip_alltags($plugin_names[$this->pluginid]),
               $sdlanguage['msg_spam_trap_triggered'].
               ' IP: <span class="ipaddress">'.USERIP.'</span>',
               WATCHDOG_NOTICE);
      RedirectFrontPage($pagelink,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      return false;
    }

    //v1.3.0: support for StopForumSpam.com; based on p12 settings
    if(empty($errors) && !empty($p12_settings['enable_sfs_antispam']) && function_exists('sd_sfs_is_spam'))
    {
      if(sd_sfs_is_spam('',USERIP))
      {
        WatchDog(strip_alltags($plugin_names[$this->pluginid]),
                 '<b>StopForumSpam:</b> IP: <span class="ipaddress">'.USERIP.'</span>',
                 WATCHDOG_ERROR);
        RedirectFrontPage($pagelink,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
        return false;
      }
    }

    $response_id = 0;
    $formid = GetVar('p' . $this->pluginid . '_formid', '', 'string', true, false);
    $recipientid = GetVar('p' . $this->pluginid . '_recipientid', '', 'string', true, false);

    $DB->result_type = MYSQL_ASSOC;
    if(!$form = $DB->query_first('SELECT * FROM '.$this->_tbl.'form WHERE form_id = %d', $formid))
    {
      DisplayMessage($this->language['msg_invalid_form'], true);
      return false;
    }

    // v1.3.0: new: "File" field types (only if form storage includes DB!)
    $att = new SD_Attachment($this->pluginid,'formresponse');

    $attach_path = ROOT_PATH.$att->getStorageBasePath();
    $attach_path_ok = @is_dir($attach_path) && @is_writable($attach_path);
    $CanAttachFiles = $attach_path_ok &&
                      (($form['submit_type'] == SUBMIT_DB) ||
                       ($form['submit_type'] == SUBMIT_EMAIL_DB)) &&
                      ($this->can_admin ||
                       (!empty($this->settings['form_attachment_usergroups']) &&
                        !empty($userinfo['usergroupids']) &&
                       @array_intersect($userinfo['usergroupids'],
                        $this->settings['form_attachment_usergroups'])));
    //v1.3.0: temp. storage of "File" fields to apply response_id later
    $fields_uploaded = array();

    $tz_arr = array();
    if($gettz = $DB->query("SELECT varname, IFNULL(customphrase,'') customphrase, defaultphrase
                           FROM {adminphrases} WHERE varname LIKE 'timezone_gmt%%'
                           ORDER BY adminphraseid"))
    while($tz = $DB->fetch_array($gettz,null,MYSQL_ASSOC))
    {
      $tz_arr[$tz['varname']] = !empty($tz['customphrase']) ? $tz['customphrase'] : $tz['defaultphrase'];
    }

    $getfields = $DB->query('SELECT * FROM '.$this->_tbl.'formfield'.
                            ' WHERE form_id = %d AND active = 1'.
                            ' ORDER BY sort_order',
                            $formid);

    $formfields = $DB->fetch_array_all($getfields, MYSQL_ASSOC);

    foreach($formfields as $field)
    {
      $filename = '';
      $field_id = (int)$field['field_id'];
      $key      = $this->_pre.'_field_'.$field_id;

      switch($field['field_type'])
      {
        case FIELD_TIMEZONE:
          $val = GetVar($key, 0, 'float', true, false);
          if(($val < -12) || ($val > 12)) $val = 0;
          $tz = 'timezone_gmt_' . ($val < 0 ? 'm'.abs($val) : ($val > 0 ? 'p'.$val : '0'));
          if(isset($tz_arr[$tz]))
            $val = $tz_arr[$tz];
          else
            $val = 'GMT'.($val >= 0 ? '+' : '').$val;
          break;
        case FIELD_CHECKMULTI: //returns array
          $val = GetVar($key, array(), 'array', true, false);
          $val = implode(', ',$val);
          break;
        default:
          $val = GetVar($key, '', 'string', true, false);
      }

      // v1.3.0: process attachment for current field
      if( $CanAttachFiles &&
          FormWizard_IsFileType($field['field_type']) &&
          !empty($_FILES[$key]['name']))
      {
        $att->setUserid($userinfo['userid']);
        $att->setUsername(empty($userinfo['loggedin'])?'-':$userinfo['username']);
        $att->setObjectID($field['form_id']); // will be replaced with response_id after success!

        // If no extensions defined, then use the usergroup's setting
        $attachments_extensions = '';
        if(!empty($field['allowed_fileext']))
        {
          $attachments_extensions = $field['allowed_fileext'];
        }
        else
        if(!empty(SDProfileConfig::$usergroups_config[$userinfo['usergroupid']]['attachments_extensions']))
        {
          $attachments_extensions = SDProfileConfig::$usergroups_config[$userinfo['usergroupid']]['attachments_extensions'];
        }
        $att->setValidExtensions($attachments_extensions);

        // If no max. filesize defined, then use the usergroup's setting
        $attachments_max_size = 0;
        if(!empty($field['max_filesize']))
        {
          $attachments_max_size = (int)$field['max_filesize'];
        }
        else
        if(!empty(SDProfileConfig::$usergroups_config[$userinfo['usergroupid']]['attachments_max_size']))
        {
          $attachments_max_size = SDProfileConfig::$usergroups_config[$userinfo['usergroupid']]['attachments_max_size'];
        }
        $att->setMaxFilesizeKB($attachments_max_size * 1024);

        $result = $att->UploadAttachment($_FILES[$key]);
        if(empty($result['id']))
        {
          if($field['validator_type'] == VALIDATOR_NOT_EMPTY)
          {
            $errors[] = $result['error'];
          }
        }
        else
        {
          $val = (int)$result['id'];
          $fields_uploaded[] = $val;
          $filename = $_FILES[$key]['name'];
        }
       }
       else
       // If this is the email address field
       // validate it and then save it for later
       if(($field['field_type'] == FIELD_EMAIL) && !isset($emailField))
       {
         $result = $this->ValidateField(VALIDATOR_EMAIL, $field['label'], $val);
         if(strlen($result) > 0)
           $errors[] = $result;
         else
           $emailField = $val;
       }
       else
       // Validate where required...
       if(!empty($field['validator_type']))
       {
         $result = $this->ValidateField($field['validator_type'], $field['label'], $val);
         if(strlen($result) > 0) $errors[] = $result;
       }

       $values[$field['field_id']] = array($val, $field['name'], $filename, $field['field_type']);

    } //while
    unset($att);

    if(empty($userinfo['adminaccess']))
    {
      if(!CaptchaIsValid('p'.$this->pluginid))
      {
        $errors[] = $sdlanguage['captcha_not_valid'];
      }
    }

    if(empty($errors))
    {
      //v1.2.4: optionally use user's email as sender
      //v1.2.5: if specified, use a separate sender for email
      // (only if below option is off)
      $SenderName = '';
      if(empty($this->settings['user_email_as_sender_email']) &&
         !empty($form['email_sender_id']))
      {
        $emailField = '';
        if($sender = $DB->query_first('SELECT r.email, r.name'.
           ' FROM '.$this->_tbl.'recipient r'.
           ' WHERE r.recipient_id = %d', $form['email_sender_id']))
        {
          if(!empty($sender['email']))
          {
            $emailField = $sender['email'];
            $SenderName = $sender['name'];
          }
        }
      }

      if(($form['submit_type'] == SUBMIT_DB) ||
         ($form['submit_type'] == SUBMIT_EMAIL_DB))
      {
        $response_id = $this->InsertDB($formid, $values);

        // v1.3.0: new: update attachment row's "objectid"
        // with actual response_id now
        if($CanAttachFiles && !empty($fields_uploaded))
        {
          foreach($fields_uploaded as $key => $attachment_id)
          {
            if(!empty($attachment_id) && Is_Valid_Number($attachment_id,0,1,9999999))
            $DB->query('UPDATE {attachments} SET objectid = %d'.
                       ' WHERE attachment_id = %d AND pluginid = %d',
                       $response_id, $attachment_id, $this->pluginid);
          }
          unset($fields_uploaded,$attachment_id);
        }
      }

      if(($form['submit_type'] == SUBMIT_EMAIL) ||
         ($form['submit_type'] == SUBMIT_EMAIL_DB))
      {
        if($form['sendtoall'] == 1)
        {
          if($getrecs = $DB->query('SELECT r.recipient_id, r.email, r.name'.
             ' FROM '.$this->_tbl.'recipient r'.
             ' INNER JOIN {p'.$this->pluginid.'_formrecipient} fr ON fr.recipient_id = r.recipient_id'.
             ' WHERE fr.form_id = %d'.
             ' ORDER BY r.recipient_id', $formid))
          {
            $DB->ignore_error = true;
            while($rec = $DB->fetch_array($getrecs,null,MYSQL_ASSOC))
            {
              $this->EmailForm($formid, $values, $rec['email'], $form['name'],
                               $emailField, $SenderName);
              if(!empty($response_id) && ($form['submit_type'] == SUBMIT_EMAIL_DB))
              {
                $DB->query('INSERT INTO '.$this->_tbl.'formresponserecipient VALUES (%d, %d)',
                          $response_id, $rec['recipient_id']);
              }
            }
            $DB->ignore_error = false;
          }
        }
        else
        {
          $DB->result_type = MYSQL_ASSOC;
          if($rec = $DB->query_first('SELECT r.recipient_id, r.email, r.name'.
             ' FROM '.$this->_tbl.'recipient r'.
             ' WHERE r.recipient_id = %d', $recipientid))
          {
            $this->EmailForm($formid, $values, $rec['email'], $form['name'],
                             $emailField, $SenderName);
            if(!empty($response_id) && ($form['submit_type'] == SUBMIT_EMAIL_DB))
            {
              $DB->ignore_error = true;
              $DB->query('INSERT INTO '.$this->_tbl.'formresponserecipient VALUES (%d, %d)',
                         $response_id, $rec['recipient_id']);
              $DB->ignore_error = false;
            }
          }
        }
      }

      //v1.3.1: option to display response summary (templated)
      if(!empty($this->settings['display_response_summary']))
      {
        $this->DisplayResponseSummary($values);
        return;
      }

      if(!strlen($form['success_text']))
      {
        $msg = $this->language['form_submitted'];
      }
      else
      {
        global $bbcode;
        $msg = $form['success_text'];
        if(!empty($bbcode) && ($bbcode instanceof BBCode))
        {
          $msg = $bbcode->Parse($msg);
        }
      }
      RedirectFrontPage('', $msg, 4, false);
    }
    else
    {
      $this->DisplayForm($formid, $errors);
    }

  } //ProcessForm


  // #########################################################################
  // DISPLAY A SUMMARY FORM FOR SPECIFIC RESPONSE
  // #########################################################################
  private function DisplayResponseSummary($values)
  {
    global $DB, $bbcode, $sdlanguage, $userinfo;

    $top_html = '';
    if(strlen($this->settings['response_summary_top']))
    {
      if(!empty($bbcode) && ($bbcode instanceof BBCode))
      {
        $top_html = $bbcode->Parse($this->settings['response_summary_top']);
      }
    }

    $bottom_html = '';
    if(strlen($this->settings['response_summary_bottom']))
    {
      if(!empty($bbcode) && ($bbcode instanceof BBCode))
      {
        $bottom_html = $bbcode->Parse($this->settings['response_summary_bottom']);
      }
    }

    $att = new SD_Attachment($this->pluginid,'formresponse');

    $tmpl = SD_Smarty::getNew();
    $tmpl->assign('loggedin', !empty($userinfo['loggedin']));
    $tmpl->assign('sdlanguage',$sdlanguage);
    $tmpl->assign('pluginid',  $this->pluginid);
    $tmpl->assign('settings',  $this->settings);
    $tmpl->assign('phrases',   $this->language);
    $tmpl->assign('response_top_html', $top_html);
    $tmpl->assign('response_bottom_html', $bottom_html);

    $entries = array();
    if(!empty($values) && is_array($values))
    {
      foreach($values as $key => $val)
      {
        //v1.2.7: option "email_only_filled_values"
        //v1.3.2: only check options for right types
        if(FormWizard_FieldHasOptions($val[3]))
        {
          if($getname = $DB->query_first('SELECT name'.
                        ' FROM '.$this->_tbl.'formoption'.
                        ' WHERE field_id = '.$key.
                        " AND optionvalue = '%s'",
                        $DB->escape_string($val[0])))
          {
            $val[2] = $getname['name'];
          }
        }

        if(FormWizard_IsFileType($val[3]))
          $value = (!empty($val[2]) && ($val[0]!=$val[2])?' ('.$val[2].')':'');
        else
          $value = $val[0].(!empty($val[2]) && ($val[0]!=$val[2])?' ('.$val[2].')':'');
        $entries[] = array(
          'id'    => 'p'.$this->pluginid.'_'.$key,
          'name'  => $val[1],
          'value' => $value);
      }
    }
    $tmpl->assign('responses', $entries);

    // Finally, call Smarty to display template containing the form:
    $tmpl_done = SD_Smarty::display($this->pluginid, 'form_response.tpl');
    if(!$tmpl_done && !empty($userinfo['adminaccess']))
    {
      echo '<pre>'.sd_wordwrap(sd_unhtmlspecialchars(SD_Smarty::getLastError()),60,'<br />').'</pre>';
    }

  } //DisplayResponseSummary


  // #########################################################################
  // DISPLAY RECIPIENT SELECTION
  // #########################################################################
  private function PrintRecipientSelection($formid, $showemailaddress)
  {
    global $DB;

    $text = '';
    $order = (empty($showemailaddress) || ($showemailaddress==2) ? 'r.name' : 'r.email');
    if(!$getrecs = $DB->query('SELECT r.recipient_id, r.name, r.email'.
                             ' FROM '.$this->_tbl.'recipient r'.
                             ' LEFT JOIN '.$this->_tbl.'formrecipient fr ON r.recipient_id = fr.recipient_id'.
                             ' WHERE fr.form_id = '.(int)$formid.
                             ' ORDER BY '.$order))
    {
      return $text;
    }
    if($DB->get_num_rows($getrecs) == 1)
    {
      $rec = $DB->fetch_array($getrecs,null,MYSQL_ASSOC);
      $id = 'p' . $this->pluginid . '_recipientid';
      $text = '<input type="hidden" id="'.$id.'" name="'.$id.'" value="' . $rec['recipient_id'] . '" />';
      if(empty($showemailaddress)) // Name
      {
        if(strlen($rec['name']))
          $text .= $rec['name'];
        else
          $text .= $rec['email'];
      }
      else
      if($showemailaddress == 1) // Email
      {
        $text .= $rec['email'];
      }
      else // Both
      {
        if(strlen($rec['name']))
          $text .= $rec['name'] . ' (' . $rec['email'] . ')';
        else
          $text .= $rec['email'];
      }
    }
    else
    {
      $text = '<select id="p' . $this->pluginid . '_recipientid" name="p' . $this->pluginid . '_recipientid">';
      $recipient_id = GetVar('p'.$this->pluginid.'_recipientid', 0, 'whole_number', true, false);
      while($rec = $DB->fetch_array($getrecs,null,MYSQL_ASSOC))
      {
        $text .= '<option value="'.$rec['recipient_id'].'"';

        if($recipient_id && ($recipient_id == $rec['recipient_id']))
        {
          $text .= ' selected="selected"';
        }
        $text .= '>';

        if(empty($showemailaddress)) // Name
        {
          if(strlen($rec['name']))
            $text .= $rec['name'];
          else
            $text .= $rec['email'];
       }
       else
       if($showemailaddress == 1) // Email
       {
         $text .= $rec['email'];
       }
       else // Both
       {
         if(strlen($rec['name']))
           $text .= $rec['name'] . ' (' . $rec['email'] . ')';
         else
           $text .= $rec['email'];
       }
       $text .= "</option>\n";
     }
     $text .= "</select>\n";
    }

    return $text;

  } //PrintRecipientSelection

} // END CLASS
} // DO NOT REMOVE


// ############################################################################
// DISPLAY
// ############################################################################

// Start Class
$FormWizard = new FormWizard($pluginid);

$DB->result_type = MYSQL_ASSOC;
$getforms = $DB->query("SELECT fc.form_id, IFNULL(f.access_view,'')".
                       ' FROM {p'.$FormWizard->pluginid.'_formcategory} fc'.
                       ' INNER JOIN {p'.$FormWizard->pluginid.'_form} f ON fc.form_id = f.form_id'.
                       ' WHERE fc.category_id = %d'.
                       ' AND f.active = 1'.
                       ' ORDER BY f.date_created DESC', $categoryid);
$fvalid = false;
while($thisForm = $DB->fetch_array($getforms))
{
  $formid  = empty($thisForm['form_id']) ? false : (int)$thisForm['form_id'];
  $fgroups = isset($thisForm['access_view'])?sd_ConvertStrToArray($thisForm['access_view'],'|'):array();
  /*
  if($FormWizard->can_admin)
  {
    echo '<br />access_view: '.$thisForm['access_view'];
    echo '<br />$fgroups: '.var_export($fgroups,true);
  }
  */
  if( $FormWizard->can_admin || empty($fgroups) ||
      in_array($userinfo['usergroupid'], $fgroups) ||
      (!empty($userinfo['usergroupids']) &&
       @array_intersect($userinfo['usergroupids'], $fgroups)) )
  {
    $fvalid = true;
    break;
  }
}

if(!$fvalid)
{
  if($FormWizard->can_admin)
  {
    DisplayMessage($FormWizard->language['must_alloc_cat'].' (page ID '.$categoryid.')',true);
  }
}
else
if($FormWizard->can_admin || $FormWizard->can_submit)
{
  $action = GetVar('action', '', 'string');
  if($action == 'processform')
  {
    $FormWizard->ProcessForm();
  }
  else
  {
    $FormWizard->DisplayForm($formid);
  }
}
else
{
  echo $FormWizard->language['msg_no_submit'];
}

unset($FormWizard, $fgroups, $formid, $plugin_folder);
