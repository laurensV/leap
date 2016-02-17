<?php
class Application
{
    private $router;
    private $controller;
    private $model;
    private $url;
    private $hooks;
    private $plugin_manager;

    /**
     * "Start" the application:
     * Analyze the URL elements and calls the according controller/method or the fallback
     */
    public function __construct()
    {
        $this->setReporting();
        $this->add_classes();
        spl_autoload_register(array($this, 'autoload_classes'));
        $this->url            = isset($_GET['args']) ? $_GET['args'] : "";
        $this->router         = new Router();
        $this->define_hooks();
        $this->plugin_manager = new PluginManager($this->router, $this->hooks);
        $this->router->set_plugin_manager($this->plugin_manager);
        $this->bootstrap();
    }


    private function define_hooks()
    {
        $hook_names  = array("parse_stylesheet");
        $this->hooks = new Hooks($hook_names);
    }

    private function bootstrap()
    {
        $this->plugin_manager->get_all_plugins();
        $plugins = $this->plugin_manager->get_sublist_plugins(array("admin", "less"));
        $this->plugin_manager->load_plugins($plugins);

        $this->router->add_route_file(ROOT . "/site/routes.ini");
        $this->router->route_url($this->url);
        //printr($this->router);
        $this->controller = new $this->router->controller($this->router->model, $this->router->template, $this->router->page, $this->hooks, $this->plugin_manager);

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
