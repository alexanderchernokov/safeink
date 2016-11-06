<?php
/**
 * Created by PhpStorm.
 * User: Alex.Chernokov
 * Date: 14/10/2016
 * Time: 20:39
 */
?>
<div class="passwordBox animated fadeInDown">
    <div class="row">
        <div class="col-md-12">
            <div class="inqbox-content">
                <h2 class="font-bold">Forgot password</h2>
                <p>
                    Enter your email address and your password will be reset and emailed to you.
                </p>
                <div class="row">
                    <div class="col-lg-12">
                        <form class="m-t" role="form" action="http://softreliance.com/inq/index.html">
                            <div class="form-group">
                                <input type="email" class="form-control" placeholder="Email address" required="">
                            </div>
                            <button type="submit" class="btn btn-primary block full-width m-b">Send new password</button>
                            <p class="text-muted text-center">
                                <small>Do not have an account?</small>
                            </p>
                            <a class="btn btn-sm btn-white btn-block" href="/register">Create an account</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <hr/>
    <div class="row">
        <div class="col-md-12"><p class="m-t"> <small>All right reserved &copy; <?php echo date("Y",time());?></small> Safeink</p></div>
    </div>
</div>
