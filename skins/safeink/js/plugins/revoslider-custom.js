 $(document).ready(function(){
// <!-- Intializing Home V1 Slider-->      
var revapi;
revapi = jQuery('#revolution-slider-home-1').revolution(
    {
      delay:9000,
      startwidth:1170,
      startheight:920,
      hideThumbs:10,
      fullWidth:"off",
      fullScreen:"on",
      forceFullWidth:"off",
      autoHeight:"off", 
      navigationType:"none",
      navigationArrows:"default",  
      // stopSlider: "on",
      // stopAfterLoops:"0",
      // stopAtSlide:"1",   
    });
// <!-- Intializing Home V5,v2 Slider--> 
revapi_v5 = jQuery('#revolution-slider').revolution(
      {
        delay:9000,
        startwidth:1170,
        startheight:550,
        hideThumbs:10,
        fullWidth:"off",
        fullScreen:"on",
        forceFullWidth:"off",
        autoHeight:"off", 
        navigationType:"none",
        navigationArrows:"default", 
        fullScreenOffsetContainer: ".header-v2"  
      });
revapi_v3 = jQuery('#revolution-slider-home-3').revolution(
      {
        delay:9000,
        startwidth:1170,
        startheight:550,
        hideThumbs:10,
        fullWidth:"off",
        fullScreen:"off",
        forceFullWidth:"on",
        autoHeight:"on", 
        navigationType:"none",
        navigationArrows:"default", 
        stopSlider: "on",
        stopAfterLoops:"0",
        stopAtSlide:"1",
      });
// <!-- Intializing Home V4 Slider--> 
jQuery('.tp-banner-box').revolution(
      {
        delay:9000,
        startwidth:1170,
        startheight:550,
        hideThumbs:10,
        forceFullWidth:"off",
        fullWidth:"off",
        navigationStyle:"preview4",
        navigationType:"none",
        stopSlider: "on",
        stopAfterLoops:"0",
        stopAtSlide:"1",
      });
// =====================================================
 });


