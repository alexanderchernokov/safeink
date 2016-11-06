//ceetip
/*
 * CeeTip 1.0.1 jQuery Plugin - Minimized
 * Requires jQuery 1.3.2
 * Code hosted on GitHub (http://github.com/catcubed/ceetip) Please visit there for version history information
 * By Colin Fahrion (http://www.catcubed.com)
 * based on vtip by vertigo project http://www.vertigo-project.com/projects/vtip 
  * Modified into a jQuery plugin and modified to work with CeeBox (http://github.com/catcubed/ceetip)
 * Copyright (c) 2009 Colin Fahrion
 * Licensed under the MIT License: http://www.opensource.org/licenses/mit-license.php
*/

(function(a){a.fn.ceetip=function(c){a.fn.ceetip.defaultSettings={xOffset:-12,yOffset:30,border:"1px solid #a6c9e2",arrow:"/images/ceetip_arrow.png"};c=a.extend({},a.fn.ceetip.defaultSettings,c);return this.each(function(){$this=a(this);$this.unbind("hover").hover(function(b){this.t=this.title;this.title="";var d=b.pageY+c.yOffset;b=b.pageX+c.xOffset;a("body").append('<p id="ceetip"><img id="ceetipArrow" />'+this.t+"</p>");a("p#ceetip #ceetipArrow").attr("src",c.arrow).css({position:"absolute",top:"-10px",
left:"5px"});a("p#ceetip").css({top:d+"px",left:b+"px",display:"none",position:"absolute",padding:"10px",fontSize:"0.8em",backgroundColor:"#fff",border:c.border,"-moz-border-radius":"5px","-webkit-border-radius":"5px",zIndex:9999}).fadeIn("slow")},function(){this.title=this.t;a("p#ceetip").fadeOut("slow").remove()}).mousemove(function(b){var d=b.pageY+c.yOffset;b=b.pageX+c.xOffset;a("p#ceetip").css({top:d+"px",left:b+"px"})})})}})(jQuery);