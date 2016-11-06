<?php
$sd_add_custom_paging = false;
if(defined('IN_PRGM'))
{
  $pluginid = $customplugin_ids[$customplugincount];
  if(!empty($userinfo['custompluginviewids']) && @in_array(substr($pluginid,1,5), $userinfo['custompluginviewids']))
  {
    $ccontent = isset($customplugin[$pluginid]) ? $customplugin[$pluginid] : '';
    if($ccontent)
    {
      //SD362: skin variable replacements
      $ccontent = sd_DoSkinReplacements($ccontent);

      if(empty($custompluginoptions[$pluginid]['ignore_excerpt_mode']) && ($output = CheckExcerptMode($ccontent)))
      {
        echo $output['content'].'
        <div class="excerpt_message">'.$output['message'].'</div>
        ';
      }
      elseif(!$mainsettings_enable_custom_plugin_paging)
      {
        echo $ccontent;
      }
      else
      {
        $cpagenum = Is_Valid_Number(GetVar($pluginid.'-page',1,'whole_number',false,true),1,1,99999);
        echo sd_PaginateCustomPlugin($ccontent, $current_page_url, $pluginid, $cpagenum, $haspages);
        $sd_add_custom_paging = !empty($haspages);
      }
    }

    $cfile = !empty($custompluginfile[$pluginid]) ? (string)$custompluginfile[$pluginid] : false;
    if($cfile &&
       ($cfile != EMPTY_PLUGIN_PATH) &&
       (strtolower(substr($cfile,0,4)) != 'http') && (strtolower(substr($cfile,0,3)) != 'ftp') &&
       (is_file($cfile)))
    {
      $sd_oldreporting = $sd_ignore_watchdog;
      $sd_ignore_watchdog = true;
      @include($cfile);
      $sd_ignore_watchdog = $sd_oldreporting;
      unset($sd_oldreporting);
    }
    unset($ccontent,$cfile,$cpagenum,$output);
  }
  $pluginid = 0;
  $customplugincount++;
}
