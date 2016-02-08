<?php
define('ROOT', dirname(dirname(__FILE__)));
  
$args = $_GET['url'];

require_once(ROOT . '/site/config/config.php');
require_once(ROOT . '/core/include/helpers.php');
require_once(ROOT . '/core/init.php');
