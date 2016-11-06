<?php
require ('init_db_ajax.php');
/**
 * Created by PhpStorm.
 * User: alexander.c
 * Date: 10/10/2016
 * Time: 17:54
 */
function getSalt() {
    $charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789{}:?.,!%^()-_=+|';
    $randString = "";
    $randStringLen = 10;

    while(strlen($randString) < $randStringLen) {
        $randChar = substr(str_shuffle($charset), mt_rand(0, strlen($charset)), 1);
        $randString .= $randChar;
    }

    return $randString;
}

function getToken() {
    $charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567891234567890';
    $randString = "";
    $randStringLen = 25;

    while(strlen($randString) < $randStringLen) {
        $randChar = substr(str_shuffle($charset), mt_rand(0, strlen($charset)), 1);
        $randString .= $randChar;
    }

    return $randString;
}

function getSaltedHash($password, $salt) {
    $hash = $password . $salt;
    $hash = hash('sha512', $password . $hash . $salt);
    return $hash;
}


switch($_POST['action']){
    case 'register':
        $email = $_POST['email'];
        $language = $_POST['language'];
        $account_type = $_POST['account_type'];
        $company_name = $_POST['ompany_name'];
        $count = $database->count("users", [
            "email" => $_POST['email']
        ]);

        if($count>0){
            echo '<div class="alert alert-danger">This email already exist.</div>';
        }else {
            $salt = getSalt();
            $token = getToken();
            $token_expiration = time() + 86400;

            $database->insert("users", [
                "email" => $_POST['email'],
                "salt" => $salt,
                "password" => getSaltedHash($_POST['password'], $salt),
                "token" => $token,
                "token_expiration" => $token_expiration,
                "verified" => 0,
                "account_type" => 0,
                "status" => 0
            ]);
            $subject = 'Safeink - user email verification';
            /*$body = '';*/
            $body = '
            <style type="text/css">
                h1{
                    font-family: Arial, Helvetica, sans-serif;
                    font-size: 24px;
                    font-weight: normal;
                }
                p{
                    font-family: Arial, Helvetica, sans-serif;
                    font-size: 12px;
                    padding: 7px 0px;
                }
                a{
                    font-family: Arial, Helvetica, sans-serif;
                    font-size: 12px;
                    padding: 7px 12px;
                    background-color: #1C84C6;
                    display: block;
                    color:#ffffff;
                    border-radius: 3px;
                }
            </style>
            <h1>Thark you for joining our system</h1>
            <p>Please verify your email address by clicking on the link bellow:</p>
            <p><a href="http://safeink.giftupp.com/login/' . $token . '">Verify your email address</a></p>
            <p>Attention please:</p>
            <p>This link will be expired at <b>' . date("m/d/Y H:i", $token_expiration) . '</b></p>
            <p>Best regards,</p>
            <p>Safeink Team.</p>
        ';

            // Always set content-type when sending HTML email
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

            // More headers
            $headers .= 'From: Safeink<info@giftupp.com>' . "\r\n";
            mail($_POST['email'], $subject, $body, $headers);
            echo '<div class="alert alert-success">
                    <b>Thark you for joining our system</b><br>
                    The verification email was sent to inserted address.<br>
                    Please follow the instruction in email body.
                 </div>';
        }
        break;
    case 'login':
        $email = $_POST['email'];
        $password = $_POST['password'];


        $count = $database->count("users", [
            "email" => $email
        ]);

        if($count==0){
            echo '<div class="alert alert-danger">This email doesn\'t associated with any account.</div>';
        }
        else {
            $datas = $database->select("users",
                                        "*",
                                        [
                                            "email" => $email
                                        ]);
            foreach($datas as $data)
            {
                $salt = $data["salt"];
                $real_password = $data["password"];
                $account_type = $data["account_type"];
            }
            $chasded = getSaltedHash($_POST['password'], $salt);
            if($chasded != $real_password){
                echo '<div class="alert alert-danger">Wrong password</div>';
            }
            else{
                if($account_type == 0){
                    echo 'choose_account::'.$data["user_id"];
                }
                else{
                    echo 'success';
                }
            }

        }
        break;
    case 'first_update':
        $database->update("users", [
            "account_type" => $_POST["type"],
            // Like insert, you can assign the serialization
            "lang" => $_POST["language"]
        ], [
            "user_id" => $_POST["user_id"]
        ]);
        $last_insert_id = $database->insert("companies", [
            "company_name" => $_POST["company_name"]
        ]);

        $database->insert("company_user", [
            "company_id" => $last_insert_id,
            "user_id" => $_POST["user_id"]
        ]);
        echo 'success';
        break;
}