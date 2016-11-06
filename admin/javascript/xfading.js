(function ($) {
  if ($.browser.msie && $.browser.version < 7) return;
  $('.fading div').removeClass('highlight').find('a')
    .append('<span class="hover" />').each(function () {
      var $span = $('> span.hover', this).css('opacity', 0);
      $(this).hover(function () {
        /* hover on */
        $span.stop().fadeTo(400, 1);
      }, function () {
        /* hover off */
        $span.stop().fadeTo(1400, 0);
      });
    });
})(jQuery);
