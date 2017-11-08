<?php
use Leap\App\Controllers\BasicController;

return [
    // Settings applied to all routes
    '(**)'  => [
        'abstract'   => true,
        'parameters' => [
            'stylesheets[]' => [
                '//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css',
                'file:stylesheets/always.less'
            ],
            'scripts[]'     => [
                '//code.jquery.com/jquery-1.12.0.min.js',
                '//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js',
                'url:js/always.js'
            ]
        ]
    ],

    // Homepage
    '/'   => [
        'callback'   => BasicController::class . '@renderPage',
        'parameters' => [
            'title' => '',
            'page'  => 'file:pages/home.php',
        ]
    ],

    // Page from url
    '{page}' => [
        'callback'   => BasicController::class . '@renderPage',
        'parameters' => [
            'page' => 'file:pages/{page}.php',
        ],
     ],

    '404' => [
        'callback'   => BasicController::class . '@renderPage',
        'parameters' => [
            'page' => 'file:pages/404.php',
        ],
    ],

    'permission-denied' => [
        'callback'   => BasicController::class . '@renderPage',
        'parameters' => [
            'page' => 'file:pages/permission-denied.php',
        ],
    ],
];
