<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Created by PhpStorm.
 * User: Alex.Chernokov
 * Date: 14/10/2016
 * Time: 17:31
 */
require ('includes/db/init_db.php');

$t = htmlentities($_GET['t']);
$expiration = time();
$datas = $database->select("users",
                                    [
                                        "user_id",
                                        "token_expiration",
                                        "verified"
                                    ],
                                    [
                                        "token[=]" => $t
                                    ]
                           );
foreach($datas as $data)
{
    if(isset($data['user_id']) AND $data['user_id'] != ''){

    }
    else{
        $msg = 'Invalid token';
    }
}
?>