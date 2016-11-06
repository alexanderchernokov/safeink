<?php
define('IN_PRGM', true);
if(!empty($_GET['admin']))
{
  define('IN_ADMIN', true);
}
define('ROOT_PATH', '../../');
include(ROOT_PATH . 'includes/init.php');

if(!$plugin_folder = sd_GetCurrentFolder(__FILE__)) return;
if(!$pluginid = GetPluginIDbyFolder($plugin_folder)) return;

if(empty($userinfo['adminaccess']) &&
   (empty($userinfo['pluginadminids']) ||
    !@in_array($pluginid,$userinfo['pluginadminids'])))
{
  echo '<h3>'.$sdlanguage['no_download_access'].'</h3>';
  $DB->close();
  exit();
}
$DB->ignore_error = true;
$lang = GetLanguage($pluginid);

if(!CheckFormToken())
{
  DisplayMessage('<strong>'.$sdlanguage['error_invalid_token'].'</strong><br />',2,true);
  return false;
}
$formid = Is_Valid_Number(GetVar('formid', 0, 'whole_number'),0,1,99999);
$type = GetVar('type', '', 'string');
if( (empty($formid) && !defined('IN_ADMIN')) ||
    !in_array($type, array('csv','doc')))
{
  echo $lang['msg_no_responses'];
  exit;
}

// If $formid is 0, then export currently active form
if(!empty($formid))
{
  $DB->result_type = MYSQL_ASSOC;
  if(!$form = $DB->query_first('SELECT * FROM {p'.$pluginid.'_form} WHERE form_id = '.$formid))
  {
    echo $lang['msg_no_responses'];
    exit;
  }

  if(!$responsefields = $DB->query('SELECT name, label, field_type FROM {p'.$pluginid.'_formfield}'.
                                   ' WHERE form_id = '.$formid.
                                   ' ORDER BY sort_order'))
  {
    echo $lang['msg_no_responses'];
    exit;
  }
}

require_once(SD_INCLUDE_PATH.'class_sd_attachment.php');
require_once(ROOT_PATH.'plugins/'.$plugin_folder.'/lib.php');

$settings = GetPluginSettings($pluginid);

function html2specialchars($string)
{
  $string = str_replace ('&amp;', '&', $string);
  $string = str_replace ('&#039;', '\'', $string);
  $string = str_replace ('&#39;', '\'', $string);
  $string = str_replace ('&39;', '\'', $string);
  $string = str_replace ('&quot;', '"', $string);
  $string = str_replace ('&lt;', '<', $string);
  $string = str_replace ('&gt;', '>', $string );
  $string = str_replace ('&uuml;', 'ü', $string);
  $string = str_replace ('&Uuml;', 'Ü', $string);
  $string = str_replace ('&auml;', 'ä', $string);
  $string = str_replace ('&Auml;', 'Ä', $string);
  $string = str_replace ('&ouml;', 'ö', $string);
  $string = str_replace ('&Ouml;', 'Ö', $string);
  return $string;
}

