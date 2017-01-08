<?php
namespace Leap\Core;

/**
 * Class Router
 *
 * @package Leap\Core
 */
class Router
{
    /**
     * @var array
     */
    public $routeCollection;
    /**
     * @var PluginManager
     */
    private $plugin_manager;
    /**
     * @var array
     */
    private $defaultValues;
    /**
     * @var array
     */
    private $replaceWildcardArgs;

    /**
     * Router constructor.
     */
    public function __construct()
    {
        $this->routeCollection = [];
        $this->defaultValues   = [];
    }


    /**
     * Setter injection for a plugin manager instance
     *
     * @param $plugin_manager
     */
    public function setPluginManager(PluginManager $plugin_manager): void
    {
        $this->plugin_manager = $plugin_manager;
    }

    /**
     * Add a new file with routes
     *
     * @param $file
     * @param $pluginForClass
     */
    public function addRouteFile($file, $pluginForNamespace): void
    {
        if (file_exists($file)) {
            $routes = parse_ini_file($file, true);
            $path   = str_replace("\\", "/", dirname($file)) . "/";
            foreach ($routes as $route => $options) {
                // Multi-value keys seperation
                $multi_regex = explode(",", $route);
                foreach ($multi_regex as $sep_route) {
                    $this->addRoute($sep_route, $options, $path, $pluginForNamespace);
                }
            }
        }
    }

    /**
     * Add a new route to the route collection
     *
     * @param      $route
     * @param      $options
     * @param      $pluginForNamespace
     * @param      $path
     */
    public function addRoute($route, $options, $path = ROOT, $pluginForNamespace = NULL): void
    {
        $route = trim($route, "/");
        if (isset($options['dependencies']) && isset($this->plugin_manager)) {
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
            if ($option == "method") {
                $options[$option] = [];
                /* TODO: change delimiter to | instead of , (problem: parsed as integer) */
                foreach (explode(",", $value) as $method) {
                    $options[$option][] = trim(strtoupper($method));
                }
            }
        }
        if (!isset($options['path'])) {
            $options['path'] = $path;
        }
        if (!isset($options['plugin']) && isset($pluginForNamespace)) {
            $options['plugin'] = $pluginForNamespace;
        }
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
     * @param $uri
     * @param $httpMethod
     *
     * @return array
     */
    public function routeUrl($uri, $httpMethod = 'GET'): Route
    {
        $uri = trim($uri, "/");

        // Sort route array
        $this->routeCollection = $this->sortRoutes($this->routeCollection);

        $parsedRoute = new Route();

        // Try to match url to one or multiple routes
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

            if (preg_match($pattern, $uri)) {
                if (!isset($options['method']) || in_array($httpMethod, $options['method'])) {
                    /* We found at least one valid route */
                    $this->parseRoute($options, $uri, $wildcard_args, $parsedRoute);
                }
            }
        }

        return $parsedRoute;
    }

    /**
     * Sort route array by weight first, then by length of route (key)
     *
     * @param array $routes
     *
     * @return array
     */
    private function sortRoutes($routes): array
    {
        $weight      = [];
        $routeLength = [];
        foreach ($routes as $key => $value) {
            if (isset($value['weight'])) {
                $weight[] = $value['weight'];
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
    private function getPregPattern($pattern, $include_slash = false): string
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
    private function parseRoute($route, $url, $wildcard_args, Route $parsedRoute): void
    {
        $parsedRoute->base_path = $route['path'];

        if (isset($route['clear'])) {
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
        /* TODO: switch to switch statement */
        if (isset($route['model'])) {
            $parsedRoute->model          = [];
            $parsedRoute->model['class'] = $this->replaceWildcardArgs($route['model']);
            if (isset($route['modelFile'])) {
                if ($route['modelFile'][0] == "/") {
                    $parsedRoute->model['file'] = ROOT . substr($route['modelFile'], 1);
                } else {
                    $parsedRoute->model['file'] = $route['path'] . $route['modelFile'];
                }
            }
            if (isset($route['modelPlugin'])) {
                $parsedRoute->model['plugin'] = $route['modelPlugin'];
            } else {
                if (isset($route['plugin'])) {
                    $parsedRoute->model['plugin'] = $route['plugin'];
                }
            }
        }
        if (isset($route['controller'])) {
            $parsedRoute->controller          = [];
            $parsedRoute->controller['class'] = $this->replaceWildcardArgs($route['controller']);
            if (isset($route['controllerFile'])) {
                if ($route['controllerFile'][0] == "/") {
                    $parsedRoute->controller['file'] = ROOT . substr($route['controllerFile'], 1);
                } else {
                    $parsedRoute->controller['file'] = $route['path'] . $route['controllerFile'];
                }
            }
            if (isset($route['controllerPlugin'])) {
                $parsedRoute->controller['plugin'] = $route['controllerPlugin'];
            } else {
                if (isset($route['plugin'])) {
                    $parsedRoute->controller['plugin'] = $route['plugin'];
                }
            }
        }
        if (isset($route['page'])) {
            $parsedRoute->page          = [];
            $parsedRoute->page['value'] = $this->replaceWildcardArgs($route['page']);
            if ($parsedRoute->page['value'][0] == "/") {
                $parsedRoute->page['value'] = substr($parsedRoute->page['value'], 1);
                $parsedRoute->page['path']  = ROOT;
            } else {
                $parsedRoute->page['path'] = $route['path'];
            }
        }
        if (isset($route['template'])) {
            $parsedRoute->template          = [];
            $parsedRoute->template['value'] = $this->replaceWildcardArgs($route['template']);
            if ($parsedRoute->template['value'][0] == "/") {
                $parsedRoute->template['value'] = substr($parsedRoute->template['value'], 1);
                $parsedRoute->template['path']  = ROOT;
            } else {
                $parsedRoute->template['path'] = $route['path'];
            }
        }
        if (isset($route['action'])) {
            $parsedRoute->action = $this->replaceWildcardArgs($route['action']);
        }
        if (isset($route['title'])) {
            $parsedRoute->title = $this->replaceWildcardArgs($route['title']);
        }
        if (isset($route['stylesheets'])) {
            $parsedRoute->stylesheets[] = ["value" => $route['stylesheets'], "path" => $route['path']];
        }
        if (isset($route['scripts'])) {
            $parsedRoute->scripts[] = ["value" => $route['scripts'], "path" => $route['path']];
        }
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private function replaceWildcardArgs(string $string): string
    {
        if (!empty($this->replaceWildcardArgs)) {
            return strtr($string, $this->replaceWildcardArgs);
        } else {
            return $string;
        }
    }
}
