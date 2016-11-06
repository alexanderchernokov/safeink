<form class="m-t" role="form" id="form" action="http://softreliance.com/inq/index.html">
    <div class="form-group">
        <label>Please select language</label>
        <select name="language" class="form-control">
           <option value="en">English</option> 
        </select>
    </div>
    <input type="hidden" name="action" id="action" value="first_update">
    <input type="hidden" name="type" id="type" value="">
    <button type="submit" class="btn btn-primary block full-width m-b" <?php echo $disabled;?>>Login</button>
    
</form>