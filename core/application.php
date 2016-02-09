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
        $this->setReporting();
        $this->add_classes();
        spl_autoload_register(array($this, 'my_autoload'));
        // get controller, action and other params from the url
        $this->parse_arguments();
        $this->callHook();

    }

    private function callHook(){
        require_once(ROOT . '/site/routes.php');
        $this->model = 'Model';
        $this->controller = 'Controller';
        if(isset($route[$this->page])){
            if (isset($route[$this->page]['model'])){
                $this->model = $route[$this->page]['model'];
            } 

            if(isset($route[$this->page]['controller'])){
                $this->controller = $route[$this->page]['controller'];
            } 

            if( isset($route[$this->page]['view'])){
                $this->page = $route[$this->page]['view'];
            }
        }

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

    /* Check if environment is development and display errors */
    private function setReporting() {
        global $config;
        if ($config['general']['dev_env'] == true) {
            error_reporting(E_ALL);
            ini_set('display_errors','On');
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors','Off');
            ini_set('log_errors', 'On');
            ini_set('error_log', ROOT . '/tmp/logs/error.log');
        }
    }
    /* Autoload any classes that are required */
    function my_autoload($className) {
        if (file_exists(ROOT . '/core/include/classes/' . $className . '.class.php')) {
            require_once(ROOT . '/core/include/classes/' . $className . '.class.php');
        } else if (file_exists(ROOT . '/site/controller/' . $className . '.php')) {
            require_once(ROOT . '/site/controller/' . $className . '.php');
        } else if (file_exists(ROOT . '/site/model/' . $className . '.php')) {
            require_once(ROOT . '/site/model/' . $className . '.php');
        } else {
            /* Error Generation Code Here */
        }
    }

    private function add_classes(){
        /* load the less to css compiler */
        require_once ROOT . '/core/include/libraries/less.php/Less.php';
        require_once(ROOT . '/core/controller.php');
        require_once(ROOT . '/core/model.php');
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