<?php
if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return;

// Registration
echo '
<div class="microtabs">

<div class="micro"'.(empty($p12_page)||($p12_page==2)||($p12_page==3)?'style="display: none;"':'').'>
  <a href="#">'.AdminPhrase('user_registration_settings').'</a>
  <div>
';
PrintPluginSettings(12, 'user_registration_settings', $refreshpage);
echo '
  </div>
</div>

<div class="micro"'.(!empty($p12_page)&&($p12_page==2)?'':' style="display: none;"').'>
  <a href="#">'.AdminPhrase('prevention_options').'</a>
  <div>
';
PrintPluginSettings(12, 'prevention_options', $refreshpage);
echo '
  </div>
</div>

<div class="micro"'.(!empty($p12_page)&&($p12_page==3)?'':' style="display: none;"').'>
  <a href="#">'.AdminPhrase('welcome_options').'</a>
  <div>
';
PrintPluginSettings(12, 'welcome_options', $refreshpage);
echo '
  </div>
</div>

</div>
';