function ExportCSVResponses($formid)
{
  global $DB, $pluginid, $form, $lang, $responsefields, $settings;

  if(empty($formid) || (!defined('IN_ADMIN') && empty($responsefields)))
  {
    return '';
  }
  //v1.2.7: default "CSV Delimiter"
  $sep = ',';
  switch($settings['csv_delimiter'])
  {
    case 1: $sep = chr(9); break;
    case 2: $sep = ';'; break;
  }

  $export = '';

  // responses' default column titles
  $columns = array('Response ID', 'Form Name', 'Username','IP Address', 'Date', 'Recipients');
  foreach($columns as $col_name)
  {
    $export .= '"'.$col_name.'"'.$sep;
  }

  // output actual form fields and keep their count;
  // $responsefields might be empty if called from admin
  if(empty($responsefields) || !is_resource($responsefields))
  {
    $responsefields = $DB->query('SELECT name, label, field_type FROM {p'.$pluginid.'_formfield}'.
                                 ' WHERE form_id = '.$formid.
                                 ' ORDER BY sort_order');
  }
  $fieldcount = 0;
  while($field = $DB->fetch_array($responsefields,null,MYSQL_ASSOC))
  {
    $export .= '"'.(empty($field['name'])?$lang['untitled']:$field['name']).'"'.$sep;
    $fieldcount++;
  }

  // fetch actual responses for specified form
  $sql = "SELECT r.*, IFNULL(f.name,'') form_name".
         ' FROM {p'.$pluginid.'_formresponse} r'.
         ' INNER JOIN {p'.$pluginid.'_form} f ON f.form_id = r.form_id'.
         ' WHERE f.form_id = 0'.$formid.
         ' ORDER BY r.date_created DESC';
  if(!$getresponses = $DB->query($sql)) return '';
  if(!$DB->get_num_rows()) return '';

  $export = rtrim($export, $sep.' ');
  $export .= "\r\n";
  $dateformat = empty($settings['export_date_format'])?'':$settings['export_date_format'];

  while($response = $DB->fetch_array($getresponses,null,MYSQL_ASSOC))
  {
    $response_id = (int)$response['response_id'];

    $date = DisplayDate($response['date_created'], $dateformat);

    // Fill default columns:
    $response['form_name'] = str_replace('&amp;','&',$response['form_name']);
    $export .= '"'.$response_id.'"'.$sep.'"'.$response['form_name'].
               '"'.$sep.'"'.addslashes($response['username']).'"'.$sep.'"'.$response['ip_address'].
               '"'.$sep.'"'.addslashes(strip_tags($date)).'"'.$sep;

    // Build list of recipient emails:
    $emails = '';
    if($recipients = $DB->query('SELECT DISTINCT r.email
                                 FROM {p'.$pluginid.'_formresponserecipient} rf
                                 LEFT JOIN {p'.$pluginid.'_recipient} r ON r.recipient_id = rf.recipient_id
                                 WHERE rf.response_id = %d
                                 ORDER BY r.email', $response_id))
    {
      while($rec = $DB->fetch_array($recipients,null,MYSQL_ASSOC))
      {
        $emails .= $rec['email'].', ';
      }
    }
    $export .= '"'.rtrim($emails,', ').'"'.$sep;

    // Build list of actual response values:
    $sql = 'SELECT rf.*, ff.name field_name, ff.field_type'.
            (empty($settings['export_option_names']) ? '' : ',
             (SELECT COUNT(*) FROM {p'.$pluginid.'_formoption} fo
              WHERE fo.field_id = rf.field_id) optionscount').'
            FROM {p'.$pluginid.'_formresponsefields} rf
            INNER JOIN {p'.$pluginid.'_formfield} ff ON ff.field_id = rf.field_id
            WHERE rf.response_id = '.$response_id.'
            ORDER BY ff.sort_order';
    $out_count = 0;
    if($responsefields = $DB->query($sql))
    {
      $att = new SD_Attachment($pluginid,'formresponse');

      while($field = $DB->fetch_array($responsefields,null,MYSQL_ASSOC))
      {
        //v1.3.2: for some field types fetch the option text for fields' value
        $DB->result_type = MYSQL_ASSOC;
        if(!empty($settings['export_option_names']) &&
           FormWizard_FieldHasOptions($field['field_type']) &&
           !empty($field['optionscount']) &&
           ($option = $DB->query_first('SELECT name FROM {p'.$pluginid.'_formoption}'.
                                       " WHERE field_id = %d AND optionvalue = '%s'".
                                       ' LIMIT 1',
                                       $field['field_id'], $field['value'])) )
        {
          $field['value'] = $option['name'];
        }

        $out_count++;
        if($field['field_type'] == FIELD_CHECKBOX)
        {
          $field['value'] = empty($field['value']) ? '0' : '1';
        }
        else
        if(FormWizard_IsFileType($field['field_type']))
        {
          $att->setObjectID($field['response_id']);
          $files = $att->getAttachmentsArray(false);
          if(empty($files))
          {
            $field['value'] = '';
          }
          else
          {
            // Only one file, so index is 0:
            $field['value'] = $files[0]['attachment_name'];
          }
        }
        else
        if(empty($field['value']))
        {
          $field['value'] = $settings['empty_values_default'];#'N/A';
        }

        $export .= '"'.html2specialchars(trim($field['value'])).'"'.$sep;
      }
    }
    // Make sure to export same amount of columns:
    if($out_count < $fieldcount)
    {
      for(true; $out_count < $fieldcount; $out_count++)
      {
        $export .= '""'.$sep;
      }
    }

    $export = rtrim($export,' '.$sep);
    $export .= "\r\n";
  } //while

  $export = rtrim($export,' '.$sep);
  return $export;

} //ExportCSVResponses


function ExportDOCResponses($formid)
{
  global $DB, $pluginid, $form, $lang, $settings;

  if(!$getresponses = $DB->query('SELECT r.*, f.name form_name
                                  FROM {p'.$pluginid.'_formresponse} r
                                  LEFT JOIN {p'.$pluginid.'_form} f ON f.form_id = r.form_id
                                  WHERE f.form_id = %d ORDER BY date_created DESC',$formid))
  {
    return '';
  }

  $att = new SD_Attachment($pluginid,'formresponse');

  $export = '';
  $dateformat = empty($settings['export_date_format'])?'':$settings['export_date_format'];
  while($response = $DB->fetch_array($getresponses,null,MYSQL_ASSOC))
  {
    $response_id = $response['response_id'];
    $export .= '<strong>'.$lang['email_response_id'].'</strong> ' . $response_id . "<br /><br />\n\n";
    $export .= '<strong>'.$lang['email_form_name'].'</strong> ' . $form['name'] . "<br /><br />\n\n";
    $export .= '<strong>'.$lang['email_username'].'</strong> ' . ((!empty($response['username'])) ? $response['username'] : $lang['guest']) . "<br /><br />\n\n";
    $export .= '<strong>'.$lang['email_ip_address'].'</strong> ' . $response['ip_address'] . "<br /><br />\n\n";
    $date = DisplayDate($response['date_created'], $dateformat);
    $export .= '<strong>'.$lang['email_date'].'</strong> '.
               strip_tags($date)."<br /><br />\n\n";

    if($recipients = $DB->query('SELECT r.email FROM {p'.$pluginid.'_formresponserecipient} rf
                                 LEFT JOIN {p'.$pluginid.'_recipient} r ON r.recipient_id = rf.recipient_id
                                 WHERE rf.response_id = %d ORDER BY r.email',$response_id))
    {
      $export .= '<strong>'.$lang['email_recipients'].'</strong> ';
      while($rec = $DB->fetch_array($recipients,null,MYSQL_ASSOC))
      {
        $export .= $rec['email'] . ' ';
      }
      $export .= "<br /><br />\n\n";
    }

    if($responsefields = $DB->query('SELECT r.*, f.name field_name, f.field_type
                                     FROM {p'.$pluginid.'_formresponsefields} r
                                     LEFT JOIN {p'.$pluginid.'_formfield} f ON f.field_id = r.field_id
                                     WHERE r.response_id = %d ORDER BY f.sort_order',$response_id))
    {
      while($field = $DB->fetch_array($responsefields,null,MYSQL_ASSOC))
      {
        if($field['field_type'] == FIELD_CHECKBOX)
        {
          $field['value'] = empty($field['value']) ? '0' : '1';
        }
        else
        if(in_array($field['field_type'], array(FIELD_FILE,FIELD_IMAGE,FIELD_MUSIC,FIELD_ARCHIVE,FIELD_DOCUMENTS)))
        {
          $att->setObjectID($field['response_id']);
          $files = $att->getAttachmentsArray(false);
          if(empty($files))
          {
            $field['value'] = '';
          }
          else
          {
            // Only one file, so index is 0:
            $field['value'] = $files[0]['attachment_name'];
          }
        }
        else
        if(empty($field['value']))
        {
          $field['value'] = trim($settings['empty_values_default']);
        }

        $field['field_name'] = str_replace(':', '', $field['field_name']);

        $export .= '<b>'.$field['field_name'].':</b> '.
                   html2specialchars($field['value']).
                   "<br /><br />\n\n";
      }
    } //while
    $export .= "<hr /><br /><br />\n\n";
  }

  return $export;

} //ExportDOCResponses


function ExportResponses($formid=0)
{
  global $DB, $lang, $pluginid, $type;

  $export = '';
  if(!empty($formid))
  {
    if($type == 'csv')
    {
      $export = ExportCSVResponses($formid);
    }
    if($type == 'doc')
    {
      $export = ExportDOCResponses($formid);
    }
  }
  else
  {
    if($forms = $DB->query('SELECT DISTINCT f.form_id'.
                           ' FROM {p'.$pluginid.'_formresponse} r'.
                           ' INNER JOIN {p'.$pluginid.'_form} f ON f.form_id = r.form_id'.
                           ' ORDER BY f.date_created DESC'))
    {
      if(!$DB->get_num_rows()) return '';
      $export = '';
      while($form = $DB->fetch_array($forms,null,MYSQL_ASSOC))
      {
        $export .= ExportCSVResponses($form['form_id']);
        $export .= "\r\n";
      }
    }
  }
  $DB->close();

  if(!empty($export))
  {
    if($type == 'csv')
    {
       Header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
       Header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
       Header("Cache-Control: no-store, no-cache, must-revalidate");
       Header("Cache-Control: post-check=0, pre-check=0", false);
       Header("Pragma: no-cache");
       Header("Content-Type: text/csv");
       Header("Content-Length: " . strlen($export));
       Header("Content-Disposition: inline; filename=export_" . Date('Y-m-d_g-i-sa',time()) . '.csv');
       echo $export;
    }
    if($type == 'doc')
    {
       header("Content-Type: application/vnd.ms-word");
       header("Expires: 0");
       header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
       header("content-disposition: attachment;filename=export_" . Date('Y-m-d_g-i-sa',time()) . '.doc');
       echo '<html><meta http-equiv="Content-Type" content="text/html; charset='.SD_CHARSET.'">';
       echo '<body>'.$export.'</body></html>';
    }
  }
  else
  {
    echo $lang['msg_no_responses'];
  }

} //ExportResponses

ExportResponses((int)$formid);
