<?php
define('ROOT', dirname(dirname(__FILE__)));
require_once(ROOT. '/core/config.php');
require_once(ROOT . '/core/include/helpers.php');
require_once(ROOT . '/core/application.php');
require_once(ROOT . '/core/controller.php');
require_once(ROOT . '/core/model.php');

$app = new Application();