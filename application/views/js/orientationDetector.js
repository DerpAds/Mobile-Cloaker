var orientationDetector = new (function() {
	var angleThreshold = 10; /* margin of error for angles to consider in flat position */
	var noiseThreshold = 0.025; /* margin of error for considering to readings different */
	var noiseDetected = false;
	var minimumEvents = 3; /* Minimum events */
	var maximumEvents = 10; /* Minimum events */
	var maxTime = 1000;	 /* Timeout */
	var timer = null;	 /* Timer to end detection */
	var w = window;		 /* Cache window variable, to make debugging harder */
	var readings = 0;
	var readingsInRange = [];
	var readingsList = [];
	var firstReading = null;
	var available = false;

	this.getTimeout = function () {
		return maxTime;
	};
	
	this.isAvailable = function (r) {
		return available;
	};

	this.hasNoise = function (r) {
		return noiseDetected;
	};

	this.rangeDetected = function (r) {
		return readings>0 && readingsInRange.every(function (i,n,l) {return i;});
	};

	this.getReadings = function (r) {
		return readingsList;
	};

	this.getReadingsCount = function (r) {
		return readings;
	};

	this.readingInRange = function (r) {
		/* we ignore compass angle, alpha value*/
		return ((Math.abs(r.b)< angleThreshold) && (Math.abs(r.g) < angleThreshold));
	};

	this.isMobileDetected = function ()
	{
		if (readings<minimumEvents
			|| !noiseDetected
			|| readingsInRange.every(function (i,n,l) {return i;})
			) {
			jslog("CHECK:ORIENTATIONTEST_FAILED");
			return false;
		}
		/* Ok is a mobile device not on a flat surface*/
		jslog("CHECK:ORIENTATIONTEST_PASSED");
		return true;
	};
	
	this.captureEvent = function (e)
	{
		if (readings >= maximumEvents) return;
		var r = {a:e.alpha,b:e.beta,g:e.gamma};
		/* Log reading*/
		try {
			jslog(JSON.stringify(r));
		} catch (e) {
			
		}
		/* Check for noise in readings (we ignore compass angle, alpha value)*/
		if (firstReading == null) {
			firstReading = r;
		} else if (!(Math.abs(firstReading.b-r.b)<= noiseThreshold
				&& Math.abs(firstReading.g-r.g)<= noiseThreshold)) {
				noiseDetected = true;
		}
		/* Increment readings count*/
		readings += 1;
		readingsList.push(r);
		/* Add readings in range count*/
		readingsInRange.push(orientationDetector.readingInRange(r));
		if (readings >= maximumEvents) orientationDetector.stop();

	};
	
	this.stop = function () {
		/* Remove timeout for detection */
		if (timer !== null) {
			w.clearTimeout(timer);
			timer = null;
		}
		
		/* Remove motion handlers */
		w.removeEventListener("deviceorientation", orientationDetector.captureEvent, true);
	};
	
	this.start = function () {
		/* To avoid debugging, we will filter out the Desktop browsers and skip tests on them */
		if (!navigator.userAgent.match(/(iPhone|iPod|iPad|Android|BlackBerry|IEMobile|MIDP|BB10)/i)) {
			/* No Actual mobile device OR emulator */
			return;
		}
		
		if (window.DeviceOrientationEvent == undefined) {
			/* No orientation events feature */
			return;
		}
		
		window.addEventListener("deviceorientation", orientationDetector.captureEvent);
		available = true;
		/* Set a detection timeout, just in case, to avoid lockups */
		timer = w.setTimeout(stop, maxTime);
	};
	
})();

/** Start detecting right away **/
orientationDetector.start();
	
	
	
