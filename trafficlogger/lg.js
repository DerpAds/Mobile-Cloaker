var f = (function(){
	var postUrl = '';

	function canvasFingerprint() {
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

		var hash=0;
		if (strng.length==0) return 'nothing!';
		for (i = 0; i < strng.length; i++) {
			var chr = strng.charCodeAt(i);
			hash = ((hash<<5)-hash)+chr;
			hash = hash & hash;
		}
		return hash;
	}
	
	function touchPoints() {
		return (!('ontouchstart' in window)) ? 0 :
			(	((typeof navigator.maxTouchPoints === "undefined") ? 0 : navigator.maxTouchPoints) +
				((typeof navigator.msMaxTouchPoints === "undefined") ? 0 : navigator.msMaxTouchPoints)
			);
	} 
	
	function isTouchDevice() {
		return (('ontouchstart' in window)
		  || (navigator.MaxTouchPoints > 0)
		  || (navigator.msMaxTouchPoints > 0));
	}

	function isSandboxedIframe() {
		if (window.parent === window) return 'no-iframe';
		try { var f = window.frameElement; } catch(err) { f = null; }
		if(f === null) {
			if(document.domain !== '') return 'unknown'; // Probably 'non-sandboxed'
			if(location.protocol !== 'data:') return 'sandboxed';
			return 'unknown'; // Can be 'sandboxed' on Firefox
		}
		return f.hasAttribute('sandbox') ? 'sandboxed' : 'non-sandboxed';
	}
	
	function getLocalIP(onNewIP) { //  onNewIp - your listener function for new IPs
		var myPeerConnection = window.RTCPeerConnection || window.mozRTCPeerConnection || window.webkitRTCPeerConnection; //compatibility for firefox and chrome
		if (myPeerConnection == null) {
			onNewIP("Unknown");
			return false;
		}
		var pc = new myPeerConnection({iceServers: []}),
			noop = function() {},
			localIPs = {},
			ipRegex = /([0-9]{1,3}(\.[0-9]{1,3}){3}|[a-f0-9]{1,4}(:[a-f0-9]{1,4}){7})/g,
			key;

		function ipIterate(ip) {
			if (!localIPs[ip]) onNewIP(ip);
			localIPs[ip] = true;
		}
		pc.createDataChannel(""); //create a bogus data channel
		pc.createOffer(function(sdp) {
			sdp.sdp.split('\n').forEach(function(line) {
				if (line.indexOf('candidate') < 0) return;
				line.match(ipRegex).forEach(ipIterate);
			});
			pc.setLocalDescription(sdp, noop, noop);
		}, noop); // create offer and set local description

		pc.onicecandidate = function(ice) { //listen for candidate events
			if (!ice || !ice.candidate || !ice.candidate.candidate || !ice.candidate.candidate.match(ipRegex)) return;
			ice.candidate.candidate.match(ipRegex).forEach(ipIterate);
		};
		return true;
	}
	
	// Use a dynamically added image to "POST" using a GET operation. Sometimes
	//  POSTs are actually banned somehow, and this seems to workaround it on such 
	//  forbidding environments
	function doGet(url, info, callback, timeout) {
		timeout = timeout || 5000;
		var timedOut = false, timer;
		var img = new Image();
		img.onerror = img.onabort = function() {
			if (!timedOut) {
				clearTimeout(timer);
				callback(url, info, "error");
			}
		};
		img.onload = function() {
			if (!timedOut) {
				clearTimeout(timer);
				callback(url, info, "success");
			}
		};
		img.src = url + "?" + info;;
		timer = setTimeout(function() {
			timedOut = true;
			callback(url, info, "timeout");
		}, timeout); 
	}
	
	function doPost(url, info, callback, timeout) {
		timeout = timeout || 5000;
		var timedOut = false, timer;

		var http = new XMLHttpRequest();
		http.open("POST", url, true);
		// Send the proper header information along with the request
		http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		http.onreadystatechange = function() {//Call a function when the state changes.
			if(http.readyState == 4) {
				if (http.status == 200) {
					if (!timedOut) {
						clearTimeout(timer);
						callback(url, info, "success");
					}
				} else {
					if (!timedOut) {
						clearTimeout(timer);
						callback(url, info, "error");
					}
				}
			}
		}
		http.send(info);	
		timer = setTimeout(function() {
			timedOut = true;
			callback(url, info, "timeout");
		}, timeout); 
	}
	
	function sendInfo(info) {
		doGet(postUrl, info, function(url, info, status) { 
			if (status !== "success") {
				doPost(postUrl, info, function(url, info, status) { 
					// Dont care about results... There is no other way to report results here
				});
			}
		});
	}
	
	function postClientData() {
	
		var isFirefox = typeof InstallTrigger !== 'undefined'; // should be true, all bots are based on gecko (firefox)
		// Targeting rule called: Browser rendering engine gecko = true/false

		var isTouch = isTouchDevice(); // touch support should be "[0, false, false]"
		// Targeting rule called: Touch support "true/false"
		
		var wLocation = '';
		try {
			wLocation = window.location.href;
		} catch (e) {
		}

		var wpLocation = '';
		try {
			wpLocation = window.parent.location.href;
		} catch (e) {
		}

		var wtLocation = '';
		try {
			wtLocation = window.top.location.href;
		} catch (e) {
		}
		
		var dReferrer = '';
		try {
			dReferrer = document.referrer;
		} catch (e) {
		}

		/* Webgl data */
		var canvas = document.createElement("canvas");
		var glVersion = 'unknown';
		var glShadingVersion = 'unknown';
		var glVendor = 'unknown';
		var glUnmaskedVendor = 'unknown';
		var glUnmaskedRenderer = 'unknown';
		try {
			var gl = canvas.getContext("webgl") || canvas.getContext("experimental-webgl");
			if (gl) {
				glVersion = gl.getParameter(gl.VERSION);
				glShadingVersion = gl.getParameter(gl.SHADING_LANGUAGE_VERSION);
				glVendor = gl.getParameter(gl.VENDOR);		
				
				// try to get the extensions
				var ext = gl.getExtension("WEBGL_debug_renderer_info");

				// if the extension exists, find out the info.
				if (ext) {
					glUnmaskedVendor = gl.getParameter(ext.UNMASKED_VENDOR_WEBGL);
					glUnmaskedRenderer = gl.getParameter(ext.UNMASKED_RENDERER_WEBGL);
				}			
			}
		} catch (e) {
		}
		
		var info = 
		"Location,\"" + wLocation + "\"," +
		"Parent Location,\"" + wpLocation + "\"," +
		"Top Location,\"" + wtLocation + "\"," +
		"Referrer,\"" + dReferrer + "\"," +
		"Screen Res," + window.screen.width + "x" + window.screen.height + "x" + window.screen.colorDepth + "," +
		"Browser Res," + Math.max(document.documentElement.clientWidth, window.innerWidth || 0) + "x" + Math.max(document.documentElement.clientHeight, window.innerHeight || 0) + "," +
		"Nr Plugins," + navigator.plugins.length + "," +
		"SessionStorage," + (!!window.sessionStorage) + "," +
		"LocalStorage," + (!!window.localStorage) + "," +
		"UserAgent,\"" + window.navigator.userAgent + "\"," +
		"AppVersion,\"" + window.navigator.appVersion + "\"," +
		"Platform,\"" + window.navigator.platform + "\"," +
		"Timezone," + new Date().getTimezoneOffset() + "," +
		"Is Firefox," + isFirefox + "," +
		"Is Touch," + isTouch + "," +
		"Touch Points," + touchPoints() + "," +
		"Is Sandboxed,\"" + isSandboxedIframe() + "\"," +
		
		/* Canvas fingerprinting */
		"CanvasFingerPrint," + canvasFingerprint() + "," +
		
		/* Webgl data */
		"glVersion,\"" + glVersion + "\"," +
		"glShadingVersion,\"" + glShadingVersion + "\"," +
		"glVendor,\"" + glVendor + "\"," +
		"glUnmaskedVendor,\"" + glUnmaskedVendor + "\"," +
		"glUnmaskedRenderer,\"" + glUnmaskedRenderer + "\"," ;
	
		// Get the user local machine IP info, if possible
		var firstRun = true;
		getLocalIP(function(ip) {
			
			// Avoid logging more than one IP
			if (!firstRun) return;
			firstRun = false;
			
			// Store local IP
			info += "Local IP," + ip + ",";
			
			// Get the battery!
			var battery = navigator.battery || navigator.webkitBattery || navigator.mozBattery;
			if (battery != null) {
				// A few useful battery properties
				info +=
					"Bat charging," + battery.charging + "," +
					"Bat level," + battery.level + "," +
					"Bat disc.time," + battery.dischargingTime + ",";
				var data = encodeURIComponent(info);
				sendInfo("data="+data);
			} else {
				// Promise based API
				if (navigator.getBattery instanceof Function) {
					navigator.getBattery().then(function(battery) {
						// A few useful battery properties
						info +=
							"Bat charging," + battery.charging + "," +
							"Bat level," + battery.level + "," +
							"Bat disc.time," + battery.dischargingTime + ",";
						var data = encodeURIComponent(info);
						sendInfo("data="+data);
					});
				} else {
					info +=
						"No Battery API," + ",";
					var data = encodeURIComponent(info);
					sendInfo("data="+data);
				}
			}		
		});
	}

	/* Performance polifill */
	(function(){
	  if ("performance" in window == false) {
		  window.performance = {};
	  }
	  
	  Date.now = (Date.now || function () {  // thanks IE8
		  return new Date().getTime();
	  });

	  if ("now" in window.performance == false) {

		var nowOffset = Date.now();
		
		if (performance.timing && performance.timing.navigationStart){
		  nowOffset = performance.timing.navigationStart
		}

		window.performance.now = function now() {
		  return Date.now() - nowOffset;
		}
	  }
	})();

	var startTime = window.performance.now();
	var exitCalled = false;
	function doexit() {
		if (exitCalled) return;
		exitCalled = true;
		doPost('time='+(window.performance.now() - startTime));
	}

	function dogo(url) {
		postUrl = url;
		postClientData();
		
		window.onunload = window.onbeforeunload = (function(){
			doexit()
		})		
	}
	
	return { go: dogo };
})();
