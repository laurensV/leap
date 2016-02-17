<?php
$config = parse_ini_file(ROOT . "/config.ini", true);

/* check for local settings */
if (file_exists(ROOT . '/config.local.ini')) {
    $config = array_replace_recursive($config, parse_ini_file(ROOT . "/config.local.ini", true));
}

// if (isset($config['application']['base_url'])) {
//     define('BASE_URL', $config['application']['base_url']);
// } else {
       define('BASE_URL', base_url());
// }

function base_url() {
    $port = ":" . $_SERVER['SERVER_PORT'];
    $http = "http";
    
    if($port == ":80"){
      $port = "";  
    }
    
    if(!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on"){
       $http = "https";
    }
    $sub_dir = dirname(dirname($_SERVER['PHP_SELF']));
    if($sub_dir == "/" || $sub_dir == "\\") {
      $sub_dir = "";
    }
    $http."://".$_SERVER['SERVER_NAME']. $port . $sub_dir;      
}
