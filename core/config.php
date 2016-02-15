<?php
$config = parse_ini_file(ROOT . "/config.ini", true);

/* check for local settings */
if (file_exists(ROOT . '/config.local.ini')) {
    $config = array_replace_recursive($config, parse_ini_file(ROOT . "/config.local.ini", true));
}

if (isset($config['application']['base_url'])) {
    define('URL', $config['application']['base_url']);
} else {
    define('URL', base_url());
}

function base_url()
{
    if (isset($_SERVER['HTTPS'])) {
        $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
    } else {
        $protocol = 'http';
    }
    return $protocol . "://" . $_SERVER['HTTP_HOST'];
}
