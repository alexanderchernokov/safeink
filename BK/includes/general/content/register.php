<body class="gray-bg">
<div class="middle-box text-center loginscreen   animated fadeInDown">
    <div>
        <div>
            <img src="/images/logo_600.png">
        </div>        
        <h3>Register to Safeink</h3>
        <p>Create account to see it in action.</p>
        <form id="form" class="m-t" role="form" action="#" method="post" novalidate="novalidate">
            <div class="form-group">
                <select name="account_type" class="form-control type" id="account_type" required="" aria-required="true" aria-invalid="true">
                    <option value="">Please select a kind of account</option>
                    <option value="1">Personal account</option>
                    <option value="2">Company account</option>
                </select>
            </div>
            <div class="form-group" style="display: none" id="comp">
                <input type="text" name="company" id="company" placeholder="Company name" class="form-control" required="" aria-required="true" aria-invalid="true" value="none">
            </div>
            <div class="form-group">
                <select name="language" class="form-control" id="language">
                    <option value="">Please select a language</option>
                    <option value="1">English</option>
                </select>
            </div>
            <div class="form-group">
                <input type="email" name="email" id="email" placeholder="Enter email" class="form-control" required="" aria-required="true" aria-invalid="true">
            </div>
            <div class="form-group">
                <input type="password" placeholder="Password" class="form-control" name="password" id="password" aria-required="true" aria-invalid="false">
            </div>
            <input type="hidden" name="action" id="action" value="register">
            <button type="submit" class="btn btn-primary block full-width m-b">Register</button>
            <p class="text-muted text-center"><small>Already have an account?</small></p>
            <a class="btn btn-sm btn-white btn-block" href="/login">Login</a>
            <div class="form-group">
                <div id="msg"></div>
            </div>
        </form>
        <p class="m-t"> <small>All right reserved &copy; <?php echo date("Y",time());?></small> Safeink</p>
    </div>
</div>
