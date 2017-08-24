<?php

class Log_data
{
    public $log_id=0;
    public $log_type=LOG_TYPE_JS_LOG; /* 1- JS LOG (default)*/
    public $campaign_id = "";
    public $client_guid = "";
    public $user_agent = "";
    public $date_registered = null;
    public $date_created = null;
    public $remote_ip = "";
    public $remote_port = "";
    public $isp = "";
    public $headers = "";
    public $message = "";

    function __construct($row)
    {
        $this->log_id = $row->log_id;
        $this->log_type = $row->log_type;
        $this->campaign_id = $row->campaign_id;
        $this->client_guid = $row->client_guid;
        $this->user_agent = $row->user_agent;
        $this->date_registered = DateTime::createFromFormat('Y-m-d H:i:s', $row->date_registered);
        $this->date_created = DateTime::createFromFormat('Y-m-d H:i:s', $row->date_created);
        $this->remote_ip = $row->remote_ip;
        $this->remote_port = $row->remote_port;
        $this->isp = $row->isp;
        $this->headers = $row->headers;
        $this->message = $row->message;
    }
}

