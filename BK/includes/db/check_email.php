<?php
require ('init_db_ajax.php');
/**
 * Created by PhpStorm.
 * User: Alex.Chernokov
 * Date: 15/10/2016
 * Time: 09:46
 */
$datas = $database->select("users",
    [
        "user_id",
        "token_expiration",
        "verified"
    ],
    [
        "token" => $item
    ]
);
$count = $database->count("users", [
    "email" => $_POST['email']
]);

    if($count>0){
        echo false;
    }else{
        echo true;
    }
?>