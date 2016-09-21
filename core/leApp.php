<?php
namespace Leap\Core;

/**
 * { class_description }
 */
class LeApp
{
    private $router;
    private $controller;
    private $model;
    private $url;
    private $hooks;
    private $plugin_manager;
    private $pdo;

    /**
     * { function_description }
     */
    public function __construct()
    {
        /* TODO: consider singleton for plugin_manager and router */
        $this->setReporting();
        $this->hooks          = new Hooks();
        $this->url            = $this->getUrl();
        $this->router         = new Router();
        $this->plugin_manager = new PluginManager($this->router, $this->hooks);
        $this->router->setPluginManager($this->plugin_manager);
        $this->bootstrap();
    }

    /**
     * { function_description }
     */
    private function bootstrap()
    {
        session_start();
        /* Try to connect to a database. Returns -1 when no database is used */
        $this->pdo                = SQLHandler::connect();
        $auto_enable_dependencies = false;
        /* TODO: cache getting plugin info */
        $this->plugin_manager->getAllPlugins($this->pdo);
        if (is_object($this->pdo)) {
            $plugins_to_enable = $this->plugin_manager->pluginsToLoad($this->pdo);
        } else {
            if ($this->pdo == -1) {
                /* site is run without database, so use custom function to load plugins */
                $plugins_to_enable        = $this->plugin_manager->PluginsToLoadNoDB();
                $auto_enable_dependencies = true;
            } else {
                printr("database error");
            }
        }

        $this->plugin_manager->loadPlugins($plugins_to_enable);

        /******
        Plugins are loaded, so from now on we can fire hooks
         ******/

        $this->router->addRouteFile(ROOT . "core/routes.ini", "core");
        $this->router->addRouteFile(ROOT . "site/routes.ini", "site");
        //printr($this->router->routes);
        $this->hooks->fire("hook_preRouteUrl", array(&$this->url));
        
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

            /* TODO: let this function return the right values instead of storing this in router object */
            $route = $this->router->routeUrl($this->url);
            
            if ($route['controllerFile']['plugin'] == 'core') {
                $namespace = "Leap\\Core\\";
            } else if ($route['controllerFile']['plugin'] == 'site') {
                $namespace = "Leap\\Site\\Controllers\\";
            } else {
                $namespace = "Leap\\Plugins\\" . ucfirst($route['controllerFile']['plugin']) . "\\Controllers\\";
            }
            $route['controller'] = $namespace . $route['controller'];
            if ($route['controller'] == 'Leap\Core\Controller' || is_subclass_of ($route['controller'], "Controller")){
                $this->controller = new $route['controller']($route, $this->hooks, $this->plugin_manager, $this->pdo);
            } else {
                printr("Controller class '" . $route['controller'] . "' does not extend the base 'Leap\Core\Controller' class");
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
     * retrieve the raw arguments after the base url
     *
     * @return     string
     */
    private function getUrl()
    {
        return rtrim(isset($_GET['args']) ? $_GET['args'] : "", "/");
    }

    /**
     * Check if environment is development and display errors
     */
    private function setReporting()
    {
        error_reporting(E_ALL);
        if (config('general')['dev_env'] == true) {
            ini_set('display_errors', 1);
        } else {
            ini_set('display_errors', 0);
        }
        ini_set('log_errors', 1);
        ini_set('error_log', ROOT . 'core/logs/error.log');
    }

    /**
     * Autoload any classes that are required
     *
     * @param      string  $className  name of the class to autoload
     */
    private function autoloadClasses($className)
    {
        /* TODO: rewrite folder structure classes */
        if (file_exists(ROOT . 'core/' . strtolower($className) . '.php')) {
            require_once ROOT . 'core/' . strtolower($className) . '.php';
        } else if (file_exists(ROOT . 'core/include/classes/' . $className . '.class.php')) {
            require_once ROOT . 'core/include/classes/' . $className . '.class.php';
        }
    }
}
