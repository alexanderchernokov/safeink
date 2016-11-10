<?php
session_start();
/**
 * Created by PhpStorm.
 * User: alexander.c
 * Date: 09/11/2016
 * Time: 17:40
 */

//require_once ('config.php');
$link = mysql_connect('localhost','root1','optionsweb1!');
if($link){
    mysql_select_db( 'safe' );
    mysql_query("SET NAMES utf8");

    switch($_POST['act']){
        case 'new':
            if($_POST['type'] == 1){
                $f = 'bayer_';
            }
            if($_POST['type'] == 2){
                $f = 'recipient_';
            }

            mysql_query("INSERT INTO `sd_payments` 
                                      (
                                        `payment_title`,`payment_description`,".$f."userid,`status`
                                      )
                         VALUES
                                      (
                                        '".$_POST['payment_title']."','".$_POST['payment_description']."',".$_POST['u'].",1
                                      )
                        ") or die(mysql_error());
            $id = mysql_insert_id();
            echo 'next::'.$id;
            break;
        case 'add_amount':
            break;
    }
}
else{
    echo 'error';
}