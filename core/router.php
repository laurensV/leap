<?php
namespace Leap\Core;

class Router
{
    private $routes;
    public $params;
    private $plugin_manager;
    private $parsedRoute;

    public function __construct()
    {
        $this->routes      = [];
        $this->parsedRoute = [];
    }

    private function defaultRouteValues()
    {
        $this->parsedRoute['model']          = 'Model';
        $this->parsedRoute['modelFile']      = null;
        $this->parsedRoute['base_path']      = null;
        $this->parsedRoute['action']         = null;
        $this->parsedRoute['controller']     = 'Controller';
        $this->parsedRoute['controllerFile'] = array('plugin' => 'core');
        $this->parsedRoute['template']       = array('path' => ROOT . 'site/templates/', 'value' => "default_template.php");
        $this->parsedRoute['page']           = array('path' => ROOT . 'site/pages/', 'value' => "");
        $this->parsedRoute['stylesheets']    = array();
        $this->parsedRoute['scripts']        = array();
        $this->parsedRoute['title']          = null;
        $this->parsedRoute['params']         = null;
    }

    public function setPluginManager($plugin_manager)
    {
        $this->plugin_manager = $plugin_manager;
    }

    public function addRouteFile($file, $plugin)
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
                    if ($option == "controller" || $option == "model") {
                        $routes[$regex][$option]['plugin'] = $plugin;
                    }
                }
                $routes[$regex]['last_path'] = $path;
            }
            $this->routes = array_replace_recursive($this->routes, $routes);
        }
    }

    public function routeUrl($url)
    {
        $this->defaultRouteValues();
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
            if (isset($this->parsedRoute['modelFile']['path'])) {
                chdir($this->parsedRoute['modelFile']['path']);
                if (file_exists($this->parsedRoute['modelFile']['value'])) {
                    /* TODO: load with composer autoload */
                    require $this->parsedRoute['modelFile']['value'];
                }
            }
            if (isset($this->parsedRoute['controllerFile']['path'])) {
                chdir($this->parsedRoute['controllerFile']['path']);
                if (file_exists($this->parsedRoute['controllerFile']['value'])) {
                    /* load controller classes TODO: either pick this or autoload with composer */
                    //require $this->parsedRoute['controllerFile']['value'];
                }
            }
            if ($this->parsedRoute['page']['value'] == "") {
                /* parse only the page from the url */
                $this->parsePageFromUrl($url);
            }
        }
        chdir($this->parsedRoute['page']['path']);
        if (!file_exists($this->parsedRoute['page']['value'])) {
            header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
            $this->routeUrl('404');
            return;
        }
        return $this->parsedRoute;
    }

    private function parseRoute($route, $url, $wildcard_args)
    {
        $this->parsedRoute['base_path'] = $route['last_path'];

        if (isset($route['clear'])) {
            $route['clear'] = $route['clear']['value'];
            $all            = in_array('all', $route['clear']);
            if ($all || in_array('scripts', $route['clear'])) {
                $this->parsedRoute['scripts'] = array();
            }
            if ($all || in_array('stylesheets', $route['clear'])) {
                $this->parsedRoute['stylesheets'] = array();
            }
            if ($all || in_array('model', $route['clear'])) {
                $this->parsedRoute['model'] = 'Model';
            }
            if ($all || in_array('modelFile', $route['clear'])) {
                $this->parsedRoute['modelFile'] = null;
            }
            if ($all || in_array('controller', $route['clear'])) {
                $this->parsedRoute['controller'] = 'Controller';
            }
            if ($all || in_array('controllerFile', $route['clear'])) {
                $this->parsedRoute['controllerFile'] = array('plugin' => 'core');
            }
            if ($all || in_array('template', $route['clear'])) {
                $this->parsedRoute['template']['path']  = ROOT . 'site/templates/';
                $this->parsedRoute['template']['value'] = "default_template.php";
            }
            if ($all || in_array('page', $route['clear'])) {
                $this->parsedRoute['page']['path']  = ROOT . 'site/pages/';
                $this->parsedRoute['page']['value'] = "";
            }
            if ($all || in_array('action', $route['clear'])) {
                $this->parsedRoute['action'] = null;
            }
            if ($all || in_array('title', $route['clear'])) {
                $this->parsedRoute['title'] = null;
            }
        }

        if (isset($route['model'])) {
            $this->parsedRoute['model'] = $route['model']['value'];
            if (isset($route['modelFile'])) {
                $this->parsedRoute['modelFile'] = $route['modelFile'];
                if ($this->parsedRoute['modelFile']['value'][0] == "/") {
                    $this->parsedRoute['modelFile']['value'] = ROOT . substr($this->parsedRoute['modelFile']['value'], 1);
                }
            } else {
                $this->parsedRoute['modelFile'] = array("value" => "models/" . $this->parsedRoute['model'] . ".php", "path" => $route['model']['path']);
            }
            $this->parsedRoute['modelFile']['plugin'] = $route['model']['plugin'];
        }
        if (isset($route['controller'])) {
            $this->parsedRoute['controller'] = $route['controller']['value'];
            if (isset($route['controllerFile'])) {
                $this->parsedRoute['controllerFile'] = $route['controllerFile'];
                if ($this->parsedRoute['controllerFile']['value'][0] == "/") {
                    $this->parsedRoute['controllerFile']['value'] = ROOT . substr($this->parsedRoute['controllerFile']['value'], 1);
                }
            } else {
                /* TODO: get rid of this */
                $this->parsedRoute['controllerFile'] = array("value" => "controllers/" . $this->parsedRoute['controller'] . ".php", "path" => $route['controller']['path']);
            }
            $this->parsedRoute['controllerFile']['plugin'] = $route['controller']['plugin'];
        }
        if (isset($route['page'])) {
            $this->parsedRoute['page'] = $route['page'];
            if ($this->parsedRoute['page']['value'][0] == "/") {
                $this->parsedRoute['page'] = ROOT . substr($this->parsedRoute['page']['value'], 1);
            }
        }
        if (isset($route['template'])) {
            $this->parsedRoute['template'] = $route['template'];
            if ($this->parsedRoute['template']['value'][0] == "/") {
                $this->parsedRoute['template'] = ROOT . substr($this->parsedRoute['template']['value'], 1);
            }
        }
        if (isset($route['action'])) {
            $this->parsedRoute['action'] = $route['action']['value'];
        }
        if (isset($route['title'])) {
            $this->parsedRoute['title'] = $route['title']['value'];
        }
        if (isset($route['stylesheets'])) {
            $this->parsedRoute['stylesheets'][] = $route['stylesheets'];
        }
        if (isset($route['scripts'])) {
            $this->parsedRoute['scripts'][] = $route['scripts'];
        }
        if (!empty($wildcard_args)) {
            if (preg_match_all("'" . $wildcard_args['pattern'] . "'", $url, $matches)) {
                $this->parsedRoute['params'] = array();
                global $wildcards_from_url;
                foreach ($matches as $key => $arg) {
                    if (!$key) {
                        continue;
                    }

                    /* TODO: choose if wildcard gets available through params or through arg() function */
                    // $this->parsedRoute['params'][$wildcard_args['args'][$key - 1]]       = $arg[0];
                    $wildcards_from_url[$wildcard_args['args'][$key - 1]] = $arg[0];
                }
            }
        }
    }

    private function parsePageFromUrl($url)
    {
        $this->parsedRoute['page']['value'] = "home";
        $args                               = arg(null, $url);
        $page                               = end($args);
        if ($page == "") {
            $page = $this->parsedRoute['page']['value'];
        }
        $this->parsedRoute['page']['path']  = $this->parsedRoute['base_path'] . "pages/";
        $this->parsedRoute['page']['value'] = $page . ".php";
    }
}
