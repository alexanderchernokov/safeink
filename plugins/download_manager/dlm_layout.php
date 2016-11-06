<?php
// +---------------------------------------------+
// | Download Manager plugin for Subdreamer      |
// +---------------------------------------------+
// | v2.2.0, September 2013                      |
// | Maintainer: 2007-2013 tobias                |
// | This version requires Subdreamer 3.6+!      |
// +---------------------------------------------+

if(!defined('IN_PRGM') || !function_exists('GetPluginID') || !isset($dlm_currentdir)) exit();

/*
Enhanced Download Manager File Layout

This include runs in the context of the function "DisplayFiles" within
the file "class_dlm.php" of the Download Manager plugin.

Main data is in array-variable "$file" containing the file/version details.

The variable "$fileisimage" is TRUE, if the main file version is an image
and is set earlier in the function.
"$this->fileid" is used for detecting single-file or filelist view mode.

Note: This code relies on the variables included by the "global" section
of the "DisplayFiles" function, e.g. "DownloadManagerTools::GetPhrase()" and
"DownloadManagerTools::GetSetting"!
************************************************************************ */

  // **********************************************************************
  // Prepare variables and settings used for further (image) file handling
  // **********************************************************************
  $isindb = strlen($file['filesize']) && ($file['filetype']!='FTP') && !strlen($file['storedfilename']);
  $imgname = (strlen($file['storedfilename']) && ($file['filetype']!='FTP')) ? $file['storedfilename'] : $file['filename'];

  $file_title = empty($file['title']) ? DownloadManagerTools::GetPhrase('untitled') : $file['title'];
  $thumbwidth = (!empty($section['left_col_width']) ? (int)$section['left_col_width'] : (NotEmpty(DownloadManagerTools::GetSetting('thumbnail_display_width'))?intval(DownloadManagerTools::GetSetting('thumbnail_display_width')):0));
  $tmpurl = DownloadManagerTools::RewriteSectionLink($this->pageurl, $file['sectionid'], $this->section_arr['name']); //SD371
  $file_url = DownloadManagerTools::RewriteFileLink($tmpurl,$file['fileid'],sd_unhtmlspecialchars($file_title));

  if(NotEmpty(DownloadManagerTools::GetSetting('enable_seo')))
  {
    $detail_href = $file_url['link'];
  }
  else
  {
    $detail_href = RewriteLink('index.php?categoryid='.$categoryid.
                     $this->URI_TAG.'_sectionid='.$this->sectionid.
                     $this->URI_TAG.'_fileid='.$file['fileid']);
  }
  $out = '';
  $link2 = '';

  // Prepare download link
  if( !empty($file['licensed']) && NotEmpty(DownloadManagerTools::GetSetting('license_agreement')) )
  {
    $link2 = '<a href="' .
             $file_url['link'].
             (strpos($file_url['link'],'?')===false?'?':'&amp;').
             DownloadManagerTools::GetVar('HTML_PREFIX').'_versionid='.$file['currentversionid'].
             $this->URI_TAG.'_action=license">';
  }
  else
  {
    $link2 = '<a href="'.SITE_URL.$this->GETFILE.$categoryid.
             $this->URI_TAG.'_sectionid='.$this->sectionid.
             $this->URI_TAG.'_fileid='.$file['fileid'].
             $this->URI_TAG.'_forced=0'.
             $this->URI_TAG.'_versionid='.$file['currentversionid'] . '"'.
             ( (NotEmpty(DownloadManagerTools::GetSetting('thumbnails_new_window')) &&
                !empty($this->fileid) && $fileisimage) ? ' target="_blank"' : '') . '>';
  }

  // Set "Edit File" link variable (if user is admin or file's author)
  $editlink = '';
  if(DownloadManagerTools::GetVar('allow_admin') ||
     (NotEmpty(DownloadManagerTools::GetSetting('display_edit_link_for_files')) &&
      DownloadManagerTools::GetVar('allow_submit') && !empty($file['author']) &&
      !empty($userinfo['loggedin']) && ($userinfo['username'] == $file['author'])))
  {
    $editlink = '<a class="dlm-link-edit" href="' .
      $this->currenturl.
      (strpos($this->currenturl,'?')===false?'?':'&amp;').
      $this->HTML_PREFIX.'_action=submitfileform'.
      $this->URI_TAG.'_fileid='.$file['fileid'].
      '">' . DownloadManagerTools::GetPhrase('edit_file') . '</a>&nbsp;';
  }


  // Flag to indicate if the row containing "Edit File" / "Download Now"
  // links is to be handled (must be defaulted to TRUE!). This row is not
  // to be shown for single-file display of a "mirrored" file.
  $linkrowdisplay = true;

  // Is file actually an embeddable file?
  $embed = false;

  // ***** LAYOUT IS CONTAINED WITHIN IT'S OWN TABLE *****
  echo '
    <table class="dlm-table" cellpadding="0" cellspacing="0" border="0" summary="layout" width="100%" >
    ';

  // Prepare ratings html
  $ratings_html = '';
  if(function_exists('GetRatingForm') && DownloadManagerTools::GetSetting('display_ratings'))
  {
    if(empty($this->fileid))
    {
      $ratings_html = GetRatingForm('p'.$this->pluginid.'-'.$file['fileid'], $this->pluginid);
    }
    else
    {
      $ratings_html = GetRatingForm('p'.$this->pluginid.'-'.$this->fileid,   $this->pluginid, false);
    }
  }

  // *****************************************************
  //  Single-file view mode only?
  // *****************************************************
  if(!empty($this->fileid))
  {
    // TODO: image file is stored in MySQL, how display it?

    // Log the download (optionally for admins)
    if(!empty($file['is_embeddable_media']) &&
       (!DownloadManagerTools::GetVar('allow_admin') ||
        NotEmpty(DownloadManagerTools::GetSetting('count_admin_downloads'))))
    {
      $DB->query('UPDATE {p'.$this->pluginid.'_files} SET downloadcount = (IFNULL(downloadcount,0)+1)'.
                 ' WHERE fileid = %d', $this->fileid);
    }

    // Prepare links, differentiate between images and other file types
    $displaywidth = DownloadManagerTools::GetSetting('imagewidth_on_more_info_page');
    if(!$isindb && $fileisimage)
    {
      // If file itself is an image, prepare a link with
      // reasonable scaling to be displayed now.
      $width = $height = $displayheight = $displaywidth = empty($displaywidth) ? 450 : $displaywidth;
      if($fileisimage)
      {
        DownloadManagerTools::dlm_ScaleImage(DownloadManagerTools::$imagesdir . $imgname, $width, $height, $displaywidth, $displayheight);
        // A user-uploaded image probably is stored anonymously, e.g. "57C9D418-7EF1-41EA-BBAC-C2844CE491BD.dat".
        // In that case the IMG SRC needs to use the GETFILE link!
        if(substr($imgname,-4) == DownloadManagerTools::GetVar('DEFAULT_EXT'))
        {
          $imglink = SITE_URL.$this->GETFILE.$categoryid.
                     $this->URI_TAG.'_sectionid='.$this->sectionid.
                     $this->URI_TAG.'_fileid='.$file['fileid'].
                     $this->URI_TAG.'_forced=0'.
                     $this->URI_TAG.'_versionid='.$file['currentversionid'];
          $out = '<img src="' . $imglink . '" '.
                 '" alt="' . $file_title .
                 '" title="' . $file_title . ' - ' . DownloadManagerTools::GetPhrase('click_to_view').
                 '" width="'.$displaywidth.'" height="'.$displayheight.'" /></a>';
        }
        else
        {
          $imglink = DownloadManagerTools::$imageurl . $imgname;
          $out = '<img src="' . $imglink . '" alt="' . $file_title .
                 '" title="' . $file_title . ' - ' . DownloadManagerTools::GetPhrase('click_to_view').
                 '" width="'.$displaywidth.'" height="'.$displayheight.'" /></a>';
        }
      }
    }
    else
    {
      // File is NOT an image file, display either thumbnail or image.
      // "thumbnail" has precedence over "image" to keep traffic low.
      $imglink = !empty($file['image'])?'image':'';
      $imgcolumn = !empty($file['thumbnail'])?'thumbnail':(empty($file['image'])?'':'image');
      if(!empty($imgcolumn))
      {
        $out = !empty($imglink) ? DownloadManagerTools::dlm_GetImageAhref(
                  $file[$imglink],$file_title.' - '.DownloadManagerTools::GetPhrase('click_to_view'),'',
                  (!NotEmpty(DownloadManagerTools::GetSetting('thumbnails_new_window'))?'':' target="_blank" ')) : '';
        $out .= DownloadManagerTools::dlm_GetFileImageAsThumb($file, $imgcolumn, $displaywidth, $displaywidth, $file_title) .
                (!empty($imglink) ? '</a>' : '');
      }
    }

    // ########################################################################
    // EMBED MEDIA FILE (either remote media site link or local)
    // ########################################################################
    $embed = false;
    if(!empty($file['is_embeddable_media']) && !empty($file['embedded_in_details']))
    {
      if(($file['filetype'] == 'FTP') && ($embed = DownloadManagerTools::dlm_LinkAsMedia($file['filename'])))
      {
        $embed = '<div class="dlm-embed">'. $embed. '</div>';
      }
      else
      {
        $mediahref = SITE_URL . $this->GETFILE . $categoryid.
                     $this->URI_TAG.'_sectionid='.$this->sectionid.
                     $this->URI_TAG.'_fileid='.$file['fileid'].
                     $this->URI_TAG.'_forced=0'.
                     $this->URI_TAG.'_versionid='.$file['currentversionid'] . '&amp;file='.$file['filename'];

        if($ext = DownloadManagerTools::dlm_GetFileExtension($file['filename']))
        {
          $file_thumb = empty($file['thumbnail']) ? '' : DownloadManagerTools::$imageurl . $file['thumbnail'];
          $embed_width  = empty($file['embed_width'])  ? 560 : (int)$file['embed_width'];
          $embed_height = empty($file['embed_height']) ? 350 : (int)$file['embed_height'];
          $isAudio = in_array($ext, DownloadManagerTools::$KnownAudioTypes);

          /* JPlayer supported file types:
             HTML5: mp3, m4a (AAC), m4v (H.264), ogv*, oga*, wav*, webm*
             Flash: mp3, m4a (AAC), m4v (H.264)
          */
          $isJP = in_array($ext, array('mp3','m4a','m4v','oga','ogv','wav','webm'));
          $isVJ = in_array($ext, array('flv','mp4','webm','ogv','m3u8'));
          $osm_path = 'includes/javascript/osm';

          // OSM Player - http://mediafront.org/
          if(file_exists(ROOT_PATH.$osm_path.'/OSMPlayer.php') &&
             in_array($ext, array('ogv', 'oga', 'flv', 'rtmp', '3g2', 'mp3', 'm4a', 'aac', 'wav', 'aif', 'wma')))
          {
            // mp4: no sound with "sintel" sample!
            $osm_url = SITE_URL . $osm_path;
            $flash_url = $osm_url.'/flash/mediafront.swf';

            include_once(SD_INCLUDE_PATH.'javascript/osm/OSMPlayer.php');
            $dlm_osm_player = new OSMPlayer(array(
              'width' => $embed_width,
              'height' => $embed_height,
              'autostart' => ($file['media_autoplay']?true:false),
              'baseURL' => $osm_url,
              'disableplaylist' => true,
              'disableembed' => true,
              'disablemenu' => true,
              'flashPlayer' => $flash_url,
              'playerPath' => $osm_path,
              'playerURL' => $osm_url,
              'embedWidth' => ($embed_width-20),
              'embedHeight' => ($embed_height-20),
              'file' => $mediahref,
              'id' => 'dlm_player_'.$file['fileid'],
              'logo' => '',
              'image' => $file_thumb,
              'link' => $detail_href,
              'prefix' => 'dlm_',
              'resizable' => true,
              'showPlaylist' => false,
              'showInfo' => false,
              'theme' => 'dark-hive',
              'template' => 'simpleblack',
              'server' => 'sd'
            ));

            $embed = $dlm_osm_player->getPlayer().'<br />
        <script type="text/javascript">
        // <![CDATA[
        jQuery(document).ready(function() {
          jQuery("#mediafront_information").remove();
        });
        // ]]>
        </script>
            ';
          }
          else
          // jQuery-media Plugin - http://malsup.com/jquery/media/
          if(in_array($ext, array(/*'flv','mp3',*/'avi','mpg','mov','ram','swf','wmv','3g2','au','aac','aif','gsm','midi','rm','wma','xaml')))
          {
            $embed = '
        <a class="dlm-mediafile" href="'.$mediahref.'"></a>
        <script type="text/javascript">
        // <![CDATA[
        jQuery(document).ready(function() {
          jQuery.fn.media.defaults.flvPlayer = "'.SITE_URL.'includes/javascript/moxieplayer.swf";
          jQuery.fn.media.defaults.mp3Player = "'.SITE_URL.'includes/javascript/moxieplayer.swf";
          jQuery("a.dlm-mediafile").media({
            width:     '.$embed_width.',
            height:    '.$embed_height.',
            autoplay:  '.($file['media_autoplay']?'true':'false').',
            params:    { "allowFullScreen": "true", "allowscriptaccess": "always" },
            bgColor:   "#000"
          });
        });
        // ]]>
        </script>
        ';
          }
          else
          if($isVJ)
          {
            // Video.js - http://videojs.com/

            //$flash_url = 'http://releases.flowplayer.org/swf/flowplayer-3.2.7.swf';
            //$flash_url = SITE_URL.'includes/javascript/moxieplayer.swf';
            $flash_url = SITE_URL.'includes/javascript/flowplayer-3.2.7.swf';

            $poster = empty($file_thumb) ? '' : 'poster="'.$file_thumb.'" ';
            switch($ext) {
              case 'webm': $ftype = $file['filetype'].'; codecs="vp8, vorbis"'; break;
              case 'ogv' : $ftype = $file['filetype'].'; codecs="theora, vorbis"'; break;
              case 'mp4' : $ftype = $file['filetype'].'; codecs="avc1.42E01E, mp4a.40.2"'; break;
              default    : $ftype = $file['filetype'];
            }
            //for Mediaplayer:
            //$flashvars = '<param name="flashvars" value="file='.urlencode($mediahref).($file_thumb?'&image='.$file_thumb:'').'" />';
            //for FlowPlayer:
            $flashvars = '<param name="flashvars" value=\'config={"clip":[{"metaData":false,"urlEncoding":true}],"playlist":['.
                          ($file_thumb?'"'.$file_thumb.'", ':'').
                          '{"url": "'.urlencode($mediahref).'","autoPlay":'.($file['media_autoplay']?'true':'false').',"autoBuffering":true}]}\' />';

            $embed = "
        <div class='video-js-box'>
        <video class=\"video-js\" ".($file['media_autoplay']?'autoplay ':'')."controls=\"controls\" $poster width=\"$embed_width\" height=\"$embed_height\">
          <source src=\"$mediahref\" type=\"$ftype\" />
          <object class=\"vjs-flash-fallback\" data=\"$flash_url\" width=\"$embed_width\" height=\"$embed_height\" type=\"application/x-shockwave-flash\">
            <param name=\"autoPlay\" value=\"".($file['media_autoplay']?'true':'false')."\",
            <param name=\"movie\" value=\"$flash_url\" />
            <param name=\"quality\" value=\"high\" />
            <param name=\"allowFullScreen\" value=\"true\" />
            <param name=\"allowscriptaccess\" value=\"always\" />
            <param name=\"wmode\" value=\"transparent\" />
            ".$flashvars."
            <img alt=\"Poster Image\" src=\"".$file_thumb."\" width=\"$embed_width\" height=\"$embed_height\" title=\"No video playback capabilities, please download the video below.\" />
          </object>
        </video>
        <p class='vjs-no-video'><strong>Download video:</strong> <a href=\"$mediahref\">$ext format</a></p>
        </div>
        <script type=\"text/javascript\">
        // <![CDATA[
          jQuery(document).ready(function() { VideoJS.setupAllWhenReady(); });
        // ]]>
        </script>";
          }
          else
          if($isJP)
          {
            // jPlayer jQuery Plugin - http://www.jplayer.com/
            $mediahref = $ext . ": '$mediahref', ";
            $file_thumb = empty($file['thumbnail']) ? '' : 'poster: "'.DownloadManagerTools::$imageurl . $file['thumbnail'].'"';

            $playerid = 'dlm-'.$this->pluginid.'-'.$file['fileid'];

            $embed = DownloadManagerTools::GetSetting($isAudio ? 'jplayer_audio_html' : 'jplayer_video_html');
            $embed = str_replace(array('%player_id%', '%audio_class%', '%video_class%'),
                                 array($playerid, $file['audio_class'], $file['video_class']), $embed);

            if($isAudio)
            {
              $embed .= '<style type="text/css">.jp-player { height: '.$embed_height.'px }</style>';
            }
            $embed .= '
<script type="text/javascript">// <![CDATA[
jQuery(document).ready(function() {
  jQuery("#'.$playerid.'").jPlayer({
    swfPath: "'.SITE_URL.'includes/javascript",
    solution: "flash,html", supplied: "'.$ext.'",
    cssSelectorAncestor: "",
    errorAlerts: false, warningAlerts: false,
    ready: function(){
      jQuery(this).jPlayer("setMedia", {
        '.$mediahref. $file_thumb.'
      })'.($file['media_autoplay']?'.jPlayer("play")':'').';
    },
    ended: function(event){
      '.($file['media_loop']?'$(this).jPlayer("play");':'').'
    }
  });
});
// ]]></script>
';
          }
        }
      }
    }

    // Display table with file image and file details.
    // If the file itself IS an image, then display everything
    // below it in the same column.
    if(($fileisimage && !$isindb) || empty($out) || !empty($embed))
    {
      // File IS an image and is stored in the local filesystem
      // -OR- there is no thumbnail set ($out empty)
      // Display file title and below it the file itself as an
      // (scaled) IMAGE.
      echo '
      <tr>
      <td class="dlm-file-title-td" align="left" colspan="2">
        <a class="dlm-title-link" href="'.$this->currenturl.'" >'.$file_title.'</a>
        ';

      if($embed)
      {
        echo $embed;
      }
      else
      {
        if($fileisimage)
        {
          echo '<a href="'.$imglink.'" target="_blank">' . $out;
        }
        else
        {
          if(!empty($out))
          {
            echo '<a href="'.DownloadManagerTools::dlm_GetSeparator('', true) . $link2;
          }
        }
      }
      echo '
      </td>
      </tr>';
      // Add dummy row to have the 1'st column be sized correctly
      // for the following rows (e.g. file details etc.)
      //echo '<tr><td colspan="2" style="min-height: 1px; padding: 0px; margin: 0px;" width="'.$thumbwidth.'">&nbsp;</td></tr>';
    }
    else
    {
      // ELSE: File is NOT an image or file is stored in MySQL.
      // Only thumbnail (if exists) and title are displayed
      $rowspan = 13;
      if(DownloadManagerTools::GetSetting('display_ratings') && !empty($userinfo['commentaccess']) && !empty($section['enable_ratings']))
      {
        $rowspan++;
      }

      echo '
      <tr>
      <td class="dlm-left-column" rowspan="'.$rowspan.'" align="center" valign="top" '.
        ($thumbwidth>0?'width="'.$thumbwidth.'"':'').' >
        ' .$out . '
      </td>
      <td class="dlm-file-title-td" colspan="2" align="left">
      ';
      if(DownloadManagerTools::GetSetting('enable_details_page'))
      {
        echo '<a class="dlm-title-link" href="' .
          #RewriteLink('index.php?categoryid='.$categoryid.$this->URI_TAG.'_sectionid='.$this->sectionid).
          $this->currenturl.
          '">' . $file_title . '</a>';
      }
      else
      {
        echo '<span class="dlm-title-link">' . $file_title . '</span>';
      }
      echo '
      </td>
      </tr>
      ';
    }

    // Display file description below image in extra row
    if(NotEmpty(DownloadManagerTools::GetSetting('description_below_title')) && !empty($file['description']))
    {
      echo '
      <tr>
        <td class="dlm-file-description" align="left" colspan="2" valign="top">';
      $file['description'] = preg_replace('#\{pagebreak\}#is', '', $file['description']); //SD370
      // Apply BBCode parsing
      if($this->allow_bbcode && isset($bbcode))
      {
        $file['description'] = $bbcode->Parse($file['description']);
      }
      echo $file['description'] . '
        </td>
      </tr>';
    }

    // "dlm_DisplayFileDetailsTable" shows the details by creating it's own
    // <TR> elements to the current table.
    $detailcount = 0;
    DownloadManagerTools::dlm_DisplayFileDetailsTable($detailcount, $file, false, !empty($embed));

    // ######################## Display Tags ##################################
    if(DownloadManagerTools::GetSetting('display_tags') &&
       !empty($file['tags']) && is_string($file['tags']))
    {
      echo '
      <tr class="dlm-detail-row'.(1+($detailcount % 2)).'">
        <td class="dlm-detail-name">'.DownloadManagerTools::GetPhrase('dlm_tags') . '</td>
        <td class="dlm-detail-value">';

      $file['tags'] = preg_replace('/[\s+]/',' ',$file['tags']);
      $ftags = array_unique(explode(',',$file['tags']));
      foreach($ftags as $tag)
      {
        $tag = rtrim(ltrim($tag));
        if(!empty($tag))
        {
          $enc = urlencode(str_replace('&amp;','&', $tag)); //SD370
          echo '<a href="'.RewriteLink('index.php?categoryid='.$categoryid.
          $this->URI_TAG.'_searchby=tags'.$this->URI_TAG.'_searchtext='.$enc).'">'.$tag.'</a> ';
        }
      } //foreach

      echo '</td>
      </tr>';
      $detailcount++;
    }

    if(DownloadManagerTools::GetSetting('display_ratings'))
    {
      echo '
      <tr class="dlm-detail-row'.(1+($detailcount % 2)).'">
        <td class="dlm-detail-name">'.DownloadManagerTools::GetPhrase('rating') . '</td>
        <td class="dlm-detail-value">
        '.$ratings_html.'
        </td>
      </tr>';
      $detailcount++;

    } //Display Ratings

    // Single file display: if file itself is an image, it goes
    // into the 1'st column *alone*, otherwise all in 1'st

    $newrow = '
      <tr>
        <td class="dlm-file-versions" align="left" colspan="2" valign="middle">';

    // ONLY add a new table row for "mirrored" file if the "Edit Link"
    // is set since otherwise this row does NOT show the regular
    // "Download now" link. Mirror files for single-file page are
    // displayed separately within function "DisplayFileVersions"!
    if($file['standalone'] >= 2) // "Standalone"==1/"Mirror"==2
    {
      if(!empty($editlink))
      {
        echo $newrow;
      }
      else
      {
        $linkrowdisplay = false;
      }
    }
    else // "Default" or "Standalone" do require new row here
    {
      echo $newrow;
    }
  }
  else
  // ##########################################################################
  // Display file in list for current section
  // ##########################################################################
  {
    if(!empty($file['thumbnail']))
    {
      $imglink = !empty($file['image'])?'image':'';
      $imgcolumn = !empty($file['thumbnail']) ? 'thumbnail' : (empty($file['image']) ? '' : 'image');
      if(!empty($imgcolumn))
      {
        $out = '';
        if(strlen($imglink))
        {
          $out = DownloadManagerTools::dlm_GetImageAhref($file[$imglink], $file_title.' - '.
                   DownloadManagerTools::GetPhrase('click_to_view'),'',
                   (!NotEmpty(DownloadManagerTools::GetSetting('thumbnails_new_window'))?'':' target="blank" '));
        }
        $out .= DownloadManagerTools::dlm_GetFileImageAsThumb($file, $imgcolumn, $thumbwidth, $thumbwidth, $file_title);
        if(strlen($imglink))
        {
          $out .= '</a>';
        };
      }
    }
    else if(!empty($file['image']))
    {
      $text = $file_title . ' - ';
      if($fileisimage)
      {
        $text .= DownloadManagerTools::GetPhrase('click_to_view');
      }
      else
      {
        $text .= DownloadManagerTools::GetPhrase('download_now');
      }
      $out = $link2 .
             DownloadManagerTools::dlm_GetFileImageAsThumb($file, 'image',
               $thumbwidth, $thumbwidth, $text) . '</a>';
    }
    else if($fileisimage && !$isindb)
    {
      $width = $height = $displaywidth = $displayheight = $thumbwidth;
      DownloadManagerTools::dlm_ScaleImage(DownloadManagerTools::$filesdir . $imgname,
        $width, $height, $displaywidth, $displayheight);
      $out = $link2 . // $link2 includes "<a href="
             '<img src="' . DownloadManagerTools::$filesdir . $imgname .
             '" alt="' . $file_title .
             '" title="' . $file_title . ' - ' .
             strip_tags(DownloadManagerTools::GetPhrase('click_to_view')). '"'.
             (!empty($displaywidth) ? ' width="'.$displaywidth.'"':'').
             (!empty($displayheight) ? ' height="'.$displayheight.'"':'').
             ' /></a>';
    }

    // Determine description output
    $descr = false;
    if(NotEmpty(DownloadManagerTools::GetSetting('file_description_in_downloads')) &&
       NotEmpty(DownloadManagerTools::GetSetting('description_below_title')))
    {
      $descr = $file['description'];
      // Apply BBCode parsing
      if($this->allow_bbcode)
      {
        $descr = $bbcode->Parse($descr);
      }

      //SD370: support for "{pagebreak}" within description
      $addmore = false;
      $descrpages = preg_split('/{(pagebreak)\s*(.*?)}/i', $descr);
      if(!empty($descrpages) && (count($descrpages) > 1))
      {
        $descr = $descrpages[0];
        $addmore = true;
      }
      else
      {
        $descrlen = sd_strlen($descr);
        if($descr > 194)
        {
          $descr = sd_substr($descr,0,190);
          $addmore = true;
        }
      }
      $descr = preg_replace('#(\A[\s]*<br[^>]*>[\s]*|' // remove <br/> at beginning of the string
               .'<br[^>]*>\s*(?=(?:\{pagebreak\}))|'   // remove <br/> before {pagebreak}
               .'(?<=(?:\{pagebreak\}))\s*<br[^>]*>|'  // remove <br/> after {pagebreak}
               .'<br[^>]*>[\s]*\Z)#is', '', $descr);   // remove <br/> at end of string
      if($addmore)
      {
        $descr .= '<br /><a href="'.$detail_href.
                  '"><span class="dlm-more-link">'.
                  DownloadManagerTools::GetPhrase('more').'</span></a>';
      }
    }

    // Init # of detail rows
    $detailcount = 0;

    // Determine file details output
    $filedetails = false;
    if(!DownloadManagerTools::GetSetting('details_only_on_more_info_page'))
    {
      $filedetails = DownloadManagerTools::dlm_DisplayFileDetailsTable($detailcount, $file, false, $file['is_embeddable_media'],true);
    }

    // Determine tags output
    $tags_html = false;
    $tags_row  = ''; // suffix for CSS class
    if(DownloadManagerTools::GetSetting('display_tags') && strlen($file['tags']))
    {
      $tags_row = 1+($detailcount % 2);
      $detailcount++;
      $file['tags'] = str_replace('&amp;','&', $file['tags']); //v2.2.0
      $file['tags'] = str_replace(';',',',$file['tags']);
      $ftags = explode(',',$file['tags']);
      foreach($ftags as $tag)
      {
        $tag = rtrim(ltrim($tag));
        if(!empty($tag))
        {
          $enc = urlencode(str_replace('&amp;','&', $tag)); //v2.2.0
          $tags_html .= '<a href="'.RewriteLink('index.php?categoryid='.$categoryid.$this->URI_TAG.
                        '_searchby=tags'.$this->URI_TAG.'_searchtext='.$enc).'">'.$tag.'</a> ';
        }
      } //foreach
    }

    // Left-column thumbnail or message
    $thumb_html = empty($out) ?
                    DownloadManagerTools::GetSetting('message_no_thumbnail_available') :
                    (strpos($out,'<a')!==false ? $out :'<a href="'.$detail_href.'">'.$out.'</a>');

    // ########################### SMARTY INTEGRATION #########################
    $smarty->assign('left_column_width',($thumbwidth >= 0 ? ' style="width: '.($thumbwidth+5).'px"':''));
    $smarty->assign('left_column_thumb_html', $thumb_html);
    $smarty->assign('detail_href',  $detail_href);
    $smarty->assign('file_title',   $file_title);
    $smarty->assign('file_descr',   $descr);
    $smarty->assign('file_details', $filedetails);
    $smarty->assign('padtop',       $filedetails ? 5 : 0);
    $smarty->assign('tags_html',    $tags_html);
    $smarty->assign('tags_row',     $tags_row);
    $smarty->assign('tags_phrase',  DownloadManagerTools::GetPhrase('dlm_tags'));
    $smarty->assign('ratings_html', $ratings_html);

    if(!empty($file['is_embeddable_media']) && !empty($file['embedded_in_list']) &&
       ($embed = DownloadManagerTools::dlm_LinkAsMedia($file['filename'])))
    {
      $smarty->assign('embed_on', true);
      $smarty->assign('embed_html', $embed);
    }
    else
    {
      $smarty->assign('embed_on', false);
      $smarty->assign('embed_html', '');
    }
    $smarty->display('dlm_list1.tpl');


  } //isset($this->fileid)-else


  // **************************************************************
  // Display Edit File, Download Now and More Info links if needed
  // **************************************************************

  if($linkrowdisplay)
  {
    if(empty($file['filesize']) ||
       (empty($file['is_embeddable_media']) && ($file['standalone'] >= 2) && !empty($this->fileid)))
    {
      echo $editlink;
    }
    else
    if(!empty($file['filesize']) || (substr(strtolower($file['filename']), 0, 3) == 'www') || (substr(strtolower($file['filename']), 0, 4) == 'http') )
    {
      echo strlen($editlink) ? $editlink . '&nbsp;&nbsp;' : '';
      // For "Standalone"-configured files don't show "Download Now" image/link at all,
      // since each file needs it's own link.
      if(($file['standalone'] != '1') && (empty($file['is_embeddable_media']) || !empty($file['media_download'])))
      {
        if(empty($file['is_embeddable_media']) && !empty($file['licensed']) && (NotEmpty(DownloadManagerTools::GetSetting('license_agreement'))))
        {
          echo '
          <a class="dlm-link-download" href="' .
             $file_url['link'].
             (strpos($file_url['link'],'?')===false?'?':'&amp;').
             DownloadManagerTools::GetVar('HTML_PREFIX').'_versionid='.$file['currentversionid'].
             $this->URI_TAG.'_action=license">';
        }
        else
        {
          echo '
          <a class="dlm-link-'.($file['is_embeddable_media'] ? 'open' : 'download').'" href="'.
            SITE_URL.$this->GETFILE.$categoryid.
            $this->URI_TAG.'_sectionid='.$this->sectionid.
            $this->URI_TAG.'_fileid='.$file['fileid'].
            $this->URI_TAG.'_versionid='.$file['currentversionid'] . '" target="_blank">';
        }

        // "Download Now" image configurable in plugin settings
        echo ($file['is_embeddable_media'] ?
              DownloadManagerTools::GetPhrase('dlm_open_link') :
              DownloadManagerTools::GetPhrase('download_now')) . '</a>';
      }
    }
  }

  // If we are NOT viewing a specific file, then show the 'more info' link
  // The "More Info" image itself is configurable in the plugin settings.
  if(!isset($this->fileid) && DownloadManagerTools::GetSetting('enable_details_page') &&
     ($fileisimage || DownloadManagerTools::GetSetting('show_more_info_for_images')))
  {
    // Either display "-" for non-"Standalone" files OR the Edit link
    if(($file['standalone'] != '1') || !empty($editlink))
    {
      echo '&nbsp;&nbsp;';
    }
    $link = DownloadManagerTools::RewriteFileLink($this->currenturl, $file['fileid'],$file['title']);
    echo '
         <a class="dlm-link-details" href="'.
         //v2.2.0: SEO URL
         $link['link'].'">'.
         DownloadManagerTools::GetPhrase('more_info') . '</a><br />';
  }

  if(!empty($this->fileid))
  {
    if($linkrowdisplay)
    {
      echo '</td></tr>';
    }
  }
  else
  {
    //???
    echo '</td></tr>'; // close the "links" cell
    // This closes file listing, the 2'nd column and table
    echo '</table>
        </td>
      </tr>
      ';
  }

  // Close main table now
  echo '</table>';

  if(!NotEmpty(DownloadManagerTools::GetSetting('description_below_title')) &&
     ((!empty($this->fileid) || DownloadManagerTools::GetSetting('file_description_in_downloads')) &&
      ($file['description'] != '<br>') && ($file['description'] != '<br />') && strlen(rtrim($file['description'])))
    )
  {
    // Display file description below image in extra tabled row
    echo '
    <!-- FILE DESCRIPTION -->
  <table><tr><td class="dlm-file-description" style="padding: 0px 0px 10px 0px;" align="left" colspan="2" valign="top" >'.
  DownloadManagerTools::GetPhrase('description2') . '<br />';

  // Apply BBCode parsing
  if($this->allow_bbcode)
  {
    $file['description'] = str_replace('&lt;br /&gt;',"\r\n",$file['description']);
    $file['description'] = $bbcode->Parse($file['description']);
  }
  else
  {
    $file['description'] = str_replace('&lt;br /&gt;','<br />',$file['description']);
  }

  echo $file['description'] .
   '</td>
  </tr></table>
  ';
  }

?>