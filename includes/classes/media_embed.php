<?php
/*************************************************************
 * This script is developed by Arturs Sosins aka ar2rsawseen, http://webcodingeasy.com
 * Feel free to distribute and modify code, but keep reference to its creator
 *
 * Media Embed class allows you to retrieve information about media like Video or Images
 * by simply using link or embed code from media providers like Youtube, Myspace, etc.
 * It can retrieve embeding codes, title, sizes and thumbnails from
 * more than 20 popular media providers
 *
 * For more information, examples and online documentation visit:
 * http://webcodingeasy.com/PHP-classes/Get-information-about-video-and-images-from-link
**************************************************************/
class media_embed
{
  private $code = '';
  private $site = '';
  private $source = ''; //SD: the source URL
  private $source_err = false; //SD: curl error on source?
  private $source_err_msg = false; //SD: error text
  private $data = array(
    'small' => '',
    'medium' => '',
    'large' => '',
    'w' => -1,
    'h' => -1,
    'embed' => '',
    'iframe' => '',
    'url' => '',
    'site' => '',
    'title' => '',
    //SD: added below
    'description' => '',
    'tags' => '',
    'duration' => 0,
    'object_html' => false,
  );
  private $default_size = array('w' => 425, 'h' => 335);
  private $supported_sites = array(); //SD
  private $all_types = array(
    "revision" => array(
      'link' => '/https?:\/\/revision3\.com\/(.*)/is',
    ),
    "google" => array(
      'link' => "/https?:\/\/video\.google\.(?:com|com\.au|co\.uk|de|es|fr|it|nl|pl|ca|cn)\/videoplay\?docid=([^&#]*)/i",
      'embed' => "/https?:\/\/video\.google\.(?:com|com\.au|co\.uk|de|es|fr|it|nl|pl|ca|cn)\/videoplay\?docid=([^&#]*)/is",
      'iframe' => "/https?:\/\/video\.google\.(?:com|com\.au|co\.uk|de|es|fr|it|nl|pl|ca|cn)\/videoplay\?docid=([^&#]*)/is"
    ),
    "liveleak" => array(
      'link' => "/https?:\/\/[w\.]*liveleak\.com\/view\?i=([^&#]*)/i",
      'iframe' => "/https?:\/\/[w\.]*liveleak\.com\/ll_embed\?f=([^&#]*)/is"
    ),
    "youtube" => array(
      //SD: support locale domains, 'image' and "youtu.be"
      #OLD: "link" => "/https?:\/\/[w\.]*youtube\.com\/watch\?v=([^&#]*)|https?:\/\/[w\.]*youtube\.com\/watch\?[^&]+&v=([^&#]*)|https?:\/\/[w\.]*youtu\.be\/([^&#]*)/i",
      'link' => "~https?:\/\/(?:[w\.]*youtu\.be\/([^&#]*))|(?:video\.google\.(?:com|com\.au|co\.uk|de|es|fr|it|nl|pl|ca|cn)/(?:[^\"]*?))?(?:https?:\/\/[w\.]*youtu\.be\/([^&#]*)?)?(?:(?:www|au|br|ca|es|fr|de|hk|ie|in|il|it|jp|kr|mx|nl|nz|pl|ru|tw|uk)\.)?youtube\.com(?:[^\"]*?)?(?:&|&amp;|/|\?|;|\%3F|\%2F)(?:video_id=|v(?:/|=|\%3D|\%2F))([0-9a-z-_]{11})~imu",
      'embed' => '/https?:\/\/[w\.]*youtube\.com\/v\/([^?&#"\']*)/is',
      'iframe' => '/https?:\/\/[w\.]*youtube\.com\/embed\/([^?&#"\']*)/is',
      'image' => '~http://img\.youtube\.com/vi/$2/0\.jpg~'
    ),
    "vimeo" => array(
      'link' => "/https?:\/\/[w\.]*vimeo\.com\/([\d]*)/is",
      'embed' => '/https?:\/\/[w\.]*vimeo\.com\/moogaloop\.swf\?clip_id=([\d]*)/is',
      'iframe' => '/https?:\/\/player\.vimeo\.com\/video\/([\d]*)/is'
    ),
    "facebook" => array(
      'link' => "/https?:\/\/[w\.]*facebook\.com\/video\/video\.php\?v=([\d]*)|https?:\/\/[w\.]*facebook\.com\/photo\.php\?v=([\d]*)/is",
      'embed' => '/https?:\/\/[w\.]*facebook\.com\/v\/([\d]*)/is'
    ),
    "dailymotion" => array(
      'link' => "/https?:\/\/[w\.]*dailymotion\.com\/video\/([^_]*)/is",
      'embed' => '/https?:\/\/[w\.]*dailymotion\.com\/swf\/video\/([^?&#"\']*)/is',
      'iframe' => '/https?:\/\/[w\.]*dailymotion\.com\/embed\/video\/([^?&#"\']*)/is'
    ),
    "myspace" => array(
      'link' => "/https?:\/\/[w\.]*myspace\.com\/.*\/video\/.*\/([\d]*)|https?:\/\/[w\.]*myspace\.com\/video\/vid\/([\d]*)/is",
      'embed' => '/https?:\/\/mediaservices\.myspace\.com\/services\/media\/embed\.aspx\/m=([\d]*)|https?:\/\/player\.hulu\.com\/embed\/myspace_player_v002\.swf\?pid=\d*&embed=true&videoID=([\d]*)/is'
    ),
    "metacafe" => array(
      'link' => '/https?:\/\/[w\.]*metacafe\.com\/watch\/([^?&#"\']*)/is',
      'embed' => '/https?:\/\/[w\.]*metacafe\.com\/fplayer\/(.*).swf/is'
    ),
    "revver" => array(
      'link' => '/https?:\/\/[w\.]*revver\.com\/video\/(.*)/is',
      'embed' => '/https?:\/\/flash\.revver\.com\/player\/\d\.\d\/player\.swf\?mediaId=([\d]*)|https?:\/\/flash\.revver\.com\/player\/\d\.\d\/player\.js\?mediaId:([\d]*)|https?:\/\/media\.revver\.com\/qt\/([\d]*)\.mov|https?:\/\/media\.revver\.com\/player\/\d\.\d\/qtplayer.js\?mediaId:([\d]*)/is'
    ),
    "aol" => array( //SD: "5min" redirects to aol now
      'link' => '/https?:\/\/on\.aol\.com\/video\/([^?&#"\']*)/is',
      'embed' => '/https?:\/\/embed\.aol\.com\/([\d]*)/is'
    ),
    "clikthrough" => array(
      'link' => '/https?:\/\/[w\.]*clikthrough\.com\/theater\/video\/([\d]*)/is',
      'embed' => '/https?:\/\/[w\.]*clikthrough\.com\/clikPlayer\.swf\?videoId=([\d]*)/is'
    ),
    "dotsub" => array(
      'link' => '/https?:\/\/[w\.]*dotsub\.com\/view\/([^\?\/&#]*)/is',
      'iframe' => '/https?:\/\/[w\.]*dotsub\.com\/media\/(.*)\/e/is'
    ),
    "videojug" => array(
      'link' => '/https?:\/\/[w\.]*videojug\.com\/film\/([^?]*)/is',
    ),
    "blip" => array(
      //SD: fetch from page does not work, so allow official shortcode from 'embed'
      'link' => '/https?:\/\/[w\.]*blip\.tv\/([^?]*)/is',
      #'link' => '/https?:\/\/[w\.]*blip\.tv\/play\/([^?]*)/is',
      'embed' => '/https?:\/\/a\.blip\.tv\/api.swf#([^?]*)/is',
    ),
    "viddler" => array(
      'link' => '/https?:\/\/[w\.]*viddler\.com\/explore\/([^?]*)/is',
    ),
    "screenr" => array(
      'link' => '/https?:\/\/[w\.]*screenr\.com\/([^?]*)/is',
    ),
    "slideshare" => array(
      'link' => '/https?:\/\/[w\.]*slideshare\.net\/([^?]*)/is',
    ),
    "hulu" => array(
      'link' => '/https?:\/\/[w\.]*hulu\.com\/watch\/([^?]*)/is',
    ),
    "qik" => array(
      'link' => '/https?:\/\/[w\.]*qik\.com\/video\/([^?]*)/is',
    ),
    "flickr" => array(
      'link' => '/https?:\/\/[w\.]*flickr\.com\/photos\/([^?]*)/is',
    ),
    "funnyordie" => array(
      'link' => '/https?:\/\/[w\.]*funnyordie\.com\/videos\/([^?]*)/is',
    ),
    "twitpic" => array(
      'link' => '/https?:\/\/[w\.]*twitpic\.com\/([^?]*)/is',
    ),
    "yfrog" => array(
      #OLD: 'link' => '/https?:\/\/[w\.]*yfrog\.[^\/]*\/([^?]*)/is',
      'link' => '/https?:\/\/[w\.]*yfrog\.com\/([^?]*)/is',
    ),
    "break" => array(
      'link' => '/https?:\/\/[w\.]*break\.com\/video\/([^?&#"\']*)/is',
    ),
    "pixelbark" => array( //SD
      'link' => '/https?:\/\/[w\.]*pixelbark\.com\/([\d]*)\/([^?&#"\']*)/is',
      'embed' => '/https?:\/\/[w\.]*pixelbark\.com\/([\d]*)/is'
    ),
    "soundcloud" => array( //SD
      'link' => '/https?:\/\/[w\.]*soundcloud\.com\/([^?&#"\']*)/is',
    ),
    "sevenload" => array( //SD
      'link' => '/https?:\/\/(?:www|en|de)*\.?sevenload\.com\/(videos|episodes)\/([^?&#"\']*)/is',
    ),
  );

