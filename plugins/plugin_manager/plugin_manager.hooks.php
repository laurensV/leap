<?php
function plugin_manager_admin_links(&$links){
    $links['plugins'] = array("link" => "/admin/plugins", "name" => "Plugins", "description" => "Manage all your plugins");
}