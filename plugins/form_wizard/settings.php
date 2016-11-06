<?php
$standalone = false;
if(!defined('ROOT_PATH'))
{
  // #############################################################
  // v1.3.2: Ajax pre-run
  // #############################################################
  $standalone = true;
  // Assume regular admin menu request, normal bootstrap required:
  define('IN_PRGM', true);
  define('IN_ADMIN', true);
  define('ROOT_PATH', '../../');
  require(ROOT_PATH . 'includes/init.php');
  if(!Is_Ajax_Request())
  {
    exit('No access!');
  }
}

if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

require_once(SD_INCLUDE_PATH.'class_sd_attachment.php');

// ############################################################################
// SETTINGS CLASS
// ############################################################################
class FormWizardSettings
{
  public  $pluginid      = 0;
  public  $pluginfolder = '';
  public  $settings      = array();
  private $_action       = '';
  private $_formid       = 0;
  private $_fieldid      = 0;
  private $_responseid   = 0;
  private $_recipientid  = 0;
  private $_pre          = '';
  private $_tbl          = '';
  private $_page         = '';
  private $_InitOK       = false;
  private $_name         = '';

  public function FormWizardSettings()
  {
    global $plugin_names, $refreshpage;

    $this->_InitOK = false;
    if(!$plugin_folder = sd_GetCurrentFolder(__FILE__)) return;
    require_once(ROOT_PATH.'plugins/'.$plugin_folder.'/lib.php');

    if(!$this->pluginid = GetPluginIDbyFolder($plugin_folder)) return;
    if(!isset($plugin_names[$this->pluginid])) return false;

    $this->_name = strip_alltags($plugin_names[$this->pluginid]);
    $this->_page = str_replace(array('&amp;load_wysiwyg=1','&load_wysiwyg=1'),
                               array('',''), $refreshpage);
    $this->pluginfolder = sd_GetCurrentFolder(__FILE__);
    $this->_pre = 'p'.$this->pluginid;
    $this->_tbl = PRGM_TABLE_PREFIX.$this->_pre.'_';
    $this->settings = GetPluginSettings($this->pluginid);

    // get parameters
    $this->_action = GetVar('action', 'displaydefaults', 'string');
    $this->_formid = Is_Valid_Number(GetVar('formid', 0, 'whole_number'),0,0);
    $this->_fieldid = Is_Valid_Number(GetVar('fieldid', 0, 'whole_number'),0,0);
    $this->_responseid = Is_Valid_Number(GetVar('responseid', 0, 'whole_number'),0,0);
    $this->_recipientid = Is_Valid_Number(GetVar('recipientid', 0, 'whole_number'),0,0);

    $this->_InitOK = true;
  }

  // ############################
  // GENERAL FUNCTIONS
  // ############################

  public function CheckResponseAttachment($response_id, $field_id)
  {
    /* v1.3.2, 2013-09-04:
    This function checks, if a given attachment no longer exists and thus the
    referencing form response field must be reset since the ajax'ed attachment
    delete does not do anything to Form Builder rows, obviously.
    E.g. it may have "42" as value, but needs to be empty
    */

    global $DB;

    // check params for sane values
    if(empty($response_id) || empty($field_id) ||
       !is_numeric($response_id) || !is_numeric($field_id))
    {
      return false;
    }
    if(!$response = $DB->query_first(
       'SELECT IFNULL(value,0) value, ff.field_type
        FROM {p'.$this->pluginid.'_formresponsefields} rf
        INNER JOIN {p'.$this->pluginid.'_formfield} ff ON ff.field_id = rf.field_id
        WHERE rf.response_id = %d AND rf.field_id = %d',
        $response_id, $field_id))
    {
      return false;
    }
    $attachment_id = (int)$response['value'];
    if(($attachment_id < 1) || !FormWizard_IsFileType($response['field_type']))
    {
      return false;
    }

    // if attachment still exists, then bail out
    $att = new SD_Attachment($this->pluginid,'formresponse');
    $a = $att->FetchAttachmentEntry($response_id, $attachment_id);
    if(!empty($a)) return false;

    // now reset the value for that response
    $DB->query('UPDATE {p'.$this->pluginid."_formresponsefields}
                SET value = ''
                WHERE response_id = %d AND field_id = %d",
                $response_id, $field_id);

    return true;
  } //CheckResponseAttachment


  public function ConvertSubmitType($submit_to)
  {
    if(empty($submit_to)) return AdminPhrase('none');
    switch($submit_to)
    {
      case SUBMIT_DB:       return AdminPhrase('submit_type_db');
      case SUBMIT_EMAIL_DB: return AdminPhrase('submit_type_both');
      case SUBMIT_EMAIL:
      default:              return AdminPhrase('submit_type_email');
    }
  } //ConvertSubmitType


  public function ConvertFieldType($field)
  {
    $field['field_type'] = empty($field['field_type'])?FIELD_TEXT:(int)$field['field_type'];
    $allowed_fileext = empty($field['allowed_fileext'])?'':' ('.$field['allowed_fileext'].')';
    $ft = 'Text';
    switch($field['field_type'])
    {
      case FIELD_TEXT:      $ft = AdminPhrase('create_field_type_text'); break;
      case FIELD_TEXTAREA:  $ft = AdminPhrase('create_field_type_textarea'); break;
      case FIELD_SELECT:    $ft = AdminPhrase('create_field_type_select'); break;
      case FIELD_CHECKBOX:  $ft = AdminPhrase('create_field_type_checkbox'); break;
      case FIELD_EMAIL:     $ft = AdminPhrase('create_field_type_email'); break;
      case FIELD_FILE:      $ft = AdminPhrase('create_field_type_file'); break;
      case FIELD_MUSIC:     $ft = AdminPhrase('create_field_type_music').$allowed_fileext; break;
      case FIELD_IMAGE:     $ft = AdminPhrase('create_field_type_image').$allowed_fileext; break;
      case FIELD_ARCHIVE:   $ft = AdminPhrase('create_field_type_archive').$allowed_fileext; break;
      case FIELD_DOCUMENTS: $ft = AdminPhrase('create_field_type_documents').$allowed_fileext; break;
      case FIELD_DATE:      $ft = AdminPhrase('create_field_type_date'); break;
      case FIELD_BBCODE:    $ft = AdminPhrase('create_field_type_bbcode'); break;
      case FIELD_RADIO:     $ft = AdminPhrase('create_field_type_radio'); break;
      case FIELD_TIME:      $ft = AdminPhrase('create_field_type_time'); break;
      case FIELD_CHECKMULTI:$ft = AdminPhrase('create_field_type_checkboxes'); break;
      case FIELD_TIMEZONE:  $ft = AdminPhrase('create_field_type_timezone'); break;
    }
    return $ft;
  } //ConvertFieldType


  public function ConvertValidatorType($validator_type)
  {
    $validator_type = empty($validator_type)?0:(int)$validator_type;
    switch($validator_type)
    {
      case VALIDATOR_NOT_EMPTY: return AdminPhrase('create_field_validator_type_empty');
      case VALIDATOR_EMAIL:     return AdminPhrase('create_field_validator_type_email');
      case VALIDATOR_URL:       return AdminPhrase('create_field_validator_type_url');
      case VALIDATOR_NUMBER:    return AdminPhrase('create_field_validator_type_number');
      //v1.3.0:
      case VALIDATOR_INTEGER:   return AdminPhrase('create_field_validator_type_int');
      case VALIDATOR_WHOLE_NUM: return AdminPhrase('create_field_validator_type_whole');
    }

    return AdminPhrase('create_field_validator_type_none');

  } //ConvertValidatorType


  function DisplayBreadcrumb($addon_arr = null)
  {
    global $DB;
    
    return;

    echo '<ol class="breadcrumb">'.
         '<li><a href="'.$this->_page.'">'.$this->_name.'</a></li>';

    if(!empty($this->_formid) && ($this->_formid > 0))
    {
      if($formname = $DB->query_first('SELECT name FROM '.$this->_tbl.'form'.
                                      ' WHERE form_id = '.$this->_formid.
                                      ' LIMIT 1'))
      {
        echo ' <li><a href="'.$this->_page.'&amp;action=displayform&amp;formid='.$this->_formid.'">' .
             (isset($formname['name']) ? $formname['name'] : AdminPhrase('form_untitled')).'</a></li>';
      }
    }
    if(!empty($addon_arr))
    {
      if(is_array($addon_arr))
      {
        foreach($addon_arr as $link => $title)
        {
          echo ' <li><a href="'.$this->_page.'&amp;formid='.$this->_formid.$link.'">'.$title.'</a></li>';
        }
      }
      else
      if(is_string($addon_arr))
      {
        echo ' <li>'.$addon_arr . '</li>';
      }
    }
    echo '</ol>';

  } //DisplayBreadcrumb


  // ############################
  // FORM FUNCTIONS
  // ############################

