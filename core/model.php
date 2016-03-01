<?php
class Model extends SQLHandler
{
    public function __construct($pdo)
    {
        $this->set_db($pdo);
        $this->init();
    }
    /* overwrite this function in extended models */
    protected function init(){
    }
}
