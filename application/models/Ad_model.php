<?php
class Ad_model extends CI_Model
{
    private $log_lines = 100;

    function __construct()
    {
        parent::__construct();
        $this->load->dto("ad_data");
        $this->load->dto("ad_stats");
        $this->load->helper("ad");
    }

    public function get($id)
    {
        $ad_config_path = $this->ad_config_path($id);
        if (!file_exists($ad_config_path)) return null;
        $config = $this->process_ad_config($ad_config_path);
        if ($config == null) return null;
        $ad_data = new Ad_data($id, $config);
        // Try to render the html template or load the clean ad from the path
        if (!empty($ad_data->HTMLTemplate)) {
            $ad_data->resultHtml = renderHTMLTemplateFilename($this->ad_template_path($ad_data->HTMLTemplate), $ad_data->HTMLTemplateValues);
        } else {
            $path = $this->ad_clean_html_path($id);
            if (file_exists($path)) {
                $ad_data->resultHtml = file_get_contents($path);
            } else {
                $ad_data->resultHtml = "";
            }
        }
        $ad_url = site_url("ad/view/$id");
        $ad_data->campainID = $id;
        $ad_data->tag_code = (array_key_exists("OutputMethod", $ad_data) && $ad_data["OutputMethod"] === "JS")
            ? htmlentities("<script type=\"text/javascript\" src=\"$ad_url\"></script>")
            : $ad_url;
        return $ad_data;
    }

    public function new_profile() {
        $profile_data = new Ad_data();
        $profile_data->is_profile = true;
        return $profile_data;
    }

    public function get_profile($id)
    {
        $profile_config_path = $this->profile_path($id);
        if (!file_exists($profile_config_path)) return null;
        $config = $this->process_ad_config($profile_config_path);
        if ($config == null) return null;
        $profile_data = new Ad_data($id, $config);
        $profile_data->profile_name = $id;
        $profile_data->is_profile = true;
        $profile_data->HTMLTemplateValues = $this->ad_model->get_template_values($profile_data->HTMLTemplate);
        $profile_data->campainID = "";
        $profile_data->tag_code = "";
        return $profile_data;
    }

    public function get_list()
    {
        $files = scandir(ADS_CONFIG_PATH);
        $result = array();
        foreach ($files as $filename) {
            if (strpos($filename, ".cleanad.html") !== false) {
                $campaignID = substr($filename, 0, strpos($filename, ".cleanad.html"));

                if (!array_key_exists($campaignID, $result)) {
                    $result[$campaignID] = $this->get($campaignID);
                }

                array_push($result[$campaignID]->config_files, $filename);
            }
            if (strpos($filename, ".config.txt") !== false) {
                $campaignID = substr($filename, 0, strpos($filename, ".config.txt"));

                if (!array_key_exists($campaignID, $result)) {
                    $result[$campaignID] = $this->get($campaignID);
                }
                array_push($result[$campaignID]->config_files, $filename);
            }
        }
        return $result;
    }

