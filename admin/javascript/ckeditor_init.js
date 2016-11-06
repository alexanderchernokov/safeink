var sd_tiny_count = 0;

function ApplyCkeditor(elem_id) {
  if(typeof (jQuery.fn.ckeditor) !== 'undefined') {
    var selector = (elem_id === undefined ? 'textarea.ckeditor_enabled' : '#'+elem_id);
    var config = {
      skin : 'office2003',
      toolbar:
      [
        ['Bold', 'Italic', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink'],
        ['UIColor']
      ]
    };

    // Initialize the editor.
    // Callback function can be passed and executed after full instance creation.
    $(selector).ckeditor(/* config */);
  }
}

function toggleEditor(id){
  var o = CKEDITOR.instances[id];
  if (o) { o.destroy(); } else ApplyCkeditor(id);
  /*
  $("#"+id).ckeditor(function(){ this.destroy(); });
  CKEDITOR.instances[id].destroy();
  alert(CKEDITOR.basePath);
  alert( CKEDITOR.instances[id].name );
  */
}

jQuery().ready(function() {
  if(typeof (jQuery.fn.ckeditor) === 'undefined') return;
  sd_tiny_count = jQuery('textarea.ckeditor_enabled').length;
  if(!wysiwyg_disabled && sd_tiny_count > 0) {
    ApplyCkeditor();
  }
})
