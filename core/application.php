<?php
namespace Leap\Core;
/**
 * { class_description }
 */
class Application
{
    
    private $router;
    private $controller;
    private $model;
    private $url;
    private $hooks;
    private $plugin_manager;
    private $pdo;

    /**
     * "Start" the application:
     */
    public function __construct()
    {
        $this->setReporting();
        /* TODO: think about making own psr-4 autoload function or use composer */
        //spl_autoload_register(array($this, 'autoloadClasses'));
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
        $this->pdo                = SQLHandler::connect();
        $auto_enable_dependencies = false;
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

        $this->router->addRouteFile(ROOT . "core/routes.ini");
        $this->router->addRouteFile(ROOT . "site/routes.ini");
        $this->hooks->fire("hook_prerouteUrl", array(&$this->url));
        for ($i = 0; $i < 2; $i++) {
            if ($i == 1) {
                if ($this->controller->result == -1) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");
                    $this->router->default_values();
                    $this->url = "permission_denied";
                } else if (!$this->controller->result) {
                    /* Something went wrong */
                    // TODO: error handling
                    break;
                } else {
                    /* everything is oke */
                    break;
                }
            }

            $this->router->routeUrl($this->url);
            /* TODO: consider singleton for plugin_manager and router */
            if(isset($this->router->controllerFile)) {
                /* TODO: think about adding controller and model classes with composer instead of with router */
            } else {
                $this->router->controller = "Leap\\Core\\" . $this->router->controller;
            }
            $this->controller = new $this->router->controller($this->router->model, $this->router->template, $this->router->page, $this->hooks, $this->plugin_manager, $this->pdo, $this->router->stylesheets_route, $this->router->scripts_route, $this->router->title);
        }
        if (method_exists($this->controller, $this->router->action)) {
            $this->controller->{$this->router->action}($this->router->params);
        } else {
            /* TODO: rewrite */
            /* when the second argument is not an action, it is probably a parameter */
            $this->router->params = $this->router->action . "/" . $this->router->params;
            $this->controller->defaultAction($this->router->params);
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
