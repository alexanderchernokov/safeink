<?php
/**
 * Created by PhpStorm.
 * User: Alex.Chernokov
 * Date: 24/10/2016
 * Time: 10:51
 */
if(!defined('IN_PRGM')) return false;
include_once ('includes/custom_languages/'.$user_language.'/login.php');
if(!class_exists('SD_Profile'))
{
    class SD_Profile
    {
        public function __construct()
        {
            global $userinfo;
            if($this->pluginid = GetPluginIDbyFolder(sd_GetCurrentFolder(__FILE__)))
            {
                $this->language = GetLanguage($this->pluginid);
                $this->settings = GetPluginSettings($this->pluginid);
                $this->hasViewPerms = !empty($userinfo['username']);
                $this->users = 'sd_users';
            }
        }
        function DisplayForm()
        {
            global $userinfo, $profile_values;

            echo '<div class="finance-tabs-style-2 clearfix">
                      <ul class="nav nav-tabs">
                        <li class="active"><a data-toggle="tab" href="#PersonalInfo" aria-expanded="false"><i class="fa fa-user"></i> '.$profile_values['personal_info'].'</a></li>
                        <li class=""><a data-toggle="tab" href="#CompanyInfo" aria-expanded="false"><i class="fa fa-users" aria-hidden="true"></i> '.$profile_values['company_info'].'</a></li>
                        <li class=""><a data-toggle="tab" href="#PaymentInfo" aria-expanded="false"><i class="fa fa-paypal" aria-hidden="true"></i> '.$profile_values['payment_info'].'</a></li>
                        <li class=""><a data-toggle="tab" href="#ChangePassword" aria-expanded="true"><i class="fa fa-key" aria-hidden="true"></i> '.$profile_values['change_password'].'</a></li>
                      </ul>

                        <div class="tab-content">
                          <div id="PersonalInfo" class="tab-pane fade active in">
                                <h4> '.$profile_values['personal_info'].'</h4>
                                <form class="form-contact-3 form-contact-finance" name="contact" method="post" action="send_form_email.php">
                                    <div class="form-group col-sm-12  col-md-12">
                                        <input type="text" class="form-control" name="first_name" id="first_name" placeholder="'.$profile_values['your_first_name'].'" value="'.$userinfo['profile']['first_name'].'">
                                    </div>
                                    <div class="form-group col-sm-12 col-md-12">
                                        <input type="text" class="form-control" name="last_name" id="last_name" placeholder="'.$profile_values['your_last_name'].'" value="'.$userinfo['profile']['last_name'].'" >
                                    </div>
                                    <div class="form-group col-sm-12 col-md-12">
                                        <input type="email" class="form-control" name="email" id="email" placeholder="'.$profile_values['your_email'].'" value="'.$userinfo['email'].'">
                                    </div>
                                    <div class="form-group col-sm-12 col-md-12">
                                        <input type="email" class="form-control" name="phone" id="phone" placeholder="'.$profile_values['your_phone_number'].'" value="'.$userinfo['profile']['contact_phone'].'">
                                    </div>
                                    
                                    <button href="#" class="ot-btn large-btn btn-rounded  btn-main-color btn-submit">'.$profile_values['save_changes'].'</button>
                                </form> <!-- End Form -->
                          </div>
                          <div id="CompanyInfo" class="tab-pane fade">
                                <h4> '.$profile_values['company_info'].'</h4>
                                <form class="form-contact-3 form-contact-finance" name="contact" method="post" action="send_form_email.php">
                                    <div class="form-group col-sm-12  col-md-12">
                                        <input type="text" class="form-control" name="company_name" id="company_name" placeholder="'.$profile_values['company_name'].'" value="'.$userinfo['profile']['user_company'].'" required>
                                    </div>
                                    <div class="form-group col-sm-12 col-md-12">
                                        <input type="text" class="form-control" name="company_fax" id="company_fax" placeholder="'.$profile_values['company_fax_number'].'" value="'.$userinfo['profile']['contact_office_phone'].'" >
                                    </div>
                                    
                                    <button href="#" class="ot-btn large-btn btn-rounded  btn-main-color btn-submit">'.$profile_values['save_changes'].'</button>
                                </form> <!-- End Form -->
                          </div>
                          <div id="PaymentInfo" class="tab-pane fade">
                                <h4> '.$profile_values['payment_info'].'</h4>
                                <form class="form-contact-3 form-contact-finance" name="contact" method="post" action="send_form_email.php">
                                    <h5>'.$profile_values['paypal'].'</h5>
                                    <div class="form-group col-sm-12  col-md-12">
                                        <input type="text" class="form-control" name="paypal" id="paypal" placeholder="'.$profile_values['paypal_account'].'" value="'.$userinfo['profile']['user_company'].'">
                                    </div>
                                    <h5>'.$profile_values['bank_information'].'</h5>
                                    <div class="form-group col-sm-12 col-md-12">
                                        <input type="text" class="form-control" name="swift" id="swift" placeholder="'.$profile_values['SWIFT'].'" value="'.$userinfo['profile']['swift_number'].'" >
                                    </div>
                                    <div class="form-group col-sm-12 col-md-12">
                                        <input type="text" class="form-control" name="ibann" id="ibann" placeholder="'.$profile_values['IBANN'].'" value="'.$userinfo['profile']['ibann_number'].'" >
                                    </div>
                                    
                                    <button href="#" class="ot-btn large-btn btn-rounded  btn-main-color btn-submit">'.$profile_values['save_changes'].'</button>
                                </form> <!-- End Form -->
                          </div>
                          <div id="ChangePassword" class="tab-pane fade">
                                <h4> '.$profile_values['change_password'].'</h4>
                                <form class="form-contact-3 form-contact-finance" name="contact" method="post" action="send_form_email.php">
                                    <div class="form-group col-sm-12  col-md-12">
                                        <input type="password" required class="form-control" name="password" id="password" placeholder="'.$profile_values['new_password'].'" value="">
                                    </div>
                                    <div class="form-group col-sm-12 col-md-12">
                                        <input type="password" required class="form-control" name="v_password" id="v_password" placeholder="'.$profile_values['verify_new_password'].'" value="" >
                                    </div>
                                    
                                    <button type="submit" href="#" class="ot-btn large-btn btn-rounded  btn-main-color btn-submit">'.$profile_values['save_changes'].'</button>
                                </form> <!-- End Form -->
                          </div>
                        </div>
            </div>';






        }
        function UpdateForm(){

        }
    }
}



$profile = new SD_Profile();
switch($_POST['action']){
    case 'update':
        break;
    default:
        $profile->DisplayForm();
        print_r($userinfo);
        break;
}
unset($profile);
?>