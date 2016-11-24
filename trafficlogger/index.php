<?php

	if (file_exists("../adlib.inc"))
	{
		require_once("../adlib.inc");
	}
	elseif (file_exists("adlib.inc"))
	{
		require_once("adlib.inc");
	}
	else
	{
		die('Cannot find include file.');
	}

	function getHeadersInfo()
	{
		$ua = "Unknown";
		if (array_key_exists('HTTP_USER_AGENT', $_SERVER))
		{
			$ua = $_SERVER['HTTP_USER_AGENT'];
		}

		$referer = "Unknown";
		if (array_key_exists('HTTP_REFERER', $_SERVER))
		{
			$referer = $_SERVER['HTTP_REFERER'];
		}

		$ip  = getClientIP();
		//$geo = getGEOInfo($ip);
		$isp = getISPInfo($ip);

		$cookie_name = "_ad_visit_count";
		$visits = "0";
		if(!isset($_COOKIE[$cookie_name]))
		{
			$visits = "0";	
		}
		else
		{
			$visits = $_COOKIE[$cookie_name];
		}

		$cookie_name = "_ad_visit_id";
		$id = "?";
		
		if(!isset($_COOKIE[$cookie_name]))
		{
			$id = "?";	
		}
		else
		{
			$id = $_COOKIE[$cookie_name];
		}
		
		//return "ID,".$id.",Nr Visit,".$visits.",ISP,\"".$isp['isp']."\",QueryString,\"".$_SERVER['QUERY_STRING']."\",Server UA,\"".$ua."\",Server Referer,\"".$referer."\",";
		return "ISP|\"".$isp['isp']."\"|QueryString|\"".$_SERVER['QUERY_STRING']."\"|";
	}

	/**
		Equivalent of decodeURIComponent Javascript function
	 */ 
	function utf8_urldecode($str)
	{ 
		$str = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($str));

		return html_entity_decode($str,null,'UTF-8'); 
	}	
		
	/**
		Log to the filesystem function
	 */
	function add_to_log($txt)
	{
		$f = fopen("ad-access.log","a");
		$line = "Date|" . date("Y-m-d H:i:s") . "|IP|" . getClientIP() . "|" . $txt . "\n";
		fwrite($f,$line);
		fclose($f);
	}

	function curPageURL()
	{
		$pageURL = 'http';

		if (!empty($_SERVER["HTTPS"]))
		{
			 $pageURL .= "s";
		}

		$pageURL .= "://";

		if ($_SERVER["SERVER_PORT"] != "80")
		{
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		}
		else
		{
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}

	    return $pageURL;
	}

	function onLoad()
	{
		/* Use a cookie to count the number of visits of this */
		$cookie_name = "_ad_visit_count";
		$visits = "0";
		
		if (!isset($_COOKIE[$cookie_name]))
		{
			$visits = "0";	
		}
		else
		{
			$visits = $_COOKIE[$cookie_name];
		}
		
		$visits += 1;
		setcookie($cookie_name, $visits, time() + (86400 * 365), "/"); // 86400 = 1 day

		/* Use a cookie to create a unique id */
		$cookie_name = "_ad_visit_id";
		$id = "0";
		
		if (!isset($_COOKIE[$cookie_name]))
		{
			$id = uniqid("",true);	
		}
		else
		{
			$id = $_COOKIE[$cookie_name];
		}

		setcookie($cookie_name, $id, time() + (86400 * 365), "/"); // 86400 = 1 day

		/* Add an entry log to signal fetched page */
		//add_to_log("Type,Start,".getHeadersInfo()."Time,?");
	}

/* Handle POSTed data to the URL and add it to our log */
if ($_SERVER["REQUEST_METHOD"] == "POST")
{
	if ($_POST["data"])
	{
		$decoded = utf8_urldecode($_POST["data"]);
		//add_to_log("Type,Info,".getHeadersInfo()."Time,?,Javascript,true,Method,POST,".$decoded);
		add_to_log("Type|Info|".getHeadersInfo().$decoded);
		echo "OK";
		exit();	
	}
	
	if ($_POST["time"])
	{
		$decoded = utf8_urldecode($_POST["time"]);
		add_to_log("Type|End|".getHeadersInfo()."Time|".$decoded."|Javascript|true");
		echo "OK";
		exit();	
	}
	
	echo "ERROR";
	exit();	
}

if ($_SERVER["REQUEST_METHOD"] == "GET")
{	
	/* GET method as a way to report information */
	if (array_key_exists('data',$_GET))
	{
		$decoded = utf8_urldecode($_GET["data"]);
		//add_to_log("Type,Info,".getHeadersInfo()."Time,?,Javascript,true,Method,GET,".$decoded);
		add_to_log("Type|Info|".getHeadersInfo().$decoded);
		
		// Create a blank image
		$im = imagecreatetruecolor(1, 1);

		// Set the content type header - in this case image/gif
		header('Content-Type: image/gif');

		// Output the image
		imagegif($im);

		// Free up memory
		imagedestroy($im);
		
		exit();	
	}
	
	/* Popup close virtual page */
	if (array_key_exists('close',$_GET) && $_GET['close'] == 1)
	{

?>	
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8"/>
		<meta name='viewport' content='width=device-width, initial-scale=1' />
		<title>Popup Page</title>
		<script type="text/javascript">
			function go() { setTimeout(function() {window.close();},500); window.open('','_self').close(); window.open('', '_self', ''); window.close(); }
		</script>
	</head>
	<body onload="go();">
	</body>
</html>
<?php	
		exit();
	}
	
	/* If we were called by a noscript tag, log that javascript was disabled */
	if (array_key_exists('nojs',$_GET) && $_GET['nojs'] == 1)
	{		
		add_to_log("Type|Info|".getHeadersInfo()."Time|?|Javascript|false");
		
		// Create a blank image
		$im = imagecreatetruecolor(1, 1);

		// Set the content type header - in this case image/gif
		header('Content-Type: image/gif');

		// Output the image
		imagegif($im);

		// Free up memory
		imagedestroy($im);
		exit();
	}
	
	/* Trigger url */
	if (array_key_exists('trigger',$_GET) && $_GET['trigger'] == 1)
	{
		/* Start detection process */
		onLoad();
		
		// Create a blank image and add some text
		$im = imagecreatetruecolor(1, 1);

		// Set the content type header - in this case image/gif
		header('Content-Type: image/gif');

		// Output the image
		imagegif($im);

		// Free up memory
		imagedestroy($im);
		exit();
	}
}

/* If control reached here, then we are ready to output the ad */
onLoad();

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8"/>
		<meta name='viewport' content='width=device-width, initial-scale=1' />
		<title>Boom Beach</title>
		<script type="text/javascript" src="lg.me.js"> </script>
		<script type="text/javascript">
			function go2() { f.go('<?php echo curPageURL(); ?>'); }
		</script>
	</head>
	<body onload="go2();">
		<div class='homepage'><a id='homepage_main' href='http://www.wiretrck.com/29c3b570-213d-4db3-beb8-c9b989c12339' target='_blank'><img src='nwwf-320x50.jpg'></a></div>	
		<noscript>
			<img src="<?php echo curPageURL(); ?>?nojs=1" alt="">
		</noscript>	
	</body>
</html>