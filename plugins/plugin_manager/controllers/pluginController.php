<?php
class pluginController extends Controller
{
	public function get_plugins() {
		$plugins = array();
		foreach (array_keys($this->plugin_manager->all_plugins) as $plugin) {
			$enabled = $this->plugin_manager->is_enabled($plugin);
			$plugins[$plugin] = $enabled;
		}
		$this->set('plugins', $plugins);
	}

	public function enable_plugin() {
		$plugin = arg(4);
        $query = "UPDATE plugins SET enabled=1 WHERE name='$plugin'";
        // Perform Query
        $result = $this->model->query($query);
		if($result) {
			$message = "PLugin " . $plugin . " successfully enabled.";
		} else {
			print $this->model->getError();
			$message = "Could not enable plugin ". $plugin .".";
		}
		$this->set('result_message', $message);
	}

	public function disable_plugin() {
		$plugin = arg(4);
        $query = "UPDATE plugins SET enabled=0 WHERE name='$plugin'";
        // Perform Query
        $result = $this->model->query($query);
  
		if($result) {
			$message = "PLugin " . $plugin . " successfully disabled.";
		} else {
			$message = "Could not disable plugin ". $plugin . ".";
		}
		$this->set('result_message', $message);
	}
}
?>