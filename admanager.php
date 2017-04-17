<?php

	session_start();

?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

	<style>

		body, html
		{ 
			height:100%; 
			padding:0; 
			margin:0;
		}

		td
		{
		    white-space: nowrap;
		}		

	</style>

<?php

	require_once("include/adlib.inc");
	require_once("include/managersecurity.inc");
	require_once("include/arrayhelpers.inc");
	require_once("include/statlib.inc");
	require_once("include/csvlib.inc");
	require_once("include/shared_file_access.inc");
	require_once("admanager_security.php");

	if (array_key_exists("logout", $_GET))
	{
		logoutUser();
	}

	if (!empty($_POST['username']) && !empty($_POST['password']))
	{
		loginUser($_POST['username'], $_POST['password'], $loginHashes);
	}

	if (!userAuthenticated())
	{
?>
		<div class="container">

	      <form class="form-signin" action="admanager.php" method="POST">
	        <h2 class="form-signin-heading">Please sign in</h2>
	        <label for="username" class="sr-only">Username</label>
	        <input type="text" id="username" name="username" class="form-control" placeholder="Username" required="" autofocus="">
	        <label for="password" class="sr-only">Password</label>
	        <input type="password" id="password" name="password" class="form-control" placeholder="Password" required="">

	        <button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
	      </form>

	    </div>
<?php
		exit;
	}

	function getAdTagCode($campaignID)
	{
		$adUrl = "http" . (!empty($_SERVER['HTTPS']) ? "s" : "") . "://" . $_SERVER['SERVER_NAME'] . substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], "/") + 1) . "ad.php?$campaignID";

		$adConfig = processAdConfig(getAdConfigFilename($campaignID));

		if (array_key_exists("OutputMethod", $adConfig) && $adConfig["OutputMethod"] === "JS")
		{
			return htmlentities("<script type=\"text/javascript\" src=\"$adUrl\"></script>");
		}

		return $adUrl;
	}

	function getAllProfiles()
	{
		$files = scandir("profiles");
		$result = array();

		foreach ($files as $filename)
		{
			if (strpos($filename, ".profile") !== false)
			{
				$profileName = substr($filename, 0, strpos($filename, ".profile"));

				$result[$profileName] = $filename;
			}
		}

		return $result;
	}

	function getAllHTMLTemplates()
	{
		$files = scandir("profiles/htmltemplates");
		$result = array();

		foreach ($files as $filename)
		{
			if (strpos($filename, ".html") !== false)
			{
				$htmlTemplateName = substr($filename, 0, strpos($filename, ".html"));

				$result[$htmlTemplateName] = $htmlTemplateName;
			}
		}

		return $result;		
	}

	function getHTMLTemplateValues($htmlTemplateName)
	{
		$template = file_get_contents("profiles/htmltemplates/$htmlTemplateName.html");

		$matches = array();

		preg_match_all("/{{.*}}/", $template, $matches);

		$result = array();

		foreach ($matches[0] as $templateValue)
		{
			$result[$templateValue] = "";
		}

		return $result;
	}

	function createOrUpdateAd($campaignID, $configArray)
	{
		$configFilename  = getAdConfigFilename($campaignID);

		$configFileContents = "";

		foreach ($configArray as $key => $value)
		{
			$configFileContents .= "$key: $value\r\n";
		}

		file_put_contents($configFilename, $configFileContents);
	}	

	function createOrUpdateAdWithCleanHtml($campaignID, $cleanHtml, $configArray)
	{
		$cleanHtmlFilename = getAdCleanHtmlFilename($campaignID);
		$configFilename  = getAdConfigFilename($campaignID);

		file_put_contents($cleanHtmlFilename, $cleanHtml);

		$configFileContents = "";

		foreach ($configArray as $key => $value)
		{
			$configFileContents .= "$key: $value\r\n";
		}

		file_put_contents($configFilename, $configFileContents);
	}

	function createOrUpdateProfile($profileName, $configArray)
	{
		$configFilename  = "profiles/$profileName.profile";

		$configFileContents = "";

		foreach ($configArray as $key => $value)
		{
			$configFileContents .= "$key: $value\r\n";
		}

		file_put_contents($configFilename, $configFileContents);		
	}

	function getAdProfileFilename($profileName)
	{
		return "profiles/$profileName.profile";
	}

	function copyAd($campaignID, $newCampaignID)
	{
		$cleanHtmlFilename = getAdCleanHtmlFilename($campaignID);
		$configFilename  = getAdConfigFilename($campaignID);

		$newCleanHtmlFilename = getAdCleanHtmlFilename($newCampaignID);
		$newConfigFilename  = getAdConfigFilename($newCampaignID);

		if (!file_exists($newCleanHtmlFilename) && !file_exists($newConfigFilename))
		{
			if (file_exists($cleanHtmlFilename))
			{
				copy($cleanHtmlFilename, $newCleanHtmlFilename);
			}

			copy($configFilename, $newConfigFilename);
		}
	}

	function copyProfile($profileName, $newProfileName)
	{
		$profileFilename = getAdProfileFilename($profileName);
		$newProfileFilename = getAdProfileFilename($newProfileName);

		if (!file_exists($newProfileFilename))
		{
			copy($profileFilename, $newProfileFilename);
		}
	}

	function deleteAd($campaignID)
	{
		$cleanHtmlFilename = getAdCleanHtmlFilename($campaignID);
		$configFilename  = getAdConfigFilename($campaignID);

		if (file_exists($cleanHtmlFilename))
		{
			unlink($cleanHtmlFilename);
		}

		if (file_exists($configFilename))
		{
			unlink($configFilename);
		}
	}

	function deleteProfile($profileName)
	{
		$profileFilename = getAdProfileFilename($profileName);

		if (file_exists($profileFilename))
		{
			unlink($profileFilename);
		}
	}

	function runTest($campaignID, $queryString, $remoteAddress, $userAgent, $httpReferrer, $adConfig)
	{
		$_SERVER['REMOTE_ADDR'] 	= $remoteAddress;
		$_SERVER['HTTP_USER_AGENT'] = $userAgent;
		$_SERVER['HTTP_REFERER'] 	= $httpReferrer;

		// todo process querystring and set $_GET

		if (ob_start())
		{
			include("ad.php");

			$result = ob_get_flush();

			return $result;
		}

		return null;
	}

	if (!empty($_POST['campaignID']))
	{
		//print_r_nice($_POST);

		$configFilename = getAdConfigFilename($_POST['campaignID'], __DIR__);

		if (!file_exists($configFilename))
		{
			copy("profiles/" . $_POST['profile'], $configFilename);

			$_GET['edit'] = $_POST['campaignID'];
		}
	}	

	if (array_key_exists("edit", $_GET))
	{
		$currentAd["campaignID"] = $_GET['edit'];
		$currentAd["configArray"] = processAdConfig(getAdConfigFilename($_GET['edit']));

		if (empty($currentAd["configArray"]["HTMLTemplateValues"]) && !empty($currentAd["configArray"]["HTMLTemplate"]))
		{
			$currentAd["configArray"]["HTMLTemplateValues"] = json_encode(getHTMLTemplateValues($currentAd["configArray"]["HTMLTemplate"]));
		}

		$cleanHtmlFilename = getAdCleanHtmlFilename($_GET['edit']);

		if (file_exists($cleanHtmlFilename))
		{
			$currentAd["cleanHtml"] = file_get_contents($cleanHtmlFilename);
		}
	}
	elseif (array_key_exists("editprofile", $_GET))
	{
		$currentAd["profileName"] = $_GET['editprofile'];
		$currentAd["configArray"] = processAdConfig(getAdProfileFilename($_GET['editprofile']));
	}
	else
	{
		$currentAd = array("campaignID" 	=> "", 
				   		   "configArray" 	=> array("Method" 							=> "windowtoplocation",
				   		   							 "TrafficLoggerEnabled"				=> "false",
				   		   							 "TrackingPixelEnabled" 			=> "true", 
				   		   							 "OutputMethod" 					=> "HTML", 
				   		   							 "CanvasFingerprintCheckEnabled" 	=> "false",
				   		   							 "ConsoleLoggingEnabled"			=> "false",
				   		   							 "ISPCloakingEnabled" 				=> "true",
				   		   							 "IFrameCloakingEnabled" 			=> "true",
				   		   							 "PluginCloakingEnabled"			=> "true",
				   		   							 "TouchCloakingEnabled" 			=> "true",
				   		   							 "ForceDirtyAd"						=> "false",
				   		   							 "VoluumAdCycleCount"				=> "-1"),
													 "IFrameCookiesEnabled"				=> "false",
				   		   "cleanHtml" 		=> "<html>\n<head>\n\t{script}\n</head>\n<body{onload}>\n</body>\n</html>");
	}

	if (array_key_exists("copy", $_GET) && array_key_exists("newCampaignID", $_GET))
	{
		copyAd($_GET['copy'], $_GET['newCampaignID']);
	}

	if (array_key_exists("copyprofile", $_GET) && array_key_exists("newProfileName", $_GET))
	{
		copyProfile($_GET['copyprofile'], $_GET['newProfileName']);
	}

	if (array_key_exists("delete", $_GET))
	{
		deleteAd($_GET['delete']);
	}

	if (array_key_exists("deleteprofile", $_GET))
	{
		deleteProfile($_GET['deleteprofile']);
	}

	if ((!empty($_POST['campaignID']) || !empty($_POST['profileName'])) && !array_key_exists("edit", $_GET))
	{
		//print_r_nice($_POST);

		$configArray = array();
		$HTMLTemplateValues = array();

		foreach ($_POST as $key => $value)
		{
			if($key{0} === strtoupper($key{0}))
			{
				if (strpos($key, "HTMLTemplateValues_") !== false)
				{
					if (is_array($value))
					{
						$HTMLTemplateValues[substr($key, strlen("HTMLTemplateValues_"))] = array_values(array_filter($value));
					}
					else
					{
						$HTMLTemplateValues[substr($key, strlen("HTMLTemplateValues_"))] = $value;
					}
				}
				elseif (is_array($value))
				{
					$convertedValue = array();

					if (strpos($key, "AffiliateLinkUrl") === false)
					{
						for ($i = 0; $i < sizeof($value); $i += 2)
						{
							if (!empty($value[$i]))
							{
								$convertedValue[$value[$i]] = explode("|", $value[$i + 1]);
							}
						}
					} else {
						// We will JSON encode the value as it is
						for ($i = 0; $i < sizeof($value); $i ++)
						{
							if (!empty($value[$i]))
							{
								$convertedValue[$i] = $value[$i];
							}
						}
					}

					$configArray[$key] = json_encode($convertedValue);
				}
				else
				{
					$configArray[$key] = $value;
				}
			}
		}

		if (empty($HTMLTemplateValues) && !empty($configArray["HTMLTemplate"]))
		{
			$HTMLTemplateValues = getHTMLTemplateValues($configArray["HTMLTemplate"]);
		}

		$configArray["HTMLTemplateValues"] = json_encode($HTMLTemplateValues);

		//print_r_nice($configArray);

		if (array_key_exists("cleanHtml", $_POST))
		{
			createOrUpdateAdWithCleanHtml($_POST['campaignID'], $_POST['cleanHtml'], $configArray);
		}
		else
		{
			if (!empty($_POST['campaignID']))
			{
				createOrUpdateAd($_POST['campaignID'], $configArray);	
			}
			else
			{
				createOrUpdateProfile($_POST['profileName'], $configArray);
			}
		}
	}

