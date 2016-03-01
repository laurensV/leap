<?php
require $this->plugin_manager->get_path("admin") . "/controllers/AdminController.php";
class pluginController extends AdminController
{
    public function get_plugins()
    {
        $plugins = array();

        foreach (array_keys($this->plugin_manager->all_plugins) as $plugin) {
            $enabled          = $this->plugin_manager->is_enabled($plugin);
            $plugins[$plugin]['status'] = $enabled;
            if ($this->model->has_connection()) {
                $query   = "SELECT * FROM plugins WHERE pid='$plugin'";
                $results = $this->model->query($query);
                if($results){
                	printr($results);
                }
            }
        }

        $this->set('plugins', $plugins);
    }

    public function enable_plugin()
    {
        $plugin = arg(4);
        $query  = "UPDATE plugins SET status=1 WHERE pid='$plugin'";
        // Perform Query
        $result = $this->model->query($query);
        if ($result) {
            $message = "PLugin " . $plugin . " successfully enabled.";
        } else {
            $message = "Could not enable plugin " . $plugin . ".<br>";
            $message .= $this->model->getError();

        }
        $this->set('result_message', $message);
    }

    public function disable_plugin()
    {
        $plugin = arg('plugin_pid');
        if ($plugin) {
            $query = "UPDATE plugins SET status=0 WHERE pid='$plugin'";
            // Perform Query
            $result = $this->model->query($query);

            if ($result) {
                $message = "PLugin " . $plugin . " successfully disabled.";
            } else {
                $message = "Could not disable plugin " . $plugin . ".<br>";
                $message .= $this->model->getError();
            }
        } else {
            $message = "No plugin specified";
        }
        $this->set('result_message', $message);
    }
}
