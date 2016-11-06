var $grouptarget=false, $groupselector=false, $statustarget=false, $statusselector=false,
    $loader=false, $loader_inner=false, $users_container=false,
    checkcount=0, checkgroup=false, posX, posY, userid;

function ValidateButtons(revalidate){
  if(revalidate && (revalidate===true)){
    checkgroup = $("form#userlist input.usercheckbox");
  }
  checkcount = checkgroup.filter("input:checked").length;
  if(checkcount==0){
    $("div#moveoptions, #updateoptions").fadeTo("fast", 0.5);
    $("select#usergroup_move").attr("disabled","disabled");
  } else {
    $("div#moveoptions, #updateoptions").fadeTo("fast", 1);
    $("select#usergroup_move").removeAttr("disabled");
  }
  var form = $("form#userlist");
  form.find("a.ugtitle").each(function(){
    $(this).attr("title", users_lang.users_link_edit_usergroup);
  });
}

function ReloadUsers(obj){
  checkcount=0;
  checkgroup=false;
  jDialog.close();
  $content = $("div#users_container");
  $loader.css({
    display: "block", margin: 0, padding: 0,
    width:   ($content.width() + 30)+"px",
    height:  ($content.height())+"px",
    top:     posY+"px", left: posX+"px"
  });
  $loader_inner.css({ top: "25%", left: "45%" });
  var formdata = $("form#searchusers").serialize();
  $("div#users_container").load("users.php?action=getuserlist", formdata, function(){
    var form = $("form#searchusers");
    var token = $("div#users_container").find("input[name=form_token]").val(); //SD341
    users_lang.users_token = "&"+users_lang.users_token_name+"="+token;
    form.find("input[name=clearsearch]").val("0").end();
    form.find("input[name=form_token]").val(token);
    ValidateButtons(true);
    $loader.hide();
  });
}

function UsersLogConsole(obj){
  if (window.console && window.console.log) {
    window.console.log(obj);
  }
}

