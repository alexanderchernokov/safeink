var skinTable, itemsDiv=false, $hotkeys, winheight = 0, editor_bottom, editor_top,
    CSSMode = false, HTMLMode = false, PHPMode = false, JSMode = false,
    legacy_skin = 0;

function FocusEditor() {
  try {
    if(editor !== null && typeof editor !== "undefined")
      editor.focus();
    else
      jQuery(ed_id).focus();
  } catch(err){};
}

function insertAtCursor(myValue) {
  var myField = jQuery(ed_id);//document.getElementById("skincontent");
  /* IE support */
  if (document.selection) {
    myField.focus();
    var sel = document.selection.createRange();
    sel.text = myValue;
  }
  /* MOZILLA/NETSCAPE support */
  else
  if(myField.selectionStart || myField.selectionStart === "0"){
    var startPos = myField.selectionStart;
    var endPos = myField.selectionEnd;
    var restoreTop = myField.scrollTop;
    myField.value = myField.value.substring(0, startPos) + myValue + myField.value.substring(endPos, myField.value.length);
    myField.selectionStart = startPos + myValue.length;
    myField.selectionEnd = startPos + myValue.length;

    if(restoreTop > 0) {
      myField.scrollTop = restoreTop;
    }
  }
  else {
    myField.value += myValue;
  }
  FocusEditor();
}

function showIndicatorBlock() {
  jQuery("#indicator").css("display", "inline");
  /*
  jQuery("div.content").block({
     css: {
       "background-color": "#0f0f0f",
       border: "1px solid #808080",
       color: "#fff",
       "border-radius": "20px",
       "font-weight": "bold",
       "-moz-border-radius": "20px",
       "-webkit-border-radius": "20px",
       opacity: .6,
       padding: "20px",
       left: "",
       right: "20px",
       width: "350px",
       top: "130px"
     },
     fadeOut: 100,
     centerY: 0,
     timeout: 1000,
     showOverlay: true,
     message: "Please wait..."
  });
  */
}
function showIndicator() { jQuery("#indicator").css("display", "inline"); }
function hideIndicator() { jQuery("#indicator").hide(); }

function toggleHighlightingTheme(theme) {
  if(typeof editor !== "undefined") {
    editor.setTheme(theme);
    jQuery.cookie(skins_options.cookie_name, theme);
    jQuery("#ace_theme").val(theme);
    FocusEditor();
  }
}

function SaveContent() {
  if(editor !== null && typeof editor !== "undefined") {
    var content = editor.getSession().getDocument().getValue();
    jQuery("#skincontent").text(content);
  }
}

function showResponse(msg, blocking) {
  if(!msg || msg == "" || msg == "1") { msg = skins_options.lang_skins_skin_updated; }
  content_changed = 0;
  jQuery("div#editor_bottom #ed_changed").css({ "visibility": "hidden" });
  jQuery.unblockUI();
	var n = noty({
			text: skins_options.lang_skins_skin_updated,
			layout: 'top',
			type: 'success',	
			timeout: 5000,					
			});
  hideIndicator();
}

function MarkChanged() {
  content_changed = (editor.getSession().getUndoManager().hasUndo()?1:0);
  if(content_changed) {
    jQuery("div#editor_bottom #ed_changed").css({ "visibility":"visible" });
  } else {
    jQuery("div#editor_bottom #ed_changed").css({ "visibility":"hidden" });
  }
}

