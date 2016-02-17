<?php
class Model extends SQLHandler
{
    public function __construct()
    {
        global $config;
        $this->connect($config['database']['db_host'], $config['database']['db_user'], $config['database']['db_pass'], $config['database']['db_name']);
    }
}
