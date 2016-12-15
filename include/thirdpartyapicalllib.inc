<?php

	/*
		Array
		(
		[response_code] => 100
		[campaign_id] => 79
		[first_name] => James
		[last_name] => Ross
		[address] => 505 galbreath st
		[address2] => 
		[city] => New Castlenew castle
		[state] => PA
		[state_id] => PA
		[zip] => 16101
		[country] => US
		[phone] => 7246149886
		[email] => rossj643@gmail.com
		[ip_address] => 162.158.74.61
		[date_created] => 2016-12-11 03:42:34
		[affiliate] => 
		[sub_affiliate] => 
		[p_notes] => Array
		(
		[0] => Referer: https://www.androenhance.com/7riz/m/shipping.php?s1=w9Q54I86LON9Q8O11CJGRPEQ
		User Agent: Mozilla/5.0 (Linux; Android 5.1.1; LG-H634 Build/LMY47V; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/54.0.2840.85 Mobile Safari/537.36
		)

		)
	*/
	function getLimeLightCMSProspectInfo($prospectId)
	{
		$apiUrl = "https://safeguardoffers.limelightcrm.com/admin/membership.php";

		$response = callAPI($apiUrl, array("username" 		=> "prospect_capture", 
										   "password" 		=> "UPZCkKYFeMAKr", 
										   "method" 		=> "prospect_view",
										   "prospect_id" 	=> $prospectId));

		parse_str($response, $json);

		return $json;		
	}

	function getLimeLightCMSUrlFromBaseUrl($config)
	{
		return "https://$config[baseUrl].limelightcrm.com/";
	}

	function getLimeLightCMSProspects($config, $startDate)
	{
		$apiUrl = getLimeLightCMSUrlFromBaseUrl($config) . "admin/membership.php";

		$response = callAPI($apiUrl, array("username" 		=> $config["username"],//"prospect_capture", 
										   "password" 		=> $config["password"], //"UPZCkKYFeMAKr", 
										   "method" 		=> "prospect_find",
										   "campaign_id" 	=> $config["campaignID"], //$campaignId,
										   "start_date" 	=> $startDate,
										   "end_date"		=> "12/31/" . (date("Y") + 1),
										   "return_type" 	=> "prospect_view"));

		$json = json_decode(substr($response, strpos($response, "&data=") + 6), true);

		if ($json == null)
		{
			return $response;
		}		

		return $json;
	}

	function addEZTextingContact($phoneNumber, $firstName, $lastName, $groups)
	{
		if (!is_array($groups))
		{
			die("EZtexting groups parameter must be an array.");
		}

		$apiUrl = "https://app.eztexting.com/contacts?format=json";

		$response = callAPI($apiUrl, array("User" 			=> "acmsms", 
							   			   "Password" 		=> "Adcrush123!", 
							   			   "Groups" 		=> $groups,
							   			   "FirstName"		=> $firstName,
							   			   "LastName"		=> $lastName,
							   			   "PhoneNumber" 	=> $phoneNumber));

		$json = json_decode($response, true);

		return $json;
	}

	function hasBeenProspectForXMins($dateTime, $syncTimeOut)
	{
		//Format: 2016-12-11 03:42:34
		$now = new DateTime("now", new DateTimeZone("America/New_York"));
		$created = DateTime::createFromFormat("Y-m-d H:i:s", urldecode($dateTime), new DateTimeZone("America/New_York"));

		$diff = $created->diff($now);

		$minutes = $diff->days * 24 * 60 + $diff->h * 60 + $diff->i;

		return $minutes > $syncTimeOut;
	}

	function validPhoneNumber($phoneNumber)
	{
		return preg_match("/^[2-9][0-9]{2}(-)?[0-9]{3}(-)?[0-9]{4}$/", $phoneNumber) === 1;
	}

?>