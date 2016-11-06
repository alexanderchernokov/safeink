<?php
   include("OSMPlayer.php");
   $player = new OSMPlayer(array(
      //'playlist' => 'playlist.xml'
      'file' => 'http://127.0.0.1:8080/sdcom/plugins/download_manager/getfile.php?categoryid=8&amp;p5001_sectionid=10&amp;p5001_fileid=126&amp;p5001_forced=0&amp;p5001_versionid=137&file=video.flv',
      'disablePlaylist' => true
   ));
?>
<html>
   <head>
      <title>Open Standard Media (OSM) Player: PHP Demo</title>
      <script type="text/javascript" src="../jquery-1.4.4.min.js"></script>
      <?php print $player->getHeader(); ?>
   </head>
   <body>
      <h2>Open Standard Media (OSM) Player</h2><br/>
      <?php print $player->getPlayer(); ?>
   </body>
</html>