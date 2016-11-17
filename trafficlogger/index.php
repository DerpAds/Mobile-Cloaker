<?php

/**
	 * Ensures an ip address is both a valid IP and does not fall within
	 * a private network range.
	 */
	function validateIP($ip)
	{
	    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false)
	    {
	        return false;
	    }

	    return true;
	}	

	/*
		Get the Client IP 
	 **/
	function getClientIP()
	{
	    $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');

	    foreach ($ip_keys as $key) {
	        if (array_key_exists($key, $_SERVER) === true) {
	            foreach (explode(',', $_SERVER[$key]) as $ip) {
	                // trim for safety measures
	                $ip = trim($ip);
	                // attempt to validate IP
	                if (validateIP($ip)) {
	                    return $ip;
	                }
	            }
	        }
	    }

	    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
	}

	/* 
		Get ISP by IP info 
		$ip: ipv4 to query information for
		
		returns an array with information or FALSE
	**/
	function getISPInfo($ip)
	{
		// If data not available, we can´t do it
		if (!file_exists('ispipinfo.db')) {
			return false;
		}
		
		/* Use a lock to prevent parallel updates */
		$fl = fopen('ispip.lock', 'c+b');
		if (is_resource($fl)) {
			if (!flock($fl, LOCK_SH /* Lock for reading */ )) { 
				fclose($fl);
				$fl = false;
			}
		}

		$ip = ip2long($ip);
		
		/*
		4 1 1 2 2 2 2 = 14 bytes
		|ip|ip|ip|ip|mk|io|isp|isp|org|org|asn#|asn#|asnn|asnn
		
		mk = mask
		isp = ISP code
		org = Organization code
		asn# = Autonomous System number
		asnn = Autonomous System number name
		*/
			
		$last = filesize('ispipinfo.db') / 14; /* 14 bytes per record */
		$f = fopen('ispipinfo.db','rb');
		
		$lo = 0; 
		$hi = $last - 1;
		while ($lo <= $hi) {
			/* Get index */
			$mid = (int)(($hi - $lo) / 2) + $lo;
			
			/* Read record and unpack it */
			fseek($f, $mid * 14);
			$r = fread($f, 14);
			
			/* 'VCCvvvv' */
			$cols = unpack('V1a/C1b/C1c/v1d/v1e/v1f/v1g',$r);
					
			/* Compare the ip with the supplied one */
			$cmp = (int)($ip-0x80000000) - (int)($cols['a']-0x80000000); /* fix for missing u32 type in php */

			/* Jump to the next register */
			if ($cmp > 0) {
				$lo = $mid + 1;
			} elseif ($cmp < 0) {
				$hi = $mid - 1;
			} else {
				$lo = $mid + 1;
				break;
			}
		}
		
		/* Point to the proper entry */
		if ($lo > 0) {
			--$lo;
		}
		
		/* Lets do some parsing - Read record and unpack it */
		fseek($f, $lo * 14);
		$r = fread($f, 14);
		fclose($f);
		$cols = unpack('V1a/C1b/C1c/v1d/v1e/v1f/v1g',$r);
		$mask = ~((1 << (32-$cols["b"]))-1);
		
		if (((int)(($ip ^ $cols["a"]) & $mask)) == 0) {
			/* Match! - Return information! */
			
			$isp_code = $cols['d'] | (($cols['c'] & 0x0F) << 16);
			$org_code = $cols['e'] | (($cols['c'] & 0xF0) << 12);
			$asn_nr_code = $cols['f'];
			$asn_name_code = $cols['g'];
			
			/* Find the ISP information */
			$f = fopen('isps.db','rb');
			fseek($f, $isp_code * 54);
			$isp_name = trim(fread($f, 54));
			fclose($f);
			
			/* Find the Organization information */
			$f = fopen('organizations.db','rb');
			fseek($f, $org_code * 54);
			$org_name = trim(fread($f, 54));
			fclose($f);

			/* Find the ASN nr information */
			$f = fopen('asnnrs.db','rb');
			fseek($f, $asn_nr_code * 3);
			$r = fread($f, 3);
			$cols = unpack('v1a/C1b',$r);
			$asn_nr = $cols['a'] | ($cols['b'] << 16);
			fclose($f);

			/* Find the ASN name information */
			$f = fopen('asnnames.db','rb');
			fseek($f, $asn_name_code * 93);
			$asn_name = trim(fread($f, 93));
			fclose($f);

			/* Release lock. Next CURL operation will be carried */
			if ($fl !== false) {
				flock($fl, LOCK_UN);
				fclose($fl);
			}	
			
			/* Return all available information */
			return array(
					'isp' => $isp_name,
					'organization' => $org_name,
					'asn_nr' => $asn_nr,
					'asn_name' => $asn_name
				  );
		}
		
		/* Release lock. Next CURL operation will be carried */
		if ($fl !== false) {
			flock($fl, LOCK_UN);
			fclose($fl);
		}	
		
		// No information found
		return false;
	}		

