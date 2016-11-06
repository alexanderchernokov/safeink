<?php

if(!defined('IN_PRGM')) exit();

// ######################### LATEST FILES SETTINGS ############################

function LatestDownloadSettings()
{
  global $DB, $admin_phrases, $pluginid, $refreshpage;

  $source_id = array();
  if($sources = $DB->query_first("SELECT value, settingid FROM {pluginsettings} WHERE pluginid = %d AND title = 'source_plugin'",
                                 (int)$pluginid))
  {
    $source_id = Is_Valid_Number($sources['value'], 0, 13, 99999);
  }

  echo '<h2 class="header blue lighter">' . AdminPhrase('dlm_selection') . '</h2>';
  echo '
  <form method="post" action="'.$refreshpage.'" class="form-horizontal">
  <input type="hidden" name="groupnames[]" value="dlm_selection" />
  <input type="hidden" name="refreshpage" value="'.$refreshpage.'" />
  <input type="hidden" name="updatesettings" value="Save Settings" />
  '.PrintSecureToken().'
  <div class="form-group">
  	<label class="control-label col-sm-3">' . AdminPhrase('source_plugin') . '</label>
  	<div class="col-sm-6">';

  $getplugins = $DB->query("SELECT pluginid, name FROM {plugins} WHERE name LIKE 'Download Manager%' ORDER BY displayname");
  $dlm_count = $DB->get_num_rows($getplugins);
  if(!$dlm_count)
  {
    DisplayMessage(AdminPhrase('error_no_dlm'), true);
  }
  else
  {
    echo '
    <select class="form-control" name="settings['.$sources['settingid'].']" >';
    if(!$source_id)
    {
      echo '
      <option value="0" selected="selected">---</option>';
    }
    while($dlm = $DB->fetch_array($getplugins))
    {
      echo '
      <option value="'.$dlm['pluginid'].'"'.($dlm['pluginid']==$source_id?' selected="selected"':'').'>'.$dlm['name'].'</option>';
    }
    echo '
    </select>';
  }

  echo '
    </div>
  </div>';

  if($dlm_count)
  echo '
    <div class="center"><button class="btn btn-info" type="submit" value="' . AdminPhrase('common_update_settings') . '" /><i class="ace-icon fa fa-check"></i> ' . AdminPhrase('common_update_settings') . '</div>';
  echo '
    </form>
    <br />
  ';

} //LatestDownloadSettings

LatestDownloadSettings();

PrintPluginSettings($pluginid, 'Options', $refreshpage);
