<?php

if(!defined('IN_PRGM') || !defined('IN_ADMIN')) return false;

/*
  Class for easier usage of: PLUPLOAD by Moxiecode: http://www.plupload.com/
  Requires external styles for input buttons:
  plupload_button
  plupload_add
*/

// ############################################################################
// SD_UPLOADER CLASS
// ############################################################################

class SD_Uploader
{
  private $html_template  = '';
  private $js_template    = '';
  private $embedderParams = array();
  private $settings       = array();

  // translations are public properties for now:
  public $button_select_text = 'Select File';
  public $button_upload_text = 'Upload';
  public $error_noruntime    = 'Your browser does not offer a supported runtime!';
  public $upload_header		= "Upload Media";
  public $file_label      = 'File: ';
  public $error_label     = 'Error: ';
  public $error_failed    = 'Upload failed:';
  public $error_empty     = 'Filename empty!';
  public $message_success = 'File temporarily uploaded!';


  public function SD_Uploader($pluginid = 0, $fileid = 0, $_params = array())
  {
    global $DB, $userinfo;

    $this->embedderParams = SD_Uploader::getDefaultParams();
    $this->settings = array_merge($this->embedderParams, $_params);
    $this->settings['pluginid'] = empty($pluginid)?'admin':(int)$pluginid;
    $this->settings['fileid']   = empty($fileid)?0:(int)$fileid;

  } //SD_Uploader


  /**
   * Returns a setting's value
   */
  public function getValue($setting)
  {
    return $this->settings[$setting];
  }

  /**
   * Set the value for a given setting
   */
  public function setValue($setting, $value)
  {
    $this->settings[$setting] = (isset($value)&&strlen($value) ? $value: '');
  }


  /**
   * Returns the default parameters
   */
  public static function getDefaultParams()
  {
    // Note: EACH entriy is to be a string!
    return array(
      // General "plupload"-related params:
      'runtimes'        => 'silverlight,gears,flash,html5,browserplus,html4',
      'browse_button'   => 'select_file',
      'chunk_size'      => '256kb',
      'max_file_size'   => '512mb',
      'unique_names'    => 'true',
      'filters'         => '
        {title : "All files", extensions : "*"},
        {title : "Archives", extensions : "zip,rar,7zip,arj"},
        {title : "Images", extensions : "jpg,jpeg,gif,png,tif,svg,bmp"},
        {title : "Media", extensions : "flv,mp2,mp3,mp4,m4v,m4a,3g2,mov,mpeg,mpg,mpe,mpga,mpega,ogv,ogg,swf,swfl,avi,rv,rm,webm,wma,wmv,xaml,aac"},
        {title : "Documents", extensions : "pdf,doc,docx,dot,xls,xlsb,xlsx,ppt,pps,pot,pptx,rtf,txt,log,dmp,sql,iso,md5,cue,bin,cdr,nrg,exe"},
        {title : "Web", extensions : "htm,html,css,js,tpl,inc,php,php5"}',

      // Params required for this class itself:
      'html_id'         => 'uploader',
      'upload_button'   => 'upload_file',
      'token_name'      => SD_TOKEN_NAME,
      'title'           => '<strong>Select a single file for upload:</strong>',
      'resize'          => '',
      'singleUpload'    => true,
      'afterUpload'     => '""',
      'maxQueueCount'   => 0,
      'removeAfterSuccess' => "true",
	  'layout'			=>	"col-sm-6 col-sm-offset-3",
	  'border'			=>	true,
    );

  } //getDefaultParams


  /**
   * Get's the current HTML template
   */
  public function getHTML()
  {
    $this->html_template = '
	<div class="'.$this->settings['layout'].'">
	' . ($this->settings['border'] ? '<div class="table-header">' . $this->upload_header . '</div>' : '') .'
	<table class="table ' . ($this->settings['border'] ? 'table-bordered' : '') .'">
	<tr>
		<td>
    <div  id="uploader_'.$this->settings['html_id'].'" class="col-sm-13">
		<div class="form-group">
			<div class="center">'.$this->settings['title'].' </div>
			<div class="space-10"></div>
			<div class="center">
        		<button id="'.$this->settings['html_id'].'_'.$this->settings['browse_button'].'" type="button" class="btn btn-sm btn-info" value="" /><i class="ace-icon fa fa-search"></i> '.$this->button_select_text.'</button>
        		<button id="'.$this->settings['html_id'].'_'.$this->settings['upload_button'].'" type="button" class="btn btn-sm btn-success" value="" /><i class="ace-icon fa fa-upload"></i> '.$this->button_upload_text.'</button>
			</div>
		</div>

	<div class="space-6"></div>
     <div class="uploader_progress center">
     	<div class="uploader_progress"  id="progress_'.$this->settings['html_id'].'"></div>
     </div>
	 <br />
      <div class="" id="filelist_'.$this->settings['html_id'].'">'.$this->error_noruntime.'</div>
	  <div class="clearfix"></div>
      <div class="alert alert-info" id="uploader_'.$this->settings['html_id'].'_messages">...</div></div>
	  </td>
	  </tr>
	  </table>
	 </div>
	 <div class="clearfix"></div>

    ';
    return $this->html_template;
  }