  function InsertFieldOption()
  {
    global $DB, $sdlanguage;

    if(!CheckFormToken())
    {
      RedirectPage($this->_page,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      return false;
    }

    $order       = Is_Valid_Number(GetVar('displayorder', 0, 'natural_number',true,false),0,0,999999);
    $name        = sd_substr(GetVar('name', '', 'string',true,false),0,128);
    $optionvalue = sd_substr(GetVar('optionvalue', '', 'string',true,false),0,128);

    if(!strlen($name) || !strlen($optionvalue) ||
       empty($this->_formid) || empty($this->_fieldid))
    {
      echo '<strong>' . AdminPhrase('err_opt_value') . '</strong><br /><br />';
      $this->DisplayField($this->_formid, $this->_fieldid);
      return;
    }

    $DB->result_type = MYSQL_ASSOC;
    $count = $DB->query_first('SELECT option_id FROM '.$this->_tbl.'formoption'.
                              " WHERE field_id = %d AND name = '%s'".
                              ' LIMIT 1',
                              $this->_fieldid, $name);
    if(!empty($count['option_id']))
    {
      echo '<strong>' . AdminPhrase('err_opt_exists') . '</strong><br /><br />';
      $this->DisplayField($this->_formid, $this->_fieldid);
      return;
    }

    $DB->query('INSERT INTO '.$this->_tbl.'formoption'.
               ' (option_id, field_id, name, optionvalue, displayorder)'.
               " VALUES (NULL, %d, '%s', '%s', %d)",
               $this->_fieldid, $name, $optionvalue, $order);

    RedirectPage($this->_page.'&amp;action=displayfield&amp;formid='.
                 $this->_formid.'&amp;fieldid='.$this->_fieldid);

  } //InsertFieldOption


  function UpdateFieldOptions()
  {
    global $DB, $sdlanguage;

    if(!CheckFormToken())
    {
      RedirectPage($this->_page,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      return false;
    }

    $optids = GetVar('optids', array(), 'array', true, false);
    $optorder = GetVar('optorder', array(), 'array', true, false);
    if( ($this->_formid > 0) && ($this->_fieldid > 0) &&
        (!empty($optids) || !empty($optorder)) )
    {
      // First update display order of options
      foreach($optorder as $id => $order)
      {
        $order = Is_Valid_Number($order,0,0,999999);
        if(!empty($id) && ($id = Is_Valid_Number($id,0,1)))
        {
          $DB->query('UPDATE '.$this->_tbl.'formoption'.
                     ' SET displayorder = %d'.
                     ' WHERE field_id = %d AND option_id = %d',
                     $order, $this->_fieldid, $id);
        }
        else break; //error
      }
      // Second, remove selected options
      for($i = 0; $i < count($optids); $i++)
      {
        if(!empty($optids[$i]) && ($oid = Is_Valid_Number($optids[$i],0,1)))
        {
          $DB->query('DELETE FROM '.$this->_tbl.'formoption'.
                     ' WHERE field_id = %d AND option_id = %d',
                     $this->_fieldid, $oid);
        }
        else break; //error
      }
    }

    RedirectPage($this->_page.'&action=displayfield&formid='.
                  $this->_formid.'&amp;fieldid='.$this->_fieldid);

  } //UpdateFieldOptions


  function DisplayFieldOptions($formid, $fieldid)
  {
    global $DB;

    if(empty($formid) || empty($fieldid)) return;

    echo '
    <form method="post" action="'.$this->_page.'">
    <input type="hidden" name="action" value="insertfieldoption" />
    <input type="hidden" name="formid" value="' . $formid . '" />
    <input type="hidden" name="fieldid" value="' . $fieldid . '" />
    '.PrintSecureToken();

    StartSection(AdminPhrase('section_select_values'));

    echo '
    <table width="100%" border="0" cellpadding="5" cellspacing="0">
    <tr>
      <td class="td1" colspan="2"><strong>' . AdminPhrase('add_value') . '</strong></td>
    </tr>
    <tr>
      <td class="td2" width="200px">
        ' . AdminPhrase('add_option_order_desc') . '
      </td>
      <td class="td3" align="left">
        <input type="text" name="displayorder" size="5" />
      </td>
    </tr>
    <tr>
      <td class="td2" width="50%">
        ' . AdminPhrase('add_option_name_desc') . '
      </td>
      <td class="td3" align="left">
        <input type="text" name="name" style="width:98%" />
      </td>
    </tr>
    <tr>
      <td class="td2" width="50%">
        ' . AdminPhrase('add_option_value_desc') . '
      </td>
      <td class="td3" align="left">
        <input type="text" name="optionvalue" style="width:98%" />
     </td>
    </tr>
    <tr>
      <td class="td2" colspan="2" align="center">
      <input class="btn btn-primary" type="submit" value="' . AdminPhrase('add_value_but') . '" />
     </td>
    </tr>
    </table>
    </form>
    ';

    echo '
    <form action="'.$this->_page.'" method="post">
    <input type="hidden" name="action" value="updatefieldoptions" />
    <input type="hidden" name="formid" value="' . $formid . '" />
    <input type="hidden" name="fieldid" value="' . $fieldid . '" />
    '.PrintSecureToken().'
    <table width="100%" border="0" cellpadding="5" cellspacing="0">
    <tr>
      <td class="td1" width="90">' . AdminPhrase('option_display_order') . '</td>
      <td class="td1">' . AdminPhrase('option_name_label') . '</td>
      <td class="td1">' . AdminPhrase('option_value_label') . '</td>
      <td class="td1" align="center" width="90">' . AdminPhrase('delete_question') . '</td>
    </tr>';

    //v1.3.1: added new columns displayorder, optionvalue
    if($getopts = $DB->query('SELECT * FROM '.$this->_tbl.'formoption'.
                             ' WHERE field_id = %d'.
                             ' ORDER BY displayorder, name, optionvalue ASC',
                             $fieldid))
    {
      while($opt = $DB->fetch_array($getopts,null,MYSQL_ASSOC))
      {
        echo '
      <tr>
        <td class="td2" width="90" align="center">
          <input type="text" name="optorder['.$opt['option_id'].']" value="' . $opt['displayorder'] . '" size="5" />
        </td>
        <td class="td2">' . strip_tags($opt['name']) .'</td>
        <td class="td2">' . strip_tags($opt['optionvalue']) .'</td>
        <td class="td2" align="center">
          <input type="checkbox" name="optids[]" value="'.$opt['option_id'].'" />
        </td>
      </tr>';
      }
      echo '
      <tr>
        <td class="td2" colspan="4" align="center">
          <input class="btn btn-primary" type="submit" value="'.strip_alltags(AdminPhrase('update_field_options_but')).'" />
        </td>
      </tr>';
    }
    echo '</table>';

    EndSection();

    echo '</form>';

  } //DisplayFieldOptions


  function InsertField()
  {
    global $DB, $sdlanguage;

    if(!CheckFormToken() || empty($this->_formid))
    {
      RedirectPage($this->_page,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      return false;
    }

    $name             = GetVar('name', '', 'string');
    $field_type       = Is_Valid_Number(GetVar('field_type', FIELD_TEXT, 'whole_number'),0,1,20);
    $validator_type   = GetVar('validator_type', 0, 'whole_number');
    $label            = GetVar('label', '', 'string');
    $width            = GetVar('width', '0', 'string');
    $height           = GetVar('height', '0', 'string');
    $allowed_fileext  = GetVar('allowed_fileext', '', 'string');
    $max_filesize     = Is_Valid_Number(GetVar('max_filesize', 0, 'natural_number'),0,0,1024*1024);

    $errors = array();

    // "Height" is only valid for TextArea and Select
    if(!in_array($field_type, array(FIELD_BBCODE,FIELD_TEXTAREA,FIELD_SELECT)))
    {
      $height = 0;
    }

    if(!strlen($name))
    {
      $errors[] = AdminPhrase('err_field_no_name');
    }

    if($field_type == FIELD_EMAIL)
    {
      $validator_type = VALIDATOR_EMAIL;
      if($existing = $DB->query_first('SELECT 1 FROM '.$this->_tbl.'formfield'.
                                      ' WHERE form_id = %d AND field_type = %d'.
                                      ' LIMIT 1',
                                      $this->_formid, $field_type))
      {
        $errors[] = AdminPhrase('err_field_dup_email');
      }
    }

    if(empty($errors))
    {
      $sort_order = $DB->query_first('SELECT MAX(sort_order) sorto'.
                                     ' FROM '.$this->_tbl.'formfield'.
                                     ' WHERE form_id = '.$this->_formid);
      $sort_order = 1 + (empty($sort_order['sorto'])?0:(int)$sort_order['sorto']);

      $DB->query('INSERT INTO '.$this->_tbl."formfield
      (field_id,form_id,field_type,name,validator_type,label,width,height,
       sort_order,active,date_created,allowed_fileext,max_filesize)
      VALUES (NULL, %d, %d, '%s', %d, '%s', '%s', '%s',
      %d, 1, %d, '%s', %d)",
      $this->_formid, $field_type, $name, $validator_type, $label, $width, $height,
      $sort_order, TIME_NOW, $allowed_fileext,$max_filesize);

      // For certain field types additional "options/values" can be added:
      $this->_action = $this->_page.'&amp;action=';
      if(FormWizard_FieldHasOptions($field_type))
      {
        RedirectPage($this->_action.'displayfield&amp;formid='.$this->_formid.
                     '&amp;fieldid=' . $DB->insert_id());
      }
      else
      {
        RedirectPage($this->_action.'displayformfields&amp;formid='.$this->_formid);
      }
      return;
    }

    PrintErrors($errors);

    $this->DisplayField($this->_formid);

  } //InsertField


  function UpdateField()
  {
    global $DB, $sdlanguage;

    if(!CheckFormToken())
    {
      RedirectPage($this->_page,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      return false;
    }
    if(($this->_fieldid < 1) || ($this->_formid < 1)) return false;

    $name             = sd_substr(GetVar('name', '', 'string'),0,128);
    $label            = sd_substr(GetVar('label', '', 'string'),0,128);
    $field_type       = Is_Valid_Number(GetVar('field_type', FIELD_TEXT, 'whole_number'),0,1,20);
    $validator_type   = Is_Valid_Number(GetVar('validator_type', 0, 'whole_number'),0,1,10);
    $width            = GetVar('width', '0', 'string');
    $height           = GetVar('height', '0', 'string');
    $sort_order       = GetVar('sort_order', 0, 'natural_number');
    $active           = (GetVar('active', 0, 'bool')?1:0);
    $allowed_fileext  = GetVar('allowed_fileext', '', 'string');//v1.3.0
    $max_filesize     = Is_Valid_Number(GetVar('max_filesize', 0, 'natural_number'),0,0,1024*1024);//v1.3.0

    $errors = array();

    // "Height" is only valid for TextArea and Select
    if(!in_array($field_type,array(FIELD_TEXTAREA,FIELD_BBCODE,FIELD_SELECT)))
    {
      $height = '0';
    }

    if(!strlen($name))
    {
      $errors[] = AdminPhrase('err_field_no_name');
    }

    if($field_type == FIELD_EMAIL)
    {
      $validator_type = VALIDATOR_EMAIL;
    }

    if(empty($errors))
    {
      $DB->query('UPDATE '.$this->_tbl."formfield
      SET field_type      = $field_type,
          name            = '$name',
          validator_type  = $validator_type,
          label           = '$label',
          width           = '$width',
          height          = '$height',
          sort_order      = $sort_order,
          active          = $active,
          allowed_fileext = '$allowed_fileext',
          max_filesize    = '$max_filesize'".
      ' WHERE field_id = '.$this->_fieldid.
      ' AND form_id = '.$this->_formid);

      $action = $this->_page.'&amp;action=';
      if(FormWizard_FieldHasOptions($field_type))
      {
        RedirectPage($action.'displayfield&amp;formid='.$this->_formid.'&amp;fieldid='.$this->_fieldid);
      }
      else
      {
        RedirectPage($action.'displayformfields&amp;formid='.$this->_formid);
      }
      return;
    }

    PrintErrors($errors);

    $this->DisplayField($this->_formid, $this->_fieldid);

  } //UpdateField


  function SortFields()
  {
    global $DB;

    $currentSort = GetVar('currentSort', 1, 'natural_number');
    $maxSort     = GetVar('maxSort', 1, 'natural_number');
    $direction   = GetVar('direction', '', 'string');

    if($direction == 'down')
    {
      $newSort = ($currentSort > 1 ? ($currentSort - 1) : $maxSort);
    }
    else
    {
      $newSort = ($currentSort < $maxSort ? ($currentSort + 1) : 1);
    }

    // Update the one we are swapping with
    $DB->query('UPDATE '.$this->_tbl.'formfield'.
               ' SET sort_order = %d'.
               ' WHERE form_id = %d AND sort_order = %d',
               $currentSort, $this->_formid, $newSort);

    // Update the one we are moving
    $DB->query('UPDATE '.$this->_tbl.'formfield'.
               ' SET sort_order = %d'.
               ' WHERE field_id = %d',
               $newSort, $this->_fieldid);

    RedirectPage($this->_page.'&amp;action=displayformfields&amp;formid='.$this->_formid);

  } //SortFields


  function DisplayField($formid, $fieldid=0)
  {
    global $DB;

    $this->DisplayBreadcrumb(array('&amp;action=displayformfields' => AdminPhrase('fields')));

    if(empty($formid) || ($formid < 1))
    {
      echo '<h2>' . AdminPhrase('err_param') . '</h2>';
      RedirectPage($this->_page, 2);
      return;
    }

    echo '
    <form method="post" action="'.$this->_page.'" class="form-horizontal">
    '.PrintSecureToken();

    if($fieldid > 0)
    {
      $DB->result_type = MYSQL_ASSOC;
      $field = $DB->query_first('SELECT * FROM '.$this->_tbl.'formfield'.
                                ' WHERE form_id = %d AND field_id = %d',
                                $formid, $fieldid);

      StartSection(AdminPhrase('section_update_field'));
      echo '
      <input type="hidden" name="formid" value="' . $formid . '" />
      <input type="hidden" name="fieldid" value="' . $fieldid . '" />
      <input type="hidden" name="sort_order" value="' . $field['sort_order'] . '" />
      <input type="hidden" name="action" value="updatefield" />';

      $buttonTitle = AdminPhrase('update_field_but');
    }
    else
    {
      StartSection(AdminPhrase('section_add_field'));
      echo '
      <input type="hidden" name="formid" value="' . $formid . '" />
      <input type="hidden" name="action" value="insertfield" />';

      $buttonTitle = AdminPhrase('create_field_but');

      $field = array(
        'field_id'       => -1,
        'form_id'        => $formid,
        'field_type'     => FIELD_TEXT,
        'name'           => '',
        'validator_type' => 0,
        'label'          => '',
        'width'          => 0,
        'height'         => 0,
        'sort_order'     => 0,
        'active'         => 1,
        'date_created'   => 0,
        'allowed_fileext'=> '',
        'max_filesize'   => 0,
      );
    }

    $field['field_type'] = empty($field['field_type'])?0:(int)$field['field_type'];
    $isFile = FormWizard_IsFileType($field['field_type']);

    echo '
    <table width="100%" border="0" cellpadding="5" cellspacing="0">
    <tr><td class="td1" colspan="2"><strong>' . AdminPhrase('create_field_details') . '</strong>:</td></tr>
    <tr>
      <td class="td2" width="50%"><strong>' . AdminPhrase('create_field_name') . '</strong>:</td>
      <td class="td3" valign="top">
        <input type="text" name="name" size="50" maxlength="64" value="' .$field['name'] . '" style="width:98%" />
      </td>
    </tr>
    <tr>
      <td class="td2" width="50%"><strong>' . AdminPhrase('create_field_label') . '</strong>:<br />
        ' . AdminPhrase('create_field_label_desc') . '
      </td>
      <td class="td3" valign="top">
        <input type="text" name="label" size="50" maxlength="64" value="' .$field['label'] . '" style="width:98%" />
      </td>
    </tr>
    <tr>
      <td class="td2" width="50%"><strong>' . AdminPhrase('create_field_type') . '</strong>:</td>
      <td class="td3" valign="top">
        <select id="field_type" name="field_type">
        <option value="' . FIELD_TEXT . '" '     . ($field['field_type'] == FIELD_TEXT ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_type_text') . '</option>
        <option value="' . FIELD_BBCODE . '" '   . ($field['field_type'] == FIELD_BBCODE? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_type_bbcode') . '</option>
        <option value="' . FIELD_TEXTAREA . '" ' . ($field['field_type'] == FIELD_TEXTAREA ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_type_textarea') . '</option>
        <option value="' . FIELD_DATE . '" '     . ($field['field_type'] == FIELD_DATE ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_type_date') . '</option>
        <option value="' . FIELD_TIME . '" '     . ($field['field_type'] == FIELD_TIME ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_type_time') . '</option>
        <option value="' . FIELD_SELECT . '" '   . ($field['field_type'] == FIELD_SELECT ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_type_select') . '</option>
        <option value="' . FIELD_CHECKBOX . '" ' . ($field['field_type'] == FIELD_CHECKBOX ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_type_checkbox') . '</option>
        <option value="' . FIELD_CHECKMULTI.'" ' . ($field['field_type'] == FIELD_CHECKMULTI ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_type_checkboxes') . '</option>
        <option value="' . FIELD_RADIO. '" '     . ($field['field_type'] == FIELD_RADIO ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_type_radio') . '</option>
        <option value="' . FIELD_EMAIL . '" '    . ($field['field_type'] == FIELD_EMAIL ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_type_email') . '</option>
        <option value="' . FIELD_FILE . '" '     . ($field['field_type'] == FIELD_FILE ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_type_file') . '</option>
        <option value="' . FIELD_IMAGE . '" '    . ($field['field_type'] == FIELD_IMAGE ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_type_image') . '</option>
        <option value="' . FIELD_MUSIC . '" '    . ($field['field_type'] == FIELD_MUSIC ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_type_music') . '</option>
        <option value="' . FIELD_ARCHIVE . '" '  . ($field['field_type'] == FIELD_ARCHIVE ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_type_archive') . '</option>
        <option value="' . FIELD_DOCUMENTS . '" '. ($field['field_type'] == FIELD_DOCUMENTS ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_type_documents') . '</option>
        <option value="' . FIELD_TIMEZONE . '" ' . ($field['field_type'] == FIELD_TIMEZONE? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_type_timezone') . '</option>
        </select>

        <div id="filesettings"'.($isFile?'':' style="display: none"').'>
          '.AdminPhrase('create_field_allowed_fileext').'
          <input type="text" id="allowed_fileext" name="allowed_fileext" size="40" maxlength="128" value="'.
          (isset($field['allowed_fileext'])?$field['allowed_fileext']:'').'" />
          <br />
          '.AdminPhrase('create_field_allowed_filesize').'
          <input type="text" id="max_filesize" name="max_filesize" size="6" maxlength="6" value="'.
          (isset($field['max_filesize'])?(int)$field['max_filesize']:'').'" />
        </div>
      </td>
    </tr>
    <tr>
      <td class="td2" width="50%"><strong>' . AdminPhrase('create_field_validator_type') . '</strong>:</td>
      <td class="td3" valign="top">
        <select name="validator_type">
        <option value="" ' . (empty($field['validator_type']) ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_validator_type_none') . '</option>
        <option value="' . VALIDATOR_NOT_EMPTY . '" ' . ($field['validator_type'] == VALIDATOR_NOT_EMPTY ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_validator_type_empty') . '</option>
        <option value="' . VALIDATOR_EMAIL . '" '     . ($field['validator_type'] == VALIDATOR_EMAIL ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_validator_type_email') . '</option>
        <option value="' . VALIDATOR_URL . '" '       . ($field['validator_type'] == VALIDATOR_URL ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_validator_type_url') . '</option>
        <option value="' . VALIDATOR_WHOLE_NUM . '" ' . ($field['validator_type'] == VALIDATOR_WHOLE_NUM ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_validator_type_whole') . '</option>
        <option value="' . VALIDATOR_INTEGER . '" '   . ($field['validator_type'] == VALIDATOR_INTEGER ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_validator_type_int') . '</option>
        <option value="' . VALIDATOR_NUMBER . '" '    . ($field['validator_type'] == VALIDATOR_NUMBER ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_validator_type_number') . '</option>
        </select>
      </td>
    </tr>
    <tr>
      <td class="td2" width="50%"><strong>' . AdminPhrase('create_field_width') . '</strong>:<br /><br />' .
        AdminPhrase('create_field_width_desc') . '<br /></td>
      <td class="td3" valign="top">
        <input type="text" name="width" size="5" maxlength="5" value="' . $field['width'] . '" />
      </td>
    </tr>
    <tr>
      <td class="td2" width="50%"><strong>' . AdminPhrase('create_field_height') . '</strong>:<br /><br />' .
        AdminPhrase('create_field_height_desc') . '<br /></td>
      <td class="td3" valign="top">
        <input type="text" name="height" size="5" maxlength="5" value="' . $field['height'] . '" />
      </td>
    </tr>';

    if($fieldid > 0)
    {
      echo '
    <tr>
      <td class="td2" width="50%"><strong>' . AdminPhrase('create_field_online_question') . '</strong></td>
      <td class="td3" valign="top">
        <select name="active">
        <option value="1" ' . (!empty($field['active']) ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_online_yes') . '</option>
        <option value="0" ' . ( empty($field['active']) ? 'selected="selected"' : '') . '>' . AdminPhrase('create_field_online_no') . '</option>
        </select>
      </td>
    </tr>';
    }
    echo   '
    <tr>
      <td class="td2" colspan="2" align="center">
        <input class="btn btn-primary" type="submit" value="' . strip_alltags($buttonTitle) . '" />
      </td>
    </tr>
    </table>';

    EndSection();

    echo '
    </form>';

    // If this is a field which can have multiple options:
    if(FormWizard_FieldHasOptions($field['field_type']))
    {
      $this->DisplayFieldOptions($field['form_id'], $fieldid);
    }

  } //DisplayField


  function DeleteFields()
  {
    global $DB, $sdlanguage;

    if(!CheckFormToken())
    {
      RedirectPage($this->_page,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      return false;
    }

    $fieldids = GetVar('fieldids', false, 'array');

    if(!empty($fieldids) && ($this->_formid > 0))
    for($i = 0; $i < count($fieldids); $i++)
    {
      if(!empty($fieldids[$i]) && ($fid = Is_Valid_Number($fieldids[$i],0,1)))
      {
        $DB->query('DELETE FROM '.$this->_tbl.'formfield'.
                   ' WHERE field_id = %d'.
                   ' AND form_id = %d',
                   $fid, $this->_formid);
      }
      else break; //error!
    }

    RedirectPage($this->_page.'&amp;action=displayformfields&amp;formid='.$this->_formid);

  } //DeleteFields


  function DisplayFormFields($formid)
  {
    global $DB, $sdlanguage;

    $this->DisplayBreadcrumb(AdminPhrase('section_fields_for_form'));
	
	echo'<div clas="align-left">
        <a class="btn btn-success btn-sm" href="'.$this->_page.'&amp;action=displayfield&amp;formid='.
        $formid.PrintSecureUrlToken().'"><i class="ace-icon fa fa-plus"></i> '.AdminPhrase('add_field').'</a>
	</div><br />';
     

    echo '
    <form action="'.$this->_page.'" method="post" id="sortform">
    <input type="hidden" id="action" name="action" value="deletefields" />
    <input type="hidden" id="formid" name="formid" value="' . $formid . '" />
    <input type="hidden" id="fieldid" name="fieldid" value="" />
    <input type="hidden" id="currentSort" name="currentSort" value="" />
    <input type="hidden" id="direction" name="direction" value="down" />
    '.PrintSecureToken();

    StartSection(AdminPhrase('section_fields_for_form') . ' - "' .
                             $this->GetFormName($formid) . '"');

    echo '
    <table class="table table-bordered table-striped">
	<thead>';

    $maxSort = 0;
    $count = 0;
    if($getfields = $DB->query('SELECT * FROM '.$this->_tbl.'formfield'.
                               ' WHERE form_id = %d ORDER BY sort_order ASC',
                               $formid))
    while($field = $DB->fetch_array($getfields,null,MYSQL_ASSOC))
    {
      if($count==0)
      {
        echo '
	  
      <tr>
        <th class="td1">'.AdminPhrase('fields_col_field').'</th>
        <th class="td1">'.AdminPhrase('fields_col_type').'</th>
        <th class="td1">'.AdminPhrase('fields_col_validator').'</th>
        <th class="td1" align="center" width="50">'.AdminPhrase('fields_col_active').'</th>
        <th class="td1" width="80">'.AdminPhrase('fields_col_sortorder').'</th>
        <th class="td1" width="160">'.AdminPhrase('fields_col_date').'</th>
        <th class="td1" align="center">'.AdminPhrase('fields_col_delete').'</th>
      </tr>
	  </thead>';
      }
      echo '
      <tr>
        <td class="td2"><a href="'.$this->_page.'&amp;action=displayfield&amp;formid='.
          $field['form_id'].'&amp;fieldid='.$field['field_id'].'"><b>'.$field['label'].'</b>'.
          ($field['name']!=$field['label']?' (<i>'.$field['name'].'</i>)':'').'</a></td>
        <td class="td2">' . $this->ConvertFieldType($field) . '&nbsp;</td>
        <td class="td2">' . $this->ConvertValidatorType($field['validator_type']) . '&nbsp;</td>
        <td class="td2" align="center">' . (!empty($field['active']) ? $sdlanguage['yes'] : $sdlanguage['no']) . '</td>
        <td class="td2" align="center">
          <input type="button" value="&uarr;" onclick="SortList(' . $field['field_id'] . ',' . $field['sort_order'] . ',\'down\');" />
          <input type="button" value="&darr;" onclick="SortList(' . $field['field_id'] . ',' . $field['sort_order'] . ',\'up\');" />
        </td>
        <td class="td2">'. DisplayDate($field['date_created']) .'</td>
        <td class="td2" align="center">
          <input type="checkbox" name="fieldids[]" value="' . $field['field_id'] . '" />
        </td>
      </tr>
	  </table>';

      $maxSort = $field['sort_order'];
      $count++;
    } //while

    if(!empty($count))
    {
      echo '
      <div class="align-right">
          <button class="btn btn-danger" type="submit" value="'.AdminPhrase('btn_delete').'"><i class="ace-icon fa fa-trash-o"></i> '.AdminPhrase('btn_delete').'</button>
       </div>';
    }
    else
    {
      echo '
      <tr>
        <td class="td1" colspan="7" align="center" style="padding: 8px;">
          '.AdminPhrase('please_add_fields').'
        </td>
      </tr>';
    }

    echo '
    <input type="hidden" name="maxSort" value="' . $maxSort . '" />';



    echo '</form>';

  } //DisplayFormFields


  function UpdateRecipients($formid, $recipientids)
  {
    global $DB, $sdlanguage;

    if(($formid < 1) || empty($recipientids)) return false;

    // Firstly delete any existing recipients
    $DB->query('DELETE FROM '.$this->_tbl.'formrecipient'.
               ' WHERE form_id = %d', $formid);

    // Add the new ones
    if(!empty($recipientids) && is_array($recipientids))
    {
      for($i = 0; $i < count($recipientids); $i++)
      {
        if(!empty($recipientids[$i]) &&
           ($rid = Is_Valid_Number($recipientids[$i],0,1)))
        {
          $DB->query('INSERT INTO '.$this->_tbl.'formrecipient VALUES (%d, %d)',
                     $formid, $rid);
        }
        else break; //error
      }
    }

    // NO page redirect here, called from other functions!

  } //UpdateRecipients


  function InsertForm()
  {
    global $DB, $sdlanguage;

    if(!CheckFormToken())
    {
      RedirectPage($this->_page,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      return false;
    }

    // post vars
    $name             = Getvar('name', '', 'string', true, false);
    $submit_type      = Is_Valid_Number(Getvar('submit_type', 1, 'whole_number',true,false),1,1,3);
    $recipients       = Getvar('recipientids', array(), 'array', true, false);
    $submit_text      = sd_substr(Getvar('submit_text', '', 'string',true,false),0,64);
    $intro_text       = Getvar('intro_text', '', 'string', true, false);
    $success_text     = Getvar('success_text', '', 'string', true, false);
    $showemailaddress = Is_Valid_Number(Getvar('showemailaddress', 0, 'natural_number', true, false),0,0,2);
    $sendtoall        = Is_Valid_Number(Getvar('sendtoall', 0, 'natural_number', true, false),0,0,1);
    $email_sender_id  = Is_Valid_Number(Getvar('email_sender_id', 0, 'whole_number', true, false),0,0,9999999); //v1.2.5
    $active           = 0;

    if(strlen($name) <= 0)
    {
      $this->DisplayForm(AdminPhrase('err_no_form_name'));
      return;
    }
    if(strlen($submit_text) <= 0)
    {
      $this->DisplayForm(AdminPhrase('err_no_submit_value'));
      return;
    }
    if(($submit_type == 1 || $submit_type == 3) && !count($recipients))
    {
      $this->DisplayForm(AdminPhrase('err_no_recipient'));
      return;
    }

    $av = GetVar('access_view', array(), 'array', true, false);
    $access_view = '';
    if(!empty($av))
    {
      $access_view = count($av) > 1 ? implode('|', $av) : $av[0];
      $access_view = empty($access_view) ? '' : '|'.$access_view.'|';
    }

    $DB->query('INSERT INTO '.$this->_tbl."form VALUES (".
         "NULL, '" . $name . "', '" . $submit_type . "', '" . $submit_text . "', '" .
         $intro_text . "', '" . $success_text . "', '" . $showemailaddress . "', '" .
         $sendtoall . "', '" . $active . "', '" . TIME_NOW . "', " .
         $email_sender_id . ", '".$access_view."')");

    if($newid = $DB->insert_id())
    {
      $this->UpdateRecipients($newid, $recipients);
      RedirectPage($this->_page.'&amp;action=displayform&amp;formid='.$newid);
      return;
    }

    RedirectPage($this->_page);

  } //InsertForm


  function UpdateForm()
  {
    global $DB, $sdlanguage;

    if(($this->_formid < 1) || !CheckFormToken())
    {
      RedirectPage($this->_page,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      return false;
    }

    // Check vars
    $name = sd_substr(Getvar('name', '', 'string'),0,64);
    if(!strlen($name))
    {
      $this->DisplayForm(AdminPhrase('err_no_form_name'));
      return;
    }

    $submit_text = sd_substr(Getvar('submit_text', '', 'string',true,false),0,64);
    if(!strlen($submit_text))
    {
      $this->DisplayForm(AdminPhrase('err_no_submit_value'));
      return;
    }

    $active           = (Getvar('active', 0, 'bool',true,false)?1:0);
    $email_sender_id  = Is_Valid_Number(Getvar('email_sender_id', 0, 'whole_number',true,false),0,0,9999999); //v1.2.5
    $intro_text       = Getvar('intro_text', '', 'string',true,false);
    $recipients       = Getvar('recipientids', array(), 'array',true,false);
    $sendtoall        = Is_Valid_Number(Getvar('sendtoall', 0, 'natural_number',true,false),0,0,1);
    $showemailaddress = Is_Valid_Number(Getvar('showemailaddress', 0, 'natural_number',true,false),0,0,2);
    $submit_type      = Is_Valid_Number(Getvar('submit_type', 1, 'whole_number',true,false),1,1,3);
    $success_text     = Getvar('success_text', '', 'string',true,false);

    if(($submit_type == SUBMIT_EMAIL || $submit_type == SUBMIT_EMAIL_DB) && !count($recipients))
    {
      $this->DisplayForm(AdminPhrase('err_no_recipient'));
      return;
    }

    $this->UpdateRecipients($this->_formid, $recipients);

    $av = GetVar('access_view', array(), 'array', true, false);
    $access_view = '';
    if(!empty($av))
    {
      $access_view = count($av) > 1 ? implode('|', $av) : $av[0];
      $access_view = empty($access_view) ? '' : '|'.$access_view.'|';
    }

    $DB->query('UPDATE '.$this->_tbl."form
      SET name             = '$name',
          submit_type      = $submit_type,
          submit_text      = '$submit_text',
          intro_text       = '$intro_text',
          success_text     = '$success_text',
          showemailaddress = $showemailaddress,
          sendtoall        = $sendtoall,
          active           = $active,
          email_sender_id  = $email_sender_id,
          access_view      = '$access_view'
      WHERE form_id        = ".$this->_formid);

     RedirectPage($this->_page.'&amp;action=displayform&amp;formid='.$this->_formid);

  } //UpdateForm


  function DeleteForms()
  {
    global $DB, $sdlanguage;

    if(!CheckFormToken())
    {
      RedirectPage($this->_page,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      return false;
    }

    $formids = GetVar('formids', array(), 'array');
    if(!empty($formids))
    for($i = 0; $i < count($formids); $i++)
    {
      if(!empty($formids[$i]) && ($fid = Is_Valid_Number($formids[$i],0,1)))
      {
        $DB->query('DELETE FROM '.$this->_tbl.'formfield WHERE form_id = %d',$fid);
        $DB->query('DELETE FROM '.$this->_tbl.'form WHERE form_id = %d',$fid);
      }
      else break; //error!
    }

    RedirectPage($this->_page);

  } //DeleteForms


  function PrintRecipientSelection($recipientId, $selectedRecipientid,
             $single=false, $showDefault=false)
  {
    global $DB;

    echo '<select class="form-control" name="' . $recipientId . '"'.
         (empty($single)?' size="5" multiple="multiple"':'').
         ' style="min-width: 350px;">';

    if(!empty($showDefault))
    {
      echo '<option value="0"' .
        ($selectedRecipientid==0 ? ' selected="selected"' : '') . '>' .
        AdminPhrase('default_sender').'</option>';
    }
    else
    if(empty($selectedRecipientid) || !is_array($selectedRecipientid))
    {
      $selectedRecipientid = array();
    }

    if($getrecs = $DB->query('SELECT recipient_id, name, email'.
                             ' FROM '.$this->_tbl.'recipient'.
                             ' ORDER BY email'))
    {
      while($rec = $DB->fetch_array($getrecs,null,MYSQL_ASSOC))
      {
        $rec_id = (int)$rec['recipient_id'];
        echo '<option value="'.$rec_id.'"';
        if(empty($single))
        {
          echo (in_array($rec_id, $selectedRecipientid) ? ' selected="selected"' : '');
        }
        else
        {
          echo (($rec_id == $selectedRecipientid) ? ' selected="selected"' : '');
        }
        echo '>' .
             $rec['email'] . (strlen($rec['name']) > 0 ? ' (' . $rec['name'] . ')' : '') .
             '</option>';
      }
    }
    echo '</select>';

  } //PrintRecipientSelection


  function PrintRecipients($formId)
  {
    global $DB;

    if(empty($formId) || ($formId < 0)) return '';

    $text = '';
    if($getrecs = $DB->query('SELECT r.recipient_id, r.name, r.email'.
                             ' FROM '.$this->_tbl.'recipient r'.
                             ' LEFT JOIN '.$this->_tbl.'formrecipient fr ON fr.recipient_id = r.recipient_id'.
                             ' WHERE fr.form_id = %d ORDER BY r.email',
                             $formId))
    while($rec = $DB->fetch_array($getrecs,null,MYSQL_ASSOC))
    {
      $text .= $rec['email'] . ', ';
    }
    return rtrim($text, ', ');

  } //PrintRecipients


  function PrintResponseRecipients($responseId)
  {
    global $DB;

    if(empty($responseId) || ($responseId<0)) return '';

    $text = '';
    if($getrecs = $DB->query('SELECT r.recipient_id, r.name, r.email'.
                             ' FROM '.$this->_tbl.'recipient r'.
                             ' LEFT JOIN '.$this->_tbl.'formresponserecipient fr ON fr.recipient_id = r.recipient_id'.
                             ' WHERE fr.response_id = '.(int)$responseId.
                             ' ORDER BY r.email'))
    while($rec = $DB->fetch_array($getrecs,null,MYSQL_ASSOC))
    {
      $text .= $rec['email'].', ';
    }
    return rtrim($text, ' ,');

  } //PrintResponseRecipients


  function GetFormName($formid)
  {
    global $DB;

    if(empty($formid)) return '';
    $DB->result_type = MYSQL_ASSOC;
    if($name = $DB->query_first('SELECT name FROM '.$this->_tbl.'form'.
                                ' WHERE form_id = %d LIMIT 1', $formid))
    {
      return $name['name'];
    }
    return '';

  } //GetFormName


  function DisplayForm($errors=false)
  {
    global $DB, $mainsettings, $sdlanguage;

    $this->DisplayBreadcrumb();

    $formid = empty($this->_formid)?0:(int)$this->_formid;
    if($formid < 0)
    {
       DisplayMessage(AdminPhrase('err_no_formid'),true);
       return;
    }

    $recs = $DB->query_first('SELECT COUNT(*) rcount FROM '.$this->_tbl.'recipient');
    if(empty($recs['rcount']))
    {
       DisplayMessage(AdminPhrase('err_no_recipient'),true);
       $this->DisplayRecipients();
       return;
    }

    if(!empty($errors))
    {
      DisplayMessage($errors,true);
    }

    $recs = array();
    if($formid > 0)
    {
      echo '<h2 class="header blue lighter">' . AdminPhrase('section_form_operations') . '</h2>';
      echo '
      <table class="table">
      <tr>
        <td class="td2" width="33%" align="center" style="padding:10px">
          <a class="btn btn-primary" href="'.$this->_page.'&amp;action=displayformpages&amp;formid='.
          $formid.'"><strong>'.AdminPhrase('disp_pages').' ('.$this->CountPages($formid).
          ')</strong></a>
        </td>
        <td class="td2" width="33%" align="center" style="padding:10px">
          <a class="btn btn-primary" href="'.$this->_page.'&amp;action=displayformfields&amp;formid='.
          $formid.'"><strong>'.AdminPhrase('disp_fields').' ('.$this->CountFields($formid).
          ')</strong></a>
        </td>
        <td class="td2" width="33%" align="center" style="padding:10px">
          <a class="btn btn-primary" href="'.$this->_page.'&amp;action=displayformresponses&amp;formid='.
          $formid.'"><strong>'.AdminPhrase('disp_responses').' ('.$this->CountResponses($formid).
          ')</strong></a>
        </td>
      </tr>
      </table>';

      echo '
      <form method="post" action="'.$this->_page.'" class="form-horizontal">';

      echo '<h2 class="header blue lighter"> ' .  AdminPhrase('section_update_form') . '</h2>';

      echo '
      <input type="hidden" name="formid" value="' . $formid . '" />
      <input type="hidden" name="action" value="updateform" />';

      $buttonTitle = strip_tags(AdminPhrase('update_form_but'));

      if(!$form = $DB->query_first('SELECT * FROM '.$this->_tbl.'form'.
                                   ' WHERE form_id = '.(int)$formid))
      {
        DisplayMessage(AdminPhrase('invalid_form_request'),true);
        return false;
      }

      if($getrecs = $DB->query('SELECT DISTINCT recipient_id'.
                               ' FROM '.$this->_tbl.'formrecipient'.
                               ' WHERE form_id = %d'.
                               ' ORDER BY recipient_id', $formid))
      {
        while($rec = $DB->fetch_array($getrecs,null,MYSQL_ASSOC))
        {
          $recs[] = $rec['recipient_id'];
        }
      }
    }
    else
    {
      echo ' <h2 class="header blue lighter"> ' . AdminPhrase('section_create_form') . '</h2>
      <form method="post" action="'.$this->_page.'" class="form-horizontal">';
     
      echo '
      <input type="hidden" name="action" value="insertform" />';

      $buttonTitle = AdminPhrase('create_form_but');
      $form = array(
        'form_id'          => -1,
        'name'             => GetVar('name','','string',true,false),
        'submit_type'      => Is_Valid_Number(Getvar('submit_type', 1, 'whole_number',true,false),1,1,3),
        'submit_text'      => sd_substr(Getvar('submit_text', AdminPhrase('submit_form'), 'string',true,false),0,64),
        'intro_text'       => GetVar('intro_text','','string',true,false),
        'success_text'     => GetVar('success_text',AdminPhrase('form_submit_success'),'string',true,false),
        'showemailaddress' => Is_Valid_Number(Getvar('showemailaddress', 0, 'natural_number', true, false),0,0,2),
        'sendtoall'        => GetVar('sendtoall',0,'whole_number',true,false),
        'active'           => (GetVar('active',0,'bool',true,false)?1:0),
        'date_created'     => TIME_NOW,
        'email_sender_id'  => Is_Valid_Number(Getvar('email_sender_id', 0, 'whole_number', true, false),0,0,9999999),
      );
    }

    echo PrintSecureToken().'
    <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('form_name') . '</label>
		<div class="col-sm-6">
      		<input type="text" name="name" size="50" class="form-control" maxlength="64" value="' .$form['name'] . '" />
		<span class="helper-text"></span>
</div></div>
    <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('submit_but_text') . '</label>
		<div class="col-sm-6">
			<input type="text" name="submit_text" class="form-control" size="50" maxlength="64" value="' .$form['submit_text'] . '" />
		     <span class="helper-text"></span>
</div></div>
    <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('submit_to') . '</label>
		<div class="col-sm-6">
        <select name="submit_type" class="form-control">
          <option value="1" ' . ($form['submit_type'] == '1' ? 'selected="selected"' : '') . '>' . AdminPhrase('send_email') . '</option>
          <option value="2" ' . ($form['submit_type'] == '2' ? 'selected="selected"' : '') . '>' . AdminPhrase('send_db') . '</option>
          <option value="3" ' . ($form['submit_type'] == '3' ? 'selected="selected"' : '') . '>' . AdminPhrase('send_db_and_email') . '</option>
        </select>
           <span class="helper-text"></span>
</div></div>
    <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('recipients') . '</label>
		<div class="col-sm-6">';

    $this->PrintRecipientSelection('recipientids[]', $recs);

    echo '
           <span class="helper-text">' . AdminPhrase('recipients_desc') .
        AdminPhrase('recipients_desc_hint').'</span>
</div></div>';

    if(empty($this->settings['user_email_as_sender_email']))
    {
      echo '
      <div class="form-group">
		<label class="control-label col-sm-2">' .
          AdminPhrase('sender_email') .'</label>
		<div class="col-sm-6">';

      $form['email_sender_id'] = empty($form['email_sender_id'])?0:$form['email_sender_id'];
      $this->PrintRecipientSelection('email_sender_id', $form['email_sender_id'], true, true);

      echo '
             <span class="helper-text">'.
          AdminPhrase('sender_email_hint').'</span>
</div></div>';
    }

    // Fetch all usergroups (do not exclude Administrators here!)
    $groups = isset($form['access_view'])?sd_ConvertStrToArray($form['access_view'], '|'):array();
    $options_cv = '';
    $getusergroups = $DB->query('SELECT usergroupid, name FROM {usergroups} ORDER BY usergroupid');
    while($ug = $DB->fetch_array($getusergroups,null,MYSQL_ASSOC))
    {
      $ugid = $ug['usergroupid'];
      $ugname = $ug['name'];
      $options_cv .= '
      <option value="'.$ugid.'" '.
      (in_array($ugid,$groups) ? 'selected="selected"' : '').">".$ugname.'</option>';
    } //while

    $form['showemailaddress'] = empty($form['showemailaddress'])?0:(int)$form['showemailaddress'];
    echo '
    <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('recipients_disp') . '</label>
		<div class="col-sm-6">
         <select name="showemailaddress" class="form-control">
            <option value="0"' . ($form['showemailaddress'] == 0 ? ' selected="selected"' : '') . '>' . AdminPhrase('disp_name') . '</option>
            <option value="1"' . ($form['showemailaddress'] == 1 ? ' selected="selected"' : '') . '>' . AdminPhrase('disp_email') . '</option>
            <option value="2"' . ($form['showemailaddress'] == 2 ? ' selected="selected"' : '') . '>' . AdminPhrase('disp_name_and_email') . '</option>
         </select>
		 <span class="helper-text">' . AdminPhrase('recipients_disp_desc').'</span>
           </div>
</div>
    <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('recipient_mode') . '</label>
		<div class="col-sm-6">
        <select name="sendtoall"  class="form-control">
          <option value="0" ' . ( empty($form['sendtoall']) ? 'selected="selected"' : '') . '>'.AdminPhrase('recipient_mode_0').'</option>
          <option value="1" ' . (!empty($form['sendtoall']) ? 'selected="selected"' : '') . '>'.AdminPhrase('recipient_mode_1').'</option>
        </select>
		<span class="helper-text">' . AdminPhrase('recipient_mode_desc') . '</span>
           </div>
</div>
   <div class="form-group">
		<label class="control-label col-sm-2">'.
        AdminPhrase('form_permissions_title').'</label>
		<div class="col-sm-6">
        <select class="form-control" name="access_view[]" size="10" multiple="multiple">
        '.$options_cv.'
        </select>
           <span class="helper-text">'. AdminPhrase('form_permissions_descr').'</span>
</div>
</div>
   <h2 class="header blue lighter">' . AdminPhrase('form_text') . '</h2>
    <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('intro_text') . '</label>
		<div class="col-sm-6">';

    DisplayBBEditor($mainsettings['allow_bbcode'], 'intro_text', $form['intro_text'], '', 80, 5);

    echo '
           <span class="helper-text">'.AdminPhrase('intro_text_desc').'</span>
</div></div>
    <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('success_text') . '</label>
		<div class="col-sm-6">';

    DisplayBBEditor($mainsettings['allow_bbcode'], 'success_text', $form['success_text'], '', 80, 5);

    echo '
           <span class="helper-text">' . AdminPhrase('success_text_desc').'</span>
</div></div>';

    if($formid)
    {
       echo '
     <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('disp_form_online') . '</label>
		<div class="col-sm-6">
         <select name="active" class="form-control">
         <option value="1" ' . (!empty($form['active']) ? 'selected="selected"' : '') . '>'.$sdlanguage['yes'].'</option>
         <option value="0" ' . ( empty($form['active']) ? 'selected="selected"' : '') . '>'.$sdlanguage['no'].'</option>
         </select>
            <span class="helper-text"></span>
</div></div>';
    }

    echo '
    <div class="center">
        <button class="btn btn-info" type="submit" value="' . strip_tags($buttonTitle) . '"><i class="ace-icon fa fa-check"></i> ' . strip_tags($buttonTitle) . '</button>
	</div></form>';

  } //DisplayForm
  
  	/**
	* Prints the contact form menu
	*/
	function PrintMenu()
	{
		global $refreshpage;
		
		echo '<ul class="nav nav-pills no-margin-left">
				<li role="presentation" ' . iif($this->_action == 'displaydefaults', 'class="active"') . '>
					<a class="" href="'.$this->_page.'">
						<i class="ace-icon fa fa-home bigger-120"></i> </a>
				</li>
				<li role="presentation" ' . iif($this->_action == 'displayform', 'class="active"') . '>
					<a class="" href="'.$this->_page.'&amp;action=displayform">
						<i class=" ace-icon fa fa-plus"></i> '.AdminPhrase('create_form_but').'
					</a>
				</li>
				<li role="presentation" ' . iif($this->_action == 'displayresponses', 'class="active"') . '>
					<a class="" href="'.$this->_page. '&amp;action=displayresponses">
						<i class="ace-icon fa fa-comment"></i> '.AdminPhrase('disp_responses') . '</a>
				</li>
				<li role="presentation" ' . iif($this->_action == 'displayrecipient'. 'class="active"') . '>
					<a class="" href="'.$this->_page.'&amp;action=displayrecipient">
						<i class="ace-icon fa fa-plus"></i> '.AdminPhrase('create_recipient_but').'</a>
				</li>
				<li role="presentation" ' . iif($this->_action == 'displaysettings', 'class="active"') . '>
					<a class="" href="'.$this->_page.'&amp;action=displaysettings">
						<i class="ace-icon fa fa-wrench"></i> '.AdminPhrase('settings').'</a>
				</li>
			</ul>
			<div class="hr hr-8"></div>
			<div class="space-20"></div>';
	}


  function DisplayForms()
  {
    global $DB, $sdlanguage;

   

    $count = 0;
    if($getforms = $DB->query('SELECT * FROM '.$this->_tbl.'form'.
                              ' ORDER BY date_created DESC'))
    {
      $count = $DB->get_num_rows($getforms);
    }

    
     StartSection(AdminPhrase('section_all_forms'));
     
     echo'
     <form action="'.$this->_page.'" id="formslist" method="post">
    <table class="table table-bordered table-striped">
   	 '.PrintSecureToken().'
	<thead>
    <tr>
      <th class="th1">' . AdminPhrase('form') . '</th>
      <th class="th1">' . AdminPhrase('submit_text') . '</th>
      <th class="th1">' . AdminPhrase('submit_to') . '</th>
      <th class="th1">' . AdminPhrase('recipients') . '</th>
      <th class="th1" align="center">' . AdminPhrase('active') . '</th>
      <th class="th1">' . AdminPhrase('date_created') . '</th>
      <th class="th1" align="center" width="90">'.AdminPhrase('delete').'</th>
    </tr>
	</thead>
	<tbody>';

    if(empty($count))
    {
      echo '
      <tr>
        <td class="td1" colspan="7" align="center" style="font-size: 120%;">'.
          AdminPhrase('msg_no_forms_available').'
        </td>
      </tr>';
    }
    else
    {
      while($form = $DB->fetch_array($getforms,null,MYSQL_ASSOC))
      {
        $form_id = (int)$form['form_id'];
        echo '
        <tr>
          <td class="td2">
            <a href="'.$this->_page.'&amp;action=displayform&amp;formid='.$form_id.'">'.
            '<i class="icon-edit"></i>&nbsp;'.
            $form['name'].'</a>
          </td>
          <td class="td3">' . $form['submit_text'] . '</td>
          <td class="td3">' . $this->ConvertSubmitType($form['submit_type']) . '&nbsp;</td>
          <td class="td3">' . $this->PrintRecipients($form_id) . '&nbsp;</td>
          <td class="td3" align="center">' . (!empty($form['active']) ? $sdlanguage['yes'] : $sdlanguage['no']) . '</td>
          <td class="td3">' . DisplayDate($form['date_created']) . '</td>
          <td class="td2" align="center" width="90">
            <input type="checkbox" class="ace" name="formids[]" value="'.$form_id.'" /><span class="lbl"></span>
          </td>
        </tr>';
      }
      
       echo '</tbody>
     </table>
     <span class="pull-right">';
      #echo '<input class="btn btn-primary" type="submit" value="' . strip_alltags(AdminPhrase('delete_but')) . '" />';
      PrintSubmit('deleteforms', AdminPhrase('delete_but'), 'formslist', 'fa-trash-o','','','btn-danger btn-sm');
      echo '
        </span>';
     }

     echo '</form><div class="space-30"></div>';

  } //DisplayForms

  // #########################################################################

  function AddFormPage()
  {
    global $DB, $sdlanguage;

    if(($this->_formid < 1) || !CheckFormToken())
    {
      RedirectPage($this->_page,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      return false;
    }

    $categoryid = Is_Valid_Number(GetVar('categoryid', 0, 'whole_number'),0,1);

    if(!empty($categoryid))
    {
      $count = $DB->query_first('SELECT COUNT(*) pcount'.
                                ' FROM '.$this->_tbl.'formcategory'.
                                ' WHERE form_id = %d AND category_id = %d',
                                $this->_formid, $categoryid);
      if(!empty($count['pcount']))
      {
         echo '<strong>' . AdminPhrase('err_cat_prev_added') . '</strong><br /><br />';
         $this->DisplayFormPages();
         return;
      }
      $DB->query('INSERT INTO '.$this->_tbl.'formcategory VALUES (%d, %d)',$this->_formid,$categoryid);
    }

    RedirectPage($this->_page.'&amp;action=displayformpages&amp;formid='.$this->_formid);

  } //AddFormPage

  // #########################################################################

  function DeleteFormPages()
  {
    global $DB;

    if(($this->_formid < 1) || !CheckFormToken())
    {
      RedirectPage($this->_page,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      return false;
    }

    $catids = GetVar('catids', array(), 'array');
    for($i = 0; $i < count($catids); $i++)
    {
      if(!empty($catids[$i]) && ($cid = Is_Valid_Number($catids[$i],0,1)))
      {
        $DB->query('DELETE FROM '.$this->_tbl.'formcategory'.
                   ' WHERE form_id = %d AND category_id = %d',
                   $this->_formid, $cid);
      }
      else break; //error
    }

    RedirectPage($this->_page.'&amp;action=displayformpages&amp;formid='.$this->_formid);

  } //DeleteFormPages

  // #########################################################################

  function DisplayFormPages()
  {
    global $DB, $sdlanguage;

    if($this->_formid < 1)
    {
      RedirectPage($this->_page,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      return false;
    }

    $this->DisplayBreadcrumb(AdminPhrase('section_pages_for_form'));

    echo '<h2 class="header blue lighter">' . AdminPhrase('section_pages_for_form') . ' - "' . $this->GetFormName($this->_formid) . '"' . '</h2>';

    echo '
	<form id="addformpage" method="post" action="'.$this->_page.'" class="form-horizontal">
    <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('add_to_category') . '</label>
		<div class="col-sm-6">
        <input type="hidden" name="action" value="addformpage" />
        <input type="hidden" name="formid" value="' . $this->_formid . '" />
        '.PrintSecureToken();

    DisplayArticleCategories($this->pluginid, 0, 1, 0, '',
                             'categoryid', 'font-size:12px;max-width:500px;min-width:320px;',
                             true, true);

    echo '
	<span class="helper-text">' . AdminPhrase('add_to_category_desc') . '</span>
      </div>
	 </div>
	 <div class="center">
	 <button class="btn btn-primary" type="submit" value="' . AdminPhrase('add_to_category') . '" /><i class="ace-icon fa fa-plus"></i> ' . AdminPhrase('add_to_category') . '</button>
	 </div>
        </form>
   <div class="space-20"></div>
    <form action="'.$this->_page.'" method="post">
    <input type="hidden" name="action" value="deleteformpages" />
    <input type="hidden" name="formid" value="' . $this->_formid . '" />
    '.PrintSecureToken().'
    <table class="table table-bordered table-striped">
	<thead>
    <tr>
      <th class="td1">' . AdminPhrase('category_name') . '</th>
      <th class="td1" align="center" width="90">' . AdminPhrase('delete_question') . '</th>
    </tr>
	</thead>';

    if($getcats = $DB->query('SELECT fc.*, c.categoryid real_cat_id, c.name cat_name'.
                             ' FROM '.$this->_tbl.'formcategory fc'.
                             ' LEFT JOIN '.PRGM_TABLE_PREFIX.'categories c ON c.categoryid = fc.category_id'.
                             ' WHERE form_id = %d'.
                             ' ORDER BY c.name, c.categoryid, fc.category_id ASC',
                             $this->_formid))
    {
      while($cat = $DB->fetch_array($getcats,null,MYSQL_ASSOC))
      {
        echo '
      <tr>
        <td class="td2">' .
          (empty($cat['real_cat_id'])?'(invalid page)':'').
          $cat['cat_name'] .'
        </td>
        <td class="td2" align="center">
          <input type="checkbox" class="ace" name="catids[]" value="' . $cat['category_id'] . '" /><span class="lbl"></span>
        </td>
      </tr>
	  </table>';
      }
        echo '
      <div class="align-right">
           <button class="btn btn-danger btn-sm" type="submit" value=""><i class="ace-icon fa fa-trash-o"></i> '.strip_alltags(AdminPhrase('delete_but')).'</button>
        </div>';
    }
    else
    {
      echo '
      <tr>
        <td class="td1" colspan="2" align="right">
           <h3>'.AdminPhrase('please_add_at_least_one_cat').'</h3>
           '.AdminPhrase('please_add_at_least_one_cat_desc').'
        </td>
      </tr>';
    }

    echo '
    </table>
    </form>';

    EndSection();

  } //DisplayFormPages


  function InsertRecipient()
  {
    global $DB, $sdlanguage;

    if(!CheckFormToken())
    {
      RedirectPage($this->_page,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      return false;
    }

    $name  = sd_substr(GetVar('name', '', 'string'),0,64);
    $email = sd_substr(GetVar('email', '', 'string'),0,64);

    if(!strlen($email))
    {
      echo AdminPhrase('err_invalid_email') . '<br />';
      $this->DisplayRecipient();
      return;
    }

    $valid = false;
    if(function_exists('IsValidEmail'))
    {
      $valid = IsValidEmail($email);
    }
    else
    {
      $valid = preg_match("/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+$/", $email);
    }
    if(!$valid)
    {
      echo AdminPhrase('err_invalid_email') . '<br />';
      echo $email . '<br />';
      $this->DisplayRecipient();
      return;
    }

    $DB->query('INSERT INTO '.$this->_tbl."recipient (email, name) VALUES ('%s', '%s')",
               $DB->escape_string($email), $DB->escape_string($name));

    RedirectPage($this->_page);

  } //InsertRecipient


  function UpdateRecipient()
  {
    global $DB, $sdlanguage;

    if(!CheckFormToken())
    {
      RedirectPage($this->_page,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      return false;
    }

    if($this->_recipientid < 1) return;

    $name  = sd_substr(GetVar('name', '', 'string'),0,64);
    $email = sd_substr(GetVar('email', '', 'string'),0,64);

    $valid = false;
    if(function_exists('IsValidEmail'))
    {
      $valid = IsValidEmail($email);
    }
    else
    {
      $valid = preg_match("/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+$/", $email);
    }
    if(!$valid)
    {
      #echo AdminPhrase('err_invalid_email') . '<br />';
      PrintErrors(AdminPhrase('err_invalid_email'));
      $this->DisplayRecipient();
      return;
    }

    $DB->query('UPDATE '.$this->_tbl."recipient SET name = '%s', email = '%s'".
               ' WHERE recipient_id = '.(int)$this->_recipientid,
               $DB->escape_string($name),
               $DB->escape_string($email));

    RedirectPage($this->_page);

  } //UpdateRecipient


  function DeleteRecipients()
  {
    global $DB;

    if(!CheckFormToken())
    {
      RedirectPage($this->_page,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      return false;
    }

    $recipientids = GetVar('recipientids', array(), 'array');
    if(!empty($recipientids) && is_array($recipientids))
    {
      for($i = 0; $i < count($recipientids); $i++)
      {
        if(!empty($recipientids[$i]) && ($rid = Is_Valid_Number($recipientids[$i],0,1)))
        {
          $DB->query('DELETE FROM '.$this->_tbl.'formrecipient WHERE recipient_id = '.$rid);
          $DB->query('DELETE FROM '.$this->_tbl.'recipient WHERE recipient_id = '.$rid);
        }
        else break; //error
      }
    }

    RedirectPage($this->_page);

  } //DeleteRecipients


  function DisplayRecipient()
  {
    global $DB, $sdlanguage;

    if(!CheckFormToken())
    {
      RedirectPage($this->_page,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      return false;
    }

    $this->DisplayBreadcrumb(AdminPhrase('view_recipient'));

    echo '
    <form method="post" action="'.$this->_page.'" class="form-horizontal">
    '.PrintSecureToken();

    if($this->_recipientid > 0)
    {
      echo '<h2 class="header blue lighter">' . AdminPhrase('section_update_recipient') . '</h2>';
      echo '
      <input type="hidden" name="action" value="updaterecipient" />
      <input type="hidden" name="recipientid" value="'.$this->_recipientid.'" />';

      $buttonTitle = AdminPhrase('update_recipient_but');

      $DB->result_type = MYSQL_ASSOC;
      $rec = $DB->query_first('SELECT * FROM '.$this->_tbl.'recipient'.
                              ' WHERE recipient_id = '.(int)$this->_recipientid);
    }
    else
    {
      echo '<h2 class="header blue lighter">' . AdminPhrase('section_create_recipient') . '</h2>';
      echo '<input type="hidden" name="action" value="insertrecipient" />';

      $buttonTitle = AdminPhrase('create_recipient_but');

      $rec = array(
        'recipient_id' => -1,
        'email' => GetVar('email', '', 'string',false,true),
        'name'  => GetVar('name', '', 'string',false,true)
      );
    }

    echo '
    <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('recipient_email_address') . '</label>
		<div class="col-sm-6">
        <input type="text" class="form-control" name="email" size="50" maxlength="64" value="' . $rec['email'] . '" />
      </div>
    </div>
     <div class="form-group">
		<label class="control-label col-sm-2">' . AdminPhrase('recipient_name') . '</label>
		<div class="col-sm-6">
        <input type="text" name="name" class="form-control" size="50" maxlength="64" value="' .$rec['name'] . '" />
      </div>
    </div>
    <div class="center">
        <button class="btn btn-info" type="submit" value="' . strip_alltags($buttonTitle) . '"><i class="ace-icon fa fa-plus"></i> ' . strip_alltags($buttonTitle) . '</button>
    </div></form>';

  } //DisplayRecipient


  function DisplayRecipients()
  {
    global $DB;

    $count = 0;
    if($getrecs = $DB->query('SELECT * FROM '.$this->_tbl.'recipient ORDER BY name, email'))
    {
      $count = $DB->get_num_rows($getrecs);
    }

    StartSection(AdminPhrase('section_recipients'));

    echo '
     <form action="'.$this->_page.'" id="delrecipients" method="post">
    '.PrintSecureToken().'
    <table class="table table-bordered table-striped">
    <thead>
    <tr>
      <th class="th1">' . AdminPhrase('recipient_name') . '</th>
      <th class="th1">' . AdminPhrase('recipient_email_address') . '</th>
      <th class="th1" align="center" width="120" style="padding: 4px;">
        ' . AdminPhrase('delete_question') . '
      </th>
    </tr>
	</thead>
	<tbody>';

    if(empty($count))
    {
      echo '
      <tr>
         <td class="td1" colspan="8" align="center" style="padding: 10px;">
            ' . AdminPhrase('please_add_at_least_one_recipient') . '
         </td>
      </tr>';
    }
    else
    {
      while($rec = $DB->fetch_array($getrecs,null,MYSQL_ASSOC))
      {
        echo '
      <tr>
         <td class="td2"><a href="'.$this->_page.
           '&amp;action=displayrecipient&amp;recipientid='.$rec['recipient_id'].
           PrintSecureUrlToken().'"><i class="icon-edit"></i> ' . $rec['name'] . '</a></td>
         <td class="td3"><a href="'.$this->_page.'&amp;action=displayrecipient&amp;recipientid='.$rec['recipient_id'].PrintSecureUrlToken().'">' . $rec['email'] .'</a></td>
         <td class="td2" align="center"><input type="checkbox" class="ace" name="recipientids[]" value="' . $rec['recipient_id'] . '" /><span class="lbl"></span></td>
      </tr>';
      }
      echo '
      <tr>
        <td class="td2" colspan="2"> </td>
        <td class="td2" style="padding-right: 10px;">';
      #echo '<input class="btn btn-primary" type="submit" value="' . strip_tags(AdminPhrase('delete_but')) . '" />';
      PrintSubmit('deleterecipients', AdminPhrase('delete_but'), 'delrecipients', 'fa-trash-o','','','btn-danger btn-sm');
      echo '
        </td>
      </tr>';
    }

    echo '
    </tbody>
    </table>
    </form>';

    EndSection();

  } //DisplayRecipients


  function DeleteResponses()
  {
    global $DB, $sdlanguage;

    if(!CheckFormToken())
    {
      RedirectPage($this->_page,'<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
      return false;
    }

    $formid = GetVar('formid', 0, 'whole_number', true, false);
    $responseid = GetVar('response_id', null, 'whole_number', true, false);
    $responseids = GetVar('responseids', null, 'a_int', true, false);

    $page = $this->_page;
    if(!empty($responseid))
    {
      $page .= '&action=displayresponse&formid='.$formid.'&responseid='.$responseid;
    }
    else
    if(!empty($formid))
    {
      $page .= '&action=displayformresponses&formid='.$formid;
    }

    if(empty($responseids) || !is_array($responseids))
    {
      RedirectPage($page);
      return false;
    }

    // v1.3.0: support for "File" field type (only if form storage includes DB!)
    $att = new SD_Attachment($this->pluginid,'formresponse');
    $att->setObjectID($responseid);

    // start deleting fields
    for($i = 0; $i < count($responseids); $i++)
    {
      if(!empty($responseids[$i]) && ($responseid = Is_Valid_Number($responseids[$i],0,1)))
      {
        //v1.3.2: remove any attached files (now a one-liner per response)
        $att->DeleteAllObjectAttachments();
        // remove response detail rows
        $DB->query('DELETE FROM '.$this->_tbl.'formresponsefields WHERE response_id = %d',$responseid);
        $DB->query('DELETE FROM '.$this->_tbl.'formresponse WHERE response_id = %d',$responseid);
      }
    }

    $page = $this->_page;
    if(empty($formid))
      $page .= '&action=displayresponses';
    else
      $page .= '&action=displayformresponses&formid='.$formid;
    RedirectPage($page);

  } //DeleteResponses


  function DisplayResponse($responseid)
  {
    global $DB, $bbcode, $sdurl;

    $this->DisplayBreadcrumb(array('&amp;action=displayresponses'=>AdminPhrase('responses')));

    $response = $DB->query_first('SELECT r.*, f.name form_name'.
                                 ' FROM '.$this->_tbl.'formresponse r'.
                                 ' LEFT JOIN '.$this->_tbl.'form f ON f.form_id = r.form_id'.
                                 ' WHERE r.response_id = '.(int)$responseid);

    if(!strlen($response['username']))
    {
      $response['username'] = AdminPhrase('guest');
    }
    $formid = (int)$response['form_id'];

    echo '
    <form method="post" action="'.$this->_page.'" id="response_form">
    <input type="hidden" name="action" value="deleteresponses" />
    <input type="hidden" name="formid" value="' . $formid . '" />
    <input type="hidden" name="response_id" value="' . $response['response_id'] . '" />
    '.PrintSecureToken();

    StartSection(AdminPhrase('section_view_responses'));

    echo '
    <table class="table table-bordered">
    <tr><td class="td1" colspan="2">'.AdminPhrase('response_info').'</td></tr>
    <tr>
      <td class="td2" width="30%">'.AdminPhrase('form').':</td>
      <td class="td3">' . $response['form_name'] . '&nbsp;</td>
    </tr>
    <tr>
      <td class="td2" width="30%">' . AdminPhrase('submitted_by') . ':</td>
      <td class="td3">' . $response['username'] . '&nbsp;</td>
    </tr>
    <tr>
      <td class="td2" width="30%">' . AdminPhrase('ip_address') . ':</td>
      <td class="td3">' . $response['ip_address'] . '&nbsp;</td>
    </tr>
    <tr>
      <td class="td2" width="30%">' . AdminPhrase('date_created') . ':</td>
      <td class="td3">' . DisplayDate($response['date_created']) . '&nbsp;</td>
    </tr>
    <tr>
      <td class="td1" colspan="2">' . AdminPhrase('form_values') . '</td>
    </tr>';

    if($responsefields = $DB->query(
       'SELECT r.*, f.name field_name, f.field_type'.
       ' FROM '.$this->_tbl.'formresponsefields r'.
       ' LEFT JOIN '.$this->_tbl.'formfield f ON f.field_id = r.field_id'.
       ' WHERE r.response_id = '.(int)$responseid))
    {
      // v1.3.0: support for "File" field type (only if form storage includes DB!)
      $att = new SD_Attachment($this->pluginid,'formresponse');
      $attach_path = $att->getStorageBasePath().$this->pluginid.'/formresponse/';
      $attach_path_ok = @is_dir($attach_path) && @is_readable($attach_path);

      while($field = $DB->fetch_array($responsefields,null,MYSQL_ASSOC))
      {
        echo '
        <tr>
          <td class="td2" width="30%" valign="top">'.$field['field_name'].': </td>
          <td class="td3" valign="top">
          ';

        //v1.3.0: new: display attachment
        if(FormWizard_IsFileType($field['field_type']))
        {
          if(empty($field['value']))
          {
            echo AdminPhrase('no_file_uploaded');
          }
          else
          {
            $att->setObjectID($field['response_id']);
            //SD362: use attachments class for output generation
            $output = $att->getAttachmentsListHTML(false,true,true,$field['value'],true);
            if(empty($output))
            {
              echo AdminPhrase('no_file_uploaded');
            }
            else
            {
              //v1.3.2: show image thumbnail and full image in popup
              if($file = $att->getAttachmentsArray(false,$field['value']))
              {
                $file = current($file);
                require_once(SD_INCLUDE_PATH.'class_sd_media.php');
                if(isset(SD_Media_Base::$known_type_ext,$file['filetype']))
                {
                  $link = $sdurl.'includes/attachments.php?pid='.$this->pluginid.
                            '&amp;objectid='.(int)$field['response_id'].
                            '&amp;id='.(int)$field['value'].
                            '&amp;isimage='.SD_Media_Base::$known_type_ext[$file['filetype']];

                  echo '<a style="display:inline;float:left;margin:4px" class="cbox" href="'.$link.'" rel="image" '.
                       'title="<strong>'.addslashes($file['attachment_name']).'</strong>';
                  echo '">';
                  echo SD_Image_Helper::GetThumbnailTag($attach_path.$file['filename'],'',$file['attachment_name'],' ',false,true);
                  echo '</a>
                  ';
                }
              }
              echo '
              <div class="response-attachments">
              <span style="display:none">'.$field['field_id'].'</span>
              '.$output.'
              </div>';
            }
          }
        }
        else
        {
          if($field['field_type']==FIELD_BBCODE)
          {
            $field['value'] = $bbcode->Parse($field['value']);
          }
          else
          if(FormWizard_FieldHasOptions($field['field_type']) && strlen($field['value']))
          {
            if($getname = $DB->query_first('SELECT name'.
                          ' FROM '.$this->_tbl.'formoption'.
                          ' WHERE field_id = '.(int)$field['field_id'].
                          " AND optionvalue = '%s'",
                          $DB->escape_string($field['value'])))
            {
              $field['value'] .= ': '.$getname['name'];
            }
          }
          echo $field['value'];
        }
        echo '&nbsp;</td>
        </tr>';
      }
    }
    echo '
    <tr>
       <td class="td2" width="30%">' . AdminPhrase('recipients') . ':</td>
       <td class="td3">' . $this->PrintResponseRecipients($responseid) . '&nbsp;</td>
    </tr>
    <tr>
       <td class="td2" width="30%">' . AdminPhrase('delete') . ':</td>
       <td class="td3">
         <label for="del_opt"><b>' . AdminPhrase('delete_question') . '</b></label>
         <input type="checkbox" id="del_opt" name="responseids[]" value="'.(int)$responseid.'" /> </td>
    </tr>
    <tr>
      <td class="td2" colspan="2" align="center" style="padding: 5px;">
         <input class="btn btn-primary" type="submit" value="' . strip_tags(AdminPhrase('update_response_but')) . '" />
      </td>
    </tr>
    </table>';
    EndSection();
    echo '</form>';

  } //DisplayResponse


  function CountFields($formid)
  {
    global $DB;

    $count = $DB->query_first('SELECT COUNT(*) row_count'.
                              ' FROM '.$this->_tbl.'formfield'.
                              ' WHERE form_id = %d', $formid);

    return !empty($count['row_count']) ? $count['row_count'] : 0;

  } //CountFields


  function CountPages($formid)
  {
    global $DB;

    $count = $DB->query_first('SELECT COUNT(*) row_count'.
                              ' FROM '.$this->_tbl.'formcategory fc'.
                              ' WHERE form_id = '.(int)$formid);

    return !empty($count['row_count']) ? $count['row_count'] : 0;

  } //CountPages


  function CountResponses($formid = null)
  {
    global $DB;

    if(!empty($formid) && ($formid > 0))
    {
      $count = $DB->query_first('SELECT COUNT(*) FROM '.$this->_tbl.'formresponse'.
                                ' WHERE form_id = %d', $formid);
    }
    else
    {
      $count = $DB->query_first('SELECT COUNT(*) FROM '.$this->_tbl.'formresponse');
    }

    return !empty($count[0]) ? $count[0] : 0;

 } //CountResponses


  // DISPLAY FORM RESPONSES
  function DisplayResponses($formid=0)
  {
    global $DB;

    if(empty($formid))
      $this->DisplayBreadcrumb(array('&amp;action=displayresponses' => AdminPhrase('section_view_responses')));
    else
      $this->DisplayBreadcrumb(array('&amp;action=displayformresponses&amp;formid='.$formid => AdminPhrase('section_responses_for_form')));

    echo '
    <form action="'.$this->_page.'" method="post" id="resp_list" name="resp_list">
    <input type="hidden" name="action" value="deleteresponses" />
    '.PrintSecureToken();

    if(empty($formid) || ($formid < 1))
    {
       StartSection(AdminPhrase('section_form_responses'));
       $getresponse_sql = "SELECT r.*, f.name as form_name,
                           (SELECT COUNT(*) FROM {attachments} a
                            WHERE a.pluginid = ".$this->pluginid."
                              AND a.area = 'formresponse'
                              AND a.objectid = r.response_id) attcount
                           FROM {p".$this->pluginid.'_formresponse} r
                           LEFT JOIN {p'.$this->pluginid.'_form} f ON f.form_id = r.form_id
                           ORDER BY date_created DESC';
    }
    else
    {
       $formid = (int)$formid;
       $getresponse_sql = "SELECT r.*, f.name as form_name,
                           (SELECT COUNT(*) FROM {attachments} a
                            WHERE a.pluginid = ".$this->pluginid."
                              AND a.area = 'formresponse'
                              AND a.objectid = r.response_id) attcount
                           FROM {p".$this->pluginid.'_formresponse} r
                           LEFT JOIN {p'.$this->pluginid.'_form} f ON f.form_id = r.form_id
                           WHERE f.form_id = '.$formid.'
                           ORDER BY date_created DESC';
       StartSection(AdminPhrase('section_responses_for_form') . ' - "' . $this->GetFormName($formid) . '"');
    }

    $count = 0;
    if($getresponses = $DB->query($getresponse_sql))
    {
      $count = $DB->get_num_rows($getresponses);
    }

    echo '
    <input type="hidden" name="formid" value="'.$formid.'" />
    <table class="table table-bordered table-striped">
	<thead>
    <tr>
      <th class="th1" align="center">#</th>
      <th class="th1">' . AdminPhrase('form') . '</th>
      <th class="th1" align="center"> Files </th>
      <th class="th1">' . AdminPhrase('username') . '</th>
      <th class="th1">' . AdminPhrase('ip_address') . '</th>
      <th class="th1">' . AdminPhrase('date_submitted') . '</th>
      <th class="th1" align="center" width="90">
      <input type="checkbox" checkall="group" name="group" value="1" onclick="javascript: return select_deselectAll (\'resp_list\', this, \'group\');" /> ' . AdminPhrase('delete_question') . '</th>
    </tr>
	</thead>';

    if(empty($count))
    {
      echo '
      <tr>
        <td class="td1" colspan="7" align="center">
          <h3>'.AdminPhrase('no_responses_available').'.</h3>
        </td>
      </tr>';
    }
    else
    {
      while($response = $DB->fetch_array($getresponses,null,MYSQL_ASSOC))
      {
        $att_count = empty($response['attcount'])?'':(int)$response['attcount'];
        echo '
      <tr>
        <td class="td3" align="center">' . $response['response_id'] . '</td>
        <td class="td2"><a href="'.$this->_page.'&amp;action=displayresponse&amp;formid='.
          $response['form_id'].'&amp;responseid='.$response['response_id'].'">'.
          $response['form_name'] .'</a></td>
        <td class="td3" align="center">'.$att_count.'</td>
        <td class="td3">' . $response['username'] . '&nbsp;</td>
        <td class="td3">' . $response['ip_address'] . '&nbsp;</td>
        <td class="td3">' . DisplayDate($response['date_created']) . '</td>
        <td class="td2" align="center">
          <input type="checkbox" checkme="group" name="responseids[]" value="'.(int)$response['response_id'].'" />
        </td>
      </tr>';
      }

      global $sdurl;
      $url = $sdurl. 'plugins/'.$this->pluginfolder.'/exportresponses.php';
      echo '
      <tr>
        <td class="td2" colspan="7" align="right" style="padding-right: 4px;">
           <input class="btn btn-primary" type="submit" value="' . strip_tags(AdminPhrase('delete_but')) . '" />
        </td>
      </tr>
      <tr>
        <td class="td2" colspan="7" align="left" style="padding: 8px;">Export:
           <a class="btn btn-primary" target="_blank" href="'.$url.'?formid=' . $formid . '&amp;admin=1&amp;type=csv'.PrintSecureUrlToken().'">' . AdminPhrase('export_to_csv') . '</a> -
           <a class="btn btn-primary" target="_blank" href="'.$url.'?formid=' . $formid . '&amp;admin=1&amp;type=doc'.PrintSecureUrlToken().'">' . AdminPhrase('export_to_doc') . '</a>
        </td>
      </tr>';
    }

    echo '
    </table>';
    EndSection();

    echo '</form>';

  } //DisplayResponses


  // DISPLAY PLUGIN SETTINGS PAGE
  function DisplaySettings()
  {
    $this->DisplayBreadcrumb();
    PrintPluginSettings($this->pluginid, array('form_wizard_settings','form_export_settings'), $this->_page);
  }

  // DISPLAY DEFAULT PAGE
  function DisplayDefaults()
  {
    $this->DisplayForms();
    $this->DisplayRecipients();

   
    echo '
   <div class="alert alert-info">
     <h5>' . AdminPhrase('how_to_use') . '</h5>
    ' . AdminPhrase('how_to_use_desc') . '
   </div>';
   

  } //DisplayDefaults


  function Start()
  {
    if(empty($this->_InitOK)) return false;
    
    // Display Menu
    $this->PrintMenu();

    // perform action
    switch($this->_action)
    {
      /* FORMS */
      case 'displayform':
        $this->DisplayForm();
        break;

      case 'displayformpages':
        $this->DisplayFormPages($this->_formid);
        break;

      case 'displayformfields':
        $this->DisplayFormFields($this->_formid);
        break;

      case 'displayformresponses':
        $this->DisplayResponses($this->_formid);
        break;

      case 'displaysettings':
        $this->DisplaySettings();
        break;

      case 'deleteforms':
        $this->DeleteForms();
        break;

      case 'insertform':
        $this->InsertForm();
        break;

      case 'updateform':
        $this->UpdateForm();
        break;

      /* PAGES */
      case 'addformpage':
        $this->AddFormPage();
        break;

      case 'deleteformpages':
        $this->DeleteFormPages();
        break;

      /* FIELDS */
      case 'displayfield':
        $this->DisplayField($this->_formid, $this->_fieldid);
        break;

      case 'deletefields':
        $this->DeleteFields();
        break;

      case 'insertfield':
        $this->InsertField();
        break;

      case 'updatefield':
        $this->UpdateField();
        break;

      case 'sortfields':
        $this->SortFields();
        break;

      /* FIELD OPTIONS */
      case 'insertfieldoption':
        $this->InsertFieldOption();
        break;

      case 'updatefieldoptions':
        $this->UpdateFieldOptions();
        break;

      /* Recipients */
      case 'displayrecipient':
        $this->DisplayRecipient();
        break;

      case 'deleterecipients':
        $this->DeleteRecipients();
        break;

      case 'insertrecipient':
        $this->InsertRecipient();
        break;

      case 'updaterecipient':
        $this->UpdateRecipient($this->_recipientid);
        break;

      /* RESPONSES */
      case 'displayresponse':
        $this->DisplayResponse($this->_responseid);
        break;

      case 'displayresponses':
        $this->DisplayResponses();
        break;

      case 'deleteresponses':
        $this->DeleteResponses();
        break;

      case 'displaydefaults':
      default:
        $this->DisplayDefaults();
        break;
    }
    return true;
  } //Start

} // END CLASS FormWizardSettings


// ############################################################################
// MAIN CODE
// ############################################################################

$FWS = new FormWizardSettings();

//v1.3.2: check for and process ajax requests first
if(Is_Ajax_Request())
{
  $action = GetVar('action', '', 'string');
  $pid    = Is_Valid_Number(GetVar('pid', 0, 'whole_number'),0,5000);
  $fid    = Is_Valid_Number(GetVar('fid', 0, 'whole_number'),0,1);
  $rid    = Is_Valid_Number(GetVar('rid', 0, 'whole_number'),0,1);
  if( ($action=='checkfile') && CheckFormToken('',false) &&
      ($pid==$FWS->pluginid) && $fid && $rid )
  {
    $FWS->CheckResponseAttachment($rid, $fid);
  }
  unset($FWS);
  $DB->close();
  exit();
}
else
{
  $FWS->Start();
}
unset($FWS);
