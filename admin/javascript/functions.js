function ConfirmDelete(msg) {
  if(msg === null) {
    msg = 'Delete this item?';
  }
  if(confirm(msg)) {
    return true;
  } else {
    return false;
  }
}

function sd_checkbox_trigger(master_id, class_name) {
  jQuery("input#"+master_id).change(function(e){
    e.preventDefault();
    var parent = $(this);
    $("input[type=checkbox]."+class_name).each(function(){
      $(this).attr('checked',parent.is(':checked'));
    });
  });
}

/*
frm is the form object
elm is the checkbox which initiated the click
val is the unique group name
*/
function select_deselectAll (formname, elm, group) {
	var frm = document.forms[formname];
  /* Loop through all elements */
  for (i=0; i<frm.length; i++)
  {
    /* Look for our Header Template's Checkbox */
    if (elm.attributes['checkall'] != null && elm.attributes['checkall'].value == group)
    {
      if (frm.elements[i].attributes['checkme'] != null && frm.elements[i].attributes['checkme'].value == group)
      {
        frm.elements[i].checked = elm.checked;
      }
    }
    /* Work here with the Item Template's multiple checkboxes */
    else if (frm.elements[i].attributes['checkme'] != null && frm.elements[i].attributes['checkme'].value == group)
    {
      /* Check if any of the checkboxes are not checked, and then uncheck top select all checkbox */
      if(frm.elements[i].checked == false)
      {
        frm.elements[1].checked = false; /* uncheck main select all checkbox */
      }
    }
  }
}

function initCheckboxGroup(formname, group) {
	var frm = document.forms[formname];
	var checkAllIndex = -1;
	var unchecked = false;
  for (i=0; i<frm.length; i++)
  {
    /* Look for our Header Template's Checkbox */
    if (frm.elements[i].attributes['checkall'] != null && frm.elements[i].attributes['checkall'].value == group)
    {
      checkAllIndex = i
    }
    /* Work here with the Item Template's multiple checkboxes */
    else if (frm.elements[i].attributes['checkme'] != null && frm.elements[i].attributes['checkme'].value == group)
    {
      /* Check if any of the checkboxes are not checked, and then uncheck top select all checkbox */
      if(frm.elements[i].checked == false)
      {
        unchecked = true;
        break;
      }
    }
  }

  /* If all the boxes in this group are checked, check the checkall */
  if(checkAllIndex != -1 && unchecked == false) {
    frm.elements[checkAllIndex].checked = true;
  }
}
