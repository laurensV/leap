<?php
namespace Leap\Core;

/**
 * Leap Application
 *
 * @package Leap\Core
 */
class LeApp
{
    private $router;
    private $controller;
    private $url;
    private $hooks;
    private $plugin_manager;
    private $pdo;

    /**
     * LeApp constructor.
     */
    public function __construct()
    {
        /* Set the error reporting level based on the environment variable */
        $this->setReporting();
        /* - Object creation - */
        $this->hooks = new Hooks();
        /* TODO: consider singleton for plugin_manager and router. Bad practice or allowed in this situation? */
        $this->router         = new Router();
        $this->plugin_manager = new PluginManager($this->router, $this->hooks);
        /* TODO: Can we get rid of this setter injection? */
        $this->router->setPluginManager($this->plugin_manager);

        /* - Variable values - */
        $this->url = $this->getUrl();

        /* Setup the application */
        $this->bootstrap();
    }

    /**
     * Setup the application
     */
    private function bootstrap()
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

        $this->hooks->fire("hook_preRouteUrl", [&$this->url]);

        /* has to be run twice in order to check if there was a redirect to
         * the permission denied page */
        for ($run = 1; $run <= 2; $run++) {
            /* check if we are in second run of for loop */
            if ($run == 2) {
                /* check if we have access in the controller */
                if (!$this->controller->access) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");
                    /* reroute to permission denied page */
                    $this->url = "permission_denied";
                } else {
                    /* We have access, break out of this for loop */
                    break;
                }
            }

            /* Get route information for the url */
            $route = $this->router->routeUrl($this->url);

            /* If the controller class name does not contain the namespace yet, add it */
            if (strpos($route['controller']['class'], "\\") === false) {
                $namespace                    = getNamespace($route['controller']['plugin'], "controller");
                $route['controller']['class'] = $namespace . $route['controller']['class'];
            }
            /* If the model name does not contain the namespace yet, add it */
            if (strpos($route['model']['class'], "\\") === false) {
                $namespace               = getNamespace($route['model']['plugin'], "model");
                $route['model']['class'] = $namespace . $route['model']['class'];
            }

            /* Check if controller class extends the core controller */
            if ($route['controller']['class'] == 'Leap\Core\Controller' || is_subclass_of($route['controller']['class'], "Leap\\Core\\Controller")) {
                /* Create the controller instance */
                $this->controller = new $route['controller']['class']($route, $this->hooks, $this->plugin_manager, $this->pdo);
            } else if (class_exists($route['controller']['class'])) {
                printr("Controller class '" . $route['controller']['class'] . "' does not extend the base 'Leap\\Core\\Controller' class");
            } else {
                printr("Controller class '" . $route['controller']['class'] . "' not found");
            }

        }
        if (method_exists($this->controller, $route['action'])) {
            $this->controller->{$route['action']}($route['params']);
        } else {
            /* TODO: rewrite */
            /* when the second argument is not an action, it is probably a parameter */
            $route['params'] = $route['action'] . "/" . $route['params'];
            $this->controller->defaultAction($route['params']);
        }

        $this->controller->render();
    }

    /**
     * Retrieve the raw arguments after the base url
     *
     * @return     string
     */
    private function getUrl()
    {
        return rtrim(isset($_GET['args']) ? $_GET['args'] : "", "/");
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
