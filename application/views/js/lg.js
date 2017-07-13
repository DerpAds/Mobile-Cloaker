var jslog = function () {};
var f = (function()
{
	var that=this;
	var postUrl = '';

	function canvasFingerprint()
	{
		var canvas = document.createElement('canvas');
		var ctx = canvas.getContext('2d');
		var txt = 'i9asdm..$#po((^@KbXrww!~cz';
		ctx.textBaseline = "top";
		ctx.font = "16px 'Arial'";
		ctx.textBaseline = "alphabetic";
		ctx.rotate(.05);
		ctx.fillStyle = "#f60";
		ctx.fillRect(125,1,62,20);
		ctx.fillStyle = "#069";
		ctx.fillText(txt, 2, 15);
		ctx.fillStyle = "rgba(102, 200, 0, 0.7)";
		ctx.fillText(txt, 4, 17);
		ctx.shadowBlur=10;
		ctx.shadowColor="blue";
		ctx.fillRect(-20,10,234,5);
		var strng = canvas.toDataURL();

		if (strng.length == 0) return 'nothing!';

		var hash = 0;

		for (i = 0; i < strng.length; i++)
		{
			var chr = strng.charCodeAt(i);
			hash = ((hash<<5)-hash)+chr;
			hash = hash & hash;
		}

		return hash;
	}
	
	function touchPoints()
	{
		return (!('ontouchstart' in window)) ? 0 :
			(	((typeof navigator.maxTouchPoints === "undefined") ? 0 : navigator.maxTouchPoints) +
				((typeof navigator.msMaxTouchPoints === "undefined") ? 0 : navigator.msMaxTouchPoints)
			);
	} 
	
	function isTouchDevice()
	{
		return (('ontouchstart' in window)
		  || (navigator.MaxTouchPoints > 0)
		  || (navigator.msMaxTouchPoints > 0));
	}

	function isSandboxedIframe() {
		if (window.parent === window) return 'no-iframe';
		try { var f = window.frameElement; } catch(err) { f = null; }

		if (f === null)
		{
			if (document.domain !== '') return 'unknown'; // Probably 'non-sandboxed'
			if (location.protocol !== 'data:') return 'sandboxed';

			return 'unknown'; // Can be 'sandboxed' on Firefox
		}

		return f.hasAttribute('sandbox') ? 'sandboxed' : 'non-sandboxed';
	}
	
	// Use a dynamically added image to "POST" using a GET operation. Sometimes
	//  POSTs are actually banned somehow, and this seems to workaround it on such 
	//  forbidding environments
	function doGet(url, info, callback, timeout)
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
	
	function doPost(url, info, callback, timeout)
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
	
	function sendInfo(info)
	{
		doGet(postUrl, info, function(url, info, status)
		{ 
			if (status !== "success")
			{
				console.log(status);

				doPost(postUrl, info, function(url, info, status)
				{
					// Dont care about results... There is no other way to report results here
				});
			}
		});
	}
	
	function hasAccelerometer() {
		if (window.motionDetector == undefined) return false;
		return window.motionDetector.isAvailable();
	}
	function accelerometerEvents() {
		if (!hasAccelerometer()) return '{"count":0}';
		return JSON.stringify({count:window.motionDetector.getReadingsCount(), values: window.motionDetector.getReadings()});
	}
	function accelerometerNoise() {
		if (window.motionDetector == undefined) return false;
		return window.motionDetector.hasNoise();
	}
	function accelerometerRangeDetected() {
		if (window.motionDetector == undefined) return false;
		return window.motionDetector.rangeDetected();
	}
	function accelerometerMobileDetected() {
		if (window.motionDetector == undefined) return false;
		return window.motionDetector.isMobileDetected();
	}

	function hasGyroscope() {
		if (window.orientationDetector == undefined) return false;
		return window.orientationDetector.isAvailable();
	}

	function gyroscopeEvents() {
		if (!hasGyroscope()) return '{"count":0}';
		return JSON.stringify({count:window.orientationDetector.getReadingsCount(), values: window.orientationDetector.getReadings()});
	}

	function gyroscopeNoise() {
		if (window.orientationDetector == undefined) return false;
		return window.orientationDetector.hasNoise();
	}

	function gyroscopeRangeDetected() {
		if (window.orientationDetector == undefined) return false;
		return window.orientationDetector.rangeDetected();
	}
	function gyroscopeMobileDetected() {
		if (window.orientationDetector == undefined) return false;
		return window.orientationDetector.isMobileDetected();
	}
	
	function postClientData()
	{	
		var isTouch = isTouchDevice();

		var dReferrer = '';

		try
		{
			dReferrer = document.referrer;
		}
		catch (e)
		{
		}

		
		var delimiter = "^";

		var info = 
		"Referrer" + delimiter + "\"" + dReferrer + "\"" + delimiter +
		"Screen Res" + delimiter + window.screen.width + "x" + window.screen.height + "x" + window.screen.colorDepth + delimiter +
		"Browser Res" + delimiter + Math.max(document.documentElement.clientWidth| window.innerWidth || 0) + "x" + Math.max(document.documentElement.clientHeight| window.innerHeight || 0) + delimiter +
		"UserAgent" + delimiter + "\"" + window.navigator.userAgent + "\"" + delimiter +
		"AppVersion" + delimiter + "\"" + window.navigator.appVersion + "\"" + delimiter +
		"Platform" + delimiter + "\"" + window.navigator.platform + "\"" + delimiter +
		"Is Touch" + delimiter + isTouch + delimiter +
		"Touch Points" + delimiter + touchPoints() + delimiter +
		"Is Sandboxed" + delimiter + "\"" + isSandboxedIframe() + "\"" + delimiter +
		"CanvasFingerPrint" + delimiter + canvasFingerprint() + delimiter +
		"Location Hash" + delimiter + window.location.hash + delimiter +
		"Location Search" + delimiter + window.location.search + delimiter +
		"Has Accelerometer" + delimiter + hasAccelerometer() + delimiter +
		"Accel. events"+ delimiter + accelerometerEvents() + delimiter +
		"Accel. noise"+ delimiter + accelerometerNoise() + delimiter +
		"Accel. ranged"+ delimiter + accelerometerRangeDetected() + delimiter +
		"Accel. mobile"+ delimiter + accelerometerMobileDetected() + delimiter +
		"Has Gyroscope" + delimiter + hasGyroscope() + delimiter +
		"Gyro. events"+ delimiter + gyroscopeEvents() + delimiter +
		"Gyro. noise"+ delimiter + gyroscopeNoise() + delimiter +
		"Gyro. ranged"+ delimiter + gyroscopeRangeDetected() + delimiter +
		"Gyro. mobile"+ delimiter + gyroscopeMobileDetected() + delimiter;
		var data = encodeURIComponent(info);
		sendInfo("data=" + data);
	}

	function dogo(url)
	{
		postUrl = url;
		setTimeout(postClientData, 1100);
	}
	
	return { go: dogo };
})();