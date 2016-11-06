<?php
// DEFINE CONSTANTS
define('IN_PRGM', true);
define('IN_ADMIN', true);
define('ROOT_PATH', '../');

// INIT PRGM
@require(ROOT_PATH . 'includes/init.php');
include_once(ROOT_PATH.'includes/enablegzip.php');

// LOAD ADMIN LANGUAGE
$admin_phrases = LoadAdminPhrases(1);

$stupdate 	= GetVar('stupdate', 0, 'int');
$lw			= GetVar('lw',0,'int');
$id			= GetVar('id', 0, 'int');
$active		= GetVar('active', -1, 'int');
$columns	= 2;
$col1		= '';
$col2		= '';

if(is_Ajax_Request())
{
	if($stupdate == 1)
	{
		$DB->query("UPDATE {widgets} SET active=$active WHERE id=$id");
		echo "success";
		exit;
	}
	elseif($lw == 1)
	{
		echo $sd_widget->LoadSingleWidget($id);
		exit;
	}

}


sd_header_add(array(
	'css'	=> array(ROOT_PATH . ADMIN_STYLES_FOLDER .'assets/css/jquery-ui.min.css'),
	'js'	=> array(ROOT_PATH . ADMIN_STYLES_FOLDER .'assets/js/jquery-ui.min.js'),
  #SD362: moved to core admin loader!
  #'css'   => array( SD_INCLUDE_PATH . 'css/jquery.jgrowl.css' ),
  #'js'    => array( ROOT_PATH . MINIFY_PREFIX_F . 'includes/javascript/jquery.easing-1.3.pack.js',
  #                  ROOT_PATH . MINIFY_PREFIX_F . 'includes/javascript/jquery.jgrowl.min.js' ),
  'other' => array('
<script type="text/javascript">// <![CDATA[
jQuery(document).ready(function() {
(function($){
	$("a.widget-close").click( function() {
		var _id = $(this).closest(".widget-container-col").attr("id");
		var id = _id.substr(_id.length - 1, 1);
		checked = 0;
		var jqhxr = $.post("cphome.php", {stupdate : 1, id: id, active: checked})
		.done(function( data ) {
			$("#widget-" + id).fadeToggle("slow", function() {
				
			});
			
			var n = noty({
				text: data,
				layout: "top",
				type: "success",	
				timeout: 5000,			
				});
		});
	});
		
	
	$("input[type=checkbox]").click( function() {
		var id = $(this).attr("id");
		var checked = 0;
		if($(this).closest("input[type=checkbox]").is(":checked"))
		{
			checked = 1;
			var jqpxr = $.post("cphome.php", {lw: 1, id: id})
				.done(function(data) {
					if($("#widget-" + id).length)
					{
						$("#widget-" + id).fadeToggle("slow", function() {
						});
					}
					else
					{
						$("div.col-xs-12:last").append(data);
					}
					
					var n = noty({
					text: "Success",
					layout: "top",
					type: "success",	
					timeout: 5000,			
					});
						
				});
		}
		else
		{
			checked = 0;
			var jqhxr = $.post("cphome.php", {stupdate : 1, id: id, active: checked})
			.done(function( data ) {
				$("#widget-" + id).fadeToggle("slow", function() {
					
				});
				
				var n = noty({
					text: data,
					layout: "top",
					type: "success",	
					timeout: 5000,			
					});
			});
		}
		
		
		
	});
  $(".widget-container-col").sortable({
    connectWith: ".widget-container-col",
    items: "> .widget-box",
    opacity: 0.8,
    revert: true,
    forceHelperSize: true,
    placeholder: "widget-placeholder",
    forcePlaceholderSize: true,
    tolerance: "pointer",
    start: function(event, ui){
        //when an element is moved, its parent (.widget-container-col) becomes empty with almost zero height
        //we set a "min-height" for it to be large enough so that later we can easily drop elements back onto it
        ui.item.parent().css({"min-height": ui.item.height()})
    },
    update: function(event, ui) {
        //the previously set "min-height" is not needed anymore
        //now the parent (.widget-container-col) should take the height of its child (.wiget-box)
        ui.item.parent({"min-height":""})
    }
 });


}(jQuery));
});
// ]]>
</script>
')));

// SD400
// Widgets require version 4.0+ or else
// it will trigger a database error.
if(!requiredVersion(4,0) && !headers_sent())
{
	header("Location: pages.php");
}

// Load all widgets
$sd_widget->LoadWidgets();


// Settings Box
$settings_box = '<div id="ace-settings-container" class="ace-settings-container">
                <div id="ace-settings-btn" class="ace-settings-btn btn btn-app btn-xs btn-warning">
                   <i class="ace-icon fa fa-cog bigger-150"></i>
                </div>
            
                <div id="ace-settings-box" class="ace-settings-box clearfix">
                  <div class="pull-left width-50">
				 <div class="ace-settings-item">
				 	<strong> Active Widgets </strong>
				</div>
                    ';
					for( $i = 0; $i < $sd_widget->GetCount(); $i++)
					{
						$_widget = $sd_widget->GetWidget($i);
						$settings_box .= '
							<div class="ace-settings-item">
							<input type="checkbox" id="'.$_widget['id'].'" value="1" class="ace" ' . ($_widget['active'] == 1 ? 'checked="checked"' : '') .' />
                        	<label for="'.$_widget['id'].'" class="lbl"> ' . $_widget['title'] . '</label><br />
							</div>';
					}
					
  $settings_box .='                  
                  </div><!-- /.pull-left -->
            
                  <div class="pull-left width-50">
                    ...
                  </div><!-- /.pull-left -->
                </div><!-- /.ace-settings-box -->
            </div>';


// ############################################################################
// CHECK PAGE ACCESS
// ############################################################################

CheckAdminAccess('cphome');

$load_wysiwyg = 0;
DisplayAdminHeader(AdminPhrase('admin_control_panel'),'','','', $settings_box);

// ############################################################################

// Output widgets

if($sd_widget->disabled)
{
	echo $sd_widget->alert;
}



foreach($sd_widget->html as $key => $widget)
{
	if($key & 1) 
	{
		$col2 .= $widget;
	}
	else
	{
		$col1 .= $widget;
	}
}


echo '<div class="col-xs-12 col-sm-6"> ' .$col1.'</div>';
echo '<div class="col-xs-12 col-sm-6"> ' .$col2.'</div>';



/*
echo ' <div class="col-xs-12 col-sm-4 widget-container-col">
<div class="widget-box">
   <div class="widget-header">
   	 <h4 class="widget-title smaller">title!</h4>
   <div class="widget-toolbar">
   <a data-action="settings" href="#"><i class="ace-icon fa fa-bars"></i></a>
   <a data-action="reload" href="#"><i class="ace-icon fa fa-refresh"></i></a>
   <a data-action="fullscreen" class="orange2" href="#"><i class="ace-icon fa fa-expand"></i></a>
   <a data-action="collapse" href="#"><i class="ace-icon fa fa-chevron-up"></i></a>
   <a data-action="close" href="#"><i class="ace-icon fa fa-times"></i></a>
   </div>
   </div>
   <div class="widget-body">
   

  <div class="widget-main">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque molestie elit sed est accumsan, nec posuere ligula vulputate. Fusce congue libero a dignissim rhoncus. Praesent hendrerit ultrices leo, quis faucibus quam consectetur et. Praesent hendrerit a nibh nec varius. Morbi vehicula gravida leo, non bibendum lacus auctor ac. Maecenas porttitor nunc erat, in facilisis purus molestie a. Cras sed lorem at est mollis venenatis.

Phasellus in ligula porta, condimentum felis sed, rhoncus leo. Aliquam ante augue, dictum et quam sit amet, lacinia mattis neque. Vestibulum tristique sapien sed sagittis luctus. Integer placerat bibendum neque, vitae semper lectus laoreet eu. Ut suscipit, lacus ut blandit blandit, dolor urna accumsan metus, a egestas enim dolor ut velit. Suspendisse maximus congue lacus eget interdum. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Nullam ac pretium massa, in dictum augue. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Suspendisse vel diam felis. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.

Morbi eu est ut leo placerat accumsan sit amet in ipsum. Fusce laoreet scelerisque eros, ut ullamcorper massa lobortis sed. Phasellus purus ligula, placerat vel feugiat nec, faucibus sed ipsum. Donec vel ipsum orci. Sed placerat velit quis porttitor porttitor. Fusce venenatis suscipit libero, eu dapibus turpis accumsan et. Donec quis sollicitudin quam, tincidunt cursus sem. Duis sit amet lorem quis ligula fermentum dignissim dictum et leo. Fusce nibh ex, rhoncus eget faucibus at, dapibus vitae nisi. Phasellus convallis elit at orci scelerisque, vel posuere neque posuere. Etiam pharetra at elit ut congue. Integer tempor elit id orci finibus, a molestie velit semper. Vestibulum id vulputate dui, in aliquet massa. Etiam sit amet sapien a mauris fringilla auctor at eget mi. Vestibulum semper erat urna, sit amet gravida justo fermentum a. Morbi auctor nunc eu diam sodales, et consequat sem tincidunt.

Nunc tincidunt ex ut libero aliquam euismod. Duis accumsan purus eu dapibus venenatis. In hac habitasse platea dictumst. Vivamus laoreet ligula ac risus porta, eu accumsan metus aliquet. In sed arcu arcu. Curabitur eleifend et nisl nec aliquet. Nam sit amet auctor mauris. Praesent vitae fringilla augue. Vestibulum purus purus, finibus at facilisis eget, varius quis diam. Proin pharetra fermentum ligula eget condimentum. Duis euismod et risus ac sodales. Donec et vulputate ante. Cras ut risus et mi facilisis posuere. Sed eget orci purus. Mauris dui nibh, lobortis quis viverra at, imperdiet quis purus.

Vestibulum pellentesque sapien a eros eleifend, eget semper turpis gravida. Vestibulum imperdiet dapibus mi, id rhoncus turpis feugiat sed. Nam scelerisque nibh orci, non iaculis urna vestibulum in. Vestibulum pretium, odio pulvinar mollis porta, enim nisi ornare ligula, in accumsan turpis leo at ex. Cras viverra tincidunt dui nec vehicula. Sed lacinia a leo sit amet facilisis. Aenean sed orci purus. Suspendisse pulvinar euismod arcu at rutrum. </div>

  <div class="widget-toolbox padding-8"> another toolbox </div>
   </div>
 </div>
 </div>
 
 
 <div class="col-xs-12 col-sm-4 widget-container-col">
<div class="widget-box">
   <div class="widget-header">
   	 <h4 class="widget-title smaller">title!</h4>
   <div class="widget-toolbar">
   	<a data-action="settings" href="#"><i class="ace-icon fa fa-bars"></i></a>
   <a data-action="reload" href="#"><i class="ace-icon fa fa-refresh"></i></a>
   <a data-action="fullscreen" class="orange2" href="#"><i class="ace-icon fa fa-expand"></i></a>
   <a data-action="collapse" href="#"><i class="ace-icon fa fa-chevron-up"></i></a>
   <a data-action="close" href="#"><i class="ace-icon fa fa-times"></i></a>
   </div>
   </div>
   <div class="widget-body">
   

  <div class="widget-main">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque molestie elit sed est accumsan, nec posuere ligula vulputate. Fusce congue libero a dignissim rhoncus. Praesent hendrerit ultrices leo, quis faucibus quam consectetur et. Praesent hendrerit a nibh nec varius. Morbi vehicula gravida leo, non bibendum lacus auctor ac. Maecenas porttitor nunc erat, in facilisis purus molestie a. Cras sed lorem at est mollis venenatis.

Phasellus in ligula porta, condimentum felis sed, rhoncus leo. Aliquam ante augue, dictum et quam sit amet, lacinia mattis neque. Vestibulum tristique sapien sed sagittis luctus. Integer placerat bibendum neque, vitae semper lectus laoreet eu. Ut suscipit, lacus ut blandit blandit, dolor urna accumsan metus, a egestas enim dolor ut velit. Suspendisse maximus congue lacus eget interdum. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Nullam ac pretium massa, in dictum augue. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Suspendisse vel diam felis. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.

Morbi eu est ut leo placerat accumsan sit amet in ipsum. Fusce laoreet scelerisque eros, ut ullamcorper massa lobortis sed. Phasellus purus ligula, placerat vel feugiat nec, faucibus sed ipsum. Donec vel ipsum orci. Sed placerat velit quis porttitor porttitor. Fusce venenatis suscipit libero, eu dapibus turpis accumsan et. Donec quis sollicitudin quam, tincidunt cursus sem. Duis sit amet lorem quis ligula fermentum dignissim dictum et leo. Fusce nibh ex, rhoncus eget faucibus at, dapibus vitae nisi. Phasellus convallis elit at orci scelerisque, vel posuere neque posuere. Etiam pharetra at elit ut congue. Integer tempor elit id orci finibus, a molestie velit semper. Vestibulum id vulputate dui, in aliquet massa. Etiam sit amet sapien a mauris fringilla auctor at eget mi. Vestibulum semper erat urna, sit amet gravida justo fermentum a. Morbi auctor nunc eu diam sodales, et consequat sem tincidunt.

Nunc tincidunt ex ut libero aliquam euismod. Duis accumsan purus eu dapibus venenatis. In hac habitasse platea dictumst. Vivamus laoreet ligula ac risus porta, eu accumsan metus aliquet. In sed arcu arcu. Curabitur eleifend et nisl nec aliquet. Nam sit amet auctor mauris. Praesent vitae fringilla augue. Vestibulum purus purus, finibus at facilisis eget, varius quis diam. Proin pharetra fermentum ligula eget condimentum. Duis euismod et risus ac sodales. Donec et vulputate ante. Cras ut risus et mi facilisis posuere. Sed eget orci purus. Mauris dui nibh, lobortis quis viverra at, imperdiet quis purus.

Vestibulum pellentesque sapien a eros eleifend, eget semper turpis gravida. Vestibulum imperdiet dapibus mi, id rhoncus turpis feugiat sed. Nam scelerisque nibh orci, non iaculis urna vestibulum in. Vestibulum pretium, odio pulvinar mollis porta, enim nisi ornare ligula, in accumsan turpis leo at ex. Cras viverra tincidunt dui nec vehicula. Sed lacinia a leo sit amet facilisis. Aenean sed orci purus. Suspendisse pulvinar euismod arcu at rutrum. </div>

  <div class="widget-toolbox padding-8"> another toolbox </div>
   </div>
 </div>
 </div>
 
 
 <div class="col-xs-12 col-sm-4 widget-container-col">
<div class="widget-box">
   <div class="widget-header">
   	 <h4 class="widget-title smaller">title!</h4>
   <div class="widget-toolbar">
   	<a data-action="settings" href="#"><i class="ace-icon fa fa-bars"></i></a>
   <a data-action="reload" href="#"><i class="ace-icon fa fa-refresh"></i></a>
   <a data-action="fullscreen" class="orange2" href="#"><i class="ace-icon fa fa-expand"></i></a>
   <a data-action="collapse" href="#"><i class="ace-icon fa fa-chevron-up"></i></a>
   <a data-action="close" href="#"><i class="ace-icon fa fa-times"></i></a>
   </div>
   </div>
   <div class="widget-body">
   

  <div class="widget-main">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque molestie elit sed est accumsan, nec posuere ligula vulputate. Fusce congue libero a dignissim rhoncus. Praesent hendrerit ultrices leo, quis faucibus quam consectetur et. Praesent hendrerit a nibh nec varius. Morbi vehicula gravida leo, non bibendum lacus auctor ac. Maecenas porttitor nunc erat, in facilisis purus molestie a. Cras sed lorem at est mollis venenatis.

Phasellus in ligula porta, condimentum felis sed, rhoncus leo. Aliquam ante augue, dictum et quam sit amet, lacinia mattis neque. Vestibulum tristique sapien sed sagittis luctus. Integer placerat bibendum neque, vitae semper lectus laoreet eu. Ut suscipit, lacus ut blandit blandit, dolor urna accumsan metus, a egestas enim dolor ut velit. Suspendisse maximus congue lacus eget interdum. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Nullam ac pretium massa, in dictum augue. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Suspendisse vel diam felis. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.

Morbi eu est ut leo placerat accumsan sit amet in ipsum. Fusce laoreet scelerisque eros, ut ullamcorper massa lobortis sed. Phasellus purus ligula, placerat vel feugiat nec, faucibus sed ipsum. Donec vel ipsum orci. Sed placerat velit quis porttitor porttitor. Fusce venenatis suscipit libero, eu dapibus turpis accumsan et. Donec quis sollicitudin quam, tincidunt cursus sem. Duis sit amet lorem quis ligula fermentum dignissim dictum et leo. Fusce nibh ex, rhoncus eget faucibus at, dapibus vitae nisi. Phasellus convallis elit at orci scelerisque, vel posuere neque posuere. Etiam pharetra at elit ut congue. Integer tempor elit id orci finibus, a molestie velit semper. Vestibulum id vulputate dui, in aliquet massa. Etiam sit amet sapien a mauris fringilla auctor at eget mi. Vestibulum semper erat urna, sit amet gravida justo fermentum a. Morbi auctor nunc eu diam sodales, et consequat sem tincidunt.

Nunc tincidunt ex ut libero aliquam euismod. Duis accumsan purus eu dapibus venenatis. In hac habitasse platea dictumst. Vivamus laoreet ligula ac risus porta, eu accumsan metus aliquet. In sed arcu arcu. Curabitur eleifend et nisl nec aliquet. Nam sit amet auctor mauris. Praesent vitae fringilla augue. Vestibulum purus purus, finibus at facilisis eget, varius quis diam. Proin pharetra fermentum ligula eget condimentum. Duis euismod et risus ac sodales. Donec et vulputate ante. Cras ut risus et mi facilisis posuere. Sed eget orci purus. Mauris dui nibh, lobortis quis viverra at, imperdiet quis purus.

Vestibulum pellentesque sapien a eros eleifend, eget semper turpis gravida. Vestibulum imperdiet dapibus mi, id rhoncus turpis feugiat sed. Nam scelerisque nibh orci, non iaculis urna vestibulum in. Vestibulum pretium, odio pulvinar mollis porta, enim nisi ornare ligula, in accumsan turpis leo at ex. Cras viverra tincidunt dui nec vehicula. Sed lacinia a leo sit amet facilisis. Aenean sed orci purus. Suspendisse pulvinar euismod arcu at rutrum. </div>

  <div class="widget-toolbox padding-8"> another toolbox </div>
   </div>
 </div>
 </div>
 
 
 <div class="col-xs-12 col-sm-4 widget-container-col">
<div class="widget-box">
   <div class="widget-header">
   	 <h4 class="widget-title smaller">title!</h4>
   <div class="widget-toolbar">
   	<a data-action="settings" href="#"><i class="ace-icon fa fa-bars"></i></a>
   <a data-action="reload" href="#"><i class="ace-icon fa fa-refresh"></i></a>
   <a data-action="fullscreen" class="orange2" href="#"><i class="ace-icon fa fa-expand"></i></a>
   <a data-action="collapse" href="#"><i class="ace-icon fa fa-chevron-up"></i></a>
   <a data-action="close" href="#"><i class="ace-icon fa fa-times"></i></a>
   </div>
   </div>
   <div class="widget-body">
   

  <div class="widget-main">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque molestie elit sed est accumsan, nec posuere ligula vulputate. Fusce congue libero a dignissim rhoncus. Praesent hendrerit ultrices leo, quis faucibus quam consectetur et. Praesent hendrerit a nibh nec varius. Morbi vehicula gravida leo, non bibendum lacus auctor ac. Maecenas porttitor nunc erat, in facilisis purus molestie a. Cras sed lorem at est mollis venenatis.



Vestibulum pellentesque sapien a eros eleifend, eget semper turpis gravida. Vestibulum imperdiet dapibus mi, id rhoncus turpis feugiat sed. Nam scelerisque nibh orci, non iaculis urna vestibulum in. Vestibulum pretium, odio pulvinar mollis porta, enim nisi ornare ligula, in accumsan turpis leo at ex. Cras viverra tincidunt dui nec vehicula. Sed lacinia a leo sit amet facilisis. Aenean sed orci purus. Suspendisse pulvinar euismod arcu at rutrum. </div>

  <div class="widget-toolbox padding-8"> another toolbox </div>
   </div>
 </div>
 </div>';
 
 */



DisplayAdminFooter();
