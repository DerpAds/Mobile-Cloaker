<?php

	require_once("shared_file_access.inc");
	require_once("include/adlib.inc");
	require_once("include/statlib.inc");

	$ads = getAllAds(__DIR__);

	$statsPerCampaign = array();

	foreach ($ads as $campaignID => $filenames)
	{
		$statsPerCampaign[$campaignID] = getAdStats($campaignID,__DIR__);
	}

	foreach ($statsPerCampaign as $campaignID => $stats)
	{
		echo renderAdStats($campaignID, $stats);
	}

?>