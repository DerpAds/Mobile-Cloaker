<?php

	function callAPI($url, $data, $headers = null)
	{
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

	    if ($headers != null && !empty($headers) && is_array($headers))
	    {
	    	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    }

	    $result = curl_exec($curl);

	    curl_close($curl);

	    return $result;
	}

	function callAPIPOST($url, $data, $headers = null)
	{
		return callAPI($url, $data, $headers);
	}

	function callAPIGET($url, $headers = null)
	{
		$curl = curl_init();

	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

	    if ($headers != null && !empty($headers) && is_array($headers))
	    {
	    	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    }

	    $result = curl_exec($curl);

	    curl_close($curl);

	    return $result;		
	}

?>