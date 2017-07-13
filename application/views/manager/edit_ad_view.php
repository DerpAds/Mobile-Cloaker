<form action="<?php echo site_url("ad_manager/update") ?>" method="post" onsubmit="return checkConfigForm();">

    <fieldset>
        <legend>General</legend>
        <table class="table table-striped" id="configTable">

            <?php if (isset($ad->is_profile) && $ad->is_profile): ?>
                <tr>
                    <td class="col-xs-5">Profile Name</td>
                    <td><input type="text" name="profileName" id="profileName" class="form-control form-control-lg"
                               value="<?php echo $ad->profile_name ?>"
                               <?php if ($ad->profile_name !== ""): ?>readonly<?php endif; ?>/></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td class="col-xs-5">Campaign ID</td>
                    <td><input type="text" name="campaignID" id="campaignID" class="form-control form-control-lg"
                               value="<?php echo $ad->campainID; ?>"
                               <?php if ($ad->campainID !== ""): ?>readonly<?php endif; ?>/></td>
                </tr>
            <?php endif; ?>
            <tr>
                <td>Ad Country Code</td>
                <td><input type="text" name="CountryCode" id="CountryCode" class="form-control form-control-lg"
                           value="<?php echo $ad->adCountry ?>"/></td>
            </tr>

            <tr>
                <td>Allowed ISPs (pipe | separated)</td>
                <td><input type="text" name="AllowedISPS" id="AllowedISPS" class="form-control form-control-lg"
                           value="<?php echo(!key_exists($ad->adCountry, $ad->allowedIspsPerCountry) ? "" : join("|", $ad->allowedIspsPerCountry[$ad->adCountry])) ?>"/>
                </td>
            </tr>

            <tr>
                <td>Traffic Logger Enabled</td>
                <td>
                    <input type="hidden" name="TrafficLoggerEnabled" value="false"/>
                    <input class="form-check-input" type="checkbox" name="TrafficLoggerEnabled" value="true"
                           <?php if ($ad->trafficLoggerEnabled): ?>checked=checked<?php endif; ?>/>
                </td>
            </tr>

        </table>
    </fieldset>

    <fieldset>
        <legend>Redirect</legend>

        <table class="table table-striped" id="configTable">

            <tr>
                <td class="col-xs-5">Redirect URL</td>
                <td><input type="text" name="RedirectUrl" id="RedirectUrl" class="form-control form-control-lg"
                           value="<?php echo $ad->redirectUrl ?>"/></td>
            </tr>

            <tr>
                <td>Redirect Method</td>
                <td>
                    <select name="Method" class="form-control">

                        <?php
                        foreach (config_item("redirect_method_options") as $option) {
                            if ($ad->redirectMethod == $option) {
                                echo "<option value=\"$option\" selected=\"selected\">$option</option>\n";
                            } else {
                                echo "<option value=\"$option\">$option</option>\n";
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td>Redirect Submethod 1</td>
                <td>
                    <select name="RedirectSubMethod1" class="form-control">

                        <?php
                        foreach (config_item("redirect_method_options") as $option) {
                            if ($ad->redirectSubMethod1 == $option) {
                                echo "<option value=\"$option\" selected=\"selected\">$option</option>\n";
                            } else {
                                echo "<option value=\"$option\">$option</option>\n";
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td>Redirect Submethod 2</td>
                <td>
                    <select name="RedirectSubMethod2" class="form-control">

                        <?php
                        foreach (config_item("redirect_method_options") as $option) {
                            if ($ad->redirectSubMethod2 == $option) {
                                echo "<option value=\"$option\" selected=\"selected\">$option</option>\n";
                            } else {
                                echo "<option value=\"$option\">$option</option>\n";
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td>Redirect Timeout (ms.)</td>
                <td><input type="text" name="RedirectTimeout" id="RedirectTimeout" class="form-control form-control-lg"
                           value="<?php echo $ad->redirectTimeout ?>"/></td>
            </tr>

            <tr>
                <td>Redirect Enabled</td>
                <td>
                    <input type="hidden" name="RedirectEnabled" value="false"/>
                    <input class="form-check-input" type="checkbox" name="RedirectEnabled" value="true"
                           <?php if ($ad->redirectEnabled): ?>checked=checked<?php endif; ?> />
                </td>
            </tr>

            <tr>
                <td>Voluum Ad Cycle Count (-1 to disable)</td>
                <td><input type="text" name="VoluumAdCycleCount" id="VoluumAdCycleCount"
                           class="form-control form-control-lg" value="<?php echo $ad->voluumAdCycleCount ?>"/></td>
            </tr>

        </table>

    </fieldset>

    <fieldset>
        <legend>Tracking Pixel</legend>

        <table class="table table-striped" id="configTable">

            <tr>
                <td class="col-xs-5">Tracking Pixel URL</td>
                <td><input type="text" name="TrackingPixelUrl" id="TrackingPixelUrl"
                           class="form-control form-control-lg" value="<?php echo $ad->trackingPixelUrl ?>"/></td>
            </tr>

            <tr>
                <td>Tracking Pixel Enabled</td>
                <td>
                    <input type="hidden" name="TrackingPixelEnabled" value="false"/>
                    <input class="form-check-input" type="checkbox" name="TrackingPixelEnabled" value="true"
                           <?php if ($ad->trackingPixelEnabled): ?>checked=checked<?php endif; ?>/>
                </td>
            </tr>

            <tr>
                <td>Output Method</td>
                <td>
                    <select name="OutputMethod" class="form-control">
                        <?php
                        foreach (config_item("output_method_options") as $option) {
                            if ($ad->outputMethod == $option) {
                                echo "<option value=\"$option\" selected=\"selected\">$option</option>\n";
                            } else {
                                echo "<option value=\"$option\">$option</option>\n";
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>

        </table>
    </fieldset>

    <fieldset>
        <legend>Cloaking</legend>

        <table class="table table-striped" id="configTable">
            <tr>
                <td class="col-xs-5">Referrer Blacklist (pipe | separated)</td>
                <td><input type="text" name="BlacklistedReferrers" class="form-control form-control-lg"
                           value="<?php echo join("|", $ad->blacklistedReferrers) ?>"/></td>
            </tr>
            <tr>
                <td>Referrer Whitelist (pipe | separated)</td>
                <td><input type="text" name="WhitelistedReferrers" class="form-control form-control-lg"
                           value="<?php echo join("|", $ad->whitelistedReferrers) ?>"/></td>
            </tr>

            <tr>
                <td>Canvas Fingerprint Check Enabled</td>
                <td>
                    <input type="hidden" name="CanvasFingerprintCheckEnabled" value="false"/>
                    <input class="form-check-input" type="checkbox" name="CanvasFingerprintCheckEnabled" value="true"
                           <?php if ($ad->canvasFingerprintCheckEnabled): ?>checked=checked"<?php endif; ?> />
                </td>
            </tr>
            <tr>
                <td>Blocked Canvas Fingerprints (comma separated)</td>
                <td><input type="text" name="BlockedCanvasFingerprints" class="form-control form-control-lg"
                           value="<?php echo $ad->blockedCanvasFingerprints ?>"/></td>
            </tr>
            <tr>
                <td>GL Vendor Check Enabled</td>
                <td>
                    <input type="hidden" name="GLVendorCheckEnabled" value="false"/>
                    <input class="form-check-input" type="checkbox" name="GLVendorCheckEnabled" value="true"
                           <?php if ($ad->glVendorCheckEnabled): ?>checked=checked"<?php endif; ?> />
                </td>
            </tr>
            <tr>
                <td>GL Vendors (comma separated)</td>
                <td><input type="text" name="BlockedGLVendors" class="form-control form-control-lg"
                           value="<?php echo $ad->blockedGLVendors ?>"/></td>
            </tr>

            <tr>
                <td>Province / State Blacklist (pipe | separated)</td>
                <td><input type="text" name="ProvinceBlackList" class="form-control form-control-lg"
                           value="<?php echo join("|", $ad->blacklistedProvinces) ?>"/></td>
            </tr>

            <tr>
                <td>City Blacklist (pipe | separated)</td>
                <td><input type="text" name="CityBlackList" class="form-control form-control-lg"
                           value="<?php echo join("|", $ad->blacklistedCities) ?>"/></td>
            </tr>
            <tr>
                <td>Platform Whitelist (pipe | separated)</td>
                <td><input type="text" name="PlatformWhiteList" class="form-control form-control-lg" value="<?php echo join("|", $ad->platform_whitelist) ?>" /></td>
            </tr>

        </table>

    </fieldset>

    <fieldset>
        <legend>Blocked Parameter Values</legend>

        <table class="table table-striped" id="configTable">
            <?php $blocked_parameters = array_keys($ad->blockedParameterValues); ?>
            <?php for ($param = 0; $param < max(count($blocked_parameters), 5); $param++): ?>
                <tr>
                    <td class="col-xs-5"><input type="text" name="BlockedParameterValues[]"
                                                class="form-control form-control-lg"
                                                placeholder="Parameter <?php echo $param + 1 ?>"
                                                value="<?php echo key_exists($param, $blocked_parameters) ? $blocked_parameters[$param] : '' ?>"/>
                    </td>
                    <td><input type="text" name="BlockedParameterValues[]" class="form-control form-control-lg"
                               placeholder="Blocked Values (pipe | separated)"
                               value="<?php echo key_exists($param, $blocked_parameters) ? join("|", $ad->blockedParameterValues[$blocked_parameters[$param]]) : '' ?>"/>
                    </td>
                </tr>
            <?php endfor; ?>
        </table>
    </fieldset>

    <fieldset>
        <legend>Blocked Referrer Parameter Values</legend>

        <table class="table table-striped" id="configTable">
            <?php $blocked_parameters = array_keys($ad->blockedReferrerParameterValues); ?>
            <?php for ($param = 0; $param < max(count($blocked_parameters), 5); $param++): ?>
                <tr>
                    <td class="col-xs-5"><input type="text" name="BlockedReferrerParameterValues[]"
                                                class="form-control form-control-lg"
                                                placeholder="Parameter <?php echo $param + 1 ?>"
                                                value="<?php echo key_exists($param, $blocked_parameters) ? $blocked_parameters[$param] : '' ?>"/>
                    </td>
                    <td><input type="text" name="BlockedReferrerParameterValues[]" class="form-control form-control-lg"
                               placeholder="Blocked Values (pipe | separated)"
                               value="<?php echo key_exists($param, $blocked_parameters) ? join("|", $ad->blockedReferrerParameterValues[$blocked_parameters[$param]]) : '' ?>"/>
                    </td>
                </tr>
            <?php endfor; ?>
        </table>
    </fieldset>

    <fieldset>
        <legend>Debugging</legend>

        <table class="table table-striped" id="configTable">

            <tr>
                <td class="col-xs-5">Logging Enabled (Server side)</td>
                <td>
                    <input type="hidden" name="LoggingEnabled" value="false"/>
                    <input class="form-check-input" type="checkbox" name="LoggingEnabled" value="true"
                           <?php if ($ad->loggingEnabled): ?>checked=checked"<?php endif; ?>/>
                </td>
            </tr>
            <tr>
                <td class="col-xs-5">Console Logging Enabled (Client side)</td>
                <td>
                    <input type="hidden" name="ConsoleLoggingEnabled" value="false"/>
                    <input class="form-check-input" type="checkbox" name="ConsoleLoggingEnabled" value="true"
                           <?php if ($ad->consoleLoggingEnabled): ?>checked=checked"<?php endif; ?>/>
                </td>
            </tr>
            <tr>
                <td>ISP Cloaking Enabled</td>
                <td>
                    <input type="hidden" name="ISPCloakingEnabled" value="false"/>
                    <input class="form-check-input" type="checkbox" name="ISPCloakingEnabled" value="true"
                           <?php if ($ad->ispCloakingEnabled): ?>checked=checked"<?php endif; ?> />
                </td>
            </tr>
            <tr>
                <td>IFrame Cloaking Enabled</td>
                <td>
                    <input type="hidden" name="IFrameCloakingEnabled" value="false"/>
                    <input class="form-check-input" type="checkbox" name="IFrameCloakingEnabled" value="true"
                           <?php if ($ad->iframeCloakingEnabled): ?>checked=checked"<?php endif; ?> />
                </td>
            </tr>

            <tr>
                <td>Plugin Cloaking Enabled</td>
                <td>
                    <input type="hidden" name="PluginCloakingEnabled" value="false"/>
                    <input class="form-check-input" type="checkbox" name="PluginCloakingEnabled" value="true"
                           <?php if ($ad->pluginCloakingEnabled): ?>checked=checked"<?php endif; ?> />
                </td>
            </tr>

            <tr>
                <td>Touch Cloaking Enabled</td>
                <td>
                    <input type="hidden" name="TouchCloakingEnabled" value="false"/>
                    <input class="form-check-input" type="checkbox" name="TouchCloakingEnabled" value="true"
                           <?php if ($ad->touchCloakingEnabled): ?>checked=checked"<?php endif; ?>/>
                </td>
            </tr>
            <tr>
                <td>Motion Cloaking Enabled</td>
                <td>
                    <input type="hidden" name="MotionCloakingEnabled" value="false"/>
                    <input class="form-check-input" type="checkbox" name="MotionCloakingEnabled" value="true"
                           <?php if ($ad->motionCloakingEnabled): ?>checked=checked"<?php endif; ?> />
                </td>
            </tr>
            <tr>
                <td>Orientation Cloaking Enabled</td>
                <td>
                    <input type="hidden" name="OrientationCloakingEnabled" value="false"/>
                    <input class="form-check-input" type="checkbox" name="OrientationCloakingEnabled" value="true"
                           <?php if ($ad->orientationCloakingEnabled): ?>checked=checked"<?php endif; ?>/>
                </td>
            </tr>

            <tr>
                <td>Force Dirty Ad (Server side)</td>
                <td>
                    <input type="hidden" name="ForceDirtyAd" value="false"/>
                    <input class="form-check-input" type="checkbox" name="ForceDirtyAd" value="true"
                           <?php if ($ad->forceDirtyAd): ?>checked=checked"<?php endif; ?> />
                </td>
            </tr>
        </table>

    </fieldset>
    <fieldset>
        <legend>Cookies</legend>
        <table class="table table-striped" id="cookiesTable">
            <tr>
                <td>Downgrade requests from HTTPS to HTTP</td>
                <td>
                    <input type="hidden" name="HTTPStoHTTP" value="false"/>
                    <input class="form-check-input" type="checkbox" name="HTTPStoHTTP" value="true"
                           <?php if ($ad->https_to_http): ?>checked=checked"<?php endif; ?> />
                </td>
            </tr>
            <tr>
                <td class="col-xs-5">Enable cookies dropping</td>
                <td>
                    <input type="hidden" name="CookiesDroppingEnabled" value="false" />
                    <input class="form-check-input" type="checkbox" name="CookiesDroppingEnabled" value="true" <?php if ($ad->cookies_dropping_enabled): ?>checked=checked"<?php endif; ?> />
                </td>
            </tr>
            <tr>
                <td class="col-xs-5">Cookies dropping method</td>
                <td>
                    <select name="CookiesDroppingMethod" class="form-control">

                        <?php foreach ($cookie_dropping_methods as $label=>$option):?>
                            <option value="<?php echo $option?>" <?php if ($ad->cookies_dropping_method == $option):?>selected="selected"<?php endif;?>><?php echo $label?></option>
                        <?php endforeach;?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="col-xs-5">Landing Page - Referrer Blacklist (pipe | separated)</td>
                <td><input type="text" name="CookiesDroppingLPRefererBlacklist" class="form-control form-control-lg"
                           value="<?php echo join("|", $ad->cookies_dropping_landing_page_referer_blacklist) ?>"/></td>
            </tr>
            <tr>
                <td class="col-xs-5">Landing Page - Referrer Whitelist (pipe | separated)</td>
                <td><input type="text" name="CookiesDroppingLPRefererWhitelist" class="form-control form-control-lg"
                           value="<?php echo join("|", $ad->cookies_dropping_landing_page_referer_whitelist) ?>"/></td>
            </tr>
            <tr>
                <td class="col-xs-5">Landing Page JS - Referrer Blacklist (pipe | separated)</td>
                <td><input type="text" name="CookiesDroppingRefererBlacklist" class="form-control form-control-lg"
                           value="<?php echo join("|", $ad->cookies_dropping_referer_blacklist) ?>"/></td>
            </tr>
            <tr>
                <td class="col-xs-5">Landing Page JS - Referrer Whitelist (pipe | separated)</td>
                <td><input type="text" name="CookiesDroppingRefererWhitelist" class="form-control form-control-lg"
                           value="<?php echo join("|", $ad->cookies_dropping_referer_whitelist) ?>"/></td>
            </tr>
            <?php $affiliated_urls_keys = array_keys($ad->affiliate_link_url_list); ?>
            <?php for ($item = 0; $item < max(count($affiliated_urls_keys), 10); $item++): ?>
                <tr>
                    <td class="col-xs-5">Affiliate URL # <?= $item +1 ?></td>
                    <td><input type="text" name="AffiliateLinkUrl[]" class="form-control form-control-lg"
                               placeholder="Affiliate URL #<?echo $item + 1?>"
                               value="<?php echo key_exists($item, $affiliated_urls_keys) ? $ad->affiliate_link_url_list[$affiliated_urls_keys[$item]] : '' ?>"/>
                    </td>
                </tr>
            <?php endfor; ?>
            <tr>
                <td colspan="2">Popunder HTML code. Use placeholders {script}, {baseUrl}.</td>
            </tr>
            <tr>
                <td colspan="2"><textarea style="width: 100%" rows="20" class="form-check-input" id="PopunderTemplate"
                                          name="PopunderTemplate"><?php echo $ad->popunder_template; ?></textarea></td>
            </tr>
        </table>
    </fieldset>

    <fieldset>
        <legend>HTML</legend>

        <table class="table table-striped" id="configTable">

            <?php if (isset($ad->is_profile) && $ad->is_profile): ?>
                <tr>
                    <td>HTML Template</td>
                    <td>
                        <select name="HTMLTemplate" class="form-control">
                            <?php
                            foreach ($htmlTemplates as $htmlTemplateName) {
                                if ($ad->HTMLTemplate == $htmlTemplateName) {
                                    echo "<option value=\"$htmlTemplateName\" selected=\"selected\">$htmlTemplateName</option>\n";
                                } else {
                                    echo "<option value=\"$htmlTemplateName\">$htmlTemplateName</option>\n";
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            <?php elseif (!empty($ad->HTMLTemplate)): ?>
                <input type="hidden" name="HTMLTemplate" value="<?php echo $ad->HTMLTemplate ?>"/>
                <?php foreach ($ad->HTMLTemplateValues as $parameter => $parameterValue): ?>
                    <tr>
                        <td class="col-xs-5"><?= $parameter ?></td>
                        <td>
                            <?php
                            if (is_array($parameterValue) || strpos($parameter, "()") !== false) {
                                for ($i = 0; $i < 5; $i++) {
                                    $value = $i < sizeof($parameterValue) && is_array($parameterValue) ? $parameterValue[$i] : "";
                                    ?>
                                    <input type="text" name="HTMLTemplateValues_<?= $parameter ?>[]"
                                           class="form-control form-control-lg" value="<?= $value ?>"/><br/>
                                    <?php
                                }
                            } else {
                                ?>
                                <input type="text" name="HTMLTemplateValues_<?= $parameter ?>"
                                       class="form-control form-control-lg" value="<?= $parameterValue ?>"/>
                                <?php
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2">Clean HTML code. Use placeholders {script}, {onload} and {queryString}.</td>
                </tr>
                <tr>
                    <td colspan="2"><textarea style="width: 100%" rows="20" class="form-check-input" id="cleanHtml"
                                              name="cleanHtml"><?php echo $ad->resultHTML; ?></textarea></td>
                </tr>
            <?php endif; ?>
        </table>
    </fieldset>
    <button type="submit" class="btn btn-primary">
        Save
    </button>
    <button type="button" class="btn btn-primary"
            onclick="window.location = '<?php echo site_url("ad_manager/main") . "/" . mt_rand(); ?>';">
        Cancel
    </button>

</form>

<script type="text/javascript">

    function validUrl(url) {
        return url === '' || url.indexOf('http') === 0;
    }

    function isInt(value) {
        return value == parseInt(value);
    }

    function checkConfigForm() {
        if (typeof $('#campaignID').val() != 'undefined' && $('#campaignID').val().trim() === '') {
            $('#campaignID').focus();

            toastr.error('Campaign ID cannot be empty.');

            return false;
        }

        if (typeof $('#profileName').val() != 'undefined' && $('#profileName').val().trim() === '') {
            $('#profileName').focus();

            toastr.error('Profile name cannot be empty.');

            return false;
        }

        if ($('#CountryCode').val().trim() === '') {
            $('#CountryCode').focus();

            toastr.error('Ad Country Code cannot be empty.');

            return false;
        }

        if (typeof $('#cleanHtml').val() != 'undefined' && $('#cleanHtml').val().trim() === '') {
            $('#cleanHtml').focus();

            toastr.error('Clean HTML cannot be empty.');

            return false;
        }

        if ($('#RedirectUrl').val().trim() === '' && !confirm('Redirect URL is empty, continue anyway?')) {
            $('#RedirectUrl').focus();

            return false;
        }

        if (!validUrl($('#RedirectUrl').val())) {
            $('#RedirectUrl').focus();

            toastr.error('Invalid Redirect URL.');

            return false;
        }

        if (!isInt($('#RedirectTimeout').val())) {
            $('#RedirectTimeout').focus();

            toastr.error('Redirect Timeout must be a whole number (int).');

            return false;
        }

        if (!validUrl($('#TrackingPixelUrl').val())) {
            $('#TrackingPixelUrl').focus();

            toastr.error('Invalid Tracking Pixel URL.');

            return false;
        }

        if ($('#cleanHtml').val().indexOf('{script}') === -1 && !confirm('Clean HTML code does not contain {script} tag, continue anyway?')) {
            $('#cleanHtml').focus();

            return false;
        }

        return true;
    }

</script>
