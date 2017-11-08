<?php
namespace Leap\Core;

use Interop\Http\Middleware\MiddlewareInterface;
use mindplay\middleman\Dispatcher;
use Psr\Http\Message\{
    ResponseInterface, ServerRequestInterface
};
use Zend\Diactoros\{
    Response, Response\SapiStreamEmitter, ServerRequestFactory
};

/**
 * Leap Kernel
 *
 * @package Leap\Core
 */
class Kernel
{
    /**
     * @var Router
     */
    private $router;
    /**
     * @var ControllerFactory
     */
    private $controllerFactory;
    /**
     * @var Hooks
     */
    private $hooks;
    /**
     * @var PluginManager
     */
    private $pluginManager;
    /**
     * $var Config
     */
    private $config;
    /**
     * @var array
     */
    private $middlewares = [];
    /**
     * @var Route
     */
    private $routeForRunFunction;

    /**
     * Kernel constructor.
     */
    public function __construct(Hooks $hooks, Router $router, PluginManager $pluginManager,
                                ControllerFactory $controllerFactory, Config $config)
    {
        /* Set the error reporting level based on the config environment variable */
        $this->setReporting($config->get('environment'));

        /* Store dependency objects as properties */
        $this->hooks             = $hooks;
        $this->router            = $router;
        $this->pluginManager     = $pluginManager;
        $this->controllerFactory = $controllerFactory;
        $this->config            = $config;

        /* Setup the Kernel */
        $this->bootstrap();
    }

    /**
     * Setup the Kernel
     */
    private function bootstrap(): void
    {
        $this->helpersFromConfig();
        $this->loadPlugins();
        $this->loadHooks();
        /* ########################################################
         * # Hooks are loaded, so from now on we can fire hooks   #
         * ######################################################## */
        $this->loadMiddleware();
        $this->loadRoutes();
    }

    /**
     * Get some global helpers which are depending on config variables
     */
    private function helpersFromConfig(): void
    {
        $paths = $this->config->get('paths');
        define('LIBRARIES', $paths['libraries']);
        define('FILES', $paths['files']);
    }


    /**
     * Load all plugins into the application
     */
    private function loadPlugins(): void
    {
        /* Get and load enabled plugins */
        /* TODO: cache getting plugin info in PluginManager */
        $this->pluginManager->getAllPlugins();
        $plugins_to_enable = $this->pluginManager->getEnabledPlugins();
        $this->pluginManager->loadPlugins($plugins_to_enable);
    }

    /**
     * Load all defined hook functions (core + plugins) in hooking system
     */
    private function loadHooks(): void
    {
        /* Add hooks from plugins */
        $functions = get_defined_functions();
        foreach ($functions['user'] as $function) {
            $parts = explode("\\", $function);
            if ($parts[0] === "leap" && $parts[1] === "hooks") {
                if (isset($parts[3])) {
                    $this->hooks->add($parts[3], $parts[2]);
                }
            }
        }
    }

    private function loadMiddleware(): void
    {
        /**
         * Load PSR-15 middlewares into the Middelware Stack
         */
        if ($this->config->has('middleware')) {
            $middleware = include ROOT . $this->config->get('middleware');
            if(!$middleware) {
                // TODO: log middleware file not found warning
            } else {
                $this->addMiddleware($middleware);
            }
        }
    }


    /**
     * @param array|MiddlewareInterface|callable $middleware
     */
    public function addMiddleware($middleware): void
    {
        if (is_array($middleware)) {
            $this->middlewares = array_merge($this->middlewares, $middleware);
        } else {
            $this->middlewares[] = $middleware;
        }
    }

    /**
     * Load Files with routes into router
     */
    private function loadRoutes(): void
    {
        /* add routes from plugins */
        foreach ($this->pluginManager->enabled_plugins as $pid) {
            $this->router->addFile($this->pluginManager->all_plugins[$pid]['path'] . $pid . ".routes.php", $pid);
        }

        /* add routes from core */
        $this->router->addFile(ROOT . "core/core.routes.php", "core");

        /* add routes from config */
        if ($this->config->has('routes')) {
            foreach($this->config->get('routes') as $route) {
                $this->router->addFile(ROOT . $route, "app");
            }
        }
    }

    /**
     * Run the Leap Application
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Leap\Core\Route                         $route
     */
    public function run(ServerRequestInterface $request = null, Route $route = null): void
    {
        /* Get PSR-7 Request */
        $request = $request ?? ServerRequestFactory::fromGlobals();

        /* Set (optional) Route for this run. If this Route is null,
         * the Route will be created from the PSR-7 Request object. */
        $this->routeForRunFunction = $route;

        $this->middlewares[] = $this->getRunFunction();
        /* PSR-7 / PSR-15 middleware dispatcher */
        $dispatcher = new Dispatcher($this->middlewares);
        $response   = $dispatcher->dispatch($request);

        /* Output the PSR-7 Response object */
        (new SapiStreamEmitter())->emit($response);
    }


    /**
     * @return callable
     */
    private function getRunFunction(): callable
    {
        return
            function (ServerRequestInterface $request): ResponseInterface {
                $response                  = new Response();
                /* Fire the hook preRouteUrl */
                $this->hooks->fire("hook_preRouteUrl", []);
                $route                     = $this->routeForRunFunction ?? $this->router->route($request);
                $this->routeForRunFunction = null;
                $body                      = null;
                switch($route->status) {
                    case Route::NOT_FOUND:
                        $response = $response->withStatus(404);
                        $route    = $this->router->routeUri("404", $request->getMethod());
                        break;
                    case Route::METHOD_NOT_ALLOWED:
                        $response = $response->withStatus(405);
                        $route    = $this->router->routeUri("method-not-allowed", $request->getMethod());
                        break;
                    case Route::FOUND:
                        break;
                    default:
                        // TODO: error
                        break;
                }

                if (is_callable($route->callback)) {
                    $body = call_user_func($route->callback, [$route->parameters, $request]);
                } else if (is_array($route->callback)) {
                    /* Create the controller instance */
                    $controller = $this->controllerFactory->make($route);

                    if (!$controller->hasAccess()) {
                        $response = $response->withStatus(403);
                        $route    = $this->router->routeUri("permission-denied", $request->getMethod());
                        /* recreate controller for permission denied route */
                        if (is_callable($route->callback)) {
                            $body       = call_user_func($route->callback);
                            $controller = null;
                        } else {
                            $controller = $this->controllerFactory->make($route);
                        }
                    }
                    if ($controller) {
                        $controller->init();
                        /* Call the action from the Controller class */
                        if (isset($route->callback['action'])) {
                            if (!method_exists($controller, $route->callback['action'])) {
                                // TODO: error handling
                                die($route->callback['action'] . " method not found");
                            }
                            $body = $controller->{$route->callback['action']}($route->parameters, $request);
                        } else {
                            $body = $controller($route->parameters, $request);
                        }
                    }
                } else {
                    // TODO: error handling
                    die("not a valid callback (no Controller and no callable method)");
                }
                if ($body instanceof ResponseInterface) {
                    $response = $body;
                } else {
                    $response->getBody()->write($body);
                }

                return $response;
            };
    }


    /**
     * Set error level based on environment
     *
     * @param null $environment
     */
    private function setReporting($environment = null): void
    {
        error_reporting(E_ALL);
        if ($environment === 'development' || $environment === 'dev') {
            ini_set('display_errors', 1);
        } else {
            ini_set('display_errors', 0);
        }
        /* Always log the errors */
        ini_set('log_errors', 1);
        /* TODO: create variable for custom log file */
        ini_set('error_log', ROOT . 'core/logs/error.log');
    }
}
