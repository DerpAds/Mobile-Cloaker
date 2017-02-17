<?php

	$timeZoneString 			= "America/Los_Angeles";
	$timeZone 					= urlencode($timeZoneString);
	$voluumAuthenticationUrl 	= "https://security.voluum.com/login";

	function getVoluumAuthenticationString($voluumusername, $voluumpassword)
	{
		return "Basic " . base64_encode("$voluumusername:$voluumpassword");
	}

	function getVoluumAuthenticationResult($voluumusername, $voluumpassword)
	{
		global $voluumAuthenticationUrl;

		return json_decode(callAPIGET($voluumAuthenticationUrl, array("Authorization:" . getVoluumAuthenticationString($voluumusername, $voluumpassword))), true);
	}

	function getVoluumAuthenticationSuccess($voluumAuthenticationResult)
	{
		return array_key_exists("loggedIn", $voluumAuthenticationResult) && $voluumAuthenticationResult["loggedIn"] == 1;
	}

	function getVoluumReportAPIURL($from, $to, $keyword, $timeZone)
	{
		return "https://portal.voluum.com/report?from=" . $from . "T00:00:00Z&to=" . $to . "T00:00:00Z&tz=$timeZone&filter=$keyword&sort=visits&direction=desc&columns=campaignName&columns=campaignId&columns=impressions&columns=visits&columns=clicks&columns=profit&columns=cpv&columns=ctr&columns=cr&columns=cv&columns=roi&columns=epv&columns=epc&columns=ap&columns=errors&columns=trafficSourceName&groupBy=campaign&offset=0&limit=1000&include=all";
	}

	// Dateformat: Y-m-d
	function getVoluumCampaigns($voluumAuthenticationResult, $from, $to)
	{
		global $timeZone;

		$reportAPIUrl = getVoluumReportAPIURL($from, $to, "", $timeZone);

		return json_decode(callAPIGET($reportAPIUrl, array("cwauth-token:" . $voluumAuthenticationResult["token"])), true);
	}

	function getVoluumCampaignsForToday($voluumAuthenticationResult)
	{
		global $timeZoneString;

		$today 		= new DateTime("today", 	new DateTimeZone($timeZoneString));
		$tomorrow 	= new DateTime("tomorrow", 	new DateTimeZone($timeZoneString));

		return getVoluumCampaigns($voluumAuthenticationResult, $today->format("Y-m-d"), $tomorrow->format("Y-m-d"));
	}

	function getVoluumCampaignsAssoc($voluumReportResult)
	{
		$result = array();

		foreach ($voluumReportResult["rows"] as $row)
		{
			$result[$row["campaignId"]] = $row["campaignName"];
		}

		return $result;
	}

	function getVoluumCampaignData($voluumAuthenticationResult, $voluumcampaignid, $from, $to)
	{
		global $timeZone;

		$reportAPIUrl = getVoluumReportAPIURL($from, $to, $voluumcampaignid, $timeZone);

		return json_decode(callAPIGET($reportAPIUrl, array("cwauth-token:" . $voluumAuthenticationResult["token"])), true);		
	}

	function getVoluumCampaignDataForToday($voluumAuthenticationResult, $voluumcampaignid)
	{
		global $timeZoneString;

		$today 		= new DateTime("today", 	new DateTimeZone($timeZoneString));
		$tomorrow 	= new DateTime("tomorrow", 	new DateTimeZone($timeZoneString));

		return getVoluumCampaignData($voluumAuthenticationResult, $voluumcampaignid, $today->format("Y-m-d"), $tomorrow->format("Y-m-d"));
	}

?>