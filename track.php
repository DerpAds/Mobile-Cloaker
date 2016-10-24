<?php

	function tracklog($txt) {
		if (file_exists('tracklog.log')) {
			$f = fopen("tracklog.log","a");
			fwrite($f, $txt."\n");
			fclose($f);
		}
	}

	tracklog($_SERVER['REMOTE_ADDR'] . ": " . $_SERVER['HTTP_USER_AGENT']);

	header("Location: https://ocpmb.voluumtrk3.com/cc41236c-31e5-4b7f-9c26-aec024524a25?ad=2"); /* Redirect browser */

	/* Make sure that code below does not get executed when we redirect. */
	exit;
?>