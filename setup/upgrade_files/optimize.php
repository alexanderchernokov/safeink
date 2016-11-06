<?php
// ############################################################################
// OPTIMIZE IMPORTANT TABLES (FOR VERY LAST UPGRADE STEP!)
// ############################################################################
$DB->query('OPTIMIZE TABLE '.PRGM_TABLE_PREFIX.'adminphrases');
$DB->query('OPTIMIZE TABLE '.PRGM_TABLE_PREFIX.'mainsettings');
$DB->query('OPTIMIZE TABLE '.PRGM_TABLE_PREFIX.'msg_master');
$DB->query('OPTIMIZE TABLE '.PRGM_TABLE_PREFIX.'msg_messages');
$DB->query('OPTIMIZE TABLE '.PRGM_TABLE_PREFIX.'msg_text');
$DB->query('OPTIMIZE TABLE '.PRGM_TABLE_PREFIX.'phrases');
$DB->query('OPTIMIZE TABLE '.PRGM_TABLE_PREFIX.'ratings');
$DB->query('OPTIMIZE TABLE '.PRGM_TABLE_PREFIX.'pluginsettings');
$DB->query('OPTIMIZE TABLE '.PRGM_TABLE_PREFIX.'revisions');
$DB->query('OPTIMIZE TABLE '.PRGM_TABLE_PREFIX.'templates');
if($DB->table_exists(PRGM_TABLE_PREFIX.'syslog'))
{
  $DB->query('OPTIMIZE TABLE '.PRGM_TABLE_PREFIX.'syslog');
}
$DB->ignore_error = false;
