<?php

	require_once("include/adlib.inc");

	function getAllNextLevelParametersValues($log, $startsWith)
	{
		$matches = array();

		//$startsWith = "CHECK:PARAMETER_ALLOWED:";
		//$log = "Date|2017-01-10 23:48:58|IP|187.237.231.107|ISP|Telmex|UserAgent|Mozilla/5.0 (Linux; Android 4.0.3; U9200 Build/HuaweiU9200) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.166 Mobile Safari/535.19|SERVER Referrer|http://delivery.porn.com/c2hvd19zdGQkJDk2MjUyMA==?ident=8722&id_site=10&id_channel=43&ref=http://es.porn.com/videos/boob-licking&sr=http://www.google.com.mx/search?client=ms-android-huawei&sourceid=chrome-mobile&ie=UTF-8&q=mamando chiches&id_dimension=13&width=300&height=250&id_palette=1&ck=922&uf=-1&vars={\"site_id\":10,\"channel_id\":43}&k=[\"boob licking\"]&m_url=media.porn.com|QueryString|0804664&site_id=10&site_name=Porn.com Mobile&channel_id=43&channel_name=Site Wide Footer&keywords=&ad_id=1013585&v_country=MX&v_language=ES&v_browser=chrome&v_os=android&v_device=Huawei U9200&v_device type=phone&v_carrier=telcel|Message|CHECK:PARAMETER_ALLOWED:site_name:Porn.com Mobile: Parameter site_name with value Porn.com Mobile is allowed.";

		$regex = "/$startsWith(.*):(.*):/";

		//echo $regex;

		preg_match_all($regex, $log, $matches);

		//print_r_nice($matches);
		//exit;

		$result = array();

		for ($i = 0; $i < sizeof($matches[1]); $i++)
		{
			if (!array_key_exists($matches[1][$i], $result))
			{
				$result[$matches[1][$i]] = array();
			}

			if (!array_key_exists($matches[2][$i], $result[$matches[1][$i]]))
			{
				$result[$matches[1][$i]][$matches[2][$i]] = 0;	
			}

			$result[$matches[1][$i]][$matches[2][$i]]++;
		}

		//print_r_nice($result);

		return $result;
	}

	function printArrayToTable($array)
	{
		if (!is_array($array))
		{
			return $array;
		}

		if (empty($array))
		{
			return "0";
		}

		$result = "";

		foreach ($array as $key => $values)
		{
			$result .= "<strong>$key</strong><br/>";

			$result .= "<table width=\"100%\" border=\"1\">";

			foreach ($values as $param => $paramValue)
			{
				$result .= "<tr><td width=\"50%\">$param</td><td>$paramValue</td></tr>";
			}

			$result .= "</table>";
		}

		return $result;
	}

	function getAdStats($campaignID)
	{
		$logFilenames = getAdLogFilenames(__DIR__, $campaignID);

		$stats = array();

		$stats["REFERRER_BLACKLIST_BLOCKED"] = 0;
		$stats["REFERRER_BLACKLIST_ALLOWED"] = 0;
		$stats["REFERRER_WHITELIST_BLOCKED"] = 0;
		$stats["REFERRER_WHITELIST_ALLOWED"] = 0;
		$stats["PARAMETER_BLOCKED"] = 0;
		$stats["PARAMETER_ALLOWED"] = 0;
		$stats["PARAMETER_MISSING"] = 0;
		$stats["REFERRER_PARAMETER_BLOCKED"] = 0;
		$stats["REFERRER_PARAMETER_ALLOWED"] = 0;
		$stats["REFERRER_PARAMETER_MISSING"] = 0;		
		$stats["USERAGENT_MOBILE"] = 0;
		$stats["GEO_ALLOWED"] = 0;
		$stats["GEO_BLOCKED"] = 0;
		$stats["ALLOWED_TRAFFIC"] = 0;
		$stats["TOTAL"] = 0;

		foreach ($logFilenames as $logFilename)
		{
			if (file_exists($logFilename))
			{
				$log = file_get_contents($logFilename);

				if (strpos($logFilename, "mbotlog") !== false)
				{
					$stats["REFERRER_BLACKLIST_BLOCKED"] = substr_count($log, "CHECK:REFERRER_BLACKLIST_BLOCKED:");
					$stats["REFERRER_BLACKLIST_ALLOWED"] = substr_count($log, "CHECK:REFERRER_BLACKLIST_ALLOWED:");
					$stats["REFERRER_WHITELIST_BLOCKED"] = substr_count($log, "CHECK:REFERRER_WHITELIST_BLOCKED:");
					$stats["REFERRER_WHITELIST_ALLOWED"] = substr_count($log, "CHECK:REFERRER_WHITELIST_ALLOWED:");
					$stats["PARAMETER_BLOCKED"] = getAllNextLevelParametersValues($log, "CHECK:PARAMETER_BLOCKED:"); //substr_count($log, "CHECK:PARAMETER_BLOCKED:");
					$stats["PARAMETER_ALLOWED"] = getAllNextLevelParametersValues($log, "CHECK:PARAMETER_ALLOWED:"); //substr_count($log, "CHECK:PARAMETER_ALLOWED:");
					$stats["PARAMETER_MISSING"] = getAllNextLevelParametersValues($log, "CHECK:PARAMETER_MISSING:"); //substr_count($log, "CHECK:PARAMETER_MISSING:");
					$stats["REFERRER_PARAMETER_BLOCKED"] = getAllNextLevelParametersValues($log, "CHECK:REFERRER_PARAMETER_BLOCKED:"); //substr_count($log, "CHECK:REFERRER_PARAMETER_BLOCKED:");
					$stats["REFERRER_PARAMETER_ALLOWED"] = getAllNextLevelParametersValues($log, "CHECK:REFERRER_PARAMETER_ALLOWED:"); //substr_count($log, "CHECK:REFERRER_PARAMETER_ALLOWED:");
					$stats["REFERRER_PARAMETER_MISSING"] = getAllNextLevelParametersValues($log, "CHECK:REFERRER_PARAMETER_MISSING:"); //substr_count($log, "CHECK:REFERRER_PARAMETER_MISSING:");

					
				}
				elseif (strpos($logFilename, "adlog") !== false)
				{
					$stats["TOTAL"] = substr_count($log, "INFO:GEO:");
					$stats["USERAGENT_MOBILE"] = substr_count($log, "CHECK:USERAGENT_MOBILE:");
					$stats["GEO_ALLOWED"] = substr_count($log, "CHECK:GEO_ALLOWED:");
					$stats["GEO_BLOCKED"] = substr_count($log, "CHECK:GEO_BLOCKED:");
				}
				elseif (strpos($logFilename, "allowed_traffic") !== false)
				{
					$stats["ALLOWED_TRAFFIC"] = substr_count($log, "CHECK:ALLOWED_TRAFFIC:");
				}
			}
		}

		return $stats;		
	}

	function renderAdStats($campaignID, $stats)
	{
		$result = "";

		$result .= "<table width=\"100%\" border=\"1\">\n";

		$result .= "<tr>";
		$result .= "<th width=\"50%\">Campaign ID</th>\n";
		$result .= "<th>$campaignID</th>";
		$result .= "</tr>";

		$result .= "<tr>";
		$result .= "<td>Referrer Blacklist Blocked</td>\n";
		$result .= "<td>" . $stats["REFERRER_BLACKLIST_BLOCKED"] . "</td>";
		$result .= "</tr>";

		$result .= "<tr>";
		$result .= "<td>Referrer Blacklist Allowed</td>\n";
		$result .= "<td>" . $stats["REFERRER_BLACKLIST_ALLOWED"] . "</td>";
		$result .= "</tr>";

		$result .= "<tr>";
		$result .= "<td>Referrer Whitelist Blocked</td>\n";
		$result .= "<td>" . $stats["REFERRER_WHITELIST_BLOCKED"] . "</td>";
		$result .= "</tr>";

		$result .= "<tr>";
		$result .= "<td>Referrer Whitelist Allowed</td>\n";
		$result .= "<td>" . $stats["REFERRER_WHITELIST_ALLOWED"] . "</td>";
		$result .= "</tr>";

		$result .= "<tr>";
		$result .= "<td>Parameter Blocked</td>\n";
		$result .= "<td>" . printArrayToTable($stats["PARAMETER_BLOCKED"]) . "</td>";
		$result .= "</tr>";

		$result .= "<tr>";
		$result .= "<td>Parameter Allowed</td>\n";
		$result .= "<td>" . printArrayToTable($stats["PARAMETER_ALLOWED"]) . "</td>";
		$result .= "</tr>";

		$result .= "<tr>";
		$result .= "<td>Parameter Missing</td>\n";
		$result .= "<td>" . printArrayToTable($stats["PARAMETER_MISSING"]) . "</td>";
		$result .= "</tr>";

		$result .= "<tr>";
		$result .= "<td>Referrer Parameter Blocked</td>\n";
		$result .= "<td>" . printArrayToTable($stats["REFERRER_PARAMETER_BLOCKED"]) . "</td>";
		$result .= "</tr>";

		$result .= "<tr>";
		$result .= "<td>Referrer Parameter Allowed</td>\n";
		$result .= "<td>" . printArrayToTable($stats["REFERRER_PARAMETER_ALLOWED"]) . "</td>";
		$result .= "</tr>";

		$result .= "<tr>";
		$result .= "<td>Referrer Parameter Missing</td>\n";
		$result .= "<td>" . printArrayToTable($stats["REFERRER_PARAMETER_MISSING"]) . "</td>";
		$result .= "</tr>";

		$result .= "<tr>";
		$result .= "<td>User Agent Not Mobile</td>\n";
		$result .= "<td>" . $stats["USERAGENT_MOBILE"] . "</td>";
		$result .= "</tr>";

		$result .= "<tr>";
		$result .= "<td>Geo Allowed</td>\n";
		$result .= "<td>" . $stats["GEO_ALLOWED"] . "</td>";
		$result .= "</tr>";

		$result .= "<tr>";
		$result .= "<td>Geo Blocked</td>\n";
		$result .= "<td>" . $stats["GEO_BLOCKED"] . "</td>";
		$result .= "</tr>";

		$result .= "<tr>";
		$result .= "<td>Allowed Traffic</td>\n";
		$result .= "<td>" . $stats["ALLOWED_TRAFFIC"] . "</td>";
		$result .= "</tr>";

		$result .= "<tr>";
		$result .= "<td>Total</td>\n";
		$result .= "<td>" . $stats["TOTAL"] . "</td>";
		$result .= "</tr>";		
		
		$result .= "</table>\n";
		$result .= "<br/><br/>";

		return $result;
	}

	$ads = getAllAds(__DIR__);

	$statsPerCampaign = array();

	foreach ($ads as $campaignID => $filenames)
	{
		$statsPerCampaign[$campaignID] = getAdStats($campaignID);
	}

	foreach ($statsPerCampaign as $campaignID => $stats)
	{
		echo renderAdStats($campaignID, $stats);
	}

?>