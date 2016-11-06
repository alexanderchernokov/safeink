 var editor = false, tmpl_options, UndoManager, $hotkeys, hl_theme = "textmate";
var CSSMode = false, HTMLMode = false, PHPMode = false, JSMode = false;

function insertAtCursor(myValue) {
  var myField = document.getElementById("skincontent");
  /* IE support */
  if (document.selection) {
    myField.focus();
    var sel = document.selection.createRange();
    sel.text = myValue;
  }
  /* MOZILLA/NETSCAPE support */
  else
  if(myField.selectionStart || myField.selectionStart === "0") {
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
  myField.focus();
}

function FocusEditor() {
  if(!tmpl_options.fallback && editor !== null && typeof editor !== "undefined") {
    editor.focus();
  }
}

function showIndicator() { jQuery("#indicator").css("display", "inline"); }
function hideIndicator() { jQuery("#indicator").hide(); }

function SaveContent() {
  if(!tmpl_options.fallback)
  if(editor !== null && typeof editor !== "undefined") {
    var content = editor.getSession().getDocument().getValue();
    jQuery("#skincontent").text(content);
  }
}

function showResponse(msg) {
  /* Reset undo buffers and changed flag */
  jQuery("form#tmplform input#content_changed").val("0");
  if(!tmpl_options.fallback) {
    editor.getSession().getUndoManager().reset();
  }
  jQuery("form#tmplform span#ed_changed").css({ "visibility":"hidden" });

  if(!msg || msg === "" || msg === "1") {
    msg = tmpl_options.lang_template_updated;
  }
  var n = noty({
					text: msg,
					layout: 'top',
					type: 'success',	
					timeout: 5000,					
					});
  hideIndicator();

  /* Load different template? */
  var tplid = jQuery("select#jump_template").val();
  var curid = jQuery("input#template_id").val();
  if(tplid && curid){
    if(tplid != curid){
      jQuery("select#jump_template").trigger("change");
    }
  }
}

function toggleHighlightingTheme(theme) {
  if(editor !== null && typeof editor !== "undefined") {
    editor.setTheme(theme);
    jQuery.cookie(tmpl_options.cookie_name, theme);
    jQuery("#ace_theme").val(theme);
  }
}

function ResizeEditor(event) {
  var newh, skineditor = jQuery(ed_id);

  if((!tmpl_options.fallback && !editor) || !skineditor.length) return;

  /* Do some resizing to fit the editor inside browser window */
  var winheight = jQuery(window).height();
  var minheight = 330;
  var topH      = editor_top.outerHeight();

  var newh = winheight - editor_top.offset().top - 120;
  if(newh < minheight){ newh = minheight; }

  newh = newh; /* accounting for styled "button" */
  cssOptions = { height: newh+"px", "maxHeight": newh+"px", "minHeight": newh+"px" };
  jQuery("#iframe-content").css(cssOptions);

  newh = Math.round(newh);
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
}

function MarkChanged(){
  hasUndo = editor.getSession().getUndoManager().hasUndo();
  jQuery("form#tmplform input#content_changed").val(hasUndo?1:0);
  if(hasUndo){
    jQuery("form#tmplform span#ed_changed").css({ "visibility":"visible" });
  } else {
    jQuery("form#tmplform span#ed_changed").css({ "visibility":"hidden" });
  }
}

function ApplyEditor(structure) {
  if(tmpl_options.fallback) {
    jQuery("div#skineditor").hide();
    jQuery("textarea#skincontent").css("fontSize",15).show();
    return;
  }

  if(typeof ace !== "undefined") {
    var config = ace.require("ace/config");

    editor = ace.edit("skineditor");
    editor.setOptions({
      enableBasicAutocompletion: false,
      enableMultiselect: false,
      enableSnippets: false,
      fontSize: "15px",
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
      useWrapMode: false
    });

    //SD370: try to map CTRL+S to trigger saving
    // Tested with FF24, Chrome 30, IE10
    editor.commands.addCommands([{
      name: "saveDoc",
      bindKey: {win: "Ctrl-S", mac: "Command-s"},
      exec: function(editor) { jQuery("#tmplform").submit(); }
    }]);
    editor.commands.addCommands([{
      name: "insertSpace",
      bindKey: {win: "Space|Shift-Space", mac: "Space"},
      exec: function(editor) { editor.insert(" "); }
    }]);
    //SD370: display an overlay with all current hotkeys
    var KeyBindings = ace.require("ace/ext/keybinding_menu").Keybinding_Menu;
    editor.commands.addCommand({
      name: "showKeyboardShortcuts",
      bindKey: {win: "Ctrl-Alt-h", mac: "Command-Alt-h"},
      exec: function(editor) {
        config.loadModule("ace/ext/keybinding_menu", function(module) {
          module.init(editor);
          editor.showKeyboardShortcuts() }) }
    });
    // Remap some keys:
    editor.commands.removeCommand("copylinesdown");
    editor.commands.addCommand({
      name: "copylinesdown",
      bindKey: {win: "Ctrl-d", mac: "Command-d"},
      exec: function(editor) { editor.copyLinesDown(); }
    });
    editor.commands.removeCommand("redo");
    editor.commands.addCommand({
      name: "redo",
      bindKey: {win: "Ctrl-Shift-Z", mac: "Command-Shift-Z"},
      exec: function(editor) { editor.redo(); }
    });
    editor.commands.removeCommand("removeline");
    editor.commands.addCommand({
      name: "removeline",
      bindKey: {win: "Ctrl-y", mac: "Command-y"},
      exec: function(editor) { editor.removeLines(); }
    });
    editor.commands.removeCommand("gotoline");
    editor.commands.addCommand({
      name: "gotoline",
      bindKey: {win: "Ctrl-Alt-L", mac: "Command-L"},
      exec: function(editor) {
        var line = parseInt(prompt("Enter line number:"), 10);
        if (!isNaN(line)) { editor.gotoLine(line); } }
    });
    editor.on("change", function(){  MarkChanged(); });
    editor.on("blur", function(){ SaveContent(); });

    hl_theme = jQuery.cookie(tmpl_options.cookie_name);
    if((typeof hl_theme === "undefined") || !hl_theme || (hl_theme === "null") || (hl_theme === "")) {
      var hl_theme = "ace/theme/textmate";
    }
    //SD370: finally, set editor theme
    toggleHighlightingTheme(hl_theme);
    jQuery("#ace_theme").val(hl_theme);

    //SD370: set mode (html or css during pageload)
    var key_mode = "html";
    if(structure === "css") { key_mode = "css"; }
    jQuery("select#mode_selector").val(key_mode);
    jQuery("select#mode_selector").trigger("change");

    //fix:
    jQuery("div.ace_sb").css("height", "100%");
  }
}

if (typeof(jQuery) !== "undefined") {
  jQuery(document).ready(function() {
  (function($){
	  
	  
    /* SD370: prompt before restoring template */
    $("a#restoretpl").click(function(event){
      if(!confirm(tmpl_options.lang_restore_prompt)) {
        event.preventDefault();
        return false;
      }
    });

    if(!tmpl_options.fallback)
    {
      /* SD370: different editor modes */
      $(document).delegate("select#mode_selector","change",function(event){
        event.preventDefault();
        jDialog.close();
        $.unblockUI();
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

      /* If ACE is used, process hotkey help window */
      $hotkeys = $("div#hotkeys");
      $(document).delegate("a#hotkeys_help","click",function(e){
        e.preventDefault();
        jDialog.close();
        $(this).jDialog({
          align   : "left",
          content : $hotkeys.html(),
          close_on_body_click : true,
          idName  : "hotkeys_popup",
          title   : tmpl_options.lang_editor_hotkeys,
          width   : 300
        });
      });

      $("a#hotkeys_help").attr("title", tmpl_options.lang_editor_hotkeys);

      $(document).delegate("#ace_theme","change",function(event) {
        event.preventDefault();
        var value = $(this).val();
        toggleHighlightingTheme(value);
        FocusEditor();
      });

      $(document).bind("keydown", "esc", function(){ jDialog.close(); });
    }
    else {
      /* SD370: Bind hotkey to save template */
      $("textarea#skincontent").unbind("keydown").bind("keydown", function(e) {
        shiftKey = e.shiftKey;
        altKey = e.altKey;
        ctrlKey = (!(e.altKey && e.ctrlKey)) ? (e.ctrlKey || e.metaKey) : false;
        if (e.type === "keydown" && ctrlKey === true && String.fromCharCode(e.keyCode) === "S") {
          e.preventDefault();
          $("form#tmplform").submit();
          return false;
        }
      });
      $("textarea#skincontent").focus();
    }

    /* Insert variable code into editor */
    $(document).delegate("#variable_selection","change",function(event) {
      event.preventDefault();
      jDialog.close();
      var value = $(this).val();
      if(value == "[PLUGIN]") {
        var data = "\n<plugin>\n  <plugin_name>[PLUGIN_NAME]</plugin_name><br />\n  [PLUGIN]\n</plugin>";
      }
      else {
        var data = value;
      }
      if(tmpl_options.fallback) {
        insertAtCursor(data);
      } else {
        editor.insert(data);
      }
      $("#variable_selection").val(0);
      FocusEditor();
    });

    /* Load different template upon select change */
    $("select#jump_template").change(function(e) {
      var tplid = $(this).val();
      var curid = $("input#template_id").val();
      if(tplid != curid){
        var hasChanged = $("form#tmplform input#content_changed").val();
        if(hasChanged === "1"){
          e.preventDefault();
          if(!window.confirm(tmpl_options.lang_abandon_changes)) {
            $(this).val(curid);
            return false;
          }
          $("#tmplform").trigger("submit");
          return false;
        }
        showIndicator();
        var link = "templates.php?action=display_template&template_id="+tplid;
        window.location = link;
      }
    });

    window.onbeforeunload = function() {
      var hasChanged = $("form#tmplform input#content_changed").val();
      if(hasChanged === "1"){
        return tmpl_options.lang_abandon_changes;
      }
    }

    /* Init editor */
    editor_top = $("div#editor_top");
    ApplyEditor("html");

    /* Resize editor to fit in window, also upon window resizing */
    $(window).resize(function(event) {
      try { ResizeEditor(event); } catch(err){};
    })

    var ajax_options = {
      beforeSubmit: showIndicator,
      success: showResponse,
      timeout: 5000
    };

    /* Ajax-submit template */
    $(document).delegate("#tmplform","submit",function(event) {
      event.preventDefault();
      jDialog.close();
      SaveContent();
      var opt = {
        beforeSubmit: showIndicator,
        timeout: 5000,
        success: showResponse
      };
      $(this).ajaxSubmit(opt);
      $(ed_id).focus();
      return false;
    });

    /* Show/hide info pane */
    $(document).delegate("a#descr-hide","click",function(e) {
      e.preventDefault();
      $("#descr-hide").css("display","none");
      $("#descr-show,div#description-container").css("display","inline");
      $("#description-container").hide();
      try { ResizeEditor(); } catch(err){};
      FocusEditor();
    });
    $(document).delegate("a#descr-show","click",function(e) {
      e.preventDefault();
      $("#descr-show").css("display","none");
      $("#descr-hide,div#description-container").css("display","inline");
      $("#description-container").show();
      try { ResizeEditor(); } catch(err){};
      FocusEditor();
    });

    /* Focus editor after pageload */
    window.setTimeout("FocusEditor(); try { ResizeEditor(); } catch(err){};", 200);
  }(jQuery));
  })
}