 $(document).ready(function(){
    
    // <!-- Initializing the loading images lazy -->
        // $('.header-v1').css("top",-110);
        //$('#page').css("opacity",0);
      
      //calling jPreLoader function with properties
     /* $('body').jpreLoader({
        splashID: "#jSplash",
        splashFunction: function() {  //passing Splash Screen script to jPreLoader
          $('#jSplash').hide().fadeIn(100);
        }
      }, function() { //jPreLoader callback function
         // $('.header-v1').animate({"top":0}, 800, function() {
         // });
        $('#page').css("opacity",1);
       
      });*/
  // <!-- Initializing the navi mobile site -->
  $("#toggle-navi-mobile").click(function(){
            $(".off-canvas-menu").collapse('toggle');
        });

        $("#close-button-navi-mobile").click(function(){
            $(".off-canvas-menu").collapse('hide');
        });
    //Mobile Menu Scroll Enabel
    $(window).load(function(){
        $(".mCustomScrollbar").mCustomScrollbar();

    });
     $("#user_button").click(function(e){
         e.preventDefault;
         if($(".user_submenu").hasClass("open")){
             $(".user_submenu").fadeOut("fast");
             $(".user_submenu").removeClass("open");
         }
         else{
             $(".user_submenu").fadeIn("fast");
             $(".user_submenu").addClass("open");
             if($(".language_submenu").hasClass("open")) {
                 $(".language_submenu").fadeOut("fast");
                 $(".language_submenu").removeClass("open");
                 $("#language_button").find("i").removeClass("fa-angle-up").addClass("fa-angle-down");
             }
         }
     });
     $("#language_button").click(function(e){
         e.preventDefault;
         if($(".language_submenu").hasClass("open")){
             $(".language_submenu").fadeOut("fast");
             $(".language_submenu").removeClass("open");
             $(this).find("i").removeClass("fa-angle-up").addClass("fa-angle-down");
         }
         else{
             $(".language_submenu").fadeIn("fast");
             $(".language_submenu").addClass("open");
             if($(".user_submenu").hasClass("open")) {
                 $(".user_submenu").fadeOut("fast");
                 $(".user_submenu").removeClass("open");
             }
             $(this).find("i").removeClass("fa-angle-down").addClass("fa-angle-up");
         }
     });




    // --------------------------------------------------
    // Sticky Header
    // --------------------------------------------------
    var a = false,
        b = null;
    $(window).scroll(function() {
        $(window).scrollTop() > 200 ? a || (b = new Waypoint.Sticky({
            element: $("#sticked-menu")
        }), a = true, $("#sticked-menu").addClass("animated slideInDown")) : (b && (b.destroy(), a = false), $("#sticked-menu").removeClass("animated slideInDown"))
    }); 

    // <!-- Intializing Navigation Effect-->
    $('ul.navi-level-1 li').hover
          (
            function()
            {
              $(this).children('ul.navi-level-2').addClass("open-navi-2 animated fadeInUp");
            },
            function()
            {
                    $(this).children('ul.navi-level-2').removeClass("open-navi-2 animated fadeInUp");
                }
          );
    // <!-- Form Search Navi-->
    $('.btn-search-navi').click(function()
        {
            $('.form-search-navi input.form-control').toggleClass("open-search-input animated fadeInUp");
            $('.btn-search-navi .fa-search').toggleClass("fa-remove");
            $('.btn-search-navi').toggleClass("active");
            return false;
        });
                               
         

      // <!-- Intializing Navi Menu-->mobile-menu-transparent
      $("#mobile-menu").mobileMenu({
                MenuWidth: 250,
                SlideSpeed : 400,
                WindowsMaxWidth : 767,
                PagePush : true,
                FromLeft : true,
                Overlay : false,
                CollapseMenu : true,
                ClassName : "mobile-menu"
            });
      // <!-- Intializing Navi Menu-->
      $("#mobile-menu-right").mobileMenu({
                MenuWidth: 250,
                SlideSpeed : 400,
                WindowsMaxWidth : 767,
                PagePush : true,
                FromLeft : false,
                Overlay : false,
                CollapseMenu : true,
                ClassName : "mobile-menu"
            });
       // <!-- Intializing Navi Menu-->
      $("#mobile-menu-transparent").mobileMenu({
                MenuWidth: 250,
                SlideSpeed : 400,
                WindowsMaxWidth : 767,
                PagePush : false,
                FromLeft : true,
                Overlay : true,
                CollapseMenu : true,
                ClassName : "mobile-menu"
            });
      // Button toggle mobile menu

    // Popover bootstrap
    $(function () {
      $('[data-toggle="popover"]').popover()
    });

    // --------------------------------------------------
    // Back To Top
    // --------------------------------------------------
    var offset = 450;
    var duration = 500;
    jQuery(window).scroll(function() {
        if (jQuery(this).scrollTop() > offset) {
            jQuery('#to-the-top').fadeIn(duration);
        } else {
            jQuery('#to-the-top').fadeOut(duration);
        }
    });
            
    jQuery('#to-the-top').click(function(event) {
        event.preventDefault();
        jQuery('html, body').animate({scrollTop: 0}, duration);
        return false;
    });
    // Intializing Select Box Custom
    $(function () {
        $('.custom-select').fancySelect();
    });




     //-------------------------------------------------
     // Pop ups
     //-------------------------------------------------

     //----- OPEN
     $('[data-popup-open]').on('click', function(e)  {
         $('.popup-inner-content').load('plugins/payments/parts/'+$(this).attr('rel')+'.php');
         var targeted_popup_class = jQuery(this).attr('data-popup-open');
         $('[data-popup="' + targeted_popup_class + '"]').fadeIn(350);

         e.preventDefault();
     });

     //----- CLOSE
     $('[data-popup-close]').on('click', function(e)  {
         var targeted_popup_class = jQuery(this).attr('data-popup-close');
         $('[data-popup="' + targeted_popup_class + '"]').fadeOut(350);

         e.preventDefault();
     });
// =====================================================
 });


