
$(document).ready(function(){
  var executed = false;
     $('.chart-2').waypoint(function() {
      if (!executed) {
        executed = true;
           $('.progress .progress-bar').progressbar({
              update: function(current_percentage, $this) {
                  $this.parent().parent().find(".update-h").html(current_percentage  + '%');
                  $this.parent().parent().find(".update-h").css("left", (current_percentage - 8) + '%'); 
                  $this.parent().parent().find(".update-v").html(current_percentage  + '%');
                  $this.parent().parent().find(".update-v").css("bottom", current_percentage + '%'); 
              }
            });
        }
    },{offset: 'bottom-in-view' })

// =====================================================
 });

