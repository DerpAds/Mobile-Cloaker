<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Js extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->model("ad_model");
	}

	public function index()
	{
		$this->show_not_found();
	}

	public function get($filename="") {
	    $this->load->helper("js_helper");
	    $file = get_js($filename, true);
	    if ($file == null || empty($file)) {
	        $this->show_not_found();
	        return;
        }
        $this->set_output_to_js();
	    echo $file;
    }

    private function set_output_to_js() {
        header("Content-Type: application/javascript");
    }

	private function show_not_found() {
		header("HTTP/1.1 404 Not Found");
		$this->load->view("errors/html/error_404",Array("heading"=>"Ad not found","message"=>"The link to the ad may be broken"));
	}
}


