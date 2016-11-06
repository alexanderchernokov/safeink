<?php
if(!defined('IN_PRGM')) exit();

class p6_settings {
	
	var $inputs = array();
	var $action = '';

	/**
	* Constructor
	*/
	function __construct($action)
	{
		global $admin_phrases;
		
		
		$this->action = $action;
		$this->pluginid = GetPluginID('Contact Form');
		
		$this->inputs[1] = 'Text';
		$this->inputs[2] = 'Email';
		$this->inputs[3] = 'Textarea';
		$this->inputs[4] = 'Yes/No';
		$this->inputs[5] = 'Checkbox';
		$this->inputs[6] = 'URL';
		$this->inputs[7] = 'Number';
		$this->inputs[8] = 'Date';
		
		$this->settings = GetPluginSettings($this->pluginid);
		
		$source ='';
		foreach($this->inputs as $key => $value)
		{
			$source .= "{value: $key, text: '$value'},\n";
		}
		
		sd_header_add(array(
		  'js'    => array(ROOT_PATH.ADMIN_STYLES_FOLDER . '/assets/js/x-editable/bootstrap-editable.min.js',
		  					ROOT_PATH.ADMIN_STYLES_FOLDER . '/assets/js/jquery.validate.min.js',
							ROOT_PATH.ADMIN_STYLES_FOLDER . '/assets/js/additional-methods.min.js',
							ROOT_PATH.ADMIN_STYLES_FOLDER . '/assets/js/jquery.dataTables.min.js ',
							ROOT_PATH.ADMIN_STYLES_FOLDER . '/assets/js/jquery.dataTables.bootstrap.js'),
		   // NO ROOT_PATH for "css" entries!
		  'css'   => array(ROOT_PATH.ADMIN_STYLES_FOLDER . '/assets/css/bootstrap-editable.css'),
		  
		  'other' => array('
		<script type="text/javascript">
		
		//<![CDATA[
		$(document).ready(function() {
			
			var myTable = 
			 $("#entry-table").dataTable({
				 "aoColumns" : [
				 null,
				 null,
				 null,
				 null,
				 null,
				 {"bSortable" : false},
				 {"bSearchable": false, "bSortable" : false},
				 {"bSearchable": false, "bSortable" : false}
				 ]
				 
				 
				 });
			
			$.fn.editable.defaults.url = "'.ROOT_PATH.'plugins/p6_contact_form/ajax.php";
			$.fn.editable.defaults.mode = "inline";
			
			$("#fields a#field_name").editable(
			{
				ajaxOptions: {
					dataType: "json"
				}
			});
			
			$("#fields a#field_type").editable(
			{
				source: [
						'.$source.'
						]
						
			});
			
			$("#fields a#required").editable(
			{
				source: [
						{value: 1, text: "'.AdminPhrase('common_yes').'"},
						{value: 0, text: "'.AdminPhrase('common_no').'"}
						]
			});
			
			$("#fields a#cssclass").editable();
			$("#fields a#displayorder").editable();
			
			
			$("a.deletefield").on("click", function(e) {
	  			e.preventDefault();
				var fid = $(this).attr("id");
  				bootbox.confirm("'.AdminPhrase('confirm_delete_field') .'", function(result) {
    			if(result) {
       				$.post("'.ROOT_PATH.'plugins/p6_contact_form/ajax.php", { id : fid, action : "delete" })
					.done(function(data) {
						$("#tr-"+fid).fadeOut("slow");;
					});				
				}
    			});
  			});
			
			$("a.deleteentry").on("click", function(e) {
	  			e.preventDefault();
				var fid = $(this).attr("id");
  				bootbox.confirm("'.AdminPhrase('confirm_delete_entry') .'", function(result) {
    			if(result) {
       				$.post("'.ROOT_PATH.'plugins/p6_contact_form/ajax.php", { id : fid, action : "deleteentry" })
					.done(function(data) {
						$("#tr-"+fid).fadeOut("slow");;
					});				
				}
    			});
  			});
			
			
			// Validate new field form
			$("#new_field").validate({
				errorClass: "has-error",
				validClass: "has-info",
				rules: {
					field_name: {
						required: true,
						minlength: 3
					},
					
					input_type: "required",
					
					display_order: {
						required: true,
						number: true
					}
				},
				
				highlight: function(element, errorClass, validClass) {
					$(element).closest(".form-group").removeClass(validClass).addClass(errorClass);
				},
				
				unhighlight: function(element, errorClass, validClass) {
					$(element).closest(".form-group").removeClass(errorClass).addClass(validClass);
				}
			});
			
			 
		});
		//]]>
		</script>')
		  ), false);
	}
		

	/**
	* Class Contact Form Settings
	*/
	
		
	/**
	* Prints the contact form menu
	*/
	function PrintMenu()
	{
		global $refreshpage;
		
		echo '<ul class="nav nav-pills no-margin-left">
				<li role="presentation" ' . iif($this->action == 'displayfields', 'class="active"') . '><a class="" href="'.$refreshpage.'&action=displayfields">'.AdminPhrase('form_fields').'</a></li>
				<li role="presentation" ' . iif($this->action == 'displaysettings' OR $this->action == '', 'class="active"') . '><a class="" href="'.$refreshpage.'&action=displaysettings">'.AdminPhrase('plugin_settings').'</a></li>
				
				<li role="presentation" ' . iif($this->action == 'displaysubmissions', 'class="active"') . '><a class="" href="'.$refreshpage.'&action=displaysubmissions">'.AdminPhrase('submissions').'</a></li>
			</ul>
			<div class="hr hr-8"></div>
			<div class="space-20"></div>';
	}
	
	/**
	* Displays contact form fields
	*/
	function DisplayFields()
	{
		
		global $DB, $refreshpage;
		
		// Start the table
		echo '<button type="button" class="btn btn-success btn-xs" data-toggle="modal" data-target="#newfield">
  					<i class="ace-icon fa fa-plus"></i> '. AdminPhrase('new_field') .'
				</button>
				<div class="space-6"></div>';
		echo '<div class="table-header">
					'. AdminPhrase('form_fields') . '
			</div>
			<table id="fields" class="table table-bordered table-striped">
				<thead>
					<th width="25%">'.AdminPhrase('field_name').'</th>
					<th width="25%">'.AdminPhrase('input_type').'</th>
					<th width="15%">'.AdminPhrase('field_required').'</th>
					<th width="25%">'.AdminPhrase('css_class').'</th>
					<th width="5%">'.AdminPhrase('display_order').'</th>
					<th width="5%">'.AdminPhrase('delete').'</th>
				</thead>
				<tbody>';
					
					
				
		
		// Get fields
		$fields = $DB->query("SELECT * FROM {p6_fields} ORDER BY displayorder ASC");
		
		while($field = $DB->fetch_array($fields))
		{
			
			echo '<tr id="tr-'.$field['id'].'">
					<td>
						<a href="#" id="field_name" data-name="field_name" data-type="text" data-pk="'.$field['id'].'" >' . $field['field_name'] . '</a>
					</td>
					<td>
						<a href="#" id="field_type" data-type="select" data-pk="'.$field['id'].'" data-value="'.$field['field_type'].'" >' . $this->inputs[$field['field_type']] . '</a></td>
					<td>
						<a href="#" id="required" data-type="select"  data-pk="'.$field['id'].'" data-value="'.$field['required'].'"  >
						' . ($field['required'] == 1 ? '<span class="green bolder">'.AdminPhrase('common_yes').'</span>' : '<span class="red bolder">'.AdminPhrase('common_no').'</span>')	. '</a>	
					</td>
					<td>
						<a href="#" id="cssclass" data-type="text" data-inputclass="" data-pk="'.$field['id'].'"  > ' . $field['cssclass'] .'</a>
					</td>
					<td>
						<a href="#" id="displayorder" data-type="text" data-inputclass="" data-pk="'.$field['id'].'"  > ' . $field['displayorder'] .'</a>
					</td>
					
					<td>
					'. ($field['id'] > 4 ? '<a href="#" id="'.$field['id'].'" class="deletefield"><i class="ace-icon fa fa-trash-o bigger-130 red"></i>' : '<i class="ace-icon fa fa-minus-circle bigger-130 red"></i>').'</td>
				</tr>';
			
		}
		
		echo '</tbody>
			</table>';
			
			
		echo '<div class="modal fade" id="newfield">
				  <div class="modal-dialog">
					<div class="modal-content">
					  <div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title">'.AdminPhrase('new_form_field').'</h4>
					  </div>
					  <div class="modal-body">
						<form class="form-horizontal" id="new_field" method="POST" action="'.$refreshpage.'">
						<input type="hidden" name="action" value="createfield">					
						  <div class="form-group">
							<label class="control-label col-sm-4" for="field_name">'. AdminPhrase('field_name') . '</label>
							<div class="col-sm-6">
							<input type="text" name="field_name" class="form-control">
							</div>
						</div>
						 <div class="form-group">
							<label class="control-label col-sm-4" for="input_type">'. AdminPhrase('input_type') . '</label>
							<div class="col-sm-6">
							<select id="input_type" name="input_type" class="form-control">';
								foreach($this->inputs as $key => $value)
								{
									echo '<option value='.$key.'>'.$value.'</option>';
								}
								
							echo'</select>
							</div>
						</div>
						 <div class="form-group">
							<label class="control-label col-sm-4" for="required">'. AdminPhrase('required') . '</label>
							<div class="col-sm-6">
							<input type="checkbox" class="ace" id="required" name="required" value="1"><span class="lbl"></span>
							</div>
						</div>
						 <div class="form-group">
							<label class="control-label col-sm-4" for="display_order">'. AdminPhrase('css_class') . '</label>
							<div class="col-sm-6">
							<input type="text" name="css_class" class="form-control">
							</div>
						</div>
						 <div class="form-group">
							<label class="control-label col-sm-4" for="display_order">'. AdminPhrase('display_order') . '</label>
							<div class="col-sm-6">
							<input type="text" name="display_order" class="form-control">
							</div>
						</div>
						
					  </div>
					  <div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">'. AdminPhrase('close').'</button>
						<button type="submit" class="btn btn-primary">'.AdminPhrase('save').'</button>
						</form>
					  </div>
					</div><!-- /.modal-content -->
				  </div><!-- /.modal-dialog -->
				</div><!-- /.modal -->';


		return true;
			
		
		
	}
	
	
	/**
	* Creates a new form field
	*/
	function CreateField()
	{
		global $DB;
		
		$fieldname = GetVar('field_name','','string');
		$fieldtype	= GetVar('input_type','','int');
		$required = GetVar('required','0','int');
		$displayorder = GetVar('display_order','','int');
		$cssclass	= GetVar('css_class', '', 'string');
		
		// Check values
		if(!strlen($fieldname) || !strlen($displayorder) || !is_numeric($displayorder))
		{
			
			DisplayMessage(AdminPhrase('error_general_error_occurred'), true);
			$this->DisplayFields();
			return;
		}
		
		// Check if field name exists
		if($DB->query_first("SELECT id FROM {p6_fields} WHERE field_name='$fieldname'"))
		{
			DisplayMessage(AdminPhrase('error_duplicate_field'), true);
			$this->DisplayFields();
			return;
		}
			
		
		
		$DB->query("INSERT INTO {p6_fields} (field_name, field_type, required, cssclass, displayorder) VALUES ('$fieldname', '$fieldtype', $required, '$cssclass', '$displayorder')");
		DisplayMessage(AdminPhrase('field_created_successfully'));
		
		$this->DisplayFields();
		
	}
	
	/**
	* Form Submissions
	*/
	function FormSubmissions()
	{
		global $DB, $mainsettings;
		
		if(!$this->settings['log_entries_in_database'])
		{
			DisplayMessage(AdminPhrase('error_log_entries_not_enabled'), true);
			//return;
		}
		
		echo "<div class='table-header'>" . AdminPhrase('submissions') . "</div>
				<table class='table table-bordered table-striped' id='entry-table'>
					<thead>
						<th width='5%'>" . AdminPhrase('id') . "</th>
						<th width='10%'>" . AdminPhrase('date') . "</th>
						<th width='10%'>" . AdminPhrase('ip_address') . "</th>
						<th width='10%'>" . AdminPhrase('full_name') . "</th>
						<th width='15%'>" . AdminPhrase('email') . "</th>
						<th width='20%'>" . AdminPhrase('subject') . "</th>
						<th width='25%'>" . AdminPhrase('message') . "</th>
						<th width='5%'>" . AdminPhrase('delete') . "</th>
					</thead>
					<tbody>";
		
		$entries = $DB->query("SELECT * FROM {p6_submissions} ORDER BY ID DESC");
		
		while($entry = $DB->fetch_array($entries))
		{
			echo "<tr id='tr-$entry[id]'>
					<td>$entry[id]</td>
					<td>" . date($mainsettings['dateformat'], $entry[submit_date]) . "</td>
					<td>$entry[ip_address]</td>
					<td>$entry[name]</td>
					<td>$entry[email]</td>
					<td>$entry[subject]</td>
					<td>". nl2br($entry['message']). "</td>
					<td><a href='#' id='$entry[id]' class='deleteentry'><i class='ace-icon fa fa-trash-o bigger-130 red'></i></a></td>
				</tr>";
		}
		
		
		echo "</tbody>
			</table>";
			
	}
	
}


$action = GetVar('action','','string');

$p6_settings = new p6_settings($action);

// Display Menu
$p6_settings->PrintMenu();


switch ($p6_settings->action)
{
	case 'displayfields':
		$p6_settings->DisplayFields();
	break;
	
	case 'createfield':
		$p6_settings->CreateField();
	break;
	
	case 'displaysubmissions':
		$p6_settings->FormSubmissions();
	break;
		
	default:
	
		PrintPluginSettings($pluginid, array('contact_form_settings','Styles'), $refreshpage);
}


