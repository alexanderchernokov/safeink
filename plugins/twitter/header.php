<?php
if(!defined('IN_PRGM') || defined('IN_ADMIN')) return true;

$s = GetPluginSettings($pluginid);

# OLD: modpath: sdurl+"includes/classes/twitter/index.php?p='.$pluginid.'&t='.TIME_NOW.PrintSecureUrlToken().'",

sd_header_add(array(
  'js'  => array(
    'jquery.tweet.js',
  ),
  'other' => array('
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function() {
if(typeof jQuery.fn.tweet !== "undefined") {
  jQuery("#tweets'.$pluginid.'").tweet({
    twitter_url: "twitter.com",
    modpath: sdurl+"includes/classes/sd_twitter.php?p='.$pluginid.'&t='.TIME_NOW.PrintSecureUrlToken().'",
    username: '.(empty($s['feed_username'])?'null':'"'.addslashes($s['feed_username']).'"').',
    query: '.(empty($s['feed_query'])?'null':'"'.addslashes($s['feed_query']).'"').',
    favorites: '.(empty($s['user_favorites'])?'false':'true').',
    count: '.(empty($s['tweet_count'])?5:intval($s['tweet_count'])).',
    avatar_size: '.(empty($s['avatar_size'])?'null':intval($s['avatar_size'])).',
    retweets: '.(empty($s['include_retweets'])?'false':'true').',
    refresh_interval: '.(empty($s['refresh_interval'])?'null':intval($s['refresh_interval'])).',
    template: '.(empty($s['display_format'])?'null':'"'.addslashes($s['display_format']).'"').',
    intro_text: '.(empty($s['intro_text'])?'""':'"'.addslashes($s['intro_text']).'"').',
    outro_text: '.(empty($s['outro_text'])?'""':'"'.addslashes($s['outro_text']).'"').',
    join_text: '.(empty($s['join_text'])?'""':'"'.addslashes($s['join_text']).'"').',
    auto_join_text_default: '.(empty($s['auto_join_text_default'])?'null':'"'.addslashes($s['auto_join_text_default']).'"').',
    auto_join_text_ed: '.(empty($s['auto_join_text_ed'])?'null':'"'.addslashes($s['auto_join_text_ed']).'"').',
    auto_join_text_ing: '.(empty($s['auto_join_text_ing'])?'null':'"'.addslashes($s['auto_join_text_ing']).'"').',
    auto_join_text_reply: '.(empty($s['auto_join_text_reply'])?'null':'"'.addslashes($s['auto_join_text_reply']).'"').',
    auto_join_text_url: '.(empty($s['auto_join_text_url'])?'null':'"'.addslashes($s['auto_join_text_url']).'"').',
    loading_text: '.(empty($s['loading_text'])?'null':'"'.addslashes($s['loading_text']).'"').',
    date_text_just_now: '.(empty($s['date_text_just_now'])?'""':'"'.addslashes($s['date_text_just_now']).'"').',
    date_text_seconds: '.(empty($s['date_text_seconds'])?'""':'"'.addslashes($s['date_text_seconds']).'"').',
    date_text_a_minute: '.(empty($s['date_text_a_minute'])?'""':'"'.addslashes($s['date_text_a_minute']).'"').',
    date_text_minutes: '.(empty($s['date_text_minutes'])?'""':'"'.addslashes($s['date_text_minutes']).'"').',
    date_text_an_hour: '.(empty($s['date_text_an_hour'])?'""':'"'.addslashes($s['date_text_an_hour']).'"').',
    date_text_hours: '.(empty($s['date_text_hours'])?'""':'"'.addslashes($s['date_text_hours']).'"').',
    date_text_a_day: '.(empty($s['date_text_a_day'])?'""':'"'.addslashes($s['date_text_a_day']).'"').',
    date_text_days: '.(empty($s['date_text_days'])?'""':'"'.addslashes($s['date_text_days']).'"').',
    date_text_a_year: '.(empty($s['date_text_a_year'])?'""':'"'.addslashes($s['date_text_a_year']).'"').',
    date_text_years: '.(empty($s['date_text_years'])?'""':'"'.addslashes($s['date_text_years']).'"').',
    list: '.(empty($s['list'])?'null':'"'.addslashes($s['list']).'"').',
    list_id: '.(empty($s['list_id'])?'null':intval($s['list_id'])).'
  });
}
})
//]]>
</script>
')), false);
/*
*/
unset($s);