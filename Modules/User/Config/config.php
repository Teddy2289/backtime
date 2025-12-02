<?php

return [
    'name' => 'User',
    'description' => 'User management module',

    'middleware' => [
        'api' => [
            \Modules\User\Presentation\Middleware\UserMiddleware::class,
        ],
    ],

    'permissions' => [
        'view users',
        'create users',
        'edit users',
        'delete users',
    ],

    'roles' => [
        'admin' => [
            'view users',
            'create users',
            'edit users',
            'delete users',
        ],
        'user' => [
            'view own profile',
            'edit own profile',
        ],
    ],
];
