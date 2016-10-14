<?php

/* 
 * Script implementing a web endpoint that returns GEO and ISP information
 * of the IP doing the request.
 * 
 * If a file called geoisplog.log is present in the same location as this script,
 * then all IP access information will be logged there.
 */

$allowedIsps = array(
	"AT&T Wireless",
	"T-Mobile USA",
	"Sprint PCS",
	"Verizon Wireless",
	"Comcast Cable",
	"Time Warner Cable",
	"AT&T U-verse",
	"Charter Communications",
	"Cox Communications",
	"CenturyLink",
	"Optimum Online",
	"AT&T Internet Services",
	"Frontier Communications",
	"Suddenlink Communications",
	"XO Communications",
	"Verizon Internet Services",
	"Mediacom Cable",
	"Windstream Communications",
	"Bright House Networks",
	"Abovenet Communications",
	"Google",
	"Cable One"
);

$blacklistedCities = array();
$blacklistedProvinces = array();
$blacklistedSubDivs1 = array();
$blacklistedSubDivs2 = array(); 
$blacklistedCountries = array();
$blacklistedContinents = array();

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
	
/*
	Compile the ISP database
 **/	
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

/*
	Update the GeoIP database
 **/	
function compileGEODB($dbName) {

	/* The temporary zip file that is downloaded to update GEOIP */
	$zipFile = __DIR__.DIRECTORY_SEPARATOR.$dbName;

	/* Unlimited max execution time	- The import process could take some time */
	set_time_limit(0); 
	
	/* The Database import process requires to load all the database into memory - So, increase limit ... */
	ini_set('memory_limit', '800M');
	
	/* Explore ZIP contents to locate the files we need */
	$blocksFile = null;
	$locationsFile = null;
	
	$zip = zip_open($zipFile); 
	if (!$zip) {
		return false;
	}
	
    while ($zip_entry = zip_read($zip)) {
        $zen = zip_entry_name($zip_entry);
		if (stripos($zen, 'Blocks-IPv4.csv') !== false) {
			$blocksFile = $zen;
		} else
		if (stripos($zen, 'Locations-en.csv') !== false) {
			$locationsFile = $zen;
		}
    }
    zip_close($zip);

	if ($blocksFile == null || $locationsFile == null) {
		mylog('Unable to find the required files in the ZIP archive');
		return false;
	}

	//mylog('blocks file:'.$blocksFile);
	//mylog('locations file:'.$locationsFile);
	
	/*
	network        ,geoname_id,registered_country_geoname_id,represented_country_geoname_id,is_anonymous_proxy,is_satellite_provider,postal_code,latitude,longitude
	2.83.147.128/25,2267095   ,2264397                      ,                              ,0                 ,0                    ,2420-197   ,39.8375,-8.6583
	4 1 3 8 4 4 = 24 bytes
	|ip|ip|ip|ip|mk|cc|cc|cc|pc*8|lt|lt|lt|lt|ln|ln|ln|ln
	
	mk = mask
	cc = city code descriptor
	pc = postal code
	lt = latitude
	ln = longitude
	
	Postal code, latitude and longitude are not present if dealing with the reduced (country) database
	*/
	
	$cc_map = array(); // City code map to internal code
	$cc_code_cnt = 0;
	$iprec = new FixedSizeRecord(24);	// Fixed size record array, to improve memory usage

	/* Read the IPv4 blocks info database */
	$fb = fopen('unzip://'.$dbName.'#'.$blocksFile,'r');
	if (!is_resource($fb)) {
		return false;
	}
	
	$fl = fopen('unzip://'.$dbName.'#'.$locationsFile,'r');
	if (!is_resource($fl)) {
		return false;
	}
	
	/* Skip header */
	fgetcsv($fb); 
	
	mylog("Start processing GEO IP records ...");
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
		
		/* Represented country, country, or registered country, in that order */
		$cc = ($cols[3] != null) ? $cols[3] : (($cols[1] == null) ? $cols[2] : $cols[1]);
		
		/* There are some ip ranges that have no associated geocode... Those records must be handled somehow */
		$cc = ($cc === '') ?  0 : (intval($cc) + 1);
		
		/* Map the city code to our own internal contiguous code */
		if (!array_key_exists($cc,$cc_map)) {
			$cc_code = $cc_code_cnt;
			$cc_map[$cc] = $cc_code;
			++$cc_code_cnt;
		} else {
			$cc_code = $cc_map[$cc];
		}
		
		/* Pack the record into a fixed length record */
		$ncols = count($cols);
		$bcol = pack('VCvCa8ff',$ip,$mask,$cc_code&0xFFFF,$cc_code>>16,
					($ncols>6?$cols[6]:''),
					($ncols>8?floatval($cols[7]):0.0),
					($ncols>8?floatval($cols[8]):0.0)
					);
		
		/* Add it to the record */
		$iprec->add($bcol);
		
	};
	fclose($fb);

	mylog("Took ".(time()-$startTime)." Seconds");
	
	mylog("Start sorting GEO IP records ...");
	$startTime = time();
	
	/* Sort by IP */
	ip_Mergesort($iprec, $iprec->getCount());
	
	mylog("Took ".(time()-$startTime)." Seconds");	
	
	/* Use a lock to prevent parallel updates */
	$gl = fopen('geoip.lock', 'c+b');
	if (!is_resource($gl)) {
		fclose($fl);
		return false;
	}
	
	if (!flock($gl, LOCK_EX /* Lock for writing. Noone can read it */ )) { 
		fclose($gl);
		fclose($fl);
		return false;
	}
	
	/* Now dump the information as a binary database to a file */
	$o = fopen('ipinfo.db','wb');
	if (!is_resource($o)) {
		/* Release Geoip lock */
		flock($gl, LOCK_UN);
		fclose($gl);
		fclose($fl);
		return false;
	}
	
	/* From this point onwards, there is no turning back ... remove all the other to 
	   be created files */
	if (file_exists('cities.db')) {
		unlink('cities.db');
	}
	if (file_exists('continents.db')) {
		unlink('continents.db');
	}
	if (file_exists('countries.db')) {
		unlink('countries.db');
	}
	if (file_exists('timezones.db')) {
		unlink('timezones.db');
	}
	if (file_exists('subdivisions.db')) {
		unlink('subdivisions.db');
	}
	if (file_exists('ip2country.db')) {
		unlink('ip2country.db');
	}
	   
	/* Dump all IP records */
	foreach( $iprec as $r) {
		fwrite($o,$r);
	}
	fclose($o);
	
	/* Now read the City locations database */
	/*
	geoname_id,locale_code,continent_code,continent_name,country_iso_code,country_name*/
	/*       0           1              2              3                4            5                      6                  7                       8                   9           10          11         12   
	geoname_id,locale_code,continent_code,continent_name,country_iso_code,country_name,subdivision_1_iso_code,subdivision_1_name, subdivision_2_iso_code, subdivision_2_name, city_name  , metro_code, time_zone
	496278    ,en         ,EU            ,Europe        ,RU              ,Russia      ,MUR                   ,Murmansk          ,                       ,                   , Severomorsk,           , Europe/Moscow
	
	From column 6 onwards, are optative, if dealing with the country database
	*/
	
	$cityrec = array();
	$continents = array();
	$countries = array();
	$continents_bynr = array();
	$countries_bynr = array();
	$subdivisions = array();
	$timezones = array();
	
	/* Skip header */
	fgetcsv($fl); 

	/* Process each record */
	while (!feof($fl)) {
		
		$cols = fgetcsv($fl);
		if ($cols === false) 
			break;

		// Trim spaces
		$cols = array_map('trim', $cols);
		
		/* If invalid register, skip it */
		if ($cols[0] === null || $cols[0] === '') 
			continue;

		/* Only add it if city is actually used */
		$occ = intval($cols[0]) + 1; /*+1 as 0 is reserved for missing */
		if (!array_key_exists($occ,$cc_map)) 
			continue;
		
		/* Get the mapped city code */
		$cc = $cc_map[$occ]; 

		/* Store continents and assign them an index */
		if (!array_key_exists($cols[2],$continents)) {
			$idx = count($continents);
			$continents[$cols[2]] = $idx;
			$continents_bynr[$idx] = array($cols[2],$cols[3]);
		}

		/* Store countries and assign them an index */
		if (!array_key_exists($cols[4],$countries)) {
			$idx = count($countries);
			$countries[$cols[4]] = $idx;
			$countries_bynr[$idx] = array($cols[4],$cols[5]);
		}

		/* Store subdivisions if present */
		if (count($cols) > 6 && $cols[6] !== '') { 
			$subdiv = str_pad($continents[$cols[2]],1,'0',STR_PAD_LEFT).
					  str_pad($countries [$cols[4]],3,'0',STR_PAD_LEFT).
					  str_pad($cols[6],3,' ',STR_PAD_RIGHT);
			$subdivisions[$subdiv] = $cols[7]; // Subdivision1 name
			if ($cols[8] !== '') { 
				$subdiv .= str_pad($cols[8],3,' ',STR_PAD_RIGHT);
				$subdivisions[$subdiv] = $cols[9]; // Subdivision2 name
			}
		} else {
			// If no subdivisions, at least try to store continent/country to be able to associate it
			// to the ip
			$subdiv = str_pad($continents[$cols[2]],1,'0',STR_PAD_LEFT).
					  str_pad($countries [$cols[4]],3,'0',STR_PAD_LEFT);
			$subdivisions[$subdiv] = ''; // No city name available
		}
		
		/* Store timezones and assign them an index */
		if (count($cols) > 12 && !array_key_exists($cols[12],$timezones)) {
			$timezones[$cols[12]] = count($timezones);
		}
		
		/* Store city information on a fixed size register */
		/* |sc|sc|sc|tz|tz|cityname(49)
		
		sc = subdivision code [added later]
		tz = timezone code
		cityname
		*/
	
		$r = pack('va49', 
				((count($cols) > 12) ? $timezones[$cols[12]] : 0),
				((count($cols) > 10) ? $cols[10] : '')
			);
		
		/* Add it to the record */
		$cityrec[$cc]=array($subdiv,$r);
		
	};
	fclose($fl);
	
	/* Check that all referenced city codes by ipinfo are present. If not, 
	   fill them to avoid crashes */
	for ($i = 0; $i < $cc_code_cnt; ++$i) {
		if (!array_key_exists($i,$cityrec)) {
			//mylog('missing cityinfo entry - Adding dummy entry for index #'.$i);
			$cityrec[$i] = array('-', pack('va49', 0,''));
		}
	}
	
	/* Sort by city code */
	ksort($cityrec);
	
	/* Also sort subdivisions by key (continent/country/sub1/sub2) */
	ksort($subdivisions);
	
	/* Assign codes to subdivisions */
	$code = 1;
	$subdivision_code = array();
	$subdivision_code['-'] = 0; /* no subdivision entry */
	foreach( $subdivisions as $subdiv => $name) {
		$subdivision_code[$subdiv] = $code;
		++$code;
	}
	
	/* Now dump the information as a binary database to a file */
	$o = fopen('cities.db','wb');
	if (!is_resource($o)) {
		/* Release Geoip lock */
		flock($gl, LOCK_UN);
		fclose($gl);
		return false;
	}
	
	foreach( $cityrec as $cc => $r ) {
		$sd = $r[0];
		$sc = $subdivision_code[$sd];
		$rr = $r[1];
		
		fwrite($o, pack('vC',$sc & 0xFFFF, $sc >> 16).$rr);
	}
	fclose($o);
	
	/* We need to dump continents - sort them by value (=index here)  - This file is very short, we just serialize it */
	ksort($continents_bynr);
	$o = fopen('continents.db','wb');
	if (!is_resource($o)) {
		/* Release Geoip lock */
		flock($gl, LOCK_UN);
		fclose($gl);
		return false;
	}
	
	fwrite($o,serialize($continents_bynr));
	fclose($o);

	/* We need to dump countries - sort them by value (=index here)  - This file is not so short, but we just serialize it */
	ksort($countries_bynr);
	$o = fopen('countries.db','wb');
	if (!is_resource($o)) {
		/* Release Geoip lock */
		flock($gl, LOCK_UN);
		fclose($gl);
		return false;
	}
	
	fwrite($o,serialize($countries_bynr));
	fclose($o);

	/* We need to dump timezones - sort them by value (=index here)  - This file is not so short, but we just serialize it */
	$timezones_bynr = array_flip($timezones);
	if (!array_key_exists(0,$timezones_bynr)) {
		$timezones_bynr[0]='';
	}
	ksort($timezones_bynr);
	$o = fopen('timezones.db','wb');
	if (!is_resource($o)) {
		/* Release Geoip lock */
		flock($gl, LOCK_UN);
		fclose($gl);
		return false;
	}
	
	fwrite($o,serialize($timezones_bynr));
	fclose($o);
	
	/* Finally, we need to dump the subdivisions - This is a big database, so we must be very careful */
	$subdivision_code_bynr = array_flip($subdivision_code); // Order them by index
	ksort($subdivision_code_bynr);
	$o = fopen('subdivisions.db','wb');
	if (!is_resource($o)) {
		/* Release Geoip lock */
		flock($gl, LOCK_UN);
		fclose($gl);
		return false;
	}
	
	$subdivisions['-'] = '';
	foreach($subdivision_code_bynr as $idx => $subdiv) {
		// We need to split subdivisions
		$ary = array(
			substr($subdiv,0,1),
			substr($subdiv,1,3),
			trim(substr($subdiv,4,3)),
			trim(substr($subdiv,7,3)));
		
		/* ct|cy|s1*3|s2*3|divname72 */
		
		$cnr = intVal($ary[0]);
		if ($ary[1] !== '') {
			$tnr = ltrim($ary[1], '0');
			if ($tnr == '') $tnr = '0';
			$tnr = intVal($tnr);
		} else {
			$tnr = 0;
		}
		
		$code = array(
			$cnr,
			$tnr,
			$ary[2],
			$ary[3]
		);
		
		/* Get the name */
		$name = $subdivisions[$subdiv];
		
		/* Pack it into a fixed size register */
		$r = pack('CCa3a3a72',$code[0],$code[1],$code[2],$code[3],$name);
		fwrite($o,$r);
		
	}
	fclose($o);
	
	/* Prepare a reduced version of the database, that maps IPs to countries */
	
	/* Now dump the information as a binary database to a file */
	$o = fopen('ip2country.db','wb');
	if (!is_resource($o)) {
		/* Release Geoip lock */
		flock($gl, LOCK_UN);
		fclose($gl);
		return false;
	}
	
	/* For each record */
	foreach($iprec as $r) {
		
		/* Unpack ipinfo record 
		|ip|ip|ip|ip|mk|cc|cc|cc|pc*8|lt|lt|lt|lt|ln|ln|ln|ln
		*/
		$cols = unpack('V1a/C1b/v1c/C1d/a8e/f1f/f1g',$r);
				
		/* Reconstruct the city code */
		$city_code = $cols['c'] | ($cols['d'] << 16);
		
		/* With that city code, get the city information */
		$sd = $cityrec[$city_code][0]; /* Get the subdivision abbrev */

		// We need to split subdivision abbrev
		$tnr = substr($sd,1,3);
		
		/* Only extract the country code number */
		if ($tnr !== '') {
			$tnr = ltrim($tnr, '0');
			if ($tnr == '') $tnr = '0';
			$tnr = intVal($tnr);
		} else {
			$tnr = 0;
		}
		
		/* With that code, translate back to country iso code */
		$country_iso = $countries_bynr[$tnr][0];

		/* Finally, write a record with the information */
		$r2 = pack('VCa2',$cols['a'],$cols['b'],$country_iso);
		
		fwrite($o,$r2);
	}
	fclose($o);

	/* Release Geoip lock */
	flock($gl, LOCK_UN);
	fclose($gl);

	/* Done! */
	return true;
}