if(typeof(jQuery) !== "undefined"){
jQuery(document).ready(function(){
  (function($){
  if($("div#users_container").length){
    var offs = $("div#users_container").offset();
    posY = offs.top - 10;
    posX = 0;//offs.left;
  }
  userid = 0;
  $loader         = $("div#loader");
  $loader_inner   = $("div#loader div:first");
  $groupselector  = $("div#groupselector");
  $statusselector = $("div#statusselector");
  $grouptarget    = false;
  $statustarget   = false;

  // Add form token to all links with "token" class
  $(document).delegate("form#userlist a.token","click",function(){
    $(this).removeClass("token");
    this.href += users_lang.users_token;
    return true;
  });

  // Instant user status change links
  $(document).delegate("#userlist a.user-status-link","click",function(e){
    e.preventDefault();
    jDialog.close();
    userid = parseInt($(this).attr("rel"),10);
    var elem, checked;

    // Fetch user status values
    $statustarget = $(this).parent();
    var status_a = $statustarget.find("input[name=usr_a]").val();
    var status_b = $statustarget.find("input[name=usr_b]").val();

    $(this).jDialog({
      align   : "right",
      content : $statusselector.clone().html(),
      close_on_body_click : true,
      idName  : "status_popup",
      title   : "",
      width   : 350
    });

    // Assign values to popup form
    $("#status_popup input").removeAttr("checked");
    if(status_a == 1) {
      $("#status_popup input.changestatusactivated").attr("checked", "checked");
    }
    if(status_b == 1) {
      $("#status_popup input.changestatusbanned").attr("checked", "checked");
    }

    $("#status_popup").find("a.pwdresetlink").click(function(e){
      if(userid && (userid > 0)){
        jDialog.close();
        this.href += users_lang.users_token+"&email_userid="+userid;
        return true;
      }
      return false;
    });
    return false;
  });
  
   

  $(document).delegate("a.sendactivationlink,a.sendwelcomemessage","click",function(e){
    e.preventDefault();
    if(userid && (userid > 0)){
      if($(this).hasClass("sendactivationlink") && !confirm(users_lang.users_confirm_activationlink)){
        return false;
      }
      this.href += users_lang.users_token+"&email_userid="+userid;
      $("#status_popup .dialog_body").load(this.href, {}, function(){
        var btn = $("#status_popup .dialog_body").find("a");
        if(btn.length==1){
          btn.removeAttr("onclick").click(function(e){
            e.preventDefault();
            jDialog.close();
          });
        }
      });
    }
    return false;
  });

  $(document).delegate("a.statuschangelink","click",(function(e){
    e.preventDefault();
    var formdata = $("div#status_popup form#userstatuschange").serialize();
    var uri = "action=setuserstatus&userid="+userid+users_lang.users_token;
    if(formdata.length > 0) uri += "&"+formdata;
    jDialog.close();
    $($statustarget).load("users.php?"+uri,
      function(response, status, xhr){
        if(response.substr(0,5)==="ERROR") alert(response);
      });
    return false;
  }));

  $(document).delegate("a.ug_link","click",function(e){
    e.preventDefault();
    jDialog.close();
    userid = parseInt($(this).attr("rel"),10);
    $grouptarget = $(this).parent("td");
    $(this).jDialog({
      align   : "left",
      content : $groupselector.html(),
      close_on_body_click : true,
      idName  : "groups_popup",
      title   : ''//users_lang.users_change_usergroup_title
    });
    return false;
  });

  $(document).delegate("a.grouplink","click",(function(e){
    e.preventDefault();
    var href = this.href + "&action=setusergroup&userid=" + userid + users_lang.users_token;
    jDialog.close();
    if((userid > 0) && ($grouptarget!==false)){
      $grouptarget.load(href, {}, function(response, status, xhr){
        if(response.substr(0,5)==="ERROR") alert(response);
      });
    }
    return false;
  }));

  $(document).delegate("div#filterarea a","click",function(e){
    e.preventDefault();
    $this = $(this);
    var form = $("form#searchusers");
    if($this.hasClass("letter-link")) {
      form.find("select#status").val(0);
      form.find("input[name=namestart]").val($(this).text());
      form.find("input[name=page]").val("---");
    }
    if($this.hasClass("status-link")) {
      var href = this.href;
      var tmp = href.split("status=")[1];
      form.find("select#status").val(tmp);
      if(tmp == "---"){
        form.find("input[name=namestart]").val("---");
      }
      form.find("input[name=usergroupid]").val("---");
      form.find("input[name=page]").val("---");
    }
    ReloadUsers(this);
    return false;
  });

  $(document).delegate("div#pagesarea a","click",function(e){
    e.preventDefault();
    var href = this.href;
    var tmp = href.split("page=")[1];
    $("input[name=page]").val(tmp);
    ReloadUsers(this);
    return false;
  });
  $(document).delegate("form#searchusers","submit",function(e) {
    e.preventDefault();
    ReloadUsers(false);
    return false;
  });

  $("form#searchusers select").on("change",function(e) {
    e.preventDefault();
    $("form#searchusers input[name=page]").val("---");
    ReloadUsers(false);
    return false;
  });

  $("a#users-submit-search").click(function(e) {
    e.preventDefault();
    ReloadUsers(false);
    return false;
  });

  $("a#users-clear-search").click(function(e) {
    e.preventDefault();
    var form = $("form#searchusers");
    form.find("input").val("");
    form.find("select").prop("selectedIndex", 0); /* no attr anymore! */
    form.find("select#sortorder").prop("selectedIndex", 1);
    form.find("input[name=clearsearch]").val("1");
    form.find("input[name=page]").val("---");
    ReloadUsers(false);
    return false;
  });

  $(document).delegate("form#userlist input.usercheckbox","change",function(e){
    ValidateButtons(false);
  });

  $(document).delegate("form#userlist a#checkall","click",function(e){
    e.preventDefault();
    var ischecked = 1 - parseInt($(this).attr("rel"),10);
    if(ischecked==1) {
      $("form#userlist input.usercheckbox").attr("checked","checked");
      $("form#userlist tr").addClass("danger");
    } else {
      $("form#userlist input.usercheckbox").removeAttr("checked");
      $("form#userlist tr").removeClass("danger");
    }
    $(this).attr("rel",ischecked);
    ValidateButtons(false);
    return false;
  });

  $(document).delegate("a#updateusers, a#moveusers","click",function(e) {
    e.preventDefault();
    ValidateButtons(true);
    if(checkcount==0) return false;
    var form = $("form#userlist");
    form.find("input[name=action]").val($(this).attr("id"));
    form.submit();
  });

  /* SD343 */
  $(document).delegate("input[type=checkbox].usercheckbox","change",function(){
    var tr = $(this).parents("tr");
    $(tr).toggleClass("danger");
  });

  ValidateButtons(true);

  $(document).bind("keydown", "esc", function(){ jDialog.close(); });

  }(jQuery));
});
}
