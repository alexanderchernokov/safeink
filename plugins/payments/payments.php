<?php
/**
 * Created by PhpStorm.
 * User: Alex.Chernokov
 * Date: 24/10/2016
 * Time: 10:51
 */
if(!defined('IN_PRGM')) return false;

if(!class_exists('SD_Payment'))
{
    class SD_Payment
    {
        public function __construct()
        {
            global $userinfo;
            if($this->pluginid = GetPluginIDbyFolder(sd_GetCurrentFolder(__FILE__)))
            {
                //$this->language = GetLanguage($this->pluginid);
                //$this->settings = GetPluginSettings($this->pluginid);
                $this->username = !empty($userinfo['username']);
                $this->payments = 'sd_payments';
            }
        }
        function DisplayForm()
        {
            global $DB, $userinfo;

            echo '<div class="finance-tabs-style-2 clearfix">
                      <ul class="nav nav-tabs">
                        <li class="active"><a data-toggle="tab" href="#PersonalInfo" aria-expanded="false">Personal Info</a></li>
                        <li class=""><a data-toggle="tab" href="#CompanyInfo" aria-expanded="false">Company Info</a></li>
                        <li class=""><a data-toggle="tab" href="#PaymentInfo" aria-expanded="false">Payment Info</a></li>
                        <li class=""><a data-toggle="tab" href="#ChangePassword" aria-expanded="true">Change Password</a></li>
                      </ul>

                        <div class="tab-content">
                          <div id="PersonalInfo" class="tab-pane fade active in">
                                <h4> Personal Info</h4>
                                <form class="form-contact-3 form-contact-finance" name="contact" method="post" action="send_form_email.php">
                                    <div class="form-group col-sm-12  col-md-12">
                                        <input type="text" class="form-control" name="first_name" id="first_name" placeholder="Your First Name" value="'.$userinfo['profile']['first_name'].'">
                                    </div>
                                    <div class="form-group col-sm-12 col-md-12">
                                        <input type="text" class="form-control" name="last_name" id="last_name" placeholder="Your Last Name" value="'.$userinfo['profile']['last_name'].'" >
                                    </div>
                                    <div class="form-group col-sm-12 col-md-12">
                                        <input type="email" class="form-control" name="email" id="email" placeholder="Your Email" value="'.$userinfo['email'].'">
                                    </div>
                                    <div class="form-group col-sm-12 col-md-12">
                                        <input type="email" class="form-control" name="phone" id="phone" placeholder="Your Phone Number" value="'.$userinfo['profile']['contact_phone'].'">
                                    </div>
                                    
                                    <button href="#" class="ot-btn large-btn btn-rounded  btn-main-color btn-submit">Save</button>
                                </form> <!-- End Form -->
                          </div>
                          <div id="CompanyInfo" class="tab-pane fade">
                                <h4> Company Info</h4>
                                <form class="form-contact-3 form-contact-finance" name="contact" method="post" action="send_form_email.php">
                                    <div class="form-group col-sm-12  col-md-12">
                                        <input type="text" class="form-control" name="company_name" id="company_name" placeholder="Company Name" value="'.$userinfo['profile']['user_company'].'">
                                    </div>
                                    <div class="form-group col-sm-12 col-md-12">
                                        <input type="text" class="form-control" name="company_fax" id="company_fax" placeholder="Fax Number" value="'.$userinfo['profile']['contact_office_phone'].'" >
                                    </div>
                                    
                                    <button href="#" class="ot-btn large-btn btn-rounded  btn-main-color btn-submit">Save</button>
                                </form> <!-- End Form -->
                          </div>
                          <div id="PaymentInfo" class="tab-pane fade">
                                <h4> Payment Info</h4>
                                <form class="form-contact-3 form-contact-finance" name="contact" method="post" action="send_form_email.php">
                                    <h5>Paypal</h5>
                                    <div class="form-group col-sm-12  col-md-12">
                                        <input type="text" class="form-control" name="paypal" id="paypal" placeholder="PayPal account" value="'.$userinfo['profile']['user_company'].'">
                                    </div>
                                    <h5>Bank account</h5>
                                    <div class="form-group col-sm-12 col-md-12">
                                        <input type="text" class="form-control" name="swift" id="swift" placeholder="Swift" value="'.$userinfo['profile']['swift_number'].'" >
                                    </div>
                                    <div class="form-group col-sm-12 col-md-12">
                                        <input type="text" class="form-control" name="ibann" id="ibann" placeholder="IBANN" value="'.$userinfo['profile']['ibann_number'].'" >
                                    </div>
                                    
                                    <button href="#" class="ot-btn large-btn btn-rounded  btn-main-color btn-submit">Save</button>
                                </form> <!-- End Form -->
                          </div>
                          <div id="ChangePassword" class="tab-pane fade">
                                <h4> Change Password</h4>
                                <form class="form-contact-3 form-contact-finance" name="contact" method="post" action="send_form_email.php">
                                    <div class="form-group col-sm-12  col-md-12">
                                        <input type="password" required class="form-control" name="password" id="password" placeholder="New Password" value="">
                                    </div>
                                    <h5>Bank account</h5>
                                    <div class="form-group col-sm-12 col-md-12">
                                        <input type="password" required class="form-control" name="v_password" id="v_password" placeholder="Verify New Password" value="" >
                                    </div>
                                    
                                    <button type="submit" href="#" class="ot-btn large-btn btn-rounded  btn-main-color btn-submit">Save</button>
                                </form> <!-- End Form -->
                          </div>
                        </div>
            </div>';






        }
        function DisplayPayments()
        {
            global $DB, $userinfo;

            echo '<div class="finance-tabs-style-2 clearfix">
                      <ul class="nav nav-tabs">
                        <li class="active"><a data-toggle="tab" href="#OutgoingPayments" aria-expanded="false"><i class="fa fa-user"></i> Outgoing Payments</a></li>
                        <li class=""><a data-toggle="tab" href="#IncomePayments" aria-expanded="false"><i class="fa fa-users" aria-hidden="true"></i> Income Payments</a></li>
                        <li class=""><a data-toggle="tab" href="#PaymentsHistory" aria-expanded="false"><i class="fa fa-users" aria-hidden="true"></i> Payments History</a></li>
                      </ul>

                        <div class="tab-content">
                          <div id="OutgoingPayments" class="tab-pane fade active in">
                                <h4> Outgoing Payments</h4>
                                
                                <div class="accordion-style-light no-round">
                                    <div class="accordion-warp">
                                      <div class="clearfix"></div>
                                      <div class="panel-group" id="accordion1">
                                      <div class="panel panel-default">
                                          <div class="panel-heading">
                                              <h4 class="panel-title">
                                                  <a data-toggle="collapse" data-parent="#accordion1" href="#out1" class="collapsed">01. Define Objectives</a>
                                              </h4>
                                          </div>
                                          <div id="out1" class="panel-collapse collapse">
                                              <div class="panel-body">
                                                <div class="accordion-content">
                                                  <p>Fusce ornare mi vel risus porttitor dignissim. Nunc eget risus at ipsum blandit ornare vel sed velit. Proin gravida arcu nisl, a dignissim mauris placerat id. Vivamus interdum urna at sapien varius elementum. Suspendisse ut mi felis et interdum libero lacinia vel. Aenean elementum odio ut lorem cursus, eu auctor magna pellentesque.  </p>
                                                </div>
                                              </div>
                                          </div>
                                      </div>
                                      <div class="panel panel-default">
                                          <div class="panel-heading">
                                              <h4 class="panel-title">
                                                  <a data-toggle="collapse" data-parent="#accordion1" href="#out2" class="collapsed">02. Develop a Plan</a>
                                              </h4>
                                          </div>
                                          <div id="out2" class="panel-collapse collapse">
                                              <div class="panel-body">
                                                  <div class="accordion-content">
                                                  <p>Fusce ornare mi vel risus porttitor dignissim. Nunc eget risus at ipsum blandit ornare vel sed velit. Proin gravida arcu nisl, a dignissim mauris placerat id. Vivamus interdum urna at sapien varius elementum. Suspendisse ut mi felis et interdum libero lacinia vel. Aenean elementum odio ut lorem cursus, eu auctor magna pellentesque.  </p>
                                                </div>
                                              </div>
                                          </div>
                                      </div>
                                      <div class="panel panel-default">
                                          <div class="panel-heading">
                                              <h4 class="panel-title">
                                                  <a data-toggle="collapse" data-parent="#accordion1" href="#out3" class="collapsed">03. Implementation</a>
                                              </h4>
                                          </div>
                                          <div id="out3" class="panel-collapse collapse">
                                              <div class="panel-body">
                                                  <div class="accordion-content">
                                                  <p>Fusce ornare mi vel risus porttitor dignissim. Nunc eget risus at ipsum blandit ornare vel sed velit. Proin gravida arcu nisl, a dignissim mauris placerat id. Vivamus interdum urna at sapien varius elementum. Suspendisse ut mi felis et interdum libero lacinia vel. Aenean elementum odio ut lorem cursus, eu auctor magna pellentesque.  </p>
                                                </div>
                                              </div>
                                          </div>
                                      </div>
                                      <div class="panel panel-default">
                                          <div class="panel-heading">
                                              <h4 class="panel-title">
                                                  <a data-toggle="collapse" data-parent="#accordion1" href="#out4" class="collapsed">04. Monitor Results</a>
                                              </h4>
                                          </div>
                                          <div id="out4" class="panel-collapse collapse">
                                              <div class="panel-body">
                                                  <div class="accordion-content">
                                                  <p>Fusce ornare mi vel risus porttitor dignissim. Nunc eget risus at ipsum blandit ornare vel sed velit. Proin gravida arcu nisl, a dignissim mauris placerat id. Vivamus interdum urna at sapien varius elementum. Suspendisse ut mi felis et interdum libero lacinia vel. Aenean elementum odio ut lorem cursus, eu auctor magna pellentesque.  </p>
                                                </div>
                                              </div>
                                          </div>
                                      </div>
                                      </div> <!-- End panel group -->
                                    </div>
                                </div>
                                
                          </div>
                          <div id="IncomePayments" class="tab-pane fade">
                                <h4> Income Payments</h4>
                                <div class="accordion-style-light no-round">
                                    <div class="accordion-warp">
                                      <div class="clearfix"></div>
                                      <div class="panel-group" id="accordion2">
                                      <div class="panel panel-default">
                                          <div class="panel-heading">
                                              <h4 class="panel-title">
                                                  <a data-toggle="collapse" data-parent="#accordion2" href="#in1" class="collapsed">01. Define Objectives</a>
                                              </h4>
                                          </div>
                                          <div id="in1" class="panel-collapse collapse">
                                              <div class="panel-body">
                                                <div class="accordion-content">
                                                  <p>Fusce ornare mi vel risus porttitor dignissim. Nunc eget risus at ipsum blandit ornare vel sed velit. Proin gravida arcu nisl, a dignissim mauris placerat id. Vivamus interdum urna at sapien varius elementum. Suspendisse ut mi felis et interdum libero lacinia vel. Aenean elementum odio ut lorem cursus, eu auctor magna pellentesque.  </p>
                                                </div>
                                              </div>
                                          </div>
                                      </div>
                                      <div class="panel panel-default">
                                          <div class="panel-heading">
                                              <h4 class="panel-title">
                                                  <a data-toggle="collapse" data-parent="#accordion2" href="#in2" class="collapsed">02. Develop a Plan</a>
                                              </h4>
                                          </div>
                                          <div id="in2" class="panel-collapse collapse">
                                              <div class="panel-body">
                                                  <div class="accordion-content">
                                                  <p>Fusce ornare mi vel risus porttitor dignissim. Nunc eget risus at ipsum blandit ornare vel sed velit. Proin gravida arcu nisl, a dignissim mauris placerat id. Vivamus interdum urna at sapien varius elementum. Suspendisse ut mi felis et interdum libero lacinia vel. Aenean elementum odio ut lorem cursus, eu auctor magna pellentesque.  </p>
                                                </div>
                                              </div>
                                          </div>
                                      </div>
                                      <div class="panel panel-default">
                                          <div class="panel-heading">
                                              <h4 class="panel-title">
                                                  <a data-toggle="collapse" data-parent="#accordion2" href="#in3" class="collapsed">03. Implementation</a>
                                              </h4>
                                          </div>
                                          <div id="in3" class="panel-collapse collapse">
                                              <div class="panel-body">
                                                  <div class="accordion-content">
                                                  <p>Fusce ornare mi vel risus porttitor dignissim. Nunc eget risus at ipsum blandit ornare vel sed velit. Proin gravida arcu nisl, a dignissim mauris placerat id. Vivamus interdum urna at sapien varius elementum. Suspendisse ut mi felis et interdum libero lacinia vel. Aenean elementum odio ut lorem cursus, eu auctor magna pellentesque.  </p>
                                                </div>
                                              </div>
                                          </div>
                                      </div>
                                      <div class="panel panel-default">
                                          <div class="panel-heading">
                                              <h4 class="panel-title">
                                                  <a data-toggle="collapse" data-parent="#accordion2" href="#in4" class="collapsed">04. Monitor Results</a>
                                              </h4>
                                          </div>
                                          <div id="in4" class="panel-collapse collapse">
                                              <div class="panel-body">
                                                  <div class="accordion-content">
                                                  <p>Fusce ornare mi vel risus porttitor dignissim. Nunc eget risus at ipsum blandit ornare vel sed velit. Proin gravida arcu nisl, a dignissim mauris placerat id. Vivamus interdum urna at sapien varius elementum. Suspendisse ut mi felis et interdum libero lacinia vel. Aenean elementum odio ut lorem cursus, eu auctor magna pellentesque.  </p>
                                                </div>
                                              </div>
                                          </div>
                                      </div>
                                      </div> <!-- End panel group -->
                                    </div>
                                </div>
                          </div>
                          <div id="PaymentsHistory" class="tab-pane fade">
                                <h4> Payments History</h4>
                                <div class="accordion-style-light no-round">
                                    <div class="accordion-warp">
                                      <div class="clearfix"></div>
                                      <div class="panel-group" id="accordion3">
                                      <div class="panel panel-default">
                                          <div class="panel-heading">
                                              <h4 class="panel-title">
                                                  <a data-toggle="collapse" data-parent="#accordion3" href="#his1" class="collapsed">01. Define Objectives</a>
                                              </h4>
                                          </div>
                                          <div id="his1" class="panel-collapse collapse">
                                              <div class="panel-body">
                                                <div class="accordion-content">
                                                  <p>Fusce ornare mi vel risus porttitor dignissim. Nunc eget risus at ipsum blandit ornare vel sed velit. Proin gravida arcu nisl, a dignissim mauris placerat id. Vivamus interdum urna at sapien varius elementum. Suspendisse ut mi felis et interdum libero lacinia vel. Aenean elementum odio ut lorem cursus, eu auctor magna pellentesque.  </p>
                                                </div>
                                              </div>
                                          </div>
                                      </div>
                                      <div class="panel panel-default">
                                          <div class="panel-heading">
                                              <h4 class="panel-title">
                                                  <a data-toggle="collapse" data-parent="#accordion3" href="#his2" class="collapsed">02. Develop a Plan</a>
                                              </h4>
                                          </div>
                                          <div id="his2" class="panel-collapse collapse">
                                              <div class="panel-body">
                                                  <div class="accordion-content">
                                                  <p>Fusce ornare mi vel risus porttitor dignissim. Nunc eget risus at ipsum blandit ornare vel sed velit. Proin gravida arcu nisl, a dignissim mauris placerat id. Vivamus interdum urna at sapien varius elementum. Suspendisse ut mi felis et interdum libero lacinia vel. Aenean elementum odio ut lorem cursus, eu auctor magna pellentesque.  </p>
                                                </div>
                                              </div>
                                          </div>
                                      </div>
                                      <div class="panel panel-default">
                                          <div class="panel-heading">
                                              <h4 class="panel-title">
                                                  <a data-toggle="collapse" data-parent="#accordion3" href="#his3" class="collapsed">03. Implementation</a>
                                              </h4>
                                          </div>
                                          <div id="his3" class="panel-collapse collapse">
                                              <div class="panel-body">
                                                  <div class="accordion-content">
                                                  <p>Fusce ornare mi vel risus porttitor dignissim. Nunc eget risus at ipsum blandit ornare vel sed velit. Proin gravida arcu nisl, a dignissim mauris placerat id. Vivamus interdum urna at sapien varius elementum. Suspendisse ut mi felis et interdum libero lacinia vel. Aenean elementum odio ut lorem cursus, eu auctor magna pellentesque.  </p>
                                                </div>
                                              </div>
                                          </div>
                                      </div>
                                      <div class="panel panel-default">
                                          <div class="panel-heading">
                                              <h4 class="panel-title">
                                                  <a data-toggle="collapse" data-parent="#accordion3" href="#his4" class="collapsed">04. Monitor Results</a>
                                              </h4>
                                          </div>
                                          <div id="his4" class="panel-collapse collapse">
                                              <div class="panel-body">
                                                  <div class="accordion-content">
                                                  <p>Fusce ornare mi vel risus porttitor dignissim. Nunc eget risus at ipsum blandit ornare vel sed velit. Proin gravida arcu nisl, a dignissim mauris placerat id. Vivamus interdum urna at sapien varius elementum. Suspendisse ut mi felis et interdum libero lacinia vel. Aenean elementum odio ut lorem cursus, eu auctor magna pellentesque.  </p>
                                                </div>
                                              </div>
                                          </div>
                                      </div>
                                      </div> <!-- End panel group -->
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
    }
}



$p_payment = new SD_Payment();
switch($_POST['action']){
    case 'update':
        break;
    default:
        $p_payment->DisplayPayments();
        break;
}
unset($p_payment);
?>