/*
	Download a given file to the same folder as the script
 **/
function downloadFile($url,$destination) {
	
	/* Unlimited max execution time	- The import process could take some time */
	set_time_limit(0); 
	
	/* We will use CURL to download the latest database */
	$dest = fopen(__DIR__.DIRECTORY_SEPARATOR.$destination, "w"); // Destination location
	if (!is_resource($dest)) {
		return false;
	}		
	
	$opts =
            array(	'http'=>  array(
								'method' 			=> 'GET',
								'timeout' 			=> 28800, // set this to 8 hours so we dont timeout on big files				
								'header' 			=> array('Cache-Control: no-cache',
															 'Connection:close',
														   //'Host:'.$_SERVER['HTTP_HOST'], // maxmind does not like to be given a host
															 'Pragma:no-cache'),
								'user_agent' 		=> 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.87 Safari/537.36',
								'follow_location' 	=> true,
								'max_redirects'		=> 15,
								'ignore_errors' 	=> true,
								),
					'ssl' => array(
								'verify_peer' => false 
								)
				);
	$context = stream_context_create($opts);
	$src = fopen($url,'rb',false,$context);
	if (!is_resource($src)) {
		mylog('Failed to open url "'.$url.'"');
		fclose($dest);
		return false;
	}

	mylog('Opened '.$url);
	
	/* Read all data */
	$length = 0;
	while (!feof($src)) {
		$chunk = fread($src,4096);
		if ($chunk === false)
			break;
		fwrite($dest,$chunk);
		$length += strlen($chunk);
	}
	
	mylog('Got '.$length.' bytes');
	
	/* Finally, get the transfer status */
	$curlErr = '';
	$statusCode = 404;
	$contentLength = -1;
	$meta_data = stream_get_meta_data($src);
	$wrapper_data = $meta_data['wrapper_data'];
	//mylog(print_r($wrapper_data,true));
	foreach ($wrapper_data as $response) {
		mylog("'".$response."'");
		/* Status code */
		if (strtolower(substr($response, 0, 7)) == 'http/1.') {
			$cols = explode(' ',$response,3);
			$statusCode = intVal($cols[1]);
			$curlErr = $cols[2];
		} else 
		if (strtolower(substr($response, 0, 15)) == 'content-length:') {
			$contentLength = intVal(substr($response,15));
		}
	}	
	
	/* Check if we transferred everything */
	$ret = $statusCode === 200 && ($contentLength == -1 || $contentLength == $length);
	fclose($src);
	fclose($dest);

	mylog("Done. Download ".($ret === false ? ("failed - Cnt:".$contentLength." Xtr:".$length." Code:".$statusCode." Reason:".$curlErr):"succeeded"));

	return $ret;
}

