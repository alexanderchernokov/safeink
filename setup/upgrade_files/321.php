<?php
if(!defined('UPGRADING_PRGM') && !defined('INSTALLING_PRGM')) exit;

// ################################ Forum Plugin ##############################
if($forum_pluginid = $DB->query_first("SELECT pluginid FROM {plugins} WHERE name = 'Forum' LIMIT 1"))
{
  $forum_pluginid = $forum_pluginid[0];
}

if($forum_pluginid)
{
  // Reset Forum plugin settings for admin "Usergroups" display
  // (comments permission not usable for Forum)
  $DB->query("UPDATE {plugins} SET settings = 55, version = '3.2.1' where pluginid = %d",$forum_pluginid);

  // Add frontpage phrases
  InsertPhrase($forum_pluginid, 'confirm_move_topic',         'Really move topic(s) to selected forum?');
  InsertPhrase($forum_pluginid, 'confirm_merge_topics',       'Really merge topics into a single topic?');
  InsertPhrase($forum_pluginid, 'topic_options_merge_topics', 'Merge Topics');
  InsertPhrase($forum_pluginid, 'topic_options_move_topics',  'Move Topics');
  InsertPhrase($forum_pluginid, 'topic_options_move_topic',   'Move Topic');
  InsertPhrase($forum_pluginid, 'topic_options_stick_topic',  'Stick Topic');
  InsertPhrase($forum_pluginid, 'topic_options_unstick_topic', 'Unstick Topic');
  InsertPhrase($forum_pluginid, 'sticky',                     'Sticky');
  InsertPhrase($forum_pluginid, 'message_topic_moved',        'Topic moved.');
  InsertPhrase($forum_pluginid, 'message_topics_moved',       'Topics moved.');
  InsertPhrase($forum_pluginid, 'message_topics_merged',      'Topics merged.');
  InsertPhrase($forum_pluginid, 'message_topic_sticky',       'Topic was sticked.');
  InsertPhrase($forum_pluginid, 'message_topic_unsticky',     'Topic was unsticked.');
  InsertPhrase($forum_pluginid, 'message_topic_move_cancelled', 'Topic move operation cancelled.');
  InsertPhrase($forum_pluginid, 'message_topics_move_cancelled', 'Topic move operation cancelled.');
  InsertPhrase($forum_pluginid, 'message_topics_merged_cancelled', 'Topics merging operation cancelled.');
  InsertPhrase($forum_pluginid, 'message_topics_operation_cancelled', 'Topics operation cancelled.');
  InsertPhrase($forum_pluginid, 'message_topics_merge_hint', 'Please select the name of the resulting merged topic:');

  // Add plugin settings
  InsertPluginSetting($forum_pluginid, 'forum_display_settings', 'Display Sticky Topics', 'Enable display of sticky topics (display at top of list):', 'yesno', '1', 19);

  // Forum: topics can be configured as "sticky"
  $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'p_forum_topics', 'sticky',      'TINYINT(1)', 'NOT NULL DEFAULT 0');

}
