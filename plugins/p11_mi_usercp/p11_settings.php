<?php
if(!defined('IN_PRGM')) exit();

$refreshpage = str_replace('&amp;load_wysiwyg=1','',$refreshpage);
define('SD_PROFILE_REDIR', $refreshpage.'&amp;action=profile_config'.PrintSecureUrlToken());

// ############################################################################
// ############################################################################


function ProfileConfig()
{
  global $DB, $refreshpage, $sdlanguage, $UserProfile;

  SDProfileConfig::init();
  $profile_fields = SDProfileConfig::GetProfileFields();
  $profile_groups = SDProfileConfig::GetProfileGroups();
  $max_columns = 10;


  echo '<h3 class="header blue lighter">' . AdminPhrase('settings') . '</h3>';
  

  echo '
  <form id="ucpForm" name="ucpForm" class="uniForm" enctype="multipart/form-data" action="'.$refreshpage.'" method="post">
  '.PrintSecureToken();
  
  echo'<ul class="nav nav-tabs" role="tablist">
			<li class="active">
				<a href="#fields" data-toggle="tab">
				<i class="blue ace-icon fa fa-edit bigger-120"></i> '.AdminPhrase('profile_fields').'</a>
			</li>
			<li>
				<a href="#groups" data-toggle="tab">
				<i class="blue ace-icon fa fa-group bigger-120"></i> '.AdminPhrase('menu_users_profile_groups').' </a>
			</li>
		</ul>';

  // Temp. storage of groups output:
  echo '
 <div class="tab-content">
  	 <div class="tab-pane" id="groups">
		  <div class="alert alert-warning":>
			  <strong>Important:</strong> profile groups are <strong>not</strong> for configuring
			  usergroup permissions for site content! For that please use the corresponding Usergroups main menu item.<br />
			  Within the user profile you can only configure the <strong>logical grouping</strong> of fields for the
			  frontpage&#39;s user profile- and member pages.
		  </div>
		  
		  	<a class="btn  btn-success" href="'.$refreshpage.'&amp;action=profilegroupaddform'.PrintSecureUrlToken().'"><i class="ace-icon fa fa-plus"></i> ' . AdminPhrase('profiles_new_group') . '</a><br />
  <table class="table table-striped table-bordered">
  <thead>
  <tr>
    <th >'.AdminPhrase('profiles_col_groupname').'<br />(internal)</th>
    <th >'.AdminPhrase('profiles_col_displayorder').'</th>
    <th >'.AdminPhrase('profiles_col_displayname').'</th>
    <th  align="center">'.AdminPhrase('profiles_col_visible_to_user').'</th>
    <th  align="center">'.AdminPhrase('profiles_col_on_public_profile').'</t=h>
    <th  align="center">'.AdminPhrase('profiles_col_friendsonly').'</th>
    <th  align="center" width="150">' . AdminPhrase('profiles_col_permissions').'</th>
    <th  align="center">'.AdminPhrase('profiles_col_delete_group').'</th>
  </tr>
  </thead>
  <tbody>
  ';
  $group_select = '<select id="fieldgroup" class="col-sm-2">';

  // Display user profile groups (users_field_groups)
  foreach($profile_groups AS $group)
  {
    $id = $group['groupname_id'];
    $group_select .= '<option '.($id==1?'selected="selected" ':'').'value="'.$id.'">'.$group['displayname'].'</option>';
    if($id > 7)
    {
      $delete_group_link = '<a class="deletelink" href="'.$refreshpage.'&amp;action=profilegroupdelete&amp;gid='.$id.PrintSecureUrlToken() . '"><i class="ace-icon fa fa-trash-o red bigger-120"></i></a>';
    }
    else
    {
      $delete_group_link = '<i class="ace-icon fa fa-trash-o red bigger-120"></i>&nbsp;</span>';
    }
    echo '
    <tr>
      <td >
        <input type="hidden" name="grp_id['.$id.']" value="'.$id.'" />
        <strong>' . $group['groupname'] . '</strong>
      </td>
      <td  align="center">
        '.(!empty($group['groupname_id'])?'<input type="text" name="grp_displayorder['.$id.']" value="'.(!empty($group['displayorder'])?(int)$group['displayorder']:0).'" size="2" class="form-control" />':'').'</td>
      <td ><input type="text" class="form-control" name="grp_displayname['.$id.']" value="'.$group['displayname'].'" size="50" style="width: 90%" /></td>
      <td  align="center">
        '.(!empty($group['groupname_id'])?'<input type="checkbox" class="ace" name="grp_is_visible['.$id.']" value="1" '.($group['is_visible']?'checked="checked"':'').' />':'').'<span class="lbl"></span></td>
      <td  align="center">';

    if(empty($group['groupname_id']) || ($group['groupname_id']==1))
    {
      echo '<i class="ace-icon fa fa-minus-circle red bigger-120"></i>
      <input type="hidden" name="grp_is_public['.$id.']" value="1" />';
    }
    else
    {
      echo '<input type="checkbox" class="ace" name="grp_is_public['.$id.']" value="1" '.($group['is_public']?'checked="checked"':'').' /><span class="lbl"></span>';
    }

    echo '
      </td>
      <td  align="center" style="width: 90px;">'.(!empty($group['groupname_id'])?'<input type="checkbox" class="ace" name="friends_only['.$id.']" value="1" '.($group['friends_only']?'checked="checked"':'').' />':'').'<span class="lbl"></span></td>
      <td  align="center" style="width: 150px;">';
    if(!empty($group['groupname_id']) && ($group['groupname_id']>1))
    {
      $perm = array(
        'columns'     => 1,
        'description' => '',
        'input'       => 'usergroups',
        'settingid'   => $id,
        'style'       => 'width:145px !important',
        'title'       => '',
        'value'       => $group['access_view'],
        'separator'   => '|',
      );
      PrintAdminSetting($perm, null, true, true);
    }

    echo '</td>
      <td  align="center" style="width: 90px;">' . $delete_group_link . '</td>
    </tr>
    ';
  } //foreach
  $group_select .= '</select>';

  echo '
  
  </tbody>
  </table>
</div>';
 // $groups_tab_content = ob_get_clean();

  // ###########################################################################
  // PROFILE FIELDS
  // ###########################################################################
  $group_header = '
  <thead>
  <tr>
    <th >'.AdminPhrase('profiles_col_order').'</th>
	<th>'.AdminPHrase('profiles_col_group').'</th>
    <th >'.AdminPhrase('profiles_col_displayname').'</th>
    <th > </td>
    <th  align="center">'.AdminPhrase('profiles_col_visible_to_user').'</th>
    <th  align="center">'.AdminPhrase('profiles_col_visible_on_frontpage').'</th>
    <th  align="center">'.AdminPhrase('profiles_col_public_req').'</th>
    <th  align="center">'.AdminPhrase('profiles_col_view_only').'</th>
    <th  align="center">'.AdminPhrase('profiles_col_required').'</th>
    <th  align="center">'.AdminPhrase('profiles_col_reg_form').'</th>
    <th  align="center">'.AdminPhrase('profiles_col_reg_form_req').'</th>
  </tr>
  </thead>
  <tbody>';

  echo '
  	 <div class="tab-pane in active" id="fields">
  <div class="well">
      <h5 class="blue smaller lighter">'.AdminPhrase('users_add_field_to_group').'</h5>
      '.str_replace('col-sm-2','col-sm-2 search-query',$group_select).'
      &nbsp;<a class="profilefieldadd btn btn-success btn-xs" href="'.$refreshpage.'&amp;action=profilefieldadd'.PrintSecureUrlToken().'"><i class="ace-icon fa fa-plus"></i> ' . AdminPhrase('profiles_new_field') . '</a></div>';

  // Display user profile fields (users_fields)
  $group_select = str_replace('col-sm-2','form-control" style="width:200px;"',$group_select);
  unset($field);
  $current = -1;
  foreach($profile_fields AS $field)
  {
    if(!isset($field['fieldnum'])) continue;

    if($current != $field['groupname_id'])
    {
      if($current !== -1) { echo '</tbody></table>'; }
      echo '
      <div class="table-header">' . (isset($field['groupname'])?$field['groupname']:'*') . '
      </div>
      <table id="table-'.$field['groupname_id'].'" class="table table-striped table-bordered">
      '.$group_header;
      $current = $field['groupname_id'];
    }
    $id = $field['fieldnum'];
    $is_custom = !empty($field['is_custom']);
    if(!$is_custom)
    {
      $edit_link = '';
      $del_link = '';
    }
    else
    {
      $edit_link = '<span class="action-buttons bigger-125"><a class="imgedit" href="'.$refreshpage.'&amp;action=profilefieldedit&amp;fieldnum='.$id.PrintSecureUrlToken().'" title="Edit?"><i class="ace-icon fa fa-pencil"></i>&nbsp;</a>';
      $del_link = '<a class="imgdelete" href="'.$refreshpage.'&amp;action=profilefielddelete&amp;fieldnum='.$id.PrintSecureUrlToken().'" title="Delete?"><i class="ace-icon fa fa-trash-o red "></i>&nbsp;</a></span>';
    }
    echo '
    <tr id="groupfields_'.$id.'">
      <td class="left" width="170">
        <input type="hidden" name="fieldname['.$id.']" value="'.$field['name'].'" />
        <input type="hidden" name="fld_id['.$id.']" value="'.$id.'" />
        <input type="text" class="col-sm-3" name="fieldorder['.$id.']" value="'.(!empty($field['fieldorder'])?(int)$field['fieldorder']:0).'" size="3" maxlength="4" /></td>
	<td >';

    $tmp = str_replace('id="fieldgroup"','name="group['.$id.']"', $group_select);
    $tmp = str_replace(' selected="selected"', '', $tmp);
    $tmp = str_replace(' value="'.$current.'"', ' value="'.$current.'" selected="selected"', $tmp);
    echo $tmp;
    echo '
      </td>
      <td class="align-middle"><input type="text" class="form-control" name="label['.$id.']" value="'.(!empty($field['label'])?$field['label']:'-').'"  /><span class="helper-text">
        '.($is_custom?'<strong>':'').$field['name'].($is_custom?'</strong>':'').'</span></td>
      <td class="center align-middle">'.$edit_link.' '.$del_link.'</td>
      <td class="center align-middle" align="center"><input type="checkbox" class="ace" name="visible['.$id.']" value="1" '.($field['visible']?'checked="checked"':'').' /><span class="lbl"></span></td>
      <td class="center align-middle">';
    if(!empty($field['ucp_only']))
    {
      echo '<i class="ace-icon fa fa-minus-circle red bigger-120"></i>
      <input type="hidden" name="public['.$id.']" value="1" />';
    }
    else
    {
      echo '<input type="checkbox" class="ace" name="public['.$id.']" value="1" '.($field['public']?'checked="checked"':'').' /><span class="lbl"></span>';
    }
    echo '</td>
      <td class="center align-middle">';
    if(!empty($field['ucp_only']))
    {
      echo '<i class="ace-icon fa fa-minus-circle red bigger-120"></i>
      <input type="hidden" name="public_req['.$id.']" value="0" />';
    }
    else
    {
      echo '<input type="checkbox" class="ace" name="public_req['.$id.']" value="1" '.($field['public_req']?'checked="checked"':'').' /><span class="lbl"></span>';
    }
    echo '</td>
      <td class="center align-middle" ><input type="checkbox" class="ace" name="readonly['.$id.']" value="1" '.($field['readonly']?'checked="checked"':'').' /><span class="lbl"></span></td>
      <td class="center align-middle"><input type="checkbox" class="ace" name="required['.$id.']" value="1" '.($field['required']?'checked="checked"':'').' /><span class="lbl"></span></td>
      <td class="center align-middle"><input type="checkbox" class="ace" name="reg_form['.$id.']" value="1" '.($field['reg_form']?'checked="checked"':'').' /><span class="lbl"></span></td>
      <td class="center align-middle"><input type="checkbox" class="ace" name="reg_form_req['.$id.']" value="1" '.($field['reg_form_req']?'checked="checked"':'').' /><span class="lbl"></span></td>
    </tr>';
  } //foreach

  echo '
  </tbody>
  </table>
</div>';

  // ###########################################################################
  // PROFILE GROUPS
  // ###########################################################################
  echo $groups_tab_content;

  PrintSubmit('profile_save_config', AdminPhrase('update_profile_config'), 'ucpForm', 'fa-check');
  echo '
  </form>
  </div>';

} //ProfileConfig


