<?php
class Application
{
    private $page;
    /** @var null The controller */
    private $controller;
    /** @var null The method (of the above controller), often also named "action" */
    private $action;
    /** @var array URL parameters */
    private $params;
        /** @var null The controller */
    private $model;
    /**
     * "Start" the application:
     * Analyze the URL elements and calls the according controller/method or the fallback
     */
    public function __construct() {
        // get controller, action and other params from the url
        $this->parse_arguments();

        if (file_exists(ROOT . '/site/model/' . $this->page . '.php')) {
            require_once ROOT . '/site/model/' . $this->page . '.php';
        }
        $this->model = class_exists($this->page . "Model") ? $page . "Model" : 'Model';
          
        // construct the controller class
        if (file_exists(ROOT . '/site/controller/' . $this->page . '.php')) {
            require_once ROOT . '/site/controller/' . $this->page . '.php';
        }
        $this->controller = class_exists($this->page . "Controller") ? $this->page . "Controller" : 'Controller';
        $this->controller = new $this->controller($this->model, $this->page);
        if (method_exists($this->controller, $this->action)) {
                $this->controller->{$this->action}($this->params);
        } else if (empty($this->params)) {
            /* when the second argument is not an action, it is probably a parameter */
            $this->params = $this->action;
            $this->controller->default_action($this->params);
        } else {
            header('location: ' . URL . '/404');
        }

        $this->controller->render();
    }
    
    private function parse_arguments() {
        $args = isset($_GET['args']) ? $_GET['args'] : "";
        
        $args_parts = explode("/",$args);
        if(empty($args_parts[0])){
            $args_parts = array();
        }

        $this->page = 'home';
        
        switch (sizeof($args_parts)) {
            case 0:
                break;
            case 1:
                $this->page = $args_parts[0];
                break;
            case 2:
                $this->page = $args_parts[0];
                $this->action = $args_parts[1];
                break;
            case 3:
                $this->page = $args_parts[0];
                $this->action = $args_parts[1];
                $this->params = $args_parts[2];
                break;
            default:
                header('location: ' . URL . '/404');
        }
        /* check if users didn't specify the default action in the url themselves */
        if($this->action == 'default_action'){
            header('location: ' . URL . '/404');
        } else if(empty($this->action)){
            $this->action = 'default_action';
        }
    }

}