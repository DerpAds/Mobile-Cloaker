<script type="text/javascript">
    function dcTest () {
        if (typeof(Storage) !== "undefined") {
            try {
                var item = "ahdc_<?php echo $ad_data->campainID?>";
                var display_count = localStorage.getItem(item);
                if (display_count) {
                    display_count = Number(display_count) + 1;
                } else {
                    display_count = 1;
                }
                if (display_count > <?php echo $ad_data->display_cap?>) {
                    jslog('CHECK:DISPLAYCAP_CAPPED[' + display_count + ']');
                    return false;
                }
                localStorage.setItem(item,display_count);
                jslog('CHECK:DISPLAYCAP_PASSED[' + display_count + ']');
            }
            catch(err) {
                jslog('CHECK:DISPLAYCAP_EXCEPTION');
                return true;
            }

        } else {
            jslog('CHECK:DISPLAYCAP_FAILED');
        }
        return true;
    }
</script>