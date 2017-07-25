<?php

class Server_settings_model extends CI_Model
{


    function __construct()
    {
        parent::__construct();
        $this->load->dto("server_settings");
    }

    /**
     * @return server_settings
     */
    public function get() {
        $settings = new server_settings();
        try {
            $content = file_get_contents($this->get_settings_filename());
            $decoded = json_decode($content);
            foreach ($decoded as $key => $value) $settings->{$key} = $value;
        } catch (Exception $ex) {
        }
        return $settings;
    }

    /**
     * @param server_settings $settings
     */
    public function save($settings) {
        file_put_contents($this->get_settings_filename(), json_encode($settings));
    }

    private function get_settings_filename()
    {
        return SETTINGS_PATH ."server-settings.json";
    }

}