?>

	<script type="text/javascript">
		$(document).ready(function() {
		    //$('#adTable').DataTable();

<?php

	echo "toastr.options.timeOut = 10;\n";
	echo "toastr.options.closeDuration = 500;\n";

	if (!empty($_POST['campaignID']) || !empty($_POST['profileName']))
	{
		echo "toastr.success('Saved');\n";
	}
	elseif (!empty($_GET['copy']) || !empty($_GET['copyProfile']))
	{
		echo "toastr.success('Copied');\n";
	}
	elseif (!empty($_GET['delete']) || !empty($_GET['deleteProfile']))
	{
		echo "toastr.success('Deleted');\n";		
	}
?>
		});
	</script>

	<title>
		Ad Manager - Adcrush Media
	</title>

</head>

<body>

	<div style="width: 95%; height: 100%; margin: 10 auto;">

<?php

	if (array_key_exists("new", $_GET))
	{ 
		$profiles = getAllProfiles();

		?>

		<form action="admanager.php" method="post" onsubmit="if ($('#campaignID').val() === '') { $('#campaignID').focus(); toastr.error('Campaign ID cannot be empty.'); return false; } return true;">

		<table class="table table-striped" id="configTable">

			<tr>
				<td class="col-xs-5">Campaign ID</td>
				<td><input type="text" name="campaignID" id="campaignID" class="form-control form-control-lg" value="<?= array_get_value_with_default($currentAd, "campaignID"); ?>" <?= array_get_value_with_default($currentAd, "campaignID", "") !== "" ? "readonly" : null; ?> /></td>
			</tr>

			<tr>
				<td>Traffic Source</td>
				<td>
					<select name="profile" class="form-control">
<?php
						foreach ($profiles as $profileName => $profileFilename)
						{
							echo "<option value=\"$profileFilename\">$profileName</option>";
						}
?>
					</select>
				</td>
			</tr>

		</table>

		<button type="submit" class="btn btn-primary">
			Save
		</button>
		<button type="button" class="btn btn-primary" onclick="window.location = 'admanager.php?<?= mt_rand(); ?>';">
			Cancel
		</button>		

		</form>

<?php
	}
	elseif (array_key_exists("edit", $_GET) || array_key_exists("newprofile", $_GET) || array_key_exists("editprofile", $_GET))
	{

		$redirectMethodOptions = array("trycatchredirect", "windowlocation", "windowtoplocation", "windowtoplocationhref", "parentwindowlocationhref", "0x0iframe", "1x1iframe");
		$outputMethodOptions = array("HTML", "JS");

?>
		<form action="admanager.php" method="post" onsubmit="return checkConfigForm();">

		<fieldset>
			<legend>General</legend>			

			<table class="table table-striped" id="configTable">

<?php
			if (array_key_exists("edit", $_GET))
			{
?>
			<tr>
				<td class="col-xs-5">Campaign ID</td>
				<td><input type="text" name="campaignID" id="campaignID" class="form-control form-control-lg" value="<?= array_get_value_with_default($currentAd, "campaignID"); ?>" <?= array_get_value_with_default($currentAd, "campaignID", "") !== "" ? "readonly" : null; ?> /></td>
			</tr>
<?php
			}
			else
			{
?>
			<tr>
				<td class="col-xs-5">Profile Name</td>
				<td><input type="text" name="profileName" id="profileName" class="form-control form-control-lg" value="<?= array_get_value_with_default($currentAd, "profileName"); ?>" <?= array_get_value_with_default($currentAd, "profileName", "") !== "" ? "readonly" : null; ?> /></td>
			</tr>
<?php				
			}
?>
			<tr>
				<td>Ad Country Code</td>
				<td><input type="text" name="CountryCode" id="CountryCode" class="form-control form-control-lg" value="<?= array_get_value_with_default($currentAd["configArray"], "CountryCode", "US"); ?>" /></td>
			</tr>

			<tr>
				<td>Allowed ISPs (pipe | separated)</td>
				<td><input type="text" name="AllowedISPS" id="AllowedISPS" class="form-control form-control-lg" value="<?= array_get_value_with_default($currentAd["configArray"], "AllowedISPS", ""); ?>" /></td>
			</tr>			

			<tr>
				<td>Traffic Logger Enabled</td>
				<td>
					<input type="hidden" name="TrafficLoggerEnabled" value="false" />
					<input class="form-check-input" type="checkbox" name="TrafficLoggerEnabled" value="true" <?= (array_get_bool($currentAd["configArray"], "TrafficLoggerEnabled") ? "checked=checked" : null); ?> />
				</td>
			</tr>			

			</table>
		</fieldset>

		<fieldset>
			<legend>Redirect</legend>

			<table class="table table-striped" id="configTable">

			<tr>
				<td class="col-xs-5">Redirect URL</td>
				<td><input type="text" name="RedirectUrl" id="RedirectUrl" class="form-control form-control-lg" value="<?= array_get_value_with_default($currentAd["configArray"], "RedirectUrl"); ?>" /></td>
			</tr>

			<tr>
				<td>Redirect Method</td>
				<td>
					<select name="Method" class="form-control">

<?php
					foreach ($redirectMethodOptions as $option)
					{
						if (array_get_value_with_default($currentAd["configArray"], "Method") == $option)
						{
							echo "<option value=\"$option\" selected=\"selected\">$option</option>\n";
						}
						else
						{
							echo "<option value=\"$option\">$option</option>\n";
						}
					}
?>
					</select>
				</td>
			</tr>

			<tr>
				<td>Redirect Submethod 1</td>
				<td>
					<select name="RedirectSubMethod1" class="form-control">

<?php
					foreach ($redirectMethodOptions as $option)
					{
						if (array_get_value_with_default($currentAd["configArray"], "RedirectSubMethod1") == $option)
						{
							echo "<option value=\"$option\" selected=\"selected\">$option</option>\n";
						}
						else
						{
							echo "<option value=\"$option\">$option</option>\n";
						}
					}
?>
					</select>
				</td>
			</tr>

			<tr>
				<td>Redirect Submethod 2</td>
				<td>
					<select name="RedirectSubMethod2" class="form-control">

<?php
					foreach ($redirectMethodOptions as $option)
					{
						if (array_get_value_with_default($currentAd["configArray"], "RedirectSubMethod2") == $option)
						{
							echo "<option value=\"$option\" selected=\"selected\">$option</option>\n";
						}
						else
						{
							echo "<option value=\"$option\">$option</option>\n";
						}
					}
?>
					</select>
				</td>
			</tr>

			<tr>
				<td>Redirect Timeout (ms.)</td>
				<td><input type="text" name="RedirectTimeout" id="RedirectTimeout" class="form-control form-control-lg" value="<?= array_get_value_with_default($currentAd["configArray"], "RedirectTimeout", "3000"); ?>" /></td>
			</tr>

			<tr>
				<td>Redirect Enabled</td>
				<td>
					<input type="hidden" name="RedirectEnabled" value="false" />
					<input class="form-check-input" type="checkbox" name="RedirectEnabled" value="true" <?= (array_get_bool($currentAd["configArray"], "RedirectEnabled") ? "checked=checked" : null); ?> />
				</td>
			</tr>

			<tr>
				<td>Voluum Ad Cycle Count (-1 to disable)</td>
				<td><input type="text" name="VoluumAdCycleCount" id="VoluumAdCycleCount" class="form-control form-control-lg" value="<?= array_get_value_with_default($currentAd["configArray"], "VoluumAdCycleCount", "-1"); ?>" /></td>
			</tr>			

			</table>

		</fieldset>

		<fieldset>
			<legend>Tracking Pixel</legend>

			<table class="table table-striped" id="configTable">

			<tr>
				<td class="col-xs-5">Tracking Pixel URL</td>
				<td><input type="text" name="TrackingPixelUrl" id="TrackingPixelUrl" class="form-control form-control-lg" value="<?= array_get_value_with_default($currentAd["configArray"], "TrackingPixelUrl"); ?>" /></td>
			</tr>

			<tr>
				<td>Tracking Pixel Enabled</td>
				<td>
					<input type="hidden" name="TrackingPixelEnabled" value="false" />
					<input class="form-check-input" type="checkbox" name="TrackingPixelEnabled" value="true" <?= (array_get_bool($currentAd["configArray"], "TrackingPixelEnabled") ? "checked=checked" : null); ?> />
				</td>
			</tr>			

			<tr>
				<td>Output Method</td>
				<td>
					<select name="OutputMethod" class="form-control">
<?php
					foreach ($outputMethodOptions as $option)
					{
						if (array_get_value_with_default($currentAd["configArray"], "OutputMethod") == $option)
						{
							echo "<option value=\"$option\" selected=\"selected\">$option</option>\n";
						}
						else
						{
							echo "<option value=\"$option\">$option</option>\n";
						}
					}
?>
					</select>
				</td>
			</tr>

			</table>
		</fieldset>

		<fieldset>
			<legend>Cloaking</legend>

			<table class="table table-striped" id="configTable">

			<tr>
				<td class="col-xs-5">Referrer Blacklist (pipe | separated)</td>
				<td><input type="text" name="BlacklistedReferrers" class="form-control form-control-lg" value="<?= array_get_value_with_default($currentAd["configArray"], "BlacklistedReferrers"); ?>" /></td>
			</tr>

			<tr>
				<td>Referrer Whitelist (pipe | separated)</td>
				<td><input type="text" name="WhitelistedReferrers" class="form-control form-control-lg" value="<?= array_get_value_with_default($currentAd["configArray"], "WhitelistedReferrers"); ?>" /></td>
			</tr>			

			<tr>
				<td>Canvas Fingerprint Check Enabled</td>
				<td>
					<input type="hidden" name="CanvasFingerprintCheckEnabled" value="false" />
					<input class="form-check-input" type="checkbox" name="CanvasFingerprintCheckEnabled" value="true" <?= (array_get_bool($currentAd["configArray"], "CanvasFingerprintCheckEnabled") ? "checked=checked" : null); ?> />
				</td>
			</tr>

			<tr>
				<td>Blocked Canvas Fingerprints (comma separated)</td>
				<td><input type="text" name="BlockedCanvasFingerprints" class="form-control form-control-lg" value="<?= array_get_value_with_default($currentAd["configArray"], "BlockedCanvasFingerprints"); ?>" /></td>
			</tr>

			<tr>
				<td>Province / State Blacklist (pipe | separated)</td>
				<td><input type="text" name="ProvinceBlackList" class="form-control form-control-lg" value="<?= array_get_value_with_default($currentAd["configArray"], "ProvinceBlackList"); ?>" /></td>
			</tr>

			<tr>
				<td>City Blacklist (pipe | separated)</td>
				<td><input type="text" name="CityBlackList" class="form-control form-control-lg" value="<?= array_get_value_with_default($currentAd["configArray"], "CityBlackList"); ?>" /></td>
			</tr>

			</table>

		</fieldset>

		<fieldset>
			<legend>Blocked Parameter Values</legend>

			<table class="table table-striped" id="configTable">

			<tr>
				<td class="col-xs-5"><input type="text" name="BlockedParameterValues[]" class="form-control form-control-lg" placeholder="Parameter 1" value="<?= array_get_json_key_at_index_with_default($currentAd["configArray"], "BlockedParameterValues", 0); ?>" /></td>
				<td><input type="text" name="BlockedParameterValues[]" class="form-control form-control-lg" placeholder="Blocked Values (pipe | separated)" value="<?= array_get_json_value_at_index_with_default($currentAd["configArray"], "BlockedParameterValues", 0); ?>" /></td>
			</tr>

			<tr>
				<td><input type="text" name="BlockedParameterValues[]" class="form-control form-control-lg" placeholder="Parameter 2" value="<?= array_get_json_key_at_index_with_default($currentAd["configArray"], "BlockedParameterValues", 1); ?>" /></td>
				<td><input type="text" name="BlockedParameterValues[]" class="form-control form-control-lg" placeholder="Blocked Values (pipe | separated)" value="<?= array_get_json_value_at_index_with_default($currentAd["configArray"], "BlockedParameterValues", 1); ?>" /></td>
			</tr>			

			<tr>
				<td><input type="text" name="BlockedParameterValues[]" class="form-control form-control-lg" placeholder="Parameter 3" value="<?= array_get_json_key_at_index_with_default($currentAd["configArray"], "BlockedParameterValues", 2); ?>" /></td>
				<td><input type="text" name="BlockedParameterValues[]" class="form-control form-control-lg" placeholder="Blocked Values (pipe | separated)" value="<?= array_get_json_value_at_index_with_default($currentAd["configArray"], "BlockedParameterValues", 2); ?>" /></td>
			</tr>			

			<tr>
				<td><input type="text" name="BlockedParameterValues[]" class="form-control form-control-lg" placeholder="Parameter 4" value="<?= array_get_json_key_at_index_with_default($currentAd["configArray"], "BlockedParameterValues", 3); ?>" /></td>
				<td><input type="text" name="BlockedParameterValues[]" class="form-control form-control-lg" placeholder="Blocked Values (pipe | separated)" value="<?= array_get_json_value_at_index_with_default($currentAd["configArray"], "BlockedParameterValues", 3); ?>" /></td>
			</tr>			

			<tr>
				<td><input type="text" name="BlockedParameterValues[]" class="form-control form-control-lg" placeholder="Parameter 5" value="<?= array_get_json_key_at_index_with_default($currentAd["configArray"], "BlockedParameterValues", 4); ?>" /></td>
				<td><input type="text" name="BlockedParameterValues[]" class="form-control form-control-lg" placeholder="Blocked Values (pipe | separated)" value="<?= array_get_json_value_at_index_with_default($currentAd["configArray"], "BlockedParameterValues", 4); ?>" /></td>
			</tr>			

			</table>

		</fieldset>

		<fieldset>
			<legend>Blocked Referrer Parameter Values</legend>

			<table class="table table-striped" id="configTable">

			<tr>
				<td class="col-xs-5"><input type="text" name="BlockedReferrerParameterValues[]" class="form-control form-control-lg" placeholder="Parameter 1" value="<?= array_get_json_key_at_index_with_default($currentAd["configArray"], "BlockedReferrerParameterValues", 0); ?>" /></td>
				<td><input type="text" name="BlockedReferrerParameterValues[]" class="form-control form-control-lg" placeholder="Blocked Values (pipe | separated)" value="<?= array_get_json_value_at_index_with_default($currentAd["configArray"], "BlockedReferrerParameterValues", 0); ?>" /></td>
			</tr>

			<tr>
				<td><input type="text" name="BlockedReferrerParameterValues[]" class="form-control form-control-lg" placeholder="Parameter 2" value="<?= array_get_json_key_at_index_with_default($currentAd["configArray"], "BlockedReferrerParameterValues", 1); ?>" /></td>
				<td><input type="text" name="BlockedReferrerParameterValues[]" class="form-control form-control-lg" placeholder="Blocked Values (pipe | separated)" value="<?= array_get_json_value_at_index_with_default($currentAd["configArray"], "BlockedReferrerParameterValues", 1); ?>" /></td>
			</tr>			

			<tr>
				<td><input type="text" name="BlockedReferrerParameterValues[]" class="form-control form-control-lg" placeholder="Parameter 3" value="<?= array_get_json_key_at_index_with_default($currentAd["configArray"], "BlockedReferrerParameterValues", 2); ?>" /></td>
				<td><input type="text" name="BlockedReferrerParameterValues[]" class="form-control form-control-lg" placeholder="Blocked Values (pipe | separated)" value="<?= array_get_json_value_at_index_with_default($currentAd["configArray"], "BlockedReferrerParameterValues", 2); ?>" /></td>
			</tr>			

			<tr>
				<td><input type="text" name="BlockedReferrerParameterValues[]" class="form-control form-control-lg" placeholder="Parameter 4" value="<?= array_get_json_key_at_index_with_default($currentAd["configArray"], "BlockedReferrerParameterValues", 3); ?>" /></td>
				<td><input type="text" name="BlockedReferrerParameterValues[]" class="form-control form-control-lg" placeholder="Blocked Values (pipe | separated)" value="<?= array_get_json_value_at_index_with_default($currentAd["configArray"], "BlockedReferrerParameterValues", 3); ?>" /></td>
			</tr>			

			<tr>
				<td><input type="text" name="BlockedReferrerParameterValues[]" class="form-control form-control-lg" placeholder="Parameter 5" value="<?= array_get_json_key_at_index_with_default($currentAd["configArray"], "BlockedReferrerParameterValues", 4); ?>" /></td>
				<td><input type="text" name="BlockedReferrerParameterValues[]" class="form-control form-control-lg" placeholder="Blocked Values (pipe | separated)" value="<?= array_get_json_value_at_index_with_default($currentAd["configArray"], "BlockedReferrerParameterValues", 4); ?>" /></td>
			</tr>			

			</table>

		</fieldset>		

		<fieldset>
			<legend>Debugging</legend>

			<table class="table table-striped" id="configTable">		

			<tr>
				<td class="col-xs-5">Logging Enabled (Server side)</td>
				<td>
					<input type="hidden" name="LoggingEnabled" value="false" />
					<input class="form-check-input" type="checkbox" name="LoggingEnabled" value="true" <?= (array_get_bool($currentAd["configArray"], "LoggingEnabled") ? "checked=checked" : null); ?> />
				</td>
			</tr>

			<tr>
				<td class="col-xs-5">Console Logging Enabled (Client side)</td>
				<td>
					<input type="hidden" name="ConsoleLoggingEnabled" value="false" />
					<input class="form-check-input" type="checkbox" name="ConsoleLoggingEnabled" value="true" <?= (array_get_bool($currentAd["configArray"], "ConsoleLoggingEnabled") ? "checked=checked" : null); ?> />
				</td>
			</tr>			

			<tr>
				<td>ISP Cloaking Enabled</td>
				<td>
					<input type="hidden" name="ISPCloakingEnabled" value="false" />
					<input class="form-check-input" type="checkbox" name="ISPCloakingEnabled" value="true" <?= (array_get_bool($currentAd["configArray"], "ISPCloakingEnabled") ? "checked=checked" : null); ?> />
				</td>
			</tr>

			<tr>
				<td>IFrame Cloaking Enabled</td>
				<td>
					<input type="hidden" name="IFrameCloakingEnabled" value="false" />
					<input class="form-check-input" type="checkbox" name="IFrameCloakingEnabled" value="true" <?= (array_get_bool($currentAd["configArray"], "IFrameCloakingEnabled") ? "checked=checked" : null); ?> />
				</td>
			</tr>

			<tr>
				<td>Plugin Cloaking Enabled</td>
				<td>
					<input type="hidden" name="PluginCloakingEnabled" value="false" />
					<input class="form-check-input" type="checkbox" name="PluginCloakingEnabled" value="true" <?= (array_get_bool($currentAd["configArray"], "PluginCloakingEnabled") ? "checked=checked" : null); ?> />
				</td>
			</tr>			

			<tr>
				<td>Touch Cloaking Enabled</td>
				<td>
					<input type="hidden" name="TouchCloakingEnabled" value="false" />
					<input class="form-check-input" type="checkbox" name="TouchCloakingEnabled" value="true" <?= (array_get_bool($currentAd["configArray"], "TouchCloakingEnabled") ? "checked=checked" : null); ?> />
				</td>
			</tr>

			<tr>
				<td>Force Dirty Ad (Server side)</td>
				<td>
					<input type="hidden" name="ForceDirtyAd" value="false" />
					<input class="form-check-input" type="checkbox" name="ForceDirtyAd" value="true" <?= (array_get_bool($currentAd["configArray"], "ForceDirtyAd") ? "checked=checked" : null); ?> />
				</td>
			</tr>			

			</table>

		</fieldset>

		<fieldset>
			<legend>Cookies</legend>
			<table class="table table-striped" id="cookiesTable">		

			<tr>
				<td class="col-xs-5">Enable cookie dropping using an iframe</td>
				<td>
					<input type="hidden" name="IFrameCookiesEnabled" value="false" />
					<input class="form-check-input" type="checkbox" name="IFrameCookiesEnabled" value="true" <?= (array_get_bool($currentAd["configArray"], "IFrameCookiesEnabled") ? "checked=checked" : null); ?> />
				</td>
			</tr>
			<?php 
			//print_r_nice($currentAd["configArray"]);
			if (array_key_exists("AffiliateLinkUrl",$currentAd["configArray"]))
				$decoded = json_decode($currentAd["configArray"]["AffiliateLinkUrl"]);
			else
				$decoded = array();
			for ($n = 1; $n <= 10; $n++) {
				
			?>
			<tr>
				<td class="col-xs-5">Affiliate URL # <?= $n ?></td>
				<td><input type="text" name="AffiliateLinkUrl[]" class="form-control form-control-lg" placeholder="Affiliate URL # <?= $n ?>" value="<?= array_get_value_with_default($decoded, ($n-1), ""); ?>" /></td>
			</tr>
<?php 
			}
?>
			</table>
		</fieldset>
		
		<fieldset>
			<legend>HTML</legend>

			<table class="table table-striped" id="configTable">

<?php

		if (array_key_exists("newprofile", $_GET) || array_key_exists("editprofile", $_GET))
		{
			$htmlTemplates = getAllHTMLTemplates();
?>
			<tr>
				<td>HTML Template</td>
				<td>
					<select name="HTMLTemplate" class="form-control">
<?php
					foreach ($htmlTemplates as $htmlTemplateName)
					{
						if (array_get_value_with_default($currentAd["configArray"], "HTMLTemplate") == $option)
						{
							echo "<option value=\"$htmlTemplateName\" selected=\"selected\">$htmlTemplateName</option>\n";
						}
						else
						{
							echo "<option value=\"$htmlTemplateName\">$htmlTemplateName</option>\n";
						}
					}
?>
					</select>
				</td>
			</tr>			
<?php
		}
		elseif (array_key_exists("HTMLTemplate", $currentAd["configArray"]))
		{
?>
			<input type="hidden" name="HTMLTemplate" value="<?= $currentAd["configArray"]["HTMLTemplate"] ?>" />
<?php
			$HTMLTemplateValues = json_decode($currentAd["configArray"]["HTMLTemplateValues"]);

			foreach ($HTMLTemplateValues as $parameter => $parameterValue)
			{
?>
				<tr>
					<td class="col-xs-5"><?= $parameter ?></td>
					<td>
<?php
				if (is_array($parameterValue) || strpos($parameter, "()") !== false)
				{
					for ($i = 0; $i < 5; $i++)
					{
						$value = $i < sizeof($parameterValue) && is_array($parameterValue) ? $parameterValue[$i] : "";
?>
						<input type="text" name="HTMLTemplateValues_<?= $parameter ?>[]" class="form-control form-control-lg" value="<?= $value ?>" /><br/>
<?php
					}
				}
				else
				{
?>
					<input type="text" name="HTMLTemplateValues_<?= $parameter ?>" class="form-control form-control-lg" value="<?= $parameterValue ?>" />
<?php
				}
?>						
					</td>
				</tr>
<?php
			}
		}
		else
		{
?>

			<tr>
				<td colspan="2">Clean HTML code. Use placeholders {script}, {onload} and {queryString}.</td>
			</tr>

			<tr>
				<td colspan="2"><textarea style="width: 100%" rows="20" class="form-check-input" id="cleanHtml" name="cleanHtml"><?= array_get_value_with_default($currentAd, "cleanHtml"); ?></textarea></td>
			</tr>

<?php
		}
?>

			</table>

		</fieldset>

		<button type="submit" class="btn btn-primary">
			Save
		</button>
		<button type="button" class="btn btn-primary" onclick="window.location = 'admanager.php?<?= mt_rand(); ?>';">
			Cancel
		</button>

		</form>

		<script type="text/javascript">

			function validUrl(url)
			{
				return url === '' || url.indexOf('http') === 0;
			}

			function isInt(value)
			{
				return value == parseInt(value);
			}

			function checkConfigForm()
			{
				if (typeof $('#campaignID').val() != 'undefined' && $('#campaignID').val().trim() === '')
				{
					$('#campaignID').focus();
						
					toastr.error('Campaign ID cannot be empty.');

					return false;
				}

				if (typeof $('#profileName').val() != 'undefined' && $('#profileName').val().trim() === '')
				{
					$('#profileName').focus();
						
					toastr.error('Profile name cannot be empty.');

					return false;
				}

				if ($('#CountryCode').val().trim() === '')
				{
					$('#CountryCode').focus();
						
					toastr.error('Ad Country Code cannot be empty.');

					return false;					
				}

				if (typeof $('#cleanHtml').val() != 'undefined' && $('#cleanHtml').val().trim() === '')
				{
					$('#cleanHtml').focus();
						
					toastr.error('Clean HTML cannot be empty.');

					return false;					
				}

				if ($('#RedirectUrl').val().trim() === '' && !confirm('Redirect URL is empty, continue anyway?'))
				{
					$('#RedirectUrl').focus();

					return false;
				}

				if (!validUrl($('#RedirectUrl').val()))
				{
					$('#RedirectUrl').focus();

					toastr.error('Invalid Redirect URL.');

					return false;
				}

				if (!isInt($('#RedirectTimeout').val()))
				{
					$('#RedirectTimeout').focus();

					toastr.error('Redirect Timeout must be a whole number (int).');

					return false;
				}

				if (!validUrl($('#TrackingPixelUrl').val()))
				{
					$('#TrackingPixelUrl').focus();

					toastr.error('Invalid Tracking Pixel URL.');

					return false;
				}				

				if ($('#cleanHtml').val().indexOf('{script}') === -1 && !confirm('Clean HTML code does not contain {script} tag, continue anyway?'))
				{
					$('#cleanHtml').focus();

					return false;
				}
				
				return true;
			}

		</script>

<?php 
	}
	elseif (array_key_exists("test", $_GET))
	{
		echo "output test form";
	}
	elseif (array_key_exists("runtest", $_GET))
	{
		echo "run test";
	}
	elseif (array_key_exists("viewlog", $_GET))
	{
		echo renderAdStats($_GET['viewlog'], getAdStats($_GET['viewlog'], __DIR__));

		$logFilenames = getAdLogFilenames(__DIR__, $_GET['viewlog']);

		$filesExist = 0;

		foreach ($logFilenames as $logFilename)
		{
			$fileContents = file_get_contents_shared_access($logFilename);			
			if ($fileContents !== false)
			{
				// Parse the CSV file 
				$parsed = parse_csv($fileContents,",");
				
				echo "<strong>$logFilename</strong><br/>";
				echo "<div style=\"overflow: scroll; width: 100%; height: 33%;\">\n";
				echo "<table class=\"table table-bordered table-striped\">\n";

				$firstRow = true;
				foreach ($parsed as $row) {
					echo "<tr>\n\t";
					foreach($row as $field) {
						if ($firstRow)
							echo "<th>".$field."</th>";
						else
							echo "<td>".$field."</td>";
					}
					echo "</tr>\n";
					$firstRow = false;
				}

				echo "</table>\n";
				echo "</div>\n";
				echo "<br/>";

				$filesExist++;
			}
		} 

		if ($filesExist == 0)
		{
			echo "No log files found.<br/>";
		}
?>

		<button type="button" class="btn btn-primary" onclick="window.location = 'admanager.php?<?= mt_rand(); ?>';">
			Back
		</button>

		<br/><br/>

<?php
	}
	else
	{
?>

		<div style="float: right;">
			<button type="button" class="btn btn-danger" onclick="window.location = 'admanager.php?logout&<?= mt_rand(); ?>';">
				Logout
			</button>
		</div>

		<ul class="nav nav-tabs" id="navtab-container">
		  <li class="active"><a data-toggle="tab" href="#ads">Ads</a></li>
		  <li><a data-toggle="tab" href="#profiles">Profiles</a></li>
		  <li><a data-toggle="tab" href="#tagtemplates">Tag Templates</a></li>
		</ul>

		<div class="tab-content">
		  <div id="ads" class="tab-pane fade in active">

		  	<br/>

			<div>
				<div style="float: right;">
					<button type="button" class="btn btn-primary" onclick="window.location = 'admanager.php?new';">
						New AD
					</button>
				</div>
			</div>

			<br/><br/>

			<table class="table table-striped" id="adTable">

				<thead>
					<tr>
						<th class="col-xs-3">Campaign ID</th>
						<th>Tag</th>
						<th style="width: 50px;"></th>
						<th style="width: 50px;"></th>
						<th style="width: 50px;"></th>
						<th style="width: 50px;"></th>
						<th style="width: 50px;"></th>
					</tr>
				</thead>

				<tbody>
			<?php

				$ads = getAllAds(__DIR__);

				foreach ($ads as $campaignID => $filenames)
				{
					$adTagCode = getAdTagCode($campaignID);

					echo "<tr>\n";
					echo "<td><a href=\"admanager.php?edit=$campaignID&" . mt_rand() . "\" alt=\"Edit\" title=\"Edit\">$campaignID</a></td>\n";
					echo "<td><input class=\"form-control form-control-lg\" type=\"text\" value=\"$adTagCode\" onclick=\"this.select(); document.execCommand('copy'); toastr.success('Link \'$adTagCode\' copied to clipboard.');\" /></td>\n";

					if (strpos($adTagCode, "javascript") === false)
					{
						echo "<td><a href=\"$adTagCode\" alt=\"View\" title=\"View\" target=\"_blank\"><span class=\"glyphicon glyphicon-search\" aria-hidden=\"true\"></span></a></td>\n";
					}
					else
					{
						echo "<td></td>\n";
					}

					//echo "<td><a href=\"admanager.php?test=$campaignID\" alt=\"Test\" title=\"Test\"><span class=\"glyphicon glyphicon-play\" aria-hidden=\"true\"></span></a></td>\n";
					//echo "<td><a href=\"admanager.php?viewlog=$campaignID\" alt=\"Logs\" title=\"Logs\" data-toggle=\"modal\" data-target=\"#myModal\"><span class=\"glyphicon glyphicon-list-alt\" aria-hidden=\"true\" onclick=\"$('.modal-body').load('admanager.php?viewlog=$campaignID');\"></span></a></td>\n";

					echo "<td><a href=\"admanager.php?copy=$campaignID\" alt=\"Copy\" title=\"Copy\" onclick=\"var newCampaignID = prompt('Please enter the id of the copied campaign'); if (newCampaignID == null || newCampaignID === '') { return false; } $(this).attr('href', $(this).attr('href') + '&newCampaignID=' + newCampaignID);\"><span class=\"glyphicon glyphicon-copy\" aria-hidden=\"true\"></span></a></td>\n";
					echo "<td><a href=\"admanager.php?viewlog=$campaignID\" alt=\"Logs and Stats\" title=\"Logs and Stats\"><span class=\"glyphicon glyphicon-list-alt\" aria-hidden=\"true\"></span></a></td>\n";
					echo "<td><a href=\"admanager.php?delete=$campaignID\" alt=\"Delete\" title=\"Delete\" onclick=\"return confirm('Are you sure you want to delete ad with campaignID \'$campaignID\'?');\"><span class=\"glyphicon glyphicon-trash\" aria-hidden=\"true\"></span></a></td>\n";
					echo "</tr>\n";
				}

			?>

				</tbody>
			</table>

			<!-- Modal -->
			<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			  <div class="modal-dialog" role="document">
			    <div class="modal-content">
			      <div class="modal-header">
			        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
			          <span aria-hidden="true">&times;</span>
			        </button>
			        <h4 class="modal-title" id="myModalLabel">Ad logs</h4>
			      </div>
			      <div class="modal-body">
			        If you see this message, please disable your ad blocker.
			      </div>
			    </div>
			  </div>
			</div>

		</div>
		<div id="profiles" class="tab-pane fade">
			<br/>

			<div>
				<div style="float: right;">
					<button type="button" class="btn btn-primary" onclick="window.location = 'admanager.php?newprofile';">
						New Profile
					</button>
				</div>

			</div>

			<br/><br/>

			<table class="table table-striped" id="adTable">

				<thead>
					<tr>
						<th class="col-xs-3">Profile Name</th>
						<th style="width: 50px;"></th>
						<th style="width: 50px;"></th>
					</tr>
				</thead>

				<tbody>
			<?php

				$profiles = getAllProfiles();

				foreach ($profiles as $profileName => $profileFilename)
				{
					echo "<tr>\n";
					echo "<td><a href=\"admanager.php?editprofile=$profileName&" . mt_rand() . "\" alt=\"Edit\" title=\"Edit\">$profileName</a></td>\n";
					echo "<td><a href=\"admanager.php?copyprofile=$profileName\" alt=\"Copy\" title=\"Copy\" onclick=\"var newProfileName = prompt('Please enter the name of the copied profile'); if (newProfileName == null || newProfileName === '') { return false; } $(this).attr('href', $(this).attr('href') + '&newProfileName=' + newProfileName);\"><span class=\"glyphicon glyphicon-copy\" aria-hidden=\"true\"></span></a></td>\n";
					echo "<td><a href=\"admanager.php?deleteprofile=$profileName\" alt=\"Delete\" title=\"Delete\" onclick=\"return confirm('Are you sure you want to delete ad with name \'$profileName\'?');\"><span class=\"glyphicon glyphicon-trash\" aria-hidden=\"true\"></span></a></td>\n";
					echo "</tr>\n";
				}
			?>

				</tbody>
			</table>
		</div>
		
		<div id="tagtemplates" class="tab-pane fade">
			<br/>

			<div>
				<div style="float: right;">
					<button type="button" class="btn btn-primary" onclick="window.location = 'admanager.php?newtagtemplate';">
						New Tag Template
					</button>
				</div>

			</div>

			<br/><br/>

			<table class="table table-striped" id="tagTemplateTable">

				<thead>
					<tr>
						<th class="col-xs-3">Tag Template Name</th>
						<th style="width: 50px;"></th>
						<th style="width: 50px;"></th>
					</tr>
				</thead>

				<tbody>
				</tbody>
			</table>
		</div>
	</div>

	<script type="text/javascript">

		$('a[data-toggle="tab"]').on('show.bs.tab', function(e) {
		    localStorage.setItem('activeTab', $(e.target).attr('href'));
		});

		var activeTab = localStorage.getItem('activeTab');

		if (activeTab)
		{
		   $('#navtab-container a[href="' + activeTab + '"]').tab('show');
		}

	</script>

<?php
	}
?>

		<div>
			<small>&copy; <?= date("Y") ?> Adcrush Media</small>
		</div>

	</div>

</body>
</html>