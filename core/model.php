<?php
class Model extends SQLHandler
{
    public function __construct($db)
    {
        $this->set_db($db);
        $this->init();
    }
    /* overwrite this function in extended models */
    protected function init(){
    }
}
