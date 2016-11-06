<?php
if(!defined('IN_ADMIN') || !defined('IN_PRGM')) return;

function DisplayLatestImagesSettings()
{
  global $DB, $admin_phrases, $pluginid, $plugin_names, $refreshpage;

  $setting = $DB->query_first("SELECT * FROM {pluginsettings} WHERE pluginid = %d AND groupname = 'source_plugin' LIMIT 1",$pluginid);

  echo '
  <form method="post" action="'.$refreshpage.'">
  <input type="hidden" name="refreshpage" value="'.$refreshpage.'" />
  <input type="hidden" name="updatesettings" value="Save Settings" />
  <input type="hidden" name="groupnames[]" value="source_plugin" />
  '.PrintSecureToken().'
  ';

  StartSection(AdminPhrase('source_plugin'));

  echo '
  <table width="100%" border="0" cellpadding="5" cellspacing="0">
  <tr>
    <td class="td1">' . AdminPhrase('common_setting_description') . '</td>
    <td class="td1">' . AdminPhrase('common_setting_value') . '</td>
  </tr>
  <tr><td class="td2" valign="top" width="55%">';

  $setting['title'] = isset($admin_phrases[$setting['title']]) ? $admin_phrases[$setting['title']] : $setting['title'];
  $setting['description'] = isset($admin_phrases[$setting['description']]) ? $admin_phrases[$setting['description']] : $setting['description'];

  if(strlen($setting['title']))
  echo '<strong>' . $setting['title'] . '</strong>';

  if(strlen($setting['description']))
  {
    if(strlen($setting['title']))
    {
      echo "<br /><br />\r\n          ";
    }
    echo $setting['description'];
  }
  echo '
    </td>
    <td class="td3" valign="top" width="45%">';

  echo '<select name="settings[' . $setting['settingid'] . ']" size="3">';
  $allplugins = array();
  $pluginvalues = sd_ConvertStrToArray($setting['value']);
  $getplugins= $DB->query(
    "SELECT pluginid, name FROM {plugins}
     WHERE name LIKE 'Image Gallery' OR name LIKE 'Media Gallery' OR
           base_plugin = 'Media Gallery'
     ORDER BY name, pluginid");
  while($p = $DB->fetch_array($getplugins))
  {
    $pid = $p['pluginid'];
    if(isset($plugin_names[$pid])) $p['name'] = $plugin_names[$pid];
    $allplugins[$p['name']] = $pid;
  }
  @natsort($allplugins);
  foreach($allplugins as $pname => $pid)
  {
    echo '<option value="'.$pid.'" '.(in_array($pid,$pluginvalues)?' selected="selected"':'').'>'.$pname.'</option>';
  }
  echo '</select>';

  echo '</td></tr></table>';
  EndSection();
  echo '
  <center><input type="submit" value="' . AdminPhrase('common_update_settings') . '" /></center>
  </form>
  <br />
  ';

  PrintPluginSettings($pluginid, 'Options');

}

DisplayLatestImagesSettings();