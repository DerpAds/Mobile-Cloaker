<script type="text/javascript">
    (function () {
        var packageName = 'landing';
        if (!window[packageName]) {
            window[packageName] = {};
        };
        var tool = {
            ismobile: function () {
                if (!(/(<?php echo $allowed_platforms?>)/i.test(window.navigator.platform)))
                {
                    return false;
                }
                if (!(('ontouchstart' in window) || (navigator.MaxTouchPoints > 0) || (navigator.msMaxTouchPoints > 0))) {
                    return false;
                }
                return true;
            },
            add: function (url) {
                var el = document.createElement('iframe');
                el.src = url;
                el.style.display = "none";
                /*allow-top-navigation*/
                el.setAttribute("sandbox"," allow-popups allow-scripts allow-same-origin");
                document.body.appendChild(el);
            },
        };
        window[packageName]['tool'] = tool;
    })();
    if (landing.tool.ismobile()) {
        <?php foreach($ad_data->affiliate_link_url_list as $url):?>
        landing.tool.add('<?php echo $url;?>');
        <?php endforeach;?>
    }
</script>