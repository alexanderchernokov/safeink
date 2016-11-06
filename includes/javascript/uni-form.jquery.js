(function($){
  // Author: Ilija Studen for the purposes of Uni–Form
// Modified by Aris Karageorgos to use the parents function
// SD modifications to only use simple highlighting, removed "default value" handling
  $.fn.uniform = function(settings) {
  settings = $.extend({
    valid_class    : 'valid',
    invalid_class  : 'invalid',
    focused_class  : 'focused',
    holder_class   : 'ctrlHolder',
    field_selector : ':text, textarea, :password',
    default_value_color: "#D0D0D0"
  }, settings);

  return this.each(function() {
    var form = $(this);
    // Select form fields and attach the highlighter functionality
    form.find(settings.field_selector).each(function(){
      var default_color = $(this).css("color");
      $(this).focus(function() {
        form.find('.' + settings.focused_class).removeClass(settings.focused_class);
        $(this).parents().filter('.'+settings.holder_class+':first').addClass(settings.focused_class);
      }).blur(function() {
        form.find('.' + settings.focused_class).removeClass(settings.focused_class);
      });
    });
  });
};
})(jQuery);
