<?php

if(!defined('IN_PRGM')) return;

// Legacy functions from SD 2.x

function Comments($plugin_id, $object_id, $url = '', $open = false)
{
  if(empty($plugin_id) || empty($object_id)) return;

  global $Comments;
  $Comments->plugin_id = $plugin_id;
  $Comments->object_id = $object_id;
  $Comments->url = $url;
  $Comments->DisplayComments();
}

function DeleteComment($pluginid, $objectid, $url, $commentid, $open)
{
}

if(!function_exists('DisplayComments'))
{
  function DisplayComments($pluginid, $objectid, $url, $open = false)
  {
  }
}

if(!function_exists('DisplayCommentForm'))
{
  function DisplayCommentForm($pluginid, $objectid, $url)
  {
  }
}

function InsertComment($pluginid, $objectid, $username, $comment, $url, $open)
{
}

function UnapproveComment($pluginid, $objectid, $url, $commentid, $open)
{
}

function CheckForEmptyField($formvariable, $dochecking)
{
  return '';
}

function GetCommentsCount($pluginid, $objectid)
{
  // exactly the same code as in 2.6
  global $DB;

  if($count = $DB->query_first("SELECT COUNT(*) FROM {comments} WHERE pluginid = %d AND objectid = %d and approved = 1",
     $pluginid, $objectid))
  {
    return $count[0];
  }
  return 0;
}

function print_tiny_toggle_links($element,$assume_enabled=false)
{
  //nothing here as SD3's "PrintWysiwygElement" already does it
}

function sd_GetVar($var_name, $default_value = '', $type = 'string', $UsePost = true, $UseGet = true)
{
  // simply map it to SD3's "GetVar" function
  return GetVar($var_name, $default_value, $type, $UsePost, $UseGet);
}
