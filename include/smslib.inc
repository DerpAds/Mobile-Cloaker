<?php

	function getSMSConfigFilename($SMSCampaignID)
	{
		return "smsconfigs/" . $SMSCampaignID . ".config";
	}

	function processSMSConfig($filename)
	{
		return json_decode(file_get_contents($filename), true);
	}

	function createOrUpdateSMSConfig($SMSCampaignID, $configArray)
	{
		$filename = getSMSConfigFilename($SMSCampaignID);

		file_put_contents($filename, json_encode($configArray));
	}

	function deleteSMSConfig($SMSCampaignID)
	{
		$filename = getSMSConfigFilename($SMSCampaignID);
		
		if (file_exists($filename))
		{
			unlink($filename);
		}
	}

?>