function ApplyEditor(structure) {
  if($("div#skineditor").length === 0){
    return false;
  }
  if(skins_options.fallback) {
    jQuery("div#skineditor").hide();
    jQuery("textarea#skincontent").css("fontSize",14).show().focus();
    return;
  }

  if(typeof ace !== "undefined") {
    var config = ace.require("ace/config");
    config.init();
    ace.require("ace/ext/language_tools");
    var snippetManager = require("ace/snippets").snippetManager;
    var Autocomplete = require("ace/autocomplete").Autocomplete;

    editor = ace.edit("skineditor");
    editor.setOptions({
      enableBasicAutocompletion: true,
      enableMultiselect: false,
      enableSnippets: true,
      highlightActiveLine: true,
      scrollSpeed: 3,
      showGutter: true,
      showInvisibles: false,
      showPrintMargin: false,
      spellcheck: false,
      tabSize: 2,
      useIncrementalSearch: true,
      useSoftTabs: false,
      useWorker: true,
      useWrapMode: false,
    });

    //SD362: try to map CTRL+S to trigger saving
    // Tested with FF24, Chrome 30, IE10
    editor.commands.addCommands([{
      name: "saveDoc",
      bindKey: {win: "Ctrl-S", mac: "Command-s"},
      exec: function(editor) {
        jQuery('#skinform').submit();
      }
    }]);

    editor.commands.addCommands([{
      name: "insertSpace",
      bindKey: {win: "Space|Shift-Space", mac: "Space"},
      exec: function(editor) {
        editor.insert(" ");
      }
    }]);

    //SD362: display an overlay with all current hotkeys
    var KeyBindings = ace.require('ace/ext/keybinding_menu').Keybinding_Menu;
    editor.commands.addCommand({
      name: "showKeyboardShortcuts",
      bindKey: {win: "Ctrl-Alt-h", mac: "Command-Alt-h"},
      exec: function(editor) {
        config.loadModule("ace/ext/keybinding_menu", function(module) {
          module.init(editor);
          editor.showKeyboardShortcuts()
        })
      }
    });
    // Remap some keys:
    editor.commands.removeCommand("copylinesdown");
    editor.commands.addCommand({
      name: "copylinesdown",
      bindKey: {win: "Ctrl-d", mac: "Command-d"},
      exec: function(editor) {
        editor.copyLinesDown();
      }
    });
    editor.commands.removeCommand("redo");
    editor.commands.addCommand({
      name: "redo",
      bindKey: {win: "Ctrl-Shift-Z", mac: "Command-Shift-Z"},
      exec: function(editor) {
        editor.redo();
      }
    });
    editor.commands.removeCommand("removeline");
    editor.commands.addCommand({
      name: "removeline",
      bindKey: {win: "Ctrl-y", mac: "Command-y"},
      exec: function(editor) {
        editor.removeLines();
      }
    });
    editor.commands.removeCommand("gotoline");
    editor.commands.addCommand({
      name: "gotoline",
      bindKey: {win: "Ctrl-Alt-L", mac: "Command-L"},
      exec: function(editor) {
        var line = parseInt(prompt("Enter line number:"), 10);
        if (!isNaN(line)) {
            editor.gotoLine(line);
        }
      }
    });

    var StatusBar = ace.require('ace/ext/statusbar').StatusBar;
    var statusBar = new StatusBar(editor, document.getElementById('editor_statusBar'));

    editor.on("blur", function(){ SaveContent(); });
    editor.on("change", function(){  MarkChanged(); });

    var hl_theme = jQuery.cookie(skins_options.cookie_name);
    if((typeof hl_theme === "undefined") || !hl_theme || (hl_theme === "null") || (hl_theme === "")) {
      var hl_theme = "ace/theme/textmate";
    }
    //SD362: finally, set editor theme
    toggleHighlightingTheme(hl_theme);
    jQuery("#ace_theme").val(hl_theme);

    //SD362: set mode (html or css during pageload)
    var key_mode = "html";
    if(structure === "css") { key_mode = "css"; }
    jQuery("select#mode_selector").val(key_mode);
    jQuery("select#mode_selector").trigger("change");

    //fix:
    jQuery("div.ace_sb").css("height", "100%");

    editor.setAutoScrollEditorIntoView();
    FocusEditor();
  }
}

function ResizeCSSlist() {
  /* SD370: resize CSS list */
  try {
    winheight = jQuery(window).height();
    var css_items_height = winheight - itemsDiv.offset().top;
    if(css_items_height < 120){
      css_items_height = 50;
    } else {
      css_items_height = css_items_height - 70;
    }
    itemsDiv.css({height: css_items_height, "max-height": css_items_height });
  } catch(err){};
}

