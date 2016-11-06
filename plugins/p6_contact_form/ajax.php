<?php
define('IN_PRGM', true);
define('ROOT_PATH', './../../');
require_once(ROOT_PATH . 'includes/init.php');


if(!Is_Ajax_Request())
{
	header("HTTP/1.0 400 Bad Request");
	exit;
}

sleep(1);

$response = array();

$action		= GetVar('action','','string');
$id			= GetVar('id','','int');
$key 		= GetVar('pk','','int');
$column		= GetVar('name','','string');
$value		= GetVar('value','','string');

// check if we're deleting
if($action == 'delete' && strlen($id) && $id > 4)
{
	// do not delete the first 4 ids as they are required
	$DB->query("DELETE FROM {p6_fields} WHERE id=$id");
	return;
}

// delete entry
if($action == 'deleteentry' && strlen($id))
{
	$DB->query("DELETE FROM {p6_submissions} WHERE id=$id");
	return;
}

// Check for inputs
if(!strlen($value) || !strlen($column) || !strlen($key))
{
	$response['status'] = '400';
	echo "cannot be empty";
	return;
}


// Update the database
$DB->query("UPDATE {p6_fields} SET $column='$value' WHERE id=$key");






