<?php

// DEFINE CONSTANTS
define('IN_PRGM', true);
define('ROOT_PATH', '../../');

// INIT PRGM
@require(ROOT_PATH . 'includes/init.php');
@require_once(ROOT_PATH . 'includes/enablegzip.php');

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
$p2_articleid = GetVar('articleid', 0, 'whole_number');

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

echo '<html>
<head>
<base href="'.$sdurl.'" />
<meta http-equiv="Content-Type" content="text/html;charset='.$sd_charset.'" />
<title>' . $news->article['title'] . '</title>
<script type="text/javascript">
//<![CDATA[
var sdurl = "'.$sdurl.'";
//]]>
</script>
<link rel="stylesheet" href="'.$sdurl.'css.php" />
<link rel="stylesheet" href="'.$sdurl.'includes/css/ceebox.css" />
<script type="text/javascript" src="'.$sdurl.'includes/min/index.php?g=jq"></script>
<script type="text/javascript" src="'.$sdurl.'includes/javascript/jquery.ceebox-min.js"></script>
<script type="text/javascript" src="'.$sdurl.'includes/javascript/markitup/markitup-full.js"></script>
</head>
<body class="popup">
<div id="wrapper"><div id="content">
<div class="article_container" style="padding: 10px;">
';
//'.$news->p_prefix.'

$news->DisplayArticle($news->article, false);

?>
</div></div></div>
</body>
</html>