
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
	    	jslog('iFramed.');
	    }
	    else
	    {
	    	jslog('Not in iFrame.');
	    }

	    return result;
	}