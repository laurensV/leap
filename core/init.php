<?php
/* add all libraries */

/* Check if environment is development and display errors */
function setReporting() {
    if (DEV_ENV == true) {
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
	require_once ROOT . '/core/include/libraries/less.php/Less.php';

	global $styles, $scripts;
	
	foreach($styles as $less_file){
		if(substr($less_file, -5) == ".less") {
			$less_file = array($less_file => "/");
			$options = array('cache_dir' => ROOT . '/core/files/css', 'compress' => true);
			$css_files[] = "/core/files/css/" . Less_Cache::Get( $less_file, $options );
		} else {
			$css_files[] = $less_file;
		}
	}

	/* include the header */
	require_once(ROOT . "/core/include/header.php");
	$page_path = ROOT . "/site/pages/" . $page . ".php";
	if(file_exists($page_path)){
		require_once($page_path);
	} else {
		require_once(ROOT . "/site/pages/404.php");
	}
	require_once(ROOT . "/core/include/footer.php");
}



setReporting();

parse_arguments();
