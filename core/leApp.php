<?php
namespace Leap\Core;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Leap Application
 *
 * @package Leap\Core
 */
class LeApp
{
    private $router;
    private $controller;
    private $path;
    private $hooks;
    private $plugin_manager;
    private $pdo;
    private $route;

    /**
     * LeApp constructor.
     */
    public function __construct()
    {
        /* Set the error reporting level based on the environment variable */
        $this->setReporting();

        /* Create PSR7 request and response */
        $request  = ServerRequestFactory::fromGlobals();
        $response = new Response();

        /* - Object creation - */
        $this->hooks = new Hooks();
        /* TODO: consider singleton for plugin_manager and router. Bad practice or allowed in this situation? */
        $this->router         = new Router();
        $this->plugin_manager = new PluginManager($this->router, $this->hooks);
        /* TODO: Can we get rid of this setter injection? */
        $this->router->setPluginManager($this->plugin_manager);

        /* - Variable values - */
        $params = $request->getQueryParams();
        if (isset($params['path'])) {
            $this->path = $request->getQueryParams()['path'];
        } else {
            $this->path = "";
        }

        /* Setup the application */
        $this->bootstrap($request, $response);
    }

    /**
     * Boot up the application
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface      $response
     */
    private function bootstrap(ServerRequestInterface $request, ResponseInterface $response)
    {
        session_start();

        /* Try to connect to a database. Returns -1 when no database is used */
        $this->pdo = SQLHandler::connect();
        /* TODO: cache getting plugin info */
        $this->plugin_manager->getAllPlugins($this->pdo);
        $plugins_to_enable = $this->plugin_manager->getEnabledPlugins($this->pdo);
        $this->plugin_manager->loadPlugins($plugins_to_enable);

        /* ########################################################
         * # Plugins are loaded, so from now on we can fire hooks #
         * ######################################################## */

        /* Add router files from core and site theme */
        $this->router->addRouteFile(ROOT . "core/routes.ini", "core");
        $this->router->addRouteFile(ROOT . "site/routes.ini", "site");

        /* Fire the hook preRouteUrl */
        $this->hooks->fire("hook_preRouteUrl", [&$this->path]);
        // Retrieve the route
        $this->route = $this->getRoute($this->path, $request->getMethod());
    }

    public function getRoute($path, $method)
    {
        /* Get route information for the url */
        $route = $this->router->routeUrl($path, $method);
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
     * Boot up the application
     *
     * @param array $route
     */
    public function run($route = null)
    {
        if (!isset($route)) {
            if (isset($this->route)) {
                $route = $this->route;
            } else {
                printr("no route to run.", true);
            }
        }
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
            header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");
            $route = $this->getRoute("permission-denied");
            $this->run($route);
        } else {
            /* Call the action from the Controller class */
            if (method_exists($this->controller, $route['action'])) {
                $this->controller->{$route['action']}();
            } else {
                $this->controller->defaultAction();
            }
            /* Render the templates */
            $this->controller->render();
        }
    }

    /**
     * @param string $uri
     *
     * @return array
     */
    private function pageNotFound($uri = "")
    {
        header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
        if ($uri != '404') {
            return $this->getRoute('404');
        } else {
            printr("Page not found and no valid route found for 404 page", true);
        }
    }

    /**
     * Set error level based on environment
     */
    private function setReporting()
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
