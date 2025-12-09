<?php

return [
    'name' => 'Projectsteams',
    'description' => 'Projectsteams management module',
    'version' => '1.0.0',
    'routes' => [
        'api' => [
            'prefix' => 'api/projectsteams',
            'middleware' => ['api'],
        ],
        'web' => [
            'prefix' => 'projectsteams',
            'middleware' => ['web'],
        ],
    ],
];
