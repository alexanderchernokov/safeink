<?php
// DEFINE CONSTANTS
define('IN_PRGM', true);
define('ROOT_PATH', '../../');

// INIT PRGM
if(!@require(ROOT_PATH . 'includes/init.php')) return;
if(!class_exists('ArticlesClass'))
{
  @require_once(SD_INCLUDE_PATH . 'class_articles.php');
}
if(!class_exists('ArticlesClass')) return;
$plugin_folder = sd_GetCurrentFolder(__FILE__);
$news = new ArticlesClass($plugin_folder);

// CHECK ACCESS
if(empty($userinfo['pluginviewids']) || !@in_array($news->pluginid,$userinfo['pluginviewids']))
{
  echo $sdlanguage['no_view_access'];
  $DB->close();
  exit();
}

// DISPLAY ARTICLE
$p2_articleid = GetVar($news->p_prefix.'articleid', 0, 'whole_number');

if(!$p2_articleid)
{
  $DB->close();
  exit;
}

$news->GetArticle($p2_articleid);
if(empty($news->article) || ($news->article['settings'] & 2 != 2))
{
  $DB->close();
  exit;
}

$pagesize = !empty($news->settings['pdf_page_size']) ? strtoupper($news->settings['pdf_page_size']) : 'LETTER';

// use global settings for this article?
if($news->article['settings'] & ArticlesClass::$articlebitfield['useglobalsettings'])
{
  $news->article['settings'] = $news->globalsettings;
}

$DoPdf = !empty($userinfo['loggedin']) && isset($_GET['pdf']);

$header = '<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset='.$sd_charset.'" />
<title>' . $news->article['title'] . '</title>
<script language=javascript>
<!--
  function PrintPage() {
    window.print();
  }
//-->
</script>
<base href="' . $sdurl . '">
</head>
<body onload=PrintPage()>';

// Fetch HTML header output
ob_start();

// display title?
if($news->article['settings'] & ArticlesClass::$articlebitfield['displaytitle'])
{
  echo '<strong>' . ($news->article['title']) . '</strong>';
}

// display author?
if($news->article['settings'] & ArticlesClass::$articlebitfield['displayauthor'])
{
  echo '<br />' . ($news->language['by'] . ' ' . $news->article['author']);
}

// display published date?
if($news->article['settings'] & ArticlesClass::$articlebitfield['displaycreateddate'])
{
  echo '<br />' . ($news->language['published']) . ' ' .
       ($news->article['datestart'] != 0 ?  DisplayDate($news->article['datestart']) : DisplayDate($news->article['datecreated']));
}

// display updated date?
if( ($news->article['settings'] & ArticlesClass::$articlebitfield['displayupdateddate']) && ($news->article['dateupdated'] != 0) )
{
  echo '<br />' . ($news->language['updated'] . ' ' . DisplayDate($news->article['dateupdated']));
}

// display description?
if($news->article['settings'] & ArticlesClass::$articlebitfield['displaydescription'])
{
  if(strlen($news->article['description']))
  {
    echo '<br /><br />' . ($news->article['description']);
  }
}

// output article
echo '<br /><br />';
$text = str_replace('{pagebreak}', '', $news->article['article']);
//SD342: obey excerpt mode
if(!($news->article['settings'] & ArticlesClass::$articlebitfield['ignoreexcerptmode']) &&
   ($output = CheckExcerptMode($text, isset($news->article['settings']['excerpt_mode_length'])?(int)$news->article['settings']['excerpt_mode_length']:0)))
{
  echo $output['content']. '<br /><br /><strong>'.$output['message'].'</strong>';
}
else
{
  echo $text;
}

$body_end = '</body></html>';

$content = ob_get_clean();

if(!$DoPdf)
{
  echo $header . $content . $body_end;
}
else
{
  // conversion HTML => PDF
  @require_once(SD_INCLUDE_PATH.'/html2pdf/html2pdf.class.php');
  try
  {
    // Fix images to have full path:
    $content = preg_replace('#<img (.+)?src="images/#','<img src="'.SITE_URL.'/images/', $content);
    $content = preg_replace('#<script(.+)</script>#s','', $content);
    $content = preg_replace('#<object(.+)</object>#s','', $content);
    $content = preg_replace('#<embed(.+)</embed>#s','', $content);

    define('mL', 20);
    define('mT', 20);
    define('mR', 20);
    define('mB', 20);
    $is_utf8 = strtolower($mainsettings['charset']) == 'utf-8';
    $html2pdf = new HTML2PDF('P', $pagesize, 'en', $is_utf8, $mainsettings['charset'], array(mL, mT, mR, mB));
    $html2pdf->pdf->SetDisplayMode('real');
    $html2pdf->setDefaultFont('freesans'); //SD343: freesans, not helvetica for non-latin characters
    if(!$is_utf8)
    {
      $news->article['author'] = utf8_decode($news->article['author']);
      $news->article['title'] = utf8_decode($news->article['title']);
      $news->article['metakeywords'] = utf8_decode($news->article['metakeywords']);
      $mainsettings_websitetitle = utf8_decode($mainsettings_websitetitle);
      $content = utf8_decode($content);
    }
    $html2pdf->pdf->SetAuthor($news->article['author']);
    $html2pdf->pdf->SetSubject($news->article['title']);
    $html2pdf->pdf->SetKeywords($news->article['metakeywords']);
    $html2pdf->writeHTML('<html><body><strong>'.($mainsettings_websitetitle).'</strong> - '.SITE_URL.
      '<hr>'.$content.$body_end, false);

    header("Content-type: application/pdf");

    $html2pdf->Output(strip_alltags($news->article['title']).'.pdf');
  }
  catch(HTML2PDF_exception $e) {
    echo '<h3 style="border: 1px solid #0000FF; width: 50%; padding: 20px;">'.$news->language['pdf_print_failed'].'</h3>';
    if(!empty($userinfo['adminaccess']))
    {
      echo $e;
    }
  }
}

if(isset($DB) && $DB->conn)
{
  $DB->close();
}
