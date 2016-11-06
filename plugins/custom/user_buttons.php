<?php
/**
 * Created by PhpStorm.
 * User: Alex.Chernokov
 * Date: 22/10/2016
 * Time: 09:43
 */
if(isset($userinfo['userid']) AND $userinfo['userid'] !=''){
    $cms_head_user_button = '<ul><li><a href="#" class="ot-btn large-btn btn-main-color icon-btn-left"><i class="fa fa-user" aria-hidden="true"></i> '.$userinfo['display_name'].'</a></li></ul>';
}
else{
    $cms_head_user_button = '<ul>
                <li><span class="has-icon sm-icon"><span class="lnr lnr-phone-handset icon-set-1 icon-xs"></span> <span class="sub-text-icon text-middle sub-text-middle">0112-826-2789</span></span></li>
                <li>
                    <a href="#" class="ot-btn btn-rounded btn-orange-color icon-btn-left"><i class="fa fa-key" aria-hidden="true"></i> SIGN IN</a>
                </li>
                <li>
                    <a href="#" class="ot-btn btn-rounded btn-hightlight-color icon-btn-left"><i class="fa fa-sign-in" aria-hidden="true"></i> SIGN UP</a>
                </li>
            </ul>';
}