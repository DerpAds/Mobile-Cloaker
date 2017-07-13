<?php

/**
 * New loader functions
 *
 * @author Juan Romero
 */
class MY_Loader extends CI_Loader {

    /**
     * Loads a DTO classes file located on the application/dto folder
     * @param string $daoName
     */
    public function dto($daoName) {
        require_once APPPATH . '/dto/' . $daoName . '.php';
    }

}
