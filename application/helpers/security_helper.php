<?php

	function userAuthenticated()
	{
		return array_key_exists("Username", $_SESSION) && !empty($_SESSION['Username']);
	}

	function loginUser($username, $password, $loginHashes)
	{
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