<div style="vertical-align: bottom">
    <div style="display:inline; float:left"> <h3><b>Ad Stats</b></h3></div>
    <div style="display:inline; float:right">
        <button type="button" class="btn btn-primary" onclick="refresh_all();">Reload Stats and logs</button>
        <button type="button" class="btn btn-danger" onclick="delete_all_link(this);">Delete all logs</button>
    </div>
</div>
<?php
/**
 * @var Ad_stats $stats
 */
?>
<?php if (isset($stats)):?>
   <table width="100%" border="1" class="table-no-wrap">
        <tr>
            <th width="50%">Campaign ID</th>
            <th><?php echo $id?></th>
            </tr>

        <tr>
            <td>Referrer Blacklist Blocked</td>
            <td><?php echo $stats->referrer_blacklist_blocked?></td>
            </tr>

        <tr>
            <td>Referrer Blacklist Allowed</td>
            <td><?php echo $stats->referrer_blacklist_allowed?></td>
            </tr>

        <tr>
            <td>Referrer Whitelist Blocked</td>
            <td><?php echo $stats->referrer_whitelist_blocked?></td>
            </tr>

        <tr>
            <td>Referrer Whitelist Allowed</td>
            <td><?php echo $stats->referrer_whitelist_allowed?></td>
            </tr>

        <tr>
            <td>Parameter Blocked</td>
            <td><?php echo $this->load->view("manager/stat_value_view",array("value"=>$stats->parameter_blocked),true)?></td>
            </tr>

        <tr>
            <td>Parameter Allowed</td>
            <td><?php echo $this->load->view("manager/stat_value_view",array("value"=>$stats->parameter_allowed),true)?></td>
            </tr>

        <tr>
            <td>Parameter Missing</td>
            <td><?php echo $this->load->view("manager/stat_value_view",array("value"=>$stats->parameter_missing),true)?></td>
            </tr>

        <tr>
            <td>Referrer Parameter Blocked</td>
            <td><?php echo $this->load->view("manager/stat_value_view",array("value"=>$stats->referrer_parameter_blocked),true)?></td>
            </tr>

        <tr>
            <td>Referrer Parameter Allowed</td>
            <td><?php echo $this->load->view("manager/stat_value_view",array("value"=>$stats->referrer_parameter_allowed),true)?></td>
            </tr>

        <tr>
            <td>Referrer Parameter Missing</td>
            <td><?php echo $this->load->view("manager/stat_value_view",array("value"=>$stats->referrer_parameter_missing),true)?></td>
            </tr>

        <tr>
            <td>User Agent Not Mobile</td>
            <td><?php echo $stats->useragent_mobile?></td>
            </tr>

        <tr>
            <td>Geo Allowed</td>
            <td><?php echo $stats->geo_allowed?></td>
            </tr>

        <tr>
            <td>Geo Blocked</td>
            <td><?php echo $stats->geo_blocked?></td>
            </tr>

        <tr>
            <td>Allowed Traffic</td>
            <td><?php echo $stats->allowed_traffic?></td>
            </tr>

        <tr>
            <td>Total</td>
            <td><?php echo $stats->total?></td>
            </tr>

        </table>
    <br/><br/>
<?php endif;?>
