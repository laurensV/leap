<?php
namespace Leap\Core;

use Composer\DependencyResolver\Request;
use mindplay\middleman\Dispatcher;
use Psr\Http\Message\{
    ServerRequestInterface, ResponseInterface
};
use Zend\Diactoros\{
    Response, Response\SapiStreamEmitter
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
     * @var ServerRequestInterface
     */
    private $request;
    /**
     * @var array
     */
    private $middlewares;

    /**
     * Kernel constructor.
     */
    public function __construct(Hooks $hooks, Router $router, PluginManager $pluginManager, ControllerFactory $controllerFactory, ServerRequestInterface $request)
    {
        /* Set the error reporting level based on the environment variable */
        /* Q: is this the right place to call this function? Maybe configHandler.php is better.. */
        $this->setReporting();

        /* Fetch objects from DI Container */
        $this->hooks             = $hooks;
        $this->router            = $router;
        $this->pluginManager     = $pluginManager;
        $this->controllerFactory = $controllerFactory;
        $this->request           = $request;

        /* Setup the Kernel */
        $this->bootstrap();

    }

    /**
     * Setup the Kernel
     */
    private function bootstrap(): void
    {
        /* Get and load enabled plugins */
        /* TODO: cache getting plugin info in PluginManager */
        $this->pluginManager->getAllPlugins();
        $plugins_to_enable = $this->pluginManager->getEnabledPlugins();
        $this->pluginManager->loadPlugins($plugins_to_enable);

        /* Add hooks from plugins */
        $functions = get_defined_functions();
        foreach($functions['user'] as $function) {
            $parts = explode("\\", $function);
            if($parts[0] == "leap" && $parts[1] == "hooks") {
                if(isset($parts[3])) {
                    $this->hooks->add($parts[3], $parts[2]);
                }
            }
        }
        /* ########################################################
         * # Plugins are loaded, so from now on we can fire hooks #
         * ######################################################## */

        /* add routes from plugins */
        foreach($this->pluginManager->enabled_plugins as $pid) {
            $this->router->addRouteFile($this->pluginManager->all_plugins[$pid]['path'] . $pid . ".routes", $pid);
        }
        /* Add router files from core and site theme */
        $this->router->addRouteFile(ROOT . "core/routes.ini", "core");
        $this->router->addRouteFile(ROOT . "site/routes.ini", "site");

        /* retrieve middleware and add last framework middleware */
        $this->middlewares   = require "middlewares.php";
        $this->middlewares[] =
            function(ServerRequestInterface $request): ResponseInterface {
                /* Fire the hook preRouteUrl */
                $this->hooks->fire("hook_preRouteUrl", []);

                $route = $this->router->match($this->request);

                /* Create the controller instance */
                $controller = $this->controllerFactory->make($route);

                $response = new Response();
                if(!$controller->access) {
                    $this->response = $response->withStatus(403);
                    $this->path     = "permission-denied";
                    /* TODO: permission denied handling handling */
                    //return $runFunction($request, $this->response, $done);
                } else {
                    /* Call the action from the Controller class */
                    if(method_exists($controller, $route->action)) {
                        $controller->{$route->action}();
                    } else {
                        $controller->defaultAction();
                    }
                    /* Render the templates */
                    $html = $controller->render($this->request);
                    $response->getBody()->write($html);
                }
                return $response;
            };
    }

    /**
     * Boot up the application
     */
    public function run(): void
    {
        $dispatcher = new Dispatcher($this->middlewares);
        $response   = $dispatcher->dispatch($this->request);

        (new SapiStreamEmitter())->emit($response);
    }

    /**
     * Set error level based on environment
     */
    private function setReporting(): void
    {
        error_reporting(E_ALL);
        if(config('general')['dev_env'] == true) {
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