  function __construct($input='')
  {
    //SD: moved code to new "set_site()" function
    $this->set_site($input);
    $this->supported_sites = array_keys($this->all_types);
  }

  /**************************
  * PUBLIC FUNCTIONS
  **************************/

  public function clear_data() //SD
  {
    $this->code = '';
    $this->site = '';
    $this->source = '';
    $this->source_err = false;
    $this->source_err_msg = false;
    $this->data = array(
      'small' => '',
      'medium' => '',
      'large' => '',
      'w' => -1,
      'h' => -1,
      'embed' => '',
      'iframe' => '',
      'url' => '',
      'site' => '',
      'title' => '',
      //SD added below:
      'description' => '',
      'tags' => '',
      'duration' => 0,
      'object_html' => false,
    );
  }

  public function get_current_data() //SD
  {
    return $this->data;
  }

  public function set_site($input='') //SD
  {
    $this->clear_data();

    if(empty($input)) return false;

    foreach($this->all_types as $site => $types)
    {
      foreach($types as $type => $regexp)
      {
        if(@preg_match($regexp, $input, $match) && !empty($match))
        {
          $this->data['matches'] = $match; //SD
          #SD: return farthest match, not first!
          #for($i = 1; $i < sizeof($match); $i++)
          for($i = sizeof($match)-1; $i >= 0; $i--)
          {
            if($match[$i] != '')
            {
              $this->code = $match[$i];
              $this->site = $site;
              $this->source = $input;
              return true;
            }
          }
        }
      }
    }
    return false;
  }

  public function get_SupportedSites() //SD
  {
    return $this->supported_sites;
  }

  public function get_vendor() //SD
  {
    return $this->site;
  }

  public function get_info($site='') //SD
  {
    if(empty($this->site) || !isset($this->all_types[$this->site])) return false;
    $fname = $this->site .'_getinfo';
    if(method_exists($this,$fname))
    {
      return $this->$fname();
    }
    return false;
  }

  public function set_sizes($w,$h) //SD
  {
    $this->default_size = array('w' => (int)$w, 'h' => (int)$h);
  }

  public function get_thumb($size = 'small')
  {
    if($this->site != '')
    {
      $size_types = array('small'=>0, 'medium'=>1, 'large'=>2);
      $size = strtolower($size);
      if(!isset($size_types[$size]))
      {
        $size = 'small';
      }
      $this->prepare_data('thumb');
      if(isset($this->data[$size])) //SD
      return $this->data[$size];
    }
    return '';
  }

  public function get_iframe($w = -1, $h = -1){
    $this->prepare_data('iframe');
    if(($this->site != '') && !empty($this->data['iframe']))
    {
      if(!empty($this->data['object_html'])) return $this->data['object_html']; //SD

      if($w < 0 || $h < 0)
      {
        $w = (is_int($this->data["w"]) && $this->data["w"] > 0) ? $this->data["w"] : $this->default_size["w"];
        $h = (is_int($this->data["h"]) && $this->data["h"] > 0) ? $this->data["h"] : $this->default_size["h"];
      }
      if(substr($this->data['iframe'],0,12)=='<object id="') return $this->data['iframe']; //SD
      return '<iframe width="'.$w.'" height="'.$h.'" src="'.$this->data['iframe'].'" frameborder="0" allowfullscreen></iframe>';
    }
    return '';
  }

  public function get_embed($w = -1, $h = -1){
    $this->prepare_data('embed');
    if(($this->site != '') && !empty($this->data['embed']))
    {
      if(!empty($this->data['object_html'])) return $this->data['object_html']; //SD

      if($w < 0 || $h < 0)
      {
        $w = (is_int($this->data["w"]) && $this->data["w"] > 0) ? $this->data["w"] : $this->default_size["w"];
        $h = (is_int($this->data["h"]) && $this->data["h"] > 0) ? $this->data["h"] : $this->default_size["h"];
      }
      #&amp;wmode=transparent
      return '<object width="'.$w.'" height="'.$h.'" tabindex="-1" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000">'.
        '<param name="movie" value="'.$this->data['embed'].'" />'.
        '<param name="allowFullScreen" value="true" /><param name="allowscriptaccess" value="always" />'.
        '<embed src="'.$this->data['embed'].'" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="'.$w.'" height="'.$h.'" /></object>';
    }
    return '';
  }

  public function get_url(){
    if($this->site != '')
    {
      $this->prepare_data('url');
      return $this->data['url'];
    }
    return '';
  }

  public function get_id()
  {
    return $this->code;
  }

  public function get_site()
  {
    $this->prepare_data('site');
    return (isset($this->data['site'])?$this->data['site']:'');
  }

  public function get_size(){
    $arr = array();
    $this->prepare_data('size');
    $arr["w"] = ($this->data["w"] < 0) ? $this->default_size["w"] : $this->data["w"];
    $arr["h"] = ($this->data["h"] < 0) ? $this->default_size["h"] : $this->data["h"];
    return $arr;
  }

  public function get_title(){
    $this->prepare_data('title');
    return $this->data['title'];
  }

