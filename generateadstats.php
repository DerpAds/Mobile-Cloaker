<?php

	require_once("include/adlib.inc");

	$ads = getAllAds(__DIR__);

	$statsPerCampaign = array();

	foreach ($ads as $campaignID => $filenames)
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
					$stats["PARAMETER_BLOCKED"] = substr_count($log, "CHECK:PARAMETER_BLOCKED:");
					$stats["PARAMETER_ALLOWED"] = substr_count($log, "CHECK:PARAMETER_ALLOWED:");
					$stats["PARAMETER_MISSING"] = substr_count($log, "CHECK:PARAMETER_MISSING:");
					$stats["REFERRER_PARAMETER_BLOCKED"] = substr_count($log, "CHECK:REFERRER_PARAMETER_BLOCKED:");
					$stats["REFERRER_PARAMETER_ALLOWED"] = substr_count($log, "CHECK:REFERRER_PARAMETER_ALLOWED:");
					$stats["REFERRER_PARAMETER_MISSING"] = substr_count($log, "CHECK:REFERRER_PARAMETER_MISSING:");					
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

		$statsPerCampaign[$campaignID] = $stats;
	}

	echo "<table width=\"100%\" border=\"1\">\n";

	echo "<tr>";
	echo "<th>Campaign ID</th>\n";
	echo "<th>Referrer Blacklist Blocked</th>\n";
	echo "<th>Referrer Blacklist Allowed</th>\n";
	echo "<th>Referrer Whitelist Blocked</th>\n";
	echo "<th>Referrer Whitelist Allowed</th>\n";
	echo "<th>Parameter Blocked</th>\n";
	echo "<th>Parameter Allowed</th>\n";
	echo "<th>Parameter Missing</th>\n";
	echo "<th>Referrer Parameter Blocked</th>\n";
	echo "<th>Referrer Parameter Allowed</th>\n";
	echo "<th>Referrer Parameter Missing</th>\n";
	echo "<th>User Agent Not Mobile</th>\n";
	echo "<th>Geo Allowed</th>\n";
	echo "<th>Geo Blocked</th>\n";
	echo "<th>Allowed Traffic</th>\n";
	echo "<th>Total</th>\n";

	foreach ($statsPerCampaign as $campaignID => $stats)
	{
		echo "<tr>";
		echo "<td>$campaignID</td>";
		echo "<td>" . $stats["REFERRER_BLACKLIST_BLOCKED"] . "</td>";
		echo "<td>" . $stats["REFERRER_BLACKLIST_ALLOWED"] . "</td>";
		echo "<td>" . $stats["REFERRER_WHITELIST_BLOCKED"] . "</td>";
		echo "<td>" . $stats["REFERRER_WHITELIST_ALLOWED"] . "</td>";

		echo "<td>" . $stats["PARAMETER_BLOCKED"] . "</td>";
		echo "<td>" . $stats["PARAMETER_ALLOWED"] . "</td>";
		echo "<td>" . $stats["PARAMETER_MISSING"] . "</td>";

		echo "<td>" . $stats["REFERRER_PARAMETER_BLOCKED"] . "</td>";
		echo "<td>" . $stats["REFERRER_PARAMETER_ALLOWED"] . "</td>";
		echo "<td>" . $stats["REFERRER_PARAMETER_MISSING"] . "</td>";

		echo "<td>" . $stats["USERAGENT_MOBILE"] . "</td>";
		echo "<td>" . $stats["GEO_ALLOWED"] . "</td>";
		echo "<td>" . $stats["GEO_BLOCKED"] . "</td>";
		echo "<td>" . $stats["ALLOWED_TRAFFIC"] . "</td>";
		echo "<td>" . $stats["TOTAL"] . "</td>";
		echo "</tr>";
	}

	echo "</table>\n";
?>