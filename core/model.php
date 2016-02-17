<?php
class Model extends SQLHandler
{
    public function __construct()
    {
        $this->init();
    }
    public function connect_with_config(){
        $dbconf = config('database');
        $this->connect($dbconf['db_host'], $dbconf['db_user'], $dbconf['db_pass'], $dbconf['db_name']);
    }
    /* overwrite this function in extended models */
    protected function init(){
    }
}
