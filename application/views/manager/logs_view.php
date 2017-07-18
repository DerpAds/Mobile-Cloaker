<div id="log-container">
    <div lazy-load="<?php echo site_url("ad_manager/view_log/$id/stats")?>" lazy-load-name="stats" delete-url="<?php echo site_url("ad_manager/delete_logs/$id")?>">
    </div>
    <br/><br/>
    <?php if (isset($logs) && count($logs) > 0):?>
        <?php foreach ($logs as $log_filename):?>
            <div lazy-load="<?php echo site_url("ad_manager/view_log/$id")."/".urlencode("$log_filename")?>" lazy-load-name="<?php echo $log_filename?>" delete-url="<?php echo site_url("ad_manager/delete_log/$id")."/".urlencode("$log_filename")?>">
            </div>
            <br/><br/>
        <?php endforeach;?>
    <?php else:?>
        No log files found.<br/>
    <?php endif;?>
    <div>
        <button type="button" class="btn btn-primary" onclick="window.location = '<?php echo site_url("ad_manager/main")."/".mt_rand();?>';">
            Back
        </button>
    </div>
</div>
<br/><br/>
<script>
    function refresh_link(link) {
        refresh_div($(link).closest('div[lazy-load]'));
    }
    function refresh_div(div) {
        var src = $(div).attr("lazy-load");
        var name = $(div).attr("lazy-load-name");
        $(div).html("<h4>Loading <b>" + name + "</b><br/></h4>");
        $(div).append($('<img width="50px" src="<?php echo base_url("assets/admin/images/loading.gif");?>"/>'));
        $.get(src,function(response) {
            $(div).html(response);
        }).fail(function () {
            $(div).html('<h4><a href="#" onclick="refresh_link(this);">Error loading <b>' + name + "</b>, click this link to try again</a></h4>");
        });
    }

    function delete_link(link) {
        var div = $(link).closest('div[lazy-load]');
        var name = $(div).attr("lazy-load-name");
        if (!confirm("Are you sure you want to delete the " + name + " file from the <?php echo $id?> campaign?")) return;
        var url = $(div).attr("delete-url");
        $(div).block();
        $.getJSON( url, function(response) {
            $(div).unblock();
            refresh_div(div);
            if (response.result!="ok"){
                alert("Error processing response");
            }
        }).fail(function() {
            $(div).unblock();
            alert("Error processing request");
        });
    }

    function delete_all_link(link) {
        if (!confirm("Are you sure you want to delete all the log files on the <?php echo $id?> campaign?")) return;
        var div = $(link).closest('div[lazy-load]');
        var url = $(div).attr("delete-url");
        $("#log-container").block();
        $.getJSON( url, function(response) {
            $("#log-container").unblock();
            refresh_all();
            if (response.result!="ok"){
                alert("Error processing response");
            }
        }).fail(function() {
            $("#log-container").unblock();
            alert("Error processing request");
        });
    }

    function refresh_all() {
        $("div[lazy-load]").each(function () {
            refresh_div(this);
        });
    }

    $(document).ready(function () {
        refresh_all();
    });
</script>