/*
	Update databases
 **/
function updateDB($maximumAllowedAgeInDays = 30) {

	if (file_exists('ispipinfo.db') &&
		 (filemtime('ispipinfo.db') + ($maximumAllowedAgeInDays * 3600 * 24)) > time() &&
		file_exists('ipinfo.db') &&
		 (filemtime('ipinfo.db') + ($maximumAllowedAgeInDays * 3600 * 24)) > time()
		 ) {
		/* Everything up to date. */
		return true;
	}

	mylog("Need to update databases");
	
	/* If we are on a main php script, invoked by a client browser, post a 2nd plane update */
	if (!array_key_exists('dbupdate',$_GET) || $_GET['dbupdate'] !== 'now') {
		
		// Open socket connection to index.php
		$socketcon = fsockopen($_SERVER['HTTP_HOST'],80,$errorno,$errorstr,10);
		if($socketcon) {
			$socketdata = 	"GET /".$_SERVER['PHP_SELF']."?dbupdate=now HTTP 1.1\r\n".
							"Host: ".$_SERVER['HTTP_HOST']."\r\n".
							"User-Agent: curl\r\n". // So we are not counted as visitor
							"Connection: Close\r\n".
							"\r\n";
							
			fwrite($socketcon,$socketdata);
			// Normally you would get all the data back with fgets and wait until 
			// $socketcon reaches feof. In this case, we just do this:
			fclose($socketcon);
		}
		
		return true;
	}
	
	/* Use a lock to prevent parallel downloads and updates */
	$gul = fopen('dbipupdate.lock', 'c+b');
	if (!is_resource($gul)) 
		return false;
	if (!flock($gul, LOCK_EX | LOCK_NB /* Lock for writing. Noone can read it */ )) { 
		/* Unable to lock: Someone is already trying to update GEO */
		fclose($gul);
		return false;
	}

	$CityZIP = 'GeoIP2-City-CSV_Latest.zip';
	$CityUrl = 'https://download.maxmind.com/app/geoip_download?edition_id=GeoIP2-City-CSV&suffix=zip&license_key=Xo7NKEAimFLk';
	$ISPZIP = 'GeoIP2-ISP-CSV_Latest.zip';
	$ISPUrl = 'https://download.maxmind.com/app/geoip_download?edition_id=GeoIP2-ISP-CSV&suffix=zip&license_key=Xo7NKEAimFLk';
	
	/* If ISP database is NOT valid, try to update it */
	if (!file_exists('ispipinfo.db') ||
		 (filemtime('ispipinfo.db') + ($maximumAllowedAgeInDays * 3600 * 24)) < time()) {
		
		mylog('Trying to download ISP database');
		/* Try to download update. */
		if (downloadFile($ISPUrl,$ISPZIP)) {
			mylog('Done - Trying to compile ISP database');
			/* Succedded downloading file. Proceed to update database */
			compileISPDB($ISPZIP);
		}
	}

	/* If GEO database is NOT valid, try to update it */
	if (!file_exists('ipinfo.db') ||
		 (filemtime('ipinfo.db') + ($maximumAllowedAgeInDays * 3600 * 24)) < time()) {
		
		mylog('Trying to download GEO database');
		/* Try to download update. */
		if (downloadFile($CityUrl,$CityZIP)) {
			
			mylog('Done - Trying to compile GEO database');
			/* Succedded downloading file. Proceed to update database */
			compileGEODB($CityZIP);
		}
	}

	/* Release update lock */
	flock($gul, LOCK_UN);
	fclose($gul);
}

