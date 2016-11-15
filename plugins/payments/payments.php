<?php
/**
 * Created by PhpStorm.
 * User: Alex.Chernokov
 * Date: 24/10/2016
 * Time: 10:51
 */
if(!defined('IN_PRGM')) return false;
include_once ('includes/custom_languages/'.$user_language.'/payments.php');
$refreshpage = '/'.$user_language.'/payments/';
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
                                                                            <p><h4><a href="payments/edit/'.$payment['paymentid'].'">'.$payment['payment_title'].' <i class="fa fa-pencil-square-o" aria-hidden="true"></i></a></h4></p>
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
                                                                                <h3>'.$payments_val['to_do'].'</h3>
                                                                                <a href="#" class="ot-btn  small-btn btn-main-color"> Edit</a>
                                                                                <a href="#" class="ot-btn  small-btn btn-red-color"><i class="fa fa-trash-o" aria-hidden="true"></i> Cancel</a>
                                                                          </div>
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
        }
        function UpdateForm()
        {
            global $DB, $userinfo,$payments_val;
            echo $this->module_header;
            echo '<div class="finance-tabs-style-2 clearfix">
                      <ul class="nav nav-tabs">
                        <li class="active"><a data-toggle="tab" href="#Payments" aria-expanded="false"><i class="fa fa-credit-card" aria-hidden="true"></i> '.$payments_val['payments'].'</a></li>
                        <li class=""><a data-toggle="tab" href="#General" aria-expanded="false"><i class="fa fa-info" aria-hidden="true"></i> '.$payments_val['general_info'].'</a></li>
                      </ul>

                        <div class="tab-content">
                          <div id="Payments" class="tab-pane fade active in">
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
                                                                            <p><h4><a href="payments/edit/'.$payment['paymentid'].'">'.$payment['payment_title'].' <i class="fa fa-pencil-square-o" aria-hidden="true"></i></a></h4></p>
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
                                                                                <h3>'.$payments_val['to_do'].'</h3>
                                                                                <a href="#" class="ot-btn  small-btn btn-main-color"> Edit</a>
                                                                                <a href="#" class="ot-btn  small-btn btn-red-color"><i class="fa fa-trash-o" aria-hidden="true"></i> Cancel</a>
                                                                          </div>
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
                          <div id="General" class="tab-pane fade">
                                
                                
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
        }
        function CreateForm(){
            global $DB, $userinfo, $refreshpage , $payments_val;
            $title         = htmlentities($_POST['payment_title']);
            $description      = htmlentities($_POST['payment_description']);
            $type        = (int)$_POST['type'];
            if($type == 1){
                $bayer = $userinfo['userid'];
                $recipient = 0;
            }
            else{
                $bayer = 0;
                $recipient = $userinfo['userid'];
            }
            $DB->query("INSERT INTO ".$this->payments."
                        (`bayer_userid`,`payment_title`,`payment_description`,`recipient_userid`,`status`,`is_own`)
                        VALUES
                        (%d,'%s','%s',%d,%d,%d)
                        ",$bayer,$title,$description,$recipient,1,$userinfo['userid']);
            $id = $DB->insert_id();
            $refreshpage = $refreshpage.'edit/'.$id;
            RedirectPage($refreshpage, $payments_val['created_new']);
        }
        function ErrorMessage(){
            echo 'Illegal operation!';
        }
        function ModalsBoxes(){
            global $DB, $userinfo,$payments_val,$user_language;
            echo '<div class="popup" data-popup="popup-1">
                        <div class="popup-inner">
                            <div>
                            
                                <form class="form-contact-3 form-contact-finance" name="payment" id="payment" method="post" action="/'.$user_language.'/payments/add">
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
                                    <button type="submit" class="ot-btn large-btn btn-rounded  btn-main-color btn-submit">'.$payments_val['save'].'</button>
                                </form>
                                ';
                                ?>

                            </div>
                            <a class="popup-close" data-popup-close="popup-1" href="#">x</a>   
                        </div>
                    </div>
<?php
        }
    }
}

//$seotitle         = GetVar('seo_title', '', 'string');
//$description      = GetVar('description', '', 'html', true, false);
//$del_image        = GetVar('delete_image', false, 'whole_number', true, false);

//$actions_arr = explode("/",$_SERVER['REQUEST_URI']);
$p_payment = new SD_Payment();
$list = array('add','update','edit','delete','');
if(in_array($uri_arr[3],$list)){
    $switch = $uri_arr[3];
}
else{
    $switch = 'error';
}
switch($switch){
    case 'update':
        break;
    case 'edit':
        $p_payment->UpdateForm();
        break;
    case 'delete':
        break;
    case 'add':
        $p_payment->CreateForm();
        break;
    case 'error':
        $p_payment->ErrorMessage();
        break;
    default:
        $p_payment->DisplayPayments();
        break;
}
$p_payment->ModalsBoxes();
unset($p_payment);
?>