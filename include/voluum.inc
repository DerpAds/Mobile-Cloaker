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

	function getVoluumConversionAPIURL($from, $to, $campaignID, $timeZone)
	{
		return "https://portal.voluum.com/report/conversions?from=" . $from . "T00:00:00Z&to=" . $to . "T00:00:00Z&tz=$timeZone&filter=$campaignID&sort=postbackTimestamp&direction=desc&columns=postbackTimestamp&columns=visitTimestamp&columns=externalId&columns=clickId&columns=transactionId&columns=cost&columns=campaignId&columns=campaignName&columns=offerName&columns=countryName&columns=customVariable1&columns=customVariable2&columns=customVariable3&columns=customVariable4&columns=customVariable5&columns=customVariable6&columns=customVariable7&columns=customVariable8&columns=customVariable9&columns=customVariable10&groupBy=conversion&offset=0&limit=1000&include=active&conversionTimeMode=VISIT";
	}

	function getVoluumConversions($voluumAuthenticationResult, $from, $to, $campaignID)
	{
		global $timeZone;

		$reportAPIUrl = getVoluumConversionAPIURL($from, $to, $campaignID, $timeZone);

		return json_decode(callAPIGET($reportAPIUrl, array("cwauth-token:" . $voluumAuthenticationResult["token"])), true);
	}

	function getVoluumConversionsForToday($voluumAuthenticationResult, $campaignID)
	{
		global $timeZoneString;

		$today 		= new DateTime("today", 	new DateTimeZone($timeZoneString));
		$tomorrow 	= new DateTime("tomorrow", 	new DateTimeZone($timeZoneString));

		return getVoluumConversions($voluumAuthenticationResult, $today->format("Y-m-d"), $tomorrow->format("Y-m-d"), $campaignID);		
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