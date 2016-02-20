<?php
function alias_preroute_url(&$url)
{

    $alias = get_aliases();

    if (isset($alias[$url])) {
        $url = $alias[$url];
    }
}

function get_aliases()
{
    if (file_exists("aliases.ini")) {
        return parse_ini_file("aliases.ini");
    }
    return array();
}
