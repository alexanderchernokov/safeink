function Rollover(imagename, image, over)
{
  if(window.document.images)
  {
    if(over)
    {
      window.document.images["sdhover" + imagename].src = image;
    }
    else
    {
      window.document.images["sdhover" + imagename].src = image;
    }
  }
}