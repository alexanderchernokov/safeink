<?php
/**
 * Created by PhpStorm.
 * User: Alex.Chernokov
 * Date: 16/10/2016
 * Time: 09:12
 */
?>
<h3>Please choose your account type</h3>
<div class="row">
    <div class="col-md-6"><a href="#" rel="1" class="account_type btn btn-outline btn-info"><i class="fa fa-user"></i> Personal account</a></div>
    <div class="col-md-6"><a href="#" rel="2" class="account_type btn btn-outline btn-success"><i class="fa fa-group"></i> Company account</a></div>
    <input type="hidden" id="user" value="">
</div>
<div class="row" id="profile_form" style="display:none;">
    <div class="col-md-12">
        <form class="m-t" role="form" id="form">
            <div class="form-group">
                <label>Please select language</label>
                <select name="language" class="form-control">
                    <option value="en">English</option>
                </select>
            </div>
            <div class="form-group" style="display:none">
                <input type="text" name="company_name" id="company_name" value="none" class="form-control" placeholder="Company name" required="">
            </div>
            <input type="hidden" name="action" id="action" value="first_update">
            <input type="hidden" name="type" id="type" value="">
            <input type="hidden" name="user_id" id="user_id" value="">
            <button type="submit" class="btn btn-primary block full-width m-b" <?php echo $disabled;?>>Login</button>

        </form>
    </div>
</div>
