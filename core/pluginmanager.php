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

    public function get_all_plugins($db)
    {
        $directory = new RecursiveDirectoryIterator(ROOT . '/plugins');
        $all_files = new RecursiveIteratorIterator($directory);

        $plugin_filenames = array();
        foreach ($all_files as $file) {
            if ($file->getExtension() == "plugin") {
                $path = $file->getPath();
                $pid = $file->getBasename('.plugin');
                $plugin_filenames[$pid] = $path;
                $plugin_info = $this->parse_plugin_file($file);
                /* TODO: rewrite to PDO */
                if($db){
                    $path = @mysql_real_escape_string($path);
                    $query = "INSERT IGNORE INTO plugins (pid, path, status, name, description, package, configure, source, dependencies) VALUES ('".$pid."','". $path ."', 0,'". $plugin_info['name'] ."','". $plugin_info['description'] ."','". $plugin_info['package'] ."','". $plugin_info['configure'] ."','". $plugin_info['source'] ."','". $plugin_info['dependencies'] ."') ON DUPLICATE KEY UPDATE path='" . $path . "', name='" . $plugin_info['name'] . "', description='" . $plugin_info['description'] . "', package='" . $plugin_info['package'] . "', configure='" . $plugin_info['configure'] . "',source='" . $plugin_info['source'] . "', dependencies='" . $plugin_info['dependencies'] . "'";
                    
                    mysql_query($query, $db);
                }
            }
        }
        $this->all_plugins = $plugin_filenames;
    }

    public function parse_plugin_file($file){
        $plugin_info = parse_ini_file($file, true);
                if(!isset($plugin_info['name'])) $plugin_info['name'] = "";
                if(!isset($plugin_info['description'])) $plugin_info['description'] = "";
                if(!isset($plugin_info['package'])) $plugin_info['package'] = "";
                if(!isset($plugin_info['configure'])) $plugin_info['configure'] = "";
                if(!isset($plugin_info['source'])) $plugin_info['source'] = "";
                if(!isset($plugin_info['dependencies'])) $plugin_info['dependencies'] = "";
                else $plugin_info['dependencies'] = implode(",", $plugin_info['dependencies']);
                return $plugin_info;
    }

    public function is_enabled($name) {
        return isset($this->enabled_plugins[$name]);
    }

    public function get_path($name)
    {
        if ($this->is_enabled($name)) {
            return $this->enabled_plugins[$name];
        }
        return;
    }

    public function plugins_to_load($db) {
        /* TODO: rewrite to PDO */
        $query = "SELECT pid FROM plugins WHERE status=1";

        // Perform Query
        $result = mysql_query($query, $db);

        $plugins = array();
        while ($row = mysql_fetch_assoc($result)) {
            $plugins[] = $row['pid'];
        }
        return $plugins;
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
                                    die("Error: plugin " . $dependency . " needed for plugin " . $name . " not found");
                                }
                            } else {
                                /* TODO: proper error handling */
                                die("Error: plugin " . $name . " needs plugin " . $dependency . " enabled");
                            }
                        }
                    }
                }
                chdir($path);
                if (file_exists($name . ".hooks.php")) {
                    
                    include_once $name . ".hooks.php";
                }
            }
        }

        $this->enabled_plugins = $plugins;
        /* for some reason (probably due to the unusual dynamically adding to the plugins array)
         * the variable $path can't be reused as it gives weird results.. therefore $plugin_path is used */
        foreach ($this->enabled_plugins as $name => $plugin_path) {
            if (!empty($plugin_path)) {
                $this->router->add_route_file($plugin_path . "/" . "routes.ini");
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
