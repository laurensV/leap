<?php
define('ROOT', call_user_func(function () {
    $root = str_replace("\\", "/", dirname(dirname(__FILE__)));
    $root .= (substr($root, -1) == '/' ? '' : '/');
    return $root;
}));

$config = parse_ini_file(ROOT . "config.ini", true);
if(!$config) {
    /* TODO: fatal error handling */
} else {
    /* check for local settings */
    if (file_exists(ROOT . 'config.local.ini')) {
        $config = array_replace_recursive($config, parse_ini_file(ROOT . "config.local.ini", true));
    }
    if (!isset($config['database']['db_type'])) {
         $config['database']['db_type'] = "";
    }
    if (!isset($config['database']['plugins_from_db'])) {
        $config['general']['plugins_from_db'] = true;
    }
    define('BASE_URL', call_user_func(function () {
        $sub_dir = str_replace("\\", "/", dirname($_SERVER['PHP_SELF']));
        $sub_dir .= (substr($sub_dir, -1) == '/' ? '' : '/');
        return $sub_dir;
    }));

    define('URL', call_user_func(function () {
        $port = ":" . $_SERVER['SERVER_PORT'];
        $http = "http";

        if ($port == ":80") {
            $port = "";
        }

        if (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
            $http = "https";
        }
        return $http . "://" . $_SERVER['SERVER_NAME'] . $port . BASE_URL;
    }));

    define('LIBRARIES', ROOT . "libraries/");

    $args_raw = isset($_GET['args']) ? $_GET['args'] : "";
}
