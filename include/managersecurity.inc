<?php

	function userAuthenticated()
	{
		return array_key_exists("voluumaccountid", $_SESSION) && !empty($_SESSION["voluumaccountid"]);
	}

	function loginUser($voluumusername, $voluumpassword)
	{
		global $mysqli;

		$voluumAuthenticationResult = getVoluumAuthenticationResult($voluumusername, $voluumpassword);

	    if (getVoluumAuthenticationSuccess($voluumAuthenticationResult))
	    {
	    	$stmt = $mysqli->prepare("SELECT * FROM voluumaccounts WHERE voluumusername=?");
	    	$stmt->bind_param("s", $voluumusername);
	    	$stmt->execute();

	  		$result = $stmt->get_result();

			if ($result->num_rows)
			{
				$row = $result->fetch_assoc();

				$voluumaccountid = $row["voluumaccountid"];
				$fullname = $row["fullname"];
			}
			else
			{
				// create account
				$fullname = $voluumAuthenticationResult["data"]["defaultClient"]["name"];

		    	$stmt = $mysqli->prepare("INSERT INTO voluumaccounts (voluumusername, voluumpassword, fullname) VALUES (?, ?, ?)");
		    	$stmt->bind_param("sss", $voluumusername, $voluumpassword, $fullname);
		    	$stmt->execute();

		    	$voluumaccountid = $stmt->insert_id;
			}

			$_SESSION["voluumaccountid"] 			= $voluumaccountid;
			$_SESSION["fullname"]					= $fullname;
			$_SESSION["voluumusername"] 			= $voluumusername;
			$_SESSION["voluumpassword"] 			= $voluumpassword;
			$_SESSION["voluumAuthenticationResult"] = $voluumAuthenticationResult;
			$_SESSION["voluumCampaigns"] 			= getVoluumCampaignsAssoc(getVoluumCampaignsForToday($_SESSION["voluumAuthenticationResult"]));
	    }
	}

	function logoutUser()
	{
		session_unset(); 
	}

?>