<?php
session_start();
/**
 * Created by PhpStorm.
 * User: alexander.c
 * Date: 10/10/2016
 * Time: 15:44
 */
if(isset($_SESSION['user_id']) AND $_SESSION['user_id'] != ''){}
else{
    header("Location:register.php");
}