/* 
	Get ISP by IP info 
	$ip: ipv4 to query information for
	
	returns an array with information or FALSE
**/
function getISPInfo($ip) {
	mylog('getISPInfo:'.$ip);
	
	// If data not available, we can´t do it
	if (!file_exists('ispipinfo.db')) {
		mylog('getISPInfo: missing DB');
		return false;
	}
	
	/* Use a lock to prevent parallel updates */
	$fl = fopen('ispip.lock', 'c+b');
	if (is_resource($fl)) {
		if (!flock($fl, LOCK_SH /* Lock for reading */ )) { 
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
	while ($lo <= $hi) {
		/* Get index */
		$mid = (int)(($hi - $lo) / 2) + $lo;
		
		/* Read record and unpack it */
		fseek($f, $mid * 14);
		$r = fread($f, 14);
		
		/* 'VCCvvvv' */
		$cols = unpack('V1a/C1b/C1c/v1d/v1e/v1f/v1g',$r);
		mylog('[#'.$mid.']: ip:'.long2ip((float)$ip).' => ip:'.long2ip((float)$cols['a']).'/'.$cols['b']);
				
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
	if ($lo > 0) {
		--$lo;
	}
	
	/* Lets do some parsing - Read record and unpack it */
	fseek($f, $lo * 14);
	$r = fread($f, 14);
	fclose($f);
	$cols = unpack('V1a/C1b/C1c/v1d/v1e/v1f/v1g',$r);
	mylog('[#'.$lo.']: ip:'.long2ip((float)$ip).' => ip:'.long2ip((float)$cols['a']).'/'.$cols['b']);
	$mask = ~((1 << (32-$cols["b"]))-1);
	
	if (((int)(($ip ^ $cols["a"]) & $mask)) == 0) {
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
		if ($fl !== false) {
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
	if ($fl !== false) {
		flock($fl, LOCK_UN);
		fclose($fl);
	}	
	
	// No information found
	return false;
}	

function lookup_subdiv($continent_nr,$country_nr=0,$subdiv1_code="",$subdiv2_code="") {
	
	$last = filesize('subdivisions.db') / 80; /* 80 bytes per record */
	$f = fopen('subdivisions.db','rb');
	
	$lo = 0; 
	$hi = $last - 1;
	while ($lo <= $hi) {
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
		if ($cmp == 0) {
			$cmp = $country_nr - $cols['b'];
			if ($cmp == 0) {
				$cmp = strcmp($subdiv1_code, $cols['c']);
				if ($cmp == 0) {
					$cmp = strcmp($subdiv2_code, $cols['d']);
				}
			}
			
		}

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
	if ($lo > 0) {
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
	if ($cmp == 0) {
		$cmp = $country_nr - $cols['b'];
		if ($cmp == 0) {
			$cmp = strcmp($subdiv1_code, $cols['c']);
			if ($cmp == 0) {
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
function getGEOInfo($ip) {

	mylog('getGEOInfo:'.$ip);

	// If data not available, fail call
	if (!file_exists('ipinfo.db')) {
		mylog('getGEOInfo: missing DB');
		return false;
	}
	
	/* Use a lock to prevent parallel updates */
	$fl = fopen('geoip.lock', 'c+b');
	if (is_resource($fl)) {
		if (!flock($fl, LOCK_SH /* Lock for reading */ )) { 
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
	while ($lo <= $hi) {
		/* Get index */
		$mid = (int)(($hi - $lo) / 2) + $lo;
		
		/* Read record and unpack it */
		fseek($f, $mid * 24);
		$r = fread($f, 24);
		$cols = unpack('V1a/C1b/v1c/C1d/a8e/f1f/f1g',$r);
		
		mylog('[#'.$mid.']: ip:'.long2ip((float)$ip).' => ip:'.long2ip((float)$cols['a']).'/'.$cols['b']);

		
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
	if ($lo > 0) {
		--$lo;
	}
	
	/* Lets do some parsing - Read record and unpack it */
	fseek($f, $lo * 24);
	$r = fread($f, 24);
	fclose($f);
	$cols = unpack("V1a/C1b/v1c/C1d/a8e/f1f/f1g",$r);
	
	mylog('[#'.$lo.']: ip:'.long2ip((float)$ip).' => ip:'.long2ip((float)$cols['a']).'/'.$cols['b']);
	
	$mask = ~((1 << (32-$cols["b"]))-1);
	
	if (((int)(($ip ^ $cols["a"]) & $mask)) == 0) {
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
		if ($fl !== false) {
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
	if ($fl !== false) {
		flock($fl, LOCK_UN);
		fclose($fl);
	}	
	
	// No information found
	return false;
}	

/*
	Get the Client IP 
 **/
function getClientIP() {
    $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                // trim for safety measures
                $ip = trim($ip);
                // attempt to validate IP
                if (validate_ip($ip)) {
                    return $ip;
                }
            }
        }
    }
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
}

/**
 * Ensures an ip address is both a valid IP and does not fall within
 * a private network range.
 */
function validate_ip($ip)
{
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return false;
    }
    return true;
}

function mylog($txt) {
	/*
	$f = fopen("ispiplog.log","a");
	fwrite($f,$txt."\n");
	fclose($f);
	*/
}

/*
 * Log to the geoip.log
 */
function geoisplog($txt) {
	if (file_exists('geoisplog.log')) {
		$f = fopen("geoisplog.log","a");
		fwrite($f,$txt."\n");
		fclose($f);
	}
}


/* Update databases, if needed */
updateDB();

$ip  = getClientIP();
$geo = getGEOInfo($ip);
$isp = getISPInfo($ip);

geoisplog(
	'ip:"'.$ip.'",'.
	'isp:"'.$isp['isp'].'",'.
	'city:"'.$geo['city'].'",'.
	'province:"'.$geo['province'].'",'.
	'country:"'.$geo['country'].'",'.
	'country_code:"'.$geo['country_code'].'",'.
	'continent:"'.$geo['continent'].'",'.
	'continent_code:"'.$geo['continent_code'].'",'.
	'subdiv1:"'.$geo['subdiv1'].'",'.
	'subdiv1_code:"'.$geo['subdiv1_code'].'",'.
	'subdiv2:"'.$geo['subdiv2'].'",'.
	'subdiv2_code:"'.$geo['subdiv2_code'].'"');

$result = array(
	'ip' => $ip,
	'isp' => $isp['isp'],
	'city' => $geo['city'],
	'province' => $geo['province'],
	'country' => $geo['country'],
	'country_code' => $geo['country_code'],
	'continent' => $geo['continent'],
	'continent_code' => $geo['continent_code'],
	'subdiv1' => $geo['subdiv1'],
	'subdiv1_code' => $geo['subdiv1_code'],
	'subdiv2' => $geo['subdiv2'],
	'subdiv2_code' => $geo['subdiv2_code']
	);
	

if (in_array($isp['isp'], $allowedIsps) &&
	!in_array($geo['city'], $blacklistedCities) &&
	!in_array($geo['province'], $blacklistedProvinces) &&
	!in_array($geo['subdiv1_code'], $blacklistedSubDivs1) &&
	!in_array($geo['subdiv2_code'], $blacklistedSubDivs2) &&
	!in_array($geo['country'], $blacklistedCountries) &&
	!in_array($geo['continent'], $blacklistedContinents))
{
	$result = array('goClean' => false);
}
else
{
	$result = array('goClean' => true);
}
	
// Make sure to return a application/json answer, otherwise, 
header("Content-type: application/json");

// Make sure file is not cached (as it happens for example on iOS devices)
header("Expires: Mon, 01 Jan 1985 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0, max-age=0", false);
header("Pragma: no-cache");

// Disable Access Control
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET'); 
	
echo json_encode($result);	

?>