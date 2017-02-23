<?php

	function array_get_bool($array, $key)
	{
		return array_key_exists($key, $array) && $array[$key] === "false" ? false : true;
	}

	function array_get_value_with_default($array, $key, $default = null)
	{
	    return array_key_exists($key, $array) ? $array[$key] : $default;
	}

	function array_get_json_key_at_index_with_default($array, $key, $index, $default = null)
	{
		if (array_key_exists($key, $array))
		{
			$jsonDecoded = json_decode($array[$key], true);
			$jsonKeys = array_keys($jsonDecoded);

			if ($index < sizeof($jsonKeys))
			{
				return $jsonKeys[$index];
			}			
		}

		return $default;
	}

	function array_get_json_value_at_index_with_default($array, $key, $index, $default = null)
	{		
		if (array_key_exists($key, $array))
		{
			$jsonDecoded = json_decode($array[$key], true);
			$jsonKeys = array_keys($jsonDecoded);

			if ($index < sizeof($jsonKeys) && is_array($jsonDecoded[$jsonKeys[$index]]))
			{
				return implode("|", $jsonDecoded[$jsonKeys[$index]]);
			}
			else if ($index < sizeof($jsonKeys))
			{
				return $jsonDecoded[$jsonKeys[$index]];
			}
		}

		return $default;
	}

?>