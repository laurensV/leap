<?php
namespace Leap\Core;

use Psr\Http\Message\ServerRequestInterface;

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
     * @var \Leap\Core\PluginManager
     */
    private $pluginManager;
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
     * Setter injection for a Leap plugin manager instance
     *
     * @param \Leap\Core\PluginManager $pluginManager
     */
    public function setPluginManager(PluginManager $pluginManager): void
    {
        $this->pluginManager = $pluginManager;
    }

    /**
     * Add a new file with routes
     *
     * @param string $file
     * @param string $pluginForNamespace
     */
    public function addFile(string $file, string $pluginForNamespace): void
    {
        if (file_exists($file)) {
            $routes = require $file;
            $path   = str_replace("\\", "/", dirname($file)) . "/";
            foreach ($routes as $route => $options) {
                $options['path'] = $path;
                $callback        = $options['callback'] ?? null;
                unset($options['callback']);
                $this->add($route, $callback, $options, $pluginForNamespace);
            }
        }
    }

    /**
     * Add a new route to the route collection
     *
     * @param string $pattern
     * @param        $callback
     * @param array  $options
     * @param string $pluginForNamespace
     */
    public function add(string $pattern, $callback, array $options = [], string $pluginForNamespace = null): void
    {
        $abstract = $options['abstract'] ?? false;
        $callback = $callback            ?? $options['callback'] ?? null;
        $weight   = $options['weight']   ?? 1;
        $path     = $options['path']     ?? ROOT;
        $pattern  = trim($pattern, "/ ");
        if (isset($this->pluginManager) && isset($options['dependencies'])) {
            $error = [];
            foreach ($options['dependencies'] as $plugin) {
                if (!$this->pluginManager->isEnabled($plugin)) {
                    $error[] = "need plugin " . $plugin . " for route \n";
                }
            }
            if (!empty($error)) {
                return;
            }
        }
        /* Get method(s) from options or from pattern */
        $methods = $options['methods'] ?? null;
        if (strpos($pattern, ' ') !== false) {
            [$methods, $pattern] = explode(' ', trim($pattern), 2);
        }
        if (is_string($methods)) {
            $methods = explode('|', $methods);
        }

        /* Add route to route collection */
        $this->routeCollection[] = [
            'pattern'  => $pattern,
            'callback' => $callback,
            'methods'  => $methods,
            'weight'   => $weight,
            'abstract' => $abstract,
            'path'     => $path,
            'plugin'   => $pluginForNamespace, // TODO: check if nessecary
            'options'  => $options
        ];
    }

    /**
     * Route a given url based on the added route files
     *
     * @param string $uri
     * @param string $method
     *
     * @return \Leap\Core\Route
     */
    public function matchUri(string $uri, string $method = 'GET'): Route
    {
        $uri = trim($uri, "/");

        // Sort route array
        $this->routeCollection = $this->sortRouteCollection($this->routeCollection);
        $parsedRoute           = new Route();

        // Try to match url to one or multiple routes
        foreach ($this->routeCollection as $route) {
            $regex = $this->getBetterRegex($route['pattern']);

            $wildcard_args = [];
            // Search for named parameters
            if (strpos($regex, "{") !== false) {
                if (preg_match_all("#{([a-zA-Z_]+[\w]*)(?::(.+))?}#", $regex, $matches)) {
                    foreach ($matches[0] as $key => $whole_match) {
                        $param_name   = $matches[1][$key];
                        $regexReplace = "([^/]+)";
                        /* check for custom regex for named parameter */
                        if (!empty($matches[2][$key])) {
                            $regexReplace = "(" . $matches[2][$key] . ")";
                        }
                        $regex                   = $wildcard_args['pattern'] = strReplaceFirst($whole_match, $regexReplace, $regex);
                        $wildcard_args['args'][] = $param_name;
                    }
                }
            }
            if (preg_match($regex, $uri)) {
                if (!isset($route['methods']) || in_array($method, $route['methods'])) {
                    /* We found at least one valid route */
                    $this->parseRoute($route, $uri, $wildcard_args, $parsedRoute);
                }
            }
        }

        return $parsedRoute;
    }

    /**
     * Route a PSR-7 Request based on the added route files
     *
     * @param ServerRequestInterface $request
     *
     * @return Route
     */
    public function match(ServerRequestInterface $request): Route
    {
        return $this->matchUri($request->getUri()->getPath(), $request->getMethod());
    }

    /**
     * Sort route array by weight first, then by length of route (key)
     *
     * @param array $routeCollection
     *
     * @return array
     */
    /* TODO: check overhead for sorting, maybe try to improve performance */
    private function sortRouteCollection(array $routeCollection): array
    {
        $weight      = [];
        $routeLength = [];
        foreach ($routeCollection as $route) {
            $pattern  = $route['pattern'];
            $weight[] = $route['weight'];
            /* set length for homepage route to 1 instead of 0 */
            if (empty($pattern)) {
                $pattern = '1';
            }
            /* remove regex and wildcards from route so it doesn't count for the length */
            $wildcards     = ['?', '*', '+', ':'];
            $pattern       = str_replace($wildcards, '', $pattern);
            $pattern       = preg_replace("/\{(.*?)\}/", '', $pattern);
            $pattern       = preg_replace("/\[(.*?)\]/", '', $pattern);
            $routeLength[] = strlen($pattern);
        }
        array_multisort($weight, SORT_ASC, $routeLength, SORT_ASC, $routeCollection);
        return $routeCollection;
    }

    /**
     * Get regex pattern for preg* functions based on fnmatch function pattern
     *
     * @param      $pattern
     *
     * @return string
     */
    private function getBetterRegex(string $pattern): string
    {
        $transforms = [
            '*'  => '[^/]*',
            '**' => '.*',
            '+'  => '[^/]+',
            '++' => '.+',
            '?'  => '.',
            '[!' => '[^',
            '('  => '(?:',
            ')'  => ')?',
        ];

        return '#^' . str_replace('][^/]', ']', strtr(trim($pattern), $transforms)) . '$#i';
    }

    /**
     * Parse a route from a route file
     *
     * @param array            $route
     * @param string           $url
     * @param array            $wildcard_args
     * @param \Leap\Core\Route $parsedRoute
     */
    private function parseRoute(array $route, string $url, array $wildcard_args, Route $parsedRoute): void
    {
        $pattern                       = $route['pattern'];
        $options                       = $route['options'];
        $parsedRoute->mathedPatterns[] = $pattern;
        $parsedRoute->base_path        = $route['path'];
        if (!empty($wildcard_args)) {
            if (preg_match_all($wildcard_args['pattern'], $url, $matches)) {
                $this->replaceWildcardArgs = [];
                global $wildcards_from_url;
                foreach ($matches as $key => $arg) {
                    if (!$key) {
                        continue;
                    }
                    $this->replaceWildcardArgs["{" . $wildcard_args['args'][$key - 1] . "}"] = $arg[0];
                    $wildcards_from_url[$wildcard_args['args'][$key - 1]]              = $arg[0];
                }
            }
        }

        if (isset($options['clear'])) {
            $parsedRoute->defaultRouteValues($options['clear']);
        }

        /* Check for at least one Route that is NOT abstract */
        $abstractRoute = $route['abstract'] ?? false;
        if (!$parsedRoute->routeFound) {
            $parsedRoute->routeFound = !$abstractRoute;
        }

        if (isset($route['callback'])) {
            if (is_callable($route['callback'])) {
                $parsedRoute->callback = $route['callback'];
            } else {
                $parsedRoute->callback = [];
                $parts                 = explode('@', $route['callback']);

                $parsedRoute->callback['class'] = $this->replaceWildcardArgs($parts[0]);
                $action                         = null;
                if (isset($parts[1])) {
                    $action = $this->replaceWildcardArgs($parts[1]);
                }
                $parsedRoute->callback['action'] = $action;
            }
        }
        if (isset($options['parameters']) && is_array($options['parameters'])) {
            foreach ($options['parameters'] as $param => $value) {
                if (is_array($value)) {
                    array_walk_recursive($value, function (&$val) use ($route) {
                        if (is_string($val)) {
                            $val = $this->parseParamValue($val, $route['path']);
                        }
                    });
                } else if (is_string($value)) {
                    $value = $this->parseParamValue($value, $route['path']);
                }

                if (substr($param, -2) === '[]') {
                    $parsedRoute->parameters[substr($param, 0, -2)][] = $this->replaceWildcardArgs($value);
                } else {
                    $parsedRoute->parameters[$param] = $this->replaceWildcardArgs($value);
                }
            }
        }
    }

    /**
     * @param string $value
     * @param string $path
     *
     * @return string
     */
    private function parseParamValue(string $value, string $path): string
    {
        if (strpos($value, ':')) {
            $parts = explode(':', $value);
            $type  = array_shift($parts);
            $value = implode(':', $parts);

            switch ($type) {
                case 'url':
                    /* add base url if file value is not an URL */
                    if (!filter_var($value, FILTER_VALIDATE_URL)) {
                        if ($value[0] === "/") {
                            $value = BASE_URL . substr($value, 1);
                        } else {
                            $value = strReplaceFirst(ROOT, BASE_URL, $path) . $value;
                        }
                    }
                    break;
                case 'file':
                    if ($value[0] === "/") {
                        $value = ROOT . substr($value, 1);
                    } else {
                        $value = $path . $value;
                    }
                    break;
                default:
                    break;
            }
        }
        return $value;
    }

    /**
     * @param $var
     *
     * @return mixed
     */
    private function replaceWildcardArgs($var)
    {
        if (is_string($var) && !empty($this->replaceWildcardArgs)) {
            return strtr($var, $this->replaceWildcardArgs);
        } else {
            return $var;
        }
    }
}
