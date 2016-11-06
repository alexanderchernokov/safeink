<?php
if(!headers_sent() && (defined('IN_ADMIN') || defined('IN_PRGM')) && !empty($mainsettings['gzipcompress']))
{
// ENABLE GZIP COMPRESSION
if(isset($sd_gzhandler) || (!empty($usersystem) && ($usersystem['name']=='XenForo 1'))) return;

$sd_gzhandler  = false;
$tmp_handler   = @ini_get('output_handler');
$tmp_zcompress = @ini_get('zlib.output_compression');
//Check for "zlib" setting or otherwise this warning is issued by PHP:
//"output handler 'ob_gzhandler' conflicts with 'zlib output compression'"
if(isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (stristr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')!==false))
{
  if(!$tmp_zcompress && @extension_loaded('zlib') && ($tmp_handler != 'ob_gzhandler'))
  {
    // This setting can be used to adjust the amount of compression applied (1-9)
    // However it may not work depending on the server environment
    //@ini_set('zlib.output_compression_level', 3);
    //SD343: call ob_end_clean to avoid PHP 5.4.x warning about conflict with zlib
    if(ob_get_contents() !== false) @ob_end_clean();
    $sd_gzhandler = @ob_start('ob_gzhandler');
  }
}
unset($tmp_handler,$tmp_zcompress);
}
