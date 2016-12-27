<?php

	/* 
		Get ISP by IP info 
		$ip: ipv4 to query information for
		
		returns an array with information or FALSE
	**/
	function getISPInfo($ip)
	{
		// If data not available, we can´t do it
		if (!file_exists('ispipinfo.db'))
		{
			//adlog('getISPInfo: missing DB');

			return false;
		}
		
		/* Use a lock to prevent parallel updates */
		$fl = fopen('ispip.lock', 'c+b');

		if (is_resource($fl))
		{
			if (!flock($fl, LOCK_SH /* Lock for reading */ ))
			{ 
				fclose($fl);
				$fl = false;
			}
		}

		$ip = ip2long($ip);
		
		/*
		4 1 1 2 2 2 2 = 14 bytes
		|ip|ip|ip|ip|mk|io|isp|isp|org|org|asn#|asn#|asnn|asnn
		
		mk = mask
		isp = ISP code
		org = Organization code
		asn# = Autonomous System number
		asnn = Autonomous System number name
		*/
			
		$last = filesize('ispipinfo.db') / 14; /* 14 bytes per record */
		$f = fopen('ispipinfo.db','rb');
		
		$lo = 0; 
		$hi = $last - 1;
		while ($lo <= $hi)
		{
			/* Get index */
			$mid = (int)(($hi - $lo) / 2) + $lo;
			
			/* Read record and unpack it */
			fseek($f, $mid * 14);
			$r = fread($f, 14);
			
			/* 'VCCvvvv' */
			$cols = unpack('V1a/C1b/C1c/v1d/v1e/v1f/v1g',$r);
					
			/* Compare the ip with the supplied one */
			$cmp = (int)($ip-0x80000000) - (int)($cols['a']-0x80000000); /* fix for missing u32 type in php */

			/* Jump to the next register */
			if ($cmp > 0) {
				$lo = $mid + 1;
			} elseif ($cmp < 0) {
				$hi = $mid - 1;
			} else {
				$lo = $mid + 1;
				break;
			}
		}
		
		/* Point to the proper entry */
		if ($lo > 0)
		{
			--$lo;
		}
		
		/* Lets do some parsing - Read record and unpack it */
		fseek($f, $lo * 14);
		$r = fread($f, 14);
		fclose($f);
		$cols = unpack('V1a/C1b/C1c/v1d/v1e/v1f/v1g',$r);
		$mask = ~((1 << (32-$cols["b"]))-1);
		
		if (((int)(($ip ^ $cols["a"]) & $mask)) == 0)
		{
			/* Match! - Return information! */
			
			$isp_code = $cols['d'] | (($cols['c'] & 0x0F) << 16);
			$org_code = $cols['e'] | (($cols['c'] & 0xF0) << 12);
			$asn_nr_code = $cols['f'];
			$asn_name_code = $cols['g'];
			
			/* Find the ISP information */
			$f = fopen('isps.db','rb');
			fseek($f, $isp_code * 54);
			$isp_name = trim(fread($f, 54));
			fclose($f);
			
			/* Find the Organization information */
			$f = fopen('organizations.db','rb');
			fseek($f, $org_code * 54);
			$org_name = trim(fread($f, 54));
			fclose($f);

			/* Find the ASN nr information */
			$f = fopen('asnnrs.db','rb');
			fseek($f, $asn_nr_code * 3);
			$r = fread($f, 3);
			$cols = unpack('v1a/C1b',$r);
			$asn_nr = $cols['a'] | ($cols['b'] << 16);
			fclose($f);

			/* Find the ASN name information */
			$f = fopen('asnnames.db','rb');
			fseek($f, $asn_name_code * 93);
			$asn_name = trim(fread($f, 93));
			fclose($f);

			/* Release lock. Next CURL operation will be carried */
			if ($fl !== false)
			{
				flock($fl, LOCK_UN);
				fclose($fl);
			}	
			
			/* Return all available information */
			return array(
					'isp' => $isp_name,
					'organization' => $org_name,
					'asn_nr' => $asn_nr,
					'asn_name' => $asn_name
				  );
		}
		
		/* Release lock. Next CURL operation will be carried */
		if ($fl !== false)
		{
			flock($fl, LOCK_UN);
			fclose($fl);
		}	
		
		// No information found
		return false;
	}	

	function lookup_subdiv($continent_nr,$country_nr=0,$subdiv1_code="",$subdiv2_code="")
	{		
		$last = filesize('subdivisions.db') / 80; /* 80 bytes per record */
		$f = fopen('subdivisions.db','rb');
		
		$lo = 0; 
		$hi = $last - 1;
		while ($lo <= $hi)
		{
			/* Get index */
			$mid = (int)(($hi - $lo) / 2) + $lo;
			
			/* Read record and unpack it */
			fseek($f, $mid * 80);
			$r = fread($f, 80);
			$cols = unpack('C1a/C1b/a3c/a3d/a72e',$r);
			$cols['c'] = trim($cols['c']);
			$cols['d'] = trim($cols['d']);
			$cols['e'] = trim($cols['e']);
			
			/* Compare with the record we are looking for */
			$cmp = $continent_nr - $cols['a'];

			if ($cmp == 0)
			{
				$cmp = $country_nr - $cols['b'];

				if ($cmp == 0)
				{
					$cmp = strcmp($subdiv1_code, $cols['c']);
					if ($cmp == 0)
					{
						$cmp = strcmp($subdiv2_code, $cols['d']);
					}
				}
				
			}

			/* Jump to the next register */
			if ($cmp > 0)
			{
				$lo = $mid + 1;
			}
			elseif ($cmp < 0)
			{
				$hi = $mid - 1;
			}
			else
			{
				$lo = $mid + 1;
				break;
			}
		}
		
		/* Point to the proper entry */
		if ($lo > 0)
		{
			--$lo;
		}
		
		/* Lets do some parsing - Read record and unpack it */
		fseek($f, $lo * 80);
		$r = fread($f, 80);
		fclose($f);
		$cols = unpack('C1a/C1b/a3c/a3d/a72e',$r);
		$cols['c'] = trim($cols['c']);
		$cols['d'] = trim($cols['d']);
		$cols['e'] = trim($cols['e']);

		/* Compare with the record we are looking for */
		$cmp = $continent_nr - $cols['a'];

		if ($cmp == 0)
		{
			$cmp = $country_nr - $cols['b'];

			if ($cmp == 0)
			{
				$cmp = strcmp($subdiv1_code, $cols['c']);

				if ($cmp == 0)
				{
					$cmp = strcmp($subdiv2_code, $cols['d']);
				}
			}
		}

		return ($cmp == 0) ? trim($cols['e']) : '';
	}

	/* 
		Get ip info 
		$ip: ipv4 to query information for
		
		returns an array with information
	**/
	function getGEOInfo($ip)
	{
		// If data not available, fail call
		if (!file_exists('ipinfo.db'))
		{
			//adlog('getGEOInfo: missing DB');

			return false;
		}
		
		/* Use a lock to prevent parallel updates */
		$fl = fopen('geoip.lock', 'c+b');
		if (is_resource($fl))
		{
			if (!flock($fl, LOCK_SH /* Lock for reading */ ))
			{ 
				fclose($fl);
				$fl = false;
			}
		}

		$ip = ip2long($ip);
		/*
		|ip|ip|ip|ip|mk|cc|cc|cc|pc*8|lt|lt|lt|lt|ln|ln|ln|ln
		*/
		
			
		$last = filesize('ipinfo.db') / 24; /* 24 bytes per record */
		$f = fopen('ipinfo.db','rb');
		
		$lo = 0; 
		$hi = $last - 1;

		while ($lo <= $hi)
		{
			/* Get index */
			$mid = (int)(($hi - $lo) / 2) + $lo;
			
			/* Read record and unpack it */
			fseek($f, $mid * 24);
			$r = fread($f, 24);
			$cols = unpack('V1a/C1b/v1c/C1d/a8e/f1f/f1g',$r);
			
			/* Compare the ip with the supplied one */
			$cmp = (int)($ip-0x80000000) - (int)($cols['a']-0x80000000); /* fix for missing u32 type in php */

			/* Jump to the next register */
			if ($cmp > 0)
			{
				$lo = $mid + 1;
			}
			elseif ($cmp < 0)
			{
				$hi = $mid - 1;
			}
			else
			{
				$lo = $mid + 1;
				break;
			}
		}
		
		/* Point to the proper entry */
		if ($lo > 0)
		{
			--$lo;
		}
		
		/* Lets do some parsing - Read record and unpack it */
		fseek($f, $lo * 24);
		$r = fread($f, 24);
		fclose($f);
		$cols = unpack("V1a/C1b/v1c/C1d/a8e/f1f/f1g",$r);
		
		$mask = ~((1 << (32-$cols["b"]))-1);
		
		if (((int)(($ip ^ $cols["a"]) & $mask)) == 0)
		{
			/* Match! - Return information! */
			$city_code = $cols['c'] | ($cols['d'] << 16);
			$zip = trim($cols['e']);
			$lat = $cols['f'];
			$lon = $cols['g'];
			
			/* Find the cityinfo information and unpack it */
			$f = fopen('cities.db','rb');
			fseek($f, $city_code * 54);
			$r = fread($f, 54);
			fclose($f);
			$cols = unpack('v1a/C1b/v1c/a49d',$r);
			
			$subdivision_code = $cols['a'] | ($cols['b'] << 16);
			$timezone_code = $cols['c'];
			$city = trim($cols['d']);
			
			/* Find the timezone information */
			$timezones_bynr = unserialize(file_get_contents('timezones.db'));
			$timezone = $timezones_bynr[$timezone_code];
			
			/* Find the subdivision associated to the record */
			$f = fopen('subdivisions.db','rb');
			fseek($f, $subdivision_code * 80);
			$r = fread($f, 80);
			fclose($f);
			$cols = unpack('C1a/C1b/a3c/a3d/a72e',$r); /* ct|cy|s1*3|s2*3|divname72 */
			$continent_nr = $cols['a'];
			$country_nr = $cols['b'];
			$subdiv1_code = trim($cols['c']);
			$subdiv2_code = trim($cols['d']);
			$province = trim($cols['e']);
			
			/* Find the continent information */
			$continents_bynr = unserialize(file_get_contents('continents.db'));
			$continent_code = $continents_bynr[$continent_nr][0];
			$continent_name = $continents_bynr[$continent_nr][1];

			/* Find the country information */
			$countries_bynr = unserialize(file_get_contents('countries.db'));
			$country_code = $countries_bynr[$country_nr][0];
			$country_name = $countries_bynr[$country_nr][1];
			
			$subdiv1   = lookup_subdiv($continent_nr,$country_nr,$subdiv1_code);
			$subdiv2   = lookup_subdiv($continent_nr,$country_nr,$subdiv1_code,$subdiv2_code);
			
			/* Release lock. Next CURL operation will be carried */
			if ($fl !== false)
			{
				flock($fl, LOCK_UN);
				fclose($fl);
			}	
			
			/* Return all available information */
			return array(
					'zip' => $zip,
					'lat' => $lat,
					'lon' => $lon,
					'city' => $city,
					'timezone' => $timezone,
					'province' => $province,
					'continent_code' => $continent_code,
					'country_code' => $country_code,
					'continent' => $continent_name,
					'country' => $country_name,
					'subdiv1_code' => $subdiv1_code,
					'subdiv2_code' => $subdiv2_code,
					'subdiv1' => $subdiv1,
					'subdiv2' => $subdiv2
				  );
		}
		
		/* Release lock. Next CURL operation will be carried */
		if ($fl !== false)
		{
			flock($fl, LOCK_UN);
			fclose($fl);
		}	
		
		// No information found
		return false;
	}

	/**
	 * Ensures an ip address is both a valid IP and does not fall within
	 * a private network range.
	 */
	function validateIP($ip)
	{
	    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false)
	    {
	        return false;
	    }

	    return true;
	}	

	/*
		Get the Client IP 
	 **/
	function getClientIP()
	{
	    $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');

	    foreach ($ip_keys as $key)
	    {
	        if (array_key_exists($key, $_SERVER) === true)
	        {
	            foreach (explode(',', $_SERVER[$key]) as $ip)
	            {
	                // trim for safety measures
	                $ip = trim($ip);
	                // attempt to validate IP
	                if (validateIP($ip))
	                {
	                    return $ip;
	                }
	            }
	        }
	    }

	    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
	}

	function getAdCleanHtmlFilename($campaignID)
	{
		return "ads/" . $campaignID . ".cleanad.html";
	}

	function getAdConfigFilename($campaignID)
	{
		return "ads/" . $campaignID . ".config.txt";
	}
	
	function trimNewLine($string)
	{
		return str_replace(PHP_EOL, '', $string);
	}

	function appendParameterPrefix($url)
	{
		if (strpos($url, "?") === false)
		{
			$url .= "?";
		}
		else
		{
			$url .= "&";
		}

		return $url;
	}

	function weightedRand($sourceWeightList)
	{
	    $pos = mt_rand(1, array_sum(array_values($sourceWeightList)));           
	    $sum = 0;

	    foreach ($sourceWeightList as $source => $weight)
	    {
	        $sum += $weight;

	        if ($sum >= $pos)
	        {
	            return $source;
	        }
	    }

	    return null;
	}

	function processAdConfig($filename)
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

	function createLogLine($ip, $isp, $txt)
	{
		$referrer = array_key_exists('HTTP_REFERER', $_SERVER) ? $_SERVER['HTTP_REFERER'] : "Unknown";
		$line = "Date|" . date("Y-m-d H:i:s") . "|IP|" . $ip . "|ISP|" . $isp . "|UserAgent|" . $_SERVER['HTTP_USER_AGENT'] . "|SERVER Referrer|" . urldecode($referrer) . "|QueryString|" . urldecode($_SERVER['QUERY_STRING']) . "|Message|" . $txt . "\n";

		return $line;		
	}	

	function substr_count_in_file($filename, $needle)
	{
		if (!file_exists($filename))
		{
			return null;
		}

		$fileContents = file_get_contents($filename);

		return substr_count($fileContents, $needle);
	}

	function print_r_nice($mixed)
	{
		echo str_replace("\n", "<br/>", print_r($mixed, true));
	}	

?>