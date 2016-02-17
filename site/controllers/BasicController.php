<?php
class BasicController extends Controller
{
    public function default_action($params)
    {
        $this->set('site_title', "BasicController");
    }

    public function include_header_hook()
    {
        return array(ROOT . "/site/pages/include/menu.php");
    }

}
