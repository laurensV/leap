<?php
return [
    'environment' => 'development',
    'dic'         => 'core/dependencies.php',
    'middleware'  => 'app/middleware/middlewares.php',

    'routes'      => [
        'app/app.routes.php'
    ],

    'paths' => [
        'libraries' => 'vendor/',
        'files'     => 'files/',
    ],

    'application' => [
        'name'  => 'Leap - PHP Framework',
        'owner' => 'Laurens Verspeek',
        'email' => 'laurens_verspeek@hotmail.com',
        'url'   => 'http://laurensverspeek.nl',
    ],

    'database' => [
        'type'     => 'none',
        'database' => 'leap',
        'username' => 'root',
        'password' => '',
        'host'     => 'localhost',
    ],

];
