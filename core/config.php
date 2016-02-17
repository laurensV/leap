<?php
$config = parse_ini_file(ROOT . "/config.ini", true);

/* check for local settings */
if (file_exists(ROOT . '/config.local.ini')) {
    $config = array_replace_recursive($config, parse_ini_file(ROOT . "/config.local.ini", true));
}

define('BASE_URL', call_user_func(function () {
    $port = ":" . $_SERVER['SERVER_PORT'];
    $http = "http";

    if ($port == ":80") {
        $port = "";
    }

    if (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
        $http = "https";
    }
    $sub_dir = dirname(dirname($_SERVER['PHP_SELF']));
    if ($sub_dir == "/" || $sub_dir == "\\") {
        $sub_dir = "";
    }
    return $http . "://" . $_SERVER['SERVER_NAME'] . $port . $sub_dir;
}));

$args_raw = isset($_GET['args']) ? $_GET['args'] : "";
function arg($id = null)
{
    global $args_raw;
    $args = explode("/", $args_raw);
    if (!isset($id)) {
        return $args;
    } else {
        $id--;
        if (isset($args[$id])) {
            return $args[$id];
        }
    }
}
