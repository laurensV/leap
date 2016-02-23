<?php
class Application
{
    private $router;
    private $controller;
    private $model;
    private $url;
    private $hooks;
    private $plugin_manager;
    private $db;

    /**
     * "Start" the application:
     * Analyze the URL elements and calls the according controller/method or the fallback
     */
    public function __construct()
    {
        $this->setReporting();
        $this->add_classes();
        spl_autoload_register(array($this, 'autoload_classes'));
        $this->url    = $this->get_url();
        $this->router = new Router();
        $this->define_hooks();
        $this->plugin_manager = new PluginManager($this->router, $this->hooks);
        $this->router->set_plugin_manager($this->plugin_manager);
        $this->bootstrap();
    }

    private function get_url()
    {
        return rtrim(isset($_GET['args']) ? $_GET['args'] : "", "/");
    }

    private function define_hooks()
    {
        /* TODO: automatically find hooks */
        $hook_names  = array("parse_stylesheet", "preroute_url", "admin_links");
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

    public function connect_with_config()
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

    private function custom_plugins_to_load()
    {
        return array("admin", "less", "alias", "plugin_manager");
    }

    private function bootstrap()
    {
        $db_result                = $this->connect_with_config();
        $auto_enable_dependencies = false;
        if ($db_result) {
            if ($db_result == -1) {
                /* site is run without database, so use custom function to load plugins */
                $plugins_to_enable        = $this->custom_plugins_to_load();
                $auto_enable_dependencies = true;
            } else {
                /* we have a database connection */
                $plugins_to_enable = $this->plugin_manager->plugins_to_load($this->db);
            }
        } else {
            printr("database error");
        }
        $this->plugin_manager->get_all_plugins($this->db);
        $plugins = $this->plugin_manager->get_sublist_plugins($plugins_to_enable);

        $this->plugin_manager->load_plugins($plugins, $auto_enable_dependencies);
        /* Plugins are loaded, so from now on we can fire hooks */
        $this->router->add_route_file(ROOT . "/core/routes.ini");
        $this->router->add_route_file(ROOT . "/site/routes.ini");
        $this->hooks->fire("preroute_url", array(&$this->url));
        for ($i = 0; $i < 2; $i++) {
            if ($i == 1) {
                if ($this->controller->result == -1) {
                    header($_SERVER["SERVER_PROTOCOL"]." 403 Forbidden");
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

            $this->router->route_url($this->url);
            $this->controller = new $this->router->controller($this->router->model, $this->router->template, $this->router->page, $this->hooks, $this->plugin_manager, $this->db, $this->router->stylesheets_route, $this->router->scripts_route);
        }
        if (method_exists($this->controller, $this->router->action)) {
            $this->controller->{$this->router->action}($this->router->params);
        } else {
            /* when the second argument is not an action, it is probably a parameter */
            $this->router->params = $this->router->action . "/" . $this->router->params;
            $this->controller->default_action($this->router->params);
        }

        $this->controller->render();
    }

    /**
     * Check if environment is development and display errors
     */
    private function setReporting()
    {
        if (config('general')['dev_env'] == true) {
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', 'Off');
            ini_set('log_errors', 'On');
            ini_set('error_log', ROOT . '/tmp/logs/error.log');
        }
    }

    /**
     * Autoload any classes that are required
     */
    public function autoload_classes($className)
    {
        if (file_exists(ROOT . '/core/' . strtolower($className) . '.php')) {
            require_once ROOT . '/core/' . strtolower($className) . '.php';
        } else if (file_exists(ROOT . '/core/include/classes/' . $className . '.class.php')) {
            require_once ROOT . '/core/include/classes/' . $className . '.class.php';
        }
    }

    private function add_classes()
    {
        /* load the less to css compiler */
        require_once ROOT . '/core/include/libraries/less.php/Less.php';
    }
}
