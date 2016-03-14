<?php
define('ROOT', call_user_func(function () {
    $root = str_replace("\\", "/", dirname(dirname(__FILE__)));
    $root .= (substr($root, -1) == '/' ? '' : '/');
    return $root;
}));
require_once ROOT . 'core/config.php';
require_once ROOT . 'core/include/helpers.php';
require_once ROOT . 'core/application.php';

$app = new Application();
