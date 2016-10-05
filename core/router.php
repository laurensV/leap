<?php
namespace Leap\Core;

/**
 * Class Router
 *
 * @package Leap\Core
 */
class Router
{
    private $routeCollection;
    private $plugin_manager;
    private $parsedRoute;
    private $defaultValues;
    private $replaceWildcardArgs;

    /**
     * Router constructor.
     */
    public function __construct()
    {
        $this->routeCollection = [];
        $this->parsedRoute     = [];
        $this->defaultValues   = [];
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
            foreach ($routes as $route => $options) {
                // Multi-value keys seperation
                $multi_regex = explode(",", $route);
                foreach ($multi_regex as $sep_route) {
                    $this->addRoute($sep_route, $options, $path, $plugin);
                }
            }
        }
    }

    /**
     * Add a new route to the route collection
     *
     * @param $route
     * @param $options
     * @param $path
     * @param $pluginForClass
     */
    public function addRoute($route, $options, $path, $pluginForClass)
    {
        if (isset($options['dependencies'])) {
            $error = "";
            foreach ($options['dependencies'] as $plugin) {
                if (!$this->plugin_manager->isEnabled($plugin)) {
                    $error .= "need plugin " . $plugin . " for route \n";
                }
            }
            if ($error != "") {
                return;
            }
        }

        foreach ($options as $option => $value) {
            $options[$option] = ["value" => $value, "path" => $path];
            if ($option == "controller" || $option == "model") {
                $options[$option]['plugin'] = $pluginForClass;
            }
            if ($option == "method") {
                $options[$option] = [];
                /* TODO: change delimiter to | instead of , (problem: parsed as integer) */
                foreach(explode(",", $value) as $method) {
                    $options[$option][] = trim(strtoupper($method));
                }
            }
        }
        $options['last_path'] = $path;
        if (isset($this->routeCollection[$route])) {
            // Merge previous options with the new options
            $this->routeCollection[$route] = array_replace($this->routeCollection[$route], $options);
        } else {
            // New route: simply add the options
            $this->routeCollection[$route] = $options;
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

        // Sort route array
        $this->routeCollection = $this->sortRoutes($this->routeCollection);

        // Try to match url to one or multiple routes
        $no_route = true;
        foreach ($this->routeCollection as $pattern => $options) {
            $include_slash = (isset($options['include_slash']) && $options['include_slash']);
            $pattern       = $this->getPregPattern($pattern, $include_slash);
            $wildcard_args = [];
            // Search for wildcard arguments
            if (strpos($pattern, ":") !== false) {
                if (preg_match_all("/:(\w+)/", $pattern, $matches)) {
                    $wildcard_args['pattern'] = $pattern;
                    foreach ($matches[0] as $key => $whole_match) {
                        $pattern                  = str_replace('\\' . $whole_match, "[^/]+", $pattern);
                        $wildcard_args['pattern'] = str_replace('\\' . $whole_match, "([^/]+)", $wildcard_args['pattern']);
                        $wildcard_args['args'][]  = $matches[1][$key];
                    }
                }
            }

            if (preg_match($pattern, $url)) {
                if (!isset($options['method']) || in_array($_SERVER['REQUEST_METHOD'] ,  $options['method'])) {
                    /* We found at least one valid route */
                    $no_route = false;
                    $this->parseRoute($options, $url, $wildcard_args);
                }
            }
        }
        if ($no_route) {
            // No route found, goto 404
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
        }
        chdir($this->parsedRoute['page']['path']);
        if (!file_exists($this->parsedRoute['page']['value'])) {
            header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
            return $this->routeUrl('404');
        }
        return $this->parsedRoute;
    }

    /**
     * Sort route array by weight first, then by length of route (key)
     *
     * @param array $routes
     *
     * @return array
     */
    private function sortRoutes($routes)
    {
        $weight      = [];
        $routeLength = [];
        foreach ($routes as $key => $value) {
            if (isset($value['weight'])) {
                $weight[] = $value['weight']['value'];
            } else {
                $weight[] = 1;
            }
            $routeLength[] = strlen($key);
        }
        /* TODO: check overhead for fix for array_multisort who re-indexes numeric keys */
        $orig_keys = array_keys($routes); // Fix for re-indexing of numeric keys
        array_multisort($weight, SORT_ASC, $routeLength, SORT_ASC, $routes, $orig_keys);
        return array_combine($orig_keys, $routes); // Fix for re-indexing of numeric keys
    }

    /**
     * Get regex pattern for preg* functions based on fnmatch function pattern
     *
     * @param      $pattern
     * @param bool $include_slash
     *
     * @return string
     */
    private function getPregPattern($pattern, $include_slash = false)
    {
        $transforms = [
            '\*'   => '[^/]*',
            '\?'   => '.',
            '\[\!' => '[^',
            '\['   => '[',
            '\]'   => ']'
        ];

        // Forward slash in string must be in pattern:
        if ($include_slash) {
            $transforms['\*'] = '.*';
        }

        return '#^' . strtr(preg_quote(trim($pattern), '#'), $transforms) . '$#i';
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

        if (!empty($wildcard_args)) {
            if (preg_match_all($wildcard_args['pattern'], $url, $matches)) {
                $this->replaceWildcardArgs = [];
                global $wildcards_from_url;
                foreach ($matches as $key => $arg) {
                    if (!$key) {
                        continue;
                    }

                    $this->replaceWildcardArgs[":" . $wildcard_args['args'][$key - 1]] = $arg[0];
                    $wildcards_from_url[$wildcard_args['args'][$key - 1]]              = $arg[0];
                }
            }
        }

        if (isset($route['model'])) {
            $this->parsedRoute['model']          = [];
            $this->parsedRoute['model']['class'] = $this->replaceWildcardArgs($route['model']['value']);
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
            $this->parsedRoute['controller']['class'] = $this->replaceWildcardArgs($route['controller']['value']);
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
            $route['page']['value']    = $this->replaceWildcardArgs($route['page']['value']);
            $this->parsedRoute['page'] = $route['page'];
            if ($this->parsedRoute['page']['value'][0] == "/") {
                $this->parsedRoute['page']['value'] = substr($this->parsedRoute['page']['value'], 1);
                $this->parsedRoute['page']['path']  = ROOT;
            }
        }
        if (isset($route['template'])) {
            $this->parsedRoute['template'] = $route['template'];
            if ($this->parsedRoute['template']['value'][0] == "/") {
                $this->parsedRoute['template'] = ROOT . substr($this->parsedRoute['template']['value'], 1);
            }
        }
        if (isset($route['action'])) {
            $this->parsedRoute['action'] = $this->replaceWildcardArgs($route['action']['value']);
        }
        if (isset($route['title'])) {
            $this->parsedRoute['title'] = $this->replaceWildcardArgs($route['title']['value']);
        }
        if (isset($route['stylesheets'])) {
            $this->parsedRoute['stylesheets'][] = $route['stylesheets'];
        }
        if (isset($route['scripts'])) {
            $this->parsedRoute['scripts'][] = $route['scripts'];
        }
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private function replaceWildcardArgs($string)
    {
        if (!empty($this->replaceWildcardArgs)) {
            return strtr($string, $this->replaceWildcardArgs);
        } else {
            return $string;
        }
    }
}
