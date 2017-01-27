<?php
use Leap\App\Controllers\BasicController;

return [
    // Settings applied to all routes
    '**'  => [
        'abstract'   => true,
        'parameters' => [
            'stylesheets[]' => [
                'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css',
                'stylesheets/always.less'
            ],
            'scripts[]'     => [
                '//code.jquery.com/jquery-1.12.0.min.js',
                'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js',
                'https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js',
                '/app/js/always.js'
            ]
        ]
    ],

    // Homepage
    '/'   => [
        'callback'   => BasicController::class . '@renderPage',
        'parameters' => [
            'title' => '',
            'page'  => 'app:pages/home.php',
        ]
    ],

    // Page from url
    '{p}' => [
        'page'       => 'pages/{p}.php',
        'callback'   => BasicController::class . '@renderPage',
        'parameters' => [
            'page' => 'app:pages/{p}.php',
        ]
    ]
];
