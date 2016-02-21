<?php
class AdminController extends Controller
{
    public function grant_access()
    {
        return true;
    }
    public function init() {
        $links = array();
        $links['Dashboard'] = "admin/dashboard";
        if($this->plugin_manager->is_enabled("plugin_manager")){
            $links['Plugins'] = "admin/plugins";
        }
        $this->set('links', $links);
    }
}
