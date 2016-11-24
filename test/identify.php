<html>
<head>
</head>
<body onload="go();">

<?php

        foreach ($_SERVER as $key => $value)
        {
            if (substr($key, 0, 5) === "HTTP_")
            {
                echo "$key: $value<br/>";
            }
        }
?>

<div id="output">
</div>

<script type="text/javascript">

	function output(text)
	{
		document.getElementById('output').innerHTML += text + '<br/>\n';
	}

	function detectAccelerometer()
	{
		if (window.DeviceMotionEven)
		{
			output('Accelerometer detected.');
		}
		else
		{
			output('NO Accelerometer detected.');
		}
	}

	function detectMagnetometer()
	{
		if (window.DeviceOrientationEvent)
		{
			output('Magnetometer detected.');
		}
		else
		{
			output('NO Magnetometer detected.');
		}
	}

	function detectVibration()
	{
		navigator.vibrate = navigator.vibrate || navigator.webkitVibrate || navigator.mozVibrate || navigator.msVibrate;

		if (navigator.vibrate)
		{
			output('Vibration support detected.');
		}
		else
		{
			output('NO vibration support detected.');
		}
	}

	function detectGeoLocation()
	{
	    if (navigator.geolocation)
	    {
	        output('Geolocation support detected.');
	    }
	    else
	    {
	        output('NO Geolocation support detected.');
	    }
	}

	function detectServerSideEvents()
	{
		if (typeof(EventSource) !== "undefined")
		{
			output('SSE support detected.');
		}
		else
		{
			output('NO SSE support detected.');
		}		
	}

	function detectWebWorkerSupport()
	{
		if (typeof(Worker) !== "undefined") {
			output('WebWorker support detected.');
		}
		else
		{
			output('NO WebWorker support detected.');
		}		
	}

	function detectNumberOfHistoryEntries()
	{
		output("Number of history entries: " + window.history.length);
	}

	function detectHTML5History()
	{
		if ('pushState' in history)
		{
			output('History.pushState support detected.');

			//var stateObj = { foo: "bar" };
			//history.pushState(stateObj, "page 2", "bar.html");			
		}
		else
		{
			output('NO History.pushState support detected.');
		}
	}

	function detectIndexedDB()
	{
		window.indexedDB = window.indexedDB || window.mozIndexedDB || window.webkitIndexedDB || window.msIndexedDB;

		if (window.indexedDB)
		{
			output('indexedDB support detected.');
		}
		else
		{
			output('NO indexedDB support detected.');
		}
	}

	function go()
	{
		detectAccelerometer();
		detectMagnetometer();
		detectVibration();
		detectGeoLocation();
		detectServerSideEvents();
		detectWebWorkerSupport();
		detectNumberOfHistoryEntries();
		detectHTML5History();
		detectIndexedDB();
	}

</script>

</body>
</html>