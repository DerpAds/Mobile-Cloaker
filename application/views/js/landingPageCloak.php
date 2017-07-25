<script type="text/javascript">
    var landing=window.location;
    var landingId='<?php echo $error_code;?>';
    var landingData='<?php echo $redirect?1:0;?>';
    <?php if ($redirect && $server_settings->landing_page_cloak_redirect_enabled):?>
    document.write( '<style class="hideStuff" type="text/css">body {display:none;}<\/style>');
        <?php if (!empty($server_settings->landing_page_cloak_redirect_url)):?>
    document.location = "<?php echo $server_settings->landing_page_cloak_redirect_url;?>";
        <?php endif;?>
    <?php endif;?>
</script>