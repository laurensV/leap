<?php
namespace Leap\Hooks\Alias {
    function hook_prerouteUrl(&$url)
    {
        $alias = getAliases();

        if (isset($alias[$url])) {
            $url = $alias[$url];
        }
    }

    function getAliases()
    {
        if (file_exists("aliases.ini")) {
            return parse_ini_file("aliases.ini");
        }
        return array();
    }
}