  /**************************
  * PRIVATE FUNCTIONS
  **************************/
  private function get_data($url)
  {
    if(!empty($this->source_err)) return '';
    //SD: error checking for curl
    if(!function_exists('curl_init') || (false === ($curl = @curl_init())))
    {
      $this->source_err = true;
      $this->source_err_msg = 'cURL not installed';
      return '';
    }

    @curl_setopt($curl, CURLOPT_URL, $url);
    @curl_setopt($curl, CURLOPT_HEADER, 0);
    #@curl_setopt($curl, CURLOPT_FAILONERROR, 1); #DON'T USE THIS!
    @curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    @curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    @curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
    @curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    /*
    if(defined('IPADDRESS') && (IPADDRESS=='127.0.0.1'))
    {
      @curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      @curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    */
    @curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:16.0) Gecko/20100101 Firefox/16.0");
    if(!$curlData = @curl_exec($curl))
    {
      $this->source_err = true;
      $this->source_err_msg = @curl_error($curl);
    }
    @curl_close($curl);
    return ($this->source_err?'':$curlData);
  }

  private function prepare_data($type)
  {
    if($this->site != '')
    {
      $ready = false;
      switch($type)
      {
        case 'size':
          if($this->data["w"] > 0 && $this->data["h"] > 0)
          {
            $ready = true;
          }
        break;
        case 'thumb':
          if($this->data['small'] != '' && $this->data['medium'] != '' && $this->data['large'] != '')
          {
            $ready = true;
          }
        break;
        default:
        if($this->data[$type] != '')
        {
          $ready = true;
        }
      }
      //if information is not yet loaded
      if(!$ready)
      {
        $func = $this->site.'_data';
        $arr = $this->$func();
        //check if information requires http request
        if(!$arr[$type])
        {
          //if not, just provide data
          $func = ($this->site).'_'.$type;
          $this->aggregate($this->$func(), $type);
        }
        else
        {
          //else if it needs http request we may as well load all other data
          //so we won't need to request it again
          $req = $this->site.'_req';
          $res = $this->get_data($this->$req());
          foreach($arr as $key => $val)
          {
            $func = ($this->site)."_".$key;
            if($val)
            {
              $this->aggregate($this->$func($res), $key);
            }
            else
            {
              $this->aggregate($this->$func(), $key);
            }
          }
        }
      }
    }
  }

  private function aggregate($data, $type)
  {
    if(is_array($data))
    {
      foreach($data as $key => $val)
      {
        $this->data[$key] = $val;
      }
    }
    else
    {
      $this->data[$type] = $data;
    }
  }

  /**************************
  * SOME STANDARDS
  **************************/
  //oembed functions
  private function _oembed_size($res='')
  {
    $arr = array();
    if(empty($res)) return $arr;
    $res = json_decode($res, true);
    if(!empty($res) && is_array($res) && isset($res['width']) && isset($res['height']))
    {
      $arr['w'] = (int)$res['width'];
      $arr['h'] = (int)$res['height'];
    }
    return $arr;
  }

  private function _oembed_data($type, $res='')
  {
    if(empty($type) || empty($res)) return '';
    $res = json_decode($res, true);
    if(!empty($res) && is_array($res) && isset($res[$type]))
    {
      return $res[$type];
    }
    return '';
  }

  // *** og functions ***
  private function _og_size($res='')
  {
    if(empty($res)) return '';
    $arr = array();
    if(@preg_match( '/property="og:video:width"\s*content="([\d]*)/i', $res, $match))
    if(!empty($match))
    {
      $arr['w'] = (int)$match[1];
    }
    if(@preg_match( '/property="og:video:height"\s*content="([\d]*)/i', $res, $match))
    if(!empty($match))
    {
      $arr['h'] = (int)$match[1];
    }
    return $arr;
  }

  private function _og_data($attr,$input='')
  {
    if(empty($input) || empty($attr)) return '';
    $ret = '';
    if(preg_match('/property="og:'.preg_quote($attr,'/').'"\s*content="([^"]*)"/i', $input, $match))
    if(!empty($match))
    {
      $ret = $match[1];
    }
    return $ret;
  }

  private function _og_title($res='')
  {
    if(empty($res)) return '';
    return $this->_og_data('title',$res);
  }

  private function _og_video($res='')
  {
    if(empty($res)) return '';
    return $this->_og_data('video',$res);
  }

  private function _og_description($res='') //SD
  {
    if(empty($res)) return '';
    return $this->_og_data('description',$res);
  }

  private function _og_image($res='') //SD
  {
    if(empty($res)) return '';
    return $this->_og_data('image',$res);
  }

  private function _og_url($res='') //SD
  {
    if(empty($res)) return '';
    return $this->_og_data('url',$res);
  }

  private function _twitter_sizes($res) //SD
  {
    $arr = array();
    $arr['w'] = 0;
    $arr['h'] = 0;
    if(empty($res)) return $arr;
    if(preg_match('/twitter:image:width"\s*value="([^"]*)"/i', $res, $match))
    if(!empty($match))
    {
      $arr['w'] = (int)$match[1];
    }
    if(preg_match('/twitter:image:height"\s*value="([^"]*)"/i', $res, $match))
    if(!empty($match))
    {
      $arr['h'] = (int)$match[1];
    }
    return $arr;
  }

  private function _video_tag($res) //SD
  {
    if(empty($res)) return '';
    return $this->_meta_property('video:tag',$res);
  }

  private function _meta_name($type,$res='') //SD
  {
    if(empty($type) || empty($res)) return '';
    if(preg_match('/meta name="'.preg_quote($type,'/').'"\s*content="([^"]*)"/i', $res, $match))
    if(!empty($match) && (count($match) > 1))
    {
      return $match[1];
    }
    return '';
  }

  private function _meta_property($type,$res='') //SD
  {
    if(empty($type) || empty($res)) return '';
    if(preg_match('/meta property="'.preg_quote($type,'/').'"\s*content="([^"]*)"/i', $res, $match))
    if(!empty($match) && (count($match) > 1))
    {
      return $match[1];
    }
    return '';
  }

  // *** others ***
  private function _link2title(){
    $title = '';
    if(false !== ($parts = explode('/', $this->code)))
    if(isset($parts[1]))
    {
      if(false !== ($parts = explode('_', $parts[1])))
      {
        foreach($parts as $key => $val)
        {
          $parts[$key] = ucfirst($val);
        }
        $title = implode(' ', $parts);
      }
    }
    return $title;
  }

  /**************************
  * YOUTUBE FUNCTIONS
  **************************/

  //which data needs additional http request
  private function youtube_data(){
    return  array(
      'thumb' => false,
      'size' => true,
      'embed' => false,
      'iframe' => false,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function youtube_req(){
    return $this->youtube_url();
  }
  //return thumbnails
  private function youtube_thumb(){
    $size_types = array('small' => 'default', 'medium' => 'hqdefault', 'large' => 'hqdefault');
    $arr = array();
    foreach($size_types as $key => $val)
    {
      $arr[$key] = 'http://i.ytimg.com/vi/'.$this->code.'/'.$val.'.jpg';
    }
    return $arr;
  }
  //return size
  private function youtube_size($res){
    return $this->_og_size($res);
  }
  //return iframe url
  private function youtube_iframe(){
    return $this->youtube_site().'/embed/'.$this->code;
  }
  //return embed url
  private function youtube_embed(){
    return $this->youtube_site().'/v/'.$this->code;#.'&autoplay=1';
  }
  //return canonical url
  private function youtube_url(){
    return $this->youtube_site().'/watch?v='.$this->code;
  }
  //return website url
  private function youtube_site(){
    return 'http://www.youtube.com';
  }
    //return title
  private function youtube_title($res){
    $this->data['description'] = '';
    $this->data['tags'] = '';
    $this->data['duration'] = 0;
    if(empty($res)) return '';
    $this->data['description'] = $this->youtube_description($res); //SD
    $this->data['tags'] = $this->_meta_name('keywords',$res); //SD
    $this->youtube_getinfo();
    return $this->_og_title($res);
  }
  private function youtube_getinfo(){ //SD
    if(empty($this->site) || empty($this->code)) return '';
    if(!$content = $this->get_data('http://youtube.com/get_video_info?video_id='.$this->code)) return;
    @parse_str($content, $arr);
    if(empty($arr)) return;
    #$this->data['title'] = isset($arr['title']) ? $arr['title'] : '';
    $this->data['duration']= isset($arr['length_seconds']) ? intval($arr['length_seconds']) : 0;
    $this->data['author'] = isset($arr['author']) ? $arr['author'] : '';
    $this->data['views']  = isset($arr['view_count']) ? intval($arr['view_count']) : 0;
    $this->data['tags']   = isset($arr['keywords']) ? trim($arr['keywords']) : '';
    $this->data['ratings_allowed'] = !empty($arr['allow_ratings']);
    $this->data['rating'] = isset($arr['avg_rating']) ? (float)$arr['avg_rating'] : 0;
    $this->data['allow_embed'] = !empty($arr['allow_embed']);
  }
  private function youtube_description($res){ //SD
    if(empty($res)) return '';

    // YouTube: dirty way to extract full description from HTML :(
    if(preg_match('#<div id="watch-description-text">(.*)<\/div>#si', $res, $match))
    if(!empty($match) && (count($match) > 1))
    {
      $code = trim(substr($match[1],0,strpos($match[1],'</div')));
      $code = preg_replace('/<p id="eow-description"\s+>/i','',$code);
      $code = preg_replace("/\r|\n/"," ",$code);
      $code = str_replace('<br />',"\n",$code);
      $code = trim(strip_tags($code));
      return $code;
    }

    // Fallback to regular function
    return $this->_og_description($res);
  }

  /**************************
  * VIMEO FUNCTIONS
  **************************/

  //which data needs additional http request
  private function vimeo_data(){
    return array(
      'thumb' => true,
      'size' => true,
      'embed' => false,
      'iframe' => false,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function vimeo_req(){
    return 'http://vimeo.com/api/v2/video/'.$this->code.'.json';
  }
  //return thumbnails
  private function vimeo_thumb($res){
    $arr = array();
    if(empty($res)) return $arr;
    $res = json_decode($res, true);
    if(!empty($res) && is_array($res))
    {
      $res = current($res);
      $sizes = array('small', 'medium', 'large');
      foreach($sizes as $val)
      {
        $arr[$val] = $res['thumbnail_'.$val];
      }
    }
    return $arr;
  }
  //return size
  private function vimeo_size($res){
    $arr = array();
    if(empty($res)) return $arr;
    $res = json_decode($res, true);
    if(!empty($res) && is_array($res))
    {
      $res = current($res);
      $arr['w'] = (isset($res['width'])  ? (int)$res['width']  : -1);
      $arr['h'] = (isset($res['height']) ? (int)$res['height'] : -1);
    }
    return $arr;
  }
  //return title
  private function vimeo_title($res){
    $this->data['description'] = '';
    $this->data['duration'] = 0;
    $this->data['tags'] = '';
    if(empty($res)) return '';
    $title = '';
    $res = json_decode($res, true);
    if(!empty($res) && is_array($res))
    {
      $res = current($res);
      $title = (isset($res['title']) ? (string)$res['title'] : '');
      //SD: fill extra data
      $this->data['description'] = (isset($res['description']) ? (string)$res['description'] : 0);
      $this->data['tags']        = (isset($res['tags']) ? (string)$res['tags'] : '');
      $this->data['duration']    = (isset($res['duration']) ? (int)$res['duration'] : 0);
    }
    return $title;
  }
  //return iframe link
  private function vimeo_iframe(){
    return 'http://player.vimeo.com/video/'.$this->code;
  }
  //return embed url
  private function vimeo_embed(){
    return 'http://vimeo.com/moogaloop.swf?clip_id='.$this->code;
  }
  //return canonical url
  private function vimeo_url(){
    return $this->vimeo_site().'/'.$this->code;
  }
  //return website url
  private function vimeo_site(){
    return 'http://www.vimeo.com';
  }

  /**************************
  * liveleak FUNCTIONS
  **************************/

  //which data needs additional http request
  private function liveleak_data(){
    return array(
      'thumb' => true,
      'size' => false,
      'embed' => false,
      'iframe' => true,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function liveleak_req(){
    return 'http://www.liveleak.com/view?i='.$this->code;
  }
  //return thumbnails
  private function liveleak_thumb($res=''){
    $arr = array();
    if(empty($res)) return $arr;
    $arr['medium'] = $this->_og_image($res);
    $arr['large'] = $arr['medium'];
    $arr['small'] = $arr['medium'];
    return $arr;
  }
  //return size
  private function liveleak_size(){
    return array('w' => 640, 'h' => 380);
  }
  //return title
  private function liveleak_title($res=''){
    $this->data['description'] = '';
    $this->data['duration'] = 0;
    $this->data['tags'] = '';
    if(empty($res)) return '';
    $this->data['description'] = $this->_og_description($res);
    return $this->_og_title($res);
  }
  //return iframe link
  private function liveleak_iframe($res=''){
    if(empty($res)) return '';
    #The embed code is different from the code in the URL... :(
    #Find it in 'generate_embed_code_generator_html()' in page source:
    if(preg_match("/generate_embed_code_generator_html\('([^']*)'\)/i", $res, $match))
    if(!empty($match))
    {
      return 'http://www.liveleak.com/ll_embed?f='.$match[1];
    }
    return '';
  }
  //return embed url
  private function liveleak_embed(){
    return '';
  }
  //return canonical url
  private function liveleak_url(){
    return $this->liveleak_site().'/view?i='.$this->code;
  }
  //return website url
  private function liveleak_site(){
    return 'http://www.liveleak.com';
  }

  /**************************
  * FACEBOOK FUNCTIONS
  **************************/
  //which data needs additional http request
  private function facebook_data(){
    return array(
      'thumb' => true,
      'size' => true,
      'embed' => false,
      'iframe' => false,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function facebook_req(){
    return $this->facebook_url();
  }
  //return thumbnails
  private function facebook_thumb($res){
    $arr = array();
    if(preg_match( '/thumbnail_src(.*)_b\.jpg/i', $res, $match))
    if(!empty($match) && (count($match)>1))
    {
      $arr['large'] = str_replace("\u00253A", ":", str_replace("\u00252F", '/', str_replace("\u00255C", '', str_replace("\u002522\u00253A\u002522", '', $match[1])))).'_b.jpg';
      $arr['medium'] = $arr['large'];
      $arr['small'] = str_replace('_b.jpg', '_t.jpg', $arr['large']);
    }
    return $arr;
  }
  //return size
  private function facebook_size($res){
    $arr = array();
    if(preg_match( '/\["width",\s*"([\d]*)"\]/i', $res, $match))
    if(!empty($match) && (count($match)>1))
    {
      $arr['w'] = (int)$match[1];
    }
    if(preg_match( '/\["height",\s*"([\d]*)"\]/i', $res, $match))
    if(!empty($match) && (count($match)>1))
    {
      $arr['h'] = (int)$match[1];
    }
    return $arr;
  }
  //return iframe url
  private function facebook_iframe(){
    return $this->facebook_site().'/v/'.$this->code;
  }
  //return embed url
  private function facebook_embed(){
    return $this->facebook_iframe();
  }
  //return canonical url
  private function facebook_url(){
    return $this->facebook_site().'/video/video.php?v='.$this->code;
  }
  //return website url
  private function facebook_site(){
    return 'http://www.facebook.com';
  }
  //return title
  private function facebook_title($res){
    $title = '';
    if(preg_match( '/<h2 class="uiHeaderTitle">([^<]*)<\/h2>/i', $res, $match))
    if(!empty($match) && (count($match)>1))
    {
      $title = $match[1];
    }
    return $title;
  }

  /**************************
  * DAILYMOTION FUNCTIONS
  **************************/
  //which data needs additional http request
  private function dailymotion_data(){
    return array(
      'thumb' => true,
      'size' => true,
      'embed' => false,
      'iframe' => false,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function dailymotion_req(){
    //SD: switched to full video page to also get description
    #return 'http://www.dailymotion.com/services/oembed?format=json&url='.urlencode($this->dailymotion_url());
    return $this->dailymotion_url();
  }
  //return thumbnails
  private function dailymotion_thumb($res){
    $arr = array();
    $arr['small']  = $this->dailymotion_site().'/thumbnail/160x120/video/'.$this->code;
    $arr['medium'] = $this->dailymotion_site().'/thumbnail/320x240/video/'.$this->code;
    $arr['large']  = $arr['medium'];
    /*
    $res = json_decode($res, true);
    if(!empty($res) && is_array($res))
    {
      $arr['large'] = (isset($res['thumbnail_url']) ? $res['thumbnail_url'] : '');
      $arr['medium'] = str_replace('large', 'medium', $arr['large']);
      $arr['small'] = str_replace('large', 'small', $arr['large']);
    }
    */
    return $arr;
  }
  //return size
  private function dailymotion_size($res){
    #return $this->_oembed_size($res);
    return $this->_og_size($res);
  }
  //return iframe url
  private function dailymotion_iframe(){
    return $this->dailymotion_site().'/embed/video/'.$this->code;
  }
  //return embed url
  private function dailymotion_embed(){
    return $this->dailymotion_site().'/swf/video/'.$this->code;
  }
  //return canonical url
  private function dailymotion_url(){
    return $this->dailymotion_site().'/video/'.$this->code;
  }
  //return website url
  private function dailymotion_site(){
    return 'http://www.dailymotion.com';
  }
  //return title
  private function dailymotion_title($res){
    $this->data['tags'] = '';
    $this->data['description'] = '';
    if(empty($res)) return '';
    $this->data['description'] = $this->_og_description($res);
    return $this->_og_title($res);
  }

  /**************************
  * MYSAPCE FUNCTIONS
  **************************/
  //which data needs additional http request
  private function myspace_data(){
    return array(
      'thumb' => true,
      'size' => true,
      'embed' => true,
      'iframe' => true,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  private function myspace_decode(){
    $parts = explode('/', $this->code);
    return $parts[sizeof($parts)-1];
  }
  //return http request url where to get data
  private function myspace_req(){
    return $this->source;
    #return "http://mediaservices.myspace.com/services/rss.ashx?type=video&videoID=".$this->code;
  }
  //return thumbnails
  private function myspace_thumb($res){
    $arr = array();
    if(empty($res)) return $arr;
    /*
    if(preg_match( '/<media:thumbnail\s*url="([^"]*)/i', $res, $match))
    if(!empty($match))
    {
      $arr['small'] = $match[1];
      $arr['medium'] = str_replace("/thumb1", "/thumb7", $arr['small']);
      $arr['large'] = str_replace("/thumb1", "/thumb0", $arr['small']);
    }
    */
    #fallback? https://x.myspacecdn.com/new/common/images/video.png
    $arr['small'] = $this->_meta_name('twitter:image',$res);
    $arr['medium'] = $arr['small'];
    $arr['large'] = $arr['small'];
    return $arr;
  }
  //return size
  private function myspace_size($res){
    $arr = array();
    $arr['h'] = 480;
    $arr['w'] = 640;
    if(empty($res)) return $arr;
    /*
    if(preg_match( '/<media:player url=".*"\s*height="([\d]*)"\s*width="([\d]*)"/i', $res, $match))
    if(!empty($match))
    {
      $arr["h"] = (int)$match[1];
      $arr["w"] = (int)$match[2];
    }
    */
    $arr['w'] = $this->_meta_name('twitter:player:width',$res);
    $arr['h'] = $this->_meta_name('twitter:player:height',$res);
    return $arr;
  }
  //return iframe url
  private function myspace_iframe($res=''){
    if(empty($res)) return '';
    // try twitter:player (HTML5)
    $tmp = $this->_meta_name('twitter:player',$res);
    // fallback to og-video
    if(empty($tmp))
    {
      $tmp = $this->_meta_property('og:video',$res);
    }
    return $tmp;
    # Old URL now invalid:
    #'http://mediaservices.myspace.com/services/media/embed.aspx/m='.$this->code;
  }
  //return embed url
  private function myspace_embed($res=''){
    return $this->_meta_property('og:video',$res);
  }
  //return canonical url
  private function myspace_url(){
    return $this->source;
    #return $this->myspace_site().'/video/vid/'.$this->code;
  }
  //return website url
  private function myspace_site(){
    return 'https://myspace.com';
  }
  //return title
  private function myspace_title($res){
    $this->data['description'] = '';
    $this->data['duration'] = '';
    $this->data['tags'] = '';
    if(empty($res)) return '';
    /*
    if(!empty($res) && (false !== ($res = simplexml_load_string($res))))
    {
      $title = $res->channel[0]->item[0]->title[0];
    }
    */
    $this->data['description'] = $this->_meta_name('twitter:description',$res);
    $this->data['duration'] = $this->_meta_property('video:duration',$res);
    #$this->data['tags'] = $this->_meta_name('keywords',$res);
    return $this->_meta_name('twitter:title',$res);
  }

  /**************************
  * pixelbark FUNCTIONS
  **************************/
  private function pixelbark_data(){
    return array(
      'thumb' => true,
      'size' => false,
      'embed' => true,
      'iframe' => true,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return website url
  private function pixelbark_site(){
    return 'http://pixelbark.com';
  }
  //return http request url where to get data
  private function pixelbark_req(){
    $code = ($this->code[strlen($this->code)-1] == '/') ? substr($this->code, 0, strlen($this->code)-1) : $this->code;
    return $this->pixelbark_site().'/'.$code;
  }
  //return thumbnails
  private function pixelbark_thumb($res=''){
    $arr = array();
    if(empty($this->code)) return $arr;
    #Ex. <meta property="og:image" content="http://media.thatvideosite.com/core/10656/image_200.jpg">
    if(!empty($res))
    {
      $link = $this->_og_image($res);
      $arr['small']  = $link;
      $arr['medium'] = $link;
      $arr['large']  = $link;
      return $arr;
    }
    $link = 'http://media.thatvideosite.com/core/'.$this->code.'/image_';
    $arr['small']  = $link.'200.jpg';
    $arr['medium'] = $link.'400.jpg';
    $arr['large']  = $link.'900.jpg';
    return $arr;
  }
    //return size
  private function pixelbark_size(){
    #full page video size: 920x516
    $arr = array();
    $arr['w'] = 460;
    $arr['h'] = 284;
    return $arr;
  }
  //return iframe url
  private function pixelbark_iframe($res=''){
    $code = ($this->code[strlen($this->code)-1] == '/') ? substr($this->code, 0, strlen($this->code)-1) : $this->code;
    return $this->pixelbark_site().'/e/'.$code;
  }
  //return embed url
  private function pixelbark_embed($res=''){
    if(empty($res)) return '';
    //SD: old embedding URL only returns empty page; reconstructed object code here:
    $this->data['object_html'] = '
  <object tabindex="0" name="mediaplayer" id="mediaplayer" bgcolor="#000000" data="'.$this->pixelbark_site().'/mediaplayer/player.swf" type="application/x-shockwave-flash" height="100%" width="100%">'.
  '<param value="true" name="allowfullscreen">'.
  '<param value="always" name="allowscriptaccess">'.
  '<param value="true" name="seamlesstabbing">'.
  '<param value="opaque" name="wmode">'.
  '<param value="netstreambasepath=http%3A%2F%2Fpixelbark.com%2F'.$this->code.
  '&amp;id=mediaplayer&amp;autostart=false&amp;skin=http%3A%2F%2Fpixelbark.com%2Fmediaplayer%2Fskins%2Fglow.zip'.
  '&amp;file=http%3A%2F%2Fm.thatvideosite.com%2Fcore%2F'.$this->code.
  '%2Fvideo.mp4&amp;controlbar.position=over" name="flashvars"></object>
  '."
  <script type=\"text/javascript\" src=\"http://pixelbark.com/mediaplayer/jwplayer.js\"></script>
  <script type=\"text/javascript\">
  if(typeof mediaplayer !== \"undefined\") {
    jwplayer('mediaplayer').setup({
      'flashplayer': 'http://pixelbark.com/mediaplayer/player.swf',
      'id': 'playerID',
      'width': '".$this->default_size['w']./*$this->data['w'].*/"',
      'height': '".$this->default_size['h']./*$this->data['h'].*/"',
      'autostart': 'false',
      'controlbar': 'over',
      'skin': 'http://pixelbark.com/mediaplayer/skins/glow.zip',
      'file': 'http://m.thatvideosite.com/core/".$this->code."/video.mp4'
    });
  }
  </script>";
    /*
    if(@preg_match('/\'file\'\s*:\s*\'(.*[^\'])\'/i', $res, $match))
    if(!empty($match))
    {
      return $match[1];
    }
    */
    return true;
    #return $this->pixelbark_iframe();
  }
  //return canonical url
  private function pixelbark_url($res=''){
    if(!empty($res)) return $this->_og_url($res);

    $code = ($this->code[strlen($this->code)-1] != '/') ? ($this->code).'/' : $this->code;
    return $this->pixelbark_site().'/'.$code;
  }
  //return title
  private function pixelbark_title($res=''){
    $title = '';
    if(!empty($res)) return $this->_og_title($res);
    return $title;
  }

  /**************************
  * METACAFE FUNCTIONS
  **************************/
  //which data needs additional http request
  private function metacafe_data(){
    return array(
      'thumb' => false,
      'size' => false,
      'embed' => false,
      'iframe' => false,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function metacafe_req(){
    return $this->metacafe_url();
  }
  //return thumbnails
  private function metacafe_thumb(){
    $arr = array();
    //SD: avoid PHP notices
    if(empty($this->code)) return $arr;
    $parts = @explode('/', $this->code);
    if(!count($parts)) return $arr;
    $arr['medium'] = 'http://s.mcstatic.com/thumb/'.$parts[0].'.jpg';
    if(count($parts) > 1)
    {
      $arr['large'] = 'http://s.mcstatic.com/thumb/'.$parts[0].'/0/4/videos/0/1/'.$parts[1].'.jpg';
      $arr['small'] = 'http://s.mcstatic.com/thumb/'.$parts[0].'/0/4/sidebar_16x9/0/1/'.$parts[1].'.jpg';
    }
    return $arr;
  }
    //return size
  private function metacafe_size(){
    $arr = array();
    $arr['w'] = 460;
    $arr['h'] = 284;
    return $arr;
  }
  //return iframe url
  private function metacafe_iframe(){
    $code = ($this->code[strlen($this->code)-1] == '/') ? substr($this->code, 0, strlen($this->code)-1) : $this->code;
    return $this->metacafe_site().'/fplayer/'.$code.'.swf';
  }
  //return embed url
  private function metacafe_embed(){
    return $this->metacafe_iframe();
  }
  //return canonical url
  private function metacafe_url(){
    $code = ($this->code[strlen($this->code)-1] != '/') ? ($this->code).'/' : $this->code;
    return $this->metacafe_site().'/watch/'.$code;
  }
  //return website url
  private function metacafe_site(){
    return 'http://www.metacafe.com';
  }
  //return title
  private function metacafe_title($res=''){
    $this->data['description'] = '';
    $this->data['tags'] = '';
    if(empty($res)) return '';
    $this->data['description'] = $this->metacafe_description($res); //SD
    return $this->_link2title();
  }
  private function metacafe_description($res='') //SD
  {
    if(empty($res)) return '';
    // MetaCafe: dirty way to extract description from HTML :(
    if(preg_match('#<div id="Description">(.*)<\/div>#si', $res, $match))
    if(!empty($match) && (count($match) > 1))
    {
      $code = trim(substr($match[1],0,strpos($match[1],'</div')));
      $code = preg_replace("/\r|\n/"," ",$code);
      $code = str_replace('<br />',"\n",$code);
      $code = trim(strip_tags($code));
      return $code;
    }
  }

  /**************************
  * REVVER FUNCTIONS
  **************************/
  private function revver_decode(){
    $parts = explode('/', $this->code);
    return (empty($parts) || !count($parts) ? '' : $parts[0]);
  }
  //which data needs additional http request
  private function revver_data(){
    return array(
      'thumb' => false,
      'size' => false,
      'embed' => false,
      'iframe' => false,
      'url' => false,
      'site' => false,
      'title' => false
    );
  }
  //return http request url where to get data
  private function revver_req(){
    return '';
  }
  //return thumbnails
  private function revver_thumb(){
    $arr = array();
    $arr['small']  = 'http://frame.revver.com/frame/120x90/'.$this->revver_decode().'.jpg';
    $arr['medium'] = 'http://frame.revver.com/frame/320x240/'.$this->revver_decode().'.jpg';
    $arr['large']  = 'http://frame.revver.com/frame/480x360/'.$this->revver_decode().'.jpg';
    return $arr;
  }
  //return size
  private function revver_size(){
    $arr = array();
    $arr['w'] = 480;
    $arr['h'] = 392;
    return $arr;
  }
  //return iframe url
  private function revver_iframe(){
    return "http://flash.revver.com/player/1.0/player.swf?mediaId=".$this->revver_decode();
  }
  //return embed url
  private function revver_embed(){
    return $this->revver_iframe();
  }
  //return canonical url
  private function revver_url(){
    return $this->revver_site().'/video/'.$this->code;
  }
  //return website url
  private function revver_site(){
    return 'http://www.revver.com';
  }
  //return title
  private function revver_title(){
    return $this->_link2title();
  }

  /**************************
  * aol FUNCTIONS
  **************************/
  private function aol_decode(){
    $parts = explode('-', $this->code);
    return $parts[sizeof($parts)-1];
  }
  //which data needs additional http request
  private function aol_data(){
    return array(
      'thumb' => true,
      'size' => true,
      'embed' => false,
      'iframe' => false,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function aol_req(){
    return "http://api.5min.com/oembed.xml?url=http://www.5min.com/Video/-".$this->aol_decode();
    #return 'http://on.aol.com/video/-'.$this->aol_decode();
  }
  //return thumbnails
  private function aol_thumb($res){
    if(empty($res)) return array();
    if(false !== ($ret = simplexml_load_string($res)))
    {
      $arr['medium'] = current($ret->thumbnail_url);
      $arr['small'] = str_replace(".jpg", "_124_92.jpg", $arr['medium']);
      $arr['large'] = str_replace(".jpg", "_".current($ret->width)."_".current($ret->height).".jpg", $arr['medium']);
    }
    return $arr;
  }
  //return size
  private function aol_size($res){
    if(empty($res)) return array();
    $arr = array();
    if(false !== ($res = simplexml_load_string($res)))
    {
      $arr['w'] = current($res->width);
      $arr['h'] = current($res->height);
    }
    return $arr;
  }
  //return iframe url
  private function aol_iframe(){
    return "http://embed.5min.com/".$this->aol_decode();
  }
  //return embed url
  private function aol_embed(){
    return $this->aol_iframe();
  }
  //return canonical url
  private function aol_url(){
    return $this->aol_site().'/video/'.$this->code;
  }
  //return website url
  private function aol_site(){
    return 'http://on.aol.com';
  }
  //return title
  private function aol_title($res){
    $this->data['description'] = '';
    $this->data['duration'] = 0;
    $this->data['tags'] = '';
    if(empty($res)) return '';
    if(false !== ($ret = @simplexml_load_string($res)))
    {
      $this->data['description'] = current($ret->description);
      $this->data['duration'] = current($ret->duration);
      return current($ret->title);
    }
    return '';
  }

  /**************************
  * CLIKTHROUGH FUNCTIONS
  **************************/
  //which data needs additional http request
  private function clikthrough_data(){
    return array(
      'thumb' => true,
      'size' => true,
      'embed' => false,
      'iframe' => false,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function clikthrough_req(){
    return $this->clikthrough_url();
    #return 'http://www.clikthrough.com/services/oembed/?url='.urlencode($this->clikthrough_url()).'%26format%3Djson';
  }
  //return thumbnails
  private function clikthrough_thumb($res){
    $arr = array();
    if(empty($res)) return $arr;
    $arr['medium'] = $this->_og_image($res);
    $arr['small'] = str_replace("/M-", "/Sw-", $arr['medium']);
    $arr['large'] = str_replace("/M-", "/L-", $arr['medium']);
    /* #OLD:
    if((false !== ($res = json_decode($res, true))))
    if(!empty($res) && is_array($res))
    {
      $arr['medium'] = $res['thumbnail_url'];
      $arr['small'] = str_replace("/M-", "/Sw-", $arr['medium']);
      $arr['large'] = str_replace("/M-", "/L-", $arr['medium']);
    }
    */
    return $arr;
  }
  //return size
  private function clikthrough_size($res){
    #return $this->_oembed_size($res);
    $arr = array();
    $arr['w'] = 640;
    $arr['h'] = 415;
    if(!empty($res))
    {
      $arr['w'] = $this->_meta_name('video_width',$res);
      $arr['h'] = $this->_meta_name('video_height',$res);
    }
    return $arr;
  }
  //return iframe url
  private function clikthrough_iframe(){
    return $this->clikthrough_site().'/clikPlayer.swf?videoId='.$this->code;
  }
  //return embed url
  private function clikthrough_embed(){
    return $this->clikthrough_iframe();
  }
  //return canonical url
  private function clikthrough_url(){
    return $this->clikthrough_site().'/theater/video/'.$this->code;
  }
  //return title
  private function clikthrough_title($res){
    #return $this->_oembed_data('title',$res);
    $this->data['description'] = '';
    $this->data['duration'] = 0;
    $this->data['tags'] = '';
    if(empty($res)) return '';
    $this->data['description'] = $this->_og_description($res);
    $this->data['tags'] = $this->_meta_name('keywords',$res);
    return $this->_og_title($res);
  }
  //return website url
  private function clikthrough_site(){
    return 'http://www.clikthrough.com';
  }

  /**************************
  * DOTSUB FUNCTIONS
  **************************/
  //which data needs additional http request
  private function dotsub_data(){
    return array(
      'thumb' => false,
      'size' => true,
      'embed' => false,
      'iframe' => false,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function dotsub_req(){
    return 'http://dotsub.com/services/oembed?url='.urlencode($this->dotsub_url());
  }
  //return thumbnails
  private function dotsub_thumb(){
    $arr = array();
    $arr['medium'] = $this->dotsub_site().'/media/'.$this->code.'/t';
    $arr['small'] = $arr['medium'];
    $arr['large'] = $arr['medium'];
    return $arr;
  }
  //return size
  private function dotsub_size($res=''){
    if(empty($res)) return array();
    return $this->_oembed_size($res);
  }
  //return iframe url
  private function dotsub_iframe(){
    return 'http://dotsub.com/static/players/portalplayer.swf?uuid='.$this->code.'&lang=eng&plugins=dotsub&embedded=true';
  }
  //return embed url
  private function dotsub_embed(){
    return $this->dotsub_iframe();
  }
  //return canonical url
  private function dotsub_url(){
    return $this->dotsub_site().'/view/'.$this->code;
  }
  //return website url
  private function dotsub_site(){
    return 'http://www.dotsub.com';
  }
  //return title
  private function dotsub_title($res=''){
    if(empty($res)) return array();
    return $this->_oembed_data('title',$res);
  }

  /**************************
  * REVISION3 FUNCTIONS
  **************************/
  //which data needs additional http request
  private function revision_data(){
    return array(
      'thumb' => true,
      'size' => true,
      'embed' => true,
      'iframe' => false,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function revision_req(){
    return $this->revision_url();
  }
  //return thumbnails
  private function revision_thumb($res=''){
    $arr = array();
    $arr['small'] = $this->_og_image($res);
    $arr['medium'] = $arr['small'];
    $arr['large'] = $arr['small'];
    //SD: thumbs don't work anymore!
    /*
    if(preg_match( '/<link\s*rel="image_src"\s*href="([^"]*)"/i', $res, $match))
    if(!empty($match))
    {
      $arr['small'] = $match[1];
      $arr['medium'] = str_replace("small.thumb.jpg", "medium.thumb.jpg", $match[1]);
      $arr['large'] = str_replace("small.thumb.jpg", "large.thumb.jpg", $match[1]);
    }
    */
    return $arr;
  }
  //return size
  private function revision_size($res=''){
    $arr = array();
    $arr['w'] = 555;
    $arr['h'] = 312;
    if(empty($res)) return $arr;
    return $this->_og_size($res);
  }
  //return iframe url
  private function revision_iframe($res=''){
    #$size = $this->revision_size($res);
    return 'http://revision3.com/html5player-v'.$this->code.'?external=true&width='.$this->data['w'].'&height='.$this->data['h'];
  }
  //return embed url
  private function revision_embed($res=''){
    return $this->_og_video($res);
  }
  //return canonical url
  private function revision_url(){
    $t = $this->revision_site().'/'.$this->code;
    return $t;
    #return $this->revision_site().'/player/embed?videoId='.$this->code;
  }
  //return website url
  private function revision_site(){
    return 'http://revision3.com';
  }
  //return title
  private function revision_title($res=''){
    $this->data['description'] = '';
    $this->data['duration'] = 0;
    $this->data['tags'] = '';
    if(empty($res)) return '';
    $this->data['description'] = $this->_og_description($res);
    $this->data['duration'] = $this->_meta_property('video:duration',$res);
    $this->data['tags'] = $this->_video_tag($res);
    return $this->_og_title($res);
  }

  /**************************
  * VIDEOJUG FUNCTIONS
  **************************/
  //which data needs additional http request
  private function videojug_data(){
    return array(
      'thumb' => true,
      'size' => true,
      'embed' => true,
      'iframe' => true,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function videojug_req(){
    return $this->videojug_url();
  }
  //return thumbnails
  private function videojug_thumb($res=''){
    $arr = array();
    if(empty($res)) return $arr;
    $arr['medium'] = $this->_og_image($res);
    $arr['small'] = $arr['medium'];#str_replace('Medium.jpg', 'Small.jpg', $match[1]);
    $arr['large'] = $arr['medium'];#str_replace('Medium.jpg', 'Large.jpg', $match[1]);
    return $arr;
  }
  //return size
  private function videojug_size($res=''){
    $arr = array();
    if(empty($res)) return $arr;
    $arr = $this->_og_size($res);
    return $arr;
  }
  //return iframe url
  private function videojug_iframe($res=''){
    if(empty($res)) return '';
    return $this->_og_video($res);
  }
  //return embed url
  private function videojug_embed($res=''){
    return $this->videojug_iframe($res);
  }
  //return canonical url
  private function videojug_url(){
    return $this->videojug_site().'/film/'.$this->code;
  }
  //return website url
  private function videojug_site(){
    return 'http://www.videojug.com';
  }
  //return title
  private function videojug_title($res=''){
    $this->data['description'] = '';
    if(empty($res)) return '';
    $this->data['description'] = $this->_og_description($res);
    return $this->_og_title($res);
  }

  /**************************
  * BLIP FUNCTIONS
  **************************/
  //which data needs additional http request
  private function blip_data(){
    return array(
      //SD: changed thumb/embed to false since it doesn't work with shortcode URL!
      'thumb' => true,
      'size' => false,
      'embed' => false,
      'iframe' => false,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function blip_req(){
    return $this->blip_url();
  }
  //return thumbnails
  private function blip_thumb($res=''){
    $arr = array();
    if(empty($res)) return $arr;
    //SD: commented out since this won't work with shortcode-urls!
    /*
    preg_match( '/<meta\s*property="og:image"\s*content="([^"]*)"/i', $res, $match);
    if(!empty($match))
    {
      $arr['large'] = $match[1];
      $file = explode("blip.tv/", $match[1]);
      $arr['small'] = "http://i.blip.tv/g?src=".$file[1]."&w=140&h=80";
      $arr['medium'] = "http://i.blip.tv/g?src=".$file[1]."&w=300&h=170";
    }
    */
    $arr['large']  = $this->_og_image($res);
    $arr['medium'] = $this->_og_image($res);
    $arr['small']  = $this->_og_image($res);
    return $arr;
  }
  //return size
  private function blip_size(){
    $arr = array();
    $arr['h'] = 430;
    $arr['w'] = 720;
    return $arr;
  }
  private function blip_decode(){
    if(empty($this->code)) return '';
    $parts = explode('-', $this->code);
    return $parts[sizeof($parts)-1];
  }
  //return iframe url
  private function blip_iframe($res=''){
    $code = $this->blip_decode();
    if(empty($code)) return '';
    return $this->blip_site()."/players/standard?no_wrap=1&id=".$code."&autoplay=false&onsite=false&no_preroll=true&no_postroll=true&data_url=http://blip.tv/players/xplayer&swf_location=http://a.blip.tv/scripts/flash/stratos.swf&site_url=http://blip.tv";
  }
  //return embed url
  private function blip_embed($res=''){
    return '';
  }
  //return canonical url
  private function blip_url(){
    return $this->blip_site().'/'.$this->code;
  }
  //return website url
  private function blip_site(){
    return 'http://blip.tv';
  }
  //return title
  private function blip_title($res=''){
    $this->data['description'] = '';
    $this->data['duration'] = 0;
    $this->data['tags'] = '';
    if(empty($res)) return '';
    $this->data['description'] = $this->_og_description($res);
    $this->data['duration'] = $this->_meta_property('video:duration',$res);
    $this->data['tags'] = $this->_video_tag($res);
    return $this->_og_title($res);
  }

  /**************************
  * VIDDLER FUNCTIONS
  **************************/
  //which data needs additional http request
  private function viddler_data(){
    return array(
      'thumb' => true,
      'size' => true,
      'embed' => false,
      'iframe' => true,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function viddler_req(){
    #return 'http://lab.viddler.com/services/oembed/?format=json&type=simple&url='.$this->viddler_url();
    return 'http://www.viddler.com/embed/'.$this->code;
  }
  //return thumbnails
  private function viddler_thumb($res=''){
    if(empty($res)) return array();
    $arr = array();
    $arr['large']  = $this->_og_image($res);
    $arr['medium'] = $arr['large'];
    $arr['small']  = $arr['large'];
    /*
    $res = json_decode($res, true);
    if(!empty($res) && is_array($res))
    {
      $arr['large'] = $res['thumbnail_url'];
      $arr['medium'] = $res['thumbnail_url'];
      $arr['small'] = str_replace("thumbnail_2", "thumbnail_1", $res['thumbnail_url']);
    }
    */
    return $arr;
  }
  //return size
  private function viddler_size($res=''){
    #return $this->_oembed_size($res);
    return $this->_og_size($res);
  }
  //return iframe url
  private function viddler_iframe($res=''){
    return $this->viddler_site().'/player/'.$this->code;
    /*
    #if(empty($res)) { $this->source_err = true; return ''; }
    $url = '';
    if(false !== $res = json_decode($res, true))
    if(!empty($res) && is_array($res))
    {
      if(preg_match('/<param\s*name="movie"\s*value="([^"]*)"/i', $res["html"], $match))
      if(!empty($match))
      {
        $url = $match[1];
      }
    }
    return $url;
    */
  }
  //return embed url
  private function viddler_embed($res=''){
    return '';#$this->viddler_iframe($res);
  }
  //return canonical url
  private function viddler_url(){
    return $this->viddler_site().'/explore/'.$this->code;
  }
  //return website url
  private function viddler_site(){
    return 'http://www.viddler.com';
  }
  //return title
  private function viddler_title($res=''){
    #return $this->_oembed_data('title',$res);
    if(empty($res)) return '';
    return $this->_meta_name('description',$res);
  }

  /**************************
  * SCREENR FUNCTIONS
  **************************/
  //which data needs additional http request
  private function screenr_data(){
    return array(
      'thumb' => true,
      'size' => true,
      'embed' => false,
      'iframe' => false,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function screenr_req(){
    return $this->screenr_site().'/api/oembed.json?url='.urlencode($this->screenr_url());
  }
  //return thumbnails
  private function screenr_thumb($res=''){
    $arr = array();
    if(!$res = json_decode($res, true)) return $arr;
    if(!empty($res) && is_array($res) && isset($res['thumbnail_url']))
    {
      $arr['small']  = $res['thumbnail_url'];
      $arr['medium'] = $res['thumbnail_url'];
      $arr['large']  = str_replace('_thumb', '', $res['thumbnail_url']);
    }
    return $arr;
  }
  //return size
  private function screenr_size($res=''){
    return $this->_oembed_size($res);
  }
  //return iframe url
  private function screenr_iframe($res=''){
    return $this->screenr_site().'/embed/'.$this->code;
  }
  //return embed url
  private function screenr_embed($res=''){
    return '';
  }
  //return canonical url
  private function screenr_url(){
    return $this->screenr_site().'/'.$this->code;
  }
  //return website url
  private function screenr_site(){
    return 'http://www.screenr.com';
  }
  //return title
  private function screenr_title($res){
    $this->data['description'] = '';
    if(empty($res)) return '';
    $this->data['description'] = $this->_oembed_data('description',$res);
    return $this->_oembed_data('title',$res);
  }

  /**************************
  * SLIDESHARE FUNCTIONS
  **************************/
  //which data needs additional http request
  private function slideshare_data(){
    return array(
      'thumb' => true,
      'size' => true,
      'embed' => false,
      'iframe' => true,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function slideshare_req(){
    return "http://www.slideshare.net/api/oembed/1?format=json&amp;url=".$this->slideshare_url();
  }
  //return thumbnails
  private function slideshare_thumb($res){
    $arr = array();
    if(false !== ($res = json_decode($res, true)))
    if(!empty($res) && is_array($res))
    {
      $arr['small'] = $res["thumbnail"]."-2";
      $arr['medium'] = $res["thumbnail"];
      $arr['large'] = $res["thumbnail"];
    }
    return $arr;
  }
  //return size
  private function slideshare_size($res){
    return $this->_oembed_size($res);
  }
  //return iframe url
  private function slideshare_iframe($res=''){
    if(empty($res)) return '';
    #$code = explode("-", $this->code);
    #return "http://www.slideshare.net/slideshow/embed_code/".($code[sizeof($code)-1]);
    $tmp = $this->_oembed_data('html',$res);
    return $tmp;
  }
  //return embed url
  private function slideshare_embed(){
    return '';
  }
  //return canonical url
  private function slideshare_url(){
    return "http://www.slideshare.net/".($this->code);
  }
  //return website url
  private function slideshare_site(){
    return "http://www.slideshare.net";
  }
  //return title
  private function slideshare_title($res){
    return $this->_oembed_data('title',$res);
  }

  /**************************
  * HULU FUNCTIONS
  **************************/
  //which data needs additional http request
  private function hulu_data(){
    return array(
      'thumb' => true,
      'size' => true,
      'embed' => true,
      'iframe' => true,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function hulu_req(){
    return "http://www.hulu.com/api/oembed.json?url=".$this->hulu_url();
  }
  //return thumbnails
  private function hulu_thumb($res){
    $arr = array();
    $res = json_decode($res, true);
    if(is_array($res) && !empty($res))
    {
      $arr['large'] = $res['thumbnail_url'];
      $arr['medium'] = $res['thumbnail_url'];
      $arr['small'] = $res['thumbnail_url'];
    }
    return $arr;
  }
  //return size
  private function hulu_size($res){
    return $this->_oembed_size($res);
  }
  //return iframe url
  private function hulu_iframe($res){
    $url = "";
    $res = json_decode($res, true);
    if(!empty($res) && is_array($res))
    {
      $url = $res['embed_url'];
    }
    return $url;
  }
  //return embed url
  private function hulu_embed($res){
    return $this->viddler_iframe($res);
  }
  //return canonical url
  private function hulu_url(){
    return "http://www.hulu.com/watch/".($this->code);
  }
  //return website url
  private function hulu_site(){
    return "http://www.hulu.com";
  }
  //return title
  private function hulu_title($res){
    return $this->_oembed_data('title',$res);
  }

  /**************************
  * QIK FUNCTIONS
  **************************/
  //which data needs additional http request
  private function qik_data(){
    return array(
      'thumb' => true,
      'size' => true,
      'embed' => true,
      'iframe' => true,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function qik_req(){
    return "http://qik.com/api/oembed.json?url=".$this->qik_url();
  }
  //return thumbnails
  private function qik_thumb($res){
    $arr = array();
    $res = json_decode($res, true);
    if(is_array($res) && !empty($res))
    {
      preg_match( '/FlashVars="streamID=([^&]*)&/i', $res["html"], $match);
      if(!empty($match))
      {
        $arr['large'] = "http://qikimg.com/media.thumbnails.128/".$match[1].".jpg";
        $arr['medium'] = "http://qikimg.com/media.thumbnails.128/".$match[1].".jpg";
        $arr['small'] = "http://qikimg.com/media.thumbnails.128/".$match[1].".jpg";
      }
    }
    return $arr;
  }
  //return size
  private function qik_size($res){
    return $this->_oembed_size($res);
  }
  //return iframe url
  private function qik_iframe($res){
    $url = "";
    $res = json_decode($res, true);
    if(is_array($res) && !empty($res))
    {
      preg_match( '/FlashVars="([^"]*)"/i', $res["html"], $match);
      if(!empty($match))
      {
        $url = "http://qik.com/swfs/qikPlayer5.swf?".$match[1];
      }
    }
    return $url;
  }
  //return embed url
  private function qik_embed($res){
    return $this->qik_iframe($res);
  }
  //return canonical url
  private function qik_url(){
    return "http://www.qik.com/video/".($this->code);
  }
  //return website url
  private function qik_site(){
    return "http://www.qik.com";
  }
  //return title
  private function qik_title($res){
    return $this->_oembed_data('title',$res);
  }

  /**************************
  * FLICKR FUNCTIONS
  **************************/
  //which data needs additional http request
  private function flickr_data(){
    return array(
      'thumb' => true,
      'size' => true,
      'embed' => false,
      'iframe' => false,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function flickr_req(){
    return 'http://www.flickr.com/services/oembed/?format=json&url='.urlencode($this->flickr_url());
  }
  //return thumbnails
  private function flickr_thumb($res){
    $arr = array();
    $res = json_decode($res, true);
    if(is_array($res) && !empty($res))
    {
      $arr['large'] = str_replace(".jpg", '_b.jpg', $res['url']);
      $arr['medium'] = $res['url'];
      $arr['small'] = str_replace(".jpg", '_m.jpg', $res['url']);
    }
    return $arr;
  }
  //return size
  private function flickr_size($res){
    return $this->_oembed_size($res);
  }
  //return iframe url
  private function flickr_iframe(){
    return '';
  }
  //return embed url
  private function flickr_embed(){
    return '';
  }
  //return canonical url
  private function flickr_url(){
    return 'http://www.flickr.com/photos/'.$this->code;
  }
  //return website url
  private function flickr_site(){
    return 'http://www.flickr.com';
  }
  //return title
  private function flickr_title($res){
    return $this->_oembed_data('title',$res);
  }

  /**************************
  * FUNNYORDIE FUNCTIONS
  **************************/
  private function funnyordie_decode(){
    $parts = explode('/', $this->code);
    return (!empty($parts) && count($parts) ? $parts[0] : '');
  }
  //which data needs additional http request
  private function funnyordie_data(){
    return array(
      'thumb' => true,
      'size' => false,
      'embed' => false,
      'iframe' => false,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function funnyordie_req(){
    //SD: json does not deliver enough data
    #return $this->funnyordie_site().'/oembed?format=json&url='.urlencode($this->funnyordie_url());
    return $this->funnyordie_url();
  }
  //return thumbnails
  private function funnyordie_thumb($res=''){
    $arr = array();
    if(empty($res)) return $arr;
    /*
    $arr['large'] = "http://assets.ordienetworks.com/tmbs/".($this->funnyordie_decode())."/fullsize_11.jpg";
    $arr['medium'] = "http://assets.ordienetworks.com/tmbs/".($this->funnyordie_decode())."/large_11.jpg";
    $arr['small'] = "http://assets.ordienetworks.com/tmbs/".($this->funnyordie_decode())."/medium_11.jpg";
    */
    $arr['medium'] = $this->_og_image($res);
    $arr['large'] = $arr['medium'];
    $arr['small'] = $arr['medium'];
    return $arr;
  }
  //return size
  private function funnyordie_size($res=''){
    return array('w' => 480, 'h' => 270);
  }
  //return iframe url
  private function funnyordie_iframe(){
    return "http://public0.ordienetworks.com/flash/fodplayer.swf?key=".$this->funnyordie_decode();
  }
  //return embed url
  private function funnyordie_embed(){
    return $this->funnyordie_iframe();
  }
  //return canonical url
  private function funnyordie_url(){
    return $this->funnyordie_site().'/videos/'.$this->code;
  }
  //return website url
  private function funnyordie_site(){
    return 'http://www.funnyordie.com';
  }
  //return title
  private function funnyordie_title($res=''){
    $this->data['description'] = '';
    $this->data['duration'] = 0;
    $this->data['tags'] = '';
    if(empty($res)) return '';
    $this->data['description'] = $this->_meta_name('description',$res); //SD
    $this->data['duration'] = $this->_meta_property('video:duration',$res); //SD
    $this->data['tags'] = $this->_meta_name('keywords',$res); //SD
    return $this->_oembed_data('title',$res);
  }

  /**************************
  * TWITPIC FUNCTIONS
  **************************/
  //which data needs additional http request
  private function twitpic_data(){
    return array(
      'thumb' => false,
      'size' => true,
      'embed' => false,
      'iframe' => false,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function twitpic_req(){
    return $this->twitpic_url();
  }
  //return thumbnails
  private function twitpic_thumb(){
    $arr = array();
    $arr['large']  = $this->twitpic_site().'/show/full/'.$this->code.'.jpg';
    $arr['medium'] = $this->twitpic_site().'/show/large/'.$this->code.'.jpg';
    $arr['small']  = $this->twitpic_site().'/show/thumb/'.$this->code.'.jpg';
    return $arr;
  }
  //return size
  private function twitpic_size($res=''){
    $arr = array();
    $arr['w'] = 150;
    $arr['h'] = 150;
    if(empty($res)) return $arr;
    return $this->_twitter_sizes($res);
  }
  //return iframe url
  private function twitpic_iframe(){
    return '';
  }
  //return embed url
  private function twitpic_embed(){
    return '';
  }
  //return canonical url
  private function twitpic_url(){
    return $this->twitpic_site().'/'.$this->code;
  }
  //return website url
  private function twitpic_site(){
    return 'http://twitpic.com';
  }
  //return title
  private function twitpic_title($res=''){
    $this->data['description'] = '';
    $this->data['tags'] = '';
    if(empty($res)) return '';
    return $this->_og_title($res);
  }

  /**************************
  * YFROG FUNCTIONS
  **************************/
  //which data needs additional http request
  private function yfrog_data(){
    return array(
      'thumb' => true,
      'size' => true,
      'embed' => true,
      'iframe' => true,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function yfrog_req(){
    return $this->yfrog_site().'/api/oembed?url='.urlencode($this->yfrog_url());
  }
  //return thumbnails
  private function yfrog_thumb($res){
    $arr = array();
    if(!$res = json_decode($res, true)) return $arr;
    if(!empty($res) && is_array($res) && isset($res['thumbnail_url']))
    {
      $arr['large'] = $res['thumbnail_url'];
      $arr['medium'] = $this->yfrog_site().'/'.$this->code.':medium';
      $arr['small'] = $this->yfrog_site().'/'.$this->code.':small';
    }
    else
    {
      $arr['large'] = $this->yfrog_site().'/'.$this->code.':small';
      $arr['medium'] = $this->yfrog_site().'/'.$this->code.':small';
      $arr['small'] = $this->yfrog_site().'/'.$this->code.':small';
    }
    return $arr;
  }
  //return size
  private function yfrog_size($res=''){
    return $this->_oembed_size($res);
  }
  //return iframe url
  private function yfrog_iframe($res=''){
    return $this->source.':embed'; //SD: ":embed"!
    /*
    if(!$res = json_decode($res, true)) return '';
    $code = '';
    if(is_array($res) && !empty($res) && isset($res['html']))
    {
      $code = $res['html'];
    }
    return $code;
    */
  }
  //return embed url
  private function yfrog_embed($res=''){
    return $this->yfrog_iframe($res);
  }
  //return canonical url
  private function yfrog_url(){
    return $this->yfrog_site().$this->code;
  }
  //return website url
  private function yfrog_site(){
    return 'http://yfrog.com';
  }
  //return title
  private function yfrog_title($res){
    return $this->_oembed_data('title',$res);
  }

  /***************************
  * SOUNDCLOUD FUNCTIONS //SD
  ****************************/
  //which data needs additional http request
  private function soundcloud_data(){
    return array(
      'thumb' => true,
      'size' => true,
      'embed' => false,
      'iframe' => true,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function soundcloud_req(){
    return $this->soundcloud_site().'/oembed?format=json&url='.$this->soundcloud_url();
  }
  //return thumbnails
  private function soundcloud_thumb($res=''){
    $arr = array();
    $res = json_decode($res, true);
    if(!empty($res) && is_array($res) && isset($res['thumbnail_url']))
    {
      $arr['large'] = $res['thumbnail_url'];
      $arr['medium'] = $arr['large'];
      $arr['small'] = $arr['large'];
    }
    return $arr;
  }
  //return size
  private function soundcloud_size($res=''){
    return $this->_oembed_size($res);
  }
  //return iframe url
  private function soundcloud_iframe($res=''){
    $code = '';
    if($res = json_decode($res, true))
    if(!empty($res) && is_array($res) && /*($res['type'] == 'video') &&*/ isset($res['html']))
    {
      $this->data['object_html'] = $res['html'];
      return true;
    }
    return '';
  }
  //return embed url
  private function soundcloud_embed($res=''){
    return '';//$this->soundcloud_iframe($res);
  }
  //return canonical url
  private function soundcloud_url(){
    return $this->soundcloud_site().'/'.$this->code;
  }
  //return website url
  private function soundcloud_site(){
    return 'http://soundcloud.com';
  }
  //return title
  private function soundcloud_title($res){
    $this->data['description'] = '';
    if(empty($res)) return '';
    $this->data['description'] = $this->_oembed_data('description',$res);
    return $this->_oembed_data('title',$res);
  }

  /**************************
  * BREAK FUNCTIONS
  **************************/
  //which data needs additional http request
  private function break_data(){
    return array(
      'thumb' => true,
      'size' => true,
      'embed' => false,
      'iframe' => false,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function break_req(){
    return $this->break_url();
  }
  //return thumbnails
  private function break_thumb($res){
    if(empty($res)) return array();
    $arr = array();
    $arr['large'] = $this->_meta_name('embed_video_thumb_url',$res);
    $arr['medium'] = $arr['large'];
    $arr['small'] = $arr['large'];
    return $arr;
  }
  //return size
  private function break_size($res){
    if(empty($res)) return array();
    return $this->_og_size($res);
  }
  //return iframe url
  private function break_iframe(){
    $parts = explode('-', $this->code);
    //get the last part of url
    return 'http://embed.break.com/'.$parts[sizeof($parts)-1];
  }
  //return embed url
  private function break_embed(){
    return $this->break_iframe();
  }
  //return canonical url
  private function break_url(){
    return $this->break_site().'/video/'.$this->code;
  }
  //return website url
  private function break_site(){
    return 'http://www.break.com';
  }
  //return title
  private function break_title($res=''){
    if(empty($res)) return '';
    return $this->_meta_name('embed_video_title',$res);
  }

  /**************************
  * SEVENLOAD FUNCTIONS
  **************************/
  //which data needs additional http request
  private function sevenload_data(){
    return array(
      'thumb' => true,
      'size' => true,
      'embed' => false,
      'iframe' => false,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function sevenload_req(){
    if(empty($this->code)) return '';
    return $this->sevenload_url();
  }
  //return thumbnails
  private function sevenload_thumb($res=''){
    if(empty($res)) return array();
    $arr = array();
    $arr['large'] = $this->_og_image($res);
    $arr['medium'] = $arr['large'];
    $arr['small'] = $arr['large'];
    return $arr;
  }
  //return size
  private function sevenload_size($res=''){
    if(empty($res)) return array();
    return $this->_og_size($res);
  }
  //return iframe url
  private function sevenload_iframe($res=''){
    //get the last part of url
    $parts = explode('-', $this->code);
    return $this->sevenload_site().'/widgets/single_player/'.$parts[sizeof($parts)-1].'/?autoplay=false';
  }
  //return embed url
  private function sevenload_embed($res=''){
    return '';
  }
  //return canonical url
  private function sevenload_url(){
    return $this->source;# $this->sevenload_site().'/videos/'.$this->code;
  }
  //return website url
  private function sevenload_site(){
    return 'http://www.sevenload.com';
  }
  //return title
  private function sevenload_title($res=''){
    $this->data['description'] = '';
    $this->data['tags'] = '';
    if(empty($res)) return '';
    $this->data['description'] = $this->_og_description($res);
    $this->data['tags'] = $this->_meta_name('keywords',$res);
    return $this->_og_title($res);
  }

  /**************************
  * GOOGLE FUNCTIONS
  **************************/
  //which data needs additional http request
  private function google_data(){
    return array(
      'thumb' => false,
      'size' => false,
      'embed' => false,
      'iframe' => false,
      'url' => false,
      'site' => false,
      'title' => true
    );
  }
  //return http request url where to get data
  private function google_req(){
    return $this->google_url();
  }
  //return thumbnail
  private function google_thumb(){
    $arr = array();
    /*
    $tmp = 'http://video.google.com/docid='.$this->code;
    $arr['medium'] = $tmp;
    $arr['small'] = $tmp;
    $arr['large'] = $tmp;
    */
    return $arr;
  }

  //return size
  private function google_size($res){
    $arr = array();
    $arr['w'] = ($this->data['w'] < 0) ? $this->default_size['w'] : $this->data['w'];
    $arr['h'] = ($this->data['h'] < 0) ? $this->default_size['h'] : $this->data['h'];
    return $arr;
  }
  //return iframe url
  private function google_iframe(){
    return '';
  }
  //return embed url
  private function google_embed(){
    return $this->google_site.'/googleplayer.swf?docId='.$this->code;
  }
  //return canonical url
  private function google_url(){
    return $this->google_site.'/videoplay?docid='.$this->code;
  }
  //return website url
  private function google_site(){
    return 'http://video.google.com';
  }
  //return title
  private function google_title(){
    return $this->code;
  }

} //end of class
