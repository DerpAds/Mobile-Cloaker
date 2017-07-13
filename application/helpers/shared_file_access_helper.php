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

function file_get_lines_shared_access($path, $waitIfLocked = true, $lines = 100)
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

    $cts = "";
    $readed = 0;
    while (!feof($fo) && $readed < $lines) {
        if ($readed>0) $cts .= "\n";
        $cts .= fgets($fo);
        $readed++;
    }

    /* Unlock access */
    flock($fo, LOCK_UN);

    /* Close the file */
    fclose($fo);

    /* Return the contents */
    return $cts;
}

function process_shared_file_per_line($path, $line_function, $waitIfLocked = true, $ignore_first_line = false)
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

    $header = ($ignore_first_line && !feof($fo))?fgets($fo):false;

    while (!feof($fo)) {
        $line = fgets($fo);
        $line_function($line);
        unset($line);
    }

    /* Unlock access */
    flock($fo, LOCK_UN);

    /* Close the file */
    fclose($fo);

    return $header;
}

function file_get_last_lines_shared_access($path, $waitIfLocked = true, $lines = 100)
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
    if (!feof($fo)) {
        $first = fgets($fo);
        $cts = tail($fo, $lines);
        if (strpos($cts,$first) === FALSE) {
            $cts = $first."\n".$cts;
        }
    }
    // Remove empty lines
    $cts = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $cts);

    /* Unlock access */
    flock($fo, LOCK_UN);

    /* Close the file */
    fclose($fo);

    /* Return the contents */
    return $cts;
}


function tail($f, $lines = 10, $buffer = 4096)
{
     // Jump to last character
    fseek($f, -1, SEEK_END);

    // Read it and adjust line number if necessary
    // (Otherwise the result would be wrong if file doesn't end with a blank line)
    if(fread($f, 1) != "\n") $lines -= 1;

    // Start reading
    $output = '';
    $chunk = '';

    // While we would like more
    while(ftell($f) > 0 && $lines >= 0)
    {
        // Figure out how far back we should jump
        $seek = min(ftell($f), $buffer);

        // Do the jump (backwards, relative to where we are)
        fseek($f, -$seek, SEEK_CUR);

        // Read a chunk and prepend it to our output
        $output = ($chunk = fread($f, $seek)).$output;

        // Jump back to where we started reading
        fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

        // Decrease our line counter
        $lines -= substr_count($chunk, "\n");
    }

    // While we have too many lines
    // (Because of buffer size we might have read too many)
    while($lines++ < 0)
    {
        // Find first newline and remove all text before that
        $output = substr($output, strpos($output, "\n") + 1);
    }

    return $output;
}


?>