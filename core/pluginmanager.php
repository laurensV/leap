<?php
class PluginManager
{
    private $router;
    private $hooks;
    public $all_plugins;
    public $enabled_plugins;
    public function __construct($router, $hooks)
    {
        $this->router = $router;
        $this->hooks  = $hooks;
    }

    public function get_all_plugins()
    {
        $directory = new RecursiveDirectoryIterator(ROOT . '/plugins');
        $all_files = new RecursiveIteratorIterator($directory);

        $plugin_filenames = array();
        foreach ($all_files as $file) {
            if ($file->getExtension() == "plugin") {
                $plugin_filenames[$file->getBasename('.plugin')] = $file->getPath();
            }
        }
        $this->all_plugins = $plugin_filenames;
    }

    public function get_sublist_plugins($plugin_names)
    {
        $sublist_plugins = array();
        foreach ($plugin_names as $name) {
            $sublist_plugins[$name] = $this->all_plugins[$name];
        }
        return $sublist_plugins;
    }

    public function load_plugins($plugins, $auto_enable_dependencies = true)
    {
        /* As dependencies are getting dynamically added to the plugins array in this foreach loop,
         * we need to inform php that he might not be executing the last element of the array */
        $plugins[] = "";
        foreach ($plugins as $name => &$path) {
            if (!empty($path)) {
                $plugin_info[$name] = parse_ini_file($path . "/" . $name . ".plugin", true);
                if (isset($plugin_info[$name]['dependencies'])) {
                    foreach ($plugin_info[$name]['dependencies'] as $dependency) {
                        if (!isset($plugins[$dependency])) {
                            if ($auto_enable_dependencies) {
                                if (isset($this->all_plugins[$dependency])) {
                                    $plugins[$dependency] = $this->all_plugins[$dependency];
                                    /* As dependencies are getting dynamically added to the plugins array in this foreach loop,
                                     * we need to inform php that he might not be executing the last element of the array */
                                    $plugins[] = "";
                                } else {
                                    return "Error: plugin " . $dependency . " needed for plugin " . $name . " not found";
                                }
                            } else {
                                /* TODO: proper error handling */
                                return "Error: plugin " . $name . " needs plugin " . $dependency . " enabled";
                            }
                        }
                    }
                }
                if (file_exists($path . "/" . $name . ".hooks.php")) {
                    include_once $path . "/" . $name . ".hooks.php";
                }

                $this->router->add_route_file($path . "/" . "routes.ini");
            }
        }

        $this->enabled_plugins = $plugins;
        /* for some reason (probably due to the unusual dynamically adding to the plugins array)
         * the variable $path can't be reused as it gives weird results.. therefore $plugin_path is used */
        foreach ($this->enabled_plugins as $name => $plugin_path) {
            if (!empty($plugin_path)) {
                foreach ($this->hooks->getHooks() as $hook) {
                    $function = $name . "_" . $hook;
                    if (function_exists($function)) {
                        $this->hooks->add($hook, $function);
                    }
                }
            }
        }
    }
}
