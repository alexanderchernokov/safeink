<?php
session_start();
/**
 * Created by PhpStorm.
 * User: alexander.c
 * Date: 08/11/2016
 * Time: 13:48
 */
$payments_val = array(

    'create_new'=>'Create new payment process',
    'save'=>'Save',
    'select_process_type'=>'Please select a Process type',
    'outgoing_payments_placeholder'=>'Outgoing payment',
    'income_payments_placeholder'=>'Incomming payment',
    'process_name'=>'Process Name',
    'process_description'=>'Process Description',
);
var_dump($userinfo);
echo '
<h2>'.$payments_val['create_new'].'</h2>
<form class="form-contact-3 form-contact-finance" name="payment" id="payment" method="post" action="send_form_email.php">
    <div class="form-group col-sm-12  col-md-12">
        <select name="type" id="type" class="form-control" required>
            <option value="">'.$payments_val['select_process_type'].'</option>
            <option value="1">'.$payments_val['outgoing_payments_placeholder'].'</option>
            <option value="2">'.$payments_val['income_payments_placeholder'].'</option>
        </select>
    </div>
    <div class="form-group col-sm-12  col-md-12">
        <input type="text" class="form-control" name="payment_title" id="payment_title" placeholder="'.$payments_val['process_name'].'" value="" required>
    </div>
    <div class="form-group col-sm-12 col-md-12">
        <textarea name="payment_description" id="payment_description"  class="form-control" placeholder="'.$payments_val['process_description'].'"></textarea>
    </div>

    <input type="hidden" name="act" value="new">
    <a href="#" class="ot-btn large-btn btn-rounded  btn-main-color btn-submit payment">'.$payments_val['save'].'</a>
</form>
';
?>
<script type="text/javascript">
    $(document).on("click",".payment",function(e){
        e.preventDefault
        //var myForm = $('#payment')
        $(":input[required]").each(function () {
            var myForm = $('#payment');
            if (myForm[0].checkValidity())
            {
                var form_data = $("#payment").serialize();
                alert(form_data);
                $.ajax({
                    type: 'POST',
                    url: 'includes/data.php',
                    enctype: 'multipart/form-data',
                    data: form_data,
                    success:function(msg) {
                        alert(msg);
                        return false;
                    },
                    error:function(){
                        alert('Whoops! This didn\'t work. Please contact us.');
                    }
                });
                return false;
            }
            else{
                alert('error_val');
            }
        });
    })

</script>
