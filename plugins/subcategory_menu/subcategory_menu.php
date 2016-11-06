<?php

if(!defined('IN_PRGM')) return false;

if(!class_exists('SD_SubcategoryMenu'))
{
class SD_SubcategoryMenu
{
  private $language = array();
  private $settings = array();
  private $pluginid = false;
  private $hasViewPerms = false;
  private $hasMenuPerms = false;

  public function __construct()
  {
    global $userinfo;
    if($this->pluginid = GetPluginIDbyFolder(sd_GetCurrentFolder(__FILE__)))
    {
      $this->language = GetLanguage($this->pluginid);
      $this->settings = GetPluginSettings($this->pluginid);
      $this->hasViewPerms = !empty($userinfo['categoryviewids']);
      $this->hasMenuPerms = !empty($userinfo['categorymenuids']);
    }
  }

  function DisplaySubcategoryMenu()
  {
    global $DB, $categoryid, $sdurl, $userinfo;

    if(empty($userinfo['pluginviewids']) || !in_array($this->pluginid,$userinfo['pluginviewids']))
    {
      return false;
    }

    // get subcategories
    $subcategories = $DB->query('SELECT categoryid, name, link, image, hoverimage FROM {categories}
                                 WHERE parentid = %d ORDER BY displayorder', $categoryid);
    $subcategoryrows = $DB->get_num_rows($subcategories);

    // Are we in a subcategory?
    if(!$subcategoryrows)
    {
      $parentcategory = $DB->query_first('SELECT parentid FROM {categories} WHERE categoryid = %d', $categoryid);

      if(!empty($parentcategory['parentid']))
      {
        unset($subcategories);
        $subcategories = $DB->query('SELECT categoryid, name, link, image, hoverimage FROM {categories}
                                     WHERE parentid = %d ORDER BY displayorder', $parentcategory['parentid']);

        $subcategoryrows = $DB->get_num_rows($subcategories);
      }
    }
    if(!$subcategoryrows) return;

    // display custom css?
    if($this->settings['custom_link_styles'])
    {
      echo '
      <!-- StartSubcategoryCustomCSS -->
      <style type="text/css">
      a.p'.$this->pluginid.'css:link    { ' . $this->settings['default_link_style'] . ' }
      a.p'.$this->pluginid.'css:active  { ' . $this->settings['active_link_style'] . ' }
      a.p'.$this->pluginid.'css:visited { ' . $this->settings['visited_link_style'] . ' }
      a.p'.$this->pluginid.'css:hover   { ' . $this->settings['hover_link_style'] . ' }
      </style>
      <!-- EndSubcategoryCustomCSS -->';
    }
    echo '
      <div id="subcategory_menu">';

    // display subcategories
    for($i = 0; $subcategory = $DB->fetch_array($subcategories); $i++)
    {
      $menu_perm = $this->hasMenuPerms && @in_array($subcategory['categoryid'], $userinfo['categorymenuids']);
      $view_perm = $this->hasViewPerms && @in_array($subcategory['categoryid'], $userinfo['categoryviewids']);
      switch($this->settings['display_usergroup_permission'])
      {
        case 1 :
          $display = $view_perm;
          break;
        case 2 :
          $display = $menu_perm && $view_perm;
          break;
        default :
          $display = $menu_perm;
      }

      if($display)
      {
        $subcategoryname = $subcategory['name'];
        if(!empty($this->settings['enable_rollover_effect']))
        {
          if(isset($subcategory['image']) && (strlen($subcategory['image'])>4))
          {
            // hover image
            if(isset($subcategory['hoverimage']) && (strlen($subcategory['hoverimage'])>4))
            {
              $subcategoryname = '<img name="sdhover'.$subcategory['categoryid'].'" src="'.$sdurl.'images/'.
                $subcategory['image'].'" style="border:none" alt="'.htmlspecialchars($subcategory['name'],ENT_COMPAT).'" '.
                'onMouseOver="Rollover('.$subcategory['categoryid'].', \''.$sdurl.'images/'.$subcategory['hoverimage'].'\', true)" '.
                'onMouseOut="Rollover('.$subcategory['categoryid'].', \''.$sdurl.'images/'.$subcategory['image'].'\', false)" />';
            }
            else
            {
              $subcategoryname = '<img src="./images/'.$subcategory['image'].'" alt="'.
                htmlspecialchars($subcategory['name'],ENT_COMPAT).'" style="border:none" />';
            }
          }
        }

        $subcategorylink = (isset($subcategory['link']) && (strlen($subcategory['link'])>4)) ? $subcategory['link'] : RewriteLink('index.php?categoryid=' . $subcategory['categoryid']);

        echo '<a ' . ($this->settings['custom_link_styles'] ? 'class="p'.$this->pluginid.'css"' : '') . ' href="' . $subcategorylink . '">' . $subcategoryname . "</a>\n";

        if( ($i + 1) != $subcategoryrows)
        {
          echo $this->settings['subcategory_separator'];
        }
      }
      else
      {
        $i--;
      }
    } //for

    echo '
    </div>
    ';

  } // end of function - DO NOT REMOVE!

} // end of class
} // NO EXISTS - DO NOT REMOVE!

$tmp_subcatmenu = new SD_SubcategoryMenu();
$tmp_subcatmenu->DisplaySubcategoryMenu();
unset($tmp_subcatmenu);
