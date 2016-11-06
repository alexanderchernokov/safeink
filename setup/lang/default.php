<?php
// DEFINE CONSTANTS
if(!defined('IN_PRGM')) return;

$lang = array(
'php_version_check_failed' => 'PHP version check failed! PHP v5.1.6+ is required!',
'php_version_check_msg' => '<strong>Server PHP version: '.PHP_VERSION.'<br /><br />
      Sorry, but installation of '.PRGM_NAME.' is currently NOT possible!<br /><br>
      Please contact your server administration/hosting company to enable PHP 5.1.6+!</strong>',
'err_already_installed_title' => PRGM_NAME . ' is already installed.',
'err_already_installed_msg' => 'To reinstall, the required file "<strong>includes/config.php</strong>" MUST be present and empty!<br /><br />
      Please <a href="' . ROOT_PATH . 'admin/index.php" style="color:red; font-weight: bold;">
      click here</a> to view your Administration panel or <a href="'.
      ROOT_PATH.'index.php" style="color:red; font-weight: bold;">click here</a> to view your website.',
'err_config_readonly_title' => 'Configuration file "<strong>includes/config.php</strong>" is not writable!',
'err_config_readonly_msg' => 'Before installing you must CHMOD "config.php" to 666 with an FTP program.<br />
      The file is located in the "includes" directory.<br /><br />
      For security reasons, it is important to CHMOD back to 644 after installation (use FTP).<br /><br />
      If you are running under windows, make sure that the file
      permissions are correct for your webserver.',
'err_config_not_found_title' => 'Could not find required file "<strong>includes/config.php</strong>"!',
'err_config_not_found_msg' => 'Make sure to rename the file "<strong>config.php.new</strong>" to "<strong>config.php</strong>".<br />
      The file is located in the "/includes" directory and should be empty.<br /><br />
      Once renamed, the file\'s permissions must be set to <strong>0666</strong> with an FTP program to be writable.<br />
      For security purposes, it is highly important to change these back to <strong>0644</strong> after installation.<br />',
'err_admin_name_too_short' => 'The administrator username MUST be at least 5 characters long!',
'err_admin_pwd_too_short' => 'The administrator password MUST be at least 6 characters long!',
'err_credentials_incomplete' => 'All database details must have a value (DB-name, username &amp; password)!',
'err_connection_failed' => '<strong>Unable to establish a connection to the MySQL server!<br />
      Please re-check the database credentials!<strong>',
'err_requirements_failed' => 'At least one of above requirements have <strong>FAILED</strong>!
    The installation cannot be continued!  Please contact your server/hosting provider to assist in meeting above requirements!',
'title_upgrading_current_version' => 'Upgrading to current version ...',
'upgrading_to' => 'Upgrading to',
'step' => 'Step',
'install_completed' => 'Installation completed!',
'post_install_hint1' => 'Please <a href="' . ROOT_PATH . ADMIN_PATH .
      '/index.php">click here</a>
      to view your Administration panel or <a href="' . ROOT_PATH .
      'index.php">click here</a>
      to view your website.',
'post_install_hint2' => '<strong>Please do not forget:</strong><br />
    <ul>
    <li> Change the permissions of the file "<strong>includes/config.php</strong>" to 644 if install was successfull (in FTP program).</li>
    <li> Change the permissions of the folder "<strong>cache</strong>" to 777 for caching to work.</li>
    <li> Password protect OR delete the folder "<strong>setup</strong>" <strong>immediately</strong>!</li>
    <li> Note that PDF export within the Articles requires a separate download! Please see file "includes/html2pdf/DOWNLOAD_read_me.txt" for instructions!</li>
    </ul>
    <strong>Check that the following folders have WRITE permissions:</strong>',
'cache_folder_purged' => 'Cache folder purged.',
'core_install_done' => 'Core Installation Step Completed',
'base_install_done_msg' => 'Base Installation complete...<br /><br />
    The base install of '.PRGM_NAME.' is done and will now be upgraded to the current version.',
'install_will_continue' => 'Installation will continue in 3 seconds (if not, please click below button)...',
'install_continue_btn2' => 'Continue Installation (Step 2)',
'please_wait' => 'Please wait...',
'err_install_failed_hint1' => 'Installation NOT successful: <strong>FAILED</strong> to write configuration file!',
'err_install_failed_hint2' => '<strong>Instructions:</strong><br />
    Please open a text file, paste below lines into it, and FTP-upload it<br />
    to this location: "<strong>includes/config.php</strong>"',
'installation_errors_title' => 'Installation Errors',
'installation_errors_hint1' => 'The following errors were found:',
'installation_not_possible' => 'Installation not possible due to previous errors!',
'step_1_title' => 'Step 1/3: '.PRGM_NAME . ' Installation ' . PRGM_VERSION,
'prompt_database' => 'Enter Database Information',
'prompt_host' => 'Database Server Hostname:',
'prompt_host_hint' => 'Ex: <i>localhost</i> or a name provided by hosting provider.',
'prompt_db_name' => 'Database Name:',
'prompt_db_name_hint' => 'Ex: <i>subdreamer</i> or a name provided by hosting provider. Up to 50 characters.',
'prompt_db_user' => 'Database User:',
'prompt_db_user_hint' => 'Ex: <i>admin</i> or a name provided by hosting provider. Up to 50 characters.',
'username' => 'Username',
'prompt_db_pwd' => 'Password for above DB user:',
'prompt_db_pwd_hint' => 'Up to 60 characters.',
'prompt_tbl_prefix' => 'Table Prefix:',
'prompt_tbl_prefix_hint' => 'Optional table prefix, default: "sd_". Up to 10 characters.',
'create_admin_account' => 'Create Admin Account',
'prompt_cms_username' => 'Administrator User:',
'prompt_cms_user_hint' => 'At least 4 and up to 13 characters.',
'prompt_cms_pwd' => 'Password:',
'prompt_cms_pwd_hint' => 'At least 6 and up to 40 characters.',
'prompt_cms_email' => 'Email:',
'prompt_cms_email_receive' => 'Use this email to receive technical site emails?',
'prompt_install_demo' => 'Install Demo Data?',
'prompt_install_demo_hint' => 'Install initial demo data (pages with content)?',
'credentials_hint' => 'Note: ALL details (except table prefix) are required for installation!',
'install_now_btn' => 'Install Now',
'installation' => 'Installation',
'requirements_title' => 'Installation Checks',
'req_requirement_title' => 'Requirement',
'req_version_title' => 'Your Version',
'req_status_title' => 'Status',
'req_pass' => 'PASS',
'req_fail' => 'FAIL',
'req_folder' => 'Folder',
'req_folder_ok' => 'Ok, exists and is writable.',
'req_folder_not_writable' => 'Fail: is NOT writable',
'req_folder_not_found' => 'Fail: does not exist!',
'req_db_charset' => 'Database Character Set',
'req_warning' => 'Warning',
'req_recommended' => 'Recommended',
'req_change_db_charset' => 'Change DB Character Set to utf8 (recommended)?',
'req_conv_tables' => 'Convert Tables to utf8 (contact support)?',
'installing_plugin' => 'Installing Plugin',
'upgrade_step' => 'Upgrade Step:',
'successfully_upgraded_to_version' => 'Successfully upgraded ' . PRGM_NAME . ' to version:',
'err_loading_upgrade_file' => 'Error loading upgrade file for version',
'err_upgrade_file_not_found' => 'Upgrade file missing for version',
'continue'	=>	'Continue',
'lang_please_wait'	=>	'Please Wait...',
'admin_skin'		=>	'\'ace\' Admin Skin Set',
);

extract($lang, EXTR_OVERWRITE | EXTR_PREFIX_ALL, 'lang');
unset($lang);