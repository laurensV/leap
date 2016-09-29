<?php
namespace Leap\Core;

/**
 * Class Router
 *
 * @package Leap\Core
 */
class Router
{
    private $routes;
    public  $params;
    private $plugin_manager;
    private $parsedRoute;
    private $defaultValues;

    /**
     * Router constructor.
     */
    public function __construct()
    {
        $this->routes        = [];
        $this->parsedRoute   = [];
        $this->defaultValues = [];
    }

    /**
     * Set default values for the route
     *
     * @param array $properties
     */
    private function defaultRouteValues($properties = null)
    {
        /* initialize default values once */
        if (empty($this->defaultValues)) {
            $this->defaultValues['model']       = ['class' => 'Model', 'plugin' => 'core'];
            $this->defaultValues['base_path']   = null;
            $this->defaultValues['action']      = null;
            $this->defaultValues['controller']  = ['class' => 'Controller', 'plugin' => 'core'];
            $this->defaultValues['template']    = ['path' => ROOT . 'site/templates/', 'value' => "default_template.php"];
            $this->defaultValues['page']        = ['path' => ROOT . 'site/pages/', 'value' => ""];
            $this->defaultValues['stylesheets'] = [];
            $this->defaultValues['scripts']     = [];
            $this->defaultValues['title']       = null;
            $this->defaultValues['params']      = null;
        }

        if (isset($properties) && !in_array("all", $properties)) {
            /* set array of properties to their default values */
            foreach ($properties as $property) {
                if (isset($defaultValues[$property])) {
                    $this->parsedRoute[$property] = $defaultValues[$property];
                }
            }
        } else {
            /* set all properties to their default values */
            foreach ($this->defaultValues as $property => $value) {
                $this->parsedRoute[$property] = $value;
            }
        }
    }

    /**
     * Setter injection for a plugin manager instance
     *
     * @param $plugin_manager
     */
    public function setPluginManager($plugin_manager)
    {
        $this->plugin_manager = $plugin_manager;
    }

    /**
     * Add a new file with routes
     *
     * @param $file
     * @param $plugin
     */
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
                    $routes[$regex][$option] = ["value" => $value, "path" => $path];
                    if ($option == "controller" || $option == "model") {
                        $routes[$regex][$option]['plugin'] = $plugin;
                    }
                }
                $routes[$regex]['last_path'] = $path;
            }
            $this->routes = array_replace_recursive($this->routes, $routes);
        }
    }

    /**
     * Route a given url based on the added route files
     *
     * @param $url
     *
     * @return array
     */
    public function routeUrl($url)
    {
        $this->defaultRouteValues();
        // sort route array by length of keys
        uksort($this->routes, function ($a, $b) {
            return strlen($a) - strlen($b);
        });
        $no_route = true;
        foreach ($this->routes as $regex => $options) {
            $multi_regex = explode(",", $regex);
            foreach ($multi_regex as $pattern) {
                $wildcard_args = [];
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
            return $this->routeUrl('404');
        } else {
            if (isset($this->parsedRoute['model']['file'])) {
                global $autoloader;
                $autoloader->addClassMap(["Leap\\Plugins\\" . ucfirst($this->parsedRoute['model']['plugin']) . "\\Models\\" . $this->parsedRoute['model']['class'] => $this->parsedRoute['model']['file']]);
            }
            if (isset($this->parsedRoute['controller']['file'])) {
                global $autoloader;
                $autoloader->addClassMap(["Leap\\Plugins\\" . ucfirst($this->parsedRoute['controller']['plugin']) . "\\Controllers\\" . $this->parsedRoute['controller']['class'] => $this->parsedRoute['controller']['file']]);
            }
            /* If we don't have a page value from the route files, try to parse the page from the url */
            if ($this->parsedRoute['page']['value'] == "") {
                /* parse only the page from the url */
                $this->parsePageFromUrl($url);
            }
        }
        chdir($this->parsedRoute['page']['path']);
        if (!file_exists($this->parsedRoute['page']['value'])) {
            header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
            return $this->routeUrl('404');
        }
        return $this->parsedRoute;
    }

    /**
     * Parse a route from a route file
     *
     * @param $route
     * @param $url
     * @param $wildcard_args
     */
    private function parseRoute($route, $url, $wildcard_args)
    {
        $this->parsedRoute['base_path'] = $route['last_path'];

        if (isset($route['clear'])) {
            $route['clear'] = $route['clear']['value'];
            $this->defaultRouteValues($route['clear']);
        }

        if (isset($route['model'])) {
            $this->parsedRoute['model']          = [];
            $this->parsedRoute['model']['class'] = $route['model']['value'];
            if (isset($route['modelFile'])) {
                if ($route['modelFile']['value'][0] == "/") {
                    $this->parsedRoute['model']['file'] = ROOT . substr($route['modelFile']['value'], 1);
                } else {
                    $this->parsedRoute['model']['file'] = $route['modelFile']['path'] . $route['modelFile']['value'];
                }
            }
            if (isset($route['modelPlugin'])) {
                $this->parsedRoute['model']['plugin'] = $route['modelPlugin']['value'];
            } else {
                $this->parsedRoute['model']['plugin'] = $route['model']['plugin'];
            }
        }
        if (isset($route['controller'])) {
            $this->parsedRoute['controller']          = [];
            $this->parsedRoute['controller']['class'] = $route['controller']['value'];
            if (isset($route['controllerFile'])) {
                if ($route['controllerFile']['value'][0] == "/") {
                    $this->parsedRoute['controller']['file'] = ROOT . substr($route['controllerFile']['value'], 1);
                } else {
                    $this->parsedRoute['controller']['file'] = $route['controllerFile']['path'] . $route['controllerFile']['value'];
                }
            }
            if (isset($route['controllerPlugin'])) {
                $this->parsedRoute['controller']['plugin'] = $route['controllerPlugin']['value'];
            } else {
                $this->parsedRoute['controller']['plugin'] = $route['controller']['plugin'];
            }
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
                $this->parsedRoute['params'] = [];
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

    /**
     * Parse the page value based on the url
     *
     * @param $url
     */
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
