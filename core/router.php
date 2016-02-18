<?php
class Router
{
    public $routes;
    public $page;
    public $action;
    public $params;
    public $model;
    private $modelFile;
    public $controller;
    private $controllerFile;
    public $template;
    private $base_path;
    private $plugin_manager;

    public function __construct()
    {
        $this->routes            = array();
        $this->model             = 'Model';
        $this->controller        = 'Controller';
        $this->template['path']  = ROOT . '/site/templates';
        $this->template['value'] = "default_page.php";
        $this->page['path']      = ROOT . '/site/pages';
        $this->page['value']     = "";
    }

    public function set_plugin_manager($plugin_manager)
    {
        $this->plugin_manager = $plugin_manager;
    }

    public function add_route_file($file)
    {
        if (file_exists($file)) {
            $routes = parse_ini_file($file, true);
            $path   = dirname($file);
            foreach ($routes as $regex => $route) {
                if (isset($route['dependencies'])) {
                    $error = "";
                    foreach ($route['dependencies'] as $plugin) {
                        if (!$this->plugin_manager->is_enabled($plugin)) {
                            $error .= "need plugin " . $plugin . " for route " . $regex . "\n";
                        }
                    }
                    if ($error != "") {
                        unset($routes[$regex]);
                        continue;
                    }
                }
                foreach ($routes[$regex] as $option => $value) {
                    $routes[$regex][$option] = array("value" => $value, "path" => $path);
                }
                $routes[$regex]['last_path'] = $path;
            }
            $this->routes = array_replace_recursive($this->routes, $routes);
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
        if ($no_route) {
            $this->parse_all_from_url($url);
        } else {
            if (isset($this->modelFile)) {
                chdir($this->modelFile['path']);
                if (file_exists($this->modelFile['value'])) {
                    require_once $this->modelFile['value'];
                }
            }
            if (isset($this->controllerFile)) {
                chdir($this->controllerFile['path']);
                if (file_exists($this->controllerFile['value'])) {
                    require_once $this->controllerFile['value'];
                }
            }
            if ($this->page['value'] == "") {
                /* parse only the page from the url */
                $this->parse_page_from_url($url);
            }
        }
    }

    public function parse_route($route, $url)
    {
        $this->base_path = $route['last_path'];

        if (isset($route['model'])) {
            $this->model = $route['model']['value'];
            if (isset($route['modelFile'])) {
                $this->modelFile = $route['modelFile'];
                if ($this->modelFile['value'][0] == "/") {
                    $this->modelFile['value'] = ROOT . $this->modelFile['value'];
                }
            } else {
                $this->modelFile = array("value" => "models/" . $this->model . ".php", "path" => $route['model']['path']);
            }
        }
        if (isset($route['controller'])) {
            $this->controller = $route['controller']['value'];
            if (isset($route['controllerFile'])) {
                $this->controllerFile = $route['controllerFile'];
                if ($this->controllerFile['value'][0] == "/") {
                    $this->controllerFile['value'] = ROOT . $this->controllerFile['value'];
                }
            } else {
                $this->controllerFile = array("value" => "controllers/" . $this->controller . ".php", "path" => $route['controller']['path']);
            }
        }
        if (isset($route['page'])) {
            $this->page = $route['page'];
            if ($this->page['value'][0] == "/") {
                $this->page = ROOT . $this->page['value'];
            }
        }
        if (isset($route['template'])) {
            $this->template = $route['template'];
            if ($this->template['value'][0] == "/") {
                $this->template = ROOT . $this->template['value'];
            }
        }
    }

    private function parse_page_from_url($url)
    {
        $this->page['value'] = "home.php";
        $args                = arg(NULL, $url);
        $page                = end($args);
        if ($page == "") {
            $page = $this->page['value'];
        }
        $this->page['path'] = $this->base_path;
        if ($this->base_path == ROOT . '/site') {
            $this->page['path'] .= "/pages";
        }

        $this->page['value'] = $page . ".php";
    }

    private function parse_all_from_url($url)
    {
        $this->page['value'] = "home.php";
        $args                = arg(NULL, $url);
        if (empty($args[0])) {
            $args = array();
        }
        $this->page['path'] = ROOT . '/site/pages/';
        $page               = array_shift($args);
        if ($page) {
            $this->page['value'] = $page . ".php";
            $action              = array_shift($args);
            if ($action) {
                $this->action = $action;
                $this->params = $args;
            }
        }

        /* check if users didn't specify the default action in the url themselves */
        if ($this->action == 'default_action') {
            header('location: ' . URL . '/404');
        } else if (empty($this->action)) {
            $this->action = 'default_action';
        }
    }
}
