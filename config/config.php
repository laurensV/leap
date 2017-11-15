<?php
return [
    // environment variable (development, production etc.)
    'environment' => 'development',

    // location of file where all dependencies between classes are defined and DI Container is created
    'dic'         => 'dependencies.php',

    // location of file where array with middleware is defined
    'middleware'  => 'App/Middleware/middlewares.php',

    // location of route files
    'routes'      => [
        'App/app.routes.php'
    ],

    // Some paths used for global helpers
    'paths' => [
        'libraries' => 'vendor/',
        'files'     => 'files/',
    ],

    // Application specific configuration
    'application' => [
        'name'  => 'Leap - PHP Framework',
        'owner' => 'Laurens Verspeek',
        'email' => 'laurens_verspeek@hotmail.com',
        'url'   => 'http://laurensverspeek.nl',
    ],

    // Database configuration
    'database' => [
        'type'     => 'none',
        'database' => 'leap',
        'username' => 'root',
        'password' => '',
        'host'     => 'localhost',
    ],
];
