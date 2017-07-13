    <div style="vertical-align: bottom">
        <div style="display:inline; float:left"> <h3><b><?php echo $log_type;?></b></h3></div>
        <div style="display:inline; float:right">
            <button type="button" class="btn btn-primary" onclick="refresh_link(this);">Reload log</button>
            <button type="button" class="btn btn-danger" onclick="delete_link(this);">Delete log file</button>
        </div>
    </div>
    <?php if (count($rows)>1):?>
        <div style="overflow: scroll; width: 100%; height: 33%;">
        <table class="table table-bordered table-striped table-no-wrap">
                <tr>
                    <?php foreach ($rows[0] as $header):?>
                        <th><?php echo $header?></th>
                    <?php endforeach;?>
                </tr>
            <?php foreach ($rows as $index=>$row):?>
                <?php if ($index>0 && count($row) > 1):?>
                <tr>
                    <?php foreach ($row as $header):?>
                        <td><?php echo $header?></td>
                    <?php endforeach;?>
                </tr>
                <?php endif;?>
            <?php endforeach;?>
        </table>
        </div>
    <?php else:?>
        <div style="overflow: hidden; width: 100%; height: 33%; background: #eeeeee">
        <h5>No log records found</h5>
        </div>
    <?php endif;?>

