if(typeof(jQuery) !== "undefined") {
  function DisableCheckboxes(disabledstatus) {
    var aform = document.forms["articleform"];
    if(jQuery("input#articleglobaloptions",aform).attr("checked") === "checked" || (disabledstatus === 1)) {
      jQuery("ul#article-settings input.globalsetting").attr("disabled", "disabled");
    } else {
      jQuery("ul#article-settings input.globalsetting").removeAttr("disabled");
    }
  }

  function UpdateImage(is_thumb, elem_name, elem_value) {
    var link = sdurl;
    if(is_thumb===1) {
      link += article_options.thumbnail_path;
    } else {
      link += article_options.featuredpic_path;
    }

    var doshow = 'none';
    if(elem_value==='') {
      $('img#'+elem_name).attr('src', sdurl+'includes/css/images/blank.gif');
    } else {
      doshow = 'block';
      $('img#'+elem_name).attr("src", link+elem_value);
    }

    $('input#'+elem_name+'path').val(elem_value);
    $('img#'+elem_name).css('display', doshow);
    $('img#'+elem_name).attr("width", 200);
    $('img#'+elem_name).css("display", doshow);
    if(is_thumb===1) {
      $('span#thumb_details').remove();
      $('a#detachthumb,a#deletethumb').css("display", doshow);
    } else {
      $('span#featuredpic_details').remove();
      $('a#detachfeatured,a#deletefeatured').css("display", doshow);
    }
  }

  function AddArticleTags() {
    var tagval = $("select#availabletags").val() || [];
    if(tagval && (tagval != "")) {
      $("ul.tagEditor").tagEditorAddTag(tagval);
    }
  }

  jQuery(document).ready(function() {
  (function($){
    $(".microtabs").microTabs({ selected: 0 });

    $("form#articles a.cbox, a.permissionslink").each(function(event) {
      $(this).addClass("cbox").attr("rel", "iframe modal:false height:240 width:900");
      var link = $(this).attr("href") + "&cbox=1";
      $(this).attr("href", link);
    });

    $("a.cbox").ceebox({
      animSpeed: "fast", fadeOut: 100, fadeIn: 100, borderWidth: "1px",
      html: true, htmlGallery: true, imageGallery: true,
      overlayOpacity: 0.8, margin: "70", padding: "14", titles: false
    });

    if (typeof($.fn.tagEditor) !== "undefined")
    $("#tags").tagEditor({
      completeOnSeparator: true,
      completeOnBlur: true,
      confirmRemoval: true,
      separator: ",",
      confirmRemovalText: article_options.lang_confirm_remove_tag,
      confirmRemovalTextTitle: article_options.lang_confirm_remove_tag_title
    });

    $("form#searcharticles select").change(function() {
      $("#searcharticles").submit();
    });

    $("a#articles-submit-search").click(function() {
      $("#searcharticles").submit();
    });

    $("a#articles-clear-search").click(function(e) {
      e.preventDefault();
      $("#searcharticles input").val("");
      $("#searcharticles select").prop("selectedIndex", 0); /* no attr anymore! */
      $("#searcharticles input[name=clearsearch]").val("1");
      $("#searcharticles").submit();
      return false;
    });

    if (typeof($.fn.datepick) !== "undefined") {
      $.datepick.setDefaults($.datepick.regional[article_options.lang_name]);

      var timeEntryOptions = {
        show24Hours: true,
        separator: ":",
        spinnerImage: article_options.include_path+"css/images/spinnerOrange.png"
      };
      var datePickerOptions = {
        yearRange: "1970:2099",
        altFormat: "yyyy-mm-dd",
        showTrigger: '<img src="'+article_options.include_path+'css/images/calendar.png" alt="..." with="16" height="16" />'
      };

      $("#timecreated,#timeupdated,#timestart,#time_featured_start,#timeend,#time_featured_end").timeEntry(timeEntryOptions);
	  
      var a_updated = false;
      var dvalue = $("#datecreated2").attr("rel") * 1000;
      var dc = new Date(dvalue);
	   $("#datecreated2").datetimepicker({
		  		sideBySide: true,
	  });
      //$("#datecreated2").datepick($.extend(datePickerOptions, { defaultDate: dc, altField: "#datecreated"}));
      if(dc > 0) {
        $("#datecreated2").data("DateTimePicker").setDate(dc);
      }

      dvalue = $("#dateupdated2").attr("rel") * 1000;
      dc = new Date(dvalue);
      $("#dateupdated2").datetimepicker({
		  		sideBySide: true,
	  });
      if(dc > 0) {
        $("#dateupdated2").data("DateTimePicker").setDate(dc);
      }

      dvalue = $("#datestart2").attr("rel") * 1000;
      dc = new Date(dvalue);
      $("#datestart2").datetimepicker({
		  		sideBySide: true,
	  });
      if(dc > 0) {
        $("#datestart2").data("DateTimePicker").setDate(dc);
      }

      dvalue = $("#dateend2").attr("rel") * 1000;
      dc = new Date(dvalue);
      $("#dateend2").datetimepicker({
		  		sideBySide: true,
	  });
      if(dc > 0) {
        $("#dateend2").data("DateTimePicker").setDate(dc);
      }

      dvalue = $("#featured_start2").attr("rel") * 1000;
      dc = new Date(dvalue);
      $("#featured_start2").datetimepicker({
		  		sideBySide: true,
	  });
      if(dc > 0) {
        $("#featured_start2").data("DateTimePicker").setDate(dc);
      }

      dvalue = $("#featured_end2").attr("rel") * 1000;
      dc = new Date(dvalue);
      $("#featured_end2").datetimepicker({
		  		sideBySide: true,
	  });
      if(dc > 0) {
        $("#featured_end2").data("DateTimePicker").setDate(dc);
      }

      $("#datecreated2,#dateupdated2,#datestart2,#dateend2,#featured_start2,#featured_end2").blur(function() {
        a_updated = $(this).val();
        if(!a_updated || a_updated === "") {
          $(this).attr("rel", "0");
          $(this).prev("input").val("0");
        }
      });
    }

    $(document).delegate("#addpage","click",function(){
      var page_id = $("select#page_select").val();
      if(typeof page_id !== "undefined") {
        var page_name = $("select#page_select :selected").text();
        page_id = parseInt(page_id,10);
        if(page_id > 0) {
          var id_exists = $("ul#article_pages #page_"+page_id).length;
          if (id_exists === 0) {
            $("ul#article_pages").append('<li class="delpage" id="page_'+page_id+'">'+
              '<input type="hidden" name="pages[]" value="'+page_id+'" />'+
              '<i class="ace-icon fa fa-trash-o red bigger-110"></i> '+
              page_name+'</li>');
          }
        }
      }
    });

    $(document).delegate("ul#article_pages li.delpage","click",function(){
      $(this).remove();
    });

    $(document).delegate("input[type=checkbox].delarticle","change",function(){
      var tr = $(this).parents("tr");
      $(tr).toggleClass("danger");
    });

    /* SD360 - extended thumbnail handling */
    $("a#deletethumb, a#detachthumb").click(function(e){
      e.preventDefault();
      var doit = confirm(article_options.lang_confirm_delete_thumbnail);
      if(!doit) return false;
      var pid =       $("input#pluginid").val();
      var articleid = parseInt($(this).attr("href"),10);
      var token =     $("input[name=form_token]").val();
      var thumb =     $("input#thumbnailpath").val();
      var thumb_org = $("input#thumbnail_org").val();
      if(thumb!==thumb_org && thumb_org!==''){
        UpdateImage(1, 'thumbnail', thumb_org);
      }
      else
      $.post("articles.php",
             { "pluginid":      pid,
               "articleid":     articleid,
               "articleaction": $(this).attr("id"),
               "form_token":    token },
             function(response, status, xhr){
               if(status !== "success" || response.substr(0,5)==="ERROR" || response != 1){
                 alert(response);
               } else {
                 $("input#thumbnailpath").val('');
                 $("input#thumbnail_org").val('');
                 $("a.thumbupload").css('display', 'block');
                 $("a#detachthumb,a#deletethumb,span#thumb_details").css('display', 'none');
                 $("img#thumbnail").attr('src', sdurl+'includes/css/images/blank.gif').css('display', 'none');
                 UpdateImage(0, 'thumbnail', '');
				  var n = noty({
					text: article_options.lang_thumbnail_removed,
					layout: 'top',
					type: 'success',	
					timeout: 5000,					
					});
               }
      });
      return false;
    });

    /* SD360 - extended featured picture handling */
    $("a#deletefeatured, a#detachfeatured").click(function(e){
      e.preventDefault();
      var doit = confirm(article_options.lang_confirm_delete_featurepic);
      if(!doit) return false;
      var pid =       $("input#pluginid").val();
      var articleid = parseInt($(this).attr("href"),10);
      var token =     $("input[name=form_token]").val();
      var thumb =     $("input#featuredpath").val();
      var thumb_org = $("input#featured_org").val();
      if(thumb!==thumb_org && thumb_org!==''){
        UpdateImage(0, 'featured', thumb_org);
      }
      else
      $.post("articles.php",
             { "pluginid":      pid,
               "articleid":     articleid,
               "articleaction": $(this).attr("id"),
               "form_token":    token },
             function(response, status, xhr){
               if(status !== "success" || response.substr(0,5)==="ERROR" || response != 1){
                 alert(response);
               } else {
                 $("input#featuredpath").val('');
                 $("input#featured_org").val('');
                 $("a.featuredpicupload").css('display', 'block');
                 $("a#detachfeatured,a#deletefeatured,span#featuredpic_details").css('display', 'none');
                 $("img#featured").attr('src', sdurl+'includes/css/images/blank.gif').css('display', 'none');
				  var n = noty({
					text: article_options.lang_featuredpic_removed,
					layout: 'top',
					type: 'success',	
					timeout: 5000,					
					});
               }
      });
      return false;
    });

    $("form#articles a.status_link").attr('unselectable','on')
      .css('MozUserSelect','none')
      .bind('dragstart', function(event) { event.preventDefault(); });
    $(document).delegate("form#articles a.status_link","click",function(e){
      e.preventDefault();
      var pid = $("form#searcharticles").find("input#pluginid").val();
      var elm = $(this).parent("div");
      var inp = elm.find("input:first");
      var newval = 1 - inp.val();
      inp.val(newval);
      elm.find("a").each(function(){ $(this).toggle(); }).end();
      elm = elm.parent("td").parent("tr").find("td:first input:hidden[name*=articleids]");
      if(elm.length==0) return;
      var articleid = elm.val();
      var token = $("form#articles input[name=form_token]").val();
      $.post("articles.php", {
          "pluginid": pid,
          "articleaction": "setarticlestatus",
          "articleid": articleid,
          "articlestatus": newval,
          "form_token": token },
        function(response, status, xhr){
          if(status !== "success" || response.substr(0,5)==="ERROR"){
            alert(response);
          } else {
            var n = noty({
					text: response,
					layout: 'top',
					type: 'success',	
					timeout: 5000,					
					});
          };
      });
      return false;
    });

    // SD344: author autocomplete and reassign author option
    var org_author_id = jQuery('input#org_author_id').val();
    org_author_id = parseInt(org_author_id,10);

    jQuery("input#UserSearch").blur(function(){
      // If authorname was blanked, revert to original author id
      // and hide the reassign option
      var newuser = $(this).val();
      if(newuser == "") {
        jQuery('input#newAuthorID').val(org_author_id);
        jQuery('input#reassign_author').parent('span').css('display','none');
      }
    }).autocomplete({
      source: sdurl+'includes/ajax/getuserselection.php',
      select: function(event,ui) {
        if(ui.item.userid) {
          var user_id = 0, old_id = 0;
          user_id = parseInt(ui.item.userid,10);
          if(user_id > 0) {
            // Assign selected, new author id
            jQuery('input#newAuthorID').val(user_id);
            // IF new differs from old id, then show reassign option
            if(org_author_id != user_id) {
              jQuery('input#reassign_author').attr('checked', null);
              jQuery('input#reassign_author').parent('span').css('display','block');
            }
          }
        }
      },
      maxItemsToShow: 10,
      minChars: 2
    });

    $("a#descr-hide").on("click",function(e){
      $("#descr-hide").css("display","none");
      $("#descr-show,div#description-container").css("display","block");
      $("div#description-container").hide();
      e.preventDefault();
    });
    $("a#descr-show").on("click",function(e){
      $("#descr-show").css("display","none");
      $("#descr-hide,div#description-container").css("display","block");
      $("div#description-container").show();
      e.preventDefault();
    });
    $("a#addtag").click(function(e){
      e.preventDefault();
      AddArticleTags();
    });
    $("#availabletags").dblclick(function(e){
      e.preventDefault();
      AddArticleTags();
    });

    /* SD360 */
    $("div#post_as_topic_options").css("display", "none");
    $("input#fp1").change(function(event) {
      if($(this).attr("checked") === "checked") {
        $("div#post_as_topic_options").css("display", "block");
      } else {
        $("div#post_as_topic_options").css("display", "none");
      }
    });
    $("#articleform a.imgdelete").click(function(event) {
      event.preventDefault();
      if(confirm('Delete attachment?')) {
        var aid = $(this).attr("id");
        var target = $(this).parents("li:first");
        if(target == "undefined") return false;
        $(target).load($(this).attr("href"), null,
          function(responseText, textStatus){
            if(textStatus=="success" && responseText==1) {
              $(target).remove();
			   var n = noty({
					text: textStatus,
					layout: 'top',
					type: 'success',	
					timeout: 5000,					
					});
            } else {
              alert("Error! "+responseText);
            }
        });
      }
      return false;
    });
    /* SD370: */
    $(document).delegate("form#articles a#checkall","click",function(e){
      e.preventDefault();
      var ischecked = 1 - parseInt($(this).attr("rel"),10);
      if(ischecked==1) {
        $("form#articles input.delarticle").attr("checked","checked");
        $("form#articles tr:not(thead tr)").addClass("danger"); /* not(":last-child"). */
      } else {
        $("form#articles input.delarticle").removeAttr("checked");
        $("form#articles tr").removeClass("danger");
      }
      $(this).attr("rel",ischecked);
      return false;
    });

   })(jQuery);
 });
}
