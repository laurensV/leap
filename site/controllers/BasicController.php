<?php

namespace Leap\Site\Controllers;

use Leap\Core\Controller;

class BasicController extends Controller
{
    public function defaultAction()
    {
        $this->set('site_title', "BasicController");
    }

    public function includeHeaderHook()
    {
        return array(ROOT . "site/pages/include/menu.php");
    }
    public function grantAccess(): bool
    {
        return false;
    }

}
