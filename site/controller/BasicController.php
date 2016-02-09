<?php
class BasicController extends Controller {
    function default_action($params){
        global $config;
        $this->set('site_title', "BasicController: " . $params . " - " . $config['application']['site_name']);
    }
}