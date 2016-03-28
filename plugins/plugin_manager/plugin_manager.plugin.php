<?php
namespace Leap\Hooks\Pluginmanager {
    function hook_adminLinks(&$links)
    {
        $links['plugins'] = array("link" => "admin/plugins", "name" => "Plugins", "description" => "Manage all your plugins");
    }
}
