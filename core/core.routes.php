<?php
return [
    '404'               => [
        'title'  => '404',
        'page'   => 'pages/404.php',
        'weight' => 10
    ],
    'permission-denied' => [
        'callback'  => \Leap\Core\Controller::class . "@render",
        'title'  => 'Permission denied',
        'page'   => 'pages/permission-denied.php',
        'weight' => 10,
    ],
];
