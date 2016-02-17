<?php
class Application
{
    private $router;
    private $controller;
    private $model;
    private $url;
    private $hooks;

    /**
     * "Start" the application:
     * Analyze the URL elements and calls the according controller/method or the fallback
     */
    public function __construct()
    {
        $this->setReporting();
        $this->add_classes();
        spl_autoload_register(array($this, 'autoload_classes'));
        $this->url    = isset($_GET['args']) ? $_GET['args'] : "";
        $this->router = new Router();
        $this->bootstrap();
    }

    private function get_plugins()
    {
        $directory = new RecursiveDirectoryIterator(ROOT . '/plugins');
        $all_files = new RecursiveIteratorIterator($directory);

        $plugin_filenames = array();
        foreach ($all_files as $file) {
            if ($file->getExtension() == "plugin") {
                $plugin_filenames[$file->getBasename('.plugin')] = $file->getPath();
            }
        }
        return $plugin_filenames;
    }

    private function load_plugins($plugins)
    {
        foreach ($plugins as $name => $path) {
            if (file_exists($path . "/" . $name . ".hooks.php")) {
                include_once $path . "/" . $name . ".hooks.php";
            }

            $this->router->add_route_file($path . "/" . "routes.ini");
        }
        foreach ($plugins as $name => $path) {
            foreach ($this->hooks->getHooks() as $hook) {
                $function = $name . "_" . $hook;
                if (function_exists($function)) {
                    $this->hooks->add($hook, $function);
                }
            }
        }
    }

    private function define_hooks()
    {
        $hook_names  = array("parse_stylesheet");
        $this->hooks = new Hooks($hook_names);
    }

    private function bootstrap()
    {
        $this->define_hooks();
        $plugins = $this->get_plugins();
        $this->load_plugins($plugins);

        $this->router->add_route_file(ROOT . "/site/routes.ini");
        $this->router->route_url($this->url);

        $this->controller = new $this->router->controller($this->router->model, $this->router->template, $this->router->page, $this->hooks, $plugins);

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
        global $config;
        if ($config['general']['dev_env'] == true) {
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
