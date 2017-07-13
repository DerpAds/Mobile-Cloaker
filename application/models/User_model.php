<?php

class User_model extends CI_Model
{

    private $login_hashes = array();
    private $user_name = "";

    function __construct()
    {
        parent::__construct();
        $this->login_hashes["arthurvanderwal"] = "731161253fafb5236f015e1d5e1c5964";
        $this->login_hashes["benson"] = "001a487c5c46e41bc7352b97e10359ce";
        $this->login_hashes["admin"] = md5("admin");
        session_start();
        $this->user_name = array_key_exists("Username", $_SESSION) && !empty($_SESSION['Username'])?$_SESSION['Username']:"";
    }

    function is_authenticated()
    {
        return !empty($this->user_name);
    }

    function login($username, $password)
    {
        if (array_key_exists($username, $this->login_hashes))
        {
            if ($this->login_hashes[$username] == md5($password))
            {
                $_SESSION['Username'] = $username;
                $this->user_name = $username;
                return true;
            }
        }
        $this->user_name = "";
        return false;
    }

    function logout()
    {
        $this->user_name = "";

        // remove all session variables
        session_unset();


        // destroy the session
        session_destroy();
    }
}