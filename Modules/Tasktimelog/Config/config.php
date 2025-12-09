<?php

return [
    'name' => 'Tasktimelog',
    'description' => 'Tasktimelog management module',
    'version' => '1.0.0',
    'routes' => [
        'api' => [
            'prefix' => 'api/tasktimelog',
            'middleware' => ['api'],
        ],
        'web' => [
            'prefix' => 'tasktimelog',
            'middleware' => ['web'],
        ],
    ],
];
