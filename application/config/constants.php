<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Display Debug backtrace
|--------------------------------------------------------------------------
|
| If set to TRUE, a backtrace will be displayed along with php errors. If
| error_reporting is disabled, the backtrace will not display, regardless
| of this setting
|
*/
defined('SHOW_DEBUG_BACKTRACE') OR define('SHOW_DEBUG_BACKTRACE', TRUE);

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
defined('FILE_READ_MODE')  OR define('FILE_READ_MODE', 0644);
defined('FILE_WRITE_MODE') OR define('FILE_WRITE_MODE', 0666);
defined('DIR_READ_MODE')   OR define('DIR_READ_MODE', 0755);
defined('DIR_WRITE_MODE')  OR define('DIR_WRITE_MODE', 0755);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/
defined('FOPEN_READ')                           OR define('FOPEN_READ', 'rb');
defined('FOPEN_READ_WRITE')                     OR define('FOPEN_READ_WRITE', 'r+b');
defined('FOPEN_WRITE_CREATE_DESTRUCTIVE')       OR define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); // truncates existing file data, use with care
defined('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE')  OR define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b'); // truncates existing file data, use with care
defined('FOPEN_WRITE_CREATE')                   OR define('FOPEN_WRITE_CREATE', 'ab');
defined('FOPEN_READ_WRITE_CREATE')              OR define('FOPEN_READ_WRITE_CREATE', 'a+b');
defined('FOPEN_WRITE_CREATE_STRICT')            OR define('FOPEN_WRITE_CREATE_STRICT', 'xb');
defined('FOPEN_READ_WRITE_CREATE_STRICT')       OR define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');

/*
|--------------------------------------------------------------------------
| Exit Status Codes
|--------------------------------------------------------------------------
|
| Used to indicate the conditions under which the script is exit()ing.
| While there is no universal standard for error codes, there are some
| broad conventions.  Three such conventions are mentioned below, for
| those who wish to make use of them.  The CodeIgniter defaults were
| chosen for the least overlap with these conventions, while still
| leaving room for others to be defined in future versions and user
| applications.
|
| The three main conventions used for determining exit status codes
| are as follows:
|
|    Standard C/C++ Library (stdlibc):
|       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
|       (This link also contains other GNU-specific conventions)
|    BSD sysexits.h:
|       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
|    Bash scripting:
|       http://tldp.org/LDP/abs/html/exitcodes.html
|
*/
defined('EXIT_SUCCESS')        OR define('EXIT_SUCCESS', 0); // no errors
defined('EXIT_ERROR')          OR define('EXIT_ERROR', 1); // generic error
defined('EXIT_CONFIG')         OR define('EXIT_CONFIG', 3); // configuration error
defined('EXIT_UNKNOWN_FILE')   OR define('EXIT_UNKNOWN_FILE', 4); // file not found
defined('EXIT_UNKNOWN_CLASS')  OR define('EXIT_UNKNOWN_CLASS', 5); // unknown class
defined('EXIT_UNKNOWN_METHOD') OR define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT')     OR define('EXIT_USER_INPUT', 7); // invalid user input
defined('EXIT_DATABASE')       OR define('EXIT_DATABASE', 8); // database error
defined('EXIT__AUTO_MIN')      OR define('EXIT__AUTO_MIN', 9); // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX')      OR define('EXIT__AUTO_MAX', 125); // highest automatically-assigned error code


defined('SITE_COPYRIGHT') OR define("SITE_COPYRIGHT", "&copy; ".date("Y")." Adcrush Media");

// Folder Structure
defined("DBS_PATH") or define("DBS_PATH", APPPATH."data/dbs/");
defined("ADS_LOGS_PATH") or define("ADS_LOGS_PATH", APPPATH."logs/");
defined("ADS_CONFIG_PATH") or define("ADS_CONFIG_PATH", APPPATH."data/ads/");
defined("ADS_TEMPLATES_PATH") or define("ADS_TEMPLATES_PATH", APPPATH."data/profiles/htmltemplates/");
defined("ADS_PROFILES_PATH") or define("ADS_PROFILES_PATH", APPPATH."data/profiles/");

defined("F_APPS_IOS_BASE_FILENAME") or define("F_APPS_IOS_BASE_FILENAME", APPPATH."data/config/f_apps_ios_");
defined("F_APPS_ANDROID_BASE_FILENAME") or define("F_APPS_ANDROID_BASE_FILENAME",APPPATH."data/config/f_apps_android_");
defined("F_SITE_BASE_FILENAME") or define("F_SITE_BASE_FILENAME",APPPATH."data/config/f_site_");
defined("F_SITE_ID_BASE_FILENAME") or define("F_SITE_ID_BASE_FILENAME", APPPATH."data/config/f_siteid_");
defined("CSV_FILE_SUFFIX") or define("CSV_FILE_SUFFIX", ".csv");
defined("SITE_VERSION") or define("SITE_VERSION","5.6-rev1");

defined('COOKIES_DROPPING_METHOD_CLOAKER') or define('COOKIES_DROPPING_METHOD_CLOAKER','cloaker');
defined('COOKIES_DROPPING_METHOD_POP_UNDER') or define('COOKIES_DROPPING_METHOD_POP_UNDER','popunder');
defined('COOKIES_DROPPING_METHOD_LANDING_PAGE') or define('COOKIES_DROPPING_METHOD_LANDING_PAGE','landing');
