/* http://ditio.net/2010/01/02/jquery-image-resize-plugin/ */
(function($) {
$.fn.resizeImg = function(options) {
  var settings = $.extend({
    scale: 1,
    maxWidth: null,
    maxHeight: null,
    onResize: null
  }, options);

  $(this).one('load', function() {
  /*return this.each(function() {*/

    if(typeof this.tagName == "undefined" || this.tagName.toLowerCase() != "img") {
      /* Only images can be resized */
      return $(this);
    }

    var width = this.naturalWidth;
    var height = this.naturalHeight;
    if(!width || !height) {
      /* Ooops you are an IE user, let's fix it. */
      var img = document.createElement('img');
      img.src = this.src;

      width = img.width;
      height = img.height;
    }

    if(settings.scale != 1) {
      width = width*settings.scale;
      height = height*settings.scale;
    }

    var pWidth = 1;
    if(settings.maxWidth != null) { pWidth = width/settings.maxWidth; }
    var pHeight = 1;
    if(settings.maxHeight != null) { pHeight = height/settings.maxHeight; }
    var reduce = 1;

    if(pWidth < pHeight) {
      reduce = pHeight;
    } else {
      reduce = pWidth;
    }

    if(reduce < 1) { reduce = 1; }
    var newWidth = width/reduce;
    var newHeight = height/reduce;
    $(this).attr("width", newWidth).attr("height", newHeight);
    if(settings.onResize && typeof settings.onResize !== "undefined") settings.onResize($(this));
    return $(this);
  }).each(function(){
    if (this.complete) { $(this).trigger("load"); }
  });
}
})(jQuery);
