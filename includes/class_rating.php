<?php
/*
Based on: ColorRating (c) 2009 Jack Moore - jack@colorpowered.com
          Released under the MIT License.

*** Special adaptation for use in Subdreamer (requires SD 3.2.x!)

*** Rating (IP-based) is ONLY enabled for non-banned, logged-in users!

*** For usage within a SD plugin see function "PrintRatingForm" in file
*** "functions_global.php" for further instructions!

Translatable phrases are to be found under Main Website Phrases.

*/

class rating
{
  public $average = 0;
  public $votes = 0;
  public $status = '';
  public $rating_id = '';
  public $pluginid = 0;
  public $user_voted = false;
  private $rating_valid = false;

  function __construct($rating_id, $plugin_id = 0)
  {
    global $DB, $userinfo;

    if(isset($rating_id) && !strlen($rating_id))
    {
      return;
    }

    // sanitize rating_id for security (only latin alphabet, digits and underscore)
    if(!$this->rating_id = SanitizeInputForSQLSearch($rating_id))
    {
      return;
    }
    if(!preg_match("/^[p][0-9]+(-[0-9]+)?$/", $this->rating_id))
    {
      return;
    }
    $this->rating_id = $DB->escape_string(substr($rating_id,0,64));
    $this->rating_valid = true;
    $this->pluginid = (int)$plugin_id;

    // Check if the rating element identified by "$rating_id" is already present in DB.
    // ONLY if it exists, fetch the current average and counts.
    // The main element's row for it has "master" as IP and contains the current
    // voting average (in %):
    if($master = $DB->query_first("SELECT r.rating,".
                                  " (SELECT COUNT(*) FROM ".PRGM_TABLE_PREFIX."ratings r2".
                                  "  WHERE r2.rating_id = r.rating_id".
                                  "  AND r2.user_ip != 'master') rating_count,".
                                  " (SELECT COUNT(*) FROM ".PRGM_TABLE_PREFIX."ratings r3".
                                  "  WHERE r3.rating_id = r.rating_id".
                                  "  AND r3.user_id = ". $userinfo['userid'].
                                  "  AND r3.user_ip != 'master') user_voted".
                                  " FROM ".PRGM_TABLE_PREFIX."ratings r".
                                  " WHERE r.rating_id = '%s'".
                                  " AND r.user_ip = 'master' LIMIT 1",
                                  $rating_id))
    {
      $this->average = $master['rating'];
      $this->votes = $master['rating_count'];
      $this->user_voted = !empty($master['user_voted']);
    }
    else
    {
      // Rating element not found, insert a master row for it:
      $DB->query("INSERT INTO {ratings} (rating_id, user_id, user_ip, rating, pluginid, rating_time)".
                 " VALUES ('%s', 0, 'master', 0, %d, %d)",
                 $this->rating_id, $this->pluginid, time());
    }

  } //constructor

  // ##########################################################################

  function is_valid()
  {
    return $this->rating_valid;
  }

  // ##########################################################################

  function already_voted()
  {
    global $DB, $userinfo;

    if(!$this->rating_valid) return false;
    return $this->user_voted;
  }

  // ##########################################################################

  function set_rating($score, $ip)
  {
    global $DB, $sdlanguage, $userinfo;

    if(!$this->rating_valid) return false;

    if(!$this->already_voted())
    {
      $DB->query('INSERT INTO {ratings} (rating_id, user_id, user_ip, rating, pluginid, rating_time)'.
                 " VALUES ('%s', %d, '%s', %d, %d, %d)",
                 $this->rating_id, $userinfo['userid'], USERIP, $score, $this->pluginid, time());
      $this->votes++;

      // Fetch total rating value and count of votes:
      $all_ratings = $DB->query_first('SELECT SUM(rating) `total`, COUNT(DISTINCT user_id) `quantity`'.
                                      ' FROM {ratings} '.
                                      " WHERE rating_id = '%s' AND user_ip != 'master'",
                                      $this->rating_id);
      if($all_ratings && !empty($all_ratings[0]))
      {
        // Store the average rating in the master row (in %)
        // 1 star == 20%
        $this->average = round((($all_ratings['total']*20) / $all_ratings['quantity']),0);
        $DB->query("UPDATE {ratings} SET rating = %s".
                   " WHERE rating_id = '%s' AND user_ip = 'master'",
                   number_format($this->average,2,'.',''), $this->rating_id);
        $this->status = $sdlanguage['rating_thanks'];
      }
    }
    else
    {
      $this->status = $sdlanguage['rating_already_rated'];
    }

  } //set_rating

} //end of class
