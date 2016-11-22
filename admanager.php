<?php

	function print_r_nice($mixed)
	{
		echo str_replace("\n", "<br/>", print_r($mixed, true));
	}

	function getCleanHtmlFilename($campaignID)
	{
		return "ads/" . $campaignID . ".cleanad.html";
	}

	function getConfigFilename($campaignID)
	{
		return "ads/" . $campaignID . ".config.txt";
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

	function createAd($campaignID, $cleanHtml, $configArray)
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

	if (array_key_exists("view", $_GET))
	{
		
	}

	if (array_key_exists("edit", $_GET))
	{
		
	}

	if (array_key_exists("delete", $_GET))
	{

	}

?>

<html>
<head>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

	<script type="text/javascript">
		$(document).ready(function() {
		    //$('#adTable').DataTable();
		});
	</script>

	<title>
		Ad Manager - Adcrush Media
	</title>
</head>

<body>

	<div style="width: 70%; margin: 0 auto;">

		<table class="table table-striped" id="adTable">

			<thead>
				<tr>
					<th>Campaign ID</th>
					<th></th>
					<th></th>
					<th></th>
				</tr>
			</thead>

			<tbody>
		<?php

			$ads = getAds();

			foreach ($ads as $campaignID => $filenames)
			{
				echo "<tr>\n";
				echo "<td>$campaignID</td>\n";
				echo "<td><a href=\"admanager.php?view=$campaignID\" alt=\"View\" title=\"View\"><span class=\"glyphicon glyphicon-search\" aria-hidden=\"true\"></span></a></td>\n";
				echo "<td><a href=\"admanager.php?edit=$campaignID\" alt=\"Edit\" title=\"Edit\"><span class=\"glyphicon glyphicon-pencil\" aria-hidden=\"true\"></span></a></td>\n";
				echo "<td><a href=\"admanager.php?delete=$campaignID\" alt=\"Delete\" title=\"Delete\" onclick=\"return confirm('Are you sure you want to delete ad with campaignID \'$campaignID\'?');\"><span class=\"glyphicon glyphicon-trash\" aria-hidden=\"true\"></span></a></td>\n";
				echo "</tr>\n";
			}

		?>
			</tbody>
		</table>

		<div style="position: absolute; bottom: 0; margin: 0 auto;">
			<small>&copy; 2016 Adcrush Media</small>
		</div>

	</div>

</body>
</html>