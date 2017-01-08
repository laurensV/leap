<?php
namespace Leap\Core;

class Model
{
    public $pdo;

    public function __construct(PdoPlus $pdo = null)
    {
        $this->init();
    }

    /* overwrite this function in extended models */
    protected function init() {}
}
