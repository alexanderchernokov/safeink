<?php
/**
 * Created by PhpStorm.
 * User: Alex.Chernokov
 * Date: 14/10/2016
 * Time: 18:42
 */
$auri = explode("/", strtolower(rtrim($_SERVER['REQUEST_URI'], "/")));
array_shift($auri);
$anavi = array("register","verify","login","forgot","reset");
if(isset($auri[0]) AND $auri[0] !=''){
    $dir = $auri[0];
}
if(!$dir) {
    $header = 'header.php';
    $content = 'home.php';
    $footer = 'footer.php';
}
elseif(in_array($dir,$anavi)){
    switch ($dir){
        case 'login':
        case 'register':
        case 'verify':
        case 'forgot':
        case 'reset':
            $header = 'login_header.php';
            $footer = 'login_footer.php';


        break;

        default:
            $header = 'header.php';
            $footer = 'footer.php';
            break;
    }
    $content = $dir.'.php';
    if(isset($auri[1]) AND $auri[1] !=''){
        switch ($dir){
            case 'login':
                $item = htmlentities($auri[1]);
                break;
            default:
                $item = (int)$auri[1];
                break;
        }
    }
}
else {
    $header = 'login_header.php';
    $content = '404.php';
    $footer = 'login_footer.php';
}