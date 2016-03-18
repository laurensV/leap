<?php
namespace Frameworkname\Core;

class Router
{
    public $routes;
    public $page;
    public $action;
    public $params;
    public $model;
    private $modelFile;
    public $controller;
    public $controllerFile;
    public $template;
    private $base_path;
    private $plugin_manager;
    public $stylesheets_route;
    public $scripts_route;
    public $title;

    public function __construct()
    {
        $this->routes = array();
        $this->defaultValues();
    }

    public function defaultValues()
    {
        $this->model             = 'Model';
        $this->modelFile         = null;
        $this->base_path         = null;
        $this->action            = null;
        $this->controller        = 'Controller';
        $this->controllerFile    = null;
        $this->template['path']  = ROOT . 'site/templates/';
        $this->template['value'] = "default_template.php";
        $this->page['path']      = ROOT . 'site/pages/';
        $this->page['value']     = "";
        $this->stylesheets_route = array();
        $this->scripts_route     = array();
        $this->title             = null;
    }

    public function setPluginManager($plugin_manager)
    {
        $this->plugin_manager = $plugin_manager;
    }

    public function addRouteFile($file)
    {
        if (file_exists($file)) {
            $routes = parse_ini_file($file, true);
            $path   = str_replace("\\", "/", dirname($file)) . "/";
            foreach ($routes as $regex => $route) {
                if (isset($route['dependencies'])) {
                    $error = "";
                    foreach ($route['dependencies'] as $plugin) {
                        if (!$this->plugin_manager->isEnabled($plugin)) {
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

    public function routeUrl($url)
    {
        // sort route array by length of keys
        uksort($this->routes, function ($a, $b) {return strlen($a) - strlen($b);});
        $no_route = true;
        foreach ($this->routes as $regex => $options) {
            $multi_regex = explode(",", $regex);
            foreach ($multi_regex as $pattern) {
                $wildcard_args = array();
                if (strpos($pattern, ":")) {
                    if (preg_match_all("/:(\w+)/", $pattern, $matches)) {
                        $wildcard_args['pattern'] = $pattern;
                        foreach ($matches[0] as $key => $whole_match) {
                            $pattern                  = str_replace($whole_match, "*", $pattern);
                            $wildcard_args['pattern'] = str_replace($whole_match, "(.*)", $wildcard_args['pattern']);
                            $wildcard_args['args'][]  = $matches[1][$key];
                        }
                    }
                }
                $exclude_slash = FNM_PATHNAME;
                if (isset($options['include_slash']) && $options['include_slash']) {
                    $exclude_slash = 0;
                }
                if (fnmatch($pattern, $url, FNM_CASEFOLD | $exclude_slash)) {
                    $no_route = false;
                    $this->parseRoute($options, $url, $wildcard_args);
                    break;
                }
            }
        }
        if ($no_route) {
            /* no route found, goto 404 */
            header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
            $this->routeUrl('404');
            return;
        } else {
            if (isset($this->modelFile)) {
                chdir($this->modelFile['path']);
                if (file_exists($this->modelFile['value'])) {
                    require $this->modelFile['value'];
                }
            }
            if (isset($this->controllerFile)) {
                chdir($this->controllerFile['path']);
                if (file_exists($this->controllerFile['value'])) {
                    require $this->controllerFile['value'];
                }
            }
            if ($this->page['value'] == "") {
                /* parse only the page from the url */
                $this->parsePageFromUrl($url);
            }
        }
        chdir($this->page['path']);
        if (!file_exists($this->page['value'])) {
            $this->defaultValues();
            header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
            $this->routeUrl('404');
            return;
        }
    }

    public function parseRoute($route, $url, $wildcard_args)
    {
        $this->base_path = $route['last_path'];

        if (isset($route['clear'])) {
            $route['clear'] = $route['clear']['value'];
            $all            = in_array('all', $route['clear']);
            if ($all || in_array('scripts', $route['clear'])) {
                $this->scripts_route = array();
            }
            if ($all || in_array('stylesheets', $route['clear'])) {
                $this->stylesheets_route = array();
            }
            if ($all || in_array('model', $route['clear'])) {
                $this->model = 'Model';
            }
            if ($all || in_array('modelFile', $route['clear'])) {
                $this->modelFile = null;
            }
            if ($all || in_array('controller', $route['clear'])) {
                $this->controller = 'Controller';
            }
            if ($all || in_array('controllerFile', $route['clear'])) {
                $this->controllerFile = null;
            }
            if ($all || in_array('template', $route['clear'])) {
                $this->template['path']  = ROOT . 'site/templates/';
                $this->template['value'] = "default_template.php";
            }
            if ($all || in_array('page', $route['clear'])) {
                $this->page['path']  = ROOT . 'site/pages/';
                $this->page['value'] = "";
            }
            if ($all || in_array('action', $route['clear'])) {
                $this->action = null;
            }
            if ($all || in_array('title', $route['clear'])) {
                $this->title = null;
            }
        }

        if (isset($route['model'])) {
            $this->model = $route['model']['value'];
            if (isset($route['modelFile'])) {
                $this->modelFile = $route['modelFile'];
                if ($this->modelFile['value'][0] == "/") {
                    $this->modelFile['value'] = ROOT . substr($this->modelFile['value'], 1);
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
                    $this->controllerFile['value'] = ROOT . substr($this->controllerFile['value'], 1);
                }
            } else {
                $this->controllerFile = array("value" => "controllers/" . $this->controller . ".php", "path" => $route['controller']['path']);
            }
        }
        if (isset($route['page'])) {
            $this->page = $route['page'];
            if ($this->page['value'][0] == "/") {
                $this->page = ROOT . substr($this->page['value'], 1);
            }
        }
        if (isset($route['template'])) {
            $this->template = $route['template'];
            if ($this->template['value'][0] == "/") {
                $this->template = ROOT . substr($this->template['value'], 1);
            }
        }
        if (isset($route['action'])) {
            $this->action = $route['action']['value'];
        }
        if (isset($route['title'])) {
            $this->title = $route['title']['value'];
        }
        if (isset($route['stylesheets'])) {
            $this->stylesheets_route[] = $route['stylesheets'];
        }
        if (isset($route['scripts'])) {
            $this->scripts_route[] = $route['scripts'];
        }
        if (!empty($wildcard_args)) {
            if (preg_match_all("'" . $wildcard_args['pattern'] . "'", $url, $matches)) {
                $this->params = array();
                global $wildcards_from_url;
                foreach ($matches as $key => $arg) {
                    if (!$key) {
                        continue;
                    }

                    /* TODO: choose if wildcard gets available through params or through arg() function */
                   // $this->params[$wildcard_args['args'][$key - 1]]       = $arg[0];
                    $wildcards_from_url[$wildcard_args['args'][$key - 1]] = $arg[0];
                }
            }
        }
    }

    private function parsePageFromUrl($url)
    {
        $this->page['value'] = "home";
        $args                = arg(null, $url);
        $page                = end($args);
        if ($page == "") {
            $page = $this->page['value'];
        }
        $this->page['path']  = $this->base_path . "pages/";
        $this->page['value'] = $page . ".php";
    }
}
