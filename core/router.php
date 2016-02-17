<?php
class Router
{
    private $routes;
    public $page;
    public $action;
    public $params;
    public $model;
    private $modelPath;
    public $controller;
    private $controllerPath;
    public $template;
    private $base_path;

    public function __construct()
    {
        $this->routes     = array();
        $this->model      = 'Model';
        $this->controller = 'Controller';
        $this->template   = ROOT . '/site/templates/default_page.php';
        $this->page       = "home";
        $this->base_path  = ROOT . '/site';
    }

    public function add_route_file($file)
    {
        if (file_exists($file)) {
            $array = parse_ini_file($file, true);
            $path  = dirname($file);
            foreach ($array as &$value) {
                $value['path'] = $path;
            }
            $this->routes = array_merge($this->routes, $array);
        }
    }

    public function route_url($url)
    {
        // sort route array by length of keys
        uksort($this->routes, function ($a, $b) {return strlen($a) - strlen($b);});
        $no_route = true;
        foreach ($this->routes as $regex => $options) {
            if (fnmatch($regex, $url)) {
                $no_route = false;
                $this->parse_route($options, $url);
            }
        }
        chdir($this->base_path);
        if (isset($this->modelPath)) {
            require_once $this->modelPath;
        }
        if (isset($this->controllerPath)) {
            require_once $this->controllerPath;
        }
        if ($no_route) {
            $this->parse_all_from_url($url);
        } else if ($this->page == "home") {
            /* parse only the page from the url */
            $this->parse_page_from_url($url, $this->base_path);
        }

    }

    public function parse_route($route, $url)
    {
        $this->base_path = $route['path'];
        if (isset($route['model'])) {
            $this->model = $route['model'];
            if (isset($route['modelPath'])) {
                if ($route['modelPath'][0] == "/") {
                    $this->modelPath = ROOT . $route['modelPath'];
                } else {
                    $this->modelPath = $route['modelPath'];
                }
            } else if (!isset($this->modelPath)) {
                $this->modelPath = "models/" . $this->model . ".php";
            }
        }
        if (isset($route['controller'])) {
            $this->controller = $route['controller'];
            if (isset($route['controllerPath'])) {
                if ($route['controllerPath'][0] == "/") {
                    $this->controllerPath = ROOT . $route['controllerPath'];
                } else {
                    $this->controllerPath = $route['controllerPath'];
                }
            } else if (!isset($this->controllerPath)) {
                $this->controllerPath = "controllers/" . $this->controller . ".php";
            }
        }
        if (isset($route['page'])) {
            if ($route['page'][0] == "/") {
                $this->page = ROOT . $route['page'];
            } else {
                $this->page = $route['path'] . "/" . $route['page'];
            }
        }
        if (isset($route['template'])) {
            if ($route['template'][0] == "/") {
                $this->template = ROOT . $route['template'];
            } else {
                $this->template = $route['path'] . "/" . $route['template'];
            }
        }
    }

    private function parse_page_from_url($url, $base_path)
    {
        $args = explode("/", $url);
        $page = end($args);
        if ($page == "") {
            $this->page = ROOT . '/site/pages/' . $this->page . '.php';
        } else {
            $this->page = $base_path . "/pages/" . $page . ".php";
        }
    }

    private function parse_all_from_url($url)
    {
        $args = explode("/", $url);
        if (empty($args[0])) {
            $args = array();
        }

        $page = array_shift($args);
        if ($page) {
            $this->page = ROOT . '/site/pages/' . $page . ".php";
            $action     = array_shift($args);
            if ($action) {
                $this->action = $action;
                $this->params = $args;
            }
        } else {
            $this->page = ROOT . '/site/pages/' . $this->page . '.php';
        }

        /* check if users didn't specify the default action in the url themselves */
        if ($this->action == 'default_action') {
            header('location: ' . URL . '/404');
        } else if (empty($this->action)) {
            $this->action = 'default_action';
        }
    }
}
