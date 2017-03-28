<?php

/*
	This function tries to detect the delimiter and separator being used by the CSV files
	Returns an array with the result ('separator' => sep, 'delimiter' => delim)
 */
function detect_csv_chars($csvstring, $delimiters = array(',',';',"\t",'|')) {

	$separator = "\n";
	
	$len = strlen($csvstring);
	$rowStats = array();


    $quoted = false;
    $firstChar = true;
	$pos = 0;

	$prevchar = ' ';
	$character = ' ';
	
	// Count of each delimiter to 0
	$delimitersCount = array(); 
	foreach($delimiters as $delim) {
		$delimitersCount[$delim]= 0;
	}
	
	//echo "CSV:".$csvstring."</br>";
	//echo "Len:".$len."</br>";
	
    while ($pos < $len) {
	
		$prevchar = $character;
        $character = $csvstring[$pos];
		$pos++;

		// echo ">".$character."<(".ord($character).") first:".($firstChar == true ? 'Y' : 'N')." Quoted:".($quoted == true ? 'Y' : 'N')."</br>";
		
        switch ($character) {
            case '"':
                if ($quoted) {
                    if ($csvstring[$pos] != '"') 	// Value is quoted and 
													// current character is " and next character is not ".
                        $quoted = false;
                    else
                        $pos++; 					// Value is quoted and current and 
													// next characters are "" - read (skip) peeked qoute.
                } else {
                    if ($firstChar) 				// Set value as quoted only if this quote is the 
													// first char in the value.
                        $quoted = true;				//
                }
				
				$firstChar = false;
                break;
				
			case "\r":
            case "\n":
				
                if (!$quoted) {
					
					if ($firstChar && (
						($prevchar == "\r" && $character == "\n") ||
						($prevchar == "\n" && $character == "\r"))
						) {
						$separator = $prevchar.$character;
					} else
					if (!$firstChar && $prevchar != "\r" && $prevchar != "\n") {
						$separator = $character;
					}
					
					if ($prevchar != "\r" && $prevchar != "\n" &&
						($character == "\r" || $character == "\n")) {
						
						// Store row stats into array
						$rowStats[] = $delimitersCount;
					
						// New row: Count of each delimiter to 0
						$delimitersCount = array(); 
						foreach($delimiters as $delim) {
							$delimitersCount[$delim]= 0;
						}
					}
					
					$firstChar = true;
					
					// echo "new line</br>";
                } else {
					$firstChar = false;
				}
                break;
				
            default:
                if (!$quoted)
                {
					if (in_array($character,$delimiters)) {
						$delimitersCount[$character] ++;
                        $firstChar = true;
                    } else {
						$firstChar = false;
					}
                } else {
					$firstChar = false;
				}
                break;
        }
    }

	// print_r($rowStats);
	
	if (count($rowStats) == 0)
		return array('separator' => $separator, 'delimiter' => ',');
		
	// We now know the delimiter count for each row. The one that is most stable wins
	$candidates = array();
	foreach($delimiters as $delim) {
		$candidates[$delim]= true;
	}

	// Evaluate, to find out the stable candidate
	foreach($rowStats as $rowStat) {
		foreach($delimiters as $delim) {
			if ($rowStat[$delim] != $rowStats[0][$delim]) {
				$candidates[$delim] = false;
			}
		}
	}
	
	// Select the best one
	$selDelim = ',';
	$selDelimCount = 0;
	foreach($delimiters as $delim) {
		if ($candidates[$delim] &&
			$rowStats[0][$delim] > $selDelimCount) {
			$selDelim = $delim;
			$selDelimCount = $rowStats[0][$delim];
		}
	}
	
    return array('separator' => $separator, 'delimiter' => $selDelim);
}


