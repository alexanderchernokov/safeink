/**
 * Created by alexander.c on 10/10/2016.
 */
$(document).ready(function () {
    "use strict";

    $('.i-checks').iCheck({
        checkboxClass: 'icheckbox_square-green',
        radioClass: 'iradio_square-green'
    });
    $.validator.addMethod("regx", function(value, element, regexpr) {
        return regexpr.test(value);
    });
    $("#form").validate({
        rules: {
            password: {
                required: true ,
                regx: /^(?=.*[A-Za-z0-9])[A-Za-z0-9 _]*$/,
                minlength: 6,
                maxlength:12
            }
        },
        messages:{
            password:{
                regx:'Only lettes and numbers allowed'
            }
        },
        submitHandler: function(form) {

            var form_id = '#form';
            var form_data = $(form_id).serialize();

            $.ajax({
                type: 'POST',
                url: '/includes/db/login_register_forgot.php',
                enctype: 'multipart/form-data',
                data: form_data,

                success:function(msg) {
                    alert(msg);
                    var m = msg.split('::');
                    if(m[0] == 'choose_account'){
                        $("#form").fadeOut("fast");
                        $(".inqbox-content").load('includes/general/content/parts/account.php');
                        $("#user").val(m[1]);
                    }
                    else{
                        $("#msg").html(msg);
                    }
                    return false;
                },
                error:function(){
                    alert('Whoops! This didn\'t work. Please contact us.');
                }
            });
            return false;
        }
    });
    $(document).on("click",".account_type",function(){
        var account = $(this).attr("rel");
        if(account == 1){
            $("#type").val("1");
            $("#user_id").val($("#user").val());
            $("#profile_form").fadeIn("fast");
            $("#company_name").parent().hide();
            $("#company_name").val("none");
        }
        if(account == 2){
            $("#type").val("2");
            $("#user_id").val($("#user").val());
            $("#company_name").parent().show();
            $("#company_name").val("");
            $("#profile_form").fadeIn("fast");
        }
    });
    $(".type").change(function(){
        if($(this).val() == 2){
            $("#comp").show();
            $("#company").val("");
        }
        else{
            $("#comp").hide();
            $("#company").val("none");
        }
    });
});
