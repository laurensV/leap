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
                if(!empty($plugin_info['dependencies'])) {
                    $plugin_info['dependencies'] = implode(",", $plugin_info['dependencies']);
                }
                $plugins[]             = $plugin_info;
            }
        }
        $this->set('plugins', $plugins);
    }

    public function enablePlugin()
    {
        $pid = arg('plugin_pid');
        if ($this->model->hasConnection()) {
            if ($pid) {
                $sql = "UPDATE plugins SET status=1 WHERE pid= ? ";
                // Perform Query
                $stmt = $this->model->run($sql, [$pid]);
                if ($stmt->rowCount()) {
                    $message = "Plugin " . $pid . " successfully enabled.";
                } else {
                    $message = "Could not enable plugin " . $pid . ".<br>";
                }
            } else {
                $message = "No plugin specified";
            }
        } else {
            /* TODO: disable plugins by adding a file .disabled to the plugin folder */
            $message = "Can't enable plugins without db, please do this manually in the code";
        }

        $this->set('result_message', $message);
    }

    public function disablePlugin()
    {
        $plugin = arg('plugin_pid');
        if ($this->model->hasConnection()) {
            if ($plugin) {
                $sql = "UPDATE plugins SET status=0 WHERE pid= ? ";
                // Perform Query
                $stmt = $this->model->run($sql, [$plugin]);
                if ($stmt->rowCount()) {
                    $message = "Plugin " . $plugin . " successfully disabled.";
                } else {
                    $message = "Could not disable plugin " . $plugin . ".<br>";
                }
            } else {
                $message = "No plugin specified";
            }
        } else {
            /* TODO: disable plugins by adding a file .disabled to the plugin folder */
            $message = "Can't disable plugins without db, please do this manually in the code";
        }

        $this->set('result_message', $message);
    }
}