  /**
   * Get's the current JS code with params in place
   */
  public function getJS()
  {
    global $userinfo;

    $this->js_template = '';

    if(!isset($this->settings['html_id']) || !strlen(trim($this->settings['html_id'])))
    {
      return '';
    }

    // Note: below "multipart_params" are required important for actual upload script to accept files!
    $this->js_template .= '
    jQuery("#progress_%html_id%").progressBar(0, {
      max: 100,
      showText: true,
      boxImage: "'.SITE_URL.'includes/images/progressbar.gif",
      barImage: {
         0:  "'.SITE_URL.'includes/images/progressbg_red.gif",
         33: "'.SITE_URL.'includes/images/progressbg_orange.gif",
         66: "'.SITE_URL.'includes/images/progressbg_green.gif" }
    });
    jQuery("#progress_%html_id%").hide();

    var sd_%html_id%_settings = {
      numSelectedFiles: 0,
      numTotalSize: 0,
      maxQueueCount: %maxQueueCount%
    };
    var uploader_%html_id% = new plupload.Uploader({
      runtimes : "%runtimes%",
      browse_button : "%html_id%_%browse_button%",
      button_browse_hover: "uploader_browse_hover",
      button_browse_active: "uploader_browse_active",
      chunk_size: "%chunk_size%",
      max_file_size : "%max_file_size%",
      unique_names: %unique_names%,
      container: "uploader_%html_id%",
      resize: {%resize%},
      multipart_params: {
        userid : '.(int)$userinfo['userid'].',
        pluginid : %pluginid%,
        fileid : %fileid%,
        securitytoken : $("form input:hidden[name=%token_name%]").val()
      },
      url : "'.SITE_URL.'includes/sd_upload.php",
      flash_swf_url: "'.SITE_URL.'includes/plupload/plupload.flash.swf",
      silverlight_xap_url: "'.SITE_URL.'includes/plupload/plupload.silverlight.xap",
      filters : [%filters%]
    });

    uploader_%html_id%.bind("Init", function(up, params) {
      jQuery("#filelist_%html_id%").html("<div id=\'uploader_%html_id%_info\'>Uploader ready, using " + params.runtime+"<\/div>");';

    if(!empty($this->settings['html_upload_remove']))
    {
      #$this->js_template .= '  jQuery("#%html_upload_remove%").hide();';
      $this->js_template .= '  jQuery("#%html_upload_remove%").remove();';
    }

    $this->js_template .= '
    });

    jQuery("#%html_id%_%upload_button%").click(function(e) {
      if(uploader_%html_id%.total.queued > 0) {
        jQuery("#progress_%html_id%").show();
        uploader_%html_id%.start();
      }
      e.preventDefault();
      return false;
    });

    uploader_%html_id%.bind("FilesAdded", function(up, files) {
      jQuery("#uploader_%html_id%_info").remove();
      jQuery.each(files, function(i, file) {
        if(sd_%html_id%_settings.maxQueueCount === 1) {
          jQuery("#filelist_%html_id%").html("");
        }
        if(sd_%html_id%_settings.maxQueueCount === 0 || sd_%html_id%_settings.maxQueueCount > jQuery("#filelist_%html_id% div").length) {
          jQuery("#filelist_%html_id%").append(
            "<div class=\"fileentry_%html_id%\" id=\'" + file.id + "\'><span>" + file.name + " (" + plupload.formatSize(file.size) + ") <b><\/b><\/span><\/div>");
          sd_%html_id%_settings.numSelectedFiles += 1;
        } else {
          file.status = 4;
        }
      });
      if(sd_%html_id%_settings.maxQueueCount > 1 && jQuery("#filelist_%html_id% div").length >= sd_%html_id%_settings.maxQueueCount) {
        alert("Queue limit reached: max. "+up.total.queued+" files allowed!");
        return false;
      }
      //uploader.total.size
      //uploader.total.queued
      return true;
    });

    /* Check the queue after files added to see if the total size of all files combined exceeds allowed limit. */
    var maxQueueSize = 681574400; // Size in bytes. This is set to 650MB.
    uploader_%html_id%.bind("QueueChanged", function(up) {
       if(uploader_%html_id%.total.size > maxQueueSize) {
          alert("Total size of all files exceeds 650MB! Remove some files!");
       }
    });
    var $uploader_%html_id%_after = %afterUpload%;
    uploader_%html_id%.bind("FileUploaded", function(up, file, response) {
      var uploader_%html_id%_single = %singleUpload%;
      var obj = jQuery.parseJSON(response.response);
      jQuery("#progress_%html_id%").progressBar(0);
      if(obj.error !== undefined && obj.error.code > 990) {
        file.status = 4;
        jQuery("#"+file.id+" span b").html("<span style=\"color: red\">'.$this->error_failed.' " + obj.error.message + "<\/span>");
        alert(obj.error.message);
      }
      else
      {
        if(obj.result !== undefined && obj.result.filename !== "") {
          if(sd_%html_id%_settings.maxQueueCount < 2) {
            jQuery("form").find("input.fileentry_%html_id%").remove();
          }
          jQuery("form").append("<input class=\"fileentry_%html_id%\" type=\"hidden\" name=\"%html_id%["+obj.result.filename+"]\" value=\""+file.name+"\" />");
          //jQuery("#"+file.id+" span").html(file.name + " - <strong>'.$this->message_success.'<\/strong>");
          if(%removeAfterSuccess% === true) {
            jQuery("#"+file.id).remove();
          }
          if($uploader_%html_id%_after)
          {
            $uploader_%html_id%_after("%html_id%", obj.result.filename, file.name);
          }'
          /*
          if(!uploader_%html_id%_single)
          {
            jQuery("#uploader_%html_id%").after("<strong>'.$this->message_success.'</strong>");
            jQuery("#uploader_%html_id%").hide();
          }
          else
          {
            jQuery("#" + file.id + " span b").html("Ok!");
          } */
          .'
        } else {
          jQuery("#filelist_%html_id%").append(
            "<div id=\'" + file.id + "\' style=\"color: red\">'.$this->error_failed.' '.$this->error_empty.'<\/div>");
        }
      }
    });

    uploader_%html_id%.bind("UploadProgress", function(up, file) {
      // if(uploader_%html_id%.total.uploaded !== uploader_%html_id%.files.length) {
      //   $("#%html_id%_%browse_button%").css({"display": "none"});
      // }
      if(file.size > 0 && up.total.queued) {
        jQuery("#" + file.id + " span b").html(file.percent + "%");
        //jQuery("#progress_%html_id%").show();
        jQuery("#progress_%html_id%").progressBar(file.percent);
      }
      if(up.total.queued === 0) {
       jQuery("#progress_%html_id%").hide();
      }
    });

    uploader_%html_id%.bind("Error", function(up, err) {
      uploader_%html_id%.removeFile(uploader_%html_id%.files[0]);
      jQuery("#progress_%html_id%").progressBar(0);
      jQuery("#progress_%html_id%").hide();
      jQuery("#"+err.file.id).remove();
      if(err.file) alert(err.file.name + ": "+ err.message); '.
    /*
      jQuery("#"+err.file.id+" span b").html(err.file ? ": "+ err.message : "");
      jQuery("#filelist_%html_id%").append(
        "<div id=\'" + err.file.id + "\' style=\"color: red\">'.$this->error_label.' " + err.code +
        ", Message: " + err.message + (err.file ? ", '.$this->file_label.' " + err.file.name : "") + "<\/div>");
    */ '
      up.refresh(); // Reposition Flash/Silverlight
    });

    jQuery("div#uploader_%html_id%").show();
    uploader_%html_id%.init();
    ';

    if(isset($this->settings['resize']) && strlen(trim($this->settings['resize'])))
    {
      //$this->settings['resize'] = 'resize: {'.trim($this->settings['resize']).'},';
    }
    else
    {
      $this->settings['resize'] = '';
    }
    // Replace remaining placeholders with settings:
    foreach ($this->settings as $k => $v)
    {
      $this->js_template = str_replace('%'.$k.'%', $v, $this->js_template);
    }
    return $this->js_template;

  } // getJSTemplate

} // end of CSS class
