<?php

	$mysqli = null;

	function connectDatabase()
	{
		global $mysqli;

		if ($_SERVER["HTTP_HOST"] == "localhost")
		{
			$databaseHost = 'localhost';
			$databaseUser = 'root';
			$databasePassword = '';
			$database = 'ad_cloaker';
		}

		else
		{
			$databaseHost = 'localhost';
			$databaseUser = 'awstst1_ad_cloak';
			$databasePassword = '8;G0+JV(7T6X';
			$database = 'awstst1_ad_cloaker';
		}		

		$mysqli = new mysqli($databaseHost, $databaseUser, $databasePassword, $database);

		if ($mysqli->connect_errno)
		{
		    //echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		    unset($myssqli);
		}
	}

?>