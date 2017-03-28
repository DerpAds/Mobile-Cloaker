<?php

/*
Quoted from PHP manual for flock:

		I just spent some time (again) to understand why a reading with file_get_contents() 
	and file was returning me an empty string "" or array() whereas the file was existing 
	and the contents not empty. 

		In fact, i was locking file when writing it (file_put_contents third arg) but not 
	testing if file was locked when reading it (and the file was accessed a lot). 

		So, please pay attention that file_get_contents(), file() and maybe others php files
	functions are going to return empty data like if the contents of the file was an empty
	string. 

		To avoid this problem, you have to set a LOCK_SH on your file before reading it (and 
	then waiting if locked). 
*/

function file_get_contents_shared_access($path, $waitIfLocked = true) 
{ 

	/* If the file does not exist, return now */
    if(!file_exists($path))
		return false;

	clearstatcache();
	$fsize = filesize($path);
	
	/* Opem the file */
	$fo = fopen($path, 'r'); 
	
	/* Lock it for shared access */
	$locked = flock($fo, LOCK_SH, $waitIfLocked); 
	
	/* If we were unable to lock it, just fail */
	if(!$locked) {
		fclose($fo);
		return false; 
	}
	
	/* Retry at least 10 times in case of errors */
	$retries = 10;
	while (true) {
		/* Get the file contents */
		$cts = file_get_contents($path); 
		$retries --;

		/* In case of errors, add an small delay and retry */
		if (($cts === false || (strlen($cts) == 0 && $fsize > 0)) && $retries > 0) {
			/* Perform an small random delay */
			usleep(rand(1, 10000)); 
		} else {
			/* Done */
			break;
		}
	};
	
	/* Unlock access */
	flock($fo, LOCK_UN); 
	
	/* Close the file */
	fclose($fo); 
	
	/* Return the contents */
	return $cts; 
} 

?>