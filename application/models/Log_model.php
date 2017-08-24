<?php
class Log_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
        $this->load->dto("log_data");
        $this->load->helper("ad");
    }

    /**
     * @param Log_data $log_data
     */
    public function insert($log_data) {
        $data = array();
        $data["campaign_id"] = $log_data->campaign_id;
        $data["client_guid"] = $log_data->client_guid;
        $data["user_agent"] = $log_data->user_agent;
        $data["date_registered"] = date('Y-m-d H:i:s',$log_data->date_registered->getTimestamp());
        $data["date_created"] = date('Y-m-d H:i:s',$log_data->date_created->getTimestamp());
        $data["remote_ip"] = $log_data->remote_ip;
        if (!empty($log_data->remote_port)) $data["remote_port"] = $log_data->remote_port;
        $data["isp"] = $log_data->isp;
        $data["headers"] = $log_data->headers;
        $data["message"] = $log_data->message;
        $this->db->insert($this->get_log_table_name($log_data->log_type),$data);
    }

    public function reset_log($log_type) {
        $table = $this->get_log_table_name($log_type);
        $database = "awstst1_ad_cloaker";
        $msg = "";
        $this->execute("DROP TABLE IF EXISTS `$database`.`$table`;");
        $msg =  $this->db->_error_message()."<br/>";
        $this->execute("
            CREATE TABLE `$database`.`$table` ( 
                `log_id` BIGINT NOT NULL AUTO_INCREMENT , 
                `campaign_id` VARCHAR(250) NOT NULL , 
                `client_guid` VARCHAR(50) NOT NULL , 
                `date_registered` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , 
                `date_created` TIMESTAMP NULL , 
                `remote_ip` VARCHAR(20) NULL , 
                `remote_port` INT NULL , 
                `user_agent` TEXT NULL , 
                `isp` TEXT NULL , 
                `headers` TEXT NULL , 
                `message` TEXT NULL , 
                PRIMARY KEY (`log_id`), 
                INDEX `ixClientLogGuid` (`client_guid`), 
                INDEX (`client_guid`)
            ) ENGINE = InnoDB;");
        $msg .=  $this->db->_error_message()."<br/>";

        return $msg;
    }

    public function clear_log($campaign_id, $log_type) {
        $table = $this->get_log_table_name($log_type);
        $this->db->where("campaign_id",$campaign_id);
        $this->db->delete($table);
    }

    public function query($campaign_id, $log_type, $all_rows = false) {
        $table = $this->get_log_table_name($log_type);
        $result = array();
        try {
            $this->db->where("campaign_id",$campaign_id);
            if (!$all_rows) {
                $this->db->order_by("log_id", "desc");
                $this->db->limit(100);
            }
            $query= $this->db->get($table);
            $rows = $query->result();
            $result = array_map(function($row) {return new Log_data($row);}, $rows);
            if (!$all_rows) {
                $result = array_reverse($result);
            }
        } catch (Exception $ex) {
            $result = array();
        }
        return $result;
    }

    private function execute($query) {
        try{
            $this->db->query($query);
        } catch (Exception $ex) {
            print_r($ex);
        }
    }

    public function get_log_name($log_type) {
        if ($log_type == LOG_TYPE_JS_LOG) return "JS Log";
        return "Ad log";

    }

    private function get_log_table_name($log_type)
    {
        if ($log_type == LOG_TYPE_JS_LOG) return "ad_js_log";
        return "ad_log";
    }

}