<?php

if(!class_exists('SD_Paging'))
{
class SD_Paging
{
  /* Example displays (current page in < >):
  « Previous | 2  3  4  5  6  7  <8>  9  10 | Next »
  <1>  2  3  4  5  6  7  8  9 | Next »
  « Previous | 2  3  4  5  6  7  8  9  <10>
  */
  var $label_previous_page = 'Previous';
  var $label_next_page     = 'Next';
  var $label_first_page    = '&laquo; ';
  var $label_last_page     = ' &raquo;';
  var $PageSeparator       = ' |&nbsp;';
  var $CurrentPageTmpl     = '<strong>%d</strong>&nbsp;';
  var $LinkPageTmpl        = '%d';
  var $PageCount           = 1;
  var $PageStart           = 1;
  var $CurrentPage         = 1;
  var $PageIndicators      = 9;
  var $PageSize            = false;
  var $TotalEntries        = false;
  var $PagingUrlCallback   = false;
  var $PaginationOutput     = '';

  // ###########################################################################

  function GetPagingUrlCallback()
  {
    return $this->PagingUrlCallback;
  }

  // ###########################################################################

  function SetPagingUrlCallback($CallbackFunction)
  {
    $this->PagingUrlCallback = $CallbackFunction;
  }

  // ###########################################################################

  function CalculatePaging()
  {
    # Calculate total pages
    if($this->TotalEntries && $this->PageSize)
    {
      $this->PageCount = ceil($this->TotalEntries / $this->PageSize);
    }

    $pages = intval($this->PageCount);

    # If the results are displayed on only one page
    if ($pages < 1)
    {
      $pages = 1;
    }

    # If the current page is more than the number of records
    # send back to page 1
    if($pages > $this->PageCount)
    {
      $this->PageStart = 1;
    }

    $this->PageCount = $pages;

  } //CalculatePaging

  // ###########################################################################

  function GetPageUrlComponent($pageid, $pageSuffix = '_page')
  {
    $pageid = empty($pageid) ? 1 : intval($pageid);
    return (($pageid - 1) > 1 ? ($pluginid . $pageSuffix . '=' . $pageid) : '');
  } //GetPageUrlComponent

  // ###########################################################################

  function GetPaginationOutput()
  {
    $this->PaginationOutput = '';
    if(($this->PageCount <= 1) || !$this->PagingUrlCallback)
    {
      return;
    }

    # Displaying links to all the pages using a loop
    $thispage = $this->CurrentPage;
    $lim      = $this->PageIndicators;
    if($lim%2 == 1)
    {
      --$lim;
    }
    $mid = intval($lim / 2);  // Middle Marker

    if ($this->CurrentPage > $mid)
    {
      $thispage = $this->CurrentPage - $mid;
    }
    else
    {
      $thispage = 1;
    }

    if (($this->CurrentPage > $this->PageCount - $mid) &&
        ($this->PageCount > $lim))
    {
      $thispage = $this->PageCount - $lim;
    }

    if ($this->PageCount <= $lim)
    {
      $lim      = $this->PageCount - 1;
      $thispage = 1;
    }

    $link_func = $this->PagingUrlCallback;

    // If the user isn't on the first page (0) we'll put a "Back" link
    // which will lead to the previous page
    if($this->PageStart > 1)
    {
      if($this->PageCount > ($this->PageIndicators - 1))
      {
        # Display "<<" and "<" for first/previous page
        $this->PaginationOutput .= $link_func(1, $this->label_first_page);
      }
      $back_page = $this->PageStart - 1;
      $this->PaginationOutput .= $link_func($back_page, $this->label_previous_page);
      $this->PaginationOutput .= $this->PageSeparator;
    }

    for ($i = $thispage; $i <= $thispage + $lim; $i++)
    {
      # We don't want to display a link to the current page, so we'll just show the current page in bold
      if ($i == $this->PageStart)
      {
        $this->PaginationOutput .= str_replace('%d', $i, $this->CurrentPageTmpl);
      }
      else # If the page number we're displaying isn't the page the user is on we'll link to it
      {
        $this->PaginationOutput .= $link_func($i, str_replace('%d', $i, $this->LinkPageTmpl));
      }
    }

    # If the total number of the pages isn't 1 and if we're not on the last page we'll display a "Next" link
    if(($this->PageStart < $this->PageCount) && $this->PageCount > 1)
    {
      $next_page = $this->PageStart + 1;
      # Display e.g. ">" and ">>" links for next/last page
      $this->PaginationOutput .= $this->PageSeparator;
      $this->PaginationOutput .= $link_func($next_page, $this->label_next_page);
      if($this->PageCount > ($this->PageIndicators - 1))
      {
        $this->PaginationOutput .= $link_func($this->PageCount, $this->label_last_page);
      }
    }

    return $this->PaginationOutput;

  } //GetPaginationOutput

}
} //DO NOT REMOVE