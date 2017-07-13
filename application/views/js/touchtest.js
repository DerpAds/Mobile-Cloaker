
	function isTouch()
	{
	   var result =  (('ontouchstart' in window) ||	/* All standard browsers, except IE */
					  (navigator.MaxTouchPoints > 0) || (navigator.msMaxTouchPoints > 0));

		if (!result)
		{
			jslog('CHECK:TOUCHTEST_FAILED');
		}
		else
		{
			jslog('CHECK:TOUCHTEST_PASSED');
		}

		return result;
	}