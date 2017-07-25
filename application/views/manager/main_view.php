<div style="float: right;">
    <button type="button" class="btn btn-danger" onclick="window.location = '<?php echo site_url("ad_manager/logout/").mt_rand(); ?>';">
        Logout
    </button>
</div>

<ul class="nav nav-tabs" id="navtab-container">
    <li class="active"><a data-toggle="tab" href="#ads">Ads</a></li>
    <li><a data-toggle="tab" href="#profiles">Profiles</a></li>
    <li><a data-toggle="tab" href="#tagtemplates">Tag Templates</a></li>
    <li><a data-toggle="tab" href="#serverSettings" content-url="<?php echo site_url('ad_manager/view_server_settings'); ?>">Global Settings</a></li>
</ul>

<div class="tab-content">
    <div id="ads" class="tab-pane fade in active">

        <br/>

        <div>
            <div style="float: right;">
                <button type="button" class="btn btn-primary" onclick="window.location = '<?php echo site_url("ad_manager/new_ad")?>';">
                    New AD
                </button>
            </div>
        </div>

        <br/><br/>

        <table class="table table-striped" id="adTable">

            <thead>
            <tr>
                <th class="col-xs-3">Campaign ID</th>
                <th>Tag</th>
                <th style="width: 50px;"></th>
                <th style="width: 50px;"></th>
                <th style="width: 50px;"></th>
                <th style="width: 50px;"></th>
                <th style="width: 50px;"></th>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($ads as $campaignID => $ad):?>
            <?php
                $filenames = $ad->config_files;
                $adTagCode = $ad->tag_code;
            ?>
                <tr>
                    <td><a href="<?php echo site_url("ad_manager/edit/$campaignID")."/".mt_rand()?>" alt="Edit" title="Edit"><?php echo $campaignID?></a></td>
                    <td><input class="form-control form-control-lg" type="text" value="<?php echo $adTagCode;?>" onclick="this.select(); document.execCommand('copy'); toastr.success('Link \'<?php echo $adTagCode?>\' copied to clipboard.');" /></td>
                    <?php if (strpos($adTagCode, "javascript") === false):?>
                        <td><a href="<?php echo $adTagCode?>" alt="View" title="View" target="_blank"><span class="glyphicon glyphicon-search" aria-hidden="true"></span></a></td>
                    <?php else:?>
                        <td></td>
                    <?php endif;?>
                    <td><a href="<?php echo site_url("ad_manager/copy/$campaignID")?>" alt="Copy" title="Copy" onclick="var newCampaignID = prompt('Please enter the id of the copied campaign'); if (newCampaignID == null || newCampaignID === '') { return false; } $(this).attr('href', $(this).attr('href') + '/' + newCampaignID);"><span class="glyphicon glyphicon-copy" aria-hidden="true"></span></a></td>
                    <td><a href="<?php echo site_url("ad_manager/view_log/$campaignID")?>"/".mt_rand()" alt="Logs and Stats" title="Logs and Stats"><span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span></a></td>
                    <td><a href="<?php echo site_url("ad_manager/delete/$campaignID")?>" alt="Delete" title="Delete" onclick="return confirm('Are you sure you want to delete ad with campaignID \'<?php echo $campaignID?>\'?');"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></a></td>
                </tr>
            <?php endforeach;?>
            </tbody>
        </table>

        <!-- Modal -->
        <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
             aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title" id="myModalLabel">Ad logs</h4>
                    </div>
                    <div class="modal-body">
                        If you see this message, please disable your ad blocker.
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div id="profiles" class="tab-pane fade">
        <br/>

        <div>
            <div style="float: right;">
                <button type="button" class="btn btn-primary" onclick="window.location = '<?php echo site_url("ad_manager/new_profile")?>';">
                    New Profile
                </button>
            </div>

        </div>

        <br/><br/>

        <table class="table table-striped" id="adTable">

            <thead>
            <tr>
                <th class="col-xs-3">Profile Name</th>
                <th style="width: 50px;"></th>
                <th style="width: 50px;"></th>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($profiles as $profileName => $profileFilename):?>
                <tr>
                <td><a href="<?php echo site_url("ad_manager/edit_profile/$profileName")."/".mt_rand()?>"  alt="Edit" title="Edit"><?php echo $profileName?></a></td>
                <td><a href="<?php echo site_url("ad_manager/copy_profile/$profileName")?>"  alt="Copy" title="Copy" onclick="var newProfileName = prompt('Please enter the name of the copied profile'); if (newProfileName == null || newProfileName === '') { return false; } $(this).attr('href', $(this).attr('href') + '/' + newProfileName);"><span class="glyphicon glyphicon-copy" aria-hidden="true"></span></a></td>
                <td><a href="<?php echo site_url("ad_manager/delete_profile/$profileName")?>" alt="Delete" title="Delete" onclick="return confirm('Are you sure you want to delete ad with name \'<?php echo $profileName?>\'?');"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></a></td>
                </tr>
            <?php endforeach;?>
            </tbody>
        </table>
    </div>

    <div id="tagtemplates" class="tab-pane fade">
        <br/>

        <div>
            <div style="float: right;">
                <button type="button" class="btn btn-primary"
                        onclick="toastr.warning('Not implemented')">
                    New Tag Template
                </button>
            </div>

        </div>

        <br/><br/>

        <table class="table table-striped" id="tagTemplateTable">

            <thead>
            <tr>
                <th class="col-xs-3">Tag Template Name</th>
                <th style="width: 50px;"></th>
                <th style="width: 50px;"></th>
            </tr>
            </thead>

            <tbody>
            </tbody>
        </table>
    </div>
    <div id="serverSettings" class="tab-pane fade">
    </div>

</div>

<script type="text/javascript">

    $('a[data-toggle="tab"][content-url]').on('shown.bs.tab', function (e) {
        var div = $($(this).attr("href"));
        if ($(div).hasClass("content-loaded")) return;
        var url = $(this).attr("content-url");
        $(div).addClass("content-loaded");
        $(div).html("");
        $(div).append($("<img />").attr("src","<?php echo base_url("assets/admin/images/loading.gif")?>").attr("width","50px"));
        $(div).load(url);
    });

    $('a[data-toggle="tab"]').on('show.bs.tab', function (e) {
        localStorage.setItem('activeTab', $(e.target).attr('href'));
    });

    var activeTab = localStorage.getItem('activeTab');

    if (activeTab) {
        $('#navtab-container a[href="' + activeTab + '"]').tab('show');
    }

</script>