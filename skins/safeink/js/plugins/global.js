 $(document).ready(function(){
  // <!-- Initializing the navi mobile site -->
  $("#toggle-navi-mobile").click(function(){
            $(".off-canvas-menu").collapse('toggle');
        });

        $("#close-button-navi-mobile").click(function(){
            $(".off-canvas-menu").collapse('hide');
        });

    // <!-- Intializing Button Search Overlay-->
    $("body").on("click", "#btn-search", function(event){
    $("#hidden-content").fadeToggle(100);;
      
    });

    $("body").on("click", "#btn-search-close", function(event){
    $("#hidden-content").fadeToggle(100);;
      
    });


    // <!-- Intializing Navigation Effect-->
    $('ul.navi-level-1 li').hover
          (
            function()
            {
              $(this).children('ul.navi-level-2').stop().slideDown(400);
            },
            function()
            {
                    $(this).children('ul.navi-level-2').stop().slideUp(400);
                }
          );

          $('ul.navi-level-2 li').hover
          (
            function()
            {
              // $(this).children('ul.navi-level-3').addClass("open-navi-level-3");
              $(this).children('ul.navi-level-3').stop().slideDown(400);
            },
            function()
            {
                    $(this).children('ul.navi-level-3').stop().slideUp(400);
                }
          );

      // <!-- Intializing Navi Menu-->
      $("#mobile-menu").mobileMenu({
                MenuWidth: 250,
                SlideSpeed : 400,
                WindowsMaxWidth : 767,
                PagePush : true,
                FromLeft : false,
                Overlay : false,
                CollapseMenu : true,
                ClassName : "mobile-menu"
            });

 // <!-- Intializing Navigation Effect-->
    $('ul.navi-level-1 li').hover
          (
            function()
            {
              $(this).children('div.megamenu').stop().slideDown(400);
            },
            function()
            {
                    $(this).children('.megamenu').stop().slideUp(400);
                }
          );

         



// =====================================================
 });