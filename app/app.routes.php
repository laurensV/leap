<?php
return [
    // Settings applied to all routes
    '*'  => [
        'abstract' => true,
        'include_slash' => true,
        'stylesheets'   => [
            'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css',
            'stylesheets/always.less'
        ],
        'scripts'       => [
            '//code.jquery.com/jquery-1.12.0.min.js',
            'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js',
            'https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js',
            '/app/js/always.js'
        ]
    ],

    // Homepage
    ''   => [
        'title' => '',
        'page'  => 'pages/home.php',
        'callback' => \Leap\Core\Controller::class
    ],

    // Page from url
    '{p}' => [
        'page' => 'pages/{p}.php',
        'callback' => \Leap\Core\Controller::class
    ]
];
