<?php

define('IN_PRGM', true);
define('ROOT_PATH', '../'); // required!

unset($userinfo, $usersettings);
if(!include(ROOT_PATH . 'includes/init.php'))
{
  $DB->close();
  exit();
}

// ################################ FETCH VVCID ###############################

// Check for VVCID in POST before GET!
$vvcid = GetVar('vvcid', null, 'whole_number', true, false);
if(!isset($vvcid))
{
  $vvcid = GetVar('vvcid', null, 'whole_number', false, true);
}
$vvcid = Is_Valid_Number($vvcid, 0, 1, 99999999);

//SD350: allow submit login/registration plugins; otherwise "forgot password"
// feature may not work, which may have this VVC:
if(empty($userinfo['loggedin']) && empty($userinfo['pluginsubmitids']))
{
  $userinfo['pluginsubmitids'][] = 10;
  $userinfo['pluginsubmitids'][] = 12;
}

if((!empty($userinfo['adminaccess']) || !empty($userinfo['pluginadminids']) ||
    !empty($userinfo['pluginsubmitids']) || !empty($userinfo['plugincommentids'])) &&
   !empty($vvcid) && ((int)$vvcid > 0))
{
  if($DB->database != $database['name']) $DB->select_db($database['name']);
  if($verification_code = $DB->query_first('SELECT verifycode FROM {vvc} WHERE vvcid = %d',$vvcid))
  {
    if(strlen($verification_code[0]) == SD_VVCLEN)
    {
      // Send browser header
      header("Content-Type: image/png");
      header('Expires: Sun, 1 Jan 2010 12:00:00 GMT');
      header('Last-Modified: '.gmdate("D, d M Y H:i:s").'GMT');
      header('Cache-Control: no-store, no-cache, must-revalidate');
      header('Cache-Control: post-check=0, pre-check=0', false);
      header('Pragma: no-cache');

      $imwidth = 180;
      $imheight = 40;

      /* set up image, width and height  */
      $im = imagecreate($imwidth, $imheight);

      $background_color = imagecolorallocate ($im, mt_rand(0, 120), mt_rand(20, 120), mt_rand(40, 120));
      $text_color   = imagecolorallocate ($im, mt_rand(235, 255), mt_rand(235, 255), mt_rand(235, 255));
      $border_color = imagecolorallocate ($im, 240, 240, 240);

      $noise_color = imagecolorallocate ($im, 180, 180, 255);
      $line_color  = imagecolorallocate($im, 90, 145, 255);
      if(!empty($mainsettings['vvc_bg_lines']))
      {
        // generate random lines in background
        $limit = ($imwidth*$imheight)/$imwidth;
        for($i=0; $i < $limit; $i++)
        {
          imageline($im, mt_rand(0,$imwidth), mt_rand(0,$imheight), mt_rand(0,$imwidth), mt_rand(0,$imheight), $line_color);
        }
      }
      if(!empty($mainsettings['vvc_bg_dots']))
      {
        // generate random dots in background
        $limit = ($imwidth*$imheight) / 8;
        for($i = 0; $i < $limit; $i++)
        {
          imagefilledellipse($im, mt_rand(0,$imwidth), mt_rand(0,$imheight), 1, 1, $noise_color);
        }
      }

      //strip any spaces that may have crept in
      $code = str_replace(' ', '', $verification_code[0]);
      $x=0;
      $gd_info = @gd_info();
      $use_ttf = !empty($gd_info['FreeType Support']) && function_exists('imagettftext') && is_callable('imagettftext') && is_file(ROOT_PATH.'includes/fonts/1.ttf');
      $fpath = realpath(ROOT_PATH.'includes/fonts').'/';

      if(!sd_safe_mode())
      {
        $old_ignore = $GLOBALS['sd_ignore_watchdog'];
        $GLOBALS['sd_ignore_watchdog'] = true;
        @putenv('GDFONTPATH=' . $fpath);
        $GLOBALS['sd_ignore_watchdog'] = $old_ignore;
      }
      for ($i = 0, $stringlength = strlen($code); $i < $stringlength; $i++)
      {
        $single_char = substr($code, $i, 1);
        if($single_char == 'o') { $single_char = '0'; }
        $single_char = ($x & 1) ? strtolower($single_char) : strtoupper($single_char);
        $char_ok = true;
        if($use_ttf)
        {
          $x = $x + 10 + (mt_rand (15, 20));
          $font = mt_rand (1, 10);
          $char_ok = @ImageTTFText($im, mt_rand(22, 26), mt_rand(-20, 20), $x, mt_rand(30, 34), $text_color,
                                   $fpath . $font . '.ttf', $single_char);
        }
        if(!$use_ttf || !$char_ok)
        {
          if($char_ok)
          {
            $x = $x + 10 + (mt_rand ($i, 25));
          }
          $y = mt_rand (5, 20);
          $font = mt_rand (4, 5);
          @imagechar($im, $font, $x, $y, $single_char, $text_color);
        }
      } //for

      if(!empty($mainsettings['vvc_bg_image']) && function_exists('imagecreatefromgif'))
      {
        $nname = rand (1, 26);
        $insertfile = ROOT_PATH . 'includes/captchabg/'.$nname.'.gif';
        $insertfile_id = imagecreatefromgif($insertfile);
        @imagerectangle ($im, 1, 1, $imwidth-1, $imheight-1, $border_color);
        @imagecopymerge ($im, $insertfile_id, 0, 0, 0, 0, 180, 40, 40);
      }

      // output to browser
      @imagepng($im);
      @imagedestroy($im);
    }
  }
}

$DB->close();
