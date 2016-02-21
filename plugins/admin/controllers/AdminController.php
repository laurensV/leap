<?php
class AdminController extends Controller
{
    public function grant_access()
    {
        return true;
    }
    public function init() {
        $links = array();
        $links[] = array("link" => "admin/dashboard", "name" => "Dashboard", "description" => "Here comes the description");
        if($this->plugin_manager->is_enabled("plugin_manager")){
            $links[] = array("link" => "admin/plugins", "name" => "Plugins", "description" => "Here comes the description");

        }
        $links[] = array("link" => "#", "name" => "Test", "description" => "Here comes the description");
        $links[] = array("link" => "#", "name" => "Test", "description" => "Here comes the description");
        $links[] = array("link" => "#", "name" => "Test", "description" => "Here comes the description");
        $links[] = array("link" => "#", "name" => "Test", "description" => "Here comes the description");
        $links[] = array("link" => "#", "name" => "Test", "description" => "Here comes the description");


        $this->set('links', $links);
    }
}
