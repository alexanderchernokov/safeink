<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

$DB->query("UPDATE {phrases} SET defaultphrase = 'Password must be a minimum of 4 characters.'
            WHERE varname = 'enter_valid_password' AND pluginid = 11 LIMIT 1");

// insert comments count
$DB->query("CREATE TABLE IF NOT EXISTS {comments_count} (
  `plugin_id` int(10) unsigned NOT NULL,
  `object_id` int(10) unsigned NOT NULL,
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  KEY `plugin_id` (`plugin_id`),
  KEY `object_id` (`object_id`),
  KEY `count` (`count`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");


$comments_md_arr = array();

$get_comments = $DB->query("SELECT pluginid, objectid, approved FROM {comments} WHERE approved = 1
                            ORDER BY pluginid ASC, objectid ASC");

while($comment_arr = $DB->fetch_array($get_comments))
{
  // $comments_md_arr[pluginid][objectid]
  if(isset($comments_md_arr[$comment_arr['pluginid']][$comment_arr['objectid']]))
  {
    $DB->query("UPDATE {comments_count} SET count = (count + 1)
                WHERE plugin_id = $comment_arr[pluginid] AND object_id = $comment_arr[objectid]");
  }
  else
  {
    $DB->query("INSERT INTO {comments_count} (plugin_id, object_id, count) VALUES
                ($comment_arr[pluginid], $comment_arr[objectid], 1)");

    $comments_md_arr[$comment_arr['pluginid']][$comment_arr['objectid']] = 1;
  }
}

?>