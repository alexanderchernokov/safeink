var sd_tinymce_applied = false;
var sd_tiny_count = 0;
var sd_tiny_plugins="imagemanager,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,visualblocks,nonbreaking,xhtmlxtras,advlist,codemagic";
function ApplyTinyMCE(elem_id) {
  if(typeof (jQuery.fn.tinymce) !== 'undefined') {
    var selector = (elem_id === undefined ? 'textarea.tiny_mce_enabled' : '#'+elem_id);
    var res = jQuery(selector).tinymce({
      /* See: http://www.tinymce.com/wiki.php/Configuration
      Location of TinyMCE script */
      script_url : sdurl + "admin/tiny_mce/tiny_mce_gzip.php",
      document_base_url : sdurl,
      convert_urls : 1,
      relative_urls : 1,
      /* comment out with "//" to not include skins CSS: */
      content_css : sdurl+'css.php?style=WYSIWYG',
      apply_source_formatting : 0, /* deprecated */
      auto_cleanup_word : 0,
      editor_selector : "tiny_mce_enabled",
      editor_deselector : "tiny_mce_disabled",
      entity_encoding : "raw",
      flash_video_player_url: sdurl + "includes/javascript/moxieplayer.swf",
      flash_video_player_absvideourl: 0,
      force_br_newlines : 1,
      force_p_newlines : 0,
      language: "en",
      mode : "specific_textareas",
      skin : "default",
      verify_html : 0, /* deprecated */

      theme : "advanced",
      theme_advanced_layout_manager : "SimpleLayout",
      theme_advanced_resizing : true,
      theme_advanced_resizing_max_height : 800,
      theme_advanced_resizing_min_height : 240,
      theme_advanced_resizing_min_width  : 400,

      plugins : sd_tiny_plugins,

      theme_advanced_fonts : "Tahoma=tahoma,Trebuchet=Trebuchet MS,Arial=arial,helvetica,sans-serif;Verdana=verdana,arial,helvetica,sans-serif;Arial=arial,helvetica,sans-serif;Arial Black=arial black,avant garde;Book Antiqua=book antiqua,palatino;Comic Sans MS=comic sans ms,sans-serif;Courier New=courier new,courier,monospace;",

      theme_advanced_buttons1 : "save,newdocument,codemagic,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
      theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,help,code,|,insertdate,inserttime,|,forecolor,backcolor",
      theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,iespell,imagemanager,media,image,emotions,advhr,|,print,|,ltr,rtl,|,fullscreen",
      theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,visualblocks,nonbreaking,autolink",

      theme_advanced_toolbar_location : "top",
      theme_advanced_toolbar_align : "left",
      theme_advanced_statusbar_location : "bottom",
      theme_advanced_resizing : true,

      valid_elements: "*[*]",
      extended_valid_elements: "object[width|height|classid|codebase|align],param[name|value],embed[width|height|name|flashvars|src|bgcolor|align|play|loop|quality|allowscriptaccess|type|pluginspage|wmode]"
    });
    if(res.length>0) sd_tinymce_applied = true;
  }
}

function toggleEditor(id){
  if(!sd_tinymce_applied) {
    ApplyTinyMCE(id);
  }
  else
  {
    if(!tinymce.getInstanceById(id)){
      tinymce.execCommand('mceAddControl', false, id);
    } else {
      tinymce.execCommand('mceRemoveControl', false, id);
    }
  }
}

jQuery().ready(function() {
  if(typeof (jQuery.fn.tinymce) === 'undefined') return;
  sd_tiny_count = jQuery('textarea.tiny_mce_enabled').length;
  if(!wysiwyg_disabled && sd_tiny_count > 0) {
    ApplyTinyMCE();
  }
})