function ResizeEditor() {
  var newh, skineditor = jQuery(ed_id);
  /* Do some resizing to fit the editor inside browser window */
  winheight = jQuery(window).height();
  var minheight = winheight;
  try {
    minheight = itemsDiv.offset().top + itemsDiv.height() - skinTable.offset().top;
  } catch(err){
    return false;
  }
  var topH = 0;
  if($(ed_id).length) {
    topH = editor_top.outerHeight();
  }
  //var bottomH   = Math.max(30, editor_bottom.outerHeight());

  if(structure === "menu") {
    var menutbl = jQuery("div#menu-content table:first");
    newh = menutbl.outerHeight(true) + menutbl.offset().top;
    newh = newh + topH ;
    if(newh < minheight){ newh = minheight; }
    var cssOptions = { height: newh +"px", "maxHeight": newh +"px", "minHeight": newh +"px" };
    jQuery("div.content").css(cssOptions);
    jQuery("#iframe-content").css(cssOptions);
    skinTable.css(cssOptions);
    return;
  }

  if((!skins_options.fallback && !editor) || !skineditor.length) return;
  var err_on = (jQuery("#error_message").css("display")=="block");
  var newh = winheight - editor_top.offset().top;
  if(newh < minheight){ newh = minheight; }
  if(err_on) { newh = newh ; }

  var cssOptions = { height: newh +"px", "maxHeight": newh +"px", "minHeight": newh +"px" };
  jQuery("div.content").css(cssOptions);

  newh = newh - 20; /* accounting for styled "button" */
  cssOptions = { height: newh+"px", "maxHeight": newh+"px", "minHeight": newh+"px" };
  skinTable.css(cssOptions);
  jQuery("#iframe-content").css(cssOptions);

  newh = Math.round(newh - topH - 20);
  if(err_on) { newh = newh - 30; }
  cssOptions = { height: newh+"px", "maxHeight": newh+"px", "minHeight": newh+"px" };
  skineditor.css(cssOptions);
  skineditor.find("div.ace_content,div.ace_layer,div.ace_scroller,div.ace_sb,div.ace_content > div.ace_layer").css(cssOptions);

  newh -= 14;
  cssOptions = { height: newh+"px", "maxHeight": newh+"px", "minHeight": newh+"px" };
  skineditor.find("div.ace_gutter-layer").css(cssOptions);
  if(typeof editor !== "undefined" && editor !== null){
    editor.resize();
    var ace_scroller = skineditor.find("div.ace_scroller");
    ace_scroller.css({"width": (parseInt(skineditor.css("width"),10)-64)+"px"});
  }
} /* ResizeEditor */


function ApplyColorPicker(){
  if(typeof(jQuery.fn.jPicker) !== "undefined") {
    jQuery("div.jPicker").remove(); /* SD341: remove duplicate jPicker layer */
    jQuery("input.colorpicker").jPicker(
      { images: { clientPath: sdurl+"includes/css/images/jpicker/"},
        window: { position: { y: "bottom"} } },
      function(color, context) {
        var all = color.val("all"), data = all && "#" + all.hex || "transparent";
        if(skins_options.fallback) {
          insertAtCursor(data);
        } else {
          editor.insert(data);
          FocusEditor();
        }
      }).addClass("jPickered");
  }
}

function DeselectEntries(){
  jQuery("a.iframe-link, div.layout-link-container").removeClass("current-item");
}

function LoadLayouts(msg){
  showResponse(msg,true);
  var formdata = {
    "action":   "getlayouts",
    "designid": $("#skinform input[name=designid]").val(),
    "skinid":   $("#skinform input[name=skinid]").val(),
    "form_token": skins_options.skins_token
  };
  $("div#skin-layouts").load("skins.php", formdata,
    function(responseText, textStatus){
      hideIndicator();
      jQuery.unblockUI();
      FocusEditor();
    });
}

