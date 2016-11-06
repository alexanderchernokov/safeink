<?php
if(isset($item)){
    $expiration = time();

    if($datas = $database->select("users",
        [
            "user_id",
            "token_expiration",
            "verified"
        ],
        [
            "token" => $item
        ]
    )) {
        foreach ($datas as $data) {
            if (isset($data['user_id']) AND $data['user_id'] != '') {
                if ($data['verified'] == 0) {
                    if ($data['token_expiration'] >= $expiration) {
                        $datas = $database->update("users",
                            [
                                "verified" => 1
                            ],
                            [
                                "user_id" => $data['user_id']
                            ]
                        );
                        $msg = '<div class="alert alert-success">Account is activated</div>';
                        $disabled = '';
                    } else {
                        $msg = '<div class="alert alert-danger"Activation link is expired</div>';
                        $disabled = 'disabled="disabled"';
                    }
                } else {
                    $msg = '<div class="alert alert-warning">Account is activated</a></div>';
                    $disabled = '';
                }
            }
        }
    }
    else {
        $msg = '<div class="alert alert-danger">Invalid token</div>';
        $disabled = 'disabled="disabled"';
    }
}
else{
    $disabled = '';
}
/**
 * Created by PhpStorm.
 * User: Alex.Chernokov
 * Date: 14/10/2016
 * Time: 20:20
 */
?>
<body class="gray-bg">
<div class="loginColumns animated fadeInDown">
    <div class="row">
        <div class="col-md-6">
            <h2 class="font-bold">Welcome to Safeink</h2>
            <p>
                <?php echo $msg;?>
            </p>
            <p>
                Perfectly designed and precisely prepared admin theme with over 50 pages with extra new web app views.
            </p>
            <p>
                Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s.
            </p>
            <p>
                When an unknown printer took a galley of type and scrambled it to make a type specimen book.
            </p>
            <p>
                <small>It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged.</small>
            </p>
            <input type="hidden" id="user" val="">
        </div>
        <div class="col-md-6">
            <div>
                <img src="/images/logo_600.png" style="width:100%">
            </div>
            <div class="inqbox-content">
                <form class="m-t" role="form" id="form" action="http://softreliance.com/inq/index.html">
                    <div class="form-group">
                        <input type="email" name="email" id="email" class="form-control" placeholder="Username" required="" <?php echo $disabled;?>>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Password" required="" <?php echo $disabled;?>>
                    </div>
                    <input type="hidden" name="action" id="action" value="login">
                    <button type="submit" class="btn btn-primary block full-width m-b" <?php echo $disabled;?>>Login</button>
                    <a href="/forgot">
                        <small>Forgot password?</small>
                    </a>
                    <p class="text-muted text-center">
                        <small>Do not have an account?</small>
                    </p>
                    <a class="btn btn-sm btn-white btn-block" href="/register">Create an account</a>
                    <div id="msg"></div>
                </form>

            </div>
        </div>
    </div>
    <hr/>
    <div class="row">
        <div class="col-md-12"><p class="m-t"> <small>All right reserved &copy; <?php echo date("Y",time());?></small> Safeink</p></div>
    </div>
    
</div>
