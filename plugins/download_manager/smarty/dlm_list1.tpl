{* Download Manager Smarty Template for File Listing *}
    <!-- FILE ENTRY -->
    <tr>
      <td class="dlm-left-column"{$left_column_width}>{$left_column_thumb_html}</td>
      <td class="dlm-right-column" valign="top" colspan="2">
        <table class="dlm-file-details" border="0" cellspacing="0" cellpadding="0" summary="layout" width="100%">
        <tr>
          <td class="dlm-title-cell" colspan="2" valign="top">
          {if $details_page}<a class="dlm-title-link" href="{$detail_href}">{$file_title}</a>{else}<span class="dlm-title">{$file_title}</span>{/if}
          {if $ratings_html}{* File Rating Row *}{$ratings_html}{/if}</td>
        </tr>
        {if $embed_on}{* Display row with embedded media object? *}
        <tr>
          <td class="dlm-embed-cell" style="padding: 0px 10px 4px 0px;" colspan="2">
            <div class="dlm-embed">{$embed_html}</div>
          </td>
        </tr>{/if}
        {if $file_descr}{* Display row with embedbed media object? *}<tr>
          <td class="dlm-file-description" align="left" colspan="2" >{$file_descr}</td>
        </tr>
        {/if}
        {if $tags_html}{* Display row with embedbed media object? *}<tr class="dlm-detail-row{$tags_row}">
          <td class="dlm-detail-name" align="left" valign="top">{$tags_phrase}</td>
          <td class="dlm-detail-value" align="left" valign="top">{$tags_html}</td>
        </tr>
        {/if}
        {$file_details}
        <tr>
          <td align="left" style="padding: {$padtop}px 0px 0px 0px;" valign="middle" colspan="2">
