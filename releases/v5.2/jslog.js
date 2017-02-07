	function jslog(txt)
	{		
		var queryUrl = "http://awstst1.com/adhost/jslog.php?txt=" + txt;

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

		req.open("GET", queryUrl, true);
		req.send();
	}  