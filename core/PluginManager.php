<?php
namespace Leap\Core;

class PluginManager
{
    public  $all_plugins;
    public  $enabled_plugins;
    private $pdo;

    public function __construct(?PdoPlus $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllPlugins()
    {
        $directory = new \RecursiveDirectoryIterator(ROOT . 'plugins');
        $all_files = new \RecursiveIteratorIterator($directory);

        if (is_object($this->pdo)) {
            $stmt = $this->pdo->prepare("INSERT INTO plugins (pid, path, status, name, description, package, configure, source, dependencies)VALUES (:pid,:path,0,:name,:description,:package,:configure,:source,:dependencies) ON DUPLICATE KEY UPDATE path=:path, name=:name, description=:description, package=:package, configure=:configure, source=:source, dependencies=:dependencies");
        }

        foreach ($all_files as $file) {
            $ext = $file->getExtension();
            if ($ext == "info" || $ext == "disabled") {
                $path                = $file->getPath();
                $pid                 = $file->getBasename('.' . $ext);
                $plugin_info         = $this->parsePluginFile($file);
                $plugin_info['path'] = $path . "/";

                if ($ext == "disabled") {
                    $plugin_info['status'] = 0;
                } else {
                    $plugin_info['status'] = 1;
                }

                $this->all_plugins[$pid] = $plugin_info;

                if (isset($stmt)) {
                    if (is_array($plugin_info['dependencies'])) {
                        $dependencies = implode(",", $plugin_info['dependencies']);
                    } else {
                        $dependencies = $plugin_info['dependencies'];
                    }
                    $data = ['pid' => $pid, 'path' => $plugin_info['path'], 'name' => $plugin_info['name'], 'description' => $plugin_info['description'], 'package' => $plugin_info['package'], 'configure' => $plugin_info['configure'], 'source' => $plugin_info['source'], 'dependencies' => $dependencies];
                    $stmt->execute($data);
                }
            }
        }
    }

    public function parsePluginFile($file)
    {
        $plugin_info = parse_ini_file($file, true);
        if (!isset($plugin_info['name'])) {
            $plugin_info['name'] = "";
        }

        if (!isset($plugin_info['description'])) {
            $plugin_info['description'] = "";
        }

        if (!isset($plugin_info['package'])) {
            $plugin_info['package'] = "";
        }

        if (!isset($plugin_info['configure'])) {
            $plugin_info['configure'] = "";
        }

        if (!isset($plugin_info['source'])) {
            $plugin_info['source'] = "";
        }

        if (!isset($plugin_info['dependencies'])) {
            $plugin_info['dependencies'] = "";
        }

        return $plugin_info;
    }

    public function isEnabled($pid)
    {
        return in_array($pid, $this->enabled_plugins);
    }

    public function getPath($pid)
    {
        return $this->all_plugins[$pid]['path'];
    }

    public function getEnabledPlugins()
    {
        if (is_object($this->pdo)) {
            $plugins       = [];
            $plugins_query = $this->pdo->query("SELECT pid FROM plugins WHERE status=1")->fetchAll(\PDO::FETCH_COLUMN);
            if (is_array($plugins_query)) {
                foreach ($plugins_query as $pid) {
                    if ($this->all_plugins[$pid]['status'] != 0) {
                        $plugins[] = $pid;
                    }
                }
            }
        } else {
            /* site is run without database, so use custom function to load plugins */
            $plugins = $this->getEnabledPluginsNoDB();
        }
        return $plugins;
    }

    private function getEnabledPluginsNoDB()
    {
        $plugins = [];
        if (is_array($this->all_plugins)) {
            foreach ($this->all_plugins as $pid => $plugin) {
                if ($plugin['status'] == 1) {
                    $plugins[] = $pid;
                }
            }
        }
        return $plugins;
    }

    /* function that loads a list of plugins */
    public function loadPlugins($plugins)
    {
        $this->enabled_plugins = $plugins;
        foreach ($plugins as $pid) {
            if (!empty($this->all_plugins[$pid]['path'])) {
                if (!empty($this->all_plugins[$pid]['dependencies'])) {
                    foreach ($this->all_plugins[$pid]['dependencies'] as $dependency) {
                        if (!$this->isEnabled($dependency)) {
                            /* TODO: proper error handling */
                            die("Error: plugin " . $pid . " needs plugin " . $dependency . " enabled");
                        }
                    }
                }

                chdir($this->all_plugins[$pid]['path']);
                if (file_exists($pid . ".plugin.php")) {
                    include $pid . ".plugin.php";
                }
                global $autoloader;
                $autoloader->setPsr4("Leap\\Plugins\\" . ucfirst($pid) . "\\", $this->all_plugins[$pid]['path']);
            }
        }
    }
}
