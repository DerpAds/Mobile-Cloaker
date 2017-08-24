<br/><br/><br/>
<fieldset>
    <legend>Affiliate Url Cookies Dropping</legend>
    <table class="table table-striped" id="cookiesTable">
        <tr>
            <td class="col-xs-5">Start delay (seconds)</td>
            <td class="col-xs-12">
                <div class="input-group">
                    <input type="text" name="cookies_dropping_delay" id="cookies_dropping_delay"
                           class="form-control form-control-lg"
                           value="<?php echo $settings->cookies_dropping_delay; ?>"/>
                </div>
            </td>
        </tr>
        <tr>
            <td class="col-xs-5">Interval (seconds)</td>
            <td class="col-xs-12">
                <div class="input-group">
                    <input type="text" name="cookies_dropping_interval" id="cookies_dropping_interval"
                           class="form-control form-control-lg"
                           value="<?php echo $settings->cookies_dropping_interval; ?>"/>
                </div>
            </td>
        </tr>
    </table>
</fieldset>
<fieldset>
    <legend>Landing Page</legend>
    <table class="table table-striped" id="cookiesTable">
        <tr>
            <td class="col-xs-5">Referrer Blacklist</td>
            <td>
                <div class="input-group">
                <input type="text" name="landing_page_referer_blacklist" class="form-control form-control-lg"
                       value="<?php echo join("|", $settings->landing_page_referer_blacklist) ?>">
                <span class="input-group-addon">
                            <a class='my-tool-tip' data-toggle="tooltip" data-placement="left"
                               title="pipe | separated list - Should be any url different than the Cloaker's">
                            <i class='glyphicon glyphicon-info-sign'></i>
                        </a>
                        </span>
                </input>
                </div>
            </td>
        </tr>
        <tr>
            <td class="col-xs-5">Referrer Whitelist</td>
            <td>
                <div class="input-group">
                    <input type="text" name="landing_page_referer_whitelist" class="form-control form-control-lg"
                           value="<?php echo join("|", $settings->landing_page_referer_whitelist) ?>">
                    <span class="input-group-addon">
                            <a class='my-tool-tip' data-toggle="tooltip" data-placement="left"
                               title="pipe | separated list - Should be the Cloaker's url">
                            <i class='glyphicon glyphicon-info-sign'></i>
                        </a>
                        </span>
                    </input>
                </div>
            </td>
        </tr>
        <tr>
            <td class="col-xs-5">Landing_js's Referrer Blacklist</td>
            <td>
                <div class="input-group">
                    <input type="text" name="landing_js_referer_blacklist" class="form-control form-control-lg"
                           value="<?php echo join("|", $settings->landing_js_referer_blacklist) ?>">
                    <span class="input-group-addon">
                            <a class='my-tool-tip' data-toggle="tooltip" data-placement="left"
                               title="pipe | separated list - Should be any url different than the landing page's">
                            <i class='glyphicon glyphicon-info-sign'></i>
                        </a>
                        </span>
                    </input>
                </div>
            </td>
        </tr>
        <tr>
            <td class="col-xs-5">Landing_js's Referrer Whitelist</td>
            <td>
                <div class="input-group">
                    <input type="text" name="landing_js_referer_whitelist" class="form-control form-control-lg"
                           value="<?php echo join("|", $settings->landing_js_referer_whitelist) ?>">
                    <span class="input-group-addon">
                            <a class='my-tool-tip' data-toggle="tooltip" data-placement="left"
                               title="pipe | separated list - Should be the landing page's url">
                            <i class='glyphicon glyphicon-info-sign'></i>
                        </a>
                        </span>
                    </input>
                </div>
            </td>
        </tr>
        <tr>
            <td class="col-xs-5">Enable Referrer Cloak Redirect</td>
            <td>
                <input class="form-check-input cb" type="checkbox" name="landing_page_cloak_redirect_enabled"
                       <?php if ($settings->landing_page_cloak_redirect_enabled): ?>checked=checked"<?php endif; ?> />
            </td>
        </tr>
        <tr>
            <td class="col-xs-5"> Cloak Referrer Redirect URL</td>
            <td><input type="text" name="landing_page_cloak_redirect_url" class="form-control form-control-lg"
                       value="<?php echo $settings->landing_page_cloak_redirect_url ?>"/></td>
        </tr>
    </table>
</fieldset>
<button type="button" class="btn btn-primary" onclick="server_settings_save();">
    Save
</button>
<br/>
<br/>
<script type="text/javascript">

    $(document).ready(function () {
        $("a.my-tool-tip").tooltip();
        $("#cookies_dropping_delay,#cookies_dropping_interval").TouchSpin({
            min:0,
            max:10,
            booster:true,
            step:0.1,
            decimals:1,
            postfix: 'seconds'
        }).closest("div.input-group");
    });


    function server_settings_save() {
        $("#serverSettings").block();
        $.post("<?php echo site_url("ad_manager/save_server_settings");?>", $("#serverSettings *").serialize(),
            function (response) {
                if (response.result != "ok") {
                    toastr.error(response.message);
                    return;
                }
                toastr.success('Settings saved');
            }, "json").fail(function () {
            toastr.error('Error saving settings');
        }).always(function () {
            $("#serverSettings").unblock();
        });
    }

</script>
