<?php
if(!defined('IN_PRGM')) return;

// Field Types
if(!defined('FIELD_TEXT'))
{
  define('FIELD_TEXT', 1);
  define('FIELD_TEXTAREA', 2);
  define('FIELD_SELECT', 3);
  define('FIELD_CHECKBOX', 4);
  define('FIELD_EMAIL', 5);
  //v1.2.7:
  define('FIELD_FILE', 6);
  define('FIELD_MUSIC', 7);
  define('FIELD_IMAGE', 8);
  define('FIELD_ARCHIVE', 9);
  define('FIELD_DATE', 10);
  define('FIELD_BBCODE', 11);
  define('FIELD_RADIO', 12);
  define('FIELD_TIME', 13);
  define('FIELD_CHECKMULTI', 14);
  define('FIELD_TIMEZONE', 15);
  //v1.3.2
  define('FIELD_DOCUMENTS', 16);

  // Submit Type
  define('SUBMIT_EMAIL', 1);
  define('SUBMIT_DB', 2);
  define('SUBMIT_EMAIL_DB', 3);

  // Validators
  define('VALIDATOR_NOT_EMPTY', 1);
  define('VALIDATOR_NUMBER', 2);
  define('VALIDATOR_EMAIL', 3);
  define('VALIDATOR_URL', 4);
  define('VALIDATOR_INTEGER', 5);
  define('VALIDATOR_WHOLE_NUM', 6);
}
if(!function_exists('FormWizard_IsFileType'))
{
  function FormWizard_IsFileType($field_type)
  {
    if(empty($field_type) || !is_numeric($field_type)) return false;
    return array_key_exists($field_type,
                            array(FIELD_FILE=>FIELD_FILE, FIELD_IMAGE=>FIELD_IMAGE,
                                  FIELD_MUSIC=>FIELD_MUSIC, FIELD_ARCHIVE=>FIELD_ARCHIVE,
                                  FIELD_DOCUMENTS=>FIELD_DOCUMENTS));
  }
}
if(!function_exists('FormWizard_FieldHasOptions'))
{
  function FormWizard_FieldHasOptions($field_type)
  {
    if(empty($field_type) || !is_numeric($field_type)) return false;
    return array_key_exists($field_type,
                            array(FIELD_SELECT=>FIELD_SELECT,
                                  FIELD_RADIO=>FIELD_RADIO,
                                  FIELD_CHECKMULTI=>FIELD_CHECKMULTI));
  }
}
