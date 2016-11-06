//SD341: put main functions in sdmedia object closure
if(typeof(jQuery) !== "undefined") {
(function($){
  var cTipLoaded = (typeof($.fn.ceetip) !== "undefined");
  sdmedia = {
    ApplyCeetip: function() {
      if(cTipLoaded) {
        $("a.ceetip").ceetip({ arrow:"styles/"+media_options.style_folder_name+"/images/ceetip_arrow.png" });
      }
    },

    ResizeColumns: function() {
      var elem = $("div.content table:first");
      if(elem.length == 1){
        var tdwidth = parseInt(elem.width(),10)-elem.offset().left*2;
        if (tdwidth === null || !tdwidth.toString().match(/^[\-]?\d*\.?\d*$/) || tdwidth < (media_options.media_max_colums*100)){
          tdwidth = 400;
        }
        tdwidth = Math.round(tdwidth / media_options.media_max_colums);
        $("div.filecell").css({"max-width": tdwidth+"px", width: tdwidth+"px" });
      }
    },

    ReloadImages: function(){
      $("#files_loader").show();
      $("#progress_file_media").hide();
      $("p#ceetip").hide();
      var formdata = {
        "action":       "getimages",
        "page":         $("input#currentpage").val(),
        "pagesize":     $("#pagesize").val(),
        "folderpath":   $("#sel_folderpath").val(),
        "displaymode":  $("#displaymode").val(),
        "filter":       $("#filter").val(),
        "sortorder":    $("#sortorder").val(),
        "sortcolumn":   $("#sortcolumn").val(),
        "form_token":   media_options.media_token
      };
      var cookieval = JSON.stringify(formdata);
      cookieval = $().crypt({method:"b64enc", source: cookieval});
      $.cookie(media_options.toolbar_cookie_name, cookieval);
      $("div#files_list").load(media_options.load_page, formdata,
       function(){
        $("p#ceetip").hide();
        sdmedia.ApplyCeetip();
        sdmedia.ResizeColumns();
        $("#files_loader").hide();
      });
    },

    ImageSelectionEnd: function(img, selection){
      if(selection.width === 0 || selection.height === 0) {
        $("#preview_details").html(media_options.selection_note);
        $("input#maxwidth").val(media_options.default_thumb_width);
        $("input#maxheight").val(media_options.default_thumb_height);
        $("select#thumb_mode").val(0);
        $("select#thumb_mode").removeAttr("disabled");
        $("#thumb_dummy").hide();
      }
      else {
        $("select#thumb_mode").val(1);
        $("select#thumb_mode").attr("disabled","disabled");
        $("input#maxwidth").val(Math.round(scale_x * selection.width));
        $("input#maxheight").val(Math.round(scale_y * selection.height));
      }
      $(img_form).find("input[name=x1]").val(Math.round(scale_x * selection.x1));
      $(img_form).find("input[name=y1]").val(Math.round(scale_y * selection.y1));
      $(img_form).find("input[name=x2]").val(Math.round(scale_x * selection.x2));
      $(img_form).find("input[name=y2]").val(Math.round(scale_y * selection.y2));
    },

    // Show a preview of the selection
    preview: function(img, selection) {
      var box = $("#thumb_dummy");
      if(selection.width === 0 || selection.height === 0) {
        box.hide();
        return;
      }
      box.show();
      var maxWidth  = box.width() -10;
      var maxHeight = box.height()-20;
      $("#thumb_container").css({ width: maxWidth+"px", height: maxHeight+"px" });

      var scaleX = maxWidth  / selection.width;
      var scaleY = maxHeight / selection.height;
      var scale2 = scaleX / scaleY;
      if(scale2 < 1) {
        var newHeight = Math.round(maxHeight*scaleX/scaleY);
        $("#thumb_container").css({ height: newHeight+"px" });
        scaleY = scale2 * scaleY;
      }
      if(scale2 > 1) {
        var newWidth = Math.round(maxWidth*scaleY/scaleX);
        $("#thumb_container").css({ width: newWidth+"px" });
        scaleX = scaleX / scale2;
      }

      $("#thumbnail_preview").css({
        width:      Math.round(scaleX * pre_width) + "px",
        height:     Math.round(scaleY * pre_height) + "px",
        marginLeft: "-" + Math.round(scaleX * selection.x1) + "px",
        marginTop:  "-" + Math.round(scaleY * selection.y1) + "px"
      });
    },

    ImageSelectionUpdate: function(img, selection){
      if(selection.width === 0 || selection.height === 0) {
        $("#preview_details").html(media_options.selection_note);
        $("input#maxwidth").val(media_options.default_thumb_width);
        $("input#maxheight").val(media_options.default_thumb_height);
      }
      var width  = Math.round(scale_x * selection.width);
      var height = Math.round(scale_y * selection.height);
      var scale = Math.round(selection.width / selection.height*100)/100;
      $("#preview_details").html(
        "Selection Width/Height = <strong>"+Math.round(width)+
        "</strong>px / <strong>"+Math.round(height)+"</strong>px" +
        " - Scale: "+scale+"<br />"+
        "Top-Left: (x: <strong>"+Math.round(selection.x1*scale_x)+
        "</strong> / y: <strong>"+Math.round(selection.y1*scale_y)+
        "</strong>) &raquo; Bottom-Right: (x: <strong>"+Math.round(selection.x2*scale_x)+
        "</strong> / y: <strong>"+Math.round(selection.y2*scale_y)+")</strong>");
      sdmedia.preview(img, selection);
    },

    ApplyImageSelector: function(){
      var image = $("#previewimg");
      if(image.length===1) {
        if(ias !== false) { return false; }
        ias = true;
        pre_width  = parseInt($(image).width(),10);
        pre_height = parseInt($(image).height(),10);
        img_width  = $("input#img_width").val();
        img_height = $("input#img_height").val();
        scale_x    = img_width / pre_width;
        scale_y    = img_height / pre_height;
        $("div#previewimg").attr("title","");
        ias = image.imgAreaSelect({
          instance: true,
          handles: true,
          fadeSpeed: 250,
          onSelectEnd: sdmedia.ImageSelectionEnd,
          onSelectChange: sdmedia.ImageSelectionUpdate
        });
      }
    },

    PositionLoader: function(){
      if(typeof previewbox !== "undefined") {
        var pos = $(previewbox).position();
        var width = $(previewbox).width();
        var height = $(previewbox).height();
        $("div#previewloader").css({
          top:  pos.top + Math.round(height / 2),
          left: pos.left + Math.round((width-16) / 2)
        });
        $("div#previewloader").css({ display: "inline" });
      }
    }
  };
}(jQuery));

/* ********* DOCUMENT READY ********** */
(function($){
  $(document).ready(function() {
  if(media_options.action !== "deleteimage")
  {
    if(media_options.action !== "displayimages")
    {
      previewbox = $("div.previewbox:first");
      if(previewbox.length > 0)
      sdmedia.PositionLoader(); //must be at start!

      img_form   = $("form#image_form");
      img_width  = $("input#img_width").val();
      img_height = $("input#img_height").val();

      $("input.media_size_btn").each(function(){
        $(this).click(function(event){
          event.preventDefault();
          var btn = $(this).attr("name");
          if(btn === "size_original") {
            $("#image_form input#maxwidth").val(img_width);
            $("#image_form input#maxheight").val(img_height);
          }
          else {
            var sizes = btn.split("_");
            sizes = sizes[1].split("x");
            $("#image_form input#maxwidth").val(parseInt(sizes[0],10));
            $("#image_form input#maxheight").val(parseInt(sizes[1],10));
          }
        });
      });

      if(img_form.length == 1)
      img_form.submit(function(event){
        event.preventDefault();

        var new_width  = parseInt($("input#maxwidth").val(),10);
        var new_height = parseInt($("input#maxheight").val(),10);
        if(new_width < 2 || new_height < 2 || new_width > 4096 || new_height > 4096) {
          alert(media_options.media_image_err_sizes);
          return false;
        }

        var elem = $(this).find("select[name=folderpath]");
        var foldername = elem.val();
        foldername = foldername.indexOf('*');
        if(foldername > 0){
          elem.focus();
          alert(media_options.media_image_err_folder);
          return false;
        }

        // Hide ImageSelector
        ias.setOptions({ disable: true, hide: true });
        ias.update();

        $("div#previewloader").css("display", "inline");
        $(img_form).find("input[name=action]").val();
        var formdata = $(img_form).serialize();
        $.post(
          media_options.admin_path+"/media.php?action=refresh", formdata,
          function(data) {
            var content = $(data).html();
            $("div#image_details").html(content);
            $("div#previewloader").css("display", "none");
            ApplyCeebox();
            sdmedia.ApplyImageSelector();
            $("html").animate({scrollTop : 0},"fast", function(){
              var url = $("form#form_delete").attr("action");
              url = url.replace("deleteimage","display_media_details");
              location.replace(url);
            });
          }
        );
        return false;
      });

      $("select#file_mode").change(function(){
        if(parseInt($(this).val(),10) === 1) {
          $("#thumbfilename").css("color","#d0d0d0");
          $("#thumbfilename").attr("disabled","disabled");
        }
        else {
          $("#thumbfilename").css("color","#000");
          $("#thumbfilename").removeAttr("disabled");
        }
      });

      $("form#form_delete").bind("submit",function() {
        return (true === confirm(media_options.media_confirm_deletion));
      });

      if(typeof($.fn.jPicker) !== "undefined") {
        $(".colorpicker").jPicker({
          images: {clientPath: sdurl+"includes/css/images/jpicker/"}
        }).addClass("jPickered");
      }

      /* SD362: input mask filters */
     // $.mask.definitions["~"] = "[+-9]";
     // $.mask.definitions["h"] = "[A-Fa-f0-9]";
      $(".mg_brightness, .mg_contrast").mask("~9?99");
      $(".mg_colorize").mask("hh");
      $(".mg_rotate, .mg_smooth").mask("9?99");
      $(".mg_gamma").mask("9?.99");
      $(".mg_pixelate").mask("9?9");
      $(".colorx").mask("hhhhhh");

    }
    else
    {
      /* Action Display Images */
      sdmedia.ResizeColumns();
      $("a#del_filter").click(function(e) {
        e.preventDefault();
        $("input#filter").val("");
        sdmedia.ReloadImages();
        return false;
      });
      $("a#sort_asc").click(function(e) {
        e.preventDefault();
        $("input#sortorder").val("asc");
        sdmedia.ReloadImages();
        return false;
      });
      $("a#sort_desc").click(function(e) {
        e.preventDefault();
        $("input#sortorder").val("desc");
        sdmedia.ReloadImages();
        return false;
      });
      $("#browse_files").submit(function(e) {
        e.preventDefault();
        sdmedia.ReloadImages();
        return false;
      });
      $("#toolbar select").change(function(e) {
        e.preventDefault();
        $("input#currentpage").val(0);
        $("input#sortcolumn").val(0);
        $("input#sortorder").val("desc");
        sdmedia.ReloadImages();
      });
      $("#instant_folder").change(function(e) {
        e.preventDefault();
        $("#sel_folderpath").val($(this).val());
        $("input#currentpage").val(0);
        sdmedia.ReloadImages();
      });

      /* Live events for ajax-reloaded files list */
      $(document).delegate("div.pagination a","click",function(e) {
        e.preventDefault();
        var elem = $(this);
        var href = elem.attr("href");
        var page = href.split("page=")[1];
        $("input#currentpage").val(page-1);
        sdmedia.ReloadImages();
        return false;
      });

      $(window).resize(function() {
        try { sdmedia.ResizeColumns(); } catch(err){}
      });
    }

    ApplyCeebox();
    sdmedia.ApplyCeetip();
  }
  }); /* EOF DOCUMENT READY */
}(jQuery));
}
