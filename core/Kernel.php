<?php
namespace Leap\Core;

use Leap\Core\Middleware\TestMiddleware;
use mindplay\middleman\{
    ContainerResolver, Dispatcher
};
use Psr\Http\Message\{
    ServerRequestInterface, ResponseInterface
};
use Zend\Diactoros\{
    Response, Response\SapiStreamEmitter, ServerRequestFactory
};
use Aura\Di\ContainerBuilder;
use Interop\Container\ContainerInterface;

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
     * @var Controller
     */
    private $controller;
    /**
     * @var string
     */
    private $path;
    /**
     * @var Hooks
     */
    private $hooks;
    /**
     * @var PluginManager
     */
    private $plugin_manager;
    /**
     * @var PdoPlus
     */
    private $pdo;
    /**
     * @var ServerRequestInterface
     */
    private $request;
    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var ContainerInterface
     */
    private $di;

    /**
     * @var array
     */
    private $middlewares;

    /**
     * Kernel constructor.
     */
    public function __construct()
    {
        /* Set the error reporting level based on the environment variable */
        /* Q: is this the right place to call this function? Maybe configHandler.php is better.. */
        $this->setReporting();

        /* Create PSR7 Request and Response */
        $this->request  = ServerRequestFactory::fromGlobals();
        $this->response = new Response();

        /* Create and config Dependency Injector Container */
        $builder  = new ContainerBuilder();
        $this->di = $builder->newInstance();

        /* Services */
        $this->di->set('hooks', $this->di->lazyNew(Hooks::class));
        $this->di->set('router', $this->di->lazyNew(Router::class));
        $this->di->set('pluginManager', $this->di->lazyNew(PluginManager::class));
        $this->di->set('route', $this->di->lazy([$this, 'getRoute'],
                                                $this->request
        ));
        $this->di->set('controller', $this->di->lazy(function () {
            $route = $this->di->get('route');
            return $this->di->newInstance($route->controller['class']);
        }));
        /* Set database service if specified in config */
        $db_conf = config('database');
        if ($db_conf['db_type'] === "mysql") {
            if (!isset($db_conf['db_host']) || !isset($db_conf['db_user']) || !isset($db_conf['db_pass']) || !isset($db_conf['db_name'])) {
                // TODO: error handling
                die('not enough database info');
            }
            /* Create PdoPlus object with pdo connection inside */
            $this->di->set('pdo', $this->di->lazyNew(PdoPlus::class));
            $this->di->params[PdoPlus::class]['host']     = $db_conf['db_host'];
            $this->di->params[PdoPlus::class]['username'] = $db_conf['db_user'];
            $this->di->params[PdoPlus::class]['password'] = $db_conf['db_pass'];
            $this->di->params[PdoPlus::class]['dbName']   = $db_conf['db_name'];
        }

        $this->di->params[Controller::class]['route']          = $this->di->lazyGet('route');
        $this->di->params[Controller::class]['hooks']          = $this->di->lazyGet('hooks');
        $this->di->params[Controller::class]['plugin_manager'] = $this->di->lazyGet('pluginManager');
        $this->di->params[Controller::class]['pdo']            = $this->di->has('pdo') ? $this->di->lazyGet('pdo') : null;

        /* Set plugin manager in router to support making routes dependent on plugins (optional) */
        $this->di->setters[Router::class]['setPluginManager'] = $this->di->lazyGet('pluginManager');;

        /* Fetch objects from DI Container */
        $this->hooks          = $this->di->get('hooks');
        $this->router         = $this->di->get('router');
        $this->plugin_manager = $this->di->get('pluginManager');
        $this->pdo            = $this->di->has('pdo') ? $this->di->get('pdo') : -1;

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
        $this->plugin_manager->getAllPlugins($this->pdo);
        $plugins_to_enable = $this->plugin_manager->getEnabledPlugins($this->pdo);
        $this->plugin_manager->loadPlugins($plugins_to_enable);

        /* Add hooks from plugins */
        $functions = get_defined_functions();
        foreach ($functions['user'] as $function) {
            $parts = explode("\\", $function);
            if ($parts[0] == "leap" && $parts[1] == "hooks") {
                if (isset($parts[3])) {
                    $this->hooks->add($parts[3], $parts[2]);
                }
            }
        }
        /* ########################################################
         * # Plugins are loaded, so from now on we can fire hooks #
         * ######################################################## */

        /* add routes from plugins */
        foreach ($this->plugin_manager->enabled_plugins as $pid) {
            $this->router->addRouteFile($this->plugin_manager->all_plugins[$pid]['path'] . $pid . ".routes", $pid);
        }
        /* Add router files from core and site theme */
        $this->router->addRouteFile(ROOT . "core/routes.ini", "core");
        $this->router->addRouteFile(ROOT . "site/routes.ini", "site");

        /* retrieve middleware and add last framework middleware */
        $this->middlewares   = require "middlewares.php";
        $this->middlewares[] =
            function (ServerRequestInterface $request): ResponseInterface {
                /* Fire the hook preRouteUrl */
                $this->hooks->fire("hook_preRouteUrl", []);

                $route = $this->di->get('route');
                /* Check if controller class extends the core controller */
                if ($route->controller['class'] == 'Leap\Core\Controller' || is_subclass_of($route->controller['class'], "Leap\\Core\\Controller")) {
                    /* Create the controller instance */
                    $this->controller = $this->di->get('controller');
                } else if (class_exists($route->controller['class'])) {
                    printr("Controller class '" . $route->controller['class'] . "' does not extend the base 'Leap\\Core\\Controller' class", true);
                } else {
                    printr("Controller class '" . $route->controller['class'] . "' not found", true);
                }
                if (!$this->controller->access) {
                    $this->response = $this->response->withStatus(403);
                    $this->path     = "permission-denied";
                    //return $runFunction($request, $this->response, $done);
                } else {
                    /* Call the action from the Controller class */
                    if (method_exists($this->controller, $route->action)) {
                        $this->controller->{$route->action}();
                    } else {
                        $this->controller->defaultAction();
                    }
                    /* Render the templates */
                    $html = $this->controller->render($this->request);
                    $this->response->getBody()->write($html);
                }
                return $this->response;
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
     * @param string $path
     *
     * @return array
     */
    public function getRoute(ServerRequestInterface $request): Route
    {
        /* Get route information for the url */
        $route = $this->router->match($request);
        /* Check if page exists */
        if (empty($route->page) || !file_exists($route->page['path'] . $route->page['value'])) {
            $this->response = $this->response->withStatus(404);
            $route          = $this->router->matchUri('404', $request->getMethod());
        }

        if (isset($route->controller['file'])) {
            global $autoloader;
            $autoloader->addClassMap(["Leap\\Plugins\\" . ucfirst($route->controller['plugin']) . "\\Controllers\\" . $route->controller['class'] => $route->controller['file']]);
        }

        /* If the controller class name does not contain the namespace yet, add it */
        if (strpos($route->controller['class'], "\\") === false && isset($route->controller['plugin'])) {
            $namespace                  = getNamespace($route->controller['plugin'], "controller");
            $route->controller['class'] = $namespace . $route->controller['class'];
        }

        return $route;
    }

    /**
     * Set error level based on environment
     */
    private function setReporting(): void
    {
        error_reporting(E_ALL);
        if (config('general')['dev_env'] == true) {
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
