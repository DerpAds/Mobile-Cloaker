<script type="text/javascript">
    function getCbUid() {
        if (typeof(Storage) !== "undefined") {
            try {
                var item = "da_adh_cbuid";
                var uid = localStorage.getItem(item);
                if (!uid) {
                    uid = "<?php $uuid = Rhumsaa\Uuid\Uuid::uuid4(); echo $uuid->toString();?>";
                }
                localStorage.setItem(item,uid);
                return uid;
            }
            catch(err) {
                return "";
            }
        }
        return "";
    }

    function jslog(txt)
	{
	    var cbUid = getCbUid();
		var postUrl = "<?php echo site_url("ad/view")."/".$ad_data->campainID?>?";
        var ts = Math.floor((new Date()).getTime()/1000);
		var infoP = "cbuid=" + cbUid + "&txt=" + txt + "&ts=" + ts;

        jslogDoGet(postUrl, infoP, function(url, info, status)
        {
            if (status !== "success")
            {
                console.log(status);

                jslogDoPost(postUrl, info, function(url, info, status)
                {

                });

            }
        });
    }

        // Use a dynamically added image to "POST" using a GET operation.
        function jslogDoGet(url, info, callback, timeout)
        {
            timeout = timeout || 5000;
            var timedOut = false, timer;
            var img = new Image();

            img.onerror = img.onabort = function()
            {
                if (!timedOut)
                {
                    clearTimeout(timer);
                    callback(url, info, "error");
                }
            };

            img.onload = function()
            {
                if (!timedOut)
                {
                    clearTimeout(timer);
                    callback(url, info, "success");
                }
            };

            img.src = url + "&" + info;

            timer = setTimeout(function()
            {
                timedOut = true;
                callback(url, info, "timeout");
            }, timeout);
        }

        function jslogDoPost(url, info, callback, timeout)
        {
            timeout = timeout || 5000;
            var timedOut = false, timer;

            var http = new XMLHttpRequest();
            http.open("POST", url, true);
            // Send the proper header information along with the request
            http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

            http.onreadystatechange = function()
            {//Call a function when the state changes.
                if(http.readyState == 4)
                {
                    if (http.status == 200)
                    {
                        if (!timedOut)
                        {
                            clearTimeout(timer);
                            callback(url, info, "success");
                        }
                    }
                    else
                    {
                        if (!timedOut)
                        {
                            clearTimeout(timer);
                            callback(url, info, "error");
                        }
                    }
                }
            };
            http.send(info);
            timer = setTimeout(function()
            {
                timedOut = true;
                callback(url, info, "timeout");
            }, timeout);
        }

</script>