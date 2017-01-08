<?php
namespace Leap\Core;

use mindplay\middleman\Dispatcher;
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
        $this->di->set('hooks', $this->di->lazyNew(Hooks::class));
        $this->di->set('router', $this->di->lazyNew(Router::class));
        $this->di->set('pluginManager', $this->di->lazyNew(PluginManager::class));
//        $this->di->params(Controller::class)['request'] = $this->response;
//        $this->di->params(Controller::class)['response'] = $this->response;
//        $this->di->params(Controller::class)['route'] = $this->response;
//        $this->di->params(Controller::class)['hooks'] = $this->response;
//        $this->di->params(Controller::class)['plugin_manager'] = $this->response;
//        $this->di->params(Controller::class)['pdo'] = $this->response;

        /* Fetch objects from DI Container */
        $this->hooks          = $this->di->get('hooks');
        $this->router         = $this->di->get('router');
        $this->plugin_manager = $this->di->get('pluginManager');

        /* Set plugin manager in router to support making routes dependent on plugins (optional) */
        $this->router->setPluginManager($this->plugin_manager);

        /* Get path parameter from request */
        $params     = $this->request->getQueryParams();
        $this->path = $params['path'] ?? "";

        /* Setup the Kernel */
        $this->bootstrap();
    }

    /**
     * Setup the Kernel
     */
    private function bootstrap(): void
    {
        /* Connect to database if specified in config */
        $db_conf = config('database');
        if ($db_conf['db_type'] === "mysql") {
            if (!isset($db_conf['db_host']) || !isset($db_conf['db_user']) || !isset($db_conf['db_pass']) || !isset($db_conf['db_name'])) {
                // TODO: error handling
                die('not enough database info');
            }
            /* Create PdoPlus object with pdo connection inside */
            $this->pdo = new PdoPlus($db_conf['db_host'], $db_conf['db_user'], $db_conf['db_pass'], $db_conf['db_name']);
        } else {
            $this->pdo = -1;
        }

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
                $this->hooks->fire("hook_preRouteUrl", [&$this->path]);
                // Retrieve the route
                $route = $this->getRoute($this->path);
                /* Check if controller class extends the core controller */
                if ($route['controller']['class'] == 'Leap\Core\Controller' || is_subclass_of($route['controller']['class'], "Leap\\Core\\Controller")) {
                    /* Create the controller instance */
                    $this->controller = new $route['controller']['class']($route, $this->hooks, $this->plugin_manager, $this->pdo);
                } else if (class_exists($route['controller']['class'])) {
                    printr("Controller class '" . $route['controller']['class'] . "' does not extend the base 'Leap\\Core\\Controller' class", true);
                } else {
                    printr("Controller class '" . $route['controller']['class'] . "' not found", true);
                }
                if (!$this->controller->access) {
                    $this->response = $this->response->withStatus(403);
                    $this->path     = "permission-denied";
                    //return $runFunction($request, $this->response, $done);
                } else {
                    /* Call the action from the Controller class */
                    if (method_exists($this->controller, $route['action'])) {
                        $this->controller->{$route['action']}();
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
    private function getRoute(string $path): array
    {
        /* Get route information for the url */
        $route = $this->router->routeUrl($path, $this->request->getMethod());
        if (empty($route['page']) || !file_exists($route['page']['path'] . $route['page']['value'])) {
            $route = $this->pageNotFound($path);
        }

        if (isset($route['model']['file'])) {
            global $autoloader;
            $autoloader->addClassMap(["Leap\\Plugins\\" . ucfirst($route['model']['plugin']) . "\\Models\\" . $route['model']['class'] => $route['model']['file']]);
        }
        if (isset($route['controller']['file'])) {
            global $autoloader;
            $autoloader->addClassMap(["Leap\\Plugins\\" . ucfirst($route['controller']['plugin']) . "\\Controllers\\" . $route['controller']['class'] => $route['controller']['file']]);
        }

        /* If the controller class name does not contain the namespace yet, add it */
        if (strpos($route['controller']['class'], "\\") === false && isset($route['controller']['plugin'])) {
            $namespace                    = getNamespace($route['controller']['plugin'], "controller");
            $route['controller']['class'] = $namespace . $route['controller']['class'];
        }
        /* If the model name does not contain the namespace yet, add it */
        if (strpos($route['model']['class'], "\\") === false && isset($route['model']['plugin'])) {
            $namespace               = getNamespace($route['model']['plugin'], "model");
            $route['model']['class'] = $namespace . $route['model']['class'];
        }
        return $route;
    }

    /**
     * @param string $uri
     *
     * @return array
     */
    private function pageNotFound(string $uri = ""): array
    {
        $this->response = $this->response->withStatus(404);

        if ($uri == '404') {
            printr("Page not found and no valid route found for 404 page", true);
        }
        return $this->getRoute('404');
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
