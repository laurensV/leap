<?php
/* add all libraries */

/* Check if environment is development and display errors */
function setReporting() {
	global $config;
    if ($config['general']['dev_env'] == true) {
        error_reporting(E_ALL);
        ini_set('display_errors','On');
    } else {
        error_reporting(E_ALL);
        ini_set('display_errors','Off');
        ini_set('log_errors', 'On');
        ini_set('error_log', ROOT . '/tmp/logs/error.log');
    }
}
/* Autoload any classes that are required */
function __autoload($className) {
    if (file_exists(ROOT . '/core/include/classes/' . strtolower($className) . '.class.php')) {
        require_once(ROOT . '/core/include/classes/' . strtolower($className) . '.class.php');
    } else {
        /* Error Generation Code Here */
    }
}

function parse_arguments($args = "") {
	if(empty($args)) global $args;
 	
    $args_parts = explode("/",$args);
    if(empty($args_parts[0])){
    	$args_parts = array();
    }

    $page = 'index';
    $action = 'view';
    $query_string = "";
    
	switch (sizeof($args_parts)) {
	    case 0:
	    	break;
	    case 1:
	    	$page = $args_parts[0];
	        break;
	    case 2:
	    	$page = $args_parts[0];
	    	$query_string = $args_parts[1];
	        break;
	    case 3:
	       	$page = $args_parts[0];
	       	$action = $args_parts[1];
	       	$$query_string = $args_parts[2];
	        break;
	    default:
	    	$page = '404';
	}
	/* get all javascript and css files to be included */
	require_once(ROOT . "/site/include.php");

	/* load the less to css compiler */
	require_once ROOT . '/core/include/libraries/less.php/Less.php';
	
	foreach($styles as $less_file){
		if(substr($less_file, -5) == ".less") {
			$less_file = array($less_file => "/");
			$options = array('cache_dir' => ROOT . '/core/files/css', 'compress' => true);
			$css_files[] = "/core/files/css/" . Less_Cache::Get( $less_file, $options );
		} else {
			/* file is not a less file, so no need to compile to css */
			$css_files[] = $less_file;
		}
	}

	/* include the header */
	require_once(ROOT . "/core/include/header.php");
	/* include the content */
	$page_path = ROOT . "/site/pages/" . $page . ".php";
	if(file_exists($page_path)){
		require_once($page_path);
	} else {
		require_once(ROOT . "/site/pages/404.php");
	}
	/* include the footer */
	require_once(ROOT . "/core/include/footer.php");
}



setReporting();

parse_arguments();
