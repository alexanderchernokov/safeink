<?php
if(!defined('IN_PRGM')) exit();

if(defined('IN_ADMIN'))
{
  $p12_page = Is_Valid_Number(GetVar('page',1,'whole_number',false,true),1,1,3);
  $load_wysiwyg = $loadwysiwyg = 1;
  sd_header_add(array(
    'other' => array('
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function() {
  (function($){ $(".microtabs").microTabs({ selected: '.($p12_page-1).' }); })(jQuery);
});
//]]>
</script>
')
  ));
}
else
{
  // ############################ FRONTPAGE ###################################

  if(!empty(SDProfileConfig::$p12_settings)) //SD370
  {
    $p12_language = SDProfileConfig::$p12_phrases;
    $p12_settings = SDProfileConfig::$p12_settings;
  }
  else
  {
    $p12_language = GetLanguage(12);
    $p12_settings = GetPluginSettings(12);
  }

  $max_usr_length = empty($p12_settings['max_username_length'])?0:(int)$p12_settings['max_username_length'];
  $max_usr_length = Is_Valid_Number($max_usr_length,13,13,64);

  $min_usr_length = empty($p12_settings['min_username_length'])?0:(int)$p12_settings['min_username_length'];
  $min_usr_length = Is_Valid_Number($min_usr_length,3,3,20);

  $min_pwd_length = empty($p12_settings['min_password_length'])?0:(int)$p12_settings['min_password_length'];
  $min_pwd_length = Is_Valid_Number($min_pwd_length,5,5,20);

  $basic_ucp = defined('UCP_BASIC') && UCP_BASIC;

  $js_arr = array();
  if(defined('ENABLE_MINIFY') && ENABLE_MINIFY)
  {
    $js_arr[] = $sdurl.MINIFY_PREFIX_G.'profile_front,bbcode';
  }
  else
  {
    $js_arr = array(
      SD_JS_PATH . 'uni-form.jquery.js',
      SD_JS_PATH . 'jquery.validate.min.js',
      SD_JS_PATH . 'jquery.mousewheel.js',
      SD_JS_PATH . 'jquery.datepick.min.js',
      SD_JS_PATH . 'jquery.autocomplete.min.js',
      SD_JS_PATH . 'markitup/markitup-full.js',
      SD_JS_PATH . 'markitup/sets/bbcode/set.js'
    );
  }
  if(!$basic_ucp)
  {
    $lang = empty($mainsettings['lang_region']) ? 'en-GB' : $mainsettings['lang_region'];
    if(file_exists(ROOT_PATH.'includes/javascript/datetime/jquery.datepick-'.$lang.'.js'))
    {
      $js_arr[] = ROOT_PATH.'includes/javascript/datetime/jquery.datepick-'.$lang.'.js';
    }
  }
  $css_arr = array(
    SD_CSS_PATH.'uni-form.css',
    SD_CSS_PATH.'default.uni-form.css',
    SD_CSS_PATH.'jquery.datepick.css',
    SD_CSS_PATH.'redmond.datepick.css',
  );

  //SD343: randomize form field id's/names against spammers/bots
  // Must be the same method as in "register.php"!!!
  $p12_hash = date('H').USERIP;
  $p12_username_hash = 'p'.md5($p12_hash.'p12_username');
  $p12_password_hash = 'p'.md5($p12_hash.'p12_password');
  $p12_passwordconfirm_hash = 'p'.md5($p12_hash.'p12_passwordconfirm');
  $p12_email_hash = 'p'.md5($p12_hash.'p12_email');
  $p12_emailconfirm_hash = 'p'.md5($p12_hash.'p12_emailconfirm');
  $p12_termsconfirm_hash = 'p'.md5($p12_hash.'p12_termsconfirm');

  /* below password generator:
     http://jquery-howto.blogspot.de/2009/10/javascript-jquery-password-generator.html */

  sd_header_add(array(
    'css'   => $css_arr,
    'js'    => $js_arr,
    'other' => array('
<script type="text/javascript"> //<![CDATA[
var VALID_NOT_EMPTY = RegExp(".+");
var VALID_NUMERIC = RegExp("[0-9]+");
var VALID_EMAIL = RegExp("^[a-zA-Z0-9]{1}([\._a-zA-Z0-9-]+)(\.[_a-zA-Z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+){1,3}$");
var VALID_URL = RegExp("^(http|https):\/\/[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}((:[0-9]{1,5})?\/.*)?$");

if(typeof(jQuery) !== "undefined") {
(function($){
$.extend({
  password: function (length, special) {
    var iteration = 0;
    var password = "";
    var randomNumber;
    if(special == undefined){
        var special = false;
    }
    while(iteration < length){
        randomNumber = (Math.floor((Math.random() * 100)) % 94) + 33;
        if(!special){
            if ((randomNumber >=33) && (randomNumber <=47)) { continue; }
            if ((randomNumber >=58) && (randomNumber <=64)) { continue; }
            if ((randomNumber >=91) && (randomNumber <=96)) { continue; }
            if ((randomNumber >=123) && (randomNumber <=126)) { continue; }
        }
        iteration++;
        password += String.fromCharCode(randomNumber);
    }
    return password;
  }
});

var p12_lang = {
  usr_long  : \''.addslashes(str_replace('#d#', $max_usr_length, $p12_language['username_too_long'])).'\',
  usr_short : \''.addslashes(str_replace('#d#', $min_usr_length, $p12_language['username_too_short'])).'\',
  pwd_short : \''.addslashes(str_replace('#d#', $min_pwd_length, $p12_language['password_too_short'])).'\',
  pwd_unmatched : \''.addslashes($p12_language['password_unmatched']).'\',
  usr_pwd_equal : \''.addslashes($p12_language['pwd_different_username']).'\',
  email_unmatched : \''.addslashes($p12_language['email_unmatched']).'\',
  email_invalid : \''.addslashes($p12_language['unvalid_email']).'\',
  email_available : \''.addslashes($p12_language['email_available']).'\',
  email_unavailable : \''.addslashes($p12_language['email_unavailable']).'\',
  usr_available : \''.addslashes($sdlanguage['short_username_available']).'\',
  usr_not_available : \''.addslashes($sdlanguage['short_username_not_available']).'\',
  usr_invalid : \''.addslashes($p12_language['invalid_username']).'\',
  checking_hint : \''.addslashes($p12_language['checking_hint']).'\' };

function p12_email_validate() {
  var emailValue = $("input#'.$p12_email_hash.'").val();
  var errors = new Array();
  if (!emailValue || !VALID_EMAIL.test(emailValue) || emailValue.length < 7) {
    errors.push(p12_lang.email_invalid);
    $("input#'.$p12_email_hash.'").focus();
  }
  if(errors.length > 0) {
    $("div#error_message").html(errors.join("<"+"br "+"/"+"><"+"br "+"/"+">")).show();
    return false;
  }
  return true;
}

function p12_checkUser(str, elem, imgid) {
  if(!elem) return false;
  if(imgid==1 && (!str.length || (str.length < '.$min_usr_length.'))) {
    $("img#p12_checkimg"+imgid).attr("src", "includes/images/check-fail.png").show();
    $("#p12_err_usr").text(p12_lang.usr_short).show();
    return false;
  }
  if(imgid==2 && (!str.length || (str.length < 5))) {
    $("img#p12_checkimg"+imgid).attr("src", "includes/images/check-fail.png").show();
    $("#p12_err_email").text(p12_lang.email_invalid);
    return false;
  }
  var errspan = $(elem).parent("div").find("span");
  var formdata = {"action": "p12_checkuser", "term": str, "t": imgid };
  $("img#p12_checkimg"+imgid).attr("src","includes/css/images/indicator.gif").show();
  $(errspan).html(p12_lang.checking_hint);
  $.post("includes/ajax/getuserselection.php", formdata,
    function(data, status) {
      if(status==="success") {
        if(data=="1") {
          $(errspan).html(imgid==2?p12_lang.email_available:p12_lang.usr_available);                                
          $("img#p12_checkimg"+imgid).attr("src", "includes/images/check-ok.png").show();
          return true;
        } else {
          if(data=="2") {
            $(errspan).html(imgid==2?p12_lang.email_invalid:p12_lang.usr_invalid);
          } else {
            $(errspan).html(imgid==2?p12_lang.email_unavailable:p12_lang.usr_not_available);
          }
          $("img#p12_checkimg"+imgid).attr("src", "includes/images/check-fail.png").show();
          return false;
        }
      }
    }, "text");
  $(errspan).html("");
  $("img#p12_checkimg"+imgid).hide();
}

function p12_validate(isSubmit) {
  var elem1 = $("input#'.$p12_username_hash.'");
  var el1Value = $(elem1).val();
  var passwordValue = $("input#'.$p12_password_hash.'").val();
  var passwordConfirmValue = $("input#'.$p12_passwordconfirm_hash.'").val();
  var emailValue = $("input#'.$p12_email_hash.'").val();
  var emailConfirmValue = $("input#'.$p12_emailconfirm_hash.'").val();
  var errors = new Array();

  if (el1Value && passwordValue && (el1Value === passwordValue)) {
    errors.push(p12_lang.usr_pwd_equal);
    $("span#p12_err_pwd").text(p12_lang.usr_pwd_equal).show();
  }
  else
  if (passwordValue && (passwordValue.length < '.min(4,(int)$p12_settings['min_password_length']).')) {
    errors.push(p12_lang.pwd_short);
    $("span#p12_err_pwd").text(p12_lang.pwd_short).show();
  }
  else
  if (!passwordValue) {
    $("span#p12_err_pwd").text("*");
  }
  else {
    $("span#p12_err_pwd").text("").hide();
  }
  if (!passwordConfirmValue || (passwordConfirmValue.length < '.min(4,(int)$p12_settings['min_password_length']).')) {
    errors.push(p12_lang.pwd_short);
    $("span#p12_err_pwdc").text(p12_lang.pwd_short).show().fadeOut(6000);
  }
  else
  if (passwordValue !== passwordConfirmValue) {
    errors.push(p12_lang.pwd_unmatched);
    $("span#p12_err_pwdc").text(p12_lang.pwd_unmatched).show().fadeOut(6000);
  }
  else {
    $("span#p12_err_pwdc").hide();
  }

  if (!el1Value || el1Value.length < '.$min_usr_length.') {
    errors.push(p12_lang.usr_short);
    $(elem1).next("span").text(p12_lang.usr_short).show().fadeOut(6000);
    $("img#p12_checkimg1").attr("src", "includes/images/check-fail.png").show();
  }
  else
  if (el1Value.length > '.$max_usr_length.') {
    errors.push(p12_lang.usr_long);
    $(elem1).next("span").text(p12_lang.usr_long).show().fadeOut(6000);
    $("img#p12_checkimg1").attr("src", "includes/images/check-fail.png").show();
  }

  if (!emailValue || !VALID_EMAIL.test(emailValue) || emailValue.length < 7) {
    errors.push(p12_lang.email_invalid);
    $("span#p12_err_email").text(p12_lang.email_invalid).show().fadeOut(6000);
  }

  if (emailValue && emailConfirmValue && emailValue.length < 7) {
    errors.push(p12_lang.email_invalid);
    $("span#p12_err_emailc").text(p12_lang.email_invalid).show().fadeOut(6000);
  }
  else
  if (emailValue != emailConfirmValue) {
    errors.push(p12_lang.email_unmatched);
    $("span#p12_err_email,span#p12_err_emailc").text("*").show().fadeOut(6000);
  }

  if(errors.length > 0) {
    $("div#error_message").html(errors.join("<br \/><br \/>")).show();
    if(isSubmit) return false;
  }

  return true;
}

$(document).ready(function() {
  var tmp_value = "";
  $("form#userregistration").append("<input type=\"hidden\" name=\"js\" value=\"1\">").css("display","block").attr("action","'.
    RewriteLink('index.php?categoryid='.$categoryid).'");
  $("input#'.$p12_password_hash.'").val("");
  $("input#'.$p12_passwordconfirm_hash.'").val("");
  $("form#userregistration").submit(function() { return p12_validate(1); });
  $("form#p12_resetpassword").submit(function() { return p12_email_validate(); });
  if(typeof $.fn.uniform !== "undefined"){ $("form.uniForm").uniform(); }
  $("input#'.$p12_email_hash.'").bind("blur",function() {
    tmp_value = $(this).val();
    if(tmp_value.length > 1) { p12_checkUser(tmp_value, this, 2); }
  });
  $("input#'.$p12_username_hash.'").bind("blur",function() {
    tmp_value = $(this).val();
    if(tmp_value.length > 1) { p12_checkUser(tmp_value, this, 1); }
  });
  
  $("input#'.$p12_password_hash.'").bind("blur", function() {
	  var passwordValue = $(this).val();
	  
	  if (!passwordValue || (passwordValue.length < '.min(4,(int)$p12_settings['min_password_length']).')) 
	  {
    	$("span#p12_err_pwd").text(p12_lang.pwd_short).show().fadeOut(6000);
  }
  });
  
  $("input#'.$p12_passwordconfirm_hash.'").bind("blur", function() {
	  var passwordCValue = $(this).val();
	  var passwordValue = $("input#'.$p12_password_hash.'").val();
	  
	  
	  if (passwordValue != passwordCValue) 
	  {
    	 $("span#p12_err_pwdc").text(p12_lang.pwd_unmatched).show().fadeOut(6000);
  }
  });
  
 
 /*
  $("form#userregistration input").bind("blur",function() {
    tmp_value = $(this).val();
    if(tmp_value.length > 1) { p12_validate(0); }
  });
  */
  

  $("a#p12_pwd_use").hide();
  $("form#userregistration .link-password").click(function(e){
    // First check which link was clicked
    linkId = $(this).attr("id");
    if (linkId == "p12_pwd_gen"){
      // If the generate link then create the password variable from the generator function
      p12_pwd = $.password(12,true);
      // Empty the random tag then append the password and fade In
      $("input#p12_random").hide().val(p12_pwd).fadeIn("slow");
      // Also fade in the confirm link
      $("a#p12_pwd_use").fadeIn("slow");
    } else {
      // If the confirm link is clicked then input the password into our form field
      $("input#'.$p12_password_hash.'").val(p12_pwd);
      $("input#'.$p12_passwordconfirm_hash.'").val(p12_pwd);
      // remove password from the random tag
      $("input#p12_random").val("");
      // Hide the confirm link again
      $(this).hide();
    }
    e.preventDefault();
  });
});
})(jQuery);
}
//]]>
</script>
')
  ));

  unset($basic_ucp,$css_arr,$js_arr,$p12_password_hash,$p12_passwordconfirm_hash,
        $p12_language,$p12_username_hash,$min_usr_length,$max_usr_length);
}
