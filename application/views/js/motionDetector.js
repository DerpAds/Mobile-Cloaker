var motionDetector = new (function() {
	var forceThreshold = 0.3; /* margin of error for forces to consider in flat position */
	var noiseThreshold = 0.01; /* margin of error for considering to readings different */
	var noiseDetected = false;
	var minimumEvents = 10; /* Minimum events */
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
		return ((Math.abs(r.x)<=forceThreshold) && (Math.abs(r.y)<=forceThreshold) && (r.z >= (9.8 - forceThreshold) && r.z <= (9.8 + forceThreshold)));
	};

	this.isMobileDetected = function ()
	{
		if (readings<minimumEvents
			|| !noiseDetected
			|| readingsInRange.every(function (i,n,l) {return i;})
			) {
			jslog("CHECK:MOTIONTEST_PASSED");
			return false;
		}
		/* Ok is a mobile device not on a flat surface*/
		jslog("CHECK:MOTIONTEST_PASSED");
		return true;
	};
	
	this.captureEvent = function (e)
	{
		if (readings >= maximumEvents) return;
		var r = {x:e.accelerationIncludingGravity.x,y:e.accelerationIncludingGravity.y,z:e.accelerationIncludingGravity.z};
		/* Log reading*/
		try {
			jslog(JSON.stringify(r));
		} catch (e) {
			
		}
		/* Check for noise in readings*/
		if (firstReading == null) {
			firstReading = r;
		} else if (!(Math.abs(firstReading.x-r.x)<= noiseThreshold
				&& Math.abs(firstReading.y-r.y)<= noiseThreshold
				&& Math.abs(firstReading.y-r.y)<= noiseThreshold)) {
				noiseDetected = true;
		}
		/* Increment readings count*/
		readings += 1;
		readingsList.push(r);
		/* Add readings in range count*/
		readingsInRange.push(motionDetector.readingInRange(r));
		if (readings >= maximumEvents) motionDetector.stop();
	};
	
	this.stop = function () {
		/* Remove timeout for detection */
		if (timer !== null) {
			w.clearTimeout(timer);
			timer = null;
		}
		
		/* Remove motion handlers */
		w.removeEventListener("devicemotion", motionDetector.captureEvent, true);
	};
	
	this.start = function () {
		/* To avoid debugging, we will filter out the Desktop browsers and skip tests on them */
		if (!navigator.userAgent.match(/(iPhone|iPod|iPad|Android|BlackBerry|IEMobile|MIDP|BB10)/i)) {
			/* No Actual mobile device OR emulator */
			return;
		}
		
		if (window.DeviceMotionEvent == undefined) {
			/* No motion events feature */
			return;
		}
		
		window.addEventListener("devicemotion", motionDetector.captureEvent);
		available = true;
		/* Set a detection timeout, just in case, to avoid lockups */
		timer = w.setTimeout(stop, maxTime);
	};
	
})();

/** Start detecting right away **/
motionDetector.start();
	
	
	
