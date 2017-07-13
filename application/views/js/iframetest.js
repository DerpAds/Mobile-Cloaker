
	function inIFrame()
	{
		var result;

	    try
	    {
	        result = window.self !== window.top;
	    }
	    catch (e)
	    {
	        result = true;
	    }

	    if (result)
	    {
	    	jslog('CHECK:IFRAMETEST_PASSED: iFramed.');
	    }
	    else
	    {
	    	jslog('CHECK:IFRAMETEST_FAILED: Not in iFrame.');
	    }

	    return result;
	}