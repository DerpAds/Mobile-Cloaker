<?php

	function callAPI($url, $data)
	{
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

	    $result = curl_exec($curl);

	    curl_close($curl);

	    return $result;
	}

?>