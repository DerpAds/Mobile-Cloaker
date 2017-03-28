<?php

require_once("include/csvlib.inc");

require_once("include/shared_file_access.inc");
	
$file = file_get_contents_shared_access("demo.csv");
$ret = detect_csv_chars($file);

print_r($ret);

?>