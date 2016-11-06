 $(document).ready(function(){
    $("#owl-partners").owlCarousel({
               
                    autoPlay: true, //Set AutoPlay to 3 seconds
                    items : 5,
                    itemsDesktop      : [1199,5],
                    itemsDesktopSmall     : [979,4],
                    itemsTablet       : [768,2],
                    itemsMobile       : [479,1],
                    pagination:false,
                    navigation:false,
                });
        var owlPartners = $("#owl-partners");
        // Custom Navigation Events

        $("body").on("click",".next-partners",function(event){
          owlPartners.trigger("owl.next");
        });
        $("body").on("click",".prev-partners",function(event){
          owlPartners.trigger("owl.prev");
        });
        // End Owl Partners
        $("#owl-experts").owlCarousel({
               
                    autoPlay: false, //Set AutoPlay to 3 seconds
                    items : 4,
                    itemsDesktop      : [1199,4],
                    itemsDesktopSmall     : [979,4],
                    itemsTablet       : [768,2],
                    itemsMobile       : [479,1],
                    pagination:false,
                    navigation:false,
               
                });
        var owlExperts = $("#owl-experts");
        // Custom Navigation Events

        $("body").on("click",".next-experts",function(event){
          owlExperts.trigger("owl.next");
        });
        $("body").on("click",".prev-experts",function(event){
          owlExperts.trigger("owl.prev");
        });
    // End Owl Testimonial 3 columns
     $("#owl-testimonials-3-columns").owlCarousel({
               
                    autoPlay: false, //Set AutoPlay to 3 seconds
                    items : 3,
                    itemsDesktop      : [1199,3],
                    itemsDesktopSmall     : [979,3],
                    itemsTablet       : [768,2],
                    itemsMobile       : [479,1],
                    pagination:false,
                    navigation:false,
               
                });
        var owlTestimonials3columns = $("#owl-testimonials-3-columns");
        // Custom Navigation Events

        $("body").on("click",".next-testimonials-3-columns",function(event){
          owlTestimonials3columns.trigger("owl.next");
        });
        $("body").on("click",".prev-testimonials-3-columns",function(event){
          owlTestimonials3columns.trigger("owl.prev");
        });
    // End Owl Testimonial 3 columns
   
        var owlTestimonials1columns = $("#sync3");
        // Custom Navigation Events

        $("body").on("click",".next-testimonials-1-columns",function(event){
          owlTestimonials1columns.trigger("owl.next");
        });
        $("body").on("click",".prev-testimonials-1-columns",function(event){
          owlTestimonials1columns.trigger("owl.prev");
        });



        $(document).ready(function() {
          var sync1 = $("#sync3");
          var sync2 = $("#sync4");
          sync1.owlCarousel({
            singleItem : true,
            slideSpeed : 1000,
            navigation: false,
            pagination:false,
            transitionStyle : "fade",
            afterAction : syncPosition,
            responsiveRefreshRate : 200,
              afterInit : progressBar,
              afterMove : moved,
              startDragging : pauseOnDragging,
              
          });
          
          var time = 5; // time in seconds

          var $progressBar,
              $bar, 
              $elem, 
              isPause, 
              tick,
              percentTime;

         
            //Init progressBar where elem is $("#owl-demo")
            function progressBar(elem){
              $elem = elem;
              //build progress bar elements
              buildProgressBar();
              //start counting
              start();
            }
         
            //create div#progressBar and div#bar then prepend to $("#owl-demo")
            function buildProgressBar(){
              $progressBar = $("<div>",{
                id:"progressBar"
              });
              $bar = $("<div>",{
                id:"bar"
              });
              $progressBar.append($bar).prependTo($elem);
            }
         
            function start() {
              //reset timer
              percentTime = 0;
              isPause = false;
              //run interval every 0.01 second
              tick = setInterval(interval, 10);
            };
         
            function interval() {
              if(isPause === false){
                percentTime += 1 / time;
                $bar.css({
                   width: percentTime+"%"
                 });
                //if percentTime is equal or greater than 100
                if(percentTime >= 100){
                  //slide to next item 
                  $elem.trigger('owl.next')
                }
              }
            }
         
            //pause while dragging 
            function pauseOnDragging(){
              isPause = true;
            }
         
            //moved callback
            function moved(){
              //clear interval
              clearTimeout(tick);
              //start again
              start();
            }




          
          sync2.owlCarousel({
            items : 5,
            itemsDesktop      : [1199,5],
            itemsDesktopSmall     : [979,5],
            itemsTablet       : [768,5],
            itemsMobile       : [479,5],
            pagination:false,
            responsiveRefreshRate : 100,
            afterInit : function(el){
              el.find(".owl-item").eq(0).addClass("synced");
            }
          });
         
          function syncPosition(el){
            var current = this.currentItem;
            $("#sync4")
              .find(".owl-item")
              .removeClass("synced")
              .eq(current)
              .addClass("synced")
            if($("#sync4").data("owlCarousel") !== undefined){
              center(current)
            }
          }
         
          $("#sync4").on("click", ".owl-item", function(e){
            e.preventDefault();
            var number = $(this).data("owlItem");
            sync1.trigger("owl.goTo",number);
          });
         
          function center(number){
            var sync2visible = sync2.data("owlCarousel").owl.visibleItems;
            var num = number;
            var found = false;
            for(var i in sync2visible){
              if(num === sync2visible[i]){
                var found = true;
              }
            }
         
            if(found===false){
              if(num>sync2visible[sync2visible.length-1]){
                sync2.trigger("owl.goTo", num - sync2visible.length+2)
              }else{
                if(num - 1 === -1){
                  num = 0;
                }
                sync2.trigger("owl.goTo", num);
              }
            } else if(num === sync2visible[sync2visible.length-1]){
              sync2.trigger("owl.goTo", sync2visible[1])
            } else if(num === sync2visible[0]){
              sync2.trigger("owl.goTo", num-1)
            }
            
          }
         
        }); 



    // End Owl services 3  columns
     $("#owl-services-3-columns").owlCarousel({
               
                    autoPlay: false, //Set AutoPlay to 3 seconds
                    items : 3,
                    margin: 40,
                    itemsDesktop      : [1199,2],
                    itemsDesktopSmall     : [979,2],
                    itemsTablet       : [768,1],
                    itemsMobile       : [479,1],
                    pagination:false,
                    navigation:false,
               
                });
        var owlServices = $("#owl-services-3-columns");
        // Custom Navigation Events

        $("body").on("click",".next-services-3-columns",function(event){
          owlServices.trigger("owl.next");
        });
        $("body").on("click",".prev-services-3-columns",function(event){
          owlServices.trigger("owl.prev");
        });
    // End Owl Our Services 3 columns


    // End Owl Project
        $("#owl-project").owlCarousel({
               
                    autoPlay: false, //Set AutoPlay to 3 seconds
                    items : 4,
                    itemsDesktop      : [1199,4],
                    itemsDesktopSmall     : [979,4],
                    itemsTablet       : [768,2],
                    itemsMobile       : [479,1],
                    pagination:false,
                    navigation:false,
               
                });
        var owlProject = $("#owl-project");
        // Custom Navigation Events

        $("body").on("click",".next-project",function(event){
          owlProject.trigger("owl.next");
        });
        $("body").on("click",".prev-project",function(event){
          owlProject.trigger("owl.prev");
        });
       // End Owl Project


      $("#owl-relate-products").owlCarousel({
               
                    autoPlay: false, //Set AutoPlay to 3 seconds
                    items : 4,
                    itemsDesktop      : [1199,4],
                    itemsDesktopSmall     : [979,4],
                    itemsTablet       : [768,2],
                    itemsMobile       : [479,1],
                    pagination:false,
                    navigation:false,
               
                });
        var owlRelateProducts = $("#owl-relate-products");
        // Custom Navigation Events

        $("body").on("click",".next-relate-products",function(event){
          owlRelateProducts.trigger("owl.next");
        });
        $("body").on("click",".prev-relate-products",function(event){
          owlRelateProducts.trigger("owl.prev");
        });
    // End Owl Relate Products

// =====================================================
 });