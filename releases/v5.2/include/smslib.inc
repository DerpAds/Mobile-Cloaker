<?php

	function getSMSConfigFilename($SMSCampaignID, $root)
	{
		return $root . "/smsconfigs/" . $SMSCampaignID . ".config";
	}

	function processSMSConfig($SMSCampaignID, $root)
	{
		return json_decode(file_get_contents(getSMSConfigFilename($SMSCampaignID, $root)), true);
	}

	function getSMSConfigs($root)
	{
		$files = scandir($root . "/smsconfigs");
		$result = array();

		foreach ($files as $filename)
		{
			if (strpos($filename, ".config") !== false)
			{
				$SMSConfigID = substr($filename, 0, strpos($filename, ".config"));

				$result[$SMSConfigID] = $filename;				
			}
		}

		return $result;
	}

	function getAllTemplatesByType()
	{
		$files = scandir("pollconfigtemplates");
		$result = array();

		foreach ($files as $filename)
		{
			if (strpos($filename, ".config.template") !== false)
			{			
				$json = json_decode(file_get_contents("pollconfigtemplates/" . $filename), true);

				if ($json["type"] == "GENERAL")
				{
					continue;
				}

				if (!array_key_exists($json["type"], $result))
				{
					$result[$json["type"]] = array();
				}

				array_push($result[$json["type"]], substr($filename, 0, strpos($filename, ".config.template")));
			}
		}

		return $result;
	}

	function getCRMProvider($SMSCampaignID, $root)
	{
		$config = processSMSConfig($SMSCampaignID, $root);

		foreach (array_keys($config) as $key)
		{
			$filename = "pollconfigtemplates/$key.config.template";
			$json = json_decode(file_get_contents($filename), true);

			if ($json["type"] == "CRM")
			{
				return $key;
			}
		}

		return null;
	}

	function getSMSProvider($SMSCampaignID, $root)
	{
		$config = processSMSConfig($SMSCampaignID, $root);

		foreach (array_keys($config) as $key)
		{
			$filename = "pollconfigtemplates/$key.config.template";
			$json = json_decode(file_get_contents($filename), true);

			if ($json["type"] == "SMS")
			{
				return $key;
			}
		}

		return null;
	}

	function isSMSConfigEnabled($SMSCampaignID, $root)
	{
		$config = processSMSConfig($SMSCampaignID, $root);

		return $config["general"]["enabled"] == "true";
	}

	function createOrUpdateSMSConfig($SMSCampaignID, $configArray, $root)
	{
		$filename = getSMSConfigFilename($SMSCampaignID, $root);

		file_put_contents($filename, json_encode($configArray));
	}

	function deleteSMSConfig($SMSCampaignID, $root)
	{
		$filename = getSMSConfigFilename($SMSCampaignID, $root);
		
		if (file_exists($filename))
		{
			unlink($filename);
		}
	}

	// TODO: function should take $root !
	function smslog($SMSCampaignID, $txt)
	{
		$f = fopen("logs/smslog.$SMSCampaignID.log","a");
		fwrite($f, date("m.d.y H:i:s") . ": " . $txt . " \n");
		fclose($f);		
	}

?>