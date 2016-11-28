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

	if ($_POST['PhoneNumber'])
	{
		callAPI("https://app.eztexting.com/contacts?format=json", array("User" => "acmsms", "Password" => "Adcrush123!", "Groups" => array("ED Coupon"), "PhoneNumber" => $_POST['PhoneNumber']));
	}

	header("Location: https://www.google.com");
	exit;

?>