<?php
class Model extends SQLHandler
{
    public function __construct()
    {
        $this->init();
    }
    public function connect_with_config(){
        global $config;
        $this->connect($config['database']['db_host'], $config['database']['db_user'], $config['database']['db_pass'], $config['database']['db_name']);
    }
    /* overwrite this function in extended models */
    protected function init(){
    }
}
