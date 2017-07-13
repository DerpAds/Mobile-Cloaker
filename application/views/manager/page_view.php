<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
        <script src="<?php echo base_url("js/blockui.js");?>"></script>
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
        <link rel="stylesheet" href="<?php echo base_url("css/manager.css");?>">
        <title>Ad Manager - Adcrush Media</title>
    </head>

    <body>
        <div style="width: 95%; height: 100%; margin: 10 auto;">
            <?php if (isset($content_view)):?>
                <?php echo $this->load->view($content_view,isset($content_data)?$content_data:array(),true)?>
            <?php endif;?>
            <?php if (!isset($hide_footer) || $hide_footer == false):?>
            <div>
                <small><?php echo SITE_COPYRIGHT?></small>
            </div>
            <?php endif;?>
        </div>
        <script type="text/javascript">
            $(document).ready(function() {
                toastr.options.timeOut = 10;
                toastr.options.closeDuration = 500;
                <?php if (isset($toast_success_message) && !empty($toast_success_message)):?>
                toastr.success('<?php echo $toast_success_message?>');
                <?php endif;?>
            });
        </script>
    </body>
</html>