// ############################################################################
// ############################################################################


function ProfileFieldEdit()
{
  $fnum = GetVar('fieldnum', 0, 'whole_number');
  ProfileFieldAdd($fnum);
} //ProfileFieldEdit


function ProfileFieldAdd($fieldnum=false)
{
  global $DB, $plugin_names, $refreshpage, $sdlanguage, $UserProfile;

  if(!CheckFormToken())
  {
    RedirectPage(SD_PROFILE_REDIR,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $field = false;
  if(($fieldnum!==false) && is_numeric($fieldnum))
  {
    $field = $DB->query_first('SELECT * FROM {users_fields} WHERE fieldnum = %d AND ifnull(is_custom,0) = 1 LIMIT 1',$fieldnum);
    if(!$field || empty($field[0]))
    {
      RedirectPage(SD_PROFILE_REDIR);
      return;
    }
    $gid      = $field['groupname_id'];
    $order    = $field['fieldorder'];
    $type     = $field['fieldtype'];
    $length   = $field['fieldlength'];
    $label    = $field['fieldlabel'];
    $visible  = ($field['fieldshow'] ? 1 : 0);
    $public   = ($field['public_status'] ? 1 : 0);
    $readonly = ($field['readonly']  ? 1 : 0);
    $required = ($field['req']       ? 1 : 0);
    $public_req = ($field['public_req'] ? 1 : 0);
  }
  else
  {
    $gid      = GetVar('gid',        1, 'whole_number');
    $order    = GetVar('fieldorder', '', 'string', true, false);
    $order    = Is_Valid_Number($order, 0, 0, 9999);
    $type     = GetVar('fieldtype',  'text', 'string', true, false);
    $length   = GetVar('fieldlength',255, 'whole_number', true, false);
    $label    = GetVar('fieldlabel', '', 'string', true, false);
    $visible  = (GetVar('fieldshow', 1,  'bool', true, false) ? 1 : 0);
    $public   = (GetVar('public',    0,  'bool', true, false) ? 1 : 0);
    $readonly = (GetVar('readonly',  0,  'bool', true, false) ? 1 : 0);
    $required = (GetVar('required',  0,  'bool', true, false) ? 1 : 0);
    $public_req = (GetVar('public_req', 0, 'bool', true, false) ? 1 : 0);
  }
  // Prepare display of user profile groups
  //$UserProfile->LoadFormFields(false, true);
  SDProfileConfig::init();
  $profile_groups = SDProfileConfig::GetProfileGroups();
  $group_select = '<select id="fieldgroup" name="gid" class="form-control">';
  foreach($profile_groups AS $group)
  {
    $id = $group['groupname_id'];
    $group_select .= '<option '.($id==$gid?'selected="selected" ':'').'value="'.$id.'">'.$group['displayname'].'</option>';
  }
  $group_select .= '</select>';

  echo '
  <form action="'.$refreshpage.'&amp;action=profilefieldinsert" method="post" class="form-horizontal">
  '.PrintSecureToken();

  if($field!==false)
  {
    echo '<input type="hidden" name="fieldnum" value="'.$fieldnum.'" />';
  }

  echo '<h3 class="header blue lighter">' . AdminPhrase('users_add_profile_field') . '</h3>';

  echo '
  <div class="form-group">
     <label class="control-label col-sm-4">'.str_replace('<br />',' ',AdminPhrase('profiles_col_groupname')).'</label>
  <div class="col-sm-6">'.$group_select.'</div>
</div>
  <div class="form-group">
     <label class="control-label col-sm-4">'.AdminPhrase('profiles_field_order').'</label>
  <div class="col-sm-6"><input type="text" class="form-control" name="fieldorder" size="2" value="'.$order.'" /></div>
</div>
  <div class="form-group">
     <label class="control-label col-sm-4">'.AdminPhrase('profiles_field_label').'</label>
  <div class="col-sm-6"><input type="text" class="form-control" name="fieldlabel" size="70" value="'.$label.'" /></div>
</div>
  <div class="form-group">
     <label class="control-label col-sm-4">'.AdminPhrase('profiles_field_type').'</label>
  <div class="col-sm-6">
      <select class="form-control" name="fieldtype"'.($field!==false?' disabled="disabled"':'').'>
      <option value="text"'.($type=='text'?' selected="selected"':'').'>'.AdminPhrase('profiles_fieldtype_text').'</option>
      <option value="textarea"'.($type=='textarea'?' selected="selected"':'').'>'.AdminPhrase('profiles_fieldtype_textarea').'</option>
      <option value="date"'.($type=='date'?' selected="selected"':'').'>'.AdminPhrase('profiles_fieldtype_date').'</option>
      <option value="yesno"'.($type=='yesno'?' selected="selected"':'').'>'.AdminPhrase('profiles_fieldtype_yesno').'</option>
      <option value="bbcode"'.($type=='bbcode'?' selected="selected"':'').'>'.AdminPhrase('profiles_fieldtype_bbcode').'</option>
      </select>
    </div>
</div>
  <div class="form-group">
     <label class="control-label col-sm-4">'.AdminPhrase('profiles_field_length').'</label>
  <div class="col-sm-6">';
  if($field===false)
  {
    echo '<input type="text" class="form-control" name="fieldlength" size="3" value="'.$length.'" />';
  }
  else
  {
    echo $length;
  }
  echo '</div>
</div>
  <div class="form-group">
     <label class="control-label col-sm-4">'.AdminPhrase('profiles_field_show').'</label>
  <div class="col-sm-6"><input type="checkbox" class="ace" name="visible" value="1" '.($visible?'checked="checked"':'').' /><span class="lbl"></span></div>
</div>
  <div class="form-group">
     <label class="control-label col-sm-4">'.AdminPhrase('profiles_field_public').'</label>
  <div class="col-sm-6"><input type="checkbox" class="ace" name="public" value="1" '.($public?'checked="checked"':'').' /><span class="lbl"></span></div>
</div>
  <div class="form-group">
     <label class="control-label col-sm-4">'.AdminPhrase('profiles_field_public_req').'</label>
  <div class="col-sm-6"><input type="checkbox" class="ace" name="public_req" value="1" '.($public_req?'checked="checked"':'').' /><span class="lbl"></span></div>
</div>
  <div class="form-group">
     <label class="control-label col-sm-4">'.AdminPhrase('profiles_field_required').'</label>
  <div class="col-sm-6"><input type="checkbox" class="ace" name="required" value="1" '.($required?'checked="checked"':'').' /><span class="lbl"></span></div>
</div>
  <div class="form-group">
     <label class="control-label col-sm-4">'.AdminPhrase('profiles_field_readonly').'</label>
  <div class="col-sm-6"><input type="checkbox" class="ace" name="readonly" value="1" '.($readonly?'checked="checked"':'').' /><span class="lbl"></span></div>
</div>
  <h3 class="header blue lighter">'.$plugin_names[12].' Plugin</h3>
  <div class="form-group">
     <label class="control-label col-sm-4">'.AdminPhrase('profiles_field_reg_form').'</label>
  <div class="col-sm-6"><input type="checkbox" class="ace" name="reg_form" value="1" '.($required?'checked="checked"':'').' /><span class="lbl"></span></div>
</div>
  <div class="form-group">
     <label class="control-label col-sm-4">'.AdminPhrase('profiles_field_reg_form_req').'</label>
  <div class="col-sm-6"><input type="checkbox" class="ace" name="reg_form_req" value="1" '.($required?'checked="checked"':'').' /><span class="lbl"></span></div>
</div>';
 
  echo '
  <div class="center">
  	<button class="btn btn-info" type="submit" value="" /><i class="ace-icon fa fa-plus"></i>'.AdminPhrase($field===false?'add_profile_field':'update_profile_field'). '</button>
	</div>
  </form>';

} //ProfileFieldAdd


// ############################################################################
// ############################################################################


function ProfileFieldDelete()
{
  global $DB, $refreshpage, $sdlanguage, $UserProfile;

  if(!CheckFormToken())
  {
    RedirectPage(SD_PROFILE_REDIR,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $confirmdelete = GetVar('confirmdelete', '', 'string', true, false);
  $fieldnum      = GetVar('fieldnum', 0, 'whole_number');

  if(!empty($fieldnum))
  {
    $validfield = false;
    if(!empty($fieldnum))
    {
      $validfield = $DB->query_first('SELECT fieldnum, fieldname, fieldlabel FROM {users_fields} WHERE fieldnum = %d AND ifnull(is_custom,0) = 1 LIMIT 1',$fieldnum);
    }

    if(($validfield === false) || ($confirmdelete == AdminPhrase('common_no')))
    {
      RedirectPage(SD_PROFILE_REDIR);
      return;
    }

    if(!$confirmdelete)
    {
      $description = AdminPhrase('profiles_confirm_delete_field');
      $hidden_input_values = '<input type="hidden" name="action" value="profilefielddelete" />';
      if(($validfield !== false) && !empty($validfield[0]))
      {
        $description .= '<br /><strong>&nbsp;&nbsp;' . $validfield['fieldlabel'].' ('.$validfield['fieldname'].')</strong>';
        $hidden_input_values .= '<input type="hidden" name="fieldnum" value="' . $fieldnum . '" />';
        // arguments: description, hidden input values, form redirect page
        ConfirmDelete($description, $hidden_input_values, ''.$refreshpage.'&amp;action=profilefielddelete');
        return;
      }
    }
    else if($confirmdelete == AdminPhrase('common_yes'))
    {
      if(!empty($validfield[0]))
      {
        $DB->query('DELETE FROM {users_fields} WHERE fieldnum = %d',$fieldnum);
        $DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users_data', 'custom_field_'.$fieldnum);
      }
    }
  }

  RedirectPage(SD_PROFILE_REDIR);

} //ProfileFieldDelete


// ############################################################################
// ############################################################################


function ProfileFieldInsert()
{
  global $DB, $refreshpage, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage(SD_PROFILE_REDIR,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $fieldnum = GetVar('fieldnum', 0, 'whole_number');
  $gid      = GetVar('gid', 0, 'whole_number');
  $order    = GetVar('fieldorder','', 'string', true, false);
  $order    = Is_Valid_Number($order, 0, 0, 9999);
  $type     = GetVar('fieldtype','text', 'string', true, false);
  $length   = GetVar('fieldlength',255,'whole_number', true, false);
  $length   = Is_Valid_Number($length, 255, 1, 255);
  $label    = GetVar('fieldlabel', '', 'string', true, false);
  $visible  = (GetVar('fieldshow', 1,  'bool', true, false) ? 1 : 0);
  $public   = (GetVar('public',    0,  'bool', true, false) ? 1 : 0);
  $public_r = (GetVar('public_req',0,  'bool', true, false) ? 1 : 0);
  $readonly = (GetVar('readonly',  0,  'bool', true, false) ? 1 : 0);
  $required = (GetVar('required',  0,  'bool', true, false) ? 1 : 0);
  $reg_form = (GetVar('reg_form',  0,  'bool', true, false) ? 1 : 0);
  $reg_form_req = (GetVar('reg_form_req',  0,  'bool', true, false) ? 1 : 0);

  if(empty($gid))
  {
    $errors[] = AdminPhrase('profiles_err_groupname');
  }
  if(!strlen($label))
  {
    $errors[] = AdminPhrase('profiles_err_displayname');
  }
  if(!in_array($type, SDProfileConfig::GetFieldTypes()))
  {
    $errors[] = AdminPhrase('profiles_err_fieldtype');
  }
  if(!empty($errors))
  {
    DisplayMessage($errors, true);
    ProfileFieldAdd();
    return false;
  }

  if(!empty($fieldnum) && is_numeric($fieldnum))
  {
    $DB->query("UPDATE {users_fields} SET groupname_id = %d, fieldlabel = '%s', fieldshow = %d,
       fieldorder = %d, req = %d, readonly = %d, public_status = %d , public_req = %d
       WHERE fieldnum = %d",
       $gid, $label, $visible, $order, $required, $readonly, $public, $public_r, $fieldnum);
  }
  else
  {
    switch($type)
    {
      case 'date':
      case 'radio':
      case 'whole_number':
        $vartype = 'integer';
        break;
      case 'currency':
      case 'decimal':
        $vartype = 'float';
        break;
      case 'yesno':
        $vartype = 'bool';
        break;
      default:
        $vartype = 'string';
        break;
    }
    $DB->query("INSERT INTO {users_fields} (`fieldnum`, `groupname_id`, `fieldname`, `fieldlabel`, `is_custom`, `fieldshow`, `fieldorder`,
       `crypted`, `fieldlength`, `fieldtype`, `vartype`, `fieldexpr`, `fieldmask`, `errdescr`,
       `req`, `upd`, `ins`, `seltable`, `readonly`, `public_status`, `public_req`)
      VALUES (null, %d, 'custom_field', '%s', 1, %d, %d,
       0, %d, '%s', '%s', '', '', '*',
       %d, 1, 1, '', %d, %d, %d)",
       $gid, $label, $visible, $order,
       $length, $type, $vartype,
       $required, $readonly, $public, $public_r);

    $fid       = $DB->insert_id();
    $fname     = 'custom_field_'.$fid;
    $isVarChar = in_array($type,array('bbcode','select','text','textarea','url','password'));
    $ftype     = ($isVarChar?'VARCHAR('.$length.') collate utf8_unicode_ci':'INT(11)');
    $fdefault  = ($isVarChar?"''":'0');

    $DB->query("UPDATE {users_fields} SET fieldname = '%s' WHERE fieldnum = %d",$fname,$fid);
    $DB->add_tablecolumn(PRGM_TABLE_PREFIX.'users_data', $fname, $ftype, 'NOT NULL DEFAULT '.$fdefault);
  }

  RedirectPage(SD_PROFILE_REDIR);

} //ProfileFieldInsert


// ############################################################################
// ############################################################################


function ProfileGroupAddForm()
{
  global $DB, $sdlanguage, $refreshpage;

  if(!CheckFormToken())
  {
    RedirectPage(SD_PROFILE_REDIR,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $groupname    = GetVar('groupname',   '', 'string', true, false);
  $displayorder = GetVar('displayorder','', 'string', true, false);
  $displayorder = Is_Valid_Number($displayorder, 0, 0, 9999);
  $displayname  = GetVar('displayname', '', 'string', true, false);
  $visible      = (GetVar('visible',    1,  'bool', true, false) ? 1 : 0);
  $public       = (GetVar('public',     1,  'bool', true, false) ? 1 : 0);
  
   echo '<h3 class="header blue lighter">' . AdminPhrase('users_add_profile_group') . '</h3>';

  echo '
  <form action="'.$refreshpage.'&amp;action=profilegroupinsert" method="post" class="form-horizontal">
  '.PrintSecureToken().'
  ';

  echo '
  <div class="form-group">
     <label class="control-label col-sm-4">'.str_replace('<br />',' ',AdminPhrase('profiles_col_groupname')).'</label>
     <div class="col-sm-6"><input type="text" class="form-control" name="groupname" value="'.$groupname.'" /></div></div>
  <div class="form-group">
     <label class="control-label col-sm-4">'.str_replace('<br />',' ',AdminPhrase('profiles_col_displayorder')).'</label>
     <div class="col-sm-6"><input type="text" class="form-control" name="displayorder" size="2" value="'.$displayorder.'" /></div></div>
  <div class="form-group">
     <label class="control-label col-sm-4">'.str_replace('<br />',' ',AdminPhrase('profiles_col_displayname')).'</label>
     <div class="col-sm-6"><input type="text" class="form-control" name="displayname" value="'.$displayname.'" /></div></div>
  <div class="form-group">
     <label class="control-label col-sm-4">'.str_replace('<br />',' ',AdminPhrase('profiles_col_visible_to_user')).'</label>
     <div class="col-sm-6"><input type="checkbox" class="ace" name="visible" value="1" '.($visible?'checked="checked"':'').' /><span class="lbl"></span></div></div>
  <div class="form-group">
     <label class="control-label col-sm-4">'.str_replace('<br />',' ',AdminPhrase('profiles_col_on_public_profile')).'</label>
     <div class="col-sm-6"><input type="checkbox" class="ace" name="public" value="1" '.($public?'checked="checked"':'').' /><span class="lbl"></span></div></div>';
 
  echo '
  <div class="center">
  	<button class="btn btn-info" type="submit" value=""><i class="ace-icon fa fa-plus"></i> '.AdminPhrase('add_profile_group').'</div>
  </form>';

} //ProfileGroupAddForm


// ############################################################################
// ############################################################################


function ProfileGroupInsert()
{
  global $DB, $SDCache, $sdlanguage;

  if(!CheckFormToken())
  {
    RedirectPage(SD_PROFILE_REDIR,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }
  $errors = array();
  $groupname    = GetVar('groupname',   '', 'string', true, false);
  $displayorder = GetVar('displayorder','', 'string', true, false);
  $displayorder = Is_Valid_Number($displayorder, 0, 0, 9999);
  $displayname  = GetVar('displayname', '', 'string', true, false);
  $visible      = (GetVar('visible',    0,  'bool', true, false) ? 1 : 0);
  $public       = (GetVar('public',     0,  'bool', true, false) ? 1 : 0);
  if(!strlen($displayname))
  {
    $errors[] = AdminPhrase('profiles_err_displayname');
  }
  if(!strlen($groupname))
  {
    $errors[] = AdminPhrase('profiles_err_groupname');
  }
  if(!empty($errors))
  {
    DisplayMessage($errors, true);
    ProfileGroupAddForm();
    return false;
  }

  $DB->query("INSERT INTO {users_field_groups}
    (groupname, displayorder, displayname, is_visible, is_public)
    VALUES ('%s', %d, '%s', %d, %d)",
    $groupname, $displayorder, $displayname, $visible, $public);

  $SDCache->delete_cacheid('UCP_PROFILE_GROUPS');
  $SDCache->delete_cacheid('UCP_MEMBER_GROUPS');

  RedirectPage(SD_PROFILE_REDIR);

} //ProfileGroupInsert


// ############################################################################
// ############################################################################


function ProfileGroupDelete()
{
  global $DB, $SDCache, $sdlanguage, $UserProfile;

  if(!CheckFormToken())
  {
    RedirectPage(SD_PROFILE_REDIR,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  $gid = GetVar('gid', 0, 'whole_number');
  if(($gid > 7))
  {
    $profile_fields = SDProfileConfig::GetProfileFields();
    foreach($profile_fields AS $field)
    {
      if(($field['groupname_id'] == $gid) && !empty($field['is_custom']) && (substr($field['name'],0,13)=='custom_field_'))
      {
        $DB->remove_tablecolumn(PRGM_TABLE_PREFIX.'users_data', $field['name']);
        $DB->query('DELETE FROM {users_fields} WHERE fieldnum= %d', $field['fieldnum']);
        $DB->query("DELETE FROM {phrases} WHERE pluginid = 11 AND varname = '%s'",
                    'select_profile_page_custom_field_'.$field['fieldnum']);
      }
    }
    $DB->query('DELETE FROM {users_field_groups} WHERE groupname_id = %d', $gid);

    $SDCache->delete_cacheid('UCP_PROFILE_GROUPS');
    $SDCache->delete_cacheid('UCP_MEMBER_GROUPS');
  }

  RedirectPage(SD_PROFILE_REDIR);

} //ProfileGroupDelete


// ############################################################################
// ############################################################################


function ProfileSaveConfig()
{
  global $DB, $sdlanguage, $SDCache;

  if(!CheckFormToken())
  {
    RedirectPage(SD_PROFILE_REDIR,'<strong>'.$sdlanguage['error_invalid_token'].'</strong>',2,true);
    return;
  }

  // Process Group Settings
  $ids = GetVar('grp_id',array(),'array',true,false);
  $o = GetVar('grp_displayorder',array(),'array',true,false);
  $n = GetVar('grp_displayname',array(),'array',true,false);
  $v = GetVar('grp_is_visible',array(),'array',true,false);
  $p = GetVar('grp_is_public',array(),'array',true,false);
  $f = GetVar('friends_only',array(),'array',true,false);
  $permissions = GetVar('settings',array(),'array',true,false);

  // Go through list and update selected groups:
  foreach($ids AS $id)
  {
    if($id > 0)
    {
      $access_view = !empty($permissions[$id]) ? (array)$permissions[$id] : false;
      if(empty($access_view))
      {
        $access_view = '';
      }
      else
      {
        $access_view = count($access_view) > 1 ? implode('|', $access_view) : $access_view[0];
        $access_view = empty($access_view) ? '' : '|'.$access_view.'|';
      }

      $DB->query("UPDATE {users_field_groups} SET displayorder = %d, displayname = '%s',
        is_visible = %d, is_public = %d, friends_only = %d, access_view = '%s'
        WHERE groupname_id = %d",
        (empty($o[$id]) ? 0 : (int)$o[$id]),
        (empty($n[$id]) ? '' : $n[$id]),
        (empty($v[$id]) ? 0 : 1),
        (empty($p[$id]) ? 0 : 1),
        (empty($f[$id]) ? 0 : 1),
        $access_view,
        $id
        );
    }
  }
  // Update Login group displayname:
  if(isset($n[0]))
  {
    $DB->query("UPDATE {phrases} SET customphrase = '%s' WHERE pluginid = 11 AND varname = 'groupname_user_credentials'", $n[0]);
  }

  // Process Field Settings
  $ids = GetVar('fld_id',array(),'array',true,false);
  $f   = GetVar('fieldorder',array(),'array',true,false);
  $g   = GetVar('group',array(),'array',true,false);
  $l   = GetVar('label',array(),'array',true,false);
  $n   = GetVar('fieldname',array(),'array',true,false);
  $v   = GetVar('visible',array(),'array',true,false);
  $ro  = GetVar('readonly',array(),'array',true,false);
  $req = GetVar('required',array(),'array',true,false);
  $pub = GetVar('public',array(),'array',true,false);
  $pubr= GetVar('public_req',array(),'array',true,false);
  $reg = GetVar('reg_form',array(),'array',true,false);
  $regq= GetVar('reg_form_req',array(),'array',true,false);

  // Go through list and update selected fields:
  foreach($ids AS $id)
  {
    if($id > 0)
    {
      $DB->query("UPDATE {users_fields} SET groupname_id = %d, fieldorder = %d, fieldlabel = '%s',
        fieldshow = %d, readonly = %d, public_status = %d, public_req = %d,
        req = %d, reg_form = %d, reg_form_req = %d
        WHERE fieldnum = %d",
        (empty($g[$id])   ? 0 : (int)$g[$id]),
        (empty($f[$id])   ? 0 : (int)$f[$id]),
        (empty($l[$id])   ? '' : $l[$id]),
        (empty($v[$id])   ? 0 : 1),
        (empty($ro[$id])  ? 0 : 1),
        (empty($pub[$id]) ? 0 : 1),
        (empty($pubr[$id])? 0 : 1),
        (empty($req[$id]) ? 0 : 1),
        (empty($reg[$id]) ? 0 : 1),
        (empty($regq[$id])? 0 : 1),
        $id
        );
      // Update special phrases too
      if(isset($n[$id]))
      {
        $phrase_id = 'select_profile_page_'.strtolower(preg_replace('/[^0-9a-zA-Z_]/', '_', $n[$id]));
        if(strlen($phrase_id) > 21)
        $DB->query("UPDATE {phrases} SET defaultphrase = '%s' WHERE pluginid = 11 AND varname = '%s'",
                   (isset($l[$id])?(string)$l[$id]:''), $phrase_id);
      }
    }
  }
  if(isset($SDCache))
  {
    $SDCache->delete_cacheid('UCP_PROFILE_GROUPS');
    $SDCache->delete_cacheid('UCP_MEMBER_GROUPS');
    $SDCache->delete_cacheid('planguage_11');
  }
  RedirectPage(SD_PROFILE_REDIR);

} //ProfileSaveConfig

function ProfileSettings()
{
  PrintPluginSettings(11);
}

// ############################################################################
// SELECT FUNCTION
// ############################################################################

$action = GetVar('action', 'profile_config', 'string');
$function_name = str_replace('_', '', $action);
if(is_callable($function_name))
{
  call_user_func($function_name);
}
else
{
  DisplayMessage("Invalid function call: $function_name()", true);
}
