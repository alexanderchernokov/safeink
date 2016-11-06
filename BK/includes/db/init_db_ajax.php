<?php
/**
 * Created by PhpStorm.
 * User: alexander.c
 * Date: 10/10/2016
 * Time: 18:24
 */
require ('medoo.php');
$database = new medoo([
    // required
    'database_type' => 'mysql',
    'database_name' => 'safeink',
    'server' => 'localhost',
    'username' => 'root1',
    'password' => 'optionsweb1!',
    'charset' => 'utf8',

    // [optional]
    'port' => 3306,

    // [optional] Table prefix
    'prefix' => 'ink_',

    // [optional] driver_option for connection, read more from http://www.php.net/manual/en/pdo.setattribute.php
    'option' => [
        PDO::ATTR_CASE => PDO::CASE_NATURAL
    ]
]);