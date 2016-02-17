<?php
function printr($data, $exit = true)
{
    if ($data) {
        print '<pre>';
        print_r($data);
        print '</pre>';
    }
    if ($exit) {
        exit;
    }
}

function str_replace_first($search, $replace, $subject)
{
    $pos = strpos($subject, $search);
    if ($pos !== false) {
        $subject = substr_replace($subject, $replace, $pos, strlen($search));
    }
    return $subject;
}

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

function config($name, $default = null)
{
    global $config;

    return isset($config[$name]) ? $config[$name] : $default;
}
