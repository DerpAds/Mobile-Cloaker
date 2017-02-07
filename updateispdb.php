<?php

	function mylog($txt)
	{
		echo $txt . "<br/>";
	}

	class FixedSizeRecord implements ArrayAccess, Iterator {
		
	    private $container = ''; 	// String handling
		private $recSize = 1;		// Record size
		private $position = 0;		// Iterator position
		private $length = 0;		// Array length

		public function recSize() {
			return $this->recSize;
		}
		
	    public function __construct($recSize) {
			$this->container = '';
			$this->recSize = $recSize;
			$this->position = 0;
			$this->length = 0;
	    }

	    public function offsetSet($offset, $value) {
			
			// Make sure value has exactly the required length -- MUST be true
			// $value = substr(str_pad($value,$this->recSize,"\0",STR_PAD_RIGHT),0,$this->recSize);
			
	        if ($offset === null) {
				// Add it to the end
				$this->container .= $value;
				++$this->length;
	        } else {
				// Make sure we have enough storage size
				if ($this->length <= $offset) {
					$this->container .= str_repeat("\0",($offset + 1 - $this->length) * $this->recSize);
					$this->length = $offset + 1;
				}
				
				// Replace bytes one by one, avoiding copies
				$offset *= $this->recSize;
				
				// Store element
				for ($i = 0; $i < $this->recSize; ++$i) {
					$this->container[$offset+$i] = $value[$i];
				}
	        }
	    }

	    public function offsetExists($offset) {
	        return $offset < $this->length;
	    }

	    public function offsetUnset($offset) {
			if ($offset < $this->length) {
				// Humm needs a copy... 
				$this->container = 
					substr($this->container,0,$offset * $this->recSize).
					substr($this->container,($offset+1) * $this->recSize);
				--$this->length;
			}
	    }

	    public function offsetGet($offset) {
			return ($offset < $this->length)
				? substr($this->container,$offset * $this->recSize,$this->recSize)
				: null;
	    }
		
		/* Iterator interfaces */
	    public function rewind() {
	        $this->position = 0;
	    }

	    public function current() {
	        return $this->offsetGet($this->position);
	    }

	    public function key() {
	        return $this->position;
	    }

	    public function next() {
	        ++$this->position;
	    }

	    public function valid() {
	        return $this->offsetExists($this->position);
	    }
		
		/* misc */
		public function getCount() {
			return $this->length;
		}
		
		/* faster versions that check nothing */
		
		public function add($value) {
			$this->container .= $value;
			++$this->length;
		}
		
		/* get: offset must exist */
	    public function get($offset) {
			return substr($this->container,$offset * $this->recSize, $this->recSize);
	    }
		
		/* set: offset must exist */
	    public function set($offset, $value) {
		
			// Replace bytes one by one, avoiding copies
			$offset *= $this->recSize;
			
			// Store element
			for ($i = 0; $i < $this->recSize; ++$i) {
				$this->container[$offset] = $value[$i];
				++$offset;
			}
	    }
	}

	/*
		Minimalistic unzip: handler, to be able to decompress zip files on the fly
		while processing them
	 **/
	class UnzipStream {
	    var $position;
		var $length;
	    var $zipfile;
		var $ziphandle;
		var $zipentry;

	    public function stream_open($path, $mode, $options, &$opened_path)
	    {
			//mylog("unzip:".$path);
			$pos = strpos($path,'#');
			if ($pos === false)
				return false;
			
	        $this->zipfile = substr($path,8,$pos-8);
			$this->file = substr($path,$pos+1);
	        $this->position = 0;
			$this->length = 0;

			//mylog("unzip open:".$this->zipfile." - ".$this->file);
			
			$this->ziphandle = zip_open($this->zipfile); 
			if (!is_resource($this->ziphandle)) {
				mylog("unable to open zip:".$this->zipfile);
				return false;
			}
		
			$found = false;
			while ($this->zip_entry = zip_read($this->ziphandle)) {
				$zen = zip_entry_name($this->zip_entry);
				if ($zen == $this->file) {
					$this->position = 0;
					$this->length = zip_entry_filesize($this->zip_entry);
					$found = true;
					//mylog("unzip length:".$this->length);	
					break;
				}
			}
			
			if (!$found) {
				//mylog("unzip unable to find ".$this->file);
				zip_close($this->ziphandle);
				$this->ziphandle = false;
			}
			
	        return true;
	    }

		public function stream_close()
		{
			if ($this->ziphandle !== false) {
				zip_close($this->ziphandle);
				$this->ziphandle = false;
			}
		}
		
	    public function stream_read($count)
	    {
			//mylog("unzip read : w:".$count.' p:'.$this->position.' l:'.$this->length);
			if ($this->position >= $this->length)
				return false;
			$ret = zip_entry_read($this->zip_entry, $count);
	        $this->position += strlen($ret);
	        return $ret;
	    }

	    public function stream_write($data)
	    {
			return false;
	    }

	    public function stream_tell()
	    {
	        return $this->position;
	    }

	    public function stream_eof()
	    {
	        return $this->position >= $this->length;
	    }

	    public function stream_seek($offset, $whence)
	    {
			return false;
	    }

	    public function stream_metadata($path, $option, $var) 
	    {
	        return false;
	    }
	}

	stream_wrapper_register("unzip", "UnzipStream")
	    or die("Failed to register protocol");

	/* 
	 *We need 2 arrays 'a' and 'b', of equal size
	 *Here 'a' is the primary array (i.e which contains initial 
	     input, and final output)
	 *And 'b' is the temporary array,
	     used for merging 2 sorted half's in 'a' 
	 */
	  
	function merge($ary, $low, $mid, $high)
	{
	    $b = new FixedSizeRecord($ary->recSize());
	    $i = $low; $j = $mid + 1; //, $k = 0;
	  
	    while ($i <= $mid && $j <= $high) {

			/* Read i record and unpack it */
			$r_i = $ary->get($i);
			$cols_i = unpack('V1a',$r_i);
			$ip_i = $cols_i['a'];
			$ip_i = (int)($ip_i -0x80000000); /* <= fix for PHP ints instead of u32 */
		
			/* Read j record and unpack it */
			$r_j = $ary->get($j);
			$cols_j = unpack('V1a',$r_j);
			$ip_j = $cols_j['a'];
			$ip_j = (int)($ip_j -0x80000000); /* <= fix for PHP ints instead of u32 */
			
			/*a[i] <= a[j]*/
	        if ($ip_i <= $ip_j) {
	            /*b[k] = a[i];*/
				$b->add($r_i);
				++$i;
	        } else {
	            /*b[k] = a[j];*/
				$b->add($r_j);
				++$j;
			}
			//++$k;
	    }
	    while ($i <= $mid) {
			
			/* Read i record */
			$r_i = $ary->get($i);
			
	        /*b[k] = a[i];*/
			$b->add($r_i);
			//++$k;
			++$i;
		}
	  
	    while ($j <= $high) {
			
			/* Read j record */
			$r_j = $ary->get($j);
			
	        /*b[k] = a[j];*/
			$b->add($r_j);
			//++$k;
			++$j;
		}
	  
		$k = $b->getCount();
	    --$k;
	    while ($k >= 0) {

			/* Read k record */
			$r_k = $b->get($k);
		
	        /*a[$low + $k] = b[$k];*/
			$ary->set($low + $k, $r_k);
			
	        --$k;
	    }
	}
	  
	function mergesort($ary, $low, $high)
	{
	    if ($low < $high) {
	        $m = ($high + $low)>>1;
	        mergesort($ary, $low, $m);
	        mergesort($ary, $m + 1, $high);
	        merge($ary, $low, $m, $high);
	    }
	}

	function ip_Mergesort($ary, $elements) 
	{
		mergesort($ary, 0, $elements-1);
	}	

	function compileISPDB($dbName) {

		/* The temporary zip file that is downloaded to update GEOIP */
		$zipFile = __DIR__.DIRECTORY_SEPARATOR.$dbName;

		/* Unlimited max execution time	- The import process could take some time */
		set_time_limit(0); 
		
		/* The Database import process requires to load all the database into memory - So, increase limit ... */
		ini_set('memory_limit', '256M');
		
		/* Explore ZIP contents to locate the files we need */
		$blocksFile = null;
		
		mylog("About to open ".$zipFile);
		
		$zip = zip_open($zipFile); 
		if (!is_resource($zip)) {
			mylog("Unable to open ".$zipFile);
			return false;
		}
		
	    while ($zip_entry = zip_read($zip)) {
	        $zen = zip_entry_name($zip_entry);
			if (stripos($zen, 'Blocks-IPv4.csv') !== false) {
				$blocksFile = $zen;
			}
	    }
	    zip_close($zip);

		if ($blocksFile == null) {
			mylog('Unable to find the required file in the ZIP archive');
			return false;
		}

		mylog('blocks file:'.$blocksFile);
		
		/*
		network     ,isp                                      ,organization                                    ,autonomous_system_number,autonomous_system_organization 	
		0.0.0.0/8   ,"IPv4 address block reserved by the IETF","IPv4 address block reserved by the IETF"       ,                        , 
		1.0.38.0/24 ,"China Telecom Guangdong"                ,"Asia Pacific Broadband Wireless Communications",24155                   ,"Asia Pacific Broadband Wireless Communications Inc"
		
		4 1 1 2 2 2 2 = 14 bytes
		|ip|ip|ip|ip|mk|io|isp|isp|org|org|asn#|asn#|asnn|asnn
		
		mk = mask
		isp = ISP code
		org = Organization code
		asn# = Autonomous System number
		asnn = Autonomous System number name
		io = nibble LO: ISP 16-23 bits
		o  = nibble HI: ORG 16-23 bits
		
		*/
		
		$isp_map = array(); // ISP map to internal code
		$isp_code_cnt = 0;
		$isp_max_len = 0;
		$org_map = array(); // Organization map to internal code
		$org_code_cnt = 0;
		$org_max_len = 0;
		$asn_nr_map = array(); // ASN number map to internal code
		$asn_nr_code_cnt = 0;
		$asn_nr_max = 0;
		$asn_name_map = array(); // ASN name map to internal code
		$asn_name_code_cnt = 0;
		$asn_name_max_len = 0;
		
		$iprec = new FixedSizeRecord(14);	// Fixed size record array, to improve memory usage

		/* Read the IPv4 blocks info database */
		$fb = fopen('unzip://'.$dbName.'#'.$blocksFile,'r');
		if (!is_resource($fb)) {
			return false;
		}
		
		/* Skip header */
		fgetcsv($fb); 
		
		mylog("Start processing ISP records ...");
		$startTime = time();
		
		/* Process each record */
		while (!feof($fb)) {
			$cols = fgetcsv($fb);
			if ($cols === false) 
				break;
			
			// Trim spaces
			$cols = array_map('trim', $cols);
			
			// Skip empty records
			if ($cols[0] === null) 
				continue;
			
			/* Split into IP and mask */
			$addr = explode('/',$cols[0]);
			$ip = ip2long($addr[0]);  // IP   32 bits
			$mask = intval($addr[1]); // Mask 8bits

			/* ISP name */
			$isp_name = $cols[1];
			
			/* Organization name */
			$org_name = $cols[2];

			/* ASN number */
			$asn_nr = $cols[3];

			/* ASN name */
			$asn_name = $cols[4];

			/* Map the isp name to our own internal contiguous code */
			if (!array_key_exists($isp_name,$isp_map)) {
				$isp_code = $isp_code_cnt;
				$isp_map[$isp_name] = $isp_code;
				++$isp_code_cnt;
				if ($isp_max_len < strlen($isp_name)) {
					$isp_max_len = strlen($isp_name);
				}
			} else {
				$isp_code = $isp_map[$isp_name];
			}

			/* Map the organization name to our own internal contiguous code */
			if (!array_key_exists($org_name,$org_map)) {
				$org_code = $org_code_cnt;
				$org_map[$org_name] = $org_code;
				++$org_code_cnt;
				if ($org_max_len < strlen($org_name)) {
					$org_max_len = strlen($org_name);
				}
			} else {
				$org_code = $org_map[$org_name];
			}
			
			/* Map the ASN number to our own internal contiguous code */
			if (!array_key_exists($asn_nr,$asn_nr_map)) {
				$asn_nr_code = $asn_nr_code_cnt;
				$asn_nr_map[$asn_nr] = $asn_nr_code;
				++$asn_nr_code_cnt;
				if ($asn_nr_max < $asn_nr) {
					$asn_nr_max = $asn_nr;
				}
				
			} else {
				$asn_nr_code = $asn_nr_map[$asn_nr];
			}

			/* Map the ASN name to our own internal contiguous code */
			if (!array_key_exists($asn_name,$asn_name_map)) {
				$asn_name_code = $asn_name_code_cnt;
				$asn_name_map[$asn_name] = $asn_name_code;
				++$asn_name_code_cnt;
				if ($asn_name_max_len < strlen($asn_name)) {
					$asn_name_max_len = strlen($asn_name);
				}
			} else {
				$asn_name_code = $asn_name_map[$asn_name];
			}
			
			/* Pack the record into a fixed length record */
			$bcol = pack('VCCvvvv',$ip,$mask,
					(($isp_code >> 16) & 0xF) | (($org_code >> 12) & 0xF0),
					$isp_code & 0xFFFF,
					$org_code & 0xFFFF,
					$asn_nr_code,
					$asn_name_code);
		
	//		$cols = unpack('V1a/C1b/C1c/v1d/v1e/v1f/v1g',$bcol);
	//		mylog('[0] '.strlen($bcol).'-'.bin2hex($bcol).' ip:'.long2ip((float)$cols['a']).'/'.$cols['b']);
				
			/* Add it to the record */
			$iprec->add($bcol);
			
		};
		fclose($fb);

		mylog("Took ".(time()-$startTime)." Seconds");
		
		mylog("isps:".$isp_code_cnt);
		mylog("orgs:".$org_code_cnt);
		mylog("asn_nrs:".$asn_nr_code_cnt);
		mylog("asn_names:".$asn_name_code_cnt);
		
		mylog("isp_max_len:".$isp_max_len);
		mylog("org_max_len:".$org_max_len);
		mylog("asn_nr_max:".$asn_nr_max);
		mylog("asn_name_max_len:".$asn_name_max_len);
		
		
		/* Dump all IP records */
	//	foreach( $iprec as $r) {
	//		$cols = unpack('V1a/C1b/C1c/v1d/v1e/v1f/v1g',$r);
	//		mylog('[1] '.strlen($r).'-'.bin2hex($r).' ip:'.long2ip((float)$cols['a']).'/'.$cols['b']);
	//	}
		
		
		mylog("Start sorting IP records ...");
		$startTime = time();
		
		/* Sort by IP */
		ip_Mergesort($iprec, $iprec->getCount());
		
		mylog("Took ".(time()-$startTime)." Seconds");	
		
		/* Use a lock to prevent parallel updates */
		$gl = fopen('ispip.lock', 'c+b');
		if (!is_resource($gl)) {
			return false;
		}
		
		if (!flock($gl, LOCK_EX /* Lock for writing. Noone can read it */ )) { 
			fclose($gl);
			return false;
		}
		
		/* Now dump the information as a binary database to a file */
		$o = fopen('ispipinfo.db','wb');
		if (!is_resource($o)) {
			/* Release Geoip lock */
			flock($gl, LOCK_UN);
			fclose($gl);
			return false;
		}

		/* From this point onwards, there is no turning back ... remove all the other to 
		   be created files */
		if (file_exists('isps.db')) {
			unlink('isps.db');
		}
		if (file_exists('organizations.db')) {
			unlink('organizations.db');
		}
		if (file_exists('asnnrs.db')) {
			unlink('asnnrs.db');
		}
		if (file_exists('asnnames.db')) {
			unlink('asnnames.db');
		}

		/* Dump all IP records */
		foreach( $iprec as $r) {
			fwrite($o,$r);
		}
		fclose($o);
		
		/* We need to dump isps - sort them by value (=index here) */
		$isps_bynr = array_flip($isp_map);
		ksort($isps_bynr);
		$o = fopen('isps.db','wb');
		if (!is_resource($o)) {
			/* Release Geoip lock */
			flock($gl, LOCK_UN);
			fclose($gl);
			return false;
		}
		
		foreach( $isps_bynr as $idx => $isp_name) {
			$r = pack('a54',$isp_name);
			fwrite($o,$r);
		}
		fclose($o);

		/* We need to dump organizations - sort them by value (=index here) */
		$organizations_bynr = array_flip($org_map);
		ksort($organizations_bynr);
		$o = fopen('organizations.db','wb');
		if (!is_resource($o)) {
			/* Release Geoip lock */
			flock($gl, LOCK_UN);
			fclose($gl);
			return false;
		}
		
		foreach( $organizations_bynr as $idx => $org_name) {
			$r = pack('a54',$org_name);
			fwrite($o,$r);
		}
		fclose($o);

		/* We need to dump ASN numbers - sort them by value (=index here)*/
		$asn_nr_bynr = array_flip($asn_nr_map);
		ksort($asn_nr_bynr);
		$o = fopen('asnnrs.db','wb');
		if (!is_resource($o)) {
			/* Release Geoip lock */
			flock($gl, LOCK_UN);
			fclose($gl);
			return false;
		}

		foreach( $asn_nr_bynr as $idx => $asn_nr) {
			$r = pack('vC',$asn_nr & 0xFFFF , ($asn_nr >> 16) & 0xFF);
			fwrite($o,$r);
		}
		fclose($o);

		/* We need to dump ASN names - sort them by value (=index here)*/
		$asn_name_bynr = array_flip($asn_name_map);
		ksort($asn_nr_bynr);
		$o = fopen('asnnames.db','wb');
		if (!is_resource($o)) {
			/* Release Geoip lock */
			flock($gl, LOCK_UN);
			fclose($gl);
			return false;
		}
		
		foreach( $asn_name_bynr as $idx => $asn_name) {
			$r = pack('a93',$asn_name);
			fwrite($o,$r);
		}
		fclose($o);

		/* Release Geoip lock */
		flock($gl, LOCK_UN);
		fclose($gl);

		/* Done! */
		return true;
	}

	compileISPDB($_GET['zipfilename']);

?>