/* ************************************************************************** */
jQuery(document).ready(function() {
/* ************************************************************************** */
(function($){
  skinTable = $("table#skin-layout-tbl");
  itemsDiv  = $("div#skin-css");
  hotkeys   = $("div#hotkeys");
  legacy_skin = $("input#legacy_skin").val();
  $(document).delegate("a.iframe-link","click",function(){
    DeselectEntries();
    $(this).addClass("current-item");
  });
  $(document).delegate("a.layout-link","click",function(){
    DeselectEntries();
    $(this).parent().addClass("current-item");
  });

  /* Load contents and apply editor (except menu) for clicked entry */
  $(document).delegate("a[target=iframe-content]","click",function(event) {
    event.preventDefault();
    if(content_changed && !window.confirm(skins_options.lang_abandon_changes)) {
      return false;
    }
    showIndicator();
    jDialog.close();
    jQuery.unblockUI();
    content_changed = 0;
    jQuery("div#editor_bottom #ed_changed").css({ "visibility": "hidden" });
    $("div#jGrowler").jGrowl("close");
    var minheight = 600;
    if(typeof itemsDiv !== "undefined"){
      minheight = itemsDiv.offset().top + itemsDiv.height() - skinTable.offset().top;
    }

    /* Remove "old" editor area */
    $("#iframe-content").html(''); /* IMPORTANT! */
    editor = null;

    var url = "skin_structure_selection.php?"+$(this).attr("href");
    $("div#iframe-content").load(url +" #output", {
      "height": minheight, /* pass height */
      "fallback": skins_options.fallback,
      "form_token": skins_options.skins_token
    }, function(){
      showIndicator();
      $("div#wrapper").next("div").remove(); /* SD341: remove obsolete ace layer */
      $("a#hotkeys_help").attr("title", skins_options.lang_editor_hotkeys);
      skinTable = $("table#skin-layout-tbl");
      itemsDiv  = $("div#skin-css");
      structure = $("input#structure").val();
      if($(ed_id).length){
        skins_options.skins_token = $("#skinform input[name="+skins_options.skins_token_name+"]").val();
        editor_top = $("div#editor_top");
        editor_bottom = $("div#editor_bottom");
      }
      if(structure != "menu" && structure != "mobile_menu" ){
        var editmode = structure === 'css' ? 'css' : 'html';
        ApplyEditor(editmode);
      }
      ApplyColorPicker();
      hideIndicator();

      /* SD362: set browser title to include current element name */
      document.title = jQuery("div#content_element_name").text() + ' :: ' + sd_page_title;

      /* focus editor after pageload (if exists) */
      window.setTimeout("ResizeCSSlist(); ResizeEditor(); FocusEditor();", 200);
    });
    return false;
  });

  /* Delete selected or current layout */
  $(document).delegate("a.delete-layout-link, a#delete_layout","click",function(event) {
    event.preventDefault();
    if(!confirm(skins_options.lang_skins_delete_layout)) return;
    jDialog.close();
    var formdata = {
      "action":   "delete_layout",
      "designid": $("#skinform input[name=designid]").val(),
      "skinid":   $("#skinform input[name=skinid]").val(),
      "form_token": skins_options.skins_token
    };

    $this = $(this);
    if($this.hasClass("delete-layout-link")){
      var tmp = $this.parent("div:first").attr("id").split("layout_")[1];
      formdata.designid = tmp;
    }

    $.post("skins.php", formdata,
      function(data, status) {
        if(status==="success") {
          showResponse(data);
          formdata.action = "getlayouts";
          $("div#skin-layouts").load("skins.php", formdata,
            function(responseText, textStatus){
              DeselectEntries();
              /*
              var obj = $("div#layout_"+formdata.designid+" a.layout-link");
              if(obj.length) obj.trigger("click");
              */
              hideIndicator();
            });
        }
        else {
          hideIndicator();
          alert("Error: "+status);
        }
      }, "text");
    return false;
  });

  /* SD342: toggle disabled-column for a single CSS entry */
  $(document).delegate("a.css-status-link","click",function(event) {
    event.preventDefault();
    jDialog.close();
    var $this = $(this);
    var rel_val = parseInt($this.attr("rel"),10);
    var img = $(this).find("i:first");
    var formdata = {
      "action":     "css_switch",
      "cssid":      $this.attr("href"),
      "disabled":   rel_val,
      "designid":   $("#skinform input[name=designid]").val(),
      "skinid":     $("#skinform input[name=skinid]").val(),
      "form_token": skins_options.skins_token
    };

    $.post("skins.php", formdata,
      function(data, status) {
        if(status==="success") {
          showResponse(data);
          /* switch class now... */
          $this.attr("rel", 1-rel_val);
          if(rel_val==0) {
            $(img).attr("class", "ace-icon fa fa-eye-slash red bigger-110");
          } else {
            $(img).attr("class", "ace-icon fa fa-eye green bigger-110");
          }
        }
        else {
          hideIndicator();
          alert("Error: "+status);
        }
      }, "text");
    return false;
  });

  /* Create a new layout, reload menu and load contents into editor */
  if(!skins_options.fallback) {
    //SD362: different editor modes
    $(document).delegate("select#mode_selector","change",function(event){
      event.preventDefault();
      jDialog.close();
      jQuery.unblockUI();
      if(!editor) return false;
      var id = $(this).val();
      var new_mode = false;
      if(id === "css") {
        if(CSSMode===false) {
          CSSMode = window.require("ace/mode/css").Mode;
          CSSMode = new CSSMode();
        }
        new_mode = CSSMode;
      } else
      if(id === "php") {
        if(PHPMode===false) {
          PHPMode = window.require("ace/mode/php").Mode;
          PHPMode = new PHPMode();
        }
        new_mode = PHPMode;
      } else
      if(id === "js") {
        if(JSMode===false) {
          JSMode = window.require("ace/mode/javascript").Mode;
          JSMode = new JSMode();
        }
        new_mode = JSMode;
      }
      else { // default to HTML mode
        if(HTMLMode===false) {
          HTMLMode = window.require("ace/mode/html").Mode;
          HTMLMode = new HTMLMode();
        }
        new_mode = HTMLMode;
      }
      editor.getSession().setMode(new_mode);
      FocusEditor();
    });

    $(document).delegate("a#hotkeys_help","click",function(e) {
      e.preventDefault();
      jDialog.close();
      $(this).jDialog({
        align   : "left",
        content : hotkeys.html(),
        close_on_body_click : true,
        idName  : "hotkeys_popup",
        title   : skins_options.lang_editor_hotkeys,
        width   : 300
      });
      return false;
    });

    $(document).bind("keydown", "esc", function(){ jDialog.close(); });

    $("a#insertLayout").click(function(e) {
      e.preventDefault();
      if(!confirm(skins_options.lang_skins_confirm_new_layout)) return false;
      showIndicator();

      var formdata = {
        "action":   "insert_layout",
        "designid": $("#skinform input[name=designid]").val(),
        "skinid":   $("#skinform input[name=skinid]").val(),
        "layout_number": $("#layout_count").val(),
        "form_token": skins_options.skins_token
      };

      $.post("skins.php", formdata,
        function(data, status) {
          /* Fetch ID of the new layout: */
          var id = parseInt(data,10);
          if(status==="success" && id > 0) {
            formdata.action = "getlayouts";
            formdata.designid = id;
            $("div#skin-layouts").load("skins.php", formdata,
              function(){
                DeselectEntries();
                var obj = $("div#layout_"+id+" a.layout-link");
                if(obj.length) obj.trigger("click");
                hideIndicator();
              });
          }
          else {
            hideIndicator();
            alert("Error: "+status);
          }
          ResizeEditor();
        }, "text");
      return false;
    });
  }
  else {
    /* SD370: Bind hotkey to save template */
    $(document).unbind("keydown").bind("keydown", function(e) {
      shiftKey = e.shiftKey;
      altKey = e.altKey;
      ctrlKey = (!(e.altKey && e.ctrlKey)) ? (e.ctrlKey || e.metaKey) : false;
      if (e.type === "keydown" && ctrlKey === true && String.fromCharCode(e.keyCode) === "S") {
        e.preventDefault();
        $("form#skinform").submit();
        return false;
      }
    });
  }

  /* Insert variable code into editor */
  $(document).delegate("#variable_selection","change",function(e) {
    e.preventDefault();
    jDialog.close();
    var value = $(this).val();
    if(value == "[PLUGIN]") {
      var data = "\n<plugin>\n  <plugin_name>[PLUGIN_NAME]</plugin_name><br />\n  [PLUGIN]\n</plugin>";
    }
    else {
      var data = value;
    }
    if(skins_options.fallback) {
      insertAtCursor(data);
    } else {
      editor.insert(data);
    }
    $("#variable_selection").val(0);
    FocusEditor();
  });

  $(document).delegate("#ace_theme","change",function(e) {
    e.preventDefault();
    var value = $(this).val();
    toggleHighlightingTheme(value);
  });

  $(document).delegate("#skinform","submit",function(e) {
    e.preventDefault();
    showIndicator();
    jDialog.close();
    SaveContent();
    var opt = {
      beforeSubmit: showIndicatorBlock,
      success: LoadLayouts,
      timeout: 10000
    };
    $(this).ajaxSubmit(opt);
    hideIndicator();
    FocusEditor();
    return false;
  });

  /* Prevent changing page with unsaved (ACE) editor changes */
  if(!skins_options.fallback) {
    window.onbeforeunload = function() {
      if(content_changed){
        return skins_options.lang_abandon_changes;
      }
    }
  }

  /* Window resizing triggers resizing of inner elements */
  $(window).resize(function(e) {
    try {
      window.setTimeout("ResizeCSSlist(); ResizeEditor(); FocusEditor();", 150);
    } catch(err){};
  });

  /* Perform delayed initial editor loading (200ms) */
  if(legacy_skin==1) { /* SD370: for SD2 skins the error page is now default */
    window.setTimeout(function() { $("#error-page").trigger("click"); }, 200);
  } else {
    current_item = $("div#skin-css a.current-item");
    if(current_item.length) {
      window.setTimeout(function() { $(current_item).trigger("click"); }, 200);
    } else {
      window.setTimeout(function() { $("#header-link").trigger("click"); }, 200);
    }
  };

})(jQuery);
});
