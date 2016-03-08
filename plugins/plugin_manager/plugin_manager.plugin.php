<?php
namespace hooks\plugin_manager {
    function adminLinks(&$links)
    {
        $links['plugins'] = array("link" => "/admin/plugins", "name" => "Plugins", "description" => "Manage all your plugins");
    }
}
