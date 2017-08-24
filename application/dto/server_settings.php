<?php

class server_settings
{
    public $landing_page_referer_blacklist=array();
    public $landing_page_referer_whitelist=array();
    public $landing_js_referer_blacklist=array();
    public $landing_js_referer_whitelist=array();
    public $landing_page_cloak_redirect_enabled=false;
    public $landing_page_cloak_redirect_url="";
    public $cookies_dropping_randomize = true;
    public $cookies_dropping_delay = 0;
    public $cookies_dropping_interval = 0;
}