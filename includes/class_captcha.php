<?php
// Get a reCaptcha key from http://recaptcha.net/api/getkey

class Captcha
{
  // the error code from reCAPTCHA, if any
  var $error = null;

  // only display captcha once per page
  var $captcha_displayed = false;

  var $privatekey = null;

  var $publickey = null;

  
  function IsValid()
  { 
	$response	=	json_decode(file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".$this->privatekey."&response=".$_POST['g-recaptcha-response']."&remoteip=".USERIP), true);
	
    if($response['success'] == false)
	{
		$this->error = $response['error-codes'];
	    return false;
	}
	else
	{
	  return true;
	}
  }

  function Display($dooutput=true)
  {
    global $sdlanguage;
	
	
    $result = '';
    if(!$this->captcha_displayed)
    {
		$result = "<script src='https://www.google.com/recaptcha/api.js?onload=resetRecaptcha'></script>";
		$result .= '<div class="g-recaptcha" data-sitekey="'. $this->publickey . '"></div>';
    }
    else
    {
      // can not display recaptcha more than once per page

      $result = $sdlanguage['error_multiple_recaptcha'];
    }
    if(!empty($dooutput))
    {
      echo $result;
    }
    else
    {
      return $result;
    }
  }
  
  /**
  * Writes the reCaptcha javascript function
  */
  function writeHeader()
  {
	  return '<script type="text/javascript">
	  // Reset reCaptcha form
	  var resetRecaptcha = function (){
		  grecaptcha.reset();
	};
	</script>';
  }
  
  
}

?>