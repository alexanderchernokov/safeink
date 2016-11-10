<?php
/**
 * Created by PhpStorm.
 * User: Alex.Chernokov
 * Date: 24/10/2016
 * Time: 10:51
 */
if(!defined('IN_PRGM')) return false;
include_once ('includes/custom_languages/'.$user_language.'/payments.php');
if(!class_exists('SD_Payment'))
{
    class SD_Payment
    {
        public function __construct()
        {
            global $userinfo,$payments_val;
            if($this->pluginid = GetPluginIDbyFolder(sd_GetCurrentFolder(__FILE__)))
            {
                //$this->language = GetLanguage($this->pluginid);
                //$this->settings = GetPluginSettings($this->pluginid);
                $this->username = !empty($userinfo['username']);
                $this->payments = 'sd_payments';
                $this->module_header = '
                <div class="row">
                    <div class="col-md-12" style="text-align: right;">
                        <a href="#" class="popup_link ot-btn large-btn btn-green-color icon-btn-left" data-popup-open="popup-1" rel="create_payment"><i class="fa fa-plus-circle" aria-hidden="true"></i>' .$payments_val['create_new'].'</a>
                    </div>
                </div>
                ';
            }
        }

        function DisplayPayments()
        {
            global $DB, $userinfo,$payments_val;
            echo $this->module_header;
            echo '<div class="finance-tabs-style-2 clearfix">
                      <ul class="nav nav-tabs">
                        <li class="active"><a data-toggle="tab" href="#OutgoingPayments" aria-expanded="false"><i class="fa fa-upload"></i> '.$payments_val['outgoing_payments'].'</a></li>
                        <li class=""><a data-toggle="tab" href="#IncomePayments" aria-expanded="false"><i class="fa fa-download" aria-hidden="true"></i> '.$payments_val['income_payments'].'</a></li>
                        <li class=""><a data-toggle="tab" href="#PaymentsHistory" aria-expanded="false"><i class="fa fa-history" aria-hidden="true"></i> '.$payments_val['payments_history'].'</a></li>
                      </ul>

                        <div class="tab-content">
                          <div id="OutgoingPayments" class="tab-pane fade active in">
                                <h4> '.$payments_val['outgoing_payments'].'</h4>
                                
                                <div class="accordion-style-light no-round">
                                    <div class="accordion-warp">
                                            <div class="clearfix"></div>
                                          <div class="panel-group" id="accordion1">';
                                            $payments = $DB->query("SELECT * FROM `" . $this->payments. "` WHERE `bayer_userid` = %d",$userinfo['userid']) or die(mysql_error());
                                            $count = $DB->get_num_rows();
                                            if($count > 0){
                                                while($payment = $DB->fetch_array($payments)){
                                                    echo '<div class="panel panel-default">
                                                              <div class="panel-heading">
                                                                  <h4 class="panel-title">
                                                                      <a data-toggle="collapse" data-parent="#accordion1" href="#out_'.$payment['paymentid'].'" class="collapsed">'.$payment['payment_title'].'</a>
                                                                  </h4>
                                                              </div>
                                                              <div id="out_'.$payment['paymentid'].'" class="panel-collapse collapse">
                                                                      <div class="panel-body">
                                                                        <div class="accordion-content">
                                                                          <div class="col-md-10">
                                                                            <p>'.$payment['payment_description'].'</p>
                                                                            <p class="timeline">
                                                                                <h5>Process Status</h5>
                                                                                <div class="steps">
                                                                                    <div class="step finish"><i class="fa fa-plus-circle" aria-hidden="true"></i></div>
                                                                                    <div class="step"><i class="fa fa-user-plus" aria-hidden="true"></i></div>
                                                                                    <div class="step"><i class="fa fa-upload" aria-hidden="true"></i></div>
                                                                                    <div class="step"><i class="fa fa-exclamation-circle" aria-hidden="true"></i></div>
                                                                                </div>
                                                                                <div class="chart-2 chart-home-2" id="chart-2">
                                                                                    <div class="chart-h-item">
                                                                                        <div class="progress progress-h">
                                                                                            <div class="progress-bar progress-bar-success" role="progressbar" data-transitiongoal="25%"></div>
                                                                                        </div>
                                                                                        <span  class="percent-h update-h"></span>
                                                                                    </div>
                                                                                </div>
                                                                            </p>
                                                                          </div>
                                                                          <div class="col-md-2 to-do-list">
                                                                                <h3>'.$payments_val['to_do'].'</h3>';
                                                                                if($payment['recipient_userid'] == ''){
                                                                                    echo '<a href="#" class="to-do"><i class="fa fa-user-plus" aria-hidden="true"></i><br>'.$payments_val['invite_recipient'].'</a>';
                                                                                }
                                                                                if($payment['status'] == 0){
                                                                                    echo '<a href="#" class="to-do"><i class="fa fa-check-square-o" aria-hidden="true"></i><br>'.$payments_val['activate'].'</a>';
                                                                                }
                                                                    echo '</div>
                                                                          
                                                                        </div>
                                                                      </div>
                                                              </div>
                                                        </div>';
                                                }
                                            }
                                            else{
                                                echo '<div class="col-md-12 payment">'.$payments_val['no_payments_found'].'</div>';
                                            }
                                          echo '</div> <!-- End panel group -->
                                    </div>
                                </div>
                                
                          </div>
                          <div id="IncomePayments" class="tab-pane fade">
                                
                                
                                <h4> '.$payments_val['income_payments'].'</h4>
                                <div class="accordion-style-light no-round">
                                    <div class="accordion-warp">
                                      <div class="clearfix"></div>
                                      <div class="panel-group" id="accordion2">';
                                        $payments = $DB->query("SELECT * FROM `" . $this->payments. "` WHERE `recipient_userid` = %d",$userinfo['userid']) or die(mysql_error());
                                        $count = $DB->get_num_rows();
                                        if($count > 0){
                                            while($payment = $DB->fetch_array($payments)){
                                                echo '<div class="panel panel-default">
                                                  <div class="panel-heading">
                                                      <h4 class="panel-title">
                                                          <a data-toggle="collapse" data-parent="#accordion2" href="#out_'.$payment['paymentid'].'" class="collapsed">'.$payment['payment_title'].'</a>
                                                      </h4>
                                                  </div>
                                                  <div id="out_'.$payment['paymentid'].'" class="panel-collapse collapse">
                                                      <div class="panel-body">
                                                        <div class="accordion-content">
                                                          <p>'.$payment['payment_description'].'</p>
                                                        </div>
                                                      </div>
                                                  </div>
                                              </div>';
                                            }
                                        }
                                        else{
                                            echo '<div class="col-md-12 payment">'.$payments_val['no_payments_found'].'</div>';
                                        }
                                      echo '</div> <!-- End panel group -->
                                    </div>
                                </div>
                          </div>
                          <div id="PaymentsHistory" class="tab-pane fade">
                                <h4> '.$payments_val['payments_history'].'</h4>
                                <div class="accordion-style-light no-round">
                                    <div class="accordion-warp">
                                      <div class="clearfix"></div>
                                      <div class="panel-group" id="accordion3">';
                                            $payments = $DB->query("SELECT * FROM `" . $this->payments. "` WHERE `status` = 9 AND (`recipient_userid` = %d OR `bayer_userid` = %d)",$userinfo['userid'],$userinfo['userid']) or die(mysql_error());
                                            $count = $DB->get_num_rows();
                                            if($count > 0){
                                                while($payment = $DB->fetch_array($payments)){
                                                    echo '<div class="panel panel-default">
                                                      <div class="panel-heading">
                                                          <h4 class="panel-title">
                                                              <a data-toggle="collapse" data-parent="#accordion3" href="#out_'.$payment['paymentid'].'" class="collapsed">'.$payment['payment_title'].'</a>
                                                          </h4>
                                                      </div>
                                                      <div id="out_'.$payment['paymentid'].'" class="panel-collapse collapse">
                                                          <div class="panel-body">
                                                            <div class="accordion-content">
                                                              <p>'.$payment['payment_description'].'</p>
                                                            </div>
                                                          </div>
                                                      </div>
                                                  </div>';
                                                }
                                            }
                                            else{
                                                echo '<div class="col-md-12 payment">'.$payments_val['no_payments_found'].'</div>';
                                            }
                                          echo '</div> <!-- End panel group -->
                                    </div>
                                </div>
                          </div>
                         
                        </div>
            </div>';






            /*echo '<div class="row">';
                echo '<h3>Outgoing Payments</h3>';
                $payments = $DB->query("SELECT * FROM `" . $this->payments. "` WHERE `userid` = %d",$userinfo['userid']) or die(mysql_error());
                while($payment = $DB->fetch_array($payments)){
                    echo '<div class="col-md-12 payment">'.$payment['payment_title'].'</div>';
                }
            echo '</div>';
            echo '<div class="row">';
                echo '<h3>Income Payments</h3>';
                $payments = $DB->query("SELECT * FROM `" . $this->payments. "` WHERE `assigned_user`=%d",$userinfo['userid']);
                while($payment = $DB->fetch_array($payments)){
                    echo '<div class="col-md-12 payment">'.$payment['payment_title'].'</div>';
                }
            echo '</div>';*/

        }
        function UpdateForm(){

        }
        function ModalsBoxes(){
            global $DB, $userinfo,$payments_val;
            echo '<div class="popup" data-popup="popup-1">
                        <div class="popup-inner">
                            <div>
                            
                                <form class="form-contact-3 form-contact-finance" name="payment" id="payment" method="post" action="send_form_email.php">
                                    <div class="form-group col-sm-12  col-md-12"><h2>'.$payments_val['create_new'].'</h2></div>
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
                                    <input type="hidden" name="u" value="'.$userinfo['userid'].'">
                                    <a href="javascript:;" class="ot-btn large-btn btn-rounded  btn-main-color btn-submit payment" rel="payment">'.$payments_val['save'].'</a>
                                </form>
                                <form class="form-contact-3 form-contact-finance" name="payment" id="amount" method="post" action="send_form_email.php" style="display:none">
                                            <div class="form-group col-sm-12  col-md-12"><h2>Add Amount</h2></div>
                                            <div class="col-sm-12  col-md-9">
                                                <div id="steps_div">
                                                    <div class="step_area">
                                                        <div class="form-group col-sm-12  col-md-12">
                                                            <input type="text" class="form-control" name="amount_1" id="amount_1" placeholder="'.$payments_val['process_name'].'" value="" required>
                                                        </div>
                                                        <div class="form-group col-sm-12 col-md-12">
                                                            <textarea name="step_description_1" id="step_description_1"  class="form-control" placeholder="'.$payments_val['process_description'].'"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="act" value="add_amount">
                                                <input type="hidden" name="payment_id"  id="payment_id" value="'.$userinfo['userid'].'">
                                                <input type="hidden" name="num"  id="num" value="1">
                                                <a href="javascript:;" class="ot-btn large-btn btn-rounded  btn-main-color btn-submit payment" rel="amount">'.$payments_val['save'].'</a>
                                            </div>
                                             <div class="col-sm-12  col-md-3">
                                                <h4>Multistep payment?<br>
                                                Add more steps</h4>
                                                <a class="add_step" href="javascript:;">
                                                    <i class="fa fa-plus-square" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                </form>
                                
                                ';
                                ?>
                                <script type="text/javascript">
                                    function addStep(num,amount,step_description,step){
                                        var cl = '';
                                        var isEven = function(someNumber) {
                                            return (someNumber % 2 == 0) ? true : false;
                                        };
                                        if(isEven(num)){
                                            cl = 'evened';
                                        }
                                        else{
                                            cl = '';
                                        }
                                        var st = '<div class="step_area '+cl+'"><h4>'+step+num+'</h4><div class="form-group col-sm-12  col-md-12"><input type="text" class="form-control" name="amount_'+num+'" id="amount_'+num+'" placeholder="'+amount+'" value="" required></div><div class="form-group col-sm-12 col-md-12"><textarea name="step_description'+num+'" id="step_description'+num+'"  class="form-control" placeholder="'+step_description+'"></textarea></div></div>';
                                        return st;
                                    }
                                    $(document).on("click",".add_step",function(){
                                        var new_num = Number($("#num").val())+1;
                                        $("#steps_div").append(addStep(new_num,'asdf','asdfasdf','Step '));
                                        $("#num").val(new_num);
                                    });
                                    $(document).on("click",".payment",function(e){
                                        e.preventDefault;


                                        //var myForm = $(\'#payment\')
                                        var myForm = $('#'+$(this).attr("rel"));
                                        $(":input[required]").each(function () {

                                            if (myForm[0].checkValidity())
                                            {
                                                var form_data = $(myForm).serialize();
                                                $.ajax({
                                                    type: 'POST',
                                                    url: 'includes/data.php',
                                                    enctype: 'multipart/form-data',
                                                    data: form_data,
                                                    success:function(msg) {
                                                        var vars =  msg.split("::");
                                                        if(vars[0] == 'next'){
                                                            $("#payment_id").val(vars[1]);
                                                            $("#payment").fadeOut("fast");
                                                            $("#amount").fadeIn("fast");
                                                        }
                                                        return false;
                                                    },
                                                    error:function(){
                                                        alert('Whoops! This didn\'t work . Please contact us . ');
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
                                </div>
                            <a class="popup-close" data-popup-close="popup-1" href="#">x</a>   
                        </div>
                    </div>
<?php
        }
    }
}



$p_payment = new SD_Payment();

switch($_POST['action']){
    case 'update':
        break;
    case 'edit':
        break;
    case 'delete':
        break;
    default:
        $p_payment->DisplayPayments();
        break;
}
$p_payment->ModalsBoxes();
unset($p_payment);
?>