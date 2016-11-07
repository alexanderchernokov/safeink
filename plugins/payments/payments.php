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
            global $userinfo;
            if($this->pluginid = GetPluginIDbyFolder(sd_GetCurrentFolder(__FILE__)))
            {
                //$this->language = GetLanguage($this->pluginid);
                //$this->settings = GetPluginSettings($this->pluginid);
                $this->username = !empty($userinfo['username']);
                $this->payments = 'sd_payments';
            }
        }

        function DisplayPayments()
        {
            global $DB, $userinfo,$payments_val;

            echo '<div class="finance-tabs-style-2 clearfix">
                      <ul class="nav nav-tabs">
                        <li class="active"><a data-toggle="tab" href="#OutgoingPayments" aria-expanded="false"><i class="fa fa-user"></i> '.$payments_val['outgoing_payments'].'</a></li>
                        <li class=""><a data-toggle="tab" href="#IncomePayments" aria-expanded="false"><i class="fa fa-users" aria-hidden="true"></i> '.$payments_val['income_payments'].'</a></li>
                        <li class=""><a data-toggle="tab" href="#PaymentsHistory" aria-expanded="false"><i class="fa fa-users" aria-hidden="true"></i> '.$payments_val['payments_history'].'</a></li>
                      </ul>

                        <div class="tab-content">
                          <div id="OutgoingPayments" class="tab-pane fade active in">
                                <h4> '.$payments_val['outgoing_payments'].'</h4>
                                
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
                                <h4> '.$payments_val['income_payments'].'</h4>
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
                                <h4> '.$payments_val['payments_history'].'</h4>
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
    case 'edit':
        break;
    case 'delete':
        break;
    default:
        $p_payment->DisplayPayments();
        break;
}
unset($p_payment);
?>