    public function delete($id)
    {
        $files = array();
        $files[] = $this->ad_config_path($id);
        $files[] = $this->ad_clean_html_path($id);
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function delete_profile($id)
    {
        $profileFilename = $this->profile_path($id);
        if (file_exists($profileFilename))
        {
            unlink($profileFilename);
        }
    }

    public function copy($original_id, $new_id)
    {
        if (empty($new_id) || empty($original_id)) return false;
        $copies = array();
        $copies[$this->ad_config_path($original_id)] = $this->ad_config_path($new_id);
        $copies[$this->ad_clean_html_path($original_id)] = $this->ad_clean_html_path($new_id);
        foreach ($copies as $original => $new) {
            if (file_exists($new)) return false;
        }
        foreach ($copies as $original => $new) {
            if (file_exists($original)) {
                copy($original, $new);
            }
        }
        return true;
    }

    public function copy_profile($original_id, $new_id)
    {
        $profileFilename = $this->profile_path($original_id);
        $newProfileFilename = $this->profile_path($new_id);

        if (!file_exists($newProfileFilename))
        {
            copy($profileFilename, $newProfileFilename);
        }
        return true;
    }

    private function ad_config_path($id)
    {
        return ADS_CONFIG_PATH . $id . ".config.txt";
    }

    private function ad_clean_html_path($id)
    {
        return ADS_CONFIG_PATH . $id . ".cleanad.html";
    }

    private function ad_template_path($template_name)
    {
        return ADS_TEMPLATES_PATH . $template_name . ".html";
    }

    private function ad_profile_path($ad)
    {
        return $this->profile_path($ad->profile_name);
    }

    private function profile_path($id)
    {
        return ADS_PROFILES_PATH . $id . ".profile";
    }

    public function get_log_names()
    {
        return array("adlog",
            "allowed_traffic",
            "mbotlog",
            "traffic_logger");
    }

    private function log_files($id)
    {
        $files = array();
        foreach ($this->get_log_names() as $type) {
            $files[] = $this->get_log_filename($id, $type);
        }
        return $files;
    }

    function get_templates()
    {
        $files = scandir(ADS_TEMPLATES_PATH );
        $result = array();

        foreach ($files as $filename)
        {
            if (strpos($filename, ".html") !== false)
            {
                $htmlTemplateName = substr($filename, 0, strpos($filename, ".html"));
                if ($htmlTemplateName != "index") {
                    $result[$htmlTemplateName] = $htmlTemplateName;
                }
            }
        }

        return $result;
    }


    public function get_template_values($template_name)
    {
        $template = file_get_contents($this->ad_template_path($template_name));

        $matches = array();

        preg_match_all("/{{.*}}/", $template, $matches);

        $result = array();

        foreach ($matches[0] as $templateValue) {
            $result[$templateValue] = "";
        }

        return $result;
    }

    public function save_ad($id, $config, $clean_html = null)
    {
        $configFilename = $this->ad_config_path($id);
        $configFileContents = "";
        foreach ($config as $key => $value) {
            $configFileContents .= "$key: $value\r\n";
        }
        file_put_contents($configFilename, $configFileContents);
        if ($clean_html == null) return;
        file_put_contents($this->ad_clean_html_path($id), $clean_html);
    }

    public function save_profile($id, $config)
    {
        $configFilename = $this->profile_path($id);

        $configFileContents = "";

        foreach ($config as $key => $value) {
            $configFileContents .= "$key: $value\r\n";
        }
        file_put_contents($configFilename, $configFileContents);
    }

    private function process_ad_config($filename)
    {
        try {
            $result = array();
            $f = fopen($filename, "r");
            while (($line = fgets($f)) !== false) {
                $colonIndex = strpos($line, ":");
                if ($colonIndex !== false) {
                    $key = trim(substr($line, 0, $colonIndex));
                    $value = trim(str_replace(PHP_EOL, '', substr($line, $colonIndex + 1)));
                    $result[$key] = $value;
                }
            }
            fclose($f);
            return $result;
        } catch (Exception $ex) {
            return null;
        }
    }


    private function get_log_filename($id, $log_type) {
        return ADS_LOGS_PATH . "$log_type.$id.log.csv";
    }

    public function get_log($id, $log_type)
    {
        $log_filename = $this->get_log_filename($id, $log_type);
        if (!file_exists($log_filename)) {
            return array();
        }
        $this->load->helper("csv_helper");
        $this->load->helper("shared_file_access_helper");
        try {

            $content = file_get_last_lines_shared_access($log_filename);
            if ($content !== false) {
                return parse_csv($content, ",");
            }
        }  catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function delete_log($id, $log_type)
    {
        $log_filename = $this->get_log_filename($id, $log_type);
        if (file_exists($log_filename)) {
            unlink($log_filename);
            return true;
        }
        return false;
    }

    public function delete_logs($id)
    {
        $deleted = false;
        foreach ($this->get_log_names() as $log_type) {
            if ($this->delete_log($id,$log_type)) $deleted = true;
        }
        return $deleted;
    }

    public function get_profiles() {
        $files = scandir(ADS_PROFILES_PATH);
        $result = array();
        foreach ($files as $filename)
        {
            if (strpos($filename, ".profile") !== false)
            {
                $profileName = substr($filename, 0, strpos($filename, ".profile"));
                $result[$profileName] = $filename;
            }
        }
        return $result;
    }

    /*public function get_stats($id)
    {
        $this->load->helper("shared_file_access_helper");
        $log_files = $this->log_files($id);

        $stats = array();

        $stats["REFERRER_BLACKLIST_BLOCKED"] = 0;
        $stats["REFERRER_BLACKLIST_ALLOWED"] = 0;
        $stats["REFERRER_WHITELIST_BLOCKED"] = 0;
        $stats["REFERRER_WHITELIST_ALLOWED"] = 0;
        $stats["PARAMETER_BLOCKED"] = 0;
        $stats["PARAMETER_ALLOWED"] = 0;
        $stats["PARAMETER_MISSING"] = 0;
        $stats["REFERRER_PARAMETER_BLOCKED"] = 0;
        $stats["REFERRER_PARAMETER_ALLOWED"] = 0;
        $stats["REFERRER_PARAMETER_MISSING"] = 0;
        $stats["USERAGENT_MOBILE"] = 0;
        $stats["GEO_ALLOWED"] = 0;
        $stats["GEO_BLOCKED"] = 0;
        $stats["ALLOWED_TRAFFIC"] = 0;
        $stats["TOTAL"] = 0;
        $stats["LOG_FILES_SIZE_ERROR"] = array();
        foreach ($log_files as $log_filename)
        {
            try {
                $log = file_get_last_lines_shared_access($log_filename);
                if ($log !== false) {
                    if (strpos($log_filename, "mbotlog") !== false)
                    {
                        $stats["REFERRER_BLACKLIST_BLOCKED"] = substr_count($log, "CHECK:REFERRER_BLACKLIST_BLOCKED:");
                        $stats["REFERRER_BLACKLIST_ALLOWED"] = substr_count($log, "CHECK:REFERRER_BLACKLIST_ALLOWED:");
                        $stats["REFERRER_WHITELIST_BLOCKED"] = substr_count($log, "CHECK:REFERRER_WHITELIST_BLOCKED:");
                        $stats["REFERRER_WHITELIST_ALLOWED"] = substr_count($log, "CHECK:REFERRER_WHITELIST_ALLOWED:");
                        $stats["PARAMETER_BLOCKED"] = $this->get_all_next_level_parameters_values($log, "CHECK:PARAMETER_BLOCKED:"); //substr_count($log, "CHECK:PARAMETER_BLOCKED:");
                        $stats["PARAMETER_ALLOWED"] = $this->get_all_next_level_parameters_values($log, "CHECK:PARAMETER_ALLOWED:"); //substr_count($log, "CHECK:PARAMETER_ALLOWED:");
                        $stats["PARAMETER_MISSING"] = $this->get_all_next_level_parameters_values($log, "CHECK:PARAMETER_MISSING:"); //substr_count($log, "CHECK:PARAMETER_MISSING:");
                        $stats["REFERRER_PARAMETER_BLOCKED"] = $this->get_all_next_level_parameters_values($log, "CHECK:REFERRER_PARAMETER_BLOCKED:"); //substr_count($log, "CHECK:REFERRER_PARAMETER_BLOCKED:");
                        $stats["REFERRER_PARAMETER_ALLOWED"] = $this->get_all_next_level_parameters_values($log, "CHECK:REFERRER_PARAMETER_ALLOWED:"); //substr_count($log, "CHECK:REFERRER_PARAMETER_ALLOWED:");
                        $stats["REFERRER_PARAMETER_MISSING"] = $this->get_all_next_level_parameters_values($log, "CHECK:REFERRER_PARAMETER_MISSING:"); //substr_count($log, "CHECK:REFERRER_PARAMETER_MISSING:");
                    }
                    elseif (strpos($log_filename, "adlog") !== false)
                    {
                        $stats["TOTAL"] = substr_count($log, "INFO:GEO:");
                        $stats["USERAGENT_MOBILE"] = substr_count($log, "CHECK:USERAGENT_MOBILE:");
                        $stats["GEO_ALLOWED"] = substr_count($log, "CHECK:GEO_ALLOWED:");
                        $stats["GEO_BLOCKED"] = substr_count($log, "CHECK:GEO_BLOCKED:");
                    }
                    elseif (strpos($log_filename, "allowed_traffic") !== false)
                    {
                        $stats["ALLOWED_TRAFFIC"] = substr_count($log, "CHECK:ALLOWED_TRAFFIC:");
                    }
                }
            } catch (Exception $ex) {
                $stats["LOG_FILES_SIZE_ERROR"][$log_filename] = $ex->getMessage();
            }

        }
        return $stats;
    }*/

    public function get_stats($id)
    {
        set_time_limit(60*30);
        $this->load->helper("shared_file_access_helper");
        $log_files = $this->log_files($id);
        $stats = new Ad_stats();
        foreach ($log_files as $log_filename)
        {
            try {
                $this->retrieve_log_stats($log_filename, $stats);
            } catch (Exception $ex) {
                $stats->log_file_error[$log_filename] = $ex->getMessage();
            }

        }
        return $stats;
    }

    /**
     * Opens a CSV log file, reviews one line at a time adding to the stats
     * @param $log_filename
     * @param Ad_stats $stats
     */
    private function retrieve_log_stats($log_filename, $stats) {

        if (strpos($log_filename, "mbotlog") !== false)
        {
            process_shared_file_per_line($log_filename, function ($line) use ($stats) {
                $stats->referrer_blacklist_blocked += substr_count($line, "CHECK:REFERRER_BLACKLIST_BLOCKED:");
                $stats->referrer_blacklist_allowed += substr_count($line, "CHECK:REFERRER_BLACKLIST_ALLOWED:");
                $stats->referrer_whitelist_blocked += substr_count($line, "CHECK:REFERRER_WHITELIST_BLOCKED:");
                $stats->referrer_whitelist_allowed += substr_count($line, "CHECK:REFERRER_WHITELIST_ALLOWED:");
                $this->process_log_parameters($line, "CHECK:PARAMETER_BLOCKED:",$stats->parameter_blocked);
                $this->process_log_parameters($line, "CHECK:PARAMETER_ALLOWED:",$stats->parameter_allowed);
                $this->process_log_parameters($line, "CHECK:PARAMETER_MISSING:",$stats->parameter_missing);
                $this->process_log_parameters($line, "CHECK:REFERRER_PARAMETER_BLOCKED:",$stats->referrer_parameter_blocked);
                $this->process_log_parameters($line, "CHECK:REFERRER_PARAMETER_ALLOWED:",$stats->referrer_parameter_allowed);
                $this->process_log_parameters($line, "CHECK:REFERRER_PARAMETER_MISSING:",$stats->referrer_parameter_missing);
            },true,true);
        }
        elseif (strpos($log_filename, "adlog") !== false)
        {
            process_shared_file_per_line($log_filename, function ($line) use ($stats) {
                $stats->total += substr_count($line, "INFO:GEO:");
                $stats->useragent_mobile += substr_count($line, "CHECK:USERAGENT_MOBILE:");
                $stats->geo_allowed += substr_count($line, "CHECK:GEO_ALLOWED:");
                $stats->geo_blocked += substr_count($line, "CHECK:GEO_BLOCKED:");
            },true,true);
        }
        elseif (strpos($log_filename, "allowed_traffic") !== false)
        {
            process_shared_file_per_line($log_filename, function ($line) use ($stats) {
                $stats->allowed_traffic += substr_count($line, "CHECK:ALLOWED_TRAFFIC:");
            },true,true);
        }
    }


    private function get_all_next_level_parameters_values($log, $startsWith)
    {
        $matches = array();
        $regex = "/$startsWith(.*):(.*):/";
        preg_match_all($regex, $log, $matches);
        $result = array();
        for ($i = 0; $i < sizeof($matches[1]); $i++)
        {
            if (!array_key_exists($matches[1][$i], $result))
            {
                $result[$matches[1][$i]] = array();
            }

            if (!array_key_exists($matches[2][$i], $result[$matches[1][$i]]))
            {
                $result[$matches[1][$i]][$matches[2][$i]] = 0;
            }

            $result[$matches[1][$i]][$matches[2][$i]]++;
        }
        return $result;
    }

    private function process_log_parameters($log, $startsWith, $data)
    {
        $matches = array();
        $regex = "/$startsWith(.*):(.*):/";
        preg_match_all($regex, $log, $matches);

        for ($i = 0; $i < sizeof($matches[1]); $i++)
        {
            $variable = $matches[1][$i];
            if (!array_key_exists($variable, $data))
            {
                $data[$variable] = array();
            }
            $valor = $matches[2][$i];
            if (!array_key_exists($valor, $data[$variable]))
            {
                $data[$variable][$valor] = 0;
            }
            $data[$variable][$valor]++;
        }
    }



}