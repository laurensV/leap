<?php
class BasicController extends Controller
{
    public function defaultAction($params)
    {
        $this->set('site_title', "BasicController");
    }

    public function includeHeaderHook()
    {
        return array(ROOT . "site/pages/include/menu.php");
    }

}
