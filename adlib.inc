<?php

	function getCleanHtmlFilename($campaignID)
	{
		return "ads/" . $campaignID . ".cleanad.html";
	}

	function getConfigFilename($campaignID)
	{
		return "ads/" . $campaignID . ".config.txt";
	}
	
	function trimNewLine($string)
	{
		return str_replace(PHP_EOL, '', $string);
	}

	function processConfig($filename)
	{
		$result = array();

		$f = fopen($filename, "r");

	    while (($line = fgets($f)) !== false)
	    {
	        $colonIndex = strpos($line, ":");

	        if ($colonIndex !== false)
	        {
		    	$key = trim(substr($line, 0, $colonIndex));
		    	$value = trim(trimNewLine(substr($line, $colonIndex + 1)));

		    	$result[$key] = $value;
		    }
	    }

	    fclose($f);

	    return $result;
	}

?>