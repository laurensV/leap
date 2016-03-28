<?php
namespace Leap\Core;

class Model extends SQLHandler
{
    public function __construct($pdo)
    {
        $this->setDb($pdo);
        $this->init();
    }

    /* overwrite this function in extended models */
    protected function init() {}
}
