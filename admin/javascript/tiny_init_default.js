tinymce.init({
    selector: "textarea.mce",
	document_base_url : sdurl,
	relative_urls : false, // default: true
	remove_script_host: false,
    content_css : sdurl + "css.php?style=WYSIWYG", // SD CSS
	entity_encoding : "raw",
    force_br_newlines : true, // default: true
    force_p_newlines : false, // default: false
    forced_root_block : false, // default: false
    paste_auto_cleanup_on_paste : true, // default: true
    paste_remove_styles: true, // default: true
    paste_remove_styles_if_webkit: true, // default: true
    paste_strip_class_attributes: true, // default: true
	remove_linebreaks: false, // default: false
    verify_html : false, // default: false
	pagebreak_separator: "{pagebreak}",
	save_enablewhendirty: true,
	browser_spellcheck : true,
	plugins: [
			 "autosave anchor code textcolor emoticons pagebreak save",
			 "advlist autolink lists link image charmap print preview anchor",
        	 "searchreplace visualblocks code fullscreen",
        	 "insertdatetime media table contextmenu paste responsivefilemanager"
			 ],
	toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | forecolor backcolor | bullist numlist outdent indent | link responsivefilemanager image anchor emoticons pagebreak",
	image_advtab: true,
	
   external_filemanager_path:"../admin/filemanager/",
   filemanager_title:"File Manager" ,
   external_plugins: { "filemanager" : "../../admin/filemanager/plugin.min.js"}
 });
