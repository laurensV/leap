<?php
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
     * Analyze the URL elements and calls the according controller/method or the fallback
     */
    public function __construct()
    {
        $this->setReporting();
        $this->addClasses();
        spl_autoload_register(array($this, 'autoloadClasses'));
        $this->defineHooks();
        $this->url            = $this->getUrl();
        $this->router         = new Router();
        $this->plugin_manager = new PluginManager($this->router, $this->hooks);
        $this->router->setPluginManager($this->plugin_manager);
        $this->bootstrap();
    }

    private function getUrl()
    {
        return rtrim(isset($_GET['args']) ? $_GET['args'] : "", "/");
    }

    private function defineHooks()
    {
        /* TODO: automatically find hooks */
        $hook_names  = array("parseStylesheet", "prerouteUrl", "adminLinks");
        $this->hooks = new Hooks($hook_names);
    }

    /**
     * Connects to database *
     */
    public function connect($host, $user, $pwd, $db_name)
    {
        $this->db = @mysql_connect($host, $user, $pwd);
        if ($this->db != 0) {
            if (mysql_select_db($db_name, $this->db)) {
                return 1;
            }
        }
        return 0;
    }

    public function connectWithConfig()
    {
        $db_conf = config('database');
        if ($db_conf['db_type'] == "mysql") {
            if (!isset($db_conf['db_host']) || !isset($db_conf['db_user']) || !isset($db_conf['db_pass']) || !isset($db_conf['db_name'])) {
                return 0;
            }
            return $this->connect($db_conf['db_host'], $db_conf['db_user'], $db_conf['db_pass'], $db_conf['db_name']);
        } else {
            return -1;
        }
    }

    private function customPluginsToLoad()
    {
        return array("admin", "less", "alias", "plugin_manager");
    }

    private function bootstrap()
    {
        $this->pdo                = SQLHandler::connect();
        $auto_enable_dependencies = false;
        if (is_object($this->pdo)) {
            $plugins_to_enable = $this->plugin_manager->plugins_to_load($this->pdo);
        } else {
            if ($this->pdo == -1) {
                /* site is run without database, so use custom function to load plugins */
                $plugins_to_enable        = $this->customPluginsToLoad();
                $auto_enable_dependencies = true;
            } else {
                printr("database error");
            }
        }
        $this->plugin_manager->getAllPlugins($this->pdo);
        $plugins = $this->plugin_manager->getSublistPlugins($plugins_to_enable);

        $this->plugin_manager->loadPlugins($plugins, $auto_enable_dependencies);
        /* Plugins are loaded, so from now on we can fire hooks */
        $this->router->addRouteFile(ROOT . "/core/routes.ini");
        $this->router->addRouteFile(ROOT . "/site/routes.ini");
        $this->hooks->fire("prerouteUrl", array(&$this->url));
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
            $this->controller = new $this->router->controller($this->router->model, $this->router->template, $this->router->page, $this->hooks, $this->plugin_manager, $this->pdo, $this->router->stylesheets_route, $this->router->scripts_route);
        }
        if (method_exists($this->controller, $this->router->action)) {
            $this->controller->{$this->router->action}($this->router->params);
        } else {
            /* when the second argument is not an action, it is probably a parameter */
            $this->router->params = $this->router->action . "/" . $this->router->params;
            $this->controller->defaultAction($this->router->params);
        }

        $this->controller->render();
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
        ini_set('error_log', ROOT . '/core/logs/error.log');
    }

    /**
     * Autoload any classes that are required
     */
    public function autoloadClasses($className)
    {
        if (file_exists(ROOT . '/core/' . strtolower($className) . '.php')) {
            require_once ROOT . '/core/' . strtolower($className) . '.php';
        } else if (file_exists(ROOT . '/core/include/classes/' . $className . '.class.php')) {
            require_once ROOT . '/core/include/classes/' . $className . '.class.php';
        }
    }

    private function addClasses()
    {
        /* load the less to css compiler */
    }
}
