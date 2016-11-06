<?php
if(!defined('IN_PRGM')) return;

// SD313: enable embedding of video links?
if(defined('IN_ADMIN')) return true;

// Quick check for plugin compatibility
$version = $DB->query_first("SELECT version FROM {plugins} WHERE pluginid=6");
if(version_compare($version['version'], '4.2.0', '<'))
{
	return;
}

$p6_pluginid = 6;
$p6_settings = GetPluginSettings($p6_pluginid);
$p6_language = GetLanguage($p6_pluginid);

//SD370: filename changed; use minify prefix anyway, it'd be empty if disabled
sd_header_add(array('js' => array(SITE_URL.MINIFY_PREFIX_F.'includes/javascript/jquery.form.min.js',
								ROOT_PATH . 'includes/javascript/jquery.validate.min.js',
								ROOT_PATH . 'includes/javascript/additional-methods.min.js')));
								
								
// Get all fields from the database
$fields = $DB->query("SELECT * FROM {p6_fields} ORDER BY displayorder ASC");

while($field = $DB->fetch_array($fields))
{
	$rules .= BuildFieldRule($field);
}

$rules = str_replace_last(',','',$rules);

// define some variables
$validatejs = '
	$("#p6_contact_form").validate({
		errorClass: "has-error",
		validClass: "has-info",
		rules: {
			'.$rules.'
		},
		
		highlight: function(element, errorClass, validClass) {
			$(element).closest(".form-group").removeClass(validClass).addClass(errorClass);
		},
		
		unhighlight: function(element, errorClass, validClass) {
			$(element).closest(".form-group").removeClass(errorClass).addClass(validClass);
		}
	});
	
	$.extend( $.validator.messages, {
		required: "'.$p6_language['validate_field_required'].'",
		maxlength: $.validator.format( "'.$p6_language['validate_maxlength'].'" ),
		minlength: $.validator.format( "'.$p6_language['validate_minlength'].'" ),
		rangelength: $.validator.format( "'.$p6_language['validate_rangelength'].'" ),
		email: "'.$p6_language['validate_email'].'",
		url: "'.$p6_language['validate_url'].'",
		date: "'.$p6_language['validate_date'].'",
		number: "'.$p6_language['validate_number'].'",
		digits: "'.$p6_language['validate_digits'].'",
		equalTo: "'.$p6_language['validate_equalto'].'",
		range: $.validator.format( "'.$p6_language['validate_range'].'" ),
		max: $.validator.format( "'.$p6_language['validate_max'].'" ),
		min: $.validator.format( "'.$p6_language['validate_min'].'" ),
		creditcard: "'.$p6_language['validate_creditcard'].'"
	});
	';						
																


//SD343: hashing against spammers/bots; must be the same as in "header.php"!!!
$p6_hash = date('H').USERIP;
$p6_honeypot_hash = 'p'.md5($p6_hash.'bn/kg&f8e$724');

//SD343: randomize form field id's/names against spammers/bots
// Must be the same method as in "register.php"!!!
$p6_useremail_hash = 'p'.md5($p6_hash.'p6_useremail');
$p6_fullname_hash = 'p'.md5($p6_hash.'p6_fullname');
$p6_custom1_hash = 'p'.md5($p6_hash.'p6_custom1');
$p6_custom2_hash = 'p'.md5($p6_hash.'p6_custom2');
$p6_custom3_hash = 'p'.md5($p6_hash.'p6_custom3');
$p6_subject_hash = 'p'.md5($p6_hash.'p6_subject');
$p6_message_hash = 'p'.md5($p6_hash.'p6_message');


sd_header_add(array(
  'other' => array('
<script type="text/javascript"> //<![CDATA[
if(typeof jQuery !== "undefined") {
	(function($){
		$(document).ready(function() {
			 $("#p6_contact_form").append("<input type=\"hidden\" name=\"js\" value=\"1\">").css("display","block").attr("action","'.RewriteLink('index.php?categoryid='.$categoryid.'&p6_action=sendemail').'");
  			'.$validatejs.'
			
		 $("form#p6_contact_form").submit( function(){
			var isvalidate=$("#p6_contact_form").valid();
			 if(isvalidate)
			 {
				 $("#contact-form-submit").val("'.$p6_language['sending'].'")
				 $("#contact-form-submit").attr("disabled","disabled");
			    $(this).ajaxSubmit({
				    type:       "POST",
				    dataType:   "html",
				    clearForm:  false,
				    target:     "#p6_usermessage",
				    url:        (sdurl + "plugins/p6_contact_form/contactform.php?p6_action=sendemail"),
				    success:    function(responseText, statusText, xhr, $form) {
					    if((statusText !== "success") || (typeof(responseText) == "undefined") || (responseText == "")) {
						    return true;
					    }
					    else {
						    var errorCode = responseText.substr(0,1);
						    responseText = responseText.substr(2);
						    $("#contactForm .loader").addClass("'.$p6_settings['css_error_class'].'").html(responseText);
						    if(errorCode == "0") {
							    $("#contactForm .loader").removeClass("'.$p6_settings['css_error_class'].'").addClass("'.$p6_settings['css_success_class'].'").html(responseText);
							    $("#contact-form-submit").attr("disabled","disabled");
							    $("#p6_contact_form").trigger("reset");
							    $("#contact-form-submit").val("'.$p6_language['message_sent'].'")
							    return false;
						    }
						    else 
						    { 
						        $("#contactForm .loader").addClass("'.$p6_settings['css_error_class'].'").html(responseText);
						        return true; 
						    }
					    }
				    }
    	        });
			 }
	//resetRecaptcha();
    return false;
  });
  		});
	})(jQuery);
}
//]]>
</script>
<style type="text/css">
	.antispam {display: none;}
</style>')));

unset($p6_useremail_hash, $p6_fullname_hash, $p6_subject_hash, $p6_message_hash,
      $p6_custom1_hash, $p6_custom2_hash, $p6_custom3_hash);
	  

function BuildFieldRule($field)
{
	$rule = 'field_' . str_replace(" ", "_", strtolower($field['field_name'])) . ": {\n";
	
	if($field['required'])
	{
		$rule .= "required: true,\n";
	}
	
	switch ($field['field_type'])
	{
		case 1:	// Text
			$rule .= "minlength: 3\n"; 
		break;
		
		case 2: // email
			$rule .= "email: true\n";
		break;
		
		case 3: // Textarea
			$rule .= "minlength: 10\n"; 
		break;
		
		case 6: // URL
			$rule .= "url: true\n";
		break;
		
		case 7: // Decimal Number
			$rule .= "number: true\n";
		break;
		
		case 8: // Date
			$rule .= "date: true\n";
		break;
		
	}
	
	$rule .= "},\n";
	
	return $rule;
}

/**
* Replace last occurrance of a string
*/
function str_replace_last( $search , $replace , $str ) {
    if( ( $pos = strrpos( $str , $search ) ) !== false ) {
        $search_length  = strlen( $search );
        $str    = substr_replace( $str , $replace , $pos , $search_length );
    }
    return $str;
}
