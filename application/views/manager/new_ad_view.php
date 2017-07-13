<form action="<?php echo site_url("ad_manager/insert_ad")?>" method="post" onsubmit="if ($('#campaignID').val() === '') { $('#campaignID').focus(); toastr.error('Campaign ID cannot be empty.'); return false; } return true;">

		<table class="table table-striped" id="configTable">

			<tr>
				<td class="col-xs-5">Campaign ID</td>
				<td><input type="text" name="campaignID" id="campaignID" class="form-control form-control-lg" value=""/></td>
</tr>

<tr>
    <td>Traffic Source</td>
    <td>
        <select name="profile" class="form-control">
            <?php
            foreach ($profiles as $profile_name => $profile_filename)
            {
                echo "<option value=\"$profile_filename\">$profile_name</option>";
            }
            ?>
        </select>
    </td>
</tr>

</table>

<button type="submit" class="btn btn-primary">
    Save
</button>
    <button type="button" class="btn btn-primary" onclick="window.location = '<?php echo site_url("ad_manager/main")."/".mt_rand();?>';">
        Cancel
    </button><br/><br/>
</form><br/><br/>