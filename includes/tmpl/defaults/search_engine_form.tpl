{* Smarty Template for SD Search Engine v1.3.5+ 2012-03-21 *}
<div class="searchform">
  <form action="{$target_url}" id="searchform" method="post">
    <div>
      <input type="hidden" name="action" value="search" />
      <input autocomplete="off" type="text" name="q" value="{$searchString}" id="searchString" onkeyup="search(this.value);" />&nbsp;<input type="submit" value="{$phrases.search_but}" />
    </div>
    {if !empty($settings.use_autocomplete)}
    <div class="acBox" id="ac" style="display: none;">
      <div class="acList" id="acList"></div>
    </div>
    {/if}
  </form>
</div>
