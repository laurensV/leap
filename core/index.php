<?php
define('ROOT', dirname(dirname(__FILE__)));
  
$args = isset($_GET['args']) ? $_GET['args'] : "";

require_once(ROOT. '/core/config.php');
require_once(ROOT . '/core/include/helpers.php');
require_once(ROOT . '/core/init.php');
