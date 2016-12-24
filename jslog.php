<?php

	require_once("include/adlib.inc");

	function jslog($txt)
	{
		if (file_exists('jslog.log'))
		{
			$f = fopen("jslog.log","a");
			fwrite($f, $txt."\n");
			fclose($f);
		}
	}

	jslog(createLogLine($_SERVER['REMOTE_ADDR'], "", $_GET['text']));

	// Make sure file is not cached (as it happens for example on iOS devices)
	header("Expires: Mon, 01 Jan 1985 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0, max-age=0", false);
	header("Pragma: no-cache");

	// Disable Access Control
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET'); 	

?>