<?php

$config = parse_ini_file(ROOT . "/config.ini", true);

if (file_exists(ROOT . '/config.local.ini')) {
	$config = array_replace_recursive($config, parse_ini_file(ROOT . "/config.local.ini", true));
}