<?php
require $this->plugin_manager->get_path("admin") . "/controllers/AdminController.php";
class pluginController extends AdminController
{
    public function get_plugins()
    {
        $plugins = array();
        if($this->model->has_connection()){
            $stmt = $this->model->query("SELECT * FROM plugins");
            $plugins = $stmt->fetchAll();
        }
        $this->set('plugins', $plugins);
    }

    public function enable_plugin()
    {
        $pid = arg('plugin_pid');
        if ($pid) {
            $sql  = "UPDATE plugins SET status=1 WHERE pid= ? ";
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
        $this->set('result_message', $message);
    }

    public function disable_plugin()
    {
        $plugin = arg('plugin_pid');
        if ($plugin) {
            $sql  = "UPDATE plugins SET status=0 WHERE pid= ? ";
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
        $this->set('result_message', $message);
    }
}
