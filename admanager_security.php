<?php

	function userAuthenticated()
	{
		return array_key_exists("Username", $_SESSION) && !empty($_SESSION['Username']);
	}

	function loginUser($username, $password)
	{
		$loginHashes = array("arthurvanderwal" => "731161253fafb5236f015e1d5e1c5964", "benson" => "001a487c5c46e41bc7352b97e10359ce");

		if (array_key_exists($username, $loginHashes))
		{
			if ($loginHashes[$username] == md5($password))
			{
				$_SESSION['Username'] = $username;
			}
		}
	}

	function logoutUser()
	{
		// remove all session variables
		session_unset(); 

		// destroy the session 
		session_destroy(); 
	}

?>