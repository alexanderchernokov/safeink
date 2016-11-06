{* Comment Form template - 2015-05-31 *}
{* Put any comment in curly brackets with * next to it like the first line.
   Comments are not displayed on the website and can assist in documentation. *}
<script type="text/javascript">
jQuery(document).ready(function() {
if(typeof (jQuery.fn.ajaxSubmit) === "undefined") {
  jQuery.getScript(sdurl+"includes/javascript/jquery.form.min.js");
}
jQuery("form#comment-form-p{$pluginid}").submit(function(e){
  e.preventDefault();
  pid = $("input[name=comment_plugin_id]",this).val();
  oid = $("input[name=comment_object_id]",this).val();
  token = $("input[name=comment_token]",this).val();
  pagelink = $(this).attr("action")+"#comments";
  submittolink = "{$sdurl}includes/ajax/sd_ajax_comments.php?do=insertcomment&amp;oid="+oid+"&amp;pid="+pid+"&amp;securitytoken="+token;
  $(this).ajaxSubmit({
    type:      "POST",
    url:       submittolink,
    dataType:  "html",
    clearForm: false,
    target:    "#comment-form-p{$pluginid}",
    success:   function(responseText, statusText, xhr, $form) {
      if(typeof(responseText) === "undefined") {
        alert("Error!");
      } else
      if(responseText !== "success") {
        alert(responseText);
      } else {
        $("textarea#comment_comment").val("");
        location.reload();
      }
    }
  });
  return false;
});
});
</script>

	<form id="comment-form-p{$pluginid}" name="comment-form-p{$pluginid}" action="{$link}" method="post">
    <input type="hidden" name="comment_action" value="insert_comment" />
    <input type="hidden" name="comment_plugin_id" value="{$pluginid}" />
    <input type="hidden" name="comment_object_id" value="{$objectid}" />
    <input type="hidden" name="categoryid" value="{$categoryid}" />
    {$secure_token}
    
    {if $userinfo.loggedin}
    	<input type="hidden" name="comment_username" value="{$userinfo.username}" />
    {/if}
   
    {if !$userinfo.loggedin}
     <div class="comment_username">
     	<label for="comment_username">{$sdlanguage.your_name}</label>
     	<input type="text" name="comment_username" id="comment_username" value="{$comment_username}" />
     </div> 
    {/if}
    
   
     <div class="comment_editor_header">
      <a name="comments_bottom">{$sdlanguage.comment}</a>
     	{$bbeditor}
     </div>
    <br />
     
     {$captcha}
  
     {$footer}
     
     </form>
     
  	{if $allow_bbcode}
		<script src="{$sdurl}includes/tiny_mce/tinymce.min.js"></script>
    {/if}
	
    <script type="text/javascript">
    //<![CDATA[
    jQuery(document).ready(function() {

   {if $allow_bbcode}
		tinymce.init({
			selector: "textarea.bbeditor",
			setup: function (editor) {
				editor.on('change', function() {
					editor.save();
					});
				},
			document_base_url : "{$sdurl}",
			relative_urls : false, // default: true
			menubar: false,
			remove_script_host: false,
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
			save_enablewhendirty: true,
			browser_spellcheck : true,
			plugins: [
					 "bbcode textcolor emoticons advlist lists link image code"
					 ],
			toolbar: "undo redo | bold italic | forecolor | bullist numlist | image anchor emoticons link",
			image_advtab: true,
		 });
    {/if}

  
      jQuery("form[name=comment-form-p{$pluginid}]").submit(function(){
        var comment_text = tinymce.activeEditor.getContent();
        if(typeof comment_text !== "undefined") {
          if(comment_text.length < 3) {
            alert("{$sdlanguage.enter_comment|escape}");
            return false;
          }
        }
      });
    });
    //]]>
    </script>
	
   
