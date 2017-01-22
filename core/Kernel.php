<?php
namespace Leap\Core;

use Interop\Http\Middleware\MiddlewareInterface;
use mindplay\middleman\Dispatcher;
use Psr\Http\Message\{
    ServerRequestInterface, ResponseInterface
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
     * Kernel constructor.
     */
    public function __construct(Hooks $hooks, Router $router, PluginManager $pluginManager,
                                ControllerFactory $controllerFactory, Config $config)
    {
        /* Set the error reporting level based on the config environment variable */
        $this->setReporting($config->general['environment']);

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
        $this->loadPlugins();
        $this->loadHooks();
        /* ########################################################
         * # Hooks are loaded, so from now on we can fire hooks   #
         * ######################################################## */
        $this->loadRoutes();
        $this->loadMiddleware();
    }

    /**
     * @return callable
     */
    private function getRunFunction(): callable
    {
        return
            function (ServerRequestInterface $request): ResponseInterface {
                /* Fire the hook preRouteUrl */
                $this->hooks->fire("hook_preRouteUrl", []);

                $route = $this->router->match($request);

                /* Create the controller instance */
                $controller = $this->controllerFactory->make($route);

                $response = new Response();
                if (!$controller->hasAccess()) {
                    $response   = $response->withStatus(403);
                    $route      = $this->router->matchUri("permission-denied", $request->getMethod());
                    $controller = $this->controllerFactory->make($route);
                }
                if ($controller) {
                    $controller->init();
                    /* Call the action from the Controller class */
                    if (method_exists($controller, $route->action)) {
                        $controller->{$route->action}();
                    } else {
                        $controller->defaultAction();
                    }
                    /* Render the templates */
                    $body = $controller->render($request);
                    $response->getBody()->write($body);
                }
                return $response;
            };
    }

    /**
     * Load PSR-15 middlewares into the Middelware Stack
     */
    private function loadMiddleware(): void
    {
        /* retrieve middleware and add last framework middleware */
        $middlewares = require "middleware/middlewares.php";
        $this->addMiddleware($middlewares);
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

    /**
     * Load Files with routes into router
     */
    private function loadRoutes(): void
    {
        /* add routes from plugins */
        foreach ($this->pluginManager->enabled_plugins as $pid) {
            $this->router->addRouteFile($this->pluginManager->all_plugins[$pid]['path'] . $pid . ".routes", $pid);
        }
        /* Add router files from core and site theme */
        $this->router->addRouteFile(ROOT . "core/routes.ini", "core");
        $this->router->addRouteFile(ROOT . "site/routes.ini", "site");
    }

    /**
     * Run the Leap Application
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     */
    public function run(ServerRequestInterface $request = null): void
    {
        $this->addMiddleware($this->getRunFunction());
        /* Get PSR-7 Request */
        $request = $request ?? ServerRequestFactory::fromGlobals();
        /* PSR-7 / PSR-15 middleware dispatcher */
        $dispatcher = new Dispatcher($this->middlewares);
        $response   = $dispatcher->dispatch($request);

        (new SapiStreamEmitter())->emit($response);
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
