<?php
/**
 * Created by PhpStorm.
 * User: alexander.c
 * Date: 09/11/2016
 * Time: 17:40
 */

require_once ('config.php');
if(mysql_connect($database['server_name'],$database['username'],$database['password'],$database['name'])){
    mysql_query("SET NAMES utf8");

    switch($_POST['act']){
        case 'new':
            //if()
            //mysql_query("INSERT INTO `sd_payments` ()")
            print_r($_POST);
            break;
        case 'amounk':
            break;
    }
}
else{
    echo 'error';
}