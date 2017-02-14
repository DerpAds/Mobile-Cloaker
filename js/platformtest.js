
	function platformTest()
	{
	   var result =  /(iphone|linux armv)/i.test(window.navigator.platform);

		if (result)
		{
			jslog('CHECK:PLATFORM_ALLOWED: Platform test succeeded: ' + window.navigator.platform);
		}
		else
		{
			jslog('CHECK:PLATFORM_BLOCKED: Platform test failed: ' + window.navigator.platform);
		}

		return result;
	}