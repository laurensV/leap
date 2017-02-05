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
     * Router constructor.
     */
    public function __construct()
    {
        $this->routeCollection = [];
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
    public function routeUri(string $uri, string $method = 'GET'): Route
    {
        $uri = trim($uri, "/");

        // Sort route array
        $this->routeCollection = $this->sortRouteCollection($this->routeCollection);
        $parsedRoute           = new Route();

        // Try to match url to one or multiple routes
        foreach ($this->routeCollection as $route) {
            $regex = $this->getBetterRegex($route['pattern']);

            // Search for named parameters
            $paramNames = [];
            if (strpos($regex, "{") !== false) {
                if (preg_match_all("#{([a-zA-Z_]+[\w]*)(?::(.+))?}#", $regex, $matches)) {
                    foreach ($matches[0] as $key => $whole_match) {
                        $param_name = $matches[1][$key];
                        /* default regex for named paramters */
                        $regexReplace = "([^/]+)";
                        /* check for custom regex for named parameter */
                        if (!empty($matches[2][$key])) {
                            $regexReplace = "(" . $matches[2][$key] . ")";
                        }
                        /* replace named parameters with regex */
                        $regex        = strReplaceFirst($whole_match, $regexReplace, $regex);
                        $paramNames[] = $param_name;
                    }
                }
            }

            /* Check if uri matches the routes regex */
            if (preg_match($regex, $uri, $paramValues)) {
                if (!isset($route['methods']) || in_array($method, $route['methods'])) {
                    /* resolve any named parameters */
                    $parameters = [];
                    if (!empty($paramNames)) {
                        foreach ($paramNames as $k => $paramName) {
                            $parameters['{' . $paramName . '}'] = $paramValues[$k + 1];
                        }
                    }
                    /* We found at least one valid route */
                    $this->parseRoute($route, $parameters, $parsedRoute);
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
    public function route(ServerRequestInterface $request): Route
    {
        return $this->routeUri($request->getUri()->getPath(), $request->getMethod());
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
     * @param array            $parameters
     * @param \Leap\Core\Route $parsedRoute
     */
    private function parseRoute(array $route, array $parameters, Route $parsedRoute): void
    {
        $pattern                       = $route['pattern'];
        $options                       = $route['options'];
        $parsedRoute->mathedPatterns[] = $pattern;
        $parsedRoute->base_path        = $route['path'];

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

                $parsedRoute->callback['class'] = $this->replaceParams($parts[0], $parameters);
                $action                         = null;
                if (isset($parts[1])) {
                    $action = $this->replaceParams($parts[1], $parameters);
                }
                $parsedRoute->callback['action'] = $action;
            }
        }
        foreach($parameters as $paramName => $paramValue) {
            $parsedRoute->parameters[substr($paramName, 1, -1)] = $paramValue;
        }
        if (isset($options['parameters']) && is_array($options['parameters'])) {
            foreach ($options['parameters'] as $param => $value) {
                if (is_array($value)) {
                    array_walk_recursive($value, function (&$val) use ($route) {
                        if (is_string($val)) {
                            $val = parsePath($val, $route['path']);
                        }
                    });
                } else if (is_string($value)) {
                    $value = parsePath($value, $route['path']);
                }

                if (substr($param, -2) === '[]') {
                    $parsedRoute->parameters[substr($param, 0, -2)][] = $this->replaceParams($value, $parameters);
                } else {
                    $parsedRoute->parameters[$param] = $this->replaceParams($value, $parameters);
                }
            }
        }
    }

    /**
     * @param mixed $var
     * @param array $parameters
     *
     * @return mixed
     */
    private function replaceParams($var, array $parameters)
    {
        if (is_string($var) && !empty($parameters)) {
            $var = strtr($var, $parameters);
        }
        return $var;
    }
}
