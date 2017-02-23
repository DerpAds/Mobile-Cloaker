<?php

	$currentDirectory = __DIR__ . "/";

	if (file_exists($currentDirectory . "include/apilib.inc"))
	{
		require_once($currentDirectory . "include/apilib.inc");
	}
	else
	{
		die("Cannot find include file.");
	}

	if (file_exists($currentDirectory . "include/adlib.inc"))
	{
		require_once($currentDirectory . "include/adlib.inc");
	}
	else
	{
		die("Cannot find include file.");
	}

	if (file_exists($currentDirectory . "include/databasehelpers.inc"))
	{
		require_once($currentDirectory . "include/databasehelpers.inc");
	}
	else
	{
		die("Cannot find include file.");
	}

	if (file_exists($currentDirectory . "include/voluum.inc"))
	{
		require_once($currentDirectory . "include/voluum.inc");
	}
	else
	{
		die("Cannot find include file.");
	}

	connectDatabase();

	$ads = getAllAds(__DIR__);

	$voluumCampaignIDs = array();

	foreach ($ads as $ad)
	{
		$adConfig = $ad["config"];

		if (array_key_exists("VoluumCampaignID", $adConfig) && !empty($adConfig["VoluumCampaignID"]))
		{
			$voluumCampaignIDs[] = $adConfig["VoluumCampaignID"];

			$voluumAccountResult = $mysqli->query("SELECT b.* FROM voluumcampaigns AS a LEFT JOIN voluumaccounts AS b ON (a.voluumaccountid = b.voluumaccountid) WHERE a.voluumcampaignid = '$adConfig[VoluumCampaignID]'");
			$voluumAccountInfo = $voluumAccountResult->fetch_assoc();

			$voluumAuthenticationResult = getVoluumAuthenticationResult($voluumAccountInfo["voluumusername"], $voluumAccountInfo["voluumpassword"]);

			$conversions = getVoluumConversionsForToday($voluumAuthenticationResult, $adConfig["VoluumCampaignID"]);

			foreach ($conversions["rows"] as $conversionRow)
			{
				$voluumClickID = $conversionRow["clickId"];
				$ccid = $conversionRow["customVariable$adConfig[VoluumCustomVarCcidIndex]"];
				$campaignID = $adConfig["VoluumCampaignID"];
				$adindex = $conversionRow["customVariable$adConfig[VoluumCustomVarAdIndex]"];

				$stmt = $mysqli->prepare("SELECT * FROM voluumconversions WHERE campaignid = ? AND ccid = ?");
				$stmt->bind_param("ss", $campaignID, $ccid);
		    	$stmt->execute();

		  		$result = $stmt->get_result();

				if (!$result->num_rows)				
				{
					$stmt = $mysqli->prepare("INSERT INTO voluumconversions (voluumclickid, voluumcampaignid, ccid, campaignid, adindex) VALUES (?, ?, ?, ?)");
					$stmt->bind_param("ssss", $voluumClickID, $adConfig["VoluumCampaignID"], $ccid, $campaignID, $adindex);
					$stmt->execute();

					echo "inserting<br/>";
				}
			}
		}
	}

	$voluumCampaignIDs = implode(",", $voluumCampaignIDs);

	$stmt = $mysqli->prepare("DELETE FROM voluumcampaigns WHERE voluumcampaignid NOT IN (?)");
	$stmt->bind_param("s", $voluumCampaignIDs);
	$stmt->execute();	

?>