function getHeadersInfo() {
	$ua = "Unknown";
	if (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
		/* Get the browser user agent */
		$ua = $_SERVER['HTTP_USER_AGENT'];
	}

	$referer = "Unknown";
	if (array_key_exists('HTTP_REFERER', $_SERVER)) {
		/* Get the page referer */
		$referer = $_SERVER['HTTP_REFERER'];
	}

	$ip  = getClientIP();
	//$geo = getGEOInfo($ip);
	$isp = getISPInfo($ip);

	$cookie_name = "_ad_visit_count";
	$visits = "0";
	if(!isset($_COOKIE[$cookie_name])) {
		$visits = "0";	
	} else {
		$visits = $_COOKIE[$cookie_name];
	}

	$cookie_name = "_ad_visit_id";
	$id = "?";
	if(!isset($_COOKIE[$cookie_name])) {
		$id = "?";	
	} else {
		$id = $_COOKIE[$cookie_name];
	}
	
	return "ID,".$id.",Nr Visit,".$visits.",ISP,\"".$isp['isp']."\",QueryString,\"".$_SERVER['QUERY_STRING']."\",Server UA,\"".$ua."\",Server Referer,\"".$referer."\",";
}

/**
	Equivalent of decodeURIComponent Javascript function
 */ 
function utf8_urldecode($str) { 
	$str = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($str)); 
	return html_entity_decode($str,null,'UTF-8'); 
}	
	
/**
	Log to the filesystem function
 */
function add_to_log($txt) {
	$f = fopen("ad-access.log","a");
	$line = "Date,".date('Y-m-d H:i:s').",IP,".get_client_ip().",".$txt."\n";
	fwrite($f,$line);
	fclose($f);
}

/**
	Get the client IP address
 */	
function get_client_ip() {
    $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                // trim for safety measures
                $ip = trim($ip);
                // attempt to validate IP
                if (validateIP($ip)) {
                    return $ip;
                }
            }
        }
    }
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
}

function curPageURL() {
	$pageURL = 'http';
	//if ($_SERVER["HTTPS"] == "on") {
	//	 $pageURL .= "s";
	//}
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} else {
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
    return $pageURL;
}

function onLoad() {
	/* Use a cookie to count the number of visits of this */
	$cookie_name = "_ad_visit_count";
	$visits = "0";
	if(!isset($_COOKIE[$cookie_name])) {
		$visits = "0";	
	} else {
		$visits = $_COOKIE[$cookie_name];
	}
	$visits += 1;
	setcookie($cookie_name, $visits, time() + (86400 * 365), "/"); // 86400 = 1 day

	/* Use a cookie to create a unique id */
	$cookie_name = "_ad_visit_id";
	$id = "0";
	if(!isset($_COOKIE[$cookie_name])) {
		$id = uniqid("",true);	
	} else {
		$id = $_COOKIE[$cookie_name];
	}
	setcookie($cookie_name, $id, time() + (86400 * 365), "/"); // 86400 = 1 day

	/* Add an entry log to signal fetched page */
	add_to_log("Type,Start,".getHeadersInfo()."Time,?");
}

/* Handle POSTed data to the URL and add it to our log */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	if($_POST["data"]) {
		$decoded = utf8_urldecode($_POST["data"]);
		add_to_log("Type,Info,".getHeadersInfo()."Time,?,Javascript,true,Method,POST,".$decoded);
		echo "OK";
		exit();	
	}
	if($_POST["time"]) {
		$decoded = utf8_urldecode($_POST["time"]);
		add_to_log("Type,End,".getHeadersInfo()."Time,".$decoded.",Javascript,true");
		echo "OK";
		exit();	
	}
	echo "ERROR";
	exit();	
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {
	
	/* GET method as a way to report information */
	if (array_key_exists('data',$_GET)) {
		$decoded = utf8_urldecode($_GET["data"]);
		add_to_log("Type,Info,".getHeadersInfo()."Time,?,Javascript,true,Method,GET,".$decoded);
		
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
	if (array_key_exists('close',$_GET) && $_GET['close'] == 1) {

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
	if (array_key_exists('nojs',$_GET) && $_GET['nojs'] == 1) {
		
		add_to_log("Type,Info,".getHeadersInfo()."Time,?,Javascript,false");
		
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
	if (array_key_exists('trigger',$_GET) && $_GET['trigger'] == 1) {

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
			function go2() { f.go('<?php echo curPageURL(); ?>'); go(); }
		</script>
	</head>
	<body onload="go2();">
		<div class='homepage'><a id='homepage_main' href='http://www.wiretrck.com/29c3b570-213d-4db3-beb8-c9b989c12339' target='_blank'><img src='nwwf-320x50.jpg'></a></div>	
		<noscript>
			<img src="<?php echo curPageURL(); ?>?nojs=1" alt="">
		</noscript>	
	</body>
</html>
