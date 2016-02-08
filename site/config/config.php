<?php

/* development environment */
define ('DEV_ENV', true);

/* MySQL info */ 
define('DB_NAME', 'yourdatabasename');
define('DB_USER', 'yourusername');
define('DB_PASSWORD', 'yourpassword');
define('DB_HOST', 'localhost');

/* Site info */
define('SITE_NAME', "Laurens Verspeek");
define('SITE_OWNER', "Laurens Verspeek");
define('SITE_URL', 'http://laurensverspeek.nl');
define('SITE_MAIL', 'laurens_verspeek@hotmail.com');

/* import less/css files */
$styles[] = ROOT . "/site/less/header.less";
$styles[] = ROOT . "/site/less/content.less";
$styles[] = "https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css";

/* import javascript files */
$scripts[] = "//code.jquery.com/jquery-1.12.0.min.js";
$scripts[] = "https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js";