/*
Like some other users here noted, str_getcsv() cannot be used if you want to comply with either the RFC or with most spreadsheet tools like Excel or Google Docs.

These tools do not escape commas or new lines, but instead place double-quotes (") around the field. If there are any double-quotes in the field, these are escaped with another double-quote (" becomes ""). All this may look odd, but it is what the RFC and most tools do ... 

For instance, try exporting as .csv a Google Docs spreadsheet (File > Download as > .csv) which has new lines and commas as part of the field values and see how the .csv content looks, then try to parse it using str_getcsv() ... it will spectacularly regardless of the arguments you pass to it.

Here is a function that can handle everything correctly, and more:

- doesn't use any for or while loops,
- it allows for any separator (any string of any length),
- option to skip empty lines,
- option to trim fields,
- can handle UTF8 data too (although .csv files are likely non-unicode).
*/

// returns a two-dimensional array or rows and fields
function parse_csv ($csv_string, $delimiter = "", $skip_empty_lines = true, $trim_fields = true)
{
	// Remove the UTF8 marker, if present
	$bom = pack('CCC', 0xEF, 0xBB, 0xBF);
	if (strncmp($csv_string, $bom, 3) === 0) {
		$csv_string = substr($csv_string, 3);
	}

	// If asked for autodetection, perform it
	if ($delimiter == "") {
		$ret = detect_csv_chars($csv_string);
		$delimiter = $ret['delimiter'];
	}
	
	// Parse the CSV
    return array_map(
        function ($line) use ($delimiter, $trim_fields) {
            return array_map(
                function ($field) {
                    return str_replace('!!_!!', '"', utf8_decode(urldecode($field)));
                },
                $trim_fields ? array_map('trim', explode($delimiter, $line)) : explode($delimiter, $line)
            );
        },
        preg_split(
            $skip_empty_lines ? ($trim_fields ? '/( *\R)+/s' : '/\R+/s') : '/\R/s',
            preg_replace_callback(
                '/"(.*?)"/s',
                function ($field) {
                    return urlencode(utf8_encode($field[1]));
                },
                $enc = preg_replace('/(?<!")""/', '!!_!!', $csv_string)
            )
        )
    );
}


/*
After using several methods in the past to create CSV strings without using files (disk IO sucks), I finally decided it's time to write a function to handle it all. This function could use some cleanup, and the variable type test might be overkill for what is needed, I haven't thought about it too much. 

Also, I took the liberty of replacing fields with certain data types with strings which I find much easier to work with. Some of you may not agree with those. Also, please note that the type "double" or float has been coded specifically for two digit precision because if I am using a float, it's most likely for currency. 

I am sure some of you out there would appreciate this function.
*/

function str_putcsv($array, $delimiter = ';', $enclosure = '"', $terminator = "\n", $encloseAll = false) { 

	$delimiter_esc = preg_quote($delimiter, '/');
	$enclosure_esc = preg_quote($enclosure, '/');

	// First convert associative array to numeric indexed array 
	foreach ($array as $key => $value) $workArray[] = $value; 

	$returnString = '';                 # Initialize return string 
	$arraySize = count($workArray);     # Get size of array 
	
	for ($i=0; $i<$arraySize; $i++) { 
		// Nested array, process nest item 
		if (is_array($workArray[$i])) { 
			$returnString .= str_putcsv($workArray[$i], $delimiter, $enclosure, $terminator, $encloseAll); 
		} else { 
			switch (gettype($workArray[$i])) { 
				// Manually set some strings 
				case "NULL":     $_spFormat = ''; break; 
				case "boolean":  $_spFormat = ($workArray[$i] == true) ? 'true': 'false'; break; 
				// Make sure sprintf has a good datatype to work with 
				case "integer":  $_spFormat = '%i'; break; 
				case "double":   $_spFormat = '%0.2f'; break; 
				case "string":   $_spFormat = '%s'; break; 
				// Unknown or invalid items for a csv - note: the datatype of array is already handled above, assuming the data is nested 
				case "object": 
				case "resource": 
				default:         $_spFormat = ''; break; 
			} 
			
			// Format field
			$field = sprintf($_spFormat, $workArray[$i]); 
			
			// Enclose fields containing $delimiter, $enclosure or whitespace
			if ( $encloseAll || preg_match( "/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field ) ) {
				$field = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure;
			}
			
			// Add it to the string
			$returnString .= $field. (($i < ($arraySize-1)) ? $delimiter : $terminator); 
		} 
	} 
	// Done the workload, return the output information 
	return $returnString; 
} 

?>