<?php
class BasicController extends Controller
{
    public function default_action($params)
    {
        global $config;
        $this->set('site_title', "BasicController: " . $params . " - " . $config['application']['site_name']);
    }

    public function include_header_hook()
    {
        return array(ROOT . "/site/pages/include/menu.php");
    }

}
