<?php
require $this->plugin_manager->getPath("admin") . "/controllers/AdminController.php";
class pluginController extends AdminController
{
    public function getPlugins()
    {
        $plugins = array();
        if ($this->model->hasConnection()) {
            $stmt    = $this->model->query("SELECT * FROM plugins");
            $plugins = $stmt->fetchAll();
        } else {
            foreach ($this->plugin_manager->all_plugins as $plugin => $path) {
                $plugin_info           = $this->plugin_manager->all_plugins[$plugin];
                $enabled               = $this->plugin_manager->isEnabled($plugin);
                $plugin_info['pid']    = $plugin;
                $plugin_info['status'] = $enabled;
                if (!empty($plugin_info['dependencies'])) {
                    $plugin_info['dependencies'] = implode(",", $plugin_info['dependencies']);
                }
                $plugins[] = $plugin_info;
            }
        }
        $this->set('plugins', $plugins);
    }

    public function enablePlugin($plugin = null, $checkDependencies = true)
    {
        if (empty($plugin)) {
            $plugin = arg('plugin_pid');
        }
        if ($plugin) {
            if ($checkDependencies) {
                $dependencies = $this->getDependencies($plugin);
                /* check if there is atleast 1 dependency (not counting yourself) */
                if (isset($dependencies[1])) {
                    $this->set('dependencies', $dependencies);
                    return;
                }
            }
            if ($this->model->hasConnection()) {
                $sql = "UPDATE plugins SET status=1 WHERE pid= ? ";
                // Perform Query
                $stmt = $this->model->run($sql, [$plugin]);
                if ($stmt->rowCount()) {
                    $message = "Plugin " . $plugin . " successfully enabled.";
                } else {
                    $message = "Could not enable plugin " . $plugin . ".<br>";
                }

            } else {
                /* TODO: disable plugins by adding a file .disabled to the plugin folder */
                if (isset($this->plugin_manager->all_plugins[$plugin])) {
                    $path = $this->plugin_manager->all_plugins[$plugin]['path'];
                    if (rename($path . "/" . $plugin . ".disabled", $path . "/" . $plugin . ".info")) {
                        $message = "Plugin " . $plugin . " successfully enabled.";
                    } else {
                        $message = "No database connection and plugin folder isn't writable, please enable plugin manually by changing the .disabled file to .info";
                    }
                } else {
                    $message = "Plugin " . $plugin . " not found.";
                }
            }
        } else {
            $message = "No plugin specified";
        }

        $this->set('result_message', $message);
    }

    public function disablePlugin($plugin = null, $checkDependents = true)
    {
        if (empty($plugin)) {
            $plugin = arg('plugin_pid');
        }
        if ($plugin) {
            if ($checkDependents) {
                $dependents = $this->getDependents($plugin);
                /* check if there is atleast 1 dependent plugin (not counting yourself) */
                if (isset($dependents[1])) {
                    $this->set('dependent_plugins', $dependents);
                    return;
                }
            }
            if ($this->model->hasConnection()) {
                $sql = "UPDATE plugins SET status=0 WHERE pid= ? ";
                // Perform Query
                $stmt = $this->model->run($sql, [$plugin]);
                if ($stmt->rowCount()) {
                    $message = "Plugin " . $plugin . " successfully disabled.";
                } else {
                    $message = "Could not disable plugin " . $plugin . ".<br>";
                }

            } else {
                if (isset($this->plugin_manager->all_plugins[$plugin])) {
                    $path = $this->plugin_manager->all_plugins[$plugin]['path'];
                    if (rename($path . "/" . $plugin . ".info", $path . "/" . $plugin . ".disabled")) {
                        $message = "Plugin " . $plugin . " successfully disabled.";
                    } else {
                        $message = "No database connection and plugin folder isn't writable, please disable plugin manually by changing the .info file to .disabled";
                    }
                } else {
                    $message = "Plugin " . $plugin . " not found.";
                }
            }
        } else {
            $message = "No plugin specified";
        }

        $this->set('result_message', $message);
    }

    public function multiplePlugins()
    {
        if (isset($_POST['action']) && $_POST['action'] == "Disable") {
            $plugins = unserialize($_POST['plugins']);
            foreach ($plugins as $plugin) {
                $this->disablePlugin($plugin, false);
            }
        } else if (isset($_POST['action']) && $_POST['action'] == "Enable") {
            $plugins = unserialize($_POST['plugins']);
            foreach ($plugins as $plugin) {
                $this->enablePlugin($plugin, false);
            }
        } else {
            header("Location: " . BASE_URL . "/admin/plugins");
        }
    }

    /* recursive dependent plugins checker */
    private function getDependents($plugin, $current_dependencies = null)
    {
        $dependent_plugins = [];
        if (!isset($current_dependencies)) {
            $dependent_plugins[] = $plugin;
        }

        foreach ($this->plugin_manager->enabled_plugins as $enabled_plugin) {
            if (!empty($this->plugin_manager->all_plugins[$enabled_plugin]['dependencies'])) {
                if (in_array($plugin, $this->plugin_manager->all_plugins[$enabled_plugin]['dependencies'])) {
                    if (!isset($current_dependencies) || !in_array($enabled_plugin, $current_dependencies)) {
                        $dependent_plugins[] = $enabled_plugin;
                        $dependent_plugins   = $this->getDependents($enabled_plugin, $dependent_plugins);
                    }
                }
            }
        }
        if (isset($current_dependencies)) {
            return array_merge($current_dependencies, $dependent_plugins);
        } else {
            return array_unique($dependent_plugins);
        }
    }

    /* recursive dependencies checker */
    private function getDependencies($plugin, $current_dependencies = null)
    {
        $dependent_plugins = [];
        if (!isset($current_dependencies)) {
            $dependent_plugins[] = $plugin;
        }

        if (!empty($this->plugin_manager->all_plugins[$plugin]['dependencies'])) {
            foreach ($this->plugin_manager->all_plugins[$plugin]['dependencies'] as $dependency) {
                if (!in_array($dependency, $this->plugin_manager->enabled_plugins)) {
                    if (!isset($current_dependencies) || !in_array($dependency, $current_dependencies)) {
                        $dependent_plugins[] = $dependency;
                        $dependent_plugins   = $this->getDependents($dependency, $dependent_plugins);
                    }
                }
            }
        }
        if (isset($current_dependencies)) {
            return array_merge($current_dependencies, $dependent_plugins);
        } else {
            return array_unique($dependent_plugins);
        }
    }
}
