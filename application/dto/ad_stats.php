<?php
class Ad_stats {
    public $referrer_blacklist_blocked = 0;
    public $referrer_blacklist_allowed = 0;
    public $referrer_whitelist_blocked = 0;
    public $referrer_whitelist_allowed = 0;
    public $parameter_blocked = 0;
    public $parameter_allowed = 0;
    public $parameter_missing = 0;
    public $referrer_parameter_blocked = 0;
    public $referrer_parameter_allowed = 0;
    public $referrer_parameter_missing = 0;
    public $useragent_mobile = 0;
    public $geo_allowed = 0;
    public $geo_blocked = 0;
    public $allowed_traffic = 0;
    public $total = 0;
    public $log_file_error = array();
}