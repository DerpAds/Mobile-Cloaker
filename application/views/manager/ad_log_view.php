    <div style="vertical-align: bottom">
        <div style="display:inline; float:left"> <h3><b><?php echo $log_name;?></b></h3></div>
        <div style="display:inline; float:right">
            <button type="button" class="btn btn-primary" onclick="refresh_link(this);">Reload log</button>
            <button type="button" class="btn btn-secondary" onclick="download_link(this);">Download copy</button>
            <button type="button" class="btn btn-danger" onclick="delete_link(this);">Delete log file</button>
        </div>
    </div>
    <?php if (count($log_rows)>1):?>
        <div style="overflow: scroll; width: 100%; height: 33%;">
        <table class="table table-bordered table-striped table-no-wrap">
                <tr>
                    <th>Date</th>
                    <th>Client ID</th>
                    <th>User Agent</th>
                    <th>IP</th>
                    <th>Port</th>
                    <th>ISP</th>
                    <th>Headers</th>
                    <th>Message</th>
                </tr>
            <?php foreach ($log_rows as $index=>$row):?>
                <tr>
                    <td><?php echo $row->date_registered->format('Y-m-d H:i:s');?></td>
                    <td><?php echo $row->client_guid?></td>
                    <td><?php echo $row->user_agent?></td>
                    <td><?php echo $row->remote_ip?></td>
                    <td><?php echo $row->remote_port?></td>
                    <td><?php echo $row->isp?></td>
                    <td><?php echo $row->headers?></td>
                    <td><?php echo $row->message?></td>
                </tr>
            <?php endforeach;?>
        </table>
        </div>
    <?php else:?>
        <div style="overflow: hidden; width: 100%; height: 33%; background: #eeeeee">
        <h5>No log records found</h5>
        </div>
    <?php endif;?>

